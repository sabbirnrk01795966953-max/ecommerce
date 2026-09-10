# Local testing checklist

1. `composer install` (Laravel 13 uses `laravel/tinker ^3.0`)
2. `copy .env.example .env`
3. `php artisan key:generate`
4. Ensure `database/database.sqlite` exists: PowerShell `New-Item database/database.sqlite -ItemType File -Force`
5. `php artisan migrate --seed`
6. `php artisan storage:link`
7. `php artisan serve`
8. Open `http://127.0.0.1:8000`
9. Admin: `http://127.0.0.1:8000/admin/login`
10. Default local admin is from `.env` (`admin@trendydealbd.shop` / `ChangeMe123!`) — change it before production.

Important URL behavior:
- Product Last URL empty => generated from English product name, e.g. `heavy-duty-multi-function-vegetable-peeler-394`.
- Product URL => `/product/heavy-duty-multi-function-vegetable-peeler-394`.
- Subcategory Last URL empty => generated from its name.
- Subcategory URL => `/subcategory/leather-stickers-patch`.
- If an admin pastes a full URL into Last URL, only the final segment is saved.

## Bootstrap cache directory
The project includes `bootstrap/cache/.gitignore` so Laravel package discovery can write its package manifest. If an older extracted copy is missing it, create it with:

```powershell
New-Item -ItemType Directory -Force bootstrap\cache
Set-Content bootstrap\cache\.gitignore "*`n!.gitignore"
```
