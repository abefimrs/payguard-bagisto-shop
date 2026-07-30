@php
    $pgAdditional = $order->payment->additional ?? [];
    if (is_string($pgAdditional)) {
        $pgAdditional = json_decode($pgAdditional, true) ?? [];
    }
    $pgTxnId = $pgAdditional['payguard_txn_id'] ?? null;
@endphp

@if ($pgTxnId && in_array($order->payment->method, ['payguard_bkash', 'payguard_nagad']))
    <div class="mt-2 flex gap-2 text-sm">
        <span class="font-semibold text-gray-800 dark:text-white">
            Transaction ID:
        </span>
        <span class="text-gray-600 dark:text-gray-300 font-mono">
            {{ $pgTxnId }}
        </span>
    </div>
@endif
