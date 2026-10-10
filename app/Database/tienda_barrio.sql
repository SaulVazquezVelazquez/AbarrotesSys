-- Ejecutar después de tienda_abarrotes_schema.sql; también migra instalaciones existentes.
ALTER TABLE productos ADD COLUMN IF NOT EXISTS codigo_barras VARCHAR(32) UNIQUE;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS plu VARCHAR(8);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS departamento VARCHAR(20) NOT NULL DEFAULT 'ABARROTES';
ALTER TABLE productos ADD COLUMN IF NOT EXISTS unidad_base VARCHAR(12) NOT NULL DEFAULT 'PZA';
ALTER TABLE productos ADD COLUMN IF NOT EXISTS unidades_por_empaque NUMERIC(10,3) NOT NULL DEFAULT 1;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS stock_minimo NUMERIC(12,3) NOT NULL DEFAULT 0;
-- Conserva gramos y cantidades fraccionarias de verdulería en ventas e inventario.
ALTER TABLE productos ALTER COLUMN stock TYPE NUMERIC(12,3) USING stock::NUMERIC(12,3);
ALTER TABLE detalle_venta ALTER COLUMN cantidad TYPE NUMERIC(12,3) USING cantidad::NUMERIC(12,3);
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS pickup_at TIMESTAMP;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS pedido_estatus VARCHAR(20) NOT NULL DEFAULT 'NO_APLICA';
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS notas_pedido VARCHAR(500);
CREATE UNIQUE INDEX IF NOT EXISTS idx_productos_plu ON productos(plu) WHERE plu IS NOT NULL;

CREATE TABLE IF NOT EXISTS pos_ventas (
 id_venta SERIAL PRIMARY KEY, id_usuario INTEGER REFERENCES usuarios(id_usuario), id_cliente INTEGER REFERENCES clientes(id_cliente),
 fecha_venta TIMESTAMP NOT NULL DEFAULT NOW(), total NUMERIC(12,2) NOT NULL, subtotal_abarrotes NUMERIC(12,2) NOT NULL DEFAULT 0,
 subtotal_verduleria NUMERIC(12,2) NOT NULL DEFAULT 0, metodo_pago VARCHAR(20) NOT NULL,
 efectivo_recibido NUMERIC(12,2) NOT NULL DEFAULT 0, cambio NUMERIC(12,2) NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS pos_detalle (
 id_detalle SERIAL PRIMARY KEY, id_venta INTEGER NOT NULL REFERENCES pos_ventas(id_venta) ON DELETE CASCADE,
 id_producto INTEGER NOT NULL REFERENCES productos(id_producto), cantidad NUMERIC(12,3) NOT NULL,
 precio_unitario NUMERIC(12,2) NOT NULL, subtotal NUMERIC(12,2) NOT NULL, departamento VARCHAR(20) NOT NULL
);
CREATE TABLE IF NOT EXISTS pos_pagos (
 id_pago SERIAL PRIMARY KEY, id_venta INTEGER NOT NULL REFERENCES pos_ventas(id_venta) ON DELETE CASCADE,
 metodo VARCHAR(20) NOT NULL, monto NUMERIC(12,2) NOT NULL
);
CREATE TABLE IF NOT EXISTS pos_esperas (
 id_espera SERIAL PRIMARY KEY, id_usuario INTEGER REFERENCES usuarios(id_usuario), referencia VARCHAR(80),
 items_json TEXT NOT NULL, fecha_creacion TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE TABLE IF NOT EXISTS pos_mermas (
 id_merma SERIAL PRIMARY KEY, id_producto INTEGER NOT NULL REFERENCES productos(id_producto), id_usuario INTEGER REFERENCES usuarios(id_usuario),
 cantidad NUMERIC(12,3) NOT NULL, motivo VARCHAR(200), fecha TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE TABLE IF NOT EXISTS pos_entradas (
 id_entrada SERIAL PRIMARY KEY, id_producto INTEGER NOT NULL REFERENCES productos(id_producto), id_usuario INTEGER REFERENCES usuarios(id_usuario),
 empaques NUMERIC(12,3) NOT NULL, unidades_por_empaque NUMERIC(12,3) NOT NULL, cantidad_base NUMERIC(12,3) NOT NULL, fecha TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE TABLE IF NOT EXISTS pos_cortes (
 id_corte SERIAL PRIMARY KEY, id_usuario INTEGER REFERENCES usuarios(id_usuario), fecha TIMESTAMP NOT NULL DEFAULT NOW(),
 efectivo_esperado NUMERIC(12,2) NOT NULL, efectivo_contado NUMERIC(12,2) NOT NULL, total NUMERIC(12,2) NOT NULL,
 abarrotes NUMERIC(12,2) NOT NULL, verduleria NUMERIC(12,2) NOT NULL, desglose_json TEXT NOT NULL
);
INSERT INTO configuracion(clave,valor) VALUES
('POS_ETIQUETA_PREFIJO','20'),
('POS_ETIQUETA_TIPO','PRECIO')
ON CONFLICT(clave) DO NOTHING;
