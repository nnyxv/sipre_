<?php
require_once ("../connections/conex.php");

$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$idDocumento = $valCadBusq[0];

$query = sprintf("SELECT 
  sa_orden_tot.id_orden_tot,
  sa_orden.id_orden,
  sa_orden.numero_orden,
  sa_tipo_orden.nombre_tipo_orden,
  cp_factura.id_factura,             #para el iva
  cp_factura.numero_control_factura,
  cp_factura.numero_factura_proveedor,
  cp_factura.fecha_origen,
  cp_factura.fecha_vencimiento,
  pg_empleado.nombre_empleado,
  pg_empleado.apellido AS apellido_empleado,
  cp_proveedor.direccion,
  cp_proveedor.nombre AS nombre_proveedor,
  cp_proveedor.id_proveedor,
  cp_proveedor.telefono,
  cp_proveedor.lrif,
  cp_proveedor.rif,
  en_registro_placas.chasis,
  an_marca.nom_marca,
  en_registro_placas.kilometraje,
  en_registro_placas.placa,
  an_modelo.nom_modelo,
  an_uni_bas.nom_uni_bas,
  cp_factura.subtotal_factura,
  cp_factura.subtotal_descuento,
  cp_factura_iva.base_imponible,
  cp_factura_iva.subtotal_iva,
  B.id_fecha_reconversion as reconversion,
  IFNULL(cp_factura.subtotal_factura - cp_factura_iva.base_imponible, cp_factura.subtotal_factura) as montoNoGravado,
  IFNULL(cp_factura.subtotal_factura + SUM(cp_factura_iva.subtotal_iva), cp_factura.subtotal_factura) AS montoTotalFactura, #ojo el sum automaticamente agrupa por ivas
  cp_factura_iva.iva #iva del registro y no actual de pg_iva
FROM
  sa_orden
  LEFT OUTER JOIN sa_tipo_orden ON (sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden)
  LEFT OUTER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
  LEFT OUTER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
  LEFT OUTER JOIN en_registro_placas ON (sa_cita.id_registro_placas = en_registro_placas.id_registro_placas)
  LEFT OUTER JOIN an_uni_bas ON (en_registro_placas.id_unidad_basica = an_uni_bas.id_uni_bas)
  LEFT OUTER JOIN an_marca ON (an_uni_bas.mar_uni_bas = an_marca.id_marca)
  LEFT OUTER JOIN an_modelo ON (an_uni_bas.mod_uni_bas = an_modelo.id_modelo)
  RIGHT OUTER JOIN sa_orden_tot ON (sa_orden.id_orden = sa_orden_tot.id_orden_servicio)
  INNER JOIN cp_factura ON (sa_orden_tot.id_factura = cp_factura.id_factura)
  INNER JOIN cp_proveedor ON (cp_factura.id_proveedor = cp_proveedor.id_proveedor)
  LEFT JOIN cp_reconversion B ON (B.id_factura=cp_factura.id_factura)
  INNER JOIN pg_usuario ON (sa_orden_tot.id_usuario = pg_usuario.id_usuario)
  LEFT JOIN cp_factura_iva ON (cp_factura.id_factura = cp_factura_iva.id_factura)
  INNER JOIN pg_empleado ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
  #LEFT OUTER JOIN pg_iva ON (cp_factura_iva.id_iva = pg_iva.idIva)
  WHERE id_orden_tot = %s",
	valTpDato($idDocumento,"int"));

$rs = mysql_query($query, $conex);
if(!$rs) { die (mysql_error()."<br> Linea: ".__LINE__); }
//if (!$rsAlmacen) return $objResponse->alert(mysql_error());
$row = mysql_fetch_assoc($rs);

$img = @imagecreate(600, 640) or die("No se puede crear la imagen");

//estableciendo los colores de la paleta:
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

imagestring($img,1,370,10,"REGISTRO DE COMPRA",$textColor);

imagestring($img,1,340,20,"ORDEN DE TRABAJO",$textColor);
imagestring($img,1,430,20,":",$textColor);
imagestring($img,1,440,20,$row['numero_orden']." ".$row['nombre_tipo_orden'],$textColor); // <----

imagestring($img,1,340,30,"SERIE-SR",$textColor);

