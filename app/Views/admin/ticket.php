<?php
$fecha = $venta->fecha_venta ?? date('Y-m-d H:i:s');
$cliente = $venta->cliente ?? 'Venta mostrador';
$atendio = $venta->atendio ?? '—';
$total = (float) ($venta->total ?? 0);
$esCredito = ($tipo ?? '') === 'CREDITO';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket #<?= esc($venta->id_venta) ?> | Tienda y Abarrotes</title>
    <style>
        *{box-sizing:border-box}body{font:13px Arial,sans-serif;color:#202124;background:#f2f4f7;margin:0;padding:22px}.ticket{width:100%;max-width:360px;margin:0 auto;background:#fff;padding:22px 20px;box-shadow:0 4px 22px #17203318}.center{text-align:center}.brand{font-size:18px;font-weight:800;letter-spacing:.04em;margin:0 0 5px}.muted{color:#687386;font-size:11px;line-height:1.55}.rule{border:0;border-top:1px dashed #aab1bb;margin:14px 0}.meta{display:grid;grid-template-columns:1fr auto;gap:5px;font-size:12px}.items{width:100%;border-collapse:collapse;margin:10px 0}.items th{text-align:left;color:#687386;font-size:10px;text-transform:uppercase;padding:5px 0;border-bottom:1px solid #dce1e8}.items td{padding:8px 0;border-bottom:1px dashed #dce1e8;vertical-align:top}.right{text-align:right;white-space:nowrap}.total{display:flex;justify-content:space-between;font-size:17px;font-weight:800;margin:12px 0}.actions{display:flex;gap:8px;justify-content:center;margin:16px auto;max-width:360px}.actions button{border:0;background:#285de8;color:#fff;border-radius:8px;padding:10px 15px;cursor:pointer;font-weight:700}.actions a{padding:10px 15px;color:#26354b}@media print{body{background:#fff;padding:0}.ticket{max-width:none;box-shadow:none;padding:0}.actions{display:none}}
    </style>
</head>
<body>
    <main class="ticket">
        <header class="center"><h1 class="brand">TIENDA Y ABARROTES</h1><div class="muted">Ticket de <?= $esCredito ? 'compra a crédito' : 'compra' ?></div></header>
        <hr class="rule">
        <div class="meta"><strong>Folio</strong><span>#<?= esc($venta->id_venta) ?></span><strong>Fecha</strong><span><?= esc(date('d/m/Y H:i', strtotime($fecha))) ?></span><strong>Cliente</strong><span><?= esc($cliente) ?></span><strong>Atendió</strong><span><?= esc($atendio) ?></span></div>
        <table class="items"><thead><tr><th>Artículo</th><th class="right">Importe</th></tr></thead><tbody>
            <?php foreach ($items as $item): ?>
                <tr><td><?= esc($item->nombre_producto ?? $item->descripcion_libre ?? 'Producto') ?><br><span class="muted"><?= esc($item->cantidad) ?> × $<?= number_format((float) $item->precio_unitario, 2) ?></span></td><td class="right">$<?= number_format((float) $item->subtotal, 2) ?></td></tr>
            <?php endforeach; ?>
        </tbody></table>
        <div class="total"><span>Total</span><span>$<?= number_format($total, 2) ?></span></div>
        <?php if ($esCredito): ?>
            <div class="meta"><strong>Saldo pendiente</strong><span>$<?= number_format((float) ($venta->saldo_pendiente ?? $total), 2) ?></span><?php if (!empty($venta->fecha_limite_pago)): ?><strong>Fecha límite</strong><span><?= esc($venta->fecha_limite_pago) ?></span><?php endif; ?></div>
        <?php else: ?>
            <?php foreach ($pagos as $pago): ?><div class="meta"><span>Pago · <?= esc($pago->metodo) ?></span><span>$<?= number_format((float) $pago->monto, 2) ?></span></div><?php endforeach; ?>
            <?php if ((float) ($venta->cambio ?? 0) > 0): ?><div class="meta"><span>Cambio</span><span>$<?= number_format((float) $venta->cambio, 2) ?></span></div><?php endif; ?>
        <?php endif; ?>
        <hr class="rule"><p class="center muted">Gracias por su compra.</p>
    </main>
    <div class="actions"><button type="button" onclick="window.print()">Imprimir ticket</button><a href="<?= base_url('admin/ventas') ?>">Volver a ventas</a></div>
    <script>window.addEventListener('load',()=>window.print());</script>
</body>
</html>
