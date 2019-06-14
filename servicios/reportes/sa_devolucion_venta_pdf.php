<?php
require_once ("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../clases/fpdf/fpdf.php');
require('../clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

/*$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$tpDocumento = $valCadBusq[0];
$idDocumento = $valCadBusq[1];*/

$idDocumento = $_GET["valBusq"];

$query = sprintf("SELECT nota_cred.*,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
	cliente.telf,
	cliente.otrotelf
FROM cj_cc_cliente cliente
	INNER JOIN cj_cc_notacredito nota_cred ON (cliente.id = nota_cred.idCliente)
WHERE nota_cred.idNotaCredito = %s
	AND nota_cred.idDepartamentoNotaCredito = 1",
	valTpDato($idDocumento,"int"));
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowNotaCred = mysql_fetch_assoc($rs);

$idEmpresa = $rowNotaCred['id_empresa'];
$idFactura = $rowNotaCred['idDocumento'];

// BUSCA LOS DATOS DE LA FACTURA
$queryFactura = sprintf("SELECT
	fact_vent.idFactura,
	fact_vent.numeroFactura,
	fact_vent.numeroControl,
	fact_vent.fechaRegistroFactura,
	fact_vent.subtotalFactura,
	fact_vent.descuentoFactura,
	fact_vent.baseImponible,
	fact_vent.montoExento,
        orden.idIva,
        sa_filtro_orden.tot_accesorio,
	fact_vent.porcentajeIvaFactura,
	fact_vent.calculoIvaFactura,
	0 AS montoNoGravado,
	fact_vent.porcentajeIvaDeLujoFactura,
	fact_vent.calculoIvaDeLujoFactura,
	fact_vent.montoTotalFactura,
	cliente.id,
	CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	cliente.telf,
	cliente.direccion AS direccion_cliente,
	uni_bas.*,
	marca.*,
	modelo.*,
	en_registro_placas.chasis,
	en_registro_placas.kilometraje,
	en_registro_placas.placa,
                B.fecha_reconversion,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado
FROM cj_cc_cliente cliente
	INNER JOIN cj_cc_encabezadofactura fact_vent ON (cliente.id = fact_vent.idCliente)
                left JOIN cj_cc_factura_reconversion B on (fact_vent.idFactura=B.id_factura)
	INNER JOIN pg_empleado empleado ON (fact_vent.idVendedor = empleado.id_empleado)
	LEFT OUTER JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden)
	LEFT OUTER JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden)
        LEFT OUTER JOIN sa_filtro_orden ON tipo_orden.id_filtro_orden = sa_filtro_orden.id_filtro_orden
	LEFT OUTER JOIN sa_recepcion recepcion ON (orden.id_recepcion = recepcion.id_recepcion)
	LEFT OUTER JOIN sa_cita cita ON (recepcion.id_cita = cita.id_cita)
	LEFT OUTER JOIN en_registro_placas ON (cita.id_registro_placas = en_registro_placas.id_registro_placas)
	LEFT OUTER JOIN an_uni_bas uni_bas ON (en_registro_placas.id_unidad_basica = uni_bas.id_uni_bas)
	LEFT OUTER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
	LEFT OUTER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
WHERE fact_vent.idFactura = %s",
	valTpDato($idFactura,"int"));
$rsFactura = mysql_query($queryFactura, $conex);
if (!$rsFactura) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFactura = mysql_num_rows($rsFactura);
$rowFactura = mysql_fetch_assoc($rsFactura);

$img = @imagecreate(470, 558) or die("No se puede crear la imagen");

// ESTABLECIENDO LOS COLORES DE LA PALETA
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

$posY = 9;
imagestring($img,1,300,$posY,str_pad(utf8_decode("NOTA DE CRÉDITO SERIE - SR"), 34, " ", STR_PAD_BOTH),$textColor);

$posY += 9;
$posY += 9;
imagestring($img,1,300,$posY,utf8_decode("NOTA CRÉD. NRO."),$textColor);
imagestring($img,2,375,$posY-3,": ".$rowNotaCred['numeracion_nota_credito'],$textColor);

$posY += 9;
imagestring($img,1,300,$posY,utf8_decode("FECHA EMISIÓN"),$textColor);
imagestring($img,1,375,$posY,": ".date("d-m-Y", strtotime($rowNotaCred['fechaNotaCredito'])),$textColor);

