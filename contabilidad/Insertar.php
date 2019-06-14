<?php  session_start();
include_once('FuncionesPHP.php');
$con =  ConectarBD();
$SqlStr = "INSERT INTO conse 
(Mes
 ,Ano
 ,dia
 ,cc
 ,Consecutivo           
 ,ConseMov) values ('06','2007','30','10000002',1,0)";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "INSERT INTO enc_diario (comprobant,fecha,actualiza,Usuario_i,Hora_i,Fecha_i,Usuario_m,Hora_m,Fecha_m,Concepto,Tipo,Soporte,ModuloOrigen,NumeroRenglones,CC)
values ('1','2007-06-30','0','','5:09:39 AM','04/04/2008','','','','Asiento de Apertura','',0,'CON',0,'10000002')";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "insert into movimien
select  1,'2007-06-30',numero,codigo,descripcion,debe,haber,documento,'',numero*10,'01','01','10000002','10000002' from movimientemp2;
";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

echo MJ('Listo el proceso de insertar');
?>