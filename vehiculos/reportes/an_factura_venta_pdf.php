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
$pdf->SetAutoPageBreak(1,"0");
$pdf->mostrarFooter = 0;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

// VERIFICA SI TIENE IMPUESTO DE VENTA
$queryIva = sprintf("SELECT 
	cxc_fact.baseImponible,
	cxc_fact.porcentajeIvaFactura,
	cxc_fact.calculoIvaFactura
FROM cj_cc_encabezadofactura cxc_fact
WHERE cxc_fact.idFactura = %s
	AND cxc_fact.calculoIvaFactura > 0;",
	valTpDato($idDocumento, "int"));
$rsIva = mysql_query($queryIva, $conex);
if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsIva = mysql_num_rows($rsIva);

// VERIFICA SI TIENE DETALLE DE IMPUESTO
$queryFactIva = sprintf("SELECT * FROM cj_cc_factura_iva
WHERE id_factura = %s
	AND id_iva IN (SELECT idIva FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1);",
	valTpDato($idDocumento, "int"));
$rsFactIva = mysql_query($queryFactIva, $conex);
if (!$rsFactIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFactIva = mysql_num_rows($rsFactIva);

if ($totalRowsIva > 0 && !($totalRowsFactIva > 0)) {
	$rowIva = mysql_fetch_assoc($rsIva);
	
	// INSERTA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	/*$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
	SELECT %s, %s, %s, idIva, %s, IF (iva.tipo IN (3,2), 1, NULL) FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1;",
		valTpDato($idDocumento, "int"),
		valTpDato($rowIva['baseImponible'], "real_inglesa"),
		valTpDato($rowIva['calculoIvaFactura'],"real_inglesa"),
		valTpDato($rowIva['porcentajeIvaFactura'], "real_inglesa"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");*/
}

// VERIFICA SI TIENE IMPUESTO DE VENTA AL LUJO
$queryIva = sprintf("SELECT 
	cxc_fact.base_imponible_iva_lujo,
	cxc_fact.porcentajeIvaDeLujoFactura,
	cxc_fact.calculoIvaDeLujoFactura
FROM cj_cc_encabezadofactura cxc_fact
WHERE cxc_fact.idFactura = %s
	AND cxc_fact.calculoIvaDeLujoFactura > 0;",
	valTpDato($idDocumento, "int"));
$rsIva = mysql_query($queryIva, $conex);
if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsIva = mysql_num_rows($rsIva);

// VERIFICA SI TIENE DETALLE DE IMPUESTO
$queryFactIva = sprintf("SELECT * FROM cj_cc_factura_iva
WHERE id_factura = %s
	AND id_iva IN (SELECT idIva FROM pg_iva iva WHERE iva.tipo IN (2) AND iva.estado = 1 AND iva.activo = 1);",
	valTpDato($idDocumento, "int"));
$rsFactIva = mysql_query($queryFactIva, $conex);
if (!$rsFactIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFactIva = mysql_num_rows($rsFactIva);

if ($totalRowsIva > 0 && !($totalRowsFactIva > 0)) {
	$rowIva = mysql_fetch_assoc($rsIva);
	
	// INSERTA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$insertSQL = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
	SELECT %s, %s, %s, idIva, %s, IF (iva.tipo IN (3,2), 1, NULL) FROM pg_iva iva WHERE iva.tipo IN (2) AND iva.estado = 1 AND iva.activo = 1;",
		valTpDato($idDocumento, "int"),
		valTpDato($rowIva['base_imponible_iva_lujo'], "real_inglesa"),
		valTpDato($rowIva['calculoIvaDeLujoFactura'],"real_inglesa"),
		valTpDato($rowIva['porcentajeIvaDeLujoFactura'], "real_inglesa"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
}

// BUSCA LOS DATOS DEL DOCUMENTO
$queryEncabezado = sprintf("SELECT 
	cxc_fact.id_empresa,
	cxc_fact.numeroFactura,
	ped_vent.id_pedido,
	ped_vent.id_moneda,
	ped_vent.id_moneda_tasa_cambio,
	ped_vent.id_tasa_cambio,
	ped_vent.monto_tasa_cambio,
	ped_vent.fecha_tasa_cambio,
	moneda_local.descripcion AS descripcion_moneda_local,
	moneda_local.abreviacion AS abreviacion_moneda_local,
	moneda_extranjera.descripcion AS descripcion_moneda_extranjera,
	moneda_extranjera.abreviacion AS abreviacion_moneda_extranjera,
	ped_vent.numeracion_pedido,
		ped_vent.porcentaje_inicial,
		ped_vent.inicial,
        banco.nombreBanco,
        ped_vent.meses_financiar,
        ped_vent.interes_cuota_financiar,
        ped_vent.cuotas_financiar,
		ped_vent.meses_financiar2,
		ped_vent.interes_cuota_financiar2,
		ped_vent.cuotas_financiar2,
		ped_vent.meses_financiar3,
		ped_vent.interes_cuota_financiar3,
		ped_vent.cuotas_financiar3,
		ped_vent.meses_financiar4,
		ped_vent.interes_cuota_financiar4,
		ped_vent.cuotas_financiar4,
		ped_vent.fecha_entrega,
	cxc_fact.fechaRegistroFactura,
	cxc_fact.fechaVencimientoFactura,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	cliente.correo,
        prospecto.fecha_nacimiento,
	cxc_fact.observacionFactura,
	
	cxc_fact.subtotalFactura AS subtotal_factura,
	cxc_fact.porcentaje_descuento,
	cxc_fact.descuentoFactura AS subtotal_descuento,
	cxc_fact.baseImponible AS base_imponible,
	cxc_fact.porcentajeIvaFactura AS porcentaje_iva,
	cxc_fact.calculoIvaFactura AS subtotal_iva,
	cxc_fact.base_imponible_iva_lujo AS base_imponible_iva_lujo,
	cxc_fact.porcentajeIvaDeLujoFactura AS porcentaje_iva_lujo,
	cxc_fact.calculoIvaDeLujoFactura AS subtotal_iva_lujo,
	cxc_fact.montoExento AS monto_exento,
	cxc_fact.montoExonerado AS monto_exonerado,
	
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact.subtotalFactura / ped_vent.monto_tasa_cambio), cxc_fact.subtotalFactura) AS subtotal_factura_moneda,
	cxc_fact.porcentaje_descuento AS porcentaje_descuento_moneda,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact.descuentoFactura / ped_vent.monto_tasa_cambio), cxc_fact.descuentoFactura) AS subtotal_descuento_moneda,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact.baseImponible / ped_vent.monto_tasa_cambio), cxc_fact.baseImponible) AS base_imponible_moneda,
	cxc_fact.porcentajeIvaFactura AS porcentaje_iva_moneda,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact.calculoIvaFactura / ped_vent.monto_tasa_cambio), cxc_fact.calculoIvaFactura) AS subtotal_iva_moneda,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact.base_imponible_iva_lujo / ped_vent.monto_tasa_cambio), cxc_fact.base_imponible_iva_lujo) AS base_imponible_iva_lujo_moneda,
	cxc_fact.porcentajeIvaDeLujoFactura AS porcentaje_iva_lujo_moneda,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact.calculoIvaDeLujoFactura / ped_vent.monto_tasa_cambio), cxc_fact.calculoIvaDeLujoFactura) AS subtotal_iva_lujo_moneda,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact.montoExento / ped_vent.monto_tasa_cambio), cxc_fact.montoExento) AS monto_exento_moneda,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact.montoExonerado / ped_vent.monto_tasa_cambio), cxc_fact.montoExonerado) AS monto_exonerado_moneda,
	
	cxc_fact.anulada,
	cxc_fact.id_credito_tradein,
	IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
	A.fecha_reconversion as reconversion
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	INNER JOIN pg_empleado empleado ON (cxc_fact.idVendedor = empleado.id_empleado)
	LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
		LEFT JOIN  cj_cc_factura_reconversion A on (A.id_factura = cxc_fact.idFactura)
		LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
        LEFT JOIN crm_perfil_prospecto prospecto ON (cliente.id = prospecto.id)
        LEFT JOIN bancos banco ON ped_vent.id_banco_financiar = banco.idBanco
WHERE cxc_fact.idFactura = %s
	AND cxc_fact.idDepartamentoOrigenFactura IN (2);",
	valTpDato($idDocumento, "int"));
$rsEncabezado = mysql_query($queryEncabezado);
if (!$rsEncabezado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEncabezado = mysql_fetch_array($rsEncabezado);

$idEmpresa = $rowEncabezado['id_empresa'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Datos del Sistema GNV en la Impresion de la Factura de Venta y Nota de Crédito)
$queryConfigDatosGNV = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 202 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfigDatosGNV = mysql_query($queryConfigDatosGNV, $conex);
if (!$rsConfigDatosGNV) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowConfigDatosGNV = mysql_fetch_assoc($rsConfigDatosGNV);

// VERIFICA VALORES DE CONFIGURACION (Incluir Saldo PND en Precio de Venta de la Unidad (Copia Banco))
$queryConfig208 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 208 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig208 = mysql_query($queryConfig208);
if (!$rsConfig208) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig208 = mysql_num_rows($rsConfig208);
$rowConfig208 = mysql_fetch_assoc($rsConfig208);

// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig403 = mysql_query($queryConfig403);
if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig403 = mysql_num_rows($rsConfig403);
$rowConfig403 = mysql_fetch_assoc($rsConfig403);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Dcto. Identificación (C.I. / R.I.F. / R.U.C. / LIC / SSN) en Documentos Fiscales.)
$queryConfig409 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 409 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig409 = mysql_query($queryConfig409);
if (!$rsConfig409) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig409 = mysql_num_rows($rsConfig409);
$rowConfig409 = mysql_fetch_assoc($rsConfig409);

