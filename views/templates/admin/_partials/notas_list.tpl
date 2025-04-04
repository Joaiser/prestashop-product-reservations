{if $notas|@count > 0}
    {foreach from=$notas item=nota}
        <div class="nota-item" data-notaid="{$nota.id_user}" data-userid="{$nota.id_user}">
            <div class="nota-header">
                <p><strong>Cliente:</strong> {$nota.cliente_nombre} {$nota.cliente_apellido}</p>
                <p><strong>Comercial:</strong> {$nota.comercial_nombre} {$nota.comercial_apellido}</p>
            </div>

            <div class="nota-body">
                {foreach from=$nota.notas item=notaItem}
                <div class="nota-contenido" data-nota-id="{$notaItem.id_nota}" data-user-id="{$nota.id_user}">
                    <p id="nota-text-{$notaItem.id_nota}" class="nota-texto">
                        {$notaItem.nota}
                        <button class="nota-edit-icon" id="edit-icon-{$notaItem.id_nota}">✏️</button>
                        <button class="nota-save-icon" style="display: none;" id="save-icon-{$notaItem.id_nota}">💾</button>
                        <button class="nota-delete-icon" id="delete-icon-{$notaItem.id_nota}">🗑️</button>
                    </p>
                    <textarea id="nota-edit-{$notaItem.id_nota}" class="nota-edit textarea-nota" style="display:none;">
                        {$notaItem.nota}
                    </textarea>
                </div>
                {/foreach}
            </div>
        </div>
    {/foreach}
{else}
    <p>No hay notas disponibles para mostrar.</p>
{/if}