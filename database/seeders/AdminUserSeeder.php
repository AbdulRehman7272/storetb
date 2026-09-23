<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Edit these values, then run: php artisan db:seed --class=AdminUserSeeder
        $name = 'Store Owner';
        $username = 'admin';
        $email = 'admin@admin.com';
        $password = 'admin12345678';

        $owner = Role::query()->firstOrCreate(
            ['slug' => 'owner'],
            ['name' => 'Owner', 'description' => 'Full store administration access.']
        );

        $user = User::query()->oldest('id')->first() ?? new User();
        $user->forceFill([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $owner->id,
            'is_active' => true,
        ])->save();

        $this->command?->info('Administrator credentials saved.');
    }
}
