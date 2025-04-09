import { areFieldsComplete, showError, showSuccess } from './utils.js';
import { sendReservation } from './apiHandlers.js';
import { toggleCustomerField, updateReference, reservationSystem } from './uiUpdates.js';
import { initializeDynamicChoices } from './choice.js';

function getProductsFromForm() {
    return Array.from(document.querySelectorAll('.reservation_item')).map(form => {
        const index = form.getAttribute('data-index');
        return {
            product_id: form.querySelector(`select[name="product_id[${index}]"]`)?.value || '',
            quantity: form.querySelector(`input[name="quantity[${index}]"]`)?.value || '1',
            id_customer: form.querySelector(`select[name="id_customer[${index}]"]`)?.value || '',
            reference: form.querySelector(`input[name="reference[${index}]"]`)?.value || '',
            id_product_attribute: form.querySelector(`input[name="id_product_attribute[${index}]"]`)?.value || '0'
        };
    });
}

export function initializeFormHandlers(reservationForm) {
    // Manejar el envío del formulario
    reservationForm.addEventListener('submit', function(e) {
        e.preventDefault();

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
                    setTimeout(() => window.location.reload(), 3000);
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
        addMoreReservationsButton.addEventListener('click', function() {
            if (!areFieldsComplete()) {
                showError('Por favor, completa todos los campos antes de añadir otro producto.');
                return;
            }

            addReservationItem();
            reindexReservationItems();
        });
    }
}

function reindexReservationItems() {
    const container = document.getElementById('product_reservation_container');
    if (!container) return;
    
    const items = container.querySelectorAll('.reservation_item');
    
    items.forEach((item, index) => {
        item.setAttribute('data-index', index);

        const numberLabel = item.querySelector('.reservation_number');
        if(numberLabel){
            numberLabel.innerText = `#${index + 1}`;
        }
        
        item.querySelectorAll('[name*="["]').forEach(field => {
            const name = field.getAttribute('name');
            field.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
        });
    });
    
    toggleCustomerField();
}

function addReservationItem() {
    const container = document.getElementById('product_reservation_container');
    if (!container) return;

    const tempIndex = Date.now();
    const customerField = document.querySelector('[name="id_customer[0]"]');
    if (!customerField) return;

    // Crear el nuevo ítem de reserva
    const newReservation = document.createElement('div');
    newReservation.className = 'reservation_item mb-3';
    newReservation.setAttribute('data-index', tempIndex);
    newReservation.style.cssText = `
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        background-color: #f0f0f0;
        box-shadow: 3px 3px 5px rgba(0,0,0,0.2);
        border-radius: 8px;
        padding: 21px;
        margin-bottom: 10px;
        position: relative;
        opacity: 0;
        transform: translateY(-10px);
        transition: opacity 0.3s ease-out, transform 0.3s ease-out;
    `;

    //añadir el numero de contenedor (número visual para guiar al usuario)
    const numerLabel = document.createElement('div');
    numerLabel.className = 'reservation_number';
    numerLabel.innerText = `#${container.querySelectorAll('.reservation_item').length + 1}`; 
    numerLabel.style.cssText = `
        position: absolute;
        top: 5px;
        left: 5px;
        font-weight: bold;
        color: #333;`;
    newReservation.appendChild(numerLabel);

    // Botón de eliminar
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'remove_reservation btn btn-danger btn-sm';
    removeButton.innerHTML = '×';
    removeButton.addEventListener('click', () => {
        newReservation.style.opacity = '0';
        newReservation.style.transform = 'translateY(-10px)';
        
        setTimeout(() => {
            newReservation.remove();
            reindexReservationItems();
        }, 300); // Coincide con la duración de la transición CSS
    });

    const removeButtonContainer = document.createElement('div');
    removeButtonContainer.style.cssText = 'position: absolute; top: 5px; right: 5px;';
    removeButtonContainer.appendChild(removeButton);
    newReservation.appendChild(removeButtonContainer);

    // Select de cliente
    const customerContainer = document.createElement('div');
    customerContainer.className = 'mb-3';
    
    const newCustomerSelect = document.createElement('select');
    newCustomerSelect.name = `id_customer[${tempIndex}]`;
    newCustomerSelect.className = 'form-select';
    newCustomerSelect.required = true;

    // Clonar opciones del select principal
    const originalCustomerSelect = document.querySelector('[name="id_customer[0]"]');
    if (originalCustomerSelect) {
        Array.from(originalCustomerSelect.options).forEach(option => {
            newCustomerSelect.appendChild(option.cloneNode(true));
        });
        if (reservationSystem.mainCustomer) {
            newCustomerSelect.value = reservationSystem.mainCustomer;
        }
    }

    customerContainer.innerHTML = '<label class="form-label">Cliente:</label>';
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

    // Solo añadir el placeholder inicial
    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = 'Buscar producto...';
    placeholderOption.disabled = true;
    placeholderOption.selected = true;
    newProductSelect.appendChild(placeholderOption);

    productContainer.innerHTML = '<label for="product_id" class="form-label">Producto:</label>';
    productContainer.appendChild(newProductSelect);

    // Input de cantidad
    const quantityInput = document.createElement('input');
    quantityInput.type = 'number';
    quantityInput.name = `quantity[${tempIndex}]`;
    quantityInput.className = 'form-control';
    quantityInput.min = 1;
    quantityInput.value = 1;
    quantityInput.required = true;

    const quantityContainer = document.createElement('div');
    quantityContainer.className = 'mb-3';
    quantityContainer.innerHTML = '<label class="form-label">Cantidad:</label>';
    quantityContainer.appendChild(quantityInput);

    // Campos ocultos
    const referenceInput = document.createElement('input');
    referenceInput.type = 'hidden';
    referenceInput.name = `reference[${tempIndex}]`;
    referenceInput.value = '';

    const idProductAttributeInput = document.createElement('input');
    idProductAttributeInput.type = 'hidden';
    idProductAttributeInput.name = `id_product_attribute[${tempIndex}]`;
    idProductAttributeInput.value = '0';

    // Agregar todos los elementos
    newReservation.append(
        removeButtonContainer,
        customerContainer,
        productContainer,
        quantityContainer,
        referenceInput,
        idProductAttributeInput
    );

    container.appendChild(newReservation);

    // Animación e inicialización
    setTimeout(() => {
        newReservation.style.opacity = '1';
        newReservation.style.transform = 'translateY(0)';
    }, 10);

    toggleCustomerField();
    initializeDynamicChoices(newReservation);
    reindexReservationItems();
}

function resetForm() {
    const firstReservation = document.querySelector('.reservation_item[data-index="0"]');
    if (firstReservation) {
        firstReservation.querySelector('select[name="product_id[0]"]').value = '';
        firstReservation.querySelector('input[name="quantity[0]"]').value = 1;
    }

    const reservationContainer = document.getElementById('product_reservation_container');
    const extraReservations = reservationContainer?.querySelectorAll('.reservation_item:not([data-index="0"])') || [];
    
    extraReservations.forEach(reservation => {
        reservation.style.opacity = '0';
        reservation.style.transform = 'translateY(-10px)';
        setTimeout(() => reservation.remove(), 300);
    });
    
    // Reindexar después de eliminar
    setTimeout(() => reindexReservationItems(), 350);
}