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

        const reservationManager = await import('./modules/reservationManager.js');
        reservationManager.init(ajaxUrl, csrfToken);

        const uiManager = await import('./modules/uiManager.js');
        uiManager.init();

        // 🔹 Importa e inicializa NotaEditor
        const { NotaEditor } = await import('./modules/notaEditor/notaEditor.js');
        new NotaEditor();  // 🔥 Instanciamos la clase para que empiece a escuchar eventos

    } catch (err) {
        console.error('Error cargando módulos:', err);
    }
});
