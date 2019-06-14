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

$idPedidoCompra = $valCadBusq[0];

$queryEncabezado = sprintf("SELECT asig.*, ped_comp.*,
	prov.id_proveedor,
	prov.nombre AS nombre_proveedor,
	CONCAT_WS('-', lrif, rif) AS rif_proveedor
FROM an_asignacion asig
	INNER JOIN an_pedido_compra ped_comp ON (asig.idAsignacion = ped_comp.idAsignacion)
	INNER JOIN cp_proveedor prov ON (asig.id_proveedor = prov.id_proveedor)
WHERE ped_comp.idPedidoCompra = %s;",
	valTpDato($idPedidoCompra, "int"));
$rsEncabezado = mysql_query($queryEncabezado);
if (!$rsEncabezado) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_array($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

$posY = 0;
imagestring($img,1,320,$posY,str_pad("PEDIDO DE COMPRA", 32, " ", STR_PAD_BOTH),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,320,$posY,("NRO. PEDIDO"),$textColor);
imagestring($img,1,390,$posY,": ".$rowEncabezado['idPedidoCompra'],$textColor);

$posY += 9;
imagestring($img,1,320,$posY,("FECHA"),$textColor);
imagestring($img,1,390,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_pedido'])),$textColor);

$posY += 9;
imagestring($img,1,320,$posY,("NRO. ASIG."),$textColor);
imagestring($img,1,390,$posY,": ".$rowEncabezado['idAsignacion'],$textColor);

$posY += 9;
imagestring($img,1,320,$posY,("FECHA ASIG."),$textColor);
imagestring($img,1,390,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fecha_asignacion'])),$textColor);

$posY += 9;
imagestring($img,1,320,$posY,("REFERENCIA."),$textColor);
imagestring($img,1,390,$posY,": ".$rowEncabezado['referencia_asignacion'],$textColor);

$posY += 9;
imagestring($img,1,0,$posY,str_pad(("DATOS DEL PROVEEDOR"), 94, " ", STR_PAD_BOTH),$textColor);
		
$posY += 9;
imagestring($img,1,0,$posY,utf8_decode("RAZÓN SOCIAL"),$textColor);
imagestring($img,1,60,$posY,": ".strtoupper($rowEncabezado['nombre_proveedor']),$textColor);
imagestring($img,1,310,$posY,utf8_decode($spanProvCxP),$textColor);
imagestring($img,1,350,$posY,": ".strtoupper($rowEncabezado['rif_proveedor']),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,0,$posY,utf8_decode("ASIGNACIÓN"),$textColor);
imagestring($img,1,60,$posY,": ".strtoupper($rowEncabezado['asunto_asignacion']),$textColor);

$posY = 120;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad(utf8_decode("UNIDAD BASICA"), 14, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,75,$posY,str_pad(utf8_decode("MODELO / VERSIÓN"), 26, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,210,$posY,str_pad(utf8_decode("PLAN DE PAGO"), 36, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,395,$posY,str_pad(utf8_decode("COSTO UNITARIO"), 15, " ", STR_PAD_BOTH),$textColor); // <----
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

$queryDetAsignacion = sprintf("SELECT
	ped_comp_det.idSolicitud,
	ped_comp.idPedidoCompra,
	
	prov.id_proveedor,
	prov.nombre,
	(CASE
		WHEN (descripcionFormaPagoAsignacion IS NULL OR descripcionFormaPagoAsignacion = '') THEN
			prov.nombre
		WHEN (descripcionFormaPagoAsignacion IS NOT NULL AND descripcionFormaPagoAsignacion <> '') THEN
			forma_pago_asig.descripcionFormaPagoAsignacion
	END) AS descripcionFormaPagoAsignacion,
	forma_pago_asig.idFormaPagoAsignacion,
	uni_fis.id_unidad_fisica,
	uni_bas.id_uni_bas,
	uni_bas.nom_uni_bas,
	modelo.nom_modelo,
	vers.nom_version,
	ped_comp_det.costo_unidad,
	ped_comp_det.estado,
	
	(SELECT (an_unidad_fisica.estado_venta + 0) AS estado_venta FROM an_unidad_fisica
	WHERE id_unidad_fisica = uni_fis.id_unidad_fisica) AS estado_venta,
	(SELECT an_unidad_fisica.estado_compra FROM an_unidad_fisica
	WHERE id_unidad_fisica = uni_fis.id_unidad_fisica) AS estado_compra,
	(SELECT an_unidad_fisica.serial_carroceria FROM an_unidad_fisica
	WHERE id_unidad_fisica = uni_fis.id_unidad_fisica) AS serial_carroceria,
	(SELECT an_unidad_fisica.placa FROM an_unidad_fisica
	WHERE id_unidad_fisica = uni_fis.id_unidad_fisica) AS placa,
	(SELECT retencion.idRetencionCabezera
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		INNER JOIN cp_factura fact_comp ON (retencion_det.idFactura = fact_comp.id_factura)
		INNER JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (fact_comp.id_factura = fact_comp_det_unidad.id_factura)
	WHERE fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad
		AND fact_comp.id_modulo = 2
	LIMIT 1) AS idRetencionCabezera
FROM cp_proveedor prov
	RIGHT JOIN formapagoasignacion forma_pago_asig ON (prov.id_proveedor = forma_pago_asig.idProveedor)
	INNER JOIN an_solicitud_factura ped_comp_det ON (forma_pago_asig.idFormaPagoAsignacion = ped_comp_det.idFormaPagoAsignacion)
	INNER JOIN an_uni_bas uni_bas ON (ped_comp_det.idUnidadBasica = uni_bas.id_uni_bas)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
	LEFT JOIN an_unidad_fisica uni_fis ON (ped_comp.idPedidoCompra = uni_fis.id_pedido_compra_detalle)
WHERE ped_comp.idPedidoCompra = %s
ORDER BY uni_bas.id_uni_bas, numero_vehiculo",
	valTpDato($idPedidoCompra, "int"));
$rsDetAsignacion = mysql_query($queryDetAsignacion);
if (!$rsDetAsignacion) die(mysql_error()."<br><br>Line: ".__LINE__);
while ($rowDet = mysql_fetch_array($rsDetAsignacion)) {
	$planPago = ($rowDet['fecha_registro_plan_pago'] != "") ? $rowDet['descripcionFormaPagoAsignacion']." (".$rowDet['fecha_registro_plan_pago'].")" : $rowDet['descripcionFormaPagoAsignacion'];
	
	$posY += 10;
	imagestring($img,1,0,$posY,strtoupper($rowDet['nom_uni_bas']),$textColor);
	imagestring($img,1,75,$posY,strtoupper($rowDet['nom_modelo']),$textColor);
	imagestring($img,1,210,$posY,strtoupper(str_pad($planPago, 32, " ", STR_PAD_BOTH)),$textColor);
	imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowDet['costo_unidad'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	
	$posY += 10;
	imagestring($img,1,75,$posY,strtoupper($rowDet['nom_version']),$textColor);
	
	switch ($rowEncabezado['estatus_asignacion']) {
		case 2 : $totalUnidades += $rowDet['cantidadAceptada']; break;
		case 3 : $totalUnidades += $rowDet['cantidadConfirmada']; break;
	}
}
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

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
if (!$rsConfig2) die(mysql_error()."<br><br>Line: ".__LINE__);
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
		$pdf->Cell(0,10,utf8_decode("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
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