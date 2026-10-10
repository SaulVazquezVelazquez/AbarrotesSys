<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Catálogo | Tienda y Abarrotes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url() ?>assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

<header class="catalogo-header">
    <span class="logo">TIENDA <span>Y ABARROTES</span></span>
    <div style="display:flex; gap:10px; align-items:center;">
        <?php if (!empty($cliente)): ?><a href="<?= base_url('portal/cuenta') ?>" class="btn btn-secondary btn-sm" aria-label="Mi cuenta"><i class="fa-solid fa-user"></i> <span class="header-action-label">Hola, <?= esc(explode(' ', $cliente->nombre_completo)[0]) ?></span></a><?php else: ?><a href="<?= base_url('portal') ?>" class="btn btn-secondary btn-sm" aria-label="Iniciar sesión"><i class="fa-solid fa-user"></i> <span class="header-action-label">Ya soy cliente</span></a><?php endif; ?>
        <?php if (!empty($cliente)): ?><a class="btn btn-secondary btn-sm" href="<?= base_url('portal/cuenta') ?>" aria-label="Mis pedidos"><i class="fa-solid fa-box"></i> <span class="header-action-label">Mis pedidos</span><span class="header-action-short">Pedidos</span></a><?php else: ?><a class="btn btn-secondary btn-sm" href="<?= base_url('portal') ?>" aria-label="Entrar para ver pedidos"><i class="fa-solid fa-box"></i> <span class="header-action-label">Mis pedidos</span><span class="header-action-short">Pedidos</span></a><?php endif; ?>
        <button class="btn btn-primary btn-sm" onclick="abrirCarrito()">
            <i class="fa-solid fa-cart-shopping"></i> <span class="header-cart-label">Carrito</span> (<span id="contadorCarrito">0</span>)
        </button>
    </div>
</header>

