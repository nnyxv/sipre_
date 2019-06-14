<?php


function aperturaCaja($frmApertura, $reapertura = "false"){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	$fechaApertura = date("Y-m-d"); // FECHA DE APERTURA SIEMPRE SERÁ LA FECHA ACTUAL.
	$horaApertura = date("h:i:s a"); // HORA DE APERTURA SIEMPRE SERÁ LA FECHA ACTUAL.
	$txtCargaEfectivo = $frmApertura['txtCargaEfectivo'];
	
	// VERIFICA VALORES DE CONFIGURACION (Apertura de Caja)
	$queryConfig410 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 410 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig410 = mysql_query($queryConfig410);
	if (!$rsConfig410) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsConfig400 = mysql_num_rows($rsConfig410);
	$rowConfig410 = mysql_fetch_assoc($rsConfig410);
	
	if ($rowConfig410['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	} else if (!($totalRowsConfig400 > 0)) {
		return $objResponse->alert("No puede aperturar la caja por esta empresa");
	}
	
	// CONSULA DATOS DE LA CAJA PARA SABER SI YA TIENE APERTURA
	$sql = sprintf("SELECT
		fechaAperturaCaja,
		saldoCaja,
		cargaEfectivoCaja
	FROM ".$apertCajaPpal."
	WHERE idCaja = %s
		AND fechaAperturaCaja = %s
		AND id_empresa = %s",
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($fechaApertura, "date"),
		valTpDato($idEmpresa, "int"));
	$consulta = mysql_query($sql);
	if (!$consulta){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	//VERIFICA SI LA CAJA YA TIENE APERTURA
	if (mysql_num_rows($consulta) > 0 && $reapertura == "false") {
		$objResponse->call(validarFrmReapertura, date(spanDateFormat, strtotime($fechaApertura)));
	} else {
		// BUSCO LA CAJA QUE ESTA ABIERTA TOTAL O PARCIAL
		$sqlConsultarFecha = sprintf("SELECT fechaAperturaCaja FROM ".$apertCajaPpal."
		WHERE idCaja = %s
			AND statusAperturaCaja IN (1,2)
			AND id_empresa = %s",
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			valTpDato($idEmpresa, "int"));
		$consultaConsultaFecha = mysql_query($sqlConsultarFecha);
		if (!$consultaConsultaFecha){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		if (mysql_num_rows($consultaConsultaFecha)) {
			$fechaAperturaCaja = mysql_fetch_array($consultaConsultaFecha);
			if ($fechaAperturaCaja['fechaAperturaCaja'] != $fechaApertura) {
				$objResponse->alert("La caja esta abierta con fecha anterior a la actual. Cierrela y aperture nuevamente.");
				if (in_array($idCajaPpal, array(1))) {
					$objResponse->script("window.location.href='cj_cierre_caja.php'");
				} else if (in_array($idCajaPpal, array(2))) {
					$objResponse->script("window.location.href='cjrs_cierre_caja.php'");
				}
			} 
		} else {
			// CONSULTA SI EXISTEN PLANILLAS PENDIENTES POR DEPOSITAR
			$queryDeposito = sprintf("SELECT *
			FROM an_encabezadodeposito deposito
				INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
			WHERE deposito.idCaja = %s
				AND deposito_det.conformado = %s
				AND deposito.id_empresa = %s",
				valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
				valTpDato(1, "int"), // 1 = Por Conformar, 2 = Conformado
				valTpDato($idEmpresa, "int"));
			$rsDeposito = mysql_query($queryDeposito);
			if (!$rsDeposito){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsDeposito = mysql_num_rows($rsDeposito);
			$rowDeposito = mysql_fetch_array($rsDeposito);
			
			if ($totalRowsDeposito > 0) {
				$objResponse->alert("No se puede realizar la apertura de caja, debido a que existen planillas sin conformar.");
				if (in_array($idCajaPpal, array(1))) {
					$objResponse->script("window.location.href = 'cj_depositos_form.php'");
				} else if (in_array($idCajaPpal, array(2))) {
					$objResponse->script("window.location.href = 'cjrs_depositos_form.php'");
				}
			} else {
				mysql_query("START TRANSACTION;");
				
				//INSERTA LA APERTURA DE LA CAJA
				$insertSQL = sprintf("INSERT INTO ".$apertCajaPpal." (idCaja, fechaAperturaCaja, horaApertura, saldoCaja, cargaEfectivoCaja, statusAperturaCaja, saldoEfectivo, saldoCheques, saldoDepositos, saldoTransferencia, saldoTarjetaCredito, saldoTarjetaDebito, saldoAnticipo, saldoNotaCredito, saldoRetencion, id_usuario, id_empresa)
				VALUES(%s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					valTpDato($fechaApertura, "date"),
					valTpDato($txtCargaEfectivo, "real_inglesa"),
					valTpDato($txtCargaEfectivo, "real_inglesa"),
					valTpDato(1, "int"), // 0 = Cerrada, 1 = Abierta, 2 = Cerrada Parcialmente
					valTpDato(0, "int"),
					valTpDato(0, "int"),
					valTpDato(0, "int"),
					valTpDato(0, "int"),
					valTpDato(0, "int"),
					valTpDato(0, "int"),
					valTpDato(0, "int"),
					valTpDato(0, "int"),
					valTpDato(0, "int"),
					valTpDato($idUsuario, "int"),
					valTpDato($idEmpresa, "int"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idApertura = mysql_insert_id();
				
				//INSERTA LA APERTURA EN EL CIERRE
				//INSERTA EN LA FECHA DE CIERRE la fecha de apertura, esto para que no haya diferencias en el historico de cierre.
				//INSERTA MOMENTANEAMENTE EN fechaEjecucionCierre la fecha de apertura, esta será actualizada al realizar el cierre de la caja.
				$insertSQL = sprintf("INSERT INTO ".$cierreCajaPpal." (id, tipoCierre, fechaCierre, horaEjecucionCierre, fechaEjecucionCierre, cargaEfectivoCaja, saldoCaja, saldoEfectivo, saldoCheques, saldoDepositos, saldoTransferencia, saldoTarjetaCredito, saldoTarjetaDebito, saldoAnticipo, saldoNotaCredito, saldoRetencion, id_usuario, id_empresa, observacion)
				VALUES (%s, %s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idApertura, "int"),
					valTpDato(1, "int"),
					valTpDato($fechaApertura, "date"),
					valTpDato($fechaApertura, "date"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato($idUsuario, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato("", "text"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				
				mysql_query("COMMIT;");
				
				$objResponse->alert("La Caja ha sido abierta.");
				
				$objResponse->script("window.location.href='index.php'");
			}
		}
	}
	
	return $objResponse;
}

function cargarDatosCaja(){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	// VERIFICA VALORES DE CONFIGURACION (Apertura de Caja)
	$queryConfig410 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 410 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig410 = mysql_query($queryConfig410);
	if (!$rsConfig410) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConfig410 = mysql_fetch_assoc($rsConfig410);
	
	if ($rowConfig410['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	
	//CONSULTA EL ESTATUS DE LA CAJA
	$queryApertura = sprintf("SELECT
		ape.*,
		(CASE ape.statusAperturaCaja
			WHEN 0 THEN 'CERRADA TOTALMENTE'
			WHEN 1 THEN CONCAT_WS(' EL ', 'ABIERTA', DATE_FORMAT(ape.fechaAperturaCaja, %s))
			WHEN 2 THEN 'CERRADA PARCIALMENTE'
			ELSE 'CERRADA TOTALMENTE'
		END) AS estatus_apertura_caja,
		vw_iv_usu.nombre_empleado,
		vw_iv_emp_suc.rif,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM ".$apertCajaPpal." ape
		INNER JOIN caja ON (ape.idCaja = caja.idCaja)
		LEFT JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
		INNER JOIN vw_iv_usuarios vw_iv_usu ON (ape.id_usuario = vw_iv_usu.id_usuario)
		RIGHT JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ape.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE ((ape.statusAperturaCaja IN (0,1,2) AND ape.idCaja = %s)
			OR ape.statusAperturaCaja IS NULL)
		AND vw_iv_emp_suc.id_empresa_reg = %s
	ORDER BY ape.id DESC
	LIMIT 1;",
		valTpDato(spanDatePick, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		valTpDato($idEmpresa, "int"));
	$rsApertura = mysql_query($queryApertura);
	if (!$rsApertura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowApertura = mysql_fetch_assoc($rsApertura);
	
	switch($rowApertura['statusAperturaCaja']) {
		case 0 : $class = "divMsjError"; break;
		case 1 : $class = "divMsjInfo"; break;
		case 2 : $class = "divMsjAlerta"; break;
	}
	
	// ASIGNA LOS DATOS DE CAJA
	$objResponse->assign("txtFechaApertura","value",date(spanDateFormat));
	$objResponse->assign("txtNombreEmpresa","value",$rowApertura['nombre_empresa']);
	$objResponse->assign("txtRif","value",$rowApertura['rif']);
	$objResponse->assign("txtEstadoCaja","className",$class);
	$objResponse->assign("txtEstadoCaja","value",$rowApertura['estatus_apertura_caja']);
	
	if (in_array($rowApertura['statusAperturaCaja'],array(1))) {
		$objResponse->script("
		byId('txtCargaEfectivo').className = 'inputCompleto';
		byId('txtCargaEfectivo').readOnly = true;
		
		byId('btnApertura').style.display = 'none';");
		
		$objResponse->assign("txtEmpleadoApertura","value",utf8_encode($rowApertura['nombre_empleado']));
		$objResponse->assign("txtSaldoCaja","value",number_format($rowApertura['saldoCaja'], 2, ".", ","));
		$objResponse->assign("txtCargaEfectivo","value",number_format($rowApertura['cargaEfectivoCaja'], 2, ".", ","));
		
		if (date(spanDateFormat, strtotime($rowApertura['fechaAperturaCaja'])) != date(spanDateFormat)) {
			$objResponse->alert("La caja esta abierta con fecha: ".date(spanDateFormat, strtotime($rowApertura['fechaAperturaCaja'])).". Cierrela y aperture nuevamente.");
		}
	} else {
		$objResponse->script("
		byId('txtCargaEfectivo').className = 'inputCompletoHabilitado';
		byId('txtCargaEfectivo').readOnly = false;
		
		byId('btnApertura').style.display = '';");
	}
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"aperturaCaja");
$xajax->register(XAJAX_FUNCTION,"cargarDatosCaja");
?>