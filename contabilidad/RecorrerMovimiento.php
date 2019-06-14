<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();

//Comienzo de la transaccion BEGIN 
 $SqlStr="BEGIN";
 $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
 
$SqlStr=" insert into sipre_contabilidad.enc_diario 
 select * from E1000000.enc_diario ";
$exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);

$sCampos= "comprobant";
  $sCampos.= ",fecha";
  $sCampos.= ",numero";
  $sCampos.= ",codigo";
  $sCampos.= ",descripcion";
  $sCampos.= ",debe";
  $sCampos.= ",haber";
  $sCampos.= ",documento";
  $sCampos.= ",generado";
  $sCampos.= ",ordenRen";
  $sCampos.= ",DT";
  $sCampos.= ",CT";
  $sCampos.= ",cc";
  $sCampos.= ",im";

  
$SqlStr=" SELECT  $sCampos  FROM E1000000.movimien b";
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
			          while ($row = mysql_fetch_row($exc2)){
					      $comprobant=$row[0];
						  $fecha = $row[1];
						  $numero= $row[2];
						  $codigo= $row[3];
						  $descripcion= $row[4];
						  $debe= $row[5];
						  $haber= $row[6];
						  $documento= $row[7];
						  $generado= $row[8];
						  $ordenRen= $row[9];
						  $DT= $row[10];
						  $CT= $row[11];
						  $cc= $row[12];
						  $im= $row[13];
						  
												  
						  $sValores = "'$comprobant'";
						  $sValores.= ",'$fecha'";
						  $sValores.= ",'$numero'";
						  $sValores.= ",'$codigo'";
						  $sValores.= ",'$descripcion'";
						  $sValores.= ",$debe";
						  $sValores.= ",$haber";
						  $sValores.= ",'$documento'";
						  $sValores.= ",'$generado'";
						  $sValores.= ",'$ordenRen'";
						  $sValores.= ",'$DT'";
						  $sValores.= ",'$CT'";
						  $sValores.= ",'$cc'";
						  $sValores.= ",'$im'";
						  $SqlStr=" insert into sipre_contabilidad.movimien ($sCampos) values ($sValores)";
						  $exc3 = EjecutarExec($con,$SqlStr) or die($SqlStr .'  '. mysql_error());
					   }
					}        
 $SqlStr="rollback";
 $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);					
 echo "Proceso Finalizado Satisfactoriamente";
	
  
  
  
?>