<?= $this->include('admin/layout_header') ?>

<div class="toolbar">
    <h1 style="margin-bottom:0;">Pagos / Abonos</h1>
    <button class="btn btn-primary btn-sm" onclick="openModal('modalPago')"><i class="fa-solid fa-plus"></i> Registrar abono</button>
</div>

<table class="admin-table">
    <thead><tr><th>Fecha</th><th>Cliente</th><th>Monto</th><th>Método</th><th>Referencia</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($pagos as $p): ?>
        <tr>
            <td><?= esc($p->fecha_pago) ?></td>
            <td><?= esc($p->cliente) ?></td>
            <td>$<?= number_format($p->monto, 2) ?></td>
            <td><?= esc($p->metodo_pago) ?></td>
            <td><?= esc($p->referencia_pago) ?></td>
            <td><?php if ($p->ruta_comprobante): ?><a class="row-link" href="<?= base_url('admin/pagos/' . $p->id_pago . '/comprobante') ?>">Comprobante</a><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($pagos)): ?><tr><td colspan="6">Sin pagos registrados.</td></tr><?php endif; ?>
    </tbody>
</table>

<div class="modal-overlay" id="modalPago">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal('modalPago')">&times;</span>
        <h3>Registrar abono</h3>
        <form id="formPago" onsubmit="return false;">
            <div class="form-group">
                <label>Cliente</label>
                <select name="id_cliente" class="admin-select" style="width:100%;" required>
                    <option value="">Selecciona un cliente</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= esc($c->id_cliente) ?>" <?= (string) ($cliente_preseleccionado ?? '') === (string) $c->id_cliente ? 'selected' : '' ?>>
                            <?= esc($c->nombre_completo) ?> — saldo actual: $<?= number_format($c->saldo_actual, 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Monto ($)</label><input type="number" step="0.01" name="monto" value="<?= esc($monto_preseleccionado ?? '') ?>" required></div>
            <div class="form-group">
                <label>Método de pago</label>
                <select name="metodo_pago" class="admin-select" style="width:100%;">
                    <option value="EFECTIVO">Efectivo</option>
                    <option value="TRANSFERENCIA">Transferencia</option>
                    <option value="SPEI">SPEI</option>
                    <option value="QR">QR</option>
                    <option value="MERCADO_PAGO">Mercado Pago</option>
                </select>
            </div>
            <div class="form-group"><label>Referencia (opcional)</label><input type="text" name="referencia_pago"></div>
            <p style="font-size:12px; color:var(--gris-texto);">El abono se aplica automáticamente primero a la nota más antigua pendiente del cliente; si sobra, se sigue aplicando a las siguientes.</p>
            <button type="submit" class="btn btn-primary btn-full">Registrar abono</button>
            <p id="mensajePago" class="form-mensaje"></p>
        </form>
    </div>
</div>

<script>
const BASE = '<?= base_url() ?>';
document.getElementById('formPago').addEventListener('submit', async function () {
    const data = Object.fromEntries(new FormData(document.getElementById('formPago')));
    const r = await adminPost(BASE + 'admin/pagos/guardar', data);
    document.getElementById('mensajePago').textContent = r.mensaje;
    document.getElementById('mensajePago').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 900);
});
</script>

<?= $this->include('admin/layout_footer') ?>
