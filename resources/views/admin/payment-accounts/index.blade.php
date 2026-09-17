@extends('layouts.admin')
@section('title', 'Payment Accounts')
@section('content')
<div class="toolbar"><a class="btn" href="{{ route('admin.payment-accounts.create') }}">Add Account</a></div><table class="table table-sm"><tr><th>Name</th><th>Type</th><th>Account</th><th>Status</th><th></th></tr>@foreach($accounts as $account)<tr><td>{{ $account->name }}</td><td>{{ $account->type }}</td><td>{{ $account->account_title }}<br><small>{{ $account->account_number }}</small></td><td>{{ $account->is_active ? 'Active' : 'Inactive' }}</td><td><a href="{{ route('admin.payment-accounts.edit',$account) }}">Edit</a></td></tr>@endforeach</table>{{ $accounts->links() }}
@endsection