<main class="catalogo-main">
    <section class="catalogo-hero">
        <div class="catalogo-hero-copy">
            <span class="eyebrow"><i class="fa-solid fa-bag-shopping"></i> Compra en línea, recoge en tienda</span>
            <h1>Tu compra lista<br><span>cuando pases por ella.</span></h1>
            <p>Arma tu carrito, elige tu horario de recogida y paga con el crédito disponible de tu cuenta de cliente.</p>
            <a class="btn btn-cta" href="#productos"><i class="fa-solid fa-bag-shopping"></i> Explorar productos</a>
            <div class="catalogo-beneficios"><span><i class="fa-solid fa-shield-heart"></i> Atención personalizada</span><span><i class="fa-solid fa-credit-card"></i> Opciones de pago flexibles</span></div>
        </div>
        <div class="catalogo-hero-art" aria-hidden="true"><div class="hero-orbit orbit-one"></div><div class="hero-orbit orbit-two"></div><div class="hero-bag"><i class="fa-solid fa-bag-shopping"></i></div><span class="hero-spark spark-one">✦</span><span class="hero-spark spark-two">✧</span><div class="hero-note"><i class="fa-solid fa-circle-check"></i><span>Tu próxima compra<br><strong>empieza aquí</strong></span></div></div>
    </section>
    <div class="catalogo-section-heading" id="productos"><div><span class="eyebrow">PARA TI</span><h2>Nuestros productos</h2><p>Agrega productos al carrito y programa cuándo los recogerás.</p></div><span class="catalogo-count"><i class="fa-solid fa-box-open"></i> <?= count($productos) ?> productos</span></div>

    <div class="catalogo-tools" style="display:flex;gap:12px;align-items:center;justify-content:space-between;margin:0 0 22px;flex-wrap:wrap">
        <label for="buscarCatalogo" style="position:relative;display:block;flex:1;min-width:240px;max-width:520px">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#758196"></i>
            <input id="buscarCatalogo" type="search" autocomplete="off" placeholder="Buscar productos…" aria-label="Buscar productos" style="width:100%;padding:13px 16px 13px 42px;border:1px solid #dce3ed;border-radius:14px;font:500 14px Inter,sans-serif;background:white">
        </label>
        <span id="resultadoCatalogo" aria-live="polite" style="font-size:13px;color:#758196"><?= count($productos) ?> productos</span>
    </div>
    <div class="card-grid" id="catalogoProductos">
        <?php foreach ($productos as $p): ?>
        <?php $unidadProducto = strtoupper((string) ($p->unidad_base ?? 'PZA')); ?>
        <div class="item-card catalogo-producto" data-nombre="<?= esc(mb_strtolower($p->nombre), 'attr') ?>" data-unidad="<?= esc($unidadProducto, 'attr') ?>">
            <?php
                $imagenesProducto = $imagenesPorProducto[$p->id_producto] ?? [];
                if (empty($imagenesProducto) && $p->ruta_imagen) {
                    $imagenesProducto = [(object) ['ruta_imagen' => $p->ruta_imagen]];
                }
            ?>
            <?php if (!empty($imagenesProducto)): ?>
                <div class="product-carousel" data-carousel>
                    <div class="product-carousel-track">
                        <?php foreach ($imagenesProducto as $indice => $imagen): ?>
                            <img class="product-carousel-image <?= $indice === 0 ? 'is-active' : '' ?>" src="<?= base_url() ?>assets/img/<?= esc($imagen->ruta_imagen) ?>" alt="<?= esc($p->nombre) ?> — foto <?= $indice + 1 ?>" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($imagenesProducto) > 1): ?>
                        <button type="button" class="carousel-arrow carousel-prev" onclick="moverCarrusel(this,-1)" aria-label="Foto anterior"><i class="fa-solid fa-chevron-left"></i></button>
                        <button type="button" class="carousel-arrow carousel-next" onclick="moverCarrusel(this,1)" aria-label="Foto siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                        <div class="carousel-dots" aria-label="Elegir foto">
                            <?php foreach ($imagenesProducto as $indice => $imagen): ?>
                                <button type="button" class="carousel-dot <?= $indice === 0 ? 'is-active' : '' ?>" onclick="mostrarFoto(this,<?= $indice ?>)" aria-label="Ver foto <?= $indice + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <span class="carousel-count"><span class="carousel-current">1</span> / <?= count($imagenesProducto) ?></span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="width:100%; aspect-ratio:1/1; background:#1c2733; border-radius:6px; display:flex; align-items:center; justify-content:center; margin-bottom:10px;"><i class="fa-solid fa-image" style="color:#45566b; font-size:28px;"></i></div>
            <?php endif; ?>
            <h3><?= esc($p->nombre) ?></h3>
            <p><?= esc(mb_strimwidth($p->descripcion ?? '', 0, 80, '...')) ?></p>
            <p style="font-family:var(--font-titulo); font-size:19px; color:var(--acento);">$<?= number_format($p->precio, 2) ?> <small style="font:500 11px Inter,sans-serif;color:#758196;">/ <?= esc($p->unidad_base ?? 'PZA') ?></small></p>
            <?php if ($unidadProducto === 'KG'): ?>
                <label style="display:block;margin:0 0 9px;font-size:12px;color:#64748b">¿Cuánto quieres gastar? (opcional)
                    <span style="display:flex;align-items:center;gap:7px;margin-top:5px"><span>$</span><input class="importe-verdura" type="number" min="0.01" step="0.01" inputmode="decimal" placeholder="Ej. 54.00" aria-label="Importe en pesos para <?= esc($p->nombre, 'attr') ?>" style="width:100%;padding:9px 10px;border:1px solid #dce3ed;border-radius:9px"></span>
                </label>
                <small class="equivalencia-verdura" style="display:block;min-height:18px;margin-bottom:8px;color:#15803d"></small>
            <?php endif; ?>
            <button class="btn btn-cta btn-full" data-product-id="<?= (int) $p->id_producto ?>" onclick='agregarProductoCatalogo(this, <?= json_encode(["id"=>$p->id_producto,"nombre"=>$p->nombre,"precio"=>(float)$p->precio,"unidad"=>$unidadProducto,"stock"=>$p->stock], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                <i class="fa-solid fa-cart-plus"></i> Agregar
            </button>
        </div>
        <?php endforeach; ?>
        <?php if (empty($productos)): ?>
            <p>Por el momento no hay productos disponibles. Vuelve pronto.</p>
        <?php endif; ?>
    </div>
</main>

