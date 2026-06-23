<?php
session_start();
require_once 'config/db.php';

$rol = $_SESSION['usuario_rol'] ?? 'visitante';
$nombre = $_SESSION['usuario_nombre'] ?? '';

$res = $conn->query("
    SELECT p.id, p.titulo, p.genero, p.duracion, p.imagen,
           f.id AS funcion_id, f.fecha_hora, f.precio
    FROM peliculas p
    JOIN funciones f ON f.pelicula_id = p.id
    ORDER BY p.id, f.fecha_hora
");

$peliculas = [];
while ($row = $res->fetch_assoc()) {
    $pid = $row['id'];
    if (!isset($peliculas[$pid])) {
        $peliculas[$pid] = [
            'titulo'   => $row['titulo'],
            'genero'   => $row['genero'],
            'duracion' => $row['duracion'],
            'imagen'   => $row['imagen'],
            'precio'   => $row['precio'],
            'funciones'=> [],
        ];
    }
    $peliculas[$pid]['funciones'][] = [
        'id'        => $row['funcion_id'],
        'fecha_hora'=> $row['fecha_hora'],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cartelera — Cine Sendera</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <span class="header-logo">Cine Sendera</span>
    <nav class="header-nav">
        <?php if ($nombre): ?>
            <span style="color:#9d7ec4;font-size:0.9rem;">Hola, <?= htmlspecialchars($nombre) ?></span>
            <?php if ($rol === 'admin'): ?>
                <a href="admin/index.php">Panel Admin</a>
            <?php else: ?>
                <a href="mis_tickets.php">Mis tickets</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-secondary btn-sm">Cerrar sesión</a>
        <?php else: ?>
            <a href="login.php">Iniciar sesión</a>
            <a href="registro.php" class="btn btn-primary btn-sm">Registrarse</a>
        <?php endif; ?>
    </nav>
</header>
<div class="main-content">
    <div class="container">
        <h1 class="page-title">Cartelera</h1>
        <div class="grid-peliculas">
            <?php foreach ($peliculas as $p): ?>
            <div class="card-pelicula">
                <?php if ($p['imagen'] && file_exists(__DIR__ . '/img/' . $p['imagen'])): ?>
                <img src="img/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['titulo']) ?>" class="card-poster">
                <?php else: ?>
                <div class="card-poster-placeholder">🎬</div>
                <?php endif; ?>
                <div class="card-body">
                    <h3 class="card-titulo"><?= htmlspecialchars($p['titulo']) ?></h3>
                    <p class="card-meta"><?= htmlspecialchars($p['genero']) ?> · <?= $p['duracion'] ?> min</p>
                    <p class="card-precio">$<?= number_format($p['precio'], 2) ?> MXN</p>
                    <div class="horarios-grid">
                        <?php foreach ($p['funciones'] as $f): ?>
                            <?php if ($rol === 'cliente' || $rol === 'admin'): ?>
                            <a href="asientos.php?funcion_id=<?= $f['id'] ?>" class="btn-horario">
                                <?= date('g:i a', strtotime($f['fecha_hora'])) ?>
                            </a>
                            <?php else: ?>
                            <a href="login.php" class="btn-horario btn-horario-locked">
                                <?= date('g:i a', strtotime($f['fecha_hora'])) ?>
                            </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($rol === 'visitante'): ?>
                    <p style="font-size:0.78rem;color:#5a4270;margin-top:0.5rem;">Inicia sesión para comprar</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</body>
</html>
