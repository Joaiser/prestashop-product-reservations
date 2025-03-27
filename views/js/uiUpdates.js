import { initializeChoices } from './choice.js';

// Estado global mejorado
const customerState = {
    initialized: false,
    previousValue: null,
    messageContainer: null,
    hasProducts: false
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
            // Actualizamos el estado con el valor actual
            if (customerState.previousValue === null) {
                customerState.previousValue = field.value;
            }
        } else {
            container.style.display = 'none';
            field.value = customerFields[0].value;
        }
    });
}

// Función para contar productos
export function countProducts() {
    return document.querySelectorAll('.reservation_item').length;
}

// Función para manejar cambios de cliente (versión mejorada)
export function handleCustomerChange(hasProducts) {
    const customerSelect = document.querySelector('select[name="id_customer[0]"]');
    if (!customerSelect) return;

    // Inicializar el contenedor del mensaje una sola vez
    if (!customerState.messageContainer) {
        customerState.messageContainer = document.createElement('div');
        customerState.messageContainer.id = 'customer-change-message';
        Object.assign(customerState.messageContainer.style, {
            display: 'none',
            marginTop: '10px',
            padding: '10px',
            backgroundColor: '#f8d7da',
            color: '#721c24',
            border: '1px solid #f5c6cb',
            borderRadius: '4px'
        });
        customerState.messageContainer.innerHTML = 'Todos los productos serán reservados para <span id="new-customer-name"></span>.';
        customerSelect.closest('.mb-3')?.appendChild(customerState.messageContainer);
    }

    // Actualizar estado de productos
    customerState.hasProducts = hasProducts();

    // Inicializar con el valor actual si es la primera vez
    if (customerState.previousValue === null) {
        customerState.previousValue = customerSelect.value;
    }

    // Manejador de eventos mejorado
    customerSelect.addEventListener('change', function() {
        const newValue = this.value;
        const customerName = this.options[this.selectedIndex]?.text || '';
        
        // Actualizar estado de productos en cada cambio
        customerState.hasProducts = hasProducts();
        
        // Mostrar mensaje solo si:
        // 1. Hay productos
        // 2. El valor cambió realmente
        // 3. No es un valor vacío
        if (customerState.hasProducts && newValue && newValue !== customerState.previousValue) {
            const nameElement = document.getElementById('new-customer-name');
            if (nameElement) {
                nameElement.textContent = customerName;
                customerState.messageContainer.style.display = 'block';
                
                setTimeout(() => {
                    customerState.messageContainer.style.display = 'none';
                }, 3000);
            }
        }

        // Actualizar el valor anterior
        customerState.previousValue = newValue;
    });
}