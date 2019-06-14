<?php
require_once('../connections/conex.php');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_informe_venta_serial_list"))) {
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
	if(!(validaAcceso("an_cierre_ventas_ac"))) {
		echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
	}
}

//obteniendo datos de la unidad fisica:
$sqlunidad = "SELECT
	uni_fis.id_unidad_fisica,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.serial_chasis,
	CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
	uni_fis.placa,
	fact_comp.numero_factura_proveedor AS factura,
	uni_fis.fecha_pago_venta AS fecha,
	uni_fis.estado_venta AS observaciones
FROM an_unidad_fisica uni_fis
	INNER JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
	INNER JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
	INNER JOIN sa_unidad_empresa unidad_emp ON (vw_iv_modelo.id_uni_bas = unidad_emp.id_unidad_basica)
WHERE uni_fis.estado_venta = 'VENDIDO'
	AND date_format(uni_fis.fecha_pago_venta,'%m') = ".$mes."
	AND date_format(uni_fis.fecha_pago_venta,'%Y') = ".$ano."
	AND unidad_emp.id_empresa = ".$idEmpresa."
	AND vw_iv_modelo.catalogo = 1
ORDER BY uni_fis.fecha_pago_venta ASC, vw_iv_modelo.nom_modelo ASC;";
$resultunidad = @mysql_query($sqlunidad, $conex);
if (!$resultunidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowunidad = mysql_fetch_assoc($resultunidad)) {
	$unidades[$rowunidad['id_unidad_fisica']] = $rowunidad;
}
	
$nombre_distribuidor = htmlentities(getmysql("SELECT nombre_empresa FROM pg_empresa WHERE id_empresa = ".$idEmpresa.";"));

//importar la vista:
//echo var_dump($unidades);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Informe de Ventas por Serial</title>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <link href="an_informe_cliente_semanal_list.css" rel="stylesheet" type="text/css"/>
    
    <script type="text/javascript" src="vehiculos.inc.js"></script><script type="text/javascript" language="javascript">
    function cambiar(){
        var f=$('freload');
        f.submit();
    }
    </script>
</head>

<body class="bodyVehiculos" <?php if ($_GET['view'] == "print") { echo 'onload="print();"'; }?>>
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php if ($_GET['view'] != "print") { include("banner_vehiculos.php"); } ?></div>
    
    <table border="0" width="100%">
    <tr>
        <td class="tituloPaginaVehiculos">Informe de Ventas por Serial<br/><span class="texto_10px">(Venta Mensual <?php echo $arrayMes[$mes].' '.$ano; ?>)</span></td>
    </tr>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td class="tdtitulo"><?php echo $nombre_distribuidor; ?></td>
    </tr>
    <tr>
    	<td>
        	<table border="0" width="100%">
            <tr class="tituloColumna">
                <td width="2%">&nbsp;</td>
                <td width="28%">Modelo</td>
                <td width="10%"><?php echo $spanSerialCarroceria; ?></td>
                <td width="10%"><?php echo $spanSerialMotor; ?></td>
                <td width="10%">Nro. Vehículo</td>
                <td width="10%"><?php echo $spanPlaca; ?></td>
                <td width="10%">Nro. Factura</td>
                <td width="10%">Fecha Venta</td>
                <td width="10%">Observaciones</td>
            </tr>
            <?php 
            if (count($unidades) == 0) {
                echo "<tr height=\"24\"><td class=\"divMsjError\" colspan=\"9\">No se han registrado ventas para este mes</td></tr>";
            } else {
                foreach ($unidades as $unidad) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
                    $contFila++; ?>
            <tr align="left" class="<?php echo $clase; ?>" height="24">
                <td align="right"><?php echo $contFila; ?></td>
                <td><?php echo htmlentities($unidad['vehiculo']); ?></td>
                <td align="center"><?php echo htmlentities($unidad['serial_carroceria']); ?></td>
                <td align="center"><?php echo htmlentities($unidad['serial_motor']); ?></td>
                <td align="center"><?php echo htmlentities($unidad['serial_chasis']); ?></td>
                <td align="center"><?php echo htmlentities($unidad['placa']); ?></td>
                <td align="right"><?php echo htmlentities($unidad['factura']); ?></td>
                <td align="center"><?php echo date(spanDateFormat, strtotime($unidad['fecha'])); ?></td>
                <td align="center"><?php echo htmlentities($unidad['observaciones']); ?></td>
            </tr>
            <?php
				}
            } ?>
            </table>
        </td>
    </tr>
	</table>
	
	<?php if ($_GET['view']!="print"){ ?>
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
	
	<?php
    if ($_GET['view'] != "print") {
		echo '<div class="noprint">';
		include("pie_pagina.php");
		echo '</div>';
	} ?>
</div>
</body>
</html>