{{-- resources/views/admin/employees/show.blade.php --}}
@extends('layouts.admin')

@section('title', 'Détails de l\'employé')

@section('content')
    <div class="container-fluid">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('employees.index') }}">Employés</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $employee->nom_complet }}
                </li>
            </ol>
        </nav>

        <!-- Page title -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Détails de l'employé</h1>
                <p class="text-muted">Gestion complète du profil et des contrats</p>
            </div>
        </div>

        <!-- Composant Livewire -->
        @livewire('admin.employee-details', ['employee' => $employee])
    </div>
@endsection

@push('styles')
    <style>
        .avatar-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            text-transform: uppercase;
        }

        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            color: #6c757d;
            font-weight: 500;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            color: #495057;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }

        .timeline-marker {
            z-index: 2;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialisation des tooltips Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Auto-refresh des données toutes les 5 minutes
            setInterval(function() {
                Livewire.emit('refreshData');
            }, 300000);
        });
    </script>
@endpush