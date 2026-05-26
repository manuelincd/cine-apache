<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT id, nombre, rol, password FROM usuarios WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();

        if ($usuario && password_verify($password, $usuario['password'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            header('Location: index.php');
            exit;
        }
        $error = 'Correo o contraseña incorrectos.';
    } else {
        $error = 'Completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar Sesión — Cine Sendera</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <span class="header-logo">Cine Sendera</span>
    <nav class="header-nav">
        <a href="cartelera.php">Cartelera</a>
        <a href="registro.php" class="btn btn-primary btn-sm">Registrarse</a>
    </nav>
</header>
<div class="main-content">
    <div class="container">
        <div class="form-wrapper" style="margin-top:2rem;">
            <h1 class="form-title">Bienvenido</h1>
            <p class="form-subtitle">Inicia sesión para comprar tus boletos</p>
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Correo electrónico</label>
                    <input type="email" name="email" placeholder="tu@correo.com" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem;">Iniciar sesión</button>
            </form>
            <p class="form-footer">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
        </div>
    </div>
</div>
</body>
</html>