imagestring($img,1,340,40,"FACTURA NRO.",$textColor);
imagestring($img,1,430,40,":",$textColor);
imagestring($img,1,440,40,$row['numero_factura_proveedor'],$textColor); // <----

imagestring($img,1,340,50,"FECHA EMISION",$textColor);
imagestring($img,1,430,50,":",$textColor);
imagestring($img,1,440,50,date("d-m-Y", strtotime($row['fecha_origen'])),$textColor); // <----

imagestring($img,1,340,60,"FECHA VENCIMIENTO",$textColor);
imagestring($img,1,430,60,":",$textColor);
imagestring($img,1,440,60,date("d-m-Y", strtotime($row['fecha_vencimiento'])),$textColor); // <----
//imagestring($img,1,440,60,date("d-m-Y", strtotime($row['fecha_vencimiento']))." CRED. ".$row['diasDeCredito']." D".utf8_decode("Í")."AS",$textColor); // <----

imagestring($img,1,340,70,"ASESOR",$textColor);
imagestring($img,1,430,70,":",$textColor);
imagestring($img,1,440,70,strtoupper($row['nombre_empleado']." ".$row['apellido_empleado']),$textColor); // <----


$dirCliente = explode(",",strtoupper($row['direccion']));


imagestring($img,1,15,40,strtoupper($row['nombre_proveedor']),$textColor); // <----
imagestring($img,1,190,40,"CODIGO",$textColor);
imagestring($img,1,220,40,":",$textColor);
imagestring($img,1,230,40,$row['id_proveedor'],$textColor); // <----

imagestring($img,1,15,50,substr($dirCliente[0],0,60),$textColor); // <----

imagestring($img,1,15,60,trim($dirCliente[1])." ".trim($dirCliente[2]),$textColor); // <----
imagestring($img,1,190,60,"TELEF.",$textColor);
imagestring($img,1,220,60,":",$textColor);
imagestring($img,1,230,60,$row['telefono'],$textColor); // <----

imagestring($img,1,15,70,trim($dirCliente[3])." ".trim($dirCliente[4])." ".trim($dirCliente[5]),$textColor); // <----
imagestring($img,1,190,70,$spanRIF,$textColor);
imagestring($img,1,220,70,":",$textColor);
imagestring($img,1,230,70,$row['lrif']."-".$row['rif'],$textColor); // <----



imagestring($img,1,5,110,"-------------------------------------------------------------------------------------------------------------------",$textColor);
imagestring($img,1,30,120,"CODIGO",$textColor); // <----
imagestring($img,1,190,120,"DESCRIPCION",$textColor); // <----
imagestring($img,1,330,120,"CANTIDAD/MECANICO",$textColor); // <----
imagestring($img,1,435,120,"PRECIO UNIT.",$textColor); // <----
imagestring($img,1,525,120,"TOTAL",$textColor); // <----
imagestring($img,1,5,130,"-------------------------------------------------------------------------------------------------------------------",$textColor);

$posY = 140;
/* DETALLE DE LOS TOT */
$queryDetalleTot = sprintf("SELECT * FROM sa_orden_tot_detalle
WHERE id_orden_tot = %s",
	valTpDato($idDocumento,"int"));
$rsDetalleTot = mysql_query($queryDetalleTot);
if(!$rsDetalleTot) { die (mysql_error()."<br> Linea: ".__LINE__); }

while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
	if($rowDetalleTot['cantidad'] != NULL && $rowDetalleTot['cantidad'] != "" && $rowDetalleTot['cantidad'] !=0){
            $cantidad = $rowDetalleTot['cantidad'];
        }else{
            $cantidad = 1;
        }
        
        
	$precioUnit = ($rowDetalleTot['monto'] > 0) ? number_format($rowDetalleTot['monto'],2,".",",") : "--";
	$total = ($rowDetalleTot['monto'] > 0) ? number_format(($cantidad*$rowDetalleTot['monto']),2,".",",") : "--";
	$posXCantidad = 340+((5*13)-(strlen($cantidad)*5))/2;
	$posXPrecio = 425+((5*15)-(strlen($precioUnit)*5));
	$posXTotal = 500+((5*15)-(strlen($total)*5));
	
	imagestring($img,1,10,$posY,$rowDetalleTot['id_orden_tot_detalle'],$textColor); // <----
	imagestring($img,1,110,$posY,$rowDetalleTot['descripcion_trabajo'],$textColor); // <----
	imagestring($img,1,$posXCantidad,$posY,$cantidad,$textColor); // <----
	imagestring($img,1,$posXPrecio,$posY,$precioUnit,$textColor); // <----
	imagestring($img,1,$posXTotal,$posY,$total,$textColor); // <----
	
	$posY += 10;
}


