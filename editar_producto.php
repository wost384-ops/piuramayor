<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['user']['rol'] ?? 'vendedor';
if($rol !== 'admin' && $rol !== 'programador'){
    echo "<div class='alert alert-danger'>No tienes permisos para editar productos.</div>";
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: productos.php");
    exit;
}

$mensaje = '';
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$producto) {
    header("Location: productos.php");
    exit;
}

$proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// --- Función para validar datos ---
function validarProducto($sku, $nombre, $precio, $stock){
    if ($sku === '' || $nombre === '' || !is_numeric($precio) || $precio < 0 || !is_numeric($stock) || $stock < 0){
        return false;
    }
    return true;
}

// --- Guardar cambios ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $sku = trim($_POST['sku']);
    $nombre = trim($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $proveedor_id = $_POST['proveedor_id'] ?: null;

    if(!validarProducto($sku, $nombre, $precio, $stock)){
        $mensaje = "Completa correctamente todos los campos. Precio y Stock deben ser positivos.";
    } else {
        $stmt = $pdo->prepare("UPDATE productos SET sku=?, nombre=?, precio=?, stock=?, proveedor_id=? WHERE id=?");
        $stmt->execute([$sku, $nombre, $precio, $stock, $proveedor_id, $id]);
        $mensaje = "✅ Producto actualizado correctamente.";

        $producto['sku'] = $sku;
        $producto['nombre'] = $nombre;
        $producto['precio'] = $precio;
        $producto['stock'] = $stock;
        $producto['proveedor_id'] = $proveedor_id;
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <h3 class="mb-4">✏️ Editar Producto</h3>

    <?php if ($mensaje): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="card p-3 shadow-sm">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-2">
                    <label>SKU</label>
                    <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($producto['sku']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label>Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                </div>
                <div class="col-md-2">
                    <label>Precio</label>
                    <input type="number" step="0.01" name="precio" class="form-control" value="<?= htmlspecialchars($producto['precio']) ?>" required>
                </div>
                <div class="col-md-2">
                    <label>Stock</label>
                    <input type="number" name="stock" class="form-control <?= $producto['stock'] <= 5 ? 'border border-danger fw-bold text-danger' : '' ?>" value="<?= htmlspecialchars($producto['stock']) ?>" required>
                </div>
                <div class="col-md-2">
                    <label>Proveedor</label>
                    <select name="proveedor_id" class="form-select">
                        <option value="">-- Sin asignar --</option>
                        <?php foreach($proveedores as $pr): ?>
                            <option value="<?= $pr['id'] ?>" <?= $producto['proveedor_id'] == $pr['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pr['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" name="editar" class="btn btn-success">Guardar cambios</button>
            <a href="productos.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
