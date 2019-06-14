<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
require_once('../../clases/barcode128.inc.php');
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
/**************************** ARCHIVO PDF ****************************/
$maxRows = 30;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

// BUSCA LOS DATOS DEL DOCUMENTO
$query = sprintf("SELECT vw_iv_pres_vent.*,
	(SELECT email FROM pg_empleado WHERE id_empleado = vw_iv_pres_vent.id_empleado_preparador) AS email,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
	vw_iv_emp_suc.rif
FROM vw_iv_presupuestos_venta vw_iv_pres_vent
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_pres_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
WHERE vw_iv_pres_vent.id_presupuesto_venta = %s",
	valTpDato($idDocumento,"int"));
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$row = mysql_fetch_assoc($rs);

$idEmpresa = $row['id_empresa'];
$tipoPago = ($row['condicion_pago'] == 0) ? "CRÉDITO" : "CONTADO";

// VERIFICA VALORES DE CONFIGURACION (Mostrar Código Articulo en Presupuesto)
$queryConfig7 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 7 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig7 = mysql_query($queryConfig7, $conex);
if (!$rsConfig7) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowConfig7 = mysql_fetch_assoc($rsConfig7);

// VERIFICA VALORES DE CONFIGURACION (Pie Página de Factura de Repuesto)
$queryConfig4 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 4 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig4 = mysql_query($queryConfig4, $conex);
if (!$rsConfig4) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig4 = mysql_num_rows($rsConfig4);
$rowConfig4 = mysql_fetch_assoc($rsConfig4);

// BUSCA LOS DATOS DEL CLIENTE
$queryCliente = sprintf("SELECT cliente.*,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
	CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_contacto,
	cliente.reputacionCliente + 0 AS id_reputacion_cliente
FROM cj_cc_cliente cliente
	INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	LEFT JOIN cj_cc_credito cliente_cred ON (cliente_emp.id_cliente_empresa = cliente_cred.id_cliente_empresa)
WHERE cliente.id = %s
	AND cliente_emp.id_empresa = %s;",
	valTpDato($row['id_cliente'], "int"),
	valTpDato($idEmpresa, "int"));
$rsCliente = mysql_query($queryCliente);
if (!$rsCliente) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br><br>SQL: ".$queryCliente);
$totalRowsCliente = mysql_num_rows($rsCliente);
$rowCliente = mysql_fetch_assoc($rsCliente);

