<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

require_once('../../clases/barcode128.inc.php');

/*error_reporting (E_ALL);
ini_set('display_errors', TRUE);	
ini_set('display_startup_errors', TRUE);*/

function calcularPrecio($cant,$precio){
	$precioT = 0;
	$precioT += $cant * $precio;
			
	return $cant;
}

function cambiaLetras($string){	
	$arrayBusqueda = array("Á","É","Í","Ó","Ú", "Ñ");
	$arrayReemplazo = array("á","é","í","ó","ú", "ñ");
	$caden = utf8_decode(str_replace($arrayBusqueda, $arrayReemplazo, utf8_encode($string)));
	
	return ucwords(strtolower($caden));
}

class PDF extends PDF_AutoPrint{
// Cabecera de página

	function Header(){		
		$queryEmpresa = "SELECT nombre_empresa,rif,logo_familia,web FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$_GET['session']."'"; //ide
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
		$rowEmpresa = mysql_fetch_array($rsEmpresa);
		$nombreEmp = $rowEmpresa['nombre_empresa'];
		$rifEmp = $rowEmpresa['rif'];
		$web = $rowEmpresa['web'];
		$ruta_logo = "../../".$rowEmpresa['logo_familia']; // Logo
		$titulo = utf8_decode("Solicitud De Compras");
		
		$this->Image($ruta_logo,10,8,33);
		
		$ruta = "tmp/img_codigo.png";
		$aux = getBarcode($_GET['idSolCom'],'tmp/img_codigo');
		$this->Image($ruta, 175,10,20);
		  
		$this->SetFont('Arial','B',9);// Arial bold 15
		$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,5,$nombreEmp,0,0,'L'); // Título
		$this->Ln(5);// Salto de línea
		$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,5,$rifEmp,0,0,'L'); // Título
		$this->Ln(5);// Salto de línea
		$this->Cell(30);//Movernos a la derecha
		$this->Write(5,$web,'http://'.$web);
		$this->Ln(20);// Salto de línea
		$this->SetFont('Arial','B',15);// Arial bold 15
		$this->SetY(25);// Posición: a 1,5 cm del final
		
		if(file_exists($ruta)){ 
			unlink($ruta); 
		}
	}
	
	//Tabla Cabecera
	function headerTable($cabecera){
		$ancho = array("18","95","15","25","15","22"/*,"22"*/);//ancho por cada celda de la cabecera
		$posiscion = array("C","C","C","C","C","C"/*,"C"*/);
		foreach($cabecera as $clave => $valor){
			$this->Cell($ancho[$clave],5,$valor,1,0,$posiscion[$clave]);
		}
	}	
	
	function bodyTable($data){
		$ancho = array("18","95","15","25","15","22"/*,"22"*/);//ancho por cada celda de la cabecera
		$posiscion = array("L","L","C","L","C","C"/*,"C"*/);
		$this->Ln();
		foreach($data as $clave => $valor){
			$this->Cell($ancho[$clave],5,$valor,1,0,$posiscion[$clave]);	
		}
	}
}


//CONSULTA LA SOLICITUD
$query = sprintf("SELECT 
	id_solicitud_compra, 
	fecha_solicitud, 
	id_unidad_centro_costo, 
	tipo_compra, 
	id_proveedor, 
	nombre_proveedor, 
	justificacion_proveedor, 
	observaciones_proveedor, 
	sustitucion, 
	presupuestado, 
	justificacion_compra, 
	id_estado_solicitud_compras, 
	id_empleado_solicitud, 
	fecha_empleado_solicitud, 
	id_empleado_aprobacion, 
	fecha_empleado_aprobacion, 
	id_empleado_conformacion, 
	fecha_empleado_conformacion, 
	id_empleado_proceso, 
	fecha_empleado_proceso, 
	fecha_creacion, 
	numero_actualizacion, 
	codigo_unidad_centro_costo, 
	nombre_unidad_centro_costo, 
	nombre_departamento, 
	codigo_departamento, 
	nombre_empresa, 
	codigo_empresa, 
	id_departamento, 
	numero_solicitud, 
	id_empresa, 
	id_empleado_solicitud, 
	id_empleado_condicionamiento, 
	motivo_condicionamiento, 
	fecha_empleado_condicionamiento, 
	tipo_seccion 
FROM vw_ga_solicitudes 
	LEFT JOIN ga_tipo_seccion ON ga_tipo_seccion.id_tipo_seccion = vw_ga_solicitudes.tipo_compra 
WHERE id_solicitud_compra = %s", 
valTpDato($_GET['idSolCom'], "int"));
$rs = mysql_query($query) or die(mysql_error());
$rowS = mysql_fetch_array($rs);

