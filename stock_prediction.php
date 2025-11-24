<?php
// Motor de Predicción de Stock y Probabilidad de Venta (Actualización cada 30 minutos)
require 'includes/db.php';

$results = [];
// Nivel: Si 3 o más clientes lo compraron hoy, se considera un "surge".
$SURGE_TRANSACTIONS_THRESHOLD = 3; 
// Nivel: Solo alerta si el stock restante es menor a 100 unidades.
$MAX_STOCK_FOR_ALERT = 100;        

try {
    // 1. Obtener datos de ventas y stock para el día de hoy
    $stmt = $pdo->prepare("
        SELECT
            p.id AS producto_id,
            p.nombre,
            p.stock,
            /* Suma de unidades vendidas HOY */
            SUM(CASE WHEN DATE(v.fecha) = CURDATE() THEN vd.cantidad ELSE 0 END) AS unidades_vendidas_hoy,
            /* Conteo de transacciones (clientes) que incluyeron el producto HOY */
            COUNT(DISTINCT v.id) AS transacciones_hoy
        FROM productos p
        LEFT JOIN ventas_detalle vd ON p.id = vd.producto_id
        LEFT JOIN ventas v ON vd.venta_id = v.id AND DATE(v.fecha) = CURDATE()
        GROUP BY p.id, p.nombre, p.stock
        HAVING p.stock > 0
    ");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos as $producto) {
        $stock_actual = (int)$producto['stock'];
        $unidades_vendidas_hoy = (float)$producto['unidades_vendidas_hoy'];
        $transacciones_hoy = (int)$producto['transacciones_hoy'];

        // CÁLCULO 1: RIESGO DE SURGE (Alta demanda HOY + Stock moderadamente bajo)
        $is_surge_risk = (
            $transacciones_hoy >= $SURGE_TRANSACTIONS_THRESHOLD && // 3 o más clientes lo compraron hoy
            $stock_actual < $MAX_STOCK_FOR_ALERT // Y el stock restante es menor a 100 unidades
        );
        
        // CÁLCULO 2: Probabilidad de Agotamiento (Porcentaje del stock vendido hoy)
        // Mide qué tanto se ha agotado el stock con la venta de hoy.
        $probabilidad_agotamiento = ($stock_actual > 0 && $unidades_vendidas_hoy > 0) 
            ? round(($unidades_vendidas_hoy / $stock_actual) * 100, 1) 
            : 0;

        // Si se cumple el riesgo de surge Y se ha vendido algo hoy (Probabilidad > 0)
        if ($is_surge_risk && $probabilidad_agotamiento > 0) {
            $results[] = [
                'producto' => htmlspecialchars($producto['nombre']),
                'stock' => $stock_actual,
                'riesgo_porcentaje' => $probabilidad_agotamiento,
                'promedio_diario' => $unidades_vendidas_hoy, 
                'stock_critico_num' => $MAX_STOCK_FOR_ALERT,
                
                // MENSAJE CORREGIDO: Usamos "ALERTA DE ALTA DEMANDA HOY"
                'mensaje_riesgo' => "ALERTA DE ALTA DEMANDA HOY: {$transacciones_hoy} ventas hoy. Se vendió el {$probabilidad_agotamiento}% del stock actual. Abastecer pronto.",
            ];
        }
    }

} catch (\PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error de Base de Datos (SQLSTATE).']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($results);
?>