<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Subcategory;
use App\Services\RichTextSanitizer;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = Product::query()->with('subcategory');

        if ($r->filled('q')) {
            $term = $r->q;
            $q->where(fn ($x) => $x
                ->where('name_bn', 'like', "%$term%")
                ->orWhere('name_en', 'like', "%$term%")
                ->orWhere('sku', 'like', "%$term%"));
        }

        return view('admin.products.index', [
            'products' => $q->latest()->paginate(30)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product,
            'subcategories' => Subcategory::with('category')->orderBy('sort_order')->get(),
            'uploadLimits' => $this->uploadLimits(),
        ]);
    }

    public function store(Request $r)
    {
        $data = $this->data($r);
        $data['slug'] = SlugService::unique(
            SlugService::normalizeOrGenerate($data['slug'] ?? null, $data['name_en'] ?: $data['name_bn'], 'product'),
            Product::class
        );

        $this->uploads($r, $data);
        $product = Product::create($data);
        $this->gallery($r, $product);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $product->load('images');

        return view('admin.products.form', [
            'product' => $product,
            'subcategories' => Subcategory::with('category')->orderBy('sort_order')->get(),
            'uploadLimits' => $this->uploadLimits(),
        ]);
    }

    public function update(Request $r, Product $product)
    {
        $data = $this->data($r, $product);
        $data['slug'] = SlugService::unique(
            SlugService::normalizeOrGenerate($data['slug'] ?? null, $data['name_en'] ?: $data['name_bn'], 'product'),
            Product::class,
            $product->id
        );

        $this->uploads($r, $data, $product);
        $product->update($data);
        $this->gallery($r, $product);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function deleteImage(Product $product, int $image)
    {
        $img = $product->images()->findOrFail($image);
        Storage::disk('public')->delete($img->path);
        $img->delete();

        return back()->with('success', 'Image removed.');
    }

    public function uploadEditorMedia(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'expected_type' => ['required', Rule::in(['image', 'video'])],
        ]);

        $file = $request->file('file');
        $expectedType = $request->string('expected_type')->toString();
        $mime = strtolower((string) $file->getMimeType());

        if ($expectedType === 'image') {
            validator(['file' => $file], [
                'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            ])->validate();

            $path = $file->store('products/editor/images', 'public');

            return response()->json([
                'type' => 'image',
                'url' => Storage::disk('public')->url($path),
                'path' => $path,
            ]);
        }

        $allowedVideoMimes = ['video/mp4', 'video/webm', 'video/quicktime'];
        if (! in_array($mime, $allowedVideoMimes, true)) {
            throw ValidationException::withMessages([
                'file' => 'Only MP4, WebM or MOV video files are allowed.',
            ]);
        }

        $path = $file->store('products/editor/videos', 'public');

        return response()->json([
            'type' => 'video',
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
        ]);
    }

    private function data(Request $r, ?Product $product = null): array
    {
        $data = $r->validate([
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'name_bn' => 'required|string|max:240',
            'name_en' => 'nullable|string|max:240',
            'slug' => ['nullable', 'string', 'max:500'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock_qty' => 'required|integer|min:0',
            'short_description' => 'nullable|string|max:15000',
            'description_html' => 'nullable|string|max:1000000',
            'meta_title' => 'nullable|string|max:240',
            'meta_description' => 'nullable|string|max:500',
            'video_url' => 'nullable|url|max:2000',
            'main_image' => 'nullable|image|max:6144',
            'gallery.*' => 'nullable|image|max:6144',
            'video_file' => [
                'nullable',
                'file',
                'max:51200',
                function (string $attribute, $value, $fail): void {
                    $extension = strtolower((string) $value->getClientOriginalExtension());
                    if (! in_array($extension, ['mp4', 'webm', 'mov'], true)) {
                        $fail('মূল ভিডিও শুধু MP4, WebM অথবা MOV হতে পারবে।');
                    }
                },
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ], [
            'video_file.uploaded' => 'ভিডিও ফাইল PHP/server upload limit-এর কারণে server-এ পৌঁছাতে পারেনি। নিচের Server Upload Limit দেখুন এবং upload_max_filesize/post_max_size বাড়ান।',
            'video_file.max' => 'মূল product video সর্বোচ্চ 50MB হতে পারবে।',
            'video_file.file' => 'নির্বাচিত video file টি valid upload নয়।',
            'main_image.uploaded' => 'Main image server upload limit-এর কারণে upload হয়নি।',
            'gallery.*.uploaded' => 'Gallery-এর একটি image server upload limit-এর কারণে upload হয়নি।',
        ]);

        $data['short_description'] = RichTextSanitizer::clean($data['short_description'] ?? null);
        $data['description_html'] = RichTextSanitizer::clean($data['description_html'] ?? null);
        $data['is_active'] = $r->boolean('is_active');
        $data['is_featured'] = $r->boolean('is_featured');
        $data['sort_order'] = (int) $r->input('sort_order', 0);

        return $data;
    }

    private function uploads(Request $r, array &$data, ?Product $product = null): void
    {
        if ($r->hasFile('main_image')) {
            if ($product?->main_image_path) {
                Storage::disk('public')->delete($product->main_image_path);
            }
            $data['main_image_path'] = $r->file('main_image')->store('products/main', 'public');
        }

        if ($r->hasFile('video_file')) {
            if ($product?->video_path) {
                Storage::disk('public')->delete($product->video_path);
            }
            $data['video_path'] = $r->file('video_file')->store('products/video', 'public');
        }
    }

    private function gallery(Request $r, Product $product): void
    {
        $newImages = [];

        foreach ($r->file('gallery', []) as $i => $file) {
            $newImages[$i] = $product->images()->create([
                'path' => $file->store('products/gallery', 'public'),
                'alt_text' => $product->name_bn ?: $product->name_en,
                'sort_order' => 100000 + $i,
            ]);
        }

        $sequence = json_decode((string) $r->input('gallery_order', '[]'), true);
        $sequence = is_array($sequence) ? $sequence : [];

        $existing = $product->images()->get()->keyBy('id');
        $used = [];
        $sortOrder = 0;

        foreach ($sequence as $token) {
            if (! is_string($token)) {
                continue;
            }

            if (str_starts_with($token, 'existing:')) {
                $id = (int) substr($token, 9);
                $image = $existing->get($id);
                if ($image && ! isset($used[$image->id])) {
                    $image->update(['sort_order' => $sortOrder++]);
                    $used[$image->id] = true;
                }
                continue;
            }

            if (str_starts_with($token, 'new:')) {
                $index = (int) substr($token, 4);
                $image = $newImages[$index] ?? null;
                if ($image && ! isset($used[$image->id])) {
                    $image->update(['sort_order' => $sortOrder++]);
                    $used[$image->id] = true;
                }
            }
        }

        // Keep any images that were not present in the browser sequence.
        foreach ($product->images()->orderBy('sort_order')->get() as $image) {
            if (! isset($used[$image->id])) {
                $image->update(['sort_order' => $sortOrder++]);
            }
        }
    }

    private function uploadLimits(): array
    {
        $uploadMax = $this->iniBytes(ini_get('upload_max_filesize'));
        $postMax = $this->iniBytes(ini_get('post_max_size'));
        $appVideoMax = 50 * 1024 * 1024;

        $limits = array_values(array_filter([$appVideoMax, $uploadMax, $postMax], fn (int $value) => $value > 0));
        $effective = $limits ? min($limits) : $appVideoMax;

        return [
            'upload_max_bytes' => $uploadMax,
            'post_max_bytes' => $postMax,
            'app_video_max_bytes' => $appVideoMax,
            'effective_video_max_bytes' => $effective,
            'upload_max_label' => $this->bytesLabel($uploadMax),
            'post_max_label' => $this->bytesLabel($postMax),
            'effective_video_max_label' => $this->bytesLabel($effective),
            'server_too_small' => ($uploadMax > 0 && $uploadMax < $appVideoMax) || ($postMax > 0 && $postMax < $appVideoMax),
        ];
    }

    private function iniBytes(string|false $value): int
    {
        if ($value === false) {
            return 0;
        }

        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function bytesLabel(int $bytes): string
    {
        if ($bytes <= 0) {
            return 'Unlimited / unknown';
        }

        if ($bytes >= 1024 * 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / (1024 * 1024 * 1024), 2), '0'), '.').' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / (1024 * 1024), 2), '0'), '.').' MB';
        }

        if ($bytes >= 1024) {
            return rtrim(rtrim(number_format($bytes / 1024, 2), '0'), '.').' KB';
        }

        return $bytes.' B';
    }
}
