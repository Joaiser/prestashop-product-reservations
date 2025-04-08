import { showSuccess, showError } from './uiManager.js';

export function init(ajaxUrl, csrfToken) {
    function habilitarReserva(products) {
        if (products.length === 0) {
            showError("No se encontraron productos válidos para habilitar reservas.");
            return;
        }

        if (!confirm(`¿Estás seguro de habilitar reservas para ${products.length} producto(s)?`)) {
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
                showSuccess("✅ Reservas habilitadas correctamente");
                window.location.reload();
            } else {
                showError("❌ Error al habilitar las reservas: " + (data.error_message || "Desconocido"));
            }
        })
        .catch(error => {
            console.error("Error:", error);
            showError("❌ Hubo un error al procesar la solicitud.");
        });
    }

    function borrarReserva(idReservation) {
        const url = `${ajaxUrl}&delete_reservation=${idReservation}&token=${csrfToken}`;
        if (confirm(`¿Seguro que deseas borrar la reserva ID ${idReservation}?`)) {
            fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess("✅ Reserva eliminada correctamente.");
                    document.querySelector(`button[data-id="${idReservation}"]`).closest('tr').remove();
                } else {
                    showError("❌ Error al borrar la reserva: " + (data.error_message || "Desconocido"));
                }
            })
            .catch(error => {
                console.error("Error:", error);
                showError("❌ Hubo un error al procesar la solicitud.");
            });
        }
    }

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
                    showSuccess("✅ Producto deshabilitado correctamente.");
                    window.location.reload();
                } else {
                    showError("❌ Error al deshabilitar: " + (data.error_message || "Desconocido"));
                }
            })
            .catch(error => {
                console.error("Error:", error);
                showError("❌ Hubo un error al procesar la solicitud.");
            });
    }

    async function actualizarCantidadReserva(idReserva, nuevaCantidad) {
        const params = new URLSearchParams();
        params.append('action', 'editarCantidadReserva');
        params.append('id_reservation', idReserva);
        params.append('nueva_cantidad', nuevaCantidad);
        params.append('ajax', '1');
        params.append('token', csrfToken);
    
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params
        });
        return await response.json();
    }

    document.querySelectorAll('.btn-editar-cantidad').forEach(btn => {
        btn.addEventListener('click', function() {
            const idReserva = this.dataset.id;
            const tr = this.closest('tr');
            this.style.display = 'none';
            tr.querySelector('.input-cantidad').style.display = 'inline-block';
            tr.querySelector('.btn-guardar-cantidad').style.display = 'inline-block';
        });
    });

    document.querySelectorAll('.btn-guardar-cantidad').forEach(btn => {
        btn.addEventListener('click', function() {
            const idReserva = this.dataset.id;
            const tr = this.closest('tr');
            const input = tr.querySelector('.input-cantidad');
            const nuevaCantidad = parseInt(input.value);

            if (isNaN(nuevaCantidad) || nuevaCantidad < 0) {
                showError('❌ Cantidad no válida');
                return;
            }

            this.innerHTML = '⌛';
            this.disabled = true;

            actualizarCantidadReserva(idReserva, nuevaCantidad)
                .then(data => {
                    if (data.success) {
                        tr.querySelector('.cantidad-reserva').textContent = `🛒 ${nuevaCantidad}`;
                        input.style.display = 'none';
                        this.style.display = 'none';
                        tr.querySelector('.btn-editar-cantidad').style.display = 'inline-block';
                        showSuccess('✅ Cantidad actualizada');
                    } else {
                        showError(`❌ ${data.message || 'Error al actualizar'}`);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('❌ Error de conexión');
                })
                .finally(() => {
                    this.innerHTML = '✔️';
                    this.disabled = false;
                });
        });
    });

    document.addEventListener('habilitarReservas', function(e) {
        habilitarReserva(e.detail.products);
    });

    document.querySelectorAll('.btn-borrar-reserva').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const idReservation = this.closest('form').querySelector('input[name="id_reservation"]').value;
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


    
    // document.getElementById("productosForm")?.addEventListener("submit", function(e) {
    //     e.preventDefault();
    //     const selectedProducts = Array.from(document.querySelectorAll('.producto-checkbox:checked'))
    //         .map(c => ({
    //             id_product: c.value,
    //             id_product_attribute: c.dataset.idProductAttribute || 0,
    //             reference: c.dataset.reference || null
    //         }));

    //     if (selectedProducts.length > 0) {
    //         habilitarReserva(selectedProducts);
    //     } else {
    //         alert("Por favor, selecciona al menos un producto.");
    //     }
    // });
