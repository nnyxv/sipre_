<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
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

$maxRows = 50;
$campOrd = "CONCAT(descripcion_almacen, ubicacion)";
$tpOrd = "ASC";

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("art_alm.estatus IS NULL");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("alm.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != ""
&& ($valCadBusq[2] == "-1" || $valCadBusq[2] == "")) {
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqEstatus .= $cond.sprintf("casilla2.estatus = %s",
			valTpDato($valCadBusq[7], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT
		COUNT(art_alm2.id_articulo)
	FROM iv_articulos_almacen art_alm2
		INNER JOIN iv_casillas casilla2 ON (art_alm2.id_casilla = casilla2.id_casilla)
	WHERE art_alm2.id_articulo = art.id_articulo 
		AND art_alm2.estatus IS NULL %s) = 1", $sqlBusqEstatus);
}

if (($valCadBusq[1] == "-1" || $valCadBusq[1] == "")
&& $valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusqEstatus .= $cond.sprintf("casilla2.estatus = %s",
			valTpDato($valCadBusq[7], "int"));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT
		COUNT(art_alm2.id_articulo)
	FROM iv_articulos_almacen art_alm2
		INNER JOIN iv_casillas casilla2 ON (art_alm2.id_casilla = casilla2.id_casilla)
	WHERE art_alm2.id_articulo = art.id_articulo
		AND art_alm2.estatus IS NULL %s) > 1", $sqlBusqEstatus);
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != ""
&& ($valCadBusq[4] == "-1" || $valCadBusq[4] == "")) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(art_alm2.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm2
	WHERE art_alm2.id_casilla = casilla.id_casilla
		AND art_alm2.estatus = 1) = 0",
		valTpDato($valCadBusq[4], "text"));
}

if (($valCadBusq[3] == "-1" || $valCadBusq[3] == "")
&& $valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(SELECT COUNT(art_alm2.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm2
	WHERE art_alm2.id_casilla = casilla.id_casilla
		AND art_alm2.estatus = 1) > 0",
		valTpDato($valCadBusq[4], "text"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != ""
&& ($valCadBusq[6] == "-1" || $valCadBusq[6] == "")) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera) > 0");
}

if (($valCadBusq[5] == "-1" || $valCadBusq[5] == "")
&& $valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera) <= 0");
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("casilla.estatus = %s",
		valTpDato($valCadBusq[7], "int"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("alm.id_almacen = %s",
		valTpDato($valCadBusq[8], "int"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("calle.id_calle = %s",
		valTpDato($valCadBusq[9], "int"));
}

if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estante.id_estante = %s",
		valTpDato($valCadBusq[10], "int"));
}

if ($valCadBusq[11] != "-1" && $valCadBusq[11] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tramo.id_tramo = %s",
		valTpDato($valCadBusq[11], "int"));
}

if ($valCadBusq[12] != "-1" && $valCadBusq[12] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("casilla.id_casilla = %s",
		valTpDato($valCadBusq[12], "int"));
}

if ($valCadBusq[13] != "-1" && $valCadBusq[13] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[13], "text"));
}

if ($valCadBusq[14] != "-1" && $valCadBusq[14] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
	OR art.descripcion LIKE %s)",
		valTpDato($valCadBusq[14], "int"),
		valTpDato("%".$valCadBusq[14]."%", "text"));
}

