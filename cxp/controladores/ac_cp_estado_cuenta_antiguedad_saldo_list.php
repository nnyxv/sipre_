<?php


function asignarProveedor($idProveedor, $nombreObjeto, $asigDescuento = "true", $cerrarVentana = "true") {
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
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",utf8_encode($rowProv['nombre_proveedor']));
	$objResponse->assign("txtRif".$nombreObjeto,"value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtNit".$nombreObjeto,"value",utf8_encode($rowProv['nit_proveedor']));
	$objResponse->assign("txtDireccion".$nombreObjeto,"innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContacto".$nombreObjeto,"value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContacto".$nombreObjeto,"value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonos".$nombreObjeto,"value",$rowProv['telefono']);
	
	if (strtoupper($rowProv['credito']) == "SI" || $rowProv['credito'] == 1) {
		$queryProvCredito = sprintf("SELECT * FROM cp_prove_credito WHERE id_proveedor = %s;",
			valTpDato($rowProv['id_proveedor'], "int"));
		$rsProvCredito = mysql_query($queryProvCredito);
		if (!$rsProvCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProvCredito = mysql_fetch_assoc($rsProvCredito);
		
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value",$rowProvCredito['diascredito']);
		
		$objResponse->call("selectedOption","lstTipoPago",1);
	} else {
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value","0");
		
		$objResponse->call("selectedOption","lstTipoPago",0);
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaProveedor').click();");
	}
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarEstadoCuenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdProv'],
		$frmBuscar['txtFecha'],
		$frmBuscar['radioOpcion'],
		$frmBuscar['lstTipoDetalle'],
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"),
		((count($frmBuscar['cbxDcto']) > 0) ? implode(",",$frmBuscar['cbxDcto']) : "-1"),
		((count($frmBuscar['cbxDiasVencidos']) > 0) ? implode(",",$frmBuscar['cbxDiasVencidos']) : "-1"),
		$frmBuscar['txtCriterio']);
	
	switch ($frmBuscar['radioOpcion']) {
		case 1 : $objResponse->loadCommands(listaECIndividual(0, "CONCAT(vw_cxp_as.fecha_origen, vw_cxp_as.idEstadoCuenta)", "DESC", $valBusq)); break;
		case 2 : $objResponse->loadCommands(listaECGeneral(0, "prov.nombre", "ASC", $valBusq)); break;
		case 3 : $objResponse->loadCommands(listaECGeneral(0, "prov.nombre", "ASC", $valBusq)); break;
		case 4 : $objResponse->loadCommands(listaECGeneral(0, "prov.nombre", "ASC", $valBusq)); break;
	}
	
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarProveedor['hddObjDestinoProveedor'],
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "ASC", $valBusq));
		
	return $objResponse;
}

function cargarDiasVencidos(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM gruposestadocuenta");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"corriente\"/> Cta. Corriente</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde1\"/> De ".$row['desde1']." a ".$row['hasta1']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde2\"/> De ".$row['desde2']." a ".$row['hasta2']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde3\"/> De ".$row['desde3']." a ".$row['hasta3']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"masDe\"/> Mas de ".$row['masDe']."</label></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdDiasVencidos","innerHTML",$html);
	
	return $objResponse;
}

function cargarModulos(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td width=\"50%\"><label><input type=\"checkbox\" id=\"cbxModulo\" name=\"cbxModulo[]\" checked=\"checked\" value=\"".$row['id_modulo']."\"/> ".$row['descripcionModulo']."</label></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdModulos","innerHTML",$html);
	
	return $objResponse;
}

function cargarTipoDocumento(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM tipodedocumentos ORDER BY idTipoDeDocumento");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td width=\"50%\"><label><input type=\"checkbox\" id=\"cbxDcto\" name=\"cbxDcto[]\" checked=\"checked\" value=\"".utf8_encode($row['abreviatura_tipo_documento'])."\"/> ".utf8_encode($row['descripcionTipoDeDocumento'])."</label></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdTipoDocumento","innerHTML",$html);
	
	return $objResponse;
}

