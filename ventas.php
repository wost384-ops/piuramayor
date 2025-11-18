<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['user']['id'];

// --- AGREGAR VENTA ---
if (isset($_POST['agregar'])) {
    $cliente_nombre = trim($_POST['cliente_nombre']);
    $productos = $_POST['productos'] ?? [];
    $cantidades = $_POST['cantidades'] ?? [];

    if (!$cliente_nombre || empty($productos)) {
        $mensaje = "Debes seleccionar un cliente y al menos un producto";
    } else {
        // Buscar ID del cliente
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE nombre = ?");
        $stmt->execute([$cliente_nombre]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre) VALUES (?)");
            $stmt->execute([$cliente_nombre]);
            $cliente_id = $pdo->lastInsertId();
        } else {
            $cliente_id = $cliente['id'];
        }

        // Calcular total
        $total = 0;
        foreach ($productos as $index => $prod_id) {
            $stmt = $pdo->prepare("SELECT precio FROM productos WHERE id = ?");
            $stmt->execute([$prod_id]);
            $precio = $stmt->fetchColumn();
            $total += $precio * $cantidades[$index];
        }

        // Contar ventas del día
        $fecha = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) AS ventas_hoy FROM ventas WHERE DATE(fecha) = ?");
        $stmt->execute([$fecha]);
        $ventasHoy = $stmt->fetch(PDO::FETCH_ASSOC)['ventas_hoy'] ?? 0;
        $numeroDia = $ventasHoy + 1;

        // Insertar venta
        $stmt = $pdo->prepare("INSERT INTO ventas (cliente_id, total, fecha, usuario_id, numero_dia) VALUES (?, ?, NOW(), ?, ?)");
        $stmt->execute([$cliente_id, $total, $usuario_id, $numeroDia]);
        $venta_id = $pdo->lastInsertId();

        // Insertar detalle venta
        foreach ($productos as $index => $prod_id) {
            $stmt = $pdo->prepare("SELECT precio FROM productos WHERE id = ?");
            $stmt->execute([$prod_id]);
            $precio = $stmt->fetchColumn();

            $stmt = $pdo->prepare("INSERT INTO ventas_detalle (venta_id, producto_id, cantidad, precio_unit) VALUES (?, ?, ?, ?)");
            $stmt->execute([$venta_id, $prod_id, $cantidades[$index], $precio]);
        }

        header("Location: ventas.php");
        exit;
    }
}

// --- OBTENER VENTAS ---
$ventas = $pdo->query("
    SELECT v.*, c.nombre AS cliente_nombre, u.nombre AS usuario_nombre
    FROM ventas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    ORDER BY v.fecha DESC, v.numero_dia ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <h3 class="mb-4 text-center">Gestión de Ventas</h3>

    <!-- Agregar venta -->
    <div class="card mb-4 p-3 shadow-sm">
        <h5>➕ Agregar nueva venta</h5>
        <form method="POST" id="ventaForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" name="cliente_nombre" id="cliente_nombre" class="form-control" placeholder="Escriba nombre del cliente" autocomplete="off" required>
                    <div id="cliente_list" class="list-group"></div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <h6>Productos</h6>
                    <input type="text" id="buscar_producto" class="form-control" placeholder="Buscar producto...">
                    <div id="productos_list" class="list-group"></div>
                </div>
            </div>

            <table class="table table-sm mt-3" id="tablaProductos">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total:</th>
                        <th id="total">0.00</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>

            <button type="submit" name="agregar" class="btn btn-primary">Guardar venta</button>
        </form>
    </div>

    <!-- Lista de ventas -->
    <div class="card p-3 shadow-sm">
        <h5>💰 Lista de ventas</h5>
        <table class="table table-striped mt-3">
            <thead>
                <tr>
                    <th># Día</th>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ventas as $ven): ?>
                <tr>
                    <td><?= $ven['numero_dia'] ?></td>
                    <td><?= $ven['id'] ?></td>
                    <td><?= htmlspecialchars($ven['cliente_nombre'] ?: 'Sin nombre') ?></td>
                    <td><?= number_format($ven['total'], 2) ?></td>
                    <td><?= htmlspecialchars($ven['fecha']) ?></td>
                    <td><?= htmlspecialchars($ven['usuario_nombre']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let productosSeleccionados = [];

// Buscar productos
$('#buscar_producto').on('keyup', function() {
    let q = $(this).val();
    if(q.length < 1) { $('#productos_list').html(''); return; }

    $.getJSON('buscar_productos.php', {q: q}, function(data){
        let html = '';
        data.forEach(p => {
            html += `<a href="#" class="list-group-item list-group-item-action agregarProducto" data-id="${p.id}" data-nombre="${p.nombre}" data-precio="${p.precio}">${p.nombre} - S/ ${p.precio}</a>`;
        });
        $('#productos_list').html(html);
    });
});

// Agregar producto a tabla
$(document).on('click', '.agregarProducto', function(e){
    e.preventDefault();
    let id = $(this).data('id');
    let nombre = $(this).data('nombre');
    let precio = parseFloat($(this).data('precio'));

    if(productosSeleccionados.includes(id)) return;
    productosSeleccionados.push(id);

    $('#tablaProductos tbody').append(`
        <tr data-id="${id}">
            <td>${nombre}<input type="hidden" name="productos[]" value="${id}"></td>
            <td>${precio.toFixed(2)}</td>
            <td><input type="number" name="cantidades[]" value="1" min="1" class="form-control cantidad"></td>
            <td class="subtotal">${precio.toFixed(2)}</td>
            <td><button class="btn btn-sm btn-danger quitar">X</button></td>
        </tr>
    `);
    actualizarTotal();
});

// Quitar producto
$(document).on('click', '.quitar', function(){
    let row = $(this).closest('tr');
    let id = row.data('id');
    productosSeleccionados = productosSeleccionados.filter(p => p != id);
    row.remove();
    actualizarTotal();
});

// Cambiar cantidad
$(document).on('input', '.cantidad', function(){
    let row = $(this).closest('tr');
    let precio = parseFloat(row.find('td:eq(1)').text());
    let cantidad = parseInt($(this).val());
    row.find('.subtotal').text((precio*cantidad).toFixed(2));
    actualizarTotal();
});

function actualizarTotal() {
    let total = 0;
    $('#tablaProductos tbody tr').each(function(){
        total += parseFloat($(this).find('.subtotal').text());
    });
    $('#total').text(total.toFixed(2));
}

// Buscar cliente (autocomplete)
$('#cliente_nombre').on('keyup', function(){
    let q = $(this).val();
    if(q.length < 1) { $('#cliente_list').html(''); return; }

    $.getJSON('buscar_clientes.php', {q: q}, function(data){
        let html = '';
        data.forEach(c => {
            html += `<a href="#" class="list-group-item list-group-item-action seleccionarCliente" data-nombre="${c.nombre}">${c.nombre}</a>`;
        });
        $('#cliente_list').html(html);
    });
});

$(document).on('click', '.seleccionarCliente', function(e){
    e.preventDefault();
    let nombre = $(this).data('nombre');
    $('#cliente_nombre').val(nombre);
    $('#cliente_list').html('');
});
</script>

<?php include 'includes/footer.php'; ?>
