import { initializeChoices } from './choice.js';

// Función para actualizar la referencia y el atributo del producto
export function updateReference(select) {
    const matchResult = select.name.match(/\d+/);
    if (!matchResult) {
        return;
    }

    const index = matchResult[0];
    const selectedProduct = select.options[select.selectedIndex];

    // Obtener la referencia y el atributo del producto desde los atributos data-*
    const reference = selectedProduct.getAttribute('data-reference');
    const idProductAttribute = selectedProduct.getAttribute('data-attribute');

    // Actualizar los campos ocultos
    const referenceInput = document.querySelector(`input[name="reference[${index}]"]`);
    const idProductAttributeInput = document.querySelector(`input[name="id_product_attribute[${index}]"]`);

    if (referenceInput && idProductAttributeInput) {
        referenceInput.value = reference || ''; // Asegurarse de que no sea undefined
        idProductAttributeInput.value = idProductAttribute || ''; // Asegurarse de que no sea undefined
    } 
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


