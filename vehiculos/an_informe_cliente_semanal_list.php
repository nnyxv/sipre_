<?php
require_once('../connections/conex.php');

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_informe_cliente_semanal_list"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

if (!isset($_GET['view'])) {
	$redirect = "an_informe_cliente_semanal_list.php?view=1";
	validaModulo('an_informe_cliente_semanal_list',editar);
}

conectar();
//Registra el año completo:
$ano = excape(intval($_POST['a']));
@session_start();
if ($ano == 0 && isset($_SESSION['sesion_ano'])) {
	$ano=intval($_SESSION['sesion_ano']);
	unset($_SESSION['sesion_ano']);
}
if ($ano == 0) {
	$ano = date('Y');
}

//verifica que dicho año, tenga registros
$verifica = intval(getmysql("SELECT COUNT(*) FROM an_listado_semanal WHERE date_format(semana_inicio,'%Y') = ".intval($ano).";"));
if ($verifica != 52) { //el año esta incompleto, lo crea por semanas:
	for ($contMes = 1; $contMes <= 12; $contMes++) {
		$primerdiames = mktime(0,0,0,$contMes,1,$ano);
		//$fecha_actual=$primerdiames;
		for ($i = 1; $i < date('t',$primerdiames); $i += 7) {
		
			$fecha_actual = mktime(0, 0, 0, $contMes, $i, $ano);
			
			$existe = "SELECT COUNT(*) FROM an_listado_semanal WHERE ".setmysqlfecha(date(spanDateFormat,$fecha_actual))." BETWEEN semana_inicio AND semana_fin;";
			
			if (intval(getmysql($existe)) == 0) {
				//calcula el rango de la semana
				$dia_semana = date('w',$fecha_actual); //0= DOMINGO, 1=LUNES - 6=sabado
				
				//sacar el domingo anterior:
				$domingo = mktime(0, 0, 0, date("m",$fecha_actual), date("d",$fecha_actual)-$dia_semana, date("Y",$fecha_actual));
				
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
if ($mes == 0 && isset($_SESSION['sesion_mes'])) {
	$mes = intval($_SESSION['sesion_mes']);
	unset($_SESSION['sesion_mes']);
}
if ($mes == 0 || $mes == "") {
	$mes = intval(date('m'));
}

//verifica fechas futuras:
if($ano > intval(date('Y'))) {
	$ano = intval(date('Y'));
	$mes = intval(date('m'));
}
if ($mes > intval(date('m')) && $ano == intval(date('Y'))) {
	$mes = intval(date('m'));
}
//verifica si tiene acceso completo:
if ($mes != intval(date('m')) || $ano != intval(date('Y'))) {
	$redirect="an_informe_cliente_semanal_list.php?view=1";
	validaModulo('an_cierre_ventas_ac');
}
$umes = intval(date('t',mktime(0,0,0,$mes,1,$ano)));

//extrae las semanas del mes:
$crit = excape($_GET['omitirprimerasemana']);
if ($crit == "") {
	$crit = "fin";
} else {
	$crit = "inicio";
}

$criterio = "semana_".$crit; //o semana_fin
$sql_mes = "SELECT 
	an_listado_semanal.id_listado_semanal,
	if(semana_inicio < str_to_date('".$ano."-".$mes."-1','%Y-%m-%d'),str_to_date('".$ano."-".$mes."-1','%Y-%m-%d'),semana_inicio) as si,
	if(semana_fin > str_to_date('".$ano."-".$mes."-".$umes."','%Y-%m-%d'),str_to_date('".$ano."-".$mes."-".$umes."','%Y-%m-%d'),semana_fin) as sf,
	date_format('%w',if(semana_inicio < str_to_date('".$ano."-".$mes."-1','%Y-%m-%d'),str_to_date('".$ano."-".$mes."-1','%Y-%m-%d'),semana_inicio)) as siw,
	semana_inicio,
	semana_fin,
	an_listado_semanal.semana_fin,
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
ORDER BY semana_inicio";//
$result_mes = mysql_query($sql_mes, $conex);
if (!$result_mes) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$snumero = 0;
while ($row_mes = mysql_fetch_assoc($result_mes)) {
	$snumero++;
	//extrae los totales por semana de vehiculos vendidos
	
	$sql_semana = "SELECT 
		an_unidad_fisica.id_uni_bas as id_uni_bas,
		count(an_unidad_fisica.id_uni_bas) AS cantidad
	FROM an_unidad_fisica
	WHERE fecha_pago_venta BETWEEN '".$row_mes['si']."' AND '".$row_mes['sf']."'
		AND estado_venta = 'VENDIDO'
	GROUP BY an_unidad_fisica.id_uni_bas";
	$result_semana = mysql_query($sql_semana, $conex);
	if (!$result_semana) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$semana = NULL;
	$tsemana = 0;
	while ($row_sem = mysql_fetch_assoc($result_semana)) {
		if ($row_mes['si'] == $row_mes['sf']) continue;
		//$semana[]=array('id_uni_bas' => $row_sem['id_uni_bas'],'cantidad' => $row_sem['cantidad']);
		//$semana[]=array($row_sem['id_uni_bas']=>$row_sem['cantidad']);
		$semana[$row_sem['id_uni_bas']]=$row_sem['cantidad'];
		$tunidadmes[$row_sem['id_uni_bas']]+=$row_sem['cantidad'];
		$totalmes+=$row_sem['cantidad'];
		$tsemana+=$row_sem['cantidad'];
	}
	
	$semanas[] = array(
		"snumero" => $snumero,
		"id_listado_semanal" =>$row_mes['id_listado_semanal'],
		"situacion" =>$row_mes['situacion'],
		"eventos" =>$row_mes['eventos'],
		"otros" =>$row_mes['otros'],
		"semana_inicio" =>$row_mes['semana_inicio'],
		"semana_fin" =>$row_mes['semana_fin'],
		"si" =>$row_mes['si'],
		"sf" =>$row_mes['sf'],
		"siw" =>$row_mes['siw'],
		"unidades" => $semana,
		"total" => $tsemana);
}

/*echo var_dump($semanas);
echo "<!-- otro -->";
echo var_dump($tunidadmes);
echo $totalmes;*/

function setnumeric($num){
	return ($num == "") ? 0 : $num;
}

$nombre_distribuidor = htmlentities(getmysql("select nombre_empresa from pg_empresa where id_empresa=".$idEmpresa.";"));
if ($_GET['omitirprimerasemana'] != "") {
	$ops = "&omitirprimerasemana=1";
}

//importar la vista:
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Informe de Clientes Nuevos</title>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">
    <link href="an_informe_cliente_semanal_list.css" rel="stylesheet" type="text/css" />
    
    <script type="text/javascript" src="vehiculos.inc.js"></script>
    <script type="text/javascript" language="javascript">
	function cambiar(){
		var f2=document.getElementById('freload');
		f2.submit();
	}
	function editar(args){
		var f = $('formu');
		f.action="an_informe_cliente_semanal_list.php";
		if (args!=""){
			f.action+="?"+args;
		}
		f.submit();
	}
    </script>
</head>

<body class="bodyVehiculos" <?php if($_GET['view'] == "print") { echo 'onload="print();"'; }?>>
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php if ($_GET['view'] != "print") { include("banner_vehiculos.php"); } ?></div>
	
	<table border="0" width="100%">
    <tr>
        <td class="tituloPaginaVehiculos">Informe de Clientes Nuevos<br/><span class="texto_10px">(Clientes Atendidos Mensualmente <?php echo $arrayMes[$mes].' '.$ano; ?>)</span></td>
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
            <tr class="tituloColumna" height="24">
                <td width="5%">Semanas</td>
			<?php
            foreach ($unidades as $unidad) { // llenando el patrón:
                echo '<td class="toptd">'.$unidad['modelo'].' '.$unidad['version'].'</td>';
            } ?>
                <td width="5%">Total</td>
            </tr>
            <?php
            foreach ($semanas as $semana) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
                if ($semana['si'] == $semana['sf'] && $semana['siw'] == 0) continue; ?>
				<tr align="left" class="<?php echo $clase; ?>" height="24">
                    <td class="tituloColumna" title="<?php echo $semana['semana_inicio'].' / '.$semana['semana_fin'].' ('.$semana['si'].' / '.$semana['sf'].')'; ?>">Semana <?php echo $semana['snumero']; ?></td>
                <?php foreach($unidades as $unidad) { ?>
                    <td><?php echo valTpDato(setnumeric($semana['unidades'][$unidad['id_uni_bas']]),"cero_por_vacio"); ?></td>
                <?php } ?>
                    <td class="trResaltarTotal3"><?php echo valTpDato(setnumeric($semana['total']),"cero_por_vacio"); ?></td>
                </tr>
            <?php
            } ?>
            <tr align="right" class="trResaltarTotal" height="24">
                <td class="tituloCampo">Totales:</td>
			<?php foreach($unidades as $unidad) { ?>
                <td><?php echo valTpDato(setnumeric($tunidadmes[$unidad['id_uni_bas']]),"cero_por_vacio"); ?></td>
            <?php } ?>	
                <td><?php echo valTpDato(setnumeric($totalmes),"cero_por_vacio"); ?></td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    	<td>
        <form id="formu" action="an_informe_cliente_semanal_guardar.php" onsubmit="return validar();" method="post">
            <input type="hidden" name="mes" value="<?php echo $mes; ?>" />
            <input type="hidden" name="a" value="<?php echo $ano; ?>" />
            <input type="hidden" name="omitirprimerasemana" value="<?php echo $_GET['omitirprimerasemana']; ?>" />
            <table border="0" width="100%">
            <tr>
                <td class="tituloArea" colspan="4">COMENTARIOS POR SEMANA</td>
            </tr>
            <tr class="tituloColumna">
                <td width="10%">SEMANAS</td>
                <td width="30%">SITUACI&Oacute;N DE LA COMPETENCIA<br/>(Disponibilidad - Precios)</td>
                <td width="30%">EVENTOS O PROMOCIONES EN LA ZONA<br/>(Distribuidor y/o Competencia)</td>
                <td width="30%">OTROS<br/>(Cr&eacute;ditos Automotrices - Sentimiento Cliente)</td>
            </tr>
            <?php
            foreach ($semanas as $semana) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
                if ($semana['si'] == $semana['sf'] && $semana['siw'] == 0) continue; ?>
				<tr align="left" class="<?php echo $clase; ?>" height="24">
                    <td class="tituloColumna" title="<?php echo $semana['semana_inicio'].' / '.$semana['semana_fin']; ?>" width="10%">Semana <?php echo $semana['snumero']; ?><br /></td>
                    <?php if (isset($_GET['view'])) { ?>
                        <td width="30%" style="height:70px;"><?php echo htmlentities($semana['situacion']); ?></td>
                        <td width="30%" style="height:70px;"><?php echo htmlentities($semana['eventos']); ?></td>
                        <td width="30%" style="height:70px;"><?php echo htmlentities($semana['otros']); ?></td>				
                    <?php } elseif($_GET['view'] != "print") { ?>
                        <td width="30%" style="height:70px;">
                            <input type="hidden" name="id_listado_semanal[]" value="<?php echo $semana['id_listado_semanal']; ?>" />
                            <textarea name="situacion[<?php echo $semana['id_listado_semanal']; ?>]" style="width:98%;height:70px;"><?php echo htmlentities($semana['situacion']) ?></textarea>
                        </td>
                        <td width="30%" style="height:70px;">
                            <textarea name="eventos[<?php echo $semana['id_listado_semanal']; ?>]" style="width:98%;height:70px;"><?php echo htmlentities($semana['eventos']) ?></textarea>
                        
                        </td>
                        <td width="30%" style="height:70px;">
                            <textarea name="otros[<?php echo $semana['id_listado_semanal']; ?>]" style="width:98%;height:70px;"><?php echo htmlentities($semana['otros']) ?></textarea>
                        
                        </td>					
                    <?php } ?>
                </tr>
            <?php
            } ?>
            </table>
            
		<?php if (!isset($_GET['view'])) { ?>
            <table class="noprint" width="100%">
            <tr>
                <td align="right"><hr/>
                    <button type="submit"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_save.png"/></td><td>&nbsp;</td><td>Guardar</td></tr></table></button>
                    <button type="button" onclick="editar('view=1');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_delete.png"/></td><td>&nbsp;</td><td>Cancelar</td></tr></table></button>
                </td>
            </tr>
            </table>
		<?php } ?>
        </form>
        </td>
    </tr>
	</table>
    
	<?php if ($_GET['view'] == 1): ?>
<form name="freload" id="freload" method="post" action="an_informe_cliente_semanal_list.php?view=1">
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
			<?php if ($_GET['view'] == "1") { ?>
                <button type="button" onclick="editar('');"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/pencil.png"/></td><td>&nbsp;</td><td>Editar</td></tr></table></button>
			<?php } ?>
        </td>
        <td align="center" width="100%">Reporte Generado el <?php echo date(spanDateFormat).' - '.getmysql("select date_format(now(),'%h:%i %p');") ?></td>
    </tr>
    </table>
</form>
	<?php
    endif; ?>

	<?php
	if ($_GET['view'] != "print") {
		echo '<div class="noprint">';
		include("pie_pagina.php");
		echo '</div>';
	} ?>
</div>
</body>
</html>