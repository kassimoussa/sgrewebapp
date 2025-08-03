<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            NationalitesSeeder::class,
            UserSeeder::class,
            EmployersSeeder::class,
            EmployeesSeeder::class,
            ContratsSeeder::class,
            DocumentsSeeder::class,
        ]);
    }
}
