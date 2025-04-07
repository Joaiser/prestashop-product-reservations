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
    
    // Verificar si se debe actualizar los productos sin stock
    if (Tools::getValue('update_products_without_stock')) {
        $this->processUpdateProductsWithoutStock();
    }

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
        $cliente_id = $nota['id_user'];  // Asegurarnos de que agrupamos por cliente

        if (!isset($notas_agrupadas[$cliente_id])) {
            $notas_agrupadas[$cliente_id] = [
                'id_user' => $nota['id_user'],
                'cliente_nombre' => $nota['cliente_nombre'],
                'cliente_apellido' => $nota['cliente_apellido'],
                'comercial_nombre' => $nota['comercial_nombre'],
                'comercial_apellido' => $nota['comercial_apellido'],
                'notas' => []  // Creamos un array vacío para las notas
            ];
        }

        // Añadimos la nota a la lista de notas para ese cliente
        $notas_agrupadas[$cliente_id]['notas'][] = [
            'id_nota' => $nota['id_nota'],
            'nota' => $nota['notas']
        ];
    }

    return $notas_agrupadas;
}




public function postProcess()
{
    if (Tools::isSubmit('ajax') && Tools::getValue('action') == 'filterProducts') {
        return $this->processFilterProducts();
    }

    if ((int)Tools::getValue('delete_reservation')) {
        return $this->processDeleteReservation();
    }

    if (Tools::isSubmit('submit')) {
        return $this->processEnableProducts();
    }

    if (Tools::getValue('deshabilitarProducto')) {
        return $this->processDisableProduct();
    }

    if (Tools::isSubmit('action')) {
        return $this->processNotaActions();
    }

    if (Tools::isSubmit('cliente_nota')) {
        return $this->processClienteNota();
    }
}

//nueva funcion que habilitara todos los productos sin stock
protected function processUpdateProductsWithoutStock()
{
    try {
        // Obtener los productos sin stock y con fecha de disponibilidad
        $productosSinStockYFecha = $this->gestorProduccion->getProductosSinStockYFecha();
        $productosConFecha = $this->gestorProduccion->getProductosConFecha();

        // Fusionar ambos conjuntos de productos
        $productos = array_merge($productosSinStockYFecha, $productosConFecha);

        // Si no hay productos, lanzar una excepción
        if (empty($productos)) {
            throw new Exception("No se encontraron productos sin stock ni fecha de disponibilidad.");
        }

        // Habilitar reservas para cada producto
        foreach ($productos as $producto) {
            $this->gestorProduccion->habilitarReservas(
                (int)$producto['id_product'],
                (int)$producto['id_product_attribute'],
                pSQL($producto['reference'])
            );
        }

        // Respuesta exitosa
        exit(json_encode([
            'success' => true,
            'message' => 'Productos sin stock habilitados para reserva.'
        ]));

    } catch (Exception $e) {
        // Respuesta en caso de error
        exit(json_encode([
            'success' => false,
            'error_message' => $e->getMessage()
        ]));
    }
}



protected function processFilterProducts()
{
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

protected function processDeleteReservation()
{
    $id_reservation = (int)Tools::getValue('delete_reservation');
    try {
        $this->gestorProduccion->borrarReserva($id_reservation);
        exit(json_encode(['success' => true, 'message' => 'Reserva eliminada con éxito.']));
    } catch (Exception $e) {
        exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
    }
}

protected function processEnableProducts()
{
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
                (int)$product['id_product'],
                (int)$product['id_product_attribute'],
                pSQL($product['reference'])
            );
        }

        exit(json_encode(['success' => true, 'message' => 'Productos habilitados para reserva.']));
    } catch (Exception $e) {
        exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
    }
}

