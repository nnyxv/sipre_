<?php

function buscarBanco($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaBancos(0, "nombreBanco", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarBanco($idBanco){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_bancos","eliminar")){ return $objResponse; }
	
	$query = sprintf("DELETE FROM bancos WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$objResponse->script("byId('btnBuscar').click();");
	$objResponse->alert("Banco eliminado correctamente");
	
	return $objResponse;
}

function formBanco($idBanco, $accion){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	$objResponse->assign("txtSucursalBanco","value",utf8_encode($row['sucursal']));
	$objResponse->assign("txtDireccionBanco","value",utf8_encode($row['direccion']));
	$objResponse->assign("txtCodigo1","value",$row['codigo1']);
	$objResponse->assign("txtCodigo2","value",$row['codigo2']);
	$objResponse->assign("txtRIF","value",$row['rif']);
	$objResponse->assign("txtTelefonoBanco","value",$row['telf']);
	$objResponse->assign("txtFaxBanco","value",$row['fax']);
	$objResponse->assign("txtEmailBanco","value",$row['email']);
	$objResponse->assign("txtPorcentajeFlatBanco","value",$row['porcentaje_flat']);
	$objResponse->assign("txtDSBCLocalesBanco","value",$row['diasSalvoBuenCobroLocales']);
	$objResponse->assign("txtDSBCForaneosBanco","value",$row['diasSalvoBuenCobroForaneos']);
	
	$objResponse->assign("selCooperativa","value",$row['cooperativa']);	
	
	$objResponse->assign("txtUrbanizacion","value",$row['urbanizacion']);
	$objResponse->assign("txtCalle","value",$row['calle']);
	$objResponse->assign("txtCasa","value",$row['casa']);
	$objResponse->assign("txtMunicipio","value",$row['municipio']);
	$objResponse->assign("txtCiudad","value",$row['ciudad']);
	$objResponse->assign("txtEstado","value",$row['estado']);
	$objResponse->assign("txtUrbanizacionPostal","value",$row['urbanizacion_postal']);
	$objResponse->assign("txtCallePostal","value",$row['calle_postal']);
	$objResponse->assign("txtCasaPostal","value",$row['casa_postal']);
	$objResponse->assign("txtMunicipioPostal","value",$row['municipio_postal']);
	$objResponse->assign("txtCiudadPostal","value",$row['ciudad_postal']);
	$objResponse->assign("txtEstadoPostal","value",$row['estado_postal']);
	
	$objResponse->assign("txtXproveedor","value",$row['x_proveedor']);
	$objResponse->assign("txtYproveedor","value",$row['y_proveedor']);
	$objResponse->assign("txtXcantidad","value",$row['x_cantidad']);
	$objResponse->assign("txtYcantidad","value",$row['y_cantidad']);
	$objResponse->assign("txtXfecha","value",$row['x_fecha']);
	$objResponse->assign("txtYfecha","value",$row['y_fecha']);
	$objResponse->assign("txtXcantidadLetras","value",$row['x_cantidad_letras']);
	$objResponse->assign("txtYcantidadLetras","value",$row['y_cantidad_letras']);
	
	if ($accion == 1) {
		$objResponse->script("byId('btnGuardar').style.display = 'none'");
	} else {
		$objResponse->script("byId('btnGuardar').style.display = ''");
	}
	
	return $objResponse;
}

function guardarBanco($frmBanco){
	$objResponse = new xajaxResponse();

	global $spanEstado;

	$frmBanco['txtUrbanizacion'] = trim(str_replace(",", "", $frmBanco['txtUrbanizacion']));
	$frmBanco['txtCalle'] = trim(str_replace(",", "", $frmBanco['txtCalle']));
	$frmBanco['txtCasa'] = trim(str_replace(",", "", $frmBanco['txtCasa']));
	$frmBanco['txtMunicipio'] = trim(str_replace(",", "", $frmBanco['txtMunicipio']));
	$frmBanco['txtCiudad'] = trim(str_replace(",", "", $frmBanco['txtCiudad']));
	$frmBanco['txtEstado'] = trim(str_replace(",", "", $frmBanco['txtEstado']));
	
	$txtDireccion = implode("; ", array(
		$frmBanco['txtUrbanizacion'],
		$frmBanco['txtCalle'],
		$frmBanco['txtCasa'],
		$frmBanco['txtMunicipio'],
		$frmBanco['txtCiudad'],
		((strlen($frmBanco['txtEstado']) > 0) ? $spanEstado : "")." ".$frmBanco['txtEstado']));
		
	$frmBanco['txtUrbanizacionPostal'] = trim(str_replace(",", "", $frmBanco['txtUrbanizacionPostal']));
	$frmBanco['txtCallePostal'] = trim(str_replace(",", "", $frmBanco['txtCallePostal']));
	$frmBanco['txtCasaPostal'] = trim(str_replace(",", "", $frmBanco['txtCasaPostal']));
	$frmBanco['txtMunicipioPostal'] = trim(str_replace(",", "", $frmBanco['txtMunicipioPostal']));
	$frmBanco['txtCiudadPostal'] = trim(str_replace(",", "", $frmBanco['txtCiudadPostal']));
	$frmBanco['txtEstadoPostal'] = trim(str_replace(",", "", $frmBanco['txtEstadoPostal']));
	
	$txtDireccionPostal = implode("; ", array(
		$frmBanco['txtUrbanizacionPostal'],
		$frmBanco['txtCallePostal'],
		$frmBanco['txtCasaPostal'],
		$frmBanco['txtMunicipioPostal'],
		$frmBanco['txtCiudadPostal'],
		((strlen($frmBanco['txtEstadoPostal']) > 0) ? $spanEstado : "")." ".$frmBanco['txtEstadoPostal']));

	if ($frmBanco['hddIdBanco'] == 0) {
		if (!xvalidaAcceso($objResponse,"te_bancos","insertar")){ return $objResponse; }
		
		$query = sprintf("INSERT INTO bancos (nombreBanco, sucursal, direccion, telf, fax, email, porcentaje_flat, diasSalvoBuenCobroLocales, diasSalvoBuenCobroForaneos, rif, codigo1, codigo2, cooperativa, urbanizacion, calle, casa, municipio, ciudad, estado, direccion_completa, urbanizacion_postal, calle_postal, casa_postal, municipio_postal, ciudad_postal, estado_postal, direccion_postal, x_proveedor, y_proveedor, x_cantidad, y_cantidad, x_fecha, y_fecha, x_cantidad_letras, y_cantidad_letras)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($frmBanco['txtNombreBanco'], "text"),
			valTpDato($frmBanco['txtSucursalBanco'], "text"),
			valTpDato($frmBanco['txtDireccionBanco'], "text"),
			valTpDato($frmBanco['txtTelefonoBanco'], "text"),
			valTpDato($frmBanco['txtFaxBanco'], "text"),
			valTpDato($frmBanco['txtEmailBanco'], "text"),
			valTpDato($frmBanco['txtPorcentajeFlatBanco'], "real_inglesa"),
			valTpDato($frmBanco['txtDSBCLocalesBanco'], "int"),
			valTpDato($frmBanco['txtDSBCForaneosBanco'], "int"),
			valTpDato($frmBanco['txtRIF'], "text"),
			valTpDato($frmBanco['txtCodigo1'], "text"),
			valTpDato($frmBanco['txtCodigo2'], "text"),
			valTpDato($frmBanco['selCooperativa'], "int"),
			valTpDato($frmBanco['txtUrbanizacion'], "text"),
			valTpDato($frmBanco['txtCalle'], "text"),
			valTpDato($frmBanco['txtCasa'], "text"),
			valTpDato($frmBanco['txtMunicipio'], "text"),
			valTpDato($frmBanco['txtCiudad'], "text"),
			valTpDato($frmBanco['txtEstado'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmBanco['txtUrbanizacionPostal'], "text"),
			valTpDato($frmBanco['txtCallePostal'], "text"),
			valTpDato($frmBanco['txtCasaPostal'], "text"),
			valTpDato($frmBanco['txtMunicipioPostal'], "text"),
			valTpDato($frmBanco['txtCiudadPostal'], "text"),
			valTpDato($frmBanco['txtEstadoPostal'], "text"),			
			valTpDato($txtDireccionPostal, "text"),
			valTpDato($frmBanco['txtXproveedor'], "int"),
			valTpDato($frmBanco['txtYproveedor'], "int"),
			valTpDato($frmBanco['txtXcantidad'], "int"),
			valTpDato($frmBanco['txtYcantidad'], "int"),
			valTpDato($frmBanco['txtXfecha'], "int"),
			valTpDato($frmBanco['txtYfecha'], "int"),
			valTpDato($frmBanco['txtXcantidadLetras'], "int"),
			valTpDato($frmBanco['txtYcantidadLetras'], "int"));
	} else {
		if (!xvalidaAcceso($objResponse,"te_bancos","editar")){ return $objResponse; }
		
		$query = sprintf("UPDATE bancos SET 
			nombreBanco = %s,
			sucursal = %s,
			direccion = %s,
			telf = %s,
			fax = %s,
			email = %s,
			porcentaje_flat = %s,
			diasSalvoBuenCobroLocales = %s,
			diasSalvoBuenCobroForaneos = %s,
			rif = %s,                        
			codigo1 = %s,
			codigo2 = %s,
			cooperativa = %s,
			urbanizacion = %s, 
			calle = %s, 
			casa = %s, 
			municipio = %s, 
			ciudad = %s, 
			estado = %s, 
			direccion_completa = %s, 
			urbanizacion_postal = %s, 
			calle_postal = %s, 
			casa_postal = %s, 
			municipio_postal = %s, 
			ciudad_postal = %s,
			estado_postal = %s, 
			direccion_postal = %s,
			x_proveedor = %s, 
			y_proveedor = %s, 
			x_cantidad = %s, 
			y_cantidad = %s, 
			x_fecha = %s, 
			y_fecha = %s, 
			x_cantidad_letras = %s, 
			y_cantidad_letras = %s
		WHERE idBanco = %s;",
			valTpDato($frmBanco['txtNombreBanco'], "text"),
			valTpDato($frmBanco['txtSucursalBanco'], "text"),
			valTpDato($frmBanco['txtDireccionBanco'], "text"),
			valTpDato($frmBanco['txtTelefonoBanco'], "text"),
			valTpDato($frmBanco['txtFaxBanco'], "text"),
			valTpDato($frmBanco['txtEmailBanco'], "text"),
			valTpDato($frmBanco['txtPorcentajeFlatBanco'], "double"),
			valTpDato($frmBanco['txtDSBCLocalesBanco'], "int"),
			valTpDato($frmBanco['txtDSBCForaneosBanco'], "int"),
			valTpDato($frmBanco['txtRIF'], "text"),
			valTpDato($frmBanco['txtCodigo1'], "text"),
			valTpDato($frmBanco['txtCodigo2'], "text"),
			valTpDato($frmBanco['selCooperativa'], "int"),
			valTpDato($frmBanco['txtUrbanizacion'], "text"),
			valTpDato($frmBanco['txtCalle'], "text"),
			valTpDato($frmBanco['txtCasa'], "text"),
			valTpDato($frmBanco['txtMunicipio'], "text"),
			valTpDato($frmBanco['txtCiudad'], "text"),
			valTpDato($frmBanco['txtEstado'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmBanco['txtUrbanizacionPostal'], "text"),
			valTpDato($frmBanco['txtCallePostal'], "text"),
			valTpDato($frmBanco['txtCasaPostal'], "text"),
			valTpDato($frmBanco['txtMunicipioPostal'], "text"),
			valTpDato($frmBanco['txtCiudadPostal'], "text"),
			valTpDato($frmBanco['txtEstadoPostal'], "text"),
			valTpDato($txtDireccionPostal, "text"),
			valTpDato($frmBanco['txtXproveedor'], "int"),
			valTpDato($frmBanco['txtYproveedor'], "int"),
			valTpDato($frmBanco['txtXcantidad'], "int"),
			valTpDato($frmBanco['txtYcantidad'], "int"),
			valTpDato($frmBanco['txtXfecha'], "int"),
			valTpDato($frmBanco['txtYfecha'], "int"),
			valTpDato($frmBanco['txtXcantidadLetras'], "int"),
			valTpDato($frmBanco['txtYcantidadLetras'], "int"),
			valTpDato($frmBanco['hddIdBanco'], "int"));
	}
	
	mysql_query("SET NAMES 'utf8'");
	$rs = mysql_query($query);
	mysql_query("SET NAMES 'latin1';");
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
	
	$objResponse->script("byId('btnBuscar').click();
		byId('btnCancelar').click();");
	
	$objResponse->alert("Banco guardado correctamente");
	
	return $objResponse;
}

function listaBancos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond."idBanco != 1";
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("nombreBanco LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM bancos %s", $sqlBusq);
					 
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaBancos", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaBancos", "40%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");
		$htmlTh .= ordenarCampo("xajax_listaBancos", "20%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "Telefono");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['sucursal'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['telf'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante1(this, '', 'ver', %s);\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver\" /></a>",
					$row['idBanco']);
			$htmlTb .= "</td>";			
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante1(this, '', 'editar', %s);\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\" /></a>",
					$row['idBanco']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td><img src='../img/iconos/cross.png' class=\"puntero\" title=\"Eliminar\" onclick=\"if (confirm('Desea eliminar ".utf8_encode($row['nombreBanco'])."?') == true) { xajax_eliminarBanco(".$row['idBanco'].");}\"/></td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBancos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBancos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaBancos(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBancos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBancos(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("tdListaBancos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function nuevoBanco(){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("byId('btnGuardar').style.display = '';");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarBanco");
$xajax->register(XAJAX_FUNCTION,"eliminarBanco");
$xajax->register(XAJAX_FUNCTION,"formBanco");
$xajax->register(XAJAX_FUNCTION,"guardarBanco");
$xajax->register(XAJAX_FUNCTION,"listaBancos");
$xajax->register(XAJAX_FUNCTION,"nuevoBanco");

?>