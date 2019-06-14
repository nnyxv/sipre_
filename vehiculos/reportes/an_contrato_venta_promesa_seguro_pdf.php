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
	contrato.id_gerente_fin,
	contrato.nmac_beneficiario,
	contrato.nombre_agencia_seguro,
	contrato.direccion_agencia_seguro,	
	contrato.telefono_agencia_seguro,	
	poliza.nombre_poliza,
	poliza.nom_comp_seguro,
	an_ped_vent.num_poliza,
	an_ped_vent.fech_efect,
	an_ped_vent.fech_expira,
	an_ped_vent.ded_poliza,
	an_ped_vent.fecha_reserva_venta,
	an_ped_vent.fecha_entrega
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	LEFT JOIN crm_perfil_prospecto perfil ON (cliente.id = perfil.id)
	INNER JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
	INNER JOIN an_poliza poliza ON (an_ped_vent.id_poliza = poliza.id_poliza)
	LEFT JOIN an_adicionales_contrato contrato ON (an_ped_vent.id_pedido = contrato.id_pedido)
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

// BUSCA GERENTE DE FINANCIAMIENTO
$queryEmpleado = sprintf("SELECT 
	empleado.id_empleado, 
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) as nombre_empleado
FROM pg_empleado empleado
	INNER JOIN pg_cargo_departamento cargo_depart ON (empleado.id_cargo_departamento = cargo_depart.id_cargo_departamento)
	INNER JOIN pg_departamento depart ON (cargo_depart.id_departamento = depart.id_departamento)
	INNER JOIN pg_cargo cargo ON (cargo.id_cargo = cargo_depart.id_cargo)
WHERE empleado.id_empleado = %s",
	valTpDato($rowContrato['id_gerente_fin'], "int"));
