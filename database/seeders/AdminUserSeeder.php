<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'name'  => 'Super Admin',
                'email' => 'ammrstesting@gmail.com',
                'password' => 'ammrstesting@1',
            ],
            [
                'name'  => 'Sachin Admin',
                'email' => 'sachin.khairnar@ammrs.co.in',
                'password' => 'sachin@123',
            ],
        ];

        foreach ($admins as $admin) {
            if (!User::where('email', $admin['email'])->exists()) {
                User::create([
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    'password' => Hash::make($admin['password']),
                    'role' => 'admin',
                    'status' => true,
                    'billing_address' => json_encode([]),
                    'shipping_address' => json_encode([]),
                ]);

                $this->command->info("✅ Admin user created: {$admin['email']} / {$admin['password']}");
            } else {
                $this->command->info("⚠️ Admin user already exists: {$admin['email']}, skipping creation.");
            }
        }
    }
}
