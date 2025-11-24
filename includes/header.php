<?php
// Incluir la conexión a DB si no está ya, para usar $pdo en la lógica de alertas
if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'includes/db.php'; 

// Asegurar que la sesión exista
if (!isset($_SESSION['user'])) {
    if (basename($_SERVER['PHP_SELF']) !== 'login.php' && basename($_SERVER['PHP_SELF']) !== 'index.php') {
        header('Location: login.php');
        exit;
    }
}

$rol = $_SESSION['user']['rol'] ?? 'vendedor';

// =================================================================
// 1. DEFINICIÓN DE PERMISOS AJUSTADA
// =================================================================
// Nivel 1: Acceso a Inventario Básico (Productos y Alertas/Barra Predictiva)
$can_manage_basic_inventory = ($rol === 'admin' || $rol === 'programador' || $rol === 'vendedor');

// Nivel 2: Acceso a Finanzas, Proveedores, Cierre de Caja y Reportes (Menús Ocultos al Vendedor)
$can_manage_finance_menus = ($rol === 'admin' || $rol === 'programador');

// PERMISO AÑADIDO: Para mostrar la tarjeta de Ingresos en el Dashboard
$can_see_daily_income_card = ($rol === 'admin' || $rol === 'programador' || $rol === 'vendedor');

// =================================================================

// Determinar fondo según sesión
$backgroundImage = isset($_SESSION['user']) ? 'fondo.jpg' : 'fondo2.jpg';

// Conexión a la base de datos para contar productos con stock bajo y total en stock
$alertaStock = 0;
$stockDisponible = 1; 
if(isset($pdo)){
    try {
        if($can_manage_basic_inventory){ 
             $stmt = $pdo->query("SELECT COUNT(*) AS bajo FROM productos WHERE stock <= 5");
             $alertaStock = $stmt->fetch(PDO::FETCH_ASSOC)['bajo'] ?? 0;
        }

        $stmt2 = $pdo->query("SELECT COUNT(*) AS total FROM productos WHERE stock > 0");
        $stockDisponible = $stmt2->fetch(PDO::FETCH_ASSOC)['total'] > 0 ? 1 : 0;
    } catch (\PDOException $e) {
        $alertaStock = 0;
        $stockDisponible = 1;
    }
}

// Función para determinar si el link está activo (soporta múltiples nombres de archivo)
function is_active($filenames) {
    $current_page = basename($_SERVER['PHP_SELF']);
    if (!is_array($filenames)) {
        $filenames = [$filenames];
    }
    foreach ($filenames as $filename) {
        if (strpos($current_page, $filename) !== false) {
            return 'active';
        }
    }
    return '';
}

