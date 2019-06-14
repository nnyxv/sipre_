<?php 
require("../../connections/conex.php");
require('../../inc_sesion.php');
require('../../clases/fpdf/fpdf.php');

/*El informe de errores
error_reporting (E_ALL);
ini_set('display_errors', TRUE);	
ini_set('display_startup_errors', TRUE);*/

class PDF extends FPDF{
// Cabecera de página
	function Header(){
		$queryEmpresa = sprintf("SELECT nombre_empresa,rif,logo_familia,web FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
			$_SESSION['idEmpresaUsuarioSysGts']);
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryEmpresa);
		$rowEmpresa = mysql_fetch_array($rsEmpresa);
		$nombreEmp = $rowEmpresa['nombre_empresa'];
		$rifEmp = $rowEmpresa['rif'];
		$web = $rowEmpresa['web'];
		$ruta_logo = "../../".$rowEmpresa['logo_familia']; // Logo
		$titulo = utf8_decode("Solicitud De Compras");
			
		$this->Image($ruta_logo,1,1,33);
		
		$this->SetFont('Arial','B',7);// Arial bold 15
		$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,3,$nombreEmp,0,0,'L'); // Título
		$this->Ln();// Salto de línea
		$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,3,$rifEmp,0,0,'L'); // Título
		$this->Ln();// Salto de línea
		$this->Cell(30);//Movernos a la derecha
		$this->Write(3,$web,'http://'.$web);
		$this->Ln();// Salto de línea
		$this->SetFont('Arial','B',8);// Arial bold 15
		$this->SetY(17);// Posición: a 1,5 cm del final
		$this->Cell(0,3,"Estatus de las Unidades Fisicas",0,0,'C'); // Título
		$this->Ln(5);// Salto de línea
	}
	
	//Tabla Cabecera
	function headerTable($ancho,$posiscion,$cabecera){
		foreach($cabecera as $clave => $valor){
			$this->Cell($ancho[$clave],4,$valor,1,0,$posiscion[$clave]);
		}
	}	
	
	function bodyTable($ancho,$posiscion,$data){
		$this->Ln();
		foreach($data as $clave => $valor){
			$this->Cell($ancho[$clave],4,$valor,1,0,$posiscion[$clave]);	
		}
	}
	
	// Pie de página
	function Footer(){
		$this->SetY(-15);// Posición: a 1,5 cm del final
		$this->SetFont('Arial','I',8);// Arial italic 8
		$this->Cell(140,3,'Page '.$this->PageNo().'/{nb}',0,0,'L');// Número de página
		$this->Cell(153,3,'Impreso '.date(spanDateFormat." h:m a"),0,0,'R');// Número de página
	}
}

//Creación del objeto pdf de la clase heredada
$pdf = new PDF('L');//
$pdf->SetMargins("2","10","2");
$pdf->AliasNbPages();
$pdf->AddPage();

$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('TRANSITO', 'POR REGISTRAR', 'SINIESTRADO', 'DISPONIBLE', 'RESERVADO', 'ACTIVO FIJO')");
	
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = alm.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca = %s",
		valTpDato($valCadBusq[1], "int"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version = %s",
		valTpDato($valCadBusq[3], "int"));
}
	
if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_compra IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[4])."'", "defined", "'".str_replace(",","','",$valCadBusq[4])."'"));
}
	
if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[5])."'", "defined", "'".str_replace(",","','",$valCadBusq[5])."'"));
}
	
if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("alm.id_almacen IN (%s)",
		valTpDato($valCadBusq[7], "campo"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_estado_adicional IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[8])."'", "defined", "'".str_replace(",","','",$valCadBusq[8])."'"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
	OR vw_iv_modelo.nom_uni_bas LIKE %s
	OR vw_iv_modelo.nom_modelo LIKE %s
	OR vw_iv_modelo.nom_version LIKE %s
	OR uni_fis.serial_motor LIKE %s
	OR uni_fis.serial_carroceria LIKE %s
	OR uni_fis.placa LIKE %s
	OR numero_factura_proveedor LIKE %s)",
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"));
}

//CONSULTA LOS ESTATUS DE LA TABLA
$sqlEstatus =  sprintf("SELECT DISTINCT uni_fis.id_estado_adicional, estado_adicional.nombre_estado
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_unidad_estado_adicional estado_adicional ON (uni_fis.id_estado_adicional = estado_adicional.id_estado_adicional)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		%s
	ORDER BY uni_fis.id_estado_adicional DESC ",$sqlBusq);
$rsEstatus = mysql_query($sqlEstatus);
if (!$rsEstatus) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlEstatus);

