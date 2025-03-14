import { areFieldsComplete, showError } from './utils.js';
import { sendReservation } from './apiHandlers.js';
import { toggleCustomerField, updateReference } from './uiUpdates.js';

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
                    resetForm();
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
        });
    } else {
        console.error('El botón "add_more_reservations" no fue encontrado en el DOM.');
    }
}

// Función para añadir un nuevo ítem de reserva
function addReservationItem() {
    const container = document.getElementById('product_reservation_container');
    const customerField = document.querySelector('[name="id_customer[0]"]');
    if (!customerField) return;

    const index = container.getElementsByClassName('reservation_item').length;
    const newReservation = container.getElementsByClassName('reservation_item')[0].cloneNode(true);

    // Actualizar los índices de los campos
    newReservation.setAttribute('data-index', index);
    newReservation.querySelector('select[name="product_id[0]"]').setAttribute('name', 'product_id[' + index + ']');
    newReservation.querySelector('input[name="quantity[0]"]').setAttribute('name', 'quantity[' + index + ']');
    newReservation.querySelector('select[name="id_customer[0]"]').setAttribute('name', 'id_customer[' + index + ']');
    newReservation.querySelector('input[name="reference[0]"]').setAttribute('name', 'reference[' + index + ']');
    newReservation.querySelector('input[name="id_product_attribute[0]"]').setAttribute('name', 'id_product_attribute[' + index + ']');

    // Configurar el evento `change` en el nuevo `<select>` de productos
    const newProductSelect = newReservation.querySelector('select[name^="product_id"]');
    newProductSelect.addEventListener('change', function () {
        updateReference(this);
    });

    // Copiar el valor del cliente del primer ítem
    const currentCustomerId = customerField.value;
    newReservation.querySelector('select[name="id_customer[' + index + ']"]').value = currentCustomerId;

    // Limpiar los campos del nuevo ítem
    newReservation.querySelector('select[name="product_id[' + index + ']"]').value = '';
    newReservation.querySelector('input[name="quantity[' + index + ']"]').value = 1;

    // Mostrar el botón de eliminar
    const removeButton = newReservation.querySelector('.remove_reservation');
    removeButton.style.display = 'inline-block';
    removeButton.addEventListener('click', function () {
        newReservation.remove();
        toggleCustomerField();
    });

    // Añadir el nuevo ítem al contenedor
    container.appendChild(newReservation);
    toggleCustomerField();
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
        console.log('Producto:', product); // Verificar los datos en la consola
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