<?php
require_once ("../../connections/conex.php");

$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

$query = sprintf("SELECT 
	retencion.idRetencionCabezera,
	retencion.numeroComprobante,
	retencion.fechaComprobante,
	retencion.anoPeriodoFiscal,
	retencion.mesPeriodoFiscal,
	prov.id_proveedor,
	CONCAT(prov.lrif,'-',prov.rif) AS rif_proveedor,
	prov.nombre,
	vw_iv_emp_suc.id_empresa_reg,
	vw_iv_emp_suc.id_empresa,
	vw_iv_emp_suc.nombre_empresa,
	vw_iv_emp_suc.rif,
	vw_iv_emp_suc.id_empresa_suc,
	vw_iv_emp_suc.nombre_empresa_suc,
	vw_iv_emp_suc.sucursal,
	vw_iv_emp_suc.id_empresa_padre_suc,
	vw_iv_emp_suc.direccion
FROM cp_proveedor prov
	INNER JOIN cp_retencioncabezera retencion ON (prov.id_proveedor = retencion.idProveedor)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (retencion.id_empresa = vw_iv_emp_suc.id_empresa_reg)
WHERE idRetencionCabezera = %s",
	valTpDato($idDocumento,"int"));
$rs = mysql_query($query, $conex);
if (!$rs) return $objResponse->alert(mysql_error()."<br><br>Line: ".__LINE__);
$row = mysql_fetch_assoc($rs);

$img = @imagecreate(1130, 866) or die("No se puede crear la imagen");

//estableciendo los colores de la paleta:
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

imagestring($img,14,10,40,$row['nombre_empresa'],$textColor);

imagestring($img,2,10,80,"COMPROBANTE DE RETENCION - IMPUESTO AL VALOR AGREGADO (I.V.A.)",$textColor);

imagestring($img,1,10,100,"Ley IVA - 11: \"Serán responsables del pago del impuesto en calidad de agentes de retencion, los",$textColor);
imagestring($img,1,10,110,"compradores o adquirientes de determinados bienes muebles o los receptores de ciertos servicios, a",$textColor);
imagestring($img,1,10,120,"quienes las Administración Tributaria designe como tal.\"",$textColor);

////imageline($img,640,105,890,105,$textColor); // <---- LINEA ARRIBA
////imageline($img,640,140,890,140,$textColor); // <---- LINEA ABAJO
//imageline($img,640,105,640,140,$textColor); // <---- LINEA IZQUIERDA
//imageline($img,890,105,890,140,$textColor); // <---- LINEA DERECHA
imagestring($img,2,650,110,"0. NUMERO DE COMPROBANTE",$textColor);
imagestring($img,2,650,125,$row['numeroComprobante'],$textColor); // <----

//imageline($img,960,105,1120,105,$textColor); // <---- LINEA ARRIBA
//imageline($img,960,140,1120,140,$textColor); // <---- LINEA ABAJO
//imageline($img,960,105,960,140,$textColor); // <---- LINEA IZQUIERDA
//imageline($img,1120,105,1120,140,$textColor); // <---- LINEA DERECHA
imagestring($img,2,970,110,"1. FECHA",$textColor);
imagestring($img,2,970,125,date("d-m-Y",strtotime($row['fechaComprobante'])),$textColor); // <----

//imageline($img,10,145,320,145,$textColor); // <---- LINEA ARRIBA
//imageline($img,10,180,320,180,$textColor); // <---- LINEA ABAJO
//imageline($img,10,145,10,180,$textColor); // <---- LINEA IZQUIERDA
//imageline($img,320,145,320,180,$textColor); // <---- LINEA DERECHA
imagestring($img,2,20,150,"2. NOMBRE RAZON SOCIAL DEL AGENTE DE RETENCION",$textColor);
imagestring($img,2,20,165,$row['nombre_empresa'],$textColor); // <----

