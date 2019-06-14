<?php  session_start();
include_once('FuncionesPHP.php');
$con =  ConectarBD();
$SqlStr = "update parametros set fec_proceso = '$TFecha',gancia = '32501',Fechacomp_cierr = '1900-01-01',CtaIngresos ='5',CtaEgresos='6'";

$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movimien";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movimiendif";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico1";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico2";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico3";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico4";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico5";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico6";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico7";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico8";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico9";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico10";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico11";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from movhistorico12";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "delete from enc_diario";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from enc_dif";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from enc_historico";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from cnt0000";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from conse";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
$SqlStr = "delete from mesclose";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);



$SqlStr = "update cuenta set debe = 0,haber = 0, saldo_ant = 0";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

echo MJ('Listo el proceso');
?>