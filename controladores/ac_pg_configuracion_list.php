<?php


function asignarConfiguracion($idConfiguracion) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_configuracion WHERE id_configuracion = %s;",
		valTpDato($idConfiguracion, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("tdConfiguracion","innerHTML",utf8_encode($row['observacion_configuracion']));
	
	return $objResponse;
}

function buscarConfiguracion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstModuloBuscar'],
		$frmBuscar['lstConfiguracionBuscar'],
		$frmBuscar['txtCriterio']);

	$objResponse->loadCommands(listaConfiguracion(0, "config.id_configuracion", "DESC", $valBusq));
		
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

function cargaLstConfiguracion($frmConfiguracion, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmConfiguracion['txtIdEmpresa'];
	$idModulo = $frmConfiguracion['lstModulo'];
	
	$query = sprintf("SELECT * FROM pg_configuracion
	WHERE id_modulo = %s
		AND (id_configuracion NOT IN (SELECT id_configuracion FROM pg_configuracion_empresa conf_emp
									WHERE conf_emp.id_empresa = %s)
			OR (id_configuracion = %s AND %s IS NOT NULL))
	ORDER BY id_configuracion ASC;", 
		valTpDato($idModulo, "int"), 
		valTpDato($idEmpresa, "int"),
		valTpDato($selId, "int"), 
		valTpDato($selId, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstConfiguracion\" name=\"lstConfiguracion\" class=\"inputHabilitado\" onchange=\"xajax_asignarConfiguracion(this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_configuracion']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_configuracion']."\">".utf8_encode($row['id_configuracion'].".- ".$row['nombre_configuracion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstConfiguracion","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstConfiguracionBuscar($idModulo = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo = %s", 
			valTpDato($idModulo, "int"));
	}
	
	$query = sprintf("SELECT * FROM pg_configuracion %s ORDER BY nombre_configuracion ASC;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstConfiguracionBuscar\" name=\"lstConfiguracionBuscar\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		switch($row['id_modulo']) {
			case 0 : $clase = "divMsjInfoSinBorde2"; break;
			case 1 : $clase = "divMsjInfoSinBorde"; break;
			case 2 : $clase = "divMsjAlertaSinBorde"; break;
			case 3 : $clase = "divMsjInfo4SinBorde"; break;
		}
		
		$selected = ($selId == $row['id_configuracion']) ? "selected=\"selected\"" : "";
		
		$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$row['id_configuracion']."\">".utf8_encode($row['nombre_configuracion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstConfiguracionBuscar", "innerHTML", $html);
	
	return $objResponse;
}

function cargaLstModulo($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos ORDER BY descripcionModulo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstConfiguracion(xajax.getFormValues('frmConfiguracion'));\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo", "innerHTML", $html);
	
	return $objResponse;
}

function cargaLstModuloBuscar($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos
	ORDER BY descripcionModulo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModuloBuscar\" name=\"lstModuloBuscar\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstConfiguracionBuscar(this.value); byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModuloBuscar", "innerHTML", $html);
	
	return $objResponse;
}

function eliminarConfiguracion($idConfiguracionEmpresa, $frmListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"pg_configuracion_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM pg_configuracion_empresa WHERE id_configuracion_empresa = %s;",
		valTpDato($idConfiguracionEmpresa, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->loadCommands(listaConfiguracion(
		$frmListaConfiguracion['pageNum'],
		$frmListaConfiguracion['campOrd'],
		$frmListaConfiguracion['tpOrd'],
		$frmListaConfiguracion['valBusq']));
	
	return $objResponse;
}

function formConfiguracion($idConfiguracionEmpresa) {
	$objResponse = new xajaxResponse();
	
	if ($idConfiguracionEmpresa > 0) {
		if (!xvalidaAcceso($objResponse,"pg_configuracion_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarConfiguracion').click();"); return $objResponse; }
		
		$query = sprintf("SELECT *
		FROM pg_configuracion_empresa conf_emp
			INNER JOIN pg_configuracion conf ON (conf_emp.id_configuracion = conf.id_configuracion)
		WHERE id_configuracion_empresa = %s;",
			valTpDato($idConfiguracionEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdConfiguracionEmpresa","value",$row['id_configuracion_empresa']);
		$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(cargaLstModulo($row['id_empresa'], $row['id_modulo']));
		$objResponse->loadCommands(cargaLstConfiguracion(array("txtIdEmpresa" => $row['id_empresa'], "lstModulo" => $row['id_modulo']), $row['id_configuracion']));
		$objResponse->loadCommands(asignarConfiguracion($row['id_configuracion']));
		$objResponse->assign("txtValor","innerHTML",utf8_encode($row['valor']));
		$objResponse->assign("txtValorAntes","innerHTML",utf8_encode($row['valor']));
		$objResponse->call("selectedOption","lstEstatus",$row['status']);
		
		$objResponse->script("
		byId('lstModulo').onchange = function() {
			selectedOption(this.id,'".$row['id_modulo']."');
		}");
		
		$objResponse->script("
		byId('lstConfiguracion').onchange = function() {
			selectedOption(this.id,'".$row['id_configuracion']."');
		}");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_configuracion_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarConfiguracion').click();"); return $objResponse; }
			
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(cargaLstModulo($_SESSION['idEmpresaUsuarioSysGts']));
		$objResponse->loadCommands(cargaLstConfiguracion(array("txtIdEmpresa" => -1, "lstModulo" => -1)));
	}
	
	return $objResponse;
}

function guardarConfiguracion($frmConfiguracion, $frmListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmConfiguracion['txtIdEmpresa'];
	
	if ($frmConfiguracion['hddIdConfiguracionEmpresa'] > 0) {
		if (!xvalidaAcceso($objResponse,"pg_configuracion_list","editar")) { errorGuardarConfiguracion($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_configuracion_empresa SET
			id_empresa = %s,
			id_configuracion = %s,
			valor = %s,
			status = %s
		WHERE id_configuracion_empresa = %s;",
			valTpDato($idEmpresa, "int"),
			valTpDato($frmConfiguracion['lstConfiguracion'], "int"),
			valTpDato($frmConfiguracion['txtValor'], "text"),
			valTpDato($frmConfiguracion['lstEstatus'], "boolean"),
			valTpDato($frmConfiguracion['hddIdConfiguracionEmpresa'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarConfiguracion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_configuracion_list","insertar")) { errorGuardarConfiguracion($objResponse); return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_configuracion_empresa (id_empresa, id_configuracion, valor, status)
		VALUE (%s, %s, %s, %s)",
			valTpDato($idEmpresa, "int"),
			valTpDato($frmConfiguracion['lstConfiguracion'], "int"),
			valTpDato($frmConfiguracion['txtValor'], "text"),
			valTpDato($frmConfiguracion['lstEstatus'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarConfiguracion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	if ($frmConfiguracion['lstConfiguracion'] == 12 && $frmConfiguracion['txtValor'] != $frmConfiguracion['txtValorAntes']) {
		switch ($frmConfiguracion['txtValor']) { // 1 = Reposición, 2 = Promedio, 3 = FIFO
			case 1 : $tipoCosto = 1; break; // 1 = Costo Reposicion, 2 = Costo Promedio
			case 2 : $tipoCosto = 2; break;
			case 3 : $tipoCosto = 1; break;
		}
		
		$updateSQL = sprintf("UPDATE pg_precios SET 
			tipo_costo = %s
		WHERE estatus IN (1,2);",
			valTpDato($tipoCosto, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarConfiguracion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		if (function_exists("actualizarLote")) {
			// ACTUALIZA EL LOTE DEL COSTO
			$Result1 = actualizarLote("", $idEmpresa, "", "CONFIGURACION");
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarConfiguracion($objResponse); return $objResponse->alert($Result1[1]); }
		}
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
		$Result1 = actualizarSaldos($idArticuloOrg, $idCasilla, $idCasillaPredetCompra);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarConfiguracion($objResponse); return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA EL COSTO PROMEDIO
		$Result1 = actualizarCostoPromedio("", $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarConfiguracion($objResponse); return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA EL PRECIO DE VENTA
		$Result1 = actualizarPrecioVenta("", $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarConfiguracion($objResponse); return $objResponse->alert($Result1[1]); }
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarConfiguracion($objResponse);
	$objResponse->alert("Configuración Guardada con Éxito");
	
	$objResponse->script("
	byId('btnCancelarConfiguracion').click();");
	
	$objResponse->loadCommands(listaConfiguracion(
		$frmListaConfiguracion['pageNum'],
		$frmListaConfiguracion['campOrd'],
		$frmListaConfiguracion['tpOrd'],
		$frmListaConfiguracion['valBusq']));
	
	return $objResponse;
}

function listaConfiguracion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config.id_modulo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config.id_configuracion = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(config.nombre_configuracion LIKE %s
		OR config.observacion_configuracion LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT
		config_emp.*,
		config.*,
		modulo.descripcionModulo AS descripcion_modulo,
		vw_iv_emp_suc.nombre_empresa,
		vw_iv_emp_suc.id_empresa_suc,
		vw_iv_emp_suc.nombre_empresa_suc
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		LEFT JOIN pg_configuracion_empresa config_emp ON (vw_iv_emp_suc.id_empresa_reg = config_emp.id_empresa)
		RIGHT JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		LEFT JOIN pg_modulos modulo ON (config.id_modulo = modulo.id_modulo) %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td class=\"noprint\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaConfiguracion", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaConfiguracion", "24%", $pageNum, "nombre_configuracion", $campOrd, $tpOrd, $valBusq, $maxRows, "Parámetro");
		$htmlTh .= ordenarCampo("xajax_listaConfiguracion", "14%", $pageNum, "descripcion_modulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo");
		$htmlTh .= ordenarCampo("xajax_listaConfiguracion", "48%", $pageNum, "valor", $campOrd, $tpOrd, $valBusq, $maxRows, "Valor");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['status']) {
			case 0 : $imgEstatus = "<img src=\"img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "";
		}
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= ($row['id_empresa_suc'] > 0) ? utf8_encode($row['nombre_empresa'])." - <span class=\"textoNegrita_10px\">".utf8_encode($row['nombre_empresa_suc'])."<span>" : utf8_encode($row['nombre_empresa']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= $row['id_configuracion'].".- ".utf8_encode($row['nombre_configuracion']);
				$htmlTb .= "<br><span class=\"textoNegrita_10px\">(".utf8_encode($row['observacion_configuracion']).")</span>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($row['id_modulo'].".- ".$row['descripcion_modulo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode(substr($row['valor'],0,250))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblConfiguracion', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar Almacén\"/></a>",
					$contFila,
					$row['id_configuracion_empresa']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/cross.png\"/></td>",
				$row['id_configuracion_empresa']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConfiguracion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConfiguracion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaConfiguracion(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConfiguracion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaConfiguracion(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaConfiguracion","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarConfiguracion");
$xajax->register(XAJAX_FUNCTION,"buscarConfiguracion");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstConfiguracion");
$xajax->register(XAJAX_FUNCTION,"cargaLstConfiguracionBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstModuloBuscar");
$xajax->register(XAJAX_FUNCTION,"eliminarConfiguracion");
$xajax->register(XAJAX_FUNCTION,"formConfiguracion");
$xajax->register(XAJAX_FUNCTION,"guardarConfiguracion");
$xajax->register(XAJAX_FUNCTION,"listaConfiguracion");

function errorGuardarConfiguracion($objResponse) {
	$objResponse->script("
	byId('btnGuardarConfiguracion').disabled = false;
	byId('btnCancelarConfiguracion').disabled = false;");
}
?>