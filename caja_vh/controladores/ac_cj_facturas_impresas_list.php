<?php
function formEditarConsecutivoFiscal($idFatura,$consecutivo_fiscal){
	$objResponse = new xajaxResponse();
	if (!xvalidaAcceso($objResponse,"cj_facturas_impresas_list","editar")){ 
		$objResponse->alert("Acceso Denegado");
		return $objResponse; 
	}
	$serialImpresora = "MANUAL";
	$fechaEdicion = date("Y-m-d");
	$horaEdicion = date("H:i:s");
	if($consecutivo_fiscal != NULL){
		$queryFacturaValidar = sprintf("SELECT COUNT(idFactura) AS num FROM cj_cc_encabezadofactura WHERE consecutivo_fiscal = %s",
			valTpDato($consecutivo_fiscal, "text"));
		$rsFacturaValidar = mysql_query($queryFacturaValidar);
		if (!$rsFacturaValidar) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowValidar = mysql_fetch_assoc($rsFacturaValidar);

		if($rowValidar['num'] == 0){

			$queryFactura = sprintf("UPDATE cj_cc_encabezadofactura SET consecutivo_fiscal = %s, serial_impresora = %s, fecha_impresora = %s, hora_impresora = %s WHERE idFactura = %s",
				valTpDato($consecutivo_fiscal, "text"),
				valTpDato($serialImpresora, "text"),
				valTpDato($fechaEdicion, "text"),
				valTpDato($horaEdicion, "text"),
				valTpDato($idFatura, "int"));

			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$objResponse->script("byId('imgCerrarDivFlotante32').click()");
			$objResponse->script("byId('btnBuscar').click()");
		}else{
			$objResponse->alert("Introdujo un Consecutivo Fiscal ya registrado");
		}
	}else{
		$objResponse->alert("Debe introducir un Consecutivo Fiscal");		
	}
	return $objResponse;
}

