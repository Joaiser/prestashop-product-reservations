import { updateReference } from "./uiUpdates.js";

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
                // Añadir estilos directamente en la configuración
                callbackOnInit: function() {
                    const dropdown = this.containerOuter.element.querySelector('.choices__list--dropdown');
                    if (dropdown) {
                        dropdown.style.zIndex = '9999';
                    }
                }
            });
        });
    });
}

export function initializeDynamicChoices(newReservation, firstCustomerValue) {
    const index = newReservation.getAttribute('data-index');
    const productSelect = newReservation.querySelector(`#product_id_${index}`);
    const originalProductSelect = document.querySelector('[name="product_id[0]"]');
    
    if (!productSelect || !originalProductSelect) return;

    // Limpiar instancia anterior
    if (productSelect._choices) {
        productSelect._choices.destroy();
    }

    // Clonar las opciones manualmente preservando los atributos data-*
    Array.from(originalProductSelect.options)
        .filter(option => option.value !== '')
        .forEach(option => {
            const newOption = new Option(option.text, option.value);
            newOption.setAttribute('data-reference', option.getAttribute('data-reference'));
            newOption.setAttribute('data-attribute', option.getAttribute('data-attribute'));
            productSelect.add(newOption);
        });

    // Establecer el valor por defecto (si existe un valor para el primer cliente)
    if (firstCustomerValue) {
        productSelect.value = firstCustomerValue;
    }

    // Inicializar Choices con las opciones clonadas
    const choices = new Choices(productSelect, {
        placeholder: true,
        placeholderValue: "Buscar producto...",
        removeItemButton: true,
        searchEnabled: true,
        shouldSort: false,
        callbackOnInit: function() {
            //ESTO NO FUNCIONA HAY QUE ARREGLARLO
            this.setChoiceByValue(firstCustomerValue || '');  // Si tienes un valor por defecto, lo pones aquí
            // Aplicar z-index directamente al dropdown
            const dropdown = this.containerOuter.element.querySelector('.choices__list--dropdown');
            if (dropdown) {
                dropdown.style.zIndex = '9999';
            }
        },
        classNames: {
            item: 'choices__item',
            button: 'choices__button',
            // Opcional: puedes añadir clases personalizadas para más control
            listDropdown: 'choices__list--dropdown-high-zindex'
        }
    });

    productSelect.addEventListener('change', function() {
        updateReference(this);
    });

    productSelect._choices = choices;
}
