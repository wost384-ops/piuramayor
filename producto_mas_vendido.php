<?php
require 'includes/db.php';
require 'includes/auth_check.php';
include 'includes/header.php';

$rol = $_SESSION['user']['rol'] ?? 'vendedor';

// Obtener mes actual
$mesActual = date('m');

// Consulta producto más vendido del mes
$stmt = $pdo->prepare("
    SELECT p.nombre, SUM(vd.cantidad) AS total_vendido
    FROM ventas_detalle vd
    JOIN ventas v ON vd.venta_id = v.id
    JOIN productos p ON vd.producto_id = p.id
    WHERE MONTH(v.fecha) = ?
    GROUP BY vd.producto_id
    ORDER BY total_vendido DESC
    LIMIT 1
");
$stmt->execute([$mesActual]);
$productoMasVendido = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="container mt-5">
    <h3 class="mb-4 text-center">📦 Producto Más Vendido del Mes</h3>

    <?php if ($productoMasVendido): ?>
        <div class="card shadow-sm p-4 text-center">
            <h5>Producto: <?= htmlspecialchars($productoMasVendido['nombre']) ?></h5>
            <p>Cantidad vendida: <?= $productoMasVendido['total_vendido'] ?></p>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">No se han registrado ventas este mes.</div>
    <?php endif; ?>

    <div class="mt-4 text-center">
        <a href="dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