//imageline($img,400,145,820,145,$textColor); // <---- LINEA ARRIBA
//imageline($img,400,180,820,180,$textColor); // <---- LINEA ABAJO
//imageline($img,400,145,400,180,$textColor); // <---- LINEA IZQUIERDA
//imageline($img,820,145,820,180,$textColor); // <---- LINEA DERECHA
imagestring($img,2,410,150,"3. REGISTRO DE INFORMACION FISCAL DEL AGENTE DE RETENCIÓN",$textColor);
imagestring($img,2,410,165,$row['rif'],$textColor);

//imageline($img,960,145,1120,145,$textColor); // <---- LINEA ARRIBA
//imageline($img,960,180,1120,180,$textColor); // <---- LINEA ABAJO
//imageline($img,960,145,960,180,$textColor); // <---- LINEA IZQUIERDA
//imageline($img,1120,145,1120,180,$textColor); // <---- LINEA DERECHA
imagestring($img,2,970,150,"4. PERIODO FISCAL",$textColor);
imagestring($img,2,970,165,"AÑO: ".$row['anoPeriodoFiscal']." / MES: ".$row['mesPeriodoFiscal'],$textColor); // <----

//imageline($img,10,185,1120,185,$textColor); // <---- LINEA ARRIBA
//imageline($img,10,220,1120,220,$textColor); // <---- LINEA ABAJO
//imageline($img,10,185,10,220,$textColor); // <---- LINEA IZQUIERDA
//imageline($img,1120,185,1120,220,$textColor); // <---- LINEA DERECHA
imagestring($img,2,20,190,"5. DIRECCIÓN FISCAL DEL AGENTE DE RETENCION",$textColor);
imagestring($img,2,20,205,$row['direccion'],$textColor); // <----

//imageline($img,10,225,320,225,$textColor); // <---- LINEA ARRIBA
//imageline($img,10,260,320,260,$textColor); // <---- LINEA ABAJO
//imageline($img,10,225,10,260,$textColor); // <---- LINEA IZQUIERDA
//imageline($img,320,225,320,260,$textColor); // <---- LINEA DERECHA
imagestring($img,2,20,230,"6. NOMBRE O RAZON SOCIAL DEL SUJETO RETENIDO",$textColor);
imagestring($img,2,20,245,$row['nombre'],$textColor); // <----

//imageline($img,400,225,820,225,$textColor); // <---- LINEA ARRIBA
//imageline($img,400,260,820,260,$textColor); // <---- LINEA ABAJO
//imageline($img,400,225,400,260,$textColor); // <---- LINEA IZQUIERDA
//imageline($img,820,225,820,260,$textColor); // <---- LINEA DERECHA
imagestring($img,2,410,230,"7. REGISTRO DE INFORMACIÓN FISCAL DEL SUJETO RETENIDO (R.I.F.)",$textColor);
imagestring($img,2,410,245,$row['rif_proveedor'],$textColor); // <----




imageline($img,10,265,1020,265,$textColor);
imageline($img,1040,265,1120,265,$textColor);
//imageline($img,10,265,10,710,$textColor); // <---- LINEA 1


imagestring($img,2,37,275,"Nº",$textColor);
imagestring($img,2,14,285,"OPERACIÓN",$textColor);

//imageline($img,70,265,70,710,$textColor); // <---- LINEA 2

imagestring($img,2,82,275,"FECHA DE",$textColor);
imagestring($img,2,84,285,"FACTURA",$textColor);

//imageline($img,140,265,140,710,$textColor); // <---- LINEA 3

imagestring($img,2,165,275,"Nº DE",$textColor);
imagestring($img,2,159,285,"FACTURA",$textColor);

//imageline($img,220,265,220,710,$textColor); // <---- LINEA 4

imagestring($img,2,227,275,"Nº DE CONTROL",$textColor);
imagestring($img,2,235,285,"DE FACTURA",$textColor);

//imageline($img,310,265,310,710,$textColor); // <---- LINEA 5

imagestring($img,2,321,275,"Nº DE NOTA",$textColor);
imagestring($img,2,333,285,"DEBITO",$textColor);

