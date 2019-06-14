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
	CONCAT_WS(' ',cliente.urbanizacion_postal, cliente.ciudad_postal) AS direccion_postal,
	cliente.calle_postal, 
	cliente.casa_postal,
	cliente.municipio_postal,
	cliente.estado_postal,
	an_ped_vent.id_banco_financiar,
	an_ped_vent.fecha_reserva_venta,
	an_ped_vent.fecha_entrega
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	LEFT JOIN crm_perfil_prospecto perfil ON (cliente.id = perfil.id)
	INNER JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
	LEFT JOIN an_adicionales_contrato contrato ON (an_ped_vent.id_pedido = contrato.id_pedido)
WHERE an_ped_vent.id_pedido = %s
	OR contrato.id_adi_contrato = %s
ORDER BY idFactura DESC LIMIT 1;",
	valTpDato($idPedido,"int"),
	valTpDato($idContrato,"int"));
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowContrato = mysql_fetch_assoc($rs);

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
$queryUnidad = sprintf("SELECT 
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
	uni_fis.titulo_vehiculo,
	uni_fis.kilometraje,
	uni_fis.registro_legalizacion,
	uni_fis.tipo_placa,
	color1.des_color AS color_externo,
	cxc_fact_det_vehic.precio_unitario,
	uni_bas.com_uni_bas,
	uni_bas.cil_uni_bas,
	uni_bas.cab_uni_bas,
	uni_bas.pto_uni_bas,
	uni_bas.cap_uni_bas,
	codigo_unico_conversion,
	marca_kit,
	marca_cilindro,
	modelo_regulador,
	serial1,
	serial_regulador,
	capacidad_cilindro,
	capacidad,
	fecha_elaboracion_cilindro,
	origen.nom_origen
FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
	INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
	INNER JOIN an_color color1 ON (uni_fis.id_color_externo1 = color1.id_color)
	LEFT JOIN an_origen origen ON (uni_fis.id_origen = origen.id_origen)
WHERE cxc_fact_det_vehic.id_factura = %s",
	valTpDato($idDocumento, "int"));
$rsUnidad = mysql_query($queryUnidad);
if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsUnidad = mysql_num_rows($rsUnidad);
$rowUnidad = mysql_fetch_assoc($rsUnidad);

//echo "<pre>"; var_dump($rowUnidad); var_dump($queryUnidad); exit;

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

//SELECCION EQUIZ X
$posY = 65;
//imagestring($img, 2, 9, $posY, 'X', $textColor);
//imagestring($img, 2, 118, $posY, 'X', $textColor);
//imagestring($img, 2, 206, $posY, 'X', $textColor);
//imagestring($img, 2, 326, $posY, 'X', $textColor);

$posY = 94;
//imagestring($img, 2, 177, $posY, 'X', $textColor);
//imagestring($img, 2, 295, $posY, 'X', $textColor);

//INFORMACION DEL ACREEDOR
$posY = 108;
imagestring($img, 1, 0, $posY, strtoupper($rowBanco['nombreBanco']), $textColor);
//imagestring($img, 1, 330, $posY, strtoupper('NRO ID PATRONAL'), $textColor);

$posY += 18;
$direccionBanco = strtoupper(str_replace(";", "", elimCaracter(utf8_encode($rowBanco['direccion_completa']),";")));
imagestring($img, 1, 0, $posY, $direccionBanco, $textColor);

$posY += 18;
imagestring($img, 1, 0, $posY, strtoupper($rowBanco['casa']), $textColor);
imagestring($img, 1, 95, $posY, strtoupper($rowBanco['calle']), $textColor);
imagestring($img, 1, 340, $posY, strtoupper($rowBanco['cod_postal_banco']), $textColor);

$posY += 18;
imagestring($img, 1, 0, $posY, strtoupper($rowBanco['municipio']), $textColor);
imagestring($img, 1, 340, $posY, strtoupper($rowBanco['cod_postal_banco']), $textColor);

//INFORMACION POSTAL DEL ACREEDOR
$posY += 17;
$direccionBancoPostal = strtoupper(str_replace(";", "", elimCaracter(utf8_encode($rowBanco['direccion_postal']),";")));
imagestring($img, 1, 0, $posY, strtoupper($direccionBancoPostal), $textColor);

$posY += 18;
imagestring($img, 1, 0, $posY, strtoupper($rowBanco['casa_postal']), $textColor);
imagestring($img, 1, 95, $posY, strtoupper($rowBanco['calle_postal']), $textColor);
imagestring($img, 1, 340, $posY, strtoupper($rowBanco['cod_postal_postal']), $textColor);

$posY += 18;
imagestring($img, 1, 0, $posY, strtoupper($rowBanco['municipio_postal']), $textColor);
imagestring($img, 1, 340, $posY, strtoupper($rowBanco['cod_postal_postal']), $textColor);



//INFORMACION DEL DEUDOR
$posY = 252;
imagestring($img, 1, 0, $posY, strtoupper($rowContrato['nombre_cliente']), $textColor);
imagestring($img, 1, 340, $posY, strtoupper($rowContrato['nit_cliente']), $textColor);

$posY += 18;
$direccionCliente = strtoupper(str_replace(";", "", elimCaracter(utf8_encode($rowContrato['direccion_cliente']),";")));
imagestring($img, 1, 0, $posY, $direccionCliente, $textColor);

$posY += 18;
imagestring($img, 1, 0, $posY, strtoupper($rowContrato['casa_cliente']), $textColor);
imagestring($img, 1, 95, $posY, strtoupper($rowContrato['calle_cliente']), $textColor);
imagestring($img, 1, 340, $posY, strtoupper($rowContrato['cod_postal_cliente']), $textColor);

