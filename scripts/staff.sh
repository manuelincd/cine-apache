#!/bin/bash
if [ $# -eq 3 ]; then
    USUARIO=$1
    ROL=$2
    CANTIDAD=$3
else
    read -p "Cuantos usuarios crear: " CANTIDAD
fi

for i in $(seq 1 $CANTIDAD); do
    if [ $# -ne 3 ]; then
        read -p "Nombre de usuario: " USUARIO
        read -p "Rol (vendedor/tecnico): " ROL
    else
        USUARIO="${1}_${i}"
    fi
    useradd -m $USUARIO
    mkdir -p /home/staff/$ROL/$USUARIO
    echo "Usuario $USUARIO creado con rol $ROL"
done
