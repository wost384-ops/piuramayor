<?php
session_start();
require 'includes/db.php';

// Verificar sesión
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Permisos
$rol = $_SESSION['user']['rol'] ?? 'vendedor';
if($rol !== 'admin' && $rol !== 'programador'){
    echo "<div class='alert alert-danger'>No tienes permisos para editar proveedores.</div>";
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: proveedores.php");
    exit;
}

$mensaje = '';
// Obtener proveedor
$stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
$stmt->execute([$id]);
$prov = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prov) {
    header("Location: proveedores.php");
    exit;
}

// Actualizar proveedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre === '' || $correo === '') {
        $mensaje = "Nombre y correo son obligatorios.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Correo no válido.";
    } else {
        $stmt = $pdo->prepare("UPDATE proveedores SET nombre = ?, correo = ?, telefono = ?, direccion = ? WHERE id = ?");
        $stmt->execute([$nombre, $correo, $telefono, $direccion, $id]);
        $mensaje = "✅ Proveedor actualizado correctamente.";
        // Actualizar $prov para refrescar los datos del formulario
        $prov['nombre'] = $nombre;
        $prov['correo'] = $correo;
        $prov['telefono'] = $telefono;
        $prov['direccion'] = $direccion;
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <h3 class="mb-4">✏️ Editar Proveedor</h3>

    <?php if ($mensaje): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="card p-3 shadow-sm">
        <form method="POST">
            <div class="mb-3">
                <label>Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($prov['nombre']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Correo</label>
                <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($prov['correo']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($prov['telefono']) ?>">
            </div>
            <div class="mb-3">
                <label>Dirección</label>
                <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($prov['direccion']) ?>">
            </div>
            <button type="submit" name="editar" class="btn btn-success">Guardar cambios</button>
            <a href="proveedores.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