//CONSULTA DETALLES	
$queryD = sprintf("SELECT 
	id_detalle_solicitud_compra, 
	id_solicitud_compra, 
	id_articulo, 
	cantidad, 
	precio_sugerido, 
	fecha_requerida, 
	estado_proceso, 
	codigo_articulo, 
	id_marca, 
	id_tipo_articulo, 
	codigo_articulo_prov, 
	descripcion, 
	id_subseccion, 
	stock_maximo, 
	stock_minimo, 
	unidad, 
	foto 
FROM vw_ga_detalle_articulos_solicitud_compra 
WHERE id_solicitud_compra =  %s", 
valTpDato($_GET['idSolCom'], "int"));
$rsD = mysql_query($queryD) or die(mysql_error());

//CONSULTA LOS EMPLEADO
$queryEmp = "SELECT id_empleado, CONCAT_WS(' ', pg_empleado.apellido, pg_empleado.nombre_empleado) AS empleado, codigo_empleado 
				FROM pg_empleado WHERE id_empleado = %s";
		
if($rowS['id_empleado_solicitud'] != "" || $rowS['id_empleado_solicitud'] != NULL)  {
	$rsEmp = mysql_query(sprintf($queryEmp,$rowS['id_empleado_solicitud']));
	$orwsEmp = mysql_fetch_array($rsEmp);
		$codEmpSolicitud = $orwsEmp['id_empleado'];
		$nombreEmpSolicitud = $orwsEmp['empleado'];
} 
if($rowS['id_empleado_aprobacion'] != "" || $rowS['id_empleado_aprobacion'] != NULL)  {
$rsEmp = mysql_query(sprintf($queryEmp,$rowS['id_empleado_aprobacion']));
$orwsEmp = mysql_fetch_array($rsEmp);
	$codEmpAprobacion = $orwsEmp['id_empleado'];
	$nombreEmpAprobacion = $orwsEmp['empleado'];
} 
if($rowS['id_empleado_conformacion'] != "" || $rowS['id_empleado_conformacion'] != NULL)  {
$rsEmp = mysql_query(sprintf($queryEmp,$rowS['id_empleado_conformacion']));
$orwsEmp = mysql_fetch_array($rsEmp);
	$codEmpConformacion = $orwsEmp['id_empleado'];
	$nombreEmpConformacion = $orwsEmp['empleado'];
} 
if($rowS['id_empleado_proceso'] != "" || $rowS['id_empleado_proceso'] != NULL)  {
$rsEmp = mysql_query(sprintf($queryEmp,$rowS['id_empleado_proceso']));
$orwsEmp = mysql_fetch_array($rsEmp);
	$codEmpProceso = $orwsEmp['id_empleado'];
	$nombreEmpProceso = $orwsEmp['empleado'];
}  

//Creación del objeto pdf de la clase heredada
$pdf = new PDF('P','mm','A4');
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->Ln(5);// Salto de línea

$pdf->SetFont('Arial','B',12);
$pdf->Cell(190,5,"SOLICITUD DE COMPRAS Y SERVICIOS",0,0,'C'); // Título

$pdf->Ln(5);// Salto de línea
$pdf->Cell(140);
$pdf->Cell(25,5,"Nro:",0,0,'R'); // Título
$pdf->SetFont('Arial','',12);
$pdf->Cell(25,5,$rowS['numero_solicitud'],0,0,'L'); // Título
$pdf->Ln();// Salto de línea

$pdf->SetFont('Arial','B',12);
$pdf->Cell(20,5,"Empresa:",0,0,'L'); // Título
$pdf->SetFont('Arial','',12);
$pdf->Cell(120,5,utf8_decode($rowS['nombre_empresa']),0,0,'L'); // Título
$pdf->SetFont('Arial','B',12);
$pdf->Cell(25,5,"Fecha:",0,0,'R'); // Título
$pdf->SetFont('Arial','',12); 
$pdf->Cell(25,5,date(spanDateFormat, strtotime($rowS['fecha_solicitud'])),0,0,'L'); // Título  
$pdf->Ln();

