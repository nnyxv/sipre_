<?php
session_start();
set_time_limit(0);
require_once ("../../connections/conex.php");
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("10","10","10");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"40");
$pdf->mostrarFooter = 1;
$pdf->nombreImpreso = $_SESSION['nombreEmpleadoSysGts'];

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/
$idPresupuesto = $_GET['id'];

// BUSCA LOS DATOS DE LA ORDEN DE SERVICIO
$queryPresupuesto = sprintf("SELECT pres_vent.*,
	pres_vent.estado AS estado_presupuesto,
	pres_vent_acc.id_presupuesto_accesorio,
	cliente.id AS id_cliente,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
	CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_contacto,
	cliente.tipo,
	cliente.ciudad,
	cliente.direccion,
	cliente.telf,
	cliente.otrotelf,
	cliente.correo,
	cliente.reputacionCliente + 0 AS id_reputacion_cliente,
	CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
	vw_iv_modelo.nom_uni_bas,
	vw_iv_modelo.nom_marca,
	vw_iv_modelo.nom_modelo,
	vw_iv_modelo.nom_version,
	vw_iv_modelo.nom_ano,
	vw_iv_modelo.imagen_auto,
	vw_pg_empleado.id_empleado,
	vw_pg_empleado.nombre_empleado,
	vw_pg_empleado.nombre_cargo,
	vw_pg_empleado.telefono,
	vw_pg_empleado.celular,
	vw_pg_empleado.email,
	IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.idmoneda, moneda_local.idmoneda) AS id_moneda,
	IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.descripcion, moneda_local.descripcion) AS descripcion_moneda,
	IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
	pres_vent.id_banco_financiar,
	banco.nombreBanco,
	ped_vent.estado_pedido,
	ped_financ.id_pedido_financiamiento,
	ped_financ.numeracion_pedido,
	ped_financ.estatus_pedido,
	
	IFNULL(pres_vent.precio_venta * (pres_vent.porcentaje_iva + pres_vent.porcentaje_impuesto_lujo) / 100, 0) AS monto_impuesto,	
	(IFNULL(pres_vent.precio_venta, 0)
		+ IFNULL(pres_vent.precio_venta * (pres_vent.porcentaje_iva + pres_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta_impuesto,
	
	(SELECT COUNT(*)
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
	WHERE id_uni_bas = pres_vent.id_uni_bas
		AND (alm.id_empresa = pres_vent.id_empresa
			OR pres_vent.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = alm.id_empresa)
			OR pres_vent.id_empresa IN (SELECT suc.id_empresa FROM pg_empresa suc
					WHERE suc.id_empresa_padre = alm.id_empresa)
			OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = pres_vent.id_empresa) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
																WHERE suc.id_empresa = alm.id_empresa))
		AND estado_venta IN ('POR REGISTRAR','DISPONIBLE')
		AND propiedad = 'PROPIO') AS ud,
	
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM an_presupuesto pres_vent
	INNER JOIN cj_cc_cliente cliente ON (pres_vent.id_cliente = cliente.id)
	INNER JOIN pg_monedas moneda_local ON (pres_vent.id_moneda = moneda_local.idmoneda)
	LEFT JOIN pg_monedas moneda_extranjera ON (pres_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (pres_vent.id_uni_bas = vw_iv_modelo.id_uni_bas)
	INNER JOIN vw_pg_empleados vw_pg_empleado ON (pres_vent.asesor_ventas = vw_pg_empleado.id_empleado)
	LEFT JOIN an_pedido ped_vent ON (pres_vent.id_presupuesto = ped_vent.id_presupuesto)
	LEFT JOIN bancos banco ON (pres_vent.id_banco_financiar = banco.idBanco)
	LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
	LEFT JOIN fi_documento ped_financ_det ON (pres_vent.id_presupuesto = ped_financ_det.id_presupuesto)
		LEFT JOIN fi_pedido ped_financ ON (ped_financ_det.id_pedido_financiamiento = ped_financ.id_pedido_financiamiento)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (pres_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
WHERE pres_vent.id_presupuesto = %s;",
	valTpDato($idPresupuesto, "int"));
$rsPresupuesto = mysql_query($queryPresupuesto);
if (!$rsPresupuesto) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRowsPresupuesto = mysql_num_rows($rsPresupuesto);
$rowPresupuesto = mysql_fetch_array($rsPresupuesto);

$idEmpresa = $rowPresupuesto['id_empresa'];
$idCliente = $rowPresupuesto['id_cliente'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$pdf->nombreRegistrado = $rowPresupuesto['nombre_empleado'];
$pdf->logo_familia = "../../".$rowEmp['logo_familia'];
$pdf->nombre_empresa = $rowEmp['nombre_empresa'];
$pdf->rif = (strlen($rowEmp['rif']) > 1) ? utf8_encode($spanRIF.": ".$rowEmp['rif']) : "";
$pdf->direccion = $rowEmp['direccion'];
$pdf->telefono1 = $rowEmp['telefono1'];
$pdf->telefono2 = $rowEmp['telefono2'];
$pdf->web = $rowEmp['web'];
$pdf->mostrarHeader = 1;
if ($totalRowsPresupuesto > 0) {
	$pdf->AddPage();
	
	// DATOS DEL CLIENTE
	$pdf->SetTextColor(217,317,247);
	$pdf->SetFont('Arial','B',8);
	$pdf->SetFillColor(49,112,143);
	$pdf->Cell("390",14,utf8_decode("DATOS DEL CLIENTE"),'B',0,'L',true);
	
	$pdf->Cell("200",14,utf8_decode("DATOS DEL PRESUPUESTO"),'B',0,'L',true);
	$pdf->Ln();
	
	
	$pdf->SetFont('Arial','',7);
	$pdf->SetTextColor(0,0,0);
	$pdf->Cell("80",14,utf8_decode("Cliente:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("115",14,$rowPresupuesto['nombre_cliente'],0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("80",14,utf8_decode($spanClienteCxC.":"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("115",14,$rowPresupuesto['ci_cliente'],0,0,'L');
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("80",14,utf8_decode("Nro. Presupuesto:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("120",14,$rowPresupuesto['numeracion_presupuesto'],0,0,'L');
	$pdf->Ln();
	
	
	$posYInicio = $pdf->GetY();
	$posXInicio = $pdf->GetX();
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("80",14,utf8_decode("Dirección:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->MultiCell("310",14,elimCaracter(utf8_encode($rowPresupuesto['direccion']),";"),'','L');
	
	$pdf->SetY($posYInicio);
	$pdf->SetX($posXInicio + 390);
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("80",14,utf8_decode("Fecha:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("120",14,date(spanDateFormat,strtotime($rowPresupuesto['fecha'])),0,0,'L');
	$pdf->Ln();
	
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("80",14,utf8_decode("Ciudad:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("115",14,$rowPresupuesto['ciudad'],0,0,'L');
	$pdf->Cell("195",14,"",0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("80",14,utf8_decode("Moneda:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("120",14,utf8_decode($rowPresupuesto['descripcion_moneda']),0,0,'L');
	$pdf->Ln();
	
	
	$arrayTelefono = NULL;
	(strlen($rowPresupuesto['telf']) > 1) ? $arrayTelefono[] = $rowPresupuesto['telf'] : "";
	(strlen($rowPresupuesto['otrotelf']) > 1) ? $arrayTelefono[] = $rowPresupuesto['otrotelf'] : "";
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("80",14,utf8_decode("Teléfono:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("115",14,implode(" / ", $arrayTelefono),0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("80",14,utf8_decode($spanEmail.":"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("115",14,utf8_encode($rowPresupuesto['correo']),0,0,'L');
	$pdf->Ln();
	
	
	
	$pdf->Ln();
	
	
	
	// DATOS DEL VEHÍCULO
	$pdf->SetTextColor(217,317,247);
	$pdf->SetFont('Arial','B',8);
	$pdf->SetFillColor(49,112,143);
	$pdf->Cell("590",14,utf8_decode("DATOS DEL VEHÍCULO"),'B',0,'L',true);
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',7);
	$pdf->SetTextColor(0,0,0);
	$pdf->Cell("70",14,utf8_decode("Unidad Básica:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("80",14,$rowPresupuesto['nom_uni_bas'],0,0,'L');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Marca:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("80",14,$rowPresupuesto['nom_marca'],0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Modelo:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("80",14,$rowPresupuesto['nom_modelo'],0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Versión:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("80",14,$rowPresupuesto['nom_version'],0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Año:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("80",14,$rowPresupuesto['nom_ano'],0,0,'L');
	$pdf->Ln();
	
	$pdf->Ln();
	
	$posYOriginal = $pdf->GetY();
	$posXOriginal = $pdf->GetX();
	
	
	
	// FOTO REFERENCIAL
	$posYInicio = $pdf->GetY();
	$posXInicio = $pdf->GetX();
	if (strlen($rowPresupuesto['imagen_auto']) > 0&& file_exists(raizSite.$rowPresupuesto['imagen_auto'])) {
		$pdf->Image(raizSite.utf8_encode($rowPresupuesto['imagen_auto']),($posXInicio + 320),$posYInicio,250);
	} else if (file_exists(raizSite."img/nodisponible.jpg")) {
		$pdf->Image(raizSite."img/nodisponible.jpg",($posXInicio + 320),$posYInicio,250);
	}
	
	$pdf->SetY($posYInicio + 160);
	$pdf->SetX($posXInicio + 305);
	$pdf->Cell("280",14,utf8_decode("FOTO REFERENCIAL"),'T',0,'C');
	
	$idConfiguracion = ($rowPresupuesto['tipo'] == "Natural") ? 200 : 201;
	
	// VERIFICA VALORES DE CONFIGURACION
	$queryConfigRecaudos = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = %s
		AND config_emp.status = 1
		AND config_emp.id_empresa = %s;",
		valTpDato($idConfiguracion, "int"),
		valTpDato($idEmpresa, "int"));
	$rsConfigRecaudos = mysql_query($queryConfigRecaudos,$conex);
	if (!$rsConfigRecaudos) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowConfigRecaudos = mysql_fetch_assoc($rsConfigRecaudos);
	
	$pdf->SetFont('Arial','',6);
	$pdf->SetY($posYInicio + 180);
	$pdf->SetX($posXInicio + 305);
	$pdf->MultiCell("280",12,($rowConfigRecaudos['valor']),'','L');
	$pdf->Ln();
	
	
	
	$pdf->Ln();
	$pdf->Ln();
	$pdf->Ln();
	$pdf->Ln();
	
	
	
	// FIRMA DEL EJECUTIVO DE VENTAS
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("400",14,"",0,0,'R');
	$pdf->Cell("190",14,utf8_decode($rowPresupuesto['nombre_empleado']),'T',0,'C');
	$pdf->Ln();
	$pdf->SetFont('Arial','',6);
	$pdf->Cell("400",12,"",0,0,'R');
	$pdf->Cell("190",12,utf8_decode($rowPresupuesto['nombre_cargo']),0,0,'C');
	$pdf->Ln();
	
	$arrayTelefono = NULL;
	(strlen($rowPresupuesto['telefono']) > 1) ? $arrayTelefono[] = $rowPresupuesto['telefono'] : "";
	(strlen($rowPresupuesto['celular']) > 1) ? $arrayTelefono[] = $rowPresupuesto['celular'] : "";
	$pdf->SetFont('Arial','',6);
	$pdf->Cell("400",12,"",0,0,'R');
	$pdf->Cell("190",12,implode(" / ", $arrayTelefono),0,0,'C');
	$pdf->Ln();
	$pdf->Cell("400",12,"",0,0,'R');
	$pdf->Cell("190",12,utf8_decode($rowPresupuesto['email']),0,0,'C');
	$pdf->Ln();
	
	
	
	// VENTA DE LA UNIDAD
	$pdf->SetY($posYOriginal);
	$pdf->SetX($posXOriginal);
	$pdf->SetTextColor(217,317,247);
	$pdf->SetFont('Arial','B',8);
	$pdf->SetFillColor(49,112,143);
	$pdf->Cell("300",14,utf8_decode("VENTA DE LA UNIDAD"),'B',0,'L',true);
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',7);
	$pdf->SetTextColor(0,0,0);
	$pdf->Cell("70",14,utf8_decode($spanPrecioUnitario." Base:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
	$pdf->Cell("80",14,number_format($rowPresupuesto['precio_venta'], 2, ".", ","),0,0,'R');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Descuento:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
	$pdf->Cell("80",14,number_format($rowPresupuesto['monto_descuento'], 2, ".", ","),0,0,'R');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Precio Venta Unidad".(($rowPresupuesto['monto_impuesto'] > 0) ? " (Incluye Impuesto)": "(E)").":"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
	$pdf->Cell("80",14,number_format($rowPresupuesto['precio_venta_impuesto'], 2, ".", ","),'T',0,'R');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Opcionales:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
	$pdf->Cell("80",14,number_format($rowPresupuesto['total_opcionales'], 2, ".", ","),0,0,'R');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Precio (Unidad + Opcionales):"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
	$pdf->Cell("80",14,number_format(($rowPresupuesto['precio_venta_impuesto'] + $rowPresupuesto['total_opcionales']), 2, ".", ","),'T',0,'R');
	$pdf->Ln();
	
	// ADICIONALES PREDETERMINADOS
	$queryAdicionalPredet = sprintf("SELECT * FROM an_accesorio acc WHERE acc.id_mostrar_predeterminado IN (1);");
	$rsAdicionalPredet = mysql_query($queryAdicionalPredet);
	if (!$rsAdicionalPredet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowAdicionalPredet = mysql_fetch_assoc($rsAdicionalPredet)) {
		$queryPedidoDet = sprintf("SELECT
			acc_pres.id_accesorio_presupuesto,
			acc_pres.id_presupuesto,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_pres.id_tipo_accesorio,
			(CASE acc_pres.id_tipo_accesorio
				WHEN 1 THEN 'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			acc_pres.precio_accesorio,
			acc_pres.costo_accesorio,
			acc_pres.porcentaje_iva_accesorio,
			(acc_pres.precio_accesorio + (acc_pres.precio_accesorio * acc_pres.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_pres.iva_accesorio,
			acc_pres.monto_pagado,
			acc_pres.id_condicion_pago,
			acc_pres.id_condicion_mostrar,
			acc_pres.monto_pendiente,
			acc_pres.id_condicion_mostrar_pendiente,
			acc_pres.estatus_accesorio_presupuesto
		FROM an_accesorio_presupuesto acc_pres
			INNER JOIN an_accesorio acc ON (acc_pres.id_accesorio = acc.id_accesorio)
		WHERE acc_pres.id_presupuesto = %s
			AND acc_pres.id_accesorio = %s
		ORDER BY acc.nom_accesorio ASC;",
			valTpDato($idPresupuesto, "int"),
			valTpDato($rowAdicionalPredet['id_accesorio'], "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		if ($totalRowsPedidoDet > 0) {
			$pdf->SetFont('Arial','',7);
			$pdf->Cell("70",14,utf8_decode($rowPedidoDet['nom_accesorio'].":"),0,0,'L');
			$pdf->SetFont('Arial','B',7);
			$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
			$pdf->Cell("80",14,number_format($rowPedidoDet['precio_con_iva'], 2, ".", ","),0,0,'R');
			$pdf->Ln();
		}
	}
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Adicionales:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
	$pdf->Cell("80",14,number_format($rowPresupuesto['total_adicionales'], 2, ".", ","),0,0,'R');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Total Pedido:"),'T',0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),'T',0,'R');
	$pdf->Cell("80",14,number_format($rowPresupuesto['total_general'], 2, ".", ","),'T',0,'R');
	$pdf->Ln();
	
	$mostrarAnticipo = 2;
	if ($mostrarAnticipo == 1) {
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("70",14,utf8_decode($spanInicial." por Adicionales y Opcionales:"),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
		$pdf->Cell("80",14,number_format($rowPresupuesto['anticipo'], 2, ".", ","),0,0,'R');
		$pdf->Ln();
		
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("70",14,utf8_decode($spanInicial." por la Unidad:"),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
		$pdf->Cell("80",14,number_format($rowPresupuesto['monto_inicial'], 2, ".", ","),0,0,'R');
		$pdf->Ln();
	} else {
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("70",14,utf8_decode($spanInicial.":"),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
		$pdf->Cell("80",14,number_format(($rowPresupuesto['anticipo'] + $rowPresupuesto['monto_inicial']), 2, ".", ","),0,0,'R');
		$pdf->Ln();
	}
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Cash Back:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
	$pdf->Cell("80",14,number_format($rowPresupuesto['monto_cash_back'], 2, ".", ","),0,0,'R');
	$pdf->Ln();
	
	
	
	$pdf->Ln();
	
	
	
	// DATOS DEL FINANCIAMIENTO
	$pdf->SetTextColor(217,317,247);
	$pdf->SetFont('Arial','B',8);
	$pdf->SetFillColor(49,112,143);
	$pdf->Cell("300",14,utf8_decode("DATOS DEL FINANCIAMIENTO"),'B',0,'L',true);
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',7);
	$pdf->SetTextColor(0,0,0);
	$pdf->Cell("70",14,utf8_decode("Entidad Bancaria:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("130",14,utf8_decode($rowPresupuesto['nombreBanco']),0,0,'L');
	$pdf->Ln();
	
	$pdf->SetFont('Arial','',7);
	$pdf->Cell("70",14,utf8_decode("Saldo a Financiar:"),0,0,'L');
	$pdf->SetFont('Arial','B',7);
	$pdf->Cell("150",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
	$pdf->Cell("80",14,number_format($rowPresupuesto['saldo_financiar'], 2, ".", ","),0,0,'R');
	$pdf->Ln();
	
	if ($rowPresupuesto['meses_financiar'] > 0) {
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("20",14,utf8_decode("En"),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("20",14,number_format($rowPresupuesto['meses_financiar'], 2, ".", ","),0,0,'R');
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("90",14,utf8_decode(($rowPresupuesto['meses_financiar'] > 1) ? "plazos mensuales con" : "plazo mensual con"),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("30",14,number_format($rowPresupuesto['interes_cuota_financiar'], 2, ".", ",")."%",0,0,'R');
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("50",14,utf8_decode("de interés"),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("10",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
		$pdf->Cell("80",14,number_format($rowPresupuesto['cuotas_financiar'], 2, ".", ","),0,0,'R');
		$pdf->Ln();
	}
	
	if ($rowPresupuesto['meses_financiar2'] > 0) {
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("20",14,utf8_decode("En"),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("20",14,number_format($rowPresupuesto['meses_financiar2'], 2, ".", ","),0,0,'R');
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("90",14,utf8_decode(($rowPresupuesto['meses_financiar2'] > 1) ? "plazos mensuales con" : "plazo mensual con"),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("30",14,number_format($rowPresupuesto['interes_cuota_financiar2'], 2, ".", ",")."%",0,0,'R');
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("50",14,utf8_decode("de interés"),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("10",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
		$pdf->Cell("80",14,number_format($rowPresupuesto['cuotas_financiar2'], 2, ".", ","),0,0,'R');
		$pdf->Ln();
	}
	
	if ($rowPresupuesto['porcentaje_flat'] > 0) {
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("70",14,utf8_decode("Comision FLAT:"),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("90",14,number_format($rowPresupuesto['porcentaje_flat'], 2, ".", ",")."%",0,0,'R');
		$pdf->SetFont('Arial','',7);
		$pdf->Cell("50",14,(""),0,0,'L');
		$pdf->SetFont('Arial','B',7);
		$pdf->Cell("10",14,utf8_decode($rowPresupuesto['abreviacion_moneda']),0,0,'R');
		$pdf->Cell("80",14,number_format($rowPresupuesto['monto_flat'], 2, ".", ","),0,0,'R');
		$pdf->Ln();
	}
	
	
	
	$pdf->Ln();
	
	
	
	// OPCIONALES
	$queryPresupuestoDet = sprintf("SELECT pres_vent.*,
		art.codigo_articulo,
		art.descripcion,
		(pres_vent.cantidad
			* (pres_vent.precio_unitario + (pres_vent.precio_unitario
											* (IFNULL((SELECT SUM(iva.iva)
														FROM pg_iva iva
															INNER JOIN iv_articulos_impuesto art_impuesto ON (iva.idIva = art_impuesto.id_impuesto)
														WHERE art_impuesto.id_articulo = pres_vent.id_articulo
															AND iva.tipo IN (6,9,2)
															AND art_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
																								WHERE cliente_imp_exento.id_cliente = %s)),0) / 100)))) AS total_item_con_impuesto
	
	FROM an_presupuesto_venta_detalle pres_vent
		INNER JOIN iv_articulos art ON (pres_vent.id_articulo = art.id_articulo)
	WHERE pres_vent.id_presupuesto_venta = %s
	ORDER BY pres_vent.id_presupuesto_venta_detalle ASC;",
		valTpDato($idCliente, "int"),
		valTpDato($idPresupuesto, "int"));
	$rsPresupuestoDet = mysql_query($queryPresupuestoDet);
	if (!$rsPresupuestoDet) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPresupuestoDet = mysql_num_rows($rsPresupuestoDet);
	if ($totalRowsPresupuestoDet > 0) {
		// OPCIONALES
		$pdf->SetTextColor(217,317,247);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetFillColor(49,112,143);
		$pdf->Cell("300",14,utf8_decode("OPCIONALES"),'B',0,'L',true);
		$pdf->Ln();
		
		$pdf->SetTextColor(217,317,247);
		$pdf->SetFont('Arial','B',6);
		$pdf->SetFillColor(49,112,143);
		$pdf->Cell("50",12,utf8_decode("Código"),1,0,'C',true);
		$pdf->Cell("150",12,utf8_decode("Descripción"),1,0,'C',true);
		$pdf->Cell("50",12,utf8_decode("Cantidad"),1,0,'C',true);
		$pdf->Cell("50",12,utf8_decode("Total"),1,0,'C',true);
		$pdf->Ln();
		
		while ($rowPresupuestoDet = mysql_fetch_assoc($rsPresupuestoDet)) {
			$pdf->SetFont('Arial','',6);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetBorder(array(
				"",
				"",
				"",
				"",
				""));
			$pdf->SetWidths(array(
				"50",
				"150",
				"50",
				"10",
				"40"));
			$pdf->SetAligns(array(
				"L",
				"L",
				"R",
				"R",
				"R"));
			$pdf->Row(array(
				elimCaracter(utf8_encode($rowPresupuestoDet['codigo_articulo']),";"),
				utf8_decode($rowPresupuestoDet['descripcion']),
				number_format($rowPresupuestoDet['cantidad'], 2, ".", ","),
				utf8_decode($rowPresupuesto['abreviacion_moneda']),
				number_format($rowPresupuestoDet['total_item_con_impuesto'], 2, ".", ",")), $fill);
		}
		
		
		
		$pdf->Ln();
	}
	
	
	
	
	// ADICIONALES
	$queryPedidoDet = sprintf("SELECT
		acc_pres.id_accesorio_presupuesto,
		acc_pres.id_presupuesto,
		acc.id_accesorio,
		CONCAT(acc.nom_accesorio, IF (acc_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
		acc.des_accesorio,
		acc_pres.id_tipo_accesorio,
		(CASE acc_pres.id_tipo_accesorio
			WHEN 1 THEN 'Adicional'
			WHEN 2 THEN 'Accesorio'
			WHEN 3 THEN 'Contrato'
		END) AS descripcion_tipo_accesorio,
		acc_pres.precio_accesorio,
		acc_pres.costo_accesorio,
		acc_pres.porcentaje_iva_accesorio,
		(acc_pres.precio_accesorio + (acc_pres.precio_accesorio * acc_pres.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
		acc_pres.iva_accesorio,
		acc_pres.monto_pagado,
		acc_pres.id_condicion_pago,
		acc_pres.id_condicion_mostrar,
		acc_pres.monto_pendiente,
		acc_pres.id_condicion_mostrar_pendiente,
		acc_pres.estatus_accesorio_presupuesto
	FROM an_accesorio_presupuesto acc_pres
		INNER JOIN an_accesorio acc ON (acc_pres.id_accesorio = acc.id_accesorio)
	WHERE acc_pres.id_presupuesto = %s
		AND (acc.id_mostrar_predeterminado IS NULL OR acc.id_mostrar_predeterminado = 0)
	ORDER BY acc_pres.id_accesorio_presupuesto ASC;",
		valTpDato($idPresupuesto, "int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsPresupuestoDet = mysql_num_rows($rsPresupuestoDet);
	if ($totalRowsPresupuestoDet > 0) {
		// ADICIONALES
		$pdf->SetTextColor(217,317,247);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetFillColor(49,112,143);
		$pdf->Cell("300",14,utf8_decode("ADICIONALES"),'B',0,'L',true);
		$pdf->Ln();
		
		$pdf->SetTextColor(217,317,247);
		$pdf->SetFont('Arial','B',6);
		$pdf->SetFillColor(49,112,143);
		$pdf->Cell("250",12,utf8_decode("Descripción"),1,0,'C',true);
		$pdf->Cell("50",12,utf8_decode("Total"),1,0,'C',true);
		$pdf->Ln();
		
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$pdf->SetFont('Arial','',6);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetBorder(array(
				"",
				"",
				""));
			$pdf->SetWidths(array(
				"250",
				"10",
				"40"));
			$pdf->SetAligns(array(
				"L",
				"R",
				"R"));
			$pdf->Row(array(
				utf8_decode($rowPedidoDet['nom_accesorio']),
				utf8_decode($rowPresupuesto['abreviacion_moneda']),
				number_format($rowPedidoDet['precio_con_iva'], 2, ".", ",")), $fill);
		}
		
		
		
		$pdf->Ln();
	}
	
	
	
	if (strlen($rowPresupuesto['observacion']) > 0) {
		// OBSERVACION
		$pdf->SetTextColor(217,317,247);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetFillColor(49,112,143);
		$pdf->Cell("300",14,utf8_decode("OBSERVACIÓN"),'B',0,'L',true);
		$pdf->Ln();
		
		$pdf->SetFont('Arial','',7);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell("300",14,utf8_decode($rowPresupuesto['observacion']),'','L');
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();
?>