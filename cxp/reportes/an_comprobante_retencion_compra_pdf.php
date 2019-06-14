<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valBusq2 = $_GET["valBusq2"];

$valCadBusq = explode("|", $valBusq);
$valCadBusq2 = explode("|", $valBusq2);

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("retencion.idRetencionCabezera = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq2[0] != "-1" && $valCadBusq2[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(retencion.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = retencion.id_empresa))",
		valTpDato($valCadBusq2[0], "int"),
		valTpDato($valCadBusq2[0], "int"));
}

if ($valCadBusq2[1] != "" && $valCadBusq2[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("prov.id_proveedor = %s",
		valTpDato($valCadBusq2[1],"int"));
}

if ($valCadBusq2[2] != "" && $valCadBusq2[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("retencion.fechaComprobante BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq2[2])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq2[3])),"date"));
}

if ($valCadBusq2[4] != "-1" && $valCadBusq2[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CASE tipoDeTransaccion
	WHEN '01' OR '1' THEN
		(SELECT cxp_fact.id_modulo FROM cp_factura cxp_fact
		WHERE cxp_fact.id_factura = retencion_det.idFactura)
	WHEN '02' OR '2' THEN
		(SELECT cxp_nd.id_modulo FROM cp_notadecargo cxp_nd
		WHERE cxp_nd.id_notacargo = retencion_det.id_nota_cargo)
	WHEN '03' OR '3' THEN
		(SELECT cxp_nc.id_departamento_notacredito FROM cp_notacredito cxp_nc
		WHERE cxp_nc.id_notacredito = retencion_det.id_nota_credito)
	END) IN (%s)",
		valTpDato($valCadBusq2[4], "campo"));
}

if ($valCadBusq2[5] != "-1" && $valCadBusq2[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
	OR prov.nombre LIKE %s
	OR retencion.numeroComprobante LIKE %s
	OR (CASE tipoDeTransaccion
			WHEN '01' OR '1' THEN
				(SELECT cxp_fact.numero_factura_proveedor FROM cp_factura cxp_fact
				WHERE cxp_fact.id_factura = retencion_det.idFactura)
			WHEN '02' OR '2' THEN
				(SELECT cxp_nd.numero_notacargo FROM cp_notadecargo cxp_nd
				WHERE cxp_nd.id_notacargo = retencion_det.id_nota_cargo)
			WHEN '03' OR '3' THEN
				(SELECT cxp_nc.numero_nota_credito FROM cp_notacredito cxp_nc
				WHERE cxp_nc.id_notacredito = retencion_det.id_nota_credito)
		END) LIKE %s
	OR (CASE tipoDeTransaccion
			WHEN '01' OR '1' THEN
				(SELECT cxp_fact.numero_control_factura FROM cp_factura cxp_fact
				WHERE cxp_fact.id_factura = retencion_det.idFactura)
			WHEN '02' OR '2' THEN
				(SELECT cxp_nd.numero_control_notacargo FROM cp_notadecargo cxp_nd
				WHERE cxp_nd.id_notacargo = retencion_det.id_nota_cargo)
			WHEN '03' OR '3' THEN
				(SELECT cxp_nc.numero_control_notacredito FROM cp_notacredito cxp_nc
				WHERE cxp_nc.id_notacredito = retencion_det.id_nota_credito)
		END) LIKE %s)",
		valTpDato("%".$valCadBusq2[5]."%", "text"),
		valTpDato("%".$valCadBusq2[5]."%", "text"),
		valTpDato("%".$valCadBusq2[5]."%", "text"),
		valTpDato("%".$valCadBusq2[5]."%", "text"),
		valTpDato("%".$valCadBusq2[5]."%", "text"));
}

$query = sprintf("SELECT DISTINCT
	retencion.idRetencionCabezera,
	retencion.numeroComprobante,
	retencion.fechaComprobante,
	retencion.anoPeriodoFiscal,
	retencion.mesPeriodoFiscal,
	prov.id_proveedor,
	CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
	CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
	prov.nombre AS nombre_proveedor,
	vw_iv_emp_suc.id_empresa_reg,
	vw_iv_emp_suc.id_empresa,
	vw_iv_emp_suc.nombre_empresa,
	vw_iv_emp_suc.rif,
	vw_iv_emp_suc.id_empresa_suc,
	vw_iv_emp_suc.nombre_empresa_suc,
	vw_iv_emp_suc.sucursal,
	vw_iv_emp_suc.id_empresa_padre_suc,
	vw_iv_emp_suc.direccion,
	rec.id_retenciones as reconversion,
	vw_iv_emp_suc.ruta_firma_digital
FROM cp_retencioncabezera retencion
	INNER JOIN cp_proveedor prov ON (retencion.idProveedor = prov.id_proveedor)
	LEFT JOIN cp_reconversion rec on (retencion.idRetencionCabezera = rec.id_retenciones)

	INNER JOIN cp_retenciondetalle retencion_det ON (retencion.idRetencionCabezera = retencion_det.idRetencionCabezera)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (retencion.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY idRetencionCabezera DESC", $sqlBusq);
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$pageNum = 0;
while ($row = mysql_fetch_assoc($rs)){
	$pageNum++;
	
	$idDocumento = $row['idRetencionCabezera'];
	$rutaFirma = $row['ruta_firma_digital'];
	
	$img = @imagecreate(1130, 866) or die("No se puede crear la imagen");
	//estableciendo los colores de la paleta:
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
	$textColor = imagecolorallocate($img, 0, 0, 0);
	
	imagestring($img,14,10,40,$row['nombre_empresa'],$textColor);
	
	imagestring($img,2,10,80,"COMPROBANTE DE RETENCION - IMPUESTO AL VALOR AGREGADO (I.V.A.)",$textColor);
	
	imagestring($img,1,10,100,"Ley IVA - 11: \"Serán responsables del pago del impuesto en calidad de agentes de retencion, los",$textColor);
	imagestring($img,1,10,110,"compradores o adquirientes de determinados bienes muebles o los receptores de ciertos servicios, a",$textColor);
	imagestring($img,1,10,120,"quienes las Administración Tributaria designe como tal.\"",$textColor);
	
	//imageline($img,640,105,890,105,$textColor); // <---- LINEA ARRIBA
	//imageline($img,640,140,890,140,$textColor); // <---- LINEA ABAJO
	//imageline($img,640,105,640,140,$textColor); // <---- LINEA IZQUIERDA
	//imageline($img,890,105,890,140,$textColor); // <---- LINEA DERECHA
	imagestring($img,2,650,110,"0. NUMERO DE COMPROBANTE",$textColor);
	imagestring($img,2,650,125,$row['numeroComprobante'],$textColor); // <----
	
	//imageline($img,960,105,1120,105,$textColor); // <---- LINEA ARRIBA
	//imageline($img,960,140,1120,140,$textColor); // <---- LINEA ABAJO
	//imageline($img,960,105,960,140,$textColor); // <---- LINEA IZQUIERDA
	//imageline($img,1120,105,1120,140,$textColor); // <---- LINEA DERECHA
	imagestring($img,2,970,110,"1. FECHA",$textColor);
	imagestring($img,2,970,125,date(spanDateFormat, strtotime($row['fechaComprobante'])),$textColor); // <----
	
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
	imagestring($img,2,20,245,$row['nombre_proveedor'],$textColor); // <----
	
	//imageline($img,400,225,820,225,$textColor); // <---- LINEA ARRIBA
	//imageline($img,400,260,820,260,$textColor); // <---- LINEA ABAJO
	//imageline($img,400,225,400,260,$textColor); // <---- LINEA IZQUIERDA
	//imageline($img,820,225,820,260,$textColor); // <---- LINEA DERECHA
	imagestring($img,2,410,230,"7. REGISTRO DE INFORMACIÓN FISCAL DEL SUJETO RETENIDO (".$spanRIF.")",$textColor);
	imagestring($img,2,410,245,$row['rif_proveedor'],$textColor); // <----
	
	
	imageline($img,0,265,1040,265,$textColor);
	imageline($img,1050,265,1130,265,$textColor);
	//imageline($img,10,265,10,710,$textColor); // <---- LINEA 1
	
	imagestring($img,2,0,275,str_pad(("Nº"), 10, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,0,285,str_pad(("OPERACIÓN"), 10, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,65,275,str_pad(("FECHA DE"), 10, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,65,285,str_pad(("FACTURA"), 10, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,130,275,str_pad(("Nº DE"), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,130,285,str_pad(("FACTURA"), 14, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,215,275,str_pad(("Nº DE CONTROL"), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,215,285,str_pad(("DE FACTURA"), 14, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,300,275,str_pad(("Nº DE NOTA"), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,300,285,str_pad(("DEBITO"), 14, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,385,275,str_pad(("Nº DE NOTA"), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,385,285,str_pad(("CREDITO"), 14, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,470,275,str_pad(("TIPO"), 12, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,470,285,str_pad(("TRANSACCION"), 12, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,545,275,str_pad(("Nº FACTURA"), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,545,285,str_pad(("AFECTADA"), 14, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,630,270,str_pad(("TOTAL DE"), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,630,280,str_pad(("COMPRAS CON"), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,630,290,str_pad(("I.V.A."), 14, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,715,270,str_pad(("COMPRAS SIN"), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,715,280,str_pad(("DERECHO A CRED."), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,715,290,str_pad(("I.V.A."), 14, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,800,275,str_pad(("BASE"), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,800,285,str_pad(("IMPONIBLE"), 14, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,885,275,str_pad(("PORCENTAJE"), 12, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,885,285,str_pad(("ALICUOTA"), 12, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,960,275,str_pad(("IMPUESTO"), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,960,285,str_pad(("I.V.A."), 14, " ", STR_PAD_BOTH),$textColor);
	
	imagestring($img,2,1050,275,str_pad(("I.V.A."), 14, " ", STR_PAD_BOTH),$textColor);
	imagestring($img,2,1050,285,str_pad(("RETENIDO"), 14, " ", STR_PAD_BOTH),$textColor);
	
	//imageline($img,1120,265,1120,710,$textColor); // <---- LINEA 16
	imageline($img,0,310,1040,310,$textColor);
	imageline($img,1050,310,1130,310,$textColor);
	
	imagestring($img,1,0,300,str_pad("", 226, " ", STR_PAD_BOTH),$textColor);
	$posY = 310;
	for ($cont = 1; $cont <= 20; $cont++) {
		$posY += 20;
		
		/*imageline($img,0,$posY,1040,$posY,$textColor);	
		imageline($img,1050,$posY,1130,$posY,$textColor);*/
	}
	
	
	// DETALLES DE LAS RETENCIONES
	$queryDet = sprintf("SELECT 
		retencion_det.idRetencionDetalle,
		retencion_det.idRetencionCabezera,
		retencion_det.fechaFactura,
		cxp_fact.id_factura,
		IF (retencion_det.id_nota_cargo IS NULL AND retencion_det.id_nota_credito IS NULL, cxp_fact.numero_factura_proveedor, '') AS numero_factura_proveedor,
		(CASE
			WHEN retencion_det.id_nota_cargo IS NOT NULL THEN
				cxp_nd.numero_control_notacargo
			WHEN retencion_det.id_nota_credito IS NOT NULL THEN
				cxp_nc.numero_control_notacredito
			ELSE
				cxp_fact.numero_control_factura
		END) AS numero_control_factura,
		retencion_det.id_nota_cargo,
		cxp_nd.numero_notacargo,
		retencion_det.id_nota_credito,
		cxp_nc.numero_nota_credito,
		retencion_det.tipoDeTransaccion,
		IF (retencion_det.id_nota_cargo IS NULL AND retencion_det.id_nota_credito IS NULL, '', cxp_fact.numero_factura_proveedor) AS numeroFacturaAfectada,
		retencion_det.totalCompraIncluyendoIva,
		retencion_det.comprasSinIva,
		retencion_det.baseImponible,
		SUM(retencion_det.porcentajeAlicuota) as porcentajeAlicuota,
		SUM(retencion_det.impuestoIva) as impuestoIva,
		SUM(retencion_det.IvaRetenido) as IvaRetenido,
		retencion_det.porcentajeRetencion
	FROM cp_retenciondetalle retencion_det
		INNER JOIN cp_factura cxp_fact ON (retencion_det.idFactura = cxp_fact.id_factura)
		LEFT JOIN cp_notacredito cxp_nc ON (retencion_det.id_nota_credito = cxp_nc.id_notacredito)
		LEFT JOIN cp_notadecargo cxp_nd ON (retencion_det.id_nota_cargo = cxp_nd.id_notacargo)
	WHERE retencion_det.idRetencionCabezera = %s
	GROUP by retencion_det.idRetencionCabezera, cxp_fact.id_factura, retencion_det.id_nota_cargo, retencion_det.id_nota_credito",
		valTpDato($idDocumento,"int"));
	$rsDet = mysql_query($queryDet, $conex);
	if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$posYDet = 295;
	$contReg = 0;
	$totBaseImponible = 0;
	$totPorcentajeAlicuota = 0;
	$totImpuestoIva = 0;
	$totIvaRetenido = 0;
	while ($rowDet = mysql_fetch_assoc($rsDet)) {
		$contReg++;
		
		$posYDet += 20;
		imagestring($img,1,0,$posYDet,str_pad($contReg, 12, " ", STR_PAD_BOTH),$textColor); // <----
		imagestring($img,1,65,$posYDet,str_pad(date(spanDateFormat, strtotime($rowDet['fechaFactura'])), 12, " ", STR_PAD_BOTH),$textColor); // <----
		imagestring($img,1,130,$posYDet,str_pad($rowDet['numero_factura_proveedor'], 16, " ", STR_PAD_BOTH),$textColor); // <----
		imagestring($img,1,215,$posYDet,str_pad($rowDet['numero_control_factura'], 16, " ", STR_PAD_BOTH),$textColor); // <----
		imagestring($img,1,300,$posYDet,str_pad($rowDet['numero_notacargo'], 16, " ", STR_PAD_BOTH),$textColor); // <----
		imagestring($img,1,385,$posYDet,str_pad($rowDet['numero_nota_credito'], 16, " ", STR_PAD_BOTH),$textColor); // <----
		imagestring($img,1,470,$posYDet,str_pad($rowDet['tipoDeTransaccion'], 14, " ", STR_PAD_BOTH),$textColor); // <----
		imagestring($img,1,545,$posYDet,str_pad($rowDet['numeroFacturaAfectada'], 16, " ", STR_PAD_BOTH),$textColor); // <----
		imagestring($img,1,630,$posYDet,str_pad(number_format($rowDet['totalCompraIncluyendoIva'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,715,$posYDet,str_pad(number_format($rowDet['comprasSinIva'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,800,$posYDet,str_pad(number_format($rowDet['baseImponible'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,885,$posYDet,str_pad(number_format($rowDet['porcentajeAlicuota'], 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,960,$posYDet,str_pad(number_format($rowDet['impuestoIva'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,1050,$posYDet,str_pad(number_format($rowDet['IvaRetenido'], 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor);
		
		$totBaseImponible += $rowDet['baseImponible'];
		$totPorcentajeAlicuota += $rowDet['porcentajeAlicuota'];
		$totImpuestoIva += $rowDet['impuestoIva'];
		$totIvaRetenido += $rowDet['IvaRetenido'];
	}
	
	
	
	
	//$row['fechaComprobante']
	//Se agrega informción de Bolívares Soberanos - Reconversión Monetaria 2018, quitar cuando sea requerido///////////////////
	if ($row['reconversion']== NULL) {
		if($row['fechaComprobante']	>='2018-08-01' and $row['fechaComprobante']	<'2018-08-20'){
		$posY += 20;
		imageline($img,0,$posY,1040,$posY,$textColor);
		imageline($img,1050,$posY,1130,$posY,$textColor);
		imagestring($img,1,800,$posY+5,str_pad("(Bs)".number_format($totBaseImponible, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,885,$posY+5,str_pad(number_format($totPorcentajeAlicuota, 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,960,$posY+5,str_pad(number_format($totImpuestoIva, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,1050,$posY+5,str_pad(number_format($totIvaRetenido, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		$posY += 9;
		imagestring($img,1,800,$posY+5,str_pad("(Bs.S)".number_format($totBaseImponible/100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,885,$posY+5,str_pad(number_format($totPorcentajeAlicuota, 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,960,$posY+5,str_pad(number_format($totImpuestoIva/100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,1050,$posY+5,str_pad(number_format($totIvaRetenido/100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
	}else if($row['fechaComprobante']	>='2018-08-20'){
		$posY += 20;
		imageline($img,0,$posY,1040,$posY,$textColor);
		imageline($img,1050,$posY,1130,$posY,$textColor);
		imagestring($img,1,800,$posY+5,str_pad("(Bs.S)".number_format($totBaseImponible, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,885,$posY+5,str_pad(number_format($totPorcentajeAlicuota, 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,960,$posY+5,str_pad(number_format($totImpuestoIva, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,1050,$posY+5,str_pad(number_format($totIvaRetenido, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		$posY += 20;
		imagestring($img,1,800,$posY+5,str_pad("(Bs)".number_format($totBaseImponible*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,885,$posY+5,str_pad(number_format($totPorcentajeAlicuota, 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,960,$posY+5,str_pad(number_format($totImpuestoIva*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,1050,$posY+5,str_pad(number_format($totIvaRetenido*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
	}else{
		$posY += 20;
		imageline($img,0,$posY,1040,$posY,$textColor);
		imageline($img,1050,$posY,1130,$posY,$textColor);
		imagestring($img,1,800,$posY+5,str_pad("(Bs)".number_format($totBaseImponible, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,885,$posY+5,str_pad(number_format($totPorcentajeAlicuota, 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,960,$posY+5,str_pad(number_format($totImpuestoIva, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,1050,$posY+5,str_pad(number_format($totIvaRetenido, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
	}
	}else{
		if($row['fechaComprobante']	>='2018-08-01' and $row['fechaComprobante']	<'2018-08-20'){
		$posY += 20;
		imageline($img,0,$posY,1040,$posY,$textColor);
		imageline($img,1050,$posY,1130,$posY,$textColor);
		imagestring($img,1,800,$posY+5,str_pad("(Bs)".number_format($totBaseImponible*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,885,$posY+5,str_pad(number_format($totPorcentajeAlicuota, 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,960,$posY+5,str_pad(number_format($totImpuestoIva*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,1050,$posY+5,str_pad(number_format($totIvaRetenido*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		$posY += 9;
		imagestring($img,1,800,$posY+5,str_pad("(Bs.S)".number_format($totBaseImponible, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,885,$posY+5,str_pad(number_format($totPorcentajeAlicuota, 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,960,$posY+5,str_pad(number_format($totImpuestoIva, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,1050,$posY+5,str_pad(number_format($totIvaRetenido, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
	}else if($row['fechaComprobante']	>='2018-08-20'){
		$posY += 20;
		imageline($img,0,$posY,1040,$posY,$textColor);
		imageline($img,1050,$posY,1130,$posY,$textColor);
		imagestring($img,1,800,$posY+5,str_pad("(Bs.S)".number_format($totBaseImponible, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,885,$posY+5,str_pad(number_format($totPorcentajeAlicuota, 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,960,$posY+5,str_pad(number_format($totImpuestoIva, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,1050,$posY+5,str_pad(number_format($totIvaRetenido, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		$posY += 20;
		imagestring($img,1,800,$posY+5,str_pad("(Bs)".number_format($totBaseImponible*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,885,$posY+5,str_pad(number_format($totPorcentajeAlicuota, 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,960,$posY+5,str_pad(number_format($totImpuestoIva*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,1050,$posY+5,str_pad(number_format($totIvaRetenido*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
	}else{
		$posY += 20;
		imageline($img,0,$posY,1040,$posY,$textColor);
		imageline($img,1050,$posY,1130,$posY,$textColor);
		imagestring($img,1,800,$posY+5,str_pad("(Bs)".number_format($totBaseImponible*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,885,$posY+5,str_pad(number_format($totPorcentajeAlicuota, 2, ".", ",")."%", 14, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,960,$posY+5,str_pad(number_format($totImpuestoIva*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
		imagestring($img,1,1050,$posY+5,str_pad(number_format($totIvaRetenido*100000, 2, ".", ","), 16, " ", STR_PAD_LEFT),$textColor); // <----
	}


	}
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	// Nombre Gte. Administracion en Firma y Sello
	$queryAdmin = sprintf("SELECT * FROM vw_pg_empleados WHERE id_cargo_departamento = 122 AND activo = 1;");
	$rsAdmin = mysql_query($queryAdmin, $conex);
	if (!$rsAdmin) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowAdmin = mysql_fetch_array($rsAdmin);
	
	if($row['fechaComprobante']>='2018-08-01'){
		$posY += 90;
	}else{
		$posY += 100;
	}
	($_GET["lstAdministradoraPDF"] == "1") ? imageline($img,10,$posY,210,$posY,$textColor) : "";
	$posY += 10;
	($_GET["lstAdministradoraPDF"] == "1") ? imagestring($img,2,70,$posY-30,strtoupper($rowAdmin['nombre_empleado']),$textColor) : "";
	imagestring($img,2,10,$posY,"FIRMA Y SELLO AGENTE DE RETENCION.",$textColor);
	
	$arrayImg[] = "tmp/"."comprobante_retencion_compra".$pageNum.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
}


if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 10, 10, 770, 590);
		($_GET["lstAdministradoraPDF"] == "1" && is_file("../../".$rutaFirma)) ? $pdf->Image("../../".$rutaFirma,48,470,75) : "";
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