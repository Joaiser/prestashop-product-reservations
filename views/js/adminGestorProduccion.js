document.addEventListener("DOMContentLoaded", function() {
    // Configuración global
    const {ajaxUrl, csrfToken} = window.gestorProduccionVars || {};
    if (!ajaxUrl || !csrfToken) {
        console.error('Configuración AJAX no disponible');
        return;
    }

    // Inicialización de módulos
    import('./modules/productFilter.js')
        .then(module => module.init(ajaxUrl, csrfToken))
        .catch(err => console.error('Error cargando módulo de filtrado:', err));

    import('./modules/reservationManager.js')
        .then(module => module.init(ajaxUrl, csrfToken))
        .catch(err => console.error('Error cargando módulo de reservas:', err));

    import('./modules/uiManager.js')
        .then(module => module.init())
        .catch(err => console.error('Error cargando módulo de UI:', err));
});