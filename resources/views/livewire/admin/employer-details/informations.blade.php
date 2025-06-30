{{-- resources/views/livewire/admin/employer-details/informations.blade.php --}}
<div class="row">
    <div class="col-md-6">
        <div class="mb-4">
            <h5 class="border-bottom pb-2 mb-3">
                <i class="fas fa-id-card text-primary me-2"></i>Informations personnelles
            </h5>
            <div class="row">
                <div class="col-sm-4"><strong>Prénom :</strong></div>
                <div class="col-sm-8">{{ $employer->prenom ?: 'Non renseigné' }}</div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4"><strong>Nom :</strong></div>
                <div class="col-sm-8">{{ $employer->nom ?: 'Non renseigné' }}</div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4"><strong>Genre :</strong></div>
                <div class="col-sm-8">
                    @if($employer->genre)
                        <span class="badge {{ $employer->genre === 'Homme' ? 'badge-info' : 'badge-warning' }}">
                            {{ $employer->genre }}
                        </span>
                    @else
                        <span class="text-muted">Non renseigné</span>
                    @endif
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4"><strong>Email :</strong></div>
                <div class="col-sm-8">
                    <a href="mailto:{{ $employer->email }}" class="text-decoration-none">
                        {{ $employer->email }}
                    </a>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4"><strong>Téléphone :</strong></div>
                <div class="col-sm-8">
                    <a href="tel:{{ $employer->telephone }}" class="text-decoration-none">
                        {{ $employer->telephone }}
                    </a>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h5 class="border-bottom pb-2 mb-3">
                <i class="fas fa-calendar text-success me-2"></i>Informations temporelles
            </h5>
            <div class="row">
                <div class="col-sm-4"><strong>Inscrit le :</strong></div>
                <div class="col-sm-8">{{ $employer->created_at->format('d/m/Y à H:i') }}</div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4"><strong>Dernière mise à jour :</strong></div>
                <div class="col-sm-8">{{ $employer->updated_at->format('d/m/Y à H:i') }}</div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4"><strong>Ancienneté :</strong></div>
                <div class="col-sm-8">
                    {{ $employer->created_at->diffForHumans() }}
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h5 class="border-bottom pb-2 mb-3">
                <i class="fas fa-shield-alt text-warning me-2"></i>Statut du compte
            </h5>
            <div class="row">
                <div class="col-sm-4"><strong>Statut :</strong></div>
                <div class="col-sm-8">
                    <span class="badge {{ $employer->is_active ? 'badge-success' : 'badge-danger' }}">
                        <i class="fas fa-{{ $employer->is_active ? 'check' : 'times' }} me-1"></i>
                        {{ $employer->is_active ? 'Compte actif' : 'Compte désactivé' }}
                    </span>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4"><strong>Profil complet :</strong></div>
                <div class="col-sm-8">
                    @php
                        $profileComplete = !is_null($employer->prenom) && 
                                         !is_null($employer->nom) && 
                                         !is_null($employer->genre) && 
                                         !is_null($employer->region) && 
                                         !is_null($employer->ville) && 
                                         !is_null($employer->quartier);
                    @endphp
                    <span class="badge {{ $profileComplete ? 'badge-success' : 'badge-warning' }}">
                        <i class="fas fa-{{ $profileComplete ? 'check' : 'exclamation-triangle' }} me-1"></i>
                        {{ $profileComplete ? 'Complet' : 'Incomplet' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="mb-4">
            <h5 class="border-bottom pb-2 mb-3">
                <i class="fas fa-map-marker-alt text-danger me-2"></i>Adresse de résidence
            </h5>
            <div class="row">
                <div class="col-sm-4"><strong>Région :</strong></div>
                <div class="col-sm-8">
                    {{ $employer->region ?: 'Non renseignée' }}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4"><strong>Ville :</strong></div>
                <div class="col-sm-8">{{ $employer->ville ?: 'Non renseignée' }}</div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4"><strong>Quartier :</strong></div>
                <div class="col-sm-8">{{ $employer->quartier ?: 'Non renseigné' }}</div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-4"><strong>Adresse complète :</strong></div>
                <div class="col-sm-8">
                    @if($employer->quartier && $employer->ville && $employer->region)
                        <div class="bg-light p-2 rounded">
                            {{ $employer->quartier }}<br>
                            {{ $employer->ville }}, {{ $employer->region }}
                        </div>
                    @else
                        <span class="text-muted">Adresse incomplète</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h5 class="border-bottom pb-2 mb-3">
                <i class="fas fa-chart-bar text-info me-2"></i>Statistiques
            </h5>
            <div class="row">
                <div class="col-sm-6">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-primary mb-1">{{ $stats['active_contracts'] }}</h4>
                        <small class="text-muted">Contrats actifs</small>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-success mb-1">{{ $stats['total_employees'] }}</h4>
                        <small class="text-muted">Employés différents</small>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-sm-6">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-warning mb-1">{{ $stats['total_confirmations'] }}</h4>
                        <small class="text-muted">Confirmations</small>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="text-center p-3 bg-light rounded">
                        <h4 class="text-info mb-1">{{ number_format($stats['monthly_salary_total'], 0, ',', ' ') }}</h4>
                        <small class="text-muted">FDJ/mois total</small>
                    </div>
                </div>
            </div>
        </div>

        @if(!$profileComplete)
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Profil incomplet</strong><br>
                Certaines informations manquent pour compléter le profil de cet employeur.
                L'utilisateur peut compléter son profil depuis l'application mobile.
            </div>
        @endif

        @if(!$employer->is_active)
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-ban me-2"></i>
                <strong>Compte désactivé</strong><br>
                Cet employeur ne peut pas se connecter à l'application mobile.
                Les contrats existants restent consultables.
            </div>
        @endif
    </div>
</div>