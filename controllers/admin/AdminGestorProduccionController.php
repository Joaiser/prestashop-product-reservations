<?php

require_once __DIR__ . '/../../classes/adminGestorProduccion.php';

class AdminGestorProduccionController extends ModuleAdminController
{
    protected $gestorProduccion;

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->meta_title = $this->l('Gestor de Producción');
        $this->gestorProduccion = new AdminGestorProduccion($this->context);
    }

    public function initContent()
{
    parent::initContent();
    
    $id_categoria = (int)Tools::getValue('id_categoria', 0);
    
    // Obtener productos iniciales (todas las categorías por defecto)
    $productos_iniciales = $this->gestorProduccion->getProductosPorCategoria(0);

    $notas_con_reservas = $this->gestorProduccion->getNotasConReservas();

    $notas = $this->eliminarDuplicadosNotas($notas_con_reservas);

    // Obtener mensajes desde Smarty para las notas
    $success_message = $this->context->smarty->getTemplateVars('success_message') ?? null;
    $error_message = $this->context->smarty->getTemplateVars('error_message') ?? null;

    
    // Asignamos las variables necesarias a Smarty
    $this->context->smarty->assign([
        'productos_sin_stock_y_fecha' => $this->gestorProduccion->getProductosSinStockYFecha(),
        'productos_con_fecha' => $this->gestorProduccion->getProductosConFecha(),
        'reservas_pendientes' => $this->gestorProduccion->getReservasPendientes(),
        'productos_habilitados' => $this->gestorProduccion->getProductosHabilitados(),
        'CustomersQueHanReservado' => $this->gestorProduccion->getCustomersQueHanReservado(),
        'categorias' => $this->gestorProduccion->getCategorias(),
        'notas' => $notas,
        'id_categoria_seleccionada' => $id_categoria,
        'productos' => $productos_iniciales, 
        'success_message' => $success_message,
        'error_message' => $error_message
    ]);

    // Establecemos la plantilla para mostrar
    $this->setTemplate('gestorproduccion.tpl');
}

//Funcion para manejar las duplicaciones delas notas
public function eliminarDuplicadosNotas($notas)
{
    $notas_agrupadas = [];
    
    foreach ($notas as $nota) {
        $cliente_id = $nota['id_user'];  // Aquí está el problema, necesitamos agregar esta clave en el array agrupado

        // Aseguramos que solo se agrega un cliente una vez
        if (!isset($notas_agrupadas[$cliente_id])) {
            $notas_agrupadas[$cliente_id] = [
                'id_user' => $nota['id_user'],  // Añadimos id_user para que esté disponible
                'cliente_nombre' => $nota['cliente_nombre'],
                'cliente_apellido' => $nota['cliente_apellido'],
                'comercial_nombre' => $nota['comercial_nombre'],
                'comercial_apellido' => $nota['comercial_apellido'],
                'notas' => $nota['notas']  // Las notas ya están agrupadas
            ];
        }
    }
    
    return $notas_agrupadas;
}



    public function postProcess()
    {
        

        if (Tools::isSubmit('ajax') && Tools::getValue('action') == 'filterProducts') {
            $id_categoria = (int)Tools::getValue('id_categoria', 0);
            $productos = $this->gestorProduccion->getProductosPorCategoria($id_categoria);
            
            $this->context->smarty->assign([
                'productos' => $productos
            ]);
            
            $html = $this->context->smarty->fetch(
                'module:gestorproduccion/views/templates/admin/_partials/productos.tpl'
            );
            
            die(json_encode([
                'success' => true,
                'html' => $html
            ]));
        }

        // Manejo de eliminación de reservas
        $id_reservation = (int) Tools::getValue('delete_reservation');
        if ($id_reservation) {
            try {
                $this->gestorProduccion->borrarReserva($id_reservation);
                exit(json_encode(['success' => true, 'message' => 'Reserva eliminada con éxito.']));
            } catch (Exception $e) {
                exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
            }
        }

        // Manejo de habilitación de productos para reserva
        if (Tools::isSubmit('submit')) {
            try {
                $json_data = Tools::getValue('products');
                if (!$json_data) {
                    throw new Exception("No se recibieron datos de productos.");
                }

                $products = json_decode($json_data, true);
                if (!is_array($products)) {
                    throw new Exception("Los datos del producto no son válidos.");
                }

                foreach ($products as $product) {
                    $this->gestorProduccion->habilitarReservas(
                        (int) $product['id_product'],
                        (int) $product['id_product_attribute'],
                        pSQL($product['reference'])
                    );
                }

                exit(json_encode(['success' => true, 'message' => 'Productos habilitados para reserva.']));
            } catch (Exception $e) {
                exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
            }
        }

        // Manejo de deshabilitación de productos
        $action = Tools::getValue('deshabilitarProducto');
        if ($action) {
            try {
                $product_id = (int) $action;

                if ($this->gestorProduccion->tieneReservasActivas($product_id)) {
                    throw new Exception("No se puede deshabilitar el producto $product_id porque tiene reservas activas.");
                }

                $this->gestorProduccion->deshabilitarProducto($product_id);
                exit(json_encode(['success' => true, 'message' => 'Producto deshabilitado con éxito.']));
            } catch (Exception $e) {
                exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
            }
        }

           // Manejo de inserción de notas
           if (Tools::isSubmit('cliente_nota') && Tools::isSubmit('comentario')) {
            $clienteNota = (int) Tools::getValue('cliente_nota');
            $comentario = trim(Tools::getValue('comentario'));
        
            // Validaciones
            if (empty($clienteNota) || $clienteNota <= 0) {
                $this->context->smarty->assign('error_message', 'Selecciona un cliente válido.');
            } elseif (empty($comentario)) {
                $this->context->smarty->assign('error_message', 'El comentario no puede estar vacío.');
            } elseif (strlen($comentario) > 500) {
                $this->context->smarty->assign('error_message', 'El comentario es demasiado largo (máximo 500 caracteres).');
            } else {
                try {
                    $comentario = strip_tags($comentario); // Evitar etiquetas HTML
        
                    // Verificar si la nota existe
                    if ($this->gestorProduccion->existeNota($clienteNota)) {
                        // Si existe, actualizar la nota
                        $this->gestorProduccion->actualizarNota($clienteNota, $comentario);
                        $this->context->smarty->assign('success_message', 'Nota actualizada correctamente.');
                    } else {
                        // Si no existe, insertar la nueva nota
                        $this->gestorProduccion->insertarNota($clienteNota, $comentario);
                        $this->context->smarty->assign('success_message', 'Nota añadida correctamente.');
                    }
                } catch (Exception $e) {
                    $this->context->smarty->assign('error_message', 'Error al añadir/actualizar la nota: ' . $e->getMessage());
                }
            }
        }
        
}
}