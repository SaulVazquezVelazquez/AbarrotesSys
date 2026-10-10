#!/bin/sh
set -eu

if [ -z "${DATABASE_URL:-}" ]; then
  echo "Define DATABASE_URL con la URL externa de PostgreSQL de Render antes de continuar." >&2
  exit 1
fi

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"

for script in \
  tienda_abarrotes_schema.sql \
  pedidos_pickup.sql \
  imagenes_productos.sql \
  mejoras_operativas.sql \
  tienda_barrio.sql
do
  echo "Aplicando ${script}..."
  psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -f "$SCRIPT_DIR/$script"
done

echo "Esquema de Tienda y Abarrotes aplicado correctamente."
