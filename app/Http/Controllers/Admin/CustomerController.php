<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->withCount('orders');
        if ($request->filled('search')) {
            $query->where(fn ($builder) => $builder->where('name', 'like', '%' . $request->search . '%')->orWhere('mobile', 'like', '%' . $request->search . '%')->orWhere('email', 'like', '%' . $request->search . '%'));
        }
        if ($request->filled('blocked')) {
            $query->where('is_blocked', $request->blocked === 'yes');
        }

        return view('admin.customers.index', ['customers' => $query->latest()->paginate(20)->withQueryString()]);
    }
}
