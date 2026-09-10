<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::query()->where('is_active', true)->with(['subcategories'=>fn($q)=>$q->where('is_active',true)])->orderBy('sort_order')->get();
        $products = Product::query()->where('is_active', true)->with('subcategory')->orderByDesc('is_featured')->orderByDesc('id')->limit(20)->get();
        return view('store.home', compact('categories','products'));
    }

    public function shop()
    {
        $products = Product::query()->where('is_active', true)->with('subcategory')->latest()->paginate(20);
        $categories = Category::query()->where('is_active', true)->with(['subcategories'=>fn($q)=>$q->where('is_active',true)])->orderBy('sort_order')->get();
        return view('store.shop', compact('products','categories'));
    }
}
