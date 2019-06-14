<?php
require_once("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt',array(612,1050)); //normal, puntos/pixel ,array (ancho,alto) CON EL MEMBRETE SUJETADOR DE LAS COPIAS
//$pdf = new PDF_AutoPrint('P','pt',array(612,1152)); //normal, puntos/pixel ,array (ancho,alto) SIN EL MEMBRETE SUJETADOR DE LAS COPIAS
$pdf->SetMargins("0","0","0");
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/


$vista = $_GET['view'];
$idContrato = $_GET["id"];


if($vista ==  "print"){
	
//******CONTRATO DE VENTA AL POR MENOR A PLAZOS CO INTERES SIMPLE Y CLAUSULA DE ARBITRAJE - PUERTO RICO *******//

	
/////////////////////// CONSULTAS A LA BASE DE DATOS ///////////////////////


	//CONTRATO GENERAL
	
	$contQuerySQL = "SELECT 
		CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nom_cont,						
		CONCAT_WS(' ',cliente.urbanizacion, cliente.calle, cliente.casa, cliente.municipio, cliente.estado) AS dir_cont,	
		emp.id_empresa,
		adi_cont.id_pedido,
		adi_cont.mot_adquisicion,
		uni_fis.id_condicion_unidad,
		marca.nom_marca,
		modelo.nom_modelo,
		ano.nom_ano,
		uni_fis.serial_carroceria,
		ped_vent.ded_poliza,
		ped_vent.interes_cuota_financiar,
		cxc_fact_acc.precio_unitario,
		ped_vent.id_poliza,
		ped_vent.ded_poliza,
		ped_vent.meses_poliza,
		ped_vent.periodo_poliza,
		ped_vent.monto_seguro,
		ped_vent.meses_financiar,
		ped_vent.cuotas_financiar,
		poliza.nombre_poliza,
		poliza.dir_agencia
	FROM an_adicionales_contrato adi_cont
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact.numeroPedido = adi_cont.id_pedido AND cxc_fact.idDepartamentoOrigenFactura = 2)
		INNER JOIN an_pedido ped_vent ON (adi_cont.id_pedido = ped_vent.id_pedido )
		INNER JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
				INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
				INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
				INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
				INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		INNER JOIN cj_cc_factura_detalle_accesorios cxc_fact_acc ON (cxc_fact.idFactura = cxc_fact_acc.id_factura)
		INNER JOIN pg_empresa emp ON (ped_vent.id_empresa = emp.id_empresa)
		LEFT JOIN an_poliza poliza ON (ped_vent.id_poliza = poliza.id_poliza)			
	WHERE adi_cont.id_adi_contrato = {$idContrato}
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
 	
	 	$posX = 60;
	 	$posY = 82;
	 	imagestring($img, 2, $posX, $posY+2,utf8_encode(strtoupper($rowCont['nom_cont'])), $textColor);

 	//DIRECCION RESIDENCIAL DEL COMPRADOR
 	
	 	$ArrayString = cutString(utf8_encode(strtoupper($rowCont['dir_cont'])),32);
		 $posX = 60;
		 $posY = 92;
		 imagestring($img, 2, $posX, $posY+1, $ArrayString[0], $textColor);
		 
		 $posY+=12;
		 imagestring($img, 2, $posX, $posY-2, $ArrayString[1], $textColor);
		 	

//******************************************************************************************	

	

// //************** VERFIFICANDO EMPRESA **********************//
	
	$idEmpresa = $rowCont['id_empresa'];
	
	$queryEmp = sprintf("SELECT 
						*,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE vw_iv_emp_suc.id_empresa_reg = %s",
			valTpDato($idEmpresa, "int"),
			mysql_query("SET NAMES 'latin1';"));
	$rsEmp = mysql_query($queryEmp);
	if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowEmp = mysql_fetch_assoc($rsEmp);

	// NOMBRE DEL VENDEDOR
	$posX = 456;
	$posY = 72;
	imagestring($img, 2, $posX, $posY+1, utf8_encode(strtoupper($rowEmp['nombre_empresa'])), $textColor);
	
	//DIRECCION DEL VENDEDOR
		
	
	$ArrayString = cutString($rowEmp['direccion'],35);
	$ArrayString2 = cutString($ArrayString[1],50);
	
	
	$posX = 300;
	$posY = 84;
	imagestring($img, 2, $posX, $posY+1, strtoupper($ArrayString[0]), $textColor);
	$posY = 96 ;
	imagestring($img, 2, $posX, $posY+1, strtoupper($ArrayString2[0]),$textColor);


//******************************************************************************************
	
// //************** DATOS UNIDAD NUEVA**********************//
	
		// NUEVO O USADO
	
		if($rowCont['id_condicion_unidad'] == 1){
		
			$posX = 72;
			$posY = 132;
			imagestring($img, 2, $posX+6, $posY,"NEW", $textColor);


		}else {

			$posX = 72;
			$posY = 132;
			imagestring($img, 2, $posX+6, $posY,"USED", $textColor);
				
	
		}
	
	// AÑO
	
		$posX = 132;
		$posY = 132;
		imagestring($img, 2, $posX-4, $posY,  utf8_encode(substr($rowCont['nom_ano'], -2)), $textColor);
		

	//MARCA COMERCIAL
	
		$posX = 156;
		imagestring($img, 2, $posX+3, $posY,utf8_encode(strtoupper(substr($rowCont['nom_marca'],0,4))), $textColor);
			
		
		
		
	//MODELO
		
		$posX = 192;
		imagestring($img, 2, $posX, $posY, utf8_encode(strtoupper(substr($rowCont['nom_modelo'],0,5))), $textColor);
	

	//NUM. DE IDENTIFICACION VEHICULAR
		
		
		$posX = 264;
		imagestring($img, 2, $posX+6, $posY, utf8_encode(strtoupper($rowCont['serial_carroceria'])), $textColor);
			
	//MOTIVO DE ADQUISICION
	
	if($rowCont['mot_adquisicion'] == 1){
		$posX = 420;
		$posY = 132;
		imagestring($img, 2, $posX-7, $posY-2, "X", $textColor);

	} else {
		$posX = 420;
		$posY = 144;
		imagestring($img, 2, $posX-7, $posY-3,"X", $textColor);
	}
		
//******* A. SEGURO DEL VEHICULO ********************//
	
	if($rowCont['nombre_poliza'] != null){
	
		
		if($rowCont['meses_poliza'] <= 12){
			//SIMPLE 
			$posX = 288;
			$posY = 360;
			imagestring($img, 2, $posX-3, $posY+1,"X", $textColor);
		} else {
			//DOBLE
			$posX= 420;
			$posY = 360;
			imagestring($img, 2, $posX-3, $posY+1,"X", $textColor);
		}
		
		//AQUIRIRA 
		$posX= 96;
		$posY = 372;
		imagestring($img, 2, $posX, $posY-2,"X", $textColor);
		
		//OBTENDRA
		$posX= 96;
		$posY = 384;
		imagestring($img, 2, $posX, $posY-3 ,"X", $textColor);
	
		//NOMBRE DE POLIZA
		
		$posX= 96;
		$posY = 444;
		imagestring($img, 2, $posX, $posY-2,utf8_encode(substr($rowCont['nombre_poliza'],0,23)), $textColor);

		// PRIMA

		$posX = 308;
		$NposX = prtDI(formatoNumero($rowCont['monto_seguro']), $posX);
 		imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero($rowCont['monto_seguro'])), $textColor);
 		
 		
 		// MESES
 		
 		$posX = 360;
 		imagestring($img, 2, $posX, $posY-2,utf8_encode(strtoupper($rowCont['periodo_poliza'])), $textColor);
	
	
	} else {
		
		//NO AQUIRIRA
		$posX= 144;
		$posY = 372;
		imagestring($img, 2, $posX+3, $posY-2,"X", $textColor);
		
		//NO OBTENDRA
		$posX= 144;
		$posY = 384;
		imagestring($img, 2, $posX+3, $posY-3,"X", $textColor);
	
		//NOMBRE DE POLIZA
		
		$posX= 96;
		$posY = 444;
		imagestring($img, 2, $posX, $posY-2,"N/A", $textColor);//???
		
		// PRIMA
		
		$posX = 308;
		$NposX = prtDI(formatoNumero(0), $posX);
		imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero(0)), $textColor);
			
			
		// MESES
			
		$posX = 360;
		imagestring($img, 2, $posX, $posY-2,utf8_encode(strtoupper(0)), $textColor);
		
		//CARGO POR FINANCIAMIENTO
		

		$posX = 528;
		$NposX = prtDI(formatoNumero(0), $posX);
		imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero(0)), $textColor);
		
	}	
