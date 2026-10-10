#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"
if [[ -r "$ENV_FILE" ]]; then
  while IFS= read -r line || [[ -n "$line" ]]; do
    if [[ "$line" =~ ^[[:space:]]*(DB_HOSTNAME|DB_PORT|DB_USERNAME|DB_PASSWORD|DB_DATABASE)[[:space:]]*=[[:space:]]*(.*)$ ]]; then
      key="${BASH_REMATCH[1]}"
      value="${BASH_REMATCH[2]}"
      value="${value%\"}"; value="${value#\"}"
      value="${value%\'}"; value="${value#\'}"
      if [[ -z "${!key:-}" ]]; then export "$key=$value"; fi
    fi
  done < "$ENV_FILE"
fi

DB_HOSTNAME="${DB_HOSTNAME:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_USERNAME="${DB_USERNAME:-postgres}"
DB_PASSWORD="${DB_PASSWORD:-root}"
DB_DATABASE="${DB_DATABASE:-tienda_abarrotes}"
export PGHOST="$DB_HOSTNAME" PGPORT="$DB_PORT" PGUSER="$DB_USERNAME" PGPASSWORD="$DB_PASSWORD"

if ! pg_isready -h "$DB_HOSTNAME" -p "$DB_PORT" >/dev/null 2>&1; then
  echo "PostgreSQL no está disponible en ${DB_HOSTNAME}:${DB_PORT}. Inicia el servidor PostgreSQL de MAMP y vuelve a ejecutar este script." >&2
  exit 1
fi

if ! psql -d postgres -Atqc "SELECT 1 FROM pg_database WHERE datname = '$DB_DATABASE'" | grep -q 1; then
  createdb "$DB_DATABASE"
fi

for script in credito_facil_schema.sql credito_facil_solicitudes.sql credito_facil_imagenes_productos.sql credito_facil_mejoras_operativas.sql tienda_barrio.sql; do
  echo "Aplicando $script..."
  psql -v ON_ERROR_STOP=1 -d "$DB_DATABASE" -f "$SCRIPT_DIR/$script"
done
echo "Base $DB_DATABASE lista. Inicia la aplicación con: php spark serve"
