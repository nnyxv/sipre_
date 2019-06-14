<?php
session_start();
include("FuncionesPHP.php");
require('idioma_modulo.php'); // Configuracion del Idioma
require('fpdf.php');

$sUsuario = $_SESSION["UsuarioSistema"];
if ($cDesde3 == 'NO'){
	$iCierre = 0;
}else{ 
	$iCierre = 1;
} 

$Nivel = $cDesde2 - 1;

CargarSaldosMes($cDesde1,$cHasta1,'','',$iCierre);
$con = ConectarBD();
class PDF extends FPDF{
	var $DesdeHasta;

	function Header(){//Cabecera de página
		global $balanceGananciaPerdidaMesTitulo;
		global $al;
		global $utilidadBrutaVenta;
		global $utilidadNetaOperaciones;
		global $utilidadPerdidaEjercicio;
				
		$con = ConectarBD();
		$sTabla = 'parametros';
		$sCondicion = '';
		$sCampos = 'fec_proceso, rif, descrip';
		$SqlStr = 'SELECT '.$sCampos.' FROM '.$sTabla;
		$exc = EjecutarExec($con,$SqlStr) or die(mysql_error());
		if (NumeroFilas($exc) > 0){
			$xDFecha = obFecha(ObtenerResultado($exc,1),'D');
			$xMFecha = obFecha(ObtenerResultado($exc,1),'M');
			$xAFecha = obFecha(ObtenerResultado($exc,1),'A');
			$_SESSION["rifEmpresa"] = ObtenerResultado($exc,2);
			$_SESSION["descrip"] = ObtenerResultado($exc,3);
		}
		
		$TituloEncabezado = '';
		$TituloEmpresa = $_SESSION["descrip"];//$_SESSION["sDesBasedeDatos"];
		$TituloRif = $_SESSION["rifEmpresa"];
		$TituloReporte = $balanceGananciaPerdidaMesTitulo;
		$TituloRango = $this->DesdeHasta;
		$TE=6;//COLSPAN TituloEncabezado EXCEL
		$TF=8;//COLSPAN Fecha EXCEL
		$TH=8;//COLSPAN Hora EXCEL
		$TR1=8;//COLSPAN TituloReporte EXCEL
		$TR2=8;//COLSPAN TituloRango EXCEL
		$logo = "";
		$this->crear_encabezado($logo,$TituloEmpresa,$TituloRif,$TituloEncabezado,$TituloReporte,$TituloRango,$TE,$TF,$TH,$TR1,$TR2);
					
		$this->SetXY(1,45);	
   		//$this->Ln(5);	             

      /*  $campos = array ('Codigo','Nombre de Cuenta','Saldo Anterior','Debe','Haber','Saldo Actual');
		$Alinear = array('L','L','R','R','R','R');
		$Bordes =array('B','B','B','B','B','B');
		$Ancho = array ('15','70','30','30','30','30');
        $TamañoLetra=array ('8','8','8','8','8','8');
		$TipoLetra=array ('B','B','B','B','B','B');
        $this->enc_detallePre($campos,$Ancho,5,$TamañoLetra,$TipoLetra,$Alinear,$Bordes);*/
	}
}

//Creación del objeto de la clase heredada
$pdf=new PDF();
$pdf->AliasNbPages();
$pdf->ExceloPdf = $ExceloPdf;
$pdf->nameExcel = "GanaciayPerdidasporNivelesMes";
$pdf->DesdeHasta = " Desde ".obFecha($cDesde1)." Hasta ".obFecha($cHasta1);
$pdf->AddPage();
		$primera = true;
$TotalSaldoAnterior = 0;
$TotalDebe = 0;
$TotalHaber = 0;
$TotalSaldoActual = 0; 


if($Nivel == 0){
	$long = 1;
}elseif($Nivel == 1){
	$long = 3;
}elseif($Nivel == 2){
    $long = 6;
}elseif($Nivel == 3){
    $long = 9;
}elseif($Nivel == 4){
	$long = 13;
}

