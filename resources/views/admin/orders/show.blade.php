@extends('layouts.admin')
@section('title',$order->invoice_id)
@section('page_title','অর্ডার '.$order->invoice_id)

@section('content')
@php
    $statusLabels = [
        'PENDING' => 'পেন্ডিং',
        'CONFIRMED' => 'কনফার্মড',
        'PROCESSING' => 'প্রসেসিং',
        'SHIPPED' => 'শিপড',
        'DELIVERED' => 'ডেলিভারড',
        'RETURNED' => 'রিটার্ন',
        'CANCELLED' => 'বাতিল',
    ];
@endphp

<div class="page-actions order-detail-actions">
    <a class="btn outline" href="{{ route('admin.orders.index') }}">← সব অর্ডার</a>
    <form action="{{ route('admin.orders.destroy',$order) }}" method="post" onsubmit="return confirm('অর্ডার {{ $order->invoice_id }} ডিলিট করবেন? OMS-এ পাঠানো হয়ে থাকলে OMS-এর কপি ডিলিট হবে না।')">
        @csrf
        @method('DELETE')
        <button class="btn danger" type="submit">অর্ডার ডিলিট করুন</button>
    </form>
</div>

<div class="two-col">
    <div class="panel">
        <h2>কাস্টমার</h2>
        <dl class="details">
            <dt>নাম</dt><dd>{{ $order->customer_name }}</dd>
            <dt>ফোন</dt><dd>{{ $order->phone }}</dd>
            <dt>ঠিকানা</dt><dd>{{ $order->address }}</dd>
            <dt>ইমেইল</dt><dd>{{ $order->email ?: '-' }}</dd>
            <dt>নোট</dt><dd>{{ $order->note ?: '-' }}</dd>
        </dl>
    </div>

    <div class="panel">
        <h2>অর্ডার অবস্থা</h2>
        <form action="{{ route('admin.orders.update',$order) }}" method="post">
            @csrf
            @method('PATCH')
            <select name="status">
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected($order->status === $status)>
                        {{ $statusLabels[$status] ?? $status }} ({{ $status }})
                    </option>
                @endforeach
            </select>
            <button class="btn primary">Update</button>
        </form>

        <hr>
        <p>OMS: <b>{{ $order->oms_status }}</b></p>
        <form action="{{ route('admin.orders.resend-oms',$order) }}" method="post">
            @csrf
            <button class="btn outline">OMS-এ আবার পাঠান</button>
        </form>

        @if($order->oms_response)
            <details>
                <summary>OMS Response</summary>
                <pre>{{ $order->oms_response }}</pre>
            </details>
        @endif
    </div>
</div>

<div class="panel table-wrap">
    <table>
        <thead>
            <tr><th>SKU</th><th>পণ্য</th><th>Qty</th><th>দাম</th><th>মোট</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>৳{{ number_format((float)$item->price,2) }}</td>
                    <td>৳{{ number_format((float)$item->total,2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><th colspan="4">Subtotal</th><th>৳{{ number_format((float)$order->subtotal,2) }}</th></tr>
            <tr><th colspan="4">Delivery</th><th>৳{{ number_format((float)$order->delivery_charge,2) }}</th></tr>
            <tr><th colspan="4">Grand Total</th><th>৳{{ number_format((float)$order->total_amount,2) }}</th></tr>
        </tfoot>
    </table>
</div>
@endsection
