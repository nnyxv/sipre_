<?php


function abrirPortadaCaja($frmVerificacionPortadaCaja){
	$objResponse = new xajaxResponse();
	
	$idAbrir = $frmVerificacionPortadaCaja['hddIdAbrir'];
	$idApertura = $frmVerificacionPortadaCaja['hddIdAperturaPortada'];
	$idCierre = $frmVerificacionPortadaCaja['hddIdCierrePortada'];
	$txtFechaCierre = date("Y-m-d", strtotime($frmVerificacionPortadaCaja['txtFechaCierre']));
	$tipoPago = $frmVerificacionPortadaCaja['slctVerificacionPortadaCaja'];
	
	if ($idAbrir == 1) { // PORTADA DE CAJA
		if ($tipoPago == 2) { // TODOS
			$objResponse->script(sprintf("verVentana('cjrs_cierre_caja_historico.php?idApertura=%s&idCierre=%s&fecha=%s', 960, 550);",
				$idApertura,
				$idCierre,
				$txtFechaCierre));
		}else if ($tipoPago == 1) { // CONTADO
			$objResponse->script(sprintf("verVentana('cjrs_cierre_caja_historico.php?idApertura=%s&idCierre=%s&fecha=%s&tipoPago=1', 960, 550);",
				$idApertura,
				$idCierre,
				$txtFechaCierre));
		} else if ($tipoPago == 0) { // CREDITO
			$objResponse->script(sprintf("verVentana('cjrs_cierre_caja_historico.php?idApertura=%s&idCierre=%s&fecha=%s&tipoPago=0', 960, 550);",
				$idApertura,
				$idCierre,
				$txtFechaCierre));
		}
	} else if ($idAbrir == 2) { // RECIBOS POR MEDIO DE PAGO
		if ($tipoPago == 2) { // TODOS
			$objResponse->script(sprintf("verVentana('cjrs_pagos_cargados_dia_historico.php?idApertura=%s&idCierre=%s&fecha=%s', 960, 550);",
				$idApertura,
				$idCierre,
				$txtFechaCierre));
		}else if ($tipoPago == 1) { // CONTADO
			$objResponse->script(sprintf("verVentana('cjrs_pagos_cargados_dia_historico.php?idApertura=%s&idCierre=%s&fecha=%s&tipoPago=1', 960, 550);",
				$idApertura,
				$idCierre,
				$txtFechaCierre));
		} else if ($tipoPago == 0) { // CREDITO
			$objResponse->script(sprintf("verVentana('cjrs_pagos_cargados_dia_historico.php?idApertura=%s&idCierre=%s&fecha=%s&tipoPago=0', 960, 550);",
				$idApertura,
				$idCierre,
				$txtFechaCierre));
		}
	}
	
	return $objResponse;
}

function buscarCierre($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['slctVerificacion']);
		
	$objResponse->loadCommands(listaCierre(0, "CONCAT(apertura.fechaAperturaCaja, apertura.id)", "DESC", $valBusq));
	
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
	
	$objResponse->script("xajax_buscarCierre(xajax.getFormValues('frmBuscar'));");
	
	return $objResponse;
}