// //*****************************//

	 $idPedido = $rowCont['id_pedido'];
	
	$querySQL = sprintf("SELECT
							acc.nom_accesorio,
							acc.id_filtro_factura,
							cxc_fact_acc.id_factura_detalle_accesorios,
							cxc_fact_acc.precio_unitario,
							adi_cont.per_gap,
							adi_cont.per_credit_life,
							adi_cont.per_cont_serv
						FROM an_pedido pedido
						INNER JOIN cj_cc_encabezadofactura cxc_fact ON (pedido.id_pedido = cxc_fact.numeroPedido)
						INNER JOIN cj_cc_factura_detalle_accesorios cxc_fact_acc ON(cxc_fact.idFactura = cxc_fact_acc.id_factura)
						INNER JOIN an_accesorio acc ON (cxc_fact_acc.id_accesorio = acc.id_accesorio)
						INNER JOIN an_adicionales_contrato adi_cont ON (pedido.id_pedido = adi_cont.id_pedido)
						WHERE pedido.id_pedido = %s",
			valTpDato($idPedido, "int"),
			mysql_query("SET NAMES 'utf8';"));
	
	$rsAdi = mysql_query($querySQL);
	if (!$rsAdi) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$arrayCreditLife= "";
	$arrayGap = "";
	$arrayContServ = "";
	while($rowAdi = mysql_fetch_assoc($rsAdi)){
		if($rowAdi['id_filtro_factura'] == 6){
			$arrayGap = $rowAdi;
		}
		if($rowAdi['id_filtro_factura'] == 8){
			$arrayContServ = $rowAdi;
		}
		if($rowAdi['id_filtro_factura'] == 11){
			$arrayCreditLife = $rowAdi;
		}
	}
	
	