while($rowEstatus = mysql_fetch_array($rsEstatus)){

	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("uni_fis.id_estado_adicional = %s",
		valTpDato($rowEstatus['id_estado_adicional'], "int"));
	
	$pdf->Ln(5);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(0,4,"Estatus: ".$rowEstatus['nombre_estado'],1,0,'L');// Número de página
	$pdf->Ln();
	
	$pdf->SetFont('Arial','B',8);
	$ancho = array("16","32","32","16","20","40","25","10","50","18","18","16");
	$posiscion = array("C","C","C","C","C","C","C","C","C","C","C","C","C","C");
	$header = array("Nro Unidad", $spanSerialCarroceria, $spanSerialMotor, $spanPlaca, "Marca", "Modelo", "Clase",utf8_decode("Año"),  "Almacen", utf8_decode("Últ. Fecha Al."), utf8_decode("Días Al."), utf8_decode("Días Sin Al."));
	$pdf->SetFont('Arial','B',8);
	$pdf->headerTable($ancho,$posiscion,$header);	
	$ancho = array("16","32","32","16","20","40","25","10","50","18","18","16");
	$posiscion = array("C","C","C","C","L","L","L","C","L","C","C","C");
	
	$totalUniFisica = array();
	$totalDiasAlquilado = array();
	$totalTotalDiasAlquilado = array();
	$totalDiasSinAlquilar = array();
	
	//DATOS DE LA UNIDAD FISICA
	$sqlUniFisica = sprintf("SELECT uni_fis.id_unidad_fisica, vw_iv_modelo.nom_marca, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version, vw_iv_modelo.nom_ano, uni_fis.id_clase, clase.nom_clase, uni_fis.serial_motor, uni_fis.serial_carroceria, uni_fis.placa, uni_fis.estado_venta, alm.nom_almacen, estado_adicional.nombre_estado,
	
		(SELECT MAX(DATE(contrato.fecha_salida)) 
			FROM al_contrato_venta contrato 
			WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica) AS ultima_fecha_alquilado,
		
		(SELECT contrato.dias_contrato
			FROM al_contrato_venta contrato
			WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica
			AND contrato.estatus_contrato_venta = 1
			ORDER BY contrato.id_contrato_venta DESC LIMIT 1) AS dias_alquilado,
			
		(SELECT SUM(IF(contrato.estatus_contrato_venta = 1, contrato.dias_contrato, contrato.dias_total)) 
			FROM al_contrato_venta contrato
			WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica) AS total_dias_alquilado,
		
		(IF((SELECT COUNT(contrato.id_contrato_venta) 
				FROM al_contrato_venta contrato 
				WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica 
				AND contrato.estatus_contrato_venta = 1) = 0,					
			(SELECT ABS(DATEDIFF(CURDATE(), DATE(contrato.fecha_final)))
				FROM al_contrato_venta contrato
				WHERE contrato.id_unidad_fisica = uni_fis.id_unidad_fisica
				ORDER BY contrato.id_contrato_venta DESC LIMIT 1),
			0)) AS dias_sin_alquilar
				
	FROM an_unidad_fisica uni_fis 
		INNER JOIN an_unidad_estado_adicional estado_adicional ON (uni_fis.id_estado_adicional = estado_adicional.id_estado_adicional)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase) %s %s", $sqlBusq, $sqlBusq2);

	$rsUniFisica = mysql_query($sqlUniFisica);
	if (!$rsUniFisica) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlUniFisica);

	while($rowUniFisica = mysql_fetch_array($rsUniFisica)){
		$totalUniFisica[] = $rowUniFisica["id_unidad_fisica"];
		$totalDiasAlquilado[] = $rowUniFisica["dias_alquilado"];
		$totalTotalDiasAlquilado[] = $rowUniFisica["total_dias_alquilado"];		
		$totalDiasSinAlquilar[] = $rowUniFisica["dias_sin_alquilar"];
		
		$tabDatos = array(
		$rowUniFisica["id_unidad_fisica"],
		$rowUniFisica["serial_carroceria"],
		$rowUniFisica["serial_motor"],
		$rowUniFisica["placa"],
		substr($rowUniFisica["nom_marca"],0,10),
		substr($rowUniFisica["nom_modelo"],0,20),
		substr($rowUniFisica["nom_clase"],0,18),
		$rowUniFisica["nom_ano"],
		substr($rowUniFisica["nom_almacen"],0,26),		
		($rowUniFisica["ultima_fecha_alquilado"] != "") ? date(spanDateFormat, strtotime($rowUniFisica["ultima_fecha_alquilado"])) : "-",
		intval($rowUniFisica["dias_alquilado"])." (".intval($rowUniFisica["total_dias_alquilado"]).")",
		intval($rowUniFisica["dias_sin_alquilar"]));
		$pdf->SetFont('Arial','',8);
		$pdf->bodyTable($ancho,$posiscion,$tabDatos);
	}
	
	$pdf->Ln();
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(48,4,"Total Unidad ".$rowEstatus['estatus'].": ",1,0,'R');// Número de página
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(193,4,count($totalUniFisica),1,0,'L');// Número de página
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(18,4,utf8_decode("Total Días : "),1,0,'R');// Número de página
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(18,4,array_sum($totalDiasAlquilado)." (".array_sum($totalTotalDiasAlquilado).")",1,0,'R');// Número de página
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(16,4,array_sum($totalDiasSinAlquilar),1,0,'R');// Número de página

	$pdf->Ln(10);// Salto de línea
}

$pdf->Output()
?>