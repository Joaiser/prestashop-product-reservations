<?php

class ReservationModel
{
  public static function getCustomersByComercial($id_comercial)
  {
    $sql = 'SELECT c.id_customer, c.firstname, c.lastname
                FROM ' . _DB_PREFIX_ . 'customer c
                WHERE c.id_comercial = ' . (int)$id_comercial . '
                AND c.deleted = 0
                ORDER BY c.firstname ASC';

    return Db::getInstance()->executeS($sql);
  }
}
