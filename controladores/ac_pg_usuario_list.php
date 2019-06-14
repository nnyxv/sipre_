<?php

function formPrivilegios($idUsuarioObjetivo,$idUsuarioCopiar,$eliminarPrivilegios){
	$objResponse = new xajaxResponse();
	if (!xvalidaAcceso($objResponse,'pg_privilegio','editar')) { return $objResponse; }

	if ($idUsuarioCopiar == NULL){
		return $objResponse->alert("No ha seleccionado el usuario para copiar, por favor seleccione un usuario para copiar sus privilegios");
	}
	
	if($idUsuarioObjetivo == $idUsuarioCopiar){
		return $objResponse->alert("Selecciono el mismo usuario, debe seleccionar un usuario diferente para copiar los privilegios");
	}
	
	if ($idUsuarioObjetivo > 0 && $idUsuarioCopiar > 0){
	
		
		if($eliminarPrivilegios==true){
			$queryUsuario=sprintf("DELETE FROM pg_menu_usuario WHERE id_usuario = %s;", valTpDato($idUsuarioObjetivo, "int"));
			$rsUsuario = mysql_query($queryUsuario);
			if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}

		
		$queryUsuario = sprintf("INSERT INTO pg_menu_usuario SELECT NULL,%s,id_empresa,id_elemento_menu,acceso,insertar,editar,eliminar,desincorporar FROM pg_menu_usuario WHERE id_usuario = %s AND id_elemento_menu NOT IN (SELECT id_elemento_menu FROM pg_menu_usuario WHERE id_usuario = %s)", 
		valTpDato($idUsuarioObjetivo, "int"),
		valTpDato($idUsuarioCopiar, "int"),
		valTpDato($idUsuarioObjetivo, "int"));
		$rsUsuario = mysql_query($queryUsuario);
		if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$objResponse->alert("Cambios guardados con exito.");
		$objResponse->script("byId('imgCerrarDivFlotante32').click()");
		
		return $objResponse;
	}
}

function cargarEmpleadoPrivilegios($idUsuario){
	$objResponse = new xajaxResponse();

	$queryEmpleado = sprintf("SELECT pg_empleado.id_empleado, pg_empleado.nombre_empleado, pg_empleado.apellido,pg_usuario.nombre_usuario,pg_usuario.id_usuario FROM pg_empleado INNER JOIN pg_usuario ON pg_empleado.id_empleado = pg_usuario.id_empleado WHERE pg_usuario.id_usuario = %s;", 
		valTpDato($idUsuario, "int"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	$nombreEmpleado=$rowEmpleado['nombre_empleado'] . " " . $rowEmpleado['apellido'];

	$objResponse->assign ("hddIdEmpleadoObj","value",$rowEmpleado['id_empleado']);
	$objResponse->assign ("txtNombreEmpleadoObj","value",utf8_encode($nombreEmpleado));
	$objResponse->assign ("hddIdUsuarioObj","value",$rowEmpleado['id_usuario']);
	$objResponse->assign ("txtNombreUsuarioObj","value",$rowEmpleado['nombre_usuario']);
	
	return $objResponse;
}

function asignarEmpleadoPrivilegios($idEmpleado, $cerrarVentana = "true"){
	$objResponse = new xajaxResponse();

	$queryEmpleado = sprintf("SELECT pg_empleado.id_empleado, pg_empleado.nombre_empleado, pg_empleado.apellido,pg_usuario.nombre_usuario,pg_usuario.id_usuario FROM pg_empleado INNER JOIN pg_usuario ON pg_empleado.id_empleado = pg_usuario.id_empleado WHERE pg_empleado.id_empleado = %s;", 
		valTpDato($idEmpleado, "int"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	$nombreEmpleado=$rowEmpleado['nombre_empleado'] . " " . $rowEmpleado['apellido'];

	$objResponse->assign ("hddIdEmpleadoCopy","value",$rowEmpleado['id_empleado']);
	$objResponse->assign ("txtNombreEmpleadoCopy","value",utf8_encode($nombreEmpleado));
	$objResponse->assign ("hddIdUsuarioCopy","value",$rowEmpleado['id_usuario']);
	$objResponse->assign ("txtNombreUsuarioCopy","value",$rowEmpleado['nombre_usuario']);

	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaEmpleado').click();");
	}

	return $objResponse;
}

function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
		OR vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_empleado.nombre_cargo LIKE %s)", 
			valTpDato("%".$valCadBusq[0]."%", "text"), 
			valTpDato("%".$valCadBusq[0]."%", "text"), 
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_empleado.id_empleado, 
		vw_pg_empleado.cedula, 
		vw_pg_empleado.nombre_empleado, 
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("C.I. / R.I.F."));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleadoPrivilegios('".$row['id_empleado']."');\" title=\"Seleccionar\"><img src=\"img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['cedula'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s)\">", 
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEmpleado", "innerHTML", $htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function buscarEmpleado($frmBuscarEmpleado) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s", 
		$frmBuscarEmpleado['txtCriterioBuscarEmpleado']);
		
	$objResponse->loadCommands(listaEmpleado(0, "id_empleado", "ASC", $valBusq));
		
	return $objResponse;
}

