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
            habilitarReserva([productId]); // Llamamos a la función con un solo ID
        });
    });

    // Evento para el formulario general
    document.getElementById("productosForm").addEventListener("submit", function(e) {
        e.preventDefault();
        const selectedProducts = Array.from(checkboxes)
            .filter(c => c.checked)
            .map(c => c.value);

        if (selectedProducts.length > 0) {
            habilitarReserva(selectedProducts);
        } else {
            alert("Por favor, selecciona al menos un producto.");
        }
    });

    // Función para habilitar reservas
    function habilitarReserva(productIds) {
        const token = getAdminToken(); // Aquí obtienes el token correctamente
        fetch("index.php?controller=AdminGestorProduccion&token=" + token, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                products: JSON.stringify(productIds) // Convierte el array de productos a JSON dentro del cuerpo
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
        .catch(error => console.error("Error:", error));
                
    }

    // Obtén el token desde el HTML o desde el contexto si no está disponible en JS
    function getAdminToken() {
        const tokenMetaTag = document.querySelector('meta[name="csrf-token"]');
        if (tokenMetaTag) {
            return tokenMetaTag.getAttribute('content');
        } else {
            console.error('No se pudo obtener el token CSRF.');
            return ''; // Retorna un valor vacío o muestra un error según sea necesario
        }
    }
});
