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



//********TRAFICANTE DE VEHICULO DE MOTOR O REPRESENTANTE AUTORIZADO********
$posY = 50;
// LICENCIA
imagestring($img, 1, 22, $posY, strtoupper($rowEmp['licencia_venta']), $textColor);
// NOMBRE NEGOCIO
imagestring($img, 1, 160, $posY, strtoupper($rowEmp['nombre_empresa']), $textColor);


$posY += 44;
// VIN
imagestring($img, 1, 25, $posY, strtoupper($rowUnidad['serial_carroceria']), $textColor);


$posY += 26;
// AÑO
imagestring($img, 1, 15, $posY, strtoupper($rowUnidad['nom_ano']), $textColor);
// MARCA
imagestring($img, 1, 50, $posY, strtoupper($rowUnidad['nom_marca']), $textColor);
// MODELO
imagestring($img, 1, 120, $posY, strtoupper($rowUnidad['nom_modelo']), $textColor);
// COLOR
imagestring($img, 1, 270, $posY, strtoupper($rowUnidad['color_externo']), $textColor);


$posY += 44;
// PUERTAS
imagestring($img, 1, 25, $posY, strtoupper($rowUnidad['puertas']), $textColor);
// CILINDROS
imagestring($img, 1, 70, $posY, strtoupper($rowUnidad['cilindros']), $textColor);


$posY += 28;
// TITULO
if ($rowUnidad['id_condicion_unidad'] == 1) {
	imagestring($img, 1, 140, $posY, strtoupper("C"), $textColor); // si es nuevo va una C (CERTIFICADO DE ORIGEN)
} else {
	imagestring($img, 1, 140, $posY, strtoupper("T"), $textColor); // si es usado debe decir T (TITULO)
}
// ESTADO DE PROCEDENCIA
if ($rowUnidad['id_condicion_unidad'] == 1) {
	imagestring($img, 1, 250, $posY, strtoupper("JP"), $textColor); // si es nuevo estado de procedencia JP
} else {
	imagestring($img, 1, 250, $posY, strtoupper("PR"), $textColor); // si es usado debe de decir PR
}


//******** FECHA DE VENTA ********
$anoEsp = date("Y",strtotime($rowUnidad['fech_ent']));
$mesEsp = date("m",strtotime($rowUnidad['fech_ent']));
$diaEsp = date("d",strtotime($rowUnidad['fech_ent']));
$posY += 70;
// AÑO
imagestring($img, 1, 30, $posY, strtoupper($anoEsp), $textColor);
// MES
imagestring($img, 1, 65, $posY, strtoupper($mesEsp), $textColor);
// DIA
imagestring($img, 1, 95, $posY, strtoupper($diaEsp), $textColor);
// BANCO
imagestring($img, 1, 135, $posY, strtoupper($rowBanco['nombreBanco']), $textColor);



//**************** DUEÑO NUEVO ***************//
//imagestring($img, 1, 47, $posY, strtoupper("TIPO"), $textColor); 
//imagestring($img, 1, 155, $posY, strtoupper("NUMERO ID"), $textColor);

$posY += 50;
// LICENCIA DE CONDUCIR
imagestring($img, 1, 290, $posY, strtoupper($rowContrato['licencia_cliente']), $textColor);


$posY += 25;
//NOMBRE DEL CLIENTE
imagestring($img, 1, 80, $posY, strtoupper($rowContrato['nombre_cliente']), $textColor);



//********RESIDENCIAL********
$posY += 44;
// URBANIZACION, BARRIO, CONDOMINIO
imagestring($img, 1, 62, $posY, strtoupper($rowContrato['urbanizacion']), $textColor);


$posY += 21;
// NUMERO DE CASA, CALLE, BUZON
imagestring($img, 1, 62, $posY, strtoupper($rowContrato['casa_cliente']." ".$rowContrato['calle_cliente']), $textColor);


$posY += 21;
// MUNICIPIO
imagestring($img, 1, 62, $posY, strtoupper($rowContrato['municipio_cliente']), $textColor);
//imagestring($img, 1, 185, $posY, strtoupper("PR"), $textColor);
// CODIGO POSTAL DEL CLIENTE
imagestring($img, 1, 219, $posY, strtoupper($rowContrato['cod_postal_cliente']), $textColor);



//********POSTAL********
$posY += 24;
// APARTADO
imagestring($img, 1, 62, $posY, strtoupper($rowContrato['urbanizacion_postal']), $textColor);


$posY += 19;
// MUNICIPIO, BUZON
imagestring($img, 1, 62, $posY, strtoupper($rowContrato['casa_postal']." ".$rowContrato['calle_postal']), $textColor);


$posY += 19;
// CIUDAD
imagestring($img, 1, 62, $posY, strtoupper($rowContrato['municipio_postal']), $textColor);
//imagestring($img, 1, 185, $posY, strtoupper("PR"), $textColor);
// CODIGO POSTAL
imagestring($img, 1, 219, $posY, strtoupper($rowContrato['estado_postal']), $textColor);



// FECHA NACIMIENTO & SEXO
$anoEsp = date("Y",strtotime($rowContrato['fecha_nacimiento']));
$mesEsp = date("m",strtotime($rowContrato['fecha_nacimiento']));
$diaEsp = date("d",strtotime($rowContrato['fecha_nacimiento']));
$posY += 28;
// AÑO
imagestring($img, 1, 30, $posY, strtoupper($anoEsp), $textColor);
// MES
imagestring($img, 1, 65, $posY, strtoupper($mesEsp), $textColor);
// DIA
imagestring($img, 1, 90, $posY, strtoupper($diaEsp), $textColor);
// SEXO
imagestring($img, 1, 150, $posY, strtoupper($rowContrato['sexo']), $textColor);



// TRAFICANTE 
$posY += 33;
//imagestring($img, 1, 113, $posY, strtoupper($rowEmp['nombre_empresa']), $textColor);

$arrayImg[] = "tmp/contrato_venta_gravamen_legal".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


// VERIFICA VALORES DE CONFIGURACION (Margen Superior e Izquierdo para Documento de Impresion (LICENCIA PROVISIONAL (DTOP)))
$queryConfig213 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 213 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig213 = mysql_query($queryConfig213, $conex);
if (!$rsConfig213) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig213 = mysql_num_rows($rsConfig213);
$rowConfig213 = mysql_fetch_assoc($rsConfig213);

$valorConfig213 = explode("|",$rowConfig213['valor']);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indiceImg => $valorImg) {
		$pdf->AddPage();
		
		$pdf->Image($valorImg, $valorConfig213[1], $valorConfig213[0], 580, 700);
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