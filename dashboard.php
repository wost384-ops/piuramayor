<?php
require 'includes/db.php';
require 'includes/auth_check.php';
include 'includes/header.php';

$rol = $_SESSION['user']['rol'] ?? 'vendedor';
$is_manager = ($rol === 'admin' || $rol === 'programador'); // Manager role

// Tarjetas resumen
$totalProductos = $totalClientes = $ingresos = 0;
// Consulta TOTAL VENTAS (COUNT)
$totalVentas = $pdo->query("SELECT COUNT(*) FROM ventas")->fetchColumn();

// Métrica de gestión: Solo Manager
if($is_manager){
    $totalProductos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    $totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    
    // Consulta INGRESOS: Suma solo las ventas del día (CURDATE())
    $ingresos = $pdo->query("SELECT IFNULL(SUM(total),0) FROM ventas WHERE DATE(fecha) = CURDATE()")->fetchColumn();
}

// Productos con stock bajo (solo gestores)
$stockBajo = [];
if($is_manager){
    $stockBajo = $pdo->query("SELECT * FROM productos WHERE stock < 10 ORDER BY stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
}

// Últimas ventas (todos pueden ver) con vendedor - LIMIT 5
$ultimasVentas = $pdo->query("
  SELECT v.id, v.total, v.fecha, c.nombre AS cliente, u.nombre AS vendedor
  FROM ventas v
  LEFT JOIN clientes c ON v.cliente_id = c.id
  LEFT JOIN usuarios u ON v.usuario_id = u.id
  ORDER BY v.id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Ventas por mes (solo gestores)
$ventasMes = [];
if($is_manager){
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

  <div class="row g-3 mb-4 justify-content-start">
    
    <?php if($is_manager): ?>
    
    <div class="col-md-3"> 
      <div class="card text-center p-3">
        <h5>Ingresos (Hoy)</h5>
        <h2 class="text-danger">S/ <?= number_format($ingresos,2) ?></h2>
      </div>
    </div>
    
    <div class="col-md-3">
      <div class="card text-center p-3">
        <h5>Productos</h5>
        <h2 class="text-primary"><?= $totalProductos ?></h2>
      </div>
    </div>
    
    <div class="col-md-3">
      <div class="card text-center p-3">
        <h5>Clientes</h5>
        <h2 class="text-success"><?= $totalClientes ?></h2>
      </div>
    </div>
    
    <?php endif; ?>

    <div class="col-md-3"> 
      <div class="card text-center p-3">
        <h5>Ventas</h5>
        <h2 class="text-warning"><?= $totalVentas ?></h2>
      </div>
    </div>
  </div>
  
  <?php if($is_manager): ?>
  <div class="card p-3 mb-4">
    <h5>🚨 Alerta Predictiva de Stock Bajo <span class="small text-muted float-end" id="ultimaActualizacion"></span></h5>
    <div id="prediccionStockList">
        <div class="alert alert-info text-center mb-0">
            Cargando predicción...
        </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="card p-3 mb-4">
    <h5>🛒 Últimas ventas (Solo las últimas 5)</h5>
    <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead>
            <tr>
              <th>ID Venta</th>
              <th>Cliente</th>
              <th>Vendedor</th>
              <th>Total</th>
              <th>Fecha/Hora</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ultimasVentas as $v): ?>
              <tr>
                <td><?= $v['id'] ?></td>
                <td><?= htmlspecialchars($v['cliente'] ?: 'Sin nombre') ?></td>
                <td><?= htmlspecialchars($v['vendedor'] ?: 'Desconocido') ?></td>
                <td>S/ <?= number_format($v['total'],2) ?></td>
                <td><?= date('Y-m-d H:i', strtotime($v['fecha'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    </div>
  </div>

  <?php if($is_manager): ?>
  <div class="card p-3 mb-4">
    <h5>📈 Ventas por Mes</h5>
    <canvas id="graficoVentas"></canvas>
  </div>
  <?php endif; ?>
</div>

<?php if($is_manager): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Lógica de Chart.js
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

<script>
// --- FUNCIÓN DE PREDICCIÓN (Actualiza cada 30 minutos) ---
function actualizarPrediccionStock() {
    fetch('stock_prediction.php') 
        .then(response => response.json())
        .then(data => {
            let html = '';
            const listDiv = document.getElementById('prediccionStockList');
            const now = new Date();
            document.getElementById('ultimaActualizacion').textContent = 'Última actualización: ' + now.toLocaleTimeString();

            if (data.error) {
                 html = `<div class="alert alert-danger mb-0">Error: ${data.error}</div>`;
            } else if (data.length === 0) {
                html = `<div class="alert alert-success text-center mb-0">
                    ✅ No hay productos con riesgo de stock bajo.
                </div>`;
            } else {
                html = `<ul class="list-group list-group-flush">`;
                data.forEach(item => {
                    const color = item.riesgo_porcentaje > 80 ? 'danger' : (item.riesgo_porcentaje > 50 ? 'warning' : 'info');
                    
                    html += `
                        <li class="list-group-item list-group-item-${color}" style="border-radius: 0;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <strong>${item.producto}</strong> (Stock actual: ${item.stock})
                                </div>
                                <span class="badge bg-${color} rounded-pill">
                                    Probabilidad de Venta Hoy: ${item.riesgo_porcentaje}%
                                </span>
                            </div>
                            <small class="text-muted">${item.mensaje_riesgo}</small>
                        </li>
                    `;
                });
                html += `</ul>`;
            }
            listDiv.innerHTML = html;
        })
        .catch(err => {
            console.error('Error en la predicción de stock:', err);
            document.getElementById('prediccionStockList').innerHTML = '<div class="alert alert-danger mb-0">Error al cargar la predicción.</div>';
        });
}


// --- LÓGICA DE ACTUALIZACIÓN ---
actualizarPrediccionStock();
setInterval(actualizarPrediccionStock, 1800000); 
</script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>