<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
require_once("../../connections/conex.php");
session_start();
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

// INSERTA LOS DETALLES DE LAS NOTAS DE CREDITO QUE NO TIENEN
$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_motivo (id_nota_credito, id_motivo, precio_unitario)
SELECT 
	cxc_nc.idNotaCredito,
	cxc_nc.id_motivo,
	cxc_nc.subtotalNotaCredito
FROM cj_cc_notacredito cxc_nc
WHERE cxc_nc.idNotaCredito NOT IN (SELECT cxc_nc_det.id_nota_credito
								FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det)
	AND cxc_nc.id_motivo IS NOT NULL;");
mysql_query("SET NAMES 'utf8';");
$Result1 = mysql_query($insertSQL);
if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
mysql_query("SET NAMES 'latin1';");

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
$pdf->mostrarFooter = 0;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

// BUSCA LOS DATOS DE LA NOTA DE CRÉDITO
$queryEncabezado = sprintf("SELECT cxc_nc.*,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
	vw_pg_empleado.nombre_empleado,
	vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
	 A.fecha_reconversion as reconversion
FROM cj_cc_notacredito cxc_nc
	INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
		LEFT JOIN  cj_cc_notacredito_reconversion  A on (A.id_notacredito=cxc_nc.idNotaCredito)
	LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cxc_nc.id_empleado_vendedor = vw_pg_empleado.id_empleado)
	LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_nc.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
WHERE cxc_nc.idNotaCredito = %s
	AND cxc_nc.idDepartamentoNotaCredito IN (2);",
	valTpDato($idDocumento, "int"));
$rsEncabezado = mysql_query($queryEncabezado, $conex);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsEncabezado = mysql_num_rows($rsEncabezado);
$rowEncabezado = mysql_fetch_assoc($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];
$idFactura = $rowEncabezado['idDocumento'];

// BUSCA LOS DATOS DE LA FACTURA
$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura cxc_fact WHERE cxc_fact.idFactura = %s;",
	valTpDato($idFactura,"int"));
$rsFact = mysql_query($queryFact, $conex);
if (!$rsFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFact = mysql_num_rows($rsFact);
$rowFact = mysql_fetch_assoc($rsFact);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Datos del Sistema GNV en la Impresion de la Factura de Venta y Nota de Crédito)
$queryConfigDatosGNV = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 202 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfigDatosGNV = mysql_query($queryConfigDatosGNV, $conex);
if (!$rsConfigDatosGNV) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowConfigDatosGNV = mysql_fetch_assoc($rsConfigDatosGNV);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Dcto. Identificación (C.I. / R.I.F. / R.U.C. / LIC / SSN) en Documentos Fiscales.)
$queryConfig409 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 409 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig409 = mysql_query($queryConfig409);
if (!$rsConfig409) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig409 = mysql_num_rows($rsConfig409);
$rowConfig409 = mysql_fetch_assoc($rsConfig409);

$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

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

$posY = 9;
imagestring($img,1,300,$posY,str_pad(utf8_decode("NOTA DE CRÉDITO SERIE - V"), 34, " ", STR_PAD_BOTH),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,300,$posY,utf8_decode("NOTA CRÉD. NRO."),$textColor);
imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numeracion_nota_credito'],$textColor);

$posY += 9;
imagestring($img,1,300,$posY,
	str_pad(utf8_decode("FECHA EMISIÓN"), 15, " ", STR_PAD_RIGHT).": ".
	str_pad(strtoupper(utf8_decode(date(spanDateFormat, strtotime($rowEncabezado['fechaNotaCredito'])))), 17, " ", STR_PAD_RIGHT),$textColor);

$posY += 9;
imagestring($img,1,300,$posY,
	str_pad(utf8_decode("FACTURA NRO."), 15, " ", STR_PAD_RIGHT).": ".
	str_pad(strtoupper(utf8_decode($rowFact['numeroFactura'])), 17, " ", STR_PAD_RIGHT),$textColor);

$posY += 9;
imagestring($img,1,300,$posY,
	str_pad(utf8_decode("VENDEDOR"), 15, " ", STR_PAD_RIGHT).": ".
	str_pad(strtoupper(utf8_decode($rowEncabezado['nombre_empleado'])), 17, " ", STR_PAD_RIGHT),$textColor);
	
$posY = 28;
imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id'], 8, " ", STR_PAD_LEFT),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,strtoupper(substr($rowEncabezado['nombre_cliente'],0,60)),$textColor); // <----

if (in_array($rowConfig409['valor'],array("","1"))) {
	$posY += 9;
	imagestring($img,1,0,$posY,utf8_decode($spanClienteCxC).": ".strtoupper($rowEncabezado['ci_cliente']),$textColor);
}
	
