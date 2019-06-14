<?php

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstVerDcto'],
		$frmBuscar['lstVerMovimiento'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaUtilidad(0, "id_unidad_fisica", "ASC", $valBusq));
	
	return $objResponse;
}

function exportarUtilidadExcel($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstVerDcto'],
		$frmBuscar['lstVerMovimiento'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("window.open('reportes/al_reporte_utilidad_historico_excel.php?valBusq=%s','_self');", $valBusq));
	
	return $objResponse;
}

function exportarUtilidadPdf($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstVerDcto'],
		$frmBuscar['lstVerMovimiento'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/al_reporte_utilidad_historico_pdf.php?valBusq=%s', 960, 550)", $valBusq));
	
	return $objResponse;
}

function listaUtilidad($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
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
			$sqlBusq .= $cond.sprintf("q.tipo_dcto != 'NC'");
		} else if ($valCadBusq[3] == 2) {// solo facturas con devolucion
			$sqlBusq .= $cond.sprintf("(q.tipo_dcto = 'NC')");
		}
	}
		
	// BUSQUEDA PARA VEHICULO sqlBusq2
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[4] == "1"){
			$sqlBusq2 .= $cond.sprintf("id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_contrato_venta)");			
		} else if ($valCadBusq[4] == "2") {
			$sqlBusq2 .= $cond.sprintf("id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_servicio_mantenimiento_compra)");
		} else if ($valCadBusq[4] == "3") {
			$sqlBusq2 .= $cond.sprintf("(id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_contrato_venta)
			OR id_unidad_fisica IN (SELECT id_unidad_fisica FROM al_servicio_mantenimiento_compra))");
		}
	}	
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(placa LIKE %s
		OR serial_carroceria LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		uni_fis.id_unidad_fisica,
		uni_fis.placa,
		uni_fis.serial_carroceria,		
		marca.nom_marca,
		modelo.nom_modelo,
		version.nom_version,
		ano.nom_ano,
		uni_bas.nom_uni_bas
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
		INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
		INNER JOIN an_version version ON (uni_bas.ver_uni_bas = version.id_version)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano) %s", $sqlBusq2);
	
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
		$htmlTh .= ordenarCampo("xajax_listaUtilidad", "1%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Unidad");
		$htmlTh .= ordenarCampo("xajax_listaUtilidad", "5%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaUtilidad", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaUtilidad", "8%", $pageNum, "nom_marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Marca");
		$htmlTh .= ordenarCampo("xajax_listaUtilidad", "10%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, "Modelo");
		$htmlTh .= ordenarCampo("xajax_listaUtilidad", "10%", $pageNum, "nom_version", $campOrd, $tpOrd, $valBusq, $maxRows, "Versión");
		$htmlTh .= ordenarCampo("xajax_listaUtilidad", "20%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidad");	
		$htmlTh .= ordenarCampo("", "5%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Venta");
		$htmlTh .= ordenarCampo("", "5%", $pageNum, "costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Costo");
		$htmlTh .= ordenarCampo("", "5%", $pageNum, "costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Utilidad");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowUnidad = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 = $cond.sprintf("(q.id_unidad_fisica = %s)",
			valTpDato($rowUnidad['id_unidad_fisica'], "int"));
		
		// FACTURAS VENTA - NOTAS DE CREDITO VENTA
		$query = sprintf("SELECT SUM(total_neto) AS total_venta
		FROM (
			SELECT 
				contrato.id_empresa,
				cxc_fact.fechaRegistroFactura AS fecha_dcto,
				unidad.id_unidad_fisica,
				'FA' AS tipo_dcto,
				(cxc_fact.subtotalFactura - cxc_fact.descuentoFactura) AS total_neto
			FROM al_contrato_venta contrato
				INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
				INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
				INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4
				INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)		
				
			UNION ALL
			
			SELECT 
				contrato.id_empresa,
				cxc_nc.fechaNotaCredito AS fecha_dcto,
				unidad.id_unidad_fisica,
				'NC' AS tipo_dcto,
				((cxc_nc.subtotalNotaCredito - cxc_nc.subtotal_descuento)) * -1 AS total_neto
			FROM al_contrato_venta contrato
				INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
				INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
				INNER JOIN cj_cc_encabezadofactura cxc_fact ON contrato.id_contrato_venta = cxc_fact.numeroPedido AND idDepartamentoOrigenFactura = 4			
				INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento)
			WHERE cxc_nc.tipoDocumento = 'FA'
			) AS q %s %s", $sqlBusq, $sqlBusq3);
		$rsVenta = mysql_query($query);
		if (!$rsVenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$rowVenta = mysql_fetch_assoc($rsVenta);
			
		// FACTURAS COMPRA - NOTAS DE CREDITO COMPRA
		$query = sprintf("SELECT SUM(costo) AS total_costo
		FROM (
			SELECT 
				cxp_fact.id_empresa,
				cxp_fact.fecha_origen AS fecha_dcto,
				unidad.id_unidad_fisica,
				'FA' AS tipo_dcto,
				serv_mant_compra.costo,
				(cxp_fact.subtotal_factura - cxp_fact.subtotal_descuento) AS total_neto				
			FROM cp_factura cxp_fact
				INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
				INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
				LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
				
			UNION ALL
			
			SELECT 
				cxp_nc.id_empresa,
				cxp_nc.fecha_notacredito AS fecha_dcto,
				unidad.id_unidad_fisica,
				'NC' AS tipo_dcto,
				serv_mant_compra.costo,
				((cxp_nc.subtotal_notacredito - cxp_nc.subtotal_descuento)) * -1 AS total_neto
			FROM cp_notacredito cxp_nc
				INNER JOIN cp_factura cxp_fact ON (cxp_nc.id_documento = cxp_fact.id_factura AND cxp_nc.tipo_documento = 'FA')
				INNER JOIN al_servicio_mantenimiento_compra serv_mant_compra ON (cxp_fact.id_factura = serv_mant_compra.id_factura)			
				INNER JOIN an_unidad_fisica unidad ON (serv_mant_compra.id_unidad_fisica = unidad.id_unidad_fisica)
				LEFT JOIN al_servicio_mantenimiento serv_mant ON (serv_mant_compra.id_servicio_mantenimiento = serv_mant.id_servicio_mantenimiento)
			) AS q %s %s", $sqlBusq, $sqlBusq3);
		$rsCompra = mysql_query($query);
		if (!$rsCompra) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$rowCompra = mysql_fetch_assoc($rsCompra);
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".$rowUnidad['id_unidad_fisica']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowUnidad['placa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowUnidad['serial_carroceria'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($rowUnidad['nom_marca'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($rowUnidad['nom_modelo'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($rowUnidad['nom_version'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($rowUnidad['nom_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowVenta['total_venta'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowCompra['total_costo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowVenta['total_venta']-$rowCompra['total_costo'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$totalFacturas += $rowVenta['total_venta'];
		$totalCosto += $rowCompra['total_costo'];
		$totalUtilidad += $rowVenta['total_venta']-$rowCompra['total_costo'];
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUtilidad(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUtilidad(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUtilidad(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUtilidad(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUtilidad(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	$objResponse->assign("divlistaUtilidad","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->assign("spnTotalVenta","innerHTML",number_format($totalFacturas, 2, ".", ","));
	$objResponse->assign("spnTotalCosto","innerHTML",number_format($totalCosto, 2, ".", ","));
	$objResponse->assign("spnTotalUtilidad","innerHTML",number_format($totalUtilidad, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"exportarUtilidadExcel");
$xajax->register(XAJAX_FUNCTION,"exportarUtilidadPdf");
$xajax->register(XAJAX_FUNCTION,"listaUtilidad");
?>