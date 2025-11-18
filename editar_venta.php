<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: ventas.php");
    exit;
}

// Obtener datos de la venta
$stmt = $pdo->prepare("SELECT * FROM ventas WHERE id = ?");
$stmt->execute([$id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venta) {
    header("Location: ventas.php");
    exit;
}

// Obtener lista de clientes
$clientes = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $total = (float)($_POST['total'] ?? 0);
    $fecha = $_POST['fecha'] ?? '';

    if ($cliente_nombre === '') {
        $mensaje = "Debes ingresar un nombre de cliente.";
    } elseif ($total <= 0) {
        $mensaje = "El total debe ser mayor a cero.";
    } elseif (!$fecha) {
        $mensaje = "Debes ingresar una fecha válida.";
    } else {
        // Verificar si el cliente ya existe
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE nombre = ?");
        $stmt->execute([$cliente_nombre]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            $cliente_id = $cliente['id'];
        } else {
            // Crear cliente nuevo automáticamente
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre) VALUES (?)");
            $stmt->execute([$cliente_nombre]);
            $cliente_id = $pdo->lastInsertId();
        }

        // Actualizar venta
        $stmt = $pdo->prepare("UPDATE ventas SET cliente_id = ?, total = ?, fecha = ? WHERE id = ?");
        $stmt->execute([$cliente_id, $total, $fecha, $id]);

        $mensaje = "✅ Venta actualizada correctamente.";
        $venta['cliente_id'] = $cliente_id;
        $venta['total'] = $total;
        $venta['fecha'] = $fecha;
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <h3 class="mb-4 text-center">✏️ Editar Venta #<?= htmlspecialchars($id) ?></h3>

    <?php if($mensaje): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="card p-3 shadow-sm">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Cliente</label>
                    <input list="clientes_list" name="cliente_nombre" class="form-control" placeholder="Escribe o selecciona un cliente" 
                        value="<?= htmlspecialchars($venta['cliente_id'] ? array_search($venta['cliente_id'], array_column($clientes, 'id')) !== false ? $clientes[array_search($venta['cliente_id'], array_column($clientes, 'id'))]['nombre'] : '' : '') ?>" required>
                    <datalist id="clientes_list">
                        <?php foreach($clientes as $cli): ?>
                            <option value="<?= htmlspecialchars($cli['nombre']) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label>Total</label>
                    <input type="number" step="0.01" name="total" class="form-control" value="<?= htmlspecialchars($venta['total']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label>Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($venta['fecha']) ?>" required>
                </div>
            </div>
            <button type="submit" name="editar" class="btn btn-success">Guardar Cambios</button>
            <a href="ventas.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
