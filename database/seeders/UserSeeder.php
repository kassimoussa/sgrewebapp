<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::create([
            'username' => 'superadmin',
            'email' => 'superadmin@test.com',
            'password' => 'password123',
            'nom' => 'Super',
            'prenom' => 'Admin',
            'role' => 'super_admin',
            'is_active' => true,
           // 'email_verified_at' => now(),
        ]);

        // Admin principal
        User::create([
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'nom' => 'Administrateur',
            'prenom' => 'Principal',
            'role' => 'admin',
            'is_active' => true,
          //  'email_verified_at' => now(),
        ]);

        // Admin de test
        User::create([
            'username' => 'test',
            'email' => 'test@test.com',
            'password' => 'password123',
            'nom' => 'Test',
            'prenom' => 'Admin',
            'role' => 'admin',
            'is_active' => true,
           // 'email_verified_at' => now(),
        ]);

        // Superviseur (pour tester les rôles)
        User::create([
            'username' => 'superviseur',
            'email' => 'superviseur@test.com',
            'password' => 'password123',
            'nom' => 'Superviseur',
            'prenom' => 'Test',
            'role' => 'superviseur',
            'is_active' => true,
            //'email_verified_at' => now(),
        ]);

        // Agent (pour tester les restrictions)
        User::create([
            'username' => 'agent',
            'email' => 'agent@test.com',
            'password' => 'password123',
            'nom' => 'Agent',
            'prenom' => 'Test',
            'role' => 'agent',
            'is_active' => false, // Désactivé pour tester
            //'email_verified_at' => now(),
        ]);
    }
}