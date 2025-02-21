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
    if (!parent::install() || !$this->installDB() || !$this->installTab() || !$this->registerHook('displayBackOfficeHeader')) {
        return false;
    }
    return true;
}

    public function uninstall()
    {
        if (!parent::uninstall() || !$this->uninstallDB() || !$this->uninstallTab()) {
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

            foreach (Language::getlanguages() as $lang) {
                $tab->name[$lang['id_lang']] = 'Gestor de Producción';
            }

            return $tab->add();
        }
    private function uninstallTab()
    {
        $id_tab = (int) tab::getIdFromClassName('GestorProduccion');
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


    private function uninstallDB()
    {
        $sql = 'DROP TABLE IF EXISTS '._DB_PREFIX_.'product_reservations';
        return Db::getInstance()->execute($sql);
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/gestorproduccionadmin.css');
        $this->context->controller->addJS($this->_path.'views/js/adminGestorProduccion.js');     
    }
}
?>