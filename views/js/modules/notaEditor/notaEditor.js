import { NotaApi } from './notaApi.js';

export class NotaEditor {
    constructor() {
        this.api = new NotaApi();
        this.isSaving = false;
        this.initHandlers();
        this.initNewNoteForm();
    }

    initHandlers() {
        // Handlers para notas existentes (edición)
        document.addEventListener('click', (e) => {
            if (e.target.closest('.nota-edit-icon')) {
                this.handleEditClick(e);
            } else if (e.target.closest('.nota-save-icon')) {
                this.handleSaveClick(e);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.target.closest('.nota-edit') && !e.shiftKey) {
                e.preventDefault();
                this.handleSaveClick(e);
            }
        });
    }

    initNewNoteForm() {
        // Handler para el formulario de nueva nota
        const newNoteForm = document.querySelector('.ajax-form-container');
        if (!newNoteForm) return;

        newNoteForm.addEventListener('click', (e) => {
            if (e.target.classList.contains('ajax-submit-nota')) {
                this.handleNewNoteSubmit(e);
            }
        });
    }

    async handleNewNoteSubmit(event) {
        event.preventDefault();
        
        if (this.isSaving) return;
        this.isSaving = true;

        const clienteSelect = document.querySelector('.ajax-cliente-nota');
        const textoTextarea = document.querySelector('.ajax-texto-nota');
        const messagesContainer = document.getElementById('ajax-messages');

        const idUser = clienteSelect.value;
        const newText = textoTextarea.value.trim();

        // Validación básica
        if (!newText) {
            this.showMessage(messagesContainer, 'error', 'El comentario no puede estar vacío');
            this.isSaving = false;
            return;
        }

        try {
            const response = await this.api.insertarNota(idUser, newText);
            this.showMessage(messagesContainer, 'success', 'Nota creada correctamente');
            
            // Limpiar el formulario
            textoTextarea.value = '';
            
            // Recargar las notas (implementar según tu necesidad)
            this.reloadNotes();
        } catch (error) {
            this.showMessage(messagesContainer, 'error', error.message || 'Error al crear la nota');
            console.error("Error al crear nota:", error);
        } finally {
            this.isSaving = false;
        }
    }

