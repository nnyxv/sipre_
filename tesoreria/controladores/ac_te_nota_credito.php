<?php

function asignarBanco($idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("txtTelefonoBanco","value",$row['telf']);
	$objResponse->assign("txtEmailBanco","value",$row['email']);
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_cargaLstCuenta(byId('hddIdEmpresa').value, ".$row['idBanco'].")");
	$objResponse->script("byId('btnCancelarBanco').click();");
	
	return $objResponse;
}

function asignarEmpresa($idEmpresa){
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
		valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
	$nombreSucursal = "";		
	if ($rowEmpresa['id_empresa_padre_suc'] > 0){
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
	}
	$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
	
	$objResponse->assign("txtNombreEmpresa","value",$empresa);
	$objResponse->assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	$objResponse->script("byId('btnCancelarEmpresa').click();");
	
	return $objResponse;
}

function asignarMotivo($idMotivo, $frmNotaCredito){
	$objResponse = new xajaxResponse();
	
	if(count($frmNotaCredito["hddIdMotivo"]) > 0){
		return $objResponse->alert("Solo se puede agregar un motivo");
	}
	foreach($frmNotaCredito["hddIdMotivo"] as $indice => $valor){
		if($idMotivo == $valor){
			return $objResponse->alert("El motivo seleccionado ya se encuentra agregado");
		}
	}
	
	$objResponse->loadCommands(insertarItemMotivo($idMotivo));		
	
	return $objResponse;
}

function asignarPorcentajeTarjetaCredito($idCuenta, $idTarjeta) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT porcentaje_comision, porcentaje_islr FROM te_retencion_punto
	WHERE id_cuenta = %s
		AND id_tipo_tarjeta = %s",
		valTpDato($idCuenta, "int"),
		valTpDato($idTarjeta, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("porcentajeRetencion","value",$row['porcentaje_islr']);
	$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
	
	$objResponse->script("calcularPorcentajeTarjetaCredito();");
	
	return $objResponse;
}

