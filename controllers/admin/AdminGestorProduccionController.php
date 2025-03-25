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
        
        $this->context->smarty->assign([
            'productos_sin_stock_y_fecha' => $this->gestorProduccion->getProductosSinStockYFecha(),
            'productos_con_fecha' => $this->gestorProduccion->getProductosConFecha(),
            'reservas_pendientes' => $this->gestorProduccion->getReservasPendientes(),
            'productos_habilitados' => $this->gestorProduccion->getProductosHabilitados(),
        ]);
        
        $this->setTemplate('gestorproduccion.tpl');
    }

    public function postProcess()
    {
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
    }
}