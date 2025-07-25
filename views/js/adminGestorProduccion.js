document.addEventListener("DOMContentLoaded", function () {

  const checkboxes = document.querySelectorAll(".producto-checkbox");
  const btnAplicar = document.getElementById("btn-aplicar");
  let urlWithToken = `${ajaxUrl}`;

  // Mostrar u ocultar el botón de aplicar cuando se seleccionan productos
  checkboxes.forEach(checkbox => {
    checkbox.addEventListener("change", function () {
      const anyChecked = Array.from(checkboxes).some(c => c.checked);
      btnAplicar.style.display = anyChecked ? "flex" : "none";
      btnAplicar.style.justifyContent = anyChecked ? "center" : "";
      btnAplicar.style.alignItems = anyChecked ? "center" : "";
    });
  });

  // Evento para el formulario general
  document.getElementById("productosForm")?.addEventListener("submit", function (e) {
    e.preventDefault();
    const selectedProducts = Array.from(checkboxes)
      .filter(c => c.checked)
      .map(c => ({
        id_product: c.value,
        id_product_attribute: c.dataset.idProductAttribute || 0,
        reference: c.dataset.reference || null
      }));

    if (selectedProducts.length > 0) {
      habilitarReserva(selectedProducts);
    } else {
      alert("Por favor, selecciona al menos un producto.");
    }
  });

  // Función para habilitar reservas
  function habilitarReserva(products) {
    if (products.length === 0) {
      alert("No se encontraron productos válidos para habilitar reservas.");
      return;
    }

    try {
      // Inicialización de módulos existentes
      const productFilter = await import('./modules/productFilter.js');
      productFilter.init(ajaxUrl, csrfToken);

      // Primero importar uiManager.js
      const uiManager = await import('./modules/uiManager.js');
      uiManager.init();

      // Luego importar reservationManager.js
      const reservationManager = await import('./modules/reservationManager.js');
      reservationManager.init(ajaxUrl, csrfToken);

      // Luego importar otros módulos
      const { NotaEditor } = await import('./modules/notaEditor/notaEditor.js');
      const notaEditor = new NotaEditor();
      notaEditor.init();

    } catch (err) {
      console.error('Error cargando módulos:', err);
    }
  });
>>>>>>> ddc47cb87cfba50a7542ddcdbefcaff3a2d1e0a7
