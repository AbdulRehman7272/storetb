<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        return view('storefront.cart', ['cart' => $this->cart()->load('items.product.primaryMedia', 'items.variant')]);
    }

    public function add(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $variant = null;
        if (! empty($validated['variant_id'])) {
            $variant = ProductVariant::query()->where('product_id', $product->id)->where('is_enabled', true)->findOrFail($validated['variant_id']);
        }

        $cart = $this->cart();
        $item = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->first();

        if ($item) {
            $item->increment('quantity', $validated['quantity'] ?? 1);
        } else {
            CartItem::query()->create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $validated['quantity'] ?? 1,
                'unit_price' => $variant?->price() ?? $product->price(),
                'options' => $variant ? ['variant' => $variant->name] : null,
            ]);
        }

        return redirect()->route($request->boolean('buy_now') ? 'checkout.index' : 'cart.index')->with('status', 'Cart updated.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate(['quantities' => ['array']]);
        foreach ($validated['quantities'] ?? [] as $itemId => $quantity) {
            CartItem::query()->where('cart_id', $this->cart()->id)->whereKey($itemId)->update(['quantity' => max(1, (int) $quantity)]);
        }

        return back()->with('status', 'Cart quantities updated.');
    }

    public function remove(CartItem $item)
    {
        abort_unless($item->cart_id === $this->cart()->id, 403);
        $item->delete();

        return back()->with('status', 'Item removed.');
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
}
