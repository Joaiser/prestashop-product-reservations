import { NotaApi } from './notaApi.js';
import { NotaFormHandler } from './notaFormHandler.js';
import { NotaEditHandler } from './notaEditHandler.js';
import { NotaUI } from './notaUI.js';

export class NotaEditor {
  constructor() {
    this.api = new NotaApi();
    this.ui = new NotaUI();
    this.formHandler = new NotaFormHandler(this);
    this.editHandler = new NotaEditHandler(this);
  }

  init() {
    this.formHandler.init();
    this.editHandler.init();
  }

  async reloadNotes() {
    try {
      const response = await fetch(window.gestorProduccionVars.ajaxUrl, {
        method: 'POST',
        body: `action=load_notas&token=${window.gestorProduccionVars.csrfToken}`
      });

      const data = await response.json();
      if (data.success) {
        document.getElementById('notas-container').innerHTML = data.html;
        // Re-inicializar handlers para las nuevas notas
        this.editHandler.init();
      }
    } catch (error) {
      console.error('Error recargando notas:', error);
    }
  }
}