import { NotaApi } from './notaApi.js';
console.log("NotaEditor.js cargado");

export class NotaEditor {
    constructor() {
        this.api = new NotaApi();
        this.initHandlers();
    }

    initHandlers() {
        console.log("Manejador de eventos inicializado");

        // Combinamos ambos manejadores de clic en uno solo
        document.addEventListener('click', (e) => {
            console.log("Objetivo del clic:", e.target);

            // Verifica si el clic fue sobre un <p> que es editable
            const pElement = e.target.closest('.nota-body p');
            if (pElement && pElement.classList.contains('nota-texto')) {
                console.log("Clic en el <p> editable detectado");
                this.handleEditClick(e);
            } else if (e.target.closest('.nota-save-icon')) {
                console.log("Clic en el botón de guardar detectado");
                this.handleSaveClick(e);
            }
        });

        // Manejo del evento 'notasCargadas'
        document.addEventListener('notasCargadas', () => this.initHandlers());
    }

    // Manejo del clic en el botón de guardar
    async handleSaveClick(event) {
        event.preventDefault();
        const container = event.target.closest('.nota-contenido');
        const notaId = container.dataset.notaId; 
        const idUser = container.dataset.userId; 
        const newText = container.querySelector('.nota-edit').value;
    
        console.log("Guardando la nota con ID:", notaId, "para el usuario:", idUser, "con nuevo texto:", newText);
    
        this.toggleEditMode(container, false);
        this.showLoader(container, true);
    
        try {
            await this.api.updateNota(notaId, idUser, newText);
            this.showFeedback(container, 'success', 'Nota guardada con éxito');
        } catch (error) {
            this.showFeedback(container, 'error', 'Error al guardar la nota');
        } finally {
            this.showLoader(container, false);
        }
    }
    

    // Alterna entre modo de edición y vista previa
    toggleEditMode(notaItem, isEditing) {
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
            if (!textareaElement) {
                textareaElement = document.createElement('textarea');
                textareaElement.id = `nota-edit-${notaId}`;
                textareaElement.classList.add('nota-edit');
                textareaElement.value = notaTextElement.textContent;
    
                // Reemplaza el contenido del texto con el textarea
                notaTextElement.replaceWith(textareaElement);
            }
    
            console.log("Modo de edición: Activado");
            textareaElement.style.display = 'block';
            textareaElement.focus();
    
            saveIcon.style.display = 'inline';
            editIcon.style.display = 'none';
        } else {
            console.log("Modo de edición: Desactivado");
    
            if (textareaElement) {
                // Crear un nuevo párrafo para restaurar el texto
                const newTextElement = document.createElement('p');
                newTextElement.id = `nota-text-${notaId}`;
                newTextElement.classList.add('nota-texto');
                newTextElement.textContent = textareaElement.value;
    
                textareaElement.replaceWith(newTextElement);
            }
    
            saveIcon.style.display = 'none';
            editIcon.style.display = 'inline';
        }
    }
    
    
    

    // Coloca el cursor al final del texto en el textarea
    focusTextElement(notaItem) {
        const notaId = notaItem.dataset.notaid;
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
    

    // Muestra un loader durante la actualización
    showLoader(container, show) {
        const loader = container.querySelector('.nota-loader') || this.createLoader(container);
        loader.style.display = show ? 'block' : 'none';
    }

    // Crea un loader si no existe
    createLoader(container) {
        const loader = document.createElement('div');
        loader.className = 'nota-loader';
        loader.innerHTML = '⏳';
        container.querySelector('.nota-actions').appendChild(loader);
        return loader;
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
    
        const notaItem = editIcon.closest('.nota-contenido');
        if (!notaItem) {
            console.error("❌ Error: No se encontró el contenedor de la nota.");
            return;
        }
    
        const notaId = notaItem.dataset.notaId;
        if (!notaId) {
            console.error("❌ Error: No se encontró el atributo data-nota-id en notaItem.");
            return;
        }
    
        console.log("Contenedor de nota encontrado:", notaItem);
        console.log("Nota ID detectada:", notaId);
    
        this.toggleEditMode(notaItem, true);
        this.focusTextElement(notaItem);
    }
    
}
