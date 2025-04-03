export class NotaEditHandler {
    constructor(notaEditor) {
        this.editor = notaEditor; // Recibimos la instancia principal
        this.isSaving = false;
    }

    init() {
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

    getNotaData(target) {
        console.log("target en getNotaData:", target);
        
        let container = target.closest('.nota-contenido');
        
        if (!container && target.classList.contains('nota-edit')) {
            container = target.closest('div[data-nota-id]');
        }
        
        if (!container) {
            console.error("❌ Error: Contenedor de la nota no encontrado.");
            return null;
        }
        
        const notaId = container.dataset.notaId;
        const newText = container.querySelector('.nota-edit')?.value.trim() || target.value.trim();
        
        if (!notaId) {
            console.error("❌ Error: ID de nota no encontrado.");
            return null;
        }
        
        if (!newText) {
            console.error("❌ Error: Texto de nota vacío.");
            return null;
        }
        
        return { notaId, newText, container };
    }

    async handleSaveClick(event) {
        event.preventDefault();
        event.stopPropagation();
    
        if (this.isSaving) return;
        this.isSaving = true;
    
        const notaData = this.getNotaData(event.target);
        if (!notaData) {
            this.isSaving = false;
            return;
        }
    
        const { notaId, newText, container } = notaData;
        
        try {
            console.log("Guardando la nota con ID:", notaId);
            if (notaId > 0) {
                await this.editor.api.updateNota(notaId, newText);
                this.editor.ui.showFeedback(container, 'success', 'Nota actualizada correctamente');
                this.editor.ui.toggleEditMode(notaId, false, newText);
            } else {
                const idUser = container.dataset.userId;
                if (!idUser) throw new Error("No se encontró el usuario asociado");
                
                await this.editor.api.insertarNota(idUser, newText);
                this.editor.ui.showFeedback(container, 'success', 'Nota creada correctamente');
                await this.editor.reloadNotes();
            }
        } catch (error) {
            this.editor.ui.showFeedback(container, 'error', error.message || 'Error al guardar la nota');
            console.error("Error al guardar la nota:", error);
            this.editor.ui.toggleEditMode(notaId, true);
        } finally {
            this.isSaving = false;
        }
    }

    handleEditClick(event) {
        const editIcon = event.target.closest('.nota-edit-icon');
        if (!editIcon) return;
    
        const notaData = this.getNotaData(editIcon);
        if (!notaData) return;
    
        const { notaId, container } = notaData;
        this.editor.ui.toggleEditMode(notaId, true);
        this.editor.ui.focusTextElement(notaId);
    }
}