<!-- Carrito lateral -->
<div class="modal-overlay" id="modalCarrito">
    <div class="modal-box carrito-box">
        <button type="button" class="modal-close carrito-close" onclick="closeModal('modalCarrito')" aria-label="Cerrar carrito">&times;</button>
        <div class="carrito-heading"><span class="carrito-heading-icon"><i class="fa-solid fa-bag-shopping"></i></span><div><span class="eyebrow">TU PEDIDO</span><h3>Tu carrito</h3><p>Revisa tus productos antes de continuar</p></div></div>
        <div id="listaCarrito"></div>
        <div class="carrito-total"><span>Total del pedido</span><strong>$<span id="totalCarrito">0.00</span></strong></div>

        <div id="formularioSolicitud" class="carrito-form">
            <?php if (empty($cliente)): ?>
                <div class="carrito-form-heading"><span class="carrito-step">2</span><div><h4>Inicia sesión para continuar</h4><p>Los pedidos están disponibles para clientes registrados.</p></div></div>
                <a class="btn btn-primary btn-full carrito-submit" href="<?= base_url('portal') ?>"><i class="fa-solid fa-user"></i> Entrar a mi cuenta</a>
                <p class="form-help">¿Aún no tienes cuenta? Regístrate con el personal de la tienda para que te asignen tu crédito y PIN.</p>
            <?php else: ?>
                <div class="carrito-form-heading"><span class="carrito-step">2</span><div><h4>Programa tu recogida</h4><p>Pedido a nombre de <?= esc($cliente->nombre_completo) ?></p></div></div>
                <div class="credit-pickup-balance" data-credit-available="<?= esc($cliente->credito_disponible) ?>">Crédito disponible: <strong>$<?= number_format($cliente->credito_disponible, 2) ?></strong></div>
                <div class="form-group"><label for="pickupAt">Día y hora para recoger</label><input type="datetime-local" id="pickupAt" required><small class="form-help">Programa con al menos 30 minutos de anticipación, hasta 7 días adelante.</small></div>
                <div class="form-group"><label for="c_notas">Notas para preparar el pedido (opcional)</label><textarea id="c_notas" rows="2" maxlength="500"></textarea></div>
                <button class="btn btn-cta btn-full carrito-submit" id="confirmarPedido" onclick="enviarSolicitud()"><i class="fa-solid fa-calendar-check"></i> Confirmar pedido a crédito <i class="fa-solid fa-arrow-right"></i></button>
            <?php endif; ?>
            <p id="mensajeCarrito" class="form-mensaje"></p>
        </div>
        <div id="confirmacionSolicitud" class="request-success" hidden>
            <span class="request-success-icon"><i class="fa-solid fa-circle-check"></i></span><h3>¡Pedido confirmado!</h3><p id="resumenPedido"></p><div class="request-reference"><small>Folio de pedido</small><strong id="folioSolicitud"></strong></div><a class="btn btn-primary btn-full" href="<?= base_url('portal/cuenta') ?>">Ver mi cuenta</a>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalSeguimientoSolicitud">
    <div class="modal-box request-track-box">
        <button type="button" class="modal-close carrito-close" onclick="closeModal('modalSeguimientoSolicitud')" aria-label="Cerrar">&times;</button>
        <span class="carrito-heading-icon"><i class="fa-solid fa-box"></i></span><h3>Consulta tu pedido</h3><p class="section-subtitle">Ingresa el código privado que recibiste al enviar tu solicitud.</p>
        <form id="formEstadoSolicitud" onsubmit="return false;"><div class="form-group"><label>Código de seguimiento</label><input type="text" name="token" id="tokenConsulta" maxlength="48" minlength="48" placeholder="Código de 48 caracteres" required></div><button class="btn btn-primary btn-full" type="submit">Consultar estado</button></form>
        <div id="resultadoEstadoSolicitud" class="request-status-result" hidden><span class="badge" id="estadoSolicitudBadge"></span><h4 id="mensajeEstadoSolicitud"></h4><p>Folio <strong id="folioEstadoSolicitud"></strong> · Actualizado <span id="fechaEstadoSolicitud"></span></p><p id="motivoEstadoSolicitud" hidden></p></div>
        <p id="errorEstadoSolicitud" class="form-mensaje"></p>
    </div>
</div>