imagestring($img,1,5,550,"----------------------------------------------------------------------",$textColor);

imagestring($img,1,15,560,"CHASIS",$textColor);
imagestring($img,1,50,560,":",$textColor);
imagestring($img,1,60,560,$row['chasis'],$textColor); // <----

imagestring($img,1,145,560,"MARCA",$textColor);
imagestring($img,1,180,560,":",$textColor);
imagestring($img,1,190,560,$row['nom_marca'],$textColor); // <----

imagestring($img,1,260,560,substr(strtoupper($spanKilometraje),0,8),$textColor);
imagestring($img,1,305,560,":",$textColor);
imagestring($img,1,315,560,$row['kilometraje'],$textColor); // <----

imagestring($img,1,15,570,"PLACA",$textColor);
imagestring($img,1,50,570,":",$textColor);
imagestring($img,1,60,570,$row['placa'],$textColor); // <----

imagestring($img,1,145,570,"MODELO",$textColor);
imagestring($img,1,180,570,":",$textColor);
imagestring($img,1,190,570,$row['nom_modelo'],$textColor); // <----

imagestring($img,1,260,570,"CATALOGO",$textColor);
imagestring($img,1,305,570,":",$textColor);
imagestring($img,1,315,570,$row['nom_uni_bas'],$textColor); // <----

imagestring($img,1,5,580,"----------------------------------------------------------------------",$textColor);


$subTotal = number_format($row['subtotal_factura'],2,".",",");
$posX = 510+((5*13)-(strlen($subTotal)*5));
imagestring($img,1,360,550,"SUB-TOTAL",$textColor);
imagestring($img,1,485,550,":",$textColor);
imagestring($img,1,$posX,550,$subTotal,$textColor); // <----

$descuento = number_format($row['subtotal_descuento'],2,".",",");
$posX = 510+((5*13)-(strlen($descuento)*5));
imagestring($img,1,360,560,"DESCUENTO",$textColor);
imagestring($img,1,485,560,":",$textColor);
imagestring($img,1,$posX,560,$descuento,$textColor); // <----

$baseImponible = number_format($row['base_imponible'],2,".",",");
$posX = 510+((5*13)-(strlen($baseImponible)*5));
imagestring($img,1,360,580,"BASE IMPONIBLE",$textColor);
imagestring($img,1,485,580,":",$textColor);
imagestring($img,1,$posX,580,$baseImponible,$textColor); // <----


$montoNoGravado = number_format($row['montoNoGravado'],2,".",",");
    $posX = 510+((5*13)-(strlen($montoNoGravado)*5));
    imagestring($img,1,360,600,"MONTO NO GRAVADO",$textColor);
    imagestring($img,1,485,600,":",$textColor);
    imagestring($img,1,$posX,600,$montoNoGravado ,$textColor); // <----

/* IVA IMPUESTOS */
$sqlIvaTot = sprintf("SELECT cp_factura_iva.subtotal_iva, "
        . "cp_factura_iva.id_iva, "
        . "concat(cp_factura_iva.iva,'%%') as porcentaje_iva, "
        . "pg_iva.observacion "
        . "FROM cp_factura_iva "
        . "LEFT JOIN pg_iva ON cp_factura_iva.id_iva = pg_iva.idIva WHERE id_factura = %s GROUP BY id_iva",
                valTpDato($row["id_factura"],"int"));
$rsIvaFactura = mysql_query($sqlIvaTot);
if(!$rsIvaFactura) { die (mysql_error()."<br> Linea: ".__LINE__); }