$arrayDireccionCliente = wordwrap(str_replace("\n","<br>",str_replace(";", "", $rowEncabezado['direccion_cliente'])), 54, "<br>");
$arrayValor = explode("<br>",$arrayDireccionCliente);
if (isset($arrayValor)) {
	foreach ($arrayValor as $indice => $valor) {
		$posY += 8;
		imagestring($img,1,0,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
	}
}

($rowEncabezado['telf'] != "") ? $arrayTelefono[] = $rowEncabezado['telf'] : "";
($rowEncabezado['otrotelf'] != "") ? $arrayTelefono[] = $rowEncabezado['otrotelf'] : "";
$posY += 9;
imagestring($img,1,95,$posY,
	str_pad(utf8_decode("TELF."), 5, " ", STR_PAD_RIGHT).": ".
	str_pad(strtoupper(utf8_decode(implode("/",$arrayTelefono))), 28, " ", STR_PAD_LEFT),$textColor);


// BUSCA LOS DATOS DE LA UNIDAD
$queryUnidad = sprintf("SELECT 
	vw_iv_modelo.nom_uni_bas,
	vw_iv_modelo.nom_marca,
	vw_iv_modelo.nom_modelo,
	vw_iv_modelo.nom_version,
	vw_iv_modelo.nom_ano,
	vw_iv_modelo.com_uni_bas,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.serial_chasis,
	uni_fis.placa,
	cond_unidad.descripcion AS condicion_unidad,
	color_ext.nom_color AS color_externo1,
	color_ext.des_color AS descripcion_color_externo1,
	uni_fis.kilometraje,
	vw_iv_modelo.com_uni_bas,
	cxc_nc_det_vehic.precio_unitario,
	
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda,
		(SELECT ped_vent.precio_venta FROM an_pedido ped_vent
		WHERE ped_vent.id_pedido = cxc_fact.numeroPedido
			AND ped_vent.id_unidad_fisica = cxc_nc_det_vehic.id_unidad_fisica), cxc_nc_det_vehic.precio_unitario) AS precio_unitario_moneda,

	uni_fis.codigo_unico_conversion,
	uni_fis.marca_kit,
	uni_fis.marca_cilindro,
	uni_fis.modelo_regulador,
	uni_fis.serial1,
	uni_fis.serial_regulador,
	uni_fis.capacidad_cilindro,
	uni_fis.fecha_elaboracion_cilindro,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
FROM cj_cc_notacredito cxc_nc
	INNER JOIN cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic ON (cxc_nc.idNotaCredito = cxc_nc_det_vehic.id_nota_credito)
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
WHERE cxc_nc_det_vehic.id_nota_credito = %s",
	valTpDato($idDocumento, "int"));
$rsUnidad = mysql_query($queryUnidad);
if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsUnidad = mysql_num_rows($rsUnidad);
$rowUnidad = mysql_fetch_array($rsUnidad);

// DETALLES DE LOS MOTIVOS
$queryDetalle = sprintf("SELECT
	cxc_nc_det_motivo.id_nota_credito_detalle_motivo,
	cxc_nc_det_motivo.id_nota_credito,
	cxc_nc_det_motivo.id_motivo,
	motivo.descripcion,
	
	(CASE motivo.modulo
		WHEN 'CC' THEN	'Cuentas por Cobrar'
		WHEN 'CP' THEN	'Cuentas por Pagar'
		WHEN 'CJ' THEN	'Caja'
		WHEN 'TE' THEN	'Tesorería'
	END) AS descripcion_modulo_transaccion,
	
	(CASE motivo.ingreso_egreso
		WHEN 'I' THEN	'Ingreso'
		WHEN 'E' THEN	'Egreso'
	END) AS descripcion_tipo_transaccion,
	
	cxc_nc_det_motivo.cantidad,
	cxc_nc_det_motivo.precio_unitario
FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
	INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
WHERE cxc_nc_det_motivo.id_nota_credito = %s;",
	valTpDato($idDocumento, "int"));
