<?php
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt',array(612,1195)); //normal, puntos/pixel ,array (ancho,alto) CON EL MEMBRETE SUJETADOR DE LAS COPIAS
//$pdf = new PDF_AutoPrint('P','pt',array(612,1152)); //normal, puntos/pixel ,array (ancho,alto) SIN EL MEMBRETE SUJETADOR DE LAS COPIAS
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/


$vista = $_GET['view'];
$idContrato = $_GET["id"];


if ($vista == "print"){
	//******CONTRATO DE VENTA AL POR MENOR A PLAZOS CO INTERES SIMPLE Y CLAUSULA DE ARBITRAJE - PUERTO RICO *******//
	
		
	/////////////////////// CONSULTAS A LA BASE DE DATOS ///////////////////////
	
	
	// CONTRATO GENERAL
	$contQuerySQL = "SELECT 
		cxc_fact.idFactura,
		cxc_fact.id_empresa,
		adi_cont.id_pedido,
		CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nom_cont,						
		CONCAT_WS(' ',cliente.urbanizacion, cliente.calle, cliente.casa) AS dir_cont,	
		cliente.municipio,
		CONCAT_WS(' ',cliente.urbanizacion_postal, cliente.calle_postal, cliente.casa_postal,cliente.municipio_postal) AS dir_postal_comprador,
		cliente.estado AS client_cod_postal,
		adi_cont.id_co_cliente,
		adi_cont.sunroof,
		adi_cont.int_cuero,
		adi_cont.uso_personal,
		adi_cont.uso_negocio,
		adi_cont.uso_agricola,
		adi_cont.nombre_gap,
		CONCAT_WS(', ',adi_cont.ciudad_agencia_seguro,adi_cont.pais_agencia_seguro) AS direccion_agencia,
		ped_vent.ded_poliza,
		ped_vent.interes_cuota_financiar,
		cxc_fact_acc.precio_unitario,
		ped_vent.id_poliza,
		ped_vent.ded_poliza,
		ped_vent.monto_seguro,
		ped_vent.meses_poliza,
		ped_vent.meses_financiar,
		ped_vent.cuotas_financiar,
		DATE_FORMAT(ped_vent.fecha_pago_cuota,'%d %b %Y') AS fecha_pago_cuotaa,
		ped_vent.meses_financiar2,
		ped_vent.cuotas_financiar2,
		DATE_FORMAT(ped_vent.fecha_pago_cuota2,'%d %b %Y') AS fecha_pago_cuotaa2,
		ped_vent.meses_financiar3,
		ped_vent.cuotas_financiar3,
		DATE_FORMAT(ped_vent.fecha_pago_cuota3,'%d %b %Y') AS fecha_pago_cuotaa3,
		ped_vent.meses_financiar4,
		ped_vent.cuotas_financiar4,
		DATE_FORMAT(ped_vent.fecha_pago_cuota4,'%d %b %Y') AS fecha_pago_cuotaa4,
		DATE_FORMAT(ped_vent.fecha_entrega,'%d %b %Y') AS fech_cont,
		DATE_FORMAT(ped_vent.fecha_entrega,'%d') AS dia_cont,
		DATE_FORMAT(ped_vent.fecha_entrega,'%b') AS mes_cont,
		DATE_FORMAT(ped_vent.fecha_entrega,'%Y') AS ano_cont,
		emp.ciudad_empresa,
		poliza.nombre_poliza
	FROM an_adicionales_contrato adi_cont
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact.numeroPedido = adi_cont.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
		INNER JOIN an_pedido ped_vent ON (adi_cont.id_pedido = ped_vent.id_pedido)
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		INNER JOIN cj_cc_factura_detalle_accesorios cxc_fact_acc ON (cxc_fact.idFactura = cxc_fact_acc.id_factura)
		INNER JOIN pg_empresa emp ON (ped_vent.id_empresa = emp.id_empresa)
		LEFT JOIN an_poliza poliza ON (ped_vent.id_poliza = poliza.id_poliza)			
	WHERE adi_cont.id_adi_contrato = {$idContrato}
	ORDER BY idFactura DESC
	LIMIT 1;";	
	$rsCont = mysql_query($contQuerySQL);
	if (!$rsCont) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowCont = mysql_fetch_array($rsCont);
	
	////////////////////// POSICIONAMIENTO DE CAMPOS /////////////////////////
	
	//************CREANDO IMAGEN POR PAGINA 1*****************//
	$img = @imagecreate(612, 1812) or die("No se puede crear la imagen");
	$pageNum = 1; //pagina 1 del documento
	
	// ESTABLECIENDO LOS COLORES DE LA PALETA
		$backgroundColor = imagecolorallocate($img, 255, 255, 255);
		$textColor = imagecolorallocate($img, 0, 0, 0);
		$textColor2 = imagecolorallocate($img, 255, 0, 0);
	
	/*if ("SI" == "SI") {
		// MARCA DE AGUA
		$src = imagecreatefrompng("../../img/contrato_venta_nmac.png");
		//imagen crear, imagen copiar, x,y destino. x,y a copiar, width height a copiar, 100 transparencia
		if(!imagecopyresampled($img, $src, -15, -60, 0, 0, 610, 1200, 610, 1200)){ die ("Error marca de agua"); }
	}*/
	
	// NOMBRE DEL COMPRADOR
		$posX = 12;
		$posY = 60;
		imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($rowCont['nom_cont'])), $textColor);
	
	// FECHA DEL CONTRATO
		$posX = 504;
		imagestring($img, 2, $posX, $posY, utf8_encode(strtoupper($rowCont['fech_cont'])), $textColor);
	
	
	// DIRECCION RESIDENCIAL DEL COMPRADOR
		$ArrayString = cutString(utf8_encode(strtoupper($rowCont['dir_cont'])),35);
		 $posX = 12;
		 $posY = 84;
		 imagestring($img, 2, $posX, $posY, $ArrayString[0], $textColor);
		
	// MUNICIPIO COMPRADOR
		$posX = 300;
		imagestring($img, 2, $posX, $posY, utf8_encode(strtoupper($rowCont['municipio'])), $textColor);
		
	// CODIGO POSTAL COMPRADOR 
		$posX = 432;
		imagestring($img, 2, $posX, $posY, utf8_encode(strtoupper($rowCont['client_cod_postal'])), $textColor);
	
	// NUMERO DE CONTRATO
		$posX = 504;
		imagestring($img, 2, $posX, $posY, utf8_encode(strtoupper($rowCont['id_pedido'])), $textColor);
	
	
	//************** VERFIFICANDO CO_DEUDOR **********************//
	if ($rowCont['id_co_cliente'] != null) {
		$coClientQuerySQL = sprintf("SELECT
			CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nom_co_client,
			CONCAT_WS(' ', cliente.urbanizacion, cliente.calle, cliente.casa) AS dir_co_client,												
			cliente.municipio,
			cliente.estado AS coclient_cod_postal							
		FROM an_adicionales_contrato adi_cont
			LEFT JOIN cj_cc_cliente cliente ON (adi_cont.id_co_cliente = cliente.id)
		WHERE adi_cont.id_adi_contrato = %s;",
			valTpDato($idContrato, "int"));
		$rsCoCliente = mysql_query($coClientQuerySQL);
		if (!$rsCoCliente) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowContCC = mysql_fetch_array($rsCoCliente);
		
		// NOMBRE Y DIRECCION DEL CO-COMPRADOR
			$posY = 108;
			$posX = 12;
			imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($rowContCC['nom_co_client'])), $textColor);
		
		// DIRECCION DEL CO-COMPRADOR 	//MUNICIPIO DEL CO-COMPRADOR  	//CODIGO POSTAL DEL CO-COMPRADOR (CONCATENADOS)
			$ArrayCoC = cutString($rowContCC['dir_co_client'],35);
			$posX += 204;
			imagestring($img, 2, $posX, $posY, utf8_encode(strtoupper($ArrayCoC[0]))." ".strtoupper($rowContCC['municipio'])." PR ".strtoupper($rowContCC['coclient_cod_postal']), $textColor);
	} else {
		// NOMBRE Y DIRECCION DEL CO-COMPRADOR
			$posY = 108;
			$posX = 12;
			imagestring($img, 2, $posX, $posY, "N/A", $textColor);
		
		// DIRECCION DEL CO-COMPRADOR 	//MUNICIPIO DEL CO-COMPRADOR  	//CODIGO POSTAL DEL CO-COMPRADOR (CONCATENADOS)
			$posX += 288;
			imagestring($img, 2, $posX, $posY, "N/A", $textColor);		
	}
	
		
	
	//************** VERFIFICANDO EMPRESA **********************//
	$idEmpresa = $rowCont['id_empresa'];
	
	$queryEmp = sprintf("SELECT *,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE vw_iv_emp_suc.id_empresa_reg = %s",
		valTpDato($idEmpresa, "int"),
	mysql_query("SET NAMES 'latin1';"));
	$rsEmp = mysql_query($queryEmp);
	if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowEmp = mysql_fetch_assoc($rsEmp);
	
	// NOMBRE DEL VENDEDOR
		$posX = 12;
		$posY = 132;
		imagestring($img, 2, $posX, $posY, utf8_encode(strtoupper($rowEmp['nombre_empresa'])), $textColor);
	
	// DIRECCION DEL VENDEDOR
		$ArrayString = cutString($rowEmp['direccion'],27);
		$ArrayString2 = cutString($ArrayString[1],38);
		
		$posX = 408;
		$posY = 120;
		imagestring($img, 2, $posX, $posY, strtoupper($ArrayString[0]), $textColor);
		$posX = 300;
		$posY = 132 ;
		imagestring($img, 2, $posX, $posY, strtoupper($ArrayString2[0]),$textColor);
	
	
	
	//************** DATOS UNIDAD NUEVA**********************//
	$idDocumento = $rowCont['idFactura']; //ID DE LA FACTURA
	
	// BUSCA LOS DATOS DE LA UNIDAD
	$queryUnidad = sprintf("SELECT
		uni_bas.nom_uni_bas,
		marca.nom_marca,
		modelo.nom_modelo,
		vers.nom_version,
		ano.nom_ano,
		uni_fis.placa,
		cond_unidad.descripcion AS condicion_unidad,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.kilometraje,
		color1.nom_color AS color_externo,
		cxc_fact_det_vehic.precio_unitario,
		uni_bas.com_uni_bas,
		codigo_unico_conversion,
		marca_kit,
		marca_cilindro,
		modelo_regulador,
		serial1,
		serial_regulador,
		capacidad_cilindro,
		fecha_elaboracion_cilindro,
		uni_fis.id_condicion_unidad,
		uni_bas.cil_uni_bas,
		uni_bas.pto_uni_bas
	FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
				INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
				INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
				INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
				INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
	WHERE cxc_fact_det_vehic.id_factura = %s",
		valTpDato($idDocumento, "int"));
	$rsUnidad = mysql_query($queryUnidad);
	if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsUnidad = mysql_num_rows($rsUnidad);
	$rowUnidad = mysql_fetch_array($rsUnidad);
	
	// AÑO
		$posX = 12;
		$posY = 220;
		imagestring($img, 2, $posX-6, $posY,  utf8_encode(substr($rowUnidad['nom_ano'], -2)), $textColor);
		
		if ($rowUnidad['id_condicion_unidad'] == 1) {
			// NUEVO
				$posX = 24;
				imagestring($img, 2, $posX+6, $posY, "XX", $textColor);
		} else {
			// USADO
				$posX = 48;
				imagestring($img, 2, $posX+6, $posY, "XX", $textColor);
		}
	
	// MARCA COMERCIAL
		$posX = 72;
		imagestring($img, 2, $posX+6, $posY,utf8_encode(strtoupper( $rowUnidad['nom_marca'])), $textColor);
	
	// CILINDRO
		$posX = 168;
		imagestring($img, 2, $posX, $posY, utf8_encode(strtoupper($rowUnidad['cil_uni_bas'])), $textColor);
	
	// ESTILO DE CARROCERIA
		$posX = 192;
		imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($rowUnidad['pto_uni_bas']))."DR", $textColor); // NUMERO DE PUERTAS 
	
	// MODELO
		$posX = 252;
		imagestring($img, 2, $posX, $posY, utf8_encode(strtoupper(substr($rowUnidad['nom_modelo'],0,12))), $textColor);
	
	// LECTURA DEL ODOMETRO
		$posX = 336;
		imagestring($img, 2, $posX+6, $posY, utf8_encode(strtoupper($rowUnidad['kilometraje'])), $textColor);
	
	// NUM. DE IDENTIFICACION
		$posX = 432;
		imagestring($img, 2, $posX+6, $posY, utf8_encode(strtoupper($rowUnidad['serial_carroceria'])), $textColor);
	
	
	
	// AIRE ACONDICIONADO
		$posX = 0;
		$posY = 240;
		imagestring($img, 2, $posX+2, $posY-3, "X", $textColor);
			
	// SUN ROOF
		$posX = 48;
		if ($rowCont['sunroof']){
			imagestring($img, 2, $posX+2, $posY-3, "X", $textColor);
		}
			
	// ALARMA
		$posX = 96;
		imagestring($img, 2, $posX+2, $posY-3, "X", $textColor);
			
	// ESTEREO
		$posX = 144;
		imagestring($img, 2, $posX, $posY-3, "X", $textColor);
			
	// REPRODUCTOR DE CD
		$posX = 192;
		imagestring($img, 2, $posX-2, $posY-3, "X", $textColor);
			
	// PARACHOQUES
		$posX = 228;
		imagestring($img, 2, $posX+3, $posY-2, "X", $textColor);
			
	// INTERIORES DE CUERO
		$posX = 276;
		if ($rowCont['int_cuero']){
			imagestring($img, 2, $posX+2, $posY-2, "X", $textColor);
		}	
			
	// COLOR
		$posX = 336;
		imagestring($img, 2, $posX+3, $posY-2, utf8_encode(strtoupper(substr($rowUnidad['color_externo'],0,17))), $textColor);
			
	// CODIGO DE LA LLAVE
		$posX = 468;
		imagestring($img, 2, $posX+3, $posY-2, "", $textColor);
	
	
	
	//****************DATOS DE TRADE IN*************//
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
		color1.nom_color AS color_externo,
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
		LEFT JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
	WHERE cxc_pago.id_factura = %s
		AND cxc_pago.formaPago IN (7)
		AND cxc_pago.estatus IN (1)
		AND cxc_ant.estatus = 1;", // 7 = Anticipo, 1 = Activo
		valTpDato($idDocumento, "int"));
	$rsTradein = mysql_query($queryTradein);
	if (!$rsTradein) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__); }
	$cantidadTradein = mysql_num_rows($rsTradein);
	$rowTradein = mysql_fetch_assoc($rsTradein);
	
	if ($cantidadTradein) {
		// OTROS
			$posX = 36;
			$posY = 252;
			imagestring($img, 2, $posX, $posY+4, "", $textColor);
		
		// AÑO - DESCRIPCION DE ENTREGA A CUENTA
			$posX = 204;
			imagestring($img, 2, $posX, $posY+4, utf8_encode(substr($rowTradein['nom_ano'], -2)), $textColor);
		
		// MARCA - DESCRIPCION DE ENTREGA A CUENTA
			$posX = 264;
			imagestring($img, 2, $posX, $posY+4, utf8_encode(strtoupper($rowTradein['nom_marca'])), $textColor);
		
		// MODELO - DESCRIPCION DE ENTREGA A CUENTA
			$posX = 336;
			imagestring($img, 2, $posX, $posY+4, utf8_encode(strtoupper(substr($rowTradein['nom_modelo'],0,8))), $textColor);
	} else {
		// OTROS
			$posX = 36;
			$posY = 252;
			imagestring($img, 2, $posX, $posY+4, "", $textColor);
		
		// AÑO - DESCRIPCION DE ENTREGA A CUENTA
			$posX = 204;
			imagestring($img, 2, $posX, $posY+4,"N/A", $textColor);
		
		// MARCA - DESCRIPCION DE ENTREGA A CUENTA
			$posX = 264;
			imagestring($img, 2, $posX, $posY+4, "N/A", $textColor);
		
		// MODELO - DESCRIPCION DE ENTREGA A CUENTA
			$posX = 336;
			imagestring($img, 2, $posX, $posY+4, "N/A", $textColor);
	}
	
	// USO - PERSONAL
		$posY = 252;
		$posX = 432;
		if ($rowCont['uso_personal'] != null){
			imagestring($img, 2, $posX+2, $posY-6, "X", $textColor);
		}
	
	// USO - NEGOCIOS
		$posY = 264;
		if ($rowCont['uso_negocio'] != null){
			imagestring($img, 2, $posX+2, $posY-8, "X", $textColor);
		}	

	// USO - AGRICOLA
		$posX = 528;
		if ($rowCont['uso_agricola'] != null){
			imagestring($img, 2, $posX+2, $posY-8, "X", $textColor);
		}
	
	//*************************************************************************************************************//
		
	// QUERY DE ADICIONALES DE CONTRATO
	$idPedido = $rowCont['id_pedido'];
	
	$queryAdiSQL = sprintf("SELECT
		acc.nom_accesorio,
		acc.id_filtro_factura,
		cxc_fact_acc.id_factura_detalle_accesorios,
		cxc_fact_acc.precio_unitario,
		adi_cont.per_gap,
		adi_cont.per_credit_life,
		adi_cont.per_cont_serv,
		adi_cont.ded_gap,
		adi_cont.ded_credit_life,
		adi_cont.ded_cont_serv,
		adi_cont.id_emp_cont_serv,
		emp_cont_serv.nombre_em_cont_serv
	FROM an_pedido pedido
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (pedido.id_pedido = cxc_fact.numeroPedido)
		INNER JOIN cj_cc_factura_detalle_accesorios cxc_fact_acc ON(cxc_fact.idFactura = cxc_fact_acc.id_factura)
		INNER JOIN an_accesorio acc ON (cxc_fact_acc.id_accesorio = acc.id_accesorio)
		INNER JOIN an_adicionales_contrato adi_cont ON (pedido.id_pedido = adi_cont.id_pedido)
		LEFT JOIN an_empresa_cont_servicio emp_cont_serv ON (adi_cont.id_emp_cont_serv = emp_cont_serv.id_emp_cont_serv)
	WHERE pedido.id_pedido = %s;",
		valTpDato($idPedido, "int"));//echo "<pre>"; var_dump($queryAdiSQL);
	mysql_query("SET NAMES 'utf8';");
	$rsAdi = mysql_query($queryAdiSQL);
	if (!$rsAdi) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	
	$arrayCargoFinan = "";
	$arrayCreditLife = "";
	$arrayGap = "";
	$arrayContServ = "";
	$arrayHunter = "";
	$arrayProtector = "";
	while ($rowAdi = mysql_fetch_assoc($rsAdi)) {
		if ($rowAdi['id_filtro_factura'] == 6){
			$arrayGap = $rowAdi;
		}
		if ($rowAdi['id_filtro_factura'] == 7){
			$arrayCargoFinan = $rowAdi;
		}
		if ($rowAdi['id_filtro_factura'] == 8){
			$arrayContServ = $rowAdi;
		}
		if ($rowAdi['id_filtro_factura'] == 11){
			$arrayCreditLife = $rowAdi;
		}
		if ($rowAdi['id_filtro_factura'] == 12){
			$arrayHunter = $rowAdi;
		}
		if ($rowAdi['id_filtro_factura'] == 13){
			$arrayProtector = $rowAdi;
		}
		$array1 = $rowAdi;
	}
	
	
	//echo "<pre>"; var_dump($array1); var_dump($arrayProtector); exit;
	
	//***** SEGURO DEL VEHICULO*********/
	
	if ($rowCont['nombre_poliza'] != null){
		// COBERTURAS REQUERIDAS
			// 1ER DEDUCIBLE
				$posX = 168;
				$posY = 372;
				imagestring($img, 2, $posX+2, $posY-2,utf8_encode(formatoNumero($rowCont['ded_poliza'])), $textColor);
				
			// 2DO DEDUCIBLE
				$posX = 60;
				$posY = 384;
				imagestring($img, 2, $posX, $posY-2,utf8_encode(formatoNumero($rowCont['ded_poliza'])), $textColor);
			
			// COBERTURAS PROPORCIONADAS AQUI		
				$posX = 72;
				$posY = 408;
				imagestring($img, 2, $posX+2, $posY,utf8_encode(strtoupper(substr($rowCont['nombre_poliza'],0,22))), $textColor);
		
		// 1ER DEDUCIBLE LISTA - COLISION
			// MONTO
				$posY = 444;
				imagestring($img, 2, $posX, $posY-2,utf8_encode(formatoNumero($rowCont['ded_poliza'])), $textColor);
				
			// MESES		
				$posX = 144;
				imagestring($img, 2, $posX+3, $posY-2,utf8_encode(strtoupper($rowCont['meses_poliza'])), $textColor);
				
			// PRIMA		
				$posX = 204;
				imagestring($img, 2, $posX-10, $posY-2,utf8_encode(formatoNumero($rowCont['monto_seguro'])), $textColor);
		
		// 2DO DEDUCIBLE - AMPLIA
			// MONTO
				$posX = 72;
				$posY = 456;
				imagestring($img, 2, $posX, $posY-2,formatoNumero(0), $textColor);
			
			// MESES
				$posX = 144;
				imagestring($img, 2, $posX+3, $posY-2,0, $textColor);
			
			// PRIMA	
				$posX = 204;
				imagestring($img, 2, $posX-10, $posY-2,formatoNumero(0), $textColor);
	} else {
		// COBERTURAS REQUERIDAS
			// 1ER DEDUCIBLE
				$posX = 168;
				$posY = 372;
				imagestring($img, 2, $posX+2, $posY-2,utf8_encode(formatoNumero(0)), $textColor);
			
			// 2DO DEDUCIBLE
				$posX = 60;
				$posY = 384;
				imagestring($img, 2, $posX, $posY-2,utf8_encode(formatoNumero(0)), $textColor);
			
			// COBERTURAS PROPORCIONADAS AQUI
				$posX = 72;
				$posY = 408;
				imagestring($img, 2, $posX+2, $posY,utf8_encode(strtoupper("N/A")), $textColor);
		
		// 1ER DEDUCIBLE LISTA - COLISION
			// MONTO
				$posY = 444;
				imagestring($img, 2, $posX, $posY-2,formatoNumero(0), $textColor);
			
			// MESES
				$posX = 144;
				imagestring($img, 2, $posX+3, $posY-2,0, $textColor);
		
			// PRIMA
				$posX = 204;
				imagestring($img, 2, $posX-10, $posY-2,formatoNumero(0), $textColor);
		
		// 2DO DEDUCIBLE - AMPLIA
			// MONTO
				$posX = 72;
				$posY = 456;
				imagestring($img, 2, $posX, $posY-2,formatoNumero(0), $textColor);
			
			// MESES
				$posX = 144;
				imagestring($img, 2, $posX+3, $posY-2,0, $textColor);
			
			// PRIMA
				$posX = 204;
				imagestring($img, 2, $posX-10, $posY-2,formatoNumero(0), $textColor);
	}
	
	
	
		// COBERTURA DE RESPONSABILIDAD
			// LESIONES CORPORALES
				// LIMITES
					$posX = 84;
					$posY = 516;
					imagestring($img, 2, $posX-2, $posY-6,formatoNumero(0), $textColor);
				
				// MESES
					$posX = 144;
					imagestring($img, 2, $posX, $posY-6,0, $textColor);
				
				// PRIMA
					$posX = 204;
					imagestring($img, 2, $posX-10, $posY-6,formatoNumero(0), $textColor);
			
			// DANOS A LA PROPIEDAD
				// LIMITES
					$posX = 84;
					$posY = 528;
					imagestring($img, 2, $posX-2, $posY-6,formatoNumero(0), $textColor);
				
				// MESES
					$posX = 144;
					imagestring($img, 2, $posX, $posY-6,0, $textColor);
				
				// PRIMA
					$posX = 204;
					imagestring($img, 2, $posX-10, $posY-6,formatoNumero(0), $textColor);
			
		// TOTAL DE PRIMAS DEL SEGURO DEL VEHICULO
			$posY = 552;
			imagestring($img, 2, $posX-10, $posY-3,utf8_encode(formatoNumero($rowCont['monto_seguro'])), $textColor);
		
		
		
		// MAXIMO DE PAGO 1
			$posX = 24;
			$posY = 708;
			imagestring($img, 2, $posX+2, $posY+1,0, $textColor);
		
		// MAXIMO DE PAGO 2
			$posX = 96;
			$posY = 732;
			imagestring($img, 2, $posX, $posY-2,0, $textColor);
		
		// MAXIMO DE PAGO 3
			$posX = 180;
			$posY = 756;
			imagestring($img, 2, $posX+2, $posY-5,0, $textColor);
		
		// COBERTURAS DISPONIBLES
			$posX = 24;
			$posY = 780;
			imagestring($img, 2, $posX, $posY,"N/A", $textColor);
		
		// DIRECCION EN NOMBRE DE LA EMPRESA
			$posX = 72;
			$posY = 792;
			imagestring($img, 2, $posX, $posY,"N/A", $textColor);
		
		// VIDA PARA GARANTIZAR LA DEUDA
			// PLAZO
				$posX = 144;
				$posY = 828;
				imagestring($img, 2, $posX, $posY+2,0, $textColor);
			
			// PRIMA
				$posX = 204;
				imagestring($img, 2, $posX-3, $posY+2,formatoNumero(0), $textColor);
		
		// VIDA MANCOMUNADA PARA GARANTIZAR LA DEUDA
			// PLAZO
				$posX = 144;
				$posY = 852;
				imagestring($img, 2, $posX, $posY,0, $textColor);
			
			// PRIMA
				$posX = 204;
				imagestring($img, 2, $posX-3, $posY,formatoNumero(0), $textColor);
					
		// DISCAPACIDAD PARA GARANTIZAR LA DEUDA
			// PLAZO
				$posX = 144;
				$posY = 876;
				imagestring($img, 2, $posX, $posY-2,0, $textColor);
			
			// PRIMA
				$posX = 204;
				imagestring($img, 2, $posX-3, $posY-2,formatoNumero(0), $textColor);
					
		// DISCAPACIDAD PARA GARANTIZAR LA DEUDA MANCOMUANDA
			// PLAZO		
				$posX = 144;
				$posY = 900;
				imagestring($img, 2, $posX, $posY-6,0, $textColor);
			
			// PRIMA
				$posX = 204;
				$posY = 888;
				imagestring($img, 2, $posX-3, $posY+6,formatoNumero(0), $textColor);
		
		// TOTAL DE LAS PRIMAS DE SEGURO PARA GARANTIZAR LA DEUDA
			$posX = 204;
			$posY = 912;
			imagestring($img, 2, $posX-3, $posY+5,utf8_encode(formatoNumero($arrayCreditLife['precio_unitario'])), $textColor);
			
	
	// OTROS SEGUROS OPCIONALES
		// 1
			// TIPO DE COBERTURA 
				$posX = 24;
				$posY = 984;
				imagestring($img, 2, $posX, $posY,"N/A", $textColor);
			
			// PLAZO
				$posX = 144;
				imagestring($img, 2, $posX+3, $posY,0, $textColor);
			
			// PRIMA
				$posX = 204;
				imagestring($img, 2, $posX-3, $posY,formatoNumero(0), $textColor);
			
			// NOMBRE PROVEEDOR
				$posX = 84;
				$posY = 1008;
				imagestring($img, 2, $posX+2, $posY-3,"N/A", $textColor);
			
			// DIRECCION
				$posX = 48;
				$posY = 1020;
				imagestring($img, 2, $posX, $posY-3,"N/A", $textColor);
		
			
		// 2
			// TIPO DE COBERTURA
				$posX = 24;
				$posY = 1032;
				imagestring($img, 2, $posX, $posY,"N/A", $textColor);
			
			// PLAZO
				$posX = 144;
				imagestring($img, 2, $posX+3, $posY,0, $textColor);
			
			// PRIMA
				$posX = 204;
				imagestring($img, 2, $posX-3, $posY,formatoNumero(0), $textColor);
			
			// NOMBRE PROVEEDOR
				$posX = 84;
				$posY = 1056;
				imagestring($img, 2, $posX+2, $posY-6,"N/A", $textColor);
			
			// DIRECCION
				$posX = 48;
				$posY = 1068;
				imagestring($img, 2, $posX, $posY-7,"N/A", $textColor);
			
		// TOTAL DE PRIMAS DE OTROS SEGUROS OPCIONALES
			$posX = 204;
			$posY = 1080;
			imagestring($img, 2, $posX-3, $posY-3,formatoNumero(0), $textColor);
	
	
	
	//*********DECLARACIONES**********//
	
	// EN ESTA SECCION SE DEBERIA ENCONTRAR LAS DECLARACIONES QUE APARECEN AL FINAL DE LA PRIMERA HOJA DEL CONTRATO EN PDF
			
			
	//*********************************//
	
			
			
			
			
	//********* DESGLOSE DE CANTIDAD FINANCIADA ***********//
			
			// BUSCA LOS ADICIONALES INCLUIDOS EN LA FACTURA
			
	//*********** QUERY PARA TODOS LOS ACCESORIOS DEL CLIENTE POR FILTRO *****************//		
	/*$queryDet = sprintf("SELECT
		acc.id_filtro_factura,
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
		cxc_fact_det_acc.tipo_accesorio,
		cxc_fact_det_acc.id_condicion_pago AS id_condicion_pago_accesorio,
		(SELECT acc_ped.id_condicion_mostrar FROM an_accesorio_pedido acc_ped
		WHERE acc_ped.id_accesorio = cxc_fact_det_acc.id_accesorio
			AND acc_ped.id_pedido = cxc_fact.numeroPedido) AS id_condicion_mostrar_accesorio
	FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
	WHERE cxc_fact_det_acc.id_factura = %s 
		AND (acc.id_filtro_factura NOT IN (6,7,11) OR acc.id_filtro_factura IS NULL);",
		valTpDato($idDocumento, "int"));*/
			
			
			
	// SOLO PARA CONTRATO DE SERVICIO
	$queryDet = sprintf("SELECT
		acc.id_filtro_factura,
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
		cxc_fact_det_acc.tipo_accesorio,
		cxc_fact_det_acc.id_condicion_pago AS id_condicion_pago_accesorio,
		(SELECT acc_ped.id_condicion_mostrar FROM an_accesorio_pedido acc_ped
		WHERE acc_ped.id_accesorio = cxc_fact_det_acc.id_accesorio
			AND acc_ped.id_pedido = cxc_fact.numeroPedido) AS id_condicion_mostrar_accesorio
	FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
	WHERE cxc_fact_det_acc.id_factura = %s;",
		valTpDato($idDocumento, "int"));
	$rsDet = mysql_query($queryDet);
	if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);//die(print_r($queryDet,true));
	
	$montoPrecioVenta = 0;
	$arrayAdiContrato = array();
	$totalContrato = 0;
	$arrayContrato = array();
	$arrayAdicionales = array();
	$arrayAdicionalesPagados = array();
	$totalAdicionales = 0;
	$totalAdicionalesPagados = 0;
			
	//************ FILTRO POR CREDITO Y CONTADO Y POR TIPO DE ACCESORIO **************//
	while ($rowDet = mysql_fetch_array($rsDet)) {
		if ($rowDet['id_tipo_accesorio'] == 1) { // 1 = Adicionales
			if ($rowDet['id_condicion_pago_accesorio'] == 1) { // 1 = Pagado, 2 = Financiado
				$totalAdicionalesPagados += $rowDet['precio_unitario'];
				$arrayAdicionalesPagados[] = array(
					"nom_accesorio" => $rowDet['nom_accesorio'],
					"precio_unitario" => $rowDet['precio_unitario']);
			}else if ($rowDet['id_condicion_pago_accesorio'] != 1) { // Null = Individual, 1 = En Precio de Venta
				$totalAdicionales += $rowDet['precio_unitario'];
				$arrayAdicionales[] = array(
					"nom_accesorio" => $rowDet['nom_accesorio'],
					"precio_unitario" => $rowDet['precio_unitario']);
			}
		} else if ($rowDet ['id_tipo_accesorio'] == 3 && $rowDet ['id_filtro_factura'] == 8 ) { // 3 = Contratos y solo contrato de servivio
			$totalContrato += $rowDet['precio_unitario'];
			$arrayAdiContrato[] = array(
				"nom_accesorio" => $rowDet['nom_accesorio'],
				"precio_unitario" => $rowDet['precio_unitario']);
		}
	}
	
	// Campo Pagado por: adicionales en el contrato
	foreach( $arrayAdiContrato as $key => $i) {
		if ($i['nom_accesorio'] = $arrayContServ['nom_accesorio']) {
			$arrayAdiContrato[$key]['nombre_em_cont_serv'] = $arrayContServ['nombre_em_cont_serv'];
		} 
		//Si llegase a necesitar la asignacion de la empresa de los otros adicionales tipo contrato
		//if ($i['nom_accesorio'] = $arrayGap['nom_accesorio']){
			//$empGap= $arrayGap['nombre_em_gap'];
		//} else {
			//$empGap = "";
		//}
	}
	
	// AGREGANDO EL BANCO Y EL TRADE IN CUANDO ES NEGATIVO
	$indice = count($arrayAdiContrato)+1;
	//echo "<pre>"; print_r($indice);
	
	if ($rowTradein['payoff'] > $rowTradein['allowance']) {
		$arrayAdiContrato[$indice]['nombre_em_cont_serv'] = $rowTradein['nombre_cliente_adeudado'];
		$arrayAdiContrato[$indice]['nom_accesorio'] = "NEGATIVE EQUITY";
		$arrayAdiContrato[$indice]['precio_unitario'] = $rowTradein['payoff'] - $rowTradein['allowance'];				
	}
	//echo "<pre>"; print_r($arrayAdiContrato);
	
	// BUSCANDO VALORES DE LOS BONOS EN EFECTIVO
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
		) AS pagos_anticipo,
		
		
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
	
	$totContado = $rowPagos['pagos_contado'] + $rowPagos['pagos_anticipo'] + $rowPagos['pagos_bono'] - $totalAdicionalesPagados;
	//print_r($rowPagos['pagos_contado']." ".$rowPagos['pagos_anticipo']." ".$rowPagos['pagos_bono']." ".$totalAdicionalesPagados);exit;
	
	// 1. PRECIO CONTADO
		$precioTotContado = $rowUnidad['precio_unitario'];
	
		$posX = 552;
		$NposX = prtDI(formatoNumero($precioTotContado), $posX);
		$posY = 588;
		imagestring($img, 2, $NposX, $posY-1,utf8_encode(formatoNumero($precioTotContado)), $textColor);
	
	// 2.
		if ($arrayHunter != null) {
			// DESCRIPCION	
				$posX = 288;
				$posY += 12;
				imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayHunter["nom_accesorio"])), $textColor);
			
			// MONTO
				$posX = 552;
				$NposX = prtDI(formatoNumero($arrayHunter["precio_unitario"]), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero($arrayHunter["precio_unitario"]), $textColor);
		} else {
			// MONTO
				$posX = 552;
				$posY += 12;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);			
		}
		/*if ($arrayAdiContado[0] != null)	{
			// DESCRIPCION		
				$posX = 288;
				$posY += 12;
				imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayAdiContado[0]["nom_accesorio"])), $textColor);
		
			// MONTO
				$posX = 552;
				$NposX = prtDI(formatoNumero($arrayAdiContado[0]["precio_unitario"]), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero($arrayAdiContado[0]["precio_unitario"]), $textColor);
		} else {
			// MONTO
				$posX = 552;
				$posY += 12;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);			
		}*/
	
	// 3.
		if ($arrayProtector != null)	{	
			// DESCRIPCION			
				$posX = 288;
				$posY += 12;
				imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayProtector["nom_accesorio"])), $textColor);
			
			// MONTO
				$posX = 552;
				$NposX = prtDI(formatoNumero($arrayProtector["precio_unitario"]), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero($arrayProtector["precio_unitario"]), $textColor);
		} else {
			// MONTO
				$posX = 552;
				$posY += 12;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);			
		}
		/*if ($arrayAdiContado[1] != null)	{				
			$posX = 288;
			$posY += 12;
			imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayAdiContado[1]["nom_accesorio"])), $textColor);
			
			// MONTO
				$posX = 552;
				$NposX = prtDI(formatoNumero($arrayAdiContado[1]["precio_unitario"]), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero($arrayAdiContado[1]["precio_unitario"]), $textColor);
		} else {
			// MONTO
				$posX = 552;
				$posY += 12;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);			
		}*/
	
	// 4.
		//if ($arrayAdiContado[2] != null) {
			// DESCRIPCION
				//$posX = 288;
				//$posY += 12;
				//imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayAdiContado[2]["nom_accesorio"])), $textColor);
			
			// MONTO
				//$posX = 552;
				//$NposX = prtDI(formatoNumero($arrayAdiContado[2]["precio_unitario"]), $posX);
				//imagestring($img, 2, $NposX, $posY,formatoNumero($arrayAdiContado[2]["precio_unitario"]), $textColor);		
		//} else {
			// MONTO
				$posX = 552;
				$posY += 12;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);			
		//}
	
	// 5.
		//if ($arrayAdiContado[3] != null) {
			// DESCRIPCION
				//$posX = 288;
				//$posY += 12;
				//imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayAdiContado[3]["nom_accesorio"])), $textColor);
			
			// MONTO
				//$posX = 552;
				//$NposX = prtDI(formatoNumero($arrayAdiContado[3]["precio_unitario"]), $posX);
				//imagestring($img, 2, $NposX, $posY,formatoNumero($arrayAdiContado[3]["precio_unitario"]), $textColor);
		//} else {
			// MONTO
				$posX = 552;
				$posY += 12;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);			
		//}	
	
	// 6.
		//if ($arrayAdiContado[4] != null) { 	
			// DESCRIPCION
				//$posX = 288;
				//$posY += 12;
				//imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayAdiContado[4]["nom_accesorio"])), $textColor);
			
			// MONTO
				//$posX = 552;
				//$NposX = prtDI(formatoNumero($arrayAdiContado[4]["precio_unitario"]), $posX);
				//imagestring($img, 2, $NposX, $posY,formatoNumero($arrayAdiContado[4]["precio_unitario"]), $textColor);
		//} else {
			// MONTO
				$posX = 552;
				$posY += 12;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);			
		//}
		
	// 7. SUBTOTAL 1+2+3+4+5+6 
	// MONTO
		if (isset($rowUnidad['precio_unitario'])) {
			$totalAdiContado = $precioTotContado+ $arrayHunter['precio_unitario'] + $arrayProtector['precio_unitario'];
			$posX = 576;
			$posY += 12;
			$NposX = prtDI(formatoNumero($totalAdiContado), $posX);
			imagestring($img, 2, $NposX, $posY,formatoNumero($totalAdiContado), $textColor);
		} else {
			$posX = 576;
			$posY += 12;
			$NposX = prtDI(formatoNumero(0), $posX);
			imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);
		}
	
	// TRADE IN - MOSTRANDO VALORES 		
	// 8. PAGO INICIAL
		if ($cantidadTradein) {
			// A. AUTO TOMADO EN CUENTA (VALOR BRUTO) - ALLOWANCE
				$posX = 552;
				$posY += 24;
				$NposX = prtDI(formatoNumero($rowTradein['allowance']), $posX);
				imagestring($img, 2, $NposX, $posY-2,formatoNumero($rowTradein['allowance']), $textColor);
				
			// B. MENOS LIQUIDACION DE ENTREGA A CUENTA PAGADA A - PAYOFF
				$posY += 12;
				$NposX = prtDI(formatoNumero($rowTradein['payoff']), $posX);
				imagestring($img, 2, $NposX, $posY-2,formatoNumero($rowTradein['payoff']), $textColor);
			
			// C. BONIFICACION NETA POR ENTREGA A CUENTA (A-B) - CREDITO NETO
				if ($rowTradein['payoff'] > $rowTradein['allowance']) {
					$sign ="-";
					$sumAB = $rowTradein['payoff'] - $rowTradein['allowance'];
				} else {
					$sign ="";
					$sumAB = $rowTradein['allowance'] - $rowTradein['payoff'];
				}
				
				$posY += 12;
				$NposX = prtDI($sign.formatoNumero($sumAB), $posX);
				imagestring($img, 2, $NposX, $posY-2,$sign.formatoNumero($sumAB), $textColor);
		} else {
			// A. AUTO TOMADO EN CUENTA (VALOR BRUTO) - ALLOWANCE
				$posX = 552;
				$posY += 24;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY-2,formatoNumero(0), $textColor);
			
			// B. MENOS LIQUIDACION DE ENTREGA A CUENTA PAGADA A - PAYOFF
				$posY += 12;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY-2,formatoNumero(0), $textColor);
			
			// C. BONIFICACION NETA POR ENTREGA A CUENTA (A-B) - CREDITO NETO
				$posY += 12;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY-2,formatoNumero(0), $textColor);
		}
		
		// D. PAGO INICIAL AL CONTADO AL CIERRE - CONTADOS + ANTICIPOS (efectivo, tdd, tdc, cheques, etc..)
			if ($rowPagos['pagos_contado'] != null || $rowPagos['pagos_anticipo'] != null || $rowPagos['pagos_bono']) {	
				$posY = 720;
				$NposX = prtDI(formatoNumero($totContado), $posX);
				imagestring($img, 2, $NposX, $posY-3,formatoNumero($totContado), $textColor);
			} else {
				$posY = 720;
				$NposX = prtDI(formatoNumero(50), $posX);
				imagestring($img, 2, $NposX, $posY-3,formatoNumero(0), $textColor);
			}
		
		// E. TIPO DE REEMBOLSO (SI CORRESPONDE) - PND's
			if ($rowPagos['pagos_pnd'] != null) { 		 
				$posY = 732;
				$NposX = prtDI(formatoNumero($rowPagos['pagos_pnd']), $posX);
				imagestring($img, 2, $NposX, $posY-3,formatoNumero($rowPagos['pagos_pnd']), $textColor);	 		
			} else {
				$posY = 732;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY-3,formatoNumero(0), $textColor);
			}
		
		// E
			$posY = 744;
			$NposX = prtDI(formatoNumero(0), $posX);
			imagestring($img, 2, $NposX, $posY-3,formatoNumero(0), $textColor);	 	
				
		// F. PAGO INICIAL DIFERIDO	
			if ($totContado != null || $rowPagos['pagos_pnd'] != null){
				$totDEF = $totContado+$rowPagos['pagos_pnd'];
				$posY = 756;
				$NposX = prtDI(formatoNumero($totDEF), $posX);
				imagestring($img, 2, $NposX, $posY-3,formatoNumero($totDEF), $textColor);
			} else {
				$posY = 756;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY-3,formatoNumero(0), $textColor);
			}
			
		// PAGO INICIAL TOTAL (C+G)	
			if ($totDEF != null || $sumAB != null) {
				// MONTO
					if ($sign == '-'){
					  $sumAB = 0;
					}
		
					$totContadoFinal = $totDEF+$sumAB;
		
					$posY = 768;
					$posX = 576;
					$NposX = prtDI(formatoNumero($totContadoFinal), $posX);
					imagestring($img, 2, $NposX, $posY-3,formatoNumero($totContadoFinal), $textColor);	 		
			} else {
				// MONTO
					$posY = 768;
					$posX = 576;
					$NposX = prtDI(formatoNumero(0), $posX);
					imagestring($img, 2, $NposX, $posY-3,formatoNumero(0), $textColor);
			}
	
	// 9. SALDO A FAVOR DEL VENDEDOR POR LOS BIENES Y SERVICIOSSSSS ARRIBA INDICADOS -ACUMULADO HASTA AHORA
		if ($totContadoFinal != null || $totalAdiContado != null) {
			$saldAFavor1 = $totalAdiContado-$totContadoFinal; //AQUI
			
			$posY = 780;
			$posX = 576;
			$NposX = prtDI(formatoNumero($saldAFavor1), $posX);
			imagestring($img, 2, $NposX, $posY+2,formatoNumero($saldAFavor1), $textColor);
		} else {
			$posY = 780;
			$posX = 576;
			$NposX = prtDI(formatoNumero(0), $posX);
			imagestring($img, 2, $NposX, $posY+2,formatoNumero(0), $textColor);
		}
	
	// 10. CANTIDADES PAGADAS A OTROS EN SU NOMBRE 	
		$totalOtrosCargos = 0;
	
		// A. LICENCIA
			$posY = 816;
			$posX = 552;
			$NposX = prtDI(formatoNumero(0), $posX);
			imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);
		
		// B. REGISTRO
			$posY = 828;
			$NposX = prtDI(formatoNumero(0), $posX);
			imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);
		
		// C. CERTIFICADO DE TITULO DE PROPIEDAD
			$posY = 840;
			$NposX = prtDI(formatoNumero(0), $posX);
			imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);
		
		// D. DECLARACION DE FINANCIAMIENTO
			$posY = 864;
			$NposX = prtDI(formatoNumero(0), $posX);
			imagestring($img, 2, $NposX, $posY-2,formatoNumero(0), $textColor);
		
		// E. OTROS IMPUESTOS
			// DESCRIPCION
				//$posX = 360;
				//$posY = 876;
				//imagestring($img, 2, $posX, $posY-3,"DESCRIPCION E", $textColor);
			
			// MONTO
				//$posX = 552;
				//$NposX = prtDI(formatoNumero(0), $posX);
				//imagestring($img, 2, $NposX, $posY-3,formatoNumero(0), $textColor);			 	
			
		// F. 
			// DESCRIPCION
				//$posX = 300;
				//$posY = 888;
				//imagestring($img, 2, $posX, $posY-3,"DESCRIPCION F", $textColor);
			
			// MONTO
				//$posX = 552;
				//$NposX = prtDI(formatoNumero(0), $posX);
				//imagestring($img, 2, $NposX, $posY-3,formatoNumero(0), $textColor);		
			
		// G.
			// DESCRIPCION
				//$posX = 300;
				//$posY = 900;
				//imagestring($img, 2, $posX, $posY-3,"DESCRIPCION G", $textColor);
			
			// MONTO
				//$posX = 552;
				//$NposX = prtDI(formatoNumero(0), $posX);
				//imagestring($img, 2, $NposX, $posY-3,formatoNumero(0), $textColor);
		
		// H. TOTAL DEL SEGURO DEL VEHICULO
			if ($rowCont['nombre_poliza'] != null) {	
				$posX = 552; 		
				$posY = 924;
				$totalOtrosCargos+=$rowCont['monto_seguro'];
				$NposX = prtDI(formatoNumero($rowCont['monto_seguro']), $posX);
				imagestring($img, 2, $NposX, $posY+2,utf8_encode(formatoNumero($rowCont['monto_seguro'])), $textColor);
			} else {
				$posX = 552;
				$posY = 924;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY+2,utf8_encode(formatoNumero(0)), $textColor);		 	
			}
			
		// I. TOTAL DEL SEGURO PARA GARANTIZAR LA DEUDA
			if ($arrayCreditLife['nom_accesorio'] != null) {
				$posY = 936;
				$totalOtrosCargos+=$arrayCreditLife['precio_unitario'];
				$NposX = prtDI(utf8_encode(formatoNumero($arrayCreditLife['precio_unitario'])), $posX);
				imagestring($img, 2, $NposX, $posY+3,utf8_encode(formatoNumero($arrayCreditLife['precio_unitario'])), $textColor);
			} else {
				$posY = 936;
				$NposX = prtDI(utf8_encode(formatoNumero(0)), $posX);
				imagestring($img, 2, $NposX, $posY+3,utf8_encode(formatoNumero(0)), $textColor);
			}
			
		// J. TOTAL DE OTROS SEGUROS OPCIONALES
			$posY = 948;
			$NposX = prtDI(formatoNumero(0), $posX);
			imagestring($img, 2, $NposX, $posY+2,formatoNumero(0), $textColor);
		
		// K. - DESGLOSE GAP
			if(isset($arrayGap)){
				// DESCRIPCION
					$posX = 300;
					$posY = 960;
					imagestring($img, 2, $posX, $posY+2,utf8_encode(strtoupper($arrayGap['nom_accesorio'])), $textColor);
				
				// MONTO
					$posX = 552;
					$totalOtrosCargos+=$arrayGap['precio_unitario'];
					$NposX = prtDI(formatoNumero($arrayGap['precio_unitario']), $posX);
					imagestring($img, 2, $NposX, $posY+2,utf8_encode(formatoNumero($arrayGap['precio_unitario'])), $textColor);
			} else {
				// DESCRIPCION
					$posX = 300;
					$posY = 960;
					imagestring($img, 2, $posX, $posY+2,"N/A", $textColor);
					
				// MONTO
					$posX = 552;
					$NposX = prtDI(formatoNumero(0), $posX);
					imagestring($img, 2, $NposX, $posY+2,formatoNumero(0), $textColor);
			}
		
		// L. 
			//if ($arrayAdiContrato[0] != null){
				// PAGADO A
					$posX = 336;
					$posY = 984;
					//if(isset($arrayAdiContrato[0]['nombre_em_cont_serv'])){
					//imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayAdiContrato[0]["nombre_em_cont_serv"])), $textColor);
						imagestring($img, 2, $posX, $posY,"ASSURANT", $textColor); // A Peticion siempre sera ASSURANT 
					//} else {
						//imagestring($img, 2, $posX, $posY,"", $textColor);
					//}
				
				// POR
					$posX = 312;
					$posY = 996;
					//imagestring($img, 2, $posX, $posY+2,utf8_encode(strtoupper($arrayAdiContrato[0]["nom_accesorio"])), $textColor);
					imagestring($img, 2, $posX, $posY+2,utf8_encode("CONT. SERVICIO"), $textColor);	
			
				// MONTO
					$posX = 552;
					$totalOtrosCargos+=$arrayAdiContrato[0]["precio_unitario"];
					$NposX = prtDI(formatoNumero($arrayAdiContrato[0]["precio_unitario"]), $posX);
					imagestring($img, 2, $NposX, $posY+2,utf8_encode(formatoNumero($arrayAdiContrato[0]["precio_unitario"])), $textColor);		 	 
			 
			//} else {
				// PAGADO A
					//$posX = 336;
					//$posY = 984;
					//imagestring($img, 2, $posX, $posY,"N/A", $textColor);
					
				// POR
					//$posX = 312;
					//$posY = 996;
					//imagestring($img, 2, $posX, $posY+2,"N/A", $textColor);
					
				// MONTO
					//$posX = 552;
					//NposX = prtDI(formatoNumero(0), $posX);
					//imagestring($img, 2, $NposX, $posY+2,utf8_encode(formatoNumero(0)), $textColor);
			//}
		
		// M.
			if ($arrayAdiContrato[1] != null) {
				// PAGADO A
					$posX = 336;
					$posY = 1008;
					if(isset($arrayAdiContrato[1]['nombre_em_cont_serv'])){
						imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayAdiContrato[1]["nombre_em_cont_serv"])), $textColor);
					} else {
						imagestring($img, 2, $posX, $posY,"", $textColor);
					}
			
				// POR
					$posX = 312;
					$posY = 1020;
					imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayAdiContrato[1]["nom_accesorio"])), $textColor);
				
				// MONTO
					$posX = 552;
					$totalOtrosCargos+=$arrayAdiContrato[1]["precio_unitario"];
					$NposX = prtDI(formatoNumero($arrayAdiContrato[1]["precio_unitario"]), $posX);
					imagestring($img, 2, $NposX, $posY,utf8_encode(formatoNumero($arrayAdiContrato[1]["precio_unitario"])), $textColor);
			 } else {
				// PAGADO A
					$posX = 336;
					$posY = 1008;
					imagestring($img, 2, $posX, $posY,"N/A", $textColor);
					
				// POR
					$posX = 312;
					$posY = 1020;
					imagestring($img, 2, $posX, $posY,"N/A", $textColor);
					
				// MONTO
					$posX = 552;
					$NposX = prtDI(formatoNumero(0), $posX);
					imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);
			 }
		
		// N.
			if ($arrayAdiContrato[2] != null){
				// PAGADO A
					$posX = 336;
					$posY = 1032;
					if(isset($arrayAdiContrato[2]['nombre_em_cont_serv'])){
						imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayAdiContrato[2]["nombre_em_cont_serv"])), $textColor);
					} else {
						imagestring($img, 2, $posX, $posY,"", $textColor);
					}
				
				// POR
					$posX = 312;
					$posY = 1044;
					imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayAdiContrato[2]["nom_accesorio"])), $textColor);
				
				// MONTO
					$posX = 552;
					$totalOtrosCargos+=$arrayAdiContrato[2]["precio_unitario"];
					$NposX = prtDI(formatoNumero($arrayAdiContrato[2]["precio_unitario"]), $posX);
					imagestring($img, 2, $NposX, $posY,utf8_encode(formatoNumero($arrayAdiContrato[2]["precio_unitario"])), $textColor);
			 } else {
				// PAGADO A
					$posX = 336;
					$posY = 1032;
					imagestring($img, 2, $posX, $posY,"N/A", $textColor);
					
				// POR
					$posX = 312;
					$posY = 1044;
					imagestring($img, 2, $posX, $posY,"N/A", $textColor);
					
				// MONTO
					$posX = 552;
					$NposX = prtDI(formatoNumero(0), $posX);
					imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);		 	 	
			 }
		
		// O.
			if ($arrayAdiContrato[3] != null){
				// PAGADO A
					$posX = 336;
					$posY = 1056;
					if(isset($arrayAdiContrato[3]['nombre_em_cont_serv'])){
						imagestring($img, 2, $posX, $posY-1,utf8_encode(strtoupper($arrayAdiContrato[3]["nombre_em_cont_serv"])), $textColor);
					} else {
						imagestring($img, 2, $posX, $posY-1,"", $textColor);
					}
				
				// POR
					$posX = 312;
					$posY = 1068;
					imagestring($img, 2, $posX, $posY-1,utf8_encode(strtoupper($arrayAdiContrato[3]["nom_accesorio"])), $textColor);
				
				// MONTO
					$posX = 552;
					$totalOtrosCargos+=$arrayAdiContrato[3]["precio_unitario"];
					$NposX = prtDI(formatoNumero($arrayAdiContrato[3]["precio_unitario"]), $posX);
					imagestring($img, 2, $NposX, $posY-1,utf8_encode(formatoNumero($arrayAdiContrato[3]["precio_unitario"])), $textColor);
			 } else {
				// PAGADO A
					$posX = 336;
					$posY = 1056;
					imagestring($img, 2, $posX, $posY-1,"N/A", $textColor);
					
				// POR
					$posX = 312;
					$posY = 1068;
					imagestring($img, 2, $posX, $posY-1,"N/A", $textColor);
					
				// MONTO
					$posX = 552;
					$NposX = prtDI(formatoNumero(0), $posX);
					imagestring($img, 2, $NposX, $posY-1,formatoNumero(0), $textColor);		 	 	
			 }
		
		// P.
			if ($arrayAdiContrato[4] != null){
				// PAGADO A
					$posX = 336;
					$posY = 1080;
					if(isset($arrayAdiContrato[4]['nombre_em_cont_serv'])){
						imagestring($img, 2, $posX, $posY-2,utf8_encode(strtoupper($arrayAdiContrato[4]["nombre_em_cont_serv"])), $textColor);
					} else {
						imagestring($img, 2, $posX, $posY-2,"", $textColor);
					}
				
				// POR
					$posX = 312;
					$posY = 1092;
					imagestring($img, 2, $posX, $posY-2,utf8_encode(strtoupper($arrayAdiContrato[4]["nom_accesorio"])), $textColor);
				
				// MONTO
					$posX = 552;
					$totalOtrosCargos+=$arrayAdiContrato[4]["precio_unitario"];
					$NposX = prtDI(formatoNumero($arrayAdiContrato[4]["precio_unitario"]), $posX);
					imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero($arrayAdiContrato[4]["precio_unitario"])), $textColor);
			 } else {
				// PAGADO A
					$posX = 336;
					$posY = 1080;
					imagestring($img, 2, $posX, $posY-2,"N/A", $textColor);
					
				// POR
					$posX = 312;
					$posY = 1092;
					imagestring($img, 2, $posX, $posY-2,"N/A", $textColor);
					
				// MONTO
					$posX = 552;
					$NposX = prtDI(formatoNumero(0), $posX);
					imagestring($img, 2, $NposX, $posY-2,formatoNumero(0), $textColor);
			 }
		 
		// TOTAL DE OTROS CARGOS (10A+...+P)
			$posX = 576;
			$posY = 1104;
			$NposX = prtDI(formatoNumero($totalOtrosCargos), $posX);
			imagestring($img, 2, $NposX, $posY-4,utf8_encode(formatoNumero($totalOtrosCargos)), $textColor);
									
	// 11. CANTIDAD FINANCIADA (9+10)
		$cantidadFinanciada = $saldAFavor1 + $totalOtrosCargos;
		
		$posX = 576;
		$posY = 1116;
		$NposX = prtDI(formatoNumero($cantidadFinanciada), $posX);
		imagestring($img, 2, $NposX, $posY-4,utf8_encode(formatoNumero($cantidadFinanciada)), $textColor);
	
	
	
	//************** DECLARACIONES INFORMATIVAS SOBRE EL CREDITO - (AQUI SE ENCUENTRAN LAS DECLARACIONES, POR MOTIVO DE CUENTAS) ****************//		
	// TASA PORCENTUAL ANUAL
		$posX = 504;
		$posY = 298;
		imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($rowCont['interes_cuota_financiar'])), $textColor);
	
	// CARGO POR FINANCIAMIENTO
		$posX = 528;
		$posY += 24;
		imagestring($img, 2, $posX, $posY,utf8_encode(formatoNumero($arrayCargoFinan['precio_unitario'])), $textColor);
	
	// CANTIDAD FINANCIADA
		$posY += 24;
		imagestring($img, 2, $posX, $posY,utf8_encode(formatoNumero($cantidadFinanciada)), $textColor);
	
	// TOTAL DE PAGOS
		$totalPago = $cantidadFinanciada + $arrayCargoFinan['precio_unitario'];
		$posY += 24;
		imagestring($img, 2, $posX, $posY,utf8_encode(formatoNumero($totalPago)), $textColor);
				
	// PRECIO TOTAL DE VENTA
		// PAGO INICIAL
			$precioTotalVenta = $totalPago + $totContadoFinal;
			
			$posX = 432;
			$posY += 24;
			imagestring($img, 2, $posX, $posY,utf8_encode(formatoNumero($totContadoFinal)), $textColor);
			
			$posX = 528;
			imagestring($img, 2, $posX, $posY,utf8_encode(formatoNumero($precioTotalVenta)), $textColor);
				
		// PLAN DE PAGOS
			//$posX = 444;
			//$posY = 408;
			//imagestring($img, 2, $posX, $posY+2,"", $textColor);
				
		// 1
		if ($rowCont['meses_financiar'] > 0){
			// NUMERO DE PAGO
				$posX = 276;
				$posY += 51;
				imagestring($img, 2, $posX, $posY,utf8_encode($rowCont['meses_financiar']), $textColor);
			
			// IMPORTE DE CADA PAGO
				$posX = 360;
				imagestring($img, 2, $posX, $posY,utf8_encode(formatoNumero($rowCont['cuotas_financiar'])), $textColor);
			
			//FECHA DE PAGO
				$posX = 456;
				($rowCont['fecha_pago_cuotaa'] != null) ? imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($rowCont['fecha_pago_cuotaa'])), $textColor) : '';
		}
		
		// 2
		if ($rowCont['meses_financiar2'] > 0){				 	
			// NUMERO DE PAGO
				$posX = 276;
				$posY += 14;
				imagestring($img, 2, $posX, $posY,utf8_encode($rowCont['meses_financiar2']), $textColor);
			
			// IMPORTE DE CADA PAGO
				$posX = 360;
				imagestring($img, 2, $posX, $posY,utf8_encode(formatoNumero($rowCont['cuotas_financiar2'])), $textColor);
			
			//FECHA DE PAGO
				$posX = 528;
				($rowCont['fecha_pago_cuotaa'] != null) ? imagestring($img, 2, $posX-5, $posY,utf8_encode(strtoupper($rowCont['fecha_pago_cuotaa2'])), $textColor) : '';
		}
		
		// 3
		if ($rowCont['meses_financiar3'] > 0){	
			// NUMERO DE PAGO
				$posX = 276;
				$posY += 14;
				imagestring($img, 2, $posX, $posY,utf8_encode($rowCont['meses_financiar3']), $textColor);
			
			// IMPORTE DE CADA PAGO
				$posX = 360;
				imagestring($img, 2, $posX, $posY,utf8_encode(formatoNumero($rowCont['cuotas_financiar3'])), $textColor);
			
			// FECHA DE PAGO
				$posX = 528;
				($rowCont['fecha_pago_cuotaa'] != null) ? imagestring($img, 2, $posX-5, $posY,utf8_encode(strtoupper($rowCont['fecha_pago_cuotaa3'])), $textColor) : '';
		}
		
		// 4
		if ($rowCont['meses_financiar4'] > 0){			 		
			// NUMERO DE PAGO
				$posX = 276;
				$posY += 14;
				//imagestring($img, 2, $posX, $posY-1,utf8_encode($rowCont['meses_financiar4']), $textColor);
			
			// IMPORTE DE CADA PAGO
				$posX = 360;
				imagestring($img, 2, $posX, $posY,utf8_encode(formatoNumero($rowCont['cuotas_financiar4'])), $textColor);
			
			// FECHA DE PAGO
				$posX = 456;
				($rowCont['fecha_pago_cuotaa'] != null) ? imagestring($img, 2, $posX+2, $posY,utf8_encode(strtoupper($rowCont['fecha_pago_cuotaa4'])), $textColor) : '';			 	
		} 	
					
					
	//************************************************************************************************************//
	
	////////////// GUARDANDO IMAGENES EN ARRAY //////////////////////
	$arrayImg[] = "tmp/"."contrato_venta".$pageNum.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
	
	
	
	//************CREANDO IMAGEN POR PAGINA 2*****************//
		$img = @imagecreate(612, 1812) or die("No se puede crear la imagen");
		$pageNum = 2; //pagina 2 del documento
		
		// ESTABLECIENDO LOS COLORES DE LA PALETA
			$backgroundColor = imagecolorallocate($img, 255, 255, 255);
			$textColor = imagecolorallocate($img, 0, 0, 0);
			$textColor2 = imagecolorallocate($img, 255, 0, 0);
		
	$monto = 2000;
	$posX = 84; 
	$posY = 72;
	//*********GAP*************//
	if ($arrayGap) {
		//NOMBRE PROVEEDOR
			$posX = 168;
			$posY = 36;
			imagestring($img, 2, $posX-4, $posY,utf8_encode(strtoupper(substr($rowCont['nombre_gap'],0,9))), $textColor);
		
		//DOMICILIO PROVEEDOR
			$posX = 120;
			$posY = 48;
			imagestring($img, 2, $posX-3, $posY-1,utf8_encode(strtoupper(substr($rowCont['direccion_agencia'],0,17))), $textColor);
		
		//PLAZO EN MESES
			$posX = 72;
			$posY = 84;
			imagestring($img, 2, $posX, $posY+9,utf8_encode(strtoupper(substr($arrayGap['per_gap'],0,17))), $textColor);
		
		//COSTO
			$posX = 180;
			imagestring($img, 2, $posX+6, $posY+9,utf8_encode(formatoNumero($arrayGap['precio_unitario'])), $textColor);
	} else {
		//NOMBRE PROVEEDOR
			$posX = 168;
			$posY = 36;
			imagestring($img, 2, $posX-4, $posY,utf8_encode("N/A"), $textColor);
		
		//DOMICILIO PROVEEDOR
			$posX = 120;
			$posY = 48;
			imagestring($img, 2, $posX-3, $posY-1,utf8_encode("N/A"), $textColor);
		
		//PLAZO EN MESES
			$posX = 72;
			$posY = 84;
			imagestring($img, 2, $posX, $posY+9,utf8_encode("N/A"), $textColor);
		
		//COSTO
			$posX = 180;
			imagestring($img, 2, $posX+6, $posY+9,utf8_encode("N/A"), $textColor);
	}
	
	
	
	//********CONTRATO DE SERVICIO, MANTENIMIENTO Y OTROS *********************//	
		// 1.
			// DESCRIPCION
				$posX = 288;
				$posY = 48;
				imagestring($img, 2, $posX, $posY,"CONT. SERVIC", $textColor);
	
		if ($arrayContServ['nom_accesorio'] != null){
			// DEDUCIBLE
				$posX = 420;
				$NposX = prtDI(substr($arrayContServ['ded_cont_serv'],0,-3), $posX);
				imagestring($img, 2, $NposX, $posY,utf8_encode($arrayContServ['ded_cont_serv']), $textColor);
		
			// PLAZO
				$posX = 480;
				imagestring($img, 2, $posX, $posY,utf8_encode($arrayContServ['per_cont_serv']), $textColor);
		
			// COSTO
				$posX = 540;
				imagestring($img, 2, $posX+8, $posY,utf8_encode(substr($arrayContServ['precio_unitario'],0,-3)), $textColor);		
		} else {		
			// DEDUCIBLE
				$posX = 420;
				$NposX = prtDI(formatoNumero(0), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(0), $textColor);
			
			// PLAZO
				$posX = 480;
				imagestring($img, 2, $posX, $posY,0, $textColor);
			
			// COSTO
				$posX = 540;
				imagestring($img, 2, $posX, $posY,formatoNumero(0), $textColor);
		}
		
	/*
		// 2.
			// DESCRIPCION
				$posX = 288;
				$posY = 60;
				imagestring($img, 2, $posX, $posY,"CONT. SERVICIO", $textColor);
				
			// DEDUCIBLE
				$posX = 420;
				$NposX = prtDI(formatoNumero(50), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(50), $textColor);
		
			// PLAZO
				$posX = 480;
				imagestring($img, 2, $posX, $posY,12, $textColor);
		
			// COSTO
				$posX = 540;
				imagestring($img, 2, $posX, $posY,formatoNumero(4000), $textColor);	
		
		// 3.
			// DESCRIPCION
				$posX = 288;
				$posY = 72;
				imagestring($img, 2, $posX, $posY,"CONT. SERVICIO", $textColor);
				
			// DEDUCIBLE
				$posX = 420;
				$NposX = prtDI(formatoNumero(50), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(50), $textColor);
		
			// PLAZO
				$posX = 480;
				imagestring($img, 2, $posX, $posY,12, $textColor);
		
			// COSTO
				$posX = 540;
				imagestring($img, 2, $posX, $posY,formatoNumero(4000), $textColor);	
		
		// 4.
			// DESCRIPCION
				$posX = 288;
				$posY = 84;
				imagestring($img, 2, $posX, "CONT. SERVICIO", $textColor);
				
			// DEDUCIBLE
				$posX = 420;
				$NposX = prtDI(formatoNumero(50), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(50), $textColor);
		
			// PLAZO
				$posX = 480;
				imagestring($img, 2, $posX, $posY,12, $textColor);
		
			// COSTO
				$posX = 540;
				imagestring($img, 2, $posX, $posY,formatoNumero(4000), $textColor);	
								
		// 5.
			// DESCRIPCION
				$posX = 288;
				$posY = 96;
				imagestring($img, 2, $posX, $posY,"CONT. SERVICIO", $textColor);
				
			// DEDUCIBLE
				$posX = 420;
				$NposX = prtDI(formatoNumero(50), $posX);
				imagestring($img, 2, $NposX, $posY,formatoNumero(50), $textColor);
		
			// PLAZO
				$posX = 480;
				imagestring($img, 2, $posX, $posY,12, $textColor);
		
			// COSTO
				$posX = 540;
				imagestring($img, 2, $posX, $posY,formatoNumero(4000), $textColor);	
	*/
	
	// SELECION DE CONTRATOS COMPRADOR Y CO-COMPRADOR
		// 1
			if ($arrayContServ['nom_accesorio']){
	
				$posY = 108;
				$posX = 420;
				imagestring($img, 2, $posX-3, $posY-1,"XX", $textColor);
			
			}
			
	/*
		// 2
			$posX = 444;
			imagestring($img, 2, $posX, $posY-1,"XX", $textColor);
		// 3
			$posX = 480;
			imagestring($img, 2, $posX-6, $posY-1,"XX", $textColor);
		// 4
			$posX = 504;
			imagestring($img, 2, $posX, $posY-1,"XX", $textColor);
		// 5
			$posX = 528;
			imagestring($img, 2, $posX+2, $posY-1,"XX", $textColor);
	*/			
	
	
	
	//********* DATOS DE EJECUCION ***********///
		// CIUDAD
			$posX = 132;
			$posY = 468;
			imagestring($img, 2, $posX, $posY-3,utf8_encode(strtoupper($rowCont['ciudad_empresa'])), $textColor);
			
		// DIA
			$posX = 336;
			imagestring($img, 2, $posX+6, $posY-3,utf8_encode(strtoupper($rowCont['dia_cont'])), $textColor);
			
		// MES
			$posX = 384;
			imagestring($img, 2, $posX+12, $posY-3,utf8_encode(strtoupper($rowCont['mes_cont'])), $textColor);
		
		// ANO (DOS ULTIMOS DIGITOS)
			$posX = 480;
			imagestring($img, 2, $posX-3, $posY-3,utf8_encode(strtoupper(substr($rowCont['ano_cont'],2))), $textColor);
	
	
	
	//********** CONTRATO AL POR MENOR A PLAZOS ***************//
		// DIRECCION COMPRADOR
			$ArrayString = cutString(utf8_encode(strtoupper($rowCont['dir_postal_comprador'])),37);
	
			$posX = 36;
			$posY = 528;
			imagestring($img, 2, $posX, $posY, $ArrayString[0], $textColor);
			$posX = 0;
			$posY = 540 ;
			imagestring($img, 2, $posX, $posY+2, substr($ArrayString[1],8),$textColor);
			
		// CODIGO POSTAL
			$posX = 144;
			$posY = 552;
			imagestring($img, 2, $posX, $posY-6,utf8_encode(strtoupper($rowCont['client_cod_postal'])), $textColor);
			
		// NOMBRE VENDEDOR (EMPRESA DEALER)
			$posX = 84;
			$posY = 564;
			imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($rowEmp['nombre_empresa'])), $textColor);
			
			if ($rowCont['id_co_cliente'] != null) {
				// DIRECCION DEL CO-COMPRADOR
					$ArrayString = cutString(utf8_encode(strtoupper($rowContCC['dir_co_client'])),37);
					
					$posX = 348;
					$posY = 528;
					imagestring($img, 2, $posX+12, $posY, $ArrayString[0], $textColor);
					$posX = 300;
					$posY = 540;
					imagestring($img, 2, $posX, $posY, substr($ArrayString[1],8), $textColor);
					
				// CODIGO POSTAL CO-COMPRADOR		
					$posX = 444;
					$posY = 552;
					imagestring($img, 2, $posX, $posY-6,utf8_encode(strtoupper($rowContCC['coclient_cod_postal'])), $textColor);
			} else {
				// DIRECCION DEL CO-COMPRADOR
					$posX = 348;
					$posY = 528;
					imagestring($img, 2, $posX+12, $posY, "N/A", $textColor);
					$posX = 300;
					$posY = 540;
					imagestring($img, 2, $posX, $posY, "", $textColor);
				
				// CODIGO POSTAL CO-COMPRADOR
					$posX = 444;
					$posY = 552;
					imagestring($img, 2, $posX, $posY-6,"N/A", $textColor);
			}
			
			// DIRECCION DEL VENDEDOR
				$posX = 396;
				$posY = 564;
				imagestring($img, 2, $posX, $posY, strtoupper(substr($rowEmp['direccion_postal'],0,33)), $textColor);
					
			//var_dump($ArrayString); var_dump($ArrayString2);exit;
			// POR
				$posX = 336;
				$posY = 576;
				imagestring($img, 2, $posX, $posY, "", $textColor);
					
			// TITULO	
				$posX = 480;
				imagestring($img, 2, $posX, $posY, "GERENTE", $textColor);
	
	
	
	//******** DATOS DEL FOOTER **************//
		// EJECUTA EN
			$posX = 252;
			$posY = 612;
			imagestring($img, 2, $posX+12, $posY, utf8_encode(strtoupper($rowCont['ciudad_empresa'])), $textColor);
			
		// DIA
			$posX = 444;
			imagestring($img, 2, $posX+12, $posY, utf8_encode(strtoupper($rowCont['dia_cont'])), $textColor);
			
		// MES
			$posX = 492;
			imagestring($img, 2, $posX+12, $posY, utf8_encode(strtoupper($rowCont['mes_cont'])), $textColor);
		
		// ANO
			$posX = 576;
			imagestring($img, 2, $posX-4, $posY, utf8_encode(strtoupper(substr($rowCont['ano_cont'],2))), $textColor);
					
	////////////// GUARDADANDO IMAGNES EN ARRAY //////////////////////
	$arrayImg[] = "tmp/"."contrato_venta".$pageNum.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
	
	
	// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de COntrato por financiamiento de  Vehículos)
	$queryConfig210 = sprintf("SELECT *
	FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 210 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig210 = mysql_query($queryConfig210, $conex);
	if (!$rsConfig210) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowConfig210 = mysql_fetch_assoc($rsConfig210);
	
	//error_reporting(E_ALL);
	//ini_set("display_errors", 1);	
	if (isset($arrayImg)) {
		foreach ($arrayImg as $indice => $valor) {
			$pdf->AddPage();
			
			if ($indice == 0){
			$pdf->Image($valor, 15, $rowConfig210['valor'], 596, 1798); // PDF pagina 1 CON EL MEMBRETE SUJETADOR // EMPRESA 2
		//	$pdf->Image($valor, 15, 15, 596, 1798); // PDF pagina 1 SIN EL MEMBRETE SUJETADOR
			} else {
			$pdf->Image($valor, 15, 0, 596, 1798); // PDF pagina 2
			}
		}
	}
	
	$pdf->SetDisplayMode(80);
	//$pdf->AutoPrint(true);
	$pdf->Output();
	
	if (isset($arrayImg)) {
		foreach ($arrayImg as $indice => $valor) {
			//if(file_exists($valor)) unlink($valor);
		}
	}
	
}

// FORMATO DE NUMEROS
function formatoNumero($monto){
	$monto = (!is_numeric($monto)) ? 0 : $monto;
	return number_format($monto, 2, ".", ",");
}

// IMPRIMIR DE DERECHA A IZQUIERDA
function prtDI ($string,$posX){
	$cont = strlen($string);
	$ret = $posX-(6*$cont);
	return $ret;
}

// CORTAR STRING
function cutString ($string,$tam) {
	$dir = explode(" ",$string);

	for ($i=0; $i < count($dir); $i++) {
		$cont += strlen($dir[$i]);
		if ($cont <= $tam){
			$str[0].=$dir[$i]." ";
		} else {
			$str[1].=$dir[$i]." ";
		}
	}
	
	return $str;
}
?>