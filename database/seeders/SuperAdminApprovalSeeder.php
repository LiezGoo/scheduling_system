<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminApprovalSeeder extends Seeder
{
    public function run(): void
    {
        // Approve the super admin account
        $admin = User::where('email', 'admin@sorsu.edu.ph')->first();
        if ($admin && !$admin->is_approved) {
            $admin->update([
                'is_approved' => true,
                'approved_at' => now(),
            ]);
            
            $this->command->info('✓ Super Admin account approved and ready to use');
        } elseif ($admin) {
            $this->command->info('✓ Super Admin account already approved');
        }
    }
}
