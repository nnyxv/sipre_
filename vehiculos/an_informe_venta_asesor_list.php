<?php
require_once('../connections/conex.php');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_informe_venta_asesor_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

conectar();
//Registra el año completo:
$ano = excape(intval($_POST['a']));
if ($ano == 0) {
	$ano = date('Y');
}
//verifica que dicho año, tenga registros
$verifica = intval(getmysql("SELECT COUNT(*) FROM an_listado_semanal WHERE date_format(semana_inicio,'%Y') = ".intval($ano).";"));
if ($verifica != 52) {//el año esta incompleto, lo crea por semanas:
	for ($contMes = 1; $contMes <= 12; $contMes++) {
		$primerdiames = mktime(0,0,0,$contMes,1,$ano);
		//$fecha_actual=$primerdiames;
		for($i = 1; $i < date('t',$primerdiames); $i += 7) {
			$fecha_actual = mktime(0,0,0,$contMes,$i,$ano);
			
			$existe = "SELECT COUNT(*) FROM an_listado_semanal WHERE ".setmysqlfecha(date(spanDateFormat,$fecha_actual))." BETWEEN semana_inicio AND semana_fin;";
			
			if (intval(getmysql($existe)) == 0) {
				//calcula el rango de la semana
				$dia_semana = date('w',$fecha_actual); //0= DOMINGO, 1=LUNES - 6=sabado
				
				//sacar el domingo anterior:
				$domingo = mktime(0, 0, 0, date("m",$fecha_actual)  , date("d",$fecha_actual)-$dia_semana, date("Y",$fecha_actual));
				
				//sacar el sábado					
				$sabado = $domingo + (6 * 24 * 60 * 60);//6 dias, 24 horas 60 minutos 60 segundos (1 semana completa)
				
				//echo date(spanDateFormat,$domingo), ' a ', date(spanDateFormat,$sabado).'<br />';//PERFECTO!!!
				$sqlinsert = "INSERT INTO an_listado_semanal(semana_inicio, semana_fin, numero_semana)
				VALUES (".setmysqlfecha(date(spanDateFormat,$domingo)).", ".setmysqlfecha(date(spanDateFormat,$sabado)).", (date_format(".setmysqlfecha(date(spanDateFormat,$domingo)).",'%U')-date_format(".setmysqlfecha(date(spanDateFormat,$primerdiames)).",'%U')));";
				$result = @mysql_query($sqlinsert, $conex);
				if (!$result) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			}
		}
	}
}

//Listado de los Vendedores:
$sqlasesor = sprintf("SELECT
	id_empleado,
	concat(apellido,' ',nombre_empleado) AS asesor
FROM pg_empleado
	INNER JOIN pg_cargo_departamento ON (pg_cargo_departamento.id_cargo_departamento = pg_empleado.id_cargo_departamento)
	INNER JOIN pg_cargo ON (pg_cargo.id_cargo = pg_cargo_departamento.id_cargo)
	INNER JOIN pg_departamento ON (pg_departamento.id_departamento = pg_cargo_departamento.id_departamento)
WHERE clave_filtro IN (1,2)
	AND activo = 1
ORDER BY asesor;");
$rasesor = @mysql_query($sqlasesor, $conex);
if (!$rasesor) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowasesor = mysql_fetch_assoc($rasesor)) {
	$asesores[] = array("id" => $rowasesor['id_empleado'], "asesor" => $rowasesor['asesor']);
}

