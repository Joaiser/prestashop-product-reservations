document.addEventListener('DOMContentLoaded', function() {
    // Configuración
    const config = window.gestorProduccionVars;
    if (!config?.ajaxUrl || !config?.csrfToken) {
        console.error('Configuración AJAX no disponible');
        return;
    }

    // Elementos del DOM
    const categoriaSelect = document.getElementById('id_categoria');
    const productosContainer = document.getElementById('productos-container');
    
    if (!categoriaSelect || !productosContainer) {
        console.error('Elementos esenciales no encontrados');
        return;
    }

    // Función mejorada para cargar productos
    const cargarProductos = async (idCategoria) => {
        try {
            productosContainer.innerHTML = '<div class="loading">Cargando productos...</div>';
            
            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    ajax: true,
                    action: 'filterProducts',
                    id_categoria: idCategoria,
                    token: config.csrfToken
                })
            });
            
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const data = await response.json();
            
            if (data.success && data.html) {
                productosContainer.innerHTML = data.html;
                setupCheckboxEvents();
            } else {
                throw new Error(data.error_message || 'Respuesta inválida del servidor');
            }
        } catch (error) {
            console.error('Error al cargar productos:', error);
            productosContainer.innerHTML = `
                <div class="error-message">
                    Error al cargar productos: ${error.message}
                </div>
            `;
        }
    };

    // Función para configurar eventos de checkboxes
    const setupCheckboxEvents = () => {
        const checkboxes = document.querySelectorAll('.producto-checkbox');
        const btnAplicar = document.getElementById('btn-aplicar');
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const card = this.closest('.producto-card');
                if (card) {
                    card.style.backgroundColor = this.checked ? '#f0f8ff' : '#f5f5f5';
                    card.style.border = this.checked ? '1px solid #4a90e2' : '1px solid #ddd';
                }
                
                if (btnAplicar) {
                    const anyChecked = [...checkboxes].some(cb => cb.checked);
                    btnAplicar.style.display = anyChecked ? 'flex' : 'none';
                }
            });
        });
    };

    // Evento para cambio de categoría
    categoriaSelect.addEventListener('change', function() {
        const selectedCategory = this.value;
        console.log('Categoría seleccionada:', selectedCategory);
        cargarProductos(selectedCategory);
    });

    // Carga inicial
    cargarProductos(categoriaSelect.value);
});