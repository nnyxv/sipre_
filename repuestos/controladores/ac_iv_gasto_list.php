<?php


function asignarModo($idModoGasto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('fieldsetlstImpuesto').style.display = 'none';
	byId('trlstAfectaDocumento').style.display = 'none';
	byId('trlstAsociaDocumento').style.display = 'none';");
	
	$objResponse->call("selectedOption","lstAfectaDocumento",0);
	$objResponse->call("selectedOption","lstAsociaDocumento",0);
	
	switch($idModoGasto) {
		case 1 : // Gastos
			$objResponse->script("byId('fieldsetlstImpuesto').style.display = '';");
			$objResponse->call("selectedOption","lstAfectaDocumento","-1");
			$objResponse->script("byId('trlstAfectaDocumento').style.display = '';");
			break;
		case 2 : // Otros Cargos
			$objResponse->call("selectedOption","lstIva",0);
			$objResponse->call("selectedOption","lstIvaVenta",0);
			$objResponse->call("selectedOption","lstAsociaDocumento","-1");
			$objResponse->script("byId('trlstAsociaDocumento').style.display = '';");
			break;
		case 3 : // Gastos por Importacion
			$objResponse->script("byId('fieldsetlstImpuesto').style.display = '';");
			break;
	}
	
	return $objResponse;
}

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstModoGastoBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaGasto(0, "id_gasto", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarImpuesto($frmBuscarImpuesto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarImpuesto['txtCriterioBuscarImpuesto']);
	
	$objResponse->loadCommands(listaImpuesto(0, "idIva", "ASC", $valBusq));
		
	return $objResponse;
}

function eliminarGasto($idGasto, $frmListaGasto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_gasto_list","eliminar")) { return $objResponse; }
	
	if (isset($idGasto)) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM pg_gastos WHERE id_gasto = %s",
			valTpDato($idGasto, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Eliminacion realizada con éxito");
		
		$objResponse->loadCommands(listaGasto(
			$frmListaGasto['pageNum'],
			$frmListaGasto['campOrd'],
			$frmListaGasto['tpOrd'],
			$frmListaGasto['valBusq']));
	}
	
	return $objResponse;
}

