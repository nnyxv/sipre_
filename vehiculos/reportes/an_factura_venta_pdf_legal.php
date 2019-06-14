<?php
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Legal');//Letter Legal
//$pdf = new PDF_AutoPrint('P','cm',array(216,330));//mm similar a legal
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idDocumento = $valCadBusq[0];

$queryEncabezado = sprintf("SELECT 
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
        banco.nombreBanco,
	fact_vent.fechaRegistroFactura,
	fact_vent.fechaVencimientoFactura,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.id,
	cliente.direccion AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf,        
	cliente.casa,        
	cliente.casa_comp,        
	cliente.telf_comp,        
	cliente.nit,        
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

// VERIFICA VALORES DE CONFIGURACION (Mostrar Datos del Sistema GNV en la Impresion de la Factura de Venta y Nota de Crédito)
$queryConfigDatosGNV = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 202 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfigDatosGNV = mysql_query($queryConfigDatosGNV, $conex);
if (!$rsConfigDatosGNV) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowConfigDatosGNV = mysql_fetch_assoc($rsConfigDatosGNV);

$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

$posY = 1; //DEAL
imagestring($img,1,280,$posY,str_pad($rowEncabezado['numeroFactura'], 34, " ", STR_PAD_BOTH),$textColor);
$posY += 18;
imagestring($img,1,280,$posY,str_pad($rowEncabezado['id'], 34, " ", STR_PAD_BOTH),$textColor);

$posY += 45; // FECHA DE LA ORDEN
imagestring($img,1,390,$posY,date(spanDateFormat, strtotime($rowEncabezado['fechaRegistroFactura'])),$textColor);

 // FECHA DE NACIMIENTO
$posY +=18;
if($rowEncabezado['fecha_nacimiento'] != NULL && $rowEncabezado['fecha_nacimiento'] != "1969-12-31" && $rowEncabezado['fecha_nacimiento'] != "0000-00-00"){
    imagestring($img,1,340,$posY,date(spanDateFormat, strtotime($rowEncabezado['fecha_nacimiento'])),$textColor);    
}// LICENCIA #LIC
    imagestring($img,1,400,$posY,str_pad(strtoupper($rowEncabezado['ci_cliente']), 14, " ", STR_PAD_LEFT),$textColor);


$posY = 64;//nuevo
// CLIENTE
imagestring($img,1,10,$posY,strtoupper(substr($rowEncabezado['nombre_cliente'],0,50)),$textColor); 
if(strlen($rowEncabezado['nombre_cliente']) <= 40){//si el nombre del cliente es corto, centramos el seguro
    $xSeguro = 220;
}else{//si es mayor, el limite es 60 y el seguro social se mueve a la derecha
    $xSeguro = 270;
}
//NUMERO SEGURO SOCIAL SEG-SOC
imagestring($img,1,$xSeguro,$posY,$rowEncabezado["nit"],$textColor);

//DIRECCION FISICA
//$rowEncabezado['direccion_cliente'] = $rowEncabezado['direccion_cliente']." ASDF OTRA PARTE DE LA DIRECCION AQUI";//test
$direccionCliente = strtoupper(str_replace(",", "", elimCaracter(utf8_encode($rowEncabezado['direccion_cliente']),";")));

if(strlen($direccionCliente) <= 54){//si cabe en 1 linea mostrarla alineada

    $posY += 18;
    imagestring($img,1,50,$posY,trim(substr($direccionCliente,0,54)),$textColor);

}else{// sino cabe en una linea, subirla para colocar dos lineas
    
    $posY += 11;
    imagestring($img,1,50,$posY,trim(substr($direccionCliente,0,54)),$textColor);

    $posY += 7;
    imagestring($img,1,50,$posY,trim(substr($direccionCliente,54,54)),$textColor);
    
}

//DIRECCION POSTAL
$posY = 100;//NUEVO
imagestring($img,1,10,$posY,"AQUI DIRECCION POSTAL",$textColor);


//TELEFONO CASA CLIENTE TEL-CASA
imagestring($img,1,220,$posY,$rowEncabezado['casa']." ".$rowEncabezado['telf'],$textColor);
if(strlen($rowEncabezado['casa']." ".$rowEncabezado['telf']) < 25){//si es menor a 25 hay espacio para nro de trabajo
    
    //TELEFONO TRABAJO CLIENTE TEL-TRABAJO
    if(strlen($rowEncabezado['casa_comp']) <= 10){//si la direccion de trabajo es corta mostrarla
        imagestring($img,1,350,$posY,strtoupper($rowEncabezado['casa_comp'])." ".$rowEncabezado['telf_comp'],$textColor);
    }else{
        imagestring($img,1,350,$posY,$rowEncabezado['telf_comp'],$textColor);
    }    
}



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
	uni_fis.id_condicion_unidad,
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


$posY = 118;
imagestring($img,1,330,$posY,$rowEncabezado['otrotelf'],$textColor);//TEL-OTROS


if ($totalRowsUnidad > 0) {//INFORMACION DEL EQUIPO
   
        //AÑO MARCA MODELO COLOR STOCK
        imagestring($img,1,52,$posY,strtoupper($rowUnidad['nom_ano']),$textColor);
        imagestring($img,1,100,$posY,strtoupper($rowUnidad['nom_marca']),$textColor);
        imagestring($img,1,160,$posY,strtoupper($rowUnidad['nom_modelo']),$textColor);
        imagestring($img,1,226,$posY,strtoupper(substr($rowUnidad['color_externo'],0,14)),$textColor);        
        imagestring($img,1,400,$posY,str_pad(strtoupper($rowUnidad['serial_motor']), 14, " ", STR_PAD_LEFT),$textColor);//STOCK#
    
        $posY += 14;
        imagestring($img,1,10,$posY,strtoupper($rowUnidad['serial_carroceria']),$textColor);
        
        if($rowUnidad['id_condicion_unidad'] == "1"){//nuevo vehiculo
            imagestring($img,1,455,$posY,strtoupper("XXX"),$textColor);
        }else{//usado vehiculo
            imagestring($img,1,455,$posY+12,strtoupper("XXX"),$textColor);
        }
        
        $posY += 14;
        imagestring($img,1,10,$posY,strtoupper($rowUnidad['placa']),$textColor);//TABLILLA VENTA
        imagestring($img,1,120,$posY,strtoupper($rowUnidad['kilometraje']),$textColor);//MILLAJE
        
        $posY += 14;
        imagestring($img,1,380,$posY,str_pad(formatoNumero($rowUnidad['precio_unitario']), 18, " ", STR_PAD_LEFT),$textColor);//PRECIO UNIDAD
}




//ACCESORIOS-ADICIONALES

$queryDet = sprintf("SELECT
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
	fact_vent_det_acc.tipo_accesorio,
        acc.id_tipo_accesorio,
        acc.id_filtro_factura
FROM cj_cc_factura_detalle_accesorios fact_vent_det_acc
	INNER JOIN an_accesorio acc ON (fact_vent_det_acc.id_accesorio = acc.id_accesorio)
WHERE fact_vent_det_acc.id_factura = %s",
	valTpDato($idDocumento, "int"));
$rsDet = mysql_query($queryDet);
if (!$rsDet) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$arrayAccesoriosFact = array();//los que exige la factura para imprimir donde se debe
$arrayAdicionalesFact = array();//los que exige la facturapara imprimir donde se debe

$arrayAccesoriosAdicionales = array();//los que NO exige la factura para iterar en el espacio sobrante

if(mysql_num_rows($rsDet)){
    
    while ($rowDet = mysql_fetch_array($rsDet)) {//separo accesorios y adicionales
        
        //accesorios
        if($rowDet["id_tipo_accesorio"] == "2" && $rowDet["id_filtro_factura"] == ""){//accesorio comun
            $arrayAccesoriosAdicionales[] = array($rowDet['nom_accesorio'],$rowDet['precio_unitario']);
        }elseif($rowDet["id_tipo_accesorio"] == "2" && $rowDet["id_filtro_factura"] != ""){//filtro 1 = ETCH, 2 = LO JACK
            $arrayAccesoriosFact[] = array($rowDet["id_filtro_factura"],$rowDet['nom_accesorio'],$rowDet['precio_unitario']);
        }
        
        //adicionales
        if($rowDet["id_tipo_accesorio"] == "1" && $rowDet["id_filtro_factura"] == ""){//adicional comun
            $arrayAccesoriosAdicionales[] = array($rowDet['nom_accesorio'],$rowDet['precio_unitario']);
        }elseif($rowDet["id_tipo_accesorio"] == "1" && $rowDet["id_filtro_factura"] != ""){//3='TABLILLA', 4='SEGURO', 5='DOBLE SEGURO',6='GAP', 7='CARGOS POR FINANCIAMIENTO',8='CONTRATO DE SERVICIO', 9='TABLILLA2', 10='TRASPASO'
            $arrayAdicionalesFact[$rowDet["id_filtro_factura"]] = array($rowDet['nom_accesorio'],$rowDet['precio_unitario']);
        }
        
    }
}

//prueba test
// $arrayAccesoriosFact = array();
// $arrayAccesoriosFact[] = array(1,"ETCH1",3400);
// $arrayAccesoriosFact[] = array(1,"222",100);

if(COUNT($arrayAccesoriosFact) == 2){//tiene ambos
    $accesorioFactNombre = "ETCH Y LO JACK";
    $accesorioFactPrecio = $arrayAccesoriosFact[0][2] + $arrayAccesoriosFact[1][2];
}else{//solo tiene 1
    $accesorioFactNombre = $arrayAccesoriosFact[0][1];
    $accesorioFactPrecio = $arrayAccesoriosFact[0][2];
}

//Y manual
imagestring($img,1,350,188,strtoupper($accesorioFactNombre),$textColor); //ETCH o LO JACK
imagestring($img,1,380,188,str_pad(formatoNumero($accesorioFactPrecio), 18, " ", STR_PAD_LEFT),$textColor);

//prueba test
//$arrayAccesoriosAdicionales = array();
//$arrayAccesoriosAdicionales[] = array("Accesorio1","10");
//$arrayAccesoriosAdicionales[] = array("Accesorio2","20");
//$arrayAccesoriosAdicionales[] = array("Accesorio3","30");
//$arrayAccesoriosAdicionales[] = array("Accesorio4","40");
//$arrayAccesoriosAdicionales[] = array("Accesorio5","50");

if(COUNT($arrayAccesoriosAdicionales) > 4){
    $arrayLimpiado = limpiarAccAdi($arrayAccesoriosAdicionales);//solo puede imprimirse hasta 4, sino imprimir 3 y el 4 sera "otros" con el total
    $arrayAccesoriosAdicionales = array(); //creo nuevo
    $arrayAccesoriosAdicionales = $arrayLimpiado;
}

//var_dump($arrayAccesoriosAdicionales);

//iteracion del resto
$totalAccesoriosAdicional = 0;
$posY = 200;//nuevo para accesorios y adicionales
foreach ($arrayAccesoriosAdicionales as $item){    
    imagestring($img,1,332,$posY,strtoupper(substr($item[0],0,17)),$textColor);
    imagestring($img,1,380,$posY,str_pad(formatoNumero($item[1]), 18, " ", STR_PAD_LEFT),$textColor);  
    $totalAccesoriosAdicional += $item[1];
    $posY += 12;
}


//prueba test
//$arrayAdicionalesFact[3] = array("tablilla","10");
//$arrayAdicionalesFact[4] = array("seguro","20");
//$arrayAdicionalesFact[5] = array("seguro doble","30");
//$arrayAdicionalesFact[6] = array("gap","40");
//$arrayAdicionalesFact[7] = array("cargos por financiamiento","50");
//$arrayAdicionalesFact[8] = array("contrato de servicio","60");
//$arrayAdicionalesFact[9] = array("tablilla2","70");
//$arrayAdicionalesFact[10] = array("traspaso","80");

if($arrayAdicionalesFact[4][1] != ""){//seguro
    $precioSeguro = $arrayAdicionalesFact[4][1];
}elseif($arrayAdicionalesFact[5][1] != ""){//seguro doble
    $precioSeguro = $arrayAdicionalesFact[5][1];
}


//FIN ACCESORIOS-ADICIONALES

$posY = 248;//nuevo para el resto abajo

//tablilla placa
imagestring($img,1,380,$posY,str_pad(formatoNumero($arrayAdicionalesFact[3][1]), 18, " ", STR_PAD_LEFT),$textColor);//adicional

$posY += 12; ///PRECIO TOTAL total sumatoria precios unitarios y accesorios
imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['subtotal_factura']), 18, " ", STR_PAD_LEFT),$textColor);

