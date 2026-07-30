# BDShop — Bangladesh E-Commerce Platform

A production-ready Bagisto e-commerce store built for Bangladesh merchants, integrated with [PayGuard](https://app.sourcemonkey.online) for bKash and Nagad payments.

**Live demo:** [shop.sourcemonkey.online](https://shop.sourcemonkey.online)
**Admin panel:** [shop.sourcemonkey.online/admin](https://shop.sourcemonkey.online/admin)

---

## Stack

| Layer | Technology |
|---|---|
| Framework | [Bagisto](https://bagisto.com) (Laravel 11) |
| Language | PHP 8.2 |
| Database | MySQL 8 |
| Cache / Queue | Redis |
| Frontend | Vue.js + Vite |
| Payment | [PayGuard](https://app.sourcemonkey.online) (bKash + Nagad) |
| Server | DigitalOcean Ubuntu 24.04 |
| Web Server | Nginx + PHP-FPM |

---

## Features

- 🛒 Full e-commerce storefront (browse → cart → checkout → order)
- 💳 bKash and Nagad payments via PayGuard redirect flow
- 🏷️ 5 product categories with 16 sample BD products (Fashion, Electronics, Grocery, Home & Living, Health & Beauty)
- 📦 Order management with PayGuard transaction ID displayed in admin
- 🔔 Signed webhook for reliable payment confirmation
- 🌐 BDT currency, Bangladesh locale

---

## Local Development Setup

### Prerequisites

- Ubuntu / Debian Linux desktop
- `sudo` access

### 1. Install

```bash
chmod +x install-bagisto-local.sh
./install-bagisto-local.sh
```

This installs PHP 8.4, MySQL, Composer, Node, and Bagisto at `~/bdshop-local`.

### 2. Start

```bash
cd ~/bdshop-local
php artisan serve --port=8000
```

- Storefront → http://localhost:8000
- Admin → http://localhost:8000/admin

### 3. Install the PayGuard plugin

```bash
# Copy the plugin into your Bagisto install
cp -r packages/Webkul/PayGuard ~/bdshop-local/packages/Webkul/PayGuard

# Register in bootstrap/providers.php (add this line):
# Webkul\PayGuard\Providers\PayGuardServiceProvider::class,

cd ~/bdshop-local
composer dump-autoload
php artisan optimize:clear
```

Then configure in **Admin → Configuration → Sales → PayGuard Settings**.

### 4. Seed sample products

```bash
cp database/seeders/BdShopDemoDataSeeder.php ~/bdshop-local/database/seeders/
cd ~/bdshop-local
php artisan db:seed --class="Database\Seeders\BdShopDemoDataSeeder"
```

### 5. Test payments locally (ngrok)

PayGuard's callback and webhook can't reach `localhost`. Use ngrok:

```bash
ngrok http 8000
# Copy the https URL, e.g. https://a1b2c3d4.ngrok-free.app
```

Temporarily set in `.env`:
```
APP_URL=https://a1b2c3d4.ngrok-free.app
```
Then `php artisan config:clear` and test a full checkout.

---

## Production Deployment

### Server: DigitalOcean Droplet (159.223.18.157)

```bash
# Copy and run the server setup script on the droplet
scp install-bagisto.sh user@159.223.18.157:~
ssh user@159.223.18.157
chmod +x install-bagisto.sh && ./install-bagisto.sh
```

### Point DNS

Add an **A record** for `shop.sourcemonkey.online` → `159.223.18.157`

### Enable HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d shop.sourcemonkey.online
```

### Deploy PayGuard plugin

```bash
cp -r packages/Webkul/PayGuard /var/www/bdshop/packages/Webkul/PayGuard
cd /var/www/bdshop
composer dump-autoload
php artisan optimize:clear
```

### Set PayGuard Webhook URL

In your PayGuard dashboard → Connections → Webhook URL:
```
https://shop.sourcemonkey.online/api/payguard/webhook
```

---

## PayGuard Configuration

Get credentials from [app.sourcemonkey.online](https://app.sourcemonkey.online):

| Admin Setting | Where to find it |
|---|---|
| API Key | Dashboard → API & Webhooks → Generate Key |
| Webhook Secret | Dashboard → Connections → Edit → Webhook Secret |
| bKash Connection ID | Dashboard → Connections → bKash row → ID |
| Nagad Connection ID | Dashboard → Connections → Nagad row → ID |

Configure under **Admin → Configuration → Sales → PayGuard Settings**.

---

## Project Structure

```
bdshop-local/
├── packages/
│   └── Webkul/
│       └── PayGuard/              ← PayGuard payment plugin
│           ├── composer.json
│           └── src/
│               ├── Config/        ← payment-methods.php, system.php
│               ├── Http/          ← PayGuardController (redirect/callback/webhook)
│               ├── Payment/       ← PayGuardBkash, PayGuardNagad
│               ├── Providers/     ← PayGuardServiceProvider
│               ├── Resources/     ← admin order view blade partial
│               ├── Routes/        ← web.php
│               └── Services/      ← PayGuardClient (API + signature verification)
├── database/
│   └── seeders/
│       └── BdShopDemoDataSeeder.php   ← 16 sample BD products
└── ...
```

---

## Payment Flow

```
Customer clicks "Place Order"
    → Creates Bagisto order
    → Opens PayGuard transaction (POST /transactions)
    → Initiates bKash/Nagad  (POST /bkash/initiate/{id})
    → Redirects to bKash/Nagad checkout

Customer completes payment
    → Browser returns to /payguard/callback/{provider}
    → Transaction ID saved to order
    → Success page shown

PayGuard server → POST /api/payguard/webhook
    → Signature verified (HMAC-SHA256)
    → Order marked paid + invoice created  ← source of truth
```

---

## Sample Product Categories

| Category | Subcategories |
|---|---|
| Fashion | Sarees, Panjabi & Kurta, Salwar Kameez, Kids' Wear |
| Electronics | Mobile Accessories, Home Appliances |
| Grocery & Food | Spices & Masala, Snacks & Bakery |
| Home & Living | Kitchen & Dining, Bedding & Linen |
| Health & Beauty | Skincare, Haircare |

---

## Related

- PayGuard API docs: [app.sourcemonkey.online/docs](https://app.sourcemonkey.online/docs)
- PayGuard backend: [github.com/abefimrs/payguard-backend](https://github.com/abefimrs/payguard-backend)
- PayGuard Bagisto plugin: [github.com/abefimrs/bagisto-payguard](https://github.com/abefimrs/bagisto-payguard)
- Bagisto docs: [devdocs.bagisto.com](https://devdocs.bagisto.com)
