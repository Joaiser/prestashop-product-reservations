document.addEventListener("DOMContentLoaded", function() {
    console.log("El script ha cargado correctamente");

    const checkboxes = document.querySelectorAll(".producto-checkbox");
    const btnAplicar = document.getElementById("btn-aplicar");
    let urlWithToken = `${ajaxUrl}&token=${csrfToken}`; 

    // Mostrar u ocultar el botón de aplicar cuando se seleccionan productos
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("change", function() {
            const anyChecked = Array.from(checkboxes).some(c => c.checked);
            btnAplicar.style.display = anyChecked ? "flex" : "none";
            btnAplicar.style.justifyContent = anyChecked ? "center" : "";
            btnAplicar.style.alignItems = anyChecked ? "center" : "";

        });
    });

    // Evento para el formulario general
    document.getElementById("productosForm").addEventListener("submit", function(e) {
        e.preventDefault();
        const selectedProducts = Array.from(checkboxes)
            .filter(c => c.checked)
            .map(c => ({
                id_product: c.value,
                id_product_attribute: c.dataset.idProductAttribute || 0, // Asegúrate de que este campo esté presente
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
        

        // Verificar si hay productos válidos
        if (products.length === 0) {
            alert("No se encontraron productos válidos para habilitar reservas.");
            return;
        }

        fetch(urlWithToken, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                products: JSON.stringify(products), // Convierte el array de productos a JSON
                submit: true // Indica que es una solicitud de envío
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error("Error en la solicitud: " + response.statusText);
            }
            return response.json();  // Esto debería estar seguro si la respuesta es JSON
        })
        .then(data => {
            if (data.success) {
                alert("Reservas habilitadas correctamente");
                window.location.reload();
            } else {
                alert("Hubo un error al habilitar las reservas: " + (data.error_message || "Desconocido"));
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Hubo un error al procesar la solicitud. Por favor, revisa la consola para más detalles.");
        });
    }
// Función para borrar una reserva
function borrarReserva(idReservation) {
    // URL que apunta a la eliminación de la reserva
    const url = `${ajaxUrl}&delete_reservation=${idReservation}&token=${csrfToken}`;

    // Confirmación antes de borrar
    if (confirm(`¿Seguro que deseas borrar la reserva ID ${idReservation}?`)) {
        fetch(url)
            .then(response => response.json()) // Esperamos una respuesta JSON
            .then(data => {
                if (data.success) {
                    alert("Reserva eliminada correctamente.");
                    // Actualizar la vista o recargar la página
                    location.reload();
                } else {
                    alert("Error al borrar la reserva: " + (data.error_message || "Desconocido"));
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Hubo un error al procesar la solicitud.");
            });
    }
}

// Agregar evento a los botones de borrar reserva
document.querySelectorAll('.btn-borrar-reserva').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();  // Evita el comportamiento por defecto del formulario
        const idReservation = this.closest('form').id.split('-')[3];  // Obtenemos la ID de la reserva desde el id del formulario
        borrarReserva(idReservation);  // Llamamos a la función con la ID de la reserva
    });
});



//    // Función para manejar la deshabilitación de reservas
//     function deshabilitarReserva(event) {
//     console.log("Botón de deshabilitar presionado"); // Log para depuración

//     const button = event.currentTarget;
//     const productId = button.dataset.id;
//     const reference = button.dataset.reference;

//     if (!confirm("¿Seguro que deseas deshabilitar este producto?")) {
//         return;
//     }

//     const urlWithToken = `${ajaxUrl}&token=${csrfToken}`; // Incluye el token en la URL

//     fetch(urlWithToken, {
//         method: "POST",
//         headers: {
//             "Content-Type": "application/x-www-form-urlencoded"
//         },
//         body: new URLSearchParams({
//             products: JSON.stringify([{ id_product: productId, reference }]), // Convertimos a formato esperado
//             action: "deshabilitar_producto"
//         })
//     })
//     .then(response => {
//         if (!response.ok) {
//             throw new Error("Error en la solicitud: " + response.statusText);
//         }
//         return response.json();
//     })
//     .then(data => {
//         if (data.success) {
//             alert("Producto deshabilitado correctamente.");
//             window.location.reload();
//         } else {
//             alert("Error al deshabilitar: " + (data.error_message || "Desconocido"));
//         }
//     })
//     .catch(error => {
//         console.error("Error:", error);
//         alert("Hubo un error al procesar la solicitud. Por favor, revisa la consola para más detalles.");
//     });
// }

// // Agregar evento a los botones de deshabilitar
// document.querySelectorAll('.btn-deshabilitar').forEach(button => {
//     button.addEventListener('click', deshabilitarReserva);
// });

});
