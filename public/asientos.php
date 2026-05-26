<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['cliente', 'admin'])) {
    header('Location: login.php');
    exit;
}

$funcion_id = (int)($_GET['funcion_id'] ?? 0);
if (!$funcion_id) {
    header('Location: cartelera.php');
    exit;
}

$funcion = $conn->query("
    SELECT f.id, f.fecha_hora, f.precio, p.titulo, s.nombre AS sala
    FROM funciones f
    JOIN peliculas p ON f.pelicula_id = p.id
    JOIN salas s ON f.sala_id = s.id
    WHERE f.id = $funcion_id
")->fetch_assoc();

if (!$funcion) {
    header('Location: cartelera.php');
    exit;
}

$asientos_res = $conn->query("
    SELECT id, fila, columna, estado
    FROM asientos
    WHERE funcion_id = $funcion_id
    ORDER BY fila ASC, columna ASC
");

$asientos = [];
while ($a = $asientos_res->fetch_assoc()) {
    $asientos[$a['fila']][$a['columna']] = $a;
}

$filas = ['A','B','C','D','E','F','G','H'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Seleccionar Asientos — Cine Sendera</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <span class="header-logo">Cine Sendera</span>
    <nav class="header-nav">
        <a href="cartelera.php">Cartelera</a>
        <a href="logout.php" class="btn btn-secondary btn-sm">Cerrar sesión</a>
    </nav>
</header>
<div class="main-content">
    <div class="container">
        <h1 class="page-title"><?= htmlspecialchars($funcion['titulo']) ?></h1>
        <p class="page-subtitle"><?= htmlspecialchars($funcion['sala']) ?> · <?= date('d M Y H:i', strtotime($funcion['fecha_hora'])) ?> · $<?= number_format($funcion['precio'], 2) ?> MXN por asiento</p>

        <div class="sala-wrapper">
            <div class="pantalla">PANTALLA</div>

            <form id="form-asientos" action="compra.php" method="POST">
                <input type="hidden" name="funcion_id" value="<?= $funcion_id ?>">
                <div id="asientos-seleccionados"></div>

                <div class="asientos-grid">
                    <?php foreach ($filas as $fila): ?>
                        <div class="asiento-fila-label"><?= $fila ?></div>
                        <?php for ($col = 1; $col <= 10; $col++): ?>
                            <?php
                            $a = $asientos[$fila][$col] ?? null;
                            $estado = $a ? $a['estado'] : 'libre';
                            $id = $a ? $a['id'] : 0;
                            ?>
                            <div
                                class="asiento <?= $estado ?>"
                                data-id="<?= $id ?>"
                                data-fila="<?= $fila ?>"
                                data-col="<?= $col ?>"
                                data-estado="<?= $estado ?>"
                                title="<?= $fila ?><?= $col ?>"
                                <?= $estado === 'ocupado' ? '' : 'onclick="toggleAsiento(this)"' ?>
                            ></div>
                        <?php endfor; ?>
                    <?php endforeach; ?>
                </div>
            </form>

            <div class="leyenda">
                <div class="leyenda-item">
                    <div class="leyenda-color libre"></div>
                    Libre
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color ocupado"></div>
                    Ocupado
                </div>
                <div class="leyenda-item">
                    <div class="leyenda-color seleccionado"></div>
                    Seleccionado
                </div>
            </div>

            <div style="text-align:center;margin-top:1rem;">
                <p id="contador" style="color:#9d7ec4;margin-bottom:1rem;font-size:0.9rem;">Ningún asiento seleccionado</p>
                <button type="button" class="btn btn-primary" onclick="continuar()" style="min-width:200px;">Continuar →</button>
            </div>
        </div>
    </div>
</div>
<script>
var seleccionados = {};

function toggleAsiento(el) {
    var id = el.dataset.id;
    var fila = el.dataset.fila;
    var col = el.dataset.col;
    if (el.classList.contains('seleccionado')) {
        el.classList.remove('seleccionado');
        el.classList.add('libre');
        delete seleccionados[id];
    } else {
        el.classList.remove('libre');
        el.classList.add('seleccionado');
        seleccionados[id] = fila + col;
    }
    var count = Object.keys(seleccionados).length;
    document.getElementById('contador').textContent = count === 0
        ? 'Ningún asiento seleccionado'
        : count + ' asiento' + (count > 1 ? 's' : '') + ' seleccionado' + (count > 1 ? 's' : '');
}

function continuar() {
    var ids = Object.keys(seleccionados);
    if (ids.length === 0) {
        alert('Selecciona al menos un asiento para continuar.');
        return;
    }
    var form = document.getElementById('form-asientos');
    var cont = document.getElementById('asientos-seleccionados');
    cont.innerHTML = '';
    ids.forEach(function(id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'asiento_ids[]';
        input.value = id;
        cont.appendChild(input);
    });
    form.submit();
}
</script>
</body>
</html>
