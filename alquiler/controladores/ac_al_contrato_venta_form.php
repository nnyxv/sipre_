<?php

function asignarAccesorio($idAccesorio, $frmListaAccesorio, $frmListaAdicional, $frmDcto, $frmLista, $insertar = false){
	$objResponse = new xajaxResponse();
		
	$sql = sprintf("SELECT id_tipo_accesorio, nom_accesorio, des_accesorio, precio_accesorio, costo_accesorio, iva_accesorio
					FROM an_accesorio 
					WHERE id_accesorio = %s",
			valTpDato($idAccesorio,"int"));
	
	$rs = mysql_query($sql);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	if($row["id_tipo_accesorio"] == 1){// 1 = Adicional, 2 = Accesorio, 3 = Contrato
		foreach($frmListaAdicional["hddIdAccesorio"] as $indice => $idAdicionalItm){
			if($idAccesorio == $idAdicionalItm){
				return $objResponse->alert("El Adicional ya se encuentra agregado");
			}
		}
	}elseif($row["id_tipo_accesorio"] == 2){// 1 = Adicional, 2 = Accesorio, 3 = Contrato
		foreach($frmListaAccesorio["hddIdAccesorio"] as $indice => $idAccesorioItm){			
			if($idAccesorio == $idAccesorioItm){
				return $objResponse->alert("El Accesorio ya se encuentra agregado");
			}
		}
	}
	
	// si el cliente paga impuesto y el accesorio/adicional tiene impuesto
	if($frmDcto["hddPagaImpuesto"] > 0 && $row["iva_accesorio"] > 0){
		$iva = iva();//ivas predeterminados
		if($iva[0] == false){ return $objResponse->alert($iva[1]); }
		$arrayIva = $iva[1];
	}
	
	if($insertar){
		$objResponse->loadCommands(insertarAccesorio("", $idAccesorio, $row["id_tipo_accesorio"], $row["nom_accesorio"], $row["des_accesorio"], str_replace(",","", $frmLista["txtCantidadItem"]), str_replace(",","", $frmLista["txtPrecioItem"]), $row["costo_accesorio"], $arrayIva));
	}else{
		$objResponse->assign("hddIdItem","value", $idAccesorio);
		$objResponse->assign("txtCantidadItem","value", "1.00");
		$objResponse->assign("txtPrecioItem","value", $row['precio_accesorio']);
		$objResponse->assign("txtCodigoItem","value", utf8_encode($row["nom_accesorio"]));
		$objResponse->assign("txtDescripcionItem","value", utf8_encode($row["des_accesorio"]));
	}
	return $objResponse;
}

function asignarCambioUnidad($frmCambioUnidad){
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(insertarCambioUnidad('', $frmCambioUnidad['txtIdCambioUnidad'], $frmCambioUnidad['txtKilometrajeEntradaCambio'], $frmCambioUnidad['lstCombustibleEntradaCambio'], $frmCambioUnidad['lstEstadoAdicionalEntradaCambio'], $frmCambioUnidad['txtMotivoCambio'], date("Y-m-d H:i:s"), $_SESSION['idEmpleadoSysGts']));
	
	return $objResponse;
}

function asignarCliente($nombreObjeto, $idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente.id = %s",
		valTpDato($idCliente, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
		
	if ($estatusCliente != "-1" && $estatusCliente != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.status = %s",
			valTpDato($estatusCliente, "text"));
	}
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto,
		cliente.status
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
		$queryClienteCredito = sprintf("SELECT * FROM cj_cc_credito WHERE id_cliente_empresa = %s;",
			valTpDato($rowCliente['id_cliente_empresa'], "int"));
		$rsClienteCredito = mysql_query($queryClienteCredito);
		if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
		
		$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$rowClienteCredito['diascredito']);
		
		$objResponse->assign("txtDiasCreditoCliente".$nombreObjeto,"value",$rowClienteCredito['diascredito']);
		$objResponse->assign("txtCreditoCliente".$nombreObjeto,"value",number_format($rowClienteCredito['creditodisponible'], 2, ".", ","));
		
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente".$nombreObjeto,"value","0");
	}
	
	if ($rowCliente['id'] > 0) {
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente exento y/o exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">El cliente se encuentra inactivo</div>" : "";
	} else if ($idCliente > 0 && in_array($cerrarVentana, array("1", "true"))) {
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjAlerta\" style=\"padding:2px;\">El cliente no se encuentra asociado a la empresa</div>" : "";
	}
	
	$objResponse->assign("txtIdCliente".$nombreObjeto,"value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente".$nombreObjeto,"value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente".$nombreObjeto,"innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente".$nombreObjeto,"value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente".$nombreObjeto,"value",$rowCliente['ci_cliente']);
	$objResponse->assign("hddPagaImpuesto".$nombreObjeto,"value",$rowCliente['paga_impuesto']);
	$objResponse->assign("tdMsjCliente".$nombreObjeto,"innerHTML",$tdMsjCliente);
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
		
	return $objResponse;
}

function asignarEmpleado($idEmpleado) {
	$objResponse = new xajaxResponse();
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s", valTpDato($idEmpleado, "int"));
	
	$queryEmpleado = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("txtIdEmpleado","value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowEmpleado['nombre_empleado']));
	
	$objResponse->script("byId('btnCancelarLista').click();");
	
	return $objResponse;
}

function asignarPrecio($idPrecio, $frmListaPrecio, $frmDcto, $idDetallePrecio = "") {
	$objResponse = new xajaxResponse();
	
	foreach($frmListaPrecio['hddIdPrecio'] as $idPrecioCargado){
		if($idPrecio == $idPrecioCargado){
			return $objResponse->alert("La tarifa ya se encuentra agregado");
		}
	}
	
	//if (count($frmListaPrecio) > 0){ return $objResponse->alert("Solo puede haber una tarifa cargada"); }
	if($idDetallePrecio != ""){ // si ya tiene cargado, solo alargar dias para recalcular con el mismo precio
		$filtro = "AND al_precios_detalle.id_precio_detalle = ".$idDetallePrecio;
	}
	$sql = sprintf("SELECT 
						al_precios.id_precio,
						al_precios.nombre_precio,
						al_precios.iva_precio,
						al_precios_detalle.id_precio_detalle,
						al_precios_detalle.descripcion,
						al_precios_detalle.precio,
						al_precios_detalle.dias,
						al_precios_detalle.id_tipo_precio
					FROM al_precios
					INNER JOIN al_precios_detalle ON al_precios.id_precio = al_precios_detalle.id_precio
					WHERE al_precios.id_precio = %s
					%s
					ORDER BY al_precios_detalle.id_tipo_precio, al_precios_detalle.dias ASC",
			valTpDato($idPrecio,"int"),
			$filtro);
	
	$rs = mysql_query($sql);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	while($row = mysql_fetch_assoc($rs)){//separo precio regular y precio sobre tiempo
		if($row["id_tipo_precio"] == 1){
			$arrayPrecioRegular[$row["id_precio_detalle"]] = $row;
		}elseif($row["id_tipo_precio"] == 2){
			$arrayPrecioSobreTiempo[$row["id_precio_detalle"]] = $row;
		}
		$precioPoseeIva = $row["iva_precio"];
	}
	
	// si el cliente paga impuesto y el accesorio/adicional tiene impuesto
	if($frmDcto["hddPagaImpuesto"] > 0 && $precioPoseeIva > 0){
		$iva = iva();//ivas predeterminados
		if($iva[0] == false){ return $objResponse->alert($iva[1]); }
		$arrayIva = $iva[1];
	}
	
	if($frmDcto["txtDiasTotal"] > 0){//dias reales que debe pagar segun el tiempo calculado
		$diasRegular = $frmDcto["txtDiasTotal"] - $frmDcto["txtDiasSobreTiempo"];
	}else{
		$diasRegular = $frmDcto["txtDiasContrato"];
	}
	$diasRegularContrato = $frmDcto["txtDiasContrato"];//debe cargar el precio establecido del contrato
	$diasSobreTiempo = $frmDcto["txtDiasSobreTiempo"];//dias para elegir precio sobre tiempo
		
	//sino hay igual busco el menor o igual a ese Ej: 1,3,5 y dia = 4; y si es mayor dia = 6, se selccionara el ultimo
	foreach($arrayPrecioRegular as $idPrecioDetalle => $arrayPrecio){
		if($arrayPrecio["dias"] <= $diasRegularContrato){// se asigna 1 y luego se reemplaza con 3
			$idDetallePrecioRegular = $idPrecioDetalle;
		}
	}
	
	foreach($arrayPrecioSobreTiempo as $idPrecioDetalle => $arrayPrecio){
		if($arrayPrecio["dias"] <= $diasSobreTiempo){// se asigna 1 y luego se reemplaza con 3
			$idDetallePrecioSobreTiempo = $idPrecioDetalle;
		}
	}
	
	if($idDetallePrecioRegular > 0){
		$arrayPrecioElegido = $arrayPrecioRegular[$idDetallePrecioRegular];
		$totalPrecio = $arrayPrecioElegido["precio"] * $diasRegular;
		
		$objResponse->loadCommands(insertarPrecio("", $arrayPrecioElegido["id_precio"], $arrayPrecioElegido["nombre_precio"], $arrayPrecioElegido["id_precio_detalle"], $arrayPrecioElegido["descripcion"], $arrayPrecioElegido["precio"], $arrayPrecioElegido["dias"], $arrayPrecioElegido["id_tipo_precio"], $diasRegular, $totalPrecio, $arrayIva));
	}
	
	if($idDetallePrecioSobreTiempo > 0){
		$arrayPrecioElegido = $arrayPrecioSobreTiempo[$idDetallePrecioSobreTiempo];
		$totalPrecio = $arrayPrecioElegido["precio"] * $diasSobreTiempo;
		
		$objResponse->loadCommands(insertarPrecio("", $arrayPrecioElegido["id_precio"], $arrayPrecioElegido["nombre_precio"], $arrayPrecioElegido["id_precio_detalle"], $arrayPrecioElegido["descripcion"], $arrayPrecioElegido["precio"], $arrayPrecioElegido["dias"], $arrayPrecioElegido["id_tipo_precio"], $diasSobreTiempo, $totalPrecio, $arrayIva));
	}
	
	$objResponse->script("byId('btnCancelarPrecio').click();");
	$objResponse->script("calcularDcto();");
	
	return $objResponse;
}

function asignarUnidadFisica($idUnidadFisica){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.placa,
			(CASE uni_fis.id_condicion_unidad
				WHEN 1 THEN	'NUEVO'
				WHEN 2 THEN	'USADO'
				WHEN 3 THEN	'USADO PARTICULAR'
			END) AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			clase.nom_clase,
			clase.id_clase,
			uni_bas.nom_uni_bas,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			uni_fis.kilometraje,
			uni_fis.id_uni_bas,
			alm.nom_almacen,
			vw_iv_modelo.nom_ano,
			vw_iv_modelo.nom_modelo,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)			
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE uni_fis.id_unidad_fisica = %s",
		valTpDato($idUnidadFisica,"int"));
	
	$rs = mysql_query($sql);	
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);

	$objResponse->assign("txtIdUnidadFisica","value",utf8_encode($row['id_unidad_fisica']));
	$objResponse->assign("txtSerialCarroceriaVehiculo","value",utf8_encode($row['serial_carroceria']));
	$objResponse->assign("txtPlacaVehiculo","value",utf8_encode($row['placa']));
	$objResponse->assign("txtUnidadBasica","value",utf8_encode($row['nom_uni_bas']));
	$objResponse->assign("txtMarcaVehiculo","value",utf8_encode($row['nom_marca']));
	$objResponse->assign("txtModeloVehiculo","value",utf8_encode($row['nom_modelo']));
	$objResponse->assign("txtAnoVehiculo","value",utf8_encode($row['nom_ano']));
	$objResponse->assign("txtColorVehiculo","value",utf8_encode($row['color_externo1']));
	$objResponse->assign("txtClaseVehiculo","value",utf8_encode($row['nom_clase']));
	$objResponse->assign("txtCondicionVehiculo","value",utf8_encode($row['condicion_unidad']));
	$objResponse->assign("txtAlmacenVehiculo","value",utf8_encode($row['nom_almacen']));
	$objResponse->assign("txtKilometrajeVehiculo","value",utf8_encode($row['kilometraje']));
	$objResponse->assign("hddIdUnidadBasica","value",$row['id_uni_bas']);
	$objResponse->assign("hddIdModelo","value",utf8_encode($row['id_modelo']));
	$objResponse->assign("hddIdClase","value",utf8_encode($row['id_clase']));

	$objResponse->script("byId('btnCancelarLista').click();");
	
	if($_GET["id"] == ""){//si es documento nuevo
		$objResponse->assign("txtKilometrajeSalida", "value", $row['kilometraje']);
	}

	return $objResponse;
}

