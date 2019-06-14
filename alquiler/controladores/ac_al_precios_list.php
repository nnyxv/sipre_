<?php

function asignarTiempo($arrayDetalle = array(0)){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT id_tipo_precio, descripcion FROM al_tipo_precio");
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	while($row = mysql_fetch_assoc($rs)){
		$arrayTipoPrecios[$row["id_tipo_precio"]] = $row["descripcion"];
	}
	
	//si viene de bd carga arrayDetalle, sino solo agrega un item con array vacio
	foreach($arrayDetalle as $detalle){

		$select = "<select style=\"width:95%\" name=\"lstTipoTiempo[]\" class=\"inputHabilitado\">";
			$select .= "<option value=\"-1\">[ Seleccione ]</option>";
			foreach($arrayTipoPrecios as $indice => $valor){
				$selected = "";
				if($indice == $detalle["id_tipo_precio"]){ $selected = "selected=\"selected\""; }
				$select .= "<option value=\"".$indice."\" ".$selected.">".utf8_encode($valor)."</option>";
			}
		$select .= "</select>";
	
		$tr .= "<tr class=\"itemTiempo\">";
			$tr .= "<td><input type=\"hidden\" name=\"idDetallePrecio[]\" value=\"".$detalle["id_precio_detalle"]."\" /><input style=\"width:95%\" type=\"text\" name=\"txtDescripcionTiempo[]\" onkeypress=\"return validarSoloTextoNumero(event);\" class=\"inputHabilitado\" value=\"".utf8_encode($detalle["descripcion"])."\" /></td>";
			$tr .= "<td><input style=\"width:95%; text-align:right;\" type=\"text\" name=\"txtPrecioTiempo[]\" onkeypress=\"return validarSoloNumerosReales(event);\" onblur=\"setFormatoRafk(this,2);\" class=\"inputHabilitado\" value=\"".number_format($detalle["precio"], 2, ".", ",")."\" /></td>";
			$tr .= "<td><input style=\"width:95%; text-align:right;\" type=\"text\" name=\"txtDiasTiempo[]\" onkeypress=\"return validarSoloNumeros(event);\" class=\"inputHabilitado\" value=\"".$detalle["dias"]."\" /></td>";
			$tr .= "<td>".$select."</td>";
			$tr .= "<td><a onclick=\"eliminarTiempo(this, \'".$detalle["id_precio_detalle"]."\');\"><img title=\"Eliminar\" src=\"../img/iconos/cross.png\" class=\"puntero\"></a></td>";
		$tr .= "</tr>";
	
	}
	$objResponse->script("$('#trItmPie').before('".$tr."');");
	
	return $objResponse;
}

function buscarPrecio($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstActivoBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPrecio(0, "id_precio", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarPrecio($idPrecio) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"al_precios_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");

	$deleteSQL = sprintf("DELETE FROM al_precios_detalle WHERE id_precio = %s",
		valTpDato($idPrecio, "int"));
	$Result1 = mysql_query($deleteSQL);	
	if(mysql_errno() == 1451){ return $objResponse->alert("No se puede eliminar porque los precios ya estan en uso"); }
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);		
	
	$deleteSQL = sprintf("DELETE FROM al_precios WHERE id_precio = %s",
		valTpDato($idPrecio, "int"));
	$Result1 = mysql_query($deleteSQL);	
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnBuscar').click();");
	$objResponse->alert("Eliminado Correctamente");
	
	return $objResponse;
}

