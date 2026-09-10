@extends('layouts.admin')

@section('title', $product->exists ? 'পণ্য এডিট' : 'নতুন পণ্য')
@section('page_title', $product->exists ? 'পণ্য এডিট' : 'নতুন পণ্য')

@section('content')
<form
    class="product-form"
    enctype="multipart/form-data"
    method="post"
    action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
>
    @csrf
    @if($product->exists)
        @method('PUT')
    @endif

    <div class="panel product-form-section">
        <div class="section-heading-row">
            <div>
                <h2>মূল তথ্য</h2>
                <p class="muted">পণ্যের নাম, URL, মূল্য, স্টক এবং প্রকাশের অবস্থা নির্ধারণ করুন।</p>
            </div>
        </div>

        <div class="form-grid">
            <label>
                বাংলা নাম
                <input name="name_bn" value="{{ old('name_bn', $product->name_bn) }}" required>
            </label>

            <label>
                English Name
                <input name="name_en" value="{{ old('name_en', $product->name_en) }}">
            </label>

            <label>
                SKU
                <input name="sku" value="{{ old('sku', $product->sku) }}" required placeholder="TD-001">
            </label>

            <label>
                Subcategory
                <select name="subcategory_id">
                    <option value="">None</option>
                    @foreach($subcategories as $s)
                        <option value="{{ $s->id }}" @selected(old('subcategory_id', $product->subcategory_id) == $s->id)>
                            {{ $s->category?->name_bn }} → {{ $s->name_bn }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="span-2">
                Last URL
                <small>ফাঁকা রাখলে নাম থেকে অটো তৈরি হবে। নিজের URL দিলে শুধু শেষ অংশ লিখুন।</small>
                <div class="url-field">
                    <span>/product/</span>
                    <input
                        name="slug"
                        value="{{ old('slug', $product->slug) }}"
                        placeholder="heavy-duty-multi-function-vegetable-peeler-394"
                    >
                </div>
                @if($product->exists)
                    <small>Live URL: {{ route('product.show', $product->slug) }}</small>
                @endif
            </label>

            <label>
                বিক্রয় মূল্য
                <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required>
            </label>

            <label>
                আগের মূল্য
                <input type="number" step="0.01" name="compare_price" value="{{ old('compare_price', $product->compare_price) }}">
            </label>

            <label>
                Stock Qty
                <input type="number" name="stock_qty" min="0" value="{{ old('stock_qty', $product->stock_qty ?? 0) }}" required>
            </label>

            <label>
                Sort Order
                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $product->sort_order ?? 0) }}">
            </label>

            <label class="check">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->exists ? $product->is_active : true))>
                Active
            </label>

            <label class="check">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))>
                Featured
            </label>
        </div>
    </div>

    <div class="panel product-form-section">
        <div class="section-heading-row">
            <div>
                <h2>Short Description</h2>
                <p class="muted">পণ্যের মূল তথ্যের পাশে দেখাবে। বাংলা/ইংরেজি, ছবি, ভিডিও ও নিরাপদ embed HTML ব্যবহার করা যাবে।</p>
            </div>
        </div>

        <div class="rich-editor-shell" data-editor-shell="short">
            <div class="editor-toolbar" data-editor-toolbar="short" role="toolbar" aria-label="Short description editor toolbar">
                <button type="button" class="editor-icon" data-action="undo" title="Undo">↶</button>
                <button type="button" class="editor-icon" data-action="redo" title="Redo">↷</button>

                <select data-action="format-block" title="Text style">
                    <option value="p">Paragraph</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                    <option value="h4">Heading 4</option>
                    <option value="blockquote">Quote</option>
                </select>

                <select data-action="font-name" title="Font">
                    <option value="Arial">Arial</option>
                    <option value="'Noto Sans Bengali', Arial, sans-serif">Noto Sans Bengali</option>
                    <option value="Verdana">Verdana</option>
                    <option value="Tahoma">Tahoma</option>
                    <option value="Georgia">Georgia</option>
                    <option value="'Times New Roman'">Times New Roman</option>
                    <option value="monospace">Monospace</option>
                </select>

                <select data-action="font-size" title="Font size">
                    <option value="12px">12</option>
                    <option value="14px">14</option>
                    <option value="16px" selected>16</option>
                    <option value="18px">18</option>
                    <option value="20px">20</option>
                    <option value="24px">24</option>
                    <option value="28px">28</option>
                    <option value="32px">32</option>
                    <option value="36px">36</option>
                    <option value="48px">48</option>
                </select>

                <span class="toolbar-separator"></span>
                <button type="button" data-command="bold" title="Bold"><b>B</b></button>
                <button type="button" data-command="italic" title="Italic"><i>I</i></button>
                <button type="button" data-command="underline" title="Underline"><u>U</u></button>
                <button type="button" data-command="strikeThrough" title="Strike"><s>S</s></button>

                <label class="color-tool" title="Text color">A<input type="color" data-action="text-color" value="#111111"></label>
                <label class="color-tool highlight" title="Background color">▰<input type="color" data-action="background-color" value="#fff59d"></label>

                <span class="toolbar-separator"></span>
                <button type="button" data-command="justifyLeft" title="Align left">⇤</button>
                <button type="button" data-command="justifyCenter" title="Align center">↔</button>
                <button type="button" data-command="justifyRight" title="Align right">⇥</button>
                <button type="button" data-command="justifyFull" title="Justify">☰</button>

                <select data-action="line-height" title="Line height">
                    <option value="1">Line 1.0</option>
                    <option value="1.2">Line 1.2</option>
                    <option value="1.4">Line 1.4</option>
                    <option value="1.6" selected>Line 1.6</option>
                    <option value="1.8">Line 1.8</option>
                    <option value="2">Line 2.0</option>
                    <option value="2.5">Line 2.5</option>
                </select>

                <span class="toolbar-separator"></span>
                <button type="button" data-command="insertUnorderedList" title="Bullet list">• List</button>
                <button type="button" data-command="insertOrderedList" title="Numbered list">1. List</button>
                <button type="button" data-command="outdent" title="Outdent">←</button>
                <button type="button" data-command="indent" title="Indent">→</button>

                <button type="button" data-action="link" title="Insert link">Link</button>
                <button type="button" data-command="unlink" title="Remove link">Unlink</button>
                <button type="button" data-action="image-url" title="Insert image from URL">Image URL</button>
                <button type="button" data-action="image-upload" title="Upload and insert image">Upload Image</button>
                <button type="button" data-action="video-url" title="Insert video from URL">Video URL</button>
                <button type="button" data-action="video-upload" title="Upload and insert video">Upload Video</button>
                <button type="button" data-action="html" title="Insert HTML / embed code">HTML / Embed</button>
                <button type="button" data-command="insertHorizontalRule" title="Horizontal line">—</button>
                <button type="button" data-command="removeFormat" title="Clear formatting">Clear</button>
                <button type="button" data-action="fullscreen" title="Fullscreen editor">⛶</button>
            </div>

            <div
                id="shortEditor"
                class="rich-editor rich-editor-short"
                contenteditable="true"
                data-editor="short"
                spellcheck="true"
            >{!! old('short_description', $product->short_description) !!}</div>
            <textarea hidden id="shortDescriptionHtml" name="short_description" data-editor-input="short"></textarea>
        </div>
    </div>

    <div class="panel product-form-section product-media-section">
        <div class="section-heading-row">
            <div>
                <h2>পণ্যের ছবি ও ভিডিও</h2>
                <p class="muted">ছবি/ভিডিও select করার পর সঙ্গে সঙ্গে preview দেখাবে। Gallery image drag করে বা arrow button দিয়ে serial পরিবর্তন করুন।</p>
            </div>
        </div>

        @if(($uploadLimits['server_too_small'] ?? false))
            <div class="upload-limit-warning">
                <strong>⚠ Video upload server limit কম আছে</strong>
                <span>
                    PHP upload_max_filesize: <b>{{ $uploadLimits['upload_max_label'] }}</b> ·
                    post_max_size: <b>{{ $uploadLimits['post_max_label'] }}</b> ·
                    এই server-এ বর্তমান effective video limit প্রায় <b>{{ $uploadLimits['effective_video_max_label'] }}</b>।
                    Website limit 50 MB হলেও PHP limit কম হলে “video file failed to upload” দেখাবে।
                </span>
            </div>
        @else
            <div class="upload-limit-ok">
                Server upload limit: upload_max_filesize <b>{{ $uploadLimits['upload_max_label'] ?? 'Unknown' }}</b> ·
                post_max_size <b>{{ $uploadLimits['post_max_label'] ?? 'Unknown' }}</b> · Product video max <b>50 MB</b>
            </div>
        @endif

        <div class="media-grid media-grid-enhanced">
            <div class="media-field-card">
                <div class="media-field-head">
                    <div>
                        <strong>Main Image</strong>
                        <small>JPG, PNG, WebP, GIF · max 6 MB</small>
                    </div>
                </div>
                <input id="mainImageInput" type="file" name="main_image" accept="image/jpeg,image/png,image/webp,image/gif">
                <div
                    id="mainImagePreviewBox"
                    class="single-media-preview {{ $product->main_image_path ? 'has-media' : '' }}"
                    data-existing-src="{{ $product->main_image_path ? asset('storage/'.$product->main_image_path) : '' }}"
                >
                    <div class="media-empty-state" @if($product->main_image_path) hidden @endif>
                        <span>🖼</span>
                        <b>ছবি select করলে এখানে preview হবে</b>
                    </div>
                    <img
                        id="mainImagePreview"
                        src="{{ $product->main_image_path ? asset('storage/'.$product->main_image_path) : '' }}"
                        alt="Main image preview"
                        @if(!$product->main_image_path) hidden @endif
                    >
                    <div class="media-preview-info" id="mainImageInfo" @if(!$product->main_image_path) hidden @endif>
                        @if($product->main_image_path)<span>বর্তমান main image</span>@endif
                    </div>
                </div>
            </div>

            <div class="media-field-card">
                <div class="media-field-head">
                    <div>
                        <strong>Gallery Images (Multiple)</strong>
                        <small>একসাথে একাধিক ছবি select করুন · তারপর drag/arrow দিয়ে order ঠিক করুন</small>
                    </div>
                </div>
                <input id="galleryInput" type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                <div class="gallery-help-row">
                    <span>↕ Drag to arrange</span>
                    <span>← → buttons দিয়েও serial বদলানো যাবে</span>
                    <span id="galleryCountLabel"></span>
                </div>
            </div>

            <div class="media-field-card">
                <div class="media-field-head">
                    <div>
                        <strong>Video Upload</strong>
                        <small>MP4 / WebM / MOV · website max 50 MB</small>
                    </div>
                </div>
                <input id="productVideoInput" type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov">
                <div
                    id="productVideoPreviewBox"
                    class="single-media-preview video-preview-box {{ $product->video_path ? 'has-media' : '' }}"
                    data-existing-src="{{ $product->video_path ? asset('storage/'.$product->video_path) : '' }}"
                >
                    <div class="media-empty-state" @if($product->video_path) hidden @endif>
                        <span>🎬</span>
                        <b>ভিডিও select করলে এখানে play করে check করতে পারবেন</b>
                    </div>
                    <video
                        id="productVideoPreview"
                        controls
                        preload="metadata"
                        src="{{ $product->video_path ? asset('storage/'.$product->video_path) : '' }}"
                        @if(!$product->video_path) hidden @endif
                    ></video>
                    <div class="media-preview-info" id="productVideoInfo" @if(!$product->video_path) hidden @endif>
                        @if($product->video_path)<span>বর্তমান uploaded video</span>@endif
                    </div>
                    <button type="button" class="media-clear-btn" id="clearProductVideoSelection" hidden>Selected video বাদ দিন</button>
                </div>
            </div>

            <div class="media-field-card">
                <div class="media-field-head">
                    <div>
                        <strong>অথবা Video URL</strong>
                        <small>YouTube / Vimeo / direct video URL</small>
                    </div>
                </div>
                <input id="productVideoUrl" name="video_url" value="{{ old('video_url', $product->video_url) }}" placeholder="https://...">
                <div id="videoUrlPreview" class="url-video-preview" hidden></div>
                <button type="button" class="btn small" id="previewVideoUrlButton">URL Preview</button>
            </div>
        </div>

        <input type="hidden" name="gallery_order" id="galleryOrderInput" value="[]">

        <div class="gallery-organizer-wrap">
            <div class="gallery-organizer-title">
                <div>
                    <strong>Gallery Organizer</strong>
                    <small>১ নম্বর ছবি gallery-তে প্রথম দেখাবে। নতুন ও existing—দুই ধরনের ছবিই একসাথে reorder করা যাবে।</small>
                </div>
            </div>

            <div id="galleryOrganizer" class="gallery-organizer" aria-label="Gallery image order">
                @if($product->exists)
                    @foreach($product->images as $img)
                        <div class="gallery-sort-item" draggable="true" data-gallery-key="existing:{{ $img->id }}">
                            <div class="gallery-order-badge">{{ $loop->iteration }}</div>
                            <div class="gallery-drag-handle" title="Drag to reorder">⠿</div>
                            <img src="{{ asset('storage/'.$img->path) }}" alt="{{ $img->alt_text ?: 'Gallery image' }}">
                            <div class="gallery-card-meta">
                                <span>Existing image</span>
                            </div>
                            <div class="gallery-card-actions">
                                <button type="button" class="gallery-move" data-gallery-move="left" title="Move left">←</button>
                                <button type="button" class="gallery-move" data-gallery-move="right" title="Move right">→</button>
                                <button form="delete-img-{{ $img->id }}" type="submit" class="gallery-delete" title="Delete image">×</button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <div id="galleryEmptyState" class="gallery-empty-state" @if($product->exists && $product->images->count()) hidden @endif>
                Gallery image select করলে thumbnail এখানে দেখা যাবে।
            </div>
        </div>
    </div>

    <div class="panel product-form-section">
        <div class="section-heading-row">
            <div>
                <h2>Full Product Details (Bangla supported)</h2>
                <p class="muted">Typography, color, alignment, line height, image/video upload, URL media এবং safe HTML/embed code supported.</p>
            </div>
        </div>

        <div class="rich-editor-shell" data-editor-shell="full">
            <div class="editor-toolbar" data-editor-toolbar="full" role="toolbar" aria-label="Full product description editor toolbar">
                <button type="button" class="editor-icon" data-action="undo" title="Undo">↶</button>
                <button type="button" class="editor-icon" data-action="redo" title="Redo">↷</button>

                <select data-action="format-block" title="Text style">
                    <option value="p">Paragraph</option>
                    <option value="h1">Heading 1</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                    <option value="h4">Heading 4</option>
                    <option value="blockquote">Quote</option>
                    <option value="pre">Code block</option>
                </select>

                <select data-action="font-name" title="Font">
                    <option value="Arial">Arial</option>
                    <option value="'Noto Sans Bengali', Arial, sans-serif">Noto Sans Bengali</option>
                    <option value="Verdana">Verdana</option>
                    <option value="Tahoma">Tahoma</option>
                    <option value="Georgia">Georgia</option>
                    <option value="'Times New Roman'">Times New Roman</option>
                    <option value="monospace">Monospace</option>
                </select>

                <select data-action="font-size" title="Font size">
                    <option value="12px">12</option>
                    <option value="14px">14</option>
                    <option value="16px" selected>16</option>
                    <option value="18px">18</option>
                    <option value="20px">20</option>
                    <option value="24px">24</option>
                    <option value="28px">28</option>
                    <option value="32px">32</option>
                    <option value="36px">36</option>
                    <option value="48px">48</option>
                </select>

                <span class="toolbar-separator"></span>
                <button type="button" data-command="bold" title="Bold"><b>B</b></button>
                <button type="button" data-command="italic" title="Italic"><i>I</i></button>
                <button type="button" data-command="underline" title="Underline"><u>U</u></button>
                <button type="button" data-command="strikeThrough" title="Strike"><s>S</s></button>

                <label class="color-tool" title="Text color">A<input type="color" data-action="text-color" value="#111111"></label>
                <label class="color-tool highlight" title="Background color">▰<input type="color" data-action="background-color" value="#fff59d"></label>

                <span class="toolbar-separator"></span>
                <button type="button" data-command="justifyLeft" title="Align left">⇤</button>
                <button type="button" data-command="justifyCenter" title="Align center">↔</button>
                <button type="button" data-command="justifyRight" title="Align right">⇥</button>
                <button type="button" data-command="justifyFull" title="Justify">☰</button>

                <select data-action="line-height" title="Line height">
                    <option value="1">Line 1.0</option>
                    <option value="1.2">Line 1.2</option>
                    <option value="1.4">Line 1.4</option>
                    <option value="1.6">Line 1.6</option>
                    <option value="1.8" selected>Line 1.8</option>
                    <option value="2">Line 2.0</option>
                    <option value="2.5">Line 2.5</option>
                </select>

                <span class="toolbar-separator"></span>
                <button type="button" data-command="insertUnorderedList" title="Bullet list">• List</button>
                <button type="button" data-command="insertOrderedList" title="Numbered list">1. List</button>
                <button type="button" data-command="outdent" title="Outdent">←</button>
                <button type="button" data-command="indent" title="Indent">→</button>

                <button type="button" data-action="link" title="Insert link">Link</button>
                <button type="button" data-command="unlink" title="Remove link">Unlink</button>
                <button type="button" data-action="image-url" title="Insert image from URL">Image URL</button>
                <button type="button" data-action="image-upload" title="Upload and insert image">Upload Image</button>
                <button type="button" data-action="video-url" title="Insert video from URL">Video URL</button>
                <button type="button" data-action="video-upload" title="Upload and insert video">Upload Video</button>
                <button type="button" data-action="html" title="Insert HTML / embed code">HTML / Embed</button>
                <button type="button" data-command="insertHorizontalRule" title="Horizontal line">—</button>
                <button type="button" data-command="removeFormat" title="Clear formatting">Clear</button>
                <button type="button" data-action="fullscreen" title="Fullscreen editor">⛶</button>
            </div>

            <div
                id="richEditor"
                class="rich-editor rich-editor-full"
                contenteditable="true"
                data-editor="full"
                spellcheck="true"
            >{!! old('description_html', $product->description_html) !!}</div>
            <textarea hidden id="descriptionHtml" name="description_html" data-editor-input="full"></textarea>
        </div>
    </div>

    <div class="panel form-grid product-form-section">
        <label>
            Meta Title
            <input name="meta_title" value="{{ old('meta_title', $product->meta_title) }}">
        </label>

        <label class="span-2">
            Meta Description
            <textarea name="meta_description" rows="3">{{ old('meta_description', $product->meta_description) }}</textarea>
        </label>

        <div class="span-2 form-save-row">
            <button class="btn primary large" type="submit">পণ্য সেভ করুন</button>
        </div>
    </div>
