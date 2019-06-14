<?php

function asignarTiempo($idPrecios, $frmPrecio){//id separados por coma
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT id_precio, nombre_precio, descripcion_precio
					FROM al_precios
					WHERE id_precio IN(%s)",
					$idPrecios);
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	while($row = mysql_fetch_assoc($rs)){
		
		if(in_array($row["id_precio"], $frmPrecio["hddIdPrecio"])){
			return $objResponse->alert("Ya se encuentra agregado");
		}
		
		$tr .= "<tr class=\"itemTiempo\">";
			$tr .= "<td><input type=\"hidden\" name=\"hddIdPrecio[]\" value=\"".$row["id_precio"]."\" />".utf8_encode($row["nombre_precio"])."</td>";
			$tr .= "<td>".utf8_encode($row["descripcion_precio"])."</td>";
			$tr .= "<td><a onclick=\"eliminarTiempo(this, \'".$row["id_precio"]."\');\"><img title=\"Eliminar\" src=\"../img/iconos/cross.png\" class=\"puntero\"></a></td>";
		$tr .= "</tr>";	
	}
	$objResponse->script("$('#trItmPie').before('".$tr."');");
	
	return $objResponse;
}

function buscarClase($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaClase(0, "id_clase", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarPrecio($frmBuscarPrecio) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarPrecio['txtCriterioBuscarPrecio']);
	
	$objResponse->loadCommands(listaPrecios(0, "id_precio", "ASC", $valBusq));
		
	return $objResponse;
}

function eliminarPrecio($idClase) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"al_asignar_precios_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");

	$deleteSQL = sprintf("DELETE FROM al_precios_clase WHERE id_clase = %s",
		valTpDato($idClase, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnBuscar').click();");
	$objResponse->alert("Eliminado Correctamente");
	
	return $objResponse;
}

function formPrecio($idClase) {
	$objResponse = new xajaxResponse();
	
	if ($idClase > 0) {
		if (!xvalidaAcceso($objResponse,"al_asignar_precios_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarPrecio').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM an_clase WHERE id_clase = %s;",
			valTpDato($idClase, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdClase","value",$idClase);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nom_clase']));
		$objResponse->assign("txtDescripcion","value",utf8_encode($row['des_clase']));
		
		$query = sprintf("SELECT id_precio FROM al_precios_clase WHERE id_clase = %s;",
			valTpDato($idClase, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$tieneDetalle = mysql_num_rows($rs);
		
		while($row = mysql_fetch_assoc($rs)){
			$arrayIdPrecios[] = $row["id_precio"];
		}
		
		if($tieneDetalle){
			$objResponse->loadCommands(asignarTiempo(implode(",", $arrayIdPrecios)));
		}
		
	} else {
		if (!xvalidaAcceso($objResponse,"al_asignar_precios_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarPrecio').click();"); return $objResponse; }		
	}
	
	return $objResponse;
}

function guardarPrecio($frmPrecio) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"al_asignar_precios_list","editar")) { return $objResponse; }
		
	mysql_query("START TRANSACTION;");
	
	$idClase = $frmPrecio['hddIdClase'];
	$arrayIdPrecios = $frmPrecio["hddIdPrecio"];//es array
	
	if($frmPrecio['hddIdPrecioEliminar'] != ""){
		$sql = sprintf("DELETE FROM al_precios_clase 
						WHERE id_precio IN(%s)
						AND id_clase = %s;",
			$frmPrecio['hddIdPrecioEliminar'],
			valTpDato($idClase,"int"));
		$rs = mysql_query($sql);
		if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	$sql = sprintf("SELECT id_precio FROM al_precios_clase 
					WHERE id_clase = %s;",
		valTpDato($idClase,"int"));
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

	while($row = mysql_fetch_assoc($rs)){
		$arrayIdPreciosExistentes[] = $row["id_precio"];
	}
	
	foreach($arrayIdPrecios as $indice => $idPrecio){
		if(!in_array($idPrecio, $arrayIdPreciosExistentes)){//sino existe
			$sql = sprintf("INSERT INTO al_precios_clase (id_clase, id_precio)  
							VALUES (%s, %s);",
				valTpDato($idClase,"int"),
				valTpDato($idPrecio,"int"));
			$rs = mysql_query($sql);
			if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Guardado correctamente.");
	
	$objResponse->script("
	byId('btnCancelarPrecio').click();
	byId('btnBuscar').click();");
	
	return $objResponse;
}

function listaPrecios($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("activo = 1");
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_precio LIKE %s
		OR descripcion_precio LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= "<td width=\"1%\"></td>";		
		$htmlTh .= ordenarCampo("xajax_listaPrecios", "30%", $pageNum, "nombre_precio", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo Precio");
		$htmlTh .= ordenarCampo("xajax_listaPrecios", "60%", $pageNum, "descripcion_precio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n Precio");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgActivo = "<img src=\"../img/iconos/ico_verde.gif\">";
		
		if($row['activo'] == "0"){
			$imgActivo = "<img src=\"../img/iconos/ico_rojo.gif\">";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" onclick=\"xajax_asignarTiempo(%s, xajax.getFormValues('frmPrecio'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$row['id_precio']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".$imgActivo."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_precio'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_precio'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecios(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecios(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPrecios(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecios(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecios(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"20\">";
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

function listaClase($pageNum = 0, $campOrd = "id_clase", $tpOrd = "ASC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_clase LIKE %s
		OR des_clase LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_clase %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaClase", "15%", $pageNum, "nom_clase", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Clase");
		$htmlTh .= ordenarCampo("xajax_listaClase", "30%", $pageNum, "des_clase", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción Clase");
		$htmlTh .= ordenarCampo("xajax_listaClase", "50%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Códigos Precios Asignados");			
		$htmlTh .= "<td colspan=\"2\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$sqlDetalle = sprintf("SELECT
									nombre_precio,
									descripcion_precio
								FROM al_precios_clase
								INNER JOIN al_precios ON al_precios_clase.id_precio = al_precios.id_precio
								WHERE id_clase = %s",
						$row["id_clase"]);
		$rsDetalle = mysql_query($sqlDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$tieneDetalle = mysql_num_rows($rsDetalle);
		
		$detalle = "";
		if($tieneDetalle){
			$arrayPrecios = array();
			while($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$arrayPrecios[] = utf8_encode($rowDetalle["nombre_precio"]);
			}			
			$detalle = implode(" | ", $arrayPrecios);
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nom_clase'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['des_clase'])."</td>";
			$htmlTb .= "<td align=\"center\">".$detalle."</td>";
			
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPrecio', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_clase']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar\"/></a>",
					$row['id_clase']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClase(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClase(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaClase(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClase(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaClase(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaClase","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarTiempo");
$xajax->register(XAJAX_FUNCTION,"buscarClase");
$xajax->register(XAJAX_FUNCTION,"buscarPrecio");
$xajax->register(XAJAX_FUNCTION,"eliminarPrecio");
$xajax->register(XAJAX_FUNCTION,"formPrecio");
$xajax->register(XAJAX_FUNCTION,"guardarPrecio");
$xajax->register(XAJAX_FUNCTION,"listaClase");
$xajax->register(XAJAX_FUNCTION,"listaPrecios");

?>