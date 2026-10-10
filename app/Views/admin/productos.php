<?= $this->include('admin/layout_header') ?>

<div class="toolbar">
    <h1 style="margin-bottom:0;">Productos</h1>
    <a class="btn btn-secondary btn-sm" href="<?= base_url('admin/productos/plantilla') ?>">Descargar plantilla</a>
    <button class="btn btn-primary btn-sm" onclick="abrirNuevo()"><i class="fa-solid fa-plus"></i> Nuevo producto</button>
</div>

<?php foreach (['success' => 'ok', 'error' => 'error', 'warning' => 'error'] as $key => $class): ?>
    <?php if ($message = session()->getFlashdata($key)): ?><div class="form-mensaje <?= $class ?>" style="display:block; margin:12px 0;"><?= esc($message) ?></div><?php endif; ?>
<?php endforeach; ?>

<section class="card" style="margin:16px 0; padding:18px;">
    <h2 style="margin:0 0 8px;">Carga masiva desde Excel</h2>
    <p class="form-help">Sube un archivo .xlsx o .csv. Usa la plantilla con la columna opcional <strong>stock_minimo</strong>; los códigos de barras ya registrados se omiten.</p>
    <form method="post" action="<?= base_url('admin/productos/importar') ?>" enctype="multipart/form-data" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <?= csrf_field() ?>
        <input type="file" name="archivo_productos" accept=".xlsx,.csv" required>
        <button class="btn btn-primary btn-sm" type="submit">Importar productos</button>
    </form>
</section>

<form method="get" class="product-filters">
    <input type="search" name="q" class="admin-input" placeholder="Buscar producto" value="<?= esc($filtros['q'] ?? '') ?>">
    <select name="categoria" class="admin-select"><option value="">Todas las categorías</option><?php foreach ($categorias as $categoria): ?><option value="<?= esc($categoria->categoria) ?>" <?= ($filtros['categoria'] ?? '') === $categoria->categoria ? 'selected' : '' ?>><?= esc($categoria->categoria) ?></option><?php endforeach; ?></select>
    <select name="disponibilidad" class="admin-select"><option value="">Cualquier disponibilidad</option><option value="disponible" <?= ($filtros['disponibilidad'] ?? '') === 'disponible' ? 'selected' : '' ?>>Disponible</option><option value="bajo" <?= ($filtros['disponibilidad'] ?? '') === 'bajo' ? 'selected' : '' ?>>Por agotarse</option><option value="agotado" <?= ($filtros['disponibilidad'] ?? '') === 'agotado' ? 'selected' : '' ?>>Agotado</option></select>
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
    <a href="<?= base_url('admin/productos') ?>" class="btn btn-secondary btn-sm">Limpiar</a>
</form>

<div class="form-mensaje <?= ($productosBajoMinimo ?? 0) > 0 ? 'error' : 'ok' ?>" style="display:block;margin:0 0 16px;">
    <strong>Semáforo de inventario:</strong>
    <span style="color:#16834b">● Verde: suficiente</span> ·
    <span style="color:#bc7900">● Amarillo: por agotarse</span> ·
    <span style="color:#c33b3b">● Rojo: agotado</span>
    <span style="margin-left:10px;"><?= (int) ($productosBajoMinimo ?? 0) ?> producto(s) en nivel bajo o agotado</span>
</div>

<div class="card-grid">
    <?php foreach ($productos as $p): ?>
    <div class="item-card">
        <?php $imagenesProducto = $imagenesPorProducto[$p->id_producto] ?? []; ?>
        <?php if (!empty($imagenesProducto) || $p->ruta_imagen): ?>
            <img src="<?= base_url() ?>assets/img/<?= esc($imagenesProducto[0]->ruta_imagen ?? $p->ruta_imagen) ?>" alt="<?= esc($p->nombre) ?>">
        <?php else: ?>
            <div style="width:100%; aspect-ratio:1/1; background:#222; border-radius:6px; display:flex; align-items:center; justify-content:center; margin-bottom:10px;"><i class="fa-solid fa-image" style="color:#555; font-size:28px;"></i></div>
        <?php endif; ?>
        <h3><?= esc($p->nombre) ?></h3>
        <p><?= esc(mb_strimwidth($p->descripcion ?? '', 0, 70, '...')) ?></p>
        <p style="font-family:var(--font-titulo); font-size:18px; color:var(--acento-suave);">$<?= number_format($p->precio, 2) ?></p>
        <?php if ($p->stock !== null): ?>
            <?php $stockActual = (float) $p->stock; $stockMinimo = (float) ($p->stock_minimo ?? 0); $estadoStock = $stockActual <= 0 ? 'Agotado' : ($stockActual <= $stockMinimo ? 'Por agotarse' : 'Suficiente'); $colorStock = $stockActual <= 0 ? '#c33b3b' : ($stockActual <= $stockMinimo ? '#bc7900' : '#16834b'); ?>
            <p>Stock: <strong><?= esc($p->stock) ?> <?= esc($p->unidad_base ?? 'PZA') ?></strong> <span style="display:inline-block;padding:3px 8px;border-radius:99px;background:<?= $stockActual <= 0 ? '#fff0f0' : ($stockActual <= $stockMinimo ? '#fff6df' : '#eaf8ef') ?>;color:<?= $colorStock ?>;font-size:11px;font-weight:700">● <?= esc($estadoStock) ?></span></p>
            <small class="form-help">Mínimo: <?= esc($p->stock_minimo ?? 0) ?> <?= esc($p->unidad_base ?? 'PZA') ?></small>
        <?php else: ?><p><span style="color:#64748b">Stock no controlado</span></p><?php endif; ?>
        <button class="btn btn-secondary btn-sm" onclick='abrirEditar(<?= esc(json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)) ?>, <?= esc(json_encode($imagenesProducto, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG)) ?>)'>Editar</button>
    </div>
    <?php endforeach; ?>
    <?php if (empty($productos)): ?><p>No se encontraron productos con esos filtros.</p><?php endif; ?>
