<?php

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\DocumentEmployee;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\AttestationService;
use Illuminate\Support\Facades\Storage;

class EmployeeDocuments extends Component
{
    use WithFileUploads;

    public Employee $employee;
    public $replaceFile;
    public $replaceModalOpen = false;
    public $imageModalOpen = false;
    public $documentToReplace = null;
    public $selectedImageUrl = '';
    public $selectedImageTitle = '';

    protected $rules = [
        'replaceFile' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
    ];

    protected $messages = [
        'replaceFile.required' => 'Veuillez sélectionner un fichier.',
        'replaceFile.mimes' => 'Le fichier doit être au format JPG, PNG ou PDF.',
        'replaceFile.max' => 'Le fichier ne doit pas dépasser 5 MB.',
    ];

    public function mount(Employee $employee)
    {
        $this->employee = $employee->load([
            'nationality',
            'documents' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'photo',
            'identityDocument',
            'passport',
        ]);
    }

    public function getDocumentStatusProperty()
    {
        $attestationService = new AttestationService();
        
        return [
            'has_passport' => $this->employee->hasPassport(),
            'has_identity_document' => $this->employee->hasIdentityDocument(),
            'document_status' => $this->employee->getDocumentStatus(),
            'needs_attestation' => $this->employee->needsIdentityAttestation(),
            'has_valid_attestation' => $attestationService->hasValidAttestation($this->employee),
            'attestation_url' => $attestationService->getValidAttestationUrl($this->employee),
            'has_valid_work_permit' => $attestationService->hasValidWorkPermit($this->employee),
            'work_permit_url' => $attestationService->getValidWorkPermitUrl($this->employee),
        ];
    }

    public function showImageModal($imageUrl, $title)
    {
        $this->selectedImageUrl = $imageUrl;
        $this->selectedImageTitle = $title;
        $this->imageModalOpen = true;
    }

    public function closeImageModal()
    {
        $this->imageModalOpen = false;
        $this->selectedImageUrl = '';
        $this->selectedImageTitle = '';
    }

    public function showReplaceModal($documentId)
    {
        $this->documentToReplace = $this->employee->documents()->find($documentId);
        if ($this->documentToReplace) {
            $this->replaceModalOpen = true;
        }
    }

    public function closeReplaceModal()
    {
        $this->replaceModalOpen = false;
        $this->documentToReplace = null;
        $this->replaceFile = null;
        $this->resetValidation();
    }

    public function deleteDocument($documentId)
    {
        try {
            $document = $this->employee->documents()->findOrFail($documentId);
            
            // Supprimer le fichier physique et l'enregistrement
            $document->delete();
            
            // Recharger les documents
            $this->employee->refresh();
            $this->employee->load('documents');
            
            session()->flash('success', 'Document supprimé avec succès.');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la suppression du document: ' . $e->getMessage());
        }
    }

    public function replaceDocument()
    {
        $this->validate();

        try {
            if (!$this->documentToReplace) {
                session()->flash('error', 'Document introuvable.');
                return;
            }

            // Supprimer l'ancien fichier
            if ($this->documentToReplace->exists()) {
                Storage::disk('public')->delete($this->documentToReplace->chemin_fichier);
            }

            // Sauvegarder le nouveau fichier
            $fileName = time() . '_' . $this->replaceFile->getClientOriginalName();
            $filePath = "employees/documents/{$this->employee->id}/" . $fileName;
            $this->replaceFile->storeAs(dirname($filePath), basename($filePath), 'public');

            // Mettre à jour l'enregistrement
            $this->documentToReplace->update([
                'nom_fichier' => $fileName,
                'chemin_fichier' => $filePath,
                'mime_type' => $this->replaceFile->getMimeType(),
                'taille_fichier' => $this->replaceFile->getSize(),
                'extension' => $this->replaceFile->getClientOriginalExtension(),
            ]);

            // Recharger les documents
            $this->employee->refresh();
            $this->employee->load('documents');

            $this->closeReplaceModal();
            session()->flash('success', 'Document remplacé avec succès.');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors du remplacement du document: ' . $e->getMessage());
        }
    }

    /**
     * Générer une attestation d'identité avec Browsershot
     */
    public function generateAttestation()
    {
        try {
            $attestationService = new AttestationService();
            $attestationPath = $attestationService->generateIdentityAttestation($this->employee);
            
            // Recharger les documents
            $this->employee->refresh();
            $this->employee->load('documents');
            
            session()->flash('success', 'Attestation d\'identité générée avec succès.');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la génération de l\'attestation: ' . $e->getMessage());
        }
    }

    /**
     * Générer une attestation d'identité avec DomPDF
     */
    public function generateAttestationDomPDF()
    {
        try {
            $attestationService = new AttestationService();
            $attestationPath = $attestationService->generateIdentityAttestationWithDomPDF($this->employee);
            
            // Recharger les documents
            $this->employee->refresh();
            $this->employee->load('documents');
            
            session()->flash('success', 'Attestation d\'identité générée avec succès (DomPDF).');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la génération de l\'attestation DomPDF: ' . $e->getMessage());
        }
    }

    /**
     * Générer un permis de travail avec Browsershot
     */
    public function generateWorkPermit()
    {
        try {
            // Vérifier si l'employé a un passeport
            if (!$this->employee->hasPassport()) {
                session()->flash('error', 'L\'employé doit avoir un passeport valide pour générer un permis de travail.');
                return;
            }

            $attestationService = new AttestationService();
            $permitPath = $attestationService->generateWorkPermitCard($this->employee);
            
            // Recharger les documents
            $this->employee->refresh();
            $this->employee->load('documents');
            
            session()->flash('success', 'Carte de permis de travail générée avec succès.');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la génération du permis de travail: ' . $e->getMessage());
        }
    }

    /**
     * Générer un permis de travail avec DomPDF
     */
    public function generateWorkPermitDomPDF()
    {
        try {
            // Vérifier si l'employé a un passeport
            if (!$this->employee->hasPassport()) {
                session()->flash('error', 'L\'employé doit avoir un passeport valide pour générer un permis de travail.');
                return;
            }

            $attestationService = new AttestationService();
            $permitPath = $attestationService->generateWorkPermitCardWithDomPDF($this->employee);
            
            // Recharger les documents
            $this->employee->refresh();
            $this->employee->load('documents');
            
            session()->flash('success', 'Carte de permis de travail générée avec succès (DomPDF).');

        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la génération du permis de travail DomPDF: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.employee-documents');
    }
}