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
    
        // Asignar los productos a la plantilla
        $this->context->smarty->assign([
            'productos_sin_stock_y_fecha' => $productos_sin_stock_y_fecha,
            'productos_con_fecha' => $productos_con_fecha,
        ]);
    
        // Asigna la plantilla al backoffice
        $this->setTemplate('gestorproduccion.tpl');
    }
    

    private function getProductosSinStockYFecha()
{
    $sql = 'SELECT p.id_product, pl.name, 
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
    $sql = 'SELECT p.id_product, pl.name, 
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
                        $this->habilitarReservas($product['id_product'], $product['reference']);
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

private function habilitarReservas($product_id, $reference)
{
    // Obtener la instancia de PDO
    $pdo = Db::getInstance()->getLink();

    try {
        // Iniciar una transacción
        $pdo->beginTransaction();

        // Insertar o actualizar la tabla `product_reservation_enabled`
        $sqlReservationEnabled = 'INSERT INTO '._DB_PREFIX_.'product_reservation_enabled 
                                  (id_product, reference, is_enabled, date_enabled) 
                                  VALUES ('.(int)$product_id.', "'.pSQL($reference).'", 1, NOW()) 
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