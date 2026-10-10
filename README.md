# Tienda y Abarrotes — POS e inventario

Sistema independiente para una tienda de abarrotes y verdulería, construido con CodeIgniter 4 y PostgreSQL. Incluye autenticación y roles, catálogo de productos y clientes, punto de venta, cuentas en espera, inventario por pieza o fracción, compras por empaque, merma, fiado, abonos y corte diario.

## Instalación

1. `composer install`
2. Copia `env.example` como `.env` y ajusta `APP_BASEURL` y credenciales de PostgreSQL si hace falta.
3. Inicia PostgreSQL en MAMP y confirma que escucha en `127.0.0.1:5432`. Copia `env.example` a `.env` si aún no lo hiciste y ajusta usuario/contraseña. El proyecto queda configurado para la base existente `tienda_abarrotes`. Para aplicar todos los scripts en orden, ejecuta `bash app/Database/setup_local.sh`. También puedes aplicar manualmente los siguientes scripts en `tienda_abarrotes`:

```sh
psql "postgresql://postgres:root@127.0.0.1:5432/tienda_abarrotes" -f app/Database/tienda_abarrotes_schema.sql
psql "postgresql://postgres:root@127.0.0.1:5432/tienda_abarrotes" -f app/Database/pedidos_pickup.sql
psql "postgresql://postgres:root@127.0.0.1:5432/tienda_abarrotes" -f app/Database/imagenes_productos.sql
psql "postgresql://postgres:root@127.0.0.1:5432/tienda_abarrotes" -f app/Database/mejoras_operativas.sql
psql "postgresql://postgres:root@127.0.0.1:5432/tienda_abarrotes" -f app/Database/tienda_barrio.sql
```

4. Inicia con `php spark serve`; POS: `/admin/pos` (requiere usuario autenticado).

## Despliegue en Render

La configuración Docker, Blueprint, comprobación de salud y pasos para preparar
PostgreSQL están en [DEPLOY_RENDER.md](DEPLOY_RENDER.md). El Blueprint actual
usa recursos gratuitos para pruebas; revisa las limitaciones de persistencia
antes de guardar datos reales.

## Operación

En Productos, capture código de barras o PLU, departamento, unidad base y existencia. Verdulería permite cantidades fraccionarias en kg. Los empaques de compra se convierten con el campo unidades base por empaque y el módulo Entrada. La captura de báscula usa prefijo `20`, PLU en 5 posiciones y los cinco dígitos siguientes como precio (dividido entre 100); se puede cambiar en `configuracion` con `POS_ETIQUETA_PREFIJO` y `POS_ETIQUETA_TIPO` (`PRECIO` o `PESO`). La etiqueta comercial debe tener longitud EAN-13 y coincidir con ese diseño.

El POS admite lector de código, búsqueda, teclas F3 para verdulería, ventas en espera, efectivo/transferencia/tarjeta/crédito, abonos con comprobante imprimible, registro de entradas y merma. El corte solicita primero el efectivo contado y luego contrasta con el registro del día, desglosado por departamento y método de pago.

## Referencia de componentes

Los controladores, modelos, vistas, sesiones y consultas siguen la estructura original `app/Controllers`, `app/Models`, `app/Views` y `app/Database`. El nuevo flujo está en `AdminPOS`, `MPOS`, `admin/pos.php` y `tienda_barrio.sql`.
