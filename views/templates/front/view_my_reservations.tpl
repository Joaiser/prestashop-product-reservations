{extends file='customer/page.tpl'}

{block name="page_content"}

{if isset($reservas_por_cliente) && is_array($reservas_por_cliente) && count($reservas_por_cliente) > 0}
    <div class="reservas" style="display: grid; gap: 40px; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); padding: 50px 0 50px 0;">
        {foreach from=$reservas_por_cliente item=reservas key=id_customer}
            <div class="cliente" style="width: 400px; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; background-color: #f9f9f9; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                <p style="font-size: 18px; font-weight: bold; margin-bottom: 15px;">Cliente: {$reservas.nombre_cliente}</p>
                <ul class="reservas_cliente" style="list-style: none; padding-left: 0;">
                    {foreach from=$reservas.reservas item=reserva}
                        <li style="font-size: 14px;">
                            <strong style="font-weight: bold;">Producto:</strong> {$reserva.reference} <br>
                            <strong style="font-weight: bold;">Stock reservado:</strong> {$reserva.reserved_stock} <br>
                            <strong style="font-weight: bold;">Fecha de reserva:</strong> {$reserva.date_added|date_format:"%d-%m-%Y"} <br>
                        </li>
                    {/foreach}
                </ul>
            </div>
        {/foreach}
    </div>
{else}
    <p>No hay reservas hechas</p>
{/if}

{/block}
