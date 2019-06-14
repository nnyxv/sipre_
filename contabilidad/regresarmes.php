<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();
$Mesword = MesLetras(12);
$Mes_d = substr($Mesword,0,3)."_d";
$Mes_h = substr($Mesword,0,3)."_h";
$Ano =2008;
$sql = "begin";
$rs1 = EjecutarExec($con,$sql) or die($sql); 

/*$sql = "update sipre_contabilidad.cuenta set saldo_ant= 0,debe= 0,haber= 0";
$rs1 = EjecutarExec($con,$sql) or die($sql); 


$sql = "update sipre_contabilidad.cuenta a,sipre_contabilidad.cnt0000 b set a.saldo_ant= b.$Mesword
where a.codigo = b.codigo and b.fecha_year= 2008";
$rs1 = EjecutarExec($con,$sql) or die($sql); 
echo "solo cuenta";*/
 //return;

/*Transferir  Diarios a Diferidos a */
/*$SqlStr = "insert into enc_dif
  select * from enc_diario ";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "insert into movimiendif
  select * from movimien";
  $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
*/

//$SqlStr = "insert into enc_dif select * from enc_historico where  year(fecha) = 2009";
//$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

/*for ($ii = 1;$ii <= 12;$ii++){
		echo "contador".$ii."<br>";
		$SqlStr = "insert into sipre_contabilidad.movimiendif select * from sipre_contabilidad.movhistorico". trim($ii) ." where  year(fecha) = 2009";
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
}

echo "listo movimientos historicos";
$sql = "commit";
$rs1 = EjecutarExec($con,$sql) or die($sql);
return;*/
/*$SqlStr = "delete from sipre_contabilidad.enc_historico where  year(fecha) = 2009";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

for ($ii = 2;$ii <= 12;$ii++){
		$SqlStr = "delete from sipre_contabilidad.movhistorico". trim($ii) ." where  year(fecha) = 2009";
		$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
}

$SqlStr = "delete from sipre_contabilidad.enc_diario ";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);



$SqlStr = "insert into sipre_contabilidad.enc_diario select * from sipre_contabilidad.enc_historico where  year(fecha) = 2008 and month(fecha) = 12";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

$SqlStr = "insert into sipre_contabilidad.movimien select * from sipre_contabilidad.movhistorico12 where  year(fecha) = 2008 and month(fecha) = 12";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
*/
//echo "listo movimientos historicos encabe";
//$sql = "commit";
//$rs1 = EjecutarExec($con,$sql) or die($sql);


//$SqlStr = "delete from sipre_contabilidad.enc_historico where  year(fecha) = 2008 and month(fecha) = 12";
//$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);


//$SqlStr = "delete from sipre_contabilidad.movhistorico12 where year(fecha) = 2008 and month(fecha) = 12";
//$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);



	  $SqlStr = " update sipre_contabilidad.enc_diario set actualiza=0";
      $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
	  
	  $SqlStr = " update  sipre_contabilidad.enc_dif  set actualiza=0";
      $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
   
   
$sql = "update sipre_contabilidad.parametros set fec_proceso='2008-12-01'";
$rs1 = EjecutarExec($con,$sql) or die($sql); 

$sql = "update sipre_contabilidad.cnt0000 b set $Mes_d=0,$Mes_h=0,dic_cierrd=0,dic_cierrh=0 where fecha_year = 2008";
$rs1 = EjecutarExec($con,$sql) or die($sql); 

$sql = "commit";
$rs1 = EjecutarExec($con,$sql) or die($sql); 
  
  echo "Finalizado con exito";
/*Fin Transferir Diferidos a Diarios */
?>