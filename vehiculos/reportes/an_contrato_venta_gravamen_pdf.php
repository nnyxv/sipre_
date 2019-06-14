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
					CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
					cliente.direccion AS direccion_cliente,
					cliente.municipio AS municipio_cliente,
					cliente.estado AS cod_postal_cliente,
					cliente.ciudad AS ciudad_cliente,
					CONCAT_WS(' ', co_cliente.nombre, co_cliente.apellido) AS nombre_co_cliente,
					co_cliente.direccion AS direccion_co_cliente,
					co_cliente.municipio AS municipio_co_cliente,
					co_cliente.estado AS cod_postal_co_cliente,
					co_cliente.ciudad AS ciudad_co_cliente					
				FROM an_adicionales_contrato contrato
				INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact.numeroPedido = contrato.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
				INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
				LEFT JOIN cj_cc_cliente co_cliente ON (contrato.id_co_cliente = co_cliente.id)
				WHERE contrato.id_adi_contrato = %s
				ORDER BY idFactura DESC LIMIT 1;",
		valTpDato($idContrato,"int"));	
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowContrato = mysql_fetch_array($rs);

$idDocumento = $rowContrato["idFactura"];

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
	fecha_elaboracion_cilindro
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
	
$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
	
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$textColor2 = imagecolorallocate($img, 255, 0, 0);

//INFORMACION DEL VEHICULO
$posY = 113;
imagestring($img, 1, 190, $posY, utf8_encode(strtoupper($rowUnidad['serial_carroceria'])), $textColor);

$posY += 10;
imagestring($img, 1, 50, $posY, utf8_encode(strtoupper($rowUnidad['nom_ano'])), $textColor);
imagestring($img, 1, 168, $posY, utf8_encode(strtoupper(substr($rowUnidad['nom_marca'],0,16))), $textColor);
imagestring($img, 1, 270, $posY, utf8_encode(strtoupper($rowUnidad['nom_modelo'])), $textColor);

//INFORMACION DEL CLIENTE
$posY = 152;
imagestring($img, 1, 90, $posY, utf8_encode(strtoupper($rowContrato['nombre_cliente'])), $textColor);

$posY += 20;
$direccionCliente = strtoupper(str_replace(";", "", elimCaracter(utf8_encode($rowContrato['direccion_cliente']),";")));
imagestring($img, 1, 65, $posY, utf8_encode(strtoupper($direccionCliente)), $textColor);

$posY += 10;
imagestring($img, 1, 55, $posY, utf8_encode(strtoupper($rowContrato['ciudad_cliente'])), $textColor);
imagestring($img, 1, 270, $posY, utf8_encode(strtoupper(substr($rowContrato['municipio_cliente'],0,14))), $textColor);
imagestring($img, 1, 410, $posY, utf8_encode(strtoupper($rowContrato['cod_postal_cliente'])), $textColor);

//INFORMACION CO CLIENTE
$posY = 206;
imagestring($img, 1, 110, $posY, utf8_encode(strtoupper($rowContrato['nombre_co_cliente'])), $textColor);

$posY += 20;
$direccionCliente = strtoupper(str_replace(";", "", elimCaracter(utf8_encode($rowContrato['direccion_co_cliente']),";")));
imagestring($img, 1, 65, $posY, utf8_encode(strtoupper($direccionCliente)), $textColor);

$posY += 10;
imagestring($img, 1, 55, $posY, utf8_encode(strtoupper($rowContrato['ciudad_co_cliente'])), $textColor);
imagestring($img, 1, 270, $posY, utf8_encode(strtoupper(substr($rowContrato['municipio_co_cliente'],0,14))), $textColor);
imagestring($img, 1, 395, $posY, utf8_encode(strtoupper($rowContrato['cod_postal_co_cliente'])), $textColor);


$arrayImg[] = "tmp/contrato_venta_gravamen".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, 40, 580, 688);
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