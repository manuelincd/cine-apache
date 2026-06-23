<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$msg = '';
$msg_type = 'success';
$editando = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'crear' || $action === 'editar') {
        $titulo = trim($_POST['titulo'] ?? '');
        $genero = trim($_POST['genero'] ?? '');
        $duracion = (int)($_POST['duracion'] ?? 0);
        $imagen = trim($_POST['imagen'] ?? '');

        if ($titulo === '' || $duracion < 1) {
            $msg = 'El título y la duración son obligatorios.';
            $msg_type = 'error';
        } elseif ($action === 'crear') {
            $stmt = $conn->prepare("INSERT INTO peliculas (titulo, genero, duracion, imagen) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssis', $titulo, $genero, $duracion, $imagen);
            $stmt->execute();
            $stmt->close();
            $msg = 'Película añadida correctamente.';
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE peliculas SET titulo=?, genero=?, duracion=?, imagen=? WHERE id=?");
            $stmt->bind_param('ssisi', $titulo, $genero, $duracion, $imagen, $id);
            $stmt->execute();
            $stmt->close();
            $msg = 'Película actualizada.';
        }
    }

    if ($action === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM peliculas WHERE id = $id");
        $msg = 'Película eliminada.';
    }
}

if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $res = $conn->query("SELECT * FROM peliculas WHERE id = $id");
    $editando = $res->fetch_assoc();
}

$peliculas = $conn->query("SELECT * FROM peliculas ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Películas — Admin Cine Sendera</title>
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
            <a href="index.php">Dashboard</a>
            <a href="ventas.php">Ventas</a>
            <a href="peliculas.php" class="active">Películas</a>
            <a href="cupones.php">Cupones</a>
            <a href="personal.php">Personal</a>
            <a href="backup.php">Backup</a>
            <a href="../validar_qr.php">Validar QR</a>
            <a href="../logout.php">Cerrar sesión</a>
        </nav>
    </aside>
    <main class="admin-main">
        <h1 class="page-title"><?= $editando ? 'Editar Película' : 'Películas' ?></h1>
        <p class="page-subtitle"><?= $editando ? 'Modifica los datos de la película' : 'Gestiona el catálogo de películas' ?></p>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>" style="margin-bottom:1.5rem;">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrapper" style="margin-bottom:2rem;">
            <div class="admin-table-header">
                <h3><?= $editando ? 'Editando: ' . htmlspecialchars($editando['titulo']) : 'Nueva película' ?></h3>
                <?php if ($editando): ?>
                <a href="peliculas.php" class="btn btn-secondary btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>
            <form method="POST" style="padding:1.5rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;align-items:end;">
                <input type="hidden" name="action" value="<?= $editando ? 'editar' : 'crear' ?>">
                <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= $editando['id'] ?>">
                <?php endif; ?>
                <div class="form-group" style="margin:0;">
                    <label>Título</label>
                    <input type="text" name="titulo" placeholder="Nombre de la película" required
                           value="<?= htmlspecialchars($editando['titulo'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Género</label>
                    <input type="text" name="genero" placeholder="Ej: Acción, Drama"
                           value="<?= htmlspecialchars($editando['genero'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Duración (min)</label>
                    <input type="number" name="duracion" min="1" placeholder="120" required
                           value="<?= htmlspecialchars($editando['duracion'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Imagen (nombre de archivo)</label>
                    <input type="text" name="imagen" placeholder="pelicula.jpg"
                           value="<?= htmlspecialchars($editando['imagen'] ?? '') ?>">
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <?= $editando ? 'Guardar cambios' : 'Añadir película' ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="admin-table-wrapper">
            <div class="admin-table-header">
                <h3>Catálogo actual</h3>
                <span style="font-size:0.85rem;color:#9d7ec4;"><?= $peliculas->num_rows ?> películas</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Título</th>
                        <th>Género</th>
                        <th>Duración</th>
                        <th>Imagen</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = $peliculas->fetch_assoc()): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><strong><?= htmlspecialchars($p['titulo']) ?></strong></td>
                        <td style="color:#9d7ec4;"><?= htmlspecialchars($p['genero'] ?? '—') ?></td>
                        <td><?= $p['duracion'] ?> min</td>
                        <td style="font-size:0.8rem;color:#5a4270;"><?= htmlspecialchars($p['imagen'] ?? '—') ?></td>
                        <td style="display:flex;gap:0.5rem;">
                            <a href="peliculas.php?editar=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar <?= htmlspecialchars($p['titulo'], ENT_QUOTES) ?>? Se borrarán también sus funciones.');">
                                <input type="hidden" name="action" value="eliminar">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
