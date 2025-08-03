<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Nationality;
use Carbon\Carbon;

class EmployeesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer des nationalités pour les employés
        $ethiopianNationality = Nationality::where('nom', 'Éthiopie')->first();
        $somalianNationality = Nationality::where('nom', 'Somalie')->first();
        $sudaneseNationality = Nationality::where('nom', 'Soudan')->first();
        $yemeniNationality = Nationality::where('nom', 'Yémen')->first();
        $eritreanNationality = Nationality::where('nom', 'Érythrée')->first();

        $employees = [
            [
                'prenom' => 'Maryam',
                'nom' => 'Osman',
                'genre' => 'Femme',
                'etat_civil' => 'Marié(e)',
                'date_naissance' => Carbon::create(1995, 3, 15),
                'nationality_id' => $ethiopianNationality->id,
                'date_arrivee' => Carbon::create(2020, 1, 10),
                'region' => 'Djibouti',
                'ville' => 'Djibouti',
                'quartier' => 'Héron',
                'adresse_complete' => 'Rue 12, Quartier Héron, Djibouti',
                'is_active' => true,
            ],
            [
                'prenom' => 'Hassan',
                'nom' => 'Ahmed',
                'genre' => 'Homme',
                'etat_civil' => 'Célibataire',
                'date_naissance' => Carbon::create(1992, 8, 22),
                'nationality_id' => $somalianNationality->id,
                'date_arrivee' => Carbon::create(2019, 5, 15),
                'region' => 'Djibouti',
                'ville' => 'Djibouti',
                'quartier' => 'Plateau du Serpent',
                'adresse_complete' => 'Avenue 5, Plateau du Serpent, Djibouti',
                'is_active' => true,
            ],
            [
                'prenom' => 'Amina',
                'nom' => 'Said',
                'genre' => 'Femme',
                'etat_civil' => 'Divorcé(e)',
                'date_naissance' => Carbon::create(1988, 12, 3),
                'nationality_id' => $sudaneseNationality->id,
                'date_arrivee' => Carbon::create(2018, 9, 20),
                'region' => 'Djibouti',
                'ville' => 'Balbala',
                'quartier' => 'Balbala',
                'adresse_complete' => 'Zone 3, Balbala, Djibouti',
                'is_active' => true,
            ],
            [
                'prenom' => 'Mohamed',
                'nom' => 'Ali',
                'genre' => 'Homme',
                'etat_civil' => 'Marié(e)',
                'date_naissance' => Carbon::create(1990, 6, 18),
                'nationality_id' => $yemeniNationality->id,
                'date_arrivee' => Carbon::create(2021, 3, 8),
                'region' => 'Ali Sabieh',
                'ville' => 'Ali Sabieh',
                'quartier' => 'Centre Ville',
                'adresse_complete' => 'Rue Principale, Ali Sabieh',
                'is_active' => true,
            ],
            [
                'prenom' => 'Fatuma',
                'nom' => 'Ibrahim',
                'genre' => 'Femme',
                'etat_civil' => 'Célibataire',
                'date_naissance' => Carbon::create(1997, 11, 25),
                'nationality_id' => $eritreanNationality->id,
                'date_arrivee' => Carbon::create(2022, 7, 12),
                'region' => 'Tadjourah',
                'ville' => 'Tadjourah',
                'quartier' => 'Port',
                'adresse_complete' => 'Quartier du Port, Tadjourah',
                'is_active' => true,
            ],
            [
                'prenom' => 'Abdullahi',
                'nom' => 'Hassan',
                'genre' => 'Homme',
                'etat_civil' => 'Marié(e)',
                'date_naissance' => Carbon::create(1985, 4, 7),
                'nationality_id' => $ethiopianNationality->id,
                'date_arrivee' => Carbon::create(2017, 11, 30),
                'region' => 'Djibouti',
                'ville' => 'Djibouti',
                'quartier' => 'Arhiba',
                'adresse_complete' => 'Cité Arhiba, Djibouti',
                'is_active' => true,
            ],
            [
                'prenom' => 'Sahra',
                'nom' => 'Mohamed',
                'genre' => 'Femme',
                'etat_civil' => 'Veuf(ve)',
                'date_naissance' => Carbon::create(1983, 9, 14),
                'nationality_id' => $somalianNationality->id,
                'date_arrivee' => Carbon::create(2016, 2, 18),
                'region' => 'Dikhil',
                'ville' => 'Dikhil',
                'quartier' => 'Centre',
                'adresse_complete' => 'Centre Ville, Dikhil',
                'is_active' => true,
            ],
            [
                'prenom' => 'Omar',
                'nom' => 'Yusuf',
                'genre' => 'Homme',
                'etat_civil' => 'Célibataire',
                'date_naissance' => Carbon::create(1994, 1, 9),
                'nationality_id' => $sudaneseNationality->id,
                'date_arrivee' => Carbon::create(2023, 1, 5),
                'region' => 'Obock',
                'ville' => 'Obock',
                'quartier' => 'Coastal',
                'adresse_complete' => 'Zone Côtière, Obock',
                'is_active' => true,
            ],
            [
                'prenom' => 'Aisha',
                'nom' => 'Abdi',
                'genre' => 'Femme',
                'etat_civil' => 'Marié(e)',
                'date_naissance' => Carbon::create(1991, 7, 31),
                'nationality_id' => $yemeniNationality->id,
                'date_arrivee' => Carbon::create(2020, 10, 22),
                'region' => 'Arta',
                'ville' => 'Arta',
                'quartier' => 'Résidentiel',
                'adresse_complete' => 'Zone Résidentielle, Arta',
                'is_active' => true,
            ],
            [
                'prenom' => 'Ibrahim',
                'nom' => 'Omar',
                'genre' => 'Homme',
                'etat_civil' => 'Divorcé(e)',
                'date_naissance' => Carbon::create(1987, 10, 12),
                'nationality_id' => $eritreanNationality->id,
                'date_arrivee' => Carbon::create(2019, 8, 15),
                'region' => 'Djibouti',
                'ville' => 'Djibouti',
                'quartier' => 'Ambouli',
                'adresse_complete' => 'Quartier Ambouli, Djibouti',
                'is_active' => true,
            ]
        ];

        foreach ($employees as $employeeData) {
            Employee::create($employeeData);
        }
    }
}