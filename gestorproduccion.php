<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class GestorProduccion extends Module
{
    public function __construct()
    {
        $this->name = 'gestorproduccion';
        $this->tab = 'administration';
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
        if (!parent::install() || !$this->installDB()) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall() || !$this->uninstallDB()) {
            return false;
        }
        return true;
    }

    private function installDB()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_reservations` (
                `id_product` INT(10) UNSIGNED NOT NULL,
                `id_product_attribute` INT(10) UNSIGNED DEFAULT NULL,
                `status` VARCHAR(255) NOT NULL,
                `reservation_expiry` DATETIME DEFAULT NULL,
                `reserved_stock` INT(10) UNSIGNED DEFAULT 0,
                `customer_id` INT(10) UNSIGNED DEFAULT NULL,
                `date_added` DATETIME NOT NULL,
                PRIMARY KEY (`id_product`, `id_product_attribute`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }
    

    private function uninstallDB()
    {
        $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'product_reservations`';
        return Db::getInstance()->execute($sql);
    }
}
?>
