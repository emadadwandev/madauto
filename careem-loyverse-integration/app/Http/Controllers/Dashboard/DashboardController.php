<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SyncLog;
use App\Models\ProductMapping;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get statistics
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'synced_orders' => Order::where('status', 'synced')->count(),
            'failed_orders' => Order::where('status', 'failed')->count(),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'product_mappings' => ProductMapping::where('is_active', true)->count(),
            'sync_success_rate' => $this->calculateSyncSuccessRate(),
        ];

        // Get recent orders
        $recentOrders = Order::with('loyverseOrder')
            ->latest()
            ->limit(10)
            ->get();

        // Get recent sync logs
        $recentLogs = SyncLog::with('order')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard.index', compact('stats', 'recentOrders', 'recentLogs'));
    }

    private function calculateSyncSuccessRate()
    {
        $total = Order::count();
        if ($total === 0) {
            return 0;
        }

        $synced = Order::where('status', 'synced')->count();
        return round(($synced / $total) * 100, 2);
    }
}
