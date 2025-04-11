{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}

<script>
    window.gestorProduccionVars = {
        ajaxUrl: "{$link->getAdminLink('AdminGestorProduccion')|escape:'javascript':'UTF-8'}",
        csrfToken: "{$token|escape:'javascript':'UTF-8'}"
    };
</script>


<h2 style="margin-bottom: 32px;">🚀 {l s='Gestor de Producción' mod='gestorproduccion'}</h2>

<h3 style="margin: 0;">📝 {l s='Incluye la nota' mod='gestorproduccion'}</h3>

<!-- Nuevo formulario AJAX -->
<div class="ajax-form-container" style="padding-block: 2%;">
    <label for="ajax-opciones">Selecciona una opción:</label><br>
    <select id="ajax-opciones" class="ajax-cliente-nota">
        {foreach from=$CustomersQueHanReservado item=reservation}
            <option value="{$reservation.id_customer}">{$reservation.final_name}</option>
        {/foreach}
    </select>
    <label for="ajax-comentario">Comentario:</label><br>
    <textarea id="ajax-comentario" class="ajax-texto-nota" rows="4" cols="50" placeholder="Escribe tu comentario..."></textarea>
    <br>
    <button id="ajax-submit-btn" class="ajax-submit-nota">Enviar</button>
</div>

<!-- Contenedor para mensajes AJAX -->
<div id="ajax-messages" style="display: none;">
    <p id="ajax-success-message" style="color: green; font-weight: bold;"></p>
    <p id="ajax-error-message" style="color: red; font-weight: bold;"></p>
</div>

<!-- Listado de notas (se actualizará via AJAX) -->
<div class="reservas-container" id="notas-container">
    {include file='./_partials/notas_list.tpl'}
</div>

<form action="{$link->getAdminLink('AdminGestorProduccion')}" method="post" class="form-habilitar-sin-stock">
    <input type="hidden" name="update_products_without_stock" value="1">
    <button class="btn btn-primary" data-action="update_products_without_stock">Habilitar productos sin stock</button>
</form>




<!-- Sección de reservas pendientes -->
<h4 style="font-weight:bold;">🛒 {l s='Reservas Pendientes' mod='gestorproduccion'}</h4>

{if $reservas_agrupadas}
    <div class="reservas-container">
        {foreach from=$reservas_agrupadas key=id_cliente item=grupo}
            <div class="reserva-cliente" style="margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; width: 100%;">
                <div class="cliente-header" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <div>
                        <h5 style="margin: 0;">👤 Cliente: {$grupo.cliente}</h5>
                        <p style="margin: 5px 0 0 0;">🧑‍💼 Comercial: {$grupo.comercial}</p>
                    </div>
                </div>
                
                <div class="productos-reservados">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f5f5f5;">
                                <th style="padding: 8px; text-align: left;">Producto</th>
                                <th style="padding: 8px; text-align: left;">Referencia</th>
                                <th style="padding: 8px; text-align: left;">Cantidad</th>
                                <th style="padding: 8px; text-align: left;">Fecha</th>
                                <th style="padding: 8px; text-align: left;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$grupo.productos item=producto}
                                <tr style="border-bottom: 1px solid #eee;" class="producto-reservado">
                                    <td style="padding: 8px;">📦 {$producto.product_name}</td>
                                    <td style="padding: 8px;">🔖 {$producto.reference}</td>
                                    <td style="padding: 8px;">
                                        <span class="cantidad-reserva">🛒 {$producto.reserved_stock}</span>
                                        <input type="number" class="input-cantidad" value="{$producto.reserved_stock}" min="0" 
                                               style="display: none; width: 60px; margin-right: 5px;">
                                        <button type="button" class="btn-editar-cantidad" data-id="{$producto.id_reservation}"
                                                style="background: none; border: none; cursor: pointer;">✏️</button>
                                        <button type="button" class="btn-guardar-cantidad" data-id="{$producto.id_reservation}"
                                                style="display: none; background: none; border: none; cursor: pointer; color: green;">✔️</button>
                                    </td>
                                    <td style="padding: 8px;">🗓 {$producto.date_added|date_format:"%d-%m-%Y"}</td>
                                    <td style="padding: 8px;">
                                        <form class="form-borrar-reserva" style="display: inline;">
                                            <input type="hidden" name="id_reservation" value="{$producto.id_reservation}">
                                            <button type="submit" class="btn-borrar-reserva"
                                                    style="background: none; border: none; cursor: pointer; color: red;">❌ Borrar</button>
                                        </form>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {/foreach}
    </div>
{else}
    <p>⏳ {l s='No hay reservas pendientes' mod='gestorproduccion'}</p>
{/if}



