<<<<<<< HEAD
document.addEventListener('DOMContentLoaded', function () {

    // Seleccionar todos los botones de reserva
    document.querySelectorAll('.reservation-toggle').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            let formContainer = this.closest('.product-reservation-widget').querySelector('.reservation-form-container');
            if (formContainer) {
                formContainer.style.display = formContainer.style.display === 'none' || formContainer.style.display === '' ? 'block' : 'none';
            } 
        });
    });

    // Manejar el envío del formulario mediante AJAX
    document.querySelectorAll('.reservation-form-content').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            let formData = new FormData(this);
            let params = new URLSearchParams(formData); // Convertimos FormData a URLSearchParams
            let actionUrl = this.action; // URL del controlador

            if (!actionUrl) {
                console.error('No se encontró la URL de acción para el formulario.');
                return;
            }

            let messageContainer = this.closest('.product-reservation-widget').querySelector('.reservation-message');
            messageContainer.textContent = ''; // Limpiar mensaje previo
            messageContainer.style.display = 'none';

            // Enviar la solicitud AJAX
            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {

                messageContainer.classList.remove('alert-success', 'alert-danger'); 

                if (data.success) {
                    messageContainer.textContent = 'Reserva realizada con éxito.';
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
    });
});
=======
document.addEventListener('DOMContentLoaded', function () {

    // Seleccionar todos los botones de reserva
    document.querySelectorAll('.reservation-toggle').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            let formContainer = this.closest('.product-reservation-widget').querySelector('.reservation-form-container');
            if (formContainer) {
                formContainer.style.display = formContainer.style.display === 'none' || formContainer.style.display === '' ? 'block' : 'none';
            } 
        });
    });

    // Manejar el envío del formulario mediante AJAX
    document.querySelectorAll('.reservation-form-content').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            let formData = new FormData(this);
            let params = new URLSearchParams(formData); // Convertimos FormData a URLSearchParams
            let actionUrl = this.action; // URL del controlador

            if (!actionUrl) {
                console.error('No se encontró la URL de acción para el formulario.');
                return;
            }

            let messageContainer = this.closest('.product-reservation-widget').querySelector('.reservation-message');
            messageContainer.textContent = ''; // Limpiar mensaje previo
            messageContainer.style.display = 'none';

            // Enviar la solicitud AJAX
            fetch(actionUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {

                messageContainer.classList.remove('alert-success', 'alert-danger'); 

                if (data.success) {
                    messageContainer.textContent = 'Reserva realizada con éxito.';
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
    });
});
>>>>>>> ddc47cb87cfba50a7542ddcdbefcaff3a2d1e0a7
