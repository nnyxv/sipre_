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
	perfil.sexo,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.direccion AS direccion_cliente,
	cliente.municipio AS municipio_cliente,
	cliente.urbanizacion,
	cliente.estado AS cod_postal_cliente,
	cliente.ciudad AS ciudad_cliente,
	cliente.licencia AS licencia_cliente,
	cliente.nit AS nit_cliente,
	cliente.casa AS casa_cliente,
	cliente.calle AS calle_cliente,
	cliente.urbanizacion_postal, 
	cliente.ciudad_postal, 
	cliente.calle_postal, 
	cliente.casa_postal, 
	cliente.municipio_postal,
	cliente.estado_postal,
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

//echo "<pre>"; var_dump($rowContrato);exit;

$idDocumento = $rowContrato["idFactura"];
$idEmpresa = $rowContrato['id_empresa'];
$idBanco = $rowContrato['id_banco_financiar'];;

// BUSCA LOS DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT 
	vw_iv_emp_suc.*,
	empresa.licencia_venta,
	empresa.nombre_empresa,
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
	ped_vent.fecha_entrega AS fech_ent,
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
WHERE cxc_fact_det_vehic.id_factura = {$idDocumento}";
$rsUnidad = mysql_query($queryUnidad);
if (!$rsUnidad) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsUnidad = mysql_num_rows($rsUnidad);
$rowUnidad = mysql_fetch_assoc($rsUnidad);


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



//*************TRAFICANTE DE VEHICULO DE MOTOR O REPRESENTANTE AUTORIZADO*************//

//LICENCIA
$posY = 39;
$posX = 22;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowEmp['licencia_venta'],0,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowEmp['licencia_venta'],1,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowEmp['licencia_venta'],3,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowEmp['licencia_venta'],4,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowEmp['licencia_venta'],5,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowEmp['licencia_venta'],6,1)), $textColor);


imagestring($img, 2, 160, $posY, strtoupper($rowEmp['nombre_empresa']), $textColor);

// VIN
$posY += 49;
$posX = 25;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],0,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],1,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],2,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],3,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],4,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],5,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],6,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],7,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],8,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],9,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],10,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],11,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],12,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],13,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],14,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],15,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['serial_carroceria'],16,1)), $textColor);


//AÑO
$posY += 30;
$posX = 22;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_ano'],0,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_ano'],1,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_ano'],2,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_ano'],3,1)), $textColor);

//MARCA
$posX += 20;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_marca'],0,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_marca'],1,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_marca'],2,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_marca'],3,1)), $textColor);


//MODELO
$posX += 19;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_modelo'],0,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_modelo'],1,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['nom_modelo'],2,1)), $textColor);

//COLOR

$posX += 68;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['color_externo'],0,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['color_externo'],1,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['color_externo'],2,1)), $textColor);

//PUERTAS

$posY += 32;
$posX = 24;
imagestring($img, 2, $posX, $posY, strtoupper($rowUnidad['puertas']), $textColor);

//CILINDROS
$posX += 27;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['cilindros'],0,1)), $textColor);
$posX += 12;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowUnidad['cilindros'],1,1)), $textColor);

//TITULO 

$posY += 29;
$posX = 120;

if($rowUnidad['id_condicion_unidad'] == 1){
	imagestring($img, 2, $posX, $posY, strtoupper("C"), $textColor); // si es nuevo va una C (certificado de origen)
}else{
	imagestring($img, 2, $posX, $posY, strtoupper("T"), $textColor); // si es usado debe decir T (TITULO)
}

//ESTADO DE PROCEDENCIA
$posX += 72;
if($rowUnidad['id_condicion_unidad'] == 1){
	imagestring($img, 2, $posX, $posY, strtoupper("J"), $textColor); // si es nuevo estado de procedencia JP
	$posX += 12;
	imagestring($img, 2, $posX, $posY, strtoupper("P"), $textColor); // si es nuevo estado de procedencia JP
}else{
	imagestring($img, 2, $posX, $posY, strtoupper("P"), $textColor); // si es usado debe de decir PR 
	$posX += 12;
	imagestring($img, 2, $posX, $posY, strtoupper("R"), $textColor); // si es usado debe de decir PR 
}

//************** FECHA DE VENTA ********************//

$posY += 66;
$posX = 20;

$anoEsp = date("Y",strtotime($rowUnidad['fech_ent']));
$mesEsp = date("m",strtotime($rowUnidad['fech_ent']));
$diaEsp = date("d",strtotime($rowUnidad['fech_ent']));

//AÑO
imagestring($img, 2, $posX, $posY, strtoupper(substr($anoEsp,0,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($anoEsp,1,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($anoEsp,2,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($anoEsp,3,1)), $textColor);

//MES
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($mesEsp,0,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($mesEsp,1,1)), $textColor);

//DIA
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($diaEsp,0,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($diaEsp,1,1)), $textColor);

//**************************************************************

//BANCO

$posX += 22;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowBanco['nombreBanco'],0,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowBanco['nombreBanco'],1,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowBanco['nombreBanco'],2,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowBanco['nombreBanco'],3,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowBanco['nombreBanco'],4,1)), $textColor);
$posX += 11;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowBanco['nombreBanco'],5,1)), $textColor);