$posY += 18;
imagestring($img, 1, 0, $posY, strtoupper($rowContrato['municipio_cliente']), $textColor);
imagestring($img, 1, 340, $posY, strtoupper($rowContrato['cod_postal_cliente']), $textColor);

//INFORMACION POSTAL DEL DEUDOR
$posY += 17;
$direccionCliente = strtoupper(str_replace(";", "", elimCaracter(utf8_encode($rowContrato['direccion_postal']),";")));
imagestring($img, 1, 0, $posY, $direccionCliente, $textColor);

$posY += 18;
imagestring($img, 1, 0, $posY, strtoupper($rowContrato['casa_postal']), $textColor);
imagestring($img, 1, 95, $posY, strtoupper($rowContrato['calle_postal']), $textColor);
imagestring($img, 1, 340, $posY, strtoupper($rowContrato['estado_postal']), $textColor);

$posY += 18;
imagestring($img, 1, 0, $posY, strtoupper($rowContrato['municipio_postal']), $textColor);
imagestring($img, 1, 340, $posY, strtoupper($rowContrato['estado_postal']), $textColor);

//FECHA NACIMIENTO Y NUMERO DE LICENCIA
$posY += 18;
if ($rowContrato['fecha_nacimiento'] != ""){
	imagestring($img, 1, 0, $posY, strtoupper(date('d M Y',strtotime($rowContrato['fecha_nacimiento']))), $textColor);
}
imagestring($img, 1, 330, $posY, strtoupper($rowContrato['licencia_cliente']), $textColor);



//INFORMACION SOBRE EL VEHICULO
$posY = 413;
imagestring($img, 1, 0, $posY, strtoupper($rowUnidad['nom_marca']), $textColor);
imagestring($img, 1, 105, $posY, strtoupper(substr($rowUnidad['nom_modelo'],0,15)), $textColor);
imagestring($img, 1, 188, $posY, strtoupper($rowUnidad['nom_ano']), $textColor);
imagestring($img, 1, 240, $posY, strtoupper(substr($rowUnidad['color_externo'],0,15)), $textColor);

if ($rowUnidad['id_condicion_unidad'] == 1){
	imagestring($img, 2, 353, $posY-3, 'X', $textColor);
} else {
	imagestring($img, 2, 412, $posY-3, 'X', $textColor);
}

$posY += 18;
if ($rowUnidad['id_condicion_unidad'] == 1){
	imagestring($img, 1, 0, $posY, strtoupper("PROVISIONAL"), $textColor);
} else {
	imagestring($img, 1, 0, $posY, strtoupper($rowUnidad['registro_legalizacion']), $textColor);
}
imagestring($img, 1, 120, $posY, strtoupper($rowUnidad['cil_uni_bas']), $textColor);
imagestring($img, 1, 225, $posY, strtoupper($rowUnidad['serial_carroceria']), $textColor);
imagestring($img, 1, 390, $posY, strtoupper($rowUnidad['placa']), $textColor);

$posY += 17;
imagestring($img, 1, 0, $posY, strtoupper('PRIVADO'), $textColor);//$rowUnidad['tipo_placa']
imagestring($img, 1, 120, $posY, strtoupper($rowUnidad['cab_uni_bas']), $textColor);
imagestring($img, 1, 225, $posY, strtoupper($rowUnidad['pto_uni_bas']), $textColor);
imagestring($img, 1, 310, $posY, strtoupper($rowUnidad['cap_uni_bas']), $textColor);
//imagestring($img, 1, 410, $posY, strtoupper('PESO'), $textColor);

$posY += 18;
imagestring($img, 1, 0, $posY, strtoupper(date('d M Y',strtotime($rowContrato['fecha_reserva_venta']))), $textColor);
imagestring($img, 1, 105, $posY, strtoupper(substr($rowUnidad['nom_origen'],0,15)), $textColor);
if ($rowUnidad['id_condicion_unidad'] == 1){
	imagestring($img, 1, 200, $posY,"", $textColor);
} else {
	imagestring($img, 1, 200, $posY, strtoupper($rowUnidad['titulo_vehiculo']), $textColor);
}
//imagestring($img, 1, 305, $posY, strtoupper('NRO DE CONT'), $textColor);
imagestring($img, 1, 400, $posY, strtoupper(date('d M Y',strtotime($rowContrato['fecha_reserva_venta']))), $textColor);

$posY += 18;
imagestring($img, 1, 0, $posY, strtoupper($rowEmp['nombre_empresa']), $textColor);
imagestring($img, 1, 190, $posY, strtoupper($rowEmp['licencia_venta']), $textColor);
imagestring($img, 1, 360, $posY, strtoupper($rowEmp['codigo_patronal']), $textColor);

$arrayImg[] = "tmp/contrato_venta_gravamen_legal".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


// VERIFICA VALORES DE CONFIGURACION (Margen Superior e Izquierdo para Documento de Impresion (SOLICITUD PRESENTACIÓN GRAVAMEN MOBILIARIO))
$queryConfig211 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 211 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig211 = mysql_query($queryConfig211, $conex);
if (!$rsConfig211) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig211 = mysql_num_rows($rsConfig211);
$rowConfig211 = mysql_fetch_assoc($rsConfig211);

$valorConfig211 = explode("|",$rowConfig211['valor']);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indiceImg => $valorImg) {
		$pdf->AddPage();
		
		$pdf->Image($valorImg, $valorConfig211[1], $valorConfig211[0], 580, 688);
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