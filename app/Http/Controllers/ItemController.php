<?php

namespace App\Http\Controllers;

use App\Models\Medium;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    function show($slug) {
        $medium = Medium::where('slug', $slug)->with([
            'type',
            'inspiredBy' => function ($q) {
                $q->with('type');
            },
            'inspired' => function ($q) {
                $q->with('type');
            }
        ])->firstOrFail();

        return view('item', ['medium' => $medium]);
    }

}
