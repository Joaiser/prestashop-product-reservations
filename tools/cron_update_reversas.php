<?php
require_once dirname(__FILE__) . '/../../../config/config.inc.php';



require_once dirname(__FILE__) . '/../classes/adminGestorProduccion.php';
echo dirname(__FILE__);


$modulo = new AdminGestorProduccion(Context::getContext());

if (!$modulo) {
  echo "[❌] No se pudo cargar el módulo gestorproduccion\n";
  exit(1);
}

try {
  $sinStock = $modulo->getProductosSinStockYFecha(true);
  $conFecha = $modulo->getProductosConFecha(true);

  echo "[✅] Reservas actualizadas correctamente\n";
  echo "[📦] Sin stock + sin fecha: " . count($sinStock) . "\n";
  echo "[🗓️] Con fecha futura: " . count($conFecha) . "\n";
} catch (Exception $e) {
  echo "[❌] Error: " . $e->getMessage() . "\n";
  exit(1);
}

exit;