//******* B. SEGURO DE CREDITO OPCIONAL ********************//
	
	if($arrayCreditLife['nom_accesorio'] != null){
	
		//AQUIRIRA
		$posX = 96;
		$posY = 504;
		imagestring($img, 2, $posX-1, $posY-1,"X", $textColor);
	
		//OBTENDRA
		$posX = 96;
		$posY = 516;
		imagestring($img, 2, $posX-1, $posY-1,"X", $textColor);
	
		//NOMBRE DE POLIZA
	
		$posX= 96;
		$posY = 576;
		imagestring($img, 2, $posX, $posY-2,utf8_encode(substr($arrayCreditLife['nom_accesorio'],0,23)), $textColor);
	
		// PRIMA
	
		$posX = 308;
		$NposX = prtDI(formatoNumero($arrayCreditLife['precio_unitario']), $posX);
		imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero($arrayCreditLife['precio_unitario'])), $textColor);
			
			
		// MESES
			
		$posX = 360;
		imagestring($img, 2, $posX, $posY-2,utf8_encode(strtoupper($arrayCreditLife['per_credit_life'])), $textColor);
	
	
	} else {
	
		//NO AQUIRIRA
		$posX = 144;
		$posY = 504;
		imagestring($img, 2, $posX+3, $posY-1,"X", $textColor);
	
		//NO OBTENDRA
		$posX = 144;
		$posY = 516;
		imagestring($img, 2, $posX+3, $posY-1,"X", $textColor);
	
		//NOMBRE DE POLIZA
	
		$posX= 96;
		$posY = 576;
		imagestring($img, 2, $posX, $posY-2,"N/A", $textColor);
	
		// PRIMA
	
		$posX = 308;
		$NposX = prtDI(formatoNumero(0), $posX);
		imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero(0)), $textColor);
			
			
		// MESES
			
		$posX = 360;
		imagestring($img, 2, $posX, $posY-2,0, $textColor);
		
		//CARGO POR FINANCIAMIENTO
		
		
		$posX = 528;
		$NposX = prtDI(formatoNumero(0), $posX);
		imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero(0)), $textColor);
	
	}	
	
// //*****************************//
	
