<?php

function cargarTipoPago($nomObjeto, $idConfiguracionEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_tipo_pago_list","editar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmContenidoCorreo'].reset();
			byId('hddIdContenidoCorreo').value = '';
			byId('lstTipoCorreo').className = 'inputInicial';
			byId('txtAsuntoCorreo').className = 'inputInicial';
			byId('contenidoCorreo').className = 'inputInicial';
			byId('lstEstatus').className = 'inputInicial';
			");
	
		$query = sprintf("SELECT * FROM grupositems	WHERE idItem = %s;",
			valTpDato($idConfiguracionEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		  
		$objResponse->assign("hddIdTipoPago","value",$row['idItem']);
		$objResponse->assign("txtNombreTipoPago","value",utf8_encode($row['item']));
		$objResponse->assign("lstEstatus","value",utf8_encode($row['status']));
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Tipo de Pago");
		$objResponse->script("centrarDiv(byId('divFlotante2'));");
	}
	
	return $objResponse;
}

function eliminarTipoPago($idTipoPago) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_tipo_pago_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("UPDATE grupositems SET status = 0 WHERE idItem = %s;",
			valTpDato($idTipoPago, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
	
		$objResponse->alert("Eliminación Realizada con Éxito");
		
		$objResponse->loadCommands(listadoContenidoCorreo(
			$valFormListaConfiguracion['pageNum'],
			$valFormListaConfiguracion['campOrd'],
			$valFormListaConfiguracion['tpOrd'],
			$valFormListaConfiguracion['valBusq']));
	}
	
	return $objResponse;
}

function formContenidoCorreo($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"crm_tipo_pago_list","insertar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmContenidoCorreo'].reset();
		byId('hddIdContenidoCorreo').value = '';
		
		byId('lstTipoCorreo').className = 'inputInicial';
		byId('txtAsuntoCorreo').className = 'inputInicial';
		byId('contenidoCorreo').className = 'inputInicial';
		byId('lstEstatus').className = 'inputInicial';");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Tipo de Pago");
		$objResponse->script("
		centrarDiv(byId('divFlotante2'));");
	}
	
	return $objResponse;
}

function guardarContenidoCorreo($valForm, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdContenidoCorreo'] > 0) {
		if (xvalidaAcceso($objResponse,"crm_tipo_pago_list","editar")) {
			$updateSQL = sprintf("UPDATE grupositems SET
				item = %s,
				status = %s
			WHERE idItem = %s;",
				valTpDato($valForm['txtNombreTipoPago'], "text"),
				valTpDato($valForm['lstEstatus'], "int"),
				valTpDato($valForm['hddIdTipoPago'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"crm_tipo_pago_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO crm_contenido_correo (tipo_correo, asunto, texto_correo, activo, id_empresa)
			VALUE (%s, %s, %s, %s, %s);",
				valTpDato($valForm['lstTipoCorreo'], "text"),
				valTpDato($valForm['txtAsuntoCorreo'], "text"),
				valTpDato($valForm['contenidoCorreo'], "text"),
				valTpDato($valForm['lstEstatus'], "int"));
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
	
	$objResponse->loadCommands(listadoContenidoCorreo(
		$valFormListaConfiguracion['pageNum'],
		$valFormListaConfiguracion['campOrd'],
		$valFormListaConfiguracion['tpOrd'],
		$valFormListaConfiguracion['valBusq']));
	
	return $objResponse;
}

function listadoContenidoCorreo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM crm_contenido_correo %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	//mysql_query("SET NAMES 'utf8'");
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
		$htmlTh .= ordenarCampo("xajax_listadoContenidoCorreo", "20%", $pageNum, "tipo_correo", $campOrd, $tpOrd, $valBusq, $maxRows, "tipo de Correo");
		$htmlTh .= ordenarCampo("xajax_listadoContenidoCorreo", "30%", $pageNum, "asunto", $campOrd, $tpOrd, $valBusq, $maxRows, "Asutno del Correo");
		$htmlTh .= ordenarCampo("xajax_listadoContenidoCorreo", "50%", $pageNum, "texto_correo", $campOrd, $tpOrd, $valBusq, $maxRows, "Contenido del Correo");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$idContenidoCorreo = $row['id_contenido_correo'];
		$tipoCorreo = utf8_encode($row['tipo_correo']); //htmlentities()
		$asunto = utf8_encode($row['asunto']);
		$textoCorreo = utf8_encode($row['texto_correo']);
		
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['activo']){
			case 0: $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1: $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = ""; break; 
			}
					
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"left\">".$tipoCorreo."</td>";
			$htmlTb .= "<td align=\"center\">".$asunto."</td>";
			$htmlTb .= "<td align=\"left\">".$textoCorreo."</td>";
			$htmlTb .= "<td align=\"center\" class=\"noprint\">";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_cargarTipoPago(this.id,'%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_edit.png\" title=\"Editar\"/></a>",
					$contFila,
					$idContenidoCorreo);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$idContenidoCorreo);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoContenidoCorreo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoContenidoCorreo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoContenidoCorreo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoContenidoCorreo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_crm.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoContenidoCorreo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaConfiguracion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargarTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarTipoPago");
$xajax->register(XAJAX_FUNCTION,"formContenidoCorreo");
$xajax->register(XAJAX_FUNCTION,"guardarTipoPago");
$xajax->register(XAJAX_FUNCTION,"listadoContenidoCorreo");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresas");
?>