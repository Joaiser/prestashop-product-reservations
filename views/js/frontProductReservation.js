document.addEventListener('DOMContentLoaded', function () {
    console.log('Script frontProductReservation.js cargado correctamente.');

    // Seleccionar todos los botones de reserva
    let toggleButtons = document.querySelectorAll('.reservation-toggle');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('Botón de reserva clickeado para el producto ID:', this.dataset.productId);

            // Buscar el formulario relacionado al botón
            let formContainer = this.closest('.product-reservation-widget').querySelector('.reservation-form-container');
            if (formContainer) {
                formContainer.style.display = (formContainer.style.display === 'none' || formContainer.style.display === '') ? 'block' : 'none';
                console.log('Formulario de reserva ' + (formContainer.style.display === 'none' ? 'ocultado' : 'mostrado') + '.');
            } else {
                console.error('No se encontró el formulario de reserva para este producto.');
            }
        });
    });

    // Manejar el envío del formulario mediante AJAX
    let reservationForms = document.querySelectorAll('.reservation-form-content');
    reservationForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Formulario de reserva enviado para el producto ID:', this.querySelector('input[name="product_id"]').value);

            let formData = new FormData(this);
            console.log('Datos del formulario:', Object.fromEntries(formData.entries()));

            let messageContainer = this.closest('.product-reservation-widget').querySelector('.reservation-message');

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Respuesta del servidor:', data);
                if (data.success) {
                    messageContainer.textContent = 'Reserva realizada con éxito.';
                    messageContainer.classList.add('alert-success');
                    messageContainer.classList.remove('alert-danger');
                    messageContainer.style.display = 'block';
                    console.log('Reserva realizada con éxito.');

                    setTimeout(() => {
                        messageContainer.style.display = 'none';
                        this.closest('.reservation-form-container').style.display = 'none';
                    }, 2000); // Ocultar mensaje y formulario tras 2 segundos
                } else {
                    messageContainer.textContent = 'Error: ' + data.message;
                    messageContainer.classList.add('alert-danger');
                    messageContainer.classList.remove('alert-success');
                    messageContainer.style.display = 'block';
                    console.error('Error en la reserva:', data.message);
                }
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
