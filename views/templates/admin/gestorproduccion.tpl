{block name="content"}
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{$smarty.session.csrf_token}">
    <title>{$meta_title}</title>
</head>

<h3 style="margin: 0;">🚀 {l s='Gestor de Producción' mod='gestorproduccion'}</h3>

<!-- Sección de productos con fecha de llegada -->
<h4 style="font-weight:bold;">📅 {l s='Con fecha de llegada' mod='gestorproduccion'}</h4>
{if $productos_con_fecha}
    <form id="productosForm">
        <div class="productos-container">
            {foreach from=$productos_con_fecha item=producto}
                <div class="producto">
                    <input type="checkbox" class="producto-checkbox" name="productos[]" value="{$producto.id_product}">
                    <p>🆔 {$producto.id_product}</p>
                    <p>📦 {$producto.name}</p>
                    <p>🔖 {$producto.reference}</p>
                    <p>📆 {$producto.available_date|date_format:"%d-%m-%Y"}</p>

                    <button type="button" class="btn btn-primary btn-individual" data-id="{$producto.id_product}">
                        ✅ {l s='Habilitar reservas' mod='gestorproduccion'}
                    </button>
                </div>
            {/foreach}
        </div>
    </form>
{else}
    <p>⏳ {l s='No hay productos con fecha de llegada' mod='gestorproduccion'}</p>
{/if}

<!-- Sección de productos sin fecha de llegada -->
<h4 style="font-weight:bold;">❌ {l s='Sin fecha de llegada' mod='gestorproduccion'}</h4>
{if $productos_sin_stock_y_fecha}
    <form id="productosForm">
        <div class="productos-container">
            {foreach from=$productos_sin_stock_y_fecha item=producto}
                <div class="producto">
                    <input type="checkbox" class="producto-checkbox" name="productos[]" value="{$producto.id_product}">
                    <p>🆔 {$producto.id_product}</p>
                    <p>📦 {$producto.name}</p>
                    <p>🔖 {$producto.reference}</p>

                    <button type="button" class="btn btn-primary btn-individual" data-id="{$producto.id_product}">
                        ✅ {l s='Habilitar reservas' mod='gestorproduccion'}
                    </button>
                </div>
            {/foreach}
        </div>
    </form>
{else}
    <p>🎉 {l s='No hay productos sin stock' mod='gestorproduccion'}</p>
{/if}

<!-- Botón general para aplicar selección -->
<div id="button-container">
    <button type="submit" id="btn-aplicar" class="btn btn-success" style="display:none;">
        🔄 {l s='Habilitar reservas para seleccionados' mod='gestorproduccion'}
    </button>
</div>

{/block}