if (in_array($rowConfig403['valor'],array(1,2))) {
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
	imagestring($img,1,300,$posY,str_pad("FACTURA SERIE - V", 34, " ", STR_PAD_BOTH),$textColor);
	
	$posY += 9;
	$posY += 9;
	imagestring($img,1,300,$posY,utf8_decode("FACTURA NRO."),$textColor);
	imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numeroFactura'],$textColor);
	
	$posY += 9;
	imagestring($img,1,300,$posY,
		str_pad(utf8_decode("FECHA EMISIÓN"), 15, " ", STR_PAD_RIGHT).": ".
		str_pad(strtoupper(utf8_decode(date(spanDateFormat, strtotime($rowEncabezado['fechaRegistroFactura'])))), 17, " ", STR_PAD_RIGHT),$textColor);
	
	$posY += 9;
	imagestring($img,1,300,$posY,
		str_pad(utf8_decode("FECHA VENC."), 15, " ", STR_PAD_RIGHT).": ".
		str_pad(strtoupper(utf8_decode(date(spanDateFormat, strtotime($rowEncabezado['fechaVencimientoFactura'])))), 17, " ", STR_PAD_RIGHT),$textColor);
	
	$posY += 18;
	imagestring($img,1,300,$posY,
		str_pad(utf8_decode("PEDIDO NRO."), 15, " ", STR_PAD_RIGHT).": ".
		str_pad(strtoupper(utf8_decode($rowEncabezado['numeracion_pedido'])), 17, " ", STR_PAD_RIGHT),$textColor);
	
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
		cxc_fact_det_vehic.precio_unitario,
		
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda,
			(SELECT ped_vent.precio_venta FROM an_pedido ped_vent
			WHERE ped_vent.id_pedido = cxc_fact.numeroPedido
				AND ped_vent.id_unidad_fisica = cxc_fact_det_vehic.id_unidad_fisica), cxc_fact_det_vehic.precio_unitario) AS precio_unitario_moneda,
  
		uni_fis.codigo_unico_conversion,
		uni_fis.marca_kit,
		uni_fis.marca_cilindro,
		uni_fis.modelo_regulador,
		uni_fis.serial1,
		uni_fis.serial_regulador,
		uni_fis.capacidad_cilindro,
		uni_fis.fecha_elaboracion_cilindro,
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
	WHERE cxc_fact_det_vehic.id_factura = %s;",
		valTpDato($idDocumento, "int"));
	$rsUnidad = mysql_query($queryUnidad);
	if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsUnidad = mysql_num_rows($rsUnidad);
	$rowUnidad = mysql_fetch_array($rsUnidad);
	
	// DETALLES DE LOS REPUESTOS
	$queryDetalle = sprintf("SELECT
		art.id_articulo,
		art.id_modo_compra,
		art.codigo_articulo,
		tipo_art.descripcion AS descripcion_tipo,
		art.descripcion AS descripcion_articulo,
		subseccion.id_subseccion,
		seccion.descripcion AS descripcion_seccion,
		cxc_fact_det.cantidad,
		cxc_fact_det.precio_unitario,
		cxc_fact_det.precio_sugerido,
		cxc_fact_det.id_iva,
		cxc_fact_det.iva,
		cxc_fact_det.id_factura_detalle,
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_factura_detalle cxc_fact_det ON (cxc_fact.idFactura = cxc_fact_det.id_factura)
			INNER JOIN iv_articulos art ON (cxc_fact_det.id_articulo = art.id_articulo)
				INNER JOIN iv_subsecciones subseccion ON (art.id_subseccion = subseccion.id_subseccion)
				INNER JOIN iv_tipos_articulos tipo_art ON (art.id_tipo_articulo = tipo_art.id_tipo_articulo)
				INNER JOIN iv_secciones seccion ON (subseccion.id_seccion = seccion.id_seccion)
		LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
	WHERE cxc_fact_det.id_factura = %s",
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
		cxc_fact_det_acc.id_factura_detalle_accesorios,
		cxc_fact_det_acc.id_accesorio,
		CONCAT(acc.nom_accesorio, IF(cxc_fact_det_acc.id_iva = 0, ' (E)', '')) AS nom_accesorio,
        cxc_fact_det_acc.id_tipo_accesorio,
		cxc_fact_det_acc.precio_unitario,
		
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda,
			(SELECT acc_ped.precio_accesorio
			FROM an_pedido ped_vent
				INNER JOIN an_accesorio_pedido acc_ped ON (ped_vent.id_pedido = acc_ped.id_pedido)
			WHERE acc_ped.id_pedido = cxc_fact.numeroPedido
				AND acc_ped.id_accesorio = cxc_fact_det_acc.id_accesorio), cxc_fact_det_acc.precio_unitario) AS precio_unitario_moneda,
		
		cxc_fact_det_acc.costo_compra,
		cxc_fact_det_acc.tipo_accesorio,
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_factura_detalle_accesorios cxc_fact_det_acc ON (cxc_fact.idFactura = cxc_fact_det_acc.id_factura)
			INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
		LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
	WHERE cxc_fact_det_acc.id_factura = %s;",
		valTpDato($idDocumento, "int"));
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsDetalle = mysql_num_rows($rsDetalle);
	while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
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
	
	if ($totalRowsUnidad > 0) {
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
			strtoupper(str_pad(substr((""),0,10), 8, " ", STR_PAD_RIGHT))." ".
			str_pad($rowUnidad['abreviacion_moneda'], 4, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowUnidad['precio_unitario_moneda']), 14, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
	}
	
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
	
	if ($rowEncabezado['id_moneda'] <> $rowEncabezado['id_moneda_tasa_cambio'] && in_array(idArrayPais,array(1))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$posY += 9;
		imagestring($img,1,0,$posY,strtoupper(sprintf("Tasa de Cambio utilizado %s %s por cada %s = %s",
			$rowEncabezado['abreviacion_moneda_local'],
			number_format($rowEncabezado['monto_tasa_cambio'], 3, ".", ","),
			$rowEncabezado['abreviacion_moneda_extranjera'],
			$rowEncabezado['descripcion_moneda_extranjera'])),$textColor);
		
		$posY += 9;
		$posY += 9;
		imagestring($img,1,20,$posY,
			str_pad("SUBTOTAL", 16, " ", STR_PAD_RIGHT).":".
			str_pad("", 8, " ", STR_PAD_LEFT).
			str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowEncabezado['subtotal_factura']), 15, " ", STR_PAD_LEFT),$textColor);
		
		if ($rowEncabezado['subtotal_descuento'] > 0) {
			$posY += 9;
			imagestring($img,1,20,$posY,
				str_pad("DESCUENTO", 16, " ", STR_PAD_RIGHT).":".
				str_pad("", 8, " ", STR_PAD_LEFT).
				str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
				str_pad(formatoNumero($rowEncabezado['subtotal_descuento']), 15, " ", STR_PAD_LEFT),$textColor);
		}
				
		$queryIvaFact = sprintf("SELECT
			iva.observacion,
			
			cxc_fact_iva.base_imponible,
			cxc_fact_iva.iva,
			cxc_fact_iva.subtotal_iva,
			
			IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact_iva.base_imponible / ped_vent.monto_tasa_cambio), cxc_fact_iva.base_imponible) AS base_imponible_moneda,
			cxc_fact_iva.iva AS iva_moneda,
			IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact_iva.subtotal_iva / ped_vent.monto_tasa_cambio), cxc_fact_iva.subtotal_iva) AS subtotal_iva_moneda,
			
			IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_factura_iva cxc_fact_iva ON (cxc_fact.idFactura = cxc_fact_iva.id_factura)
			INNER JOIN pg_iva iva ON (cxc_fact_iva.id_iva = iva.idIva)
			LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		WHERE id_factura = %s;",
			valTpDato($idDocumento, "int"));
		$rsIvaFact = mysql_query($queryIvaFact);
		if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
			$posY += 9;
			imagestring($img,1,20,$posY,
				str_pad("BASE IMPONIBLE", 16, " ", STR_PAD_RIGHT).":".
				str_pad("", 8, " ", STR_PAD_LEFT).
				str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
				str_pad(formatoNumero($rowIvaFact['base_imponible']), 15, " ", STR_PAD_LEFT),$textColor);
			
			$posY += 9;
			imagestring($img,1,20,$posY,
				str_pad(substr(strtoupper(utf8_decode($rowIvaFact['observacion'])),0,16), 16, " ", STR_PAD_RIGHT).":".
				str_pad(formatoNumero($rowIvaFact['iva'])."%", 8, " ", STR_PAD_LEFT).
				str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
				str_pad(formatoNumero($rowIvaFact['subtotal_iva']), 15, " ", STR_PAD_LEFT),$textColor);
			
			$totalIva += $rowIvaFact['subtotal_iva'];
		}
		
		$posY += 9;
		imagestring($img,1,20,$posY,
			str_pad("MONTO EXENTO", 16, " ", STR_PAD_RIGHT).":".
			str_pad("", 8, " ", STR_PAD_LEFT).
			str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowEncabezado['monto_exento']), 15, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
		imagestring($img,1,20,$posY,
			str_pad("MONTO EXONERADO", 16, " ", STR_PAD_RIGHT).":".
			str_pad("", 8, " ", STR_PAD_LEFT).
			str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowEncabezado['monto_exonerado']), 15, " ", STR_PAD_LEFT),$textColor);
		
		$fechaRegistroFactura = date_create_from_format('Y-m-d',$rowEncabezado['fechaRegistroFactura']);
		$fechaReconversion = date_create_from_format('Y-m-d','2018-08-01');
		$fechaAjusten = date_create_from_format('Y-m-d','2018-08-20');

		$posY += 8;
		imagestring($img,1,20,$posY,str_pad("", 46, "-", STR_PAD_LEFT),$textColor);
		
		if ($rowEncabezado['reconversion'] == null) {
				if ($fechaRegistroFactura>=$fechaReconversion and $fechaRegistroFactura<$fechaAjusten) {
					
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
					imagestring($img,1,20,$posY,
						str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 15, " ", STR_PAD_LEFT),$textColor);
					$posY += 8;
					imagestring($img,1,20,$posY,str_pad("", 46, "-", STR_PAD_LEFT),$textColor);
					$posY += 8;
					imagestring($img,1,20,$posY,
						str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad('Bs.S', 7, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura/100000), 14, " ", STR_PAD_LEFT),$textColor);
				}else if ($fechaRegistroFactura>=$fechaAjusten) {
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
					imagestring($img,1,20,$posY,
						str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad('Bs.S', 7, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 14, " ", STR_PAD_LEFT),$textColor);
					$posY += 8;
					imagestring($img,1,20,$posY,str_pad("", 46, "-", STR_PAD_LEFT),$textColor);
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
					imagestring($img,1,20,$posY,
						str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura*100000), 15, " ", STR_PAD_LEFT),$textColor);

				}else{
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
					imagestring($img,1,20,$posY,
						str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 15, " ", STR_PAD_LEFT),$textColor);
				}
			}else{

				if ($fechaRegistroFactura>=$fechaReconversion and $fechaRegistroFactura<$fechaAjusten) {
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
					imagestring($img,1,20,$posY,
						str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura*100000), 15, " ", STR_PAD_LEFT),$textColor);
					$posY += 8;
					imagestring($img,1,20,$posY,str_pad("", 46, "-", STR_PAD_LEFT),$textColor);
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
					imagestring($img,1,20,$posY,
						str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad('Bs.S', 7, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 14, " ", STR_PAD_LEFT),$textColor);
				}else if ($fechaRegistroFactura>=$fechaAjusten) {
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
					imagestring($img,1,20,$posY,
						str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad('Bs.S', 7, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 14, " ", STR_PAD_LEFT),$textColor);
					$posY += 8;
					imagestring($img,1,20,$posY,str_pad("", 46, "-", STR_PAD_LEFT),$textColor);
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
					imagestring($img,1,20,$posY,
						str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura*100000), 15, " ", STR_PAD_LEFT),$textColor);

				}else{
					$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
					imagestring($img,1,20,$posY,
						str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":".
						str_pad("", 8, " ", STR_PAD_LEFT).
						str_pad($rowEncabezado['abreviacion_moneda_local'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura*100000), 15, " ", STR_PAD_LEFT),$textColor);
				}	
			}

		


	}
	
	$posY = 410;
	
	if ($rowEncabezado['id_moneda'] <> $rowEncabezado['id_moneda_tasa_cambio'] && in_array(idArrayPais,array(1))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$textoColetilla = sprintf("El precio de venta es en %s y solo se expresa en %s como una referencia a la tasa de cambio para dar cumplimiento a los Artículos 25 de la ley del IVA y 38 de su reglamento.\nTasa de Cambio %s/%s %s, determinado de conformidad con el convenio cambiario Nro. 33 del 10-02-2015.\nTipo de Cambio %s/%s de fecha %s en concordancia con el Art. 17 del convenio Nro. 35 del 09-03-2016.",
			$rowEncabezado['descripcion_moneda_extranjera'],
			$rowEncabezado['descripcion_moneda_local'],
			$rowEncabezado['abreviacion_moneda_local'],
			$rowEncabezado['abreviacion_moneda_extranjera'],
			number_format($rowEncabezado['monto_tasa_cambio'], 3, ".", ","),
			$rowEncabezado['abreviacion_moneda_local'],
			$rowEncabezado['abreviacion_moneda_extranjera'],
			date(spanDateFormat, strtotime($rowEncabezado['fecha_tasa_cambio'])));
		
		$arrayColetilla = wordwrap(str_replace("\n","<br>",$textoColetilla), 94, "<br>");
		$arrayValor = explode("<br>",$arrayColetilla);
		if (isset($arrayValor)) {
			foreach ($arrayValor as $indice => $valor) {
				$posY += 8;
				imagestring($img,1,0,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
			}
		}
	}
		
	$posY = 460;
	
	$arrayObservacionDcto = wordwrap(str_replace("\n","<br>",str_replace(";", "", $rowEncabezado['observacionFactura'])), 47, "<br>");
	$arrayValor = explode("<br>",$arrayObservacionDcto);
	if (isset($arrayValor)) {
		foreach ($arrayValor as $indice => $valor) {
			$posY += 8;
			imagestring($img,1,0,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
		}
	}
	
	$posY = 460;
	
	$posY += 9;
	imagestring($img,1,240,$posY,
		str_pad("SUBTOTAL", 16, " ", STR_PAD_RIGHT).":".
		str_pad("", 8, " ", STR_PAD_LEFT).
		str_pad($rowEncabezado['abreviacion_moneda'], 5, " ", STR_PAD_LEFT).
		str_pad(formatoNumero($rowEncabezado['subtotal_factura_moneda']), 15, " ", STR_PAD_LEFT),$textColor);
	
	if ($rowEncabezado['subtotal_descuento_moneda'] > 0) {
		$posY += 9;
		imagestring($img,1,240,$posY,
			str_pad("DESCUENTO", 16, " ", STR_PAD_RIGHT).":".
			str_pad("", 8, " ", STR_PAD_LEFT).
			str_pad($rowEncabezado['abreviacion_moneda'], 5, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowEncabezado['subtotal_descuento_moneda']), 15, " ", STR_PAD_LEFT),$textColor);
	}
			
	$queryIvaFact = sprintf("SELECT
		iva.observacion,
		
		cxc_fact_iva.base_imponible,
		cxc_fact_iva.iva,
		cxc_fact_iva.subtotal_iva,
		
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact_iva.base_imponible / ped_vent.monto_tasa_cambio), cxc_fact_iva.base_imponible) AS base_imponible_moneda,
		cxc_fact_iva.iva AS iva_moneda,
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, (cxc_fact_iva.subtotal_iva / ped_vent.monto_tasa_cambio), cxc_fact_iva.subtotal_iva) AS subtotal_iva_moneda,
		
		IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_factura_iva cxc_fact_iva ON (cxc_fact.idFactura = cxc_fact_iva.id_factura)
		INNER JOIN pg_iva iva ON (cxc_fact_iva.id_iva = iva.idIva)
		LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
		LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
	WHERE id_factura = %s;",
		valTpDato($idDocumento, "int"));
	$rsIvaFact = mysql_query($queryIvaFact);
	if (!$rsIvaFact) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	while ($rowIvaFact = mysql_fetch_assoc($rsIvaFact)) {
		$posY += 9;
		imagestring($img,1,240,$posY,
			str_pad("BASE IMPONIBLE", 16, " ", STR_PAD_RIGHT).":".
			str_pad("", 8, " ", STR_PAD_LEFT).
			str_pad($rowIvaFact['abreviacion_moneda'], 5, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowIvaFact['base_imponible_moneda']), 15, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 9;
		imagestring($img,1,240,$posY,
			str_pad(substr(strtoupper(utf8_decode($rowIvaFact['observacion'])),0,16), 16, " ", STR_PAD_RIGHT).":".
			str_pad(formatoNumero($rowIvaFact['iva_moneda'])."%", 8, " ", STR_PAD_LEFT).
			str_pad($rowIvaFact['abreviacion_moneda'], 5, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowIvaFact['subtotal_iva_moneda']), 15, " ", STR_PAD_LEFT),$textColor);
		
		$totalIva += $rowIvaFact['subtotal_iva_moneda'];
	}
	
	if ($rowEncabezado['monto_exento_moneda'] != 0) {
		$posY += 9;
		imagestring($img,1,240,$posY,
			str_pad("MONTO EXENTO", 16, " ", STR_PAD_RIGHT).":".
			str_pad("", 8, " ", STR_PAD_LEFT).
			str_pad($rowEncabezado['abreviacion_moneda'], 5, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowEncabezado['monto_exento_moneda']), 15, " ", STR_PAD_LEFT),$textColor);
	}
	
	if ($rowEncabezado['monto_exonerado_moneda'] != 0) {
		$posY += 9;
		imagestring($img,1,240,$posY,
			str_pad("MONTO EXONERADO", 16, " ", STR_PAD_RIGHT).":".
			str_pad("", 8, " ", STR_PAD_LEFT).
			str_pad($rowEncabezado['abreviacion_moneda'], 5, " ", STR_PAD_LEFT).
			str_pad(formatoNumero($rowEncabezado['monto_exonerado_moneda']), 15, " ", STR_PAD_LEFT),$textColor);
	}
	
	$posY += 8;
	imagestring($img,1,240,$posY,str_pad("", 46, "-", STR_PAD_LEFT),$textColor);
	
