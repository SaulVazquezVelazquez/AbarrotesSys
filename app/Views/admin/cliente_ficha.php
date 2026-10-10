<?= $this->include('admin/layout_header') ?>

<?php function dotSemaforo($s) { return '<span class="semaforo-dot dot-' . strtolower($s ?: 'verde') . '"></span>'; } ?>

<?php $inicialesCliente = mb_strtoupper(mb_substr(trim($cliente->nombre_completo), 0, 1) . (mb_strpos(trim($cliente->nombre_completo), ' ') !== false ? mb_substr(trim($cliente->nombre_completo), mb_strpos(trim($cliente->nombre_completo), ' ') + 1, 1) : '')); ?>
<section class="client-profile-banner">
    <div class="client-avatar client-avatar-lg"><?php if (!empty($cliente->foto)): ?><img src="<?= base_url('admin/clientes/' . $cliente->id_cliente . '/foto') ?>" alt="Foto de <?= esc($cliente->nombre_completo) ?>"><?php else: ?><?= esc($inicialesCliente) ?><?php endif; ?></div>
    <div class="client-profile-details"><span class="eyebrow">PERFIL DEL CLIENTE</span><h1><?= esc($cliente->nombre_completo) ?></h1><p><i class="fa-solid fa-phone"></i> <?= esc($cliente->telefono) ?> <span class="client-profile-divider">·</span> <?= esc($cliente->estatus) ?></p></div>
    <div class="toolbar-left">
        <?php if ($cliente->estatus === 'BLOQUEADO'): ?>
            <button class="btn btn-secondary btn-sm" onclick="desbloquear()">Desbloquear cliente</button>
        <?php else: ?>
            <button class="btn btn-danger btn-sm" onclick="bloquear()">Bloquear cliente</button>
        <?php endif; ?>
        <button class="btn btn-secondary btn-sm" onclick="abrirEditar()">Editar datos</button>
    </div>
</section>

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Saldo actual</div><div class="kpi-value">$<?= number_format($cliente->saldo_actual, 2) ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Límite de crédito</div><div class="kpi-value">$<?= number_format($cliente->limite_credito, 2) ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Disponible</div><div class="kpi-value">$<?= number_format($cliente->credito_disponible, 2) ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Semáforo</div><div class="kpi-value"><?= dotSemaforo($cliente->semaforo) ?><?= esc($cliente->semaforo) ?></div></div>
</div>

<div class="chart-card client-timeline-card">
    <div class="timeline-heading"><div><span class="eyebrow">HISTORIAL</span><h2>Actividad del cliente</h2></div><span class="timeline-count"><?= count($actividad) ?> eventos</span></div>
    <?php if (!empty($actividad)): ?>
        <div class="client-timeline">
            <?php foreach ($actividad as $evento): ?>
                <?php $iconoActividad = ['VENTA'=>'fa-bag-shopping','PAGO'=>'fa-circle-check','PROMESA'=>'fa-calendar-check','GESTION'=>'fa-headset','SOLICITUD'=>'fa-inbox'][$evento->tipo] ?? 'fa-circle'; ?>
                <article class="timeline-event">
                    <span class="timeline-icon timeline-<?= strtolower($evento->tipo) ?>"><i class="fa-solid <?= $iconoActividad ?>"></i></span>
                    <div class="timeline-copy"><div class="timeline-title-row"><strong><?= esc($evento->titulo) ?></strong><span class="badge badge-<?= strtolower($evento->estatus) ?>"><?= esc($evento->estatus) ?></span></div><p><?= esc($evento->detalle) ?></p><time><?= esc(date('d/m/Y · H:i', strtotime($evento->fecha))) ?></time></div>
                    <?php if ($evento->monto !== null): ?><strong class="timeline-amount">$<?= number_format((float) $evento->monto, 2) ?></strong><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?><p class="empty-state">Aún no hay actividad registrada para este cliente.</p><?php endif; ?>
