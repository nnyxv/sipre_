<?php
require_once("../connections/conex.php");

@session_start();

// procesando ajax:
if (!isset($include)) {
	cache_expires();
}
//Recargas XML
if (isset($_GET['ajax_a']) && isset($_GET['ajax_unidad']) && ($_GET['ajax_a']!="") && ($_GET['ajax_unidad']!="")) {
	$ano = excape($_GET['ajax_a']);
	$vmes = getmysqlnum($_GET['ajax_mes']);
	if ($vmes == 0) {
		require_once('../inc_sesion.php');
		validaModulo('an_rollout_asesor');
	}
	$id_uni_bas = excape($_GET['ajax_unidad']);
	conectar();
	
	//extrae los asesores de la empresa:
	$sqlasesores = "SELECT
		id_empleado,
		asesor
	FROM vw_an_asesor_ventas
	WHERE id_empresa = ".getempresa()."
	ORDER BY asesor;";
	$resultasesor = @mysql_query($sqlasesores,$conex);
	if (!$resultasesor) die(mysql_error($conex)."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__);
	while ($rowa = mysql_fetch_assoc($resultasesor)) { // obteniendo datos
		$asesores[] = $rowa;
	}
	
	
	$sql = "SELECT 
		an_rollout_asesor.id_rollout_asesor,
		vw_an_asesor_ventas.id_empleado as empleado,
		an_rollout_asesor.mes,
		an_rollout_asesor.objetivo
	FROM an_rollout_asesor
		INNER JOIN vw_an_asesor_ventas ON (vw_an_asesor_ventas.id_empleado = an_rollout_asesor.id_empleado)
	WHERE id_empresa = ".getempresa()."
		AND ano = ".$ano."
		AND id_uni_bas = ".$id_uni_bas."
	ORDER BY mes;";
	$result = @mysql_query($sql,$conex);
	if (!$result) die(mysql_error($conex)."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__);
	while ($row = mysql_fetch_assoc($result)) { // obteniendo datos
		$objetivos[$row['empleado']][$row['mes']] = $row;
	}
} else {
	echo 'No se ha especificado la unidad';
	exit;
}

function blockmes($m) {
global $vmes;
	if ($vmes == 0) {
		return false;
	} else if ($m == $vmes) {
		return false;
	}
	return true;
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
if (isset($asesores)) {
	foreach ($asesores as $asesor) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++; ?>
		<tr align="left" class="<?php echo $clase; ?>" height="24">
			<td class="tituloCampo">
				<?php echo htmlentities($asesor['asesor']); ?>
				<input type="hidden" name="asesor[]" id="asesor[]" value="<?php echo htmlentities($asesor['id_empleado']); ?>" />
			</td>
		<?php for ($cont = 1; $cont <= 12; $cont++) { ?>
			<td align="right" <?php if(blockmes($cont)) echo "style=\"background:#F2F2F2;\"" ?>>
				<input type="text" id="objetivo[<?php echo $asesor['id_empleado'] ?>][<?php echo $cont ?>]" name="objetivo[<?php echo $asesor['id_empleado'] ?>][<?php echo $cont ?>]" class="inputSinFondo" <?php if(blockmes($cont)) echo "readonly=\"readonly\"" ?> onkeyup="this.value=parsenum(this.value);percent();" onkeypress="return inputonlyint(event);" maxlength="3" value="<?php echo valTpDato($objetivos[$asesor['id_empleado']][$cont]['objetivo'],"cero_por_vacio"); ?>"  />
				<input type="hidden" id="id_rollout_asesor[<?php echo $asesor['id_empleado'] ?>][<?php echo $cont ?>]" name="id_rollout_asesor[<?php echo $asesor['id_empleado'] ?>][<?php echo $cont ?>]" value="<?php echo $objetivos[$asesor['id_empleado']][$cont]['id_rollout_asesor'] ?>"  />
			</td>
		<?php } ?>
			<td align="right" class="divMsjInfo" id="easesor[<?php echo $asesor['id_empleado'] ?>]"></td>
		</tr>
<?php
	}
} ?>
<tr align="right" class="trResaltarTotal">
    <td class="tituloCampo">Total:</td>
<?php for ($cont = 1; $cont <= 12; $cont++): ?>
    <td><b><span id="mes[<?php echo $cont ?>]"></span></b></td>
<?php endfor; ?>
    <td><b><span id="total"></span></b></td>
</tr>
</table>
<br>
<center>
	<button type="button" onclick="enviar('rollout');" value="guardar"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
</center>