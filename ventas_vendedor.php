<?php
require 'includes/db.php';
require 'includes/auth_check.php';
include 'includes/header.php';

// Filtros
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';

$condicion = "";
$parametros = [];

if ($desde != "" && $hasta != "") {
    $condicion = "WHERE v.fecha BETWEEN ? AND ?";
    $parametros = [$desde . " 00:00:00", $hasta . " 23:59:59"];
}

$query = "
    SELECT u.nombre AS vendedor, COUNT(v.id) AS total_ventas, SUM(v.total) AS total_generado
    FROM ventas v
    INNER JOIN usuarios u ON v.usuario_id = u.id
    $condicion
    GROUP BY u.id
    ORDER BY total_generado DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($parametros);
$vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h3>🧑‍💼 Ranking de vendedores</h3>

    <form method="GET" class="row g-3">
        <div class="col-md-4">
            <label>Desde:</label>
            <input type="date" name="desde" value="<?= $desde ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label>Hasta:</label>
            <input type="date" name="hasta" value="<?= $hasta ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label>&nbsp;</label>
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>

    <div class="card shadow-sm p-3 mt-4">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Vendedor</th>
                    <th>Cantidad de ventas</th>
                    <th>Total generado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendedores as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['vendedor']) ?></td>
                    <td><?= $v['total_ventas'] ?></td>
                    <td>S/ <?= number_format($v['total_generado'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
