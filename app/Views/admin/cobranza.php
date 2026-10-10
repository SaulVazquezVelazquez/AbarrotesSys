<?= $this->include('admin/layout_header') ?>

<h1>Cobranza</h1>

<div class="chart-card">
    <div class="toolbar"><div><h2 style="margin-bottom:4px;">Próximos seguimientos</h2><p class="section-subtitle">Gestiones programadas para hoy o los próximos 7 días.</p></div><button class="btn btn-primary btn-sm" onclick="abrirNuevoSeguimiento()"><i class="fa-solid fa-plus"></i> Registrar gestión</button></div>
    <table class="admin-table">
        <thead><tr><th>Fecha</th><th>Cliente</th><th>Último resultado</th><th>Detalle</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($seguimientos as $s): ?>
            <tr><td><?= esc($s->proxima_fecha) ?></td><td><a class="row-link" href="<?= base_url('admin/clientes/' . $s->id_cliente) ?>"><?= esc($s->nombre_completo) ?></a><br><small><?= esc($s->telefono) ?></small></td><td><span class="badge badge-pendiente"><?= esc(str_replace('_', ' ', $s->resultado)) ?></span></td><td><?= esc($s->detalle ?: 'Sin notas') ?></td><td><a class="btn btn-secondary btn-sm" href="<?= base_url('admin/clientes/' . $s->id_cliente) ?>">Ver cliente</a></td></tr>
            <?php endforeach; ?>
            <?php if (empty($seguimientos)): ?><tr><td colspan="5">No tienes seguimientos próximos. Registra una gestión para agendar la siguiente.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="chart-card" id="avisos-pago">
    <h2>Avisos de pago por confirmar</h2>
    <p class="section-subtitle">El cliente indicó que realizará un pago. Registra el abono cuando lo recibas para cerrar el aviso.</p>
    <table class="admin-table">
        <thead><tr><th>Fecha de aviso</th><th>Cliente</th><th>Monto indicado</th><th>Método / notas</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($intenciones_pago as $intencion): ?>
            <tr><td><?= esc($intencion->fecha_programada) ?></td><td><a class="row-link" href="<?= base_url('admin/clientes/' . $intencion->id_cliente) ?>"><?= esc($intencion->nombre_completo) ?></a><br><small><?= esc($intencion->telefono) ?></small></td><td>$<?= number_format((float) $intencion->monto_reportado, 2) ?></td><td><?= esc($intencion->texto_final) ?></td><td><a class="btn btn-primary btn-sm" href="<?= base_url('admin/pagos?cliente=' . $intencion->id_cliente . '&monto=' . urlencode($intencion->monto_reportado) . '&intento=' . $intencion->id_mensaje) ?>">Registrar abono</a></td></tr>
            <?php endforeach; ?>
            <?php if (empty($intenciones_pago)): ?><tr><td colspan="5">No hay avisos de pago pendientes.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="chart-card">
    <h2>Resumen de morosidad (aging)</h2>
    <div class="kpi-grid">
        <?php foreach ($resumen_aging as $r): ?>
        <div class="kpi-card <?= $r->rango_atraso === 'MAS_30_DIAS' ? 'alerta' : '' ?>">
            <div class="kpi-label"><?= esc(str_replace('_', ' ', $r->rango_atraso)) ?></div>
            <div class="kpi-value">$<?= number_format($r->total_saldo, 2) ?></div>
            <p style="font-size:12px; color:var(--gris-texto);"><?= esc($r->total_notas) ?> nota(s)</p>
        </div>
        <?php endforeach; ?>
    </div>

    <table class="admin-table">
        <thead><tr><th>Cliente</th><th>Teléfono</th><th>Saldo</th><th>Fecha límite</th><th>Días de atraso</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($aging as $a): ?>
            <tr>
                <td><?= esc($a->nombre_completo) ?></td>
                <td><?= esc($a->telefono) ?></td>
                <td>$<?= number_format($a->saldo_pendiente, 2) ?></td>
                <td><?= esc($a->fecha_limite_pago) ?></td>
                <td><?= esc($a->dias_atraso) ?></td>
                <td><button class="btn btn-secondary btn-sm" onclick="abrirPromesa(<?= esc($a->id_cliente) ?>, <?= esc($a->id_venta) ?>)">Registrar promesa</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($aging)): ?><tr><td colspan="6">No hay cuentas atrasadas. 🎉</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para registrar el resultado de una gestión -->
<div class="modal-overlay" id="modalSeguimiento">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal('modalSeguimiento')">&times;</span>
        <h3>Registrar gestión de cobranza</h3>
        <form id="formSeguimiento" onsubmit="return false;">
            <div class="form-group"><label>Cliente</label><select name="id_cliente" class="admin-select" style="width:100%;" required><option value="">Selecciona un cliente</option><?php foreach ($clientes as $c): ?><option value="<?= esc($c->id_cliente) ?>"><?= esc($c->nombre_completo) ?> (<?= esc($c->telefono) ?>)</option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Resultado del contacto</label><select name="resultado" class="admin-select" style="width:100%;" required><option value="CONTACTADO">Contactado</option><option value="SIN_RESPUESTA">Sin respuesta</option><option value="PROMESA_PAGO">Promesa de pago</option><option value="SEGUIMIENTO">Otro seguimiento</option></select></div>
            <div class="form-group"><label>Notas de la gestión</label><textarea name="detalle" rows="3" placeholder="Resumen de la conversación o acuerdo"></textarea></div>
            <div class="form-group"><label>Próxima fecha de seguimiento (opcional)</label><input type="date" name="proxima_fecha"></div>
            <button type="submit" class="btn btn-primary btn-full">Guardar seguimiento</button><p id="mensajeSeguimiento" class="form-mensaje"></p>
        </form>
    </div>
