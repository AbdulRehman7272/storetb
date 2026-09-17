@extends('layouts.admin')
@section('title', 'Payment Accounts')
@section('content')
<div class="toolbar"><a class="btn" href="{{ route('admin.payment-accounts.create') }}"><i data-lucide="plus"></i>Add Account</a></div>
<section class="panel data-panel"><div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Type</th><th>Account</th><th>Status</th><th></th></tr></thead><tbody>@foreach($accounts as $account)<tr><td><strong>{{ $account->name }}</strong></td><td>{{ str($account->type)->headline() }}</td><td>{{ $account->account_title }}<br><small>{{ $account->account_number }}</small></td><td><span class="status-badge status-badge--{{ $account->is_active ? 'active' : 'inactive' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td><td class="cell-actions"><a class="icon-btn" href="{{ route('admin.payment-accounts.edit',$account) }}" title="Edit {{ $account->name }}"><i data-lucide="pencil"></i></a></td></tr>@endforeach</tbody></table></div></section>{{ $accounts->links() }}
@endsection
