<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function activarArticulo($idArticulo, $frmListaArticulos, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","eliminar")) { return $objResponse; }
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	
	if (isset($idArticulo)) {
		mysql_query("START TRANSACTION;");
		
		// ACTIVA EL ARTICULO PARA LA EMPRESA
		$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
			id_casilla_predeterminada = NULL,
			id_casilla_predeterminada_compra = NULL,
			estatus = 1
		WHERE id_articulo = %s
			AND id_empresa = %s;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalAffectedRows = mysql_affected_rows();
		
		mysql_query("COMMIT;");
		
		($totalAffectedRows > 0) ? $objResponse->alert("Registro Activado con Éxito") : "";
		
		$objResponse->loadCommands(listaArticulo(
			$frmListaArticulos['pageNum'],
			$frmListaArticulos['campOrd'],
			$frmListaArticulos['tpOrd'],
			$frmListaArticulos['valBusq']));
	}
	
	return $objResponse;
}

function activarUbicacion($idArticuloAlmacen, $frmUbicacionArticulo) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","editar")) { return $objResponse; }
	
	$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
		estatus = 1
	WHERE id_articulo_almacen = %s;",
		valTpDato($idArticuloAlmacen, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$query = sprintf("SELECT * FROM iv_articulos_almacen WHERE id_articulo_almacen = %s;",
		valTpDato($idArticuloAlmacen, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$idArticulo = $row['id_articulo'];
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
	$Result1 = actualizarPedidas($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
	$Result1 = actualizarSaldos($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	$objResponse->script("xajax_activarArticulo('".$idArticulo."', xajax.getFormValues('frmListaArticulos'), xajax.getFormValues('frmBuscar'));");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Relacion Ubicación con Artículo Activada con Éxito");
	
	$objResponse->loadCommands(listaUbicacionArticulo(
		$frmUbicacionArticulo['pageNum'],
		$frmUbicacionArticulo['campOrd'],
		$frmUbicacionArticulo['tpOrd'],
		$frmUbicacionArticulo['valBusq']));
	
	return $objResponse;
}

function buscarArticulo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstEstatus']) ? implode(",",$frmBuscar['lstEstatus']) : $frmBuscar['lstEstatus']),
		(is_array($frmBuscar['lstModoCompra']) ? implode(",",$frmBuscar['lstModoCompra']) : $frmBuscar['lstModoCompra']),
		(is_array($frmBuscar['lstTipoArticulo']) ? implode(",",$frmBuscar['lstTipoArticulo']) : $frmBuscar['lstTipoArticulo']),
		(is_array($frmBuscar['lstVerClasificacion']) ? implode(",",$frmBuscar['lstVerClasificacion']) : $frmBuscar['lstVerClasificacion']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaArticulo(0, "id_articulo", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarModelo($frmBuscarModelo, $frmModeloArticulo) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmModeloArticulo['hddIdEmpresa'],
		$frmModeloArticulo['hddIdArticuloModeloArticulo'],
		$frmBuscarModelo['txtCriterioBuscarModelo']);
	
	$objResponse->loadCommands(listaModelo(0, "id_uni_bas", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstCalleEstanteTramoCasilla($idLstOrigen, $adjLst, $padreId = "", $selId = ""){
	$objResponse = new xajaxResponse();
	
	$arraySelec = array("lstPadre","lstAlmacen","lstCalle","lstEstante","lstTramo","lstCasilla");
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	// SI EL OBJETO NO ES EL ULTIMO ASIGNA LA FUNCION PARA CARGAR EL SIGUIENTE OBJETO
	if (($posList + 1) != count($arraySelec) - 1)
		$onChange = "onchange=\"xajax_cargaLstCalleEstanteTramoCasilla('".$arraySelec[$posList+1]."', '".$adjLst."', this.value);\"";
	/*else if (($posList + 1) == count($arraySelec) - 1)
		$onChange = "onchange=\"xajax_buscarDisponibilidadUbicacion(xajax.getFormValues('frmAlmacen'));\"";*/
	
	$html = "<select id=\"".$arraySelec[$posList+1].$adjLst."\" name=\"".$arraySelec[$posList+1].$adjLst."\" class=\"inputHabilitado\" ".$onChange.">";
	
	// SI EL VALOR DEL OBJETO ES IGUAL A SELECCIONE, LIMPIARA TODOS LOS OBJETOS SIGUIENTES
	if ($padreId == '-1') {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\" class=\"inputHabilitado\">";
					$html .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html .= "</select>";
				$objResponse->assign("td".$valor.$adjLst, 'innerHTML', $html);
			}
		}
		
		return $objResponse;
	} else {
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html2 = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\" class=\"inputHabilitado\">";
					$html2 .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html2 .= "</select>";
				$objResponse->assign("td".$valor.$adjLst, 'innerHTML', $html2);
			}
		}
		
		switch ($posList) {
			case 0 :
				$query = sprintf("SELECT
					almacen.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
						INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
						INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
						INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
					WHERE calle.id_almacen = almacen.id_almacen
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_almacenes almacen
				WHERE almacen.id_empresa = %s
					AND almacen.estatus = 1
				ORDER BY almacen.descripcion;",
					valTpDato($padreId, "int"));
				$campoId = "id_almacen";
				$campoDesc = "descripcion";
				break;
			case 1 :
				$query = sprintf("SELECT
					calle.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
						INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
						INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
					WHERE estante.id_calle = calle.id_calle
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_calles calle
				WHERE calle.id_almacen = %s
					AND calle.estatus = 1
				ORDER BY calle.descripcion_calle;",
					valTpDato($padreId, "int"));
				$campoId = "id_calle";
				$campoDesc = "descripcion_calle";
				break;
			case 2 :
				$query = sprintf("SELECT
					estante.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
						INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
					WHERE tramo.id_estante = estante.id_estante
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_estantes estante
				WHERE estante.id_calle = %s
					AND estante.estatus = 1
				ORDER BY estante.descripcion_estante;",
					valTpDato($padreId, "int"));
				$campoId = "id_estante";
				$campoDesc = "descripcion_estante";
				break;
			case 3 :
				$query = sprintf("SELECT
					tramo.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
					FROM iv_articulos_almacen art_alm
						INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
					WHERE casilla.id_tramo = tramo.id_tramo
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_tramos tramo
				WHERE tramo.id_estante = %s
					AND tramo.estatus = 1
				ORDER BY tramo.descripcion_tramo;",
					valTpDato($padreId, "int"));
				$campoId = "id_tramo";
				$campoDesc = "descripcion_tramo";
				break;
			case 4 :
				$query = sprintf("SELECT
					casilla.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
					WHERE art_alm.id_casilla = casilla.id_casilla
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_casillas casilla
				WHERE casilla.id_tramo = %s
					AND casilla.estatus = 1
				ORDER BY casilla.descripcion_casilla;",
					valTpDato($padreId, "int"));
				$campoId = "id_casilla";
				$campoDesc = "descripcion_casilla";
				break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row[$campoId]) ? "selected=\"selected\"" : "";
			
			$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
			$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
			$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
			
			$html .= "<option ".$selected." value=\"".$row[$campoId]."\" class=\"".$classUbic."\">".utf8_encode($row[$campoDesc].$ocupada)."</option>";
		}
	}
	$html .= "</select>";
	
	$objResponse->assign("td".$arraySelec[$posList+1].$adjLst, 'innerHTML', $html);
	
	return $objResponse;
}

function cargaLstClasificacion($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$array = array("A" => "A", "B" => "B", "C" => "C", "D" => "D", "E" => "E", "F" => "F");
	$totalRows = count($array);
	
	$html .= "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstVerClasificacion\" name=\"lstVerClasificacion\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstVerClasificacion","innerHTML", $html);
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"\"";
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html .= "<select ".(($totalRows > 1) ? "multiple" : "")."  id=\"lstTipoArticulo\" name=\"lstTipoArticulo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_articulo']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function eliminarArticulo($idArticulo, $frmListaArticulos, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","eliminar")) { return $objResponse; }
	
	if (isset($idArticulo)) {
		mysql_query("START TRANSACTION;");
		
		// VERIFICA SI TIENE REGISTROS EN KARDEX
		$queryKardex = sprintf("SELECT * FROM iv_kardex
		WHERE id_articulo = %s;",
			valTpDato($idArticulo, "int"));
		$rsKardex = mysql_query($queryKardex);
		if (!$rsKardex) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsKardex = mysql_num_rows($rsKardex);
		
		// VERIFICA SI TIENE REGISTROS EN ANALISIS DE INVENTARIO
		$queryAnalisisInvDet = sprintf("SELECT * FROM iv_analisis_inventario_detalle analisis_inv_det
		WHERE analisis_inv_det.id_articulo = %s;",
			valTpDato($idArticulo, "int"));
		$rsAnalisisInvDet = mysql_query($queryAnalisisInvDet);
		if (!$rsAnalisisInvDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsAnalisisInvDet = mysql_num_rows($rsAnalisisInvDet);
		
		// BUSCA LOS DATOS DEL ARTICULO
		$queryArticulo = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
			valTpDato($idArticulo, "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		if ($totalRowsKardex > 0 || $totalRowsAnalisisInvDet > 0) {
			if ($idEmpresa > 0) {
				// VERIFICA SI LA EMPRESA TIENE CANTIDADES PENDIENTES
				$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_empresa
				WHERE id_empresa = %s
					AND id_articulo = %s
					AND cantidad_futura = 0;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"));
				$rsArtEmp = mysql_query($queryArtEmp);
				if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
				$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
				
				if ($totalRowsArtEmp > 0) {
					// INACTIVA EL ARTICULO PARA LA EMPRESA
					$updateSQL = sprintf("UPDATE iv_articulos_empresa SET 
						estatus = 0
					WHERE id_articulo_empresa = %s;",
						valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					// INACTIVA LAS UBICACIONES QUE TIENE ASIGNADA EL ARTICULO
					$updateSQL = sprintf("UPDATE iv_articulos_almacen SET 
						estatus = NULL
					WHERE id_articulo_almacen IN (SELECT id_articulo_almacen FROM vw_iv_articulos_almacen
													WHERE id_empresa = %s
														AND id_articulo = %s);",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					$arrayArticuloInactivo[] = elimCaracter($rowArticulo['codigo_articulo'],";");
				} else {
					$arrayArticuloConSaldo[] = elimCaracter($rowArticulo['codigo_articulo'],";");
				}
			} else {
				$arrayArticuloNoEliminado[] = elimCaracter($rowArticulo['codigo_articulo'],";");
			}
		} else {
			$deleteSQL = sprintf("DELETE FROM iv_articulos WHERE id_articulo = %s",
				valTpDato($idArticulo, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$arrayArticuloEliminado[] = elimCaracter($rowArticulo['codigo_articulo'],";");
		}
		
		mysql_query("COMMIT;");
		
		if (count($arrayArticuloInactivo) > 0) {
			$objResponse->alert("El(Los) registro(s):\n\n".implode(", ",$arrayArticuloInactivo)."\n\nfue(ron) actualizado(s) como Inactivo. No puede(n) ser eliminado(s) debido a que tiene(n) otros registros dependientes.");
		}
		
		if (count($arrayArticuloConSaldo) > 0) {
			$objResponse->alert("El(Los) registro(s):\n\n".implode(", ",$arrayArticuloConSaldo)."\n\nno fue(ron) eliminado(s) debido a que tiene(n) saldos pendientes.");
		}
		
		if (count($arrayArticuloNoEliminado) > 0) {
			$objResponse->alert("El(Los) registro(s):\n\n".implode(", ",$arrayArticuloNoEliminado)."\n\nno fue(ron) eliminado(s) debido a que tiene(n) otros registros dependientes.");
		}
		
		if (count($arrayArticuloEliminado) > 0) {
			$objResponse->alert("El(Los) registro(s):\n\n".implode(", ",$arrayArticuloEliminado)."\n\nfue(ron) eliminado(s) con éxito.");
		}
		
		$objResponse->loadCommands(listaArticulo(
			$frmListaArticulos['pageNum'],
			$frmListaArticulos['campOrd'],
			$frmListaArticulos['tpOrd'],
			$frmListaArticulos['valBusq']));
	}
		
	return $objResponse;
}

function eliminarArticuloLote($frmListaArticulos, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","eliminar")) { return $objResponse; }
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
		
	if (isset($frmListaArticulos['cbxItm'])) {
		mysql_query("START TRANSACTION;");
		
		foreach ($frmListaArticulos['cbxItm'] as $indiceItm => $valorItm) {
			$idArticulo = $valorItm;
			
			// VERIFICA SI TIENE REGISTROS EN KARDEX
			$queryKardex = sprintf("SELECT * FROM iv_kardex
			WHERE id_articulo = %s;",
				valTpDato($idArticulo, "int"));
			$rsKardex = mysql_query($queryKardex);
			if (!$rsKardex) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsKardex = mysql_num_rows($rsKardex);
			
			// VERIFICA SI TIENE REGISTROS EN ANALISIS DE INVENTARIO
			$queryAnalisisInvDet = sprintf("SELECT * FROM iv_analisis_inventario_detalle analisis_inv_det
			WHERE analisis_inv_det.id_articulo = %s;",
				valTpDato($idArticulo, "int"));
			$rsAnalisisInvDet = mysql_query($queryAnalisisInvDet);
			if (!$rsAnalisisInvDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsAnalisisInvDet = mysql_num_rows($rsAnalisisInvDet);
			
			// BUSCA LOS DATOS DEL ARTICULO
			$queryArticulo = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
				valTpDato($idArticulo, "int"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArticulo = mysql_fetch_assoc($rsArticulo);
			
			if ($totalRowsKardex > 0 || $totalRowsAnalisisInvDet > 0) {
				if ($idEmpresa > 0) {
					// VERIFICA SI LA EMPRESA TIENE CANTIDADES PENDIENTES
					$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_empresa
					WHERE id_empresa = %s
						AND id_articulo = %s
						AND cantidad_futura = 0;",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"));
					$rsArtEmp = mysql_query($queryArtEmp);
					if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
					$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
					
					if ($totalRowsArtEmp > 0) {
						// INACTIVA EL ARTICULO PARA LA EMPRESA
						$updateSQL = sprintf("UPDATE iv_articulos_empresa SET 
							estatus = 0
						WHERE id_articulo_empresa = %s;",
							valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						// INACTIVA LAS UBICACIONES QUE TIENE ASIGNADA EL ARTICULO
						$updateSQL = sprintf("UPDATE iv_articulos_almacen SET 
							estatus = NULL
						WHERE id_articulo_almacen IN (SELECT id_articulo_almacen FROM vw_iv_articulos_almacen
														WHERE id_empresa = %s
															AND id_articulo = %s);",
							valTpDato($idEmpresa, "int"),
							valTpDato($idArticulo, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
						$arrayArticuloInactivo[] = elimCaracter($rowArticulo['codigo_articulo'],";");
					} else {
						$arrayArticuloConSaldo[] = elimCaracter($rowArticulo['codigo_articulo'],";");
					}
				} else {
					$arrayArticuloNoEliminado[] = elimCaracter($rowArticulo['codigo_articulo'],";");
				}
			} else {
				$deleteSQL = sprintf("DELETE FROM iv_articulos WHERE id_articulo = %s",
					valTpDato($idArticulo, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				$arrayArticuloEliminado[] = elimCaracter($rowArticulo['codigo_articulo'],";");
			}
		}
		
		mysql_query("COMMIT;");
		
		if (count($arrayArticuloInactivo) > 0) {
			$objResponse->alert("El(Los) registro(s):\n\n".implode(", ",$arrayArticuloInactivo)."\n\nfue(ron) actualizado(s) como Inactivo. No puede(n) ser eliminado(s) debido a que tiene(n) otros registros dependientes.");
		}
		
		if (count($arrayArticuloConSaldo) > 0) {
			$objResponse->alert("El(Los) registro(s):\n\n".implode(", ",$arrayArticuloConSaldo)."\n\nno fue(ron) eliminado(s) debido a que tiene(n) saldos pendientes.");
		}
		
		if (count($arrayArticuloNoEliminado) > 0) {
			$objResponse->alert("El(Los) registro(s):\n\n".implode(", ",$arrayArticuloNoEliminado)."\n\nno fue(ron) eliminado(s) debido a que tiene(n) otros registros dependientes.");
		}
		
		if (count($arrayArticuloEliminado) > 0) {
			$objResponse->alert("El(Los) registro(s):\n\n".implode(", ",$arrayArticuloEliminado)."\n\nfue(ron) eliminado(s) con éxito.");
		}
		
		$objResponse->loadCommands(listaArticulo(
			$frmListaArticulos['pageNum'],
			$frmListaArticulos['campOrd'],
			$frmListaArticulos['tpOrd'],
			$frmListaArticulos['valBusq']));
	}
		
	return $objResponse;
}

function eliminarModeloArticuloLote($frmModeloArticulo) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","editar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmModeloArticulo['cbxItm3'];
	
	mysql_query("START TRANSACTION;");

	if (isset($arrayObj3)) {
		foreach ($arrayObj3 as $indice => $valor) {
			$deleteSQL = sprintf("DELETE FROM iv_articulos_modelos_compatibles WHERE id_articulo_modelo_compatible = %s;",
				valTpDato($valor, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}

	mysql_query("COMMIT;");
	
	$objResponse->alert("Modelo(s) Compatible(s) Eliminado(s) con Éxito");
	
	$objResponse->loadCommands(listaModeloArticulo(
		$frmModeloArticulo['pageNum'],
		$frmModeloArticulo['campOrd'],
		$frmModeloArticulo['tpOrd'],
		$frmModeloArticulo['valBusq']));
		
	return $objResponse;
}

function eliminarUbicacionArticuloLote($frmUbicacionArticulo) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","editar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj4 = $frmUbicacionArticulo['cbxItm4'];
	
	mysql_query("START TRANSACTION;");

	if (isset($arrayObj4)) {
		foreach ($arrayObj4 as $indice => $valor) {
			// VERIFICA SI EL ARTICULO TIENE MOVIMIENTOS EN LA UBICACION
			$query = sprintf("SELECT * FROM iv_articulos_almacen
			WHERE id_articulo_almacen = %s
				AND (cantidad_entrada > 0 OR cantidad_salida > 0);",
				valTpDato($valor, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			// SI HAY MOVIMIENTOS DEL ARTICULO EN ESA UBICACION SOLO LE CAMBIA EL ESTADO A LA UBICACION
			if ($totalRows > 0) {
				$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
					id_casilla_predeterminada = NULL
				WHERE id_casilla_predeterminada = %s;",
					valTpDato($row['id_casilla'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
					id_casilla_predeterminada_compra = NULL
				WHERE id_casilla_predeterminada_compra = %s;",
					valTpDato($row['id_casilla'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
					estatus = NULL
				WHERE id_articulo_almacen = %s;",
					valTpDato($valor, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			} else {
				$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
					id_casilla_predeterminada = NULL
				WHERE id_casilla_predeterminada IN (SELECT art_alm.id_casilla FROM iv_articulos_almacen art_alm
													WHERE art_alm.id_articulo_almacen = %s);",
					valTpDato($valor, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
					id_casilla_predeterminada_compra = NULL
				WHERE id_casilla_predeterminada_compra IN (SELECT art_alm.id_casilla FROM iv_articulos_almacen art_alm
													WHERE art_alm.id_articulo_almacen = %s);",
					valTpDato($valor, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				$deleteSQL = sprintf("DELETE FROM iv_articulos_almacen_costo
				WHERE id_articulo_almacen = %s
					AND (cantidad_inicio = 0 OR cantidad_inicio IS NULL)
					AND (cantidad_entrada = 0 OR cantidad_entrada IS NULL)
					AND (cantidad_salida = 0 OR cantidad_salida IS NULL);",
					valTpDato($valor, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				$deleteSQL = sprintf("DELETE FROM iv_articulos_almacen WHERE id_articulo_almacen = %s;",
					valTpDato($valor, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
	}
	
	$idEmpresa = $frmUbicacionArticulo['hddIdEmpresaUbicacionArticulo'];
	$idArticulo = $frmUbicacionArticulo['hddIdArticuloUbicacionArticulo'];
	
	// ASIGNA LA PRIMERA CASILLA COMO PREDETERMINADA PARA LA VENTA SI NO TIENE ALGUNA
	$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
		id_casilla_predeterminada = (SELECT art_alm.id_casilla
									FROM iv_articulos_almacen art_alm
										INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
										INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
										INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
										INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
										INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
									WHERE alm.id_empresa = art_emp.id_empresa
										AND art_alm.id_articulo = art_emp.id_articulo
										AND art_alm.estatus = 1
										AND alm.estatus_almacen_venta = 1
									LIMIT 1),
		estatus = 1
	WHERE art_emp.id_empresa = %s
		AND art_emp.id_articulo = %s
		AND art_emp.id_casilla_predeterminada IS NULL;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ASIGNA LA PRIMERA CASILLA COMO PREDETERMINADA PARA LA COMPRA SI NO TIENE ALGUNA
	$updateSQL = sprintf("UPDATE iv_articulos_empresa art_emp SET
		id_casilla_predeterminada_compra = (SELECT art_alm.id_casilla
									FROM iv_articulos_almacen art_alm
										INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
										INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
										INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
										INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
										INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
									WHERE alm.id_empresa = art_emp.id_empresa
										AND art_alm.id_articulo = art_emp.id_articulo
										AND art_alm.estatus = 1
										AND alm.estatus_almacen_compra = 1
									LIMIT 1),
		estatus = 1
	WHERE art_emp.id_empresa = %s
		AND art_emp.id_articulo = %s
		AND art_emp.id_casilla_predeterminada_compra IS NULL;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");

	mysql_query("COMMIT;");
	
	$objResponse->alert("Ubicacion(es) Eliminada(s) con Éxito");
	
	$objResponse->loadCommands(listaUbicacionArticulo(
		$frmUbicacionArticulo['pageNum'],
		$frmUbicacionArticulo['campOrd'],
		$frmUbicacionArticulo['tpOrd'],
		$frmUbicacionArticulo['valBusq']));
	
	return $objResponse;
}

function exportarArticulos($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstEstatus']) ? implode(",",$frmBuscar['lstEstatus']) : $frmBuscar['lstEstatus']),
		(is_array($frmBuscar['lstModoCompra']) ? implode(",",$frmBuscar['lstModoCompra']) : $frmBuscar['lstModoCompra']),
		(is_array($frmBuscar['lstTipoArticulo']) ? implode(",",$frmBuscar['lstTipoArticulo']) : $frmBuscar['lstTipoArticulo']),
		(is_array($frmBuscar['lstVerClasificacion']) ? implode(",",$frmBuscar['lstVerClasificacion']) : $frmBuscar['lstVerClasificacion']),
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_articulo_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formArticulo($idArticulo, $frmArticulo, $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmArticulo['cbx2'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmArticulo['cbx3'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj2)) {
		foreach($arrayObj2 as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItmImpuesto:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj3)) {
		foreach($arrayObj3 as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItmImpuesto:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	return $objResponse;
}

function formAlmacen($frmUbicacionArticulo, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmUbicacionArticulo['hddIdEmpresaUbicacionArticulo'];
	
	$onChange = "onchange=\"selectedOption(this.id,'".$idEmpresa."'); xajax_cargaLstCalleEstanteTramoCasilla('lstPadre', 'Act', this.value);\"";
	$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, $onChange, "lstEmpresaAct"));
	$objResponse->loadCommands(cargaLstCalleEstanteTramoCasilla("lstPadre", "Act", $idEmpresa));
	
	return $objResponse;
}

function formDatosArticulo($idArticulo, $idEmpresa) {
	$objResponse = new xajaxResponse();
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
	$Result1 = actualizarSaldos($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// BUSQUEDA DEL ARTICULO POR EL ID
	$queryArticulo = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
	// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
	$imgFoto = (!file_exists($rowArticulo['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $rowArticulo['foto'];
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"398\">";
	$htmlTb .= "<tr align=\"center\">";
		$htmlTb .= "<td>"."<img id=\"imgArticulo\" src=\"".$imgFoto."\" width=\"220\"/>"."</td>";
	$htmlTb .= "</tr>";
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divDatosArtículo","innerHTML",$htmlTblIni.$htmlTb.$htmlTblFin);
	
	
	
	
	// CREA LA RELACION LOTE Y UBICACION DE AQUELLOS QUE NO LO TENGAN
	$updateSQL = sprintf("INSERT INTO iv_articulos_almacen_costo (id_articulo_almacen, id_articulo_costo, estatus)
	SELECT
		(SELECT art_alm.id_articulo_almacen FROM iv_articulos_almacen art_alm
		WHERE art_alm.id_articulo = kardex.id_articulo
			AND art_alm.id_casilla = kardex.id_casilla),
		kardex.id_articulo_costo,
		1
	FROM iv_kardex kardex
	WHERE id_articulo = %s
		AND id_articulo_almacen_costo IS NULL
		AND id_articulo_costo IS NOT NULL;;",
		valTpDato($idArticulo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA LAS RELACIONES DEPENDIENDO DEL LOTE Y LA UBICACION
	$updateSQL = sprintf("UPDATE iv_kardex kardex SET
		id_articulo_almacen_costo = (SELECT art_alm_costo.id_articulo_almacen_costo
									FROM iv_articulos_almacen art_alm
										INNER JOIN iv_articulos_almacen_costo art_alm_costo ON (art_alm.id_articulo_almacen = art_alm_costo.id_articulo_almacen)
										INNER JOIN iv_articulos_costos art_costo ON (art_alm_costo.id_articulo_costo = art_costo.id_articulo_costo)
									WHERE art_alm.id_casilla = kardex.id_casilla
										AND art_costo.id_articulo_costo = kardex.id_articulo_costo)
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$updateSQL = sprintf("UPDATE iv_movimiento_detalle SET
		id_articulo_almacen_costo = (SELECT kardex.id_articulo_almacen_costo FROM iv_kardex kardex
									WHERE kardex.id_kardex = iv_movimiento_detalle.id_kardex)
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$updateSQL = sprintf("UPDATE iv_pedido_venta_detalle ped_vent_det SET
		id_articulo_almacen_costo = (SELECT art_alm_costo.id_articulo_almacen_costo
									FROM iv_articulos_almacen art_alm
										INNER JOIN iv_articulos_almacen_costo art_alm_costo ON (art_alm.id_articulo_almacen = art_alm_costo.id_articulo_almacen)
									WHERE art_alm.id_articulo = ped_vent_det.id_articulo
										AND art_alm.id_casilla = ped_vent_det.id_casilla
										AND art_alm_costo.id_articulo_costo = ped_vent_det.id_articulo_costo)
	WHERE ped_vent_det.id_casilla IS NOT NULL
		AND ped_vent_det.id_articulo_almacen_costo IS NULL
		AND ped_vent_det.id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$updateSQL = sprintf("UPDATE sa_det_orden_articulo sa_det_orden_art
		INNER JOIN sa_det_solicitud_repuestos sa_det_sol_rep ON (sa_det_orden_art.id_det_orden_articulo = sa_det_sol_rep.id_det_orden_articulo) SET
		id_articulo_almacen_costo = (SELECT art_alm_costo.id_articulo_almacen_costo
									FROM iv_articulos_almacen art_alm
										INNER JOIN iv_articulos_almacen_costo art_alm_costo ON (art_alm.id_articulo_almacen = art_alm_costo.id_articulo_almacen)
									WHERE art_alm.id_articulo = sa_det_orden_art.id_articulo
										AND art_alm.id_casilla = sa_det_sol_rep.id_casilla
										AND art_alm_costo.id_articulo_costo = sa_det_orden_art.id_articulo_costo
										AND sa_det_sol_rep.id_estado_solicitud IN (1,2,3,4,5,6,10))
	WHERE sa_det_sol_rep.id_casilla IS NOT NULL
		AND sa_det_orden_art.id_articulo_almacen_costo IS NULL
		AND sa_det_orden_art.id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// BUSCA LAS UBICACIONES DEL ARTICULO
	/*$queryArtAlmacen = sprintf("SELECT * FROM iv_articulos_almacen 
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArtAlmacen = mysql_query($queryArtAlmacen);
	if (!$rsArtAlmacen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowArtAlmacen = mysql_fetch_assoc($rsArtAlmacen)) {
		// BUSCA LOS LOTES DEL ARTICULO
		$queryArtCosto = sprintf("SELECT * FROM iv_articulos_almacen 
		WHERE id_articulo = %s;",
			valTpDato($idArticulo, "int"));
		$rsArtCosto = mysql_query($queryArtCosto);
		if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
			$hddIdArticuloCosto = $rowArtCosto['id_articulo_costo'];
			
			// VERIFICA SI EL LOTE TIENE LA UBICACION ASIGNADA
			$queryArtAlmCosto = sprintf("SELECT *
			FROM iv_articulos_almacen art_almacen
				INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
			WHERE art_almacen.id_articulo = %s
				AND art_almacen.id_casilla = %s
				AND art_almacen_costo.id_articulo_costo = %s;",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($hddIdArticuloCosto, "int"));
			$rsArtAlmCosto = mysql_query($queryArtAlmCosto);
			if (!$rsArtAlmCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArtAlm = mysql_num_rows($rsArtAlmCosto);
			$rowArtAlmCosto = mysql_fetch_assoc($rsArtAlmCosto);
			
			$hddIdArticuloAlmacenCosto = $rowArtAlmCosto['id_articulo_almacen_costo'];
			
			if ($totalRowsArtAlm > 0) {
				// ACTUALIZA EL ESTATUS DE LA UBICACION DEL LOTE
				$updateSQL = sprintf("UPDATE iv_articulos_almacen_costo SET
					estatus = 1
				WHERE id_articulo_almacen_costo = %s;",
					valTpDato($hddIdArticuloAlmacenCosto, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			} else {
				// LE ASIGNA EL LOTE A LA UBICACION
				$insertSQL = sprintf("INSERT INTO iv_articulos_almacen_costo (id_articulo_almacen, id_articulo_costo, estatus)
				SELECT art_almacen.id_articulo_almacen, %s, 1 FROM iv_articulos_almacen art_almacen
				WHERE art_almacen.id_casilla = %s
					AND art_almacen.id_articulo = %s
					AND art_almacen.estatus = 1;",
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($idArticulo, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$hddIdArticuloAlmacenCosto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}*/
	
	return $objResponse;
}

function formModelo($frmModeloArticulo) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarModelo').click();"); return $objResponse; }
	
	$valBusq = sprintf("%s|%s",
		$frmModeloArticulo['hddIdEmpresa'],
		$frmModeloArticulo['hddIdArticuloModeloArticulo']);
	
	$objResponse->loadCommands(listaModelo(0, "", "", $valBusq));
	
	return $objResponse;
}

function formModeloArticulo($idArticulo, $idEmpresa) {
	$objResponse = new xajaxResponse();
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT art.*,
		
		(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad
		
	FROM iv_articulos art
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$objResponse->assign("hddIdArticuloModeloArticulo","value",$idArticulo);
	$objResponse->assign("hddIdEmpresa","value",$idEmpresa);
	
	$valBusq = sprintf("%s",
		$idArticulo);
	
	$objResponse->loadCommands(listaModeloArticulo(0, "", "", $valBusq));
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Modelos Compatibles (Artículo Código: ".elimCaracter($rowArticulo['codigo_articulo'],";")." - ".utf8_encode($rowArticulo['descripcion']).")");
	
	return $objResponse;
}

function formImportarArticulo() {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","insertar")) { $objResponse->script("byId('btnCancelarImportarArticulo').click();"); return $objResponse; }
	
	$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "EmpresaImportarArticulo", "ListaEmpresa"));
	
	return $objResponse;
}

function formSaldos($idEmpresa, $idArticulo) {
	$objResponse = new xajaxResponse();
		
	$objResponse->script("byId('fieldsetListaDcto').style.display = 'none';");
		
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","")) { $objResponse->script("$('#btnCancelarListaDcto').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT art.*,
		
		(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad
		
	FROM iv_articulos art
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$objResponse->assign("hddIdArtVentaPerdida","value",$idArticulo);
	$objResponse->assign("txtCodigoArtVentaPerdida","value",elimCaracter($rowArticulo['codigo_articulo'],";"));
	$objResponse->assign("txtUnidadArtVentaPerdida","value",utf8_encode($rowArticulo['unidad']));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$queryDispUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_articulo = %s
		AND ((estatus_articulo_almacen = 1)
			OR (((estatus_articulo_almacen IS NULL AND (cantidad_pedida > 0 OR existencia > 0)) OR estatus_articulo_almacen = 1) %s)
			OR (id_casilla IS NOT NULL AND (estatus_articulo_almacen IS NULL AND cantidad_pedida > 0)));",
		valTpDato($idArticulo, "int"),
		$sqlBusq);
	$rsDispUbic = mysql_query($queryDispUbic);
	if (!$rsDispUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsDispUbic = mysql_num_rows($rsDispUbic);
	while ($rowDispUbic = mysql_fetch_assoc($rsDispUbic)) {
		if ($totalRowsDispUbic > 0
		&& ($rowDispUbic['id_casilla'] > 0 || $rowDispUbic['cantidad_reservada'] > 0 || $rowDispUbic['cantidad_pedida'] > 0)) {
			// VERIFICA SI ALGUN ARTICULO TIENE LA UBICACION OCUPADA
			$queryCasilla = sprintf("SELECT
				casilla.*,
				
				(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
				WHERE art_alm.id_casilla = casilla.id_casilla
					AND art_alm.estatus = 1) AS cantidad_ocupada
			FROM iv_casillas casilla
			WHERE casilla.id_casilla = %s;",
				valTpDato($rowDispUbic['id_casilla'], "int"));
			$rsCasilla = mysql_query($queryCasilla);
			if (!$rsCasilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsCasilla = mysql_num_rows($rsCasilla);
			$rowCasilla = mysql_fetch_assoc($rsCasilla);
			
			$arrayAlmacen[$rowDispUbic['id_almacen']]['id_almacen'] = $rowDispUbic['id_almacen'];
			$arrayAlmacen[$rowDispUbic['id_almacen']]['descripcion_almacen'] = $rowDispUbic['descripcion_almacen'];
			$arrayAlmacen[$rowDispUbic['id_almacen']]['estatus_almacen'] = $rowDispUbic['estatus_almacen'];
			$arrayAlmacen[$rowDispUbic['id_almacen']]['estatus_almacen_venta'] = $rowDispUbic['estatus_almacen_venta'];
			$arrayAlmacen[$rowDispUbic['id_almacen']]['estatus_almacen_compra'] = $rowDispUbic['estatus_almacen_compra'];
			$arrayAlmacen[$rowDispUbic['id_almacen']]['detalle_almacen'][] = array(
				"id_casilla" => $rowDispUbic['id_casilla'],
				"ubicacion" => $rowDispUbic['ubicacion'],
				"cantidad_ocupada" => $rowCasilla['cantidad_ocupada'],
				"estatus_casilla" => $rowCasilla['estatus'],
				"casilla_predeterminada" => $rowDispUbic['casilla_predeterminada'],
				"casilla_predeterminada_compra" => $rowDispUbic['casilla_predeterminada_compra'],
				"existencia" => $rowDispUbic['existencia'],
				"cantidad_reservada" => $rowDispUbic['cantidad_reservada'],
				"cantidad_espera" => $rowDispUbic['cantidad_espera'],
				"cantidad_bloqueada" => $rowDispUbic['cantidad_bloqueada'],
				"cantidad_disponible_logica" => $rowDispUbic['cantidad_disponible_logica'],
				"cantidad_pedida" => $rowDispUbic['cantidad_pedida'],
				"cantidad_futura" => $rowDispUbic['cantidad_futura'],
				"estatus_articulo_almacen" => $rowDispUbic['estatus_articulo_almacen']);
		}
	}
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td align=\"right\" class=\"tituloCampo\">"."Ult. Compra:"."</td>";
		$htmlTh .= "<td colspan=\"2\">".(($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx")."</td>";
		$htmlTh .= "<td align=\"right\" class=\"tituloCampo\">"."Ult. Venta:"."</td>";
		$htmlTh .= "<td colspan=\"2\">".(($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx")."</td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\"></td>";
		$htmlTh .= "<td width=\"10%\">"."Saldo"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Reservada (Serv.)"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Espera por Facturar"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Bloqueada"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Unid. Disponible"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Pedida a Proveedor"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Futura"."</td>";
	$htmlTh .= "</tr>";
	if (isset($arrayAlmacen)) {
		foreach ($arrayAlmacen as $indiceAlmacen => $valorAlmacen) {
			$arrayDetalleAlmacen = $valorAlmacen['detalle_almacen'];
			
			$htmlTb .= "<tr class=\"tituloCampo\">";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr align=\"left\" ".(($valorAlmacen['estatus_almacen'] == 1) ? "" : "class=\"trResaltarRojo\"").">";
						$htmlTb .= "<td width=\"100%\"><b>".$valorAlmacen['descripcion_almacen']."</b></td>";
						$htmlTb .= ($valorAlmacen['estatus_almacen_venta'] == 1) ? "<td><span class=\"textoAzulNegrita_10px\">[Venta]</span><td>" : "";
						$htmlTb .= ($valorAlmacen['estatus_almacen_compra'] == 1) ? "<td><span class=\"textoVerdeNegrita_10px\">[Compra]</span><td>" : "";
						$htmlTb .= "</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td colspan=\"7\"></td>";
			$htmlTb .= "</tr>";
			
			if (isset($arrayDetalleAlmacen)) {
				$contFila = 0;
				foreach ($arrayDetalleAlmacen as $indiceDetalleAlmacen => $valorDetalleAlmacen) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$imgEstatusArticuloAlmacen = ($valorDetalleAlmacen['estatus_articulo_almacen'] == 1) ? "" : "<span class=\"textoRojoNegrita_10px\">Relacion Inactiva</span>";
					
					$classUbic = "";
					//$classUbic = ($valorDetalleAlmacen['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
					$classUbic = ($valorDetalleAlmacen['estatus_casilla'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
					//$ocupada = ($valorDetalleAlmacen['cantidad_ocupada'] > 0) ? "*" : "";
					
					$ubicacion = $valorDetalleAlmacen['ubicacion'];
					
					$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
						$htmlTb .= "<td class=\"".$classUbic."\" nowrap=\"nowrap\">";
							$htmlTb .= "<table width=\"100%\">";
							$htmlTb .= "<tr>";
								$htmlTb .= "<td align=\"left\" width=\"100%\">";
									$htmlTb .= "&nbsp;&nbsp;&nbsp;&nbsp;".utf8_encode(str_replace("-[]", "", $ubicacion));
								$htmlTb .= "</td>";
								$htmlTb .= "<td>";
									$htmlTb .= ($valorDetalleAlmacen['casilla_predeterminada'] == 1 && $valorDetalleAlmacen['id_casilla'] > 0) ? "<img src=\"../img/iconos/aprob_mecanico.png\" title=\"Ubicación Predeterminada para Venta\"/>" : "";
									$htmlTb .= ($valorDetalleAlmacen['casilla_predeterminada_compra'] == 1 && $valorDetalleAlmacen['id_casilla'] > 0) ? "&nbsp;<img src=\"../img/iconos/aprob_control_calidad.png\" title=\"Ubicación Predeterminada para Compra\"/>" : "";
									$htmlTb .= $imgEstatusArticuloAlmacen;
								$htmlTb .= "</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format($valorDetalleAlmacen['existencia'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"right\">";
							$htmlTb .= "<table width=\"100%\">";
							$htmlTb .= "<tr align=\"right\">";
								$htmlTb .= "<td>";
								if ($valorDetalleAlmacen['cantidad_reservada'] > 0) {
									$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_listaSolicitudServicio(0,0,'','%s|%s|%s');\" src=\"../img/iconos/ico_view.png\"/>",
										$idEmpresa,
										$idArticulo,
										$valorDetalleAlmacen['id_casilla']);
								}
								$htmlTb .= "</td>";
								$htmlTb .= "<td width=\"100%\">".valTpDato(number_format($valorDetalleAlmacen['cantidad_reservada'], 2, ".", ","),"cero_por_vacio")."</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
						$htmlTb .= "<td align=\"right\">";
							$htmlTb .= "<table width=\"100%\">";
							$htmlTb .= "<tr align=\"right\">";
								$htmlTb .= "<td>";
								if ($valorDetalleAlmacen['cantidad_espera'] > 0) {
									$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_listaPedidoVenta(0,'','','%s|%s|%s');\" src=\"../img/iconos/ico_view.png\"/>",
										$idEmpresa,
										$idArticulo,
										$valorDetalleAlmacen['id_casilla']);
								}
								$htmlTb .= "</td>";
								$htmlTb .= "<td width=\"100%\">".valTpDato(number_format($valorDetalleAlmacen['cantidad_espera'], 2, ".", ","),"cero_por_vacio")."</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
						$htmlTb .= "<td align=\"right\">";
							$htmlTb .= "<table width=\"100%\">";
							$htmlTb .= "<tr align=\"right\">";
								$htmlTb .= "<td>";
								if ($valorDetalleAlmacen['cantidad_bloqueada'] > 0) {
									$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_listaBloqueoVenta(0,0,'','%s|%s|%s');\" src=\"../img/iconos/ico_view.png\"/>",
										$idEmpresa,
										$idArticulo,
										$valorDetalleAlmacen['id_casilla']);
								}
								$htmlTb .= "</td>";
								$htmlTb .= "<td width=\"100%\">".valTpDato(number_format($valorDetalleAlmacen['cantidad_bloqueada'], 2, ".", ","),"cero_por_vacio")."</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
						$htmlTb .= "<td align=\"right\" ".(($valorAlmacen['estatus_almacen_venta'] == 1 && $valorDetalleAlmacen['cantidad_disponible_logica'] > 0) ? "class=\"divMsjInfo\"" : "").">";
							$htmlTb .= valTpDato(number_format($valorDetalleAlmacen['cantidad_disponible_logica'], 2, ".", ","),"cero_por_vacio");
						$htmlTb .= "</td>";
						$htmlTb .= "<td align=\"right\">";
							$htmlTb .= "<table width=\"100%\">";
							$htmlTb .= "<tr align=\"right\">";
								$htmlTb .= "<td>";
								if ($valorDetalleAlmacen['cantidad_pedida'] > 0) {
									$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_listaPedidoCompra(0,'','','%s|%s');\" src=\"../img/iconos/ico_view.png\"/>",
										$idEmpresa,
										$idArticulo);
								}
								$htmlTb .= "</td>";
								$htmlTb .= "<td width=\"100%\">".valTpDato(number_format($valorDetalleAlmacen['cantidad_pedida'], 2, ".", ","),"cero_por_vacio")."</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format($valorDetalleAlmacen['cantidad_futura'], 2, ".", ","),"cero_por_vacio")."</td>";
					$htmlTb .= "</tr>";
				}
			}
		}
	} else {
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	$html .= "</table>";
	
	/*$query = sprintf("SELECT id_almacen, descripcion_almacen FROM vw_iv_articulos_almacen
	WHERE id_articulo = %s
		AND (estatus_articulo_almacen = 1 OR estatus_articulo_almacen IS NULL) %s
	GROUP BY id_almacen, descripcion_almacen",
		valTpDato($idArticulo, "int"),
		$sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\"></td>";
		$htmlTh .= "<td width=\"10%\">"."Saldo"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Reservada (Serv.)"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Espera por Facturar"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Bloqueada"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Unid. Disponible"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Pedida a Proveedor"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Futura"."</td>";
	$htmlTh .= "</tr>";
	while ($row = mysql_fetch_assoc($rs)) {
		// BUSCA LAS UBICACIONES DEL ARTICULO EN EL ALMACEN
		$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_almacen
		WHERE (id_almacen = %s OR (id_almacen IS NULL AND %s IS NULL))
			AND id_articulo = %s
			AND (estatus_articulo_almacen = 1 OR estatus_articulo_almacen IS NULL) %s",
			valTpDato($row['id_almacen'], "int"),
			valTpDato($row['id_almacen'], "int"),
			valTpDato($idArticulo, "int"),
			$sqlBusq);
		$rsUbic = mysql_query($queryUbic);
		if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila = 0;
		while ($rowUbic = mysql_fetch_assoc($rsUbic)) {
			$queryDispUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
			WHERE id_articulo = %s
				AND ((id_casilla = %s AND estatus_articulo_almacen = 1)
					OR ((id_casilla = %s OR (id_casilla IS NULL AND %s IS NULL))
						AND ((estatus_articulo_almacen IS NULL AND (cantidad_pedida > 0 OR existencia > 0)) OR estatus_articulo_almacen = 1) %s)
					OR (id_casilla IS NOT NULL AND (estatus_articulo_almacen IS NULL AND cantidad_pedida > 0)))
				AND id_articulo_almacen = %s;",
				valTpDato($idArticulo, "int"),
				valTpDato($rowUbic['id_casilla'], "int"),
				valTpDato($rowUbic['id_casilla'], "int"),
				valTpDato($rowUbic['id_casilla'], "int"),
				$sqlBusq,
				valTpDato($rowUbic['id_articulo_almacen'], "int"));
			$rsDispUbic = mysql_query($queryDispUbic);
			if (!$rsDispUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsDispUbic = mysql_num_rows($rsDispUbic);
			$rowDispUbic = mysql_fetch_assoc($rsDispUbic);
			
			$ubicacion = $rowDispUbic['ubicacion'];
			
			if ($totalRowsDispUbic > 0
			&& ($rowDispUbic['id_casilla'] > 0 || $rowDispUbic['cantidad_reservada'] > 0 || $rowDispUbic['cantidad_pedida'] > 0)) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
			
				if ($contFila == 1) {
					$htmlTb .= "<tr class=\"tituloCampo\">";
						$htmlTb .= "<td>";
							$htmlTb .= "<table width=\"100%\">";
							$htmlTb .= ($rowDispUbic['estatus_almacen'] == 1) ? "<tr align=\"left\">" : "<tr align=\"left\" class=\"trResaltarRojo\">";
								$htmlTb .= "<td width=\"100%\"><b>".$row['descripcion_almacen']."</b></td>";
								$htmlTb .= ($rowDispUbic['estatus_almacen_venta'] == 1) ? "<td><span class=\"textoAzulNegrita_10px\">[Venta]</span><td>" : "";
								$htmlTb .= ($rowDispUbic['estatus_almacen_compra'] == 1) ? "<td><span class=\"textoVerdeNegrita_10px\">[Compra]</span><td>" : "";
								$htmlTb .= "</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
						$htmlTb .= "<td colspan=\"7\"></td>";
					$htmlTb .= "</tr>";
				}
				
				// VERIFICA SI ALGUN ARTICULO TIENE LA UBICACION OCUPADA
				$queryCasilla = sprintf("SELECT
					casilla.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
					WHERE art_alm.id_casilla = casilla.id_casilla
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_casillas casilla
				WHERE casilla.id_casilla = %s;",
					valTpDato($rowDispUbic['id_casilla'], "int"));
				$rsCasilla = mysql_query($queryCasilla);
				if (!$rsCasilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsCasilla = mysql_num_rows($rsCasilla);
				$rowCasilla = mysql_fetch_assoc($rsCasilla);
			
				$imgEstatusArticuloAlmacen = ($rowDispUbic['estatus_articulo_almacen'] == 1) ? "" : "<span class=\"textoRojoNegrita_10px\">Relacion Inactiva</span>";
				
				$classUbic = "";
				//$classUbic = ($rowCasilla['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
				$classUbic = ($rowCasilla['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
				//$ocupada = ($rowCasilla['cantidad_ocupada'] > 0) ? "*" : "";
				
				$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
					$htmlTb .= "<td class=\"".$classUbic."\" nowrap=\"nowrap\">";
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr>";
							$htmlTb .= "<td align=\"left\" width=\"100%\">";
								$htmlTb .= "&nbsp;&nbsp;&nbsp;&nbsp;".utf8_encode(str_replace("-[]", "", $ubicacion));
							$htmlTb .= "</td>";
							$htmlTb .= "<td>";
								$htmlTb .= ($rowDispUbic['casilla_predeterminada'] == 1 && $rowDispUbic['id_casilla'] > 0) ? "<img src=\"../img/iconos/aprob_mecanico.png\" title=\"Ubicación Predeterminada para Venta\"/>" : "";
								$htmlTb .= ($rowDispUbic['casilla_predeterminada_compra'] == 1 && $rowDispUbic['id_casilla'] > 0) ? "&nbsp;<img src=\"../img/iconos/aprob_control_calidad.png\" title=\"Ubicación Predeterminada para Compra\"/>" : "";
								$htmlTb .= $imgEstatusArticuloAlmacen;
							$htmlTb .= "</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td align=\"right\">".valTpDato(number_format($rowDispUbic['existencia'], 2, ".", ","),"cero_por_vacio")."</td>";
					$htmlTb .= "<td>";
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr align=\"right\">";
						if ($rowDispUbic['cantidad_reservada'] > 0) {
							$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_listaSolicitudServicio(0,0,'','%s|%s|%s');\" src=\"../img/iconos/ico_view.png\"/></td>",
								$idEmpresa,
								$idArticulo,
								$rowUbic['id_casilla']);
						}
							$htmlTb .= "<td width=\"100%\">".valTpDato(number_format($rowDispUbic['cantidad_reservada'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td>";
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr align=\"right\">";
						if ($rowDispUbic['cantidad_espera'] > 0) {
							$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_listaPedidoVenta(0,'','','%s|%s|%s');\" src=\"../img/iconos/ico_view.png\"/></td>",
								$idEmpresa,
								$idArticulo,
								$rowUbic['id_casilla']);
						}
							$htmlTb .= "<td width=\"100%\">".valTpDato(number_format($rowDispUbic['cantidad_espera'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td align=\"right\">";
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr align=\"right\">";
						if ($rowDispUbic['cantidad_bloqueada'] > 0) {
							$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_listaBloqueoVenta(0,0,'','%s|%s|%s');\" src=\"../img/iconos/ico_view.png\"/></td>",
								$idEmpresa,
								$idArticulo,
								$rowUbic['id_casilla']);
						}
							$htmlTb .= "<td width=\"100%\">".valTpDato(number_format($rowDispUbic['cantidad_bloqueada'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= ($rowDispUbic['estatus_almacen_venta'] == 1 && $rowDispUbic['cantidad_disponible_logica'] > 0) ? "<td align=\"right\" class=\"divMsjInfo\">" : "<td align=\"right\">";
						$htmlTb .= valTpDato(number_format($rowDispUbic['cantidad_disponible_logica'], 2, ".", ","),"cero_por_vacio");
					$htmlTb .= "</td>";
					$htmlTb .= "<td>";
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr align=\"right\">";
						if ($rowDispUbic['cantidad_pedida'] > 0) {
							$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_listaPedidoCompra(0,'','','%s|%s');\" src=\"../img/iconos/ico_view.png\"/></td>",
								$idEmpresa,
								$idArticulo);
						}
							$htmlTb .= "<td width=\"100%\">".valTpDato(number_format($rowDispUbic['cantidad_pedida'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td align=\"right\">".valTpDato(number_format($rowDispUbic['cantidad_futura'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "</tr>";
			}
		}
	}
	$htmlTblFin .= "</table>";

	if (!($totalRows > 0) && !($totalRowsDispUbic > 0)) {
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}*/

	$objResponse->assign("divUbicacionesSaldos","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Saldos (Artículo Código: ".elimCaracter($rowArticulo['codigo_articulo'],";")." - ".utf8_encode($rowArticulo['descripcion']).")");
	
	return $objResponse;
}

function formUbicacionArticulo($idArticulo, $idEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarUbicacionArticulo').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT art.*,
		
		(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad
		
	FROM iv_articulos art
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$idEmpresa = ($idEmpresa > 0) ? $idEmpresa : $_SESSION['idEmpresaUsuarioSysGts'];
	
	$objResponse->assign("hddIdEmpresaUbicacionArticulo","value",$idEmpresa);
	$objResponse->assign("hddIdArticuloUbicacionArticulo","value",$idArticulo);
	
	$valBusq = sprintf("%s|%s",
		$idEmpresa,
		$idArticulo);
	
	$objResponse->loadCommands(listaUbicacionArticulo(0, "orden_prioridad_venta", "ASC", $valBusq));
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Ubicaciones (Artículo Código: ".elimCaracter($rowArticulo['codigo_articulo'],";")." - ".utf8_encode($rowArticulo['descripcion']).")");
	
	return $objResponse;
}

function guardarAccion($idArticuloAlmacen, $accionUbicacionArticulo, $frmUbicacionArticulo, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_articulos_almacen art_alm
	WHERE art_alm.id_articulo_almacen = %s;",
		valTpDato($idArticuloAlmacen, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$idArticulo = $row['id_articulo'];
	$idCasilla = $row['id_casilla'];
	$ordenPrioridadVenta = $row['orden_prioridad_venta'];
	
	$query = sprintf("SELECT alm.id_empresa
	FROM iv_calles calle
		INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
		INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
		INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
		INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
	WHERE casilla.id_casilla = %s;",
		valTpDato($idCasilla, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	
	switch ($accionUbicacionArticulo) {
		case "subir_orden" :
			$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
				orden_prioridad_venta = orden_prioridad_venta - 1
			WHERE orden_prioridad_venta = (%s + 1)
				AND estatus = 1
				AND (SELECT alm.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
						INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
					WHERE casilla.id_casilla = iv_articulos_almacen.id_casilla) = %s;",
				valTpDato($ordenPrioridadVenta, "int"),
				valTpDato($idEmpresa, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
				orden_prioridad_venta = orden_prioridad_venta + 1
			WHERE id_articulo_almacen = %s;",
				valTpDato($idArticuloAlmacen, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$objResponse->loadCommands(listaUbicacionArticulo(
				$frmUbicacionArticulo['pageNum'],
				$frmUbicacionArticulo['campOrd'],
				$frmUbicacionArticulo['tpOrd'],
				$frmUbicacionArticulo['valBusq']));
			
			break; 
		case "bajar_orden" :
			$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
				orden_prioridad_venta = orden_prioridad_venta + 1
			WHERE orden_prioridad_venta = (%s - 1)
				AND estatus = 1
				AND (SELECT alm.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
						INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
					WHERE casilla.id_casilla = iv_articulos_almacen.id_casilla) = %s;",
				valTpDato($ordenPrioridadVenta, "int"),
				valTpDato($idEmpresa, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
				orden_prioridad_venta = orden_prioridad_venta - 1
			WHERE id_articulo_almacen = %s;",
				valTpDato($idArticuloAlmacen, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$objResponse->loadCommands(listaUbicacionArticulo(
				$frmUbicacionArticulo['pageNum'],
				$frmUbicacionArticulo['campOrd'],
				$frmUbicacionArticulo['tpOrd'],
				$frmUbicacionArticulo['valBusq']));
			
			break;
		case "predeterminado_compra" :
			// RESETEA LA CASILLA PREDETERMINADA
			$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
				id_casilla_predeterminada_compra = NULL
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// ACTIVA LA UBICACION PARA EL ARTICULO
			$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
				estatus = 1
			WHERE id_articulo = %s
				AND id_casilla = %s",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// ASIGNA LA CASILLA PREDETERMINADA
			$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
				id_casilla_predeterminada_compra = %s,
				estatus = 1
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idCasilla, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			break;
		case "predeterminado_venta" :
			// RESETEA LA CASILLA PREDETERMINADA
			$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
				id_casilla_predeterminada = NULL
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// ACTIVA LA UBICACION PARA EL ARTICULO
			$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
				estatus = 1
			WHERE id_articulo = %s
				AND id_casilla = %s",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// ASIGNA LA CASILLA PREDETERMINADA
			$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
				id_casilla_predeterminada = %s,
				estatus = 1
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idCasilla, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			break;
	}
	
	return $objResponse;
}

function guardarModeloArticulo($frmModelo, $frmModeloArticulo) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmModelo['cbxItm2'];
	
	mysql_query("START TRANSACTION;");
	
	// INSERTA LOS MODELOS COMPATIBLES NUEVOS
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indice => $valor) {
			$insertSQL = sprintf("INSERT INTO iv_articulos_modelos_compatibles (id_articulo, id_unidad_basica)
			VALUE (%s, %s);",
				valTpDato($frmModeloArticulo['hddIdArticuloModeloArticulo'], "int"),
				valTpDato($valor, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Modelo(s) Compatible(s) Guardado(s) con Éxito");
	
	$objResponse->script("
	byId('btnCancelarModelo').click();");
	
	$objResponse->loadCommands(listaModeloArticulo(
		$frmModeloArticulo['pageNum'],
		$frmModeloArticulo['campOrd'],
		$frmModeloArticulo['tpOrd'],
		$frmModeloArticulo['valBusq']));
	
	return $objResponse;
}

function guardarUbicacionArticulo($frmAlmacen, $frmUbicacionArticulo) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmUbicacionArticulo['hddIdEmpresaUbicacionArticulo'];
	$idArticulo = $frmUbicacionArticulo['hddIdArticuloUbicacionArticulo'];
	
	// BUSCA LAS UBICACIONES ACTIVAS AGREGADAS AL ARTICULO
	$query = sprintf("SELECT * FROM iv_articulos_almacen
	WHERE id_articulo = %s
		AND estatus = 1
		AND id_casilla IN (SELECT casilla.id_casilla
							FROM iv_calles calle
								INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
								INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
								INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
								INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
							WHERE alm.id_empresa = %s);",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$insertSQL = sprintf("INSERT INTO iv_articulos_almacen (id_casilla, id_articulo, estatus, orden_prioridad_venta)
	VALUE (%s, %s, %s, %s);",
		valTpDato($frmAlmacen['lstCasillaAct'], "int"),
		valTpDato($idArticulo, "int"),
		valTpDato(1, "boolean"), // 0 = Inactivo, 1 = Activo 
		valTpDato($totalRows + 1, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	$idCasillaPredeterminada = $frmAlmacen['lstCasillaAct'];
	$idCasillaPredeterminadaCompra = $frmAlmacen['lstCasillaAct'];
	
	// VERIFICA SI EL ARTICULO YA ESTA REGISTRADO PARA LA EMPRESA
	$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
	WHERE id_empresa = %s
		AND id_articulo = %s",
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"));
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
	$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
	
	if ($totalRowsArtEmp == 0) { // SI NO EXISTE EL ARTICULO PARA LA EMPRESA LO INSERTA
		$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, clasificacion, estatus)
		VALUE (%s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato("F", "text"),
			valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
		$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA EL COSTO PROMEDIO
		$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA EL PRECIO DE VENTA
		$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	} else { // EN CASO DE QUE YA EXISTIA LO COLOCA COMO ACTIVO NUEVAMENTE
		$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
			estatus = 1
		WHERE id_articulo_empresa = %s;",
			valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	if ($totalRows == 0) {
		// RESETEA LA CASILLA PREDETERMINADA
		$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
			id_casilla_predeterminada = NULL,
			id_casilla_predeterminada_compra = NULL
		WHERE id_empresa = %s
			AND id_articulo = %s;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		if ($idCasillaPredeterminada > 0) {
			// ACTIVA LA UBICACION PARA EL ARTICULO
			$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
				estatus = 1
			WHERE id_articulo = %s
				AND id_casilla = %s",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasillaPredeterminada, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// ASIGNA LA CASILLA PREDETERMINADA
			$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
				id_casilla_predeterminada = %s,
				estatus = 1
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idCasillaPredeterminada, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		
		if ($idCasillaPredeterminadaCompra > 0) {
			// ACTIVA LA UBICACION PARA EL ARTICULO
			$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
				estatus = 1
			WHERE id_articulo = %s
				AND id_casilla = %s",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasillaPredeterminadaCompra, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			// ASIGNA LA CASILLA PREDETERMINADA
			$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
				id_casilla_predeterminada_compra = %s,
				estatus = 1
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idCasillaPredeterminadaCompra, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
	$Result1 = actualizarPedidas($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
	$Result1 = actualizarSaldos($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Ubicación Guardada con Éxito");
	
	$objResponse->script("
	byId('btnCancelarAlmacen').click();");
	
	$objResponse->loadCommands(listaUbicacionArticulo(
		$frmUbicacionArticulo['pageNum'],
		$frmUbicacionArticulo['campOrd'],
		$frmUbicacionArticulo['tpOrd'],
		$frmUbicacionArticulo['valBusq']));
	
	return $objResponse;
}

function guardarVentaPerdida($frmVentaPerdida, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	$idArticulo = $frmVentaPerdida['hddIdArtVentaPerdida'];
	$cantidadPerdida = $frmVentaPerdida['txtCantidadArtVentaPerdida'];
	
	mysql_query("START TRANSACTION;");
	
	$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_empresa
	WHERE id_empresa = %s
		AND id_articulo = %s;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$insertSQL = sprintf("INSERT INTO iv_ventas_perdidas (id_empresa, id_articulo, cantidad, maximo, minimo)
	VALUE (%s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"),
		valTpDato($cantidadPerdida, "real_inglesa"),
		valTpDato($rowArticulo['stock_maximo'], "real_inglesa"),
		valTpDato($rowArticulo['stock_minimo'], "real_inglesa"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnCancelarVentaPerdida').click();");
	
	$objResponse->alert(("Ventas Perdidas Guardadas con Éxito"));
	
	return $objResponse;
}

function importarArticulo($valForm, $frmListaArticulos) {
	$objResponse = new xajaxResponse();
	
	$inputFileType = 'Excel5';
	//$inputFileType = 'Excel2007';
	//$inputFileType = 'Excel2003XML';
	//$inputFileType = 'OOCalc';
	//$inputFileType = 'Gnumeric';
	$inputFileName = 'reportes/tmp/'.$valForm['hddUrlArchivo'];
	
	$phpExcel = new PHPExcel_Reader_Excel2007();
	$archivoExcel = $phpExcel->load($inputFileName);
	
	$archivoExcel->setActiveSheetIndex(0);
	$i = 1;
	while ($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue() != '') {
		if ($itemExcel == true) {
			$arrayArticuloDetalle[0] = $archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue();
			$arrayArticuloDetalle[1] = $archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue();
			$arrayArticuloDetalle[2] = $archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue();
			$arrayArticuloDetalle[3] = $archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue();
			$arrayArticuloDetalle[4] = $archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue();
			$arrayArticuloDetalle[5] = $archivoExcel->getActiveSheet()->getCell('F'.$i)->getValue();
			$arrayArticuloDetalle[6] = $archivoExcel->getActiveSheet()->getCell('G'.$i)->getValue();
			$arrayArticuloDetalle[7] = $archivoExcel->getActiveSheet()->getCell('H'.$i)->getValue();
			$arrayArticuloDetalle[8] = $archivoExcel->getActiveSheet()->getCell('I'.$i)->getValue();
			$arrayArticuloDetalle[9] = $archivoExcel->getActiveSheet()->getCell('J'.$i)->getValue();
		
			$arrayArticulo[] = $arrayArticuloDetalle;
		}
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Codigo"))) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	if (isset($arrayArticulo)) {
		mysql_query("START TRANSACTION;");
		
		$idEmpresa = $valForm['txtIdEmpresaImportarArticulo'];
			
		foreach ($arrayArticulo as $indice => $valor) {
			$poseeIva = 1; // 0 = No, 1 = Si
			$fechaRegistro = "NOW()";
			$idPrecioPredeterminado = 1;
			$generaComision = 1;
			
			$codigoArticulo = $arrayArticulo[$indice][0];
			$idMarca = $arrayArticulo[$indice][1];
			$idTipoArticulo = $arrayArticulo[$indice][2];
			$codigoArticuloProv = $arrayArticulo[$indice][3];
			$descripcion = $arrayArticulo[$indice][4];
			$idSubseccion = $arrayArticulo[$indice][5];
			$clasificacion = $arrayArticulo[$indice][6];
			$idTipoUnidad = $arrayArticulo[$indice][7];
			$idProveedor = $arrayArticulo[$indice][8];
			$costoUnitario = $arrayArticulo[$indice][9];
			
			// BUSCA SI EXISTE EL CODIGO DEL ARTICULO
			$queryArticulo = sprintf("SELECT * FROM iv_articulos
			WHERE codigo_articulo LIKE %s;",
				valTpDato($codigoArticulo, "text"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsArticulo = mysql_num_rows($rsArticulo);
			$rowArticulo = mysql_fetch_array($rsArticulo);
			
			$idArticulo = $rowArticulo['id_articulo'];
			
			if ($totalRowsArticulo == 0) {
				$insertSQL = sprintf("INSERT INTO iv_articulos (codigo_articulo, id_marca, id_tipo_articulo, codigo_articulo_prov, descripcion, id_subseccion, clasificacion, id_tipo_unidad, posee_iva, id_empresa_creador, fecha_registro, id_precio_predeterminado, genera_comision)
				VALUE (TRIM(%s), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($codigoArticulo, "text"),
					valTpDato($idMarca, "int"),
					valTpDato($idTipoArticulo, "int"),
					valTpDato($codigoArticuloProv, "text"),
					valTpDato($descripcion, "text"),
					valTpDato($idSubseccion, "int"),
					valTpDato($clasificacion, "text"),
					valTpDato($idTipoUnidad, "int"),
					valTpDato($poseeIva, "boolean"),
					valTpDato($idEmpresa, "int"),
					valTpDato($fechaRegistro, "campo"),
					valTpDato($idPrecioPredeterminado, "int"),
					valTpDato($generaComision, "boolean")); 
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idArticulo = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// INSERTA LOS IMPUESTOS DEL ARTICULO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$insertSQL = sprintf("INSERT INTO iv_articulos_impuesto (id_articulo, id_impuesto)
				SELECT %s, iva.idIva FROM pg_iva iva WHERE iva.tipo IN (1,6) AND iva.estado = 1 AND iva.activo = 1;",
					valTpDato($idArticulo, "int")); 
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// INSERTA EL COSTO DE LA PIEZA
				$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, costo_promedio, id_moneda, fecha_registro)
				SELECT %s, %s, %s, %s, %s, %s, moneda.idmoneda, %s FROM pg_monedas moneda
				WHERE moneda.estatus = 1
					AND moneda.predeterminada = 1;",
					valTpDato($idEmpresa, "int"),
					valTpDato($idProveedor, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($fechaRegistro, "campo"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($fechaRegistro, "campo"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idArticuloCosto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// ACTUALIZA EL COSTO PROMEDIO
				$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA EL PRECIO DE VENTA
				$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			}
			
			// BUSCA SI EXISTE EL CODIGO DEL ARTICULO PARA LA EMPRESA
			$queryArticuloEmpresa = sprintf("SELECT * FROM iv_articulos_empresa
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			$rsArticuloEmpresa = mysql_query($queryArticuloEmpresa);
			if (!$rsArticuloEmpresa) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsArticuloEmpresa = mysql_num_rows($rsArticuloEmpresa);
			$rowArticuloEmpresa = mysql_fetch_array($rsArticuloEmpresa);
			
			if ($totalRowsArticuloEmpresa == 0) {
				$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, clasificacion, estatus)
				VALUE (%s, %s, %s, %s);",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($clasificacion, "text"),
					valTpDato(1, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
		
				// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
				$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA EL COSTO PROMEDIO
				$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA EL PRECIO DE VENTA
				$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			}
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Artículos Importado con Éxito");
		
		$objResponse->script("
		byId('btnCancelarImportarArticulo').click();");
		
		$objResponse->loadCommands(listaArticulo(
			$frmListaArticulos['pageNum'],
			$frmListaArticulos['campOrd'],
			$frmListaArticulos['tpOrd'],
			$frmListaArticulos['valBusq']));
	}
	
	return $objResponse;
}

function listaArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = "";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("estatus_articulo_empresa = %s",
				valTpDato($valCadBusq[1], "boolean"));
		}
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modo_compra = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art.id_tipo_articulo IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art.clasificacion IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[4])."'", "defined", "'".str_replace(",","','",$valCadBusq[4])."'"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(codigo_articulo REGEXP %s
		OR vw_iv_art.id_articulo IN (SELECT art_cod_sust.id_articulo
									FROM iv_articulos_codigos_sustitutos art_cod_sust
										INNER JOIN iv_articulos art_sust ON (art_cod_sust.id_articulo_sustituido = art_sust.id_articulo)
									WHERE art_sust.codigo_articulo REGEXP %s) )",
			valTpDato($valCadBusq[5], "text"),
			valTpDato($valCadBusq[5], "text"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_articulo = %s
		OR descripcion LIKE %s
		OR codigo_articulo_prov LIKE %s
		OR codigo_arancel LIKE %s)",
			valTpDato($valCadBusq[6], "int"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$query = sprintf("SELECT vw_iv_art.*,
			arancel_fam.codigo_arancel,
			arancel_fam.descripcion_arancel,
			arancel_grup.porcentaje_grupo
		FROM pg_arancel_grupo arancel_grup
			RIGHT JOIN pg_arancel_familia arancel_fam ON (arancel_grup.id_arancel_grupo = arancel_fam.id_arancel_grupo)
			RIGHT JOIN vw_iv_articulos_empresa_datos_basicos vw_iv_art ON (arancel_fam.id_arancel_familia = vw_iv_art.id_arancel_familia) %s", $sqlBusq);
	} else {
		$query = sprintf("SELECT vw_iv_art.*,
			arancel_fam.codigo_arancel,
			arancel_fam.descripcion_arancel,
			arancel_grup.porcentaje_grupo
		FROM pg_arancel_grupo arancel_grup
			RIGHT JOIN pg_arancel_familia arancel_fam ON (arancel_grup.id_arancel_grupo = arancel_fam.id_arancel_grupo)
			RIGHT JOIN vw_iv_articulos_datos_basicos vw_iv_art ON (arancel_fam.id_arancel_familia = vw_iv_art.id_arancel_familia) %s", $sqlBusq);
	}
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,'frmListaArticulos');\"/></td>";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "52%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "12%", $pageNum, "codigo_articulo_prov", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Prov.");
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "14%", $pageNum, "tipo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Artículo");
		$htmlTh .= "<td colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Clasif.");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			switch ($row['estatus_articulo_empresa']) {
				case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
				case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
				default : $imgEstatus = "";
			}
		}
		
		$queryArtSustituto = sprintf("SELECT 
			art_sust.codigo_articulo
		FROM iv_articulos_codigos_sustitutos art_cod_sust
			INNER JOIN iv_articulos art_sust ON (art_cod_sust.id_articulo_sustituido = art_sust.id_articulo)
		WHERE art_cod_sust.id_articulo = %s;",
			valTpDato($row['id_articulo'], "int"));
		$rsArtSustituto = mysql_query($queryArtSustituto);
		if (!$rsArtSustituto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsArtSustituto = mysql_num_rows($rsArtSustituto);
		$arrayArtSustituto = NULL;
		while ($rowArtSustituto = mysql_fetch_assoc($rsArtSustituto)) {
			$arrayArtSustituto[] = elimCaracter($rowArtSustituto['codigo_articulo'],";");
		}
		
		$queryArtSustituido = sprintf("SELECT
			art.codigo_articulo
		FROM iv_articulos_codigos_sustitutos art_cod_sust
			INNER JOIN iv_articulos art ON (art_cod_sust.id_articulo = art.id_articulo)
		WHERE art_cod_sust.id_articulo_sustituido = %s;",
			valTpDato($row['id_articulo'], "int"));
		$rsArtSustituido = mysql_query($queryArtSustituido);
		if (!$rsArtSustituido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsArtSustituido = mysql_num_rows($rsArtSustituido);
		$arrayArtSustituido = NULL;
		while ($rowArtSustituido = mysql_fetch_assoc($rsArtSustituido)) {
			$arrayArtSustituido[] = elimCaracter($rowArtSustituido['codigo_articulo'],";");
		}
		
		if ($totalRowsArtSustituto > 0) {
			$msjArt = "<span class=\"textoVerdeNegrita_10px\">Sustituye al Artículo Código: ".implode(", ",$arrayArtSustituto)."</span>";
		} else if ($totalRowsArtSustituido > 0) {
			$msjArt = "<span class=\"textoRojoNegrita_10px\">Sustituido por el Artículo Código: ".implode(", ",$arrayArtSustituido)."</span>";
		} else {
			$msjArt = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
				$row['id_articulo']);
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"80%\">".utf8_encode($row['descripcion'])."</td>";
					$htmlTb .= "<td align=\"right\" width=\"20%\">".utf8_encode($row['descripcion_modo_compra'])."</td>";
				$htmlTb .= "</tr>";
			if (strlen($row['codigo_arancel']) > 0) {
				$htmlTb .= "<tr class=\"textoNegrita_10px\">";
					$htmlTb .= "<td colspan=\"2\" title=\"".utf8_encode($row['descripcion_arancel'])."\" width=\"100%\">".utf8_encode($row['codigo_arancel'])." (".number_format($row['porcentaje_grupo'], 2, ".", ",")."%)</td>";
				$htmlTb .= "</tr>";
			}
			if (strlen($msjArt) > 0) {
				$htmlTb .= "<tr class=\"textoNegrita_10px\">";
					$htmlTb .= "<td colspan=\"2\">".$msjArt."</td>";
				$htmlTb .= "</tr>";
			}
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_articulo_prov'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['tipo_articulo']." (".$row['marca'].")")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onmouseover=\"Tip('<div id=divPrecios></div><div id=divCostos></div>', TITLE, '%s) Lista de Precios (Artículo Código: %s - %s)', WIDTH, 800); xajax_listaPrecios('%s', xajax.getFormValues('frmBuscar'));\" onmouseout=\"UnTip();\"><img class=\"puntero\" src=\"../img/iconos/money.png\" title=\"Ver Precios\"/></a>",
					$row['id_articulo'],
					elimCaracter($row['codigo_articulo'],";"),
					preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$row['descripcion'])))),
					$row['id_articulo']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblListaDcto', byId('lstEmpresa').value, '%s');\" onmouseover=\"Tip('<div id=divUbicaciones></div>', TITLE, '%s) Saldos (Artículo Código: %s - %s)', WIDTH, 760); xajax_listaSaldos(byId('lstEmpresa').value, '%s');\" onmouseout=\"UnTip();\"><img class=\"puntero\" src=\"../img/iconos/package_green.png\" title=\"Ver Ubicaciones\"/></a>",
					$row['id_articulo'],
					$row['id_articulo'],
					elimCaracter($row['codigo_articulo'],";"),
					preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$row['descripcion'])))),
					$row['id_articulo']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aModelo%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblModeloArticulo', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/car.png\" title=\"Modelos Compatibles\"/></a>",
					$contFila,
					$row['id_articulo'],
					$row['id_empresa']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificación A\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificación B\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificación C\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificación D\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificación E\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificación F\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" href=\"iv_articulo_form.php?id=%s&ide=%s&vw=v\" onmouseover=\"Tip('<div id=divDatosArtículo></div>', TITLE, '%s) Foto (Artículo Código: %s - %s)', WIDTH, 400); xajax_formDatosArticulo('%s','%s');\" onmouseout=\"UnTip();\"><img src=\"../img/iconos/ico_view.png\" title=\"Ver Artículo\"/></a>",
					$row['id_articulo'],
					$row['id_empresa'],
					$row['id_articulo'],
					elimCaracter($row['codigo_articulo'],";"),
					preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$row['descripcion'])))),
					$row['id_articulo'],
					$row['id_empresa']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"iv_articulo_form.php?id=%s&ide=%s\"><img src=\"../img/iconos/pencil.png\" title=\"Editar Artículo\"/></a>",
					$row['id_articulo'],
					$row['id_empresa']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aUbicacion%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUbicacionArticulo', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/package_edit.png\" title=\"Editar Ubicaciones\"/></a>",
					$contFila,
					$row['id_articulo'],
					$row['id_empresa']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if (($valCadBusq[0] != "-1" && $valCadBusq[0] != "") && $row['estatus_articulo_empresa'] == 0) {
				$htmlTb .= sprintf("<a id=\"imgActivarArticulo%s\" onclick=\"validarActivarArticulo('%s');\"><img class=\"puntero\" src=\"../img/iconos/select.png\" title=\"Activar Artículo\"/></a>",
					$contFila,
					$row['id_articulo']);
			} else if (!($valCadBusq[0] != "-1" && $valCadBusq[0] != "") || $row['estatus_articulo_empresa'] == 1) {
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblPermiso', '2', %s);\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar Artículo\"/></a>",
					$row['id_articulo']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"15\">";
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
		$htmlTb .= "<td colspan=\"15\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticulos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaBloqueoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("bloqueo_vent_det.cantidad > 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("bloqueo_vent.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("bloqueo_vent_det.id_articulo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("bloqueo_vent_det.id_casilla = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	$query = sprintf("SELECT
		bloqueo_vent.id_bloqueo_venta,
		DATE(bloqueo_vent.fecha_bloqueo) AS fecha_bloqueo,
		vw_pg_empleado.nombre_empleado,
		bloqueo_vent_det.cantidad
	FROM iv_bloqueo_venta_detalle bloqueo_vent_det
		INNER JOIN iv_bloqueo_venta bloqueo_vent ON (bloqueo_vent_det.id_bloqueo_venta = bloqueo_vent.id_bloqueo_venta)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (bloqueo_vent.id_empleado = vw_pg_empleado.id_empleado) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"12%\">"."Fecha"."</td>";
		$htmlTh .= "<td width=\"16%\">"."Nro. Bloqueo"."</td>";
		$htmlTh .= "<td width=\"60%\">"."Empleado"."</td>";
		$htmlTh .= "<td width=\"12%\">"."Cantidad"."</td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".(($row['fecha_bloqueo'] != "") ? date(spanDateFormat,strtotime($row['fecha_bloqueo'])) : "xx-xx-xxxx")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_bloqueo_venta']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cantidad']."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"4\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBloqueoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBloqueoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaBloqueoVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBloqueoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBloqueoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaDcto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$objResponse->assign("tdMsj","innerHTML",$html);
	
	$objResponse->assign("legendListaDcto","innerHTML","Bloqueos de Venta");
	$objResponse->script("byId('fieldsetListaDcto').style.display = '';");
	
	return $objResponse;
}

function listaCostoProveedor($idEmpresa, $idArticulo) {
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($idArticulo != "-1" && $idArticulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.id_articulo = %s",
			valTpDato($idArticulo, "int"));
	}
	
	$query = sprintf("SELECT DISTINCT art_costo.*,
		art_emp.id_empresa,
		prov.id_proveedor,
		prov.nombre AS nombre_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		art.codigo_articulo_prov,
		art.id_tipo_articulo,
		art_emp.clasificacion,
		(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0)) AS existencia,
		IFNULL(art_costo.cantidad_reservada, 0) AS cantidad_reservada,
		(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0) - IFNULL(art_costo.cantidad_reservada, 0)) AS cantidad_disponible_fisica,
		IFNULL(art_costo.cantidad_espera, 0) AS cantidad_espera,
		IFNULL(art_costo.cantidad_bloqueada, 0) AS cantidad_bloqueada,
		(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0) - IFNULL(art_costo.cantidad_reservada, 0) - IFNULL(art_costo.cantidad_espera, 0) - IFNULL(art_costo.cantidad_bloqueada, 0)) AS cantidad_disponible_logica,
		moneda_local.abreviacion AS abreviacion_moneda_local,
		moneda_origen.abreviacion AS abreviacion_moneda_origen,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM iv_articulos art
		INNER JOIN iv_articulos_empresa art_emp ON (art.id_articulo = art_emp.id_articulo)
		LEFT JOIN iv_articulos_costos art_costo ON (art_emp.id_empresa = art_costo.id_empresa)
			AND (art_emp.id_articulo = art_costo.id_articulo)
		LEFT JOIN cp_proveedor prov ON (art_costo.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (art_costo.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_origen ON (art_costo.id_moneda_origen = moneda_origen.idmoneda)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (art_emp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	ORDER BY art_costo.id_articulo_costo DESC
	LIMIT 10;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">".utf8_encode("Fecha")."</td>";
		$htmlTh .= "<td width=\"26%\">".utf8_encode("Empresa")."</td>";
		$htmlTh .= "<td width=\"30%\">".utf8_encode("Proveedor")."</td>";
		$htmlTh .= "<td width=\"6%\">".utf8_encode("Lote")."</td>";
		$htmlTh .= "<td width=\"14%\">".utf8_encode("Costo")."</td>";
		$htmlTh .= "<td width=\"14%\">".utf8_encode("Costo Promedio")."</td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
		$ResultConfig12 = valorConfiguracion(12, $row['id_empresa']);
		if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
			return $objResponse->alert($ResultConfig12[1]);
		} else if ($ResultConfig12[0] == true) {
			$ResultConfig12 = $ResultConfig12[1];
		}
		
		$classCosto = (in_array($ResultConfig12, array(1,3))) ? "divMsjInfo" : "";
		$classCostoProm = (!in_array($ResultConfig12, array(1,3))) ? "divMsjInfo" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" title=\"Id Artículo Costo: ".$row['id_articulo_costo']."\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_articulo_costo']."</td>";
			$htmlTb .= "<td align=\"right\" class=\"".$classCosto."\">";
				$htmlTb .= $row['abreviacion_moneda_local'].number_format($row['costo'],2,".",",");
				$htmlTb .= ($row['costo_origen'] != 0) ? "<br>".$row['abreviacion_moneda_origen'].number_format($row['costo_origen'], 2, ".", ",") : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" class=\"".$classCostoProm."\">".$row['abreviacion_moneda_local'].number_format($row['costo_promedio'],2,".",",")."</td>";
		$htmlTb .= "</tr>";
	}
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divCostos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaModelo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 120, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("unid_emp.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_uni_bas NOT IN (SELECT art_modelo_comp.id_unidad_basica FROM iv_articulos_modelos_compatibles art_modelo_comp
														WHERE art_modelo_comp.id_articulo = %s)",
		valTpDato($valCadBusq[1], "int"));
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_uni_bas LIKE %s
		OR nom_modelo LIKE %s
		OR nom_version LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT *
	FROM vw_iv_modelos vw_iv_modelo
		INNER JOIN sa_unidad_empresa unid_emp ON (vw_iv_modelo.id_uni_bas = unid_emp.id_unidad_basica) %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		
		$htmlTb .= "<td width=\"33%\" valign=\"top\">";
			$htmlTb .= "<label><table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td rowspan=\"5\" style=\"background-color:#FFFFFF\">%s</td>", "<img src=\"".$imgFoto."\" width=\"80\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>", "100%",
					utf8_encode($row['nom_uni_bas']));
				$htmlTb .= "<td align=\"center\" rowspan=\"5\" valign=\"top\">";
					$htmlTb .= sprintf("<input type=\"checkbox\" id=\"cbxItm2\" name=\"cbxItm2[]\" %s value=\"%s\"/>",
						$checked,
						$row['id_uni_bas']);
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_marca']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_modelo']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_version']));
			$htmlTb .= "</tr>";
			$htmlTb .= "</table></label>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModelo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModelo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaModelo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModelo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModelo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaModelo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaModeloArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 120, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_articulo = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT *
	FROM vw_iv_modelos vw_iv_modelo
		INNER JOIN iv_articulos_modelos_compatibles art_modelo_comp ON (vw_iv_modelo.id_uni_bas = art_modelo_comp.id_unidad_basica) %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		
		$htmlTb .= "<td width=\"33%\" valign=\"top\">";
			$htmlTb .= "<label><table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td rowspan=\"5\" style=\"background-color:#FFFFFF\">%s</td>", "<img src=\"".$imgFoto."\" width=\"80\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>", "100%",
					utf8_encode($row['nom_uni_bas']));
				$htmlTb .= "<td align=\"center\" rowspan=\"5\" valign=\"top\">";
					$htmlTb .= sprintf("<input type=\"checkbox\" id=\"cbxItm3\" name=\"cbxItm3[]\" %s value=\"%s\"/>",
						$checked,
						$row['id_articulo_modelo_compatible']);
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_marca']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_modelo']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_version']));
			$htmlTb .= "</tr>";
			$htmlTb .= "</table></label>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModeloArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModeloArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaModeloArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModeloArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaModeloArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaModeloArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPedidoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_vent.estatus_pedido_venta IN (0,1,2)");
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_vent_det.pendiente > 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_vent.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_vent_det.id_articulo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_vent_det.id_casilla = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	$query = sprintf("SELECT
		ped_vent.id_pedido_venta,
		ped_vent.fecha,
		ped_vent.id_pedido_venta_propio,
		ped_vent.id_pedido_venta_referencia,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		ped_vent_det.pendiente,
		ped_vent.estatus_pedido_venta
	FROM iv_pedido_venta ped_vent
		INNER JOIN iv_pedido_venta_detalle ped_vent_det ON (ped_vent.id_pedido_venta = ped_vent_det.id_pedido_venta)
		INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td width=\"12%\">"."Fecha"."</td>";
		$htmlTh .= "<td width=\"16%\">"."Nro. Pedido"."</td>";
		$htmlTh .= "<td width=\"16%\">"."Nro. Referencia"."</td>";
		$htmlTh .= "<td width=\"44%\">"."Cliente"."</td>";
		$htmlTh .= "<td width=\"12%\">"."Cantidad"."</td>";
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estatus_pedido_venta']) {
			case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Pendiente por Terminar\"/>"; break;
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Convertido a Pedido\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Aprobado\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_propio']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_referencia']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['pendiente'], 2, ".", ",")."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"verVentana('reportes/iv_pedido_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_view.png\"/></td>",
				$row['id_pedido_venta']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaDcto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo2\" width=\"100%\">";
	$html .= "<tr>";
		$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\" width=\"25\"></td>";
		$html .= "<td align=\"center\">";
			$html .= "<table>";
			$html .= "<tr>";
				$html .= "<td><img src=\"../img/iconos/ico_azul.gif\"></td><td>Pedido Aprobado</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_verde.gif\"></td><td>Convertido a Pedido</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_amarillo.gif\"></td><td>Pendiente por Terminar</td>";
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	$objResponse->assign("tdMsj","innerHTML",$html);
	
	$objResponse->assign("legendListaDcto","innerHTML","Pedidos de Venta");
	$objResponse->script("byId('fieldsetListaDcto').style.display = '';");
	
	return $objResponse;
}

function listaPedidoCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_pedido_compra IN (0,1,2)");
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("ped_comp_det.pendiente > 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$query = sprintf("SELECT
		ped_comp.id_pedido_compra,
		ped_comp.fecha,
		ped_comp.id_pedido_compra_propio,
		ped_comp.id_pedido_compra_referencia,
		prov.nombre,
		ped_comp_det.pendiente,
		ped_comp.estatus_pedido_compra
	FROM iv_pedido_compra ped_comp
		INNER JOIN iv_pedido_compra_detalle ped_comp_det ON (ped_comp.id_pedido_compra = ped_comp_det.id_pedido_compra)
		INNER JOIN cp_proveedor prov ON (ped_comp.id_proveedor = prov.id_proveedor) %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td width=\"12%\">"."Fecha"."</td>";
		$htmlTh .= "<td width=\"16%\">"."Nro. Pedido"."</td>";
		$htmlTh .= "<td width=\"16%\">"."Nro. Referencia"."</td>";
		$htmlTh .= "<td width=\"44%\">"."Proveedor"."</td>";
		$htmlTh .= "<td width=\"12%\">"."Cantidad"."</td>";
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus_pedido_compra']) {
			case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pendiente por Terminar\"/>"; break;
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Convertido a Pedido\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Convertido a Orden\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_compra_propio']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_compra_referencia']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['pendiente'], 2, ".", ",")."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"verVentana('reportes/iv_pedido_compra_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/ico_view.png\"/></td>",
				$row['id_pedido_compra']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaDcto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo2\" width=\"100%\">";
	$html .= "<tr>";
		$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\" width=\"25\"></td>";
		$html .= "<td align=\"center\">";
			$html .= "<table>";
			$html .= "<tr>";
				$html .= "<td><img src=\"../img/iconos/ico_azul.gif\"></td><td>Convertido a Orden</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_verde.gif\"></td><td>Convertido a Pedido</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_amarillo.gif\"></td><td>Pendiente por Terminar</td>";
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	$objResponse->assign("tdMsj","innerHTML",$html);
	
	$objResponse->assign("legendListaDcto","innerHTML","Pedidos de Compra");
	$objResponse->script("byId('fieldsetListaDcto').style.display = '';");
	
	return $objResponse;
}

function listaPrecios($idArticulo, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","")) { return $objResponse; }
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	
	// ACTUALIZA EL PRECIO DE VENTA
	$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$ResultConfig12 = valorConfiguracion(12, $idEmpresa);
	if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
		return $objResponse->alert($ResultConfig12[1]);
	} else if ($ResultConfig12[0] == true) {
		$ResultConfig12 = $ResultConfig12[1];
	}
	
	// BUSCA LOS LOTES DEL ARTICULO
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo = %s
	AND vw_iv_art_almacen_costo.id_empresa = %s
	AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
	AND vw_iv_art_almacen_costo.estatus_almacen_venta = 1", 
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
		
	if (!in_array($ResultConfig12, array(1,2))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
	}
	$limitArtCosto = (in_array($ResultConfig12, array(1,2))) ? 1 : 4;
	
	$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s
	ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;", $sqlBusq);
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	
	
	// BUSCA LA EXISTENCIA DEL ARTICULO
	$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_empresa vw_iv_art_emp
	WHERE id_articulo = %s
		AND id_empresa = %s;", 
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
	$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
	
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("porcentaje <> 0 AND precio.estatus IN (1,2)");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("emp_precio.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$queryEmpPrecio = sprintf("SELECT DISTINCT precio.*
	FROM pg_empresa_precios emp_precio
		INNER JOIN pg_precios precio ON (emp_precio.id_precio = precio.id_precio) %s
	ORDER BY precio.porcentaje DESC;", $sqlBusq);
	$rsEmpPrecio = mysql_query($queryEmpPrecio);
	if (!$rsEmpPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsEmpPrecio = mysql_num_rows($rsEmpPrecio);
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"798\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"40%\">";
			$htmlTh .= "Descripción";
			$htmlTh .= "<br>(Cant. Lotes: ".$totalRowsArtCosto.", Unid. Disponible: ".utf8_encode($rowArtEmp['cantidad_disponible_logica']).")";
		$htmlTh .= "</td>";
		while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
			$contArtCosto++;
			if ($contArtCosto <= $limitArtCosto) {
				$htmlTh .= "<td width=\"".(60 / $totalRowsArtCosto)."%\">";
					$htmlTh .= "<table border=\"0\" width=\"100%\">";
					$htmlTh .= "<tr align=\"center\">";
						$htmlTh .= "<td>";
							$htmlTh .= "Precio + Impuesto = Total";
							if (!in_array($ResultConfig12, array(1,2))) {
								$htmlTh .= "<br>(LOTE: ".utf8_encode($rowArtCosto['id_articulo_costo']).", Unid. Disponible: ".utf8_encode($rowArtCosto['cantidad_disponible_logica']).")";
							}
						$htmlTh .= "</td>";
					$htmlTh .= "</tr>";
					$htmlTh .= "</table>";
				$htmlTh .= "</td>";
				
				$arrayIdArtCosto[] = array($rowArtCosto['id_articulo_costo']);
			}
		}
	$htmlTh .= "</tr>";
	
	while ($rowEmpPrecio = mysql_fetch_assoc($rsEmpPrecio)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td title=\"Id Articulo Precio: ".$rowEmpPrecio['id_articulo_precio']."\">".utf8_encode($rowEmpPrecio['descripcion_precio'])."</td>";
			if ($arrayIdArtCosto) {
				$contFila2 = 0;
				foreach ($arrayIdArtCosto as $indice => $valor) {
					$contFila2++;
					
					$queryArtPrecio = sprintf("SELECT
						art_precio.precio AS precio_unitario,
						
						(SELECT iva.observacion
						FROM iv_articulos_impuesto art_impsto
							INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
						WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1
							AND art_impsto.id_articulo = art_precio.id_articulo
						LIMIT 1) AS descripcion_impuesto,
						
						(SELECT SUM(iva.iva)
						FROM iv_articulos_impuesto art_impsto
							INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
						WHERE iva.tipo IN (6,9,2)
							AND art_impsto.id_articulo = art_precio.id_articulo) AS porcentaje_impuesto,
						
						(art_precio.precio * (SELECT SUM(iva.iva)
											FROM iv_articulos_impuesto art_impsto
												INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
											WHERE iva.tipo IN (6,9,2)
												AND art_impsto.id_articulo = art_precio.id_articulo) / 100) AS monto_impuesto,
						
						moneda.abreviacion AS abreviacion_moneda
					FROM iv_articulos_precios art_precio
						INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
					WHERE art_precio.id_precio = %s
						AND art_precio.id_articulo_costo = %s;",
						valTpDato($rowEmpPrecio['id_precio'], "int"),
						valTpDato($arrayIdArtCosto[$indice][0], "int"));
					$rsArtPrecio = mysql_query($queryArtPrecio);
					if (!$rsArtPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
					
					$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">";
						$htmlTb .= $rowArtPrecio['abreviacion_moneda'].number_format($rowArtPrecio['precio_unitario'], 2, ".", ",");
						$htmlTb .= " + ";
						$htmlTb .= $rowArtPrecio['abreviacion_moneda'].number_format($rowArtPrecio['monto_impuesto'], 2, ".", ",");
						$htmlTb .= " = ";
						$htmlTb .= "<span class=\"divMsjInfo\" style=\"padding:4px\">".$rowArtPrecio['abreviacion_moneda'].number_format($rowArtPrecio['precio_unitario'] + $rowArtPrecio['monto_impuesto'], 2, ".", ",")."</span>";
					$htmlTb .= "</td>";
				}
			}
		$htmlTb .= "</tr>";
	}
	$htmlTblFin .= "</table>";

	if (!($totalRowsEmpPrecio > 0)) {
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divPrecios","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	if (!in_array(idArrayPais,array(2))) {
		$objResponse->loadCommands(listaCostoProveedor($idEmpresa, $idArticulo));
	}
	
	return $objResponse;
}

function listaSaldos($idEmpresa, $idArticulo) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","")) { return $objResponse; }
	
	$queryArticulo = sprintf("SELECT *,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = art.id_articulo
			AND kardex.tipo_movimiento IN (1)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_compra,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = art.id_articulo
			AND kardex.tipo_movimiento IN (3)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_venta
		
	FROM iv_articulos art
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$queryDispUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_articulo = %s
		AND ((estatus_articulo_almacen = 1)
			OR (((estatus_articulo_almacen IS NULL AND (cantidad_pedida > 0 OR existencia > 0)) OR estatus_articulo_almacen = 1) %s)
			OR (id_casilla IS NOT NULL AND (estatus_articulo_almacen IS NULL AND cantidad_pedida > 0)));",
		valTpDato($idArticulo, "int"),
		$sqlBusq);
	$rsDispUbic = mysql_query($queryDispUbic);
	if (!$rsDispUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsDispUbic = mysql_num_rows($rsDispUbic);
	while ($rowDispUbic = mysql_fetch_assoc($rsDispUbic)) {
		if ($totalRowsDispUbic > 0
		&& ($rowDispUbic['id_casilla'] > 0 || $rowDispUbic['cantidad_reservada'] > 0 || $rowDispUbic['cantidad_pedida'] > 0)) {
			// VERIFICA SI ALGUN ARTICULO TIENE LA UBICACION OCUPADA
			$queryCasilla = sprintf("SELECT
				casilla.*,
				
				(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
				WHERE art_alm.id_casilla = casilla.id_casilla
					AND art_alm.estatus = 1) AS cantidad_ocupada
			FROM iv_casillas casilla
			WHERE casilla.id_casilla = %s;",
				valTpDato($rowDispUbic['id_casilla'], "int"));
			$rsCasilla = mysql_query($queryCasilla);
			if (!$rsCasilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsCasilla = mysql_num_rows($rsCasilla);
			$rowCasilla = mysql_fetch_assoc($rsCasilla);
			
			$arrayAlmacen[$rowDispUbic['id_almacen']]['id_almacen'] = $rowDispUbic['id_almacen'];
			$arrayAlmacen[$rowDispUbic['id_almacen']]['descripcion_almacen'] = $rowDispUbic['descripcion_almacen'];
			$arrayAlmacen[$rowDispUbic['id_almacen']]['estatus_almacen'] = $rowDispUbic['estatus_almacen'];
			$arrayAlmacen[$rowDispUbic['id_almacen']]['estatus_almacen_venta'] = $rowDispUbic['estatus_almacen_venta'];
			$arrayAlmacen[$rowDispUbic['id_almacen']]['estatus_almacen_compra'] = $rowDispUbic['estatus_almacen_compra'];
			$arrayAlmacen[$rowDispUbic['id_almacen']]['detalle_almacen'][] = array(
				"id_casilla" => $rowDispUbic['id_casilla'],
				"ubicacion" => $rowDispUbic['ubicacion'],
				"cantidad_ocupada" => $rowCasilla['cantidad_ocupada'],
				"estatus_casilla" => $rowCasilla['estatus'],
				"casilla_predeterminada" => $rowDispUbic['casilla_predeterminada'],
				"casilla_predeterminada_compra" => $rowDispUbic['casilla_predeterminada_compra'],
				"existencia" => $rowDispUbic['existencia'],
				"cantidad_reservada" => $rowDispUbic['cantidad_reservada'],
				"cantidad_espera" => $rowDispUbic['cantidad_espera'],
				"cantidad_bloqueada" => $rowDispUbic['cantidad_bloqueada'],
				"cantidad_disponible_logica" => $rowDispUbic['cantidad_disponible_logica'],
				"cantidad_pedida" => $rowDispUbic['cantidad_pedida'],
				"cantidad_futura" => $rowDispUbic['cantidad_futura'],
				"estatus_articulo_almacen" => $rowDispUbic['estatus_articulo_almacen']);
		}
	}
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"758\">";
	$htmlTh .= "<tr align=\"center\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td align=\"right\" class=\"tituloCampo\">"."Ult. Compra:"."</td>";
		$htmlTh .= "<td colspan=\"2\">".(($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx")."</td>";
		$htmlTh .= "<td align=\"right\" class=\"tituloCampo\">"."Ult. Venta:"."</td>";
		$htmlTh .= "<td colspan=\"2\">".(($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx")."</td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\"></td>";
		$htmlTh .= "<td width=\"10%\">"."Saldo"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Reservada (Serv.)"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Espera por Facturar"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Bloqueada"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Unid. Disponible"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Pedida a Proveedor"."</td>";
		$htmlTh .= "<td width=\"10%\">"."Futura"."</td>";
	$htmlTh .= "</tr>";
	if (isset($arrayAlmacen)) {
		foreach ($arrayAlmacen as $indiceAlmacen => $valorAlmacen) {
			$arrayDetalleAlmacen = $valorAlmacen['detalle_almacen'];
			
			$htmlTb .= "<tr class=\"tituloCampo\">";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr align=\"left\" ".(($valorAlmacen['estatus_almacen'] == 1) ? "" : "class=\"trResaltarRojo\"").">";
						$htmlTb .= "<td width=\"100%\"><b>".$valorAlmacen['descripcion_almacen']."</b></td>";
						$htmlTb .= ($valorAlmacen['estatus_almacen_venta'] == 1) ? "<td><span class=\"textoAzulNegrita_10px\">[Venta]</span><td>" : "";
						$htmlTb .= ($valorAlmacen['estatus_almacen_compra'] == 1) ? "<td><span class=\"textoVerdeNegrita_10px\">[Compra]</span><td>" : "";
						$htmlTb .= "</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td colspan=\"7\"></td>";
			$htmlTb .= "</tr>";
			
			if (isset($arrayDetalleAlmacen)) {
				$contFila = 0;
				foreach ($arrayDetalleAlmacen as $indiceDetalleAlmacen => $valorDetalleAlmacen) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$imgEstatusArticuloAlmacen = ($valorDetalleAlmacen['estatus_articulo_almacen'] == 1) ? "" : "<span class=\"textoRojoNegrita_10px\">Relacion Inactiva</span>";
					
					$classUbic = "";
					//$classUbic = ($valorDetalleAlmacen['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
					$classUbic = ($valorDetalleAlmacen['estatus_casilla'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
					//$ocupada = ($valorDetalleAlmacen['cantidad_ocupada'] > 0) ? "*" : "";
					
					$ubicacion = $valorDetalleAlmacen['ubicacion'];
					
					$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
						$htmlTb .= "<td class=\"".$classUbic."\" nowrap=\"nowrap\">";
							$htmlTb .= "<table width=\"100%\">";
							$htmlTb .= "<tr>";
								$htmlTb .= "<td align=\"left\" width=\"100%\">";
									$htmlTb .= "&nbsp;&nbsp;&nbsp;&nbsp;".utf8_encode(str_replace("-[]", "", $ubicacion));
								$htmlTb .= "</td>";
								$htmlTb .= "<td>";
									$htmlTb .= ($valorDetalleAlmacen['casilla_predeterminada'] == 1 && $valorDetalleAlmacen['id_casilla'] > 0) ? "<img src=\"../img/iconos/aprob_mecanico.png\" title=\"Ubicación Predeterminada para Venta\"/>" : "";
									$htmlTb .= ($valorDetalleAlmacen['casilla_predeterminada_compra'] == 1 && $valorDetalleAlmacen['id_casilla'] > 0) ? "&nbsp;<img src=\"../img/iconos/aprob_control_calidad.png\" title=\"Ubicación Predeterminada para Compra\"/>" : "";
									$htmlTb .= $imgEstatusArticuloAlmacen;
								$htmlTb .= "</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format($valorDetalleAlmacen['existencia'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format($valorDetalleAlmacen['cantidad_reservada'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format($valorDetalleAlmacen['cantidad_espera'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format($valorDetalleAlmacen['cantidad_bloqueada'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"right\" ".(($valorAlmacen['estatus_almacen_venta'] == 1 && $valorDetalleAlmacen['cantidad_disponible_logica'] > 0) ? "class=\"divMsjInfo\"" : "").">";
							$htmlTb .= valTpDato(number_format($valorDetalleAlmacen['cantidad_disponible_logica'], 2, ".", ","),"cero_por_vacio");
						$htmlTb .= "</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format($valorDetalleAlmacen['cantidad_pedida'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td align=\"right\">".valTpDato(number_format($valorDetalleAlmacen['cantidad_futura'], 2, ".", ","),"cero_por_vacio")."</td>";
					$htmlTb .= "</tr>";
				}
			}
		}
	} else {
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	$html .= "</table>";
	
	$objResponse->assign("divUbicaciones","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	return $objResponse;
}

function listaSolicitudServicio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	//$sqlBusq = " WHERE estatus_pedido_venta <> 3";
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_casilla = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	
	$query = sprintf("SELECT * FROM vw_iv_articulos_solicitud_venta %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td width=\"12%\">"."Fecha"."</td>";
		$htmlTh .= "<td width=\"16%\">"."Nro. Solicitud"."</td>";
		$htmlTh .= "<td width=\"16%\">"."Nro. Orden"."</td>";
		$htmlTh .= "<td width=\"44%\">"."Cliente"."</td>";
		$htmlTh .= "<td width=\"12%\">"."Cantidad"."</td>";
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estado_solicitud']) {
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Abierta\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Aprobada\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Despachada\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Devuelto\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Facturada\"/>"; break;
			case 6 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Anulada\"/>"; break;
			case 9 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris_parcial.gif\" title=\"Devuelta Parcial\"/>"; break;
			default : $imgEstatusPedido = "";
		}	
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['tiempo_solicitud']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_orden']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['total_cantidad']."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"verVentana('reportes/iv_solicitud_pdf.php?valBusq=%s|%s', 1010, 500);\" src=\"../img/iconos/ico_view.png\"/></td>",
				$row['id_empresa'],
				$row['id_solicitud']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaSolicitudServicio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudServicio(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaDcto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo2\" width=\"100%\">";
	$html .= "<tr>";
		$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\" width=\"25\"></td>";
		$html .= "<td align=\"center\">";
			$html .= "<table>";
			$html .= "<tr>";
				$html .= "<td><img src=\"../img/iconos/ico_azul.gif\"></td><td>Abierta</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_amarillo.gif\"></td><td>Aprobada</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_naranja.gif\"></td><td>Despachada</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_verde.gif\"></td><td>Facturada</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_gris_parcial.gif\"></td><td>Devuelta Parcial</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_gris.gif\"></td><td>Devuelta</td>";
				$html .= "<td>&nbsp;</td>";
				$html .= "<td><img src=\"../img/iconos/ico_rojo.gif\"></td><td>Anulada</td>";
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	$objResponse->assign("tdMsj","innerHTML",$html);
	
	$objResponse->assign("legendListaDcto","innerHTML","Solicitudes de Repuestos");
	$objResponse->script("byId('fieldsetListaDcto').style.display = '';");
	
	return $objResponse;
}

function listaUbicacionArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 120, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $valCadBusq[0];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.id_casilla > 0");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp_ubic.id_articulo = %s",
		valTpDato($valCadBusq[1], "int"));
	
	$query = sprintf("SELECT 
		vw_iv_art_emp_ubic.estatus_articulo_almacen,
		vw_iv_art_emp_ubic.id_empresa,
		vw_iv_art_emp_ubic.id_articulo,
		vw_iv_art_emp_ubic.id_articulo_almacen,
		vw_iv_art_emp_ubic.id_almacen,
		vw_iv_art_emp_ubic.id_calle,
		vw_iv_art_emp_ubic.id_estante,
		vw_iv_art_emp_ubic.id_tramo,
		vw_iv_art_emp_ubic.id_casilla,
		vw_iv_art_emp_ubic.estatus_almacen_venta,
		vw_iv_art_emp_ubic.estatus_almacen_compra,
		vw_iv_art_emp_ubic.casilla_predeterminada,
		vw_iv_art_emp_ubic.casilla_predeterminada_compra,
		vw_iv_art_emp_ubic.descripcion_almacen,
		vw_iv_art_emp_ubic.ubicacion,
		vw_iv_art_emp_ubic.existencia,
		vw_iv_art_emp_ubic.cantidad_reservada,
		vw_iv_art_emp_ubic.cantidad_disponible_fisica,
		vw_iv_art_emp_ubic.cantidad_espera,
		vw_iv_art_emp_ubic.cantidad_bloqueada,
		vw_iv_art_emp_ubic.cantidad_disponible_logica,
		vw_iv_art_emp_ubic.cantidad_pedida,
		vw_iv_art_emp_ubic.cantidad_futura,
		vw_iv_art_emp_ubic.orden_prioridad_venta,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		INNER JOIN vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic ON (vw_iv_emp_suc.id_empresa_reg = vw_iv_art_emp_ubic.id_empresa) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY vw_iv_art_emp_ubic.id_empresa ASC, %s %s", $campOrd, $tpOrd) : "";
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm4\" onclick=\"selecAllChecks(this.checked,this.id,'frmUbicacionArticulo');\"/></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaUbicacionArticulo", "20%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaUbicacionArticulo", "20%", $pageNum, "CONCAT(vw_iv_art_emp_ubic.descripcion_almacen, vw_iv_art_emp_ubic.ubicacion)", $campOrd, $tpOrd, $valBusq, $maxRows, "Ubicación");
		$htmlTh .= ordenarCampo("xajax_listaUbicacionArticulo", "10%", $pageNum, "existencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listaUbicacionArticulo", "10%", $pageNum, "cantidad_reservada", $campOrd, $tpOrd, $valBusq, $maxRows, "Reservada
(Serv.)");
		$htmlTh .= ordenarCampo("xajax_listaUbicacionArticulo", "10%", $pageNum, "cantidad_espera", $campOrd, $tpOrd, $valBusq, $maxRows, "Espera por Facturar");
		$htmlTh .= ordenarCampo("xajax_listaUbicacionArticulo", "10%", $pageNum, "cantidad_bloqueada", $campOrd, $tpOrd, $valBusq, $maxRows, "Bloqueada");
		$htmlTh .= ordenarCampo("xajax_listaUbicacionArticulo", "10%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Disponible");
		$htmlTh .= "<td><img src=\"../img/iconos/aprob_mecanico.png\" title=\"Ubicación Predeterminada para Venta\"/></td>";
		$htmlTh .= "<td><img src=\"../img/iconos/aprob_control_calidad.png\" title=\"Ubicación Predeterminada para Compra\"/></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaUbicacionArticulo", "10%", $pageNum, "orden_prioridad_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Orden");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$checked = ($row['casilla_predeterminada'] == 1) ? "checked=\"checked\"" : "";
		$checkedCompra = ($row['casilla_predeterminada_compra'] == 1) ? "checked=\"checked\"" : "";
		
		$aUp = "";
		$aDown = "";
		$cbxItmAlm = "";
		$classUbic = "";
		$ocupada = "";
		$rbtPredeterminado = "";
		$rbtPredeterminadoCompra = "";
		$htmlBtnUbic = "";
		$aEtiqueta = "";
		if ($row['estatus_articulo_almacen'] == 1) {
			if ($row['cantidad_disponible_fisica'] == 0 && $idEmpresa == $row['id_empresa']) {
				$cbxItmAlm = "<input id=\"cbxItm4\" name=\"cbxItm4[]\" type=\"checkbox\" value=\"".$row['id_articulo_almacen']."\"/>";
			}
			
			$imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Relacion Ubicación con Artículo Activa\"/>";
			
			if ($idEmpresa == $row['id_empresa']) {
				if ($row['estatus_almacen_venta'] == 1) {
					$rbtPredeterminado = "<input type=\"radio\" id=\"rbtPredeterminado".$contFila."\" name=\"rbtPredeterminado\" ".$checked." onclick=\"xajax_guardarAccion(".$row['id_articulo_almacen'].", 'predeterminado_venta', xajax.getFormValues('frmUbicacionArticulo'), xajax.getFormValues('frmBuscar'));\" value=\"".$contFila."\"/>";
				}
				if ($row['estatus_almacen_compra'] == 1) {
					$rbtPredeterminadoCompra = "<input type=\"radio\" id=\"rbtPredeterminadoCompra".$contFila."\" name=\"rbtPredeterminadoCompra\" ".$checkedCompra." onclick=\"xajax_guardarAccion(".$row['id_articulo_almacen'].", 'predeterminado_compra', xajax.getFormValues('frmUbicacionArticulo'), xajax.getFormValues('frmBuscar'));\" value=\"".$contFila."\"/>";
				}
				
				if ($row['orden_prioridad_venta'] > 1) {
					$aUp = "<a class=\"modalImg\" id=\"aUp".$contFila."\" rel=\"#divFlotante1\" onclick=\"xajax_guardarAccion(".$row['id_articulo_almacen'].", 'bajar_orden', xajax.getFormValues('frmUbicacionArticulo'), xajax.getFormValues('frmBuscar'));\"><img class=\"puntero\" src=\"../img/iconos/cross_up.png\" title=\"Subir\"/></a>";
				}
				
				if ($contFila < $totalRows) {
					$aDown = "<a class=\"modalImg\" id=\"aDown".$contFila."\" rel=\"#divFlotante1\" onclick=\"xajax_guardarAccion(".$row['id_articulo_almacen'].", 'subir_orden', xajax.getFormValues('frmUbicacionArticulo'), xajax.getFormValues('frmBuscar'));\"><img class=\"puntero\" src=\"../img/iconos/cross_down.png\" title=\"Bajar\"/></a>";
				}
			}
			
			$aEtiqueta = "<a class=\"modalImg\" id=\"aEtiqueta".$contFila."\" rel=\"#divFlotante1\" onclick=\"verVentana('reportes/iv_articulo_etiqueta_pdf.php?valBusq=".$row['id_articulo'].",".$row['id_casilla'].",".$row['id_articulo_costo'].",1', 400, 300);\"><img class=\"puntero\" src=\"../img/iconos/tag_blue.png\" title=\"Etiqueta\"/></a>";
		} else {
			// VERIFICA SI ALGUN ARTICULO TIENE LA UBICACION OCUPADA
			$queryCasilla = sprintf("SELECT
				casilla.*,
				
				(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
				WHERE art_alm.id_casilla = casilla.id_casilla
					AND art_alm.estatus = 1) AS cantidad_ocupada
			FROM iv_casillas casilla
			WHERE casilla.id_casilla = %s;",
				valTpDato($row['id_casilla'], "int"));
			$rsCasilla = mysql_query($queryCasilla);
			if (!$rsCasilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsCasilla = mysql_num_rows($rsCasilla);
			$rowCasilla = mysql_fetch_assoc($rsCasilla);
			
			$imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Relacion Ubicación con Artículo Inactiva\"/>";
			
			$classUbic = ($rowCasilla['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
			$classUbic = ($rowCasilla['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
			$ocupada = ($rowCasilla['cantidad_ocupada'] > 0) ? "*" : "";
			
			if ($rowCasilla['cantidad_ocupada'] == 0 && $rowCasilla['estatus'] == 1) {
				$htmlBtnUbic = "<button type=\"button\" id=\"btnActivarUbic".$contFila."\" name=\"btnActivarUbic".$contFila."\" onclick=\"xajax_activarUbicacion(".$row['id_articulo_almacen'].", xajax.getFormValues('frmUbicacionArticulo'));\" title=\"Activar Relación\"><img src=\"../img/iconos/accept.png\"/></button>";
			}
		}
		
		$classDisponible = ($row['cantidad_disponible_logica'] > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		
		$classReservada = ($row['cantidad_reservada'] > 0) ? "class=\"divMsjAlerta\"" : "";
		
		$classEspera = ($row['cantidad_espera'] > 0) ? "class=\"divMsjInfo2\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$cbxItmAlm."</td>";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".$classUbic."\">";
				$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode(strtoupper($row['descripcion_almacen']))."</div>";
				$htmlTb .= "<div>".utf8_encode(str_replace("-[]", "", $row['ubicacion'])).$ocupada."</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['existencia'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classReservada.">".number_format($row['cantidad_reservada'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classEspera.">".number_format($row['cantidad_espera'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cantidad_bloqueada'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible.">".number_format($row['cantidad_disponible_logica'],2,".",",")."</td>";
			if (strlen($htmlBtnUbic) > 0) {
				$htmlTb .= "<td align=\"center\" colspan=\"2\">".$htmlBtnUbic."</td>";
			} else {
				$htmlTb .= "<td>".$rbtPredeterminado."</td>";
				$htmlTb .= "<td>".$rbtPredeterminadoCompra."</td>";
			}
			$htmlTb .= "<td>".$aEtiqueta."</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr><td>".$aUp."</td><td align=\"right\" rowspan=\"2\" width=\"100%\">".$row['orden_prioridad_venta']."</td></tr>";
				$htmlTb .= "<tr><td>".$aDown."</td></tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUbicacionArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 

							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUbicacionArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUbicacionArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUbicacionArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUbicacionArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"14\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUbicacionArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarPermiso($valForm) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($valForm['txtContrasena'], "text"),
		valTpDato($valForm['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		if ($valForm['hddAccion'] == 1) {
			$objResponse->script("
			if (confirm('¿Seguro desea eliminar los Registros Seleccionados?') == true) {
				xajax_eliminarArticuloLote(xajax.getFormValues('frmListaArticulos'), xajax.getFormValues('frmBuscar'));
				$('#btnCancelarPermiso').click();
			}");
		} else if ($valForm['hddAccion'] == 2) {
			$objResponse->script(sprintf("
			if (confirm('¿Seguro desea eliminar el Registro?') == true) {
				xajax_eliminarArticulo('%s', xajax.getFormValues('frmListaArticulos'), xajax.getFormValues('frmBuscar'));
				$('#btnCancelarPermiso').click();
			}",
				$valForm['hddFrm']));
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"activarArticulo");
$xajax->register(XAJAX_FUNCTION,"activarUbicacion");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarModelo");
$xajax->register(XAJAX_FUNCTION,"cargaLstCalleEstanteTramoCasilla");
$xajax->register(XAJAX_FUNCTION,"cargaLstClasificacion");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarArticuloLote");
$xajax->register(XAJAX_FUNCTION,"eliminarModeloArticuloLote");
$xajax->register(XAJAX_FUNCTION,"eliminarUbicacionArticuloLote");
$xajax->register(XAJAX_FUNCTION,"exportarArticulos");
$xajax->register(XAJAX_FUNCTION,"formArticulo");
$xajax->register(XAJAX_FUNCTION,"formAlmacen");
$xajax->register(XAJAX_FUNCTION,"formDatosArticulo");
$xajax->register(XAJAX_FUNCTION,"formModelo");
$xajax->register(XAJAX_FUNCTION,"formModeloArticulo");
$xajax->register(XAJAX_FUNCTION,"formImportarArticulo");
$xajax->register(XAJAX_FUNCTION,"formSaldos");
$xajax->register(XAJAX_FUNCTION,"formUbicacionArticulo");
$xajax->register(XAJAX_FUNCTION,"guardarAccion");
$xajax->register(XAJAX_FUNCTION,"guardarModeloArticulo");
$xajax->register(XAJAX_FUNCTION,"guardarUbicacionArticulo");
$xajax->register(XAJAX_FUNCTION,"guardarVentaPerdida");
$xajax->register(XAJAX_FUNCTION,"importarArticulo");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaBloqueoVenta");
$xajax->register(XAJAX_FUNCTION,"listaCostoProveedor");
$xajax->register(XAJAX_FUNCTION,"listaModelo");
$xajax->register(XAJAX_FUNCTION,"listaModeloArticulo");
$xajax->register(XAJAX_FUNCTION,"listaPedidoVenta");
$xajax->register(XAJAX_FUNCTION,"listaPedidoCompra");
$xajax->register(XAJAX_FUNCTION,"listaPrecios");
$xajax->register(XAJAX_FUNCTION,"listaSaldos");
$xajax->register(XAJAX_FUNCTION,"listaSolicitudServicio");
$xajax->register(XAJAX_FUNCTION,"listaUbicacionArticulo");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function buscarEnArray($array, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($array as $indice => $valor) {
		if ($valor == $dato) return $x;
		
		$x++;
	}
	return NULL;
}
?>