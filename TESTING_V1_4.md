# Trendy Deal BD v1.4 editor test

1. Start Laravel: `php artisan serve`
2. Open `/admin/products/create`.
3. Confirm sidebar stays on the left and no raw `@endsection` text appears.
4. Test both Short Description and Full Product Details:
   - bold / italic / underline / strike
   - font family and size
   - text/background colors
   - left/center/right/justify
   - line height
   - bullet and numbered lists
   - link/unlink
   - Image URL and Upload Image
   - Video URL and Upload Video
   - HTML / Embed (iframe supported; scripts are stripped on save)
   - fullscreen
5. Save a product and reopen it. Formatting must remain.
6. Open the public product page. Short description and full description formatting must render.