function exportarListadoCierre($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['slctVerificacion']);
	
	$objResponse->script("window.open('reportes/cjrs_historico_cierre_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formVerificacion($idApertura, $idCierre){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	$fechaActual = date(spanDateFormat);
	$horaActual = date("H:i:s");
	
	$objResponse->script("
	document.forms['frmVerificacion'].reset();");
	
	$objResponse->assign("hddIdApertura","value",utf8_encode($idApertura));
	$objResponse->assign("hddIdCierre","value",utf8_encode($idCierre));
	
	// VERIFICO QUE SE HAYA REALIZADO LA APROBACION
	$sqlAprobacion = sprintf("SELECT
		cierre.fechaCierre,
		cierre.horaEjecucionCierre,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		usu.nombre_usuario,
		verif_cierre.*,
		emp.nombre_empresa
	FROM ".$apertCajaPpal." ape
		INNER JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
		INNER JOIN cj_verificacion_cierre verif_cierre ON (ape.idCaja = verif_cierre.id_caja AND ape.id = verif_cierre.id_apertura AND cierre.idCierre = verif_cierre.id_cierre)
		INNER JOIN pg_empleado empleado ON (verif_cierre.id_empleado = empleado.id_empleado)
		INNER JOIN pg_usuario usu ON (verif_cierre.id_usuario = usu.id_usuario)
		INNER JOIN pg_empresa emp ON (ape.id_empresa = emp.id_empresa)
	WHERE verif_cierre.id_apertura = %s
		AND verif_cierre.id_cierre = %s
		AND verif_cierre.id_caja = %s
		AND verif_cierre.accion = %s;",
		valTpDato($idApertura, "int"),
		valTpDato($idCierre, "int"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato(1, "int")); // 1 = Aprobación, 2 = Validación
	$rsAprobacion = mysql_query($sqlAprobacion);
	if (!$rsAprobacion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowAprobacion = mysql_fetch_assoc($rsAprobacion);
	
	// SI YA EXISTE LA APROBACION MUESTRA LOS DATOS GUARDADOS
	if (mysql_num_rows($rsAprobacion) > 0) {
		$objResponse->assign("txtIdEmpresa","value",utf8_encode($rowAprobacion['id_empresa']));
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowAprobacion['nombre_empresa']));
		$objResponse->assign("txtFechaCaja","value",(date(spanDateFormat, strtotime($rowAprobacion['fechaCierre']))));
		$objResponse->assign("txtHoraCierre","value",($rowAprobacion['horaEjecucionCierre']));
		$objResponse->assign("txtFechaAprobacion","value",(date(spanDateFormat, strtotime($rowAprobacion['fecha']))));
		$objResponse->assign("txtHoraAprobacion","value",($rowAprobacion['hora']));
		$objResponse->assign("txtIdEmpleadoAprobacion","value",utf8_encode($rowAprobacion['id_empleado']));
		$objResponse->assign("txtEmpleadoAprobacion","value",utf8_encode($rowAprobacion['nombre_empleado']));
		$objResponse->assign("txtIdUsuarioAprobacion","value",utf8_encode($rowAprobacion['id_usuario']));
		$objResponse->assign("txtUsuarioAprobacion","value",utf8_encode($rowAprobacion['nombre_usuario']));
	} else {
		$sql = sprintf("SELECT cierre.id_empresa, cierre.fechaCierre, cierre.horaEjecucionCierre FROM ".$cierreCajaPpal." cierre
		WHERE id = %s
			AND idCierre = %s;",
			valTpDato($idApertura, "int"),
			valTpDato($idCierre, "int"));
		$rs = mysql_query($sql);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$row = mysql_fetch_assoc($rs);
		
		// SI NO EXISTE LA APROBACION MUESTRA LOS DATOS DEL USUARIO CONECTADO
		$sqlEmpleado = sprintf("SELECT
			empleado.id_empleado,
			CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
			pg_usuario.nombre_usuario,
			pg_empresa.nombre_empresa
		FROM pg_empleado empleado
			INNER JOIN pg_usuario ON (empleado.id_empleado = pg_usuario.id_empleado)
			INNER JOIN pg_empresa ON (pg_empresa.id_empresa)
		WHERE pg_usuario.id_usuario = %s;",
			valTpDato($idUsuario, "int"));
		$rsEmpleado = mysql_query($sqlEmpleado);
		if (!$rsEmpleado) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
		
		$objResponse->assign("txtIdEmpresa","value",utf8_encode($row['id_empresa']));
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpleado['nombre_empresa']));
		$objResponse->assign("txtFechaCaja","value",(date(spanDateFormat, strtotime($row['fechaCierre']))));
		$objResponse->assign("txtHoraCierre","value",($row['horaEjecucionCierre']));
		$objResponse->assign("txtFechaAprobacion","value",($fechaActual));
		$objResponse->assign("txtHoraAprobacion","value",($horaActual));
		$objResponse->assign("txtIdEmpleadoAprobacion","value",utf8_encode($rowEmpleado['id_empleado']));
		$objResponse->assign("txtEmpleadoAprobacion","value",utf8_encode($rowEmpleado['nombre_empleado']));
		$objResponse->assign("txtIdUsuarioAprobacion","value",utf8_encode($idUsuario));
		$objResponse->assign("txtUsuarioAprobacion","value",utf8_encode($rowEmpleado['nombre_usuario']));
	}
	
	// VERIFICO QUE SE HAYA REALIZADO LA VALIDACION
	$sqlValidacion = sprintf("SELECT
		cierre.fechaCierre,
		cierre.horaEjecucionCierre,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		usu.nombre_usuario,
		verif_cierre.*,
		emp.nombre_empresa
	FROM ".$apertCajaPpal." ape
		INNER JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
		INNER JOIN cj_verificacion_cierre verif_cierre ON (ape.idCaja = verif_cierre.id_caja AND ape.id = verif_cierre.id_apertura AND cierre.idCierre = verif_cierre.id_cierre)
		INNER JOIN pg_empleado empleado ON (verif_cierre.id_empleado = empleado.id_empleado)
		INNER JOIN pg_usuario usu ON (verif_cierre.id_usuario = usu.id_usuario)
		INNER JOIN pg_empresa emp ON (ape.id_empresa = emp.id_empresa)
	WHERE verif_cierre.id_apertura = %s
		AND verif_cierre.id_cierre = %s
		AND verif_cierre.id_caja = %s
		AND verif_cierre.accion = %s;",
		valTpDato($idApertura, "int"),
		valTpDato($idCierre, "int"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato(2, "int")); // 1 = Aprobación, 2 = Validación
	$rsValidacion = mysql_query($sqlValidacion);
	if (!$rsValidacion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowValidacion = mysql_fetch_assoc($rsValidacion);
	
	// SI YA EXISTE LA VALIDACION MUESTRA LOS DATOS GUARDADOS
	if (mysql_num_rows($rsValidacion) > 0) {
		$objResponse->assign("txtIdEmpresa","value",utf8_encode($rowValidacion['id_empresa']));
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowValidacion['nombre_empresa']));
		$objResponse->assign("txtFechaCaja","value",(date(spanDateFormat, strtotime($rowValidacion['fechaCierre']))));
		$objResponse->assign("txtHoraCierre","value",($rowValidacion['horaEjecucionCierre']));
		$objResponse->assign("txtFechaValidacion","value",(date(spanDateFormat, strtotime($rowValidacion['fecha']))));
		$objResponse->assign("txtHoraValidacion","value",($rowValidacion['hora']));
		$objResponse->assign("txtIdEmpleadoValidacion","value",utf8_encode($rowValidacion['id_empleado']));
		$objResponse->assign("txtEmpleadoValidacion","value",utf8_encode($rowValidacion['nombre_empleado']));
		$objResponse->assign("txtIdUsuarioValidacion","value",utf8_encode($rowValidacion['id_usuario']));
		$objResponse->assign("txtUsuarioValidacion","value",utf8_encode($rowValidacion['nombre_usuario']));
	} else if (mysql_num_rows($rsAprobacion) > 0) {
		$sql = sprintf("SELECT cierre.id_empresa, cierre.fechaCierre, cierre.horaEjecucionCierre FROM ".$cierreCajaPpal." cierre
		WHERE id = %s
			AND idCierre = %s",
			valTpDato($idApertura, "int"),
			valTpDato($idCierre, "int"));
		$rs = mysql_query($sql);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$row = mysql_fetch_assoc($rs);
		
		// SI NO EXISTE LA VALIDACION MUESTRA LOS DATOS DEL USUARIO CONECTADO
		$sqlEmpleado = sprintf("SELECT
			empleado.id_empleado,
			CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
			pg_usuario.nombre_usuario,
			pg_empresa.nombre_empresa
		FROM pg_empleado empleado
			INNER JOIN pg_usuario ON (empleado.id_empleado = pg_usuario.id_empleado)
			INNER JOIN pg_empresa ON (pg_empresa.id_empresa)
		WHERE pg_usuario.id_usuario = %s;",
			valTpDato($idUsuario, "int"));
		$rsEmpleado = mysql_query($sqlEmpleado);
		if (!$rsEmpleado) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
		
		$objResponse->assign("txtIdEmpresa","value",utf8_encode($row['id_empresa']));
		$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpleado['nombre_empresa']));
		$objResponse->assign("txtFechaCaja","value",(date(spanDateFormat, strtotime($row['fechaCierre']))));
		$objResponse->assign("txtHoraCierre","value",($row['horaEjecucionCierre']));
		$objResponse->assign("txtFechaValidacion","value",($fechaActual));
		$objResponse->assign("txtHoraValidacion","value",($horaActual));
		$objResponse->assign("txtIdEmpleadoValidacion","value",utf8_encode($rowEmpleado['id_empleado']));
		$objResponse->assign("txtEmpleadoValidacion","value",utf8_encode($rowEmpleado['nombre_empleado']));
		$objResponse->assign("txtIdUsuarioValidacion","value",utf8_encode($idUsuario));
		$objResponse->assign("txtUsuarioValidacion","value",utf8_encode($rowEmpleado['nombre_usuario']));
	}
	
	// SI YA EXISTE LA VALIDACION
	if (mysql_num_rows($rsValidacion) > 0) {
		$objResponse->script("byId('btnGuardarAprobacion').style.display='none';");
		$objResponse->script("byId('btnGuardarValidacion').style.display='none';");
	} else { // SI YA EXISTE LA APROBACION
		if (mysql_num_rows($rsAprobacion) > 0) {
			$objResponse->script("byId('btnGuardarAprobacion').style.display='none';");
			$objResponse->script("byId('btnGuardarValidacion').style.display='';");
		} else {
			$objResponse->script("byId('btnGuardarAprobacion').style.display='';");
			$objResponse->script("byId('btnGuardarValidacion').style.display='none';");
		}
	}
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Verificacion del Cierre de Caja");
	$objResponse->script("
	if (byId('divFlotante1').style.display == 'none') {
		byId('divFlotante1').style.display='';
		centrarDiv(byId('divFlotante1'));
	}");
	
	return $objResponse;
}

