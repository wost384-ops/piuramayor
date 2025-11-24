<?php
session_start();
require 'includes/db.php';
include 'includes/header.php';

$rol = $_SESSION['user']['rol'] ?? 'vendedor';
// Definimos la variable de gestión ampliada
$can_manage_inventory = ($rol === 'admin' || $rol === 'programador' || $rol === 'vendedor');

// --- RESTRICCIÓN DE ACCESO: SOLO GESTORES PUEDEN ACCEDER A LA GESTIÓN DE PRODUCTOS ---
// Ya que el Vendedor ahora es un Gestor de Inventario, el acceso es total para él también.
if(!$can_manage_inventory){
    echo "<div class='container mt-5'><div class='alert alert-danger text-center'>❌ No tienes permisos para acceder a la Gestión de Productos.</div></div>";
    include 'includes/footer.php';
    exit;
}

$mensaje = '';

// --- Agregar producto ---
if (isset($_POST['agregar'])) {
    $nombre = trim($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $proveedor_id = intval($_POST['proveedor_id']);
    $categoria_id = intval($_POST['categoria_id']);

    if ($nombre === '' || $precio <= 0 || $stock < 0) {
        $mensaje = "Completa todos los campos correctamente.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO productos (nombre, precio, stock, proveedor_id, categoria_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $precio, $stock, $proveedor_id, $categoria_id]); 
        $mensaje = "Producto agregado correctamente.";
    }
}

// --- Editar producto ---
if (isset($_POST['editar'])) {
    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $proveedor_id = intval($_POST['proveedor_id']);
    $categoria_id = intval($_POST['categoria_id']);

    if ($nombre === '' || $precio <= 0 || $stock < 0) {
        $mensaje = "Completa todos los campos correctamente.";
    } else {
        $stmt = $pdo->prepare("UPDATE productos SET nombre=?, precio=?, stock=?, proveedor_id=?, categoria_id=? WHERE id=?");
        $stmt->execute([$nombre, $precio, $stock, $proveedor_id, $categoria_id, $id]); 
        $mensaje = "Producto actualizado correctamente.";
    }
}

// --- Eliminar producto ---
if (isset($_GET['eliminar']) && $can_manage_inventory) {
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

    <div class="card p-4 mb-4">
        <h5 class="mb-3">➕ Agregar Producto</h5>
        <form method="post">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Precio</label>
                    <input type="number" step="0.01" name="precio" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Proveedor</label>
                    <select name="proveedor_id" class="form-control">
                        <option value="">-- Seleccionar proveedor --</option>
                        <?php foreach($proveedores as $prov): ?>
                            <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Categoría</label>
                    <select name="categoria_id" class="form-control">
                        <option value="">-- Seleccionar categoría --</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" name="agregar" class="btn btn-primary w-100 mt-3">Agregar Producto</button>
        </form>
    </div>

    <div class="card p-3">
        <h5>Lista de Productos</h5>
        <div class="table-responsive">
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Proveedor</th>
                        <th>Categoría</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($productos as $p): ?>
                        <?php
                        // Clase según stock
                        $claseStock = '';
                        if ($p['stock'] == 0) {
                            $claseStock = 'table-danger fw-bold';
                        } elseif ($p['stock'] <= 5) {
                            $claseStock = 'table-warning fw-bold';
                        }
                        ?>
                        <tr class="<?= $claseStock ?>">
                            <td><?= $p['id'] ?></td>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td>S/ <?= number_format($p['precio'],2) ?></td>
                            <td><?= $p['stock'] ?></td>
                            <td><?= htmlspecialchars($p['proveedor']) ?></td>
                            <td><?= htmlspecialchars($p['categoria']) ?></td>
                            <td>
                                <a href="editar_producto.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="productos.php?eliminar=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este producto?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
