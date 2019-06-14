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
/**************************** ARCHIVO PDF ****************************/
$maxRows = 14;
$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];
$estatusSolicitud = $valCadBusq[1];
$fechaMovimiento = $valCadBusq[2];

// BUSCA LOS DATOS DE LA SOLICITUD
$queryEncabezado = sprintf("SELECT
	orden.id_empresa,
	orden.id_orden,
	orden.numero_orden,
	orden.tiempo_orden,
	orden.id_estado_orden,
	estado_orden.nombre_estado,
	tp_orden.nombre_tipo_orden,
	uni_bas.imagen_auto,
	vw_sa_vale_recep.chasis,
	vw_sa_vale_recep.placa,
	sol_rep.id_solicitud,
	sol_rep.numero_solicitud,
	sol_rep.tiempo_solicitud,
	sol_rep.id_empleado_entrega,
	sol_rep.id_jefe_taller,
	sol_rep.id_empleado_devuelto,
	sol_rep.id_empleado_recibo,
	sol_rep.id_empleado_recibo,
	estado_sol.id_estado_solicitud,
	estado_sol.descripcion_estado_solicitud,
	
	IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago FROM sa_recepcion r
			WHERE r.id_recepcion = orden.id_recepcion), (SELECT c.id_cliente_contacto AS id_cliente_contacto FROM sa_cita c
														WHERE c.id_cita = (SELECT r.id_cita AS id_cita FROM sa_recepcion r
																			WHERE r.id_recepcion = orden.id_recepcion))) AS id_cliente,
	
	(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido) FROM cj_cc_cliente cliente
	WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago FROM sa_recepcion r
									WHERE r.id_recepcion = orden.id_recepcion), (SELECT c.id_cliente_contacto AS id_cliente_contacto FROM sa_cita c
																				WHERE c.id_cita = (SELECT r.id_cita AS id_cita FROM sa_recepcion r
																									WHERE r.id_recepcion = orden.id_recepcion))) AS id_cliente)) AS nombre_cliente,
	
	CONCAT_WS(', ', CONCAT_WS(' ', 'Motor', ccc_uni_bas), CONCAT_WS(' ', cil_uni_bas, 'Cilindros')) AS motor,
	
	(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_empleado_entrega) AS nombre_empleado_entrega,
	(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_jefe_taller) AS nombre_empleado_jefe_taller,
	(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_jefe_repuesto) AS nombre_empleado_jefe_repuestos,
	(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_gerente_postventa) AS nombre_empleado_gte_postventa,
	(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_empleado_recibo) AS nombre_empleado_recibo,
	(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_empleado_devuelto) AS nombre_empleado_devuelto
FROM sa_tipo_orden tp_orden
	INNER JOIN sa_orden orden ON (tp_orden.id_tipo_orden = orden.id_tipo_orden)
	INNER JOIN sa_estado_orden estado_orden ON (orden.id_estado_orden = estado_orden.id_estado_orden)
	INNER JOIN sa_solicitud_repuestos sol_rep ON (orden.id_orden = sol_rep.id_orden)
	INNER JOIN sa_estado_solicitud estado_sol ON (sol_rep.estado_solicitud = estado_sol.id_estado_solicitud)
	INNER JOIN vw_sa_vales_recepcion vw_sa_vale_recep ON (orden.id_recepcion = vw_sa_vale_recep.id_recepcion)
	INNER JOIN an_uni_bas uni_bas ON (vw_sa_vale_recep.id_uni_bas = uni_bas.id_uni_bas)
WHERE sol_rep.id_solicitud = %s;",
	valTpDato($idDocumento,"int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];


$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("sol_rep.id_solicitud = %s",
	valTpDato($idDocumento, "int"));

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("kardex_surtido.id_estado_solicitud = %s",
	valTpDato($estatusSolicitud, "int"));

$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("kardex_surtido.fecha_movimiento = %s",
	valTpDato($fechaMovimiento, "date"));

// BUSCA LOS DATOS DEL SURTIDO
$queryPie = sprintf("SELECT 
	sol_rep.id_orden,
	sol_rep.id_solicitud,
	kardex_surtido.fecha_movimiento,
	kardex_surtido.id_estado_solicitud,
	kardex_surtido.id_empleado_despacha,
	kardex_surtido.id_empleado_recibe,
	estado_sol.descripcion_estado_solicitud,
	
	(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = kardex_surtido.id_empleado_despacha) AS nombre_empleado_entrega,
	(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = kardex_surtido.id_empleado_recibe) AS nombre_empleado_recibo_devuelto
FROM sa_solicitud_repuestos sol_rep
	INNER JOIN iv_kardex_surtido kardex_surtido ON (sol_rep.id_solicitud = kardex_surtido.id_solicitud)
	INNER JOIN sa_estado_solicitud estado_sol ON (kardex_surtido.id_estado_solicitud = estado_sol.id_estado_solicitud) %s
GROUP BY sol_rep.id_orden,
	sol_rep.id_solicitud,
	kardex_surtido.fecha_movimiento,
	kardex_surtido.id_estado_solicitud,
	kardex_surtido.id_empleado_despacha,
	kardex_surtido.id_empleado_recibe", $sqlBusq);
$rsPie = mysql_query($queryPie, $conex);
if (!$rsPie) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowPie = mysql_fetch_assoc($rsPie);


$sqlBusq = "";
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("sol_rep.id_solicitud = %s",
	valTpDato($idDocumento, "int"));

/*$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("det_sol_rep.id_estado_solicitud = %s",
	valTpDato($estatusSolicitud, "int"));*/

if ($estatusSolicitud == 3) { // 3 = DESPACHADO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("det_sol_rep.tiempo_despacho = %s",
		valTpDato($fechaMovimiento, "date"));
} else if ($estatusSolicitud == 4) { // 4 = DEVUELTO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("det_sol_rep.tiempo_devolucion = %s",
		valTpDato($fechaMovimiento, "date"));
}

