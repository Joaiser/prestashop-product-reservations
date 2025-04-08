document.addEventListener("DOMContentLoaded", async function() {
    // Configuración global
    const { ajaxUrl, csrfToken } = window.gestorProduccionVars || {};
    if (!ajaxUrl || !csrfToken) {
        console.error('Configuración AJAX no disponible');
        return;
    }

    try {
        // Inicialización de módulos existentes
        const productFilter = await import('./modules/productFilter.js');
        productFilter.init(ajaxUrl, csrfToken);

        // Primero importar uiManager.js
        const uiManager = await import('./modules/uiManager.js');
        uiManager.init();

        // Luego importar reservationManager.js
        const reservationManager = await import('./modules/reservationManager.js');
        reservationManager.init(ajaxUrl, csrfToken);

        // Luego importar otros módulos
        const { NotaEditor } = await import('./modules/notaEditor/notaEditor.js');
        const notaEditor = new NotaEditor();
        notaEditor.init();

    } catch (err) {
        console.error('Error cargando módulos:', err);
    }
});
