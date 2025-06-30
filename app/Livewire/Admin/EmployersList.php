<?php

namespace App\Livewire\Admin;

use App\Models\Employer;
use Livewire\Component;
use Livewire\WithPagination;

class EmployersList extends Component
{
    use WithPagination;

    public $search = '';
    public $region = '';
    public $genre = '';
    public $perPage = 10;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRegion()
    {
        $this->resetPage();
    }

    public function updatingGenre()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function toggleStatus($employerId)
    {
        $employer = Employer::find($employerId);
        if ($employer) {
            $employer->update(['is_active' => !$employer->is_active]);
            
            session()->flash('message', 
                $employer->is_active 
                    ? 'Employeur activé avec succès' 
                    : 'Employeur désactivé avec succès'
            );
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->region = '';
        $this->genre = '';
        $this->resetPage();
    }

    public function getEmployersProperty()
    {
        $query = Employer::query()
            ->withCount('contrats')
            ->withCount(['contrats as active_contracts_count' => function($query) {
                $query->where('est_actif', true);
            }]);

        // Recherche
        if ($this->search) {
            $query->where(function($q) {
                $q->where('prenom', 'like', '%' . $this->search . '%')
                  ->orWhere('nom', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('telephone', 'like', '%' . $this->search . '%');
            });
        }

        // Filtres
        if ($this->region) {
            $query->where('region', $this->region);
        }

        if ($this->genre) {
            $query->where('genre', $this->genre);
        }

        // Tri
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    public function getRegionsProperty()
    {
        return [
            'Djibouti',
            'Ali Sabieh',
            'Dikhil',
            'Tadjourah',
            'Obock',
            'Arta'
        ];
    }

    public function getGenresProperty()
    {
        return ['Homme', 'Femme'];
    }

    public function render()
    {
        return view('livewire.admin.employers-list', [
            'employers' => $this->employers,
            'regions' => $this->regions,
            'genres' => $this->genres,
        ]);
    }
}