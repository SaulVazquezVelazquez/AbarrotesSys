<?= $this->include('admin/layout_header') ?>

<?php
function dotSemaforo($semaforo) {
    return '<span class="semaforo-dot dot-' . strtolower($semaforo ?: 'verde') . '"></span>';
}
?>

<div class="toolbar">
    <h1 style="margin-bottom:0;">Clientes</h1>
    <div class="toolbar-left">
        <form method="get" class="client-filters">
            <input type="search" name="q" class="admin-input" placeholder="Nombre o teléfono" value="<?= esc($_GET['q'] ?? '') ?>">
            <select name="semaforo" class="admin-select"><option value="">Todos los semáforos</option><?php foreach (['VERDE','AMARILLO','ROJO'] as $semaforo): ?><option value="<?= $semaforo ?>" <?= ($_GET['semaforo'] ?? '') === $semaforo ? 'selected' : '' ?>><?= ucfirst(strtolower($semaforo)) ?></option><?php endforeach; ?></select>
            <select name="estatus" class="admin-select"><option value="">Todos los estados</option><option value="ACTIVO" <?= ($_GET['estatus'] ?? '') === 'ACTIVO' ? 'selected' : '' ?>>Activo</option><option value="BLOQUEADO" <?= ($_GET['estatus'] ?? '') === 'BLOQUEADO' ? 'selected' : '' ?>>Bloqueado</option></select>
            <select name="deuda" class="admin-select"><option value="">Con o sin saldo</option><option value="si" <?= ($_GET['deuda'] ?? '') === 'si' ? 'selected' : '' ?>>Con deuda</option><option value="no" <?= ($_GET['deuda'] ?? '') === 'no' ? 'selected' : '' ?>>Sin deuda</option></select>
            <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </form>
        <button class="btn btn-primary btn-sm" onclick="abrirNuevoCliente()"><i class="fa-solid fa-plus"></i> Nuevo cliente</button>
    </div>
</div>

<table class="admin-table">
    <thead><tr><th>Semáforo</th><th>Cliente</th><th>Teléfono</th><th>Saldo actual</th><th>Límite</th><th>Disponible</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($clientes as $c): ?>
        <?php $iniciales = mb_strtoupper(mb_substr(trim($c->nombre_completo), 0, 1) . (mb_strpos(trim($c->nombre_completo), ' ') !== false ? mb_substr(trim($c->nombre_completo), mb_strpos(trim($c->nombre_completo), ' ') + 1, 1) : '')); ?>
        <tr>
            <td><?= dotSemaforo($c->semaforo) ?><?= esc($c->semaforo) ?></td>
            <td><div class="client-table-identity"><span class="client-avatar client-avatar-sm"><?php if (!empty($c->foto)): ?><img src="<?= base_url('admin/clientes/' . $c->id_cliente . '/foto') ?>" alt="Foto de <?= esc($c->nombre_completo) ?>"><?php else: ?><?= esc($iniciales) ?><?php endif; ?></span><span><?= esc($c->nombre_completo) ?> <?= $c->estatus === 'BLOQUEADO' ? '<span class="badge badge-bloqueado">Bloqueado</span>' : '' ?></span></div></td>
            <td><?= esc($c->telefono) ?></td>
            <td>$<?= number_format($c->saldo_actual, 2) ?></td>
            <td>$<?= number_format($c->limite_credito, 2) ?></td>
            <td>$<?= number_format($c->credito_disponible, 2) ?></td>
            <td><a class="row-link" href="<?= base_url('admin/clientes/' . $c->id_cliente) ?>">Ver ficha</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($clientes)): ?>
            <tr><td colspan="7">No se encontraron clientes con esos filtros.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Modal: nuevo cliente -->
<div class="modal-overlay" id="modalCliente">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal('modalCliente')">&times;</span>
        <h3>Nuevo cliente</h3>
        <form id="formCliente" onsubmit="return false;" enctype="multipart/form-data">
            <div class="client-photo-editor"><div class="client-photo-preview" id="previewFotoNueva"><span class="client-photo-placeholder"><i class="fa-solid fa-user"></i></span></div><div><strong>Foto del cliente</strong><p>Una imagen clara ayuda a identificarlo en el panel.</p><label class="btn btn-secondary btn-sm client-photo-select" for="fotoClienteNuevo"><i class="fa-solid fa-camera"></i> Elegir foto</label><input type="file" id="fotoClienteNuevo" name="foto_cliente" accept="image/jpeg,image/png,image/gif,image/webp" hidden></div></div>
            <div class="form-group"><label>Nombre completo</label><input type="text" name="nombre_completo" required></div>
            <div class="form-group"><label>Teléfono</label><input type="text" name="telefono" required></div>
            <div class="form-group"><label>Dirección</label><input type="text" name="direccion"></div>
            <div class="form-group"><label>Límite de crédito ($)</label><input type="number" step="0.01" name="limite_credito" value="1000"></div>
            <div class="form-group">
                <label>Plazo de pago</label>
                <select name="plazo_dias" class="admin-select" style="width:100%;">
                    <option value="7">Semanal (7 días)</option>
                    <option value="15" selected>Quincenal (15 días)</option>
                    <option value="30">Mensual (30 días)</option>
                </select>
            </div>
            <div class="form-group"><label>PIN de 4 dígitos (para el portal y confirmar ventas)</label><input type="text" name="pin" maxlength="4" pattern="[0-9]{4}" placeholder="Ej. 1234"></div>
            <div class="form-group"><label>Notas</label><textarea name="notas" rows="2"></textarea></div>
            <button type="submit" class="btn btn-primary btn-full">Guardar cliente</button>
            <p id="mensajeCliente" class="form-mensaje"></p>
        </form>
    </div>
</div>

<script>
const BASE = '<?= base_url() ?>';
function abrirNuevoCliente() {
    document.getElementById('formCliente').reset();
    document.getElementById('previewFotoNueva').innerHTML = '<span class="client-photo-placeholder"><i class="fa-solid fa-user"></i></span>';
    openModal('modalCliente');
}
document.getElementById('fotoClienteNuevo').addEventListener('change', function () {
    mostrarVistaPrevia(this, 'previewFotoNueva');
});
document.getElementById('formCliente').addEventListener('submit', async function () {
    const resultado = await enviarFormularioCliente(document.getElementById('formCliente'));
    const msg = document.getElementById('mensajeCliente');
    msg.textContent = resultado.mensaje;
    msg.className = 'form-mensaje ' + (resultado.respuesta ? 'ok' : 'error');
    if (resultado.respuesta) setTimeout(() => window.location.reload(), 900);
});
</script>

<?= $this->include('admin/layout_footer') ?>