//******* C. ACUERDO DE PROTECCION GARANTIZADA (GAP) ********************//

	if($arrayGap['nom_accesorio'] != null){
	
		//AQUIRIRA
		$posX = 96;
		$posY = 648;
		imagestring($img, 2, $posX-1, $posY-1,"X", $textColor);
	
		//OBTENDRA
		$posX = 96;
		$posY = 660;
		imagestring($img, 2, $posX-1, $posY-1,"X", $textColor);
	
		//NOMBRE DE POLIZA
	
		$posX= 96;
		$posY = 708;
		imagestring($img, 2, $posX, $posY-2,utf8_encode(substr($arrayGap['nom_accesorio'],0,23)), $textColor);
	
		// PRIMA
	
		$posX = 308;
		$NposX = prtDI(formatoNumero($arrayGap['precio_unitario']), $posX);
		imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero($arrayGap['precio_unitario'])), $textColor);
			
			
		// MESES
			
		$posX = 360;
		imagestring($img, 2, $posX, $posY-2,utf8_encode(strtoupper($arrayGap['per_gap'])), $textColor);
	
	
	} else {
	
		//NO AQUIRIRA
		$posX = 144;
		$posY = 648;
		imagestring($img, 2, $posX+3, $posY-1,"X", $textColor);
	
		//NO OBTENDRA
		$posX = 144;
		$posY = 660;
		imagestring($img, 2, $posX+3, $posY-1,"X", $textColor);
	
		//NOMBRE DE POLIZA
	
		$posX= 96;
		$posY = 708;
		imagestring($img, 2, $posX, $posY-2,"N/A", $textColor);
	
		// PRIMA
	
		$posX = 308;
		$NposX = prtDI(formatoNumero(0), $posX);
		imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero(0)), $textColor);
			
			
		// MESES
			
		$posX = 360;
		imagestring($img, 2, $posX, $posY-2,0, $textColor);
		
		//CARGO POR FINANCIAMIENTO
		
		
		$posX = 528;
		$NposX = prtDI(formatoNumero(0), $posX);
		imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero(0)), $textColor);
	
	}
	
	
	
// //*****************************//

//******* D. OTRO SEGURO OPCIONAL ********************//
	
	if($arrayContServ['nom_accesorio'] != null){
	
		//AQUIRIRA
		$posX = 96;
		$posY = 756;
		imagestring($img, 2, $posX-3, $posY+1,"X", $textColor);
	
		//OBTENDRA
		$posX = 96;
		$posY = 768;
		imagestring($img, 2, $posX-3, $posY+1,"X", $textColor);
	
		//NOMBRE DE POLIZA
	
		$posX= 96;
		$posY = 828;
		imagestring($img, 2, $posX, $posY,utf8_encode(substr($arrayContServ['nom_accesorio'],0,23)), $textColor);
	
		// PRIMA
	
		$posX = 308;
		$NposX = prtDI(formatoNumero($arrayContServ['precio_unitario']), $posX);
		imagestring($img, 2, $NposX, $posY,utf8_encode(formatoNumero($arrayContServ['precio_unitario'])), $textColor);
			
			
		// MESES
			
		$posX = 360;
		imagestring($img, 2, $posX, $posY,utf8_encode(strtoupper($arrayContServ['per_cont_serv'])), $textColor);
	
	
	} else {
	
		//NO AQUIRIRA
		$posX = 144;
		$posY = 756;
		imagestring($img, 2, $posX+3, $posY+1,"X", $textColor);
	
		//NO OBTENDRA
		$posX = 144;
		$posY = 768;
		imagestring($img, 2, $posX+3, $posY+1,"X", $textColor);
	
		//NOMBRE DE POLIZA
	
		$posX= 96;
		$posY = 828;
		imagestring($img, 2, $posX, $posY,"N/A", $textColor);
	
		// PRIMA
	
		$posX = 308;
		$NposX = prtDI(formatoNumero(0), $posX);
		imagestring($img, 2, $NposX, $posY,utf8_encode(formatoNumero(0)), $textColor);
			
			
		// MESES
			
		$posX = 360;
		imagestring($img, 2, $posX, $posY,0, $textColor);
		
		//CARGO POR FINANCIAMIENTO
		
		$posX = 528;
		$NposX = prtDI(formatoNumero(0), $posX);
		imagestring($img, 2, $NposX, $posY-2,utf8_encode(formatoNumero(0)), $textColor);
		
	}
	
// ////////////// GUARDANDO IMAGENES EN ARRAY //////////////////////
		
		
			$arrayImg[] = "tmp/"."contrato_venta_info_seguro".$pageNum.".png";
 			$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		if($indice == 0){
		$pdf->Image($valor, 15, 59, 596, 1798); // PDF pagina 1 CON EL MEMBRETE SUJETADOR
	//	$pdf->Image($valor, 15, 15, 596, 1798); // PDF pagina 1 SIN EL MEMBRETE SUJETADOR
		} else {
		$pdf->Image($valor, 15, 0, 596, 1798); // PDF pagina 2
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