function formVerificacionPortadaCaja($idApertura, $idCierre, $fechaCierre){
	$objResponse = new xajaxResponse();
	
	$idAbrir = '1'; // PORTADA DE CAJA
	$objResponse->assign("hddIdAbrir","value",($idAbrir));
	$objResponse->assign("hddIdAperturaPortada","value",($idApertura));
	$objResponse->assign("hddIdCierrePortada","value",($idCierre));
	$objResponse->assign("txtFechaCierre","value",date(spanDateFormat, strtotime($fechaCierre)));
		
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Portada de Caja");
	$objResponse->script("
	if (byId('divFlotante2').style.display == 'none') {
		byId('divFlotante2').style.display='';
		centrarDiv(byId('divFlotante2'));
	}");
			
	return $objResponse;
}

function formVerificacionRecibosPorMedioPago($idApertura, $idCierre, $fechaCierre){
	$objResponse = new xajaxResponse();
	
	$idAbrir = '2'; // RECIBOS POR MEDIO DE PAGO
	$objResponse->assign("hddIdAbrir","value",($idAbrir));
	$objResponse->assign("hddIdAperturaPortada","value",($idApertura));
	$objResponse->assign("hddIdCierrePortada","value",($idCierre));
	$objResponse->assign("txtFechaCierre","value",date(spanDateFormat, strtotime($fechaCierre)));
		
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Recibos por Medio de Pago");
	$objResponse->script("
	if (byId('divFlotante2').style.display == 'none') {
		byId('divFlotante2').style.display='';
		centrarDiv(byId('divFlotante2'));
	}");
			
	return $objResponse;
}

function guardarAprobacion($frmVerificacion, $frmListaCierre){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	mysql_query("START TRANSACTION;");
	
	// CONSULTA EL CARGO DEL USUARIO
	$sqlEmpleado = sprintf("SELECT id_cargo_departamento FROM pg_empleado
	WHERE id_empleado = %s",
		valTpDato($frmVerificacion['txtIdEmpleadoAprobacion'], "int"));
	$rsEmpleado = mysql_query($sqlEmpleado);
	if (!$rsEmpleado) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	$idCargoDepartamento = $rowEmpleado['id_cargo_departamento'];
	
	// CONSULTA LA CLAVE FILTRO PARA DAR ACCESO
	$sqlClaveFiltro = sprintf("SELECT id_cargo_departamento, clave_filtro FROM pg_cargo_departamento
	WHERE id_cargo_departamento = %s
		AND clave_filtro IN (3,9,10)", // 3 = Gte. Administracion ; 9 = Jefe Fact. y Cobranza RyS ; 10 = Jefe Fact. y Cobranza Vehiculos
		valTpDato($idCargoDepartamento, "int"));
	$rsClaveFiltro = mysql_query($sqlClaveFiltro);
	if (!$rsClaveFiltro) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowClaveFiltro = mysql_fetch_assoc($rsClaveFiltro);
	
	// SI EXISTE CLAVE FILTRO SE ADMITE ACCESO PARA VALIDAR
	if (mysql_num_rows($rsClaveFiltro) > 0) {
		$sqlInsert = sprintf("INSERT INTO cj_verificacion_cierre (fecha, hora, id_empresa, id_empleado, id_usuario, accion, id_caja, id_apertura, id_cierre)
		VALUES (NOW(), NOW(), %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($frmVerificacion['txtIdEmpresa'], "int"),
			valTpDato($frmVerificacion['txtIdEmpleadoAprobacion'], "int"),
			valTpDato($frmVerificacion['txtIdUsuarioAprobacion'], "int"),
			valTpDato(1, "int"), // 1 = Aprobación, 2 = Validación
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($frmVerificacion['hddIdApertura'], "int"),
			valTpDato($frmVerificacion['hddIdCierre'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$rsInsert = mysql_query($sqlInsert);
		if (!$rsInsert) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		return $objResponse->alert('Ud. no posee el cargo necesario para realizar esta accion');
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Aprobacion realizada con exito");
	
	$objResponse->script("byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listaCierre(
		$frmListaCierre['pageNum'],
		$frmListaCierre['campOrd'],
		$frmListaCierre['tpOrd'],
		$frmListaCierre['valBusq']));
	
	return $objResponse;
}

function guardarValidacion($frmVerificacion, $frmListaCierre){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	mysql_query("START TRANSACTION;");
	
	// CONSULTA EL CARGO DEL USUARIO
	$sqlEmpleado = sprintf("SELECT id_cargo_departamento FROM pg_empleado
	WHERE id_empleado = %s",
		valTpDato($frmVerificacion['txtIdEmpleadoValidacion'], "int"));
	$rsEmpleado = mysql_query($sqlEmpleado);
	if (!$rsEmpleado) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	$idCargoDepartamento = $rowEmpleado['id_cargo_departamento'];
	
	// CONSULTA LA CLAVE FILTRO PARA DAR ACCESO
	$sqlClaveFiltro = sprintf("SELECT id_cargo_departamento, clave_filtro FROM pg_cargo_departamento
	WHERE id_cargo_departamento = %s
		AND clave_filtro IN (3)", // 3 = Gte. Administracion
		valTpDato($idCargoDepartamento, "int"));
	$rsClaveFiltro = mysql_query($sqlClaveFiltro);
	if (!$rsClaveFiltro) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowClaveFiltro = mysql_fetch_assoc($rsClaveFiltro);
	
	// SI EXISTE CLAVE FILTRO SE ADMITE ACCESO PARA VALIDAR
	if (mysql_num_rows($rsClaveFiltro) > 0) {
		$sqlInsert = sprintf("INSERT INTO cj_verificacion_cierre (fecha, hora, id_empresa, id_empleado, id_usuario, accion, id_caja, id_apertura, id_cierre)
		VALUES (NOW(), NOW(), %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($frmVerificacion['txtIdEmpresa'], "int"),
			valTpDato($frmVerificacion['txtIdEmpleadoValidacion'], "int"),
			valTpDato($frmVerificacion['txtIdUsuarioValidacion'], "int"),
			valTpDato(2, "int"), // 1 = Aprobación, 2 = Validación
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($frmVerificacion['hddIdApertura'], "int"),
			valTpDato($frmVerificacion['hddIdCierre'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$rsInsert = mysql_query($sqlInsert);
		if (!$rsInsert) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		return $objResponse->alert('Ud. no posee el cargo necesario para realizar esta accion');
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Validacion realizada con exito");
	
	$objResponse->script("byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listaCierre(
		$frmListaCierre['pageNum'],
		$frmListaCierre['campOrd'],
		$frmListaCierre['tpOrd'],
		$frmListaCierre['valBusq']));
	
	return $objResponse;
}

function listaCierre($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $idModuloPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre.idCierre = (SELECT MAX(cierre2.idCierre) FROM ".$cierreCajaPpal." cierre2 WHERE cierre2.id = apertura.id)
	AND apertura.statusAperturaCaja IN (0)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre.fechaCierre BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[3] == 0) { // Caja No Verificada
			$sqlBusq .= $cond.sprintf("cierre.idCierre NOT IN (SELECT verif_cierre.id_cierre FROM cj_verificacion_cierre verif_cierre
			WHERE verif_cierre.id_caja = %s
				AND verif_cierre.id_empresa = apertura.id_empresa)",
				valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		} else {
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(verif_cierre.id_cierre) FROM cj_verificacion_cierre verif_cierre
			WHERE verif_cierre.id_caja = %s
				AND verif_cierre.id_empresa = apertura.id_empresa
				AND verif_cierre.id_cierre = cierre.idCierre
				AND verif_cierre.accion IN (%s))",
				valTpDato($idCajaPpal, "int"),
				valTpDato($valCadBusq[3], "campo")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		}
	}
	
	$query = sprintf("SELECT
		apertura.id,
		apertura.id_empresa,
		apertura.idCaja,
		apertura.fechaAperturaCaja,
		CONCAT_WS(' ', apertura.fechaAperturaCaja, apertura.horaApertura) AS ejecucion_apertura,
		apertura.id_usuario AS idUsuarioApertura,
		cierre.idCierre,
		cierre.fechaCierre,
		CONCAT_WS(' ', cierre.fechaEjecucionCierre, cierre.horaEjecucionCierre) AS ejecucion_cierre,
		cierre.id_usuario AS idUsuarioCierre,
		cierre.tipoCierre,
		(CASE cierre.tipoCierre
			WHEN 0 THEN 'Caja Cerrada'
			WHEN 1 THEN 'Caja Abierta'
			WHEN 2 THEN 'Caja Cierre Parcial'
		END) AS descripcion_tipo_cierre,
		SUM(cierre.cargaEfectivoCaja) AS cargaEfectivoCaja,
		SUM(cierre.saldoCaja) AS saldoCaja,
		SUM(cierre.saldoEfectivo) AS saldoEfectivo,
		SUM(cierre.saldoCheques) AS saldoCheques,
		SUM(cierre.saldoDepositos) AS saldoDepositos,
		SUM(cierre.saldoTransferencia) AS saldoTransferencia,
		SUM(cierre.saldoTarjetaCredito) AS saldoTarjetaCredito,
		SUM(cierre.saldoTarjetaDebito) AS saldoTarjetaDebito,
		SUM(cierre.saldoAnticipo) AS saldoAnticipo,
		SUM(cierre.saldoNotaCredito) AS saldoNotaCredito,
		SUM(cierre.saldoRetencion) AS saldoRetencion,
		SUM(cierre.saldoOtro) AS saldoOtro,
		cierre.observacion,
		
		(SELECT nombre_usuario FROM pg_usuario usuario
		WHERE usuario.id_usuario = apertura.id_usuario) AS usuario_apertura,
		
		(SELECT nombre_usuario FROM pg_usuario usuario
		WHERE usuario.id_usuario = cierre.id_usuario) AS usuario_cierre,
		
		(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado empleado
		WHERE empleado.id_empleado = (SELECT id_empleado FROM pg_usuario usuario
									WHERE usuario.id_usuario = apertura.id_usuario)) AS empleado_apertura,
									
		(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado empleado
		WHERE empleado.id_empleado = (SELECT id_empleado FROM pg_usuario usuario
									WHERE usuario.id_usuario = cierre.id_usuario)) AS empleado_cierre,
		
		(SELECT MAX(accion) AS accion FROM cj_verificacion_cierre verif_cierre
		WHERE verif_cierre.id_caja = apertura.idCaja
			AND verif_cierre.id_apertura = apertura.id
			AND verif_cierre.id_cierre = cierre.idCierre
			AND verif_cierre.id_empresa = apertura.id_empresa
		LIMIT 1) AS accion_verif_cierre,
									
		(SELECT COUNT(cxc_fact.fechaRegistroFactura) FROM cj_cc_encabezadofactura cxc_fact
		WHERE cxc_fact.fechaRegistroFactura = cierre.fechaCierre
			AND idDepartamentoOrigenFactura IN (%s)
		GROUP BY cxc_fact.fechaRegistroFactura) AS cant_fact_cred,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM ".$apertCajaPpal." apertura
		INNER JOIN ".$cierreCajaPpal." cierre ON (apertura.id = cierre.id)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cierre.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	GROUP BY apertura.id, cierre.fechaCierre",
		valTpDato($idModuloPpal, "campo"),
		$sqlBusq);
	
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaCierre", "", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaCierre", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCierre", "10%", $pageNum, "fechaAperturaCaja", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Apertura"));
		$htmlTh .= ordenarCampo("xajax_listaCierre", "20%", $pageNum, "empleado_apertura", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado Apertura"));
		$htmlTh .= ordenarCampo("xajax_listaCierre", "10%", $pageNum, "ejecucion_apertura", $campOrd, $tpOrd, $valBusq, $maxRows, ("Ejecución Apertura"));
		$htmlTh .= ordenarCampo("xajax_listaCierre", "20%", $pageNum, "empleado_cierre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado Cierre"));
		$htmlTh .= ordenarCampo("xajax_listaCierre", "10%", $pageNum, "ejecucion_cierre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Ejecución Cierre"));
		$htmlTh .= ordenarCampo("xajax_listaCierre", "12%", $pageNum, "saldoCaja", $campOrd, $tpOrd, $valBusq, $maxRows, ("Saldo de Caja"));
		$htmlTh .= "<td align=\"center\" colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['accion_verif_cierre']) {
			case 1 : $estatus = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Aprobada\"/>"; break;
			case 2 : $estatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Validada\"/>"; break;
			default : $estatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"No Verificada\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$estatus."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_10px\">".date(spanDateFormat, strtotime($row['fechaAperturaCaja']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['empleado_apertura'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= date(spanDateFormat, strtotime($row['ejecucion_apertura']))."<br>";
				$htmlTb .= date("H:i:s", strtotime($row['ejecucion_apertura']));
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['empleado_cierre'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= date(spanDateFormat, strtotime($row['ejecucion_cierre']))."<br>";
				$htmlTb .= date("H:i:s", strtotime($row['ejecucion_cierre']));
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td align=\"right\" onmouseover=\"Tip('<div id=divPrecios></div>', TITLE, '%s) Saldos de Caja (Fecha Apertura: %s)', WIDTH, 400); xajax_listaSaldoCierre('%s');\" onmouseout=\"UnTip();\">%s</td>",
				$row['idCierre'],
				date(spanDateFormat, strtotime($row['fechaAperturaCaja'])),
				$row['idCierre'],
				number_format($row['saldoCaja'], 2, ".", ","));
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formVerificacionPortadaCaja('%s','%s','%s');\" src=\"../img/iconos/ico_examinar.png\" title=\"Portada de Caja\"/></td>",
				$row['id'],
				$row['idCierre'],
				$row['fechaCierre']); // Portada de Caja
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formVerificacionRecibosPorMedioPago('%s','%s','%s');\" src=\"../img/iconos/application_view_columns.png\" title=\"Recibos por Medio de Pago\"/></td>",
				$row['id'],
				$row['idCierre'],
				$row['fechaCierre']); // Recibo por Mediio de Pago
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"xajax_formVerificacion('%s','%s');\" src=\"../img/iconos/find.png\" title=\"Verificar Cierre\"/></td>",
				$row['id'],
				$row['idCierre']); // Verificacion de Caja
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCierre(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierre(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierre(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoCierre","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

function listaSaldoCierre($idCierre) {
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $idModuloPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	$query = sprintf("SELECT
		apertura.id,
		apertura.id_empresa,
		apertura.idCaja,
		apertura.fechaAperturaCaja,
		CONCAT_WS(' ', apertura.fechaAperturaCaja, apertura.horaApertura) AS ejecucion_apertura,
		apertura.id_usuario AS idUsuarioApertura,
		cierre.idCierre,
		cierre.fechaCierre,
		CONCAT_WS(' ', cierre.fechaEjecucionCierre, cierre.horaEjecucionCierre) AS ejecucion_cierre,
		cierre.id_usuario AS idUsuarioCierre,
		cierre.tipoCierre,
		(CASE cierre.tipoCierre
			WHEN 0 THEN 'Caja Cerrada'
			WHEN 1 THEN 'Caja Abierta'
			WHEN 2 THEN 'Caja Cierre Parcial'
		END) AS descripcion_tipo_cierre,
		SUM(cierre.cargaEfectivoCaja) AS cargaEfectivoCaja,
		SUM(cierre.saldoCaja) AS saldoCaja,
		SUM(cierre.saldoEfectivo) AS saldoEfectivo,
		SUM(cierre.saldoCheques) AS saldoCheques,
		SUM(cierre.saldoDepositos) AS saldoDepositos,
		SUM(cierre.saldoTransferencia) AS saldoTransferencia,
		SUM(cierre.saldoTarjetaCredito) AS saldoTarjetaCredito,
		SUM(cierre.saldoTarjetaDebito) AS saldoTarjetaDebito,
		SUM(cierre.saldoAnticipo) AS saldoAnticipo,
		SUM(cierre.saldoNotaCredito) AS saldoNotaCredito,
		SUM(cierre.saldoRetencion) AS saldoRetencion,
		SUM(cierre.saldoOtro) AS saldoOtro,
		cierre.observacion,
		
		(SELECT nombre_usuario FROM pg_usuario usuario
		WHERE usuario.id_usuario = apertura.id_usuario) AS usuario_apertura,
		
		(SELECT nombre_usuario FROM pg_usuario usuario
		WHERE usuario.id_usuario = cierre.id_usuario) AS usuario_cierre,
		
		(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado empleado
		WHERE empleado.id_empleado = (SELECT id_empleado FROM pg_usuario usuario
									WHERE usuario.id_usuario = apertura.id_usuario)) AS empleado_apertura,
									
		(SELECT CONCAT_WS(' ', nombre_empleado, apellido) FROM pg_empleado empleado
		WHERE empleado.id_empleado = (SELECT id_empleado FROM pg_usuario usuario
									WHERE usuario.id_usuario = cierre.id_usuario)) AS empleado_cierre,
		
		(SELECT MAX(accion) AS accion FROM cj_verificacion_cierre verif_cierre
		WHERE verif_cierre.id_caja = apertura.idCaja
			AND verif_cierre.id_apertura = apertura.id
			AND verif_cierre.id_cierre = cierre.idCierre
			AND verif_cierre.id_empresa = apertura.id_empresa
		LIMIT 1) AS accion_verif_cierre,
									
		(SELECT COUNT(cxc_fact.fechaRegistroFactura) FROM cj_cc_encabezadofactura cxc_fact
		WHERE cxc_fact.fechaRegistroFactura = cierre.fechaCierre
			AND idDepartamentoOrigenFactura IN (%s)
		GROUP BY cxc_fact.fechaRegistroFactura) AS cant_fact_cred,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM ".$apertCajaPpal." apertura
		INNER JOIN ".$cierreCajaPpal." cierre ON (apertura.id = cierre.id)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cierre.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE cierre.idCierre = %s
	GROUP BY apertura.id, cierre.fechaCierre",
		valTpDato($idModuloPpal, "campo"),
		valTpDato($idCierre, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$htmlTblIni = "<table border=\"0\" width=\"398\">";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\" width=\"45%\">".("Carga de Efectivo:")."</td>";
		$htmlTb .= "<td width=\"55%\">".number_format($row['cargaEfectivoCaja'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo Efectivo:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoEfectivo'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo Cheques:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoCheques'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo Depósitos:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoDepositos'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo Transferencias:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoTransferencia'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo TDC:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoTarjetaCredito'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo TDD:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoTarjetaDebito'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo Nota de Crédito:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoNotaCredito'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo Retención:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoRetencion'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo Otro:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoOtro'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo de Caja:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoCaja'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "<tr>";
		$htmlTb .= "<td colspan=\"2\"><hr></td>";
	$htmlTb .= "<tr>";
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">".("Saldo Anticipo:")."</td>";
		$htmlTb .= "<td>".number_format($row['saldoAnticipo'], 2, ".", ",")."</td>";
	$htmlTb .= "</tr>";
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divPrecios","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"abrirPortadaCaja");
$xajax->register(XAJAX_FUNCTION,"buscarCierre");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"exportarListadoCierre");
$xajax->register(XAJAX_FUNCTION,"formVerificacion");
$xajax->register(XAJAX_FUNCTION,"formVerificacionPortadaCaja");
$xajax->register(XAJAX_FUNCTION,"formVerificacionRecibosPorMedioPago");
$xajax->register(XAJAX_FUNCTION,"guardarAprobacion");
$xajax->register(XAJAX_FUNCTION,"guardarValidacion");
$xajax->register(XAJAX_FUNCTION,"listaCierre");
$xajax->register(XAJAX_FUNCTION,"listaSaldoCierre");
?>