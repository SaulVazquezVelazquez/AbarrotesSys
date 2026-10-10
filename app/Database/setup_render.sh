#!/bin/sh
set -eu

if [ -z "${DATABASE_URL:-}" ]; then
  echo "Define DATABASE_URL con la URL externa de PostgreSQL de Render antes de continuar." >&2
  exit 1
fi

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"

for script in \
  credito_facil_schema.sql \
  credito_facil_solicitudes.sql \
  credito_facil_imagenes_productos.sql \
  credito_facil_mejoras_operativas.sql \
  tienda_barrio.sql
do
  echo "Aplicando ${script}..."
  psql "$DATABASE_URL" -v ON_ERROR_STOP=1 -f "$SCRIPT_DIR/$script"
done

echo "Esquema de Tienda y Abarrotes aplicado correctamente."
