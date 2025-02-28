{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
    <h3 style="margin: 0;">🚀 {l s='Gestor de Producción' mod='gestorproduccion'}</h3>

    <!-- Sección de productos con fecha de llegada -->
    <h4 style="font-weight:bold;">📅 {l s='Con fecha de llegada' mod='gestorproduccion'}</h4>
    {if $productos_con_fecha}
        <form id="productosForm">
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
    <h4 style="font-weight:bold;">❌ {l s='Sin fecha de llegada' mod='gestorproduccion'}</h4>
    {if $productos_sin_stock_y_fecha}
        <div class="productos-container">
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
    {else}
        <p>🎉 {l s='No hay productos sin stock' mod='gestorproduccion'}</p>
    {/if}

    <!-- Botón general para aplicar selección -->
    <div id="button-container">
        <button type="submit" id="btn-aplicar" class="btn btn-success" style="display:none;">
            🔄 {l s='Habilitar reservas para seleccionados' mod='gestorproduccion'}
        </button>
    </div>

    </form>

<script>
    const ajaxUrl = "{$link->getAdminLink('AdminGestorProduccion')|escape:'javascript':'UTF-8'}";
    const csrfToken = "{$token|escape:'javascript':'UTF-8'}";
</script>

{/block}