</div>

<?php if ($scoring && $scoring->porcentaje_puntualidad !== null): ?>
<div class="chart-card">
    <h2>Comportamiento de pago</h2>
    <p>Puntualidad histórica: <strong><?= esc($scoring->porcentaje_puntualidad) ?>%</strong> (<?= esc($scoring->ventas_pagadas) ?> pagadas a tiempo, <?= esc($scoring->ventas_vencidas) ?> vencidas)</p>
</div>
<?php endif; ?>

<div class="chart-card">
    <h2>Contactos de referencia / avales</h2>
    <ul>
        <?php foreach ($contactos as $c): ?>
            <li><?= esc($c->nombre) ?> — <?= esc($c->telefono) ?> (<?= esc($c->relacion) ?>)</li>
        <?php endforeach; ?>
        <?php if (empty($contactos)): ?><li style="list-style:none; color:var(--gris-texto);">Sin contactos registrados.</li><?php endif; ?>
    </ul>
    <button class="btn btn-secondary btn-sm" onclick="openModal('modalContacto')">Agregar contacto</button>
</div>

<div class="chart-card">
    <h2>Historial de compras</h2>
    <table class="admin-table">
        <thead><tr><th>Fecha</th><th>Total</th><th>Saldo pendiente</th><th>Fecha límite</th><th>Estatus</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($compras as $v): ?>
            <tr>
                <td><?= esc($v->fecha_venta) ?></td>
                <td>$<?= number_format($v->total, 2) ?></td>
                <td>$<?= number_format($v->saldo_pendiente, 2) ?></td>
                <td><?= !empty($v->fecha_limite_pago) ? esc($v->fecha_limite_pago) : '—' ?></td>
                <td><span class="badge badge-<?= strtolower($v->estatus) ?>"><?= esc($v->estatus) ?></span></td>
                <td><?php if (($v->tipo ?? 'CREDITO') === 'CREDITO'): ?><a class="row-link" href="<?= base_url('admin/ventas/' . $v->id_venta) ?>">Ver detalle</a><?php else: ?>Mostrador<?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($compras)): ?><tr><td colspan="6">Sin compras registradas.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="chart-card">
    <h2>Historial de pagos / abonos</h2>
    <table class="admin-table">
        <thead><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Referencia</th></tr></thead>
        <tbody>
            <?php foreach ($pagos as $p): ?>
            <tr>
                <td><?= esc($p->fecha_pago) ?></td>
                <td>$<?= number_format($p->monto, 2) ?></td>
                <td><?= esc($p->metodo_pago) ?></td>
                <td><?= esc($p->referencia_pago) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($pagos)): ?><tr><td colspan="4">Sin pagos registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: editar cliente -->
<div class="modal-overlay" id="modalEditar">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal('modalEditar')">&times;</span>
        <h3>Editar cliente</h3>
        <form id="formEditar" onsubmit="return false;" enctype="multipart/form-data">
            <input type="hidden" name="id_cliente" value="<?= esc($cliente->id_cliente) ?>">
            <div class="client-photo-editor"><div class="client-photo-preview" id="previewFotoEditar"><?php if (!empty($cliente->foto)): ?><img src="<?= base_url('admin/clientes/' . $cliente->id_cliente . '/foto') ?>" alt="Foto actual del cliente"><?php else: ?><span class="client-photo-placeholder"><i class="fa-solid fa-user"></i></span><?php endif; ?></div><div><strong>Foto del cliente</strong><p>Selecciona una nueva foto para actualizarla.</p><label class="btn btn-secondary btn-sm client-photo-select" for="fotoClienteEditar"><i class="fa-solid fa-camera"></i> Cambiar foto</label><input type="file" id="fotoClienteEditar" name="foto_cliente" accept="image/jpeg,image/png,image/gif,image/webp" hidden></div></div>
            <div class="form-group"><label>Nombre completo</label><input type="text" name="nombre_completo" value="<?= esc($cliente->nombre_completo) ?>" required></div>
            <div class="form-group"><label>Teléfono</label><input type="text" name="telefono" value="<?= esc($cliente->telefono) ?>" required></div>
            <div class="form-group"><label>Límite de crédito ($)</label><input type="number" step="0.01" name="limite_credito" value="<?= esc($cliente->limite_credito) ?>"></div>
            <div class="form-group"><label>Nuevo PIN (dejar vacío para no cambiar)</label><input type="text" name="pin" maxlength="4" pattern="[0-9]{4}"></div>
            <button type="submit" class="btn btn-primary btn-full">Guardar cambios</button>
            <p id="mensajeEditar" class="form-mensaje"></p>
        </form>
    </div>
