@extends('layouts.admin')

@section('title', 'ওয়েবসাইট সেটিংস')
@section('page_title', 'ওয়েবসাইট সেটিংস')

@section('content')
<form action="{{ route('admin.settings.update') }}" method="post" enctype="multipart/form-data" id="settingsForm">
    @csrf
    @method('PUT')

    <div class="settings-tabs">
        <a href="#general">General</a>
        <a href="#design">Design</a>
        <a href="#footer-settings">Footer</a>
        <a href="#meta">Meta Pixel + CAPI</a>
        <a href="#oms">OMS</a>
    </div>

    <div id="general" class="panel form-grid">
        <h2 class="span-2">General</h2>
        <label>Site Name
            <input name="site_name" value="{{ old('site_name', $siteSettings['site_name'] ?? 'Trendy Deal BD') }}" required>
        </label>
        <label>Subtitle
            <input name="site_subtitle" value="{{ old('site_subtitle', $siteSettings['site_subtitle'] ?? '') }}">
        </label>
        <label>Help Line
            <input name="help_line" value="{{ old('help_line', $siteSettings['help_line'] ?? '') }}">
        </label>
        <label>Phone
            <input name="phone" value="{{ old('phone', $siteSettings['phone'] ?? '') }}">
        </label>
        <label>Email
            <input name="email" value="{{ old('email', $siteSettings['email'] ?? '') }}">
        </label>
        <label class="span-2">Address
            <textarea name="address">{{ old('address', $siteSettings['address'] ?? '') }}</textarea>
        </label>
        <label>Inside Dhaka Delivery
            <input type="number" step="0.01" name="shipping_inside_dhaka" value="{{ old('shipping_inside_dhaka', $siteSettings['shipping_inside_dhaka'] ?? 60) }}">
        </label>
        <label>Outside Dhaka Delivery
            <input type="number" step="0.01" name="shipping_outside_dhaka" value="{{ old('shipping_outside_dhaka', $siteSettings['shipping_outside_dhaka'] ?? 120) }}">
        </label>
    </div>

    <div id="design" class="panel form-grid">
        <h2 class="span-2">Logo, Color & Hero</h2>
        <label>Logo
            <input type="file" name="logo" accept="image/*">
            @if(!empty($siteSettings['logo_path']))
                <img class="settings-logo" src="{{ asset('storage/'.$siteSettings['logo_path']) }}" alt="Logo">
            @endif
        </label>
        <label>Hero Image
            <input type="file" name="hero_image" accept="image/*">
            @if(!empty($siteSettings['hero_image_path']))
                <img class="preview" src="{{ asset('storage/'.$siteSettings['hero_image_path']) }}" alt="Hero">
            @endif
        </label>
        <label>Primary Color
            <input type="color" name="primary_color" value="{{ old('primary_color', $siteSettings['primary_color'] ?? '#00B957') }}">
        </label>
        <label>Secondary Color
            <input type="color" name="secondary_color" value="{{ old('secondary_color', $siteSettings['secondary_color'] ?? '#122B35') }}">
        </label>
        <label>Hero Title
            <input name="hero_title" value="{{ old('hero_title', $siteSettings['hero_title'] ?? '') }}">
        </label>
        <label>Hero Button Text
            <input name="hero_button_text" value="{{ old('hero_button_text', $siteSettings['hero_button_text'] ?? '') }}">
        </label>
        <label class="span-2">Hero Subtitle
            <textarea name="hero_subtitle">{{ old('hero_subtitle', $siteSettings['hero_subtitle'] ?? '') }}</textarea>
        </label>
        <label class="span-2">Hero Button URL
            <input name="hero_button_url" value="{{ old('hero_button_url', $siteSettings['hero_button_url'] ?? '/shop') }}">
        </label>
    </div>

    <div id="footer-settings" class="panel form-grid">
        <h2 class="span-2">Footer (Admin-controlled)</h2>
        <p class="span-2 muted">প্রতি লাইনে: Label|URL</p>
        <label>Shop Column Title
            <input name="footer_shop_title" value="{{ old('footer_shop_title', $siteSettings['footer_shop_title'] ?? '') }}">
            <textarea name="footer_shop_links" rows="7">{{ old('footer_shop_links', $siteSettings['footer_shop_links'] ?? '') }}</textarea>
        </label>
        <label>Useful Column Title
            <input name="footer_useful_title" value="{{ old('footer_useful_title', $siteSettings['footer_useful_title'] ?? '') }}">
            <textarea name="footer_useful_links" rows="7">{{ old('footer_useful_links', $siteSettings['footer_useful_links'] ?? '') }}</textarea>
        </label>
        <label>Service Column Title
            <input name="footer_service_title" value="{{ old('footer_service_title', $siteSettings['footer_service_title'] ?? '') }}">
            <textarea name="footer_service_links" rows="7">{{ old('footer_service_links', $siteSettings['footer_service_links'] ?? '') }}</textarea>
        </label>
        <label>Copyright
            <input name="footer_copyright" value="{{ old('footer_copyright', $siteSettings['footer_copyright'] ?? '') }}">
        </label>
        <label>Facebook URL
            <input name="facebook_url" value="{{ old('facebook_url', $siteSettings['facebook_url'] ?? '') }}">
        </label>
        <label>Instagram URL
            <input name="instagram_url" value="{{ old('instagram_url', $siteSettings['instagram_url'] ?? '') }}">
        </label>
    </div>

    <div id="meta" class="panel form-grid">
        <h2 class="span-2">Meta Pixel + Conversion API</h2>

        <label class="check">
            <input type="checkbox" name="meta_browser_enabled" value="1" @checked($integrationState['meta_browser_enabled'])>
            Browser Pixel enabled
        </label>
        <label class="check">
            <input type="checkbox" name="meta_capi_enabled" value="1" @checked($integrationState['meta_capi_enabled'])>
            Server CAPI enabled
        </label>

        <label>Pixel ID
            <input name="meta_pixel_id" value="{{ old('meta_pixel_id', $siteSettings['meta_pixel_id'] ?? '') }}">
        </label>
        <label>Graph API Version
            <input name="meta_api_version" value="{{ old('meta_api_version', $siteSettings['meta_api_version'] ?? 'v24.0') }}" placeholder="v24.0">
        </label>
        <label>Test Event Code
            <input name="meta_test_event_code" value="{{ old('meta_test_event_code', $siteSettings['meta_test_event_code'] ?? '') }}">
        </label>
        <label class="span-2">New CAPI Access Token
            <input type="password"
                   name="meta_access_token_new"
                   value=""
                   autocomplete="off"
                   data-lpignore="true"
                   data-1p-ignore
                   placeholder="{{ $integrationState['meta_access_token_saved'] ? 'Token already saved — leave blank to keep it' : 'Paste CAPI access token' }}">
        </label>

        <div class="span-2 info-box">
            Browser + server event একই <b>Event ID</b> ব্যবহার করে deduplicate হবে।
            Catalog Feed: <code>{{ route('meta.catalog') }}</code>
        </div>
    </div>

    <div id="oms" class="panel form-grid integration-panel">
        <div class="span-2 integration-heading">
            <div>
                <h2>OMS Integration</h2>
                <p class="muted">Order save হওয়ার পর server-side থেকে OMS-এ JSON পাঠানো হবে।</p>
            </div>
            <span id="omsStatusBadge" class="integration-status {{ $integrationState['oms_enabled'] ? 'is-on' : 'is-off' }}">
                {{ $integrationState['oms_enabled'] ? '● OMS ENABLED' : '● OMS DISABLED' }}
            </span>
        </div>

        <label class="check integration-toggle span-2">
            <input type="checkbox" id="oms_enabled" name="oms_enabled" value="1" @checked($integrationState['oms_enabled'])>
            <span>
                <strong>OMS enabled</strong>
                <small>Checked থাকলে নতুন order স্বয়ংক্রিয়ভাবে OMS-এ পাঠানো হবে।</small>
            </span>
        </label>

        <label class="span-2">Full OMS Endpoint
            <input id="oms_endpoint"
                   name="oms_endpoint"
                   value="{{ old('oms_endpoint', $siteSettings['oms_endpoint'] ?? '') }}"
                   placeholder="https://your-oms-domain.com/api/integrations/trendy-deal-bd-web/orders">
        </label>

        <label class="span-2">New API Key
            <input type="password"
                   id="oms_api_key_new"
                   name="oms_api_key_new"
                   value=""
                   autocomplete="off"
                   data-lpignore="true"
                   data-1p-ignore
                   placeholder="{{ $integrationState['oms_api_key_saved'] ? 'API key already saved — leave blank to keep it' : 'Paste OMS API key' }}">
            <small class="secret-state {{ $integrationState['oms_api_key_saved'] ? 'saved' : 'missing' }}">
                {{ $integrationState['oms_api_key_saved'] ? '✓ একটি OMS API key server-side এ saved আছে' : '⚠ কোনো OMS API key saved নেই' }}
            </small>
        </label>

        <div class="span-2 oms-test-card">
            <div class="oms-test-head">
                <div>
                    <strong>OMS Test Connection</strong>
                    <p>এই button চাপলে OMS-এ একটি clearly-marked demo order পাঠানো হবে। Laravel shop database-এ test order তৈরি হবে না।</p>
                </div>
                <div class="oms-action-buttons">
                    <button type="button" class="btn primary" id="omsSaveButton" data-save-url="{{ route('admin.settings.save-oms') }}">Save OMS Settings</button>
                    <button type="button" class="btn outline" id="omsTestButton" data-test-url="{{ route('admin.settings.test-oms') }}">Test Connection + Send Demo Data</button>
                </div>
            </div>

            <div id="omsTestResult" class="oms-test-result" hidden></div>

            <details class="demo-payload">
                <summary>Demo JSON payload দেখুন</summary>
