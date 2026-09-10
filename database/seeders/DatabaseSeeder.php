<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email'=>env('ADMIN_EMAIL','admin@trendydealbd.shop')],[
            'name'=>env('ADMIN_NAME','Trendy Deal BD Admin'),'password'=>Hash::make(env('ADMIN_PASSWORD','ChangeMe123!')),'is_admin'=>true,
        ]);
        $defaults=[
            'site_name'=>'Trendy Deal BD','site_subtitle'=>'স্মার্ট শপিং, সহজ জীবন','primary_color'=>'#00B957','secondary_color'=>'#122B35',
            'help_line'=>'01712969880','phone'=>'01712969880','email'=>'','address'=>'Bangladesh','shipping_inside_dhaka'=>'60','shipping_outside_dhaka'=>'120',
            'hero_title'=>'সেরা ডিল, দ্রুত ডেলিভারি','hero_subtitle'=>'বিশ্বস্ত পণ্য, সহজ অর্ডার এবং দ্রুত ডেলিভারি।','hero_button_text'=>'এখনই শপ করুন','hero_button_url'=>'/shop',
            'footer_shop_title'=>'শপ করুন','footer_shop_links'=>"হোম & লিভিং|/shop\nগার্ডেনিং|/shop\nট্রেন্ডি আইটেম|/shop",
            'footer_useful_title'=>'প্রয়োজনীয় লিংক','footer_useful_links'=>"আমাদের সম্পর্কে|/\nশপ|/shop\nযোগাযোগ|/",
            'footer_service_title'=>'কাস্টমার সার্ভিস','footer_service_links'=>"FAQ|/\nশিপিং ও রিটার্ন|/\nসাপোর্ট|/",
            'footer_copyright'=>'© '.date('Y').' Trendy Deal BD. All Rights Reserved.','meta_api_version'=>'v24.0','meta_browser_enabled'=>'0','meta_capi_enabled'=>'0','oms_enabled'=>'0'
        ];
        foreach($defaults as $k=>$v)Setting::query()->updateOrCreate(['key'=>$k],['value'=>$v]);
        $cat=Category::firstOrCreate(['slug'=>'trendy-item'],['name_bn'=>'ট্রেন্ডি আইটেম','name_en'=>'Trendy Item','icon'=>'⭐','is_active'=>true,'sort_order'=>1]);
        Subcategory::firstOrCreate(['slug'=>'general'],['category_id'=>$cat->id,'name_bn'=>'সাধারণ','name_en'=>'General','is_active'=>true,'sort_order'=>1]);
    }
}