//imageline($img,390,265,390,710,$textColor); // <---- LINEA 6

imagestring($img,2,401,275,"Nº DE NOTA",$textColor);
imagestring($img,2,409,285,"CREDITO",$textColor);

//imageline($img,470,265,470,710,$textColor); // <---- LINEA 7

imagestring($img,2,497,275,"TIPO",$textColor);
imagestring($img,2,477,285,"TRANSACCION",$textColor);

//imageline($img,550,265,550,710,$textColor); // <---- LINEA 8

imagestring($img,2,561,275,"Nº FACTURA",$textColor);
imagestring($img,2,566,285,"AFECTADA",$textColor);

//imageline($img,630,265,630,710,$textColor); // <---- LINEA 9

imagestring($img,2,646,270,"TOTAL DE",$textColor);
imagestring($img,2,637,280,"COMPRAS CON",$textColor);
imagestring($img,2,653,290,"I.V.A.",$textColor);

//imageline($img,710,265,710,710,$textColor); // <---- LINEA 10

imagestring($img,2,727,270,"COMPRAS SIN",$textColor);
imagestring($img,2,717,280,"DERECHO A CRED.",$textColor);
imagestring($img,2,740,290,"I.V.A.",$textColor);

//imageline($img,810,265,810,710,$textColor); // <---- LINEA 11

imagestring($img,2,834,275,"BASE",$textColor);
imagestring($img,2,818,285,"IMPONIBLE",$textColor);

//imageline($img,880,265,880,710,$textColor); // <---- LINEA 12

imagestring($img,2,886,275,"PORCENTAJE",$textColor);
imagestring($img,2,892,285,"ALICUOTA",$textColor);

//imageline($img,950,265,950,710,$textColor); // <---- LINEA 13

imagestring($img,2,961,275,"IMPUESTO",$textColor);
imagestring($img,2,969,285,"I.V.A.",$textColor);

//imageline($img,1020,265,1020,710,$textColor); // <---- LINEA 14

//imageline($img,1040,265,1040,710,$textColor); // <---- LINEA 15

imagestring($img,2,1069,275,"I.V.A.",$textColor);
imagestring($img,2,1062,285,"RETENIDO",$textColor);

//imageline($img,1120,265,1120,710,$textColor); // <---- LINEA 16
imageline($img,10,310,1020,310,$textColor);
imageline($img,1040,310,1120,310,$textColor);


$posY = 330;
for ($cont = 1; $cont <= 20; $cont++) {
	//imageline($img,10,$posY,1020,$posY,$textColor);	
	//imageline($img,1040,$posY,1120,$posY,$textColor);
	
	$posY += 20;
}


/* DETALLES DE LAS RETENCIONES */
$queryDet = sprintf("SELECT 
	retencion_det.idRetencionDetalle,
	retencion_det.idRetencionCabezera,
	retencion_det.fechaFactura,
	fac_comp.id_factura,
	fac_comp.numero_factura_proveedor,
	fac_comp.numero_control_factura,
	retencion_det.numeroNotaDebito,
	retencion_det.numeroNotaCredito,
	retencion_det.tipoDeTransaccion,
	retencion_det.numeroFacturaAfectada,
	retencion_det.totalCompraIncluyendoIva,
	retencion_det.comprasSinIva,
	retencion_det.baseImponible,
	sum(retencion_det.porcentajeAlicuota) as porcentajeAlicuota,
	sum(retencion_det.impuestoIva) as impuestoIva,
	sum(retencion_det.IvaRetenido) as IvaRetenido,
	retencion_det.porcentajeRetencion
FROM cp_retenciondetalle retencion_det
	INNER JOIN cp_factura fac_comp ON (retencion_det.idFactura = fac_comp.id_factura)
WHERE retencion_det.idRetencionCabezera = %s
GROUP by idRetencionCabezera",
	valTpDato($idDocumento,"int"));