$rsDetalle = mysql_query($queryDetalle, $conex);
if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalle = mysql_num_rows($rsDetalle);
if ($totalRowsDetalle > 0) {
	$pdf->mostrarFooter = 1;
	
	while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		$arrayDetalle[] = array(
			"codigo_articulo" => $rowDetalle['id_motivo'],
			"descripcion_articulo" => $rowDetalle['descripcion'],
			"cantidad" => $rowDetalle['cantidad'],
			'abreviacion_moneda_local' => "",
			'precio_unitario_moneda_local' => "",
			'abreviacion_moneda' => "",
			"precio_unitario" => $rowDetalle['precio_unitario'],
			"precio_sugerido" => "");
	}
} else {
	// DETALLES DE LOS REPUESTOS
	$queryDetalle = sprintf("SELECT
		art.id_articulo,
		art.id_modo_compra,
		art.codigo_articulo,
		tipo_art.descripcion AS descripcion_tipo,
		art.descripcion AS descripcion_articulo,
		subseccion.id_subseccion,
		seccion.descripcion AS descripcion_seccion,
		cxc_nc_det.cantidad,
		cxc_nc_det.precio_unitario,
		cxc_nc_det.precio_sugerido,
		cxc_nc_det.id_iva,
		cxc_nc_det.iva,
		cxc_nc_det.id_nota_credito_detalle,
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_nota_credito_detalle cxc_nc_det ON (cxc_nc.idNotaCredito = cxc_nc_det.id_nota_credito)
			INNER JOIN iv_articulos art ON (cxc_nc_det.id_articulo = art.id_articulo)
				INNER JOIN iv_subsecciones subseccion ON (art.id_subseccion = subseccion.id_subseccion)
				INNER JOIN iv_tipos_articulos tipo_art ON (art.id_tipo_articulo = tipo_art.id_tipo_articulo)
				INNER JOIN iv_secciones seccion ON (subseccion.id_seccion = seccion.id_seccion)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
			LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
				LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
				LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
	WHERE cxc_nc_det.id_nota_credito = %s",
		valTpDato($idDocumento, "int"));
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		$arrayDetalle[] = array(
			"codigo_articulo" => elimCaracter($rowDetalle['codigo_articulo'],";"),
			"descripcion_articulo" => $rowDetalle['descripcion_articulo'],
			"cantidad" => $rowDetalle['cantidad'],
			'abreviacion_moneda_local' => "",
			'precio_unitario_moneda_local' => "",
			'abreviacion_moneda' => $rowDetalle['abreviacion_moneda'],
			"precio_unitario" => $rowDetalle['precio_unitario'],
			"precio_sugerido" => $rowDetalle['precio_sugerido']);
	}
	
	$queryDetalle = sprintf("SELECT
		cxc_nc_det_acc.id_nota_credito_detalle_accesorios,
		cxc_nc_det_acc.id_accesorio,
		CONCAT(acc.nom_accesorio, IF(cxc_nc_det_acc.id_iva = 0, ' (E)', '')) AS nom_accesorio,
		cxc_nc_det_acc.tipo_accesorio,
		cxc_nc_det_acc.precio_unitario,
		
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda,
			(SELECT acc_ped.precio_accesorio
			FROM an_pedido ped_vent
				INNER JOIN an_accesorio_pedido acc_ped ON (ped_vent.id_pedido = acc_ped.id_pedido)
			WHERE acc_ped.id_pedido = cxc_fact.numeroPedido
				AND acc_ped.id_accesorio = cxc_nc_det_acc.id_accesorio), cxc_nc_det_acc.precio_unitario) AS precio_unitario_moneda,
		
		cxc_nc_det_acc.costo_compra,
		cxc_nc_det_acc.tipo_accesorio,
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
	FROM cj_cc_notacredito cxc_nc
		INNER JOIN cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc ON (cxc_nc.idNotaCredito = cxc_nc_det_acc.id_nota_credito)
			INNER JOIN an_accesorio acc ON (cxc_nc_det_acc.id_accesorio = acc.id_accesorio)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
			LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
				LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
				LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
	WHERE cxc_nc_det_acc.id_nota_credito = %s;",
		valTpDato($idDocumento, "int"));
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		$arrayDetalle[] = array(
			'codigo_articulo' => "",
			'descripcion_articulo' => $rowDetalle['nom_accesorio'],
			'cantidad' => 1,
			'abreviacion_moneda_local' => $rowEncabezado['abreviacion_moneda_local'],
			'precio_unitario_moneda_local' => $rowDetalle['precio_unitario'],
			'abreviacion_moneda' => $rowDetalle['abreviacion_moneda'],
			'precio_unitario' => $rowDetalle['precio_unitario_moneda'],
			"precio_sugerido" => "");
	}
}

