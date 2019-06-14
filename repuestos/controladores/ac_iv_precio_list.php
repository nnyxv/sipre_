<?php

function abrirPrecioLote(){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_precio_list","editar")) { return $objResponse; }
	
	$objResponse->script("window.open('extras/iv_revalorar_inventario.php','_self');");	
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listaEmpresas(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarPrecio($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstEstatusBuscar']) ? implode(",",$frmBuscar['lstEstatusBuscar']) : $frmBuscar['lstEstatusBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPrecio(0, "id_precio", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstActualizarConCostoItm($nombreObjeto, $selId = "") {
	$array = array(0 => "No", 1 => "Si");
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputCompleto\">";
	if (isset($array)) {
		foreach ($array as $indice => $valor) {
			$selected = ($selId == $indice) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$indice."\">".htmlentities($valor)."</option>";
		}
	}
	$html .= "</select>";
	
	return $html;
}

function eliminarPrecio($idPrecio, $frmListaPrecio) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_precio_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM pg_precios WHERE id_precio = %s",
		valTpDato($idPrecio, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->loadCommands(listaPrecio(
		$frmListaPrecio['pageNum'],
		$frmListaPrecio['campOrd'],
		$frmListaPrecio['tpOrd'],
		$frmListaPrecio['valBusq']));
	
	return $objResponse;
}

function eliminarEmpresaPrecio($frmPrecio) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmPrecio['cbxItm'])) {
		foreach($frmPrecio['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
		$objResponse->script("xajax_eliminarEmpresaPrecio(xajax.getFormValues('frmPrecio'));");
	}
	
	return $objResponse;
}

function formPrecio($idPrecio, $frmPrecio) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmPrecio['cbx'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
	}
	
	if ($idPrecio > 0) {
		if (!xvalidaAcceso($objResponse,"iv_precio_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarPrecio').click();"); return $objResponse; }
		
		$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.id_precio = %s;",
			valTpDato($idPrecio, "int"));
		$rsPrecio = mysql_query($queryPrecio);
		if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowPrecio = mysql_fetch_assoc($rsPrecio);
		
		$objResponse->assign("hddIdPrecio","value",$rowPrecio['id_precio']);
		$objResponse->assign("txtDescripcion","value",utf8_encode($rowPrecio['descripcion_precio']));
		$objResponse->assign("txtPorcentaje","value",utf8_encode($rowPrecio['porcentaje']));
		$objResponse->call("selectedOption","lstTipoPrecio",$rowPrecio['tipo']);
		$objResponse->call("selectedOption","lstTipoCosto",$rowPrecio['tipo_costo']);
		$objResponse->call("selectedOption","lstEstatus",$rowPrecio['estatus']);
		
		$objResponse->script((($rowPrecio['estatus'] == 2) ? "byId('optReservado').disabled = false;" : "byId('optReservado').disabled = true;"));
		$objResponse->script("
		byId('lstEstatus').onchange = function() {
			".(($rowPrecio['estatus'] == 2) ? "selectedOption(this.id,'".$rowPrecio['estatus']."');" : "")."
		}");
		
		
		$queryEmpresaPrecios = sprintf("SELECT * FROM pg_empresa_precios emp_precios
		WHERE id_precio = %s
		ORDER BY id_empresa ASC;",
			valTpDato($idPrecio, "int"));
		$rsEmpresaPrecios = mysql_query($queryEmpresaPrecios);
		if (!$rsEmpresaPrecios) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj = NULL;
		while ($rowEmpresaPrecios = mysql_fetch_assoc($rsEmpresaPrecios)) {
			$Result1 = insertarItemEmpresaPrecio($contFila, $rowEmpresaPrecios['id_empresa_precio']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
	} else {
		if (!xvalidaAcceso($objResponse,"iv_precio_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarPrecio').click();"); return $objResponse; }
			
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
		$objResponse->script("byId('optReservado').disabled = true;");
	}
	
	return $objResponse;
}

function guardarPrecio($frmPrecio, $frmListaPrecio) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmPrecio['cbx'];
	
	mysql_query("START TRANSACTION;");
	
	$idPrecio = $frmPrecio['hddIdPrecio'];
	
	if ($idPrecio > 0) {
		if (!xvalidaAcceso($objResponse,"iv_precio_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_precios SET
			descripcion_precio = %s,
			porcentaje = %s,
			tipo = %s,
			tipo_costo = %s,
			estatus = %s
		WHERE id_precio = %s;",
			valTpDato($frmPrecio['txtDescripcion'], "text"),
			valTpDato($frmPrecio['txtPorcentaje'], "real_inglesa"),
			valTpDato($frmPrecio['lstTipoPrecio'], "int"),
			valTpDato($frmPrecio['lstTipoCosto'], "int"),
			valTpDato($frmPrecio['lstEstatus'], "int"),
			valTpDato($idPrecio, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$updateSQL); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_precio_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_precios (descripcion_precio, porcentaje, tipo, tipo_costo, estatus)
		VALUE (%s, %s, %s, %s, %s);",
			valTpDato($frmPrecio['txtDescripcion'], "text"),
			valTpDato($frmPrecio['txtPorcentaje'], "real_inglesa"),
			valTpDato($frmPrecio['lstTipoPrecio'], "int"),
			valTpDato($frmPrecio['lstTipoCosto'], "int"),
			valTpDato($frmPrecio['lstEstatus'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idPrecio = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	if ($frmPrecio['lstEstatus'] == 0) {
		$query = sprintf("SELECT * FROM iv_articulos art
		WHERE art.id_precio_predeterminado = %s;",
			valTpDato($idPrecio, "int"));
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
		while ($row = mysql_fetch_assoc($rs)) {
			$arrayArticulosPrecioPredet[] = $row['codigo_articulo'];
		}
		
		if ($totalRows > 0) {
			return $objResponse->alert(("Existe(n) en el sistema ".count($arrayArticulosPrecioPredet)." artículos con dicho precio como predeterminado:\n".implode("\n",$arrayArticulosPrecioPredet)));
		}
	}
	
	// VERIFICA SI LAS EMPRESAS ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryEmpresaPrecios = sprintf("SELECT * FROM pg_empresa_precios emp_precios
	WHERE id_precio = %s;",
		valTpDato($idPrecio, "int"));
	$rsEmpresaPrecios = mysql_query($queryEmpresaPrecios);
	if (!$rsEmpresaPrecios) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowEmpresaPrecios = mysql_fetch_assoc($rsEmpresaPrecios)) {
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if ($rowEmpresaPrecios['id_empresa_precio'] == $frmPrecio['hddIdEmpresaPrecio'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM pg_empresa_precios WHERE id_empresa_precio = %s;",
				valTpDato($rowEmpresaPrecios['id_empresa_precio'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA LAS EMPRESAS PARA EL PRECIO
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$idEmpresaPrecio = $frmPrecio['hddIdEmpresaPrecio'.$valor];
			$idEmpresa = $frmPrecio['hddIdEmpresa'.$valor];
			
			if ($idEmpresaPrecio > 0) {
				$updateSQL = sprintf("UPDATE pg_empresa_precios SET
					id_empresa = %s,
					id_precio = %s,
					actualizar_con_costo = %s
				WHERE id_empresa_precio = %s;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idPrecio, "int"),
					valTpDato($frmPrecio['lstActualizarConCosto'.$valor], "boolean"),
					valTpDato($idEmpresaPrecio, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			} else {
				$insertSQL = sprintf("INSERT INTO pg_empresa_precios (id_empresa, id_precio, actualizar_con_costo)
				VALUE (%s, %s, %s);",
					valTpDato($idEmpresa, "int"),
					valTpDato($idPrecio, "int"),
					valTpDato($frmPrecio['lstActualizarConCosto'.$valor], "boolean"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
			
			if ($frmPrecio['lstActualizarPrecio'.$valor] == 1) {
				// ACTUALIZA EL PRECIO DE VENTA
				$Result1 = actualizarPrecioVenta("", $idEmpresa, $idPrecio);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				$ejecutarMarkUp = true;
			}
			
			if ($frmPrecio['lstEjecutarAumento'.$valor] == 1) {
				$updateSQL = sprintf("UPDATE pg_empresa_precios SET
					porcentaje_aumento = %s,
					fecha_aumento = NOW()
				WHERE id_empresa_precio = %s;",
					valTpDato($frmPrecio['txtPorcentajeAumento'.$valor], "real_inglesa"),
					valTpDato($idEmpresaPrecio, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// ACTUALIZA EL PRECIO DE VENTA
				$Result1 = actualizarPrecioVenta("", $idEmpresa, $idPrecio, true);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				$ejecutarAumento = true;
			}
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Precio Guardado con Éxito");
	
	if ($ejecutarMarkUp == true) { $objResponse->alert("Precio de Artículos Actualizados con Éxito"); }
	if ($ejecutarAumento == true) { $objResponse->alert("Aumento de Precio de Artículos Ejecutado con Éxito"); }
	
	$objResponse->script("byId('btnCancelarPrecio').click();");
	
	$objResponse->loadCommands(listaPrecio(
		$frmListaPrecio['pageNum'],
		$frmListaPrecio['campOrd'],
		$frmListaPrecio['tpOrd'],
		$frmListaPrecio['valBusq']));
	
	return $objResponse;
}

function insertarEmpresaPrecio($idEmpresa, $frmPrecio) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmPrecio['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idEmpresa > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmPrecio['hddIdEmpresa'.$valor] == $idEmpresa) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemEmpresaPrecio($contFila, "", $idEmpresa);
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

function listaEmpresas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanRIF;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("id_empresa_reg <> 100");
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_empresa LIKE %s
		OR nombre_empresa_suc LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpresas", "10%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, ("Código"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresas", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanRIF));
		$htmlTh .= ordenarCampo("xajax_listaEmpresas", "35%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresas", "35%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, ("Sucursal"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarEmpresa%s\" onclick=\"validarInsertarEmpresa('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$contFila,
				$row['id_empresa_reg']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['rif'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".htmlentities($nombreSucursal)."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresas(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult.gif\"/>");
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
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPrecio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("precio.porcentaje NOT IN (0)");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("precio.estatus IN (%s)",
			valTpDato($valCadBusq[1], "campo"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("precio.descripcion_precio LIKE %s",
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT
		precio.id_precio,
		precio.descripcion_precio,
		precio.porcentaje,
		(CASE precio.tipo
			WHEN 0 THEN	'Sobre Costo'
			WHEN 1 THEN	'Sobre Venta'
		END) AS tipo,
		(CASE precio.tipo_costo
			WHEN 1 THEN	'Costo Reposición'
			WHEN 2 THEN	'Costo Promedio'
		END) AS tipo_costo,
		precio.estatus
	FROM pg_precios precio %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "8%", $pageNum, "id_precio", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "30%", $pageNum, "descripcion_precio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "10%", $pageNum, "porcentaje", $campOrd, $tpOrd, $valBusq, $maxRows, "Porcentaje");
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "12%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo");
		$htmlTh .= ordenarCampo("xajax_listadoClavesUsuarios", "12%", $pageNum, "tipo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Costo");
		$htmlTh .= "<td width=\"20%\">Empresa</td>";
		$htmlTh .= "<td width=\"8%\">Actualizar con Costo</td>";
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("precios.id_precio = %s",
			valTpDato($row['id_precio'], "int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("emp_precios.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
		}
		
		$queryEmpresaPrecio = sprintf("SELECT
			emp_precios.actualizar_con_costo,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM pg_precios precios
			INNER JOIN pg_empresa_precios emp_precios ON (precios.id_precio = emp_precios.id_precio)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (emp_precios.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s;", $sqlBusq2);
		$rsEmpresaPrecio = mysql_query($queryEmpresaPrecio);
		if (!$rsEmpresaPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayFactDet = NULL;
		while ($rowEmpresaPrecio = mysql_fetch_assoc($rsEmpresaPrecio)) {
			$arrayDet[0] = $rowEmpresaPrecio['nombre_empresa'];
			$arrayDet[1] = $rowEmpresaPrecio['actualizar_con_costo'];
			$arrayFactDet[] = $arrayDet;
		}
		
		switch ($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			case 2 : $imgEstatus = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Reservado\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".($row['id_precio'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_precio'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['porcentaje'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['tipo'])."</td>";
			$htmlTb .= "<td>".($row['tipo_costo'])."</td>";
			$htmlTb .= "<td>";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						$htmlTb .= "<tr>";
							$htmlTb .= "<td>".$arrayFactDet[$indice][0]."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">";
				if (isset($arrayFactDet)) {
					$htmlTb .= "<table>";
					foreach ($arrayFactDet as $indice => $valor) {
						$htmlTb .= "<tr align=\"center\">";
							$htmlTb .= "<td>".(($arrayFactDet[$indice][1] == 1) ? "Si" : "-")."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPrecio', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_precio']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus'] != 2) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/cross.png\"/>",
					$row['id_precio']);
			}
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
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

$xajax->register(XAJAX_FUNCTION,"abrirPrecioLote");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarPrecio");
$xajax->register(XAJAX_FUNCTION,"eliminarPrecio");
$xajax->register(XAJAX_FUNCTION,"eliminarEmpresaPrecio");
$xajax->register(XAJAX_FUNCTION,"formPrecio");
$xajax->register(XAJAX_FUNCTION,"guardarPrecio");
$xajax->register(XAJAX_FUNCTION,"insertarEmpresaPrecio");
$xajax->register(XAJAX_FUNCTION,"listaEmpresas");
$xajax->register(XAJAX_FUNCTION,"listaPrecio");

function insertarItemEmpresaPrecio($contFila, $idEmpresaPrecio = "", $idEmpresa = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idEmpresaPrecio > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryEmpresaPrecios = sprintf("SELECT * FROM pg_empresa_precios
		WHERE id_empresa_precio = %s;",
			valTpDato($idEmpresaPrecio, "int"));
		$rsEmpresaPrecios = mysql_query($queryEmpresaPrecios);
		if (!$rsEmpresaPrecios) return array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila, $arrayObjUbicacion);
		$totalRowsEmpresaPrecios = mysql_num_rows($rsEmpresaPrecios);
		$rowEmpresaPrecios = mysql_fetch_assoc($rsEmpresaPrecios);
	}
	
	$idEmpresa = ($idEmpresa == "" && $totalRowsEmpresaPrecios > 0) ? $rowEmpresaPrecios['id_empresa'] : $idEmpresa;
	$lstActualizarConCosto = ($lstActualizarConCosto == "" && $totalRowsEmpresaPrecios > 0) ? $rowEmpresaPrecios['actualizar_con_costo'] : $lstActualizarConCosto;
	$txtPorcentajeAumento = ($txtPorcentajeAumento == "" && $totalRowsEmpresaPrecios > 0) ? $rowEmpresaPrecios['porcentaje_aumento'] : $txtPorcentajeAumento;
	$txtFechaAumento = ($txtFechaAumento == "" && $totalRowsEmpresaPrecios > 0) ? $rowEmpresaPrecios['fecha_aumento'] : $txtFechaAumento;
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE id_empresa_reg = %s;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>"."<select id=\"lstActualizarPrecio%s\" name=\"lstActualizarPrecio%s\" class=\"inputCompleto\"><option value=\"0\">No</option><option value=\"1\">Si</option></select>"."</td>".
			"<td>"."<input type=\"text\" id=\"txtPorcentajeAumento%s\" name=\"txtPorcentajeAumento%s\" class=\"inputCompleto\" style=\"text-align:right\" value=\"%s\">"."</td>".
			"<td>"."<input type=\"text\" id=\"txtFechaAumento%s\" name=\"txtFechaAumento%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\">"."</td>".
			"<td>"."<select id=\"lstEjecutarAumento%s\" name=\"lstEjecutarAumento%s\" class=\"inputCompleto\"><option value=\"0\">No</option><option value=\"1\">Si</option></select>".
				"<input type=\"hidden\" id=\"hddIdEmpresaPrecio%s\" name=\"hddIdEmpresaPrecio%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdEmpresa%s\" name=\"hddIdEmpresa%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['nombre_empresa']),
			cargaLstActualizarConCostoItm("lstActualizarConCosto".$contFila, $lstActualizarConCosto),
			$contFila, $contFila,
			$contFila, $contFila, $txtPorcentajeAumento,
			$contFila, $contFila, $txtFechaAumento,
			$contFila, $contFila,
				$contFila, $contFila, $idEmpresaPrecio,
				$contFila, $contFila, $idEmpresa);
	
	return array(true, $htmlItmPie, $contFila);
}
?>