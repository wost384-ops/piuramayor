<?php
require 'includes/db.php';

$q = $_GET['q'] ?? '';

if($q){
    $stmt = $pdo->prepare("SELECT id, nombre FROM clientes WHERE nombre LIKE ? LIMIT 10");
    $stmt->execute(["%$q%"]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($clientes);
} else {
    echo json_encode([]);
}
