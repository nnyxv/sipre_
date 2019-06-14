<?php
session_start();
include("FuncionesPHP.php");
require('idioma_modulo.php'); // Configuracion del Idioma
require('fpdf.php');

$sUsuario = $_SESSION["UsuarioSistema"];
$REPORTE = $_REQUEST['ExceloPdf'];
$cDesde1 = $_REQUEST['cDesde1']; //1er
$cDesde2 = $_REQUEST['cDesde2']; //2do
$cDesde3 = $_REQUEST['cDesde3']; //3er
$cDesde4 = $_REQUEST['cDesde4']; //4to
$cDesde5 = $_REQUEST['cDesde5']; // codigo de formato
$cDesde6 = $_REQUEST['cDesde6']; // asiento de cierre
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
					
				   $cPar1	= "$Fecha_ano1-$Fecha_mes1-01";
				   $cPar2	= "$Fecha_ano1-$Fecha_mes1-".ultimo_dia($Fecha_mes1,$Fecha_ano1);
				     CargarSaldos($cPar1,$cPar2,"","",$icierre);
					if ($icierre==1){
						$SqlStr=" select a.codigo,sum(a.saldo_ant + a.debe-a.haber) as monto";
					}else{ 
						$SqlStr=" select a.codigo,sum(a.saldo_ant+(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr)) as monto ";
					} 		
						$SqlStr.=" from cuentageneral a where usuario = '$sUsuario' group by a.codigo";
						
						$exc1er = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$array1er = array();
						while($row1er= mysql_fetch_row($exc1er)){
						   $insertarEste1er = array($row1er[0], $row1er[1]);
							array_push($array1er, $insertarEste1er);
							
						}
						
						
				   $cPar1	= "$Fecha_ano2-$Fecha_mes2-01";
				   $cPar2	= "$Fecha_ano2-$Fecha_mes2-".ultimo_dia($Fecha_mes2,$Fecha_ano2);
				   
				   	   CargarSaldos($cPar1,$cPar2,"","",$icierre);
				   	if ($icierre==1){
						$SqlStr=" select a.codigo,sum(a.saldo_ant + a.debe-a.haber as monto)";
					}else{ 
						$SqlStr=" select a.codigo,sum(a.saldo_ant+(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr)) as monto ";
					} 		
						$SqlStr.=" from cuentageneral a where usuario = '$sUsuario' group by a.codigo";
						$exc2do = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$array2do = array();
						while($row2do= mysql_fetch_row($exc2do)){
						   $insertarEste2do = array($row2do[0],$row2do[1]);
							array_push($array2do, $insertarEste2do);
						}
						
						
						
				   $cPar1	= "$Fecha_ano3-$Fecha_mes3-01";
				   $cPar2	= "$Fecha_ano3-$Fecha_mes3-".ultimo_dia($Fecha_mes3,$Fecha_ano3);
				   
				   	   CargarSaldos($cPar1,$cPar2,"","",$icierre);
				   	if ($icierre==1){
						$SqlStr=" select a.codigo,sum(a.saldo_ant + a.debe-a.haber as monto)";
					}else{ 
						$SqlStr=" select a.codigo,sum(a.saldo_ant+(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr)) as monto ";
					} 		
						$SqlStr.=" from cuentageneral a where usuario = '$sUsuario' group by a.codigo";
						$exc3ra = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$array3ra = array();
						while($row3ra= mysql_fetch_row($exc3ra)){
						   $insertarEste3ra = array($row3ra[0],$row3ra[1]);
							array_push($array3ra, $insertarEste3ra);
						}

						
				   $cPar1	= "$Fecha_ano4-$Fecha_mes4-01";
				   $cPar2	= "$Fecha_ano4-$Fecha_mes4-".ultimo_dia($Fecha_mes4,$Fecha_ano4);
				   
				   	   CargarSaldos($cPar1,$cPar2,"","",$icierre);
				   	if ($icierre==1){
						$SqlStr=" select a.codigo,sum(a.saldo_ant + a.debe-a.haber as monto)";
					}else{ 
						$SqlStr=" select a.codigo,sum(a.saldo_ant+(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr)) as monto ";
					} 		
						$SqlStr.=" from cuentageneral a where usuario = '$sUsuario' group by a.codigo";
						$exc4ra = EjecutarExec($con,$SqlStr) or die($SqlStr);
						$array4ra = array();
						while($row4ra= mysql_fetch_row($exc4ra)){
						   $insertarEste4ra = array($row4ra[0],$row4ra[1]);
							array_push($array4ra, $insertarEste4ra);
						}

						
				
			$SqlStr=" delete from cuentageneral where usuario = '$sUsuario'";
			$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
           
 			$SqlStr="select titulo,cod_ins,ubicacion from balance_a where formato = '$cDesde5' order by orden";
			//$exc = EjecutarExec($con,$SqlStr) or die(mysql_error());
		  	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
			
			// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
			$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
				INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
			$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
			$rowConfig403 = ObtenerFetch($rsConfig403);
			$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
							
		
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
				   $dif1 = 0;
				   $porcentaje1 = 0;
				   $dif2 = 0;
				   $porcentaje2 = 0;
				   
				  if($codins != ""){
					if ($valor == 1) { //1 = Venezuela
					
					   $valor1 = operacionAritmetica($codins,$cDesde1,$dfec_proceso,$mescierre,$icierre,2,$array1er);
					   $valor2 = operacionAritmetica($codins,$cDesde2,$dfec_proceso,$mescierre,$icierre,2,$array2do);
					   $valor3 = operacionAritmetica($codins,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
					   $valor4 = operacionAritmetica($codins,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);
					   $dif1 =  bcsub($valor2,$valor1);
					   $porcentaje1 = $dif1*100/$valor1;
					   $dif2 =  bcsub($valor4,$valor3);
					   $porcentaje2 = $dif2*100/$valor3;
					}
					
					if ($valor == 2 || $valor == 3) { //2 = Panama, 3 = Puerto Rico
					
					   $valor1 = operacionAritmetica($codins,$cDesde1,$dfec_proceso,$mescierre,$icierre,2,$array1er);
					   $valor2 = operacionAritmetica($codins,$cDesde2,$dfec_proceso,$mescierre,$icierre,1,$array2do);
					   $valor3 = operacionAritmetica($codins,$cDesde3,$dfec_proceso,$mescierre,$icierre,2,$array3ra);
					   $valor4 = operacionAritmetica($codins,$cDesde4,$dfec_proceso,$mescierre,$icierre,2,$array4ra);
					   /*$dif1 =  bcsub($valor2,$valor1);
					   $porcentaje1 = $dif1*100/$valor1;*/
					   $dif1 =  bcsub($valor1,$valor2);
					   $porcentaje1 = $dif1*100/$valor1;
					   
					   $valor1 = $valor1 /1000;
					   $valor2 = $valor2 /1000;
					   $dif1 = $dif1/1000;
					   
					   /*$dif2 =  bcsub($valor4,$valor3);;
					   $porcentaje2 = $dif2*100/$valor3;*/
					   $dif2 =  bcsub($valor3,$valor4);
					   $porcentaje2 = $dif2*100/$valor3;
					   $valor3 = $valor3 /1000;
					   $valor4 = $valor4 /1000;
					   $dif2 = $dif2/1000;
					}
				}
	          ?>

                <?php if ($bprimera == "SI") { ?>
                    <tr>
                        <td style='width:20%' align="LEFT">
                            <strong><?=$_SESSION["sDesBasedeDatos"]?></strong>
                        </td>
                        <td style='width:15%' align="CENTER">
                            <strong>ACUMULADO MES ACTUAL <br>
							                  VS.<br>
							ACUMULADO MES ANTERIOR</strong>
                        </td> 
                        <td style='width:5%' align="LEFT">
                            <strong></strong>
                        </td>
                        <td style='width:2%' align="LEFT">
                            <strong></strong>
                        </td>
                        <!--------------------------------- MES ACTUAL ------------------------------------>
                        <td style='width:15%' align="CENTER">
                            <strong>ACUMULADO AÑO MES ACTUAL <br>
												VS. <br>
									ACUMULADO AÑO MES ANTERIOR</strong>
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
                    </tr>
                <?php } $bprimera = "NO"; ?>
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
                    </tr>

                <?php
	 			}
			} 
        ?>
        </table>    
    </body>
</html>