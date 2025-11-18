<?php
require 'includes/db.php';
session_start();

// Asegurarnos de que el usuario esté logueado
if(!isset($_SESSION['user'])){
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Fecha actual
$hoy = date('Y-m-d');

// Obtener datos del usuario
$usuario_id = $_SESSION['user']['id'];
$rol = $_SESSION['user']['rol'];

// Preparar consulta
if($rol === 'admin' || $rol === 'programador'){
    $stmt = $pdo->prepare("SELECT COUNT(*) AS ventas_dia FROM ventas WHERE DATE(fecha) = ?");
    $stmt->execute([$hoy]);
} else {
    // Solo ventas del vendedor
    $stmt = $pdo->prepare("SELECT COUNT(*) AS ventas_dia FROM ventas WHERE DATE(fecha) = ? AND usuario_id = ?");
    $stmt->execute([$hoy, $usuario_id]);
}

$result = $stmt->fetch(PDO::FETCH_ASSOC);

// Devolver JSON
header('Content-Type: application/json');
echo json_encode([
    'ventas_dia' => (int)$result['ventas_dia']
]);

?>