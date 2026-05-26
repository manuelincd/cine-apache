<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['cliente', 'admin'])) {
    header('Location: login.php');
    exit;
}

$funcion_id = (int)($_POST['funcion_id'] ?? 0);
$asiento_ids_raw = $_POST['asiento_ids'] ?? [];

if (!$funcion_id || empty($asiento_ids_raw)) {
    header('Location: cartelera.php');
    exit;
}

$asiento_ids = array_map('intval', $asiento_ids_raw);

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

$placeholders = implode(',', $asiento_ids);
$asientos_res = $conn->query("SELECT id, fila, columna, estado FROM asientos WHERE id IN ($placeholders) AND funcion_id = $funcion_id");
$asientos = [];
while ($a = $asientos_res->fetch_assoc()) {
    if ($a['estado'] === 'libre') {
        $asientos[] = $a;
    }
}

if (empty($asientos)) {
    header('Location: cartelera.php');
    exit;
}

$precio_unitario = $funcion['precio'];
$subtotal = $precio_unitario * count($asientos);
$descuento = 0;
$cupon_id = null;
$cupon_msg = '';
$cupon_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aplicar_cupon'])) {
    $codigo = strtoupper(trim($_POST['cupon'] ?? ''));
    if ($codigo) {
        $stmt = $conn->prepare("SELECT id, descuento_pct, usos_actual, usos_max FROM cupones WHERE codigo = ? AND activo = 1");
        $stmt->bind_param('s', $codigo);
        $stmt->execute();
        $cup = $stmt->get_result()->fetch_assoc();
        if ($cup && $cup['usos_actual'] < $cup['usos_max']) {
            $hora_funcion = (int)date('H', strtotime($funcion['fecha_hora']));
            if ($codigo === 'MATINE50' && $hora_funcion >= 12) {
                $cupon_msg = 'El cupón MATINE50 solo aplica en funciones antes de las 12:00 pm.';
                unset($_SESSION['cupon_aplicado']);
            } else {
                $descuento = $subtotal * ($cup['descuento_pct'] / 100);
                $cupon_id = $cup['id'];
                $cupon_msg = 'Cupón aplicado: ' . $cup['descuento_pct'] . '% de descuento';
                $cupon_ok = true;
                $_SESSION['cupon_aplicado'] = ['id' => $cup['id'], 'descuento' => $descuento, 'codigo' => $codigo];
            }
        } else {
            $cupon_msg = 'Cupón inválido o agotado.';
            unset($_SESSION['cupon_aplicado']);
        }
    }
}

if (isset($_SESSION['cupon_aplicado']) && !isset($_POST['aplicar_cupon'])) {
    $descuento = $_SESSION['cupon_aplicado']['descuento'];
    $cupon_id = $_SESSION['cupon_aplicado']['id'];
    $cupon_ok = true;
}

$total = $subtotal - $descuento;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_compra'])) {
    $usuario_id = $_SESSION['usuario_id'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO ventas (usuario_id, funcion_id, total, cupon_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iidd', $usuario_id, $funcion_id, $total, $cupon_id);
        $stmt->execute();
        $venta_id = $conn->insert_id;

        foreach ($asientos as $a) {
            $asiento_id = $a['id'];
            $conn->query("INSERT INTO detalle_venta (venta_id, asiento_id) VALUES ($venta_id, $asiento_id)");
            $conn->query("UPDATE asientos SET estado = 'ocupado' WHERE id = $asiento_id");
        }

        if ($cupon_id) {
            $conn->query("UPDATE cupones SET usos_actual = usos_actual + 1 WHERE id = $cupon_id");
        }

        $conn->commit();

        $asientos_str = implode(', ', array_map(fn($a) => $a['fila'] . $a['columna'], $asientos));
        $_SESSION['compra'] = [
            'venta_id' => $venta_id,
            'pelicula' => $funcion['titulo'],
            'sala' => $funcion['sala'],
            'fecha_hora' => $funcion['fecha_hora'],
            'asientos' => $asientos_str,
            'total' => $total,
            'usuario_nombre' => $_SESSION['usuario_nombre'],
        ];
        unset($_SESSION['cupon_aplicado']);

        header('Location: ticket.php');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirmar compra — Cine Sendera</title>
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
        <h1 class="page-title">Confirmar compra</h1>
        <p class="page-subtitle">Revisa tu selección antes de pagar</p>

        <div class="compra-wrapper">
            <div>
                <div class="compra-resumen">
                    <h3 class="resumen-titulo">Resumen</h3>
                    <div class="resumen-fila">
                        <span>Película</span>
                        <span><?= htmlspecialchars($funcion['titulo']) ?></span>
                    </div>
                    <div class="resumen-fila">
                        <span>Sala</span>
                        <span><?= htmlspecialchars($funcion['sala']) ?></span>
                    </div>
                    <div class="resumen-fila">
                        <span>Función</span>
                        <span><?= date('d M Y H:i', strtotime($funcion['fecha_hora'])) ?></span>
                    </div>
                    <div class="resumen-fila">
                        <span>Asientos</span>
                        <span><?= implode(', ', array_map(fn($a) => $a['fila'] . $a['columna'], $asientos)) ?></span>
                    </div>
                    <div class="resumen-fila">
                        <span>Precio unitario</span>
                        <span>$<?= number_format($precio_unitario, 2) ?></span>
                    </div>
                    <div class="resumen-fila">
                        <span>Subtotal (<?= count($asientos) ?> asiento<?= count($asientos) > 1 ? 's' : '' ?>)</span>
                        <span>$<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <?php if ($descuento > 0): ?>
                    <div class="resumen-fila" style="color:#4ade80;">
                        <span>Descuento cupón</span>
                        <span>-$<?= number_format($descuento, 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="resumen-fila">
                        <span class="resumen-total">Total</span>
                        <span class="resumen-total">$<?= number_format($total, 2) ?> MXN</span>
                    </div>
                </div>
            </div>

            <div>
                <div class="compra-resumen">
                    <h3 class="resumen-titulo">Código de descuento</h3>
                    <form method="POST">
                        <?php foreach ($asientos as $a): ?>
                        <input type="hidden" name="asiento_ids[]" value="<?= $a['id'] ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="funcion_id" value="<?= $funcion_id ?>">
                        <div class="cupon-row">
                            <div class="form-group" style="margin:0;flex:1;">
                                <input type="text" name="cupon" placeholder="SENDERA10" value="<?= isset($_SESSION['cupon_aplicado']) ? htmlspecialchars($_SESSION['cupon_aplicado']['codigo']) : '' ?>">
                            </div>
                            <button type="submit" name="aplicar_cupon" class="btn btn-secondary">Aplicar</button>
                        </div>
                        <?php if ($cupon_msg): ?>
                        <div class="alert <?= $cupon_ok ? 'alert-success' : 'alert-error' ?>" style="margin-top:0.5rem;"><?= htmlspecialchars($cupon_msg) ?></div>
                        <?php endif; ?>
                    </form>

                    <form method="POST" style="margin-top:1.5rem;">
                        <?php foreach ($asientos as $a): ?>
                        <input type="hidden" name="asiento_ids[]" value="<?= $a['id'] ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="funcion_id" value="<?= $funcion_id ?>">
                        <button type="submit" name="confirmar_compra" class="btn btn-primary" style="width:100%;">Confirmar compra — $<?= number_format($total, 2) ?></button>
                    </form>
                    <a href="asientos.php?funcion_id=<?= $funcion_id ?>" class="btn btn-secondary" style="width:100%;margin-top:0.75rem;text-align:center;">← Cambiar asientos</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
