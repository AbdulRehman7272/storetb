<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAccount;
use Illuminate\Http\Request;

class PaymentAccountController extends Controller
{
    public function index()
    {
        return view('admin.payment-accounts.index', ['accounts' => PaymentAccount::query()->orderBy('display_order')->paginate(20)]);
    }

    public function create()
    {
        return view('admin.payment-accounts.form', ['account' => new PaymentAccount(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        PaymentAccount::query()->create($this->payload($request));
        return redirect()->route('admin.payment-accounts.index')->with('status', 'Payment account saved.');
    }

    public function edit(PaymentAccount $paymentAccount)
    {
        return view('admin.payment-accounts.form', ['account' => $paymentAccount]);
    }

    public function update(Request $request, PaymentAccount $paymentAccount)
    {
        $paymentAccount->update($this->payload($request));
        return redirect()->route('admin.payment-accounts.index')->with('status', 'Payment account updated.');
    }

    public function destroy(PaymentAccount $paymentAccount)
    {
        $paymentAccount->update(['is_active' => false]);
        return back()->with('status', 'Payment account disabled.');
    }

    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:bank,easypaisa,jazzcash,sadapay,nayapay,other'],
            'account_title' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'branch_code' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return $validated + ['is_active' => $request->boolean('is_active')];
    }
}
