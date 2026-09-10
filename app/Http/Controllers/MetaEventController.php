<?php

namespace App\Http\Controllers;

use App\Services\MetaCapiService;
use Illuminate\Http\Request;

class MetaEventController extends Controller
{
    public function store(Request $request, MetaCapiService $meta)
    {
        $data = $request->validate([
            'event_name'=>'required|string|max:80','event_id'=>'required|string|max:120','source_url'=>'nullable|url|max:2000',
            'custom_data'=>'nullable|array'
        ]);
        $allowed = ['PageView','ViewContent','AddToCart','InitiateCheckout','Search','Contact','ViewCategory'];
        abort_unless(in_array($data['event_name'],$allowed,true),422,'Unsupported event');
        $meta->send($data['event_name'],$data['event_id'],$data['custom_data']??[],$request,$data['source_url']??null);
        return response()->json(['ok'=>true]);
    }
}
