<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Mi Cuenta | Tienda y Abarrotes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="<?= base_url() ?>assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>
    <div class="portal-body">
        <div class="portal-card" style="text-align:center; margin-top:60px;">
            <h1>Consulta tu cuenta</h1>
            <p style="color:var(--gris-texto);">Ingresa con tu teléfono y tu PIN de 4 dígitos.</p>
            <form id="formEntrar" onsubmit="return false;" style="text-align:left; margin-top:16px;">
                <div class="form-group"><label>Teléfono</label><input type="text" id="telefono" required></div>
                <div class="form-group"><label>PIN</label><input type="text" id="pin" maxlength="4" pattern="[0-9]{4}" required></div>
                <button type="submit" class="btn btn-primary btn-full" id="btnEntrar">Entrar</button>
                <p id="mensajeEntrar" class="form-mensaje"></p>
            </form>
            <p style="font-size:12.5px; color:var(--gris-texto); margin-top:14px;">¿No tienes PIN? Pídelo directamente en la tienda.</p>
        </div>
    </div>
    <script>
    document.getElementById('formEntrar').addEventListener('submit', async function () {
        const btn = document.getElementById('btnEntrar');
        btn.disabled = true;
        const body = new URLSearchParams({ telefono: document.getElementById('telefono').value, pin: document.getElementById('pin').value });
        const res = await fetch('<?= base_url() ?>portal/entrar', { method: 'POST', body });
        const data = await res.json();
        const msg = document.getElementById('mensajeEntrar');
        msg.textContent = data.mensaje;
        msg.className = 'form-mensaje ' + (data.respuesta ? 'ok' : 'error');
        if (data.respuesta) { window.location.href = '<?= base_url() ?>'; }
        else { btn.disabled = false; }
    });
    </script>
</body>
</html>