$query = sprintf("SELECT
	alm.id_empresa,
	alm.id_almacen,
	alm.descripcion AS descripcion_almacen,
	alm.estatus,
	calle.id_calle,
	estante.id_estante ,
	tramo.id_tramo,
	casilla.id_casilla,
	CONCAT_WS('-', calle.descripcion_calle, estante.descripcion_estante, tramo.descripcion_tramo, casilla.descripcion_casilla) AS ubicacion,
	casilla.estatus AS estatus_casilla,
	
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	
	(SELECT art_emp.clasificacion
	FROM iv_articulos_empresa art_emp
	WHERE art_emp.id_articulo = art.id_articulo
		AND art_emp.id_empresa = alm.id_empresa) AS clasificacion,
	
	(art_alm.cantidad_entrada - art_alm.cantidad_salida - art_alm.cantidad_reservada - art_alm.cantidad_espera) AS cantidad_disponible_logica
FROM iv_estantes estante
	INNER JOIN iv_calles calle ON (estante.id_calle = calle.id_calle)
	INNER JOIN iv_almacenes alm ON (calle.id_almacen = alm.id_almacen)
	INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
	INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
	INNER JOIN iv_articulos_almacen art_alm ON (casilla.id_casilla = art_alm.id_casilla)
	INNER JOIN iv_articulos art ON (art_alm.id_articulo = art.id_articulo) %s", $sqlBusq);

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
for ($pageNumSql = 0; $pageNumSql <= $totalPages; $pageNumSql++) {
	$startRow = $pageNumSql * $maxRows;
	
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
		$contFilaY++;
		
		if (fmod($contFilaY, $maxRows) == 1) {
			$img = @imagecreate(580, 650) or die("No se puede crear la imagen");
			
			// ESTABLECIENDO LOS COLORES DE LA PALETA
			$backgroundColor = imagecolorallocate($img, 255, 255, 255);
			$textColor = imagecolorallocate($img, 0, 0, 0);
			$backgroundGris = imagecolorallocate($img, 230, 230, 230);
			$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
			
			$posY = 0;
			imagestring($img,1,0,$posY,str_pad(utf8_decode("HISTÓRICO DE RELACIÓN ARTÍCTULO CON UBICACIÓN"), 116, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 20;
			imagestring($img,1,0,$posY,str_pad("", 116, "-", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagefilledrectangle($img, 0, $posY-4, 619, $posY+4+9, $backgroundGris);
			imagestring($img,1,0,$posY,str_pad(utf8_decode("ESTATUS"), 8, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,45,$posY,str_pad(utf8_decode("ALMACÉN"), 22, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,160,$posY,str_pad(utf8_decode("UBICACION"), 14, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,235,$posY,str_pad(utf8_decode(""), 8, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,280,$posY,str_pad(utf8_decode("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,395,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 22, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,510,$posY,str_pad(utf8_decode("CLAS."), 5, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,540,$posY,str_pad(utf8_decode("DISP."), 8, " ", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 116, "-", STR_PAD_BOTH),$textColor);
		}
	
		switch ($row['estatus_casilla']) {
			case 0 : $imgEstatus = "INACTIVO"; break;
			case 1 : $imgEstatus = "ACTIVO"; break;
			default : $imgEstatus = "";
		}
		
		$queryCasilla = sprintf("SELECT
			casilla.*,
			
			(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
			WHERE art_alm.id_casilla = casilla.id_casilla
				AND art_alm.estatus = 1) AS cantidad_ocupada
		FROM iv_casillas casilla
		WHERE casilla.id_casilla = %s;",
			valTpDato($row['id_casilla'], "int"));
		$rsCasilla = mysql_query($queryCasilla);
		if (!$rsCasilla) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsCasilla = mysql_num_rows($rsCasilla);
		$rowCasilla = mysql_fetch_assoc($rsCasilla);
		
		//$imgEstatusArticuloAlmacen = ($row['estatus_articulo_almacen'] == 1) ? "" : "<span class=\"textoRojoNegrita_10px\">".utf8_encode("Relacion Inactiva")."</span>";
		
		$classUbic = "";
		$classUbic = ($rowCasilla['cantidad_ocupada'] > 0) ? "Ocupada" : "Disponible";
		$classUbic = ($rowCasilla['estatus'] == 0) ? "Inactiva" : $classUbic;
		$ocupada = ($rowCasilla['cantidad_ocupada'] > 0) ? "*" : "";
		
		$posY += 9;
		(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 619, $posY+9, $backgroundAzul);
		imagestring($img,1,0,$posY,$imgEstatus,$textColor);
		imagestring($img,1,45,$posY,$row['descripcion_almacen'],$textColor);
		imagestring($img,1,160,$posY,str_pad(str_replace("-[]", "", $row['ubicacion'].$ocupada), 14, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,235,$posY,strtoupper(substr($classUbic,0,8)),$textColor);
		imagestring($img,1,280,$posY,elimCaracter($row['codigo_articulo'],";"),$textColor); // <-
		imagestring($img,1,395,$posY,strtoupper(substr($row['descripcion'],0,22)),$textColor);
		imagestring($img,1,510,$posY,str_pad($row['clasificacion'], 5, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,540,$posY,str_pad(valTpDato(number_format($row['cantidad_disponible_logica'], 2, ".", ","),"cero_por_vacio"), 8, " ", STR_PAD_LEFT),$textColor);
		
		if (fmod($contFilaY, $maxRows) == 0 || $contFila == $totalRows) {
			$pageNum++;
			$arrayImg[] = "tmp/"."ubicacion".$pageNum.".png";
			$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
		}
	}
}

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
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
		
		$pdf->Image($valor, 15, 90, 580, 650);
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