/*---------------------------------------------------------------------------------------*/
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

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (strlen($frmBuscarEmpresa['hddObjDestino']) > 0) {
		$valBusq = sprintf("%s|%s|%s",
			$frmBuscarEmpresa['hddObjDestino'],
			$frmBuscarEmpresa['hddNomVentana'],
			$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
		
		$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
	} else {
		$valBusq = sprintf("%s",
			$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
		
		$objResponse->loadCommands(listaEmpresa(0, "id_empresa_reg", "ASC", $valBusq));
	}
		
	return $objResponse;
}

function buscarUsuario($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['lstUnipersonal'],
		$frmBuscar['txtCriterio']);

	$objResponse->loadCommands(listaUsuario(0, "nombre_cargo", "ASC", $valBusq));

	return $objResponse;
}

function cargaLstConfiguracion($frmConfiguracion, $frmUsuario, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmConfiguracion['txtIdEmpresa'];
	$idModulo = $frmConfiguracion['lstModulo'];
	$idUsuario = $frmUsuario['hddIdUsuario'];
	
	$query = sprintf("SELECT * FROM pg_configuracion
	WHERE id_modulo = %s
		AND (id_configuracion NOT IN (SELECT id_configuracion FROM pg_configuracion_usuario config_usu
									WHERE config_usu.id_empresa = %s
										AND config_usu.id_usuario = %s)
			OR (id_configuracion = %s AND %s IS NOT NULL))
		AND id_configuracion IN (19)
	ORDER BY id_configuracion ASC;", 
		valTpDato($idModulo, "int"), 
		valTpDato($idEmpresa, "int"),
		valTpDato($idUsuario, "int"),
		valTpDato($selId, "int"), 
		valTpDato($selId, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
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

function cargaLstEmpleado($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM vw_pg_empleados
	WHERE id_empresa = %s
		AND ((activo = 1 AND id_empleado NOT IN (SELECT usuario.id_empleado FROM pg_usuario usuario WHERE usuario.id_empleado IS NOT NULL))
			OR (id_empleado = %s AND %s IS NOT NULL))
	ORDER BY nombre_empleado",
		valTpDato($idEmpresa, "int"),
		valTpDato($selId, "int"),
		valTpDato($selId, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstEmpleado\" name=\"lstEmpleado\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
		
		switch($row['activo']) {
			case 0 : $clase = "divMsjErrorSinBorde"; break;
			case 1 : $clase = "divMsjInfoSinBorde"; break;
		}

		$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpleado","innerHTML",$html);

	return $objResponse;
}

function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	$queryEmpresaSuc = sprintf("SELECT DISTINCT
		id_empresa_reg,
		nombre_empresa
	FROM vw_iv_empresas_sucursales
	WHERE id_empresa_padre_suc IS NULL
		AND id_empresa_reg <> 100
	ORDER BY nombre_empresa_suc ASC",
		valTpDato($row['id_empresa'], "int"));
	$rsEmpresaSuc = mysql_query($queryEmpresaSuc);
	if (!$rsEmpresaSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsEmpresaSuc = mysql_num_rows($rsEmpresaSuc);
	while ($rowEmpresaSuc = mysql_fetch_assoc($rsEmpresaSuc)) {
		$selected = ($selId == $rowEmpresaSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
	
		$htmlOption .= "<option ".$selected." value=\"".$rowEmpresaSuc['id_empresa_reg']."\">".utf8_encode($rowEmpresaSuc['nombre_empresa'])."</option>";	
	}
	
	$query = sprintf("SELECT DISTINCT
		id_empresa,
		nombre_empresa
	FROM vw_iv_empresas_sucursales
	WHERE id_empresa_padre_suc IS NOT NULL
		AND id_empresa_reg <> 100
	ORDER BY nombre_empresa");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$htmlOption .= "<optgroup label=\"".$row['nombre_empresa']."\">";
		
		$queryEmpresaSuc = sprintf("SELECT DISTINCT
			id_empresa_reg,
			nombre_empresa_suc,
			sucursal
		FROM vw_iv_empresas_sucursales
		WHERE id_empresa_padre_suc = %s
			AND id_empresa_reg <> 100
		ORDER BY nombre_empresa_suc ASC",
			valTpDato($row['id_empresa'], "int"));
		$rsEmpresaSuc = mysql_query($queryEmpresaSuc);
		if (!$rsEmpresaSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowEmpresaSuc = mysql_fetch_assoc($rsEmpresaSuc)) {
			$selected = ($selId == $rowEmpresaSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
		
			$htmlOption .= "<option ".$selected." value=\"".$rowEmpresaSuc['id_empresa_reg']."\">".utf8_encode($rowEmpresaSuc['nombre_empresa_suc'])."</option>";	
		}
	
		$htmlOption .= "</optgroup>";
	}
	
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstEmpleado(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos ORDER BY descripcionModulo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);	
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstConfiguracion(xajax.getFormValues('frmConfiguracion'), xajax.getFormValues('frmUsuario'));\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo", "innerHTML", $html);
	
	return $objResponse;
}

function cargaLstPerfil($selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM pg_perfil ORDER BY nombre_perfil;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstPerfil\" name=\"lstPerfil\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_perfil']) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$row['id_perfil']."\">".utf8_encode($row['nombre_perfil'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstPerfil","innerHTML",$html);

	return $objResponse;
}

function desbloquearSesion($idUsuario, $frmListaUsuario) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE pg_block_log SET
		lock_s = 0
	WHERE usuario LIKE (SELECT usu.nombre_usuario FROM pg_usuario usu
						WHERE usu.id_usuario = %s);",
		valTpDato($idUsuario, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Desbloqueo de sesión del usuario guardado con éxito");
	
	$objResponse->loadCommands(listaUsuario(
		$frmListaUsuario['pageNum'],
		$frmListaUsuario['campOrd'],
		$frmListaUsuario['tpOrd'],
		$frmListaUsuario['valBusq']));

	return $objResponse;
}

function eliminarConfiguracionUsuario($frmUsuario) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmUsuario['cbxItmConfig'])) {
		foreach($frmUsuario['cbxItmConfig'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmConfig:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarConfiguracionUsuario(xajax.getFormValues('frmUsuario'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmUsuario['cbx1'];
	
	return $objResponse;
}

function eliminarEmpresaUsuario($frmUsuario) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmUsuario['cbxItm'])) {
		foreach($frmUsuario['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarEmpresaUsuario(xajax.getFormValues('frmUsuario'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUsuario['cbx'];
	
	return $objResponse;
}

function exportarUsuarios($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['lstUnipersonal'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/pg_usuario_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formConfiguracion($idConfiguracionUsuario, $frmUsuario) {
	$objResponse = new xajaxResponse();
	
	if ($idConfiguracionUsuario > 0) {
		if (!xvalidaAcceso($objResponse,"pg_usuario_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();"); return $objResponse; }
		
		$query = sprintf("SELECT *
		FROM pg_configuracion_usuario config_usu
			INNER JOIN pg_configuracion config ON (config_usu.id_configuracion = config.id_configuracion)
		WHERE config_usu.id_configuracion_usuario = %s;",
			valTpDato($idConfiguracionUsuario, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdConfigUsuario","value",$row['id_configuracion_usuario']);
		$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(cargaLstModulo($row['id_empresa'], $row['id_modulo']));
		$objResponse->loadCommands(cargaLstConfiguracion(array("txtIdEmpresa" => $row['id_empresa'], "lstModulo" => $row['id_modulo']), $frmUsuario, $row['id_configuracion']));
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
		if (!xvalidaAcceso($objResponse,"pg_usuario_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();"); return $objResponse; }
			
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "", false));
		$objResponse->loadCommands(cargaLstModulo($_SESSION['idEmpresaUsuarioSysGts']));
		$objResponse->loadCommands(cargaLstConfiguracion(array("txtIdEmpresa" => -1, "lstModulo" => -1), $frmUsuario));
	}
	
	return $objResponse;
}

function formUsuario($idUsuario, $frmUsuario) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUsuario['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmUsuario['cbx1'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj1)) {
		foreach($arrayObj1 as $indice1 => $valor1) {
			$objResponse->script("
			fila = document.getElementById('trItmConfig:".$valor1."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	if ($idUsuario > 0) {
		if (!xvalidaAcceso($objResponse,"pg_usuario_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
			valTpDato($idUsuario, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdUsuario","value",$row['id_usuario']);
		$objResponse->assign("txtUsuario","value",utf8_encode($row['nombre_usuario']));
		$objResponse->assign("txtContrasena","value",$row['clave']);
		$objResponse->loadCommands(cargaLstEmpresa($row['id_empresa']));
		$objResponse->loadCommands(cargaLstEmpleado($row['id_empresa'], $row['id_empleado']));
		$objResponse->loadCommands(cargaLstPerfil($row['perfil_precargado']));
		
		$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s;",
			valTpDato($idUsuario, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_array($rs)) {
			$checked = ($row['predeterminada'] == 1) ? "checked=\"checked\"" : "";
			
			$Result1 = insertarItemEmpresa($contFila, $row['id_usuario_empresa'], $row['id_empresa_reg'], $checked);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		$query = sprintf("SELECT * FROM pg_configuracion_usuario WHERE id_usuario = %s;",
			valTpDato($idUsuario, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_array($rs)) {
			$Result1 = insertarItemConfiguracion($contFila, $row['id_configuracion_usuario'], $row['id_configuracion'], $row['id_empresa'], $row['valor']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj1[] = $contFila;
			}
		}
	} else {
		if (!xvalidaAcceso($objResponse,"pg_usuario_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstEmpresa());
		$objResponse->loadCommands(cargaLstEmpleado('-1'));
		$objResponse->loadCommands(cargaLstPerfil());
	}

	return $objResponse;
}

function guardarUsuario($frmUsuario, $frmListaUsuario) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUsuario['cbx'];
	
	mysql_query("START TRANSACTION;");
	
	$idUsuario = $frmUsuario['hddIdUsuario'];
	
	if ($idUsuario > 0) {
		if (!xvalidaAcceso($objResponse,"pg_usuario_list","editar")) { return $objResponse; }
		
		// VERIFICA LA CLAVE ANTERIOR
		$query = sprintf("SELECT * FROM pg_usuario
		WHERE id_usuario = %s
			AND clave LIKE %s;",
			valTpDato($idUsuario, "int"),
			valTpDato($frmUsuario['txtContrasena'], "text"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
		$asunto = "Cuenta de Usuario de Sistema";
		$body .= "<p>Bienvenido al Sistema SIPRE 2.0.</p><p>Su cuenta de usuario ha sido modificada:</p><br>Nombre: ".$nombre_usuario;
		
		if (!($totalRows > 0)) {
			$body .= "<p>Clave:<strong> {</strong><span style=\"color:#FFFFFF;\">".$clave."</span><strong>}</strong><em>Sombree con el mouse el espacio delimitado por las llaves <strong> { } </strong>para descubrir su clave, si no aparece haga clic con el bot&oacute;n derecho del mouse y seleccione <strong>Copiar</strong></em></p><p><strong>IMPORTANTE:</strong> Debe cambiar su clave de usuario en: <strong>Par&aacute;metros -> Cambiar clave de usuario</strong></p>";
			//$altbody.="Clave: ".$clave."\nIMPORTANTE: Debe cambiar su clave de usuario en: Pantalla principal/cambiar clave de usuario \n";
		}
		
		$frmUsuario['txtContrasena'] = ($totalRows > 0) ? $frmUsuario['txtContrasena'] : md5($frmUsuario['txtContrasena']);
		
		$updateSQL = sprintf("UPDATE pg_usuario SET
			id_empresa = %s,
			nombre_usuario = %s,
			clave = %s,
			perfil_precargado = %s,
			id_empleado = %s,
			ultima_fecha_cambio_clave = NULL
		WHERE id_usuario = %s;",
			valTpDato($frmUsuario['lstEmpresa'], "int"),
			valTpDato($frmUsuario['txtUsuario'], "text"),
			valTpDato($frmUsuario['txtContrasena'], "text"),
			valTpDato($frmUsuario['lstPerfil'], "int"),
			valTpDato($frmUsuario['lstEmpleado'], "int"),
			valTpDato($idUsuario, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_usuario_list","insertar")) { return $objResponse; }
		
		$asunto = "Nueva Cuenta de Usuario de Sistema";
		$body .= "<p>Bienvenido al Sistema SIPRE 2.0.</p><p>Su cuenta de usuario ha sido creada<br>Nombre: ".$frmUsuario['txtUsuario']."<br>Clave: ".$frmUsuario['txtContrasena']."</p><p><strong>IMPORTANTE:</strong> Debe cambiar su clave de usuario en: <strong>Par&aacute;metros -> Cambiar clave de usuario</strong></p>";
		
		$insertSQL = sprintf("INSERT INTO pg_usuario (id_empresa, nombre_usuario, clave, perfil_precargado, id_empleado)
		VALUES (%s, %s, %s, %s, %s);",
			valTpDato($frmUsuario['lstEmpresa'], "int"),
			valTpDato($frmUsuario['txtUsuario'], "text"),
			valTpDato(md5($frmUsuario['txtContrasena']), "text"),
			valTpDato($frmUsuario['lstPerfil'], "int"),
			valTpDato($frmUsuario['lstEmpleado'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idUsuario = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$nuevoUsuario = true;
	}
	
	// VERIFICA SI LAS EMPRESAS ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryUsuarioEmpresa = sprintf("SELECT * FROM pg_usuario_empresa WHERE id_usuario = %s;",
		valTpDato($idUsuario, "int"));
	$rsUsuarioEmpresa = mysql_query($queryUsuarioEmpresa);
	if (!$rsUsuarioEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowUsuarioEmpresa = mysql_fetch_assoc($rsUsuarioEmpresa)) {
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if ($rowUsuarioEmpresa['id_usuario_empresa'] == $frmUsuario['hddIdUsuarioEmpresa'.$valor]) {
					$existRegDet = true;
					
					$predeterminada = ($valor == $frmUsuario['rbtPredeterminado']) ? true : false;
					
					$updateSQL = sprintf("UPDATE pg_usuario_empresa SET
						predeterminada = %s
					WHERE id_usuario_empresa = %s;",
						valTpDato($predeterminada, "boolean"),
						valTpDato($rowUsuarioEmpresa['id_usuario_empresa'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM pg_usuario_empresa WHERE id_usuario_empresa = %s;",
				valTpDato($rowUsuarioEmpresa['id_usuario_empresa'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	// INSERTA EL DETALLE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if (strlen($frmUsuario['hddIdEmpresa'.$valor]) > 0) {
				if ($frmUsuario['hddIdUsuarioEmpresa'.$valor] == "") {
					$idEmpresa = $frmUsuario['hddIdEmpresa'.$valor];
					
					$predeterminada = ($valor == $frmUsuario['rbtPredeterminado']) ? true : false;
					
					$insertSQL = sprintf("INSERT INTO pg_usuario_empresa (id_usuario, id_empresa, predeterminada)
					VALUE (%s, %s, %s);",
						valTpDato($idUsuario, "int"),
						valTpDato($idEmpresa, "int"),
						valTpDato($predeterminada, "boolean"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
	}
	
	
	// VERIFICA SI LOS PARAMETROS DE CONFIGURACION EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryConfigUsuario = sprintf("SELECT * FROM pg_configuracion_usuario WHERE id_usuario = %s;",
		valTpDato($idUsuario, "int"));
	$rsConfigUsuario = mysql_query($queryConfigUsuario);
	if (!$rsConfigUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowConfigUsuario = mysql_fetch_assoc($rsConfigUsuario)) {
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if ($rowConfigUsuario['id_configuracion_usuario'] == $frmUsuario['hddIdConfigUsuario'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM pg_configuracion_usuario WHERE id_configuracion_usuario = %s;",
				valTpDato($rowConfigUsuario['id_configuracion_usuario'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	// INSERTA EL DETALLE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if ($frmUsuario['hddIdConfiguracion'.$valor] > 0 && $frmUsuario['hddIdEmpresa'.$valor] > 0) {
				if ($frmUsuario['hddIdConfigUsuario'.$valor] == "") {
					$insertSQL = sprintf("INSERT INTO pg_configuracion_usuario (id_empresa, id_configuracion, id_usuario, valor)
					VALUE (%s, %s, %s, %s);",
						valTpDato($frmUsuario['hddIdEmpresa'.$valor], "int"),
						valTpDato($frmUsuario['hddIdConfiguracion'.$valor], "int"),
						valTpDato($idUsuario, "int"),
						valTpDato($frmUsuario['txtValor'.$valor], "text"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
	}
	
	
	if ($frmUsuario['cbxRecargar'] || $nuevoUsuario == true) {
		if ($idUsuario > 0) {
			$deleteSQL = sprintf("DELETE FROM pg_menu_usuario WHERE id_usuario = %s;",
				valTpDato($idUsuario, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		// BUSCA LAS EMPRESAS ASIGNADAS AL USUARIO
		$queryUsuarioEmpresa = sprintf("SELECT * FROM pg_usuario_empresa WHERE id_usuario = %s;",
			valTpDato($idUsuario, "int"));
		$rsUsuarioEmpresa = mysql_query($queryUsuarioEmpresa);
		if (!$rsUsuarioEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowUsuarioEmpresa = mysql_fetch_assoc($rsUsuarioEmpresa)) {
			$idEmpresa = $rowUsuarioEmpresa['id_empresa'];
		
			// RECARGA LOS PROVILEGIOS DEL PERFIL
			$insertSQL = sprintf("INSERT INTO pg_menu_usuario (id_usuario, id_empresa, id_elemento_menu, acceso, insertar, editar, eliminar, desincorporar)
			SELECT %s, %s, id_elemento_menu, acceso, insertar, editar, eliminar, desincorporar
			FROM pg_perfil_menu
			WHERE id_perfil = %s;",
				valTpDato($idUsuario, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($frmUsuario['lstPerfil'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		$nota .= "\nSe han Recargado los privilegios de acceso del usuario desde el perfil.";	
		$body .= "<p>Se han recargado sus privilegios de usuario. Cualquier problema de acceso no autorizado que no le permita utilizar cierta funci&oacute;n del sistema comun&iacute;quelo a Soporte T&eacute;cnico</p>";
		//$altbody.="Se ha recargado sus privilegios de usuario. \n";
	}
	
	require_once 'servicios/PHPMailer/class.phpmailer.php';
	
	$sqlEmpleado = sprintf("SELECT * FROM pg_empleado WHERE id_empleado = %s;",
		valTpDato($frmUsuario['lstEmpleado'], "int"));
	$rsEmpleado = mysql_query($sqlEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$emailEmpleado = $rowEmpleado['email'];

	date_default_timezone_set('America/Caracas');
	
	if ($frmUsuario['rbtEnviarCorreo'] == 1) {
		$mail = new PHPMailer();

		$mail->IsSMTP();
		
		$mail->SMTPAuth= true;
		$mail->Host = "mail.gotosys.com";
		$mail->Port = 25;

		$mail->Username = 'erp';
		$mail->Password = "123456";
		$mail->SetFrom("erp@gotosys.com", "SIPRE 2.0");

		$mail->MsgHTML("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
						<html>
							<head></head>
							<body>".$body."</body>
						</html>");

		$mail->Subject = utf8_decode("SIPRE 2.0");
		$mail->AltBody = "To view the message, please use an HTML compatible email viewer!";

		$mail->AddAddress($emailEmpleado, $rowEmpleado['nombre_empleado']." ".$rowEmpleado['apellido']);
		
		if ($mail->Send()) {
			$nota .= "\nMensaje enviado correctamente al: ".$emailEmpleado.".";
		} else {
			return $mail->ErrorInfo;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Usuario Guardado con Éxito".$nota);
	
	$objResponse->script("byId('btnCancelarUsuario').click();");
	
	$objResponse->loadCommands(listaUsuario(
		$frmListaUsuario['pageNum'],
		$frmListaUsuario['campOrd'],
		$frmListaUsuario['tpOrd'],
		$frmListaUsuario['valBusq']));
	
	return $objResponse;
}

function insertarConfiguracion($frmConfiguracion, $frmUsuario) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUsuario['cbx1'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$idConfiguracion = $frmConfiguracion['lstConfiguracion'];
	$idEmpresa = $frmConfiguracion['txtIdEmpresa'];
	
	if ($idConfiguracion > 0 && $idEmpresa > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmUsuario['hddIdConfiguracion'.$valor] == $idConfiguracion && $frmUsuario['hddIdEmpresa'.$valor] == $idEmpresa) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemConfiguracion($contFila, "", $idConfiguracion, $idEmpresa, $frmConfiguracion['txtValor']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			return $objResponse->alert("Este item ya se encuentra incluido");
		}
	}
	
	$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	
	return $objResponse;
}

function insertarEmpresa($idEmpresa, $frmUsuario) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUsuario['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idEmpresa > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmUsuario['hddIdEmpresa'.$valor] == $idEmpresa) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemEmpresa($contFila, "", $idEmpresa, "checked=\"checked\"");
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			return $objResponse->alert("Este item ya se encuentra incluido");
		}
	}
	
	return $objResponse;
}

function listaUsuario($pageNum = 0, $campOrd = "nombre_cargo", $tpOrd = "ASC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("perfil = 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_empleados.activo = %s",
			valTpDato($valCadBusq[0], "boolean"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT cargo.unipersonal FROM pg_cargo cargo
			WHERE cargo.id_cargo = vw_pg_empleados.id_cargo) = %s",
			valTpDato($valCadBusq[1], "boolean"));
	}

	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleados.cedula LIKE %s
		OR vw_pg_empleados.nombre_empleado LIKE %s
		OR vw_pg_empleados.nombre_departamento LIKE %s
		OR vw_pg_empleados.nombre_cargo LIKE %s
		OR usu.nombre_usuario LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}

	$query = sprintf("SELECT
		vw_pg_empleados.nombre_empleado,
		vw_pg_empleados.nombre_cargo,
		
		(SELECT cargo.unipersonal FROM pg_cargo cargo
		WHERE cargo.id_cargo = vw_pg_empleados.id_cargo) AS unipersonal,
		
		usu.id_usuario,
		usu.nombre_usuario,
		usu.fecha_creacion,
		
		vw_pg_empleados.nombre_empresa,
		
		(SELECT perfil.nombre_perfil FROM pg_perfil perfil WHERE perfil.id_perfil = usu.perfil_precargado) AS perfil_precargado,
		
		(SELECT COUNT(block_log.id_block_log) FROM pg_block_log block_log
		WHERE block_log.usuario LIKE usu.nombre_usuario
			AND block_log.lock_s = 1) AS cant_block_log,
			
		vw_pg_empleados.activo
	FROM vw_pg_empleados
		INNER JOIN pg_usuario usu ON (vw_pg_empleados.id_empleado = usu.id_empleado) %s", $sqlBusq);

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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaUsuario", "8%", $pageNum, "id_usuario", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaUsuario", "18%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Empleado");
		$htmlTh .= ordenarCampo("xajax_listaUsuario", "18%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Cargo");
		$htmlTh .= ordenarCampo("xajax_listaUsuario", "12%", $pageNum, "nombre_usuario", $campOrd, $tpOrd, $valBusq, $maxRows, "Usuario");
		$htmlTh .= ordenarCampo("xajax_listaUsuario", "8%", $pageNum, "fecha_creacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Creación");
		$htmlTh .= ordenarCampo("xajax_listaUsuario", "18%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaUsuario", "18%", $pageNum, "perfil_precargado", $campOrd, $tpOrd, $valBusq, $maxRows, "Perfil Precargado");
		$htmlTh .= "<td colspan=\"5\">Acciones</td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['activo']) {
			case 0 : $imgEstatus = "<img src=\"img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$imgEstatusCargo = ($row['unipersonal'] == 1) ? "<img src=\"img/iconos/user_suit.png\" title=\"Cargo Unipersonal\"/>" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_usuario']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_cargo'])."</td>";
					$htmlTb .= "<td>".$imgEstatusCargo."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td class=\"divMsjInfo\">".utf8_encode($row['nombre_usuario'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_creacion']))."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['perfil_precargado'])."</td>";
			$htmlTb .= "<td>";
			if ($row['cant_block_log'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarDesbloquear('%s');\" src=\"img/iconos/lock.png\" title=\"Desbloquear\"/>",
					$row['id_usuario']);
			}
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aCopiarPrivilegio\" rel=\"#divFlotante3\" onclick=\"abrirDivFlotante3(this, 'tblPrivilegio', '%s');\"><img class=\"puntero\" src=\"img/iconos/group_link.png\" title=\"Copiar Privilegios\"/></a>", 
					
					$row['id_usuario']);
			$htmlTb .= "</td>";
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><a href=\"pg_usuario_privilegio_form.php?id_usuario=".$row['id_usuario']."\"><img src='img/iconos/edit_privilegios.png' title=\"Editar Privilegios\"></a></td>",$row['id_usuario']);
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUsuario', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_usuario']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"javascript:validarEliminar('%s');\" src=\"img/iconos/cross.png\"/></td>",
				$row['id_usuario']);
		$htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";

							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUsuario(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUsuario(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaUsuario","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

function listaEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("id_empresa_reg <> 100");
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_empresa LIKE %s
		OR nombre_empresa_suc LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "8%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, ("R.I.F."));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "36%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "36%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, ("Sucursal"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarEmpresa%s\" onclick=\"validarInsertarEmpresa('%s');\" title=\"Seleccionar\"><img src=\"img/iconos/tick.png\"/></button>",
				$contFila,
				$row['id_empresa_reg']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($nombreSucursal)."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarConfiguracion");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarUsuario");
$xajax->register(XAJAX_FUNCTION,"cargaLstConfiguracion");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstPerfil");
$xajax->register(XAJAX_FUNCTION,"desbloquearSesion");
$xajax->register(XAJAX_FUNCTION,"eliminarConfiguracionUsuario");
$xajax->register(XAJAX_FUNCTION,"eliminarEmpresaUsuario");
$xajax->register(XAJAX_FUNCTION,"exportarUsuarios");
$xajax->register(XAJAX_FUNCTION,"formConfiguracion");
$xajax->register(XAJAX_FUNCTION,"formUsuario");
$xajax->register(XAJAX_FUNCTION,"guardarUsuario");
$xajax->register(XAJAX_FUNCTION,"insertarConfiguracion");
$xajax->register(XAJAX_FUNCTION,"insertarEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaUsuario");
$xajax->register(XAJAX_FUNCTION,"cargarEmpleadoPrivilegios");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleadoPrivilegios");
$xajax->register(XAJAX_FUNCTION,"formPrivilegios");

function insertarItemConfiguracion($contFila, $idConfiguracionUsuario = "", $idConfiguracion = "", $idEmpresa = "", $valor = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// BUSCA LOS DATOS DE LA CONFIGURACION
	$queryConfig = sprintf("SELECT config.*,
		modulo.descripcionModulo
	FROM pg_configuracion config
		INNER JOIN pg_modulos modulo ON (config.id_modulo = modulo.id_modulo)
	WHERE config.id_configuracion = %s;",
		valTpDato($idConfiguracion, "int"));
	$rsConfig = mysql_query($queryConfig);
	if (!$rsConfig) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$rowConfig = mysql_fetch_assoc($rsConfig);
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT *,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE vw_iv_emp_suc.id_empresa_reg = %s;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieConfig').before('".
		"<tr id=\"trItmConfig:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmConfig:%s\"><input id=\"cbxItmConfig\" name=\"cbxItmConfig[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"center\"><input type=\"text\" id=\"txtValor%s\" name=\"txtValor%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdConfigUsuario%s\" name=\"hddIdConfigUsuario%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdConfiguracion%s\" name=\"hddIdConfiguracion%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdEmpresa%s\" name=\"hddIdEmpresa%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['nombre_empresa']),
			utf8_encode($rowConfig['descripcionModulo']),
			utf8_encode($rowConfig['nombre_configuracion']),
			$contFila, $contFila, utf8_encode($valor),
				$contFila, $contFila, $idConfiguracionUsuario,
				$contFila, $contFila, $idConfiguracion,
				$contFila, $contFila, $idEmpresa);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemEmpresa($contFila, $idUsuarioEmpresa = "", $idEmpresa = "", $checked = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT *,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE vw_iv_emp_suc.id_empresa_reg = %s;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$row = mysql_fetch_assoc($rs);
	
	$rbtPredeterminado = sprintf("<input type=\"radio\" id=\"rbtPredeterminado%s\" name=\"rbtPredeterminado\" %s value=\"%s\"/>",
		$contFila, $checked, $contFila);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td align=\"right\">%s</td>".
			"<td>%s</td>".
			"<td align=\"center\">%s".
				"<input type=\"hidden\" id=\"hddIdUsuarioEmpresa%s\" name=\"hddIdUsuarioEmpresa%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdEmpresa%s\" name=\"hddIdEmpresa%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['rif']),
			utf8_encode($row['nombre_empresa']),
			$rbtPredeterminado,
				$contFila, $contFila, $idUsuarioEmpresa,
				$contFila, $contFila, $idEmpresa);
	
	return array(true, $htmlItmPie, $contFila);
}
?>