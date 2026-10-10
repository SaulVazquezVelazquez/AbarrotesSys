<?= $this->include('admin/layout_header') ?>

<div class="toolbar">
    <h1 style="margin-bottom:0;">Usuarios y roles</h1>
    <button class="btn btn-primary btn-sm" onclick="abrirNuevo()"><i class="fa-solid fa-plus"></i> Nuevo usuario</button>
</div>

<table class="admin-table">
    <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Activo</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><?= esc($u->nombre_completo) ?></td>
            <td><?= esc($u->usuario) ?></td>
            <td><?= esc($u->rol) ?></td>
            <td><?= $u->activo ? 'Sí' : 'No' ?></td>
            <td><button class="btn btn-secondary btn-sm" onclick='abrirEditar(<?= json_encode($u) ?>)'>Editar</button></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="modal-overlay" id="modalUsuario">
    <div class="modal-box">
        <span class="modal-close" onclick="closeModal('modalUsuario')">&times;</span>
        <h3 id="tituloModal">Nuevo usuario</h3>
        <form id="formUsuario" onsubmit="return false;">
            <input type="hidden" id="u_id" name="id_usuario">
            <div class="form-group"><label>Nombre completo</label><input type="text" id="u_nombre" name="nombre_completo" required></div>
            <div class="form-group"><label>Usuario (para iniciar sesión)</label><input type="text" id="u_usuario" name="usuario" required></div>
            <div class="form-group">
                <label>Rol</label>
                <select id="u_rol" name="rol" class="admin-select" style="width:100%;">
                    <option value="CAJERO">Cajero</option>
                    <option value="ADMIN">Administrador</option>
                    <option value="DUENO">Dueño</option>
                </select>
            </div>
            <div class="form-group"><label>Contraseña (dejar vacío para no cambiar)</label><input type="password" id="u_password" name="password"></div>
            <div class="form-group"><label><input type="checkbox" id="u_activo" name="activo" checked> Activo</label></div>
            <button type="submit" class="btn btn-primary btn-full">Guardar usuario</button>
            <p id="mensajeUsuario" class="form-mensaje"></p>
        </form>
    </div>
</div>

<script>
const BASE = '<?= base_url() ?>';
function abrirNuevo() {
    document.getElementById('formUsuario').reset();
    document.getElementById('u_id').value = '';
    document.getElementById('tituloModal').textContent = 'Nuevo usuario';
    openModal('modalUsuario');
}
function abrirEditar(u) {
    document.getElementById('formUsuario').reset();
    document.getElementById('u_id').value = u.id_usuario;
    document.getElementById('u_nombre').value = u.nombre_completo;
    document.getElementById('u_usuario').value = u.usuario;
    document.getElementById('u_rol').value = u.rol;
    document.getElementById('u_activo').checked = u.activo == 1;
    document.getElementById('tituloModal').textContent = 'Editar usuario';
    openModal('modalUsuario');
}
document.getElementById('formUsuario').addEventListener('submit', async function () {
    const data = Object.fromEntries(new FormData(document.getElementById('formUsuario')));
    data.activo = document.getElementById('u_activo').checked ? 1 : 0;
    const r = await adminPost(BASE + 'admin/usuarios/guardar', data);
    document.getElementById('mensajeUsuario').textContent = r.mensaje;
    document.getElementById('mensajeUsuario').className = 'form-mensaje ' + (r.respuesta ? 'ok' : 'error');
    if (r.respuesta) setTimeout(() => window.location.reload(), 900);
});
</script>

<?= $this->include('admin/layout_footer') ?>
