<<<<<<< HEAD
<?php

class AdminGestorProduccionController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->meta_title = $this->l('Gestor de Producción');
    }

    public function initContent()
    {
        parent::initContent();
        
        // Obtener productos sin stock y sin fecha
        $productos_sin_stock_y_fecha = $this->getProductosSinStockYFecha();
        
        // Obtener productos sin stock pero con fecha de llegada
        $productos_con_fecha = $this->getProductosConFecha();
    
        // Obtener reservas pendientes
        $reservas_pendientes = $this->getReservasPendientes();
        
        // Obtener productos habilitados
        $productos_habilitados = $this->getProductosHabilitados();
        
        // Asignar los productos a la plantilla
        $this->context->smarty->assign([
            'productos_sin_stock_y_fecha' => $productos_sin_stock_y_fecha,
            'productos_con_fecha' => $productos_con_fecha,
            'reservas_pendientes' => $reservas_pendientes,
            'productos_habilitados' => $productos_habilitados, // Asigna los productos habilitados
        ]);
        
        // Asigna la plantilla al backoffice
        $this->setTemplate('gestorproduccion.tpl');
    }
    
    

    private function getProductosSinStockYFecha()
{
    $sql = 'SELECT p.id_product, pa.id_product_attribute, pl.name, 
                   IFNULL(pa.reference, p.reference) AS reference
            FROM '._DB_PREFIX_.'product p
            INNER JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
            LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
            LEFT JOIN '._DB_PREFIX_.'stock_available sa 
                ON (p.id_product = sa.id_product AND (pa.id_product_attribute = sa.id_product_attribute OR pa.id_product_attribute IS NULL))
            WHERE pl.id_lang = '.(int)$this->context->language->id.'
            AND (sa.quantity <= 0 OR sa.quantity IS NULL)
            AND (p.available_date IS NULL OR p.available_date = "0000-00-00")';

    return Db::getInstance()->executeS($sql);
}

private function getProductosConFecha()
{
    $sql = 'SELECT p.id_product, pa.id_product_attribute, pl.name, 
                   IFNULL(pa.reference, p.reference) AS reference,
                   p.available_date
            FROM '._DB_PREFIX_.'product p
            INNER JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
            LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
            LEFT JOIN '._DB_PREFIX_.'stock_available sa 
                ON (p.id_product = sa.id_product AND (pa.id_product_attribute = sa.id_product_attribute OR pa.id_product_attribute IS NULL))
            WHERE pl.id_lang = '.(int)$this->context->language->id.'
            AND (sa.quantity <= 0 OR sa.quantity IS NULL)
            AND (p.available_date IS NOT NULL AND p.available_date != "0000-00-00")';

    return Db::getInstance()->executeS($sql);
}

private function getReservasPendientes()
{
    $sql = 'SELECT pr.*, 
                   pl.name AS product_name, 
                   c.firstname AS customer_firstname, 
                   c.lastname AS customer_lastname,
                   com.firstname AS comercial_firstname, 
                   com.lastname AS comercial_lastname
            FROM '._DB_PREFIX_.'product_reservations pr
            LEFT JOIN '._DB_PREFIX_.'product p ON pr.id_product = p.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
            LEFT JOIN '._DB_PREFIX_.'customer c ON pr.id_customer = c.id_customer
            LEFT JOIN '._DB_PREFIX_.'customer com ON c.id_comercial = com.id_customer
            WHERE pr.status = "pendiente" 
            AND pl.id_lang = '.(int)$this->context->language->id;

    $reservas = Db::getInstance()->executeS($sql);
    return $reservas;
}




private function getProductosHabilitados()
{
    $sql = 'SELECT p.id_product, pa.id_product_attribute, pl.name AS product_name, 
                   IFNULL(pa.reference, p.reference) AS reference
            FROM '._DB_PREFIX_.'product_reservation_enabled pre
            LEFT JOIN '._DB_PREFIX_.'product p ON pre.id_product = p.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
            LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
            WHERE pl.id_lang = '.(int)$this->context->language->id.' 
            AND pre.is_enabled = 1';

    return Db::getInstance()->executeS($sql);
}



