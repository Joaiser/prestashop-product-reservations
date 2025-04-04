export class NotaFormHandler {
    constructor(notaEditor) {
        this.editor = notaEditor;
    }

    init() {
        const form = document.querySelector('.ajax-form-container');
        if (!form) return;

        form.addEventListener('click', (e) => {
            if (e.target.classList.contains('ajax-submit-nota')) {
                this.handleSubmit(e);
            }
        });
    }

    async handleSubmit(event) {
        event.preventDefault();
        
        if (this.editor.isSaving) return;
        this.editor.isSaving = true;

        const clienteSelect = document.querySelector('.ajax-cliente-nota');
        const textoTextarea = document.querySelector('.ajax-texto-nota');

        const idUser = clienteSelect.value;
        const newText = textoTextarea.value.trim();

        if (!newText) {
            this.editor.ui.showMessage('error', 'El comentario no puede estar vacío');
            this.editor.isSaving = false;
            return;
        }

        try {
            await this.editor.api.insertarNota(idUser, newText);
            this.editor.ui.showMessage('success', 'Nota creada correctamente');
            textoTextarea.value = '';
            await this.editor.reloadNotes();
        } catch (error) {
            this.editor.ui.showMessage('error', error.message || 'Error al crear la nota');
            console.error("Error al crear nota:", error);
        } finally {
            this.editor.isSaving = false;
        }
    }


async handleDelete(event, notaId) {
    if (!notaId) {
        console.error("ID de nota no encontrado");
        return;
    }

    try {
        const result = await this.editor.api.eliminarNota(notaId);  

        this.editor.ui.showMessage('success', 'Nota eliminada correctamente');
   
        const notaItem = event.target.closest('.nota-item');  // Asumimos que cada nota tiene la clase .nota-item
        if (notaItem) {
            notaItem.remove();
        }
    } catch (error) {
        console.error("Error al eliminar la nota:", error);
        this.editor.ui.showMessage('error', 'Error al eliminar la nota');
    }
}

    
}