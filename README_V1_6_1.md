# Trendy Deal BD v1.6.1 — Checkout + Success Hotfix

This patch fixes the order-success Blade ParseError and improves desktop quick checkout.

## Fixed
- `resources/views/store/success.blade.php`: removed complex `@json(...)` expression that Blade could misparse.
- Purchase browser event now builds the payload in a PHP block and outputs it with `Illuminate\Support\Js::from()`.
- Desktop `অর্ডার করুন` now keeps the AJAX add-to-cart -> popup checkout flow.
- Desktop checkout is a centered responsive modal with customer information on the left and cart/order summary on the right.
- Tablet collapses to a single-column modal.
- Mobile remains a bottom-sheet popup.
- Quick-order AJAX failures no longer silently fall back to a normal form submit.
- Asset query version updated to `v=1.6.1` to avoid stale browser CSS/JS.

## Install
Stop `php artisan serve`, extract this patch into the project root with overwrite enabled, then run:

```powershell
php artisan optimize:clear
php artisan view:clear
php artisan serve
```

No migration and no Composer install are required.
