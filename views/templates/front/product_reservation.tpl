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
                            <option value=""></option>
                            {foreach from=$available_products item=product}
                                <option value="{$product.id_product}" data-reference="{$product.reference}" data-attribute="{$product.id_product_attribute}">
                                    {$product.name} - {$product.reference} {$product.attribute_reference}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity_0" class="form-label">Cantidad:</label>
                        <input type="number" id="quantity_0" name="quantity[0]" class="form-control" min="1" value="1" required style="width: 480px;">
                    </div>
                    <div class="mb-3">
                        <label for="id_customer_0" class="form-label">Cliente:</label>
                        <select name="id_customer[0]" id="id_customer_0" class="form-select" required>
                            <option value=""></option>
                            {foreach from=$customers item=customer}
                                <option value="{$customer.id_customer}">{$customer.firstname} {$customer.lastname}</option>
                            {/foreach}
                        </select>
                        <button type="button" class="remove_reservation btn btn-danger btn-sm" style="display:none;">&times;</button>
                    </div>
                    <div class="mb-3">
                        <!-- Campos ocultos para referencia y atributo -->
                        <input type="hidden" name="reference[0]" id="reference_0" value="">
                        <input type="hidden" name="id_product_attribute[0]" id="id_product_attribute_0" value="">
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

    {if isset($reservas_por_cliente) && is_array($reservas_por_cliente) && count($reservas_por_cliente) > 0}
    <div class="reservas" style="display: grid; gap: 40px; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); padding: 50px 0 50px 0;">
        {foreach from=$reservas_por_cliente item=reservas key=id_customer}

        <div class="cliente" style="width: 400px; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; background-color: #f9f9f9; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            <p style="font-size: 18px; font-weight: bold; margin-bottom: 15px;">Cliente: {$reservas.nombre_cliente}</p>
            <ul class="reservas_cliente" style="list-style: none; padding-left: 0;">
                {foreach from=$reservas.reservas item=reserva}
                    <li style="margin-bottom: 12px; font-size: 14px;">
                        <strong style="font-weight: bold;">Producto:</strong> {$reserva.reference} <br>
                        <strong style="font-weight: bold;">Stock reservado:</strong> {$reserva.reserved_stock} <br>
                        <strong style="font-weight: bold;">Fecha de reserva:</strong> {$reserva.date_added|date_format:"%d-%m-%Y"} <br>
                    </li>
                {/foreach}
            </ul>
        </div>
        
        {/foreach}
    </div>
{/if}

    <!-- Cargar el script de reserva -->
    <script src="{$urls.base_url}modules/gestorproduccion/views/js/frontProductReservation.js"></script>

{/block}