if(mysql_num_rows($rsIvaFactura)){

    $lineaY = 610;
    while ($rowIvas = mysql_fetch_assoc($rsIvaFactura)){


       $porcentajeIva = $rowIvas['porcentaje_iva'];
       $calculoIva = number_format($rowIvas['subtotal_iva'],2,".",",");
       $posXIva = 440+((5*8)-(strlen($porcentajeIva)*5));
       $posX = 510+((5*13)-(strlen($calculoIva)*5));
       imagestring($img,1,360,$lineaY,strtoupper(utf8_encode($rowIvas['observacion'])),$textColor);
       imagestring($img,1,$posXIva,$lineaY,$porcentajeIva,$textColor);
       imagestring($img,1,485,$lineaY,":",$textColor);
       imagestring($img,1,$posX,$lineaY,$calculoIva,$textColor); // <----

       $lineaY = $lineaY+10;
       
       if($lineaY == 630){
           break;
       }

    }
}else{
    $porcentajeIva = number_format($row['iva'],2,".",",")."%";
    $calculoIva = number_format($row['subtotal_iva'],2,".",",");
    $posXIva = 440+((5*8)-(strlen($porcentajeIva)*5));
    $posX = 510+((5*13)-(strlen($calculoIva)*5));
    imagestring($img,1,360,610,nombreIva(1),$textColor);//sino tiene varias, por defecto??
    imagestring($img,1,$posXIva,610,$porcentajeIva,$textColor);
    imagestring($img,1,485,600,":",$textColor);
    imagestring($img,1,$posX,610,$calculoIva,$textColor); // <----

    $porcentajeIva = number_format(0,2,".",",")."%";
    $calculoImpuestoLujo = number_format(0,2,".",",");
    $posXIva = 440+((5*8)-(strlen($porcentajeIva)*5));
    $posX = 510+((5*13)-(strlen($calculoImpuestoLujo)*5));
    imagestring($img,1,360,620,"IMPUESTO AL LUJO",$textColor);
    imagestring($img,1,$posXIva,620,$porcentajeIva,$textColor);
    imagestring($img,1,485,620,":",$textColor);
    imagestring($img,1,$posX,620,$calculoImpuestoLujo,$textColor); // <----
}
 
 //ANTES
 /*
$porcentajeIva = number_format($row['iva'],2,".",",")."%";
$calculoIva = number_format($row['subtotal_iva'],2,".",",");
$posXIva = 440+((5*8)-(strlen($porcentajeIva)*5));
$posX = 510+((5*13)-(strlen($calculoIva)*5));
imagestring($img,1,360,600,"I.V.A.",$textColor);
imagestring($img,1,$posXIva,600,$porcentajeIva,$textColor);
imagestring($img,1,485,600,":",$textColor);
imagestring($img,1,$posX,600,$calculoIva,$textColor); // <----

$montoNoGravado = number_format($row['montoNoGravado'],2,".",",");
$posX = 510+((5*13)-(strlen($montoNoGravado)*5));
imagestring($img,1,360,610,"MONTO NO GRAVADO",$textColor);
imagestring($img,1,485,610,":",$textColor);
imagestring($img,1,$posX,610,$montoNoGravado ,$textColor); // <----

$porcentajeIva = number_format(0,2,".",",")."%";
$calculoImpuestoLujo = number_format(0,2,".",",");
$posXIva = 440+((5*8)-(strlen($porcentajeIva)*5));
$posX = 510+((5*13)-(strlen($calculoImpuestoLujo)*5));
imagestring($img,1,360,620,"IMPUESTO AL LUJO",$textColor);
imagestring($img,1,$posXIva,620,$porcentajeIva,$textColor);
imagestring($img,1,485,620,":",$textColor);
imagestring($img,1,$posX,620,$calculoImpuestoLujo,$textColor); // <----
*/

