<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Contrat;
use App\Models\Employee;
use App\Models\Employer;
use Carbon\Carbon;

class ContratsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::all();
        $employers = Employer::all();
        
        $typesEmploi = ['Ménage', 'Gardien', 'Jardinier', 'Coulis', 'Vendeur'];
        
        // Créer des contrats pour chaque employé
        foreach ($employees as $index => $employee) {
            // Distribuer les employeurs de manière équilibrée
            $employer = $employers[$index % $employers->count()];
            
            // Définir le type d'emploi selon le genre (tendance réaliste)
            if ($employee->genre === 'Femme') {
                $typeEmploi = fake()->randomElement(['Ménage', 'Vendeur']);
            } else {
                $typeEmploi = fake()->randomElement(['Gardien', 'Jardinier', 'Coulis', 'Vendeur']);
            }
            
            // Salaire selon le type d'emploi
            $salaire = match($typeEmploi) {
                'Ménage' => fake()->numberBetween(25000, 40000),
                'Gardien' => fake()->numberBetween(30000, 45000),
                'Jardinier' => fake()->numberBetween(28000, 42000),
                'Coulis' => fake()->numberBetween(35000, 50000),
                'Vendeur' => fake()->numberBetween(20000, 35000),
                default => 30000
            };
            
            // Date de début du contrat (entre 1 mois et 2 ans)
            $dateDebut = Carbon::now()->subDays(fake()->numberBetween(30, 730));
            
            // 80% des contrats sont actifs, 20% terminés
            $estActif = fake()->boolean(80);
            $dateFin = $estActif ? null : $dateDebut->copy()->addMonths(fake()->numberBetween(6, 18));
            
            Contrat::create([
                'employer_id' => $employer->id,
                'employee_id' => $employee->id,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'type_emploi' => $typeEmploi,
                'salaire_mensuel' => $salaire,
                'est_actif' => $estActif,
                'notes' => fake()->optional(0.3)->text(100), // 30% de chance d'avoir des notes
            ]);
            
            // Quelques employés peuvent avoir eu des contrats précédents
            if (fake()->boolean(25) && $estActif) { // 25% de chance d'avoir un ancien contrat
                $ancienEmployeur = $employers->where('id', '!=', $employer->id)->random();
                $ancienneDateFin = $dateDebut->copy()->subDays(fake()->numberBetween(10, 180));
                $ancienneDateDebut = $ancienneDateFin->copy()->subMonths(fake()->numberBetween(6, 24));
                
                Contrat::create([
                    'employer_id' => $ancienEmployeur->id,
                    'employee_id' => $employee->id,
                    'date_debut' => $ancienneDateDebut,
                    'date_fin' => $ancienneDateFin,
                    'type_emploi' => fake()->randomElement($typesEmploi),
                    'salaire_mensuel' => fake()->numberBetween(18000, 35000),
                    'est_actif' => false,
                    'notes' => 'Contrat précédent terminé',
                ]);
            }
        }
    }
}