<?php
set_time_limit(0);
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('L','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idEmpresa = $valCadBusq[0];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);


$maxRows = 40;
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
if (!$rsPrecio) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRowsPrecio = mysql_num_rows($rsPrecio);

$maxRows = $maxRows / $totalRowsPrecio;

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

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("art.id_tipo_articulo = %s",
		valTpDato($valCadBusq[2], "int"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.clasificacion LIKE %s",
		valTpDato($valCadBusq[3], "text"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != ""
&& ($valCadBusq[5] == "-1" || $valCadBusq[5] == "")) {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_emp.cantidad_disponible_logica > 0");
}

if (($valCadBusq[4] == "-1" || $valCadBusq[4] == "")
&& $valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
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
	INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo) %s", $sqlBusq);

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
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);// echo $queryLimit."<br><br>";
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
			$img = @imagecreate(614, 403) or die("No se puede crear la imagen");
			
			// ESTABLECIENDO LOS COLORES DE LA PALETA
			$backgroundColor = imagecolorallocate($img, 255, 255, 255);
			$textColor = imagecolorallocate($img, 0, 0, 0);
			
			
			$posY = 0;
			imagestring($img,1,0,$posY,str_pad("LISTA DE PRECIOS AL ".date(spanDateFormat), 123, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 18;
			imagestring($img,1,0,$posY,str_pad("", 123, "-", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 22, "X", STR_PAD_BOTH),$textColor);
			imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 34, "X", STR_PAD_BOTH),$textColor);
			imagestring($img,1,290,$posY,str_pad(utf8_decode("LOTE"), 9, "X", STR_PAD_BOTH),$textColor);
			imagestring($img,1,340,$posY,str_pad(utf8_decode("UNID. DISP."), 12, "X", STR_PAD_BOTH),$textColor);
			
			$rsPrecio = mysql_query($queryPrecio);
			if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsPrecio = mysql_num_rows($rsPrecio);
			$arrayIdPrecio = NULL;
			$posY -= 9;
			while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
				$posY += 9;
				imagestring($img,1,405,$posY,strtoupper(str_pad(substr(utf8_decode($rowPrecio['descripcion_precio']),0,12), 12, "X", STR_PAD_BOTH)),$textColor);
				imagestring($img,1,470,$posY,str_pad(utf8_decode("IMPUESTO"), 12, "X", STR_PAD_BOTH),$textColor);
				imagestring($img,1,535,$posY,str_pad(utf8_decode("TOTAL"), 16, "X", STR_PAD_BOTH),$textColor);
				
				$arrayIdPrecio[] = array($rowPrecio['id_precio'], $rowPrecio['actualizar_con_costo']);
			}
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 123, "-", STR_PAD_BOTH),$textColor);
		}
		
		$posY += 9;
		imagestring($img,1,0,$posY,elimCaracter($row['codigo_articulo'],";"),$textColor);
		imagestring($img,1,115,$posY,strtoupper(substr($row['descripcion'],0,40)),$textColor);
		imagestring($img,1,320,$posY,str_pad(valTpDato(number_format($row['cantidad_disponible_fisica'], 2, ".", ","),"cero_por_vacio"), 10, " ", STR_PAD_LEFT),$textColor);
		if ($arrayIdPrecio) {
			$contFila2 = 0;
			$posY -= 9;
			foreach ($arrayIdPrecio as $indice => $valor) {
				$style = (fmod($contFila2, 2) == 0) ? "" : "font-weight:bold";
				$contFila2++;
				
				$queryArtPrecio = sprintf("SELECT
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
					AND art_precio.id_precio = %s;",
					valTpDato($row['id_articulo'], "int"),
					valTpDato($arrayIdPrecio[$indice][0], "int"));
				$rsArtPrecio = mysql_query($queryArtPrecio);
				if (!$rsArtPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
				
				$posY += 9;
				imagestring($img,1,405,$posY,str_pad($rowArtPrecio['abreviacion_moneda'].valTpDato(number_format($rowArtPrecio['precio_unitario'], 2, ".", ","),"cero_por_vacio"), 12, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,470,$posY,str_pad($rowArtPrecio['abreviacion_moneda'].valTpDato(number_format($rowArtPrecio['monto_impuesto'], 2, ".", ","),"cero_por_vacio"), 12, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,535,$posY,str_pad($rowArtPrecio['abreviacion_moneda'].valTpDato(number_format($rowArtPrecio['precio_unitario'] + $rowArtPrecio['monto_impuesto'], 2, ".", ","),"cero_por_vacio"), 16, " ", STR_PAD_LEFT),$textColor);
			}
		}
		
		if (fmod($contFila, $maxRows) == 0 || $contFila == $totalRows) {
			$arrayImg[] = "tmp/"."precio_lista".$pageNum.".png";
			$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
		}
	}
}


// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		// CABECERA DEL DOCUMENTO 
		if ($idEmpresa != "") {
			$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
			
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
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 758, 498);
		
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