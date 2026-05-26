<?php
session_start();

if (isset($_SESSION['usuario_rol'])) {
    if ($_SESSION['usuario_rol'] === 'admin') {
        header('Location: admin/index.php');
        exit;
    }
    if ($_SESSION['usuario_rol'] === 'cliente') {
        header('Location: cartelera.php');
        exit;
    }
}

header('Location: cartelera.php');
exit;
