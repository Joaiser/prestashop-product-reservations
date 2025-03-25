import { areFieldsComplete, showError, showSuccess } from './utils.js';
import { sendReservation } from './apiHandlers.js';
import { toggleCustomerField, updateReference } from './uiUpdates.js';
import { initializeDynamicChoices } from './choice.js';

export function initializeFormHandlers(reservationForm) {
    // Manejar el envío del formulario
    reservationForm.addEventListener('submit', function (e) {
        e.preventDefault();

        // Comprobar si todos los campos están completos antes de enviar
        if (!areFieldsComplete()) {
            showError('Por favor, completa todos los campos antes de enviar el formulario.');
            return;
        }

        const token = document.querySelector('input[name="token"]').value;
        const products = getProductsFromForm();

        sendReservation(reservationForm.action, token, products)
            .then(data => {
                if (data.success) {
                    showSuccess('Reserva realizada con éxito.');
                    resetForm();
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    showError(data.message || 'Error desconocido.');
                }
            })
            .catch(() => {
                showError('Ocurrió un error al procesar la solicitud.');
            });
    });

    // Manejar el botón de añadir más reservas
    const addMoreReservationsButton = document.getElementById('add_more_reservations');
    if (addMoreReservationsButton) {
        addMoreReservationsButton.addEventListener('click', function () {
            // Comprobar si todos los campos están completos antes de añadir otro producto
            if (!areFieldsComplete()) {
                showError('Por favor, completa todos los campos antes de añadir otro producto.');
                return;
            }

            addReservationItem(); // Añadir un nuevo item de reserva
        });
    }
}

function reindexReservationItems() {
    const container = document.getElementById('product_reservation_container');
    if (!container) return;
    
    const items = container.querySelector('.reservation_item');
    
    Array.from(items).forEach((item, index) => {
        // Actualizar el atributo data-index
        item.setAttribute('data-index', index);
        
        // Reindexar todos los campos dentro del item
        const fields = item.querySelectorAll('[name*="["]');
        fields.forEach(field => {
            const name = field.getAttribute('name');
            const newName = name.replace(/\[\d+\]/, `[${index}]`);
            field.setAttribute('name', newName);
            field.setAttribute('id', newName.replace(/[\[\]]/g, '_'));
        });
    });
    
    // Actualizar la visibilidad de los campos de cliente
    toggleCustomerField();
}