// DETALLES DE LOS REPUESTOS
$queryDetalle = sprintf("SELECT
	det_sol_rep.id_det_solicitud_repuesto,
	sol_rep.id_empresa,
	art.id_articulo,
	art.codigo_articulo,
	art.descripcion AS descripcion_articulo,
	det_sol_rep.id_casilla,
	vw_iv_casilla.descripcion_almacen,
	vw_iv_casilla.ubicacion,
	COUNT(art.id_articulo) AS cantidad,
	det_ord_art.id_articulo_costo,
	det_ord_art.precio_unitario,
	det_ord_art.id_iva,
	det_ord_art.iva,
	det_sol_rep.id_estado_solicitud,
	det_sol_rep.tiempo_aprobacion,
	det_sol_rep.tiempo_despacho,
	det_sol_rep.tiempo_devolucion,
	det_sol_rep.tiempo_anulacion
FROM sa_solicitud_repuestos sol_rep
	INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
	INNER JOIN sa_det_orden_articulo det_ord_art ON (det_sol_rep.id_det_orden_articulo = det_ord_art.id_det_orden_articulo)
	INNER JOIN iv_articulos art ON (det_ord_art.id_articulo = art.id_articulo)
	INNER JOIN vw_iv_casillas vw_iv_casilla ON (det_sol_rep.id_casilla = vw_iv_casilla.id_casilla) %s
GROUP BY art.id_articulo,
	art.codigo_articulo,
	art.descripcion,
	det_sol_rep.id_casilla,
	det_ord_art.id_articulo_costo,
	det_ord_art.precio_unitario,
	det_ord_art.iva,
	sol_rep.id_empresa,
	det_sol_rep.id_estado_solicitud
ORDER BY CONCAT(vw_iv_casilla.descripcion_almacen, vw_iv_casilla.ubicacion), det_sol_rep.id_det_solicitud_repuesto DESC", $sqlBusq);
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
	$contFila++;
	$contFilaY++;
	
	if (fmod($contFilaY, $maxRows) == 1) {
		$img = @imagecreate(570, 390) or die("No se puede crear la imagen");
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		$backgroundGris = imagecolorallocate($img, 230, 230, 230);
		$backgroundAzul = imagecolorallocate($img, 226, 239, 254);
		
		switch ($estatusSolicitud) {
			case 3 : $tituloComprobanteSolicitud = "COMPROBANTE DE SURTIDO DE REPUESTOS"; break;
			case 4 : $tituloComprobanteSolicitud = "COMPROBANTE DE DEVOLUCIÓN DE REPUESTOS"; break;
		}
		
		$posY = 0;
		imagestring($img,1,350,$posY,str_pad(utf8_decode($tituloComprobanteSolicitud), 44, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,350,$posY,utf8_decode("NRO. SOLICITUD"),$textColor);
		imagestring($img,2,430,$posY-3,": ".$rowEncabezado['numero_solicitud'],$textColor);
		
		$posY += 9;
		imagestring($img,1,350,$posY,utf8_decode("NRO. ORDEN"),$textColor);
		imagestring($img,2,430,$posY-3,": ".$rowEncabezado['numero_orden'],$textColor);
		
		$posY += 9;
		imagestring($img,1,350,$posY,utf8_decode("FECHA EMISIÓN"),$textColor);
		imagestring($img,1,430,$posY,": ".date(spanDateFormat,strtotime($rowEncabezado['tiempo_solicitud'])),$textColor);
		
		$posY += 9;
		imagestring($img,1,350,$posY,utf8_decode("ESTADO SOLICITUD"),$textColor);
		imagestring($img,1,430,$posY,": ".$rowEncabezado['descripcion_estado_solicitud'],$textColor);
		
		$posY += 9;
		imagestring($img,1,350,$posY,utf8_decode("JEFE TALLER"),$textColor);
		imagestring($img,1,430,$posY,": ".strtoupper($rowEncabezado['nombre_empleado_jefe_taller']),$textColor);
		
		$posY = 28;
		imagestring($img,1,200,$posY,utf8_decode("CÓDIGO"),$textColor);
		imagestring($img,1,230,$posY,": ".$rowEncabezado['id_cliente'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode("CLIENTE"),$textColor);
		imagestring($img,1,35,$posY,": ".strtoupper($rowEncabezado['nombre_cliente']),$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode("PLACA"),$textColor);
		imagestring($img,1,35,$posY,": ".$rowEncabezado['placa'],$textColor);
		
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode("CHASIS"),$textColor);
		imagestring($img,1,35,$posY,": ".$rowEncabezado['chasis'],$textColor);
		
		
		switch ($estatusSolicitud) {
			case 3 : $tituloEstatusSolicitud = "DESPACHO"; break;
			case 4 : $tituloEstatusSolicitud = "DEVUELTO"; break;
		}
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagefilledrectangle($img, 0, $posY-4, 569, $posY+4+9, $backgroundGris);
		imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO / DESCRIPCIÓN"), 32, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,165,$posY,str_pad(utf8_decode("CANTIDAD"), 15, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,245,$posY,str_pad(utf8_decode("ALMACEN"), 26, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,380,$posY,str_pad(utf8_decode("UBICACIÓN"), 19, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,480,$posY,str_pad(utf8_decode($tituloEstatusSolicitud), 10, " ", STR_PAD_BOTH),$textColor);
		imagestring($img,1,535,$posY,str_pad(utf8_decode("CHEQUEO"), 7, " ", STR_PAD_BOTH),$textColor);
		$posY += 9;
		imagestring($img,1,0,$posY,str_pad("", 114, "-", STR_PAD_BOTH),$textColor);
	}
	
	switch ($estatusSolicitud) {
		case 3 : $fechaMovimiento = $rowDetalle['tiempo_despacho']; break;
		case 4 : $fechaMovimiento = $rowDetalle['tiempo_devolucion']; break;
	}
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 569, $posY+9, $backgroundAzul);
	imagestring($img,1,0,$posY,elimCaracter($rowDetalle['codigo_articulo'],";"),$textColor);
	imagestring($img,1,165,$posY,strtoupper(str_pad(number_format($rowDetalle['cantidad'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	imagestring($img,1,245,$posY,strtoupper(substr($rowDetalle['descripcion_almacen'],0,26)),$textColor);
	imagestring($img,1,380,$posY,strtoupper(str_replace("-[]", "", $rowDetalle['ubicacion'])),$textColor);
	imagestring($img,1,480,$posY,str_pad(date(spanDateFormat, strtotime($fechaMovimiento)), 10, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 9;
	(fmod($contFila, 2) == 0) ? "" : imagefilledrectangle($img, 0, $posY, 569, $posY+9, $backgroundAzul);
	imagestring($img,1,0,$posY,strtoupper(substr($rowDetalle['descripcion_articulo'],0,32)),$textColor);
	if (!in_array($_SESSION['idMetodoCosto'], array(1,2))) {
		imagestring($img,1,165,$posY,strtoupper("LOTE:".str_pad($rowDetalle['id_articulo_costo'], 10, " ", STR_PAD_LEFT)),$textColor);
	}
	imagestring($img,1,480,$posY,str_pad(date("h:i:s a", strtotime($fechaMovimiento)), 10, " ", STR_PAD_BOTH),$textColor);
		
	if (fmod($contFilaY, $maxRows) == 0 || $contFila == $totalRowsDetalle) {
		if ($contFila == $totalRowsDetalle) {
			$posY = 370;
			switch ($estatusSolicitud) {
				case 3 : 
					imagestring($img,1,0,$posY,"DESPACHADO POR",$textColor);
					imagestring($img,1,70,$posY,": ".strtoupper($rowPie['nombre_empleado_entrega']),$textColor);
					
					imagestring($img,1,300,$posY,"DESPACHADO A",$textColor);
					imagestring($img,1,370,$posY,": ".strtoupper($rowPie['nombre_empleado_recibo_devuelto']),$textColor);
					break;
				case 4 :
					imagestring($img,1,0,$posY,"DEVUELTO A",$textColor);
					imagestring($img,1,70,$posY,": ".strtoupper($rowPie['nombre_empleado_entrega']),$textColor);
					
					imagestring($img,1,300,$posY,"DEVUELTO POR",$textColor);
					imagestring($img,1,370,$posY,": ".strtoupper($rowPie['nombre_empleado_recibo_devuelto']),$textColor);
					break;
			}
		}
		
		$pageNum++;
		$arrayImg[] = "tmp/"."surtido_repuestos".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
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

$pdf->nombreRegistrado = $rowPie['nombre_empleado_entrega'];
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