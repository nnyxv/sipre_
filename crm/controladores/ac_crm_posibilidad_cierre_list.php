<?php
function asignarEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s", 
		valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryEmpresa);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$nombreSucursal = ($rowEmpresa['id_empresa_padre_suc'] > 0) ? sprintf("%s - %s (%s)",$rowEmpresa['nombre_empresa'],$rowEmpresa['nombre_empresa_suc'],$rowEmpresa['sucursal']):$rowEmpresa['nombre_empresa'];

	$objResponse->assign("txtIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	$objResponse->assign("txtEmpresa","value",utf8_encode($nombreSucursal));
	
	$objResponse->script("byId('btnCancelarListaEmpresa').click();");
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s",
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
		
	$objResponse->loadCommands(listadoEmpresas(0, "", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarPosibilidadCierre($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$valForm['lstEmpresa'],
		$valForm['lstEstatusBus'],
		$valForm['textCriterio']
	);
	
	$objResponse->loadCommands(listadoPosibilidadCierre(0, "posicion_posibilidad_cierre", "", $valBusq));
	
	return $objResponse;
}

function cargarPosibilidadCierre($idPosibilidadCierre = ""){
	$objResponse = new xajaxResponse();

	if ($idPosibilidadCierre != "") {
		$query = sprintf("SELECT * FROM crm_posibilidad_cierre WHERE id_posibilidad_cierre = %s", 
			valTpDato($idPosibilidadCierre, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
	}
	
	//CONSULTA LA CONFIGURACION POSIBILIDAD DE CIERRE
	$query2 = sprintf("SELECT * FROM crm_posibilidad_cierre WHERE por_defecto = %s AND id_empresa = %s", 
			valTpDato(1, "int"), 
			valTpDato((($row['id_empresa'] == "") ? $_SESSION['idEmpresaUsuarioSysGts'] : $row['id_empresa']), "int"));
	$rs2 = mysql_query($query2);
	if (!$rs2) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row2 = mysql_fetch_array($rs2);
	$numRow = mysql_num_rows($rs2);
	if($numRow){
		if($row2['id_posibilidad_cierre'] == $idPosibilidadCierre){
			$objResponse->script(sprintf("document.getElementById('trPorDefecto').style.display = '';
			verMsj(%s);",$row2['por_defecto']));
		}else{
			$objResponse->script("document.getElementById('trPorDefecto').style.display= 'none';");
		}
	}else{
		$objResponse->script("document.getElementById('trPorDefecto').style.display = '';");
	}
	
	$objResponse->assign("hddIdPosibilidadCierre","value",$row['id_posibilidad_cierre']);
	$objResponse->loadCommands(asignarEmpresa((($row['id_empresa'] == "") ? $_SESSION['idEmpresaUsuarioSysGts'] : $row['id_empresa'])));
	$objResponse->assign("txtNombre","value",utf8_encode($row['nombre_posibilidad_cierre']));
	$objResponse->call("selectedOption","lstEstatus",$row['activo']);
	$objResponse->assign("hddUrlImagen","value",$row['img_posibilidad_cierre']);
	$objResponse->loadCommands(cargaLstPosicion((($row['id_empresa'] == "") ? $_SESSION['idEmpresaUsuarioSysGts'] : $row['id_empresa']),(($row['posicion_posibilidad_cierre'] == "")? "" : $row['posicion_posibilidad_cierre'])));
	$checked = ($row['por_defecto'] == 1) ? "rdoPorDefectoSi" : "rdoPorDefectoNo";
	$objResponse->assign($checked,"checked",true);
	 if($row['fin_trafico'] == 1){
		$checkedFin = true;
		$objResponse->script("verMsj(1,'checkPosibildiadCierre')");
	}else{
		$checkedFin = false; 
		$objResponse->script("verMsj(0,'checkPosibildiadCierre')");
	} 
	$objResponse->assign("checkPosibildiadCierre","checked",$checkedFin);
	

	// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
	$imgFoto = (!file_exists($row['img_posibilidad_cierre'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['img_posibilidad_cierre'];
	$objResponse->assign("imgPosibleCierre","src",$imgFoto);

	return $objResponse;
}

function cargaLstPosicion($idEmpresa = "" , $posicion = "" ){
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = %s",
			valTpDato(1, "int"));
			
	if ($idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}

	$query = sprintf("SELECT IFNULL(MAX(posicion_posibilidad_cierre),0) as ultima_posicion FROM crm_posibilidad_cierre %s",$sqlBusq);
	$rs = mysql_query($query);
	if(!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);

	$numInicio = ($row['ultima_posicion'] >= 10) ? $row['ultima_posicion'] + 2 : 10;
		for($i = 1; $i <= $numInicio; $i++){
			if($posicion > 0){
				$checked = ($posicion == $i) ? "selected='selected'" : "";
			}
			
			//CONSULTA LA POSICION PARA VER LAS DISPONIBLES
			$sql2 = sprintf("SELECT * FROM crm_posibilidad_cierre WHERE activo = %s AND id_empresa = %s AND posicion_posibilidad_cierre = %s",
				valTpDato(1, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($i, "int"));
			$rs2 = mysql_query($sql2);
			if(!$rs2) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$row2 = mysql_fetch_assoc($rs2);
			$numRows = mysql_num_rows($rs2);
			$class = ($numRows > 0) ? "style='background-color:#FFEEEE';" : "style='background-color:#ECFCFF';" ;
			$htmlOption .= sprintf("<option value=\"%s\" %s %s>%s</option>",$i,$checked,$class,$i);//
		}
		
	$html .= sprintf("<select id=\"lstPosicion\" name=\"lstPosicion\" class=\"inputHabilitado\">");
		$html .= "<option value=''>[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";

	$objResponse->assign("tdLstPosicion","innerHTML",$html);

	return $objResponse;
}

function eliminarPosibilidadCierre($idConfiguracionEmpresa, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	$Query = sprintf("SELECT * FROM crm_posibilidad_cierre WHERE id_posibilidad_cierre= %s", valTpDato($idConfiguracionEmpresa, "int"));
	$rs= mysql_query($Query); 
	if(!$rs) return $objResponse->alert(mysql_error()."\n\n Line: ". __LINE__);
	$rows = mysql_fetch_array($rs);
	
	if($rows["nombre_posibilidad_cierre"] == 'Rechazo') {
		return $objResponse->alert('No se puede eliminar '.$rows["nombre_posibilidad_cierre"]);
	}
		
	if (!xvalidaAcceso($objResponse,"crm_posibilidad_cierre_list","eliminar")) {return $objResponse;}
	
	$deleteSQL = sprintf("UPDATE crm_posibilidad_cierre SET 
		activo = %s,
		posicion_posibilidad_cierre = %s  
		WHERE 
		id_posibilidad_cierre = %s",
	valTpDato(0, "int"),
	valTpDato(NULL, "text"),
	valTpDato($idConfiguracionEmpresa, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->script("byId('btnBuscar').click();");
	
	return $objResponse;
}

function guardarPosibilidadCierre($valForm, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	//CONSUTA POSIBILIDAD DE CIERRE PARA BUSCANDO POSICION IGUAL 
	$sql = sprintf("SELECT * FROM crm_posibilidad_cierre WHERE id_empresa = %s",
		valTpDato($valForm['txtIdEmpresa'], "int"));
	$rs = mysql_query($sql);
	if(!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$num = mysql_num_rows($rs);
	$arrayPosicion [] = array();
	$existePosicion = false;
	while($row = mysql_fetch_array($rs)){
		$arrayPosicion [] = $row['posicion_posibilidad_cierre'];
		if($valForm['lstEstatus'] == 1){
			if(($valForm['hddIdPosibilidadCierre'] == $row['id_posibilidad_cierre']) && ($valForm['lstPosicion'] != $row['posicion_posibilidad_cierre'])){
				foreach($arrayPosicion as $indice => $valor){
					if($valForm['lstPosicion'] == $valor){
						$existePosicion = true;
					}
				}
			} else if($valForm['hddIdPosibilidadCierre'] == "" || $valForm['hddIdPosibilidadCierre'] == NULL){
				foreach($arrayPosicion as $indice => $valor){
					if($valForm['lstPosicion'] == $valor){
						$existePosicion = true;
					}
				}	
			}
		}
	}
	if($existePosicion == true){
		return $objResponse->alert(sprintf("Ya Existe la posicion (%s), asignada a una posibildiad de cierre; seleccione otra posicion que no este asignada",
		$valForm['lstPosicion']));
	}

	if ($valForm['hddIdPosibilidadCierre'] > 0) {
		if (!xvalidaAcceso($objResponse,"crm_posibilidad_cierre_list","editar")) { return $objResponse; }
		$updateSQL = sprintf("UPDATE crm_posibilidad_cierre SET
			id_empresa = %s,
			nombre_posibilidad_cierre = %s,
			posicion_posibilidad_cierre = %s,
			por_defecto = %s,
			fin_trafico = %s,
			img_posibilidad_cierre = %s,
			activo = %s
		WHERE id_posibilidad_cierre = %s;",
			valTpDato($valForm['txtIdEmpresa'], "int"),
			valTpDato($valForm['txtNombre'], "text"),
			($valForm['lstEstatus'] == 1) ? valTpDato($valForm['lstPosicion'], "int") : valTpDato(NULL, "text"),
			valTpDato($valForm['rdoPorDefecto'], "int"),
			((isset($valForm['checkPosibildiadCierre'])) ? valTpDato($valForm['checkPosibildiadCierre'], "int") : valTpDato(NULL, "text")),
			valTpDato($valForm['hddUrlImagen'], "text"),
			valTpDato($valForm['lstEstatus'], "boolean"),
			valTpDato($valForm['hddIdPosibilidadCierre'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"crm_posibilidad_cierre_list","insertar")) {return $objResponse;}

		$insertSQL = sprintf("INSERT INTO crm_posibilidad_cierre (id_empresa, nombre_posibilidad_cierre,posicion_posibilidad_cierre,por_defecto,fin_trafico,img_posibilidad_cierre, activo)
		VALUE (%s, %s, %s, %s, %s, %s, %s);",
			valTpDato($valForm['txtIdEmpresa'], "int"),
			valTpDato($valForm['txtNombre'], "text"),
			valTpDato($valForm['lstPosicion'], "int"),
			valTpDato($valForm['rdoPorDefecto'], "int"),
			valTpDato($valForm['checkPosibildiadCierre'], "int"),
			valTpDato($valForm['hddUrlImagen'], "text"),
			valTpDato($valForm['lstEstatus'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}

	$objResponse->alert("Fuente de Informacion Guardada con Éxito");
	
	$objResponse->script("byId('btnCancelar').click();byId('btnBuscar').click();");
	
	return $objResponse;
}

function listadoPosibilidadCierre($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("crm_posibilidad_cierre.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre_posibilidad_cierre LIKE %s
			OR posicion_posibilidad_cierre LIKE %s
			OR activo LIKE %s",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}

	$query = sprintf("SELECT 
			id_posibilidad_cierre, nombre_posibilidad_cierre,por_defecto, img_posibilidad_cierre,posicion_posibilidad_cierre, activo, 
			fin_trafico,crm_posibilidad_cierre.id_empresa, nombre_empresa
		FROM crm_posibilidad_cierre
		INNER JOIN pg_empresa ON pg_empresa.id_empresa = crm_posibilidad_cierre.id_empresa %s", 
	$sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "5%", $pageNum, "id_posibilidad_cierre", $campOrd, $tpOrd, $valBusq, $maxRows, "id");
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "65%", $pageNum, "nombre_posibilidad_cierre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "10%", $pageNum, "nombre_posibilidad_cierre", $campOrd, $tpOrd, $valBusq, $maxRows, "Posicion");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['activo']){
			case 0: $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1: $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = ""; break; 
		}
		
		switch($row['fin_trafico']){
			case 1: $imgFin = "<img src=\"../img/iconos/aprob_jefe_taller.png\" title=\"Finaliza el Trafico de Sala\"/>"; break;
			default : $imgFin = ""; break; 
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>".$row['id_posibilidad_cierre']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['nombre_posibilidad_cierre']."</td>"; //htmlentities()
			$htmlTb .= "<td align=\"center\">".$row['posicion_posibilidad_cierre']."</td>";
			$htmlTb .= sprintf("<td align=\"center\"><input id=\"checkPosicibilidadCierre%s\" name=\"checkPosicibilidadCierre%s\" type=\"checkbox\" %s disabled=\"disabled\" /></td>",
					$row['id_posibilidad_cierre'],$row['id_posibilidad_cierre'],(($row['por_defecto'] == 1) ? "checked='checked'" : ""));
			$htmlTb .= sprintf("<td align=\"center\">%s</td>",$imgFin);
			$htmlTb .= "<td align=\"center\" class=\"noprint\">";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"abrirFrom(this, 'frmPosibilidadCierre', 'tdFlotanteTitulo', %s);\"><img class=\"puntero\" src=\"../img/iconos/ico_edit.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_posibilidad_cierre']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['id_posibilidad_cierre']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPosibilidadCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPosibilidadCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoPosibilidadCierre(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPosibilidadCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPosibilidadCierre(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\">";
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

function listadoEmpresas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa_reg != %s",
		valTpDato(100, "int"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre_empresa LIKE %s
			OR nombre_empresa_suc LIKE %s
			OR sucursal LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales %s",$sqlBusq);
	
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
	
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? sprintf("%s (%s)",$row['nombre_empresa_suc'],$row['sucursal']) :"";

		$htmlTb .= "<tr class=\"".$clase."\" height=\"22\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$row['id_empresa_reg']."');\" title=\"Seleccionar Empresa\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td>".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif']."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".htmlentities($nombreSucursal)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\">";
		$htmlTf .= "<td align=\"center\" colspan=\"6\">";
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
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarPosibilidadCierre");
$xajax->register(XAJAX_FUNCTION,"cargarPosibilidadCierre");
$xajax->register(XAJAX_FUNCTION,"cargaLstPosicion");
$xajax->register(XAJAX_FUNCTION,"eliminarPosibilidadCierre");
$xajax->register(XAJAX_FUNCTION,"guardarPosibilidadCierre");
$xajax->register(XAJAX_FUNCTION,"listadoPosibilidadCierre");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresas");
?>