<?php
session_start();
require 'includes/db.php';
include 'includes/header.php';

$mensaje = '';

// --- Agregar cliente ---
if (isset($_POST['agregar'])) {
    $nombre = trim($_POST['nombre']);
    $ruc = trim($_POST['ruc']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $direccion = trim($_POST['direccion']);

    // Validaciones
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) && !empty($correo)) {
        $mensaje = "Correo inválido";
    } elseif (!preg_match('/^\d{7,15}$/', $telefono) && !empty($telefono)) {
        $mensaje = "Teléfono inválido";
    } else {
        $stmt = $pdo->prepare("INSERT INTO clientes (nombre, ruc, telefono, correo, direccion) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $ruc, $telefono, $correo, $direccion]);
        $mensaje = "Cliente agregado correctamente";
    }
}

// --- Editar cliente ---
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $ruc = trim($_POST['ruc']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $direccion = trim($_POST['direccion']);

    // Validaciones
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) && !empty($correo)) {
        $mensaje = "Correo inválido";
    } elseif (!preg_match('/^\d{7,15}$/', $telefono) && !empty($telefono)) {
        $mensaje = "Teléfono inválido";
    } else {
        $stmt = $pdo->prepare("UPDATE clientes SET nombre=?, ruc=?, telefono=?, correo=?, direccion=? WHERE id=?");
        $stmt->execute([$nombre, $ruc, $telefono, $correo, $direccion, $id]);
        $mensaje = "Cliente actualizado correctamente";
    }
}

// --- Eliminar cliente ---
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $pdo->prepare("DELETE FROM clientes WHERE id=?")->execute([$id]);
    $mensaje = "Cliente eliminado correctamente";
}

// --- Obtener cliente a editar ---
$clienteEditar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id=?");
    $stmt->execute([$id]);
    $clienteEditar = $stmt->fetch();
}

// --- Lista de clientes ---
$clientes = $pdo->query("SELECT * FROM clientes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
  <h3 class="text-center mb-4">Gestión de Clientes</h3>

  <?php if($mensaje): ?>
    <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <div class="card p-4 mb-4 shadow-sm">
    <form method="post">
      <?php if ($clienteEditar): ?>
        <input type="hidden" name="id" value="<?= $clienteEditar['id'] ?>">
      <?php endif; ?>

      <div class="row mb-3">
        <div class="col">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($clienteEditar['nombre'] ?? '') ?>" required>
        </div>
        <div class="col">
          <label class="form-label">RUC</label>
          <input type="text" name="ruc" class="form-control" value="<?= htmlspecialchars($clienteEditar['ruc'] ?? '') ?>">
        </div>
      </div>

      <div class="row mb-3">
        <div class="col">
          <label class="form-label">Teléfono</label>
          <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($clienteEditar['telefono'] ?? '') ?>">
        </div>
        <div class="col">
          <label class="form-label">Correo</label>
          <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($clienteEditar['correo'] ?? '') ?>">
        </div>
        <div class="col">
          <label class="form-label">Dirección</label>
          <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($clienteEditar['direccion'] ?? '') ?>">
        </div>
      </div>

      <button type="submit" name="<?= $clienteEditar ? 'editar' : 'agregar' ?>" class="btn btn-primary w-100">
        <?= $clienteEditar ? 'Actualizar Cliente' : 'Agregar Cliente' ?>
      </button>
    </form>
  </div>

  <div class="card shadow-sm p-3">
    <h5>Lista de Clientes</h5>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>RUC</th>
          <th>Teléfono</th>
          <th>Correo</th>
          <th>Dirección</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($clientes as $cli): ?>
        <tr>
          <td><?= $cli['id'] ?></td>
          <td><?= htmlspecialchars($cli['nombre']) ?></td>
          <td><?= htmlspecialchars($cli['ruc']) ?></td>
          <td><?= htmlspecialchars($cli['telefono']) ?></td>
          <td><?= htmlspecialchars($cli['correo']) ?></td>
          <td><?= htmlspecialchars($cli['direccion']) ?></td>
          <td>
            <a href="clientes.php?editar=<?= $cli['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
            <a href="clientes.php?eliminar=<?= $cli['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este cliente?')">Eliminar</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
