<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the super admin dashboard.
     */
    public function index()
    {
        // Get key metrics
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'suspended_tenants' => Tenant::where('status', 'suspended')->count(),
            'trial_tenants' => Tenant::whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now())
                ->count(),

            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'cancelled_subscriptions' => Subscription::where('status', 'cancelled')->count(),
            'past_due_subscriptions' => Subscription::where('status', 'past_due')->count(),

            'total_orders_today' => Order::whereDate('created_at', today())->count(),
            'total_orders_month' => Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'total_orders_all_time' => Order::count(),

            'total_users' => User::count(),
            'users_joined_this_month' => User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        // Calculate MRR (Monthly Recurring Revenue)
        $mrr = Subscription::join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->where('subscriptions.status', 'active')
            ->sum('subscription_plans.price');

        $stats['mrr'] = $mrr;

        // Get revenue chart data (last 12 months)
        $revenueRaw = Subscription::join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->where('subscriptions.status', 'active')
            ->whereDate('subscriptions.created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('MONTH(subscriptions.created_at) as month'),
                DB::raw('YEAR(subscriptions.created_at) as year'),
                DB::raw('SUM(subscription_plans.price) as revenue')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $revenueChartData = [
            'labels' => $revenueRaw->map(function ($item) {
                return date('M Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            })->toArray(),
            'data' => $revenueRaw->pluck('revenue')->toArray(),
        ];

        // Get order volume chart data (last 30 days)
        $orderVolumeRaw = Order::whereDate('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $orderVolumeData = [
            'labels' => $orderVolumeRaw->map(function ($item) {
                return date('M d', strtotime($item->date));
            })->toArray(),
            'data' => $orderVolumeRaw->pluck('count')->toArray(),
        ];

        // Get tenant growth chart data (last 12 months)
        $tenantGrowthRaw = Tenant::whereDate('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $tenantGrowthData = [
            'labels' => $tenantGrowthRaw->map(function ($item) {
                return date('M Y', mktime(0, 0, 0, $item->month, 1, $item->year));
            })->toArray(),
            'data' => $tenantGrowthRaw->pluck('count')->toArray(),
        ];

        // Get recent activity (last 10 tenants)
        $recentTenants = Tenant::with('subscription.subscriptionPlan')
            ->latest()
            ->take(10)
            ->get();

        // Convert recent tenants to activity format for the view
        $recentActivity = $recentTenants->map(function ($tenant) {
            return (object) [
                'description' => "New tenant registered: {$tenant->name} ({$tenant->subdomain})",
                'created_at' => $tenant->created_at,
            ];
        });

        // Get tenants with issues (suspended or past_due subscriptions)
        $tenantsWithIssues = Tenant::where('status', 'suspended')
            ->orWhereHas('subscription', function ($query) {
                $query->where('status', 'past_due');
            })
            ->with('subscription.subscriptionPlan')
            ->take(10)
            ->get();

        return view('super-admin.dashboard', compact(
            'stats',
            'revenueChartData',
            'orderVolumeData',
            'tenantGrowthData',
            'recentActivity',
            'tenantsWithIssues'
        ));
    }
}
