<?php
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");

$pdf->SetFillColor(204,204,204);
$pdf->SetDrawColor(153,153,153);
$pdf->SetLineWidth(1);
/**************************** ARCHIVO PDF ****************************/

$valBusq = $_GET["valBusq"];
$valCadBusq = explode("|", $valBusq);
$idPedidoCompra = $valCadBusq[0];
$idCarta = $valCadBusq[1];
$idFormaPago = $valCadBusq[2];

$queryAsignacion = sprintf("SELECT
	carta_sol.idCartaSolicitud,
	carta_sol.fechaCartaSolicitud,
	ped_comp.idPedidoCompra,
	pago_asig.idFormaPagoAsignacion,
	prov2.nombre AS nombre_proveedor,
	
	(CASE
		WHEN (pago_asig.descripcionFormaPagoAsignacion IS NULL OR pago_asig.descripcionFormaPagoAsignacion = '') THEN
			prov.nombre
		WHEN (pago_asig.descripcionFormaPagoAsignacion IS NOT NULL AND pago_asig.descripcionFormaPagoAsignacion <> '') THEN
			pago_asig.descripcionFormaPagoAsignacion
	END) AS descripcionFormaPagoAsignacion,
	
	COUNT(*) AS cant_unidades
FROM  an_pedido_compra ped_comp
	INNER JOIN an_encabezadocartasolicitud carta_sol ON (ped_comp.idPedidoCompra = carta_sol.idPedidoCompra)
	INNER JOIN an_solicitud_factura det_ped_comp ON (ped_comp.idPedidoCompra = det_ped_comp.idPedidoCompra)
	INNER JOIN an_detallecartasolicitud det_carta_sol ON (carta_sol.idCartaSolicitud = det_carta_sol.idCartaSolicitud)
		AND (det_carta_sol.idSolicitud = det_ped_comp.idSolicitud)
	INNER JOIN formapagoasignacion pago_asig ON (det_ped_comp.idFormaPagoAsignacion = pago_asig.idFormaPagoAsignacion)
	LEFT JOIN cp_proveedor prov ON (pago_asig.idProveedor = prov.id_proveedor)
	INNER JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
	INNER JOIN cp_proveedor prov2 ON (asig.id_proveedor = prov2.id_proveedor)
WHERE ped_comp.idPedidoCompra = %s
	AND carta_sol.idCartaSolicitud = %s
	AND pago_asig.idFormaPagoAsignacion = %s
GROUP BY idCartaSolicitud, idPedidoCompra, descripcionFormaPagoAsignacion",
	valTpDato($idPedidoCompra, "int"),
	valTpDato($idCarta, "int"),
	valTpDato($idFormaPago, "int"));
$rsAsignacion = mysql_query($queryAsignacion);
if (!$rsAsignacion) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowDcto = mysql_fetch_array($rsAsignacion);

@session_start();
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

