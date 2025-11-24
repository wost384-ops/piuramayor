<?php
session_start();
require 'includes/db.php'; 

// === RESTRICCIÓN DE ACCESO ===
$rol = $_SESSION['user']['rol'] ?? '';
if ($rol !== 'admin' && $rol !== 'programador') {
    exit('Acceso denegado.');
}

// === INCLUSIÓN DE FPDF ===
// IMPORTANTE: Asegúrate de que la ruta sea correcta. (Debe ser fpdf/fpdf.php)
require('fpdf/fpdf.php');

// === CONSULTA DE DATOS ===
// Usamos CURDATE() para asegurar la sincronización de fechas
$totalGeneralDia = 0;

try {
    // Consulta para el total general
    $stmtTotal = $pdo->prepare("SELECT IFNULL(SUM(total), 0) FROM ventas WHERE DATE(fecha) = CURDATE()");
    $stmtTotal->execute();
    $totalGeneralDia = $stmtTotal->fetchColumn();

    // Consulta para el resumen por vendedor
    $stmt = $pdo->prepare("
        SELECT 
            u.nombre AS vendedor_nombre,
            COUNT(v.id) AS total_operaciones,
            SUM(v.total) AS total_ingresado
        FROM ventas v
        JOIN usuarios u ON v.usuario_id = u.id
        WHERE DATE(v.fecha) = CURDATE()
        GROUP BY u.id, u.nombre
        ORDER BY total_ingresado DESC
    ");
    $stmt->execute();
    $resumenDia = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    exit("Error de base de datos al generar PDF.");
}


// =================================================================
// GENERACIÓN DEL PDF
// =================================================================

class PDF extends FPDF
{
    // Encabezado de página
    function Header()
    {
        // Título Principal
        $this->SetFont('Arial','B',15);
        $this->Cell(0,10,utf8_decode('REPORTE DE CIERRE DE CAJA DIARIO'),0,1,'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0,5,utf8_decode('Fecha del Reporte: ' . date('d/m/Y')),0,1,'C');
        $this->Ln(5);
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb}',0,0,'C');
    }

    // Tabla de resumen (cabecera y datos)
    function FancyTable($header, $data)
    {
        // Colores de la cabecera
        $this->SetFillColor(200,220,255);
        $this->SetDrawColor(128,128,128);
        $this->SetTextColor(0);
        $this->SetLineWidth(.3);
        
        // Cabecera
        $w = array(60, 40, 50); // Ancho de las columnas
        $this->SetFont('Arial','B',10);
        for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,utf8_decode($header[$i]),1,0,'C',true);
        $this->Ln();

        // Restaurar colores y fuente para los datos
        $this->SetFillColor(224,235,255);
        $this->SetTextColor(0);
        $this->SetFont('Arial','',10);
        
        // Filas de datos
        $fill = false;
        foreach($data as $row)
        {
            $this->Cell($w[0],6,utf8_decode($row['vendedor_nombre']),'LR',0,'L',$fill);
            $this->Cell($w[1],6,number_format($row['total_operaciones'], 0),'LR',0,'C',$fill);
            $this->Cell($w[2],6,'S/ '.number_format($row['total_ingresado'], 2),'LR',0,'R',$fill);
            $this->Ln();
            $fill = !$fill;
        }
        // Línea de cierre
        $this->Cell(array_sum($w),0,'','T');
    }
}

// Instanciación de la clase PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// TITULO PRINCIPAL
$pdf->SetFont('Arial','B',12);
$pdf->SetTextColor(220, 50, 50); // Rojo para la alerta
$pdf->Cell(0,10,utf8_decode('TOTAL DE INGRESOS DEL DÍA: S/ ' . number_format($totalGeneralDia, 2)),0,1,'C');
$pdf->Ln(5);

// TABLA DETALLADA
// CABECERA CORREGIDA: Cambia 'Vendedor' por 'Usuario'
$header = array('Usuario', 'Operaciones', 'Monto Ingresado (S/)'); 
$pdf->FancyTable($header, $resumenDia);

$pdf->Output('I', 'CierreCaja_'. date('Ymd') .'.pdf');
?>