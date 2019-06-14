<?php session_start();
include_once('FuncionesPHP.php');
$con = ConectarBD();

//Comienzo de la transaccion BEGIN 
$conAd = ConectarBD();
$SqlStr = "Select a.codigo,a.descripcion from sipre_co_config.company a
where   a.codigo <> 'BASEPRUEBA'";
$excAd = EjecutarExec($conAd,$SqlStr) or die($SqlStr);
     while ($rowAd=ObtenerFetch($excAd)){
            $TablaEnc = $rowAd[0];
			$DesTabla = $rowAd[1];	

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

  
$SqlStr=" select * from $TablaEnc.movimien a left join sipre_contabilidad.cuenta b on a.codigo = b.codigo
where b.codigo is null order by fecha,comprobant";
$exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
//echo "           Las Cuentas Que no tienen Codigo <br>";			  	
            $bprimera = true;  
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
				    echo "<strong> Estado:  $TablaEnc  $DesTabla En diario </strong> <BR>";
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
						  echo "$comprobant , $fecha , $numero, $codigo"."<br>";	
						  $SQL = "delete from $TablaEnc.movimien where comprobant = '$comprobant' 
						  and fecha = '$fecha' and numero = $numero and cc = '$cc'";
						  $exc22 = EjecutarExec($con,$SQL) or die($SQL);
					   }
					}        

					
					
$SqlStr=" select * from $TablaEnc.movimiendif a left join sipre_contabilidad.cuenta b on a.codigo = b.codigo
where b.codigo is null order by fecha,comprobant";
$exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
			   echo "<strong>  Estado:  $TablaEnc  $DesTabla  en Posteriores </strong> <br>";
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
						  echo "$comprobant , $fecha , $numero, $codigo"."<br>";
						$SQL = "delete from $TablaEnc.movimiendif where comprobant = '$comprobant' 	
						  and fecha = '$fecha' and numero = $numero and cc = '$cc'";
						  $exc22 = EjecutarExec($con,$SQL) or die($SQL);						  
					   }
					}        

						  
$SqlStr=" select * from $TablaEnc.movimien a left join sipre_contabilidad.transacciones b on a.CT = b.codigo
where b.codigo is null order by fecha,comprobant";
$exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
//echo "           Las Cuentas Que no tienen Codigo <br>";			  	
            $bprimera = true;  
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
				    echo "<strong> Estado:  $TablaEnc  $DesTabla  En Diario Tramsaccionres </strong> <BR>";
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
						  echo "$comprobant , $fecha , $numero"."<br>";			  	
						  $SqlStr=" update $TablaEnc.movimien set ct = '00' where ";
						  $SqlStr.=" comprobant= '$comprobant' and numero=$numero and fecha = '$fecha'  and cc = '$cc'";	
						  $excAct = EjecutarExec($con,$SqlStr) or die($SqlStr);
					   }
					}
					
$SqlStr=" select * from $TablaEnc.movimiendif a left join sipre_contabilidad.transacciones b on a.CT = b.codigo
where b.codigo is null order by fecha,comprobant";
$exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
//echo "           Las Cuentas Que no tienen Codigo <br>";			  	
            $bprimera = true;  
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
				    echo "<strong> Estado:  $TablaEnc  $DesTabla  En Diferido Tramsaccionres </strong> <BR>";
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
						  echo "$comprobant , $fecha , $numero"."<br>";			  	
  						  $SqlStr=" update $TablaEnc.movimiendif set ct = '00' where ";
						  $SqlStr.=" comprobant= '$comprobant' and numero=$numero and fecha = '$fecha'  and cc = '$cc'";	
						  $excAct = EjecutarExec($con,$SqlStr) or die($SqlStr);

					   }
					}        

$SqlStr=" select * from $TablaEnc.movimien a left join sipre_contabilidad.documentos  b on a.DT = b.codigo
where b.codigo is null order by fecha,comprobant";
$exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
//echo "           Las Cuentas Que no tienen Codigo <br>";			  	
            $bprimera = true;  
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
				    echo "<strong> Estado:  $TablaEnc  $DesTabla  En Diario Documentos  </strong> <BR>";
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
						  echo "$comprobant , $fecha , $numero"."<br>";			  	
   						  $SqlStr=" update $TablaEnc.movimien set dt = '00' where ";
						  $SqlStr.=" comprobant= '$comprobant' and numero=$numero and fecha = '$fecha' and cc = '$cc'";	
						  $excAct = EjecutarExec($con,$SqlStr) or die($SqlStr);

					   }
					}        

$SqlStr=" select * from $TablaEnc.movimiendif a left join sipre_contabilidad.documentos b on a.DT = b.codigo
where b.codigo is null order by fecha,comprobant";
$exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
//echo "           Las Cuentas Que no tienen Codigo <br>";			  	
            $bprimera = true;  
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
				    echo "<strong> Estado:  $TablaEnc  $DesTabla  En Diferido Documentos </strong> <BR>";
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
						  echo "$comprobant , $fecha , $numero"."<br>";			
						  $SqlStr=" update $TablaEnc.movimiendif set dt = '00' where ";
						  $SqlStr.=" comprobant= '$comprobant' and numero=$numero and fecha = '$fecha' and cc = '$cc'";	
						  $excAct = EjecutarExec($con,$SqlStr) or die($SqlStr);						  
					   }
					}        

					
					
$SqlStr=" select * from $TablaEnc.movimien a left join sipre_contabilidad.centrocosto  b on a.im = b.codigo
where b.codigo is null order by fecha,comprobant";
$exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
//echo "           Las Cuentas Que no tienen Codigo <br>";			  	
            $bprimera = true;  
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
				    echo "<strong> Estado:  $TablaEnc  $DesTabla  En Diario imputacion  </strong> <BR>";
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
						  echo "$comprobant , $fecha , $numero"."<br>";			  	
   						  $SqlStr=" update $TablaEnc.movimien set im = '10701107' where ";
						  $SqlStr.=" comprobant= '$comprobant' and numero=$numero and fecha = '$fecha' and im = '$im'";	
						  $excAct = EjecutarExec($con,$SqlStr) or die($SqlStr);

					   }
					}        

$SqlStr=" select * from $TablaEnc.movimiendif a left join sipre_contabilidad.centrocosto b on a.im = b.codigo
where b.codigo is null order by fecha,comprobant";
$exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
//echo "           Las Cuentas Que no tienen Codigo <br>";			  	
            $bprimera = true;  
            $exc2 = EjecutarExec($con,$SqlStr) or die($SqlStr);
			   if ( NumeroFilas($exc2)>0){
				    echo "<strong> Estado:  $TablaEnc  $DesTabla  En Diferido imputacion </strong> <BR>";
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
						  echo "$comprobant , $fecha , $numero"."<br>";			
						  $SqlStr=" update $TablaEnc.movimiendif set im = '10701107' where ";
						  $SqlStr.=" comprobant= '$comprobant' and numero=$numero and fecha = '$fecha' and im = '$im'";	
						  $excAct = EjecutarExec($con,$SqlStr) or die($SqlStr);						  
					   }
					}        					
					
					
					
}

/*

					
	*/		

								
 echo "Proceso Finalizado Satisfactoriamente";
	