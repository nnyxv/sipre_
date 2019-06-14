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
	
	$mostrar = false;
	
	//echo "<pre>"; var_dump($array1); var_dump($arrayProtector); exit;
	
	
	
	//************** SEGURO DEL VEHICULO ****************//
	// COBERTURAS REQUERIDAS
		// 1ER DEDUCIBLE
			$posY = 370;
			imagestring($img,2,105,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "DED. DE" : ""),0,7), 7, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($rowCont['ded_poliza']), 12, " ", STR_PAD_LEFT),$textColor);
		
		// 2DO DEDUCIBLE
			$posY += 12;
			imagestring($img,2,10,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "DED. DE" : ""),0,7), 7, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($rowCont['ded_poliza']), 12, " ", STR_PAD_LEFT),$textColor);
		
		// COBERTURAS PROPORCIONADAS AQUI	
			$posY += 26;
			imagestring($img,2,0,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "PROPORC." : ""),0,11), 11, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper($rowCont['nombre_poliza'])),0,22), 22, " ", STR_PAD_LEFT),$textColor);
		
		// COBERTURA
			// COLISION
				$posY += 34;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "COLISION" : ""),0,10), 10, " ", STR_PAD_RIGHT).
					str_pad(formatoNumero($rowCont['ded_poliza']), 12, " ", STR_PAD_LEFT).
					str_pad($rowCont['meses_poliza'], 3, " ", STR_PAD_LEFT).
					str_pad(formatoNumero($rowCont['monto_seguro']), 16, " ", STR_PAD_LEFT),$textColor);
			
			// AMPLIA
				$posY += 12;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "AMPLIA" : ""),0,10), 10, " ", STR_PAD_RIGHT).
					str_pad(formatoNumero(0), 12, " ", STR_PAD_LEFT).
					str_pad((0), 3, " ", STR_PAD_LEFT).
					str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
		
		// COBERTURA DE RESPONSABILIDAD
			// LESIONES CORPORALES
				$posY += 56;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "LESIONES CORPORALES" : ""),0,10), 10, " ", STR_PAD_RIGHT).
					str_pad(formatoNumero(0), 12, " ", STR_PAD_LEFT).
					str_pad((0), 3, " ", STR_PAD_LEFT).
					str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
			
			// DANOS A LA PROPIEDAD
				$posY += 12;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "DANOS A LA PROPIEDAD" : ""),0,10), 10, " ", STR_PAD_RIGHT).
					str_pad(formatoNumero(0), 12, " ", STR_PAD_LEFT).
					str_pad((0), 3, " ", STR_PAD_LEFT).
					str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
			
		// TOTAL DE PRIMAS DEL SEGURO DEL VEHICULO
				$posY += 26;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "TOTAL DE PRIMAS DEL SEGURO DEL VEHICULO" : ""),0,25), 25, " ", STR_PAD_RIGHT).
					str_pad(formatoNumero($rowCont['monto_seguro']), 16, " ", STR_PAD_LEFT),$textColor);
	//************** FIN SEGURO DEL VEHICULO ****************//
	
	
	
	//************** SEGURO A CREDITO ****************//
		// MAXIMO DE PAGO 1
			$posY = 710;
			imagestring($img,2,10,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "DE" : ""),0,4), 4, " ", STR_PAD_RIGHT).
				str_pad((0), 2, " ", STR_PAD_LEFT),$textColor);
			
		// MAXIMO DE PAGO 2
			$posY += 20;
			imagestring($img,2,80,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "DE" : ""),0,4), 4, " ", STR_PAD_RIGHT).
				str_pad((0), 2, " ", STR_PAD_LEFT),$textColor);
		
		// MAXIMO DE PAGO 3
			$posY += 20;
			imagestring($img,2,165,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "DE" : ""),0,4), 4, " ", STR_PAD_RIGHT).
				str_pad((0), 2, " ", STR_PAD_LEFT),$textColor);
		
		// COBERTURAS DISPONIBLES
			$posY += 30;
			imagestring($img,2,5,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "DE" : ""),0,4), 4, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper("N/A")),0,22), 22, " ", STR_PAD_RIGHT),$textColor);
		
		// DIRECCION EN NOMBRE DE LA EMPRESA
			$posY += 12;
			imagestring($img,2,55,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "DE" : ""),0,4), 4, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper("N/A")),0,22), 22, " ", STR_PAD_RIGHT),$textColor);
		
		// TIPO DE COBERTURA
			// VIDA PARA GARANTIZAR LA DEUDA
				$posY += 38;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "VIDA PARA GARANTIZAR LA DEUDA" : ""),0,22), 22, " ", STR_PAD_RIGHT).
					str_pad((0), 4, " ", STR_PAD_LEFT).
					str_pad(formatoNumero(0), 15, " ", STR_PAD_LEFT),$textColor);
		
			// VIDA MANCOMUNADA PARA GARANTIZAR LA DEUDA
				$posY += 21;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "VIDA MANCOMUNADA PARA GARANTIZAR LA DEUDA" : ""),0,22), 22, " ", STR_PAD_RIGHT).
					str_pad((0), 4, " ", STR_PAD_LEFT).
					str_pad(formatoNumero(0), 15, " ", STR_PAD_LEFT),$textColor);
						
			// DISCAPACIDAD PARA GARANTIZAR LA DEUDA
				$posY += 21;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "DISCAPACIDAD PARA GARANTIZAR LA DEUDA" : ""),0,22), 22, " ", STR_PAD_RIGHT).
					str_pad((0), 4, " ", STR_PAD_LEFT).
					str_pad(formatoNumero(0), 15, " ", STR_PAD_LEFT),$textColor);
						
			// DISCAPACIDAD PARA GARANTIZAR LA DEUDA MANCOMUANDA
				$posY += 21;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "DISCAPACIDAD PARA GARANTIZAR LA DEUDA MANCOMUANDA" : ""),0,22), 22, " ", STR_PAD_RIGHT).
					str_pad((0), 4, " ", STR_PAD_LEFT).
					str_pad(formatoNumero(0), 15, " ", STR_PAD_LEFT),$textColor);
		
		// TOTAL DE LAS PRIMAS DE SEGURO PARA GARANTIZAR LA DEUDA
			$posY += 24;
			imagestring($img,2,0,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "TOTAL DE LAS PRIMAS DE SEGURO PARA GARANTIZAR LA DEUDA" : ""),0,22), 22, " ", STR_PAD_RIGHT).
				str_pad((0), 4, " ", STR_PAD_LEFT).
				str_pad(formatoNumero($arrayCreditLife['precio_unitario']), 15, " ", STR_PAD_LEFT),$textColor);
	//************** FIN SEGURO A CREDITO ****************//
	
	
	
	//************** OTROS SEGUROS OPCIONALES ****************//
		// 1
			// TIPO DE COBERTURA
				$posY = 984;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "COB" : ""),0,3), 3, " ", STR_PAD_RIGHT).
					str_pad(substr(utf8_encode(strtoupper("N/A")),0,19), 19, " ", STR_PAD_RIGHT).
					str_pad((0), 4, " ", STR_PAD_LEFT).
					str_pad(formatoNumero(0), 15, " ", STR_PAD_LEFT),$textColor);
			
			// NOMBRE PROVEEDOR
				$posY += 21;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "PROVEEDOR" : ""),0,15), 15, " ", STR_PAD_RIGHT).
					str_pad(substr(utf8_encode(strtoupper("N/A")),0,22), 22, " ", STR_PAD_RIGHT),$textColor);
				
				$posY += 12;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "DIRECC." : ""),0,8), 8, " ", STR_PAD_RIGHT).
					str_pad(substr(utf8_encode(strtoupper("N/A")),0,22), 22, " ", STR_PAD_RIGHT),$textColor);
		
		// 2
			// TIPO DE COBERTURA
				$posY += 15;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "COB" : ""),0,3), 3, " ", STR_PAD_RIGHT).
					str_pad(substr(utf8_encode(strtoupper("N/A")),0,19), 19, " ", STR_PAD_RIGHT).
					str_pad((0), 4, " ", STR_PAD_LEFT).
					str_pad(formatoNumero(0), 15, " ", STR_PAD_LEFT),$textColor);
			
			// NOMBRE PROVEEDOR
				$posY += 21;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "PROVEEDOR" : ""),0,15), 15, " ", STR_PAD_RIGHT).
					str_pad(substr(utf8_encode(strtoupper("N/A")),0,22), 22, " ", STR_PAD_RIGHT),$textColor);
				
				$posY += 12;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "DIRECC." : ""),0,8), 8, " ", STR_PAD_RIGHT).
					str_pad(substr(utf8_encode(strtoupper("N/A")),0,22), 22, " ", STR_PAD_RIGHT),$textColor);
			
		// TOTAL DE PRIMAS DE OTROS SEGUROS OPCIONALES
			$posY += 12;
				imagestring($img,2,0,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "TOTAL DE PRIMAS DE OTROS SEGUROS OPCIONALES" : ""),0,26), 26, " ", STR_PAD_RIGHT).
					str_pad(formatoNumero(0), 15, " ", STR_PAD_LEFT),$textColor);
	//************** FIN OTROS SEGUROS OPCIONALES ****************//
	
	
	
	//*********DECLARACIONES**********//
	
	// EN ESTA SECCION SE DEBERIA ENCONTRAR LAS DECLARACIONES QUE APARECEN AL FINAL DE LA PRIMERA HOJA DEL CONTRATO EN PDF
	
	//*********FIN DECLARACIONES**********//
	
	
	
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
		cxc_fact_det_acc.monto_pagado,
		cxc_fact_det_acc.id_condicion_pago AS id_condicion_pago_accesorio,
		cxc_fact_det_acc.id_condicion_mostrar AS id_condicion_mostrar_accesorio,
		cxc_fact_det_acc.monto_pendiente,
		cxc_fact_det_acc.id_condicion_mostrar_pendiente,
		cxc_fact_det_acc.tipo_accesorio
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
		cxc_fact_det_acc.monto_pagado,
		cxc_fact_det_acc.id_condicion_pago AS id_condicion_pago_accesorio,
		cxc_fact_det_acc.id_condicion_mostrar AS id_condicion_mostrar_accesorio,
		cxc_fact_det_acc.monto_pendiente,
		cxc_fact_det_acc.id_condicion_mostrar_pendiente,
		cxc_fact_det_acc.tipo_accesorio
	FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
	WHERE cxc_fact_det_acc.id_factura = %s;",
		valTpDato($idDocumento, "int"));
	$rsDet = mysql_query($queryDet);
	if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);//die(print_r($queryDet,true));
	
		$montoPrecioVenta = 0;
	$arrayAdiContrato = array();
		$arrayContrato = array();
		$arrayAdicionales = array();
		$arrayAdicionalesPagados = array();
		$arrayAdicionalesPrecio = array();
		$arrayAdicionalesCosto = array();
	$totalAdiContrato = 0;
		$totalContrato = 0;
		$totalAdicionales = 0;
		$totalAdicionalesPagados = 0;
		$totalAdicionalesPrecio = 0;
		$totalAdicionalesCosto = 0;
			
	//************ FILTRO POR CREDITO Y CONTADO Y POR TIPO DE ACCESORIO **************//
	while ($rowDet = mysql_fetch_array($rsDet)) {
		if ($rowDet['id_tipo_accesorio'] == 1) { // 1 = Adicionales
			if ($rowDet['id_condicion_pago_accesorio'] == 1) { // 1 = Pagado, 2 = Financiado
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
			} else if ($rowDet['id_condicion_mostrar_accesorio'] == 1) { // Null = Individual, 1 = En Precio de Venta, 2 = En Costo de la Unidad
				$totalAdicionalesPrecio += $rowDet['precio_unitario'];
				$arrayAdicionalesPrecio[] = array(
					"nom_accesorio" => $rowDet['nom_accesorio'],
					"precio_unitario" => $rowDet['precio_unitario']);
			} else if ($rowDet['id_condicion_mostrar_accesorio'] == 2) { // Null = Individual, 1 = En Precio de Venta, 2 = En Costo de la Unidad
				$totalAdicionalesCosto += $rowDet['precio_unitario'];
				$arrayAdicionalesCosto[] = array(
					"nom_accesorio" => $rowDet['nom_accesorio'],
					"precio_unitario" => $rowDet['precio_unitario']);
			} else if (($rowDet['id_condicion_pago_accesorio'] == 2) // 1 = Pagado, 2 = Financiado
			|| (!in_array($rowDet['id_condicion_mostrar_accesorio'],array(1)))) { // Null = Individual, 1 = En Precio de Venta, 2 = En Costo de la Unidad
				$totalAdicionales += $rowDet['precio_unitario'];
				$arrayAdicionales[] = array(
					"nom_accesorio" => $rowDet['nom_accesorio'],
					"precio_unitario" => $rowDet['precio_unitario']);
			}
		} else if ($rowDet['id_tipo_accesorio'] == 3) { // 3 = Contratos
			if ($rowDet['id_filtro_factura'] == 8) { // 8 = CONTRATO DE SERVICIO (Relacion Tabla an_filtro_factura)
				$totalAdiContrato += $rowDet['precio_unitario'];
				$arrayAdiContrato[] = array(
					"nom_accesorio" => $rowDet['nom_accesorio'],
					"precio_unitario" => $rowDet['precio_unitario']);
			} else {
				$totalContrato += $rowDet['precio_unitario'];
				$arrayContrato[] = array(
					"nom_accesorio" => $rowDet['nom_accesorio'],
					"precio_unitario" => $rowDet['precio_unitario']);
			}
		}
	}
	
	// Campo Pagado por: adicionales en el contrato
	foreach ($arrayAdiContrato as $indiceAdiContrato => $valorAdiContrato) {
		if ($valorAdiContrato['nom_accesorio'] = $arrayContServ['nom_accesorio']) {
			$arrayAdiContrato[$indiceAdiContrato]['nombre_em_cont_serv'] = $arrayContServ['nombre_em_cont_serv'];
		} 
		//Si llegase a necesitar la asignacion de la empresa de los otros adicionales tipo contrato
		/*if ($valorAdiContrato['nom_accesorio'] = $arrayGap['nom_accesorio']){
			$empGap= $arrayGap['nombre_em_gap'];
		} else {
			$empGap = "";
		}*/
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
	
	
	
	//************** DESGLOSE DEL CANTIDAD FINANCIADA ****************//
	// 1. PRECIO CONTADO
		$precioTotContado = $rowUnidad['precio_unitario'] + $totalAdicionalesPrecio;
		
		$posY = 588;
		imagestring($img,2,275,$posY-1,
			str_pad(substr(utf8_decode(($mostrar == true) ? "1. PRECIO AL CONTADO" : ""),0,30), 30, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero($precioTotContado), 16, " ", STR_PAD_LEFT),$textColor);
	
	// 2.
		$posY += 13;
		imagestring($img,2,275,$posY-1,
			str_pad(substr(utf8_decode(($mostrar == true) ? "2. -" : strtoupper($arrayHunter["nom_accesorio"])),0,30), 30, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero($arrayHunter["precio_unitario"]), 16, " ", STR_PAD_LEFT),$textColor);
	
	// 3.
		$posY += 12;
		imagestring($img,2,275,$posY-1,
			str_pad(substr(utf8_decode(($mostrar == true) ? "3. -" : strtoupper($arrayProtector["nom_accesorio"])),0,30), 30, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero($arrayProtector["precio_unitario"]), 16, " ", STR_PAD_LEFT),$textColor);
	
	// 4.
		$posY += 12;
		imagestring($img,2,275,$posY-1,
			str_pad(substr(utf8_decode(($mostrar == true) ? "4. -" : ""),0,30), 30, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
	
	// 5.
		$posY += 12;
		imagestring($img,2,275,$posY-1,
			str_pad(substr(utf8_decode(($mostrar == true) ? "5. -" : ""),0,30), 30, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
	
	// 6.
		$posY += 12;
		imagestring($img,2,275,$posY-1,
			str_pad(substr(utf8_decode(($mostrar == true) ? "6. -" : ""),0,30), 30, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
		
	// 7. SUBTOTAL 1+2+3+4+5+6 
		$totalAdiContado = $precioTotContado + $arrayHunter['precio_unitario'] + $arrayProtector['precio_unitario'];
		
		$posY += 12;
		imagestring($img,2,275,$posY-1,
			str_pad(substr(utf8_decode(($mostrar == true) ? "7. SUBTOTAL (1+2+3)" : ""),0,30), 30, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero($totalAdiContado), 20, " ", STR_PAD_LEFT),$textColor);
	
	// 8. PAGO INICIAL - TRADE IN
		// A. AUTO TOMADO EN CUENTA (VALOR BRUTO) - ALLOWANCE
			$posY += 22;
			imagestring($img,2,275,$posY-1,
				str_pad(substr(utf8_decode(($mostrar == true) ? "A. AUTO TOMADO EN CUENTA (VALOR BRUTO)" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($rowTradein['allowance']), 16, " ", STR_PAD_LEFT),$textColor);
		
		// B. MENOS LIQUIDACION DE ENTREGA A CUENTA PAGADA A - PAYOFF
			$posY += 12;
			imagestring($img,2,275,$posY-1,
				str_pad(substr(utf8_decode(($mostrar == true) ? "B. MENOS LIQUIDACION DE ENTREGA A CUENTA PAGADA A" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($rowTradein['payoff']), 16, " ", STR_PAD_LEFT),$textColor);
		
		// C. BONIFICACION NETA POR ENTREGA A CUENTA (A-B) - CREDITO NETO
			if ($rowTradein['payoff'] > $rowTradein['allowance']) {
				$sign = "-";
				$sumAB = $rowTradein['payoff'] - $rowTradein['allowance'];
			} else {
				$sign = "";
				$sumAB = $rowTradein['allowance'] - $rowTradein['payoff'];
			}
			
			$posY += 12;
			imagestring($img,2,275,$posY-1,
				str_pad(substr(utf8_decode(($mostrar == true) ? "C. BONIFICACION NETA POR ENTREGA A CUENTA (A-B)" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($sumAB), 16, " ", STR_PAD_LEFT),$textColor);
		
		// D. PAGO INICIAL AL CONTADO AL CIERRE - CONTADOS + ANTICIPOS (efectivo, tdd, tdc, cheques, etc..)
			$posY += 11;
			imagestring($img,2,275,$posY-1,
				str_pad(substr(utf8_decode(($mostrar == true) ? "D. PAGO INICIAL AL CONTADO AL CIERRE" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($totContado), 16, " ", STR_PAD_LEFT),$textColor);
		
		// E. TIPO DE REEMBOLSO (SI CORRESPONDE) - PND's
			$posY += 12;
			imagestring($img,2,275,$posY-1,
				str_pad(substr(utf8_decode(($mostrar == true) ? "E. TIPO DE REEMBOLSO (SI CORRESPONDE)" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($rowPagos['pagos_pnd']), 16, " ", STR_PAD_LEFT),$textColor);
		
		// F. PAGO INICIAL DIFERIDO
			$posY += 12;
			imagestring($img,2,275,$posY-1,
				str_pad(substr(utf8_decode(($mostrar == true) ? "F. PAGO INICIAL DIFERIDO" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
				
		// G. PAGO INICIAL TOTAL AL CONTADO (D+E+F)
			$totDEF = $totContado + $rowPagos['pagos_pnd'];
			
			$posY += 12;
			imagestring($img,2,275,$posY-1,
				str_pad(substr(utf8_decode(($mostrar == true) ? "G. PAGO INICIAL TOTAL AL CONTADO (D+E+F)" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($totDEF), 16, " ", STR_PAD_LEFT),$textColor);
		
		// PAGO INICIAL TOTAL (C+G)	
			if ($sign == '-'){
			  $sumAB = 0;
			}
			
			$totContadoFinal = $totDEF + $sumAB;
			
			$posY += 12;
			imagestring($img,2,275,$posY-1,
				str_pad(substr(utf8_decode(($mostrar == true) ? "PAGO INICIAL TOTAL (C+G)" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($totContadoFinal), 20, " ", STR_PAD_LEFT),$textColor);
		
	// 9. SALDO A FAVOR DEL VENDEDOR POR LOS BIENES Y SERVICIOS ARRIBA INDICADOS (7-8) - ACUMULADO HASTA AHORA
		$saldAFavor1 = $totalAdiContado - $totContadoFinal;
		
		$posY += 17;
		imagestring($img,2,275,$posY-1,
			str_pad(substr(utf8_decode(($mostrar == true) ? "9. SALDO A FAVOR DEL VENDEDOR POR LOS BIENES Y SERVICIOS ARRIBA INDICADOS (7-8)" : ""),0,30), 30, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero($saldAFavor1), 20, " ", STR_PAD_LEFT),$textColor);
	
	// 10. CANTIDADES PAGADAS A OTROS EN SU NOMBRE 	
		$totalOtrosCargos = 0;
	
		// A. LICENCIA
			$posY = 816;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "A. LICENCIA" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
		
		// B. REGISTRO
			$posY += 12;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "B. REGISTRO" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
		
		// C. CERTIFICADO DE TITULO DE PROPIEDAD
			$posY += 12;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "C. CERTIFICADO DE TITULO DE PROPIEDAD" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
		
		// D. DECLARACION DE FINANCIAMIENTO
			$posY += 22;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "D. DECLARACION DE FINANCIAMIENTO" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
		
		// E. OTROS IMPUESTOS
			$posY += 11;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "E. OTROS IMPUESTOS" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
		
		// F. 
			$posY += 11;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "F. -" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
			
		// G.
			$posY += 11;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "G. -" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
		
		// H. TOTAL DEL SEGURO DEL VEHICULO
			$totalOtrosCargos += $rowCont['monto_seguro'];
			
			$posY += 31;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "H. TOTAL DEL SEGURO DEL VEHICULO" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($rowCont['monto_seguro']), 16, " ", STR_PAD_LEFT),$textColor);
		
		// I. TOTAL DEL SEGURO PARA GARANTIZAR LA DEUDA
			$totalOtrosCargos += $arrayCreditLife['precio_unitario'];
			
			$posY += 13;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "I. TOTAL DEL SEGURO PARA GARANTIZAR LA DEUDA" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($arrayCreditLife['precio_unitario']), 16, " ", STR_PAD_LEFT),$textColor);
		
		// J. TOTAL DE OTROS SEGUROS OPCIONALES
			$posY += 11;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "J. TOTAL DE OTROS SEGUROS OPCIONALES" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
		
		// K. - DESGLOSE GAP
			if (isset($arrayGap)) {
				$totalOtrosCargos += $arrayGap['precio_unitario'];
				
				$posY += 12;
				imagestring($img,2,275,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "K. -" : strtoupper($arrayGap['nom_accesorio'])),0,30), 30, " ", STR_PAD_RIGHT).
					str_pad(formatoNumero($arrayGap['precio_unitario']), 16, " ", STR_PAD_LEFT),$textColor);
			} else {
				$posY += 12;
				imagestring($img,2,275,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "K. -" : "N/A"),0,30), 30, " ", STR_PAD_RIGHT).
					str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
			}
		
		// L. 
			$posY += 23;
			// A PETICION SIEMPRE SERA "ASSURANT"
			imagestring($img,2,336,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "L. PAGADO A" : "ASSURANT"),0,30), 30, " ", STR_PAD_RIGHT),$textColor);
			
			$totalOtrosCargos += $arrayAdiContrato[0]["precio_unitario"];
		
			$posY += 13;
			// A PETICION SIEMPRE SERA "CONT. SERVICIO" 
			imagestring($img,2,311,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "L. POR" : "CONT. SERVICIO"),0,24), 24, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($arrayAdiContrato[0]["precio_unitario"]), 16, " ", STR_PAD_LEFT),$textColor);
		
		// M. HASTA P.
		$contCol = 0;
		for ($col = "M"; $col <= "P"; $col++) {
			$contCol++;
			
			if ($arrayAdiContrato[$contCol] != null) {
				$posY += 10;
				imagestring($img,2,336,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? $col.". PAGADO A" : strtoupper($arrayAdiContrato[$contCol]["nombre_em_cont_serv"])),0,30), 30, " ", STR_PAD_RIGHT),$textColor);
				
				$totalOtrosCargos += $arrayAdiContrato[$contCol]["precio_unitario"];
			
				$posY += 13;
				imagestring($img,2,311,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? $col.". POR" : strtoupper($arrayAdiContrato[$contCol]["nom_accesorio"])),0,24), 24, " ", STR_PAD_RIGHT).
					str_pad(formatoNumero($arrayAdiContrato[$contCol]["precio_unitario"]), 16, " ", STR_PAD_LEFT),$textColor);
			 } else {
				$posY += 10;
				imagestring($img,2,336,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? $col.". PAGADO A" : "N/A"),0,30), 30, " ", STR_PAD_RIGHT),$textColor);
			
				$posY += 13;
				imagestring($img,2,311,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? $col.". POR" : "N/A"),0,24), 24, " ", STR_PAD_RIGHT).
					str_pad(formatoNumero(0), 16, " ", STR_PAD_LEFT),$textColor);
			 }
		}
		
		// TOTAL DE OTROS CARGOS (10A+...P)
			$posY += 10;
			imagestring($img,2,275,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "TOTAL DE OTROS CARGOS (SUME DE LA LINEA 10A HASTA LA LINEA 10P)" : ""),0,30), 30, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($totalOtrosCargos), 20, " ", STR_PAD_LEFT),$textColor);
		
	// 11. CANTIDAD FINANCIADA (9+10)
		$cantidadFinanciada = $saldAFavor1 + $totalOtrosCargos;
		
		$posY += 12;
		imagestring($img,2,275,$posY,
			str_pad(substr(utf8_decode(($mostrar == true) ? "11. CANTIDAD FINANCIADA (9+10)" : ""),0,30), 30, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero($cantidadFinanciada), 20, " ", STR_PAD_LEFT),$textColor);
	//************** FIN DESGLOSE DEL CANTIDAD FINANCIADA ****************//
	
	
	
	//************** DECLARACIONES INFORMATIVAS SOBRE EL CREDITO ****************//
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
	//************** FIN DE DECLARACIONES INFORMATIVAS SOBRE EL CREDITO ****************//
	
	
	
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
		
	//************** CONTRATO DE GAP ****************//
	if ($arrayGap) {
		// NOMBRE DEL PROVEEDOR
			$posY = 36;
			imagestring($img,2,130,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "PROV." : ""),0,6), 6, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper($rowCont['nombre_gap'])),0,12), 12, " ", STR_PAD_RIGHT),$textColor);
		
		// DOMICILIO PROVEEDOR
			$posY += 11;
			imagestring($img,2,70,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "DIRECC." : ""),0,8), 8, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper($rowCont['direccion_agencia'])),0,12), 12, " ", STR_PAD_RIGHT),$textColor);
		
		// PLAZO EN MESES
			$posY += 46;
			imagestring($img,2,35,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "PLAZO" : ""),0,6), 6, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper($arrayGap['per_gap'])),0,13), 13, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_decode(($mostrar == true) ? "COSTO" : ""),0,6), 6, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper($arrayGap['precio_unitario'])),0,13), 13, " ", STR_PAD_RIGHT),$textColor);
	} else {
		// NOMBRE DEL PROVEEDOR
			$posY = 36;
			imagestring($img,2,130,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "PROV." : ""),0,6), 6, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper("N/A")),0,12), 12, " ", STR_PAD_RIGHT),$textColor);
		
		// DOMICILIO PROVEEDOR
			$posY += 11;
			imagestring($img,2,70,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "DIRECC." : ""),0,8), 8, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper("N/A")),0,12), 12, " ", STR_PAD_RIGHT),$textColor);
		
		//PLAZO EN MESES
			$posY += 46;
			imagestring($img,2,35,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "PLAZO" : ""),0,6), 6, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper("N/A")),0,13), 13, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_decode(($mostrar == true) ? "COSTO" : ""),0,6), 6, " ", STR_PAD_RIGHT).
				str_pad(substr(utf8_encode(strtoupper("N/A")),0,13), 13, " ", STR_PAD_RIGHT),$textColor);
	}
	//************** FIN CONTRATO DE GAP ****************//
	
	
	
	//************** CONTRATO DE SERVICIO, MANTENIMIENTO Y OTROS RELACIONADOS ****************//
	// 1.
		if ($arrayContServ['nom_accesorio'] != null){
			$posY = 48;
			imagestring($img,2,290,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "1. -" : "CONT. SERVICIO"),0,16), 16, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero($arrayContServ['ded_cont_serv']), 9, " ", STR_PAD_LEFT).
				str_pad($arrayContServ['per_cont_serv'], 8, " ", STR_PAD_LEFT).
				str_pad(formatoNumero($arrayContServ['precio_unitario']), 17, " ", STR_PAD_LEFT),$textColor);
		} else {
			$posY = 48;
			imagestring($img,2,290,$posY,
				str_pad(substr(utf8_decode(($mostrar == true) ? "1. -" : "CONT. SERVICIO"),0,16), 16, " ", STR_PAD_RIGHT).
				str_pad(formatoNumero(0), 9, " ", STR_PAD_LEFT).
				str_pad((0), 8, " ", STR_PAD_LEFT).
				str_pad(formatoNumero(0), 17, " ", STR_PAD_LEFT),$textColor);
		}
		
	
	// 2.
		$posY += 11;
		/*imagestring($img,2,290,$posY,
			str_pad(substr(utf8_decode(($mostrar == true) ? "1. -" : "N/A"),0,16), 16, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero(0), 9, " ", STR_PAD_LEFT).
			str_pad((0), 8, " ", STR_PAD_LEFT).
			str_pad(formatoNumero(0), 17, " ", STR_PAD_LEFT),$textColor);*/
	
	// 3.
		$posY += 11;
		/*imagestring($img,2,290,$posY,
			str_pad(substr(utf8_decode(($mostrar == true) ? "1. -" : "N/A"),0,16), 16, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero(0), 9, " ", STR_PAD_LEFT).
			str_pad((0), 8, " ", STR_PAD_LEFT).
			str_pad(formatoNumero(0), 17, " ", STR_PAD_LEFT),$textColor);*/
	
	// 4.
		$posY += 11;
		/*imagestring($img,2,290,$posY,
			str_pad(substr(utf8_decode(($mostrar == true) ? "1. -" : "N/A"),0,16), 16, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero(0), 9, " ", STR_PAD_LEFT).
			str_pad((0), 8, " ", STR_PAD_LEFT).
			str_pad(formatoNumero(0), 17, " ", STR_PAD_LEFT),$textColor);*/
							
	// 5.
		$posY += 11;
		/*imagestring($img,2,290,$posY,
			str_pad(substr(utf8_decode(($mostrar == true) ? "1. -" : "N/A"),0,16), 16, " ", STR_PAD_RIGHT).
			str_pad(formatoNumero(0), 9, " ", STR_PAD_LEFT).
			str_pad((0), 8, " ", STR_PAD_LEFT).
			str_pad(formatoNumero(0), 17, " ", STR_PAD_LEFT),$textColor);*/
	
	// EL COMPRADOR Y EL CO-COMPRADOR QUIEREN
		// 1
			if ($arrayContServ['nom_accesorio']){
				$posY += 12;
				imagestring($img,2,290,$posY,
					str_pad(substr(utf8_decode(($mostrar == true) ? "EL COMPRADOR Y EL CO-COMPRADOR QUIEREN" : ""),0,19), 19, " ", STR_PAD_RIGHT).
					str_pad(substr(utf8_encode(strtoupper("XX")),0,2), 2, " ", STR_PAD_RIGHT),$textColor);
			}
	//************** FIN CONTRATO DE SERVICIO, MANTENIMIENTO Y OTROS RELACIONADOS ****************//
	
	
	
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