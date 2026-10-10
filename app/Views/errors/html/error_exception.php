<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Error | Tienda y Abarrotes (desarrollo)</title>
    <style>
        body { background:#171d25; color:#eef2f6; font-family:monospace; padding:24px; }
        h1 { color:#e0574a; font-size:20px; }
        pre { background:#12161c; padding:16px; border-radius:6px; overflow-x:auto; white-space:pre-wrap; word-break:break-word; }
        .meta { color:#9aa7b5; margin-bottom:16px; }
    </style>
</head>
<body>
    <h1><?= esc(get_class($exception)) ?></h1>
    <p class="meta"><?= esc($exception->getMessage()) ?></p>
    <p class="meta">Archivo: <?= esc($exception->getFile()) ?> (línea <?= esc($exception->getLine()) ?>)</p>
    <pre><?= esc($exception->getTraceAsString()) ?></pre>
</body>
</html>