/* TOTAL FACTURA */
  if ($row['reconversion']==null) {
      if ($row['fecha_origen']>='2018-08-01' and $row['fecha_origen']<'2018-08-20') {
            $totalFactura = number_format($row['montoTotalFactura'],2,".",",");
            $posX = 510+((5*13)-(strlen($totalFactura)*5));
            imagestring($img,1,360,620,"TOTAL",$textColor);//antes 630 ahora $lineaY
            imagestring($img,1,485,620,":",$textColor);
            imagestring($img,1,$posX,620,$totalFactura,$textColor); // <----
            $totalFacturaa = number_format($row['montoTotalFactura']/100000,2,".",",");
            imagestring($img,1,360,630,"TOTAL Bs.S",$textColor);//antes 630 ahora $lineaY
            imagestring($img,1,485,630,":",$textColor);
            imagestring($img,1,$posX,630,$totalFacturaa,$textColor); // <----
        }else if ($row['fecha_origen']>='2018-08-20') {
            $totalFacturaa = number_format($row['montoTotalFactura']/100000,2,".",",");
            imagestring($img,1,360,620,"TOTAL Bs.S",$textColor);//antes 620 ahora $lineaY
            imagestring($img,1,485,620,":",$textColor);
            imagestring($img,1,$posX,620,$totalFacturaa,$textColor); // <----
            $totalFactura = number_format($row['montoTotalFactura'],2,".",",");
            $posX = 510+((5*13)-(strlen($totalFactura)*5));
            imagestring($img,1,360,630,"TOTAL",$textColor);//antes 630 ahora $lineaY
            imagestring($img,1,485,630,":",$textColor);
            imagestring($img,1,$posX,630,$totalFactura,$textColor); // <----
        }else{
            $totalFactura = number_format($row['montoTotalFactura'],2,".",",");
            $posX = 510+((5*13)-(strlen($totalFactura)*5));
            imagestring($img,1,360,620,"TOTAL",$textColor);//antes 630 ahora $lineaY
            imagestring($img,1,485,620,":",$textColor);
            imagestring($img,1,$posX,620,$totalFactura,$textColor); // <----
        }
  }else{
        if ($row['fecha_origen']>='2018-08-01' and $row['fecha_origen']<'2018-08-20') {
            $totalFactura = number_format($row['montoTotalFactura']*100000,2,".",",");
            $posX = 510+((5*13)-(strlen($totalFactura)*5));
            imagestring($img,1,360,620,"TOTAL",$textColor);//antes 630 ahora $lineaY
            imagestring($img,1,485,620,":",$textColor);
            imagestring($img,1,$posX,620,$totalFactura,$textColor); // <----
            $totalFacturaa = number_format($row['montoTotalFactura'],2,".",",");
            imagestring($img,1,360,630,"TOTAL Bs.S",$textColor);//antes 630 ahora $lineaY
            imagestring($img,1,485,630,":",$textColor);
            imagestring($img,1,$posX,630,$totalFacturaa,$textColor); // <----
        }else if ($row['fecha_origen']>='2018-08-20') {
            $totalFacturaa = number_format($row['montoTotalFactura'],2,".",",");
            imagestring($img,1,360,620,"TOTAL Bs.S",$textColor);//antes 620 ahora $lineaY
            imagestring($img,1,485,620,":",$textColor);
            imagestring($img,1,$posX,620,$totalFacturaa,$textColor); // <----
            $totalFactura = number_format($row['montoTotalFactura']*100000,2,".",",");
            $posX = 510+((5*13)-(strlen($totalFactura)*5));
            imagestring($img,1,360,630,"TOTAL",$textColor);//antes 630 ahora $lineaY
            imagestring($img,1,485,630,":",$textColor);
            imagestring($img,1,$posX,630,$totalFactura,$textColor); // <----
        }else{
            $totalFactura = number_format($row['montoTotalFactura']*100000,2,".",",");
            $posX = 510+((5*13)-(strlen($totalFactura)*5));
            imagestring($img,1,360,620,"TOTAL",$textColor);//antes 630 ahora $lineaY
            imagestring($img,1,485,620,":",$textColor);
            imagestring($img,1,$posX,620,$totalFactura,$textColor); // <----
        }
    }
// <----




$r = imagepng($img,"img/tmp/"."factura_venta".'.png');


/* ARCHIVO PDF */
require('clases/fpdf/fpdf.php');
require('clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");

$pdf->AddPage();

$pdf->Image("img/tmp/factura_venta.png", 10, 95, 600, 640);

$pdf->SetDisplayMode(88);
//$pdf->AutoPrint(true);
$pdf->Output();


//aqui busca directamente la oservacion, este lo uso por defecto cuando no hay iva
function nombreIva($idIva){
    //cuando se crea no posee iva, por lo tanto deberia ser el primero id 1 itbms-iva
    if($idIva == NULL || $idIva == "0" || $idIva == "" || $idIva == " "){
        $idIva = 1;
    }    
    $query = "SELECT observacion FROM pg_iva WHERE idIva = ".$idIva."";
    $rs = mysql_query($query);
    if(!$rs){ die ("Error cargarDcto \n".mysql_error().$query."\n Linea: ".__LINE__); }
    
    $row = mysql_fetch_assoc($rs);
    
    return $row['observacion'];
    
}
?>
