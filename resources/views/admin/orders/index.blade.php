@extends('layouts.admin')
@section('title','অর্ডার')
@section('page_title','অর্ডার')

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

<form class="panel filter-bar order-filter" method="get" action="{{ route('admin.orders.index') }}">
    <input type="date" name="date" value="{{ request('date') }}">
    <select name="status">
        <option value="">সব স্ট্যাটাস</option>
        @foreach($statuses as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>
                {{ $statusLabels[$status] ?? $status }}
            </option>
        @endforeach
    </select>
    <input name="q" value="{{ request('q') }}" placeholder="Invoice / phone / customer">
    <button class="btn" type="submit">Filter</button>
    <a class="btn outline" href="{{ route('admin.orders.index') }}">Reset</a>
</form>

<form action="{{ route('admin.orders.bulk-status') }}" method="post" id="bulkOrderForm">
    @csrf
    @method('PATCH')

    <div class="panel order-bulk-bar">
        <div class="bulk-selection-info">
            <strong>Bulk Action</strong>
            <span><b id="selectedOrderCount">0</b>টি অর্ডার সিলেক্ট করা হয়েছে</span>
        </div>

        <div class="bulk-status-controls">
            <select name="status" id="bulkOrderStatus" required>
                <option value="">নতুন স্ট্যাটাস নির্বাচন করুন</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}">{{ $statusLabels[$status] ?? $status }} ({{ $status }})</option>
                @endforeach
            </select>
            <button class="btn primary" type="submit" id="bulkStatusApply" disabled>স্ট্যাটাস পরিবর্তন করুন</button>
        </div>
    </div>

    <div class="panel table-wrap order-table-panel">
        <table class="order-table">
            <thead>
                <tr>
                    <th class="select-col">
                        <input type="checkbox" id="selectAllOrders" aria-label="Select all orders on this page">
                    </th>
                    <th>তারিখ</th>
                    <th>ইনভয়েস</th>
                    <th>কাস্টমার</th>
                    <th>ফোন</th>
                    <th>মোট</th>
                    <th>স্ট্যাটাস</th>
                    <th>OMS</th>
                    <th class="order-actions-col">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="select-col">
                            <input
                                class="order-row-checkbox"
                                type="checkbox"
                                name="order_ids[]"
                                value="{{ $order->id }}"
                                aria-label="Select {{ $order->invoice_id }}"
                            >
                        </td>
                        <td>{{ $order->created_at->timezone('Asia/Dhaka')->format('d M Y h:i A') }}</td>
                        <td>
                            <a href="{{ route('admin.orders.show',$order) }}">
                                <b>{{ $order->invoice_id }}</b>
                            </a>
                        </td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->phone }}</td>
                        <td>৳{{ number_format((float)$order->total_amount,2) }}</td>
                        <td>
                            <span class="pill order-status-pill status-{{ strtolower($order->status) }}">
                                {{ $statusLabels[$order->status] ?? $order->status }}
                            </span>
                        </td>
                        <td>
                            <span class="pill {{ strtolower($order->oms_status) }}">{{ $order->oms_status }}</span>
                        </td>
                        <td>
                            <div class="order-row-actions">
                                <a class="btn small outline" href="{{ route('admin.orders.show',$order) }}">দেখুন</a>
                                <button
                                    class="btn small danger"
                                    type="submit"
                                    form="delete-order-{{ $order->id }}"
                                    onclick="return confirm('অর্ডার {{ $order->invoice_id }} ডিলিট করবেন? OMS-এ পাঠানো হয়ে থাকলে OMS-এর কপি ডিলিট হবে না।')"
                                >
                                    ডিলিট
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-table-message">কোনো অর্ডার পাওয়া যায়নি।</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>

@foreach($orders as $order)
    <form
        id="delete-order-{{ $order->id }}"
        action="{{ route('admin.orders.destroy',$order) }}"
        method="post"
        class="hidden-order-delete-form"
    >
        @csrf
        @method('DELETE')
    </form>
@endforeach

{{ $orders->links('vendor.pagination.default') }}
@endsection

@push('scripts')
<script>
(() => {
    const selectAll = document.getElementById('selectAllOrders');
    const boxes = Array.from(document.querySelectorAll('.order-row-checkbox'));
    const count = document.getElementById('selectedOrderCount');
    const applyButton = document.getElementById('bulkStatusApply');
    const bulkForm = document.getElementById('bulkOrderForm');
    const statusSelect = document.getElementById('bulkOrderStatus');

    if (!selectAll || !count || !applyButton || !bulkForm || !statusSelect) return;

    const sync = () => {
        const checked = boxes.filter(box => box.checked).length;
        count.textContent = String(checked);
        applyButton.disabled = checked === 0;
        selectAll.checked = boxes.length > 0 && checked === boxes.length;
        selectAll.indeterminate = checked > 0 && checked < boxes.length;
    };

    selectAll.addEventListener('change', () => {
        boxes.forEach(box => { box.checked = selectAll.checked; });
        sync();
    });

    boxes.forEach(box => box.addEventListener('change', sync));

    bulkForm.addEventListener('submit', (event) => {
        const checked = boxes.filter(box => box.checked).length;
        if (checked === 0) {
            event.preventDefault();
            alert('কমপক্ষে একটি অর্ডার সিলেক্ট করুন।');
            return;
        }

        if (!statusSelect.value) {
            event.preventDefault();
            alert('নতুন স্ট্যাটাস নির্বাচন করুন।');
            statusSelect.focus();
            return;
        }

        if (!confirm(`${checked}টি অর্ডারের স্ট্যাটাস ${statusSelect.value} করতে চান?`)) {
            event.preventDefault();
        }
    });

    sync();
})();
</script>
@endpush