function eliminarGastoImpuesto($frmGasto) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmGasto['cbxItmImpuesto'])) {
		foreach($frmGasto['cbxItmImpuesto'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmImpuesto:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarGastoImpuesto(xajax.getFormValues('frmGasto'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmGasto['cbxImpuesto'];
	
	return $objResponse;
}

function eliminarGastoLote($frmListaGasto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_gasto_list","eliminar")) { return $objResponse; }
	
	if (isset($frmListaGasto['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach ($frmListaGasto['cbxItm'] as $indiceItm => $valorItm) {
			$deleteSQL = sprintf("DELETE FROM pg_gastos WHERE id_gasto = %s",
				valTpDato($valorItm, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		mysql_query("COMMIT;");

		$objResponse->alert("Eliminación Realizada con Éxito");
		
		$objResponse->loadCommands(listaGasto(
			$frmListaGasto['pageNum'],
			$frmListaGasto['campOrd'],
			$frmListaGasto['tpOrd'],
			$frmListaGasto['valBusq']));
	}
	
	return $objResponse;
}

function formGasto($idGasto, $frmGasto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjImpuesto = $frmGasto['cbxImpuesto'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObjImpuesto)) {
		foreach($arrayObjImpuesto as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItmImpuesto:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	if ($idGasto > 0) {
		if (!xvalidaAcceso($objResponse,"iv_gasto_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarGasto').click();"); return $objResponse; }
		
		$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s",
			valTpDato($idGasto, "int"));
		$rsGasto = mysql_query($queryGasto);
		if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowGasto = mysql_fetch_assoc($rsGasto);
		
		$objResponse->assign("hddIdGasto","value",$idGasto);
		$objResponse->assign("txtGasto","value",utf8_encode($rowGasto['nombre']));
		
		$objResponse->call("selectedOption","lstModoGasto",$rowGasto['id_modo_gasto']);
		$objResponse->loadCommands(asignarModo($rowGasto['id_modo_gasto']));
		$objResponse->call("selectedOption","lstAfectaDocumento",$rowGasto['afecta_documento']);
		$objResponse->call("selectedOption","lstAsociaDocumento",$rowGasto['asocia_documento']);
		
		$queryGastoImpuesto = sprintf("SELECT * FROM pg_gastos_impuesto gasto_impuesto WHERE gasto_impuesto.id_gasto = %s;",
			valTpDato($idGasto, "int"));
		$rsGastoImpuesto = mysql_query($queryGastoImpuesto);
		if (!$rsGastoImpuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowGastoImpuesto = mysql_fetch_assoc($rsGastoImpuesto)) {
			$Result1 = insertarItemImpuesto($contFila4, $rowGastoImpuesto['id_gasto_impuesto'], $rowGastoImpuesto['id_impuesto']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila4 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj4[] = $contFila4;
			}
		}
	} else {
		if (!xvalidaAcceso($objResponse,"iv_gasto_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarGasto').click();"); return $objResponse; }
		
		$objResponse->loadCommands(asignarModo("-1"));
		
		// INSERTA LOS IMPUESTOS DEL ARTICULO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$query = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1,6) AND iva.estado = 1 AND iva.activo = 1;");
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemImpuesto($contFila4, "", $row['idIva']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila4 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj4[] = $contFila4;
			}
		}
	}
	
	return $objResponse;
}

function guardarGasto($frmGasto, $frmListaGasto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjImpuesto = $frmGasto['cbxImpuesto'];
	
	mysql_query("START TRANSACTION;");
	
	$idGasto = $frmGasto['hddIdGasto'];
	
	if ($idGasto > 0) {
		if (!xvalidaAcceso($objResponse,"iv_gasto_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_gastos SET
			nombre = %s,
			id_modo_gasto = %s,
			afecta_documento = %s,
			asocia_documento = %s
		WHERE id_gasto = %s;",
			valTpDato($frmGasto['txtGasto'], "text"),
			valTpDato($frmGasto['lstModoGasto'], "int"),
			valTpDato($frmGasto['lstAfectaDocumento'], "boolean"),
			valTpDato($frmGasto['lstAsociaDocumento'], "boolean"),
			valTpDato($idGasto, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_gasto_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_gastos (nombre, id_modo_gasto, afecta_documento, asocia_documento)
		VALUE (%s, %s, %s, %s);",
			valTpDato($frmGasto['txtGasto'], "text"),
			valTpDato($frmGasto['lstModoGasto'], "int"),
			valTpDato($frmGasto['lstAfectaDocumento'], "boolean"),
			valTpDato($frmGasto['lstAsociaDocumento'], "boolean"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idGasto = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// ELIMINA TODOS LOS IMPUESTOS DEL GASTO
	$deleteSQL = sprintf("DELETE FROM pg_gastos_impuesto WHERE id_gasto = %s;",
		valTpDato($idGasto, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// INSERTA LOS IMPUESTOS NUEVOS
	if (isset($arrayObjImpuesto)) {
		foreach($arrayObjImpuesto as $indice => $valor) {
			$idImpuesto = $frmGasto['hddIdImpuesto'.$valor];
			
			if ($idImpuesto > 0 && in_array($frmGasto['lstModoGasto'],array(1,3))) {
				$insertSQL = sprintf("INSERT INTO pg_gastos_impuesto (id_gasto, id_impuesto)
				VALUE (%s, %s);",
					valTpDato($idGasto, "int"),
					valTpDato($idImpuesto, "int"));
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
		id_iva_venta = (SELECT gasto_impuesto.id_impuesto
						FROM pg_gastos_impuesto gasto_impuesto
							INNER JOIN pg_iva iva ON (gasto_impuesto.id_impuesto = iva.idIva)
						WHERE gasto_impuesto.id_gasto = pg_gastos.id_gasto
							AND iva.tipo IN (6)
							AND (SELECT COUNT(gasto_impuesto.id_impuesto)
								FROM pg_gastos_impuesto gasto_impuesto
									INNER JOIN pg_iva iva ON (gasto_impuesto.id_impuesto = iva.idIva)
								WHERE gasto_impuesto.id_gasto = pg_gastos.id_gasto
									AND iva.tipo IN (6)) = 1),
		estatus_iva = IF((SELECT COUNT(gasto_impuesto.id_impuesto)
						FROM pg_gastos_impuesto gasto_impuesto
						WHERE gasto_impuesto.id_gasto = pg_gastos.id_gasto) > 0,1,0)
	WHERE id_gasto = %s;",
		valTpDato($idGasto, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Gasto Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarGasto').click();");
	
	$objResponse->loadCommands(listaGasto(
		$frmListaGasto['pageNum'],
		$frmListaGasto['campOrd'],
		$frmListaGasto['tpOrd'],
		$frmListaGasto['valBusq']));
		
	return $objResponse;
}

function insertarImpuesto($idImpuesto, $frmGasto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmGasto['cbxImpuesto'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idImpuesto > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmGasto['hddIdImpuesto'.$valor] == $idImpuesto) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemImpuesto($contFila, "", $idImpuesto);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
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
	
	return $objResponse;
}

function listaGasto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
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
		gasto.id_gasto,
		gasto.nombre,
		gasto.estatus_iva,
		gasto.id_modo_gasto,
		gasto.afecta_documento,
		gasto.asocia_documento,
		(SELECT SUM(iva.iva)
		FROM pg_gastos_impuesto gasto_impuesto
			INNER JOIN pg_iva iva ON (gasto_impuesto.id_impuesto = iva.idIva)
		WHERE gasto_impuesto.id_gasto = gasto.id_gasto
			AND iva.tipo IN (1,8,3)) AS iva_compra,
		(SELECT SUM(iva.iva)
		FROM pg_gastos_impuesto gasto_impuesto
			INNER JOIN pg_iva iva ON (gasto_impuesto.id_impuesto = iva.idIva)
		WHERE gasto_impuesto.id_gasto = gasto.id_gasto
			AND iva.tipo IN (6,9,2)) AS iva_venta
	FROM pg_gastos gasto %s", $sqlBusq);
	
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
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,1);\"/></td>";
		$htmlTh .= ordenarCampo("xajax_listaGasto", "50%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaGasto", "8%", $pageNum, "iva_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto Compra");
		$htmlTh .= ordenarCampo("xajax_listaGasto", "8%", $pageNum, "iva_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto Venta");
		$htmlTh .= ordenarCampo("xajax_listaGasto", "14%", $pageNum, "id_modo_gasto", $campOrd, $tpOrd, $valBusq, $maxRows, "Modo");
		$htmlTh .= ordenarCampo("xajax_listaGasto", "10%", $pageNum, "afecta_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Afecta Documento");
		$htmlTh .= ordenarCampo("xajax_listaGasto", "10%", $pageNum, "asocia_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Asociar Documento");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['id_modo_gasto']) {
			case 1 : $modoGasto = "Gastos"; break;
			case 2 : $modoGasto = "Otros Cargos"; break;
			case 3 : $modoGasto = "Gastos por Importación"; break;
			default : $modoGasto = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
				$row['id_gasto']);
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['iva_compra'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['iva_venta'])."</td>";
			$htmlTb .= "<td align=\"center\">".($modoGasto)."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['afecta_documento'] == 1) ? "Si" : "-")."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['asocia_documento'] == 1) ? "Si" : "-")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaGasto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("divListaGasto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaImpuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iva.estado = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iva.tipo IN (1,2,3,6)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(observacion LIKE %s
		OR tipo_impuesto LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		iva.idIva,
		iva.iva,
		iva.observacion,
		tipo_imp.tipo_impuesto,
		iva.estado,
		iva.activo
	FROM pg_iva iva
		INNER JOIN pg_tipo_impuesto tipo_imp ON (iva.tipo = tipo_imp.id_tipo_impuesto) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "8%", $pageNum, "idIva", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "24%", $pageNum, "tipo_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "44%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "12%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "12%", $pageNum, "activo", $campOrd, $tpOrd, $valBusq, $maxRows, "Predeterminado");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$activo = ($row['activo'] == 1) ? "SI" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarImpuesto%s\" onclick=\"validarInsertarImpuesto('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['idIva']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['idIva']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['tipo_impuesto'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['iva'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($activo)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaImpuesto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarModo");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"buscarImpuesto");
$xajax->register(XAJAX_FUNCTION,"eliminarGasto");
$xajax->register(XAJAX_FUNCTION,"eliminarGastoImpuesto");
$xajax->register(XAJAX_FUNCTION,"eliminarGastoLote");
$xajax->register(XAJAX_FUNCTION,"formGasto");
$xajax->register(XAJAX_FUNCTION,"guardarGasto");
$xajax->register(XAJAX_FUNCTION,"insertarImpuesto");
$xajax->register(XAJAX_FUNCTION,"listaGasto");
$xajax->register(XAJAX_FUNCTION,"listaImpuesto");

function insertarItemImpuesto($contFila, $hddIdGastoImpuesto = "", $idImpuesto = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// BUSCA LOS DATOS DEL TIPO DE ORDEN
	$query = sprintf("SELECT
		iva.idIva,
		iva.iva,
		iva.observacion,
		tipo_imp.tipo_impuesto,
		iva.estado,
		iva.activo
	FROM pg_iva iva
		INNER JOIN pg_tipo_impuesto tipo_imp ON (iva.tipo = tipo_imp.id_tipo_impuesto)
	WHERE iva.idIva = %s;",
		valTpDato($idImpuesto, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieImpuesto').before('".
		"<tr id=\"trItmImpuesto:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmImpuesto:%s\"><input id=\"cbxItmImpuesto\" name=\"cbxItmImpuesto[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxImpuesto\" name=\"cbxImpuesto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"right\">%s".
				"<input type=\"hidden\" id=\"hddIdGastoImpuesto%s\" name=\"hddIdGastoImpuesto%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdImpuesto%s\" name=\"hddIdImpuesto%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['tipo_impuesto']),
			utf8_encode($row['observacion']),
			utf8_encode($row['iva']),
				$contFila, $contFila, $hddIdGastoImpuesto,
				$contFila, $contFila, $idImpuesto);
	
	return array(true, $htmlItmPie, $contFila);
}
?>