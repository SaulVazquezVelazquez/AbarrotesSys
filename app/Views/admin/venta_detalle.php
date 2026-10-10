<?= $this->include('admin/layout_header') ?>

<h1>Venta #<?= esc($venta->id_venta) ?></h1>
<p style="color:var(--gris-texto);">Cliente: <a class="row-link" href="<?= base_url('admin/clientes/' . $venta->id_cliente) ?>"><?= esc($venta->cliente) ?></a> (<?= esc($venta->telefono) ?>) · Atendió: <?= esc($venta->atendio ?? '-') ?></p>
<?php if (!empty($venta->pickup_at)): ?><div class="chart-card"><h2>Pedido para recoger</h2><p>Recogida programada: <strong><?= esc(date('d/m/Y H:i', strtotime($venta->pickup_at))) ?></strong></p><p>Estado de preparación: <strong><?= esc($venta->pedido_estatus) ?></strong></p><?php if (!empty($venta->notas_pedido)): ?><p>Notas: <?= esc($venta->notas_pedido) ?></p><?php endif; ?></div><?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Total</div><div class="kpi-value">$<?= number_format($venta->total, 2) ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Saldo pendiente</div><div class="kpi-value">$<?= number_format($venta->saldo_pendiente, 2) ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Fecha límite</div><div class="kpi-value" style="font-size:18px;"><?= esc($venta->fecha_limite_pago) ?></div></div>
    <div class="kpi-card"><div class="kpi-label">Estatus</div><div class="kpi-value" style="font-size:18px;"><span class="badge badge-<?= strtolower($venta->estatus) ?>"><?= esc($venta->estatus) ?></span></div></div>
</div>

<div class="chart-card">
    <h2>Artículos</h2>
    <table class="admin-table">
        <thead><tr><th>Artículo</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th></tr></thead>
        <tbody>
            <?php foreach ($items as $i): ?>
            <tr>
                <td><?= esc($i->nombre_producto ?? $i->descripcion_libre) ?></td>
                <td><?= esc($i->cantidad) ?></td>
                <td>$<?= number_format($i->precio_unitario, 2) ?></td>
                <td>$<?= number_format($i->subtotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="chart-card">
    <h2>Pagos aplicados a esta venta</h2>
    <table class="admin-table">
        <thead><tr><th>Fecha</th><th>Monto</th><th>Método</th></tr></thead>
        <tbody>
            <?php foreach ($pagos as $p): ?>
            <tr><td><?= esc($p->fecha_pago) ?></td><td>$<?= number_format($p->monto, 2) ?></td><td><?= esc($p->metodo_pago) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($pagos)): ?><tr><td colspan="3">Sin pagos aplicados todavía.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (in_array($venta->estatus, ['PENDIENTE','PARCIAL','VENCIDA']) && in_array($rol, ['DUENO','ADMIN'])): ?>
<div class="chart-card">
    <h2>Cancelar deuda</h2>
    <p style="color:var(--gris-texto); font-size:13px;">Usa esto solo en casos excepcionales (condonación, error de captura). Queda registrado en auditoría.</p>
    <form id="formCancelar" onsubmit="return false;">
        <div class="form-group"><label>Motivo</label><textarea name="motivo" rows="2" required></textarea></div>
        <button type="submit" class="btn btn-danger">Cancelar esta deuda</button>
        <p id="mensajeCancelar" class="form-mensaje"></p>
    </form>
</div>
<script>
const BASE = '<?= base_url() ?>';
document.getElementById('formCancelar').addEventListener('submit', async function () {
    const data = Object.fromEntries(new FormData(document.getElementById('formCancelar')));
    const r = await adminPost(BASE + 'admin/ventas/<?= esc($venta->id_venta) ?>/cancelar', data);
    document.getElementById('mensajeCancelar').textContent = r.mensaje;
    document.getElementById('mensajeCancelar').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 900);
});
</script>
<?php endif; ?>

<?= $this->include('admin/layout_footer') ?>
