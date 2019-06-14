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

function fechaMesAnterior($fecha){
	$mes = $fecha[0];
		$arrayMes = array("01 "=> "Enero","02" => "Febrero","03" => "Marzo","04" => "Abril","05" => "Mayo","06" => "Junio",
		"07" => "Julio", "08" => "Agosto","09" => "Septiembre","10" => "Octubre","11" => "Noviembre","12" => "Diciembre");
		foreach($arrayMes as $indice => $valorMes){
			if($indice == $mes){
				$mes = $valorMes;
			}
		}
	return 	$stringFecha = " Mes: ".$mes.utf8_decode(" Año: ").$fecha[1];
}

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

function numCuentaBanco($idCuenta){
	
	$queryEmp = sprintf("SELECT * FROM cuentas WHERE idCuentas = %s;",
	valTpDato($idCuenta, "int"));
	$rsEmp = mysql_query($queryEmp);
	if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmp);
	
	return $rowEmpresa['numeroCuentaCompania'];
	
}

function sqlComisionEmpleado($valCadBusq){
	
	// EMPLEADO ACTIVO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pg_emp.activo = 1");
	
	//POR EMPRESA
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
	
	//POR DEPARTAMENTO
	if($valCadBusq[2] != "" && $valCadBusq[2] != "-1"){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pg_cargo_departamento.id_cargo = %s",
			$valCadBusq[2]);
	}
	
	//POR EMPLEADO
	if($valCadBusq[3] != "" && $valCadBusq[3] != "-1"){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pg_comision_emp.id_empleado = %s",
			$valCadBusq[3]);
	}
	
	//POR DEPARTAMENTO
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
			(CASE 
					WHEN (id_factura IS NOT NULL AND id_nota_credito IS NULL) THEN
						(SELECT idDepartamentoOrigenFactura FROM cj_cc_encabezadofactura encabezado_fact 
							WHERE encabezado_fact.idFactura = pg_comision_emp.id_factura) 
							
					WHEN (id_nota_credito IS NOT NULL) THEN
						(SELECT idDepartamentoNotaCredito FROM cj_cc_notacredito 
							WHERE cj_cc_notacredito.idNotaCredito = pg_comision_emp.id_nota_credito)
				END) AS modulo,
                id_tempario,pg_comision_emp_det.id_articulo,
			pg_comision_emp_det.porcentaje_comision,
			SUM(IF(pg_comision_emp.id_nota_credito IS NOT NULL, (-1), 1) * `cantidad` * `precio_venta`) AS produccion_departamento
		FROM pg_comision_empleado pg_comision_emp
			INNER JOIN pg_comision_empleado_detalle pg_comision_emp_det ON (pg_comision_emp.id_comision_empleado = pg_comision_emp_det.id_comision_empleado)
			INNER JOIN pg_empleado pg_emp ON (pg_comision_emp.id_empleado = pg_emp.id_empleado)
			INNER JOIN pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = pg_comision_emp.id_cargo_departamento
			INNER JOIN pg_departamento ON pg_departamento.id_departamento = pg_cargo_departamento.id_departamento
			INNER JOIN pg_cargo ON pg_cargo.id_cargo = pg_cargo_departamento.id_cargo
		%s
		GROUP BY pg_comision_emp.id_empleado,modulo ",$sqlBusq);

	return $sqlComiDet;
} 

class PDF extends FPDF{
// Cabecera de página
	function Header(){
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
			$this->SetFont('Arial','B',9);// Arial bold 15
			$this->SetX(45);
			$this->Write(5,$web,'http://'.$web);
			$this->Ln(20);// Salto de línea
			$this->SetFont('Arial','B',15);// Arial bold 15
			$this->SetY(30);// Posición: a 1,5 cm del 
			$this->Cell(195,5,"CARTA DE AUTORIZACION",0,0,'C');// Número de página
	}
	
	//Tabla Cabecera
	function headerTable($cabecera){
		$ancho = array("25","50","40","40","40");//ancho por cada celda de la cabecera
		$posiscion = array("C","C","C","C","C");
		foreach($cabecera as $clave => $valor){
			$this->Cell($ancho[$clave],6,$valor,1,0,$posiscion[$clave]);
		}
	}	
	
	function bodyTable($data){
		$ancho = array("25","50","40","40","40");//ancho por cada celda de la cabecera
		$posiscion = array("L","L","C","C","C");
			$this->Ln();
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

	$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato(1, "int"));
	$rsEmp = mysql_query($queryEmp);
	if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmp);		
	$nombreEmp = $rowEmpresa['nombre_empresa'];
	
	//Creación del objeto pdf de la clase heredada
	$pdf = new PDF();
	$pdf->AliasNbPages();
	$pdf->AddPage();
	
	$pdf->Ln(10);
	//TITULO
	$pdf->SetFont('Arial','B',10);
	$pdf->MultiCell(195,5,"Por medio del presente autorizo a debitar de la cuenta ".numCuentaBanco($_GET["Idcuenta"])." el pago por concepto decomisiones devengadas en ".$nombreEmp. " durante el ".fechaMesAnterior($valFecha),0,"L",false);
	
	$pdf->Ln(5);
	$header = array(utf8_decode("Codigo"), utf8_decode("Apellido Nombre"),utf8_decode("Comisión ".$valCadBusq[1]),
	"SA DO y Feriado","Comision A Pagar");
	$pdf->SetFont('Arial','B',11);
	$pdf->headerTable($header);
	
	$rsComi = mysql_query(sqlComisionEmpleado($valCadBusq));
	if (!$rsComi) die(mysql_error()."<br/>".sqlComisionEmpleado($valCadBusq)."<br/>Line: ".__LINE__);
	while($rowComi = mysql_fetch_assoc($rsComi)){
		
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
		
		$totalDiasFeriadosSaDo += $diasFeriadosSaDo;
		$totalComision += $comision;
		}
		$totalApagar += ($totalComision + $totalDiasFeriadosSaDo);
		
		$totalGeneralDiasFeriadosSaDo []= $totalDiasFeriadosSaDo;
		$totalGeneralComision [] = $totalComision;
		$totalGeneralApagar []= $totalApagar;
		
		$body = array(utf8_decode($rowComi['codigo_empleado']), utf8_decode($rowComi['nombre_apellido_empleado']),
		number_format($totalComision, 2, ",", "."),number_format($totalDiasFeriadosSaDo, 2, ",", "."),
		number_format($totalApagar, 2, ",", "."));
		$pdf->SetFont('Arial','',11);
		$pdf->bodyTable($body);	
	}
	$pdf->Ln();
	$pdf->SetFont('Arial','B',11);
	$pdf->Cell(75,5,"Total",1,0,'R');
	$pdf->Cell(40,5,number_format(array_sum($totalGeneralComision), 2, ",", "."),1,0,'R');
	$pdf->Cell(40,5,number_format(array_sum($totalGeneralDiasFeriadosSaDo), 2, ",", "."),1,0,'R');	
	$pdf->Cell(40,5,number_format(array_sum($totalGeneralApagar), 2, ",", "."),1,0,'R');

	$pdf->SetMargins(5,5,5,5);
	$pdf->Output()
	?>