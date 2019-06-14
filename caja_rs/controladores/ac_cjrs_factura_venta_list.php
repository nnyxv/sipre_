<?php


function buscar($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaPedidoVenta(0, "id_pedido_venta", "DESC", $valBusq));
	$objResponse->loadCommands(listaPedidoVentaServicio(0, "numero_orden", "DESC", $valBusq));
	$objResponse->loadCommands(listaPedidoVentaAdmon(0, "numero_pedido", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstIva($nombreObjeto, $selId = "", $selVal = "", $bloquearObj = false, $alturaObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	$style = ($alturaObj == true) ? "style=\"height:200px; width:99%\"" : " style=\"width:99%\"";
	
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 ORDER BY iva;");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select  id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." ".$style.">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	$selected = "";
	if ((in_array(0,explode(",",$selId)) || in_array(-1,explode(",",$selId))) && !(count(explode(",",$selId)) > 1) && $selId != "") {
		$selected = "selected=\"selected\"";
	}
		$html .= "<option ".$selected." value=\"0\">-</option>";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if (in_array($rowIva['iva'],explode(",",$selVal)) && in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['iva'],explode(",",$selVal))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if ($selId == "" && in_array($rowIva['tipo'],array(1,6)) && $rowIva['activo'] == 1) { // IMPUESTO PREDETERMINADO
				$selected = "selected=\"selected\"";
			}
			
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargarPagina($idEmpresa){
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	if ($rowConfig400['valor'] == 0) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, "onchange=\"selectedOption(this.id,'".$idEmpresa."');\""));
	} else {
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa));
	}
	
	$objResponse->script("xajax_buscar(xajax.getFormValues('frmBuscar'));");
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	return $objResponse;
}

