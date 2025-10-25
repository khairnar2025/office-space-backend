<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Check if admin already exists
        if (!User::where('email', 'ammrstesting@gmail.com')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'ammrstesting@gmail.com',
                'password' => Hash::make('ammrstesting@1'),
                'role' => 'admin',
                'status' => true,
                'billing_address' => json_encode([]),
                'shipping_address' => json_encode([]),
            ]);

            $this->command->info('✅ Admin user created: ammrstesting@gmail.com / ammrstesting@1');
        } else {
            $this->command->info('⚠️ Admin user already exists, skipping creation.');
        }
    }
}
