<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    public function csv()
    {
        $products = Product::query()->where('is_active',true)->get();
        $brand = Setting::getValue('site_name','Trendy Deal BD');
        $callback = function () use ($products,$brand) {
            $out=fopen('php://output','w');
            fputcsv($out,['id','title','description','availability','condition','price','link','image_link','brand']);
            foreach($products as $p){
                fputcsv($out,[
                    $p->sku,$p->name_bn ?: $p->name_en,Str::limit(strip_tags((string)($p->short_description ?: $p->description_html)),500),
                    $p->stock_qty>0?'in stock':'out of stock','new',number_format((float)$p->price,2,'.','').' BDT',
                    route('product.show',$p->slug),$p->main_image_path?asset('storage/'.$p->main_image_path):'', $brand
                ]);
            }
            fclose($out);
        };
        return response()->streamDownload($callback,'meta-catalog.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
