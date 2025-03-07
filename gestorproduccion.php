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
                id_reservation INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_product INT(10) UNSIGNED NOT NULL,
                id_product_attribute INT(10) UNSIGNED DEFAULT NULL,
                reference VARCHAR(255) NOT NULL,
                status ENUM("pendiente", "confirmada", "cancelada") NOT NULL DEFAULT "pendiente",
                reservation_expiry DATETIME DEFAULT NULL,
                reserved_stock INT(10) UNSIGNED DEFAULT 0,               
                id_comercial INT(10) UNSIGNED NOT NULL,
                id_customer INT(10) UNSIGNED NOT NULL,
                date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_product (id_product),
                INDEX idx_customer (id_customer),
                INDEX idx_comercial (id_comercial)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';
    
        return Db::getInstance()->execute($sql);
    }

    private function installReservationEnabledDB()
{
    $sql = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'product_reservation_enabled (
            id_product INT(10) UNSIGNED NOT NULL,
            id_product_attribute INT(10) UNSIGNED NOT NULL DEFAULT 0,  
            reference VARCHAR(64) DEFAULT NULL, 
            is_enabled TINYINT(1) NOT NULL DEFAULT 0, 
            date_enabled DATETIME NOT NULL,     
            PRIMARY KEY (id_product, id_product_attribute, reference) 
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
        // Obtener el id del producto, la referencia y la combinación
        $id_product = $params['product']['id_product'];
        $reference = $params['product']['reference'];
        $id_product_attribute = (int)Tools::getValue('id_product_attribute', $params['product']['id_product_attribute']); // Obtener la combinación

        // Verificar si el producto o la combinación están habilitados para reservas
        if ($this->isProductInReservationTable($id_product, $reference, $id_product_attribute)) {
            // Obtener los clientes asignados al comercial
            $customers = $this->getCustomersByComercial($customer->id);

            // Asignar variables a la plantilla
            $this->context->smarty->assign([
                'product_id' => $id_product,
                'reference' => $reference,
                'id_product_attribute' => $id_product_attribute,
                'customers' => $customers,
                'module_url' => $this->context->link->getModuleLink('gestorproduccion', 'ProductReservation'),
            ]);

            // Renderizar la plantilla
            return $this->fetch('module:gestorproduccion/views/templates/front/product_reservation.tpl');
        }
    }

    return ''; // Si no es comercial o el producto no está habilitado, no muestra nada
}


public function getCustomersByComercial($id_comercial)
{
    $sql = 'SELECT c.id_customer, c.firstname, c.lastname
            FROM '._DB_PREFIX_.'customer c
            WHERE c.id_comercial = '.(int)$id_comercial.'
            AND c.deleted = 0'; // Excluir clientes eliminados (si aplica)
    return Db::getInstance()->executeS($sql);
}


private function isProductInReservationTable($id_product, $reference, $id_product_attribute = 0)
{
    // Si no se ha proporcionado una combinación (id_product_attribute = 0), verificar solo por id_product
    if ($id_product_attribute == 0) {
        // Verificar si el producto base está habilitado (sin necesidad de referencia)
        $sql_base = 'SELECT COUNT(*) 
                     FROM '._DB_PREFIX_.'product_reservation_enabled 
                     WHERE id_product = '.(int)$id_product;
        
        $result_base = (bool)Db::getInstance()->getValue($sql_base);
    } else {
        // Si hay una combinación, buscar por id_product_attribute
        $sql_combination = 'SELECT COUNT(*) 
                            FROM '._DB_PREFIX_.'product_reservation_enabled 
                            WHERE id_product = '.(int)$id_product.' 
                            AND id_product_attribute = '.(int)$id_product_attribute;
        
        $result_combination = (bool)Db::getInstance()->getValue($sql_combination);
        $result_base = false; // No hace falta verificar el producto base cuando tenemos una combinación
    }

    // Si no se encuentra ni el producto base ni la combinación, loguear más información
    if (!$result_base && !$result_combination) {
        PrestaShopLogger::addLog('Producto ID ' . $id_product . ' con combinación ' . $id_product_attribute . ' NO encontrado en la tabla product_reservation_enabled. SQL Base: '.$sql_base.' SQL Combinación: '.$sql_combination, 3);
    }

    // El producto está habilitado si el producto base o la combinación están habilitados
    $result = $result_base || $result_combination;

    if ($result) {
        PrestaShopLogger::addLog('Producto ID ' . $id_product . ' con combinación ' . $id_product_attribute . ' encontrado en la tabla product_reservation_enabled', 1);
    } else {
        PrestaShopLogger::addLog('Producto ID ' . $id_product . ' con combinación ' . $id_product_attribute . ' no encontrado en la tabla product_reservation_enabled', 2);
    }

    return $result;
}




}

