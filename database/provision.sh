#!/usr/bin/env bash
# Aplica esquema y datos de ejemplo (idempotente).
# Se ejecuta automáticamente al arrancar el contenedor backend (docker-entrypoint.sh).
# También se puede lanzar a mano desde el host: ./database/provision.sh
#
# Nota: la carpeta database/ también se monta en /docker-entrypoint-initdb.d del
# contenedor MySQL. Ahí ya corren 01_init.sql y 02_seed.sql; este script no debe
# reejecutarse (el servidor temporal del init no escucha TCP y bloquearía el arranque).

set -euo pipefail

if [[ "${BASH_SOURCE[0]}" == /docker-entrypoint-initdb.d/* ]]; then
  echo "Omitiendo provision.sh durante initdb de MySQL (los .sql ya se aplicaron)."
  exit 0
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

DB_HOST="${MYSQL_HOST:-db}"
DB_NAME="${MYSQL_DATABASE:-${DB_NAME:-pulso_solidario}}"
DB_USER="${MYSQL_USER:-pulso_user}"
DB_PASSWORD="${MYSQL_PASSWORD:-pulso_password}"
DB_CONTAINER="${DB_CONTAINER:-pulso-solidario-db}"

MYSQL_SSL_FLAGS=(--skip-ssl)
# El cliente MariaDB del backend (locale POSIX) usa latin1 por defecto.
# Sin utf8mb4, acentos del seed se guardan como mojibake (Rodríguez → RodrÃ­guez).
MYSQL_CHARSET_FLAGS=(--default-character-set=utf8mb4)

run_sql() {
  local file="$1"
  echo "→ $(basename "$file")"
  if command -v mysql &>/dev/null; then
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" \
      "${MYSQL_SSL_FLAGS[@]}" "${MYSQL_CHARSET_FLAGS[@]}" "$DB_NAME" < "$file"
  else
    docker exec -i "$DB_CONTAINER" \
      mysql -u"$DB_USER" -p"$DB_PASSWORD" "${MYSQL_CHARSET_FLAGS[@]}" "$DB_NAME" < "$file"
  fi
}

wait_for_mysql() {
  if ! command -v mysqladmin &>/dev/null; then
    return 0
  fi

  echo "Esperando MySQL en $DB_HOST..."
  for _ in $(seq 1 30); do
    if mysqladmin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "${MYSQL_SSL_FLAGS[@]}" --silent 2>/dev/null; then
      return 0
    fi
    sleep 2
  done

  echo "MySQL no respondió a tiempo en $DB_HOST"
  exit 1
}

if ! command -v mysql &>/dev/null; then
  if ! docker ps --format '{{.Names}}' | grep -qx "$DB_CONTAINER"; then
    echo "El contenedor '$DB_CONTAINER' no está en ejecución."
    echo "Levanta el stack primero: docker-compose up -d"
    exit 1
  fi
fi

echo "Provisionando base de datos '$DB_NAME'..."
wait_for_mysql
run_sql "$SCRIPT_DIR/01_init.sql"
run_sql "$SCRIPT_DIR/02_seed.sql"
echo "Base de datos lista."
