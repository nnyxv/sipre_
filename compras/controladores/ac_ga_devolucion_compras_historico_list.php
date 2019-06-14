<?php

function buscarNotaCredito($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$numSolicitud = (strpos($frmBuscar["txtNroSolicitud"],"-")) ? substr($frmBuscar["txtNroSolicitud"], 4) : $frmBuscar["txtNroSolicitud"];
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtNroNotaCredito'],
		$numSolicitud,
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaNotaCredito(0, "id_notacredito", "DESC", $valBusq));
	
	return $objResponse;
}

function exportarExcel($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$numSolicitud = (strpos($frmBuscar["txtNroSolicitud"],"-")) ? substr($frmBuscar["txtNroSolicitud"], 4) : $frmBuscar["txtNroSolicitud"];

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtNroNotaCredito'],
		$numSolicitud,
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);

	$objResponse->script("window.open('reportes/ga_devolucion_compra_historico_excel.php?valBusq=".$valBusq."','_self');");

	return $objResponse;
}

function listaNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
	WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) > 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxp_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxp_nc.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") { 
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxp_nc.numero_nota_credito = %s 
		OR cxp_nc.numero_control_notacredito = %s)",
			valTpDato($valCadBusq[1], "text"),
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") { 
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(solicitud_compra.numero_solicitud = %s
		OR orden_compra.id_orden_compra = %s)",
			valTpDato($valCadBusq[2], "int"),			
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_nc.fecha_registro_notacredito BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[4])),"date"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(prov.id_proveedor LIKE %s
		OR CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_nc.numero_nota_credito LIKE %s
		OR cxp_nc.numero_control_notacredito LIKE %s
		OR cxp_nc.observacion_notacredito LIKE %s
		OR solicitud_compra.numero_solicitud LIKE %s
		OR orden_compra.id_orden_compra LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT cxp_nc.*,
		solicitud_compra.numero_solicitud,
		solicitud_compra.justificacion_compra,
		orden_compra.id_orden_compra,
		
		IF (solicitud_compra.numero_solicitud IS NULL,
			'-',
			CONCAT_WS('-', (SELECT empresa.codigo_empresa 
							FROM pg_empresa empresa WHERE empresa.id_empresa = cxp_fact.id_empresa), 
							solicitud_compra.numero_solicitud) 
		) AS num_solicitud,
		
		IFNULL(orden_compra.id_orden_compra,'-') AS num_orden,
		
		(CASE cxp_nc.estado_notacredito
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Sin Asignar'
			WHEN 2 THEN 'Asignado Parcial'
			WHEN 3 THEN 'Asignado'
		END) AS descripcion_estado_nota_credito,
		
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		
		(CASE
			WHEN cxp_nc.tipo_documento LIKE 'FA' THEN
				cxp_fact.numero_factura_proveedor
		END) AS numero_factura_proveedor,
		
		(CASE
			WHEN cxp_nc.tipo_documento LIKE 'FA' THEN
				cxp_fact.fecha_factura_proveedor
		END) AS fecha_factura_proveedor,
		
		(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
			WHERE cxp_fact_det.id_factura = cxp_nc.id_documento) AS cant_items,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.id_nota_credito = cxp_nc.id_notacredito
		LIMIT 1) AS idRetencionCabezera,
		
		(IFNULL(cxp_nc.subtotal_notacredito, 0)
			- IFNULL(cxp_nc.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto
					FROM cp_notacredito_gastos cxp_nc_gasto
					WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
						AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
		
		(IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva
				FROM cp_notacredito_iva cxp_nc_iva
				WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)) AS total_iva,
		
		(IFNULL(cxp_nc.subtotal_notacredito, 0)
			- IFNULL(cxp_nc.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
					WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
						AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
					WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0)) AS total,
		
		vw_iv_usuario.nombre_empleado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_notacredito cxp_nc
		INNER JOIN cp_factura cxp_fact ON (cxp_nc.id_documento = cxp_fact.id_factura AND cxp_nc.tipo_documento LIKE 'FA')
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
		LEFT JOIN ga_orden_compra orden_compra ON (cxp_fact.id_orden_compra = orden_compra.id_orden_compra)
		LEFT JOIN ga_solicitud_compra solicitud_compra ON (orden_compra.id_solicitud_compra = solicitud_compra.id_solicitud_compra)
		LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_motivo motivo ON (cxp_nc.id_motivo = motivo.id_motivo)
		LEFT JOIN vw_iv_usuarios vw_iv_usuario ON (cxp_fact.id_empleado_creador = vw_iv_usuario.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "12%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "fecha_registro_notacredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "fecha_notacredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Nota de Crédito Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "numero_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota de Crédito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "numero_control_notacredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Factura");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "5%", $pageNum, "num_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Orden");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "5%", $pageNum, "num_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "25%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "25%", $pageNum, "observacion_notacredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Observación");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_departamento_notacredito']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_departamento_notacredito'];
		}
		
		$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
		
		switch($row['estado_notacredito']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado']) > 0) ? "title=\"Nro. Nota de Crédito: ".$row['numero_nota_credito'].". Registrado por: ".utf8_encode($row['nombre_empleado'])."\"" : "").">".date(spanDateFormat, strtotime($row['fecha_registro_notacredito']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_notacredito']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_nota_credito']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_control_notacredito']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['num_orden']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['num_solicitud']."</td>";
			$htmlTb .= "<td>".utf8_encode($row["id_proveedor"].".- ".$row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['observacion_factura'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('reportes/ga_nota_credito_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota de Crédito PDF\"/></a>",
					$row['id_notacredito']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['idRetencionCabezera'] > 0) {
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/ico_print.png\" title=\"Comprobante de Retención\"/></a>",
					$row['idRetencionCabezera']);
			}
			$htmlTb .= "</td>";
			// MODIFICADO ERNESTO
			$sPar = "idobject=".$row['id_notacredito'];
			$sPar .= "&ct=17";
			$sPar .= "&dt=10";
			$sPar .= "&cc=01";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?".$sPar."', 960, 550);\" target=\"_self\"><img src=\"../img/iconos/new_window.png\" title=\"Ver Movimiento Contable\"/></a>");
			$htmlTb .= "</td>";
			// MODIFICADO ERNESTO
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"19\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
		$htmlTb .= "<td colspan=\"19\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaNotaCredito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION, "buscarNotaCredito");
$xajax->register(XAJAX_FUNCTION, "exportarExcel");
$xajax->register(XAJAX_FUNCTION, "listaNotaCredito");

?>