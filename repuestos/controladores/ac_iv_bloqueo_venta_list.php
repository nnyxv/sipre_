<?php


function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['cbxVerConItemsBloq'],
		$frmBuscar['cbxVerSinItemsBloq'],
		$frmBuscar['lstClaveMovimiento'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaRegistroCompra(0, "id_bloqueo_venta", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarArticulo($frmBuscarArticulo, $frmBloqueoVenta){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmBloqueoVenta['cbx1'];
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $frmBuscarArticulo['hddCantCodigo']; $cont++) {
		$codArticulo .= $frmBuscarArticulo['txtCodigoArticulo'.$cont].";";
	}
	$auxCodArticulo = $codArticulo;
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_casilla IS NOT NULL");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
		AND vw_iv_art_almacen_costo.id_articulo_costo IS NOT NULL)
	OR (vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo IS NULL
		AND vw_iv_art_almacen_costo.estatus_articulo_costo IS NULL
		AND vw_iv_art_almacen_costo.id_articulo_costo IS NULL)
	OR (vw_iv_art_almacen_costo.cantidad_disponible_logica > 0)
	OR (vw_iv_art_almacen_costo.cantidad_reservada > 0))");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
	
	if (strlen($frmBloqueoVenta['txtIdEmpresa']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_empresa = %s",
			valTpDato($frmBloqueoVenta['txtIdEmpresa'], "int"));
	}
	
	if ($auxCodArticulo != "---") {
		if ($codArticulo != "-1" && $codArticulo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
				valTpDato($codArticulo, "text"));
		}
	}
	
	if (strlen($frmBuscarArticulo['txtCriterioBuscarArticulo']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($frmBuscarArticulo['lstBuscarArticulo']) {
			case 1 :
				$sqlBusq .= $cond.sprintf("(SELECT marca.marca FROM iv_marcas marca
				WHERE marca.id_marca = art.id_marca) LIKE %s",
					valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text"));
				break;
			case 2 :
				$sqlBusq .= $cond.sprintf("(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
				WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) LIKE %s",
					valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text"));
				break;
			case 3 :
				$sqlBusq .= $cond.sprintf("(SELECT sec.descripcion
				FROM iv_subsecciones subsec
					INNER JOIN iv_secciones sec ON (subsec.id_seccion = sec.id_seccion)
				WHERE subsec.id_subseccion = vw_iv_art_emp_ubic.id_subseccion) LIKE %s",
					valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text"));
				break;
			case 4 :
				$sqlBusq .= $cond.sprintf("(SELECT subsec.descripcion FROM iv_subsecciones subsec
				WHERE subsec.id_subseccion = vw_iv_art_emp_ubic.id_subseccion) LIKE %s",
					valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text"));
				break;
			case 5 : $sqlBusq .= $cond.sprintf("art.descripcion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 6 : $sqlBusq .= $cond.sprintf("art.id_articulo = %s", valTpDato($frmBuscarArticulo['txtCriterioBuscarArticulo'], "int")); break;
			case 7 : $sqlBusq .= $cond.sprintf("art.codigo_articulo_prov LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
		}
	}
		
	$objResponse->assign("divListaArticulo","innerHTML","");
	
	if ($auxCodArticulo != "---" || strlen($frmBuscarArticulo['txtCriterioBuscarArticulo']) > 0) {
		$query = sprintf("SELECT vw_iv_art_almacen_costo.*,
			
			(SELECT marca.marca FROM iv_marcas marca
			WHERE marca.id_marca = art.id_marca) AS marca,
			
			(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
			WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) AS tipo_articulo,
			
			(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
			WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
			
			(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
					WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_almacen_costo.id_empresa)
				WHEN 1 THEN	vw_iv_art_almacen_costo.costo
				WHEN 2 THEN	vw_iv_art_almacen_costo.costo_promedio
				WHEN 3 THEN	vw_iv_art_almacen_costo.costo
			END) AS costo
		FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
			INNER JOIN iv_articulos art ON (vw_iv_art_almacen_costo.id_articulo = art.id_articulo) %s", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
		if ($totalRows == 1) {
			$row = mysql_fetch_assoc($rs);
			
			// VERIFICA SI ALGUN ARTICULO YA ESTA INCLUIDO EN EL DOCUMENTO
			$existe = false;
			if (isset($arrayObj1)) {
				foreach ($arrayObj1 as $indice => $valor) {
					if ($frmBloqueoVenta['hddIdArticuloAlmacenCosto'.$valor] == $row['id_articulo_almacen_costo']) {
						$objResponse->alert("El(Los) registro(s): ".elimCaracter($row['codigo_articulo'],";")." Lote ".$row['id_articulo_costo']." ya se encuentra(n) incluido(s)");
						$existe = true;
					}
				}
			}
			
			if ($existe == false) {
				$objResponse->script(sprintf("xajax_insertarArticulo('%s', xajax.getFormValues('frmBloqueoVenta'), 'false');",
					$row['id_articulo_almacen_costo']));
			}
			
			$objResponse->script("
			byId('txtCriterioBuscarArticulo').value = '';");
		} else if ($totalRows > 1) {
			$valBusq = sprintf("%s|%s|%s|%s",
						$frmBloqueoVenta['txtIdEmpresa'],
						$codArticulo,
						$frmBuscarArticulo['lstBuscarArticulo'],
						$frmBuscarArticulo['txtCriterioBuscarArticulo']);
			
			$objResponse->loadCommands(listaArticulo(0, "CONCAT(descripcion_almacen, ubicacion)", "ASC", $valBusq));
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

function buscarArticuloBloqueo($frmDesbloqueoVenta) {
	$objResponse = new xajaxResponse();
	
	$codArticulo = "";
	if (isset($frmDesbloqueoVenta['hddCantCodigoDesbloqueo'])){
		for ($cont = 0; $cont <= $frmDesbloqueoVenta['hddCantCodigoDesbloqueo']; $cont++) {
			$codArticulo .= $frmDesbloqueoVenta['txtCodigoArticuloDesbloqueo'.$cont].";";
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = codArticuloExpReg($codArticulo);
	}
	
	$valBusq = sprintf("%s|%s|%s",
		$frmDesbloqueoVenta['hddIdBloqueoVenta'],
		$codArticulo,
		$frmDesbloqueoVenta['txtCriterioDesbloqueoVenta']);
	
	$objResponse->loadCommands(listaArticuloBloqueo(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarArticuloBloque($frmBuscarArticuloBloque, $frmBloqueoVenta) {
	$objResponse = new xajaxResponse();
	
	$codArticulo = "";
	if (isset($frmBuscarArticuloBloque['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscarArticuloBloque['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscarArticuloBloque['txtCodigoArticulo'.$cont].";";
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = codArticuloExpReg($codArticulo);
	}
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBloqueoVenta['txtIdEmpresa'],
		$frmBuscarArticuloBloque['lstTipoArticulo'],
		$frmBuscarArticuloBloque['lstVerClasificacion'],
		$codArticulo);
	
	$objResponse->loadCommands(listaArticuloBloque(0, "CONCAT(descripcion_almacen, ubicacion)", "ASC", $valBusq));
	
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

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
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
	$html = "<select id=\"lstTipoArticulo\" name=\"lstTipoArticulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscarArticuloBloque').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$row['id_tipo_articulo']."\" ".$selected.">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function eliminarArticulo($frmBloqueoVenta) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBloqueoVenta['cbxItm'])) {
		foreach ($frmBloqueoVenta['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarArticulo(xajax.getFormValues('frmBloqueoVenta'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmBloqueoVenta['cbx1'];
	if (isset($arrayObj1)) {
		$i = 0;
		foreach ($arrayObj1 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	
	if (count($arrayObj1) > 0) {
		$objResponse->script("
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('aListarEmpresa').style.display = 'none';");
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').readOnly = false;
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('aListarEmpresa').style.display = '';");
	}
		
	return $objResponse;
}

function formBloqueoVenta($frmBloqueoVenta) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_bloqueo_venta_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarBloqueo').click();"); return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmBloqueoVenta['cbx1'];
	
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice1 => $valor1) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valor1."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
	
	$objResponse->script("xajax_eliminarArticulo(xajax.getFormValues('frmBloqueoVenta'));");
	
	return $objResponse;
}

function formDesbloqueoArticulo($idBloqueoVentaDetalle) {
	$objResponse = new xajaxResponse();
	
	// BUSCA LOS DATOS DEL DETALLE BLOQUEADO
	$query = sprintf("SELECT 
		bloqueo_vent_det.id_bloqueo_venta_detalle,
		bloqueo_vent_det.id_bloqueo_venta,
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		bloqueo_vent_det.cantidad,
		tp_unidad.unidad,
		tp_unidad.decimales
	FROM iv_bloqueo_venta_detalle bloqueo_vent_det
		INNER JOIN iv_articulos art ON (bloqueo_vent_det.id_articulo = art.id_articulo)
		INNER JOIN iv_tipos_unidad tp_unidad ON (art.id_tipo_unidad = tp_unidad.id_tipo_unidad)
	WHERE bloqueo_vent_det.id_bloqueo_venta_detalle = %s;",
		valTpDato($idBloqueoVentaDetalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdBloqueoVentaDetalle","value",$row['id_bloqueo_venta_detalle']);
	$objResponse->assign("txtCodigoArt","value",elimCaracter(utf8_encode($row['codigo_articulo']),";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($row['descripcion']));
	$objResponse->assign("txtCantidadArt","value",$row['cantidad']);
	$objResponse->assign("txtUnidadArt","value",$row['unidad']);
	
	$objResponse->script(sprintf("
	if (navigator.appName == 'Netscape') {
		byId('txtCantidadArt').onblur = function(e){ %s }
		byId('txtCantidadArt').onkeypress = function(e){ %s }
	} else if (navigator.appName == 'Microsoft Internet Explorer') {
		byId('txtCantidadArt').onblur = function(e){ %s }
		byId('txtCantidadArt').onkeypress = function(e){ %s }
	}",
		(($rowArticulo['decimales'] == 0) ? "setFormatoRafk(this,0);" : "setFormatoRafk(this,2);"),
		(($rowArticulo['decimales'] == 0) ? "return validarSoloNumeros(e);" : "return validarSoloNumerosReales(e);"),
		(($rowArticulo['decimales'] == 0) ? "setFormatoRafk(this,0);" : "setFormatoRafk(this,2);"),
		(($rowArticulo['decimales'] == 0) ? "return validarSoloNumeros(event);" : "return validarSoloNumerosReales(event);")));
	
	return $objResponse;
}

function formDesbloqueoVenta($idBloqueoVenta) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_bloqueo_venta_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarDesbloqueoVenta').click();"); return $objResponse; }
	
	// BUSCA LOS DATOS DEL REGISTRO DE COMPRA
	$query = sprintf("SELECT *
	FROM iv_bloqueo_venta bloqueo_vent
		LEFT JOIN cp_factura fact_comp ON (bloqueo_vent.id_factura_compra = fact_comp.id_factura)
	WHERE id_bloqueo_venta = %s",
		valTpDato($idBloqueoVenta, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->loadCommands(objetoCodigoDinamico('tdCodigoArtDesbloqueo', $row['id_empresa'], "", "", "", false, "Desbloqueo"));
	
	$objResponse->assign("hddIdBloqueoVenta","value",$idBloqueoVenta);
	
	$objResponse->loadCommands(listaArticuloBloqueo(0, "", "", $idBloqueoVenta));
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Artículos Bloqueados del Bloqueo <b>Nro. ".$row['id_bloqueo_venta']."</b>");
	
	return $objResponse;
}

function guardarBloqueo($frmBloqueoVenta, $frmListaRegistroCompra) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmBloqueoVenta['cbx1'];
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmBloqueoVenta['txtIdEmpresa'];
	$idBloqueoVenta = $frmBloqueoVenta['hddIdBloqueoVenta'];
	
	if ($idBloqueoVenta > 0) {
		if (!xvalidaAcceso($objResponse,"iv_bloqueo_venta_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE iv_bloqueo_venta SET
			id_empleado = %s
		WHERE id_bloqueo_venta = %s;",
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($idBloqueoVenta, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_bloqueo_venta_list","insertar")) { return $objResponse; }
		
		// BUSCA EL PRIMER BLOQUEO QUE NO SEA DE FACTURA
		$query = sprintf("SELECT * FROM iv_bloqueo_venta
		WHERE id_empresa = %s
			AND id_factura_compra IS NULL;",
			valTpDato($idEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
		
		if ($totalRows > 0) {
			$idBloqueoVenta = $row['id_bloqueo_venta'];
		} else {
			$insertSQL = sprintf("INSERT INTO iv_bloqueo_venta (id_empresa, id_empleado, fecha_bloqueo)
			VALUE (%s, %s, NOW());",
				valTpDato($idEmpresa, "int"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$idBloqueoVenta = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice => $valor) {
			// BUSCA LOS DATOS DEL ARTICULO Y UBICACION
			$query = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
			WHERE vw_iv_art_almacen_costo.id_articulo_almacen_costo = %s;",
				valTpDato($frmBloqueoVenta['hddIdArticuloAlmacenCosto'.$valor], "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$idArticulo = $row['id_articulo'];
			$idCasilla = $row['id_casilla'];
			
			$insertSQL = sprintf("INSERT INTO iv_bloqueo_venta_detalle (id_bloqueo_venta, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, cantidad_bloquear, cantidad, id_empleado_bloqueo, estatus)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idBloqueoVenta, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($row['id_articulo_almacen_costo'], "real_inglesa"),
				valTpDato($row['id_articulo_costo'], "real_inglesa"),
				valTpDato($row['cantidad_disponible_logica'], "real_inglesa"),
				valTpDato($row['cantidad_disponible_logica'], "real_inglesa"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato(1, "real_inglesa")); // 1 = Bloqueado, 2 = Desbloqueado, 3 = Bloqueado Parcial
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (BLOQUEADA)
			$Result1 = actualizarBloqueada($idArticulo, $idCasilla);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Bloqueo de Venta Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarBloqueo').click();");
	
	$objResponse->loadCommands(listaRegistroCompra(
		$frmListaRegistroCompra['pageNum'],
		$frmListaRegistroCompra['campOrd'],
		$frmListaRegistroCompra['tpOrd'],
		$frmListaRegistroCompra['valBusq']));
	
	return $objResponse;
}

function guardarDesbloqueoArticulo($frmDesbloqueoArticulo, $frmDesbloqueoVenta, $frmListaRegistroCompra) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idBloqueoVentaDetalle = $frmDesbloqueoArticulo['hddIdBloqueoVentaDetalle'];
	
	// BUSCA LOS DATOS DEL DETALLE BLOQUEADO
	$query = sprintf("SELECT * FROM iv_bloqueo_venta_detalle WHERE id_bloqueo_venta_detalle = %s;",
		valTpDato($idBloqueoVentaDetalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	if ($frmDesbloqueoArticulo['txtCantidadDesbloquear'] > $row['cantidad']) {
		$objResponse->script("byId('txtCantidadDesbloquear').className = 'inputErrado'");
		
		$objResponse->loadCommands(formDesbloqueoArticulo($idBloqueoVentaDetalle));
		
		return $objResponse->alert("Los campos señalados en rojo son invalidos");
	}
	
	$idArticulo = $row['id_articulo'];
	$idCasilla = $row['id_casilla'];
	
	$updateSQL = sprintf("UPDATE iv_bloqueo_venta_detalle SET
		cantidad = cantidad - %s,
		id_empleado_desbloqueo = %s,
		fecha_desbloqueo = NOW()
	WHERE id_bloqueo_venta_detalle = %s;",
		valTpDato($frmDesbloqueoArticulo['txtCantidadDesbloquear'], "real_inglesa"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($idBloqueoVentaDetalle, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// 1 = Bloqueado, 2 = Desbloqueado, 3 = Bloqueado Parcial
	$updateSQL = sprintf("UPDATE iv_bloqueo_venta_detalle SET
		estatus = (CASE 
						WHEN cantidad = 0 THEN 2
						WHEN cantidad = cantidad_bloquear THEN 1
						WHEN cantidad < cantidad_bloquear THEN 3
					END)
	WHERE id_bloqueo_venta_detalle = %s;",
		valTpDato($idBloqueoVentaDetalle, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
				
	// ACTUALIZA LOS SALDOS DEL ARTICULO (BLOQUEADA)
	$Result1 = actualizarBloqueada($idArticulo, $idCasilla);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
	mysql_query("COMMIT;");
	
	$objResponse->alert("Desbloqueo de Venta Guardado con Éxito");
	
	$objResponse->script("byId('btnCancelarArticuloBloque').click();");
	
	$objResponse->loadCommands(listaArticuloBloqueo(
		$frmDesbloqueoVenta['pageNum'],
		$frmDesbloqueoVenta['campOrd'],
		$frmDesbloqueoVenta['tpOrd'],
		$frmDesbloqueoVenta['valBusq']));
	
	$objResponse->loadCommands(listaRegistroCompra(
		$frmListaRegistroCompra['pageNum'],
		$frmListaRegistroCompra['campOrd'],
		$frmListaRegistroCompra['tpOrd'],
		$frmListaRegistroCompra['valBusq']));
	
	return $objResponse;
}

function insertarArticulo($idArticuloAlmacenCosto, $frmBloqueoVenta, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmBloqueoVenta['cbx1'];
	$contFila1 = $arrayObj1[count($arrayObj1)-1];
	
	$existe = false;
	if (isset($arrayObj1)) {
		foreach ($arrayObj1 as $indice => $valor) {
			if ($frmBloqueoVenta['hddIdArticuloAlmacenCosto'.$valor] == $idArticuloAlmacenCosto) {
				// BUSCA LOS DATOS DEL ARTICULO
				$query = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
					valTpDato($frmBloqueoVenta['hddIdArticuloItm'.$valor], "int"));
				$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRows = mysql_num_rows($rs);
				$row = mysql_fetch_assoc($rs);
				
				$arrayArticuloRepetido[] = elimCaracter($row['codigo_articulo'],";")." Lote ".$frmBloqueoVenta['hddIdArticuloCosto'.$valor];
				
				$existe = true;
			}
		}
	}
	
	if ($existe == false) {
		$Result1 = insertarItemArticulo($contFila1, $idArticuloAlmacenCosto);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila1 = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj1[] = $contFila1;
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($arrayObj1)) {
		$i = 0;
		foreach ($arrayObj1 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	
	if (count($arrayObj1) > 0) {
		$objResponse->script("
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('aListarEmpresa').style.display = 'none';");
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').readOnly = false;
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('aListarEmpresa').style.display = '';");
	}
	
	if (count($arrayArticuloRepetido) > 0) {
		$objResponse->alert(utf8_encode("El(Los) registro(s):\n\n".implode(", ",$arrayArticuloRepetido)."\n\nno fue(ron) agregados(s) debido a que ya se encuentra(n) incluido(s)."));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarArticuloBloque').click();");
	}
	
	return $objResponse;
}

function insertarArticuloBloque($frmListaArticuloBloque, $frmBloqueoVenta, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj1 = $frmBloqueoVenta['cbx1'];
	$contFila1 = $arrayObj1[count($arrayObj1)-1];
	
	if (isset($frmListaArticuloBloque['cbxItm'])) {
		foreach ($frmListaArticuloBloque['cbxItm'] as $indiceItm => $valorItm) {
			$existe = false;
			if (isset($arrayObj1)) {
				foreach ($arrayObj1 as $indice => $valor) {
					if ($frmBloqueoVenta['hddIdArticuloAlmacenCosto'.$valor] == $valorItm) {
						// BUSCA LOS DATOS DEL ARTICULO
						$query = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
							valTpDato($frmBloqueoVenta['hddIdArticuloItm'.$valor], "int"));
						$rs = mysql_query($query);
						if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRows = mysql_num_rows($rs);
						$row = mysql_fetch_assoc($rs);
						
						$arrayArticuloRepetido[] = elimCaracter($row['codigo_articulo'],";")." Lote ".$frmBloqueoVenta['hddIdArticuloCosto'.$valor];
						
						$existe = true;
					}
				}
			}
			
			if ($existe == false) {
				$Result1 = insertarItemArticulo($contFila1, $valorItm);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila1 = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj1[] = $contFila1;
				}
			}
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($arrayObj1)) {
		$i = 0;
		foreach ($arrayObj1 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	
	if (count($arrayObj1) > 0) {
		$objResponse->script("
		byId('txtIdEmpresa').readOnly = true;
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('aListarEmpresa').style.display = 'none';");
	} else {
		$objResponse->script("
		byId('txtIdEmpresa').readOnly = false;
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('aListarEmpresa').style.display = '';");
	}
	
	if (count($arrayArticuloRepetido) > 0) {
		$objResponse->alert(utf8_encode("El(Los) registro(s):\n\n".implode(", ",$arrayArticuloRepetido)."\n\nno fue(ron) agregados(s) debido a que ya se encuentra(n) incluido(s)."));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarArticuloBloque').click();");
	}
	
	return $objResponse;
}

function listaArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_casilla IS NOT NULL");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
		AND vw_iv_art_almacen_costo.id_articulo_costo IS NOT NULL)
	OR (vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo IS NULL
		AND vw_iv_art_almacen_costo.estatus_articulo_costo IS NULL
		AND vw_iv_art_almacen_costo.id_articulo_costo IS NULL)
	OR (vw_iv_art_almacen_costo.cantidad_disponible_logica > 0)
	OR (vw_iv_art_almacen_costo.cantidad_reservada > 0))");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_empresa = %s",
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
				WHERE marca.id_marca = art.id_marca) LIKE %s",
					valTpDato("%".$valCadBusq[3]."%", "text"));
				break;
			case 2 :
				$sqlBusq .= $cond.sprintf("(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
				WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) LIKE %s",
					valTpDato("%".$valCadBusq[3]."%", "text"));
				break;
			case 3 :
				$sqlBusq .= $cond.sprintf("(SELECT sec.descripcion
				FROM iv_subsecciones subsec
					INNER JOIN iv_secciones sec ON (subsec.id_seccion = sec.id_seccion)
				WHERE subsec.id_subseccion = vw_iv_art_emp_ubic.id_subseccion) LIKE %s",
					valTpDato("%".$valCadBusq[3]."%", "text"));
				break;
			case 4 :
				$sqlBusq .= $cond.sprintf("(SELECT subsec.descripcion FROM iv_subsecciones subsec
				WHERE subsec.id_subseccion = vw_iv_art_emp_ubic.id_subseccion) LIKE %s",
					valTpDato("%".$valCadBusq[3]."%", "text"));
				break;
			case 5 : $sqlBusq .= $cond.sprintf("art.descripcion LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text")); break;
			case 6 : $sqlBusq .= $cond.sprintf("art.id_articulo = %s", valTpDato($valCadBusq[3], "int")); break;
			case 7 : $sqlBusq .= $cond.sprintf("art.codigo_articulo_prov LIKE %s", valTpDato("%".$valCadBusq[3]."%", "text")); break;
		}
	}
	
	$query = sprintf("SELECT vw_iv_art_almacen_costo.*,
		
		(SELECT marca.marca FROM iv_marcas marca
		WHERE marca.id_marca = art.id_marca) AS marca,
		
		(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
		WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) AS tipo_articulo,
		
		(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
		
		(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
				WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_almacen_costo.id_empresa)
			WHEN 1 THEN	vw_iv_art_almacen_costo.costo
			WHEN 2 THEN	vw_iv_art_almacen_costo.costo_promedio
			WHEN 3 THEN	vw_iv_art_almacen_costo.costo
		END) AS costo
	FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		INNER JOIN iv_articulos art ON (vw_iv_art_almacen_costo.id_articulo = art.id_articulo) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Código"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "42%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "16%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, ("Marca"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Clasif."));
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "10%", $pageNum, "CONCAT(descripcion_almacen, ubicacion)", $campOrd, $tpOrd, $valBusq, $maxRows, ("Ubicación"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "6%", $pageNum, "id_articulo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Lote");
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "6%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Disponible");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$cantDisponible = $row['cantidad_disponible_logica']; // Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
		
		$classEstatusAlmacen = ($row['estatus_articulo_almacen'] == 1) ? "class=\"texto_9px\"" : "class=\"divMsjError texto_9px\"";
		
		$classDisponible = ($cantDisponible > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		
		$classReservada = ($cantReservada > 0) ? "class=\"divMsjAlerta\"" : "";
		
		$classEspera = ($cantEspera > 0) ? "class=\"divMsjInfo2\"" : "";
		
		$classBloqueada = ($cantBloqueada > 0) ? "class=\"divMsjInfo3\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= "<button type=\"button\" id=\"btnInsertarArticulo".$contFila."\" onclick=\"validarInsertarArticulo('".$row['id_articulo_almacen_costo']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca']." (".$row['tipo_articulo'].")")."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".utf8_encode("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".utf8_encode("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".utf8_encode("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".utf8_encode("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".utf8_encode("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".utf8_encode("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$classEstatusAlmacen." nowrap=\"nowrap\">";
				$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode(strtoupper($row['descripcion_almacen']))."</div>";
				$htmlTb .= "<div>".utf8_encode(str_replace("-[]", "", $row['ubicacion']))."</div>";
				$htmlTb .= ($row['estatus_articulo_almacen'] == 1) ? "" : "<div class=\"textoRojoNegrita_10px\">(Relacion Inactiva)</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_articulo_costo'])."</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible.">".number_format($cantDisponible, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaArticuloBloqueo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("bloqueo_vent_det.id_bloqueo_venta = %s",
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s
		OR bloqueo_vent_det.id_articulo_costo LIKE %s)",
			valTpDato($valCadBusq[2], "int"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT bloqueo_vent_det.*,
		art.codigo_articulo,
		art.descripcion,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		vw_iv_casilla.descripcion_almacen,
		vw_iv_casilla.ubicacion
	FROM iv_bloqueo_venta_detalle bloqueo_vent_det
		INNER JOIN iv_articulos art ON (bloqueo_vent_det.id_articulo = art.id_articulo)
		LEFT JOIN cj_cc_cliente cliente ON (bloqueo_vent_det.id_cliente = cliente.id)
		INNER JOIN vw_iv_casillas vw_iv_casilla ON (bloqueo_vent_det.id_casilla = vw_iv_casilla.id_casilla) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloqueo", "22%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloqueo", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloqueo", "24%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloqueo", "16%", $pageNum, "CONCAT(descripcion_almacen, ubicacion)", $campOrd, $tpOrd, $valBusq, $maxRows, "Ubicación");
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloqueo", "8%", $pageNum, "id_articulo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Lote");
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloqueo", "8%", $pageNum, "cantidad_bloquear", $campOrd, $tpOrd, $valBusq, $maxRows, "Bloqueadas");
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloqueo", "8%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Restantes");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++;
		
		switch($row['estatus']) {
			//case 1 : $imgEstatusArt = "<img src=\"../img/iconos/lock.png\" title=\"Bloqueado\"/>"; break;
			case 2 : $imgEstatusArt = "<img src=\"../img/iconos/lock_open.png\" title=\"Desbloqueado\"/>"; break;
			default : $imgEstatusArt = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusArt."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\" ".$classEstatusAlmacen." nowrap=\"nowrap\">";
				$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode(strtoupper($row['descripcion_almacen']))."</div>";
				$htmlTb .= "<div>".utf8_encode(str_replace("-[]", "", $row['ubicacion']))."</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_articulo_costo']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cantidad_bloquear'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cantidad'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if (in_array($row['estatus'],array(1,3))) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDesbloqueoArticulo%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblDesbloqueoArticulo', '%s');\"><img class=\"puntero\" src=\"../img/iconos/lock.png\" title=\"Desbloquear Artículo\"/></a>",
					$contFila,
					$row['id_bloqueo_venta_detalle']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloBloqueo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloBloqueo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticuloBloqueo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloBloqueo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloBloqueo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	$objResponse->assign("divListaArticuloBloqueo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaArticuloBloque($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_casilla IS NOT NULL");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
		AND vw_iv_art_almacen_costo.id_articulo_costo IS NOT NULL)
	OR (vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo IS NULL
		AND vw_iv_art_almacen_costo.estatus_articulo_costo IS NULL
		AND vw_iv_art_almacen_costo.id_articulo_costo IS NULL)
	OR (vw_iv_art_almacen_costo.cantidad_disponible_logica > 0)
	OR (vw_iv_art_almacen_costo.cantidad_reservada > 0))");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.clasificacion LIKE %s",
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[3], "text"));
	}
	
	$query = sprintf("SELECT vw_iv_art_almacen_costo.*,
		
		(SELECT marca.marca FROM iv_marcas marca
		WHERE marca.id_marca = art.id_marca) AS marca,
		
		(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
		WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) AS tipo_articulo,
		
		(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
		
		(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
				WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_almacen_costo.id_empresa)
			WHEN 1 THEN	vw_iv_art_almacen_costo.costo
			WHEN 2 THEN	vw_iv_art_almacen_costo.costo_promedio
			WHEN 3 THEN	vw_iv_art_almacen_costo.costo
		END) AS costo
	FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		INNER JOIN iv_articulos art ON (vw_iv_art_almacen_costo.id_articulo = art.id_articulo) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"scroll texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna texto_10px\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,8);\"/></td>";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Código"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "42%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "16%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, ("Marca"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Clasif."));
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "10%", $pageNum, "CONCAT(descripcion_almacen, ubicacion)", $campOrd, $tpOrd, $valBusq, $maxRows, ("Ubicación"));
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "6%", $pageNum, "id_articulo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Lote");
		$htmlTh .= ordenarCampo("xajax_listaArticuloBloque", "6%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, "Unid. Disponible");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$cantDisponible = $row['cantidad_disponible_logica']; // Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
		
		$classEstatusAlmacen = ($row['estatus_articulo_almacen'] == 1) ? "class=\"texto_9px\"" : "class=\"divMsjError texto_9px\"";
		
		$classDisponible = ($cantDisponible > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		
		$classReservada = ($cantReservada > 0) ? "class=\"divMsjAlerta\"" : "";
		
		$classEspera = ($cantEspera > 0) ? "class=\"divMsjInfo2\"" : "";
		
		$classBloqueada = ($cantBloqueada > 0) ? "class=\"divMsjInfo3\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
				$row['id_articulo_almacen_costo']);
			$htmlTb.= "<td align=\"center\" class=\"textoNegrita_9px\">".($contFila + (($pageNum) * $maxRows))."</td>"; // <----
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca']." (".$row['tipo_articulo'].")")."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".utf8_encode("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".utf8_encode("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".utf8_encode("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".utf8_encode("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".utf8_encode("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".utf8_encode("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$classEstatusAlmacen." nowrap=\"nowrap\">";
				$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode(strtoupper($row['descripcion_almacen']))."</div>";
				$htmlTb .= "<div>".utf8_encode(str_replace("-[]", "", $row['ubicacion']))."</div>";
				$htmlTb .= ($row['estatus_articulo_almacen'] == 1) ? "" : "<div class=\"textoRojoNegrita_10px\">(Relacion Inactiva)</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_articulo_costo'])."</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible.">".number_format($cantDisponible, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[9] += $cantDisponible;
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[9], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				
				$cantDisponible = $row['cantidad_disponible_logica']; // Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
				
				$classEstatusAlmacen = ($row['estatus_articulo_almacen'] == 1) ? "class=\"texto_9px\"" : "class=\"divMsjError texto_9px\"";
				
				$classDisponible = ($cantDisponible > 0) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
				
				$classReservada = ($cantReservada > 0) ? "class=\"divMsjAlerta\"" : "";
				
				$classEspera = ($cantEspera > 0) ? "class=\"divMsjInfo2\"" : "";
				
				$classBloqueada = ($cantBloqueada > 0) ? "class=\"divMsjInfo3\"" : "";
				
				$arrayTotalFinal[9] += $cantDisponible;
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[9], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
		}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloBloque(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloBloque(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticuloBloque(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloBloque(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticuloBloque(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListaArticuloBloque","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	return $objResponse;
}

function listaRegistroCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(id_modulo IN (0)
	OR id_modulo IS NULL)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_comp.id_empresa = %s
		OR bloqueo_vent.id_empresa = %s)",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fecha_origen BETWEEN %s AND %s
		OR DATE(fecha_bloqueo) BETWEEN %s AND %s)",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != ""
	&& ($valCadBusq[4] == "-1" || $valCadBusq[4] == "")) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(bloqueo_vent_det.id_articulo) AS items_bloqueados
		FROM iv_bloqueo_venta_detalle bloqueo_vent_det
		WHERE bloqueo_vent_det.id_bloqueo_venta = bloqueo_vent.id_bloqueo_venta
			AND bloqueo_vent_det.estatus IN (1,3)) > 0");
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != ""
	&& ($valCadBusq[3] == "-1" || $valCadBusq[3] == "")) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(bloqueo_vent_det.id_articulo) AS items_bloqueados
		FROM iv_bloqueo_venta_detalle bloqueo_vent_det
		WHERE bloqueo_vent_det.id_bloqueo_venta = bloqueo_vent.id_bloqueo_venta
			AND bloqueo_vent_det.estatus IN (1,3)) = 0");
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
			INNER JOIN vw_pg_clave_movimiento ON (mov.id_clave_movimiento = vw_pg_clave_movimiento.id_clave_movimiento)
		WHERE vw_pg_clave_movimiento.tipo = 1
			AND mov.id_documento = fact_comp.id_factura
		LIMIT 1) = %s",
			valTpDato($valCadBusq[5], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR fact_comp.numero_control_factura LIKE %s
		OR fact_comp.numero_factura_proveedor LIKE %s
		OR bloqueo_vent.id_bloqueo_venta LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT
		fact_comp.id_factura,
		fact_comp.id_modo_compra,
		fact_comp.fecha_origen,
		fact_comp.fecha_factura_proveedor,
		fact_comp.numero_factura_proveedor,
		fact_comp.id_modulo,
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor,
		bloqueo_vent.id_bloqueo_venta,
		bloqueo_vent.fecha_bloqueo,
		
		(SELECT COUNT(bloqueo_vent_det.id_articulo) AS items_bloqueados
		FROM iv_bloqueo_venta_detalle bloqueo_vent_det
		WHERE bloqueo_vent_det.id_bloqueo_venta = bloqueo_vent.id_bloqueo_venta
			AND bloqueo_vent_det.estatus IN (1,3)) AS items_bloqueados,
		
		(SELECT COUNT(fact_compra_det.id_factura) AS items
		FROM cp_factura_detalle fact_compra_det
		WHERE (fact_compra_det.id_factura = fact_comp.id_factura)) AS items,
		
		(IFNULL(fact_comp.subtotal_factura, 0)
			- IFNULL(fact_comp.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
					FROM cp_factura_gasto fact_compra_gasto
					WHERE fact_compra_gasto.id_factura = fact_comp.id_factura
						AND fact_compra_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva fact_compra_iva
					WHERE fact_compra_iva.id_factura = fact_comp.id_factura), 0)
		) AS total,
		
		moneda_local.abreviacion AS abreviacion_moneda_local,
		
		(SELECT DISTINCT ped_comp.estatus_pedido_compra
		FROM cp_factura_detalle fact_comp_det
			INNER JOIN iv_pedido_compra ped_comp ON (fact_comp_det.id_pedido_compra = ped_comp.id_pedido_compra)
		WHERE fact_comp_det.id_factura = fact_comp.id_factura
		LIMIT 1) AS estatus_pedido_compra
	FROM iv_bloqueo_venta bloqueo_vent
		LEFT JOIN cp_factura fact_comp ON (bloqueo_vent.id_factura_compra = fact_comp.id_factura)
		LEFT JOIN cp_proveedor prov ON (fact_comp.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (fact_comp.id_moneda = moneda_local.idmoneda) %s", $sqlBusq);
	
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
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "7%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "id_bloqueo_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Bloqueo");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro Compra");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "37%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "items_bloqueados", $campOrd, $tpOrd, $valBusq, $maxRows, "Items Bloq.");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "6%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaRegistroCompra", "12%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Factura Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Factura Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Factura Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Factura Administración\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['items'] > 0 || $row['id_modulo'] == "") ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_bloqueo']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_bloqueo_venta']."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['fecha_origen'] != "") ? date(spanDateFormat,strtotime($row['fecha_origen'])) : "xx-xx-xxxx")."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['fecha_factura_proveedor'] != "") ? date(spanDateFormat,strtotime($row['fecha_factura_proveedor'])) : "xx-xx-xxxx")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items_bloqueados']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")." ".$row['abreviacion_moneda_local']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblDesbloqueoVenta', '%s');\"><img class=\"puntero\" src=\"../img/iconos/application_view_columns.png\" title=\"Ver Detalle\"/></a>",
					$contFila,
					$row['id_bloqueo_venta']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaRegistroCompra(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaRegistroCompra","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarArticuloBloqueo");
$xajax->register(XAJAX_FUNCTION,"buscarArticuloBloque");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"formBloqueoVenta");
$xajax->register(XAJAX_FUNCTION,"formDesbloqueoArticulo");
$xajax->register(XAJAX_FUNCTION,"formDesbloqueoVenta");
$xajax->register(XAJAX_FUNCTION,"guardarBloqueo");
$xajax->register(XAJAX_FUNCTION,"guardarDesbloqueoArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarArticuloBloque");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaArticuloBloqueo");
$xajax->register(XAJAX_FUNCTION,"listaArticuloBloque");
$xajax->register(XAJAX_FUNCTION,"listaRegistroCompra");

function insertarItemArticulo($contFila, $hddIdArticuloAlmacenCosto = "") {
	$contFila++;
	
	// BUSCA LOS DATOS DEL ARTICULO Y UBICACION
	$query = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
	WHERE vw_iv_art_almacen_costo.id_articulo_almacen_costo = %s;",
		valTpDato($hddIdArticuloAlmacenCosto, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
		
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx1\" name=\"cbx1[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"center\">%s".
				"%s".
				"%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloAlmacenCosto%s\" name=\"hddIdArticuloAlmacenCosto%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloCosto%s\" name=\"hddIdArticuloCosto%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila,
			$contFila, $contFila,
				$contFila,
			$contFila,
			elimCaracter($row['codigo_articulo'],";"),
			preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode($row['descripcion']))),
			"<div class=\"textoNegrita_10px\">".utf8_encode(strtoupper($row['descripcion_almacen']))."</div>",
				"<div>".utf8_encode(str_replace("-[]", "", $row['ubicacion']))."</div>",
				(($row['estatus_articulo_almacen'] == 1) ? "" : "<div class=\"textoRojoNegrita_10px\">(Relacion Inactiva)</div>"),
			$row['id_articulo_costo'],
			number_format($row['cantidad_disponible_logica'], 2, ".", ","),
				$contFila, $contFila, $row['id_articulo'],
				$contFila, $contFila, $hddIdArticuloAlmacenCosto,
				$contFila, $contFila, $row['id_articulo_costo'],
				$contFila, $contFila, $row['id_casilla']);
	
	return array(true, $htmlItmPie, $contFila);
}
?>