private function habilitarReservas($product_id, $id_product_attribute, $reference)
{
    // Obtener la instancia de PDO
    $pdo = Db::getInstance()->getLink();

    try {
        // Iniciar una transacción
        $pdo->beginTransaction();

        // Insertar o actualizar la tabla `product_reservation_enabled`
        $sqlReservationEnabled = 'INSERT INTO '._DB_PREFIX_.'product_reservation_enabled 
                                  (id_product, id_product_attribute, reference, is_enabled, date_enabled) 
                                  VALUES (
                                      '.(int)$product_id.', 
                                      '.(int)$id_product_attribute.', 
                                      "'.pSQL($reference).'", 
                                      1, 
                                      NOW()
                                  ) 
                                  ON DUPLICATE KEY UPDATE 
                                  is_enabled = 1, date_enabled = NOW()';
        Db::getInstance()->execute($sqlReservationEnabled);

        // Confirmar la transacción
        $pdo->commit();
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception("Error al habilitar las reservas: " . $e->getMessage());
    }
}

//Para borrar las reservas de los clientes que han reservado productos
private function borrarReserva($id_reservation)
{
    // Obtener la instancia de PDO
    $pdo = Db::getInstance()->getLink();

    try {
        // Iniciar una transacción
        $pdo->beginTransaction();

        // Eliminar la reserva de la tabla `product_reservation_enabled`
        $sql = 'DELETE FROM '._DB_PREFIX_.'product_reservations
        WHERE id_reservation = '.(int)pSQL($id_reservation);

        if (!Db::getInstance()->execute($sql)) {
            throw new Exception("No se pudo eliminar la reserva.");
        }

        // Confirmar la transacción
        $pdo->commit();
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception("Error al borrar la reserva: " . $e->getMessage());
    }
}


/**
 * Verifica si un producto tiene reservas activas
 */
private function tieneReservasActivas($product_id)
{
    $sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'product_reservations  WHERE id_product = '.(int)$product_id;
    return (bool)Db::getInstance()->getValue($sql);
}

/**
 * Deshabilita un producto eliminándolo de la tabla `product_reservation_enabled`
 */
private function deshabilitarProducto($product_id)
{
    // Obtener la instancia de PDO
    $pdo = Db::getInstance()->getLink();

    try {
        // Iniciar una transacción
        $pdo->beginTransaction();

        // Eliminar el registro de la tabla `product_reservation_enabled`
        $sql = 'DELETE FROM '._DB_PREFIX_.'product_reservation_enabled 
                WHERE id_product = '.(int)$product_id;

        if (!Db::getInstance()->execute($sql)) {
            throw new Exception("No se pudo eliminar el producto.");
        }

        // Confirmar la transacción
        $pdo->commit();
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception("Error al deshabilitar el producto: " . $e->getMessage());
    }
}

/**
 * Maneja las acciones POST del formulario
 */
public function postProcess()
{
    // Manejo de eliminación de reservas
    $id_reservation = (int) Tools::getValue('delete_reservation');
    if ($id_reservation) {
        try {
            $this->borrarReserva($id_reservation);
            exit(json_encode(['success' => true, 'message' => 'Reserva eliminada con éxito.']));
        } catch (Exception $e) {
            exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
        }
    }

    // Manejo de habilitación de productos para reserva
    if (Tools::isSubmit('submit')) {
        try {
            $json_data = Tools::getValue('products');
            if (!$json_data) {
                throw new Exception("No se recibieron datos de productos.");
            }

            $products = json_decode($json_data, true);
            if (!is_array($products)) {
                throw new Exception("Los datos del producto no son válidos.");
            }

            foreach ($products as $product) {
                $this->habilitarReservas(
                    (int) $product['id_product'],
                    (int) $product['id_product_attribute'],
                    pSQL($product['reference'])
                );
            }

            exit(json_encode(['success' => true, 'message' => 'Productos habilitados para reserva.']));
        } catch (Exception $e) {
            exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
        }
    }

    // Manejo de deshabilitación de productos
    $action = Tools::getValue('deshabilitarProducto');
    if ($action) {
        try {
            $product_id = (int) $action;  // Obtener el id_product de la URL

            // Verificar si el producto tiene reservas activas
            if ($this->tieneReservasActivas($product_id)) {
                throw new Exception("No se puede deshabilitar el producto $product_id porque tiene reservas activas.");
            }

            // Deshabilitar el producto
            $this->deshabilitarProducto($product_id);
            exit(json_encode(['success' => true, 'message' => 'Producto deshabilitado con éxito.']));
        } catch (Exception $e) {
            exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
        }
    }
}

