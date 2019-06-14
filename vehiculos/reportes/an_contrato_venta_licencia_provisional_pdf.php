<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require_once("../../connections/conex.php");
/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter'); //normal, puntos/pixel ,array (ancho,alto)
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$vista = $_GET['view'];
$idPedido = $_GET["idPedido"];
$idContrato = $_GET["id"];

$query = sprintf("SELECT 
	cliente.id,
	cxc_fact.idFactura,
	cxc_fact.id_empresa,
	cxc_fact.fechaRegistroFactura,
	perfil.fecha_nacimiento,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.municipio AS municipio_cliente,
	cliente.estado AS cod_postal_cliente,
	cliente.ciudad AS ciudad_cliente,
	cliente.licencia AS licencia_cliente,
	cliente.nit AS nit_cliente,
	cliente.casa AS casa_cliente,
	cliente.calle AS calle_cliente,
	CONCAT_WS(' ',cliente.urbanizacion_postal, cliente.ciudad_postal, cliente.calle_postal, cliente.casa_postal, cliente.municipio_postal, cliente.estado_postal) AS direccion_postal,
	pedido.id_banco_financiar
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	LEFT JOIN crm_perfil_prospecto perfil ON (cliente.id = perfil.id)
	INNER JOIN an_pedido pedido ON (cxc_fact.numeroPedido = pedido.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
	LEFT JOIN an_adicionales_contrato contrato ON (pedido.id_pedido = contrato.id_pedido)
WHERE pedido.id_pedido = %s
	OR contrato.id_adi_contrato = %s
ORDER BY idFactura DESC LIMIT 1;",
	valTpDato($idPedido,"int"),
	valTpDato($idContrato,"int"));
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowContrato = mysql_fetch_assoc($rs);

//echo "<pre>"; var_dump($rowContrato); exit;

$idDocumento = $rowContrato["idFactura"];
$idEmpresa = $rowContrato['id_empresa'];
$idBanco = $rowContrato['id_banco_financiar'];;

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT 
	vw_iv_emp_suc.*,
	empresa.licencia_venta,
	empresa.codigo_patronal,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
INNER JOIN pg_empresa empresa ON (vw_iv_emp_suc.id_empresa_reg = empresa.id_empresa)
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);


// BUSCA LOS DATOS DE LA UNIDAD
$queryUnidad = "SELECT 
	uni_bas.nom_uni_bas,
	marca.nom_marca,
	modelo.nom_modelo,
	vers.nom_version,
	ano.nom_ano,
	uni_fis.id_unidad_fisica,
	uni_fis.placa,
	uni_fis.id_condicion_unidad,
	cond_unidad.descripcion AS condicion_unidad,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
	uni_fis.kilometraje,
	uni_fis.registro_legalizacion,
	uni_fis.tipo_placa,
	uni_fis.serial_carroceria,
	DATE_FORMAT(ped_vent.fecha_entrega,'%d %b %Y') AS fech_ent,
	DATE_FORMAT(DATE_ADD(ped_vent.fecha_entrega,INTERVAL 1 YEAR),'%b/%Y') AS fech_sig,
	color1.des_color AS color_externo,
	cxc_fact_det_vehic.precio_unitario,
	uni_bas.cil_uni_bas AS cilindros,
	uni_bas.pto_uni_bas AS puertas,
	uni_bas.cab_uni_bas AS cab_fuerza,
	origen.nom_origen
FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic 
	INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_vehic.id_factura = cxc_fact.idFactura)
	INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)	
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
	INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
	LEFT JOIN an_origen origen ON (uni_fis.id_origen = origen.id_origen)
WHERE cxc_fact_det_vehic.id_factura = {$idDocumento}";// echo "<pre>"; var_dump($queryUnidad);
$rsUnidad = mysql_query($queryUnidad);
if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsUnidad = mysql_num_rows($rsUnidad);
$rowUnidad = mysql_fetch_assoc($rsUnidad);// echo "<pre>"; var_dump($rowUnidad); exit;


//BUSCA LOS DATOS DEL BANCO ACREEDOR
$query = sprintf("SELECT 
	banco.nombreBanco,	
	banco.urbanizacion,
	banco.calle,
	banco.casa,
	banco.municipio,
	banco.ciudad,
	banco.estado AS cod_postal_banco,
	banco.direccion_completa,
	banco.urbanizacion_postal,
	banco.calle_postal,
	banco.casa_postal,
	banco.municipio_postal,
	banco.ciudad_postal,
	banco.estado_postal AS cod_postal_postal,
	banco.direccion_postal
FROM bancos banco
WHERE banco.idBanco = %s",
	valTpDato($idBanco, "int"));
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowBanco = mysql_fetch_assoc($rs);