<!-- Sección de productos habilitados -->
<h4 style="font-weight:bold;">✅ {l s='Productos Habilitados' mod='gestorproduccion'}</h4>

{if $productos_habilitados}
    <div class="productos-habilitados-container">
        {foreach from=$productos_habilitados item=producto}
            <div class="producto-habilitado">
                <p class="producto-habilitado-id">🆔 Producto ID: {$producto.id_product}</p>
                <p class="producto-habilitado-reference">🔖 Referencia: {$producto.reference}</p>
                <p class="producto-habilitado-estado">✔ Estado: Habilitado</p>
                
                <!-- Formulario para deshabilitar producto -->
                <form id="form-deshabilitar-producto-{$producto.id_product}" class="form-deshabilitar-producto">
                    <button type="submit" class="btn-deshabilitar" data-id="{$producto.id_product}">
                        ❌ Deshabilitar
                    </button>
                </form>
            </div>
        {/foreach}
    </div>
{else}
    <p>⏳ {l s='No hay productos habilitados' mod='gestorproduccion'}</p>
{/if}

<!-- Sección para filtrar por categoría -->
<h4 style="font-weight:bold;">📦 {l s='Todos los Productos' mod='gestorproduccion'}</h4>

<!-- Formulario para seleccionar categoría -->
<form id="categoria-filter-form">
    <label for="id_categoria">{l s='Selecciona una categoría:' mod='gestorproduccion'}</label>
    <select name="id_categoria" id="id_categoria" class="form-control">
        <option value="0" {if $id_categoria_seleccionada == 0}selected{/if}>
            {l s='Todas las categorías' mod='gestorproduccion'}
        </option>
        {foreach from=$categorias item=category}
            <option value="{$category.id_category}" {if $category.id_category == $id_categoria_seleccionada}selected{/if}>
                {$category.category_name}
            </option>
        {/foreach}
    </select>
</form>

<!-- Contenedor principal para productos -->
<div id="productos-container">
    {include file='module:gestorproduccion/views/templates/admin/_partials/productos.tpl' productos=$productos}
</div>

<!-- Sección de productos con fecha de llegada -->
<h4 style="font-weight:bold; display: none;">📅 {l s='Con fecha de llegada' mod='gestorproduccion'}</h4>
{if $productos_con_fecha}
    <form id="productosForm" style="display: none;">
        <div class="productos-container">
            {foreach from=$productos_con_fecha item=producto}
                <div class="producto">
                    <input type="checkbox" class="producto-checkbox" name="productos[]" 
                           value="{$producto.id_product}" 
                           data-reference="{$producto.reference}" 
                           data-id-product-attribute="{$producto.id_product_attribute}">
                    <p>🆔 {$producto.id_product}</p>
                    <p>📦 {$producto.name}</p>
                    <p>🔖 {$producto.reference}</p>
                    <p>📆 {$producto.available_date|date_format:"%d-%m-%Y"}</p>
                </div>
            {/foreach}
        </div>
{else}
    <p>⏳ {l s='No hay productos con fecha de llegada' mod='gestorproduccion'}</p>
{/if}

 <!-- Sección de productos sin fecha de llegada -->
 <h4 style="font-weight:bold;  display: none">❌ {l s='Sin fecha de llegada' mod='gestorproduccion'}</h4>
{if $productos_sin_stock_y_fecha}
    <div class="productos-container" style="display: none;">
        {foreach from=$productos_sin_stock_y_fecha item=producto}
            <div class="producto">
                <input type="checkbox" class="producto-checkbox" name="productos[]" 
                       value="{$producto.id_product}" 
                       data-reference="{$producto.reference}" 
                       data-id-product-attribute="{$producto.id_product_attribute}">
                <p>🆔 {$producto.id_product}</p>
                <p>📦 {$producto.name}</p>
                <p>🔖 {$producto.reference}</p>
                <p>📆 {l s='Sin fecha de llegada' mod='gestorproduccion'}</p>
            </div>
        {/foreach}
    </div>
    </form>
{else}
    <p>🎉 {l s='No hay productos sin stock' mod='gestorproduccion'}</p>
{/if}

<!-- Botón general para aplicar selección -->
<div id="button-container">
    <button type="button" id="btn-aplicar" class="btn btn-success">
        🔄 {l s='Habilitar reservas para seleccionados' mod='gestorproduccion'}
    </button>
</div>



{/block}
