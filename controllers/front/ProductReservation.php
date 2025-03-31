<?php

require_once _PS_MODULE_DIR_ . 'gestorproduccion/config/config.php';

class GestorProduccionProductReservationModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        parent::initContent();
    
        $view = Tools::getValue('view', 'reserve'); // Por defecto, mostrar la reserva
    
        $product_id = (int)Tools::getValue('product_id');
        $reference = Tools::getValue('reference');
        $id_product_attribute = (int)Tools::getValue('id_product_attribute', 0);
        $id_comercial = (int)$this->context->customer->id; // ID del comercial logueado
    
        // Obtener reservas activas por cliente
        $reservas_por_cliente = $this->getReservasActivas($id_comercial);
        
        // Obtener notas solo si hay reservas
        $notas_clientes = !empty($reservas_por_cliente) 
            ? $this->getNotasClientes(array_keys($reservas_por_cliente))
            : [];
    
        $customers = Db::getInstance()->executeS('
            SELECT id_customer, firstname, lastname 
            FROM '._DB_PREFIX_.'customer 
            WHERE id_comercial = '.(int)$id_comercial
        );
    
        // Obtener productos habilitados para reservar
        $available_products = Db::getInstance()->executeS('
            SELECT 
                p.id_product, 
                IFNULL(pa.reference, p.reference) AS reference,
                pl.name, 
                pre.id_product_attribute,
                IFNULL(pa.reference, "") AS attribute_reference 
            FROM '._DB_PREFIX_.'product p
            JOIN '._DB_PREFIX_.'product_reservation_enabled pre ON p.id_product = pre.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product 
                AND pl.id_lang = '.(int)$this->context->language->id.'
            LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON pre.id_product_attribute = pa.id_product_attribute
            WHERE pre.is_enabled = 1
        ');
    
        // Verificar si hay productos disponibles para mostrar
        $no_products_message = empty($available_products) 
            ? 'No hay productos disponibles para reservar en este momento.' 
            : '';
    
        // Asignar variables al template
        $this->context->smarty->assign([
            'product_id' => $product_id,
            'reference' => $reference,
            'id_product_attribute' => $id_product_attribute,
            'customers' => $customers,
            'available_products' => $available_products,
            'reservas_por_cliente' => $reservas_por_cliente,
            'no_products_message' => $no_products_message,
            'token' => Tools::getToken(),
            'notas_clientes' => $notas_clientes, // Corregido el nombre de la variable
            'url_for_submission' => $this->context->link->getModuleLink('gestorproduccion', 'ProductReservation'),
        ]);
    
        // Definir qué plantilla cargar según el parámetro "view"
        if ($view === 'reservations') {
            $this->setTemplate('module:gestorproduccion/views/templates/front/view_my_reservations.tpl');
        } else {
            $this->setTemplate('module:gestorproduccion/views/templates/front/product_reservation.tpl');
        }
    }
    
    protected function getNotasClientes($ids_clientes)
    {
        if (empty($ids_clientes)) {
            return [];
        }
    
        // Prepara los IDs para la consulta SQL
        $ids_limpios = array_map('intval', $ids_clientes);
        $lista_ids = implode(',', $ids_limpios);
    
        $sql = 'SELECT id_user, nota 
                FROM notas_reservas 
                WHERE id_user IN ('.$lista_ids.')
                ORDER BY id_nota DESC';
        
        $notas = Db::getInstance()->executeS($sql);
        
        if (empty($notas)) {
            return [];
        }
        
        $agrupadas = [];
        foreach ($notas as $nota) {
            if (!isset($agrupadas[$nota['id_user']])) {
                $agrupadas[$nota['id_user']] = [];
            }
            $agrupadas[$nota['id_user']][] = $nota['nota'];
        }
        
        return $agrupadas;
    }


    // Lógica de procesamiento de la reserva (por AJAX)
    public function postProcess()
{
    // Leer el cuerpo de la solicitud
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Verificar si la solicitud es AJAX
    if (isset($data['ajax']) && isset($data['products'])) {
        $products = $data['products']; // Obtener los productos del cuerpo de la solicitud


        $productosReservados = []; // Array para almacenar los productos reservados

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
                            // Agregar el producto a la lista de reservados
                            $productosReservados[] = [
                                'product_id' => $product_id,
                                'quantity' => $quantity,
                                'id_customer' => $id_customer,
                                'id_comercial' => $id_comercial,
                                'reference' => $reference
                            ];
                        } else {
                            // Registrar el error en la base de datos
                            die(json_encode(['success' => false, 'message' => 'Error al guardar la reserva.']));  // Mensaje de error
                        }
                    } catch (Exception $e) {
                        // Registrar la excepción
                        die(json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()])); 
                    }
                } else {
                    // Registrar datos inválidos
                    die(json_encode(['success' => false, 'message' => 'Datos inválidos para el producto ' . $product_id]));  // Mensaje de validación
                }
            }

            // Enviar el correo solo si hay productos reservados
            if (!empty($productosReservados)) {
                $this->sendReservationEmail($productosReservados, $id_customer, $id_comercial);
                // $this->sendEmailToAddress($productosReservados, $id_customer, FIXED_EMAIL); // Descomenta si necesitas enviar al correo general
            } 
            die(json_encode(['success' => true]));
        } else {
            die(json_encode(['success' => false, 'message' => 'Formato de datos inválido.'])); // Mensaje de error si no es un array
        }
    } else {
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
        // Verificar que los IDs de cliente y comercial sean válidos
        if (!$id_customer || !$id_comercial) {
            throw new Exception('ID de cliente o comercial no válido.');
        }

        // Obtener el nombre del cliente
        $customerName = Db::getInstance()->getValue(
            'SELECT CONCAT(firstname, " ", lastname) 
             FROM '._DB_PREFIX_.'customer 
             WHERE id_customer = '.(int)$id_customer
        );

        // Obtener el nombre y correo del comercial
        $comercialData = Db::getInstance()->getRow(
            'SELECT CONCAT(firstname, " ", lastname) AS name, email 
             FROM '._DB_PREFIX_.'customer 
             WHERE id_customer = '.(int)$id_comercial
        );

        // Verificar que se obtuvieron los datos del cliente y comercial
        if (!$customerName || !$comercialData) {
            throw new Exception('No se pudo obtener el nombre del cliente o del comercial.');
        }

        $comercialName = $comercialData['name']; // Nombre del comercial
        $comercialEmail = $comercialData['email']; // Correo del comercial
        $shopName = Configuration::get('PS_SHOP_NAME');

        // Verificar que hay productos reservados
        if (empty($productosReservados)) {
            throw new Exception('No hay productos reservados.');
        }

        // Variable para almacenar el texto de los productos
        $productosTexto = "";

        // Recorrer los productos reservados
        foreach ($productosReservados as $producto) {
            // Verificar que los campos mínimos estén presentes
            if (!isset($producto['product_id'], $producto['quantity'], $producto['id_customer'])) {
                PrestaShopLogger::addLog('Producto con datos incompletos: ' . print_r($producto, true), 2);
                continue; // Saltar productos con datos incompletos
            }

            // Obtener el nombre del producto
            $productName = Db::getInstance()->getValue(
                'SELECT name 
                 FROM '._DB_PREFIX_.'product_lang 
                 WHERE id_product = '.(int)$producto['product_id'].' 
                 AND id_lang = '.(int)$this->context->language->id
            );

            // Si no se obtiene el nombre, usar un valor predeterminado
            if (!$productName) {
                $productName = 'Producto desconocido';
            }

            // Obtener la referencia del producto (si está disponible)
            $reference = isset($producto['reference']) ? $producto['reference'] : 'Sin referencia';

            // Construir el texto del producto
            $productosTexto .= "Producto: {$productName} (Ref: {$reference}), Cantidad: {$producto['quantity']}\n";
        }

        // Verificar que se generó texto para los productos
        if (empty($productosTexto)) {
            throw new Exception('No se pudo obtener la información de los productos.');
        }

        // Preparar las variables para el correo
        $templateVars = [
            '{comercial_name}' => $comercialName,
            '{customer_name}' => $customerName,
            '{products}' => nl2br($productosTexto),
            '{store_name}' => $shopName
        ];

        // Enviar el correo al comercial
        $mailSentToComercial = Mail::Send(
            (int)$this->context->language->id,
            'reservation_email_template', // Plantilla de correo
            'Nueva Reserva de Productos', // Asunto del correo
            $templateVars, // Variables para la plantilla
            $comercialEmail, // Correo del comercial
            $comercialName, // Nombre del comercial
            null, // Desde (opcional)
            null, // Desde nombre (opcional)
            null, // Adjuntos (opcional)
            null, // SMTP (opcional)
            _PS_MODULE_DIR_ . 'gestorproduccion/mails/', // Ruta de plantillas
            false, // No usar SMTP
            null // ID de la tienda (opcional)
        );

        /// Enviar el correo al correo general (jefe o administrador)
        $mailSentToGeneral = Mail::Send(
           (int)$this->context->language->id,
           'reservation_email_jefe', // Plantilla de correo
           'Nueva Reserva de Productos', // Asunto del correo
           $templateVars, // Variables para la plantilla
           FIXED_EMAIL, // Correo general
           null, // Nombre del destinatario (opcional)
           null, // Desde (opcional)
           null, // Desde nombre (opcional)
           null, // Adjuntos (opcional)
           null, // SMTP (opcional)
           _PS_MODULE_DIR_ . 'gestorproduccion/mails/', // Ruta de plantillas
           false, // No usar SMTP
           null // ID de la tienda (opcional)
        );

        $mailSentToGeneral = Mail::Send(
            (int)$this->context->language->id,
            'reservation_email_jefe', // Plantilla de correo
            'Nueva Reserva de Productos', // Asunto del correo
            $templateVars, // Variables para la plantilla
            FIXED_EMAIL_INFO, // Correo general
            null, // Nombre del destinatario (opcional)
            null, // Desde (opcional)
            null, // Desde nombre (opcional)
            null, // Adjuntos (opcional)
            null, // SMTP (opcional)
            _PS_MODULE_DIR_ . 'gestorproduccion/mails/', // Ruta de plantillas
            false, // No usar SMTP
            null // ID de la tienda (opcional)
         );

        // Verificar si los correos se enviaron correctamente
        if (!$mailSentToComercial || !$mailSentToGeneral ) {
            throw new Exception('Error al enviar el correo.');
        }

    } catch (Exception $e) {
        // Log de error
        PrestaShopLogger::addLog('Error en sendReservationEmail: ' . $e->getMessage(), 3);
    }
}
    
}