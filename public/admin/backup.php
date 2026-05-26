<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$salida = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ejecutar_backup'])) {
    $salida = shell_exec('bash /home/manuelcd/Castillo/cine-sendera/scripts/backup.sh 2>&1');
}

$backups = [];
$backup_dir = '/home/manuelcd/Castillo/cine-sendera/backups/';
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '*.sql');
    if ($files) {
        foreach ($files as $f) {
            $backups[] = [
                'name' => basename($f),
                'size' => round(filesize($f) / 1024, 1) . ' KB',
                'fecha' => date('d/m/Y H:i', filemtime($f)),
            ];
        }
        usort($backups, fn($a, $b) => strcmp($b['name'], $a['name']));
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Backup — Admin Cine Sendera</title>
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
            <a href="personal.php">Personal</a>
            <a href="backup.php" class="active">Backup</a>
            <a href="../validar_qr.php">Validar QR</a>
            <a href="../logout.php">Cerrar sesión</a>
        </nav>
    </aside>
    <main class="admin-main">
        <h1 class="page-title">Backup de base de datos</h1>
        <p class="page-subtitle">Genera y gestiona respaldos del sistema</p>

        <form method="POST" style="margin-bottom:1.5rem;">
            <button type="submit" name="ejecutar_backup" class="btn btn-primary">💾 Ejecutar Backup ahora</button>
        </form>

        <?php if ($salida !== ''): ?>
        <div class="script-output"><?= htmlspecialchars($salida) ?></div>
        <?php endif; ?>

        <div class="backup-list" style="margin-top:2rem;">
            <h3 style="margin-bottom:1rem;color:var(--morado-claro);">Backups existentes</h3>
            <?php if (empty($backups)): ?>
            <p style="color:#9d7ec4;">No hay backups disponibles aún.</p>
            <?php else: ?>
            <?php foreach ($backups as $b): ?>
            <div class="backup-item">
                <span class="name">📄 <?= htmlspecialchars($b['name']) ?></span>
                <span class="size"><?= $b['size'] ?> · <?= $b['fecha'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
