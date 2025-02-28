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
    $sql = 'SELECT pr.*, pl.name AS product_name, c.firstname AS customer_firstname, c.lastname AS customer_lastname
            FROM '._DB_PREFIX_.'product_reservations pr
            LEFT JOIN '._DB_PREFIX_.'product p ON pr.id_product = p.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
            LEFT JOIN '._DB_PREFIX_.'customer c ON pr.id_customer = c.id_customer
            WHERE pr.status = "pendiente" 
            AND pl.id_lang = '.(int)$this->context->language->id; // Asegúrate de que se filtre por el idioma actual

    return Db::getInstance()->executeS($sql);
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





public function postProcess()
{
    if (Tools::isSubmit('submit')) {
        try {
            // Obtenemos los datos de la petición
            $json_data = Tools::getValue('products');
            if ($json_data) {
                // Convertir los datos de JSON a un array PHP
                $products = json_decode($json_data, true);

                if (is_array($products)) {
                    foreach ($products as $product) {
                        // Lógica para habilitar reservas
                        $this->habilitarReservas(
                            (int)$product['id_product'],
                            (int)$product['id_product_attribute'], 
                            pSQL($product['reference'])
                        );
                    }
                    die(json_encode(['success' => true])); // Devuelve un JSON
                } else {
                    throw new Exception("Los datos del producto no son válidos.");
                }
            } else {
                throw new Exception("No se recibieron datos de productos.");
            }
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'error_message' => $e->getMessage()])); // Devuelve un JSON
        }
    }
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

}