@extends('layouts.admin')
@section('title', 'Staff & Roles')
@section('content')
<section class="panel"><h2>Roles</h2><table class="table table-sm"><tr><th>Role</th><th>Permissions</th></tr>@foreach($roles as $role)<tr><td>{{ $role->name }}</td><td>{{ $role->permissions_count }}</td></tr>@endforeach</table></section><section class="panel"><h2>Staff</h2><table class="table table-sm"><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>@foreach($staff as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role?->name }}</td><td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach</table></section>
@endsection
