<?= $this->include('admin/layout_header') ?>

<h1>Auditoría</h1>
<p style="color:var(--gris-texto); font-size:13.5px;">Registro de quién otorgó crédito, quién recibió un abono, y modificaciones sensibles (límites, bloqueos, cancelaciones).</p>

<table class="admin-table">
    <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>Detalle</th></tr></thead>
    <tbody>
        <?php foreach ($registros as $r): ?>
        <tr>
            <td><?= esc($r->fecha_creacion) ?></td>
            <td><?= esc($r->usuario ?? '-') ?></td>
            <td><?= esc($r->accion) ?></td>
            <td><?= esc($r->entidad) ?> #<?= esc($r->id_entidad) ?></td>
            <td><?= esc($r->detalle) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($registros)): ?><tr><td colspan="5">Sin registros todavía.</td></tr><?php endif; ?>
    </tbody>
</table>

<?= $this->include('admin/layout_footer') ?>
