<?php

class GestorProduccionQueries
{
  protected $context;

  public function __construct(Context $context)
  {
    $this->context = $context;
  }

  public function getClientesPorComercial($id_comercial)
  {
    return Db::getInstance()->executeS(
      '
            SELECT id_customer, firstname, lastname 
            FROM ' . _DB_PREFIX_ . 'customer 
            WHERE id_comercial = ' . (int)$id_comercial
    );
  }

  public function getProductosHabilitadosParaReserva()
  {
    return Db::getInstance()->executeS('
            SELECT 
                p.id_product, 
                IFNULL(pa.reference, p.reference) AS reference,
                pl.name, 
                pre.id_product_attribute,
                IFNULL(pa.reference, "") AS attribute_reference 
            FROM ' . _DB_PREFIX_ . 'product p
            JOIN ' . _DB_PREFIX_ . 'product_reservation_enabled pre ON p.id_product = pre.id_product
            LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product 
                AND pl.id_lang = ' . (int)$this->context->language->id . '
            LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON pre.id_product_attribute = pa.id_product_attribute
            WHERE pre.is_enabled = 1
        ');
  }

  public function getReservasActivasPorComercial($id_comercial)
  {
    $reservas = Db::getInstance()->executeS('
            SELECT pr.id_reservation, pr.id_product, pr.id_product_attribute, pr.reference, 
                   pr.status, pr.reservation_expiry, pr.reserved_stock, 
                   pr.id_comercial, pr.id_customer, pr.date_added
            FROM ' . _DB_PREFIX_ . 'product_reservations pr
            WHERE pr.id_comercial = ' . (int)$id_comercial . ' 
            AND pr.status = "pendiente"
            ORDER BY pr.id_customer, pr.date_added ASC
        ');

    if (!$reservas) {
      return [];
    }

    $reservasAgrupadas = [];
    foreach ($reservas as $reserva) {
      $id_cliente = $reserva['id_customer'];

      if (!isset($reservasAgrupadas[$id_cliente])) {
        $nombreCliente = $this->getNombreCliente($id_cliente);
        $reservasAgrupadas[$id_cliente] = [
          'nombre_cliente' => $nombreCliente,
          'reservas' => []
        ];
      }

      $reservasAgrupadas[$id_cliente]['reservas'][] = $reserva;
    }

    return $reservasAgrupadas;
  }

  public function getNombreCliente($id_customer)
  {
    $result = Db::getInstance()->getRow(
      '
            SELECT firstname, lastname 
            FROM ' . _DB_PREFIX_ . 'customer 
            WHERE id_customer = ' . (int)$id_customer
    );

    return $result ? $result['firstname'] . ' ' . $result['lastname'] : 'Cliente no encontrado';
  }

  public function crearReserva($product_id, $id_product_attribute, $reference, $quantity, $id_comercial, $id_customer)
  {
    return Db::getInstance()->execute('
            INSERT INTO ' . _DB_PREFIX_ . 'product_reservations 
            (id_product, id_product_attribute, reference, reserved_stock, id_comercial, id_customer, date_added) 
            VALUES (
                ' . (int)$product_id . ', 
                ' . (int)$id_product_attribute . ', 
                "' . pSQL($reference) . '", 
                ' . (int)$quantity . ', 
                ' . (int)$id_comercial . ', 
                ' . (int)$id_customer . ', 
                NOW()
            )
        ');
  }

  public function getDatosClienteYComercial($id_customer, $id_comercial)
  {
    $cliente = Db::getInstance()->getRow(
      '
            SELECT CONCAT(firstname, " ", lastname) AS name 
            FROM ' . _DB_PREFIX_ . 'customer 
            WHERE id_customer = ' . (int)$id_customer
    );

    $comercial = Db::getInstance()->getRow(
      '
            SELECT CONCAT(firstname, " ", lastname) AS name, email 
            FROM ' . _DB_PREFIX_ . 'customer 
            WHERE id_customer = ' . (int)$id_comercial
    );

    return [
      'cliente' => $cliente ? $cliente['name'] : null,
      'comercial' => $comercial ? $comercial['name'] : null,
      'email_comercial' => $comercial ? $comercial['email'] : null
    ];
  }

  public function getNombreProducto($product_id)
  {
    return Db::getInstance()->getValue(
      '
            SELECT name 
            FROM ' . _DB_PREFIX_ . 'product_lang 
            WHERE id_product = ' . (int)$product_id . ' 
            AND id_lang = ' . (int)$this->context->language->id
    );
  }
}
