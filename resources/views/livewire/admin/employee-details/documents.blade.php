<div>
    @if($employee->documents->count() > 0)
        <div class="row g-3">
            @foreach($employee->documents as $document)
                <div class="col-md-6 col-lg-4">
                    <div class="document-card">
                        <div class="document-header">
                            <div class="document-icon">
                                @if($document->type_document === 'photo')
                                    <i class="fas fa-camera text-info"></i>
                                @elseif($document->type_document === 'piece_identite')
                                    <i class="fas fa-id-card text-warning"></i>
                                @elseif($document->type_document === 'contrat')
                                    <i class="fas fa-file-contract text-success"></i>
                                @elseif($document->type_document === 'visa')
                                    <i class="fas fa-passport text-primary"></i>
                                @else
                                    <i class="fas fa-file-alt text-secondary"></i>
                                @endif
                            </div>
                            <div class="document-info">
                                <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $document->type_document)) }}</h6>
                                <small class="text-muted">
                                    Ajouté le {{ $document->created_at->format('d/m/Y') }}
                                </small>
                            </div>
                        </div>
                        
                        <div class="document-body">
                            @if($document->type_document === 'photo')
                                <div class="document-preview">
                                    <img src="{{ asset('storage/' . $document->chemin_fichier) }}" 
                                         alt="Photo de {{ $employee->nom_complet }}" 
                                         class="img-fluid rounded">
                                </div>
                            @else
                                <div class="document-preview-placeholder">
                                    <i class="fas fa-file-alt fa-3x text-muted"></i>
                                    <p class="text-muted mt-2 mb-0">Document</p>
                                </div>
                            @endif
                        </div>
                        
                        <div class="document-actions">
                            <a href="{{ asset('storage/' . $document->chemin_fichier) }}" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Voir
                            </a>
                            <a href="{{ asset('storage/' . $document->chemin_fichier) }}" 
                               download 
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i>Télécharger
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Statistiques des documents -->
        <div class="mt-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="stats-mini">
                        <h6>Total documents</h6>
                        <span class="badge bg-primary">{{ $employee->documents->count() }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-mini">
                        <h6>Photos</h6>
                        <span class="badge bg-info">{{ $employee->documents->where('type_document', 'photo')->count() }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-mini">
                        <h6>Pièces d'identité</h6>
                        <span class="badge bg-warning">{{ $employee->documents->where('type_document', 'piece_identite')->count() }}</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-mini">
                        <h6>Autres</h6>
                        <span class="badge bg-secondary">{{ $employee->documents->whereNotIn('type_document', ['photo', 'piece_identite'])->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucun document</h5>
            <p class="text-muted">Aucun document n'a été téléchargé pour cet employé.</p>
        </div>
    @endif
</div>

@push('styles')
<style>
    .document-card {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .document-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .document-header {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
    }
    
    .document-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        margin-right: 12px;
        font-size: 18px;
    }
    
    .document-info h6 {
        color: #495057;
        margin: 0;
    }
    
    .document-body {
        padding: 15px;
    }
    
    .document-preview img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    
    .document-preview-placeholder {
        height: 150px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 4px;
    }
    
    .document-actions {
        padding: 15px;
        border-top: 1px solid #e9ecef;
        display: flex;
        gap: 8px;
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