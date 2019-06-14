<?php
require_once ("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$idDocumento = $_GET["id"];

    $query = sprintf("SELECT 
                        te_nota_credito.id_nota_credito,
                        te_nota_credito.id_numero_cuenta,
                        te_nota_credito.fecha_registro,
                        te_nota_credito.observaciones,
                        te_nota_credito.monto_nota_credito,
                        te_nota_credito.id_empresa,
                        te_nota_credito.numero_nota_credito,
                        cuentas.idCuentas,
                        cuentas.id_empresa,
                        cuentas.numeroCuentaCompania,
                        bancos.idBanco,
                        bancos.nombreBanco
                      FROM
                        te_nota_credito
                        INNER JOIN cuentas ON (te_nota_credito.id_numero_cuenta = cuentas.idCuentas)
                        INNER JOIN bancos ON (cuentas.idBanco = bancos.idBanco)
                      WHERE
                        te_nota_credito.id_nota_credito ='%s'",
            valTpDato($idDocumento,"int"));
    

$rs = mysql_query($query, $conex);
//if (!$rsAlmacen) return $objResponse->alert(mysql_error());
$row = mysql_fetch_assoc($rs);

$img = @imagecreate(530, 630) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);
imagestring($img,0,10,0,empresa($row['id_empresa']),$textColor);
imagestring($img,1,395,0,"================",$textColor);
imagestring($img,1,395,10,"COMPROBANTE",$textColor);
imagestring($img,1,395,20,"NOTA CREDITO",$textColor);
imagestring($img,1,395,30,"# ".$row['id_nota_credito'],$textColor);
imagestring($img,1,395,35,"================",$textColor);


imagestring($img,1,10,40,"FECHA: ",$textColor);
imagestring($img,1,110,40,date(spanDateFormat, strtotime($row['fecha_registro'])),$textColor);
imagestring($img,1,10,55,"No. NOTA CREDITO:   ".$row['numero_nota_credito'],$textColor);
imagestring($img,1,10,70,"BANCO",$textColor);
imagestring($img,1,110,70,strtoupper($row['nombreBanco']),$textColor); // <----
imagestring($img,1,10,85,"CUENTA No. :",$textColor);
imagestring($img,1,110,85,$row['numeroCuentaCompania'],$textColor);


imagestring($img,1,0,100,"-------------------------------------------------------------------------------------------------------------------",$textColor);

imagestring($img,1,10,110,"DESCRIPCION",$textColor); 

imagestring($img,1,0,120,"-------------------------------------------------------------------------------------------------------------------",$textColor);

imagestring($img,1,10,130,utf8_decode(strtoupper($row['observaciones'])),$textColor);

imagestring($img,1,0,390,"-------------------------------------------------------------------------------------------------------------------",$textColor);
    imagestring($img,1,315,400,"MONTO",$textColor);
    imagestring($img,1,400,400,":",$textColor);
    imagestring($img,1,455,400,strtoupper(str_pad(number_format($row['monto_nota_credito'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor); // <----

    imagestring($img,1,10,500,"ELABORADO POR:________________________________",$textColor);
	imagestring($img,1,280,500,"REVISADO POR:________________________________",$textColor);
	
	
$arrayImg[] = "tmp/nota_credito".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();		
		$pdf->Image($valor, 15, 55, 580, 680);
		
		if(file_exists($valor)){ unlink($valor); }
	}
}

$pdf->SetDisplayMode(88);
//$pdf->AutoPrint(true);
$pdf->Output();


function empresa($id){
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = $row['nombre_empresa'];
	
	return $respuesta;
}

?>