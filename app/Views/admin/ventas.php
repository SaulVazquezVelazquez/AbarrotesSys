<?= $this->include('admin/layout_header') ?>

<div class="toolbar">
    <h1 style="margin-bottom:0;">Ventas y pedidos</h1>
    <button class="btn btn-primary btn-sm" onclick="abrirNuevaVenta()"><i class="fa-solid fa-plus"></i> Nueva venta a crédito</button>
</div>

<section class="card" style="margin:14px 0;padding:16px;">
    <form method="get" action="<?= base_url('admin/ventas') ?>" style="display:flex;align-items:end;gap:10px;flex-wrap:wrap;">
        <label class="form-group" style="margin:0;"><small>Ver todas las ventas POS de un día</small><input type="date" name="dia" class="admin-input" value="<?= esc($diaSeleccionado ?? '') ?>" required></label>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-calendar-day"></i> Ver día completo</button>
        <?php if (!empty($diaSeleccionado)): ?><a class="btn btn-secondary btn-sm" href="<?= base_url('admin/ventas') ?>">Quitar día</a><?php endif; ?>
    </form>
</section>

<?php if (!empty($diaSeleccionado)): ?>
<section class="card" style="margin:0 0 22px;padding:18px;">
    <h2 style="margin:0 0 14px;">Resumen POS · <?= esc(date('d/m/Y', strtotime($diaSeleccionado))) ?></h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:18px;">
        <div class="card" style="padding:13px;"><small>Venta total del día</small><strong style="display:block;font-size:23px;">$<?= number_format((float) ($resumenDiaPos->total ?? 0), 2) ?></strong></div>
        <div class="card" style="padding:13px;"><small>Ventas registradas</small><strong style="display:block;font-size:23px;"><?= (int) ($resumenDiaPos->cantidad_ventas ?? 0) ?></strong></div>
        <div class="card" style="padding:13px;"><small>Subtotal abarrotes</small><strong style="display:block;font-size:23px;">$<?= number_format((float) ($resumenDiaPos->subtotal_abarrotes ?? 0), 2) ?></strong></div>
        <div class="card" style="padding:13px;"><small>Subtotal verdulería</small><strong style="display:block;font-size:23px;">$<?= number_format((float) ($resumenDiaPos->subtotal_verduleria ?? 0), 2) ?></strong></div>
    </div>
    <h3 style="margin:0 0 8px;">Totales por forma de pago</h3>
    <div style="overflow:auto;margin-bottom:18px;">
        <table class="admin-table"><thead><tr><th>Forma de pago</th><th>Ventas</th><th>Abarrotes</th><th>Verdulería</th><th>Total</th></tr></thead><tbody>
        <?php foreach (($metodosDiaPos ?? []) as $metodo): ?>
            <tr><td><?= esc($metodo->metodo_pago) ?></td><td><?= (int) $metodo->cantidad_ventas ?></td><td>$<?= number_format((float) $metodo->subtotal_abarrotes, 2) ?></td><td>$<?= number_format((float) $metodo->subtotal_verduleria, 2) ?></td><td><strong>$<?= number_format((float) $metodo->total, 2) ?></strong></td></tr>
        <?php endforeach; ?>
        <?php if (empty($metodosDiaPos)): ?><tr><td colspan="5">No hay ventas POS para este día.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
    <h3 style="margin:0 0 8px;">Detalle de ventas del día</h3>
    <div style="overflow:auto;">
        <table class="admin-table"><thead><tr><th>Folio / hora</th><th>Productos</th><th>Cliente</th><th>Cajero</th><th>Pago</th><th>Abarrotes</th><th>Verdulería</th><th>Total</th><th>Cambio</th></tr></thead><tbody>
        <?php foreach (($ventasDiaPos ?? []) as $ventaDia): ?>
            <tr><td>#<?= (int) $ventaDia->id_venta ?><br><small><?= esc(date('H:i', strtotime($ventaDia->fecha_venta))) ?></small></td><td><?= esc($ventaDia->productos) ?></td><td><?= esc($ventaDia->cliente) ?></td><td><?= esc($ventaDia->cajero) ?></td><td><?= esc($ventaDia->metodo_pago) ?></td><td>$<?= number_format((float) $ventaDia->subtotal_abarrotes, 2) ?></td><td>$<?= number_format((float) $ventaDia->subtotal_verduleria, 2) ?></td><td><strong>$<?= number_format((float) $ventaDia->total, 2) ?></strong></td><td>$<?= number_format((float) $ventaDia->cambio, 2) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($ventasDiaPos)): ?><tr><td colspan="9">No hay ventas POS para este día.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
