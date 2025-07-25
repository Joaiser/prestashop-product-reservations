export function init() {
  createFeedbackContainer();

  function setupCheckboxEvents() {
    const checkboxes = document.querySelectorAll('.producto-checkbox');
    const btnAplicar = document.getElementById('btn-aplicar');

    checkboxes.forEach(checkbox => {
      checkbox.addEventListener('change', function () {
        const card = this.closest('.producto-card');
        if (card) {
          card.style.backgroundColor = this.checked ? '#f0f8ff' : '';
          card.style.border = this.checked ? '1px solid #4a90e2' : '';
        }

        if (btnAplicar) {
          btnAplicar.classList.toggle('visible', [...checkboxes].some(c => c.checked));
        }
      });
    });

    if (btnAplicar) {
      btnAplicar.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const selectedProducts = Array.from(document.querySelectorAll('.producto-checkbox:checked'))
          .map(c => ({
            id_product: c.value,
            id_product_attribute: c.dataset.idProductAttribute || 0,
            reference: c.dataset.reference || null
          }));

        if (selectedProducts.length > 0) {
          const event = new CustomEvent('habilitarReservas', {
            detail: { products: selectedProducts },
            bubbles: false
          });
          document.dispatchEvent(event);
        } else {
          showError("Por favor, selecciona al menos un producto.");
        }
      });
    }
  }

  setupCheckboxEvents();
  document.addEventListener('productosCargados', setupCheckboxEvents);
}

function createFeedbackContainer() {
  let container = document.getElementById('ui-feedback-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'ui-feedback-container';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    container.style.maxWidth = '300px';
    document.body.appendChild(container);
  }
}

function showMessage(message, bgColor) {
  const container = document.getElementById('ui-feedback-container');
  if (!container) return;

  const messageDiv = document.createElement('div');
  messageDiv.textContent = message;
  messageDiv.style.backgroundColor = bgColor;
  messageDiv.style.color = '#fff';
  messageDiv.style.padding = '10px 15px';
  messageDiv.style.marginTop = '10px';
  messageDiv.style.borderRadius = '5px';
  messageDiv.style.boxShadow = '0 2px 6px rgba(0,0,0,0.2)';
  messageDiv.style.fontSize = '14px';
  messageDiv.style.transition = 'opacity 0.5s';

  container.appendChild(messageDiv);

  setTimeout(() => {
    messageDiv.style.opacity = '0';
    setTimeout(() => container.removeChild(messageDiv), 500);
  }, 3000);
}

export function showSuccess(message) {
  showMessage(message, '#4CAF50');
}

export function showError(message) {
  showMessage(message, '#f44336');
}