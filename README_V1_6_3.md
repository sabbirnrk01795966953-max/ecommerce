# Trendy Deal BD v1.6.3 — OMS Settings + Test Connection

This patch fixes OMS enable persistence and adds dedicated OMS save/test controls.

## Changes
- Removes duplicate hidden+checkbox OMS toggle submission.
- OMS toggle is read directly from DB and saved as literal `1` / `0`.
- Adds dedicated **Save OMS Settings** AJAX endpoint.
- Adds **Test Connection + Send Demo Data**.
- Test uses the API key entered in the field, or the already-saved API key when the field is blank.
- Test does not create a local Laravel order, but it sends a clearly marked demo order to the configured OMS endpoint.
- Shows HTTP status, test invoice ID, external order ID, and OMS response in the admin page.
- Secret inputs renamed to `*_new` to reduce browser/password-manager accidental overwrites.

## Install
Extract this patch into the project root with overwrite enabled, then run:

```powershell
php artisan optimize:clear
php artisan view:clear
php artisan route:list --name=admin.settings
php artisan serve
```

No migration and no Composer install are required.
