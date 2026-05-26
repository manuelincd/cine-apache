#!/bin/bash
LOG=/var/log/cine_error.log
FECHA=$(date '+%Y-%m-%d %H:%M:%S')
TODO_OK=true

STATUS_APACHE=$(systemctl is-active apache2)
if [ "$STATUS_APACHE" != "active" ]; then
    systemctl restart apache2
    echo "$FECHA - Apache caido, reiniciado" >> $LOG
    TODO_OK=false
fi

STATUS_MYSQL=$(docker ps --filter name=cine_mysql --filter status=running -q)
if [ -z "$STATUS_MYSQL" ]; then
    docker start cine_mysql
    echo "$FECHA - MySQL caido, reiniciado" >> $LOG
    TODO_OK=false
fi

if [ "$TODO_OK" = true ]; then
    echo "$FECHA - OK: Apache y MySQL activos" >> $LOG
fi
