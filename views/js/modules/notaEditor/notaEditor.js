import { NotaApi } from './notaApi.js';
console.log("NotaEditor.js cargado");

export class NotaEditor {
    constructor() {
        this.api = new NotaApi();
        this.initHandlers();
    }

    initHandlers() {
        console.log("Manejador de eventos inicializado");
   
        document.addEventListener('click', (e) => {
            console.log("Objetivo del clic:", e.target);
       
            if (e.target.closest('.edit-lapiz')) {
                console.log("Clic en el lápiz detectado");
                this.handleEditClick(e);
            } else if (e.target.closest('.save-nota-btn')) {
                console.log("Clic en el botón de guardar detectado");
                this.handleSaveClick(e);
            }
       });
       
        document.addEventListener('notasCargadas', () => this.initHandlers());
    }
   
    async handleSaveClick(event) {
        event.preventDefault();
        const container = event.target.closest('.nota-texto-container');
        const notaItem = container.closest('.nota-item');
        
        const notaId = notaItem.dataset.notaid;  // Obtenemos notaId desde el data attribute
        const idUser = notaItem.dataset.userId; 
        const textIndex = container.dataset.textid;
        const newText = container.querySelector('.nota-texto').textContent;
    
        console.log("Guardando la nota con ID:", notaId, "y nuevo texto:", newText);
        
        this.toggleEditMode(container, false);
        this.showLoader(container, true);
    
        try {
            await this.api.updateNota(notaId, idUser, newText, textIndex);  // Usamos notaId aquí
            this.showFeedback(container, 'success', 'Nota guardada con éxito');
        } catch (error) {
            this.showFeedback(container, 'error', 'Error al guardar la nota');
        } finally {
            this.showLoader(container, false);
        }
    }
    

    // Helpers
    toggleEditMode(container, isEditing) {
        const notaId = container.closest('.nota-item').dataset.notaid;
        const notaTextElement = document.getElementById(`nota-text-${notaId}`);
        const textareaElement = document.getElementById(`nota-edit-${notaId}`);
    
        if (!textareaElement) {
            console.log(`El textarea con id "nota-edit-${notaId}" no se encuentra en el DOM`);
            return;
        }
    
        console.log(`Alternando el modo de edición para nota ${notaId}. Modo de edición: ${isEditing ? "Activado" : "Desactivado"}`);
    
        if (isEditing) {
            // Hacer visible el textarea y ocultar el <p>
            textareaElement.style.display = 'block';
            textareaElement.classList.add('visible');
            textareaElement.value = notaTextElement.textContent;  // Rellenamos el textarea con el texto
            notaTextElement.style.display = 'none';  // Ocultamos el texto original
        } else {
            // Restablecer visibilidad
            textareaElement.style.display = 'none';
            textareaElement.classList.remove('visible');
            notaTextElement.style.display = 'block';  // Volver a mostrar el texto original
        }
    }
    
    
    
    focusTextElement(container) {
        const notaId = container.closest('.nota-item').dataset.notaid;
        const textareaElement = document.getElementById(`nota-edit-${notaId}`);
        textareaElement.focus();

        // Mover cursor al final del texto
        const range = document.createRange();
        range.selectNodeContents(textareaElement);
        range.collapse(false); // false para mover el cursor al final
        const sel = window.getSelection();
        sel.removeAllRanges(); // Limpiar selecciones anteriores
        sel.addRange(range); // Seleccionar el rango
    }

    getIds(container) {
        return {
            notaId: container.closest('.nota-item').dataset.notaid,
            textId: container.dataset.textid
        };
    }

    showLoader(container, show) {
        const loader = container.querySelector('.nota-loader') || this.createLoader(container);
        loader.style.display = show ? 'block' : 'none';
    }

    createLoader(container) {
        const loader = document.createElement('div');
        loader.className = 'nota-loader';
        loader.innerHTML = '⏳';
        container.querySelector('.nota-actions').appendChild(loader);
        return loader;
    }

    showFeedback(container, type, message) {
        const feedback = document.createElement('div');
        feedback.className = `nota-feedback nota-feedback--${type}`;
        feedback.textContent = message;

        container.appendChild(feedback);
        setTimeout(() => {
            feedback.remove();
        }, 2000);
    }

    // Gestionar el clic en el emoji de lápiz
    handleEditClick(event) {
        const spanElement = event.target.closest('.edit-lapiz');
        
        if (!spanElement || spanElement.style.display === 'none') {
            console.log("El lápiz no está visible o no se hizo clic en el lápiz.");
            return;
        }
    
        const container = spanElement.closest('.nota-body');
        console.log("Contenedor de nota encontrado:", container);
    
        if (container) {
            console.log("Clic en el lápiz detectado para nota:", container.closest('.nota-item').dataset.notaid);
            this.toggleEditMode(container, true);
            this.focusTextElement(container);
        } else {
            console.log("No se encontró el contenedor .nota-body");
        }
    } 
    
}
