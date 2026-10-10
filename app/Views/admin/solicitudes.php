<?= $this->include('admin/layout_header') ?>

<h1>Solicitudes de compra</h1>
<p style="color:var(--gris-texto); font-size:13.5px;">Pedidos enviados desde el catálogo público. Apruébalos para convertirlos en una venta a crédito real (se valida el límite disponible del cliente).</p>

<table class="admin-table">
    <thead><tr><th>Fecha</th><th>Cliente</th><th>Teléfono</th><th>Total estimado</th><th>Estatus</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($solicitudes as $s): ?>
        <tr>
            <td><?= esc($s->fecha_creacion) ?></td>
            <td><?= esc($s->nombre_cliente) ?></td>
            <td><?= esc($s->telefono) ?></td>
            <td>$<?= number_format($s->total_estimado, 2) ?></td>
            <td><span class="badge badge-<?= strtolower($s->estatus) ?>"><?= esc($s->estatus) ?></span></td>
            <td>
                <?php if ($s->estatus === 'PENDIENTE'): ?>
                    <button class="btn btn-primary btn-sm" onclick="abrirSolicitud(<?= esc($s->id_solicitud) ?>)">Revisar</button>
                <?php else: ?>
                    <button class="btn btn-secondary btn-sm" onclick="abrirSolicitud(<?= esc($s->id_solicitud) ?>)">Ver</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($solicitudes)): ?><tr><td colspan="6">Sin solicitudes todavía.</td></tr><?php endif; ?>
    </tbody>
</table>

<!-- Modal: detalle de solicitud -->
<div class="modal-overlay" id="modalSolicitud">
    <div class="modal-box" style="max-width:600px;">
        <span class="modal-close" onclick="closeModal('modalSolicitud')">&times;</span>
        <h3>Solicitud #<span id="sId"></span></h3>
        <p><strong id="sNombre"></strong> — <span id="sTelefono"></span></p>
        <p id="sEmail" style="color:var(--gris-texto); font-size:13px;"></p>
        <p id="sNotas" style="color:var(--gris-texto); font-size:13px;"></p>

        <table class="admin-table" style="margin:14px 0;">
            <thead><tr><th>Artículo</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead>
            <tbody id="sItems"></tbody>
        </table>
        <p style="text-align:right; font-family:var(--font-titulo); font-size:18px;">Total: $<span id="sTotal"></span></p>

        <div id="bloqueAccion">
            <div class="form-group">
                <label>Cliente registrado (crea o busca primero en "Clientes" si aún no existe)</label>
                <select id="s_id_cliente" class="admin-select" style="width:100%;">
                    <option value="">Selecciona un cliente</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= esc($c->id_cliente) ?>" data-telefono="<?= esc($c->telefono) ?>">
                            <?= esc($c->nombre_completo) ?> (<?= esc($c->telefono) ?>) — disponible: $<?= number_format($c->credito_disponible, 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Plazo de pago</label>
                <select id="s_plazo" class="admin-select" style="width:100%;">
                    <option value="7">Semanal (7 días)</option>
                    <option value="15" selected>Quincenal (15 días)</option>
                    <option value="30">Mensual (30 días)</option>
                </select>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn btn-primary btn-full" onclick="aprobar()">Aprobar y convertir en venta</button>
                <button class="btn btn-danger btn-full" onclick="rechazar()">Rechazar</button>
            </div>
        </div>
        <p id="mensajeSolicitud" class="form-mensaje"></p>
    </div>
</div>

<script>
const BASE = '<?= base_url() ?>';
let solicitudActual = null;

async function abrirSolicitud(id) {
    const res = await adminGet(BASE + 'admin/solicitudes/' + id);
    if (!res.respuesta) { alert(res.mensaje || 'No se pudo cargar la solicitud.'); return; }

    solicitudActual = res.data.solicitud;
    document.getElementById('sId').textContent = solicitudActual.id_solicitud;
    document.getElementById('sNombre').textContent = solicitudActual.nombre_cliente;
    document.getElementById('sTelefono').textContent = solicitudActual.telefono;
    document.getElementById('sEmail').textContent = solicitudActual.email ? ('Email: ' + solicitudActual.email) : '';
    document.getElementById('sNotas').textContent = solicitudActual.notas ? ('Notas: ' + solicitudActual.notas) : '';
    document.getElementById('sTotal').textContent = Number(solicitudActual.total_estimado).toFixed(2);

    const tbody = document.getElementById('sItems');
    tbody.innerHTML = '';
    res.data.items.forEach(i => {
        tbody.innerHTML += `<tr><td>${i.nombre_producto}</td><td>${i.cantidad}</td><td>$${Number(i.precio_unitario).toFixed(2)}</td><td>$${Number(i.subtotal).toFixed(2)}</td></tr>`;
    });

    // Intenta preseleccionar el cliente si el teléfono ya coincide con uno registrado
    const select = document.getElementById('s_id_cliente');
    select.value = '';
    for (const opt of select.options) {
        if (opt.dataset.telefono === solicitudActual.telefono) { select.value = opt.value; break; }
    }

    document.getElementById('bloqueAccion').style.display = solicitudActual.estatus === 'PENDIENTE' ? 'block' : 'none';
    document.getElementById('mensajeSolicitud').textContent = '';
    openModal('modalSolicitud');
}

async function aprobar() {
    const idCliente = document.getElementById('s_id_cliente').value;
    if (!idCliente) { alert('Selecciona el cliente registrado al que corresponde esta solicitud.'); return; }

    const r = await adminPost(BASE + 'admin/solicitudes/' + solicitudActual.id_solicitud + '/aprobar', {
        id_cliente: idCliente,
        plazo_dias: document.getElementById('s_plazo').value,
    });
    document.getElementById('mensajeSolicitud').textContent = r.mensaje;
    document.getElementById('mensajeSolicitud').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 1200);
}

async function rechazar() {
    const motivo = prompt('Motivo del rechazo (opcional):') || 'No especificado';
    const r = await adminPost(BASE + 'admin/solicitudes/' + solicitudActual.id_solicitud + '/rechazar', { motivo });
    document.getElementById('mensajeSolicitud').textContent = r.mensaje;
    document.getElementById('mensajeSolicitud').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 900);
}
</script>

<?= $this->include('admin/layout_footer') ?>
