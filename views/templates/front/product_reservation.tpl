{extends file='customer/page.tpl'}

{block name="page_content"}

<link rel="stylesheet" href="{$urls.base_url}modules/gestorproduccion/views/css/choices.min.css" />

     <script src="{$urls.base_url}modules/gestorproduccion/views/js/choices.min.js"></script>
 
     <script type="module" src="{$urls.base_url}modules/gestorproduccion/views/js/frontProductReservation.js"></script>

     
    <h2 style="width: 100%; align-content: center;">Formulario de reserva</h2>

    {if isset($available_products) && $available_products|@count > 0}
        <form id="reservation_form" class="container mt-4 product-reservation-widget" action="{$url_for_submission}" method="POST">
            <!-- Token CSRF de Prestashop -->
            <input type="hidden" name="token" value="{$token}">

            <div id="product_reservation_container">
                <div class="reservation_item mb-3" data-index="0" style="display: flex; flex-direction: column; align-items: flex-start; justify-content: center;">
                    <div class="mb-3" style="width: 100%;" role="dialog">
                        <label for="id_customer" class="form-label">Cliente:</label>
                        <select name="id_customer[0]" id="id_customer" class="form-select" required style="width: 100%;">
                            <option value="" disabled selected>Buscar cliente...</option>
                            {foreach from=$customers item=customer}
                                <option value="{$customer.id_customer}">{$customer.firstname} {$customer.lastname}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="mb-3" style="width: 100%;">
                        <label for="product_id" class="form-label">Producto:</label>
                        <select name="product_id[0]" id="product_id_0" class="form-select" required style="width: 100%;">
                            <option value="" disabled selected>Buscar producto...</option>
                            {foreach from=$available_products item=product}
                                <option value="{$product.id_product}" data-reference="{$product.reference}" data-attribute="{$product.id_product_attribute}">
                                    {$product.reference} - {$product.name}
                                </option>
                            {/foreach}
                        </select>
                        <button type="button" class="remove_reservation btn btn-danger btn-sm" style="display:none;">&times;</button>
                    </div>
                    
                    <div class="mb-3" style="width: 100%;">
                        <label for="quantity" class="form-label">Cantidad:</label>
                        <input type="number" id="quantity" name="quantity[0]" class="form-control" min="1" value="1" required style="width: 100%;">
                    </div>
                    <div class="mb-3">
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

     
{/block}