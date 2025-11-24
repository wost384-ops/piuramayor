<?php
session_start();
require 'includes/db.php';
require 'includes/auth_check.php';

// =================================================================
// RESTRICCIÓN DE ACCESO: SOLO ADMIN/PROGRAMADOR
// =================================================================
$rol = $_SESSION['user']['rol'] ?? 'vendedor';
if($rol !== 'admin' && $rol !== 'programador'){
    header('Location: dashboard.php');
    exit;
}

include 'includes/header.php'; // Incluye el header con los permisos definidos

// =================================================================
// CONSULTA SQL: Resumen de ventas del día (usando CURDATE() para sincronizar)
// =================================================================

$resumenDia = [];
$totalGeneralDia = 0;
$totalOperaciones = 0;
$fechaCierre = date('d/m/Y'); // Solo para mostrar en el título

try {
    // 1. Obtener el total de ventas por cada usuario para el día de hoy
    $stmt = $pdo->prepare("
        SELECT 
            u.nombre AS vendedor_nombre,
            COUNT(v.id) AS total_operaciones,
            SUM(v.total) AS total_ingresado
        FROM ventas v
        JOIN usuarios u ON v.usuario_id = u.id
        WHERE DATE(v.fecha) = CURDATE()  -- La función CURDATE() de MySQL
        GROUP BY u.id, u.nombre
        ORDER BY total_ingresado DESC
    ");
    $stmt->execute(); 

    $resumenDia = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Calcular el total general del día y el número total de operaciones
    $stmtTotal = $pdo->prepare("SELECT IFNULL(SUM(total), 0) FROM ventas WHERE DATE(fecha) = CURDATE()");
    $stmtTotal->execute();
    $totalGeneralDia = $stmtTotal->fetchColumn();

    $stmtOps = $pdo->prepare("SELECT COUNT(id) FROM ventas WHERE DATE(fecha) = CURDATE()");
    $stmtOps->execute();
    $totalOperaciones = $stmtOps->fetchColumn();

} catch (\PDOException $e) {
    $resumenDia = [];
    $totalGeneralDia = 0;
    $error_db = "Error al cargar el resumen de la base de datos.";
}
?>

<div class="container mt-4">
    <h3 class="mb-4 text-center">💵 Cierre de Caja Diario (<?= $fechaCierre ?>)</h3>

    <?php if (isset($error_db)): ?>
        <div class="alert alert-danger text-center"><?= $error_db ?></div>
    <?php endif; ?>

    <?php if (empty($resumenDia)): ?>
        <div class="alert alert-warning text-center">Aún no hay ventas registradas para el día de hoy.</div>
    <?php else: ?>
        
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card bg-success text-white text-center shadow">
                    <div class="card-body">
                        <h4 class="card-title">TOTAL DE CAJA HOY</h4>
                        <p class="h1">S/ <?= number_format($totalGeneralDia, 2) ?></p>
                        <p class="card-text">Monto total de ingresos brutos registrados.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-info text-white text-center shadow">
                    <div class="card-body">
                        <h4 class="card-title">N° DE OPERACIONES</h4>
                        <p class="h1"><?= $totalOperaciones ?></p>
                        <p class="card-text">Total de ventas registradas hoy.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card p-3 shadow-sm mb-4">
            <h5>Resumen Detallado por Usuario</h5>
            <div class="table-responsive">
                <table class="table table-striped mt-3">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Total Operaciones</th>
                            <th>Monto Ingresado (S/)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resumenDia as $res): ?>
                        <tr>
                            <td><?= htmlspecialchars($res['vendedor_nombre']) ?></td>
                            <td><?= $res['total_operaciones'] ?></td>
                            <td>S/ <?= number_format($res['total_ingresado'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center mb-5">
            <a href="generar_pdf_caja.php" target="_blank" class="btn btn-danger btn-lg me-3">
                <i class="bi bi-file-earmark-pdf-fill me-2"></i> Generar PDF
            </a>
            <button class="btn btn-info btn-lg" onclick="window.print()">
                <i class="bi bi-printer-fill me-2"></i> Imprimir Reporte
            </button>
        </div>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<style>
/* Estilos para una impresión limpia */
@media print {
    /* Ocultar el menú lateral, footer y botones de acción al imprimir */
    .sidebar, footer, .btn, .nav-item {
        display: none !important;
    }
    .content {
        margin-left: 0 !important;
        background: none !important;
        box-shadow: none !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
    }
    /* Muestra la tabla de manera simple para el papel */
    .table-responsive {
        overflow: visible !important;
    }
}
</style>