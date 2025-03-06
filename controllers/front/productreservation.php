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
            PrestaShopLogger::addLog('Token inválido recibido. Token: ' . $token, 3);
            die(json_encode(['success' => false, 'message' => 'Token inválido.'])); // Error si el token no es válido
        }

        // Verificar si la solicitud es AJAX
        if (Tools::isSubmit('product_id') && Tools::isSubmit('quantity') && Tools::isSubmit('id_customer')) {
            $product_id = (int)Tools::getValue('product_id');
            $quantity = (int)Tools::getValue('quantity');
            $id_customer = (int)Tools::getValue('id_customer');
            $reference = pSQL(Tools::getValue('reference'));
            $id_product_attribute = (int)Tools::getValue('id_product_attribute', 0); // Combinación (opcional)

            // Obtener el ID del comercial logueado
            $id_comercial = $this->context->customer->id;

            // Validar los datos
            if ($product_id > 0 && $quantity > 0 && $id_customer > 0) {
                try {
                    // Insertar la reserva en la base de datos
                    $sql = 'INSERT INTO '._DB_PREFIX_.'product_reservations 
                    (id_product, id_product_attribute, reference, reserved_stock, id_comercial, id_customer, date_added) 
                    VALUES (
                        '.(int)$product_id.', 
                        '.(int)$id_product_attribute.', 
                        "'.pSQL($reference).'", 
                        '.(int)$quantity.', 
                        '.(int)$id_comercial.', 
                        '.(int)$id_customer.', 
                        NOW()
                    )';
                    PrestaShopLogger::addLog('Consulta SQL para insertar reserva: ' . $sql, 1);
                    $result = Db::getInstance()->execute($sql);

                    if ($result) {
                        PrestaShopLogger::addLog('Reserva guardada correctamente para el producto ID: ' . $product_id, 1);

                        // Respuesta exitosa sin enviar correos
                        die(json_encode(['success' => true]));
                    } else {
                        PrestaShopLogger::addLog('Error al guardar la reserva. Producto ID: ' . $product_id, 3);
                        die(json_encode(['success' => false, 'message' => 'Error al guardar la reserva.']));
                    }
                } catch (Exception $e) {
                    PrestaShopLogger::addLog('Error en la consulta SQL: ' . $e->getMessage(), 3);
                    die(json_encode(['success' => false, 'message' => 'Error en la base de datos.']));
                }
            } else {
                PrestaShopLogger::addLog('Datos inválidos en la solicitud: Producto ID: ' . $product_id . ', Cantidad: ' . $quantity . ', ID Cliente: ' . $id_customer, 3);
                die(json_encode(['success' => false, 'message' => 'Datos inválidos.']));
            }
        }

        PrestaShopLogger::addLog('Solicitud inválida recibida (sin parámetros).', 3);
        die(json_encode(['success' => false, 'message' => 'Solicitud inválida.']));
    }
}
