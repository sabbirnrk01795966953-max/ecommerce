<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\OmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit()
    {
        // Read the important integration toggles directly from the database.
        // This avoids any browser old-input/autofill confusion on the settings page.
        $integrationState = [
            'meta_browser_enabled' => Setting::isEnabled('meta_browser_enabled'),
            'meta_capi_enabled' => Setting::isEnabled('meta_capi_enabled'),
            'oms_enabled' => Setting::isEnabled('oms_enabled'),
            'oms_api_key_saved' => trim((string) Setting::getValue('oms_api_key', '')) !== '',
            'meta_access_token_saved' => trim((string) Setting::getValue('meta_access_token', '')) !== '',
        ];

        return view('admin.settings.edit', compact('integrationState'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => 'required|string|max:160',
            'site_subtitle' => 'nullable|string|max:255',
            'primary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'help_line' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:190',
            'address' => 'nullable|string|max:500',
            'facebook_url' => 'nullable|url|max:1000',
            'instagram_url' => 'nullable|url|max:1000',
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:500',
            'hero_button_text' => 'nullable|string|max:80',
            'hero_button_url' => 'nullable|string|max:1000',
            'shipping_inside_dhaka' => 'required|numeric|min:0',
            'shipping_outside_dhaka' => 'required|numeric|min:0',
            'footer_shop_title' => 'nullable|string|max:120',
            'footer_shop_links' => 'nullable|string|max:5000',
            'footer_useful_title' => 'nullable|string|max:120',
            'footer_useful_links' => 'nullable|string|max:5000',
            'footer_service_title' => 'nullable|string|max:120',
            'footer_service_links' => 'nullable|string|max:5000',
            'footer_copyright' => 'nullable|string|max:255',
            'meta_pixel_id' => 'nullable|string|max:100',
            'meta_api_version' => 'nullable|string|max:20',
            'meta_access_token_new' => 'nullable|string|max:4000',
            'meta_test_event_code' => 'nullable|string|max:160',
            'oms_endpoint' => 'nullable|url|max:2000',
            'oms_api_key_new' => 'nullable|string|max:4000',
            'logo' => 'nullable|image|max:4096',
            'hero_image' => 'nullable|image|max:6144',
        ]);

        $normalKeys = [
            'site_name', 'site_subtitle', 'primary_color', 'secondary_color',
            'help_line', 'phone', 'email', 'address', 'facebook_url', 'instagram_url',
            'hero_title', 'hero_subtitle', 'hero_button_text', 'hero_button_url',
            'shipping_inside_dhaka', 'shipping_outside_dhaka',
            'footer_shop_title', 'footer_shop_links', 'footer_useful_title', 'footer_useful_links',
            'footer_service_title', 'footer_service_links', 'footer_copyright',
            'meta_pixel_id', 'meta_api_version', 'meta_test_event_code', 'oms_endpoint',
        ];

        $omsEnabled = $request->boolean('oms_enabled');
        $metaBrowserEnabled = $request->boolean('meta_browser_enabled');
        $metaCapiEnabled = $request->boolean('meta_capi_enabled');

        DB::transaction(function () use ($data, $normalKeys, $omsEnabled, $metaBrowserEnabled, $metaCapiEnabled) {
            foreach ($normalKeys as $key) {
                if (array_key_exists($key, $data)) {
                    Setting::setValue($key, $data[$key] ?? '');
                }
            }

            // Store toggles explicitly as literal 0/1 strings.
            Setting::setValue('oms_enabled', $omsEnabled ? '1' : '0');
            Setting::setValue('meta_browser_enabled', $metaBrowserEnabled ? '1' : '0');
            Setting::setValue('meta_capi_enabled', $metaCapiEnabled ? '1' : '0');

            // Secret fields are intentionally named *_new so Chrome/password managers
            // cannot accidentally overwrite a previously saved API key/token.
            if (! empty($data['oms_api_key_new'])) {
                Setting::setValue('oms_api_key', trim($data['oms_api_key_new']));
            }

            if (! empty($data['meta_access_token_new'])) {
                Setting::setValue('meta_access_token', trim($data['meta_access_token_new']));
            }
        });

        if ($request->hasFile('logo')) {
            $old = Setting::getValue('logo_path');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            Setting::setValue('logo_path', $request->file('logo')->store('settings', 'public'));
        }

        if ($request->hasFile('hero_image')) {
            $old = Setting::getValue('hero_image_path');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            Setting::setValue('hero_image_path', $request->file('hero_image')->store('settings', 'public'));
        }

        // Verify what is actually persisted before redirecting.
        $savedOmsEnabled = Setting::isEnabled('oms_enabled');

        return back()->with(
            'success',
            'Settings saved. OMS is now '.($savedOmsEnabled ? 'ENABLED' : 'DISABLED').'.'
        );
    }

    public function saveOms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'required',
            'endpoint' => 'nullable|url|max:2000',
            'api_key' => 'nullable|string|max:4000',
        ]);

        // The browser sends 1 or 0 explicitly. Do not depend on PHP/JSON boolean casting.
        $requestedEnabled = in_array(
            strtolower(trim((string) $validated['enabled'])),
            ['1', 'true', 'on', 'yes', 'enabled'],
            true
        );

        Setting::setValue('oms_enabled', $requestedEnabled ? '1' : '0');
        Setting::setValue('oms_endpoint', trim((string) ($validated['endpoint'] ?? '')));

        if (! empty($validated['api_key'])) {
            Setting::setValue('oms_api_key', trim((string) $validated['api_key']));
        }

        // Re-read from the DB using type-safe normalization.
        $rawEnabled = Setting::getValue('oms_enabled', '0');
        $enabled = Setting::isEnabled('oms_enabled');
        $keySaved = trim((string) Setting::getValue('oms_api_key', '')) !== '';

        return response()->json([
            'ok' => true,
            'enabled' => $enabled,
            'stored_value' => (string) $rawEnabled,
            'api_key_saved' => $keySaved,
            'message' => 'OMS settings saved. OMS is now '.($enabled ? 'ENABLED' : 'DISABLED').'.',
        ]);
    }

    public function testOms(Request $request, OmsService $oms): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'nullable|url|max:2000',
            'api_key' => 'nullable|string|max:4000',
        ]);

        $endpoint = trim((string) ($validated['endpoint'] ?? ''));
        if ($endpoint === '') {
            $endpoint = trim((string) Setting::getValue('oms_endpoint', ''));
        }

        $apiKey = trim((string) ($validated['api_key'] ?? ''));
        if ($apiKey === '') {
            $apiKey = trim((string) Setting::getValue('oms_api_key', ''));
        }

        if ($endpoint === '') {
            return response()->json([
                'ok' => false,
                'message' => 'OMS endpoint পাওয়া যায়নি। আগে Full OMS Endpoint দিন।',
            ], 422);
        }

        if ($apiKey === '') {
            return response()->json([
                'ok' => false,
                'message' => 'OMS API key পাওয়া যায়নি। নতুন API key দিন অথবা আগে Save Settings করুন।',
            ], 422);
        }

        $result = $oms->testConnection($endpoint, $apiKey);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
