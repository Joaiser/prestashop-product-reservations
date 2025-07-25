<?php
require_once __DIR__ . '/../config/config.php';
require_once 'GestorProduccionQueries.php';

class ReservationEmailService
{
  protected $context;
  protected $queries;

  public function __construct(Context $context, GestorProduccionQueries $queries)
  {
    $this->context = $context;
    $this->queries = $queries;
  }

  public function sendReservationEmails(array $productosReservados, $id_customer, $id_comercial)
  {
    try {
      // Validación básica
      if (empty($productosReservados)) {
        throw new Exception('No hay productos reservados');
      }

      // Obtener datos del comercial
      // En el método sendReservationEmails, modifica la obtención de datos:
      $comercialData = $this->queries->getDatosComercial($id_comercial);
      if (!$comercialData || empty($comercialData['email'])) {
        throw new Exception('Datos del comercial incompletos o email no disponible');
      }

      $clienteData = $this->queries->getDatosCliente($id_customer);
      $nombreCliente = $clienteData ? $clienteData['firstname'] . ' ' . $clienteData['lastname'] : 'Cliente no registrado';

      // Asegúrate que las constantes están definidas
      if (!defined('FIXED_EMAIL') || !defined('FIXED_EMAIL_INFO')) {
        throw new Exception('Configuración de emails no definida');
      }

      // Construir contenido del email
      $productosTexto = $this->buildProductsText($productosReservados);
      $shopName = Configuration::get('PS_SHOP_NAME');

      $templateVars = [
        '{comercial_name}' => $comercialData['firstname'] . ' ' . $comercialData['lastname'],
        '{customer_name}' => $nombreCliente,
        '{products}' => nl2br($productosTexto),
        '{store_name}' => $shopName
      ];

      // Enviar emails con validación
      $this->sendValidatedEmail(
        $comercialData['email'],
        $comercialData['firstname'] . ' ' . $comercialData['lastname'],
        'reservation_email_template',
        'Nueva Reserva de Productos - ' . $shopName,
        $templateVars
      );

      $this->sendValidatedEmail(
        FIXED_EMAIL,
        null,
        'reservation_email_jefe',
        'Nueva Reserva de Productos - ' . $shopName,
        $templateVars
      );

      $this->sendValidatedEmail(
        FIXED_EMAIL_INFO,
        null,
        'reservation_email_jefe',
        'Nueva Reserva de Productos - ' . $shopName,
        $templateVars
      );
    } catch (Exception $e) {
      throw $e;
    }
  }

  protected function buildProductsText(array $productosReservados)
  {
    $text = "";
    foreach ($productosReservados as $producto) {
      $productName = $this->queries->getNombreProducto($producto['product_id']) ?: 'Producto desconocido';
      $reference = $producto['reference'] ?? 'Sin referencia';
      $text .= "• {$productName} (Ref: {$reference}), Cantidad: {$producto['quantity']}\n";
    }
    return $text;
  }

  protected function sendValidatedEmail($to, $toName, $template, $subject, $templateVars)
  {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
      throw new Exception("Email destinatario no válido: $to");
    }

    $templatePath = _PS_MODULE_DIR_ . 'gestorproduccion/mails/' . $template;
    if (!file_exists($templatePath)) {
      throw new Exception("Plantilla de email no encontrada: $template");
    }

    $result = Mail::Send(
      (int)$this->context->language->id,
      $template,
      $subject,
      $templateVars,
      $to,
      $toName,
      null,
      null,
      null,
      null,
      _PS_MODULE_DIR_ . 'gestorproduccion/mails/',
      false,
      null
    );

    if (!$result) {
      throw new Exception("Falló el envío a $to");
    }

    return $result;
  }
}
