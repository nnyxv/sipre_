<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$idAsignacion = $valCadBusq[0];

$queryEncabezado = sprintf("SELECT * FROM an_asignacion
WHERE idAsignacion = %s;",
	valTpDato($idAsignacion, "int"));
$rsEncabezado = mysql_query($queryEncabezado);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_array($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

imagestring($img,1,0,10,strtoupper($rowEmp['ciudad_empresa'].", ".date("d")." de ".$arrayMes[date("n")]." de ".date("Y")),$textColor);
imagestring($img,1,320,10,"SR. LUCAS OUTUMURO",$textColor);

imagestring($img,1,20,50,"ASUNTO: ",$textColor);
imagestring($img,1,60,50,$rowEncabezado['asunto_asignacion'],$textColor);

imagestring($img,1,0,60,"REFERENCIA: ",$textColor);
imagestring($img,1,60,60,$rowEncabezado['referencia_asignacion'],$textColor);

imagestring($img,1,0,80,strtoupper(utf8_decode("A continuación propuesta de asignación para el mes de ".$arrayMes[date("n", strtotime($rowEncabezado['fecha_asignacion']))]." de ".date("Y", strtotime($rowEncabezado['fecha_asignacion'])).". Una vez hecha su")),$textColor);

imagestring($img,1,0,90,strtoupper(utf8_decode("revisión les pedimos por favor firmar y comunicar su aceptación para el ".date("d", strtotime($rowEncabezado['fecha_asignacion']))." de ".$arrayMes[date("n", strtotime($rowEncabezado['fecha_asignacion']))]." de ".date("Y", strtotime($rowEncabezado['fecha_asignacion'])).",")),$textColor);

imagestring($img,1,0,100,strtoupper("la cual se entiende como un compromiso adquirido con la marca."),$textColor);

$posY = 120;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad(utf8_decode("UNIDAD BASICA"), 14, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,75,$posY,str_pad(utf8_decode("MODELO"), 20, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,180,$posY,str_pad(utf8_decode("VERSIÓN"), 26, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,315,$posY,str_pad(utf8_decode("ASIGNADOS"), 9, " ", STR_PAD_BOTH),$textColor); // <----
if ($rowEncabezado['estatus_asignacion'] >= 1) {
	imagestring($img,1,365,$posY,str_pad(utf8_decode("ACEPTADOS"), 9, " ", STR_PAD_BOTH),$textColor); // <----
}
if ($rowEncabezado['estatus_asignacion'] >= 2) {
	imagestring($img,1,415,$posY,str_pad(utf8_decode("CONFIRMADOS"), 11, " ", STR_PAD_BOTH),$textColor); // <----
}
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

$queryDetAsignacion = sprintf("SELECT
	asig.idAsignacion,
	asig.asunto_asignacion,
	asig.estatus_asignacion,
	det_asig.idDetalleAsignacion,
	uni_bas.nom_uni_bas,
	modelo.nom_modelo,
	vers.nom_version,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	det_asig.cantidadAsignada,
	det_asig.cantidadAceptada,
	det_asig.cantidadConfirmada,
	det_asig.flotilla
FROM an_det_asignacion det_asig
	INNER JOIN an_asignacion asig ON (det_asig.idAsignacion = asig.idAsignacion)
	INNER JOIN an_uni_bas uni_bas ON (det_asig.idUnidadesBasicas = uni_bas.id_uni_bas)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	LEFT JOIN cj_cc_cliente cliente ON (det_asig.idCliente = cliente.id)
WHERE asig.idAsignacion = %s
	AND flotilla = 0
ORDER BY flotilla ASC",
	valTpDato($idAsignacion, "int"));
$rsDetAsignacion = mysql_query($queryDetAsignacion);
if (!$rsDetAsignacion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowDetAsignacion = mysql_fetch_array($rsDetAsignacion)) {
	$posY += 10;
	imagestring($img,1,0,$posY,strtoupper($rowDetAsignacion['nom_uni_bas']),$textColor);
	imagestring($img,1,75,$posY,strtoupper($rowDetAsignacion['nom_modelo']),$textColor);
	imagestring($img,1,180,$posY,strtoupper($rowDetAsignacion['nom_version']),$textColor);
	imagestring($img,1,315,$posY,strtoupper(str_pad($rowDetAsignacion['cantidadAsignada'], 9, " ", STR_PAD_LEFT)),$textColor);
	if ($rowEncabezado['estatus_asignacion'] >= 1) {
		imagestring($img,1,365,$posY,strtoupper(str_pad($rowDetAsignacion['cantidadAceptada'], 9, " ", STR_PAD_LEFT)),$textColor);
	}
	if ($rowEncabezado['estatus_asignacion'] >= 2) {
		imagestring($img,1,415,$posY,strtoupper(str_pad($rowDetAsignacion['cantidadConfirmada'], 11, " ", STR_PAD_LEFT)),$textColor);
	}
	
	if ($rowEncabezado['estatus_asignacion'] == 2) {
		$totalUnidades += $rowDetAsignacion['cantidadAceptada'];
	} else if ($rowEncabezado['estatus_asignacion'] == 3) {
		$totalUnidades += $rowDetAsignacion['cantidadConfirmada'];
	}
	
}
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("FLOTILLAS Y VENTAS ESPECIALES:"),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad(utf8_decode("UNIDAD BASICA"), 14, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,75,$posY,str_pad(utf8_decode("MODELO / VERSIÓN"), 20, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,180,$posY,str_pad(utf8_decode("CLIENTE"), 26, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,315,$posY,str_pad(utf8_decode("ASIGNADOS"), 9, " ", STR_PAD_BOTH),$textColor); // <----
if ($rowEncabezado['estatus_asignacion'] >= 1) {
	imagestring($img,1,365,$posY,str_pad(utf8_decode("ACEPTADOS"), 9, " ", STR_PAD_BOTH),$textColor); // <----
}
if ($rowEncabezado['estatus_asignacion'] >= 2) {
	imagestring($img,1,415,$posY,str_pad(utf8_decode("CONFIRMADOS"), 11, " ", STR_PAD_BOTH),$textColor); // <----
}
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

$queryDetAsignacion = sprintf("SELECT
	asig.idAsignacion,
	asig.asunto_asignacion,
	asig.estatus_asignacion,
	det_asig.idDetalleAsignacion,
	uni_bas.nom_uni_bas,
	modelo.nom_modelo,
	vers.nom_version,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	det_asig.cantidadAsignada,
	det_asig.cantidadAceptada,
	det_asig.cantidadConfirmada,
	det_asig.flotilla
FROM an_det_asignacion det_asig
	INNER JOIN an_asignacion asig ON (det_asig.idAsignacion = asig.idAsignacion)
	INNER JOIN an_uni_bas uni_bas ON (det_asig.idUnidadesBasicas = uni_bas.id_uni_bas)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	LEFT JOIN cj_cc_cliente cliente ON (det_asig.idCliente = cliente.id)
WHERE asig.idAsignacion = %s
	AND flotilla = 1
ORDER BY flotilla ASC",
	valTpDato($idAsignacion, "int"));
$rsDetAsignacion = mysql_query($queryDetAsignacion);
if (!$rsDetAsignacion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while ($rowDetAsignacion = mysql_fetch_array($rsDetAsignacion)) {
	$posY += 10;
	imagestring($img,1,0,$posY,strtoupper($rowDetAsignacion['nom_uni_bas']),$textColor);
	imagestring($img,1,75,$posY,strtoupper($rowDetAsignacion['nom_modelo']),$textColor);
	imagestring($img,1,180,$posY,strtoupper($rowDetAsignacion['nombre_cliente']),$textColor);
	imagestring($img,1,315,$posY,strtoupper(str_pad($rowDetAsignacion['cantidadAsignada'], 9, " ", STR_PAD_LEFT)),$textColor);
	if ($rowEncabezado['estatus_asignacion'] >= 1) {
		imagestring($img,1,365,$posY,strtoupper(str_pad($rowDetAsignacion['cantidadAceptada'], 9, " ", STR_PAD_LEFT)),$textColor);
	}
	if ($rowEncabezado['estatus_asignacion'] >= 2) {
		imagestring($img,1,415,$posY,strtoupper(str_pad($rowDetAsignacion['cantidadConfirmada'], 11, " ", STR_PAD_LEFT)),$textColor);
	}
	
	$posY += 10;
	imagestring($img,1,75,$posY,strtoupper($rowDetAsignacion['nom_version']),$textColor);
	
	if ($rowEncabezado['estatus_asignacion'] == 2) {
		$totalUnidades += $rowDetAsignacion['cantidadAceptada'];
	} else if ($rowEncabezado['estatus_asignacion'] == 3) {
		$totalUnidades += $rowDetAsignacion['cantidadConfirmada'];
	}
}
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,0,$posY,strtoupper(utf8_decode("TOTAL ASIGNACIÓN CONFIRMADA: ").$totalUnidades." UNIDADES"),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("Asimismo Prestamos las siguientes condiciones al respecto: "),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,20,$posY,strtoupper("- EL CIERRE DE COMPRAS ES EL: ".date(spanDateFormat, strtotime($rowEncabezado['fecha_cierre_compra']))),$textColor);

$posY += 9;
imagestring($img,1,20,$posY,strtoupper("- EL CIERRE DE VENTAS A PUBLICO ES EL: ".date(spanDateFormat, strtotime($rowEncabezado['fecha_cierre_venta']))),$textColor);

$posY = 500;
imagestring($img,1,0,$posY,strtoupper("Muchas Gracias por la pronta respuesta"),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,0,$posY,strtoupper("Cordialmente,"),$textColor);

$queryCarta = sprintf("SELECT * FROM pg_encabezadoypiecartas
WHERE idEncabezadoYpie = %s;",
	valTpDato(1, "int"));
$rsCarta = mysql_query($queryCarta);
if (!$rsCarta) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowCarta = mysql_fetch_array($rsCarta);

$posY += 9;
imagestring($img,1,20,$posY,strtoupper($rowCarta['personaEncargada']),$textColor);
$posY += 9;
imagestring($img,1,20,$posY,strtoupper($rowCarta['cargoPersonaEncargada']),$textColor);
$posY += 9;
imagestring($img,1,20,$posY,strtoupper($rowCarta['empresa']),$textColor);
 
$arrayImg[] = "tmp/"."asignacion_vehiculo".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig2 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10
	AND config_emp.status = 1
	AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig2 = mysql_query($queryConfig2, $conex);
if (!$rsConfig2) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig2 = mysql_num_rows($rsConfig2);
$rowConfig2 = mysql_fetch_assoc($rsConfig2);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		// CABECERA DEL DOCUMENTO 
		if ($idEmpresa != "") {
			if (strlen($rowEmp['logo_familia']) > 5) {
				$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
			}
			
			$pdf->SetY(15);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',5);
			$pdf->SetX(88);
			$pdf->Cell(200,9,$rowEmp['nombre_empresa'],0,2,'L');
			
			if (strlen($rowEmp['rif']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_encode($spanRIF.": ".$rowEmp['rif']),0,2,'L');
			}
			if (strlen($rowEmp['direccion']) > 1) {
				$direcEmpresa = $rowEmp['direccion'].".";
				$telfEmpresa = "";
				if (strlen($rowEmp['telefono1']) > 1) {
					$telfEmpresa .= "Telf.: ".$rowEmp['telefono1'];
				}
				if (strlen($rowEmp['telefono2']) > 1) {
					$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
					$telfEmpresa .= $rowEmp['telefono2'];
				}
				
				$pdf->SetX(88);
				$pdf->Cell(100,9,$direcEmpresa." ".$telfEmpresa,0,2,'L');
			}
			if (strlen($rowEmp['web']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_encode($rowEmp['web']),0,0,'L');
				$pdf->Ln();
			}
		}
		//$pdf->SetY(-20);
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 688);
		
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',6);
		$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',8);
		$pdf->Cell(0,10,("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		if(file_exists($valor)) unlink($valor);
	}
}
?>