<?php


function asignarProveedor($idProveedor, $asigDescuento = true) {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor prov
	WHERE id_proveedor = %s",
		valTpDato($idProveedor, "text"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtIdProv","value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",htmlentities($rowProv['nombre_proveedor']));
	$objResponse->assign("txtRifProv","value",htmlentities($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccionProv","innerHTML",htmlentities($rowProv['direccion']));
	$objResponse->assign("txtContactoProv","value",htmlentities($rowProv['contacto']));
	$objResponse->assign("txtEmailContactoProv","value",htmlentities($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonosProv","value",htmlentities($rowProv['telefono']));
	
	if ($asigDescuento == true) {
		$objResponse->assign("txtDescuento","value",number_format($rowProv['descuento'], 2, ".", ","));
	}
	
	$objResponse->script("
	byId('btnCancelarLista').click();");
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedores(0, "id_proveedor", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarRetencion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtIdProv'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaRetencion(0, "idRetencionCabezera", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstAdministradoraPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array(0 => "Sin Firma de Administración", 1 => "Con Firma de Administración");
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirRetencion(xajax.getFormValues('frmBuscar'));\"";
	
	$html = "<select id=\"lstAdministradoraPDF\" name=\"lstAdministradoraPDF\" ".$class." ".$onChange.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAdministradoraPDF","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".htmlentities($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function imprimirRetencion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtIdProv'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq2=%s&lstAdministradoraPDF=%s',890,550)", $valBusq, $frmBuscar['lstAdministradoraPDF']));
	
	$objResponse->assign("tdlstAdministradoraPDF","innerHTML","");
	
	return $objResponse;
}

function listaProveedores($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanProvCxP;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");*/
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor prov %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaProveedores", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaProveedores", "18%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedores", "56%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedores", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedores(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaRetencion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(retencion.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = retencion.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("prov.id_proveedor = %s",
			valTpDato($valCadBusq[1],"int"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("retencion.fechaComprobante BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])),"date"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CASE tipoDeTransaccion
			WHEN '01' OR '1' THEN
				(SELECT cxp_fact.id_modulo FROM cp_factura cxp_fact
				WHERE cxp_fact.id_factura = retencion_det.idFactura)
			WHEN '02' OR '2' THEN
				(SELECT cxp_nd.id_modulo FROM cp_notadecargo cxp_nd
				WHERE cxp_nd.id_notacargo = retencion_det.id_nota_cargo)
			WHEN '03' OR '3' THEN
				(SELECT cxp_nc.id_departamento_notacredito FROM cp_notacredito cxp_nc
				WHERE cxp_nc.id_notacredito = retencion_det.id_nota_credito)
		END) IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR retencion.numeroComprobante LIKE %s
		OR (CASE tipoDeTransaccion
				WHEN '01' OR '1' THEN
					(SELECT cxp_fact.numero_factura_proveedor FROM cp_factura cxp_fact
					WHERE cxp_fact.id_factura = retencion_det.idFactura)
				WHEN '02' OR '2' THEN
					(SELECT cxp_nd.numero_notacargo FROM cp_notadecargo cxp_nd
					WHERE cxp_nd.id_notacargo = retencion_det.id_nota_cargo)
				WHEN '03' OR '3' THEN
					(SELECT cxp_nc.numero_nota_credito FROM cp_notacredito cxp_nc
					WHERE cxp_nc.id_notacredito = retencion_det.id_nota_credito)
			END) LIKE %s
		OR (CASE tipoDeTransaccion
				WHEN '01' OR '1' THEN
					(SELECT cxp_fact.numero_control_factura FROM cp_factura cxp_fact
					WHERE cxp_fact.id_factura = retencion_det.idFactura)
				WHEN '02' OR '2' THEN
					(SELECT cxp_nd.numero_control_notacargo FROM cp_notadecargo cxp_nd
					WHERE cxp_nd.id_notacargo = retencion_det.id_nota_cargo)
				WHEN '03' OR '3' THEN
					(SELECT cxp_nc.numero_control_notacredito FROM cp_notacredito cxp_nc
					WHERE cxp_nc.id_notacredito = retencion_det.id_nota_credito)
			END) LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT
		retencion.idRetencionCabezera,
		retencion.numeroComprobante,
		retencion.fechaComprobante,
		retencion_det.idFactura,
		(CASE tipoDeTransaccion
			WHEN '01' OR '1' THEN
				(SELECT cxp_fact.numero_factura_proveedor FROM cp_factura cxp_fact
				WHERE cxp_fact.id_factura = retencion_det.idFactura)
			WHEN '02' OR '2' THEN
				(SELECT cxp_nd.numero_notacargo FROM cp_notadecargo cxp_nd
				WHERE cxp_nd.id_notacargo = retencion_det.id_nota_cargo)
			WHEN '03' OR '3' THEN
				(SELECT cxp_nc.numero_nota_credito FROM cp_notacredito cxp_nc
				WHERE cxp_nc.id_notacredito = retencion_det.id_nota_credito)
		END) AS numero_factura_proveedor,
		(CASE tipoDeTransaccion
			WHEN '01' OR '1' THEN
				(SELECT cxp_fact.numero_control_factura FROM cp_factura cxp_fact
				WHERE cxp_fact.id_factura = retencion_det.idFactura)
			WHEN '02' OR '2' THEN
				(SELECT cxp_nd.numero_control_notacargo FROM cp_notadecargo cxp_nd
				WHERE cxp_nd.id_notacargo = retencion_det.id_nota_cargo)
			WHEN '03' OR '3' THEN
				(SELECT cxp_nc.numero_control_notacredito FROM cp_notacredito cxp_nc
				WHERE cxp_nc.id_notacredito = retencion_det.id_nota_credito)
		END) AS numero_control_factura,
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		SUM(retencion_det.baseImponible) AS baseImponible,
		SUM(retencion_det.impuestoIva) AS impuestoIva,
		SUM(retencion_det.IvaRetenido) AS IvaRetenido,
		SUM(retencion_det.totalCompraIncluyendoIva) AS totalCompraIncluyendoIva,
		cxp_fact2.id_modulo,
		
		(CASE cxp_fact2.id_modulo
			WHEN 1 THEN
				(SELECT COUNT(orden_tot.id_factura)
				FROM sa_orden_tot orden_tot
					INNER JOIN sa_orden_tot_detalle orden_tot_det ON (orden_tot.id_orden_tot = orden_tot_det.id_orden_tot)
				WHERE orden_tot.id_factura = cxp_fact2.id_factura)
			WHEN 2 THEN
				(SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
				WHERE cxp_fact_det_unidad.id_factura = cxp_fact2.id_factura)
				+
				(SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
				WHERE cxp_fact_det_acc.id_factura = cxp_fact2.id_factura)
			ELSE
				(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
				WHERE cxp_fact_det.id_factura = cxp_fact2.id_factura)
		END) AS cant_items
	FROM cp_retencioncabezera retencion
		INNER JOIN cp_proveedor prov ON (retencion.idProveedor = prov.id_proveedor)
		INNER JOIN cp_retenciondetalle retencion_det ON (retencion.idRetencionCabezera = retencion_det.idRetencionCabezera)
		INNER JOIN cp_factura cxp_fact2 ON (retencion_det.idFactura = cxp_fact2.id_factura) %s
	GROUP by retencion.idRetencionCabezera", $sqlBusq);
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "8%", $pageNum, "fechaComprobante", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "10%", $pageNum, "numeroComprobante", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Comprobante");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "10%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "10%", $pageNum, "numero_control_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control Factura");
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "26%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Proveedor"));	
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "9%", $pageNum, "baseImponible", $campOrd, $tpOrd, $valBusq, $maxRows,htmlentities("Base Imponible"));
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "9%", $pageNum, "impuestoIva", $campOrd, $tpOrd, $valBusq, $maxRows,htmlentities("Impuesto"));
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "9%", $pageNum, "IvaRetenido", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Impuesto Retenido"));
		$htmlTh .= ordenarCampo("xajax_listaRetencion", "9%", $pageNum, "totalCompraIncluyendoIva", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Total"));
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
				
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaComprobante']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroComprobante']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_control_factura']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['id_proveedor'].".- ".$row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['baseImponible'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['impuestoIva'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['IvaRetenido'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['totalCompraIncluyendoIva'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<a href=\"javascript:verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=".$row['idRetencionCabezera']."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Comprobante de Retención\"/><a>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal['IvaRetenido'] += $row['IvaRetenido'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['IvaRetenido'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal['IvaRetenido'] += $row['IvaRetenido'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['IvaRetenido'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaRetencion(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaRetencion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarRetencion");
$xajax->register(XAJAX_FUNCTION,"cargaLstAdministradoraPDF");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"imprimirRetencion");
$xajax->register(XAJAX_FUNCTION,"listaProveedores");
$xajax->register(XAJAX_FUNCTION,"listaRetencion");
?>