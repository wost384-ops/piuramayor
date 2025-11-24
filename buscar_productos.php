<?php
// Este archivo NO debe tener restricciones de sesión/rol, ya que alimenta la búsqueda de productos para VENDER (ventas.php), la cual sí es accesible por el Vendedor.
require 'includes/db.php';

$q = $_GET['q'] ?? '';

if($q){
   
    $stmt = $pdo->prepare("SELECT id, nombre, precio, stock FROM productos WHERE nombre LIKE ? LIMIT 10"); 
    $stmt->execute(["%$q%"]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($productos);
} else {
    echo json_encode([]);
}
?>