</div>

<div class="chart-card">
    <h2>Promesas de pago pendientes</h2>
    <table class="admin-table">
        <thead><tr><th>Cliente</th><th>Monto prometido</th><th>Fecha prometida</th><th>Notas</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($promesas as $p): ?>
            <tr>
                <td><?= esc($p->nombre_completo) ?></td>
                <td>$<?= number_format($p->monto_prometido, 2) ?></td>
                <td><?= esc($p->fecha_prometida) ?></td>
                <td><?= esc($p->notas) ?></td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="actualizarPromesa(<?= esc($p->id_promesa) ?>, 'CUMPLIDA')">Cumplida</button>
                    <button class="btn btn-secondary btn-sm" onclick="actualizarPromesa(<?= esc($p->id_promesa) ?>, 'INCUMPLIDA')">Incumplida</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($promesas)): ?><tr><td colspan="5">Sin promesas de pago pendientes.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="chart-card">
    <h2>Centro de mensajes de cobranza</h2>
    <form id="formMensaje" onsubmit="return false;" style="margin-bottom:18px;">
        <div class="form-group">
            <label>Cliente</label>
            <select name="id_cliente" class="admin-select" style="width:100%;" required>
                <option value="">Selecciona un cliente</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= esc($c->id_cliente) ?>"><?= esc($c->nombre_completo) ?> (<?= esc($c->telefono) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Mensaje</label><textarea name="texto" rows="2" placeholder="Ej. Hola Juan, te recordamos tu saldo de $500 vence el viernes." required></textarea></div>
        <button type="submit" class="btn btn-secondary">Agregar a la cola de envío</button>
        <p id="mensajeEnvio" class="form-mensaje"></p>
    </form>

    <table class="admin-table">
        <thead><tr><th>Cliente</th><th>Tipo</th><th>Programado</th><th>Estatus</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($cola_mensajes as $m): ?>
            <tr>
                <td><?= esc($m->nombre_completo) ?></td>
                <td><?= esc($m->tipo) ?></td>
                <td><?= esc($m->fecha_programada) ?></td>
                <td><span class="badge badge-<?= strtolower($m->estatus) ?>"><?= esc($m->estatus) ?></span></td>
                <td><?php if ($m->estatus === 'PENDIENTE'): ?><button class="btn btn-secondary btn-sm" onclick="marcarEnviado(<?= esc($m->id_mensaje) ?>)">Marcar enviado</button><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($cola_mensajes)): ?><tr><td colspan="5">Sin mensajes en cola.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <p style="font-size:12px; color:var(--gris-texto); margin-top:10px;">
        El envío real por WhatsApp requiere conectar un proveedor (Meta WhatsApp Business API, Twilio, etc.) en <code>app/Commands/ProcesarMensajesCobranza.php</code>.
    </p>
</div>

<!-- Modal: promesa de pago -->
<div class="modal-overlay" id="modalPromesa">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal('modalPromesa')">&times;</span>
        <h3>Registrar promesa de pago</h3>
        <form id="formPromesa" onsubmit="return false;">
            <input type="hidden" id="pr_id_cliente" name="id_cliente">
            <input type="hidden" id="pr_id_venta" name="id_venta">
            <div class="form-group"><label>Monto prometido ($)</label><input type="number" step="0.01" name="monto_prometido" required></div>
            <div class="form-group"><label>Fecha prometida</label><input type="date" name="fecha_prometida" required></div>
            <div class="form-group"><label>Notas</label><input type="text" name="notas"></div>
            <button type="submit" class="btn btn-primary btn-full">Guardar promesa</button>
            <p id="mensajePromesa" class="form-mensaje"></p>
        </form>
    </div>
</div>

<script>
const BASE = '<?= base_url() ?>';

function abrirNuevoSeguimiento() {
    document.getElementById('formSeguimiento').reset();
    openModal('modalSeguimiento');
}
document.getElementById('formSeguimiento').addEventListener('submit', async function () {
    const data = Object.fromEntries(new FormData(document.getElementById('formSeguimiento')));
    const r = await adminPost(BASE + 'admin/cobranza/seguimiento/guardar', data);
    document.getElementById('mensajeSeguimiento').textContent = r.mensaje;
    document.getElementById('mensajeSeguimiento').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 900);
});

function abrirPromesa(idCliente, idVenta) {
    document.getElementById('formPromesa').reset();
    document.getElementById('pr_id_cliente').value = idCliente;
    document.getElementById('pr_id_venta').value = idVenta;
    openModal('modalPromesa');
}
document.getElementById('formPromesa').addEventListener('submit', async function () {
    const data = Object.fromEntries(new FormData(document.getElementById('formPromesa')));
    const r = await adminPost(BASE + 'admin/cobranza/promesa/guardar', data);
    document.getElementById('mensajePromesa').textContent = r.mensaje;
    document.getElementById('mensajePromesa').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 900);
});

async function actualizarPromesa(id, estatus) {
    await adminPost(BASE + 'admin/cobranza/promesa/' + id + '/actualizar', { estatus });
    window.location.reload();
}

document.getElementById('formMensaje').addEventListener('submit', async function () {
    const data = Object.fromEntries(new FormData(document.getElementById('formMensaje')));
    const r = await adminPost(BASE + 'admin/cobranza/mensaje/enviar', data);
    document.getElementById('mensajeEnvio').textContent = r.mensaje;
    document.getElementById('mensajeEnvio').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 900);
});

async function marcarEnviado(id) {
    await adminPost(BASE + 'admin/cobranza/mensaje/' + id + '/marcarEnviado', {});
    window.location.reload();
}
</script>

<?= $this->include('admin/layout_footer') ?>