function addReservationItem() {
    const container = document.getElementById('product_reservation_container');
    if (!container) return;

    // Usamos timestamp como índice temporal (será corregido por reindexación)
    const tempIndex = Date.now();
    const customerField = document.querySelector('[name="id_customer[0]"]');
    if (!customerField) return;

    // Crear el nuevo ítem de reserva
    const newReservation = document.createElement('div');
    newReservation.classList.add('reservation_item', 'mb-3');
    newReservation.setAttribute('data-index', tempIndex);
    
    // Estilos 
    Object.assign(newReservation.style, {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'flex-start',
        justifyContent: 'center',
        backgroundColor: '#f0f0f0',
        boxShadow: '3px 3px 5px rgba(0,0,0,0.2)',
        borderRadius: '8px',
        padding: '10px',
        marginBottom: '10px',
        position: 'relative',
        opacity: '0',
        transform: 'translateY(-10px)',
        transition: 'opacity 0.3s ease-out, transform 0.3s ease-out'
    });

    // Botón de eliminar
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'remove_reservation btn btn-danger btn-sm';
    Object.assign(removeButton.style, {
        padding: '2px 6px',
        fontSize: '14px',
        borderRadius: '50%',
        boxShadow: '2px 2px 4px rgba(0,0,0,0.2)'
    });
    removeButton.innerHTML = '×';
    removeButton.addEventListener('click', function() {
        newReservation.remove();
        reindexReservationItems();
    });

    // Contenedor del botón
    const removeButtonContainer = document.createElement('div');
    Object.assign(removeButtonContainer.style, {
        position: 'absolute',
        top: '5px',
        right: '5px'
    });
    removeButtonContainer.appendChild(removeButton);
    newReservation.appendChild(removeButtonContainer);

    // Select de cliente (hidden para items adicionales)
    const customerContainer = document.createElement('div');
    customerContainer.className = 'mb-3';
    customerContainer.style.width = '100%';
    
    const newCustomerSelect = document.createElement('select');
    newCustomerSelect.name = `id_customer[${tempIndex}]`;
    newCustomerSelect.id = `id_customer_${tempIndex}`;
    newCustomerSelect.className = 'form-select';
    newCustomerSelect.style.width = '100%';
    newCustomerSelect.required = true;

    // Clonar opciones del primer select
    const originalCustomerSelect = document.querySelector('[name="id_customer[0]"]');
    if (originalCustomerSelect) {
        Array.from(originalCustomerSelect.options).forEach(option => {
            newCustomerSelect.appendChild(option.cloneNode(true));
        });
    }

    customerContainer.innerHTML = '<label for="id_customer" class="form-label">Cliente:</label>';
    customerContainer.appendChild(newCustomerSelect);

    // Select de producto
    const productContainer = document.createElement('div');
    productContainer.className = 'mb-3';
    productContainer.style.width = '100%';
    
    const newProductSelect = document.createElement('select');
    newProductSelect.name = `product_id[${tempIndex}]`;
    newProductSelect.id = `product_id_${tempIndex}`;
    newProductSelect.className = 'form-select';
    newProductSelect.style.width = '100%';
    newProductSelect.required = true;
    newProductSelect.addEventListener('change', function() {
        updateReference(this);
    });

    // Clonar opciones del primer select de productos
    const originalProductSelect = document.querySelector('[name="product_id[0]"]');
    if (originalProductSelect) {
        Array.from(originalProductSelect.options).forEach(option => {
            newProductSelect.appendChild(option.cloneNode(true));
        });
    }

    productContainer.innerHTML = '<label for="product_id" class="form-label">Producto:</label>';
    productContainer.appendChild(newProductSelect);

    // Input de cantidad
    const quantityContainer = document.createElement('div');
    quantityContainer.className = 'mb-3';
    quantityContainer.style.width = '100%';
    
    const quantityInput = document.createElement('input');
    quantityInput.type = 'number';
    quantityInput.name = `quantity[${tempIndex}]`;
    quantityInput.id = `quantity_${tempIndex}`;
    quantityInput.className = 'form-control';
    quantityInput.min = 1;
    quantityInput.value = 1;
    quantityInput.required = true;
    quantityInput.style.width = '50%';

    quantityContainer.innerHTML = '<label for="quantity" class="form-label">Cantidad:</label>';
    quantityContainer.appendChild(quantityInput);

    // Campos ocultos
    const referenceInput = document.createElement('input');
    referenceInput.type = 'hidden';
    referenceInput.name = `reference[${tempIndex}]`;
    referenceInput.id = `reference_${tempIndex}`;
    referenceInput.value = '';

    const idProductAttributeInput = document.createElement('input');
    idProductAttributeInput.type = 'hidden';
    idProductAttributeInput.name = `id_product_attribute[${tempIndex}]`;
    idProductAttributeInput.id = `id_product_attribute_${tempIndex}`;
    idProductAttributeInput.value = '';

    // Agregar todos los elementos al ítem
    newReservation.append(
        customerContainer,
        productContainer,
        quantityContainer,
        referenceInput,
        idProductAttributeInput
    );

    // Agregar al contenedor principal
    container.appendChild(newReservation);

    // Animación
    setTimeout(() => {
        newReservation.style.opacity = '1';
        newReservation.style.transform = 'translateY(0)';
    }, 10);

    // Actualizar campos de cliente
    toggleCustomerField();

    // Inicializar Choices.js si es necesario
    if (typeof initializeDynamicChoices === 'function') {
        initializeDynamicChoices(newReservation);
    }
}



function getProductsFromForm() {
    let products = [];
    document.querySelectorAll('.reservation_item').forEach((form, index) => {
        let product = {
            product_id: form.querySelector('select[name="product_id[' + index + ']"]').value,
            quantity: form.querySelector('input[name="quantity[' + index + ']"]').value,
            id_customer: form.querySelector('select[name="id_customer[' + index + ']"]').value,
            reference: form.querySelector('input[name="reference[' + index + ']"]').value,
            id_product_attribute: form.querySelector('input[name="id_product_attribute[' + index + ']"]').value
        };
        products.push(product);
    });

    return products;
}

// Función para resetear el formulario
function resetForm() {
    const firstReservation = document.querySelector('.reservation_item[data-index="0"]');
    if (firstReservation) {
        firstReservation.querySelector('select[name="product_id[0]"]').value = '';
        firstReservation.querySelector('input[name="quantity[0]"]').value = 1;
        firstReservation.querySelector('select[name="id_customer[0]"]').value = '';
        firstReservation.querySelector('input[name="reference[0]"]').value = '';
        firstReservation.querySelector('input[name="id_product_attribute[0]"]').value = '';
    }

    const reservationContainer = document.getElementById('product_reservation_container');
    const extraReservations = reservationContainer.querySelectorAll('.reservation_item:not([data-index="0"])');
    extraReservations.forEach(reservation => reservation.remove());
}
