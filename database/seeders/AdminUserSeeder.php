<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');
        if (app()->isProduction() && blank($password)) {
            throw new RuntimeException('Set ADMIN_PASSWORD before running db:seed in production.');
        }

        $owner = Role::query()->where('slug', 'owner')->firstOrFail();
        $user = User::query()->oldest('id')->first() ?? new User();
        $user->forceFill([
            'name' => env('ADMIN_NAME', 'Store Owner'),
            'username' => env('ADMIN_USERNAME', 'admin'),
            'email' => env('ADMIN_EMAIL', 'admin@admin.com'),
            'password' => Hash::make($password ?: 'admin12345678'),
            'role_id' => $owner->id,
            'is_active' => true,
        ])->save();
    }
}
