-- Datos de demostración para el catálogo de Tienda y Abarrotes.
-- Ejecutar después de tienda_abarrotes_schema.sql y tienda_barrio.sql.
-- Se omiten códigos de barras ya existentes para permitir ejecutar el script más de una vez.

INSERT INTO productos
    (nombre, descripcion, categoria, precio, stock, activo, codigo_barras, plu, departamento, unidad_base, unidades_por_empaque)
VALUES
    ('Arroz blanco 1 kg', 'Bolsa de arroz blanco', 'Granos', 28.50, 25, 1, '7501000000012', NULL, 'ABARROTES', 'PZA', 12),
    ('Frijol negro 1 kg', 'Bolsa de frijol negro', 'Granos', 36.00, 18, 1, '7501000000029', NULL, 'ABARROTES', 'PZA', 12),
    ('Azúcar 1 kg', 'Azúcar estándar', 'Básicos', 29.00, 20, 1, '7501000000036', NULL, 'ABARROTES', 'PZA', 10),
    ('Aceite vegetal 1 L', 'Aceite comestible', 'Abarrotes', 42.50, 15, 1, '7501000000043', NULL, 'ABARROTES', 'PZA', 12),
    ('Atún en agua 140 g', 'Lata de atún', 'Enlatados', 19.50, 30, 1, '7501000000050', NULL, 'ABARROTES', 'PZA', 24),
    ('Leche entera 1 L', 'Leche ultrapasteurizada', 'Lácteos', 27.00, 24, 1, '7501000000067', NULL, 'ABARROTES', 'PZA', 12),
    ('Pasta de sopa 200 g', 'Pasta tipo codito', 'Pastas', 12.00, 40, 1, '7501000000074', NULL, 'ABARROTES', 'PZA', 20),
    ('Refresco cola 600 ml', 'Botella individual', 'Bebidas', 18.00, 36, 1, '7501000000081', NULL, 'ABARROTES', 'PZA', 24),
    ('Jitomate', 'Jitomate saladette vendido por kilogramo', 'Frutas y verduras', 32.00, 18.500, 1, '7501000001019', '01001', 'VERDULERIA', 'KG', 20),
    ('Papa blanca', 'Papa blanca a granel', 'Frutas y verduras', 28.00, 25.000, 1, '7501000001026', '01002', 'VERDULERIA', 'KG', 20),
    ('Cebolla blanca', 'Cebolla blanca a granel', 'Frutas y verduras', 30.00, 12.000, 1, '7501000001033', '01003', 'VERDULERIA', 'KG', 20),
    ('Plátano', 'Plátano a granel', 'Frutas y verduras', 24.00, 16.750, 1, '7501000001040', '01004', 'VERDULERIA', 'KG', 20),
    ('Limón', 'Limón a granel', 'Frutas y verduras', 38.00, 14.250, 1, '7501000001057', '01005', 'VERDULERIA', 'KG', 20),
    ('Zanahoria', 'Zanahoria a granel', 'Frutas y verduras', 18.00, 10.000, 1, '7501000001064', '01006', 'VERDULERIA', 'KG', 20)
ON CONFLICT (codigo_barras) DO NOTHING;
