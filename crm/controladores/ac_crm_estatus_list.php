<?php
function asignarEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_empresa_reg = %s", valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$objResponse->assign("txtIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	
	$nombreSucursal = "";
	if ($rowEmpresa['id_empresa_padre_suc'] > 0)
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";
	
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal));
	
	$objResponse->script(
	"byId('btnCancelar2').click();
	");
	
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


function buscarEstatus($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['lstEmpresa']);
	
	$objResponse->loadCommands(listadoEstatus(0, "", "", $valBusq));
	
	return $objResponse;
}

function cargarEstatus($nomObjeto, $idConfiguracionEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_estatus_list","editar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmEstatus'].reset();
		byId('hddIdEstatus').value = '';
		
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtNombre').className = 'inputInicial';
		byId('lstPosicion').className = 'inputHabilitado';
		byId('lstEstatus').className = 'inputHabilitado';");
	
		$query = sprintf("SELECT * FROM crm_estatus WHERE id_estatus = %s;",
			valTpDato($idConfiguracionEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdEstatus","value",$row['id_estatus']);
		$objResponse->loadCommands(asignarEmpresa($row['id_empresa']));
		$objResponse->loadCommands(comboPosicion($row['posicion_estatus']));
		$objResponse->assign("txtNombre","value",utf8_encode($row['nombre_estatus']));
		$objResponse->call("selectedOption","lstEstatus",$row['activo']);
		
		$objResponse->assign("tdFlotanteEstatus","innerHTML","Editar Estado");
		$objResponse->script("
		centrarDiv(byId('divFlotante2'));");
	}
	
	return $objResponse;
}

function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
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

function comboPosicion($pos){
	$objResponse = new xajaxResponse();
	
	$sqlUltimaPosicion = "SELECT IFNULL(MAX(posicion_estatus),0) as ultima_posicion 
								FROM crm_estatus";
	$rs = mysql_query($sqlUltimaPosicion);
	if(!$rs) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$idEmpresa = $_SESSION['session_empresa'];
	$numInicio = ($row['ultima_posicion'] >= 10) ? $row['ultima_posicion'] + 2 : 10;
	
	for($i = 1; $i <= $numInicio; $i++){
		
		if($pos > 0){
			$checked = ($pos == $i) ? "selected='selected'" : "";
		}
		
		//CONSULTA LA POSICION PARA VER LAS DISPONIBLES
		$sql2 = sprintf("SELECT * FROM crm_estatus
							WHERE activo = %s AND id_empresa = %s AND posicion_estatus = %s",
				valTpDato(1, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($i, "int"));
		$rs2 = mysql_query($sql2);
		if(!$rs2) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$numRows = mysql_num_rows($rs2);
		$class = ($numRows > 0) ? "style='background-color:#FFEEEE';" : "style='background-color:#ECFCFF';";
			
		$htmlOption .= sprintf("<option value='%s' %s %s>%s</option>",$i, $checked, $class, $i);
		$numRows = 0;
	}
	
	$html .= sprintf("<select id=\"lstPosicion\" name=\"lstPosicion\" class=\"inputHabilitado\">");
		$html .= "<option value=''>[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdLstPosicion","innerHTML",$html);
	
	return $objResponse;
}

function eliminarEstatus($idConfiguracionEmpresa, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_estatus_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM crm_estatus WHERE id_estatus = %s;",
			valTpDato($idConfiguracionEmpresa, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
	
		$objResponse->alert("Eliminación Realizada con Éxito");
		
		$objResponse->loadCommands(listadoEstatus(
			$valFormListaConfiguracion['pageNum'],
			$valFormListaConfiguracion['campOrd'],
			$valFormListaConfiguracion['tpOrd'],
			$valFormListaConfiguracion['valBusq']));
	}
	
	return $objResponse;
}

function formEstatus($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_estatus_list","insertar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmEstatus'].reset();
		byId('hddIdEstatus').value = '';
		
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtNombre').className = 'inputHabilitado';
		byId('lstEstatus').className = 'inputHabilitado';
		byId('lstPosicion').className = 'inputHabilitado';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresa($idEmpresa));

		$objResponse->loadCommands(comboPosicion($pos));
		
		$objResponse->assign("tdFlotanteEstatus","innerHTML","Agregar Estado");
		$objResponse->script("
		centrarDiv(byId('divFlotante2'));");
	}
	
	return $objResponse;
}

function guardarEstatus($valForm, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$query = sprintf("SELECT * FROM crm_estatus
							WHERE posicion_estatus = %s
								AND id_empresa = %s
								AND activo = %s",
				valTpDato($valForm['lstPosicion'], "int"),
				valTpDato($valForm['txtIdEmpresa'], "int"),
				valTpDato($valForm['lstEstatus'], "boolean"));
		
	$Result = mysql_query($query);
	if (!$Result) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($Result);

	if($totalRows == 0){
		if ($valForm['hddIdEstatus'] > 0) {
			if (xvalidaAcceso($objResponse,"crm_estatus_list","editar")) {
				
	
					$updateSQL = sprintf("UPDATE crm_estatus SET
												id_empresa = %s,
												nombre_estatus = %s,
												posicion_estatus = %s,
												activo = %s
											WHERE id_estatus = %s;",
						valTpDato($valForm['txtIdEmpresa'], "int"),
						valTpDato($valForm['txtNombre'], "text"),
						valTpDato($valForm['lstPosicion'], "int"),
						valTpDato($valForm['lstEstatus'], "boolean"),
						valTpDato($valForm['hddIdEstatus'], "int"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
			} else {
				return $objResponse;
			}
		} else {
			if (xvalidaAcceso($objResponse,"crm_estatus_list","insertar")) {
				$insertSQL = sprintf("INSERT INTO crm_estatus (id_empresa, nombre_estatus, posicion_estatus, activo)
				VALUE (%s, %s, %s, %s);",
					valTpDato($valForm['txtIdEmpresa'], "int"),
					valTpDato($valForm['txtNombre'], "text"),
					valTpDato($valForm['lstPosicion'], "int"),
					valTpDato($valForm['lstEstatus'], "boolean"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			} else {
				return $objResponse;
			}
		}
	} else{
		$objResponse->alert(sprintf("Ya Existe la posicion (%s), asignada a un Estatus; seleccione otra posicion que no este asignada",
				valTpDato($valForm['lstPosicion'], "int")));
		return $objResponse;
	}
	mysql_query("COMMIT;");
	
	$objResponse->alert("Fuente de Informacion Guardada con Éxito");
	
	$objResponse->script("
	byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listadoEstatus(
		$valFormListaConfiguracion['pageNum'],
		$valFormListaConfiguracion['campOrd'],
		$valFormListaConfiguracion['tpOrd'],
		$valFormListaConfiguracion['valBusq']));
	
	return $objResponse;
}

function listadoEstatus($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	$query = sprintf("SELECT * FROM crm_estatus fuente %s",$sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listadoEstatus", "90%", $pageNum, "nombre_estatus", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listadoEstatus", "10%", $pageNum, "posicion_estatus", $campOrd, $tpOrd, $valBusq, $maxRows, "Posicion");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatus = "";
		if ($row['activo'] == 0)
			$imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>";
		else if ($row['activo'] == 1)
			$imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"left\">".$row['nombre_estatus']."</td>"; //htmlentities()
			$htmlTb .= "<td align=\"center\">".$row['posicion_estatus']."</td>";
			$htmlTb .= "<td align=\"center\" class=\"noprint\">";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_cargarEstatus(this.id,'%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_edit.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_estatus']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['id_estatus']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstatus(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstatus(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEstatus(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstatus(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstatus(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
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
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$row['id_empresa_reg']."');\" title=\"Seleccionar Empresa\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td>".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif']."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".htmlentities($nombreSucursal)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
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

$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarEstatus");
$xajax->register(XAJAX_FUNCTION,"cargarEstatus");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"comboPosicion");
$xajax->register(XAJAX_FUNCTION,"eliminarEstatus");
$xajax->register(XAJAX_FUNCTION,"formEstatus");
$xajax->register(XAJAX_FUNCTION,"guardarEstatus");
$xajax->register(XAJAX_FUNCTION,"listadoEstatus");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresas");
?>