$pdf->SetFont('Arial','B',12);
$pdf->Cell(31,5,"Departamento:",0,0,'L'); // Título
$pdf->SetFont('Arial','',12); 
$pdf->Cell(85,5,cambiaLetras($rowS['nombre_departamento']),0,0,'L'); // Título  
$pdf->SetFont('Arial','B',12);
$pdf->Cell(50,5,"Cod. Departamento:",0,0,'R'); // Título
$pdf->SetFont('Arial','',12); 
$pdf->Cell(25,5,$rowS['codigo_departamento'],0,0,'L'); // Título  
$pdf->Ln();

$pdf->SetFont('Arial','B',12);
$pdf->Cell(53,5,"Unidad (Centro de Costo):",0,0,'L'); // Título
$pdf->SetFont('Arial','',12); 
$pdf->Cell(85,5,cambiaLetras($rowS['nombre_unidad_centro_costo']),0,0,'L'); // Título  
$pdf->SetFont('Arial','B',12);
$pdf->Cell(25,5,utf8_decode("Código:"),0,0,'R'); // Título
$pdf->SetFont('Arial','',12); 
$pdf->Cell(25,5,$rowS['codigo_unidad_centro_costo'],0,0,'L'); // Título  
$pdf->Ln();

$pdf->SetFont('Arial','B',12);
$pdf->Cell(35,5,"Tipo De Compra:",0,0,'L'); // Título
$pdf->SetFont('Arial','',12); 
$pdf->Cell(155,5,cambiaLetras($rowS['tipo_seccion']),0,0,'L'); // Título  
$pdf->Ln(10);

//DETALLES DE LA SOLICITUD
$pdf->SetFont('Arial','B',12);
$pdf->Cell(190,5,utf8_decode("Descripción del Material o Servicio"),0,0,'C'); // Título
$pdf->Ln();
$header = array(utf8_decode("Código"), utf8_decode("Descripción"),"Unidad","Fecha R","Cant.","Precio"/*,"Total"*/);
$pdf->SetFont('Arial','B',12);
$pdf->headerTable($header);

$total = array();
while($rowsD = mysql_fetch_array($rsD)){
	$total[] = $rowsD["cantidad"]*$rowsD["precio_sugerido"];
	if(!$rowsD["fecha_requerida"] == "" ){
		$fechaRequerida = date(spanDateFormat, strtotime($rowsD["fecha_requerida"]));
	}	

	$tabDatos = array(substr($rowsD["codigo_articulo_prov"],0,6),
						cambiaLetras(substr($rowsD["descripcion"],0,45)),
						$rowsD["unidad"],
						$fechaRequerida,
						$rowsD["cantidad"],	
						number_format($rowsD["precio_sugerido"],2,".",",")/*,
						  ($rowsD["cantidad"] * $rowsD["precio_sugerido"])*/
				);
	$pdf->SetFont('Arial','',8);
	$pdf->bodyTable($tabDatos);
	
}


//Se agrega informción de Bolívares Soberanos - Reconversión Monetaria 2018, quitar cuando sea requerido/////////////////// 
		if($rowS['fecha_empleado_solicitud'] >= '2018-08-01' and $rowS['fecha_empleado_solicitud'] < '2018-08-20'){
			$pdf->Ln();
			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(153,5,"Total(Bs):",1,0,'R'); // Título
			$pdf->Cell(37,5,number_format(array_sum($total),2,".",","),1,0,'R'); // Título 
			$pdf->Ln();
			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(153,5,"Total(Bs.S):",1,0,'R'); // Título
			$totalReconv = array_sum($total)/100000;
			$pdf->Cell(37,5,number_format($totalReconv,2,".",","),1,0,'R'); // Título 
		}else if($rowS['fecha_empleado_solicitud'] >= '2018-08-20'){
			$pdf->Ln();
			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(153,5,"Total(Bs.S):",1,0,'R'); // Título
			$totalReconv = array_sum($total);
			$pdf->Cell(37,5,number_format($totalReconv,2,".",","),1,0,'R'); // Título 
			$pdf->Ln();
			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(153,5,"Total(Bs):",1,0,'R'); // Título
			$pdf->Cell(37,5,number_format(array_sum($total)*100000,2,".",","),1,0,'R'); // Título 
		}else{
			$pdf->Ln();
			$pdf->SetFont('Arial','B',12);
			$pdf->Cell(153,5,"Total(Bs):",1,0,'R'); // Título
			$pdf->Cell(37,5,number_format(array_sum($total),2,".",","),1,0,'R'); // Título 
		}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(190,5,utf8_decode("Para ser llenado para todo tipo de compra"),0,0,'C'); // Título