    async reloadNotes() {
        // Implementa la recarga de notas según tu backend
        // Puedes usar fetch directamente o añadir un método a NotaApi
        try {
            const response = await fetch(window.gestorProduccionVars.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=load_notas&token=${window.gestorProduccionVars.csrfToken}`
            });
            
            const data = await response.json();
            if (data.success) {
                document.getElementById('notas-container').innerHTML = data.html;
            }
        } catch (error) {
            console.error('Error recargando notas:', error);
        }
    }

    showMessage(container, type, message) {
        // Limpiar mensajes anteriores
        container.querySelectorAll('.ajax-message').forEach(el => el.remove());
        
        const messageEl = document.createElement('p');
        messageEl.className = `ajax-message ${type}`;
        messageEl.textContent = message;
        messageEl.style.display = 'block';
        messageEl.style.color = type === 'success' ? 'green' : 'red';
        messageEl.style.fontWeight = 'bold';
        
        container.appendChild(messageEl);
        
        if (type === 'success') {
            setTimeout(() => {
                messageEl.remove();
            }, 3000);
        }
    }

    // Manejo del clic en el botón de guardar
    async handleSaveClick(event) {
        event.preventDefault();
        event.stopPropagation();
    
        if (this.isSaving) return;
        this.isSaving = true;
    
        const target = event.target;
        const notaData = this.getNotaData(target);
        if (!notaData) {
            this.isSaving = false;
            return;
        }
    
        const { notaId, newText, container } = notaData;
        
        try {
            console.log("Guardando la nota con ID:", notaId, "con texto:", newText);
            if (notaId > 0) {
                // Nota existente - actualizar
                await this.api.updateNota(notaId, newText);
                this.showFeedback(container, 'success', 'Nota actualizada correctamente');
            } else {
                // Nueva nota - insertar
                const idUser = container.dataset.userId; // Obtener id_user del contenedor
                if (!idUser) {
                    throw new Error("No se encontró el usuario asociado");
                }
                await this.api.insertarNota(idUser, newText);
                this.showFeedback(container, 'success', 'Nota creada correctamente');
                // Recargar o actualizar la interfaz según sea necesario
            }
        } catch (error) {
            this.showFeedback(container, 'error', error.message || 'Error al guardar la nota');
            console.error("Error al guardar la nota:", error);
            this.toggleEditMode(container, true); // Volver a modo edición si falla
        } finally {
            this.isSaving = false;
        }
    }

    getNotaData(target) {
        console.log("target en getNotaData:", target);
        
        // Buscar el contenedor de diferentes maneras según el target
        let container = target.closest('.nota-contenido');
        
        // Si no encontramos el contenedor directamente, puede ser un textarea
        if (!container && target.classList.contains('nota-edit')) {
            // Buscar el contenedor padre del textarea
            container = target.closest('div[data-nota-id]');
        }
        
        console.log("Contenedor encontrado:", container);
        
        if (!container) {
            console.error("❌ Error: Contenedor de la nota no encontrado.");
            return null;
        }
        
        const notaId = container.dataset.notaId;
        const newText = container.querySelector('.nota-edit') ? 
                       container.querySelector('.nota-edit').value.trim() : 
                       target.value.trim(); // En caso de que el target sea el textarea
        
        if (!notaId) {
            console.error("❌ Error: ID de nota no encontrado.");
            return null;
        }
        
        if (!newText) {
            console.error("❌ Error: Texto de nota vacío.");
            return null;
        }
        
        return { notaId, newText, container }; // Eliminamos idUser del return
    }




    // Alterna entre modo de edición y vista previa
    toggleEditMode(notaItem, isEditing, newText = null) {
        const notaId = notaItem.dataset.notaId;
        console.log("ID de la nota detectado:", notaId);
        
        if (!notaId) {
            console.error("❌ Error: notaId no definido en el dataset de notaItem.");
            return;
        }
        
        const notaTextElement = document.getElementById(`nota-text-${notaId}`);
        if (!notaTextElement) {
            console.error(`❌ Error: No se encontró el elemento con ID "nota-text-${notaId}"`);
            return;
        }
        
        let textareaElement = document.getElementById(`nota-edit-${notaId}`);
        const saveIcon = document.getElementById(`save-icon-${notaId}`);
        const editIcon = document.getElementById(`edit-icon-${notaId}`);
        
        console.log(`Intentando alternar el modo de edición para la nota con ID: ${notaId}`);
        
        if (isEditing) {
            console.log("Modo de edición: Activado");
            notaTextElement.style.display = 'none';
            textareaElement.style.display = 'block';
            textareaElement.focus();
            
            saveIcon.style.display = 'inline';
            editIcon.style.display = 'none';
        } else {
            console.log("Modo de edición: Desactivado");
            notaTextElement.style.display = 'block';
            textareaElement.style.display = 'none';
            
            // Actualizar el texto si se proporciona
            if (newText) {
                notaTextElement.textContent = newText;
            }
            
            saveIcon.style.display = 'none';
            editIcon.style.display = 'inline';
        }
    }
    

    // Focus al final del textarea
focusTextElement(notaItem) {
    const notaId = notaItem.dataset.notaId;
    const textareaElement = document.getElementById(`nota-edit-${notaId}`);
    
    // Verifica si el textarea existe
    if (!textareaElement) {
        console.log(`No se encontró el textarea con id "nota-edit-${notaId}"`);
        return; // Salir si el textarea no está en el DOM
    }
    
    console.log(`Enfocando el textarea de la nota con ID: ${notaId}`);
    textareaElement.focus();
    
    const range = document.createRange();
    range.selectNodeContents(textareaElement);
    range.collapse(false); // Mueve el cursor al final
    const sel = window.getSelection();
    sel.removeAllRanges(); // Limpia selecciones previas
    sel.addRange(range); // Agrega el nuevo rango
} 

    // Muestra un feedback después de guardar
    showFeedback(container, type, message) {
        const feedback = document.createElement('div');
        feedback.className = `nota-feedback nota-feedback--${type}`;
        feedback.textContent = message;

        container.appendChild(feedback);
        setTimeout(() => {
            feedback.remove();
        }, 2000);
    }

    // Gestiona el clic en el lápiz para activar la edición
    handleEditClick(event) {
        const editIcon = event.target.closest('.nota-edit-icon');
        if (!editIcon) return;
    
        const notaData = this.getNotaData(editIcon);
        if (!notaData) return;
    
        const { container } = notaData;
        this.toggleEditMode(container, true);
        this.focusTextElement(container);
    }
    
}
