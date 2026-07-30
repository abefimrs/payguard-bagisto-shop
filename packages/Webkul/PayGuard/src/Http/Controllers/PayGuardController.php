<?php

namespace Webkul\PayGuard\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\PayGuard\Services\PayGuardClient;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class PayGuardController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository
    ) {}

    /**
     * Step 1: called from checkout ("Place Order") via Payment::getRedirectUrl().
     * Places the Bagisto order, opens a PayGuard transaction, and redirects
     * the customer to the bKash/Nagad checkout page.
     */
    public function redirect(Request $request, string $provider)
    {
        abort_unless(in_array($provider, ['bkash', 'nagad']), 404);

        if (Cart::hasError() || ! Cart::getCart()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        Cart::collectTotals();

        $cart = Cart::getCart();

        DB::beginTransaction();

        try {
            // Verified against packages/Webkul/Paypal/src/Http/Controllers/StandardController.php
            // (the same pattern used by PayPal, Stripe, Razorpay, PhonePe in this codebase).
            $data  = (new OrderResource($cart))->jsonSerialize();
            $order = $this->orderRepository->create($data);

            Cart::deActivateCart();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('PayGuard: order placement failed', ['error' => $e->getMessage()]);

            session()->flash('error', 'We could not start your order. Please try again.');

            return redirect()->route('shop.checkout.cart.index');
        }

        $paymentCode = 'payguard_'.$provider;

        $connectionId = core()->getConfigData("sales.payment_methods.{$paymentCode}.connection_id");

        try {
            $client = new PayGuardClient;

            $transaction = $client->createTransaction([
                'mfs_connection_id' => (int) $connectionId,
                'amount'            => round($order->grand_total, 2),
                'reference_id'      => $order->increment_id,
                'customer_name'     => trim($order->customer_first_name.' '.$order->customer_last_name) ?: 'Guest',
                'customer_number'   => $order->billing_address->phone ?? '',
                'customer_email'    => $order->customer_email ?? '',
                'callback_url'      => route('payguard.callback', $provider),
                'webhook_url'       => route('payguard.webhook'),
                'metadata'          => [
                    'order_id'     => (string) $order->id,
                    'increment_id' => $order->increment_id,
                ],
            ]);

            $initiator = $provider === 'bkash' ? 'initiateBkash' : 'initiateNagad';
            $payment   = $client->{$initiator}($transaction['id']);

            session(['payguard_pending_order' => $order->increment_id]);

            $checkoutUrl = $payment['checkout_url'] ?? $payment['callBackUrl'] ?? null;

            if (! $checkoutUrl) {
                throw new Exception('PayGuard did not return a checkout URL.');
            }

            return redirect()->away($checkoutUrl);
        } catch (Exception $e) {
            Log::error('PayGuard: payment initiation failed', [
                'order' => $order->increment_id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Payment could not be started: '.$e->getMessage());

            return redirect()->route('shop.checkout.cart.index');
        }
    }

    /**
     * Step 2: browser return (PayGuard's `callback_url`). This is only for
     * showing the customer the right page — order fulfilment is driven by
     * the signed webhook() below, which is authoritative.
     */
    public function callback(Request $request, string $provider)
    {
        Log::info('PayGuard callback', $request->all());

        // PayGuard sometimes HTML-encodes the callback URL before appending
        // query params, producing keys like "amp;txn_id". Parse the raw query
        // string ourselves to get clean field names.
        $params = [];
        parse_str(html_entity_decode($request->server('QUERY_STRING', '')), $params);

        $status      = $params['status'] ?? $request->query('status');
        $txnId       = $params['txn_id'] ?? $request->query('txn_id');
        $referenceId = $params['reference_id'] ?? $request->query('reference_id') ?? session('payguard_pending_order');

        session()->forget('payguard_pending_order');

        if ($status !== 'success') {
            session()->flash('error', 'Your payment was not completed. You can try again or choose another payment method.');

            return redirect()->route('shop.checkout.cart.index');
        }

        try {
            $order = $referenceId
                ? $this->orderRepository->findOneByField('increment_id', $referenceId)
                : null;

            if ($order) {
                $this->saveTransactionId($order, $txnId);

                if ($order->canInvoice()) {
                    $this->markOrderPaid($order);
                }
            }
        } catch (Exception $e) {
            Log::warning('PayGuard: callback-time processing skipped', ['error' => $e->getMessage()]);
        }

        session()->flash('order_id', $order->id ?? null);

        return redirect()->route('shop.checkout.onepage.success');
    }

    /**
     * Step 3: server-to-server IPN. Signed, reliable, authoritative.
     */
    public function webhook(Request $request)
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('X-PayGuard-Signature');
        $secret    = core()->getConfigData('sales.payment_methods.payguard.webhook_secret');

        if (! PayGuardClient::verifySignature($rawBody, $signature, (string) $secret)) {
            Log::warning('PayGuard webhook: invalid signature');

            return response('Unauthorized', 401);
        }

        $payload = $request->json()->all();

        Log::info('PayGuard webhook received', ['event' => $payload['event'] ?? null, 'reference_id' => $payload['reference_id'] ?? null]);

        if (($payload['event'] ?? null) !== 'payment.success') {
            return response('OK', 200);
        }

        $referenceId = $payload['reference_id'] ?? null;

        if (! $referenceId) {
            return response('OK', 200);
        }

        try {
            $order = $this->orderRepository->findOneByField('increment_id', $referenceId);

            if ($order) {
                // Webhook payload may carry txn_id directly
                $txnId = $payload['txn_id'] ?? $payload['transaction_id'] ?? null;
                $this->saveTransactionId($order, $txnId);

                if ($order->canInvoice()) {
                    $this->markOrderPaid($order);
                }
            }
        } catch (Exception $e) {
            Log::error('PayGuard webhook: failed to mark order paid', [
                'reference_id' => $referenceId,
                'error'        => $e->getMessage(),
            ]);

            // Return 200 anyway so PayGuard doesn't hammer retries for a
            // problem that only a human can fix; the failure is logged above.
        }

        return response('OK', 200);
    }

    /**
     * Persist the PayGuard transaction ID onto the order's payment record.
     */
    protected function saveTransactionId($order, ?string $txnId): void
    {
        if (! $txnId || ! $order->payment) {
            return;
        }

        $additional = $order->payment->additional ?? [];

        if (is_string($additional)) {
            $additional = json_decode($additional, true) ?? [];
        }

        $additional['payguard_txn_id'] = $txnId;

        $order->payment->additional = $additional;
        $order->payment->save();
    }

    /**
     * Create a full invoice for the order, marking it paid.
     *
     * NOTE: verify this against your installed Bagisto version — invoice
     * item shape can vary slightly between 1.x and 2.x. Test one full
     * end-to-end order in sandbox before going live.
     */
    protected function markOrderPaid($order): void
    {
        $invoiceData = [
            'order_id' => $order->id,
            'invoice'  => ['items' => []],
        ];

        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        $this->invoiceRepository->create($invoiceData);
    }
}
