<?php
require_once ("../../connections/conex.php");
require_once ("../inc_caja.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

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

$idEmpresa = $valCadBusq[0];
$txtFechaDesde = $valCadBusq[1];
$txtFechaHasta = $valCadBusq[2];
$txtCriterio = $valCadBusq[3];

//PUEDE SER NULL AL SELECCIONAR [TODOS] EN LA BUSQUEDA
if ($idEmpresa == NULL || $idEmpresa == -1) {
	$idEmpresa = '1';
}

$totalRows = 1;

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// DEVOLUCIÓN DE CHEQUES ///////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// ENCABEZADO EMPRESA
$queryEmpresa = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmpresa = mysql_query($queryEmpresa);
if (!$rsEmpresa) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
if ($totalRows > 0) {
	// DATA
	$contFila = 0;
	$fill = false;
	while ($contFila<1) {
		$contFila++;
		
		if ($contFila % 45 == 1) {
			$pdf->AddPage();
			
			// CABECERA DEL DOCUMENTO 
			if ($idEmpresa != "") {
				$pdf->Image("../../".$rowEmpresa['logo_familia'],15,17,80);
				
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',6);
				$pdf->SetX(100);
				$pdf->Cell(200,9,utf8_encode($rowEmpresa['nombre_empresa']),0,2,'L');
				
				if (strlen($rowEmpresa['rif']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,utf8_encode($spanRIF.": ".$rowEmpresa['rif']),0,2,'L');
				}
				if (strlen($rowEmpresa['direccion']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(100,9,utf8_encode($rowEmpresa['direccion']),0,2,'L');
				}
				if (strlen($rowEmpresa['web']) > 1) {
					$pdf->SetX(100);
					$pdf->Cell(200,9,utf8_encode($rowEmpresa['web']),0,0,'L');
					$pdf->Ln();
				}
			}
			
			$pdf->Cell('',8,'',0,2);

			//FECHA
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','',7);
				$pdf->Cell(560,20,"Fecha de Emisión: ".date(spanDateFormat." H:i:s"),0,0,'R');
				$pdf->Ln();
				
			$pdf->Cell('',8,'',0,2);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',11);
			$pdf->Ln();
			$pdf->Cell(562,5,$nombreCajaPpal,0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();
			$pdf->Cell(562,5,"DEVOLUCIÓN DE CHEQUES",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("100","50","50","50","124","100","50","40");
			$arrayCol = array("EMPRESA\n\n","FECHA\n\n","NRO. DCTO.\n\n","NRO. CHEQUE\n\n","BANCO\n\n","CLIENTE\n\n","TIPO DCTO.\n\n","MONTO\n\n");
			
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
		
		//CONSULTA EL LISTADO DE ANTICIPOS SEGUN BUSQUEDA
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
			$sqlBusqAnticipo .= $cond.sprintf("cj_cc_anticipo.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
				
			$sqlBusqNotaCargo .= $cond.sprintf("cj_cc_notadecargo.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
				
			$sqlBusqFactura .= $cond.sprintf("cj_cc_encabezadofactura.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
		}
			
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
			$sqlBusqAnticipo .= $cond.sprintf("cj_cc_anticipo.fechaAnticipo BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
			$sqlBusqNotaCargo .= $cond.sprintf("cj_det_nota_cargo.fechaPago BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			
			$sqlBusqFactura .= $cond.sprintf("cxc_pago.fechaPago BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
			$sqlBusqAnticipo .= $cond.sprintf("(cj_cc_detalleanticipo.numeroControlDetalleAnticipo LIKE %s
			OR (SELECT CONCAT_WS(' ',nombre,apellido) FROM cj_cc_cliente WHERE id = cj_cc_anticipo.idCliente) LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
			
			$sqlBusqNotaCargo .= $cond.sprintf("(cj_det_nota_cargo.numeroDocumento LIKE %s
			OR (SELECT CONCAT_WS(' ',nombre,apellido) FROM cj_cc_cliente WHERE id = cj_cc_notadecargo.idCliente) LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
			
			$sqlBusqFactura .= $cond.sprintf("(cxc_pago.numeroDocumento LIKE %s
			OR (SELECT CONCAT_WS(' ',nombre,apellido) FROM cj_cc_cliente WHERE id = cj_cc_encabezadofactura.idCliente) LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
		}
			
		// DETALLE DEL LSITADO
		$queryDetalle = sprintf("SELECT 
			'ANTICIPO' AS tipoDocumento,
			cj_cc_anticipo.idAnticipo AS idDocumento,
			cj_cc_anticipo.idCliente AS idCliente,
			cj_cc_anticipo.id_empresa AS idEmpresa,
			cj_cc_anticipo.numeroAnticipo AS numeroDocumento,
			cj_cc_detalleanticipo.bancoClienteDetalleAnticipo AS idBanco,
			bancos.nombreBanco AS nombreBanco,
			cj_cc_detalleanticipo.numeroControlDetalleAnticipo AS numeroCheque,
			cj_cc_detalleanticipo.montoDetalleAnticipo AS montoCheque,
			cj_cc_anticipo.fechaAnticipo AS fechaCheque,
			CONCAT_WS(' ', cj_cc_cliente.nombre,cj_cc_cliente.apellido) AS nombreCliente,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombreEmpresa
		FROM cj_cc_anticipo
			INNER JOIN cj_cc_detalleanticipo ON (cj_cc_detalleanticipo.idAnticipo = cj_cc_anticipo.idAnticipo)
			INNER JOIN cj_cc_cliente ON (cj_cc_anticipo.idCliente = cj_cc_cliente.id)
			INNER JOIN bancos ON (cj_cc_detalleanticipo.bancoClienteDetalleAnticipo = bancos.idBanco)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cj_cc_anticipo.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cj_cc_detalleanticipo.tipoPagoDetalleAnticipo = 'CH'
			AND cj_cc_anticipo.idDepartamento IN (%s)
			AND cj_cc_detalleanticipo.numeroControlDetalleAnticipo NOT IN (SELECT cxc_nd.numeroControlNotaCargo
												FROM cj_cc_notadecargo cxc_nd
												WHERE cxc_nd.numeroControlNotaCargo = cj_cc_detalleanticipo.numeroControlDetalleAnticipo
													AND cxc_nd.idCliente = cj_cc_anticipo.idCliente
													AND cxc_nd.idBanco = cj_cc_detalleanticipo.bancoClienteDetalleAnticipo
													AND cxc_nd.montoTotalNotaCargo = cj_cc_detalleanticipo.montoDetalleAnticipo)
			AND cj_cc_anticipo.estatus = 1 %s
			
		UNION
		
		SELECT 
			'NOTA DE CARGO' AS tipoDocumento,
			cj_cc_notadecargo.idNotaCargo AS idDocumento,
			cj_cc_notadecargo.idCliente AS idCliente,
			cj_cc_notadecargo.id_empresa AS idEmpresa,
			cj_cc_notadecargo.numeroNotaCargo AS numeroDocumento,
			cj_det_nota_cargo.bancoOrigen AS idBanco,
			bancos.nombreBanco AS nombreBanco,
			cj_det_nota_cargo.numeroDocumento AS numeroCheque,
			cj_det_nota_cargo.monto_pago AS montoCheque,
			cj_det_nota_cargo.fechaPago AS fechaCheque,
			CONCAT_WS(' ', cj_cc_cliente.nombre,cj_cc_cliente.apellido) AS nombreCliente, 
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombreEmpresa
		FROM cj_cc_notadecargo
			INNER JOIN cj_det_nota_cargo ON (cj_cc_notadecargo.idNotaCargo = cj_det_nota_cargo.idNotaCargo)
			INNER JOIN cj_cc_cliente ON (cj_cc_notadecargo.idCliente = cj_cc_cliente.id)
			INNER JOIN bancos ON (cj_det_nota_cargo.bancoOrigen = bancos.idBanco)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cj_cc_notadecargo.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cj_det_nota_cargo.idFormaPago = 2
			AND cj_cc_notadecargo.idDepartamentoOrigenNotaCargo IN (%s)
			AND cj_det_nota_cargo.numeroDocumento NOT IN (SELECT cxc_nd.numeroControlNotaCargo
												FROM cj_cc_notadecargo cxc_nd
												WHERE cxc_nd.numeroControlNotaCargo = cj_det_nota_cargo.numeroDocumento
													AND cxc_nd.idCliente = cj_cc_notadecargo.idCliente
													AND cxc_nd.idBanco = cj_det_nota_cargo.bancoOrigen
													AND cxc_nd.montoTotalNotaCargo = cj_det_nota_cargo.monto_pago) %s
			
		UNION
		
		SELECT 
			'FACTURA' AS tipoDocumento,
			cj_cc_encabezadofactura.idFactura AS idDocumento,
			cj_cc_encabezadofactura.idCliente AS idCliente,
			cj_cc_encabezadofactura.id_empresa AS idEmpresa,
			cxc_pago.numeroFactura AS numeroDocumento,
			cxc_pago.bancoOrigen AS idBanco,
			bancos.nombreBanco AS nombreBanco,
			cxc_pago.numeroDocumento AS numeroCheque,
			cxc_pago.montoPagado AS montoCheque,
			cxc_pago.fechaPago AS fechaCheque,
			CONCAT_WS(' ', cj_cc_cliente.nombre,cj_cc_cliente.apellido) AS nombreCliente, 
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombreEmpresa
		FROM cj_cc_encabezadofactura
			INNER JOIN sa_iv_pagos cxc_pago ON (cj_cc_encabezadofactura.idFactura = cxc_pago.id_factura)
			INNER JOIN cj_cc_cliente ON (cj_cc_encabezadofactura.idCliente = cj_cc_cliente.id) 
			INNER JOIN bancos ON (cxc_pago.bancoOrigen = bancos.idBanco)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cj_cc_encabezadofactura.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE cxc_pago.formaPago = 2
			AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura IN (%s)
			AND cxc_pago.numeroDocumento NOT IN (SELECT cxc_nd.numeroControlNotaCargo
												FROM cj_cc_notadecargo cxc_nd
												WHERE cxc_nd.numeroControlNotaCargo = cxc_pago.numeroDocumento
													AND cxc_nd.idCliente = cj_cc_encabezadofactura.idCliente
													AND cxc_nd.idBanco = cxc_pago.bancoOrigen
													AND cxc_nd.montoTotalNotaCargo = cxc_pago.montoPagado) %s",
				valTpDato($idModuloPpal, "campo"), $sqlBusqAnticipo,
				valTpDato($idModuloPpal, "campo"), $sqlBusqNotaCargo,
				valTpDato($idModuloPpal, "campo"), $sqlBusqFactura);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalle);
			
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$contFila++;
				
				$nombreEmpresa = $rowDetalle['nombreEmpresa'];
				$fechaCheque = $rowDetalle['fechaCheque'];
				$numeroDocumento = $rowDetalle['numeroDocumento'];
				$numeroCheque = $rowDetalle['numeroCheque'];
				
				$nombreBanco = $rowDetalle['nombreBanco'];
				$nombreCliente = $rowDetalle['nombreCliente'];
				$tipoDocumento = $rowDetalle['tipoDocumento'];
				$montoCheque = $rowDetalle['montoCheque'];
				
				
						$pdf->Cell($arrayTamCol[0],12,$nombreEmpresa,'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[1],12,date(spanDateFormat, strtotime($fechaCheque)),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[2],12,utf8_encode($numeroDocumento),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[3],12,utf8_encode($numeroCheque),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[4],12,utf8_encode($nombreBanco),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[5],12,utf8_encode($nombreCliente),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[6],12,utf8_encode($tipoDocumento),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[7],12,number_format($montoCheque,2,".",","),'LR',0,'R',true);
						$pdf->Ln();
						
				$montoTotalCheque += $montoCheque;
			}
			
			$pdf->MultiCell('',0,'',1,'C',true); // cierra linea de tabla
			
			$pdf->Ln();
			
			$pdf->SetFillColor(255);
			$pdf->Cell(562,5,"",'T',0,'L',true);
			$pdf->Ln();
						
			// TOTAL ANTCIPOS
			$pdf->SetFillColor(255,255,255);
			$pdf->Cell(442,14,"",0,0,'L',true);
			$pdf->SetFillColor(204,204,204,204);
			$pdf->Cell(72,14,"TOTAL: ",1,0,'L',true);
			$pdf->Cell(50,14,number_format($montoTotalCheque,2,".",","),1,0,'R',true);
			
		$fill = !$fill;
		
		if (($contFila % 45 == 0) || $contFila == $totalRows) {
		
			$pdf->Cell(array_sum($arrayTamCol),0,'','T');
			
			$pdf->SetY(-30);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','I',8);
			$pdf->Cell(0,10,"Página ".$pdf->PageNo()."/{nb}",0,0,'C');
		}
	}
}
$pdf->SetDisplayMode("real");
//$pdf->AutoPrint(true);
$pdf->Output();
?>