<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\MetaCapiService;
use App\Services\OmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CheckoutController extends Controller
{
    public function show()
    {
        $cart = array_values(session('cart', []));
        if (! count($cart)) return redirect()->route('shop')->with('error','আপনার কার্ট খালি।');
        return view('store.checkout', compact('cart'));
    }

    public function quick(Request $request)
    {
        $cart = array_values(session('cart', []));

        if (! count($cart)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'আপনার কার্ট খালি।'], 422);
            }

            return response('আপনার কার্ট খালি।', 422);
        }

        return view('store.partials.checkout-popup', compact('cart'));
    }

    public function store(Request $request, OmsService $oms, MetaCapiService $meta)
    {
        $validated = $request->validate([
            'customer_name'=>'required|string|max:120','phone'=>'required|string|max:30','address'=>'required|string|max:1000',
            'shipping_area'=>'required|in:inside,outside','email'=>'nullable|email|max:190','note'=>'nullable|string|max:1000','event_id'=>'nullable|string|max:120',
        ]);
        $cart = array_values(session('cart', []));
        if (! count($cart)) {
            if ($request->expectsJson()) {
                throw ValidationException::withMessages(['cart' => 'কার্ট খালি।']);
            }
            return back()->withErrors(['cart'=>'কার্ট খালি।']);
        }

        $inside = (float) Setting::getValue('shipping_inside_dhaka', '60');
        $outside = (float) Setting::getValue('shipping_outside_dhaka', '120');
        $delivery = $validated['shipping_area'] === 'inside' ? $inside : $outside;
        $products = Product::query()->whereIn('id', collect($cart)->pluck('product_id'))->get()->keyBy('id');
        $subtotal = 0;
        foreach ($cart as $line) {
            $p = $products->get($line['product_id']);
            if (! $p || ! $p->is_active) {
                if ($request->expectsJson()) {
                    throw ValidationException::withMessages(['cart' => 'কার্টের একটি পণ্য বর্তমানে পাওয়া যাচ্ছে না।']);
                }
                return back()->withErrors(['cart'=>'কার্টের একটি পণ্য বর্তমানে পাওয়া যাচ্ছে না।']);
            }
            $subtotal += (float)$p->price * (int)$line['quantity'];
        }
        $purchaseEventId = $validated['event_id'] ?: 'purchase_'.Str::uuid();

        $order = DB::transaction(function () use ($validated,$cart,$products,$subtotal,$delivery,$purchaseEventId) {
            // Create with a temporary unique code first so we can use the database order ID.
            // Final invoice format is always: TW + 6 digits, e.g. TW000123.
            $temporary = 'TMP-'.Str::uuid();

            $order = Order::create([
                'invoice_id'=>$temporary,
                'external_order_id'=>$temporary,
                'customer_name'=>$validated['customer_name'],'phone'=>$validated['phone'],
                'address'=>$validated['address'],'shipping_phone'=>$validated['phone'],'shipping_customer_name'=>$validated['customer_name'],
                'shipping_address1'=>$validated['address'],'shipping_address2'=>'','shipping_city'=>'','shipping_province'=>'','shipping_zip'=>'',
                'shipping_country'=>'Bangladesh','email'=>$validated['email']??null,'delivery_charge'=>$delivery,'discount'=>0,'advance'=>0,
                'subtotal'=>$subtotal,'total_amount'=>$subtotal+$delivery,'note'=>$validated['note']??null,'status'=>'PENDING','oms_status'=>'PENDING','purchase_event_id'=>$purchaseEventId,
            ]);

            if ($order->id > 999999) {
                throw new RuntimeException('TW 6-digit invoice range is exhausted.');
            }

            $invoice = 'TW'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
            $order->forceFill([
                'invoice_id' => $invoice,
                'external_order_id' => $invoice,
            ])->save();

            foreach ($cart as $line) {
                $p=$products->get($line['product_id']); $qty=(int)$line['quantity'];
                $order->items()->create(['product_id'=>$p->id,'sku'=>$p->sku,'name'=>$p->name_bn ?: $p->name_en,'quantity'=>$qty,'price'=>$p->price,'total'=>(float)$p->price*$qty]);
            }
            return $order;
        });

        $oms->send($order);
        $meta->send('Purchase', $purchaseEventId, [
            'currency'=>'BDT','value'=>(float)$order->total_amount,'order_id'=>$order->invoice_id,
            'content_type'=>'product','contents'=>$order->items()->get()->map(fn($i)=>['id'=>$i->sku,'quantity'=>(int)$i->quantity,'item_price'=>(float)$i->price])->all(),
        ], $request, route('order.success',$order->invoice_id));

        session()->forget('cart');

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'invoice_id' => $order->invoice_id,
                'redirect' => route('order.success', $order->invoice_id),
            ]);
        }

        return redirect()->route('order.success',$order->invoice_id);
    }

    public function success(string $invoiceId)
    {
        $order = Order::query()->where('invoice_id',$invoiceId)->with('items')->firstOrFail();
        return view('store.success', compact('order'));
    }
}
