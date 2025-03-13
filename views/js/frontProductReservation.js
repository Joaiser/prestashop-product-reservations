document.addEventListener('DOMContentLoaded', function () {
    const reservationForm = document.querySelector('#reservation_form');

    if (reservationForm) {
        // Función para filtrar opciones en un select
        function filterOptions(select, searchText) {
            const options = select.querySelectorAll('option');
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                if (text.includes(searchText.toLowerCase())) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        }

        // Función para habilitar la búsqueda en tiempo real en un select
        function enableSearchOnSelect(select) {
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Buscar...';
            searchInput.style.width = '100%';
            searchInput.style.marginBottom = '10px';
            searchInput.addEventListener('input', function () {
                filterOptions(select, this.value);
            });

            // Insertar el campo de búsqueda antes del select
            select.parentNode.insertBefore(searchInput, select);
        }

        // Habilitar la búsqueda en los selects
        const customerSelect = document.querySelector('select[name^="id_customer"]');
        const productSelect = document.querySelector('select[name^="product_id"]');

        if (customerSelect) {
            enableSearchOnSelect(customerSelect);
        }
        if (productSelect) {
            enableSearchOnSelect(productSelect);
        }

        // Función para actualizar la referencia cuando se selecciona un producto
        function updateReference(select) {
            const index = select.name.match(/\d+/)[0];
            const selectedProduct = select.options[select.selectedIndex];
            const reference = selectedProduct.text.split(' - ')[1];
            const referenceInput = document.querySelector(`input[name="reference[${index}]"]`);
            if (referenceInput) {
                referenceInput.value = reference;
            }
        }

        // Función para contar cuántos productos hay
        function countProducts() {
            return document.querySelectorAll('.reservation_item').length;
        }

        // Función para habilitar o deshabilitar el campo id_customer
        function toggleCustomerField() {
            const productCount = countProducts();
            const customerFields = document.querySelectorAll('select[name^="id_customer"]');
            customerFields.forEach(field => field.disabled = productCount !== 1);
        }

        // Función para verificar si todos los campos están completos
        function areFieldsComplete() {
            const reservationItems = document.querySelectorAll('.reservation_item');
            for (let item of reservationItems) {
                const productId = item.querySelector('select[name^="product_id"]').value;
                const quantity = item.querySelector('input[name^="quantity"]').value;
                const customerId = item.querySelector('select[name^="id_customer"]').value;

                if (!productId || !quantity || !customerId) {
                    return false;
                }
            }
            return true;
        }

        // Función para mostrar un mensaje de error
        function showError(message) {
            const messageContainer = document.querySelector('.reservation-message');
            messageContainer.textContent = message;
            messageContainer.classList.remove('alert-success');
            messageContainer.classList.add('alert-danger');
            messageContainer.style.display = 'block';
        }

        // Asignar el evento `change` a los campos de selección de productos existentes
        document.querySelectorAll('select[name^="product_id"]').forEach(select => {
            select.addEventListener('change', function () {
                updateReference(this);
            });
        });

        reservationForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Obtener el token CSRF
            const token = document.querySelector('input[name="token"]').value;

            // Crear un array para almacenar los productos
            let products = [];
            document.querySelectorAll('.reservation_item').forEach((form, index) => {
                let product = {
                    product_id: form.querySelector('select[name="product_id[' + index + ']"]').value,
                    quantity: form.querySelector('input[name="quantity[' + index + ']"]').value,
                    id_customer: form.querySelector('select[name="id_customer[' + index + ']"]').value,
                    reference: form.querySelector('input[name="reference[' + index + ']"]').value,
                    id_product_attribute: form.querySelector('input[name="id_product_attribute[' + index + ']"]').value
                };
                products.push(product);
            });

            let actionUrl = this.action;
            let messageContainer = this.closest('.product-reservation-widget').querySelector('.reservation-message');
            messageContainer.textContent = '';
            messageContainer.style.display = 'none';

            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ ajax: 1, products: products })
            })
            .then(response => response.json())
            .then(data => {
                messageContainer.classList.remove('alert-success', 'alert-danger');

                if (data.success) {
                    messageContainer.textContent = 'Reservas realizadas con éxito.';
                    messageContainer.classList.add('alert-success');
                    messageContainer.style.display = 'block';

                    // Limpiar los inputs existentes
                    const firstReservation = document.querySelector('.reservation_item[data-index="0"]');
                    if (firstReservation) {
                        firstReservation.querySelector('select[name="product_id[0]"]').value = '';
                        firstReservation.querySelector('input[name="quantity[0]"]').value = 1;
                        firstReservation.querySelector('select[name="id_customer[0]"]').value = '';
                        firstReservation.querySelector('input[name="reference[0]"]').value = '';
                        firstReservation.querySelector('input[name="id_product_attribute[0]"]').value = '';
                    }

                    // Eliminar los inputs extras
                    const reservationContainer = document.getElementById('product_reservation_container');
                    const extraReservations = reservationContainer.querySelectorAll('.reservation_item:not([data-index="0"])');
                    extraReservations.forEach(reservation => reservation.remove());

                    // Ocultar el mensaje después de 2 segundos
                    setTimeout(() => {
                        messageContainer.style.display = 'none';
                    }, 2000);
                } else {
                    messageContainer.textContent = 'Error: ' + (data.message || 'Error desconocido.');
                    messageContainer.classList.add('alert-danger');
                }
                messageContainer.style.display = 'block';
            })
            .catch(() => {
                messageContainer.textContent = 'Ocurrió un error al procesar la solicitud.';
                messageContainer.classList.add('alert-danger');
                messageContainer.style.display = 'block';
            });
        });

        // Manejar el botón de añadir más reservas
        document.getElementById('add_more_reservations').addEventListener('click', function() {
            if (!areFieldsComplete()) {
                showError('Por favor, completa todos los campos antes de añadir otro producto.');
                return;
            }

            const container = document.getElementById('product_reservation_container');
            const customerField = document.querySelector('[name="id_customer[0]"]');
            if (!customerField) return;

            const index = container.getElementsByClassName('reservation_item').length;
            const newReservation = container.getElementsByClassName('reservation_item')[0].cloneNode(true);

            newReservation.setAttribute('data-index', index);
            newReservation.querySelector('select[name="product_id[0]"]').setAttribute('name', 'product_id[' + index + ']');
            newReservation.querySelector('input[name="quantity[0]"]').setAttribute('name', 'quantity[' + index + ']');
            newReservation.querySelector('select[name="id_customer[0]"]').setAttribute('name', 'id_customer[' + index + ']');
            newReservation.querySelector('input[name="reference[0]"]').setAttribute('name', 'reference[' + index + ']');
            newReservation.querySelector('input[name="id_product_attribute[0]"]').setAttribute('name', 'id_product_attribute[' + index + ']');

            const currentCustomerId = customerField.value;
            newReservation.querySelector('select[name="id_customer[' + index + ']"]').value = currentCustomerId;

            newReservation.querySelector('select[name="product_id[' + index + ']"]').value = '';
            newReservation.querySelector('input[name="quantity[' + index + ']"]').value = 1;

            const removeButton = newReservation.querySelector('.remove_reservation');
            removeButton.style.display = 'inline-block';
            removeButton.addEventListener('click', function () {
                newReservation.remove();
                toggleCustomerField();
            });

            const newProductSelect = newReservation.querySelector('select[name^="product_id"]');
            if (newProductSelect) {
                newProductSelect.addEventListener('change', function () {
                    updateReference(this);
                });
            }

            container.appendChild(newReservation);
            toggleCustomerField();
        });

        // Habilitar o deshabilitar el campo id_customer al cargar la página
        toggleCustomerField();
    }
});