<?php

require_once _PS_MODULE_DIR_ . 'gestorproduccion/config/config.php';

class GestorProduccionProductReservationModuleFrontController extends ModuleFrontController
{

    public function initContent()
{
    parent::initContent();

    // Asignar información de reservas y otros datos necesarios
    $product_id = (int)Tools::getValue('product_id');
    $reference = Tools::getValue('reference');
    $id_product_attribute = (int)Tools::getValue('id_product_attribute', 0);

    // Obtener lista de clientes del comercial logueado
    $id_comercial = (int)$this->context->customer->id; // ID del comercial logueado
    $customers = Db::getInstance()->executeS('SELECT id_customer, firstname, lastname FROM '._DB_PREFIX_.'customer WHERE id_comercial = '.(int)$id_comercial);

    // Obtener los productos habilitados para reservar
    $available_products = Db::getInstance()->executeS('
        SELECT p.id_product, p.reference, pl.name 
        FROM '._DB_PREFIX_.'product p
        JOIN '._DB_PREFIX_.'product_reservation_enabled pre ON p.id_product = pre.id_product
        LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.'
        WHERE pre.id_product IS NOT NULL
    ');

    // Verificar si hay productos disponibles para mostrar
    $no_products_message = empty($available_products) ? 'No hay productos disponibles para reservar en este momento.' : '';

    // Asignar variables al template
    $this->context->smarty->assign(array(
        'product_id' => $product_id,
        'reference' => $reference,
        'id_product_attribute' => $id_product_attribute,
        'customers' => $customers,
        'available_products' => $available_products,
        'no_products_message' => $no_products_message,
        'token' => Tools::getToken(),
    ));

    // Mostrar la plantilla con la estructura completa
    $this->setTemplate('module:gestorproduccion/views/templates/front/product_reservation.tpl');
}

    // Lógica de procesamiento de la reserva (por AJAX)
    public function postProcess()
    {
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
                        die(json_encode(['success' => false, 'message' => 'Error al guardar la reserva.']));  // Mensaje de error
                    }
                } catch (Exception $e) {
                    die(json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()])); 
                }
            } else {
                die(json_encode(['success' => false, 'message' => 'Datos inválidos.']));  // Mensaje de validación
            }
        }
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
