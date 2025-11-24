<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['user']['id'];
$rol = $_SESSION['user']['rol'] ?? 'vendedor';
$mensaje = '';
$can_manage = ($rol === 'admin' || $rol === 'programador'); // Variable para simplificar la verificación de permisos

// --- AGREGAR VENTA (Acceso para todos) ---
if (isset($_POST['agregar'])) {
    $cliente_nombre = trim($_POST['cliente_nombre']);
    $cliente_telefono = trim($_POST['cliente_telefono']); // <-- NUEVO: Capturamos el teléfono
    $productos = $_POST['productos'] ?? [];
    $cantidades = $_POST['cantidades'] ?? [];

    if (!$cliente_nombre || empty($productos)) {
        $mensaje = "Debes seleccionar un cliente y al menos un producto";
    } else {
        $pudo_vender = true;
        $error_stock = '';

        // PRIMERA VERIFICACIÓN: Stock suficiente
        foreach ($productos as $index => $prod_id) {
            $cantidad_solicitada = intval($cantidades[$index]);
            
            // Obtener stock actual del producto
            $stmt_stock = $pdo->prepare("SELECT stock, nombre FROM productos WHERE id = ?");
            $stmt_stock->execute([$prod_id]);
            $producto_info = $stmt_stock->fetch(PDO::FETCH_ASSOC);

            if ($producto_info) {
                // VERIFICACIÓN CRÍTICA EN PHP
                if ($cantidad_solicitada > $producto_info['stock']) {
                    $error_stock = "Stock insuficiente para: " . htmlspecialchars($producto_info['nombre']) . ". Solicitado: {$cantidad_solicitada}, Disponible: {$producto_info['stock']}.";
                    $pudo_vender = false;
                    break;
                }
            }
        }
        
        if (!$pudo_vender) {
            $mensaje = "❌ Error: " . $error_stock;
        } else {
            // Lógica para encontrar o crear cliente
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE nombre = ?");
            $stmt->execute([$cliente_nombre]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cliente) {
                // CORRECCIÓN: Insertamos el nuevo cliente con el teléfono capturado
                $stmt = $pdo->prepare("INSERT INTO clientes (nombre, telefono) VALUES (?, ?)");
                $stmt->execute([$cliente_nombre, $cliente_telefono]); 
                $cliente_id = $pdo->lastInsertId();
            } else {
                $cliente_id = $cliente['id'];
                // Opcional: Podrías actualizar el teléfono si el campo no está vacío
            }

            // Calcular total y registrar venta (TRANSACTION recomendado para estos casos)
            try {
                $pdo->beginTransaction();

                $total = 0;
                foreach ($productos as $index => $prod_id) {
                    $stmt = $pdo->prepare("SELECT precio FROM productos WHERE id = ?");
                    $stmt->execute([$prod_id]);
                    $precio = $stmt->fetchColumn();
                    $total += $precio * $cantidades[$index];
                }

                // Contar ventas del día (usando CURDATE() para sincronizar)
                $stmt = $pdo->prepare("SELECT COUNT(*) AS ventas_hoy FROM ventas WHERE DATE(fecha) = CURDATE()");
                $stmt->execute();
                $ventasHoy = $stmt->fetch(PDO::FETCH_ASSOC)['ventas_hoy'] ?? 0;
                $numeroDia = $ventasHoy + 1;

                // Insertar venta
                $stmt = $pdo->prepare("INSERT INTO ventas (cliente_id, total, fecha, usuario_id, numero_dia) VALUES (?, ?, NOW(), ?, ?)");
                $stmt->execute([$cliente_id, $total, $usuario_id, $numeroDia]);
                $venta_id = $pdo->lastInsertId();

                // Insertar detalle venta y ACTUALIZAR STOCK
                foreach ($productos as $index => $prod_id) {
                    $cantidad = intval($cantidades[$index]);
                    
                    $stmt = $pdo->prepare("SELECT precio FROM productos WHERE id = ?");
                    $stmt->execute([$prod_id]);
                    $precio = $stmt->fetchColumn();

                    // Insertar detalle
                    $stmt = $pdo->prepare("INSERT INTO ventas_detalle (venta_id, producto_id, cantidad, precio_unit) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$venta_id, $prod_id, $cantidad, $precio]);

                    // ACTUALIZAR STOCK: Restar la cantidad vendida
                    $stmt_stock_update = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                    $stmt_stock_update->execute([$cantidad, $prod_id]);
                }

                $pdo->commit();
                $mensaje = "✅ Venta registrada correctamente.";
                // Redirigir a la misma página para limpiar el POST y ver el mensaje
                header("Location: ventas.php?mensaje=" . urlencode($mensaje));
                exit;

            } catch (\Exception $e) {
                $pdo->rollBack();
                $mensaje = "❌ Error al procesar la venta: " . $e->getMessage();
            }
        }
    }
}

// Mostrar mensaje si existe en la URL después de la redirección
if (isset($_GET['mensaje'])) {
    $mensaje = htmlspecialchars(urldecode($_GET['mensaje']));
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

    <?php if($mensaje): ?>
        <div class="alert <?= strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-danger' ?>"><?= $mensaje ?></div>
    <?php endif; ?>

    <div class="card mb-4 p-3 shadow-sm">
        <h5>➕ Agregar nueva venta</h5>
        <form method="POST" id="ventaForm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" name="cliente_nombre" id="cliente_nombre" class="form-control" placeholder="Escriba nombre del cliente" autocomplete="off" required>
                    <div id="cliente_list" class="list-group"></div>
                </div>
                <div class="col-md-6">
                    <input type="text" name="cliente_telefono" id="cliente_telefono" class="form-control" placeholder="Teléfono (9 dígitos, opcional)" maxlength="9">
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

    <div class="card p-3 shadow-sm">
        <h5>💰 Lista de ventas</h5>
        <div class="table-responsive">
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th># Día</th>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <?php if ($can_manage): // COLUMNA ACCIONES SOLO PARA ADMIN/PROGRAMADOR ?>
                        <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventas as $ven): ?>
                    <tr>
                        <td><?= $ven['numero_dia'] ?></td>
                        <td><?= $ven['id'] ?></td>
                        <td><?= htmlspecialchars($ven['cliente_nombre'] ?: 'Sin nombre') ?></td>
                        <td>S/ <?= number_format($ven['total'], 2) ?></td>
                        <td><?= htmlspecialchars(substr($ven['fecha'], 0, 10)) ?></td>
                        <td><?= htmlspecialchars($ven['usuario_nombre']) ?></td>
                        <?php if ($can_manage): // BOTÓN EDITAR SOLO PARA ADMIN/PROGRAMADOR ?>
                        <td>
                            <a href="editar_venta.php?id=<?= $ven['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let productosSeleccionados = {};

// Buscar productos (Se corrigió para incluir data-stock en el link)
$('#buscar_producto').on('keyup', function() {
    let q = $(this).val();
    if(q.length < 1) { $('#productos_list').html(''); return; }

    $.getJSON('buscar_productos.php', {q: q}, function(data){
        let html = '';
        data.forEach(p => {
            // Se añade data-stock al elemento <a>
            html += `<a href="#" class="list-group-item list-group-item-action agregarProducto" 
                     data-id="${p.id}" data-nombre="${p.nombre}" data-precio="${p.precio}" data-stock="${p.stock}">
                     ${p.nombre} - S/ ${p.precio} (Stock: ${p.stock})
                     </a>`;
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
    let stock = parseInt($(this).data('stock')); 

    if(productosSeleccionados[id]) return; 

    if (stock < 1) {
        alert("No se puede agregar el producto: Stock agotado.");
        return;
    }

    productosSeleccionados[id] = 1;

    $('#productos_list').html('');
    $('#buscar_producto').val('');

    // Se añade data-stock a la fila <tr> y max="" al input
    $('#tablaProductos tbody').append(`
        <tr data-id="${id}" data-stock="${stock}">
            <td>${nombre}<input type="hidden" name="productos[]" value="${id}"></td>
            <td>${precio.toFixed(2)}</td>
            <td><input type="number" name="cantidades[]" value="1" min="1" max="${stock}" class="form-control cantidad"></td>
            <td class="subtotal">${precio.toFixed(2)}</td>
            <td><button type="button" class="btn btn-sm btn-danger quitar">X</button></td>
        </tr>
    `);
    actualizarTotal();
});

// Cambiar cantidad (función que maneja el input y valida el stock)
$(document).on('input', '.cantidad', function(){
    let row = $(this).closest('tr');
    let precio = parseFloat(row.find('td:eq(1)').text());
    let stockMax = parseInt(row.data('stock')); 

    let cantidad = parseInt($(this).val()); 
    
    if (isNaN(cantidad) || cantidad < 1) {
        $(this).val(1); 
        cantidad = 1; 
    }
    
    if (cantidad > stockMax) {
        alert(`Stock insuficiente. Solo hay ${stockMax} unidades disponibles.`);
        $(this).val(stockMax);
        cantidad = stockMax;
    }

    row.find('.subtotal').text((precio * cantidad).toFixed(2));
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
            // CONSIDERACIÓN: Al seleccionar un cliente existente, se debería prellenar el teléfono.
            // Para simplificar, solo mostramos el nombre
            html += `<a href="#" class="list-group-item list-group-item-action seleccionarCliente" data-nombre="${c.nombre}" data-telefono="${c.telefono}">${c.nombre}</a>`;
        });
        $('#cliente_list').html(html);
    });
});

$(document).on('click', '.seleccionarCliente', function(e){
    e.preventDefault();
    let nombre = $(this).data('nombre');
    let telefono = $(this).data('telefono'); // Capturamos el teléfono
    
    $('#cliente_nombre').val(nombre);
    $('#cliente_list').html('');
    
    // Si el cliente ya existe, rellenamos el campo de teléfono
    if (telefono) {
        $('#cliente_telefono').val(telefono);
    } else {
        $('#cliente_telefono').val(''); // Si es nulo, lo limpiamos
    }
});
</script>

<?php include 'includes/footer.php'; ?>
