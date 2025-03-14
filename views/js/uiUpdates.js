// Función para actualizar la referencia y el atributo del producto
export function updateReference(select) {
    const matchResult = select.name.match(/\d+/);
    if (!matchResult) {
        console.error('No se encontró un número en el atributo "name" del select:', select.name);
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
        referenceInput.value = reference;
        idProductAttributeInput.value = idProductAttribute;
    } else {
        console.error('No se encontraron los inputs de referencia o atributo para el índice:', index);
    }
}

// Función para inicializar las actualizaciones de la UI
export function initializeUIUpdates(reservationForm) {
    // Asignar el evento `change` a los campos de selección de productos existentes
    document.querySelectorAll('select[name^="product_id"]').forEach(select => {
        select.addEventListener('change', function () {
            updateReference(this);
        });
    });

    toggleCustomerField();
}

// Función para habilitar o deshabilitar el campo de cliente
export function toggleCustomerField() {
    const productCount = countProducts();
    const customerFields = document.querySelectorAll('select[name^="id_customer"]');
    customerFields.forEach(field => field.disabled = productCount !== 1);
}

// Función para contar los productos
function countProducts() {
    return document.querySelectorAll('.reservation_item').length;
}