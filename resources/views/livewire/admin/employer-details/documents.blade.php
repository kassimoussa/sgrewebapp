{{-- resources/views/livewire/admin/employer-details/documents.blade.php --}}
<div>
    @if ($employer->documents->count() > 0)
        <div class="row">
            @foreach ($employer->documents as $document)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i
                                    class="fas fa-{{ $document->isImage() ? 'image' : ($document->isPdf() ? 'file-pdf' : 'file') }} me-2"></i>
                                {{ $document->type_label }}
                            </h6>
                            <span class="badge badge-info">{{ strtoupper($document->extension) }}</span>
                        </div>

                        @if ($document->isImage())
                            <div class="card-img-top" style="height: 200px; overflow: hidden; background: #f8f9fa;">
                                @if ($document->exists())
                                    <img src="{{ $document->url }}" alt="{{ $document->type_label }}" class="w-100 h-100"
                                        style="object-fit: cover; cursor: pointer;"
                                        onclick="showImageModal('{{ $document->url }}', '{{ $document->type_label }}')">
                                @else
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                            <br>Fichier introuvable
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="card-img-top d-flex align-items-center justify-content-center"
                                style="height: 200px; background: #f8f9fa;">
                                <div class="text-center">
                                    <i
                                        class="fas fa-file-{{ $document->isPdf() ? 'pdf' : 'alt' }} fa-4x text-muted mb-3"></i>
                                    <br>
                                    @if ($document->exists())
                                        <small class="text-muted">Cliquez pour télécharger</small>
                                    @else
                                        <small class="text-danger">Fichier introuvable</small>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <small class="text-muted">{{ $document->nom_fichier }}</small>
                                <small class="text-muted">{{ $document->taille_fichier_formatee }}</small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ $document->created_at->format('d/m/Y') }}
                                </small>

                                @if ($document->exists())
                                    <div class="btn-group btn-group-sm" role="group">
                                        @if ($document->isImage())
                                            <button type="button" class="btn btn-outline-primary"
                                                onclick="showImageModal('{{ $document->url }}', '{{ $document->type_label }}')"
                                                title="Voir l'image">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        @endif
                                        <a href="{{ $document->url }}" class="btn btn-outline-success"
                                            download="{{ $document->nom_fichier }}" title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                @else
                                    <span class="badge badge-danger">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Fichier manquant
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Statistiques des documents -->
        <div class="bg-light p-3 rounded mt-4">
            <h6 class="mb-3">
                <i class="fas fa-chart-bar me-2"></i>Statistiques des documents
            </h6>
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="text-center">
                        <h4 class="text-primary mb-1">{{ $employer->documents->count() }}</h4>
                        <small class="text-muted">Total documents</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-center">
                        <h4 class="text-success mb-1">
                            {{ $employer->documents->where('type_document', 'piece_identite')->count() }}</h4>
                        <small class="text-muted">Pièces d'identité</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-center">
                        <h4 class="text-info mb-1">
                            {{ $employer->documents->filter(fn($doc) => $doc->isImage())->count() }}</h4>
                        <small class="text-muted">Images</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="text-center">
                        <h4 class="text-warning mb-1">
                            {{ $employer->documents->filter(fn($doc) => $doc->isPdf())->count() }}</h4>
                        <small class="text-muted">PDF</small>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- État vide -->
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="fas fa-folder-open fa-4x text-muted"></i>
            </div>
            <h5 class="text-muted">Aucun document</h5>
            <p class="text-muted">
                Cet employeur n'a encore téléchargé aucun document.<br>
                Les documents sont uploadés depuis l'application mobile lors de l'inscription.
            </p>
        </div>
    @endif
</div>

<!-- Modal pour affichage des images -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a id="downloadButton" href="" class="btn btn-primary" download>
                    <i class="fas fa-download me-2"></i>Télécharger
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function showImageModal(imageUrl, title) {
        document.getElementById('modalImage').src = imageUrl;
        document.getElementById('imageModalLabel').textContent = title;
        document.getElementById('downloadButton').href = imageUrl;

        const modal = new bootstrap.Modal(document.getElementById('imageModal'));
        modal.show();
    }
</script>
