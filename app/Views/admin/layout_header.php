<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tienda y Abarrotes | Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url() ?>assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>
<header class="admin-header">
    <span class="logo">TIENDA <span>Y ABARROTES</span></span>
    <div class="admin-header-right">
        <span><?= esc($nombre_completo ?? '') ?> · <?= esc($rol ?? '') ?></span>
        <a href="<?= base_url() ?>admin/logout" class="btn btn-secondary btn-sm">Cerrar sesión</a>
    </div>
</header>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <nav>
            <a href="<?= base_url() ?>admin/dashboard"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
            <a href="<?= base_url() ?>admin/pos"><i class="fa-solid fa-cash-register"></i> Punto de venta</a>
            <?php
                $db = db_connect();
                $conteoPendientes = $db->tableExists('solicitudes_compra')
                    ? (int) ($db->query("SELECT COUNT(*) AS total FROM solicitudes_compra WHERE estatus = 'PENDIENTE'")->getRow()->total ?? 0)
                    : 0;
            ?>
            <a href="<?= base_url() ?>admin/solicitudes" style="position:relative;">
                <i class="fa-solid fa-cart-shopping"></i> Solicitudes
                <?php if ($conteoPendientes > 0): ?>
                    <span style="background:var(--cta); color:#111; border-radius:20px; font-size:10.5px; font-weight:700; padding:1px 7px; margin-left:4px;"><?= esc($conteoPendientes) ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= base_url() ?>admin/clientes"><i class="fa-solid fa-users"></i> Clientes</a>
            <a href="<?= base_url() ?>admin/productos"><i class="fa-solid fa-box"></i> Productos</a>
            <a href="<?= base_url() ?>admin/ventas"><i class="fa-solid fa-cash-register"></i> Ventas (POS)</a>
            <a href="<?= base_url() ?>admin/pagos"><i class="fa-solid fa-hand-holding-dollar"></i> Pagos / Abonos</a>
            <a href="<?= base_url() ?>admin/cobranza"><i class="fa-solid fa-bell"></i> Cobranza</a>
            <a href="<?= base_url() ?>admin/reportes"><i class="fa-solid fa-chart-pie"></i> Reportes</a>
            <?php if (($rol ?? '') === 'DUENO'): ?>
                <a href="<?= base_url() ?>admin/usuarios"><i class="fa-solid fa-user-shield"></i> Usuarios</a>
            <?php endif; ?>
            <?php if (in_array($rol ?? '', ['DUENO','ADMIN'])): ?>
                <a href="<?= base_url() ?>admin/auditoria"><i class="fa-solid fa-clipboard-list"></i> Auditoría</a>
            <?php endif; ?>
        </nav>
    </aside>
    <main class="admin-main">
