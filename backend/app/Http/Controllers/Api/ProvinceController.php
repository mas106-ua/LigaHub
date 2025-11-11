<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Province;

class ProvinceController extends Controller
{
    public function index(Request $request)
    {
        $regionCode = (string) $request->query('region', '');
        $q = Province::query()->select('id','name','code','region_id');

        if ($regionCode !== '') {
            $q->whereIn('region_id', function ($sub) use ($regionCode) {
                $sub->select('id')->from('regions')->where('code', $regionCode);
            });
        }

        return response()->json($q->orderBy('name')->get());
    }
}
