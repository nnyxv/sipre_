<?php
require_once("../connections/conex.php");

@session_start();

// procesando ajax:
if (!isset($include)) {
	cache_expires();
}
//Recargas XML
if (isset($_GET['ajax_a']) && ($_GET['ajax_a'] != "")) {
	$ano = excape($_GET['ajax_a']);
	//$empleado=excape($_GET['ajax_unidad']);
	conectar();
	
	//extrae los unidades de la empresa:
	$sqlunidades = "SELECT
		id_empleado AS empleado,
		asesor AS unidad
	FROM vw_an_asesor_ventas
	WHERE id_empresa = ".getempresa()."
	ORDER BY unidad;";
	$resultunidades = @mysql_query($sqlunidades,$conex);
	if (!$resultunidades) die(mysql_error($conex)."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__);
	while ($rowunidades = mysql_fetch_assoc($resultunidades)){ // obteniendo datos
		$unidades[] = $rowunidades;
	}
	
	$sql = "SELECT 
		id_rollout_asesor AS id_rollout_unidades,
		id_empleado AS empleado,
		mes,
		sum(objetivo) AS objetivo
	FROM an_rollout_asesor 
	WHERE ano = ".$ano." 
	GROUP BY id_empleado, mes
	ORDER BY mes;";
	$result = @mysql_query($sql,$conex);
	if (!$result) die(mysql_error($conex)."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__);
	while ($row = mysql_fetch_assoc($result)) { // obteniendo datos
		$objetivos[$row['empleado']][$row['mes']] = $row;
		$tmes[$row['mes']] += $row['objetivo']; // total por mes
		$tunidad[$row['empleado']] += $row['objetivo']; // total unidad
		$total += $row['objetivo']; // total general
	}
} else {
	echo 'No se ha especificado la unidad';
	exit;
}
?>

<table border="0" width="100%">
<tr class="tituloColumna">
    <td width="22%">Asesores</td>
<?php for ($cont = 1; $cont <= 12; $cont++): ?>
    <td width="6%"><?php echo $arrayMes[$cont] ?></td>
<?php endfor; ?>
    <td width="6%">Total</td>
</tr>
<?php
if (isset($unidades)) {
	foreach ($unidades as $unidad) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++; ?>
		<tr align="left" class="<?php echo $clase; ?>" height="24">
			<td class="tituloCampo"><?php echo htmlentities($unidad['unidad']); ?></td>
		<?php for ($cont = 1; $cont <= 12; $cont++): ?>
			<td align="right"><?php echo valTpDato($objetivos[$unidad['empleado']][$cont]['objetivo'],"cero_por_vacio"); ?></td>
		<?php endfor; ?>
			<td align="right" class="divMsjInfo"><?php echo $tunidad[$unidad['empleado']]; ?></td>
		</tr>
<?php
	}
} ?>
<tr align="right" class="trResaltarTotal">
    <td class="tituloCampo">Total:</td>
<?php for ($cont = 1; $cont <= 12; $cont++) { ?>
    <td><b><?php echo $tmes[$cont]; ?></b></td>
<?php } ?>
    <td><b><?php echo $total; ?></b></td>
</tr>
</table>