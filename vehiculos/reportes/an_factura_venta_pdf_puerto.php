<?php
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idDocumento = $valCadBusq[0];

$queryEncabezado = sprintf("SELECT 
        banco.nombreBanco,
	fact_vent.id_empresa,
	fact_vent.numeroFactura,
	ped_vent.id_pedido,
	ped_vent.numeracion_pedido,
        ped_vent.meses_financiar,
        ped_vent.interes_cuota_financiar,
        ped_vent.cuotas_financiar,
		ped_vent.meses_financiar2,
		ped_vent.interes_cuota_financiar2,
		ped_vent.cuotas_financiar2,
	fact_vent.fechaRegistroFactura,
	fact_vent.fechaVencimientoFactura,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,
	cliente.correo,
        prospecto.fecha_nacimiento,
	fact_vent.observacionFactura,
	fact_vent.subtotalFactura AS subtotal_factura,
	fact_vent.porcentaje_descuento,
	fact_vent.descuentoFactura AS subtotal_descuento,
	fact_vent.baseImponible AS base_imponible,
	fact_vent.porcentajeIvaFactura AS porcentaje_iva,
	fact_vent.calculoIvaFactura AS subtotal_iva,
	fact_vent.porcentajeIvaDeLujoFactura AS porcentaje_iva_lujo,
	fact_vent.calculoIvaDeLujoFactura AS subtotal_iva_lujo,
	fact_vent.montoExento AS monto_exento,
	fact_vent.montoExonerado AS monto_exonerado
FROM cj_cc_encabezadofactura fact_vent
	INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
	INNER JOIN pg_empleado empleado ON (fact_vent.idVendedor = empleado.id_empleado)
	INNER JOIN an_pedido ped_vent ON (fact_vent.numeroPedido = ped_vent.id_pedido)
        LEFT JOIN crm_perfil_prospecto prospecto ON (cliente.id = prospecto.id)
        LEFT JOIN bancos banco ON ped_vent.id_banco_financiar = banco.idBanco
WHERE fact_vent.idFactura = %s
	AND fact_vent.idDepartamentoOrigenFactura = 2",
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

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
$rutaLogo = "../../".$rowEmp["logo_familia"];

// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Datos del Sistema GNV en la Impresion de la Factura de Venta y Nota de Crédito)
$queryConfigDatosGNV = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 202 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfigDatosGNV = mysql_query($queryConfigDatosGNV, $conex);
if (!$rsConfigDatosGNV) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowConfigDatosGNV = mysql_fetch_assoc($rsConfigDatosGNV);

// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig403 = mysql_query($queryConfig403);
if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig403 = mysql_num_rows($rsConfig403);
$rowConfig403 = mysql_fetch_assoc($rsConfig403);


$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

//ENCABEZADO
$posY = 9;
imagestring($img,1,70,$posY,$rowEmp["nombre_empresa"],$textColor);

$direccion = explode("\n",$rowEmp["direccion"]);
$posY += 9;
imagestring($img,1,70,$posY,strtoupper(trim($direccion[0])),$textColor);
$posY += 9;
imagestring($img,1,70,$posY,strtoupper(trim($direccion[1])),$textColor);

if($rowEmp["fax"] != ""){
    $fax = " FAX ".$rowEmp["fax"];
}
$posY += 9;
imagestring($img,1,70,$posY,"Tel.: ".$rowEmp["telefono1"]." ".$rowEmp["telefono2"].$fax,$textColor);
$posY += 9;  	 


$posY = 9*3;
imagestring($img,1,300,$posY,str_pad("FACTURA SERIE - V", 34, " ", STR_PAD_BOTH),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,300,$posY,utf8_decode("FACTURA NRO."),$textColor);
imagestring($img,2,375,$posY-3,": ".$rowEncabezado['numeroFactura'],$textColor);

//$posY += 9;
//imagestring($img,1,300,$posY,utf8_decode("FECHA EMISIÓN"),$textColor);
//imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fechaRegistroFactura'])),$textColor);
//
//$posY += 9;
//imagestring($img,1,300,$posY,utf8_decode("FECHA VENC."),$textColor);
//imagestring($img,1,375,$posY,": ".date(spanDateFormat, strtotime($rowEncabezado['fechaVencimientoFactura'])),$textColor);

$posY += 9;
imagestring($img,1,300,$posY,utf8_decode("PEDIDO NRO."),$textColor);
imagestring($img,1,375,$posY,": ".$rowEncabezado['numeracion_pedido'],$textColor);

$posY += 9;
imagestring($img,1,300,$posY,utf8_decode("VENDEDOR"),$textColor);
imagestring($img,1,375,$posY,": ".strtoupper($rowEncabezado['nombre_empleado']),$textColor);


$posY = 56;
imagestring($img,1,190,$posY,utf8_decode("CÓDIGO").": ".str_pad($rowEncabezado['id'], 8, " ", STR_PAD_LEFT),$textColor);



$posY += 27;
imageline($img, 0, $posY-5, 470, $posY-5, $textColor);//linea H -
imagestring($img,1,0,$posY,"CLIENTE: ".strtoupper($rowEncabezado['nombre_cliente']),$textColor);
$posY += 8;
imagestring($img,1,0,$posY,"SSN/EIN: ".strtoupper($rowEncabezado['ci_cliente']),$textColor);

$direccionCliente = strtoupper(str_replace(",", "", utf8_decode("DIRRECIÓN: ").elimCaracter(utf8_encode($rowEncabezado['direccion_cliente']),";")));
$posY += 9;
imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,84)),$textColor);

