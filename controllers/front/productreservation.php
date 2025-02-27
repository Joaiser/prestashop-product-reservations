<?php

class GestorProduccionProductReservationModuleFrontController extends ModuleFrontController
{
    public function initContent()
{
    parent::initContent();

    // Verificar el token CSRF
    $token = Tools::getValue('token');
    $expectedToken = Tools::getToken();

    if ($token !== $expectedToken) {
        die(json_encode(['success' => false, 'message' => 'Token inválido.']));
    }

    // Verificar si la solicitud es AJAX
    if (Tools::isSubmit('product_id') && Tools::isSubmit('quantity') && Tools::isSubmit('reference')) {
        $product_id = (int)Tools::getValue('product_id');
        $quantity = (int)Tools::getValue('quantity');
        $reference = pSQL(Tools::getValue('reference')); // Obtener y sanitizar la referencia

        // Validar los datos
        if ($product_id > 0 && $quantity > 0 && !empty($reference)) {
            try {
                // Insertar la reserva en la base de datos
                $sql = 'INSERT INTO '._DB_PREFIX_.'product_reservations 
                        (id_product, reference, reserved_stock, date_added) 
                        VALUES ('.(int)$product_id.', "'.$reference.'", '.(int)$quantity.', NOW())';
                $result = Db::getInstance()->execute($sql);

                if ($result) {
                    die(json_encode(['success' => true]));
                } else {
                    die(json_encode(['success' => false, 'message' => 'Error al guardar la reserva.']));
                }
            } catch (Exception $e) {
                PrestaShopLogger::addLog('Error en la consulta SQL: ' . $e->getMessage(), 3);
                die(json_encode(['success' => false, 'message' => 'Error en la base de datos.']));
            }
        } else {
            die(json_encode(['success' => false, 'message' => 'Datos inválidos.']));
        }
    }

    die(json_encode(['success' => false, 'message' => 'Solicitud inválida.']));
}
}