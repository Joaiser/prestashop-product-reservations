{extends file='customer/page.tpl'}

{block name="page_content"}

{if isset($reservas_por_cliente) && is_array($reservas_por_cliente) && count($reservas_por_cliente) > 0}
    <div class="reservas" style="display: grid; gap: 40px; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); padding: 50px 0 50px 0;">
        {foreach from=$reservas_por_cliente item=cliente key=id_cliente}
            <div class="cliente" style="width: 400px; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; background-color: #f9f9f9; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                <p style="font-size: 18px; font-weight: bold; margin-bottom: 15px;">
                    Cliente: {$cliente.nombre_cliente}
                </p>

                {* Sección de Notas *}
                {if isset($notas_clientes[$id_cliente]) && count($notas_clientes[$id_cliente]) > 0}
                    <div style="margin-bottom: 15px;">
                        <strong style="display: block; margin-bottom: 5px;">Notas:</strong>
                        <ul style="list-style: none; padding-left: 0; margin-top: 0;">
                            {foreach from=$notas_clientes[$id_cliente] item=nota}
                                <li style="padding: 8px; background: #fff; border-left: 3px solid #17a2b8; margin-bottom: 5px;">
                                    {$nota}
                                </li>
                            {/foreach}
                        </ul>
                    </div>
                {else}
                    <p style="color: #999; margin-bottom: 15px;">Sin notas disponibles</p>
                {/if}

                {* Sección de Reservas *}
                <div>
                    <strong style="display: block; margin-bottom: 5px;">Reservas:</strong>
                    <ul style="list-style: none; padding-left: 0;">
                        {foreach from=$cliente.reservas item=reserva}
                            <li style="padding: 10px 0; border-bottom: 1px dashed #eee;">
                                <strong>Producto:</strong> {$reserva.reference}<br>
                                <strong>Stock reservado:</strong> {$reserva.reserved_stock}<br>
                                <strong>Estado:</strong> {$reserva.status|ucfirst}<br>
                                <strong>Fecha:</strong> {$reserva.date_added|date_format:"%d-%m-%Y"}
                            </li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        {/foreach}
    </div>
{else}
    <p style="text-align: center; padding: 50px; color: #999;">No hay reservas activas</p>
{/if}

{/block}
