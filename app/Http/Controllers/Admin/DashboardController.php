<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $stats=[
            'today_orders'=>Order::query()->whereDate('created_at',today())->count(),
            'today_sales'=>Order::query()->whereDate('created_at',today())->whereNotIn('status',['CANCELLED'])->sum('total_amount'),
            'pending_orders'=>Order::query()->where('status','PENDING')->count(),
            'products'=>Product::query()->count(),
        ];
        $recent=Order::query()->latest()->limit(8)->get();
        return view('admin.dashboard',compact('stats','recent'));
    }
}