//extrae las unidades básicas:
$sql_unidades = sprintf("SELECT *
FROM sa_unidad_empresa unidad_emp
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (unidad_emp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
WHERE id_empresa = %s
	AND catalogo = 1
ORDER BY vw_iv_modelo.nom_uni_bas ASC;",
	valTpDato($idEmpresa, "int"));
$r_unidades = @mysql_query($sql_unidades, $conex);
if (!$r_unidades) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($row = @mysql_fetch_assoc($r_unidades)) {
	$unidades[] = array(
		'id_uni_bas' => $row['id_unidad_basica'],
		'nombre' => htmlentities($row['nombre_unidad_basica']),
		'modelo' => htmlentities($row['nom_modelo']),
		'version' => htmlentities($row['nom_version']),
		'transmision' => htmlentities($row['nom_transmision']));
}


$mes = excape(intval($_POST['mes']));
if ($mes == 0 || $mes == "") {
	$mes = intval(date('m'));
}

//verifica fechas futuras:
if ($ano > intval(date('Y'))) {
	$ano = intval(date('Y'));
	$mes = intval(date('m'));
}
if ($mes > intval(date('m')) && $ano == intval(date('Y'))) {
	$mes = intval(date('m'));
}

//verifica si tiene acceso completo:
if ($mes != intval(date('m')) || $ano != intval(date('Y'))) {
	$redirect="an_informe_venta_asesor_list.php?view=1";
	validaModulo('an_cierre_ventas_ac');
}
$umes = intval(date('t',mktime(0,0,0,$mes,1,$ano)));

//extrae las semanas del mes:
$crit = excape($_GET['omitirprimerasemana']);
$crit = ($crit == "") ? "fin" : "inicio";
$criterio = "semana_".$crit;
$sql_mes = "SELECT 
	an_listado_semanal.id_listado_semanal,
	an_listado_semanal.semana_inicio,
	an_listado_semanal.semana_fin,
	if(semana_inicio < str_to_date('".$ano."-".$mes."-1','%Y-%m-%d'),str_to_date('".$ano."-".$mes."-1','%Y-%m-%d'),semana_inicio) as si,
	if(semana_fin > str_to_date('".$ano."-".$mes."-".$umes."','%Y-%m-%d'),str_to_date('".$ano."-".$mes."-".$umes."','%Y-%m-%d'),semana_fin) as sf,
	date_format('%w',if(semana_inicio < str_to_date('".$ano."-".$mes."-1','%Y-%m-%d'),str_to_date('".$ano."-".$mes."-1','%Y-%m-%d'),semana_inicio)) as siw,
	an_listado_semanal.numero_semana,
	an_listado_semanal.situacion,
	an_listado_semanal.eventos,
	an_listado_semanal.otros,
	date_format(semana_inicio, '%m') AS f1,
	date_format(semana_fin, '%m') AS f2
FROM an_listado_semanal
WHERE (date_format(semana_inicio, '%m') = ".$mes."
		AND date_format(semana_inicio, '%Y') = ".$ano.")
	OR (date_format(semana_fin, '%m') = ".$mes."
		AND date_format(semana_fin, '%Y') = ".$ano.")
ORDER BY semana_inicio";
$result_mes = mysql_query($sql_mes, $conex);
if (!$result_mes) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$snumero = 0;
while ($row_mes = mysql_fetch_assoc($result_mes)) {
	$snumero++;
	
	//extrae los totales por semana de vehiculos vendidos
	$sql_semana = "SELECT 
		fact_vent.idVendedor AS id_vendedor, 
		uni_fis.id_uni_bas AS id_uni_bas, 
		COUNT(*) AS cantidad
	FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
		INNER JOIN an_unidad_fisica uni_fis ON (fact_vent_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN cj_cc_encabezadofactura fact_vent ON (fact_vent_det_vehic.id_factura = fact_vent.idFactura)
	WHERE fecha_pago_venta BETWEEN '".$row_mes['si']."' AND '".$row_mes['sf']."' and estado_venta='VENDIDO'
	GROUP BY fact_vent.idVendedor, id_uni_bas";
	$result_semana = mysql_query($sql_semana, $conex);
	if (!$result_semana) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$semana = NULL;
	unset($tsemana);
	while ($row_sem = mysql_fetch_assoc($result_semana)) {
		$semana[$row_sem['id_vendedor']][$row_sem['id_uni_bas']] = $row_sem['cantidad'];
		$tunidadsem[$snumero][$row_sem['id_uni_bas']] += $row_sem['cantidad'];
		$totalsem[$snumero] += $row_sem['cantidad'];
		$tsemana[$row_sem['id_vendedor']] += $row_sem['cantidad'];
	}
	
	$semanas[] = array(
		"snumero" => $snumero,
		"id_listado_semanal" => $row_mes['id_listado_semanal'],
		"situacion" => $row_mes['situacion'],
		"eventos" => $row_mes['eventos'],
		"otros" => $row_mes['otros'],
		"semana_inicio" => $row_mes['semana_inicio'],
		"semana_fin" => $row_mes['semana_fin'],
		"si" => $row_mes['si'],
		"sf" => $row_mes['sf'],
		"siw" => $row_mes['siw'],
		"unidades" => $semana,
		"total" => $tsemana,
		"s" => $sql_semana);
}

/*echo var_dump($semanas);
echo "<!-- otro -->";
echo var_dump($tunidadsem);
echo $totalsem;*/

function setnumeric($num){
	return ($num == "") ? 0 : $num;
}

$nombre_distribuidor = htmlentities(getmysql(sprintf("SELECT nombre_empresa FROM pg_empresa
WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"))));
if ($_GET['omitirprimerasemana'] != "") {
	$ops = "&omitirprimerasemana=1";
}

//importar la vista:
//echo var_dump($semanas);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Informe de Ventas por Asesor</title>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <link href="an_informe_cliente_semanal_list.css" rel="stylesheet" type="text/css" />
    
    <script type="text/javascript" src="vehiculos.inc.js"></script>
    
    <script type="text/javascript" language="javascript">
    function cambiar(){
        var f = $('freload');
        f.submit();
    }
    </script>
</head>

<body class="bodyVehiculos" <?php if($_GET['view']=="print"){echo 'onload="print();"'; }?>>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php if ($_GET['view'] != "print") { include("banner_vehiculos.php"); } ?></div>
    
    <table border="0" width="100%">
    <tr>
        <td class="tituloPaginaVehiculos">Informe de Ventas por Asesor<br/><span class="texto_10px">(Ventas Semanales <?php echo $arrayMes[$mes].' '.$ano; ?>)</span></td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td class="tdtitulo"><?php echo $nombre_distribuidor; ?></td>
    </tr>
    <tr>
    	<td>
        <?php
		if (isset($semanas)) {
			foreach ($semanas as $semana) {
				if ($semana['si'] == $semana['sf'] && $semana['siw'] == 0) continue; ?>
				<div style="page-break-after:auto;"></div>
				<table border="0" width="100%">
				<tr class="tituloColumna">
					<td nowrap="nowrap" title="<?php echo $semana['semana_inicio'].' / '.$semana['semana_fin'].' ('.$semana['si'].' / '.$semana['sf'].')'; ?>" class="toptd" width="10%">SEMANA <?php echo $semana['snumero']; ?></td>
					<?php
					foreach ($unidades as $unidad){ //llenando el patrón:
						echo '<td class="toptd">'.$unidad['modelo'].' '.$unidad['version'].'</td>';
					} ?>
					<td rowspan="2" class="toptd" width="5%">Total</td>
				</tr>
				<tr class="tituloColumna" height="24">
					<td class="toptd">Nombre Asesor</td>
					<?php
					foreach ($unidades as $unidad){ //llenando el patrón:
						echo '<td class="toptd">Facturado</td>';
					} ?>
					<!--<td class="toptd" width="5%">Total</td>-->
				</tr>
				<?php
				foreach ($asesores as $asesor) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
                    $contFila++; ?>
				<tr align="left" class="<?php echo $clase; ?>" height="24">
					<td class="tituloColumna"><?php echo htmlentities($asesor['asesor']); ?></td>
					<?php foreach($unidades as $unidad){ ?>
						<td align="right"><?php echo valTpDato(setnumeric($semana['unidades'][$asesor["id"]][$unidad['id_uni_bas']]),"cero_por_vacio"); ?> </td>
					<?php } ?>
					<td align="right" class="trResaltarTotal"><?php echo valTpDato(setnumeric($semana['total'][$asesor["id"]]),"cero_por_vacio"); ?></td>
				</tr>
				<?php
				} ?>
				<tr align="right" class="trResaltarTotal" height="24">
					<td class="tituloCampo">Totales:</td>
					<?php foreach ($unidades as $unidad) { ?>
						<td class="toptd"><?php echo valTpDato(setnumeric($tunidadsem[$semana['snumero']][$unidad['id_uni_bas']]),"cero_por_vacio"); ?></td>
					<?php } ?>	
					<td class="toptd"><?php echo valTpDato(setnumeric($totalsem[$semana['snumero']]),"cero_por_vacio"); ?></td>
				</tr>
				</table>
				<br/>
			<?php
			}
		} ?>
        </td>
	</tr>
	</table>
    
	<?php if ($_GET['view'] != "print") { ?>
<form id="freload" name="freload" method="post">
    <hr/>
    <table class="noprint" width="100%">
    <tr>
        <td>
            <select id="mes" name="mes"> 
            <?php
            $mesactual = $mes;
            for ($i = 1; $i <= 12; $i++) {
                $selected = ($i == $mesactual) ? "selected=\"selected\"" : "";
                echo "<option ".$selected." value=\"".$i."\">".$arrayMes[$i]."</option>";
            } ?>
            </select>
        </td>
        <td>
            <select id="a" name="a"> 
            <?php
            $anoactual = $ano;
            for ($i = $anoactual - 10; $i <= $anoactual + 10; $i++) {
                $selected = ($i == $anoactual) ? "selected=\"selected\"" : "";
                echo "<option ".$selected." value=\"".$i."\">".$i."</option>";
            } ?>
            </select>
        </td>
        <td nowrap="nowrap">
            <button type="button" onclick="cambiar();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img border="0" src="../img/iconos/return.png"/></td><td>&nbsp;</td><td>Recargar</td></tr></table></button>
            <button type="button" onclick="print();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
        </td>
        <td align="center" width="100%">Reporte Generado el <?php echo date(spanDateFormat).' - '.getmysql("select date_format(now(),'%h:%i %p');") ?></td>
    </tr>
    </table>
</form>
	<?php } ?>
    
	<div class="noprint"><?php if ($_GET['view'] != "print") { include("pie_pagina.php"); } ?></div>
</div>
</body>
</html>