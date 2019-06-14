<?php
set_time_limit(0);
require_once ("../../connections/conex.php");
//require_once ("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("2","2","2");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$valFecha[0] = date("m", strtotime("01-".$valCadBusq[1]));
$valFecha[1] = date("Y", strtotime("01-".$valCadBusq[1]));

/*El informe de errores 
error_reporting (E_ALL);
ini_set('display_errors', TRUE);	
ini_set('display_startup_errors', TRUE);*/

function cuentaSabadoDomingo($valFecha){

	$primerDiaMes = date("d",(mktime(0,0,0,$valFecha[0]+1,1,$valFecha[1])+1));
	$ultimoDiaMes = date("d",(mktime(0,0,0,$valFecha[0]+1,1,$valFecha[1])-1));

	$arraySabDom = array();
	for($dia = $primerDiaMes; $dia <= $ultimoDiaMes; $dia++){
		if(date('N', strtotime(date("Y-m-d", mktime(0, 0, 0, $valFecha[0] , $dia, $valFecha[1])))) == 6 || 
		   date('N', strtotime(date("Y-m-d", mktime(0, 0, 0, $valFecha[0] , $dia, $valFecha[1])))) == 7){
			$arraySabDom [] = $SabDom ;
		}
	}

	$sqlDiasFeriados = sprintf("SELECT * FROM pg_fecha_baja WHERE tipo LIKE 'FERIADO'");
	$rsDiasFeriados = mysql_query($sqlDiasFeriados);
	if (!$rsDiasFeriados) return(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$numDiaFeriado = 0;
	while ($rowDiasFeriados = mysql_fetch_array($rsDiasFeriados)) {
		$fechaFeriado = explode("-",$rowDiasFeriados['fecha_baja']);
		if($valFecha[0] == $fechaFeriado[1]){
			$numDiaFeriado++;	
		}
	}
	return (count($arraySabDom) + $numDiaFeriado);
}

function sqlComisionEmpleado($valCadBusq){
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pg_emp.activo = 1");
	
	if($valCadBusq[0] != "" && $valCadBusq[0] != "-1"){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("'%s' = (CASE 
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT id_empresa FROM cj_cc_encabezadofactura encabezado_fact 
					WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
			
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT id_empresa FROM cj_cc_notacredito 
					WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito) 
					
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT id_empresa FROM sa_vale_salida sa_vs
										WHERE sa_vs.id_vale_salida = pg_comision_emp.id_vale_salida) 
                
			WHEN (id_vale_entrada IS NOT NULL) THEN 
				(SELECT id_empresa FROM sa_vale_entrada sa_ve
										WHERE sa_ve.id_vale_entrada = pg_comision_emp.id_vale_entrada)
			END)",$valCadBusq[0]);
		
	}
	if($valCadBusq[1] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("'%s' = (CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT DATE_FORMAT(fechaRegistroFactura, %s) FROM cj_cc_encabezadofactura encabezado_fact 
					WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
			
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT DATE_FORMAT(fechaNotaCredito, %s) FROM cj_cc_notacredito 
					WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito) 
			
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT DATE_FORMAT(fecha_vale, %s) FROM sa_vale_salida sa_vs
					WHERE sa_vs.id_vale_salida = pg_comision_emp.id_vale_salida) 
			
			WHEN (id_vale_entrada IS NOT NULL) THEN 
				(SELECT DATE_FORMAT(fecha_creada, %s) FROM sa_vale_entrada sa_ve
					WHERE sa_ve.id_vale_entrada = pg_comision_emp.id_vale_entrada)
			END)",
		$valCadBusq[1],
		valTpDato("%m-%Y", "date"),
		valTpDato("%m-%Y", "date"),
		valTpDato("%m-%Y", "date"),
		valTpDato("%m-%Y", "date"));
	}
	if($valCadBusq[2] != "" && $valCadBusq[2] != "-1"){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pg_cargo_departamento.id_cargo = %s",
			$valCadBusq[2]);
	}
	if($valCadBusq[3] != "" && $valCadBusq[3] != "-1"){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pg_comision_emp.id_empleado = %s",
			$valCadBusq[3]);
	}
	if($valCadBusq[4] != "" && $valCadBusq[4] != "-1"){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("%s = 
			(CASE 
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
					WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
			
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
					WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
			END)",$valCadBusq[4]);
	}
	
	$sqlComi = sprintf("SELECT	pg_comision_emp.id_comision_empleado,pg_emp.id_empleado,codigo_empleado,
		REPLACE(REPLACE(cedula, 'V-', 'V'),'V','V-')AS cedula,concat_ws(' ',nombre_empleado,apellido) AS nombre_apellido_empleado,
		pg_cargo_departamento.id_departamento,nombre_departamento,nombre_cargo,
		(CASE 
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
					WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
			
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
					WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
		END) AS modulo,
		pg_comision_emp_det.porcentaje_comision,
		SUM(IF(pg_comision_emp.id_nota_credito IS NOT NULL, (-1), 1) * `cantidad` * `precio_venta`) AS total_produccion
	FROM pg_comision_empleado pg_comision_emp
		INNER JOIN pg_comision_empleado_detalle pg_comision_emp_det ON (pg_comision_emp.id_comision_empleado = pg_comision_emp_det.id_comision_empleado)
		INNER JOIN pg_empleado pg_emp ON (pg_comision_emp.id_empleado = pg_emp.id_empleado)
		INNER JOIN pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = pg_comision_emp.id_cargo_departamento
		INNER JOIN pg_departamento ON pg_departamento.id_departamento = pg_cargo_departamento.id_departamento
		INNER JOIN pg_cargo ON pg_cargo.id_cargo = pg_cargo_departamento.id_cargo
		%s
	GROUP BY pg_comision_emp.id_empleado ORDER BY id_departamento,modulo;",$sqlBusq);
	//echo $sqlComi;
	return $sqlComi;
}

function sqlComisionEmpleadoDetalle($valCadBusq, $idEmpleado){
	
	// POR EMPLEADO
	if($valCadBusq[0] != "" && $valCadBusq[0] != "-1"){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("'%s' = (CASE 
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT id_empresa FROM cj_cc_encabezadofactura encabezado_fact 
					WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
			
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT id_empresa FROM cj_cc_notacredito 
					WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito) 
					
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT id_empresa FROM sa_vale_salida sa_vs
										WHERE sa_vs.id_vale_salida = pg_comision_emp.id_vale_salida) 
                
			WHEN (id_vale_entrada IS NOT NULL) THEN 
				(SELECT id_empresa FROM sa_vale_entrada sa_ve
										WHERE sa_ve.id_vale_entrada = pg_comision_emp.id_vale_entrada)
			END)",$valCadBusq[0]);
		
	}
	// POR FECHA
	if($valCadBusq[1] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("'%s' = (CASE
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT DATE_FORMAT(fechaRegistroFactura, %s) FROM cj_cc_encabezadofactura encabezado_fact 
					WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
			
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT DATE_FORMAT(fechaNotaCredito, %s) FROM cj_cc_notacredito 
					WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito) 
			
			WHEN (id_vale_salida IS NOT NULL AND id_vale_entrada IS NULL) THEN
				(SELECT DATE_FORMAT(fecha_vale, %s) FROM sa_vale_salida sa_vs
					WHERE sa_vs.id_vale_salida = pg_comision_emp.id_vale_salida) 
			
			WHEN (id_vale_entrada IS NOT NULL) THEN 
				(SELECT DATE_FORMAT(fecha_creada, %s) FROM sa_vale_entrada sa_ve
					WHERE sa_ve.id_vale_entrada = pg_comision_emp.id_vale_entrada)
			END)",
		$valCadBusq[1],
		valTpDato("%m-%Y", "date"),
		valTpDato("%m-%Y", "date"),
		valTpDato("%m-%Y", "date"),
		valTpDato("%m-%Y", "date"));
	}
	// POR CARGO
	if($valCadBusq[2] != "" && $valCadBusq[2] != "-1"){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pg_cargo_departamento.id_cargo = %s",
			$valCadBusq[2]);
	}
	
	// POR EMPLEADO
	if($idEmpleado != "" ){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pg_comision_emp.id_empleado = %s",
			$idEmpleado);
	}
	
	// POR DEPATAMENTO
	if($valCadBusq[4] != "" && $valCadBusq[4] != "-1"){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("%s = 
			(CASE 
			WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
				(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
					WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
			
			WHEN (id_nota_credito IS NOT NULL) THEN
				(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
					WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
			END)",$valCadBusq[4]);
	}
	
	$sqlComiDet = sprintf("SELECT pg_comision_emp.id_comision_empleado,pg_emp.id_empleado,
		concat_ws(' ',nombre_empleado,apellido) AS nombre_apellido_empleado,
			(CASE 
					WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
						(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
							WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
							
					WHEN (id_nota_credito IS NOT NULL) THEN
						(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
							WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
				END) AS modulo,
			(CASE 
				WHEN ( (CASE 
					WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
						(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
							WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
							
					WHEN (id_nota_credito IS NOT NULL) THEN
						(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
							WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
				END) = 0 AND id_tempario IS NULL AND id_det_fact_tot IS NULL AND id_articulo IS NOT NULL AND id_unidad_fisica IS NULL) THEN
					'VENTA DE RESPUESTO MOSTRADOR'
				WHEN ( (CASE 
					WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
						(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
							WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
							
					WHEN (id_nota_credito IS NOT NULL) THEN
						(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
							WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
				END) = 1 AND id_tempario IS NOT NULL AND id_det_fact_tot IS NULL AND id_articulo IS NOT NULL AND id_unidad_fisica IS NULL) THEN
					'VENTA DE RESPUESTO Y SERVICIO TALLER'
                    
				WHEN ( (CASE 
					WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
						(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
							WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
							
					WHEN (id_nota_credito IS NOT NULL) THEN
						(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
							WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
				END) = 1 AND id_tempario IS NULL AND id_det_fact_tot IS NULL AND id_articulo IS NOT NULL AND id_unidad_fisica IS NULL) THEN
					'VENTA DE RESPUESTO POR TALLER'
                    
				WHEN ( (CASE 
					WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
						(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
							WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
							
					WHEN (id_nota_credito IS NOT NULL) THEN
						(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
							WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
				END) = 1 AND id_tempario IS NOT NULL AND id_det_fact_tot IS NULL AND id_articulo IS NULL AND id_unidad_fisica IS NULL) THEN
					'SERVICIO MANO DE OBRA TALLER'
                    
               WHEN ( (CASE 
					WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
						(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
							WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
							
					WHEN (id_nota_credito IS NOT NULL) THEN
						(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
							WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
				END) = 1 AND id_tempario IS NULL AND id_det_fact_tot IS NOT NULL AND id_articulo IS NULL AND id_unidad_fisica IS NULL) THEN
					'VENTA DE SERVICIO '
                    
				WHEN ( (CASE 
					WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
						(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
							WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
							
					WHEN (id_nota_credito IS NOT NULL) THEN
						(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
							WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
				END) = 2 AND id_articulo IS NULL AND id_tempario IS NULL) THEN
					'VENTAS DE AUTOS'
            END) AS concepto_comision,
            id_tempario, id_det_fact_tot, id_articulo, id_unidad_fisica,
			pg_comision_emp_det.porcentaje_comision,
			SUM(IF(pg_comision_emp.id_nota_credito IS NOT NULL, (-1), 1) * `cantidad` * `precio_venta`) AS produccion_departamento
		FROM pg_comision_empleado pg_comision_emp
			INNER JOIN pg_comision_empleado_detalle pg_comision_emp_det ON (pg_comision_emp.id_comision_empleado = pg_comision_emp_det.id_comision_empleado)
			INNER JOIN pg_empleado pg_emp ON (pg_comision_emp.id_empleado = pg_emp.id_empleado)
			INNER JOIN pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = pg_comision_emp.id_cargo_departamento
			INNER JOIN pg_departamento ON pg_departamento.id_departamento = pg_cargo_departamento.id_departamento
			INNER JOIN pg_cargo ON pg_cargo.id_cargo = pg_cargo_departamento.id_cargo
		%s
		GROUP BY pg_comision_emp.id_empleado,modulo",$sqlBusq);

	return $sqlComiDet;
} 

class PDF extends FPDF{
// Cabecera de página
  function Header($valFecha){
		$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
		valTpDato(1, "int"));
		$rsEmp = mysql_query($queryEmp);
		if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
		$rowEmpresa = mysql_fetch_assoc($rsEmp);
		
		$nombreEmp = $rowEmpresa['nombre_empresa'];
		$rifEmp = $rowEmpresa['rif'];
		$web = $rowEmpresa['web'];
		$ruta_logo = "../../".$rowEmpresa['logo_familia']; // Logo
				
		if($rowEmpresa['nit'] != 0){
			$rifDvEmp = $rowEmpresa['nit'];
		} else {
			$rifDvEmp = $rowEmpresa['rif'];
		}
			
			global $valFecha;
			$arrayMes = array("01" => "Enero","02" => "Febrero","03" => "Marzo","04" => "Abril","05" => "Mayo","06" => "Junio",
				"07" => "Julio", "08" => "Agosto","09" => "Septiembre","10" => "Octubre","11" => "Noviembre","12" => "Diciembre");
			foreach($arrayMes as $indice => $valorMes){
				if($indice == $valFecha[0]){
					$mes = $valorMes;
				}
			}
			$titulo = "REPORTE DE COMISIONES A CANCELAR POR NOMINA Mes: ".$mes.utf8_decode(" Año: ").$valFecha[1];
			
			$this->Image($ruta_logo,10,8,33);
			$this->SetFont('Arial','B',9);// Arial bold 15
			$this->Cell(30);//Movernos a la derecha
			$this->SetX(45);
			$this->Cell(20,5,$nombreEmp,0,0,'L'); // Título
			$this->Ln(5);// Salto de línea
			$this->Cell(30);//Movernos a la derecha
			$this->SetX(45);
			$this->Cell(20,5,$rowEmpresa['rif'].' D.V: '.$rowEmpresa['nit'],0,0,'L'); // Título
			$this->SetFont('Arial','B',12);// Arial bold 15
			$this->Ln(5);// Salto de línea
			$this->Cell(30);//Movernos a la derecha
			$this->SetX(45);
			$this->SetFont('Arial','B',9);// Arial bold 15
			$this->Write(5,$web,'http://'.$web);
			$this->Ln(20);// Salto de línea
			$this->SetFont('Arial','B',15);// Arial bold 15
			$this->SetY(30);// Posición: a 1,5 cm del finalstrftime("%B")
			$this->SetX(70);// Posición: a 1,5 cm del final 
			$this->MultiCell(150,5,$titulo,0,"C",false); // Título
			$this->Ln(5);
	}
	
	//Tabla Cabecera
	function headerTable($cabecera){
		$ancho = array("30","30","220");//ancho por cada celda de la cabecera
		$posiscion = array("C","C","L");
		foreach($cabecera as $clave => $valor){
			$this->Cell($ancho[$clave],6,$valor,1,0,$posiscion[$clave]);
		}
	}
	function headerTable2($cabecera){
		$ancho = array("105","15","30","40","40","50");//ancho por cada celda de la cabecera
		$posiscion = array("C","C","C","C","C","R");
		foreach($cabecera as $clave => $valor){
			$this->Cell($ancho[$clave],6,$valor,1,0,$posiscion[$clave]);
		}
	}	
	
	function bodyTable($data){
		$ancho = array("30","30","220");//ancho por cada celda de la cabecera
		$posiscion = array("L","L","L");
			//$this->Ln();
				foreach($data as $clave => $valor){
					$this->Cell($ancho[$clave],5,$valor,1,0,$posiscion[$clave]);	
				}
	}
	function bodyTable2($data){
		$ancho = array("105","15","30","40","40","50");//ancho por cada celda de la cabecera
		$posiscion = array("L","C","C","C","C","R");
			foreach($data as $clave => $valor){
				$this->Cell($ancho[$clave],5,$valor,1,0,$posiscion[$clave]);	
			}
	}
	function bodyTableTotales($data){
		$ancho = array("105","15","30","40","40","50");//ancho por cada celda de la cabecera
		$posiscion = array("R","C","C","C","C","R");
			foreach($data as $clave => $valor){
				$this->Cell($ancho[$clave],5,$valor,1,0,$posiscion[$clave]);	
			}
	}
	
	// Pie de página
	function Footer(){
		
		$this->SetY(-15);// Posición: a 1,5 cm del final
		$this->SetFont('Arial','I',8);// Arial italic 8
		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');// Número de página
	}
}

	//Creación del objeto pdf de la clase heredada
	$pdf = new PDF('L');
	$pdf->AliasNbPages();
	$pdf->AddPage();

	$pdf->SetLeftMargin(150);
	$pdf->SetFont('Arial','',15);
	$pdf->Cell(90,5,"Dias del Mes:",0,0,'R');
	$pdf->Cell(10,5,date("d",(mktime(0,0,0,$valFecha[0]+1,1,$valFecha[1])-1)),0,0,'L');
	$pdf->Ln();
	$pdf->Cell(90,5,"Dias Fereiados y SA / DO:",0,0,'R');
	$pdf->Cell(10,5,cuentaSabadoDomingo($valFecha),0,0,'L');
	$pdf->SetMargins(5,5,5,5);
	$pdf->Ln(5);
//echo sqlComisionEmpleado($valCadBusq);
	$rsComi = mysql_query(sqlComisionEmpleado($valCadBusq));
	if (!$rsComi) die(mysql_error()."<br/>".sqlComisionEmpleado($valCadBusq)."<br/>Line: ".__LINE__);
	$pdf->Ln(5);
	while($rowComi = mysql_fetch_assoc($rsComi)){
		$header = array(utf8_decode("Codigo"), "CI", utf8_decode("Apellido Nombre y Cargo"));
		$pdf->SetFont('Arial','B',11);
		$pdf->headerTable($header);
	$pdf->Ln();
		$tabDatos = array($rowComi['codigo_empleado'],$rowComi['cedula'],$rowComi['nombre_apellido_empleado']."  ".
		$rowComi['nombre_cargo']);
		$pdf->SetFont('Arial','',11);
		$pdf->bodyTable($tabDatos);	
	$pdf->Ln();	
		$header = array(utf8_decode("Concepto"), "%", utf8_decode("Produccióon"), utf8_decode("Comision"), utf8_decode("SA y DO y Feriados"),
				 utf8_decode("Total a Pagar"));
			$pdf->SetFont('Arial','B',11);
			$pdf->headerTable2($header);
	$pdf->Ln();

	$totalProduccion = NULL;
	$totalDiasFeriadosSaDo = NULL;
	$totalComision = NULL;
	$totalApagar = NULL;

	$rsComiEmpDet = mysql_query(sqlComisionEmpleadoDetalle($valCadBusq, $rowComi['id_empleado']));
	if (!$rsComiEmpDet) die(mysql_error()."<br/>".sqlComisionEmpleadoDetalle($valCadBusq, $rowComi['id_empleado'])."<br/>Line: ".__LINE__);
		while($rowComiEmpDet = mysql_fetch_assoc($rsComiEmpDet)){

		$diaMes = date("d",(mktime(0,0,0,$valFecha[0]+1,1,$valFecha[1])-1));
		$NumDiasFeriasdoSaDo = cuentaSabadoDomingo($valFecha);
		
		$porComi = $rowComiEmpDet['porcentaje_comision'];//number_format(, 2, ",", ".")		
		$prduDet = $rowComiEmpDet['produccion_departamento'];
		$porceComi = (($rowComiEmpDet['produccion_departamento'] * $rowComiEmpDet['porcentaje_comision']) / 100);
		$diasFeriadosSaDo =  (($porceComi / $diaMes) * $NumDiasFeriasdoSaDo);
		$comision = $porceComi - $diasFeriadosSaDo;
		
		$bodyDate = array(
			utf8_decode($rowComiEmpDet['concepto_comision']),
			number_format($porComi, 2, ",", "."),number_format($prduDet, 2, ",", "."),
			number_format($comision, 2, ",", "."),
			number_format($diasFeriadosSaDo, 2, ",", "."),
			"");
			$pdf->SetFont('Arial','',11);
			$pdf->bodyTable2($bodyDate);
		$pdf->Ln();
			$totalProduccion []= $rowComiEmpDet['produccion_departamento'];
			$totalDiasFeriadosSaDo []= $diasFeriadosSaDo;
			$totalComision []= $comision;
		}
		$totalApagar []= array_sum($totalDiasFeriadosSaDo)+ array_sum($totalComision);
	$bodyDate = array(utf8_decode('TOTALES'),"",
		number_format(array_sum($totalProduccion), 2, ",", "."),
		number_format(array_sum($totalComision), 2, ",", "."),
		number_format(array_sum($totalDiasFeriadosSaDo), 2, ",", "."),
		number_format(array_sum($totalApagar), 2, ",", "."));
		$pdf->SetFont('Arial','B',11);
		$pdf->bodyTableTotales($bodyDate);
	$pdf->Ln(10);
	}

	$pdf->SetMargins(5,5,5,5);
	$pdf->Output()
	?>