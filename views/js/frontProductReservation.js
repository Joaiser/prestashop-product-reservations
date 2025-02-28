document.addEventListener('DOMContentLoaded', function () {
    console.log('Script frontProductReservation.js cargado correctamente.');

    // Seleccionar todos los botones de reserva
    document.querySelectorAll('.reservation-toggle').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('Botón de reserva clickeado para el producto ID:', this.dataset.productId);

            let formContainer = this.closest('.product-reservation-widget').querySelector('.reservation-form-container');
            if (formContainer) {
                formContainer.style.display = formContainer.style.display === 'none' || formContainer.style.display === '' ? 'block' : 'none';
                console.log('Formulario de reserva ' + (formContainer.style.display === 'none' ? 'ocultado' : 'mostrado') + '.');
            } else {
                console.error('No se encontró el formulario de reserva para este producto.');
            }
        });
    });

    // Manejar el envío del formulario mediante AJAX
    document.querySelectorAll('.reservation-form-content').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Formulario de reserva enviado para el producto ID:', this.querySelector('input[name="product_id"]').value);

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
                console.log('Respuesta del servidor:', data);

                messageContainer.classList.remove('alert-success', 'alert-danger'); // Limpiar clases previas

                if (data.success) {
                    messageContainer.textContent = 'Reserva realizada con éxito.';
                    messageContainer.classList.add('alert-success');
                    console.log('Reserva realizada con éxito.');

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
