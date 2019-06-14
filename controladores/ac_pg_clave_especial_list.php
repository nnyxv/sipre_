<?php

/*----------------------------------FUNCIONES PARA COPIAR CLAVES ESPECIALES--------------------------*/
function formCopiarClaveUsuario($empleadoObjetivo,$empleadoCopiar,$claveUsuario,$usuarioObjetivo,$usuarioCopiar){
	$objResponse = new xajaxResponse();

	if ($empleadoCopiar == NULL){
		return $objResponse->alert("No ha seleccionado el usuario para copiar, por favor seleccione un usuario para copiar sus claves");
	}

	if ($empleadoObjetivo == NULL){
		return $objResponse->alert("No ha seleccionado el usuario objetivo, por favor seleccione un usuario objetivo");
	}
	
	if($empleadoObjetivo == $empleadoCopiar){
		return $objResponse->alert("Selecciono el mismo usuario, debe seleccionar un usuario diferente para copiar las claves");
	}
	
	if ($empleadoObjetivo > 0 && $empleadoCopiar > 0){

		$claveUsuaroMD5=md5($claveUsuario);

		$queryClavesGenerales=sprintf("INSERT INTO pg_claves_usuarios SELECT NULL, id_clave_modulo, %s,%s FROM pg_claves_usuarios WHERE id_usuario = %s AND id_clave_modulo NOT IN (SELECT id_clave_modulo FROM pg_claves_usuarios WHERE id_usuario = %s)",
			valTpDato($usuarioObjetivo, "int"),
			valTpDato($claveUsuario, "int"),
			valTpDato($usuarioCopiar, "int"),
			valTpDato($usuarioObjetivo, "int"));
		mysql_query("SET NAMES 'utf8'");
		$rsClavesGenerales = mysql_query($queryClavesGenerales);
		if (!$rsClavesGenerales) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

		$queryClavesServicios=sprintf("INSERT INTO sa_claves 
			SELECT NULL, %s, descripcion, '0000-00-00', %s, id_empresa, modulo 
			FROM sa_claves 
			WHERE id_empleado = %s 
				AND modulo NOT IN(SELECT modulo 
								FROM sa_claves 
								WHERE id_empleado = %s)",
			valTpDato($empleadoObjetivo, "int"),
			valTpDato($claveUsuaroMD5, "text"),
			valTpDato($empleadoCopiar, "int"),
			valTpDato($empleadoObjetivo, "int"));
		mysql_query("SET NAMES 'utf8'");
		$rsClavesServicios = mysql_query($queryClavesServicios);
		if (!$rsClavesServicios) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

		$objResponse->alert("Claves guardados con exito.");
		$objResponse->script("byId('btnCancelarClaveEspecialObjetivo').click()");
		
		return $objResponse;
	}
}

