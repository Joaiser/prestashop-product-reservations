// Función para comprobar si todos los campos están completos
export function areFieldsComplete() {
  const reservationItems = document.querySelectorAll('.reservation_item');
  for (let item of reservationItems) {
    const productId = item.querySelector('select[name^="product_id"]').value;
    const quantity = item.querySelector('input[name^="quantity"]').value;
    const customerId = item.querySelector('select[name^="id_customer"]').value;

    // Si alguno de los campos está vacío, retorna false
    if (!productId || !quantity || !customerId) {
      return false;
    }
  }
  return true;  // Si todos los campos están completos, retorna true
}

// Función para mostrar mensaje de error
export function showError(message) {
  const messageContainer = document.querySelector('.reservation-message');
  messageContainer.textContent = message;
  messageContainer.classList.remove('alert-success');
  messageContainer.classList.add('alert-danger');
  messageContainer.style.display = 'block';

  setTimeout(() => {
    messageContainer.style.display = 'none';
  }, 5000);
}

// Función para mostrar mensaje de éxito
export function showSuccess(message) {
  const messageContainer = document.querySelector('.reservation-message');
  messageContainer.textContent = message;
  messageContainer.classList.remove('alert-danger');
  messageContainer.classList.add('alert-success');
  messageContainer.style.display = 'block';

  setTimeout(() => {
    messageContainer.style.display = 'none';
  }, 5000);
}
