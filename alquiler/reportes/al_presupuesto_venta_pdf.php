<?php 
require("../../connections/conex.php");
require('../../inc_sesion.php');
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');
require('../../clases/barcode128.inc.php');


/*error_reporting (E_ALL);
ini_set('display_errors', TRUE);	
ini_set('display_startup_errors', TRUE);*/

$idDocumento = $_GET['valBusq'];

//CONSULTA LOS DATOS DEL DOCUMENTO
$sqlDoc =  sprintf("SELECT
		presupuesto.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM al_presupuesto_venta presupuesto
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (presupuesto.id_empresa = vw_iv_emp_suc.id_empresa_reg)		
	WHERE presupuesto.id_presupuesto_venta = %s",
	valTpDato($idDocumento, "int"));
$rsDoc = mysql_query($sqlDoc);
if (!$rsDoc) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlDoc);
$rowDoc = mysql_fetch_array($rsDoc);

if (mysql_num_rows($rsDoc) == 0) { die("No se encontró el presupuesto por id"); }

$idEmpresa = $rowDoc['id_empresa'];

class PDF extends PDF_AutoPrint{
// Cabecera de página

	function Header(){
		global $spanRIF;
		global $spanNIT;
		global $rowDoc;
		global $idEmpresa;
		
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
			$idEmpresa);
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryEmpresa);
		$rowEmpresa = mysql_fetch_array($rsEmpresa);
		
		$ruta_logo = "../../".$rowEmpresa['logo_familia']; // Logo
		
		$this->Image($ruta_logo,10,5,30);
		
		$ruta = "tmp/img_codigo.png";
		
		$aux = getBarcode($rowDoc['numero_presupuesto_venta'],'tmp/img_codigo');
		$this->Image($ruta, 175,10,20);
		
		if(file_exists($ruta)) unlink($ruta);
		
		$this->SetY(22);
		$this->SetFont('Arial','B',7);// Arial bold 15
		//$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,3,$rowEmpresa['nombre_empresa'],0,0,'L'); // Título
		$this->Ln();// Salto de línea
		//$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,3,$spanRIF." ".$rowEmpresa['rif']. " ".$spanNIT." ".$rowEmpresa['nit'],0,0,'L'); // Título
		$this->Ln();// Salto de línea
		//$this->Cell(30);//Movernos a la derecha
		$this->Cell(20,3,$rowEmpresa['direccion'],0,0,'L');
		
		$this->Ln();
		$this->Cell(20,3,$rowEmpresa['telefono1']." ".$rowEmpresa['telefono2'],0,0,'L');
		
		$this->Ln();
		$this->Cell(20,3,$rowEmpresa['web']." ".$rowEmpresa['correo'],0,0,'L');
		$this->Ln();// Salto de línea
		$this->SetFont('Arial','B',10);// Arial bold 15
		//$this->SetY(25);// Posición: a 1,5 cm del final
		$this->SetY(16);
		$this->Cell(0,3,"Presupuesto de Arrendamiento",0,0,'C'); // Título

		//Put the watermark
		$this->SetFont('Arial','B',50);
		//$this->SetTextColor(255,192,203);// ROJO
		$this->SetTextColor(205,201,201);
		$this->RotatedText(40,170,'P R E S U P U E S T O',45);
	}
}

function headerTable($ancho,$posiscion,$cabecera,$borde = 1){
	global $pdf;
	foreach($cabecera as $clave => $valor){
		$pdf->Cell($ancho[$clave],3.2,$valor,$borde,0,$posiscion[$clave]);
	}
}	

function bodyTable($ancho,$posiscion,$data,$borde = 1){
	global $pdf;
	$pdf->Ln();
	foreach($data as $clave => $valor){
		$pdf->Cell($ancho[$clave],3.2,$valor,$borde,0,$posiscion[$clave]);	
	}
}

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$arrayCombustible = array("0.00" => "0",
						"0.25" => "1/4",
						"0.50" => "1/2",
						"0.75" => "3/4",
						"1.00" => "FULL");

//TIPO DE CONTRARO
$sqlTpCnt = sprintf("SELECT * FROM al_tipo_contrato WHERE id_tipo_contrato = %s",
valTpDato($rowDoc['id_tipo_contrato'], "int"));
$rsTpCnt = mysql_query($sqlTpCnt);
if (!$rsTpCnt) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlTpCnt);
$rowTpCnt = mysql_fetch_array($rsTpCnt);

