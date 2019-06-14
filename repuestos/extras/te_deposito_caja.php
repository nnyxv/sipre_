<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
// QUITAR EL COMENTARIO SI SE VA A UTILIZAR ESTE ARCHIVO PARA LA EJECUCION EN MASA
require_once("../../connections/conex.php");

mysql_query("START TRANSACTION;");

// BUSCA LOS DEPOSITOS DE TESORERIA QUE NO ESTAN RELACIONADOS CON DEPOSITOS DE LAS CAJAS
$queryDeposito = sprintf("SELECT * FROM te_depositos WHERE id_planilla_deposito IS NULL;");
$rsDeposito = mysql_query($queryDeposito);
if (!$rsDeposito) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
while ($rowDeposito = mysql_fetch_assoc($rsDeposito)) {
	$idEmpresa = $rowDeposito['id_empresa'];
	$fechaRegistro = $rowDeposito['fecha_registro'];
	$idCaja = $rowDeposito['origen']; // 0 = Tesoreria , 1 = Caja Vehiculos , 2 = Caja Repuestos y Servicios
	$numeroPlanilla = $rowDeposito['numero_deposito_banco'];
	$totalEfectivo = $rowDeposito['monto_efectivo'];
	$totalCheque = $rowDeposito['monto_cheques_total'];
	
	// BUSCA LA PLANILLA DE DEPOSITO QUE SE ASEMEJE AL INGRESADO EN TESORERIA
	$queryPlanilaDeposito = sprintf("SELECT *,
		(SELECT SUM(deposito_det.monto) FROM an_detalledeposito deposito_det
		WHERE deposito_det.idPlanilla = planilla_deposito.idPlanilla
			AND deposito_det.formaPago = 1
			AND deposito_det.anulada LIKE 'NO') AS total_efectivo,
		(SELECT SUM(deposito_det.monto) FROM an_detalledeposito deposito_det
		WHERE deposito_det.idPlanilla = planilla_deposito.idPlanilla
			AND deposito_det.formaPago = 2
			AND deposito_det.anulada LIKE 'NO') AS total_cheques,
		(SELECT deposito_det.numeroDeposito FROM an_detalledeposito deposito_det
		WHERE deposito_det.numeroDeposito = %s
			AND deposito_det.idPlanilla = planilla_deposito.idPlanilla
			AND deposito_det.anulada LIKE 'NO'
		LIMIT 1) AS numeroDeposito
	FROM an_encabezadodeposito planilla_deposito
	WHERE id_empresa = %s
		AND fechaPlanilla = %s
		AND idCaja = %s
		AND idPlanilla NOT IN (SELECT id_planilla_deposito FROM te_depositos WHERE id_planilla_deposito IS NOT NULL)",
		valTpDato($numeroPlanilla, "text"),
		valTpDato($idEmpresa, "int"),
		valTpDato($fechaRegistro, "date"),
		valTpDato($idCaja, "int"));
	$rsPlanilaDeposito = mysql_query($queryPlanilaDeposito);
	if (!$rsPlanilaDeposito) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPlanilaDeposito = mysql_fetch_array($rsPlanilaDeposito);
	
	echo "<pre>".$queryPlanilaDeposito."</pre>";
	
	echo "<ul>";
		echo "<li>NRO PLANILLA => TE: ".$numeroPlanilla." - CJ: ".$rowPlanilaDeposito['numeroDeposito']."</li>";
		echo "<li>EFECTIVO => TE: ".$totalEfectivo." - CJ: ".$rowPlanilaDeposito['total_efectivo']."</li>";
		echo "<li>CHEQUE => TE: ".$totalCheque." - CJ: ".$rowPlanilaDeposito['total_cheques']."</li>";
	echo "</ul>";
	
	if ($idCaja > 0
		&& ($numeroPlanilla == $rowPlanilaDeposito['numeroDeposito']
			|| $totalEfectivo == $rowPlanilaDeposito['total_efectivo']
			|| $totalCheque == $rowPlanilaDeposito['total_cheques'])) {
		$updateSQL = sprintf("UPDATE te_depositos SET
			id_planilla_deposito = %s
		WHERE id_deposito = %s;",
			valTpDato($rowPlanilaDeposito['idPlanilla'], "int"),
			valTpDato($rowDeposito['id_deposito'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		echo "<pre>".$updateSQL."</pre>";
	}
	
	echo "<hr>";
}

mysql_query("COMMIT;");

echo "<h1>RELACION DE DEPOSITOS INGRESADOS CON EXITO</h1>";
?>