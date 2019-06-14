<?php


function buscarAccesorio($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaAccesorio(0, "nom_accesorio", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarAccesorio($idAccesorio, $frmListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_accesorio_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM an_accesorio WHERE id_accesorio = %s",
		valTpDato($idAccesorio, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaAccesorio(
		$frmListaAccesorio['pageNum'],
		$frmListaAccesorio['campOrd'],
		$frmListaAccesorio['tpOrd'],
		$frmListaAccesorio['valBusq']));
	
	return $objResponse;
}

function formAccesorio($idAccesorio) {
	$objResponse = new xajaxResponse();
	
	if ($idAccesorio > 0) {
		if (!xvalidaAcceso($objResponse,"an_accesorio_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarAccesorio').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM an_accesorio
		WHERE id_accesorio = %s;",
			valTpDato($idAccesorio, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdAccesorio","value",$idAccesorio);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nom_accesorio']));
		$objResponse->assign("txtDescripcion","value",utf8_encode($row['des_accesorio']));
		$objResponse->call("selectedOption","lstPoseeIva",$row['iva_accesorio']);
		$objResponse->assign("txtPrecio","value",utf8_encode($row['precio_accesorio']));
		$objResponse->assign("txtCosto","value",utf8_encode($row['costo_accesorio']));
		$objResponse->script("byId('imgGeneraComision').style.display = '';");
		$objResponse->script("byId('lstGeneraComision').onchange = function(){ selectedOption(this.id,'".$row['genera_comision']."'); };");
		$objResponse->call("selectedOption","lstGeneraComision",$row['genera_comision']);
		$objResponse->script("byId('imgIncluirCostoCompraUnidad').style.display = '';");
		$objResponse->script("byId('lstIncluirCostoCompraUnidad').onchange = function(){ selectedOption(this.id,'".$row['incluir_costo_compra_unidad']."'); };");
		$objResponse->call("selectedOption","lstIncluirCostoCompraUnidad",$row['incluir_costo_compra_unidad']);
	} else {
		if (!xvalidaAcceso($objResponse,"an_accesorio_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarAccesorio').click();"); return $objResponse; }
		
		$objResponse->script("byId('imgGeneraComision').style.display = '';");
		$objResponse->script("byId('lstGeneraComision').onchange = function(){ selectedOption(this.id,'0'); };");
		$objResponse->call("selectedOption","lstGeneraComision",0);
		$objResponse->script("byId('imgIncluirCostoCompraUnidad').style.display = '';");
		$objResponse->script("byId('lstIncluirCostoCompraUnidad').onchange = function(){ selectedOption(this.id,'1'); };");
		$objResponse->call("selectedOption","lstIncluirCostoCompraUnidad",1);
	}
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmPermiso'].reset();
	
	byId('txtContrasena').className = 'inputInicial';");
	
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	$objResponse->script("
	byId('tblPermiso').style.display = '';
	byId('tblAccesorio').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Ingreso de Clave Especial");
	$objResponse->script("		
	byId('divFlotante1').style.display = '';
	centrarDiv(byId('divFlotante1'));
	
	byId('txtContrasena').focus();");
	
	return $objResponse;
}

function guardarAccesorio($frmAccesorio, $frmListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($frmAccesorio['hddIdAccesorio'] > 0) {
		if (!xvalidaAcceso($objResponse,"an_accesorio_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE an_accesorio SET
			nom_accesorio = %s,
			des_accesorio = %s,
			iva_accesorio = %s,
			precio_accesorio = %s,
			costo_accesorio = %s,
			genera_comision = %s,
			incluir_costo_compra_unidad = %s
		WHERE id_accesorio = %s;",
			valTpDato($frmAccesorio['txtNombre'], "text"),
			valTpDato($frmAccesorio['txtDescripcion'], "text"),
			valTpDato($frmAccesorio['lstPoseeIva'], "boolean"),
			valTpDato($frmAccesorio['txtPrecio'], "real_inglesa"),
			valTpDato($frmAccesorio['txtCosto'], "real_inglesa"),
			valTpDato($frmAccesorio['lstGeneraComision'], "boolean"),
			valTpDato($frmAccesorio['lstIncluirCostoCompraUnidad'], "boolean"),
			valTpDato($frmAccesorio['hddIdAccesorio'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"an_accesorio_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO an_accesorio (id_tipo_accesorio, nom_accesorio, des_accesorio, iva_accesorio, precio_accesorio, costo_accesorio, genera_comision, incluir_costo_compra_unidad)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato(1, "int"), // 1 = Adicional, 2 = Accesorio
			valTpDato($frmAccesorio['txtNombre'], "text"),
			valTpDato($frmAccesorio['txtDescripcion'], "text"),
			valTpDato($frmAccesorio['lstPoseeIva'], "boolean"),
			valTpDato($frmAccesorio['txtPrecio'], "real_inglesa"),
			valTpDato($frmAccesorio['txtCosto'], "real_inglesa"),
			valTpDato($frmAccesorio['lstGeneraComision'], "boolean"),
			valTpDato($frmAccesorio['lstIncluirCostoCompraUnidad'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Adicional guardado con éxito.");
	
	$objResponse->script("
	byId('btnCancelarAccesorio').click();");
	
	$objResponse->loadCommands(listaAccesorio(
		$frmListaAccesorio['pageNum'],
		$frmListaAccesorio['campOrd'],
		$frmListaAccesorio['tpOrd'],
		$frmListaAccesorio['valBusq']));
	
	return $objResponse;
}

function listaAccesorio($pageNum = 0, $campOrd = "nom_accesorio", $tpOrd = "ASC", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_accesorio = 1");
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("nom_accesorio LIKE %s
		OR des_accesorio LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_accesorio %s", $sqlBusq);
	
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "24%", $pageNum, "nom_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "48%", $pageNum, "des_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "8%", $pageNum, "iva_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "10%", $pageNum, "precio_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "10%", $pageNum, "costo_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nom_accesorio'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['des_accesorio'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(($row['iva_accesorio'] == 1) ? "Si" : "No")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_accesorio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['costo_accesorio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAccesorio', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar Adicional\"/></a>",
					$contFila,
					$row['id_accesorio']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar Adicional\"/></a>",
					$row['id_accesorio']);
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaAccesorio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function validarPermiso($frmPermiso) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		if ($frmPermiso['hddModulo'] == "an_accesorio_list_genera_comision") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			
			$objResponse->script("byId('lstGeneraComision').onchange = function(){};");
			$objResponse->script("byId('imgGeneraComision').style.display = 'none';");
			
		} else if ($frmPermiso['hddModulo'] == "an_accesorio_list_incluir_costo_unidad") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			
			$objResponse->script("byId('lstIncluirCostoCompraUnidad').onchange = function(){};");
			$objResponse->script("byId('imgIncluirCostoCompraUnidad').style.display = 'none';");
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarAccesorio");
$xajax->register(XAJAX_FUNCTION,"eliminarAccesorio");
$xajax->register(XAJAX_FUNCTION,"formAccesorio");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"guardarAccesorio");
$xajax->register(XAJAX_FUNCTION,"listaAccesorio");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");
?>