export function init(ajaxUrl, csrfToken) {
    // Función para habilitar reservas
    function habilitarReserva(products) {
        if (products.length === 0) {
            alert("No se encontraron productos válidos para habilitar reservas.");
            return;
        }

        fetch(ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                products: JSON.stringify(products),
                submit: true,
                token: csrfToken
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

    // Función para borrar reserva
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

    // Función para deshabilitar producto
    function deshabilitarProducto(event) {
        const button = event.target;
        const productId = button.dataset.id;
        const url = `${ajaxUrl}&deshabilitarProducto=${productId}&token=${csrfToken}`;

        if (!confirm(`¿Seguro que deseas deshabilitar el producto con 🆔:${productId}?`)) return;

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error("Error en la respuesta del servidor");
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert("✅ Producto deshabilitado correctamente.");
                    window.location.reload();
                } else {
                    alert("❌ Error al deshabilitar: " + (data.error_message || "Desconocido"));
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("❌ Hubo un error al procesar la solicitud.");
            });
    }

    // Eventos
    document.getElementById("productosForm")?.addEventListener("submit", function(e) {
        e.preventDefault();
        const selectedProducts = Array.from(document.querySelectorAll('.producto-checkbox:checked'))
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

    document.querySelectorAll('.btn-borrar-reserva').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const idReservation = this.closest('form').id.split('-')[3];
            borrarReserva(idReservation);
        });
    });

    document.querySelectorAll('.form-deshabilitar-producto').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const button = this.querySelector('.btn-deshabilitar');
            if (button) deshabilitarProducto({ target: button });
        });
    });
}