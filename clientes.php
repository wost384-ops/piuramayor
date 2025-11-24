<?php
session_start();
require 'includes/db.php';
include 'includes/header.php';

// Verificar si el usuario ha iniciado sesión (ya lo hace header.php, pero es buena práctica)
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['user']['rol'] ?? 'vendedor';
$mensaje = '';
$can_manage = ($rol === 'admin' || $rol === 'programador'); // Variable para simplificar las verificaciones

// --- AGREGAR CLIENTE (Permitido para todos) ---
if (isset($_POST['agregar'])) {
    $nombre = trim($_POST['nombre']);
    $ruc = trim($_POST['ruc']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $direccion = trim($_POST['direccion']);

    // Validaciones
    if ($nombre === '') {
        $mensaje = "El nombre es obligatorio.";
    } elseif (!empty($ruc) && (!is_numeric($ruc) || strlen($ruc) != 11)) {
        $mensaje = "El RUC debe tener 11 dígitos numéricos.";
    } elseif (!empty($telefono) && (!is_numeric($telefono) || strlen($telefono) != 9)) {
        $mensaje = "El teléfono debe tener 9 dígitos numéricos.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO clientes (nombre, ruc, telefono, correo, direccion) VALUES (?,?,?,?,?)");
        $stmt->execute([$nombre, $ruc, $telefono, $correo, $direccion]);
        $mensaje = "Cliente agregado correctamente.";
    }
}

// --- ELIMINAR CLIENTE (Solo Admin/Programador) ---
if (isset($_GET['eliminar']) && $can_manage) {
    $id = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $mensaje = "Cliente eliminado";
}

// --- OBTENER CLIENTES ---
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-4">
    <h3 class="mb-4 text-center">Gestión de Clientes</h3>

    <?php if($mensaje): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="card mb-4 p-3">
        <h5>➕ Agregar Cliente</h5>
        <form method="POST">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="nombre" class="form-control" placeholder="Nombre" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="ruc" class="form-control" placeholder="RUC (11 dígitos)" pattern="\d{11}" title="Debe tener 11 dígitos" maxlength="11">
                </div>
                <div class="col-md-2">
                    <input type="text" name="telefono" class="form-control" placeholder="Teléfono (9 dígitos)" pattern="\d{9}" title="Debe tener 9 dígitos" maxlength="9">
                </div>
                <div class="col-md-3">
                    <input type="email" name="correo" class="form-control" placeholder="Correo">
                </div>
                <div class="col-md-2">
                    <input type="text" name="direccion" class="form-control" placeholder="Dirección">
                </div>
            </div>
            <button name="agregar" class="btn btn-primary mt-2">Agregar Cliente</button>
        </form>
    </div>

    <div class="card p-3">
        <h5>📋 Lista de Clientes</h5>
        <div class="table-responsive">
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>RUC</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Dirección</th>
                        <?php if($can_manage): // COLUMNA ACCIONES SOLO PARA ADMIN/PROGRAMADOR ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($clientes as $cli): ?>
                        <tr>
                            <td><?= $cli['id'] ?></td>
                            <td><?= htmlspecialchars($cli['nombre']) ?></td>
                            <td><?= htmlspecialchars($cli['ruc']) ?></td>
                            <td><?= htmlspecialchars($cli['telefono']) ?></td>
                            <td><?= htmlspecialchars($cli['correo']) ?></td>
                            <td><?= htmlspecialchars($cli['direccion']) ?></td>
                            <?php if($can_manage): // BOTONES SOLO PARA ADMIN/PROGRAMADOR ?>
                            <td>
                                <a href="editar_clientes.php?id=<?= $cli['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                <a href="clientes.php?eliminar=<?= $cli['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Deseas eliminar este cliente?')">Eliminar</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>