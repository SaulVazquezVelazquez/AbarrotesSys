# Publicar Tienda y Abarrotes en Render

El proyecto está preparado como servicio Docker de CodeIgniter 4, PHP 8.2,
Apache y PostgreSQL. El Blueprint `render.yaml` crea el servicio web, conecta
PostgreSQL mediante `DATABASE_URL`, genera una clave privada para cifrado y
comprueba la disponibilidad en `/healthz`. Si se configura
`SUPABASE_DATABASE_URL`, la aplicación usa esa URL con prioridad sobre la base
de Render.

## 1. Subir el código a GitHub

Render despliega una rama de un repositorio Git. Antes de subirlo, revisa que
`.env`, `vendor/`, `writable/` y cualquier respaldo de la base de datos no estén
incluidos. `.gitignore` y `.dockerignore` excluyen esos archivos. Sube los
cambios a una rama y confirma que `Dockerfile`, `render.yaml` y esta guía estén
en la raíz del repositorio.

No publiques credenciales ni datos personales de clientes dentro del repositorio.

## 2. Crear los recursos en Render

1. En Render, crea un **Blueprint** y conecta el repositorio y rama que subiste.
2. Revisa los recursos descritos en `render.yaml` y confirma su creación.
3. Si quieres usar Supabase, agrega `SUPABASE_DATABASE_URL` en **Environment**
   del servicio web de Render y pega ahí la cadena de conexión PostgreSQL de
   Supabase con SSL requerido. No la agregues al repositorio ni a `render.yaml`.
   La app prioriza esta variable sobre `DATABASE_URL`, que el Blueprint enlaza
   a la base PostgreSQL de Render. `ENCRYPTION_KEY` se genera automáticamente.
4. Revisa **Events** y **Logs** si el despliegue no queda listo. El endpoint
   `/healthz` verifica que la aplicación pueda consultar PostgreSQL.

## 3. Crear el esquema en una base nueva

Usa la URL externa de PostgreSQL de la base que elegiste (Render o Supabase)
desde una terminal de tu computadora. No la guardes en Git ni la pegues en
mensajes. Desde la raíz del proyecto, exporta la variable correspondiente y
ejecuta el instalador:

```sh
export SUPABASE_DATABASE_URL='postgresql://USUARIO:CONTRASENA@HOST:5432/BASE?sslmode=require'
sh app/Database/setup_render.sh
```

Para instalar el esquema en PostgreSQL de Render, puedes exportar
`DATABASE_URL` en su lugar.

El script aplica el esquema base, clientes y crédito, catálogo, mejoras
operativas y tablas del POS. No carga productos de demostración. Si quieres
datos de ejemplo, ejecuta por separado `app/Database/productos_prueba.sql`
contra esa base.

Para crear el primer usuario dueño, genera un hash de contraseña en tu equipo:

```sh
php -r '$password = readline("Nueva contraseña: "); echo password_hash($password, PASSWORD_DEFAULT), PHP_EOL;'
```

Conecta `psql` a la base de Render y guarda el hash generado:

```sql
INSERT INTO usuarios (nombre_completo, usuario, password_hash, rol, activo)
VALUES ('Dueño de la tienda', 'admin', 'PEGA_AQUI_EL_HASH', 'DUENO', 1)
ON CONFLICT (usuario) DO NOTHING;
```

## 4. Copiar los datos locales existentes (opcional)

Si necesitas conservar los productos, clientes, ventas y usuarios de MAMP, haz
una copia de seguridad y restáurala en la base nueva de Render. No ejecutes esto
contra una base de Render que ya tenga información: la restauración reemplaza
objetos con el mismo nombre.

```sh
pg_dump -h 127.0.0.1 -p 5432 -U postgres -d tienda_abarrotes \
  --format=custom --no-owner --no-acl -f /tmp/tienda_abarrotes.dump
pg_restore --clean --if-exists --no-owner --no-acl \
  --dbname="$DATABASE_URL" /tmp/tienda_abarrotes.dump
```

Si usas credenciales distintas en MAMP, ajusta el host, puerto y usuario del
comando `pg_dump`. La restauración incluye el esquema; no ejecutes antes el paso
3 ni vuelvas a crear el usuario dueño si vas a restaurar la copia completa: el
respaldo ya incluye esos datos.

## 5. Verificar la aplicación

Abre la URL `onrender.com` del servicio y revisa el catálogo, `/portal`,
`/admin/login`, el alta de productos, el POS y una venta de prueba. Verifica que
la base usada sea la de Render y que los datos esperados aparezcan.

## Uso de prueba y uso con datos reales

El Blueprint actual usa planes gratuitos para facilitar una prueba. Render
indica que el servicio web gratuito puede suspenderse por inactividad y que las
bases PostgreSQL gratuitas tienen 1 GB, no incluyen respaldos administrados y
expiran 30 días después de crearse. Los archivos guardados por la app (sesiones,
fotos de clientes, comprobantes y nuevas fotos de productos) también pueden
perderse al reiniciar o desplegar porque el sistema de archivos gratuito es
temporal.

Antes de manejar datos reales, cambia a un servicio y PostgreSQL de pago,
configura copias de seguridad y almacenamiento persistente para los archivos
subidos. Otra opción es mover las imágenes y comprobantes a almacenamiento de
objetos. La base de datos conserva las ventas y el inventario, pero no conserva
por sí sola los archivos subidos.
