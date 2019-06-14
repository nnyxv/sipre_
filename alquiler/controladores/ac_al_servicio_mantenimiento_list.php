<?php

function asignarMarca($idMarca, $frmServicioMantenimiento){
	$objResponse = new xajaxResponse();
	
	foreach($frmServicioMantenimiento["idMarca"] as $idMarcaAgregada){
		if ($idMarca == $idMarcaAgregada){
			return $objResponse->alert("La marca ya se encuentra asignada");
		}
	}
	
	$sql = sprintf("SELECT id_marca, nom_marca FROM an_marca WHERE id_marca = %s", 
		valTpDato($idMarca, "int"));
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$tr .= "<tr class=\"itemMarca\">";
		$tr .= "<td><input type=\"hidden\" name=\"idMarca[]\" value=\"".$row["id_marca"]."\" />".$row['nom_marca']."</td>";
		$tr .= "<td><a onclick=\"eliminarMarca(this);\"><img title=\"Eliminar\" src=\"../img/iconos/cross.png\" class=\"puntero\"></a></td>";
	$tr .= "</tr>";
	
	$objResponse->script("$('#trItmPieMarca').before('".$tr."');");
	
	return $objResponse;
}

function buscarMarca($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterioBuscarMarca']);
	
	$objResponse->loadCommands(listaMarca(0, "id_marca", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarServicioMantenimiento($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstActivoBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaServicioMantenimiento(0, "id_servicio_mantenimiento", "ASC", $valBusq));
	
	return $objResponse;
}

function eliminarServicioMantenimiento($idServicioMantenimiento) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"al_servicio_mantenimiento_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");

	$deleteSQL = sprintf("DELETE FROM al_servicio_mantenimiento_marca WHERE id_servicio_mantenimiento = %s",
		valTpDato($idServicioMantenimiento, "int"));
	$Result1 = mysql_query($deleteSQL);	
	if(mysql_errno() == 1451){ return $objResponse->alert("No se puede eliminar porque el servicio/mantenimiento de la marca ya está en uso"); }
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);		
	
	$deleteSQL = sprintf("DELETE FROM al_servicio_mantenimiento WHERE id_servicio_mantenimiento = %s",
		valTpDato($idServicioMantenimiento, "int"));
	$Result1 = mysql_query($deleteSQL);
	if(mysql_errno() == 1451){ return $objResponse->alert("No se puede eliminar porque el servicio/mantenimiento ya está en uso"); }
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnBuscar').click();");
	$objResponse->alert("Eliminado Correctamente");
	
	return $objResponse;
}