$rsDet = mysql_query($queryDet, $conex) or die(mysql_error()."<br><br>Line: ".__LINE__);
$posYDet = 315;
$contReg = 1;
$totalIvaRetenido = 0;
while ($rowDet = mysql_fetch_assoc($rsDet)) {
	$anex = (strlen($rowOrdenDetRep['descripcion']) > 30) ? "..." : "";
	
	$totBaseImponible += $rowDet['baseImponible'];
	$totPorcentajeAlicuota += $rowDet['porcentajeAlicuota'];
	$totImpuestoIva += $rowDet['impuestoIva'];
	$totIvaRetenido += $rowDet['IvaRetenido'];
	
	$fechaFactura = date("d-m-Y",strtotime($rowDet['fechaFactura']));
	$comprasConIva = number_format($rowDet['totalCompraIncluyendoIva'],2,".",",");
	$comprasSinIva = number_format($rowDet['comprasSinIva'],2,".",",");
	$baseImponible = number_format($rowDet['baseImponible'],2,".",",");
	$porcentajeAlicuota = number_format($rowDet['porcentajeAlicuota'],2,".",",")."%";
	$impuestoIva = number_format($rowDet['impuestoIva'],2,".",",");
	$ivaRetenido = number_format($rowDet['IvaRetenido'],2,".",",");
	
	
	$posXNumOperacion = 15+((5*10)-(strlen($contReg)*5))/2; // CENTRAR
	$posXFechaFactura = 75+((5*12)-(strlen($fechaFactura)*5))/2; // CENTRAR
	$posXNumeroFactura = 145+((5*14)-(strlen($rowDet['numero_factura_proveedor'])*5))/2; // CENTRAR
	$posXNumeroControlFactura = 225+((5*16)-(strlen($rowDet['numero_control_factura'])*5))/2; // CENTRAR
	$posXNotaDebito = 315+((5*14)-(strlen($rowDet['numeroNotaDebito'])*5))/2; // CENTRAR
	$posXNotaCredito = 395+((5*14)-(strlen($rowDet['numeroNotaCredito'])*5))/2; // CENTRAR
	$posXTipoTransaccion = 475+((5*14)-(strlen($rowDet['tipoDeTransaccion'])*5))/2; // CENTRAR
	$posXFacturaAfectada = 555+((5*14)-(strlen($rowDet['numeroFacturaAfectada'])*5))/2; // CENTRAR
	$posXComprasConIva = 635+((5*14)-(strlen($comprasConIva)*5)); // DERECHA
	$posXComprasSinIva = 715+((5*18)-(strlen($comprasSinIva)*5)); // DERECHA
	$posXBaseImponible = 815+((5*12)-(strlen($baseImponible)*5)); // DERECHA
	$posXPorcentajeAlicuota = 885+((5*12)-(strlen($porcentajeAlicuota)*5)); // DERECHA
	$posXImpuestoIva = 955+((5*12)-(strlen($impuestoIva)*5)); // DERECHA
	$posXIvaRetenido = 1055+((5*12)-(strlen($ivaRetenido)*5)); // DERECHA
	
	
	imagestring($img,1,$posXNumOperacion,$posYDet,$contReg,$textColor); // <----
	imagestring($img,1,$posXFechaFactura,$posYDet,$fechaFactura,$textColor); // <----
	imagestring($img,1,$posXNumeroFactura,$posYDet,$rowDet['numero_factura_proveedor'],$textColor); // <----
	imagestring($img,1,$posXNumeroControlFactura,$posYDet,$rowDet['numero_control_factura'],$textColor); // <----
	imagestring($img,1,$posXNotaDebito,$posYDet,$rowDet['numeroNotaDebito'],$textColor); // <----
	imagestring($img,1,$posXNotaCredito,$posYDet,$rowDet['numeroNotaCredito'],$textColor); // <----
	imagestring($img,1,$posXTipoTransaccion,$posYDet,$rowDet['tipoDeTransaccion'],$textColor); // <----
	imagestring($img,1,$posXFacturaAfectada,$posYDet,$rowDet['numeroFacturaAfectada'],$textColor); // <----
	imagestring($img,1,$posXComprasConIva,$posYDet,$comprasConIva,$textColor); // <----
	imagestring($img,1,$posXComprasSinIva,$posYDet,$comprasSinIva,$textColor); // <----
	imagestring($img,1,$posXBaseImponible,$posYDet,$baseImponible,$textColor); // <----
	imagestring($img,1,$posXPorcentajeAlicuota,$posYDet,$porcentajeAlicuota,$textColor); // <----
	imagestring($img,1,$posXImpuestoIva,$posYDet,$impuestoIva,$textColor); // <----
	imagestring($img,1,$posXIvaRetenido,$posYDet,$ivaRetenido,$textColor); // <----
	
	$contReg++;
	
	$posYDet += 20;
}


