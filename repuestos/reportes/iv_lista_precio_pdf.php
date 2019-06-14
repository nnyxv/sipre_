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

$maxRows = 38;
$campOrd = "art.codigo_articulo";
$tpOrd = "ASC";

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("precio.lista_precio = 1");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("emp_precio.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("precio.id_precio IN (%s)",
		valTpDato($valCadBusq[6], "campo"));
}

$queryPrecio = sprintf("SELECT DISTINCT precio.*
FROM pg_empresa_precios emp_precio
	INNER JOIN pg_precios precio ON (emp_precio.id_precio = precio.id_precio) %s
ORDER BY precio.id_precio ASC;", $sqlBusq);
$rsPrecio = mysql_query($queryPrecio);
if (!$rsPrecio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsPrecio = mysql_num_rows($rsPrecio);
while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
	$arrayTituloPrecio[] = strtoupper($rowPrecio['descripcion_precio']);
}

$maxRows = $maxRows;

$sqlBusq = "";
if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.posee_iva = %s",
		valTpDato($valCadBusq[1], "text"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.id_tipo_articulo IN (%s)",
		valTpDato($valCadBusq[3], "campo"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.clasificacion IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[4])."'", "defined", "'".str_replace(",","','",$valCadBusq[4])."'"));
}

if (in_array(1,explode(",",$valCadBusq[5]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica > 0");
}

if (in_array(2,explode(",",$valCadBusq[5]))) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica <= 0");
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
		valTpDato($valCadBusq[7], "text"));
}

if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
	OR art.descripcion LIKE %s
	OR art.codigo_articulo_prov LIKE %s
	OR (SELECT COUNT(art_costo.id_articulo_costo) FROM iv_articulos_costos art_costo
		WHERE art_costo.id_articulo = vw_iv_art_emp.id_articulo
			AND art_costo.id_empresa = vw_iv_art_emp.id_empresa
			AND art_costo.id_articulo_costo LIKE %s) > 0)",
		valTpDato($valCadBusq[8], "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"),
		valTpDato("%".$valCadBusq[8]."%", "text"));
}

$query = sprintf("SELECT
	vw_iv_art_emp.id_empresa,
	vw_iv_art_emp.id_articulo,
	vw_iv_art_emp.codigo_articulo,
	vw_iv_art_emp.descripcion,
	art.posee_iva,
	vw_iv_art_emp.cantidad_disponible_fisica,
	vw_iv_art_emp.cantidad_disponible_logica
FROM vw_iv_articulos_empresa vw_iv_art_emp
	INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo) %s
