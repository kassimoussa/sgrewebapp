<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGRE - Admin - @yield('title')</title>

    @vite(['resources/js/app.js'])

    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    
    @livewireStyles

    <style>
        /* Rendre le contenu invisible pendant le chargement */
        body {
            visibility: hidden;
        }

        /* Empêcher les transitions pendant le chargement de la page */
        .preload * {
            transition: none !important;
        }

        .loading-spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            transition: opacity 0.3s ease-in-out;
        }

        .loading-spinner.hidden {
            opacity: 0;
            pointer-events: none;
        }
    </style>

    
    <style>
        .table-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: white;
        }
        
        .card-header-title {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0;
        }
        
        .profile-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .table th {
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--secondary-color);
            padding: 0.75rem 1rem;
            white-space: nowrap;
        }
        
        .table td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        .status-badge.active {
            background-color: rgba(22, 163, 74, 0.1);
            color: var(--success-color);
        }
        
        .status-badge.expired {
            background-color: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
        }
        
        .status-badge.pending {
            background-color: rgba(202, 138, 4, 0.1);
            color: var(--warning-color);
        }
        
        .status-badge.expiring-soon {
            background-color: rgba(14, 165, 233, 0.1);
            color: var(--info-color);
        }
        
        .status-badge i {
            margin-right: 0.25rem;
            font-size: 0.625rem;
        }
        
        .nationality-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .card-footer {
            padding: 1rem 1.5rem;
            background-color: white;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .action-btn {
            color: var(--secondary-color);
            background: transparent;
            border: none;
            padding: 0.25rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .action-btn:hover {
            color: var(--primary-color);
        }
        
        .btn-outline-danger {
            color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-outline-danger:hover {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-outline-success {
            color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-outline-success:hover {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-outline-warning {
            color: var(--warning-color);
            border-color: var(--warning-color);
        }
        
        .btn-outline-warning:hover {
            background-color: var(--warning-color);
            color: white;
        }
    </style> 

    @stack('style')

</head>

<body class="preload">


    <div class="wrapper">
        <!-- Menu latéral -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h2 class="site-title">SRGE</h2>
                <div class="site-title-short">IMI</div>
                <p class="site-subtitle">Panneau d'administration</p>
            </div>

            <div class="menu-items">
                <ul class="list-unstyled components">
                    <li class="{{ request()->is('admin/dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="Tableau de bord">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                </ul>

                <div class="menu-section">
                    <div class="menu-section-title">Menu principal</div>
                    <ul class="list-unstyled components">
                        <li class="{{ request()->is('admin/employees*') ? 'active' : '' }}">
                            <a href="{{ route('employees.index') }}" data-bs-toggle="tooltip" data-bs-placement="right"
                                title="La liste des employés étrangers ">
                                <i class="fas fa-user-plus"></i>
                                <span>Employés</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('admin/employers*') ? 'active' : '' }}">
                            <a href="{{ route('employers.index') }}" data-bs-toggle="tooltip" data-bs-placement="right"
                                title="La liste des employeurs">
                                <i class="fas fa-users-gear"></i>
                                <span>Employeurs</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="menu-section">
                    <div class="menu-section-title">ADMINISTRATION</div>
                    <ul class="list-unstyled components">
                        <li class="{{ request()->is('admin/users*') ? 'active' : '' }}">
                            <a href="{{ route('users.index') }}" data-bs-toggle="tooltip" data-bs-placement="right"
                                title="Utilisateurs">
                                <i class="fas fa-users-cog"></i>
                                <span>Utilisateurs</span>
                            </a>
                        </li>
                        <li class="{{ request()->is('admin/rapports*') ? 'active' : '' }}">
                            <a href="{{ route('statistics.index') }}" data-bs-toggle="tooltip" data-bs-placement="right"
                                title="Rapports">
                                <i class="fas fa-chart-line"></i>
                                <span>Rapports</span>
                            </a>
                        </li>
                        {{-- <li class="{{ request()->is('admin/settings*') ? 'active' : '' }}">
                            <a href="{{ route('settings.index') }}" data-bs-toggle="tooltip" data-bs-placement="right"
                                title="Paramètres">
                                <i class="fas fa-cog"></i>
                                <span>Paramètres</span>
                            </a>
                        </li> --}}
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Conteneur du contenu principal -->
        <div id="content-container">
            <!-- Topbar -->
            <div class="topbar">
                <div class="topbar-left">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="topbar-title">@yield('page-title', 'SGRE - IMI')</span>
                </div>
                <div class="topbar-right">
                    <a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Rechercher">
                        <i class="fas fa-search"></i>
                    </a>
                    <a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Notifications">
                        <div style="position: relative;">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </div>
                    </a>
                    <a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Aide">
                        <i class="fas fa-question-circle"></i>
                    </a>
                    <!-- Dropdown pour le profil et la déconnexion -->
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header">Admin</h6>
                            </li>
                            <li><a class="dropdown-item" href={{ route('profile.show') }}>Mon profil</a>
                            </li>
                           {{--  <li><a class="dropdown-item" href="#">Paramètres</a></li> --}}
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout', [], false) }}" id="logout-form">
                                    @csrf
                                    <a class="dropdown-item" href="#"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                                    </a>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contenu de la page -->
            <div id="content">
                @yield('content')
            </div>
        </div>
    </div>

    @stack('scripts') 
    @livewireScripts   

</body>

</html>
