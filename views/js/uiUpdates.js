import { initializeChoices } from './choice.js';

export const reservationSystem = {
    initialized: false,
    mainCustomer: null,
    messageContainer: null,
    isFirstSelection: true,
    
    init: function() {
        if (this.initialized) return;
        
        const mainCustomerSelect = document.querySelector('select[name="id_customer[0]"]');
        if (!mainCustomerSelect) return;
        
        // Crear mensaje (oculto inicialmente)
        this.messageContainer = document.createElement('div');
        this.messageContainer.id = 'customer-change-message';
        Object.assign(this.messageContainer.style, {
            display: 'none',
            marginTop: '10px',
            padding: '10px',
            backgroundColor: '#d4edda', 
            color: '#155724',
            border: '1px solid #f5c6cb',
            borderRadius: '4px'
        });
        this.messageContainer.innerHTML = 'Todos los productos serán reservados para <span id="new-customer-name"></span>.';
        mainCustomerSelect.parentNode.appendChild(this.messageContainer);
        
        // Inicialización
        this.mainCustomer = null;
        this.isFirstSelection = true;
        this.initialized = true;
        
        // Listener para cambios
        mainCustomerSelect.addEventListener('change', (e) => {
            this.handleCustomerChange(e);
        });
    },
    
    handleCustomerChange: function(e) {
        const newValue = e.target.value;
        if (!newValue) return;
        
        const customerName = e.target.options[e.target.selectedIndex]?.text || '';
        
        // Actualizar todos los selects de cliente
        document.querySelectorAll('select[name^="id_customer"]').forEach(select => {
            select.value = newValue;
        });
        
        // Mostrar mensaje siempre que haya un cambio válido
        if (!this.isFirstSelection && newValue !== this.mainCustomer) {
            const nameSpan = document.getElementById('new-customer-name');
            if (nameSpan) {
                nameSpan.textContent = customerName;
                this.messageContainer.style.display = 'block';
                
                setTimeout(() => {
                    this.messageContainer.style.display = 'none';
                }, 3000);
            }
        }
        
        this.mainCustomer = newValue;
        this.isFirstSelection = false;
    }
};

// Función para actualizar la referencia del producto
export function updateReference(select) {
    const reservationItem = select.closest('.reservation_item');
    if (!reservationItem) return;
    
    const index = reservationItem.getAttribute('data-index');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption?.value) {
        const referenceInput = reservationItem.querySelector(`input[name="reference[${index}]"]`);
        const idProductAttributeInput = reservationItem.querySelector(`input[name="id_product_attribute[${index}]"]`);
        
        if (referenceInput) referenceInput.value = selectedOption.getAttribute('data-reference') || '';
        if (idProductAttributeInput) idProductAttributeInput.value = selectedOption.getAttribute('data-attribute') || '0';
    }
}

// Función para inicializar la UI
export function initializeUIUpdates(reservationForm) {
    reservationSystem.init();
    
    document.querySelectorAll('select[name^="product_id"]').forEach(select => {
        select.addEventListener('change', function() {
            updateReference(this);
        });
    });

    toggleCustomerField();
    initializeChoices();
}

// Función para manejar el campo de cliente
export function toggleCustomerField() {
    const customerFields = document.querySelectorAll('select[name^="id_customer"]');
    
    customerFields.forEach((field, index) => {
        const container = field.closest('.mb-3');
        if (!container) return;
        
        if (index === 0) {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
            if (reservationSystem.mainCustomer) {
                field.value = reservationSystem.mainCustomer;
            }
        }
    });
}