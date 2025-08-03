<?php

namespace App\Livewire\Admin;

use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use App\Services\AttestationService;

class EmployeeDetails extends Component
{
    use WithPagination;

    public Employee $employee;
    public string $activeTab = 'informations';
    public string $contractFilter = 'all'; // all, active, inactive
    public int $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    public function mount(Employee $employee)
    {
        $this->employee = $employee->load([
            'nationality',
            'contrats.employer',
            'activeContrat.employer',
            'documents',
            'photo',
            'identityDocument',
            'passport',
        ]);
    }

    public function getDocumentStatusProperty()
    {
        $attestationService = new AttestationService();
        
        return [
            'has_passport' => $this->employee->hasPassport(),
            'has_identity_document' => $this->employee->hasIdentityDocument(),
            'document_status' => $this->employee->getDocumentStatus(),
            'needs_attestation' => $this->employee->needsIdentityAttestation(),
            'has_valid_attestation' => $attestationService->hasValidAttestation($this->employee),
            'attestation_url' => $attestationService->getValidAttestationUrl($this->employee),
        ];
    }

    public function updatingContractFilter()
    {
        $this->resetPage();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function toggleStatus()
    {
        $this->employee->update([
            'is_active' => !$this->employee->is_active
        ]);
        
        session()->flash('message', 
            $this->employee->is_active 
                ? 'Employé activé avec succès.' 
                : 'Employé désactivé avec succès.'
        );
    }

    public function exportEmployeeData()
    {
        return response()->streamDownload(function () {
            echo $this->generateEmployeeReport();
        }, "employe_{$this->employee->id}_details.txt");
    }

    private function generateEmployeeReport(): string
    {
        $report = "=== DÉTAILS EMPLOYÉ ===\n\n";
        $report .= "Nom complet: {$this->employee->nom_complet}\n";
        $report .= "Genre: {$this->employee->genre}\n";
        $report .= "État civil: {$this->employee->etat_civil}\n";
        $report .= "Date de naissance: {$this->employee->date_naissance->format('d/m/Y')}\n";
        $report .= "Âge: {$this->employee->age} ans\n";
        $report .= "Nationalité: {$this->employee->nationality->name}\n";
        $report .= "Date d'arrivée: {$this->employee->date_arrivee->format('d/m/Y')}\n";
        $report .= "Durée à Djibouti: {$this->employee->duree_en_djibouti} ans\n";
        $report .= "Région: {$this->employee->region}\n";
        $report .= "Ville: {$this->employee->ville}\n";
        $report .= "Quartier: {$this->employee->quartier}\n";
        $report .= "Adresse complète: {$this->employee->adresse_complete}\n";
        $report .= "Statut: " . ($this->employee->is_active ? 'Actif' : 'Inactif') . "\n\n";
        
        if ($this->employee->activeContrat) {
            $report .= "=== CONTRAT ACTUEL ===\n";
            $report .= "Employeur: {$this->employee->activeContrat->employer->nom_complet}\n";
            $report .= "Type d'emploi: {$this->employee->activeContrat->type_emploi}\n";
            $report .= "Salaire mensuel: {$this->employee->activeContrat->salaire_mensuel} DJF\n";
            $report .= "Date de début: {$this->employee->activeContrat->date_debut->format('d/m/Y')}\n";
        }
        
        return $report;
    }

    public function render()
    {
        return view('livewire.admin.employee-details');
    }
}
