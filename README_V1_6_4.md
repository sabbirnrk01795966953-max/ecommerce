# Trendy Deal BD v1.6.4 — OMS Enable State Hotfix

This hotfix fixes the OMS checkbox being saved as enabled but immediately displayed/read as disabled.

Changes:
- Adds `Setting::isEnabled()` to normalize SQLite/MySQL/PDO values (`1`, `"1"`, `true`, etc.).
- OMS save sends explicit `1` / `0` instead of a JSON boolean.
- OMS controller re-reads the stored database value safely and returns it to the page.
- OMS runtime sender uses the same safe enabled-state reader.
- Save result displays the raw database value for verification.

No database migration is required.
No Composer install is required.
