<?php

function anularPedido($idPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_captura_pedido_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// ANULA EL PEDIDO
	$updateSQL = sprintf("UPDATE cj_cc_pedido SET
		estado_pedido = 5
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	mysql_query("COMMIT;");
	
	$objResponse->script("$('#btnBuscar').click();");
	$objResponse->alert("Pedido anulado correctamente");

	return $objResponse;
}

function autorizarPedido($idPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_captura_pedido_list","desautorizar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");	
	
	$updateSQL = sprintf("UPDATE cj_cc_pedido SET
		estado_pedido = 1
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("$('#btnBuscar').click();");
	$objResponse->alert("Pedido autorizado correctamente, ahora se puede facturar");
		
	return $objResponse;
}

function buscarPedido($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstEmpleado']) ? implode(",",$frmBuscar['lstEmpleado']) : $frmBuscar['lstEmpleado']),
		(is_array($frmBuscar['lstTipoPago']) ? implode(",",$frmBuscar['lstTipoPago']) : $frmBuscar['lstTipoPago']),
		(is_array($frmBuscar['lstEstadoPedido']) ? implode(",",$frmBuscar['lstEstadoPedido']) : $frmBuscar['lstEstadoPedido']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPedido(0, "numero_pedido", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstEstadoPedido($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$array = array("0" => "Pendiente", "1" => "Autorizado", "2" => "Facturado", "3" => "Desautorizado", "4" => "Devuelta", "5" => "Anulada");	
	$totalRows = count($array);
	
	$html .= "<select ".(($totalRows > 2) ? "multiple" : "")." id=\"lstEstadoPedido\" name=\"lstEstadoPedido\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($indice.".- ".$valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstadoPedido","innerHTML", $html);
	
	return $objResponse;
}

function cargaLstModulo($idModulo = "", $selId = ""){
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("modulo.id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	$query = sprintf("SELECT * FROM pg_modulos modulo %s;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple" : "")." id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['id_modulo'].".- ".$row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstVendedor($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM cj_cc_pedido cxc_pedido
		INNER JOIN vw_pg_empleados empleado ON (cxc_pedido.id_vendedor = empleado.id_empleado)
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstEmpleado\" name=\"lstEmpleado\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado']." {".$row['id_empleado']."}")."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpleado","innerHTML",$html);
	
	return $objResponse;
}

function desautorizarPedido($idPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_captura_pedido_list","desautorizar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE cj_cc_pedido SET
		estado_pedido = 3
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("$('#btnBuscar').click();");
	$objResponse->alert("Pedido desautorizado correctamente");
	
	return $objResponse;
}


function editarPedido($idPedido){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_captura_pedido_list","editar")) { return $objResponse; }
	
	$objResponse->script("window.location.href = 'cc_pedido_form.php?id=".$idPedido."&acc=1'");
	
	return $objResponse;
}

function exportarPedido($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstEmpleado']) ? implode(",",$frmBuscar['lstEmpleado']) : $frmBuscar['lstEmpleado']),
		(is_array($frmBuscar['lstTipoPago']) ? implode(",",$frmBuscar['lstTipoPago']) : $frmBuscar['lstTipoPago']),
		(is_array($frmBuscar['lstEstadoPedido']) ? implode(",",$frmBuscar['lstEstadoPedido']) : $frmBuscar['lstEstadoPedido']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/cc_pedido_historico_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaPedido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_pedido.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_pedido.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_pedido.fecha_registro BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));		
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_pedido.id_vendedor IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_pedido.condicion_pago = %s",
			valTpDato($valCadBusq[4], "boolean"));
	}
		
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_pedido.estado_pedido IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_pedido.id_modulo IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}	
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR cxc_pedido.numero_pedido LIKE %s
		OR cxc_pedido.observacion LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		cxc_pedido.id_pedido,
		cxc_pedido.id_empresa,
		cxc_pedido.fecha_registro,
		cxc_pedido.numero_pedido,
		cxc_pedido.id_modulo,
		vw_pg_empleado_vendedor.id_empleado AS id_empleado_vendedor,
		vw_pg_empleado_vendedor.nombre_empleado AS nombre_empleado_vendedor,
		cxc_pedido.condicion_pago,		
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_pedido.estado_pedido,
		(CASE cxc_pedido.estado_pedido
			WHEN 0 THEN 'Pendiente'
			WHEN 1 THEN 'Autorizado'
			WHEN 2 THEN 'Facturado'
			WHEN 3 THEN 'Desautorizado'
			WHEN 4 THEN 'Devuelta'
			WHEN 5 THEN 'Anulada'
		END) AS descripcion_estado_pedido,
		cxc_pedido.subtotal,
		cxc_pedido.subtotal_descuento,
		
		(IFNULL(cxc_pedido.subtotal, 0)
			- IFNULL(cxc_pedido.subtotal_descuento, 0)) AS total_neto,
			
		IFNULL((SELECT SUM(cxc_pedido_imp.subtotal_impuesto) FROM cj_cc_pedido_impuesto cxc_pedido_imp
				WHERE cxc_pedido_imp.id_pedido = cxc_pedido.id_pedido), 0) AS total_impuestos,
		
		cxc_pedido.monto_total,
		cxc_pedido.observacion,		
		vw_pg_empleado_creador.id_empleado AS id_empleado_creador,
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
				
		IFNULL((SELECT COUNT(cxc_pedido_det.id_pedido) FROM cj_cc_pedido_detalle cxc_pedido_det
						WHERE cxc_pedido_det.id_pedido = cxc_pedido.id_pedido), 0) AS cant_items
		
	FROM cj_cc_pedido cxc_pedido
		INNER JOIN cj_cc_cliente cliente ON (cxc_pedido.id_cliente = cliente.id)
		INNER JOIN vw_pg_empleados vw_pg_empleado_vendedor ON (cxc_pedido.id_vendedor = vw_pg_empleado_vendedor.id_empleado)
		INNER JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_pedido.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_pedido.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"3\" width=\"1%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedido", "12%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "4%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "4%", $pageNum, "numero_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "25%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");		
		$htmlTh .= ordenarCampo("xajax_listaPedido", "4%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "monto_total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Pedido");
		$htmlTh .= "<td colspan=\"10\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
		
		switch ($row['estado_pedido']) { 
			case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Pedido Pendiente\"/>"; break;
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Autorizado\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
		}
		
		$rowspan = (strlen($row['observacion']) > 0) ? "rowspan=\"2\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td ".$rowspan.">".$imgEstatusPedido."</td>";
			$htmlTb .= "<td ".$rowspan.">".$imgDctoModuloCondicion."</td>";
			$htmlTb .= "<td ".$rowspan.">".$imgDctoModulo."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"Pedido Nro: ".utf8_encode($row['numero_pedido']).". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "").">".date(spanDateFormat, strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_pedido']."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicion_pago'] == 1) ? "divMsjInfo" : "divMsjAlerta")."\" ".$rowspan.">";
				$htmlTb .= ($row['condicion_pago'] == 1) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['monto_total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td ".$rowspan." align=\"center\">";
				$htmlTb .= sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_pedido_form.php?id=%s&acc=0\" target=\"_self\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Pedido Venta")."\"/><a>",
					$row['id_pedido']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan." align=\"center\">";
			if (in_array($row['estado_pedido'], array(1))) {// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarDesautorizar('%s');\" src=\"../img/iconos/cancel.png\" title=\"Desautorizar Pedido\"/>",
					$row['id_pedido']);
			} 
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan." align=\"center\">";
			if (in_array($row['estado_pedido'], array(0,3))) {// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarAutorizar('%s');\" src=\"../img/iconos/accept.png\" title=\"Autorizar Pedido\"/>",
					$row['id_pedido']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan." align=\"center\">";
			if (in_array($row['estado_pedido'], array(0,3)) && $row['id_modulo'] != 5) {// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_editarPedido(%s);\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/>",
					$row['id_pedido']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan." align=\"center\">";
			if (in_array($row['estado_pedido'], array(0,1,3))) {// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarAnular('%s');\" src=\"../img/iconos/ico_delete.png\" title=\"Anular Pedido\"/>",
					$row['id_pedido']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan." align=\"center\">";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('reportes/cc_pedido_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Pedido Venta PDF")."\"/></a>", $row['id_pedido']);
			$htmlTb .= "</td>";

		$htmlTb .= "</tr>";
		
		if (strlen($rowspan) > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td colspan=\"4\">";
					$htmlTb .= ((strlen($row['observacion']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion'])."</div>" : "");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}
		
		$arrayTotal['cant_items'] += $row['cant_items'];
		$arrayTotal['monto_total'] += $row['monto_total'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal['monto_total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"10\"></td>";
		$htmlTb .= "</tr>";
		
		$arrayTotalFinal['cant_items'] += $arrayTotal['cant_items'];
		$arrayTotalFinal['monto_total'] += $arrayTotal['monto_total'];
		
		if ($pageNum == $totalPages) {
			if ($totalPages > 0) {
				$rs = mysql_query($query);
				$arrayTotalFinal = array();
				while ($row = mysql_fetch_assoc($rs)) {
					$arrayTotalFinal['cant_items'] += $row['cant_items'];
					$arrayTotalFinal['monto_total'] += $row['monto_total'];
				}
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['cant_items'], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal['monto_total'], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"10\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedido(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
	
	$objResponse->assign("divListaPedido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalImpuesto += $row['total_impuestos'];
		$totalPedidos += $row['monto_total'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalImpuesto","innerHTML",number_format($totalImpuesto, 2, ".", ","));
	$objResponse->assign("spnTotalPedidos","innerHTML",number_format($totalPedidos, 2, ".", ","));
	
	return $objResponse;
}

function nuevoPedido(){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_captura_pedido_list",'insertar')) { return $objResponse; }
	
	$objResponse->script("window.location.href='cc_pedido_form.php?acc=0'");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularPedido");
$xajax->register(XAJAX_FUNCTION,"autorizarPedido");
$xajax->register(XAJAX_FUNCTION,"buscarPedido");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoPedido");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"desautorizarPedido");
$xajax->register(XAJAX_FUNCTION,"editarPedido");
$xajax->register(XAJAX_FUNCTION,"exportarPedido");
$xajax->register(XAJAX_FUNCTION,"listaPedido");
$xajax->register(XAJAX_FUNCTION,"nuevoPedido");

?>