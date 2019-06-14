<?php session_start();

require('fpdf.php');
include_once("FuncionesPHP.php");
/*ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);*/

class PDF extends FPDF{
//Cabecera de página
	function Header(){   
		$con = ConectarBD();
		
		//$NombreEmpresa =  substr ($_SESSION["sDesBasedeDatos"], 0, -1);
		$SqlEmp = "SELECT id_empresa, nombre_empresa,logo_empresa,rif,web FROM sipre_automotriz.pg_empresa WHERE id_empresa = 1";
		//echo $SqlEmp."<br>";
		$queryEmp = mysql_query($SqlEmp);  
		if(!$queryEmp){ die("Error: ".mysql_error().__LINE__); } 
		$rowsEmp = mysql_fetch_array($queryEmp);
		
		//llamando a la funcion encabezado del fpdf
		$idEmpresa = $rowsEmp['id_empresa'];
		$nombreEmpresa = $rowsEmp['nombre_empresa'];
		$logoEmpresa = $rowsEmp['logo_empresa'];//logo del reporte
		$rif = $rowsEmp['rif'];
		$web = $rowsEmp['web'];
		
		$fecha = date("d/m/Y");
		$hora = date("h:i. A");


		switch($_GET['orientacion']){
			case 'P': //LISTADO DE ACTIVO
				//FILA PARA EL LOGO NOMBRE EMPRESA Y FECHA
				$this->SetFont('Arial','B',8);
				$this->Image("../".$logoEmpresa,10,10,75);
				$this->Cell(50,8,'	',0,0);
				$this->SetFont('Arial','B',8);
				$this->Cell(350,8,$nombreEmpresa,0,0);
				$this->SetFont('Arial','',6);
				$this->Cell(150,8,'Fecha: '.$fecha,0,0,'R');
				
				$this->Ln(10);
				//FILA PARA RIF Y HORA
				$this->Cell(50,8,'',0,0);
				$this->SetFont('Arial','B',8);
				$this->Cell(350,8,$rif,0,0);
				$this->SetFont('Arial','',6);
				$this->Cell(150,8,'Hora: '.$hora,0,0,'R');
				
				$this->Ln(10);
				//FILA PARA DIRECCIONES WEB
				$this->Cell(50,8,'',0,0);
				$this->SetFont('Arial','B',8);
				$this->Cell(500,8,$web,0,0);
				
				$this->Ln(15);
				//TITULO
				$this->Ln(15);
				$this->Cell(100,8,'',0,0);
				$this->SetFont('Arial','B',10);
				$this->Cell(380,8,"Listado Activo Fijo",0,0,'C');
				$this->Ln(15);
					break;
					
			case 'L': //DEPRECIACION ACTIVO
				//FILA PARA EL LOGO NOMBRE EMPRESA Y FECHA
				$this->SetFont('Arial','B',10);
				$this->Image("../".$logoEmpresa,15,17,80);
				$this->Cell(100,8,'	',0,0);
				$this->SetFont('Arial','B',10);
				$this->Cell(100,8,$nombreEmpresa,0,0);
				$this->SetFont('Arial','',7);
				$this->Cell(580,8,'Fecha: '.$fecha,0,0,'R');
				
				$this->Ln(10);
				//FILA PARA RIF Y HORA
				$this->Cell(100,8,'',0,0);
				$this->SetFont('Arial','B',10);
				$this->Cell(100,8,$rif,0,0);
				$this->SetFont('Arial','',7);
				$this->Cell(580,8,'Hora: '.$hora,0,0,'R');
				
				$this->Ln(10);
				//FILA PARA DIRECCIONES WEB
				$this->Cell(100,8,'',0,0);
				$this->SetFont('Arial','B',10);
				$this->Cell(100,8,$web,0,0);
				
				$this->Ln(15);
				//TITULO
				$this->Cell(100,8,'',0,0);
				$this->SetFont('Arial','B',15);
				$this->Cell(680,8,utf8_decode("Depreciación Activo Fijo"),0,0,'C');
				$this->Ln(15);
				
				$sqlFech = "SELECT DATE_FORMAT(DATE_SUB(Fechacomp_cierr, INTERVAL 1 YEAR),'%d-%m-%Y') AS fecha_cierre_periodo_anterior,
							  DATE_FORMAT(Fechacomp_cierr,'%d-%m-%Y') AS fecha_cierre_periodo_actual
							FROM sipre_contabilidad.parametros;";
				$queryFec= mysql_query($sqlFech);
					if(!$queryFec){ die("Error: ".mysql_error().__LINE__); } 
				$rowsFech = mysql_fetch_array($queryFec);
				
				$camposFech = array ('','Fecha de Cierre Periodo Anterior: ',$rowsFech['fecha_cierre_periodo_anterior'],'Fecha de Cierre Periodo Actual:',$rowsFech['fecha_cierre_periodo_actual']); 
				$AnchoFech = array ('427','132','50','130','50');
				$AlinearFech = array ('','R','L','R','L');
				$BordesFech = array ('0','0','0','0','0','0');
				$tipoLetraFech = array('','B','','B','');
				$this->SetFont('Arial','B',10);
				$this->enc_detalle($camposFech,$AnchoFech,10,'8',$tipoLetraFech,$AlinearFech,$BordesFech);
				$campos = array ('');
				$Ancho = array ('190');
				$this->Ln(15);//Separacion entre fecha y tabla
					break;
		}
		   
	}

//Pie de página
	function Footer(){
		//Posición: a 1,5 cm del final
		$this->SetY(-15);
		//Arial italic 8
		$this->SetFont('Arial','I',8);
		//Número de página
		$this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
		
		//CONSULTO PARA SABER EL NOMBRE USUARIO 
		$sqlUser = "SELECT * FROM sipre_co_config.usuario WHERe nombre = '".$_SESSION["UsuarioSistema"]."' ;";
		$queryUser = mysql_query($sqlUser);  
			if(!$queryUser){ die("Error: ".mysql_error().__LINE__); } 
		$rowsUser = mysql_fetch_array($queryUser);
	
		$this->Cell(0,10,'Generado por: '.$rowsUser['nombusuario'] ,0,0,'R');
	
	}
}

