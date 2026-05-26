<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$uid = (int)$_SESSION['usuario_id'];

$ventas = $conn->query("
    SELECT v.id, v.total, v.fecha, v.cupon_id,
           p.titulo, p.imagen,
           f.fecha_hora,
           s.nombre AS sala,
           GROUP_CONCAT(CONCAT(a.fila, a.columna) ORDER BY a.fila, a.columna SEPARATOR ', ') AS asientos,
           COUNT(dv.id) AS num_asientos
    FROM ventas v
    JOIN funciones f ON v.funcion_id = f.id
    JOIN peliculas p ON f.pelicula_id = p.id
    JOIN salas s ON f.sala_id = s.id
    JOIN detalle_venta dv ON dv.venta_id = v.id
    JOIN asientos a ON dv.asiento_id = a.id
    WHERE v.usuario_id = $uid
    GROUP BY v.id
    ORDER BY v.fecha DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Tickets — Cine Sendera</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <span class="header-logo">Cine Sendera</span>
    <nav class="header-nav">
        <span style="color:#9d7ec4;font-size:0.9rem;">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
        <a href="cartelera.php">Cartelera</a>
        <a href="logout.php" class="btn btn-secondary btn-sm">Cerrar sesión</a>
    </nav>
</header>
<div class="main-content">
    <div class="container">
        <h1 class="page-title">Mis Tickets</h1>
        <p class="page-subtitle">Historial de todas tus compras</p>

        <?php if ($ventas->num_rows === 0): ?>
        <div class="alert alert-info" style="max-width:480px;">
            Aún no has comprado ningún boleto. <a href="cartelera.php">Ver cartelera</a>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:1rem;max-width:680px;">
            <?php while ($v = $ventas->fetch_assoc()): ?>
            <div class="ticket-list-card">
                <div class="ticket-list-poster">
                    <?php if ($v['imagen'] && file_exists(__DIR__ . '/img/' . $v['imagen'])): ?>
                    <img src="img/<?= htmlspecialchars($v['imagen']) ?>" alt="">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">🎬</div>
                    <?php endif; ?>
                </div>
                <div class="ticket-list-info">
                    <h3><?= htmlspecialchars($v['titulo']) ?></h3>
                    <p class="card-meta"><?= htmlspecialchars($v['sala']) ?> · <?= date('d M Y', strtotime($v['fecha_hora'])) ?></p>
                    <p class="card-meta"><?= date('g:i a', strtotime($v['fecha_hora'])) ?> · <?= $v['num_asientos'] ?> asiento<?= $v['num_asientos'] > 1 ? 's' : '' ?></p>
                    <p class="card-meta">Asientos: <strong style="color:var(--texto);"><?= htmlspecialchars($v['asientos']) ?></strong></p>
                    <p style="margin-top:0.4rem;font-size:0.85rem;color:#9d7ec4;">Comprado el <?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></p>
                </div>
                <div class="ticket-list-actions">
                    <p class="card-precio" style="margin:0 0 0.75rem;">$<?= number_format($v['total'], 2) ?></p>
                    <a href="ticket.php?venta_id=<?= $v['id'] ?>" class="btn btn-primary btn-sm">Ver boleto</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
