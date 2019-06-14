<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();
$Mesword = MesLetras(12);
$Mes_d = substr($Mesword,0,3)."_d";
$Mes_h = substr($Mesword,0,3)."_h";
$Ano =2008;

$sql = "delete from sipre_contabilidad.cuentaverificar" ;
$rs1 = EjecutarExec($con,$sql) or die($sql); 
$sql = "delete from sipre_contabilidad.cnt0000_2" ;
$rs1 = EjecutarExec($con,$sql) or die($sql); 

$sCondicion1 = " fecha_year = 2008";
$sql = "insert into sipre_contabilidad.cuentaverificar (codigo,Descripcion,saldo_ant,debe,haber)
Select codigo,Descripcion,$Mesword,0,0 from ". "sipre_contabilidad.cnt0000 where " .$sCondicion1 ;
$rs1 = EjecutarExec($con,$sql) or die($sql); 

//******************************Actuializar Movimientos*********************************************// 
for($iMes = 1; $iMes <= 11; $iMes++){
$Mesword = MesLetras($iMes);
$Mes_d = substr($Mesword,0,3)."_d";
$Mes_h = substr($Mesword,0,3)."_h";
echo $Mesword . "<br>"; 
$MesMov = "sipre_contabilidad.movhistorico".trim($iMes);
$SqlStr = "Select codigo,sum(debe),sum(haber) from $MesMov  group by codigo order by codigo";
	$rs5 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
						if (NumeroFilas($rs5)>0){
							while($row = mysql_fetch_array($rs5)){
							$CodigoMov  = trim($row[0]);
							$DebeMov = $row[1];
							$HaberMov = $row[2];
							 
							 	/*para actualizar las cuentas */
					$SqlStr= "  update sipre_contabilidad.cuentaverificar set debe = debe + $DebeMov,
								haber = haber + $HaberMov                                
								where (length(rtrim(sipre_contabilidad.cuentaverificar.codigo)) < length(rtrim('$CodigoMov'))
								and rtrim(sipre_contabilidad.cuentaverificar.codigo) = substring('$CodigoMov',1,length(rtrim(sipre_contabilidad.cuentaverificar.codigo))))
								or sipre_contabilidad.cuentaverificar.codigo = '$CodigoMov'"; 
								$rs4 = EjecutarExec($con,$SqlStr) or die($SqlStr); 
							}
						}
 

//******************************Fin Actuializar Movimientos*********************************************// 

 $SqlStr=" INSERT sipre_contabilidad.cnt0000_2 (codigo,Descripcion,fecha_year)
     select rtrim(a.codigo),a.Descripcion,$Ano  From sipre_contabilidad.cuentaverificar a left join sipre_contabilidad.cnt0000_2 b 
     on rtrim(a.codigo) = rtrim(b.codigo) AND b.fecha_year = $Ano
     WHERE b.codigo is null";
 $exc1 = EjecutarExec($con,$SqlStr) or die($SqlStr) ;
 
 
  $SqlStr= "	update sipre_contabilidad.cnt0000_2 a,sipre_contabilidad.cuentaverificar b set a.$Mesword = b.saldo_ant,a.$Mes_d = b.debe,a.$Mes_h = b.haber 
				where a.codigo = b.codigo and fecha_year = $Ano ";
 $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);	

 /* colocar e catalogo de cuenta en cero*/  
$SqlStr= " update sipre_contabilidad.cuentaverificar
set saldo_ant = sipre_contabilidad.cuentaverificar.saldo_ant +  (sipre_contabilidad.cuentaverificar.debe) - (sipre_contabilidad.cuentaverificar.haber),
sipre_contabilidad.cuentaverificar.debe = 0,sipre_contabilidad.cuentaverificar.haber = 0";
$exc3 = EjecutarExec($con,$SqlStr) or die($SqlStr);
/* fin colocar e catalogo de cuenta en cero*/  

 

 
}

echo "proceso realizado satisfactoriamente";