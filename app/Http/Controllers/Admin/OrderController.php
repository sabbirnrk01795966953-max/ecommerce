<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OmsService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const STATUSES = [
        'PENDING',
        'CONFIRMED',
        'PROCESSING',
        'SHIPPED',
        'DELIVERED',
        'RETURNED',
        'CANCELLED',
    ];

    public function index(Request $r)
    {
        $q = Order::query();

        if ($r->filled('date')) {
            $q->whereDate('created_at', $r->date);
        }

        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }

        if ($r->filled('q')) {
            $term = trim((string) $r->q);
            $q->where(function ($query) use ($term) {
                $query->where('invoice_id', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%");
            });
        }

        return view('admin.orders.index', [
            'orders' => $q->latest()->paginate(40)->withQueryString(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(Order $order)
    {
        $order->load('items');

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $r, Order $order)
    {
        $data = $r->validate([
            'status' => 'required|in:'.implode(',', self::STATUSES),
        ]);

        $order->update($data);

        return back()->with('success', 'Order status updated.');
    }

    public function bulkStatus(Request $r)
    {
        $data = $r->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
        ], [
            'order_ids.required' => 'কমপক্ষে একটি অর্ডার সিলেক্ট করুন।',
            'order_ids.min' => 'কমপক্ষে একটি অর্ডার সিলেক্ট করুন।',
            'status.required' => 'নতুন স্ট্যাটাস নির্বাচন করুন।',
        ]);

        $count = Order::query()
            ->whereIn('id', $data['order_ids'])
            ->update([
                'status' => $data['status'],
                'updated_at' => now(),
            ]);

        return back()->with('success', $count.'টি অর্ডারের স্ট্যাটাস '.$data['status'].' করা হয়েছে।');
    }

    public function destroy(Order $order)
    {
        $invoice = $order->invoice_id;
        $wasSent = $order->oms_status === 'SENT';

        $order->delete();

        $message = 'অর্ডার '.$invoice.' ডিলিট করা হয়েছে।';
        if ($wasSent) {
            $message .= ' এটি OMS-এ আগে পাঠানো ছিল; এখানে ডিলিট করলে OMS-এর কপি ডিলিট হয় না।';
        }

        return redirect()->route('admin.orders.index')->with('success', $message);
    }

    public function resend(Order $order, OmsService $oms)
    {
        $oms->send($order);

        return back()->with('success', 'OMS send attempted. Current status: '.$order->fresh()->oms_status);
    }
}
