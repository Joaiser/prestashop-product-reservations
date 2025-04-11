<?php

class AdminGestorProduccion
{
    protected $context;
    
    public function __construct(Context $context)
    {
        $this->context = $context;
    }
    public function getProductosSinStockYFecha($autoInsert = false )
{
    $this->limpiarReservasInvalidas();
    
    $sql = 'SELECT DISTINCT p.id_product, 
                   COALESCE(pa.id_product_attribute, 0) AS id_product_attribute,
                   IFNULL(pa.reference, p.reference) AS reference,
                   pl.name AS name,
                   pl.name AS product_name 
            FROM '._DB_PREFIX_.'product p
            INNER JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
            LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
            LEFT JOIN '._DB_PREFIX_.'stock_available sa ON (
                p.id_product = sa.id_product AND 
                (pa.id_product_attribute = sa.id_product_attribute OR 
                (pa.id_product_attribute IS NULL AND sa.id_product_attribute = 0))
            )
            WHERE pl.id_lang = '.(int)$this->context->language->id.'
            AND p.active = 1
            AND p.available_for_order = 1
            AND (sa.quantity <= 0 OR sa.quantity IS NULL)
            AND (p.available_date IS NULL OR p.available_date = "0000-00-00")';

    $productos = Db::getInstance()->executeS($sql);

    if ($autoInsert) {
        foreach ($productos as $producto) {
            Db::getInstance()->insert('product_reservation_enabled', [
                'id_product' => (int)$producto['id_product'],
                'id_product_attribute' => (int)$producto['id_product_attribute'],
                'reference' => pSQL($producto['reference']),
                'is_enabled' => 1,
                'date_enabled' => date('Y-m-d H:i:s')
            ], false, true, Db::REPLACE);
        }
    }
   

    return $productos;
}

public function getProductosConFecha( $autoInsert = false)
{
    $this->limpiarReservasInvalidas();
    
    $sql = 'SELECT DISTINCT p.id_product, 
                   COALESCE(pa.id_product_attribute, 0) AS id_product_attribute,
                   IFNULL(pa.reference, p.reference) AS reference,
                   p.available_date,
                   pl.name AS name,
                   pl.name AS product_name  
            FROM '._DB_PREFIX_.'product p
            INNER JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
            LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
            LEFT JOIN '._DB_PREFIX_.'stock_available sa ON (
                p.id_product = sa.id_product AND 
                (pa.id_product_attribute = sa.id_product_attribute OR 
                (pa.id_product_attribute IS NULL AND sa.id_product_attribute = 0))
            )
            WHERE pl.id_lang = '.(int)$this->context->language->id.'
            AND p.active = 1
            AND p.available_for_order = 1
            AND (sa.quantity <= 0 OR sa.quantity IS NULL)
            AND (p.available_date IS NOT NULL AND p.available_date != "0000-00-00")';

    $productos = Db::getInstance()->executeS($sql);

    if ($autoInsert) {
        foreach ($productos as $producto) {
            Db::getInstance()->insert('product_reservation_enabled', [
                'id_product' => (int)$producto['id_product'],
                'id_product_attribute' => (int)$producto['id_product_attribute'],
                'reference' => pSQL($producto['reference']),
                'is_enabled' => 1,
                'date_enabled' => date('Y-m-d H:i:s')
            ], false, true, Db::REPLACE);
    }
    
    }

    return $productos;
}

protected function limpiarReservasInvalidas()
{
    Db::getInstance()->execute('
        DELETE pre FROM `'._DB_PREFIX_.'product_reservation_enabled` pre
        LEFT JOIN `'._DB_PREFIX_.'product` p ON pre.id_product = p.id_product
        LEFT JOIN `'._DB_PREFIX_.'stock_available` sa ON (
            p.id_product = sa.id_product AND
            (pre.id_product_attribute = sa.id_product_attribute OR 
            (pre.id_product_attribute = 0 AND sa.id_product_attribute = 0))
        )
        WHERE p.id_product IS NULL 
        OR p.active = 0 
        OR p.available_for_order = 0
        OR (sa.quantity > 0 AND sa.quantity IS NOT NULL)
    ');
}

public function getReservasAgrupadas()
{
    $sql = 'SELECT pr.*, 
                   pl.name AS product_name, 
                   c.firstname AS customer_firstname, 
                   c.lastname AS customer_lastname,
                   c.id_customer,
                   com.firstname AS comercial_firstname, 
                   com.lastname AS comercial_lastname,
                   com.id_customer AS id_comercial
            FROM '._DB_PREFIX_.'product_reservations pr
            LEFT JOIN '._DB_PREFIX_.'product p ON pr.id_product = p.id_product
            LEFT JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
            LEFT JOIN '._DB_PREFIX_.'customer c ON pr.id_customer = c.id_customer
            LEFT JOIN '._DB_PREFIX_.'customer com ON c.id_comercial = com.id_customer
            WHERE pr.status = "pendiente" 
            AND pl.id_lang = '.(int)$this->context->language->id;

    $reservas = Db::getInstance()->executeS($sql);
    
    // Agrupamos por cliente
    $agrupadas = [];
    foreach ($reservas as $reserva) {
        $id_cliente = $reserva['id_customer'];
        if (!isset($agrupadas[$id_cliente])) {
            $agrupadas[$id_cliente] = [
                'cliente' => $reserva['customer_firstname'].' '.$reserva['customer_lastname'],
                'comercial' => $reserva['comercial_firstname'].' '.$reserva['comercial_lastname'],
                'id_comercial' => $reserva['id_comercial'],
                'productos' => []
            ];
        }
        $agrupadas[$id_cliente]['productos'][] = [
            'id_reservation' => $reserva['id_reservation'],
            'product_name' => $reserva['product_name'],
            'reference' => $reserva['reference'],
            'reserved_stock' => $reserva['reserved_stock'],
            'date_added' => $reserva['date_added'] 
        ];
    }
    
    return $agrupadas;
}

    public function editarCantidadReserva($idReservation, $nuevaCantidad)
    {
        // Validaciones básicas para asegurarse de que los datos son válidos
        if (!is_numeric($idReservation) || $idReservation <= 0) {
            return ['success' => false, 'error_message' => 'ID de reserva inválido'];
        }
    
        if (!is_numeric($nuevaCantidad) || $nuevaCantidad < 0) {
            return ['success' => false, 'error_message' => 'Cantidad inválida'];
        }
    
        try {
            $sql = 'UPDATE '._DB_PREFIX_.'product_reservations 
                    SET reserved_stock = '.(int)$nuevaCantidad.'
                    WHERE id_reservation = '.(int)$idReservation;
    
            $resultado = Db::getInstance()->execute($sql);
    
            return [
                'success' => (bool)$resultado,
                'error_message' => $resultado ? null : 'No se pudo actualizar la reserva'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error_message' => 'Error en la base de datos: '.$e->getMessage()
            ];
        }
    }


    public function getProductosHabilitados()
{
    $sql = 'SELECT 
                pre.id_product, 
                pre.id_product_attribute,
                pl.name AS product_name,
                pre.reference
            FROM '._DB_PREFIX_.'product_reservation_enabled pre
            LEFT JOIN '._DB_PREFIX_.'product_lang pl 
                ON pre.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.'
            WHERE pre.is_enabled = 1';

    return Db::getInstance()->executeS($sql);
}


    public function habilitarReservas($product_id, $id_product_attribute, $reference)
{
    $pdo = Db::getInstance()->getLink();

    try {
        $pdo->beginTransaction();

        // Si no hay combinación, usar 0
        $id_product_attribute = $id_product_attribute ?: 0;

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

    // public function tieneReservasActivas($product_id)
    // {
    //     $sql = 'SELECT COUNT(*) FROM '._DB_PREFIX_.'product_reservations 
    //             WHERE id_product = '.(int)$product_id;
    //     return (bool)Db::getInstance()->getValue($sql);
    // }

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
	
    // Función para insertar una nueva nota
public function insertarNota($customer_id, $notas_reservas)
{
    $pdo = Db::getInstance()->getLink();

    try {
        $pdo->beginTransaction();

        // Insertar la nueva nota
        $sqlInsert = 'INSERT INTO notas_reservas 
                      (id_user, nota) 
                      VALUES (' . (int)$customer_id . ', "' . pSQL($notas_reservas) . '")';
        Db::getInstance()->execute($sqlInsert);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception("Error al insertar la nota: " . $e->getMessage());
    }
}

// Función para actualizar una nota existente
public function actualizarNota($nota_id, $notas_reservas)
{
    $pdo = Db::getInstance()->getLink();

    try {
        $pdo->beginTransaction();

        $sqlUpdate = 'UPDATE notas_reservas 
                      SET nota = "' . pSQL($notas_reservas) . '" 
                      WHERE id_nota = ' . (int)$nota_id;
        
        Db::getInstance()->execute($sqlUpdate);
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception("Error al actualizar la nota: " . $e->getMessage());
    }
}


// Función para comprobar si ya existe una nota
public function existeNota($nota_id)
{
    $pdo = Db::getInstance()->getLink();

    // Consulta para verificar si la nota existe
    $sql = 'SELECT COUNT(*) 
            FROM notas_reservas 
            WHERE id_nota = ' . (int)$nota_id;

    // Usamos executeS para ejecutar la consulta
    $result = Db::getInstance()->getValue($sql); 

    // Si el resultado es mayor que 0, la nota existe
    return (int)$result > 0;
}

public function deleteNota($nota_id)
{
    $pdo = Db::getInstance()->getLink();

    try {
        $pdo->beginTransaction();

        $sqlDelete = 'DELETE FROM notas_reservas WHERE id_nota = ' . (int)$nota_id;
        $result = Db::getInstance()->execute($sqlDelete);

        if (!$result) {
            throw new Exception("No se pudo eliminar la nota.");
        }

        $pdo->commit();
        return true; 
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception("Error al eliminar la nota: " . $e->getMessage());
    }
}

    	
	public function getCustomersQueHanReservado()
    {
		$sql = 'SELECT DISTINCT c.id_customer, CONCAT(c.firstname, " ", c.lastname) AS final_name 
        FROM ' . _DB_PREFIX_ . 'product_reservations pr
        INNER JOIN ' . _DB_PREFIX_ . 'customer c ON pr.id_customer = c.id_customer';
		
        return Db::getInstance()->executeS($sql);
    }

    public function getTodosLosProductos()
    {
        $sql = 'SELECT p.id_product, pa.id_product_attribute, pl.name AS product_name, 
                       IFNULL(pa.reference, p.reference) AS reference
                FROM '._DB_PREFIX_.'product p
                INNER JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
                LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
                WHERE pl.id_lang = '.(int)$this->context->language->id;

        return Db::getInstance()->executeS($sql);
    }

    // Función para obtener productos filtrados por categoría, con atributos
    public function getProductosPorCategoria($id_categoria)
    {
        // Si se selecciona la categoría "Todas las categorías" (id_categoria == 0)
        if ($id_categoria == 0) {
            $sql = 'SELECT p.id_product, pa.id_product_attribute, pl.name AS product_name, 
                           IFNULL(pa.reference, p.reference) AS reference
                    FROM '._DB_PREFIX_.'product p
                    INNER JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
                    LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
                    WHERE pl.id_lang = '.(int)$this->context->language->id;
        } else {
            $sql = 'SELECT p.id_product, pa.id_product_attribute, pl.name AS product_name, 
                           IFNULL(pa.reference, p.reference) AS reference
                    FROM '._DB_PREFIX_.'product p
                    INNER JOIN '._DB_PREFIX_.'product_lang pl ON p.id_product = pl.id_product
                    LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON p.id_product = pa.id_product
                    INNER JOIN '._DB_PREFIX_.'category_product cp ON p.id_product = cp.id_product
                    WHERE pl.id_lang = '.(int)$this->context->language->id.'
                    AND cp.id_category = '.(int)$id_categoria;
        }
    
        return Db::getInstance()->executeS($sql);
    }    

    public function getCategorias()
{
    $sql = 'SELECT c.id_category, cl.name AS category_name
            FROM '._DB_PREFIX_.'category c
            INNER JOIN '._DB_PREFIX_.'category_lang cl ON c.id_category = cl.id_category
            WHERE cl.id_lang = '.(int)$this->context->language->id.'
            AND c.active = 1'; // Filtramos solo las categorías activas

    return Db::getInstance()->executeS($sql);
}

public static function getNotasConReservas()
{
    $sql = 'SELECT 
            nr.id_nota,  -- Añadir el id_nota para obtener el identificador de la nota
            nr.id_user,
            c.firstname AS cliente_nombre,
            c.lastname AS cliente_apellido,
            c_comercial.firstname AS comercial_nombre,
            c_comercial.lastname AS comercial_apellido,
            GROUP_CONCAT(DISTINCT nr.nota SEPARATOR "||") AS notas  
    FROM notas_reservas nr
    JOIN '._DB_PREFIX_.'product_reservations pr ON nr.id_user = pr.id_customer
    JOIN '._DB_PREFIX_.'customer c ON nr.id_user = c.id_customer
    JOIN '._DB_PREFIX_.'customer c_comercial ON pr.id_comercial = c_comercial.id_customer
    GROUP BY nr.id_user, pr.id_comercial, nr.id_nota  -- Agrupar también por id_nota
    ORDER BY nr.id_user, nr.id_nota DESC';

    return Db::getInstance()->executeS($sql);
}



}