</section>
<?php endif; ?>

<form method="get" action="<?= base_url('admin/ventas') ?>" class="product-filters" style="margin:14px 0 22px">
    <input type="search" name="cliente" class="admin-input" placeholder="Cliente o teléfono" value="<?= esc($filtros['cliente']) ?>" aria-label="Filtrar por cliente o teléfono">
    <label class="form-group" style="margin:0"><small>Desde</small><input type="datetime-local" name="desde" class="admin-input" value="<?= esc($filtros['desde']) ?>"></label>
    <label class="form-group" style="margin:0"><small>Hasta</small><input type="datetime-local" name="hasta" class="admin-input" value="<?= esc($filtros['hasta']) ?>"></label>
    <select name="tipo" class="admin-select"><option value="">Todos los tipos</option><option value="CREDITO" <?= $filtros['tipo'] === 'CREDITO' ? 'selected' : '' ?>>A crédito / pedidos</option><option value="MOSTRADOR" <?= $filtros['tipo'] === 'MOSTRADOR' ? 'selected' : '' ?>>Mostrador</option></select>
    <select name="metodo_pago" class="admin-select"><option value="">Todas las formas de pago</option><option value="CREDITO" <?= $filtros['metodo_pago'] === 'CREDITO' ? 'selected' : '' ?>>Crédito</option><option value="EFECTIVO" <?= $filtros['metodo_pago'] === 'EFECTIVO' ? 'selected' : '' ?>>Efectivo</option><option value="TRANSFERENCIA" <?= $filtros['metodo_pago'] === 'TRANSFERENCIA' ? 'selected' : '' ?>>Transferencia</option><option value="TARJETA" <?= $filtros['metodo_pago'] === 'TARJETA' ? 'selected' : '' ?>>Tarjeta</option><option value="MIXTO" <?= $filtros['metodo_pago'] === 'MIXTO' ? 'selected' : '' ?>>Mixto</option></select>
    <select name="estatus" class="admin-select"><option value="">Todos los estatus de crédito</option><?php foreach (['PENDIENTE','PARCIAL','PAGADA','VENCIDA','CANCELADA'] as $estatusFiltro): ?><option value="<?= esc($estatusFiltro) ?>" <?= $filtros['estatus'] === $estatusFiltro ? 'selected' : '' ?>><?= esc(ucfirst(strtolower($estatusFiltro))) ?></option><?php endforeach; ?></select>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Aplicar filtros</button>
    <a class="btn btn-secondary btn-sm" href="<?= base_url('admin/ventas') ?>">Limpiar</a>
</form>

