<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaCapiService
{
    public function send(string $eventName, string $eventId, array $customData = [], ?Request $request = null, ?string $sourceUrl = null): bool
    {
        if (Setting::getValue('meta_capi_enabled', '0') !== '1') return false;
        $pixelId = trim((string) Setting::getValue('meta_pixel_id', ''));
        $token = trim((string) Setting::getValue('meta_access_token', ''));
        $version = trim((string) Setting::getValue('meta_api_version', 'v24.0')) ?: 'v24.0';
        $version = Str::startsWith($version, 'v') ? $version : 'v'.$version;
        if ($pixelId === '' || $token === '') return false;

        $request ??= request();
        $email = mb_strtolower(trim((string) $request->input('email', '')));
        $phone = preg_replace('/\D+/', '', (string) $request->input('phone', ''));
        if ($phone && str_starts_with($phone, '0')) $phone = '880'.substr($phone, 1);
        $name = mb_strtolower(trim((string) $request->input('customer_name', '')));

        $userData = array_filter([
            'client_ip_address' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $request->cookie('_fbc'),
            'em' => $email !== '' ? [hash('sha256', $email)] : null,
            'ph' => $phone !== '' ? [hash('sha256', $phone)] : null,
            'fn' => $name !== '' ? [hash('sha256', $name)] : null,
            'country' => [hash('sha256', 'bd')],
            'external_id' => [hash('sha256', (string) $request->session()->getId())],
        ]);

        $payload = [
            'data' => [[
                'event_name' => $eventName,
                'event_time' => now()->timestamp,
                'event_id' => $eventId,
                'action_source' => 'website',
                'event_source_url' => $sourceUrl ?: $request->fullUrl(),
                'user_data' => $userData,
                'custom_data' => $customData,
            ]],
        ];
        $testCode = trim((string) Setting::getValue('meta_test_event_code', ''));
        if ($testCode !== '') $payload['test_event_code'] = $testCode;

        try {
            $url = "https://graph.facebook.com/{$version}/{$pixelId}/events?access_token=".urlencode($token);
            $response = Http::asJson()->timeout(12)->post($url, $payload);
            if (! $response->successful()) Log::warning('Meta CAPI failed', ['status'=>$response->status(),'body'=>$response->body(),'event'=>$eventName]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI exception', ['error'=>$e->getMessage(),'event'=>$eventName]);
            return false;
        }
    }
}
