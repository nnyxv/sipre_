<?php
require_once("../connections/conex.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if(!(validaAcceso("an_rollout_asesor","editar"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

//generando los datos principales
$ano = excape(intval($_GET['a']));
//echo 'V:'.$cambio;
if (isset($_POST['ano'])) {
	$ano = getmysqlnum($_POST['ano']);
}
if ($ano == 0) {
	$ano = date('Y');
}
//verifica fechas futuras:
if ($ano > intval(date('Y'))) {
	$ano = intval(date('Y'));
}
$id_uni_bas = excape(intval($_GET['unidad']));
if ($id_uni_bas == 0 || $id_uni_bas == "") {
	$id_uni_bas = "";
}
if (isset($_POST['mes'])) {
	$vmes = getmysqlnum($_POST['mes']);
}
if (intval($vmes) <= 0) {
	$vmes = intval(date('m'));
}
if (isset($_GET['cambio'])) {
	$cambio = true;
	$vmes = 0;
	
	if(!(validaAcceso("an_rollout_asesor"))) {
		echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
	}
}

conectar();
$sqlunidad = sprintf("SELECT 
	unidad_emp.id_unidad_basica,
	CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
FROM vw_iv_modelos vw_iv_modelo
	INNER JOIN sa_unidad_empresa unidad_emp ON (vw_iv_modelo.id_uni_bas = unidad_emp.id_unidad_basica)
WHERE unidad_emp.id_empresa = %s
	AND vw_iv_modelo.catalogo = 1
ORDER BY CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) ASC;",
	valTpDato($idEmpresa, "int"));
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. Vehículos - Editar Roll-Out | Objetivos por Asesores</title>
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragVehiculos.css">

	<script type="text/javascript" src="vehiculos.inc.js"></script>
    <script type="text/javascript" src="anajax.js"></script>

	<script type="text/javascript" language="javascript">
	//función que carga los datos:
	function recarga(){
		//creando el objeto ajax:
		var a= new Ajax();
		var ano = document.getElementById('ano');
		var id_uni_bas = document.getElementById('id_uni_bas');

		//a.loading=carga;
		//a.error=er;
		a.load= function(texto){
			var _capa= document.getElementById("capadatos");
			_capa.innerHTML=texto;
			percent();
			ienfoca();
			recargatotal();
			recargatotalasesor();
		};
		a.sendget("an_rollout_asesor_ajax.php","ajax_a="+ano.value+'&ajax_unidad='+id_uni_bas.value+'&ajax_mes=<?php echo numformat($vmes,0); ?>',false);
	}
	
	function recargatotal(){
		//creando el objeto ajax:
		var a= new Ajax();
		var ano = document.getElementById('ano');
		//var id_uni_bas = document.getElementById('id_uni_bas');

		//a.loading=carga;
		//a.error=er;
		a.load= function(texto){
			var _capa= document.getElementById("capadatostotal");
			_capa.innerHTML=texto;
			percent();
		};
		a.sendget("an_rollout_unidad_ajax.php","ajax_a="+ano.value,false);
	}
	
	function recargatotalasesor(){
		//creando el objeto ajax:
		var a= new Ajax();
		var ano = document.getElementById('ano');
		//var id_uni_bas = document.getElementById('id_uni_bas');

		//a.loading=carga;
		//a.error=er;
		a.load= function(texto){
			var _capa= document.getElementById("capadatostotalasesor");
			_capa.innerHTML=texto;
			percent();
		};
		a.sendget("an_rollout_vendedor_ajax.php","ajax_a="+ano.value,false);
	}
	
	function validar(){
		return true;
	}
	
	function enviar(form){
		var f = $(form);
		f.submit();
	}

	function percent(){
		var f = $('rollout');
		/*for(var e in f.elements['objetivo']){
			alert(e.value);
		}*/
		var asesores = f.elements['asesor[]'];
		if (asesores==null) return;
		/*if(!isArray(asesores)){
			//lo convierte en un array de 1 sola dimensión
			var temp= f.elements['asesor[]'];
			asesores = new Array();
			asesores[0]=temp;
		}*/
		asesores=getForceArray(asesores);
		var sumaa=new Array();
		var total=0;
		/*for(var asesor=0;asesor<asesores.length;asesor++){
			sumaa[asesor]=0;
		}*/
		for(var mes = 1; mes <=12; mes++){
			var suma=0;
			for(var asesor=0;asesor<asesores.length;asesor++){
				var id_empleado = asesores[asesor].value;
				//recorrer por meses
				var obj = $('objetivo['+id_empleado+']['+mes+']');
				var v=parsenum(obj.value);
				suma+=v;
				if(sumaa[asesor]==undefined){
					sumaa[asesor]=0;
				}
				sumaa[asesor]+=v;
				total+=v;
			}
			//alert('mes '+mes+': '+suma);
			$('mes['+mes+']').innerHTML=suma;
		}
		for(var asesor=0;asesor<asesores.length;asesor++){
			$('easesor['+asesores[asesor].value+']').innerHTML=sumaa[asesor];
			//alert('easesor['+asesores[asesor].value+']');
		}
		$('total').innerHTML=total;		
	}
	
	function ienfoca(){
		var f = $('rollout');
		var asesores = f.elements['asesor[]'];
		if (asesores==null) return;
		/*if(!isArray(asesores)){
			//lo convierte en un array de 1 sola dimensión
			var temp= f.elements['asesor[]'];
			asesores = new Array();
			asesores[0]=temp;
		}*/
		
		asesores=getForceArray(asesores);
		var mes = <?php echo intval($vmes); ?>;
		if (mes == 0)
			mes = 1;
		
		var obj=$('objetivo['+asesores[0].value+']['+(mes)+']');
		if(obj!=null){
			obj.focus();
			obj.focus();
		}
	}
    </script>
