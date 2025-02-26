<div class="product-reservation-widget">
    <button id="reservation-toggle" class="btn btn-primary">
        <i class="fas fa-calendar-alt"></i> Reservar producto
    </button>
    <div id="reservation-form" class="reservation-form-container mt-3" style="display: none;">
        <form id="reservation-form-content" class="reservation-form">
            <input type="hidden" name="product_id" value="{$product_id}">
            <input type="hidden" name="reference" value="{$reference}">
            <div class="form-group">
                <label for="quantity" class="form-label">Cantidad:</label>
                <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check"></i> Confirmar reserva
            </button>
        </form>
    </div>
</div>