//Creación del objeto pdf de la clase heredada
$pdf = new PDF('P', 'mm', 'A4');//'L'
//$pdf->SetMargins("2","2","2");
$pdf->AliasNbPages();
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];

$pdf->nombreRegistrado = $row['nombre_empleado'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
//$pdf->mostrarHeader = 1;

$pdf->AddPage();
$pdf->Ln(18);// Salto de línea

//DATOS DEL CLIENTE
$sqlCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado,
		cliente.paga_impuesto,
		cliente.status,
		cliente.contacto AS nombre_contacto,
		CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_cliente_contacto,
		cliente.telfcontacto,
		cliente.correocontacto,
		crm_perfil.fecha_nacimiento
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) 
		LEFT JOIN crm_perfil_prospecto crm_perfil ON (cliente.id = crm_perfil.id)
	WHERE cliente.id = %s", 
valTpDato($rowDoc['id_cliente'], "int"));
$rsCliente = mysql_query($sqlCliente);
if (!$rsCliente) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlCliente);
$rowCliente = mysql_fetch_assoc($rsCliente);

//DATOS DEL CLIENTE DE PAGO
$sqlCliente = sprintf("SELECT
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM cj_cc_cliente cliente
	WHERE cliente.id = %s", 
valTpDato($rowDoc['id_cliente_pago'], "int"));
$rsCliente = mysql_query($sqlCliente);
if (!$rsCliente) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlCliente);
$rowClientePago = mysql_fetch_assoc($rsCliente);

