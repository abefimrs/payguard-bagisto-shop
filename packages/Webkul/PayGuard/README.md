# PayGuard for Bagisto

[![Packagist Version](https://img.shields.io/packagist/v/payguard/bagisto-payguard)](https://packagist.org/packages/payguard/bagisto-payguard)
[![Bagisto](https://img.shields.io/badge/Bagisto-2.x-orange)](https://bagisto.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)

Accept **bKash** and **Nagad** payments in your Bagisto store, powered by the [PayGuard](https://app.sourcemonkey.online) payment gateway — built specifically for Bangladesh merchants.

---

## Features

- bKash and Nagad as separate checkout payment options
- Full redirect flow: order created → PayGuard → bKash/Nagad → callback
- Signed webhook (HMAC-SHA256) for reliable server-to-server payment confirmation
- Invoice auto-created on `payment.success` webhook
- Per-method connection IDs (so you can have multiple bKash/Nagad accounts)
- Admin configuration panel — no code changes needed after install

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | ^8.1 |
| Bagisto | ^2.0 |
| PayGuard account | [app.sourcemonkey.online](https://app.sourcemonkey.online) |

---

## Installation

```bash
composer require payguard/bagisto-payguard
php artisan optimize:clear
```

That's it — Laravel auto-discovery registers the service provider automatically.

---

## Configuration

### 1. PayGuard Dashboard setup

Log into [app.sourcemonkey.online](https://app.sourcemonkey.online) and collect:

- **API Key** → API & Webhooks → Generate Key
- **Webhook Secret** → Connections → Edit → Webhook Secret
- **bKash Connection ID** → Connections → your bKash connection → ID column
- **Nagad Connection ID** → Connections → your Nagad connection → ID column

Set your webhook URL in PayGuard to:
```
https://yourdomain.com/api/payguard/webhook
```

### 2. Bagisto Admin Panel

**Configuration → Sales → PayGuard Settings**
| Field | Value |
|---|---|
| API Key | From PayGuard dashboard |
| API Base URL | `https://app.sourcemonkey.online/api/v1` |
| Webhook Secret | From PayGuard dashboard |

**Configuration → Sales → PayGuard - bKash**
| Field | Value |
|---|---|
| Status | Yes |
| Title | bKash (shown to customers) |
| Connection ID | Your bKash connection ID |

**Configuration → Sales → PayGuard - Nagad**
| Field | Value |
|---|---|
| Status | Yes |
| Title | Nagad (shown to customers) |
| Connection ID | Your Nagad connection ID |

---

## How it works

```
Customer clicks "Place Order"
    → PayGuardController::redirect()
        → Creates Bagisto order
        → Opens PayGuard transaction (POST /transactions)
        → Initiates bKash/Nagad (POST /bkash/initiate/{id})
        → Redirects customer to checkout_url

Customer completes bKash/Nagad payment
    → Browser returns to /payguard/callback/{provider}
        → Shows success/failure page

PayGuard server calls your webhook (POST /api/payguard/webhook)
    → Signature verified (HMAC-SHA256)
    → Order marked paid + invoice created  ← authoritative
```

The webhook is the source of truth — order fulfilment happens here, independently of whether the customer's browser makes it back to your site.

---

## Webhooks in local development

PayGuard can't reach `localhost`. Use [ngrok](https://ngrok.com) to expose your local server:

```bash
ngrok http 8000
# copy the https URL, e.g. https://a1b2c3d4.ngrok-free.app

# temporarily update APP_URL in .env:
APP_URL=https://a1b2c3d4.ngrok-free.app
php artisan config:clear
```

---

## Changelog

### 1.0.0
- Initial release: bKash + Nagad via PayGuard, full redirect + webhook flow

---

## License

MIT — see [LICENSE](LICENSE).

## Support

- PayGuard docs: [app.sourcemonkey.online/docs](https://app.sourcemonkey.online/docs)
- Issues: [github.com/abefimrs/bagisto-payguard/issues](https://github.com/abefimrs/bagisto-payguard/issues)
