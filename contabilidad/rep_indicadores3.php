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
        <table border="0" width="150%" cellpadding="0px" >  
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
    
        <table border="1" style='width:150%'>
                   <?php
				   //$condicionBoG = " and (trim(codigo)='4' or trim(codigo)='5')";
				   $condicionBoG = " and codigo <4";
				   
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
            while ($row = ObtenerFetch($exc)) {
            	$iFila++;
                $titulocta = trim(ObtenerResultado($exc,1,$iFila)) ;
				$codins = trim(ObtenerResultado($exc,2,$iFila)) ;
				$ubicacion = trim(ObtenerResultado($exc,3,$iFila)) ;
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
				   $valor1 = operacionAritmetica($codins,$cDesde1,$dfec_proceso,$mescierre,$icierre,1,$array1er);
				   $valor2 = operacionAritmetica($codins,$cDesde2,$dfec_proceso,$mescierre,$icierre,1,$array2do);
				   $valor3 = operacionAritmetica($codins,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
				   $valor4 = operacionAritmetica($codins,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);
				   $valor5 = operacionAritmetica($codins,$cDesde5,$dfec_proceso,$mescierre,$icierre,2,$array5ra);
				   $valor6 = operacionAritmetica($codins,$cDesde6,$dfec_proceso,$mescierre,$icierre,2,$array6ra);
				   $dif1 =  bcsub($valor2,$valor1);
			       $porcentaje1 = $dif1*100/$valor1;
				   $dif2 =  bcsub($valor4,$valor3);;
				   $porcentaje2 = $dif2*100/$valor3;;
				   $dif3 =  bcsub($valor6,$valor5);
			       $porcentaje3 = $dif3*100/$valor5;
				   
				   
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
                            <strong> MES ACTUAL <br>
							                  VS.<br>
							         MES ANTERIOR</strong>
                        </td> 
                        <td style='width:5%' align="LEFT">
                            <strong></strong>
                        </td>
                        <td style='width:2%' align="LEFT">
                            <strong></strong>
                        </td>
                        <!--------------------------------- MES ACTUAL ------------------------------------>
                        <td style='width:15%' align="CENTER">
                            <strong>MES ACTUAL <br>
									(Diferencia Año)<br>
									</strong>
                        </td> 
                        <td style='width:5%' align="LEFT">
                            <strong></strong>
                        </td>
                        <td style='width:2%' align="LEFT">
                            <strong></strong>
                        </td>
							<!--------------------------------- AÑO ACUMULADO ------------------------------------>
                        <td style='width:15%' align="CENTER">
                            <strong>AÑO ACUMULADO <br>
								<br>
									</strong>
                        </td> 
                        <td style='width:5%' align="LEFT">
                            <strong></strong>
                        </td>
                        <td style='width:2%' align="LEFT">
                            <strong></strong>
                        </td>
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
                                        <strong><?=$ano2?></strong> <!-- año anterior-->
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style='width:5%' align="CENTER">
                            <strong>DIF</strong>
                        </td>
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
                                        <strong><?=$ano4?></strong> <!-- año anterior-->
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style='width:5%' align="CENTER">
                            <strong>DIF</strong>
                        </td>
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
                                        <strong><?=$ano6?></strong> <!-- año anterior-->
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style='width:5%' align="CENTER">
                            <strong>DIF</strong>
                        </td>
                        <td style='width:2%' align="CENTER">
                            <strong>%</strong>
                        </td>
                    </tr>
                    <!---------------------------- (TR)MESES ----------------------------------->
                    <tr>
                        <td style='width:20%' align="LEFT">
                            <strong>(en miles de bolivares) </strong>
                        </td> 
                        <td>
                            <table border='1' width="100%">
                                <tr>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes1?></strong>
                                    </td>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes2?></strong><!--mes anterior -->
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style='width:5%' align="LEFT">
                            <strong></strong>
                        </td>
                        <td style='width:2%' align="LEFT">
                            <strong></strong>
                        </td>
                        <!--------------------------------- MES ACTUAL ------------------------------------>
                        <td>
                            <table border='1' width="100%">
                                <tr>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes3?></strong> <!-- año actual-->
                                    </td>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes4?></strong> <!-- año anterior-->
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style='width:5%' align="right">
                            <strong></strong>
                        </td>
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
                                        <strong><?=$mes6?></strong> <!-- año anterior-->
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style='width:5%' align="right">
                            <strong></strong>
                        </td>
                        <td style='width:2%' align="right">
                            <strong></strong>
                        </td>
                    </tr>
                <?php } $bprimera = "NO"; 
				
		if($codins == "8"){		
			for ($ix=1;$ix<=2 ;$ix++){
			    $ubicacionU="TITU";
			    if($ix==1){
			      $codinsU=" 4.1.01 - 5.1.01 ";
				  $tituloctaU = "MARGEN BRUTO EN UTILIDAD VEHICULO";
			    }else{
				  $codinsU=" 4.1.02 - 5.1.02 ";
				  $tituloctaU = "MARGEN BRUTO EN UTILIDAD POST-VENTA";
				}
				
				   $valor1U = operacionAritmetica($codinsU,$cDesde1,$dfec_proceso,$mescierre,$icierre,1,$array1er);
				   $valor2U = operacionAritmetica($codinsU,$cDesde2,$dfec_proceso,$mescierre,$icierre,1,$array2do);
				   $valor3U = operacionAritmetica($codinsU,$cDesde3,$dfec_proceso,$mescierre,$icierre,1,$array3ra);
				   $valor4U = operacionAritmetica($codinsU,$cDesde4,$dfec_proceso,$mescierre,$icierre,1,$array4ra);
				 if($ix==1){
			      $codinsU=" 4.1.01 + 5.1.01 ";
				  $tituloctaU = "MARGEN BRUTO EN UTILIDAD VEHICULO";
			    }else{
				  $codinsU=" 4.1.02 + 5.1.02 ";
				  $tituloctaU = "MARGEN BRUTO EN UTILIDAD POST-VENTA";
				}	

				  $valor5U = operacionAritmetica($codinsU,$cDesde5,$dfec_proceso,$mescierre,$icierre,2,$array5ra);
				   $valor6U = operacionAritmetica($codinsU,$cDesde6,$dfec_proceso,$mescierre,$icierre,2,$array6ra);
				   $dif1U =  bcsub($valor2U,$valor1U);
			       $porcentaje1U = $dif1U*100/$valor1U;
				   $dif2U =  bcsub($valor4U,$valor3U);
				   $porcentaje2U = $dif2U*100/$valor3U;
				   $dif3U =  bcsub($valor6U,$valor5U);
			       $porcentaje3U = $dif3U*100/$valor5U;
			
					
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
												 <strong><?=number_format($valor2U,2)?></strong>
											</td>
										<?php }else{ ?>	
											<td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
										<?php } ?>								
										</tr>
									</table>
								</td>
							<?php if($ubicacionU == "TITU"){?>
								<td style='width:5%' align="right" >
									 <?php if($dif1U < 0){ ?>
									  <strong><font color="red"> <?= number_format($dif1U,2)?></font></strong>
									 <?php }else{ ?>
									  <strong><?=number_format($dif1U,2)?></strong>
									<?php } ?>							 
								</td>
								<td style='width:2%' align="right">
									<?php if($dif1U < 0){ ?>
									   <strong><font color="red"><?= number_format(abs($porcentaje1U),2)?></font></strong>
									<?php }else{ ?>
										<strong><?= number_format($porcentaje1U,2)?></strong>
									<?php } ?>							
								</td>
									<?php }else{ ?>	
										<td style='width:5%' align="right" >
										</td>
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
												 <strong><?=number_format($valor4U,2)?></strong>
											</td>
									<?php }else{ ?>	
											<td style='width:25%' align="right">
											</td>
											<td style='width:25%' align="right">
											</td>
									<?php } ?>	
									 </tr>
									</table>
								</td>
								<?php if($ubicacionU == "TITU"){?>
								<td style='width:5%' align="right" >
									 <?php if($dif2U < 0){ ?>
									  <strong><font color="red"> <?= number_format($dif2U,2)?></font></strong>
									 <?php }else{ ?>
									  <?=number_format($dif2U,2)?>
									<?php } ?>							 
								</td>
								<td style='width:2%' align="right">
									<?php if($dif2U < 0){ ?>
									   <strong><font color="red"><?= number_format(abs($porcentaje2U),2)?></font></strong>
									<?php }else{ ?>
										<strong><?= number_format($porcentaje2U,2)?></strong>
									<?php } ?>							
								</td>
								<?php }else{ ?>	
								<td style='width:5%' align="right" >
								</td>
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
												 <strong><?=number_format($valor6U,2)?></strong>
											</td>
										<?php }else{ ?>	
											<td style='width:25%' align="right"></td>
											<td style='width:25%' align="right"></td>
										<?php } ?>								
										</tr>
									</table>
								</td>
							<?php if($ubicacionU == "TITU"){?>
								<td style='width:5%' align="right" >
									 <?php if($dif3U < 0){ ?>
									  <strong><font color="red"> <?= number_format($dif3U,2)?></font></strong>
									 <?php }else{ ?>
									  <strong><?=number_format($dif3U,2)?></strong>
									<?php } ?>							 
								</td>
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
				
				
				
			if($muestrate == "SI"){
				?>
				<!--REsutaldo-->
				<tr>
				   <td style='width:10%' align="LEFT">
                            <?php 
							    if($ubicacion == "TITU"){
							      echo "<strong>".$titulocta."</strong>";
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
                                         <?=number_format($valor2,2)?>
                                    </td>
								<?php }else{ ?>	
							        <td style='width:25%' align="right"></td>
                                    <td style='width:25%' align="right"></td>
								<?php } ?>								
                                </tr>
							</table>
                        </td>
					<?php if($ubicacion == "DETA"){?>
						<td style='width:5%' align="right" >
						     <?php if($dif1 < 0){ ?>
                              <font color="red"> <?= number_format($dif1,2)?></font>
							 <?php }else{ ?>
                              <?=number_format($dif1,2)?>
							<?php } ?>							 
                        </td>
                        <td style='width:2%' align="right">
							<?php if($dif1 < 0){ ?>
                               <font color="red"><?= number_format(abs($porcentaje1),2)?></font>
							<?php }else{ ?>
								<?= number_format($porcentaje1,2)?>
							<?php } ?>							
                        </td>
							<?php }else{ ?>	
								<td style='width:5%' align="right" >
								</td>
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
                                         <?=number_format($valor4,2)?>
                                    </td>
							<?php }else{ ?>	
                                    <td style='width:25%' align="right">
                                    </td>
                                    <td style='width:25%' align="right">
                                    </td>
							<?php } ?>	
							 </tr>
                            </table>
                        </td>
						<?php if($ubicacion == "DETA"){?>
                        <td style='width:5%' align="right" >
						     <?php if($dif2 < 0){ ?>
                              <font color="red"> <?= number_format($dif2,2)?></font>
							 <?php }else{ ?>
                              <?=number_format($dif2,2)?>
							<?php } ?>							 
                        </td>
                        <td style='width:2%' align="right">
							<?php if($dif2 < 0){ ?>
                               <font color="red"><?= number_format(abs($porcentaje2),2)?></font>
							<?php }else{ ?>
								<?= number_format($porcentaje2,2)?>
							<?php } ?>							
                        </td>
						<?php }else{ ?>	
						<td style='width:5%' align="right" >
						</td>
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
                                         <?=number_format($valor6,2)?>
                                    </td>
								<?php }else{ ?>	
							        <td style='width:25%' align="right"></td>
                                    <td style='width:25%' align="right"></td>
								<?php } ?>								
                                </tr>
							</table>
                        </td>
					<?php if($ubicacion == "DETA"){?>
						<td style='width:5%' align="right" >
						     <?php if($dif3 < 0){ ?>
                              <font color="red"> <?= number_format($dif3,2)?></font>
							 <?php }else{ ?>
                              <?=number_format($dif3,2)?>
							<?php } ?>							 
                        </td>
                        <td style='width:2%' align="right">
							<?php if($dif3 < 0){ ?>
                               <font color="red"><?= number_format(abs($porcentaje3),2)?></font>
							<?php }else{ ?>
								<?= number_format($porcentaje3,2)?>
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
			} 
        ?>
        </table>    
    </body>
</html>