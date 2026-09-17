<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date('from', now()->subDays(30))->startOfDay();
        $to = $request->date('to', now())->endOfDay();
        $orders = Order::query()->whereBetween('created_at', [$from, $to]);

        return view('admin.reports.index', [
            'grossSales' => (clone $orders)->sum('subtotal'),
            'netSales' => (clone $orders)->whereNotIn('status', ['cancelled', 'returned'])->sum('grand_total'),
            'ordersCount' => (clone $orders)->count(),
            'averageOrderValue' => (clone $orders)->avg('grand_total') ?: 0,
            'pendingVerification' => Order::query()->where('payment_status', 'verification_pending')->count(),
            'bestProducts' => OrderItem::query()->selectRaw('product_name, SUM(quantity) as qty, SUM(line_total) as total')->groupBy('product_name')->orderByDesc('qty')->take(10)->get(),
            'lowStock' => Product::query()->whereColumn('stock', '<=', 'low_stock_threshold')->get(),
            'dailySales' => Order::query()->selectRaw('DATE(created_at) as day, SUM(grand_total) as total')->whereBetween('created_at', [$from, $to])->groupBy('day')->orderBy('day')->get(),
        ]);
    }
}
