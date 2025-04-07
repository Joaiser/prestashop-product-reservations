// Versión final de uiManager.js
export function init() {
    console.log('UI Manager inicializado');
    
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
                    btnAplicar.classList.toggle('visible', [...checkboxes].some(c => c.checked));
                }
            });
        });

        if (btnAplicar) {
            btnAplicar.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation(); 
                
                const selectedProducts = Array.from(document.querySelectorAll('.producto-checkbox:checked'))
                    .map(c => ({
                        id_product: c.value,
                        id_product_attribute: c.dataset.idProductAttribute || 0,
                        reference: c.dataset.reference || null
                    }));
        
                if (selectedProducts.length > 0) {
                    const event = new CustomEvent('habilitarReservas', { 
                        detail: { products: selectedProducts },
                        bubbles: false
                    });
                    document.dispatchEvent(event);
                } else {
                    alert("Por favor, selecciona al menos un producto.");
                }
            });
        }
    }

    setupCheckboxEvents();
    document.addEventListener('productosCargados', setupCheckboxEvents);
}