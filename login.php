<?php
session_start();
require 'includes/db.php';

// Redirigir si ya está logueado
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

// --- Manejo de login ---
$error = '';
if (isset($_POST['login'])) {
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Correo o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - PiuraMayor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: url('img/fondo2.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: Arial, sans-serif;
        height: 100vh;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-box {
        background: rgba(255, 255, 255, 0.85);
        padding: 40px 30px;
        border-radius: 10px;
        max-width: 400px;
        width: 100%;
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
        text-align: center;
    }

    .login-box h1 {
        margin-bottom: 20px;
        color: #007bff;
    }

    .login-box .btn-primary {
        width: 100%;
    }

    .error {
        color: red;
        margin-bottom: 15px;
    }
</style>
</head>
<body>

<div class="login-box">
    <h1>PiuraMayor</h1>
    <p>Sistema integral de gestión para mayoristas de abarrotes</p>

    <?php if($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <input type="email" name="correo" class="form-control" placeholder="Correo" required>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
        </div>
        <button type="submit" name="login" class="btn btn-primary">Iniciar Sesión</button>
    </form>
</div>

</body>
</html>