$posY += 12;//credito total
imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['Credito total']), 18, " ", STR_PAD_LEFT),$textColor);

$posY += 12;//balance a pagar
imagestring($img,1,380,$posY,str_pad(formatoNumero($rowEncabezado['subtotal_factura']), 18, " ", STR_PAD_LEFT),$textColor);

$posY += 12;//seguro
imagestring($img,1,380,$posY,str_pad(formatoNumero($precioSeguro), 18, " ", STR_PAD_LEFT),$textColor);//adicional

$posY += 12;//GP 'SELLOS, REGISTROS, GR'
imagestring($img,1,380,$posY,str_pad(formatoNumero($arrayAdicionalesFact[6][1]), 18, " ", STR_PAD_LEFT),$textColor);//adicional

$posY += 12;//cargos por financiamiento
imagestring($img,1,380,$posY,str_pad(formatoNumero($arrayAdicionalesFact[7][1]), 18, " ", STR_PAD_LEFT),$textColor);//adicional

$totalFactura = $rowEncabezado['subtotal_factura'] - $rowEncabezado['subtotal_descuento'] + $rowEncabezado['subtotal_iva'] + $rowEncabezado['subtotal_iva_lujo'];
$posY += 17;//balance contrato ESTE CAMPO ES MAS ANCHO
imagestring($img,1,380,$posY,str_pad(formatoNumero($totalFactura), 18, " ", STR_PAD_LEFT),$textColor);