if(strlen($direccionCliente) > 85){
    $posY += 9;
    imagestring($img,1,0,$posY,trim(substr($direccionCliente,84,84)),$textColor);
}

if($rowEncabezado['fecha_nacimiento'] != NULL && $rowEncabezado['fecha_nacimiento'] != "1969-12-31" && $rowEncabezado['fecha_nacimiento'] != "0000-00-00"){
   $fechaNacimiento = "   FECHA NAC.: ".date("m-d-Y", strtotime($rowEncabezado['fecha_nacimiento']));
}

$posY += 9;
imagestring($img,1,0,$posY,utf8_decode("TELÉFONOS: ").$rowEncabezado['telf'] ." ".$rowEncabezado['otrotelf'] . "   EMAIL: ".strtoupper($rowEncabezado["correo"]).$fechaNacimiento,$textColor);
$posY += 9;

imageline($img, 0, $posY+5, 470, $posY+5, $textColor);//linea H -



// BUSCA LOS DATOS DE LA UNIDAD
$queryUnidad = sprintf("SELECT 
	uni_bas.nom_uni_bas,
	marca.nom_marca,
	modelo.nom_modelo,
	vers.nom_version,
	ano.nom_ano,
	uni_fis.placa,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.kilometraje,
	color1.nom_color AS color_externo,
	fact_vent_det_vehic.precio_unitario,
	uni_bas.com_uni_bas,
	codigo_unico_conversion,
	marca_kit,
	marca_cilindro,
	modelo_regulador,
	serial1,
	serial_regulador,
	capacidad_cilindro,
	fecha_elaboracion_cilindro
FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
	INNER JOIN an_unidad_fisica uni_fis ON (fact_vent_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
WHERE fact_vent_det_vehic.id_factura= %s",
	valTpDato($idDocumento, "int"));
$rsUnidad = mysql_query($queryUnidad);
if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsUnidad = mysql_num_rows($rsUnidad);
$rowUnidad = mysql_fetch_array($rsUnidad);

$posY += 10;

if ($totalRowsUnidad > 0) {
    
        if($rowConfig403['valor'] == "3"){//puerto rico
            
            imagestring($img,1,0,$posY,strtoupper(substr($rowUnidad['nom_uni_bas'],0,18)),$textColor);
            imagestring($img,1,95,$posY,utf8_decode("MARCA"),$textColor);
            imagestring($img,1,120,$posY,": ".strtoupper($rowUnidad['nom_marca']),$textColor);
            
            imagestring($img,1,200,$posY,utf8_decode("MODELO"),$textColor);
            imagestring($img,1,230,$posY,": ".strtoupper($rowUnidad['nom_modelo']),$textColor);
            
            imagestring($img,1,340,$posY,utf8_decode("AÑO"),$textColor);
            imagestring($img,1,355,$posY,": ".strtoupper($rowUnidad['nom_ano']),$textColor);
            
            $posY += 9;    
            imagestring($img,1,0,$posY,utf8_decode("VERSIÓN"),$textColor);
            imagestring($img,1,35,$posY,": ".strtoupper(substr($rowUnidad['nom_version'],0,28)),$textColor);

            imagestring($img,1,190,$posY,utf8_decode("TABLILLA"),$textColor);
            imagestring($img,2,229,$posY-3,": ".strtoupper($rowUnidad['placa']),$textColor);
            
            imagestring($img,1,330,$posY,utf8_decode("STOCK"),$textColor);
            imagestring($img,1,355,$posY,": ".strtoupper($rowUnidad['serial_motor']),$textColor);
            
            $posY += 9;
            
            imagestring($img,1,0,$posY,utf8_decode("VIN"),$textColor);
            imagestring($img,2,34,$posY-3,": ".strtoupper($rowUnidad['serial_carroceria']),$textColor);
            
            imagestring($img,1,205,$posY,utf8_decode("COLOR"),$textColor);
            imagestring($img,1,230,$posY,": ".strtoupper(substr($rowUnidad['color_externo'],0,16)),$textColor);
            
            imagestring($img,1,325,$posY,utf8_decode("MILLAS"),$textColor);
            imagestring($img,1,355,$posY,": ".strtoupper($rowUnidad['kilometraje']),$textColor);

            
        }else{//venezuela panama
    
            imagestring($img,1,0,$posY,strtoupper(substr($rowUnidad['nom_uni_bas'],0,18)),$textColor);
            imagestring($img,1,95,$posY,utf8_decode("MARCA"),$textColor);
            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_marca']),$textColor);

            $posY += 9;
            imagestring($img,1,95,$posY,utf8_decode("MODELO"),$textColor);
            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_modelo']),$textColor);

            $posY += 9;
            imagestring($img,1,95,$posY,utf8_decode("VERSIÓN"),$textColor);
            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_version']),$textColor);

            $posY += 9;
            imagestring($img,1,95,$posY,utf8_decode("AÑO"),$textColor);
            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['nom_ano']),$textColor);

            $posY += 12;
            imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanPlaca)),$textColor);
            imagestring($img,2,210,$posY-3,": ".strtoupper($rowUnidad['placa']),$textColor);

            $posY += 12;
            imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanSerialCarroceria)),$textColor);
            imagestring($img,2,210,$posY-3,": ".strtoupper($rowUnidad['serial_carroceria']),$textColor);

            $posY += 12;
            imagestring($img,1,95,$posY,strtoupper(utf8_decode($spanSerialMotor)),$textColor);
            imagestring($img,2,210,$posY-3,": ".strtoupper($rowUnidad['serial_motor']),$textColor);

            $posY += 18;
            imagestring($img,1,95,$posY,utf8_decode("COLOR CARROCERIA"),$textColor);
            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['color_externo']),$textColor);

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
                            imagestring($img,1,95,$posY,utf8_decode("CÓDIGO UNICO"),$textColor);
                            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['codigo_unico_conversion']),$textColor);

                            $posY += 9;
                            imagestring($img,1,95,$posY,utf8_decode("MARCA KIT"),$textColor);
                            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['marca_kit']),$textColor);

                            $posY += 9;
                            imagestring($img,1,95,$posY,utf8_decode("MARCA CILINDRO"),$textColor);
                            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['marca_cilindro']),$textColor);

                            $posY += 9;
                            imagestring($img,1,95,$posY,utf8_decode("MODELO REGULADOR"),$textColor);
                            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['modelo_regulador']),$textColor);

                            $posY += 9;
                            imagestring($img,1,95,$posY,utf8_decode("SERIAL 1"),$textColor);
                            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['serial1']),$textColor);

                            $posY += 9;
                            imagestring($img,1,95,$posY,utf8_decode("SERIAL REGULADOR"),$textColor);
                            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['serial_regulador']),$textColor);

                            $posY += 9;
                            imagestring($img,1,95,$posY,utf8_decode("CAPACIDAD CILINDRO (NG)"),$textColor);
                            imagestring($img,1,210,$posY,": ".strtoupper($rowUnidad['capacidad_cilindro']),$textColor);

                            $posY += 9;
                            imagestring($img,1,95,$posY,utf8_decode("FECHA ELAB. CILINDRO"),$textColor);
                            imagestring($img,1,210,$posY,($rowUnidad['fecha_elaboracion_cilindro']) ? ": ".date(spanDateFormat, strtotime($rowUnidad['fecha_elaboracion_cilindro'])) : ": "."----------",$textColor);
                    }
            }
	        
        }
        
	$posY += 9;
        imageline($img, 0, $posY+5, 470, $posY+5, $textColor);//linea H -
	//imagestring($img,1,95,$posY,"--------------------------------------------------------",$textColor);
	
	//$posY += 9;
