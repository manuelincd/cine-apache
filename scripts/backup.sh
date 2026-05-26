#!/bin/bash
FECHA=$(date '+%Y-%m-%d_%H-%M')
DESTINO=/home/manuelcd/Castillo/cine-sendera/backups/backup_$FECHA.sql
USO=$(df / | awk 'NR==2 {print $5}' | tr -d '%')
LIBRE=$((100 - USO))

if [ $LIBRE -gt 15 ]; then
    docker exec cine_mysql mysqldump --no-tablespaces -u cine_user -pcine_pass cine_sendera > $DESTINO
    echo "Backup guardado en $DESTINO"
else
    echo "Espacio insuficiente: solo $LIBRE% disponible"
fi