// VERIFICA VALORES DE CONFIGURACION (Consulta el Pais del sistema)
$queryConfig403 = "SELECT valor FROM ".$_SESSION['bdEmpresa'].".pg_configuracion_empresa config_emp
	INNER JOIN ".$_SESSION['bdEmpresa'].".pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = 1";
$rsConfig403 =  EjecutarExec($con,$queryConfig403) or die($queryConfig403." " .mysql_error()); 
$rowConfig403 = ObtenerFetch($rsConfig403);
$valor = $rowConfig403['0'];// 1 = Venezuela, 2 = Panama, 3 = Puerto Rico

if ($valor == 1) { //1 = Venezuela
	$cuenta1 = '3.1.01.01.002';
	$condicion = "and (trim(codigo)='4' or trim(codigo)='5' or trim(codigo)='8' or codigo ='9')";
}

if ($valor == 2 || $valor == 3) { //2 = Panama, 3 = Puerto Rico
	$cuenta1 = '3.1.01.01.001';
	$condicion = "and (trim(codigo)='4' or trim(codigo)='5' or trim(codigo)='6')";
}


$SqlStr=" select sum(a.saldo_ant+a.debe-a.haber)";
$SqlStr.=" from cuentageneral a where Deshabilitar = '0'  and usuario = '$sUsuario' 
and (trim(codigo)='4' or trim(codigo)='5') ";

$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
if ( NumeroFilas($exc)>0){
				$UtilidadVenta = ObtenerResultado($exc,1,$iFila); 
}			

$SqlStr=" select sum(a.saldo_ant+a.debe-a.haber)";
$SqlStr.=" from cuentageneral a where Deshabilitar = '0'  and usuario = '$sUsuario' 
and (trim(codigo)='4' or trim(codigo)='5' or trim(codigo)='8') ";

$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
if(NumeroFilas($exc)>0){
	$UtilidadOperaciones = ObtenerResultado($exc,1,$iFila); 
}

//if ($cDesde3 == 'NO'){
$SqlStr=" select a.codigo, a.descripcion,a.saldo_ant as saldo_ant, a.debe as debe, a.haber ";
//}else{ 
 //   $SqlStr=" select a.codigo, a.descripcion,a.saldo_ant as saldo_ant, a.debe + a.debe_cierr  as debe, a.haber + a.haber_cierr as haber ";
//} 
	$SqlStr.=" from cuentageneral a where 
	Deshabilitar = '0' and (a.saldo_ant <> 0 or a.debe <> 0 or a.haber <> 0) 
	and  CHAR_LENGTH(a.codigo) <= $long	and usuario = '$sUsuario' and codigo >=4
order by codigo";
$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);

