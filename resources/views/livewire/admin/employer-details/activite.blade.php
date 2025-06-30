{{-- resources/views/livewire/admin/employer-details/activite.blade.php --}}
<div>
    <div class="row">
        <!-- Confirmations récentes -->
        <div class="col-md-8">
            <div class="mb-4">
                <h5 class="border-bottom pb-2 mb-3">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Confirmations mensuelles récentes
                </h5>

                @if ($recentConfirmations->count() > 0)
                    <div class="activity-feed">
                        @foreach ($recentConfirmations as $confirmation)
                            <div class="activity-item">
                                <div
                                    class="activity-icon bg-{{ $confirmation->statut_emploi === 'actif' ? 'success' : ($confirmation->statut_emploi === 'conge' ? 'warning' : 'danger') }}">
                                    <i
                                        class="fas fa-{{ $confirmation->statut_emploi === 'actif' ? 'check' : ($confirmation->statut_emploi === 'conge' ? 'beach-access' : 'times') }} text-white"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        Confirmation {{ strtolower($confirmation->periode) }} -
                                        <strong>{{ $confirmation->contrat->employee->nom_complet }}</strong>
                                    </div>
                                    <div class="activity-details mb-2">
                                        <span
                                            class="badge badge-{{ $confirmation->statut_emploi === 'actif' ? 'success' : ($confirmation->statut_emploi === 'conge' ? 'warning' : 'danger') }}">
                                            {{ $confirmation->statut_label }}
                                        </span>
                                        @if ($confirmation->statut_emploi === 'actif')
                                            • {{ $confirmation->jours_travailles }} jours travaillés
                                            • {{ number_format($confirmation->salaire_verse, 0, ',', ' ') }} FDJ versés
                                        @endif
                                    </div>
                                    @if ($confirmation->observations)
                                        <div class="text-muted small mb-2">
                                            <i class="fas fa-comment me-1"></i>
                                            {{ $confirmation->observations }}
                                        </div>
                                    @endif
                                    <div class="activity-time">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $confirmation->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list me-2"></i>Voir toutes les confirmations
                        </a>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucune confirmation récente</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Résumé d'activité -->
        <div class="col-md-4">
            <div class="mb-4">
                <h5 class="border-bottom pb-2 mb-3">
                    <i class="fas fa-chart-line text-info me-2"></i>
                    Résumé d'activité
                </h5>

                <!-- Activité ce mois -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-calendar-alt me-2"></i>Ce mois-ci
                        </h6>
                        @php
                            $thisMonthConfirmations = $employer
                                ->contrats()
                                ->join(
                                    'confirmations_mensuelles',
                                    'contrats.id',
                                    '=',
                                    'confirmations_mensuelles.contrat_id',
                                )
                                ->where('confirmations_mensuelles.mois', now()->month)
                                ->where('confirmations_mensuelles.annee', now()->year)
                                ->count();
                        @endphp
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Confirmations</span>
                            <span class="badge badge-primary">{{ $thisMonthConfirmations }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span>Contrats actifs</span>
                            <span class="badge badge-success">{{ $stats['active_contracts'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Évolution -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-trending-up me-2"></i>Évolution
                        </h6>
                        @php
                            $lastMonthConfirmations = $employer
                                ->contrats()
                                ->join(
                                    'confirmations_mensuelles',
                                    'contrats.id',
                                    '=',
                                    'confirmations_mensuelles.contrat_id',
                                )
                                ->where('confirmations_mensuelles.mois', now()->subMonth()->month)
                                ->where('confirmations_mensuelles.annee', now()->subMonth()->year)
                                ->count();

                            $confirmationTrend = $thisMonthConfirmations - $lastMonthConfirmations;
                        @endphp
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Vs mois dernier</span>
                            @if ($confirmationTrend > 0)
                                <span class="badge badge-success">
                                    <i class="fas fa-arrow-up me-1"></i>+{{ $confirmationTrend }}
                                </span>
                            @elseif($confirmationTrend < 0)
                                <span class="badge badge-danger">
                                    <i class="fas fa-arrow-down me-1"></i>{{ $confirmationTrend }}
                                </span>
                            @else
                                <span class="badge badge-secondary">
                                    <i class="fas fa-equals me-1"></i>Stable
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statut des employés -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-users me-2"></i>Statut des employés
                        </h6>
                        @php
                            $lastConfirmationsByEmployee = [];
                            foreach ($employer->contrats->where('est_actif', true) as $contrat) {
                                $lastConfirmation = $contrat->confirmations()->latest('created_at')->first();
                                if ($lastConfirmation) {
                                    $status = $lastConfirmation->statut_emploi;
                                    $lastConfirmationsByEmployee[$status] =
                                        ($lastConfirmationsByEmployee[$status] ?? 0) + 1;
                                }
                            }
                        @endphp

                        @foreach (['actif' => 'Actifs', 'conge' => 'En congé', 'absent' => 'Absents'] as $status => $label)
                            @if (isset($lastConfirmationsByEmployee[$status]))
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span>{{ $label }}</span>
                                    <span
                                        class="badge badge-{{ $status === 'actif' ? 'success' : ($status === 'conge' ? 'warning' : 'danger') }}">
                                        {{ $lastConfirmationsByEmployee[$status] }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline des événements importants -->
    <div class="row mt-4">
        <div class="col-12">
            <h5 class="border-bottom pb-2 mb-3">
                <i class="fas fa-history text-secondary me-2"></i>
                Chronologie des événements
            </h5>

            <div class="timeline">
                <!-- Inscription -->
                <div class="timeline-item">
                    <div class="timeline-marker bg-primary">
                        <i class="fas fa-user-plus text-white"></i>
                    </div>
                    <div class="timeline-content">
                        <h6>Inscription sur la plateforme</h6>
                        <p class="text-muted mb-1">L'employeur s'est inscrit sur l'application</p>
                        <small class="text-muted">{{ $employer->created_at->format('d/m/Y à H:i') }}</small>
                    </div>
                </div>

                <!-- Premier contrat -->
                @if ($employer->contrats->count() > 0)
                    @php $firstContract = $employer->contrats->sortBy('created_at')->first(); @endphp
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success">
                            <i class="fas fa-file-signature text-white"></i>
                        </div>
                        <div class="timeline-content">
                            <h6>Premier contrat créé</h6>
                            <p class="text-muted mb-1">
                                Contrat avec <strong>{{ $firstContract->employee->nom_complet }}</strong>
                                ({{ $firstContract->type_emploi }})
                            </p>
                            <small class="text-muted">{{ $firstContract->created_at->format('d/m/Y à H:i') }}</small>
                        </div>
                    </div>
                @endif

                <!-- Documents uploadés -->
                @if ($employer->documents->count() > 0)
                    @php $firstDocument = $employer->documents->sortBy('created_at')->first(); @endphp
                    <div class="timeline-item">
                        <div class="timeline-marker bg-info">
                            <i class="fas fa-upload text-white"></i>
                        </div>
                        <div class="timeline-content">
                            <h6>Premiers documents uploadés</h6>
                            <p class="text-muted mb-1">
                                {{ $employer->documents->count() }} document(s) ajouté(s)
                            </p>
                            <small class="text-muted">{{ $firstDocument->created_at->format('d/m/Y à H:i') }}</small>
                        </div>
                    </div>
                @endif

                <!-- Dernière activité -->
                <div class="timeline-item">
                    <div class="timeline-marker bg-secondary">
                        <i class="fas fa-clock text-white"></i>
                    </div>
                    <div class="timeline-content">
                        <h6>Dernière activité</h6>
                        <p class="text-muted mb-1">Dernière mise à jour du profil</p>
                        <small class="text-muted">{{ $employer->updated_at->format('d/m/Y à H:i') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 30px;
    }

    .timeline-marker {
        position: absolute;
        left: -30px;
        top: 0;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid #dee2e6;
    }

    .timeline-content h6 {
        margin-bottom: 8px;
        color: #495057;
    }
</style>
