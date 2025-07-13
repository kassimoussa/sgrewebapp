<div>
    <!-- Filtres -->
    <div class="mb-3">
        <div class="row g-3">
            <div class="col-md-4">
                <select class="form-select" wire:model.live="contractFilter">
                    <option value="all">Tous les contrats</option>
                    <option value="active">Actifs uniquement</option>
                    <option value="inactive">Inactifs uniquement</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Liste des contrats -->
    @if($employee->contrats->count() > 0)
        <div class="contracts-timeline">
            @foreach($employee->contrats->sortByDesc('created_at') as $contrat)
                @if($contractFilter === 'all' || 
                    ($contractFilter === 'active' && $contrat->est_actif) || 
                    ($contractFilter === 'inactive' && !$contrat->est_actif))
                    
                    <div class="contract-item {{ $contrat->est_actif ? 'active' : 'inactive' }}">
                        <div class="contract-marker">
                            <i class="fas fa-{{ $contrat->est_actif ? 'play' : 'stop' }}"></i>
                        </div>
                        
                        <div class="contract-content">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1">
                                        <a href="{{ route('employers.show', $contrat->employer) }}" 
                                           class="text-decoration-none">
                                            {{ $contrat->employer->nom_complet }}
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        {{ $contrat->employer->region }}, {{ $contrat->employer->ville }}
                                    </small>
                                </div>
                                <span class="badge {{ $contrat->est_actif ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $contrat->est_actif ? 'Actif' : 'Terminé' }}
                                </span>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="contract-detail">
                                        <strong>Type d'emploi :</strong>
                                        <span class="badge bg-info">{{ $contrat->type_emploi }}</span>
                                    </div>
                                    <div class="contract-detail">
                                        <strong>Salaire mensuel :</strong>
                                        <span class="text-success fw-bold">
                                            {{ number_format($contrat->salaire_mensuel, 0, ',', ' ') }} FDJ
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="contract-detail">
                                        <strong>Date de début :</strong>
                                        <span>{{ $contrat->date_debut->format('d/m/Y') }}</span>
                                    </div>
                                    @if($contrat->date_fin)
                                        <div class="contract-detail">
                                            <strong>Date de fin :</strong>
                                            <span>{{ $contrat->date_fin->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            @if($contrat->notes)
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <strong>Notes :</strong> {{ $contrat->notes }}
                                    </small>
                                </div>
                            @endif
                            
                            <!-- Confirmations pour ce contrat -->
                            @if($contrat->confirmations->count() > 0)
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-check-circle me-1"></i>
                                        {{ $contrat->confirmations->count() }} confirmation(s) mensuelle(s)
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucun contrat</h5>
            <p class="text-muted">Cet employé n'a pas encore de contrat enregistré.</p>
        </div>
    @endif
</div>

@push('styles')
<style>
    .contracts-timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .contracts-timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    
    .contract-item {
        position: relative;
        margin-bottom: 30px;
    }
    
    .contract-marker {
        position: absolute;
        left: -37px;
        top: 5px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        background: #6c757d;
        color: white;
        border: 3px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .contract-item.active .contract-marker {
        background: #198754;
    }
    
    .contract-content {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .contract-item.active .contract-content {
        border-left: 4px solid #198754;
    }
    
    .contract-detail {
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .contract-detail strong {
        color: #495057;
        min-width: 120px;
    }
</style>
@endpush