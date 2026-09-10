# Trendy Deal BD v1.5 Media Manager

## Why the main product video showed "The video file failed to upload"

That Laravel message means PHP marked the upload as invalid before normal Laravel video validation completed. The most common cause is `upload_max_filesize` or `post_max_size` being smaller than the selected video. The form now shows the currently detected PHP limits and blocks oversized selections before submit with a clear message.

Check current Windows PHP limits:

```powershell
php -i | Select-String "upload_max_filesize|post_max_size|max_execution_time|max_input_time"
```

For local testing, start Laravel with limits large enough for the application's 50 MB video limit:

```powershell
php -d upload_max_filesize=100M -d post_max_size=120M -d memory_limit=256M -d max_execution_time=300 -d max_input_time=300 artisan serve
```

## Media improvements

- Main image previews immediately after selection.
- Main product video previews immediately and can be played before saving.
- Existing saved main image/video still preview on Edit Product.
- Gallery images preview immediately after multi-select.
- Existing + newly selected gallery images share one Gallery Organizer.
- Drag cards to reorder, or use left/right arrow buttons.
- The numbered badge shows final gallery order.
- Remove newly selected gallery images before saving.
- Existing gallery delete action remains available.
- Final gallery order persists in `product_images.sort_order` (no migration needed).
- Video URL can be previewed before saving.
- Editor video upload uses the same client-side server-size check.

## Files changed

- app/Http/Controllers/Admin/ProductController.php
- resources/views/admin/products/form.blade.php
- public/assets/admin.js
- public/assets/admin.css

No database migration is required.
