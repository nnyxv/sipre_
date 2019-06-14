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
	cliente.telf,
	cliente.telf_comp,
	cliente.correo,
	contrato.id_gerente_fin,
	poliza.nombre_poliza,
	poliza.dir_agencia,
	poliza.telf_agencia,
	poliza.nom_comp_seguro,
	banco.nombreBanco
FROM an_adicionales_contrato contrato
INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact.numeroPedido = contrato.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
INNER JOIN an_pedido pedido ON (contrato.id_pedido = pedido.id_pedido)
INNER JOIN an_poliza poliza ON (pedido.id_poliza = poliza.id_poliza)
INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
INNER JOIN bancos banco ON (pedido.id_banco_financiar = banco.idBanco)
LEFT JOIN crm_perfil_prospecto perfil ON (cliente.id = perfil.id)
WHERE contrato.id_adi_contrato = %s
ORDER BY idFactura DESC LIMIT 1;",
	valTpDato($idContrato,"int"));	
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowContrato = mysql_fetch_assoc($rs);

$idDocumento = $rowContrato["idFactura"];
$idEmpresa = $rowContrato['id_empresa'];

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

// BUSCA DETALLE DEL HUNTER
$queryAccesorio = sprintf("SELECT 
	accesorio.nom_accesorio,
	det_fact_accesorio.precio_unitario
FROM cj_cc_factura_detalle_accesorios det_fact_accesorio
INNER JOIN an_accesorio accesorio ON (det_fact_accesorio.id_accesorio = accesorio.id_accesorio)
WHERE det_fact_accesorio.id_factura = %s AND accesorio.id_filtro_factura IN (12)",
	valTpDato($idDocumento, "int"));
