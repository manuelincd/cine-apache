<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$compra = null;

if (isset($_GET['venta_id'])) {
    $vid = (int)$_GET['venta_id'];
    $uid = (int)$_SESSION['usuario_id'];
    $row = $conn->query("
        SELECT v.id, v.total, v.fecha, u.nombre AS usuario_nombre,
               p.titulo AS pelicula, s.nombre AS sala, f.fecha_hora,
               GROUP_CONCAT(CONCAT(a.fila, a.columna) ORDER BY a.fila, a.columna SEPARATOR ', ') AS asientos
        FROM ventas v
        JOIN usuarios u ON v.usuario_id = u.id
        JOIN funciones f ON v.funcion_id = f.id
        JOIN peliculas p ON f.pelicula_id = p.id
        JOIN salas s ON f.sala_id = s.id
        JOIN detalle_venta dv ON dv.venta_id = v.id
        JOIN asientos a ON dv.asiento_id = a.id
        WHERE v.id = $vid AND v.usuario_id = $uid
        GROUP BY v.id
    ")->fetch_assoc();

    if (!$row) {
        header('Location: mis_tickets.php');
        exit;
    }

    $compra = [
        'venta_id'      => $row['id'],
        'usuario_nombre'=> $row['usuario_nombre'],
        'pelicula'      => $row['pelicula'],
        'sala'          => $row['sala'],
        'fecha_hora'    => $row['fecha_hora'],
        'asientos'      => $row['asientos'],
        'total'         => $row['total'],
    ];
} elseif (isset($_SESSION['compra'])) {
    $compra = $_SESSION['compra'];
    unset($_SESSION['compra']);
} else {
    header('Location: mis_tickets.php');
    exit;
}

$qr_file = 'qr_' . $compra['venta_id'] . '.png';
$qr_full_path = __DIR__ . '/img/' . $qr_file;
$qr_path = '';

if (file_exists(__DIR__ . '/lib/phpqrcode/qrlib.php')) {
    require_once __DIR__ . '/lib/phpqrcode/qrlib.php';
    if (!file_exists($qr_full_path)) {
        QRcode::png('VENTA-' . $compra['venta_id'], $qr_full_path, QR_ECLEVEL_M, 6, 2);
    }
    $qr_path = 'img/' . $qr_file;
}

$desde_historial = isset($_GET['venta_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Boleto — Cine Sendera</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <span class="header-logo">Cine Sendera</span>
    <nav class="header-nav">
        <a href="mis_tickets.php">Mis tickets</a>
        <a href="cartelera.php">Cartelera</a>
        <a href="logout.php" class="btn btn-secondary btn-sm">Cerrar sesión</a>
    </nav>
</header>
<div class="main-content">
    <div class="container">
        <div class="ticket-wrapper ticket-print-area">
            <h1 class="page-title" style="text-align:center;margin-bottom:1.5rem;">Tu boleto digital</h1>
            <div class="ticket">
                <div class="ticket-header">
                    <h2>Cine Sendera</h2>
                    <p>Boleto electrónico confirmado</p>
                </div>
                <div class="ticket-body">
                    <div class="ticket-info">
                        <div class="ticket-row">
                            <span class="label">Cliente</span>
                            <span class="value"><?= htmlspecialchars($compra['usuario_nombre']) ?></span>
                        </div>
                        <div class="ticket-row">
                            <span class="label">Película</span>
                            <span class="value"><?= htmlspecialchars($compra['pelicula']) ?></span>
                        </div>
                        <div class="ticket-row">
                            <span class="label">Sala</span>
                            <span class="value"><?= htmlspecialchars($compra['sala']) ?></span>
                        </div>
                        <div class="ticket-row">
                            <span class="label">Fecha y hora</span>
                            <span class="value"><?= date('d M Y H:i', strtotime($compra['fecha_hora'])) ?></span>
                        </div>
                        <div class="ticket-row">
                            <span class="label">Asientos</span>
                            <span class="value"><?= htmlspecialchars($compra['asientos']) ?></span>
                        </div>
                        <div class="ticket-row">
                            <span class="label">Total pagado</span>
                            <span class="value" style="color:var(--rosa-claro);">$<?= number_format($compra['total'], 2) ?> MXN</span>
                        </div>
                    </div>

                    <div class="ticket-divider"></div>

                    <div class="ticket-qr">
                        <?php if ($qr_path): ?>
                        <img src="<?= $qr_path ?>" alt="QR Boleto" width="140" height="140">
                        <?php else: ?>
                        <div style="width:140px;height:140px;background:#fff;margin:0 auto;display:flex;align-items:center;justify-content:center;color:#000;font-size:0.7rem;text-align:center;border-radius:8px;">QR no disponible</div>
                        <?php endif; ?>
                        <p class="ticket-codigo">ID: #<?= str_pad($compra['venta_id'], 6, '0', STR_PAD_LEFT) ?></p>
                    </div>
                </div>
            </div>

            <div class="ticket-actions">
                <button onclick="window.print()" class="btn btn-primary">Imprimir boleto</button>
                <?php if ($desde_historial): ?>
                <a href="mis_tickets.php" class="btn btn-secondary">← Mis tickets</a>
                <?php else: ?>
                <a href="cartelera.php" class="btn btn-secondary">← Volver a cartelera</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
