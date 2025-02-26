<?php 

class GestorProduccionProductReservationModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Verificar si la solicitud es AJAX
        if (Tools::isSubmit('product_id') && Tools::isSubmit('quantity')) {
            $product_id = (int)Tools::getValue('product_id');
            $reference = Tools::getValue('reference');
            $quantity = (int)Tools::getValue('quantity');

            // Validar los datos
            if ($product_id > 0 && $quantity > 0) {
                // Insertar la reserva en la base de datos
                $sql = 'INSERT INTO '._DB_PREFIX_.'product_reservations 
                        (id_product, reference, quantity, date_added) 
                        VALUES ('.(int)$product_id.', "'.pSQL($reference).'", '.(int)$quantity.', NOW())';
                $result = Db::getInstance()->execute($sql);

                if ($result) {
                    PrestaShopLogger::addLog('Reserva realizada correctamente para el producto ID ' . $product_id, 1);
                    die(json_encode(['success' => true]));
                } else {
                    PrestaShopLogger::addLog('Error al guardar la reserva para el producto ID ' . $product_id, 3);
                    die(json_encode(['success' => false, 'message' => 'Error al guardar la reserva.']));
                }
            } else {
                PrestaShopLogger::addLog('Datos inválidos para la reserva', 3);
                die(json_encode(['success' => false, 'message' => 'Datos inválidos.']));
            }
        }

        PrestaShopLogger::addLog('Solicitud inválida en ProductReservationController', 3);
        die(json_encode(['success' => false, 'message' => 'Solicitud inválida.']));
    }
}