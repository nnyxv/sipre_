<?php
require_once ("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$idDocumento = $_GET['id'];
$contador = 0;

$query = sprintf("SELECT 
  te_transferencia.id_transferencia,
  te_transferencia.numero_transferencia,
  te_transferencia.num_cuenta,
  te_transferencia.observacion,
  te_transferencia.monto_transferencia,
  te_transferencia.fecha_registro,
  te_transferencia.id_documento,
  te_transferencia.tipo_documento,
  te_transferencia.id_empresa,
  bancos.nombreBanco,
  cuentas.numeroCuentaCompania,
	  (case 
			when(te_transferencia.beneficiario_proveedor = 0) 
			then(select te_beneficiarios.nombre_beneficiario 
				 from te_beneficiarios 
				 where(te_transferencia.id_beneficiario_proveedor = te_beneficiarios.id_beneficiario)) 
			
			when(te_transferencia.beneficiario_proveedor = 1) 
			then(select cp_proveedor.nombre 
				 from cp_proveedor 
				 where(te_transferencia.id_beneficiario_proveedor = cp_proveedor.id_proveedor)) 
	  end) AS nombre,
  pg_empresa.nombre_empresa,
  pg_empresa.logo_empresa,
  pg_empresa.rif,
  pg_empresa.direccion
FROM
  te_transferencia
  INNER JOIN cuentas ON (te_transferencia.id_cuenta = cuentas.idCuentas)
  INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
  INNER JOIN pg_empresa ON (te_transferencia.id_empresa = pg_empresa.id_empresa)
WHERE
te_transferencia.id_transferencia ='%s'", valTpDato($idDocumento,"int"));
$rs = mysql_query($query, $conex) or die(mysql_error());



while ($row = mysql_fetch_assoc($rs)){
	
$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	$row['id_empresa']);
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) die(mysql_error()."<br><br>Line: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

$img = @imagecreate(530, 630) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

imagestring($img,1,60,10,$row['nombre_empresa'],$textColor);
imagestring($img,1,60,20,"RIF : ",$textColor);
imagestring($img,1,90,20,$row['rif'],$textColor);		

imagestring($img,1,0,50,str_pad("COMPROBANTE TRANSFERENCIA", 110, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,19,90,"N COMPROBANTE: ",$textColor);
imagestring($img,1,100,90,str_pad($row['id_transferencia'], 4, "0", STR_PAD_LEFT),$textColor);

imagestring($img,1,19,100,"N TRANSFRENCIA: ",$textColor);
imagestring($img,1,100,100,$row['numero_transferencia'],$textColor);

imagestring($img,1,19,120,"BANCO: ",$textColor);
imagestring($img,1,70,120,str_pad($row['nombreBanco'], 4, "0", STR_PAD_LEFT),$textColor);


imagestring($img,1,380,90,"FECHA : ",$textColor);
imagestring($img,1,430,90,": ".date(spanDateFormat, strtotime($row['fecha_registro'])),$textColor);
imagestring($img,1,19,130,"CUENTA HA DEBITAR : ",$textColor);
imagestring($img,1,140,130,str_pad($row['numeroCuentaCompania'], 15, " ", STR_PAD_LEFT),$textColor);

imagestring($img,1,0,150,str_pad("DATOS DEL BENEFICIARIO", 110, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,0,160,"-------------------------------------------------------------------------------------------------------------------",$textColor);



imagestring($img,1,19,170,"NOMBRE : ",$textColor);
imagestring($img,1,140,170,$row['nombre'],$textColor);

imagestring($img,1,19,180,"CUENTA HA TRANSFERIR : ",$textColor);
imagestring($img,1,140,180,str_pad($row['num_cuenta'], 15, " ", STR_PAD_LEFT),$textColor);

imagestring($img,1,0,190,"-------------------------------------------------------------------------------------------------------------------",$textColor);

imagestring($img,1,0,200,str_pad("DESCRIPCION", 110, " ", STR_PAD_BOTH),$textColor);
imagestring($img,1,0,215,"-------------------------------------------------------------------------------------------------------------------",$textColor);
imagestring($img,1,19,230,$row['observacion'],$textColor);

imagestring($img,1,0,390,"-------------------------------------------------------------------------------------------------------------------",$textColor);
    imagestring($img,1,315,400,"MONTO",$textColor);
    imagestring($img,1,400,400,":",$textColor);
    imagestring($img,1,455,400,strtoupper(str_pad(number_format($row['monto_transferencia'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	
	$r = imagepng($img,"tmp/comprobante_transferencia".$contador.'.png');
	$contador++;
}


//$direccion="\"".$row['logo_empresa']."\"";
for ($i = 0;$i < $contador;$i++){
	$pdf->AddPage();
	
	$pdf->Image("tmp/comprobante_transferencia".$i.".png", 15, 55, 580, 680);
	$pdf->Image("../../".$rowEmp['logo_familia'], '20', '65', '40', '40');
	
	if(file_exists("tmp/comprobante_transferencia".$i.".png")){ unlink("tmp/comprobante_transferencia".$i.".png"); }
}

$pdf->SetDisplayMode(88);
$pdf->AutoPrint(true);
$pdf->Output();
?>