=======
<?php

require_once __DIR__ . '/../../classes/adminGestorProduccion.php';

class AdminGestorProduccionController extends ModuleAdminController
{
  protected $gestorProduccion;

  public function __construct()
  {
    parent::__construct();
    $this->bootstrap = true;
    $this->meta_title = $this->l('Gestor de Producción');
    $this->gestorProduccion = new AdminGestorProduccion($this->context);
  }

  public function initContent()
  {
    parent::initContent();

    if (Tools::isSubmit('update_products_without_stock')) {
      $response = $this->processUpdateProductsWithoutStock();

      if (!Tools::getValue('ajax')) {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminGestorProduccion'));
      } else {
        die($response);
      }
    }

    $id_categoria = (int)Tools::getValue('id_categoria', 0);

    // Obtener productos iniciales (todas las categorías por defecto)
    $productos_iniciales = $this->gestorProduccion->getProductosPorCategoria(0);

    $notas_con_reservas = $this->gestorProduccion->getNotasConReservas();
    $notas = $this->eliminarDuplicadosNotas($notas_con_reservas);

    // Obtener mensajes desde Smarty para las notas
    $success_message = $this->context->smarty->getTemplateVars('success_message') ?? null;
    $error_message = $this->context->smarty->getTemplateVars('error_message') ?? null;

    // Asignamos las variables necesarias a Smarty
    $this->context->smarty->assign([
      // Solo mostrar productos ya habilitados (sin ejecutar las funciones problemáticas)
      'productos_sin_stock_y_fecha' => [],
      'productos_con_fecha' => [],
      'reservas_agrupadas' => $this->gestorProduccion->getReservasAgrupadas(),
      'productos_habilitados' => $this->gestorProduccion->getProductosHabilitados(),
      'CustomersQueHanReservado' => $this->gestorProduccion->getCustomersQueHanReservado(),
      'categorias' => $this->gestorProduccion->getCategorias(),
      'notas' => $notas,
      'id_categoria_seleccionada' => $id_categoria,
      'productos' => $productos_iniciales,
      'success_message' => $success_message,
      'error_message' => $error_message
    ]);

    $this->setTemplate('gestorproduccion.tpl');
  }

  //Funcion para manejar las duplicaciones delas notas
  public function eliminarDuplicadosNotas($notas)
  {
    $notas_agrupadas = [];

    foreach ($notas as $nota) {
      $cliente_id = $nota['id_user'];  // Asegurarnos de que agrupamos por cliente

      if (!isset($notas_agrupadas[$cliente_id])) {
        $notas_agrupadas[$cliente_id] = [
          'id_user' => $nota['id_user'],
          'cliente_nombre' => $nota['cliente_nombre'],
          'cliente_apellido' => $nota['cliente_apellido'],
          'comercial_nombre' => $nota['comercial_nombre'],
          'comercial_apellido' => $nota['comercial_apellido'],
          'notas' => []  // Creamos un array vacío para las notas
        ];
      }

      // Añadimos la nota a la lista de notas para ese cliente
      $notas_agrupadas[$cliente_id]['notas'][] = [
        'id_nota' => $nota['id_nota'],
        'nota' => $nota['notas']
      ];
    }

    return $notas_agrupadas;
  }




