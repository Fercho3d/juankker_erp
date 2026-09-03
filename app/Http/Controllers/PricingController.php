<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index()
    {
        if (! Setting::bool('show_plans', true)) {
            abort(404);
        }

        $plans = Plan::where('activo', true)->where('visible', true)
            ->orderBy('orden')->get();

        return view('pricing', [
            'plans' => $plans,
            'showPrices' => Setting::bool('show_prices', true),
        ]);
    }
}
