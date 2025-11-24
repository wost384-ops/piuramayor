<?php
session_start();
require 'includes/db.php';
include 'includes/header.php';

$rol = $_SESSION['user']['rol'] ?? 'vendedor';

// Definir límite de stock bajo: CAMBIADO DE 10 A 50 UNIDADES
$limite_stock = 50;

// Obtener productos con stock bajo (Stock <= 50)
$productosBajoStock = $pdo->prepare("SELECT nombre, stock FROM productos WHERE stock <= ? ORDER BY stock ASC");
$productosBajoStock->execute([$limite_stock]);
$productos = $productosBajoStock->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h3 class="mb-4 text-center">⚠️ Alerta de Stock Bajo</h3>
    <p class="text-muted text-center">Mostrando productos con stock igual o menor a <?= $limite_stock ?> unidades.</p>

    <?php if (count($productos) > 0): ?>
        <table class="table table-striped shadow-sm">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Stock Actual</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                    <tr style="color: <?= $p['stock'] <= 5 ? 'red' : 'orange' ?>;">
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= $p['stock'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-success text-center">✅ Todos los productos tienen stock superior a <?= $limite_stock ?> unidades.</div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
