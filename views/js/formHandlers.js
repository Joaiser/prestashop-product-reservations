import { areFieldsComplete, showError, showSuccess } from './utils.js';
import { sendReservation } from './apiHandlers.js';
import { toggleCustomerField, updateReference } from './uiUpdates.js';
import { initializeDynamicChoices } from './choice.js';

export function initializeFormHandlers(reservationForm) {
    // Manejar el envío del formulario
    reservationForm.addEventListener('submit', function (e) {
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
            if (!areFieldsComplete()) {
                showError('Por favor, completa todos los campos antes de añadir otro producto.');
                return;
            }

            addReservationItem();
        });
    }
}

function addReservationItem() {
    const container = document.getElementById('product_reservation_container');
    if (!container) {
        console.error('No se encontró el contenedor de reservas.');
        return;
    }

    const customerField = document.querySelector('[name="id_customer[0]"]');
    if (!customerField) {
        console.error('No se encontró el campo de cliente.');
        return;
    }

    const index = container.getElementsByClassName('reservation_item').length;

    // Crear un nuevo ítem de reserva desde cero
    const newReservation = document.createElement('div');
    newReservation.classList.add('reservation_item', 'mb-3');
    newReservation.setAttribute('data-index', index);
    newReservation.style.display = 'flex';
    newReservation.style.flexDirection = 'column';
    newReservation.style.alignItems = 'flex-start';
    newReservation.style.justifyContent = 'center';

    // Crear el select de cliente
    const newCustomerSelect = document.createElement('select');
    newCustomerSelect.setAttribute('name', `id_customer[${index}]`);
    newCustomerSelect.setAttribute('id', `id_customer_${index}`);
    newCustomerSelect.classList.add('form-select');
    newCustomerSelect.style.width = '100%';
    newCustomerSelect.required = true;

    // Copiar las opciones del select de cliente original
    const originalCustomerSelect = document.querySelector('[name="id_customer[0]"]');
    if (originalCustomerSelect) {
        const options = originalCustomerSelect.querySelectorAll('option');
        options.forEach(option => {
            const clonedOption = option.cloneNode(true);
            newCustomerSelect.appendChild(clonedOption);
        });
    }

    // Crear el contenedor para el select de cliente
    const customerContainer = document.createElement('div');
    customerContainer.classList.add('mb-3');
    customerContainer.style.width = '100%';
    customerContainer.setAttribute('role', 'dialog');
    customerContainer.innerHTML = '<label for="id_customer" class="form-label">Cliente:</label>';
    customerContainer.appendChild(newCustomerSelect);

    // Crear el select de productos
    const newProductSelect = document.createElement('select');
    newProductSelect.setAttribute('name', `product_id[${index}]`);
    newProductSelect.setAttribute('id', `product_id_${index}`);
    newProductSelect.classList.add('form-select');
    newProductSelect.style.width = '100%';
    newProductSelect.required = true;

    // Copiar las opciones del select de productos original
    const originalProductSelect = document.querySelector('[name="product_id[0]"]');
    if (originalProductSelect) {
        const options = originalProductSelect.querySelectorAll('option');
        options.forEach(option => {
            const clonedOption = option.cloneNode(true);
            newProductSelect.appendChild(clonedOption);
        });
    }

    // Configurar el evento `change` en el nuevo select de productos
    newProductSelect.addEventListener('change', function () {
        updateReference(this);
    });

    // Crear el contenedor para el select de productos
    const productContainer = document.createElement('div');
    productContainer.classList.add('mb-3');
    productContainer.style.width = '100%';
    productContainer.innerHTML = '<label for="product_id" class="form-label">Producto:</label>';
    productContainer.appendChild(newProductSelect);

    // Crear el botón de eliminar
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.classList.add('remove_reservation', 'btn', 'btn-danger', 'btn-sm');
    removeButton.style.display = 'inline-block';
    removeButton.innerHTML = '×';
    removeButton.addEventListener('click', function () {
        newReservation.remove();
        toggleCustomerField();
    });
    productContainer.appendChild(removeButton);

    // Crear el input de cantidad
    const quantityInput = document.createElement('input');
    quantityInput.type = 'number';
    quantityInput.setAttribute('name', `quantity[${index}]`);
    quantityInput.setAttribute('id', `quantity_${index}`);
    quantityInput.classList.add('form-control');
    quantityInput.min = 1;
    quantityInput.value = 1;
    quantityInput.required = true;
    quantityInput.style.width = '50%';

    // Crear el contenedor para el input de cantidad
    const quantityContainer = document.createElement('div');
    quantityContainer.classList.add('mb-3');
    quantityContainer.style.width = '100%';
    quantityContainer.innerHTML = '<label for="quantity" class="form-label">Cantidad:</label>';
    quantityContainer.appendChild(quantityInput);

    // Crear los campos ocultos
    const referenceInput = document.createElement('input');
    referenceInput.type = 'hidden';
    referenceInput.setAttribute('name', `reference[${index}]`);
    referenceInput.setAttribute('id', `reference_${index}`);
    referenceInput.value = '';

    const idProductAttributeInput = document.createElement('input');
    idProductAttributeInput.type = 'hidden';
    idProductAttributeInput.setAttribute('name', `id_product_attribute[${index}]`);
    idProductAttributeInput.setAttribute('id', `id_product_attribute_${index}`);
    idProductAttributeInput.value = '';

    // Añadir todos los elementos al nuevo ítem
    newReservation.appendChild(customerContainer);
    newReservation.appendChild(productContainer);
    newReservation.appendChild(quantityContainer);
    newReservation.appendChild(referenceInput);
    newReservation.appendChild(idProductAttributeInput);

    // Añadir el nuevo ítem al contenedor
    container.appendChild(newReservation);

    // Deshabilitar el select de cliente si ya hay más de un ítem
    toggleCustomerField();

    // Inicializar Choices.js en el nuevo select de productos
    initializeDynamicChoices(newReservation);
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

    console.log("Datos a enviar:", products); // Verificar los datos antes de enviar
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
