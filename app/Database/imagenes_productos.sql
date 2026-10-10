-- Galería de imágenes para productos existentes.
-- Ejecutar una vez después de tienda_abarrotes_schema.sql.
CREATE TABLE IF NOT EXISTS producto_imagenes (
    id_imagen       SERIAL PRIMARY KEY,
    id_producto     INTEGER NOT NULL REFERENCES productos(id_producto) ON DELETE CASCADE,
    ruta_imagen     VARCHAR(255) NOT NULL,
    orden           INTEGER NOT NULL DEFAULT 0,
    fecha_creacion  TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_producto_imagenes_producto
    ON producto_imagenes(id_producto, orden, id_imagen);

-- Migra las fotos principales existentes a la galería sin duplicarlas al repetir el script.
INSERT INTO producto_imagenes (id_producto, ruta_imagen, orden)
SELECT p.id_producto, p.ruta_imagen, 0
FROM productos p
WHERE p.ruta_imagen IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM producto_imagenes pi
      WHERE pi.id_producto = p.id_producto
        AND pi.ruta_imagen = p.ruta_imagen
  );
