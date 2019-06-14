<?php
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt',array(612,837)); //normal, puntos/pixel ,array (ancho,alto) CON EL MEMBRETE SUJETADOR DE LAS COPIAS
//$pdf = new PDF_AutoPrint('P','pt',array(612,1152)); //normal, puntos/pixel ,array (ancho,alto) SIN EL MEMBRETE SUJETADOR DE LAS COPIAS
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/


$vista = $_GET['view'];
$idContrato = $_GET["id"];


if($vista ==  "print"){
	
//******ELECCION DE LA CUBIERTA DE SEGUROS *******//

	
/////////////////////// CONSULTAS A LA BASE DE DATOS ///////////////////////


	//CONTRATO GENERAL
	
$contQuerySQL = "SELECT
	CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nom_cont,
	DATE_FORMAT(an_ped_vent.fecha_reserva_venta,'%d %b %Y') AS fech_cont,
	DATE_FORMAT(an_ped_vent.fecha_reserva_venta,'%d') AS dia_cont,
	DATE_FORMAT(an_ped_vent.fecha_reserva_venta,'%b') AS mes_cont,
	DATE_FORMAT(an_ped_vent.fecha_reserva_venta,'%Y') AS ano_cont	
FROM cj_cc_encabezadofactura cxc_fact
	INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
	INNER JOIN an_pedido an_ped_vent ON (cxc_fact.numeroPedido = an_ped_vent.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
	LEFT JOIN an_adicionales_contrato contrato ON (an_ped_vent.id_pedido = contrato.id_pedido)
WHERE contrato.id_adi_contrato = {$idContrato}
ORDER BY idFactura DESC LIMIT 1;";
$rsCont = mysql_query($contQuerySQL);
if (!$rsCont) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowCont = mysql_fetch_array($rsCont);

	
////////////////////// POSICIONAMIENTO DE CAMPOS /////////////////////////

//************CREANDO IMAGEN POR PAGINA 1*****************//

	$img = @imagecreate(612, 1812) or die("No se puede crear la imagen");
	$pageNum = 1; //pagina 1 del documento
	
	// ESTABLECIENDO LOS COLORES DE LA PALETA
	$backgroundColor = imagecolorallocate($img, 255, 255, 255);
	$textColor = imagecolorallocate($img, 0, 0, 0);
	$textColor2 = imagecolorallocate($img, 255, 0, 0);
	
 	// NOMBRE DEL COMPRADOR
 	
	$posX = 110;
	$posY = 510;

	imagestring($img, 2, $posX, $posY+5,utf8_encode(strtoupper(substr($rowCont['nom_cont'],0,20))), $textColor);	 	
 	//FECHA COMPLETA

	 $posX = 96;
	 $posY = 569;
	 imagestring($img, 2, $posX, $posY+5, utf8_encode(strtoupper($rowCont['fech_cont'])), $textColor);
		 
	//FECHA DIVIDIDA
	
	 //DIA
	 $posX = 168;
	 $posY = 601;
	 imagestring($img, 2, $posX, $posY+4,utf8_encode(strtoupper($rowCont['dia_cont'])), $textColor);
	 
	 //MEs
	 $posX = 252;
	 imagestring($img, 2, $posX, $posY+4,utf8_encode(strtoupper($rowCont['mes_cont'])), $textColor);
	 
	 //AÑO
	 $posX = 420;
	 imagestring($img, 2, $posX, $posY+4, utf8_encode(strtoupper($rowCont['ano_cont'])), $textColor);
		
//******************************************************************************************	


	
// ////////////// GUARDANDO IMAGENES EN ARRAY //////////////////////		

	$arrayImg[] = "tmp/"."contrato_venta_info_seguro".$pageNum.".png";
	$r = imagepng($img,$arrayImg[count($arrayImg)-1]);
	
	
	if (isset($arrayImg)) {
		foreach ($arrayImg as $indice => $valor) {
			$pdf->AddPage();
			
			if($indice == 0){
				$pdf->Image($valor, 15, 15, 596, 1798); // PDF pagina 1 CON EL MEMBRETE SUJETADOR
	//			$pdf->Image($valor, 15, 15, 596, 1798); // PDF pagina 1 SIN EL MEMBRETE SUJETADOR
			}
		}
	}
	
	$pdf->SetDisplayMode(80);
	//$pdf->AutoPrint(true);
	$pdf->Output();
	
	if (isset($arrayImg)) {
		foreach ($arrayImg as $indice => $valor) {
			//if(file_exists($valor)) unlink($valor);
		}
	}

}
// FORMATO DE NUMEROS

function formatoNumero($monto){
    return number_format($monto, 2, ".", ",");
}

// IMPRIMIR DE DERECHA A IZQUIERDA

function prtDI ($string,$posX){
	$cont = strlen($string);
	$ret = $posX-(6*$cont);
	return $ret;
}

//cortar string 

function cutString ($string,$tam) {

	$dir = explode(" ",$string);

	for ($i=0; $i < count($dir); $i++) {
		$cont += strlen($dir[$i]);
		if($cont <= $tam){
			$str[0].=$dir[$i]." ";
		}else{
			$str[1].=$dir[$i]." ";
		}
	}
	
	return $str;
}




?>