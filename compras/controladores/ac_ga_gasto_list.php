<?php
function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstModoGastoBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listadoGastos(0, "id_gasto", "ASC", $valBusq));
	
	return $objResponse;
}

function cargarGasto($idGasto) {
	$objResponse = new xajaxResponse();

	if (xvalidaAcceso($objResponse,"ga_gasto_list","editar")) {
		$objResponse->script("
		document.forms['frmGasto'].reset();
		byId('hddIdGasto').value = '';
		
		byId('txtGasto').className = 'inputHabilitado';
		byId('lstIva').className = 'inputHabilitado';
		byId('lstModoGasto').className = 'inputHabilitado';
		byId('lstAfectaDocumento').className = 'inputHabilitado';
		byId('lstAsociaDocumento').className = 'inputHabilitado';");
		
		$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s",
			valTpDato($idGasto, "int"));
		$rsGasto = mysql_query($queryGasto);
		if (!$rsGasto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowGasto = mysql_fetch_assoc($rsGasto);
		
		$objResponse->assign("hddIdGasto","value",$idGasto);
		$objResponse->assign("txtGasto","value",utf8_encode($rowGasto['nombre']));
		
		if($rowGasto['id_retencion'] == 0 || $rowGasto['id_retencion'] == NULL){
			$objResponse->script("$('#trretencion').hide();");
			$objResponse->assign("retencionISLRno","checked","true");
		}else {	
			$objResponse->script("$('#trretencion').show();");
			$objResponse->assign("retencionISLRsi","checked","true");
			$objResponse->loadCommands(cargarRetenciones($rowGasto['id_retencion']));
		}

		if ($rowGasto['estatus_iva'] == 1){
			$objResponse->assign("rbtEstatusIvaSi","checked","true");
			$objResponse->call(agregarImpuesto,1);
				$querImpuestoGasto = sprintf("SELECT * FROM pg_gastos_impuesto
						INNER JOIN pg_iva ON pg_iva.idIva = pg_gastos_impuesto.id_impuesto
					WHERE tipo in (1,3,8) AND pg_gastos_impuesto.id_gasto = %s ",$idGasto);
				$rsImpuestoGasto = mysql_query($querImpuestoGasto);
				while($rowsIpuestoGasto = mysql_fetch_assoc($rsImpuestoGasto)){
					$Result1 = insertarItemImpuesto($contFila, $rowsIpuestoGasto['id_impuesto']);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) {
						$objResponse->script("RecorrerForm('frmImpuesto',1)");// DESBLOQUEA LOS BOTONES DEL LISTADO
						return $objResponse->alert($Result1[1]);
					} else if ($Result1[0] == true) {
						$contFila = $Result1[2];
						$objResponse->script($Result1[1]);
						$arrayObj[] = $contFila;
					}
				}
		}else{
			$objResponse->assign("rbtEstutusIvaNo","checked","true");
		}

		$objResponse->call("selectedOption","lstModoGasto",$rowGasto['id_modo_gasto']);
		$objResponse->call("selectedOption","lstAfectaDocumento",$rowGasto['afecta_documento']);
		$objResponse->call("selectedOption","lstAsociaDocumento",$rowGasto['asocia_documento']);
	}
	
	return $objResponse;
}

function cargarRetenciones($selId = ""){
	$objResponse = new xajaxResponse();
		
	$queryRet = "SELECT * FROM te_retenciones;";
	$rsRetenciones = mysql_query($queryRet);
	if (!$queryRet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	$html ="<select id=\"listRet\" name=\"listRet\" class=\"inputHabilitado\" >";
		$html .="<option value=\"\">[ Seleccione]</option>";
	while($rowRetenciones = mysql_fetch_array($rsRetenciones)){
		$seleccion = "";
		if($selId == $rowRetenciones["id"]){
			$seleccion = "selected='selected'";	
		}
		$html .="<option value=".$rowRetenciones["id"]." ".$seleccion.">".$rowRetenciones["descripcion"]."</option>";
	}
	$html .= " </select>";
	
	$objResponse->assign("tdlistRetenciones","innerHTML",$html);
	
	return $objResponse;
}

function eliminarGasto($idGasto, $valFormListaGasto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_gasto_list","eliminar")) { return $objResponse; }
	
	if ($idGasto != "") {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM pg_gastos WHERE id_gasto = %s",
			valTpDato($idGasto, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		$deleteSQL = sprintf("DELETE FROM pg_gastos_impuesto WHERE id_gasto = %s",
			valTpDato($idGasto, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Eliminacion realizada con éxito");		
		$objResponse->loadCommands(listadoGastos(0, "id_gasto", "ASC"));
	}
	
	return $objResponse;
}

function eliminarGastoBloque($frmListaGasto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_gasto_list","eliminar")) { return $objResponse; }
	
	if (isset($frmListaGasto['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach ($frmListaGasto['cbxItm'] as $indiceItm => $valorItm) {
			$deleteSQL = sprintf("DELETE FROM pg_gastos WHERE id_gasto = %s",
				valTpDato($frmListaGasto, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			
			$deleteSQL2 = sprintf("DELETE FROM pg_gastos_impuesto WHERE id_gasto = %s",
				valTpDato($frmListaGasto, "int"));
			$Result1 = mysql_query($deleteSQL2);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		}

		$objResponse->script("
			if(byId('cbxItm').checked == true){
				byId('cbxItm').checked = false;
		}");
					
		mysql_query("COMMIT;");

		$objResponse->alert("Eliminación Realizada con Éxito");
		
		$objResponse->loadCommands(listadoGastos(
			$frmListaGasto['pageNum'],
			$frmListaGasto['campOrd'],
			$frmListaGasto['tpOrd'],
			$frmListaGasto['valBusq']));
	}
	
	return $objResponse;
}

function eliminarImpuesto($frmGasto, $eliminar = "") {
	$objResponse = new xajaxResponse();
	
	switch($eliminar){
		case 1:
			if(isset($frmGasto['cbxItmImpuesto2'])){
				foreach($frmGasto['cbxItmImpuesto2'] as $keyIvaGastos => $IvaGastos){
					$objResponse->script(sprintf("
						fila = document.getElementById('trItemsImpuesto:%s');
						padre = fila.parentNode;
						padre.removeChild(fila);",
					$IvaGastos));	
				}
			}
				break;
		default:
			if(isset($frmGasto['cbxItmImpuesto'])){
				foreach($frmGasto['cbxItmImpuesto'] as $keyIvaGastos => $IvaGastos){
					$objResponse->script(sprintf("
						fila = document.getElementById('trItemsImpuesto:%s');
						padre = fila.parentNode;
						padre.removeChild(fila);",
					$IvaGastos));	
				}
				$objResponse->script("
					if(byId('cbxItmImpuesto').checked == true){
						byId('cbxItmImpuesto').checked = false;
					}");
			} else {
				return $objResponse->alert("Debe seleccionar una Item ");		
			}
		$objResponse->alert("Eliminación Realizada con Éxito");
				break;	
		
	}

	return $objResponse;
}

function insertarImpuesto($idImpuesto, $frmGastos) {
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmGastos['cbxItmImpuesto2'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idImpuesto > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmGastos['hddIdImpuesto'.$valor] == $idImpuesto) {
					$existe = true;
				}
			}
		}
		if ($existe == false) {
			$Result1 = insertarItemImpuesto($contFila, $idImpuesto);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					$objResponse->script("RecorrerForm('frmImpuesto',1)");// DESBLOQUEA LOS BOTONES DEL LISTADO
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert("Este item ya se encuentra incluido");
		}
	}
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	$objResponse->script("RecorrerForm('frmImpuesto',1)");
	
	return $objResponse;
}

function formGasto() {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"ga_gasto_list","insertar")) {
		
		$objResponse->script("
		document.forms['frmGasto'].reset();
		byId('hddIdGasto').value = '';
		
		byId('txtGasto').className = 'inputHabilitado';
		byId('lstIva').className = 'inputHabilitado';
		byId('lstModoGasto').className = 'inputHabilitado';
		byId('lstAfectaDocumento').className = 'inputHabilitado';
		byId('lstAsociaDocumento').className = 'inputHabilitado';");
		
		$objResponse->assign("rbtEstatusIvaSi","checked","true");
		$objResponse->call(agregarImpuesto,1);
		$Result1 = insertarItemImpuesto($contFila, 0, 1, 1);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			$objResponse->script("RecorrerForm('frmImpuesto',1)");// DESBLOQUEA LOS BOTONES DEL LISTADO
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	}
	
	return $objResponse;
}

function guardarGasto($frmGasto, $valFormListaGasto) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");

	$idGasto = $frmGasto['hddIdGasto'];
	if ($idGasto > 0) {
		if (!xvalidaAcceso($objResponse,"ga_gasto_list","editar")) { return $objResponse; }
		
		if($frmGasto['retencionISLR'] == 1){
			if($frmGasto['listRet'] == ""){
				return $objResponse->alert("Debe seleccionar una Retencion.");
			} else {
				$listRet = $frmGasto['listRet'];
			}
		} else {
			$listRet = "";
		}

		$updateSQL = sprintf("UPDATE pg_gastos SET
			nombre = %s,
			estatus_iva = %s,
			id_modo_gasto = %s,
			afecta_documento = %s,
			asocia_documento = %s,
			id_retencion = %s
		WHERE id_gasto = %s;",
			valTpDato($frmGasto['txtGasto'], "text"),
			valTpDato($frmGasto['rbtEstutusIva'], "boolean"),
			valTpDato($frmGasto['lstModoGasto'], "int"),
			valTpDato($frmGasto['lstAfectaDocumento'], "boolean"),
			valTpDato($frmGasto['lstAsociaDocumento'], "boolean"),
			valTpDato($listRet, "int"),
			valTpDato($frmGasto['hddIdGasto'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"ga_gasto_list","insertar")) { return $objResponse; }
			
		if($frmGasto['retencionISLR'] == 1){
			if($frmGasto['listRet'] == ""){
				return $objResponse->alert("Debe seleccionar una Retencion.");
			}
		}
		
		$insertSQL = sprintf("INSERT INTO pg_gastos (nombre, estatus_iva, id_modo_gasto, afecta_documento, asocia_documento, id_retencion)
		VALUE (%s, %s, %s, %s, %s, %s);",
			valTpDato($frmGasto['txtGasto'], "text"),
			valTpDato($frmGasto['rbtEstutusIva'], "boolean"),
			valTpDato($frmGasto['lstModoGasto'], "int"),
			valTpDato($frmGasto['lstAfectaDocumento'], "boolean"),
			valTpDato($frmGasto['lstAsociaDocumento'], "boolean"),
			valTpDato($frmGasto['listRet'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$idGasto = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	//ELIMINA TODOS LOS IMPUESTOS DEL GASTO
	$deleteSQL = sprintf("DELETE FROM pg_gastos_impuesto WHERE id_gasto = %s;",
		valTpDato($idGasto, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	// RECORRE LOS IMPUESTO DEL FROMULARIO
	if (isset($frmGasto['cbxItmImpuesto2'])) {
		foreach($frmGasto['cbxItmImpuesto2'] as $indice => $valor) {
			if ($valor > 0 && in_array($frmGasto['lstModoGasto'],array(1,3))) {//INSERTA LOS IMPUESTOS NUEVOS
				$insertSQL = sprintf("INSERT INTO pg_gastos_impuesto (id_gasto, id_impuesto)
				VALUE (%s, %s);",
					valTpDato($idGasto, "int"),
					valTpDato($frmGasto['hddIdImpuesto'.$valor], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}	
	
	$updateSQL = sprintf("UPDATE pg_gastos SET
		id_iva = (SELECT gasto_impuesto.id_impuesto
					FROM pg_gastos_impuesto gasto_impuesto
						INNER JOIN pg_iva iva ON (gasto_impuesto.id_impuesto = iva.idIva)
					WHERE gasto_impuesto.id_gasto = pg_gastos.id_gasto
						AND iva.tipo IN (1)
						AND (SELECT COUNT(gasto_impuesto.id_impuesto)
							FROM pg_gastos_impuesto gasto_impuesto
								INNER JOIN pg_iva iva ON (gasto_impuesto.id_impuesto = iva.idIva)
							WHERE gasto_impuesto.id_gasto = pg_gastos.id_gasto
								AND iva.tipo IN (1)) = 1),
		estatus_iva = IF((SELECT COUNT(gasto_impuesto.id_impuesto)
						FROM pg_gastos_impuesto gasto_impuesto
						WHERE gasto_impuesto.id_gasto = pg_gastos.id_gasto) > 0,1,0)
	WHERE id_gasto = %s;",
		valTpDato($frmGasto['hddIdGasto'], "int"));
		
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");	
	
	mysql_query("COMMIT;");
  
	$objResponse->alert("Gasto Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelar').click();
	ocultaMuestra('no')");
	
	$valBusq = sprintf("|%s",
		$frmGasto['txtGasto']);
	
	$objResponse->loadCommands(listadoGastos(0, "id_gasto", "ASC", $valBusq));
	
	return $objResponse;
}

function  listadoGastos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modo_gasto = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		id_gasto,
		nombre,
		estatus_iva,
		id_modo_gasto,
		afecta_documento,
		asocia_documento,
		IFNULL((SELECT GROUP_CONCAT(iva SEPARATOR '|') AS iva 
				FROM pg_gastos_impuesto
				INNER JOIN pg_iva ON pg_iva.idIva = pg_gastos_impuesto.id_impuesto
				WHERE tipo in (1,3,8) AND pg_gastos_impuesto.id_gasto = pg_gastos.id_gasto
				GROUP BY id_gasto) ,pg_iva.iva) AS iva
	FROM pg_gastos
 	LEFT JOIN pg_iva ON pg_iva.idIva = pg_gastos.id_iva %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n".$queryLimit);
	
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n".$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,1);\"/></td>";
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "44%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "8%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% I.V.A.");
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "14%", $pageNum, "id_modo_gasto", $campOrd, $tpOrd, $valBusq, $maxRows, "Modo");
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "12%", $pageNum, "afecta_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Afecta Documento");
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "12%", $pageNum, "asocia_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Asociar Documento");
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "10%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Retenciónes");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus_iva']) {
			//case 0 : $imgEstatusIva = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatusIva = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatusIva = "";
		}
		switch ($row['afecta_documento']) {
			//case 0 : $imgEstatusIva = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 0 : $imgAfectaDoc = "<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"; break;
			default : $imgAfectaDoc = "";
		}	
		
		switch ($row['id_modo_gasto']) {
			case 1 : $modoGasto = "Gastos"; break;
			case 2 : $modoGasto = "Otros Cargos"; break;
			case 3 : $modoGasto = "Gastos por Importación"; break;
			default : $modoGasto = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",$row['id_gasto']);
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgAfectaDoc."</td>";
					$htmlTb .= "<td align=\"right\" width=\"100%\">";
						$porcIva = explode("|",$row['iva']);
						foreach($porcIva as $indice => $valor){
							if($valor != ""){
								$htmlTb .= "<table width=\"100%\">";
									$htmlTb .= "<tr>";
										$htmlTb .= "<td>".$imgEstatusIva."</td>";
										$htmlTb .= "<td align=\"right\" width=\"100%\">".$valor."%</td>";
									$htmlTb .= "</tr>";
								$htmlTb .= "</table>";
							}
						}	
					$htmlTb .= "</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".($modoGasto)."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['afecta_documento'] == 1) ? "Si" : "-")."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['asocia_documento'] == 1) ? "Si" : "-")."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['porcentaje'] == NULL) ? "-" : $row['porcentaje'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGasto', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_gasto']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/cross.png\"/></td>",
				$row['id_gasto']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoGastos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoGastos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoGastos(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoGastos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoGastos(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaGasto","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listImpuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanClienteCxC;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tipo IN (1,3,8)");
	
	$query = sprintf("SELECT idIva, iva, observacion, tipo_impuesto 
	FROM pg_iva 
		INNER JOIN pg_tipo_impuesto ON (pg_iva.tipo = pg_tipo_impuesto.id_tipo_impuesto) %s", $sqlBusq);
//$objResponse->alert($query);	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listImpuesto", "5%", $pageNum, "idIva", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listImpuesto", "15%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Iva");
		$htmlTh .= ordenarCampo("xajax_listImpuesto", "50%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observación");
		$htmlTh .= ordenarCampo("xajax_listImpuesto", "30%", $pageNum, "tipo_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Impuesto"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
//		<input type=\"checkbox\" id=\"ckboxIvaList\" name=\"ckboxIvaList[]\" value=\"%s\"/>
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><button id=\"btnImpuesto%s\" name=\"btnImpuesto%s\" type=\"button\" onclick=\"RecorrerForm('frmImpuesto',0);xajax_insertarImpuesto(%s, xajax.getFormValues('frmGasto'))\" title=\"Seleccionar\"><img src=\"../img/iconos/add.png\"/></button></td>",
				$row['idIva'],$row['idIva'],$row['idIva']);
			$htmlTb .= "<td align=\"right\">".$row['idIva']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['iva']."%"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_impuesto'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listImpuesto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
		$htmlTb .= "<td colspan=\"5\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListIpmuesto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;	
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargarGasto");
$xajax->register(XAJAX_FUNCTION,"cargarRetenciones");
$xajax->register(XAJAX_FUNCTION,"eliminarGasto");
$xajax->register(XAJAX_FUNCTION,"eliminarGastoBloque");
$xajax->register(XAJAX_FUNCTION,"eliminarImpuesto");
$xajax->register(XAJAX_FUNCTION,"insertarImpuesto");
$xajax->register(XAJAX_FUNCTION,"formGasto");
$xajax->register(XAJAX_FUNCTION,"guardarGasto");
$xajax->register(XAJAX_FUNCTION,"listadoGastos");
$xajax->register(XAJAX_FUNCTION,"listImpuesto");

function insertarItemImpuesto($contFila, $idImpuesto = "", $idTipoImpuesto = "", $idImpuestoPredeterminado = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idImpuesto != "" && $idImpuesto != 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idIva = %s",
			valTpDato($idImpuesto, "int"));
	}
	
	if ($idTipoImpuesto != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo = %s",
			valTpDato($idTipoImpuesto, "int"));
	}
	
	if ($idImpuestoPredeterminado != "" && $idTipoImpuesto != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = %s",
			valTpDato(1, "int"));
	}
	
	if ($idImpuestoPredeterminado != "" && $idTipoImpuesto != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estado = %s",
			valTpDato(1, "int"));
	}
	
	// BUSCA LOS DATOS DEL TIPO DE ORDEN
	$query = sprintf("SELECT idIva,tipo,tipo_impuesto,observacion,iva,activo,estado FROM pg_iva 
		INNER JOIN pg_tipo_impuesto ON pg_tipo_impuesto.id_tipo_impuesto = pg_iva.tipo %s", $sqlBusq,
			valTpDato($idImpuesto, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItemsImpuesto').before('".
		"<tr id=\"trItemsImpuesto:%s\" align=\"left\" class=\"textoGris_11px %s\" title=\"trItemsImpuesto:%s\">".
			"<td align=\"center\">".
				"<input id=\"cbxItmImpuesto\" name=\"cbxItmImpuesto[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxItmImpuesto2\" name=\"cbxItmImpuesto2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
			"</td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td>%s<input type=\"hidden\" id=\"hddIdImpuesto%s\" name=\"hddIdImpuesto%s\" readonly=\"readonly\" value=\"%s\"/>".
		"</tr>');",
		$contFila, $clase, $contFila,//tr
		$contFila,//check
			$contFila,//check
			$contFila,				
			utf8_encode($row['tipo_impuesto']),//
			utf8_encode($row['observacion']),
				$row['iva']."%", $contFila, $contFila, $row['idIva']/**/);
	
	return array(true, $htmlItmPie, $contFila,$query);
}

?>