if (($totalRowsUnidad + $totalRowsDetalle) == 0) {
	$tieneDetalle = false;
	$queryDetalle = sprintf("SELECT
		NULL AS id_nota_credito_detalle_motivo,
		NULL AS id_nota_credito,
		NULL AS id_motivo,
		NULL AS descripcion,
		NULL AS descripcion_modulo_transaccion,
		NULL AS descripcion_tipo_transaccion,
		NULL AS cantidad,
		NULL AS precio_unitario");
	$rsDetalle = mysql_query($queryDetalle, $conex);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
}

$posY = 90;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,
	strtoupper(str_pad(substr(utf8_decode("CÓDIGO"),0,18), 18, " ", STR_PAD_BOTH))." ".
	strtoupper(str_pad(substr(utf8_decode("DESCRIPCIÓN"),0,45), 45, " ", STR_PAD_BOTH))." ".
	strtoupper(str_pad(substr(utf8_decode("CANTIDAD"),0,10), 10, " ", STR_PAD_BOTH))." ".
	strtoupper(str_pad(substr(utf8_decode("TOTAL"),0,18), 18, " ", STR_PAD_BOTH)),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

if ($totalRowsUnidad > 0 && !isset($tieneDetalle)) {
	$posY += 9;
	
	imagestring($img,1,0,$posY,strtoupper(substr($rowUnidad['nom_uni_bas'],0,18)),$textColor);
	imagestring($img,1,95,$posY,
		str_pad(utf8_decode("MARCA"), 23, " ", STR_PAD_RIGHT).": ".
		str_pad(strtoupper(utf8_decode($rowUnidad['nom_marca'])), 16, " ", STR_PAD_RIGHT),$textColor);

	$posY += 9;
	imagestring($img,1,95,$posY,
		str_pad(utf8_decode("MODELO"), 23, " ", STR_PAD_RIGHT).": ".
		str_pad(strtoupper(utf8_decode($rowUnidad['nom_modelo'])), 16, " ", STR_PAD_RIGHT),$textColor);

	$posY += 9;
	imagestring($img,1,95,$posY,
		str_pad(utf8_decode("VERSIÓN"), 23, " ", STR_PAD_RIGHT).": ".
		str_pad(strtoupper(utf8_decode($rowUnidad['nom_version'])), 16, " ", STR_PAD_RIGHT),$textColor);

	$posY += 9;
	imagestring($img,1,95,$posY,
		str_pad(utf8_decode("AÑO"), 23, " ", STR_PAD_RIGHT).": ".
		str_pad(strtoupper(utf8_decode($rowUnidad['nom_ano'])), 16, " ", STR_PAD_RIGHT),$textColor);

	$posY += 12;
	imagestring($img,1,95,$posY,str_pad(strtoupper(utf8_decode($spanPlaca)), 23, " ", STR_PAD_RIGHT).": ",$textColor);
	imagestring($img,2,220,$posY-3,strtoupper($rowUnidad['placa']),$textColor);

	$posY += 12;
	imagestring($img,1,95,$posY,str_pad(strtoupper(utf8_decode($spanSerialCarroceria)), 23, " ", STR_PAD_RIGHT).": ",$textColor);
	imagestring($img,2,220,$posY-3,strtoupper($rowUnidad['serial_carroceria']),$textColor);

	$posY += 12;
	imagestring($img,1,95,$posY,str_pad(strtoupper(utf8_decode($spanSerialMotor)), 23, " ", STR_PAD_RIGHT).": ",$textColor);
	imagestring($img,2,220,$posY-3,strtoupper($rowUnidad['serial_motor']),$textColor);

	$posY += 18;
	imagestring($img,1,95,$posY,
		str_pad(utf8_decode("COLOR CARROCERIA"), 23, " ", STR_PAD_RIGHT).": ".
		str_pad(strtoupper(utf8_decode($rowUnidad['color_externo1'])), 16, " ", STR_PAD_RIGHT),$textColor);
	
	if ($rowConfigDatosGNV['valor'] == 1
	|| ($rowConfigDatosGNV['valor'] == 2
		&& (strlen($rowUnidad['codigo_unico_conversion']) > 1
			|| strlen($rowUnidad['marca_kit']) > 1
			|| strlen($rowUnidad['marca_cilindro']) > 1
			|| strlen($rowUnidad['modelo_regulador']) > 1
			|| strlen($rowUnidad['serial1']) > 1
			|| strlen($rowUnidad['serial_regulador']) > 1
			|| strlen($rowUnidad['capacidad_cilindro']) > 1
			|| strlen($rowUnidad['fecha_elaboracion_cilindro']) > 1))) {
		if ($rowUnidad['com_uni_bas'] == 2 || $rowUnidad['com_uni_bas'] == 5) {
			$posY += 18;
			imagestring($img,1,95,$posY,str_pad(utf8_decode("SISTEMA GNV"), 65, " ", STR_PAD_BOTH),$textColor);
			
			$posY += 9;
			imagestring($img,1,95,$posY,
				str_pad(utf8_decode("CÓDIGO UNICO"), 23, " ", STR_PAD_RIGHT).": ".
				str_pad(strtoupper(utf8_decode($rowUnidad['codigo_unico_conversion'])), 16, " ", STR_PAD_RIGHT),$textColor);

			$posY += 9;
			imagestring($img,1,95,$posY,
				str_pad(utf8_decode("MARCA KIT"), 23, " ", STR_PAD_RIGHT).": ".
				str_pad(strtoupper(utf8_decode($rowUnidad['marca_kit'])), 16, " ", STR_PAD_RIGHT),$textColor);

			$posY += 9;
			imagestring($img,1,95,$posY,
				str_pad(utf8_decode("MARCA CILINDRO"), 23, " ", STR_PAD_RIGHT).": ".
				str_pad(strtoupper(utf8_decode($rowUnidad['marca_cilindro'])), 16, " ", STR_PAD_RIGHT),$textColor);

			$posY += 9;
			imagestring($img,1,95,$posY,
				str_pad(utf8_decode("MODELO REGULADOR"), 23, " ", STR_PAD_RIGHT).": ".
				str_pad(strtoupper(utf8_decode($rowUnidad['modelo_regulador'])), 16, " ", STR_PAD_RIGHT),$textColor);

			$posY += 9;
			imagestring($img,1,95,$posY,
				str_pad(utf8_decode("SERIAL 1"), 23, " ", STR_PAD_RIGHT).": ".
				str_pad(strtoupper(utf8_decode($rowUnidad['serial1'])), 16, " ", STR_PAD_RIGHT),$textColor);

			$posY += 9;
			imagestring($img,1,95,$posY,
				str_pad(utf8_decode("SERIAL REGULADOR"), 23, " ", STR_PAD_RIGHT).": ".
				str_pad(strtoupper(utf8_decode($rowUnidad['serial_regulador'])), 16, " ", STR_PAD_RIGHT),$textColor);

			$posY += 9;
			imagestring($img,1,95,$posY,
				str_pad(utf8_decode("CAPACIDAD CILINDRO (NG)"), 23, " ", STR_PAD_RIGHT).": ".
				str_pad(strtoupper(utf8_decode($rowUnidad['capacidad_cilindro'])), 16, " ", STR_PAD_RIGHT),$textColor);

			$posY += 9;
			imagestring($img,1,95,$posY,
				str_pad(utf8_decode("FECHA ELAB. CILINDRO"), 23, " ", STR_PAD_RIGHT).": ".
				str_pad(strtoupper(utf8_decode(($rowUnidad['fecha_elaboracion_cilindro']) ? ": ".date(spanDateFormat, strtotime($rowUnidad['fecha_elaboracion_cilindro'])) : ": "."----------")), 16, " ", STR_PAD_RIGHT),$textColor);
		}
	}
	
	$posY += 9;
	imagestring($img,1,95,$posY,strtoupper(str_pad("", 45, "-", STR_PAD_RIGHT)),$textColor);
	
	$posY += 9;
	imagestring($img,1,0,$posY,
		strtoupper(str_pad(substr((""),0,18), 18, " ", STR_PAD_RIGHT))." ".
		strtoupper(str_pad(substr(utf8_decode("MONTO VEHÍCULO"),0,45), 45, " ", STR_PAD_RIGHT))." ".
		strtoupper(str_pad(substr((""),0,10), 10, " ", STR_PAD_RIGHT))." ".
		str_pad($rowUnidad['abreviacion_moneda'], 4, " ", STR_PAD_LEFT).
		str_pad(formatoNumero($rowUnidad['precio_unitario_moneda']), 14, " ", STR_PAD_LEFT),$textColor);
	
	$posY += 9;
}

if (isset($tieneDetalle)) {
	$arrayObservacionDcto = str_split(strtoupper($rowEncabezado['observacionesNotaCredito']), 51);
	if (isset($arrayObservacionDcto)) {
		foreach ($arrayObservacionDcto as $indice => $valor) {
			$posY += 9;
			imagestring($img,1,0,$posY,strtoupper(trim($valor)),$textColor);
			if ($indice == 0) {
				imagestring($img,1,260,$posY,str_pad(number_format(1, 2, ".", ","), 10, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,315,$posY,str_pad(number_format($rowEncabezado['subtotalNotaCredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
				imagestring($img,1,395,$posY,str_pad(number_format($rowEncabezado['subtotalNotaCredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT),$textColor);
			}
		}
	}
} else if ($totalRowsDetalle > 0) {
	if (isset($arrayDetalle)) {
		foreach ($arrayDetalle as $indiceDetalle => $valorDetalle) {
			$contFilaY++;
			
			$posY += 9;
			if ($rowEncabezado['id_moneda'] <> $rowEncabezado['id_moneda_tasa_cambio'] && in_array(idArrayPais,array(1))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
				imagestring($img,1,0,$posY,
					strtoupper(str_pad(substr(($valorDetalle['codigo_articulo']),0,18), 18, " ", STR_PAD_RIGHT))." ".
					strtoupper(str_pad(substr(($valorDetalle['descripcion_articulo']),0,26), 26, " ", STR_PAD_RIGHT))." ".
					str_pad($valorDetalle['abreviacion_moneda_local'], 4, " ", STR_PAD_LEFT).
					str_pad(formatoNumero($valorDetalle['precio_unitario_moneda_local']), 14, " ", STR_PAD_LEFT)." ".
					str_pad(formatoNumero($valorDetalle['cantidad']), 10, " ", STR_PAD_LEFT)." ".
					str_pad($valorDetalle['abreviacion_moneda'], 4, " ", STR_PAD_LEFT).
					str_pad(formatoNumero($valorDetalle['cantidad'] * $valorDetalle['precio_unitario']), 14, " ", STR_PAD_LEFT),$textColor);
			} else {
				imagestring($img,1,0,$posY,
					strtoupper(str_pad(substr(($valorDetalle['codigo_articulo']),0,18), 18, " ", STR_PAD_RIGHT))." ".
					strtoupper(str_pad(substr(($valorDetalle['descripcion_articulo']),0,45), 45, " ", STR_PAD_RIGHT))." ".
					str_pad(formatoNumero($valorDetalle['cantidad']), 10, " ", STR_PAD_LEFT)." ".
					str_pad($valorDetalle['abreviacion_moneda'], 4, " ", STR_PAD_LEFT).
					str_pad(formatoNumero($valorDetalle['cantidad'] * $valorDetalle['precio_unitario']), 14, " ", STR_PAD_LEFT),$textColor);
			}
		}
	}
}

$posY = 460;

$arrayObservacionDcto = (isset($tieneDetalle)) ? "" : wordwrap(str_replace("\n","<br>",str_replace(";", "", $rowEncabezado['observacionesNotaCredito'])), 47, "<br>");
if (strlen($arrayObservacionDcto) > 0 || strlen($rowEncabezado['numero_siniestro']) > 0) {
	$arrayValor = explode("<br>",$arrayObservacionDcto);
	if (isset($arrayValor)) {
		foreach ($arrayValor as $indice => $valor) {
			$posY += 8;
			imagestring($img,1,0,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
		}
	}
	if (strlen($rowEncabezado['numero_siniestro']) > 0) {
		$posY += 9;
		imagestring($img,1,0,$posY,utf8_decode("NRO. SINIESTRO"),$textColor);
		imagestring($img,1,70,$posY,": ".$rowEncabezado['numero_siniestro'],$textColor);
	}
}

if ($totalRowsFact > 0) {
	$posY += 9;
	imagestring($img,1,0,$posY,"NOTA DE CREDITO QUE HACE REFERENCIA A",$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,"FACT. NRO ".$rowFact['numeroFactura']." NRO CONTROL ".$rowFact['numeroControl'],$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,"DE FECHA ".date(spanDateFormat, strtotime($rowFact['fechaRegistroFactura'])),$textColor);
}

$posY = 460;

$posY += 9;
imagestring($img,1,240,$posY,
	str_pad("SUBTOTAL", 16, " ", STR_PAD_RIGHT).":".
	str_pad("", 8, " ", STR_PAD_LEFT).
	str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
	str_pad(formatoNumero($rowEncabezado['subtotalNotaCredito']), 15, " ", STR_PAD_LEFT),$textColor);

if ($rowEncabezado['porcentaje_descuento'] == "") {
	$porcDescuento = ($rowEncabezado['descuentoFactura'] * 100) / $rowEncabezado['subtotalNotaCredito'];
	$subtotalDescuento = $rowEncabezado['descuentoFactura'];
} else {
	$porcDescuento = $rowEncabezado['porcentaje_descuento'];
	$subtotalDescuento = $rowEncabezado['subtotal_descuento'];
}

if ($subtotalDescuento > 0) {
	$posY += 9;
	imagestring($img,1,240,$posY,
		str_pad("DESCUENTO", 16, " ", STR_PAD_RIGHT).":".
		str_pad($porcDescuento, 8, " ", STR_PAD_LEFT).
		str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
		str_pad(formatoNumero($subtotalDescuento), 15, " ", STR_PAD_LEFT),$textColor);
}

if ($totalGastosConIva != 0) {
	$posY += 9;
	imagestring($img,1,240,$posY,
		str_pad("GASTOS C/IMPTO", 16, " ", STR_PAD_RIGHT).":".
		str_pad("", 8, " ", STR_PAD_LEFT).
		str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
		str_pad(formatoNumero($totalGastosConIva), 15, " ", STR_PAD_LEFT),$textColor);
}
		
$queryIvaFact = sprintf("SELECT
	iva.observacion,
	
	cxc_nc_iva.base_imponible,
	cxc_nc_iva.iva,
	cxc_nc_iva.subtotal_iva,
	
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_nc_iva.base_imponible / ped_vent.monto_tasa_cambio), cxc_nc_iva.base_imponible) AS base_imponible_moneda,
	cxc_nc_iva.iva AS iva_moneda,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_nc_iva.subtotal_iva / ped_vent.monto_tasa_cambio), cxc_nc_iva.subtotal_iva) AS subtotal_iva_moneda,
	
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
FROM cj_cc_notacredito cxc_nc
	INNER JOIN cj_cc_nota_credito_iva cxc_nc_iva ON (cxc_nc.idNotaCredito = cxc_nc_iva.id_nota_credito)
		INNER JOIN pg_iva iva ON (cxc_nc_iva.id_iva = iva.idIva)
	LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
		LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
WHERE cxc_nc.idNotaCredito = %s;",
	valTpDato($idDocumento, "int"));
$rsIvaFact = mysql_query($queryIvaFact);
if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRows = mysql_num_rows($rsIvaFact);
if ($totalRows > 0) {
	while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
		$posY += 9;
		imagestring($img,1,240,$posY,
			str_pad("BASE IMPONIBLE", 16, " ", STR_PAD_RIGHT).":".
			str_pad("", 8, " ", STR_PAD_LEFT).
			str_pad($rowIvaFact['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowIvaFact['base_imponible_moneda']), 15, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
		imagestring($img,1,240,$posY,
			str_pad(substr(strtoupper(utf8_decode($rowIvaFact['observacion'])),0,16), 16, " ", STR_PAD_RIGHT).":".
			str_pad(formatoNumero($rowIvaFact['iva_moneda'])."%", 8, " ", STR_PAD_LEFT).
			str_pad($rowIvaFact['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowIvaFact['subtotal_iva_moneda']), 15, " ", STR_PAD_LEFT),$textColor);
		
		$totalIva += $rowIvaFact['subtotal_iva_moneda'];
	}
} else {
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1;");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowIva = mysql_fetch_assoc($rsIva);
	
	$posY += 9;
	imagestring($img,1,240,$posY,
		str_pad("BASE IMPONIBLE", 16, " ", STR_PAD_RIGHT).":".
		str_pad("", 8, " ", STR_PAD_LEFT).
		str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
		str_pad(formatoNumero($rowEncabezado['baseimponibleNotaCredito']), 15, " ", STR_PAD_LEFT),$textColor);
	
	if ($rowEncabezado['porcentajeIvaNotaCredito'] > 0) {
		$porcIva = $rowEncabezado['porcentajeIvaNotaCredito'];
	} else if ($rowEncabezado['baseimponibleNotaCredito'] > 0) {
		$porcIva = (doubleval($rowEncabezado['ivaNotaCredito']) * 100) / doubleval($rowEncabezado['baseimponibleNotaCredito']);
	}
	$posY += 9;
	imagestring($img,1,240,$posY,
		str_pad(substr(strtoupper(utf8_decode($rowIva['observacion'])),0,16), 16, " ", STR_PAD_RIGHT).":".
		str_pad(formatoNumero($porcIva)."%", 8, " ", STR_PAD_LEFT).
		str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
		str_pad(formatoNumero($rowEncabezado['ivaNotaCredito']), 15, " ", STR_PAD_LEFT),$textColor);
	
	if ($rowEncabezado['ivaLujoNotaCredito'] > 0) {
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (2) AND iva.estado = 1 AND iva.activo = 1;");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowIva = mysql_fetch_assoc($rsIva);
		
		if ($rowEncabezado['ivaLujoNotaCredito'] > 0) {
			$porcIvaLujo = $rowEncabezado['ivaLujoNotaCredito'];
		} else if ($rowEncabezado['baseimponibleNotaCredito'] > 0) {
			$porcIvaLujo = (doubleval($rowEncabezado['ivaLujoNotaCredito']) * 100) / doubleval($rowEncabezado['baseimponibleNotaCredito']);
		}
		$posY += 9;
		imagestring($img,1,240,$posY,
			str_pad(substr(strtoupper(utf8_decode($rowIva['observacion'])),0,16), 16, " ", STR_PAD_RIGHT).":".
			str_pad(formatoNumero($porcIvaLujo)."%", 8, " ", STR_PAD_LEFT).
			str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowEncabezado['ivaLujoNotaCredito']), 15, " ", STR_PAD_LEFT),$textColor);
	}
}

if ($totalGastosSinIva != 0) {
	$posY += 9;
	imagestring($img,1,240,$posY,
		str_pad("GASTOS S/IMPTO", 16, " ", STR_PAD_RIGHT).":".
		str_pad("", 8, " ", STR_PAD_LEFT).
		str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
		str_pad(formatoNumero($totalGastosSinIva), 15, " ", STR_PAD_LEFT),$textColor);
}

if ($rowEncabezado['montoExentoCredito'] != 0) {
	$posY += 9;
	imagestring($img,1,240,$posY,
		str_pad("MONTO EXENTO", 16, " ", STR_PAD_RIGHT).":".
		str_pad("", 8, " ", STR_PAD_LEFT).
		str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
		str_pad(formatoNumero($rowEncabezado['montoExentoCredito']), 15, " ", STR_PAD_LEFT),$textColor);
}

if ($rowEncabezado['montoExoneradoCredito'] != 0) {
	$posY += 9;
	imagestring($img,1,240,$posY,
		str_pad("MONTO EXONERADO", 16, " ", STR_PAD_RIGHT).":".
		str_pad("", 8, " ", STR_PAD_LEFT).
		str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
		str_pad(formatoNumero($rowEncabezado['montoExoneradoCredito']), 15, " ", STR_PAD_LEFT),$textColor);
}

$posY += 8;
imagestring($img,1,240,$posY,str_pad("", 46, "-", STR_PAD_LEFT),$textColor);

//$rowFact['fechaRegistroFactura']

                                               $fechaRegistroFactura = date_create_from_format('Y-m-d',$rowFact['fechaRegistroFactura']);
                                               $fechaReconversion = date_create_from_format('Y-m-d','2018-08-01');
                                               $fechaAjusten = date_create_from_format('Y-m-d','2018-08-20');

                                               if ($rowEncabezado['reconversion'] == null) {
                                                               if ($fechaRegistroFactura>=$fechaReconversion and $fechaRegistroFactura<$fechaAjusten) {
                                                                               $posY += 8;
                                                                               imagestring($img,1,240,$posY,str_pad("TOTAL ", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,333,$posY,
                                                                               str_pad('Bs', 6, " ", STR_PAD_LEFT).
                                                                               str_pad(formatoNumero($rowEncabezado['montoNetoNotaCredito']), 17, " ", STR_PAD_LEFT),$textColor);
                                                                               $posY += 10;
                                                                              imagestring($img,1,240,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,333,$posY,
                                                                                              str_pad("Bs.S", 6, " ", STR_PAD_LEFT).
                                                                                               str_pad(formatoNumero($rowEncabezado['montoNetoNotaCredito']/100000), 17, " ", STR_PAD_LEFT),$textColor);
                                                               }else if ($fechaRegistroFactura>=$fechaAjusten) {
                                                                               $posY += 8;
                                                                               imagestring($img,1,240,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,333,$posY,
                                                                                              str_pad("Bs.S", 6, " ", STR_PAD_LEFT).
                                                                                              str_pad(formatoNumero($rowEncabezado['montoNetoNotaCredito']), 17, " ", STR_PAD_LEFT),$textColor);
                                                                               $posY += 10;
                                                                               imagestring($img,1,240,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,333,$posY,
                                                                               str_pad('Bs', 6, " ", STR_PAD_LEFT).
                                                                               str_pad(formatoNumero($rowEncabezado['montoNetoNotaCredito']*100000), 17, " ", STR_PAD_LEFT),$textColor);

                                                               }else{
                                                                               $posY += 8;
                                                                               imagestring($img,1,240,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,333,$posY,
                                                                                              str_pad('Bs', 6, " ", STR_PAD_LEFT).
                                                                                              str_pad(formatoNumero($rowEncabezado['montoNetoNotaCredito']), 17, " ", STR_PAD_LEFT),$textColor);
                                                               }
                                               }else{

                                                               if ($fechaRegistroFactura>=$fechaReconversion and $fechaRegistroFactura<$fechaAjusten) {
                                                                               $posY += 8;
                                                                               imagestring($img,1,240,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,333,$posY,
                                                                               str_pad('Bs', 6, " ", STR_PAD_LEFT).
                                                                               str_pad(formatoNumero($rowEncabezado['montoNetoNotaCredito']*100000), 17, " ", STR_PAD_LEFT),$textColor);
                                                                               $posY += 10;
                                                                               imagestring($img,1,240,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,333,$posY,
                                                                                              str_pad("Bs.S", 6, " ", STR_PAD_LEFT).
                                                                                              str_pad(formatoNumero($rowEncabezado['montoNetoNotaCredito']), 17, " ", STR_PAD_LEFT),$textColor);
                                                               }else if ($fechaRegistroFactura>=$fechaAjusten) {
                                                                               $posY += 8;
                                                                               imagestring($img,1,240,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,333,$posY,
                                                                                              str_pad("Bs.S", 6, " ", STR_PAD_LEFT).
                                                                                              str_pad(formatoNumero($rowEncabezado['montoNetoNotaCredito']), 17, " ", STR_PAD_LEFT),$textColor);
                                                                               $posY += 10;
                                                                               imagestring($img,1,240,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,333,$posY,
                                                                               str_pad('Bs', 6, " ", STR_PAD_LEFT).
                                                                               str_pad(formatoNumero($rowEncabezado['montoNetoNotaCredito']*100000), 17, " ", STR_PAD_LEFT),$textColor);

                                                               }else{
                                                                               $posY += 8;
                                                                               imagestring($img,1,240,$posY,str_pad("TOTAL", 16, " ", STR_PAD_RIGHT).":",$textColor);
                                                                               imagestring($img,2,333,$posY,
                                                                                              str_pad("Bs.S", 6, " ", STR_PAD_LEFT).
                                                                                              str_pad(formatoNumero($rowEncabezado['montoNetoNotaCredito']), 17, " ", STR_PAD_LEFT),$textColor);
                                                               }              
                                               }

$pageNum++;
$arrayImg[] = "tmp/"."devolucion_venta_vehiculo".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

$pdf->nombreRegistrado = $rowEncabezado['nombre_empleado_creador'];
if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 688);
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

function formatoNumero($monto){
    return number_format($monto, 2, ".", ",");
}
?>