$posY += 14;//contrato de servicios
imagestring($img,1,380,$posY,str_pad(formatoNumero($arrayAdicionalesFact[8][1]), 18, " ", STR_PAD_LEFT),$textColor);//adicional

$posY += 12;//tablilla
imagestring($img,1,380,$posY,str_pad(formatoNumero($arrayAdicionalesFact[9][1]), 18, " ", STR_PAD_LEFT),$textColor);//adicional

$posY += 12;//traspaso
imagestring($img,1,380,$posY,str_pad(formatoNumero($arrayAdicionalesFact[10][1]), 18, " ", STR_PAD_LEFT),$textColor);//adicional



$posY += 19;//acreedor banco
imagestring($img,1,330,$posY,strtoupper(substr($rowEncabezado['nombreBanco'],0,35)),$textColor);

$posY += 26;//vendedor
imagestring($img,1,330,$posY,strtoupper(substr($rowEncabezado['nombre_empleado'],0,35)),$textColor);


//TRADEIN

//CONSULTO SI LA FACTURA TIENE PAGO ANTICIPO Y ES DE TIPO TRADE-IN
$queryTradein = sprintf("SELECT 
	#an_pagos.montoPagado,
	cj_cc_anticipo.saldoAnticipo,
	an_tradein.acv,
	an_tradein.payoff,
	an_marca.nom_marca,
	an_unidad_fisica.placa,
	an_unidad_fisica.serial_carroceria, 
	an_unidad_fisica.kilometraje,
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

$rowTradein = mysql_fetch_assoc($rsTradein);

//vehiculo usado tomado a cambio TRADEIN TRADE-IN
$posY = 176; //nuevo
imagestring($img,1,95,$posY,strtoupper($rowTradein["nom_marca"]),$textColor);//MARCA TRADEIN

$posY += 12;
imagestring($img,1,95,$posY,strtoupper($rowTradein["placa"]),$textColor);//TABLILLA TRADEIN

$posY += 12;
imagestring($img,1,95,$posY,strtoupper($rowTradein["ano_modelo"]),$textColor);//AÑO - MODELO TRADEIN

$posY += 12;
imagestring($img,1,95,$posY,$rowTradein["kilometraje"],$textColor);//MILLAJE TRADEIN 

$posY += 12;
imagestring($img,1,95,$posY,strtoupper($rowTradein["serial_carroceria"]),$textColor);//SERIE TRADEIN

$posY += 12;
imagestring($img,1,95,$posY,strtoupper(substr($rowTradein["nombre_cliente_adeudado"],0,42)),$textColor);//BALANCE ADEUDADO A TRADEIN

$posY += 12;
imagestring($img,1,95,$posY,formatoNumero($rowTradein["acv"]),$textColor);//CREDITO POR CARRO USADO TRADEIN 

$posY += 12;
imagestring($img,1,95,$posY,formatoNumero($rowTradein["payoff"]),$textColor);//BALANCE ADEUDADO TRADEIN

$posY += 12;
imagestring($img,1,95,$posY,"",$textColor);//CREDITO NETO TRADE-IN

$posY += 12;
imagestring($img,1,95,$posY,"",$textColor);//PAGO DE CONTADO TRADEIN

$posY += 12;
imagestring($img,1,95,$posY,formatoNumero($rowTradein["saldoAnticipo"]),$textColor);//CREDITO A SU FAVOR TRADEIN

$posY += 12;
imagestring($img,1,95,$posY,"",$textColor);//OTROS PAGOS TRADEIN

$posY += 12;
imagestring($img,1,95,$posY,formatoNumero(""),$textColor);//CREDITO TOTAL TRADEIN


//balance contrato plazos mensuales

if($rowEncabezado["meses_financiar"] == 0){//evita imprimir 0
    $rowEncabezado["meses_financiar"] = NULL;
}
$posY = 352;//NUEVO
imagestring($img,1,50,$posY,$rowEncabezado["meses_financiar"],$textColor);
imagestring($img,1,190,$posY,formatoNumero($rowEncabezado["cuotas_financiar"]),$textColor);

$posY += 14;
imagestring($img,1,50,$posY,"",$textColor);
imagestring($img,1,190,$posY,formatoNumero(""),$textColor);



//observaciones double insurance con monto
$posY = 450;

$observacionDcto = strtoupper($rowEncabezado['observacionFactura']);

$posY += 9;
imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,0,40))),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,40,40))),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,80,40))),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,120,40))),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,strtoupper(trim(substr($observacionDcto,160,40))),$textColor);



