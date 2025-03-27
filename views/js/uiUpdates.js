import { initializeChoices } from './choice.js';

// Estado global mejorado
export const reservationSystem = {
    initialized: false,
    mainCustomer: null,
    messageContainer: null,
    
    init: function() {
        if (this.initialized) return;
        
        const mainCustomerSelect = document.querySelector('select[name="id_customer[0]"]');
        if (!mainCustomerSelect) return;
        
        // Crear mensaje
        this.messageContainer = document.createElement('div');
        this.messageContainer.id = 'customer-change-message';
        Object.assign(this.messageContainer.style, {
            display: 'none',
            marginTop: '10px',
            padding: '10px',
            backgroundColor: '#f8d7da',
            color: '#721c24',
            border: '1px solid #f5c6cb',
            borderRadius: '4px'
        });
        this.messageContainer.innerHTML = 'Todos los productos serán reservados para <span id="new-customer-name"></span>.';
        mainCustomerSelect.parentNode.appendChild(this.messageContainer);
        
        // Establecer cliente principal
        this.mainCustomer = mainCustomerSelect.value;
        this.initialized = true;
        
        // Listener para cambios en el cliente principal
        mainCustomerSelect.addEventListener('change', this.handleCustomerChange.bind(this));
    },
    
    handleCustomerChange: function(e) {
        const newValue = e.target.value;
        const customerName = e.target.options[e.target.selectedIndex]?.text || '';
        
        if (newValue && newValue !== this.mainCustomer) {
            // Actualizar todos los selects de cliente
            document.querySelectorAll('select[name^="id_customer"]').forEach(select => {
                select.value = newValue;
            });
            
            // Mostrar mensaje
            const nameSpan = document.getElementById('new-customer-name');
            if (nameSpan) {
                nameSpan.textContent = customerName;
                this.messageContainer.style.display = 'block';
                
                setTimeout(() => {
                    this.messageContainer.style.display = 'none';
                }, 3000);
            }
            
            this.mainCustomer = newValue;
        }
    },
    
    // Verificar si hay productos válidos
    hasValidProducts: function() {
        return Array.from(document.querySelectorAll('.reservation_item')).some(item => {
            return item.querySelector('select[name^="product_id"]').value !== '';
        });
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
    const productCount = countProducts();
    const customerFields = document.querySelectorAll('select[name^="id_customer"]');
    
    customerFields.forEach((field, index) => {
        const container = field.closest('.mb-3');
        if (!container) return;
        
        if (index === 0) {
            container.style.display = 'block';
            field.disabled = productCount > 1;
        } else {
            container.style.display = 'none';
            // Sincronizar con el cliente principal
            if (reservationSystem.mainCustomer) {
                field.value = reservationSystem.mainCustomer;
            }
        }
    });
}

// Función para contar productos
export function countProducts() {
    return document.querySelectorAll('.reservation_item').length;
}

// Función para manejar cambios de cliente
export function handleCustomerChange() {
    // Esta función ahora está integrada en reservationSystem
    return reservationSystem.hasValidProducts();
}