protected function processDisableProduct()
{
    try {
        $product_id = (int)Tools::getValue('deshabilitarProducto');

        // if ($this->gestorProduccion->tieneReservasActivas($product_id)) {
        //     throw new Exception("No se puede deshabilitar el producto $product_id porque tiene reservas activas.");
        // }

        $this->gestorProduccion->deshabilitarProducto($product_id);
        exit(json_encode(['success' => true, 'message' => 'Producto deshabilitado con éxito.']));
    } catch (Exception $e) {
        exit(json_encode(['success' => false, 'error_message' => $e->getMessage()]));
    }
}

protected function processNotaActions()
{
    header('Content-Type: application/json');
    
    try {
        $action = Tools::getValue('action');
        
        switch ($action) {
            case 'update_nota_text':
                return $this->processUpdateNota();
            case 'delete_nota':
                return $this->processDeleteNota();
            default:
                throw new Exception('Acción no reconocida.');
        }
    } catch (Exception $e) {
        die(json_encode(['success' => false, 'message' => $e->getMessage()]));
    }
}

protected function processUpdateNota()
{
    $notaId = (int)Tools::getValue('nota_id');
    $comentario = trim(Tools::getValue('new_text'));
    $idUser = (int)Tools::getValue('id_user');
    
    // Validaciones
    if (empty($comentario)) {
        throw new Exception('El comentario no puede estar vacío.');
    } elseif (strlen($comentario) > 500) {
        throw new Exception('El comentario es demasiado largo (máximo 500 caracteres).');
    }
    
    $comentario = strip_tags($comentario);
    
    if ($notaId > 0 && $this->gestorProduccion->existeNota($notaId)) {
        $this->gestorProduccion->actualizarNota($notaId, $comentario);
        die(json_encode(['success' => true, 'message' => 'Nota actualizada correctamente.']));
    } else {
        if (empty($idUser)) {
            throw new Exception('Usuario no válido.');
        }
        $this->gestorProduccion->insertarNota($idUser, $comentario);
        die(json_encode(['success' => true, 'message' => 'Nota añadida correctamente.']));
    }
}

protected function processDeleteNota()
{
    $notaId = (int)Tools::getValue('nota_id');
    
    if ($notaId <= 0 || !$this->gestorProduccion->existeNota($notaId)) {
        throw new Exception('La nota no existe o el ID es inválido.');
    }
    
    $this->gestorProduccion->deleteNota($notaId);
    die(json_encode(['success' => true, 'message' => 'Nota eliminada correctamente.']));
}

protected function processClienteNota()
{
    $idUser = (int)Tools::getValue('cliente_nota');
    $comentario = trim(Tools::getValue('comentario'));
    
    // Validaciones
    if (empty($idUser)) {
        $this->context->smarty->assign('error_message', 'Selecciona un cliente válido.');
    } elseif (empty($comentario)) {
        $this->context->smarty->assign('error_message', 'El comentario no puede estar vacío.');
    } elseif (strlen($comentario) > 500) {
        $this->context->smarty->assign('error_message', 'El comentario es demasiado largo (máximo 500 caracteres).');
    } else {
        try {
            $comentario = strip_tags($comentario);
            $this->gestorProduccion->insertarNota($idUser, $comentario);
            $this->context->smarty->assign('success_message', 'Nota añadida correctamente.');
        } catch (Exception $e) {
            $this->context->smarty->assign('error_message', 'Error al añadir la nota: ' . $e->getMessage());
        }
    }
}
        

public function ajaxProcessLoadNotas()
{
    try {
        $notas_con_reservas = $this->gestorProduccion->getNotasConReservas();
        $notas = $this->eliminarDuplicadosNotas($notas_con_reservas);
        
        $this->context->smarty->assign('notas', $notas);
        
        $html = $this->context->smarty->fetch(
            _PS_MODULE_DIR_.'gestorproduccion/views/templates/admin/_partials/notas_list.tpl'
        );
        
        die(json_encode([
            'success' => true,
            'html' => $html
        ]));
    } catch (Exception $e) {
        die(json_encode([
            'success' => false,
            'message' => 'Error al cargar notas: ' . $e->getMessage()
        ]));
    }
}


}