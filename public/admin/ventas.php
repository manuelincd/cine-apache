<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$ventas = $conn->query("
    SELECT v.id, v.total, v.fecha, u.nombre AS cliente, p.titulo AS pelicula,
           f.fecha_hora AS funcion,
           GROUP_CONCAT(CONCAT(a.fila, a.columna) ORDER BY a.fila, a.columna SEPARATOR ', ') AS asientos,
           c.codigo AS cupon
    FROM ventas v
    JOIN usuarios u ON v.usuario_id = u.id
    JOIN funciones f ON v.funcion_id = f.id
    JOIN peliculas p ON f.pelicula_id = p.id
    JOIN detalle_venta dv ON dv.venta_id = v.id
    JOIN asientos a ON dv.asiento_id = a.id
    LEFT JOIN cupones c ON v.cupon_id = c.id
    GROUP BY v.id
    ORDER BY v.fecha DESC
");

$hoy = $conn->query("SELECT COUNT(*) AS cnt, SUM(total) AS ingresos FROM ventas WHERE DATE(fecha) = CURDATE()")->fetch_assoc();
$mas_vendida = $conn->query("
    SELECT p.titulo, COUNT(dv.id) AS boletos
    FROM detalle_venta dv
    JOIN ventas v ON dv.venta_id = v.id
    JOIN funciones f ON v.funcion_id = f.id
    JOIN peliculas p ON f.pelicula_id = p.id
    GROUP BY f.pelicula_id
    ORDER BY boletos DESC
    LIMIT 1
")->fetch_assoc();
$ingresos_total = $conn->query("SELECT SUM(total) AS total FROM ventas")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventas — Admin Cine Sendera</title>
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
            <a href="ventas.php" class="active">Ventas</a>
            <a href="peliculas.php">Películas</a>
            <a href="cupones.php">Cupones</a>
            <a href="personal.php">Personal</a>
            <a href="backup.php">Backup</a>
            <a href="../validar_qr.php">Validar QR</a>
            <a href="../logout.php">Cerrar sesión</a>
        </nav>
    </aside>
    <main class="admin-main">
        <h1 class="page-title">Ventas</h1>
        <p class="page-subtitle">Historial y resumen de ingresos</p>

        <div class="summary-cards">
            <div class="summary-card">
                <div class="num"><?= $hoy['cnt'] ?? 0 ?></div>
                <div class="lbl">Ventas hoy</div>
            </div>
            <div class="summary-card">
                <div class="num">$<?= number_format($hoy['ingresos'] ?? 0, 0) ?></div>
                <div class="lbl">Ingresos hoy</div>
            </div>
            <div class="summary-card">
                <div class="num" style="font-size:1.1rem;"><?= htmlspecialchars($mas_vendida['titulo'] ?? '—') ?></div>
                <div class="lbl">Película más vendida</div>
            </div>
            <div class="summary-card">
                <div class="num">$<?= number_format($ingresos_total['total'] ?? 0, 0) ?></div>
                <div class="lbl">Ingresos totales</div>
            </div>
        </div>

        <div class="admin-table-wrapper">
            <div class="admin-table-header">
                <h3>Todas las ventas</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Película</th>
                        <th>Función</th>
                        <th>Asientos</th>
                        <th>Total</th>
                        <th>Cupón</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($v = $ventas->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= str_pad($v['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($v['cliente']) ?></td>
                        <td><?= htmlspecialchars($v['pelicula']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($v['funcion'])) ?></td>
                        <td><?= htmlspecialchars($v['asientos']) ?></td>
                        <td>$<?= number_format($v['total'], 2) ?></td>
                        <td><?= $v['cupon'] ? '<span class="badge badge-ok">' . htmlspecialchars($v['cupon']) . '</span>' : '—' ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