$posY += 9;
/*imagestring($img,1,300,$posY,utf8_decode("ORDEN NRO."),$textColor);
imagestring($img,1,375,$posY,": ".$row['numeroPedido'],$textColor);*/

$posY += 9;
imagestring($img,1,300,$posY,utf8_decode("ASESOR"),$textColor);
imagestring($img,1,375,$posY,": ".strtoupper($rowFactura['nombre_empleado']),$textColor);

$posY = 28;
imagestring($img,1,170,$posY,utf8_decode("CÓDIGO"),$textColor);
imagestring($img,1,200,$posY,": ".$rowNotaCred['id'],$textColor);

$posY += 9;
imagestring($img,1,0,$posY,strtoupper($rowNotaCred['nombre_cliente']),$textColor); // <----

$direccionCliente = strtoupper(str_replace(",", " ", $rowNotaCred['direccion_cliente']));
$posY += 9;
imagestring($img,1,0,$posY,trim(substr($direccionCliente,0,50)),$textColor); // <----

$posY += 9;
imagestring($img,1,0,$posY,trim(substr($direccionCliente,50,30)),$textColor); // <----
imagestring($img,1,155,$posY,$spanCI."/".$spanRIF,$textColor);
imagestring($img,1,220,$posY,": ".strtoupper($rowNotaCred['ci_cliente']),$textColor);

$posY += 9;
imagestring($img,1,0,$posY,trim(substr($direccionCliente,80,30)),$textColor); // <----
imagestring($img,1,155,$posY,utf8_decode("TELEFONO"),$textColor);
imagestring($img,1,220,$posY,": ".$rowNotaCred['telf'],$textColor);

$posY += 9;
imagestring($img,1,0,$posY,trim(substr($direccionCliente,120,30)),$textColor); // <----
imagestring($img,1,230,$posY,$rowNotaCred['otrotelf'],$textColor);