function buscarAccesorio($frmBuscarAccesorio, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		2,// 1 = Adicional, 2 = Accesorio, 3 = Contrato
		$frmBuscarAccesorio['txtCriterioBuscarAccesorio']);
	
	$objResponse->loadCommands(listaAccesorio(0, "des_accesorio", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarAdicional($frmBuscarAdicional, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		1,// 1 = Adicional, 2 = Accesorio, 3 = Contrato
		$frmBuscarAdicional['txtCriterioBuscarAdicional']);
	
	$objResponse->loadCommands(listaAccesorio(0, "des_accesorio", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente'],
		$frmBuscarCliente['hddClientePago']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarEmpleado($frmBuscarEmpleado, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarEmpleado['txtCriterioBuscarEmpleado']);
	
	$objResponse->loadCommands(listaEmpleado(0, "id_empleado", "ASC", $valBusq));
		
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

function buscarPrecio($frmBuscarPrecio, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['hddIdClase'],
		$frmBuscarPrecio['txtCriterioBuscarPrecio']);
	
	$objResponse->loadCommands(listaPrecio(0, "id_precio", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarUnidadFisica($frmBuscarVehiculo, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarVehiculo['txtCriterioBuscarUnidadFisica']);
	
	$objResponse->loadCommands(listaUnidadFisica(0, "id_unidad_fisica", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaPrecio, $frmListaAccesorio, $frmListaAdicional, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['hddIdContrato'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtDescuento = ($txtDescuento == "") ? 0 : $txtDescuento;
	$porcDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);	
	
	$idMonedaLocal = $frmDcto['lstMoneda'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	
	// VERIFICA VALORES DE CONFIGURACION (Porcentaje Máximo de Descuento) POR USUARIO
	$ResultConfig500 = valorConfiguracion(500, $idEmpresa, $_SESSION['idUsuarioSysGts']);
	if ($ResultConfig500[0] != true && strlen($ResultConfig500[1]) > 0) {
		return $objResponse->alert($ResultConfig500[1]);
	} else if ($ResultConfig500[0] == true) {
		$ResultConfig500 = $ResultConfig500[1];
	}
	
	if (!($txtDescuento > 0 && $ResultConfig500 != "")) {
		// VERIFICA VALORES DE CONFIGURACION (Porcentaje Máximo de Descuento) GENERAL
		$ResultConfig500 = valorConfiguracion(500, $idEmpresa);
		if ($ResultConfig500[0] != true && strlen($ResultConfig500[1]) > 0) {
			return $objResponse->alert($ResultConfig500[1]);
		} else if ($ResultConfig500[0] == true) {
			$ResultConfig500 = $ResultConfig500[1];
		}
	}
	
	//$objResponse->script("byId('txtDescuento').className = 'inputHabilitado';");	
	if ($frmTotalDcto['hddConfig500'] == 1 && $txtDescuento > $ResultConfig500) {
		$txtDescuento = $ResultConfig500;		
		$objResponse->alert(utf8_encode("El porcentaje de descuento supera al máximo permitido."));
	}
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	
	foreach($frmListaPrecio["hddTotalPrecio"] as $valor){//LOS INPUT SON ARRAY nombre[]
		$txtSubTotal += $valor;
	}
	
	foreach($frmListaAccesorio["hddTotalAccesorio"] as $valor){
		$txtSubTotal += $valor;
	}
	
	foreach($frmListaAdicional["hddTotalAccesorio"] as $valor){
		$txtSubTotal += $valor;
	}
		
	// BASES IMPONIBLE Y DESCUENTOS POR ITEM
	$txtTotalExento = 0;
	$txtTotalExonerado = 0;
	$txtSubTotalDescuento = 0;
	$arrayIva = array();
	$arrayBaseIva = array();
	$arrayPorcIva = array();
	$arrayDescIva = array();
	
	$sql = "SELECT idIva, observacion FROM pg_iva";
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	while($row = mysql_fetch_assoc($rs)){
		$arrayDescIva[$row["idIva"]] = $row["observacion"];
	}
	
	//ITEM PRECIOS
	foreach($frmListaPrecio["hddIdDetallePrecioContrato"] as $indice => $valor){//SOLO SE USA EL INDICE QUE INDICA CADA ITEM
		$subTotalItm = $frmListaPrecio["hddTotalPrecio"][$indice] - (($frmListaPrecio["hddTotalPrecio"][$indice] * $txtDescuento) / 100);
		//iva por item
		$arrayIdIvaItm = array_filter(explode("|", $frmListaPrecio["hddIdIvaPrecio"][$indice]));
		$arrayPorcIvaItm = array_filter(explode("|", $frmListaPrecio["hddPorcentajeIvaPrecio"][$indice]));
		foreach($arrayIdIvaItm as $indiceIva => $idIvaItm){
			$arrayPorcIva[$idIvaItm] = $arrayPorcIvaItm[$indiceIva];//porc iva por item
			$arrayBaseIva[$idIvaItm] += $subTotalItm;//base imponible por item
			$arrayIva[$idIvaItm] += ($subTotalItm * $arrayPorcIvaItm[$indiceIva]) / 100;//subtotal iva por item
		}
		//exento
		if(count($arrayIdIvaItm) == 0){ $txtTotalExento += $subTotalItm; }
	}
	
	//ITEM ACCESORIOS
	foreach($frmListaAccesorio["hddIdDetalleAccesorioContrato"] as $indice => $valor){
		$subTotalItm = $frmListaAccesorio["hddTotalAccesorio"][$indice] - (($frmListaAccesorio["hddTotalAccesorio"][$indice] * $txtDescuento) / 100);
		
		$arrayIdIvaItm = array_filter(explode("|", $frmListaAccesorio["hddIdIvaAccesorio"][$indice]));
		$arrayPorcIvaItm = array_filter(explode("|", $frmListaAccesorio["hddPorcentajeIvaAccesorio"][$indice]));
		foreach($arrayIdIvaItm as $indiceIva => $idIvaItm){
			$arrayPorcIva[$idIvaItm] = $arrayPorcIvaItm[$indiceIva];
			$arrayBaseIva[$idIvaItm] += $subTotalItm;
			$arrayIva[$idIvaItm] += ($subTotalItm * $arrayPorcIvaItm[$indiceIva]) / 100;
		}
		
		if(count($arrayIdIvaItm) == 0){ $txtTotalExento += $subTotalItm; }
	}
	
	//ITEM ADICIONALES
	foreach($frmListaAdicional["hddIdDetalleAccesorioContrato"] as $indice => $valor){
		$subTotalItm = $frmListaAdicional["hddTotalAccesorio"][$indice] - (($frmListaAdicional["hddTotalAccesorio"][$indice] * $txtDescuento) / 100);
		
		$arrayIdIvaItm = array_filter(explode("|", $frmListaAdicional["hddIdIvaAccesorio"][$indice]));
		$arrayPorcIvaItm = array_filter(explode("|", $frmListaAdicional["hddPorcentajeIvaAccesorio"][$indice]));
		foreach($arrayIdIvaItm as $indiceIva => $idIvaItm){			
			$arrayPorcIva[$idIvaItm] = $arrayPorcIvaItm[$indiceIva];
			$arrayBaseIva[$idIvaItm] += $subTotalItm;
			$arrayIva[$idIvaItm] += ($subTotalItm * $arrayPorcIvaItm[$indiceIva]) / 100;
		}
		
		if(count($arrayIdIvaItm) == 0){ $txtTotalExento += $subTotalItm; }
	}
	
	// CREA LOS ELEMENTOS DE IVA
	$objResponse->script("$('.trIva').remove();");
	foreach ($arrayIva as $indiceIva => $valorIva) {
		//$totalIva = $valorIva + (($valorIva * $arrayPorcIva[$indiceIva]) / 100);
		$objResponse->script(sprintf("
		$('#trNetoContrato').before('".
			"<tr align=\"right\" class=\"textoGris_11px trIva\">".
				"<td class=\"tituloCampo\">%s:".
					"<input type=\"hidden\" name=\"hddIdIva[]\" value=\"%s\"/>".
				"<td nowrap=\"nowrap\"><input type=\"text\" name=\"txtBaseImpIva[]\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
				"<td nowrap=\"nowrap\"><input type=\"text\" name=\"txtIva[]\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
				"<td>%s</td>".
				"<td><input type=\"text\" name=\"txtSubTotalIva[]\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
			"</tr>');", 
			utf8_encode($arrayDescIva[$indiceIva]), 
			$indiceIva,//id iva
			number_format(round($arrayBaseIva[$indiceIva], 2), 2, ".", ","), //monto base imponible
			$arrayPorcIva[$indiceIva], "%", //porcentaje iva
			$abrevMonedaLocal, 
			number_format(round($valorIva, 2), 2, ".", ",")//subtotal iva
			));
		
		$subTotalIva += round(doubleval($valorIva), 2);
	}
	
	//ANTICIPOS A FAVOR DEL CLIENTE
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(dcto.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
		WHERE suc.id_empresa_padre = dcto.id_empresa)
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = dcto.id_empresa)
	OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
		WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
										WHERE suc.id_empresa = dcto.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(idDepartamento IN (%s)
	AND idCliente = %s)",
		valTpDato($idModuloPpal, "campo"),
		valTpDato($frmDcto['txtIdClientePago'], "int"));
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estadoAnticipo IN (0,1,2) AND estatus = 1");
	
	$query = sprintf("SELECT
		SUM(dcto.saldoAnticipo) AS saldoDocumento
	FROM cj_cc_anticipo dcto
		INNER JOIN cj_cc_cliente cliente ON (dcto.idCliente = cliente.id)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dcto.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAnticipo = mysql_fetch_assoc($rs);
	
	$txtSubTotalDescuento = $txtSubTotal * ($porcDescuento / 100);
	$txtTotalOrden = doubleval($txtSubTotal) - doubleval($txtSubTotalDescuento);
	$txtTotalOrden += doubleval($subTotalIva) + doubleval($txtGastosConIva) + doubleval($txtGastosSinIva);

	$objResponse->assign("txtSubTotal", "value", number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento","value",number_format($txtDescuento, 2, ".", ","));	
	$objResponse->assign("txtSubTotalDescuento","value",number_format($txtSubTotalDescuento, 2, ".", ","));	
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExento + $txtGastosSinIva), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExonerado, 2, ".", ","));
	
	$objResponse->assign("txtTotalAnticipo", "value", number_format($rowAnticipo['saldoDocumento'], 2, ".", ","));
	$objResponse->assign("txtTotalRestaPagar", "value", number_format($txtTotalOrden-$rowAnticipo['saldoDocumento'], 2, ".", ","));
	
	$objResponse->assign("tdSubTotalMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdDescuentoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdTotalRegistroMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdTotalFacturaMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdExentoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdExoneradoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdAnticipoMoneda", "innerHTML", $abrevMonedaLocal);
	$objResponse->assign("tdRestaPagarMoneda", "innerHTML", $abrevMonedaLocal);

	return $objResponse;
}

function calcularTiempo($frmDcto, $reasignarPrecio = 0){
	$objResponse = new xajaxResponse();
	
	$txtFechaSalida = $frmDcto["txtFechaSalida"]." ".$frmDcto["txtHoraSalida"];
	$txtFechaEntrada = $frmDcto["txtFechaEntrada"]." ".$frmDcto["txtHoraEntrada"];
	$txtFechaEntradaFinal = $frmDcto["txtFechaEntradaFinal"]." ".$frmDcto["txtHoraEntradaFinal"];
	
	if($frmDcto["txtFechaEntrada"] != "" && $frmDcto["txtHoraEntrada"] != "" && $frmDcto["txtFechaSalida"] != "" && $frmDcto["txtHoraSalida"] != ""){
		if(strtotime($frmDcto["txtFechaEntrada"]) < strtotime($frmDcto["txtFechaSalida"])){
			$objResponse->script("byId('txtFechaEntrada').value = '';
								byId('txtDiasContrato').value = '';
								byId('txtDiasTotal').value = '';");
			return $objResponse->alert("La fecha de Entrada no puede ser menor a la de salida");
		}
		//+1 porque si es la misma fecha da 0; Si es hoy y mañana devuelve 1 y deberia ser 2 (YA NO, AHORA USA HORAS TAMB)
		$diasContrato = diasEntreFechas($txtFechaSalida, $txtFechaEntrada);// + 1
	}
	
	if($frmDcto["txtFechaEntradaFinal"] != "" && $frmDcto["txtHoraEntradaFinal"] != "" && $frmDcto["txtFechaSalida"] != "" && $frmDcto["txtHoraSalida"] != ""){
		if(strtotime($frmDcto["txtFechaEntradaFinal"]) < strtotime($frmDcto["txtFechaSalida"])){
			$objResponse->script("byId('txtFechaEntradaFinal').value = '';
								byId('txtDiasSobreTiempo').value = '';
								byId('txtDiasBajoTiempo').value = '';
								byId('txtDiasTotal').value = '';");
			return $objResponse->alert("La fecha de Entrada Final no puede ser menor a la de salida");
		}
		//+1 porque si es la misma fecha da 0; Si es hoy y mañana devuelve 1 y deberia ser 2 (YA NO, AHORA USA HORAS TAMB)
		$diasEntregaFinal = diasEntreFechas($txtFechaSalida, $txtFechaEntradaFinal); //+ 1
	}
	
	if($diasEntregaFinal != "" && $diasContrato != ""){
		$restaDias = $diasEntregaFinal - $diasContrato;
		if($restaDias > 0){ $diasSobreTiempo = $restaDias; }
		if($restaDias < 0){ $diasBajoTiempo = abs($restaDias); }
	}
	
	$objResponse->assign("txtDiasContrato","value",$diasContrato);
	$objResponse->assign("txtDiasTotal","value",$diasEntregaFinal);
	$objResponse->assign("txtDiasSobreTiempo","value",$diasSobreTiempo);
	$objResponse->assign("txtDiasBajoTiempo","value",$diasBajoTiempo);
	
	if($reasignarPrecio == 1){//fecha final debe re agregar precio con el total de dias actual
		$objResponse->script("reasignarPrecio();");
	}
	
	return $objResponse;
}

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['idmoneda']) {
			$selected = "selected=\"selected\"";
		} else if ($row['predeterminada'] == 1 && $selId == "") {
			$selected = "selected=\"selected\"";
		}
		
		$html .= "<option ".$selected." value=\"".$row['idmoneda']."\">".utf8_encode($row['descripcion']." (".$row['abreviacion'].")")."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMoneda","innerHTML",$html);
	
	return $objResponse;
}

function cargarDcto($idDocumento) {
	$objResponse = new xajaxResponse();
		
	if ($idDocumento > 0) {
		$objResponse->assign("tituloPaginaAlquiler","innerHTML","Edici&oacute;n Contrato de Alquiler");
		
		$objResponse->script("
		byId('txtDescuento').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';");
		
		$sql = sprintf("SELECT
				contrato.*,
				presupuesto.numero_presupuesto_venta,
				tipo_contrato.id_filtro_contrato,
				tipo_contrato.modo_factura,
				tipo_contrato.nombre_tipo_contrato,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM al_contrato_venta contrato
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
			LEFT JOIN al_presupuesto_venta presupuesto ON (contrato.id_presupuesto_venta = presupuesto.id_presupuesto_venta)
			WHERE contrato.id_contrato_venta = %s
			AND contrato.estatus_contrato_venta = 1;",
			valTpDato($idDocumento, "int"));
		$rs = mysql_query($sql);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if(mysql_num_rows($rs) == 0){
			$objResponse->alert(utf8_encode("El Contrato no puede ser cargado debido a que su estado no es válido"));
			return $objResponse->script("byId('btnCancelar').click();");
		}
		
		// CARGA LOS CAMBIOS DE VEHICULOS
		$sqlCambioUnidad = sprintf("SELECT * FROM al_contrato_venta_cambio_unidad 
			WHERE id_contrato_venta = %s",
			valTpDato($idDocumento, "int"));
		$rsCambioUnidad = mysql_query($sqlCambioUnidad);
		if (!$rsCambioUnidad) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($rowCambioUnidad = mysql_fetch_assoc($rsCambioUnidad)) {
			$objResponse->loadCommands(insertarCambioUnidad($rowCambioUnidad['id_contrato_venta_cambio_unidad'], $rowCambioUnidad['id_unidad_fisica'], $rowCambioUnidad['kilometraje_entrada'], $rowCambioUnidad['nivel_combustible_entrada'], $rowCambioUnidad['id_estado_adicional_entrada'], utf8_encode($rowCambioUnidad['motivo']), $rowCambioUnidad['fecha_creacion'], $rowCambioUnidad['id_empleado']));
		}
		
		// CARGA EL PRECIO DEL CONTRATO
		$sqlPrecioDet = sprintf("SELECT 
				al_contrato_venta_precio.*,
				al_precios.nombre_precio,
				al_precios_detalle.descripcion
			FROM al_contrato_venta_precio 
			INNER JOIN al_precios_detalle ON al_contrato_venta_precio.id_precio_detalle = al_precios_detalle.id_precio_detalle
			INNER JOIN al_precios ON al_precios_detalle.id_precio = al_precios.id_precio
			WHERE id_contrato_venta = %s",
			valTpDato($idDocumento, "int"));
		$rsPrecioDet = mysql_query($sqlPrecioDet);
		if (!$rsPrecioDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($rowPrecioDet = mysql_fetch_assoc($rsPrecioDet)) {
			$arrayIva = array();
			$sqlIva = sprintf("SELECT id_impuesto, impuesto FROM al_contrato_venta_precio_impuesto WHERE id_contrato_venta_precio = %s",
				valTpDato($rowPrecioDet["id_contrato_venta_precio"], "int"));
			$rsIva = mysql_query($sqlIva);			
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			while($rowIva = mysql_fetch_assoc($rsIva)){
				$arrayIva[$rowIva["id_impuesto"]] = array('idIva' => $rowIva["id_impuesto"], 'iva' => $rowIva["impuesto"]);
			}
			
			$objResponse->loadCommands(insertarPrecio($rowPrecioDet["id_contrato_venta_precio"], $rowPrecioDet["id_precio"], $rowPrecioDet["nombre_precio"], $rowPrecioDet["id_precio_detalle"], $rowPrecioDet["descripcion"], $rowPrecioDet["precio"], $rowPrecioDet["dias"], $rowPrecioDet["id_tipo_precio"], $rowPrecioDet['dias_calculado'], $rowPrecioDet['total_precio'], $arrayIva));
		}
		
		// CARGA LOS ACCESORIOS Y ADICIONALES
		$sqlAccesorioDet = sprintf("SELECT 
				al_contrato_venta_accesorio.*,
				nom_accesorio,
				des_accesorio 
			FROM al_contrato_venta_accesorio 
			INNER JOIN an_accesorio ON al_contrato_venta_accesorio.id_accesorio = an_accesorio.id_accesorio
			WHERE id_contrato_venta = %s",
			valTpDato($idDocumento, "int"));
		$rsAccesorioDet = mysql_query($sqlAccesorioDet);
		if (!$rsAccesorioDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($rowAccesorioDet = mysql_fetch_assoc($rsAccesorioDet)) {
			$arrayIva = array();
			$sqlIva = sprintf("SELECT id_impuesto, impuesto FROM al_contrato_venta_accesorio_impuesto WHERE id_contrato_venta_accesorio = %s",
				valTpDato($rowAccesorioDet["id_contrato_venta_accesorio"], "int"));
			$rsIva = mysql_query($sqlIva);			
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			while($rowIva = mysql_fetch_assoc($rsIva)){
				$arrayIva[$rowIva["id_impuesto"]] = array('idIva' => $rowIva["id_impuesto"], 'iva' => $rowIva["impuesto"]);
			}
			
			$objResponse->loadCommands(insertarAccesorio($rowAccesorioDet["id_contrato_venta_accesorio"], $rowAccesorioDet["id_accesorio"], $rowAccesorioDet["id_tipo_accesorio"], $rowAccesorioDet["nom_accesorio"], $rowAccesorioDet["des_accesorio"], $rowAccesorioDet["cantidad"], $rowAccesorioDet["precio"], $rowAccesorioDet["costo"], $arrayIva));
		}
					
		// DATOS DEL CONTRATO
		$objResponse->assign("txtIdEmpresa","value",$row['id_empresa']);
		$objResponse->assign("txtEmpresa","value",utf8_encode($row['nombre_empresa']));
		$objResponse->assign("hddIdContrato","value",$row['id_contrato_venta']);
		$objResponse->assign("txtNumeroContrato","value",$row['numero_contrato_venta']);
		$objResponse->assign("txtFechaContrato","value",date(spanDateFormat,strtotime($row['fecha_creacion'])));
		$objResponse->loadCommands(cargaLstMoneda($row['id_moneda']));
		$objResponse->loadCommands(cargarLstTipoContrato($row['id_tipo_contrato'], $row['id_empresa']));
		$objResponse->loadCommands(cargarLstEstadoAdicionalSalida($row['id_estado_adicional_salida']));
		//$objResponse->loadCommands(cargarLstEstadoAdicionalEntrada($row['id_estado_adicional_entrada']));//NO, solo al finalizar
		$objResponse->loadCommands(cargarLstTipoPago($row['condicion_pago']));
		$objResponse->assign("txtDescuento","value",$row['porcentaje_descuento']);
		$objResponse->assign("txtObservacion","value",utf8_encode($row['observacion']));
		$objResponse->loadCommands(asignarEmpleado($row['id_empleado_creador']));
		$objResponse->loadCommands(asignarUnidadFisica($row['id_unidad_fisica']));
		
		$objResponse->assign("txtFechaSalida","value",fecha($row['fecha_salida']));
		$objResponse->assign("txtHoraSalida","value",tiempo($row['fecha_salida']));
		$objResponse->assign("txtFechaEntrada","value",fecha($row['fecha_entrada']));
		$objResponse->assign("txtHoraEntrada","value",tiempo($row['fecha_entrada']));
		$objResponse->assign("txtFechaEntradaFinal","value",fecha($row['fecha_final']));
		$objResponse->assign("txtHoraEntradaFinal","value",tiempo($row['fecha_final']));
		
		$objResponse->assign("txtKilometrajeSalida","value",$row['kilometraje_salida']);
		$objResponse->assign("lstCombustibleSalida","value",$row['nivel_combustible_salida']);
		$objResponse->assign("txtKilometrajeEntrada","value",$row['kilometraje_entrada']);
		$objResponse->assign("lstCombustibleEntrada","value",$row['nivel_combustible_entrada']);
		
		$objResponse->assign("txtDiasContrato","value",$row['dias_contrato']);
		$objResponse->assign("txtDiasSobreTiempo","value",$row['dias_sobre_tiempo']);
		$objResponse->assign("txtDiasBajoTiempo","value",$row['dias_bajo_tiempo']);
		$objResponse->assign("txtDiasTotal","value",$row['dias_total']);
		
		// DATOS DEL CLIENTE
		$objResponse->loadCommands(asignarCliente('', $row['id_cliente'], $row['id_empresa'], "", $row['condicion_pago'], "false", "false", "false"));
		$objResponse->loadCommands(asignarCliente('Pago', $row['id_cliente_pago'], $row['id_empresa'], "", $row['condicion_pago'], "false", "false", "false"));
		
		
		// DATOS DEL PRESUPUESTO
		$objResponse->assign("hddIdPresupuestoVenta","value", $row['id_presupuesto_venta']);
		$objResponse->assign("txtNumeroPresupuestoVenta","value", $row['numero_presupuesto_venta']);			
		
		$objResponse->script("
		byId('aListarUnidadFisica').style.display = 'none';		
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtIdCliente').readOnly = true;
		byId('aListarCliente').style.display = 'none';
		byId('txtIdClientePago').className = 'inputInicial';
		byId('txtIdClientePago').readOnly = true;
		//byId('aListarClientePago').style.display = 'none';");
		
		if(!esFinalizar()){
			$objResponse->script("byId('aCambiarUnidadFisica').style.display = '';");
		}
		
		$objResponse->script("
		byId('lstMoneda').className = 'inputInicial';
		byId('lstMoneda').onchange = function () {
			selectedOption(this.id,".$row["id_moneda"].");
		}");
		
		$objResponse->script("calcularDcto();");
		
	} else {
		$objResponse->assign("tituloPaginaAlquiler","innerHTML","Nuevo Contrato de Alquiler");
		
		$objResponse->script("
		byId('txtDescuento').className = 'inputHabilitado';
		byId('txtObservacion').className = 'inputHabilitado';");
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
				
		$objResponse->loadCommands(cargaLstMoneda());		
		$objResponse->loadCommands(cargarLstTipoContrato("", $idEmpresa));
		$objResponse->loadCommands(cargarLstEstadoAdicionalSalida());		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", ""));
		$objResponse->loadCommands(asignarEmpleado($_SESSION['idEmpleadoSysGts']));
		$objResponse->assign("txtFechaSalida","value",date(spanDateFormat));
		$objResponse->assign("txtFechaContrato","value",date(spanDateFormat));		
		if(date('i') > 30){
			$horaSalida = (date("H")+1).":00";
		}else{
			$horaSalida = date("H").":30";
		}
		$objResponse->assign("txtHoraSalida","value",date("h:i A", strtotime($horaSalida)));//sugerida
		$objResponse->assign("txtHoraEntrada","value",date("h:i A", strtotime($horaSalida)));//sugerir la misma para agilizar
		$objResponse->script("calcularDcto();");//inicializar en cero
	}
	
	$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		byId('txtIdEmpleado').className = 'inputInicial';
		byId('txtIdEmpleado').readOnly = true;
		byId('aListarEmpleado').style.display = 'none';");
		
	if(esFinalizar()){
		$objResponse->assign("tituloPaginaAlquiler","innerHTML","Cierre de Contrato de Alquiler");
		$objResponse->loadCommands(cargarLstEstadoAdicionalEntrada($row['id_estado_adicional_salida'], $row['id_estado_adicional_entrada']));
		
		$objResponse->script("$('.spanRojoFinalizar').show();
		byId('txtKilometrajeEntrada').className = 'inputHabilitado';
		byId('lstCombustibleEntrada').className = 'inputHabilitado';
		byId('txtFechaEntradaFinal').className = 'inputHabilitado';
		byId('txtHoraEntradaFinal').className = 'inputHabilitado';		
		byId('txtKilometrajeEntrada').readOnly = false;
		byId('txtKilometrajeSalida').readOnly = true;
		byId('txtFechaSalida').className = 'inputInicial';
		byId('txtHoraSalida').className = 'inputInicial';
		byId('txtKilometrajeSalida').className = 'inputInicial';
		byId('lstCombustibleSalida').className = 'inputInicial';
		byId('txtFechaEntrada').className = 'inputInicial';
		byId('txtHoraEntrada').className = 'inputInicial';
		");
		
		$objResponse->script("
		byId('lstCombustibleSalida').onchange = function () {
			selectedOption(this.id,".$row["nivel_combustible_salida"].");
		}");
		
		if($row["modo_factura"] == 1){
			$mensaje = utf8_encode("Los contratos de tipo ".$row["nombre_tipo_contrato"]." al cerrar se env&iacute;an a caja para ser facturados");
		}else if($row["modo_factura"] == 2){
			$mensaje = utf8_encode("Los contratos de tipo ".$row["nombre_tipo_contrato"]." al cerrar generan vale de salida");
		}
		$objResponse->assign("tdMensajeCerrar","innerHTML", $mensaje);
		$objResponse->script("byId('trMensajeCerrar').style.display = '';");
	}
	
	return $objResponse;
}

function cargarLstEstadoAdicionalEntrada($idEstadoAdicionalSalida, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$sql = "SELECT id_estado_adicional, nombre_estado 
			FROM an_unidad_estado_adicional 
			WHERE activo = 1
			AND id_estado_adicional != 2";// 2 = Alquilado
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if($selId == "" && $idEstadoAdicionalSalida == 2){ $idSugerido = 1; }//si salio como alquilado sugerir entrar disponible
	
	$html = "<select id=\"lstEstadoAdicionalEntrada\" name=\"lstEstadoAdicionalEntrada\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row["id_estado_adicional"] || $idSugerido == $row["id_estado_adicional"]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row["id_estado_adicional"]."\">".utf8_encode($row["nombre_estado"])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstEstadoAdicionalEntrada","innerHTML",$html);
	
	$objResponse->script("byId('spanTextoRojo').style.display = '';");
	
	if($selId != ""){
		$objResponse->script("byId('lstEstadoAdicionalEntrada').className = 'inputInicial';");
		$objResponse->script("
		byId('lstEstadoAdicionalEntrada').onchange = function () {
			selectedOption(this.id,'".$selId."');
		}");
	}
	
	return $objResponse;
}

function cargarLstEstadoAdicionalEntradaCambio($selId = "") {
	$objResponse = new xajaxResponse();
	
	$sql = "SELECT id_estado_adicional, nombre_estado 
			FROM an_unidad_estado_adicional 
			WHERE activo = 1
			AND id_estado_adicional != 2";// 2 = Alquilado
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$idSugerido = 1; //si salio como alquilado sugerir entrar disponible
	
	$html = "<select id=\"lstEstadoAdicionalEntradaCambio\" name=\"lstEstadoAdicionalEntradaCambio\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row["id_estado_adicional"] || $idSugerido == $row["id_estado_adicional"]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row["id_estado_adicional"]."\">".utf8_encode($row["nombre_estado"])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstEstadoAdicionalEntradaCambio","innerHTML",$html);
	
	return $objResponse;
}

function cargarLstEstadoAdicionalSalida($selId = "") {
	$objResponse = new xajaxResponse();
	
	$sql = "SELECT id_estado_adicional, nombre_estado 
			FROM an_unidad_estado_adicional 
			WHERE activo = 1
			AND id_estado_adicional != 1";// 1 = Disponible
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
	
	if($selId == ""){ $idSugerido = 2; }
	
	$html = "<select id=\"lstEstadoAdicionalSalida\" name=\"lstEstadoAdicionalSalida\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row["id_estado_adicional"] || $idSugerido == $row["id_estado_adicional"]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row["id_estado_adicional"]."\">".utf8_encode($row["nombre_estado"])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstEstadoAdicionalSalida","innerHTML",$html);
	
	if($selId != ""){
		$objResponse->script("byId('lstEstadoAdicionalSalida').className = 'inputInicial';");
		$objResponse->script("
		byId('lstEstadoAdicionalSalida').onchange = function () {
			selectedOption(this.id,'".$selId."');
		}");
	}
	
	return $objResponse;
}


function cargarLstTipoContrato($idTipoContrato = "", $idEmpresa){
	$objResponse = new xajaxResponse();

	$sql = sprintf("SELECT
						al_tipo_contrato.id_tipo_contrato, 
						al_tipo_contrato.nombre_tipo_contrato,
						al_tipo_contrato.id_filtro_contrato
					FROM al_tipo_contrato 
					INNER JOIN al_tipo_contrato_usuario ON al_tipo_contrato_usuario.id_tipo_contrato = al_tipo_contrato.id_tipo_contrato					
					WHERE al_tipo_contrato_usuario.id_usuario = %s
					AND al_tipo_contrato_usuario.id_empresa = %s",
		valTpDato($_SESSION["idUsuarioSysGts"],"int"),
		valTpDato($idEmpresa,"int"));
	
	$rs = mysql_query($sql);	
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$tienePermisoTipoContrato = mysql_num_rows($rs);	
	
	if($tienePermisoTipoContrato == 0){
		return $objResponse->alert("No tienes permisos por tipo de contrato");
	}
	
	$html = "<select id=\"lstTipoContrato\" name=\"lstTipoContrato\" class=\"inputHabilitado\" onchange=\"xajax_cargarLstTipoPago('',this.value);\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if($idTipoContrato == $row['id_tipo_contrato']){
			$selected = "selected=\"selected\"";
			$idFiltroContrato = $row["id_filtro_contrato"];
		}		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_contrato']."\">".utf8_encode($row['nombre_tipo_contrato'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoContrato","innerHTML",$html);
	
	if($idTipoContrato > 0){
		$objResponse->loadCommands(cargarLstTipoPago("",$idTipoContrato));
		$objResponse->script("byId('hddIdFiltroContrato').value = '".$idFiltroContrato."'");
		//$objResponse->script("byId('lstTipoContrato').className = 'inputInicial';");
		/*$objResponse->script("
		byId('lstTipoContrato').onchange = function () {
			selectedOption(this.id,".$idTipoContrato.");
		}");*/
	}
	
	return $objResponse;
}

function cargarLstTipoPago($tipoPago = "", $idTipoContrato = ""){
	$objResponse = new xajaxResponse();
	
	if($idTipoContrato > 0){
		$sql = sprintf("SELECT
							pg_clave_movimiento.pago_contado,
							al_tipo_contrato.id_filtro_contrato
						FROM al_tipo_contrato 
						INNER JOIN pg_clave_movimiento ON al_tipo_contrato.id_clave_movimiento = pg_clave_movimiento.id_clave_movimiento
						AND al_tipo_contrato.id_tipo_contrato = %s",
			valTpDato($idTipoContrato,"int"));	
	
		$rs = mysql_query($sql);	
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if($row["pago_contado"] == 1){
			$tipoPago = "1";
		}else{
			$tipoPago = "0";
		}
		$objResponse->script("byId('hddIdFiltroContrato').value = '".$row["id_filtro_contrato"]."'");
	}
		
	$html = "<select id=\"lstTipoPago\" name=\"lstTipoPago\">";
	if($tipoPago == "0"){
		$html .= "<option selected=\"selected\" value=\"0\">Cr&eacute;dito</option>";	
	}else{
		$html .= "<option selected=\"selected\" value=\"1\">Contado</option>";	
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaPrecio, $frmListaAccesorio, $frmListaAdicional, $frmTotalDcto, $frmListaCambioUnidades){
	$objResponse = new xajaxResponse();
		
	global $spanKilometraje;
		
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idCliente = $frmDcto['txtIdCliente'];
	$idClientePago = $frmDcto['txtIdClientePago'];
	$idDocumentoVenta = $frmDcto['hddIdContrato'];
	$numeroActual = $frmDcto['txtNumeroPedidoPropio'];

	$fechaSalida = date("Y-m-d H:i:s", strtotime($frmDcto['txtFechaSalida']." ".$frmDcto['txtHoraSalida']));
	$fechaEntrada = date("Y-m-d H:i:s", strtotime($frmDcto['txtFechaEntrada']." ".$frmDcto['txtHoraEntrada']));
	$fechaEntradaFinal = $frmDcto['txtFechaEntradaFinal'];
	
	if($fechaEntradaFinal != ""){
		$fechaEntradaFinal = date("Y-m-d H:i:s", strtotime($fechaEntradaFinal." ".$frmDcto['txtHoraEntradaFinal']));
	}
	
	if (strpos($fechaSalida, '1969') !== false || strpos($fechaSalida, '00:00:00') !== false) {
		return $objResponse->alert("Verifica la fecha/hora de salida: ".$fechaSalida);
	}
	
	if (strpos($fechaEntrada, '1969') !== false || strpos($fechaEntrada, '00:00:00') !== false) {
		return $objResponse->alert("Verifica la fecha/hora de entrada: ".$fechaEntrada);
	}
	
	if (strpos($fechaEntradaFinal, '1969') !== false || strpos($fechaEntradaFinal, '00:00:00') !== false) {
		return $objResponse->alert("Verifica la fecha/hora de entrada Final: ".$fechaEntradaFinal);
	}
	
	if ($frmDcto['txtKilometrajeEntrada'] != '' && ($frmDcto['txtKilometrajeEntrada'] < $frmDcto['txtKilometrajeSalida'])){
		return $objResponse->alert("El ".$spanKilometraje." de entrada no puede ser menor al de salida");
	}
	
	// VERIFICA VALORES DE CONFIGURACION (Dias de vencimiento del contrato)
	$diasVencimientoContrato = 0;
	$arrayConfig = valorConfiguracion(502, $idEmpresa);
	if ($arrayConfig[0] != true && strlen($arrayConfig[1]) > 0) {
		return $objResponse->alert($arrayConfig[1]);
	} else if ($arrayConfig[0] == true) {
		if($arrayConfig[1] == ""){
			return $objResponse->alert("No se ha configurado dias de vencimiento por contrato");
		} 
		$diasVencimientoContrato = $arrayConfig[1];
	}
	
	$fechaVencimientoContrato = date("Y-m-d", strtotime($fechaEntrada. " + ".$diasVencimientoContrato." DAYS"));
	
	if (strpos($fechaVencimientoContrato, '1969') !== false || strpos($fechaVencimientoContrato, '00:00:00') !== false) {
		return $objResponse->alert("Verifica la fecha de entrada para generar fecha de vencimiento: ".$fechaVencimientoContrato);
	}
		
	mysql_query("START TRANSACTION;");
	
	if ($idDocumentoVenta > 0) {
		if (!xvalidaAcceso($objResponse,"al_contrato_venta_list","editar")) { return $objResponse; }
		
		//comprobar estado del contrato
		$sql = sprintf("SELECT * FROM al_contrato_venta WHERE id_contrato_venta = %s AND estatus_contrato_venta = 1;",
			valTpDato($idDocumentoVenta, "int"));
		$rs = mysql_query($sql);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }		
		
		if(mysql_num_rows($rs) == 0){
			$objResponse->alert(utf8_encode("El Contrato no puede ser actualizado debido a que su estado no es válido"));
			return $objResponse->script("byId('btnCancelar').click();");
		}
		
		$rowContrato = mysql_fetch_assoc($rs);
		
		//INSERTA SOLO SI HUBO CAMBIO DE UNIDAD // solo se puede cambiar uno a la vez, no es necesario el multiple
		foreach($frmListaCambioUnidades['hddIdDetalleCambioUnidad'] as $indice => $idDetalleCambioUnidad){
			if($idDetalleCambioUnidad == ""){
				$insertSQL = sprintf("INSERT INTO al_contrato_venta_cambio_unidad (id_contrato_venta, id_unidad_fisica, kilometraje_salida, kilometraje_entrada, nivel_combustible_salida, nivel_combustible_entrada, id_estado_adicional_salida, id_estado_adicional_entrada, motivo, id_empleado, fecha_creacion)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($frmListaCambioUnidades['hddIdCambioUnidad'][$indice], "int"),
					valTpDato($rowContrato['kilometraje_salida'], "int"),
					valTpDato($frmListaCambioUnidades['hddKilometrajeEntradaCambio'][$indice], "int"),
					valTpDato($rowContrato['nivel_combustible_salida'], "real_inglesa"),
					valTpDato($frmListaCambioUnidades['hddCombustibleEntradaCambio'][$indice], "real_inglesa"),
					valTpDato($rowContrato['id_estado_adicional_salida'], "int"),
					valTpDato($frmListaCambioUnidades['hddIdEstadoAdicionalEntradaCambio'][$indice], "int"),
					valTpDato($frmListaCambioUnidades['hddMotivoCambio'][$indice], "text"),
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato("NOW()", "campo")
					);
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idContratoVentaCambioUnidad = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// AHORA ACTUALIZO VEHICULO VIEJO
				$sql = sprintf("SELECT * FROM an_unidad_fisica WHERE id_unidad_fisica = %s;",
					valTpDato($frmListaCambioUnidades['hddIdCambioUnidad'][$indice], "int"));
				$rs = mysql_query($sql);
				if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowUnidadFisica = mysql_fetch_assoc($rs);
				
				if(mysql_num_rows($rs) == 0){
					return $objResponse->alert(utf8_encode("El vehículo posee un estado que no es válido."));
				}
				
				// ACTUALIZA EL ESTADO DEL VEHICULO
				$updateSQL = sprintf("UPDATE an_unidad_fisica SET 
					kilometraje = %s,
					id_estado_adicional = %s
					WHERE id_unidad_fisica = %s;",
					valTpDato($frmListaCambioUnidades['hddKilometrajeEntradaCambio'][$indice], "int"),
					valTpDato($frmListaCambioUnidades['hddIdEstadoAdicionalEntradaCambio'][$indice], "int"),
					valTpDato($frmListaCambioUnidades['hddIdCambioUnidad'][$indice], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				//BUSCA CLAVE MOV DE TIPO DE CONTRATO PARA KARDEX
				$sql = sprintf("SELECT id_clave_movimiento_entrada FROM al_tipo_contrato WHERE id_tipo_contrato = %s;",
					valTpDato($frmDcto['lstTipoContrato'], "int"));
				$rs = mysql_query($sql);
				if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowTipoContrato = mysql_fetch_assoc($rs);
						
				//INSERTA KARDEX DEL VEHICULO ENTRADA
				$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, estadoKardex, fechaMovimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($rowUnidadFisica["id_uni_bas"], "int"),
					valTpDato($rowUnidadFisica["id_unidad_fisica"], "int"),
					valTpDato(2, "int"),// 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($rowTipoContrato["id_clave_movimiento_entrada"], "int"),
					valTpDato(3, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito, 3 = Alquiler
					valTpDato(1 , "real_inglesa"),
					valTpDato($rowUnidadFisica["costo_compra"], "real_inglesa"),
					valTpDato($rowUnidadFisica["costo_compra"], "real_inglesa"),
					valTpDato(0, "int"),// 0 entrada, 1 salida
					"NOW()"
					);
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				// AHORA EL VEHICULO NUEVO
				// COMPRUEBA EL ESTADO DEL VEHICULO
				$sql = sprintf("SELECT * FROM an_unidad_fisica WHERE estado_venta = 'ACTIVO FIJO' AND id_unidad_fisica = %s;",
					valTpDato($frmDcto['txtIdUnidadFisica'], "int"));
				$rs = mysql_query($sql);
				if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowUnidadFisica = mysql_fetch_assoc($rs);
				
				if(mysql_num_rows($rs) == 0){
					return $objResponse->alert(utf8_encode("El vehículo posee un estado que no es válido.."));
				}
				
				// ACTUALIZA EL ESTADO DEL VEHICULO
				$updateSQL = sprintf("UPDATE an_unidad_fisica SET 
					kilometraje = %s,
					id_estado_adicional = %s
					WHERE id_unidad_fisica = %s;",
					valTpDato($frmDcto['txtKilometrajeSalida'], "int"),
					valTpDato($frmDcto['lstEstadoAdicionalSalida'], "int"),
					valTpDato($frmDcto['txtIdUnidadFisica'], "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				//BUSCA CLAVE MOV DE TIPO DE CONTRATO PARA KARDEX
				$sql = sprintf("SELECT id_clave_movimiento_salida FROM al_tipo_contrato WHERE id_tipo_contrato = %s;",
					valTpDato($frmDcto['lstTipoContrato'], "int"));
				$rs = mysql_query($sql);
				if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowTipoContrato = mysql_fetch_assoc($rs);
						
				//INSERTA KARDEX DEL VEHICULO SALIDA
				$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, estadoKardex, fechaMovimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idDocumentoVenta, "int"),
					valTpDato($rowUnidadFisica["id_uni_bas"], "int"),
					valTpDato($rowUnidadFisica["id_unidad_fisica"], "int"),
					valTpDato(4, "int"),// 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($rowTipoContrato["id_clave_movimiento_salida"], "int"),
					valTpDato(3, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito, 3 = Alquiler
					valTpDato(1 , "real_inglesa"),
					valTpDato($rowUnidadFisica["costo_compra"], "real_inglesa"),
					valTpDato($rowUnidadFisica["costo_compra"], "real_inglesa"),
					valTpDato(1, "int"),// 0 entrada, 1 salida
					"NOW()"
					);
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
			}
		}
		
		$updateSQL = sprintf("UPDATE al_contrato_venta SET
			id_cliente = %s,
			id_cliente_pago = %s,
			id_unidad_fisica = %s,
			id_tipo_contrato = %s,
			id_estado_adicional_entrada = %s,
			id_moneda = %s,
			condicion_pago = %s,
			id_empleado_creador = %s,
			observacion = %s,
			subtotal = %s,
			porcentaje_descuento = %s,
			subtotal_descuento = %s,
			monto_exento = %s,
			total_contrato = %s,
			fecha_vencimiento = %s,
			kilometraje_salida = %s, 
			kilometraje_entrada = %s, 
			nivel_combustible_salida = %s, 
			nivel_combustible_entrada = %s, 
			fecha_salida = %s, 
			fecha_entrada = %s, 
			fecha_final = %s,
			dias_contrato = %s, 
			dias_sobre_tiempo = %s, 
			dias_bajo_tiempo = %s, 
			dias_total = %s
		WHERE id_contrato_venta = %s;",
			valTpDato($idCliente, "int"),
			valTpDato($idClientePago, "int"),
			valTpDato($frmDcto['txtIdUnidadFisica'], "int"),
			valTpDato($frmDcto['lstTipoContrato'], "int"),
			valTpDato($frmDcto['lstEstadoAdicionalEntrada'], "int"),
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($frmDcto['lstTipoPago'], "int"),
			valTpDato($frmDcto['txtIdEmpleado'], "int"),			
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
			valTpDato($fechaVencimientoContrato, "date"),
			valTpDato($frmDcto['txtKilometrajeSalida'], "int"),
			valTpDato($frmDcto['txtKilometrajeEntrada'], "int"),
			valTpDato($frmDcto['lstCombustibleSalida'], "real_inglesa"),
			valTpDato($frmDcto['lstCombustibleEntrada'], "real_inglesa"),
			valTpDato($fechaSalida, "date"),
			valTpDato($fechaEntrada, "date"),
			valTpDato($fechaEntradaFinal, "date"),
			valTpDato($frmDcto['txtDiasContrato'], "int"),
			valTpDato($frmDcto['txtDiasSobreTiempo'], "int"),
			valTpDato($frmDcto['txtDiasBajoTiempo'], "int"),
			valTpDato($frmDcto['txtDiasTotal'], "int"),			
			valTpDato($idDocumentoVenta, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"al_contrato_venta_list","insertar")) { return $objResponse; }
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(48, "int"), // 48 = Contrato Alquiler
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		if($rowNumeracion['numero_actual'] == ""){ return $objResponse->alert("No se ha configurado la numeracion de contratos"); }
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		// INSERTA LOS DATOS DEL CONTRATO
		$insertSQL = sprintf("INSERT INTO al_contrato_venta (numero_contrato_venta, id_empresa, id_tipo_contrato, id_cliente, id_cliente_pago, id_unidad_fisica, id_estado_adicional_salida, id_moneda, condicion_pago, estatus_contrato_venta, id_empleado_creador, observacion, subtotal, porcentaje_descuento, subtotal_descuento, monto_exento, total_contrato, fecha_vencimiento, kilometraje_salida, nivel_combustible_salida, fecha_salida, fecha_entrada, dias_contrato)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($numeroActual, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($frmDcto['lstTipoContrato'], "int"),
			valTpDato($idCliente, "int"),
			valTpDato($idClientePago, "int"),
			valTpDato($frmDcto['txtIdUnidadFisica'], "int"),
			valTpDato($frmDcto['lstEstadoAdicionalSalida'], "int"),
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($frmDcto['lstTipoPago'], "int"),
			valTpDato(1, "int"), // 1 = Contrato Activo
			valTpDato($frmDcto['txtIdEmpleado'], "int"),
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
			valTpDato($fechaVencimientoContrato, "date"),
			valTpDato($frmDcto['txtKilometrajeSalida'], "int"),
			valTpDato($frmDcto['lstCombustibleSalida'], "real_inglesa"),
			valTpDato($fechaSalida, "date"),
			valTpDato($fechaEntrada, "date"),
			valTpDato($frmDcto['txtDiasContrato'], "int")
			);	
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idDocumentoVenta = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// COMPRUEBA EL ESTADO DEL VEHICULO
		$sql = sprintf("SELECT * FROM an_unidad_fisica WHERE estado_venta = 'ACTIVO FIJO' AND id_unidad_fisica = %s;",
			valTpDato($frmDcto['txtIdUnidadFisica'], "int"));
		$rs = mysql_query($sql);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowUnidadFisica = mysql_fetch_assoc($rs);
		
		if(mysql_num_rows($rs) == 0){
			return $objResponse->alert(utf8_encode("El vehículo posee un estado que no es válido..."));
		}
		
		// ACTUALIZA EL ESTADO DEL VEHICULO
		$updateSQL = sprintf("UPDATE an_unidad_fisica SET 
			kilometraje = %s,
			id_estado_adicional = %s
			WHERE id_unidad_fisica = %s;",
			valTpDato($frmDcto['txtKilometrajeSalida'], "int"),
			valTpDato($frmDcto['lstEstadoAdicionalSalida'], "int"),
			valTpDato($frmDcto['txtIdUnidadFisica'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		//BUSCA CLAVE MOV DE TIPO DE CONTRATO PARA KARDEX
		$sql = sprintf("SELECT id_clave_movimiento_salida FROM al_tipo_contrato WHERE id_tipo_contrato = %s;",
			valTpDato($frmDcto['lstTipoContrato'], "int"));
		$rs = mysql_query($sql);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowTipoContrato = mysql_fetch_assoc($rs);
				
		//INSERTA KARDEX DEL VEHICULO SALIDA
		$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, estadoKardex, fechaMovimiento)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idDocumentoVenta, "int"),
			valTpDato($rowUnidadFisica["id_uni_bas"], "int"),
			valTpDato($rowUnidadFisica["id_unidad_fisica"], "int"),
			valTpDato(4, "int"),// 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
			valTpDato($rowTipoContrato["id_clave_movimiento_salida"], "int"),
			valTpDato(3, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito, 3 = Alquiler
			valTpDato(1 , "real_inglesa"),
			valTpDato($rowUnidadFisica["costo_compra"], "real_inglesa"),
			valTpDato($rowUnidadFisica["costo_compra"], "real_inglesa"),
			valTpDato(1, "int"),// 0 entrada, 1 salida
			"NOW()"
			);
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	//BORRAR PRECIOS TARIFAS
	if(strlen($frmDcto["hddIdDetPrecioEliminar"]) > 0){		
		$deleteSQL = sprintf("DELETE FROM al_contrato_venta_precio WHERE id_contrato_venta_precio IN (%s);",
			valTpDato($frmDcto["hddIdDetPrecioEliminar"], "campo"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__.$deleteSQL); }
	}
	
	//BORRAR ACCESORIOS / ADICIONALES
	if(strlen($frmDcto["hddIdDetAccesorioEliminar"]) > 0){
		$deleteSQL = sprintf("DELETE FROM al_contrato_venta_accesorio WHERE id_contrato_venta_accesorio IN (%s);",
			valTpDato($frmDcto["hddIdDetAccesorioEliminar"], "campo"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	// INSERTA EL PRECIO / TARIFA
	foreach($frmListaPrecio['hddIdDetallePrecioContrato'] as $indice => $idDetallePrecioContrato){
		if($idDetallePrecioContrato == ""){
			$insertSQL = sprintf("INSERT INTO al_contrato_venta_precio (id_contrato_venta, id_precio, id_precio_detalle, id_tipo_precio, dias, precio, dias_calculado, total_precio)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idDocumentoVenta, "int"),
				valTpDato($frmListaPrecio['hddIdPrecio'][$indice], "int"),
				valTpDato($frmListaPrecio['hddIdPrecioDetalle'][$indice], "int"),
				valTpDato($frmListaPrecio['hddIdTipoPrecio'][$indice], "int"),
				valTpDato($frmListaPrecio['hddDiasPrecio'][$indice], "int"),
				valTpDato($frmListaPrecio['hddPrecio'][$indice], "real_inglesa"),
				valTpDato($frmListaPrecio['hddDiasPrecioCalculado'][$indice], "int"),
				valTpDato($frmListaPrecio['hddTotalPrecio'][$indice], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idContratoVentaPrecio = mysql_insert_id();
			
			$arrayIdPrecioImpuesto = array_filter(explode("|", $frmListaPrecio['hddIdIvaPrecio'][$indice]));
			$arrayPorcPrecioImpuesto = array_filter(explode("|", $frmListaPrecio['hddPorcentajeIvaPrecio'][$indice]));
			
			foreach($arrayIdPrecioImpuesto as $indiceImpuesto => $idImpuesto){
				$insertSQL = sprintf("INSERT INTO al_contrato_venta_precio_impuesto (id_contrato_venta_precio, id_impuesto, impuesto)
				VALUE (%s, %s, %s);",
					valTpDato($idContratoVentaPrecio, "int"),
					valTpDato($arrayIdPrecioImpuesto[$indiceImpuesto], "int"),
					valTpDato($arrayPorcPrecioImpuesto[$indiceImpuesto], "real_inglesa"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}
		}
	}
	
	//INSERTA ACCESORIOS
	foreach($frmListaAccesorio['hddIdDetalleAccesorioContrato'] as $indice => $idDetalleAccesorioContrato){
		if($idDetalleAccesorioContrato == ""){
			$insertSQL = sprintf("INSERT INTO al_contrato_venta_accesorio (id_contrato_venta, id_accesorio, id_tipo_accesorio, cantidad, precio, costo)
			VALUE (%s, %s, %s, %s, %s, %s);",
				valTpDato($idDocumentoVenta, "int"),
				valTpDato($frmListaAccesorio['hddIdAccesorio'][$indice], "int"),
				valTpDato($frmListaAccesorio['hddIdTipoAccesorio'][$indice], "int"),
				valTpDato($frmListaAccesorio['hddCantidadAccesorio'][$indice], "real_inglesa"),
				valTpDato($frmListaAccesorio['hddPrecioAccesorio'][$indice], "real_inglesa"),
				valTpDato($frmListaAccesorio['hddCostoAccesorio'][$indice], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idContratoVentaAccesorio = mysql_insert_id();
			
			$arrayIdAccesorioImpuesto = array_filter(explode("|", $frmListaAccesorio['hddIdIvaAccesorio'][$indice]));
			$arrayPorcPrecioImpuesto = array_filter(explode("|", $frmListaAccesorio['hddPorcentajeIvaAccesorio'][$indice]));
			
			foreach($arrayIdAccesorioImpuesto as $indiceImpuesto => $idImpuesto){
				$insertSQL = sprintf("INSERT INTO al_contrato_venta_accesorio_impuesto (id_contrato_venta_accesorio, id_impuesto, impuesto)
				VALUE (%s, %s, %s);",
					valTpDato($idContratoVentaAccesorio, "int"),
					valTpDato($arrayIdAccesorioImpuesto[$indiceImpuesto], "int"),
					valTpDato($arrayPorcPrecioImpuesto[$indiceImpuesto], "real_inglesa"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}
		}
	}
	
	//INSERTA ADICIONALES
	foreach($frmListaAdicional['hddIdDetalleAccesorioContrato'] as $indice => $idDetalleAccesorioContrato){
		if($idDetalleAccesorioContrato == ""){
			$insertSQL = sprintf("INSERT INTO al_contrato_venta_accesorio (id_contrato_venta, id_accesorio, id_tipo_accesorio, cantidad, precio, costo)
			VALUE (%s, %s, %s, %s, %s, %s);",
				valTpDato($idDocumentoVenta, "int"),
				valTpDato($frmListaAdicional['hddIdAccesorio'][$indice], "int"),
				valTpDato($frmListaAdicional['hddIdTipoAccesorio'][$indice], "int"),
				valTpDato($frmListaAdicional['hddCantidadAccesorio'][$indice], "real_inglesa"),
				valTpDato($frmListaAdicional['hddPrecioAccesorio'][$indice], "real_inglesa"),
				valTpDato($frmListaAdicional['hddCostoAccesorio'][$indice], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idContratoVentaAccesorio = mysql_insert_id();
			
			$arrayIdAccesorioImpuesto = array_filter(explode("|", $frmListaAdicional['hddIdIvaAccesorio'][$indice]));
			$arrayPorcPrecioImpuesto = array_filter(explode("|", $frmListaAdicional['hddPorcentajeIvaAccesorio'][$indice]));
			
			foreach($arrayIdAccesorioImpuesto as $indiceImpuesto => $idImpuesto){
				$insertSQL = sprintf("INSERT INTO al_contrato_venta_accesorio_impuesto (id_contrato_venta_accesorio, id_impuesto, impuesto)
				VALUE (%s, %s, %s);",
					valTpDato($idContratoVentaAccesorio, "int"),
					valTpDato($arrayIdAccesorioImpuesto[$indiceImpuesto], "int"),
					valTpDato($arrayPorcPrecioImpuesto[$indiceImpuesto], "real_inglesa"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}
		}
	}
	
	// ELIMINA LOS IMPUESTOS DEL CONTRATO
	$deleteSQL = sprintf("DELETE FROM al_contrato_venta_iva WHERE id_contrato_venta = %s;",
		valTpDato($idDocumentoVenta, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// INSERTA LOS IMPUESTOS DEL CONTRATO
	foreach ($frmTotalDcto['hddIdIva'] as $indice => $idIva) {
		$insertSQL = sprintf("INSERT INTO al_contrato_venta_iva (id_contrato_venta, base_imponible, subtotal_iva, id_iva, iva)
		VALUE (%s, %s, %s, %s, %s);",
			valTpDato($idDocumentoVenta, "int"),
			valTpDato($frmTotalDcto['txtBaseImpIva'][$indice], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalIva'][$indice], "real_inglesa"),
			valTpDato($idIva, "int"),
			valTpDato($frmTotalDcto['txtIva'][$indice], "real_inglesa"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	if(esFinalizar()){
		if($rowContrato["fecha_cierre"] == ""){
			//CAMBIA ESTADO DEL VEHICULO
			$sql = sprintf("UPDATE an_unidad_fisica SET 
				kilometraje = %s,
				id_estado_adicional = %s
				WHERE id_unidad_fisica = %s;",
				valTpDato($frmDcto['txtKilometrajeEntrada'], "int"),
				valTpDato($frmDcto['lstEstadoAdicionalEntrada'], "int"),
				valTpDato($frmDcto['txtIdUnidadFisica'], "int"));
			$rs = mysql_query($sql);
			if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	
		$Result1 = cerrarContrato($idDocumentoVenta);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		if($Result1[1] != ""){ $objResponse->alert($Result1[1]); }
		if($Result1[2] != ""){ $objResponse->script($Result1[2]); }
	}
	
	mysql_query("COMMIT;");

	$objResponse->alert(utf8_encode("Contrato Guardado con Éxito"));
	
	if($frmDcto['hddIdContrato'] == ""){//si es nuevo
		$objResponse->script("verVentana('reportes/al_contrato_venta_pdf.php?valBusq=".$idDocumentoVenta."', 960, 550)");
	}
	
	$objResponse->script("byId('btnCancelar').click();");
	
	return $objResponse;
}

function insertarAccesorio($idDetalleAccesorioContrato, $idAccesorio, $idTipoAccesorio, $nombreAccesorio, $descripcionAccesorio, $cantidadAccesorio, $precioAccesorio, $costoAccesorio, $arrayIva){
	$objResponse = new xajaxResponse();

	if($idTipoAccesorio == 1){// 1 = Adicional, 2 = Accesorio, 3 = Contrato
		$itmPie = "#trItmPieAdicional";
		$checkboxClase = "checkboxAdicional";
	}elseif($idTipoAccesorio == 2){
		$itmPie = "#trItmPieAccesorio";
		$checkboxClase = "checkboxAccesorio";
	}
	
	foreach($arrayIva as $idIva => $arrayIvaCargado){
		$arrayIdIva[] = $arrayIvaCargado["idIva"];
		$arrayPorcentajesIva[] = $arrayIvaCargado["iva"];
	}

	$htmlItmPie = sprintf("$('%s').before('".
		"<tr>".
			"<td><input type=\"checkbox\" value=\"%s\" class=\"%s\" />".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s".
				"<input type=\"hidden\" name=\"hddIdDetalleAccesorioContrato[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdTipoAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddCantidadAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddPrecioAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddCostoAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddTotalAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdIvaAccesorio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddPorcentajeIvaAccesorio[]\" value=\"%s\" />".
			"</td>".
		"</tr>');",
		$itmPie,
		$idDetalleAccesorioContrato, $checkboxClase,
		//td
		utf8_encode($nombreAccesorio),
		utf8_encode($descripcionAccesorio),
		number_format($cantidadAccesorio, 2, ".", ","),
		number_format($precioAccesorio, 2, ".", ","),
		implode(" <br> ", $arrayPorcentajesIva),
		number_format($cantidadAccesorio * $precioAccesorio, 2, ".", ","),
		//hidden
		$idDetalleAccesorioContrato,
		$idAccesorio,
		$idTipoAccesorio,
		$cantidadAccesorio,
		$precioAccesorio,
		$costoAccesorio,
		round($cantidadAccesorio * $precioAccesorio,2),
		implode("|", $arrayIdIva),
		implode("|", $arrayPorcentajesIva));

	$objResponse->script($htmlItmPie);
	
	if($idDetalleAccesorioContrato == ""){//cuando es nuevo, calcular total
		$objResponse->script("calcularDcto();");
	}
	
	return $objResponse;
}

function insertarCambioUnidad($idDetalleCambioUnidad, $idUnidadFisica, $kilometrajeEntradaCambio, $combustibleEntradaCambio, $idEstadoAdicionalEntradaCambio, $motivoCambio, $fechaCambio, $idEmpleado){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT
			uni_fis.placa,
			clase.nom_clase,
			uni_bas.nom_uni_bas,
			vw_iv_modelo.nom_ano,
			vw_iv_modelo.nom_modelo,
			vw_iv_modelo.nom_marca
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)			
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
		WHERE uni_fis.id_unidad_fisica = %s", 
		valTpDato($idUnidadFisica, "int"));
	$rs = mysql_query($sql);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$row = mysql_fetch_assoc($rs);
	
	$informacionUnidad = "<b>Placa:</b> ".$row['placa']."<br><b>Unidad:</b> ".$row['nom_modelo']." ".$row['nom_uni_bas']."<br><b>Clase:</b> ".$row['nom_clase'];
	
	$sql = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado
	WHERE vw_pg_empleado.id_empleado = %s", 
		valTpDato($idEmpleado, "int"));
	$rs = mysql_query($sql);
	if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$rowEmpleado = mysql_fetch_assoc($rs);
	
	$htmlItmPie = sprintf("$('#trItmPieCambioUnidad').before('".
		"<tr>".
			"<td align=\"left\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"left\">%s".
				"<input type=\"hidden\" name=\"hddIdDetalleCambioUnidad[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdCambioUnidad[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddKilometrajeEntradaCambio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddCombustibleEntradaCambio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdEstadoAdicionalEntradaCambio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddMotivoCambio[]\" value=\"%s\" />".
			"</td>".
		"</tr>');",
		//td
		utf8_encode($informacionUnidad),
		date(spanDateFormat." h:i a", strtotime($fechaCambio)),
		utf8_encode($rowEmpleado['nombre_empleado']),
		(addslashes($motivoCambio)),//al cargar de bd necesita utf8 encode pero no cuando es nuevo
		//hidden
		$idDetalleCambioUnidad,
		$idUnidadFisica,
		$kilometrajeEntradaCambio,
		$combustibleEntradaCambio,
		$idEstadoAdicionalEntradaCambio,
		(addslashes($motivoCambio)));

	$objResponse->script($htmlItmPie);
	
	if($idDetalleCambioUnidad == ""){//cuando es un nuevo cambio
		$objResponse->script("byId('btnCancelarCambiarUnidad').click();
		byId('hddVehiculoYaCambiado').value = '1';
		
		byId('txtIdUnidadFisica').className = 'inputHabilitado';
		byId('txtIdUnidadFisica').value = '';
		byId('txtPlacaVehiculo').value = '';
		byId('txtSerialCarroceriaVehiculo').value = '';
		byId('txtMarcaVehiculo').value = '';
		byId('txtModeloVehiculo').value = '';
		byId('txtCondicionVehiculo').value = '';
		byId('hddIdClase').value = '';
		byId('txtClaseVehiculo').value = '';
		byId('txtAnoVehiculo').value = '';
		byId('txtColorVehiculo').value = '';
		byId('txtUnidadBasica').value = '';
		byId('txtAlmacenVehiculo').value = '';
		byId('txtKilometrajeVehiculo').value = '';
		
		byId('txtKilometrajeSalida').value = '';
		byId('lstCombustibleSalida').value = '';
		setTimeout(function(){
			byId('aListarUnidadFisica').click();
		}, 2000);
		");
	}
	
	return $objResponse;
}

function insertarPrecio($idDetallePrecioContrato, $idPrecio, $nombrePrecio, $idPrecioDetalle, $descripcion, $precio, $diasPrecio, $idTipoPrecio, $diasCalculado, $totalPrecio, $arrayIva){
	$objResponse = new xajaxResponse();
	
	foreach($arrayIva as $idIva => $arrayIvaCargado){
		$arrayIdIva[] = $arrayIvaCargado["idIva"];
		$arrayPorcentajesIva[] = $arrayIvaCargado["iva"];
	}
	
	$htmlItmPie = sprintf("$('#trItmPiePrecio').before('".
		"<tr>".
			"<td><input type=\"checkbox\" value=\"%s\" class=\"checkboxPrecio\" />".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s".
				"<input type=\"hidden\" name=\"hddIdDetallePrecioContrato[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdPrecioDetalle[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdTipoPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddDiasPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddDiasPrecioCalculado[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddTotalPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdIvaPrecio[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddPorcentajeIvaPrecio[]\" value=\"%s\" />".
			"</td>".
		"</tr>');",
		$idDetallePrecioContrato,
		//td
		utf8_encode($nombrePrecio),
		utf8_encode($descripcion),		
		$diasCalculado,
		number_format($precio, 2, ".", ","),
		implode(" <br> ", $arrayPorcentajesIva),
		number_format($totalPrecio, 2, ".", ","),
		//hidden
		$idDetallePrecioContrato,
		$idPrecio,
		$idPrecioDetalle,
		$idTipoPrecio,
		$diasPrecio,
		$precio,
		$diasCalculado,
		$totalPrecio,
		implode("|", $arrayIdIva),
		implode("|", $arrayPorcentajesIva));

	$objResponse->script($htmlItmPie);
	
	return $objResponse;	
}

function listaAccesorio($pageNum = 0, $campOrd = "des_accesorio", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
			
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo IN (4)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_accesorio = %s",
				valTpDato($valCadBusq[0],"int"));
			
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_accesorio LIKE %s
			OR des_accesorio LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
        
	$query = sprintf("SELECT * FROM an_accesorio %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "20%", $pageNum, "nom_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "60%", $pageNum, "des_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "10%", $pageNum, "iva_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "10%", $pageNum, "precio_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		                
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";			
			$htmlTb .= sprintf("<td><button title=\"Seleccionar\" onclick=\"xajax_asignarAccesorio(%s, xajax.getFormValues('frmListaAccesorio'), xajax.getFormValues('frmListaAdicional'), xajax.getFormValues('frmDcto'));\" type=\"button\"><img src=\"../img/iconos/tick.png\"></button></td>",
				$row['id_accesorio']);
			$htmlTb .= "<td>".utf8_encode($row['nom_accesorio'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['des_accesorio'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(($row['iva_accesorio'] == 1) ? "Si" : "No")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_accesorio'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lci, ci) LIKE %s
		OR CONCAT_WS('', lci, ci) LIKE %s
		OR CONCAT_Ws(' ', nombre, apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.credito
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	if($valCadBusq[2] == 1){
		$nombreObjeto = "Pago";
	}
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$nombreObjeto."', '".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
		OR vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_empleado.nombre_cargo LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanCI));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleado('".$row['id_empleado']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPrecio($pageNum = 0, $campOrd = "id_precio", $tpOrd = "ASC", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("al_precios_clase.id_clase = %s",
			valTpDato($valCadBusq[0],"int"));
				
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_precio LIKE %s
		OR descripcion_precio LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT al_precios.* 
						FROM al_precios_clase
						INNER JOIN al_precios ON al_precios_clase.id_precio = al_precios.id_precio
						%s", $sqlBusq);
	
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPrecio", "15%", $pageNum, "nombre_precio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaPrecio", "30%", $pageNum, "descripcion_precio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaPrecio", "50%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "Precios / Tiempos");				
		$htmlTh .= ordenarCampo("xajax_listaPrecio", "5%", $pageNum, "iva_precio", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$sqlDetalle = sprintf("SELECT
									det.descripcion,
									det.precio,
									det.dias,
									tipo.descripcion AS tipo_precio
								FROM al_precios_detalle det
								INNER JOIN al_tipo_precio tipo ON det.id_tipo_precio = tipo.id_tipo_precio
								WHERE id_precio = %s",
						$row["id_precio"]);
		$rsDetalle = mysql_query($sqlDetalle);
		if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$tieneDetalle = mysql_num_rows($rsDetalle);
		
		$tablaDetalle = "";
		if($tieneDetalle){
			$tablaDetalle .= "<table border=\"0\" width=\"100%\" class=\"texto_9px\">";
			$tablaDetalle .= "<tr align=\"center\" class=\"tituloCampo textoNegrita_9px\">";
				$tablaDetalle .= "<td width=\"40%\">Descripci&oacute;n</td>";
				$tablaDetalle .= "<td width=\"25%\">Precio</td>";
				$tablaDetalle .= "<td width=\"15%\">D&iacute;as</td>";
				$tablaDetalle .= "<td width=\"20%\">Tipo</td>";
			$tablaDetalle .= "</tr>";
			while($rowDetalle = mysql_fetch_assoc($rsDetalle)){
				$tablaDetalle .= "<tr align=\"center\">";
					$tablaDetalle .= "<td width=\"40%\">".utf8_encode($rowDetalle["descripcion"])."</td>";
					$tablaDetalle .= "<td width=\"25%\">".number_format($rowDetalle["precio"], 2, ".", ",")."</td>";
					$tablaDetalle .= "<td width=\"15%\">".$rowDetalle["dias"]."</td>";
					$tablaDetalle .= "<td width=\"20%\">".utf8_encode($rowDetalle["tipo_precio"])."</td>";
				$tablaDetalle .= "</tr>";
			}
			$tablaDetalle .= "</table>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><button title=\"Seleccionar\" onclick=\"xajax_asignarPrecio(%s, xajax.getFormValues('frmListaPrecio'), xajax.getFormValues('frmDcto'));\" type=\"button\"><img src=\"../img/iconos/tick.png\"></button></td>", $row['id_precio']);
			$htmlTb .= "<td>".utf8_encode($row['nombre_precio'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_precio'])."</td>";
			$htmlTb .= "<td>".$tablaDetalle."</td>";
			
			$htmlTb .= "<td align=\"center\">".utf8_encode(($row['iva_precio'] == 1) ? "Si" : "No")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPrecio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPrecio(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("divListaPrecio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('ACTIVO FIJO')");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_estado_adicional IN (1)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = alm.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		vw_iv_modelo.id_uni_bas,
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
		
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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "1%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Uni");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialMotor));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, "Color");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "nom_clase", $campOrd, $tpOrd, $valBusq, $maxRows, "Clase");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Venta");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "20%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "15%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Almac&eacute;n");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"15\">".utf8_encode($row['vehiculo'])."</td>";
		$htmlTb .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
			valTpDato($row['id_uni_bas'], "int"));
		
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.placa,
			(CASE uni_fis.id_condicion_unidad
				WHEN 1 THEN	'NUEVO'
				WHEN 2 THEN	'USADO'
				WHEN 3 THEN	'USADO PARTICULAR'
			END) AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			clase.nom_clase,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			alm.nom_almacen,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $sqlBusq2);
		$queryUnidadFisica .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayTotal = NULL;
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			switch($rowUnidadFisica['estado_venta']) {
				case "SINIESTRADO" : $class = "class=\"divMsjError\""; break;
				case "DISPONIBLE" : $class = "class=\"divMsjInfo\""; break;
				case "RESERVADO" : $class = "class=\"divMsjAlerta\""; break;
				case "VENDIDO" : $class = "class=\"divMsjInfo3\""; break;
				case "ENTREGADO" : $class = "class=\"divMsjInfo4\""; break;
				case "ACTIVO FIJO" : $class = "class=\"divMsjInfo5\""; break;
				default : $class = ""; break;
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= sprintf("<td><button title=\"Seleccionar\" onclick=\"xajax_asignarUnidadFisica(%s);\" type=\"button\"><img src=\"../img/iconos/tick.png\"></button></td>",
							$rowUnidadFisica['id_unidad_fisica']);
				$htmlTb .= "<td align=\"center\">".($rowUnidadFisica['id_unidad_fisica'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_carroceria'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr class=\"textoNegrita_10px\">";
						$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['condicion_unidad'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['color_externo1'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_clase'])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($rowUnidadFisica['placa'])."</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">";
					$htmlTb .= utf8_encode($rowUnidadFisica['estado_venta']);
					$htmlTb .= ($rowUnidadFisica['estado_venta'] == "RESERVADO" && $rowUnidadFisica['estado_compra'] != "REGISTRADO") ? "<br><b>(".utf8_encode($rowUnidadFisica['estado_compra']).")</b>" : "";
					$htmlTb .= ($rowUnidadFisica['id_activo_fijo'] > 0) ? "<br><span class=\"textoNegrita_9px\">C&oacute;digo: ".$rowUnidadFisica['id_activo_fijo']."</span>" : "";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
			$htmlTb .= "</tr>";
		}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	return $objResponse;
}

function validarPermiso($frmPermiso) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		if ($frmPermiso['hddModulo'] == "al_contrato_venta_form_descuento") {
			$objResponse->assign("hddConfig500","value",1);
			$objResponse->script("byId('txtDescuento').readOnly = false;");
			$objResponse->script("byId('aDesbloquearDescuento').style.display = 'none';");
			$objResponse->script("
			byId('txtDescuento').focus();
			byId('txtDescuento').select();");
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
	}
	
	$objResponse->script("byId('btnCancelarPermiso').click();");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarAccesorio");
$xajax->register(XAJAX_FUNCTION,"asignarCambioUnidad");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarPrecio");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"buscarAccesorio");
$xajax->register(XAJAX_FUNCTION,"buscarAdicional");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarPrecio");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"calcularTiempo");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"cargarLstEstadoAdicionalEntrada");
$xajax->register(XAJAX_FUNCTION,"cargarLstEstadoAdicionalEntradaCambio");
$xajax->register(XAJAX_FUNCTION,"cargarLstEstadoAdicionalSalida");
$xajax->register(XAJAX_FUNCTION,"cargarLstTipoContrato");
$xajax->register(XAJAX_FUNCTION,"cargarLstTipoPago");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarAccesorio");
$xajax->register(XAJAX_FUNCTION,"insertarCambioUnidad");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaAccesorio");
$xajax->register(XAJAX_FUNCTION,"listaPrecio");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function cerrarContrato($idContrato){
	
	$sql = sprintf("SELECT 
				contrato.*, 
				tipo_contrato.modo_factura,
				tipo_contrato.id_clave_movimiento_entrada
			FROM al_contrato_venta contrato
			INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
			WHERE contrato.id_contrato_venta = %s",
		valTpDato($idContrato, "int"));
	$rs = mysql_query($sql);
	if (!$rs) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$rowContrato = mysql_fetch_assoc($rs);
	
	$idEmpresa = $rowContrato["id_empresa"];
	
	//1 = Contrato Activo, 2 = Contrato Cerrado, 3 = Facturado, 4 = Nota de Crédito, 5 = Vale de Salida, 6 = Vale de Entrada
	if($rowContrato["modo_factura"] == 1){// 1 = Factura, 2 = Vale de Salida	
		$estatusCierre = 2;	
		$mensaje = "Contrato cerrado, ahora se puede facturar";
	}else if($rowContrato["modo_factura"] == 2){// 1 = Factura, 2 = Vale de Salida
		$estatusCierre = 5;
		$mensaje = "Contrato cerrado por vale de salida";
		
		//VERIFICAR SI YA SE HIZO VALE DE SALIDA
		$sql = sprintf("SELECT * FROM al_vale_salida WHERE id_contrato_venta = %s",
			valTpDato($idContrato, "int"));
		$rs = mysql_query($sql);
		if (!$rs) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		if(mysql_num_rows($rs) > 0){ return array(false, "Este contrato ya fue cerrado anteriormente como vale de salida"); }
		
		// NUMERACION DE VALES DE SALIDA
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(50, "int"), // 50 = Vale Salida Alquiler
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		if($rowNumeracion['numero_actual'] == ""){ return array(false, "No se ha configurado la numeracion de vales de salida"); }
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$sql = sprintf("INSERT INTO al_vale_salida (numero_vale_salida, fecha_vale_salida, estado_vale_salida, id_contrato_venta, id_empresa, id_cliente, subtotal, porcentaje_descuento, subtotal_descuento, monto_exento, monto_exonerado, total_vale_salida, id_empleado_creador)
			SELECT
				%s,
				%s,
				%s,
				contrato.id_contrato_venta,
				contrato.id_empresa,
				contrato.id_cliente_pago,
				contrato.subtotal,
				contrato.porcentaje_descuento,
				contrato.subtotal_descuento,
				contrato.monto_exento,
				contrato.monto_exonerado,
				contrato.total_contrato,
				%s
			FROM al_contrato_venta contrato
			WHERE contrato.id_contrato_venta = %s",
			valTpDato($numeroActual, "int"),
			valTpDato("NOW()", "campo"),
			valTpDato(0, "int"), //0 = GENERADO, 1 = DEVUELTO
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
			valTpDato($idContrato, "int"));
		$rs = mysql_query($sql);
		if (!$rs) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idValeSalida = mysql_insert_id();
		
		$script = "verVentana('reportes/al_vale_salida_pdf.php?valBusq=".$idValeSalida."', 960, 550)";
	}else{
		return array(false, "No se ha condigurado modo de factura para el tipo de contrato ".$rowContrato["modo_factura"]);
	}
	
	$sql = sprintf("UPDATE al_contrato_venta SET 
				estatus_contrato_venta = %s,
				id_empleado_cierre = %s,
				fecha_cierre = %s
			WHERE id_contrato_venta = %s",
		valTpDato($estatusCierre, "int"),
		valTpDato($_SESSION["idEmpleadoSysGts"], "int"),
		valTpDato("NOW()", "campo"),
		valTpDato($idContrato, "int"));
	$rs = mysql_query($sql);
	if (!$rs) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$sql = sprintf("SELECT * FROM an_unidad_fisica WHERE id_unidad_fisica = %s;",
		valTpDato($rowContrato['id_unidad_fisica'], "int"));
	$rs = mysql_query($sql);
	if (!$rs) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$rowUnidadFisica = mysql_fetch_assoc($rs);
	
	//cambia los estados cuando esta cerrado, verifico si tiene cierre debido a que la pueden devolver para cargar y volver a cerrar, en ese caso no debe modificarse ningun estatus
	if($rowContrato["fecha_cierre"] == ""){// && $rowUnidadFisica["estado_venta"] == "ALQUILADO" 		
		//INSERTA KARDEX DEL VEHICULO ENTRADA
		$sql = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, estadoKardex, fechaMovimiento)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idContrato, "int"),
			valTpDato($rowUnidadFisica["id_uni_bas"], "int"),
			valTpDato($rowUnidadFisica["id_unidad_fisica"], "int"),
			valTpDato(2, "int"),// 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
			valTpDato($rowContrato["id_clave_movimiento_entrada"], "int"),
			valTpDato(3, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito, 3 = Alquiler
			valTpDato(1 , "real_inglesa"),
			valTpDato($rowUnidadFisica["costo_compra"], "real_inglesa"),
			valTpDato($rowUnidadFisica["costo_compra"], "real_inglesa"),
			valTpDato(0, "int"),// 0 entrada, 1 salida
			"NOW()"
			);	
		$rs = mysql_query($sql);
		if (!$rs) { return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	return array(true, $mensaje, $script);
}

function diasEntreFechas($fecha1, $fecha2){
	$datetime1 = new DateTime($fecha1);
	$datetime2 = new DateTime($fecha2);
	$interval = $datetime1->diff($datetime2);
	return $interval->days;
}

function esFinalizar(){
	if(isset($_GET["acc"]) && $_GET["acc"] == "1"){
		return true;
	}else{
		return false;
	}
}

function iva(){
    $arrayIva = array();
    $query = sprintf("SELECT idIva, iva, observacion
                      FROM pg_iva 
                      WHERE tipo = 6 AND activo = 1 AND estado = 1");//activo = predeterminado
    $rs = mysql_query($query);
    if(!$rs) { return array(false, mysql_error()."\nLinea: ".__LINE__."\nArchivo: ".__FILE__); }
    
    while($row = mysql_fetch_assoc($rs)){
        $arrayIva[$row["idIva"]] = array("idIva" => $row["idIva"],
                                         "iva" => $row["iva"],
                                         "observacion" => $row["observacion"]);
    }
    
    return array(true, $arrayIva);
}

function fecha($fechaTiempo){
	if($fechaTiempo != ""){
		$fechaTiempo = date(spanDateFormat,strtotime($fechaTiempo));
	}	
	return $fechaTiempo;
}

function tiempo($fechaTiempo){
	if($fechaTiempo != ""){
		$fechaTiempo = date("h:i A",strtotime($fechaTiempo));
	}	
	return $fechaTiempo;
}

function fechaTiempo($fechaTiempo){
	if($fechaTiempo != ""){
		$fechaTiempo = date(spanDateFormat." h:i A",strtotime($fechaTiempo));
	}	
	return $fechaTiempo;
}

?>