<script src="<?= base_url() ?>assets/js/carrito.js?v=<?= time() ?>"></script>
<script>
const BASE = <?= json_encode(base_url(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const CATALOGO = <?= json_encode(array_map(static fn($p) => ['id'=>(int)$p->id_producto,'nombre'=>$p->nombre,'precio'=>(float)$p->precio,'unidad'=>$p->unidad_base ?? 'PZA','stock'=>$p->stock === null ? null : (float)$p->stock], $productos), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
function normalizarBusqueda(texto) { return String(texto || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es-MX').trim(); }
function filtrarCatalogo() {
    const termino = normalizarBusqueda(document.getElementById('buscarCatalogo').value);
    let visibles = 0;
    document.querySelectorAll('.catalogo-producto').forEach(card => {
        const coincide = normalizarBusqueda(card.dataset.nombre).includes(termino);
        card.hidden = !coincide;
        card.style.display = coincide ? '' : 'none';
        if (coincide) visibles++;
    });
    document.getElementById('resultadoCatalogo').textContent = visibles + (visibles === 1 ? ' producto' : ' productos');
}
function actualizarEquivalenciaVerdura(input) {
    const card = input.closest('.catalogo-producto');
    const p = productoCatalogo(card.querySelector('button').dataset.productId);
    const precio = Number(p?.precio || 0), pesos = Number(input.value || 0);
    const cantidad = precio > 0 && pesos > 0 ? pesos / precio : 0;
    card.querySelector('.equivalencia-verdura').textContent = cantidad ? `Aproximadamente ${cantidad.toFixed(3)} kg` : '';
}
function agregarProductoCatalogo(boton, producto) {
    const card = boton.closest('.catalogo-producto');
    const importe = card.querySelector('.importe-verdura');
    let cantidad = 1;
    if (producto.unidad === 'KG' && importe && importe.value !== '') {
        const pesos = Number(importe.value);
        if (!Number.isFinite(pesos) || pesos <= 0) { importe.focus(); return; }
        cantidad = Math.round((pesos / Number(producto.precio)) * 1000) / 1000;
        if (cantidad <= 0) { importe.focus(); return; }
        if (producto.stock !== null && cantidad > Number(producto.stock)) {
            alert('El importe supera la existencia disponible de este producto.'); return;
        }
    }
    agregarAlCarrito(producto, cantidad);
    if (importe) { importe.value = ''; card.querySelector('.equivalencia-verdura').textContent = ''; }
}
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('buscarCatalogo')?.addEventListener('input', filtrarCatalogo);
    document.querySelectorAll('.importe-verdura').forEach(input => input.addEventListener('input', () => actualizarEquivalenciaVerdura(input)));
});
</script>
<script>
function actualizarCarrusel(carrusel, indice) {
    const fotos = [...carrusel.querySelectorAll('.product-carousel-image')];
    const indiceActivo = (indice + fotos.length) % fotos.length;
    fotos.forEach((foto, i) => foto.classList.toggle('is-active', i === indiceActivo));
    carrusel.querySelectorAll('.carousel-dot').forEach((punto, i) => punto.classList.toggle('is-active', i === indiceActivo));
    const contador = carrusel.querySelector('.carousel-current');
    if (contador) contador.textContent = indiceActivo + 1;
}
function moverCarrusel(boton, direccion) {
    const carrusel = boton.closest('[data-carousel]');
    const actual = [...carrusel.querySelectorAll('.product-carousel-image')].findIndex(foto => foto.classList.contains('is-active'));
    actualizarCarrusel(carrusel, actual + direccion);
}
function mostrarFoto(boton, indice) { actualizarCarrusel(boton.closest('[data-carousel]'), indice); }

function abrirSeguimientoSolicitud(token = '') {
    const guardado = token || sessionStorage.getItem('cf_ultimo_token_solicitud') || '';
    document.getElementById('tokenConsulta').value = guardado;
    document.getElementById('modalSeguimientoSolicitud').classList.add('open');
}
document.getElementById('formEstadoSolicitud').addEventListener('submit', async function () {
    const datos = new URLSearchParams(new FormData(this));
    const respuesta = await fetch(BASE + 'catalogo/estado', { method: 'POST', body: datos }).then(r => r.json());
    const error = document.getElementById('errorEstadoSolicitud');
    const resultado = document.getElementById('resultadoEstadoSolicitud');
    error.textContent = respuesta.respuesta ? '' : respuesta.mensaje;
    error.className = 'form-mensaje ' + (respuesta.respuesta ? '' : 'error');
    resultado.hidden = !respuesta.respuesta;
    if (respuesta.respuesta) {
        const s = respuesta.solicitud;
        document.getElementById('estadoSolicitudBadge').textContent = s.estatus;
        document.getElementById('estadoSolicitudBadge').className = 'badge badge-' + s.estatus.toLowerCase();
        document.getElementById('mensajeEstadoSolicitud').textContent = s.mensaje;
        document.getElementById('folioEstadoSolicitud').textContent = '#' + s.folio;
        document.getElementById('fechaEstadoSolicitud').textContent = new Date(s.fecha).toLocaleString('es-MX');
        const motivo = document.getElementById('motivoEstadoSolicitud');
        motivo.hidden = !s.motivo;
        motivo.textContent = s.motivo ? 'Motivo: ' + s.motivo : '';
    }
});
</script>

</body>
</html>