$queryEmp = sprintf("SELECT *,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_empresas_sucursales vw_iv_emp_suc
WHERE vw_iv_emp_suc.id_empresa_reg = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$img = @imagecreate(530, 630) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

imagestring($img,1,5,30,utf8_decode("SEÑORES:"),$textColor);


imagestring($img,1,370,20,strtoupper($rowEmp['ciudad_empresa']).", ".date("d", strtotime($rowDcto['fechaCartaSolicitud']))." DE ".strtoupper($arrayMes[date("n", strtotime($rowDcto['fechaCartaSolicitud']))])." DE ".date("Y", strtotime($rowDcto['fechaCartaSolicitud'])),$textColor);

imagestring($img,1,5,50,utf8_decode("CIUDAD"),$textColor);

imagestring($img,1,5,80,utf8_decode("ESTIMADOS SEÑORES:"),$textColor);

if ($rowDcto['idFormaPagoAsignacion'] == 1) { // Cheque
	imagestring($img,1,15,40,utf8_decode("DEPARTAMENTO DE TESORERIA"),$textColor);
	
	imagestring($img,1,5,100,strtoupper(utf8_decode("Mediante la presente me dirijo a ustedes para solicitar que paguen por ".$rowDcto['descripcionFormaPagoAsignacion']." de ".$rowEmp['nombre_empresa'])),$textColor);
	imagestring($img,1,5,110,strtoupper(utf8_decode("a ".$rowDcto['nombre_proveedor'].", las siguientes unidades:")),$textColor);
} else if ($rowDcto['idFormaPagoAsignacion'] == 2) { // Transferencia
	imagestring($img,1,15,40,utf8_decode("DEPARTAMENTO DE TESORERIA"),$textColor);
	
	imagestring($img,1,5,100,strtoupper(utf8_decode("Mediante la presente me dirijo a ustedes para solicitar que paguen por ".$rowDcto['descripcionFormaPagoAsignacion']." de ")),$textColor);
	imagestring($img,1,5,110,strtoupper(utf8_decode($rowEmp['nombre_empresa']." a ".$rowDcto['nombre_proveedor'].", las siguientes unidades:")),$textColor);
} else if ($rowDcto['idFormaPagoAsignacion'] == 3) { // Crédito
	imagestring($img,1,15,40,utf8_decode("DEPARTAMENTO DE TESORERIA"),$textColor);
	
	imagestring($img,1,5,100,strtoupper(utf8_decode("Mediante la presente me dirijo a ustedes para solicitar que paguen a ".$rowDcto['descripcionFormaPagoAsignacion']." de ".$rowEmp['nombre_empresa'])),$textColor);
	imagestring($img,1,5,110,strtoupper(utf8_decode("a ".$rowDcto['nombre_proveedor'].", las siguientes unidades:")),$textColor);
} else {
	$queryCarta = sprintf("SELECT * FROM pg_encabezadoypiecartas
	WHERE idEncabezadoYpie = %s",
		valTpDato(2, "int"));
	$rsCarta = mysql_query($queryCarta);
	if (!$rsCarta) die(mysql_error()."<br><br>Line: ".__LINE__);
	$rowCarta = mysql_fetch_array($rsCarta);
	
	imagestring($img,1,15,40,strtoupper(utf8_decode($rowDcto['descripcionFormaPagoAsignacion'])),$textColor);
	
	imagestring($img,1,340,40,strtoupper(utf8_decode("ATENCIÓN")),$textColor);
	imagestring($img,1,340,50,strtoupper(utf8_decode($rowCarta['personaEncargada'])),$textColor);
	imagestring($img,1,340,60,strtoupper(utf8_decode($rowCarta['cargoPersonaEncargada'])),$textColor);
	
	imagestring($img,1,5,100,strtoupper(utf8_decode("Mediante la presente me dirijo a ustedes para solicitar que paguen por orden y cuenta de ")),$textColor);
	imagestring($img,1,5,110,strtoupper(utf8_decode($rowEmp['nombre_empresa']." a ".$rowDcto['nombre_proveedor'].", las siguientes unidades:")),$textColor);
}

$posY = 130;

imagestring($img,1,0,$posY,"----------------------------------------------------------------------------------------------------------",$textColor);
imagestring($img,1,10,$posY+10,strtoupper(utf8_decode("Unidad")),$textColor);
imagestring($img,1,10,$posY+20,strtoupper(utf8_decode("Básica")),$textColor);
imagestring($img,1,80,$posY+15,strtoupper(utf8_decode("Modelo")),$textColor);
imagestring($img,1,190,$posY+15,strtoupper(utf8_decode("Versión")),$textColor);
imagestring($img,1,320,$posY+15,strtoupper(utf8_decode("Cantidad")),$textColor);
imagestring($img,1,375,$posY+15,strtoupper(utf8_decode("Precio Unidad")),$textColor);
imagestring($img,1,465,$posY+15,strtoupper(utf8_decode("Monto Total")),$textColor);
imagestring($img,1,0,$posY+30,"----------------------------------------------------------------------------------------------------------",$textColor);

$queryDet = sprintf("SELECT
	carta_sol.idCartaSolicitud,
	carta_sol.fechaCartaSolicitud,
	ped_comp.idPedidoCompra,
	pago_asig.idFormaPagoAsignacion,
		
	(CASE
		WHEN (pago_asig.descripcionFormaPagoAsignacion IS NULL OR pago_asig.descripcionFormaPagoAsignacion = '') THEN
			prov.nombre
		WHEN (pago_asig.descripcionFormaPagoAsignacion IS NOT NULL AND pago_asig.descripcionFormaPagoAsignacion <> '') THEN
			pago_asig.descripcionFormaPagoAsignacion
	END) AS descripcionFormaPagoAsignacion,
	
	uni_bas.nom_uni_bas,
	modelo.nom_modelo,
	vers.nom_version,
	COUNT(*) AS cant_unidades,
	det_ped_comp.costo_unidad
FROM  an_pedido_compra ped_comp
	INNER JOIN an_encabezadocartasolicitud carta_sol ON (ped_comp.idPedidoCompra = carta_sol.idPedidoCompra)
	INNER JOIN an_solicitud_factura det_ped_comp ON (ped_comp.idPedidoCompra = det_ped_comp.idPedidoCompra)
	INNER JOIN an_detallecartasolicitud det_carta_sol ON (carta_sol.idCartaSolicitud = det_carta_sol.idCartaSolicitud)
	AND (det_carta_sol.idSolicitud = det_ped_comp.idSolicitud)
	INNER JOIN formapagoasignacion pago_asig ON (det_ped_comp.idFormaPagoAsignacion = pago_asig.idFormaPagoAsignacion)
	LEFT JOIN cp_proveedor prov ON (pago_asig.idProveedor = prov.id_proveedor)
	INNER JOIN an_uni_bas uni_bas ON (det_ped_comp.idUnidadBasica = uni_bas.id_uni_bas)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
WHERE ped_comp.idPedidoCompra = %s
	AND carta_sol.idCartaSolicitud = %s
	AND pago_asig.idFormaPagoAsignacion = %s
GROUP BY idCartaSolicitud, idPedidoCompra, descripcionFormaPagoAsignacion, costo_unidad",
	valTpDato($idPedidoCompra, "int"),
	valTpDato($idCarta, "int"),
	valTpDato($idFormaPago, "int"));
$rsDet = mysql_query($queryDet);
if (!$rsDet) die(mysql_error()."<br><br>Line: ".__LINE__);

$posY += 40;
while ($rowDet = mysql_fetch_array($rsDet)) {
	imagestring($img,1,0,$posY,strtoupper($rowDet['nom_uni_bas']),$textColor);
	imagestring($img,1,55,$posY,strtoupper($rowDet['nom_modelo']),$textColor);
	imagestring($img,1,145,$posY,strtoupper($rowDet['nom_version']),$textColor);
	imagestring($img,1,320,$posY,strtoupper(str_pad(number_format($rowDet['cant_unidades'], 2, ".", ","), 8, " ", STR_PAD_BOTH)),$textColor);
	imagestring($img,1,370,$posY,strtoupper(str_pad(number_format($rowDet['costo_unidad'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	imagestring($img,1,455,$posY,strtoupper(str_pad(number_format(($rowDet['cant_unidades']*$rowDet['costo_unidad']), 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	
	if ($rowDcto['estatus_asignacion'] == 2) {
		$totalUnidades += $rowDet['cantidadAceptada'];
	} else if ($rowDcto['estatus_asignacion'] == 3) {
		$totalUnidades += $rowDet['cantidadConfirmada'];
	}
	
	$posY += 10;
}
imagestring($img,1,0,$posY,"----------------------------------------------------------------------------------------------------------",$textColor);

$posY = 450;

imagestring($img,1,5,$posY,utf8_decode("SIN OTRO PARTICULAR"),$textColor);

$posY += 10;
imagestring($img,1,5,$posY,utf8_decode("ATENTAMENTE"),$textColor);

$posY += 60;
imagestring($img,1,5,$posY,utf8_decode(str_pad("LUCAS E. OUTUMURO G.", 28, " ", STR_PAD_BOTH)),$textColor);

$posY += 10;
imagestring($img,1,5,$posY,utf8_decode(str_pad("DIRECTOR", 28, " ", STR_PAD_BOTH)),$textColor);

$posY = 600;
imagestring($img,1,5,$posY,strtoupper(str_pad($rowEmp['nombre_empresa']." ".$spanRIF.": ".$rowEmp['rif'], 104, " ", STR_PAD_BOTH)),$textColor);

$posY += 10;
imagestring($img,1,5,$posY,strtoupper(str_pad("TELF.: ".$rowEmp['telefono1']." / ".$rowEmp['telefono2']." FAX: ".$rowEmp['fax']. " E-MAIL: ".$rowEmp['correo'], 104, " ", STR_PAD_BOTH)),$textColor);

$posY += 10;
imagestring($img,1,5,$posY,strtoupper(str_pad("fpa".$rowDcto['idFormaPagoAsignacion']."-crt".$rowDcto['idCartaSolicitud'], 104, " ", STR_PAD_BOTH)),$textColor);

$arrayImg[] = "tmp/"."solicitud_compra".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig10 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10
	AND config_emp.status = 1
	AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig10 = mysql_query($queryConfig10, $conex);
if (!$rsConfig10) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig10 = mysql_num_rows($rsConfig10);
$rowConfig10 = mysql_fetch_assoc($rsConfig10);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		// CABECERA DEL DOCUMENTO 
		if ($idEmpresa != "") {
			if (strlen($rowEmp['logo_familia']) > 5) {
				$pdf->Image("../../".$rowEmp['logo_familia'],15,17,70);
			}
			
			$pdf->SetY(15);
			
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',5);
			$pdf->SetX(88);
			$pdf->Cell(200,9,$rowEmp['nombre_empresa'],0,2,'L');
			
			if (strlen($rowEmp['rif']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_encode($spanRIF.": ".$rowEmp['rif']),0,2,'L');
			}
			if (strlen($rowEmp['direccion']) > 1) {
				$direcEmpresa = $rowEmp['direccion'].".";
				$telfEmpresa = "";
				if (strlen($rowEmp['telefono1']) > 1) {
					$telfEmpresa .= "Telf.: ".$rowEmp['telefono1'];
				}
				if (strlen($rowEmp['telefono2']) > 1) {
					$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
					$telfEmpresa .= $rowEmp['telefono2'];
				}
				
				$pdf->SetX(88);
				$pdf->Cell(100,9,$direcEmpresa." ".$telfEmpresa,0,2,'L');
			}
			if (strlen($rowEmp['web']) > 1) {
				$pdf->SetX(88);
				$pdf->Cell(200,9,utf8_encode($rowEmp['web']),0,0,'L');
				$pdf->Ln();
			}
		}
		//$pdf->SetY(-20);
		
		$pdf->Image($valor, 15, $rowConfig10['valor'], 580, 688);
		
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',6);
		$pdf->Cell(0,8,"Impreso: ".date(spanDateFormat." h:i:s a"),0,0,'R');
		$pdf->SetY(-20);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','I',8);
		$pdf->Cell(0,10,("Página ").$pdf->PageNo()."/{nb}",0,0,'C');
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