</head>

<body <?php if($_GET['view'] == "print"){ echo "onload='recarga(); print();'"; } else { echo "onload='recarga();'"; }?>>
<div id="divGeneralPorcentaje">
	<div class="noprint"><?php if ($_GET['view'] != "print") { include("banner_vehiculos.php"); } ?></div>
    
    <div id="divInfo" class="print">
		<table border="0" width="100%">
        <tr class="solo_print">
        	<td align="left" id="tdEncabezadoImprimir"></td>
        </tr>
        <tr>
        	<td class="tituloPaginaVehiculos">Editar Objetivos ROLL-OUT por Asesor</td>
        </tr>
        <tr>
        	<td>
            <fieldset>
            	<iframe id="rooloutsave" name="rooloutsave" style="display:none;"></iframe>
            <form name="rollout" id="rollout" target="rooloutsave" action="an_rollout_asesor_guardar.php" method="post" onsubmit="return validar();">
            	<table>
                <tr>
                	<td align="right" class="tituloCampo" width="120"><label for="id_uni_bas">Unidad Básica:</label></td>
                    <td>
                        <select id="id_uni_bas" name="id_uni_bas" onchange="recarga();">
                            <option value="">[ Seleccione ]</option>
                            <?php generar_select($id_uni_bas, $sqlunidad); ?>
                        </select>
					</td>
				<?php if (!$cambio) { ?>
                    <td align="right" class="tituloCampo" width="100">Fecha:</td>
                    <td width="120">
						<?php echo $arrayMes[$vmes]; ?>-<?php echo $ano; ?>
						<input type="hidden" name="vmes" id="vmes" value="<?php echo $vmes;?>" />
                        <input type="hidden" name="ano" id="ano" value="<?php echo $ano;?>" />
					</td>
                    <td><a href="an_rollout_asesor.php?cambio" title="Permite editar los objetivos Roll-out completos independientemente del mes y a&ntilde;o actual. Necesita Permisos para esta operaci&oacute;n">Modificar Roll-Out Completo</a></td>
				<?php } else { ?>
                	<td align="right" class="tituloCampo" width="100">A&ntilde;o:</td>
                	<td>
                        <select id="ano" name="ano" onchange="recarga();"> 
                        <?php
						$anoactual = $ano;
						for ($i = $anoactual-10; $i <= $anoactual+10; $i++) {
							$selected = ($i == $anoactual) ? "selected=\"selected\"" : "";
							echo "<option ".$selected." value=\"".$i."\">".$i."</option>";
						} ?>
                        </select>
                	</td>
				<?php } ?>
                </tr>
                </table>
                <br>
                <div id="capadatos"></div>
            </form>
            </fieldset>
            </td>
        </tr>
        <tr>
        	<td>
            	<table width="100%">
                <tr>
                	<td class="tituloArea">Reporte Autom&aacute;tico: Objetivos Roll-Out por Unidades</td>
				</tr>
                <tr>
                	<td id="capadatostotal"></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td>
            	<table width="100%">
                <tr>
                	<td class="tituloArea">Reporte Autom&aacute;tico: Objetivos Roll-Out por Asesor</td>
				</tr>
                <tr>
                	<td id="capadatostotalasesor"></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
        	<td>
            	<table cellpadding="0" cellspacing="0" class="divMsjInfo2" width="100%">
				<tr>
					<td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
					<td align="center">
                    	Al <em>GUARDAR</em> le Permite ver los objetivos Roll-Out por Unidades y los objetivos Roll-Out por Asesores
                    </td>
				</tr>
				</table>
            </td>
        </tr>
        </table>
    </div>
    
    <div class="noprint"><?php if ($_GET['view'] != "print") { include("pie_pagina.php"); } ?></div>
</div>
</body>
</html>