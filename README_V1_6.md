# Trendy Deal BD v1.6 — Quick Checkout + Mobile Sticky Order

This patch changes customer-side order behavior only. It does not include `.env`, database files, `vendor`, or uploads.

## New behavior

- Clicking **অর্ডার করুন** on a product card or product page adds that product to the existing cart with AJAX.
- The cart counter updates immediately.
- A checkout popup opens immediately after the item is added.
- Desktop: checkout opens as a right-side drawer.
- Mobile: checkout opens as a bottom sheet.
- Existing products already in cart stay in cart and are shown in the popup.
- Checkout can be completed inside the popup without first visiting `/cart` or `/checkout`.
- Server validation errors are shown inside the popup.
- The existing normal cart and checkout pages continue to work.
- Meta events remain supported: AddToCart, InitiateCheckout, and Purchase deduplication flow.
- On mobile product pages, after the original order bar has been scrolled past, a sticky order bar appears immediately above the existing bottom navigation.
- Clicking the sticky button uses the quantity currently selected in the main product order bar, adds the product to cart, and opens checkout.

## Files changed

- `app/Http/Controllers/CartController.php`
- `app/Http/Controllers/CheckoutController.php`
- `routes/web.php`
- `resources/views/layouts/store.blade.php`
- `resources/views/store/product.blade.php`
- `resources/views/store/partials/product-card.blade.php`
- `resources/views/store/partials/checkout-popup.blade.php` (new)
- `public/assets/app.js`
- `public/assets/app.css`

## Install

Stop `php artisan serve`, extract this ZIP over the Laravel project root with overwrite enabled, then run:

```powershell
php artisan optimize:clear
php artisan route:list --name=checkout.quick
php artisan serve
```

No Composer install and no migration are required.

## Test

1. Open the home page and click **অর্ডার করুন** on a product card. Confirm the popup opens and the product is in the checkout cart.
2. Close the popup, add a second product, and confirm both products appear.
3. Open a product page, change quantity to 2, click **অর্ডার করুন**, and confirm quantity 2 is added.
4. On a mobile-width browser, scroll below the normal order button. Confirm the sticky order bar appears above the bottom navigation.
5. Click the sticky button and confirm the checkout popup opens.
6. Submit a test order and confirm the normal success page opens.
