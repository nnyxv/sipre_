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
$pdf->SetAutoPageBreak(1,"0");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
/**************************** ARCHIVO PDF ****************************/
$maxRows = 36;
$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = ($valCadBusq[0] > 0) ? $valCadBusq[0] : 100;

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = alm.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca IN (%s)",
		valTpDato($valCadBusq[1], "campo"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo IN (%s)",
		valTpDato($valCadBusq[2], "campo"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version IN (%s)",
		valTpDato($valCadBusq[3], "campo"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_ano IN (%s)",
		valTpDato($valCadBusq[4], "campo"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_compra IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[5])."'", "defined", "'".str_replace(",","','",$valCadBusq[5])."'"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN (%s)",
		valTpDato("'".str_replace(",","','",$valCadBusq[6])."'", "defined", "'".str_replace(",","','",$valCadBusq[6])."'"));
}

if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
		valTpDato($valCadBusq[7], "campo"));
}
	
if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("alm.id_almacen IN (%s)",
		valTpDato($valCadBusq[8], "campo"));
}

if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
	OR vw_iv_modelo.nom_uni_bas LIKE %s
	OR vw_iv_modelo.nom_modelo LIKE %s
	OR vw_iv_modelo.nom_version LIKE %s
	OR uni_fis.serial_carroceria LIKE %s
	OR uni_fis.serial_motor LIKE %s
	OR uni_fis.serial_chasis LIKE %s
	OR uni_fis.placa LIKE %s
	OR cxp_fact.numero_factura_proveedor LIKE %s)",
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"),
		valTpDato("%".$valCadBusq[9]."%", "text"));
}