<table class="admin-table">
    <thead><tr><th>Fecha</th><th>Cliente</th><th>Total</th><th>Saldo</th><th>Pago / recogida</th><th>Estatus</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($ventas as $v): ?>
        <tr>
            <td><?= esc($v->fecha_venta) ?></td>
            <td><?= esc($v->cliente) ?> (<?= esc($v->telefono) ?>)</td>
            <td>$<?= number_format($v->total, 2) ?></td>
            <td>$<?= number_format($v->saldo_pendiente, 2) ?></td>
            <td><?php if (!empty($v->pickup_at)): ?>Recoger <?= esc(date('d/m/Y H:i', strtotime($v->pickup_at))) ?><br><small><?= esc($v->pedido_estatus) ?></small><?php else: ?>Vence <?= esc($v->fecha_limite_pago) ?><?php endif; ?></td>
            <td><span class="badge badge-<?= strtolower($v->estatus) ?>"><?= esc($v->estatus) ?></span></td>
            <td><a class="row-link" href="<?= base_url('admin/ventas/' . $v->id_venta) ?>">Ver</a><a class="row-link" style="margin-left:8px" target="_blank" rel="noopener" href="<?= base_url('admin/ventas/' . $v->id_venta . '/ticket') ?>">Imprimir ticket</a><?php if (!empty($v->pickup_at) && $v->pedido_estatus !== 'ENTREGADO'): ?><button type="button" class="btn btn-secondary btn-sm" style="margin-left:7px;" onclick="avanzarPedido(<?= (int) $v->id_venta ?>,'<?= esc($v->pedido_estatus, 'js') ?>')">Avanzar recogida</button><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($ventas)): ?><tr><td colspan="7">Sin ventas registradas.</td></tr><?php endif; ?>
    </tbody>
</table>

<script>
async function avanzarPedido(id, estadoActual) {
    const siguiente = { RECIBIDO: 'PREPARANDO', PREPARANDO: 'LISTO', LISTO: 'ENTREGADO' }[estadoActual];
    if (!siguiente || !confirm('¿Cambiar el pedido #' + id + ' a ' + siguiente + '?')) return;
    const result = await adminPost('<?= base_url() ?>admin/ventas/pedido/' + id + '/estado', { pedido_estatus: siguiente });
    if (result.respuesta) window.location.reload(); else alert(result.mensaje);
}
</script>

<div class="toolbar" style="margin-top:30px;"><h2 style="margin:0;">Ventas de mostrador</h2><a class="btn btn-secondary btn-sm" href="<?= base_url('admin/pos') ?>">Abrir punto de venta</a></div>
<p style="color:var(--gris-texto);font-size:13px;">Las ventas cobradas en efectivo, tarjeta o transferencia también quedan visibles aquí.</p>
<table class="admin-table">
    <thead><tr><th>Folio</th><th>Fecha</th><th>Productos</th><th>Cliente</th><th>Cajero</th><th>Forma de pago</th><th>Total</th><th>Cambio</th><th></th></tr></thead>
    <tbody>
        <?php foreach (($ventasMostrador ?? []) as $v): ?>
        <tr><td>#<?= esc($v->id_venta) ?></td><td><?= esc($v->fecha_venta) ?></td><td><?= esc($v->productos) ?></td><td><?= esc($v->cliente) ?></td><td><?= esc($v->cajero) ?></td><td><?= esc($v->metodo_pago) ?></td><td>$<?= number_format($v->total, 2) ?></td><td>$<?= number_format($v->cambio, 2) ?></td><td><a class="row-link" target="_blank" rel="noopener" href="<?= base_url('admin/pos/ticket/' . $v->id_venta) ?>">Imprimir ticket</a></td></tr>
        <?php endforeach; ?>
        <?php if (empty($ventasMostrador)): ?><tr><td colspan="9">Sin ventas de mostrador registradas.</td></tr><?php endif; ?>
    </tbody>
</table>

