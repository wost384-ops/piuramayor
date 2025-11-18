<?php
require 'includes/db.php';
require 'includes/auth_check.php';
include 'includes/header.php';

$rol = $_SESSION['user']['rol'] ?? 'vendedor';

// Tarjetas resumen
$totalProductos = $totalClientes = $ingresos = 0;
$totalVentas = $pdo->query("SELECT COUNT(*) FROM ventas")->fetchColumn();

// Solo admin/programador ven productos, clientes e ingresos
if($rol === 'admin' || $rol === 'programador'){
    $totalProductos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    $totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $ingresos = $pdo->query("SELECT IFNULL(SUM(total),0) FROM ventas")->fetchColumn();
}

// Ventas del día
$ventasDia = $pdo->query("SELECT COUNT(*) FROM ventas WHERE DATE(fecha) = CURDATE()")->fetchColumn();

// Productos con stock bajo (solo admin/programador)
$stockBajo = [];
if($rol === 'admin' || $rol === 'programador'){
    $stockBajo = $pdo->query("SELECT * FROM productos WHERE stock < 10 ORDER BY stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
}

// Últimas ventas (todos pueden ver) con vendedor
$ultimasVentas = $pdo->query("
  SELECT v.id, v.total, v.fecha, c.nombre AS cliente, u.nombre AS vendedor
  FROM ventas v
  LEFT JOIN clientes c ON v.cliente_id = c.id
  LEFT JOIN usuarios u ON v.usuario_id = u.id
  ORDER BY v.id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Ventas por mes (solo admin/programador)
$ventasMes = [];
if($rol === 'admin' || $rol === 'programador'){
    $ventasMes = $pdo->query("
        SELECT DATE_FORMAT(fecha, '%M') AS mes, SUM(total) AS total
        FROM ventas
        GROUP BY MONTH(fecha)
        ORDER BY MONTH(fecha)
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container mt-4">
  <h2 class="mb-4">📊 Dashboard</h2>

  <div class="row g-3 mb-4">
    <!-- Ventas del día -->
    <div class="col-md-3">
      <div class="card shadow-sm text-center p-3 border-info">
        <h5>Ventas del día</h5>
        <h2 class="text-info" id="ventasDia"><?= $ventasDia ?></h2>
      </div>
    </div>

    <?php if($rol === 'admin' || $rol === 'programador'): ?>
    <div class="col-md-3">
      <div class="card shadow-sm text-center p-3 border-primary">
        <h5>Productos</h5>
        <h2 class="text-primary"><?= $totalProductos ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm text-center p-3 border-success">
        <h5>Clientes</h5>
        <h2 class="text-success"><?= $totalClientes ?></h2>
      </div>
    </div>
    <?php endif; ?>

    <div class="col-md-3">
      <div class="card shadow-sm text-center p-3 border-warning">
        <h5>Ventas</h5>
        <h2 class="text-warning"><?= $totalVentas ?></h2>
      </div>
    </div>

    <?php if($rol === 'admin' || $rol === 'programador'): ?>
    <div class="col-md-3">
      <div class="card shadow-sm text-center p-3 border-danger">
        <h5>Ingresos</h5>
        <h2 class="text-danger">S/ <?= number_format($ingresos,2) ?></h2>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Últimas ventas -->
  <div class="card shadow-sm p-3 mb-4">
    <h5>🛒 Últimas ventas</h5>
    <table class="table table-sm table-striped">
      <thead>
        <tr>
          <th>ID Venta</th>
          <th>Cliente</th>
          <th>Vendedor</th>
          <th>Total</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ultimasVentas as $v): ?>
          <tr>
            <td><?= $v['id'] ?></td>
            <td><?= htmlspecialchars($v['cliente'] ?: 'Sin nombre') ?></td>
            <td><?= htmlspecialchars($v['vendedor'] ?: 'Desconocido') ?></td>
            <td>S/ <?= number_format($v['total'],2) ?></td>
            <td><?= $v['fecha'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if($rol === 'admin' || $rol === 'programador'): ?>
  <!-- Gráfico -->
  <div class="card shadow-sm p-3 mb-4">
    <h5>📈 Ventas por Mes</h5>
    <canvas id="graficoVentas"></canvas>
  </div>
  <?php endif; ?>
</div>

<?php if($rol === 'admin' || $rol === 'programador'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('graficoVentas').getContext('2d');
const data = {
  labels: [<?php foreach ($ventasMes as $v) { echo "'" . $v['mes'] . "',"; } ?>],
  datasets: [{
    label: 'Ventas S/ ',
    data: [<?php foreach ($ventasMes as $v) { echo $v['total'] . ","; } ?>],
    borderWidth: 2,
    backgroundColor: 'rgba(54, 162, 235, 0.5)',
    borderColor: 'rgba(54, 162, 235, 1)'
  }]
};
new Chart(ctx, {
  type: 'bar',
  data: data,
  options: {
    responsive: true,
    plugins: { legend: { display: false }},
    scales: { y: { beginAtZero: true }}
  }
});
</script>
<?php endif; ?>

<script>
function actualizarVentasDia() {
  fetch('ventas_dia.php')
    .then(response => response.json())
    .then(data => {
      document.getElementById('ventasDia').textContent = data.ventas_dia;
    })
    .catch(err => console.error('Error al obtener ventas del día:', err));
}

actualizarVentasDia();
setInterval(actualizarVentasDia, 5000);
</script>

<?php include 'includes/footer.php'; ?>
