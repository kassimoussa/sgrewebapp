@extends('layouts.admin')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')
<!-- Stats Cards -->
<div class="dashboard-cards">
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card-item stats-card-primary">
                <div class="card-icon" style="background-color: rgba(37, 99, 235, 0.1);">
                    <i class="fas fa-user-tie" style="color: #2563eb;"></i>
                </div>
                <div class="card-title">Employeurs</div>
                <div class="card-value">{{ $stats['total_employers'] }}</div>
                <div class="card-trend">
                    <i class="fas fa-arrow-up"></i>
                    Total enregistrés
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card-item stats-card-success">
                <div class="card-icon" style="background-color: rgba(22, 163, 74, 0.1);">
                    <i class="fas fa-users" style="color: #16a34a;"></i>
                </div>
                <div class="card-title">Employés</div>
                <div class="card-value">{{ $stats['total_employees'] }}</div>
                <div class="card-trend">
                    <i class="fas fa-arrow-up"></i>
                    Total actifs
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card-item stats-card-warning">
                <div class="card-icon" style="background-color: rgba(202, 138, 4, 0.1);">
                    <i class="fas fa-file-contract" style="color: #ca8a04;"></i>
                </div>
                <div class="card-title">Contrats Actifs</div>
                <div class="card-value">{{ $stats['active_contracts'] }}</div>
                <div class="card-trend">
                    <i class="fas fa-check-circle"></i>
                    En cours
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card-item stats-card-info">
                <div class="card-icon" style="background-color: rgba(14, 165, 233, 0.1);">
                    <i class="fas fa-flag" style="color: #0ea5e9;"></i>
                </div>
                <div class="card-title">Nationalités</div>
                <div class="card-value">{{ $stats['total_nationalities'] }}</div>
                <div class="card-trend">
                    <i class="fas fa-globe"></i>
                    Disponibles
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tableaux récents -->
<div class="row">
    <!-- Derniers employeurs -->
    <div class="col-lg-6 mb-4">
        <div class="table-card">
            <div class="card-header">
                <h5 class="card-header-title">
                    <i class="fas fa-user-tie me-2"></i>
                    Derniers employeurs inscrits
                </h5>
            </div>
            <div class="card-body p-0">
                @if($recent_employers->count() > 0)
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Région</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_employers as $employer)
                            <tr>
                                <td>
                                    <strong>{{ $employer->prenom }} {{ $employer->nom }}</strong>
                                </td>
                                <td>{{ $employer->email }}</td>
                                <td>
                                    <span class="nationality-badge">{{ $employer->region }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $employer->created_at->format('d/m/Y') }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun employeur pour le moment</p>
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('employers.index') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye me-1"></i>
                    Voir tous les employeurs
                </a>
            </div>
        </div>
    </div>

    <!-- Derniers employés -->
    <div class="col-lg-6 mb-4">
        <div class="table-card">
            <div class="card-header">
                <h5 class="card-header-title">
                    <i class="fas fa-users me-2"></i>
                    Derniers employés ajoutés
                </h5>
            </div>
            <div class="card-body p-0">
                @if($recent_employees->count() > 0)
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Nationalité</th>
                                <th>Âge</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent_employees as $employee)
                            <tr>
                                <td>
                                    <strong>{{ $employee->prenom }} {{ $employee->nom }}</strong>
                                </td>
                                <td>
                                    @if($employee->nationality)
                                        <span class="status-badge active">{{ $employee->nationality->name }}</span>
                                    @else
                                        <span class="status-badge pending">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $employee->age }} ans</td>
                                <td>
                                    <small class="text-muted">{{ $employee->created_at->format('d/m/Y') }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Aucun employé pour le moment</p>
                    </div>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-eye me-1"></i>
                    Voir tous les employés
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-4">
                <i class="fas fa-bolt me-2"></i>
                Actions rapides
            </h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <a href="{{ route('employers.index') }}" class="btn btn-outline-primary w-100 py-3">
                        <i class="fas fa-user-tie fa-2x d-block mb-2"></i>
                        Gérer les employeurs
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-success w-100 py-3">
                        <i class="fas fa-users fa-2x d-block mb-2"></i>
                        Gérer les employés
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="{{ route('contrats.index') }}" class="btn btn-outline-warning w-100 py-3">
                        <i class="fas fa-file-contract fa-2x d-block mb-2"></i>
                        Voir les contrats
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="{{ route('statistics.index') }}" class="btn btn-outline-info w-100 py-3">
                        <i class="fas fa-chart-bar fa-2x d-block mb-2"></i>
                        Rapports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activité récente -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <h5 class="mb-4">
                <i class="fas fa-clock me-2"></i>
                Activité récente
            </h5>
            <div class="activity-feed">
                <div class="activity-item">
                    <div class="activity-icon" style="background-color: rgba(22, 163, 74, 0.1);">
                        <i class="fas fa-user-plus" style="color: #16a34a;"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Nouvel employeur inscrit</div>
                        <div class="activity-time">Il y a 2 heures</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon" style="background-color: rgba(37, 99, 235, 0.1);">
                        <i class="fas fa-file-contract" style="color: #2563eb;"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Nouveau contrat créé</div>
                        <div class="activity-time">Il y a 5 heures</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon" style="background-color: rgba(202, 138, 4, 0.1);">
                        <i class="fas fa-edit" style="color: #ca8a04;"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Profil employé mis à jour</div>
                        <div class="activity-time">Il y a 1 jour</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection