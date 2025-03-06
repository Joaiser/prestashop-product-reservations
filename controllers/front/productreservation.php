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
                    
                        // Obtener el código del idioma actual
                        $languageCode = $this->context->language->iso_code;

                        // Ruta de la plantilla específica para el idioma actual
                        $templatePath = _PS_THEME_DIR_ . 'mails/' . $languageCode . '/';

                        // Asegúrate de que las plantillas .html y .txt existan
                        $templateFileHtml = $templatePath . 'reservation_email_template.html';
                        $templateFileTxt = $templatePath . 'reservation_email_template.txt';

                        PrestaShopLogger::addLog('Ruta completa para archivo HTML: ' . $templateFileHtml, 1);
                        PrestaShopLogger::addLog('Archivo HTML existe: ' . (file_exists($templateFileHtml) ? 'Sí' : 'No'), 1);
                        PrestaShopLogger::addLog('Ruta completa para archivo TXT: ' . $templateFileTxt, 1);
                        PrestaShopLogger::addLog('Archivo TXT existe: ' . (file_exists($templateFileTxt) ? 'Sí' : 'No'), 1);

                        // Validar que ambos archivos existen
                        if (!file_exists($templateFileHtml)) {
                            PrestaShopLogger::addLog('Plantilla HTML de correo no encontrada en: ' . $templateFileHtml, 3);
                            die(json_encode(['success' => false, 'message' => 'Plantilla HTML no encontrada.']));
                        }
                        if (!file_exists($templateFileTxt)) {
                            PrestaShopLogger::addLog('Plantilla TXT de correo no encontrada en: ' . $templateFileTxt, 3);
                            die(json_encode(['success' => false, 'message' => 'Plantilla TXT no encontrada.']));
                        }

                        // Obtener el nombre del producto y cliente
                        $productName = Db::getInstance()->getValue('SELECT name FROM '._DB_PREFIX_.'product_lang WHERE id_product = '.(int)$product_id.' AND id_lang = '.(int)$this->context->language->id);
                        $customerName = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_customer);
                        $storeName = 'Salamandra Luz';  // Para este caso, el nombre de la tienda es fijo

                        // Crear el contenido del correo
                        $mailData = array(
                            '{product_name}' => $productName,
                            '{customer_name}' => $customerName,
                            '{reserved_quantity}' => $quantity,
                            '{store_name}' => $storeName,
                        );

                        // Obtener el email de los comerciales
                        $commercials = Db::getInstance()->executeS('SELECT email FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_comercial);

                        // Verificar si se encontraron correos electrónicos
                        if (!$commercials || empty($commercials)) {
                            PrestaShopLogger::addLog('No se encontró ningún correo para el comercial con ID: ' . (int)$id_comercial, 3);
                            die(json_encode(['success' => false, 'message' => 'No se encontró correo del comercial.']));
                        }

                        foreach ($commercials as $commercial) {
                            if (empty($commercial['email'])) {
                                PrestaShopLogger::addLog('Correo vacío o NULL para el comercial con ID: ' . (int)$id_comercial, 3);
                                continue;
                            }
                        
                            PrestaShopLogger::addLog('Intentando enviar correo a: ' . $commercial['email'], 1);
                        
                            try {
                                // Enviar un correo simple sin plantillas personalizadas
                                $subject = 'Nueva reserva de producto';
                                $message = 'Se ha realizado una nueva reserva del producto ' . $productName . 
                                           ' por el cliente ' . $customerName . 
                                           ' con una cantidad de ' . $quantity . ' unidades.';
                        
                                $mailSent = Mail::Send(
                                    $this->context->language->id,
                                    '', // No usar plantilla personalizada
                                    $subject,
                                    $message, // Mensaje en texto plano
                                    $commercial['email'],
                                    null,
                                    null,
                                    null,
                                    null, // No usar ruta de plantilla
                                    null,
                                    false
                                );
                        
                                if (!$mailSent) {
                                    throw new Exception('Mail::Send devolvió false.');
                                } else {
                                    PrestaShopLogger::addLog('Correo enviado correctamente a: ' . $commercial['email'], 1);
                                }
                            } catch (Exception $e) {
                                PrestaShopLogger::addLog('Error al enviar el correo a: ' . $commercial['email'] . ' - ' . $e->getMessage(), 3);
                                die(json_encode(['success' => false, 'message' => 'Error al enviar el correo: ' . $e->getMessage()]));
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
                    die(json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]));
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