$pdf->Ln();
$pdf->Cell(190,5,utf8_decode("(excepto para las compras de material de stock del almacén)"),0,0,'C'); // Título
$pdf->Ln();

switch($rowS["sustitucion"]){
	case 1: $susAdic = "Sustitución"; break;
	case 2: $susAdic = "Adición"; break;	
	default: $susAdic = "Adición"; break;
}
switch($rowS["presupuestado"]){
	case 1: $presupuesto = "Presupuestado"; break;
	default: $presupuesto = ""; 	
}
if(!$rowS["fecha_empleado_solicitud"] == "" ){
	$fechaSolicitud = date(spanDateFormat, strtotime($rowS["fecha_empleado_solicitud"]));
}	
if(!$rowS["fecha_empleado_aprobacion"] == "" ){
	$fechaAprobacion = date(spanDateFormat, strtotime($rowS["fecha_empleado_aprobacion"]));
}	
if(!$rowS["fecha_empleado_conformacion"] == "" ){
	$fechaConformacion = date(spanDateFormat, strtotime($rowS["fecha_empleado_conformacion"]));
}
if(!$rowS["fecha_empleado_proceso"] == "" ){
	$fechaProceso = date(spanDateFormat, strtotime($rowS["fecha_empleado_proceso"]));
}	

$pdf->SetFont('Arial','',12);
$pdf->Cell(95,5,utf8_decode("Sustitución o Adición: ".$susAdic),1,0,'L'); // Título
$pdf->Cell(95,5,utf8_decode("Presupuestado (S/N): ".$presupuesto),1,0,'L'); // Título
$pdf->Ln();

$pdf->MultiCell(190,5,utf8_decode("Justificación de la Compra: ").cambiaLetras($rowS["justificacion_compra"]),1,'L',false); // Título

$pdf->SetFont('Arial','B',12);
$pdf->Ln(10);
$pdf->Cell(190,5,utf8_decode("Unidad Solicitante"),1,0,'C'); // Título
$pdf->Ln();
$pdf->SetFont('Arial','',12);  
$pdf->Cell(95,5,utf8_decode("Solicitado Por: ".cambiaLetras($nombreEmpSolicitud)),1,0,'L'); // Título
$pdf->Cell(95,5,utf8_decode("Aprobado Por: ".cambiaLetras($nombreEmpAprobacion)),1,0,'L'); // Título
$pdf->Ln();
$pdf->Cell(95,5,utf8_decode("N° Empleado: ".$codEmpSolicitud ),1,0,'L'); // Título
$pdf->Cell(95,5,utf8_decode("N° Empleado: ".$codEmpAprobacion),1,0,'L'); // Título
$pdf->Ln(); 
$pdf->Cell(95,5,"Fecha: ".$fechaSolicitud,1,0,'L'); // Título
$pdf->Cell(95,5,"Fecha: ".$fechaAprobacion,1,0,'L'); // Título
$pdf->Ln();
$pdf->SetFont('Arial','B',12);
$pdf->Cell(190,5,utf8_decode("Gerencia de Compras"),1,0,'C'); // Título
$pdf->Ln();
$pdf->SetFont('Arial','',12);   
$pdf->Cell(95,5,utf8_decode("Conformado Por: ".cambiaLetras($nombreEmpConformacion)),1,0,'L'); // Título
$pdf->Cell(95,5,utf8_decode("Procesado Por: ".cambiaLetras($nombreEmpProceso)),1,0,'L'); // Título
$pdf->Ln();
$pdf->Cell(95,5,utf8_decode("N° Empleado: ".$codEmpConformacion),1,0,'L'); // Título
$pdf->Cell(95,5,utf8_decode("N° Empleado: ".$codEmpProceso),1,0,'L'); // Título
$pdf->Ln();
$pdf->Cell(95,5,"Fecha: ".$fechaConformacion,1,0,'L'); // Título
$pdf->Cell(95,5,"Fecha: ".$fechaProceso,1,0,'L'); // Título

$pdf->Output();
?>