<pre>{
  "apiKey": "********",
  "externalOrderId": "TDBD-OMS-TEST-...",
  "invoiceId": "TDBDTEST...",
  "customerName": "OMS Test Customer",
  "phone": "01700000000",
  "address": "Test Address, Dhaka, Bangladesh",
  "shippingPhone": "01700000000",
  "shippingCustomerName": "OMS Test Customer",
  "shippingAddress1": "House 10, Road 5",
  "shippingAddress2": "OMS TEST - DELETE THIS ORDER",
  "shippingCity": "Dhaka",
  "shippingProvince": "Dhaka",
  "shippingZip": "1207",
  "shippingCountry": "Bangladesh",
  "email": "oms-test@trendydealbd.shop",
  "deliveryCharge": 120,
  "discount": 0,
  "advance": 0,
  "totalAmount": 670,
  "note": "OMS CONNECTION TEST FROM TRENDY DEAL BD - SAFE TO DELETE",
  "items": [
    {
      "sku": "TDBD-TEST-001",
      "name": "OMS Demo Product",
      "quantity": 1,
      "price": 550
    }
  ]
}</pre>
            </details>
        </div>

        <div class="span-2 info-box">
            <b>Important:</b> API key browser-এ প্রকাশ করা হয় না। Test button-এ API key field blank রাখলে already-saved key ব্যবহার হবে।
            Test সফল হলে OMS response, HTTP status, invoice ID এবং external order ID এখানেই দেখাবে।
        </div>
    </div>

    <div class="sticky-save">
        <button class="btn primary large" type="submit">সব সেটিংস সেভ করুন</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(() => {
    const button = document.getElementById('omsTestButton');
    const saveButton = document.getElementById('omsSaveButton');
    const enabledInput = document.getElementById('oms_enabled');
    const statusBadge = document.getElementById('omsStatusBadge');
    const resultBox = document.getElementById('omsTestResult');
    const endpointInput = document.getElementById('oms_endpoint');
    const keyInput = document.getElementById('oms_api_key_new');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (!button || !saveButton || !enabledInput || !statusBadge || !resultBox || !endpointInput || !keyInput) return;

    const renderResult = (data, ok) => {
        resultBox.hidden = false;
        resultBox.className = 'oms-test-result ' + (ok ? 'success' : 'error');

        const status = data.http_status ?? 'No HTTP response';
        const invoice = data.invoice_id ?? '-';
        const externalId = data.external_order_id ?? '-';
        const responseText = typeof data.response === 'string' ? data.response : JSON.stringify(data.response ?? '', null, 2);

        resultBox.innerHTML = `
            <div class="oms-result-title">${ok ? '✓ OMS Connection Successful' : '✕ OMS Test Failed'}</div>
            <div class="oms-result-grid">
                <span>Message</span><b>${escapeHtml(data.message || '')}</b>
                <span>HTTP Status</span><b>${escapeHtml(String(status))}</b>
                <span>Test Invoice</span><b>${escapeHtml(String(invoice))}</b>
                <span>External Order ID</span><b>${escapeHtml(String(externalId))}</b>
            </div>
            <details open>
                <summary>OMS Response</summary>
                <pre>${escapeHtml(responseText)}</pre>
            </details>
        `;
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    saveButton.addEventListener('click', async () => {
        saveButton.disabled = true;
        saveButton.textContent = 'Saving OMS...';
        resultBox.hidden = false;
        resultBox.className = 'oms-test-result loading';
        resultBox.textContent = 'OMS settings save করা হচ্ছে...';

        try {
            const response = await fetch(saveButton.dataset.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    enabled: enabledInput.checked ? 1 : 0,
                    endpoint: endpointInput.value.trim(),
                    api_key: keyInput.value.trim(),
                }),
            });

            const raw = await response.text();
            let data;
            try { data = JSON.parse(raw); } catch (_) { data = { message: raw || 'Unknown server response' }; }

            if (!response.ok || data.ok !== true) {
                throw new Error(data.message || 'OMS settings could not be saved.');
            }

            statusBadge.className = 'integration-status ' + (data.enabled ? 'is-on' : 'is-off');
            statusBadge.textContent = data.enabled ? '● OMS ENABLED' : '● OMS DISABLED';
            enabledInput.checked = !!data.enabled;
            keyInput.value = '';

            resultBox.className = 'oms-test-result success';
            resultBox.innerHTML = '<div class="oms-result-title">✓ ' + escapeHtml(data.message) + '</div>' +
                '<div class="oms-result-grid"><span>Database value</span><b>' + escapeHtml(String(data.stored_value ?? (data.enabled ? '1' : '0'))) + '</b></div>';
        } catch (error) {
            resultBox.className = 'oms-test-result error';
            resultBox.textContent = error?.message || String(error);
        } finally {
            saveButton.disabled = false;
            saveButton.textContent = 'Save OMS Settings';
        }
    });

    button.addEventListener('click', async () => {
        const endpoint = endpointInput.value.trim();
        if (!endpoint) {
            resultBox.hidden = false;
            resultBox.className = 'oms-test-result error';
            resultBox.textContent = 'আগে Full OMS Endpoint দিন।';
            return;
        }

        button.disabled = true;
        button.textContent = 'Testing...';
        resultBox.hidden = false;
        resultBox.className = 'oms-test-result loading';
        resultBox.textContent = 'OMS endpoint-এ demo order পাঠানো হচ্ছে...';

        try {
            const response = await fetch(button.dataset.testUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    endpoint,
                    api_key: keyInput.value.trim(),
                }),
            });

            const raw = await response.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch (_) {
                data = { message: 'OMS test endpoint returned a non-JSON response.', response: raw };
            }

            renderResult(data, response.ok && data.ok === true);
        } catch (error) {
            renderResult({
                message: 'Browser could not complete the OMS test request.',
                response: error?.message || String(error),
            }, false);
        } finally {
            button.disabled = false;
            button.textContent = 'Test Connection + Send Demo Data';
        }
    });
})();
</script>
@endpush
