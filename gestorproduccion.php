<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class GestorProduccion extends Module
{
    public function __construct()
    {
        $this->name = 'gestorproduccion';
        $this->tab = 'AdminCatalog';
        $this->version = '1.0.0';
        $this->author = 'Aitor';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Gestor de Producción y Reservas de Productos');
        $this->description = $this->l('Este módulo gestiona los estados de producción y las reservas de productos.');

        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar este módulo?');
    }

    public function install()
    {
        if (!parent::install() || !$this->installDB() || !$this->installTab() || !$this->registerHook('displayBackOfficeHeader') || !$this->installReservationEnabledDB() || !$this->registerHook('displayProductAdditionalInfo')) {
            return false;
        }
        return true;
    }
    
    public function uninstall()
    {
        if (!parent::uninstall() || !$this->uninstallDB() || !$this->uninstallTab() || !$this->uninstallReservationEnabledDB()) {
            return false;
        }
        return true;
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminGestorProduccion';
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;
        $tab->name = [];

        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Gestor de Producción';
        }

        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminGestorProduccion'); // Cambia 'GestorProduccion' a 'AdminGestorProduccion'
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    private function installDB()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'product_reservations (
                id_product INT(10) UNSIGNED NOT NULL,
                id_product_attribute INT(10) UNSIGNED DEFAULT NULL,
                status VARCHAR(255) NOT NULL,
                reservation_expiry DATETIME DEFAULT NULL,
                reserved_stock INT(10) UNSIGNED DEFAULT 0,
                customer_id INT(10) UNSIGNED DEFAULT NULL,
                date_added DATETIME NOT NULL,
                PRIMARY KEY (id_product, id_product_attribute)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    private function installReservationEnabledDB()
{
    $sql = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'product_reservation_enabled (
            id_product INT(10) UNSIGNED NOT NULL,
            reference VARCHAR(64) DEFAULT NULL,  -- Nueva columna para la referencia
            is_enabled TINYINT(1) NOT NULL DEFAULT 0, 
            date_enabled DATETIME NOT NULL,     
            PRIMARY KEY (id_product, reference)  -- Clave primaria compuesta por id_product y reference
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

    return Db::getInstance()->execute($sql);
}

    private function uninstallDB()
    {
        $sql = 'DROP TABLE IF EXISTS '._DB_PREFIX_.'product_reservations';
        return Db::getInstance()->execute($sql);
    }

    private function uninstallReservationEnabledDB()
    {
        $sql = 'DROP TABLE IF EXISTS '._DB_PREFIX_.'product_reservation_enabled';
        return Db::getInstance()->execute($sql);
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/gestorproduccionadmin.css');
        $this->context->controller->addJS($this->_path.'views/js/adminGestorProduccion.js');     
    }

    /*A partir de aquí, vamos a poner los hooks para la ui del user*/

    public function hookModuleRoutes()
{
    return [
        'module-gestorproduccion-productreservation' => [
            'controller' => 'ProductReservation',
            'rule' => 'gestorproduccion/productreservation',
            'keywords' => [],
            'params' => [
                'fc' => 'module',
                'module' => 'gestorproduccion',
            ],
        ],
    ];
}


public function hookDisplayProductAdditionalInfo($params)
{
    // Obtener el usuario actual
    $customer = $this->context->customer;

    // Verificar si el usuario es un comercial (id_default_group = 4)
    if ($customer->isLogged() && $customer->id_default_group == 4) {
        // Obtener el id del producto y la referencia
        $id_product = $params['product']['id_product'];
        $reference = $params['product']['reference'];

        // Verificar si el producto está en la tabla de reservas
        if ($this->isProductInReservationTable($id_product, $reference)) {
            // Asignar variables a la plantilla
            $this->context->smarty->assign([
                'product_id' => $id_product,
                'reference' => $reference,
                'module_url' => $this->context->link->getModuleLink('gestorproduccion', 'ProductReservation'),
            ]);
            
            PrestaShopLogger::addLog('Archivos JS del Front Office cargados para el producto ID ' . $id_product . ' y referencia ' . $reference, 1);


            
            // PrestaShopLogger::addLog('Intentando registrar frontProductReservation.js');
            // $this->context->controller->registerJavascript(
            //     'module-gestorproduccion-js',
            //     'modules/' . $this->name . '/views/js/frontProductReservation.js',
            //     ['position' => 'bottom', 'priority' => 150]
            // );


            $this->context->controller->registerStylesheet(
                'module-gestorproduccion-css',
                'modules/' . $this->name . '/views/css/frontProductReservation.css',
                ['media' => 'all', 'priority' => 150]
            );
            PrestaShopLogger::addLog('Archivos CSS');

            

            // Renderizar la plantilla
            return $this->fetch('module:gestorproduccion/views/templates/front/product_reservation.tpl');
        }
    }

    // Log detallado si el producto no está habilitado o el usuario no es comercial
    if (!$customer->isLogged()) {
        PrestaShopLogger::addLog('El usuario no está logueado. No se puede mostrar el formulario de reserva.', 2);
    } elseif ($customer->id_default_group != 4) {
        PrestaShopLogger::addLog('El usuario logueado no es un comercial (ID de grupo: ' . $customer->id_default_group . '). No se puede mostrar el formulario de reserva.', 2);
    } else {
        PrestaShopLogger::addLog('El producto ID ' . $id_product . ' con referencia ' . $reference . ' no está habilitado para reservas.', 2);
    }

    return ''; // Si no es comercial o el producto no está en la tabla, no muestra nada
}


private function isProductInReservationTable($id_product, $reference)
{
    $sql = 'SELECT COUNT(*) 
            FROM '._DB_PREFIX_.'product_reservation_enabled 
            WHERE id_product = '.(int)$id_product.' 
            AND reference = "'.pSQL($reference).'"';

    $result = (bool)Db::getInstance()->getValue($sql);

    if ($result) {
        PrestaShopLogger::addLog('Producto ID ' . $id_product . ' con referencia ' . $reference . ' encontrado en la tabla product_reservation_enabled', 1);
    } else {
        PrestaShopLogger::addLog('Producto ID ' . $id_product . ' con referencia ' . $reference . ' no encontrado en la tabla product_reservation_enabled', 2);
    }

    return $result;
}

}