// DETALLES DE LOS REPUESTOS
$queryDetalle = sprintf("SELECT 
	pres_venta_det.*,
		
	(SELECT SUM(monto_gasto) AS total_gasto_art FROM iv_presupuesto_venta_detalle_gastos
	WHERE id_presupuesto_venta_detalle = pres_venta_det.id_presupuesto_venta_detalle) AS total_gasto_art,
	
	(SELECT SUM(iva.iva)
	FROM pg_iva iva
		INNER JOIN iv_articulos_impuesto art_impuesto ON (iva.idIva = art_impuesto.id_impuesto)
	WHERE art_impuesto.id_articulo = pres_venta_det.id_articulo
		AND iva.tipo IN (6,9,2)) AS porc_iva,
	
	vw_iv_art_datos_bas.codigo_articulo,
	vw_iv_art_datos_bas.descripcion
FROM iv_presupuesto_venta_detalle pres_venta_det
	INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_bas ON (pres_venta_det.id_articulo = vw_iv_art_datos_bas.id_articulo)
WHERE id_presupuesto_venta = %s
ORDER BY id_presupuesto_venta_detalle",
	valTpDato($idDocumento,"int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	
	$cantPedida = $rowDetalle['cantidad'];
	$cantEntregada = $rowDetalle['cantidad'] - $rowDetalle['pendiente'];
	$cantPendiente = $rowDetalle['pendiente'];
	$gastoUnitario = $rowDetalle['total_gasto_art'] / $rowDetalle['cantidad'];
	$precioUnitario = $rowDetalle['precio_unitario'] + $gastoUnitario;
	$porcIva = ($rowDetalle['porc_iva'] > 0) ? $rowDetalle['porc_iva']."%" : "-";
	$total = ($rowDetalle['cantidad'] * $rowDetalle['precio_unitario']) + $rowDetalle['total_gasto_art'];
	
	$arrayFila[] = array(
		'nro_fila' => $contFila,
		'codigo_articulo' => elimCaracter($rowDetalle['codigo_articulo'],";"),
		'descripcion_articulo' => $rowDetalle['descripcion'],
		'cant_pedida' => $cantPedida,
		'cant_entregada' => $cantEntregada,
		'precio_unitario' => $precioUnitario,
		'porc_impuesto' => $porcIva,
		'precio_total' => $total);
	
	if (!in_array($_SESSION['idMetodoCosto'], array(1,2)) && $rowDetalle['id_articulo_costo'] > 0 && in_array(idArrayPais,array(1))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$arrayFila[] = array(
			'nro_fila' => $contFila,
			'descripcion_lote' => "LOTE: ",
			'lote_articulo' => $rowDetalle['id_articulo_costo']);
	}
	
	if ($rowDetalle['precio_sugerido'] > 0 && in_array(idArrayPais,array(3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$arrayFila[] = array(
			'nro_fila' => $contFila,
			'descripcion_precio_lista' => "PRECIO LISTA: ",
			'precio_lista' => $row['abreviacion'].number_format($rowDetalle['precio_sugerido'], 2, ".", ","));
	}
	
	if ($rowDetalle['id_modo_compra'] == 2) { // 1 = Nacional, 2 = Importacion
		$queryArtPrecio = sprintf("SELECT
			precio.descripcion_precio,
			art_precio.precio,
			moneda.descripcion AS descripcion_moneda
		FROM iv_articulos_precios art_precio
			INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
			INNER JOIN pg_precios precio ON (art_precio.id_precio = precio.id_precio)
		WHERE art_precio.id_articulo_costo = %s
			AND art_precio.id_precio = 13;",
			valTpDato($rowDetalle['id_articulo_costo'],"int"));
		$rsArtPrecio = mysql_query($queryArtPrecio, $conex) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsArtPrecio = mysql_num_rows($rsArtPrecio);
		$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
		
		if ($totalRowsArtPrecio > 0) {
			$arrayFila[] = array(
				'nro_fila' => $contFila,
				'descripcion_precio_importacion' => $rowArtPrecio['descripcion_precio'],
				'precio_importacion' => $row['abreviacion'].number_format($rowArtPrecio['precio'], 2, ".", ","));
		}
	}
	
	$totalArticulos += $total;
}

$contFila = 0;
$contFilaY = 0;
if (isset($arrayFila)) {
	foreach ($arrayFila as $indiceFila => $valorFila) {
		$contFila = $valorFila['nro_fila'];
		$contFilaY++;
		
		if (fmod($contFilaY, $maxRows) == 1) {
			$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
			
			// ESTABLECIENDO LOS COLORES DE LA PALETA
			$backgroundColor = imagecolorallocate($img, 255, 255, 255);
			$textColor = imagecolorallocate($img, 0, 0, 0);
			$backgroundGris = imagecolorallocate($img, 230, 230, 230);
			$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
			
			$posY = 0;
			imagestring($img,1,300,$posY,str_pad("PRESUPUESTO DE VENTA", 34, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 9;
			imagestring($img,1,300,$posY,"NRO. PRESUPUESTO",$textColor);
			imagestring($img,1,375,$posY,": ".$row['numeracion_presupuesto'],$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"EMPRESA",$textColor);
			imagestring($img,1,40,$posY,": ".strtoupper($row['nombre_empresa']),$textColor);
			imagestring($img,1,300,$posY,"FECHA",$textColor);
			imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($row['fecha'])),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,$spanRIF.": ".strtoupper($row['rif']),$textColor);
			imagestring($img,1,300,$posY,"FECHA VENC.",$textColor);
			imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($row['fecha_vencimiento'])),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"VENDEDOR",$textColor);
			imagestring($img,1,40,$posY,": ".strtoupper($row['nombre_empleado']),$textColor);
			imagestring($img,1,300,$posY,"MONEDA",$textColor);
			imagestring($img,1,375,$posY,": ".strtoupper($row['descripcion']),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"CORREO",$textColor);
			imagestring($img,1,40,$posY,": ".strtoupper($row['email']),$textColor);
			imagestring($img,1,300,$posY,"NRO. SINIESTRO",$textColor);
			imagestring($img,1,375,$posY,": ".$row['numero_siniestro'],$textColor);
			
			$posY += 9;
			imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundGris);
			imagestring($img,1,0,$posY,str_pad("DATOS DEL CLIENTE", 94, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 9;
			imagestring($img,1,310,$posY,"CÓDIGO",$textColor);
			imagestring($img,1,375,$posY,": ".strtoupper($rowCliente['id']),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"CLIENTE",$textColor);
			imagestring($img,1,45,$posY,": ".strtoupper($rowCliente['nombre_cliente']),$textColor);
			imagestring($img,1,310,$posY,$spanClienteCxC,$textColor);
			imagestring($img,1,375,$posY,": ".$rowCliente['ci_cliente'],$textColor);
			
			$direccionCliente = strtoupper(elimCaracter($rowCliente['direccion_cliente'],";"));
			$posY += 9;
			imagestring($img,1,0,$posY,"DIRECCIÓN",$textColor);/////////////////////////////////////////
			imagestring($img,1,45,$posY,": ".trim(substr($direccionCliente,0,50)),$textColor);
			imagestring($img,1,310,$posY,"TELÉFONO",$textColor);///////////////////////////////////////////////////
			imagestring($img,1,375,$posY,": ".$rowCliente['telf'],$textColor);
			
			$posY += 9;
			imagestring($img,1,55,$posY,trim(substr($direccionCliente,50,50)),$textColor);
			if ($rowCliente['diascredito'] > 0) {
				imagestring($img,1,310,$posY,"DIAS CRÉDITO",$textColor);///////////////////////////////////////////////////
				imagestring($img,1,375,$posY,": ".number_format($rowCliente['diascredito'])." DÍAS",$textColor);
			}
			
			$posY += 9;
			imagestring($img,1,55,$posY,trim(substr($direccionCliente,100,50)),$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,"TIPO",$textColor);///////////////////////////////////////////////////
			imagestring($img,1,45,$posY,": "."VENTA",$textColor);
			imagestring($img,1,130,$posY,"CLAVE",$textColor);///////////////////////////////////////////////////
			imagestring($img,1,180,$posY,": ".strtoupper($row['descripcion_clave_movimiento']),$textColor);
			imagestring($img,1,345,$posY,"TIPO DE PAGO",$textColor);///////////////////////////////////////////////////
			imagestring($img,1,405,$posY,": ".$tipoPago,$textColor);
			
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagefilledrectangle($img, 0, $posY-4, 469, $posY+4+9, $backgroundGris);
			imagestring($img,1,0,$posY,str_pad("CODIGO", 22, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,115,$posY,str_pad("DESCRIPCIÓN", 21, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,225,$posY,str_pad("PED.", 6, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,260,$posY,str_pad("ENTREG", 6, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,295,$posY,strtoupper(str_pad($spanPrecioUnitario, 12, " ", STR_PAD_BOTH)),$textColor);
			imagestring($img,1,360,$posY,str_pad("%IMPTO", 6, " ", STR_PAD_BOTH),$textColor);
			imagestring($img,1,395,$posY,str_pad("TOTAL", 15, " ", STR_PAD_BOTH),$textColor);
			$posY += 9;
			imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
		}
		
		
		// DETALLE DEL PRESUPUESTO
		if (strlen($valorFila['codigo_articulo']) > 0) {
			$posY += 9;
			(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
				imagestring($img,1,0,$posY,
					strtoupper(str_pad(substr((($rowConfig7['valor'] == 1) ? $valorFila['codigo_articulo'] : ""),0,22), 22, " ", STR_PAD_RIGHT))." ".
					strtoupper(str_pad(substr(($valorFila['descripcion_articulo']),0,21), 21, " ", STR_PAD_RIGHT))." ".
					strtoupper(str_pad(substr(number_format($valorFila['cant_pedida'], 2, ".", ","),0,6), 6, " ", STR_PAD_LEFT))." ".
					strtoupper(str_pad(substr(number_format($valorFila['cant_entregada'], 2, ".", ","),0,6), 6, " ", STR_PAD_LEFT))." ".
					strtoupper(str_pad(substr(number_format($valorFila['precio_unitario'], 2, ".", ","),0,12), 12, " ", STR_PAD_LEFT))." ".
					strtoupper(str_pad(substr(($valorFila['porc_impuesto']),0,6), 6, " ", STR_PAD_LEFT))." ".
					strtoupper(str_pad(substr(number_format($valorFila['precio_total'], 2, ".", ","),0,15), 15, " ", STR_PAD_LEFT)),$textColor);
		} else if (strlen($valorFila['descripcion_lote']) > 0) {
			$posY += 9;
			(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
				imagestring($img,1,0,$posY,
					strtoupper(str_pad(substr(($valorFila['descripcion_lote']),0,22), 22, " ", STR_PAD_LEFT))." ".
					strtoupper(str_pad(substr(($valorFila['lote_articulo']),0,21), 21, " ", STR_PAD_RIGHT)),$textColor);
		} else if (strlen($valorFila['descripcion_precio_lista']) > 0) {
			$posY += 9;
			(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
				imagestring($img,1,0,$posY,
					strtoupper(str_pad(substr(($valorFila['descripcion_precio_lista']),0,22), 22, " ", STR_PAD_LEFT))." ".
					strtoupper(str_pad(substr(($valorFila['precio_lista']),0,21), 21, " ", STR_PAD_LEFT)),$textColor);
		} else if (strlen($valorFila['descripcion_precio_importacion']) > 0) {
			$posY += 9;
			(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 469, $posY+9, $backgroundAzul);
				imagestring($img,1,0,$posY,
					strtoupper(str_pad(substr(($valorFila['descripcion_precio_importacion']),0,22), 22, " ", STR_PAD_LEFT))." ".
					strtoupper(str_pad(substr(($valorFila['precio_importacion']),0,21), 21, " ", STR_PAD_LEFT)),$textColor);
		}
		
		
		if (fmod($contFilaY, $maxRows) == 0 || ($contFila == $totalRowsDetalle && $contFilaY == count($arrayFila))) {
			if ($contFila == $totalRowsDetalle) {
				$posY = 410;
				if ($totalRowsConfig4 > 0) {
					$arrayColetilla = wordwrap(str_replace("\n","<br>",$rowConfig4['valor']), 94, "<br>");
					$arrayValor = explode("<br>",$arrayColetilla);
					if (isset($arrayValor)) {
						foreach ($arrayValor as $indice => $valor) {
							$posY += 7;
							imagestring($img,1,0,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
						}
					}
				} else if ($maxRows > $contFilaY + 1 && !$verObservacion) {
					$verObservacion = true;
					
					$arrayObservacionDcto = wordwrap(str_replace("\n","<br>",str_replace(";", "", $row['observaciones'])), 94, "<br>");
					$arrayValor = explode("<br>",$arrayObservacionDcto);
					if (isset($arrayValor)) {
						foreach ($arrayValor as $indice => $valor) {
							$posY += 8;
							imagestring($img,1,0,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
						}
					}
				}
				
				$queryGasto = sprintf("SELECT
					pres_vent_gasto.id_presupuesto_venta_gasto,
					pres_vent_gasto.id_presupuesto_venta,
					pres_vent_gasto.tipo,
					pres_vent_gasto.porcentaje_monto,
					pres_vent_gasto.monto,
					pres_vent_gasto.estatus_iva,
					pres_vent_gasto.id_iva,
					pres_vent_gasto.iva,
					gasto.*
				FROM pg_gastos gasto
					INNER JOIN iv_presupuesto_venta_gasto pres_vent_gasto ON (gasto.id_gasto = pres_vent_gasto.id_gasto)
				WHERE id_presupuesto_venta = %s;",
					valTpDato($idDocumento, "text"));
				$rsGasto = mysql_query($queryGasto);
				if (!$rsGasto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
				$posY = 450;
				while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
					$posY += 9;
					imagestring($img,1,0,$posY,strtoupper($rowGasto['nombre']),$textColor);
					imagestring($img,1,90,$posY,":",$textColor);
					imagestring($img,1,100,$posY,str_pad(number_format($rowGasto['porcentaje_monto'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT),$textColor);
					imagestring($img,1,145,$posY,str_pad(number_format($rowGasto['monto'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					
					if ($rowGasto['estatus_iva'] == 0) {
						$totalGastosSinIva += $rowGasto['monto'];
					} else if ($rowGasto['estatus_iva'] == 1) {
						$totalGastosConIva += $rowGasto['monto'];
					}
					
					$totalGasto += $rowGasto['monto'];
				}
				
				$arrayObservacionDcto = wordwrap(str_replace("\n","<br>",str_replace(";", "", ((isset($verObservacion)) ? "" : $row['observaciones']))), 47, "<br>");
				$arrayValor = explode("<br>",$arrayObservacionDcto);
				if (isset($arrayValor)) {
					foreach ($arrayValor as $indice => $valor) {
						$posY += 8;
						imagestring($img,1,0,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
					}
				}
				
				$posY = 450;
				
				$posY += 9;
				imagestring($img,1,240,$posY,
					str_pad("SUBTOTAL", 16, " ", STR_PAD_RIGHT).":".
					str_pad("", 8, " ", STR_PAD_LEFT).
					str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
					str_pad(number_format($totalArticulos, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
				if ($row['subtotal_descuento'] > 0) {
					$posY += 9;
					imagestring($img,1,240,$posY,
						str_pad("DESCUENTO", 16, " ", STR_PAD_RIGHT).":".
						str_pad(number_format($row['porcentaje_descuento'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT).
						str_pad($rowIvaFact['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(number_format($row['subtotal_descuento'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				}
				
				if ($totalGastosConIva != 0) {
					$posY += 9;
					imagestring($img,1,240,$posY,
						str_pad("CARGOS C/IMPTO", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(number_format($totalGastosConIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				}
				
				$queryIvaFact = sprintf("SELECT *
				FROM iv_presupuesto_venta_iva pres_vent_iva
					INNER JOIN pg_iva iva ON (pres_vent_iva.id_iva = iva.idIva)
				WHERE id_presupuesto_venta = %s",
					valTpDato($idDocumento, "int"));
				$rsIvaFact = mysql_query($queryIvaFact);
				if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
				while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
					$posY += 9;
					imagestring($img,1,240,$posY,
						str_pad("BASE IMPONIBLE", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad($rowIvaFact['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(number_format($rowIvaFact['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					
					$posY += 9;
					imagestring($img,1,240,$posY,
						str_pad(substr(strtoupper(utf8_decode($rowIvaFact['observacion'])),0,16), 16, " ", STR_PAD_RIGHT).":".
						str_pad(number_format($rowIvaFact['iva'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT).
						str_pad($rowIvaFact['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(number_format($rowIvaFact['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
					
					$totalIva += $rowIvaFact['subtotal_iva'];
				}
				
				if ($totalGastosSinIva != 0) {
					$posY += 9;
					imagestring($img,1,240,$posY,
						str_pad("CARGOS S/IMPTO", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(number_format($totalGastosSinIva, 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				}
				
				$posY += 8;
				imagestring($img,1,240,$posY,str_pad("", 46, "-", STR_PAD_LEFT),$textColor);
				
				$posY += 8;
				$totalFactura = $totalArticulos - $row['subtotal_descuento'] + $totalIva + $totalGasto;
				imagestring($img,1,240,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
				imagestring($img,2,333,$posY,
					str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
					str_pad(number_format($totalFactura, 2, ".", ","), 17, " ", STR_PAD_LEFT),$textColor);
			}
			
			$pageNum++;
			$arrayImg[] = "tmp/"."presupuesto_venta".$pageNum.".png";
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

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$pdf->nombreRegistrado = $row['nombre_empleado'];
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
		
		$rutaCodigoBarra = "tmp/img_codigo".$idDocumento.".png";
		$aux = getBarcode($idDocumento, "tmp/img_codigo".$idDocumento, 2, 1, 25, "a", 0);
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 688);
		
		$pdf->Image($rutaCodigoBarra, 447, $rowConfig10['valor'] - 26, 80, '', '','');
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		if(file_exists($valor)) unlink($valor);
		if(file_exists($rutaCodigoBarra)) unlink($rutaCodigoBarra);
	}
}
?>