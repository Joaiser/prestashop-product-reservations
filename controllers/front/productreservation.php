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
            die(json_encode(['success' => false, 'message' => 'Token inválido.']));
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
                    
                       // Obtener los correos de los comerciales
$commercials = Db::getInstance()->executeS('SELECT email FROM '._DB_PREFIX_.'customer WHERE id_customer = '.$id_comercial);

// Obtener el nombre del producto y cliente
$productName = Db::getInstance()->getValue('SELECT name FROM '._DB_PREFIX_.'product_lang WHERE id_product = '.(int)$product_id.' AND id_lang = '.(int)$this->context->language->id);
$customerName = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_customer);
$storeName = 'Salamandra Luz';  // Para este caso, el nombre de la tienda es fijo

// Enviar el correo a cada comercial
foreach ($commercials as $commercial) {
    // Crear el contenido del correo
    $mailData = array(
        '{product_name}' => $productName,
        '{customer_name}' => $customerName,
        '{reserved_quantity}' => $quantity,
        '{store_name}' => $storeName,
    );

    // Obtener el código del idioma actual (por ejemplo, "es" o "en")
    $languageCode = $this->context->language->iso_code;

    // Ruta de la plantilla específica para el idioma actual
    $templatePath = _PS_MODULE_DIR_ . 'gestorproduccion/mails/' . $languageCode . '/';
    $templateFile = $templatePath . 'reservation_email_template.html';

    PrestaShopLogger::addLog('Ruta de la plantilla: ' . $templatePath, 1);
    PrestaShopLogger::addLog('Archivo de plantilla: ' . $templateFile, 1);

    if (!file_exists($templateFile)) {
        PrestaShopLogger::addLog('Plantilla de correo no encontrada en: ' . $templateFile, 3);
        die(json_encode(['success' => false, 'message' => 'Plantilla de correo no encontrada.']));
    }

    // Enviar el correo
    $mailSent = Mail::Send(
        $this->context->language->id,
        'reservation_email_template', // Solo el nombre de la plantilla
        'Nueva reserva de producto',
        $mailData,
        $commercial['email'],
        null,
        null,
        null,
        $templatePath, // Ruta de la plantilla (específica para el idioma actual)
        null,
        false
    );

    if (!$mailSent) {
        PrestaShopLogger::addLog('Error al enviar el correo a: ' . $commercial['email'], 3);
    }
}


                        // Respuesta exitosa después de enviar todos los correos
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