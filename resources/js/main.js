/**
 * Script principal pour le panneau d'administration Visit Djibouti
 */
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'état par défaut est défini
    initDefaultState();
    
    // Restaurer l'état du sidebar avant de rendre le contenu visible
    restoreSidebarState();
    
    // Configurer le bouton de bascule du sidebar
    setupSidebarToggle();
    
    // Initialiser les tooltips Bootstrap
    initializeTooltips();
    
    // Rendre le contenu visible et activer les transitions après un court délai
    setTimeout(function() {
        document.body.style.visibility = 'visible';
        setTimeout(function() {
            document.body.classList.remove('preload');
        }, 50);
    }, 10);
});

/**
 * Initialise l'état par défaut si aucun état n'est sauvegardé
 */
function initDefaultState() {
    if (localStorage.getItem('sidebarCollapsed') === null) {
        localStorage.setItem('sidebarCollapsed', 'true'); // Par défaut fermé
    }
}

/**
 * Restaure l'état du sidebar depuis localStorage
 */
function restoreSidebarState() {
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapseBtn = document.getElementById('sidebarCollapse');
    const collapseIcon = sidebarCollapseBtn ? sidebarCollapseBtn.querySelector('i') : null;
    
    // Récupérer l'état sauvegardé
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    // Appliquer l'état sauvegardé avant que le contenu ne soit visible
    if (isCollapsed) {
        sidebar.classList.add('active');
        if (collapseIcon) {
            collapseIcon.classList.remove('fa-bars');
            collapseIcon.classList.add('fa-indent');
        }
    } else {
        sidebar.classList.remove('active');
        if (collapseIcon) {
            collapseIcon.classList.remove('fa-indent');
            collapseIcon.classList.add('fa-bars');
        }
    }
}

/**
 * Configure le comportement du bouton de bascule du sidebar
 */
function setupSidebarToggle() {
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapseBtn = document.getElementById('sidebarCollapse');
    
    if (!sidebarCollapseBtn) return;
    
    const collapseIcon = sidebarCollapseBtn.querySelector('i');
    
    // Fonction pour mettre à jour l'icône du bouton
    function updateCollapseIcon() {
        if (sidebar.classList.contains('active')) {
            collapseIcon.classList.remove('fa-bars');
            collapseIcon.classList.add('fa-indent');
        } else {
            collapseIcon.classList.remove('fa-indent');
            collapseIcon.classList.add('fa-bars');
        }
    }
    
    // Gérer l'événement de clic sur le bouton
    sidebarCollapseBtn.addEventListener('click', function() {
        // Basculer la classe active sur le sidebar
        sidebar.classList.toggle('active');
        
        // Mettre à jour l'icône
        updateCollapseIcon();
        
        // Sauvegarder l'état du sidebar dans localStorage
        saveSidebarState();
        
        // Détruire et réinitialiser les tooltips
        resetTooltips();
    });
}

/**
 * Sauvegarde l'état du sidebar dans localStorage
 */
function saveSidebarState() {
    const sidebar = document.getElementById('sidebar');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('active'));
}

/**
 * Initialise les tooltips Bootstrap sur tous les éléments avec l'attribut data-bs-toggle="tooltip"
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Stocker la liste des tooltips dans une variable globale pour y accéder plus tard
    window.appTooltips = tooltipList;
}

/**
 * Réinitialise tous les tooltips après un changement d'état du sidebar
 * Les tooltips doivent être recréés pour s'adapter à la nouvelle position des éléments
 */
function resetTooltips() {
    // Détruire tous les tooltips existants
    if (window.appTooltips) {
        window.appTooltips.forEach(function(tooltip) {
            tooltip.dispose();
        });
    }
    
    // Recréer les tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    window.appTooltips = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}