<div>
    <!-- En-tête avec informations principales -->
    <div class="content-card mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-circle me-3" style="width: 80px; height: 80px; font-size: 24px;">
                        {{ substr($employer->prenom, 0, 1) }}{{ substr($employer->nom, 0, 1) }}
                    </div>
                    <div>
                        <h2 class="mb-1">{{ $employer->nom_complet }}</h2>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge {{ $employer->is_active ? 'badge-success' : 'badge-danger' }}">
                                {{ $employer->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <small class="text-muted">
                                <i class="fas fa-envelope me-1"></i>{{ $employer->email }}
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-phone me-1"></i>{{ $employer->telephone }}
                            </small>
                        </div>
                        <small class="text-muted">
                            Inscrit le {{ $employer->created_at->format('d/m/Y') }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button 
                    wire:click="toggleEmployerStatus" 
                    class="btn {{ $employer->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} me-2"
                    onclick="return confirm('Êtes-vous sûr de vouloir {{ $employer->is_active ? 'désactiver' : 'activer' }} cet employeur ?')"
                >
                    <i class="fas fa-{{ $employer->is_active ? 'ban' : 'check' }} me-1"></i>
                    {{ $employer->is_active ? 'Désactiver' : 'Activer' }}
                </button>
                <a href="{{ route('employers.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="row g-3">
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-primary">
                    <h5>Contrats actifs</h5>
                    <h2 class="text-primary">{{ $stats['active_contracts'] }}</h2>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-info">
                    <h5>Total contrats</h5>
                    <h2 class="text-info">{{ $stats['total_contracts'] }}</h2>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-success">
                    <h5>Employés</h5>
                    <h2 class="text-success">{{ $stats['total_employees'] }}</h2>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-warning">
                    <h5>Confirmations</h5>
                    <h2 class="text-warning">{{ $stats['total_confirmations'] }}</h2>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-info">
                    <h5>Salaire total/mois</h5>
                    <h2 class="text-info">{{ number_format($stats['monthly_salary_total'], 0, ',', ' ') }} FDJ</h2>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-secondary">
                    <h5>Documents</h5>
                    <h2 class="text-secondary">{{ $stats['documents_count'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages de notification -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Navigation par onglets -->
    <div class="content-card">
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link {{ $activeTab === 'informations' ? 'active' : '' }}"
                    wire:click="setActiveTab('informations')"
                    type="button"
                >
                    <i class="fas fa-user me-2"></i>Informations
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link {{ $activeTab === 'contrats' ? 'active' : '' }}"
                    wire:click="setActiveTab('contrats')"
                    type="button"
                >
                    <i class="fas fa-file-contract me-2"></i>Contrats 
                    <span class="badge bg-primary ms-1">{{ $stats['total_contracts'] }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link {{ $activeTab === 'documents' ? 'active' : '' }}"
                    wire:click="setActiveTab('documents')"
                    type="button"
                >
                    <i class="fas fa-folder me-2"></i>Documents
                    <span class="badge bg-secondary ms-1">{{ $stats['documents_count'] }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link {{ $activeTab === 'activite' ? 'active' : '' }}"
                    wire:click="setActiveTab('activite')"
                    type="button"
                >
                    <i class="fas fa-chart-line me-2"></i>Activité récente
                </button>
            </li>
        </ul>

        <!-- Contenu des onglets -->
        <div class="tab-content">
            @if($activeTab === 'informations')
                @include('livewire.admin.employer-details.informations')
            @elseif($activeTab === 'contrats')
                @include('livewire.admin.employer-details.contrats')
            @elseif($activeTab === 'documents')
                @include('livewire.admin.employer-details.documents')
            @elseif($activeTab === 'activite')
                @include('livewire.admin.employer-details.activite')
            @endif
        </div>
    </div>
</div>