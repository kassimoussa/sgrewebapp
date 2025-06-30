<?php

namespace App\Livewire\Admin;

use App\Models\Employer;
use App\Models\Contrat;
use App\Models\Employee;
use App\Models\ConfirmationMensuelle;
use Livewire\Component;
use Livewire\WithPagination;

class EmployerDetails extends Component
{
    use WithPagination;

    public Employer $employer;
    public string $activeTab = 'informations';
    public string $contractFilter = 'all'; // all, active, inactive
    public string $searchEmployee = '';
    public int $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    public function mount(Employer $employer)
    {
        $this->employer = $employer->load([
            'contrats.employee.nationality',
            'contrats.confirmations',
            'documents'
        ]);
    }

    public function updatingSearchEmployee()
    {
        $this->resetPage();
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

    public function toggleEmployerStatus()
    {
        $this->employer->update([
            'is_active' => !$this->employer->is_active
        ]);

        $message = $this->employer->is_active 
            ? 'Employeur activé avec succès' 
            : 'Employeur désactivé avec succès';

        session()->flash('message', $message);
    }

    public function terminateContract($contractId)
    {
        $contract = Contrat::find($contractId);
        if ($contract && $contract->employer_id === $this->employer->id) {
            $contract->update([
                'est_actif' => false,
                'date_fin' => now()
            ]);

            // Recharger les relations
            $this->employer->load([
                'contrats.employee.nationality',
                'contrats.confirmations',
                'documents'
            ]);

            session()->flash('message', 'Contrat terminé avec succès');
        }
    }

    public function reactivateContract($contractId)
    {
        $contract = Contrat::find($contractId);
        if ($contract && $contract->employer_id === $this->employer->id) {
            // Désactiver les autres contrats de cet employé
            Contrat::where('employee_id', $contract->employee_id)
                   ->where('est_actif', true)
                   ->update(['est_actif' => false, 'date_fin' => now()]);

            // Réactiver ce contrat
            $contract->update([
                'est_actif' => true,
                'date_fin' => null
            ]);

            $this->employer->refresh();
            session()->flash('message', 'Contrat réactivé avec succès');
        }
    }

    public function getFilteredContractsProperty()
    {
        $query = $this->employer->contrats()->with(['employee.nationality']);

        // Filtre par statut
        if ($this->contractFilter === 'active') {
            $query->where('est_actif', true);
        } elseif ($this->contractFilter === 'inactive') {
            $query->where('est_actif', false);
        }

        // Recherche par nom d'employé
        if ($this->searchEmployee) {
            $query->whereHas('employee', function($q) {
                $q->where('nom', 'like', '%' . $this->searchEmployee . '%')
                  ->orWhere('prenom', 'like', '%' . $this->searchEmployee . '%');
            });
        }

        return $query->latest()->paginate($this->perPage);
    }

    public function getStatsProperty()
    {
        return [
            'total_contracts' => $this->employer->contrats->count(),
            'active_contracts' => $this->employer->contrats->where('est_actif', true)->count(),
            'total_employees' => $this->employer->contrats->unique('employee_id')->count(),
            'total_confirmations' => $this->employer->contrats->sum(function($contrat) {
                return $contrat->confirmations->count();
            }),
            'monthly_salary_total' => $this->employer->contrats->where('est_actif', true)->sum('salaire_mensuel'),
            'documents_count' => $this->employer->documents->count(),
        ];
    }

    public function getRecentConfirmationsProperty()
    {
        return ConfirmationMensuelle::whereHas('contrat', function($query) {
            $query->where('employer_id', $this->employer->id);
        })
        ->with(['contrat.employee'])
        ->latest('created_at')
        ->limit(5)
        ->get();
    }

    public function refreshData()
    {
        $this->employer->refresh();
        $this->employer->load([
            'contrats.employee.nationality',
            'contrats.confirmations',
            'documents'
        ]);
    }

    public function exportContracts($format = 'excel')
    {
        // Logique d'export à implémenter selon les besoins
        session()->flash('message', 'Export en cours de préparation...');
    }

    public function sendNotification($contractId, $type = 'confirmation_reminder')
    {
        // Logique d'envoi de notification à implémenter
        session()->flash('message', 'Notification envoyée avec succès');
    }

    public function getContractsByStatusProperty()
    {
        return [
            'active' => $this->employer->contrats->where('est_actif', true),
            'inactive' => $this->employer->contrats->where('est_actif', false),
            'total' => $this->employer->contrats
        ];
    }

    public function getEmployeeNationalitiesProperty()
    {
        return $this->employer->contrats
            ->pluck('employee.nationality.nom')
            ->filter()
            ->unique()
            ->values();
    }

    public function getSalaryDistributionProperty()
    {
        $salaries = $this->employer->contrats->where('est_actif', true)->pluck('salaire_mensuel');
        
        return [
            'min' => $salaries->min() ?? 0,
            'max' => $salaries->max() ?? 0,
            'avg' => $salaries->avg() ?? 0,
            'total' => $salaries->sum() ?? 0
        ];
    }

    protected $listeners = ['refreshData'];

    public function render()
    {
        return view('livewire.admin.employer-details', [
            'filteredContracts' => $this->filteredContracts,
            'stats' => $this->stats,
            'recentConfirmations' => $this->recentConfirmations,
            'contractsByStatus' => $this->contractsByStatus,
            'employeeNationalities' => $this->employeeNationalities,
            'salaryDistribution' => $this->salaryDistribution,
        ]);
    }
}