ORDER BY art.codigo_articulo ASC", $sqlBusq);
$rs = mysql_query($query, $conex) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	$contFilaY++;
	
	if (fmod($contFilaY, $maxRows) == 1) {
		$img = @imagecreate(614, 403) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		$backgroundGris = imagecolorallocate($img, 230, 230, 230);
		$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
		
		$posY = 0;
		imagestring($img,1,0,$posY,str_pad("LISTA DE ".implode(", ", $arrayTituloPrecio)." AL ".date(spanDateFormat), 123, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad(strtoupper("Nota: Precio Vigente hasta Agotar Lotes Existentes"), 123, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 123, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagefilledrectangle($img, 0, $posY-4, 619, $posY+4+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad(utf8_decode((in_array(1,explode(",",$valCadBusq[2]))) ? "CÓDIGO" : ""), 22, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 23, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,235,$posY,str_pad(utf8_decode((in_array(2,explode(",",$valCadBusq[2]))) ? "UNID. DISP" : ""), 12, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,300,$posY,str_pad(utf8_decode("LOTE"), 12, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagefilledrectangle($img, 0, $posY, 619, $posY+4+9, $backgroundGris);
		imagestring($img,1,300,$posY,str_pad(utf8_decode((in_array(2,explode(",",$valCadBusq[2]))) ? "UNID. DISP" : ""), 12, " ", STR_PAD_BOTH),$textColor);
		$posY -= 9;
		
		$rsPrecio = mysql_query($queryPrecio);
		if (!$rsPrecio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsPrecio = mysql_num_rows($rsPrecio);
		$arrayIdPrecio = NULL;
		$posY -= 9;
		while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
			$posY += 9;
			imagestring($img,1,365,$posY,strtoupper(str_pad(substr(utf8_decode($rowPrecio['descripcion_precio']),0,12), 16, " ", STR_PAD_BOTH)),$textColor);
			imagestring($img,1,450,$posY,str_pad(utf8_decode("IMPUESTO"), 16, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,535,$posY,str_pad(utf8_decode("TOTAL"), 16, " ", STR_PAD_BOTH),$textColor);
			
			$arrayIdPrecio[] = array($rowPrecio['id_precio'], $rowPrecio['actualizar_con_costo']);
		}
		$posY += ($totalRowsPrecio > 1) ? 9 : 18;
		imagestring($img,1,0,$posY,str_pad("", 123, "-", STR_PAD_BOTH),$textColor);
	}
	
	$idEmpresa = $row['id_empresa'];
	$idArticulo = $row['id_articulo'];
	
	$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
	WHERE vw_iv_art_almacen_costo.id_articulo = %s
		AND vw_iv_art_almacen_costo.id_empresa = %s
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
		AND vw_iv_art_almacen_costo.cantidad_disponible_logica > 0
	ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;", 
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArtCosto = mysql_query($queryArtCosto);
	if (!$rsArtCosto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
	
	$rowspan = ($totalRowsArtCosto > 0) ? "rowspan=\"".$totalRowsArtCosto."\"" : "";
	
	$imgAplicaIva = ($row['posee_iva'] == 1) ? "Si Aplica Impuesto" : "";
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 619, $posY+9, $backgroundAzul);
	imagestring($img,1,0,$posY,(in_array(1,explode(",",$valCadBusq[2]))) ? elimCaracter(($row['codigo_articulo']),";") : "",$textColor);
	imagestring($img,1,115,$posY,strtoupper(substr($row['descripcion'],0,27)),$textColor);
	
	imagestring($img,1,235,$posY,str_pad(valTpDato(((in_array(2,explode(",",$valCadBusq[2]))) ? number_format($row['cantidad_disponible_logica'], 2, ".", ",") : ""),"cero_por_vacio"), 12, " ", STR_PAD_LEFT),$textColor);
	$contFila2 = 0;
	$paginaFinal = "";
	while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
		$contFila2++;
		$contFilaY += ($contFila2 > 1) ? 1 : 0;
		
		if (fmod($contFilaY, $maxRows) == 1 && $paginaFinal != "") {
			$img = @imagecreate(614, 403) or die("No se puede crear la imagen");
			
			// ESTABLECIENDO LOS COLORES DE LA PALETA
			$backgroundColor = imagecolorallocate($img, 255, 255, 255);
			$textColor = imagecolorallocate($img, 0, 0, 0);
			$backgroundGris = imagecolorallocate($img, 230, 230, 230);
			$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
			
			$posY = 0;
			imagestring($img,1,0,$posY,str_pad("LISTA DE ".implode(", ", $arrayTituloPrecio)." AL ".date(spanDateFormat), 123, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 123, "-", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagefilledrectangle($img, 0, $posY-4, 619, $posY+4+9, $backgroundGris);
			imagestring($img,1,0,$posY,str_pad(utf8_decode((in_array(1,explode(",",$valCadBusq[2]))) ? "CÓDIGO" : ""), 22, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 23, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,235,$posY,str_pad(utf8_decode((in_array(2,explode(",",$valCadBusq[2]))) ? "UNID. DISP" : ""), 12, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,300,$posY,str_pad(utf8_decode("LOTE"), 12, " ", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagefilledrectangle($img, 0, $posY, 619, $posY+4+9, $backgroundGris);
			imagestring($img,1,300,$posY,str_pad(utf8_decode((in_array(2,explode(",",$valCadBusq[2]))) ? "UNID. DISP" : ""), 12, " ", STR_PAD_BOTH),$textColor);
			$posY -= 9;
			
			$rsPrecio = mysql_query($queryPrecio);
			if (!$rsPrecio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsPrecio = mysql_num_rows($rsPrecio);
			$arrayIdPrecio = NULL;
			$posY -= 9;
			while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
				$posY += 9;
				imagestring($img,1,365,$posY,strtoupper(str_pad(substr(utf8_decode($rowPrecio['descripcion_precio']),0,12), 16, " ", STR_PAD_BOTH)),$textColor);
				imagestring($img,1,450,$posY,str_pad(utf8_decode("IMPUESTO"), 16, " ", STR_PAD_BOTH),$textColor);
				imagestring($img,1,535,$posY,str_pad(utf8_decode("TOTAL"), 16, " ", STR_PAD_BOTH),$textColor);
				
				$arrayIdPrecio[] = array($rowPrecio['id_precio'], $rowPrecio['actualizar_con_costo']);
			}
			$posY += ($totalRowsPrecio > 1) ? 9 : 18;
			imagestring($img,1,0,$posY,str_pad("", 123, "-", STR_PAD_BOTH),$textColor);
		}
		
		($contFila2 > 1) ? (fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 619, $posY+9, $backgroundAzul) : "";
		imagestring($img,1,0,$posY,(in_array(1,explode(",",$valCadBusq[2]))) ? elimCaracter(($row['codigo_articulo']),";") : "",$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($row['descripcion'],0,27)),$textColor);
		imagestring($img,1,300,$posY,str_pad($rowArtCosto['id_articulo_costo'], 12, " ", STR_PAD_LEFT),$textColor);
		$posY -= 9;
		
		if ($arrayIdPrecio) {
			$ultColum = "F";
			
			$contFila3 = 0;
			$paginaFinal = "";
			foreach ($arrayIdPrecio as $indice => $valor) {
				$style = (fmod($contFila2, 2) == 0) ? "" : "font-weight:bold";
				$contFila3++;
				$contFilaY += ($contFila3 > 1) ? 1 : 0;
				
				if (fmod($contFilaY, $maxRows) == 1 && $paginaFinal != "") {
					$img = @imagecreate(614, 403) or die("No se puede crear la imagen");
					
					// ESTABLECIENDO LOS COLORES DE LA PALETA
					$backgroundColor = imagecolorallocate($img, 255, 255, 255);
					$textColor = imagecolorallocate($img, 0, 0, 0);
					$backgroundGris = imagecolorallocate($img, 230, 230, 230);
					$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
					
					$posY = 0;
					imagestring($img,1,0,$posY,str_pad("LISTA DE ".implode(", ", $arrayTituloPrecio)." AL ".date(spanDateFormat), 123, " ", STR_PAD_BOTH),$textColor);
					
					$posY += 9;
					imagestring($img,1,0,$posY,str_pad("", 123, "-", STR_PAD_BOTH),$textColor);
					$posY += 9;
					imagefilledrectangle($img, 0, $posY-4, 619, $posY+4+9, $backgroundGris);
					imagestring($img,1,0,$posY,str_pad(utf8_decode((in_array(1,explode(",",$valCadBusq[2]))) ? "CÓDIGO" : ""), 22, " ", STR_PAD_BOTH),$textColor);
					imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 23, " ", STR_PAD_BOTH),$textColor);
					imagestring($img,1,235,$posY,str_pad(utf8_decode((in_array(2,explode(",",$valCadBusq[2]))) ? "UNID. DISP" : ""), 12, " ", STR_PAD_BOTH),$textColor);
					imagestring($img,1,300,$posY,str_pad(utf8_decode("LOTE"), 12, " ", STR_PAD_BOTH),$textColor);
					$posY += 9;
					imagefilledrectangle($img, 0, $posY, 619, $posY+4+9, $backgroundGris);
					imagestring($img,1,300,$posY,str_pad(utf8_decode((in_array(2,explode(",",$valCadBusq[2]))) ? "UNID. DISP" : ""), 12, " ", STR_PAD_BOTH),$textColor);
					$posY -= 9;
					
					$rsPrecio = mysql_query($queryPrecio);
					if (!$rsPrecio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
					$totalRowsPrecio = mysql_num_rows($rsPrecio);
					$arrayIdPrecio = NULL;
					$posY -= 9;
					while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
						$posY += 9;
						imagestring($img,1,365,$posY,strtoupper(str_pad(substr(utf8_decode($rowPrecio['descripcion_precio']),0,12), 16, " ", STR_PAD_BOTH)),$textColor);
						imagestring($img,1,450,$posY,str_pad(utf8_decode("IMPUESTO"), 16, " ", STR_PAD_BOTH),$textColor);
						imagestring($img,1,535,$posY,str_pad(utf8_decode("TOTAL"), 16, " ", STR_PAD_BOTH),$textColor);
						
						$arrayIdPrecio[] = array($rowPrecio['id_precio'], $rowPrecio['actualizar_con_costo']);
					}
					$posY += ($totalRowsPrecio > 1) ? 9 : 18;
					imagestring($img,1,0,$posY,str_pad("", 123, "-", STR_PAD_BOTH),$textColor);
				}
				
				$queryArtPrecio = sprintf("SELECT
					art_precio.id_articulo_precio,
					art_precio.id_precio,
					art_precio.precio AS precio_unitario,
					
					(SELECT iva.observacion
					FROM iv_articulos_impuesto art_impsto
						INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
					WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1
						AND art_impsto.id_articulo = art_precio.id_articulo
					LIMIT 1) AS descripcion_impuesto,
					
					(SELECT SUM(iva.iva)
					FROM iv_articulos_impuesto art_impsto
						INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
					WHERE iva.tipo IN (6,9,2)
						AND art_impsto.id_articulo = art_precio.id_articulo) AS porcentaje_impuesto,
					
					(art_precio.precio * (SELECT SUM(iva.iva)
										FROM iv_articulos_impuesto art_impsto
											INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
										WHERE iva.tipo IN (6,9,2)
											AND art_impsto.id_articulo = art_precio.id_articulo) / 100) AS monto_impuesto,
					
					moneda.abreviacion AS abreviacion_moneda
				FROM iv_articulos_precios art_precio
					INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
				WHERE art_precio.id_articulo = %s
					AND art_precio.id_articulo_costo = %s
					AND art_precio.id_precio = %s;",
					valTpDato($row['id_articulo'], "int"),
					valTpDato($rowArtCosto['id_articulo_costo'], "int"),
					valTpDato($arrayIdPrecio[$indice][0], "int"));
				$rsArtPrecio = mysql_query($queryArtPrecio);
				if (!$rsArtPrecio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
				$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
				
				$posY += 9;
				if (($totalRowsPrecio > 1 && $contFila3 == 2) || !($totalRowsPrecio > 1)) {
					$contFilaY += ($totalRowsPrecio > 1) ? 0 : 1;
					$posY += ($totalRowsPrecio > 1) ? 0 : 9;
					
					(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 619, $posY+9, $backgroundAzul);
					imagestring($img,1,300,$posY,str_pad(valTpDato(((in_array(2,explode(",",$valCadBusq[2]))) ? number_format($rowArtCosto['cantidad_disponible_logica'], 2, ".", ",") : ""),"cero_por_vacio"), 12, " ", STR_PAD_LEFT),$textColor);
				}
				imagestring($img,1,365,$posY,str_pad($rowArtPrecio['abreviacion_moneda'].valTpDato(number_format($rowArtPrecio['precio_unitario'], 2, ".", ","),"cero_por_vacio"), 16, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,450,$posY,str_pad($rowArtPrecio['abreviacion_moneda'].valTpDato(number_format($rowArtPrecio['monto_impuesto'], 2, ".", ","),"cero_por_vacio"), 16, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,535,$posY,str_pad($rowArtPrecio['abreviacion_moneda'].valTpDato(number_format($rowArtPrecio['precio_unitario'] + $rowArtPrecio['monto_impuesto'], 2, ".", ","),"cero_por_vacio"), 16, " ", STR_PAD_LEFT),$textColor);
				
				if ((fmod($contFilaY, $maxRows) == 0 || ($contFila == $totalRows && $contFila3 == count($arrayIdPrecio))) && !in_array($paginaFinal, array(1))) {
					$pageNum++;
					$arrayImg[] = "tmp/"."precio_lista".$pageNum.".png";
					$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
					$paginaFinal = 1;
				}
			}
		}
		
		$posY += ($totalRowsArtCosto > 1) ? 9 : 0;
	
		if ((fmod($contFilaY, $maxRows) == 0 || ($contFila == $totalRows && $contFila3 == count($arrayIdPrecio))) && !in_array($paginaFinal, array(1,2))) {
			$pageNum++;
			$arrayImg[] = "tmp/"."precio_lista".$pageNum.".png";
			$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
			$paginaFinal = 2;
		}
	}
	
	$posY -= ($totalRowsArtCosto > 1) ? 9 : 0;
	
	if ((fmod($contFilaY, $maxRows) == 0 || ($contFila == $totalRows && $contFila3 == count($arrayIdPrecio))) && !in_array($paginaFinal, array(1,2,3))) {
		$pageNum++;
		$arrayImg[] = "tmp/"."precio_lista".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
		$paginaFinal = 3;
	}
}

/*echo "<pre>".print_r($arrayImg)."</pre>";
exit;*/

// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

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
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 758, 498);
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