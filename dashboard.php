<?php
require 'includes/db.php';
require 'includes/auth_check.php';
include 'includes/header.php'; // Aquí se definen los permisos

$rol = $_SESSION['user']['rol'] ?? 'vendedor';
$usuario_id = $_SESSION['user']['id']; 

// =================================================================
// CÁLCULO DE VENTAS (CONDICIONAL POR ROL)
// =================================================================

// Admin/Programador: Muestra el TOTAL de ventas del sistema.
// Vendedor: Muestra solo las ventas registradas por su ID.
if($can_manage_finance_menus){
    $queryVentas = "SELECT COUNT(*) FROM ventas";
    $paramsVentas = [];
} else {
    $queryVentas = "SELECT COUNT(*) FROM ventas WHERE usuario_id = ?";
    $paramsVentas = [$usuario_id];
}
$stmtVentas = $pdo->prepare($queryVentas);
$stmtVentas->execute($paramsVentas);
$totalVentas = $stmtVentas->fetchColumn(); 

// =================================================================
// RESTO DE CÁLCULOS DEL DASHBOARD
// =================================================================

$totalProductos = $totalClientes = $ingresos = 0;

// Métrica de gestión básica (Vendedor/Admin/Programador)
if($can_manage_basic_inventory){
    $totalProductos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    $totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
}

// Métrica de INGRESOS (Ahora visible para VENDEDOR también)
if($can_see_daily_income_card){ 
    // Consulta INGRESOS: Muestra el TOTAL de ingresos de la COMPAÑÍA hoy.
    $ingresos = $pdo->query("SELECT IFNULL(SUM(total),0) FROM ventas WHERE DATE(fecha) = CURDATE()")->fetchColumn();
    
    // Ventas por mes (Solo Admin/Programador - sigue usando can_manage_finance_menus)
    $mesesEspanol = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    $ventasLabels = []; 
    $ventasData = [];  
    
    if ($can_manage_finance_menus) { // Esta restricción se mantiene para el gráfico
        $rawVentasMes = $pdo->query("
            SELECT 
                MONTH(fecha) AS mes_num, 
                SUM(total) AS total
            FROM ventas
            GROUP BY MONTH(fecha)
            ORDER BY MONTH(fecha)
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rawVentasMes as $v) {
            $ventasLabels[] = $mesesEspanol[(int)$v['mes_num']];
            $ventasData[] = $v['total'];
        }
    }
}

// Productos con stock bajo (solo gestores)
$stockBajo = [];
if($can_manage_basic_inventory){
    $stockBajo = $pdo->query("SELECT * FROM productos WHERE stock < 10 ORDER BY stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
}

// Últimas ventas (todos pueden ver) con vendedor - LIMIT 5
$ultimasVentas = $pdo->query("
  SELECT v.id, v.total, v.fecha, c.nombre AS cliente, u.nombre AS vendedor, v.numero_dia
  FROM ventas v
  LEFT JOIN clientes c ON v.cliente_id = c.id
  LEFT JOIN usuarios u ON v.usuario_id = u.id
  ORDER BY v.id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
  <h2 class="mb-4">📊 Dashboard</h2>

  <div class="row g-3 mb-4 justify-content-start">
    
    <?php if($can_see_daily_income_card): // Tarjeta Ingresos AHORA visible para Vendedor ?>
    
    <div class="col-md-3"> 
      <div class="card text-center p-3">
        <h5>Ingresos (Hoy)</h5>
        <h2 class="text-danger">S/ <?= number_format($ingresos,2) ?></h2>
      </div>
    </div>
    
    <?php endif; ?>

    <?php if($can_manage_basic_inventory): // Tarjetas Productos/Clientes para Vendedor/Admin/Programador ?>
    
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
  
  <?php if($can_manage_basic_inventory): // La barra predictiva es visible al Vendedor/Gestor de Stock ?>
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
              <th># Día</th>
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
                <td><?= $v['numero_dia'] ?></td>
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

  <?php if($can_manage_finance_menus): // Gráfico de Ventas por Mes solo para Admin/Programador ?>
  <div class="card p-3 mb-4">
    <h5>📈 Ventas por Mes</h5>
    <canvas id="graficoVentas"></canvas>
  </div>
  <?php endif; ?>
</div>

<?php if($can_manage_finance_menus): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Lógica de Chart.js
const ctx = document.getElementById('graficoVentas').getContext('2d');
const data = {
  // Las etiquetas ahora vienen del array de PHP mapeado a español
  labels: [<?php echo implode(',', array_map(function($label) { return "'" . $label . "'"; }, $ventasLabels)); ?>],
  datasets: [{
    label: 'Ventas S/ ',
    data: [<?php echo implode(',', $ventasData); ?>],
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

<?php if($can_manage_basic_inventory): // El script de predicción solo se carga si es gestor/vendedor ?>
<script>
// --- FUNCIÓN DE PREDICCIÓN (Actualiza cada 5 segundos) ---
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
                    ✅ No hay productos con riesgo de agotamiento inminente.
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
                                    Probabilidad de Agotamiento: ${item.riesgo_porcentaje}%
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
setInterval(actualizarPrediccionStock, 5000); 
</script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>