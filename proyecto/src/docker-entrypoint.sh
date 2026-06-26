#!/bin/bash
set -e

echo "Starting Pulso Solidario Backend Server..."

if [[ -x /database/provision.sh ]]; then
  /database/provision.sh
fi

echo "Container initialization complete! Starting Apache..."
exec apache2-foreground
