 <!-- Contenedor del formulario, oculto por defecto -->
 <div class="reservation-form-container mt-3" style="display: none;">
    <form class="reservation-form-content" action="{$module_url}" method="post">
        <input type="hidden" name="product_id" value="{$product_id}">
        <input type="hidden" name="reference" value="{$reference}">
        <input type="hidden" name="id_product_attribute" value="{$id_product_attribute}"> <!-- Campo para la combinación -->
        <input type="hidden" name="token" value="{$token}">
    
        <!-- Campo para seleccionar cliente -->
        <div class="form-group">
            <label for="customer-{$product_id}" class="form-label">Cliente:</label>
            <select name="id_customer" id="customer-{$product_id}" class="form-control" required>
                {foreach $customers as $customer}
                    <option value="{$customer.id_customer}">
                        {$customer.firstname} {$customer.lastname}
                    </option>
                {/foreach}
            </select>
        </div>
    
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

<!-- Nueva sección: Productos reservados -->
<h2 class="mt-5">Productos reservados</h2>
<div class="reserved-products-list mt-3">
{foreach $reserved_products as $reserved_product}
    <div class="reserved-product-item">
        <p><strong>Producto:</strong> {$reserved_product.name}</p>
        <p><strong>Cantidad:</strong> {$reserved_product.quantity}</p>
        <p><strong>Cliente:</strong> {$reserved_product.customer_name}</p>
        <p><strong>Fecha de reserva:</strong> {$reserved_product.reservation_date}</p>
    </div>
{/foreach}
</div>

<script src="{$urls.base_url}modules/gestorproduccion/views/js/frontProductReservation.js"></script>