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
	pedido.id_banco_financiar
FROM an_adicionales_contrato contrato
INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact.numeroPedido = contrato.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
INNER JOIN an_pedido pedido ON (contrato.id_pedido = pedido.id_pedido)
LEFT JOIN crm_perfil_prospecto perfil ON (cliente.id = perfil.id)
WHERE contrato.id_adi_contrato = %s
ORDER BY idFactura DESC LIMIT 1;",
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
	uni_fis.titulo_vehiculo,
	uni_fis.registro_legalizacion,
	uni_fis.id_condicion_unidad,
	cond_unidad.descripcion AS condicion_unidad,
	uni_fis.serial_carroceria,
	uni_fis.serial_motor,
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


//INFORMACION DEL DEUDOR
$posY = 264;
imagestring($img, 1, 12, $posY, strtoupper($rowContrato['nombre_cliente']), $textColor);
imagestring($img, 1, 352, $posY, strtoupper($rowContrato['nit_cliente']), $textColor);

$posY += 20;
$direccionCliente = strtoupper(str_replace(";", "", elimCaracter(utf8_encode($rowContrato['direccion_cliente']),";")));
imagestring($img, 1, 12, $posY, $direccionCliente, $textColor);

$posY += 19;
imagestring($img, 1, 12, $posY, strtoupper($rowContrato['casa_cliente']), $textColor);
imagestring($img, 1, 157, $posY, strtoupper($rowContrato['calle_cliente']), $textColor);
imagestring($img, 1, 322, $posY, strtoupper($rowContrato['cod_postal_cliente']), $textColor);

$posY += 19;
imagestring($img, 1, 12, $posY, strtoupper($rowContrato['municipio_cliente']), $textColor);
imagestring($img, 1, 322, $posY, strtoupper($rowContrato['cod_postal_cliente']), $textColor);

//INFORMACION POSTAL DEL DEUDOR
$posY += 18;
$direccionCliente = strtoupper(str_replace(";", "", elimCaracter(utf8_encode($rowContrato['direccion_postal']),";")));
imagestring($img, 1, 12, $posY, $direccionCliente, $textColor);

$posY += 18;
imagestring($img, 1, 12, $posY, strtoupper($rowContrato['casa_postal']), $textColor);
imagestring($img, 1, 157, $posY, strtoupper($rowContrato['calle_postal']), $textColor);
imagestring($img, 1, 322, $posY, strtoupper($rowContrato['estado_postal']), $textColor);

$posY += 18;
imagestring($img, 1, 12, $posY, strtoupper($rowContrato['municipio_postal']), $textColor);
imagestring($img, 1, 322, $posY, strtoupper($rowContrato['estado_postal']), $textColor);

//FECHA NACIMIENTO Y NUMERO DE LICENCIA
$posY += 18;
if($rowContrato['fecha_nacimiento'] != ""){
	imagestring($img, 1, 12, $posY, strtoupper(date('d M Y',strtotime($rowContrato['fecha_nacimiento']))), $textColor);
}
imagestring($img, 1, 322, $posY, strtoupper($rowContrato['licencia_cliente']), $textColor);



//INFORMACION SOBRE EL VEHICULO
$posY = 425;
imagestring($img, 1, 12, $posY, strtoupper($rowUnidad['nom_marca']), $textColor);
imagestring($img, 1, 100, $posY, strtoupper(substr($rowUnidad['nom_modelo'],0,15)), $textColor);
imagestring($img, 1, 169, $posY, strtoupper($rowUnidad['nom_ano']), $textColor);
imagestring($img, 1, 203, $posY, strtoupper(substr($rowUnidad['color_externo'],0,15)), $textColor);

if($rowUnidad['id_condicion_unidad'] == 1){
	imagestring($img, 2, 341, $posY, 'X', $textColor);
}else{
	imagestring($img, 2, 407, $posY, 'X', $textColor);
}

$posY += 18;
if($rowUnidad['id_condicion_unidad'] == 1){
	imagestring($img, 1, 12, $posY, strtoupper("PROVISIONAL"), $textColor);
}else{
	imagestring($img, 1, 12, $posY, strtoupper($rowUnidad['registro_legalizacion']), $textColor);
}
imagestring($img, 1, 100, $posY, strtoupper($rowUnidad['cil_uni_bas']), $textColor);
imagestring($img, 1, 178, $posY, strtoupper($rowUnidad['serial_carroceria']), $textColor);
imagestring($img, 1, 351, $posY, strtoupper($rowUnidad['placa']), $textColor);

$posY += 17;
imagestring($img, 1, 12, $posY, strtoupper('PRIVADO'), $textColor);//$rowUnidad['tipo_placa']
imagestring($img, 1, 100, $posY, strtoupper($rowUnidad['cab_uni_bas']), $textColor);
imagestring($img, 1, 200, $posY, strtoupper($rowUnidad['pto_uni_bas']), $textColor);
imagestring($img, 1, 310, $posY, strtoupper($rowUnidad['cap_uni_bas']), $textColor);
//imagestring($img, 1, 410, $posY, strtoupper('PESO'), $textColor);

$posY += 18;
imagestring($img, 1, 12, $posY, strtoupper(date('d M Y',strtotime($rowContrato['fechaRegistroFactura']))), $textColor);
imagestring($img, 1, 117, $posY, strtoupper(substr($rowUnidad['nom_origen'],0,15)), $textColor);
if($rowUnidad['id_condicion_unidad'] == 1){
	imagestring($img, 1, 212, $posY,"", $textColor);
}else{
	imagestring($img, 1, 212, $posY, strtoupper($rowUnidad['titulo_vehiculo']), $textColor);
}
//imagestring($img, 1, 305, $posY, strtoupper('NRO DE CONT'), $textColor);
imagestring($img, 1, 400, $posY, strtoupper(date('d M Y',strtotime($rowContrato['fechaRegistroFactura']))), $textColor);

$posY += 18;
imagestring($img, 1, 12, $posY, strtoupper($rowEmp['nombre_empresa']), $textColor);
imagestring($img, 1, 202, $posY, strtoupper($rowEmp['licencia_venta']), $textColor);
imagestring($img, 1, 372, $posY, strtoupper($rowEmp['codigo_patronal']), $textColor);




$arrayImg[] = "tmp/contrato_venta_gravamen_legal".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, 15, 580, 688);
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