function exportarAntiguedadSaldo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdProv'],
		$frmBuscar['txtFecha'],
		$frmBuscar['radioOpcion'],
		$frmBuscar['lstTipoDetalle'],
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"),
		((count($frmBuscar['cbxDcto']) > 0) ? implode(",",$frmBuscar['cbxDcto']) : "-1"),
		((count($frmBuscar['cbxDiasVencidos']) > 0) ? implode(",",$frmBuscar['cbxDiasVencidos']) : "-1"),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/cp_antiguedad_saldo_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanProvCxP;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(strtoupper($arrayTipoPago[strtoupper($row['credito'])]))."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaECGeneral($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$queryGrupoEstado = "SELECT * FROM gruposestadocuenta";
	$rsGrupoEstado = mysql_query($queryGrupoEstado);
	if (!$rsGrupoEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGrupoEstado = mysql_fetch_array($rsGrupoEstado);
				
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_as.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxp_as.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_as.id_proveedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(vw_cxp_as.fecha_origen) <= %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	
	if (strtotime($valCadBusq[2]) >= strtotime(date(spanDateFormat))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ROUND(vw_cxp_as.saldoFactura, 2) > 0",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((CASE
			WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
				(vw_cxp_as.total_cuenta_pagar - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
														WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
															AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
															AND cxp_pago.fecha_pago <= %s
															AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0))
			WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
				(vw_cxp_as.total_cuenta_pagar - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
														WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
															AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
															AND cxp_pago.fecha_pago <= %s
															AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0))
		END) > 0
			AND NOT ((CASE
					WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
						(SELECT MAX(cxp_pago.fecha_pago) FROM cp_pagos_documentos cxp_pago
						WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
							AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
							AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))) 
					WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
						(SELECT MAX(cxp_pago.fecha_pago) FROM cp_pagos_documentos cxp_pago
						WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
							AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
							AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s)))
				END) < %s
					AND ((vw_cxp_as.tipoDocumento IN ('FA','ND') AND vw_cxp_as.estadoFactura IN (1))
						OR (vw_cxp_as.tipoDocumento IN ('AN','NC') AND vw_cxp_as.estadoFactura IN (3)))))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		 // 1 = Detallado por Empresa, 2 = Consolidado
		$groupBy = ($valCadBusq[4] == 1) ? "GROUP BY vw_cxp_as.id_empresa, vw_cxp_as.id_proveedor" : "GROUP BY vw_cxp_as.id_proveedor";
	} else {
		$groupBy = "GROUP BY vw_cxp_as.id_empresa, vw_cxp_as.id_proveedor";
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_as.id_modulo IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_as.tipoDocumento IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$arrayDiasVencidos = NULL;
		if (in_array("corriente",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde1",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde2",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde3",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("masDe",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_as.numeroFactura LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_cxp_as.*,
		prov.nombre AS nombre_proveedor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cp_antiguedad_saldo vw_cxp_as
		INNER JOIN cp_proveedor prov ON (vw_cxp_as.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $groupBy);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__.$query);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Proveedor, 4 = General por Dcto.
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"".((in_array($valCadBusq[3],array(3))) ? 10 : 11)."\"></td>";
			$htmlTh .= "<td colspan=\"4\">D&iacute;as Vencidos</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"4%\"></td>";
			if (in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "0%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			} else {
				$htmlTh .= "<td></td>";
			}
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Dcto. Proveedor");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "6%", $pageNum, "fecha_vencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
			if (in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
				$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
				$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
				$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
				$htmlTh .= "<td width=\"9%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
			} else {
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "7%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
				$htmlTh .= ordenarCampo("xajax_listaECGeneral", "7%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
				$htmlTh .= "<td width=\"6%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
				$htmlTh .= "<td width=\"6%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
				$htmlTh .= "<td width=\"6%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
				$htmlTh .= "<td width=\"6%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
			}
		$htmlTh .= "</tr>";
	} else {
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"".(($valCadBusq[4] == 1) ? 6 : 5)."\"></td>";
			$htmlTh .= "<td colspan=\"4\">D&iacute;as Vencidos</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"4%\"></td>";
			$htmlTh .= ($valCadBusq[4] == 1) ? ordenarCampo("xajax_listaECGeneral", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa") : "";
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "20%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "11%", $pageNum, "SUM(saldoFactura)", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
			$htmlTh .= ordenarCampo("xajax_listaECGeneral", "11%", $pageNum, "SUM(saldoFactura)", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
			$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
			$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
			$htmlTh .= "<td width=\"9%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
			$htmlTh .= "<td width=\"8%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
		$htmlTh .= "</tr>";
	}
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$totalSaldoProv = 0;
		$totalCorrienteProv = 0;
		$totalEntre1Prov = 0;
		$totalEntre2Prov = 0;
		$totalEntre3Prov = 0;
		$totalMasDeProv = 0;
		
		$sqlBusq2 = "";
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			if ($valCadBusq[4] == 1) { // 1 = Detallado por Empresa, 2 = Consolidado
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(vw_cxp_as.id_empresa = %s)",
					valTpDato($row['id_empresa'], "int"));
			} else {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(vw_cxp_as.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = vw_cxp_as.id_empresa))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
			}
		} else {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxp_as.id_empresa = %s)",
				valTpDato($row['id_empresa'], "int"));
		}
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxp_as.id_proveedor = %s",
			valTpDato($row['id_proveedor'], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("DATE(vw_cxp_as.fecha_origen) <= %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		
		if (strtotime($valCadBusq[2]) >= strtotime(date(spanDateFormat))) {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("ROUND(vw_cxp_as.saldoFactura, 2) > 0",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		} else {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("((CASE
				WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
					(vw_cxp_as.total_cuenta_pagar - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
															WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
																AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
																AND cxp_pago.fecha_pago <= %s
																AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0))
				WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
					(vw_cxp_as.total_cuenta_pagar - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
															WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
																AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
																AND cxp_pago.fecha_pago <= %s
																AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0))
			END) > 0
				AND NOT ((CASE
						WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
							(SELECT MAX(cxp_pago.fecha_pago) FROM cp_pagos_documentos cxp_pago
							WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
								AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
								AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))) 
						WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
							(SELECT MAX(cxp_pago.fecha_pago) FROM cp_pagos_documentos cxp_pago
							WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
								AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
								AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s)))
					END) < %s
						AND ((vw_cxp_as.tipoDocumento IN ('FA','ND') AND vw_cxp_as.estadoFactura IN (1))
							OR (vw_cxp_as.tipoDocumento IN ('AN','NC') AND vw_cxp_as.estadoFactura IN (3)))))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
			
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_as.id_modulo IN (%s)",
				valTpDato($valCadBusq[5], "campo"));
		}
	
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_as.tipoDocumento IN (%s)",
				valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
		}
	
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$arrayDiasVencidos = NULL;
			if (in_array("corriente",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde1",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde2",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("desde3",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1)
				AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																	WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			if (in_array("masDe",explode(",",$valCadBusq[7]))) {
				$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																								WHERE grupo_ec.idGrupoEstado = 1))",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
			}
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
		}
		
		if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxp_as.numeroFactura LIKE %s
			OR prov.nombre LIKE %s)",
				valTpDato("%".$valCadBusq[8]."%", "text"),
				valTpDato("%".$valCadBusq[8]."%", "text"));
		}
		
		$queryEstado = sprintf("SELECT
			vw_cxp_as.*,
			prov.nombre AS nombre_proveedor,
			
			(SELECT orden_tot.id_orden_tot FROM sa_orden_tot orden_tot
			WHERE orden_tot.id_factura = vw_cxp_as.idFactura) AS id_orden_tot,
			
			(CASE
				WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
					IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
							WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
								AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
								AND cxp_pago.fecha_pago <= %s
								AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0)
				WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
					IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
							WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
								AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
								AND cxp_pago.fecha_pago <= %s
								AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0)
			END) AS total_pagos,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_cp_antiguedad_saldo vw_cxp_as
			INNER JOIN cp_proveedor prov ON (vw_cxp_as.id_proveedor = prov.id_proveedor)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			$sqlBusq2);
		$rsEstado = mysql_query($queryEstado);
		if (!$rsEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsEstado = mysql_num_rows($rsEstado);
		
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
			$htmlTb .= ($contFila > 1) ? "<tr height=\"24\"><td>&nbsp;</td></tr>" : "";
			
			$htmlTb .= "<tr align=\"left\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px tituloCampo\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".$row['id_proveedor']."</td>";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"12\">".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "</tr>";
			
			$contFila2 = 0;
		}
		
		while ($rowEstado = mysql_fetch_array($rsEstado)) {
			$numeroSiniestro = "";
			$totalSaldo = 0;
			$totalCorriente = 0;
			$totalEntre1 = 0;
			$totalEntre2 = 0;
			$totalEntre3 = 0;
			$totalMasDe = 0;
			
			$fecha1 = strtotime($valCadBusq[2]);
			$fecha2 = strtotime($rowEstado['fecha_vencimiento']);
			
			$dias = ($fecha1 - $fecha2) / 86400;
			
			switch($rowEstado['id_modulo']) {
				case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
				case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
				case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
				case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
				case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
				default : $imgPedidoModulo = $rowEstado['id_modulo'];
			}
			
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array($rowEstado['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 1 : 4;
			$objDcto->tipoDocumento = $rowEstado['tipoDocumento'];
			$objDcto->tipoDocumentoMovimiento = (in_array($rowEstado['tipoDocumento'],array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $rowEstado['id_modulo'];
			$objDcto->idDocumento = $rowEstado['idFactura'];
			$aVerDcto = $objDcto->verDocumento();
			
			if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalSaldo += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
			} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalSaldo -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
			}
			
			if ($dias < $rowGrupoEstado['desde1']){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalCorriente += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalCorriente -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde1']) && ($dias <= $rowGrupoEstado['hasta1'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre1 += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre1 -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde2']) && ($dias <= $rowGrupoEstado['hasta2'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre2 += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre2 -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				}
			} else if (($dias >= $rowGrupoEstado['desde3']) && ($dias <= $rowGrupoEstado['hasta3'])){
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalEntre3 += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalEntre3 -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				}
			} else {
				if (in_array($rowEstado['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
					$totalMasDe += $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				} else if (in_array($rowEstado['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
					$totalMasDe -= $rowEstado['total_cuenta_pagar'] - $rowEstado['total_pagos'];
				}
			}
			
			if (in_array($valCadBusq[3],array(3,4))) { // 3 = General por Proveedor, 4 = General por Dcto.
				$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila2++;
				
				$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
					$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila2) + (($pageNum) * $maxRows))."</td>";
					$htmlTb .= "<td>"."</td>";
					$htmlTb .= "<td>".utf8_encode($rowEstado['nombre_empresa'])."</td>";
					$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($rowEstado['fecha_origen']))."</td>";
					$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($rowEstado['fecha_factura_proveedor']))."</td>";
					$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($rowEstado['fecha_vencimiento']))."</td>";
					$htmlTb .= "<td align=\"center\" title=\"Id Estado Cuenta: ".$rowEstado['idEstadoCuenta']."\">".utf8_encode($rowEstado['tipoDocumento']).(($rowEstado['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
					$htmlTb .= "<td align=\"right\">";
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
							$htmlTb .= "<td>".$imgPedidoModulo."</td>";
							$htmlTb .= "<td width=\"100%\">".utf8_encode($rowEstado['numeroFactura'])."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
						$htmlTb .= ($dias > 0) ? "<div class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</div>" : "";
					$htmlTb .= "</td>";
					if (in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
					} else {
						$htmlTb .= "<td>".utf8_encode($rowEstado['nombre_proveedor'])."</td>";
					}
					$htmlTb .= "<td align=\"right\">".number_format($totalSaldo, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalCorriente, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalEntre1, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalEntre2, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalEntre3, 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".number_format($totalMasDe, 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
			}
			
			$totalSaldoProv += $totalSaldo;
			$totalCorrienteProv += $totalCorriente;
			$totalEntre1Prov += $totalEntre1;
			$totalEntre2Prov += $totalEntre2;
			$totalEntre3Prov += $totalEntre3;
			$totalMasDeProv += $totalMasDe;
		}
		
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"left\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"8\">".utf8_encode($row['nombre_proveedor']).":</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalSaldoProv, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalCorrienteProv, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre1Prov, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre2Prov, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre3Prov, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalMasDeProv, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else if (in_array($valCadBusq[3],array(4))) { // 3 = General por Proveedor, 4 = General por Dcto.
		} else {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
				$htmlTb .= ($valCadBusq[4] == 1) ? "<td>".utf8_encode($row['nombre_empresa'])."</td>" : "";
				$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
				$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalSaldoProv, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalCorrienteProv, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre1Prov, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre2Prov, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalEntre3Prov, 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($totalMasDeProv, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotal[4] += $totalSaldoProv;
		$arrayTotal[5] += $totalCorrienteProv;
		$arrayTotal[6] += $totalEntre1Prov;
		$arrayTotal[7] += $totalEntre2Prov;
		$arrayTotal[8] += $totalEntre3Prov;
		$arrayTotal[9] += $totalMasDeProv;
	}
	if ($contFila > 0) {
		if (in_array($valCadBusq[3],array(3))) { // 3 = General por Proveedor, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">Totales:</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[4], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else if (in_array($valCadBusq[3],array(4))) { // 3 = General por Proveedor, 4 = General por Dcto.
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">Totales:</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[4], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		} else {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"".(($valCadBusq[4] == 1) ? 4 : 3)."\">Totales:</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[4], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaECIndividual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$queryGrupoEstado = "SELECT * FROM gruposestadocuenta";
	$rsGrupoEstado = mysql_query($queryGrupoEstado);
	if (!$rsGrupoEstado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowGrupoEstado = mysql_fetch_array($rsGrupoEstado);
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_as.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxp_as.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_as.id_proveedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(vw_cxp_as.fecha_origen) <= %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	
	if (strtotime($valCadBusq[2]) >= strtotime(date(spanDateFormat))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ROUND(vw_cxp_as.saldoFactura, 2) > 0",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((CASE
			WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
				(vw_cxp_as.total_cuenta_pagar - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
														WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
															AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
															AND cxp_pago.fecha_pago <= %s
															AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0))
			WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
				(vw_cxp_as.total_cuenta_pagar - IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
														WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
															AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
															AND cxp_pago.fecha_pago <= %s
															AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0))
		END) > 0
			AND NOT ((CASE
					WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
						(SELECT MAX(cxp_pago.fecha_pago) FROM cp_pagos_documentos cxp_pago
						WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
							AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
							AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))) 
					WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
						(SELECT MAX(cxp_pago.fecha_pago) FROM cp_pagos_documentos cxp_pago
						WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
							AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
							AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s)))
				END) < %s
					AND ((vw_cxp_as.tipoDocumento IN ('FA','ND') AND vw_cxp_as.estadoFactura IN (1))
						OR (vw_cxp_as.tipoDocumento IN ('AN','NC') AND vw_cxp_as.estadoFactura IN (3)))))",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_as.id_modulo IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_as.tipoDocumento IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$arrayDiasVencidos = NULL;
		if (in_array("corriente",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde1",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde2",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("desde3",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		if (in_array("masDe",explode(",",$valCadBusq[7]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(%s, vw_cxp_as.fecha_vencimiento) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_as.numeroFactura LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_cxp_as.*,
		prov.nombre AS nombre_proveedor,
		
		(SELECT orden_tot.id_orden_tot FROM sa_orden_tot orden_tot
		WHERE orden_tot.id_factura = vw_cxp_as.idFactura) AS id_orden_tot,
		
		(CASE
			WHEN (vw_cxp_as.tipoDocumento IN ('FA','ND')) THEN
				IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
						WHERE cxp_pago.id_documento_pago = vw_cxp_as.idFactura
							AND CONVERT(cxp_pago.tipo_documento_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
							AND cxp_pago.fecha_pago <= %s
							AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0)
			WHEN (vw_cxp_as.tipoDocumento IN ('AN','NC')) THEN
				IFNULL((SELECT SUM(cxp_pago.monto_cancelado) FROM cp_pagos_documentos cxp_pago
						WHERE cxp_pago.id_documento = vw_cxp_as.idFactura
							AND CONVERT(cxp_pago.tipo_pago, CHAR) = CONVERT(vw_cxp_as.tipoDocumento, CHAR)
							AND cxp_pago.fecha_pago <= %s
							AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND cxp_pago.fecha_pago <> DATE(cxp_pago.fecha_anulado) AND DATE(cxp_pago.fecha_anulado) > %s))),0)
		END) AS total_pagos,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cp_antiguedad_saldo vw_cxp_as
		INNER JOIN cp_proveedor prov ON (vw_cxp_as.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_as.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq);
	
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
		$htmlTh .= "<td colspan=\"10\"></td>";
		$htmlTh .= "<td colspan=\"4\">D&iacute;as Vencidos</td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Dcto. Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "fecha_vencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "6%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "10%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listaECIndividual", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Cta. Corriente");
		$htmlTh .= "<td width=\"8%\">De ".$rowGrupoEstado['desde1']." A ".$rowGrupoEstado['hasta1']."</td>";
		$htmlTh .= "<td width=\"8%\">De ".$rowGrupoEstado['desde2']." A ".$rowGrupoEstado['hasta2']."</td>";
		$htmlTh .= "<td width=\"8%\">De ".$rowGrupoEstado['desde3']." A ".$rowGrupoEstado['hasta3']."</td>";
		$htmlTh .= "<td width=\"8%\">Mas de ".$rowGrupoEstado['masDe']."</td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$numeroSiniestro = "";
		$totalSaldo = 0;
		$totalCorriente = 0;
		$totalEntre1 = 0;
		$totalEntre2 = 0;
		$totalEntre3 = 0;
		$totalMasDe = 0;
		
		$fecha1 = strtotime($valCadBusq[2]);
		$fecha2 = strtotime($row['fecha_vencimiento']);
		
		$dias = ($fecha1 - $fecha2) / 86400;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 1 : 4;
		$objDcto->tipoDocumento = $row['tipoDocumento'];
		$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $row['id_modulo'];
		$objDcto->idDocumento = $row['idFactura'];
		$aVerDcto = $objDcto->verDocumento();
		
		if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
			$totalSaldo += $row['total_cuenta_pagar'] - $row['total_pagos'];
		} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
			$totalSaldo -= $row['total_cuenta_pagar'] - $row['total_pagos'];
		}
		
		if ($dias < $rowGrupoEstado['desde1']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalCorriente += $row['total_cuenta_pagar'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalCorriente -= $row['total_cuenta_pagar'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde1'] && $dias <= $rowGrupoEstado['hasta1']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre1 += $row['total_cuenta_pagar'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre1 -= $row['total_cuenta_pagar'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde2'] && $dias <= $rowGrupoEstado['hasta2']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre2 += $row['total_cuenta_pagar'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre2 -= $row['total_cuenta_pagar'] - $row['total_pagos'];
			}
		} else if ($dias >= $rowGrupoEstado['desde3'] && $dias <= $rowGrupoEstado['hasta3']){
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalEntre3 += $row['total_cuenta_pagar'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalEntre3 -= $row['total_cuenta_pagar'] - $row['total_pagos'];
			}
		} else {
			if (in_array($row['tipoDocumento'],array("FA","ND"))) { // 1 = FA, 2 = ND
				$totalMasDe += $row['total_cuenta_pagar'] - $row['total_pagos'];
			} else if (in_array($row['tipoDocumento'],array("NC","AN","CH","TB"))){ // 3 = NC, 4 = AN, 5 = CH, 6 = TB
				$totalMasDe -= $row['total_cuenta_pagar'] - $row['total_pagos'];
			}
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_vencimiento']))."</td>";
			$htmlTb .= "<td align=\"center\" title=\"Id Estado Cuenta: ".$row['idEstadoCuenta']."\">".utf8_encode($row['tipoDocumento']).(($row['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeroFactura'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
				$htmlTb .= ($dias > 0) ? "<div class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalSaldo, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalCorriente, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalEntre1, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalEntre2, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalEntre3, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalMasDe, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[8] += $totalSaldo;
		$arrayTotal[9] += $totalCorriente;
		$arrayTotal[10] += $totalEntre1;
		$arrayTotal[11] += $totalEntre2;
		$arrayTotal[12] += $totalEntre3;
		$arrayTotal[13] += $totalMasDe;
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">Totales:</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"cargarDiasVencidos");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"cargarTipoDocumento");
$xajax->register(XAJAX_FUNCTION,"exportarAntiguedadSaldo");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"listaECGeneral");
$xajax->register(XAJAX_FUNCTION,"listaECIndividual");
?>