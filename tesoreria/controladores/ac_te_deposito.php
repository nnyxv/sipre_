<?php

function actualizarMonto($valForm){
	$objResponse = new xajaxResponse();

	$montoTotal = str_replace(",","",$valForm['txtTotalCheques']) + str_replace(",","",$valForm['txtTotalEfectivo']);
	
	$objResponse->assign("txtTotalDeposito","value",number_format($montoTotal, 2, ".", ","));

	return $objResponse;
}

function actualizarObjetosExistentes($valForm,$valFormCheques){
	$objResponse = new xajaxResponse();	 
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS	*/
	for ($cont = 0; $cont <= strlen($valForm['hddObj']); $cont++){
		$caracter = substr($valForm['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;				
		}else{			
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}

	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($valForm['hddMontoCheques'.$valor]))
			$cadena .= "|".$valor;
	}
	
	$objResponse->assign("hddObj","value",$cadena);
	
	$cadena2 = $cadena;
	$cadena = "";
	for ($cont = 0; $cont <= strlen($cadena2); $cont++) {
		$caracter = substr($cadena2, $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
		}else{
			$arrayObj2[] = $cadena;
			$cadena = "";
		}	
	}
		
	foreach($arrayObj2 as $indice => $valor) {
		$montoCheques += str_replace(",","",$valForm["hddMontoCheques".$valor]);
	}

	$objResponse->assign("txtTotalCheques","value",number_format($montoCheques, 2, ".", ","));
	$objResponse->script("xajax_actualizarMonto(xajax.getFormValues('frmDeposito'));");
	
	return $objResponse;
}

function asignarBanco($idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_cargaLstCuenta(xajax.getFormValues('frmDeposito'))");
	$objResponse->script("byId('btnCancelarBanco').click();");
	
	return $objResponse;
}

