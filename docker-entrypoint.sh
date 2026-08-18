#!/bin/bash
set -e

echo "Starting Pulso Solidario Backend Server..."

# provision.sh llega por bind mount, así que puede traer CRLF desde un host Windows.
# Se normaliza a una copia temporal y se le pasa PROVISION_DIR porque los .sql
# siguen viviendo en el directorio montado, no junto a la copia.
if [[ -f /database/provision.sh ]]; then
  sed 's/\r$//' /database/provision.sh > /tmp/provision.sh
  PROVISION_DIR=/database bash /tmp/provision.sh
fi

echo "Container initialization complete! Starting Apache..."
exec apache2-foreground
