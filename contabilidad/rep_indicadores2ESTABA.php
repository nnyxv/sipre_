<?php
session_start();
require('fpdf.php');
include("FuncionesPHP.php");
$sUsuario = $_SESSION["UsuarioSistema"];
$REPORTE = $_REQUEST['ExceloPdf'];
$cDesde1 = $_REQUEST['cDesde1']; //1er
$cDesde2 = $_REQUEST['cDesde2']; //2do
$cDesde3 = $_REQUEST['cDesde3']; //3er
$cDesde4 = $_REQUEST['cDesde4']; //4to
$cDesde5 = $_REQUEST['cDesde5']; //5to
$cDesde6 = $_REQUEST['cDesde6']; //6to
$cDesde7 = $_REQUEST['cDesde7']; // codigo de formato
$cDesde8 = $_REQUEST['cDesde8']; // asiento de cierre
$icierre = 0;
if($cDesde6 == "NO"){
$icierre = 1;
}



$mes1 = substr($cDesde1,0,2);
$ano1 = substr($cDesde1,2,4);
$mes2 = substr($cDesde2,0,2);
$ano2 = substr($cDesde2,2,4);
$mes3 = substr($cDesde3,0,2);
$ano3 = substr($cDesde3,2,4);
$mes4 = substr($cDesde4,0,2);
$ano4 = substr($cDesde4,2,4);
$mes5 = substr($cDesde5,0,2);
$ano5 = substr($cDesde5,2,4);
$mes6 = substr($cDesde6,0,2);
$ano6 = substr($cDesde6,2,4);




if ($REPORTE == 'P') {
    $a = 1;
}

