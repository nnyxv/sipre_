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

$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;
$lstTipoPago = $valCadBusq[1];
$txtCriterio = $valCadBusq[2];

$totalRows = 1;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////// ELIMINACIÓN DE PAGOS DEL DÍA ///////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
			$pdf->Cell(562,5,"ELIMINACIÓN DE PAGOS DEL DÍA",0,0,'C');
			$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();$pdf->Ln();
				
			/* COLUMNAS */
			//COLORES, ANCHO DE LINEA Y FUENTE EN NEGRITA
			$pdf->SetFillColor(204,204,204);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetDrawColor(153,153,153);
			$pdf->SetLineWidth(1);
			$pdf->SetFont('Arial','',6.8);
			
			// ENCABEZADO DE LA TABLA
			$arrayTamCol = array("100","70","60","60","130","70","74");
			$arrayCol = array("EMPRESA\n\n","TIPO DCTO.\n\n","NRO. DCTO.\n\n","NRO. REFERENCIA\n\n","CLIENTE\n\n","TIPO PAGO\n\n","MONTO\n\n");
			
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
		
		// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
		$queryAperturaCaja = sprintf("SELECT *,
			(CASE ape.statusAperturaCaja
				WHEN 0 THEN 'CERRADA TOTALMENTE'
				WHEN 1 THEN CONCAT_WS(' EL ', 'ABIERTA', DATE_FORMAT(ape.fechaAperturaCaja, %s))
				WHEN 2 THEN 'CERRADA PARCIALMENTE'
				ELSE 'CERRADA TOTALMENTE'
			END) AS estatus_apertura_caja
		FROM ".$apertCajaPpal." ape
			INNER JOIN caja ON (ape.idCaja = caja.idCaja)
			LEFT JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
		WHERE caja.idCaja = %s
			AND ape.statusAperturaCaja IN (1,2)
			AND (ape.id_empresa = %s
				OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = %s));",
			valTpDato(spanDatePick, "date"),
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
		$rsAperturaCaja = mysql_query($queryAperturaCaja);
		if (!$rsAperturaCaja) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$fechaApertura = $rowAperturaCaja['fechaAperturaCaja'];

		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_fact.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
				
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(cxc_nd.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_nd.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
				
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("(cxc_ant.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_ant.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("cxc_pago.formaPago IN (%s)",
				valTpDato($valCadBusq[1], "campo"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("cxc_pago.idFormaPago IN (%s)",
				valTpDato($valCadBusq[1], "campo"));
			
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("cxc_pago.id_forma_pago IN (%s)",
				valTpDato($valCadBusq[1], "campo"));
		}
			
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(cxc_fact.numeroFactura LIKE %s
			OR cxc_fact.numeroControl LIKE %s
			OR recibo.numeroComprobante LIKE %s
			OR cliente.nombre LIKE %s
			OR cliente.apellido LIKE %s)",
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(cxc_nd.numeroNotaCargo LIKE %s
			OR cxc_nd.numeroControlNotaCargo LIKE %s
			OR recibo.numeroComprobante LIKE %s
			OR cliente.nombre LIKE %s
			OR cliente.apellido LIKE %s)",
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"));
			
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("(cxc_ant.numeroAnticipo LIKE %s
			OR recibo.numeroReporteImpresion LIKE %s
			OR cliente.nombre LIKE %s
			OR cliente.apellido LIKE %s)",
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"),
				valTpDato("%".$valCadBusq[2]."%", "text"));
		}
		
		// DETALLE DEL LSITADO
		$queryDetalle = sprintf("SELECT query.*
		FROM (
			SELECT 
				cxc_pago.idPago,
				'FACTURA' AS tipoDoc,
				cxc_fact.idDepartamentoOrigenFactura AS idDepartamento,
				cxc_fact.idFactura AS id_documento_pagado,
				cxc_fact.numeroFactura AS numero_documento,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cxc_pago.fechaPago,
				recibo.idComprobante AS id_recibo_pago,
				recibo.numeroComprobante AS nro_comprobante,
				cxc_pago.formaPago,
				forma_pago.nombreFormaPago,
				NULL AS id_concepto,
				(CASE cxc_pago.formaPago
					WHEN 7 THEN
						(SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
					WHEN 8 THEN
						(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
					ELSE
						cxc_pago.numeroDocumento
				END) AS numero_documento_pago,
				cxc_pago.bancoOrigen,
				banco_origen.nombreBanco AS nombre_banco_origen,
				cxc_pago.bancoDestino,
				banco_destino.nombreBanco AS nombre_banco_destino,
				cxc_pago.cuentaEmpresa,
				cxc_pago.idCaja,
				cxc_pago.montoPagado,
				cxc_pago.estatus,
				cxc_pago.estatus AS estatus_pago,
				DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
				'an_pagos' AS tabla,
				'idPago' AS campo_id_pago,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM cj_cc_encabezadofactura cxc_fact
				INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
				INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
				INNER JOIN formapagos forma_pago on (cxc_pago.formaPago = forma_pago.idFormaPago)
				INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
				INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
				INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
				INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
			
			UNION
			
			SELECT 
				cxc_pago.id_det_nota_cargo AS idPago,
				'NOTA DEBITO' AS tipoDoc,
				cxc_nd.idDepartamentoOrigenNotaCargo AS idDepartamento,
				cxc_nd.idNotaCargo AS id_documento_pagado,
				cxc_nd.numeroNotaCargo AS numero_documento,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cxc_pago.fechaPago,
				recibo.idComprobante AS id_recibo_pago,
				recibo.numeroComprobante AS nro_comprobante,
				cxc_pago.idFormaPago AS formaPago,
				forma_pago.nombreFormaPago,
				NULL AS id_concepto,
				(CASE cxc_pago.idFormaPago
					WHEN 8 THEN
						(SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
					ELSE
						cxc_pago.numeroDocumento
				END) AS numero_documento_pago,
				cxc_pago.bancoOrigen,
				banco_origen.nombreBanco AS nombre_banco_origen,
				cxc_pago.bancoDestino,
				banco_destino.nombreBanco AS nombre_banco_destino,
				cxc_pago.cuentaEmpresa,
				cxc_pago.idCaja,
				cxc_pago.monto_pago AS montoPagado,
				cxc_pago.estatus,
				cxc_pago.estatus AS estatus_pago,
				DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
				'cj_det_nota_cargo' AS tabla,
				'id_det_nota_cargo' AS campo_id_pago,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM cj_cc_notadecargo cxc_nd
				INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
				INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
				INNER JOIN formapagos forma_pago on (cxc_pago.idFormaPago = forma_pago.idFormaPago)
				INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
				INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
				INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
				INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_nd.idDepartamentoOrigenNotaCargo = recibo.id_departamento AND recibo.idTipoDeDocumento = 2)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
			
			UNION
			
			SELECT 
				cxc_pago.idDetalleAnticipo AS idPago,
				CONCAT_WS(' ', 'ANTICIPO', IF(concepto_forma_pago.descripcion IS NOT NULL, CONCAT('(', concepto_forma_pago.descripcion, ')'), NULL)) AS tipoDoc,
				cxc_ant.idDepartamento AS idDepartamento,
				cxc_ant.idAnticipo AS id_documento_pagado,
				cxc_ant.numeroAnticipo AS numero_documento,
				cliente.id AS id_cliente,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cxc_pago.fechaPagoAnticipo AS fechaPago,
				recibo.idReporteImpresion AS id_recibo_pago,
				recibo.numeroReporteImpresion AS nro_comprobante,
				cxc_pago.id_forma_pago AS formaPago,
				forma_pago.nombreFormaPago,
				cxc_pago.id_concepto AS id_concepto,
				cxc_pago.numeroControlDetalleAnticipo AS numero_documento_pago,
				cxc_pago.bancoClienteDetalleAnticipo AS bancoOrigen,
				banco_origen.nombreBanco AS nombre_banco_origen,
				cxc_pago.bancoCompaniaDetalleAnticipo AS bancoDestino,
				banco_destino.nombreBanco AS nombre_banco_destino,
				cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
				cxc_pago.idCaja,
				cxc_pago.montoDetalleAnticipo AS montoPagado,
				cxc_ant.estatus AS estatus,
				cxc_pago.estatus AS estatus_pago,
				DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
				'cj_cc_detalleanticipo' AS tabla,
				'idDetalleAnticipo' AS campo_id_pago,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
				INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
				INNER JOIN formapagos forma_pago on (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
				INNER JOIN bancos banco_origen on (cxc_pago.bancoClienteDetalleAnticipo = banco_origen.idBanco)
				INNER JOIN bancos banco_destino on (cxc_pago.bancoCompaniaDetalleAnticipo = banco_destino.idBanco)
				INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion AND cxc_ant.idDepartamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'AN')
				LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
			) AS query", $sqlBusq, $sqlBusq2, $sqlBusq3);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rsDetalle);
			
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$contFila++;
				
				$nombreEmpresa = $rowDetalle['nombre_empresa'];
				$tipoDocumento = $rowDetalle['tipo_documento'];
				$numeroDocumento = $rowDetalle['numero_documento'];
				$numeroControlPago = $rowDetalle['numero_control_pago'];
				$cliente = $rowDetalle['cliente'];
				$tipoPago = $rowDetalle['tipo_pago'];
				$montoPagado = $rowDetalle['monto_pagado'];
				
				if ($condicionPago == 0)
					$condicionPago = 'Credito';
				else
					$condicionPago = 'Contado';
						$pdf->Cell($arrayTamCol[0],12,$nombreEmpresa,'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[1],12,utf8_encode($tipoDocumento),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[2],12,utf8_encode($numeroDocumento),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[3],12,utf8_encode($numeroControlPago),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[4],12,utf8_encode($cliente),'LR',0,'L',true);
						$pdf->Cell($arrayTamCol[5],12,utf8_encode($tipoPago),'LR',0,'C',true);
						$pdf->Cell($arrayTamCol[6],12,number_format($montoPagado,2,".",","),'LR',0,'R',true);
						$pdf->Ln();
						
				$montoTotalPago += $montoPagado;
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
			$pdf->Cell(50,14,number_format($montoTotalPago,2,".",","),1,0,'R',true);
			
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
$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>