<!-- Modal: nueva venta -->
<div class="modal-overlay" id="modalVenta">
    <div class="modal-box" style="max-width:640px;">
        <span class="modal-close" onclick="closeModal('modalVenta')">&times;</span>
        <h3>Nueva venta a crédito</h3>

        <div class="form-group">
            <label>Buscar cliente (nombre o teléfono)</label>
            <input type="text" id="buscarCliente" class="admin-input" style="width:100%;" placeholder="Escribe para buscar..." oninput="buscarClientes(this.value)">
            <div id="resultadosCliente" style="background:var(--negro); border-radius:6px; margin-top:4px;"></div>
        </div>

        <div id="infoCliente" style="display:none; padding:12px; background:var(--negro); border-radius:6px; margin-bottom:14px;">
            <p><strong id="infoNombre"></strong></p>
            <p>Crédito disponible: <strong id="infoDisponible"></strong></p>
            <input type="hidden" id="v_id_cliente">
        </div>

        <h4>Artículos</h4>
        <div id="listaItems"></div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="agregarItem()">+ Agregar artículo</button>

        <p style="text-align:right; margin-top:14px; font-family:var(--font-titulo); font-size:20px;">Total: $<span id="totalVenta">0.00</span></p>

        <div id="validacionLimite" class="form-mensaje"></div>

        <div class="form-group">
            <label>Plazo de pago</label>
            <select id="v_plazo" class="admin-select" style="width:100%;">
                <option value="7">Semanal (7 días)</option>
                <option value="15" selected>Quincenal (15 días)</option>
                <option value="30">Mensual (30 días)</option>
            </select>
        </div>

        <div class="form-group">
            <label>Confirmación de entrega</label>
            <select id="v_metodo_confirmacion" class="admin-select" style="width:100%;" onchange="toggleConfirmacion()">
                <option value="NINGUNO">Ninguna</option>
                <option value="PIN">PIN del cliente</option>
                <option value="FIRMA">Firma en papel (solo registrar que se hizo)</option>
            </select>
        </div>
        <div class="form-group" id="grupoPin" style="display:none;">
            <label>PIN del cliente</label>
            <input type="text" id="v_pin" maxlength="4" pattern="[0-9]{4}">
        </div>

        <button type="button" class="btn btn-primary btn-full" onclick="confirmarVenta()">Registrar venta a crédito</button>
        <p id="mensajeVenta" class="form-mensaje"></p>
    </div>
</div>

<script>
const BASE = '<?= base_url() ?>';
const PRODUCTOS = <?= json_encode($productos) ?>;
let itemCounter = 0;
let clienteSeleccionado = null;

function abrirNuevaVenta() {
    document.getElementById('listaItems').innerHTML = '';
    document.getElementById('infoCliente').style.display = 'none';
    document.getElementById('buscarCliente').value = '';
    document.getElementById('resultadosCliente').innerHTML = '';
    document.getElementById('mensajeVenta').textContent = '';
    document.getElementById('validacionLimite').textContent = '';
    clienteSeleccionado = null;
    itemCounter = 0;
    agregarItem();
    openModal('modalVenta');
}

async function buscarClientes(termino) {
    if (termino.length < 2) { document.getElementById('resultadosCliente').innerHTML = ''; return; }
    const data = await adminGet(BASE + 'admin/clientes/buscarJson?q=' + encodeURIComponent(termino));
    const cont = document.getElementById('resultadosCliente');
    cont.innerHTML = '';

    if (!data.data || data.data.length === 0) {
        cont.innerHTML = '<p style="padding:8px; color:var(--gris-texto); font-size:13px;">Sin resultados.</p>';
        return;
    }

    data.data.forEach(function (c) {
        const div = document.createElement('div');
        div.style = 'padding:8px 10px; cursor:pointer; border-bottom:1px solid rgba(255,255,255,.06); font-size:13.5px;';
        div.textContent = c.nombre_completo + ' — ' + c.telefono + ' (disponible: $' + Number(c.credito_disponible).toFixed(2) + ')';
        div.addEventListener('click', () => seleccionarCliente(c));
        cont.appendChild(div);
    });
}

function seleccionarCliente(c) {
    clienteSeleccionado = c;
    document.getElementById('infoCliente').style.display = 'block';
    document.getElementById('infoNombre').textContent = c.nombre_completo + ' — ' + c.telefono;
    document.getElementById('infoDisponible').textContent = '$' + Number(c.credito_disponible).toFixed(2);
    document.getElementById('resultadosCliente').innerHTML = '';
    document.getElementById('buscarCliente').value = c.nombre_completo;
    calcularTotal();
}

