export function init() {
    // Configuración de checkboxes
    function setupCheckboxEvents() {
        const checkboxes = document.querySelectorAll('.producto-checkbox');
        const btnAplicar = document.getElementById('btn-aplicar');
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const card = this.closest('.producto-card');
                if (card) {
                    card.style.backgroundColor = this.checked ? '#f0f8ff' : '';
                    card.style.border = this.checked ? '1px solid #4a90e2' : '';
                }
                
                if (btnAplicar) {
                    btnAplicar.style.display = [...checkboxes].some(c => c.checked) ? 'flex' : 'none';
                    btnAplicar.style.alignItems = 'center';
                    btnAplicar.style.justifyContent = 'center';
                    btnAplicar.style.gap = '8px';
                    
                }
            });
        });
    }

    // Escuchar evento de productos cargados
    document.addEventListener('productosCargados', setupCheckboxEvents);
    
    // Configuración inicial
    setupCheckboxEvents();
}