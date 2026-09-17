<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryMovement::query()->with('product');
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $query->where('reference', 'like', '%' . $request->search . '%');
        }

        return view('admin.inventory.index', [
            'movements' => $query->latest()->paginate(20)->withQueryString(),
            'lowStock' => Product::query()->whereColumn('stock', '<=', 'low_stock_threshold')->get(),
        ]);
    }
}
