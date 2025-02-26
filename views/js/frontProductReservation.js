document.addEventListener('DOMContentLoaded', function () {
    console.log('Script frontProductReservation.js cargado correctamente.');

    // Mostrar/ocultar el formulario al hacer clic en el botón
    var toggleButton = document.getElementById('reservation-toggle');
    if (toggleButton) {
        toggleButton.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('Botón de reserva clickeado.');

            var form = document.getElementById('reservation-form');
            if (form) {
                form.style.display = (form.style.display === 'none') ? 'block' : 'none';
                console.log('Formulario de reserva ' + (form.style.display === 'none' ? 'ocultado' : 'mostrado') + '.');
            } else {
                console.error('El formulario de reserva no se encontró en el DOM.');
            }
        });
    } else {
        console.error('El botón de reserva no se encontró en el DOM.');
    }

    // Manejar el envío del formulario mediante AJAX
    var reservationForm = document.getElementById('reservation-form-content');
    if (reservationForm) {
        reservationForm.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Formulario de reserva enviado.');

            // Obtener los datos del formulario
            var formData = new FormData(this);
            console.log('Datos del formulario:', Object.fromEntries(formData.entries()));

            // Enviar los datos mediante AJAX
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
                    alert('Reserva realizada con éxito.');
                    console.log('Reserva realizada con éxito.');
                    document.getElementById('reservation-form').style.display = 'none'; // Ocultar el formulario
                } else {
                    alert('Error: ' + data.message);
                    console.error('Error en la reserva:', data.message);
                }
            })
            .catch(error => {
                console.error('Error en la solicitud AJAX:', error);
                alert('Ocurrió un error al procesar la solicitud.');
            });
        });
    } else {
        console.error('El formulario de reserva no se encontró en el DOM.');
    }
});