<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'cards' => [
                'Revenue' => Order::query()->whereNotIn('status', ['cancelled', 'returned'])->sum('grand_total'),
                'Orders' => Order::query()->count(),
                'Products' => Product::query()->count(),
                'Customers' => Customer::query()->count(),
                'Payment proofs' => Order::query()->where('payment_status', 'verification_pending')->count(),
            ],
            'recentOrders' => Order::query()->latest()->take(8)->get(),
            'dailySales' => Order::query()
                ->selectRaw('DATE(created_at) as day, SUM(grand_total) as total')
                ->where('created_at', '>=', now()->subDays(14))
                ->groupBy('day')
                ->orderBy('day')
                ->get(),
            'lowStock' => Product::query()->whereColumn('stock', '<=', 'low_stock_threshold')->take(8)->get(),
        ]);
    }
}
