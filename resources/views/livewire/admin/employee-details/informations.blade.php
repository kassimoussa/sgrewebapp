<div class="row">
    <!-- Informations personnelles -->
    <div class="col-md-6">
        <h5 class="mb-3">
            <i class="fas fa-user me-2"></i>Informations personnelles
        </h5>
        
        <div class="info-group">
            <div class="info-item">
                <strong>Prénom :</strong>
                <span>{{ $employee->prenom }}</span>
            </div>
            <div class="info-item">
                <strong>Nom :</strong>
                <span>{{ $employee->nom }}</span>
            </div>
            <div class="info-item">
                <strong>Genre :</strong>
                <span>{{ $employee->genre }}</span>
            </div>
            <div class="info-item">
                <strong>État civil :</strong>
                <span>{{ $employee->etat_civil }}</span>
            </div>
            <div class="info-item">
                <strong>Date de naissance :</strong>
                <span>{{ $employee->date_naissance->format('d/m/Y') }} ({{ $employee->age }} ans)</span>
            </div>
            <div class="info-item">
                <strong>Nationalité :</strong>
                <span class="badge bg-primary">{{ $employee->nationality->nom }}</span>
            </div>
            <div class="info-item">
                <strong>Date d'arrivée à Djibouti :</strong>
                <span>{{ $employee->date_arrivee->format('d/m/Y') }} ({{ $employee->duree_en_djibouti }} ans)</span>
            </div>
        </div>
    </div>

    <!-- Localisation -->
    <div class="col-md-6">
        <h5 class="mb-3">
            <i class="fas fa-map-marker-alt me-2"></i>Localisation
        </h5>
        
        <div class="info-group">
            <div class="info-item">
                <strong>Région :</strong>
                <span>{{ $employee->region }}</span>
            </div>
            <div class="info-item">
                <strong>Ville :</strong>
                <span>{{ $employee->ville }}</span>
            </div>
            <div class="info-item">
                <strong>Quartier :</strong>
                <span>{{ $employee->quartier }}</span>
            </div>
            <div class="info-item">
                <strong>Adresse complète :</strong>
                <div class="mt-1">
                    <span class="text-muted">{{ $employee->adresse_complete }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contrat actuel -->
@if($employee->activeContrat)
    <hr class="my-4">
    <div class="row">
        <div class="col-12">
            <h5 class="mb-3">
                <i class="fas fa-file-contract me-2"></i>Contrat actuel
            </h5>
            
            <div class="alert alert-success">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <strong>Employeur :</strong>
                            <a href="{{ route('admin.employers.show', $employee->activeContrat->employer) }}" 
                               class="text-decoration-none">
                                {{ $employee->activeContrat->employer->nom_complet }}
                            </a>
                        </div>
                        <div class="info-item">
                            <strong>Type d'emploi :</strong>
                            <span class="badge bg-info">{{ $employee->activeContrat->type_emploi }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <strong>Salaire mensuel :</strong>
                            <span class="text-success fw-bold">
                                {{ number_format($employee->activeContrat->salaire_mensuel, 0, ',', ' ') }} FDJ
                            </span>
                        </div>
                        <div class="info-item">
                            <strong>Date de début :</strong>
                            <span>{{ $employee->activeContrat->date_debut->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <hr class="my-4">
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Cet employé n'a pas de contrat actuel.
    </div>
@endif

@push('styles')
<style>
    .info-group {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #e9ecef;
    }
    
    .info-item {
        margin-bottom: 12px;
        display: flex;
        align-items: flex-start;
        flex-wrap: wrap;
    }
    
    .info-item:last-child {
        margin-bottom: 0;
    }
    
    .info-item strong {
        min-width: 150px;
        color: #495057;
        margin-right: 10px;
    }
    
    .info-item span {
        flex: 1;
    }
    
    .alert .info-item {
        margin-bottom: 8px;
        background: none;
    }
    
    .alert .info-item:last-child {
        margin-bottom: 0;
    }
</style>
@endpush