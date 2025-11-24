<?php
session_start();
require 'includes/db.php';
include 'includes/header.php';

$rol = $_SESSION['user']['rol'] ?? 'vendedor';

// --- RESTRICCIÓN DE ACCESO: SOLO ADMIN/PROGRAMADOR PUEDEN ACCEDER A LA GESTIÓN DE PROVEEDORES ---
if($rol !== 'admin' && $rol !== 'programador'){
    echo "<div class='container mt-5'><div class='alert alert-danger text-center'>❌ No tienes permisos para acceder a la Gestión de Proveedores.</div></div>";
    include 'includes/footer.php';
    exit;
}

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

  <div class="card p-4 mb-4">
    <form method="post">
      <?php if ($proveedorEditar): ?>
        <h5 class="mb-3">✏️ Editar Proveedor: <?= htmlspecialchars($proveedorEditar['nombre']) ?></h5>
        <input type="hidden" name="id" value="<?= $proveedorEditar['id'] ?>">
      <?php else: ?>
        <h5 class="mb-3">➕ Agregar Nuevo Proveedor</h5>
      <?php endif; ?>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($proveedorEditar['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Correo</label>
          <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($proveedorEditar['correo'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Teléfono</label>
          <input type="text" name="telefono" class="form-control" 
                 value="<?= htmlspecialchars($proveedorEditar['telefono'] ?? '') ?>" 
                 required maxlength="9" pattern="\d{9}" title="Debe tener 9 dígitos numéricos">
        </div>
        <div class="col-md-6">
          <label class="form-label">Dirección</label>
          <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($proveedorEditar['direccion'] ?? '') ?>">
        </div>
      </div>
      
      <div class="mt-4">
        <button type="submit" name="<?= $proveedorEditar ? 'editar' : 'agregar' ?>" class="btn btn-primary w-100">
          <?= $proveedorEditar ? 'Actualizar Proveedor' : 'Agregar Proveedor' ?>
        </button>
      </div>
    </form>
  </div>
    
  <div class="card p-3">
    <h5>📋 Lista de Proveedores (Total: <?= count($proveedores) ?>)</h5>
    <div class="table-responsive">
        <table class="table table-striped mt-3">
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
                        <a href="editar_proveedores.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                        <a href="proveedores.php?eliminar=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este proveedor?')">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
