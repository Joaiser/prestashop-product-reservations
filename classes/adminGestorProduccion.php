<?php

class AdminGestorProduccion
{
    protected $context;
    
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getProductosSinStockYFecha()
    {
        $sql = 'SELECT p.id_product, pa.id_product_attribute, pl.name, 
                       IFNULL(pa.reference, p.reference) AS reference
                FROM '._DB_PREFIX_.'product p
                INNER JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
                LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
                LEFT JOIN '._DB_PREFIX_.'stock_available sa 
                    ON (p.id_product = sa.id_product AND (pa.id_product_attribute = sa.id_product_attribute OR pa.id_product_attribute IS NULL))
                WHERE pl.id_lang = '.(int)$this->context->language->id.'
                AND (sa.quantity <= 0 OR sa.quantity IS NULL)
                AND (p.available_date IS NULL OR p.available_date = "0000-00-00")';

        return Db::getInstance()->executeS($sql);
    }

    public function getProductosConFecha()
    {
        $sql = 'SELECT p.id_product, pa.id_product_attribute, pl.name, 
                       IFNULL(pa.reference, p.reference) AS reference,
                       p.available_date
                FROM '._DB_PREFIX_.'product p
                INNER JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
                LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
                LEFT JOIN '._DB_PREFIX_.'stock_available sa 
                    ON (p.id_product = sa.id_product AND (pa.id_product_attribute = sa.id_product_attribute OR pa.id_product_attribute IS NULL))
                WHERE pl.id_lang = '.(int)$this->context->language->id.'
                AND (sa.quantity <= 0 OR sa.quantity IS NULL)
                AND (p.available_date IS NOT NULL AND p.available_date != "0000-00-00")';

        return Db::getInstance()->executeS($sql);
    }

    public function getReservasPendientes()
    {
        $sql = 'SELECT pr.*, 
                       pl.name AS product_name, 
                       c.firstname AS customer_firstname, 
                       c.lastname AS customer_lastname,
                       com.firstname AS comercial_firstname, 
                       com.lastname AS comercial_lastname
                FROM '._DB_PREFIX_.'product_reservations pr
                LEFT JOIN '._DB_PREFIX_.'product p ON pr.id_product = p.id_product
                LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
                LEFT JOIN '._DB_PREFIX_.'customer c ON pr.id_customer = c.id_customer
                LEFT JOIN '._DB_PREFIX_.'customer com ON c.id_comercial = com.id_customer
                WHERE pr.status = "pendiente" 
                AND pl.id_lang = '.(int)$this->context->language->id;

        return Db::getInstance()->executeS($sql);
    }

    public function getProductosHabilitados()
    {
        $sql = 'SELECT p.id_product, pa.id_product_attribute, pl.name AS product_name, 
                       IFNULL(pa.reference, p.reference) AS reference
                FROM '._DB_PREFIX_.'product_reservation_enabled pre
                LEFT JOIN '._DB_PREFIX_.'product p ON pre.id_product = p.id_product
                LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
                LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
                WHERE pl.id_lang = '.(int)$this->context->language->id.' 
                AND pre.is_enabled = 1';

        return Db::getInstance()->executeS($sql);
    }

    public function habilitarReservas($product_id, $id_product_attribute, $reference)
    {
        $pdo = Db::getInstance()->getLink();

        try {
            $pdo->beginTransaction();

            $sqlReservationEnabled = 'INSERT INTO '._DB_PREFIX_.'product_reservation_enabled 
                                      (id_product, id_product_attribute, reference, is_enabled, date_enabled) 
                                      VALUES (
                                          '.(int)$product_id.', 
                                          '.(int)$id_product_attribute.', 
                                          "'.pSQL($reference).'", 
                                          1, 
                                          NOW()
                                      ) 
                                      ON DUPLICATE KEY UPDATE 
                                      is_enabled = 1, date_enabled = NOW()';
            Db::getInstance()->execute($sqlReservationEnabled);

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Error al habilitar las reservas: " . $e->getMessage());
        }
    }

    public function borrarReserva($id_reservation)
    {
        $pdo = Db::getInstance()->getLink();

        try {
            $pdo->beginTransaction();

            $sql = 'DELETE FROM '._DB_PREFIX_.'product_reservations
                    WHERE id_reservation = '.(int)pSQL($id_reservation);

            if (!Db::getInstance()->execute($sql)) {
                throw new Exception("No se pudo eliminar la reserva.");
            }

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Error al borrar la reserva: " . $e->getMessage());
        }
    }

    public function tieneReservasActivas($product_id)
    {
        $sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'product_reservations 
                WHERE id_product = '.(int)$product_id;
        return (bool)Db::getInstance()->getValue($sql);
    }

    public function deshabilitarProducto($product_id)
    {
        $pdo = Db::getInstance()->getLink();

        try {
            $pdo->beginTransaction();

            $sql = 'DELETE FROM '._DB_PREFIX_.'product_reservation_enabled 
                    WHERE id_product = '.(int)$product_id;

            if (!Db::getInstance()->execute($sql)) {
                throw new Exception("No se pudo eliminar el producto.");
            }

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Error al deshabilitar el producto: " . $e->getMessage());
        }
    }
	
	   public function insertarNota($customer_id,$notas_reservas)
    {
        $pdo = Db::getInstance()->getLink();

        try {
            $pdo->beginTransaction();

            $sqlReservationEnabled = 'INSERT INTO notas_reservas 
                          (id_user, nota) 
                          VALUES (' . (int)$customer_id . ', "' . pSQL($notas_reservas) . '")';

            Db::getInstance()->execute($sqlReservationEnabled);


            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Error al habilitar las reservas: " . $e->getMessage());
        }
    }
	
	public function getCustomersQueHanReservado()
    {
		$sql = 'SELECT DISTINCT c.id_customer, CONCAT(c.firstname, " ", c.lastname) AS final_name 
        FROM ' . _DB_PREFIX_ . 'product_reservations pr
        INNER JOIN ' . _DB_PREFIX_ . 'customer c ON pr.id_customer = c.id_customer';
		
        return Db::getInstance()->executeS($sql);
    }
	

}