<?php
session_start();
require 'includes/db.php';
include 'includes/header.php';

$rol = $_SESSION['user']['rol'] ?? 'vendedor';

// --- RESTRICCIÓN DE ACCESO: SOLO ADMIN/PROGRAMADOR PUEDEN ACCEDER A LA EDICIÓN ---
if($rol !== 'admin' && $rol !== 'programador'){
    echo "<div class='container mt-5'><div class='alert alert-danger text-center'>❌ No tienes permisos para editar clientes.</div></div>";
    include 'includes/footer.php';
    exit;
}

$mensaje = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: clientes.php");
    exit;
}

// Obtener cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$clienteEditar = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clienteEditar) {
    header("Location: clientes.php");
    exit;
}

// Actualizar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $ruc = trim($_POST['ruc'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    // Validaciones
    if ($nombre === '') {
        $mensaje = "El nombre es obligatorio.";
    } elseif (!empty($ruc) && (!is_numeric($ruc) || strlen($ruc) != 11)) {
        $mensaje = "El RUC debe tener 11 dígitos numéricos.";
    } elseif (!empty($telefono) && (!is_numeric($telefono) || strlen($telefono) != 9)) {
        $mensaje = "El teléfono debe tener 9 dígitos numéricos.";
    } else {
        $stmt = $pdo->prepare("UPDATE clientes SET nombre=?, ruc=?, telefono=?, correo=?, direccion=? WHERE id=?");
        $stmt->execute([$nombre, $ruc, $telefono, $correo, $direccion, $id]);
        $mensaje = "✅ Cliente actualizado correctamente.";
        
        // Actualizar el objeto $clienteEditar para reflejar los cambios en el formulario
        $clienteEditar['nombre'] = $nombre;
        $clienteEditar['ruc'] = $ruc;
        $clienteEditar['telefono'] = $telefono;
        $clienteEditar['correo'] = $correo;
        $clienteEditar['direccion'] = $direccion;
    }
}

?>

<div class="container mt-5">
  <h3 class="text-center mb-4">Editar Cliente: <?= htmlspecialchars($clienteEditar['nombre'] ?? '') ?></h3>

  <?php if($mensaje): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <div class="card p-4 mb-4">
    <form method="POST">
      <input type="hidden" name="id" value="<?= $clienteEditar['id'] ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($clienteEditar['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">RUC</label>
          <input type="text" name="ruc" class="form-control" placeholder="11 dígitos" 
                 value="<?= htmlspecialchars($clienteEditar['ruc'] ?? '') ?>" maxlength="11">
        </div>
        <div class="col-md-4">
          <label class="form-label">Teléfono</label>
          <input type="text" name="telefono" class="form-control" placeholder="9 dígitos" 
                 value="<?= htmlspecialchars($clienteEditar['telefono'] ?? '') ?>" maxlength="9">
        </div>
        <div class="col-md-4">
          <label class="form-label">Correo</label>
          <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($clienteEditar['correo'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Dirección</label>
          <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($clienteEditar['direccion'] ?? '') ?>">
        </div>
      </div>
      
      <div class="mt-4">
        <button type="submit" name="editar" class="btn btn-success">Guardar cambios</button>
        <a href="clientes.php" class="btn btn-secondary">Volver a Clientes</a>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
