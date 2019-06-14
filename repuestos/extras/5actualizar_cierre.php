<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
$raiz = "../";
require_once("../../connections/conex.php");

session_start();

require ('../../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../../controladores/xajax/');

include("../../controladores/ac_if_generar_cierre_mensual.php");
include("../../controladores/ac_iv_general.php"); 

$xajax->processRequest();

$xajax->printJavascript('../../controladores/xajax/');

mysql_query("START TRANSACTION;");

// BUSCA LOS DATOS DEL CIERRE MENSUAL
$query = sprintf("SELECT * FROM iv_cierre_mensual cierre_mensual
WHERE id_cierre_mensual IN (SELECT MAX(a.id_cierre_mensual) FROM iv_cierre_mensual a)
ORDER BY ano ASC, mes ASC;");
$rs = mysql_query($query);
if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while($row = mysql_fetch_assoc($rs)) {
	$idCierreMensual = $row['id_cierre_mensual'];
	$idEmpresa = $row['id_empresa'];
	$mesCierre = $row['mes'];
	$anoCierre = $row['ano'];
	
	# ELIMINA
	$deleteSQL = sprintf("DELETE FROM iv_cierre_mensual_facturacion
	WHERE id_cierre_mensual = %s;",
		valTpDato($idCierreMensual, "int"));
	$Result1 = mysql_query($deleteSQL, $conex);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	# ELIMINA
	$deleteSQL = sprintf("DELETE FROM iv_cierre_mensual_orden
	WHERE id_cierre_mensual = %s;",
		valTpDato($idCierreMensual, "int"));
	$Result1 = mysql_query($deleteSQL, $conex);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// INSERTA LOS DATOS DE LA FACTURACION POR MOSTRADOR
	$Result1 = facturacionMostrador($idEmpresa, $mesCierre, $anoCierre);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		die($Result1[1]); 
	} else {
		$arrayVentaVendedor = $Result1[1];
		$totalVentaVendedores = $Result1[2];
	}
	if (isset($arrayVentaVendedor)) {
		foreach ($arrayVentaVendedor as $indice => $valor) {
			$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_facturacion (id_cierre_mensual, id_empleado, id_modulo, total_repuesto, total_facturacion_contado, total_facturacion_credito, total_devolucion_contado, total_devolucion_credito)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idCierreMensual, "int"),
				valTpDato($arrayVentaVendedor[$indice][0], "int"),
				valTpDato(0, "int"), // 0 = Repuestos, 1 = Servicios
				valTpDato($arrayVentaVendedor[$indice][9], "real_inglesa"),
				valTpDato($arrayVentaVendedor[$indice][2], "real_inglesa"),
				valTpDato($arrayVentaVendedor[$indice][3], "real_inglesa"),
				valTpDato($arrayVentaVendedor[$indice][4], "real_inglesa"),
				valTpDato($arrayVentaVendedor[$indice][5], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	// INSERTA LOS DATOS DE LA FACTURACION DE ASESORES
	$Result1 = facturacionAsesores($idEmpresa, $mesCierre, $anoCierre);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		die($Result1[1]); 
	} else {
		$arrayVentaAsesor = $Result1[1];
		$totalVentaAsesores = $Result1[2];
	}
	if (isset($arrayVentaAsesor)) {
		foreach ($arrayVentaAsesor as $indice => $valor) {
			$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_facturacion (id_cierre_mensual, id_empleado, id_modulo, id_tipo_orden, total_mano_obra, total_tot, total_repuesto)
			VALUE (%s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idCierreMensual, "int"),
				valTpDato($arrayVentaAsesor[$indice]['id_empleado'], "int"),
				valTpDato(1, "int"), // 0 = Repuestos, 1 = Servicios
				valTpDato($arrayVentaAsesor[$indice]['id_tipo_orden'], "int"),
				valTpDato($arrayVentaAsesor[$indice]['total_mo'], "real_inglesa"),
				valTpDato($arrayVentaAsesor[$indice]['total_tot'], "real_inglesa"),
				valTpDato($arrayVentaAsesor[$indice]['total_repuestos'], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	// INSERTA LAS ORDENES DE SERVICIOS ABIERTAS Y CERRADAS
	$Result1 = cierreOrdenesServicio($idEmpresa, $mesCierre, $anoCierre);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		die($Result1[1]); 
	} else {
		$arrayTipoOrden = $Result1[1];
		$totalTipoOrdenAbierta = $Result1[2];
		$totalTipoOrdenCerrada = $Result1[3];
	}
	if (isset($arrayTipoOrden)) {
		foreach ($arrayTipoOrden as $indice => $valor) {
			$insertSQL = sprintf("INSERT INTO iv_cierre_mensual_orden (id_cierre_mensual, id_tipo_orden, cantidad_abiertas, cantidad_cerradas)
			VALUE (%s, %s, %s, %s);",
				valTpDato($idCierreMensual, "int"),
				valTpDato($indice, "int"),
				valTpDato($arrayTipoOrden[$indice]['cantidad_abiertas'], "int"),
				valTpDato($arrayTipoOrden[$indice]['cantidad_cerradas'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
	}
}

mysql_query("COMMIT;");

echo "<h1>CIERRES GENERADOS CON EXITO</h1>";
?>