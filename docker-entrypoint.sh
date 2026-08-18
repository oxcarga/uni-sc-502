#!/bin/bash
set -e

echo "Starting Pulso Solidario Backend Server..."

# Se invoca con bash porque el bit de ejecución no sobrevive el bind mount
# en algunos hosts (Windows), y sin él la DB quedaría sin provisionar en silencio.
if [[ -f /database/provision.sh ]]; then
  bash /database/provision.sh
fi

echo "Container initialization complete! Starting Apache..."
exec apache2-foreground
