<?php
require_once("../connections/conex.php");

require_once("../inc_sesion.php");

$idEmpresa = $_POST['txtIdEmpresa'];
$idPresupuesto = excape($_POST['txtIdPresupuesto']);
$idCliente = excape($_POST['txtIdCliente']);
$idUnidadBasica = excape($_POST['txtIdUnidadBasica']);
$idAsesorVentas = excape($_POST['asesor_ventas']);
$txtObservacion = excape($_POST['txtObservacion']);

$txtPrecioBase = getmysqlnum($_POST['txtPrecioBase']);
$txtDescuento = getmysqlnum($_POST['txtDescuento']);
$precioVenta = getmysqlnum($_POST['precio_venta']);
$porcentaje_iva = getmysqlnum($_POST['porcentaje_iva']);
$porcentaje_impuesto_lujo = getmysqlnum($_POST['porcentaje_impuesto_lujo']);
$hddTipoInicial = getmysqlnum($_POST['hddTipoInicial']);
$txtPorcInicial = getmysqlnum($_POST['porcentaje_inicial']);
$txtMontoInicial = getmysqlnum($_POST['p_inicial']);

// FINANCIAMIENTO
$lstBancoFinanciar = $_POST['lstBancoFinanciar'];
$txtSaldoFinanciar = getmysqlnum($_POST['txtSaldoFinanciar']);
$lstMesesFinanciar = getemptynum($_POST['lstMesesFinanciar'],'null');
$txtInteresCuotaFinanciar = getemptynum($_POST['txtInteresCuotaFinanciar'],'null');
$txtCuotasFinanciar = getemptynum($_POST['txtCuotasFinanciar'],'null');
$lstMesesFinanciar2 = getemptynum($_POST['lstMesesFinanciar2'],'null');
$txtInteresCuotaFinanciar2 = getemptynum($_POST['txtInteresCuotaFinanciar2'],'null');
$txtCuotasFinanciar2 = getemptynum($_POST['txtCuotasFinanciar2'],'null');

$total_accesorio = getempty(getmysqlnum($_POST['total_accesorio']),"null");
$txtTotalInicialGastos = getmysqlnum($_POST['totalinicial']);
$txtTotalAdicionalContrato = getmysqlnum($_POST['txtTotalAdicionalContrato']);
$total_general = getmysqlnum($_POST['total_general']);
$txtPorcFLAT = getempty($_POST['porcentaje_flat'],'0');
$txtMontoFLAT = getemptynum($_POST['monto_flat'],'null');

// ACCESORIOS
$empresa_accesorio = excape($_POST['empresa_accesorio']);
$vexacc2 = getempty(getmysqlnum($_POST['vexacc2']),"null");
$exacc2 = excape($_POST['exacc2']);
$vexacc3 = getempty(getmysqlnum($_POST['vexacc3']),"null");
$exacc3 = excape($_POST['exacc3']);
$vexacc4 = getempty(getmysqlnum($_POST['vexacc4']),"null");
$exacc4 = excape($_POST['exacc4']);

// POLIZA
$id_poliza = $_POST['id_poliza'];
$monto_seguro = getempty(getmysqlnum($_POST['monto_seguro']),"null");
$inicial_poliza = getempty(getmysqlnum($_POST['inicial_poliza']),"null");
$cuotas_poliza = getempty(getmysqlnum($_POST['cuotas_poliza']),"null");
$meses_poliza = getempty($_POST['meses_poliza'],'null');

if ($txtPorcInicial == 100) {
	$lstBancoFinanciar = "";
	$lstMesesFinanciar = "";
	$txtInteresCuotaFinanciar = "";
	$txtCuotasFinanciar = "";
	$lstMesesFinanciar2 = "";
	$txtInteresCuotaFinanciar2 = "";
	$txtCuotasFinanciar2 = "";
	
	$txtPorcFLAT = "";
	$txtMontoFLAT = "";
}

//extraer los ivas:
$imp = $porcentaje_impuesto_lujo + $porcentaje_iva;
if ($imp != 0){
	$precioVenta = $precioVenta - ($precioVenta * $imp / (100 + $imp));//extrae los impuestos
}

mysql_query("START TRANSACTION;");

