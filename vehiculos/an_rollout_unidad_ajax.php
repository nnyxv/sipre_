<?php
require_once("../connections/conex.php");

@session_start();

$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

// procesando ajax:
if (!isset($include)) {
	cache_expires();
}
//Recargas XML
if (isset($_GET['ajax_a'])  && ($_GET['ajax_a']!="") ) {
	$ano = excape($_GET['ajax_a']);
	//$id_uni_bas=excape($_GET['ajax_unidad']);
	conectar();
	
	//extrae los unidades de la empresa:
	$sqlunidades = sprintf("SELECT
		id_unidad_basica,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
	FROM sa_unidad_empresa unidad_emp
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (unidad_emp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
	WHERE id_empresa = %s
		AND catalogo = 1
	ORDER BY vw_iv_modelo.nom_uni_bas ASC;",
		valTpDato($idEmpresa, "int"));
	$resultunidades = @mysql_query($sqlunidades,$conex);
	if (!$resultunidades) die(mysql_error($conex)."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__);
	$cont = 0;
	while($rowunidades = mysql_fetch_assoc($resultunidades)){
		$arrayUnidad[$cont][0] = $rowunidades['vehiculo'];
		$arrayUnidad[$cont][15] = $rowunidades['id_unidad_basica'];
		
		$cont++;
	}
	
	$sql = "SELECT 
		id_rollout_asesor as id_rollout_unidades,
		id_uni_bas,
		mes,
		sum(objetivo) as objetivo
	FROM an_rollout_asesor 
	WHERE ano = ".$ano."
	GROUP BY id_uni_bas, mes
	ORDER BY mes;";
	$result = @mysql_query($sql,$conex);
	if (!$result) die(mysql_error($conex)."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__);
	while ($row = mysql_fetch_assoc($result)){
		foreach ($arrayUnidad as $indice => $valor) {
			if ($arrayUnidad[$indice][15] == $row['id_uni_bas']) {
				$arrayUnidad[$indice][13] += $row['objetivo'];
				$arrayUnidad[$indice][$row['mes']] += $row['objetivo'];
				
				$arrayTot[$row['mes']] += $row['objetivo'];
				$arrayTot[13] += $row['objetivo'];
			}
		}
	}
} else {
	echo 'No se ha especificado la unidad';
	exit;
} ?>

<table border="0" width="100%">
<tr class="tituloColumna">
    <td width="22%">Unidades</td>
<?php for ($cont = 1; $cont <= 12; $cont++) { ?>
    <td width="6%"><?php echo $arrayMes[$cont]; ?></td>
<?php } ?>
    <td width="6%">Total</td>
</tr>
<?php
if (isset($arrayUnidad)) {
	foreach ($arrayUnidad as $indice => $valor) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		$contFila++; ?>
		<tr align="left" class="<?php echo $clase; ?>" height="24">
			<td class="tituloCampo"><?php echo htmlentities($arrayUnidad[$indice][0]); ?></td>
		<?php for ($cont = 1; $cont <= 12; $cont++) { ?>
			<td align="right"><?php echo valTpDato($arrayUnidad[$indice][$cont],"cero_por_vacio"); ?></td>
		<?php } ?>
			<td align="right" class="divMsjInfo"><?php echo valTpDato($arrayUnidad[$indice][13],"cero_por_vacio"); ?></td>
		</tr>
	<?php
	}
} ?>
<tr align="right" class="trResaltarTotal">
    <td class="tituloCampo">Total:</td>
<?php for ($cont = 1; $cont <= 12; $cont++) { ?>
	<td><b><?php echo valTpDato($arrayTot[$cont],"cero_por_vacio"); ?></b></td>
<?php } ?>
    <td><b><?php echo valTpDato($arrayTot[13],"cero_por_vacio"); ?></b></td>
</tr>
</table>