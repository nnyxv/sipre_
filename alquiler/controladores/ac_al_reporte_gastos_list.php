<?php

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstVerDcto'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaFacturaCompra(0, "fecha_dcto, nro_control_dcto", "ASC", $valBusq));
	
	return $objResponse;
}

function exportarGastosExcel($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstVerDcto'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("window.open('reportes/al_reporte_gastos_historico_excel.php?valBusq=%s','_self');", $valBusq));
	
	return $objResponse;
}

function exportarGastosPdf($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstVerDcto'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/al_reporte_gastos_historico_pdf.php?valBusq=%s', 960, 550)", $valBusq));
	
	return $objResponse;
}

function listaFacturaCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
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
		if ($valCadBusq[3] == 1) {// solo facturas sin devolucion
			$sqlBusq .= $cond.sprintf("q.tipo_dcto != 'NC' AND q.activa = 1");
		} else if ($valCadBusq[3] == 2) {// solo facturas con devolucion
			$sqlBusq .= $cond.sprintf("(q.tipo_dcto = 'NC' OR q.activa = 0 OR q.activa IS NULL)");
		}
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(q.nro_dcto LIKE %s
		OR q.nro_control_dcto LIKE %s
		OR q.placa LIKE %s
		OR q.serial_carroceria LIKE %s
		OR q.nombre_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT * 
	FROM (
		SELECT 
			cxp_fact.id_factura AS id_dcto,
			cxp_fact.fecha_origen AS fecha_dcto,
			'FA' AS tipo_dcto,
			cxp_fact.numero_factura_proveedor AS nro_dcto,
			cxp_fact.numero_control_factura AS nro_control_dcto,
			cxp_fact.activa,
			cxp_fact.id_proveedor,
			prov.nombre AS nombre_proveedor,			
			cxp_fact.id_empresa,
			unidad.placa,
			unidad.serial_carroceria,
			serv_mant.descripcion_servicio_mantenimiento,
			serv_mant_compra.costo,			
			
			(cxp_fact.subtotal_factura - cxp_fact.subtotal_descuento) AS total_neto,
			cxp_fact.total_cuenta_pagar AS total,
			
			(SELECT SUM(cp_factura_iva.subtotal_iva) FROM cp_factura_iva
			WHERE cp_factura_iva.id_factura = cxp_fact.id_factura) AS total_iva,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa	
		FROM cp_factura cxp_fact
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
			INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
			INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
			LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
			
		UNION ALL
		
		SELECT 
			cxp_nc.id_notacredito AS id_dcto,
			cxp_nc.fecha_notacredito AS fecha_dcto,
			'NC' AS tipo_dcto,
			cxp_nc.numero_nota_credito AS nro_dcto,
			cxp_nc.numero_control_notacredito AS nro_control_dcto,
			0 AS activa,
			cxp_nc.id_proveedor,
			prov.nombre AS nombre_proveedor,			
			cxp_nc.id_empresa,			
			unidad.placa,
			unidad.serial_carroceria,
			serv_mant.descripcion_servicio_mantenimiento,
			(serv_mant_compra.costo) * -1 AS costo,	
			
			((cxp_nc.subtotal_notacredito - cxp_nc.subtotal_descuento)) * -1 AS total_neto,
			(cxp_nc.total_cuenta_pagar * -1) AS total,
			
			((SELECT SUM(cp_notacredito_iva.subtotal_iva_notacredito) FROM cp_notacredito_iva
			WHERE cp_notacredito_iva.id_notacredito = cxp_nc.id_notacredito)) * -1 AS total_iva,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa			
		FROM cp_notacredito cxp_nc
			INNER JOIN cp_factura cxp_fact ON (cxp_nc.id_documento = cxp_fact.id_factura AND cxp_nc.tipo_documento = 'FA')
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN cp_proveedor prov ON (cxp_nc.id_proveedor = prov.id_proveedor)			
			INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
			INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
			LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
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
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "10%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "5%", $pageNum, "fecha_dcto", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Dcto");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "5%", $pageNum, "tipo_dcto", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "5%", $pageNum, "nro_dcto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "5%", $pageNum, "nro_control_dcto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "15%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "5%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "10%", $pageNum, "descripcion_servicio_mantenimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Servicio / Mantenimiento");		
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "5%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto");
		$htmlTh .= ordenarCampo("xajax_listaFacturaCompra", "5%", $pageNum, "costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Unidad");
		$htmlTh .= "<td class=\"noprint\" colspan=\"4\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusFactura = "";
		if ($row['tipo_dcto'] == 'FA' && (strtoupper($row['activa']) == 1)){
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
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['serial_carroceria'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['descripcion_servicio_mantenimiento'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['costo'], 2, ".", ",")."</td>";			
			$htmlTb .= "<td>";
				if ($row['tipo_dcto'] == 'FA') {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../compras/reportes/ga_registro_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Factura PDF\"/>",
						$row['id_dcto']);
				} else if($row['tipo_dcto'] == 'NC') {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('../compras/reportes/ga_nota_credito_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Nota de Crédito PDF\"/>",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	$objResponse->assign("divlistaFacturaCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$arrayDuplicado = array();
	while ($row = mysql_fetch_assoc($rs)) {				
		//documento se repite tanto como detalles de costo tenga el vehiculo
		if(!in_array($row['tipo_dcto'].$row['id_dcto'], $arrayDuplicado)) {
			$totalNeto += $row['total_neto'];
			$totalIva += $row['total_iva'];
			$totalFacturas += $row['total'];
		}
		
		$arrayDuplicado[] = $row['tipo_dcto'].$row['id_dcto'];
		
		$totalCosto += $row['costo'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalFacturas, 2, ".", ","));
	$objResponse->assign("spnTotalCosto","innerHTML",number_format($totalCosto, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"exportarGastosExcel");
$xajax->register(XAJAX_FUNCTION,"exportarGastosPdf");
$xajax->register(XAJAX_FUNCTION,"listaFacturaCompra");
?>