function formServicioMantenimiento($idServicioMantenimiento) {
	$objResponse = new xajaxResponse();
	
	if ($idServicioMantenimiento > 0) {
		if (!xvalidaAcceso($objResponse,"al_servicio_mantenimiento_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarServicioMantenimiento').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM al_servicio_mantenimiento WHERE id_servicio_mantenimiento = %s;",
			valTpDato($idServicioMantenimiento, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdServicioMantenimiento","value",$row['id_servicio_mantenimiento']);
		$objResponse->assign("lstActivo","value",$row['activo']);
		$objResponse->assign("txtDescripcionServicioMantenimiento","value",utf8_encode($row['descripcion_servicio_mantenimiento']));
		$objResponse->assign("txtKilometraje","value",$row['kilometraje']);
		$objResponse->assign("txtKilometrajeAntes","value",$row['kilometraje_antes']);
		$objResponse->assign("txtKilometrajeDespues","value",$row['kilometraje_despues']);
		
		$query = sprintf("SELECT * FROM al_servicio_mantenimiento_marca WHERE id_servicio_mantenimiento = %s;",
			valTpDato($idServicioMantenimiento, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while($rowMarca = mysql_fetch_assoc($rs)){
			$objResponse->loadCommands(asignarMarca($rowMarca["id_marca"]));
		}
		
	} else {
		if (!xvalidaAcceso($objResponse,"al_servicio_mantenimiento_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarServicioMantenimiento').click();"); return $objResponse; }
		
		$objResponse->assign("lstActivo","value",1);
	}
	
	return $objResponse;
}

function guardarServicioMantenimiento($frmServicioMantenimiento) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idServicioMantenimiento = $frmServicioMantenimiento['hddIdServicioMantenimiento'];
	
	if ($idServicioMantenimiento > 0) {
		if (!xvalidaAcceso($objResponse,"al_servicio_mantenimiento_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE al_servicio_mantenimiento SET			
			descripcion_servicio_mantenimiento = %s,
			kilometraje = %s,
			kilometraje_antes = %s,
			kilometraje_despues = %s,
			activo = %s
		WHERE id_servicio_mantenimiento = %s;",
			valTpDato($frmServicioMantenimiento['txtDescripcionServicioMantenimiento'], "text"),
			valTpDato($frmServicioMantenimiento['txtKilometraje'], "int"),
			valTpDato($frmServicioMantenimiento['txtKilometrajeAntes'], "int"),
			valTpDato($frmServicioMantenimiento['txtKilometrajeDespues'], "int"),
			valTpDato($frmServicioMantenimiento['lstActivo'], "boolean"),
			valTpDato($idServicioMantenimiento, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"al_servicio_mantenimiento_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO al_servicio_mantenimiento (descripcion_servicio_mantenimiento, kilometraje, kilometraje_antes, kilometraje_despues, activo)
		VALUE (%s, %s, %s, %s, %s);",
			valTpDato($frmServicioMantenimiento['txtDescripcionServicioMantenimiento'], "text"),
			valTpDato($frmServicioMantenimiento['txtKilometraje'], "int"),
			valTpDato($frmServicioMantenimiento['txtKilometrajeAntes'], "int"),
			valTpDato($frmServicioMantenimiento['txtKilometrajeDespues'], "int"),
			valTpDato($frmServicioMantenimiento['lstActivo'], "boolean"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idServicioMantenimiento = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	$sql = sprintf("DELETE FROM al_servicio_mantenimiento_marca WHERE id_servicio_mantenimiento = %s;",
		valTpDato($idServicioMantenimiento, "int"));
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	foreach($frmServicioMantenimiento["idMarca"] as $indice => $idMarca){
			$insertSQL = sprintf("INSERT INTO al_servicio_mantenimiento_marca (id_servicio_mantenimiento, id_marca)
			VALUE (%s, %s);",
				valTpDato($idServicioMantenimiento, "int"),
				valTpDato($idMarca, "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Guardado correctamente.");
	
	$objResponse->script("
	byId('btnCancelarServicioMantenimiento').click();
	byId('btnBuscar').click();");
	
	return $objResponse;
}

function listaMarca($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_marca LIKE %s
		OR des_marca LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_marca %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaMarca", "8%", $pageNum, "id_marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaMarca", "46%", $pageNum, "nom_marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Marca");
		$htmlTh .= ordenarCampo("xajax_listaMarca", "46%", $pageNum, "des_marca", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" onclick=\"xajax_asignarMarca(%s, xajax.getFormValues('frmServicioMantenimiento'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$row['id_marca']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_marca'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_marca'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['des_marca'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMarca(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMarca(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMarca(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMarca(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMarca(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
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
	
	$objResponse->assign("divListaMarca","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listaServicioMantenimiento($pageNum = 0, $campOrd = "id_servicio_mantenimiento", $tpOrd = "ASC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanKilometraje;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = %s",
			valTpDato($valCadBusq[0], "boolean"));
	}
		
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(descripcion_servicio_mantenimiento LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM al_servicio_mantenimiento %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaServicioMantenimiento", "25%", $pageNum, "descripcion_servicio_mantenimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Servicio / Mantenimiento");
		$htmlTh .= ordenarCampo("xajax_listaServicioMantenimiento", "10%", $pageNum, "kilometraje", $campOrd, $tpOrd, $valBusq, $maxRows, $spanKilometraje);
		$htmlTh .= ordenarCampo("xajax_listaServicioMantenimiento", "10%", $pageNum, "kilometraje_antes", $campOrd, $tpOrd, $valBusq, $maxRows, $spanKilometraje." Antes");
		$htmlTh .= ordenarCampo("xajax_listaServicioMantenimiento", "10%", $pageNum, "kilometraje_despues", $campOrd, $tpOrd, $valBusq, $maxRows, $spanKilometraje." Después");
		$htmlTh .= ordenarCampo("xajax_listaServicioMantenimiento", "30%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Marcas");
		$htmlTh .= "<td colspan=\"2\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch ($row['activo']) {
			case 0 : $imgActivo = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgActivo = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgActivo = $row['activo']; break;
		}
		
		$sqlMarca = sprintf("SELECT
				marca.nom_marca
			FROM al_servicio_mantenimiento_marca serv_mant_marca
				INNER JOIN an_marca marca ON (serv_mant_marca.id_marca = marca.id_marca)
			WHERE serv_mant_marca.id_servicio_mantenimiento = %s",
			valTpDato($row["id_servicio_mantenimiento"], "int"));
		$rsMarca = mysql_query($sqlMarca);
		if (!$rsMarca) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$detalleMarcas = "";
		while($rowMarca = mysql_fetch_assoc($rsMarca)){
			$detalleMarcas .= $rowMarca["nom_marca"].", ";
		}
		$detalleMarcas = substr($detalleMarcas,0,-2);
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgActivo."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_servicio_mantenimiento'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['kilometraje'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['kilometraje_antes'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['kilometraje_despues'])."</td>";
			$htmlTb .= "<td>".$detalleMarcas."</td>";
			
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblServicioMantenimiento', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_servicio_mantenimiento']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar\"/></a>",
					$row['id_servicio_mantenimiento']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaServicioMantenimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaServicioMantenimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaServicioMantenimiento(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaServicioMantenimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaServicioMantenimiento(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaServicioMantenimiento","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarMarca");
$xajax->register(XAJAX_FUNCTION,"buscarMarca");
$xajax->register(XAJAX_FUNCTION,"buscarServicioMantenimiento");
$xajax->register(XAJAX_FUNCTION,"eliminarServicioMantenimiento");
$xajax->register(XAJAX_FUNCTION,"formServicioMantenimiento");
$xajax->register(XAJAX_FUNCTION,"guardarServicioMantenimiento");
$xajax->register(XAJAX_FUNCTION,"listaMarca");
$xajax->register(XAJAX_FUNCTION,"listaServicioMantenimiento");

?>