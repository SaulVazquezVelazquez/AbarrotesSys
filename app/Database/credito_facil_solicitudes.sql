-- =====================================================================
-- CRÉDITO FÁCIL - Incremental: Catálogo público + Solicitudes de compra
-- Ejecutar DESPUÉS de credito_facil_schema.sql
-- =====================================================================

-- Cabecera de la solicitud que cualquier visitante puede enviar desde el
-- catálogo público (carrito), sin necesidad de iniciar sesión.
CREATE TABLE IF NOT EXISTS solicitudes_compra (
    id_solicitud     SERIAL PRIMARY KEY,
    id_cliente       INTEGER REFERENCES clientes(id_cliente), -- se liga si el teléfono ya existe como cliente
    nombre_cliente   VARCHAR(150) NOT NULL,
    telefono         VARCHAR(30)  NOT NULL,
    email            VARCHAR(150),
    notas            TEXT,
    total_estimado   NUMERIC(10,2) NOT NULL DEFAULT 0,
    estatus          VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE', -- PENDIENTE | APROBADA | RECHAZADA | CONVERTIDA
    motivo_rechazo   VARCHAR(255),
    id_venta         INTEGER REFERENCES ventas(id_venta), -- se llena al aprobar y convertir en venta a crédito
    ip_origen        VARCHAR(60),
    token_seguimiento VARCHAR(64) UNIQUE,
    fecha_creacion   TIMESTAMP NOT NULL DEFAULT NOW(),
    fecha_resolucion TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_solicitudes_estatus ON solicitudes_compra(estatus);

CREATE TABLE IF NOT EXISTS detalle_solicitud_compra (
    id_detalle       SERIAL PRIMARY KEY,
    id_solicitud     INTEGER NOT NULL REFERENCES solicitudes_compra(id_solicitud) ON DELETE CASCADE,
    id_producto      INTEGER REFERENCES productos(id_producto),
    nombre_producto  VARCHAR(150) NOT NULL, -- copia del nombre al momento de pedir (por si luego cambia/borra)
    cantidad         NUMERIC(10,2) NOT NULL DEFAULT 1,
    precio_unitario  NUMERIC(10,2) NOT NULL,
    subtotal         NUMERIC(10,2) NOT NULL
);
