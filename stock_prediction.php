<?php
// Motor de Predicción de Stock y Probabilidad de Venta (Actualización cada 30 minutos)
require 'includes/db.php';

$results = [];
$dias_a_monitorear = 7; // Monitoreamos los últimos 7 días para una "venta constante"

try {
    // 1. Obtener la fecha de inicio del monitoreo
    $fecha_inicio = date('Y-m-d', strtotime("-$dias_a_monitorear days"));

    // 2. Obtener datos históricos de ventas por producto (Últimos 7 días)
    $stmt = $pdo->prepare("
        SELECT
            p.id AS producto_id,
            p.nombre,
            p.stock,
            -- Promedio diario basado en 7 días para ser más 'constante'
            IFNULL(SUM(vd.cantidad) / 7, 0) AS promedio_diario,
            -- Días en los que se vendió en los últimos 7 días
            COUNT(DISTINCT DATE(v.fecha)) AS dias_vendidos_7
        FROM productos p
        LEFT JOIN ventas_detalle vd ON p.id = vd.producto_id
        LEFT JOIN ventas v ON vd.venta_id = v.id AND v.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY p.id
        HAVING p.stock > 0 -- Solo productos con stock actual > 0
    ");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos as $producto) {
        $stock_actual = (int)$producto['stock'];
        $promedio_diario = (float)$producto['promedio_diario'];
        $dias_vendidos_7 = (int)$producto['dias_vendidos_7'];

        // CÁLCULO 1: Cantidad de stock mínima deseada (cubrir 2 días de venta promedio)
        $stock_critico = $promedio_diario * 2; 

        // CÁLCULO 2: Riesgo de stock bajo (Stock actual < Stock crítico)
        $riesgo_stock = ($stock_actual < $stock_critico);
        
        // CÁLCULO 3: Probabilidad de venta para un día aleatorio (en porcentaje)
        // Porcentaje de días vendidos en la última semana
        $probabilidad_venta = ($dias_vendidos_7 / $dias_a_monitorear) * 100;

        // Solo se muestran productos en RIESGO REAL y con probabilidad de venta significativa (> 5%)
        if ($riesgo_stock && $probabilidad_venta > 5) {
            $results[] = [
                'producto' => htmlspecialchars($producto['nombre']),
                'stock' => $stock_actual,
                'riesgo_porcentaje' => round($probabilidad_venta, 1),
                'promedio_diario' => round($promedio_diario, 2),
                'stock_critico_num' => round($stock_critico, 0),
                'mensaje_riesgo' => "Se espera vender $\approx$ " . round($promedio_diario, 1) . " u/día. Sugerencia: Abastecer hasta " . round($stock_critico * 2, 0) . " unidades.",
            ];
        }
    }

} catch (\PDOException $e) {
    header('Content-Type: application/json');
    // Si la tabla productos o ventas no existe aún, devolvemos un error informativo
    echo json_encode(['error' => 'Error de Base de Datos (SQLSTATE). Asegúrese de haber creado la BD e importado el script SQL.']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($results);
?>