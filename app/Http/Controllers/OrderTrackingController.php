<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function index()
    {
        return view('storefront.track');
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
            'mobile' => ['required', 'string'],
        ]);

        $order = Order::query()
            ->with('items', 'histories')
            ->where('order_number', $validated['order_number'])
            ->where('mobile', $validated['mobile'])
            ->first();

        return view('storefront.track', compact('order'));
    }
}