</div>

<div class="modal-overlay" id="modalProducto">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal('modalProducto')">&times;</span>
        <h3 id="tituloModal">Nuevo producto</h3>
        <form id="formProducto" onsubmit="return false;" enctype="multipart/form-data">
            <input type="hidden" id="p_id" name="id_producto">
            <div class="form-group"><label>Nombre</label><input type="text" id="p_nombre" name="nombre" required></div>
            <div class="form-group"><label>Descripción</label><textarea id="p_descripcion" name="descripcion" rows="3"></textarea></div>
            <div class="form-group"><label>Categoría</label><input type="text" id="p_categoria" name="categoria"></div>
            <div class="form-group"><label>Precio ($)</label><input type="number" step="0.01" id="p_precio" name="precio" required></div>
            <div class="form-group"><label>Stock (kg o piezas)</label><input type="number" step="0.001" id="p_stock" name="stock"></div>
            <div class="form-group"><label>Stock mínimo para alerta</label><input type="number" min="0" step="0.001" id="p_stock_minimo" name="stock_minimo" value="0" required><small class="form-help">Al llegar a este nivel se mostrará amarillo; con cero existencias se mostrará rojo.</small></div>
            <div class="form-group"><label>Código de barras</label><input id="p_codigo" name="codigo_barras"></div><div class="form-group"><label>PLU báscula (5 dígitos)</label><input id="p_plu" name="plu" maxlength="8"></div>
            <div class="form-group"><label>Departamento</label><select id="p_departamento" name="departamento"><option>ABARROTES</option><option>VERDULERIA</option></select></div>
            <div class="form-group"><label>Unidad base</label><select id="p_unidad" name="unidad_base"><option>PZA</option><option>KG</option><option>G</option><option>L</option></select></div>
            <div class="form-group"><label>Unidades base por empaque de compra</label><input type="number" step="0.001" id="p_empaque" name="unidades_por_empaque" value="1"></div>
            <div class="form-group"><label>Fotos del producto</label><input type="file" name="imagenes[]" accept="image/*" multiple><small class="form-help">Puedes seleccionar varias fotos. Las nuevas se agregan a las que ya tiene el producto.</small></div>
            <div id="galeriaImagenesActuales" class="admin-image-gallery"></div>
            <button type="submit" class="btn btn-primary btn-full">Guardar producto</button>
            <p id="mensajeProducto" class="form-mensaje"></p>
        </form>
    </div>
</div>

<script>
const BASE = '<?= base_url() ?>';
function abrirNuevo() {
    document.getElementById('formProducto').reset();
    document.getElementById('p_id').value = '';
    document.getElementById('tituloModal').textContent = 'Nuevo producto';
    document.getElementById('galeriaImagenesActuales').innerHTML = '';
    openModal('modalProducto');
}
function abrirEditar(p, imagenes = []) {
    document.getElementById('formProducto').reset();
    document.getElementById('p_id').value = p.id_producto;
    document.getElementById('p_nombre').value = p.nombre;
    document.getElementById('p_descripcion').value = p.descripcion || '';
    document.getElementById('p_categoria').value = p.categoria || '';
    document.getElementById('p_precio').value = p.precio;
    document.getElementById('p_stock').value = p.stock ?? '';
    document.getElementById('p_stock_minimo').value = p.stock_minimo ?? 0;
    document.getElementById('p_codigo').value = p.codigo_barras || ''; document.getElementById('p_plu').value = p.plu || '';
    document.getElementById('p_departamento').value = p.departamento || 'ABARROTES'; document.getElementById('p_unidad').value = p.unidad_base || 'PZA'; document.getElementById('p_empaque').value = p.unidades_por_empaque || 1;
    document.getElementById('tituloModal').textContent = 'Editar producto';
    const galeria = document.getElementById('galeriaImagenesActuales');
    galeria.innerHTML = '';
    if (imagenes.length) {
        galeria.innerHTML = '<strong>Fotos actuales</strong><div class="admin-image-list"></div><small class="form-help">Marca las fotos que quieras quitar.</small>';
        const lista = galeria.querySelector('.admin-image-list');
        imagenes.forEach((imagen, indice) => {
            const etiqueta = document.createElement('label');
            etiqueta.className = 'admin-image-choice';
            etiqueta.innerHTML = `<img src="${BASE}assets/img/${imagen.ruta_imagen}" alt="Foto ${indice + 1}"><span><input type="checkbox" name="eliminar_imagenes[]" value="${imagen.id_imagen}"> Quitar</span>`;
            lista.appendChild(etiqueta);
        });
    }
    openModal('modalProducto');
}
document.getElementById('formProducto').addEventListener('submit', async function () {
    const formData = new FormData(document.getElementById('formProducto'));
    const res = await fetch(BASE + 'admin/productos/guardar', { method: 'POST', body: formData });
    const data = await res.json();
    const msg = document.getElementById('mensajeProducto');
    msg.textContent = data.mensaje;
    msg.className = 'form-mensaje ' + (data.respuesta ? 'ok' : 'error');
    if (data.respuesta) setTimeout(() => window.location.reload(), 900);
});
</script>

<?= $this->include('admin/layout_footer') ?>
