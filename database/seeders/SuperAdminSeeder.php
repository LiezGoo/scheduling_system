<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SuperAdminSeeder
 * 
 * Creates the initial super admin account for production.
 * Run this after migrate:fresh to set up the first admin.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@sorsu.edu.ph'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('SorSU@AdminPass123!'),
            ]
        );

        $this->command->info("✓ Super Admin account created/verified:");
        $this->command->line("  Email: {$admin->email}");
        $this->command->line("  Name: {$admin->first_name} {$admin->last_name}");
        $this->command->line("  Status: " . ($admin->is_active ? 'Active' : 'Inactive'));
        $this->command->warn("\n⚠ IMPORTANT: Change the default password immediately after first login!");
    }
}
