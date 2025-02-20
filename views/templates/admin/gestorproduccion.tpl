{block name="content"}
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{$smarty.session.csrf_token}">
    <title>{$meta_title}</title>
</head>


        <h3>{l s='Gestor de Producción' mod='gestorproduccion'}</h3>

        <h4>{l s='Productos sin stock y sin fecha de llegada' mod='gestorproduccion'}</h4>
        {if $productos_sin_stock_y_fecha}
            <div class="productos-container">
                {foreach from=$productos_sin_stock_y_fecha item=producto}
                    <div class="producto">
                        <p class="producto-id">ID: {$producto.id_product}</p>
                        <p class="producto-nombre">{$producto.name}</p>
                        <p class="producto-referencia">{$producto.reference}</p>

                        <button type="button" class="btn btn-primary btn-individual" data-id="{$producto.id_product}">

                        {l s='Habilitar reservas' mod='gestorproduccion'}
                        </button>
                    </div>
                {/foreach}
                <button type="submit" id="btn-aplicar" class="btn btn.succes" style="display:none;">
                    {l s='Habilitar reservas para seleccionados' mod='gestorproduccion'}
                </button>
            </div>
        {else}
            <p>{l s='No hay productos sin stock y sin fecha de llegada' mod='gestorproduccion'}</p>
        {/if}
     
{/block}
