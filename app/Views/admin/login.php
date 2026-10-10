<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Tienda y Abarrotes | Acceso</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="<?= base_url() ?>assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body style="display:flex; align-items:center; justify-content:center; min-height:100vh;">
    <div class="portal-card" style="width:340px; text-align:center;">
        <h1>TIENDA <span style="color:var(--acento-suave);">Y ABARROTES</span></h1>
        <p style="color:var(--gris-texto); margin-bottom:20px;">Panel administrativo</p>
        <form id="formLogin" onsubmit="return false;">
            <div class="form-group"><label>Usuario</label><input type="text" id="usuario" required></div>
            <div class="form-group"><label>Contraseña</label><input type="password" id="password" required></div>
            <button type="submit" class="btn btn-primary btn-full" id="btnLogin">Ingresar</button>
            <p id="mensajeLogin" class="form-mensaje"></p>
        </form>
    </div>
    <script>
    document.getElementById('formLogin').addEventListener('submit', async function () {
        const btn = document.getElementById('btnLogin');
        btn.disabled = true; btn.textContent = 'Ingresando...';
        const body = new URLSearchParams({ usuario: document.getElementById('usuario').value, password: document.getElementById('password').value });
        const res = await fetch('<?= base_url() ?>admin/login/autenticar', { method: 'POST', body });
        const data = await res.json();
        const msg = document.getElementById('mensajeLogin');
        msg.textContent = data.mensaje;
        msg.className = 'form-mensaje ' + (data.respuesta ? 'ok' : 'error');
        if (data.respuesta) { window.location.href = '<?= base_url() ?>admin/dashboard'; }
        else { btn.disabled = false; btn.textContent = 'Ingresar'; }
    });
    </script>
</body>
</html>
