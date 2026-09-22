<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->with('customer', 'paymentAccount')->withCount('items');
        foreach (['status', 'payment_status', 'payment_method', 'city', 'province'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->$filter);
            }
        }
        if ($request->filled('search')) {
            $query->where(fn ($builder) => $builder
                ->where('order_number', 'like', '%' . $request->search . '%')
                ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                ->orWhere('mobile', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return view('admin.orders.index', [
            'orders' => $query->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load('items.product.media', 'items.variant.optionValues.option', 'payments', 'proofs', 'histories.user', 'paymentAccount');
        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,confirmed,packed,shipped,delivered,cancelled,returned,exchanged'],
            'payment_status' => ['required', 'in:unpaid,verification_pending,verified,rejected,partially_paid,refunded'],
            'internal_notes' => ['nullable', 'string'],
            'courier' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $order->update($validated);
        $order->payments()->latest()->first()?->update(['status' => $validated['payment_status']]);
        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'note' => $validated['note'] ?? 'Updated by admin.',
        ]);

        return back()->with('status', 'Order updated.');
    }

    public function proof(PaymentProof $proof)
    {
        abort_unless(Storage::exists($proof->path), 404);
        return Storage::download($proof->path, $proof->original_name);
    }
}
