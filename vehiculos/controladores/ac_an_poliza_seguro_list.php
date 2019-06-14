<?php


function buscarPoliza($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$valForm['lstActivoBuscar'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaPoliza(0, "", "", $valBusq));
	
	return $objResponse;
}

function cargarPoliza($nomObjeto, $idConfiguracionEmpresa) {
	$objResponse = new xajaxResponse();

	if (xvalidaAcceso($objResponse,"an_poliza_seguro_list","editar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmPoliza'].reset();
		byId('hddIdPoliza').value = '';
		byId('txtNombre').className = 'inputHabilitado';
		byId('txtDireccion').className = 'inputHabilitado';
		byId('txtCiudad').className = 'inputHabilitado';
		byId('txtPais').className = 'inputHabilitado';
		byId('txtCompSeguros').className = 'inputHabilitado';
		byId('txtTelfAgencia').className = 'inputHabilitado';	 
		byId('lstEstatus').className = 'inputHabilitado';");
	/*	byId('txtPolizaContado').className = 'inputHabilitado';
		byId('txtInicial').className = 'inputHabilitado';
		byId('txtMeses').className = 'inputHabilitado';
		byId('txtMontoCuotas').className = 'inputHabilitado';
		byId('txtCheque').className = 'inputHabilitado';
		byId('txtFinanciada').className = 'inputHabilitado'; */
		$query = sprintf("SELECT * FROM an_poliza
		WHERE id_poliza = %s;",
			valTpDato($idConfiguracionEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdPoliza","value",$row['id_poliza']);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nombre_poliza']));
		$objResponse->assign("txtDireccion","value",utf8_encode($row['dir_agencia']));
		$objResponse->assign("txtCompSeguros","value",utf8_encode($row['nom_comp_seguro']));
		$objResponse->assign("txtTelfAgencia","value",utf8_encode($row['telf_agencia']));
	/*	$objResponse->assign("txtPolizaContado","value",number_format($row['contado_poliza'], 2, ".", ","));
		$objResponse->assign("txtInicial","value",number_format($row['inicial_poliza'], 2, ".", ","));
		$objResponse->assign("txtMeses","value",number_format($row['meses_poliza'], 2, ".", ","));
		$objResponse->assign("txtMontoCuotas","value",number_format($row['cuotas_poliza'], 2, ".", ","));
		$objResponse->assign("txtCheque","value",utf8_encode($row['cheque_poliza']));
		$objResponse->assign("txtFinanciada","value",utf8_encode($row['financiada'])); */
		$objResponse->call("selectedOption","lstEstatus",$row['estatus']);
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Póliza");
		$objResponse->script("
		centrarDiv(byId('divFlotante2'));");
	}
	
	return $objResponse;
}

function eliminarPoliza($idConfiguracionEmpresa, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_poliza_seguro_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM an_poliza WHERE id_poliza = %s;",
			valTpDato($idConfiguracionEmpresa, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
	
		$objResponse->alert("Eliminación Realizada con éxito.");
		
		$objResponse->loadCommands(listaPoliza(
			$valFormListaConfiguracion['pageNum'],
			$valFormListaConfiguracion['campOrd'],
			$valFormListaConfiguracion['tpOrd'],
			$valFormListaConfiguracion['valBusq']));
	}
	
	return $objResponse;
}

function formPoliza($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_poliza_seguro_list","insertar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmPoliza'].reset();
		byId('hddIdPoliza').value = '';
		
		byId('txtNombre').className = 'inputHabilitado';
		byId('txtDireccion').className = 'inputHabilitado';
		byId('txtCiudad').className = 'inputHabilitado';
		byId('txtPais').className = 'inputHabilitado';
		byId('txtCompSeguros').className = 'inputHabilitado';
		byId('txtTelfAgencia').className = 'inputHabilitado';
		byId('lstEstatus').className = 'inputHabilitado';");
		
		/*byId('txtPolizaContado').className = 'inputHabilitado';
		byId('txtInicial').className = 'inputHabilitado';
		byId('txtMeses').className = 'inputHabilitado';
		byId('txtMontoCuotas').className = 'inputHabilitado';
		byId('txtCheque').className = 'inputHabilitado';
		byId('txtFinanciada').className = 'inputHabilitado';*/
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Póliza");
		$objResponse->script("centrarDiv(byId('divFlotante2'));");
	}
	
	return $objResponse;
}

function guardarPoliza($valForm, $valFormListaConfiguracion) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdPoliza'] > 0) {
		if (xvalidaAcceso($objResponse,"an_poliza_seguro_list","editar")) {
			$updateSQL = sprintf("UPDATE an_poliza SET
				nombre_poliza = %s,
				dir_agencia = %s,
				ciudad_agencia = %s,
				pais_agencia = %s,
				nom_comp_seguro = %s,
				telf_agencia = %s,
				contado_poliza = %s,
				inicial_poliza = %s,
				meses_poliza = %s,
				cuotas_poliza = %s,
				cheque_poliza = %s,
				financiada = %s,
				estatus = %s
			WHERE id_poliza = %s;",
				valTpDato($valForm['txtNombre'], "text"),
				valTpDato($valForm['txtDireccion'], "text"),
				valTpDato($valForm['txtCiudad'], "text"),
				valTpDato($valForm['txtPais'], "text"),
				valTpDato($valForm['txtCompSeguros'], "text"),
				valTpDato($valForm['txtTelfAgencia'], "text"),
				0,
				0,
				0,
				0,
				0,
				0,
				valTpDato($valForm['lstEstatus'], "boolean"),				
				valTpDato($valForm['hddIdPoliza'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"an_poliza_seguro_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO an_poliza (nombre_poliza,	dir_agencia, ciudad_agencia, pais_agencia, nom_comp_seguro, telf_agencia, contado_poliza, inicial_poliza, meses_poliza, cuotas_poliza, cheque_poliza, financiada, estatus)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($valForm['txtNombre'], "text"),						
				valTpDato($valForm['txtDireccion'], "text"),
				valTpDato($valForm['txtCiudad'], "text"),
				valTpDato($valForm['txtPais'], "text"),
				valTpDato($valForm['txtCompSeguros'], "text"),
				valTpDato($valForm['txtTelfAgencia'], "text"),
				0,
				0,
				0,
				0,
				0,
				0,
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
	
	$objResponse->alert("Póliza guardada con éxito.");
	
	$objResponse->script("
	byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listaPoliza(
		$valFormListaConfiguracion['pageNum'],
		$valFormListaConfiguracion['campOrd'],
		$valFormListaConfiguracion['tpOrd'],
		$valFormListaConfiguracion['valBusq']));
	
	return $objResponse;
}

function listaPoliza($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus = %s",
			valTpDato($valCadBusq[0], "boolean"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre_poliza LIKE %s",
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_poliza %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPoliza", "30%", $pageNum, "nombre_poliza", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaPoliza", "20%", $pageNum, "dir_agencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Direccion");
		$htmlTh .= ordenarCampo("xajax_listaPoliza", "10%", $pageNum, "ciudad_agencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Ciudad");
		$htmlTh .= ordenarCampo("xajax_listaPoliza", "10%", $pageNum, "pais_agencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Pais");
		$htmlTh .= ordenarCampo("xajax_listaPoliza", "20%", $pageNum, "nom_comp_seguro", $campOrd, $tpOrd, $valBusq, $maxRows, "Compañia de Seguros");
		$htmlTh .= ordenarCampo("xajax_listaPoliza", "10%", $pageNum, "telf_agencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Tlf. Agencia");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatus = "";
		if ($row['estatus'] == 0){
			$imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>";
		}else if ($row['estatus'] == 1){
			$imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($row['nombre_poliza'])."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($row['dir_agencia'])."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($row['ciudad_agencia'])."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($row['pais_agencia'])."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($row['nom_comp_seguro'])."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($row['telf_agencia'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"noprint\">";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_cargarPoliza(this.id,'%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_poliza']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['id_poliza']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPoliza(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPoliza(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPoliza(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPoliza(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPoliza(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPoliza","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarPoliza");
$xajax->register(XAJAX_FUNCTION,"cargarPoliza");
$xajax->register(XAJAX_FUNCTION,"eliminarPoliza");
$xajax->register(XAJAX_FUNCTION,"formPoliza");
$xajax->register(XAJAX_FUNCTION,"guardarPoliza");
$xajax->register(XAJAX_FUNCTION,"listaPoliza");
?>