<?php


function asignarEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_empresa_reg = %s", 
	valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$objResponse->assign("txtIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	$nombreSucursal = ($rowEmpresa['id_empresa_padre_suc'] > 0) ? " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")" : "";
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal));
	$objResponse->script("byId('btnCancelar2').click();");
	
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

function buscarActividad($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['comboxTipoActividadBus'],
		$valForm['textCriterio'],
		$valForm['lstEstatusBus']);

	$objResponse->loadCommands(listaActividad(0, "tipo,posicion_actividad", "", $valBusq));
	
	return $objResponse;
}

function cargarDatosActividad($idActividad) {
	$objResponse = new xajaxResponse();

	$sql = sprintf("SELECT * FROM crm_actividad WHERE id_actividad = %s;",
	valTpDato($idActividad, "int"));
	$rs = mysql_query($sql);
	if(!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);

	if($row['tipo'] == 'Postventa'){
		$sql2 = sprintf("SELECT * FROM crm_actividad 
			WHERE actividad_auto = %s AND 
				id_empresa = %s AND
				tipo = %s;",
		valTpDato(1, "int"),
		valTpDato($row['id_empresa'], "int"),
		valTpDato("Postventa", "text"));
		$rs2 = mysql_query($sql2);
		if (!$rs2) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$num = mysql_num_rows($rs2);
		$objResponse->script("$('#trActividadAuto').show();");
		if($num){
			if($row['actividad_auto'] == 1){
				$objResponse->script("
					document.getElementById('actividaAuto').disabled = false;
					document.getElementById('actividaAuto2').disabled = false;
				");
			} else {
				$objResponse->script("
					document.getElementById('actividaAuto').disabled = true;
					document.getElementById('actividaAuto2').disabled = true;
				");
			}
		} else {
			$objResponse->script("
				document.getElementById('actividaAuto').disabled = false;
				document.getElementById('actividaAuto2').disabled = false;
			");
		}
	}
		
	$objResponse->assign("hddIdActividad","value",$row['id_actividad']);
	$objResponse->loadCommands(asignarEmpresa($row['id_empresa']));
	$objResponse->loadCommands(comboxTipoDeActividad($row['tipo'],$row['id_empresa']));
	$objResponse->loadCommands(comboPosicion($row['posicion_actividad'],$row['id_empresa'],$row['tipo']));
	$objResponse->assign("txtNombre","value",utf8_encode($row['nombre_actividad']));
	$objResponse->assign("txtNombreAbreviado","value",utf8_decode($row['nombre_actividad_abreviatura']));
	$objResponse->call("selectedOption","lstEstatus",$row['activo']);
			
	$actividadAuto= ($row['actividad_auto'] == 1) ? "actividaAuto2" : "actividaAuto";
	$objResponse->assign($radioActiAuto,"checked",true);
	
	$rdBtnSeguimientoAct= ($row['actividad_seguimiento'] == 1) ? "rdActSeguimiento2" : "rdActSeguimiento";
	$objResponse->assign($rdBtnSeguimientoAct,"checked",true);
	
	return $objResponse;
}

function comboxTipoDeActividad($tipo, $idEmpresa = "", $idObjDestino = "tdcomboxTipoActividad"){
	$objResponse = new xajaxResponse();

	$result = buscaTipo();
	if($result[0] == true){
		if($result[1] != NULL || $result[1] != ""){
			$onchange = ($idObjDestino == "tdcomboxTipoActividad") ? sprintf("onchange=\"xajax_comboPosicion('',%s,this.value); selectedOption(this.id,'".$result[1]."');\"",$idEmpresa) : "onchange=\" byId('btnBuscar').click();\"";
		}else{
			$onchange = ($idObjDestino == "tdcomboxTipoActividad") ? sprintf("onchange=\"xajax_comboPosicion('',%s,this.value);\"",$idEmpresa) : "onchange=\" byId('btnBuscar').click();\"";
		}

		$class = ($result[1] != NULL || $result[1] != "") ? "":"class='inputHabilitado'";
	}else{
		return $objResponse-> alert($result[1]);	
	}

	$objeto = ($idObjDestino == "tdcomboxTipoActividad") ? "comboxTipoActividad":"comboxTipoActividadBus";
	
	$sql = sprintf("SHOW COLUMNS FROM crm_equipo WHERE field= %s",
		valTpDato("tipo_equipo", "text"));
	$rs = mysql_query($sql);
	if(!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	while ($row = mysql_fetch_row($rs)) {
		foreach(explode("','",substr($row[1],6,-2)) as $option) {
			$checked = ($tipo == $option) ? "selected='selected'" : "";
			$htmlOption .= sprintf('<option id="%s" %s>%s</option>', $option, $checked, $option);
		} 
	}
	
	$html .= sprintf("<select id=\"%s\" name=\"%s\" %s %s>",$objeto,$objeto,$class,$onchange);
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign($idObjDestino,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
	
		$selected = "";
		if ($selId == $row['id_empresa_reg'] || $idEmpresa == $row['id_empresa_reg'])
			$selected = "selected='selected'";
		
		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".htmlentities($row['nombre_empresa'].$nombreSucursal)."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function comboPosicion($posicion = "",$idEmpresa ="",$tipo = ""){
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = %s",
			valTpDato(1, "int"));
			
	if ($idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo = %s",
			valTpDato($tipo, "text"));
	}
	
	//CONSULTA LA ULTIMA POSICION DE LA ACTIVIDAD
	$query = sprintf("SELECT MAX(posicion_actividad) AS ultima_posicion FROM crm_actividad %s",$sqlBusq);
	$rs = mysql_query($query);
	if(!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
	$row = mysql_fetch_array($rs);

	$numInicio = ($row['ultima_posicion'] >= 10) ? ($row['ultima_posicion'] + 2) : 10;
	
	for($i = 1; $i <= $numInicio; $i++){
		if($posicion > 0){
			$checked = ($posicion == $i) ? "selected='selected'" : "";
		}	
		
		//CONSULTA CUALES SON LAS POSICIONES ASIGNADAS
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("activo = %s",
			valTpDato(1, "int"));
			
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("tipo = %s",
			valTpDato($tipo, "text"));
			
		$cond2 = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond2.sprintf("posicion_actividad = %s",
			valTpDato($i, "int"));
		
		$query2 = sprintf("SELECT * FROM crm_actividad %s",$sqlBusq2);
		$rs2 = mysql_query($query2);
		if(!$rs2) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query2);
		$row2 = mysql_fetch_array($rs2);
		$numRow = mysql_num_rows($rs2);
		
		$class = ($numRow > 0) ? "style='background-color:#FFEEEE';" : "style='background-color:#ECFCFF';" ;
		$htmlOption .= sprintf("<option value=\"%s\" %s %s >%s</option>",
			$i,$checked,$class,$i);	
		$sqlBusq2 = "";
	}
	
	$html .= sprintf("<select id=\"lstPosicion\" name=\"lstPosicion\" class=\"inputHabilitado\">");
		$html .= "<option value=''>[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdLstPosicion","innerHTML",$html);

	return $objResponse;
}

function eliminarActividad($idConfiguracionEmpresa, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"crm_actividad_list","eliminar")) {return $objResponse;}
		$sql = sprintf("SELECT * FROM crm_actividad WHERE id_actividad = %s;",
		valTpDato($idConfiguracionEmpresa, "int"));
		$mysql = mysql_query($sql);
		if(!$mysql) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rows = mysql_fetch_array($mysql);
		
		if($rows['actividad_auto'] == 1){
			return $objResponse->alert("No se Puede Eliminar Estas Actividada");
		}
		
		$deleteSQL = sprintf("UPDATE crm_actividad SET
			activo = %s,
			posicion_actividad = %s,
			actividad_seguimiento = %s
			WHERE id_actividad = %s;",
		valTpDato(0, "int"),
		valTpDato(NULL, "text"),
		valTpDato(NULL, "text"),
		valTpDato($idConfiguracionEmpresa, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

		$objResponse->alert("Eliminación Realizada con Éxito");
		
		$objResponse->script("byId('btnBuscar').click()");

	return $objResponse;
}

function guardarActividad($valForm, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	//CONSULTA LA POSICION PARA QUE NO SE REPITA
	$sql = sprintf("SELECT * FROM crm_actividad 
						WHERE tipo = %s AND id_empresa = %s AND activo = %s ",
					valTpDato($valForm['comboxTipoActividad'], "text"),
					valTpDato($valForm['txtIdEmpresa'], "int"),
					valTpDato(1, "int"));
	$rs = mysql_query($sql);
	if(!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$num = mysql_num_rows($rs);
	$arrayPosicion = array();
	$existePosicion = false;
	
	while($row = mysql_fetch_assoc($rs)){
		$arrayPosicion [] = $row['posicion_actividad'];
		if($valForm['lstEstatus'] == 1){
			if(($valForm['hddIdActividad'] == $row['id_actividad'])  && ($valForm['lstPosicion'] != $row['posicion_actividad'])){
				foreach($arrayPosicion as $indice => $valor){
					if($valForm['lstPosicion'] == $valor){
						$existePosicion = true;
					}
				}
			}
			if($valForm['lstPosicion'] == $row['posicion_actividad']){
				foreach($arrayPosicion as $indice => $valor){
					if($valForm['lstPosicion'] == $valor){
						$existePosicion = true;
					}
				}	
			}	
		}
	}
	
	if($existePosicion == true && $valForm['hddIdActividad'] == ''){
		return $objResponse->alert(sprintf("Ya Existe la posicion (%s), asignada a una actividad; seleccione otra posicion que no este asignada",
		$valForm['lstPosicion']));
	}
	
	if ($valForm['hddIdActividad'] > 0) {
		if (!xvalidaAcceso($objResponse,"crm_actividad_list","editar")) {return $objResponse;}
			$updateSQL = sprintf("UPDATE crm_actividad SET
										id_empresa = %s,
										nombre_actividad = %s,
										nombre_actividad_abreviatura = %s,
										tipo = %s,
										posicion_actividad = %s,
										activo = %s,
										actividad_auto = %s,
										actividad_seguimiento = %s,
										id_empleado_actualiza = %s,
										id_posible_cierre = %s
									WHERE id_actividad = %s;",
								valTpDato($valForm['txtIdEmpresa'], "int"),
								valTpDato($valForm['txtNombre'], "text"),
								valTpDato($valForm['txtNombreAbreviado'], "text"),
								valTpDato($valForm['comboxTipoActividad'], "text"),
								($valForm['lstEstatus'] == 1) ? valTpDato($valForm['lstPosicion'], "int"): valTpDato(NULL, "text"),
								valTpDato($valForm['lstEstatus'], "boolean"),
								valTpDato($valForm['actividaAuto'], "int"),
								($valForm['lstEstatus'] == 1) ? valTpDato($valForm['rdActSeguimiento'], "int") : valTpDato(NULL, "text"),
								valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
								valTpDato($valForm['lstActividadSeg'], "int"),
								valTpDato($valForm['hddIdActividad'], "int"));
			mysql_query("SET NAMES UTF8");
			$Result1 = mysql_query($updateSQL);
			if(!$Result1){
				if(mysql_errno() == 1406){
					return $objResponse->alert("Error la abreviatura es demasiado largo para la columna el maximo de caracteres es son de 6.");
				}else{
					return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}	
			} 
	} else {
		if (!xvalidaAcceso($objResponse,"crm_actividad_list","insertar")) {return $objResponse;}
		$insertSQL = sprintf("INSERT INTO crm_actividad (id_empresa, nombre_actividad, nombre_actividad_abreviatura, tipo, posicion_actividad, actividad_seguimiento, activo,
		id_empleado_creador, id_posible_cierre)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($valForm['txtIdEmpresa'], "int"),
				valTpDato($valForm['txtNombre'], "text"),
				valTpDato($valForm['txtNombreAbreviado'], "text"),
				valTpDato($valForm['comboxTipoActividad'], "text"),
				valTpDato($valForm['lstPosicion'], "int"),
				valTpDato($valForm['rdActSeguimiento'], "int"),
				valTpDato($valForm['lstEstatus'], "boolean"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($valForm['lstActividadSeg'], "int"));
			mysql_query("SET NAMES UTF8");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1){
				if(mysql_errno() == 1406){
					return $objResponse->alert("Error la abreviatura es demasiado largo para la columna.");
				}else{
					return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}	
			} 
	}

	$objResponse->alert("Fuente de Informacion Guardada con Éxito");
	
	$objResponse->script("byId('btnCancelar').click();
	byId('btnBuscar').click();");

	return $objResponse;
}

function listaActividad($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("actividad.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$result = buscaTipo();
	if($result[0] == true){
		if($result[1] != NULL || $result[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("tipo = %s",
				valTpDato($result[1], "text"));
		}else{
			if($valCadBusq[1] != "-1" && $valCadBusq[1] != ""){
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("tipo = %s",
				valTpDato($valCadBusq[1], "text"));
			}
		}
	}else{
		return $objResponse-> alert($result[1]);	
	}

	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("actividad.tipo LIKE %s
		OR actividad.nombre_actividad LIKE %s
		OR actividad.posicion_actividad LIKE %s",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("actividad.activo = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	$query = sprintf("SELECT actividad.*,
		posib_cierre.nombre_posibilidad_cierre,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM crm_actividad actividad
		LEFT JOIN crm_posibilidad_cierre posib_cierre ON (actividad.id_posible_cierre = posib_cierre.id_posibilidad_cierre)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (actividad.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n".$queryLimit );
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaActividad", "8%", $pageNum, "id_actividad", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaActividad", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaActividad", "22%", $pageNum, "nombre_actividad", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaActividad", "10%", $pageNum, "nombre_actividad_abreviatura", $campOrd, $tpOrd, $valBusq, $maxRows, "Abreviatura");
		$htmlTh .= ordenarCampo("xajax_listaActividad", "10%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Actividad");
		$htmlTh .= ordenarCampo("xajax_listaActividad", "8%", $pageNum, "posicion_actividad", $campOrd, $tpOrd, $valBusq, $maxRows, "Posición");
		$htmlTh .= ordenarCampo("xajax_listaActividad", "22%", $pageNum, "nombre_posibilidad_cierre", $campOrd, $tpOrd, $valBusq, $maxRows, "Actividad por Cierre");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['activo']){
			case 0: $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\" />"; break;
			case 1: $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = ""; break;
		}
			
		$imgAuto= ($row['actividad_auto'] == 1) ?"<img src=\"../img/iconos/flag_yellow.png\" title=\"Actividad Automatica\"/>" :"";
		$check = ($row['actividad_seguimiento'] == 1) ? "checked=\"checked\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_actividad'])."</td>";
			$htmlTb .= "<td>".($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".($row['nombre_actividad'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['nombre_actividad_abreviatura'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['posicion_actividad']."</td>";
			$htmlTb .= "<td>".($row['nombre_posibilidad_cierre'])."</td>";
			$htmlTb .= "<td align=\"center\">".$imgAuto."</td>";
			$htmlTb .= sprintf("<td><input type=\"checkbox\" id=\"checkActiSeguimiento%s\" name=\"checkActiSeguimiento\" disabled=\"disabled\" %s/></td>",
				$row['id_actividad'],$check);
			$htmlTb .= "<td align=\"center\" class=\"noprint\">";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"abrirFrom(this.id,%s);\"><img class=\"puntero\" src=\"../img/iconos/ico_edit.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_actividad']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['id_actividad']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActividad(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActividad(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaActividad(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActividad(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaActividad(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaConfiguracion","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listadoEmpresas($pageNum = 0, $campOrd = "nombre_empresa", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, "RIF");
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "35%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "30%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = $row['nombre_empresa_suc']." (".$row['sucursal'].")";
		
		$htmlTb .= "<tr class=\"".$clase."\" height=\"22\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$row['id_empresa_reg']."');\" title=\"Seleccionar Empresa\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td>".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif']."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".htmlentities($nombreSucursal)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_crm.gif\"/>");
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
	
	$objResponse->assign("tdListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listadoPosibleCierre($selId = '') {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT
							id_posibilidad_cierre,
							nombre_posibilidad_cierre
						FROM
							crm_posibilidad_cierre 
						WHERE id_empresa = %s",
			valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rs);
	
	if($selId > 0){
		$queryAct = sprintf("SELECT
								act.id_actividad,
								act.id_posible_cierre
							FROM crm_actividad AS act
							WHERE id_actividad = %s",
					valTpDato($selId, "int"));
		$rsAct = mysql_query($queryAct);
		if (!$rsAct) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsAct);
		$rowAct = mysql_fetch_assoc($rsAct);
	}

	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($rowAct['id_posible_cierre'] == $row['id_posibilidad_cierre']) ? "selected=\"selected\"" : "";
		
		$htmlOption .= sprintf("<option %s value=\"%s\">%s</option>",$selected,$row['id_posibilidad_cierre'],utf8_encode($row['nombre_posibilidad_cierre']));
	}
	
	$html .= sprintf("<select id=\"lstActividadSeg\" name=\"lstActividadSeg\" class=\"inputHabilitado\" style=\"width:180px\">");
	$html .= "<option value=\"\">[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdListActCierre","innerHTML",$html);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarActividad");
$xajax->register(XAJAX_FUNCTION,"cargoUsuario");
$xajax->register(XAJAX_FUNCTION,"cargarDatosActividad"); 
$xajax->register(XAJAX_FUNCTION,"comboxTipoDeActividad");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"comboPosicion");
$xajax->register(XAJAX_FUNCTION,"eliminarActividad");
$xajax->register(XAJAX_FUNCTION,"guardarActividad");
$xajax->register(XAJAX_FUNCTION,"listaActividad");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresas");
$xajax->register(XAJAX_FUNCTION,"listadoPosibleCierre");

function buscaTipo(){
//AVERIGUAR VENTA O POSTVENTA
	$queryUsuario = sprintf("SELECT id_usuario, nombre_usuario,
        CONCAT_WS(' ', nombre_empleado, apellido) AS nombre,
        clave_filtro,
		(CASE clave_filtro
			  WHEN 1 THEN 'Ventas'		
              WHEN 2 THEN 'Ventas'
			  WHEN 4 THEN 'Postventa'
              WHEN 5 THEN 'Postventa'
              WHEN 6 THEN 'Postventa'
              WHEN 7 THEN 'Postventa'
              WHEN 8 THEN 'Postventa'
              WHEN 26 THEN 'Postventa'
              WHEN 400 THEN 'Postventa'
		END) AS tipo
        
	FROM pg_usuario
		INNER JOIN pg_empleado ON pg_usuario.id_empleado = pg_empleado.id_empleado
		INNER JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
	WHERE id_usuario = %s ",
	valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryUsuario);
	$row = mysql_fetch_array($rsUsuario);

	return array(true, $row['tipo']);
}

?>