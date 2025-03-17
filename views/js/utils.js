export function areFieldsComplete() {
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

export function showError(message) {
    const messageContainer = document.querySelector('.reservation-message');
    messageContainer.textContent = message;
    messageContainer.classList.remove('alert-success');
    messageContainer.classList.add('alert-danger');
    messageContainer.style.display = 'block';
}

export function showSuccess(message) {
    const messageContainer = document.querySelector('.reservation-message');
    messageContainer.textContent = message;
    messageContainer.classList.remove('alert-danger');
    messageContainer.classList.add('alert-success');
    messageContainer.style.display = 'block';
}