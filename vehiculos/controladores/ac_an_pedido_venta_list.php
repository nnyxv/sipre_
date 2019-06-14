<?php


function anularPedido($idPedido, $frmListaPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// ANULA EL PEDIDO
	$updateSQL = sprintf("UPDATE an_pedido SET
		estado_pedido = 5
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// ANULA LOS ACCESORIOS DEL PEDIDO
	$updateSQL = sprintf("UPDATE an_accesorio_pedido SET
		estatus_accesorio_pedido = 2
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 0 = Pendiente, 1 = Facturado, 2 = Anulado
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// ANULA LOS ACCESORIOS DEL PAQUETE DEL PEDIDO
	$updateSQL = sprintf("UPDATE an_paquete_pedido SET
		estatus_paquete_pedido = 2
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 0 = Pendiente, 1 = Facturado, 2 = Anulado
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// ACTUALIZA EL ESTATUS DE LA UNIDAD FISICA AGREGADA AL PEDIDO DE VENTA
	$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis, an_pedido an_ped_vent SET
		estado_venta = (CASE uni_fis.estado_compra
							WHEN 'COMPRADO' THEN 'POR REGISTRAR'
							WHEN 'REGISTRADO' THEN 'DISPONIBLE'
						END)
	WHERE uni_fis.id_unidad_fisica = an_ped_vent.id_unidad_fisica
		AND uni_fis.estado_venta LIKE 'RESERVADO'
		AND an_ped_vent.id_pedido = %s;",
		valTpDato($idPedido, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPedido(
		$frmListaPedido['pageNum'],
		$frmListaPedido['campOrd'],
		$frmListaPedido['tpOrd'],
		$frmListaPedido['valBusq']));

	return $objResponse;
}

function autorizarPedido($idPedido, $frmListaPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","desautorizar")) { return $objResponse; }
	
	// BUSCA LOS DATOS DEL PEDIDO
	$queryPedido = sprintf("SELECT an_ped_vent.*,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		cxc_fact.idFactura AS id_factura_reemplazo,
		cxc_fact.numeroFactura AS numero_factura_reemplazo,
		pres_vent_acc.id_presupuesto_accesorio,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
		CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_contacto,
		cliente.tipo,
		cliente.ciudad,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.tipo_cuenta_cliente,
		clave_mov.id_clave_movimiento,
		clave_mov.clave,
		clave_mov.descripcion AS descripcion_clave_movimiento,
		IFNULL(uni_fis.id_uni_bas, pres_vent.id_uni_bas) AS id_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		uni_fis.estado_venta,
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo,
		vw_pg_empleado.telefono,
		vw_pg_empleado.celular,
		vw_pg_empleado.email,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.idmoneda, moneda_local.idmoneda) AS id_moneda,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.descripcion, moneda_local.descripcion) AS descripcion_moneda,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
		pres_vent.id_banco_financiar,
		banco.nombreBanco,
		pres_vent.estado AS estado_presupuesto,
		an_ped_vent.estado_pedido,
		ped_financ.id_pedido_financiamiento,
		ped_financ.numeracion_pedido AS numeracion_pedido_financiamiento,
		ped_financ.estatus_pedido AS estatus_pedido_financiamiento,
		adicional_contrato.nombre_agencia_seguro,
		adicional_contrato.direccion_agencia_seguro,
		adicional_contrato.ciudad_agencia_seguro,
		adicional_contrato.pais_agencia_seguro,
		adicional_contrato.telefono_agencia_seguro,
		
		IF(vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_pedido an_ped_vent
		INNER JOIN cj_cc_cliente cliente ON (an_ped_vent.id_cliente = cliente.id)
		INNER JOIN pg_monedas moneda_local ON (an_ped_vent.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (an_ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		INNER JOIN pg_clave_movimiento clave_mov ON (an_ped_vent.id_clave_movimiento = clave_mov.id_clave_movimiento)
		LEFT JOIN an_unidad_fisica uni_fis ON (an_ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
			LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
		LEFT JOIN an_adicionales_contrato adicional_contrato ON (an_ped_vent.id_pedido = adicional_contrato.id_pedido)
		LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
			LEFT JOIN fi_documento ped_financ_det ON (pres_vent.id_presupuesto = ped_financ_det.id_presupuesto)
				LEFT JOIN fi_pedido ped_financ ON (ped_financ_det.id_pedido_financiamiento = ped_financ.id_pedido_financiamiento)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (an_ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (an_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (an_ped_vent.id_factura_cxc = cxc_fact.idFactura)
	WHERE an_ped_vent.id_pedido = %s;",
		valTpDato($idPedido, "int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPedido = mysql_num_rows($rsPedido);
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	$idEmpresa = $rowPedido['id_empresa'];
	$idUnidadFisica = $rowPedido['id_unidad_fisica'];
	$idUnidadBasica = $rowPedido['id_uni_bas'];
	
	if ($rowPedido['tipo_cuenta_cliente'] == 1) {
		return $objResponse->alert("El pedido no puede ser aprobado debido a que el prospecto perteneciente a este pedido no está aprobado como cliente. Recomendamos lo apruebe en la pantalla de Prospectación");
	}
	
	if ($idUnidadBasica > 0 && !($idUnidadFisica > 0)) {
		return $objResponse->alert("El pedido no puede ser aprobado debido a que no tiene unidad seleccionada");
	}
	
	// VERIFICA QUE LA UNIDAD ESTE REGISTRADA
	$queryUnidadFisica = sprintf("SELECT * FROM an_unidad_fisica uni_fis
	WHERE uni_fis.id_unidad_fisica = %s
		AND uni_fis.estado_compra IN ('COMPRADO');",
		valTpDato($idUnidadFisica, "int"));
	$rsUnidadFisica = mysql_query($queryUnidadFisica);
	if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsUnidadFisica = mysql_num_rows($rsUnidadFisica);
	
	if ($totalRowsUnidadFisica > 0) {
		return $objResponse->alert("El pedido no puede ser aprobado debido a que el registro de compra de la unidad no ha finalizado totalmente.");
	}
	
	// VERIFICA QUE LA UNIDAD PERTENEZCA A UN ALMACEN DE LA EMPRESA
	$queryAlmacen = sprintf("SELECT *
	FROM an_almacen alm
		INNER JOIN an_unidad_fisica uni_fis ON (alm.id_almacen = uni_fis.id_almacen)
	WHERE uni_fis.id_unidad_fisica = %s
		AND alm.id_empresa <> %s;",
		valTpDato($idUnidadFisica, "int"),
		valTpDato($idEmpresa, "int"));
	$rsAlmacen = mysql_query($queryAlmacen);
	if (!$rsAlmacen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsAlmacen = mysql_num_rows($rsAlmacen);
	
	if ($totalRowsAlmacen > 0) {
		return $objResponse->alert("El pedido no puede ser aprobado debido a que la unidad esta registrada en un almacen de otra empresa.");
	}
	
	if (!($totalRowsUnidadFisica > 0) && !($totalRowsAlmacen > 0)) {
		mysql_query("START TRANSACTION;");
		
		$updateSQL = sprintf("UPDATE an_pedido SET
			estado_pedido = 1
		WHERE id_pedido = %s;",
			valTpDato($idPedido, "int")); // 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
	}
	
	$objResponse->loadCommands(listaPedido(
		$frmListaPedido['pageNum'],
		$frmListaPedido['campOrd'],
		$frmListaPedido['tpOrd'],
		$frmListaPedido['valBusq']));
	
	return $objResponse;
}

function buscarPedido($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		(is_array($frmBuscar['lstEstatusPedido']) ? implode(",",$frmBuscar['lstEstatusPedido']) : $frmBuscar['lstEstatusPedido']),
		(is_array($frmBuscar['lstEmpleado']) ? implode(",",$frmBuscar['lstEmpleado']) : $frmBuscar['lstEmpleado']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPedido(0, "id_pedido", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstVendedor($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$sqlBusq = "";
	if (in_array(idArrayPais,array(3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		// 1.- ASESOR VENTAS VEHICULOS
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((vw_pg_empleado.id_empleado IN (SELECT vw_pg_empleado2.id_empleado FROM vw_pg_empleados vw_pg_empleado2
																WHERE vw_pg_empleado2.clave_filtro IN (1))
			AND (SELECT COUNT(vw_pg_empleado2.id_empleado) FROM vw_pg_empleados vw_pg_empleado2
				WHERE vw_pg_empleado2.id_empleado = %s
					AND vw_pg_empleado2.clave_filtro IN (1)) = 0)
		OR (vw_pg_empleado.id_empleado = %s
			AND (SELECT COUNT(vw_pg_empleado2.id_empleado) FROM vw_pg_empleados vw_pg_empleado2
				WHERE vw_pg_empleado2.id_empleado = %s
					AND vw_pg_empleado2.clave_filtro IN (1)) > 0))",
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_empleado.id_empleado IN (SELECT an_ped_vent.asesor_ventas FROM an_pedido an_ped_vent)");
	}
	
	$query = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado %s ORDER BY vw_pg_empleado.nombre_empleado", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstEmpleado\" name=\"lstEmpleado\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= ($totalRows > 1) ? "<option value=\"-1\">[ Seleccione ]</option>" : "";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpleado","innerHTML",$html);
	
	return $objResponse;
}

function cargarPagina(){
	$objResponse = new xajaxResponse();
	
	$sqlBusq = "";
	if (in_array(idArrayPais,array(3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		// 1.- ASESOR VENTAS VEHICULOS
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((vw_pg_empleado.id_empleado IN (SELECT vw_pg_empleado2.id_empleado FROM vw_pg_empleados vw_pg_empleado2
																	WHERE vw_pg_empleado2.clave_filtro IN (1))
				AND (SELECT COUNT(vw_pg_empleado2.id_empleado) FROM vw_pg_empleados vw_pg_empleado2
					WHERE vw_pg_empleado2.id_empleado = %s
						AND vw_pg_empleado2.clave_filtro IN (1)) = 0)
			OR (vw_pg_empleado.id_empleado = %s
				AND (SELECT COUNT(vw_pg_empleado2.id_empleado) FROM vw_pg_empleados vw_pg_empleado2
					WHERE vw_pg_empleado2.id_empleado = %s
						AND vw_pg_empleado2.clave_filtro IN (1)) > 0))",
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_empleado.id_empleado IN (SELECT an_ped_vent.asesor_ventas FROM an_pedido an_ped_vent)");
	}
	
	$query = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado %s ORDER BY vw_pg_empleado.nombre_empleado", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->loadCommands(cargaLstEmpresaFinal($_SESSION['idEmpresaUsuarioSysGts']));
	$objResponse->loadCommands(cargaLstVendedor((($totalRows == 1) ? $_SESSION['idEmpleadoSysGts'] : "-1")));
	
	$objResponse->script("xajax_buscarPedido(xajax.getFormValues('frmBuscar'));");
	
	return $objResponse;
}

function desautorizarPedido($idPedido, $frmListaPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","desautorizar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE an_pedido SET
		estado_pedido = 3
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int")); // 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPedido(
		$frmListaPedido['pageNum'],
		$frmListaPedido['campOrd'],
		$frmListaPedido['tpOrd'],
		$frmListaPedido['valBusq']));
	
	return $objResponse;
}

function exportarPedidoVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstatusPedido'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/an_pedido_venta_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaPedido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	global $spanInicial;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((an_ped_vent.estado_pedido IN (1,2,3,4)
		AND ((SELECT COUNT(acc_ped.id_pedido) FROM an_accesorio_pedido acc_ped
				WHERE acc_ped.id_pedido = an_ped_vent.id_pedido
					AND acc_ped.estatus_accesorio_pedido = 0) > 0
			OR (SELECT COUNT(paq_ped.id_pedido) FROM an_paquete_pedido paq_ped
				WHERE paq_ped.id_pedido = an_ped_vent.id_pedido
					AND paq_ped.estatus_paquete_pedido = 0) > 0
			OR ((SELECT COUNT(uni_fis.id_unidad_fisica) FROM an_unidad_fisica uni_fis
				WHERE uni_fis.id_unidad_fisica = an_ped_vent.id_unidad_fisica
					AND uni_fis.estado_venta = 'RESERVADO') > 0
				AND an_ped_vent.estatus_unidad_fisica IN (0))))
	OR (an_ped_vent.estado_pedido IN (1,3)
		AND an_ped_vent.id_factura_cxc IS NOT NULL))");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(an_ped_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = an_ped_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("an_ped_vent.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if (in_array("00",explode($valCadBusq[3]))) {
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 0)");
		} else if (in_array("22",explode($valCadBusq[3]))) {
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 2)");
		} else if (in_array("33",explode($valCadBusq[3]))) {
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 3)");
		} else {
			$sqlBusq .= $cond.sprintf("(an_ped_vent.estado_pedido = %s)",
				valTpDato($valCadBusq[3], "campo"));
		}
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("an_ped_vent.asesor_ventas IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(an_ped_vent.id_pedido LIKE %s
		OR an_ped_vent.id_presupuesto LIKE %s
		OR an_ped_vent.id_cliente LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT an_ped_vent.*,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		cxc_fact.idFactura AS id_factura_reemplazo,
		cxc_fact.numeroFactura AS numero_factura_reemplazo,
		pres_vent_acc.id_presupuesto_accesorio,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
		CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_contacto,
		cliente.tipo,
		cliente.ciudad,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.tipo_cuenta_cliente,
		clave_mov.id_clave_movimiento,
		clave_mov.clave,
		clave_mov.descripcion AS descripcion_clave_movimiento,
		IFNULL(uni_fis.id_uni_bas, pres_vent.id_uni_bas) AS id_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		uni_fis.estado_venta,
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo,
		vw_pg_empleado.telefono,
		vw_pg_empleado.celular,
		vw_pg_empleado.email,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.idmoneda, moneda_local.idmoneda) AS id_moneda,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.descripcion, moneda_local.descripcion) AS descripcion_moneda,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
		pres_vent.id_banco_financiar,
		banco.nombreBanco,
		pres_vent.estado AS estado_presupuesto,
		an_ped_vent.estado_pedido,
		ped_financ.id_pedido_financiamiento,
		ped_financ.numeracion_pedido AS numeracion_pedido_financiamiento,
		ped_financ.estatus_pedido AS estatus_pedido_financiamiento,
		adicional_contrato.nombre_agencia_seguro,
		adicional_contrato.direccion_agencia_seguro,
		adicional_contrato.ciudad_agencia_seguro,
		adicional_contrato.pais_agencia_seguro,
		adicional_contrato.telefono_agencia_seguro,
		
		IFNULL(an_ped_vent.precio_venta * (an_ped_vent.porcentaje_iva + an_ped_vent.porcentaje_impuesto_lujo) / 100, 0) AS monto_impuesto,	
		(IFNULL(an_ped_vent.precio_venta, 0)
			+ IFNULL(an_ped_vent.precio_venta * (an_ped_vent.porcentaje_iva + an_ped_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta_impuesto,
		
		(SELECT an_cxc_fact.tipo_factura FROM an_factura_venta an_cxc_fact
		WHERE an_cxc_fact.numeroPedido = an_ped_vent.id_pedido
			AND (SELECT COUNT(an_cxc_fact2.numeroPedido) FROM an_factura_venta an_cxc_fact2
				WHERE an_cxc_fact.numeroPedido = an_ped_vent.id_pedido
					AND an_cxc_fact.tipo_factura IN (1,2)) = 1) AS tipo_factura,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_pedido an_ped_vent
		INNER JOIN cj_cc_cliente cliente ON (an_ped_vent.id_cliente = cliente.id)
		INNER JOIN pg_monedas moneda_local ON (an_ped_vent.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (an_ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		INNER JOIN pg_clave_movimiento clave_mov ON (an_ped_vent.id_clave_movimiento = clave_mov.id_clave_movimiento)
		LEFT JOIN an_unidad_fisica uni_fis ON (an_ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
			LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
		LEFT JOIN an_adicionales_contrato adicional_contrato ON (an_ped_vent.id_pedido = adicional_contrato.id_pedido)
		LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
			LEFT JOIN fi_documento ped_financ_det ON (pres_vent.id_presupuesto = ped_financ_det.id_presupuesto)
				LEFT JOIN fi_pedido ped_financ ON (ped_financ_det.id_pedido_financiamiento = ped_financ.id_pedido_financiamiento)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (an_ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (an_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (an_ped_vent.id_factura_cxc = cxc_fact.idFactura) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaPedido", "", $pageNum, "estado_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "LPAD(CONVERT(numeracion_pedido, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "LPAD(CONVERT(numeracion_presupuesto, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "id_presupuesto_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto Accesorios");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "18%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo / ".$spanSerialCarroceria." / ".$spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaPedido", "8%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Empleado Creador");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "8%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Venta / ".$spanInicial);
		$htmlTh .= ordenarCampo("xajax_listaPedido", "8%", $pageNum, "total_inicial_gastos", $campOrd, $tpOrd, $valBusq, $maxRows, "Total General");
		$htmlTh .= "<td colspan=\"5\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusPedido = "";
		if ($row['estado_presupuesto'] == 0 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Presupuesto Autorizado\"/>";
		} else if ($row['estado_presupuesto'] == 1 || $row['estado_presupuesto'] == "") {
			switch ($row['estado_pedido']) {
				case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Autorizado\"/>"; break;
				case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			}
		} else if ($row['estado_presupuesto'] == 2 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Presupuesto Anulado\"/>";
		} else if ($row['estado_presupuesto'] == 3 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Presupuesto Desautorizado\"/>";
		}
		
		$rowspan = (strlen($row['observaciones']) > 0) ? "rowspan=\"2\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td ".$rowspan.">".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "PD";
				$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = 2;
				$objDcto->idDocumento = $row['id_pedido'];
				$objDcto->mostrarDocumento = "verPDF";
				$aVerDcto = $objDcto->verPedido();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeracion_pedido'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">";
				$htmlTb .= "<div>".$row['numeracion_presupuesto']."</div>";
				$htmlTb .= ($row['id_pedido_financiamiento'] > 0) ? "<div><span class=\"textoNegrita_10px\">Nro. Fmto.: </span>".$row['numeracion_pedido_financiamiento']."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_presupuesto_accesorio']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['vehiculo'])."</div>";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "FA";
				$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = 2;
				$objDcto->idDocumento = $row['id_factura_reemplazo'];
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr class=\"textoNegrita_10px\">";
					$htmlTb .= "<td width=\"50%\">".utf8_encode($row['serial_carroceria'])."</td>";
					$htmlTb .= "<td width=\"50%\">".utf8_encode($row['placa'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
				$htmlTb .= ($row['numero_factura_reemplazo'] > 0) ? 
					"<div>".
						"<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo4\" width=\"100%\">".
						"<tr align=\"center\">".
							"<td height=\"25\" width=\"25\"><img src=\"../img/iconos/exclamation.png\"/></td>".
							"<td>".
								"<table>".
								"<tr align=\"right\">".
									"<td nowrap=\"nowrap\">"."Edición de la Factura Nro. "."</td>".
									"<td nowrap=\"nowrap\">".$aVerDcto."</td>".
									"<td>".$row['numero_factura_reemplazo']."</td>".
								"</tr>".
								"</table>".
							"</td>".
						"</tr>".
						"</table>".
					"</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['id_empleado'].".- ".$row['nombre_empleado'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['porcentaje_inicial'] == 100) ? "divMsjInfo" : "divMsjAlerta")."\" ".$rowspan.">";
				$htmlTb .= ($row['porcentaje_inicial'] == 100) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">";
				$htmlTb .= "<div>".$row['abreviacion_moneda'].number_format($row['precio_venta_impuesto'], 2, ".", ",")."</div>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td class=\"textoNegrita_10px\" width=\"50%\">"."(".number_format($row['porcentaje_inicial'], 2, ".", ",")."%)"."</td>";
					$htmlTb .= "<td width=\"50%\">".$row['abreviacion_moneda'].number_format($row['inicial'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".$row['abreviacion_moneda'].number_format($row['total_pedido'], 2, ".", ",")."</td>";
			$htmlTb .= "<td ".$rowspan.">";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('an_pedido_venta_form.php?id=%s','_self');\" src=\"../img/iconos/pencil.png\" title=\"Editar Pedido\"/>",
					$row['id_pedido']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if (!($row['id_pedido_financiamiento'] > 0) && $row['estado_pedido'] == 1) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarDesautorizar('%s');\" src=\"../img/iconos/cancel.png\" title=\"Desautorizar Pedido\"/>",
					$row['id_pedido']);
			} else if ($row['estado_pedido'] == 3) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarAutorizar('%s');\" src=\"../img/iconos/accept.png\" title=\"Autorizar Pedido\"/>",
					$row['id_pedido']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if (!($row['id_pedido_financiamiento'] > 0) && $row['estado_pedido'] == 3) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarAnular('%s');\" src=\"../img/iconos/ico_delete.png\" title=\"Anular Pedido\"/>",
					$row['id_pedido']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('an_ventas_pedido_editar.php?view=print&id=%s', 960, 550);\" src=\"../img/iconos/ico_print.png\" title=\"Imprimir Pedido\"/>",
					$row['id_pedido']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if ($row['id_presupuesto_accesorio'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_combo_presupuesto_pdf.php?idPresupuesto=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Presupuesto Accesorio PDF\"/>",
					$row['id_presupuesto_accesorio']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if (strlen($rowspan) > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td colspan=\"8\">";
					$htmlTb .= ((strlen($row['observaciones']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observaciones'])."</div>" : "");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedido(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularPedido");
$xajax->register(XAJAX_FUNCTION,"autorizarPedido");
$xajax->register(XAJAX_FUNCTION,"buscarPedido");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"desautorizarPedido");
$xajax->register(XAJAX_FUNCTION,"exportarPedidoVenta");
$xajax->register(XAJAX_FUNCTION,"listaPedido");
?>