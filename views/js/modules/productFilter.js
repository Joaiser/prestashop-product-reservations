export function init(ajaxUrl, csrfToken) {
  const categoriaSelect = document.getElementById('id_categoria');
  const productosContainer = document.getElementById('productos-container');

  if (!categoriaSelect || !productosContainer) return;

  // Función para cargar productos
  const cargarProductos = async (idCategoria) => {
    try {
      productosContainer.innerHTML = '<div class="loading">Cargando productos...</div>';

      const response = await fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          ajax: true,
          action: 'filterProducts',
          id_categoria: idCategoria,
          token: csrfToken
        })
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

      const data = await response.json();

      if (data.success && data.html) {
        productosContainer.innerHTML = data.html;
        // Disparar evento personalizado cuando se cargan nuevos productos
        document.dispatchEvent(new CustomEvent('productosCargados'));
      } else {
        throw new Error(data.error_message || 'Respuesta inválida del servidor');
      }
    } catch (error) {
      console.error('Error al cargar productos:', error);
      productosContainer.innerHTML = `<div class="error-message">Error al cargar productos: ${error.message}</div>`;
    }
  };

  // Evento para cambio de categoría
  categoriaSelect.addEventListener('change', function () {
    cargarProductos(this.value);
  });

  // Carga inicial
  cargarProductos(categoriaSelect.value);
}