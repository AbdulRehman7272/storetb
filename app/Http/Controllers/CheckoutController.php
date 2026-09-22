<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PaymentProof;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = $this->cart()->load('items.product.primaryMedia', 'items.variant');
        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        return view('storefront.checkout', [
            'cart' => $cart,
            'shipping' => ShippingMethod::query()->where('is_active', true)->first(),
            'paymentAccounts' => PaymentAccount::query()->where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $cart = $this->cart()->load('items.product.primaryMedia', 'items.variant');
        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        $order = DB::transaction(function () use ($request, $cart) {
            $customer = Customer::query()->firstOrCreate(
                ['mobile' => $request->mobile],
                ['name' => $request->full_name, 'email' => $request->email]
            );
            $customer->update([
                'name' => $request->full_name,
                'secondary_mobile' => $request->secondary_mobile,
                'email' => $request->email,
                'last_order_at' => now(),
            ]);
            $customer->addresses()->updateOrCreate(
                ['address' => $request->address, 'city' => $request->city],
                ['province' => $request->province, 'landmark' => $request->landmark, 'is_default' => true]
            );

            $subtotal = $cart->items->sum(fn ($item) => $item->quantity * $item->unit_price);
            $shipping = ShippingMethod::query()->where('is_active', true)->first();
            ['shipping' => $shippingTotal, 'discount' => $paymentDiscount] = $this->paymentAdjustments($subtotal, $request->payment_method);

            $order = Order::query()->create([
                'order_number' => 'TB-' . now()->format('ymd') . '-' . strtoupper(Str::random(5)),
                'customer_id' => $customer->id,
                'shipping_method_id' => $shipping?->id,
                'customer_name' => $request->full_name,
                'mobile' => $request->mobile,
                'secondary_mobile' => $request->secondary_mobile,
                'email' => $request->email,
                'province' => $request->province,
                'city' => $request->city,
                'address' => $request->address,
                'landmark' => $request->landmark,
                'customer_notes' => $request->notes,
                'subtotal' => $subtotal,
                'discount_total' => $paymentDiscount,
                'shipping_total' => $shippingTotal,
                'grand_total' => max(0, $subtotal - $paymentDiscount + $shippingTotal),
                'payment_method' => $request->payment_method,
                'payment_account_id' => $request->payment_account_id,
                'payment_status' => $request->payment_method === 'manual' ? 'verification_pending' : 'unpaid',
            ]);

            foreach ($cart->items as $item) {
                $variant = $item->variant;
                $product = $item->product;
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'variant_name' => $variant?->name,
                    'sku' => $variant?->sku ?: $product->sku,
                    'image' => $variant?->image ?: $product->primaryMedia?->path,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->quantity * $item->unit_price,
                    'options' => $item->options,
                ]);

                $stockModel = $variant ?: $product;
                $previous = $stockModel->stock;
                $stockModel->decrement('stock', $item->quantity);
                InventoryMovement::query()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'order_id' => $order->id,
                    'type' => 'order_reservation',
                    'previous_quantity' => $previous,
                    'change_quantity' => -$item->quantity,
                    'new_quantity' => $previous - $item->quantity,
                    'reason' => 'Stock reserved during checkout',
                    'reference' => $order->order_number,
                ]);
            }

            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'payment_account_id' => $request->payment_account_id,
                'amount' => $order->grand_total,
                'method' => $request->payment_method,
                'status' => $order->payment_status,
                'transaction_reference' => $request->transaction_reference,
            ]);

            if ($request->hasFile('payment_screenshot')) {
                $file = $request->file('payment_screenshot');
                $path = $file->store('payment-proofs');
                PaymentProof::query()->create([
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'status' => 'verification_pending',
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'status' => 'new',
                'payment_status' => $order->payment_status,
                'note' => 'Order placed by customer.',
            ]);

            $customer->increment('orders_count');
            $customer->increment('total_spent', $order->grand_total);
            $cart->items()->delete();

            return $order;
        });

        return redirect()->route('checkout.success', $order)->with('status', 'Order placed successfully.');
    }

    public function success(Order $order)
    {
        return view('storefront.success', compact('order'));
    }

    public function storeFrontend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'], 'mobile' => ['required', 'regex:/^(\+92|0)?3\d{9}$/'], 'email' => ['nullable', 'email', 'max:255'],
            'province' => ['required', 'string', 'max:100'], 'city' => ['required', 'string', 'max:100'], 'address' => ['required', 'string', 'max:1000'], 'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:cod,manual'], 'payment_account_id' => ['nullable', 'required_if:payment_method,manual', 'exists:payment_accounts,id'],
            'transaction_reference' => ['nullable', 'string', 'max:255'], 'payment_screenshot' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'items' => ['required', 'array', 'min:1'], 'items.*.product_id' => ['required', 'exists:products,id'], 'items.*.variant_id' => ['nullable', 'exists:product_variants,id'], 'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $order = DB::transaction(function () use ($request, $data) {
            $customer = Customer::query()->firstOrCreate(['mobile' => $data['mobile']], ['name' => $data['full_name'], 'email' => $data['email'] ?? null]);
            $customer->update(['name' => $data['full_name'], 'email' => $data['email'] ?? null, 'last_order_at' => now()]);
            $customer->addresses()->updateOrCreate(['address' => $data['address'], 'city' => $data['city']], ['province' => $data['province'], 'is_default' => true]);
            $lines = collect($data['items'])->map(function ($line) {
                $product = Product::with('primaryMedia')->where('status', 'published')->lockForUpdate()->findOrFail($line['product_id']);
                $variant = filled($line['variant_id'] ?? null) ? ProductVariant::where('product_id', $product->id)->where('is_enabled', true)->lockForUpdate()->findOrFail($line['variant_id']) : null;
                $stock = $variant ?: $product;
                abort_if($stock->stock < $line['quantity'], 422, "Insufficient stock for {$product->name}.");
                return compact('product', 'variant', 'stock', 'line') + ['price' => $variant?->price() ?? $product->price()];
            });
            $subtotal = $lines->sum(fn ($line) => $line['price'] * $line['line']['quantity']);
            $shippingMethod = ShippingMethod::query()->where('is_active', true)->first();
            ['shipping' => $shipping, 'discount' => $paymentDiscount] = $this->paymentAdjustments($subtotal, $data['payment_method']);
            $order = Order::create(['order_number' => 'TB-'.now()->format('ymd').'-'.strtoupper(Str::random(5)), 'customer_id' => $customer->id, 'shipping_method_id' => $shippingMethod?->id, 'customer_name' => $data['full_name'], 'mobile' => $data['mobile'], 'email' => $data['email'] ?? null, 'province' => $data['province'], 'city' => $data['city'], 'address' => $data['address'], 'customer_notes' => $data['notes'] ?? null, 'subtotal' => $subtotal, 'discount_total' => $paymentDiscount, 'shipping_total' => $shipping, 'grand_total' => max(0, $subtotal - $paymentDiscount + $shipping), 'payment_method' => $data['payment_method'], 'payment_account_id' => $data['payment_account_id'] ?? null, 'payment_status' => $data['payment_method'] === 'manual' ? 'verification_pending' : 'unpaid']);
            foreach ($lines as $line) {
                $product = $line['product']; $variant = $line['variant']; $quantity = $line['line']['quantity']; $previous = $line['stock']->stock;
                OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_variant_id' => $variant?->id, 'product_name' => $product->name, 'variant_name' => $variant?->name, 'sku' => $variant?->sku ?: $product->sku, 'image' => $variant?->image ?: $product->primaryMedia?->path, 'quantity' => $quantity, 'unit_price' => $line['price'], 'line_total' => $line['price'] * $quantity]);
                $line['stock']->decrement('stock', $quantity);
                if ($variant) $product->update(['stock' => $product->variants()->sum('stock')]);
                InventoryMovement::create(['product_id' => $product->id, 'product_variant_id' => $variant?->id, 'order_id' => $order->id, 'type' => 'order_reservation', 'previous_quantity' => $previous, 'change_quantity' => -$quantity, 'new_quantity' => $previous - $quantity, 'reason' => 'Stock reserved during storefront checkout', 'reference' => $order->order_number]);
            }
            $payment = Payment::create(['order_id' => $order->id, 'payment_account_id' => $data['payment_account_id'] ?? null, 'amount' => $order->grand_total, 'method' => $data['payment_method'], 'status' => $order->payment_status, 'transaction_reference' => $data['transaction_reference'] ?? null]);
            if ($request->hasFile('payment_screenshot')) { $file = $request->file('payment_screenshot'); $path = $file->store('payment-proofs'); PaymentProof::create(['order_id' => $order->id, 'payment_id' => $payment->id, 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'status' => 'verification_pending']); }
            OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'new', 'payment_status' => $order->payment_status, 'note' => 'Order placed through storefront.']);
            $customer->increment('orders_count'); $customer->increment('total_spent', $order->grand_total);
            return $order;
        });
        return response()->json(['message' => 'Order placed successfully.', 'order_number' => $order->order_number, 'order_id' => $order->id, 'total' => (float) $order->grand_total]);
    }

    private function cart(): Cart
    {
        if (request()->session()->has('cart_id')) {
            return Cart::query()->firstOrCreate(['id' => request()->session()->get('cart_id')], ['session_id' => request()->session()->getId()]);
        }

        $cart = Cart::query()->firstOrCreate(['session_id' => request()->session()->getId()]);
        request()->session()->put('cart_id', $cart->id);

        return $cart;
    }

    private function paymentAdjustments(float $subtotal, string $paymentMethod): array
    {
        $manualPayment = $paymentMethod === 'manual';
        $threshold = (float) StoreSettings::get('free_shipping_threshold', 5000);
        $freeForAdvancePayment = (bool) StoreSettings::get('advance_payment_free_shipping', false);
        $shipping = $subtotal === 0 || ($threshold > 0 && $subtotal >= $threshold) || ($manualPayment && $freeForAdvancePayment)
            ? 0
            : (float) StoreSettings::get('shipping_charge', 250);
        $discount = $manualPayment
            ? min($subtotal, (float) StoreSettings::get('advance_payment_discount', 0))
            : 0;

        return compact('shipping', 'discount');
    }
}
