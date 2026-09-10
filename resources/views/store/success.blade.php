@extends('layouts.store')

@section('title', 'অর্ডার সফল - '.$order->invoice_id)

@section('content')
<section class="container section narrow">
    <div class="success-card">
        <div class="success-icon">✓</div>
        <h1>আপনার অর্ডারটি গ্রহণ করা হয়েছে</h1>
        <p>ইনভয়েস: <strong>{{ $order->invoice_id }}</strong></p>
        <p>মোট: <strong>৳{{ number_format((float) $order->total_amount, 2) }}</strong></p>
        <p>আমাদের টিম আপনার সাথে যোগাযোগ করবে।</p>
        <a class="btn primary" href="{{ route('home') }}">হোমে ফিরে যান</a>
    </div>
</section>
@endsection

@php
    $purchaseContents = $order->items->map(function ($item) {
        return [
            'id' => $item->sku,
            'quantity' => (int) $item->quantity,
            'item_price' => (float) $item->price,
        ];
    })->values()->all();

    $purchaseData = [
        'currency' => 'BDT',
        'value' => (float) $order->total_amount,
        'content_type' => 'product',
        'contents' => $purchaseContents,
        'order_id' => $order->invoice_id,
    ];

    $purchaseEventId = $order->purchase_event_id;
@endphp

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.shopMeta?.enabled || typeof fbq !== 'function') {
        return;
    }

    const purchaseData = {{ \Illuminate\Support\Js::from($purchaseData) }};
    const purchaseEventId = {{ \Illuminate\Support\Js::from($purchaseEventId) }};

    fbq('track', 'Purchase', purchaseData, {
        eventID: purchaseEventId
    });
});
</script>
@endpush
