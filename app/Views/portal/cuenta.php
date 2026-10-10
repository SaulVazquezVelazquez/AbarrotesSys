<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Mi Cuenta | Tienda y Abarrotes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url() ?>assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>
    <div class="portal-body">
        <div style="display:flex; justify-content:space-between; align-items:center; margin:16px 0;">
            <h2 style="margin:0;">Hola, <?= esc(explode(' ', $cliente->nombre_completo)[0]) ?></h2>
            <a href="<?= base_url('portal/logout') ?>" class="btn btn-secondary btn-sm">Salir</a>
        </div>

        <div class="portal-card saldo-hero">
            <p style="color:var(--gris-texto); margin-bottom:4px;">Saldo de tu cuenta</p>
            <div class="monto">$<?= number_format($cliente->saldo_actual, 2) ?></div>
            <p>
                <?= (float) $cliente->saldo_actual <= 0 ? 'No tienes saldo pendiente' : 'Tienes saldo pendiente en tu cuenta' ?>
            </p>
            <p style="font-size:13px; color:var(--gris-texto);">Crédito disponible: $<?= number_format($cliente->credito_disponible, 2) ?> de $<?= number_format($cliente->limite_credito, 2) ?></p>
            <?php if ($cliente->proxima_fecha_limite): ?>
                <p style="font-size:13px; color:var(--gris-texto);">Próxima fecha de pago: <?= esc($cliente->proxima_fecha_limite) ?></p>
            <?php endif; ?>
        </div>

        <div class="portal-card">
            <h3>Historial de compras</h3>
            <?php foreach ($compras as $c): ?>
                <div style="border-bottom:1px solid rgba(255,255,255,.06); padding:8px 0; font-size:13.5px;">
                    <strong><?= $c->tipo === 'MOSTRADOR' ? 'Compra de mostrador' : 'Pedido a crédito' ?> #<?= esc($c->id_venta) ?> · <?= esc($c->fecha_venta) ?></strong> — $<?= number_format($c->total, 2) ?>
                    <span class="badge badge-<?= strtolower($c->estatus) ?>"><?= esc($c->estatus) ?></span>
                    <?php if ((float) $c->saldo_pendiente > 0): ?><span>Saldo: $<?= number_format($c->saldo_pendiente, 2) ?></span><?php endif; ?>
                    <?php if (!empty($c->pickup_at)): ?><div style="margin-top:5px;color:var(--gris-texto);font-size:12px;">Recogida: <?= esc(date('d/m/Y H:i', strtotime($c->pickup_at))) ?> · <?= esc($c->pedido_estatus) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (empty($compras)): ?><p style="color:var(--gris-texto);">Sin compras registradas.</p><?php endif; ?>
            <a href="<?= base_url() ?>" class="btn btn-primary btn-sm" style="margin-top:12px;">Hacer un pedido para recoger</a>
        </div>

        <div class="portal-card">
            <h3>Historial de pagos</h3>
            <?php foreach ($pagos as $p): ?>
                <div style="border-bottom:1px solid rgba(255,255,255,.06); padding:8px 0; font-size:13.5px; display:flex; justify-content:space-between;">
                    <span><?= esc($p->fecha_pago) ?> — $<?= number_format($p->monto, 2) ?> (<?= esc($p->metodo_pago) ?>)</span>
                    <?php if ($p->ruta_comprobante): ?><a class="row-link" href="<?= base_url('portal/comprobante/' . $p->id_pago) ?>">Comprobante</a><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (empty($pagos)): ?><p style="color:var(--gris-texto);">Sin pagos registrados.</p><?php endif; ?>
        </div>
    </div>

</body>
</html>
