<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('loyverseOrder')->latest()->paginate(20);

        return view('dashboard.orders.index', compact('orders'));
    }
}
