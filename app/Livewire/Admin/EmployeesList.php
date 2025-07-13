<?php

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\Nationality;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class EmployeesList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';
    
    #[Url]
    public string $regionFilter = '';
    
    #[Url]
    public string $nationalityFilter = '';
    
    #[Url]
    public string $statusFilter = '';
    
    #[Url]
    public string $genderFilter = '';
    
    #[Url]
    public string $sortField = 'created_at';
    
    #[Url]
    public string $sortDirection = 'desc';
    
    public int $perPage = 15;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'regionFilter' => ['except' => ''],
        'nationalityFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'genderFilter' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRegionFilter()
    {
        $this->resetPage();
    }

    public function updatingNationalityFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingGenderFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->regionFilter = '';
        $this->nationalityFilter = '';
        $this->statusFilter = '';
        $this->genderFilter = '';
        $this->resetPage();
    }

    public function toggleStatus($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $employee->update([
            'is_active' => !$employee->is_active
        ]);

        session()->flash('message', 
            $employee->is_active 
                ? 'Employé activé avec succès.' 
                : 'Employé désactivé avec succès.'
        );
    }

    public function getRegionsProperty()
    {
        return Employee::distinct()->pluck('region')->filter()->sort();
    }

    public function getNationalitiesProperty()
    {
        return Nationality::orderBy('nom')->get();
    }

    public function render()
    {
        $employees = Employee::query()
            ->with(['nationality', 'activeContrat.employer', 'photo'])
            ->when($this->search, fn($query) => $query->search($this->search))
            ->when($this->regionFilter, fn($query) => $query->byRegion($this->regionFilter))
            ->when($this->nationalityFilter, fn($query) => $query->byNationality($this->nationalityFilter))
            ->when($this->statusFilter, function($query) {
                if ($this->statusFilter === 'active') {
                    $query->active();
                } elseif ($this->statusFilter === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($this->genderFilter, fn($query) => $query->where('genre', $this->genderFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        // Statistiques globales (non filtrées)
        $stats = [
            'total' => Employee::count(),
            'active' => Employee::where('is_active', true)->count(),
            'inactive' => Employee::where('is_active', false)->count(),
            'with_contract' => Employee::whereHas('activeContrat')->count(),
        ];

        return view('livewire.admin.employees-list', [
            'employees' => $employees,
            'regions' => $this->regions,
            'nationalities' => $this->nationalities,
            'stats' => $stats,
        ]);
    }
}
