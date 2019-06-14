<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function buscarComision($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha'],
		$frmBuscar['lstCargo'],
		$frmBuscar['lstEmpleado'],
		$frmBuscar['lstModulo']);
	
	$objResponse->loadCommands(listaComision(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarCuenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstBanco'],
		$frmBuscar['lstTipoCuenta'],
		$frmBuscar['lstMoneda'],
		$frmBuscar['textCriterio']);
	
	$objResponse->loadCommands(listaCuenta(0, "", "", $valBusq));
	
	return $objResponse;
}

function cargaLstCargo($idEmpresa = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
		
	$query = sprintf("SELECT DISTINCT
		id_cargo,
		nombre_cargo
	FROM pg_comision comision
		INNER JOIN vw_pg_cargos ON (comision.id_cargo_departamento = vw_pg_cargos.id_cargo_departamento) %s
	ORDER BY nombre_cargo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstCargo\" name=\"lstCargo\" onchange=\"xajax_cargaLstVendedor(this.value)\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_cargo']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_cargo']."\">".utf8_encode($row['nombre_cargo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCargo","innerHTML",$html);
	
	return $objResponse;
}

function cargarLstBanco(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT idBanco,nombreBanco FROM sipre_automotriz.bancos ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstBanco\" name=\"lstBanco\" onchange=\"xajax_buscarCuenta(xajax.getFormValues('frmBuscarCuenta'))\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
							
			$html .= "<option value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
		}
	$html .= "</select>";
	$objResponse->assign("tdLstBanco","innerHTML",$html);
	
	return $objResponse;	
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM pg_modulos ORDER BY descripcionModulo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" onchange=\"selectedOption(this.id,'".$selId."');\">";
		$html .="<option value=\"-1\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo']) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstModulo","innerHTML",$html);

	return $objResponse;
}
 
function cargarLstMoneda(){ 
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT idmoneda,descripcion,abreviacion FROM sipre_automotriz.pg_monedas");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" onchange=\"xajax_buscarCuenta(xajax.getFormValues('frmBuscarCuenta'))\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$html .= "<option value=\"".$row['idmoneda']."\">".utf8_encode($row['abreviacion'])." - ".utf8_encode($row['descripcion'])."</option>";
		}
	$html .= "</select>";
	$objResponse->assign("tdLstMoneda","innerHTML",$html);
	
	return $objResponse;	
}

function cargarLstTipoCuenta(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT DISTINCT tipo_cuenta FROM cuentas ORDER BY tipo_cuenta ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstTipoCuenta\" name=\"lstTipoCuenta\" onchange=\"xajax_buscarCuenta(xajax.getFormValues('frmBuscarCuenta'))\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$html .= "<option value=\"".$row['tipo_cuenta']."\">".utf8_encode($row['tipo_cuenta'])."</option>";
		}
	$html .= "</select>";
	$objResponse->assign("tdLstTipoCuenta","innerHTML",$html);
	
	return $objResponse;	
}

function cargaLstVendedor($idCargo = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado NOT IN (1)");
	
	if ($idCargo != "-1" && $idCargo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_cargo = %s",
			valTpDato($idCargo, "int"));
	}
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM vw_pg_empleados %s
	ORDER BY nombre_empleado", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstEmpleado\" name=\"lstEmpleado\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpleado","innerHTML",$html);
	
	return $objResponse;
}

function eliminarComision($idComisionEmpleado, $frmListaComisiones) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"if_comisiones_list","eliminar")) { return $objResponse; }
	
	if (isset($idComisionEmpleado)) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM pg_comision_empleado WHERE id_comision_empleado = %s;",
			valTpDato($idComisionEmpleado, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Eliminacion realizada con éxito");
		
		$objResponse->loadCommands(listaComision(
			$frmListaComisiones['pageNum'],
			$frmListaComisiones['campOrd'],
			$frmListaComisiones['tpOrd'],
			$frmListaComisiones['valBusq']));
	}
		
	return $objResponse;
}

function exportarComisiones($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha'],
		$frmBuscar['lstCargo'],
		$frmBuscar['lstEmpleado'],
		$frmBuscar['lstModulo']);
	
	$objResponse->script("window.open('reportes/if_comisiones_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formComisionDetalle($idComision) {
	$objResponse = new xajaxResponse();
	
	$queryDetalle = sprintf("SELECT
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT cxc_fact.idDepartamentoOrigenFactura
				FROM cj_cc_encabezadofactura cxc_fact
				WHERE cxc_fact.idFactura = comision_emp.id_factura)
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT cxc_nc.idDepartamentoNotaCredito
				FROM cj_cc_notacredito cxc_nc
				WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
		END) AS id_modulo,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				'FA'
			WHEN (id_nota_credito IS NOT NULL) THEN
				'NC'
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				'VS'
			WHEN (id_vale_entrada IS NOT NULL) THEN
				'VE'
		END) AS tipo_documento,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact
				WHERE cxc_fact.idFactura = comision_emp.id_factura)
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc
				WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
		END) AS numero_documento,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT cxc_fact.condicionDePago FROM cj_cc_encabezadofactura cxc_fact
				WHERE cxc_fact.idFactura = comision_emp.id_factura)
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT cxc_facta.condicionDePago AS condicionDePago
				FROM cj_cc_encabezadofactura cxc_facta
					JOIN cj_cc_notacredito cxc_nc on (cxc_facta.idFactura = cxc_nc.idDocumento)
				WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
		END) AS tipo_pago
	FROM pg_comision_empleado comision_emp
	WHERE id_comision_empleado = %s;",
		valTpDato($idComision, "int"));
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDetalle = mysql_fetch_assoc($rsDetalle);
	
	$objResponse->loadCommands(listaComisionDetalle(0,"","",$idComision));
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Comisiónes del Dcto. Nro. ".$rowDetalle['numero_documento']);
	
	return $objResponse;
}