// //**************** DUEÑO NUEVO ***************//
// //imagestring($img, 1, 47, $posY, strtoupper("TIPO"), $textColor); 
// //imagestring($img, 1, 155, $posY, strtoupper("NUMERO ID"), $textColor);

//LICENCIA DE CONDUCIR

$posY += 60;
$posX = 217;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['licencia_cliente'],0,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['licencia_cliente'],1,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['licencia_cliente'],2,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['licencia_cliente'],3,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['licencia_cliente'],4,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['licencia_cliente'],5,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['licencia_cliente'],6,1)), $textColor);


//NOMBRE DEL CLIENTE

$posX = 95;
$posY += 27;
imagestring($img, 2, $posX, $posY, strtoupper($rowContrato['nombre_cliente']), $textColor);

//RESIDENCIAL
$posY += 27;
imagestring($img, 2, 62, $posY, strtoupper($rowContrato['urbanizacion']), $textColor);

//URBANIZACION, BARRIO CONDOMINIO

$posY += 22;
imagestring($img, 2, 62, $posY, strtoupper($rowContrato['casa_cliente']." ".$rowContrato['calle_cliente']), $textColor);

//MUNICIPIO

$posY += 23;
imagestring($img, 2, 62, $posY, strtoupper($rowContrato['municipio_cliente']), $textColor);
// imagestring($img, 1, 185, $posY, strtoupper("PR"), $textColor);

//CODIGO POSTAL DEL CLIENTE
$posX = 219;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['cod_postal_cliente'],0,1)), $textColor);
$posX += 14;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['cod_postal_cliente'],1,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['cod_postal_cliente'],2,1)), $textColor);
$posX += 15;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['cod_postal_cliente'],3,1)), $textColor);
$posX += 16;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['cod_postal_cliente'],4,1)), $textColor);
$posX += 15;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['cod_postal_cliente'],5,1)), $textColor);

//POSTAL
$posY += 26;
imagestring($img, 2, 62, $posY, strtoupper($rowContrato['urbanizacion_postal']), $textColor);

$posY += 20;
imagestring($img, 2, 62, $posY, strtoupper($rowContrato['casa_postal']." ".$rowContrato['calle_postal']), $textColor);

$posY += 25;
imagestring($img, 2, 62, $posY, strtoupper($rowContrato['municipio_postal']), $textColor);
//imagestring($img, 1, 185, $posY, strtoupper("PR"), $textColor);

//CODIGO POSTAL - POSTAL

$posX = 219;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['estado_postal'],0,1)), $textColor);
$posX += 14;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['estado_postal'],1,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['estado_postal'],2,1)), $textColor);
$posX += 15;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['estado_postal'],3,1)), $textColor);
$posX += 16;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['estado_postal'],4,1)), $textColor);
$posX += 15;
imagestring($img, 2, $posX, $posY, strtoupper(substr($rowContrato['estado_postal'],5,1)), $textColor);


// //FECHA NACIMIENTO & SEXO


$posY += 33;
$posX = 25;

$anoEsp = date("Y",strtotime($rowContrato['fecha_nacimiento']));
$mesEsp = date("m",strtotime($rowContrato['fecha_nacimiento']));
$diaEsp = date("d",strtotime($rowContrato['fecha_nacimiento']));

//AÑO
imagestring($img, 2, $posX, $posY, strtoupper(substr($anoEsp,0,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($anoEsp,1,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($anoEsp,2,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($anoEsp,3,1)), $textColor);

//MES
$posX += 14;
imagestring($img, 2, $posX, $posY, strtoupper(substr($mesEsp,0,1)), $textColor);
$posX += 14;
imagestring($img, 2, $posX, $posY, strtoupper(substr($mesEsp,1,1)), $textColor);

//DIA
$posX += 15;
imagestring($img, 2, $posX, $posY, strtoupper(substr($diaEsp,0,1)), $textColor);
$posX += 13;
imagestring($img, 2, $posX, $posY, strtoupper(substr($diaEsp,1,1)), $textColor);

//SEXO

imagestring($img, 2, 169, $posY, strtoupper($rowContrato['sexo']), $textColor);
// $posY += 27;

//TRAFICANTE 

$posY += 33;
//imagestring($img, 2, 113, $posY, strtoupper($rowEmp['nombre_empresa']), $textColor);

///***************************************************************************************************

$arrayImg[] = "tmp/contrato_venta_gravamen_legal".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indiceImg => $valorImg) {
		$pdf->AddPage();
		
		$pdf->Image($valorImg, 0, 0, 580, 700);
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