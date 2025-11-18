<?php
session_start();
require 'includes/db.php';
include 'includes/header.php';

$rol = $_SESSION['user']['rol'] ?? 'vendedor';
$mensaje = '';

// --- Agregar proveedor ---
if (isset($_POST['agregar'])) {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);

    // Validaciones
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Correo inválido";
    } elseif (!preg_match('/^\d{9}$/', $telefono)) { // Teléfono numérico exacto 9 dígitos
        $mensaje = "El teléfono debe tener 9 dígitos numéricos.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO proveedores (nombre, correo, telefono, direccion) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $correo, $telefono, $direccion]);
        $mensaje = "Proveedor agregado correctamente";
    }
}

// --- Editar proveedor ---
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);

    // Validaciones
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Correo inválido";
    } elseif (!preg_match('/^\d{9}$/', $telefono)) { // Teléfono numérico exacto 9 dígitos
        $mensaje = "El teléfono debe tener 9 dígitos numéricos.";
    } else {
        $stmt = $pdo->prepare("UPDATE proveedores SET nombre=?, correo=?, telefono=?, direccion=? WHERE id=?");
        $stmt->execute([$nombre, $correo, $telefono, $direccion, $id]);
        $mensaje = "Proveedor actualizado correctamente";
    }
}

// --- Eliminar proveedor ---
if (isset($_GET['eliminar']) && ($rol === 'admin' || $rol === 'programador')) {
    $id = $_GET['eliminar'];
    $pdo->prepare("DELETE FROM proveedores WHERE id=?")->execute([$id]);
    $mensaje = "Proveedor eliminado correctamente";
}

// --- Mostrar proveedor en modo edición ---
$proveedorEditar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id=?");
    $stmt->execute([$id]);
    $proveedorEditar = $stmt->fetch();
}

// Obtener lista de proveedores
$proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
  <h3 class="text-center mb-4">Gestión de Proveedores</h3>

  <?php if($mensaje): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <div class="card p-4 mb-4 shadow-sm">
    <form method="post">
      <?php if ($proveedorEditar): ?>
        <input type="hidden" name="id" value="<?= $proveedorEditar['id'] ?>">
      <?php endif; ?>

      <div class="row mb-3">
        <div class="col">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($proveedorEditar['nombre'] ?? '') ?>" required>
        </div>
        <div class="col">
          <label class="form-label">Correo</label>
          <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($proveedorEditar['correo'] ?? '') ?>" required>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col">
          <label class="form-label">Teléfono</label>
          <input type="text" name="telefono" class="form-control" 
                 value="<?= htmlspecialchars($proveedorEditar['telefono'] ?? '') ?>" 
                 required maxlength="9" pattern="\d{9}" title="Debe tener 9 dígitos numéricos">
        </div>
        <div class="col">
          <label class="form-label">Dirección</label>
          <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($proveedorEditar['direccion'] ?? '') ?>">
        </div>
      </div>

      <button type="submit" name="<?= $proveedorEditar ? 'editar' : 'agregar' ?>" class="btn btn-primary w-100">
        <?= $proveedorEditar ? 'Actualizar Proveedor' : 'Agregar Proveedor' ?>
      </button>
    </form>
  </div>

  <div class="card shadow-sm p-3">
    <h5>Lista de Proveedores</h5>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Teléfono</th>
          <th>Dirección</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($proveedores as $row): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['nombre']) ?></td>
          <td><?= htmlspecialchars($row['correo']) ?></td>
          <td><?= htmlspecialchars($row['telefono']) ?></td>
          <td><?= htmlspecialchars($row['direccion']) ?></td>
          <td>
            <a href="proveedores.php?editar=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
            <?php if($rol === 'admin' || $rol === 'programador'): ?>
            <a href="proveedores.php?eliminar=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este proveedor?')">Eliminar</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
