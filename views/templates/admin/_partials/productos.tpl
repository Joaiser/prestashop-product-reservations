<style>
    /* Contenedor principal del grid */
    .productos-grid {
        display: grid;
        gap: 20px;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        padding: 20px 0;
    }

    /* Tarjeta de producto individual */
    .producto-card {
        background: #f5f5f5;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #ddd;
        transition: all 0.3s ease;
        position: relative;
    }

    .producto-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* Contenedor del checkbox */
    .producto-checkbox-container {
        position: absolute;
        top: 5%;
        left: 60%;
    }

    .producto-checkbox {
        transform: scale(1.2);
        cursor: pointer;
    }

    /* Información del producto */
    .producto-info {
        margin-top: 25px;
    }

    .producto-id {
        font-size: 12px;
        color: #666;
        margin: 0 0 5px 0;
    }

    .producto-nombre {
        font-weight: 500;
        margin: 0 0 8px 0;
        line-height: 1.3;
        word-break: break-word;
    }

    .producto-reference {
        font-size: 13px;
        color: #555;
        margin: 0;
    }

    /* Estados de carga y error */
    .loading, .error-message {
        text-align: center;
        padding: 40px;
        grid-column: 1 / -1;
    }

    .error-message {
        color: #d9534f;
    }
</style>

{if $productos}
    <div class="productos-grid">
        {foreach from=$productos item=producto}
            <div class="producto-card">
                <div class="producto-checkbox-container">
                    <input type="checkbox" 
                           class="producto-checkbox" 
                           name="productos[]" 
                           value="{$producto.id_product}" 
                           data-reference="{$producto.reference}" 
                           data-id-product-attribute="{$producto.id_product_attribute}">
                </div>
                <div class="producto-info">
                    <p class="producto-id">🆔 {$producto.id_product}</p>
                    <p class="producto-nombre">
                        📦 {if !empty($producto.product_name)}{$producto.product_name|truncate:25:'...'}{else}Nombre no disponible{/if}
                    </p>
                    <p class="producto-reference">
                        🔖 {if !empty($producto.reference)}{$producto.reference|truncate:15:'...'}{else}Ref. no disponible{/if}
                    </p>
                </div>
            </div>
        {/foreach}
    </div>
{else}
    <p class="no-productos">⏳ {l s='No hay productos disponibles' mod='gestorproduccion'}</p>
{/if}