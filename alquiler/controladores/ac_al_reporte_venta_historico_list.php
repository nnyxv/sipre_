<?php

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['lstVerDcto'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaContratoVenta(0, "fecha_dcto, nro_control_dcto", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT empleado.id_empleado, empleado.nombre_empleado
	FROM al_contrato_venta contrato
		INNER JOIN vw_pg_empleados empleado ON (contrato.id_empleado_creador = empleado.id_empleado)
	ORDER BY empleado.nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function exportarFacturaVentaExcel($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['lstVerDcto'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("window.open('reportes/al_reporte_venta_historico_excel.php?valBusq=%s','_self');", $valBusq));
	
	return $objResponse;
}

function exportarFacturaVentaPdf($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['lstVerDcto'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/al_reporte_venta_historico_pdf.php?valBusq=%s', 960, 550)", $valBusq));
	
	return $objResponse;
}

function listaContratoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(q.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = q.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(q.fecha_dcto) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("q.id_empleado_creador = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[4] == 1) {// solo facturas sin devolucion
			$sqlBusq .= $cond.sprintf("q.tipo_dcto != 'NC' AND q.anulada = 'NO'");
		} else if ($valCadBusq[4] == 2) {// solo facturas con devolucion
			$sqlBusq .= $cond.sprintf("(q.tipo_dcto = 'NC' OR q.anulada = 'SI')");
		}
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(q.numero_contrato_venta LIKE %s		
		OR q.nro_dcto LIKE %s
		OR q.nro_control_dcto LIKE %s
		OR q.placa LIKE %s
		OR q.serial_carroceria LIKE %s
		OR q.nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT * 
	FROM (
		SELECT 
			cxc_fact.idFactura AS id_dcto,
			cxc_fact.fechaRegistroFactura AS fecha_dcto,
			'FA' AS tipo_dcto,
			cxc_fact.numeroFactura AS nro_dcto,
			cxc_fact.numeroControl AS nro_control_dcto,
			cxc_fact.anulada,
			contrato.fecha_creacion AS fecha_contrato,
			contrato.numero_contrato_venta,
			contrato.id_tipo_contrato,
			tipo_contrato.nombre_tipo_contrato,
			contrato.id_empleado_creador,
			empleado.nombre_empleado,
			contrato.id_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,	
			contrato.id_contrato_venta,
			contrato.id_empresa,
			contrato.id_unidad_fisica,
			contrato.estatus_contrato_venta,
			unidad.placa,
			unidad.serial_carroceria,
			(cxc_fact.subtotalFactura - cxc_fact.descuentoFactura) AS total_neto,
			cxc_fact.montoTotalFactura AS total,
			
			(SELECT SUM(cj_cc_factura_iva.subtotal_iva) FROM cj_cc_factura_iva
			WHERE cj_cc_factura_iva.id_factura = cxc_fact.idFactura) AS total_iva,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa	
		FROM al_contrato_venta contrato
			INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN vw_pg_empleados empleado ON (contrato.id_empleado_creador = empleado.id_empleado)		
			INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
			INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4
			INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)		
			
		UNION ALL
		
		SELECT 
			cxc_nc.idNotaCredito AS id_dcto,
			cxc_nc.fechaNotaCredito AS fecha_dcto,
			'NC' AS tipo_dcto,
			cxc_nc.numeracion_nota_credito AS nro_dcto,
			cxc_nc.numeroControl AS nro_control_dcto,
			'NO' AS anulada,
			contrato.fecha_creacion AS fecha_contrato,
			contrato.numero_contrato_venta,
			contrato.id_tipo_contrato,
			tipo_contrato.nombre_tipo_contrato,
			contrato.id_empleado_creador,
			empleado.nombre_empleado,
			contrato.id_cliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,	
			contrato.id_contrato_venta,
			contrato.id_empresa,
			contrato.id_unidad_fisica,
			contrato.estatus_contrato_venta,
			unidad.placa,
			unidad.serial_carroceria,
			((cxc_nc.subtotalNotaCredito - cxc_nc.subtotal_descuento)) * -1 AS total_neto,
			(cxc_nc.montoNetoNotaCredito * -1) AS total,
			((SELECT SUM(cj_cc_nota_credito_iva.subtotal_iva) FROM cj_cc_nota_credito_iva
			WHERE cj_cc_nota_credito_iva.id_nota_credito = cxc_nc.idNotaCredito)) * -1 AS total_iva,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa	
		FROM al_contrato_venta contrato
			INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN vw_pg_empleados empleado ON (contrato.id_empleado_creador = empleado.id_empleado)		
			INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
			INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4			
			INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento)
			INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
		WHERE cxc_nc.tipoDocumento = 'FA'
		) AS q %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "10%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "5%", $pageNum, "fecha_dcto", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Dcto");
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "5%", $pageNum, "tipo_dcto", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto");
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "5%", $pageNum, "nro_dcto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto");
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "5%", $pageNum, "nro_control_dcto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "5%", $pageNum, "numero_contrato_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Contrato");
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "5%", $pageNum, "fecha_contrato", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Contrato");
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "6%", $pageNum, "nombre_tipo_contrato", $campOrd, $tpOrd, $valBusq, $maxRows,"Tipo Contrato");
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "15%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Vendedor");
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "15%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "5%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);		
		$htmlTh .= ordenarCampo("xajax_listaContratoVenta", "5%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto");
		$htmlTh .= "<td class=\"noprint\" colspan=\"4\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusContrato = "";
		switch ($row['estatus_contrato_venta']){			
			case 1: $imgEstatusContrato = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Contrato Activo\"/>"; break;
			case 2: $imgEstatusContrato = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Contrato Cerrado\"/>"; break;
			case 3:	$imgEstatusContrato = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
			case 4:	$imgEstatusContrato = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
			case 5:	$imgEstatusContrato = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Vale de Salida\"/>"; break;
			case 6:	$imgEstatusContrato = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Vale de Entrada\"/>"; break;
			default : $imgEstatusContrato = "";
		}
		
		$imgEstatusFactura = "";
		if ($row['tipo_dcto'] == 'FA' && (strtoupper($row['anulada']) == "NO" || strtoupper($row['anulada']) == "")){
			$imgEstatusFactura = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
		}else if($row['tipo_dcto'] == 'NC'){
			$imgEstatusFactura = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Nota de Crédito\"/>";
		}else{
			$imgEstatusFactura = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura con Devolución\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusFactura."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_dcto']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo_dcto']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['nro_dcto']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['nro_control_dcto']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_contrato_venta']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i a", strtotime($row['fecha_contrato']))."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_contrato'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['serial_carroceria'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/al_contrato_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Contrato PDF\"/>",
					$row['id_contrato_venta']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				if ($row['tipo_dcto'] == 'FA') {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/al_factura_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Factura PDF\"/>",
						$row['id_dcto']);
				} else if($row['tipo_dcto'] == 'NC') {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/al_devolucion_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Nota de Crédito PDF\"/>",
						$row['id_dcto']);
				}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaContratoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaContratoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaContratoVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaContratoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaContratoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"25\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divlistaContratoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_iva'];
		$totalFacturas += $row['total'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalFacturas, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"exportarFacturaVentaExcel");
$xajax->register(XAJAX_FUNCTION,"exportarFacturaVentaPdf");
$xajax->register(XAJAX_FUNCTION,"listaContratoVenta");
?>