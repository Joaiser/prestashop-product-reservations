export class NotaApi {
    constructor() {
        if (!window.gestorProduccionVars) {
            throw new Error("Variables globales gestorProduccionVars no definidas");
        }
        
        this.endpoint = window.gestorProduccionVars.ajaxUrl;
        this.token = window.gestorProduccionVars.csrfToken;
        
        if (!this.endpoint || !this.token) {
            console.error("Configuración incorrecta:", { 
                endpoint: this.endpoint,
                token: this.token 
            });
            throw new Error("Configuración de API incorrecta");
        }
    }

    async updateNota(notaId, newText) {  
        const formData = new FormData();
        formData.append('action', 'update_nota_text');
        formData.append('nota_id', notaId);
        formData.append('new_text', newText.trim());
        formData.append('token', this.token);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData,
            });

            const textResponse = await response.text();
            
            try {
                const data = JSON.parse(textResponse);
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Error al actualizar la nota');
                }
                return data;
            } catch (e) {
                console.error("Respuesta no es JSON:", textResponse);
                throw new Error("Respuesta inválida del servidor");
            }
        } catch (error) {
            console.error("Error en updateNota:", error);
            throw error;
        }
    }

    async insertarNota(idUser, newText) {
        const formData = new FormData();
        formData.append('action', 'update_nota_text'); // Mismo endpoint
        formData.append('id_user', idUser);
        formData.append('new_text', newText.trim());
        formData.append('token', this.token);
    
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData,
            });
    
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Error al insertar la nota');
            }
            return data;
        } catch (error) {
            console.error("Error en insertarNota:", error);
            throw error;
        }
    }
}