function devolverOrden($idOrden, $frmListaPedidoVentaServicio){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE sa_orden SET
		id_estado_orden = 21,
		id_empleado_aprobacion_factura = NULL
	WHERE id_orden = %s;",
		valTpDato($idOrden, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert('Orden de servicio devuelta con éxito');
	
	$objResponse->loadCommands(listaPedidoVentaServicio(
		$frmListaPedidoVentaServicio['pageNum'],
		$frmListaPedidoVentaServicio['campOrd'],
		$frmListaPedidoVentaServicio['tpOrd'],
		$frmListaPedidoVentaServicio['valBusq']));
	
	return $objResponse;
}

function devolverPedido($idPedido, $frmListaPedidoVenta) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_pedido_venta_list","editar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DEL PEDIDO DE VENTA
	$queryPedido = sprintf("SELECT * FROM vw_iv_pedidos_venta
	WHERE id_pedido_venta = %s;",
		valTpDato($idPedido, "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	$estatusPedidoVenta = 1; // 0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado, 4 = Devuelta, 5 = Anulada
	
	// EDITA LOS DATOS DEL PEDIDO
	$updateSQL = sprintf("UPDATE iv_pedido_venta SET
		estatus_pedido_venta = %s,
		id_empleado_aprobador = NULL,
		fecha_aprobacion = NULL
	WHERE id_pedido_venta = %s
		AND estatus_pedido_venta IN (2);",
		valTpDato($estatusPedidoVenta, "int"),
		valTpDato($idPedido, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// SE CONECTA CON EL SISTEMA DE SOLICITUDES
	$Result1 = actualizarEstatusSistemaSolicitud($rowPedido['id_pedido_venta_referencia'], $estatusPedidoVenta);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$objResponse->alert($Result1[1]);
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Pedido de venta devuelto con éxito");
	
	$objResponse->loadCommands(listaPedidoVenta(
		$frmListaPedidoVenta['pageNum'],
		$frmListaPedidoVenta['campOrd'],
		$frmListaPedidoVenta['tpOrd'],
		$frmListaPedidoVenta['valBusq']));
	
	return $objResponse;
}

function formImpuesto($idOrden) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT id_orden, numero_orden, IFNULL((SELECT orden_iva.base_imponible
														FROM sa_orden_iva orden_iva 
														WHERE orden_iva.id_orden = sa_orden.id_orden LIMIT 1), 0) AS base_imponible
		FROM sa_orden WHERE id_orden = %s AND id_estado_orden = 13",
		valTpDato($idOrden, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if(mysql_num_rows($rs) == 0){ return $objResponse->alert("La orden no posee estado 'Terminado'"); }
	
	$objResponse->assign("txtNumeroOrdenImpuesto","value",$row['numero_orden']);
	$objResponse->assign("txtBaseImponibleOrden","value",number_format($row['base_imponible'], 2, ".", ","));
	$objResponse->assign("hddIdOrdenImpuesto","value",$row['id_orden']);
	$objResponse->assign("lstTipoPagoCambio","value",-1);
	
	$htmlIva = "<select id=\"lstIvaCbxCambio\" name=\"lstIvaCbxCambio\">
	<option value=\"-1\">[ Seleccione ]</option>
	</select>";
	//$objResponse->loadCommands(cargaLstIva("lstIvaCbxCambio", "", "", false, true));
	$objResponse->assign("tdlstIvaCbxCambio","innerHTML",$htmlIva);
	
	$objResponse->script("byId('lstTipoPagoCambio').className = 'inputHabilitado'");
	
	return $objResponse;
}

function guardarImpuestoServicio($frmImpuesto){// SOLO PARA VZLA
	$objResponse = new xajaxResponse();
	
	$hddIdIvaItm = (is_array($frmImpuesto['lstIvaCbxCambio'])) ? implode(",",$frmImpuesto['lstIvaCbxCambio']) : $frmImpuesto['lstIvaCbxCambio'];
	
	$query = sprintf("SELECT * FROM sa_orden WHERE id_orden = %s",
		valTpDato($frmImpuesto["hddIdOrdenImpuesto"], "int"));
	$rs = mysql_query($query);	
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowOrden = mysql_fetch_assoc($rs);
	
	$idOrden = $rowOrden['id_orden'];
	$idEmpresa = $rowOrden['id_empresa'];
	
	mysql_query("START TRANSACTION;");
	
	$query = sprintf("DELETE FROM sa_orden_iva WHERE id_orden = %s",
		valTpDato($idOrden, "int"));
	$rs = mysql_query($query);	
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);		

	$query = sprintf("DELETE FROM sa_det_orden_articulo_iva
	WHERE id_det_orden_articulo IN (SELECT id_det_orden_articulo 
										FROM sa_det_orden_articulo 
										WHERE id_orden = %s)",
		valTpDato($idOrden, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__.$query);
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
	WHERE iva.tipo IN (6,9,2)
		AND iva.idIva IN (%s);",
		valTpDato($hddIdIvaItm, "campo"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$arrayIva = array();
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$arrayIva[$rowIva["idIva"]] = $rowIva;
	}
	
	$query = sprintf("SELECT * FROM sa_det_orden_articulo WHERE id_orden = %s",
		valTpDato($idOrden, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	while ($rowArt = mysql_fetch_assoc($rs)){		
		foreach ($arrayIva as $indice => $rowIva){
			$query = sprintf("INSERT INTO sa_det_orden_articulo_iva (id_det_orden_articulo, id_iva, iva) 
			VALUES (%s, %s, %s)",
				valTpDato($rowArt["id_det_orden_articulo"], "int"),
				valTpDato($rowIva["idIva"], "int"),
				valTpDato($rowIva["iva"], "real_inglesa"));
			$rsIva = mysql_query($query);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);			
		}
	}
	
	foreach ($arrayIva as $indice => $rowIva){//FALTA MONTOS, SE AGREGAN AL CALCULAR LA ORDEN
		$query = sprintf("INSERT INTO sa_orden_iva (id_orden, id_iva, iva) 
		VALUES (%s, %s, %s)",
			valTpDato($idOrden, "int"),
			valTpDato($rowIva["idIva"], "int"),
			valTpDato($rowIva["iva"], "real_inglesa"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	$Result1 = actualizarOrdenServicio($idOrden);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	//REVISO MONTO CALCULADO
	$query = sprintf("SELECT * FROM sa_orden_iva 
	WHERE id_orden = %s 
	AND (base_imponible = 0 OR subtotal_iva = 0 OR iva = 0)",
		valTpDato($idOrden, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if (mysql_num_rows($rs) > 0) { return $objResponse->alert("El impuesto no puede ser cero"); }
	
	mysql_query("COMMIT;");
	
	/*$objResponse->script("byId('btnCancelarImpuesto').click();
	byId('btnBuscar').click();");*/
	
	$objResponse->alert("Impuesto actualizado correctamente");
	
	$objResponse->script(sprintf("window.open('cjrs_facturacion_devolucion_servicios_form.php?doc_type=3&id=%s&ide=%s&acc=2&cons=1','_self');",
		$idOrden,
		$idEmpresa));
		
	return $objResponse;
}

function imprimirPedidoVenta($frmBuscar){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjrs_factura_venta_r_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function imprimirOrdenServicio($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjrs_factura_venta_s_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listaPedidoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_pedido_venta NOT IN (0,1,3,4)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(estatus_pedido_venta = 2 AND id_empleado_aprobador IS NOT NULL)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_pedidos_venta.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_pedidos_venta.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_pedido_venta_propio LIKE %s
		OR id_pedido_venta_referencia LIKE %s
		OR numeracion_presupuesto LIKE %s
		OR ci_cliente LIKE %s
		OR nombre_cliente LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT *,
		(SELECT COUNT(ped_venta_det.id_pedido_venta) AS items
		FROM iv_pedido_venta_detalle ped_venta_det
		WHERE ped_venta_det.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta) AS items,
		
		(SELECT SUM(ped_venta_det.cantidad) AS pedidos
		FROM iv_pedido_venta_detalle ped_venta_det
		WHERE ped_venta_det.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta) AS pedidos,
		
		(SELECT SUM(ped_venta_det.pendiente) AS pendientes
		FROM iv_pedido_venta_detalle ped_venta_det
		WHERE ped_venta_det.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta) AS pendientes,
		
		(vw_iv_pedidos_venta.subtotal - vw_iv_pedidos_venta.subtotal_descuento) AS total_neto,
		
		(SELECT SUM(ped_vent_iva.subtotal_iva)
		FROM iv_pedido_venta_iva ped_vent_iva
		WHERE ped_vent_iva.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta) AS total_iva,
		
		(vw_iv_pedidos_venta.subtotal
			- vw_iv_pedidos_venta.subtotal_descuento
			+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
					WHERE ped_vent_gasto.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta), 0)
			+ IFNULL((SELECT SUM(ped_iva.subtotal_iva) AS total_iva
					FROM iv_pedido_venta_iva ped_iva
					WHERE ped_iva.id_pedido_venta = vw_iv_pedidos_venta.id_pedido_venta), 0)) AS total,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM
		vw_iv_pedidos_venta
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_pedidos_venta.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "", $pageNum, "estatus_pedido_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "18%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "id_pedido_venta_propio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "id_pedido_venta_referencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Referencia");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "numeracion_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "numero_siniestro", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Siniestro");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "18%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Pedido");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estatus_pedido_venta']) {
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Aprobado\"/>"; break;
			default : $imgEstatusPedido = "";
		}	
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_propio']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_pedido_venta_referencia']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_presupuesto']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_siniestro']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= ($row['condicion_pago'] == 0) ? "<td align=\"center\" class=\"divMsjAlerta\">" : "<td align=\"center\" class=\"divMsjInfo\">";
			$htmlTb .= ($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" id=\"imgDevolverPedido%s\" onclick=\"validarDevolverPedido('%s', '%s')\" src=\"../img/iconos/ico_return.png\" title=\"Devolver Pedido\"/></td>",
				$contFila,
				$row['id_pedido_venta'],
				$contFila);
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('cjrs_facturacion_repuestos_form.php?id=%s', '_self');\" src=\"../img/iconos/book_next.png\" title=\"Facturar\"/>", $row['id_pedido_venta']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_cj_rs.gif\"/>");
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
		$htmlTb .= "<td colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPedidoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPedidoVentaAdmon($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_pedido.estado_pedido IN (1)");// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_pedido.id_modulo IN (3)");// 5 = Administración
	
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
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR cxc_pedido.numero_pedido LIKE %s
		OR cxc_pedido.observacion LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
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
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "12%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "4%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "4%", $pageNum, "numero_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "25%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");		
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "4%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "6%", $pageNum, "monto_total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Pedido");
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
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('cjrs_facturacion_admon_form.php?id=%s','_self');\" src=\"../img/iconos/book_next.png\" title=\"Facturar\"/>",
					$row['id_pedido']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if (strlen($rowspan) > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td colspan=\"4\">";
					$htmlTb .= ((strlen($row['observacion']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion'])."</div>" : "");
				$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAdmon(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAdmon(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVentaAdmon(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAdmon(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAdmon(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_cj_rs.gif\"/>");
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
	
	$objResponse->assign("divListaPedidoVentaAdmon","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);;
	
	return $objResponse;
}

function listaPedidoVentaServicio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// 13 = TERMINADO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_sa_orden.id_estado_orden = 13 AND vw_sa_orden.modo_factura != 'VALE SALIDA'"); 
			
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_sa_orden.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_sa_orden.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(tiempo_orden) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) LIKE %s
		OR vw_sa_orden.numero_orden = %s
		OR vw_sa_orden.placa LIKE %s
		OR vw_sa_orden.chasis LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato($valCadBusq[3], "int"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
		
	$query = sprintf("SELECT
		CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
		cj_cc_cliente.lci,
		cj_cc_cliente.ci,
		cj_cc_cliente.nit,

		(IF(cita.id_cliente_contacto != orden.id_cliente,
			(SELECT CONCAT_WS(' ', cj.nombre, cj.apellido) FROM cj_cc_cliente cj WHERE cj.id = cita.id_cliente_contacto),
			NULL)) AS nombre_cliente_anterior,

		vw_sa_orden.*,
		sa_retrabajo_orden.id_orden_retrabajo,
		(SELECT vw_sa_orden.numero_orden FROM vw_sa_orden WHERE vw_sa_orden.id_orden = sa_retrabajo_orden.id_orden_retrabajo) AS numero_orden_retrabajo,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_cliente
		INNER JOIN sa_orden orden ON (cj_cc_cliente.id = orden.id_cliente)
		INNER JOIN vw_sa_orden ON (orden.id_orden = vw_sa_orden.id_orden)
		INNER JOIN sa_recepcion ON (vw_sa_orden.id_recepcion = sa_recepcion.id_recepcion)
		INNER JOIN sa_cita cita ON (sa_recepcion.id_cita = cita.id_cita)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_sa_orden.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		LEFT JOIN sa_retrabajo_orden ON (vw_sa_orden.id_orden = sa_retrabajo_orden.id_orden) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
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
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "13%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "7%", $pageNum, "tiempo_orden_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "7%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Orden"));
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "7%", $pageNum, "numeracion_recepcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Recepción"));
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "7%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, ("Catálogo"));
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "7%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, "Placa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "13%", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, "Chasis");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "7%", $pageNum, "numero_orden_retrabajo", $campOrd, $tpOrd, $valBusq, $maxRows, "Orden Retrabajo");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "18%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "7%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaServicio", "7%", $pageNum, "total_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Orden");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	$objResponse->assign("tdTituloListado","innerHTML",("Facturación de Servicios"));
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$clienteAnterior = "";
		if($row["nombre_cliente_anterior"]){
			$clienteAnterior = "<br><small><b>Ant: ".utf8_encode($row["nombre_cliente_anterior"])."</b></small>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['tiempo_orden_registro']))."</td>";
			$htmlTb .= "<td align=\"right\" idordenoculta=\"".$row['id_orden']."\" idempresaoculta=\"".$row['id_empresa']."\">".$row['numero_orden']."</td>";
			$htmlTb .= "<td align=\"right\" idrecepcionoculto =\"".$row['id_recepcion']."\">".$row['numeracion_recepcion']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['chasis'])."</td>";
			$htmlTb .= "<td align=\"right\" idordenocultaretrabajo =\"".$row['id_orden_retrabajo']."\" >".$row['numero_orden_retrabajo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente']).$clienteAnterior."</td>";
			$htmlTb .= ($row['nombre_tipo_orden'] == 'CREDITO') ? "<td align=\"center\" class=\"divMsjAlerta\">" : "<td align=\"center\" class=\"divMsjInfo\">";
			$htmlTb .= $row['nombre_tipo_orden'];
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_orden'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aDevolver%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPermiso', 'ac_sa_orden_list', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_return.png\" title=\"Devolver Orden\"/></a>",
					$contFila,
					$row['id_orden'],
					$row['numero_orden']);
			$htmlTb .= "</td>";			
			// ABRIR OPCION "SELECCIONA TIPO DE PAGO PARA CAMBIAR TIPO DE IMPUESTO" //COMENTADO YA NO VA POR LEY
			//if (in_array(idArrayPais,array(1))) {// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
				//$htmlTb .= "<td>";
					//$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aImpuesto%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblImpuesto', '%s');\"><img class=\"puntero\" src=\"../img/iconos/book_next.png\" title=\"Impuesto\"/></a>",
						//$contFila,
						//$row['id_orden']);
				//$htmlTb .= "</td>";
			//} else {	
				$htmlTb .= "<td>";					
					$htmlTb .= sprintf("<img class=\"puntero\" src=\"../img/iconos/book_next.png\" title=\"Facturar\" onclick=\"window.open('cjrs_facturacion_devolucion_servicios_form.php?doc_type=3&id=%s&ide=%s&acc=2&cons=1','_self');\"/>",
						$row['id_orden'],
						$row['id_empresa']);
				$htmlTb .= "</td>";
			//}
		$html .= "</tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVentaServicio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaServicio(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_cj_rs.gif\"/>");
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
	
	$objResponse->assign("divListaPedidoVentaServicio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarPermiso($frmPermiso){
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT *
	FROM pg_empleado empleado
		INNER JOIN pg_usuario usuario ON (empleado.id_empleado = usuario.id_empleado)
		INNER JOIN sa_claves clave ON (empleado.id_empleado = clave.id_empleado)
	WHERE usuario.id_usuario = %s
		AND clave.clave = %s
		AND clave.modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato(md5($frmPermiso['txtContrasena']), "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPermiso = mysql_num_rows($rsPermiso);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($totalRowsPermiso > 0) {
		if ($frmPermiso['hddModulo'] == "ac_sa_orden_list") {
			$objResponse->script("xajax_devolverOrden('".$frmPermiso['hddIdOrden']."', xajax.getFormValues('frmListaPedidoVentaServicio'));");
		}
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstIva");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"devolverOrden");
$xajax->register(XAJAX_FUNCTION,"devolverPedido");
$xajax->register(XAJAX_FUNCTION,"formImpuesto");
$xajax->register(XAJAX_FUNCTION,"guardarImpuestoServicio");
$xajax->register(XAJAX_FUNCTION,"imprimirPedidoVenta");
$xajax->register(XAJAX_FUNCTION,"imprimirOrdenServicio");
$xajax->register(XAJAX_FUNCTION,"listaPedidoVenta");
$xajax->register(XAJAX_FUNCTION,"listaPedidoVentaAdmon");
$xajax->register(XAJAX_FUNCTION,"listaPedidoVentaServicio");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function validarAperturaCaja($idEmpresa, $fecha) {
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM ".$apertCajaPpal." ape
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND id_empresa = %s;",
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$totalRowsCierreCaja = mysql_num_rows($rsCierreCaja);
	$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
	
	if ($totalRowsCierreCaja > 0) {
		return array(false, "Debe cerrar la caja del dia: ".date(spanDateFormat, strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND id_empresa = %s;",
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$totalRowsVerificarApertura = mysql_num_rows($rsVerificarApertura);
		
		return ($totalRowsVerificarApertura > 0) ? array(true, "") : array(false, "Esta caja no tiene apertura");
	}
}
?>