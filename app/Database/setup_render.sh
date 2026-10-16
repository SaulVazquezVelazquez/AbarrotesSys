#!/bin/sh
set -eu

DB_URL="${SUPABASE_DATABASE_URL:-${DATABASE_URL:-}}"
if [ -z "$DB_URL" ]; then
  echo "Define SUPABASE_DATABASE_URL o DATABASE_URL con la URL de PostgreSQL antes de continuar." >&2
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
  psql "$DB_URL" -v ON_ERROR_STOP=1 -f "$SCRIPT_DIR/$script"
done

echo "Esquema de Tienda y Abarrotes aplicado correctamente."
