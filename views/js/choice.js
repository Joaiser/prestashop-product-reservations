// Función para inicializar Choices.js en los selects pasados por parámetro
export function initializeChoices(selectors = ['#id_customer', '#product_id_0'], container = document) {

    selectors.forEach(selector => {
        const elements = container.querySelectorAll(selector);
        elements.forEach(element => {
            new Choices(element, {
                placeholder: true,
                placeholderValue: "Selecciona una opción",
                removeItemButton: true,
                searchEnabled: true,
            });
        });
    });
}


export function initializeDynamicChoices(newReservation) {
    const index = newReservation.getAttribute('data-index');

    const productSelect = newReservation.querySelector(`#product_id_${index}`);
    if (productSelect) {

        // Destruir la instancia anterior de Choices.js si existe
        if (productSelect._choices) {
            productSelect._choices.destroy();
        }

        // Inicializar Choices.js en el nuevo select de productos
        const choices = new Choices(productSelect, {
            placeholder: true,
            placeholderValue: "Selecciona un producto",
            removeItemButton: true,
            searchEnabled: true,
        });

    } 
}