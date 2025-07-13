<div>
    @if($employee->confirmations->count() > 0)
        <!-- Filtres -->
        <div class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="confirmationFilter">
                        <option value="all">Toutes les confirmations</option>
                        <option value="confirmed">Confirmées uniquement</option>
                        <option value="pending">En attente uniquement</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="yearFilter">
                        <option value="">Toutes les années</option>
                        @for($year = date('Y'); $year >= 2020; $year--)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        <!-- Liste des confirmations -->
        <div class="confirmations-grid">
            @foreach($employee->confirmations->sortByDesc('annee')->sortByDesc('mois') as $confirmation)
                <div class="confirmation-card">
                    <div class="confirmation-header">
                        <div class="confirmation-period">
                            <h6 class="mb-1">{{ $confirmation->periode }}</h6>
                            <small class="text-muted">
                                @if($confirmation->contrat)
                                    Contrat avec {{ $confirmation->contrat->employer->nom_complet }}
                                @else
                                    Contrat non disponible
                                @endif
                            </small>
                        </div>
                        <div class="confirmation-status">
                            @if($confirmation->date_confirmation)
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>Confirmé
                                </span>
                            @else
                                <span class="badge bg-warning">
                                    <i class="fas fa-clock me-1"></i>En attente
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="confirmation-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="confirmation-detail">
                                    <strong>Statut emploi :</strong>
                                    <span class="badge {{ $confirmation->statut_emploi === 'actif' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $confirmation->statut_label }}
                                    </span>
                                </div>
                                <div class="confirmation-detail">
                                    <strong>Jours travaillés :</strong>
                                    <span>{{ $confirmation->jours_travailles ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="confirmation-detail">
                                    <strong>Jours d'absence :</strong>
                                    <span>{{ $confirmation->jours_absence ?? 0 }}</span>
                                </div>
                                <div class="confirmation-detail">
                                    <strong>Jours de congé :</strong>
                                    <span>{{ $confirmation->jours_conge ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        
                        @if($confirmation->salaire_verse)
                            <div class="confirmation-detail mt-2">
                                <strong>Salaire versé :</strong>
                                <span class="text-success fw-bold">
                                    {{ number_format($confirmation->salaire_verse, 0, ',', ' ') }} FDJ
                                </span>
                            </div>
                        @endif
                        
                        @if($confirmation->observations)
                            <div class="confirmation-detail mt-2">
                                <strong>Observations :</strong>
                                <div class="mt-1">
                                    <small class="text-muted">{{ $confirmation->observations }}</small>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    @if($confirmation->date_confirmation)
                        <div class="confirmation-footer">
                            <small class="text-muted">
                                <i class="fas fa-calendar-check me-1"></i>
                                Confirmé le {{ $confirmation->date_confirmation->format('d/m/Y à H:i') }}
                            </small>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        
        <!-- Statistiques des confirmations -->
        <div class="mt-4">
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="stats-mini">
                        <h6>Total</h6>
                        <span class="badge bg-primary">{{ $employee->confirmations->count() }}</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-mini">
                        <h6>Confirmées</h6>
                        <span class="badge bg-success">{{ $employee->confirmations->whereNotNull('date_confirmation')->count() }}</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-mini">
                        <h6>En attente</h6>
                        <span class="badge bg-warning">{{ $employee->confirmations->whereNull('date_confirmation')->count() }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-mini">
                        <h6>Salaire total versé</h6>
                        <span class="badge bg-info">
                            {{ number_format($employee->confirmations->sum('salaire_verse'), 0, ',', ' ') }} FDJ
                        </span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-mini">
                        <h6>Jours travaillés</h6>
                        <span class="badge bg-secondary">{{ $employee->confirmations->sum('jours_travailles') }}</span>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucune confirmation</h5>
            <p class="text-muted">Aucune confirmation mensuelle n'a été enregistrée pour cet employé.</p>
        </div>
    @endif
</div>

@push('styles')
<style>
    .confirmations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 20px;
    }
    
    .confirmation-card {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .confirmation-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .confirmation-header {
        padding: 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .confirmation-period h6 {
        color: #495057;
        margin: 0;
    }
    
    .confirmation-body {
        padding: 15px;
    }
    
    .confirmation-detail {
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .confirmation-detail strong {
        color: #495057;
        min-width: 100px;
        font-size: 0.875rem;
    }
    
    .confirmation-footer {
        padding: 10px 15px;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }
    
    .stats-mini {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        border: 1px solid #e9ecef;
    }
    
    .stats-mini h6 {
        margin-bottom: 8px;
        color: #6c757d;
        font-size: 0.875rem;
    }
</style>
@endpush