$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
	
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$textColor2 = imagecolorallocate($img, 255, 0, 0);

// AÑO, MARCA, MODELO
$posY = 62;
imagestring($img, 2, 5, $posY,  strtoupper($rowUnidad['nom_ano']."  ".$rowUnidad['nom_marca']."  ".$rowUnidad['nom_modelo']), $textColor);


// DECRIPCION Y CLASIFICACION DEL VEHICULO
$posY = 90;
imagestring($img, 1, 95, $posY, strtoupper($rowUnidad['fech_ent']), $textColor);


// AÑO, MARCA, MODELO (SEPARADO), COLOR, PUERTAS, CILINDROS, CAB FUERZA
$posY += 20;
imagestring($img, 1, 5, $posY, strtoupper(substr($rowUnidad['nom_ano'],2,2)), $textColor);
imagestring($img, 1, 40, $posY, strtoupper(substr($rowUnidad['nom_marca'],0,6)), $textColor);
imagestring($img, 1, 75, $posY, strtoupper(substr($rowUnidad['nom_modelo'],0,10)), $textColor);
imagestring($img, 1, 140, $posY, strtoupper(substr($rowUnidad['color_externo'],0,10)), $textColor);
imagestring($img, 1, 208, $posY, strtoupper($rowUnidad['puertas']), $textColor);
imagestring($img, 1, 238, $posY, strtoupper($rowUnidad['cilindros']), $textColor);
imagestring($img, 1, 260, $posY, strtoupper($rowUnidad['cab_fuerza']), $textColor);


// VIN
$posY += 25;
imagestring($img, 1, 6, $posY, strtoupper($rowUnidad['serial_carroceria']), $textColor);


// VENTA CONDICIONAL = NOMBRE BANCO
$posY += 18;
imagestring($img, 1, 80, $posY, strtoupper($rowBanco['nombreBanco']), $textColor);


// FECHA EXPIRACION
$posY += 21;
imagestring($img, 1, 225, $posY, strtoupper($rowUnidad['fech_sig']), $textColor);


// INFORMACION DEL DEUDOR
$posY += 20;
imagestring($img, 1, 6, $posY, strtoupper($rowContrato['nombre_cliente']), $textColor);
$arrayDireccionCliente = wordwrap(str_replace("\n","<br>",str_replace(";", "", $rowContrato['direccion_cliente'])), 40, "<br>");
$arrayValor = explode("<br>",$arrayDireccionCliente);
if (isset($arrayValor)) {
	foreach ($arrayValor as $indiceValor => $valorValor) {
		$posY += 8;
		imagestring($img,1,6,$posY,strtoupper((trim($valorValor))),$textColor);
	}
}


// INFORMACION POSTAL DEL DEUDOR
$posY += 30;
$arrayDireccionPostal = wordwrap(str_replace("\n","<br>",str_replace(";", "", $rowContrato['direccion_postal'])), 40, "<br>");
$arrayValor = explode("<br>",$arrayDireccionPostal);
if (isset($arrayValor)) {
	foreach ($arrayValor as $indiceValor => $valorValor) {
		$posY += 8;
		imagestring($img,1,6,$posY,strtoupper((trim($valorValor))),$textColor);
	}
}


// NUMERO DE LICENCIA
$posY += 27;
imagestring($img, 1, 150, $posY, strtoupper($rowContrato['licencia_cliente']), $textColor);

$arrayImg[] = "tmp/contrato_venta_gravamen_legal".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


// VERIFICA VALORES DE CONFIGURACION (Margen Superior e Izquierdo para Documento de Impresion (LICENCIA PROVISIONAL (DTOP)))
$queryConfig212 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 212 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig212 = mysql_query($queryConfig212, $conex);
if (!$rsConfig212) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig212 = mysql_num_rows($rsConfig212);
$rowConfig212 = mysql_fetch_assoc($rsConfig212);

$valorConfig212 = explode("|",$rowConfig212['valor']);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indiceImg => $valorImg) {
		$pdf->AddPage();
		
		$pdf->Image($valorImg, $valorConfig212[1], $valorConfig212[0], 580, 688);
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();

if (isset($arrayImg)) {
	foreach ($arrayImg as $indiceImg => $valorImg) {
		if (file_exists($valorImg)) unlink($valorImg);
	}
}

?>