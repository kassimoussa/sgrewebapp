<div>
    <!-- En-tête avec informations principales -->
    <div class="content-card mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-3">
                    @if($employee->photo_url && $employee->photo_url !== asset('images/default-employee.png'))
                        <img src="{{ $employee->photo_url }}" 
                             alt="Photo {{ $employee->nom_complet }}" 
                             class="avatar-photo-large me-3">
                    @else
                        <div class="avatar-circle me-3" style="width: 80px; height: 80px; font-size: 24px;">
                            {{ substr($employee->prenom, 0, 1) }}{{ substr($employee->nom, 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="mb-1">{{ $employee->nom_complet }}</h2>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge {{ $employee->is_active ? 'badge-success' : 'badge-danger' }}">
                                {{ $employee->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <small class="text-muted">
                                <i class="fas fa-flag me-1"></i>{{ $employee->nationality->nom }}
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-birthday-cake me-1"></i>{{ $employee->age }} ans
                            </small>
                        </div>
                        <small class="text-muted">
                            Inscrit le {{ $employee->created_at->format('d/m/Y') }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <button 
                    wire:click="toggleStatus" 
                    class="btn {{ $employee->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} me-2"
                    onclick="return confirm('Êtes-vous sûr de vouloir {{ $employee->is_active ? 'désactiver' : 'activer' }} cet employé ?')"
                >
                    <i class="fas fa-{{ $employee->is_active ? 'ban' : 'check' }} me-1"></i>
                    {{ $employee->is_active ? 'Désactiver' : 'Activer' }}
                </button>
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="row g-3">
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-primary">
                    <h5>Contrats actifs</h5>
                    <h2 class="text-primary">{{ $employee->contrats->where('est_actif', true)->count() }}</h2>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-info">
                    <h5>Total contrats</h5>
                    <h2 class="text-info">{{ $employee->contrats->count() }}</h2>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-success">
                    <h5>Employeurs</h5>
                    <h2 class="text-success">{{ $employee->contrats->unique('employer_id')->count() }}</h2>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-warning">
                    <h5>Confirmations</h5>
                    <h2 class="text-warning">{{ $employee->confirmations->count() }}</h2>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-info">
                    <h5>Salaire actuel</h5>
                    <h2 class="text-info">{{ $employee->activeContrat ? number_format($employee->activeContrat->salaire_mensuel, 0, ',', ' ') . ' FDJ' : 'N/A' }}</h2>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stats-card stats-card-secondary">
                    <h5>Documents</h5>
                    <h2 class="text-secondary">{{ $employee->documents->count() }}</h2>
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
                    <span class="badge bg-primary ms-1">{{ $employee->contrats->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link {{ $activeTab === 'documents' ? 'active' : '' }}"
                    wire:click="setActiveTab('documents')"
                    type="button"
                >
                    <i class="fas fa-folder me-2"></i>Documents
                    <span class="badge bg-secondary ms-1">{{ $employee->documents->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button 
                    class="nav-link {{ $activeTab === 'confirmations' ? 'active' : '' }}"
                    wire:click="setActiveTab('confirmations')"
                    type="button"
                >
                    <i class="fas fa-check-square me-2"></i>Confirmations
                    <span class="badge bg-warning ms-1">{{ $employee->confirmations->count() }}</span>
                </button>
            </li>
        </ul>

        <!-- Contenu des onglets -->
        <div class="tab-content">
            @if($activeTab === 'informations')
                @include('livewire.admin.employee-details.informations')
            @elseif($activeTab === 'contrats')
                @include('livewire.admin.employee-details.contrats')
            @elseif($activeTab === 'documents')
                @include('livewire.admin.employee-details.documents')
            @elseif($activeTab === 'confirmations')
                @include('livewire.admin.employee-details.confirmations')
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    .avatar-photo-large {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--primary-color);
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
    
    .stats-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .stats-card h5 {
        font-size: 0.875rem;
        color: #6c757d;
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .stats-card h2 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        line-height: 1;
    }
    
    .stats-card-primary {
        border-left: 4px solid var(--bs-primary);
    }
    
    .stats-card-info {
        border-left: 4px solid var(--bs-info);
    }
    
    .stats-card-success {
        border-left: 4px solid var(--bs-success);
    }
    
    .stats-card-warning {
        border-left: 4px solid var(--bs-warning);
    }
    
    .stats-card-secondary {
        border-left: 4px solid var(--bs-secondary);
    }
    
    .content-card {
        background: white;
        border-radius: 8px;
        padding: 24px;
        border: 1px solid #e9ecef;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
</style>
@endpush