if(NumeroFilas($exc)>0){
	$iFila = -1;
	$pdf->Ln(3); 
	$primera = true;
	
	while ($row = ObtenerFetch($exc)){
		$iFila++;

		if(substr_count(ObtenerResultado($exc,1,$iFila), '.') <= $Nivel){
			$codigo = trim(ObtenerResultado($exc,1,$iFila)) ; 
			$Veces = substr_count($codigo, '.');
			$descripcion = trim(ObtenerResultado($exc,2,$iFila)) ; 
			$Sal_ant = number_format(trim(ObtenerResultado($exc,3,$iFila)), 2); 
			$Debe = number_format(trim(ObtenerResultado($exc,4,$iFila)),2); 
			$Haber = number_format(trim(ObtenerResultado($exc,5,$iFila)),2) ; 
			$SaldoActual1 = bcadd(ObtenerResultado($exc,3,$iFila),ObtenerResultado($exc,4,$iFila),2);
			$SaldoActual = bcsub($SaldoActual1,ObtenerResultado($exc,5,$iFila),2);
			$iFilaSuma=$iFila+1;
			$codigoAfter = trim(ObtenerResultado($exc,1,$iFilaSuma)); 
			$VecesAfter = substr_count($codigoAfter, '.');
																
			if($VecesAfter > $Veces){						
				if(substr($cuenta1,0,strlen(trim($codigo)))==trim($codigo)){
					$montoTotal[$Veces] = bcadd($SaldoActual,$Utilidad,2);
				}else{
					$montoTotal[$Veces] = $SaldoActual;								
				}
				$codigoTotal[$Veces] = $codigo; 
				$descripcionTotal[$Veces] = $descripcion;																														
			}elseif($VecesAfter < $Veces){
				$varbor = 'Bor4';
				$$varbor = "B";
			}
			
			$Alinear1 = array('L','L','R','R','R','R');
			$Ancho1 = array ('15','70','20','20','20','20','20');
			$TamañoLetra1=array ('6','7','7','7','7','7');
			$MaxLon1=array (0,45,0,0,0,0);
					
			if($Veces == $Nivel){
				$pdf->SetX(1); 
						  	  
				$Sal4 = parentesis($SaldoActual);			
				$bordes1 = array ('','',$Bor4,$Bor3,$Bor2,$Bor1,$Bor0); 
				$campos1 = array ($codigo,$descripcion,$Sal4,"","","","");
					
				$pdf->enc_detallePre($campos1,$Ancho1,4,$TamañoLetra1,'',$Alinear1,$bordes1,$MaxLon1);
						//SOLO  PARA LA UTILIDAD NO ESTOY TOMANDO NUCA EN CUENTA EL ASIENTO DE CIERRE SIEMPRE LO ESTOY APLICANDO DIRECTO 
				$colocar = 3;									
																						
				for($i = $Veces-1;$i >= $VecesAfter;$i--){
					$Sal4="";
					$Sal3="";
					$Sal2="";
					$Sal1="";
					$Sal0="";
					$Bor4="";
					$Bor3="";
					$Bor2="";
					$Bor1="";
					$Bor0="";						
					$pdf->SetX(1);   
					$TipoLetra = array ('B','B','B','B','B','B','B');
					$sal = 'Sal'.$colocar;																				
					$$sal =  parentesis($montoTotal[$i]);							
					$campos = array ("","TOTAL: ".$descripcionTotal[$i],$Sal4,$Sal3,$Sal2,$Sal1,$Sal0);
					$colocar2 = $colocar +1;  
					$varbor = 'Bor'.$colocar2;
					$$varbor =  "T";
					
					if($i ==  $VecesAfter){							     
								/*	if(strlen(trim($codigoTotal[$i])) == 1){
										$varbor = 'Bor'.$colocar;
										$$varbor =  "B";
									}*/
					}
					
					$bordes1 = array('','',$Bor4,$Bor3,$Bor2,$Bor1,$Bor0); 
					
					$pdf->enc_detallePre($campos,$Ancho1,6,$TamañoLetra1,$TipoLetra,$Alinear1,$bordes1,$MaxLon1);
					
					if(strlen(trim($codigoTotal[$i])) == 1){    
						$Bor4="";
						$Bor3="";
						$Bor2="";
						$Bor1="";
						$Bor0="";
						
						if(trim($codigoTotal[$i]) == '5' || trim($codigoTotal[$i]) == '8'){		
							$varbor = 'Bor'.$colocar;
							$$varbor =  "B";
						} 	
						
						$sal = 'Sal'.$colocar;										
						$campos1 = array ("","","","","","","");
						$bordes1 = array('','',$Bor4,$Bor3,$Bor2,$Bor1,$Bor0); 
						$pdf->SetX(1);  
						
						$pdf->enc_detallePre($campos1,$Ancho1,3,$TamañoLetra1,$TipoLetra,$Alinear1,$bordes1,$MaxLon1);
											
						if(trim($codigoTotal[$i]) == '5'){
							$$sal =  parentesis($UtilidadVenta);
							$campos1 = array ("",$utilidadBrutaVenta.":",$Sal4,$Sal3,$Sal2,$Sal1,$Sal0);
							$bordes1 = array('','','','','','',''); 
							$pdf->SetX(1);  
							
							$pdf->enc_detallePre($campos1,$Ancho1,7,$TamañoLetra1,$TipoLetra,$Alinear1,$bordes1,$MaxLon1);
						}	
										
						if(trim($codigoTotal[$i]) == '8'){
							$$sal =  parentesis($UtilidadOperaciones);
							$campos1 = array ("",$utilidadNetaOperaciones.":",$Sal4,$Sal3,$Sal2,$Sal1,$Sal0);
							$bordes1 = array('','','','','','',''); 
							$pdf->SetX(1);  
							
							$pdf->enc_detallePre($campos1,$Ancho1,7,$TamañoLetra1,$TipoLetra,$Alinear1,$bordes1,$MaxLon1);
						}												
					}

					$colocar--;	
				}						
			}
			
			if($Veces < $Nivel){
				$pdf->SetX(1); 
				$bordes1 = "";						
				$campos1 = array ($codigo,$descripcion,"","","","","");
				
				$pdf->enc_detallePre($campos1,$Ancho1,3,$TamañoLetra1,'',$Alinear1,$bordes1,$MaxLon1);						
			}					
		}//if(substr_count(ObtenerResultado($exc,1,$iFila), '.') <= $Nivel){		
		$Bor4="";
		$Bor3="";
		$Bor2="";
		$Bor1="";
		$Bor0="";			
	}	
		//***************************************************
		//TUVE QUE REPETIRLO POR QUE NO AGARRA EL ULTIMO
		//***************************************************
	$iFila++;
	$codigo = trim(ObtenerResultado($exc,1,$iFila)) ; 
	$Veces = substr_count($codigo, '.');
	$descripcion = trim(ObtenerResultado($exc,2,$iFila)) ; 
	$Sal_ant = number_format(trim(ObtenerResultado($exc,3,$iFila)), 2); 
	$Debe = number_format(trim(ObtenerResultado($exc,4,$iFila)),2); 
	$Haber = number_format(trim(ObtenerResultado($exc,5,$iFila)),2) ; 
	$SaldoActual1 = bcadd(ObtenerResultado($exc,3,$iFila),ObtenerResultado($exc,4,$iFila),2);
	$SaldoActual = bcsub($SaldoActual1,ObtenerResultado($exc,5,$iFila),2);
		$iFilaSuma=$iFila+1;
	$codigoAfter = trim(ObtenerResultado($exc,1,$iFilaSuma)); 
	$VecesAfter = substr_count($codigoAfter, '.');
	
	if($VecesAfter > $Veces){
		$montoTotal[$Veces] = $SaldoActual;
		$codigoTotal[$Veces] = $codigo; 
		$descripcionTotal[$Veces] = $descripcion;
	}elseif($VecesAfter < $Veces){
			$varbor = 'Bor4';
			$$varbor = "B";
	}
	
	$Alinear1 = array('L','L','R','R','R','R');
	$Ancho1 = array ('15','70','20','20','20','20','20');
	$TamañoLetra1=array ('6','7','7','7','7','7');
	$MaxLon1=array (0,45,0,0,0,0);
					
	if($Veces == $Nivel){
		$pdf->SetX(1);   
		$Sal4 = parentesis($SaldoActual);											
		$bordes1 = array ('','',$Bor4,$Bor3,$Bor2,$Bor1,$Bor0); 							
		$campos1 = array ($codigo,$descripcion,$Sal4,"","","","");
					
		$pdf->enc_detallePre($campos1,$Ancho1,4,$TamañoLetra1,'',$Alinear1,$bordes1,$MaxLon1);
												
		$colocar = 3;
		
		for($i = $Veces-1;$i >= $VecesAfter;$i--){
			$Sal4="";
			$Sal3="";
			$Sal2="";
			$Sal1="";
			$Sal0="";
			$Bor4="";
			$Bor3="";
			$Bor2="";
			$Bor1="";
			$Bor0="";						
			$pdf->SetX(1);   
			$TipoLetra = array ('B','B','B','B','B','B','B');
			$sal = 'Sal'.$colocar;
			$$sal =  parentesis($montoTotal[$i]);
			$campos = array ("","TOTAL: ".$descripcionTotal[$i],$Sal4,$Sal3,$Sal2,$Sal1,$Sal0);
			$colocar2 = $colocar +1;  
			$varbor = 'Bor'.$colocar2;
			$$varbor =  "T";
			
			if($i ==  $VecesAfter){							     
				if(strlen(trim($codigoTotal[$i])) == 1){
									/*	$varbor = 'Bor'.$colocar;
										$$varbor =  "B";*/
				}
			}
			
			$bordes1 = array('','',$Bor4,$Bor3,$Bor2,$Bor1,$Bor0); 
			
			$pdf->enc_detallePre($campos,$Ancho1,7,$TamañoLetra1,$TipoLetra,$Alinear1,$bordes1,$MaxLon1);
			
			if(strlen(trim($codigoTotal[$i])) == 1){    
				$Bor4="";
				$Bor3="";
				$Bor2="";
				$Bor1="";
				$Bor0="";	
				$varbor = 'Bor'.$colocar;
				$$varbor =  "B";
				$campos1 = array ("","","","","","","");
				$bordes1 = array('','',$Bor4,$Bor3,$Bor2,$Bor1,$Bor0); 
				$pdf->SetX(1);  
				
				$pdf->enc_detallePre($campos1,$Ancho1,3,$TamañoLetra1,$TipoLetra,$Alinear1,$bordes1,$MaxLon1);				
			}
			$colocar--;	
		}
	}
	
	if($Veces < $Nivel){
		$pdf->SetX(1); 
		$bordes1 = "";						
		$campos1 = array ($codigo,$descripcion,"","","","","");
	
		$pdf->enc_detallePre($campos1,$Ancho1,3,$TamañoLetra1,'',$Alinear1,$bordes1,$MaxLon1);		
	}
										
	//	if ($cDesde3 == 'NO'){
			 	$SqlStr=" select sum(a.saldo_ant+a.debe-a.haber)";
		/*}else{ 
				$SqlStr=" select sum(a.saldo_ant +(a.debe + a.debe_cierr)-(a.haber + a.haber_cierr))";
		} */
	$SqlStr.=" from cuentageneral a where Deshabilitar = '0'  and usuario = '$sUsuario' $condicion";
	$varbor = 'Bor'.$colocar2;
	$$varbor =  "";
	$exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
		  
	if(NumeroFilas($exc)>0){
		$iFila=0;
		$SaldoActual = ObtenerResultado($exc,1,$iFila); 
		$colocar++; 	
		$sal = 'Sal'.$colocar;
		$pdf->SetX(1);
		$$sal =  parentesis($SaldoActual);
		$campos = array ("",$utilidadPerdidaEjercicio.": ",$Sal4,$Sal3,$Sal2,$Sal1,$Sal0);
		$bordes1 = array('','',$Bor4,$Bor3,$Bor2,$Bor1,$Bor0); 
		
		$pdf->enc_detallePre($campos,$Ancho1,10,$TamañoLetra1,$TipoLetra,$Alinear1,$bordes1,$MaxLon1);
		
		$pdf->SetX(1);
		$campos = array ("","","","","","","");
		
		$pdf->enc_detallePre($campos,$Ancho1,3,$TamañoLetra1,$TipoLetra,$Alinear1,$bordes1,$MaxLon1);						
	}			
}

//auditoria
auditoria('consulta','cuentageneral',$sCampos,'consulta estado de ganancias/perdidas mensual, rango de fechas: '.$cDesde2." - ".$cHasta2);
//fin auditoria

$pdf->Output();
?>