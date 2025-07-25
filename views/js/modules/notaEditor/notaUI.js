export class NotaUI {

  toggleEditMode(notaId, isEditing, newText = null) {
    const elements = {
      notaTextElement: document.getElementById(`nota-text-${notaId}`),
      textareaElement: document.getElementById(`nota-edit-${notaId}`),
      saveIcon: document.getElementById(`save-icon-${notaId}`),
      editIcon: document.getElementById(`edit-icon-${notaId}`),
      deleteIcon: document.getElementById(`delete-icon-${notaId}`)
    };

    // Comprobar si todos los elementos existen
    if (!elements.notaTextElement || !elements.textareaElement || !elements.saveIcon || !elements.editIcon || !elements.deleteIcon) {
      console.error(`Elementos no encontrados para nota ${notaId}`);
      return;
    }

    // Forzar un reflow (recalcular estilo)
    elements.notaTextElement.offsetHeight;

    if (isEditing) {
      elements.notaTextElement.style.display = 'none';
      elements.textareaElement.style.display = 'block';
      elements.saveIcon.style.display = 'inline';
      elements.editIcon.style.display = 'none';
      elements.deleteIcon.style.display = 'none';
      this.focusTextElement(notaId);
    } else {
      elements.notaTextElement.style.display = 'block';
      elements.textareaElement.style.display = 'none';
      elements.saveIcon.style.display = 'none';
      elements.editIcon.style.display = 'inline';
      elements.deleteIcon.style.display = 'inline';

      if (newText) {
        elements.notaTextElement.innerHTML = `${newText}
                 <button class="nota-edit-icon" id="edit-icon-${notaId}">✏️</button>
                <button class="nota-delete-icon" id="delete-icon-${notaId}">🗑️</button>`;
      }
    }
  }



  focusTextElement(notaId) {
    const textarea = document.getElementById(`nota-edit-${notaId}`);
    if (textarea) {
      textarea.focus();
      textarea.setSelectionRange(textarea.value.length, textarea.value.length);

      // Scroll suave al textarea si es necesario
      textarea.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
        inline: 'nearest'
      });
    }
  }


  showMessage(type, message) {
    const container = document.getElementById('ajax-messages');
    if (!container) return;

    container.innerHTML = '';
    container.style.display = 'block';

    const messageEl = document.createElement('p');
    messageEl.className = `ajax-message ${type}`;
    messageEl.textContent = message;
    messageEl.style.color = type === 'success' ? 'green' : 'red';
    messageEl.style.fontWeight = 'bold';
    messageEl.style.marginTop = '10px';

    container.appendChild(messageEl);

    if (type === 'success') {
      setTimeout(() => {
        messageEl.remove();
        container.style.display = 'none';
      }, 3000);
    }
  }
}
