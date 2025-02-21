{extends file="page.tpl"}

{block name='page_content'}
    <h1>{l s='Reserva tu Producto' mod='gestorproduccion'}</h1>
    <form id="reservation-form">
        <div class="product-list">
            {foreach from=$productos item=producto}
                <div class="product-item">
                    <input type="checkbox" name="product_reservation[]" value="{$producto.id_product}" class="producto-checkbox" />
                    <span>{$producto.name} - {if $producto.available_date}{$producto.available_date|date_format:'%d/%m/%Y'}{/if}</span>
                </div>
            {/foreach}
        </div>
        <button type="submit" id="btn-reservar" disabled>{l s='Reservar' mod='gestorproduccion'}</button>
    </form>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const checkboxes = document.querySelectorAll(".producto-checkbox");
            const btnReservar = document.getElementById("btn-reservar");

            // Habilitar el botón de reserva si hay algún producto seleccionado
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener("change", function() {
                    const anyChecked = Array.from(checkboxes).some(c => c.checked);
                    btnReservar.disabled = !anyChecked;
                });
            });

            document.getElementById("reservation-form").addEventListener("submit", function(e) {
                e.preventDefault();
                const selectedProducts = Array.from(checkboxes).filter(c => c.checked).map(c => c.value);

                if (selectedProducts.length > 0) {
                    habilitarReserva(selectedProducts);
                } else {
                    alert("{l s='Por favor, selecciona al menos un producto.' mod='gestorproduccion'}");
                }
            });

            function habilitarReserva(productIds) {
                const token = getAdminToken();
                fetch('index.php?controller=product_reservation&token=' + token, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        'products': JSON.stringify(productIds)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("{l s='Reserva realizada con éxito.' mod='gestorproduccion'}");
                    } else {
                        alert("{l s='Hubo un error al realizar la reserva.' mod='gestorproduccion'}");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            function getAdminToken() {
                const tokenMetaTag = document.querySelector('meta[name="csrf-token"]');
                return tokenMetaTag ? tokenMetaTag.getAttribute('content') : '';
            }
        });
    </script>
{/block}
