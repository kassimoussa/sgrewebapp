<div>
    {{-- Header avec titre et statistiques --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Employés domestiques</h1>
            <p class="text-muted mb-0">Gestion des employés déclarés sur le territoire djiboutien</p>
        </div>
        <div class="d-flex gap-2">
            <div class="badge badge-primary">
                {{ $employees->total() }} employés
            </div>
        </div>
    </div>

    {{-- Messages flash --}}
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filtres et recherche --}}
    <div class="filter-card mb-3">
        <h6 class="filter-title">Filtres et recherche</h6>
        <div class="row g-3">
            {{-- Recherche --}}
            <div class="col-md-4">
                <label class="form-label">Rechercher</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           class="form-control" 
                           wire:model.live.debounce.300ms="search"
                           placeholder="Nom, prénom, nationalité...">
                </div>
            </div>

            {{-- Filtre région --}}
            <div class="col-md-3">
                <label class="form-label">Région</label>
                <select class="form-select" wire:model.live="regionFilter">
                    <option value="">Toutes les régions</option>
                    @foreach($regions as $region)
                        <option value="{{ $region }}">{{ $region }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre nationalité --}}
            <div class="col-md-3">
                <label class="form-label">Nationalité</label>
                <select class="form-select" wire:model.live="nationalityFilter">
                    <option value="">Toutes nationalités</option>
                    @foreach($nationalities as $nationality)
                        <option value="{{ $nationality->id }}">{{ $nationality->nom }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre statut --}}
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select class="form-select" wire:model.live="statusFilter">
                    <option value="">Tous</option>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>
            </div>

            {{-- Filtre passeport --}}
            <div class="col-md-2">
                <label class="form-label">Passeport</label>
                <select class="form-select" wire:model.live="passportFilter">
                    <option value="">Tous</option>
                    <option value="with_passport">Avec passeport</option>
                    <option value="without_passport">Sans passeport</option>
                    <option value="needs_attestation">Besoin attestation</option>
                </select>
            </div>

            {{-- Filtre genre --}}
            <div class="col-md-2">
                <label class="form-label">Genre</label>
                <select class="form-select" wire:model.live="genderFilter">
                    <option value="">Tous</option>
                    <option value="Homme">Homme</option>
                    <option value="Femme">Femme</option>
                </select>
            </div>

            {{-- Nombre par page --}}
            <div class="col-md-2">
                <label class="form-label">Par page</label>
                <select class="form-select" wire:model.live="perPage">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            {{-- Bouton reset --}}
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="button" 
                        class="btn btn-outline-secondary w-100" 
                        wire:click="resetFilters"
                        title="Effacer les filtres">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Tableau des employés --}}
    <div class="content-card">
        @if($employees->count() > 0)
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th wire:click="sortBy('nom')" style="cursor: pointer;" class="text-nowrap">
                                Nom complet
                                @if($sortField === 'nom')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </th>
                            <th>Nationalité & Âge</th>
                            <th wire:click="sortBy('region')" style="cursor: pointer;" class="text-nowrap">
                                Localisation
                                @if($sortField === 'region')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </th>
                            <th class="text-center">Employeur actuel</th>
                            <th wire:click="sortBy('created_at')" style="cursor: pointer;" class="text-nowrap">
                                Inscription
                                @if($sortField === 'created_at')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                            <tr class="{{ !$employee->is_active ? 'opacity-50' : '' }}">
                                {{-- Nom complet --}}
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($employee->photo_url && $employee->photo_url !== asset('images/default-employee.png'))
                                            <img src="{{ $employee->photo_url }}" 
                                                 alt="Photo {{ $employee->nom_complet }}" 
                                                 class="avatar-photo me-3">
                                        @else
                                            <div class="avatar-circle text-black me-3">
                                                {{ strtoupper(substr($employee->prenom, 0, 1) . substr($employee->nom, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $employee->nom_complet }}</div>
                                            <small class="text-muted">
                                                <i class="fas fa-venus-mars me-1"></i>{{ $employee->genre }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                {{-- Nationalité & Âge --}}
                                <td>
                                    <div>
                                        <div class="fw-semibold">{{ $employee->nationality->nom }}</div>
                                        <small class="text-muted">{{ $employee->age }} ans</small>
                                        <div class="mt-1">
                                            @if($employee->hasPassport())
                                                <span class="badge bg-success" title="Employé avec passeport">
                                                    <i class="fas fa-passport me-1"></i>Passeport
                                                </span>
                                            @else
                                                <span class="badge bg-warning" title="Employé sans passeport">
                                                    No Passport
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Localisation --}}
                                <td>
                                    <div>
                                        <div class="fw-semibold">{{ $employee->region }}</div>
                                        <small class="text-muted">{{ $employee->ville }}, {{ $employee->quartier }}</small>
                                    </div>
                                </td>

                                {{-- Employeur actuel --}}
                                <td class="text-center">
                                    <div>
                                        @if($employee->activeContrat)
                                            <div class="fw-semibold">{{ $employee->activeContrat->employer->nom_complet }}</div>
                                            <small class="text-muted">{{ $employee->activeContrat->type_emploi }}</small>
                                        @else
                                            <span class="badge">Aucun contrat</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Inscription --}}
                                <td>
                                    <div class="small">
                                        <div>{{ $employee->created_at->format('d/m/Y') }}</div>
                                        <div class="text-muted">{{ $employee->created_at->format('H:i') }}</div>
                                    </div>
                                </td>

                                {{-- Statut --}}
                                <td class="text-center">
                                    @if($employee->is_active)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check me-1"></i>Actif
                                        </span>
                                    @else
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times me-1"></i>Inactif
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        {{-- Voir détails --}}
                                        <a href="{{ route('admin.employees.show', $employee->id) }}" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        {{-- Toggle statut --}}
                                        <button type="button" 
                                                class="btn btn-sm {{ $employee->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                wire:click="toggleStatus({{ $employee->id }})"
                                                wire:confirm="Êtes-vous sûr de vouloir {{ $employee->is_active ? 'désactiver' : 'activer' }} cet employé ?"
                                                title="{{ $employee->is_active ? 'Désactiver' : 'Activer' }}">
                                            <i class="fas fa-{{ $employee->is_active ? 'ban' : 'check' }}"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                <div class="text-muted small">
                    Affichage de {{ $employees->firstItem() }} à {{ $employees->lastItem() }} 
                    sur {{ $employees->total() }} résultats
                </div>
                <div>
                    {{ $employees->links() }}
                </div>
            </div>
        @else
            {{-- État vide --}}
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-users fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">Aucun employé trouvé</h5>
                <p class="text-muted">
                    @if($search || $regionFilter || $nationalityFilter || $statusFilter || $genderFilter || $passportFilter)
                        Aucun employé ne correspond à vos critères de recherche.
                        <br>
                        <button type="button" 
                                class="btn btn-link p-0" 
                                wire:click="resetFilters">
                            Effacer les filtres
                        </button>
                    @else
                        Les employés déclarés apparaîtront ici.
                    @endif
                </p>
            </div>
        @endif
    </div>

    {{-- Statistiques rapides --}}
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['total'] }}</h3>
                    <p>Total employés</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['active'] }}</h3>
                    <p>Actifs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['inactive'] }}</h3>
                    <p>Inactifs</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['with_contract'] }}</h3>
                    <p>Avec contrat</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading indicators ciblés --}}
    {{-- Spinner pour la recherche --}}
    <div wire:loading wire:target="search" class="position-fixed top-50 start-50 translate-middle" style="z-index: 1000;">
        <div class="bg-white rounded p-3 shadow">
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                <span>Recherche...</span>
            </div>
        </div>
    </div>

    {{-- Opacity pour les filtres --}}
    <div wire:loading.class="opacity-50" wire:target="regionFilter,nationalityFilter,statusFilter,genderFilter,passportFilter,perPage">
        <!-- Le contenu sera automatiquement rendu opaque -->
    </div>

    {{-- Spinner pour le tri --}}
    <div wire:loading wire:target="sortBy" class="position-fixed top-50 start-50 translate-middle" style="z-index: 1000;">
        <div class="bg-white rounded p-3 shadow">
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                <span>Tri en cours...</span>
            </div>
        </div>
    </div>

    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
        }
        
        .avatar-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }
        
        .data-table th[wire\:click] {
            user-select: none;
            cursor: pointer;
        }
        
        .data-table th[wire\:click]:hover {
            background-color: rgba(var(--primary-color-rgb, 37, 99, 235), 0.1);
        }

        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
        }
        
        .stat-content h3 {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #1f2937;
        }
        
        .stat-content p {
            color: #6b7280;
            margin-bottom: 0;
            font-size: 0.875rem;
        }
    </style>
</div>