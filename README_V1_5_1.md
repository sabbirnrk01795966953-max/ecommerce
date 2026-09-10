# Trendy Deal BD v1.5.1 hotfix

Fixes Blade ParseError in `resources/views/admin/products/form.blade.php` caused by passing a complex PHP array directly into Blade's `@json(...)` directive.

Replace the file and run:

```powershell
php artisan optimize:clear
php artisan view:clear
php artisan serve
```

No database migration or Composer install is required.
