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
				   $condicionBoG = " and codigo <4 and length(ltrim(rtrim(codigo))) <=9";
				   
				   
				
				   
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
			$TotalAi = count($arrayTotal);  
			$iFila = -1;
			$primeraTotal = true;
			$codigo6Ant = '';
			for($Ai = 0;$Ai <= $TotalAi;$Ai++){
            	$iFila++;
				$codins = $arrayTotal[$Ai][0];
				$titulocta = $arrayTotal[$Ai][1];
				$ubicacion = $arrayTotal[$Ai][2] ;
				$iNegrita="";
				$fNegrita="";
				if(strlen(trim($codins))==1 or strlen(trim($codins))==3 or strlen(trim($codins))==6){
				    $ubicacion = "TITU"; 
				}
				   $valor1 = 0;
				   $valor2 = 0;
				
				   $dif1 = 0;
				   $porcentaje1 = 0;
				   $dif2 = 0;
				   $porcentaje2 = 0;
				   $dif3 = 0;
				   $porcentaje3 = 0;
				   
				  if($codins != ""){
				   $valor1 = operacionAritmetica($codins,$cDesde1,$dfec_proceso,$mescierre,$icierre,2,$array1er,"N");
				   $valor2 = operacionAritmetica($codins,$cDesde2,$dfec_proceso,$mescierre,$icierre,2,$array2do,"N");
				      
				   $arrayTotal[$Ai][3] = $valor1;
				   $arrayTotal[$Ai][4] = $valor2;
				  
				   
				   $arrayTemp[$Ai][3] = $valor1;
				   $arrayTemp[$Ai][4] = $valor2;
				 
					
				   $dif1 =  bcsub($valor2,$valor1);
			       $porcentaje1 = abs($dif1*100/$valor2);
				   $ventas1 = 0;
				   $ventas2 = 0;
				 
				 

				 if(strlen(trim($codins)) == 6){
				      $valorctamayor1= $valor1;
					  $valorctamayor2= $valor2;
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
				   }
				   
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
											$ventas1 =  abs($valor1*100/$valorctamayor1);     
											$ventas2 =  abs($valor2*100/$valorctamayor2);     
											break;
										 }// if ((strlen($arrayTemp[$AiTemp][0]) < strlen($arrayTotal[$Ai][0])) && substr($arrayTotal[$Ai][0],0,strlen($arrayTemp[$AiTemp][0])) == $arrayTemp[$AiTemp][0]){
										}else{//if($arrayTotal[$Ai][0] < '5'){
											if($codBuscar ==trim($arrayTemp[$AiTemp][0])){
												$valorctamayor1 = $arrayTemp[$AiTemp][3];
												$valorctamayor2 = $arrayTemp[$AiTemp][4];
												$ventas1 =  abs($valor1*100/$valorctamayor1);     
												$ventas2 =  abs($valor2*100/$valorctamayor2);     
												break;
											}	//if($codBuscar ==trim($arrayTemp[$AiTemp][0])){
										 }//if($arrayTotal[$Ai][0] < '5'){
					   }
   				   }
				   
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
                                        <strong><?=$mes1." ".$ano1?></strong> <!-- año actual-->
                                    </td>
                                    <td style='width:25%' align="CENTER">
                                        <strong><?=$mes2." ".$ano2?></strong> <!-- año anterior-->
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
                <?php } $bprimera = "NO"; 
				

               if($codins =="5.1.03" || $codins =="5.1.02.02" || $codins =="4.1.02.03" || $codins =="8.2" || $codins =="5.1" || $codins =="4.1" || $codins =="4.1.01.01.003" || $codins =="4.1.02.01.002" || $codins =="4.1.02.01.003" || $codins =="4.1.02.02.002"){
			      $muestrate = "NO";
			   }

		   if($muestrate == "SI"){
				if (strlen(trim($codins)) == 6){
					if ($primeraTotal == false){ 
				?>
				<tr>
						<td style='width:10%' align="LEFT">
                            <?php 
							    if($ubicacion == "TITU"){
							      echo "<strong>".$descripcionTotal."</strong>";
								}else{
								  echo $descripcionTotal;
								}
							?>
                        </td>
                        <td>
                            <table border='0' width="100%">
							    <tr>
									<td style='width:25%' align="right">
                                         <strong><?php 
											      echo number_format($valor1Total,2);
									        ?></strong>
                                    </td>
                                    <td style='width:25%' align="right">
                                         <strong><?php 
												  echo number_format($valor2Total,2);
												 ?></strong>
                                    </td>
                                </tr>
							</table>
                        </td>
							<td style='width:5%' align="right">
												<?php if($dif1Total < 0){ ?>
												<strong><font color="red"><?php
													    echo number_format(abs($dif1Total),2);
												?></font></strong>
												<?php }else{ ?>
													<strong><?php
														  echo number_format($dif1Total,2);
														  ?></strong>
												<?php } ?>	
								</td>	
								<td style='width:2%' align="right">
									<?php if($dif1Total < 0){ ?>
									   <strong><font color="red"><?php 
									                         echo number_format(abs($porcentaje1Total),2);
														  ?></font></strong>
									<?php }else{ ?>
										<strong><?php 
										                     echo number_format($porcentaje1Total,2);
												?></strong>
									<?php } ?>							
								</td>								
                    </tr>
				
				<?php
				    }// if ($primeraTotal == false){ 
					
						$codigoTotal =$codins;
					    $descripcionTotal = "TOTAL:".$titulocta;
						$valor1Total = $valor1;
						$valor2Total = $valor2;
						$dif1Total = $dif1;
						$porcentaje1Total = $porcentaje1;
						$primeraTotal = false; 
				}
				?>
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
                                         <?php if(strlen(trim($codins))>6){
										         echo number_format($valor1,2);
										       }
										 ?>
                                    </td>
                                    <td style='width:25%' align="right">
                                         <?php  if(strlen(trim($codins))>6){ 
										          echo number_format($valor2,2);
												}
												?>
                                    </td>
								<?php }elseif($ubicacion == "TITU"){ ?>	
									<td style='width:25%' align="right">
                                         <strong><?php 
										       if(strlen(trim($codins))>6){ 
											      echo number_format($valor1,2);
											   }	  
									        ?></strong>
                                    </td>
                                    <td style='width:25%' align="right">
                                         <strong><?php 
										         if(strlen(trim($codins))>6){  
												  echo number_format($valor2,2);
												 }  
												 ?></strong>
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
                              <font color="red"> <?php
                   							     if(strlen(trim($codins))>6){ 
													echo number_format($dif1,2);
												 }	
												?></font>
							 <?php }else{ ?>
                              <?php      if(strlen(trim($codins))>6){  
							              echo number_format($dif1,2);
										 } 
							   ?>
							<?php } ?>							 
                        </td>
                        <td style='width:2%' align="right">
							<?php if($dif1 < 0){ ?>
                               <font color="red"><?php
							        if(strlen(trim($codins))>6){  
									   echo number_format(abs($porcentaje1),2);
									}   
							   ?></font>
							<?php }else{ ?>
								<?php   if(strlen(trim($codins))>6){ 
        								   echo number_format($porcentaje1,2);
										}   
									  ?>
							<?php } ?>							
                        </td>
							<?php }elseif($ubicacion == "TITU"){ ?>	
								<td style='width:5%' align="right">
												<?php if($dif1 < 0){ ?>
												<strong><font color="red"><?php
												      if(strlen(trim($codins))>6){  
													    echo number_format(abs($dif1),2);
													  }	
												?></font></strong>
												<?php }else{ ?>
													<strong><?php
													      if(strlen(trim($codins))>6){   
														  echo number_format($dif1,2);
														  }
														  ?></strong>
												<?php } ?>	
								</td>	
								<td style='width:2%' align="right">
									<?php if($dif1 < 0){ ?>
									   <strong><font color="red"><?php 
														  if(strlen(trim($codins))>6){  
									                         echo number_format(abs($porcentaje1),2);
														  }
														  ?></font></strong>
									<?php }else{ ?>
										<strong><?php 
                     									  if(strlen(trim($codins))>6){   
										                     echo number_format($porcentaje1,2);
														  }
												?></strong>
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
				/*if (strlen(trim($codins)) == 6){
						$codigoTotal =$codins;
					    $descripcionTotal =$titulocta;
						$valor1Total = $valor1;
						$valor2Total = $valor2;
						$dif1Total = $dif1;
						$porcentaje1Total = $porcentaje1;
				}*/
	 			}
			  }
			  ?>
					<tr>
						<td style='width:10%' align="LEFT">
                            <?php 
							      echo "<strong>".$descripcionTotal."</strong>";
							?>
                        </td>
                        <td>
                            <table border='0' width="100%">
							    <tr>
									<td style='width:25%' align="right">
                                         <strong><?php 
											      echo number_format($valor1Total,2);
									        ?></strong>
                                    </td>
                                    <td style='width:25%' align="right">
                                         <strong><?php 
												  echo number_format($valor2Total,2);
												 ?></strong>
                                    </td>
                                </tr>
							</table>
                        </td>
							<td style='width:5%' align="right">
												<?php if($dif1Total < 0){ ?>
												<strong><font color="red"><?php
													    echo number_format(abs($dif1Total),2);
												?></font></strong>
												<?php }else{ ?>
													<strong><?php
														  echo number_format($dif1Total,2);
														  ?></strong>
												<?php } ?>	
								</td>	
								<td style='width:2%' align="right">
									<?php if($dif1Total < 0){ ?>
									   <strong><font color="red"><?php 
									                         echo number_format(abs($porcentaje1Total),2);
														  ?></font></strong>
									<?php }else{ ?>
										<strong><?php 
										                     echo number_format($porcentaje1Total,2);
												?></strong>
									<?php } ?>							
								</td>								
                    </tr>
		<?php			
			} 
        ?>
		
        </table>    
    </body>
</html>