imageline($img,10,$posY,1020,$posY,$textColor);
imageline($img,1040,$posY,1120,$posY,$textColor);

//imageline($img,10,$posY,10,$posY+20,$textColor); // <---- LINEA 1
//imageline($img,630,$posY,630,$posY+20,$textColor); // <---- LINEA 9
//imageline($img,710,$posY,710,$posY+20,$textColor); // <---- LINEA 10
//imageline($img,810,$posY,810,$posY+20,$textColor); // <---- LINEA 11
//imageline($img,880,$posY,880,$posY+20,$textColor); // <---- LINEA 12
//imageline($img,950,$posY,950,$posY+20,$textColor); // <---- LINEA 13
//imageline($img,1020,$posY,1020,$posY+20,$textColor); // <---- LINEA 14
//imageline($img,1040,$posY,1040,$posY+20,$textColor); // <---- LINEA 15
//imageline($img,1120,$posY,1120,$posY+20,$textColor); // <---- LINEA 16


$totalBaseImponible = number_format($totBaseImponible,2,".",",");
$totalPorcentajeAlicuota = number_format($totPorcentajeAlicuota,2,".",",")."%";
$totalImpuestoIva = number_format($totImpuestoIva,2,".",",");
$totalIvaRetenido = number_format($totIvaRetenido,2,".",",");

$posXTotalBaseImponible = 815+((5*12)-(strlen($totalBaseImponible)*5)); // DERECHA
$posXTotalPorcentajeAlicuota = 885+((5*12)-(strlen($totalPorcentajeAlicuota)*5)); // DERECHA
$posXTotalImpuestoIva = 955+((5*12)-(strlen($totalImpuestoIva)*5)); // DERECHA
$posXTotalIvaRetenido = 1055+((5*12)-(strlen($totalIvaRetenido)*5)); // DERECHA

imagestring($img,1,$posXTotalBaseImponible,$posY+5,$totalBaseImponible,$textColor); // <----
imagestring($img,1,$posXTotalPorcentajeAlicuota,$posY+5,$totalPorcentajeAlicuota,$textColor); // <----
imagestring($img,1,$posXTotalImpuestoIva,$posY+5,$totalImpuestoIva,$textColor); // <----
imagestring($img,1,$posXTotalIvaRetenido,$posY+5,$totalIvaRetenido,$textColor); // <----


$posY += 20;
//imageline($img,10,$posY,1020,$posY,$textColor);
//imageline($img,1040,$posY,1120,$posY,$textColor);


$posY += 80;
//imageline($img,10,$posY,210,$posY,$textColor);
$posY += 10;
imagestring($img,2,10,$posY,"FIRMA Y SELLO AGENTE DE RETENCION.",$textColor);


$r = imagepng($img,"tmp/"."comprobante_retencion_compra".'.png');


/* ARCHIVO PDF */
require('../clases/fpdf/fpdf.php');
require('../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");

$pdf->AddPage();

$pdf->Image("tmp/comprobante_retencion_compra.png", 10, 10, 770, 590);

$pdf->SetDisplayMode(88);
//$pdf->AutoPrint(true);
$pdf->Output();
?>