<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$salida = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['usuario'] ?? '');
    $rol = in_array($_POST['rol'] ?? '', ['vendedor', 'tecnico']) ? $_POST['rol'] : 'vendedor';
    $cantidad = max(1, min(10, (int)($_POST['cantidad'] ?? 1)));

    if ($usuario) {
        $salida = shell_exec("bash /home/manuelcd/Castillo/cine-sendera/scripts/staff.sh " . escapeshellarg($usuario) . " " . escapeshellarg($rol) . " " . escapeshellarg($cantidad) . " 2>&1");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Personal — Admin Cine Sendera</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header>
    <span class="header-logo">Cine Sendera Admin</span>
    <nav class="header-nav">
        <a href="../logout.php" class="btn btn-secondary btn-sm">Cerrar sesión</a>
    </nav>
</header>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <p class="sidebar-title">Panel</p>
        <nav class="sidebar-nav">
            <a href="index.php">Dashboard</a>
            <a href="ventas.php">Ventas</a>
            <a href="personal.php" class="active">Personal</a>
            <a href="backup.php">Backup</a>
            <a href="../validar_qr.php">Validar QR</a>
            <a href="../logout.php">Cerrar sesión</a>
        </nav>
    </aside>
    <main class="admin-main">
        <h1 class="page-title">Gestión de Personal</h1>
        <p class="page-subtitle">Crear usuarios del sistema para el cine</p>

        <div style="max-width:500px;">
            <div class="compra-resumen">
                <h3 class="resumen-titulo">Crear usuarios del sistema</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Nombre de usuario base</label>
                        <input type="text" name="usuario" placeholder="vendedor01" pattern="[a-zA-Z0-9_]+" required>
                    </div>
                    <div class="form-group">
                        <label>Rol</label>
                        <select name="rol">
                            <option value="vendedor">Vendedor</option>
                            <option value="tecnico">Técnico</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cantidad a crear (1-10)</label>
                        <input type="number" name="cantidad" min="1" max="10" value="1">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Crear usuarios</button>
                </form>
            </div>
        </div>

        <?php if ($salida !== ''): ?>
        <div class="script-output" style="margin-top:1.5rem;"><?= htmlspecialchars($salida) ?></div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