function asignarBanco1($idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtBancoCheque","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBancoCheque","value",$row['idBanco']);
	$objResponse->script("byId('tblListaBanco1').style.display = 'none';
						byId('tblMontos').style.display = '';");
	
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

function asignarMotivo($idMotivo, $frmDeposito){
	$objResponse = new xajaxResponse();
	
	if(count($frmDeposito["hddIdMotivo"]) > 0){
		return $objResponse->alert("Solo se puede agregar un motivo");
	}
	foreach($frmDeposito["hddIdMotivo"] as $indice => $valor){
		if($idMotivo == $valor){
			return $objResponse->alert("El motivo seleccionado ya se encuentra agregado");
		}
	}
	
	$objResponse->loadCommands(insertarItemMotivo($idMotivo));		
	
	return $objResponse;
}

function buscarBanco($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarBanco']);
	
	$objResponse->loadCommands(listaBanco(0, "idBanco", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarBanco1($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarBanco1']);
	
	$objResponse->loadCommands(listaBanco1(0, "idBanco", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarDeposito($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",		
		$valForm['lstEmpresa'],
		$valForm['lstEstado'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaDeposito(0, "fecha_registro", "DESC", $valBusq));
	
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

function cargaLstCuenta($valForm){
	$objResponse = new xajaxResponse();
	
	if ($valForm['hddIdBanco'] == -1){
		$disabled = "disabled=\"disabled\"";
	}else{
		$disabled = "";
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idBanco = %s AND id_empresa = %s",
			valTpDato($valForm['hddIdBanco'], "int"),
			valTpDato($valForm['hddIdEmpresa'], "int"));
	}
	
	$queryCuentas = sprintf("SELECT * FROM cuentas %s", $sqlBusq);
	$rsCuentas = mysql_query($queryCuentas);
	if(!$rsCuentas){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstCuenta\" name=\"lstCuenta\" ".$disabled." onchange=\"xajax_cargaSaldoCuenta(this.value)\" class=\"inputHabilitado\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
		$html .= "<option value=\"".$rowCuentas['idCuentas']."\">".$rowCuentas['numeroCuentaCompania']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdLstCuenta","innerHTML",$html);
		
	return $objResponse;
}

function cargaLstEstado(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_estados_principales ORDER BY id_estados_principales");
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstEstado\" name=\"lstEstado\" onChange=\"xajax_buscarDeposito(xajax.getFormValues('frmBuscar'))\" class=\"inputHabilitado\">";
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

function eliminaElementos($valForm){
	$objResponse = new xajaxResponse();	
	
	if (isset($valForm['cbxItm'])) {
		foreach($valForm['cbxItm'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = byId('trItemCheque:%s');
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	
	$objResponse->script("xajax_actualizarObjetosExistentes(xajax.getFormValues('frmDeposito'),xajax.getFormValues('frmMonto'))");
			
	return $objResponse;	
}

function formAgregarCheques(){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
		document.forms['frmMonto'].reset();
		
		byId('txtBancoCheque').className = 'inputInicial';
		byId('hddIdBancoCheque').className = 'inputInicial';
		byId('txtNumeroCuentaCheque').className = 'inputHabilitado';
		byId('txtMontoCheque').className = 'inputHabilitado';
		byId('txtNumeroCheque').className = 'inputHabilitado';");
	
	return $objResponse;
}

function formDeposito($idDeposito){
	$objResponse = new xajaxResponse();
	
	if ($idDeposito > 0){
		$queryConsulta = sprintf("SELECT 
			te_depositos.id_deposito,
			te_depositos.id_numero_cuenta,
			te_depositos.fecha_registro,
			te_depositos.fecha_aplicacion,
			te_depositos.fecha_conciliacion,
			te_depositos.fecha_movimiento_banco,
			te_depositos.numero_deposito_banco,
			te_depositos.estado_documento,
			te_depositos.origen,
			te_depositos.id_usuario,
			te_depositos.monto_total_deposito,
			te_depositos.id_empresa,
			te_depositos.desincorporado,
			te_depositos.monto_efectivo,
			te_depositos.monto_cheques_total,
			te_depositos.observacion,
			te_depositos.folio_deposito,
			cuentas.idCuentas,
			cuentas.idBanco,
			cuentas.numeroCuentaCompania,
			bancos.idBanco,
			bancos.nombreBanco,
			cuentas.saldo_tem,
			te_estados_principales.id_estados_principales,
			te_estados_principales.descripcion,
			motivo.id_motivo,
			motivo.descripcion AS descripcion_motivo,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM te_depositos
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (te_depositos.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN cuentas ON (te_depositos.id_numero_cuenta = cuentas.idCuentas)
			INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
			INNER JOIN te_estados_principales ON (te_depositos.estado_documento = te_estados_principales.id_estados_principales)
			LEFT JOIN pg_motivo motivo ON (te_depositos.id_motivo = motivo.id_motivo)
		WHERE te_depositos.id_deposito = %s",
			valTpDato($idDeposito, "int"));									  
		$rsConsulta = mysql_query($queryConsulta);
		if(!$rsConsulta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$rowConsulta = mysql_fetch_assoc($rsConsulta);
		
		$fechaConciliacion = ($rowConsulta['fecha_conciliacion'] != "") ? date(spanDateFormat,strtotime($rowConsulta['fecha_conciliacion'])) : "";
		
		$objResponse->assign("txtNombreEmpresa","value",utf8_encode($rowConsulta['nombre_empresa']));
		$objResponse->assign("txtNombreBanco","value",utf8_encode($rowConsulta['nombreBanco']));
		$objResponse->assign("txtCuentaBanco","value",$rowConsulta['numeroCuentaCompania']);
		$objResponse->assign("txtFechaRegistro","value",date(spanDateFormat,strtotime($rowConsulta['fecha_registro'])));
		$objResponse->assign("txtFechaAplicacion","value",date(spanDateFormat,strtotime($rowConsulta['fecha_aplicacion'])));
		$objResponse->assign("txtFechaConciliacion","value",$fechaConciliacion);
		$objResponse->assign("txtNumeroPlanilla","value",$rowConsulta['numero_deposito_banco']);
		$objResponse->assign("txtObservacion","value",utf8_encode($rowConsulta['observacion']));
		$objResponse->assign("txtTotalEfectivo","value",number_format($rowConsulta['monto_efectivo'],'2','.',','));
		$objResponse->assign("txtTotalCheques","value",number_format($rowConsulta['monto_cheques_total'],'2','.',','));
		$objResponse->assign("txtTotalDeposito","value",number_format($rowConsulta['monto_total_deposito'],'2','.',','));
				
		$objResponse->loadCommands(listaCheques(0,'','',$rowConsulta['id_deposito']));
		$objResponse->loadCommands(cargaLstEstadoDcto($rowConsulta['id_estados_principales']));
		
		$queryMotivo = sprintf("SELECT 
			det_motivo.id_deposito_detalle_motivo,
			det_motivo.id_motivo,
			det_motivo.precio_unitario
		FROM te_depositos_detalle_motivo det_motivo
		WHERE det_motivo.id_deposito = %s",
			valTpDato($idDeposito, "int"));									  
		$rsMotivo = mysql_query($queryMotivo);
		if(!$rsMotivo){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		while($rowMotivo = mysql_fetch_assoc($rsMotivo)){
			$objResponse->loadCommands(insertarItemMotivo($rowMotivo['id_motivo'], $rowMotivo['id_deposito_detalle_motivo'], $rowMotivo['precio_unitario']));
		}
		
		$objResponse->script("byId('txtTotalEfectivo').readOnly = true;
			byId('txtTotalEfectivo').className = 'inputInicial';
			byId('txtFechaRegistro').className = 'inputInicial';
			byId('txtFechaAplicacion').className = 'inputInicial';
			byId('txtObservacion').readOnly = true;
			byId('txtObservacion').className = 'inputInicial';
			byId('txtNumeroPlanilla').readOnly = true;
			byId('txtNumeroPlanilla').className = 'inputInicial';
			byId('txtNombreBanco').className = 'inputInicial';
			byId('aAgregarMotivo').style.display = 'none';
			byId('btnQuitarMotivo').style.display = 'none';
			byId('tblCheques').style.display = 'none';
			byId('tdNumeroCuenta').style.display = '';
			byId('tdLstCuenta').style.display = 'none';
			byId('aListarEmpresa').style.display = 'none';
			byId('btnGuardar').style.display = 'none';						
			byId('aListarBanco').style.display = 'none';
			byId('tdListaCheques').style.display = '';");
	} else {
		/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
		for ($cont = 0; $cont <= strlen($valForm['hddObj']); $cont++){
			$caracter = substr($valForm['hddObj'], $cont, 1);
			
			if ($caracter != "|" && $caracter != ""){
				$cadena .= $caracter;
			}else{			
				$arrayObj[] = $cadena;
				$cadena = "";
			}	
		}
		
		foreach($arrayObj as $indiceItmRep => $valorItmRep) {
			if($valorItmRep != ""){
				$objResponse->script(sprintf("
					fila = byId('trItemCheque:%s');
					padre = fila.parentNode;
					padre.removeChild(fila);",
				$valorItmRep));
			}
		}
		
		$objResponse->assign("txtFechaRegistro","value",date(spanDateFormat));
		
		$objResponse->loadCommands(asignarEmpresa($_SESSION['idEmpresaUsuarioSysGts']));
		$objResponse->loadCommands(cargaLstCuenta());
		$objResponse->loadCommands(cargaLstEstadoDcto());		
		
		$objResponse->script("			
			byId('txtNombreBanco').className = 'inputInicial';
			byId('txtNombreEmpresa').className = 'inputInicial';
			byId('txtObservacion').className = 'inputHabilitado';
			byId('txtFechaRegistro').className = 'inputHabilitado';
			byId('txtFechaAplicacion').className = 'inputHabilitado';
			byId('txtTotalEfectivo').readOnly = false;
			byId('txtTotalEfectivo').className = 'inputHabilitado';
			byId('txtObservacion').readOnly = false;
			byId('txtNumeroPlanilla').readOnly = false;
			byId('txtNumeroPlanilla').className = 'inputHabilitado';
			byId('txtTotalDeposito').className = 'inputInicial';
			byId('txtSaldoCuenta').className = 'inputInicial';
			byId('tblCheques').style.display = '';
			byId('tdNumeroCuenta').style.display = 'none';
			byId('tdLstCuenta').style.display = '';
			byId('aListarEmpresa').style.display = '';
			byId('btnGuardar').style.display = '';
			byId('aListarBanco').style.display = '';
			byId('aAgregarMotivo').style.display = '';
			byId('btnQuitarMotivo').style.display = '';
			byId('tdListaCheques').style.display = 'none';");		
	}
	
	return $objResponse;
}

function guardarDeposito($valForm){
	$objResponse = new xajaxResponse();	
	
	$objResponse->script("desbloquearGuardado();");
	
	if (!xvalidaAcceso($objResponse,"te_deposito","insertar")) { return $objResponse; }
        
	mysql_query("START TRANSACTION;");
	
	//* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS	*/
	for ($cont = 1; $cont <= strlen($valForm['hddObj']); $cont++){
		$caracter = substr($valForm['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;				
		}else{			
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	$queryFolio = sprintf("SELECT * FROM te_folios WHERE id_folios = 2");
	$rsFolio = mysql_query($queryFolio);
	if (!$rsFolio) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
	$rowFolio = mysql_fetch_assoc($rsFolio);
		
	$numeroFolio = $rowFolio['numero_actual'];
	
	$queryFoliosUpdate = sprintf("UPDATE te_folios SET numero_actual = (numero_actual + 1) WHERE id_folios = 2");
	$rsFoliosUpdate = mysql_query($queryFoliosUpdate);
	if (!$rsFoliosUpdate) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
	
	$query = sprintf("INSERT INTO te_depositos (id_numero_cuenta, fecha_registro, fecha_aplicacion, numero_deposito_banco, estado_documento, origen, id_usuario, monto_total_deposito, id_empresa, desincorporado, monto_efectivo, monto_cheques_total, observacion, folio_deposito, id_motivo) 
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($valForm['lstCuenta'], "int"),
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaRegistro'])), "date"),
		valTpDato(date("Y-m-d",strtotime($valForm['txtFechaAplicacion'])), "date"),
		valTpDato($valForm['txtNumeroPlanilla'], "text"),
		valTpDato($valForm['lstEstadoDcto'], "int"),// 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada
		valTpDato(0, "int"),// 0 = Tesoreria, 1 = Caja Veh, 2 = Caja RyS, 3 = Ingreso por Bonificaciones, 4 = Otros Ingresos
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($valForm['txtTotalDeposito'], "real_inglesa"),
		valTpDato($valForm['hddIdEmpresa'], "int"),
		valTpDato(1, "int"),// 0 = desincorporado, 1 = normal
		valTpDato($valForm['txtTotalEfectivo'], "real_inglesa"),
		valTpDato($valForm['txtTotalCheques'], "real_inglesa"),
		valTpDato($valForm['txtObservacion'], "text"),
		valTpDato($numeroFolio, "int"),
		valTpDato($valForm['hddIdMotivo'][0], "int"));		
	mysql_query("SET NAMES 'utf8';");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$idDeposito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$queryEstadoCuenta = sprintf("INSERT INTO te_estado_cuenta (tipo_documento, id_documento, fecha_registro, id_cuenta, id_empresa, monto, suma_resta, numero_documento, desincorporado, observacion, estados_principales) 
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato("DP", "text"),
		valTpDato($idDeposito, "int"),
		valTpDato(date("Y-m-d h:i:s",strtotime($valForm['txtFechaAplicacion'])), "date"),
		valTpDato($valForm['lstCuenta'], "int"),
		valTpDato($valForm['hddIdEmpresa'], "int"),
		valTpDato($valForm['txtTotalDeposito'], "real_inglesa"),
		valTpDato(1, "int"),// 0 = resta, 1 = suma
		valTpDato($valForm['txtNumeroPlanilla'], "text"),
		valTpDato(1, "int"),// 0 = desincorporado, 1 = normal
		valTpDato($valForm['txtObservacion'], "text"),
		valTpDato($valForm['lstEstadoDcto'], "int"));// 1 = Por Aplicar, 2 = Aplicada, 3 = Conciliada
	mysql_query("SET NAMES 'utf8';");
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	if(isset($arrayObj)){
		foreach($arrayObj as $indice => $valor) {
			$queryDepositosCheques = sprintf("INSERT INTO te_deposito_detalle (id_deposito, id_banco, numero_cuenta_cliente, numero_cheques, monto) 
			VALUES (%s, %s, %s, %s, %s)",
				valTpDato($idDeposito, "int"),
				valTpDato($valForm['hddBancoCheque'.$valor], "int"),
				valTpDato($valForm['hddNumeroCuentaCheque'.$valor], "text"),
				valTpDato($valForm['hddNumeroCheque'.$valor], "text"),
				valTpDato($valForm['hddMontoCheques'.$valor], "real_inglesa"));			
			$rsDepositosCheques = mysql_query($queryDepositosCheques);
			if (!$rsDepositosCheques) return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__);
		}
	}
	
	foreach($valForm["hddIdMotivo"] as $indice => $valor){
		$queryMotivo = sprintf("INSERT INTO te_depositos_detalle_motivo (id_deposito, id_motivo, precio_unitario) 
		VALUES (%s, %s, %s)",
			valTpDato($idDeposito, "int"),
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
	
	$restoCuenta = $rowSaldoCuenta['saldo_tem'] + str_replace(",","",$valForm['txtTotalDeposito']);
	
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
	if(function_exists("generarDepositoTe")){
		generarDepositoTe($idDeposito,"","");
	}
	//Modifcar Ernesto
	
	return $objResponse;	
}

function insertarCheques($valForm, $valFormCheques) {
	$objResponse = new xajaxResponse();
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	for ($cont = 0; $cont <= strlen($valForm['hddObj']); $cont++) {
		$caracter = substr($valForm['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
		}else{
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	$sigValor = $arrayObj[count($arrayObj)-1] + 1;
	
	$objResponse->script(sprintf(""
		."var nuevoTr = \"<tr id='trItemCheque:%s' class='textoGris_11px' title= 'trItemCheque:%s'>".
			"<td align='center'><input id='cbxMonto' name='cbxItm[]' type='checkbox' value='%s'/></td>".
			"<td align='center'>%s<input id='hddBancoCheque%s' name='hddBancoCheque%s' type='hidden' value='%s' /></td>".
			"<td align='center'>%s<input id='hddNumeroCuentaCheque%s' name='hddNumeroCuentaCheque%s' type='hidden' value='%s' /></td>".
			"<td align='center'>%s<input id='hddNumeroCheque%s' name='hddNumeroCheque%s' type='hidden' value='%s' /></td>".
			"<td align='right'>%s<input id='hddMontoCheques%s' name='hddMontoCheques%s' type='hidden'  value='%s' /></td>".
		"</tr>\";".
	"$(nuevoTr).insertBefore('#trMontosDepositos');",
	$sigValor, $sigValor,
	$sigValor,
	$valFormCheques['txtBancoCheque'],$sigValor, $sigValor, $valFormCheques['hddIdBancoCheque'],
	$valFormCheques['txtNumeroCuentaCheque'],$sigValor, $sigValor, $valFormCheques['txtNumeroCuentaCheque'],
	$valFormCheques['txtNumeroCheque'],$sigValor, $sigValor, $valFormCheques['txtNumeroCheque'],
	number_format(str_replace(",","",$valFormCheques['txtMontoCheque']), 2, ".", ","),$sigValor, $sigValor, str_replace(",","",$valFormCheques['txtMontoCheque'])
	));	
	
	$arrayObj[] = $sigValor;
	foreach($arrayObj as $indice => $valor) {
		$cadena = $valForm['hddObj']."|".$valor;
		$montoCheques += str_replace(",","",$valForm['hddMontoCheques'.$valor]); 
	}
	
	$montoCheques += str_replace(",","",$valFormCheques['txtMontoCheque']); 	
	$montoTotal = $montoCheques + str_replace(",","",$valForm['txtTotalEfectivo']);
	
	$objResponse->assign("hddObj","value",$cadena);
	$objResponse->assign("txtTotalCheques","value",number_format($montoCheques, 2, ".", ","));
	$objResponse->assign("txtTotalDeposito","value",number_format($montoTotal, 2, ".", ","));
	
	$objResponse->script("byId('btnCancelarCheque').click();");
	
	return $objResponse;
}

function insertarItemMotivo($idMotivo, $hddIdDepositoDet = "", $precioUnitario = "") {
	$objResponse = new xajaxResponse();
	
	$aClassReadonly = ($hddIdDepositoDet > 0) ? "class=\"inputSinFondo\" readonly=\"readonly\"" : "class=\"inputCompletoHabilitado\"";
	$aEliminar = ($hddIdDepositoDet > 0) ? "" :
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
				"<input type=\"hidden\" id=\"hddIdDepositoDet[]\" name=\"hddIdDepositoDet[]\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdMotivo[]\" name=\"hddIdMotivo[]\" readonly=\"readonly\" value=\"%s\"></td>".
		"</tr>');",
		$rowMotivo['id_motivo'],
		$rowMotivo['descripcion'],
		$rowMotivo['descripcion_modulo_transaccion'],
		$rowMotivo['descripcion_tipo_transaccion'],
		$aClassReadonly, number_format($precioUnitario,2,".",","),
		$aEliminar,
		$hddIdDepositoDet,
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

function listaBanco1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
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
	FROM bancos banco %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaBanco1", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco1", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco1", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'><button type=\"button\" onclick=\"xajax_asignarBanco1('".$row['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/select.png\"/></button></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaBanco1(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco1(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("tdListaBanco1","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaCheques($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 35, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_deposito_detalle.id_deposito = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$queryBanco = sprintf("SELECT 
		bancos.nombreBanco,
		bancos.idBanco,
		te_deposito_detalle.id_deposito_detalle,
		te_deposito_detalle.id_deposito,
		te_deposito_detalle.id_banco,
		te_deposito_detalle.numero_cuenta_cliente,
		te_deposito_detalle.numero_cheques,
		te_deposito_detalle.monto
	FROM te_deposito_detalle
		INNER JOIN bancos ON (te_deposito_detalle.id_banco = bancos.idBanco) %s", $sqlBusq);
	$rsBanco = mysql_query($queryBanco);
	if(!$rsBanco){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitBanco = sprintf("%s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);        
	
	$rsLimitBanco = mysql_query($queryLimitBanco);
	if(!$rsLimitBanco){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco);
		if(!$rsBanco){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsBanco);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";        
        $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";		
		$htmlTh .= ordenarCampo("xajax_listaCheques", "25%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "25%", $pageNum, "numero_cuenta_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Cuenta");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "25%", $pageNum, "numero_cheques", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Cheque");
		$htmlTh .= ordenarCampo("xajax_listaCheques", "25%", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Cheque");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowBanco = mysql_fetch_assoc($rsLimitBanco)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".$rowBanco['nombreBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['numero_cuenta_cliente']."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['numero_cheques']."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($rowBanco['monto'],'2','.',',')."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCheques(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheques(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("tdListaCheques","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);		
		
	return $objResponse;
}

function listaDeposito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_depositos.desincorporado != 0");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_depositos.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
			
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_depositos.estado_documento = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(te_depositos.fecha_registro) BETWEEN %s AND %s ",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "date")); 
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(te_depositos.numero_deposito_banco LIKE %s 
		OR te_depositos.observacion LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		te_depositos.id_deposito,
		te_depositos.id_numero_cuenta,
		te_depositos.fecha_registro,
		te_depositos.fecha_aplicacion,
		te_depositos.fecha_conciliacion,
		te_depositos.fecha_movimiento_banco,
		te_depositos.numero_deposito_banco,
		te_depositos.estado_documento,
		te_depositos.origen,
		te_depositos.id_usuario,
		te_depositos.monto_total_deposito,
		te_depositos.id_empresa,
		te_depositos.desincorporado,
		te_depositos.monto_efectivo,
		te_depositos.monto_cheques_total,
		te_depositos.observacion,
		te_depositos.folio_deposito,       
		
		(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR '<br>')
		FROM te_depositos_detalle_motivo te_depo_det_motivo
			INNER JOIN pg_motivo motivo ON (te_depo_det_motivo.id_motivo = motivo.id_motivo)
		WHERE te_depo_det_motivo.id_deposito = te_depositos.id_deposito) AS descripcion_motivo,
		
		cuentas.numeroCuentaCompania,
		bancos.nombreBanco,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		
	FROM te_depositos
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (te_depositos.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN cuentas ON te_depositos.id_numero_cuenta = cuentas.idCuentas
		INNER JOIN bancos ON cuentas.idBanco = bancos.idBanco
		LEFT JOIN pg_motivo ON te_depositos.id_motivo = pg_motivo.id_motivo %s ", $sqlBusq);		
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "1%", $pageNum, "estado_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "1%", $pageNum, "origen", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "1%", $pageNum, "numero_deposito_banco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dep&oacute;sito");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "5%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "5%", $pageNum, "fecha_aplicacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "5%", $pageNum, "fecha_conciliacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Conciliaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "15%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco Compa&ntilde;ia");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "15%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuenta Compa&ntilde;ia");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "30%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "", $pageNum, "monto_total_deposito", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowDeposito = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
        $fechaConciliacion = ($rowDeposito['fecha_conciliacion'] != "") ? date(spanDateFormat,strtotime($rowDeposito['fecha_conciliacion'])) : "";
		
		switch($rowDeposito['estado_documento']){
			case 1: $imgEstado = "<img src=\"../img/iconos/ico_rojo.gif\">"; break;
			case 2: $imgEstado = "<img src=\"../img/iconos/ico_amarillo.gif\">"; break;
			case 3: $imgEstado = "<img src=\"../img/iconos/ico_verde.gif\">"; break;
			default : $imgEstado = "";
		}
		
		switch($rowDeposito['origen']){
			case 0: $imgOrigen = "<img src=\"../img/iconos/ico_tesoreria.gif\">"; break;
			case 1: $imgOrigen = "<img src=\"../img/iconos/ico_caja_vehiculo.gif\">"; break;
			case 2: $imgOrigen = "<img src=\"../img/iconos/ico_caja_rs.gif\">"; break;
			default : $imgOrigen = "";
		}
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgEstado."</td>";
			$htmlTb .= "<td align=\"center\">".$imgOrigen."</td>";
			$htmlTb .= "<td>".utf8_encode($rowDeposito['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"right\">".$rowDeposito['numero_deposito_banco']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($rowDeposito['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($rowDeposito['fecha_aplicacion']))."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaConciliacion."</td>";
			$htmlTb .= "<td>".utf8_encode($rowDeposito['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".$rowDeposito['numeroCuentaCompania']."</td>";
			
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($rowDeposito['observacion'])."</td>";
				$htmlTb .= "</tr>";				
					$htmlTb .= ($rowDeposito['descripcion_motivo'] != "") ? "<tr><td><span class=\"textoNegrita_9px\">".$rowDeposito['descripcion_motivo']."</span></td></tr>" : "";				
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			
			$htmlTb .= "<td align=\"right\">".number_format($rowDeposito['monto_total_deposito'],'2','.',',')."</td>";
			$htmlTb .= "<td align=\"center\"><a class=\"modalImg\" id=\"aNuevo\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, '', ".$rowDeposito['id_deposito'].");\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" /></a></td>";
		if($rowDeposito['estado_documento']==3 || $rowDeposito['origen'] != 0){
			//$htmlTb .= "<td align=\"center\"><img src=\"../img/iconos/ico_quitarf2.gif\"/></td>";	
		}else if($rowDeposito['estado_documento']!=3 && $rowDeposito['origen'] == 0){
			//$htmlTb .= "<td align=\"center\"><img src=\"../img/iconos/ico_quitarf2.gif\"/></td>";	
			//$htmlTb .= "<td align=\"center\"><img class=\"puntero\" onclick=\"xajax_eliminarDeposito(".$rowDeposito['id_deposito'].")\" src=\"../img/iconos/ico_quitar.gif\" /></td>";
		}
		if($rowDeposito['origen']==0){//TESORERIA
			$sPar = "idobject=".$rowDeposito['id_deposito'];
				 $sPar.= "&ct=18";
				 $sPar.= "&dt=03";
				 $sPar.= "&cc=05";
		}else if($rowDeposito['origen']==1){//VEHICULO
			$sPar = "idobject=".$rowDeposito['id_deposito'];
				 $sPar.= "&ct=13";
				 $sPar.= "&dt=03";
				 $sPar.= "&cc=05";
		}else if($rowDeposito['origen']==2){//REPUESTO
			$sPar = "idobject=".$rowDeposito['id_deposito'];
				 $sPar.= "&ct=05";
				 $sPar.= "&dt=03";
				 $sPar.= "&cc=05";}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaDeposito(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("tdListaDeposito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
				$htmlTb .= sprintf("<button type=\"button\" onclick=\"xajax_asignarMotivo(%s, xajax.getFormValues('frmDeposito'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
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

$xajax->register(XAJAX_FUNCTION,"actualizarMonto");
$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentes");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarBanco1");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarBanco");
$xajax->register(XAJAX_FUNCTION,"buscarBanco1");
$xajax->register(XAJAX_FUNCTION,"buscarDeposito");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstado");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoDcto");
$xajax->register(XAJAX_FUNCTION,"cargaSaldoCuenta");
$xajax->register(XAJAX_FUNCTION,"eliminar");
$xajax->register(XAJAX_FUNCTION,"eliminaElementos");
$xajax->register(XAJAX_FUNCTION,"formAgregarCheques");
$xajax->register(XAJAX_FUNCTION,"formDeposito");
$xajax->register(XAJAX_FUNCTION,"guardarDeposito");
$xajax->register(XAJAX_FUNCTION,"insertarCheques");
$xajax->register(XAJAX_FUNCTION,"listaBanco");
$xajax->register(XAJAX_FUNCTION,"listaBanco1");
$xajax->register(XAJAX_FUNCTION,"listaCheques");
$xajax->register(XAJAX_FUNCTION,"listaDeposito");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");

?>