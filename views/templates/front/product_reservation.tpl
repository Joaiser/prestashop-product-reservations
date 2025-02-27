<div class="product-reservation-widget mt-3">
    <!-- Botón para abrir/cerrar el formulario -->
    <button class="reservation-toggle btn btn-primary" data-product-id="{$product_id}">
        <i class="fas fa-calendar-alt"></i> Reservar producto
    </button>

    <!-- Contenedor del formulario, oculto por defecto -->
    <div class="reservation-form-container mt-3" style="display: none;">
        <form class="reservation-form-content" action="{$link->getModuleLink('gestorproduccion', 'productreservation')}" method="post">
            <input type="hidden" name="product_id" value="{$product_id}">
            <input type="hidden" name="reference" value="{$reference}">
            <input type="hidden" name="token" value="{$token}"> <!-- Añadir el token CSRF -->

            <!-- Campo para la cantidad -->
            <div class="form-group">
                <label for="quantity-{$product_id}" class="form-label">Cantidad:</label>
                <input type="number" name="quantity" id="quantity-{$product_id}" class="form-control" min="1" required>
            </div>

            <!-- Mensaje dinámico para respuestas AJAX -->
            <div class="reservation-message alert" style="display: none;"></div>

            <!-- Botón de envío -->
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check"></i> Confirmar reserva
            </button>
        </form>
    </div>
</div>

<script src="{$urls.base_url}modules/gestorproduccion/views/js/frontProductReservation.js"></script>