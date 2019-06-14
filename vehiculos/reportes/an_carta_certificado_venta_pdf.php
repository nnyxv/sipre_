<?php
require_once("../../connections/conex.php");

session_start();

include('../../inc_sesion.php');
if(!(validaAcceso("an_certificado"))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');
require('../../clases/fpdf/fpdf_print.inc.php');

$pdf=new PDF_AutoPrint('P','mm','Letter');
$pdf->SetAutoPageBreak(false);
$pdf->SetTitle('Certificado de origen');
/**************************** ARCHIVO PDF ****************************/


//Conectando con la base de datos:
conectar();

$id_pedido = excape($_GET['id']);
$view = excape($_GET['view']);

$sqlp = "SELECT * FROM vw_an_certificado WHERE id_pedido = ".$id_pedido.";";
$rs = @mysql_query($sqlp,$conex);
if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__."<br>Nro: ".mysql_errno($conex));
$row = mysql_fetch_assoc($rs);


$_font='courier';
$_sizefont=12;
$_minisizefont=10;
$_EFECTS='B';
$pdf->AddPage();
$pdf->SetFont($_font,$_EFECTS,$_sizefont);
$pdf->SetX(0);


//constantes: 
$_offsettop=173;//186 ok,177, 174, altautos 173
$_offsetleft=26;//25 ok
$_offsetheight=9;//10 ok,9

if(isset($_GET['cambio'])){
	if(isset($_GET['offsettop'])){
		$_offsettop=getmysqlnum($_GET['offsettop']);
	}
	if(isset($_GET['font'])){
		$_font=excape($_GET['font']);
	}
	if(isset($_GET['effects'])){
		$_EFECTS=excape($_GET['effects']);
	}
	if(isset($_GET['size'])){
		$_sizefont=getmysqlnum($_GET['size']);
	}
	if(isset($_GET['minisize'])){
		$_minisizefont=getmysqlnum($_GET['minisize']);
	}
}

//probando:

$pdf->SetY($_offsettop-2); //establaciendo el TOPE
$pdf->SetX($_offsetleft+130); //establñecioendo el margen

$pdf->SetFont($_font,$_EFECTS,$_minisizefont);
$pdf->Cell(0,0,date(spanDateFormat, strtotime($row['fechaRegistroFactura'])));

$pdf->SetY($_offsettop); //establaciendo el TOPE
$pdf->SetX($_offsetleft); //establñecioendo el margen

//imprimiendo el primer campo:

$pdf->SetFont($_font,$_EFECTS,$_sizefont);
$pdf->Cell(90,0,strtoupper($row['cedula']));
$pdf->Cell(0,0,strtoupper($row['numeroFactura']));


$pdf->SetY($pdf->GetY()+$_offsetheight);
$pdf->SetX($_offsetleft); //establñecioendo el margen
$pdf->Cell(0,0,strtoupper($row['comprador']));

//direccion:
$direccion=@split(';',strtoupper($row['direccion']));
//$municipio=trim(@$direccion[0]);
$urbanizacion=trim(@$direccion[0]);
$calle=trim(@$direccion[1]);
$casa=trim(@$direccion[2]);

$pdf->SetY($pdf->GetY()+$_offsetheight);
$pdf->SetX($_offsetleft); //establñecioendo el margen
$pdf->Cell(95,0,$casa);
$pdf->Cell(0,0,$calle);

$pdf->SetY($pdf->GetY()+$_offsetheight);
$pdf->SetX($_offsetleft); //establñecioendo el margen
$pdf->Cell(125,0,$urbanizacion);
$pdf->Cell(0,0,strtoupper($row['ciudad']));

$pdf->SetY($pdf->GetY()+$_offsetheight);
$pdf->SetX($_offsetleft); //establñecioendo el margen
$pdf->Cell(0,0,$municipio);

$pdf->SetY($pdf->GetY()+$_offsetheight-1);
$pdf->SetX($_offsetleft); //establñecioendo el margen
$pdf->SetFont($_font,$_EFECTS,$_minisizefont);

//picando los telefonos
if($row['telf']!=""){
	$t1=str_replace('-','',$row['telf']);
	$t1=str_replace('(','',$t1);
	$t1=str_replace(')','',$t1);
	$t1=str_replace('.','',$t1);
	$t1=str_replace(',','',$t1);
	$t1c=substr($t1,0,strlen($t1)-7);
	$t1t=substr($t1,strlen($t1c));
}

if($row['otrotelf']!=""){
	$t2=str_replace('-','',$row['otrotelf']);
	$t2=str_replace('(','',$t2);
	$t2=str_replace(')','',$t2);
	$t2=str_replace('.','',$t2);
	$t2=str_replace(',','',$t2);
	$t2c=substr($t2,0,strlen($t2)-7);
	$t2t=substr($t2,strlen($t2c));
}


/*$pdf->Cell(40,0,$t1c);
$pdf->Cell(48,0,$t1t);
$pdf->Cell(35,0,$t2c);
$pdf->Cell(0,0,$t2t);*/
$pdf->Cell(40,0,$t1c);
$pdf->Cell(48,0,$t1t);
$pdf->Cell(35,0,$t2c);
$pdf->Cell(0,0,$t2t);

$pdf->SetY($pdf->GetY()+$_offsetheight);
$pdf->SetX($_offsetleft); //establñecioendo el margen
$pdf->SetFont($_font,$_EFECTS,$_minisizefont);
$pdf->Cell(88,0,'');//seguro
$pdf->Cell(48,0,'');
$pdf->Cell(35,0,'');


$pdf->SetY($pdf->GetY()+$_offsetheight+1);
$pdf->SetX($_offsetleft); //establñecioendo el margen
$pdf->SetFont($_font,$_EFECTS,$_sizefont);
$pdf->Cell(0,0,strtoupper($row['reserva']));

$pdf->SetY($pdf->GetY()+$_offsetheight+4);
$pdf->SetX($_offsetleft+10); //establñecioendo el margen
$pdf->SetFont($_font,$_EFECTS,$_minisizefont);
$pdf->Cell(0,0,strtoupper($row['comprador']));

$pdf->SetY($pdf->GetY()+$_offsetheight-1);
$pdf->SetX($_offsetleft+100); //establñecioendo el margen
$pdf->SetFont($_font,$_EFECTS,$_minisizefont);
$pdf->Cell(0,0,strtoupper($row['nombre_empresa']));
	
if ($view == "print") {
	$pdf->AutoPrint(true);
}
$pdf->Output();
?>