$arrayImg[] = "tmp/"."factura_vehiculo".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, 55, 580, 688);//x15 y80 resol 580 x 688
	}
}

$pdf->SetDisplayMode(80);
$pdf->SetTitle('Impresion Factura Legal');
//$pdf->AutoPrint(true);
$pdf->Output("Impresion Factura Legal.pdf","I");

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		if(file_exists($valor)) unlink($valor);
	}
}


/**
 * Solo pueden imprimir hasta 4 lineas en la factura, asi que los accesorios / adicionales
 * deben ser 3 items y el resto una sumatoria y colocarlos en "otros"
 * @param Array $arrayAccesoriosAdicionales Array que contiene array
 * @return Array Array que contiene array, limpio a 4 lineas
 */
function limpiarAccAdi(&$arrayAccesoriosAdicionales){
    $descripcion1 = $arrayAccesoriosAdicionales[0][0];//formato array(array('descripcion', 'precio'))
    $precio1 = $arrayAccesoriosAdicionales[0][1];
    $descripcion2 = $arrayAccesoriosAdicionales[1][0];
    $precio2 = $arrayAccesoriosAdicionales[1][1];
    $descripcion3 = $arrayAccesoriosAdicionales[2][0];
    $precio3 = $arrayAccesoriosAdicionales[2][1];
    
    $arrayCortado = array_slice($arrayAccesoriosAdicionales, 3); //quito los primeros 3 el resto se suma y se coloca como "otros"
    
    $totalOtros = 0;
    
    foreach($arrayCortado as $item){
        $totalOtros += $item[1];
    }
    
    $arrayLimpio = array();
    $arrayLimpio[] = array($descripcion1,$precio1);
    $arrayLimpio[] = array($descripcion2,$precio2);
    $arrayLimpio[] = array($descripcion3,$precio3);
    $arrayLimpio[] = array("otros",$totalOtros);
    
    return $arrayLimpio;
}

function formatoNumero($monto){//devuelve null si es cero para imprimir en blanco
    
    if($monto != "" && $monto != "0.00" && $monto != "0"){
        return number_format($monto, 2, ".", ",");
    }
    
}

?>