$query = sprintf("SELECT DISTINCT
	vw_iv_modelo.id_uni_bas,
	vw_iv_modelo.nom_uni_bas,
	CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
	(CASE vw_iv_modelo.catalogo
		WHEN 0 THEN ''
		WHEN 1 THEN 'En Catálogo'
	END) AS mostrar_catalogo
FROM an_unidad_fisica uni_fis
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
	INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
	INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
	INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
	LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
	LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
ORDER BY CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version) ASC", $sqlBusq);
$rs = mysql_query($query, $conex) or die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rs);
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
		valTpDato($row['id_uni_bas'], "int"));
	
	$queryDetalle = sprintf("SELECT
		uni_fis.id_unidad_fisica,
		uni_fis.id_activo_fijo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.titulo_vehiculo,
		uni_fis.placa,
		uni_fis.tipo_placa,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		uni_fis.kilometraje,
		color_ext.nom_color AS color_externo1,
		color_int.nom_color AS color_interno1,
		(CASE
			WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
				IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
			WHEN (an_ve.fecha IS NOT NULL) THEN
				an_ve.fecha
		END) AS fecha_origen,
		IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
				WHEN (an_ve.fecha IS NOT NULL) THEN
					TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
			END),
		0) AS dias_inventario,
		uni_fis.estado_compra,
		uni_fis.estado_venta,
		asig.idAsignacion,
		alm.nom_almacen,
		cxp_fact.id_factura,
		cxp_fact.numero_factura_proveedor,
		cxp_fact.id_modulo AS id_modulo_cxp,
		uni_fis.costo_compra,
		uni_fis.precio_compra,
		uni_fis.costo_agregado,
		uni_fis.costo_depreciado,
		uni_fis.costo_trade_in,
		
		(SELECT COUNT(uni_fis_agregado.id_unidad_fisica) FROM an_unidad_fisica_agregado uni_fis_agregado
		WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica) AS cant_agregado,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_unidad_fisica uni_fis
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
			AND an_ve.fecha IS NOT NULL
			AND an_ve.tipo_vale_entrada = 1
			AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $sqlBusq2);
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	$arrayTotal = NULL;
	$arrayFila = NULL;
	$arrayFila[] = array(
		'vehiculo' => $row['vehiculo']);
	$arrayFila[] = array(
		'linea_vacia' => " ");
	while($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		if (strlen($rowDetalle['id_unidad_fisica']) > 0) {
			$arrayFila[] = array(
				'id_unidad_fisica' => $rowDetalle['id_unidad_fisica'],
				'serial_carroceria' => $rowDetalle['serial_carroceria'],
				'color_externo1' => $rowDetalle['color_externo1'],
				'fecha_origen' => $rowDetalle['fecha_origen'],
				'estado_venta' => $rowDetalle['estado_venta'],
				'idAsignacion' => $rowDetalle['idAsignacion'],
				'nombre_empresa' => $rowDetalle['nombre_empresa'],
				'numero_factura_proveedor' => $rowDetalle['numero_factura_proveedor']);
		}
		
		if (strlen($rowDetalle['serial_motor']) > 0) {
			$arrayFila[] = array(
				'serial_motor' => $rowDetalle['serial_motor'],
				'condicion_unidad' => $rowDetalle['condicion_unidad'],
				'dias_inventario' => $rowDetalle['dias_inventario'],
				'nom_almacen' => $rowDetalle['nom_almacen'],
				'precio_compra' => ($rowDetalle['precio_compra'] + $rowDetalle['costo_agregado'] - $rowDetalle['costo_depreciado'] - $rowDetalle['costo_trade_in']));
		}
		
		if (strlen($rowDetalle['placa']) > 0) {
			$arrayFila[] = array(
				'placa' => $rowDetalle['placa']);
		}
			
		$arrayTotal['precio_compra'] += ($rowDetalle['precio_compra'] + $rowDetalle['costo_agregado'] - $rowDetalle['costo_depreciado'] - $rowDetalle['costo_trade_in']);
	}
	$arrayFila[] = array(
		'linea' => "-");
	$arrayFila[] = array(
		'cant_items' => $totalRowsDetalle,
		'subtotal_costo' => $arrayTotal['precio_compra']);
	$arrayFila[] = array(
		'doble_linea' => "=");
	
	$arrayTotalFinal['cant_unidades'] += $totalRowsDetalle;
	$arrayTotalFinal['precio_compra'] += $arrayTotal['precio_compra'];
	
	if ($contFila == $totalRows) {
		$arrayFila[] = array(
			'total_cant_unidades' => $arrayTotalFinal['cant_unidades'],
			'total_precio_compra' => $arrayTotalFinal['precio_compra']);
	}
	
	$contFila2 = 0;
	if (isset($arrayFila)) {
		foreach ($arrayFila as $indice => $valor) {
			$contFilaY++;
			
			if (fmod($contFilaY, $maxRows) == 1) {
				$img = @imagecreate(570, 390) or die("No se puede crear la imagen");
				
				// ESTABLECIENDO LOS COLORES DE LA PALETA
				$backgroundColor = imagecolorallocate($img, 255, 255, 255);
				$textColor = imagecolorallocate($img, 0, 0, 0);
				$textColorBlanco = imagecolorallocate($img, 255, 255, 255);
				$backgroundGris = imagecolorallocate($img, 204, 204, 204);
				$backgroundGrisClaro = imagecolorallocate($img, 240, 240, 240);
				$backgroundAmarillo = imagecolorallocate($img, 255, 255, 204);
				$backgroundVerde = imagecolorallocate($img, 230, 255, 230);
				$backgroundAzul = imagecolorallocate($img, 221, 238, 255);
				$backgroundNaranja = imagecolorallocate($img, 255, 238, 213);
				$backgroundNegro = imagecolorallocate($img, 0, 0, 0);
				
				$posY = 0;
				imagestring($img,1,0,$posY,str_pad(utf8_decode("UNIDADES FÍSICAS"), 114, " ", STR_PAD_BOTH),$textColor);
				
				$posY += 9;
				imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
				$posY += 9;
				imagefilledrectangle($img, 0, $posY-4, 569, $posY+4+27, $backgroundGris);
					imagestring($img,1,0,$posY,
						strtoupper(str_pad(substr((""),0,6), 6, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("NRO."),0,8), 8, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode($spanSerialCarroceria),0,20), 20, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("COLOR"),0,14), 14, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("FECHA ING."),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("EST. VENT."),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("ASIG."),0,8), 8, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("EMPRESA"),0,16), 16, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("FACT COMP"),0,14), 14, " ", STR_PAD_BOTH)),$textColor);
					$posY += 9;
					imagestring($img,1,0,$posY,
						strtoupper(str_pad(substr((""),0,6), 6, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("UND FÍS"),0,8), 8, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode($spanSerialMotor),0,20), 20, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("CONDICIÓN"),0,14), 14, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("DIAS"),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,8), 8, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("ALMACÉN"),0,16), 16, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode("COSTO"),0,14), 14, " ", STR_PAD_BOTH)),$textColor);
					$posY += 9;
					imagestring($img,1,0,$posY,
						strtoupper(str_pad(substr((""),0,6), 6, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,8), 8, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(utf8_decode($spanPlaca),0,20), 20, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,14), 14, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,8), 8, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,16), 16, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,14), 14, " ", STR_PAD_BOTH)),$textColor);
				$posY += 9;
				imagestring($img,1,0,$posY,str_pad("", 152, "-", STR_PAD_BOTH),$textColor);
			}
			
			if (strlen($arrayFila[$indice]['vehiculo']) > 0) {
				$posY += 9;
				imagefilledrectangle($img, 0, $posY-4, 569, $posY+4+9, $backgroundGrisClaro);
					imagestring($img,1,0,$posY,utf8_encode($row['vehiculo']),$textColor);
				
			} else if (strlen($arrayFila[$indice]['id_unidad_fisica']) > 0) {
				$contFila2++;
				
				$posY += 9;
				imagefilledrectangle($img, 0, $posY, 569, $posY+9, ((fmod($contFila2, 2) == 0) ? $backgroundAzul : ""));
					imagestring($img,1,0,$posY,
						strtoupper(str_pad(substr(($contFila2.")"),0,6), 6, " ", STR_PAD_LEFT))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['id_unidad_fisica']),0,8), 8, " ", STR_PAD_LEFT))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['serial_carroceria']),0,20), 20, " ", STR_PAD_RIGHT))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['color_externo1']),0,14), 14, " ", STR_PAD_RIGHT))." ".
						strtoupper(str_pad(substr((date(spanDateFormat,strtotime($arrayFila[$indice]['fecha_origen']))),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['estado_venta']),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['idAsignacion']),0,8), 8, " ", STR_PAD_LEFT))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['nombre_empresa']),0,16), 16, " ", STR_PAD_RIGHT))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['numero_factura_proveedor']),0,14), 14, " ", STR_PAD_LEFT)),$textColor);
				
			} else if (strlen($arrayFila[$indice]['serial_motor']) > 0) {
				$posY += 9;
				imagefilledrectangle($img, 0, $posY, 569, $posY+9, ((fmod($contFila2, 2) == 0) ? $backgroundAzul : ""));
					imagestring($img,1,0,$posY,
						strtoupper(str_pad(substr((""),0,6), 6, " ", STR_PAD_LEFT))." ".
						strtoupper(str_pad(substr((""),0,8), 8, " ", STR_PAD_LEFT))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['serial_motor']),0,20), 20, " ", STR_PAD_RIGHT))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['condicion_unidad']),0,14), 14, " ", STR_PAD_RIGHT))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['dias_inventario']),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,8), 8, " ", STR_PAD_LEFT))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['nom_almacen']),0,16), 16, " ", STR_PAD_RIGHT))." ".
						strtoupper(str_pad(substr((number_format($arrayFila[$indice]['precio_compra'], 2, ".", ",")),0,14), 14, " ", STR_PAD_LEFT)),$textColor);
				
			} else if (strlen($arrayFila[$indice]['placa']) > 0) {
				$posY += 9;
				imagefilledrectangle($img, 0, $posY, 569, $posY+9, ((fmod($contFila2, 2) == 0) ? $backgroundAzul : ""));
					imagestring($img,1,0,$posY,
						strtoupper(str_pad(substr((""),0,6), 6, " ", STR_PAD_LEFT))." ".
						strtoupper(str_pad(substr((""),0,8), 8, " ", STR_PAD_LEFT))." ".
						strtoupper(str_pad(substr(($arrayFila[$indice]['placa']),0,20), 20, " ", STR_PAD_RIGHT))." ".
						strtoupper(str_pad(substr((""),0,14), 14, " ", STR_PAD_RIGHT))." ".
						strtoupper(str_pad(substr((""),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,10), 10, " ", STR_PAD_BOTH))." ".
						strtoupper(str_pad(substr((""),0,8), 8, " ", STR_PAD_LEFT))." ".
						strtoupper(str_pad(substr((""),0,16), 16, " ", STR_PAD_RIGHT))." ".
						strtoupper(str_pad(substr((""),0,14), 14, " ", STR_PAD_LEFT)),$textColor);
				
			} else if (strlen($arrayFila[$indice]['linea']) > 0) {
				$posY += 9;
				imagestring($img,1,0,$posY,str_pad("", 152, $arrayFila[$indice]['linea'], STR_PAD_BOTH),$textColor);
				
			} else if (strlen($arrayFila[$indice]['cant_items']) > 0) {
				$posY += 9;
				imagefilledrectangle($img, 0, $posY, 569, $posY+9, $backgroundGrisClaro);
				imagefilledrectangle($img, 415, $posY, 569, $posY+9, $backgroundVerde);
					imagestring($img,1,0,$posY,
						str_pad("TOTAL ".utf8_encode($row['nom_uni_bas']).":", 83, " ", STR_PAD_LEFT).
						str_pad(utf8_encode($arrayFila[$indice]['cant_items']), 16, " ", STR_PAD_LEFT).
						str_pad(number_format($arrayFila[$indice]['subtotal_costo'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				
			} else if (strlen($arrayFila[$indice]['doble_linea']) > 0) {
				$posY += 9;
				imagestring($img,1,0,$posY,str_pad("", 152, $arrayFila[$indice]['doble_linea'], STR_PAD_BOTH),$textColor);
				
			} else if (strlen($arrayFila[$indice]['linea_vacia']) > 0) {
				$posY += 9;
				imagestring($img,1,0,$posY,str_pad("", 152, $arrayFila[$indice]['linea_vacia'], STR_PAD_BOTH),$textColor);
				
			} else if (strlen($arrayFila[$indice]['total_cant_unidades']) > 0) {
				$posY += 9;
				imagefilledrectangle($img, 0, $posY, 569, $posY+9, $backgroundGrisClaro);
				imagefilledrectangle($img, 415, $posY, 569, $posY+9, $backgroundNaranja);
					imagestring($img,1,0,$posY,
						str_pad("TOTAL DE TOTALES:", 83, " ", STR_PAD_LEFT).
						str_pad($arrayFila[$indice]['total_cant_unidades'], 16, " ", STR_PAD_LEFT).
						str_pad(number_format($arrayFila[$indice]['total_precio_compra'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
			
			if (fmod($contFilaY, $maxRows) == 0 || ($contFila == $totalRows && $contFila2 == $totalRowsDetalle && strlen($arrayFila[$indice]['total_cant_unidades']) > 0)) {
				$pageNum++;
				$arrayImg[] = "tmp/"."unidad_fisica".$pageNum.".png";
				$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
			}
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
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
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
		
		$pdf->Image($valor, 16, $rowConfig10['valor'], 758, 520);
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