<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$es_movil = preg_match('/Mobile|Android|iPhone|iPad/i', $ua);

$resultado = null;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajax_venta_id'])) {
        $vid = (int)$_POST['ajax_venta_id'];
        $data = $conn->query("
            SELECT v.id, v.total, v.fecha, u.nombre AS cliente, p.titulo AS pelicula,
                   GROUP_CONCAT(CONCAT(a.fila, a.columna) ORDER BY a.fila, a.columna SEPARATOR ', ') AS asientos
            FROM ventas v
            JOIN usuarios u ON v.usuario_id = u.id
            JOIN funciones f ON v.funcion_id = f.id
            JOIN peliculas p ON f.pelicula_id = p.id
            JOIN detalle_venta dv ON dv.venta_id = v.id
            JOIN asientos a ON dv.asiento_id = a.id
            WHERE v.id = $vid
            GROUP BY v.id
        ")->fetch_assoc();

        header('Content-Type: application/json');
        if ($data) {
            echo json_encode(['ok' => true, 'data' => $data]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Venta no encontrada']);
        }
        exit;
    }

    $venta_id = (int)($_POST['venta_id'] ?? 0);
    if ($venta_id) {
        $resultado = $conn->query("
            SELECT v.id, v.total, v.fecha, u.nombre AS cliente, p.titulo AS pelicula,
                   GROUP_CONCAT(CONCAT(a.fila, a.columna) ORDER BY a.fila, a.columna SEPARATOR ', ') AS asientos
            FROM ventas v
            JOIN usuarios u ON v.usuario_id = u.id
            JOIN funciones f ON v.funcion_id = f.id
            JOIN peliculas p ON f.pelicula_id = p.id
            JOIN detalle_venta dv ON dv.venta_id = v.id
            JOIN asientos a ON dv.asiento_id = a.id
            WHERE v.id = $venta_id
            GROUP BY v.id
        ")->fetch_assoc();
        if (!$resultado) {
            $error_msg = 'No se encontró ninguna venta con ese ID.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Validar QR — Cine Sendera</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <span class="header-logo">Cine Sendera Admin</span>
    <nav class="header-nav">
        <span style="color:#9d7ec4;font-size:0.85rem;"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
        <a href="logout.php" class="btn btn-secondary btn-sm">Cerrar sesión</a>
    </nav>
</header>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <p class="sidebar-title">Panel</p>
        <nav class="sidebar-nav">
            <a href="admin/index.php">Dashboard</a>
            <a href="admin/ventas.php">Ventas</a>
            <a href="admin/personal.php">Personal</a>
            <a href="admin/backup.php">Backup</a>
            <a href="validar_qr.php" class="active">Validar QR</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </aside>
    <main class="admin-main">
        <h1 class="page-title">Validar boleto</h1>
        <p class="page-subtitle">Escanea el QR o ingresa el ID de venta</p>

        <?php if ($es_movil): ?>
        <div class="validar-tabs">
            <button class="active" onclick="showTab('camara',this)">Camara QR</button>
            <button onclick="showTab('manual',this)">Manual</button>
        </div>

        <div id="tab-camara">
            <div class="qr-scanner-box">
                <video id="video" autoplay playsinline></video>
                <canvas id="canvas" style="display:none;"></canvas>
            </div>
            <p id="scan-status" style="text-align:center;margin-top:1rem;color:#9d7ec4;">Iniciando camara...</p>
            <div id="scan-result" class="qr-result" style="display:none;"></div>
        </div>

        <div id="tab-manual" style="display:none;">
        <?php else: ?>
        <div id="tab-manual">
        <?php endif; ?>
            <?php if ($error_msg): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>
            <form method="POST" class="form-wrapper" style="margin:0;max-width:400px;">
                <div class="form-group">
                    <label>ID de venta</label>
                    <input type="number" name="venta_id" placeholder="Ej: 123" min="1" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Buscar boleto</button>
            </form>

            <?php if ($resultado): ?>
            <div class="qr-result" style="margin-top:1.5rem;max-width:400px;">
                <div class="ticket-row"><span class="label">Cliente:</span> <strong><?= htmlspecialchars($resultado['cliente']) ?></strong></div>
                <div class="ticket-row"><span class="label">Pelicula:</span> <strong><?= htmlspecialchars($resultado['pelicula']) ?></strong></div>
                <div class="ticket-row"><span class="label">Asientos:</span> <strong><?= htmlspecialchars($resultado['asientos']) ?></strong></div>
                <div class="ticket-row"><span class="label">Total:</span> <strong>$<?= number_format($resultado['total'], 2) ?></strong></div>
                <div class="ticket-row"><span class="label">Fecha:</span> <strong><?= $resultado['fecha'] ?></strong></div>
                <div class="alert alert-success" style="margin-top:1rem;">Boleto valido</div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php if ($es_movil): ?>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
function showTab(tab, btn) {
    document.getElementById('tab-camara').style.display = tab === 'camara' ? 'block' : 'none';
    document.getElementById('tab-manual').style.display = tab === 'manual' ? 'block' : 'none';
    document.querySelectorAll('.validar-tabs button').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');
}

var video = document.getElementById('video');
var canvas = document.getElementById('canvas');
var ctx = canvas.getContext('2d');
var scanning = true;

navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    .then(function(stream) {
        video.srcObject = stream;
        video.play();
        document.getElementById('scan-status').textContent = 'Apunta la camara al codigo QR';
        requestAnimationFrame(tick);
    })
    .catch(function() {
        document.getElementById('scan-status').textContent = 'No se pudo acceder a la camara.';
    });

function tick() {
    if (!scanning) return;
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.height = video.videoHeight;
        canvas.width = video.videoWidth;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        var code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code) {
            var match = code.data.match(/VENTA-(\d+)/);
            if (match) {
                scanning = false;
                document.getElementById('scan-status').textContent = 'QR detectado: #' + match[1];
                fetch('validar_qr.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'ajax_venta_id=' + match[1]
                })
                .then(function(r){ return r.json(); })
                .then(function(d) {
                    var box = document.getElementById('scan-result');
                    box.style.display = 'block';
                    if (d.ok) {
                        box.innerHTML = '<div class="ticket-row"><span class="label">Cliente:</span> <strong>' + d.data.cliente + '</strong></div>'
                            + '<div class="ticket-row"><span class="label">Pelicula:</span> <strong>' + d.data.pelicula + '</strong></div>'
                            + '<div class="ticket-row"><span class="label">Asientos:</span> <strong>' + d.data.asientos + '</strong></div>'
                            + '<div class="ticket-row"><span class="label">Total:</span> <strong>$' + parseFloat(d.data.total).toFixed(2) + '</strong></div>'
                            + '<div class="alert alert-success" style="margin-top:1rem;">Boleto valido</div>'
                            + '<button onclick="reiniciarScan()" class="btn btn-secondary" style="margin-top:1rem;width:100%;">Escanear otro</button>';
                    } else {
                        box.innerHTML = '<div class="alert alert-error">' + d.msg + '</div>'
                            + '<button onclick="reiniciarScan()" class="btn btn-secondary" style="margin-top:1rem;width:100%;">Reintentar</button>';
                    }
                });
            }
        }
    }
    requestAnimationFrame(tick);
}

function reiniciarScan() {
    scanning = true;
    document.getElementById('scan-result').style.display = 'none';
    document.getElementById('scan-status').textContent = 'Apunta la camara al codigo QR';
}
</script>
<?php endif; ?>
</body>
</html>