if ($idPresupuesto > 0) {
	validaModulo("an_presupuesto_venta_list",editar,true);
	
	$sql = "UPDATE an_presupuesto SET
		id_cliente = ".valTpDato($idCliente, "int").",
		id_uni_bas = ".valTpDato($idUnidadBasica, "int").",
		asesor_ventas = ".valTpDato($idAsesorVentas, "int").",
		estado = 0,
		precio_venta = ".valTpDato($txtPrecioBase, "real_inglesa").",
		monto_descuento = ".valTpDato($txtDescuento, "real_inglesa").",
		porcentaje_iva = ".valTpDato($porcentaje_iva, "real_inglesa").",
		porcentaje_impuesto_lujo = ".valTpDato($porcentaje_impuesto_lujo, "real_inglesa").",
		tipo_inicial = ".valTpDato($hddTipoInicial, "int").",
		porcentaje_inicial = ".valTpDato($_POST['porcentaje_inicial'], "real_inglesa").",
		monto_inicial = ".valTpDato($txtMontoInicial, "real_inglesa").",
		id_banco_financiar = ".valTpDato($lstBancoFinanciar, "int").",
		saldo_financiar = ".valTpDato($txtSaldoFinanciar, "real_inglesa").",
		meses_financiar = ".valTpDato($lstMesesFinanciar, "real_inglesa").",
		interes_cuota_financiar = ".valTpDato($txtInteresCuotaFinanciar, "real_inglesa").",
		cuotas_financiar = ".valTpDato($txtCuotasFinanciar, "real_inglesa").",
		total_accesorio = ".valTpDato($total_accesorio, "real_inglesa").",
		total_inicial_gastos = ".valTpDato($txtTotalInicialGastos, "real_inglesa").",
		total_adicional_contrato = ".valTpDato($txtTotalAdicionalContrato, "real_inglesa").",
		total_general = ".valTpDato($total_general, "real_inglesa").",
		porcentaje_flat = ".valTpDato($txtPorcFLAT, "real_inglesa").",
		monto_flat = ".valTpDato($txtMontoFLAT, "real_inglesa").",
		empresa_accesorio = ".valTpDato($empresa_accesorio, "text").",
		exacc1 = ".valTpDato($_POST['txtDescripcionAccesorio'], "text").",
		exacc2 = ".valTpDato($exacc2, "text").",
		exacc3 = ".valTpDato($exacc3, "text").",
		exacc4 = ".valTpDato($exacc4, "text").",
		vexacc1 = ".valTpDato($_POST['txtMontoAccesorio'], "real_inglesa").",
		vexacc2 = ".valTpDato($vexacc2, "real_inglesa").",
		vexacc3 = ".valTpDato($vexacc3, "real_inglesa").",
		vexacc4 = ".valTpDato($vexacc4, "real_inglesa").",
		id_poliza = ".valTpDato($id_poliza, "int").",
		monto_seguro = ".valTpDato($monto_seguro, "real_inglesa").",
		contado_poliza = ".valTpDato(0, "real_inglesa").",
		inicial_poliza = ".valTpDato($inicial_poliza, "real_inglesa").",
		meses_poliza = ".valTpDato($meses_poliza, "real_inglesa").",
		cuotas_poliza = ".valTpDato($cuotas_poliza, "real_inglesa").",
		observacion = ".valTpDato($txtObservacion, "text")."
	WHERE id_presupuesto = ".valTpDato($idPresupuesto, "int").";";
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($sql,$conex);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
} else {
	validaModulo("an_presupuesto_venta_list",insertar,true);
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = %s
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato(38, "int"), // 38 = Presupuesto Venta Vehículos
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	
	// INSERTA LOS DATOS DEL PRESUPUESTO
	$sql = "INSERT INTO an_presupuesto (numeracion_presupuesto, id_empresa, id_cliente, id_uni_bas, asesor_ventas, estado, fecha, precio_venta, monto_descuento, porcentaje_iva, porcentaje_impuesto_lujo, tipo_inicial, porcentaje_inicial, monto_inicial, id_banco_financiar, saldo_financiar, meses_financiar, interes_cuota_financiar, cuotas_financiar, meses_financiar2, interes_cuota_financiar2, cuotas_financiar2, total_accesorio, total_inicial_gastos, total_adicional_contrato, total_general, porcentaje_flat, monto_flat, empresa_accesorio, exacc1, exacc2, exacc3, exacc4, vexacc1, vexacc2, vexacc3, vexacc4, id_poliza, monto_seguro, contado_poliza, inicial_poliza, meses_poliza, cuotas_poliza, observacion)
	VALUES (".
		valTpDato($numeroActual, "text").", ".
		valTpDato($idEmpresa, "int").", ".
		valTpDato($idCliente, "int").", ".
		valTpDato($idUnidadBasica, "int").", ".
		valTpDato($idAsesorVentas, "int").", ".
		valTpDato(0, "int").", ".
		valTpDato("CURRENT_DATE()", "campo").", ".
		valTpDato($txtPrecioBase, "real_inglesa").", ".
		valTpDato($txtDescuento, "real_inglesa").", ".
		valTpDato($porcentaje_iva, "real_inglesa").", ".
		valTpDato($porcentaje_impuesto_lujo, "real_inglesa").", ".
		valTpDato($hddTipoInicial, "int").", ".
		valTpDato($_POST['porcentaje_inicial'], "real_inglesa").", ".
		valTpDato($txtMontoInicial, "real_inglesa").", ".
		valTpDato($lstBancoFinanciar, "int").", ".
		valTpDato($txtSaldoFinanciar, "real_inglesa").", ".
		valTpDato($lstMesesFinanciar, "real_inglesa").", ".
		valTpDato($txtInteresCuotaFinanciar, "real_inglesa").", ".
		valTpDato($txtCuotasFinanciar, "real_inglesa").", ".
		valTpDato($lstMesesFinanciar2, "real_inglesa").", ".
		valTpDato($txtInteresCuotaFinanciar2, "real_inglesa").", ".
		valTpDato($txtCuotasFinanciar2, "real_inglesa").", ".
		valTpDato($total_accesorio, "real_inglesa").", ".
		valTpDato($txtTotalInicialGastos, "real_inglesa").", ".
		valTpDato($txtTotalAdicionalContrato, "real_inglesa").", ".
		valTpDato($total_general, "real_inglesa").", ".
		valTpDato($txtPorcFLAT, "real_inglesa").", ".
		valTpDato($txtMontoFLAT, "real_inglesa").", ".
		valTpDato($empresa_accesorio, "text").", ".
		valTpDato($_POST['txtDescripcionAccesorio'], "text").", ".
		valTpDato($exacc2, "text").", ".
		valTpDato($exacc3, "text").", ".
		valTpDato($exacc4, "text").", ".
		valTpDato($_POST['txtMontoAccesorio'], "real_inglesa").", ".
		valTpDato($vexacc2, "real_inglesa").", ".
		valTpDato($vexacc3, "real_inglesa").", ".
		valTpDato($vexacc4, "real_inglesa").", ".
		valTpDato($id_poliza, "int").", ".
		valTpDato($monto_seguro, "real_inglesa").", ".
		valTpDato($txtContadoPoliza, "real_inglesa").", ".
		valTpDato($inicial_poliza, "real_inglesa").", ".
		valTpDato($meses_poliza, "real_inglesa").", ".
		valTpDato($cuotas_poliza, "real_inglesa").", ".
		valTpDato($txtObservacion, "text").");";
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($sql,$conex);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__.$sql);
	$idPresupuesto = mysql_insert_id($conex);
	mysql_query("SET NAMES 'latin1';");
}

