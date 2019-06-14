<?php


function asignarArticulo($idArticulo, $frmPrecioEspecialTipoOrden, $hddNumeroArt = "") {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmPrecioEspecialTipoOrden['txtIdEmpresa'];
	
	$objResponse->script("
	document.forms['frmDatosArticulo'].reset();");
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT *,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art.id_articulo
			AND kardex.tipo_movimiento IN (1)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_compra,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = vw_iv_art.id_articulo
			AND kardex.tipo_movimiento IN (3)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_venta
		
	FROM vw_iv_articulos vw_iv_art
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$objResponse->assign("hddIdArt","value",$rowArticulo['id_articulo']);
	$objResponse->assign("txtCodigoArt","value",elimCaracter($rowArticulo['codigo_articulo'],";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['tipo_articulo']));
	$objResponse->assign("txtFechaUltCompraArt","value",(($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtFechaUltVentaArt","value",(($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtCantDisponible","value",$rowArticulo['cantidad_disponible_logica']);
	
	if ($hddNumeroArt == "") { // NO EXISTE EL ARTICULO EN LA LISTA
		$objResponse->assign("hddNumeroArt","value","");
		
		// CARGA LOS PRECIOS DEL ARTICULO
		$queryArtPrecio = sprintf("SELECT * FROM vw_iv_articulos_precios
		WHERE id_articulo = %s
			AND id_empresa = %s
			AND estatus = 1
		ORDER BY porcentaje DESC;",
			valTpDato($rowArticulo['id_articulo'], "int"),
			valTpDato($idEmpresa, "int"));
		$rsArtPrecio = mysql_query($queryArtPrecio);
		if (!$rsArtPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowArtPrecio = mysql_fetch_assoc($rsArtPrecio)) {
			$html .= "<optgroup label=\"".utf8_encode($rowArtPrecio['descripcion_precio'])."\">";
				$selected = "";
				if ($selId == $rowArtPrecio['id_articulo_precio'] || $rowArtPrecio['id_precio'] == $predPrec) {
					$selected = "selected=\"selected\"";
					
					if ($rowArtPrecio['id_precio'] == $predPrec)
						$valorSelecPred = $rowArtPrecio['id_articulo_precio'];
				}
				
				$html .= "<option ".$selected." value=\"".$rowArtPrecio['id_precio']."\">".$rowArtPrecio['precio']."</option>";
			$html .= "</optgroup>";
		}
		$htmlLstIni = "<select id=\"lstPrecioArt\" name=\"lstPrecioArt\" class=\"inputHabilitado\" style=\"width:200px\">";
			$htmlLstIni .= "<option value=\"-1\">[ Seleccione ]</option>";
		$htmlLstFin = "</select>";
		
		$objResponse->assign("tdlstPrecioArt","innerHTML",$htmlLstIni.$html.$htmlLstFin);
		
		$objResponse->script("
		if (byId('hddNumeroArt').value > 0) {
			byId('btnInsertarArticulo').click();
		} else {
			byId('lstPrecioArt').focus();
			byId('lstPrecioArt').select();
		}");
	}
	
	return $objResponse;
}

function asignarEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_empresa_reg = %s", valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$objResponse->assign("txtIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	
	$nombreSucursal = "";
	if ($rowEmpresa['id_empresa_padre_suc'] > 0)
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";
	
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal));
	
	return $objResponse;
}

function buscarArticulo($frmBuscarArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$frmTotalDcto['hddObj']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $frmBuscarArticulo['hddCantCodigo']; $cont++) {
		$codArticulo .= $frmBuscarArticulo['txtCodigoArticulo'.$cont].";";
	}
	$auxCodArticulo = $codArticulo;
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
	if (strlen($frmDcto['txtIdEmpresa']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("id_empresa = %s",
			valTpDato($frmDcto['txtIdEmpresa'], "int"));
	}
	
	if ($auxCodArticulo != "---") {
		if ($codArticulo != "-1" && $codArticulo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
				valTpDato($codArticulo, "text"));
		}
	}
	
	if (strlen($frmBuscarArticulo['txtCriterioBuscarArticulo']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($frmBuscarArticulo['lstBuscarArticulo']) {
			case 1 : $sqlBusq .= $cond.sprintf("marca LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 2 : $sqlBusq .= $cond.sprintf("tipo_articulo LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 3 : $sqlBusq .= $cond.sprintf("descripcion_seccion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 4 : $sqlBusq .= $cond.sprintf("descripcion_subseccion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 5 : $sqlBusq .= $cond.sprintf("descripcion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 6 : $sqlBusq .= $cond.sprintf("id_articulo = %s", valTpDato($frmBuscarArticulo['txtCriterioBuscarArticulo'], "int")); break;
			case 7 : $sqlBusq .= $cond.sprintf("codigo_articulo_prov LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
		}
	}
		
	$objResponse->assign("divListaArticulo","innerHTML","");
	
	if ($auxCodArticulo != "---" || strlen($frmBuscarArticulo['txtCriterioBuscarArticulo']) > 0) {
		$query = sprintf("SELECT id_articulo FROM vw_iv_articulos_empresa_datos_basicos %s", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
		if ($totalRows == 1) {
			$row = mysql_fetch_assoc($rs);
			
			// VERIFICA SI ALGUN ARTICULO YA ESTA INCLUIDO EN EL DOCUMENTO
			$existe = false;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					if ($frmListaArticulo['hddIdArt'.$valor] == $row['id_articulo']) {
						$objResponse->script(sprintf("xajax_asignarArticulo('%s', xajax.getFormValues('frmPrecioEspecialTipoOrden'), '%s')",
							$row['id_articulo'],
							$valor));
						$existe = true;
					}
				}
			}
			
			if ($existe == false) {
				$objResponse->script(sprintf("xajax_asignarArticulo('%s', xajax.getFormValues('frmPrecioEspecialTipoOrden'))",
					$row['id_articulo']));
			}
			
			$objResponse->script("byId('txtCriterioBuscarArticulo').value = '';");
		} else if ($totalRows > 1) {
			$valBusq = sprintf("%s|%s|%s|%s",
						$frmDcto['txtIdEmpresa'],
						$codArticulo,
						$frmBuscarArticulo['lstBuscarArticulo'],
						$frmBuscarArticulo['txtCriterioBuscarArticulo']);
			
			$objResponse->loadCommands(listaArticulo(0, "id_articulo", "DESC", $valBusq));
		} else {
			$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
			$htmlTb .= "<td colspan=\"11\">";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTblFin .= "</table>";
			
			$objResponse->assign("divListaArticulo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		}
	}
	
	return $objResponse;	
}

function buscarArticuloLote($frmBuscar, $frmPrecioEspecialTipoOrden) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s",
		$frmPrecioEspecialTipoOrden['txtIdEmpresa'],
		$frmBuscar['lstTipoArticulo'],
		$codArticulo);
	
	$objResponse->loadCommands(listaArticuloLote(0, "id_articulo", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarPrecioEspecialTipoOrden($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPrecioEspecialTipoOrden(0, "id_tipo_orden", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstPrecios($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.id_precio NOT IN (6,7) AND precio.estatus = 1 ORDER BY precio.id_precio ASC;");
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
		$selected = ($selId == $rowPrecio['id_precio']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$rowPrecio['id_precio']."\" ".$selected.">".utf8_encode($rowPrecio['descripcion_precio'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoArticulo\" name=\"lstTipoArticulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscarArticuloLote').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$row['id_tipo_articulo']."\" ".$selected.">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function cargarPrecioEspecialTipoOrden($nomObjeto, $idTipoOrden, $frmPrecioEspecialTipoOrden) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$frmPrecioEspecialTipoOrden['hddObj']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
						
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
	}
	
	if (xvalidaAcceso($objResponse,"iv_precios_especiales_tipo_orden_list","editar")) {
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		$objResponse->script("
		document.forms['frmPrecioEspecialTipoOrden'].reset();
		
		byId('txtCodigoArt').className = 'inputInicial';
		byId('lstPrecioArt').className = 'inputHabilitado';");
		
		// BUSCA LOS DATOS DEL TIPO DE ORDEN
		$query = sprintf("SELECT * FROM sa_tipo_orden
		WHERE id_tipo_orden = %s;",
			valTpDato($idTipoOrden, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->loadCommands(asignarEmpresa($row['id_empresa']));
		$objResponse->assign("hddIdTipoOrden","value",$row['id_tipo_orden']);
		$objResponse->assign("txtIdTipoOrden","value",$row['id_tipo_orden']);
		$objResponse->assign("txtDescripcionTipoOrden","value",$row['nombre_tipo_orden']);
		$objResponse->loadCommands(cargaLstPrecios("lstPrecio", $row['id_precio_repuesto']));
		
		$query = sprintf("SELECT * FROM iv_articulos_precios_tipo_orden art_precios_tipo_orden
			INNER JOIN iv_articulos art ON (art_precios_tipo_orden.id_articulo = art.id_articulo)
			INNER JOIN pg_precios precio ON (art_precios_tipo_orden.id_precio = precio.id_precio)
		WHERE art_precios_tipo_orden.id_tipo_orden = %s;",
			valTpDato($idTipoOrden, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$sigValor = 1;
		$arrayObj = NULL;
		while ($row = mysql_fetch_array($rs)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			// INSERTA EL ARTICULO MEDIANTE INJECT
			$objResponse->script(sprintf("$('#trItmPie').before('".
				"<tr id=\"trItm:%s\" class=\"textoGris_11px %s\" title=\"trItm:%s\">".
					"<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/></td>".
					"<td align=\"left\">%s</td>".
					"<td align=\"left\">%s</td>".
					"<td align=\"center\">%s".
						"<input type=\"hidden\" id=\"hddIdArticulosPreciosTipoOrden%s\" name=\"hddIdArticulosPreciosTipoOrden%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArt%s\" name=\"hddIdArt%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdPrecio%s\" name=\"hddIdPrecio%s\" readonly=\"readonly\" value=\"%s\"/></td>".
				"</tr>');",
				$sigValor, $clase, $sigValor,
					$sigValor,
					elimCaracter($row['codigo_articulo'],";"),
					preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($row['descripcion']))),
					utf8_encode($row['descripcion_precio']),
						$sigValor, $sigValor, $row['id_articulos_precios_tipo_orden'],
						$sigValor, $sigValor, $row['id_articulo'],
						$sigValor, $sigValor, $row['id_precio']));
			
			$arrayObj[] = $sigValor;
			$sigValor++;
		}
		
		$cadena = "";
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$cadena .= "|".$valor;
			}
		}
		$objResponse->assign("hddObj","value",$cadena);
	}
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Precios Especiales por Tipo de Orden");
	$objResponse->script("
	byId('txtIdMonedaExtranjera').focus();
	byId('txtIdMonedaExtranjera').select();");
	
	return $objResponse;
}

function eliminarArticulo($frmPrecioEspecialTipoOrden) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmPrecioEspecialTipoOrden['cbxItm'])) {
		foreach ($frmPrecioEspecialTipoOrden['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
		$objResponse->script("xajax_eliminarArticulo(xajax.getFormValues('frmPrecioEspecialTipoOrden'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$frmPrecioEspecialTipoOrden['hddObj']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$cadena = "";
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmPrecioEspecialTipoOrden['hddIdArt'.$valor] != "")
				$cadena .= "|".$valor;
		}
	}
	$objResponse->assign("hddObj","value",$cadena);
		
	return $objResponse;
}

function formArticulo($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	openImg(byId('".$nomObjeto."'));");
	
	$objResponse->loadCommands(objetoCodigoDinamico("tdCodigoArt", $_SESSION['idEmpresaUsuarioSysGts']));
	
	$objResponse->script("
	byId('tblArticulo').style.display = '';
	byId('tblArticuloLote').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Listado de Artículos");
	$objResponse->script("
	byId('txtCriterioBuscarArticulo').focus();
	byId('txtCriterioBuscarArticulo').select();");
		
	return $objResponse;
}

function formArticuloLote($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	openImg(byId('".$nomObjeto."'));");
	
	$objResponse->script("
	document.forms['frmBuscarArticuloLote'].reset();
	document.forms['frmListaArticuloLote'].reset();");
	
	$idTipoOrden = $frmPrecioEspecialTipoOrden['txtIdTipoOrden'];
	
	// BUSCA LOS DATOS DEL TIPO DE ORDEN
	$query = sprintf("SELECT * FROM sa_tipo_orden
	WHERE id_tipo_orden = %s;",
		valTpDato($idTipoOrden, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->loadCommands(cargaLstTipoArticulo());
	$objResponse->loadCommands(cargaLstPrecios("lstPrecioArtLote"));
	$objResponse->script("byId('btnBuscarArticuloLote').click();");
	
	$objResponse->script("
	byId('tblArticulo').style.display = 'none';
	byId('tblArticuloLote').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Listado de Artículos");
	$objResponse->script("
	byId('lstTipoArticulo').focus();
	byId('lstTipoArticulo').select();");
		
	return $objResponse;
}

function guardarPrecioEspecialTipoOrden($frmPrecioEspecialTipoOrden, $frmListaPrecioEspecialTipoOrden) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$frmPrecioEspecialTipoOrden['hddObj']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	mysql_query("START TRANSACTION;");
	
	if ($frmPrecioEspecialTipoOrden['txtIdTipoOrden'] > 0) {
		if (xvalidaAcceso($objResponse,"iv_precios_especiales_tipo_orden_list","editar")) {
			$updateSQL = sprintf("UPDATE sa_tipo_orden SET
				id_precio_repuesto = %s
			WHERE id_tipo_orden = %s;",
				valTpDato($frmPrecioEspecialTipoOrden['lstPrecio'], "int"),
				valTpDato($frmPrecioEspecialTipoOrden['txtIdTipoOrden'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) {
				if (mysql_errno() == 1062) {
					return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
				} else {
					return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
			mysql_query("SET NAMES 'latin1';");
			
			$deleteSQL = sprintf("DELETE FROM iv_articulos_precios_tipo_orden
			WHERE id_tipo_orden = %s;",
				valTpDato($frmPrecioEspecialTipoOrden['txtIdTipoOrden'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			if (strlen($frmPrecioEspecialTipoOrden['hddObj'])) {
				if (isset($arrayObj)) {
					foreach ($arrayObj as $indice => $valor) {
						if (strlen($frmPrecioEspecialTipoOrden['hddIdArt'.$valor]) > 0) {
							$insert = sprintf("INSERT INTO iv_articulos_precios_tipo_orden (id_tipo_orden, id_precio, id_articulo) 
							VALUES (%s, %s, %s);",
								$frmPrecioEspecialTipoOrden['txtIdTipoOrden'],
								$frmPrecioEspecialTipoOrden['hddIdPrecio'.$valor],
								$frmPrecioEspecialTipoOrden['hddIdArt'.$valor]);
							$Result1 = mysql_query($insert);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						}
					}
				}
			}
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Precio Especial Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listaPrecioEspecialTipoOrden(
		$frmListaPrecioEspecialTipoOrden['pageNum'],
		$frmListaPrecioEspecialTipoOrden['campOrd'],
		$frmListaPrecioEspecialTipoOrden['tpOrd'],
		$frmListaPrecioEspecialTipoOrden['valBusq']));
	
	return $objResponse;
}

function insertarArticulo($frmDatosArticulo, $frmPrecioEspecialTipoOrden) {
	$objResponse = new xajaxResponse();
	
	$hddNumeroArt = $frmDatosArticulo['hddNumeroArt'];
	
	if ($hddNumeroArt == "") {
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayVal = explode("|",$frmPrecioEspecialTipoOrden['hddObj']);
		foreach ($arrayVal as $indice => $valor) {
			if ($valor > 0)
				$arrayObj[] = $valor;
		}
		
		$sigValor = $arrayObj[count($arrayObj)-1];
	
		$existe = false;
		if (strlen($frmPrecioEspecialTipoOrden['hddObj'])) { 
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					if ($frmPrecioEspecialTipoOrden['hddIdArt'.$valor] == $frmDatosArticulo['hddIdArt']) {
						$existe = true;
					}
				}
			}
		}
		
		if ($existe == false) {
			$clase = (fmod($sigValor, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$sigValor++;
			
			$idArticulo = $frmDatosArticulo['hddIdArt'];
			$idPrecio = $frmDatosArticulo['lstPrecioArt'];
			
			// BUSCA LOS DATOS DEL ARTICULO
			$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos vw_iv_art
			WHERE id_articulo = %s;",
				valTpDato($idArticulo, "int"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowArticulo = mysql_fetch_assoc($rsArticulo);
				
			// BUSCA LOS DATOS DEL PRECIO
			$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.id_precio = %s;",
				valTpDato($idPrecio, "int"));
			$rsPrecio = mysql_query($queryPrecio);
			if (!$rsPrecio) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowPrecio = mysql_fetch_array($rsPrecio);
			
			// INSERTA EL ARTICULO SIN INJECT
			$objResponse->script(sprintf("$('#trItmPie').before('".
				"<tr id=\"trItm:%s\" class=\"textoGris_11px %s\">".
					"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/></td>".
					"<td align=\"left\">%s</td>".
					"<td align=\"left\">%s</td>".
					"<td align=\"center\">%s".
						"<input type=\"hidden\" id=\"hddIdArticulosPreciosTipoOrden%s\" name=\"hddIdArticulosPreciosTipoOrden%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdArt%s\" name=\"hddIdArt%s\" readonly=\"readonly\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdPrecio%s\" name=\"hddIdPrecio%s\" readonly=\"readonly\" value=\"%s\"/></td>".
				"</tr>');",
				$sigValor, $clase,
					$sigValor, $sigValor,
					elimCaracter($rowArticulo['codigo_articulo'],";"),
					preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($rowArticulo['descripcion']))),
					utf8_encode($rowPrecio['descripcion_precio']),
						$sigValor, $sigValor, "",
						$sigValor, $sigValor, $idArticulo,
						$sigValor, $sigValor, $idPrecio));
		
			$arrayObj[] = $sigValor;
			$sigValor++;
			
			$cadena = "";
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					$cadena .= "|".$valor;
				}
			}
			$objResponse->assign("hddObj","value",$cadena);
			
			
			$objResponse->script("
			if (byId('hddNumeroArt').value > 0) {
			} else {
				document.forms['frmDatosArticulo'].reset();
				byId('txtDescripcionArt').innerHTML = '';
				
				if (inArray(byId('lstBuscarArticulo').value, [6,7])) {
					byId('txtCriterioBuscarArticulo').focus();
					byId('txtCriterioBuscarArticulo').select();
				} else {
					document.forms['frmBuscarArticulo'].reset();
					byId('txtCodigoArticulo0').focus();
					byId('txtCodigoArticulo0').select();
				}
			}");
			
			$objResponse->assign("divListadoArticulos","innerHTML","");
		} else {
			$objResponse->alert('El Artículo ya esta en la lista');
		}
	}
	
	return $objResponse;
}

function insertarArticuloLote($frmListaArticuloLote, $frmPrecioEspecialTipoOrden) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$frmPrecioEspecialTipoOrden['hddObj']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$sigValor = $arrayObj[count($arrayObj)-1];

	$existe = false;
	if (strlen($frmPrecioEspecialTipoOrden['hddObj'])) { 
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if (isset($frmListaArticuloLote['cbxItm'])) {
					foreach ($frmListaArticuloLote['cbxItm'] as $indiceItm => $valorItm) {
						if ($frmPrecioEspecialTipoOrden['hddIdArt'.$valor] == $valorItm) {
							$contSinCosto++;
							
							// BUSCA LOS DATOS DEL ARTICULO
							$query = sprintf("SELECT * FROM vw_iv_articulos_empresa_datos_basicos
							WHERE id_articulo = %s;",
								valTpDato($valorItm, "int"));
							$rs = mysql_query($query);
							if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							$totalRows = mysql_num_rows($rs);
							$row = mysql_fetch_assoc($rs);
							
							$msjArticulo .= ($msjArticulo != "") ? "": "El(Los) registro(s):\n";
							$msjArticulo .= ($contSinCosto % 4 == 1) ? "\n" : "";
							
							$msjArticulo .= str_pad("(".elimCaracter($row['codigo_articulo'],";").")", 30, " ", STR_PAD_RIGHT);
							
							$existe = true;
						}
					}
				}
			}
		}
	}
	
	if ($existe == false) {
		if (isset($frmListaArticuloLote['cbxItm'])) {
			foreach ($frmListaArticuloLote['cbxItm'] as $indiceItm => $valorItm) {
				$clase = (fmod($sigValor, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$sigValor++;
				
				$idArticulo = $valorItm;
				$idPrecio = $frmListaArticuloLote['lstPrecioArtLote'];
				
				// BUSCA LOS DATOS DEL ARTICULO
				$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos vw_iv_art
				WHERE id_articulo = %s;",
					valTpDato($idArticulo, "int"));
				$rsArticulo = mysql_query($queryArticulo);
				if (!$rsArticulo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArticulo = mysql_fetch_assoc($rsArticulo);
					
				// BUSCA LOS DATOS DEL PRECIO
				$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.id_precio = %s;",
					valTpDato($idPrecio, "int"));
				$rsPrecio = mysql_query($queryPrecio);
				if (!$rsPrecio) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowPrecio = mysql_fetch_array($rsPrecio);
				
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("$('#trItmPie').before('".
					"<tr id=\"trItm:%s\" class=\"textoGris_11px %s\">".
						"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/></td>".
						"<td align=\"left\">%s</td>".
						"<td align=\"left\">%s</td>".
						"<td align=\"center\">%s".
							"<input type=\"hidden\" id=\"hddIdArticulosPreciosTipoOrden%s\" name=\"hddIdArticulosPreciosTipoOrden%s\" readonly=\"readonly\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddIdArt%s\" name=\"hddIdArt%s\" readonly=\"readonly\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddIdPrecio%s\" name=\"hddIdPrecio%s\" readonly=\"readonly\" value=\"%s\"/></td>".
					"</tr>');",
					$sigValor, $clase,
						$sigValor, $sigValor,
						elimCaracter($rowArticulo['codigo_articulo'],";"),
						preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($rowArticulo['descripcion']))),
						utf8_encode($rowPrecio['descripcion_precio']),
							$sigValor, $sigValor, "",
							$sigValor, $sigValor, $idArticulo,
							$sigValor, $sigValor, $idPrecio));
			
				$arrayObj[] = $sigValor;
				
				$cadena = "";
				if (isset($arrayObj)) {
					foreach ($arrayObj as $indice => $valor) {
						$cadena .= "|".$valor;
					}
				}
				$objResponse->assign("hddObj","value",$cadena);
			}
		}
	} else {
		$msjArticulo .= "\n\nya se encuentra(n) incluido(s)";
		
		return $objResponse->alert(utf8_encode($msjArticulo));
	}
	
	return $objResponse;
}

function listaArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("art_emp.estatus = 1");
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if (strlen($valCadBusq[3]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($valCadBusq[2]) {
			case 1 : 
				$sqlBusq .= $cond.sprintf("(SELECT marca.marca FROM iv_marcas marca
				WHERE marca.id_marca = art.id_marca) LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
				break;
			case 2 : 
				$sqlBusq .= $cond.sprintf("(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
				WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
				break;
			case 3 : 
				$sqlBusq .= $cond.sprintf("(SELECT sec.descripcion
				FROM iv_subsecciones subsec
					INNER JOIN iv_secciones sec ON (subsec.id_seccion = sec.id_seccion)
				WHERE subsec.id_subseccion = vw_iv_articulos_empresa.id_subseccion) LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
				break;
			case 4 : 
				$sqlBusq .= $cond.sprintf("(SELECT subsec.descripcion
				FROM iv_subsecciones subsec
				WHERE subsec.id_subseccion = vw_iv_articulos_empresa.id_subseccion) LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text"));
				break;
			case 5 : $sqlBusq .= $cond.sprintf("art.descripcion LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text")); break;
			case 6 : $sqlBusq .= $cond.sprintf("art.id_articulo = %s", valTpDato($valCadBusq[3], "int")); break;
			case 7 : $sqlBusq .= $cond.sprintf("art.codigo_articulo_prov LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text")); break;
		}
	}
	
	$query = sprintf("SELECT art_emp.*,
		art.codigo_articulo,
		art.descripcion,
		art.codigo_articulo_prov,
	
		(SELECT marca.marca FROM iv_marcas marca
		WHERE marca.id_marca = art.id_marca) AS marca,
		
		(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
		WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) AS tipo_articulo,
		
		art_emp.clasificacion
	FROM iv_articulos_empresa art_emp
		INNER JOIN iv_articulos art ON (art_emp.id_articulo = art.id_articulo) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna texto_10px\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Código"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "70%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "16%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, ("Marca"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Clasif."));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= "<button type=\"submit\" onclick=\"xajax_asignarArticulo('".$row['id_articulo']."', xajax.getFormValues('frmPrecioEspecialTipoOrden'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca']." (".$row['tipo_articulo'].")")."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListadoArticulos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaArticuloLote($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tipo_articulo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	$query = sprintf("SELECT *,
		(SELECT marca.marca FROM iv_marcas marca
		WHERE marca.id_marca = vw_iv_articulos_empresa.id_marca) AS marca,
		
		(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
		WHERE tipo_art.id_tipo_articulo = vw_iv_articulos_empresa.id_tipo_articulo) AS tipo_articulo
	FROM vw_iv_articulos_empresa %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna texto_10px\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,6);\"/></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticuloLote", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Código"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloLote", "68%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloLote", "16%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, ("Marca"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloLote", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Clasif."));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
				$row['id_articulo']);
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca']." (".$row['tipo_articulo'].")")."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloLote(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloLote(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticuloLote(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloLote(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloLote(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticuloLote","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	return $objResponse;
}

function listaPrecioEspecialTipoOrden($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(tipo_orden.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = tipo_orden.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(tipo_orden.nombre_tipo_orden LIKE %s
		OR precios.descripcion_precio LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		tipo_orden.id_tipo_orden,
		tipo_orden.nombre_tipo_orden,
		
		(SELECT COUNT(art_precios_tipo_orden.id_articulo)
		FROM iv_articulos_precios_tipo_orden art_precios_tipo_orden
		WHERE art_precios_tipo_orden.id_tipo_orden = tipo_orden.id_tipo_orden) AS cant_rep_precio_especial,
		
		precios.id_precio AS id_precio_repuestos,
		precios.descripcion_precio AS descripcion_precio_repuestos,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM sa_tipo_orden tipo_orden
		INNER JOIN pg_precios precios ON (tipo_orden.id_precio_repuesto = precios.id_precio)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (tipo_orden.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaPrecioEspecialTipoOrden", "6%", $pageNum, "id_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaPrecioEspecialTipoOrden", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPrecioEspecialTipoOrden", "40%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");
		$htmlTh .= ordenarCampo("xajax_listaPrecioEspecialTipoOrden", "20%", $pageNum, "cant_rep_precio_especial", $campOrd, $tpOrd, $valBusq, $maxRows, "Cant. Repuestos con Precio Especial");
		$htmlTh .= ordenarCampo("xajax_listaPrecioEspecialTipoOrden", "20%", $pageNum, "descripcion_precio_repuestos", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Especial General");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".($row['id_tipo_orden'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['cant_rep_precio_especial'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_precio_repuestos'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_cargarPrecioEspecialTipoOrden(this.id,'%s',xajax.getFormValues('frmPrecioEspecialTipoOrden'));\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_tipo_orden']);
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioEspecialTipoOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioEspecialTipoOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPrecioEspecialTipoOrden(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioEspecialTipoOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecioEspecialTipoOrden(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPrecioEspecialTipoOrden","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarArticuloLote");
$xajax->register(XAJAX_FUNCTION,"buscarPrecioEspecialTipoOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstPrecios");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"cargarPrecioEspecialTipoOrden");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"formArticulo");
$xajax->register(XAJAX_FUNCTION,"formArticuloLote");
$xajax->register(XAJAX_FUNCTION,"guardarPrecioEspecialTipoOrden");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarArticuloLote");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaArticuloLote");
$xajax->register(XAJAX_FUNCTION,"listaPrecioEspecialTipoOrden");
?>