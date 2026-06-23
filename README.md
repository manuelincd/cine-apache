# Cine Sendera

Sistema de venta de boletos para cine hecho en PHP con MySQL. Permite a los usuarios ver la cartelera, seleccionar asientos, aplicar cupones de descuento y recibir su ticket con código QR.

## Requisitos

- Apache con PHP 8+
- Docker (para la base de datos)

## Instalación

Levanta la base de datos:

```bash
docker compose up -d
```

Esto crea el contenedor MySQL en el puerto 3307 e inicializa la base de datos con tablas y datos de prueba.

Apunta Apache al directorio `public/` como raíz del sitio.

## Credenciales por defecto

**Base de datos**
- Host: `127.0.0.1:3307`
- Base de datos: `cine_sendera`
- Usuario: `cine_user`
- Contraseña: `cine_pass`

**Admin**
- Email: `admin@sendera.com`
- Contraseña: `password`

## Estructura

```
public/         archivos del sitio (PHP, CSS, imágenes)
public/admin/   panel de administración
db/init.sql     esquema y datos iniciales
scripts/        watchdog, backups, gestión de personal
```

## Panel de administración

Accesible en `/admin/` con rol admin. Desde ahí se puede ver ventas, gestionar películas, cupones, personal del cine y hacer backups de la base de datos.
