<?php
require 'includes/db.php';
require 'includes/auth_check.php';
include 'includes/header.php';

$rol = $_SESSION['user']['rol'] ?? 'vendedor';

// Obtener mes actual
$mesActual = date('m');

// Consulta cliente más frecuente del mes
$stmt = $pdo->prepare("
    SELECT c.nombre, COUNT(v.id) AS total_compras
    FROM ventas v
    JOIN clientes c ON v.cliente_id = c.id
    WHERE MONTH(v.fecha) = ?
    GROUP BY v.cliente_id
    ORDER BY total_compras DESC
    LIMIT 1
");
$stmt->execute([$mesActual]);
$clienteFrecuente = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="container mt-5">
    <h3 class="mb-4 text-center">👤 Cliente Más Frecuente del Mes</h3>

    <?php if ($clienteFrecuente): ?>
        <div class="card shadow-sm p-4 text-center">
            <h5>Cliente: <?= htmlspecialchars($clienteFrecuente['nombre']) ?></h5>
            <p>Compras realizadas: <?= $clienteFrecuente['total_compras'] ?></p>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">No se han registrado ventas este mes.</div>
    <?php endif; ?>

    <div class="mt-4 text-center">
        <a href="dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
