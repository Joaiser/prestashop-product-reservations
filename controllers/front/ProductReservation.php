<?php

require_once _PS_MODULE_DIR_ . 'gestorproduccion/config/config.php';

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

        // Verificar si la solicitud es AJAX para procesar la reserva
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
                    $result = Db::getInstance()->execute($sql);

                    if ($result) {
                        // Llamar a la función para enviar el correo a los comerciales
                        $this->sendReservationEmail($product_id, $quantity, $id_customer, $id_comercial);

                        // Respuesta exitosa
                        die(json_encode(['success' => true]));
                    } else {
                        die(json_encode(['success' => false, 'message' => 'Error al guardar la reserva.']));
                    }
                } catch (Exception $e) {
                    die(json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]));
                }
            } else {
                die(json_encode(['success' => false, 'message' => 'Datos inválidos.']));
            }
        }

        // Mostrar el formulario de reserva
        $product_id = (int)Tools::getValue('product_id');
        $reference = Tools::getValue('reference');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute', 0);

        // Obtener lista de clientes
        $customers = Db::getInstance()->executeS('SELECT id_customer, firstname, lastname FROM '._DB_PREFIX_.'customer');

        // Asignar variables al template
        $this->context->smarty->assign(array(
            'product_id' => $product_id,
            'reference' => $reference,
            'id_product_attribute' => $id_product_attribute,
            'customers' => $customers,
            'token' => Tools::getToken(),
        ));

        // Mostrar el formulario en el frontend
        $this->setTemplate('module:gestorproduccion/views/templates/front/productReservation.tpl');
    }

    // Función para enviar el correo a los comerciales
    private function sendReservationEmail($product_id, $quantity, $id_customer, $id_comercial)
    {
        try {
            // Obtener el nombre del producto y cliente
            $productName = Db::getInstance()->getValue('SELECT name FROM '._DB_PREFIX_.'product_lang WHERE id_product = '.(int)$product_id.' AND id_lang = '.(int)$this->context->language->id);
            $customerName = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_customer);
            $comercialName = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_comercial);
            $storeName = 'Salamandra Luz';  // Nombre de la tienda

            // Crear el contenido del correo
            $mailData = array(
                '{product_name}' => $productName,
                '{customer_name}' => $customerName,
                '{reserved_quantity}' => $quantity,
                '{store_name}' => $storeName,
                '{comercial_name}' => $comercialName,
            );

            // Obtener el email de los comerciales
            $commercials = Db::getInstance()->executeS('SELECT email FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_comercial);

            // Verificar si se encontraron correos electrónicos
            if (!$commercials || empty($commercials)) {
                die(json_encode(['success' => false, 'message' => 'No se encontró correo del comercial.']));
            }

            $fixedEmail = FIXED_EMAIL; // Correo fijo 

            $this->sendEmailToAddress($mailData, $fixedEmail);

            foreach ($commercials as $commercial) {
                if (empty($commercial['email'])) {
                    continue;
                }

                // Enviar el correo utilizando la plantilla
                $subject = 'Nueva reserva de producto';

                // Enviar el correo utilizando la plantilla HTML y el texto plano
                $mailSent = Mail::Send(
                    $this->context->language->id,
                    'reservation_email_template',               
                    $subject,                        
                    $mailData,                                    
                    $commercial['email'],                         
                    null,                                         
                    null,                                         
                    null,                                         
                    null,                                         
                    null,                                         
                    false                                         
                );

                if (!$mailSent) {
                    throw new Exception('Mail::Send devolvió false.');
                } 
            }
        } catch (Exception $e) {
            die(json_encode(['success' => false, 'message' => 'Error al enviar el correo: ' . $e->getMessage()]));
        }
    }

    // Función auxiliar para enviar el correo a una dirección específica
    private function sendEmailToAddress($mailData, $email)
    {
        try {
            $subject = 'Nueva reserva de producto';

            // Enviar el correo utilizando la plantilla HTML y el texto plano
            $mailSent = Mail::Send(
                $this->context->language->id,
                'reservation_email_jefe',               
                $subject,                        
                $mailData,                                    
                $email,                          
                null,                                         
                null,                                         
                null,                                         
                null,                                         
                null,                                         
                false                                         
            );

            if (!$mailSent) {
                throw new Exception('Mail::Send devolvió false.');
            } 
        } catch (Exception $e) {
            throw new Exception('Error al enviar el correo a ' . $email . ': ' . $e->getMessage());
        }
    }

}
