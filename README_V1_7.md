# Trendy Deal BD v1.7 — Order Management Upgrade

## Changes

- New customer orders now use invoice format `TW` + six digits, for example `TW000123`.
- Existing orders keep their existing invoice IDs.
- The six-digit serial is based on the database order ID, which avoids reusing an invoice during normal operation.
- `external_order_id` for new website orders matches the same `TW######` code.
- Admin Orders page now has:
  - select-all checkbox for the current page;
  - per-order checkbox;
  - bulk status change;
  - selected-order counter;
  - individual View and Delete actions;
  - delete confirmation warning that deleting locally does not delete an already-sent OMS order.
- Order detail page also includes a delete button.

## No migration required

This update does not change the database schema.

## Install patch

```powershell
Ctrl + C
cd "D:\Trendy Deal bd\trendydealbd-shop-laravel"
Expand-Archive `
  -Path "$env:USERPROFILE\Downloads\trendydealbd-shop-laravel-v1.7-orders-patch.zip" `
  -DestinationPath "D:\Trendy Deal bd\trendydealbd-shop-laravel" `
  -Force
php artisan optimize:clear
php artisan view:clear
php artisan route:list --name=admin.orders
php artisan serve
```

Do not run `migrate:fresh`.
