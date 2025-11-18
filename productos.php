<?php
session_start();
require 'includes/db.php';
include 'includes/header.php';

$rol = $_SESSION['user']['rol'] ?? 'vendedor';
$mensaje = '';

// --- Agregar producto ---
if (isset($_POST['agregar'])) {
    $nombre = trim($_POST['nombre']);
    $sku = trim($_POST['sku']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $proveedor_id = intval($_POST['proveedor_id']);
    $categoria_id = intval($_POST['categoria_id']);

    if ($nombre === '' || $precio <= 0 || $stock < 0) {
        $mensaje = "Completa todos los campos correctamente.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, sku, precio, stock, proveedor_id, categoria_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $sku, $precio, $stock, $proveedor_id, $categoria_id]);
        $mensaje = "Producto agregado correctamente.";
    }
}

// --- Editar producto ---
if (isset($_POST['editar'])) {
    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $sku = trim($_POST['sku']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $proveedor_id = intval($_POST['proveedor_id']);
    $categoria_id = intval($_POST['categoria_id']);

    if ($nombre === '' || $precio <= 0 || $stock < 0) {
        $mensaje = "Completa todos los campos correctamente.";
    } else {
        $stmt = $pdo->prepare("UPDATE productos SET nombre=?, sku=?, precio=?, stock=?, proveedor_id=?, categoria_id=? WHERE id=?");
        $stmt->execute([$nombre, $sku, $precio, $stock, $proveedor_id, $categoria_id, $id]);
        $mensaje = "Producto actualizado correctamente.";
    }
}

// --- Eliminar producto ---
if (isset($_GET['eliminar']) && ($rol === 'admin' || $rol === 'programador')) {
    $id = intval($_GET['eliminar']);
    $pdo->prepare("DELETE FROM productos WHERE id=?")->execute([$id]);
    $mensaje = "Producto eliminado.";
}

// --- Obtener lista de productos ---
$productos = $pdo->query("
    SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// --- Obtener lista de proveedores y categorías ---
$proveedores = $pdo->query("SELECT id, nombre FROM proveedores ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <h3 class="text-center mb-4">Gestión de Productos</h3>

    <?php if($mensaje): ?>
        <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="card p-4 mb-4 shadow-sm">
        <form method="post">
            <div class="row mb-3">
                <div class="col">
                    <label>Nombre</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col">
                    <label>SKU</label>
                    <input type="text" name="sku" class="form-control">
                </div>
                <div class="col">
                    <label>Precio</label>
                    <input type="number" step="0.01" name="precio" class="form-control" required>
                </div>
                <div class="col">
                    <label>Stock</label>
                    <input type="number" name="stock" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label>Proveedor</label>
                    <select name="proveedor_id" class="form-control">
                        <option value="">-- Seleccionar proveedor --</option>
                        <?php foreach($proveedores as $prov): ?>
                            <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col">
                    <label>Categoría</label>
                    <select name="categoria_id" class="form-control">
                        <option value="">-- Seleccionar categoría --</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" name="agregar" class="btn btn-primary w-100">Agregar Producto</button>
        </form>
    </div>

    <div class="card shadow-sm p-3">
        <h5>Lista de Productos</h5>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>SKU</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Proveedor</th>
                    <th>Categoría</th>
                    <?php if($rol === 'admin' || $rol === 'programador'): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($productos as $p): ?>
                    <?php
                    // Clase según stock
                    $claseStock = '';
                    if ($p['stock'] == 0) {
                        $claseStock = 'text-danger fw-bold text-decoration-underline';
                    } elseif ($p['stock'] <= 5) {
                        $claseStock = 'text-warning fw-bold';
                    }
                    ?>
                    <tr class="<?= $claseStock ?>">
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['sku']) ?></td>
                        <td>S/ <?= number_format($p['precio'],2) ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td><?= htmlspecialchars($p['proveedor']) ?></td>
                        <td><?= htmlspecialchars($p['categoria']) ?></td>
                        <?php if($rol === 'admin' || $rol === 'programador'): ?>
                        <td>
                            <a href="productos.php?editar=<?= $p['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                            <a href="productos.php?eliminar=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este producto?')">Eliminar</a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