function agregarItem() {
    itemCounter++;
    const id = itemCounter;
    const div = document.createElement('div');
    div.className = 'form-group';
    div.id = 'item_' + id;
    div.style = 'display:flex; gap:8px; align-items:flex-end; border-bottom:1px solid rgba(255,255,255,.06); padding-bottom:10px; margin-bottom:10px;';

    let opciones = '<option value="">Artículo libre (escribir abajo)</option>';
    PRODUCTOS.forEach(p => { opciones += `<option value="${p.id_producto}" data-precio="${p.precio}">${p.nombre} - $${p.precio}</option>`; });

    div.innerHTML = `
        <div style="flex:2;">
            <label>Producto</label>
            <select class="admin-select item-producto" style="width:100%;" onchange="autocompletarPrecio(${id}, this)">${opciones}</select>
            <input type="text" class="admin-input item-descripcion" style="width:100%; margin-top:4px; display:none;" placeholder="Descripción del artículo">
        </div>
        <div style="width:70px;"><label>Cant.</label><input type="number" class="admin-input item-cantidad" value="1" min="0.01" step="0.01" onchange="calcularTotal()"></div>
        <div style="width:100px;"><label>Precio</label><input type="number" class="admin-input item-precio" step="0.01" onchange="calcularTotal()"></div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('item_${id}').remove(); calcularTotal();">Quitar</button>
    `;
    document.getElementById('listaItems').appendChild(div);

    div.querySelector('.item-producto').addEventListener('change', function () {
        div.querySelector('.item-descripcion').style.display = this.value === '' ? 'block' : 'none';
    });

    calcularTotal();
}

function autocompletarPrecio(id, select) {
    const div = document.getElementById('item_' + id);
    const precio = select.selectedOptions[0].dataset.precio;
    if (precio) div.querySelector('.item-precio').value = precio;
    calcularTotal();
}

function calcularTotal() {
    let total = 0;
    document.querySelectorAll('#listaItems > div').forEach(div => {
        const cant = parseFloat(div.querySelector('.item-cantidad').value) || 0;
        const precio = parseFloat(div.querySelector('.item-precio').value) || 0;
        total += cant * precio;
    });
    document.getElementById('totalVenta').textContent = total.toFixed(2);
    if (clienteSeleccionado) validarLimiteEnVivo(total);
    return total;
}

async function validarLimiteEnVivo(total) {
    const data = await adminGet(BASE + 'admin/ventas/validarLimite?id_cliente=' + clienteSeleccionado.id_cliente + '&monto=' + total);
    const div = document.getElementById('validacionLimite');
    div.textContent = data.mensaje;
    div.className = 'form-mensaje ' + (data.respuesta ? 'ok' : 'error');
}

function toggleConfirmacion() {
    document.getElementById('grupoPin').style.display = document.getElementById('v_metodo_confirmacion').value === 'PIN' ? 'block' : 'none';
}

async function confirmarVenta() {
    if (!clienteSeleccionado) { alert('Selecciona un cliente primero (usa la búsqueda de arriba).'); return; }

    const items = [];
    document.querySelectorAll('#listaItems > div').forEach(div => {
        const idProducto = div.querySelector('.item-producto').value;
        items.push({
            id_producto: idProducto || null,
            descripcion_libre: idProducto ? null : div.querySelector('.item-descripcion').value,
            cantidad: parseFloat(div.querySelector('.item-cantidad').value) || 0,
            precio_unitario: parseFloat(div.querySelector('.item-precio').value) || 0,
        });
    });

    const data = {
        id_cliente: clienteSeleccionado.id_cliente,
        items: JSON.stringify(items),
        plazo_dias: document.getElementById('v_plazo').value,
        metodo_confirmacion: document.getElementById('v_metodo_confirmacion').value,
        confirmacion_valor: document.getElementById('v_metodo_confirmacion').value === 'PIN' ? document.getElementById('v_pin').value : 'firma en papel',
    };

    const resultado = await adminPost(BASE + 'admin/ventas/guardar', data);
    const msg = document.getElementById('mensajeVenta');
    msg.textContent = resultado.mensaje;
    msg.className = 'form-mensaje ' + (resultado.respuesta ? 'ok' : 'error');
    if (resultado.respuesta) setTimeout(() => window.location.reload(), 1200);
}
</script>

<?= $this->include('admin/layout_footer') ?>
