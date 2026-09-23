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
        $owner = Role::query()->firstOrCreate(
            ['slug' => 'owner'],
            ['name' => 'Owner', 'description' => 'Full store administration access.']
        );
        $user = User::query()->oldest('id')->first();

        if ($user) {
            $user->forceFill(['role_id' => $owner->id, 'is_active' => true])->save();

            return;
        }

        $password = app()->runningUnitTests()
            ? 'TestPassword!123'
            : $this->command?->secret('admin12345678');
        $confirmation = app()->runningUnitTests()
            ? $password
            : $this->command?->secret('admin12345678');

        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('The administrator password must contain at least 12 characters.');
        }

        if (! hash_equals($password, (string) $confirmation)) {
            throw new RuntimeException('The administrator password confirmation does not match.');
        }

        User::query()->create([
            'name' => 'Store Owner',
            'username' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make($password),
            'role_id' => $owner->id,
            'is_active' => true,
        ]);

        $this->command?->info('Initial administrator created. Change the profile details after signing in.');
        $this->command?->line('Username: admin');
    }
}
