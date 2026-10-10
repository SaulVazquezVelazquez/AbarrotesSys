async function adminPost(url, dataObj) {
    const body = new URLSearchParams(dataObj);
    const res = await fetch(url, { method: 'POST', body });
    return res.json();
}
async function enviarFormularioCliente(formulario) {
    const res = await fetch(BASE + 'admin/clientes/guardar', {
        method: 'POST',
        body: new FormData(formulario),
    });
    return res.json();
}
function mostrarVistaPrevia(input, idContenedor) {
    const archivo = input.files && input.files[0];
    if (!archivo) return;
    const contenedor = document.getElementById(idContenedor);
    const imagen = document.createElement('img');
    const url = URL.createObjectURL(archivo);
    imagen.src = url;
    imagen.alt = 'Vista previa de la foto del cliente';
    imagen.onload = () => URL.revokeObjectURL(url);
    contenedor.replaceChildren(imagen);
}
async function adminGet(url) {
    const res = await fetch(url);
    return res.json();
}
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function badgeEstatus(estatus) {
    return `<span class="badge badge-${(estatus||'').toLowerCase()}">${estatus||'-'}</span>`;
}
function dotSemaforo(semaforo) {
    return `<span class="semaforo-dot dot-${(semaforo||'verde').toLowerCase()}"></span>`;
}

document.addEventListener('DOMContentLoaded', function () {
    const path = window.location.pathname;
    document.querySelectorAll('.admin-sidebar nav a').forEach(function (a) {
        try {
            const linkPath = new URL(a.href).pathname;
            if (linkPath !== '/' && path.startsWith(linkPath)) a.classList.add('active');
        } catch (e) {}
    });
});
