{extends file='page.tpl'}

{block name="content"}
    <h2>Formulario de reserva</h2>

    {if isset($available_products) && $available_products|@count > 0}
        <form id="reservation_form" class="container mt-4 product-reservation-widget" action="{$url_for_submission}" method="POST">
            <!-- Token CSRF de Prestashop -->
            <input type="hidden" name="token" value="{$token}">

            <div id="product_reservation_container">
                <div class="reservation_item mb-3" data-index="0">
                    <div class="mb-3">
                        <label for="product_0" class="form-label">Producto:</label>
                        <select name="product_id[0]" id="product_0" class="form-select" required>
                            {foreach from=$available_products item=product}
                                <option value="{$product.id_product}">{$product.name} - {$product.reference}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity_0" class="form-label">Cantidad:</label>
                        <input type="number" id="quantity_0" name="quantity[0]" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="id_customer_0" class="form-label">Cliente:</label>
                        <select name="id_customer[0]" id="id_customer_0" class="form-select" required>
                            {foreach from=$customers item=customer}
                                <option value="{$customer.id_customer}">{$customer.firstname} {$customer.lastname}</option>
                            {/foreach}
                        </select>
                        <button type="button" class="remove_reservation btn btn-danger btn-sm" style="display:none;">&times;</button>
                    </div>
                    <div class="mb-3">
                        <!-- Asegúrate de pasar la referencia del producto aquí -->
                        <input type="hidden" name="reference[0]" value="{$product.reference}">
                        <input type="hidden" name="id_product_attribute[0]" value="{$id_product_attribute}">
                    </div>
                </div>
            </div>

            <button type="button" id="add_more_reservations" class="btn btn-secondary">+ Añadir más productos</button>

            <div class="mb-3 mt-3">
                <button type="submit" class="btn btn-primary">Realizar reserva</button>
            </div>

            <!-- Contenedor de mensajes de éxito o error -->
            <div class="mb-3">
                <div class="reservation-message" style="display:none;"></div>
            </div>
        </form>
    {else}
        <p>No hay productos habilitados para reserva.</p>
    {/if}

    <!-- Cargar el script de reserva -->
    <script src="{$urls.base_url}modules/gestorproduccion/views/js/frontProductReservation.js"></script>
{/block}
