export class NotaApi{
    constructor(){
        this.endpoint = window.admin_controller_url;
        this.token = window.admin_token;
    }

    async updateNota(notaId, idUser, newText, textIndex){
        const formData = new FormData(); 
        formData.append('action', 'update_nota_text');
        formData.append('nota_id', notaId);
        formData.append('id_user', idUser);
        formData.append('text_index', textIndex);
        formData.append('new_text', newText);
        formData.append('token', this.token);

        const response = await fetch(this.endpoint, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw new Error('Error al actualizar la nota');
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error('Error al actualizar la nota: ' + data.message);
        }

        return data;
    }
}
