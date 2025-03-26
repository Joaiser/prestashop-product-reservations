import { initializeChoices } from './choice.js';
// Función para actualizar la referencia y el atributo del producto
export function updateReference(select) {
    const reservationItem = select.closest('.reservation_item');
    if (!reservationItem) return;
    
    const index = reservationItem.getAttribute('data-index');
    let reference = '';
    let idProductAttribute = '0';
    
    // Obtener la opción seleccionada directamente del select
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        reference = selectedOption.getAttribute('data-reference') || '';
        idProductAttribute = selectedOption.getAttribute('data-attribute') || '0';
    }
    
    // Actualizar campos ocultos
    const referenceInput = reservationItem.querySelector(`input[name="reference[${index}]"]`);
    const idProductAttributeInput = reservationItem.querySelector(`input[name="id_product_attribute[${index}]"]`);
    
    if (referenceInput) referenceInput.value = reference;
    if (idProductAttributeInput) idProductAttributeInput.value = idProductAttribute;
    
}

export function initializeUIUpdates(reservationForm) {

    // Inicializa el evento de cambio de producto
    document.querySelectorAll('select[name^="product_id"]').forEach(select => {
        select.addEventListener('change', function () {
            updateReference(this);
        });
    });

    // Solo actualiza el campo de cliente y no los campos de productos
    toggleCustomerField();

    // Inicializa Choices.js para todos los selects de productos
    initializeChoices();
}

// Función para habilitar o deshabilitar el campo de cliente
export function toggleCustomerField() {
    const productCount = countProducts();
    const customerFields = document.querySelectorAll('select[name^="id_customer"]');
    
    customerFields.forEach((field, index) => {
        const container = field.closest('.mb-3'); // Encuentra el contenedor del campo
        
        if (container) {
            if (index === 0) { // Siempre mostrar el primer campo
                container.style.display = 'block';
                field.disabled = productCount > 1;
            } else { // Ocultar completamente los campos adicionales
                container.style.display = 'none';
                
                // Asegurarse de que el valor se mantenga
                const firstCustomerValue = customerFields[0].value;
                field.value = firstCustomerValue;
            }
        }
    });
}

// Función para contar los productos
function countProducts() {
    return document.querySelectorAll('.reservation_item').length;
}