function buscarBanco($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarBanco']);
	
	$objResponse->loadCommands(listaBanco(0, "idBanco", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listaEmpresa(0, "id_empresa_reg", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarMotivo($frmBuscarMotivo){
	$objResponse = new xajaxResponse;
	
	$valBusq = sprintf("%s",
		$frmBuscarMotivo['txtCriterioBuscarMotivo']);
	
	$objResponse->loadCommands(listaMotivo(0, "id_motivo", "DESC", $valBusq));
						
	return $objResponse;
}

function buscarNotaCredito($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",		
		$valForm['lstEmpresa'],
		$valForm['lstEstado'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaNotaCredito(0, "id_nota_credito", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstCuenta($idEmpresa, $idBanco, $selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM cuentas WHERE id_empresa = %s AND idBanco = %s",
		valTpDato($idEmpresa, "int"),
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstCuenta\" name=\"lstCuenta\" onchange=\"xajax_cargaSaldoCuenta(this.value)\" class=\"inputHabilitado\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)){
		$selected = ($selId == $row['idCuentas']) ? "selected=\"selected\"" : "";
		$html .= "<option ".$selected." value=\"".$row['idCuentas']."\">".$row['numeroCuentaCompania']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdLstCuenta","innerHTML",$html);
	
	if ($selId > 0) {
		$objResponse->script("
		byId('lstCuenta').onchange = function () {
			selectedOption(this.id,".$selId.");
		}");
		$objResponse->script("byId('lstCuenta').className = \"inputInicial\"");
	}
	
	return $objResponse;
}

function cargaLstEstado(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_estados_principales ORDER BY id_estados_principales");
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstEstado\" name=\"lstEstado\" onChange=\"xajax_buscarNotaCredito(xajax.getFormValues('frmBuscar'))\" class=\"inputHabilitado\">";
	$html .="<option selected=\"selected\" value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_estados_principales']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdLstEstado","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoDcto($selId = ""){
	$objResponse = new xajaxResponse();
	
	if($selId > 0){
		$class = "class=\"inputInicial\"";
		$camposIn = $selId;
	}else{
		$class = "class=\"inputHabilitado\"";
		$camposIn = "1,2";	// 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada	
	}
	
	$query = sprintf("SELECT * FROM te_estados_principales WHERE id_estados_principales IN (%s)",
		valTpDato($camposIn, "campo"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstEstadoDcto\" name=\"lstEstadoDcto\" ".$class.">";
	$html .="<option selected=\"selected\" value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_estados_principales']) ? "selected=\"selected\"" : "";
		$html .= "<option ".$selected." value=\"".$row['id_estados_principales']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdLstEstadoDcto","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTarjetaCuenta($idCuenta, $tipoPago, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($tipoPago == 3) { // Tarjeta de Credito
		$query = sprintf("SELECT idTipoTarjetaCredito, descripcionTipoTarjetaCredito FROM tipotarjetacredito 
		WHERE idTipoTarjetaCredito IN (SELECT id_tipo_tarjeta FROM te_retencion_punto
										WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta NOT IN (6))
		ORDER BY descripcionTipoTarjetaCredito",
			valTpDato($idCuenta, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select id=\"tarjeta\" name=\"tarjeta\" onchange=\"xajax_asignarPorcentajeTarjetaCredito(".$idCuenta.",this.value)\" style=\"width:120px\" class=\"inputHabilitado\">";
			$html .= "<option value=\"\">[ Seleccione ]</option>";
		while($row = mysql_fetch_assoc($rs)) {
			$selected = ($selId == $row['idTipoTarjetaCredito'] || $totalRows == 1) ? "selected=\"selected\"" : "";
			if ($totalRows == 1) { $objResponse->loadCommands(asignarPorcentajeTarjetaCredito($idCuenta, $row["idTipoTarjetaCredito"])); }
			
			$html .= "<option ".$selected." value=\"".$row['idTipoTarjetaCredito']."\">".$row['descripcionTipoTarjetaCredito']."</option>";
		}
		$html .= "</select>";
		
		
	} else if ($tipoPago == 2) { // Tarjeta de Debito
		$query = sprintf("SELECT porcentaje_comision FROM te_retencion_punto WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta IN (6);",
			valTpDato($idCuenta,'int'));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("porcentajeComision","value",$row['porcentaje_comision']);
		$objResponse->script("calcularPorcentajeTarjetaCredito();");
	}else{
	
	}
	
	$objResponse->assign("tdtarjeta","innerHTML",$html);
	
	return $objResponse;
}

function cargaSaldoCuenta($idCuenta){
	$objResponse = new xajaxResponse();

	$queryCuenta = sprintf("SELECT * FROM cuentas WHERE idCuentas = %s",
		valTpDato($idCuenta, "int"));
	$rsCuenta = mysql_query($queryCuenta);
	if(!$rsCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuenta = mysql_fetch_assoc($rsCuenta);
	
	$objResponse->assign("txtSaldoCuenta","value",number_format($rowCuenta['saldo_tem'],'2','.',','));
	
	return $objResponse;
}

function formNotaCredito($idNotaCredito){
	$objResponse = new xajaxResponse();
	
	if ($idNotaCredito) {	
		$queryConsulta = sprintf("SELECT 
			nota_credito.id_nota_credito,
			nota_credito.id_numero_cuenta,
			nota_credito.fecha_registro,
			nota_credito.fecha_aplicacion,
			nota_credito.fecha_conciliacion,
			nota_credito.folio_tesoreria,
			nota_credito.id_beneficiario_proveedor,
			nota_credito.observaciones,
			nota_credito.folio_estado_cuenta_banco,
			nota_credito.estado_documento,
			nota_credito.origen,
			nota_credito.id_usuario,
			nota_credito.monto_nota_credito,
			nota_credito.control_beneficiario_proveedor,
			nota_credito.id_empresa,
			nota_credito.desincorporado,
			nota_credito.numero_nota_credito,
			nota_credito.tipo_nota_credito,
			nota_credito.id_motivo,
			(CASE nota_credito.tipo_nota_credito
				WHEN 1 THEN 'Normal'
				WHEN 2 THEN 'Tarjeta Débito'
				WHEN 3 THEN 'Tarjeta Crédito'
				WHEN 4 THEN 'Transferencia'
			END) AS descripcion_tipo_nota_credito,
			cuenta.idCuentas,
			cuenta.id_empresa,
			cuenta.numeroCuentaCompania,
			banco.idBanco,
			banco.nombreBanco,
			cuenta.saldo_tem,
			banco.telf,
			banco.email,
			te_estados_principales.id_estados_principales,
			te_estados_principales.descripcion AS descripcion_estado,
			te_origen.descripcion AS descripcion_origen,
			motivo.id_motivo,
			motivo.descripcion AS descripcion_motivo,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM te_nota_credito nota_credito
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (nota_credito.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN cuentas cuenta ON (nota_credito.id_numero_cuenta = cuenta.idCuentas)
			INNER JOIN bancos banco ON (cuenta.idBanco = banco.idBanco)			
			INNER JOIN te_estados_principales ON (nota_credito.estado_documento = te_estados_principales.id_estados_principales)
			INNER JOIN te_origen ON (nota_credito.origen = te_origen.id)
			LEFT JOIN pg_motivo motivo ON (nota_credito.id_motivo = motivo.id_motivo)
		WHERE nota_credito.id_nota_credito = %s",
			valTpDato($idNotaCredito, "int"));
		$rsConsulta = mysql_query($queryConsulta);
		if(!$rsConsulta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$rowConsulta = mysql_fetch_assoc($rsConsulta);
		
		$fechaConciliacion = ($rowConsulta['fecha_conciliacion'] != "") ? date(spanDateFormat,strtotime($rowConsulta['fecha_conciliacion'])) : "";
		
		$objResponse->assign("txtNombreEmpresa","value",utf8_encode($rowConsulta['nombre_empresa']));
		$objResponse->assign("txtNombreBanco","value",utf8_encode($rowConsulta['nombreBanco']));
		$objResponse->assign("txtCuentasConsulta","value",utf8_encode($rowConsulta['numeroCuentaCompania']));
		$objResponse->assign("txtTelefonoBanco","value",utf8_encode($rowConsulta['telf']));
		$objResponse->assign("txtEmailBanco","value",utf8_encode($rowConsulta['email']));
		$objResponse->assign("txtFechaRegistro","value",date(spanDateFormat,strtotime($rowConsulta['fecha_registro'])));
		$objResponse->assign("txtFechaAplicacion","value",date(spanDateFormat,strtotime($rowConsulta['fecha_aplicacion'])));
		$objResponse->assign("txtFechaConciliacion","value",$fechaConciliacion);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowConsulta['observaciones']));
		$objResponse->assign("txtOrigenNotaCredito","value",utf8_encode($rowConsulta['descripcion_origen']));
		$objResponse->assign("txtImporteMovimiento","value",number_format($rowConsulta['monto_nota_credito'],'2','.',','));
		$objResponse->assign("txtNumeroDocumentoConsulta","value",utf8_encode($rowConsulta['numero_nota_credito']));
		$objResponse->assign("selTipoNotaCredito","value",$rowConsulta['tipo_nota_credito']);
		
		$objResponse->loadCommands(cargaLstEstadoDcto($rowConsulta['id_estados_principales']));		
		$objResponse->loadCommands(cargaLstCuenta($rowConsulta["id_empresa"], $rowConsulta["idBanco"], $rowConsulta["idCuentas"]));

		$queryMotivo = sprintf("SELECT 
			det_motivo.id_nota_credito_detalle_motivo,
			det_motivo.id_motivo,
			det_motivo.precio_unitario
		FROM te_nota_credito_detalle_motivo det_motivo
		WHERE det_motivo.id_nota_credito = %s",
			valTpDato($idNotaCredito, "int"));									  
		$rsMotivo = mysql_query($queryMotivo);
		if(!$rsMotivo){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		while($rowMotivo = mysql_fetch_assoc($rsMotivo)){
			$objResponse->loadCommands(insertarItemMotivo($rowMotivo['id_motivo'], $rowMotivo['id_nota_credito_detalle_motivo'], $rowMotivo['precio_unitario']));
		}
		
		$objResponse->script("
			byId('txtSaldoCuenta').className = 'inputInicial';
			byId('txtNombreBanco').className = 'inputInicial';			
			byId('txtFechaRegistro').className = 'inputInicial';
			byId('txtFechaAplicacion').className = 'inputInicial';
			byId('txtNumeroDocumento').className = 'inputInicial';
			byId('selTipoNotaCredito').className = 'inputInicial';
			byId('txtObservacion').className = 'inputInicial';
			byId('txtImporteMovimiento').className = 'inputInicial';");
			
		$objResponse->script("byId('btnGuardar').style.display = 'none';
			byId('aListarEmpresa').style.display = 'none';
			byId('aListarBanco').style.display = 'none';
			byId('aAgregarMotivo').style.display = 'none';
			byId('btnQuitarMotivo').style.display = 'none';");
	} else {
		$objResponse->assign("txtFechaRegistro","value",date(spanDateFormat));
		$objResponse->assign("txtOrigenNotaCredito","value","Tesorería");
		
		$objResponse->script("mostrarTarjetas();");
		$objResponse->loadCommands(asignarEmpresa($_SESSION['idEmpresaUsuarioSysGts']));
		$objResponse->loadCommands(cargaLstCuenta());
		$objResponse->loadCommands(cargaLstEstadoDcto());		
		
		$objResponse->script("
			byId('txtSaldoCuenta').className = 'inputInicial';
			byId('txtNombreBanco').className = 'inputInicial';			
			byId('txtFechaRegistro').className = 'inputHabilitado';
			byId('txtFechaAplicacion').className = 'inputHabilitado';
			byId('txtNumeroDocumento').className = 'inputHabilitado';
			byId('selTipoNotaCredito').className = 'inputHabilitado';
			byId('txtObservacion').className = 'inputHabilitado';
			byId('txtImporteMovimiento').className = 'inputHabilitado';");	

		$objResponse->script("byId('btnGuardar').style.display = '';
		byId('aListarEmpresa').style.display = '';
		byId('aListarBanco').style.display = '';
		byId('aAgregarMotivo').style.display = '';
		byId('btnQuitarMotivo').style.display = '';");
	}
	
	return $objResponse;
}

function guardarNotaCredito($valForm){
	$objResponse = new xajaxResponse();	
	
	$objResponse->script("desbloquearGuardado();");
	
	if (!xvalidaAcceso($objResponse,"te_nota_credito","insertar")){ return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$queryFolio = sprintf("SELECT * FROM te_folios WHERE id_folios = 3");
	$rsFolio = mysql_query($queryFolio);
	if (!$rsFolio) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
	$rowFolio = mysql_fetch_assoc($rsFolio);
		
	$numeroFolio = $rowFolio['numero_actual'];
	
	$queryFoliosUpdate = sprintf("UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = 3");
	$rsFoliosUpdate = mysql_query($queryFoliosUpdate);
	if (!$rsFoliosUpdate) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
	
	$montoBase = ($valForm['montoBase'] != "") ? $valForm['montoBase'] : 0;
	$porcentajeComision = ($valForm['porcentajeComision'] != "") ? $valForm['porcentajeComision'] : 0;
	$porcentajeRetencion = ($valForm['porcentajeRetencion'] != "") ? $valForm['porcentajeRetencion'] : 0;

	$query = sprintf ("INSERT INTO te_nota_credito (id_numero_cuenta, fecha_registro, folio_tesoreria, id_beneficiario_proveedor, observaciones, fecha_aplicacion, estado_documento, origen, id_usuario, monto_nota_credito, id_empresa, desincorporado, numero_nota_credito, tipo_nota_credito, id_motivo, monto_original_nota_credito, porcentaje_comision, porcentaje_islr)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($valForm['lstCuenta'], "int"),
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
		valTpDato($numeroFolio, "int"),
		valTpDato(0, "int"),// 0 = Proveedor, 1 = Beneficiario
		valTpDato($valForm['txtObservacion'], "text"),
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaAplicacion'])), "date"),
		valTpDato($valForm['lstEstadoDcto'], "int"),// 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada
		valTpDato(0, "int"),// 0 = Tesoreria, 1 = Caja Veh, 2 = Caja RyS, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($valForm['txtImporteMovimiento'], "real_inglesa"),
		valTpDato($valForm['hddIdEmpresa'], "int"),
		valTpDato(1, "int"),// 0 = desincorporado, 1 = normal
		valTpDato($valForm['txtNumeroDocumento'], "text"),
		valTpDato($valForm['selTipoNotaCredito'], "int"),
		valTpDato($valForm['hddIdMotivo'][0], "int"),
		valTpDato($montoBase, "real_inglesa"),
		valTpDato($porcentajeComision, "real_inglesa"),
		valTpDato($porcentajeRetencion, "real_inglesa"));
	mysql_query("SET NAMES 'utf8';");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$queryEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales) 
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d h:i:s",strtotime($valForm['txtFechaAplicacion'])), "date"),
		valTpDato($valForm['lstCuenta'], "int"),
		valTpDato($valForm['hddIdEmpresa'], "int"),
		valTpDato($valForm['txtImporteMovimiento'], "real_inglesa"),
		valTpDato(1, "int"),// 0 = resta, 1 = suma
		valTpDato($valForm['txtNumeroDocumento'], "text"),
		valTpDato(1, "int"),// 0 = desincorporado, 1 = normal
		valTpDato($valForm['txtObservacion'], "text"),
		valTpDato($valForm['lstEstadoDcto'], "int"));// 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada
	mysql_query("SET NAMES 'utf8';");
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
	mysql_query("SET NAMES 'latin1';");

	foreach($valForm["hddIdMotivo"] as $indice => $valor){
		$queryMotivo = sprintf("INSERT INTO te_nota_credito_detalle_motivo (id_nota_credito, id_motivo, precio_unitario) 
		VALUES (%s, %s, %s)",
			valTpDato($idNotaCredito, "int"),
			valTpDato($valForm['hddIdMotivo'][$indice], "int"),
			valTpDato($valForm['txtPrecioItm'][$indice], "real_inglesa"));		
		$rsMotivo = mysql_query($queryMotivo);
		if (!$rsMotivo) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
	}
	
	$querySaldoCuenta = sprintf("SELECT * FROM cuentas WHERE idCuentas = %s",
		valTpDato($valForm['lstCuenta'], "int"));
	$rsSaldoCuenta = mysql_query($querySaldoCuenta);
	if (!$rsSaldoCuenta) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
	$rowSaldoCuenta = mysql_fetch_assoc($rsSaldoCuenta);
	
	$restoCuenta = $rowSaldoCuenta['saldo_tem'] + str_replace(",","",$valForm['txtImporteMovimiento']);
	
	$queryCuentaActualiza = sprintf("UPDATE cuentas SET saldo_tem = %s WHERE idCuentas = %s", 
		valTpDato($restoCuenta, "real_inglesa"),
		valTpDato($valForm['lstCuenta'], "int"));
	$rsCuentaActualiza = mysql_query($queryCuentaActualiza);
	if (!$rsCuentaActualiza) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Los Datos se han Guardado Correctamente");	
	$objResponse->script("byId('btnBuscar').click();
	byId('btnCancelar').click();");
	
	//Modifcar Ernesto
	if(function_exists("generarNotaCreditoTe")){
	   generarNotaCreditoTe($idNotaCredito,"","");
	}
	//Modifcar Ernesto

	return $objResponse;
}

function insertarItemMotivo($idMotivo, $hddIdNotaCreditoDet = "", $precioUnitario = "") {
	$objResponse = new xajaxResponse();
	
	$aClassReadonly = ($hddIdNotaCreditoDet > 0) ? "class=\"inputSinFondo\" readonly=\"readonly\"" : "class=\"inputCompletoHabilitado\"";
	$aEliminar = ($hddIdNotaCreditoDet > 0) ? "" :
		sprintf("<a onclick=\"eliminarMotivo(this);\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>");
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryMotivo = sprintf("SELECT motivo.*,
		(CASE motivo.modulo
			WHEN 'CC' THEN	'Cuentas por Cobrar'
			WHEN 'CP' THEN	'Cuentas por Pagar'
			WHEN 'CJ' THEN	'Caja'
			WHEN 'TE' THEN	'Tesorería'
		END) AS descripcion_modulo_transaccion,
		
		(CASE motivo.ingreso_egreso
			WHEN 'I' THEN	'Ingreso'
			WHEN 'E' THEN	'Egreso'
		END) AS descripcion_tipo_transaccion
	FROM pg_motivo motivo WHERE id_motivo = %s;",
		valTpDato($idMotivo, "int"));
	$rsMotivo = mysql_query($queryMotivo);
	if (!$rsMotivo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsMotivo = mysql_num_rows($rsMotivo);
	$rowMotivo = mysql_fetch_assoc($rsMotivo);
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr align=\"left\" class=\"textoGris_11px trItemMotivo\">".
			"<td align=\"center\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\"/></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm[]\" name=\"txtPrecioItm[]\" %s onkeypress=\"return validarSoloNumerosReales(event);\" onblur=\"setFormatoRafk(this,2);\" style=\"text-align:right\" tabindex=\"1\" value=\"%s\"></td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdNotaCreditoDet[]\" name=\"hddIdNotaCreditoDet[]\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdMotivo[]\" name=\"hddIdMotivo[]\" readonly=\"readonly\" value=\"%s\"></td>".
		"</tr>');",
		$rowMotivo['id_motivo'],
		$rowMotivo['descripcion'],
		$rowMotivo['descripcion_modulo_transaccion'],
		$rowMotivo['descripcion_tipo_transaccion'],
		$aClassReadonly, number_format($precioUnitario,2,".",","),
		$aEliminar,
		$hddIdNotaCreditoDet,
		$idMotivo);
	
	$objResponse->script($htmlItmPie);
	
	return $objResponse;
}

function listaBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("banco.idBanco != 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("banco.nombreBanco LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		banco.idBanco, 
		banco.nombreBanco, 
		banco.sucursal 
	FROM bancos banco
		INNER JOIN cuentas cuenta ON (cuenta.idBanco = banco.idBanco) %s GROUP BY banco.idBanco", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"5%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaBanco", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'><button type=\"button\" onclick=\"xajax_asignarBanco('".$row['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/select.png\"/></button></td>";
			$htmlTb .= "<td align=\"center\">".$row['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['sucursal'])."</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaBanco(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
				
	$objResponse->assign("tdListaBanco","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("usuario_empresa.id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre_empresa LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}

	$query = sprintf("SELECT 
		usuario_empresa.id_empresa_reg,
		CONCAT_WS(' - ', usuario_empresa.nombre_empresa, usuario_empresa.nombre_empresa_suc) AS nombre_empresa
		FROM vw_iv_usuario_empresa usuario_empresa %s", $sqlBusq);
	
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
		$htmlTh .= "<td width='5%'></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$row['id_empresa_reg']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/select.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}		
		
	$objResponse->assign("tdListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
			
	$objResponse->script("byId('txtNombreBanco').value = '';
						byId('txtSaldoCuenta').value = '';");	
	return $objResponse;
}

function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo = 'TE' AND ingreso_egreso = 'I'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT motivo.*,
		(CASE motivo.modulo
			WHEN 'CC' THEN	'Cuentas por Cobrar'
			WHEN 'CP' THEN	'Cuentas por Pagar'
			WHEN 'CJ' THEN	'Caja'
			WHEN 'TE' THEN	'Tesorería'
		END) AS descripcion_modulo_transaccion,
		
		(CASE motivo.ingreso_egreso
			WHEN 'I' THEN	'Ingreso'
			WHEN 'E' THEN	'Egreso'
		END) AS descripcion_tipo_transaccion
	FROM pg_motivo motivo %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "10%", $pageNum, "id_motivo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "54%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo");
		$htmlTh .= ordenarCampo("xajax_listaMotivo", "16%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Transacción");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['modulo']) {
			case "CC" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("CxC")."\"/>"; break;
			case "CP" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"".utf8_encode("CxP")."\"/>"; break;
			case "CJ" : break;
			case "TE" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_tesoreria.gif\" title=\"".utf8_encode("Tesorería")."\"/>"; break;
			default : $imgPedidoModulo = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" onclick=\"xajax_asignarMotivo(%s, xajax.getFormValues('frmNotaCredito'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$row['id_motivo']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".($row['descripcion_modulo_transaccion'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".($row['descripcion_tipo_transaccion'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMotivo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("tdListaMotivo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("nota_credito.desincorporado != 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nota_credito.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
			
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nota_credito.estado_documento = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(nota_credito.fecha_registro) BETWEEN %s AND %s ",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "date")); 
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nota_credito.id_nota_credito LIKE %s 
		OR nota_credito.observaciones LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		nota_credito.id_nota_credito,
		nota_credito.id_numero_cuenta,
		nota_credito.fecha_registro,
		nota_credito.fecha_aplicacion,
		nota_credito.fecha_conciliacion,
		nota_credito.folio_tesoreria,
		nota_credito.id_beneficiario_proveedor,
		nota_credito.observaciones,
		nota_credito.folio_estado_cuenta_banco,
		nota_credito.estado_documento,
		nota_credito.origen,
		nota_credito.id_usuario,
		nota_credito.monto_nota_credito,
		nota_credito.control_beneficiario_proveedor,
		nota_credito.id_empresa,
		nota_credito.desincorporado,
		nota_credito.numero_nota_credito,
		nota_credito.tipo_nota_credito,
		cuenta.idCuentas,
		cuenta.idBanco,
		cuenta.numeroCuentaCompania,
		banco.idBanco,
		banco.nombreBanco,
		
		(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR '<br>')
		FROM te_nota_credito_detalle_motivo te_nc_det_motivo
			INNER JOIN pg_motivo motivo ON (te_nc_det_motivo.id_motivo = motivo.id_motivo)
		WHERE te_nc_det_motivo.id_nota_credito = nota_credito.id_nota_credito) AS descripcion_motivo,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		
	FROM te_nota_credito nota_credito
		INNER JOIN cuentas cuenta ON (nota_credito.id_numero_cuenta = cuenta.idCuentas)
		INNER JOIN bancos banco ON (cuenta.idBanco = banco.idBanco)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (nota_credito.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		LEFT JOIN pg_motivo motivo ON (nota_credito.id_motivo = motivo.id_motivo) %s", $sqlBusq);		
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
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "1%", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "1%", $pageNum, "origen", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "7%", $pageNum, "id_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota Cr&eacute;dito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "5%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "5%", $pageNum, "fecha_aplicacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "5%", $pageNum, "fecha_conciliacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Conciliaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "15%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "15%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuenta");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "30%", $pageNum, "observaciones", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "", $pageNum, "monto_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= "<td colspan=\"4\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
        $fechaConciliacion = ($row['fecha_conciliacion'] != "") ? date(spanDateFormat,strtotime($row['fecha_conciliacion'])) : "";
		
		switch($row['estado_documento']){
			case 1: $imgEstado = "<img src=\"../img/iconos/ico_rojo.gif\">"; break;
			case 2: $imgEstado = "<img src=\"../img/iconos/ico_amarillo.gif\">"; break;
			case 3: $imgEstado = "<img src=\"../img/iconos/ico_verde.gif\">"; break;
			default : $imgEstado = "";
		}
		
		switch($row['origen']){
			case 0: $imgOrigen = "<img src=\"../img/iconos/ico_tesoreria.gif\">"; break;
			case 1: $imgOrigen = "<img src=\"../img/iconos/ico_caja_vehiculo.gif\">"; break;
			case 2: $imgOrigen = "<img src=\"../img/iconos/ico_caja_rs.gif\">"; break;
			default : $imgOrigen = "";
		}
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgEstado."</td>";
			$htmlTb .= "<td align=\"center\">".$imgOrigen."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_nota_credito']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_aplicacion']))."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaConciliacion."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroCuentaCompania']."</td>";
			
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['observaciones'])."</td>";
				$htmlTb .= "</tr>";				
					$htmlTb .= ($row['descripcion_motivo'] != "") ? "<tr><td><span class=\"textoNegrita_9px\">".$row['descripcion_motivo']."</span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_nota_credito'],'2','.',',')."</td>";
			$htmlTb .= "<td align=\"center\"><a class=\"modalImg\" id=\"aNuevo\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, '', ".$row['id_nota_credito'].");\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" /></a></td>";
			if($row['estado_documento']==3 || $row['origen'] != 0){
				//$htmlTb .= "<td align=\"center\"><img class=\"puntero\")\" src=\"../img/iconos/ico_quitarf2.gif\" /></td>";
			}
			if($row['estado_documento']!=3 && $row['origen'] == 0){
				//$htmlTb .= "<td align=\"center\"><img class=\"puntero\")\" src=\"../img/iconos/ico_quitarf2.gif\" /></td>";
				//$htmlTb .= "<td align=\"center\"><img class=\"puntero\" onclick=\"xajax_eliminarNota(".$row['id_nota_credito'].")\" src=\"../img/iconos/ico_quitar.gif\" /></td>";
			}
			$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" onclick=\"verVentana('reportes/te_imprimir_nc_pdf.php?id=".$row['id_nota_credito']."',1100,600);\" src=\"../img/iconos/ico_print.png\"></td>";
			// Modificado Ernesto
			$sPar = "idobject=".$row['id_nota_credito'];
				 $sPar.= "&ct=07";
				 $sPar.= "&dt=03";
				 $sPar.= "&cc=05";
			// Modificado Ernesto
			$htmlTb .= "<td  align=\"center\">";
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?$sPar', 1000, 500);\" src=\"../img/iconos/new_window.png\" title=\"Ver Movimiento Contable\"/>";
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaNotaCredito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"asignarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarBanco");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"cargaLstTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaSaldoCuenta");
$xajax->register(XAJAX_FUNCTION,"cargarDatos");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstado");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoDcto");
$xajax->register(XAJAX_FUNCTION,"formNotaCredito");
$xajax->register(XAJAX_FUNCTION,"guardarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"listaBanco");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaNotaCredito");

?>