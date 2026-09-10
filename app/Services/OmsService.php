<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OmsService
{
    public function send(Order $order): bool
    {
        $enabled = Setting::isEnabled('oms_enabled');
        $endpoint = trim((string) Setting::getValue('oms_endpoint', ''));
        $apiKey = trim((string) Setting::getValue('oms_api_key', ''));

        if (! $enabled || $endpoint === '' || $apiKey === '') {
            $order->update(['oms_status' => 'DISABLED']);
            return false;
        }

        $order->loadMissing('items');
        $payload = [
            'apiKey' => $apiKey,
            'externalOrderId' => $order->external_order_id,
            'invoiceId' => $order->invoice_id,
            'customerName' => $order->customer_name,
            'phone' => $order->phone,
            'address' => $order->address,
            'shippingPhone' => $order->shipping_phone,
            'shippingCustomerName' => $order->shipping_customer_name,
            'shippingAddress1' => $order->shipping_address1,
            'shippingAddress2' => $order->shipping_address2 ?? '',
            'shippingCity' => $order->shipping_city ?? '',
            'shippingProvince' => $order->shipping_province ?? '',
            'shippingZip' => $order->shipping_zip ?? '',
            'shippingCountry' => $order->shipping_country ?: 'Bangladesh',
            'email' => $order->email ?? '',
            'deliveryCharge' => (float) $order->delivery_charge,
            'discount' => (float) $order->discount,
            'advance' => (float) $order->advance,
            'totalAmount' => (float) $order->total_amount,
            'note' => $order->note ?? '',
            'items' => $order->items->map(fn ($item) => [
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
            ])->values()->all(),
        ];

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(20)
                ->retry(2, 400)
                ->post($endpoint, $payload);

            $ok = $response->successful();
            $order->update([
                'oms_status' => $ok ? 'SENT' : 'FAILED',
                'oms_response' => mb_substr($response->body(), 0, 5000),
            ]);

            if (! $ok) {
                Log::warning('OMS order push failed', [
                    'invoice' => $order->invoice_id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $ok;
        } catch (\Throwable $e) {
            $order->update([
                'oms_status' => 'FAILED',
                'oms_response' => mb_substr($e->getMessage(), 0, 5000),
            ]);
            Log::error('OMS order push exception', [
                'invoice' => $order->invoice_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send one clearly marked demo order to verify the configured OMS endpoint.
     * This does not create an order in this Laravel shop database.
     */
    public function testConnection(string $endpoint, string $apiKey): array
    {
        $stamp = now()->format('ymdHis');
        $suffix = strtoupper(Str::random(4));
        $invoiceId = 'TDBDTEST'.$stamp.$suffix;
        $externalOrderId = 'TDBD-OMS-TEST-'.$stamp.'-'.$suffix;

        $payload = [
            'apiKey' => $apiKey,
            'externalOrderId' => $externalOrderId,
            'invoiceId' => $invoiceId,
            'customerName' => 'OMS Test Customer',
            'phone' => '01700000000',
            'address' => 'Test Address, Dhaka, Bangladesh',
            'shippingPhone' => '01700000000',
            'shippingCustomerName' => 'OMS Test Customer',
            'shippingAddress1' => 'House 10, Road 5',
            'shippingAddress2' => 'OMS TEST - DELETE THIS ORDER',
            'shippingCity' => 'Dhaka',
            'shippingProvince' => 'Dhaka',
            'shippingZip' => '1207',
            'shippingCountry' => 'Bangladesh',
            'email' => 'oms-test@trendydealbd.shop',
            'deliveryCharge' => 120,
            'discount' => 0,
            'advance' => 0,
            'totalAmount' => 670,
            'note' => 'OMS CONNECTION TEST FROM TRENDY DEAL BD - SAFE TO DELETE',
            'items' => [
                [
                    'sku' => 'TDBD-TEST-001',
                    'name' => 'OMS Demo Product',
                    'quantity' => 1,
                    'price' => 550,
                ],
            ],
        ];

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(20)
                ->post($endpoint, $payload);

            $body = trim($response->body());
            $bodyPreview = mb_substr($body, 0, 3000);

            return [
                'ok' => $response->successful(),
                'message' => $response->successful()
                    ? 'OMS connection successful. Demo order was accepted by the endpoint.'
                    : 'OMS responded, but rejected the demo order.',
                'http_status' => $response->status(),
                'invoice_id' => $invoiceId,
                'external_order_id' => $externalOrderId,
                'response' => $bodyPreview !== '' ? $bodyPreview : '(empty response body)',
                'demo_payload' => $this->maskApiKey($payload),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Could not connect to the OMS endpoint.',
                'http_status' => null,
                'invoice_id' => $invoiceId,
                'external_order_id' => $externalOrderId,
                'response' => mb_substr($e->getMessage(), 0, 3000),
                'demo_payload' => $this->maskApiKey($payload),
            ];
        }
    }

    private function maskApiKey(array $payload): array
    {
        $payload['apiKey'] = '******** (saved/current API key)';
        return $payload;
    }
}
