<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->withCount('permissions')->orderBy('name')->get(),
            'staff' => User::query()->with('role')->orderBy('name')->get(),
        ]);
    }
}
