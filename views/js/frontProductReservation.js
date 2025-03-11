document.addEventListener('DOMContentLoaded', function () {
    const reservationForm = document.querySelector('#reservation_form');

    if (reservationForm) {
        // Manejar el envío del formulario mediante AJAX
        reservationForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Obtener todos los formularios de reserva (incluso los nuevos agregados)
            let formData = new FormData();
            let reservationForms = document.querySelectorAll('.reservation_item');  // Seleccionar todos los formularios de reserva

            // Recorremos los formularios de reserva para agregar sus datos a formData
            reservationForms.forEach((form, index) => {
                let formFields = form.querySelectorAll('select, input');
                formFields.forEach(field => {
                    formData.append(field.name, field.value);
                });
            });

            let actionUrl = this.action;  // URL del controlador
            if (!actionUrl) {
                console.error('No se encontró la URL de acción para el formulario.');
                return;
            }

            let messageContainer = this.closest('.product-reservation-widget').querySelector('.reservation-message');
            messageContainer.textContent = '';  // Limpiar mensaje previo
            messageContainer.style.display = 'none';

            // Enviar la solicitud AJAX
            fetch(actionUrl, {
                method: 'POST',
                body: formData  // Enviar los datos del formulario
            })
            .then(response => response.json())
            .then(data => {
                messageContainer.classList.remove('alert-success', 'alert-danger'); 

                if (data.success) {
                    messageContainer.textContent = 'Reservas realizadas con éxito.';
                    messageContainer.classList.add('alert-success');

                    // Ocultar mensaje y formulario tras 2 segundos
                    setTimeout(() => {
                        messageContainer.style.display = 'none';
                        this.closest('.reservation-form-container').style.display = 'none';
                    }, 2000);
                } else {
                    messageContainer.textContent = 'Error: ' + (data.message || 'Error desconocido.');
                    messageContainer.classList.add('alert-danger');
                    console.error('Error en la reserva:', data.message);
                }

                messageContainer.style.display = 'block';
            })
            .catch(error => {
                console.error('Error en la solicitud AJAX:', error);
                messageContainer.textContent = 'Ocurrió un error al procesar la solicitud.';
                messageContainer.classList.add('alert-danger');
                messageContainer.style.display = 'block';
            });
        });
    } else {
        console.error("El formulario con id 'reservation_form' no se encontró.");
    }

    // Manejar el botón de añadir más reservas
    document.getElementById('add_more_reservations').addEventListener('click', function() {
        // Obtener el contenedor de las reservas
        var container = document.getElementById('product_reservation_container');
        
        // Obtener el valor del cliente del primer formulario
        let customerField = document.querySelector('[name="id_customer[0]"]');
        if (!customerField) {
            console.error('El campo "id_customer[0]" no se encontró.');
            return;
        }

        // Obtener el índice de la última reserva
        var index = container.getElementsByClassName('reservation_item').length;

        // Clonar el primer formulario
        var newReservation = container.getElementsByClassName('reservation_item')[0].cloneNode(true);

        // Cambiar los IDs y nombres para el nuevo formulario
        newReservation.setAttribute('data-index', index);
        newReservation.querySelector('select[name="product_id[0]"]').setAttribute('name', 'product_id[' + index + ']');
        newReservation.querySelector('input[name="quantity[0]"]').setAttribute('name', 'quantity[' + index + ']');
        newReservation.querySelector('select[name="id_customer[0]"]').setAttribute('name', 'id_customer[' + index + ']');
        newReservation.querySelector('input[name="reference[0]"]').setAttribute('name', 'reference[' + index + ']');
        newReservation.querySelector('input[name="id_product_attribute[0]"]').setAttribute('name', 'id_product_attribute[' + index + ']');

        // Mantener el mismo cliente en los formularios clonados
        let currentCustomerId = customerField.value;
        newReservation.querySelector('select[name="id_customer[' + index + ']"]').value = currentCustomerId;

        // Deshabilitar el campo "id_customer" en los formularios nuevos
        newReservation.querySelector('select[name="id_customer[' + index + ']"]').disabled = true;

        // Limpiar los valores de los nuevos campos (excepto el cliente)
        newReservation.querySelector('select[name="product_id[' + index + ']"]').value = '';
        newReservation.querySelector('input[name="quantity[' + index + ']"]').value = 1;

        // Mostrar el botón de eliminar (X) solo en los campos nuevos
        const removeButton = newReservation.querySelector('.remove_reservation');
        removeButton.style.display = 'inline-block';

        // Agregar el evento para eliminar la reserva
        removeButton.addEventListener('click', function () {
            newReservation.remove();  // Eliminar el campo de la reserva
        });

        // Añadir el nuevo formulario al contenedor
        container.appendChild(newReservation);
    });
});