//$rowEncabezado['fechaRegistroFactura']
			$fechaRegistroFactura = date_create_from_format('Y-m-d',$rowEncabezado['fechaRegistroFactura']);
			$fechaReconversion = date_create_from_format('Y-m-d','2018-08-01');
			$fechaAjusten = date_create_from_format('Y-m-d','2018-08-20');
if ($rowEncabezado['id_moneda'] <> $rowEncabezado['id_moneda_tasa_cambio'] && in_array(idArrayPais,array(1))) { 

	$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 17, " ", STR_PAD_LEFT),$textColor);

}else{
	if ($rowEncabezado['reconversion'] == null) {
				if ($fechaRegistroFactura>=$fechaReconversion and $fechaRegistroFactura<$fechaAjusten) {
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 17, " ", STR_PAD_LEFT),$textColor);
					$posY += 10;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad("Bs.S", 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura/100000), 17, " ", STR_PAD_LEFT),$textColor);
				}else if ($fechaRegistroFactura>=$fechaAjusten) {
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad("Bs.S", 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 17, " ", STR_PAD_LEFT),$textColor);
					$posY += 10;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura*100000), 17, " ", STR_PAD_LEFT),$textColor);

				}else{
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 17, " ", STR_PAD_LEFT),$textColor);
				}
			}else{

				if ($fechaRegistroFactura>=$fechaReconversion and $fechaRegistroFactura<$fechaAjusten) {
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura*100000), 17, " ", STR_PAD_LEFT),$textColor);
					$posY += 10;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad("Bs.S", 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 17, " ", STR_PAD_LEFT),$textColor);
				}else if ($fechaRegistroFactura>=$fechaAjusten) {
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad("Bs.S", 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura), 17, " ", STR_PAD_LEFT),$textColor);
					$posY += 10;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad($rowEncabezado['abreviacion_moneda'], 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura*100000), 17, " ", STR_PAD_LEFT),$textColor);

				}else{
					$posY += 8;
					$totalFactura = $rowEncabezado['subtotal_factura_moneda'] - $rowEncabezado['subtotal_descuento_moneda'] + $rowEncabezado['subtotal_iva_moneda'] + $rowEncabezado['subtotal_iva_lujo_moneda'];
					imagestring($img,1,240,$posY,str_pad("TOTAL FACTURA", 16, " ", STR_PAD_RIGHT).":",$textColor);
					imagestring($img,2,333,$posY,
						str_pad("Bs.S", 6, " ", STR_PAD_LEFT).
						str_pad(formatoNumero($totalFactura*100000), 17, " ", STR_PAD_LEFT),$textColor);
				}	
			}
}
			


	$arrayImg[] = "tmp/"."factura_vehiculo".$pageNum.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
} else if (in_array($rowConfig403['valor'],array(3))) {
	for ($pageNum = 0; $pageNum < 2; $pageNum++) {
		$img = @imagecreate(470, 628) or die("No se puede crear la imagen");
		
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
		
		//////////////////////////////////////////////////////////// COPIA BANCO ////////////////////////////////////////////////////////////
		
		if ($rowEncabezado['anulada'] == "SI") {
			// MARCA DE AGUA
			$src = imagecreatefrompng("../../img/dcto_anulado.png");
			//imagen crear, imagen copiar, x,y destino. x,y a copiar, width height a copiar, 100 transparencia
			if(!imagecopyresampled($img, $src, 0, 40, 0, 0, 470, 500, 470, 500)){ die ("Error marca de agua"); }
		}
		
		if ($copiaBanco == true) {
		} else {
			if ($rowEncabezado['anulada'] == "SI") {
				// MARCA DE AGUA
				$src = imagecreatefrompng("../img/copia_cliente_anulado.png");
				//imagen crear, imagen copiar, x,y destino. x,y a copiar, width height a copiar, 100 transparencia
				if(!imagecopyresampled($img, $src, 0, 40, 0, 0, 470, 500, 470, 500)){ die ("Error marca de agua"); }
			} else {			
				// MARCA DE AGUA
				$src = imagecreatefrompng("../img/copia_cliente.png");
				//imagen crear, imagen copiar, x,y destino. x,y a copiar, width height a copiar, 100 transparencia
				if(!imagecopyresampled($img, $src, 0, 40, 0, 0, 470, 500, 470, 500)){ die ("Error marca de agua"); }
			}
		}
		
		//ENCABEZADO
		$posY = 0;
		imagestring($img,1,70,$posY,$rowEmp["nombre_empresa"],$textColor);
		
		$arrayDireccion = wordwrap(str_replace("\n","<br>",str_replace(";", "", ((isset($verObservacion)) ? "" : $rowEmp['direccion']))), 50, "<br>");
		$arrayValor = explode("<br>",$arrayDireccion);
		if (isset($arrayValor)) {
			foreach ($arrayValor as $indice => $valor) {
				$posY += 8;
				imagestring($img,1,70,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
			}
		}
		
		if ($rowEmp["fax"] != ""){
			$fax = " FAX ".$rowEmp["fax"];
		}
		$posY += 10;
		imagestring($img,1,70,$posY,"Tel.: ".$rowEmp["telefono1"]." ".$rowEmp["telefono2"].$fax,$textColor);
		$posY += 10;  	 
		
		
		$posY = 10;
		imagestring($img,1,310,$posY,str_pad("FACTURA SERIE - V", 32, " ", STR_PAD_BOTH),$textColor);
		
		$posY += 10;
		imagestring($img,1,310,$posY,strtoupper(str_pad(utf8_decode("FACTURA NRO."), 13, " ", STR_PAD_RIGHT)."  "),$textColor);
		imagestring($img,2,380,$posY-3,strtoupper(str_pad(utf8_decode(substr($rowEncabezado['numeroFactura'],0,15)), 15, " ", STR_PAD_BOTH)),$textColor);
			imageline($img, 380, $posY+8, 468, $posY+8, $textColor); // linea H -
		
		$posY += 10;
		if ($copiaBanco == true) {
			imagestring($img,1,310,$posY,strtoupper(str_pad(utf8_decode("FECHA ENTREGA"), 13, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr(date(spanDateFormat, strtotime($rowEncabezado['fecha_entrega'])),0,16)), 16, " ", STR_PAD_BOTH)),$textColor);
				imageline($img, 380, $posY+8, 468, $posY+8, $textColor); // linea H -
		} else {
			imagestring($img,1,310,$posY,strtoupper(str_pad(utf8_decode("FECHA EMISIÓN"), 13, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr(date(spanDateFormat, strtotime($rowEncabezado['fechaRegistroFactura'])),0,16)), 16, " ", STR_PAD_BOTH)),$textColor);
				imageline($img, 380, $posY+8, 468, $posY+8, $textColor); // linea H -
		}
		
		$posY += 10;
		imagestring($img,1,310,$posY,strtoupper(str_pad(utf8_decode("PEDIDO NRO."), 13, " ", STR_PAD_RIGHT)."  ".
			str_pad(utf8_decode(substr($rowEncabezado['numeracion_pedido'],0,16)), 16, " ", STR_PAD_BOTH)),$textColor);
			imageline($img, 380, $posY+8, 468, $posY+8, $textColor); // linea H -
		
		$posY += 10;
		imagestring($img,1,310,$posY,strtoupper(str_pad(utf8_decode("VENDEDOR"), 13, " ", STR_PAD_RIGHT)."  ".
			str_pad(utf8_decode(substr($rowEncabezado['nombre_empleado'],0,16)), 16, " ", STR_PAD_BOTH)),$textColor);
		
		
		$posYVenta = $posY; // es usado en los accesorios del recuadro de la derecha
		
		imageline($img, 0, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		
		$posY += 11; imageline($img, 0, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode("CLIENTE"), 9, " ", STR_PAD_RIGHT)."  ".
			str_pad(utf8_decode(substr($rowEncabezado["nombre_cliente"],0,32)), 32, " ", STR_PAD_RIGHT)),$textColor);
		imagestring($img,1,230,$posY,strtoupper(str_pad(utf8_decode("CÓDIGO"), 6, " ", STR_PAD_RIGHT)."  ".
			str_pad(utf8_decode(substr($rowEncabezado["id"],0,8)), 8, " ", STR_PAD_RIGHT)),$textColor);
			
			
		if (in_array($rowConfig409['valor'],array("","1"))) {
			imagestring($img,1,320,$posY,utf8_decode($spanClienteCxC)."  ".strtoupper($rowEncabezado['ci_cliente']),$textColor);
		}
		
		$arrayDireccionCliente = wordwrap(str_pad(utf8_decode("DIRECCIÓN"), 9, " ", STR_PAD_RIGHT)."  ".str_replace("\n","<br>",str_replace(";", "", $rowEncabezado['direccion_cliente'])), 84, "<br>");
		$arrayValor = explode("<br>",$arrayDireccionCliente);
		if (isset($arrayValor)) {
			foreach ($arrayValor as $indice => $valor) {
				$posY += 11; imageline($img, 0, ($posY+9), 468, ($posY+9), $textColor); // linea H -
				imagestring($img,1,5,$posY,strtoupper(utf8_decode(trim($valor))),$textColor);
			}
		}
		
		if ($rowEncabezado['fecha_nacimiento'] != NULL && $rowEncabezado['fecha_nacimiento'] != "1969-12-31" && $rowEncabezado['fecha_nacimiento'] != "0000-00-00"){
		   $fechaNacimiento = date(spanDateFormat, strtotime($rowEncabezado['fecha_nacimiento']));
		}
		
		($rowEncabezado['telf'] != "") ? $arrayTelefono[] = $rowEncabezado['telf'] : "";
		($rowEncabezado['otrotelf'] != "") ? $arrayTelefono[] = $rowEncabezado['otrotelf'] : "";
		$posY += 11; imageline($img, 0, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode("TELF."), 9, " ", STR_PAD_RIGHT)."  ".
			str_pad(substr(strtoupper(utf8_decode(implode("/",$arrayTelefono))),0,20), 20, " ", STR_PAD_RIGHT)),$textColor);
		imagestring($img,1,170,$posY,strtoupper(str_pad(utf8_decode($spanEmail), 6, " ", STR_PAD_RIGHT)."  ".
			str_pad(utf8_decode(substr($rowEncabezado["correo"],0,24)), 24, " ", STR_PAD_RIGHT)),$textColor);
		imagestring($img,1,340,$posY,strtoupper(str_pad(utf8_decode("FECHA NAC."), 11, " ", STR_PAD_RIGHT)."  ".
			str_pad(utf8_decode(substr($fechaNacimiento,0,12)), 12, " ", STR_PAD_RIGHT)),$textColor);
		
		// BUSCA LOS DATOS DE LA UNIDAD
		$queryUnidad = sprintf("
		SELECT 
			vw_iv_modelo.nom_uni_bas,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.nom_modelo,
			vw_iv_modelo.nom_version,
			vw_iv_modelo.nom_ano,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_ext.des_color AS descripcion_color_externo1,
			uni_fis.kilometraje,
			vw_iv_modelo.com_uni_bas,
			cxc_fact_det_vehic.precio_unitario,
			
			IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda,
				(SELECT ped_vent.precio_venta FROM an_pedido ped_vent
				WHERE ped_vent.id_pedido = cxc_fact.numeroPedido
					AND ped_vent.id_unidad_fisica = cxc_fact_det_vehic.id_unidad_fisica), cxc_fact_det_vehic.precio_unitario) AS precio_unitario_moneda,
	  
			uni_fis.codigo_unico_conversion,
			uni_fis.marca_kit,
			uni_fis.marca_cilindro,
			uni_fis.modelo_regulador,
			uni_fis.serial1,
			uni_fis.serial_regulador,
			uni_fis.capacidad_cilindro,
			uni_fis.fecha_elaboracion_cilindro,
			IF(moneda_extranjera.idmoneda <> moneda_local.idmoneda, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
			INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			LEFT JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
			LEFT JOIN pg_monedas moneda_local ON (ped_vent.id_moneda = moneda_local.idmoneda)
			LEFT JOIN pg_monedas moneda_extranjera ON (ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		WHERE cxc_fact_det_vehic.id_factura = %s;",
			valTpDato($idDocumento, "int"));
		$rsUnidad = mysql_query($queryUnidad);
		if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsUnidad = mysql_num_rows($rsUnidad);
		$rowUnidad = mysql_fetch_array($rsUnidad);
		
		if ($totalRowsUnidad > 0) {
			$posY += 11; imageline($img, 0, ($posY+9), 468, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode(substr($rowUnidad["nom_uni_bas"],0,30)), 30, " ", STR_PAD_RIGHT)),$textColor);
			
			if ($copiaBanco == true) {
			} else {
				imagestring($img,1,340,$posY,strtoupper(str_pad(utf8_decode("FECHA ENTREGA"), 11, " ", STR_PAD_RIGHT)."  ".
					str_pad(utf8_decode(substr(date(spanDateFormat, strtotime($rowEncabezado['fecha_entrega'])),0,12)), 12, " ", STR_PAD_RIGHT)),$textColor);
			}
			
			$posY += 11; imageline($img, 0, ($posY+9), 468, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode("MARCA"), 9, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr($rowUnidad["nom_marca"],0,24)), 24, " ", STR_PAD_RIGHT)),$textColor);
			
			imagestring($img,1,190,$posY,strtoupper(str_pad(utf8_decode("MODELO"), 9, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr($rowUnidad["nom_modelo"],0,18)), 18, " ", STR_PAD_RIGHT)),$textColor);
			
			imagestring($img,1,340,$posY,strtoupper(str_pad(utf8_decode("VERSIÓN"), 9, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr($rowUnidad["nom_version"],0,14)), 14, " ", STR_PAD_RIGHT)),$textColor);
			
			
			$posY += 11; imageline($img, 0, ($posY+9), 468, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode("AÑO"), 9, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr($rowUnidad["nom_ano"],0,24)), 24, " ", STR_PAD_RIGHT)),$textColor);
			
			imagestring($img,1,190,$posY,strtoupper(str_pad(utf8_decode("COLOR"), 9, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr($rowUnidad["descripcion_color_externo1"],0,18)), 18, " ", STR_PAD_RIGHT)),$textColor);
			
			
			$posY += 11; imageline($img, 0, ($posY+9), 468, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode($spanSerialCarroceria), 9, " ", STR_PAD_RIGHT)."  "),$textColor);
			imagestring($img,2,60,$posY-3,strtoupper(str_pad(utf8_decode(substr($rowUnidad["serial_carroceria"],0,20)), 20, " ", STR_PAD_RIGHT)),$textColor);
			
			imagestring($img,1,190,$posY,strtoupper(str_pad(utf8_decode($spanSerialMotor), 9, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr($rowUnidad["serial_motor"],0,18)), 18, " ", STR_PAD_RIGHT)),$textColor);
			
			imagestring($img,1,340,$posY,strtoupper(str_pad(utf8_decode($spanKilometraje), 9, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr($rowUnidad["kilometraje"],0,14)), 14, " ", STR_PAD_RIGHT)),$textColor);
			
			
			$posY += 11; imageline($img, 0, ($posY+9), 468, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode($spanPlaca), 9, " ", STR_PAD_RIGHT)."  "),$textColor);
			imagestring($img,2,60,$posY-3,strtoupper(str_pad(utf8_decode(substr($rowUnidad["placa"],0,20)), 20, " ", STR_PAD_RIGHT)),$textColor);
			
			/*imagestring($img,1,190,$posY,strtoupper(str_pad(utf8_decode("FORMA PAGO"), 9, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr(((strlen($rowEncabezado['nombreBanco']) > 0) ? "FINANCIADO" : "CONTADO"),0,18)), 18, " ", STR_PAD_RIGHT)),$textColor);*/
			
			imagestring($img,1,340,$posY,strtoupper(str_pad(utf8_decode("CONDICIÓN"), 9, " ", STR_PAD_RIGHT)."  ".
				str_pad(utf8_decode(substr($rowUnidad["condicion_unidad"],0,14)), 14, " ", STR_PAD_RIGHT)),$textColor);
		}
		
		imageline($img, 0, $posYVenta + 10, 0, ($posY+9), $textColor);//linea V |
		imageline($img, 468, $posYVenta + 10, 468, ($posY+9), $textColor);//linea V |
		
		// BUSCA LOS ADICIONALES INCLUIDOS EN LA FACTURA
		$queryDet = sprintf("SELECT
			cxc_fact_det_acc.id_tipo_accesorio,
			cxc_fact_det_acc.id_factura_detalle_accesorios,
			cxc_fact_det_acc.id_accesorio,
			cxc_fact_det_acc.costo_compra,
			cxc_fact_det_acc.precio_unitario,
			(CASE
				WHEN cxc_fact_det_acc.id_iva = 0 THEN
					CONCAT(acc.nom_accesorio, ' (E)')
				ELSE
					acc.nom_accesorio
			END) AS nom_accesorio,
			cxc_fact_det_acc.monto_pagado,
			cxc_fact_det_acc.id_condicion_pago AS id_condicion_pago_accesorio,
			cxc_fact_det_acc.id_condicion_mostrar AS id_condicion_mostrar_accesorio,
			cxc_fact_det_acc.monto_pendiente,
			cxc_fact_det_acc.id_condicion_mostrar_pendiente,
			cxc_fact_det_acc.tipo_accesorio
		FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
			INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
			INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
		WHERE cxc_fact_det_acc.id_factura = %s",
			valTpDato($idDocumento, "int"));
		$rsDet = mysql_query($queryDet);
		if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		
		$montoPrecioVenta = 0;
		$arrayContrato = array();
		$arrayAdicionales = array();
		$arrayAdicionalesPagados = array();
		$arrayAdicionalesPrecio = array();
		$arrayAdicionalesCosto = array();
		$totalContrato = 0;
		$totalAdicionales = 0;
		$totalAdicionalesPagados = 0;
		$totalAdicionalesPrecio = 0;
		$totalAdicionalesCosto = 0;
		while ($rowDet = mysql_fetch_array($rsDet)) {
			if ($rowDet['id_tipo_accesorio'] == 1) { // 1 = Adicionales
				if ($copiaBanco == true && $rowDet['id_condicion_pago_accesorio'] == 1) { // 1 = Pagado, 2 = Financiado
					$totalAdicionalesPagados += $rowDet['monto_pagado'];
					$arrayAdicionalesPagados[] = array(
						"nom_accesorio" => $rowDet['nom_accesorio'],
						"precio_unitario" => $rowDet['monto_pagado']);
						
					if ($rowDet['monto_pagado'] != $rowDet['precio_unitario']) {
						if ($rowDet['id_condicion_mostrar_pendiente'] == 1) { // Null = Individual, 1 = En Precio de Venta, 2 = En Costo de la Unidad
							$totalAdicionalesPrecio += $rowDet['monto_pendiente'];
							$arrayAdicionalesPrecio[] = array(
								"nom_accesorio" => $rowDet['nom_accesorio'],
								"precio_unitario" => $rowDet['monto_pendiente']);
						} else if ($rowDet['id_condicion_mostrar_pendiente'] == 2) { // Null = Individual, 1 = En Precio de Venta, 2 = En Costo de la Unidad
							$totalAdicionalesCosto += $rowDet['monto_pendiente'];
							$arrayAdicionalesCosto[] = array(
								"nom_accesorio" => $rowDet['nom_accesorio'],
								"precio_unitario" => $rowDet['monto_pendiente']);
						} else {
							$totalAdicionales += $rowDet['monto_pendiente'];
							$arrayAdicionales[] = array(
								"nom_accesorio" => $rowDet['nom_accesorio'],
								"precio_unitario" => $rowDet['monto_pendiente']);
						}
					}
				} else if ($copiaBanco == true && $rowDet['id_condicion_mostrar_accesorio'] == 1) { // Null = Individual, 1 = En Precio de Venta, 2 = En Costo de la Unidad
					$totalAdicionalesPrecio += $rowDet['precio_unitario'];
					$arrayAdicionalesPrecio[] = array(
						"nom_accesorio" => $rowDet['nom_accesorio'],
						"precio_unitario" => $rowDet['precio_unitario']);
				} else if ($copiaBanco == true && $rowDet['id_condicion_mostrar_accesorio'] == 2) { // Null = Individual, 1 = En Precio de Venta, 2 = En Costo de la Unidad
					$totalAdicionalesCosto += $rowDet['precio_unitario'];
					$arrayAdicionalesCosto[] = array(
						"nom_accesorio" => $rowDet['nom_accesorio'],
						"precio_unitario" => $rowDet['precio_unitario']);
				} else if ($copiaBanco != true
				|| ($copiaBanco == true && $rowDet['id_condicion_pago_accesorio'] == 2) // 1 = Pagado, 2 = Financiado
				|| ($copiaBanco == true && !in_array($rowDet['id_condicion_mostrar_accesorio'],array(1)))) { // Null = Individual, 1 = En Precio de Venta, 2 = En Costo de la Unidad
					$totalAdicionales += $rowDet['precio_unitario'];
					$arrayAdicionales[] = array(
						"nom_accesorio" => $rowDet['nom_accesorio'],
						"precio_unitario" => $rowDet['precio_unitario']);
				}
			} else if ($rowDet['id_tipo_accesorio'] == 3) { // 3 = Contratos
				$totalContrato += $rowDet['precio_unitario'];
				$arrayContrato[] = array(
					"nom_accesorio" => $rowDet['nom_accesorio'],
					"precio_unitario" => $rowDet['precio_unitario']);
				
			}
		}
		$montoPrecioVenta += $totalAdicionalesPrecio;
		
		// PAGOS DE CONTADO Y PAGOS BONO (7 = Anticipo, 8 = Nota de Crédito)
		$queryPagos = sprintf("SELECT 
			(SELECT
				SUM(cxc_pago.montoPagado)
			FROM an_pagos cxc_pago                   
			WHERE cxc_pago.id_factura = %s 
				AND (cxc_pago.formaPago NOT IN (7,8)
					OR (cxc_pago.formaPago IN (8)
							AND (((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
									WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento
										AND cxc_nc.idDocumento <> cxc_pago.id_factura) LIKE 'FA')
									OR ((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
										WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento) LIKE 'NC'))
							AND cxc_pago.numeroDocumento NOT IN (SELECT tradein_cxc.id_nota_credito_cxc FROM an_tradein_cxc tradein_cxc
																WHERE tradein_cxc.id_anticipo IS NOT NULL
																	AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)))
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
			) AS pagos_contado,
			
			(SELECT
				SUM(cxc_pago.montoPagado)
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON (cxc_pago.numeroDocumento = cxc_ant.idAnticipo)
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND (SELECT COUNT(cxc_pago2.idAnticipo) FROM cj_cc_detalleanticipo cxc_pago2
					WHERE cxc_pago2.idAnticipo = cxc_pago.numeroDocumento
						AND cxc_pago2.id_concepto IS NOT NULL) = 0
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
			) AS pagos_anticipo,
			
			(SELECT 
				SUM(DISTINCT cxc_pago.montoPagado)
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (2)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
			) AS pagos_tradein,
			
			(SELECT 
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (7,8)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
			) AS pagos_pnd,
			
			(SELECT
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (1,6)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
			) AS pagos_bono",
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"));
		$rsPagos = mysql_query($queryPagos);
		if (!$rsPagos) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagos); }    
		$rowPagos = mysql_fetch_assoc($rsPagos);
		
		// PAGOS DE NOTAS DE CREDITO CON ANTICIPO CERO O NEGATIVO
		$queryPagosOtro = sprintf("SELECT
			(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
			FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
				INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
			WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroDocumento) AS descripcion_motivo,
			cxc_pago.montoPagado
		FROM an_pagos cxc_pago                   
		WHERE cxc_pago.id_factura = %s 
			AND (cxc_pago.formaPago IN (8)
					AND (((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento
								AND cxc_nc.idDocumento <> cxc_pago.id_factura) LIKE 'FA')
							OR ((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
								WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento) LIKE 'NC'))
					AND cxc_pago.numeroDocumento IN (SELECT tradein_cxc.id_nota_credito_cxc FROM an_tradein_cxc tradein_cxc
													WHERE tradein_cxc.id_anticipo IS NOT NULL
														AND tradein_cxc.id_nota_credito_cxc IS NOT NULL
														AND tradein_cxc.id_anticipo IN (SELECT cxc_ant.idAnticipo FROM cj_cc_anticipo cxc_ant
																						WHERE cxc_ant.montoNetoAnticipo = 0)))
			AND cxc_pago.estatus IN (1,2)
			AND cxc_pago.id_condicion_mostrar = 1",
			valTpDato($idDocumento, "int"));
		/*$rsPagosOtro = mysql_query($queryPagosOtro);
		if (!$rsPagosOtro) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagosOtro); }    
		$totalRowsPagosOtro = mysql_num_rows($rsPagosOtro);
		$pagosNCTradeIn = 0;
		while ($rowPagosOtro = mysql_fetch_assoc($rsPagosOtro)) {
			$pagosNCTradeIn += $rowPagosOtro['montoPagado'];
		}*/
		
		// PAGOS DE CONTADO Y PAGOS BONO (7 = Anticipo, 8 = Nota de Crédito)
		// ESTO ES PARA LOS CASOS EN QUE SE DESEE QUE EL PAGO SEA SUMADO SOLO VISUALMENTE EN LOS MONTOS DEL TRADE IN
		$queryPagosMostrarTradeIn = sprintf("SELECT 
			(SELECT
				SUM(cxc_pago.montoPagado)
			FROM an_pagos cxc_pago                   
			WHERE cxc_pago.id_factura = %s 
				AND (cxc_pago.formaPago NOT IN (7,8)
					OR (cxc_pago.formaPago IN (8)
							AND (((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
									WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento
										AND cxc_nc.idDocumento <> cxc_pago.id_factura) LIKE 'FA')
									OR ((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
										WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento) LIKE 'NC'))
							AND cxc_pago.numeroDocumento NOT IN (SELECT tradein_cxc.id_nota_credito_cxc FROM an_tradein_cxc tradein_cxc
																WHERE tradein_cxc.id_anticipo IS NOT NULL
																	AND tradein_cxc.id_nota_credito_cxc IS NOT NULL)))
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 2
			) AS pagos_contado,
			
			(SELECT
				SUM(DISTINCT cxc_pago.montoPagado)
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND (SELECT COUNT(cxc_pago2.idAnticipo) FROM cj_cc_detalleanticipo cxc_pago2
					WHERE cxc_pago2.idAnticipo = cxc_pago.numeroDocumento
						AND cxc_pago2.id_concepto IS NOT NULL) = 0
				AND cxc_ant_det.id_concepto IS NULL
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 2
			) AS pagos_anticipo,
			
			(SELECT 
				SUM(DISTINCT cxc_pago.montoPagado)
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (2)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 2
			) AS pagos_tradein,
			
			(SELECT 
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (7,8)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 2
			) AS pagos_pnd,
			
			(SELECT
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (1,6)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 2
			) AS pagos_bono",
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"));
		$rsPagosMostrarTradeIn = mysql_query($queryPagosMostrarTradeIn);
		if (!$rsPagosMostrarTradeIn) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagos); }    
		$rowPagosMostrarTradeIn = mysql_fetch_assoc($rsPagosMostrarTradeIn);
		
		// PAGOS DE CONTADO Y PAGOS BONO (7 = Anticipo, 8 = Nota de Crédito)
		// ESTO ES PARA LOS CASOS EN QUE SE DESEE QUE EL PAGO SEA SUMADO SOLO VISUALMENTE COMO PAGO DE CONTADO
		$queryPagosMostrarContado = sprintf("SELECT 
			(SELECT 
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (7,8)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 1
			) AS pagos_pnd,
			
			(SELECT
				SUM(IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado))
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON cxc_pago.numeroDocumento = cxc_ant.idAnticipo
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON cxc_ant.idAnticipo = cxc_ant_det.idAnticipo                                
			WHERE cxc_pago.id_factura = %s 
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (1,6)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1
				AND cxc_pago.id_mostrar_contado = 1
			) AS pagos_bono",
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($idDocumento, "int"));
		$rsPagosMostrarContado = mysql_query($queryPagosMostrarContado);
		if (!$rsPagosMostrarContado) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagos); }    
		$rowPagosMostrarContado = mysql_fetch_assoc($rsPagosMostrarContado);
		
		
		$pagosContado = $rowPagos['pagos_contado'] + $rowPagos['pagos_anticipo'];
		$pagosContado -= ($copiaBanco == true) ? $totalAdicionalesPagados : 0;
		
		$pagosTradeIn = $rowPagos['pagos_tradein'];
		$pagosPND = $rowPagos['pagos_pnd'];
		$pagosBono = $rowPagos['pagos_bono'];
		
		// SUMA EN PAGO DE CONTADO AQUELLOS PAGOS SELECCIONADOS SOLO EN LA COPIA DEL BANCO
		$pagosContado += (($copiaBanco == true) ? $rowPagosMostrarContado['pagos_pnd'] : 0);
		$pagosContado += (($copiaBanco == true) ? $rowPagosMostrarContado['pagos_bono'] : 0);
		
		// RESTA EN PAGO DE PND AQUELLOS PAGOS SELECCIONADOS SOLO EN LA COPIA DEL BANCO
		$pagosContado -= (($copiaBanco == true) ? $rowPagosMostrarTradeIn['pagos_contado'] + $rowPagosMostrarContado['pagos_contado'] : 0);
		$pagosPND -= (($copiaBanco == true) ? $rowPagosMostrarTradeIn['pagos_pnd'] + $rowPagosMostrarContado['pagos_pnd'] : 0);
		$pagosBono -= (($copiaBanco == true) ? $rowPagosMostrarTradeIn['pagos_bono'] + $rowPagosMostrarContado['pagos_bono'] : 0);
		
		
		$posYVenta = $posY; // es usado en los accesorios del recuadro de la derecha
		
		// TRADEIN
		// CONSULTO SI LA FACTURA TIENE PAGO ANTICIPO Y ES DE TIPO TRADE-IN
		$queryTradein = sprintf("SELECT DISTINCT
			tradein.id_tradein,
			cxc_pago.montoPagado,
			cxc_ant.saldoAnticipo,
			tradein.allowance,
			tradein.payoff,
			tradein.acv,
			tradein.total_credito,
			vw_iv_modelo.nom_marca,
			uni_fis.placa,
			uni_fis.serial_carroceria, 
			uni_fis.kilometraje,
			uni_fis.serial_motor,
			color_ext.nom_color AS color_externo1,
			color_ext.des_color AS descripcion_color_externo1,
			vw_iv_modelo.nom_ano,
			vw_iv_modelo.nom_modelo,
			prov.nombre AS nombre_cliente_adeudado
		FROM an_pagos cxc_pago
			INNER JOIN cj_cc_anticipo cxc_ant ON (cxc_pago.numeroDocumento = cxc_ant.idAnticipo)
			INNER JOIN an_tradein tradein ON (cxc_ant.idAnticipo = tradein.id_anticipo)
			LEFT JOIN an_tradein_cxp tradein_cxp ON (tradein.id_tradein = tradein_cxp.id_tradein
				AND (tradein_cxp.estatus = 1 OR (tradein_cxp.estatus IS NULL AND DATE(tradein_cxp.fecha_anulado) > cxc_pago.fechaPago)))
			LEFT JOIN cp_proveedor prov ON (tradein_cxp.id_proveedor = prov.id_proveedor)
			INNER JOIN an_unidad_fisica uni_fis ON (tradein.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			LEFT JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		WHERE cxc_pago.id_factura = %s
			AND cxc_pago.formaPago IN (7)
			AND cxc_pago.estatus IN (1)
			AND cxc_ant.estatus = 1
		LIMIT 2;", // 7 = Anticipo
			valTpDato($idDocumento, "int"));
		$rsTradein = mysql_query($queryTradein);
		if (!$rsTradein) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__); }
		$totalRowsTradein = mysql_num_rows($rsTradein);
		if ($totalRowsTradein == 0) {
			$queryTradein = sprintf("SELECT NULL;");
			$rsTradein = mysql_query($queryTradein);
			if (!$rsTradein) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__); }
			$totalRowsTradein = mysql_num_rows($rsTradein);
		}
		$contTradeIn = 0;
		$creditoTradeIn = 0;
		while ($rowTradein = mysql_fetch_assoc($rsTradein)) {
			$contTradeIn++;
			
			if ($contTradeIn == 1) {
				$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
				imagefilledrectangle($img, 0, ($posY-1), 235, ($posY+8), $backgroundNegro);
				imagestring($img,1,0,$posY,str_pad(utf8_decode("VEHÍCULO USADO TOMADO A CAMBIO"), 47, " ", STR_PAD_BOTH),$textColorBlanco);
			}
			
			$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("MARCA"), 8, " ", STR_PAD_RIGHT)."  ".
				substr($rowTradein["nom_marca"],0,12)),$textColor);
			
			imagestring($img,1,115,$posY, strtoupper(str_pad(utf8_decode("MODELO"), 8, " ", STR_PAD_RIGHT)."  ".
				substr($rowTradein["nom_modelo"],0,12)),$textColor);
			
			
			$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("AÑO"), 8, " ", STR_PAD_RIGHT)."  ".
				substr($rowTradein["nom_ano"],0,12)),$textColor);
			
			imagestring($img,1,115,$posY, strtoupper(str_pad(utf8_decode("COLOR"), 8, " ", STR_PAD_RIGHT)."  ".
				substr($rowTradein["descripcion_color_externo1"],0,12)),$textColor);
			
			
			$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode($spanSerialCarroceria), 8, " ", STR_PAD_RIGHT)."  "),$textColor);
			imagestring($img,2,55,$posY-3, strtoupper(substr($rowTradein["serial_carroceria"],0,24)),$textColor);
			
			
			$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode($spanPlaca), 8, " ", STR_PAD_RIGHT)."  "),$textColor);
			imagestring($img,2,55,$posY-3, strtoupper(substr($rowTradein["placa"],0,24)),$textColor);
			
			imagestring($img,1,115,$posY, strtoupper(str_pad(utf8_decode($spanKilometraje), 8, " ", STR_PAD_RIGHT)."  ".
				substr($rowTradein["kilometraje"],0,12)),$textColor);
			
			
			$montoAllowance = $rowTradein['allowance'];
			$montoACV = $rowTradein['acv'];
			$montoAjusteTradeIn = $rowTradein['allowance'] - $rowTradein['acv'];
			
			// VARIABLE PARA ESTABLECER SI SE DESEA QUE EL TRADE-IN SEA MOSTRADO CON EL AJUSTE DE LA DIFERENCIA ENTRE EL ALLOWANCE Y EL ACV
			$mostrarTradeInConAjuste = false;
			$montoCreditoTradeIn = ($mostrarTradeInConAjuste == true) ? $montoACV : $montoAllowance;
			$montoAjusteTradeIn = ($mostrarTradeInConAjuste == true) ? $montoAjusteTradeIn : 0;
			
			$montoCreditoTradeIn += (($copiaBanco == true && $contTradeIn == 1) ? $rowPagosMostrarTradeIn['pagos_contado'] : 0);
			$montoCreditoTradeIn += (($copiaBanco == true && $contTradeIn == 1) ? $rowPagosMostrarTradeIn['pagos_pnd'] : 0);
			$montoCreditoTradeIn += (($copiaBanco == true && $contTradeIn == 1) ? $rowPagosMostrarTradeIn['pagos_bono'] : 0);
			$montoPayoff = $rowTradein['payoff'];
			$montoCreditoNeto = $montoCreditoTradeIn + $montoAjusteTradeIn - $montoPayoff;
			if ($copiaBanco == true) {
				if ($rowTradein['payoff'] > $rowTradein['allowance']
				&& in_array($rowEncabezado['id_credito_tradein'], array(1))) { // Null = Credito Negativo, 1 = Credito Positivo
					$montoCreditoTradeIn = $rowTradein['payoff'] - $montoAjusteTradeIn;
					
					// LOS PAGOS SERAN AGREGADOS COMO CREDITO AL ALLOWANCE DEL TRADE-IN
					$montoAllowanceTradeIn = $rowTradein['allowance'];
					$montoAllowanceTradeIn += (($copiaBanco == true && $contTradeIn == 1) ? $rowPagosMostrarTradeIn['pagos_contado'] : 0);
					$montoAllowanceTradeIn += (($copiaBanco == true && $contTradeIn == 1) ? $rowPagosMostrarTradeIn['pagos_pnd'] : 0);
					$montoAllowanceTradeIn += (($copiaBanco == true && $contTradeIn == 1) ? $rowPagosMostrarTradeIn['pagos_bono'] : 0);
					
					$montoPayoff = $rowTradein['payoff'];
					$montoCreditoNeto = $montoCreditoTradeIn + $montoAjusteTradeIn - $montoPayoff;
					$montoPrecioVenta += $rowTradein['payoff'] - $montoAllowanceTradeIn;
				}
			}
			$pagosTradeIn += ($montoCreditoNeto >= 0) ? 0 : $montoCreditoNeto;
			$creditoTradeIn += $montoCreditoNeto;
			
			$pagosTradeIn = ($copiaBanco == true) ? $creditoTradeIn : $pagosTradeIn;
			
			
			$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
			imagefilledrectangle($img, 0, ($posY-1), 235, ($posY+8), $backgroundAzul);
			imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("ADEUDADO A"), 12, " ", STR_PAD_RIGHT)."  ".
				substr((($montoCreditoTradeIn > 0 && $montoPayoff == 0) ? "SALDO" : $rowTradein['nombre_cliente_adeudado']),0,27)),$textColor);
			
			$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("CRÉDITO POR VEHÍCULO USADO"), 26, " ", STR_PAD_RIGHT)."  ".
				str_pad(formatoNumero($montoCreditoTradeIn), 17, " ", STR_PAD_LEFT)),$textColor);
			
			if ($montoAjusteTradeIn > 0) {
				// PAGOS DE NOTAS DE CREDITO CON ANTICIPO CERO O NEGATIVO
				$queryPagosUpsideDown = sprintf("SELECT
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nc_det_motivo.id_nota_credito = cxc_pago.numeroDocumento) AS descripcion_motivo,
					cxc_pago.montoPagado
				FROM an_pagos cxc_pago
				WHERE cxc_pago.id_factura = %s 
					AND cxc_pago.formaPago IN (8)
					AND cxc_pago.numeroDocumento IN (SELECT tradein_cxc.id_nota_credito_cxc FROM an_tradein_cxc tradein_cxc
													WHERE tradein_cxc.id_tradein = %s
														AND tradein_cxc.id_anticipo IS NOT NULL
														AND tradein_cxc.id_nota_credito_cxc IS NOT NULL
														AND tradein_cxc.id_anticipo IN (SELECT cxc_ant.idAnticipo FROM cj_cc_anticipo cxc_ant
																						WHERE cxc_ant.montoNetoAnticipo = 0))
					AND (((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
							WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento
								AND cxc_nc.idDocumento <> cxc_pago.id_factura) LIKE 'FA')
							OR ((SELECT cxc_nc.tipoDocumento FROM cj_cc_notacredito cxc_nc
								WHERE cxc_nc.idNotaCredito = cxc_pago.numeroDocumento) LIKE 'NC'))
					AND cxc_pago.estatus IN (1,2)",
					valTpDato($idDocumento, "int"),
					valTpDato($rowTradein['id_tradein'], "int"));//die($queryPagosUpsideDown);
				$rsPagosUpsideDown = mysql_query($queryPagosUpsideDown);
				if (!$rsPagosUpsideDown) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagosUpsideDown); }
				$totalRowsPagosUpsideDown = mysql_num_rows($rsPagosUpsideDown);
				$rowPagosUpsideDown = mysql_fetch_assoc($rsPagosUpsideDown);
				
				$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
				imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode($rowPagosUpsideDown['descripcion_motivo']), 26, " ", STR_PAD_RIGHT))."  ",$textColor);
				imagestring($img,1,140,$posY,str_pad(formatoNumero($montoAjusteTradeIn), 18, " ", STR_PAD_LEFT),$textColor);
			} else if ($montoAjusteTradeIn < 0) {
				// PAGOS DE NOTAS DE CREDITO CON ANTICIPO CERO O NEGATIVO
				$queryPagosUpsideDown = sprintf("SELECT
					(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
					FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
						INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
					WHERE cxc_nd_det_motivo.id_nota_cargo IN (SELECT cxc_pago2.idNotaCargo FROM cj_det_nota_cargo cxc_pago2
																WHERE cxc_pago2.idFormaPago IN (7)
																	AND cxc_pago2.numeroDocumento = cxc_pago.numeroDocumento
																	AND cxc_pago2.estatus IN (1,2))) AS descripcion_motivo
				FROM an_pagos cxc_pago
				WHERE cxc_pago.id_factura = %s
					AND cxc_pago.formaPago IN (7)
					AND cxc_pago.numeroDocumento IN (SELECT cxc_pago2.numeroDocumento FROM cj_det_nota_cargo cxc_pago2
									WHERE cxc_pago2.idNotaCargo IN (SELECT tradein_cxc.id_nota_cargo_cxc FROM an_tradein_cxc tradein_cxc
																		WHERE tradein_cxc.id_tradein = %s
																			AND tradein_cxc.id_anticipo IS NOT NULL
																			AND tradein_cxc.id_nota_cargo_cxc IS NOT NULL
																			AND tradein_cxc.id_anticipo IN (SELECT cxc_ant.idAnticipo FROM cj_cc_anticipo cxc_ant
																											WHERE cxc_ant.montoNetoAnticipo >= 0))
										AND cxc_pago2.idFormaPago IN (7))
					AND cxc_pago.estatus IN (1,2);",
					valTpDato($idDocumento, "int"),
					valTpDato($rowTradein['id_tradein'], "int"));
				$rsPagosUpsideDown = mysql_query($queryPagosUpsideDown);
				if (!$rsPagosUpsideDown) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagosUpsideDown); }
				$totalRowsPagosUpsideDown = mysql_num_rows($rsPagosUpsideDown);
				$rowPagosUpsideDown = mysql_fetch_assoc($rsPagosUpsideDown);
				
				$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
				imagestring($img,1,5,$posY,strtoupper(str_pad(utf8_decode($rowPagosUpsideDown['descripcion_motivo']), 26, " ", STR_PAD_RIGHT))."  ",$textColor);
				imagestring($img,1,140,$posY,str_pad(formatoNumero($montoAjusteTradeIn), 18, " ", STR_PAD_LEFT),$textColor);
			}
			
			$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
			imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("BALANCE ADEUDADO"), 26, " ", STR_PAD_RIGHT)."  ".
				str_pad(formatoNumero($montoPayoff), 17, " ", STR_PAD_LEFT)),$textColor);
			
			$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
			imagefilledrectangle($img, 0, ($posY-1), 235, ($posY+8), $backgroundGris);
			imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("CRÉDITO NETO"), 26, " ", STR_PAD_RIGHT)."  "),$textColor);
			imagestring($img,2,123,$posY-3,str_pad(formatoNumero($montoCreditoNeto), 18, " ", STR_PAD_LEFT),$textColor);
		}
		
		if ($copiaBanco == true) {
			if ($pagosPND > 0 && in_array($rowConfig208['valor'], array(1))) {
				$montoPrecioVenta -= $pagosPND;
				$pagosPND -= $pagosPND;
			}
		}
		
		$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
		imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("PAGO CONTADO"), 26, " ", STR_PAD_RIGHT)."  ".
			str_pad(formatoNumero($pagosContado), 17, " ", STR_PAD_LEFT)),$textColor);
		
		$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
		imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("PND"), 26, " ", STR_PAD_RIGHT)."  ".
			str_pad(formatoNumero($pagosPND), 17, " ", STR_PAD_LEFT)),$textColor);
		
		$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
		if ($copiaBanco == true) {
			imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("OTROS PAGOS"), 26, " ", STR_PAD_RIGHT)."  "),$textColor);
		} else {
			// PAGOS BONO
			$queryPagosBono = sprintf("SELECT DISTINCT
				concepto_forma_pago.descripcion,
				IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0), cxc_ant_det.montoDetalleAnticipo, cxc_pago.montoPagado) AS montoPagado,
				IF(IFNULL(cxc_pago.montoPagado,0) <> IFNULL(cxc_ant_det.montoDetalleAnticipo,0),
					(IFNULL(cxc_pago.montoPagado,0)
						- IFNULL(cxc_ant_det.montoDetalleAnticipo,0)
						- IFNULL((SELECT SUM(cxc_ant_det2.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_ant_det2
							WHERE cxc_ant_det2.idAnticipo = cxc_ant.idAnticipo
								AND (id_concepto IS NULL OR id_concepto NOT IN (1,6))),0)), cxc_pago.montoPagado) AS montoPagado2
			FROM an_pagos cxc_pago
				LEFT JOIN cj_cc_anticipo cxc_ant ON (cxc_pago.numeroDocumento = cxc_ant.idAnticipo)
				LEFT JOIN cj_cc_detalleanticipo cxc_ant_det ON (cxc_ant.idAnticipo = cxc_ant_det.idAnticipo)
				LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_ant_det.id_concepto = concepto_forma_pago.id_concepto)
			WHERE cxc_pago.id_factura = %s
				AND cxc_pago.formaPago IN (7)
				AND cxc_ant_det.id_concepto IN (1,6)
				AND cxc_ant.estatus = 1
				AND cxc_pago.estatus IN (1,2)
				AND cxc_pago.id_condicion_mostrar = 1",
				valTpDato($idDocumento, "int"));
			$rsPagosBono = mysql_query($queryPagosBono);
			if (!$rsPagosBono) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>Query: ".$queryPagosBono); }    
			$totalRowsPagosBono = mysql_num_rows($rsPagosBono);
			if ($totalRowsPagosBono > 0) {
				imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("OTROS PAGOS"), 26, " ", STR_PAD_RIGHT)."  "),$textColor);
				
				while ($rowPagosBono = mysql_fetch_assoc($rsPagosBono)) {
					$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
					imagestring($img,1,15,$posY, strtoupper(str_pad(utf8_decode($rowPagosBono['descripcion']), 24, " ", STR_PAD_RIGHT)."  ".
						str_pad(formatoNumero($rowPagosBono['montoPagado']), 17, " ", STR_PAD_LEFT)),$textColor);
				}
				
				$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
				imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("TOTAL OTROS PAGOS"), 26, " ", STR_PAD_RIGHT)."  "),$textColor);
			} else {
				imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("OTROS PAGOS"), 26, " ", STR_PAD_RIGHT)."  "),$textColor);
			}
		}
		imagestring($img,1,140,$posY,str_pad(formatoNumero($pagosBono), 18, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
		imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("MONTO POLIZA"), 26, " ", STR_PAD_RIGHT)."  ".
			str_pad(formatoNumero($rowTradein['']), 17, " ", STR_PAD_LEFT)),$textColor);
		
		$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
		imagefilledrectangle($img, 0, ($posY-1), 235, ($posY+8), $backgroundGris);
		imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("CRÉDITO A FAVOR"), 26, " ", STR_PAD_RIGHT)."  "),$textColor);
		imagestring($img,2,123,$posY-3,str_pad(formatoNumero($pagosContado + $pagosPND + $pagosBono), 18, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
		imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("VENC. POLIZA"), 12, " ", STR_PAD_RIGHT)."  ".
			substr($rowTradein[''],0,27)),$textColor);
		
		$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
		imagefilledrectangle($img, 0, ($posY-1), 235, ($posY+8), $backgroundAzul);
		imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("ASEGURADORA"), 12, " ", STR_PAD_RIGHT)."  ".
			substr($rowTradein[''],0,27)),$textColor);
		
		$creditoTotal = $pagosContado + $pagosTradeIn + $pagosPND + $pagosBono;
		
		$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
		imagefilledrectangle($img, 0, ($posY-1), 235, ($posY+8), $backgroundGris);
		imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("CRÉDITO TOTAL"), 26, " ", STR_PAD_RIGHT)."  "),$textColor);
		imagestring($img,2,123,$posY-3,str_pad(formatoNumero($creditoTotal), 18, " ", STR_PAD_LEFT),$textColor);
		
		//if (strlen($rowEncabezado['nombreBanco']) > 0) {
			$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
			imagefilledrectangle($img, 0, ($posY-1), 235, ($posY+8), $backgroundNegro);
			imagestring($img,1,0,$posY,str_pad(utf8_decode("BALANCE - CONTRATO A PAGARSE DE ACUERDO CON"), 47, " ", STR_PAD_BOTH),$textColorBlanco);
			
			//if (strlen($rowEncabezado['nombreBanco']) > 0) {
				$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
				imagefilledrectangle($img, 0, ($posY-1), 235, ($posY+8), $backgroundAzul);
				imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("FINANCIADO POR"), 14, " ", STR_PAD_RIGHT)."  ".
					substr((($rowEncabezado['porcentaje_inicial'] < 100 && $rowEncabezado['nombreBanco'] == "") ? "DEALER TRADE" : $rowEncabezado['nombreBanco']),0,27)),$textColor);
			//}
			
			$mesesFinanciar = ($rowEncabezado["meses_financiar"] > 0) ? $rowEncabezado["meses_financiar"]." MESES. APR: ".$rowEncabezado["interes_cuota_financiar"]." %" : "";
			//if ($mesesFinanciar) {
				/*$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
				imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("FINANCIAMIENTO"), 14, " ", STR_PAD_RIGHT)."  ".
					substr($mesesFinanciar,0,10)),$textColor);*/
				
				$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
				imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("EN"), 4, " ", STR_PAD_RIGHT)."  ".
					str_pad(utf8_decode(substr($rowEncabezado["meses_financiar"],0,8)), 8, " ", STR_PAD_RIGHT).
					str_pad(utf8_decode("PLAZOS MENSUALES DE"), 20, " ", STR_PAD_RIGHT)." ".
					str_pad(utf8_decode(substr(formatoNumero($rowEncabezado["cuotas_financiar"]),0,10)), 10, " ", STR_PAD_LEFT)),$textColor);
				
				$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
				imagestring($img,1,5,$posY, strtoupper(str_pad(utf8_decode("EN"), 4, " ", STR_PAD_RIGHT)."  ".
					str_pad(utf8_decode(substr($rowEncabezado["meses_financiar2"],0,8)), 8, " ", STR_PAD_RIGHT).
					str_pad(utf8_decode("PLAZOS MENSUALES DE"), 20, " ", STR_PAD_RIGHT)." ".
					str_pad(utf8_decode(substr(formatoNumero($rowEncabezado["cuotas_financiar2"]),0,10)), 10, " ", STR_PAD_LEFT)),$textColor);
			//}
		//}
		
		$posY += 11; imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
		imagefilledrectangle($img, 0, ($posY-1), 235, ($posY+8), $backgroundNegro);
		imagestring($img,1,0,$posY,str_pad(utf8_decode("OBSERVACIONES"), 47, " ", STR_PAD_BOTH),$textColorBlanco);
		$posY += 2;
		$arrayObservacionDcto = str_split(strtoupper($rowEncabezado['observacionFactura']), 45);
		if (isset($arrayObservacionDcto)) {
			foreach ($arrayObservacionDcto as $indice => $valor) {
				if (strlen($valor) > 0) {
					$posY += 8;
					imagestring($img,1,5,$posY,strtoupper(trim($valor)),$textColor);
				}
			}
			imageline($img, 0, ($posY+9), 235, ($posY+9), $textColor); // linea H -
		}
		
		imageline($img, 0, $posYVenta + 10, 0, ($posY+9), $textColor);//linea V |
		imageline($img, 235, $posYVenta + 10, 235, ($posY+9), $textColor);//linea V |
		
		
		
		
		$posY = $posYVenta; // a partir de la linea del recuadro inicial
		
		$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		imagefilledrectangle($img, 235, ($posY-1), 468, ($posY+8), $backgroundGris);
		$montoPrecioVenta = $rowUnidad['precio_unitario'] + $montoPrecioVenta;
		imagestring($img,1,240,$posY, strtoupper(str_pad(utf8_decode("PRECIO UNIDAD"), 25, " ", STR_PAD_RIGHT)."  ".
			str_pad(formatoNumero($montoPrecioVenta), 17, " ", STR_PAD_LEFT)),$textColor);
		
		foreach ($arrayAdicionales as $key => $accAdi){
			$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
			imagestring($img,1,240,$posY,
				strtoupper(str_pad(substr(utf8_decode($accAdi['nom_accesorio']),0,25), 25, " ", STR_PAD_RIGHT))."  ".
				str_pad(formatoNumero($accAdi['precio_unitario']), 17, " ", STR_PAD_LEFT),$textColor);
		}
		
		$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		imagefilledrectangle($img, 235, ($posY-1), 468, ($posY+8), $backgroundGris);
		$totalVehiculoAdicionales = $montoPrecioVenta + $totalAdicionales;
		imagestring($img,1,240,$posY, strtoupper(str_pad(utf8_decode("PRECIO TOTAL"), 25, " ", STR_PAD_RIGHT)."  "),$textColor);
		imagestring($img,2,359,$posY-3,str_pad(formatoNumero($totalVehiculoAdicionales), 17, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		imagestring($img,1,240,$posY, strtoupper(str_pad(utf8_decode("CRÉDITO TOTAL"), 25, " ", STR_PAD_RIGHT)."  ".
			str_pad(formatoNumero($creditoTotal), 17, " ", STR_PAD_LEFT)),$textColor);
		
		$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		imagefilledrectangle($img, 235, ($posY-1), 468, ($posY+8), $backgroundGris);
		$balancePagar = $totalVehiculoAdicionales - $creditoTotal;
		imagestring($img,1,240,$posY, strtoupper(str_pad(utf8_decode("BALANCE A PAGAR"), 25, " ", STR_PAD_RIGHT)."  "),$textColor);
		imagestring($img,2,359,$posY-3,str_pad(formatoNumero($balancePagar), 17, " ", STR_PAD_LEFT),$textColor);
		
		$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		
		$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		imagestring($img,1,240,$posY, strtoupper(str_pad(utf8_decode("OTROS"), 25, " ", STR_PAD_RIGHT)."  "),$textColor);
		
		foreach ($arrayContrato as $key => $contratos){
			$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
			imagestring($img,1,240,$posY,
				strtoupper(str_pad(substr(utf8_decode($contratos['nom_accesorio']),0,25), 25, " ", STR_PAD_RIGHT))."  ".
				str_pad(formatoNumero($contratos['precio_unitario']), 17, " ", STR_PAD_LEFT),$textColor);
		}
		
		$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		imagestring($img,1,240,$posY, strtoupper(str_pad(utf8_decode("TOTAL"), 25, " ", STR_PAD_RIGHT)."  ".
			str_pad(formatoNumero($totalContrato), 17, " ", STR_PAD_LEFT)),$textColor);
		
		$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
		imagefilledrectangle($img, 235, ($posY-1), 468, ($posY+8), $backgroundGris);
		$balancePagar = $totalVehiculoAdicionales - $creditoTotal;
		imagestring($img,1,240,$posY, strtoupper(str_pad(utf8_decode("BALANCE DE CONTRATO"), 25, " ", STR_PAD_RIGHT)."  "),$textColor);
		imagestring($img,2,359,$posY-3,str_pad(formatoNumero($balancePagar + $totalContrato), 17, " ", STR_PAD_LEFT),$textColor);
		
		
		if (count($arrayAdicionalesPagados) > 0) {
			$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
			
			$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
			imagestring($img,1,240,$posY, strtoupper(str_pad(utf8_decode("ADICIONALES PAGADOS"), 25, " ", STR_PAD_RIGHT)."  "),$textColor);
			foreach ($arrayAdicionalesPagados as $indice => $valor){
				$posY += 11; imageline($img, 235, ($posY+9), 468, ($posY+9), $textColor); // linea H -
				imagestring($img,1,240,$posY, strtoupper(str_pad(utf8_decode($valor['nom_accesorio']), 25, " ", STR_PAD_RIGHT)."  ".
					str_pad(formatoNumero($valor['precio_unitario']), 17, " ", STR_PAD_LEFT)),$textColor);
			}
		}
		
		imageline($img, 235, $posYVenta + 10, 235, ($posY+9), $textColor);//linea V |
		imageline($img, 468, $posYVenta + 10, 468, ($posY+9), $textColor);//linea V |
		
		$posY = 550;
		$posYVenta = $posY; // es usado en los accesorios del recuadro de la derecha
		imageline($img, 0, ($posY+5), 468, ($posY+5), $textColor); // linea H -
		if ($totalRowsTradein > 0) {
			$posY += 8;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("NOTA: EL COMPRADOR CERTIFICA QUE LA UNIDAD TOMADA A CAMBIO ESTÁ LIBRE DE CUALQUIER GRAVAMEN")), 92, " ", STR_PAD_BOTH),$textColor);
			$posY += 8;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("O VENTA CONDICIONAL. ASÍ MISMO, SE PACTA QUE DE HABER CUALQUIER DEUDA, EL COMPRADOR SE HARÁ")), 92, " ", STR_PAD_BOTH),$textColor);
			$posY += 8;
			imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("RESPONSABLE. EJ. MULTAS, SEGURO, ETC.")), 92, " ", STR_PAD_BOTH),$textColor);
		}
		
		$posY += 8;
		imagestring($img,1,5,$posY,str_pad(strtoupper(utf8_decode("SE COBRARÁ 0.95 CENTAVOS POR CADA MILLA CORRIDA A PARTIR DE LA FECHA DE COMPRA")), 92, " ", STR_PAD_BOTH),$textColor);
		imageline($img, 0, $posYVenta + 5, 0, ($posY+10), $textColor);//linea V |
		imageline($img, 468, $posYVenta + 5, 468, ($posY+10), $textColor);//linea V |
		imageline($img, 0, ($posY+10), 468, ($posY+10), $textColor); // linea H -
		
		$posY = 612;
		imagestring($img,1,0,$posY,utf8_decode("______________________"),$textColor);
		imagestring($img,1,180,$posY,utf8_decode("______________________"),$textColor);
		imagestring($img,1,360,$posY,utf8_decode("______________________"),$textColor);
		
		$posY += 9; 
		imagestring($img,1,0,$posY,str_pad(utf8_decode("FIRMA DEL CLIENTE"), 20, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,180,$posY,str_pad(utf8_decode("FIRMA DEL GERENTE"), 20, " ", STR_PAD_LEFT),$textColor);	
		imagestring($img,1,360,$posY,str_pad(utf8_decode("FIRMA DEL VENDEDOR"), 20, " ", STR_PAD_LEFT),$textColor);	
		
		$arrayImg[] = "tmp/"."factura_vehiculo".$pageNum.".png";
		$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
		
		$copiaBanco = true;
	}
}


// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Vehículos)
$queryConfig206 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 206 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig206 = mysql_query($queryConfig206, $conex);
if (!$rsConfig206) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig206 = mysql_num_rows($rsConfig206);
$rowConfig206 = mysql_fetch_assoc($rsConfig206);

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
$rutaLogo = "../../".$rowEmp["logo_familia"];

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		if (in_array($rowConfig403['valor'],array(1,2))) {
			$pdf->Image($valor, 15, $rowConfig206['valor'], 580, 688);
		} else if (in_array($rowConfig403['valor'],array(3))) {
			$pdf->Image($valor, 15, $rowConfig206['valor'], 580, 738);
		}
		
		if ($idEmpresa > 0 && $rowConfig403['valor'] == 3) {
			$pdf->Image($rutaLogo,15,$rowConfig206['valor'] + 5,80);
		}
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
