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

}