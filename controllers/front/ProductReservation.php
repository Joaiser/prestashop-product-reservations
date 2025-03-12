<?php

require_once _PS_MODULE_DIR_ . 'gestorproduccion/config/config.php';

class GestorProduccionProductReservationModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();

        $product_id = (int)Tools::getValue('product_id');
        $reference = Tools::getValue('reference');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute', 0);
        $id_comercial = (int)$this->context->customer->id; // ID del comercial logueado

        // Obtener reservas activas por cliente
        $reservas_por_cliente = $this->getReservasActivas($id_comercial);

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
            'reservas_por_cliente' => $reservas_por_cliente,
            'no_products_message' => $no_products_message,
            'token' => Tools::getToken(),
            'url_for_submission' => $this->context->link->getModuleLink('gestorproduccion', 'ProductReservation'),
        ));

        // Mostrar la plantilla con la estructura completa
        $this->setTemplate('module:gestorproduccion/views/templates/front/product_reservation.tpl');
    }

    // Lógica de procesamiento de la reserva (por AJAX)
    public function postProcess()
    {
        // Leer el cuerpo de la solicitud
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
    
        // Registrar los datos recibidos
        PrestaShopLogger::addLog('Datos recibidos en postProcess: ' . print_r($data, true), 1);
    
        // Verificar si la solicitud es AJAX
        if (isset($data['ajax']) && isset($data['products'])) {
            // Registrar que se detectó una solicitud AJAX
            PrestaShopLogger::addLog('Solicitud AJAX detectada.', 1);
    
            $products = $data['products']; // Obtener los productos del cuerpo de la solicitud
    
            // Registrar los productos recibidos
            PrestaShopLogger::addLog('Productos recibidos: ' . print_r($products, true), 1);
            
            $productosReservados = [];
    
            if (is_array($products)) {
                foreach ($products as $product) {
                    $product_id = (int)$product['product_id'];
                    $quantity = (int)$product['quantity'];
                    $id_customer = (int)$product['id_customer'];
                    $reference = isset($product['reference']) ? (string)$product['reference'] : ''; // Convertir a string
    
                    // Asegurarnos de que 'reference' no sea un array vacío u objeto
                    if (is_array($reference)) {
                        $reference = ''; // Puedes asignar un valor predeterminado si no es válido
                    }
                    $id_product_attribute = (int)$product['id_product_attribute'];
    
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
                               $productosReservados[] = [
                                    'product_id' => $product_id,
                                    'quantity' => $quantity,
                                    'id_customer' => $id_customer,
                                    'id_comercial' => $id_comercial
                                ];
    
                                // Enviar el correo a los comerciales
                                $this->sendReservationEmail($productosReservados, $id_customer, $id_comercial);

                                // Enviar el correo al correo general del jefe
                                $this->sendEmailToAddress($productosReservados, $id_customer, FIXED_EMAIL);

                            } else {
                                // Registrar el error en la base de datos
                                PrestaShopLogger::addLog('Error al guardar la reserva en la base de datos.', 3);
                                die(json_encode(['success' => false, 'message' => 'Error al guardar la reserva.']));  // Mensaje de error
                            }
                        } catch (Exception $e) {
                            // Registrar la excepción
                            PrestaShopLogger::addLog('Error en la base de datos: ' . $e->getMessage(), 3);
                            die(json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()])); 
                        }
                    } else {
                        // Registrar datos inválidos
                        PrestaShopLogger::addLog('Datos inválidos para el producto ' . $product_id, 2);
                        die(json_encode(['success' => false, 'message' => 'Datos inválidos para el producto ' . $product_id]));  // Mensaje de validación
                    }
                }
    
                // Respuesta exitosa después de procesar todos los productos
                PrestaShopLogger::addLog('Reserva realizada con éxito.', 1);
                die(json_encode(['success' => true]));
            } else {
                // Registrar formato de datos inválido
                PrestaShopLogger::addLog('Formato de datos inválido.', 2);
                die(json_encode(['success' => false, 'message' => 'Formato de datos inválido.'])); // Mensaje de error si no es un array
            }
        } else {
            // Registrar que no se detectó una solicitud AJAX
            PrestaShopLogger::addLog('No se detectó una solicitud AJAX.', 2);
    
            // Si no es una solicitud AJAX, mostrar la página completa
            parent::postProcess();
        }
    }

    // Función para mostrar las reservas activas por comercial
    public function getReservasActivas($id_comercial)
    {
        $sql = 'SELECT pr.id_reservation, pr.id_product, pr.id_product_attribute, pr.reference, pr.status, pr.reservation_expiry, pr.reserved_stock, pr.id_comercial, pr.id_customer, pr.date_added
                FROM '._DB_PREFIX_.'product_reservations pr
                WHERE pr.id_comercial = '.(int)$id_comercial.' 
                AND pr.status = "pendiente"
                ORDER BY pr.id_customer, pr.date_added ASC';

        $reservas_activas = Db::getInstance()->executeS($sql);

        if (!$reservas_activas) {
            return [];
        }

        $reservas_por_cliente = [];

        foreach ($reservas_activas as $reserva) {
            $id_cliente = $reserva['id_customer'];

            if (!isset($reservas_por_cliente[$id_cliente])) {
                $reservas_por_cliente[$id_cliente] = [
                    'nombre_cliente' => $this->getClientName($id_cliente),
                    'reservas' => []
                ];
            }

            $reservas_por_cliente[$id_cliente]['reservas'][] = $reserva;
        }

        return $reservas_por_cliente;
    }

    // Función auxiliar para obtener el nombre del cliente
    private function getClientName($id_customer) {
        $sql = 'SELECT firstname, lastname FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_customer;
        $result = Db::getInstance()->getRow($sql);

        if ($result) {
            return $result['firstname'] . ' ' . $result['lastname'];
        }
        return 'Cliente no encontrado';
    }
    


    // Función para enviar el correo a los comerciales
    private function sendReservationEmail($productosReservados, $id_customer, $id_comercial)
    {
        try {
            if (!$id_customer || !$id_comercial) {
                throw new Exception('ID de cliente o comercial no válido.');
            }
    
            $customerName = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_customer);
            $comercialName = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_comercial);
    
            if (!$customerName || !$comercialName) {
                throw new Exception('No se pudo obtener el nombre del cliente o del comercial.');
            }
    
            if (empty($productosReservados)) {
                throw new Exception('No hay productos reservados.');
            }
    
            $productosTexto = "";
            foreach ($productosReservados as $producto) {
                if (!isset($producto['product_id'], $producto['reference'], $producto['quantity'])) {
                    continue; // Saltar productos con datos incompletos
                }
    
                $productName = Db::getInstance()->getValue('SELECT name FROM '._DB_PREFIX_.'product_lang WHERE id_product = '.(int)$producto['product_id'].' AND id_lang = '.(int)$this->context->language->id);
    
                if (!$productName) {
                    $productName = 'Producto desconocido'; // En caso de error en la consulta
                }
    
                $productosTexto .= "Producto: {$productName} (Ref: {$producto['reference']}), Cantidad: {$producto['quantity']}\n";
            }
    
            if (empty($productosTexto)) {
                throw new Exception('No se pudo obtener la información de los productos.');
            }
    
            $templateVars = [
                '{comercial_name}' => $comercialName,
                '{customer_name}' => $customerName,
                '{products}' => nl2br($productosTexto)
            ];
    
            $mailSent = Mail::Send(
                (int)$this->context->language->id,
                'reservation_notification',
                'Nueva Reserva de Productos',
                $templateVars,
                'correo@empresa.com',
                null,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_ . 'gestorproduccion/mails/',
                false,
                null
            );
    
            if (!$mailSent) {
                throw new Exception('Error al enviar el correo.');
            }
    
            PrestaShopLogger::addLog('Correo de reserva enviado correctamente.', 1);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error en sendReservationEmail: ' . $e->getMessage(), 3);
        }
    }
    
    

    // Función auxiliar para enviar el correo a una dirección específica
    private function sendEmailToAddress($productosReservados, $id_customer, $email)
{
    try {
        if (!$id_customer) {
            throw new Exception('ID de cliente no válido.');
        }

        $customerName = Db::getInstance()->getValue('SELECT CONCAT(firstname, " ", lastname) FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int)$id_customer);
        
        if (!$customerName) {
            throw new Exception('No se pudo obtener el nombre del cliente.');
        }

        if (empty($productosReservados)) {
            throw new Exception('No hay productos reservados.');
        }

        $productosTexto = "";
        foreach ($productosReservados as $producto) {
            if (!isset($producto['product_id'], $producto['reference'], $producto['quantity'])) {
                continue; // Saltar productos con datos incompletos
            }

            $productName = Db::getInstance()->getValue('SELECT name FROM '._DB_PREFIX_.'product_lang WHERE id_product = '.(int)$producto['product_id'].' AND id_lang = '.(int)$this->context->language->id);

            if (!$productName) {
                $productName = 'Producto desconocido'; // En caso de error en la consulta
            }

            $productosTexto .= "Producto: {$productName} (Ref: {$producto['reference']}), Cantidad: {$producto['quantity']}\n";
        }

        if (empty($productosTexto)) {
            throw new Exception('No se pudo obtener la información de los productos.');
        }

        $templateVars = [
            '{customer_name}' => $customerName,
            '{products}' => nl2br($productosTexto)
        ];

        $mailSent = Mail::Send(
            (int)$this->context->language->id,
            'reservation_email_jefe',
            'Nueva Reserva de Productos',
            $templateVars,
            $email,
            null,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'gestorproduccion/mails/',
            false,
            null
        );

        if (!$mailSent) {
            throw new Exception('Error al enviar el correo.');
        }

        PrestaShopLogger::addLog('Correo de reserva enviado al jefe correctamente.', 1);
    } catch (Exception $e) {
        PrestaShopLogger::addLog('Error en sendEmailToAddress: ' . $e->getMessage(), 3);
    }
}

}