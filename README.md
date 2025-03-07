Módulo de Reservas de Productos - PrestaShop
Descripción
Este módulo permite gestionar productos en producción, con fechas de llegada y reservas de stock. A partir de la asignación de una fecha de llegada, el módulo delega el envío de notificaciones a ps_emailalerts, y se enfoca en gestionar las reservas de stock de los productos.

El módulo también maneja la adición de productos al carrito de los clientes cuando el producto llega, y proporciona una interfaz de modal para notificar a los clientes sobre la disponibilidad del producto.

Funcionalidades
Crear la tabla personalizada product_reservations:

Almacena las reservas de productos hechas por los clientes.
Incluye: ID del producto, ID de la combinación, estado, fecha de expiración, cantidad reservada, ID del cliente y fecha de reserva.
Panel de Administración:

Los administradores pueden ver y gestionar los productos en producción.
Una vez se asigne una fecha de llegada a un producto, el producto deja de mostrarse como "En Producción" en el frontend y el manejo de las notificaciones pasa a ser gestionado por ps_emailalerts.
Los administradores podrán gestionar las reservas de stock para productos en producción o con fecha de llegada.
Integración con ps_emailalerts:

El módulo no enviará correos electrónicos. Toda la gestión de notificaciones (como la disponibilidad de productos) será manejada por el módulo ps_emailalerts.
Cuando un producto tiene fecha de llegada, el módulo ps_emailalerts se encarga de enviar correos a los clientes que tienen ese producto en reserva.
Gestión de Productos:

Los productos pueden tener tres estados: "En Producción", "Con Fecha de Llegada" y "Disponible".
En Producción: El producto está en producción y se puede reservar.
Con Fecha de Llegada: El producto tiene una fecha de llegada definida, y el módulo se asegura de actualizar la fecha sin mostrar la notificación del estado "En Producción".
Disponible: El producto ya ha llegado y está disponible para el cliente.
Gestión de Reservas:

Los clientes pueden reservar productos mientras estén en producción o con fecha de llegada.
Cuando el producto llega: El stock reservado será automáticamente añadido al carrito del cliente. Además, se mostrará un modal notificando al cliente que su producto está disponible, y el registro de la reserva se eliminará de la base de datos una vez el cliente cierre el modal o añada el producto al carrito.
Pasos para empezar:
Instalar el Módulo:

Descarga el módulo.
Instálalo desde el panel de administración de PrestaShop.
Configurar el Módulo:

Accede a la configuración del módulo desde el panel de administración.
Configura las opciones relacionadas con las reservas de productos y su disponibilidad.
Gestionar Productos:

Ve a la lista de productos en el panel de administración.
Para cada producto, podrás marcarlo como "En Producción", asignarle una fecha de llegada o dejarlo como "Disponible".
Gestionar Reservas de Clientes:

Los clientes podrán reservar productos cuando estén en producción o con fecha de llegada.
Al llegar el producto, el stock reservado será añadido al carrito del cliente, y un modal notificará al cliente de que su producto está disponible



CAMBIOS, LOS CLIENTES SOLO PODRÁN RESERVAR CUANDO HAYA UNA FECHA FIJA

AÑADIDO CAMPOS DE MÁXIMO Y MÍNIMOS PARA RESERVAR PRODUCTOS, SE VA A GESTIONAR DESDE EL ADMIN ESTO