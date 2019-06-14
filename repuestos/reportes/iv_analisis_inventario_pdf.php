<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idEmpresa = $valCadBusq[0];
$idCierreMensual = $valCadBusq[1];

$maxRows = 45;
$campOrd = "clasificacion";
$tpOrd = "DESC";

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre_mensual.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cierre_mensual.id_cierre_mensual = %s",
		valTpDato($valCadBusq[1], "int"));
}
	
$queryAnalisisInv = sprintf("SELECT *
FROM iv_analisis_inventario analisis_inv
	INNER JOIN iv_cierre_mensual cierre_mensual ON (analisis_inv.id_cierre_mensual = cierre_mensual.id_cierre_mensual) %s", $sqlBusq);
$rsAnalisisInv = mysql_query($queryAnalisisInv);
if (!$rsAnalisisInv) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowAnalisisInv = mysql_fetch_assoc($rsAnalisisInv);
$idAnalisisInv = $rowAnalisisInv['id_analisis_inventario'];

$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("analisis_inv.id_analisis_inventario = %s",
	valTpDato($idAnalisisInv, "int"));

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != ""
&& ($valCadBusq[3] == "-1" || $valCadBusq[3] == "")) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("analisis_inv_det.cantidad_existencia > 0");
}

if (($valCadBusq[2] == "-1" || $valCadBusq[2] == "")
&& $valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("analisis_inv_det.cantidad_existencia <= 0");
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("analisis_inv_det.clasificacion LIKE %s",
		valTpDato($valCadBusq[4], "text"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[5], "text"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(analisis_inv_det.id_articulo = %s
	OR art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s)",
		valTpDato($valCadBusq[6], "int"),
		valTpDato("%".$valCadBusq[6]."%", "text"),
		valTpDato("%".$valCadBusq[6]."%", "text"));
}

$query = sprintf("SELECT
	analisis_inv_det.id_analisis_inventario_detalle,
	analisis_inv_det.id_analisis_inventario,
	analisis_inv_det.id_articulo,
	analisis_inv_det.cantidad_existencia,
	analisis_inv_det.cantidad_disponible_logica,
	analisis_inv_det.cantidad_disponible_fisica,
	analisis_inv_det.costo,
	(analisis_inv_det.costo * analisis_inv_det.cantidad_existencia) AS costo_total,
	(analisis_inv_det.cantidad_existencia / analisis_inv_det.promedio_mensual) AS meses_existencia,
	analisis_inv_det.promedio_diario,
	analisis_inv_det.promedio_mensual,
	(analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario) AS inventario_recomendado,
	(analisis_inv_det.cantidad_existencia - (analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario)) AS sobre_stock,
	((analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario) - analisis_inv_det.cantidad_existencia) AS sugerido,
	analisis_inv_det.clasificacion,
	art.codigo_articulo,
	art.codigo_articulo_prov,
	art.descripcion
FROM iv_cierre_mensual cierre_mensual
	INNER JOIN iv_analisis_inventario analisis_inv ON (cierre_mensual.id_cierre_mensual = analisis_inv.id_cierre_mensual)
	INNER JOIN iv_analisis_inventario_detalle analisis_inv_det ON (analisis_inv.id_analisis_inventario = analisis_inv_det.id_analisis_inventario)
	INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo) %s", $sqlBusq);

$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
$rsLimit = mysql_query($queryLimit);
if (!$rsLimit) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
if ($totalRows == NULL) {
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
}
$totalPages = ceil($totalRows/$maxRows)-1;

$arrayImg = NULL;
$contFila = 0;
for ($pageNum = 0; $pageNum <= $totalPages; $pageNum++) {
	$startRow = $pageNum * $maxRows;
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit, $conex);
	if (!$rsLimit) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query, $conex);
		if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		if (fmod($contFila, $maxRows) == 1) {
			$img = @imagecreate(760, 520) or die("No se puede crear la imagen");
			
			// ESTABLECIENDO LOS COLORES DE LA PALETA
			$backgroundColor = imagecolorallocate($img, 255, 255, 255);
			$textColor = imagecolorallocate($img, 0, 0, 0);
			$backgroundGris = imagecolorallocate($img, 230, 230, 230);
			$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
			
			$posY = 10;
			imagestring($img,1,285,$posY,"ANÁLISIS DE INVENTARIO AL ".str_pad($rowAnalisisInv['mes'], 2, "0", STR_PAD_LEFT)."-".$rowAnalisisInv['ano'],$textColor);
			
			$posY += 20;
			imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagefilledrectangle($img, 0, $posY-4, 759, $posY+4+9, $backgroundGris);
			imagestring($img,1,0,$posY,str_pad(("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,115,$posY,str_pad(("DESCRIPCIÓN"), 24, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,240,$posY,str_pad(("COD. PROV."), 16, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,325,$posY,str_pad(("EXIST."), 7, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,365,$posY,str_pad(("COSTO UNIT."), 12, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,430,$posY,str_pad(("COSTO TOT."), 12, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,495,$posY,str_pad(("MESES"), 7, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,535,$posY,str_pad(("PROM."), 7, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,575,$posY,str_pad(("VENTA"), 8, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,620,$posY,str_pad(("INV."), 7, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,660,$posY,str_pad(("SOBRE"), 6, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,695,$posY,str_pad(("SUGER."), 6, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,730,$posY,str_pad(("CLASIF"), 6, " ", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagefilledrectangle($img, 0, $posY, 759, $posY+4+9, $backgroundGris);
			imagestring($img,1,495,$posY,str_pad(("EXIST."), 7, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,535,$posY,str_pad(("DIA"), 7, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,575,$posY,str_pad(("MENSUAL"), 8, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,620,$posY,str_pad(("RECOM."), 7, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,660,$posY,str_pad(("STOCK"), 6, " ", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
		}
		
		$mesesExistencia = ($row['promedio_mensual'] > 0) ? $row['meses_existencia'] : 0;
		$sobreStock = ($row['cantidad_existencia'] > $row['inventario_recomendado']) ? $row['sobre_stock'] : 0;
		$sugerido = ($row['cantidad_existencia'] < $row['inventario_recomendado']) ? $row['sugerido'] : 0;
		
		$posY += 9;
		(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 759, $posY+9, $backgroundAzul);
		imagestring($img,1,0,$posY,elimCaracter($row['codigo_articulo'],";"),$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($row['descripcion'],0,24)),$textColor);
		imagestring($img,1,240,$posY,substr($row['codigo_articulo_prov'],0,16),$textColor);
		imagestring($img,1,325,$posY,str_pad(number_format($row['cantidad_existencia'], 2, ".", ","), 7, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,365,$posY,str_pad(number_format($row['costo'], 2, ".", ","), 12, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,430,$posY,str_pad(number_format($row['costo_total'], 2, ".", ","), 12, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,495,$posY,str_pad(number_format($mesesExistencia, 2, ".", ","), 7, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,535,$posY,str_pad(number_format($row['promedio_diario'], 2, ".", ","), 7, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,575,$posY,str_pad(number_format($row['promedio_mensual'], 2, ".", ","), 8, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,620,$posY,str_pad(number_format($row['inventario_recomendado'], 2, ".", ","), 7, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,660,$posY,str_pad(number_format($sobreStock, 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,695,$posY,str_pad(number_format($sugerido, 2, ".", ","), 6, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,730,$posY,str_pad($row['clasificacion'], 6, " ", STR_PAD_BOTH),$textColor);
			
		if (fmod($contFila, $maxRows) == 0 || $contFila == $totalRows) {
			$arrayImg[] = "tmp/"."analisis_inv".$pageNum.".png";
			$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
		}
	}
}


$img = @imagecreate(760, 520) or die("No se puede crear la imagen");
			
// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$backgroundGris = imagecolorallocate($img, 230, 230, 230);
$backgroundAzul = imagecolorallocate($img, 226, 239, 254);

$posY = 10;
imagestring($img,1,285,$posY,"ANÁLISIS DE INVENTARIO AL ".date(spanDateFormat),$textColor);
imagestring($img,1,600,$posY+10,"GENERADO EL: ".date(spanDateFormat." H:i:s", strtotime($rowAnalisisInv['fecha'])),$textColor);

$posY += 20;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
imagefilledrectangle($img, 0, $posY-4, 759, $posY+4+9, $backgroundGris);
imagestring($img,1,0,$posY,str_pad("CLASIFICACION", 37, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,190,$posY,str_pad("CANT. ARTICULOS", 22, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,305,$posY,str_pad("EXISTENCIA", 22, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,420,$posY,str_pad("COSTO INV.", 22, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,535,$posY,str_pad("PROM. VENTA 6M", 22, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,650,$posY,str_pad("MESES EXIST", 22, " ", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

// AGRUPA LAS CLASIFICACIONES PARA CALCULAR SUS TOTALES
$queryAnalisisInvDet = sprintf("SELECT analisis_inv_det.clasificacion
FROM iv_cierre_mensual cierre_mensual
	INNER JOIN iv_analisis_inventario analisis_inv ON (cierre_mensual.id_cierre_mensual = analisis_inv.id_cierre_mensual)
	INNER JOIN iv_analisis_inventario_detalle analisis_inv_det ON (analisis_inv.id_analisis_inventario = analisis_inv_det.id_analisis_inventario)
	INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)  %s
GROUP BY clasificacion", $sqlBusq);
$rsAnalisisInvDet = mysql_query($queryAnalisisInvDet);
if (!$rsAnalisisInvDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
while($rowAnalisisInvDet = mysql_fetch_array($rsAnalisisInvDet)){
	$contFila++;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 = $cond.sprintf("analisis_inv.id_analisis_inventario = %s
	AND ((analisis_inv_det.clasificacion = %s AND %s IS NOT NULL)
		OR analisis_inv_det.clasificacion IS NULL AND %s IS NULL)",
		valTpDato($idAnalisisInv,"int"),
		valTpDato($rowAnalisisInvDet['clasificacion'],"text"),
		valTpDato($rowAnalisisInvDet['clasificacion'],"text"),
		valTpDato($rowAnalisisInvDet['clasificacion'],"text"));
	
	$queryDetalle = sprintf("SELECT
		analisis_inv_det.id_analisis_inventario_detalle,
		analisis_inv_det.id_analisis_inventario,
		analisis_inv_det.id_articulo,
		analisis_inv_det.cantidad_existencia,
		analisis_inv_det.cantidad_disponible_logica,
		analisis_inv_det.cantidad_disponible_fisica,
		analisis_inv_det.costo,
		(analisis_inv_det.costo * analisis_inv_det.cantidad_existencia) AS costo_total,
		(analisis_inv_det.cantidad_existencia / analisis_inv_det.promedio_mensual) AS meses_existencia,
		analisis_inv_det.promedio_diario,
		analisis_inv_det.promedio_mensual,
		(analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario) AS inventario_recomendado,
		(analisis_inv_det.cantidad_existencia - (analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario)) AS sobre_stock,
		((analisis_inv_det.promedio_mensual * cierre_mensual.meses_inventario) - analisis_inv_det.cantidad_existencia) AS sugerido,
		analisis_inv_det.clasificacion,
		art.codigo_articulo,
		art.codigo_articulo_prov,
		art.descripcion
	FROM iv_cierre_mensual cierre_mensual
		INNER JOIN iv_analisis_inventario analisis_inv ON (cierre_mensual.id_cierre_mensual = analisis_inv.id_cierre_mensual)
		INNER JOIN iv_analisis_inventario_detalle analisis_inv_det ON (analisis_inv.id_analisis_inventario = analisis_inv_det.id_analisis_inventario)
		INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo) %s %s", $sqlBusq, $sqlBusq2);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$cantArt = 0;
	$exist = 0;
	$costoInv = 0;
	$promVenta = 0;
	$mesesExist = 0;
	while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
		$cantArt ++;
		$exist += $rowDetalle['cantidad_existencia'];
		$costoInv += $rowDetalle['costo_total'];
		$promVenta += $rowDetalle['promedio_mensual'] * $rowDetalle['costo'];
		$mesesExist += $rowDetalle['meses_existencia'];
	}
	
	$totalCantArt += $cantArt;
	$totalExistArt += $exist;
	$totalCostoInv += $costoInv;
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 759, $posY+9, $backgroundAzul);
	imagestring($img,1,0,$posY,$rowAnalisisInvDet['clasificacion'],$textColor);
	imagestring($img,1,190,$posY,str_pad(number_format($cantArt, 2, ".", ","), 22, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,305,$posY,str_pad(number_format($exist, 2, ".", ","), 22, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,420,$posY,str_pad(number_format($costoInv, 2, ".", ","), 22, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,535,$posY,str_pad(number_format(($promVenta / $cantArt), 2, ".", ","), 22, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,650,$posY,str_pad(number_format(($mesesExist / $cantArt), 2, ".", ","), 22, " ", STR_PAD_LEFT),$textColor);
}

$posY += 10;
imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);

$posY += 10;
imagestring($img,1,0,$posY,str_pad("TOTALES:", 37, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,190,$posY,str_pad(number_format($totalCantArt, 2, ".", ","), 22, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,305,$posY,str_pad(number_format($totalExistArt, 2, ".", ","), 22, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,420,$posY,str_pad(number_format($totalCostoInv, 2, ".", ","), 22, " ", STR_PAD_LEFT),$textColor);

$arrayImg[] = "tmp/"."analisis_inv".($pageNum+1).".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

//$pdf->nombreRegistrado = $row['nombre_empleado'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = 1;
if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, 60, 760, 520);
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