{{-- resources/views/livewire/admin/employer-details/contrats.blade.php --}}
<div>
    <!-- Filtres et recherche -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="d-flex gap-3">
                <select wire:model.live="contractFilter" class="form-select" style="width: auto;">
                    <option value="all">Tous les contrats</option>
                    <option value="active">Contrats actifs</option>
                    <option value="inactive">Contrats terminés</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="input-group">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="searchEmployee" 
                    class="form-control" 
                    placeholder="Rechercher un employé..."
                >
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
            </div>
        </div>
    </div>

    @if($filteredContracts->count() > 0)
        <!-- Tableau des contrats -->
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th>Employé</th>
                        <th>Type d'emploi</th>
                        <th>Salaire</th>
                        <th>Période</th>
                        <th>Statut</th>
                        <th>Confirmations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($filteredContracts as $contrat)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($contrat->employee->photo_url && $contrat->employee->photo_url !== asset('images/default-employee.png'))
                                        <img src="{{ $contrat->employee->photo_small }}" 
                                             alt="Photo {{ $contrat->employee->nom_complet }}" 
                                             class="avatar-photo-small me-2"
                                             loading="lazy">
                                    @else
                                        <div class="avatar-circle me-2" style="width: 40px; height: 40px; font-size: 14px;">
                                            {{ substr($contrat->employee->prenom, 0, 1) }}{{ substr($contrat->employee->nom, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <strong>{{ $contrat->employee->nom_complet }}</strong><br>
                                        <small class="text-muted">
                                            {{ $contrat->employee->nationality->nom ?? 'N/A' }} • 
                                            {{ $contrat->employee->age }} ans
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $contrat->type_emploi }}</span>
                            </td>
                            <td>
                                <strong>{{ number_format($contrat->salaire_mensuel, 0, ',', ' ') }} FDJ</strong><br>
                                <small class="text-muted">par mois</small>
                            </td>
                            <td>
                                <div>
                                    <strong>Début :</strong> {{ $contrat->date_debut->format('d/m/Y') }}<br>
                                    @if($contrat->date_fin)
                                        <strong>Fin :</strong> {{ $contrat->date_fin->format('d/m/Y') }}
                                    @else
                                        <span class="text-success">En cours</span>
                                    @endif
                                </div>
                                <small class="text-muted">
                                    Durée : {{ $contrat->duree_en_mois }} mois
                                </small>
                            </td>
                            <td>
                                @if($contrat->est_actif)
                                    <span class="badge badge-success">
                                        <i class="fas fa-check me-1"></i>Actif
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        <i class="fas fa-times me-1"></i>Terminé
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $contrat->confirmations->count() }}</strong> confirmations<br>
                                    @if($contrat->confirmations->count() > 0)
                                        <small class="text-muted">
                                            Dernière : {{ $contrat->confirmations->first()->created_at->format('m/Y') }}
                                        </small>
                                    @else
                                        <small class="text-warning">Aucune confirmation</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.employees.show', $contrat->employee) }}" 
                                       class="btn btn-sm btn-outline-primary"
                                       title="Voir le profil de {{ $contrat->employee->nom_complet }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.employees.show', $contrat->employee) }}">
                                                    <i class="fas fa-user me-2"></i>Profil complet
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.employees.show', $contrat->employee) }}?tab=confirmations">
                                                    <i class="fas fa-file-alt me-2"></i>Confirmations
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.employees.show', $contrat->employee) }}?tab=documents">
                                                    <i class="fas fa-folder me-2"></i>Documents
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            @if($contrat->est_actif)
                                                <li>
                                                    <button 
                                                        class="dropdown-item text-danger" 
                                                        wire:click="terminateContract({{ $contrat->id }})"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir terminer ce contrat ?')"
                                                    >
                                                        <i class="fas fa-stop me-2"></i>Terminer contrat
                                                    </button>
                                                </li>
                                            @else
                                                <li>
                                                    <button 
                                                        class="dropdown-item text-success" 
                                                        wire:click="reactivateContract({{ $contrat->id }})"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir réactiver ce contrat ?')"
                                                    >
                                                        <i class="fas fa-play me-2"></i>Réactiver contrat
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
                <small class="text-muted">
                    Affichage de {{ $filteredContracts->firstItem() ?? 0 }} à {{ $filteredContracts->lastItem() ?? 0 }} 
                    sur {{ $filteredContracts->total() }} contrats
                </small>
            </div>
            <div>
                {{ $filteredContracts->links() }}
            </div>
        </div>

    @else
        <!-- État vide -->
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-file-contract fa-4x text-muted"></i>
            </div>
            <h5 class="text-muted">Aucun contrat trouvé</h5>
            <p class="text-muted">
                @if($searchEmployee)
                    Aucun contrat ne correspond à votre recherche "{{ $searchEmployee }}".
                @elseif($contractFilter === 'active')
                    Cet employeur n'a aucun contrat actif.
                @elseif($contractFilter === 'inactive')
                    Cet employeur n'a aucun contrat terminé.
                @else
                    Cet employeur n'a encore créé aucun contrat.
                @endif
            </p>
            @if($searchEmployee || $contractFilter !== 'all')
                <button wire:click="$set('searchEmployee', '')" wire:click="$set('contractFilter', 'all')" class="btn btn-outline-primary">
                    <i class="fas fa-times me-2"></i>Effacer les filtres
                </button>
            @endif
        </div>
    @endif

    <!-- Résumé des contrats par statut -->
    @if($stats['total_contracts'] > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="bg-light p-3 rounded">
                    <h6 class="mb-3">
                        <i class="fas fa-chart-pie me-2"></i>Résumé des contrats
                    </h6>
                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <h4 class="text-success mb-1">{{ $stats['active_contracts'] }}</h4>
                                <small class="text-muted">Contrats actifs</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <h4 class="text-danger mb-1">{{ $stats['total_contracts'] - $stats['active_contracts'] }}</h4>
                                <small class="text-muted">Contrats terminés</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <h4 class="text-primary mb-1">{{ $stats['total_employees'] }}</h4>
                                <small class="text-muted">Employés différents</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center">
                                <h4 class="text-info mb-1">{{ number_format($stats['monthly_salary_total'], 0, ',', ' ') }}</h4>
                                <small class="text-muted">FDJ/mois (actifs)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    .avatar-photo-small {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary-color);
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .avatar-circle {
        background-color: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
</style>
@endpush