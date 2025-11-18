<?php
require 'includes/db.php';

$q = $_GET['q'] ?? '';

if($q){
    $stmt = $pdo->prepare("SELECT id, nombre, precio FROM productos WHERE nombre LIKE ? LIMIT 10");
    $stmt->execute(["%$q%"]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($productos);
} else {
    echo json_encode([]);
}
