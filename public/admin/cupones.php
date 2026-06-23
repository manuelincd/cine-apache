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

    if ($action === 'crear') {
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $descuento = (int)($_POST['descuento_pct'] ?? 0);
        $usos_max = (int)($_POST['usos_max'] ?? 1);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $hora_limite = trim($_POST['hora_limite'] ?? '') ?: null;

        if ($codigo === '' || $descuento < 1 || $descuento > 100 || $usos_max < 1) {
            $msg = 'Datos inválidos. Revisa el formulario.';
            $msg_type = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO cupones (codigo, descuento_pct, activo, usos_max, usos_actual, hora_limite) VALUES (?, ?, ?, ?, 0, ?)");
            $stmt->bind_param('siiis', $codigo, $descuento, $activo, $usos_max, $hora_limite);
            if ($stmt->execute()) {
                $msg = 'Cupón creado correctamente.';
            } else {
                $msg = 'Error: el código ya existe o hubo un problema.';
                $msg_type = 'error';
            }
            $stmt->close();
        }
    }

    if ($action === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
        $descuento = (int)($_POST['descuento_pct'] ?? 0);
        $usos_max = (int)($_POST['usos_max'] ?? 1);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $hora_limite = trim($_POST['hora_limite'] ?? '') ?: null;

        if ($codigo === '' || $descuento < 1 || $descuento > 100 || $usos_max < 1) {
            $msg = 'Datos inválidos. Revisa el formulario.';
            $msg_type = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE cupones SET codigo=?, descuento_pct=?, activo=?, usos_max=?, hora_limite=? WHERE id=?");
            $stmt->bind_param('siiisi', $codigo, $descuento, $activo, $usos_max, $hora_limite, $id);
            $stmt->execute();
            $stmt->close();
            $msg = 'Cupón actualizado.';
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("UPDATE cupones SET activo = NOT activo WHERE id = $id");
        $msg = 'Estado actualizado.';
    }

    if ($action === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM cupones WHERE id = $id");
        $msg = 'Cupón eliminado.';
    }
}

if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $res = $conn->query("SELECT * FROM cupones WHERE id = $id");
    $editando = $res->fetch_assoc();
}

$cupones = $conn->query("SELECT * FROM cupones ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cupones — Admin Cine Sendera</title>
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
            <a href="peliculas.php">Películas</a>
            <a href="cupones.php" class="active">Cupones</a>
            <a href="personal.php">Personal</a>
            <a href="backup.php">Backup</a>
            <a href="../validar_qr.php">Validar QR</a>
            <a href="../logout.php">Cerrar sesión</a>
        </nav>
    </aside>
    <main class="admin-main">
        <h1 class="page-title"><?= $editando ? 'Editar Cupón' : 'Cupones' ?></h1>
        <p class="page-subtitle"><?= $editando ? 'Modifica los datos del cupón' : 'Gestiona los códigos de descuento' ?></p>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>" style="margin-bottom:1.5rem;">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrapper" style="margin-bottom:2rem;">
            <div class="admin-table-header">
                <h3><?= $editando ? 'Editando: ' . htmlspecialchars($editando['codigo']) : 'Nuevo cupón' ?></h3>
                <?php if ($editando): ?>
                <a href="cupones.php" class="btn btn-secondary btn-sm">Cancelar</a>
                <?php endif; ?>
            </div>
            <form method="POST" style="padding:1.5rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;align-items:end;">
                <input type="hidden" name="action" value="<?= $editando ? 'editar' : 'crear' ?>">
                <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= $editando['id'] ?>">
                <?php endif; ?>
                <div class="form-group" style="margin:0;">
                    <label>Código</label>
                    <input type="text" name="codigo" placeholder="EJ: VERANO20" required maxlength="20"
                           value="<?= htmlspecialchars($editando['codigo'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Descuento (%)</label>
                    <input type="number" name="descuento_pct" min="1" max="100" placeholder="10" required
                           value="<?= htmlspecialchars($editando['descuento_pct'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Usos máximos</label>
                    <input type="number" name="usos_max" min="1" placeholder="100" required
                           value="<?= htmlspecialchars($editando['usos_max'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Hora límite (opcional)</label>
                    <input type="time" name="hora_limite" title="Solo para cupones con restricción horaria"
                           value="<?= $editando && $editando['hora_limite'] ? substr($editando['hora_limite'], 0, 5) : '' ?>">
                </div>
                <div class="form-group" style="margin:0;display:flex;align-items:center;gap:0.5rem;padding-top:1.4rem;">
                    <input type="checkbox" name="activo" id="activo_nuevo" style="width:auto;"
                           <?= !$editando || $editando['activo'] ? 'checked' : '' ?>>
                    <label for="activo_nuevo" style="margin:0;cursor:pointer;">Activo</label>
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <?= $editando ? 'Guardar cambios' : 'Crear cupón' ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="admin-table-wrapper">
            <div class="admin-table-header">
                <h3>Todos los cupones</h3>
                <span style="font-size:0.85rem;color:#9d7ec4;"><?= $cupones->num_rows ?> registros</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Descuento</th>
                        <th>Hora límite</th>
                        <th>Usos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = $cupones->fetch_assoc()): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><strong style="color:var(--morado-claro);letter-spacing:1px;"><?= htmlspecialchars($c['codigo']) ?></strong></td>
                        <td><?= $c['descuento_pct'] ?>%</td>
                        <td style="color:#9d7ec4;">
                            <?= $c['hora_limite'] ? 'Antes de las ' . date('g:i a', strtotime($c['hora_limite'])) : '—' ?>
                        </td>
                        <td><?= $c['usos_actual'] ?> / <?= $c['usos_max'] ?></td>
                        <td>
                            <span class="badge <?= $c['activo'] ? 'badge-ok' : 'badge-err' ?>">
                                <?= $c['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td style="display:flex;gap:0.5rem;">
                            <a href="cupones.php?editar=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <?= $c['activo'] ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar el cupón <?= htmlspecialchars($c['codigo'], ENT_QUOTES) ?>?');">
                                <input type="hidden" name="action" value="eliminar">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
