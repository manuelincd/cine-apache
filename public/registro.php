<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmar = trim($_POST['confirmar'] ?? '');

    if (!$nombre || !$email || !$password || !$confirmar) {
        $error = 'Completa todos los campos.';
    } elseif ($password !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = 'Ya existe una cuenta con ese correo.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'cliente')");
            $stmt->bind_param('sss', $nombre, $email, $hash);
            if ($stmt->execute()) {
                $exito = 'Cuenta creada exitosamente. Ahora puedes iniciar sesión.';
            } else {
                $error = 'Error al crear la cuenta. Intenta de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro — Cine Sendera</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <span class="header-logo">Cine Sendera</span>
    <nav class="header-nav">
        <a href="cartelera.php">Cartelera</a>
        <a href="login.php" class="btn btn-secondary btn-sm">Iniciar sesión</a>
    </nav>
</header>
<div class="main-content">
    <div class="container">
        <div class="form-wrapper" style="margin-top:2rem;">
            <h1 class="form-title">Crear cuenta</h1>
            <p class="form-subtitle">Únete a Cine Sendera y disfruta la experiencia</p>
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($exito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($exito) ?> <a href="login.php">Ir al login</a></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre" placeholder="Tu nombre" required>
                </div>
                <div class="form-group">
                    <label>Correo electrónico</label>
                    <input type="email" name="email" placeholder="tu@correo.com" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="form-group">
                    <label>Confirmar contraseña</label>
                    <input type="password" name="confirmar" placeholder="Repite tu contraseña" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem;">Crear cuenta</button>
            </form>
            <p class="form-footer">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        </div>
    </div>
</div>
</body>
</html>