$rsEmpleado = mysql_query($queryEmpleado);
if (!$rsEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowEmpleado = mysql_fetch_assoc($rsEmpleado);

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


	
	
$img = @imagecreate(490, 558) or die("No se puede crear la imagen");
	
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
$textColor2 = imagecolorallocate($img, 255, 0, 0);

//SELECCION FECHA
$posY = 102;
imagestring($img, 1, 50, $posY, strtoupper(date('d M Y',strtotime($rowContrato['fecha_reserva_venta']))), $textColor);

//INFORMACION DEL DETALLISTA / CLIENTE / VEHICULO
$posY = 190;
imagestring($img, 1, 65, $posY, strtoupper($rowEmp['nombre_empresa']), $textColor);

$posY += 16;
imagestring($img, 1, 130, $posY, strtoupper($rowEmp['telefono1']), $textColor);
imagestring($img, 1, 365, $posY, strtoupper($rowEmpleado['nombre_empleado']), $textColor);

$posY += 16;
imagestring($img, 1, 65, $posY, strtoupper($rowContrato['nombre_cliente']), $textColor);

$posY += 16;
$direccionCliente = strtoupper(str_replace(";", "", elimCaracter(utf8_encode($rowContrato['direccion_cliente']),";")));
imagestring($img, 1, 65, $posY, strtoupper($direccionCliente), $textColor);

$posY += 16;
imagestring($img, 1, 120, $posY, strtoupper($rowContrato['telf']), $textColor); 
imagestring($img, 1, 340, $posY, strtoupper($rowContrato['telf_comp']), $textColor);

$posY += 16;
imagestring($img, 1, 40, $posY, strtoupper($rowUnidad['nom_ano']), $textColor);
imagestring($img, 1, 120, $posY, strtoupper(substr($rowUnidad['nom_marca'],0,22)), $textColor);
imagestring($img, 1, 275, $posY, strtoupper(substr($rowUnidad['nom_modelo'],0,16)), $textColor);
imagestring($img, 1, 400, $posY, strtoupper(formatoNumero($rowUnidad['precio_unitario'])), $textColor);

$posY += 16;
$arraySerial = str_split(trim($rowUnidad['serial_carroceria']));
imagestring($img, 1, 36, $posY, strtoupper($arraySerial[0]), $textColor);
imagestring($img, 1, 50, $posY, strtoupper($arraySerial[1]), $textColor);
imagestring($img, 1, 66, $posY, strtoupper($arraySerial[2]), $textColor);
imagestring($img, 1, 81, $posY, strtoupper($arraySerial[3]), $textColor);
imagestring($img, 1, 96, $posY, strtoupper($arraySerial[4]), $textColor);
imagestring($img, 1, 111, $posY, strtoupper($arraySerial[5]), $textColor);
imagestring($img, 1, 126, $posY, strtoupper($arraySerial[6]), $textColor);
imagestring($img, 1, 142, $posY, strtoupper($arraySerial[7]), $textColor);
imagestring($img, 1, 156, $posY, strtoupper($arraySerial[8]), $textColor);
imagestring($img, 1, 172, $posY, strtoupper($arraySerial[9]), $textColor);
imagestring($img, 1, 187, $posY, strtoupper($arraySerial[10]), $textColor);
imagestring($img, 1, 201, $posY, strtoupper($arraySerial[11]), $textColor);
imagestring($img, 1, 216, $posY, strtoupper($arraySerial[12]), $textColor);
imagestring($img, 1, 231, $posY, strtoupper($arraySerial[13]), $textColor);
imagestring($img, 1, 246, $posY, strtoupper($arraySerial[14]), $textColor);
imagestring($img, 1, 262, $posY, strtoupper($arraySerial[15]), $textColor);
imagestring($img, 1, 276, $posY, strtoupper($arraySerial[16]), $textColor);

imagestring($img, 1, 425, $posY, strtoupper(date('d M y',strtotime($rowContrato['fecha_entrega']))), $textColor);

$posY += 16;
imagestring($img, 1, 50, $posY, strtoupper($rowContrato['nombre_poliza']), $textColor);
imagestring($img, 1, 345, $posY, strtoupper($rowContrato['telefono_agencia_seguro']), $textColor);


//INFORMACION DEL SEGURO
$posY = 328;
imagestring($img, 1, 120, $posY, strtoupper($rowContrato['nombre_agencia_seguro']), $textColor);

$posY += 16;
imagestring($img, 1, 120, $posY, strtoupper($rowContrato['direccion_agencia_seguro']), $textColor);

$posY += 15;
imagestring($img, 1, 110, $posY, strtoupper(substr($rowContrato['nombre_agencia_seguro'],0,20)), $textColor);
imagestring($img, 1, 345, $posY, strtoupper($rowContrato['num_poliza']), $textColor);

$posY += 16;
if($rowContrato['fech_efect'] != ""){
	imagestring($img, 1, 120, $posY, strtoupper(date('d M Y',strtotime($rowContrato['fech_efect']))), $textColor);
}
if($rowContrato['fech_expira'] != ""){
	imagestring($img, 1, 345, $posY, strtoupper(date('d M Y',strtotime($rowContrato['fech_expira']))), $textColor);	
}

$posY += 16;
imagestring($img, 1, 70, $posY, strtoupper(formatoNumero($rowContrato['ded_poliza'])), $textColor);
//imagestring($img, 1, 210, $posY, strtoupper('AMPLITUD'), $textColor);
//imagestring($img, 1, 395, $posY, strtoupper('CHOQUE'), $textColor);

// Observacion Glendalys: siempre NMAC como beneficiario

//if($rowContrato['nmac_beneficiario'] == 1){
	$posY = 422;
	imagestring($img, 2, 330, $posY-3, 'X', $textColor);
//}else{
//	$posY = 442;
//	imagestring($img, 2, 330, $posY-3, 'X', $textColor);
//}

$arrayImg[] = "tmp/contrato_venta_gravamen_legal".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, 15, 596, 688);
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