$pdf->SetFont('Arial','B',8);
$pdf->Cell(175,3,utf8_decode("N° Presupuesto:"),0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(15,3,$rowDoc['numero_presupuesto_venta'],0,0,'L'); // Título 
$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(107,3,"Datos del Cliente",0,0,'R'); // Título
$pdf->SetFont('Arial','B',8);
$pdf->Cell(65,3,"Tipo de Contrato:",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(35,3,$rowTpCnt['nombre_tipo_contrato'],0,0,'L'); // Título 
$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(20,3,"Arrendatario:",0,0,'L'); // Título
$pdf->SetFont('Arial','',8);
$pdf->Cell(85,3,substr($rowCliente['nombre_cliente'],0,60),0,0,'L'); // Nombre Cliente
$pdf->SetFont('Arial','B',8);
$pdf->Cell(17,3,$spanClienteCxC.":",0,0,'L'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(35,3,$rowCliente['ci_cliente'],0,0,'L'); // Título 
$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(15,3,utf8_decode("Dirección:"),0,0,'L'); // Título 
$pdf->SetFont('Arial','',8);
$pdf->Cell(175,3,$rowCliente['direccion'],0,0,'L'); // Título
$pdf->SetFont('Arial','B',8);
$pdf->Ln();

if($rowCliente['fecha_nacimiento'] != ""){
	$fechaNacimiento = date(spanDateFormat, strtotime($rowCliente['fecha_nacimiento']));

	$y = date("Y", strtotime($fechaNacimiento));
	$m = date("m", strtotime($fechaNacimiento));
	$d = date("d", strtotime($fechaNacimiento));
	
	$edad = date("Y") - $y -1;
	
	if(date("m") > $m){
		$edad = $edad + 1;
	}else if(date("m") == $m){
		if(date("d") >= $d){
			$edad = $edad + 1;
		}
	}		
	
	$edad = $edad." Años";
}

$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,3,"Fecha de Nacimiento:",0,0,'L'); // Título
$pdf->SetFont('Arial','',8);
$pdf->Cell(50,3,$fechaNacimiento."   ".utf8_decode($edad),0,0,'L'); // Título
$pdf->SetFont('Arial','B',8);
$pdf->Cell(15,3,"Telf:",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(90,3,$rowCliente['telf']."   ".$rowCliente['otrotelf'],0,0,'L'); // Título  
$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(25,3,"Contacto Local:",0,0,'L'); // Título
$pdf->SetFont('Arial','',8);
$pdf->Cell(155,3,$rowCliente['nombre_contacto']." ".$rowCliente['ci_cliente_contacto']." ".$rowCliente['telfcontacto']." ".$rowCliente['correocontacto'],0,0,'L');
$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(20,3,"Cliente Pago:",0,0,'L'); // Título
$pdf->SetFont('Arial','',8);
$pdf->Cell(85,3,substr($rowClientePago['nombre_cliente'],0,60),0,0,'L'); // Nombre Cliente
$pdf->SetFont('Arial','B',8);
$pdf->Cell(17,3,$spanClienteCxC.":",0,0,'L'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(35,3,$rowClientePago['ci_cliente'],0,0,'L'); // Título 
$pdf->Ln();

$pdf->SetFont('Arial','B',8); 
$pdf->MultiCell(190,3,utf8_decode("'Arrendatario' bajo ninguna circunstancia permitirá el uso del vehículo rentado a cualquier persona que no sea las descritas en este contrato. La operación del vehículo por cualquier otro conductor esta prohíbido"),1,'J',false); // Título 

//DATO DEL VEHICULO 
$sqlAuto = sprintf("SELECT
		uni_fis.id_unidad_fisica,
		uni_fis.id_activo_fijo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.placa,
		(CASE uni_fis.id_condicion_unidad
			WHEN 1 THEN	'NUEVO'
			WHEN 2 THEN	'USADO'
			WHEN 3 THEN	'USADO PARTICULAR'
		END) AS condicion_unidad,
		color_ext.nom_color AS color_externo1,
		color_int.nom_color AS color_interno1,
		clase.nom_clase,
		clase.id_clase,
		uni_bas.nom_uni_bas,
		uni_fis.estado_compra,
		uni_fis.estado_venta,
		uni_fis.kilometraje,
		uni_fis.id_uni_bas,
		alm.nom_almacen,
		vw_iv_modelo.nom_ano,
		vw_iv_modelo.nom_modelo,
		vw_iv_modelo.nom_marca,
		vw_iv_modelo.id_modelo,			
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
	WHERE uni_fis.id_unidad_fisica = %s",
valTpDato($rowDoc['id_unidad_fisica'], "int"));
$rsAuto = mysql_query($sqlAuto);
if (!$rsAuto) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlAuto);
$rowAuto = mysql_fetch_array($rsAuto);

$pdf->Ln(5);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(0,3,utf8_decode("Datos del Vehículo"),0,0,'C'); // Título
$pdf->Ln();
$pdf->SetFont('Arial','B',8);
$pdf->Cell(20,3,"Nro Unidad:",0,0,'L'); // Título
$pdf->SetFont('Arial','',8);
$pdf->Cell(10,3,$rowAuto['id_unidad_fisica'],0,0,'L'); // Nombre Cliente
$pdf->SetFont('Arial','B',8);
$pdf->Cell(20,3,"Serial Carro",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(35,3,$rowAuto['serial_carroceria'],0,0,'L'); // Título 
$pdf->SetFont('Arial','B',8);
$pdf->Cell(10,3,"Marca:",0,0,'L'); // Título 
$pdf->SetFont('Arial','',8);
$pdf->Cell(20,3,$rowAuto['nom_marca'],0,0,'L'); // Título
$pdf->SetFont('Arial','B',8);
$pdf->Cell(13,3,"Modelo:",0,0,'L'); // Título
$pdf->SetFont('Arial','',8);
$pdf->Cell(30,3,$rowAuto['nom_modelo'],0,0,'L'); // Título
$pdf->SetFont('Arial','B',8);
$pdf->Cell(10,3,"Clase:",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(20,3,$rowAuto['nom_clase'],0,0,'L'); // Título 

$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(8,3,utf8_decode("Año"),0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(15,3,$rowAuto['nom_ano'],0,0,'L'); // Título 
$pdf->SetFont('Arial','B',8);
$pdf->Cell(15,3,"Color:",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(25,3,$rowAuto['color_externo1'],0,0,'L'); // Título  
$pdf->SetFont('Arial','B',8);
$pdf->Cell(15,3,"Placa:",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(25,3,$rowAuto['placa'],0,0,'L'); // Título  
$pdf->SetFont('Arial','B',8);
$pdf->Cell(25,3,utf8_decode("Condición:"),0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(20,3,$rowAuto['condicion_unidad'],0,0,'L'); // Título
$pdf->SetFont('Arial','B',8);
$pdf->Cell(20,3,$spanKilometraje.":",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(22,3,$rowAuto['kilometraje'],0,0,'L'); // Título  
$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(33,3,$spanKilometraje." de Salida:",0,0,'L'); // Título
$pdf->SetFont('Arial','',8);
$pdf->Cell(20,3,$rowDoc['kilometraje_salida'],0,0,'L'); // Título
$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,3,"Combustible Salida:",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(39,3,$arrayCombustible[$rowDoc['nivel_combustible_salida']],0,0,'L'); // Título
$pdf->SetFont('Arial','B',8); 
$pdf->Cell(29,3,"Fecha Salida:",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(39,3,($rowDoc['fecha_salida'] != "") ? date(spanDateFormat." h:i a", strtotime($rowDoc['fecha_salida'])):"",0,0,'L'); // Título
$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(33,3,$spanKilometraje." de Entrada:",0,0,'L'); // Título 
$pdf->SetFont('Arial','',8);
$pdf->Cell(20,3,$rowDoc['kilometraje_entrada'],0,0,'L'); // Título
$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,3,"Combustible entrada:",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(39,3,$arrayCombustible[$rowDoc['nivel_combustible_entrada']],0,0,'L'); // Título  
$pdf->SetFont('Arial','B',8); 
$pdf->Cell(29,3,"Fecha Entrada:",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(39,3,($rowDoc['fecha_entrada'] != "") ? date(spanDateFormat." h:i a", strtotime($rowDoc['fecha_entrada'])):"",0,0,'L'); // Título
$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(33,3,$spanKilometraje." Corridas:",0,0,'L'); // Título
$pdf->SetFont('Arial','B',8);
$kilometrajeRecorrido = ($rowDoc['kilometraje_entrada'] > 0) ? $rowDoc['kilometraje_entrada'] - $rowDoc['kilometraje_salida'] : "";
$pdf->SetFont('Arial','',8);
$pdf->Cell(20,3,$kilometrajeRecorrido,0,0,'L'); // Título
$pdf->SetFont('Arial','B',8);
//$pdf->Cell(16,3,"Original:",0,0,'R'); // Título
$pdf->SetFont('Arial','B',8); 
$pdf->Cell(17,3,"",0,0,'L'); // Título  
$pdf->SetFont('Arial','B',8);
//$pdf->Cell(18,3,"Sustituto:",0,0,'R'); // Título
$pdf->SetFont('Arial','',8); 
$pdf->Cell(18,3,"",0,0,'L'); // Título
$pdf->Ln();
$pdf->SetFont('Arial','B',8); 
$pdf->MultiCell(0,3,utf8_decode("Fecha Vencimiento: ".date(spanDateFormat, strtotime($rowDoc['fecha_vencimiento']))." Vehículo no devuelto a la fecha de vencimiento se considera robado"),1,'J',false); // Título 
$pdf->Ln();

$pdf->SetFont('Arial','B',8);
$pdf->Cell(0,3,"Precios / Tiempo",0,0,'C'); // Título
$pdf->Ln();

$ancho = array("10","30","95","15","20","20");
$posiscion = array("C","C","C","C","C","C","C");
$header = array("Item",utf8_decode("Código"),utf8_decode("Descripción"), utf8_decode("Días/Cant."), "Precio", "Total");
$pdf->SetFont('Arial','B',8);
headerTable($ancho,$posiscion,$header);

//PRESIO / TIEMPO
$sqlPrecio = sprintf("SELECT 
		al_presupuesto_venta_precio.id_presupuesto_venta_precio,
		al_presupuesto_venta_precio.id_presupuesto_venta,
		al_presupuesto_venta_precio.id_precio,
		nombre_precio,
		al_precios_detalle.descripcion,
		al_presupuesto_venta_precio.id_precio_detalle,
		al_presupuesto_venta_precio.id_tipo_precio,
		al_presupuesto_venta_precio.dias_calculado,
		total_precio,
		al_presupuesto_venta_precio.precio
	FROM al_presupuesto_venta_precio
		INNER JOIN al_precios_detalle ON al_presupuesto_venta_precio.id_precio_detalle = al_precios_detalle.id_precio_detalle
		INNER JOIN al_precios ON al_presupuesto_venta_precio.id_precio = al_precios.id_precio
		INNER JOIN al_tipo_precio ON al_presupuesto_venta_precio.id_tipo_precio = al_tipo_precio.id_tipo_precio
	WHERE al_presupuesto_venta_precio.id_presupuesto_venta = %s",
valTpDato($idDocumento, "int"));
$rsPrecio = mysql_query($sqlPrecio);
if (!$rsPrecio) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlPrecio);
$totalPrecio = array();
$itemPrecio = 1;
$posiscion = array("C","L","L","C","C","R");
while($rowsPrecio = mysql_fetch_array($rsPrecio)){
	$totalPrecio[] = ($rowsPrecio["total_precio"]);
	$tabDatos = array($itemPrecio ++,
						ucfirst(strtolower(substr($rowsPrecio["nombre_precio"],0,20))),
						ucfirst(strtolower(substr($rowsPrecio["descripcion"],0,49))),
						$rowsPrecio["dias_calculado"],
						number_format($rowsPrecio["precio"], 2, ".", ","),
						$rowsPrecio["total_precio"]);
	$pdf->SetFont('Arial','',8);
	bodyTable($ancho,$posiscion,$tabDatos);
}

$pdf->Ln();
$pdf->SetFont('Arial','B',8);
$pdf->Cell(170,3.1,"total:",1,0,'R'); // Título
$pdf->Cell(20,3.1,number_format(array_sum($totalPrecio), 2, ".", ","),1,0,'R'); // Título
$pdf->Ln(5);

//ACCESORIOS
$pdf->SetFont('Arial','B',8);
$pdf->Cell(0,3,"Accesorios",0,0,'C'); // Título
$pdf->Ln();

$ancho = array("10","30","95","15","20","20");
$posiscion = array("C","C","C","C","C","C");
$header = array("Item",utf8_decode("Código"),utf8_decode("Descripción"), utf8_decode("Días/Cant."), "Precio", "Total");
$pdf->SetFont('Arial','B',8);
headerTable($ancho,$posiscion,$header);
$sqlAcc = sprintf("SELECT al_presupuesto_venta_accesorio.id_presupuesto_venta_accesorio, nom_accesorio,des_accesorio, 
	 (CASE acc.id_tipo_accesorio
			WHEN 1
		THEN 'Adicional'
			WHEN 2
		THEN 'Accesorio'
			WHEN 3
		THEN 'Contrato'
	END) AS descripcion_tipo_accesorio, al_presupuesto_venta_accesorio.id_presupuesto_venta,
	al_presupuesto_venta_accesorio.id_accesorio, al_presupuesto_venta_accesorio.id_tipo_accesorio, cantidad, precio
FROM al_presupuesto_venta_accesorio
	INNER JOIN an_accesorio acc ON al_presupuesto_venta_accesorio.id_accesorio = acc.id_accesorio
WHERE id_presupuesto_venta = %s AND acc.id_tipo_accesorio IN (%s) AND id_modulo IN (%s)",
valTpDato($idDocumento, "int"),
valTpDato(2, "int"),// ACCESORIOS
valTpDato(4, "int"));
$rsAcc = mysql_query($sqlAcc);
if (!$rsAcc) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlAcc);
$totalAcc = array();
$itemAcc = 1;
$posiscion = array("C","L","L","C","C","R");
while($rowsAcc = mysql_fetch_array($rsAcc)){
	$totalAcc[] = round($rowsAcc["cantidad"] * $rowsAcc["precio"], 2);
	$tabDatos = array($itemAcc ++,
						ucfirst(strtolower(substr($rowsAcc["nom_accesorio"],0,21))),
						ucfirst(strtolower(substr($rowsAcc["des_accesorio"],0,49))),
						$rowsAcc["cantidad"],
						number_format($rowsAcc["precio"], 2, ".", ","),
						number_format($rowsAcc["cantidad"] * $rowsAcc["precio"], 2, ".", ","));
	$pdf->SetFont('Arial','',8);
	bodyTable($ancho,$posiscion,$tabDatos);
}
$pdf->Ln();
$pdf->SetFont('Arial','B',8);
$pdf->Cell(170,3.1,"total:",1,0,'R'); // Título
$pdf->Cell(20,3.1,number_format(array_sum($totalAcc), 2, ".", ","),1,0,'R'); // Título
$pdf->Ln(5);

//ADICIONALES
$pdf->SetFont('Arial','B',8);
$pdf->Cell(0,3,"Adicionales",0,0,'C'); // Título
$pdf->Ln();

$ancho = array("10","30","95","15","20","20");
$posiscion = array("C","C","C","C","C","C");
$header = array("Item",utf8_decode("Código"),utf8_decode("Descripción"), utf8_decode("Días/Cant."), "Precio", "Total");
$pdf->SetFont('Arial','B',8);
headerTable($ancho,$posiscion,$header);

$sqlAdc = sprintf("SELECT al_presupuesto_venta_accesorio.id_presupuesto_venta_accesorio, nom_accesorio,des_accesorio, 
	 (CASE acc.id_tipo_accesorio
			WHEN 1
		THEN 'Adicional'
			WHEN 2
		THEN 'Accesorio'
			WHEN 3
		THEN 'Contrato'
	END) AS descripcion_tipo_accesorio, al_presupuesto_venta_accesorio.id_presupuesto_venta,
	al_presupuesto_venta_accesorio.id_accesorio, al_presupuesto_venta_accesorio.id_tipo_accesorio, cantidad, precio
FROM al_presupuesto_venta_accesorio
	INNER JOIN an_accesorio acc ON al_presupuesto_venta_accesorio.id_accesorio = acc.id_accesorio
WHERE id_presupuesto_venta = %s AND acc.id_tipo_accesorio IN (%s) AND id_modulo IN (%s)",
valTpDato($idDocumento, "int"),
valTpDato(1, "int"), //ADICIONAL
valTpDato(4, "int"));
$rsAdc = mysql_query($sqlAdc);
if (!$rsAdc) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlAdc);
$totalAdc = array();
$itemAdc = 1;
$posiscion = array("C","L","L","C","C","R");
while($rowsAdc = mysql_fetch_array($rsAdc)){
	$totalAdc[] = round($rowsAdc["cantidad"] * $rowsAdc["precio"], 2);
	$tabDatos = array($itemAdc ++,
						ucfirst(strtolower(substr($rowsAdc["nom_accesorio"],0,21))),
						ucfirst(strtolower(substr($rowsAdc["des_accesorio"],0,49))),
						$rowsAdc["cantidad"],
						number_format($rowsAdc["precio"], 2, ".", ","),
						number_format($rowsAdc["cantidad"] * $rowsAdc["precio"], 2, ".", ","));
	$pdf->SetFont('Arial','',8);
	bodyTable($ancho,$posiscion,$tabDatos);
}
$pdf->Ln();
$pdf->SetFont('Arial','B',8);
$pdf->Cell(170,3.1,"total:",1,0,'R'); // Título
$pdf->Cell(20,3.1,number_format(array_sum($totalAdc), 2, ".", ","),1,0,'R'); // Título

$pdf->Ln();
$pdf->Ln();
$pdf->SetFont('Arial','B',8);
$pdf->Cell(33,3,"Observaciones:",0,0,'L'); // Título

$arrayObservacion = explode("\n", $rowDoc['observacion']);
foreach($arrayObservacion as $observacion){
	$pdf->Ln();
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(20,3,$observacion,0,0,'L'); // Título	
}

$pdf->SetXY(125,195);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(35,3,"Subtotal:",0,0,'R'); // Título
$pdf->Cell(40,3,number_format($rowDoc['subtotal'], 2, ".", ","),0,0,'R'); // Título
$pdf->Ln();

$pdf->SetX(125);
$pdf->Cell(35,3,"Descuento:",0,0,'R'); // Título
$pdf->Cell(40,3,number_format($rowDoc['subtotal_descuento'], 2, ".", ","),0,0,'R'); // Título
$pdf->Ln();

//CONSULTA LOS IVA DEL CONTRATO	
$sqlIvaContrato = sprintf("
	SELECT id_presupuesto_venta_iva, id_presupuesto_venta, observacion, 
		al_presupuesto_venta_iva.id_iva, al_presupuesto_venta_iva.iva,
		al_presupuesto_venta_iva.base_imponible, subtotal_iva
	FROM al_presupuesto_venta_iva 
		INNER JOIN pg_iva ON al_presupuesto_venta_iva.id_iva = pg_iva.idIva
	WHERE id_presupuesto_venta = %s",
valTpDato($rowDoc['id_presupuesto_venta'], "int"));
$rsIvaContrato = mysql_query($sqlIvaContrato);
if (!$rsIvaContrato) die (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$sqlIvaContrato);
$pdf->SetX(125);
while($rowsIvaContrato = mysql_fetch_array($rsIvaContrato)){
	$pdf->Cell(35,3,$rowsIvaContrato['observacion'],0,0,'R'); // Título
	$pdf->Cell(15,3,number_format($rowsIvaContrato['base_imponible'], 2, ".", ","),0,0,'C'); // Título
	$pdf->Cell(10,3,number_format($rowsIvaContrato['iva'], 2, ".", ",")."%",0,0,'C'); // Título
	$pdf->Cell(15,3,number_format($rowsIvaContrato['subtotal_iva'], 2, ".", ","),0,0,'R'); // Título
	$pdf->Ln();
	$pdf->SetX(125);
}
$pdf->SetX(125);
$pdf->Cell(35,3,"Total:",0,0,'R'); // Título
$pdf->Cell(40,3,number_format($rowDoc['total_contrato'], 2, ".", ","),0,0,'R'); // Título
$pdf->Ln();
$pdf->SetX(125);
$pdf->Cell(35,3,"Exento:",0,0,'R'); // Título
$pdf->Cell(40,3,number_format($rowDoc['monto_exonerado'], 2, ".", ","),0,0,'R'); // Título
$pdf->Ln();
$pdf->SetX(125);
$pdf->Cell(35,3,"Exonerado:",0,0,'R'); // Título
$pdf->Cell(40,3,number_format($rowDoc['monto_exento'], 2, ".", ","),0,0,'R'); // Título
$pdf->Ln(5);

$pdf->SetXY(10,218);

$pdf->SetFont('Arial','B',7); 
$pdf->MultiCell(90,3,utf8_decode("Términos y Condiciones\n"),0,'J',false);

$pdf->SetFont('Arial','',7); 
$pdf->MultiCell(90,3,utf8_decode(
	utf8_encode(chr(149))." Con su firma, usted reconoce que ha recibido, leído y está de acuerdo con todas las clausulas escritas en su porta contrato anexado. El mismo es parte integral de su contrato de alquiler.\n\n"),0,'J',false);
	
$pdf->SetFont('Arial','B',7); 
$pdf->MultiCell(90,3,utf8_decode("Condiciones del seguro:\n"),0,'J',false);

$pdf->SetFont('Arial','',7); 
$pdf->MultiCell(90,3,utf8_decode(	
	"Deducibles (  ) $500    (  ) $1500   (  ) Otros  Acepto: ____________\n\n".
	
	utf8_encode(chr(149))." En caso de accidente deberá estar al día con la cuenta, presentar parte policivo, llenar reporte de accidente dentro de las primeras 24 horas ocurrida el mismo para hacer efectivas las coberturas pactadas.\n".
	
	utf8_encode(chr(149))." Al utilizar su tarjeta de crédito, usted autoriza a TOTAL CARRO CORP (Panarental) a cargar a su cuenta todos los cargos generados por este contrato, incluyendo cargos por combustibles, boletas, gastos administrativos, daños imputables al arrendamiento entre otros.\n".
	
	utf8_encode(chr(149))." Con la firma de este contrato, autorizo a TOTAL CARRO CORP (Panarental) consultar, verificar, tramitar y actualizar con mi total consentimiento mi historial de crédito (artículo 40. Ley 24.) Los timbres fiscales son pagados según declaración jurada.\n"),0,'J',false);

$pdf->SetXY(100,218);
$pdf->MultiCell(100,3,utf8_decode(
	utf8_encode(chr(149))." Todo vehículo devuelto en condiciones distintas a las entregadas (sucio excesivo como: lodo, arena, manchas en los asientos, pelo de animal entre otros y/o malos olores estará sujeto a un cargo extra desde $50.00 en adelante. La multa por fumar dentro del vehículo es de $150.\n".
	
	utf8_encode(chr(149))." No habrá devolución por combustible.\n".
	
	utf8_encode(chr(149))." Las boletas no reportadas al cierre del contrato, autorizo me sean cargados.\n".
	
	utf8_encode(chr(149))." Nuestra flota vehicular cuenta con el sistema panapass PREPAGADO. Antes de utilizar cualquier corredor deberá recargar el mismo. Cualquier saldo no utilizado o recargar no será reembolsado. Todo cliente que devuelva el panapass con un saldo menor al que se le entrego al inicio del contrato será responsable de pagar lo consumido más $30.00 de gastos administrativos.\n"),0,'J',false);
	
$pdf->SetXY(100,253);
$pdf->SetFont('Arial','',8);
$pdf->Cell(33,12,"Firma del Arrendatario:",1,0,'R');
$pdf->Cell(67,12,"",1,0,'R');
$pdf->Ln(5);

//$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>