</div>

<!-- Modal: bloquear -->
<div class="modal-overlay" id="modalBloquear">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal('modalBloquear')">&times;</span>
        <h3>Bloquear cliente</h3>
        <form id="formBloquear" onsubmit="return false;">
            <div class="form-group"><label>Motivo</label><textarea name="motivo" rows="2" required></textarea></div>
            <button type="submit" class="btn btn-danger btn-full">Confirmar bloqueo</button>
            <p id="mensajeBloquear" class="form-mensaje"></p>
        </form>
    </div>
</div>

<!-- Modal: agregar contacto -->
<div class="modal-overlay" id="modalContacto">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal('modalContacto')">&times;</span>
        <h3>Agregar contacto de referencia</h3>
        <form id="formContacto" onsubmit="return false;">
            <input type="hidden" name="id_cliente" value="<?= esc($cliente->id_cliente) ?>">
            <div class="form-group"><label>Nombre</label><input type="text" name="nombre" required></div>
            <div class="form-group"><label>Teléfono</label><input type="text" name="telefono"></div>
            <div class="form-group"><label>Relación</label><input type="text" name="relacion" placeholder="Ej. esposo, hermana, vecino"></div>
            <button type="submit" class="btn btn-primary btn-full">Guardar contacto</button>
            <p id="mensajeContacto" class="form-mensaje"></p>
        </form>
    </div>
</div>

<script>
const BASE = '<?= base_url() ?>';
const ID_CLIENTE = <?= esc($cliente->id_cliente) ?>;

function abrirEditar() { openModal('modalEditar'); }
function bloquear() { openModal('modalBloquear'); }
document.getElementById('fotoClienteEditar').addEventListener('change', function () {
    mostrarVistaPrevia(this, 'previewFotoEditar');
});
function desbloquear() {
    if (!confirm('¿Desbloquear a este cliente y permitirle volver a comprar a crédito?')) return;
    adminPost(BASE + 'admin/clientes/' + ID_CLIENTE + '/desbloquear', {}).then(() => window.location.reload());
}

document.getElementById('formEditar').addEventListener('submit', async function () {
    const r = await enviarFormularioCliente(document.getElementById('formEditar'));
    document.getElementById('mensajeEditar').textContent = r.mensaje;
    document.getElementById('mensajeEditar').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 900);
});

document.getElementById('formBloquear').addEventListener('submit', async function () {
    const data = Object.fromEntries(new FormData(document.getElementById('formBloquear')));
    const r = await adminPost(BASE + 'admin/clientes/' + ID_CLIENTE + '/bloquear', data);
    document.getElementById('mensajeBloquear').textContent = r.mensaje;
    document.getElementById('mensajeBloquear').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 900);
});

document.getElementById('formContacto').addEventListener('submit', async function () {
    const data = Object.fromEntries(new FormData(document.getElementById('formContacto')));
    const r = await adminPost(BASE + 'admin/clientes/contacto/guardar', data);
    document.getElementById('mensajeContacto').textContent = r.mensaje;
    document.getElementById('mensajeContacto').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 900);
});
</script>

<?= $this->include('admin/layout_footer') ?>