//	imagestring($img,1,95,$posY,utf8_decode("MONTO VEHÍCULO"),$textColor);
//	imagestring($img,1,380,$posY,str_pad(formatoNumero($rowUnidad['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);

	$posY += 9;
}

imagestring($img,1,0,$posY,"FORMA PAGO: ",$textColor);
imagestring($img,1,130,$posY,"FINANCIADO POR: ",$textColor);
$posY += 18;

$posYVenta = $posY;//es usado en los accesorios del recuadro de la derecha
//TRADEIN
imagestring($img,1,5,$posY,strtoupper(utf8_decode("VEHÍCULO TOMADO A CAMBIO")),$textColor);
imageline($img, 0, $posY+9, 210, $posY+9, $textColor);//linea H -
imageline($img, 0, $posY+95, 210, $posY+95, $textColor);//linea H -
imageline($img, 0, $posY+115, 210, $posY+115, $textColor);//linea H -
imageline($img, 0, $posY+160, 210, $posY+160, $textColor);//linea H -
imageline($img, 0, $posY+230, 210, $posY+230, $textColor);//linea H -
imageline($img, 0, $posY+9, 0, $posY+230, $textColor);//linea V |
imageline($img, 210, $posY+9, 210, $posY+230, $textColor);//linea V |

if($rowConfig403['valor'] == "3"){//puerto rico
    //CONSULTO SI LA FACTURA TIENE PAGO ANTICIPO Y ES DE TIPO TRADE-IN
    $queryTradein = sprintf("SELECT 
		an_pagos.montoPagado,
		cj_cc_anticipo.saldoAnticipo,
		an_tradein.acv,
		an_tradein.payoff,
		an_marca.nom_marca,
		an_unidad_fisica.placa,
		an_unidad_fisica.serial_carroceria, 
		an_unidad_fisica.kilometraje,
		an_unidad_fisica.serial_motor,
		an_ano.nom_ano,
		an_modelo.nom_modelo,
		CONCAT_WS(' ', an_ano.nom_ano, an_modelo.nom_modelo) as ano_modelo,
		CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) as nombre_cliente_adeudado
	FROM an_pagos
		INNER JOIN cj_cc_anticipo ON an_pagos.numeroDocumento = cj_cc_anticipo.idAnticipo
		INNER JOIN an_tradein ON cj_cc_anticipo.idAnticipo = an_tradein.id_anticipo
		INNER JOIN an_unidad_fisica ON an_tradein.id_unidad_fisica = an_unidad_fisica.id_unidad_fisica
		INNER JOIN an_ano ON an_unidad_fisica.ano = an_ano.id_ano
		INNER JOIN an_uni_bas ON an_unidad_fisica.id_uni_bas = an_uni_bas.id_uni_bas
		INNER JOIN an_marca ON an_uni_bas.mar_uni_bas = an_marca.id_marca
		INNER JOIN an_modelo ON an_uni_bas.mod_uni_bas = an_modelo.id_modelo
		LEFT JOIN cj_cc_cliente ON an_tradein.id_cliente = cj_cc_cliente.id
	WHERE an_pagos.id_factura = %s
		AND an_pagos.formaPago = 7
		AND cj_cc_anticipo.estatus = 1
	LIMIT 1",// 7 = anticipo, 1 = activo
		valTpDato($idDocumento,"int"));

    $rsTradein = mysql_query($queryTradein);
    if (!$rsTradein) { die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__); }
    $tieneTradein = mysql_num_rows($rsTradein);
    $rowTradein = mysql_fetch_assoc($rsTradein);
    
    if($tieneTradein){
    
        $posY += 11;
        imagestring($img,1,5,$posY,utf8_decode("MARCA"),$textColor);
        imagestring($img,1,35,$posY,": ".strtoupper($rowTradein["nom_marca"]),$textColor);

        imagestring($img,1,100,$posY,utf8_decode("MODELO"),$textColor);
        imagestring($img,1,135,$posY,": ".strtoupper($rowTradein['nom_modelo']),$textColor);
       

        $posY += 9;    
        imagestring($img,1,5,$posY,utf8_decode("VIN"),$textColor);
        imagestring($img,2,34,$posY-3,": ".strtoupper($rowTradein['serial_carroceria']),$textColor);

        $posY += 9; 
        imagestring($img,1,5,$posY,utf8_decode("TABLILLA"),$textColor);
        imagestring($img,2,45,$posY-3,": ".strtoupper($rowTradein["placa"]),$textColor);   
        
        imagestring($img,1,115,$posY,utf8_decode("MILLAS"),$textColor);
        imagestring($img,1,150,$posY,": ".strtoupper($rowTradein['kilometraje']),$textColor);

//        imagestring($img,1,330,$posY,utf8_decode("STOCK"),$textColor);
//        imagestring($img,1,355,$posY,": ".strtoupper($rowTradein['serial_motor']),$textColor);

        $posY += 9;        
        
        imagestring($img,1,5,$posY,utf8_decode("AÑO"),$textColor);
        imagestring($img,1,20,$posY,": ".strtoupper($rowTradein['nom_ano']),$textColor);
        
        imagestring($img,1,65,$posY,utf8_decode("COLOR"),$textColor);
        imagestring($img,1,95,$posY,": ".strtoupper(substr($rowUnidad['color_externo'],0,16)),$textColor);
        
        $posY += 9;
        imagestring($img,1,5,$posY,utf8_decode("ADEUDADO A:"),$textColor);
        imagestring($img,1,55,$posY,": ".strtoupper(substr($rowTradein['nombre_cliente_adeudado'],0,28)),$textColor);        
        
        $posY += 9;
        imagestring($img,1,65,$posY,strtoupper(substr($rowTradein['nombre_cliente_adeudado'],28,28)),$textColor);        

        $posY += 9;
        imagestring($img,1,5,$posY,utf8_decode("CRÉDITO POR AUTO USADO"),$textColor);
        imagestring($img,1,125,$posY,": ".formatoNumero($rowTradein["acv"]),$textColor);
                
        $posY += 9;
        
        imagestring($img,1,5,$posY,utf8_decode("BALANCE ADEUDADO"),$textColor);
        imagestring($img,1,125,$posY,": ".formatoNumero($rowTradein['payoff']),$textColor);

        $posY += 9;
        
        imagestring($img,1,5,$posY,utf8_decode("CRÉDITO A FAVOR"),$textColor);
        imagestring($img,1,125,$posY,": ".formatoNumero($rowTradein['saldoAnticipo']),$textColor);
        

        $posY += 18;
        imagestring($img,1,5,$posY,utf8_decode("CRÉDITO NETO"),$textColor);
        imagestring($img,1,125,$posY,": ".formatoNumero($rowTradein['montoPagado']),$textColor);
        
        $posY += 18;        
        imagestring($img,1,5,$posY,utf8_decode("MONTO POLIZA"),$textColor);
        imagestring($img,1,125,$posY,": ".formatoNumero($rowTradein['']),$textColor);
        
        $posY += 9;
        imagestring($img,1,5,$posY,utf8_decode("VENC. POLIZA"),$textColor);
        imagestring($img,1,125,$posY,": ".formatoNumero($rowTradein['']),$textColor);
        
        $posY += 9;
        imagestring($img,1,5,$posY,utf8_decode("ASEGURADORA: "),$textColor);
        imagestring($img,1,70,$posY,strtoupper(substr($rowTradein[''],0,27)),$textColor);
        
        $posY += 9;        
        imagestring($img,1,70,$posY,strtoupper(substr($rowTradein[''],27,27)),$textColor);
        
        $posY += 18;
        imagestring($img,1,5,$posY,utf8_decode("     AVISO AL COMPRADOR: EL COMPRADOR"),$textColor);
        $posY += 9;
        imagestring($img,1,5,$posY,utf8_decode("CERTIFICA QUE LA UNIDAD TOMADA A CAMBIO"),$textColor);
        $posY += 9;
        imagestring($img,1,5,$posY,utf8_decode("ESTÁ LIBRE DE CUALQUIER GRAVAMEN O VENTA"),$textColor);
        $posY += 9;
        imagestring($img,1,5,$posY,utf8_decode(" CONDICIONAL. ASÍ MISMO, SE PACTA QUE DE"),$textColor);
        $posY += 9;
        imagestring($img,1,5,$posY,utf8_decode(" HABER CUALQUIER DEUDA, EL COMPRADOR SE"),$textColor);
        $posY += 9;
        imagestring($img,1,5,$posY,utf8_decode(" HARÁ RESPONSABLE. EJ. MULTAS, MARBETES,"),$textColor);
        $posY += 9;
        imagestring($img,1,5,$posY,utf8_decode("              SEGURO, ETC."),$textColor);
        
        $posY += 18;
        imagestring($img,1,5,$posY,utf8_decode("CRÉDITO TOTAL"),$textColor);
        imagestring($img,1,125,$posY,": ".formatoNumero($rowTradein['credito total']),$textColor);
        
        $posY += 18;
        imagestring($img,1,5,$posY,utf8_decode("CRÉDITO APROBADO BANCO: ".$rowEncabezado['nombreBanco']),$textColor);
        
        if($rowEncabezado["meses_financiar"] > 0){
            $mesesFinanciar = $rowEncabezado["meses_financiar"]." MESES. APR: ".$rowEncabezado["interes_cuota_financiar"]." %";
        }
            
        $posY += 18;
        imagestring($img,1,5,$posY,"FINANCIAMIENTO EN: ".$mesesFinanciar,$textColor);
        $posY += 9;
        imagestring($img,1,5,$posY,"PRIMER PAGO MENSUAL DE: ".formatoNumero($rowEncabezado["cuotas_financiar"]),$textColor);
        $posY += 9;
        imagestring($img,1,5,$posY,($rowEncabezado["meses_financiar"]-1)." PAGOS MENSUALES DE: ".formatoNumero($rowEncabezado["cuotas_financiar"]),$textColor);

    }
}