// Lógica para mantener el menú de reportes abierto si se está en una página de reporte
$reportes_pages = ['producto_mas_vendido.php', 'cliente_frecuente.php', 'ventas_vendedor.php'];
$is_reporte_active = false;
foreach ($reportes_pages as $page) {
    if (strpos(basename($_SERVER['PHP_SELF']), $page) !== false) {
        $is_reporte_active = true;
        break;
    }
}
$collapse_class = $is_reporte_active && $can_manage_finance_menus ? 'show' : '';
$aria_expanded = $is_reporte_active && $can_manage_finance_menus ? 'true' : 'false';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PiuraMayor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    /* Estilos del Body y Contenido General */
    body {
      background: url('img/<?= $backgroundImage ?>') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      margin: 0;
      font-family: Arial, sans-serif;
    }
    
    /* MODIFICACIÓN DE DISEÑO: SIDEBAR (Menú Lateral) */
    .sidebar { 
        width: 260px; 
        position: fixed; 
        top: 0; 
        left: 0; 
        height: 100vh; 
        background: #f8f9fa; 
        border-right: none;
        box-shadow: 2px 0 15px rgba(0,0,0,0.08); 
        padding: 25px 20px; 
        z-index: 1000; 
        overflow-y: auto; 
    }
    
    /* MODIFICACIÓN DE DISEÑO: CONTENIDO PRINCIPAL */
    .content { 
        margin-left: 280px;
        padding: 30px; 
        background: rgba(255,255,255,0.92); 
        border-radius: 15px; 
        min-height: calc(100vh - 60px); 
        box-shadow: 0 0 20px rgba(0,0,0,0.1); 
    }
    
    /* LINKS DE NAVEGACIÓN */
    .nav-link { 
        background: none;
        margin-bottom: 5px; 
        padding: 12px 15px;
        border-radius: 10px;
        border: none;
        color: #495057;
        font-weight: 500; 
        transition: all .2s ease; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
    }
    .nav-link:hover { 
        background: #e9ecef; 
        color: #007bff;
        transform: none; 
    }
    /* Se aplica a los enlaces activos (incluye los reportes activos) */
    .nav-link.active {
        background: #007bff;
        color: white;
        box-shadow: 0 4px 6px rgba(0, 123, 255, 0.2);
    }
    .nav-link.text-danger:hover { background: #f8d7da; color: #dc3545; border-color: #dc3545; transform: none; }
    .submenu { margin-left: 15px; }
    .submenu .nav-link { padding-left: 10px; font-size: 0.95rem; }
    .badge-stock { background:red; color:white; font-weight:bold; border-radius: 5px; padding: 3px 7px; }
    .disabled-link { pointer-events: none; opacity: 0.5; }

    /* CARDS DEL DASHBOARD Y CONTENIDO (Para que se noten) */
    .content .card { 
        border: none !important; 
        border-radius: 12px !important; 
        /* Aumentamos la sombra para que los recuadros se vean "levantados" */
        box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important; 
        transition: transform 0.3s ease;
    }
    .content .card:hover {
        transform: translateY(-3px);
    }
  </style>
</head>
<body>
<div class="sidebar">
  <h3 style="font-weight:700; letter-spacing:1px; margin-bottom: 25px;">
    <i class="bi bi-cart-check-fill" style="color:#007bff;"></i>
    <span style="color:#007bff;">Piura</span><span style="color:#333;">Mayor</span>
  </h3>
  <?php if(isset($_SESSION['user'])): ?>
    <p class="small text-muted" style="border-bottom: 1px solid #dee2e6; padding-bottom: 15px;">
      <?php
      // Lógica de Saludo Dinámico
      $hora = date('H');
      if ($hora >= 6 && $hora < 12) {
          $saludo = 'Buenos días';
      } elseif ($hora >= 12 && $hora < 19) {
          $saludo = 'Buenas tardes';
      } else {
          $saludo = 'Buenas noches';
      }
      ?>
      <?= $saludo ?>, <strong><?= htmlspecialchars($_SESSION['user']['nombre']) ?></strong>
      <br>
      <span class="text-muted small"><?= htmlspecialchars($_SESSION['user']['correo']) ?></span>
    </p>

    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?= is_active('dashboard.php') ?>" href="dashboard.php">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
          <?php if($alertaStock > 0 && $can_manage_basic_inventory): ?>
            <span class="badge-stock"><?= $alertaStock ?></span>
          <?php endif; ?>
        </a>
      </li>

      <?php if($can_manage_basic_inventory): // Productos y Alertas (Vendedor incluido) ?>
        <li class="nav-item">
          <a class="nav-link <?= is_active(['productos.php', 'editar_producto.php']) ?>" href="productos.php">
            <i class="bi bi-box-seam me-2"></i> Productos
            <?php if($alertaStock > 0): ?>
              <span class="badge-stock"><?= $alertaStock ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endif; ?>

      <?php if($can_manage_finance_menus): // Proveedores y Cierre de Caja (Vendedor EXCLUIDO) ?>
        <li class="nav-item">
            <a class="nav-link <?= is_active(['proveedores.php', 'editar_proveedores.php']) ?>" href="proveedores.php">
                <i class="bi bi-truck me-2"></i> Proveedores
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= is_active('cierre_caja.php') ?>" href="cierre_caja.php">
                <i class="bi bi-currency-dollar me-2"></i> Cierre de Caja
            </a>
        </li>
      <?php endif; ?>

      <li class="nav-item">
        <a class="nav-link <?= is_active(['clientes.php', 'editar_clientes.php']) ?>" href="clientes.php">
            <i class="bi bi-person-heart me-2"></i> Clientes
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link <?= is_active(['ventas.php', 'editar_venta.php']) ?> <?= $stockDisponible ? '' : 'disabled-link' ?>" href="ventas.php">
          <i class="bi bi-cash-stack me-2"></i> Ventas
          <?php if(!$stockDisponible): ?>
            <span class="badge-stock">Sin stock</span>
          <?php endif; ?>
        </a>
      </li>
      
      <?php if($can_manage_basic_inventory): // Alertas (Vendedor incluido) ?>
      <li class="nav-item">
        <a class="nav-link <?= is_active('alertas.php') ?>" href="alertas.php">
            <i class="bi bi-bell me-2"></i> Alertas
        </a>
      </li>
      <?php endif; ?>

      <?php if($can_manage_finance_menus): // Reportes (Vendedor EXCLUIDO) ?>
        <li class="nav-item">
          <a class="nav-link <?= $is_reporte_active ? 'active' : '' ?>" data-bs-toggle="collapse" href="#reportesSubmenu" role="button" aria-expanded="<?= $aria_expanded ?>" aria-controls="reportesSubmenu">
            <i class="bi bi-graph-up me-2"></i> Reportes <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
          </a>
          <div class="collapse submenu <?= $collapse_class ?>" id="reportesSubmenu">
            <a class="nav-link small <?= is_active('producto_mas_vendido.php') ?>" href="producto_mas_vendido.php">Producto más vendido</a>
            <a class="nav-link small <?= is_active('cliente_frecuente.php') ?>" href="cliente_frecuente.php">Cliente más frecuente</a>
            <a class="nav-link small <?= is_active('ventas_vendedor.php') ?>" href="ventas_vendedor.php">Ventas por vendedor</a>
          </div>
        </li>
      <?php endif; ?>

      <?php if($rol === 'programador'): ?>
        <li class="nav-item"><a class="nav-link text-danger" href="config.php"><i class="bi bi-gear me-2"></i> Configuración avanzada</a></li>
      <?php endif; ?>

      <li class="nav-item mt-4"><a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión</a></li>
    </ul>
  <?php else: ?>
    <a href="login.php" class="btn btn-primary w-100">Iniciar sesión</a>
  <?php endif; ?>
</div>

<div class="content">