document.addEventListener("DOMContentLoaded", function() {
    console.log("El script ha cargado correctamente");

    const checkboxes = document.querySelectorAll(".producto-checkbox");
    const btnAplicar = document.getElementById("btn-aplicar");

    // Mostrar u ocultar el botón de aplicar cuando se seleccionan productos
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener("change", function() {
            const anyChecked = Array.from(checkboxes).some(c => c.checked);
            btnAplicar.style.display = anyChecked ? "block" : "none";
        });
    });

    // Evento para los botones individuales
    document.querySelectorAll(".btn-individual").forEach(button => {
        button.addEventListener("click", function() {
            const productId = this.dataset.id;
            const reference = this.dataset.reference || null;
            habilitarReserva([{ id_product: productId, reference: reference }]);
        });
    });

    // Evento para el formulario general
    document.getElementById("productosForm").addEventListener("submit", function(e) {
        e.preventDefault();
        const selectedProducts = Array.from(checkboxes)
            .filter(c => c.checked)
            .map(c => ({
                id_product: c.value,
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
        const urlWithToken = `${ajaxUrl}&token=${csrfToken}`; // Incluye el token en la URL

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
            } else {
                alert("Hubo un error al habilitar las reservas: " + (data.error_message || "Desconocido"));
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Hubo un error al procesar la solicitud. Por favor, revisa la consola para más detalles.");
        });
    }
});