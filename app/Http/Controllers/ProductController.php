<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Subcategory;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::query()->where('slug',$slug)->where('is_active',true)->with(['images','subcategory.category'])->firstOrFail();
        $related = Product::query()->where('is_active',true)->where('id','!=',$product->id)
            ->when($product->subcategory_id, fn($q)=>$q->where('subcategory_id',$product->subcategory_id))
            ->latest()->limit(4)->get();
        return view('store.product', compact('product','related'));
    }

    public function subcategory(string $slug)
    {
        $subcategory = Subcategory::query()->where('slug',$slug)->where('is_active',true)->with('category')->firstOrFail();
        $products = Product::query()->where('subcategory_id',$subcategory->id)->where('is_active',true)->latest()->paginate(20);
        return view('store.subcategory', compact('subcategory','products'));
    }
}
