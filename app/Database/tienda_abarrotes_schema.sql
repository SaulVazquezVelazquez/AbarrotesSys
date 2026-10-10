-- =====================================================================
-- Esquema base de Tienda y Abarrotes, incluyendo fiado y cuentas de clientes.
-- PostgreSQL
-- =====================================================================
-- Ejecutar primero (conectado a la BD "postgres"):
--   CREATE DATABASE tienda_abarrotes WITH ENCODING 'UTF8' TEMPLATE template0;
-- Luego conectarte a tienda_abarrotes y correr el resto de este script.
-- =====================================================================

CREATE EXTENSION IF NOT EXISTS unaccent;

-- ---------------------------------------------------------------------
-- USUARIOS (dueño, administrador, cajeros) - multi-usuario con roles
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario      SERIAL PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    usuario         VARCHAR(60)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    rol             VARCHAR(20)  NOT NULL DEFAULT 'CAJERO', -- DUENO | ADMIN | CAJERO
    activo          SMALLINT     NOT NULL DEFAULT 1,
    fecha_creacion  TIMESTAMP    NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- CLIENTES (expediente digital + política de crédito)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientes (
    id_cliente        SERIAL PRIMARY KEY,
    nombre_completo   VARCHAR(150) NOT NULL,
    telefono          VARCHAR(30)  NOT NULL UNIQUE,
    direccion         VARCHAR(255),
    foto              VARCHAR(255),
    identificacion    VARCHAR(255), -- ruta de foto de identificación (opcional)
    pin_hash          VARCHAR(255), -- PIN de 4 dígitos (hasheado) para el portal y confirmar ventas
    limite_credito    NUMERIC(10,2) NOT NULL DEFAULT 0,
    plazo_dias        INTEGER NOT NULL DEFAULT 15, -- semanal=7, quincenal=15, mensual=30
    estatus           VARCHAR(20) NOT NULL DEFAULT 'ACTIVO', -- ACTIVO | BLOQUEADO
    motivo_bloqueo    VARCHAR(255),
    notas             TEXT,
    fecha_creacion    TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_clientes_telefono ON clientes(telefono);

-- ---------------------------------------------------------------------
-- PRODUCTOS (catálogo con imagen)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
    id_producto     SERIAL PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    descripcion     TEXT,
    ruta_imagen     VARCHAR(255),
    categoria       VARCHAR(80),
    precio          NUMERIC(10,2) NOT NULL,
    stock           NUMERIC(12,3), -- NULL = no se controla stock (ej. servicios); 3 decimales para fracciones de kg
    stock_minimo    NUMERIC(12,3) NOT NULL DEFAULT 0,
    activo          SMALLINT NOT NULL DEFAULT 1,
    fecha_creacion  TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS producto_imagenes (
    id_imagen       SERIAL PRIMARY KEY,
    id_producto     INTEGER NOT NULL REFERENCES productos(id_producto) ON DELETE CASCADE,
    ruta_imagen     VARCHAR(255) NOT NULL,
    orden           INTEGER NOT NULL DEFAULT 0,
    fecha_creacion  TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_producto_imagenes_producto
    ON producto_imagenes(id_producto, orden, id_imagen);

-- ---------------------------------------------------------------------
-- VENTAS A CRÉDITO (cabecera) + detalle
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ventas (
    id_venta            SERIAL PRIMARY KEY,
    id_cliente          INTEGER NOT NULL REFERENCES clientes(id_cliente),
    id_usuario          INTEGER REFERENCES usuarios(id_usuario), -- quién atendió/autorizó
    fecha_venta         TIMESTAMP NOT NULL DEFAULT NOW(),
    fecha_limite_pago   DATE,
    subtotal            NUMERIC(10,2) NOT NULL DEFAULT 0,
    total               NUMERIC(10,2) NOT NULL DEFAULT 0,
    saldo_pendiente     NUMERIC(10,2) NOT NULL DEFAULT 0,
    estatus             VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE', -- PENDIENTE | PARCIAL | PAGADA | VENCIDA | CANCELADA
    metodo_confirmacion VARCHAR(20) DEFAULT 'NINGUNO', -- FIRMA | PIN | SMS | NINGUNO
    confirmacion_valor  TEXT, -- firma en base64, o el PIN/código usado como evidencia
    ip_registro         VARCHAR(60),
    pickup_at           TIMESTAMP,
    pedido_estatus      VARCHAR(20) NOT NULL DEFAULT 'NO_APLICA',
    notas_pedido        VARCHAR(500),
    fecha_creacion      TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_ventas_cliente ON ventas(id_cliente);
CREATE INDEX IF NOT EXISTS idx_ventas_estatus ON ventas(estatus);
CREATE INDEX IF NOT EXISTS idx_ventas_fecha_limite ON ventas(fecha_limite_pago);

CREATE TABLE IF NOT EXISTS detalle_venta (
    id_detalle       SERIAL PRIMARY KEY,
    id_venta         INTEGER NOT NULL REFERENCES ventas(id_venta) ON DELETE CASCADE,
    id_producto      INTEGER REFERENCES productos(id_producto),
    descripcion_libre VARCHAR(255), -- para artículos fuera de catálogo
    cantidad         NUMERIC(12,3) NOT NULL DEFAULT 1,
    precio_unitario  NUMERIC(10,2) NOT NULL,
    subtotal         NUMERIC(10,2) NOT NULL
);

-- ---------------------------------------------------------------------
-- PAGOS / ABONOS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pagos_abonos (
    id_pago           SERIAL PRIMARY KEY,
    id_cliente        INTEGER NOT NULL REFERENCES clientes(id_cliente),
    id_venta          INTEGER REFERENCES ventas(id_venta), -- NULL = abono general a la cuenta
    monto             NUMERIC(10,2) NOT NULL,
    metodo_pago       VARCHAR(30) NOT NULL DEFAULT 'EFECTIVO', -- EFECTIVO | TRANSFERENCIA | SPEI | QR | MERCADO_PAGO
    referencia_pago   VARCHAR(150),
    ruta_comprobante  VARCHAR(255), -- PDF generado del comprobante
    id_usuario        INTEGER REFERENCES usuarios(id_usuario),
    fecha_pago        TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_pagos_cliente ON pagos_abonos(id_cliente);
CREATE INDEX IF NOT EXISTS idx_pagos_venta ON pagos_abonos(id_venta);

-- ---------------------------------------------------------------------
-- AUDITORÍA (quién hizo qué)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auditoria (
    id_auditoria    SERIAL PRIMARY KEY,
    id_usuario      INTEGER REFERENCES usuarios(id_usuario),
    accion          VARCHAR(60) NOT NULL, -- CREAR_CREDITO | REGISTRAR_ABONO | MODIFICAR_LIMITE | BLOQUEAR_CLIENTE | CANCELAR_DEUDA...
    entidad         VARCHAR(60),          -- clientes | ventas | pagos_abonos...
    id_entidad      INTEGER,
    detalle         TEXT,
    fecha_creacion  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- CONFIGURACIÓN GLOBAL (parámetros ajustables sin tocar código)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracion (
    clave   VARCHAR(60) PRIMARY KEY,
    valor   VARCHAR(255) NOT NULL
);

INSERT INTO configuracion (clave, valor) VALUES
('LIMITE_CREDITO_DEFAULT', '1000'),
('PLAZO_DIAS_DEFAULT', '15'),
('NOMBRE_NEGOCIO', 'Mi Negocio')
ON CONFLICT (clave) DO NOTHING;

-- =====================================================================
-- VISTAS (saldo de clientes y flujo de caja)
-- =====================================================================

-- Saldo y crédito disponible por cliente
CREATE OR REPLACE VIEW vista_saldo_cliente AS
SELECT
    c.id_cliente,
    c.nombre_completo,
    c.telefono,
    c.limite_credito,
    c.estatus,
    COALESCE(SUM(v.saldo_pendiente) FILTER (WHERE v.estatus IN ('PENDIENTE','PARCIAL','VENCIDA')), 0) AS saldo_actual,
    c.limite_credito - COALESCE(SUM(v.saldo_pendiente) FILTER (WHERE v.estatus IN ('PENDIENTE','PARCIAL','VENCIDA')), 0) AS credito_disponible,
    MIN(v.fecha_limite_pago) FILTER (WHERE v.estatus IN ('PENDIENTE','PARCIAL','VENCIDA')) AS proxima_fecha_limite,
    CASE
        WHEN c.estatus = 'BLOQUEADO' THEN 'ROJO'
        WHEN MIN(v.fecha_limite_pago) FILTER (WHERE v.estatus IN ('PENDIENTE','PARCIAL','VENCIDA')) < CURRENT_DATE THEN 'ROJO'
        WHEN MIN(v.fecha_limite_pago) FILTER (WHERE v.estatus IN ('PENDIENTE','PARCIAL','VENCIDA')) <= CURRENT_DATE + INTERVAL '3 days' THEN 'AMARILLO'
        WHEN COALESCE(SUM(v.saldo_pendiente) FILTER (WHERE v.estatus IN ('PENDIENTE','PARCIAL','VENCIDA')), 0) >= c.limite_credito * 0.9 THEN 'AMARILLO'
        ELSE 'VERDE'
    END AS semaforo
FROM clientes c
LEFT JOIN ventas v ON v.id_cliente = c.id_cliente
GROUP BY c.id_cliente, c.nombre_completo, c.telefono, c.limite_credito, c.estatus;

-- Flujo de abonos registrados
CREATE OR REPLACE VIEW vista_flujo_caja AS
SELECT
    DATE(fecha_pago) AS dia,
    SUM(monto) AS total_cobrado
FROM pagos_abonos
GROUP BY DATE(fecha_pago)
ORDER BY dia DESC;

/*
php -r 'echo password_hash("12345678", PASSWORD_DEFAULT), PHP_EOL;'

INSERT INTO usuarios (nombre_completo, usuario, password_hash, rol, activo)
VALUES ('Fer Limoncito', 'admin', '$2y$12$gaBSUIBk52q8lZF5CL5lrueL9fECWOqTDe/AUAh12M3mLb7jvxbwi', 'DUENO', 1)
ON CONFLICT (usuario) DO NOTHING;
*/
