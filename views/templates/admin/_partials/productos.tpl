<style>
  .productos-tabla {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
  }

  .productos-tabla th,
  .productos-tabla td {
    border: 1px solid #ccc;
    padding: 4px 6px;
    text-align: left;
    vertical-align: top;
  }

  .productos-tabla th {
    background-color: #f0f0f0;
    font-weight: 600;
  }

  .producto-checkbox {
    transform: scale(1.1);
    cursor: pointer;
  }

  .loading,
  .error-message {
    text-align: center;
    padding: 20px;
  }

  .error-message {
    color: #d9534f;
  }
</style>

{if $productos}
<table class="productos-tabla">
  <thead>
    <tr>
      <th>✔️</th>
      <th>ID</th>
      <th>Nombre</th>
      <th>Referencia</th>
    </tr>
  </thead>
  <tbody>
    {foreach from=$productos item=producto}
    <tr>
      <td>
        <input type="checkbox" class="producto-checkbox" name="productos[]" value="{$producto.id_product}"
          data-reference="{$producto.reference}" data-id-product-attribute="{$producto.id_product_attribute}">
      </td>
      <td>{$producto.id_product}</td>
      <td>{if !empty($producto.product_name)}{$producto.product_name|truncate:25:'...'}{else}Nombre no disponible{/if}
      </td>
      <td>{if !empty($producto.reference)}{$producto.reference|truncate:15:'...'}{else}Ref. no disponible{/if}</td>
    </tr>
    {/foreach}
  </tbody>
</table>
{else}
<p class="no-productos">⏳ {l s='No hay productos disponibles' mod='gestorproduccion'}</p>
{/if}