  public function postProcess()
  {

    if (
      isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' &&
      Tools::getValue('action') == 'editarCantidadReserva'
    ) {
      return $this->processEditarCantidadReserva();
    }


    if (Tools::isSubmit('ajax') && Tools::getValue('action') == 'filterProducts') {
      return $this->processFilterProducts();
    }

    if ((int)Tools::getValue('delete_reservation')) {
      return $this->processDeleteReservation();
    }

    if (Tools::isSubmit('submit')) {
      return $this->processEnableProducts();
    }

    if (Tools::getValue('deshabilitarProducto')) {
      return $this->processDisableProduct();
    }

    if (Tools::isSubmit('action')) {
      return $this->processNotaActions();
    }

    if (Tools::isSubmit('cliente_nota')) {
      return $this->processClienteNota();
    }
  }

  protected function processEditarCantidadReserva()
  {
    // Validación de que es una petición AJAX
    if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
      die(json_encode(['success' => false, 'message' => 'Acceso no autorizado']));
    }

    $idReservation = (int)Tools::getValue('id_reservation');
    $nuevaCantidad = (int)Tools::getValue('nueva_cantidad');

    // Validaciones
    if ($idReservation <= 0 || $nuevaCantidad < 0) {
      die(json_encode(['success' => false, 'message' => 'Parámetros inválidos']));
    }

