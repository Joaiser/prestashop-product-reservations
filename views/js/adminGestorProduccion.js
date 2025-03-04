document.addEventListener("DOMContentLoaded", function () {
    console.log("El script ha cargado correctamente");

    const checkboxes = document.querySelectorAll(".producto-checkbox");
    const btnAplicar = document.getElementById("btn-aplicar");
    let urlWithToken = `${ajaxUrl}&token=${csrfToken}`;

    // Mostrar u ocultar el botón de aplicar cuando se seleccionan productos
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("change", function () {
            const anyChecked = Array.from(checkboxes).some(c => c.checked);
            btnAplicar.style.display = anyChecked ? "flex" : "none";
            btnAplicar.style.justifyContent = anyChecked ? "center" : "";
            btnAplicar.style.alignItems = anyChecked ? "center" : "";
        });
    });

    // Evento para el formulario general
    document.getElementById("productosForm")?.addEventListener("submit", function (e) {
        e.preventDefault();
        const selectedProducts = Array.from(checkboxes)
            .filter(c => c.checked)
            .map(c => ({
                id_product: c.value,
                id_product_attribute: c.dataset.idProductAttribute || 0,
                reference: c.dataset.reference || null
            }));

        if (selectedProducts.length > 0) {
            habilitarReserva(selectedProducts);
        } else {
            alert("Por favor, selecciona al menos un producto.");
        }
    });

    // Función para habilitar reservas
    function habilitarReserva(products) {
        if (products.length === 0) {
            alert("No se encontraron productos válidos para habilitar reservas.");
            return;
        }

        fetch(urlWithToken, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                products: JSON.stringify(products),
                submit: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("✅ Reservas habilitadas correctamente");
                window.location.reload();
            } else {
                alert("❌ Error al habilitar las reservas: " + (data.error_message || "Desconocido"));
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("❌ Hubo un error al procesar la solicitud.");
        });
    }

    // Función para borrar una reserva
    function borrarReserva(idReservation) {
        const url = `${ajaxUrl}&delete_reservation=${idReservation}&token=${csrfToken}`;
        if (confirm(`¿Seguro que deseas borrar la reserva ID ${idReservation}?`)) {
            fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("✅ Reserva eliminada correctamente.");
                    window.location.reload();
                } else {
                    alert("❌ Error al borrar la reserva: " + (data.error_message || "Desconocido"));
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("❌ Hubo un error al procesar la solicitud.");
            });
        }
    }

    // Agregar evento a los botones de borrar reserva
    document.querySelectorAll('.btn-borrar-reserva').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const idReservation = this.closest('form').id.split('-')[3];
            borrarReserva(idReservation);
        });
    });

   // Función para deshabilitar un producto
function deshabilitarProducto(event) {
    console.log("Botón de deshabilitar presionado");

    // Obtener el botón que disparó el evento
    const button = event.target;
    const productId = button.dataset.id;

    // Crear URL específica para deshabilitar producto
    const url = `${ajaxUrl}&deshabilitarProducto=${productId}&token=${csrfToken}`;
    console.log("URL de deshabilitar producto:", url); // Verificar la URL

    // Confirmar con el usuario antes de proceder
    if (!confirm(`¿Seguro que deseas deshabilitar el producto con 🆔:${productId}?`)) {
        return; // Si el usuario cancela, no hacer nada
    }

    // Usar fetch con método GET (como en borrarReserva)
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error("Error en la respuesta del servidor");
            }
            return response.json();
        })
        .then(data => {
            console.log("Respuesta del servidor:", data); // Verificar la respuesta
            if (data.success) {
                alert("✅ Producto deshabilitado correctamente.");
                window.location.reload(); // Recargar la página para reflejar los cambios
            } else {
                alert("❌ Error al deshabilitar: " + (data.error_message || "Desconocido"));
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("❌ Hubo un error al procesar la solicitud.");
        });
}

// Prevenir el envío del formulario y manejar el evento de deshabilitar
document.querySelectorAll('.form-deshabilitar-producto').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevenir el envío del formulario
        const button = this.querySelector('.btn-deshabilitar');
        if (button) {
            deshabilitarProducto({ target: button }); // Llamar a la función deshabilitarProducto
        }
    });
});

});
