<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 15px; text-align:center; margin-bottom:2px; }
        .sub { text-align:center; color:#555; margin-bottom:14px; }
        table { width:100%; border-collapse:collapse; }
        td { padding:4px 0; }
        td.label { font-weight:bold; width:110px; }
        .total { border-top:1px dashed #999; margin-top:10px; padding-top:8px; text-align:center; font-size:16px; font-weight:bold; }
        .footer { text-align:center; margin-top:16px; font-size:10px; color:#777; }
    </style>
</head>
<body>
    <h1>Comprobante de Pago</h1>
    <p class="sub">Recibo #<?= esc($idPago) ?></p>

    <table>
        <tr><td class="label">Cliente:</td><td><?= esc($cliente->nombre_completo) ?></td></tr>
        <tr><td class="label">Teléfono:</td><td><?= esc($cliente->telefono) ?></td></tr>
        <tr><td class="label">Fecha:</td><td><?= esc($fecha) ?></td></tr>
        <tr><td class="label">Método de pago:</td><td><?= esc($metodoPago) ?></td></tr>
        <?php if ($referencia): ?><tr><td class="label">Referencia:</td><td><?= esc($referencia) ?></td></tr><?php endif; ?>
    </table>

    <div class="total">$<?= number_format($monto, 2) ?></div>

    <p class="footer">Gracias por tu pago. Conserva este comprobante.</p>
</body>
</html>