function cargarEmpleadoCopiarClave($idUsuario,$target){
	$objResponse = new xajaxResponse();

	$queryEmpleado = sprintf("SELECT pg_empleado.id_empleado, pg_empleado.nombre_empleado, pg_empleado.apellido,pg_usuario.nombre_usuario,pg_usuario.id_usuario FROM pg_empleado INNER JOIN pg_usuario ON pg_empleado.id_empleado = pg_usuario.id_empleado WHERE pg_usuario.id_usuario = %s;", 
		valTpDato($idUsuario, "int"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	$nombreEmpleado=$rowEmpleado['nombre_empleado'] . " " . $rowEmpleado['apellido'];

	if($target == 1){// 1 = Objetivo 2 = A Copiar
		$objResponse->assign ("hddIdEmpleadoObjetivo","value",$rowEmpleado['id_empleado']);
		$objResponse->assign ("txtNombreEmpleadoObjetivo","value",utf8_encode($nombreEmpleado));
		$objResponse->assign ("hddIdUsuarioObjetivo","value",$rowEmpleado['id_usuario']);
		$objResponse->assign ("txtNombreUsuarioObjetivo","value",$rowEmpleado['nombre_usuario']);
	}else{
		$objResponse->assign ("hddIdEmpleadoObjetivo","value",$rowEmpleado['id_empleado']);
		$objResponse->assign ("txtNombreEmpleadoObjetivo","value",utf8_encode($nombreEmpleado));
		$objResponse->assign ("hddIdUsuarioObjetivo","value",$rowEmpleado['id_usuario']);
		$objResponse->assign ("txtNombreUsuarioObjetivo","value",$rowEmpleado['nombre_usuario']);
	}
	
	return $objResponse;
}



function listaEmpleadoCopiarClaves($pageNum = 0, $campOrd = "", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
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

	$target=$valCadBusq[1];
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpleadoCopiarClaves", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleadoCopiarClaves", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("C.I. / R.I.F."));
		$htmlTh .= ordenarCampo("xajax_listaEmpleadoCopiarClaves", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleadoCopiarClaves", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleadoCopiarClave('".$row['id_empleado']."','1','".$target."'".");\" title=\"Seleccionar\"><img src=\"img/iconos/tick.png\"/></button>"."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleadoCopiarClaves(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleadoCopiarClaves(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleadoCopiarClavesCopiarClaves(%s, '%s', '%s', '%s', %s)\">", 
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleadoCopiarClaves(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleadoCopiarClaves(%s, '%s', '%s', '%s', %s);\">%s</a>", 
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
	
	$objResponse->assign("divListaEmpleadoCopiarClave", "innerHTML", $htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}


function asignarEmpleadoCopiarClave($idEmpleado, $cerrarVentana = "true",$target){
	$objResponse = new xajaxResponse();

	$queryEmpleado = sprintf("SELECT pg_empleado.id_empleado,pg_empleado.codigo_empleado, pg_empleado.nombre_empleado, pg_empleado.apellido,pg_usuario.nombre_usuario,pg_usuario.id_usuario FROM pg_empleado INNER JOIN pg_usuario ON pg_empleado.id_empleado = pg_usuario.id_empleado WHERE pg_empleado.id_empleado = %s;", 
		valTpDato($idEmpleado, "int"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	$nombreEmpleado=$rowEmpleado['nombre_empleado'] . " " . $rowEmpleado['apellido'];

	if($target == 1){// 1 = Objetivo 2 = A Copiar
		$objResponse->assign ("hddIdEmpleadoObjetivo","value",$rowEmpleado['id_empleado']);
		$objResponse->assign ("txtNombreEmpleadoObjetivo","value",utf8_encode($nombreEmpleado));
		$objResponse->assign ("hddIdUsuarioObjetivo","value",$rowEmpleado['id_usuario']);
		$objResponse->assign ("txtNombreUsuarioObjetivo","value",$rowEmpleado['nombre_usuario']);
		$objResponse->assign ("txtContrasenaObjetivo","value",$rowEmpleado['codigo_empleado']);
	}else{
		$objResponse->assign ("hddIdEmpleadoCopiar","value",$rowEmpleado['id_empleado']);
		$objResponse->assign ("txtNombreEmpleadoCopiar","value",utf8_encode($nombreEmpleado));
		$objResponse->assign ("hddIdUsuarioCopiar","value",$rowEmpleado['id_usuario']);
		$objResponse->assign ("txtNombreUsuarioCopiar","value",$rowEmpleado['nombre_usuario']);
	}

	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaEmpleadoCopiarClave').click();");
	}

	return $objResponse;
}

function buscarEmpleadoCopiarClave($frmBuscarEmpleado) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s", 
		$frmBuscarEmpleado['txtCriterioBuscarEmpleadoCopiarClaves'],
		$frmBuscarEmpleado['hddTarget']);
	$objResponse->loadCommands(listaEmpleadoCopiarClaves(0, "id_empleado", "ASC", $valBusq));
		
	return $objResponse;
}


/*---------------------------------------------------------------------------------------------------*/
function asignarEmpleado($idEmpleado, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryEmpleado = sprintf("SELECT *, 
		(SELECT contrasena_especial FROM pg_empleado
		WHERE id_empleado = vw_pg_empleado.id_empleado) AS contrasena_especial
	FROM vw_pg_empleados vw_pg_empleado
		INNER JOIN pg_usuario usuario ON (vw_pg_empleado.id_empleado = usuario.id_empleado)
	WHERE vw_pg_empleado.id_empleado = %s;", 
		valTpDato($idEmpleado, "int"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("hddIdEmpleado", "value", $rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado", "value", utf8_encode($rowEmpleado['nombre_empleado']));
	$objResponse->assign("hddIdUsuario", "value", $rowEmpleado['id_usuario']);
	$objResponse->assign("txtNombreUsuario", "value", utf8_encode($rowEmpleado['nombre_usuario']));
	$objResponse->assign("txtContrasena", "value", utf8_encode($rowEmpleado['contrasena_especial']));
	
	$objResponse->loadCommands(cargaLstModulo($rowEmpleado['id_usuario']));

	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("
		byId('btnCancelarListaEmpleado').click();");
	}
	
	return $objResponse;
}

function buscarClaveUsuario($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s", 
		$frmBuscar['lstModuloClaveBuscar'], 
		$frmBuscar['txtCriterio'], 
		$frmBuscar['lstModuloBuscar']);
	
	$objResponse->loadCommands(listaClaveUsuario(0, "descripcion", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarEmpleado($frmBuscarEmpleado) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s", 
		$frmBuscarEmpleado['txtCriterioBuscarEmpleado']);
		
	$objResponse->loadCommands(listaEmpleado(0, "id_empleado", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstModulo($idUsuario, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos
	ORDER BY descripcionModulo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstModuloClave(".$idUsuario.", this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo", "innerHTML", $html);
	
	return $objResponse;
}

function cargaLstModuloClave($idUsuario, $idModulo, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_claves_modulos
	WHERE id_modulo = %s
		AND ((id_clave_modulo NOT IN (SELECT id_clave_modulo FROM pg_claves_usuarios clave_usu
									WHERE clave_usu.id_usuario = %s)
				AND modulo NOT IN (SELECT clave_serv.modulo FROM sa_claves clave_serv
									WHERE clave_serv.id_empleado IN (SELECT usu.id_empleado FROM pg_usuario usu
															WHERE usu.id_usuario = %s)))
			OR (id_clave_modulo = %s AND %s IS NOT NULL))
	ORDER BY descripcion", 
		valTpDato($idModulo, "int"),
		valTpDato($idUsuario, "int"),
		valTpDato($idUsuario, "int"),
		valTpDato($selId, "int"), 
		valTpDato($selId, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
	$html = "<select id=\"lstModuloClave\" name=\"lstModuloClave\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_clave_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_clave_modulo']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModuloClave", "innerHTML", $html);
	
	switch ($idModulo) {
		case 1 :
			$objResponse->script("byId('trlstEmpresa').style.display = '';");
			$objResponse->loadCommands(cargaLstEmpresaFinal($_SESSION['idEmpresaUsuarioSysGts']));
			break;
		default : $objResponse->script("byId('trlstEmpresa').style.display = 'none';");
	}
	
	return $objResponse;
}

function cargaLstModuloBuscar($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos
	ORDER BY descripcionModulo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModuloBuscar\" name=\"lstModuloBuscar\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstModuloClaveBuscar(this.value); byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModuloBuscar", "innerHTML", $html);
	
	return $objResponse;
}

function cargaLstModuloClaveBuscar($idModulo = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo = %s", 
			valTpDato($idModulo, "int"));
	}
	
	$query = sprintf("SELECT * FROM pg_claves_modulos %s ORDER BY descripcion", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModuloClaveBuscar\" name=\"lstModuloClaveBuscar\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		switch($row['id_modulo']) {
			case 0 : $clase = "divMsjInfoSinBorde2"; break;
			case 1 : $clase = "divMsjInfoSinBorde"; break;
			case 2 : $clase = "divMsjAlertaSinBorde"; break;
			case 3 : $clase = "divMsjInfo4SinBorde"; break;
		}
		
		$selected = ($selId == $row['id_clave_modulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$row['id_clave_modulo']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModuloClaveBuscar", "innerHTML", $html);
	
	return $objResponse;
}

function eliminarClaveUsuario($idClaveUsuario, $idModulo, $frmListaClaveUsuario) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse, "pg_clave_especial_list", "eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	if ($idModulo == 1) {
		$deleteSQL = sprintf("DELETE FROM sa_claves WHERE id_clave = %s", 
			valTpDato($idClaveUsuario, "int"));
	} else {
		$deleteSQL = sprintf("DELETE FROM pg_claves_usuarios WHERE id_clave_usuario = %s", 
			valTpDato($idClaveUsuario, "int"));
	}
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaClaveUsuario(
		$frmListaClaveUsuario['pageNum'], 
		$frmListaClaveUsuario['campOrd'], 
		$frmListaClaveUsuario['tpOrd'], 
		$frmListaClaveUsuario['valBusq']));
	
	return $objResponse;
}

function formClaveUsuario($idClaveUsuario, $idModulo) {
	$objResponse = new xajaxResponse();
	
	if ($idClaveUsuario > 0) {
		if (!xvalidaAcceso($objResponse, "pg_clave_especial_list", "editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarClaveEspecial').click();"); return $objResponse; }
		
		if ($idModulo == 1) {
			$query = sprintf("SELECT 
				clave_serv.id_clave,
				vw_pg_empleado.id_empleado,
				usu.id_usuario,
				clave_mod.id_clave_modulo,
				clave_mod.id_modulo,
				clave_serv.clave AS contrasena,
				clave_serv.id_empresa
			FROM sa_claves clave_serv
				INNER JOIN pg_claves_modulos clave_mod ON (clave_serv.modulo = clave_mod.modulo)
				INNER JOIN vw_pg_empleados vw_pg_empleado ON (clave_serv.id_empleado = vw_pg_empleado.id_empleado)
				INNER JOIN pg_usuario usu ON (vw_pg_empleado.id_empleado = usu.id_empleado)
			WHERE clave_serv.id_clave = %s;", 
				valTpDato($idClaveUsuario, "int"));
		} else {
			$query = sprintf("SELECT * FROM vw_pg_claves_modulos
			WHERE id_clave_usuario = %s;", 
				valTpDato($idClaveUsuario, "int"));
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdClaveUsuario", "value", $idClaveUsuario);
		$objResponse->loadCommands(asignarEmpleado($row['id_empleado'], "false"));
		$objResponse->loadCommands(cargaLstModulo($row['id_usuario'], $row['id_modulo']));
		$objResponse->loadCommands(cargaLstModuloClave($row['id_usuario'], $row['id_modulo'], $row['id_clave_modulo']));
		$objResponse->assign("txtContrasena", "value", $row['contrasena']);
		
		$objResponse->script("
		byId('lstModulo').className = 'inputInicial';
		byId('lstModulo').onchange = function () {
			selectedOption(this.id,".$row['id_modulo'].");
		}");
		
		switch ($row['id_modulo']) {
			case 1 :
				$objResponse->script("byId('trlstEmpresa').style.display = '';");
				$objResponse->loadCommands(cargaLstEmpresaFinal($row['id_empresa']));
				break;
			default : $objResponse->script("byId('trlstEmpresa').style.display = 'none';");
		}
	} else {
		if (!xvalidaAcceso($objResponse, "pg_clave_especial_list", "insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarClaveEspecial').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstModulo(-1));
		$objResponse->loadCommands(cargaLstModuloClave(-1, -1));
	}
	
	return $objResponse;
}

function guardarClaveUsuario($frmClaveUsuario, $frmListaClaveUsuario) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idClaveUsuario = $frmClaveUsuario['hddIdClaveUsuario'];
	
	if ($idClaveUsuario > 0) {
		if (!xvalidaAcceso($objResponse, "pg_clave_especial_list", "editar")) { errorGuardarClaveUsuario($objResponse); return $objResponse; }
		
		if ($frmClaveUsuario['lstModulo'] == 1) {
			$updateSQL = sprintf("UPDATE sa_claves clave_serv, pg_claves_modulos clave_mod SET
				clave_serv.descripcion = clave_mod.descripcion,
				clave_serv.id_empresa = %s, 
				clave_serv.modulo = clave_mod.modulo
			WHERE clave_mod.id_clave_modulo = %s
				AND clave_serv.id_clave = %s;",
				valTpDato($frmClaveUsuario['lstEmpresa'], "int"),
				valTpDato($frmClaveUsuario['lstModuloClave'], "int"), 
				valTpDato($frmClaveUsuario['hddIdClaveUsuario'], "int"));
		} else {
			$updateSQL = sprintf("UPDATE pg_claves_usuarios SET
				id_clave_modulo = %s
			WHERE id_clave_usuario = %s;", 
				valTpDato($frmClaveUsuario['lstModuloClave'], "int"), 
				valTpDato($frmClaveUsuario['hddIdClaveUsuario'], "int"));
		}
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarClaveUsuario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse, "pg_clave_especial_list", "insertar")) { errorGuardarClaveUsuario($objResponse); return $objResponse; }
		
		if ($frmClaveUsuario['lstModulo'] == 1) {
			$query = sprintf("SELECT usu_emp.id_empresa FROM pg_usuario_empresa usu_emp
			WHERE usu_emp.id_usuario = %s;", 
				valTpDato($frmClaveUsuario['hddIdUsuario'], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while($row = mysql_fetch_assoc($rs)) {
				$insertSQL = sprintf("INSERT INTO sa_claves (id_empleado, descripcion, clave, id_empresa, modulo)
				SELECT
					%s,
					clave_mod.descripcion,
					MD5(%s),
					%s,
					clave_mod.modulo
				FROM pg_claves_modulos clave_mod
				WHERE clave_mod.id_clave_modulo = %s;",
					valTpDato($frmClaveUsuario['hddIdEmpleado'], "int"), 
					valTpDato($frmClaveUsuario['txtContrasena'], "text"),
					valTpDato($row['id_empresa'], "int"),
					valTpDato($frmClaveUsuario['lstModuloClave'], "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarClaveUsuario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		} else {
			$insertSQL = sprintf("INSERT INTO pg_claves_usuarios (id_clave_modulo, id_usuario, contrasena)
			VALUE (%s, %s, %s);", 
				valTpDato($frmClaveUsuario['lstModuloClave'], "int"), 
				valTpDato($frmClaveUsuario['hddIdUsuario'], "int"), 
				valTpDato($frmClaveUsuario['txtContrasena'], "text"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarClaveUsuario($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarClaveUsuario($objResponse);
	$objResponse->alert(utf8_encode("Clave Especial Guardada con Éxito"));
	
	$objResponse->script("byId('btnCancelarClaveEspecial').click();");
	
	$objResponse->loadCommands(listaClaveUsuario(
		$frmListaClaveUsuario['pageNum'], 
		$frmListaClaveUsuario['campOrd'], 
		$frmListaClaveUsuario['tpOrd'], 
		$frmListaClaveUsuario['valBusq']));
	
	return $objResponse;
}

function listaClaveUsuario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_clave_modulo.id_clave_modulo = %s", 
			valTpDato($valCadBusq[0], "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("clave_mod.id_clave_modulo = %s", 
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_clave_modulo.nombre_usuario LIKE %s)", 
			valTpDato("%".$valCadBusq[1]."%", "text"), 
			valTpDato("%".$valCadBusq[1]."%", "text"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(vw_pg_empleado.nombre_empleado LIKE %s
		OR usu.nombre_usuario LIKE %s)", 
			valTpDato("%".$valCadBusq[1]."%", "text"), 
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_clave_modulo.id_modulo = %s", 
			valTpDato($valCadBusq[2], "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("clave_mod.id_modulo = %s", 
			valTpDato($valCadBusq[2], "int"));
	}	
	
	$query = sprintf("SELECT
		vw_pg_clave_modulo.id_clave_usuario, 
		(SELECT descripcionModulo FROM pg_modulos
		WHERE id_modulo = vw_pg_clave_modulo.id_modulo) AS descripcion_modulo, 
		vw_pg_empleado.nombre_empleado, 
		vw_pg_empleado.nombre_cargo, 
		vw_pg_clave_modulo.nombre_usuario, 
		vw_pg_clave_modulo.descripcion, 
		vw_pg_empleado.activo, 
		vw_pg_clave_modulo.id_modulo,
		NULL AS nombre_empresa
	FROM vw_pg_empleados vw_pg_empleado
		INNER JOIN vw_pg_claves_modulos vw_pg_clave_modulo ON (vw_pg_empleado.id_empleado = vw_pg_clave_modulo.id_empleado) %s
	
	UNION
	
	SELECT 
		clave_serv.id_clave,
		(SELECT descripcionModulo FROM pg_modulos
		WHERE id_modulo = clave_mod.id_modulo) AS descripcion_modulo, 
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo,
		usu.nombre_usuario,
		clave_mod.descripcion,
		vw_pg_empleado.activo,
		clave_mod.id_modulo,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM sa_claves clave_serv
		INNER JOIN pg_claves_modulos clave_mod ON (clave_serv.modulo = clave_mod.modulo)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (clave_serv.id_empleado = vw_pg_empleado.id_empleado)
		INNER JOIN pg_usuario usu ON (vw_pg_empleado.id_empleado = usu.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (clave_serv.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq, $sqlBusq2);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaClaveUsuario", "", $pageNum, "activo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode(""));		
		$htmlTh .= ordenarCampo("xajax_listaClaveUsuario", "18%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado"));
		$htmlTh .= ordenarCampo("xajax_listaClaveUsuario", "20%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
		$htmlTh .= ordenarCampo("xajax_listaClaveUsuario", "12%", $pageNum, "nombre_usuario", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Usuario"));
		$htmlTh .= ordenarCampo("xajax_listaClaveUsuario", "15%", $pageNum, "descripcion_modulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Módulo"));
		$htmlTh .= ordenarCampo("xajax_listaClaveUsuario", "35%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Acción"));
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['activo']) {
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
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
			$htmlTb .= "<td class=\"divMsjInfo\">".utf8_encode($row['nombre_usuario'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($row['id_modulo'].".- ".$row['descripcion_modulo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['nombre_empresa']) > 0) ? "<tr>"."<td></td><td>".utf8_encode($row['nombre_empresa'])."</td>"."</tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblClaveUsuario', '%s', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>", 
					$contFila, 
					$row['id_clave_usuario'], 
					$row['id_modulo']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s', '%s')\" src=\"img/iconos/cross.png\"/></td>", 
				$row['id_clave_usuario'], 
				$row['id_modulo']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClaveUsuario(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClaveUsuario(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaClaveUsuario(%s, '%s', '%s', '%s', %s)\">", 
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClaveUsuario(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClaveUsuario(%s, '%s', '%s', '%s', %s);\">%s</a>", 
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaClaveUsuario", "innerHTML", $htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
		
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
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleado('".$row['id_empleado']."');\" title=\"Seleccionar\"><img src=\"img/iconos/tick.png\"/></button>"."</td>";
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

$xajax->register(XAJAX_FUNCTION, "asignarEmpleado");
$xajax->register(XAJAX_FUNCTION, "buscarClaveUsuario");
$xajax->register(XAJAX_FUNCTION, "buscarEmpleado");
$xajax->register(XAJAX_FUNCTION, "cargaLstModulo");
$xajax->register(XAJAX_FUNCTION, "cargaLstModuloClave");
$xajax->register(XAJAX_FUNCTION, "cargaLstModuloBuscar");
$xajax->register(XAJAX_FUNCTION, "cargaLstModuloClaveBuscar");
$xajax->register(XAJAX_FUNCTION, "eliminarClaveUsuario");
$xajax->register(XAJAX_FUNCTION, "formClaveUsuario");
$xajax->register(XAJAX_FUNCTION, "guardarClaveUsuario");
$xajax->register(XAJAX_FUNCTION, "listaClaveUsuario");
$xajax->register(XAJAX_FUNCTION, "listaEmpleado");

$xajax->register(XAJAX_FUNCTION, "cargarEmpleadoCopiarClave");
$xajax->register(XAJAX_FUNCTION, "asignarEmpleadoCopiarClave");
$xajax->register(XAJAX_FUNCTION, "listaEmpleadoCopiarClaves");
$xajax->register(XAJAX_FUNCTION, "buscarEmpleadoCopiarClave");
$xajax->register(XAJAX_FUNCTION, "formCopiarClaveUsuario");

function errorGuardarClaveUsuario($objResponse) {
	$objResponse->script("
	byId('btnGuardarClaveEspecial').disabled = false;
	byId('btnCancelarClaveEspecial').disabled = false;");
}
?>