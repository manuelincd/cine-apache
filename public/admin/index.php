<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['metrics'])) {
    $cpu_raw = shell_exec("top -bn1 | grep 'Cpu(s)'");
    preg_match('/(\d+[\.,]\d+)\s*us/', $cpu_raw ?? '', $cpu_m);
    $cpu = isset($cpu_m[1]) ? str_replace(',', '.', $cpu_m[1]) : '0';

    $mem_raw = shell_exec("free -m");
    $mem_lines = explode("\n", trim($mem_raw ?? ''));
    $mem_parts = preg_split('/\s+/', $mem_lines[1] ?? '');
    $mem_total = $mem_parts[1] ?? 1;
    $mem_used = $mem_parts[2] ?? 0;
    $mem_pct = $mem_total > 0 ? round(($mem_used / $mem_total) * 100) : 0;

    $disk_raw = shell_exec("df -h /");
    $disk_lines = explode("\n", trim($disk_raw ?? ''));
    $disk_parts = preg_split('/\s+/', $disk_lines[1] ?? '');
    $disk_use = $disk_parts[4] ?? '0%';
    $disk_free = $disk_parts[3] ?? '0';

    $apache_status = trim(shell_exec("systemctl is-active apache2") ?? 'unknown');
    $mysql_status = trim(shell_exec("docker ps --filter name=cine_mysql --format '{{.Status}}'") ?? '');

    header('Content-Type: application/json');
    echo json_encode([
        'cpu' => $cpu,
        'mem_used' => $mem_used,
        'mem_total' => $mem_total,
        'mem_pct' => $mem_pct,
        'disk_use' => $disk_use,
        'disk_free' => $disk_free,
        'apache' => $apache_status,
        'mysql' => $mysql_status ? 'running' : 'stopped',
    ]);
    exit;
}

if (isset($_GET['watchdog'])) {
    $salida = shell_exec('sudo /bin/bash /home/manuelcd/Castillo/cine-sendera/scripts/watchdog.sh 2>&1');
    header('Content-Type: application/json');
    echo json_encode(['output' => $salida ?: 'Watchdog ejecutado sin salida.']);
    exit;
}

if (isset($_GET['log'])) {
    $log_file = '/var/log/cine_error.log';
    $lineas = file_exists($log_file) ? shell_exec("tail -50 " . escapeshellarg($log_file)) : '';
    header('Content-Type: application/json');
    echo json_encode(['log' => $lineas ?: 'El log está vacío o no existe aún.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Admin Cine Sendera</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header>
    <span class="header-logo">Cine Sendera Admin</span>
    <nav class="header-nav">
        <span style="color:#9d7ec4;font-size:0.85rem;"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
        <a href="../logout.php" class="btn btn-secondary btn-sm">Cerrar sesión</a>
    </nav>
</header>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <p class="sidebar-title">Panel</p>
        <nav class="sidebar-nav">
            <a href="index.php" class="active">Dashboard</a>
            <a href="ventas.php">Ventas</a>
            <a href="peliculas.php">Películas</a>
            <a href="cupones.php">Cupones</a>
            <a href="personal.php">Personal</a>
            <a href="backup.php">Backup</a>
            <a href="../validar_qr.php">Validar QR</a>
            <a href="../logout.php">Cerrar sesión</a>
        </nav>
    </aside>
    <main class="admin-main">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Estado del servidor en tiempo real</p>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">CPU Usage</div>
                <div class="metric-value" id="cpu-val">—</div>
                <div class="metric-bar"><div class="metric-bar-fill" id="cpu-bar" style="width:0%"></div></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">RAM</div>
                <div class="metric-value" id="mem-val">—</div>
                <div class="metric-bar"><div class="metric-bar-fill" id="mem-bar" style="width:0%"></div></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Disco /</div>
                <div class="metric-value" id="disk-val">—</div>
                <div class="metric-bar"><div class="metric-bar-fill" id="disk-bar" style="width:0%"></div></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Apache</div>
                <div class="metric-value metric-status-ok" id="apache-val">—</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">MySQL (Docker)</div>
                <div class="metric-value metric-status-ok" id="mysql-val">—</div>
            </div>
        </div>

        <div style="margin-bottom:2rem;">
            <button class="btn btn-secondary" onclick="runWatchdog()" id="btn-watchdog">Ejecutar Watchdog</button>
            <span id="watchdog-status" style="margin-left:1rem;font-size:0.85rem;color:#9d7ec4;"></span>
        </div>

        <div class="admin-table-wrapper">
            <div class="admin-table-header">
                <h3>Log del sistema</h3>
                <button class="btn btn-secondary btn-sm" onclick="loadLog()">Actualizar</button>
            </div>
            <div class="script-output" id="log-output" style="border-radius:0;border:none;min-height:120px;max-height:340px;overflow-y:auto;">Cargando...</div>
        </div>
    </main>
</div>
<script>
function loadMetrics() {
    fetch('index.php?metrics=1')
        .then(function(r){ return r.json(); })
        .then(function(d) {
            document.getElementById('cpu-val').textContent = d.cpu + '%';
            document.getElementById('cpu-bar').style.width = Math.min(d.cpu, 100) + '%';

            document.getElementById('mem-val').textContent = d.mem_used + ' / ' + d.mem_total + ' MB';
            document.getElementById('mem-bar').style.width = d.mem_pct + '%';

            document.getElementById('disk-val').textContent = d.disk_use + ' usado · ' + d.disk_free + ' libre';
            var diskPct = parseInt(d.disk_use) || 0;
            document.getElementById('disk-bar').style.width = diskPct + '%';

            var apacheEl = document.getElementById('apache-val');
            apacheEl.textContent = d.apache;
            apacheEl.className = 'metric-value ' + (d.apache === 'active' ? 'metric-status-ok' : 'metric-status-err');

            var mysqlEl = document.getElementById('mysql-val');
            mysqlEl.textContent = d.mysql;
            mysqlEl.className = 'metric-value ' + (d.mysql === 'running' ? 'metric-status-ok' : 'metric-status-err');
        });
}

function loadLog() {
    fetch('index.php?log=1')
        .then(function(r){ return r.json(); })
        .then(function(d) {
            var el = document.getElementById('log-output');
            el.textContent = d.log;
            el.scrollTop = el.scrollHeight;
        });
}

function runWatchdog() {
    var btn = document.getElementById('btn-watchdog');
    var status = document.getElementById('watchdog-status');
    btn.disabled = true;
    status.textContent = 'Ejecutando...';
    fetch('index.php?watchdog=1')
        .then(function(r){ return r.json(); })
        .then(function(d) {
            status.textContent = 'Completado';
            setTimeout(function(){ status.textContent = ''; }, 3000);
            btn.disabled = false;
            loadLog();
        });
}

loadMetrics();
loadLog();
setInterval(loadMetrics, 5000);
setInterval(loadLog, 15000);
</script>
</body>
</html>
