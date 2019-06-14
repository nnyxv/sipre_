<?php


function buscarMotivo($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		(is_array($frmBuscar['lstModuloAdministracionBuscar']) ? implode(",",$frmBuscar['lstModuloAdministracionBuscar']) : $frmBuscar['lstModuloAdministracionBuscar']),
		(is_array($frmBuscar['lstTipoTransaccionBuscar']) ? implode(",",$frmBuscar['lstTipoTransaccionBuscar']) : $frmBuscar['lstTipoTransaccionBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaMotivo(0, "id_motivo", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstModuloAdministracion($nombreObjeto, $onChange = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"".$onChange."\"";
	
	$array = array("CP" => "Cuentas por Pagar", "CC" => "Cuentas por Cobrar", "TE" => "Tesoreria", "CJ" => "Caja");
	$totalRows = count($array);
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	if (isset($array)) {
		foreach ($array as $indice => $valor) {
			$selected = ($selId != "" && $selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$indice."\">".utf8_encode($valor)."</option>";
		}
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto, "innerHTML", $html);
	
	return $objResponse;
}

function cargaLstModuloBuscar($nombreObjeto, $onChange = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"".$onChange."\"";
	
	$array = array("CP" => "Cuentas por Pagar", "CC" => "Cuentas por Cobrar", "TE" => "Tesoreria", "CJ" => "Caja");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	if (isset($array)) {
		foreach ($array as $indice => $valor) {
			$selected = ($selId != "" && $selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$indice."\">".utf8_encode($valor)."</option>";
		}
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto, "innerHTML", $html);
	
	return $objResponse;
}

function cargaLstTipoTransaccion($nombreObjeto, $onChange = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"".$onChange."\"";
	
	$array = array("I" => "Ingreso", "E" => "Egreso");
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	if (isset($array)) {
		foreach ($array as $indice => $valor) {
			$selected = ($selId != "" && $selId == $indice || $totalRows == 1) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$indice."\">".utf8_encode($valor)."</option>";
		}
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto, "innerHTML", $html);
	
	return $objResponse;
}

function exportarMotivo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		(is_array($frmBuscar['lstModuloAdministracionBuscar']) ? implode(",",$frmBuscar['lstModuloAdministracionBuscar']) : $frmBuscar['lstModuloAdministracionBuscar']),
		(is_array($frmBuscar['lstTipoTransaccionBuscar']) ? implode(",",$frmBuscar['lstTipoTransaccionBuscar']) : $frmBuscar['lstTipoTransaccionBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/pg_motivo_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formMotivo($idMotivo, $frmMotivo) {
	$objResponse = new xajaxResponse();
	
	if ($idMotivo > 0) {
		if (!xvalidaAcceso($objResponse,"pg_motivo_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarMotivo').click();"); return $objResponse; }
	
		$selectMotivo = sprintf("SELECT * FROM pg_motivo WHERE id_motivo = %s;",
			valTpDato($idMotivo, "int"));
		$rsMotivo = mysql_query($selectMotivo);
		if (!$rsMotivo) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
		$rowMotivo = mysql_fetch_array($rsMotivo);
		
		$objResponse->assign("hddIdMotivo","value",$rowMotivo['id_motivo']);
		$objResponse->assign("txtDescripcion","value",$rowMotivo['descripcion']);
		$objResponse->loadCommands(cargaLstModuloAdministracion("lstModuloAdministracion", "", $rowMotivo['modulo']));
		$objResponse->loadCommands(cargaLstTipoTransaccion("lstTipoTransaccion", "", $rowMotivo['ingreso_egreso']));
	} else {
		if (!xvalidaAcceso($objResponse,"pg_motivo_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarMoneda').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstModuloAdministracion("lstModuloAdministracion"));
		$objResponse->loadCommands(cargaLstTipoTransaccion("lstTipoTransaccion"));
	}
	
	return $objResponse;
}

function guardarMotivo($frmMotivo, $frmListaMotivo) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($frmMotivo['hddIdMotivo'] > 0) {
		if (!xvalidaAcceso($objResponse,"pg_motivo_list","editar")) { errorGuardarMotivo($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_motivo SET
			descripcion = %s,
			modulo = %s,
			ingreso_egreso = %s
		WHERE id_motivo = %s;",
			valTpDato($frmMotivo['txtDescripcion'], "text"),
			valTpDato($frmMotivo['lstModuloAdministracion'], "text"),
			valTpDato($frmMotivo['lstTipoTransaccion'], "text"),
			valTpDato($frmMotivo['hddIdMotivo'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarMotivo($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_motivo_list","insertar")) { errorGuardarMotivo($objResponse); return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_motivo (descripcion, modulo, ingreso_egreso, id_empleado_creador)
		VALUES (%s, %s, %s, %s);",
			valTpDato($frmMotivo['txtDescripcion'], "text"),
			valTpDato($frmMotivo['lstModuloAdministracion'], "text"),
			valTpDato($frmMotivo['lstTipoTransaccion'], "text"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarMotivo($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarMotivo($objResponse);
	$objResponse->alert("Motivo Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarMotivo').click();");
	
	$objResponse->loadCommands(listaMotivo(
		$frmListaMotivo['pageNum'],
		$frmListaMotivo['campOrd'],
		$frmListaMotivo['tpOrd'],
		$frmListaMotivo['valBusq']));
	
	return $objResponse;
}

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("motivo.modulo IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[0])."'", "defined", "'".str_replace(",","','",$valCadBusq[0])."'"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("motivo.ingreso_egreso IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[1])."'", "defined", "'".str_replace(",","','",$valCadBusq[1])."'"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(motivo.descripcion LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT motivo.*,
		(CASE motivo.modulo
			WHEN 'CC' THEN	'Cuentas por Cobrar'
			WHEN 'CP' THEN	'Cuentas por Pagar'
			WHEN 'CJ' THEN	'Caja'
			WHEN 'TE' THEN	'Tesorería'
		END) AS descripcion_modulo_transaccion,
		
		(CASE motivo.ingreso_egreso
			WHEN 'I' THEN	'Ingreso'
			WHEN 'E' THEN	'Egreso'
		END) AS descripcion_tipo_transaccion,
		
		((SELECT COUNT(cxc_nd_det_motivo.id_motivo) FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
			WHERE cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
			+ (SELECT COUNT(cxc_nc_det_motivo.id_motivo) FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
				WHERE cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
			+ (SELECT COUNT(cxp_nd_det_motivo.id_motivo) FROM cp_notacargo_detalle_motivo cxp_nd_det_motivo
				WHERE cxp_nd_det_motivo.id_motivo = motivo.id_motivo)
			+ (SELECT COUNT(cxp_nc_det_motivo.id_motivo) FROM cp_notacredito_detalle_motivo cxp_nc_det_motivo
				WHERE cxp_nc_det_motivo.id_motivo = motivo.id_motivo)
			+ (SELECT COUNT(te_dep.id_motivo) FROM te_depositos te_dep
				WHERE te_dep.id_motivo = motivo.id_motivo)
			+ (SELECT COUNT(te_nc.id_motivo) FROM te_nota_credito te_nc
				WHERE te_nc.id_motivo = motivo.id_motivo)
			+ (SELECT COUNT(te_nd.id_motivo) FROM te_nota_debito te_nd
				WHERE te_nd.id_motivo = motivo.id_motivo)) AS cantidad_documentos
	FROM pg_motivo motivo %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "6%", $pageNum, "id_motivo", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "48%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "16%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Transacción");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "10%", $pageNum, "cantidad_documentos", $campOrd, $tpOrd, $valBusq, $maxRows, "Cant. Dctos.");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch($row['modulo']) {
			case "CC" : $imgPedidoModulo = "<img src=\"img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("CxC")."\"/>"; break;
			case "CP" : $imgPedidoModulo = "<img src=\"img/iconos/ico_cuentas_pagar.gif\" title=\"".utf8_encode("CxP")."\"/>"; break;
			case "CJ" : break;
			case "TE" : $imgPedidoModulo = "<img src=\"img/iconos/ico_tesoreria.gif\" title=\"".utf8_encode("Tesorería")."\"/>"; break;
			default : $imgPedidoModulo = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".($row['descripcion_modulo_transaccion'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".($row['descripcion_tipo_transaccion'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cantidad_documentos'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				if ($row['fijo'] == 0){
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblMotivo', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_motivo']);
				}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMotivo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_pg.gif\"/>");
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("divListaMotivo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"cargaLstModuloAdministracion");
$xajax->register(XAJAX_FUNCTION,"cargaLstModuloBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoTransaccion");
$xajax->register(XAJAX_FUNCTION,"exportarMotivo");
$xajax->register(XAJAX_FUNCTION,"formMotivo");
$xajax->register(XAJAX_FUNCTION,"guardarMotivo");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");

function errorGuardarMotivo($objResponse) {
	$objResponse->script("
	byId('btnGuardarMotivo').disabled = false;
	byId('btnCancelarMotivo').disabled = false;");
}
?>