//CREO EL OBJETO SEGUN LA ORIENTACION 
	switch($_GET['orientacion']){
		case 'P': //LISTADO DE ACTIVO
			//Creación del objeto de la clase heredada
			$pdf= new PDF('P','pt');
			$pdf->AliasNbPages();
			$pdf->AddPage();
			$pdf->SetFont('Arial','',10);
				break;
				
		case 'L'://DEPRECIACION ACTIVO
			//Creación del objeto de la clase heredada
			$pdf= new PDF('L','pt');
			$pdf->AliasNbPages();
			$pdf->AddPage();
			$pdf->SetFont('Arial','',10);
	}

	switch($_GET['orientacion']){
		case 'P'://LISTADO DE ACTIVO
		
		 	$campos2 = array ('ID Activo',utf8_decode('Código Cuenta'),utf8_decode('Descripción'),'Retirado'); 
			$Ancho2 = array ('100','100','300','50');
			$Alinear2 = array ('C','C','C','C');
			$Bordes2 = array ('1','1','1','1');
			$tipoLetra = array('B','B','B');
			$pdf->SetFont('Arial','B',8);
			$pdf->enc_detalle($campos2,$Ancho2,10,15,$tipoLetra,$Alinear2,$Bordes2);
			$campos = array ('');
			$Ancho = array ('190');
			
			$sqlListAct = "SELECT * FROM sipre_contabilidad.deprecactivos ORDER BY Codigo ASC;";
					$queryListAct = mysql_query($sqlListAct);
			if(!$queryListAct){ die("Erro de conexio".mysql_error().__LINE__); } 
			
			$totalRegistros = mysql_num_rows($queryListAct);
			while ($rows = mysql_fetch_array($queryListAct)){
				$campos2 = array ($rows['Codigo'],$rows['Tipo'],$rows['Descripcion'],$rows['']); 
				$Ancho2 = array ('100','100','300','50');
				$Alinear2 = array ('C','C','L','C');
				$Bordes2 = array ('1','1','1','1');
				//$tipoLetra = array('B','B','B','B','B');
				$pdf->SetFont('Arial','B',8);
				$pdf->enc_detalle($campos2,$Ancho2,10,15,'',$Alinear2,$Bordes2);
				$campos = array ('');
				$Ancho = array ('190');
			}
				$camposT = array ('TOTAL ACTIVOS FIJO:',$totalRegistros); 
				$AnchoT = array ('450','100');
				$AlinearT = array ('R','R');
				$BordesT = array ('1','1');
				$tipoLetraT = array('B','B');
				$pdf->SetFont('Arial','B',8);
				$pdf->enc_detalle($camposT,$AnchoT,10,15,$tipoLetraT,$AlinearT,$BordesT);
				$campos = array ('');
				$Ancho = array ('190');
				break; //fin listadio activo
				
		case 'L': //DEPRECIACION ACTIVO
				$sqlDepAct= "SELECT deprecactivos.Codigo, tipo, tipoactivo.Descripcion AS  tipo_Activo_Descripcion,
			DATE_FORMAT(Fecha,'%d-%m-%Y') AS fecha_compra, anomes, deprecactivos.Descripcion,
			deprecactivos.MesesDepre AS Vida_Util,
			  #CALCULO DE LA VUP_ANTERIOR
						  (SELECT COUNT(a.id) FROM sipre_contabilidad.con_depreciacion a
						  WHERE a.anomes <= DATE_FORMAT(
							(SELECT DATE_SUB(Fechacomp_cierr, INTERVAL 1 YEAR) FROM sipre_contabilidad.parametros  limit 1), '%Y%m')
							AND a.anomes >= DATE_FORMAT(
							  (SELECT DATE_SUB(Fechacomp_cierr, INTERVAL 2 YEAR) FROM sipre_contabilidad.parametros limit 1), '%Y%m')
							  AND a.codigoactivos = deprecactivos.Codigo) AS VUP_ANTERIOR,
			  #CALCULO DE LA VUP_ACTUAL
						(SELECT COUNT(b.anomes) FROM sipre_contabilidad.con_depreciacion b
						  WHERE b.anomes >= DATE_FORMAT(FechaDepre,'%Y%m')
						 AND b.anomes <= DATE_FORMAT(
						   (SELECT Fechacomp_cierr FROM sipre_contabilidad.parametros limit 1), '%Y%m')
							AND b.codigoactivos = deprecactivos.Codigo) AS VUP_ACTUAL,
			  #CALCULO DE LA VU_RESTANTE = (Vida_Util - VUP_ANTERIOR - VIDA UTIL P ACTUAL)
					   (deprecactivos.MesesDepre - (SELECT COUNT(b.anomes) FROM sipre_contabilidad.con_depreciacion b
										 WHERE b.anomes >= DATE_FORMAT(FechaDepre,'%Y%m')
										 AND b.anomes <= DATE_FORMAT(
										   (SELECT Fechacomp_cierr FROM sipre_contabilidad.parametros limit 1), '%Y%m')
										 AND b.codigoactivos = deprecactivos.Codigo)) AS VU_RESTANTE,
			 #CALCULO DE GSTO ANUAL
						IF(DATE_FORMAT(Fecha, '%Y') <
					  (SELECT DATE_FORMAT(Fechacomp_cierr, '%Y')
						FROM sipre_contabilidad.parametros LIMIT 1), 12,COUNT(SUBSTRING(anomes,5))) AS GSTO_ANUAL,
						CompAdquisicion AS COSTO_HIST,
						round((DepreMensual), 2) AS DEP_MENSUAL,
			 #CALCULO DE LA DEP ACUMULADA ANTERIOR = (COSTO_HIST / Vida_Util * VUP_ANTERIOR)
						ROUND((CompAdquisicion / deprecactivos.MesesDepre *
						(SELECT COUNT(a.id) FROM sipre_contabilidad.con_depreciacion a
						  WHERE a.anomes <= DATE_FORMAT(
							(SELECT DATE_SUB(Fechacomp_cierr, INTERVAL 1 YEAR) FROM sipre_contabilidad.parametros limit 1), '%Y%m')
								AND a.anomes >= DATE_FORMAT(
								(SELECT DATE_SUB(Fechacomp_cierr, INTERVAL 2 YEAR) FROM sipre_contabilidad.parametros limit 1), '%Y%m')
									AND a.codigoactivos = deprecactivos.Codigo)),2) AS DEP_ACUML_ANT,
			 #CALCULO DEL PERIODO = (GSTO_ANUAL * DEP_MENSUAL)
					  ROUND(((IF(DATE_FORMAT(Fecha, '%Y') < (
						SELECT DATE_FORMAT(Fechacomp_cierr, '%Y')
						  FROM sipre_contabilidad.parametros LIMIT 1), 12,COUNT(SUBSTRING(anomes,5))))*DepreMensual),2) AS DE_PERIODO
					FROM sipre_contabilidad.deprecactivos
						LEFT JOIN sipre_contabilidad.con_depreciacion ON deprecactivos.Codigo = con_depreciacion.codigoactivos
						LEFT JOIN  sipre_contabilidad.tipoactivo ON deprecactivos.tipo = tipoactivo.Codigo
						WHERE estatus = 0
					AND SUBSTRING(anomes,1,4) <= YEAR((SELECT Fechacomp_cierr FROM sipre_contabilidad.parametros LIMIT 1))
					AND SUBSTRING(anomes,5) <= MONTH((SELECT Fechacomp_cierr FROM sipre_contabilidad.parametros LIMIT 1))
					GROUP BY deprecactivos.Codigo ORDER BY deprecactivos.tipo, anomes, deprecactivos.Codigo ASC;";
		$querDepAct = mysql_query($sqlDepAct);
		if(!$querDepAct){ die("Erro de conexio".mysql_error().__LINE__); } 
		
		$totalRegistros = mysql_num_rows($querDepAct);
		
		$cont = 0;
		
		$cabecera1 = array();
		$pie = array();
		
		/*$totalCostoHistorico = 0;
		$totalDepAcumAnt = 0;*/
		
		while ($rows= mysql_fetch_array($querDepAct)){
			
			$cont ++;
			if($rows['DEP_ACUML_ANT'] == "" || $rows['DEP_ACUML_ANT'] == NULL){
				$DEP_ACUML_ANT= 0.00;	
			} else {
				$DEP_ACUML_ANT = $rows['DEP_ACUML_ANT'];
			}
			
			//CALCULO DE LA DEP_ACUMULADA
			$DEP_ACUMULADA = ($DEP_ACUML_ANT + $rows['DE_PERIODO']);
				
			//CALCULO PARA EL V_NETO
			$V_NETO = ($rows['COSTO_HIST'] - $DEP_ACUML_ANT - $rows['DE_PERIODO']);		
				
			if(!empty($pie) && end($pie) != $rows['tipo']){
				$camposP = array('TOTAL: ', $totalCostoHistorico,'',$totalDepAcumAnt,$totalDePreiodo,'', $totaValorNEto);
				$AnchoP = array('420','55','70','75','55','70','45');
				$AlinearP = array('R','R','C','R','R','C','R');
				$BordesP = array('1','1','1','1','1','1','1');
				$tipoLetra = array('B','B','B','B','B','B','B');
				$pdf->SetFont('Arial','B',7);
				$pdf->enc_detalle($camposP,$AnchoP,15,'8',$tipoLetra,$AlinearP,$BordesP);
				$campos = array ('');
				$Ancho = array ('190');
				
				$totalCostoHistorico = 0;
				$totalDepAcumAnt = 0;
				$totalDePreiodo = 0;
				$totaValorNEto = 0;
				
			}	
			
			//SUMA Y ASIGNA DE TOTALES
			$totalCostoHistorico += round($rows['COSTO_HIST'], 2); 
			$totalDepAcumAnt += round($DEP_ACUML_ANT,2);
			$totalDePreiodo += round($rows['DE_PERIODO'],2);
			$totaValorNEto += round($V_NETO,2);
			
			$pie[] = $rows['tipo'];
			
			if(!in_array($rows['tipo'],$cabecera1)){
				$cabecera1[] = $rows['tipo'];					

				$campos1 = array ('Codigo Tipo de activo:',$rows['tipo'],'Descripcrion:',$rows['tipo_Activo_Descripcion']); 
				$Ancho1 = array ('90','50','60','590');
				$Alinear1 = array ('L','L','L','L');
				$Bordes1 = array ('0','0','0','0');
				$tipoLetra = array('B','','B','');
				$pdf->SetFont('Arial','B',7);
				$pdf->Ln(20);
				$pdf->enc_detalle($campos1,$Ancho1,15,'8',$tipoLetra,$Alinear1,$Bordes1);
				$campos = array ('');
				$Ancho = array ('190');
				
				$campos2 = array ('id','Fecha Compra','Vida Util','VU P Anteior','VU P actual','VU Restante','Gto Anual', 'Costo Hist','DEP Mensual',
				'DEP acumul. Ant','DEP Periodo','DPE Acumulada	
				','V Neto'); 
				$Ancho2 = array ('30','60','70','75','70','70','45','55','70','75','55','70','45'); /*-40*/
				$Alinear2 = array ('C','C','C','C','C','C','C','C','C','C','C','C','C');
				$Bordes2 = array ('1','1','1','1','1','1','1','1','1','1','1','1','1');
				$tipoLetra = array('B','B','B','B','B','B','B','B','B','B','B','B','B');
				$pdf->SetFont('Arial','B',7);
				$pdf->enc_detalle($campos2,$Ancho2,15,'8',$tipoLetra,$Alinear2,$Bordes2);
				$campos = array ('');
				$Ancho = array ('190');
				
			}
			
		
			$campos22 = array ($rows['Codigo'],$rows['fecha_compra'],convertirMeses($rows['Vida_Util']),convertirMeses($rows['VUP_ANTERIOR']),convertirMeses($rows['VUP_ACTUAL']),convertirMeses($rows['VU_RESTANTE']),
			$rows['GSTO_ANUAL'],round($rows['COSTO_HIST'],2),round($rows['DEP_MENSUAL'],2),round($DEP_ACUML_ANT,2),round($rows['DE_PERIODO'],2), round($DEP_ACUMULADA,2), round($V_NETO,2)); 
			$Ancho22 = array ('30','60','70','75','70','70','45','55','70','75','55','70','45');
			$Alinear22 = array ('C','C','L','L','L','L','C','C','C','C','C','C','C');
			$Bordes22 = array ('1','1','1','1','1','1','1','1','1','1','1','1','1');
			$pdf->SetFont('Arial','B',7);
			$pdf->enc_detalle($campos22,$Ancho22,15,'8','',$Alinear22,$Bordes22);
			$campos = array ('');
			$Ancho = array ('190');
			
			
			//CUANDO SEA EL ULTIMO TIPO DE ACTIVO DE LA CONSULTA
			if($totalRegistros == $cont){
				$camposP = array ('TOTAL: ', $totalCostoHistorico,'',$totalDepAcumAnt,$totalDePreiodo,'', $totaValorNEto);
				$AnchoP = array ('420','55','70','75','55','70','45');
				$AlinearP = array ('R','R','C','R','R','C','R');
				$BordesP = array ('1','1','1','1','1','1','1');
				$tipoLetra = array('B','B','B','B','B','B','B');
				$pdf->SetFont('Arial','B',7);
				$pdf->enc_detalle($camposP,$AnchoP,15,'8',$tipoLetra,$AlinearP,$BordesP);
				$campos = array ('');
				$Ancho = array ('190');
			}	
		
			/*echo "<PRE>";
			print_r($cabecera1);
			echo "<PRE>";*/
	
		}
				break; //fin case depreaciacion	
	}
switch($_GET['orientacion']){
	case 'P': $nombreArchivo = "Listado_activo_fijo.pdf"; break;
	case 'L': $nombreArchivo = "Depreaciacion_activo_fijo.pdf"; break;	
	}
$pdf->Output($nombreArchivo,'I');
?>