function buscarFacturaEditarConsecutivoFiscal($idFactura){
	$objResponse = new xajaxResponse();
	$queryFactura = sprintf("SELECT cj_cc_encabezadofactura.consecutivo_fiscal,cj_cc_encabezadofactura.numeroFactura, cj_cc_encabezadofactura.numeroControl, pg_empresa.nombre_empresa, cj_cc_cliente.nombre, cj_cc_cliente.apellido FROM cj_cc_encabezadofactura 
			INNER JOIN cj_cc_cliente ON cj_cc_cliente.id = cj_cc_encabezadofactura.idCliente
			INNER JOIN pg_empresa ON pg_empresa.id_empresa = cj_cc_encabezadofactura.id_empresa
				WHERE idFactura = %s",
		valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	$cliente = $rowFactura['nombre'] . " " . $rowFactura['apellido'];
	$objResponse->assign ("nomEmpresa","innerHTML","<b>".$rowFactura['nombre_empresa']."</b>");
	$objResponse->assign ("nomCliente","innerHTML","<b>".$cliente."</b>");
	$objResponse->assign ("nroFactura","innerHTML","<b>".$rowFactura['numeroFactura']."</b>");
	$objResponse->assign ("nroControl","innerHTML","<b>".$rowFactura['numeroControl']."</b>");
	$objResponse->assign ("hddIdFactura","value",$idFactura);
	$objResponse->assign ("hddConsecutivoFiscal","value",$rowFactura['consecutivo_fiscal']);

	return $objResponse;
}

function reimprimirFactura($idFactura){
	$objResponse = new xajaxResponse();
	if (!xvalidaAcceso($objResponse,"cj_facturas_impresas_list","editar")){ 
		$objResponse->alert("Acceso Denegado");
		return $objResponse; 
	}
	$queryFactura=sprintf("UPDATE cj_cc_encabezadofactura SET consecutivo_fiscal = NULL , serial_impresora = NULL, fecha_impresora = NULL, hora_impresora = NULL WHERE idFactura = %s", valTpDato($idFactura, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$objResponse->alert("Factura lista para reimprimir.");
	$objResponse->script("byId('btnBuscar').click()");
	return $objResponse;
}


function buscarFactura($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstEmpleado']),
		$frmBuscar['lstAplicaLibro'],
		implode(",",$frmBuscar['lstEstadoFactura']),
		$frmBuscar['lstTipoPago'],
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['lstEstadoPedido'],
		implode(",",$frmBuscar['lstEstadoFiscal']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaFactura(0, "idFactura", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s)", valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstOrientacionPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array("V" => "Vertical", "H" => "Horizontal");
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirFactura(xajax.getFormValues('frmBuscar'));\"";
	
	$html = "<select id=\"lstOrientacionPDF\" name=\"lstOrientacionPDF\" ".$class." ".$onChange.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstOrientacionPDF","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstVendedor($selId = "") {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN vw_pg_empleados empleado ON (cxc_fact.idVendedor = empleado.id_empleado)
	WHERE cxc_fact.idDepartamentoOrigenFactura IN (%s)
	ORDER BY nombre_empleado",
		valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstEmpleado\" name=\"lstEmpleado\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpleado","innerHTML",$html);
	
	return $objResponse;
}

function exportarFactura($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstEmpleado']),
		$frmBuscar['lstAplicaLibro'],
		implode(",",$frmBuscar['lstEstadoFactura']),
		$frmBuscar['lstTipoPago'],
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['lstEstadoPedido'],
		implode(",",$frmBuscar['lstEstadoFiscal']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/cjvh_factura_impresa_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function imprimirFactura($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstEmpleado']),
		$frmBuscar['lstAplicaLibro'],
		implode(",",$frmBuscar['lstEstadoFactura']),
		$frmBuscar['lstTipoPago'],
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['lstEstadoPedido'],
		implode(",",$frmBuscar['lstEstadoFiscal']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script(sprintf("verVentana('reportes/cjvh_factura_impresa_pdf.php?valBusq=%s&lstOrientacionPDF=%s',890,550)", $valBusq, $frmBuscar['lstOrientacionPDF']));
	
	$objResponse->assign("tdlstOrientacionPDF","innerHTML","");
	
	return $objResponse;
}

function listaFactura($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
		valTpDato($idModuloPpal, "campo"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idVendedor IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.aplicaLibros = %s",
			valTpDato($valCadBusq[4], "boolean"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.estadoFactura IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.condicionDePago = %s",
			valTpDato($valCadBusq[6], "boolean"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (%s)",
			valTpDato($valCadBusq[7], "campo"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_fact.anulada LIKE %s",
			valTpDato($valCadBusq[8], "text"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$arrayConsecutivoFiscal = explode(",",$valCadBusq[9]);
		if (count($arrayConsecutivoFiscal) == 1) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			if (in_array(1,$arrayConsecutivoFiscal) && !in_array(2,$arrayConsecutivoFiscal)) {
				$sqlBusq .= $cond.sprintf("cxc_fact.consecutivo_fiscal IS NOT NULL");
			} else if (!in_array(1,$arrayConsecutivoFiscal) && in_array(2,$arrayConsecutivoFiscal)) {
				$sqlBusq .= $cond.sprintf("cxc_fact.consecutivo_fiscal IS NULL");
			}
		}
	}
	
	if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR cxc_fact.numeroFactura LIKE %s
		OR cxc_fact.numeroControl LIKE %s
		OR cxc_fact.consecutivo_fiscal LIKE %s
		OR cxc_fact.serial_impresora LIKE %s
		OR ped_vent.id_pedido_venta_propio LIKE %s
		OR orden.numero_orden LIKE %s
		OR an_ped_vent.numeracion_pedido LIKE %s)",
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cxc_ec.idEstadoDeCuenta AS idEstadoCuenta,
		cxc_ec.tipoDocumentoN,
		cxc_ec.tipoDocumento,
		cxc_fact.idFactura,
		cxc_fact.id_empresa,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.fechaVencimientoFactura,
		cxc_fact.fecha_pagada,
		cxc_fact.fecha_cierre,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl,
		cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
		vw_pg_empleado_vendedor.id_empleado AS id_empleado_vendedor,
		vw_pg_empleado_vendedor.nombre_empleado AS nombre_empleado_vendedor,
		cxc_fact.condicionDePago,
		cxc_fact.numeroPedido,
		
		(SELECT an_ped_vent2.id_pedido FROM an_pedido an_ped_vent2
		WHERE an_ped_vent2.id_factura_cxc = cxc_fact.idFactura
			AND an_ped_vent2.estado_pedido IN (0,1,2,3,4)) AS id_pedido_reemplazo,
		
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		cond_unidad.descripcion AS condicion_unidad,
		ped_comp_det.flotilla,
		cxc_fact.estadoFactura,
		(CASE cxc_fact.estadoFactura
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_factura,
		cxc_fact.aplicaLibros,
		cxc_fact.anulada,
		cxc_fact.estatus_factura,
		cxc_fact.subtotalFactura,
		cxc_fact.descuentoFactura,
		(IFNULL(cxc_fact.subtotalFactura, 0)
			- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
		IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
				WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_impuestos,
		cxc_fact.montoTotalFactura,
		cxc_fact.saldoFactura,
		cxc_fact.observacionFactura,
		
		vw_pg_empleado_creador.id_empleado AS id_empleado_creador,
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.fecha
			WHEN 1 THEN		orden.tiempo_orden
			WHEN 2 THEN		an_ped_vent.fecha
			ELSE			NULL
		END) AS fecha_pedido,
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		NULL
			WHEN 1 THEN		NULL
			WHEN 2 THEN		an_ped_vent.fecha_reserva_venta
			ELSE			NULL
		END) AS fecha_reserva_venta,
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		NULL
			WHEN 1 THEN		orden.tiempo_entrega
			WHEN 2 THEN		an_ped_vent.fecha_entrega
			ELSE			NULL
		END) AS fecha_entrega,
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN		ped_vent.id_pedido_venta_propio
			WHEN 1 THEN		orden.numero_orden
			WHEN 2 THEN		an_ped_vent.numeracion_pedido
			ELSE			NULL
		END) AS numero_pedido,
		ped_vent.estatus_pedido_venta,
		tipo_orden.nombre_tipo_orden,
		banco.nombreBanco,
		
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 0 THEN
				IFNULL((SELECT COUNT(cxc_fact_det.id_factura) FROM cj_cc_factura_detalle cxc_fact_det
						WHERE cxc_fact_det.id_factura = cxc_fact.idFactura), 0)
			WHEN 1 THEN
				(IFNULL((SELECT COUNT(sa_fact_det_art.idFactura) FROM sa_det_fact_articulo sa_fact_det_art
						WHERE sa_fact_det_art.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_nota.idFactura) FROM sa_det_fact_notas sa_fact_det_nota
							WHERE sa_fact_det_nota.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_temp.idFactura) FROM sa_det_fact_tempario sa_fact_det_temp
							WHERE sa_fact_det_temp.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_tot.idFactura) FROM sa_det_fact_tot sa_fact_det_tot
							WHERE sa_fact_det_tot.idFactura = cxc_fact.idFactura), 0))
			WHEN 2 THEN
				(IFNULL((SELECT COUNT(cxc_fact_det_acc.id_factura) FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
						WHERE cxc_fact_det_acc.id_factura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(cxc_fact_det_vehic.id_factura) FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
							WHERE cxc_fact_det_vehic.id_factura = cxc_fact.idFactura), 0))
			WHEN 3 THEN
				IFNULL((SELECT COUNT(cxc_fact_det_adm.id_factura) FROM cj_cc_factura_detalle_adm cxc_fact_det_adm
					WHERE cxc_fact_det_adm.id_factura = cxc_fact.idFactura), 0)
		END) AS cant_items,
		
		(CASE cxc_fact.idDepartamentoOrigenFactura
			WHEN 2 THEN
				IFNULL((SELECT COUNT(cxc_fact_det_acc.id_factura) FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
						WHERE cxc_fact_det_acc.id_factura = cxc_fact.idFactura), 0)
		END) AS cant_accesorios,
		
		cxc_fact.consecutivo_fiscal,
		cxc_fact.serial_impresora,
		cxc_fact.fecha_impresora,
		cxc_fact.hora_impresora,
		CONCAT_WS('/', cxc_fact.consecutivo_fiscal, cxc_fact.serial_impresora) AS consecutivo_serial,
		CONCAT_WS(' ', cxc_fact.fecha_impresora, cxc_fact.hora_impresora) AS fecha_hora
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (cxc_fact.idFactura = cxc_fact_det_acc.id_factura)
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		LEFT JOIN cj_cc_estadocuenta cxc_ec ON (cxc_fact.idFactura = cxc_ec.idDocumento AND cxc_ec.tipoDocumento LIKE 'FA')
		LEFT JOIN iv_pedido_venta ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido_venta AND cxc_fact.idDepartamentoOrigenFactura = 0)
		LEFT JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden AND cxc_fact.idDepartamentoOrigenFactura = 1)
			LEFT JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden AND cxc_fact.idDepartamentoOrigenFactura = 1)
		LEFT JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica AND cxc_fact.idDepartamentoOrigenFactura = 2)
				LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
				LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_vendedor ON (cxc_fact.idVendedor = vw_pg_empleado_vendedor.id_empleado)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_fact.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaFactura", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "fechaVencimientoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "numero_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido / Orden");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "8%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "6%", $pageNum, "condicionDePago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "8%", $pageNum, "descripcion_estado_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "fecha_hora", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha / Hora Impresora");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "consecutivo_serial", $campOrd, $tpOrd, $valBusq, $maxRows, "Consecutivo Fiscal / Serial Impresora");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "8%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Factura");
		$htmlTh .= ordenarCampo("xajax_listaFactura", "8%", $pageNum, "montoTotalFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan='2'></td>";
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
			
		$imgEstatusPedido = ($row['anulada'] == "SI") ? "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>" : "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
		
		switch($row['estadoFactura']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgDctoModulo."</td>";
			$htmlTb .= "<td>".$imgDctoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaVencimientoFactura']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_pedido']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['observacionFactura']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionFactura'])."<span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicionDePago'] == 1) ? "divMsjInfo" : "divMsjAlerta")."\">";
				$htmlTb .= ($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['descripcion_estado_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['fecha_hora']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['consecutivo_serial'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoTotalFactura'], 2, ".", ",")."</td>";

			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">";
			$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditarFactura\" rel=\"#divFlotante3\" onclick=\"abrirDivFlotante3(this, 'tblEditarConsecutivo', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>", 
					
					$row['idFactura']);
			$htmlTb .= "</td>";

			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">";
			$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aReimprimirFactura\" rel=\"\" onclick=\"reimprimirFactura(this, 'tblPrivilegio', '%s');\"><img class=\"puntero\" src=\"../img/cc.png\" title=\"Reimprimir\"/></a>", 
					
					$row['idFactura']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[13] += $row['cant_items'];
		$arrayTotal[14] += $row['saldoFactura'];
		$arrayTotal[15] += $row['montoTotalFactura'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"15\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[14], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[15], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"4\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[13] += $row['cant_items'];
				$arrayTotalFinal[14] += $row['saldoFactura'];
				$arrayTotalFinal[15] += $row['montoTotalFactura'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"15\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[15], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"4\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"22\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFactura(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFactura(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"21\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaFactura","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_iva'];
		$totalFacturas += $row['total'];
		$totalSaldo += $row['saldoFactura'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalFacturas, 2, ".", ","));
	$objResponse->assign("spnSaldoFacturas","innerHTML",number_format($totalSaldo, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarFactura");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstOrientacionPDF");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"exportarFactura");
$xajax->register(XAJAX_FUNCTION,"imprimirFactura");
$xajax->register(XAJAX_FUNCTION,"listaFactura");
$xajax->register(XAJAX_FUNCTION,"reimprimirFactura");
$xajax->register(XAJAX_FUNCTION,"buscarFacturaEditarConsecutivoFiscal");
$xajax->register(XAJAX_FUNCTION,"formEditarConsecutivoFiscal");
?>