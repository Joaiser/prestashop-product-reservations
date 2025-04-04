export class NotaUI {
    showFeedback(container, type, message) {
        const feedback = document.createElement('div');
        feedback.className = `nota-feedback nota-feedback--${type}`;
        feedback.textContent = message;
        container.appendChild(feedback);
        
        setTimeout(() => feedback.remove(), 2000);
    }

    toggleEditMode(notaId, isEditing, newText = null) {
        const notaTextElement = document.getElementById(`nota-text-${notaId}`);
        const textareaElement = document.getElementById(`nota-edit-${notaId}`);
        const saveIcon = document.getElementById(`save-icon-${notaId}`);
        const editIcon = document.getElementById(`edit-icon-${notaId}`);

        if (!notaTextElement || !textareaElement || !saveIcon || !editIcon) {
            console.error(`Elementos no encontrados para nota ${notaId}`);
            return;
        }

        if (isEditing) {
            notaTextElement.style.display = 'none';
            textareaElement.style.display = 'block';
            saveIcon.style.display = 'inline';
            editIcon.style.display = 'none';
            this.focusTextElement(notaId);
        } else {
            notaTextElement.style.display = 'block';
            textareaElement.style.display = 'none';
            saveIcon.style.display = 'none';
            editIcon.style.display = 'inline';
            
            if (newText) {
                notaTextElement.textContent = newText;
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
