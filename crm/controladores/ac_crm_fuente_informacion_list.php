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

function buscarFuenteInformacion($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['lstEmpresa']);
	
	$objResponse->loadCommands(listadoFuenteInformacion(0, "", "", $valBusq));
	
	return $objResponse;
}

function cargarFuenteInformacion($nomObjeto, $idConfiguracionEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_fuente_informacion_list","editar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmFuenteInformacion'].reset();
		byId('hddIdFuenteInformacion').value = '';
		
		byId('txtNombreFunete').className = 'inputInicial';
		byId('lstEstatus').className = 'inputInicial';");
	
		$query = sprintf("SELECT * FROM grupositems
		WHERE idItem = %s;",
			valTpDato($idConfiguracionEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdFuenteInformacion","value",$row['idItem']);
		$objResponse->assign("txtNombreFunete","value",utf8_encode($row['item']));
		$objResponse->call("selectedOption","lstEstatus",$row['status']);
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Fuente de Informacion");
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

function eliminarFuenteInformacion($idConfiguracionEmpresa, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_fuente_informacion_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("UPDATE grupositems SET
					status = 0
					WHERE idItem = %s;",
				valTpDato($idConfiguracionEmpresa, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
	
		$objResponse->alert("Eliminación Realizada con Éxito");
		
		$objResponse->loadCommands(listadoFuenteInformacion(
			$valFormListaConfiguracion['pageNum'],
			$valFormListaConfiguracion['campOrd'],
			$valFormListaConfiguracion['tpOrd'],
			$valFormListaConfiguracion['valBusq']));
	}
	
	return $objResponse;
}

function formFuenteInformacion($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_fuente_informacion_list","insertar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmFuenteInformacion'].reset();
		byId('hddIdFuenteInformacion').value = '';
		
		byId('txtNombreFunete').className = 'inputHabilitado';
		byId('lstEstatus').className = 'inputInicial';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		$objResponse->loadCommands(asignarEmpresa($idEmpresa));
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Fuente de Informacion");
		$objResponse->script("
		centrarDiv(byId('divFlotante2'));");
	}
	
	return $objResponse;
}

function guardarFuenteInformacion($valForm, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdFuenteInformacion'] > 0) {
		if (xvalidaAcceso($objResponse,"crm_fuente_informacion_list","editar")) {
			$updateSQL = sprintf("UPDATE grupositems SET
				item = %s,
				status = %s
			WHERE idItem = %s;",
				valTpDato($valForm['txtNombreFunete'], "text"),
				valTpDato($valForm['lstEstatus'], "boolean"),
				valTpDato($valForm['hddIdFuenteInformacion'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"crm_fuente_informacion_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO grupositems (idGrupo, item, status)
			VALUE (3, %s, %s);",
				valTpDato($valForm['txtNombreFunete'], "text"),
				valTpDato($valForm['lstEstatus'], "boolean"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Fuente de Informacion Guardada con Éxito");
	
	$objResponse->script("
	byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listadoFuenteInformacion(
		$valFormListaConfiguracion['pageNum'],
		$valFormListaConfiguracion['campOrd'],
		$valFormListaConfiguracion['tpOrd'],
		$valFormListaConfiguracion['valBusq']));
	
	return $objResponse;
}

function listadoFuenteInformacion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	/*if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}*/
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item, status
	FROM grupositems git
		LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
	WHERE gps.grupo = 'medios'
	ORDER BY item %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "100%", $pageNum, "item", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['status']){
			case 0: $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;// inactivo
			case 1: $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;//activo
			default : $imgEstatus = "";
			}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"left\">".$row['item']."</td>"; //htmlentities()
			$htmlTb .= "<td align=\"center\" class=\"noprint\">";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_cargarFuenteInformacion(this.id,'%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_edit.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['idItem']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['idItem']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFuenteInformacion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFuenteInformacion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoFuenteInformacion(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFuenteInformacion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFuenteInformacion(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
		$htmlTb .= "<tr class=\"".$clase."\" height=\"22\">";
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

$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarFuenteInformacion");
$xajax->register(XAJAX_FUNCTION,"cargarFuenteInformacion");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarFuenteInformacion");
$xajax->register(XAJAX_FUNCTION,"formFuenteInformacion");
$xajax->register(XAJAX_FUNCTION,"guardarFuenteInformacion");
$xajax->register(XAJAX_FUNCTION,"listadoFuenteInformacion");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresas");
?>