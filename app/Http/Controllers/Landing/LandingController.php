<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;

class LandingController extends Controller
{
    /**
     * Display the landing page.
     */
    public function index()
    {
        return view('landing.index');
    }

    /**
     * Display the pricing page.
     */
    public function pricing()
    {
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('landing.pricing', compact('plans'));
    }
}
