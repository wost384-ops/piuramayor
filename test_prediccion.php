<?php
// Script de prueba para forzar una Alerta Predictiva de Stock Bajo
// NOTA: Asegúrese de que su base de datos piuramayor_db esté creada e inicializada.

// Incluir la conexión a DB
require 'includes/db.php';
require 'includes/auth_check.php'; // Asegura que solo usuarios logueados accedan
include 'includes/header.php'; // Para usar el diseño de la app

$test_product_id = 9999;
$test_product_name = "Producto Test Crítico";
$test_stock = 3;
$test_dias_simulados = 7;
$test_ventas_por_dia = 5;

// --- 1. PREPARACIÓN DE LA BASE DE DATOS PARA EL ESCENARIO DE PRUEBA ---
try {
    // 1.1. Limpiar datos viejos de prueba para el producto 9999
    $pdo->exec("DELETE FROM ventas_detalle WHERE producto_id = $test_product_id");
    $pdo->exec("DELETE FROM productos WHERE id = $test_product_id");
    
    // 1.2. Crear o resetear el producto de prueba con stock bajo
    $stmt = $pdo->prepare("INSERT INTO productos (id, nombre, precio, stock, proveedor_id, categoria_id) 
                           VALUES (?, ?, 10.00, ?, 1, 1)");
    $stmt->execute([$test_product_id, $test_product_name, $test_stock]);
    
    // 1.3. Simular ventas de las últimas 6 fechas (alta probabilidad de venta)
    // El objetivo es simular un 85.7% de probabilidad de venta (6/7 días)
    $stmt_venta = $pdo->prepare("INSERT INTO ventas (cliente_id, total, fecha, usuario_id, numero_dia) VALUES (1, 50.00, ?, 2, 1)");
    $stmt_detalle = $pdo->prepare("INSERT INTO ventas_detalle (venta_id, producto_id, cantidad, precio_unit) VALUES (?, ?, ?, 10.00)");
    
    $simulaciones_exitosas = 0;
    
    for ($i = 1; $i <= $test_dias_simulados; $i++) {
        // Simular 6 de 7 días vendidos (omitir el día 4 para que no sea 100%)
        if ($i != 4) { 
            $fecha_simulada = date('Y-m-d H:i:s', strtotime("-$i days"));
            
            // Insertar Venta
            $stmt_venta->execute([$fecha_simulada]);
            $venta_id = $pdo->lastInsertId();
            
            // Insertar Detalle
            $stmt_detalle->execute([$venta_id, $test_product_id, $test_ventas_por_dia]);
            $simulaciones_exitosas++;
        }
    }

    $mensaje_simulacion = "✅ Simulación de venta completada: $simulaciones_exitosas ventas insertadas en los últimos $test_dias_simulados días.";

} catch (\PDOException $e) {
    $mensaje_simulacion = "❌ Error al simular datos de prueba. Asegúrese de que el usuario `id=2` exista y las tablas estén creadas: " . $e->getMessage();
}

// --- 2. EJECUCIÓN Y VISUALIZACIÓN DEL MOTOR DE PREDICCIÓN ---
ob_start(); // Iniciar buffer para capturar la salida JSON
include 'stock_prediction.php';
$json_output = ob_get_clean(); // Capturar la salida
$prediccion_data = json_decode($json_output, true);
?>

<div class="container mt-5">
    <h3 class="mb-4 text-center">🧪 Herramienta de Prueba de Predicción de Stock</h3>

    <div class="alert alert-info">
        <?= $mensaje_simulacion ?>
        <p><strong>Configuración del Test:</strong></p>
        <ul>
            <li><strong>Producto:</strong> <?= $test_product_name ?> (ID <?= $test_product_id ?>)</li>
            <li><strong>Stock Actual:</strong> <?= $test_stock ?> unidades.</li>
            <li><strong>Días Vendidos:</strong> <?= $simulaciones_exitosas ?> de 7 días.</li>
        </ul>
        <p><strong>Resultado Esperado:</strong> Alerta **ROJA** o **NARANJA** con un porcentaje de venta de **$\approx$ 85.7%**.</p>
    </div>

    <div class="card p-4">
        <h5 class="mb-3">Resultado de la Alerta Predictiva (`stock_prediction.php`)</h5>

        <?php if (!empty($prediccion_data) && !isset($prediccion_data['error'])): ?>
            <?php $item = array_filter($prediccion_data, fn($p) => $p['producto'] === $test_product_name); ?>
            <?php if (!empty($item)): ?>
                <?php $item = reset($item); // Tomar el primer y único elemento ?>
                <div class="alert alert-danger p-4">
                    <p class="h4">🚨 **RIESGO CONFIRMADO:** <?= htmlspecialchars($item['producto']) ?></p>
                    <hr>
                    <p><strong>Probabilidad de Venta Hoy:</strong> <span class="badge bg-danger fs-5"><?= htmlspecialchars($item['riesgo_porcentaje']) ?>%</span></p>
                    <p><strong>Stock Actual:</strong> <?= htmlspecialchars($item['stock']) ?> unidades.</p>
                    <p><strong>Venta Diaria Promedio (Última Semana):</strong> $\approx$ <?= htmlspecialchars($item['promedio_diario']) ?> unidades.</p>
                    <p><strong>Mensaje del Motor:</strong> <em><?= htmlspecialchars($item['mensaje_riesgo']) ?></em></p>
                    <p class="small text-muted">La alerta se activó porque el stock (<?= $item['stock'] ?>) es menor al doble de la venta promedio (<?= $item['promedio_diario'] * 2 ?>).</p>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">El producto de prueba no fue listado como riesgo. Revise el stock y las ventas simuladas.</div>
            <?php endif; ?>
        <?php elseif(isset($prediccion_data['error'])): ?>
             <div class="alert alert-danger">Error del motor: <?= htmlspecialchars($prediccion_data['error']) ?></div>
        <?php else: ?>
            <div class="alert alert-success">No se encontraron productos en riesgo de stock bajo después de la simulación.</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>