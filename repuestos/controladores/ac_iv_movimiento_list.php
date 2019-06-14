<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function buscarMovimiento($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstTipoMovimiento']) ? implode(",",$frmBuscar['lstTipoMovimiento']) : $frmBuscar['lstTipoMovimiento']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		(is_array($frmBuscar['lstClaveMovimiento']) ? implode(",",$frmBuscar['lstClaveMovimiento']) : $frmBuscar['lstClaveMovimiento']),
		(is_array($frmBuscar['lstEmpleadoVendedor']) ? implode(",",$frmBuscar['lstEmpleadoVendedor']) : $frmBuscar['lstEmpleadoVendedor']),
		(is_array($frmBuscar['lstTipoArticulo']) ? implode(",",$frmBuscar['lstTipoArticulo']) : $frmBuscar['lstTipoArticulo']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaMovimientos(0, "id_tipo_movimiento, clave, id_movimiento", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	$idModulo = (is_array($idModulo)) ? implode(",",$idModulo) : $idModulo;
	$idTipoClave = (is_array($idTipoClave)) ? implode(",",$idTipoClave) : $idTipoClave;
	
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
	$html = "<select multiple id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:99%\">";
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

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT id_empleado, nombre_empleado FROM vw_pg_empleados empleado
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select multiple id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstClaveMovimiento('lstClaveMovimiento', $('#lstModulo').val(), $('#lstTipoMovimiento').val());\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = (in_array($row['id_modulo'],explode(",",$selId))) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstTipoArticulo\" name=\"lstTipoArticulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_articulo']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function exportarMovimiento($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstTipoMovimiento']) ? implode(",",$frmBuscar['lstTipoMovimiento']) : $frmBuscar['lstTipoMovimiento']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		(is_array($frmBuscar['lstClaveMovimiento']) ? implode(",",$frmBuscar['lstClaveMovimiento']) : $frmBuscar['lstClaveMovimiento']),
		(is_array($frmBuscar['lstEmpleadoVendedor']) ? implode(",",$frmBuscar['lstEmpleadoVendedor']) : $frmBuscar['lstEmpleadoVendedor']),
		(is_array($frmBuscar['lstTipoArticulo']) ? implode(",",$frmBuscar['lstTipoArticulo']) : $frmBuscar['lstTipoArticulo']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_movimiento_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaMovimientos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 50, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_mov.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_mov.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_movimiento) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tipo_movimiento IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_clave_movimiento IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empleado_vendedor = %s",
			valTpDato($valCadBusq[6], "int"));
	}
		
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(mov_det.id_movimiento) FROM iv_movimiento_detalle mov_det
			INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo)
		WHERE mov_det.id_movimiento = vw_iv_mov.id_movimiento
			AND art.id_tipo_articulo IN (%s)) > 0",
			valTpDato($valCadBusq[7], "campo"));
	}
	
	if ($valCadBusq[8] != "" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numero_documento LIKE %s
		OR folio LIKE %s
		OR (CASE (vw_iv_mov.tipo_proveedor_cliente_empleado)
				WHEN (1) THEN
					(SELECT nombre FROM cp_proveedor
					WHERE id_proveedor = vw_iv_mov.id_proveedor_cliente_empleado)
					
				WHEN (2) THEN
					(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
					WHERE id = vw_iv_mov.id_proveedor_cliente_empleado)
					
				WHEN (3) THEN
					(SELECT CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado FROM pg_empleado
					WHERE id_empleado = vw_iv_mov.id_proveedor_cliente_empleado)
			END) LIKE %s)",
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT vw_iv_mov.* FROM vw_iv_movimiento vw_iv_mov %s", $sqlBusq);
	
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
	while ($rowMovDet = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$idModulo = $rowMovDet['id_modulo'];
			
		switch ($idModulo) {
			case 0 : $imgModuloDcto = "<img src=\"../img/iconos/ico_repuestos.gif\"/ title=\"Repuestos\">"; break;
			case 1 : $imgModuloDcto = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgModuloDcto = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgModuloDcto = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			default : $imgModuloDcto = "";
		}
		
		switch ($rowMovDet['id_tipo_movimiento']) {
			case 1 : // COMPRA
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_factura_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Registro Compra")."\"/><a>",
					$rowMovDet['id_documento']);
				switch ($idModulo) {
					case 0: $aVerDctoAux = "../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
					case 2: $aVerDctoAux = "../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Registro Compra PDF")."\"/></a>" : "";
				break;
			case 2 : // ENTRADA
				switch ($rowMovDet['tipo_documento_movimiento']) {
					case 1 : // VALE ENTRADA
						switch ($idModulo) {
							case 0 : $aVerDctoAux = "../repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=".$rowMovDet['id_documento']."|2"; break;
							case 1 : $aVerDctoAux = "../servicios/sa_devolucion_vale_salida_pdf.php?valBusq=1|".$rowMovDet['id_documento']; break;
							case 2 : $aVerDctoAux = "../vehiculos/reportes/an_ajuste_inventario_vale_entrada_imp.php?id=".$rowMovDet['id_documento']; break;
							default : $aVerDctoAux = "";
						}
						$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Vale Entrada PDF")."\"/></a>" : "";
						break;
					case 2 : // NOTA DE CREDITO
						$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota Crédito")."\"/><a>",
							$rowMovDet['id_documento']);
						switch ($idModulo) {
							case 0 : $aVerDctoAux = "../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
							case 1 : $aVerDctoAux = "../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
							case 2 : $aVerDctoAux = "../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
							case 3 : $aVerDctoAux = "../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
							case 4 : $aVerDctoAux = "../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
							default : $aVerDctoAux = "";
						}
						$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Nota Crédito PDF")."\"/></a>" : "";
						break;
				}
				break;
			case 3 : // VENTA
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
					$rowMovDet['id_documento']);
				switch ($idModulo) {
					case 0 : $aVerDctoAux = "../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
					case 1 : $aVerDctoAux = "../servicios/reportes/sa_factura_venta_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
					case 2 : $aVerDctoAux = "../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
					case 3 : $aVerDctoAux = "../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
					case 4 : $aVerDctoAux = "../alquiler/reportes/al_factura_venta_pdf.php?valBusq=".$rowMovDet['id_documento']; break;
					default : $aVerDctoAux = "";
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Factura Venta PDF")."\"/></a>" : "";
				break;
			case 4 : // SALIDA
				switch ($rowMovDet['tipo_documento_movimiento']) {
					case 1 : // VALE SALIDA
						switch ($idModulo) {
							case 0 : $aVerDctoAux = "../repuestos/reportes/iv_ajuste_inventario_pdf.php?valBusq=".$rowMovDet['id_documento']."|4"; break;
							case 1 : $aVerDctoAux = "../servicios/sa_imprimir_historico_vale.php?valBusq=".$rowMovDet['id_documento']."|2|3"; break;
							case 2 : $aVerDctoAux = "../vehiculos/reportes/an_ajuste_inventario_vale_salida_imp.php?id=".$rowMovDet['id_documento']; break;
							default : $aVerDctoAux = "";
						}
						$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Vale Salida PDF")."\"/></a>" : "";
						break;
					case 2 : // NOTA DE CREDITO
						$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_nota_credito_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota Crédito")."\"/><a>",
							$rowMovDet['id_documento']);
						$aVerDcto .= sprintf("<a id=\"aVerDcto\" href=\"javascript:verVentana('../cxp/reportes/cp_nota_credito_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Nota Crédito PDF")."\"/></a>",
							$rowMovDet['id_documento']);
						break;
				}
				break;
			default : $aVerDcto = "";
		}
		
		if ($rowMovDet['tipo_proveedor_cliente_empleado'] == 1) { // PROVEEDOR
			$queryProvClienteEmpleado = sprintf("SELECT
				CONCAT_WS('-', lrif, rif) AS rif_proveedor,
				nombre
			FROM cp_proveedor
			WHERE id_proveedor = %s;",
				$rowMovDet['id_proveedor_cliente_empleado']);
			$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
			if (!$rsProvClienteEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
			$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre'];
			$rifProvClienteEmpleado = $rowProvClienteEmpleado['rif_proveedor'];
		} else if ($rowMovDet['tipo_proveedor_cliente_empleado'] == 2) { // CLIENTE
			$queryProvClienteEmpleado = sprintf("SELECT
				CONCAT_WS('-', lci, ci) AS ci_cliente,
				CONCAT_WS(' ', nombre, apellido) AS nombre_cliente
			FROM cj_cc_cliente
			WHERE id = %s ",
				$rowMovDet['id_proveedor_cliente_empleado']);
			$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
			if (!$rsProvClienteEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
			$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre_cliente'];
			$rifProvClienteEmpleado = $rowProvClienteEmpleado['ci_cliente'];
		} else if ($rowMovDet['tipo_proveedor_cliente_empleado'] == 3) { // EMPLEADO
			$queryProvClienteEmpleado = sprintf("SELECT
				cedula,
				CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado
			FROM pg_empleado
			WHERE id_empleado = %s",
				$rowMovDet['id_proveedor_cliente_empleado']);
			$rsProvClienteEmpleado = mysql_query($queryProvClienteEmpleado);
			if (!$rsProvClienteEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$rowProvClienteEmpleado = mysql_fetch_array($rsProvClienteEmpleado);
			$nombreProvClienteEmpleado = $rowProvClienteEmpleado['nombre_empleado'];
			$rifProvClienteEmpleado = $rowProvClienteEmpleado['cedula'];
		}
		
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("mov_det.id_movimiento = %s",
			valTpDato($rowMovDet['id_movimiento'], "int"));
		
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("art.id_tipo_articulo IN (%s)",
				valTpDato($valCadBusq[7], "campo"));
		}
		
		$queryDetalle = sprintf("SELECT 
			mov_det.id_movimiento_detalle,
			art.codigo_articulo,
			art.descripcion,
			mov_det.id_kardex,
			mov_det.id_articulo_costo,
			mov_det.cantidad,
			(CASE mov.id_tipo_movimiento
				WHEN 1 THEN -- COMPRA
					(IFNULL(mov_det.precio,0)
						+ IFNULL(mov_det.costo_cargo,0)
						+ IFNULL(mov_det.costo_diferencia,0))
				ELSE
					(IFNULL(mov_det.precio,0))
			END) AS precio,
			mov_det.pmu_unitario,
			(IFNULL(mov_det.costo,0)
				+ IFNULL(mov_det.costo_cargo,0)
				+ IFNULL(mov_det.costo_diferencia,0)) AS costo,
			mov_det.porcentaje_descuento,
			
			(SELECT 
				precio.descripcion_precio
			FROM cj_cc_encabezadofactura cxc_fact
				INNER JOIN cj_cc_factura_detalle cxc_fact_det ON (cxc_fact.idFactura = cxc_fact_det.id_factura)
				LEFT JOIN iv_pedido_venta_detalle ped_vent_det ON (cxc_fact.numeroPedido = ped_vent_det.id_pedido_venta
					AND cxc_fact_det.id_articulo = ped_vent_det.id_articulo
					AND cxc_fact_det.cantidad = ped_vent_det.cantidad
					AND cxc_fact.idDepartamentoOrigenFactura IN (0))
				LEFT JOIN sa_det_orden_articulo det_orden_art ON (cxc_fact.numeroPedido = det_orden_art.id_orden
					AND cxc_fact_det.id_articulo = det_orden_art.id_articulo
					AND cxc_fact_det.cantidad = det_orden_art.cantidad
					AND det_orden_art.estado_articulo IN ('FACTURADO','DEVUELTO')
					AND cxc_fact.idDepartamentoOrigenFactura IN (1))
				LEFT JOIN pg_precios precio ON ((ped_vent_det.id_precio = precio.id_precio AND cxc_fact.idDepartamentoOrigenFactura IN (0))
					OR (det_orden_art.id_precio = precio.id_precio AND cxc_fact.idDepartamentoOrigenFactura IN (1)))
			WHERE cxc_fact.idFactura = mov.id_documento
				AND cxc_fact_det.id_articulo = mov_det.id_articulo
				AND cxc_fact_det.id_articulo_costo = mov_det.id_articulo_costo
				AND cxc_fact_det.cantidad = mov_det.cantidad
			LIMIT 1) AS descripcion_precio
		FROM iv_movimiento mov
			INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
			INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
		ORDER BY id_movimiento_detalle;", $sqlBusq2);
		$rsDetalle = mysql_query($queryDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$htmlTb .= "<tr>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				if ($auxActual != $rowMovDet['id_clave_movimiento']) {
					$htmlTb .= "<tr align=\"left\" class=\"trResaltar6 textoNegrita_12px\" height=\"24\">";
						$htmlTb .= "<td colspan=\"14\">".utf8_encode($rowMovDet['descripcion_tipo_movimiento'])." - ".$rowMovDet['clave'].") ".utf8_encode($rowMovDet['descripcion_clave_movimiento'])."</td>";
					$htmlTb .= "</tr>";
				
					$auxActual = $rowMovDet['id_clave_movimiento'];
				}
				$htmlTb .= "<tr align=\"left\">";
					$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" title=\"Id Movimiento: ".$rowMovDet['id_movimiento']."\">Nro. Dcto:</td>";
					$htmlTb .= "<td colspan=\"2\">";
						$htmlTb .= "<table width=\"100%\">";
						$htmlTb .= "<tr>";
							$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
							$htmlTb .= "<td>".$imgModuloDcto."</td>";
							$htmlTb .= "<td align=\"right\" width=\"100%\">".utf8_encode($rowMovDet['numero_documento'])."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Nro. Control / Folio:</td>
								<td align=\"right\" colspan=\"2\">".$rowMovDet['folio']."</td>
								<td align=\"right\" class=\"tituloCampo\">Fecha Dcto.:</td>
								<td align=\"center\" colspan=\"2\">".date(spanDateFormat,strtotime($rowMovDet['fecha_documento']))."</td>
								<td align=\"right\" class=\"tituloCampo\">Fecha Registro:</td>
								<td align=\"center\">".date(spanDateFormat,strtotime($rowMovDet['fecha_captura']))."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr align=\"left\">";
					$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Prov./Clnte./Emp.:</td>
								<td align=\"right\">".$rifProvClienteEmpleado."</td>
								<td colspan=\"2\">".utf8_encode($nombreProvClienteEmpleado)."</td>
								<td align=\"center\" ".$class." colspan=\"2\">".$rowMovDet['estado_documento']."</td>
								<td align=\"right\" class=\"tituloCampo\">Nro. Orden:</td>
								<td align=\"right\" colspan=\"2\">".$rowMovDet['numero_orden']."</td>
								<td align=\"right\" class=\"tituloCampo\">Clave Mov.:</td>
								<td colspan=\"4\">".((strlen($rowMovDet['clave']) > 0) ? utf8_encode($rowMovDet['clave'].") ".$rowMovDet['descripcion_clave_movimiento']) : "")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr class=\"tituloColumna\">";
					$htmlTb .= "<td width=\"4%\"></td>
								<td width=\"10%\">Código</td>
								<td width=\"20%\">Descripción</td>
								<td width=\"6%\">Cantidad</td>
								<td width=\"6%\">".$spanPrecioUnitario." / PMU Unit.</td>
								<td width=\"6%\">Costo Unit.</td>
								<td width=\"6%\">Importe Precio</td>
								<td width=\"6%\">Importe PMU</td>
								<td width=\"6%\">Dscto.</td>
								<td width=\"6%\">Importe Neto</td>
								<td width=\"8%\">Importe Costo</td>
								<td width=\"8%\">Utl.</td>
								<td width=\"4%\">%Utl.</td>
								<td width=\"4%\">%Dscto.</td>";
				$htmlTb .= "</tr>";
				
				$contFila2 = 0;
				$arrayTotal = NULL;
				while ($rowDetalle = mysql_fetch_array($rsDetalle)){
					$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila2++;
					
					$importeCosto = $rowDetalle['cantidad'] * $rowDetalle['costo'];
					$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio'];
					$importePMU = $rowDetalle['cantidad'] * $rowDetalle['pmu_unitario'];
					$descuento = $rowDetalle['porcentaje_descuento'] * ($importePrecio + $importePMU) / 100;
					$neto = ($importePrecio + $importePMU) - $descuento;
					
					$importeCosto = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
					
					$porcUtilidadCosto = 0;
					$porcUtilidadVenta = 0;
					if (($importePrecio + $importePMU) > 0) {
						$utilidad = $neto - $importeCosto;
						
						$porcUtilidadCosto = $utilidad * 100 / $importeCosto;
						$porcUtilidadVenta = $utilidad * 100 / ($importePrecio + $importePMU);
					}
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
						$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".($contFila2)."</td>";
						$htmlTb .= "<td align=\"left\" title=\"Id Kardex: ".$rowDetalle['id_kardex']."\nId Mov. Det.: ".$rowDetalle['id_movimiento_detalle']."\">";
							$htmlTb .= elimCaracter(utf8_encode($rowDetalle['codigo_articulo']),";");
							$htmlTb .= ($rowDetalle['id_articulo_costo'] > 0) ? "<br><span id=\"spnLote".$contFila2."\" class=\"textoNegrita_9px\">LOTE: ".$rowDetalle['id_articulo_costo']."</span>" : "";
						$htmlTb .= "</td>";
						$htmlTb .= "<td align=\"left\">".utf8_encode($rowDetalle['descripcion'])."</td>";
						$htmlTb .= "<td>".number_format($rowDetalle['cantidad'], 2, ".", ",")."</td>";
						$htmlTb .= "<td title=\"".$rowDetalle['descripcion_precio']."\">";
							$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
							$htmlTb .= "<tr align=\"right\">";
								$htmlTb .= "<td></td>";
								$htmlTb .= "<td width=\"100%\">".number_format($rowDetalle['precio'], 2, ".", ",")."</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= ($rowDetalle['pmu_unitario'] != 0) ? "<tr align=\"right\"><td>PMU:</td>"."<td>".number_format($rowDetalle['pmu_unitario'], 2, ".", ",")."</td></tr>" : "";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
						$htmlTb .= "<td>".number_format($rowDetalle['costo'], 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($importePrecio, 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($importePMU, 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($descuento, 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($neto, 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($importeCosto, 2, ".", ",")."</td>";
						$htmlTb .= "<td>".number_format($utilidad, 2, ".", ",")."</td>";
						$htmlTb .= "<td>";
							$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
							$htmlTb .= "<tr align=\"right\">";
								$htmlTb .= "<td>S/V:</td>";
								$htmlTb .= "<td width=\"100%\">".number_format($porcUtilidadVenta, 2, ".", ",")."</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "<tr align=\"right\">";
								$htmlTb .= "<td>S/C:</td>";
								$htmlTb .= "<td>".number_format($porcUtilidadCosto, 2, ".", ",")."</td>";
							$htmlTb .= "</tr>";
							$htmlTb .= "</table>";
						$htmlTb .= "</td>";
						$htmlTb .= "<td>".number_format($rowDetalle['porcentaje_descuento'], 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
					
					$arrayTotal['cant_dctos'] = $rowDetalle['cant_dctos'];
					$arrayTotal['cantidad'] += $rowDetalle['cantidad'];
					$arrayTotal['importe_precio'] += $importePrecio;
					$arrayTotal['importe_pmu'] += $importePMU;
					$arrayTotal['descuento'] += $descuento;
					$arrayTotal['importe_neto'] += $neto;
					$arrayTotal['importe_costo'] += $importeCosto;
					$arrayTotal['utilidad'] += $utilidad;
				}
					
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"3\">Total Dcto. ".$rowMovDet['numero_documento'].":</td>
								<td>".number_format($arrayTotal['cantidad'], 2, ".", ",")."</td>
								<td>"."</td>
								<td>"."</td>
								<td>".number_format($arrayTotal['importe_precio'], 2, ".", ",")."</td>
								<td>".number_format($arrayTotal['importe_pmu'], 2, ".", ",")."</td>
								<td>".number_format($arrayTotal['descuento'], 2, ".", ",")."</td>
								<td>".number_format($arrayTotal['importe_neto'], 2, ".", ",")."</td>
								<td>".number_format($arrayTotal['importe_costo'], 2, ".", ",")."</td>
								<td>".number_format($arrayTotal['utilidad'], 2, ".", ",")."</td>";
					$htmlTb .= "<td>";
						$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td>S/V:</td>";
							$htmlTb .= "<td width=\"100%\">".number_format((($arrayTotal['utilidad'] > 0) ? ($arrayTotal['utilidad'] * 100) / ($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu']) : 0), 2, ".", ",")."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "<tr align=\"right\">";
							$htmlTb .= "<td>S/C:</td>";
							$htmlTb .= "<td>".number_format((($arrayTotal['utilidad'] > 0) ? ($arrayTotal['utilidad'] * 100) / $arrayTotal['importe_costo'] : 0), 2, ".", ",")."</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
					$htmlTb .= "<td>".number_format(((($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu']) > 0) ? ($arrayTotal['descuento'] * 100) / ($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu']) : 0), 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if ($contFila < $maxRows && (($maxRows * $pageNum) + $contFila) < $totalRows)
			$htmlTb .= "<tr><td><hr></td></tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimientos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimientos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMovimientos(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimientos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMovimientos(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td>";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaMovimientos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td width=\"24%\">Clave de Movimiento</td>
					<td width=\"6%\">Cant. Dctos.</td>
					<td width=\"8%\">Importe Precio</td>
					<td width=\"8%\">Importe PMU</td>
					<td width=\"8%\">Dscto.</td>
					<td width=\"8%\">Importe Neto</td>
					<td width=\"8%\">Importe Costo</td>
					<td width=\"10%\">Utl.</td>
					<td width=\"10%\">%Utl.</td>
					<td width=\"10%\">%Dscto.</td>";
	$htmlTh .= "</tr>";	
	$htmlTb = "";
	for ($idTipoMovimiento = 1; $idTipoMovimiento <= 4; $idTipoMovimiento++) {
		$arrayTipoMovimiento = array(1 => "Compra", 2 => "Entrada", 3 => "Venta", 4 => "Salida");
		
		$htmlTb .= "<tr align=\"left\" class=\"tituloColumna\" height=\"24\">";
			$htmlTb .= "<td colspan=\"11\">".$arrayTipoMovimiento[$idTipoMovimiento]."</td>";
		$htmlTb .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_iv_mov.id_tipo_movimiento IN (%s)",
			valTpDato($idTipoMovimiento, "int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_iv_mov.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = vw_iv_mov.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		// Agrupa los tipos de movimiento por clave de movimiento para luego calcular el total de ese movimiento
		$queryTipoMov = sprintf("SELECT
			vw_iv_mov.id_clave_movimiento,
			vw_iv_mov.clave,
			vw_iv_mov.descripcion_clave_movimiento,
			vw_iv_mov.id_tipo_movimiento,
			vw_iv_mov.descripcion_tipo_movimiento,
			vw_iv_mov.id_modulo
		FROM vw_iv_movimiento vw_iv_mov %s %s
		GROUP BY id_clave_movimiento, descripcion_clave_movimiento, id_tipo_movimiento
		ORDER BY clave ASC;", $sqlBusq, $sqlBusq2);
		$rsTipoMov = mysql_query($queryTipoMov);
		if (!$rsTipoMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila2 = 0;
		$arrayDet = NULL;
		while($rowMovDet = mysql_fetch_array($rsTipoMov)){
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			$contFila2++;
			
			switch($rowMovDet['id_modulo']) {
				case 0 : $imgModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			}
			
			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("mov.id_clave_movimiento = %s",
				valTpDato($rowMovDet['id_clave_movimiento'], "int"));
			
			if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(
					(CASE
						WHEN (mov.id_tipo_movimiento = 1) THEN
							(SELECT cxp_fact.id_empresa AS id_empresa FROM cp_factura cxp_fact
							WHERE (cxp_fact.id_factura = mov.id_documento))
						WHEN (mov.id_tipo_movimiento = 2) THEN
							(CASE
								WHEN (mov.tipo_documento_movimiento = 1) THEN
									(SELECT iv_ve.id_empresa AS id_empresa FROM iv_vale_entrada iv_ve
									WHERE (iv_ve.id_vale_entrada = mov.id_documento))
								WHEN (mov.tipo_documento_movimiento = 2) THEN
									(SELECT cxc_nc.id_empresa AS id_empresa FROM cj_cc_notacredito cxc_nc
									WHERE (cxc_nc.idNotaCredito = mov.id_documento))
							END)
						WHEN (mov.id_tipo_movimiento = 3) THEN
							(SELECT cxc_fact.id_empresa AS id_empresa FROM cj_cc_encabezadofactura cxc_fact
							WHERE (cxc_fact.idFactura = mov.id_documento))
						WHEN (mov.id_tipo_movimiento = 4) THEN
							(CASE
								WHEN (mov.tipo_documento_movimiento = 1) THEN
									(CASE
										WHEN (clave_mov.id_modulo = 0) THEN
											(SELECT iv_vs.id_empresa AS id_empresa FROM iv_vale_salida iv_vs
											WHERE (iv_vs.id_vale_salida = mov.id_documento))
										WHEN (clave_mov.id_modulo = 1) THEN
											(SELECT orden.id_empresa AS id_empresa
											FROM (sa_vale_salida sa_vs
												INNER JOIN sa_orden orden on ((sa_vs.id_orden = orden.id_orden)))
											WHERE (sa_vs.id_vale_salida = mov.id_documento))
									END)
								WHEN (mov.tipo_documento_movimiento = 2) THEN
									(SELECT cxp_nc.id_empresa AS id_empresa FROM cp_notacredito cxp_nc
									WHERE (cxp_nc.id_notacredito = mov.id_documento))
							END)
					END) = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = (CASE
													WHEN (mov.id_tipo_movimiento = 1) THEN
														(SELECT cxp_fact.id_empresa AS id_empresa FROM cp_factura cxp_fact
														WHERE (cxp_fact.id_factura = mov.id_documento))
													WHEN (mov.id_tipo_movimiento = 2) THEN
														(CASE
															WHEN (mov.tipo_documento_movimiento = 1) THEN
																(SELECT iv_ve.id_empresa AS id_empresa FROM iv_vale_entrada iv_ve
																WHERE (iv_ve.id_vale_entrada = mov.id_documento))
															WHEN (mov.tipo_documento_movimiento = 2) THEN
																(SELECT cxc_nc.id_empresa AS id_empresa FROM cj_cc_notacredito cxc_nc
																WHERE (cxc_nc.idNotaCredito = mov.id_documento))
														END)
													WHEN (mov.id_tipo_movimiento = 3) THEN
														(SELECT cxc_fact.id_empresa AS id_empresa FROM cj_cc_encabezadofactura cxc_fact
														WHERE (cxc_fact.idFactura = mov.id_documento))
													WHEN (mov.id_tipo_movimiento = 4) THEN
														(CASE
															WHEN (mov.tipo_documento_movimiento = 1) THEN
																(CASE
																	WHEN (clave_mov.id_modulo = 0) THEN
																		(SELECT iv_vs.id_empresa AS id_empresa FROM iv_vale_salida iv_vs
																		WHERE (iv_vs.id_vale_salida = mov.id_documento))
																	WHEN (clave_mov.id_modulo = 1) THEN
																		(SELECT orden.id_empresa AS id_empresa
																		FROM (sa_vale_salida sa_vs
																			INNER JOIN sa_orden orden on ((sa_vs.id_orden = orden.id_orden)))
																		WHERE (sa_vs.id_vale_salida = mov.id_documento))
																END)
															WHEN (mov.tipo_documento_movimiento = 2) THEN
																(SELECT cxp_nc.id_empresa AS id_empresa FROM cp_notacredito cxp_nc
																WHERE (cxp_nc.id_notacredito = mov.id_documento))
														END)
												END)))",
					valTpDato($valCadBusq[0], "int"),
					valTpDato($valCadBusq[0], "int"));
			}
			
			if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("DATE(mov.fecha_movimiento) BETWEEN %s AND %s",
					valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
					valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
			}
			
			if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("
				(CASE
					WHEN (mov.id_tipo_movimiento = 2) THEN
						(CASE
							WHEN (mov.tipo_documento_movimiento = 2) THEN
								(SELECT cxc_nc.id_empleado_vendedor FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = mov.id_documento)
						END)
					WHEN (mov.id_tipo_movimiento = 3) THEN
						(SELECT cxc_fact.idVendedor FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = mov.id_documento)
				END) = %s",
					valTpDato($valCadBusq[6], "int"));
			}
				
			if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(SELECT COUNT(mov_det.id_movimiento) FROM iv_movimiento_detalle mov_det
					INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo)
				WHERE mov_det.id_movimiento = mov.id_movimiento
					AND art.id_tipo_articulo IN (%s)) > 0",
					valTpDato($valCadBusq[7], "campo"));
			}
			
			if ($valCadBusq[8] != "" && $valCadBusq[8] != "") {
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("(
				(CASE
					WHEN (clave_mov.tipo = 1 OR mov.id_tipo_movimiento = 1) THEN -- COMPRA
						(SELECT cxp_fact.numero_factura_proveedor AS numero_factura_proveedor FROM cp_factura cxp_fact
						WHERE cxp_fact.id_factura = mov.id_documento)
					WHEN (clave_mov.tipo = 2 OR mov.id_tipo_movimiento = 2) THEN -- ENTRADA
						(CASE mov.tipo_documento_movimiento
							WHEN 1 THEN
								(SELECT iv_ve.numeracion_vale_entrada AS numeracion_vale_entrada FROM iv_vale_entrada iv_ve
								WHERE iv_ve.id_vale_entrada = mov.id_documento)
							WHEN 2 THEN
								(SELECT nota_cred.numeracion_nota_credito AS numeracion_nota_credito FROM cj_cc_notacredito nota_cred
								WHERE nota_cred.idNotaCredito = mov.id_documento)
						END)
					WHEN (clave_mov.tipo = 3 OR mov.id_tipo_movimiento = 3) THEN -- VENTA
						(SELECT cxc_fact.numeroFactura AS numeroFactura FROM cj_cc_encabezadofactura cxc_fact
						WHERE cxc_fact.idFactura = mov.id_documento)
					WHEN (clave_mov.tipo = 4 OR mov.id_tipo_movimiento = 4) THEN -- SALIDA
						(CASE mov.tipo_documento_movimiento
							WHEN 1 THEN
								(CASE clave_mov.id_modulo
									WHEN 0 THEN
										(SELECT iv_vs.numeracion_vale_salida AS numeracion_vale_salida FROM iv_vale_salida iv_vs
										WHERE iv_vs.id_vale_salida = mov.id_documento)
									WHEN 1 THEN
										(SELECT iv_vs.numero_vale AS numero_vale
										FROM sa_vale_salida iv_vs
											INNER JOIN sa_orden orden ON (iv_vs.id_orden = orden.id_orden)
										WHERE iv_vs.id_vale_salida = mov.id_documento)
								END)
							WHEN 2 THEN
								(SELECT cxp_nc.numero_nota_credito AS numero_nota_credito FROM cp_notacredito cxp_nc
								WHERE cxp_nc.id_notacredito = mov.id_documento)
						END)
				END) LIKE %s
				OR (CASE (CASE
							WHEN (clave_mov.tipo = 1 OR mov.id_tipo_movimiento = 1) THEN 1
							WHEN (clave_mov.tipo = 2 OR mov.id_tipo_movimiento = 2) THEN
								(CASE mov.tipo_documento_movimiento
									WHEN 1 THEN
										(CASE (SELECT iv_ve.tipo_vale_entrada AS tipo_vale_entrada FROM iv_vale_entrada iv_ve
												WHERE iv_ve.id_vale_entrada = mov.id_documento)
											WHEN 1 THEN 2
											WHEN 2 THEN 2
											WHEN 3 THEN 2
											WHEN 4 THEN 3
											WHEN 5 THEN 3
										END)
									WHEN 2 THEN 2
								END)
							WHEN (clave_mov.tipo = 3 OR mov.id_tipo_movimiento = 3) THEN 2
							WHEN (clave_mov.tipo = 4 OR mov.id_tipo_movimiento = 4) THEN
								(CASE mov.tipo_documento_movimiento
									WHEN 1 THEN
										(CASE (SELECT iv_vs.tipo_vale_salida AS tipo_vale_salida FROM iv_vale_salida iv_vs
												WHERE iv_vs.id_vale_salida = mov.id_documento)
											WHEN 1 THEN 2
											WHEN 2 THEN 2
											WHEN 3 THEN 2
											WHEN 4 THEN 3
											WHEN 5 THEN 3
										END)
									WHEN 2 THEN 1
								END)
						END)
					WHEN 1 THEN
						(SELECT nombre FROM cp_proveedor
						WHERE id_proveedor = mov.id_cliente_proveedor)
						
					WHEN 2 THEN
						(SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente
						WHERE id = mov.id_cliente_proveedor)
						
					WHEN 3 THEN
						(SELECT CONCAT_WS(' ', nombre_empleado, apellido) AS nombre_empleado FROM pg_empleado
						WHERE id_empleado = mov.id_cliente_proveedor)
				END) LIKE %s)",
					valTpDato("%".$valCadBusq[8]."%", "text"),
					valTpDato("%".$valCadBusq[8]."%", "text"));
			}
			
			$sqlBusq3 = "";
			$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
			$sqlBusq3 .= $cond.sprintf("mov_det.id_movimiento IN (SELECT mov.id_movimiento
																FROM iv_movimiento mov
																	INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s)", $sqlBusq2);
			
			if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
				$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
				$sqlBusq3 .= $cond.sprintf("art.id_tipo_articulo IN (%s)",
					valTpDato($valCadBusq[7], "campo"));
			}
			
			$queryDetalle = sprintf("SELECT 
				art.codigo_articulo,
				mov_det.cantidad,
				(CASE mov.id_tipo_movimiento
					WHEN 1 THEN -- COMPRA
						(IFNULL(mov_det.precio,0)
							+ IFNULL(mov_det.costo_cargo,0)
							+ IFNULL(mov_det.costo_diferencia,0))
					ELSE
						(IFNULL(mov_det.precio,0))
				END) AS precio,
				mov_det.pmu_unitario,
				(IFNULL(mov_det.costo,0)
					+ IFNULL(mov_det.costo_cargo,0)
					+ IFNULL(mov_det.costo_diferencia,0)) AS costo,
				mov_det.porcentaje_descuento,
				
				(SELECT COUNT(mov.id_movimiento)
				FROM iv_movimiento mov
					INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento) %s) AS cant_dctos
			FROM iv_movimiento mov
				INNER JOIN iv_movimiento_detalle mov_det ON (mov.id_movimiento = mov_det.id_movimiento)
				INNER JOIN iv_articulos art ON (mov_det.id_articulo = art.id_articulo) %s
			ORDER BY id_movimiento_detalle;", $sqlBusq2, $sqlBusq3);
			$rsDetalle = mysql_query($queryDetalle);
			if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$arrayTotal = NULL;
			while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
				$importeCosto = $rowDetalle['cantidad'] * $rowDetalle['costo'];
				$importePrecio = $rowDetalle['cantidad'] * $rowDetalle['precio'];
				$importePMU = $rowDetalle['cantidad'] * $rowDetalle['pmu_unitario'];
				$descuento = $rowDetalle['porcentaje_descuento'] * ($importePrecio + $importePMU) / 100;
				$neto = ($importePrecio + $importePMU) - $descuento;
				
				$importeCosto = ($rowMovDet['id_tipo_movimiento'] == 1) ? $neto : $importeCosto;
				
				$porcUtilidadCosto = 0;
				$porcUtilidadVenta = 0;
				if (($importePrecio + $importePMU) > 0) {
					$utilidad = $neto - $importeCosto;
					
					$porcUtilidadCosto = $utilidad * 100 / $importeCosto;
					$porcUtilidadVenta = $utilidad * 100 / ($importePrecio + $importePMU);
				}
				
				$arrayTotal['cant_dctos'] = $rowDetalle['cant_dctos'];
				$arrayTotal['importe_precio'] += $importePrecio;
				$arrayTotal['importe_pmu'] += $importePMU;
				$arrayTotal['descuento'] += $descuento;
				$arrayTotal['importe_neto'] += $neto;
				$arrayTotal['importe_costo'] += $importeCosto;
				$arrayTotal['utilidad'] += $utilidad;
			}
			
			if (($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu']) > 0) {
				$porcUtilidadCosto = ($arrayTotal['utilidad'] > 0) ? (($arrayTotal['utilidad'] * 100) / $arrayTotal['importe_costo']) : 0;
				$porcUtilidadVenta = ($arrayTotal['utilidad'] > 0) ? (($arrayTotal['utilidad'] * 100) / ($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu'])) : 0;
				
				$porcDescuento = (($arrayTotal['descuento'] * 100) / ($arrayTotal['importe_precio'] + $arrayTotal['importe_pmu']));
			} else {
				$porcUtilidadCosto = 0;
				$porcUtilidadVenta = 0;
				
				$porcDescuento = 0;
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td>".$imgModulo."</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($rowMovDet['clave'].") ".$rowMovDet['descripcion_clave_movimiento'])."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['cant_dctos'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['importe_precio'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['importe_pmu'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['descuento'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['importe_neto'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['importe_costo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotal['utilidad'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td>S/V:</td>";
						$htmlTb .= "<td width=\"100%\">".number_format($porcUtilidadVenta, 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td>S/C:</td>";
						$htmlTb .= "<td>".number_format($porcUtilidadCosto, 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".number_format($porcDescuento, 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
			
			$arrayDet['cant_dctos'] += $arrayTotal['cant_dctos'];
			$arrayDet['importe_precio'] += $arrayTotal['importe_precio'];
			$arrayDet['importe_pmu'] += $arrayTotal['importe_pmu'];
			$arrayDet['descuento'] += $arrayTotal['descuento'];
			$arrayDet['importe_neto'] += $arrayTotal['importe_neto'];
			$arrayDet['importe_costo'] += $arrayTotal['importe_costo'];
			$arrayDet['utilidad'] += $arrayTotal['utilidad'];
			
			$arrayTotalMovimiento[$idTipoMovimiento] = $arrayDet;
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"2\">Total ".$arrayTipoMovimiento[$idTipoMovimiento].":</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento]['cant_dctos'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento]['importe_precio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento]['importe_pmu'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento]['descuento'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento]['importe_neto'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento]['importe_costo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalMovimiento[$idTipoMovimiento]['utilidad'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td>S/V:</td>";
					$htmlTb .= "<td width=\"100%\">".number_format((($arrayTotalMovimiento[$idTipoMovimiento]['utilidad'] > 0) ? (($arrayTotalMovimiento[$idTipoMovimiento]['utilidad'] * 100) / ($arrayTotalMovimiento[$idTipoMovimiento]['importe_precio'] + $arrayTotalMovimiento[$idTipoMovimiento]['importe_pmu'])) : 0), 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td>S/C:</td>";
					$htmlTb .= "<td>".number_format((($arrayTotalMovimiento[$idTipoMovimiento]['utilidad'] > 0) ? (($arrayTotalMovimiento[$idTipoMovimiento]['utilidad'] * 100) / $arrayTotalMovimiento[$idTipoMovimiento]['importe_costo']) : 0), 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".number_format(((($arrayTotalMovimiento[$idTipoMovimiento]['importe_precio'] + $arrayTotalMovimiento[$idTipoMovimiento]['importe_pmu']) > 0) ? ($arrayTotalMovimiento[$idTipoMovimiento]['descuento'] * 100) / ($arrayTotalMovimiento[$idTipoMovimiento]['importe_precio'] + $arrayTotalMovimiento[$idTipoMovimiento]['importe_pmu']) : 0), 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaResumenMovimientos","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"exportarMovimiento");
$xajax->register(XAJAX_FUNCTION,"listaMovimientos");
?>