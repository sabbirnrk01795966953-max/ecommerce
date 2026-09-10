<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index() { return view('store.cart', ['cart'=>$this->cart()]); }

    public function add(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);
        $qty = max(1, min(99, (int) $request->input('quantity',1)));
        $cart = session('cart', []);
        $existing = $cart[$product->id]['quantity'] ?? 0;
        $cart[$product->id] = [
            'product_id'=>$product->id,'name'=>$product->name_bn ?: $product->name_en,'slug'=>$product->slug,'sku'=>$product->sku,
            'price'=>(float)$product->price,'quantity'=>min(99,$existing+$qty),'image'=>$product->main_image_path,
        ];
        session(['cart'=>$cart]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'পণ্যটি কার্টে যোগ হয়েছে।',
                'cart_count' => collect($cart)->sum('quantity'),
            ]);
        }

        return back()->with('success','পণ্যটি কার্টে যোগ হয়েছে।');
    }

    public function update(Request $request, Product $product)
    {
        $cart = session('cart', []);
        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] = max(1, min(99, (int)$request->input('quantity',1)));
            session(['cart'=>$cart]);
        }
        return back();
    }

    public function remove(Product $product)
    {
        $cart = session('cart', []); unset($cart[$product->id]); session(['cart'=>$cart]);
        return back();
    }

    private function cart(): array { return array_values(session('cart', [])); }
}
