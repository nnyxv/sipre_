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
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdProv'],
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"),
		((count($frmBuscar['cbxDcto']) > 0) ? implode(",",$frmBuscar['cbxDcto']) : "-1"),
		$frmBuscar['txtCriterio']);
	
	switch ($frmBuscar['radioOpcion']) {
		case 1 : $objResponse->loadCommands(listaECCanceladaTodoDcto(0, "fecha_origen", "DESC", $valBusq)); break;
		case 2 : $objResponse->loadCommands(listaECCanceladaTipoDcto(0, "fecha_origen", "DESC", $valBusq)); break;
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
			$htmlTb .= "<td align=\"center\">".(strtoupper($arrayTipoPago[strtoupper($row['credito'])]))."</td>";
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
		$htmlTb .= "<td colspan=\"5\">";
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

function listaECCanceladaTipoDcto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanProvCxP;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxp_ec.estado = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_ec.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxp_ec.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_ec.id_proveedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_ec.id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_ec.tipoDocumento IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[3])."'", "defined", "'".str_replace(",","','",$valCadBusq[3])."'"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_ec.numero_documento LIKE %s
		OR vw_cxp_ec.observacion_factura LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}

	$query = sprintf("SELECT vw_cxp_ec.tipoDocumentoN, vw_cxp_ec.tipoDocumento
	FROM vw_cp_estado_cuenta vw_cxp_ec
		INNER JOIN cp_proveedor prov ON (vw_cxp_ec.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_ec.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	GROUP BY vw_cxp_ec.tipoDocumento", $sqlBusq);
	
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
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.estado = 1");
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.tipoDocumento LIKE %s",
			valTpDato($row['tipoDocumento'], "text"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxp_ec.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = vw_cxp_ec.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.id_proveedor = %s",
				valTpDato($valCadBusq[1], "int"));
		}
		
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.id_modulo IN (%s)",
				valTpDato($valCadBusq[2], "campo"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.tipoDocumento IN (%s)",
				valTpDato("'".str_replace(",","','",$valCadBusq[3])."'", "defined", "'".str_replace(",","','",$valCadBusq[3])."'"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxp_ec.numero_documento LIKE %s
			OR vw_cxp_ec.observacion_factura LIKE %s
			OR prov.nombre LIKE %s)",
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"));
		}
		
		$queryDetalle = sprintf("SELECT vw_cxp_ec.*,
			(CASE 
				WHEN (vw_cxp_ec.tipoDocumento IN ('FA','ND')) THEN
					(CASE vw_cxp_ec.estado
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado'
						WHEN 2 THEN 'Cancelado Parcial'
					END)
				WHEN (vw_cxp_ec.tipoDocumento IN ('NC','AN')) THEN
					(CASE vw_cxp_ec.estado
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Sin Asignar'
						WHEN 2 THEN 'Asignado Parcial'
						WHEN 3 THEN 'Asignado'
					END)
			END) AS estado_documento,
			prov.nombre AS nombre_proveedor,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_cp_estado_cuenta vw_cxp_ec
			INNER JOIN cp_proveedor prov ON (vw_cxp_ec.id_proveedor = prov.id_proveedor)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_ec.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq2);
		$queryDetalle .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$htmlTb .= "<tr class=\"tituloColumna\" height=\"22\">";
			$htmlTb .= "<td align=\"center\" colspan=\"13\">".$row['tipoDocumento']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "<tr class=\"tituloColumna\">";
			$htmlTb .= "<td width=\"4%\"></td>";
			$htmlTb .= ordenarCampo("xajax_listaECCanceladaTipoDcto", "14%", $pageNum, "vw_cxp_ec.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
			$htmlTb .= ordenarCampo("xajax_listaECCanceladaTipoDcto", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
			$htmlTb .= ordenarCampo("xajax_listaECCanceladaTipoDcto", "6%", $pageNum, "fecha_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Dcto. Proveedor");
			$htmlTb .= ordenarCampo("xajax_listaECCanceladaTipoDcto", "6%", $pageNum, "fecha_vencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
			$htmlTb .= ordenarCampo("xajax_listaECCanceladaTipoDcto", "6%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
			$htmlTb .= ordenarCampo("xajax_listaECCanceladaTipoDcto", "8%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
			$htmlTb .= ordenarCampo("xajax_listaECCanceladaTipoDcto", "26%", $pageNum, "prov.nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
			$htmlTb .= ordenarCampo("xajax_listaECCanceladaTipoDcto", "8%", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Dcto.");
			$htmlTb .= ordenarCampo("xajax_listaECCanceladaTipoDcto", "8%", $pageNum, "saldo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Dcto.");
			$htmlTb .= ordenarCampo("xajax_listaECCanceladaTipoDcto", "8%", $pageNum, "total_cuenta_pagar", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto.");
		$htmlTb .= "</tr>";
		$arrayTotalRenglon = NULL;
		$contFila2 = 0;
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			switch($rowDetalle['id_modulo']) {
				case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
				case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
				case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
				case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
				case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
				default : $imgPedidoModulo = $rowDetalle['id_modulo'];
			}
			
			switch($rowDetalle['estado']) {
				case 0 : $class = "class=\"divMsjError\""; break;
				case 1 : $class = "class=\"divMsjInfo\""; break;
				case 2 : $class = "class=\"divMsjAlerta\""; break;
				case 3 : $class = "class=\"divMsjInfo3\""; break;
				case 4 : $class = "class=\"divMsjInfo4\""; break;
			}
			
			$objDcto = new Documento;
			$objDcto->raizDir = $raiz;
			$objDcto->tipoMovimiento = (in_array($rowDetalle['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 1 : 4;
			$objDcto->tipoDocumento = $rowDetalle['tipoDocumento'];
			$objDcto->tipoDocumentoMovimiento = (in_array($rowDetalle['tipoDocumento'],array("NC"))) ? 2 : 1;
			$objDcto->idModulo = $rowDetalle['id_modulo'];
			$objDcto->idDocumento = $rowDetalle['id_documento'];
			$aVerDcto = $objDcto->verDocumento();
			
			switch ($rowDetalle['tipoDocumento']) {
				case "FA" : // 1 = FA
					$cantFactura++;
					$saldoTotalFactura += $rowDetalle['saldo'];
					break;
				case "ND" : // 2 = ND
					$cantNotaCargo++;
					$saldoTotalNotaCargo += $rowDetalle['saldo'];
					break;
				case "NC" : // 3 = NC
					$cantNotaCredito++;
					$saldoTotalNotaCredito += $rowDetalle['saldo'];
					break;
				case "AN" : // 4 = AN
					$cantAnticipo++;
					$saldoTotalAnticipo += $rowDetalle['saldo'];
					break;
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila2) + (($pageNum) * $maxRows))."</td>";
				$htmlTb .= "<td>".utf8_encode($rowDetalle['nombre_empresa'])."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($rowDetalle['nombre_empleado']) > 0) ? "title=\"Factura Nro: ".$rowDetalle['numero_factura_proveedor'].". Registrado por: ".$rowDetalle['nombre_empleado']."\"" : "").">".date(spanDateFormat, strtotime($rowDetalle['fecha_origen']))."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($rowDetalle['fecha_proveedor']))."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($rowDetalle['fecha_vencimiento']))."</td>";
				$htmlTb .= "<td align=\"center\" title=\"".$rowDetalle['idEstadoCuenta']."\">".utf8_encode($rowDetalle['tipoDocumento']).(($rowDetalle['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
						$htmlTb .= "<td>".$imgPedidoModulo."</td>";
						$htmlTb .= "<td>".(($rowDetalle['id_nota_cargo_planmayor'] > 0) ? "<img src=\"../img/iconos/ico_plan_mayor.png\" title=\"Factura por Plan Mayor\"/>" : "")."</td>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($rowDetalle['numero_documento'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
					$htmlTb .= ($dias > 0) ? "<div class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</div>" : "";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($rowDetalle['nombre_proveedor'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= (strlen($rowDetalle['observacion_factura']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($rowDetalle['observacion_factura'])."<span></td></tr>" : "";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">".$rowDetalle['estado_documento']."</td>";
				$htmlTb .= "<td align=\"right\">".$rowDetalle['abreviacion_moneda_local'].number_format($rowDetalle['saldo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".$rowDetalle['abreviacion_moneda_local'].number_format($rowDetalle['total_cuenta_pagar'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalRenglon[10] ++;
			$arrayTotalRenglon[11] += $rowDetalle['saldo'];
			$arrayTotalRenglon[12] += $rowDetalle['total_cuenta_pagar'];
		}
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total ".$row['tipoDocumento'].":"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalRenglon[10],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalRenglon[11],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalRenglon[12],2)."</td>";
			$htmlTb .= "<td>"."</td>";
		$htmlTb .= "</tr>";
			
		$arrayTotal[10] += $arrayTotalRenglon[10];
		$arrayTotal[11] += $arrayTotalRenglon[11];
		$arrayTotal[12] += $arrayTotalRenglon[12];
	}
	/*if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12],2)."</td>";
			$htmlTb .= "<td>"."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$sqlBusq2 = "";
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.tipoDocumento LIKE %s",
					valTpDato($row['tipoDocumento'], "text"));
				
				if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
					$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
					$sqlBusq2 .= $cond.sprintf("(vw_cxp_ec.id_empresa = %s
					OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
							WHERE suc.id_empresa = vw_cxp_ec.id_empresa))",
						valTpDato($valCadBusq[0], "int"),
						valTpDato($valCadBusq[0], "int"));
				}
				
				if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
					$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
					$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.id_proveedor = %s",
						valTpDato($valCadBusq[1], "int"));
				}
				
				$queryDetalle = sprintf("SELECT vw_cxp_ec.*,
					(CASE 
						WHEN (vw_cxp_ec.tipoDocumento IN ('FA','ND')) THEN
							(CASE vw_cxp_ec.estado
								WHEN 0 THEN 'No Cancelado'
								WHEN 1 THEN 'Cancelado'
								WHEN 2 THEN 'Cancelado Parcial'
							END)
						WHEN (vw_cxp_ec.tipoDocumento IN ('NC','AN')) THEN
							(CASE vw_cxp_ec.estado
								WHEN 0 THEN 'No Cancelado'
								WHEN 1 THEN 'Sin Asignar'
								WHEN 2 THEN 'Asignado Parcial'
								WHEN 3 THEN 'Asignado'
							END)
					END) AS estado_documento,
					prov.nombre AS nombre_proveedor,
					IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
				FROM vw_cp_estado_cuenta vw_cxp_ec
					INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_ec.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq2);
				$queryDetalle .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
				$rsDetalle = mysql_query($queryDetalle);
				if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
					$arrayTotalFinal[10] ++;
					$arrayTotalFinal[11] += $rowDetalle['saldo'];
					$arrayTotalFinal[12] += $rowDetalle['total_cuenta_pagar'];
				}
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[10],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12],2)."</td>";
				$htmlTb .= "<td>"."</td>";
			$htmlTb .= "</tr>";
		}
	}*/
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECCanceladaTipoDcto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECCanceladaTipoDcto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaECCanceladaTipoDcto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECCanceladaTipoDcto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECCanceladaTipoDcto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblIni .= "<tr>";
		$htmlTblIni .= "<td colspan=\"13\">";
			$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"8\">"."Saldos"."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"2\">"."Facturas"."</td>";
				$htmlTblIni .= "<td colspan=\"2\">"."Nota de Débito"."</td>";
				$htmlTblIni .= "<td colspan=\"2\">"."Anticipo"."</td>";
				$htmlTblIni .= "<td colspan=\"2\">"."Nota de Crédito"."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr align=\"right\">";
				$htmlTblIni .= "<td width=\"10%\">".number_format($cantFactura, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"15%\">".number_format($saldoTotalFactura, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"10%\">".number_format($cantNotaCargo, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"15%\">".number_format($saldoTotalNotaCargo, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"10%\">".number_format($cantAnticipo, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"15%\">".number_format($saldoTotalAnticipo, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"10%\">".number_format($cantNotaCredito, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"15%\">".number_format($saldoTotalNotaCredito, 2, ".", ",")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "</table>";
		$htmlTblIni .= "</td>";
	$htmlTblIni .= "</tr>";
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"13\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
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

function listaECCanceladaTodoDcto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxp_ec.estado = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_ec.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxp_ec.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_ec.id_proveedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_ec.id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_ec.tipoDocumento IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[3])."'", "defined", "'".str_replace(",","','",$valCadBusq[3])."'"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_ec.numero_documento LIKE %s
		OR vw_cxp_ec.observacion_factura LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT vw_cxp_ec.*,
		(CASE
			WHEN (vw_cxp_ec.tipoDocumento IN ('FA','ND')) THEN
				(CASE vw_cxp_ec.estado
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END)
			WHEN (vw_cxp_ec.tipoDocumento IN ('NC','AN')) THEN
				(CASE vw_cxp_ec.estado
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Sin Asignar'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
				END)
		END) AS estado_documento,
		prov.nombre AS nombre_proveedor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cp_estado_cuenta vw_cxp_ec
		INNER JOIN cp_proveedor prov ON (vw_cxp_ec.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_ec.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaECCanceladaTodoDcto", "14%", $pageNum, "vw_cxp_ec.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaECCanceladaTodoDcto", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaECCanceladaTodoDcto", "6%", $pageNum, "fecha_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Dcto. Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaECCanceladaTodoDcto", "6%", $pageNum, "fecha_vencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECCanceladaTodoDcto", "6%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECCanceladaTodoDcto", "8%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECCanceladaTodoDcto", "26%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaECCanceladaTodoDcto", "8%", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECCanceladaTodoDcto", "8%", $pageNum, "saldo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaECCanceladaTodoDcto", "8%", $pageNum, "total_cuenta_pagar", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Dcto.");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		switch($row['estado']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array($row['tipoDocumento'],array("FA","ND","AN","CH","TB"))) ? 1 : 4;
		$objDcto->tipoDocumento = $row['tipoDocumento'];
		$objDcto->tipoDocumentoMovimiento = (in_array($row['tipoDocumento'],array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $row['id_modulo'];
		$objDcto->idDocumento = $row['id_documento'];
		$aVerDcto = $objDcto->verDocumento();
		
		switch ($row['tipoDocumento']) {
			case "FA" : // 1 = FA
				$cantFactura++;
				$saldoTotalFactura += $row['saldo'];
				break;
			case "ND" : // 2 = ND
				$cantNotaCargo++;
				$saldoTotalNotaCargo += $row['saldo'];
				break;
			case "NC" : // 3 = NC
				$cantNotaCredito++;
				$saldoTotalNotaCredito += $row['saldo'];
				break;
			case "AN" : // 4 = AN
				$cantAnticipo++;
				$saldoTotalAnticipo += $row['saldo'];
				break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado']) > 0) ? "title=\"Factura Nro: ".$row['numero_factura_proveedor'].". Registrado por: ".$row['nombre_empleado']."\"" : "").">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_proveedor']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_vencimiento']))."</td>";
			$htmlTb .= "<td align=\"center\" title=\"".$row['idEstadoCuenta']."\">".utf8_encode($row['tipoDocumento']).(($row['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".(($row['id_nota_cargo_planmayor'] > 0) ? "<img src=\"../img/iconos/ico_plan_mayor.png\" title=\"Factura por Plan Mayor\"/>" : "")."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numero_documento'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
				$htmlTb .= ($dias > 0) ? "<div class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['observacion_factura']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_factura'])."<span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['estado_documento']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['saldo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['total_cuenta_pagar'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[11] += $row['saldo'];
		$arrayTotal[12] += $row['total_cuenta_pagar'];
	}
	/*if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12],2)."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[11] += $row['saldo'];
				$arrayTotalFinal[12] += $row['total_cuenta_pagar'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12],2)."</td>";
			$htmlTb .= "</tr>";
		}
	}*/
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECCanceladaTodoDcto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECCanceladaTodoDcto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaECCanceladaTodoDcto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECCanceladaTodoDcto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaECCanceladaTodoDcto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblIni .= "<tr>";
		$htmlTblIni .= "<td colspan=\"13\">";
			$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"8\">"."Saldos"."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr class=\"tituloColumna\">";
				$htmlTblIni .= "<td colspan=\"2\">"."Facturas"."</td>";
				$htmlTblIni .= "<td colspan=\"2\">"."Nota de Débito"."</td>";
				$htmlTblIni .= "<td colspan=\"2\">"."Anticipo"."</td>";
				$htmlTblIni .= "<td colspan=\"2\">"."Nota de Crédito"."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "<tr align=\"right\">";
				$htmlTblIni .= "<td width=\"10%\">".number_format($cantFactura, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"15%\">".number_format($saldoTotalFactura, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"10%\">".number_format($cantNotaCargo, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"15%\">".number_format($saldoTotalNotaCargo, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"10%\">".number_format($cantAnticipo, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"15%\">".number_format($saldoTotalAnticipo, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"10%\">".number_format($cantNotaCredito, 2, ".", ",")."</td>";
				$htmlTblIni .= "<td width=\"15%\">".number_format($saldoTotalNotaCredito, 2, ".", ",")."</td>";
			$htmlTblIni .= "</tr>";
			$htmlTblIni .= "</table>";
		$htmlTblIni .= "</td>";
	$htmlTblIni .= "</tr>";
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"13\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
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
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"cargarTipoDocumento");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"listaECCanceladaTipoDcto");
$xajax->register(XAJAX_FUNCTION,"listaECCanceladaTodoDcto");
?>