<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $owner = Role::query()->where('slug', 'owner')->firstOrFail();
        $user = User::query()->oldest('id')->first();

        if ($user) {
            $user->forceFill(['role_id' => $owner->id, 'is_active' => true])->save();

            return;
        }

        $password = Str::password(20);
        User::query()->create([
            'name' => 'Store Owner',
            'username' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make("admin12345678"),
            'role_id' => $owner->id,
            'is_active' => true,
        ]);

        $this->command?->warn('Initial administrator created. Change these details after signing in.');
        $this->command?->line('Username: admin');
        $this->command?->line('One-time password: '.$password);
    }
}