$posY = $posYVenta;//a partir de la linea del recuadro inicial
imageline($img, 230, $posY+9, 460, $posY+9, $textColor);//linea H -

$posY += 18;
imagestring($img,1,240,$posY,"PRECIO VENTA:",$textColor);
imagestring($img,1,360,$posY,str_pad(formatoNumero($rowUnidad['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);
$posY += 12;

$queryDet = sprintf("SELECT
        acc.id_tipo_accesorio,
	fact_vent_det_acc.id_factura_detalle_accesorios,
	fact_vent_det_acc.id_accesorio,	
	fact_vent_det_acc.costo_compra,
	fact_vent_det_acc.precio_unitario,
	(CASE
		WHEN fact_vent_det_acc.id_iva = 0 THEN
			CONCAT(acc.nom_accesorio, ' (E)')
		ELSE
			acc.nom_accesorio
	END) AS nom_accesorio,
	fact_vent_det_acc.tipo_accesorio
FROM cj_cc_factura_detalle_accesorios fact_vent_det_acc
	INNER JOIN an_accesorio acc ON (fact_vent_det_acc.id_accesorio = acc.id_accesorio)
#WHERE fact_vent_det_acc.id_factura = %s",
	valTpDato($idDocumento, "int"));
$rsDet = mysql_query($queryDet);
if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$arrayContrato = array();
$arrayAccAdi = array();
$totalAccAdi = 0;
        
while ($rowDet = mysql_fetch_array($rsDet)) {   
    if($rowDet["id_tipo_accesorio"] == 3){
        $arrayContrato[] = array("nom_accesorio" => $rowDet['nom_accesorio'],
                                 "precio_unitario" => $rowDet['precio_unitario']);
    }else{
        $arrayAccAdi[] = array("nom_accesorio" => $rowDet['nom_accesorio'],
                                 "precio_unitario" => $rowDet['precio_unitario']);
        $totalAccAdi += $rowDet['precio_unitario'];
    }
}

//PRUEBA
$arrayContrato[] = array("nom_accesorio" => "Este es un contrato de pruebas1", "precio_unitario" => "5650");
$arrayContrato[] = array("nom_accesorio" => "Este es un contrato de pruebas2", "precio_unitario" => "7000");
$arrayContrato[] = array("nom_accesorio" => "Este es un contrato de pruebas3", "precio_unitario" => "300");
$arrayContrato[] = array("nom_accesorio" => "Este es un contrato de pruebas3", "precio_unitario" => "300");
$arrayContrato[] = array("nom_accesorio" => "Este es un contrato de pruebas3", "precio_unitario" => "300");

foreach ($arrayContrato as $key => $contratos){
    imagestring($img,1,240,$posY,strtoupper(substr($contratos['nom_accesorio'],0,30)),$textColor);	
    imagestring($img,1,360,$posY,str_pad(formatoNumero($contratos['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);
    $posY += 9;
}

imageline($img, 230, $posY+5, 460, $posY+5, $textColor);//linea H -
$posY += 9;
imagestring($img,1,240,$posY,"TOTAL:",$textColor);	
imagestring($img,1,360,$posY,str_pad(formatoNumero($rowUnidad['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);
$posY += 9;
imageline($img, 230, $posY+5, 460, $posY+5, $textColor);//linea H -


$posY += 18;
imagestring($img,1,240,$posY,"OTROS:",$textColor);	
$posY += 18;

foreach ($arrayAccAdi as $key => $accAdi){
    imagestring($img,1,240,$posY,strtoupper($accAdi['nom_accesorio']),$textColor);	
    imagestring($img,1,360,$posY,str_pad(formatoNumero($accAdi['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);
    $posY += 9;
}

$posY += 9;
imagestring($img,1,240,$posY,"TOTAL:",$textColor);	
imagestring($img,1,360,$posY,str_pad(formatoNumero($totalAccAdi), 18, " ", STR_PAD_LEFT),$textColor);
$posY += 18;
imageline($img, 230, $posY, 460, $posY, $textColor);//linea H -

$posY += 9;
imagestring($img,1,240,$posY,"TOTAL VENTA:",$textColor);	
imagestring($img,1,360,$posY,str_pad(formatoNumero($rowUnidad['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);

$posY += 9;
imagestring($img,1,240,$posY,utf8_decode("TOTAL CRÉDITO:"),$textColor);	
imagestring($img,1,360,$posY,str_pad(formatoNumero($rowUnidad['']), 18, " ", STR_PAD_LEFT),$textColor);
$posY += 18;
imageline($img, 230, $posY, 460, $posY, $textColor);//linea H -

$posY += 9;
imagestring($img,1,240,$posY,utf8_decode("BALANCE DE CONTRATO:"),$textColor);	
imagestring($img,1,360,$posY,str_pad(formatoNumero($rowUnidad['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);
$posY += 18;
imageline($img, 230, $posY, 460, $posY, $textColor);//linea H -


$posYVenta += 9;

imageline($img, 230, $posYVenta, 230, $posY, $textColor);//linea V |
imageline($img, 460, $posYVenta, 460, $posY, $textColor);//linea V |

//
//$posY = 500;
//
//$observacionDcto = strtoupper($rowEncabezado['observacionFactura']);
//$posY += 9;
//imagestring($img,1,0,$posY,"OBSERVACIONES :",$textColor);
//$posY += 9;
//imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,0,50))),$textColor);
//$posY += 9;
//imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,50,50))),$textColor);
//$posY += 9;
//imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,100,50))),$textColor);
//$posY += 9;
//imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,150,50))),$textColor);
//$posY += 9;
//imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,200,50))),$textColor);
//
//$posY = 460;
//
//$posY += 9;
//imagestring($img,1,260,$posY,"SUB-TOTAL",$textColor);
//imagestring($img,1,340,$posY,":",$textColor);
//imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['subtotal_factura']), 18, " ", STR_PAD_LEFT),$textColor); // <----
//
//if ($rowEncabezado['subtotal_descuento'] > 0) {
//	$posY += 9;
//	imagestring($img,1,260,$posY,"DESCUENTO",$textColor);
//	imagestring($img,1,340,$posY,":",$textColor);
//	imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['subtotal_descuento']), 18, " ", STR_PAD_LEFT),$textColor); // <----
//}
//			
//// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
//$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1;");
//$rsIva = mysql_query($queryIva);
//if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
//$rowIva = mysql_fetch_assoc($rsIva);
//
//$posY += 9;
//imagestring($img,1,260,$posY,"BASE IMPONIBLE",$textColor);
//imagestring($img,1,340,$posY,":",$textColor);
//imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['base_imponible']), 18, " ", STR_PAD_LEFT),$textColor); // <----
//
//$posY += 9;
//imagestring($img,1,260,$posY,strtoupper($rowIva['observacion']),$textColor);
//imagestring($img,1,340,$posY,":",$textColor);
//imagestring($img,1,345,$posY,str_pad(formatoNumero($rowEncabezado['porcentaje_iva'])."%", 8, " ", STR_PAD_LEFT),$textColor);
//imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['subtotal_iva']), 18, " ", STR_PAD_LEFT),$textColor); // <----
//
//if ($rowEncabezado['subtotal_iva_lujo'] > 0) {
//	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
//	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (2) AND iva.estado = 1 AND iva.activo = 1;");
//	$rsIva = mysql_query($queryIva);
//	if (!$rsIva) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
//	$rowIva = mysql_fetch_assoc($rsIva);
//	
//	$posY += 9;
//	imagestring($img,1,260,$posY,strtoupper($rowIva['observacion']),$textColor);
//	imagestring($img,1,340,$posY,":",$textColor);
//	imagestring($img,1,345,$posY,str_pad(formatoNumero($rowEncabezado['porcentaje_iva_lujo'])."%", 8, " ", STR_PAD_LEFT),$textColor);
//	imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['subtotal_iva_lujo']), 18, " ", STR_PAD_LEFT),$textColor); // <----
//}
//
//$posY += 9;
//imagestring($img,1,260,$posY,"MONTO EXENTO",$textColor);
//imagestring($img,1,340,$posY,":",$textColor);
//imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['monto_exento']), 18, " ", STR_PAD_LEFT),$textColor); // <----
//
//$posY += 9;
//imagestring($img,1,260,$posY,"MONTO EXONERADO",$textColor);
//imagestring($img,1,340,$posY,":",$textColor);
//imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['monto_exonerado']), 18, " ", STR_PAD_LEFT),$textColor); // <----
//
//$posY += 8;
//imagestring($img,1,260,$posY,"------------------------------------------",$textColor);
//
//$posY += 8;
//$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
//imagestring($img,1,260,$posY,"TOTAL FACTURA",$textColor);
//imagestring($img,1,340,$posY,":",$textColor);
//imagestring($img,2,360,$posY,str_pad(formatoNumero($totalFactura), 18, " ", STR_PAD_LEFT),$textColor);

$pageNum="";
$arrayImg[] = "tmp/"."factura_vehiculo".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);



if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, 20, 580, 688);
                $pdf->Image($rutaLogo,15,25,65);
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