function formPrecio($idPrecio) {
	$objResponse = new xajaxResponse();
	
	if ($idPrecio > 0) {
		if (!xvalidaAcceso($objResponse,"al_precios_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarPrecio').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM al_precios WHERE id_precio = %s;",
			valTpDato($idPrecio, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdPrecio","value",$idPrecio);
		$objResponse->assign("lstActivo","value",$row['activo']);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nombre_precio']));
		$objResponse->assign("txtDescripcion","value",utf8_encode($row['descripcion_precio']));
		$objResponse->call("selectedOption","lstPoseeIva",$row['iva_precio']);
		
		$query = sprintf("SELECT * FROM al_precios_detalle WHERE id_precio = %s;",
			valTpDato($idPrecio, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$tieneDetalle = mysql_num_rows($rs);
		
		while($row = mysql_fetch_assoc($rs)){
			$arrayDetalle[] = $row;
		}
		
		if($tieneDetalle){
			$objResponse->loadCommands(asignarTiempo($arrayDetalle));
		}
		
	} else {
		if (!xvalidaAcceso($objResponse,"al_precios_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarPrecio').click();"); return $objResponse; }
		
		$objResponse->assign("lstActivo","value",1);
	}
	
	return $objResponse;
}

function guardarPrecio($frmPrecio) {
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");
	
	$idPrecio = $frmPrecio['hddIdPrecio'];
	
	if ($idPrecio > 0) {
		if (!xvalidaAcceso($objResponse,"al_precios_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE al_precios SET			
			nombre_precio = %s,
			descripcion_precio = %s,
			iva_precio = %s,
			activo = %s
		WHERE id_precio = %s;",
			valTpDato($frmPrecio['txtNombre'], "text"),
			valTpDato($frmPrecio['txtDescripcion'], "text"),
			valTpDato($frmPrecio['lstPoseeIva'], "boolean"),
			valTpDato($frmPrecio['lstActivo'], "boolean"),
			valTpDato($idPrecio, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		if($frmPrecio['hddIdDetalleEliminar'] != ""){
			$sql = sprintf("DELETE FROM al_precios_detalle WHERE id_precio_detalle IN(%s);",
				$frmPrecio['hddIdDetalleEliminar']);
			$rs = mysql_query($sql);
			if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
		
	} else {
		if (!xvalidaAcceso($objResponse,"al_precios_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO al_precios (nombre_precio, descripcion_precio, iva_precio, activo)
		VALUE (%s, %s, %s, %s);",
			valTpDato($frmPrecio['txtNombre'], "text"),
			valTpDato($frmPrecio['txtDescripcion'], "text"),
			valTpDato($frmPrecio['lstPoseeIva'], "boolean"),
			valTpDato($frmPrecio['lstActivo'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idPrecio = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	foreach($frmPrecio["idDetallePrecio"] as $indice => $idDetallePrecio){
		if($idDetallePrecio > 0){
			$updateSQL = sprintf("UPDATE al_precios_detalle SET			
				descripcion = %s,
				precio = %s,
				dias = %s,
				id_tipo_precio = %s
			WHERE id_precio_detalle = %s;",
				valTpDato($frmPrecio['txtDescripcionTiempo'][$indice], "text"),
				valTpDato($frmPrecio['txtPrecioTiempo'][$indice], "real_inglesa"),
				valTpDato($frmPrecio['txtDiasTiempo'][$indice], "int"),
				valTpDato($frmPrecio['lstTipoTiempo'][$indice], "int"),
				valTpDato($idDetallePrecio, "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}else{
			$insertSQL = sprintf("INSERT INTO al_precios_detalle (id_precio, descripcion, precio, dias, id_tipo_precio)
			VALUE (%s, %s, %s, %s, %s);",
				valTpDato($idPrecio, "int"),
				valTpDato($frmPrecio['txtDescripcionTiempo'][$indice], "text"),
				valTpDato($frmPrecio['txtPrecioTiempo'][$indice], "real_inglesa"),
				valTpDato($frmPrecio['txtDiasTiempo'][$indice], "int"),
				valTpDato($frmPrecio['lstTipoTiempo'][$indice], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Guardado correctamente.");
	
	$objResponse->script("
	byId('btnCancelarPrecio').click();
	byId('btnBuscar').click();");
	
	return $objResponse;
}

function listaPrecio($pageNum = 0, $campOrd = "id_precio", $tpOrd = "ASC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = %s",
			valTpDato($valCadBusq[0], "boolean"));
	}
		
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_precio LIKE %s
		OR descripcion_precio LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM al_precios %s", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPrecio", "15%", $pageNum, "nombre_precio", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Precio");
		$htmlTh .= ordenarCampo("xajax_listaPrecio", "30%", $pageNum, "descripcion_precio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción Precio");
		$htmlTh .= ordenarCampo("xajax_listaPrecio", "50%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Precios / Tiempos");				
		$htmlTh .= ordenarCampo("xajax_listaPrecio", "5%", $pageNum, "iva_precio", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto");		
		$htmlTh .= "<td colspan=\"2\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$imgActivo = "<img src=\"../img/iconos/ico_verde.gif\">";
		
		if($row['activo'] == "0"){
			$imgActivo = "<img src=\"../img/iconos/ico_rojo.gif\">";
		}
		
		$sqlDetalle = sprintf("SELECT
									det.descripcion,
									det.precio,
									det.dias,
									tipo.descripcion AS tipo_precio
								FROM al_precios_detalle det
								INNER JOIN al_tipo_precio tipo ON det.id_tipo_precio = tipo.id_tipo_precio
								WHERE id_precio = %s",
						$row["id_precio"]);
		$rsDetalle = mysql_query($sqlDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$tieneDetalle = mysql_num_rows($rsDetalle);
		
		$tablaDetalle = "";
		if($tieneDetalle){
			$tablaDetalle .= "<table border=\"0\" width=\"100%\" class=\"texto_9px\">";
			$tablaDetalle .= "<tr align=\"center\" class=\"tituloCampo textoNegrita_9px\">";
				$tablaDetalle .= "<td width=\"40%\">Descripci&oacute;n</td>";
				$tablaDetalle .= "<td width=\"25%\">Precio</td>";
				$tablaDetalle .= "<td width=\"15%\">D&iacute;as</td>";
				$tablaDetalle .= "<td width=\"20%\">Tipo</td>";
			$tablaDetalle .= "</tr>";
			while($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$tablaDetalle .= "<tr align=\"center\">";
					$tablaDetalle .= "<td width=\"40%\">".utf8_encode($rowDetalle["descripcion"])."</td>";
					$tablaDetalle .= "<td width=\"25%\">".number_format($rowDetalle["precio"], 2, ".", ",")."</td>";
					$tablaDetalle .= "<td width=\"15%\">".$rowDetalle["dias"]."</td>";
					$tablaDetalle .= "<td width=\"20%\">".utf8_encode($rowDetalle["tipo_precio"])."</td>";
				$tablaDetalle .= "</tr>";
			}
			$tablaDetalle .= "</table>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgActivo."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_precio'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_precio'])."</td>";
			$htmlTb .= "<td>".$tablaDetalle."</td>";
			
			$htmlTb .= "<td align=\"center\">".utf8_encode(($row['iva_precio'] == 1) ? "Si" : "No")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPrecio', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_precio']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar\"/></a>",
					$row['id_precio']);
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPrecio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
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
	
	$objResponse->assign("divListaPrecio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarTiempo");
$xajax->register(XAJAX_FUNCTION,"buscarPrecio");
$xajax->register(XAJAX_FUNCTION,"eliminarPrecio");
$xajax->register(XAJAX_FUNCTION,"formPrecio");
$xajax->register(XAJAX_FUNCTION,"guardarPrecio");
$xajax->register(XAJAX_FUNCTION,"listaPrecio");

?>