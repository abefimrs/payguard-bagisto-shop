# PayGuard for Bagisto

Adds **bKash** and **Nagad** as checkout payment methods, powered by your
PayGuard gateway (`https://app.sourcemonkey.online`).

Built against the documented PayGuard API v1.0 and Bagisto's official
payment-method extension architecture (`devdocs.bagisto.com/payment-method-development`).

## What it does

- Adds two payment options at checkout: **bKash** and **Nagad**
- On "Place Order": creates the Bagisto order, opens a PayGuard transaction, redirects the customer to the bKash/Nagad checkout page
- On return: shows the customer the right success/failure page
- On PayGuard's signed webhook (`payment.success`): marks the order paid and generates the invoice — this is the source of truth, independent of whether the customer's browser makes it back to your site
- Verifies every webhook with HMAC-SHA256 before trusting it

## 1. Install

From your Bagisto root directory:

```bash
# 1. Copy this package in
cp -r packages/Webkul/PayGuard  <your-bagisto-root>/packages/Webkul/PayGuard

# 2. Install the PayGuard PHP SDK
composer require payguard/sdk
```

Add the PSR-4 autoload entry to your **root** `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "Webkul\\PayGuard\\": "packages/Webkul/PayGuard/src"
        }
    }
}
```

Register the service provider in `bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    // ... other providers ...
    Webkul\PayGuard\Providers\PayGuardServiceProvider::class,
];
```

Then:

```bash
composer dump-autoload
php artisan optimize:clear
```

## 2. Configure

**Admin Panel → Configuration → Sales → PayGuard Settings**
- API Key — from PayGuard Dashboard → API & Webhooks → Generate Key
- API Base URL — `https://app.sourcemonkey.online/api/v1`
- Webhook Secret — from PayGuard Dashboard → Connections → Edit → Webhook Secret

**Admin Panel → Configuration → Sales → PayGuard - bKash / PayGuard - Nagad**
- Status → Yes
- Title / Description — shown to customers at checkout
- Connection ID — the `mfs_connection_id` for that specific bKash/Nagad connection (from your PayGuard dashboard's Connections list)

## 3. Point PayGuard at your webhook

In the PayGuard dashboard, set your **default IPN URL** (or rely on the
per-transaction `webhook_url` this plugin already sends) to:

```
https://shop.sourcemonkey.online/api/payguard/webhook
```

This endpoint is CSRF-exempt (it's a server-to-server call from PayGuard, not
a browser) but every request is signature-verified — do not remove that check.

If you're on Laravel 11's `bootstrap/app.php` middleware style, also add a
belt-and-suspenders exclusion:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/payguard/webhook',
    ]);
})
```

## 4. Test end-to-end before going live

1. Place a real small-value order through checkout, choose bKash
2. Confirm you land on PayGuard's checkout, complete payment
3. Confirm you're redirected back and the order shows **Paid** in Admin → Sales → Orders
4. Check `storage/logs/laravel.log` for `PayGuard webhook received` — this confirms the webhook path actually works before you rely on it in production
5. Repeat for Nagad

## Known area to double-check for your exact Bagisto version

`PayGuardController::markOrderPaid()` builds an invoice directly via
`InvoiceRepository`. Bagisto's exact invoice-array shape has shifted slightly
across 1.x/2.x point releases — if invoice creation throws in your logs after
a real test payment, compare against Admin → an existing order → "Invoice" to
see the field names your installed version expects, and adjust
`markOrderPaid()` accordingly. Everything else (API calls, signature
verification, routes, admin config) follows the documented, stable contracts.

## Files

```
src/
├── Config/
│   ├── payment-methods.php   # registers payguard_bkash / payguard_nagad
│   └── system.php            # admin settings fields
├── Payment/
│   ├── AbstractPayGuardPayment.php
│   ├── PayGuardBkash.php
│   └── PayGuardNagad.php
├── Services/
│   └── PayGuardClient.php    # REST calls + webhook signature verification
├── Http/Controllers/
│   └── PayGuardController.php # redirect / callback / webhook
├── Providers/
│   └── PayGuardServiceProvider.php
└── Routes/
    └── web.php
```