$rsAccesorio = mysql_query($queryAccesorio);
if (!$rsAccesorio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalAccesorio = mysql_num_rows($rsAccesorio);
$rowAccesorio = mysql_fetch_assoc($rsAccesorio);

if($totalAccesorio == 0){
	die("No posee adicional tipo HUNTER");
}

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
	uni_fis.kilometraje,
	uni_fis.registro_legalizacion,
	uni_fis.tipo_placa,
	color1.nom_color AS color_externo,
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

	
$img = @imagecreate(470, 558) or die("No se puede crear la imagen");
	
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$textColor2 = imagecolorallocate($img, 255, 0, 0);

//INFORMACION EMPRESA
$posY = 5;
imagestring($img, 1, 36, $posY, strtoupper($rowEmp['nombre_empresa']), $textColor);
//imagestring($img, 1, 270, $posY, strtoupper('FECHA DE ENVIO'), $textColor);

$posY = 35;
//imagestring($img, 1, 270, $posY, strtoupper('PERSONA CONTACTO DEALER'), $textColor);

$posY += 22;//65
imagestring($img, 1, 36, $posY, strtoupper($rowEmp['telefono1']), $textColor);
imagestring($img, 1, 270, $posY, formatoNumero($rowAccesorio['precio_unitario']), $textColor);


//INFORMACION DEL CLIENTE CUADRO IZQUIERDO
$posY = 130;
imagestring($img, 1, 5, $posY, strtoupper($rowContrato['nombre_cliente']), $textColor);

$posY += 26;
//$direccionCliente = strtoupper(str_replace(";", "", elimCaracter(utf8_encode($rowContrato['direccion_cliente']),";")));
$arrayDireccionCliente = explode(';', utf8_encode($rowContrato['direccion_cliente']));
imagestring($img, 1, 5, $posY, strtoupper(substr(trim($arrayDireccionCliente[0]." ".$arrayDireccionCliente[1]),0,45)), $textColor);

$posY += 10;
imagestring($img, 1, 5, $posY, strtoupper(substr(trim($arrayDireccionCliente[2]." ".$arrayDireccionCliente[3]),0,45)), $textColor);

$posY += 10;
imagestring($img, 1, 5, $posY, strtoupper(substr(trim($arrayDireccionCliente[4]." ".$arrayDireccionCliente[5]),0,45)), $textColor);

$posY += 23;
imagestring($img, 1, 5, $posY, strtoupper($rowContrato['correo']), $textColor); 

//INFORMACION DEL CLIENTE CUADRO DERECHO
$posY = 130;
imagestring($img, 1, 270, $posY, strtoupper($rowContrato['licencia_cliente']), $textColor); 

$posY += 25;
imagestring($img, 1, 270, $posY, strtoupper($rowContrato['telf']." ".$rowContrato['telf_comp']), $textColor);

$posY += 23;
imagestring($img, 1, 270, $posY, strtoupper($rowContrato['nombreBanco']), $textColor); 

$posY += 25;
imagestring($img, 1, 270, $posY, strtoupper($rowContrato['nom_comp_seguro']), $textColor); 

$posY = 230;
if($rowContrato['fecha_nacimiento'] != ""){
	imagestring($img, 1, 275, $posY, strtoupper(date('M', strtotime($rowContrato['fecha_nacimiento']))), $textColor); 
	imagestring($img, 1, 330, $posY, strtoupper(date('d', strtotime($rowContrato['fecha_nacimiento']))), $textColor); 
	imagestring($img, 1, 385, $posY, strtoupper(date('Y', strtotime($rowContrato['fecha_nacimiento']))), $textColor); 
}

//PERSONAS AUTORIZADAS
$posY = 285;
//imagestring($img, 1, 5, $posY, strtoupper('NOMBRE Y APELLIDO'), $textColor); 
//imagestring($img, 1, 185, $posY, strtoupper('PARENTESCO CLIENTE'), $textColor); 
//imagestring($img, 1, 315, $posY, strtoupper('TELEFONOS'), $textColor); 

$posY += 15;
//imagestring($img, 1, 5, $posY, strtoupper('NOMBRE Y APELLIDO2'), $textColor); 
//imagestring($img, 1, 185, $posY, strtoupper('PARENTESCO CLIENTE2'), $textColor); 
//imagestring($img, 1, 315, $posY, strtoupper('TELEFONOS2'), $textColor); 


//INFORMACION DEL VEHICULO
$posY = 345;
$arraySerial = str_split(trim($rowUnidad['serial_carroceria']));
imagestring($img, 1, 0, $posY, strtoupper($arraySerial[0]), $textColor);
imagestring($img, 1, 18, $posY, strtoupper($arraySerial[1]), $textColor);
imagestring($img, 1, 38, $posY, strtoupper($arraySerial[2]), $textColor);
imagestring($img, 1, 62, $posY, strtoupper($arraySerial[3]), $textColor);
imagestring($img, 1, 83, $posY, strtoupper($arraySerial[4]), $textColor);
imagestring($img, 1, 103, $posY, strtoupper($arraySerial[5]), $textColor);
imagestring($img, 1, 123, $posY, strtoupper($arraySerial[6]), $textColor);
imagestring($img, 1, 145, $posY, strtoupper($arraySerial[7]), $textColor);
imagestring($img, 1, 164, $posY, strtoupper($arraySerial[8]), $textColor);
imagestring($img, 1, 188, $posY, strtoupper($arraySerial[9]), $textColor);
imagestring($img, 1, 208, $posY, strtoupper($arraySerial[10]), $textColor);
imagestring($img, 1, 228, $posY, strtoupper($arraySerial[11]), $textColor);
imagestring($img, 1, 250, $posY, strtoupper($arraySerial[12]), $textColor);
imagestring($img, 1, 268, $posY, strtoupper($arraySerial[13]), $textColor);
imagestring($img, 1, 288, $posY, strtoupper($arraySerial[14]), $textColor);
imagestring($img, 1, 310, $posY, strtoupper($arraySerial[15]), $textColor);
imagestring($img, 1, 330, $posY, strtoupper($arraySerial[16]), $textColor);

imagestring($img, 1, 360, $posY, strtoupper($rowUnidad['nom_ano']), $textColor);
imagestring($img, 1, 405, $posY, strtoupper($rowUnidad['placa']), $textColor);

$posY = 374;
imagestring($img, 1, 30, $posY, strtoupper(substr($rowUnidad['nom_marca'],0,16)), $textColor);
imagestring($img, 1, 175, $posY, strtoupper(substr($rowUnidad['nom_modelo'],0,30)), $textColor);
imagestring($img, 1, 360, $posY, strtoupper(substr($rowUnidad['color_externo'],0,18)), $textColor);

//GRUPO X EQUIS
$posY = 407;
//imagestring($img, 2, 0, $posY-3, 'X', $textColor);//AUTO 2 puertas
//imagestring($img, 2, 48, $posY-3, 'X', $textColor);//AUTO 4 puertas
//imagestring($img, 2, 93, $posY-3, 'X', $textColor);//AUTO 5 puertas
//imagestring($img, 2, 135, $posY-3, 'X', $textColor);//AUTO 2 convertible

//imagestring($img, 2, 191, $posY-3, 'X', $textColor);//SUV 3 puertas
//imagestring($img, 2, 238, $posY-3, 'X', $textColor);//SUV 5 puertas

//imagestring($img, 2, 284, $posY-3, 'X', $textColor);//VAN van
//imagestring($img, 2, 318, $posY-3, 'X', $textColor);//VAN mini

//imagestring($img, 2, 357, $posY-3, 'X', $textColor);//PICKUP 1 cab
//imagestring($img, 2, 389, $posY-3, 'X', $textColor);//PICKUP 11/2 Cab
//imagestring($img, 2, 425, $posY-3, 'X', $textColor);//PICKUP 2 Cab


$arrayImg[] = "tmp/contrato_venta_hunter".$pageNum.".png";
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

function formatoNumero($monto){
    return number_format($monto, 2, ".", ",");
}

?>