    try {
      $resultado = $this->gestorProduccion->editarCantidadReserva($idReservation, $nuevaCantidad);

      die(json_encode([
        'success' => $resultado['success'],
        'message' => $resultado['success'] ? 'Cantidad actualizada' : ($resultado['error_message'] ?? 'Error al actualizar')
      ]));
    } catch (Exception $e) {
      die(json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage()
      ]));
    }
  }

  protected function processUpdateProductsWithoutStock()
  {
    try {
      $sinStock = $this->gestorProduccion->getProductosSinStockYFecha(true);
      $conFecha = $this->gestorProduccion->getProductosConFecha(true);

      return json_encode([
        'success' => true,
        'message' => 'Productos actualizados correctamente',
        'count_sin_stock' => count($sinStock),
        'count_con_fecha' => count($conFecha)
      ]);
    } catch (Exception $e) {
      return json_encode([
        'success' => false,
        'error_message' => $e->getMessage()
      ]);
    }
  }


  protected function processFilterProducts()
  {
    $id_categoria = (int)Tools::getValue('id_categoria', 0);
    $productos = $this->gestorProduccion->getProductosPorCategoria($id_categoria);

    $this->context->smarty->assign([
      'productos' => $productos
    ]);

    $html = $this->context->smarty->fetch(
      'module:gestorproduccion/views/templates/admin/_partials/productos.tpl'
    );

    die(json_encode([
      'success' => true,
      'html' => $html
    ]));
  }

  protected function processDeleteReservation()
  {
    $id_reservation = (int)Tools::getValue('delete_reservation');
    try {
      $this->gestorProduccion->borrarReserva($id_reservation);
      exit(json_encode(['success' => true, 'message' => 'Reserva eliminada con éxito.']));
    } catch (Exception $e) {
      exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
    }
  }

  protected function processEnableProducts()
  {
    try {
      $json_data = Tools::getValue('products');
      if (!$json_data) {
        throw new Exception("No se recibieron datos de productos.");
      }

      $products = json_decode($json_data, true);
      if (!is_array($products)) {
        throw new Exception("Los datos del producto no son válidos.");
      }

      foreach ($products as $product) {
        $this->gestorProduccion->habilitarReservas(
          (int)$product['id_product'],
          (int)$product['id_product_attribute'],
          pSQL($product['reference'])
        );
      }

      exit(json_encode(['success' => true, 'message' => 'Productos habilitados para reserva.']));
    } catch (Exception $e) {
      exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
    }
  }

  protected function processDisableProduct()
  {
    try {
      $product_id = (int)Tools::getValue('deshabilitarProducto');
      $product_attribute_id = (int)Tools::getValue('product_attribute_id', 0);

      // if ($this->gestorProduccion->tieneReservasActivas($product_id)) {
      //     throw new Exception("No se puede deshabilitar el producto $product_id porque tiene reservas activas.");
      // }

      $this->gestorProduccion->deshabilitarProducto($product_id, $product_attribute_id);

      exit(json_encode([
        'success' => true,
        'message' => $product_attribute_id > 0
          ? 'Combinación deshabilitada con éxito.'
          : 'Producto deshabilitado con éxito.'
      ]));
    } catch (Exception $e) {
      exit(json_encode([
        'success' => false,
        'error_message' => $e->getMessage()
      ]));
    }
  }

  protected function processNotaActions()
  {
    header('Content-Type: application/json');

    try {
      $action = Tools::getValue('action');

      switch ($action) {
        case 'update_nota_text':
          return $this->processUpdateNota();
        case 'delete_nota':
          return $this->processDeleteNota();
        default:
          throw new Exception('Acción no reconocida.');
      }
    } catch (Exception $e) {
      die(json_encode(['success' => false, 'message' => $e->getMessage()]));
    }
  }

  protected function processUpdateNota()
  {
    $notaId = (int)Tools::getValue('nota_id');
    $comentario = trim(Tools::getValue('new_text'));
    $idUser = (int)Tools::getValue('id_user');

    // Validaciones
    if (empty($comentario)) {
      throw new Exception('El comentario no puede estar vacío.');
    } elseif (strlen($comentario) > 500) {
      throw new Exception('El comentario es demasiado largo (máximo 500 caracteres).');
    }

    $comentario = strip_tags($comentario);

    if ($notaId > 0 && $this->gestorProduccion->existeNota($notaId)) {
      $this->gestorProduccion->actualizarNota($notaId, $comentario);
      die(json_encode(['success' => true, 'message' => 'Nota actualizada correctamente.']));
    } else {
      if (empty($idUser)) {
        throw new Exception('Usuario no válido.');
      }
      $this->gestorProduccion->insertarNota($idUser, $comentario);
      die(json_encode(['success' => true, 'message' => 'Nota añadida correctamente.']));
    }
  }

  protected function processDeleteNota()
  {
    $notaId = (int)Tools::getValue('nota_id');

    if ($notaId <= 0 || !$this->gestorProduccion->existeNota($notaId)) {
      throw new Exception('La nota no existe o el ID es inválido.');
    }

    $this->gestorProduccion->deleteNota($notaId);
    die(json_encode(['success' => true, 'message' => 'Nota eliminada correctamente.']));
  }

  protected function processClienteNota()
  {
    $idUser = (int)Tools::getValue('cliente_nota');
    $comentario = trim(Tools::getValue('comentario'));

    // Validaciones
    if (empty($idUser)) {
      $this->context->smarty->assign('error_message', 'Selecciona un cliente válido.');
    } elseif (empty($comentario)) {
      $this->context->smarty->assign('error_message', 'El comentario no puede estar vacío.');
    } elseif (strlen($comentario) > 500) {
      $this->context->smarty->assign('error_message', 'El comentario es demasiado largo (máximo 500 caracteres).');
    } else {
      try {
        $comentario = strip_tags($comentario);
        $this->gestorProduccion->insertarNota($idUser, $comentario);
        $this->context->smarty->assign('success_message', 'Nota añadida correctamente.');
      } catch (Exception $e) {
        $this->context->smarty->assign('error_message', 'Error al añadir la nota: ' . $e->getMessage());
      }
    }
  }


  public function ajaxProcessLoadNotas()
  {
    try {
      $notas_con_reservas = $this->gestorProduccion->getNotasConReservas();
      $notas = $this->eliminarDuplicadosNotas($notas_con_reservas);

      $this->context->smarty->assign('notas', $notas);

      $html = $this->context->smarty->fetch(
        _PS_MODULE_DIR_ . 'gestorproduccion/views/templates/admin/_partials/notas_list.tpl'
      );

      die(json_encode([
        'success' => true,
        'html' => $html
      ]));
    } catch (Exception $e) {
      die(json_encode([
        'success' => false,
        'message' => 'Error al cargar notas: ' . $e->getMessage()
      ]));
    }
}

}