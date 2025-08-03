<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employer;
use Illuminate\Support\Facades\Hash;

class EmployersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employers = [
            [
                'prenom' => 'Ahmed',
                'nom' => 'Hassan',
                'genre' => 'Homme',
                'telephone' => '+253 77 12 34 56',
                'region' => 'Djibouti',
                'ville' => 'Djibouti',
                'quartier' => 'Héron',
                'email' => 'ahmed.hassan@email.dj',
                'mot_de_passe_hash' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
            [
                'prenom' => 'Fatima',
                'nom' => 'Mohamed',
                'genre' => 'Femme',
                'telephone' => '+253 77 98 76 54',
                'region' => 'Djibouti',
                'ville' => 'Djibouti',
                'quartier' => 'Plateau du Serpent',
                'email' => 'fatima.mohamed@email.dj',
                'mot_de_passe_hash' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
            [
                'prenom' => 'Omar',
                'nom' => 'Ali',
                'genre' => 'Homme',
                'telephone' => '+253 77 55 44 33',
                'region' => 'Ali Sabieh',
                'ville' => 'Ali Sabieh',
                'quartier' => 'Centre Ville',
                'email' => 'omar.ali@email.dj',
                'mot_de_passe_hash' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
            [
                'prenom' => 'Khadija',
                'nom' => 'Abdillahi',
                'genre' => 'Femme',
                'telephone' => '+253 77 22 11 00',
                'region' => 'Djibouti',
                'ville' => 'Balbala',
                'quartier' => 'Balbala',
                'email' => 'khadija.abdillahi@email.dj',
                'mot_de_passe_hash' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
            [
                'prenom' => 'Said',
                'nom' => 'Ibrahim',
                'genre' => 'Homme',
                'telephone' => '+253 77 88 99 00',
                'region' => 'Tadjourah',
                'ville' => 'Tadjourah',
                'quartier' => 'Port',
                'email' => 'said.ibrahim@email.dj',
                'mot_de_passe_hash' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        ];

        foreach ($employers as $employerData) {
            Employer::create($employerData);
        }
    }
}