$posY = 90;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);
$posY += 9;
imagestring($img,1,0,$posY,str_pad(utf8_decode("CÓDIGO"), 22, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,115,$posY,str_pad(utf8_decode("DESCRIPCIÓN"), 26, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,250,$posY,str_pad(utf8_decode("CANT./MEC."), 12, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,315,$posY,str_pad(utf8_decode("PRECIO UNIT."), 15, " ", STR_PAD_BOTH),$textColor); // <----
imagestring($img,1,395,$posY,str_pad(utf8_decode("TOTAL"), 15, " ", STR_PAD_BOTH),$textColor); // <----
$posY += 9;
imagestring($img,1,0,$posY,str_pad("", 94, "-", STR_PAD_BOTH),$textColor);

// DETALLES DE LOS REPUESTOS
$queryRepuestosGenerales = sprintf("SELECT 
	iv_subsecciones.id_subseccion,
	iv_articulos.codigo_articulo,
	sa_det_fact_articulo.cantidad,
	sa_det_fact_articulo.precio_unitario,
	sa_det_fact_articulo.id_iva,
	sa_det_fact_articulo.iva,
	sa_det_fact_articulo.id_articulo,
	sa_det_fact_articulo.id_det_fact_articulo,
	iv_tipos_articulos.descripcion AS descripcion_tipo,
	iv_articulos.descripcion AS descripcion_articulo,
	iv_secciones.descripcion AS descripcion_seccion,
	sa_det_fact_articulo.aprobado,
	sa_det_fact_articulo.id_paquete,
	sa_paquetes.codigo_paquete
FROM iv_articulos
	INNER JOIN sa_det_fact_articulo ON (iv_articulos.id_articulo = sa_det_fact_articulo.id_articulo)
	INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
	INNER JOIN iv_tipos_articulos ON (iv_articulos.id_tipo_articulo = iv_tipos_articulos.id_tipo_articulo)
	INNER JOIN iv_secciones ON (iv_subsecciones.id_seccion = iv_secciones.id_seccion)
	LEFT OUTER JOIN sa_paquetes ON (sa_det_fact_articulo.id_paquete = sa_paquetes.id_paquete)
WHERE sa_det_fact_articulo.idFactura = %s
ORDER BY sa_det_fact_articulo.id_paquete",
	valTpDato($idFactura,"int"));
$rsOrdenDetRep = mysql_query($queryRepuestosGenerales, $conex);
if (!$rsOrdenDetRep) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsOrdenDetRep = mysql_num_rows($rsOrdenDetRep);
while ($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)) {
	$total = ($rowOrdenDetRep['cantidad'] * $rowOrdenDetRep['precio_unitario']);
	
	$posY += 9;
	imagestring($img,1,0,$posY,elimCaracter($rowOrdenDetRep['codigo_articulo'],";"),$textColor);
	imagestring($img,1,115,$posY,strtoupper(substr($rowOrdenDetRep['descripcion_articulo'],0,26)),$textColor);
	imagestring($img,1,250,$posY,strtoupper(str_pad(number_format($rowOrdenDetRep['cantidad'], 2, ".", ","), 12, " ", STR_PAD_BOTH)),$textColor);
	imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($rowOrdenDetRep['precio_unitario'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($total, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
}

// DETALLES DE LOS TEMPARIOS
$queryFactDetTemp = sprintf("SELECT 
	sa_modo.descripcion_modo,
	sa_tempario.codigo_tempario,
	sa_tempario.descripcion_tempario,
	sa_det_fact_tempario.operador,
	sa_det_fact_tempario.id_tempario,
	sa_det_fact_tempario.precio,
	sa_det_fact_tempario.base_ut_precio,
	sa_det_fact_tempario.id_modo,
	(CASE sa_det_fact_tempario.id_modo
		WHEN '1' THEN
			sa_det_fact_tempario.ut * sa_det_fact_tempario.precio_tempario_tipo_orden / sa_det_fact_tempario.base_ut_precio
		WHEN '2' THEN
			sa_det_fact_tempario.precio
		WHEN '3' THEN
			sa_det_fact_tempario.costo
		WHEN '4' THEN
			'4'
	END) AS total_por_tipo_orden,
	(CASE sa_det_fact_tempario.id_modo
		WHEN '1' THEN
			sa_det_fact_tempario.ut
		WHEN '2' THEN
			sa_det_fact_tempario.precio
		WHEN '3' THEN
			sa_det_fact_tempario.costo
		WHEN '4' THEN
			'4'
	END) AS precio_por_tipo_orden,
	sa_det_fact_tempario.id_det_fact_tempario,
	pg_empleado.nombre_empleado,
	pg_empleado.apellido,
	pg_empleado.id_empleado,
	sa_mecanicos.id_mecanico,
	sa_det_fact_tempario.aprobado,
	sa_det_fact_tempario.origen_tempario,
	sa_det_fact_tempario.origen_tempario + 0 AS idOrigen,
	sa_paquetes.codigo_paquete,
	sa_paquetes.id_paquete,
	sa_det_fact_tempario.precio_tempario_tipo_orden
FROM sa_det_fact_tempario
	INNER JOIN sa_tempario ON (sa_det_fact_tempario.id_tempario = sa_tempario.id_tempario)
	INNER JOIN sa_modo ON (sa_det_fact_tempario.id_modo = sa_modo.id_modo)
	INNER JOIN sa_mecanicos ON (sa_det_fact_tempario.id_mecanico = sa_mecanicos.id_mecanico)
	INNER JOIN pg_empleado ON (sa_mecanicos.id_empleado = pg_empleado.id_empleado)
	LEFT OUTER JOIN sa_paquetes ON (sa_det_fact_tempario.id_paquete = sa_paquetes.id_paquete)
	INNER JOIN cj_cc_encabezadofactura ON (sa_det_fact_tempario.idFactura = cj_cc_encabezadofactura.idFactura)
	INNER JOIN cj_cc_notacredito ON (cj_cc_encabezadofactura.idFactura = cj_cc_notacredito.idDocumento)
WHERE cj_cc_notacredito.idNotaCredito = %s
ORDER BY sa_det_fact_tempario.id_paquete",
	valTpDato($idDocumento,"int"));
$rsFactDetTemp = mysql_query($queryFactDetTemp, $conex);
if (!$rsFactDetTemp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsFactDetTemp = mysql_num_rows($rsFactDetTemp);
while ($rowFactDetTemp = mysql_fetch_assoc($rsFactDetTemp)) {
	//Es entre 100 o la base ut? : $rowFactDetTemp['precio_por_tipo_orden']/100
	$caractCantTempario = ($rowFactDetTemp['id_modo'] == 1) ? number_format($rowFactDetTemp['precio_por_tipo_orden']/100,2,".",",") : number_format(1,2,".",",");
		
	$caracterPrecioTempario = ($rowFactDetTemp['id_modo'] == 1) ? $rowFactDetTemp['precio_tempario_tipo_orden'] : $rowFactDetTemp['precio_por_tipo_orden'];
	
	$cantidad = $caractCantTempario."/MEC:".sprintf("%04s",$rowFactDetTemp['id_mecanico']);
	
	$posY += 7;
	imagestring($img,1,0,$posY,$rowFactDetTemp['codigo_tempario'],$textColor);
	imagestring($img,1,115,$posY,strtoupper(substr($rowFactDetTemp['descripcion_tempario'],0,26)),$textColor);
	imagestring($img,1,250,$posY,strtoupper(str_pad($cantidad, 12, " ", STR_PAD_BOTH)),$textColor);
	imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($caracterPrecioTempario, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowFactDetTemp['total_por_tipo_orden'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
}

// DETALLE DE LOS TOT
$queryDetalleTot = sprintf("SELECT * FROM sa_orden_tot
	INNER JOIN cp_proveedor ON (sa_orden_tot.id_proveedor = cp_proveedor.id_proveedor)
	INNER JOIN sa_det_fact_tot ON (sa_orden_tot.id_orden_tot = sa_det_fact_tot.id_orden_tot)
	INNER JOIN cj_cc_encabezadofactura ON (sa_det_fact_tot.idFactura = cj_cc_encabezadofactura.idFactura)
	INNER JOIN cj_cc_notacredito ON (cj_cc_encabezadofactura.idFactura = cj_cc_notacredito.idDocumento)
WHERE cj_cc_notacredito.idNotaCredito = %s AND sa_orden_tot.monto_subtotal > 0",
	valTpDato($idDocumento,"int"));
$rsDetalleTot = mysql_query($queryDetalleTot);
if (!$rsDetalleTot) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetalleTot = mysql_num_rows($rsDetalleTot);
while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
	$cantidad = "1";
	$precioUnit = $rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100);
	$total = $rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100);
	
	$posY += 9;
        $adicionalY = 1;
	imagestring($img,1,0,$posY,"TOT".$rowDetalleTot['numero_tot'],$textColor); // <----
        if(strlen($rowDetalleTot['observacion_factura']) > 28 && $rowFactura["tot_accesorio"] == 1){
            $adicionalY = dividirObservacion($img, $rowDetalleTot['observacion_factura'], $posY, $textColor);
        }else{
            imagestring($img,1,115,$posY,strtoupper(substr($rowDetalleTot['observacion_factura'],0,28)),$textColor); // <----
        }	
        
        if($rowFactura["tot_accesorio"] == 1){
            
        }else{
            imagestring($img,1,250,$posY,strtoupper(str_pad($cantidad, 12, " ", STR_PAD_BOTH)),$textColor); // <----
            imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($precioUnit, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor); // <----
            imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($total, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor); // <----
        }
        $posY += 9*($adicionalY-1);
        
        if($rowFactura["tot_accesorio"] == 1){
            $queryItemsTot = sprintf("SELECT descripcion_trabajo, monto, cantidad, id_precio_tot FROM sa_orden_tot_detalle WHERE id_orden_tot = %s",
                                        $rowDetalleTot['id_orden_tot']);
            $rsItemsTot = mysql_query($queryItemsTot) or die(mysql_error());
            
//            $cantidadAcc = mysql_num_rows($rsItemsTot);
            
            while($rowItemsTot = mysql_fetch_assoc($rsItemsTot)){   
//                if($cantidadAcc == 1 && $rowItemsTot['id_precio_tot']== 1){
//                    break;
//                }
                $cantidadDetalle = $rowItemsTot['cantidad'];
                $precioUnitDetalle = $rowItemsTot['monto']+($rowItemsTot['monto']*($rowDetalleTot['porcentaje_tot']/100));
                
                if($cantidadDetalle == NULL || $cantidadDetalle == "" || $cantidadDetalle == 0 ){
                   $cantidadDetalle = 1;
                }
                
                $totalDetalle = number_format($cantidadDetalle*$precioUnitDetalle,2,".",",");
                $posY += 9;
                
                $precioUnitDetalle = number_format($precioUnitDetalle,2,".",",");
                
                imagestring($img,1,0,$posY," ACC".$rowItemsTot['id_precio_tot'],$textColor);
                imagestring($img,1,115,$posY,strtoupper(substr(" - ".$rowItemsTot['descripcion_trabajo'],0,28)),$textColor);
                imagestring($img,1,250,$posY,strtoupper(str_pad($cantidadDetalle, 12, " ", STR_PAD_BOTH)),$textColor);
                imagestring($img,1,315,$posY,strtoupper(str_pad($precioUnitDetalle, 15, " ", STR_PAD_LEFT)),$textColor);
                imagestring($img,1,395,$posY,strtoupper(str_pad($totalDetalle, 15, " ", STR_PAD_LEFT)),$textColor);
                
                
            }
        }
}

// DETALLES DE LAS NOTAS
$queryDetTipoDocNotas = sprintf("SELECT 
	sa_det_fact_notas.id_det_fact_nota AS idDetNota,
	sa_det_fact_notas.descripcion_nota,
	sa_det_fact_notas.precio
FROM cj_cc_encabezadofactura
	INNER JOIN sa_det_fact_notas ON (cj_cc_encabezadofactura.idFactura = sa_det_fact_notas.idFactura)
	INNER JOIN cj_cc_notacredito ON (cj_cc_encabezadofactura.idFactura = cj_cc_notacredito.idDocumento)
WHERE cj_cc_notacredito.idNotaCredito = %s",
	valTpDato($idDocumento,"int"));
$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas);
if (!$rsDetTipoDocNotas) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsDetTipoDocNotas = mysql_num_rows($rsDetTipoDocNotas);

if($totalRowsDetTipoDocNotas >= 1 && $totalRowsDetalleTot == 0 && $totalRowsFactDetTemp == 0 && $totalRowsOrdenDetRep == 0){//si solo es notas, descripcion larga
    
//    $cantidad = "1";
//    $total = $cantidad * $rowDetTipoDocNotas['precio'];
//
//    $posY += 10;
//    imagestring($img,1,0,$posY,"N".$rowDetTipoDocNotas['idDetNota'],$textColor);
//    imagestring($img,1,115,$posY,substr($rowDetTipoDocNotas['descripcion_nota'],0,30),$textColor);
//    imagestring($img,1,250,$posY,strtoupper(str_pad(number_format($cantidad, 2, ".", ","), 12, " ", STR_PAD_BOTH)),$textColor);
//    imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($rowDetTipoDocNotas['precio'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor); 
//    imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($total, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
    
    $posY += 10;
    while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
            $cantidad = "1";
            $precioUnit = number_format($rowDetTipoDocNotas['precio'],2,".",",");
            $total = number_format($rowDetTipoDocNotas['precio'],2,".",",");
            $posXCantidad = 290+((5*13)-(strlen($cantidad)*5))/2;
            $posXPrecio = 370+((5*15)-(strlen($precioUnit)*5));
            $posXTotal = 455+((5*15)-(strlen($total)*5));
            
            $cantidadLineas = ceil(strlen($rowDetTipoDocNotas['descripcion_nota'])/40);
            
            imagestring($img,1,0,$posY,"N".$rowDetTipoDocNotas['idDetNota'],$textColor); // <----
            $linea = 0;
            $texto = 0;
            
            $enter = 0;
            if($enter){//por cada enter realizado \r\n en mysql linux
                $lineasTexto = explode("\r\n",$rowDetTipoDocNotas['descripcion_nota']);
                $cantidadLineas = count($lineasTexto);
                foreach($lineasTexto as $textoDescripcion){
                    imagestring($img,1,110,$posY+$linea,strtoupper(substr($textoDescripcion,$texto,40)),$textColor); // <----
                    $linea += 10;
                    $texto += 40;
                }                
            }else{//por cantidad de caractres            
                for($i=1; $i<=$cantidadLineas; $i++){                
                    imagestring($img,1,110,$posY+$linea,strtoupper(substr($rowDetTipoDocNotas['descripcion_nota'],$texto,40)),$textColor); // <----
                    $linea += 10;
                    $texto += 40;
                }
            }
            $centrar = 0;
            if($cantidadLineas > 2){
                $centrar = (ceil($cantidadLineas/2)*10)-10;
            }
            //imagestring($img,1,$posXCantidad,$posY+$centrar,$cantidad,$textColor); // <----
            imagestring($img,1,$posXPrecio,$posY+$centrar,$precioUnit,$textColor); // <----
            imagestring($img,1,$posXTotal,$posY+$centrar,$total,$textColor); // <----

            $posY += (10*$cantidadLineas);
    }
    
}else{//sino imprimir comun

    while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
            $cantidad = "1";
            $total = $cantidad * $rowDetTipoDocNotas['precio'];

	$posY += 9;
            imagestring($img,1,0,$posY,"N".$rowDetTipoDocNotas['idDetNota'],$textColor);
            imagestring($img,1,115,$posY,substr($rowDetTipoDocNotas['descripcion_nota'],0,30),$textColor);
            imagestring($img,1,250,$posY,strtoupper(str_pad(number_format($cantidad, 2, ".", ","), 12, " ", STR_PAD_BOTH)),$textColor);
            imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($rowDetTipoDocNotas['precio'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor); 
            imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($total, 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
    }
}

if ($totalRowsOrdenDetRep == 0 && $totalRowsFactDetTemp == 0 && $totalRowsDetalleTot == 0 && $totalRowsDetTipoDocNotas == 0) {
	$posY += 10;
	imagestring($img,1,0,$posY,trim(substr($rowNotaCred['observacionesNotaCredito'],0,49)),$textColor); // <----
	imagestring($img,1,250,$posY,strtoupper(str_pad(number_format(1, 2, ".", ","), 12, " ", STR_PAD_BOTH)),$textColor);
	imagestring($img,1,315,$posY,strtoupper(str_pad(number_format($rowNotaCred['subtotalNotaCredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowNotaCred['subtotalNotaCredito'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	
	$posY += 10;
	imagestring($img,1,0,$posY,trim(substr($rowNotaCred['observacionesNotaCredito'],49,49)),$textColor); // <----
	$posY += 10;
	imagestring($img,1,0,$posY,trim(substr($rowNotaCred['observacionesNotaCredito'],98,49)),$textColor); // <----
	$posY += 10;
	imagestring($img,1,0,$posY,trim(substr($rowNotaCred['observacionesNotaCredito'],147,49)),$textColor); // <----
}


$posY = 462;
if ($totalRowsFactura > 0) {
	imagestring($img,1,0,$posY,"---------------------------------------------------",$textColor);
	$posY += 9;
	imagestring($img,1,0,$posY,"NOTA DE CREDITO QUE HACE REFERENCIA A",$textColor); // <----
	$posY += 9;
	imagestring($img,1,0,$posY,"FACT. NRO ".$rowFactura['numeroFactura']." NRO CONTROL ".$rowFactura['numeroControl'],$textColor); // <----
	$posY += 9;
	imagestring($img,1,0,$posY,"DE FECHA ".date("d-m-Y", strtotime($rowFactura['fechaRegistroFactura'])),$textColor); // <----
	$posY += 9;
	imagestring($img,1,0,$posY,"---------------------------------------------------",$textColor);
}


$posY = 462;
imagestring($img,1,260,$posY,"SUB-TOTAL",$textColor);
imagestring($img,1,340,$posY,":",$textColor);
imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['subtotalNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);

//$rowNotaCred['porcentaje_descuento'] = 151;
if ($rowNotaCred['porcentaje_descuento'] == "" || $rowNotaCred['porcentaje_descuento'] == 0) {
	$porcentajeDescuento = ($rowFactura['descuentoFactura'] * 100) / $rowNotaCred['subtotalNotaCredito'];
	$descuento = $rowFactura['descuentoFactura'];
} else {
	$porcentajeDescuento = $rowNotaCred['porcentaje_descuento'];
	$descuento = $rowNotaCred['subtotal_descuento'];
}


$posY += 9;
imagestring($img,1,260,$posY,"DESCUENTO",$textColor);
imagestring($img,1,340,$posY,":",$textColor);
imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($descuento, 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);

$posY += 9;
imagestring($img,1,260,$posY,"MONTO EXENTO",$textColor);
imagestring($img,1,340,$posY,":",$textColor);
imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['montoExento'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);

$queryIvas = sprintf("SELECT pg_iva.observacion,
                             cj_cc_nota_credito_iva.base_imponible,
                             cj_cc_nota_credito_iva.iva,
                             cj_cc_nota_credito_iva.subtotal_iva       
                    FROM cj_cc_nota_credito_iva
                    INNER JOIN pg_iva ON cj_cc_nota_credito_iva.id_iva = pg_iva.idIva
                    WHERE id_nota_credito = %s",
            valTpDato($idDocumento,"int"));

$rsIvas = mysql_query($queryIvas) or die(mysql_error()."<br><br>Line: ".__LINE__);

while ($rowIvas = mysql_fetch_assoc($rsIvas)){
    $posY += 9;
    
    imagestring($img,1,260,$posY,$rowIvas['observacion'],$textColor);
    imagestring($img,1,310,$posY,$rowIvas['iva'],$textColor);
    imagestring($img,1,340,$posY,":",$textColor);
    
    imagestring($img,1,350,$posY,strtoupper(str_pad(number_format($rowIvas['base_imponible'], 2, ".", ","), 8, " ", STR_PAD_LEFT)),$textColor);
    imagestring($img,1,395,$posY,strtoupper(str_pad(number_format($rowIvas['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
}

//$posY += 9;
//imagestring($img,1,260,$posY,"BASE IMPONIBLE",$textColor);
//imagestring($img,1,340,$posY,":",$textColor);
//imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['baseimponibleNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);

//$posY += 9;
//$porcentajeIva = (doubleval($rowNotaCred['ivaNotaCredito'])*100)/doubleval($rowNotaCred['baseimponibleNotaCredito']);
//imagestring($img,1,260,$posY,nombreIva($rowFactura['idIva']),$textColor);
//imagestring($img,1,340,$posY,":",$textColor);
//imagestring($img,1,345,$posY,strtoupper(str_pad(number_format($porcentajeIva, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
//imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['ivaNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);

//$posY += 9;
//imagestring($img,1,260,$posY,"MONTO NO GRAVADO",$textColor);
//imagestring($img,1,340,$posY,":",$textColor);
//imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['montoNoGravado'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);

//$posY += 9;
//$porcentajeIvaLujo = (doubleval($rowNotaCred['ivaLujoNotaCredito'])*100)/doubleval($rowNotaCred['baseimponibleNotaCredito']);
//imagestring($img,1,260,$posY,"IMPUESTO AL LUJO",$textColor);
//imagestring($img,1,340,$posY,":",$textColor);
//imagestring($img,1,345,$posY,strtoupper(str_pad(number_format($porcentajeIvaLujo, 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
//imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['ivaLujoNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);

$posY += 8;
imagestring($img,1,260,$posY,"------------------------------------------",$textColor);

                if ($rowFactura['fecha_reconversion']==null) {
                               if ($rowFactura['fechaRegistroFactura']>="2018-08-01" and $rowFactura['fechaRegistroFactura']< "2018-08-20") {
                                               $posY += 8;
                                               imagestring($img,1,260,$posY,"TOTAL Bs",$textColor);
                                               imagestring($img,1,340,$posY,":",$textColor);
                                               imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['montoNetoNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor); 
                                               $posY += 8;
                                               imagestring($img,1,260,$posY,"TOTAL Bs.S",$textColor);
                                               imagestring($img,1,340,$posY,":",$textColor);
                               imagestring($img,1,380,$posY,strtoupper(str_pad(number_format(($rowNotaCred['montoNetoNotaCredito']/100000), 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
                               }else if ($rowFactura['fechaRegistroFactura']>="2018-08-20") {
                                               $posY += 8;
                                               imagestring($img,1,260,$posY,"TOTAL Bs.S",$textColor);
                                               imagestring($img,1,340,$posY,":",$textColor);
                                             imagestring($img,1,380,$posY,strtoupper(str_pad(number_format(($rowNotaCred['montoNetoNotaCredito']), 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
                                               $posY += 8;
                                               imagestring($img,1,260,$posY,"TOTAL Bs",$textColor);
                                               imagestring($img,1,340,$posY,":",$textColor);
                               imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['montoNetoNotaCredito']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor); 
                               }else{
                                               $posY += 8;
                                               imagestring($img,1,260,$posY,"TOTAL Bs",$textColor);
                                               imagestring($img,1,340,$posY,":",$textColor);
                                               imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['montoNetoNotaCredito'], 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor); 
                               }
                }else{
                               if ($rowFactura['fechaRegistroFactura']>="2018-08-01" and $rowFactura['fechaRegistroFactura']< "2018-08-20") {
                                               $posY += 8;
                                               imagestring($img,1,260,$posY,"TOTAL Bs",$textColor);
                                               imagestring($img,1,340,$posY,":",$textColor);
                               imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['montoNetoNotaCredito']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor); 
                                               $posY += 8;
                                               imagestring($img,1,260,$posY,"TOTAL Bs.S",$textColor);
                                               imagestring($img,1,340,$posY,":",$textColor);
                                             imagestring($img,1,380,$posY,strtoupper(str_pad(number_format(($rowNotaCred['montoNetoNotaCredito']), 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
                               }else if ($rowFactura['fechaRegistroFactura']>="2018-08-20") {
                                               $posY += 8;
                                               imagestring($img,1,260,$posY,"TOTAL Bs.S",$textColor);
                                               imagestring($img,1,340,$posY,":",$textColor);
                                             imagestring($img,1,380,$posY,strtoupper(str_pad(number_format(($rowNotaCred['montoNetoNotaCredito']), 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor);
                                               $posY += 8;
                                               imagestring($img,1,260,$posY,"TOTAL Bs",$textColor);
                                               imagestring($img,1,340,$posY,":",$textColor);
                               imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['montoNetoNotaCredito']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor); 
                               }else{
                                               $posY += 8;
                                               imagestring($img,1,260,$posY,"TOTAL Bs",$textColor);
                                               imagestring($img,1,340,$posY,":",$textColor);
                               imagestring($img,1,380,$posY,strtoupper(str_pad(number_format($rowNotaCred['montoNetoNotaCredito']*100000, 2, ".", ","), 18, " ", STR_PAD_LEFT)),$textColor); 
                               }
                }
                
// <----

$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig403 = mysql_query($queryConfig403);
if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig403 = mysql_num_rows($rsConfig403);
$rowConfig403 = mysql_fetch_assoc($rsConfig403);

if($rowConfig403['valor'] == NULL){ die("No se ha configurado formato."); }

if($rowConfig403['valor'] == "3"){//puerto rico
    
    $img2 = @imagecreate(530, 40) or die("No se puede crear la imagen");
    $backgroundColor = imagecolorallocate($img2, 255, 255, 255);
    $textColor = imagecolorallocate($img2, 0, 0, 0);

    //estableciendo los colores de la paleta:
    
    $queryEmpresaInfo = sprintf("SELECT nombre_empresa, direccion, logo_familia, fax, telefono1, telefono2  FROM pg_empresa WHERE id_empresa = %s LIMIT 1",
            valTpDato($idEmpresa, "int"));
    $rsEmpresaInfo = mysql_query($queryEmpresaInfo);
    if (!$rsEmpresaInfo) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
    
    $rowEmpresaInfo = mysql_fetch_assoc($rsEmpresaInfo);
    
    $posY = 0;
    imagestring($img2,1,70,$posY,$rowEmpresaInfo["nombre_empresa"],$textColor);

    $direccion = explode("\n",$rowEmpresaInfo["direccion"]);
    $posY += 9;
    imagestring($img2,1,70,$posY,strtoupper(trim($direccion[0])),$textColor);
    $posY += 9;
    imagestring($img2,1,70,$posY,strtoupper(trim($direccion[1])),$textColor);

    if($rowEmpresaInfo["fax"] != ""){
        $fax = " FAX ".$rowEmpresaInfo["fax"];
    }
    $posY += 9;
    imagestring($img2,1,70,$posY,"Tel.: ".$rowEmpresaInfo["telefono1"]." ".$rowEmpresaInfo["telefono2"].$fax,$textColor);
    $posY += 9;  	 
    
    $rutaLogo = "../../".$rowEmpresaInfo["logo_familia"];
    imagepng($img2,"tmp/devolucion_venta_encabezado.png");
}


$arrayImg[] = "tmp/"."devolucion_venta_servicios".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig2 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10
	AND config_emp.status = 1
	AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig2 = mysql_query($queryConfig2, $conex);
if (!$rsConfig2) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig2 = mysql_num_rows($rsConfig2);
$rowConfig2 = mysql_fetch_assoc($rsConfig2);

if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $valor) {
		$pdf->AddPage();
		
		$pdf->Image($valor, 15, $rowConfig2['valor'], 580, 680);
                
                if($rowConfig403['valor'] == "3"){                    
                    $pdf->Image("tmp/devolucion_venta_encabezado.png", 55, 25, 530+60, 40+15);
                    $pdf->Image($rutaLogo,15,25,80);
                }
                
	}
}

$pdf->SetDisplayMode(80);
//$pdf->AutoPrint(true);
$pdf->Output();


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

function dividirObservacion($img, $texto, $posY, $textColor){
    $array[] = substr($texto,0,28);
    $array[] = substr($texto,28,28);
    $array[] = substr($texto,56,28);
    $array[] = substr($texto,84,28);
    $array[] = substr($texto,112,28);
    $array[] = substr($texto,140,28);
    $array[] = substr($texto,168,28);
    $array[] = substr($texto,196,28);
    $array[] = substr($texto,224,28);
    
    $adicional = 0;
    
    foreach($array as $texto){
        if($texto != NULL){
            imagestring($img,1,110,$posY,strtoupper($texto),$textColor); // <----  
            $posY += 9;
            $adicional++;
        }
    }
    return $adicional;
    
}

?>