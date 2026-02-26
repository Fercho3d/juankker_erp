<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    public function index()
    {
        $features = config('erp_features');
        return view('welcome', compact('features'));
    }
}