function formComisionProduccion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"if_comisiones_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarComisionProduccion').click();"); return $objResponse; }
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	$valFecha[0] = date("m", strtotime("01-".$frmBuscar['txtFecha']));
	$valFecha[1] = date("Y", strtotime("01-".$frmBuscar['txtFecha']));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("cierre_mensual.mes = %s
	AND cierre_mensual.ano = %s
	AND cierre_mensual_fact.id_modulo = 1
	AND cierre_mensual_fact.id_tipo_orden IS NULL",
		valTpDato($valFecha[0], "int"),
		valTpDato($valFecha[1], "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cierre_mensual.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cierre_mensual.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	$queryDetalle = sprintf("SELECT *
	FROM iv_cierre_mensual cierre_mensual
		INNER JOIN iv_cierre_mensual_facturacion cierre_mensual_fact ON (cierre_mensual.id_cierre_mensual = cierre_mensual_fact.id_cierre_mensual)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cierre_mensual_fact.id_empleado = vw_pg_empleado.id_empleado) %s", $sqlBusq);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	$htmlTblIni = "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\">Empleado</td>";
		$htmlTh .= "<td width=\"22%\">Cargo</td>";
		$htmlTh .= "<td width=\"12%\">% Productividad</td>";
		$htmlTh .= "<td width=\"12%\">UT'S</td>";
		$htmlTh .= "<td width=\"12%\">UT'S Físicas</td>";
		$htmlTh .= "<td width=\"12%\">".cAbrevMoneda."</td>";
	$htmlTh .= "</tr>";
	while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($rowDetalle['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($rowDetalle['nombre_cargo'])."</td>";
			$htmlTb .= "<td align=\"right\">";
			if ($rowDetalle['total_ut_fisica'] > 0) {
				$htmlTb .= number_format((($rowDetalle['total_ut'] / $rowDetalle['total_ut_fisica']) * 100), 2, ".", ",");
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowDetalle['total_ut'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">";
			if ($rowDetalle['total_ut_fisica'] > 0) {
				$htmlTb .= number_format($rowDetalle['total_ut_fisica'], 2, ".", ",");
			} else {
				$htmlTb .= sprintf("<input type=\"text\" id=\"txtUtsFisica%s\" name=\"txtUtsFisica%s\" class=\"inputCompletoHabilitado\" onblur=\"setFormatoRafk(this,2);\" onkeypress=\"return validarSoloNumerosReales(event)\" style=\"text-align:right\"/>".
					"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
					"<input type=\"hidden\" id=\"hddIdCierreMensualFacturacion%s\" name=\"hddIdCierreMensualFacturacion%s\" readonly=\"readonly\" value=\"%s\"/>",
						$contFila, $contFila,
						$contFila,
						$contFila, $contFila, $rowDetalle['id_cierre_mensual_facturacion']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowDetalle['total_mano_obra'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaComisionProduccion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function guardarComisionProduccion($frmComisionProduccion, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"if_comisiones_list","insertar")) { return $objResponse; }
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmComisionProduccion['cbx'];
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	$valFecha[0] = date("m", strtotime("01-".$frmBuscar['txtFecha']));
	$valFecha[1] = date("Y", strtotime("01-".$frmBuscar['txtFecha']));
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$objResponse->script("byId('txtUtsFisica".$valor."').className = 'inputCompletoHabilitado'");
			
			if (!(doubleval(str_replace(",", "", $frmComisionProduccion['txtUtsFisica'.$valor])) > 0)) {
				$arrayCantidadInvalida[] = "txtUtsFisica".$valor;
			}
		}
	}
	
	if (count($arrayCantidadInvalida) > 0) {
		if (count($arrayCantidadInvalida) > 0) {
			foreach ($arrayCantidadInvalida as $indice => $valor) {
				$objResponse->script("byId('".$valor."').className = 'inputErrado';");
			}
		}
		
		errorGuardarComisionProduccion($objResponse);
		return $objResponse->alert("Los campos señalados en rojo son invalidos");
	}
	
	mysql_query("START TRANSACTION;");
	
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if (isset($frmComisionProduccion['txtUtsFisica'.$valor])) {
				$updateSQL = sprintf("UPDATE iv_cierre_mensual_facturacion SET
					total_ut_fisica = %s
				WHERE id_cierre_mensual_facturacion = %s;",
					valTpDato($frmComisionProduccion['txtUtsFisica'.$valor], "real_inglesa"),
					valTpDato($frmComisionProduccion['hddIdCierreMensualFacturacion'.$valor], "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorGuardarComisionProduccion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	# ELIMINA LAS COMISIONES POR PRODUCTIVIDAD PARA EVITAR DUPLICIDAD
	$deleteSQL = sprintf("DELETE FROM pg_comision_empleado_detalle
	WHERE id_comision_empleado IN (SELECT id_comision_empleado FROM pg_comision_empleado
									WHERE id_factura IN (SELECT cxc_fact.idFactura FROM cj_cc_encabezadofactura cxc_fact
														WHERE MONTH(cxc_fact.fechaRegistroFactura) = %s
															AND YEAR(cxc_fact.fechaRegistroFactura) = %s
															AND (cxc_fact.id_empresa = %s
																OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
																		WHERE suc.id_empresa = cxc_fact.id_empresa)))
										AND id_nota_credito IS NULL)
		AND id_tipo_porcentaje IN (2);",
		valTpDato($valFecha[0], "int"),
		valTpDato($valFecha[1], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { errorGuardarComisionProduccion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	# ELIMINA LAS COMISIONES POR PRODUCTIVIDAD PARA EVITAR DUPLICIDAD
	$deleteSQL = sprintf("DELETE FROM pg_comision_empleado_detalle
	WHERE id_comision_empleado IN (SELECT id_comision_empleado FROM pg_comision_empleado
									WHERE id_nota_credito IN (SELECT cxc_nc.idNotaCredito FROM cj_cc_notacredito cxc_nc
															WHERE MONTH(cxc_nc.fechaNotaCredito) = %s
																AND YEAR(cxc_nc.fechaNotaCredito) = %s
																AND (cxc_nc.id_empresa = %s
																	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
																			WHERE suc.id_empresa = cxc_nc.id_empresa))))
		AND id_tipo_porcentaje IN (2);",
		valTpDato($valFecha[0], "int"),
		valTpDato($valFecha[1], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { errorGuardarComisionProduccion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	# ELIMINA LAS COMISIONES POR PRODUCTIVIDAD PARA EVITAR DUPLICIDAD
	$deleteSQL = sprintf("DELETE FROM pg_comision_empleado_detalle
	WHERE id_comision_empleado IN (SELECT id_comision_empleado FROM pg_comision_empleado
									WHERE id_vale_salida IN (SELECT sa_vs.id_vale_salida FROM sa_vale_salida sa_vs
															WHERE MONTH(sa_vs.fecha_vale) = %s
																AND YEAR(sa_vs.fecha_vale) = %s
																AND (sa_vs.id_empresa = %s
																	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
																			WHERE suc.id_empresa = sa_vs.id_empresa))))
		AND id_tipo_porcentaje IN (2);",
		valTpDato($valFecha[0], "int"),
		valTpDato($valFecha[1], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { errorGuardarComisionProduccion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	
	# GENERA COMISIONES FACTURAS
	$query = sprintf("SELECT * FROM cj_cc_encabezadofactura cxc_fact
	WHERE MONTH(cxc_fact.fechaRegistroFactura) = %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s
		AND (cxc_fact.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_fact.id_empresa));",
		valTpDato($valFecha[0], "int"),
		valTpDato($valFecha[1], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) { errorGuardarComisionProduccion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($row = mysql_fetch_assoc($rs)) {
		switch ($row['idDepartamentoOrigenFactura']) {
			case 0 : $Result1 = generarComision($row['idFactura'], false, $valFecha[0], $valFecha[1]); $arrayFactura[] = $row['numeroFactura']; break;
			case 1 : $Result1 = calcular_comision_factura($row['idFactura'], false, $valFecha[0], $valFecha[1]); $arrayFactura[] = $row['numeroFactura']; break;
		}
		if ($Result1[0] != true) { errorGuardarComisionProduccion($objResponse); return $objResponse->alert($Result1[1]); }
	}
	
	# GENERA COMISIONES VALE SALIDA
	$query = sprintf("SELECT * FROM sa_vale_salida sa_vs
	WHERE MONTH(sa_vs.fecha_vale) = %s
		AND YEAR(sa_vs.fecha_vale) = %s
		AND (sa_vs.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = sa_vs.id_empresa));",
		valTpDato($valFecha[0], "int"),
		valTpDato($valFecha[1], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) { errorGuardarComisionProduccion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($row = mysql_fetch_assoc($rs)) {
		$Result1 = calcular_comision_vale_salida($row['id_vale_salida'], false, $valFecha[0], $valFecha[1]); $arrayValeSalida[] = $row['numero_vale'];
		if ($Result1[0] != true) { errorGuardarComisionProduccion($objResponse); return $objResponse->alert($Result1[1]); }
	}
	
	# GENERA COMISIONES NOTAS DE CREDITO
	$query = sprintf("SELECT * FROM cj_cc_notacredito cxc_nc
	WHERE MONTH(cxc_nc.fechaNotaCredito) = %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s
		AND (cxc_nc.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = cxc_nc.id_empresa));",
		valTpDato($valFecha[0], "int"),
		valTpDato($valFecha[1], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) { errorGuardarComisionProduccion($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($row = mysql_fetch_assoc($rs)) {
		switch ($row['idDepartamentoOrigenFactura']) {
			case 0 : $Result1 = devolverComision($row['idNotaCredito'], false, $valFecha[0], $valFecha[1]); $arrayNotaCredito[] = $row['numeracion_nota_credito']; break;
			case 1 : $Result1 = devolverComisionNC($row['idNotaCredito'], $row['idDocumento'], false, $valFecha[0], $valFecha[1]); $arrayNotaCredito[] = $row['numeracion_nota_credito']; break;
		}
		if ($Result1[0] != true) { errorGuardarComisionProduccion($objResponse); return $objResponse->alert($Result1[1]); }
	}
	
	$objResponse->alert("Facturas: ".count($arrayFactura)." => ".implode(", ",$arrayFactura)."\n\nVales de Salida: ".count($arrayValeSalida)." => ".implode(", ",$arrayValeSalida)."\n\nNotas de Crédito: ".count($arrayNotaCredito)." => ".implode(", ",$arrayNotaCredito));
	
	mysql_query("COMMIT;");
	
	errorGuardarComisionProduccion($objResponse);
	$objResponse->alert("Registro(s) Guardado(s) con Éxito");
	
	$objResponse->script("
	byId('btnCancelarComisionProduccion').click();");
	
	return $objResponse;
}

function imprimirComisiones($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha'],
		$frmBuscar['lstCargo'],
		$frmBuscar['lstEmpleado'],
		$frmBuscar['lstModulo']);
	
	$objResponse->script(sprintf("verVentana('reportes/if_comisiones_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function imprimirComisionesResumen($frmBuscar,$idCuentas) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha'],
		$frmBuscar['lstCargo'],
		$frmBuscar['lstEmpleado'],
		$frmBuscar['lstModulo']);
	$objResponse->script("byId('btnCancelarListNumCuenta').click();");
	$objResponse->script(sprintf("verVentana('reportes/if_comisiones_resumen_pdf.php?valBusq=%s',890,550)", $valBusq));
	$objResponse->script(sprintf("verVentana('reportes/if_comisiones_carta_pdf.php?valBusq=%s&Idcuenta=%s',890,550)", $valBusq,$idCuentas));
	
	return $objResponse;
}

function nombreAccesorio($id_cargo_departamento, $id_factura, $id_empleado){
	$queryArt = sprintf("SELECT DISTINCT comi.id_comision, acc.id_accesorio, com_art.monto, acc.nom_accesorio
							FROM pg_comision comi 
							LEFT JOIN pg_comision_articulo com_art ON com_art.id_comision = comi.id_comision
							LEFT JOIN an_accesorio acc ON acc.id_accesorio = com_art.id_articulo
							LEFT JOIN cj_cc_factura_detalle_accesorios facc ON facc.id_accesorio = com_art.id_articulo
							WHERE comi.tipo_importe = 3 AND comi.tipo_comision = 6 AND comi.tipo_porcentaje = 4 AND facc.id_factura = %s",
			valTpDato($id_factura, "int"));
	$rsArt = mysql_query($queryArt);
	return $rsArt;
}

function listaComision($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 1, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$valFecha[0] = date("m", strtotime("01-".$valCadBusq[1]));
	$valFecha[1] = date("Y", strtotime("01-".$valCadBusq[1]));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((CASE
										WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
											(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact
											WHERE cxc_fact.idFactura = comision_emp.id_factura)
										WHEN (id_nota_credito IS NOT NULL) THEN
											(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc
											WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
										WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
											(SELECT sa_vs.id_empresa FROM sa_vale_salida sa_vs
											WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
										WHEN (id_vale_entrada IS NOT NULL) THEN
											(SELECT sa_ve.id_empresa FROM sa_vale_entrada sa_ve
											WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
									END) = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = (CASE
											WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
												(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact
												WHERE cxc_fact.idFactura = comision_emp.id_factura)
											WHEN (id_nota_credito IS NOT NULL) THEN
												(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc
												WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
											WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
												(SELECT sa_vs.id_empresa FROM sa_vale_salida sa_vs
												WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
											WHEN (id_vale_entrada IS NOT NULL) THEN
												(SELECT sa_ve.id_empresa FROM sa_vale_entrada sa_ve
												WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
										END)))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "" && $valCadBusq[4] != 2) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				MONTH((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
						WHERE cxc_fact.idFactura = comision_emp.id_factura AND 1 = 1))
			WHEN (id_nota_credito IS NOT NULL) THEN
				MONTH((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
						WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				MONTH((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
						WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
			WHEN (id_vale_entrada IS NOT NULL) THEN
				MONTH((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
						WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
		END) = %s",
			valTpDato($valFecha[0], "text"));
	} elseif($valCadBusq[4] == 2){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(comision_emp.fecha_comision) = %s AND YEAR(comision_emp.fecha_comision) = %s",
				valTpDato($valFecha[0], "int"),
				valTpDato($valFecha[1], "int"));
	}

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				YEAR((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
						WHERE cxc_fact.idFactura = comision_emp.id_factura AND 2 = 2))
			WHEN (id_nota_credito IS NOT NULL) THEN
				YEAR((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
						WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				YEAR((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
						WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
			WHEN (id_vale_entrada IS NOT NULL) THEN
				YEAR((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
						WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
		END) = %s",
			valTpDato($valFecha[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT cargo_dep.id_cargo
		FROM pg_cargo_departamento cargo_dep
			INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento)
			INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
		WHERE cargo_dep.id_cargo_departamento = empleado.id_cargo_departamento) = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision_emp.id_empleado = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact
				WHERE cxc_fact.idFactura = comision_emp.id_factura)
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc
				WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT 1 FROM sa_vale_salida sa_vs
				WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
			WHEN (id_vale_entrada IS NOT NULL) THEN
				(SELECT 1 FROM sa_vale_entrada sa_ve
				WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
		END) = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	$queryComision = sprintf("SELECT
		empleado.id_empleado,
		empleado.cedula,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact
				WHERE cxc_fact.idFactura = comision_emp.id_factura)
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc
				WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT 1 FROM sa_vale_salida sa_vs
				WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
			WHEN (id_vale_entrada IS NOT NULL) THEN
				(SELECT 1 FROM sa_vale_entrada sa_ve
				WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
		END) AS id_modulo,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				MONTH((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
						WHERE cxc_fact.idFactura = comision_emp.id_factura AND 3 = 3))
			WHEN (id_nota_credito IS NOT NULL) THEN
				MONTH((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
						WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				MONTH((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
						WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
			WHEN (id_vale_entrada IS NOT NULL) THEN
				MONTH((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
						WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
		END) AS mes_documento,
		
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				YEAR((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
						WHERE cxc_fact.idFactura = comision_emp.id_factura AND 4 = 4))
			WHEN (id_nota_credito IS NOT NULL) THEN
				YEAR((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
						WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				YEAR((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
						WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
			WHEN (id_vale_entrada IS NOT NULL) THEN
				YEAR((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
						WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
		END) AS ano_documento,
		
		(SELECT GROUP_CONCAT(vw_pg_cargos.nombre_cargo SEPARATOR ', ') FROM vw_pg_cargos
		WHERE vw_pg_cargos.id_cargo_departamento = comision_emp.id_cargo_departamento) AS nombre_cargo,
		
		comision_emp.id_cargo_departamento,
			
		(SELECT (SUM(total_ut / total_ut_fisica) * 100) AS porcentaje_productividad
		FROM iv_cierre_mensual cierre_mensual
			INNER JOIN iv_cierre_mensual_facturacion cierre_mensual_fact ON (cierre_mensual.id_cierre_mensual = cierre_mensual_fact.id_cierre_mensual)
		WHERE cierre_mensual_fact.id_empleado = comision_emp.id_empleado
			AND cierre_mensual.id_empresa = (CASE
												WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
													(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact
													WHERE cxc_fact.idFactura = comision_emp.id_factura)
												WHEN (id_nota_credito IS NOT NULL) THEN
													(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc
													WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
												WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
													(SELECT sa_vs.id_empresa FROM sa_vale_salida sa_vs
													WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
												WHEN (id_vale_entrada IS NOT NULL) THEN
													(SELECT sa_ve.id_empresa FROM sa_vale_entrada sa_ve
													WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
											END)
			AND cierre_mensual.mes = (CASE
											WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
												MONTH((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
														WHERE cxc_fact.idFactura = comision_emp.id_factura AND 5 = 5))
											WHEN (id_nota_credito IS NOT NULL) THEN
												MONTH((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
														WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
											WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
												MONTH((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
														WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
											WHEN (id_vale_entrada IS NOT NULL) THEN
												MONTH((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
														WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
										END)
			AND cierre_mensual.ano = (CASE
											WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
												YEAR((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
														WHERE cxc_fact.idFactura = comision_emp.id_factura AND 6 = 6))
											WHEN (id_nota_credito IS NOT NULL) THEN
												YEAR((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
														WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
											WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
												YEAR((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
														WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
											WHEN (id_vale_entrada IS NOT NULL) THEN
												YEAR((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
														WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
										END)) AS porcentaje_productividad,
			comision_emp.id_factura
	FROM pg_comision_empleado comision_emp
		INNER JOIN pg_empleado empleado ON (comision_emp.id_empleado = empleado.id_empleado) %s
	GROUP BY 1,2,3", $sqlBusq);
	
	$queryLimitComision = sprintf(" %s LIMIT %d OFFSET %d", $queryComision, $maxRows, $startRow);
	$rsLimitComision = mysql_query($queryLimitComision);
	if (!$rsLimitComision) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rsComision = mysql_query($queryComision);
		if (!$rsComision) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsComision);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	while ($rowComision = mysql_fetch_assoc($rsLimitComision)) {
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("id_empleado = %s",
			valTpDato($rowComision['id_empleado'], "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(comision_emp.venta_bruta > 0
		OR comision_emp.monto_comision > 0
		OR (SELECT AVG(comision_emp_det.porcentaje_comision) FROM pg_comision_empleado_detalle comision_emp_det
			WHERE comision_emp_det.id_comision_empleado = comision_emp.id_comision_empleado) > 0
		OR comision_emp.porcentaje_comision > 0)");
		
		$mesComisionFact = ($rowComision['id_modulo'] == 2) ? "MONTH((comision_emp.fecha_comision))" : "MONTH((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = comision_emp.id_factura AND 33 = 33))";
		$mesComisionCre = ($rowComision['id_modulo'] == 2) ? "MONTH((comision_emp.fecha_comision))" : "MONTH((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))";

		$anoComisionFact = ($rowComision['id_modulo'] == 2) ? "YEAR((comision_emp.fecha_comision))" : "YEAR((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = comision_emp.id_factura))";
		$anoComisionCre = ($rowComision['id_modulo'] == 2) ? "YEAR((comision_emp.fecha_comision))" : "YEAR((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))";
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				%s 
			WHEN (id_nota_credito IS NOT NULL) THEN
				%s 
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				MONTH((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
						WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
			WHEN (id_vale_entrada IS NOT NULL) THEN
				MONTH((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
						WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
		END) = %s",
			valTpDato($mesComisionFact, "string"),
			valTpDato($mesComisionCre, "string"),
			valTpDato($valFecha[0], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("
		(CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				%s
			WHEN (id_nota_credito IS NOT NULL) THEN
				%s
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				YEAR((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
						WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
			WHEN (id_vale_entrada IS NOT NULL) THEN
				YEAR((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
						WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
		END) = %s",
			valTpDato($anoComisionFact, "string"),
			valTpDato($anoComisionCre, "string"),
			valTpDato($valFecha[1], "int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("((CASE
											WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
												(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact
												WHERE cxc_fact.idFactura = comision_emp.id_factura)
											WHEN (id_nota_credito IS NOT NULL) THEN
												(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc
												WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
											WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
												(SELECT sa_vs.id_empresa FROM sa_vale_salida sa_vs
												WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
											WHEN (id_vale_entrada IS NOT NULL) THEN
												(SELECT sa_ve.id_empresa FROM sa_vale_entrada sa_ve
												WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
										END) = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = (CASE
												WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
													(SELECT cxc_fact.id_empresa FROM cj_cc_encabezadofactura cxc_fact
													WHERE cxc_fact.idFactura = comision_emp.id_factura)
												WHEN (id_nota_credito IS NOT NULL) THEN
													(SELECT cxc_nc.id_empresa FROM cj_cc_notacredito cxc_nc
													WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
												WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
													(SELECT sa_vs.id_empresa FROM sa_vale_salida sa_vs
													WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
												WHEN (id_vale_entrada IS NOT NULL) THEN
													(SELECT sa_ve.id_empresa FROM sa_vale_entrada sa_ve
													WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
											END)))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = comision_emp.id_factura)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT 1 FROM sa_vale_salida sa_vs
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT 1 FROM sa_vale_entrada sa_ve
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) = %s",
				valTpDato($valCadBusq[4], "int"));
		}
		
		$queryDetalle = sprintf("SELECT
			comision_emp.id_comision_empleado,
		
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_fact.idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = comision_emp.id_factura)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_nc.idDepartamentoNotaCredito FROM cj_cc_notacredito cxc_nc
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT 1 FROM sa_vale_salida sa_vs
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT 1 FROM sa_vale_entrada sa_ve
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS id_modulo,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					'FA'
				WHEN (id_nota_credito IS NOT NULL) THEN
					'NC'
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					'VS'
				WHEN (id_vale_entrada IS NOT NULL) THEN
					'VE'
			END) AS tipo_documento,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_fact.numeroFactura FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = comision_emp.id_factura)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_nc.numeracion_nota_credito FROM cj_cc_notacredito cxc_nc
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT sa_vs.numero_vale FROM sa_vale_salida sa_vs
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT sa_ve.numero_vale_entrada FROM sa_vale_entrada sa_ve
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS numero_documento,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_fact.condicionDePago FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = comision_emp.id_factura)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_facta.condicionDePago AS condicionDePago
					FROM cj_cc_encabezadofactura cxc_facta
						INNER JOIN cj_cc_notacredito cxc_nc on (cxc_facta.idFactura = cxc_nc.idDocumento)
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT tp_ord.nombre_tipo_orden
					FROM sa_orden ord
						INNER JOIN sa_tipo_orden tp_ord ON (ord.id_tipo_orden = tp_ord.id_tipo_orden)
						INNER JOIN sa_vale_salida sa_vs ON (ord.id_orden = sa_vs.id_orden)
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT tp_ord.nombre_tipo_orden
					FROM sa_orden ord
						INNER JOIN sa_tipo_orden tp_ord ON (ord.id_tipo_orden = tp_ord.id_tipo_orden)
						INNER JOIN sa_vale_salida sa_vs ON (ord.id_orden = sa_vs.id_orden)
						INNER JOIN sa_vale_entrada sa_ve ON (sa_vs.id_vale_salida = sa_vs.id_vale_salida)
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS tipo_pago,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT tipo_orden.nombre_tipo_orden
					FROM sa_orden orden_serv
						INNER JOIN sa_tipo_orden tipo_orden ON (orden_serv.id_tipo_orden = tipo_orden.id_tipo_orden)
						INNER JOIN cj_cc_encabezadofactura cxc_fact ON (orden_serv.id_orden = cxc_fact.numeroPedido)
					WHERE cxc_fact.idFactura = comision_emp.id_factura
						AND cxc_fact.idDepartamentoOrigenFactura = 1)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT tipo_orden.nombre_tipo_orden
					FROM sa_orden orden_serv
						INNER JOIN sa_tipo_orden tipo_orden ON (orden_serv.id_tipo_orden = tipo_orden.id_tipo_orden)
						INNER JOIN cj_cc_encabezadofactura cxc_fact ON (orden_serv.id_orden = cxc_fact.numeroPedido)
						INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_fact.idFactura = cxc_nc.idDocumento)
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito
						AND cxc_fact.idDepartamentoOrigenFactura = 1
						AND cxc_nc.idDepartamentoNotaCredito = cxc_fact.idDepartamentoOrigenFactura
						AND cxc_nc.tipoDocumento LIKE 'FA')
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT tipo_orden.nombre_tipo_orden
					FROM sa_orden orden_serv
						INNER JOIN sa_tipo_orden tipo_orden ON (orden_serv.id_tipo_orden = tipo_orden.id_tipo_orden)
						INNER JOIN sa_vale_salida sa_vs ON (orden_serv.id_orden = sa_vs.id_orden)
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT tipo_orden.nombre_tipo_orden
					FROM sa_orden orden_serv
						INNER JOIN sa_tipo_orden tipo_orden ON (orden_serv.id_tipo_orden = tipo_orden.id_tipo_orden)
						INNER JOIN sa_vale_salida sa_vs ON (orden_serv.id_orden = sa_vs.id_orden)
						INNER JOIN sa_vale_entrada sa_ve ON (sa_vs.id_vale_salida = sa_ve.id_vale_salida)
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS nombre_tipo_orden,

			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_facta.idCliente AS idCliente
					FROM cj_cc_encabezadofactura cxc_facta
					WHERE (cxc_facta.idFactura = comision_emp.id_factura))
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_facta.idCliente AS idCliente
					FROM (cj_cc_notacredito cxc_nc
						JOIN cj_cc_encabezadofactura cxc_facta on ((cxc_nc.idDocumento = cxc_facta.idFactura)))
					WHERE (cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
									FROM sa_recepcion r
									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
								WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
									FROM sa_recepcion r
									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
									INNER JOIN sa_vale_entrada sa_ve ON (sa_vs.id_vale_salida = sa_ve.id_vale_salida)
								WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS id_cliente,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
					FROM cj_cc_cliente cliente
					WHERE (cliente.id = (SELECT cxc_facta.idCliente AS idCliente FROM cj_cc_encabezadofactura cxc_facta
										WHERE (cxc_facta.idFactura = comision_emp.id_factura))))
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
					FROM cj_cc_cliente cliente
					WHERE (cliente.id = (SELECT cxc_facta.idCliente AS idCliente
										FROM (cj_cc_notacredito cxc_nc
											JOIN cj_cc_encabezadofactura cxc_facta on ((cxc_nc.idDocumento = cxc_facta.idFactura)))
										WHERE (cxc_nc.idNotaCredito = comision_emp.id_nota_credito))))
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
					FROM cj_cc_cliente cliente
					WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
								WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente
					FROM cj_cc_cliente cliente
					WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
									INNER JOIN sa_vale_entrada sa_ve ON (sa_vs.id_vale_salida = sa_ve.id_vale_salida)
								WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
			END) AS ci_cliente,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
					FROM cj_cc_cliente cliente
					WHERE (cliente.id = (SELECT cxc_facta.idCliente AS idCliente FROM cj_cc_encabezadofactura cxc_facta
										WHERE (cxc_facta.idFactura = comision_emp.id_factura))))
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
					FROM cj_cc_cliente cliente
					WHERE (cliente.id = (SELECT cxc_facta.idCliente AS idCliente FROM (cj_cc_notacredito cxc_nc
											JOIN cj_cc_encabezadofactura cxc_facta on ((cxc_nc.idDocumento = cxc_facta.idFactura)))
										WHERE (cxc_nc.idNotaCredito = comision_emp.id_nota_credito))))
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
					FROM cj_cc_cliente cliente
					WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
								WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
					FROM cj_cc_cliente cliente
					WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
														FROM sa_orden o
														WHERE o.id_orden = sa_vs.id_orden)), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																					FROM sa_cita c
																					WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																									FROM sa_recepcion r
																									WHERE r.id_recepcion = (SELECT o.id_recepcion AS id_recepcion
																												FROM sa_orden o
																												WHERE o.id_orden = sa_vs.id_orden)))) AS id_cliente
								FROM sa_vale_salida sa_vs
									INNER JOIN sa_vale_entrada sa_ve ON (sa_vs.id_vale_salida = sa_ve.id_vale_salida)
								WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
			END) AS nombre_cliente,
			
			comision_emp.venta_bruta,
			comision_emp.monto_descuento,
			(comision_emp.venta_bruta - comision_emp.monto_descuento) AS venta_neta,
			comision_emp.costo_compra,
			
			(comision_emp.venta_bruta
				- comision_emp.monto_descuento
				- comision_emp.costo_compra) AS utilidad_bruta,
			
			(((comision_emp.venta_bruta
				- comision_emp.monto_descuento
				- comision_emp.costo_compra) * 100) / (comision_emp.venta_bruta - comision_emp.monto_descuento)) AS porcentaje_utilidad_venta,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
					WHERE cxc_fact.idFactura = comision_emp.id_factura)
				WHEN (id_nota_credito IS NOT NULL) THEN
					(SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
					WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					(SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
					WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida)
				WHEN (id_vale_entrada IS NOT NULL) THEN
					(SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
					WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada)
			END) AS fecha_documento,
			
			comision_emp.monto_comision,
			
			(SELECT AVG(comision_emp_det.porcentaje_comision) FROM pg_comision_empleado_detalle comision_emp_det
			WHERE comision_emp_det.id_comision_empleado = comision_emp.id_comision_empleado) AS promedio_porcentaje_comision,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					MONTH((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
							WHERE cxc_fact.idFactura = comision_emp.id_factura AND 8 = 8))
				WHEN (id_nota_credito IS NOT NULL) THEN
					MONTH((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					MONTH((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
							WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
				WHEN (id_vale_entrada IS NOT NULL) THEN
					MONTH((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
							WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
			END) AS mes_documento,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					YEAR((SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
							WHERE cxc_fact.idFactura = comision_emp.id_factura AND 7 = 7))
				WHEN (id_nota_credito IS NOT NULL) THEN
					YEAR((SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito))
				WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
					YEAR((SELECT sa_vs.fecha_vale FROM sa_vale_salida sa_vs
							WHERE sa_vs.id_vale_salida = comision_emp.id_vale_salida))
				WHEN (id_vale_entrada IS NOT NULL) THEN
					YEAR((SELECT sa_ve.fecha_creada FROM sa_vale_entrada sa_ve
							WHERE sa_ve.id_vale_entrada = comision_emp.id_vale_entrada))
			END) AS ano_documento,
			id_factura,
			
			(CASE
				WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
					(to_days((SELECT cxc_fact.fechaRegistroFactura
							FROM cj_cc_encabezadofactura cxc_fact
							WHERE cxc_fact.idFactura = comision_emp.id_factura)) - to_days((SELECT uni_fis.fecha_ingreso
					FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
						INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
					WHERE cxc_fact_det_vehic.id_factura = comision_emp.id_factura)))
				WHEN (id_nota_credito IS NOT NULL) THEN
					(to_days((SELECT cxc_nc.fechaNotaCredito
							FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)) - to_days((SELECT uni_fis.fecha_ingreso
					FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
						INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
					WHERE cxc_fact_det_vehic.id_factura = comision_emp.id_factura)))
			END) AS dias_inventario,
			comision_emp.fecha_comision
		FROM pg_comision_empleado comision_emp %s
		ORDER BY 2,3,4", $sqlBusq2);
		$rsDetalle = mysql_query($queryDetalle);
		$rsDetalle2 = mysql_query($queryDetalle);
		$rsDetalle3 = mysql_query($queryDetalle);
		
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayTotalRep = $arrayTotalServ = $arrayTotalVehic = NULL;
		
		while ($rowDet = mysql_fetch_assoc($rsDetalle2)) {
			
			$accesorio = nombreAccesorio($rowComision['id_cargo_departamento'], $rowDet['id_factura'], $rowComision['id_empleado']);
				
			while($Art = mysql_fetch_assoc($accesorio)){
				$rowArtEncabezado[$Art['nom_accesorio']] = $Art['monto'];
			}
		}
		
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
			
			$rsArt = nombreAccesorio($rowComision['id_cargo_departamento'], $rowDetalle['id_factura'], $rowComision['id_empleado']);
			$rowArt = '';
			while($Art = mysql_fetch_assoc($rsArt)){
				$rowArt[$Art['nom_accesorio']] = $Art['monto'];
			}

			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$clase = ($rowDetalle['tipo_documento'] == "FA" || $rowDetalle['tipo_documento'] == "VS") ? $clase : "divMsjError";
			
			if ($rowDetalle['tipo_documento'] == "FA") {
				$indice = 0;
				$signo = 1;
			} else if ($rowDetalle['tipo_documento'] == "NC") {
				$indice = 1;
				$signo = (-1);
			} else if ($rowDetalle['tipo_documento'] == "VS") {
				$indice = 2;
				$signo = 1;
			}
			
			$queryDetalleTipo = sprintf("SELECT 
				(CASE
					WHEN comision_emp_det.id_tempario IS NOT NULL THEN 1
					WHEN comision_emp_det.id_det_fact_tot IS NOT NULL THEN 2
					WHEN comision_emp_det.id_det_fact_nota IS NOT NULL THEN 3
					WHEN comision_emp_det.id_articulo IS NOT NULL THEN 4
					WHEN comision_emp_det.id_unidad_fisica IS NOT NULL THEN 5
					WHEN comision_emp_det.id_accesorio IS NOT NULL THEN 6
				END) AS tipo_comision,
				
				(CASE
					WHEN comision_emp_det.id_tempario IS NOT NULL THEN 'Manos de Obra'
					WHEN comision_emp_det.id_det_fact_tot IS NOT NULL THEN 'T.O.T.'
					WHEN comision_emp_det.id_det_fact_nota IS NOT NULL THEN 'Notas'
					WHEN comision_emp_det.id_articulo IS NOT NULL THEN 'Repuestos'
					WHEN comision_emp_det.id_unidad_fisica IS NOT NULL THEN 'Vehículos'
					WHEN comision_emp_det.id_accesorio IS NOT NULL THEN 'Accesorios'
				END) AS nombre_tipo_comision,
				
				SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) AS total_venta_bruta,
				
				IF(comision_emp_det.id_tempario IS NULL AND comision_emp_det.id_det_fact_tot IS NULL AND comision_emp_det.id_det_fact_nota IS NULL, ((SELECT comision_emp.monto_descuento * 100 / SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) FROM pg_comision_empleado comision_emp
				WHERE comision_emp.id_comision_empleado = comision_emp_det.id_comision_empleado) * SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) / 100), 0) AS total_descuento,
				
				(SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) - IF(comision_emp_det.id_tempario IS NULL AND comision_emp_det.id_det_fact_tot IS NULL AND comision_emp_det.id_det_fact_nota IS NULL, ((SELECT comision_emp.monto_descuento * 100 / SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) FROM pg_comision_empleado comision_emp
				WHERE comision_emp.id_comision_empleado = comision_emp_det.id_comision_empleado) * SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) / 100), 0)) AS total_venta_neta,
				
				SUM(comision_emp_det.cantidad * comision_emp_det.costo_compra) AS total_costo,
				
				((SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) - IF(comision_emp_det.id_tempario IS NULL AND comision_emp_det.id_det_fact_tot IS NULL AND comision_emp_det.id_det_fact_nota IS NULL, ((SELECT comision_emp.monto_descuento * 100 / SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) FROM pg_comision_empleado comision_emp
				WHERE comision_emp.id_comision_empleado = comision_emp_det.id_comision_empleado) * SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) / 100), 0))
				-
				SUM(comision_emp_det.cantidad * comision_emp_det.costo_compra)) AS utilidad_bruta,
				
				((((SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) - IF(comision_emp_det.id_tempario IS NULL AND comision_emp_det.id_det_fact_tot IS NULL AND comision_emp_det.id_det_fact_nota IS NULL, ((SELECT comision_emp.monto_descuento * 100 / SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) FROM pg_comision_empleado comision_emp
				WHERE comision_emp.id_comision_empleado = comision_emp_det.id_comision_empleado) * SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) / 100), 0))
				-
				SUM(comision_emp_det.cantidad * comision_emp_det.costo_compra)) * 100)
				/
				(SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) - IF(comision_emp_det.id_tempario IS NULL AND comision_emp_det.id_det_fact_tot IS NULL AND comision_emp_det.id_det_fact_nota IS NULL, ((SELECT comision_emp.monto_descuento * 100 / SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) FROM pg_comision_empleado comision_emp
				WHERE comision_emp.id_comision_empleado = comision_emp_det.id_comision_empleado) * SUM(comision_emp_det.cantidad * comision_emp_det.precio_venta) / 100), 0))) AS porcentaje_utilidad_venta,
				
				SUM(comision_emp_det.cantidad * comision_emp_det.monto_comision) AS total_monto_comision
			FROM pg_comision_empleado_detalle comision_emp_det
			WHERE comision_emp_det.id_comision_empleado = %s
			GROUP BY
				(CASE
					WHEN comision_emp_det.id_tempario IS NOT NULL THEN 1
					WHEN comision_emp_det.id_det_fact_tot IS NOT NULL THEN 2
					WHEN comision_emp_det.id_det_fact_nota IS NOT NULL THEN 3
					WHEN comision_emp_det.id_articulo IS NOT NULL THEN 4
					WHEN comision_emp_det.id_unidad_fisica IS NOT NULL THEN 5
					WHEN comision_emp_det.id_accesorio IS NOT NULL THEN 6
				END)",
					valTpDato($rowDetalle['id_comision_empleado'], "int"));
			$rsDetalleTipo = mysql_query($queryDetalleTipo);
			if (!$rsDetalleTipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while ($rowDetalleTipo = mysql_fetch_assoc($rsDetalleTipo)) {
				$arrayResumen[$rowDetalleTipo['tipo_comision']][0] = $rowDetalleTipo['nombre_tipo_comision'];
				$arrayResumen[$rowDetalleTipo['tipo_comision']][1] += $signo * $rowDetalleTipo['total_venta_bruta'];
				$arrayResumen[$rowDetalleTipo['tipo_comision']][2] += $signo * $rowDetalleTipo['total_descuento'];
				$arrayResumen[$rowDetalleTipo['tipo_comision']][3] += $signo * $rowDetalleTipo['total_venta_neta'];
				$arrayResumen[$rowDetalleTipo['tipo_comision']][4] += $signo * $rowDetalleTipo['total_costo'];
				$arrayResumen[$rowDetalleTipo['tipo_comision']][5] += $signo * $rowDetalleTipo['utilidad_bruta'];
				$arrayResumen[$rowDetalleTipo['tipo_comision']][6] += $signo * $rowDetalleTipo['porcentaje_utilidad_venta'];
				$arrayResumen[$rowDetalleTipo['tipo_comision']][7] += $signo * $rowDetalleTipo['total_monto_comision'];
			}
			
			if ($rowDetalle['id_modulo'] == 0) {
				$imgModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>";
				
				$arrayTotalRep[$indice][7] ++;
				$arrayTotalRep[$indice][0] += $signo * $rowDetalle['venta_bruta'];
				$arrayTotalRep[$indice][1] += $signo * $rowDetalle['monto_descuento'];
				$arrayTotalRep[$indice][2] += $signo * $rowDetalle['venta_neta'];
				$arrayTotalRep[$indice][3] += $signo * $rowDetalle['costo_compra'];
				$arrayTotalRep[$indice][4] += $signo * $rowDetalle['utilidad_bruta'];
				$arrayTotalRep[$indice][5] = ($arrayTotalRep[$indice][7] > 0) ? ($arrayTotalRep[$indice][4] * 100) / $arrayTotalRep[$indice][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalRep[$indice][6] += $signo * $rowDetalle['monto_comision'];
			} else if ($rowDetalle['id_modulo'] == 1) {
				$imgModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>";
				
				$arrayTotalServ[$indice][7] ++;
				$arrayTotalServ[$indice][0] += $signo * $rowDetalle['venta_bruta'];
				$arrayTotalServ[$indice][1] += $signo * $rowDetalle['monto_descuento'];
				$arrayTotalServ[$indice][2] += $signo * $rowDetalle['venta_neta'];
				$arrayTotalServ[$indice][3] += $signo * $rowDetalle['costo_compra'];
				$arrayTotalServ[$indice][4] += $signo * $rowDetalle['utilidad_bruta'];
				$arrayTotalServ[$indice][5] = ($arrayTotalServ[$indice][7] > 0) ? ($arrayTotalServ[$indice][4] * 100) / $arrayTotalServ[$indice][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalServ[$indice][6] += $signo * $rowDetalle['monto_comision'];
			} else if ($rowDetalle['id_modulo'] == 2) {
				$imgModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>";
				
				$arrayTotalVehic[$indice][7] ++;
				$arrayTotalVehic[$indice][0] += $signo * $rowDetalle['venta_bruta'];
				$arrayTotalVehic[$indice][1] += $signo * $rowDetalle['monto_descuento'];
				$arrayTotalVehic[$indice][2] += $signo * $rowDetalle['venta_neta'];
				$arrayTotalVehic[$indice][3] += $signo * $rowDetalle['costo_compra'];
				$arrayTotalVehic[$indice][4] += $signo * $rowDetalle['utilidad_bruta'];
				$arrayTotalVehic[$indice][5] = ($arrayTotalVehic[$indice][7] > 0) ? ($arrayTotalVehic[$indice][4] * 100) / $arrayTotalVehic[$indice][2] : 0; // PORCENTAJE UTILIDAD VENTA
				$arrayTotalVehic[$indice][6] += $signo * $rowDetalle['monto_comision'];
			}
			
			if(!$encabezado){
				$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td colspan=\"3\">"."Folio Factura"."</td>";
				$htmlTb .= "<td>"."Tipo Pago"."</td>";
				$htmlTb .= "<td>"."C.I. / R.I.F."."</td>";
				$htmlTb .= "<td>"."Cliente"."</td>";
				$htmlTb .= "<td>"."Venta Bruta"."</td>";
				$htmlTb .= "<td>"."Descuento"."</td>";
				$htmlTb .= "<td>"."Venta Neta"."</td>";
				$htmlTb .= "<td>"."Costo"."</td>";
				$htmlTb .= "<td>"."Utl. Bruta"."</td>";
				$htmlTb .= "<td>"."%Utl. Bruta"."</td>";
				$htmlTb .= "<td>"."Fecha Dcto."."</td>";
				$htmlTb .= "<td>"."Fecha comision"."</td>";
				$htmlTb .= "<td>"."Dias de Inv."."</td>";

				if ($arrayTotalVehic[0][7] > 0 || $arrayTotalVehic[1][7] > 0) {
					foreach ($rowArtEncabezado as $nb_art => $valor){
						$htmlTb .= "<td>".$nb_art."</td>";
					}
				}
				$htmlTb .= "<td>"."% Comisión"."</td>";
				$htmlTb .= "<td>"."Comisión"."</td>";
				$htmlTb .= "<td colspan=\"2\"></td>";
				$htmlTb .= "</tr>";
				$encabezado = true;
			}
			
			if($rowDetalle['costo_compra'] > 0){
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
					$htmlTb .= "<td>".$imgModulo."</td>";
					$htmlTb .= "<td title=\"".$rowDetalle['id_comision_empleado']."\" width=\"1%\">".$rowDetalle['tipo_documento']."</td>";
					$htmlTb .= "<td width=\"5%\">".$rowDetalle['numero_documento']."</td>";
					$htmlTb .= "<td align=\"left\" width=\"6%\">";
						switch ($rowDetalle['tipo_pago']) {
							case "0" :	$htmlTb .= "CRÉDITO"; break;
							case "1" :	$htmlTb .= "CONTADO"; break;
							default :	$htmlTb .= $rowDetalle['tipo_pago']; break;
						}
						$htmlTb .= (strlen($rowDetalle['nombre_tipo_orden']) > 0) ? "<br><span class=\"textoNegrita_7px\">".$rowDetalle['nombre_tipo_orden']."</span>" : "";
					$htmlTb .= "</td>";
					$htmlTb .= "<td style=\"padding-right:2px\" width=\"7%\">".$rowDetalle['ci_cliente']."</td>";
					$htmlTb .= "<td align=\"left\" width=\"20%\">".utf8_encode($rowDetalle['nombre_cliente'])."</td>";
					$htmlTb .= "<td width=\"7%\">";
						$htmlTb .= number_format($signo * $rowDetalle['venta_bruta'], 2, ".", ",");
					$htmlTb .= "</td>";
					$htmlTb .= "<td width=\"7%\">";
						$htmlTb .= number_format($signo * $rowDetalle['monto_descuento'], 2, ".", ",");
					$htmlTb .= "</td>";
					$htmlTb .= "<td width=\"7%\">";
						$htmlTb .= number_format($signo * $rowDetalle['venta_neta'], 2, ".", ",");
					$htmlTb .= "</td>";
					$htmlTb .= "<td width=\"7%\">";
						$htmlTb .= number_format($signo * $rowDetalle['costo_compra'], 2, ".", ",");
					$htmlTb .= "</td>";
					$htmlTb .= "<td width=\"7%\">";
						$htmlTb .= number_format($signo * $rowDetalle['utilidad_bruta'], 2, ".", ",");
					$htmlTb .= "</td>";
					$htmlTb .= "<td width=\"6%\">";
						$htmlTb .= number_format($signo * $rowDetalle['porcentaje_utilidad_venta'], 2, ".", ",");
					$htmlTb .= "</td>";
					$htmlTb .= "<td align=\"center\" width=\"7%\">".date("d-m-Y",strtotime($rowDetalle['fecha_documento']))."</td>";
					$htmlTb .= "<td align=\"center\" width=\"7%\">".date("d-m-Y",strtotime($rowDetalle['fecha_comision']))."</td>";
					$htmlTb .= "<td align=\"right\" width=\"3%\">";
						$htmlTb .= ($rowDetalle['dias_inventario'] != "") ? $rowDetalle['dias_inventario'] : "-";
					$htmlTb .= "</td>";
					
					if ($arrayTotalVehic[0][7] > 0 || $arrayTotalVehic[1][7] > 0) {
						
						$j = 0;
						foreach ($rowArtEncabezado As $nb_art => $valor){
							
							$i = 0;
							foreach ($rowArt as $nb_artEnc => $valor1)
								if($nb_art == $nb_artEnc) $i = 1;
							
							if($i > 0)$rowArtNew[$nb_art] = $valor;
							else $rowArtNew[$nb_art] = 0;
						}
						
						foreach ($rowArtNew As $nb_art => $valor){
							$htmlTb .= "<td width=\"6%\">";
							$htmlTb .= number_format($signo * $valor, 2, ".", ",");
							$htmlTb .= "</td>";
						}
						
					}
					
					$detComision = implode("/", $rowDetalle);
					
					$htmlTb .= "<td width=\"4%\">";
						$htmlTb .= sprintf("<input onblur='setFormatoRafk(this, 2)' onchange=\"xajax_calcularComision('%s', %s, this.value);\" style='text-align:center' size=\"6px\"  name='3' value=\"%s\" />", $detComision, $rowComision['id_cargo_departamento'], number_format($signo * $rowDetalle['promedio_porcentaje_comision'],2,'.',','));
					$htmlTb .= "</td>";
					$htmlTb .= "<td id='monto/{$rowDetalle['id_comision_empleado']}' width=\"6%\">";
						$htmlTb .= number_format($signo * $rowDetalle['monto_comision'], 2, ".", ",");
					$htmlTb .= "</td>";
					$htmlTb .= "<td>";
						$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this,'tblListaComisionDetalle','%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\" title=\"Ver Detalle\"/></a>",
							$rowDetalle['id_comision_empleado']);
					$htmlTb .= "</td>";
					$htmlTb .= "<td>";
						$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_delete.png\" title=\"Eliminar Comisión\"/></a>",
							$rowDetalle['id_comision_empleado']);
					$htmlTb .= "</td>";
				$htmlTb .= "</tr>";
			}
		}
		
		// TOTAL FACTURAS
		$arrayTotal[0][7] += $arrayTotalRep[0][7] + $arrayTotalServ[0][7] + $arrayTotalVehic[0][7];
		$arrayTotal[0][0] += $arrayTotalRep[0][0] + $arrayTotalServ[0][0] + $arrayTotalVehic[0][0];
		$arrayTotal[0][1] += $arrayTotalRep[0][1] + $arrayTotalServ[0][1] + $arrayTotalVehic[0][1];
		$arrayTotal[0][2] += $arrayTotalRep[0][2] + $arrayTotalServ[0][2] + $arrayTotalVehic[0][2];
		$arrayTotal[0][3] += $arrayTotalRep[0][3] + $arrayTotalServ[0][3] + $arrayTotalVehic[0][3];
		$arrayTotal[0][4] += $arrayTotalRep[0][4] + $arrayTotalServ[0][4] + $arrayTotalVehic[0][4];
		$arrayTotal[0][5] = ($arrayTotal[0][7] > 0) ? ($arrayTotal[0][4] * 100) / $arrayTotal[0][2] : 0; // PORCENTAJE UTILIDAD VENTA
		$arrayTotal[0][6] += $arrayTotalRep[0][6] + $arrayTotalServ[0][6] + $arrayTotalVehic[0][6];
		
		// TOTAL NOTAS CREDITO
		$arrayTotal[1][7] += $arrayTotalRep[1][7] + $arrayTotalServ[1][7] + $arrayTotalVehic[1][7];
		$arrayTotal[1][0] += $arrayTotalRep[1][0] + $arrayTotalServ[1][0] + $arrayTotalVehic[1][0];
		$arrayTotal[1][1] += $arrayTotalRep[1][1] + $arrayTotalServ[1][1] + $arrayTotalVehic[1][1];
		$arrayTotal[1][2] += $arrayTotalRep[1][2] + $arrayTotalServ[1][2] + $arrayTotalVehic[1][2];
		$arrayTotal[1][3] += $arrayTotalRep[1][3] + $arrayTotalServ[1][3] + $arrayTotalVehic[1][3];
		$arrayTotal[1][4] += $arrayTotalRep[1][4] + $arrayTotalServ[1][4] + $arrayTotalVehic[1][4];
		$arrayTotal[1][5] = ($arrayTotal[1][7] > 0) ? ($arrayTotal[1][4] * 100) / $arrayTotal[1][2] : 0; // PORCENTAJE UTILIDAD VENTA
		$arrayTotal[1][6] += $arrayTotalRep[1][6] + $arrayTotalServ[1][6] + $arrayTotalVehic[1][6];
		
		// TOTAL VALES SALIDA
		$arrayTotal[2][7] += $arrayTotalServ[2][7];
		$arrayTotal[2][0] += $arrayTotalServ[2][0];
		$arrayTotal[2][1] += $arrayTotalServ[2][1];
		$arrayTotal[2][2] += $arrayTotalServ[2][2];
		$arrayTotal[2][3] += $arrayTotalServ[2][3];
		$arrayTotal[2][4] += $arrayTotalServ[2][4];
		$arrayTotal[2][5] = ($arrayTotal[2][7] > 0) ? ($arrayTotal[2][4] * 100) / $arrayTotal[2][2] : 0; // PORCENTAJE UTILIDAD VENTA
		$arrayTotal[2][6] += $arrayTotalServ[2][6];
		
		// TOTAL COMISION
		$arrayTotalComision[7] = $arrayTotal[0][7] + $arrayTotal[1][7] + $arrayTotal[2][7];
		$arrayTotalComision[0] = $arrayTotal[0][0] + $arrayTotal[1][0] + $arrayTotal[2][0];
		$arrayTotalComision[1] = $arrayTotal[0][1] + $arrayTotal[1][1] + $arrayTotal[2][1];
		$arrayTotalComision[2] = $arrayTotal[0][2] + $arrayTotal[1][2] + $arrayTotal[2][2];
		$arrayTotalComision[3] = $arrayTotal[0][3] + $arrayTotal[1][3] + $arrayTotal[2][3];
		$arrayTotalComision[4] = $arrayTotal[0][4] + $arrayTotal[1][4] + $arrayTotal[2][4];
		$arrayTotalComision[5] = ($arrayTotalComision[7] > 0) ? ($arrayTotalComision[4] * 100) / $arrayTotalComision[2] : 0; // PORCENTAJE UTILIDAD VENTA
		$arrayTotalComision[6] = $arrayTotal[0][6] + $arrayTotal[1][6] + $arrayTotal[2][6];
		
		$htmlTh .= "<tr align=\"left\" class=\"trResaltar4\" height=\"24\">";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"4\" rowspan=\"3\">"."Empleado:"."</td>";
			$htmlTh .= "<td colspan=\"7\">".$rowComision['cedula']."</td>";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">"."Mes / Año:"."</td>";
			$htmlTh .= "<td align=\"center\" colspan=\"5\">".$mes[$rowComision['mes_documento']]." ".$rowComision['ano_documento']."</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"left\" class=\"trResaltar4\" height=\"24\">";
			$htmlTh .= "<td colspan=\"7\">".utf8_encode($rowComision['nombre_empleado'])."</td>";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">"."% Productividad:"."</td>";
		$htmlTh .= "<td align=\"center\" colspan=\"5\">".number_format($rowComision['porcentaje_productividad'], 2, ".", ",")."</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"left\" class=\"trResaltar4\" height=\"24\">";
			$htmlTh .= "<td colspan=\"14\">".utf8_encode($rowComision['nombre_cargo'])."</td>";
		$htmlTh .= "</tr>";

		$numColspan = (4+count($rowArtEncabezado));
		$colVeh = ($arrayTotalVehic[0][7] > 0 || $arrayTotalVehic[1][7] > 0) ? $numColspan : 3;
		
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"6\"></td>";
			$htmlTh .= "<td>"."Venta Bruta"."</td>";
			$htmlTh .= "<td>"."Descuento"."</td>";
			$htmlTh .= "<td>"."Venta Neta"."</td>";
			$htmlTh .= "<td>"."Costo"."</td>";
			$htmlTh .= "<td>"."Utl. Bruta"."</td>";
			$htmlTh .= "<td>"."%Utl. Bruta"."</td>";
			$htmlTh .= "<td colspan=\"".$numColspan."\">&nbsp;</td>";
			$htmlTh .= "<td colspan=\"3\">"."Comision"."</td>";
		$htmlTh .= "</tr>";
		
		if ($arrayTotalRep[0][7] > 0 || $arrayTotalServ[0][7] > 0 || $arrayTotalVehic[0][7] > 0) {
			if ($arrayTotalRep[0][7] > 0) {
				$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
					$htmlTh .= "<td colspan=\"4\"></td>";
					$htmlTh .= "<td>";
						$htmlTh .= "<div style=\"float:left\">"."<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"."</div>";
						$htmlTh .= "<div style=\"float:right\">".$arrayTotalRep[0][7]."</div>";
					$htmlTh .= "</td>";
					$htmlTh .= "<td>"."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[0][0], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[0][1], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[0][2], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[0][3], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[0][4], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[0][5], 3, ".", ",")."</td>";
					$htmlTh .= "<td colspan=\"3\">&nbsp;</td>";
					$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotalRep[0][6], 3, ".", ",")."</td>";
				$htmlTh .= "</tr>";
			}
			
			if ($arrayTotalServ[0][7] > 0) {
				$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
					$htmlTh .= "<td colspan=\"4\"></td>";
					$htmlTh .= "<td>";
						$htmlTh .= "<div style=\"float:left\">"."<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"."</div>";
						$htmlTh .= "<div style=\"float:right\">".$arrayTotalServ[0][7]."</div>";
					$htmlTh .= "</td>";
					$htmlTh .= "<td>"."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[0][0], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[0][1], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[0][2], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[0][3], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[0][4], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[0][5], 3, ".", ",")."</td>";
					$htmlTh .= "<td colspan=\"3\">&nbsp;</td>";
					$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotalServ[0][6], 3, ".", ",")."</td>";
				$htmlTh .= "</tr>";
			}
			
			if ($arrayTotalVehic[0][7] > 0) {
				$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
					$htmlTh .= "<td colspan=\"4\"></td>";
					$htmlTh .= "<td>";
						$htmlTh .= "<div style=\"float:left\">"."<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"."</div>";
						$htmlTh .= "<div style=\"float:right\">".$arrayTotalVehic[0][7]."</div>";
					$htmlTh .= "</td>";
					$htmlTh .= "<td>"."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalVehic[0][0], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalVehic[0][1], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalVehic[0][2], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalVehic[0][3], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalVehic[0][4], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalVehic[0][5], 3, ".", ",")."</td>";
					$htmlTh .= "<td colspan=\"".$numColspan."\">&nbsp;</td>";
					$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotalVehic[0][6], 3, ".", ",")."</td>";
				$htmlTh .= "</tr>";
			}
			$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTh .= "<td class=\"tituloCampo\" colspan=\"4\">Total Facturas:</td>";
				$htmlTh .= "<td>".$arrayTotal[0][7]."</td>";
				$htmlTh .= "<td>"."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[0][0], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[0][1], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[0][2], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[0][3], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[0][4], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[0][5], 3, ".", ",")."</td>";
			$htmlTh .= "<td colspan=\"".$colVeh."\">&nbsp;</td>";
				$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotal[0][6], 3, ".", ",")."</td>";
			$htmlTh .= "</tr>";
		}
		
		if ($arrayTotalRep[1][7] > 0 || $arrayTotalServ[1][7] > 0 || $arrayTotalVehic[1][7] > 0) {
			if ($arrayTotalRep[1][7] > 0) {
				$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
					$htmlTh .= "<td colspan=\"4\"></td>";
					$htmlTh .= "<td>";
						$htmlTh .= "<div style=\"float:left\">"."<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"."</div>";
						$htmlTh .= "<div style=\"float:right\">".$arrayTotalRep[1][7]."</div>";
					$htmlTh .= "</td>";
					$htmlTh .= "<td>"."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[1][0], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[1][1], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[1][2], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[1][3], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[1][4], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalRep[1][5], 3, ".", ",")."</td>";
					$htmlTh .= "<td colspan=\"3\">&nbsp;</td>";
					$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotalRep[1][6], 3, ".", ",")."</td>";
				$htmlTh .= "</tr>";
			}
			
			if ($arrayTotalServ[1][7] > 0) {
				$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
					$htmlTh .= "<td colspan=\"4\"></td>";
					$htmlTh .= "<td>";
						$htmlTh .= "<div style=\"float:left\">"."<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"."</div>";
						$htmlTh .= "<div style=\"float:right\">".$arrayTotalServ[1][7]."</div>";
					$htmlTh .= "</td>";
					$htmlTh .= "<td>"."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[1][0], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[1][1], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[1][2], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[1][3], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[1][4], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[1][5], 3, ".", ",")."</td>";
					$htmlTh .= "<td colspan=\"3\">&nbsp;</td>";
					$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotalServ[1][6], 3, ".", ",")."</td>";
				$htmlTh .= "</tr>";
			}
			
			$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTh .= "<td class=\"tituloCampo\" colspan=\"4\">"."Total Notas Créd.:"."</td>";
				$htmlTh .= "<td>".$arrayTotal[1][7]."</td>";
				$htmlTh .= "<td>"."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[1][0], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[1][1], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[1][2], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[1][3], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[1][4], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[1][5], 3, ".", ",")."</td>";
			$htmlTh .= "<td colspan=\"".$colVeh."\">&nbsp;</td>";
				$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotal[1][6], 3, ".", ",")."</td>";
			$htmlTh .= "</tr>";
		}
		
		if ($arrayTotalServ[2][7] > 0) {
			if ($arrayTotalServ[2][7] > 0) {
				$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
					$htmlTh .= "<td colspan=\"4\"></td>";
					$htmlTh .= "<td>";
						$htmlTh .= "<div style=\"float:left\">"."<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"."</div>";
						$htmlTh .= "<div style=\"float:right\">".$arrayTotalServ[2][7]."</div>";
					$htmlTh .= "</td>";
					$htmlTh .= "<td>"."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[2][0], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[2][1], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[2][2], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[2][3], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[2][4], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayTotalServ[2][5], 3, ".", ",")."</td>";
					$htmlTh .= "<td colspan=\"3\">&nbsp;</td>";
					$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotalServ[2][6], 3, ".", ",")."</td>";
				$htmlTh .= "</tr>";
			}
			$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTh .= "<td class=\"tituloCampo\" colspan=\"4\">".utf8_encode("Total Vales Salida:")."</td>";
				$htmlTh .= "<td>".$arrayTotal[2][7]."</td>";
				$htmlTh .= "<td>"."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[2][0], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[2][1], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[2][2], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[2][3], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[2][4], 3, ".", ",")."</td>";
				$htmlTh .= "<td>".number_format($arrayTotal[2][5], 3, ".", ",")."</td>";
				$htmlTh .= "<td colspan=\"3\">&nbsp;</td>";
				$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotal[2][6], 3, ".", ",")."</td>";
			$htmlTh .= "</tr>";
		}
		
		$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTh .= "<td class=\"tituloCampo\" colspan=\"4\">Total:</td>";
			$htmlTh .= "<td colspan=\"2\"></td>";
			$htmlTh .= "<td>".number_format($arrayTotalComision[0], 3, ".", ",")."</td>";
			$htmlTh .= "<td>".number_format($arrayTotalComision[1], 3, ".", ",")."</td>";
			$htmlTh .= "<td>".number_format($arrayTotalComision[2], 3, ".", ",")."</td>";
			$htmlTh .= "<td>".number_format($arrayTotalComision[3], 3, ".", ",")."</td>";
			$htmlTh .= "<td>".number_format($arrayTotalComision[4], 3, ".", ",")."</td>";
			$htmlTh .= "<td>".number_format($arrayTotalComision[5], 3, ".", ",")."</td>";
		$htmlTh .= "<td colspan=\"".$colVeh."\">&nbsp;</td>";
			$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotalComision[6], 3, ".", ",")."</td>";
		$htmlTh .= "</tr>";
		
		$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"50\">&nbsp;</td>";
		$htmlTh .= "</tr>";
		
		if (isset($arrayResumen)) {
			foreach ($arrayResumen as $indice => $valor) {
				$arrayResumen[$indice][6] = ($arrayResumen[$indice][1] > 0) ? ($arrayResumen[$indice][5] * 100) / $arrayResumen[$indice][3] : 0; // PORCENTAJE UTILIDAD VENTA
				
				$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
					$htmlTh .= "<td class=\"tituloCampo\" colspan=\"4\">"."Total ".$arrayResumen[$indice][0].":"."</td>";
					$htmlTh .= "<td colspan=\"2\"></td>";
					$htmlTh .= "<td>".number_format($arrayResumen[$indice][1], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayResumen[$indice][2], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayResumen[$indice][3], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayResumen[$indice][4], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayResumen[$indice][5], 3, ".", ",")."</td>";
					$htmlTh .= "<td>".number_format($arrayResumen[$indice][6], 3, ".", ",")."</td>";
					$htmlTh .= "<td colspan=\"".$colVeh."\"></td>";
					$htmlTh .= "<td colspan=\"3\">".number_format($arrayResumen[$indice][7], 3, ".", ",")."</td>";
				$htmlTh .= "</tr>";
				
				$arrayTotalResumen[1] += $arrayResumen[$indice][1];
				$arrayTotalResumen[2] += $arrayResumen[$indice][2];
				$arrayTotalResumen[3] += $arrayResumen[$indice][3];
				$arrayTotalResumen[4] += $arrayResumen[$indice][4];
				$arrayTotalResumen[5] += $arrayResumen[$indice][5];
				$arrayTotalResumen[6] += $arrayResumen[$indice][6];
				$arrayTotalResumen[7] += $arrayResumen[$indice][7];
			}
		}
		
		$arrayTotalResumen[6] = ($arrayTotalResumen[1] > 0) ? ($arrayTotalResumen[5] * 100) / $arrayTotalResumen[3] : 0; // PORCENTAJE UTILIDAD VENTA

		$htmlTh .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTh .= "<td class=\"tituloCampo\" colspan=\"4\">"."Total:"."</td>";
			$htmlTh .= "<td colspan=\"2\"></td>";
			$htmlTh .= "<td>".number_format($arrayTotalResumen[1], 3, ".", ",")."</td>";
			$htmlTh .= "<td>".number_format($arrayTotalResumen[2], 3, ".", ",")."</td>";
			$htmlTh .= "<td>".number_format($arrayTotalResumen[3], 3, ".", ",")."</td>";
			$htmlTh .= "<td>".number_format($arrayTotalResumen[4], 3, ".", ",")."</td>";
			$htmlTh .= "<td>".number_format($arrayTotalResumen[5], 3, ".", ",")."</td>";
			$htmlTh .= "<td>".number_format($arrayTotalResumen[6], 3, ".", ",")."</td>";
			$htmlTh .= "<td colspan=\"".$colVeh."\"></td>";
			$htmlTh .= "<td colspan=\"3\">".number_format($arrayTotalResumen[7], 3, ".", ",")."</td>";
		$htmlTh .= "</tr>";
		
		$colFoot = ($arrayTotalVehic[0][7] > 0 || $arrayTotalVehic[1][7] > 0) ? 27 : 18;
		$htmlTh .= "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td colspan=\"$colFoot\">&nbsp;</td>";
		$htmlTh .= "</tr>";
	}
	
	$htmlTf = "<tr>";
			$htmlTf .= "<td align=\"center\" colspan=\"50\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">&nbsp;";
					/*$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);*/
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComision(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComision(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaComision(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComision(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComision(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	$objResponse->assign("divListaComisiones","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}


function listaComisionDetalle($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_comision_empleado = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	$queryComision = sprintf("SELECT
		(CASE
			WHEN (comision_emp_det.id_articulo IS NOT NULL) THEN
				(SELECT art.codigo_articulo FROM iv_articulos art WHERE art.id_articulo = comision_emp_det.id_articulo)
			WHEN (comision_emp_det.id_unidad_fisica IS NOT NULL) THEN
				(SELECT uni_bas.nom_uni_bas FROM an_unidad_fisica uni_fis
					INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
				WHERE uni_fis.id_unidad_fisica = comision_emp_det.id_unidad_fisica)
			WHEN (comision_emp_det.id_accesorio IS NOT NULL) THEN
				'-'
			WHEN (comision_emp_det.id_tempario IS NOT NULL) THEN
				(SELECT codigo_tempario FROM sa_tempario WHERE id_tempario = comision_emp_det.id_tempario)
			WHEN (comision_emp_det.id_det_fact_tot IS NOT NULL) THEN
				(SELECT ord_tot.id_orden_tot FROM sa_det_fact_tot det_fact_tot
					INNER JOIN sa_orden_tot ord_tot ON (det_fact_tot.id_orden_tot = ord_tot.id_orden_tot)
				WHERE det_fact_tot.id_det_fact_tot = comision_emp_det.id_det_fact_tot)
			ELSE
				NULL
		END) AS codigo_articulo,
		
		(CASE
			WHEN (comision_emp_det.id_articulo IS NOT NULL) THEN
				(SELECT art.descripcion FROM iv_articulos art WHERE art.id_articulo = comision_emp_det.id_articulo)
			WHEN (comision_emp_det.id_unidad_fisica IS NOT NULL) THEN
				(SELECT
					CONCAT(modelo.nom_modelo, ' - ', version.nom_version, ' (', placa, ')') AS vehiculo
				FROM an_unidad_fisica uni_fis
					INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
					INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
					INNER JOIN an_version version ON (uni_bas.ver_uni_bas = version.id_version)
				WHERE uni_fis.id_unidad_fisica = comision_emp_det.id_unidad_fisica)
			WHEN (comision_emp_det.id_accesorio IS NOT NULL) THEN
				(SELECT acc.nom_accesorio FROM an_accesorio acc WHERE acc.id_accesorio = comision_emp_det.id_accesorio)
			WHEN (comision_emp_det.id_tempario IS NOT NULL) THEN
				(SELECT descripcion_tempario FROM sa_tempario WHERE id_tempario = comision_emp_det.id_tempario)
			WHEN (comision_emp_det.id_det_fact_tot IS NOT NULL) THEN
				IF((SELECT observacion_factura FROM sa_det_fact_tot det_fact_tot
					INNER JOIN sa_orden_tot ord_tot ON (det_fact_tot.id_orden_tot = ord_tot.id_orden_tot)
				WHERE det_fact_tot.id_det_fact_tot = comision_emp_det.id_det_fact_tot) <> '', (SELECT observacion_factura FROM sa_det_fact_tot det_fact_tot
					INNER JOIN sa_orden_tot ord_tot ON (det_fact_tot.id_orden_tot = ord_tot.id_orden_tot)
				WHERE det_fact_tot.id_det_fact_tot = comision_emp_det.id_det_fact_tot), 'TOT')
			ELSE
				NULL
		END) AS descripcion,
		
		(TO_DAYS((
			SELECT
				(CASE
					WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
						(SELECT cxc_fact.fechaRegistroFactura FROM cj_cc_encabezadofactura cxc_fact
						WHERE cxc_fact.idFactura = comision_emp.id_factura)
					WHEN (id_nota_credito IS NOT NULL) THEN
						(SELECT cxc_nc.fechaNotaCredito FROM cj_cc_notacredito cxc_nc
						WHERE cxc_nc.idNotaCredito = comision_emp.id_nota_credito)
				END) AS fecha_documento
			FROM pg_comision_empleado comision_emp
			WHERE comision_emp.id_comision_empleado = comision_emp_det.id_comision_empleado))
			-
			TO_DAYS((SELECT uni_fis.fecha_ingreso FROM an_unidad_fisica uni_fis
			WHERE uni_fis.id_unidad_fisica = comision_emp_det.id_unidad_fisica))) AS dias_inventario,
		
		comision_emp_det.*,
		
		(CASE
			WHEN (id_tempario IS NOT NULL) THEN
				'M.O.'
			WHEN (id_det_fact_tot IS NOT NULL) THEN
				'T.O.T.'
			WHEN (id_det_fact_nota IS NOT NULL) THEN
				'Nota'
			WHEN (id_articulo IS NOT NULL) THEN
				'Repuesto'
			WHEN (id_unidad_fisica IS NOT NULL) THEN
				'Vehículo'
			WHEN (id_accesorio IS NOT NULL) THEN
				'Accesorio'
			ELSE 
				'Arbitrario'
		END) AS descripcion_tipo_comision
	FROM pg_comision_empleado_detalle comision_emp_det %s", $sqlBusq);
	
	$queryLimitComision = sprintf("%s LIMIT %d OFFSET %d", $queryComision, $maxRows, $startRow);
	$rsLimitComision = mysql_query($queryLimitComision);
	if (!$rsLimitComision) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rsComision = mysql_query($queryComision);
		if (!$rsComision) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsComision);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$queryTipoComision = sprintf("SELECT * FROM pg_comision_empleado
				 					WHERE id_factura IN (SELECT idFactura FROM cj_cc_encabezadofactura
				      										WHERE idDepartamentoOrigenFactura IN (2) AND id_comision_empleado = %s);",
							valTpDato($valCadBusq[0], "int"));
	$rsTipoComision = mysql_query($queryTipoComision);
	$totalTipoComision = mysql_num_rows($rsTipoComision);

	if($totalTipoComision > 0){
		while ($rowDet = mysql_fetch_assoc($rsTipoComision)) {
			
			$accesorio = nombreAccesorio($rowDet['id_cargo_departamento'], $rowDet['id_factura'], $rowDet['id_empleado']);
	
			while($Art = mysql_fetch_assoc($accesorio)){
				$rowArtEncabezado[$Art['nom_accesorio']] = $Art['monto'];
			}
		}
	}
	
	$htmlTblIni = "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTb .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTb .= "<td width=\"8%\">"."Tipo Comisión"."</td>";
		$htmlTb .= "<td width=\"16%\">"."Código"."</td>";
		$htmlTb .= "<td width=\"34%\">"."Descripción"."</td>";
		$htmlTb .= "<td width=\"6%\">"."Días Inv."."</td>";
		$htmlTb .= "<td width=\"6%\">"."UT"."</td>";
		$htmlTb .= "<td width=\"6%\">"."Valor M.O."."</td>";
		$htmlTb .= "<td width=\"6%\">"."Precio"."</td>";
		$htmlTb .= "<td width=\"6%\">"."Costo"."</td>";
		if ($totalTipoComision > 0) {
			foreach ($rowArtEncabezado as $nb_art => $valor){
				$htmlTb .= "<td width=\"6%\">".$nb_art."</td>";
			}
		}
		$htmlTb .= "<td width=\"6%\">"."% Comisión"."</td>";
		$htmlTb .= "<td width=\"6%\">"."Comisión"."</td>";
	$htmlTb .= "</tr>";
	$numAcc = 0;
	
	while ($rowComision = mysql_fetch_assoc($rsLimitComision)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td title=\"".$rowComision['id_comision_empleado_detalle']."\">".substr($rowComision['descripcion_tipo_comision'],0,46)."</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($rowComision['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode(substr($rowComision['descripcion'],0,46))."</td>";
			$htmlTb .= "<td align=\"center\">".$rowComision['dias_inventario']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowComision['ut'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowComision['precio_tempario'], 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format(($rowComision['cantidad'] * $rowComision['precio_venta']), 3, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format(($rowComision['cantidad'] * $rowComision['costo_compra']), 3, ".", ",")."</td>";
			if ($totalTipoComision > 0) {
				$numAcc = count($rowArtEncabezado)+ 1;
				foreach ($rowArtEncabezado as $nb_art => $valor){
					$htmlTb .= "<td align=\"right\">".$valor."</td>";
				}
			}
			$htmlTb .= "<td align=\"right\">".number_format($rowComision['porcentaje_comision'],3,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format(($rowComision['cantidad'] * $rowComision['monto_comision']), 3, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[5] += $rowComision['ut'];
		$arrayTotal[7] += ($rowComision['cantidad'] * $rowComision['precio_venta']);
		$arrayTotal[8] += ($rowComision['cantidad'] * $rowComision['costo_compra']);
		$arrayTotal[10] += ($rowComision['cantidad'] * $rowComision['monto_comision']);
	}
	if ($contFila > 0) {
		$htmlTb.= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">Totales:</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[5], 3, ".", ",")."</td>";
			$htmlTb .= "<td>"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[7], 3, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[8], 3, ".", ",")."</td>";
			$htmlTb .= "<td colspan={$numAcc}>"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 3, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	$numAcc = 10 + $numAcc;
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"{$numAcc}\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComisionDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComisionDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaComisionDetalle(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComisionDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComisionDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("frmListaComisionDetalle","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaCuenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cuentas.idBanco = %s", 
			$valCadBusq[0]);
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_cuenta = %s",
			$valCadBusq[1]);
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cuentas.id_moneda = %s",
			$valCadBusq[2]);
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre_empresa LIKE %s
			OR nombreBanco LIKE %s
			OR numeroCuentaCompania LIKE %s",
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"),
		valTpDato("%".$valCadBusq[3]."%", "text"));
	}

	$queryCuenta = sprintf("SELECT
		idCuentas,
		cuentas.id_empresa,
		nombre_empresa,
		cuentas.idBanco,
		nombreBanco,
		numeroCuentaCompania,
		tipo_cuenta,
		cuentas.id_moneda,
		descripcion 
	FROM cuentas
		INNER JOIN bancos ON cuentas.idBanco = bancos.idBanco
		INNER JOIN pg_monedas ON cuentas.id_moneda = pg_monedas.idmoneda
		INNER JOIN pg_empresa ON cuentas.id_empresa = pg_empresa.id_empresa %s", $sqlBusq);
	
	$queryLimitCuenta = sprintf("%s LIMIT %d OFFSET %d", $queryCuenta, $maxRows, $startRow);
	$rsLimitCuenta = mysql_query($queryLimitCuenta);
	if (!$rsLimitCuenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rsCuenta = mysql_query($queryCuenta);
		if (!$rsCuenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsCuenta);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"2%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaCuenta", "20%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCuenta", "30%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listaCuenta", "20%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Cuenta");
		$htmlTh .= ordenarCampo("xajax_listaCuenta", "10%", $pageNum, "tipo_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Cuenta");
		$htmlTh .= ordenarCampo("xajax_listaCuenta", "10%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Moneda");
	$htmlTh .= "</tr>";
	
	while ($rowCuenta = mysql_fetch_assoc($rsLimitCuenta)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\">";
			$htmlTb .= sprintf("<td><button type=\"button\" id=\"btnListCuneta\" onclick=\"xajax_imprimirComisionesResumen(xajax.getFormValues('frmBuscar'),%s);\"><img src=\"../img/iconos/accept.png\"/></button></td>",$rowCuenta['idCuentas']);
			$htmlTb .= "<td>".$rowCuenta['nombre_empresa']."</td>";
			$htmlTb .= "<td>".utf8_encode($rowCuenta['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities($rowCuenta['numeroCuentaCompania'])."</td>";
			$htmlTb .= "<td align=\"center\">".$rowCuenta['tipo_cuenta']."</td>";
			$htmlTb .= "<td align=\"center\">".$rowCuenta['descripcion']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCuenta(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaNumCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

function calcularComision($detComision, $idCargoDpto, $porcTemporal) {
	$objResponse = new xajaxResponse();
	$detComision = explode("/", $detComision);
	
	if (isset($detComision)) {
		$queryIdPro = sprintf("SELECT id_comision FROM pg_comision
									WHERE id_modulo = 2 AND tipo_porcentaje = 4 AND tipo_importe = 3 AND tipo_comision = 6
									AND id_cargo_departamento = %s;",
								valTpDato($idCargoDpto, "int"));
		$rsIdPro = mysql_query($queryIdPro);
		if (!$rsIdPro) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		
		$rowIdPro = mysql_fetch_assoc($rsIdPro);
		$idComisionProducto = $rowIdPro['id_comision'];
		
		$queryProduct = sprintf("SELECT SUM(coma.monto) as monto, SUM(coma.porcentaje) as porcentaje FROM cj_cc_factura_detalle_accesorios facc
									LEFT JOIN pg_comision_articulo coma ON coma.id_articulo = facc.id_accesorio
								    WHERE facc.id_factura = %s AND coma.id_comision = %s;",
								valTpDato($detComision[20], "int"),
								valTpDato($idComisionProducto, "int"));
		$rsPro = mysql_query($queryProduct);
		if (!$rsPro) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			
		$rowPro = mysql_fetch_assoc($rsPro);
		$productos = $rowPro['monto'];
		$montoComisionVieja = $detComision[16];
		
		//Descontar los productos adicionales para obtener el profit original
		$utilidadPorc = $montoComisionVieja - $productos;
		
		//utilidad principal, incluyendo los descuentos de bonos
		$UtilidadVieja = floatval(($utilidadPorc * 100)/ $detComision[17]);
		
		//utilidad temporal
		$UtilidadTemp = number_format((($UtilidadVieja * $porcTemporal) / 100) + $productos, 2, ".", ",");
	}
	$objResponse->assign("monto/{$detComision[0]}","innerHTML", $UtilidadTemp);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarComision");
$xajax->register(XAJAX_FUNCTION,"buscarCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstCargo");
$xajax->register(XAJAX_FUNCTION,"cargarLstBanco");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargarLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargarLstTipoCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"eliminarComision");
$xajax->register(XAJAX_FUNCTION,"exportarComisiones");
$xajax->register(XAJAX_FUNCTION,"formComisionDetalle");
$xajax->register(XAJAX_FUNCTION,"formComisionProduccion");
$xajax->register(XAJAX_FUNCTION,"guardarComisionProduccion");
$xajax->register(XAJAX_FUNCTION,"imprimirComisiones");
$xajax->register(XAJAX_FUNCTION,"imprimirComisionesResumen");
$xajax->register(XAJAX_FUNCTION,"listaComision");
$xajax->register(XAJAX_FUNCTION,"listaComisionDetalle");
$xajax->register(XAJAX_FUNCTION,"listaCuenta");
$xajax->register(XAJAX_FUNCTION,"calcularComision");

function errorGuardarComisionProduccion($objResponse) {
	$objResponse->script("
	byId('btnGuardarComisionProduccion').disabled = false;
	byId('btnCancelarComisionProduccion').disabled = false;");
}
?>