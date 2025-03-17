// Función para inicializar Choices.js en los selects pasados por parámetro
export function initializeChoices(selectors = ['#id_customer', '#product_id_0'], container = document) {
    console.log("Inicializando Choices.js en los selects:", selectors);

    selectors.forEach(selector => {
        const elements = container.querySelectorAll(selector);
        console.log(`Encontrados ${elements.length} elementos para selector: ${selector}`);
        elements.forEach(element => {
            console.log('Inicializando el elemento: ', element);
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
    console.log(`Inicializando Choices.js en los nuevos selects dinámicos (índice: ${index})...`);

    const productSelect = newReservation.querySelector(`#product_id_${index}`);
    if (productSelect) {
        console.log("Select encontrado:", productSelect.outerHTML);

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

        console.log(`Choices.js inicializado en el nuevo select de productos (índice: ${index}).`);
    } else {
        console.error(`No se encontró el select #product_id_${index}. Verifica el ID.`);
    }
}