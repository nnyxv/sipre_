<?php
require_once('../connections/conex.php');

require_once('../inc_sesion.php');
validaModulo('an_informe_cliente_semanal_list',editar);

$mes = excape($_POST['mes']);
$ano = excape($_POST['a']);

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
	$redirect = "an_informe_cliente_semanal_list.php?view=1";
	validaModulo('an_cierre_ventas_ac');
}

conectar();
inputmysqlutf8();
iniciotransaccion();
foreach ($_POST['id_listado_semanal'] as $v) {
	//echo $v.' => '.$_POST['situacion'][$v].' - '.$_POST['eventos'][$v].' - '.$_POST['otros'][$v].'<br />';
	$sqlupdate = "UPDATE an_listado_semanal SET
		situacion = '".excape($_POST['situacion'][$v])."',
		eventos = '".excape($_POST['eventos'][$v])."',
		otros = '".excape($_POST['otros'][$v])."',
		ultima_modificacion = now()
	WHERE id_listado_semanal = ".$v.";";
	$result = @mysql_query($sqlupdate, $conex);
	if (!$result) { rollback(); die(mysql_error()."<br>Error Nro: ".mysql_errno($conex)."<br>Line: ".__LINE__); }
}
fintransaccion();
cerrar();

if ($_POST['omitirprimerasemana'] != "") {
	$ops = "&omitirprimerasemana=1";
}

//temporal:
@session_start();
$_SESSION['sesion_mes']=$mes;
$_SESSION['sesion_ano']=$ano;
?>
<script type="text/javascript" src="vehiculos.inc.js"></script>
<script type="text/javascript" language="javascript">
utf8alert('Se modificaron los comentarios del mes con &eacute;xito.');
window.location.href="an_informe_cliente_semanal_list.php?view=1";
</script>