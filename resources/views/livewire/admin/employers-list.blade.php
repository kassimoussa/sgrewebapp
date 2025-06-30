<div>
    {{-- Header avec titre et statistiques --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Employeurs inscrits</h1>
            <p class="text-muted mb-0">Gestion des employeurs enregistrés via l'application mobile</p>
        </div>
        <div class="d-flex gap-2">
            <div class="badge badge-primary">
                {{ $employers->total() }} employeurs
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
                           placeholder="Nom, prénom, email, téléphone...">
                </div>
            </div>

            {{-- Filtre région --}}
            <div class="col-md-3">
                <label class="form-label">Région</label>
                <select class="form-select" wire:model.live="region">
                    <option value="">Toutes les régions</option>
                    @foreach($regions as $regionOption)
                        <option value="{{ $regionOption }}">{{ $regionOption }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filtre genre --}}
            <div class="col-md-2">
                <label class="form-label">Genre</label>
                <select class="form-select" wire:model.live="genre">
                    <option value="">Tous</option>
                    @foreach($genres as $genreOption)
                        <option value="{{ $genreOption }}">{{ $genreOption }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Nombre par page --}}
            <div class="col-md-2">
                <label class="form-label">Par page</label>
                <select class="form-select" wire:model.live="perPage">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            {{-- Bouton reset --}}
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="button" 
                        class="btn btn-outline-secondary w-100" 
                        wire:click="clearFilters"
                        title="Effacer les filtres">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Tableau des employeurs --}}
    <div class="content-card">
        @if($employers->count() > 0)
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th wire:click="sortBy('prenom')" style="cursor: pointer;" class="text-nowrap">
                                Nom complet
                                @if($sortBy === 'prenom')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </th>
                            <th>Contact</th>
                            <th wire:click="sortBy('region')" style="cursor: pointer;" class="text-nowrap">
                                Localisation
                                @if($sortBy === 'region')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort text-muted"></i>
                                @endif
                            </th>
                            <th class="text-center">Employés</th>
                            <th wire:click="sortBy('created_at')" style="cursor: pointer;" class="text-nowrap">
                                Inscription
                                @if($sortBy === 'created_at')
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
                        @foreach($employers as $employer)
                            <tr class="{{ !$employer->is_active ? 'opacity-50' : '' }}">
                                {{-- Nom complet --}}
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle text-black me-3">
                                            {{ strtoupper(substr($employer->prenom, 0, 1) . substr($employer->nom, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $employer->prenom }} {{ $employer->nom }}</div>
                                            <small class="text-muted">
                                                <i class="fas fa-venus-mars me-1"></i>{{ $employer->genre }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                {{-- Contact --}}
                                <td>
                                    <div>
                                        <div class="small">
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            <a href="mailto:{{ $employer->email }}" class="text-decoration-none">
                                                {{ $employer->email }}
                                            </a>
                                        </div>
                                        <div class="small mt-1">
                                            <i class="fas fa-phone me-1 text-muted"></i>
                                            <a href="tel:{{ $employer->telephone }}" class="text-decoration-none">
                                                {{ $employer->telephone }}
                                            </a>
                                        </div>
                                    </div>
                                </td>

                                {{-- Localisation --}}
                                <td>
                                    <div>
                                        <div class="fw-semibold">{{ $employer->region }}</div>
                                        <small class="text-muted">{{ $employer->ville }}, {{ $employer->quartier }}</small>
                                    </div>
                                </td>

                                {{-- Employés --}}
                                <td class="text-center">
                                    <div>
                                        @if($employer->active_contracts_count > 0)
                                            <span class="badge badge-success">
                                                {{ $employer->active_contracts_count }} actif{{ $employer->active_contracts_count > 1 ? 's' : '' }}
                                            </span>
                                        @else
                                            <span class="badge">Aucun</span>
                                        @endif
                                    </div>
                                    @if($employer->contrats_count > $employer->active_contracts_count)
                                        <small class="text-muted">
                                            {{ $employer->contrats_count - $employer->active_contracts_count }} inactif{{ ($employer->contrats_count - $employer->active_contracts_count) > 1 ? 's' : '' }}
                                        </small>
                                    @endif
                                </td>

                                {{-- Inscription --}}
                                <td>
                                    <div class="small">
                                        <div>{{ $employer->created_at->format('d/m/Y') }}</div>
                                        <div class="text-muted">{{ $employer->created_at->format('H:i') }}</div>
                                    </div>
                                </td>

                                {{-- Statut --}}
                                <td class="text-center">
                                    @if($employer->is_active)
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
                                        <a href="{{ route('employers.show', $employer->id) }}" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        {{-- Toggle statut --}}
                                        <button type="button" 
                                                class="btn btn-sm {{ $employer->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                wire:click="toggleStatus({{ $employer->id }})"
                                                wire:confirm="Êtes-vous sûr de vouloir {{ $employer->is_active ? 'désactiver' : 'activer' }} cet employeur ?"
                                                title="{{ $employer->is_active ? 'Désactiver' : 'Activer' }}">
                                            <i class="fas fa-{{ $employer->is_active ? 'ban' : 'check' }}"></i>
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
                    Affichage de {{ $employers->firstItem() }} à {{ $employers->lastItem() }} 
                    sur {{ $employers->total() }} résultats
                </div>
                <div>
                    {{ $employers->links() }}
                </div>
            </div>
        @else
            {{-- État vide --}}
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-users fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">Aucun employeur trouvé</h5>
                <p class="text-muted">
                    @if($search || $region || $genre)
                        Aucun employeur ne correspond à vos critères de recherche.
                        <br>
                        <button type="button" 
                                class="btn btn-link p-0" 
                                wire:click="clearFilters">
                            Effacer les filtres
                        </button>
                    @else
                        Les employeurs qui s'inscrivent via l'application mobile apparaîtront ici.
                    @endif
                </p>
            </div>
        @endif
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
    <div wire:loading.class="opacity-50" wire:target="region,genre,perPage">
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
        
        .data-table th[wire\:click] {
            user-select: none;
            cursor: pointer;
        }
        
        .data-table th[wire\:click]:hover {
            background-color: rgba(var(--primary-color-rgb, 37, 99, 235), 0.1);
        }
    </style>
</div>