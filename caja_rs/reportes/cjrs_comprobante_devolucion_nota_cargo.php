<?php
require_once ("../../connections/conex.php");
require_once ("../inc_caja.php");
require_once("../../inc_sesion.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

include('../../clases/num2letras.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("24","20","24");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"20");

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;
$nroNotaCargo = $valCadBusq[1];
$idUsuario = $_SESSION["idUsuarioSysGts"];

global $spanClienteCxC;

$totalRows = 1;

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// COMPROBANTE DE PAGO ///////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ENCABEZADO EMPRESA
$queryEmpresa = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s",
	$idEmpresa);
$rsEmpresa = mysql_query($queryEmpresa);
if (!$rsEmpresa) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$rowEmpresa = mysql_fetch_assoc($rsEmpresa);

if ($totalRows > 0) {
	// DATA
	$contFila = 0;
	$fill = false;
	while ($contFila < 1) {
		$contFila++;
		
		if ($contFila % 45 == 1) {
			$pdf->AddPage();
			
			// CABECERA DEL DOCUMENTO
			if ($idEmpresa != "") {
				$pdf->Image("../../".$rowEmpresa['logo_familia'],15,17,80);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',6);
				$pdf->SetX(100);
				$pdf->Cell(200,9,($rowEmpresa['nombre_empresa']),0,2,'L');
				
				if (strlen($rowEmpresa['rif']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,($spanRIF.": ".$rowEmpresa['rif']),0,2,'L');
				}
				if (strlen($rowEmpresa['direccion']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(100,9,($rowEmpresa['direccion']),0,2,'L');
				}
				if (strlen($rowEmpresa['web']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,($rowEmpresa['web']),0,0,'L');
					$pdf->Ln();
				}
			}
			
			$pdf->Cell('',8,'',0,2);
			
			//FECHA Y HORA EMISION
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',8);
			$pdf->Cell(560,20,"Fecha de Emisión: ".date(spanDateFormat." H:i:s"),0,0,'R');
			$pdf->Ln();
			
			$pdf->Cell('',8,'',0,2);
						
			$queryDetalleCab = sprintf("SELECT 
				cj_cc_cliente.id,
				cj_cc_cliente.nombre,
				cj_cc_cliente.apellido,
				cj_cc_cliente.lci,
				cj_cc_cliente.ci,
				cj_cc_cliente.telf,
				cj_cc_notadecargo.numeroControlNotaCargo,
				cj_cc_notadecargo.fechaRegistroNotaCargo,
				cj_cc_notadecargo.numeroNotaCargo,
				cj_cc_notadecargo.montoTotalNotaCargo,
				cj_cc_notadecargo.observacionNotaCargo
			FROM cj_cc_cliente
				INNER JOIN cj_cc_notadecargo ON (cj_cc_cliente.id = cj_cc_notadecargo.idCliente)
			WHERE idNotaCargo = %s
				AND idDepartamentoOrigenNotaCargo IN (%s)
				AND cj_cc_notadecargo.id_empresa = %s",
				valTpDato($nroNotaCargo, "int"),
				valTpDato($idModuloPpal, "campo"),
				valTpDato($idEmpresa, "int"));
			$rsDetalleCab = mysql_query($queryDetalleCab);
			if (!$rsDetalleCab) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalleCab);
			$rowDetalleCab = mysql_fetch_assoc($rsDetalleCab);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',10);
			$pdf->Ln();
			$pdf->Cell(562,15,$nombreCajaPpal,0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->SetFont('Arial','',9);
			$pdf->Cell(562,5,"COMPROBANTE DE DEVOLUCION DE CHEQUE - NOTA DE DEBITO",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->SetFont('Arial','',9);
			$pdf->Cell(562,5,"Nro. Nota de Débito: ".$rowDetalleCab['numeroNotaCargo']."",0,0,'C');
			
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
						
			$pdf->SetFont('Arial','',9);
			$pdf->Cell(80,5,"",0,0,'L');
			$pdf->Cell(150,15,"Id: ".$rowDetalleCab['id']."",0,0,'L');
			$pdf->Cell(250,15,"".$spanClienteCxC.": "."".$rowDetalleCab['lci'].'-'.$rowDetalleCab['ci']."",0,0,'R');
			$pdf->Ln();
			$pdf->Cell(80,20,"",0,0,'L');
			$pdf->Cell(150,15,"Cliente: ".$rowDetalleCab['nombre']." ".$rowDetalleCab['apellido']."",0,0,'L');
			$pdf->Cell(250,15,"Teléfono: ".$rowDetalleCab['telf']."",0,0,'R');
			
			$pdf->Ln();$pdf->Ln();
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("164","180","220");
			$arrayCol = array("FECHA DE DEVOLUCIÓN\n","NRO. DOCUMENTO\n","IMPORTE\n");
			
			$posY = $pdf->GetY();
			$posX = $pdf->GetX();
			
			foreach ($arrayCol as $indice => $valor) {
				$pdf->SetY($posY);
				$pdf->SetX($posX);
				
				$pdf->MultiCell($arrayTamCol[$indice],8,$valor,1,'C',true);
				
				$posX += $arrayTamCol[$indice];
			}
		}
		
		//RESTAURACION DE COLORES Y FUENTES
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('');
		
		//$pdf->SetFillColor(234,244,255); // blanco
		$pdf->SetFillColor(255,255,255); // azul
		
		// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
		$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
		$rsMoneda = mysql_query($queryMoneda);
		if (!$rsMoneda) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMoneda = mysql_fetch_assoc($rsMoneda);
	
		//DETALLE DE LOS PAGOS
			$queryDetalle = sprintf("SELECT 
				cj_cc_cliente.id,
				cj_cc_cliente.nombre,
				cj_cc_cliente.apellido,
				cj_cc_cliente.lci,
				cj_cc_cliente.ci,
				cj_cc_cliente.telf,
				cj_cc_notadecargo.numeroControlNotaCargo,
				cj_cc_notadecargo.fechaRegistroNotaCargo,
				cj_cc_notadecargo.numeroNotaCargo,
				cj_cc_notadecargo.montoTotalNotaCargo,
				cj_cc_notadecargo.observacionNotaCargo
			FROM cj_cc_cliente
				INNER JOIN cj_cc_notadecargo ON (cj_cc_cliente.id = cj_cc_notadecargo.idCliente)
			WHERE idNotaCargo = %s
				AND idDepartamentoOrigenNotaCargo IN (%s)
				AND cj_cc_notadecargo.id_empresa = %s",
				valTpDato($nroNotaCargo, "int"),
				valTpDato($idModuloPpal, "campo"),
				valTpDato($idEmpresa, "int"));
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalle);
			
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$contFila++;
								
				$fechaPago = $rowDetalle['fechaRegistroNotaCargo'];
				$numeroDcto = $rowDetalle["numeroControlNotaCargo"];
				$montoPagado = $rowDetalle['montoTotalNotaCargo'];
				
					$pdf->Cell($arrayTamCol[0],12,date(spanDateFormat, strtotime($fechaPago)),'LR',0,'L',true);
					$pdf->Cell($arrayTamCol[1],12,utf8_encode($numeroDcto),'LR',0,'C',true);
					$pdf->Cell($arrayTamCol[2],12,number_format($montoPagado,2,".",","),'LR',0,'R',true);
					$pdf->Ln();
					
				$montoTotal += $montoPagado;
			}
			
			$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
			
			$pdf->Ln();
			
			$pdf->SetFillColor(255);
			$pdf->Cell(564,5,"",'T',0,'L',true);
			$pdf->Ln();
			
			$queryEmpleado = sprintf("SELECT 
				pg_empleado.nombre_empleado,
				pg_empleado.apellido,
				pg_usuario.nombre_usuario
			FROM
				pg_empleado
				INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado) WHERE pg_usuario.id_usuario = %s",
				valTpDato($idUsuario, "int"));
			$rsEmpleado = mysql_query($queryEmpleado);
			if (!$rsEmpleado) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			if($rowEmpleado = mysql_fetch_array($rsEmpleado)){
				$empleado = $rowEmpleado["nombre_empleado"].' '.$rowEmpleado["apellido"];
				$usuario = $rowEmpleado["nombre_usuario"];
			}
  			
			// TOTAL DOCUMENTOS
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(373,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(50,14,"TOTAL: ",1,0,'R',true);
			$pdf->Cell(141,14,number_format($montoTotal,2,".",","),1,0,'R',true);
			
			$pdf->SetFont('Arial','',9);
			$pdf->Cell(80,5,"",0,0,'L');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->Cell(150,15,"OBSERVACIÓN: ".$rowDetalleCab['observacionNotaCargo']."",0,0,'L');
			$pdf->Ln();
			$pdf->Cell(20,5,"",0,0,'L');
			$pdf->Cell(150,15,"Nota de Débito por la Cantidad de: ".utf8_decode(trim(substr(strtoupper(num2letras($montoTotal,false,true,$rowMoneda['descripcion'])),0,75)))."",0,0,'L');
			$pdf->Ln();
			$pdf->Cell(20,5,"",0,0,'L');
			$pdf->Cell(150,15,"".utf8_decode(trim(substr(strtoupper(num2letras($montoTotal,false,true,$rowMoneda['descripcion'])),75,180)))."",0,0,'L');
			$pdf->Ln();
			$pdf->Cell(20,5,"",0,0,'L');
			$pdf->Cell(0,230,"EMITIDO POR: ".utf8_decode($empleado)." (".utf8_decode($usuario).")",0,0,'C');
			
		$fill = !$fill;
		
		if (($contFila % 45 == 0) || $contFila == $totalRows) {
			$pdf->Cell(array_sum($arrayTamCol),0,'','T');
			
			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,5,"Página ".$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>