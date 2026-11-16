<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RegularUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a regular user role if it doesn't exist
        $userRole = Role::firstOrCreate(['name' => 'user']);
        
        // Assign limited permissions to user role
        $userRole->syncPermissions([
            'view-sync-dashboard',
            'sync-microsoft',
            'sync-google',
            'export-data'
        ]);

        // Create regular user
        $regularUser = User::firstOrCreate(
            ['email' => 'user@domain.com'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('user123'),
                'email_verified_at' => now(),
            ]
        );

        // Assign user role
        $regularUser->assignRole('user');

        $this->command->info('Regular user created successfully!');
        $this->command->info('Email: user@domain.com');
        $this->command->info('Password: user123');
    }
}