// ADICIONALES
$acc = $_POST['ac'];
for ($i = 0; $i < count($acc); $i++) {
	$idDetAccPaq = getmysqlnum(getempty($_POST['iddetacc'][$i],'null'));
	$value = getmysqlnum($_POST['acv'][$i]);
	$iva = getempty($_POST['ivaacc'][$i],'null');
	$civa = getmysqlnum(getempty($_POST['civaacc'][$i],'0'));
	$piva = getmysqlnum(getempty($_POST['pivaacc'][$i],'9'));
	
	if ($piva != 0) {
		$value = $value - ($value * $piva / (100 + $piva));
	}
	
	if ($_POST['acp'][$i] == '') {
		// ACCESORIOS
		if ($_POST['acaccion'][$i] == 1) { // INSERTA
			$sqla = "INSERT INTO an_accesorio_presupuesto (id_presupuesto, id_accesorio, precio_accesorio, iva_accesorio, costo_accesorio, porcentaje_iva_accesorio)
			VALUES (".$idPresupuesto.", ".$acc[$i].", ".$value.", ".$iva.", ".$civa.", ".$piva.");";
			$Result1 = mysql_query($sqla,$conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		} else if ($_POST['acaccion'][$i] == 3) { // ELIMINA
			$sqla = "UPDATE an_accesorio_presupuesto SET
				precio_accesorio = ".$value.",
				iva_accesorio = ".$iva.",
				costo_accesorio = ".$civa.",
				porcentaje_iva_accesorio = ".$piva."
			WHERE id_accesorio_presupuesto = ".$idDetAccPaq.";";
			$Result1 = mysql_query($sqla,$conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		} else if ($idPresupuesto > 0) {
			$sqla = "DELETE FROM an_accesorio_presupuesto WHERE id_accesorio_presupuesto = ".$idDetAccPaq.";";
			$Result1 = mysql_query($sqla,$conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		}
	} else {
		// PAQUETES
		if ($_POST['acaccion'][$i] == 1) { // INSERTA
			$sqla = "INSERT INTO an_paquete_presupuesto (id_presupuesto, id_acc_paq, precio_accesorio, iva_accesorio, costo_accesorio, porcentaje_iva_accesorio)
			VALUES (".$idPresupuesto.", ".$_POST['acp'][$i].", ".$value.", ".$iva.", ".$civa.", ".$piva.");";
			$Result1 = mysql_query($sqla,$conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		} else if ($_POST['acaccion'][$i] == 3) { // ELIMINA
			$sqla = "UPDATE an_paquete_presupuesto SET
				precio_accesorio = ".$value.",
				iva_accesorio = ".$iva.",
				costo_accesorio = ".$civa.",
				porcentaje_iva_accesorio = ".$piva."
			WHERE id_paquete_presupuesto = ".$idDetAccPaq.";";
			$Result1 = mysql_query($sqla,$conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);		
		} else if ($idPresupuesto > 0) {
			$sqla = "DELETE FROM an_paquete_presupuesto WHERE id_paquete_presupuesto = ".$idDetAccPaq.";";
			$Result1 = mysql_query($sqla,$conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		}	
	}
}

mysql_query("COMMIT;");

echo "<script language=\"javascript\" type=\"text/javascript\">
alert('Se ha registrado el presupuesto');
window.location.href='an_ventas_presupuesto_editar.php?view=1&id=".$idPresupuesto."';
</script>";
?>