</form>

<input id="editorImageUpload" type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
<input id="editorVideoUpload" type="file" accept="video/mp4,video/webm,video/quicktime" hidden>

<div class="editor-modal" id="editorHtmlModal" hidden>
    <div class="editor-modal-backdrop" data-modal-close></div>
    <div class="editor-modal-card" role="dialog" aria-modal="true" aria-labelledby="editorHtmlModalTitle">
        <div class="editor-modal-head">
            <div>
                <h3 id="editorHtmlModalTitle">HTML / Embed Code</h3>
                <p>iframe, image, video, table এবং formatted HTML paste করতে পারবেন। নিরাপত্তার জন্য script/event-handler code save করার সময় remove হবে।</p>
            </div>
            <button type="button" class="editor-modal-x" data-modal-close>×</button>
        </div>
        <textarea id="editorHtmlCode" rows="14" placeholder='<iframe src="https://www.youtube.com/embed/..."></iframe>'></textarea>
        <div class="editor-modal-actions">
            <button type="button" class="btn" data-modal-close>Cancel</button>
            <button type="button" class="btn primary" id="insertEditorHtml">Insert HTML</button>
        </div>
    </div>
</div>

@if($product->exists)
    @foreach($product->images as $img)
        <form id="delete-img-{{ $img->id }}" method="post" action="{{ route('admin.products.images.destroy', [$product, $img->id]) }}">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif
@endsection

@push('scripts')
@php
    $productUploadConfig = [
        'uploadMaxBytes' => $uploadLimits['upload_max_bytes'] ?? 0,
        'postMaxBytes' => $uploadLimits['post_max_bytes'] ?? 0,
        'appVideoMaxBytes' => $uploadLimits['app_video_max_bytes'] ?? (50 * 1024 * 1024),
        'effectiveVideoMaxBytes' => $uploadLimits['effective_video_max_bytes'] ?? (50 * 1024 * 1024),
    ];
@endphp
<script>
    window.productEditorMediaUploadUrl = {{ \Illuminate\Support\Js::from(route('admin.products.editor-media')) }};
    window.productUploadConfig = {{ \Illuminate\Support\Js::from($productUploadConfig) }};
</script>
@endpush
