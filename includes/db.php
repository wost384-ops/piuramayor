<?php
// === CRITICAL FIX: Establecer la Zona Horaria para PHP ===
// Esto asegura que date() y NOW() de PHP coincidan con la hora local de Lima/Piura (UTC-5)
date_default_timezone_set('America/Lima'); 

$host = '127.0.0.1';
$port = '3307'; // tu puerto XAMPP
$db   = 'piuramayor_db';
$user = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