if ($REPORTE == 'E') {
    $a = 0;
    header("Content-type: application/x-msexcel");
    header("Content-Disposition: attachment; filename=ArchivoExcel.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>INDICADORES</title>
        <style>			
            @media print{
                .botonImprimir{
                    display: none;
                }
            }
            body{

                font-family:"Times New Roman", Times;
                font-size:10pt;

            }
            .texto{
                font-weight: bolder;
            }

            .tabla1 td{
                border-color:#000000;
                border-width: 1px;
                border-style: solid;
                padding:0px;
                border-spacing: 0px
            }
            .SaltoDePagina
            {
                PAGE-BREAK-AFTER: always
            }

        </style>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    </head>
    <body style="width: 820px; margin-bottom: 10px; margin-top:0px" >
        <table border="0" width="300%" cellpadding="0px" >  
            <?php if ($a != 0) { ?>
                <tr>
                    <td align="right" colspan="8">
                        <button class="botonImprimir" type="button" id="btnImprimir" name="btnImprimir" style="cursor:default" onclick="window.print();">
                            <table align="center" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="10">&nbsp;</td>
                                    <td width="17"><img src="../../img/icons/printer.png" alt="print"/></td>
                                    <td width="10">&nbsp;</td>
                                    <td width="36">Imprimir</td>
                                </tr>
                            </table>
                        </button>
                    </td>
                </tr>
            <?php } ?>
        </table>
    
        <table border="1" style='width:300%'>
                   <?php
				   //$condicionBoG = " and (trim(codigo)='4' or trim(codigo)='5')";
				   
				   $condicionBoG = " and codigo <> '9.1.01.01' and codigo <> '9.1.01.01'";
				   $condicionBoG.= " and codigo <> '9.2.01.01' and codigo <> '9.2.01.01'";
				   $condicionBoG.= " and codigo <> '5.1.03.01' and codigo >=4 and length(ltrim(rtrim(codigo))) <=9 or codigo='4.1.01.01.003'";
				   $condicionBoG.= " or codigo='4.1.02.01.002' or codigo='4.1.02.01.003' or codigo='4.1.02.02.002'";
				   $condicionBoG.= " or codigo='4.1.02.03.001' or codigo='4.1.02.03.002'";
				   $condicionBoG.= " or codigo='5.1.02.02.001' or codigo='5.1.02.02.002'";
				   $condicionBoG.= " or codigo='8.2.01.01.009' or codigo='8.2.01.01.044'";


                   $bprimera = "SI";
                   $con = ConectarBD();
		
					$sTabla='parametros';
					$sCampos='fec_proceso';
					$sCampos.=',mescierre';
					$SqlStr='Select '.$sCampos.' from '.$sTabla;
					$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
					if (NumeroFilas($exc)>0){
						 $dfec_proceso=trim(ObtenerResultado($exc,1));
						 $mescierre   = trim(ObtenerResultado($exc,2)) ; 
					}
					$Fecha_ano1=substr($cDesde1,2,4);
					$Fecha_mes1=substr($cDesde1,0,2);
					$Fecha_ano2=substr($cDesde2,2,4);
					$Fecha_mes2=substr($cDesde2,0,2);
					
					$Fecha_ano3=substr($cDesde3,2,4);
					$Fecha_mes3=substr($cDesde3,0,2);
					$Fecha_ano4=substr($cDesde4,2,4);
					$Fecha_mes4=substr($cDesde4,0,2);
						
					$Fecha_ano5=substr($cDesde5,2,4);
					$Fecha_mes5=substr($cDesde5,0,2);
					$Fecha_ano6=substr($cDesde6,2,4);
					$Fecha_mes6=substr($cDesde6,0,2);
					
				if($cDesde1 !=""){			
				   $cPar1	= "$Fecha_ano1-$Fecha_mes1-01";
				   $cPar2	= "$Fecha_ano1-$Fecha_mes1-".ultimo_dia($Fecha_mes1,$Fecha_ano1);
				     CargarSaldos($cPar1,$cPar2,"","",$icierre);
					if ($icierre==1){
						$SqlStr=" select a.codigo,sum(a.saldo_ant + a.debe-a.haber) as monto,max(a.descripcion) as descripcion";
					}else{ 
						$SqlStr=" select a.codigo,sum(a.saldo_ant+(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr)) as monto ,max(a.descripcion) as descripcion";
					} 		
						$SqlStr.=" from cuentageneral a where usuario = '$sUsuario' $condicionBoG group by a.codigo";
						
						$exc1er = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$array1er = array();
						while($row1er= mysql_fetch_row($exc1er)){
						   $insertarEste1er = array($row1er[0], $row1er[1], $row1er[2]);
							array_push($array1er, $insertarEste1er);
						}
				}

			/*	
				if($cDesde2 !=""){								
				   $cPar1	= "$Fecha_ano2-$Fecha_mes2-01";
				   $cPar2	= "$Fecha_ano2-$Fecha_mes2-".ultimo_dia($Fecha_mes2,$Fecha_ano2);
				   
				   	   CargarSaldos($cPar1,$cPar2,"","",$icierre);
				   	if ($icierre==1){
						$SqlStr=" select a.codigo,sum(a.saldo_ant + a.debe-a.haber as monto),max(a.descripcion) as descripcion";
					}else{ 
						$SqlStr=" select a.codigo,sum(a.saldo_ant+(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr)) as monto,max(a.descripcion) as descripcion ";
					} 		
						$SqlStr.=" from cuentageneral a where usuario = '$sUsuario' $condicionBoG group by a.codigo";
						$exc2do = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$array2do = array();
						while($row2do= mysql_fetch_row($exc2do)){
						   $insertarEste2do = array($row2do[0],$row2do[1],$row2do[2]);
							array_push($array2do, $insertarEste2do);
						}
				}		
		



		          if($cDesde3 !=""){				
				   $cPar1	= "$Fecha_ano3-$Fecha_mes3-01";
				   $cPar2	= "$Fecha_ano3-$Fecha_mes3-".ultimo_dia($Fecha_mes3,$Fecha_ano3);
				   
				   	   CargarSaldos($cPar1,$cPar2,"","",$icierre);
				   	if ($icierre==1){
						$SqlStr=" select a.codigo,sum(a.saldo_ant + a.debe-a.haber as monto) ,max(a.descripcion) as descripcion";
					}else{ 
						$SqlStr=" select a.codigo,sum(a.saldo_ant+(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr)) as monto ,max(a.descripcion) as descripcion";
					} 		
						$SqlStr.=" from cuentageneral a where usuario = '$sUsuario' $condicionBoG group by a.codigo";
						$exc3ra = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$array3ra = array();
						while($row3ra= mysql_fetch_row($exc3ra)){
						   $insertarEste3ra = array($row3ra[0],$row3ra[1],$row3ra[2]);
							array_push($array3ra, $insertarEste3ra);
						}
					}
				 if($cDesde4 !=""){		
				   $cPar1	= "$Fecha_ano4-$Fecha_mes4-01";
				   $cPar2	= "$Fecha_ano4-$Fecha_mes4-".ultimo_dia($Fecha_mes4,$Fecha_ano4);
				   
				   	   CargarSaldos($cPar1,$cPar2,"","",$icierre);
				   	if ($icierre==1){
						$SqlStr=" select a.codigo,sum(a.saldo_ant + a.debe-a.haber as monto) ,max(a.descripcion) as descripcion";
					}else{ 
						$SqlStr=" select a.codigo,sum(a.saldo_ant+(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr)) as monto ,max(a.descripcion) as descripcion ";
					} 		
						$SqlStr.=" from cuentageneral a where usuario = '$sUsuario' $condicionBoG group by a.codigo";
						$exc4ra = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$array4ra = array();
						while($row4ra= mysql_fetch_row($exc4ra)){
						   $insertarEste4ra = array($row4ra[0],$row4ra[1],$row4ra[2]);
							array_push($array4ra, $insertarEste4ra);
						}
				 }  
				 
				if($cDesde5 !=""){			
				   $cPar1	= "$Fecha_ano5-$Fecha_mes5-01";
				   $cPar2	= "$Fecha_ano5-$Fecha_mes5-".ultimo_dia($Fecha_mes5,$Fecha_ano5);
				   
				   	   CargarSaldos($cPar1,$cPar2,"","",$icierre);
				   	if ($icierre==1){
						$SqlStr=" select a.codigo,sum(a.saldo_ant + a.debe-a.haber as monto) ,max(a.descripcion) as descripcion";
					}else{ 
						$SqlStr=" select a.codigo,sum(a.saldo_ant+(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr)) as monto ,max(a.descripcion) as descripcion";
					} 		
						$SqlStr.=" from cuentageneral a where usuario = '$sUsuario' $condicionBoG group by a.codigo";
						$exc5ra = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$array5ra = array();
						while($row5ra= mysql_fetch_row($exc5ra)){
						   $insertarEste5ra = array($row5ra[0],$row5ra[1],$row5ra[2]);
							array_push($array5ra, $insertarEste5ra);
						}	
				}
				
				if($cDesde6 !=""){		
				  $cPar1	= "$Fecha_ano6-$Fecha_mes6-01";
				   $cPar2	= "$Fecha_ano6-$Fecha_mes6-".ultimo_dia($Fecha_mes6,$Fecha_ano6);
				   
				   	   CargarSaldos($cPar1,$cPar2,"","",$icierre);
				   	if ($icierre==1){
						$SqlStr=" select a.codigo,sum(a.saldo_ant + a.debe-a.haber as monto) ,max(a.descripcion) as descripcion";
					}else{ 
						$SqlStr=" select a.codigo,sum(a.saldo_ant+(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr)) as monto ,max(a.descripcion) as descripcion";
					} 		
						$SqlStr.=" from cuentageneral a where usuario = '$sUsuario' $condicionBoG group by a.codigo";
						$exc6ra = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$array6ra = array();
						while($row6ra= mysql_fetch_row($exc6ra)){
						   $insertarEste6ra = array($row6ra[0],$row6ra[1],$row6ra[2]);
							array_push($array6ra, $insertarEste6ra);
						}	
				} 
				*/
			$SqlStr=" delete from cuentageneral where usuario = '$sUsuario'";
			$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
           
		   
		        $arrayAll = array();
				foreach ($array1er as $arr){
				     $codigoBuscar=trim($arr[0]);
					        $bConsiguio = false;
							foreach ($arrayAll as $arrAll){
								if(trim($codigoBuscar)==trim($arrAll[0])){
								   $bConsiguio = true;
								}
							}
					if($bConsiguio == false){
					    $insertarEncontrado = array($arr[0]);
						array_push($arrayAll, $insertarEncontrado);
                    }					
				}
		
				foreach ($array2do as $arr){
				     $codigoBuscar=trim($arr[0]);
					        $bConsiguio = false;
							foreach ($arrayAll as $arrAll){
								if(trim($codigoBuscar)==trim($arrAll[0])){
								   $bConsiguio = true;
								}
							}
					if($bConsiguio == false){
					    $insertarEncontrado = array($arr[0]);
						array_push($arrayAll, $insertarEncontrado);
                    }					
				}
		   
				foreach ($array3ra as $arr){
				     $codigoBuscar=trim($arr[0]);
					        $bConsiguio = false;
							foreach ($arrayAll as $arrAll){
								if(trim($codigoBuscar)==trim($arrAll[0])){
								   $bConsiguio = true;
								}
							}
					if($bConsiguio == false){
					    $insertarEncontrado = array($arr[0]);
						array_push($arrayAll, $insertarEncontrado);
                    }					
				}


				foreach ($array5ra as $arr){
				     $codigoBuscar=trim($arr[0]);
					        $bConsiguio = false;
							foreach ($arrayAll as $arrAll){
								if(trim($codigoBuscar)==trim($arrAll[0])){
								   $bConsiguio = true;
								}
							}
					if($bConsiguio == false){
					    $insertarEncontrado = array($arr[0]);
						array_push($arrayAll, $insertarEncontrado);
                    }					
				}
				
				foreach ($array4ra as $arr){
				     $codigoBuscar=trim($arr[0]);
					        $bConsiguio = false;
							foreach ($arrayAll as $arrAll){
								if(trim($codigoBuscar)==trim($arrAll[0])){
								   $bConsiguio = true;
								}
							}
					if($bConsiguio == false){
					    $insertarEncontrado = array($arr[0]);
						array_push($arrayAll, $insertarEncontrado);
                    }					
				}
				
				foreach ($array6ra as $arr){
				     $codigoBuscar=trim($arr[0]);
					        $bConsiguio = false;
							foreach ($arrayAll as $arrAll){
								if(trim($codigoBuscar)==trim($arrAll[0])){
								   $bConsiguio = true;
								}
							}
					if($bConsiguio == false){
					    $insertarEncontrado = array($arr[0]);
						array_push($arrayAll, $insertarEncontrado);
                    }					
				}
				
				
			
				
 			//$SqlStr="select titulo,cod_ins,ubicacion from balance_a where formato = '$cDesde7' order by orden";
			//$exc = EjecutarExec($con,$SqlStr) or die(mysql_error()); 
			$SqlStr=" select a.descripcion,a.codigo,'DETA' as ubicacion from cuenta a where codigo <> '' $condicionBoG group by a.codigo";
		  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		
          if (NumeroFilas($exc)>0){
     		$iFila = -1;
			$menorpos = 0;
			
			$rowArray = 0;
			$iFila = -1;
            while ($row = ObtenerFetch($exc)) {
		 	    $iFila++;
     		    $titulocta = trim(ObtenerResultado($exc,1,$iFila)) ;
				$codins = trim(ObtenerResultado($exc,2,$iFila)) ;
				$ubicacion = trim(ObtenerResultado($exc,3,$iFila)) ;
					$arrayTotal[$rowArray][0] = $codins;
					$arrayTotal[$rowArray][1] = $titulocta;
					$arrayTotal[$rowArray][2] = $ubicacion;
				$rowArray++; 
			}
			$arrayTemp =$arrayTotal ;
			//$TotalAi = 10;  
			$TotalAi = count($arrayTotal);  
			$iFila = -1;
			for($Ai = 0;$Ai <= $TotalAi;$Ai++){
            	$iFila++;
				$codins = $arrayTotal[$Ai][0];
				$titulocta = $arrayTotal[$Ai][1];
					if(trim($titulocta) == "INGRESOS"){
							$titulocta = "VENTAS";
					}
				
				$ubicacion = $arrayTotal[$Ai][2] ;
				$iNegrita="";
				$fNegrita="";
				if(strlen(trim($codins))==1 or strlen(trim($codins))==3 or strlen(trim($codins))==6){
				    $ubicacion = "TITU"; 
				}
				   $valor1 = 0;
				   $valor2 = 0;
				   $valor3 = 0;
				   $valor4 = 0;
				   $valor5 = 0;
				   $valor6 = 0;
				   $dif1 = 0;
				   $porcentaje1 = 0;
				   $dif2 = 0;
				   $porcentaje2 = 0;
				   $dif3 = 0;
				   $porcentaje3 = 0;
				   
				  if($codins != ""){
				   $valor1 = operacionAritmetica($codins,$cDesde1,$dfec_proceso,$mescierre,$icierre,1,$array1er,"N");
				   $valor2 = operacionAritmetica($codins,$cDesde2,$dfec_proceso,$mescierre,$icierre,1,$array2do,"N");
				   $valor3 = operacionAritmetica($codins,$cDesde3,$dfec_proceso,$mescierre,$icierre,1,$array3ra,"N");
				   $valor4 = operacionAritmetica($codins,$cDesde4,$dfec_proceso,$mescierre,$icierre,1,$array4ra,"N");
				   $valor5 = operacionAritmetica($codins,$cDesde5,$dfec_proceso,$mescierre,$icierre,2,$array5ra,"N");
				   $valor6 = operacionAritmetica($codins,$cDesde6,$dfec_proceso,$mescierre,$icierre,2,$array6ra,"N");
				   
				   $arrayTotal[$Ai][3] = $valor1;
				   $arrayTotal[$Ai][4] = $valor2;
				   $arrayTotal[$Ai][5] = $valor3;
				   $arrayTotal[$Ai][6] = $valor4;
				   $arrayTotal[$Ai][7] = $valor5;
				   $arrayTotal[$Ai][8] = $valor6;
				   
				   $arrayTemp[$Ai][3] = $valor1;
				   $arrayTemp[$Ai][4] = $valor2;
				   $arrayTemp[$Ai][5] = $valor3;
				   $arrayTemp[$Ai][6] = $valor4;
				   $arrayTemp[$Ai][7] = $valor5;
				   $arrayTemp[$Ai][8] = $valor6;	
					
				   /*$dif1 =  bcsub($valor2,$valor1);
			       $porcentaje1 = abs($dif1*100/$valor1);
				   $dif2 =  bcsub($valor4,$valor3);;
				   $porcentaje2 = abs($dif2*100/$valor3);
				   $dif3 =  bcsub($valor6,$valor5);
			       $porcentaje3 = abs($dif3*100/$valor5);*/
				   
				   $dif1 =  bcsub($valor1,$valor2);
			       $porcentaje1 = abs($dif1/$valor1)*100;
				   $dif2 =  bcsub($valor3,$valor4);
				   $porcentaje2 = abs($dif2/$valor3)*100;
				   $dif3 =  bcsub($valor5,$valor6);
			       $porcentaje3 = abs($dif3/$valor5)*100;
				   
				   $dif1 = $dif1/1000; 
				   $valor1 = $valor1/1000;
				   $valor2 = $valor2/1000;
				   $dif2 = $dif2/1000; 
				   $valor3 = $valor3/1000;
				   $valor4 = $valor4/1000;
				   $dif3 =  $dif3/1000;
			       $valor5 = $valor5/1000;
				   $valor6 = $valor6/1000;
				   
				   
				   $ventas1 = 0;
				   $ventas2 = 0;
				   $ventas3 = 0;
				   $ventas4 = 0;
				   $ventas5 = 0;
				   $ventas6 = 0;
				 

				 if(strlen(trim($codins)) == 6){
				      $valorctamayor1= $valor1;
					  $valorctamayor2= $valor2;
					  $valorctamayor3= $valor3;
					  $valorctamayor4= $valor4;
					  $valorctamayor5= $valor5;
					  $valorctamayor6= $valor6;
					  $codctamayor  = $codins;
				  }
				   
				   if($codins == '4.1.01'){
				      $valorVENTAS1= $valor1;
				      $valorVENTAS2= $valor2;
					  $valorVENTAS3= $valor3;
					  $valorVENTAS4= $valor4;
					  $valorVENTAS5= $valor5;
					  $valorVENTAS6= $valor6;
				   }
				   if($codins == '4.1.02'){
				      $valorPOSTVENTAS1 = $valor1;
					  $valorPOSTVENTAS2 = $valor2;
					  $valorPOSTVENTAS3 = $valor3;
					  $valorPOSTVENTAS4 = $valor4;
					  $valorPOSTVENTAS5 = $valor5;
					  $valorPOSTVENTAS6 = $valor6;
				   }
				   
				   
				/*   if(strlen(trim($codins)) > 6 && substr($codins,0,6) == trim($codctamayor)){
				         $ventas1 =  abs($valor1*100/$valorctamayor1);     
						 $ventas2 =  abs($valor2*100/$valorctamayor2);     
						 $ventas3 =  abs($valor3*100/$valorctamayor3);     
						 $ventas4 =  abs($valor4*100/$valorctamayor4);      
						 $ventas5 =  abs($valor5*100/$valorctamayor5);      
						 $ventas6 =  abs($valor6*100/$valorctamayor6);      
				   }
				  */
                   if($Ai!=0){
				   $codBuscar="";
				     if($codins == '5.1.01.01'){ // VENTAS VEHICULOS NUEVOS 
					      $codBuscar='4.1.01.01';
					 }elseif($codins == '5.1.01.02'){ // VENTAS VEHICULOSUSADOS
					      $codBuscar='4.1.01.02';
				  	 }elseif($codins == '5.1.02.01'){ // POST VENTAS DE REPUESTOS
					      $codBuscar='4.1.02.01';	  
					 }elseif($codins == '5.1.02.02'){ // POST VENTAS DE REPUESTOS MECANICA
					      $codBuscar='4.1.02.02';	  	  
					 }elseif($codins == '5.1.02.03'){ // POST VENTAS DE REPUESTOS CARRORECERIA
					      $codBuscar='4.1.02.05';	  	  	  
					 }
				   
					   for($AiTemp = $Ai;$AiTemp >= 0;$AiTemp--){
									if($arrayTotal[$Ai][0] < '5'){
										  if ((strlen($arrayTemp[$AiTemp][0]) < strlen($arrayTotal[$Ai][0])) && substr($arrayTotal[$Ai][0],0,strlen($arrayTemp[$AiTemp][0])) == $arrayTemp[$AiTemp][0]){
											$valorctamayor1 = $arrayTemp[$AiTemp][3];
											$valorctamayor2 = $arrayTemp[$AiTemp][4];
											$valorctamayor3 = $arrayTemp[$AiTemp][5];
											$valorctamayor4 = $arrayTemp[$AiTemp][6];
											$valorctamayor5 = $arrayTemp[$AiTemp][7];
											$valorctamayor6 = $arrayTemp[$AiTemp][8];	
											$ventas1 =  abs($valor1*100/$valorctamayor1);     
											$ventas2 =  abs($valor2*100/$valorctamayor2);     
											$ventas3 =  abs($valor3*100/$valorctamayor3);     
											$ventas4 =  abs($valor4*100/$valorctamayor4);      
											$ventas5 =  abs($valor5*100/$valorctamayor5);      
											$ventas6 =  abs($valor6*100/$valorctamayor6);      
											break;
										 }// if ((strlen($arrayTemp[$AiTemp][0]) < strlen($arrayTotal[$Ai][0])) && substr($arrayTotal[$Ai][0],0,strlen($arrayTemp[$AiTemp][0])) == $arrayTemp[$AiTemp][0]){
										}else{//if($arrayTotal[$Ai][0] < '5'){
											if($codBuscar ==trim($arrayTemp[$AiTemp][0])){
												$valorctamayor1 = $arrayTemp[$AiTemp][3];
												$valorctamayor2 = $arrayTemp[$AiTemp][4];
												$valorctamayor3 = $arrayTemp[$AiTemp][5];
												$valorctamayor4 = $arrayTemp[$AiTemp][6];
												$valorctamayor5 = $arrayTemp[$AiTemp][7];
												$valorctamayor6 = $arrayTemp[$AiTemp][8];	
												$ventas1 =  abs($valor1*100/$valorctamayor1);     
												$ventas2 =  abs($valor2*100/$valorctamayor2);     
												$ventas3 =  abs($valor3*100/$valorctamayor3);     
												$ventas4 =  abs($valor4*100/$valorctamayor4);      
												$ventas5 =  abs($valor5*100/$valorctamayor5);      
												$ventas6 =  abs($valor6*100/$valorctamayor6);      
												break;
											}	//if($codBuscar ==trim($arrayTemp[$AiTemp][0])){
										 }//if($arrayTotal[$Ai][0] < '5'){
					   }
   				   }
				   
				 /*   $coduti=" 4.1.01 + 5.1.01 ";
				   $utilidad_1_1 = operacionAritmetica($coduti,$cDesde1,$dfec_proceso,$mescierre,$icierre,1,$array1er);
				   $utilidad_2_1 = operacionAritmetica($coduti,$cDesde2,$dfec_proceso,$mescierre,$icierre,1,$array2do);
				   $utilidad_3_1 = operacionAritmetica($coduti,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
				   $utilidad_4_1 = operacionAritmetica($coduti,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);
				   $utilidad_5_1 = operacionAritmetica($coduti,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
				   $utilidad_6_1 = operacionAritmetica($coduti,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);

				   $utilidad_dif1 = bcsub($utilidad_2_1,$utilidad_1_1);
			       $utilidad_porcentaje1 = $utilidad_dif1*100/$utilidad_1_1;

				   $utilidad_dif2 =  bcsub($utilidad_4_1,$utilidad_3_1);
				   $utilidad_porcentaje2 = $utilidad_dif2*100/$utilidad_3_1;
				   
				   $utilidad_dif3 =  bcsub($utilidad_6_1,$utilidad_5_1);
			       $utilidad_porcentaje3 = $utilidad_dif3*100/$utilidad_5_1;

				   
				   
				   $coduti=" 4.1.02 + 5.1.02 ";
				   $utilidad_1_2 = operacionAritmetica($coduti,$cDesde1,$dfec_proceso,$mescierre,$icierre,1,$array1er);
				   $utilidad_2_2 = operacionAritmetica($coduti,$cDesde2,$dfec_proceso,$mescierre,$icierre,1,$array2do);
				   $utilidad_3_2 = operacionAritmetica($coduti,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
				   $utilidad_4_2 = operacionAritmetica($coduti,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);
				   $utilidad_5_2 = operacionAritmetica($coduti,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
				   $utilidad_6_2 = operacionAritmetica($coduti,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);

				   $utilidad_dif12 = bcsub($utilidad_2_2,$utilidad_1_2);
			       $utilidad_porcentaje12 = $utilidad_dif12*100/$utilidad_1_2;

				   $utilidad_dif22 =  bcsub($utilidad_4_2,$utilidad_3_2);
				   $utilidad_porcentaje22 = $utilidad_dif22*100/$utilidad_3_2;
				   
				   $utilidad_dif32 =  bcsub($utilidad_6_2,$utilidad_5_2);
			       $utilidad_porcentaje32 = $utilidad_dif32*100/$utilidad_5_2; */
				   
				   
				/*    $coduti="";
				   $utilidad_1_3 = operacionAritmetica($coduti,$cDesde1,$dfec_proceso,$mescierre,$icierre,1,$array1er);
				   $utilidad_2_3 = operacionAritmetica($coduti,$cDesde2,$dfec_proceso,$mescierre,$icierre,1,$array2do);
				   $utilidad_3_3 = operacionAritmetica($coduti,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
				   $utilidad_4_3 = operacionAritmetica($coduti,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);
				   $utilidad_5_3 = operacionAritmetica($coduti,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
				   $utilidad_6_3 = operacionAritmetica($coduti,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);

				   $coduti="";
				   $utilidad_1_4 = operacionAritmetica($coduti,$cDesde1,$dfec_proceso,$mescierre,$icierre,1,$array1er);
				   $utilidad_2_4 = operacionAritmetica($coduti,$cDesde2,$dfec_proceso,$mescierre,$icierre,1,$array2do);
				   $utilidad_3_4 = operacionAritmetica($coduti,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
				   $utilidad_4_4 = operacionAritmetica($coduti,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);
				   $utilidad_5_4 = operacionAritmetica($coduti,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
				   $utilidad_6_4 = operacionAritmetica($coduti,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);
	 */			   
				   
				         $muestrate = "SI";
				   if($valor1==0 and $valor2==0 and $valor3==0 and $valor4==0 and $valor5==0 and $valor6==0){
                         $muestrate = "NO";
                   }				   
				   
				}  
	             ?>
				 

                <?php if ($bprimera == "SI") { ?>
                    <tr>
                        <td style='width:20%' align="LEFT">
                            <strong><?=$_SESSION["sDesBasedeDatos"]?></strong>
                        </td>
                        <td style='width:15%' align="CENTER">
                            <strong> MES <?php echo $mes1."/".$ano1;  ?> <br>
							                  VS.<br>
							         MES <?php echo $mes2."/".$ano2;  ?></strong>
                        </td> 
                      <!--xx<td style='width:5%' align="LEFT">
                            <strong></strong>
                        </td>-->
                        <td style='width:2%' align="LEFT">
                            <strong></strong>
                        </td>
                        <!--------------------------------- MES ACTUAL ------------------------------------>
                        <td style='width:15%' align="CENTER">
                            <strong>MES ACTUAL vs MES ANTERIOR<br>
									    (Diferencia Año)
									</strong>
                        </td> 
                        <td style='width:5%' align="LEFT">
                            <strong></strong>
                        </td>
                       <!-- <td style='width:2%' align="LEFT">
                            <strong></strong>
                        </td>-->
							<!--------------------------------- AÑO ACUMULADO ------------------------------------>
                        <td style='width:15%' align="CENTER">
                            <strong>AÑO ACUMULADO <br>  <?=$ano5?> vs <?=$ano6?></strong>
                        </td> 
                        <td style='width:5%' align="LEFT">
                            <strong></strong>
                        </td>
                       <!-- <td style='width:2%' align="LEFT">
                            <strong></strong>
                        </td>-->
                    </tr>
                    <!---------------------------- (TR)AÑOS ----------------------------------->
                    <tr>
                        <td style='width:20%' align="LEFT">
                            <strong>INDICADORES FINANCIEROS </strong>
                        </td>
                        <td>
                            <table border='1' width="100%">
                                <tr>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$ano1?></strong> <!-- año actual-->
                                    </td>
                                    <td style='width:25%' align="CENTER">
                                        <strong></strong> <!-- año actual-->
                                    </td>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$ano2?></strong> <!-- año anterior-->
                                    </td>
									 <!--xx<td style='width:25%' align="CENTER">
                                        <strong></strong> 
                                    </td>-->
                                </tr>
                            </table>
                        </td>
                        <!--xx<td style='width:5%' align="CENTER">
                            <strong>DIF</strong>
                        </td>-->
                        <td style='width:2%' align="CENTER">
                            <strong>%</strong>
                        </td>
                        <!--------------------------------- MES ACTUAL ------------------------------------>
                        <td>
                            <table border='1' width="100%">
                                <tr>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$ano3?></strong> <!-- año actual-->
                                    </td>
                                    <td style='width:25%' align="CENTER">
                                        <strong></strong> <!-- año actual-->
                                    </td>
                                 <td style='width:25%' align="CENTER">
                                        <strong><?=$ano4?></strong> <!-- año anterior-->
                                 </td>
									 <!--xx<td style='width:25%' align="CENTER">
                                        <strong></strong> 
                                    </td>-->
                                </tr>
                            </table>
                        </td>
                        <!--<td style='width:5%' align="CENTER">
                            <strong>DIF</strong>
                        </td>-->
                        <td style='width:2%' align="CENTER">
                            <strong>%</strong>
                        </td>
						<!--------------------------------- AÑO ACUMULADO ------------------------------------>
                        <td>
                            <table border='1' width="100%">
                                <tr>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$ano5?></strong> <!-- año actual-->
                                    </td>
									 <td style='width:25%' align="CENTER">
                                        <strong></strong> <!-- año actual-->
                                    </td>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$ano6?></strong> <!-- año anterior-->
                                    </td>
									<!-- <td style='width:25%' align="CENTER">
                                        <strong></strong> 
                                    </td>-->
                                </tr>
                            </table>
                        </td>
                        <!--<td style='width:5%' align="CENTER">
                            <strong>DIF</strong>
                        </td>-->
                        <td style='width:2%' align="CENTER">
                            <strong>%</strong>
                        </td>
                    </tr>
                    <!---------------------------- (TR)MESES ----------------------------------->
                    <tr>
                        <td style='width:20%' align="LEFT">
                            <strong>(en miles de dolares) </strong>
                        </td> 
                        <td>
                            <table border='1' width="100%">
                                <tr>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes1?></strong>
                                    </td>
									 <td style='width:25%' align="CENTER">
                                        <strong>%</strong> <!-- año actual-->
                                    </td>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes2?></strong><!--mes anterior -->
                                    </td>
								<!--xx<td style='width:25%' align="CENTER">
                                        <strong>%</strong> 
                                    </td>-->
                                </tr>
                            </table>
                        </td>
                        <td style='width:5%' align="LEFT">
                            <strong></strong>
                        </td>
                       <!-- <td style='width:2%' align="LEFT">
                            <strong></strong>
                        </td>-->
                        <!--------------------------------- MES ACTUAL ------------------------------------>
                        <td>
                            <table border='1' width="100%">
                                <tr>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes3?></strong> <!-- año actual-->
                                    </td>
									 <td style='width:25%' align="CENTER">
                                        <strong>%</strong> <!-- año actual-->
                                    </td>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes4?></strong> <!-- año anterior-->
                                    </td>
									<!--xx<td style='width:25%' align="CENTER">
                                        <strong>%</strong> 
                                    </td>-->
                                </tr>
                            </table>
                        </td>
                       <!-- <td style='width:5%' align="right">
                            <strong></strong>
                        </td>-->
                        <td style='width:2%' align="right">
                            <strong></strong>
                        </td>
						<!--------------------------------- AÑO ACUMULADO ------------------------------------>
						  <td>
                            <table border='1' width="100%">
                                <tr>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes5?></strong> <!-- año actual-->
                                    </td>
									 <td style='width:25%' align="CENTER">
                                        <strong>%</strong> <!-- año actual-->
                                    </td>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes6?></strong> <!-- año anterior-->
                                    </td>
									<!--<td style='width:25%' align="CENTER">
                                        <strong>%</strong> 
                                    </td>-->
                                </tr>
                            </table>
                        </td>
                        <!--<td style='width:5%' align="right">
                            <strong></strong>
                        </td>-->
                        <td style='width:2%' align="right">
                            <strong></strong>
                        </td>
                    </tr>
                <?php } $bprimera = "NO"; 
				
		if($codins == "8"){		
			for ($ix=1;$ix<=3 ;$ix++){
				  $ventas1U =  0; 
				  $ventas2U =  0; 
				  $ventas3U =  0; 
				  $ventas4U =  0; 
				  $ventas5U =  0; 
				  $ventas6U =  0; 
				  
			    $ubicacionU="TITU";
			    if($ix==1){
			      $codinsU=" 4.1.01 + 5.1.01 ";
				  $tituloctaU = "MARGEN BRUTO EN UTILIDAD VEHICULO";
			    }elseif($ix==2){
				 $codinsU=" 4.1.02 + 5.1.02 + 5.1.04";
				  $tituloctaU = "MARGEN BRUTO EN UTILIDAD POST-VENTA";
				}else{ 
				 // $codinsU="(4.1.01 + 5.1.01) + (4.1.02 + 5.1.02) "; 
				  $codinsU="(4) + (5) "; 
				  $tituloctaU = "MARGEN BRUTO VENTAS TOTALES";
				}
				
				   $valor1U = operacionAritmetica($codinsU,$cDesde1,$dfec_proceso,$mescierre,$icierre,1,$array1er,"N");
				   $valor2U = operacionAritmetica($codinsU,$cDesde2,$dfec_proceso,$mescierre,$icierre,1,$array2do,"N");
				   $valor3U = operacionAritmetica($codinsU,$cDesde3,$dfec_proceso,$mescierre,$icierre,1,$array3ra,"N");
				   $valor4U = operacionAritmetica($codinsU,$cDesde4,$dfec_proceso,$mescierre,$icierre,1,$array4ra,"N");
				 if($ix==1){
			      $codinsU=" 4.1.01 + 5.1.01 ";
				  $tituloctaU = "MARGEN BRUTO EN UTILIDAD VEHICULO";
				  $ventas1U =  abs($valor1U*100/$valorVENTAS1); 
				  $ventas2U =  abs($valor2U*100/$valorVENTAS2); 
				  $ventas3U =  abs($valor3U*100/$valorVENTAS3); 
				  $ventas4U =  abs($valor4U*100/$valorVENTAS4); 
			     }elseif($ix==2){
				  $codinsU=" 4.1.02 + 5.1.02 + 5.1.04";
				  $tituloctaU = "MARGEN BRUTO EN UTILIDAD POST-VENTA";
 				  $ventas1U =  abs($valor1U*100/$valorPOSTVENTAS1); 
				  $ventas2U =  abs($valor2U*100/$valorPOSTVENTAS2); 
				  $ventas3U =  abs($valor3U*100/$valorPOSTVENTAS3); 
				  $ventas4U =  abs($valor4U*100/$valorPOSTVENTAS4); 
				}else{ 
				//  $codinsU="(4.1.01 - 5.1.01) + (4.1.02 - 5.1.02) "; 
				  $codinsU="(4) + (5) "; 
				  $tituloctaU = "MARGEN BRUTO VENTAS TOTALES";
				}
				
				  $valor5U = operacionAritmetica($codinsU,$cDesde5,$dfec_proceso,$mescierre,$icierre,2,$array5ra,"N");
				  $valor6U = operacionAritmetica($codinsU,$cDesde6,$dfec_proceso,$mescierre,$icierre,2,$array6ra,"N");
				  $dif1U =  bcsub($valor2U,$valor1U);
				  $porcentaje1U = abs($dif1U*100/$valor1U);
				  $dif2U =  bcsub($valor4U,$valor3U);
				  $porcentaje2U = abs($dif2U*100/$valor3U);
				  $dif3U =  bcsub($valor6U,$valor5U);
				  $porcentaje3U = abs($dif3U*100/$valor5U);
			
			
			
			
			
			
			     if($ix==1){
				  $ventas5U =  abs($valor5U*100/$valorVENTAS1); 
				  $ventas6U =  abs($valor6U*100/$valorVENTAS2); 
			     }elseif($ix==2){
 				  $ventas5U =  abs($valor5U*100/$valorPOSTVENTAS1); 
				  $ventas6U =  abs($valor6U*100/$valorPOSTVENTAS2); 
				 }
			
			    $ventas1U =  $ventas1U/1000; 
				$ventas2U =  $ventas2U/1000; 
				$ventas3U =  $ventas3U/1000; 
				$ventas4U =  $ventas4U/1000; 
				$ventas5U =  $ventas5U/1000; 
				$ventas6U =  $ventas6U/1000; 
			
			
			    $valor1U =  $valor1U/1000;
				$valor2U =  $valor2U/1000;
				$valor3U =  $valor3U/1000;
				$valor4U =  $valor4U/1000;
				$valor5U =  $valor5U/1000;
				$valor6U =  $valor6U/1000;
				
				$dif1U =  $dif1U/1000;
				$dif2U =  $dif2U/1000;
				$dif3U =  $dif3U/1000;
			       
				   
						?>
						<!--REsutaldo-->
						<tr>
						   <td style='width:10%' align="LEFT">
									<?php 
								
									
										if($ubicacionU == "TITU"){
										  echo "<strong>".$tituloctaU."</strong>";
										}else{
										  echo $tituloctaU ;
										}
										
										
									?>
								</td>
								<td>
									<table border='0' width="100%">
										<tr>
										<?php if($ubicacionU == "TITU"){?>
											<td style='width:25%' align="right">
												 <strong><?=number_format($valor1U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
												 <strong><?=number_format($ventas1U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
												 <strong><?=number_format($valor2U,2)?></strong>
											</td>
										<?php }else{ ?>	
										    <td style='width:25%' align="right"></td>
										    <td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
										<?php } ?>								
										</tr>
									</table>
								</td>
							<?php if($ubicacionU == "TITU"){?>
								
								<td style='width:2%' align="right">
									<?php if($dif1U < 0){ ?>
									   <strong><font color="red"><?= number_format(abs($porcentaje1U),2)?></font></strong>
									<?php }else{ ?>
										<strong><?= number_format($porcentaje1U,2)?></strong>
									<?php } ?>							
								</td>
									<?php }else{ ?>	
										<td style='width:2%' align="right">
										</td>
									<?php } ?>	
									
						<!---MES ACTUAL----->			
								<td>
									<table border='0' width="100%">
									   <tr>
									<?php if($ubicacionU == "TITU"){?>
											<td style='width:25%' align="right">
												<strong><?=number_format($valor3U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
												 <strong><?=number_format($ventas3U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
												 <strong><?=number_format($valor4U,2)?></strong>
											</td>
									<?php }else{ ?>	
											<td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
									<?php } ?>	
									 </tr>
									</table>
								</td>
								<?php if($ubicacionU == "TITU"){?>
								<td style='width:2%' align="right">
									<?php if($dif2U < 0){ ?>
									   <strong><font color="red"><?= number_format(abs($porcentaje2U),2)?></font></strong>
									<?php }else{ ?>
										<strong><?= number_format($porcentaje2U,2)?></strong>
									<?php } ?>							
								</td>
								<?php }else{ ?>	
								<td style='width:2%' align="right">
								</td>
								<?php } ?>
								<!---AÑO ACUMULADO----->			
								<td>
									<table border='0' width="100%">
										<tr>
										<?php if($ubicacionU == "TITU"){?>
											<td style='width:25%' align="right">
												 <strong><?=number_format($valor5U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
											     <strong><?=number_format($ventas5U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
												 <strong><?=number_format($valor6U,2)?></strong>
											</td>
										<?php }else{ ?>	
											<td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
										<?php } ?>								
										</tr>
									</table>
								</td>
							<?php if($ubicacionU == "TITU"){?>
								<td style='width:2%' align="right">
									<?php if($dif3U < 0){ ?>
									   <strong><font color="red"><?= number_format(abs($porcentaje3U),2)?></font></strong>
									<?php }else{ ?>
										<strong><?= number_format($porcentaje3U,2)?></strong>
									<?php } ?>							
								</td>
									<?php }else{ ?>	
										<td style='width:5%' align="right" >
										</td>
										<td style='width:2%' align="right">
										</td>
									<?php } ?>	
							</tr>
						<?php
						}
			}
				


               if($codins =="5.1.03" || $codins =="5.1.02.02" || $codins =="4.1.02.03" || $codins =="8.2" || $codins =="5.1" || $codins =="4.1" || $codins =="4.1.01.01.003" || $codins =="4.1.02.01.002" || $codins =="4.1.02.01.003" || $codins =="4.1.02.02.002"){
			      $muestrate = "NO";
			   }



		
				
			if($muestrate == "SI"){
				?>
				<!--REsutaldo-->
				<tr>
				   <td style='width:10%' align="LEFT">
                            <?php 
							    if($ubicacion == "TITU"){
							      echo "<strong>".$titulocta. "</strong>";
								}else{
								  echo $titulocta;
								}
							?>
                        </td>
                        <td>
                            <table border='0' width="100%">
							    <tr>
								<?php if($ubicacion == "DETA"){?>
                                    <td style='width:25%' align="right">
                                         <?=number_format($valor1,2)?>
                                    </td>
									<td style='width:25%' align="right">
                                         <?=number_format($ventas1,2)?>
                                    </td>
                                    <td style='width:25%' align="right">
                                         <?=number_format($valor2,2)?>
                                    </td>
								<?php }elseif($ubicacion == "TITU"){ ?>	
									<td style='width:25%' align="right">
                                         <strong><?=number_format($valor1,2)?></strong>
                                    </td>
									<td style='width:25%' align="right">
                                         <strong><?=number_format($ventas1,2)?></strong>
                                    </td>
                                    <td style='width:25%' align="right">
                                         <strong><?=number_format($valor2,2)?></strong>
                                    </td>
								<?php }else{ ?>	
							        <td style='width:25%' align="right"></td>
									<td style='width:25%' align="right"></td>
									<td style='width:25%' align="right"></td>
								<?php } ?>								
                                </tr>
							</table>
                        </td>
					<?php if($ubicacion == "DETA"){?>
	                        <td style='width:2%' align="right">
							<?php if($dif1 < 0){ ?>
                               <font color="red"><?= number_format(abs($porcentaje1),2)?></font>
							<?php }else{ ?>
								<?= number_format($porcentaje1,2)?>
							<?php } ?>							
                        </td>
							<?php }elseif($ubicacion == "TITU"){ ?>	
								<td style='width:2%' align="right">
									<?php if($dif1 < 0){ ?>
									   <strong><font color="red"><?= number_format(abs($porcentaje1),2)?></font></strong>
									<?php }else{ ?>
										<strong><?= number_format($porcentaje1,2)?></strong>
									<?php } ?>							
								</td>								
							<?php }else{ ?>	
										<td style='width:2%' align="right">
										</td>
							<?php } ?>	
							
				<!---MES ACTUAL----->			
					    <td>
                            <table border='0' width="100%">
							   <tr>
							<?php if($ubicacion == "DETA"){?>
                                    <td style='width:25%' align="right">
                                        <?=number_format($valor3,2)?>
                                    </td>
									  <td style='width:25%' align="right">
                                        <?=number_format($ventas3,2)?>
                                    </td>
                                    <td style='width:25%' align="right">
                                         <?=number_format($valor4,2)?>
                                    </td>
							<?php }elseif($ubicacion == "TITU"){ ?>			
									<td style='width:25%' align="right">
                                        <strong><?=number_format($valor3,2)?></strong>
                                    </td>
									<td style='width:25%' align="right">
                                        <strong><?=number_format($ventas3,2)?></strong>
                                    </td>
                                    <td style='width:25%' align="right">
                                         <strong><?=number_format($valor4,2)?></strong>
                                    </td>
							<?php }else{ ?>	
                                    <td style='width:25%' align="right">
                                    </td>
									<td style='width:25%' align="right">
                                    </td>
                                    <td style='width:25%' align="right">
                                    </td>
							<?php } ?>	
							 </tr>
                            </table>
                        </td>
						<?php if($ubicacion == "DETA"){?>
                        <td style='width:2%' align="right">
							<?php if($dif2 < 0){ ?>
                               <font color="red"><?= number_format(abs($porcentaje2),2)?></font>
							<?php }else{ ?>
								<?= number_format($porcentaje2,2)?>
							<?php } ?>							
                        </td>
						<?php }elseif($ubicacion == "TITU"){ ?>
                        <td style='width:2%' align="right">
							<?php if($dif2 < 0){ ?>
                               <strong><font color="red"><?= number_format(abs($porcentaje2),2)?></font></strong>
							<?php }else{ ?>
								<strong><?= number_format($porcentaje2,2)?></strong>
							<?php } ?>							
                        </td>
						<?php }else{ ?>	
                        <td style='width:2%' align="right">
						</td>
						<?php } ?>
	                    <!---AÑO ACUMULADO----->			
						<td>
                            <table border='0' width="100%">
							    <tr>
								<?php if($ubicacion == "DETA"){?>
                                    <td style='width:25%' align="right">
                                        <?=number_format($valor5,2)?>
                                    </td>
									<td style='width:25%' align="right">
										<?=number_format($ventas5,2)?>
                                    </td>
                                    <td style='width:25%' align="right">
                                        <?=number_format($valor6,2)?>
                                    </td>
								<?php }elseif($ubicacion == "TITU"){ ?>
									<td style='width:25%' align="right">
                                         <strong><?=number_format($valor5,2)?></strong>
                                    </td>
									<td style='width:25%' align="right">
                                         <strong><?=number_format($ventas5,2)?></strong>
                                    </td>
                                    <td style='width:25%' align="right">
                                         <strong><?=number_format($valor6,2)?></strong>
                                    </td>
								<?php }else{ ?>	
							        <td style='width:25%' align="right"></td>
                                    <td style='width:25%' align="right"></td>
									<td style='width:25%' align="right"></td>
								<?php } ?>								
                                </tr>
							</table>
                        </td>
					<?php if($ubicacion == "DETA"){?>
                        <td style='width:2%' align="right">
							<?php if($dif3 < 0){ ?>
                               <font color="red"><?= number_format(abs($porcentaje3),2)?></font>
							<?php }else{ ?>
								<?= number_format($porcentaje3,2)?>
							<?php } ?>							
                        </td>
					<?php }elseif($ubicacion == "TITU"){ ?>	
                        <td style='width:2%' align="right">
							<?php if($dif3 < 0){ ?>
                               <strong><font color="red"><?= number_format(abs($porcentaje3),2)?></font></strong>
							<?php }else{ ?>
								<strong><?= number_format($porcentaje3,2)?></strong>
							<?php } ?>							
                        </td>
							<?php }else{ ?>	
								<td style='width:2%' align="right">
								</td>
							<?php } ?>	
                    </tr>
                <?php
	 			}
			  }
			//  if($codins == "9.1.01.01"){		
			for ($ix=1;$ix<=1 ;$ix++){
				  $ventas1U =  0; 
				  $ventas2U =  0; 
				  $ventas3U =  0; 
				  $ventas4U =  0; 
				  $ventas5U =  0; 
				  $ventas6U =  0; 
				  
			    $ubicacionU="TITU";
			     if($ix==1){
			      $codinsU="4 + 5 + 8 + 9";
				  $tituloctaU = "UTILIDAD O PERDIDA DEL EJERCICIO";
				 }
				   $valor1U = operacionAritmetica($codinsU,$cDesde1,$dfec_proceso,$mescierre,$icierre,1,$array1er,"N");
				   $valor2U = operacionAritmetica($codinsU,$cDesde2,$dfec_proceso,$mescierre,$icierre,1,$array2do,"N");
				   $valor3U = operacionAritmetica($codinsU,$cDesde3,$dfec_proceso,$mescierre,$icierre,1,$array3ra,"N");
				   $valor4U = operacionAritmetica($codinsU,$cDesde4,$dfec_proceso,$mescierre,$icierre,1,$array4ra,"N");
				 if($ix==1){
				  $codinsU=" 4 + 5 + 8 + 9";
				  $tituloctaU = "UTILIDAD O PERDIDA DEL EJERCICIO";
    			 }
				
				  $valor5U = operacionAritmetica($codinsU,$cDesde5,$dfec_proceso,$mescierre,$icierre,2,$array5ra,"N");
				   $valor6U = operacionAritmetica($codinsU,$cDesde6,$dfec_proceso,$mescierre,$icierre,2,$array6ra,"N");
				  /* $dif1U =  bcsub($valor2U,$valor1U);
			       $porcentaje1U = abs($dif1U*100/$valor1U);
				   $dif2U =  bcsub($valor4U,$valor3U);
				   $porcentaje2U = abs($dif2U*100/$valor3U);
				   $dif3U =  bcsub($valor6U,$valor5U);
			       $porcentaje3U = abs($dif3U*100/$valor5U);*/
				   
				   $dif1U =  bcsub($valor1U,$valor2U);
			       $porcentaje1U = abs($dif1U*100/$valor2U);
			       $dif2U =  bcsub($valor3U,$valor4U);
				   $porcentaje2U = abs($dif2U*100/$valor4U);
				   $dif3U =  bcsub($valor5U,$valor6U);
			       $porcentaje3U = abs($dif3U*100/$valor6U);
				   
				   $ventas1U =  abs($valor1U*100/$valorVENTAS1); 
				   
			     if($ix==1){
				  $ventas5U =  abs($valor5U*100/$valorVENTAS1); 
				  $ventas6U =  abs($valor6U*100/$valorVENTAS2); 
				 }
				 
				 
				  $dif1U= $dif1U /1000;
				  $dif2U= $dif2U /1000;
				  $dif3U= $dif3U /1000;

				  $ventas1U =  $ventas1U/1000; 
				  $ventas2U =  $ventas2U/1000; 
				  $ventas3U =  $ventas3U/1000; 
				  $ventas4U =  $ventas4U/1000; 
				  $ventas5U =  $ventas5U/1000; 
				  $ventas6U =  $ventas6U/1000; 
				  
				  $valor1U = $valor1U/1000;  
				  $valor2U = $valor2U/1000;  
				  $valor3U = $valor3U/1000;  
				  $valor4U = $valor4U/1000;  
				  $valor5U = $valor5U/1000;  
				  $valor6U = $valor6U/1000;  
				  
			
					
						?>
						<!--REsutaldo-->
						<tr>
						   <td style='width:10%' align="LEFT">
									<?php 
										if($ubicacionU == "TITU"){
										  echo "<strong>".$tituloctaU."</strong>";
										}else{
										  echo $tituloctaU;
										}
									?>
								</td>
								<td>
									<table border='0' width="100%">
										<tr>
										<?php if($ubicacionU == "TITU"){?>
											<td style='width:25%' align="right">
												 <strong><?=number_format($valor1U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
												 <strong><?=number_format($ventas1U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
												 <strong><?=number_format($valor2U,2)?></strong>
											</td>
										<?php }else{ ?>	
										    <td style='width:25%' align="right"></td>
										    <td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
										<?php } ?>								
										</tr>
									</table>
								</td>
							<?php if($ubicacionU == "TITU"){?>
								<td style='width:2%' align="right">
									<?php if($dif1U < 0){ ?>
									   <strong><font color="red"><?= number_format(abs($porcentaje1U),2)?></font></strong>
									<?php }else{ ?>
										<strong><?= number_format($porcentaje1U,2)?></strong>
									<?php } ?>							
								</td>
									<?php }else{ ?>	
										<td style='width:2%' align="right">
										</td>
									<?php } ?>	
									
						<!---MES ACTUAL----->			
								<td>
									<table border='0' width="100%">
									   <tr>
									<?php if($ubicacionU == "TITU"){?>
											<td style='width:25%' align="right">
												<strong><?=number_format($valor3U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
												 <strong><?=number_format($ventas3U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
												 <strong><?=number_format($valor4U,2)?></strong>
											</td>
									<?php }else{ ?>	
											<td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
									<?php } ?>	
									 </tr>
									</table>
								</td>
								<?php if($ubicacionU == "TITU"){?>
								<td style='width:2%' align="right">
									<?php if($dif2U < 0){ ?>
									   <strong><font color="red"><?= number_format(abs($porcentaje2U),2)?></font></strong>
									<?php }else{ ?>
										<strong><?= number_format($porcentaje2U,2)?></strong>
									<?php } ?>							
								</td>
								<?php }else{ ?>	
								<td style='width:2%' align="right">
								</td>
								<?php } ?>
								<!---AÑO ACUMULADO----->			
								<td>
									<table border='0' width="100%">
										<tr>
										<?php if($ubicacionU == "TITU"){?>
											<td style='width:25%' align="right">
												 <strong><?=number_format($valor5U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
											     <strong><?=number_format($ventas5U,2)?></strong>
											</td>
											<td style='width:25%' align="right">
												 <strong><?=number_format($valor6U,2)?></strong>
											</td>
										<?php }else{ ?>	
											<td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
										<?php } ?>								
										</tr>
									</table>
								</td>
							<?php if($ubicacionU == "TITU"){?>
								<td style='width:2%' align="right">
									<?php if($dif3U < 0){ ?>
									   <strong><font color="red"><?= number_format(abs($porcentaje3U),2)?></font></strong>
									<?php }else{ ?>
										<strong><?= number_format($porcentaje3U,2)?></strong>
									<?php } ?>							
								</td>
									<?php }else{ ?>	
										<td style='width:2%' align="right">
										</td>
									<?php } ?>	
							</tr>
						<?php
						}
			//}		

			  
			  
			} 
        ?>
        </table>    
    </body>
</html>