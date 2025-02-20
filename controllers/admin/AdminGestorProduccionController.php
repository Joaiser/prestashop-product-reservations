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

    // Asignar los productos a la plantilla
    $this->context->smarty->assign([
        'productos_sin_stock_y_fecha' => $productos_sin_stock_y_fecha,
    ]);

    // Asigna la plantilla al backoffice
    $this->setTemplate('gestorproduccion.tpl');
}


    private function getProductosSinStockYFecha()
{
    $sql = 'SELECT p.id_product, pl.name, 
                   IFNULL(pa.reference, p.reference) AS reference
            FROM `'._DB_PREFIX_.'product` p
            INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON p.id_product = pl.id_product
            LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON p.id_product = pa.id_product
            LEFT JOIN `'._DB_PREFIX_.'stock_available` sa 
                ON (p.id_product = sa.id_product AND (pa.id_product_attribute = sa.id_product_attribute OR pa.id_product_attribute IS NULL))
            WHERE pl.id_lang = '.(int)$this->context->language->id.'
            AND (sa.quantity <= 0 OR sa.quantity IS NULL)
            AND (p.available_date IS NULL OR p.available_date = "0000-00-00")';

    return Db::getInstance()->executeS($sql);
}


public function postProcess()
{
    // Asegúrate de que la solicitud sea de tipo POST
    if (Tools::isSubmit('submit')) {
        try {
            // Obtenemos los datos de la petición
            $json_data = Tools::getValue('products');
            if ($json_data) {
                // Convertir los datos de JSON a un array PHP
                $products = json_decode($json_data, true); 

                if (is_array($products)) {
                    foreach ($products as $productId) {
                        // Lógica para habilitar reservas
                        $this->habilitarReservas($productId);
                    }
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception("Los datos del producto no son válidos.");
                }
            } else {
                throw new Exception("No se recibieron datos de productos.");
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error_message' => $e->getMessage()]);
        }
        exit;
    }
}


private function habilitarReservas($product_id)
{
    $sql = 'UPDATE `'._DB_PREFIX_.'product` 
            SET available_for_order = 1 
            WHERE id_product = '.(int)$product_id;

    Db::getInstance()->execute($sql);
}
 
}
