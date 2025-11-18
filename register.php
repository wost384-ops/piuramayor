<?php
session_start();
require 'includes/db.php';

$mensaje = '';

// Datos del programador (fijo en la base de datos)
$programador_correo = 'yweslydaniel@gmail.com';
$programador_password = 'https://programadoradmin/2025.com';

if (isset($_POST['register'])) {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];
    $rol = $_POST['rol']; // "admin" o "vendedor"
    $clave_programador = $_POST['clave_programador'] ?? '';

    // Evitar que el programador se registre nuevamente
    if ($correo === $programador_correo) {
        $mensaje = "El programador ya está registrado en el sistema.";
    }

    // Validación: solo el programador puede autorizar creación de admins
    if ($mensaje === '' && $rol === 'admin') {
        if ($clave_programador !== $programador_password) {
            $mensaje = "Para crear un administrador se requiere la contraseña del programador.";
        }
    }

    // Continuar si no hay error
    if ($mensaje === '') {
        // Verificar si el correo ya existe
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        if ($stmt->rowCount() > 0) {
            $mensaje = "El correo ya está registrado";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (correo,password,nombre,rol) VALUES (?,?,?,?)");
            $stmt->execute([$correo, $hash, $nombre, $rol]);
            $mensaje = "Usuario registrado correctamente";
        }
    }
}
?>

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Registro - PiuraMayor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script>
function toggleClaveProgramador() {
    const rolSelect = document.getElementById('rol');
    const claveDiv = document.getElementById('clave_programador_div');
    if (rolSelect.value === 'admin') {
        claveDiv.style.display = 'block';
    } else {
        claveDiv.style.display = 'none';
    }
}
</script>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm p-4">
                <h4 class="mb-4 text-center">Registrarse</h4>

                <?php if($mensaje): ?>
                    <div class="alert <?= strpos($mensaje,'correctamente')!==false?'alert-success':'alert-danger' ?>">
                        <?= $mensaje ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="text" name="nombre" class="form-control mb-3" placeholder="Nombre completo" required>
                    <input type="email" name="correo" class="form-control mb-3" placeholder="Correo" required>
                    <input type="password" name="password" class="form-control mb-3" placeholder="Contraseña" required>

                    <select name="rol" id="rol" class="form-control mb-3" onchange="toggleClaveProgramador()" required>
                        <option value="vendedor">Vendedor</option>
                        <option value="admin">Administrador</option>
                    </select>

                    <div id="clave_programador_div" style="display:none;">
                        <input type="password" name="clave_programador" class="form-control mb-3" placeholder="Contraseña del programador (solo admin)">
                    </div>

                    <button type="submit" name="register" class="btn btn-success w-100">Registrarse</button>
                </form>

                <p class="mt-3 text-center">
                    ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>toggleClaveProgramador();</script>
</body>
</html>
