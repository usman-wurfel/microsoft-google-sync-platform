<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'view-sync-dashboard',
            'sync-microsoft',
            'sync-google',
            'export-data',
            'manage-users',
            'manage-groups',
            'view-sync-logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        
        // Assign all permissions to admin role
        $adminRole->syncPermissions($permissions);

        // Create admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@domain.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin1'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role to user
        $adminUser->assignRole('admin');

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@domain.com');
        $this->command->info('Password: admin1');
    }
}
