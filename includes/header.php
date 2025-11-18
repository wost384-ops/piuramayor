<?php
if (session_status() == PHP_SESSION_NONE) session_start();
$rol = $_SESSION['user']['rol'] ?? 'vendedor';

// Determinar fondo según sesión
$backgroundImage = isset($_SESSION['user']) ? 'fondo.jpg' : 'fondo2.jpg';

// Conexión a la base de datos para contar productos con stock bajo y total en stock
$alertaStock = 0;
$stockDisponible = 1; // Por defecto hay stock
if(isset($pdo) && ($rol === 'admin' || $rol === 'programador')){
    // Productos con stock crítico (≤5)
    $stmt = $pdo->query("SELECT COUNT(*) AS bajo FROM productos WHERE stock <= 5");
    $alertaStock = $stmt->fetch(PDO::FETCH_ASSOC)['bajo'] ?? 0;

    // Verificar si hay productos disponibles en stock
    $stmt2 = $pdo->query("SELECT COUNT(*) AS total FROM productos WHERE stock > 0");
    $stockDisponible = $stmt2->fetch(PDO::FETCH_ASSOC)['total'] > 0 ? 1 : 0;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PiuraMayor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: url('img/<?= $backgroundImage ?>') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      margin: 0;
      font-family: Arial, sans-serif;
    }
    .sidebar { width: 220px; position: fixed; top:0; left:0; height:100vh; background:#fff; border-right:1px solid #e6eef8; padding:20px; }
    .content { margin-left:240px; padding:30px; background: rgba(255,255,255,0.85); border-radius:10px; min-height: calc(100vh - 60px); }
    .nav-link { background: #f8fafc; margin-bottom: 8px; padding: 10px 12px; border-radius: 6px; border: 1px solid #d6e2f1; color: #333; font-weight: 500; transition: all .25s; display:flex; justify-content:space-between; align-items:center; }
    .nav-link:hover { background: #007bff; color: white; border-color: #007bff; transform: translateX(4px); }
    .nav-link.text-danger:hover { background: #dc3545; border-color: #dc3545; transform: translateX(4px); }
    .submenu { margin-left: 15px; }
    .badge-stock { background:red; color:white; font-weight:bold; }
    .disabled-link { pointer-events: none; opacity: 0.5; }
  </style>
</head>
<body>
<div class="sidebar">
  <h3 style="font-weight:600; letter-spacing:1px;">
    <i class="bi bi-cart-check-fill" style="color:#007bff;"></i>
    <span style="color:#007bff;">Piura</span><span style="color:#333;">Mayor</span>
  </h3>

  <?php if(isset($_SESSION['user'])): ?>
    <p class="small text-muted">
      <?php
      $hora = date('H');
      if ($hora < 12) $saludo = 'Buenos días';
      elseif ($hora < 18) $saludo = 'Buenas tardes';
      else $saludo = 'Buenas noches';
      ?>
      <?= $saludo ?>, <?= htmlspecialchars($_SESSION['user']['nombre']) ?>
      <br>
      <span class="text-muted small"><?= htmlspecialchars($_SESSION['user']['correo']) ?></span>
    </p>

    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link" href="dashboard.php">Dashboard
          <?php if($alertaStock > 0): ?>
            <span class="badge-stock"><?= $alertaStock ?></span>
          <?php endif; ?>
        </a>
      </li>

      <?php if($rol === 'admin' || $rol === 'programador'): ?>
        <li class="nav-item">
          <a class="nav-link" href="productos.php">Productos
            <?php if($alertaStock > 0): ?>
              <span class="badge-stock"><?= $alertaStock ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li class="nav-item"><a class="nav-link" href="proveedores.php">Proveedores</a></li>
      <?php endif; ?>

      <li class="nav-item"><a class="nav-link" href="clientes.php">Clientes</a></li>

      <li class="nav-item">
        <a class="nav-link <?= $stockDisponible ? '' : 'disabled-link' ?>" href="ventas.php">
          Ventas
          <?php if(!$stockDisponible): ?>
            <span class="badge-stock">Sin stock</span>
          <?php endif; ?>
        </a>
      </li>

      <?php if($rol === 'admin' || $rol === 'programador'): ?>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#reportesSubmenu" role="button">Reportes</a>
          <div class="collapse submenu" id="reportesSubmenu">
            <a class="nav-link" href="producto_mas_vendido.php">Producto más vendido</a>
            <a class="nav-link" href="cliente_frecuente.php">Cliente más frecuente</a>
            <a class="nav-link" href="ventas_vendedor.php">Ventas por vendedor</a>
          </div>
        </li>
      <?php endif; ?>

      <?php if($rol === 'programador'): ?>
        <li class="nav-item"><a class="nav-link text-danger" href="config.php">Configuración avanzada</a></li>
      <?php endif; ?>

      <li class="nav-item mt-3"><a class="nav-link text-danger" href="logout.php">Cerrar Sesión</a></li>
    </ul>
  <?php else: ?>
    <a href="login.php" class="btn btn-primary w-100">Iniciar sesión</a>
  <?php endif; ?>
</div>

<div class="content">
