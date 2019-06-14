<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_informe_rollout_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

conectar();
//Registra el año completo:
$ano = excape(intval($_POST['a']));
if($ano == 0) {
	$ano = date('Y');
}
$mes = excape(intval($_POST['mes']));
if($mes == 0 || $mes == ""){
	$mes=intval(date('m'));
}
//verifica fechas futuras:
if($ano>intval(date('Y'))){
	$ano=intval(date('Y'));
	$mes=intval(date('m'));
}
if($mes > intval(date('m')) && $ano == intval(date('Y'))) {
	$mes = intval(date('m'));
}
//verifica si tiene acceso completo:
if ($mes != intval(date('m')) || $ano != intval(date('Y'))) {
	if(!(validaAcceso("an_informe_rollout_list"))) {
		echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
	}
}
//verifica que dicho año, tenga registros
		
//Listado de los Vendedores:
$sqlasesor = "SELECT id_empleado, asesor FROM vw_an_asesor_ventas
WHERE id_empresa = ".$idEmpresa."
ORDER BY asesor;";
$rasesor = @mysql_query($sqlasesor,$conex);
if (!$rasesor) die(mysql_error($conex)."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__);
while($rowasesor=mysql_fetch_assoc($rasesor)){
	$asesores[$rowasesor['id_empleado']] = $rowasesor;
}

//extrae las unidades básicas:
$sql_unidades = "SELECT *,
	CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
FROM sa_unidad_empresa unidad_emp
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (unidad_emp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
WHERE id_empresa = ".$idEmpresa."
	AND catalogo = 1;";
$r_unidades = @mysql_query($sql_unidades,$conex);
if (!$r_unidades) die(mysql_error($conex)."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__);
while($row = @mysql_fetch_assoc($r_unidades)){
	$unidades[$row['id_unidad_basica']] = $row;
}
	
//reuniendo los valores:
$sqlrollout = "SELECT
	id_rollout_asesor,
	id_uni_bas,
	id_empleado,
	ano,
	mes,
	objetivo,
	ventas,
	diferencia
FROM vw_an_rollout
WHERE ano = ".$ano."
	AND mes = ".$mes."
	AND id_empresa = ".$idEmpresa."
	AND catalogo = 1;";
$resultrollout = @mysql_query($sqlrollout,$conex);
if (!$resultrollout) die(mysql_error($conex)."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__);
while($rowrollout = mysql_fetch_assoc($resultrollout)){
	$rollout[$rowrollout['id_empleado']][$rowrollout['id_uni_bas']] = array(
		'objetivo' => $rowrollout['objetivo'],
		'ventas' => $rowrollout['ventas'],
		'diferencia' => $rowrollout['diferencia']);
	$asesores[$rowrollout['id_empleado']]['totalobjetivo'] += $rowrollout['objetivo'];
	$asesores[$rowrollout['id_empleado']]['totalventas'] += $rowrollout['ventas'];
	$asesores[$rowrollout['id_empleado']]['totaldiferencia'] += $rowrollout['diferencia'];
	
	
	$unidades[$rowrollout['id_uni_bas']]['totalobjetivo'] += $rowrollout['objetivo'];
	$unidades[$rowrollout['id_uni_bas']]['totalventas'] += $rowrollout['ventas'];
	$unidades[$rowrollout['id_uni_bas']]['totaldiferencia'] += $rowrollout['diferencia'];
	
	$total['totalobjetivo'] += $rowrollout['objetivo'];
	$total['totalventas'] += $rowrollout['ventas'];
	$total['totaldiferencia'] += $rowrollout['diferencia'];
}

//echo $sqlrollout,' ',var_dump($rollout);
	
function eval_direfencia($val){
	if($val==0){
		return '<span>'.$val.'</span>';
	}else if($val<0){
		return '<span style="color:#FF0000">'.$val.'</span>';
	}else{
		return '<span style="color:#0000FF">'.$val.'</span>';
	}
}

function setnumeric($num){
	return ($num == "") ? 0 : $num;
}

$nombre_distribuidor = htmlentities(getmysql("SELECT nombre_empresa FROM pg_empresa WHERE id_empresa = ".$idEmpresa.";"));

//echo var_dump($semanas);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Reporte Objetivos / Ventas</title>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <link rel="stylesheet" type="text/css" href="an_informe_cliente_semanal_list.css">
    
    <script type="text/javascript" src="vehiculos.inc.js"></script>
    <script type="text/javascript" language="javascript">
    function cambiar(){
        var f=$('freload');
        f.submit();
    }
    </script>
</head>

<body class="bodyVehiculos" <?php if($_GET['view'] == "print"){ echo 'onload="print();"'; }?>>
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php if ($_GET['view'] != "print") { include("banner_vehiculos.php"); } ?></div>
	
    <table border="0" width="100%">
    <tr>
        <td class="tituloPaginaVehiculos">Reporte Objetivos / Ventas<br/><span class="texto_10px">(ROLL-OUT Objetivos / Ventas <?php echo $arrayMes[$mes].' '.$ano; ?>)</span></td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td class="tdtitulo"><?php echo $nombre_distribuidor; ?></td>
    </tr>
    <tr>
    	<td>
        	<table border="0" class="texto_9px" width="100%">
            <tr class="tituloColumna">
                <td nowrap="nowrap" width="10%">Unidades</td>
                <?php
                foreach($unidades as $unidad){
                    echo '<td colspan="3" class="toptd">'.htmlentities($unidad['vehiculo']).'</td>';
                } ?>
                <td rowspan="2" width="5%">Total OBJ</td>
                <td rowspan="2" width="5%">Total VEN</td>
                <td rowspan="2" width="5%">Total E</td>
            </tr>
            <tr class="tituloColumna">
                <td>Asesores</td>
            <?php foreach($unidades as $unidad): ?>
                <td>OBJ</td>
                <td>VEN</td>
                <td>E</td>
            <?php endforeach; ?>
                <!--<td class="toptd" width="5%">Total</td>-->
            </tr>
        <?php
        foreach($asesores as $asesor) {
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $contFila++; ?>
            <tr align="left" class="<?php echo $clase; ?>" height="24">
                <td><?php echo htmlentities($asesor['asesor']); ?></td>
            <?php foreach($unidades as $unidad) { ?>
                <td align="right"><?php echo $rollout[$asesor['id_empleado']][$unidad['id_unidad_basica']]['objetivo'] ?></td>
                <td align="right"><?php echo $rollout[$asesor['id_empleado']][$unidad['id_unidad_basica']]['ventas'] ?></td>
                <td align="right"><?php echo eval_direfencia($rollout[$asesor['id_empleado']][$unidad['id_unidad_basica']]['diferencia']) ?></td>
            <?php } ?>
                <td align="right"><?php echo $asesor['totalobjetivo'] ?></td>
                <td align="right"><?php echo $asesor['totalventas'] ?></td>
                <td align="right"><?php echo eval_direfencia($asesor['totaldiferencia']) ?></td>
            </tr>
        <?php
        } ?>
            <tr align="right" class="trResaltarTotal">
                <td class="tituloCampo">Totales</td>
            <?php foreach($unidades as $unidad) { ?>
                <td><?php echo $unidad['totalobjetivo'] ?></td>
                <td><?php echo $unidad['totalventas'] ?></td>
                <td><?php echo eval_direfencia($unidad['totaldiferencia']) ?></td>
            <?php } ?>	
                <td><?php echo $total['totalobjetivo'] ?></td>
                <td><?php echo $total['totalventas'] ?></td>
                <td><?php echo eval_direfencia($total['totaldiferencia']) ?></td>
            </tr>
            </table>
        </td>
	</tr>
    </table>

	<?php if ($_GET['view'] != "print") { ?>
<form id="freload" name="freload" method="post">
    <hr/>
    <table>
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
        <td align="center" width="100%">Reporte generado el <?php echo date(spanDateFormat).' - '.getmysql("select date_format(now(),'%h:%i %p');") ?></td>
	</tr>
	</table>
</form>
<?php } ?>

	<div class="noprint"><?php if ($_GET['view'] != "print") { include("pie_pagina.php"); } ?></div>
</div>
</body>
</html>