<?php 

class InstallHelper
{
    public static function installDB()
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

    public static function uninstallDB()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'product_reservations');
    }

    public static function installReservationEnabledDB()
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

    public static function uninstallReservationEnabledDB()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'product_reservation_enabled');
    }

    public static function installDBForNotesReservation(){
        $sql = "CREATE TABLE IF NOT EXISTS `notas_reservas` (
            `id_nota` INT(11) NOT NULL AUTO_INCREMENT,
            `id_user` INT(11) NOT NULL,
            `nota` VARCHAR(2000) NOT NULL,
            PRIMARY KEY (`id_nota`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        return Db::getInstance()->execute($sql);
    }

    public static function uninstallDBForNotesReservation()
    {
        $sql = "DROP TABLE IF EXISTS `notas_reservas`;";
        return Db::getInstance()->execute($sql);
    }
}
