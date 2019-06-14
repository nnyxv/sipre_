<?php
require_once ("../../connections/conex.php");

/**************************** ARCHIVO PDF ****************************/
require('../../clases/fpdf/fpdf.php');

$pdf = new FPDF('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");
/**************************** ARCHIVO PDF ****************************/

$idDocumento = $_GET['valBusq'];

// BUSCA LOS DATOS DE LA FACTURA
$query = sprintf("SELECT
	fact_vent.id_empresa,
	numeroPedido,
	orden.numero_orden,
	orden.tiempo_orden,
	orden.tiempo_finalizado,
	orden.tiempo_entrega,
	nombre_tipo_orden,
	sa_filtro_orden.id_filtro_orden,
	sa_filtro_orden.tot_accesorio,
	numeroFactura,
	numeroControl,
	fechaRegistroFactura,
	fechaVencimientoFactura,
	diasDeCredito,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
	id,
	cliente.direccion AS direccion_cliente,
	telf,
	lci,
	ci,
	chasis,
	nom_marca,
	recepcion.kilometraje AS kilometraje_vale_recepcion,
	recepcion.kilometraje_salida AS kilometraje_vale_recepcion_salida,
	placa,
	nom_modelo,
	nom_uni_bas,
	observacionFactura,
	subtotalFactura,
	descuentoFactura,
	baseImponible,
	idIva,
	porcentajeIvaFactura,
	calculoIvaFactura,
	0 AS montoNoGravado,
	porcentajeIvaDeLujoFactura,
	calculoIvaDeLujoFactura,
	montoTotalFactura,
	fact_vent.montoExento,
	B.fecha_reconversion
FROM cj_cc_cliente cliente
	INNER JOIN cj_cc_encabezadofactura fact_vent ON (cliente.id = fact_vent.idCliente)
	left JOIN cj_cc_factura_reconversion B on (fact_vent.idFactura=B.id_factura)
	INNER JOIN pg_empleado empleado ON (fact_vent.idVendedor = empleado.id_empleado)
	LEFT OUTER JOIN sa_orden orden ON (fact_vent.numeroPedido = orden.id_orden)
	LEFT OUTER JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden)
	LEFT OUTER JOIN sa_filtro_orden ON (tipo_orden.id_filtro_orden = sa_filtro_orden.id_filtro_orden)
	LEFT OUTER JOIN sa_recepcion recepcion ON (orden.id_recepcion = recepcion.id_recepcion)
	LEFT OUTER JOIN sa_cita cita ON (recepcion.id_cita = cita.id_cita)
	LEFT OUTER JOIN en_registro_placas ON (cita.id_registro_placas = en_registro_placas.id_registro_placas)
	LEFT OUTER JOIN an_uni_bas uni_bas ON (en_registro_placas.id_unidad_basica = uni_bas.id_uni_bas)
	LEFT OUTER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
	LEFT OUTER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
WHERE fact_vent.idFactura = %s;",
	valTpDato($idDocumento,"int"));
$rs = mysql_query($query, $conex);
if (!$rs) die(mysql_error()."<br><br>Line: ".__LINE__);
$row = mysql_fetch_assoc($rs);

$idEmpresa = $row['id_empresa'];

// VERIFICA VALORES DE CONFIGURACION (Mostrar Numero Control en Impresion de Factura)
$queryConfig101 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 101 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig101 = mysql_query($queryConfig101, $conex);
if (!$rsConfig101) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig101 = mysql_num_rows($rsConfig101);
$rowConfig101 = mysql_fetch_assoc($rsConfig101);

// VERIFICA VALORES DE CONFIGURACION (Mostrar Dcto. IdentificaciÃ³n (C.I. / R.I.F. / R.U.C. / LIC / SSN) en Documentos Fiscales.)
$queryConfig409 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 409 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig409 = mysql_query($queryConfig409);
if (!$rsConfig409) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig409 = mysql_num_rows($rsConfig409);
$rowConfig409 = mysql_fetch_assoc($rsConfig409);

$img = @imagecreate(530, 631) or die("No se puede crear la imagen"); //631

//estableciendo los colores de la paleta:
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

imagestring($img,1,350,10,str_pad("FACTURA SERIE - SR", 36, " ", STR_PAD_BOTH),$textColor);

imagestring($img,1,350,30,"ORDEN DE TRABAJO",$textColor);
imagestring($img,1,440,30,":",$textColor);
imagestring($img,1,450,30,$row['numero_orden']." ".$row['nombre_tipo_orden'],$textColor);

imagestring($img,1,350,40,"FACTURA NRO.",$textColor);
imagestring($img,1,440,40,":",$textColor);
imagestring($img,2,450,37,$row['numeroFactura'],$textColor); // <----

$posY = 50;
if ($rowConfig101['valor'] == 1) {	
	imagestring($img,1,350,$posY,utf8_decode("NRO. CONTROL"),$textColor);
	imagestring($img,1,440,$posY,": ".$row['numeroControl'],$textColor);
	$posY += 10;
}

imagestring($img,1,350,$posY,"FECHA EMISION",$textColor);
imagestring($img,1,440,$posY,":",$textColor);
imagestring($img,1,450,$posY,date(spanDateFormat, strtotime($row['fechaRegistroFactura'])),$textColor); // <----
$posY += 10;
imagestring($img,1,350,$posY,"FECHA VENCIMIENTO",$textColor);
imagestring($img,1,440,$posY,":",$textColor);
imagestring($img,1,450,$posY,date(spanDateFormat, strtotime($row['fechaVencimientoFactura'])),$textColor); // <----
$posY += 10;
imagestring($img,1,450,$posY,"CRED. ".number_format($row['diasDeCredito'])." DIAS",$textColor); // <----

$posY += 10;
imagestring($img,1,350,$posY,"ASESOR",$textColor);
imagestring($img,1,440,$posY,":",$textColor);
imagestring($img,1,450,$posY,strtoupper($row['nombre_empleado']),$textColor); // <----


imagestring($img,1,5,40,strtoupper($row['nombre_cliente']),$textColor); // <----
imagestring($img,1,210,30,"CODIGO",$textColor);
imagestring($img,1,240,30,":",$textColor);
imagestring($img,1,250,30,$row['id'],$textColor); // <----

$direccionCliente = str_replace(";", " ", $row['direccion_cliente']);

imagestring($img,1,5,50,trim(substr($direccionCliente,0,50)),$textColor); // <----

imagestring($img,1,5,60,trim(substr($direccionCliente,50,35)),$textColor); // <----
imagestring($img,1,200,60,"TELEF.",$textColor);
imagestring($img,1,230,60,":",$textColor);
imagestring($img,1,240,60,$row['telf'],$textColor); // <----

imagestring($img,1,5,70,trim(substr($direccionCliente,85,40)),$textColor); // <----
if($rowConfig409['valor'] == 1){
	imagestring($img,1,175,70,$spanCI."/".$spanRIF,$textColor);
	imagestring($img,1,230,70,":",$textColor);
	imagestring($img,1,240,70,$row['lci']."-".$row['ci'],$textColor); // <----
}

$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig403 = mysql_query($queryConfig403);
if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$totalRowsConfig403 = mysql_num_rows($rsConfig403);
$rowConfig403 = mysql_fetch_assoc($rsConfig403);

if($rowConfig403['valor'] == NULL){ die("No se ha configurado formato."); }

if($rowConfig403['valor'] == "3") {//puerto rico
	imagestring($img, 1, 5, 80, "FECHA APERTURA   :" . fechaTiempo($row['tiempo_orden']), $textColor);
	imagestring($img, 1, 5, 88, "FECHA FINALIZADO :" . fechaTiempo($row['tiempo_finalizado']), $textColor);
	imagestring($img, 1, 5, 95, "FECHA ENTREGADO  :" . fechaTiempo($row['tiempo_entrega']), $textColor);
	
	/* MECANICO DEL TEMPARIO */
	$queryMecanico = sprintf("SELECT
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		sa_mecanicos.id_mecanico,
		sa_mecanicos.licencia
	FROM sa_det_fact_tempario
		INNER JOIN sa_tempario ON (sa_det_fact_tempario.id_tempario = sa_tempario.id_tempario)
		INNER JOIN sa_mecanicos ON (sa_det_fact_tempario.id_mecanico = sa_mecanicos.id_mecanico)
		INNER JOIN pg_empleado empleado ON (sa_mecanicos.id_empleado = empleado.id_empleado)
	WHERE sa_det_fact_tempario.idFactura = %s",
		valTpDato($idDocumento,"int"));
	$rsMecanico = mysql_query($queryMecanico, $conex) or die(mysql_error()."<br><br>Line: ".__LINE__);
	$rowMecanico = mysql_fetch_assoc($rsMecanico);
	imagestring($img, 1, 215, 95, strtoupper("MECANICO: LIC#:".$rowMecanico['licencia']." ".$rowMecanico['nombre_empleado']), $textColor);
}

imagestring($img,1,0,100,"----------------------------------------------------------------------------------------------------------",$textColor);
if($rowConfig403['valor'] == "3") {//puerto rico
	imagestring($img,1,40,110,"CODIGO",$textColor);
	imagestring($img,1,170,110,"DESCRIPCION",$textColor);
	imagestring($img,1,290,110,"CANTIDAD",$textColor);
	imagestring($img,1,340,110,"PRECIO LISTA",$textColor);
	imagestring($img,1,410,110,"PRECIO UNIT.",$textColor);
	imagestring($img,1,490,110,"TOTAL",$textColor);
} else {
	imagestring($img,1,40,110,"CODIGO",$textColor); // <----
	imagestring($img,1,170,110,"DESCRIPCION",$textColor); // <----
	imagestring($img,1,280,110,"CANTIDAD/MECANICO",$textColor); // <----
	imagestring($img,1,380,110,"PRECIO UNIT.",$textColor); // <----
	imagestring($img,1,480,110,"TOTAL",$textColor); // <----
}
imagestring($img,1,0,120,"----------------------------------------------------------------------------------------------------------",$textColor);

/* DETALLES DE LOS REPUESTOS */
$queryRepuestosGenerales = sprintf("SELECT
	iv_subsecciones.id_subseccion,
	iv_articulos.codigo_articulo,
	sa_det_fact_articulo.cantidad,
	sa_det_fact_articulo.precio_unitario,
	sa_det_fact_articulo.precio_sugerido,
	sa_det_fact_articulo.pmu_unitario,
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
	valTpDato($idDocumento,"int"));
$rsOrdenDetRep = mysql_query($queryRepuestosGenerales, $conex) or die(mysql_error()."<br><br>Line: ".__LINE__);

$totalRowsOrdenDetRep = mysql_num_rows($rsOrdenDetRep);

$posY = 130;
while ($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)) {
	
	if($rowConfig403['valor'] == "3") {//puerto rico
		$precioLista = number_format($rowOrdenDetRep['precio_sugerido'],2,".",",");
		imagestring($img,1,340,$posY,str_pad($precioLista,10," ",STR_PAD_LEFT),$textColor);
	}
	
	$cantidad = $rowOrdenDetRep['cantidad'];
	$precioUnit = number_format($rowOrdenDetRep['precio_unitario'] + $rowOrdenDetRep['pmu_unitario'],2,".",",");
	$total = number_format($rowOrdenDetRep['cantidad']*($rowOrdenDetRep['precio_unitario']+$rowOrdenDetRep['pmu_unitario']),2,".",",");
	
	if($rowConfig403['valor'] == "3") {//puerto rico
		$posXCantidad = 280+((5*13)-(strlen($cantidad)*5))/2;
	}else{
		$posXCantidad = 290+((5*13)-(strlen($cantidad)*5))/2;	
	}
	
	$posXPrecio = 370+((5*15)-(strlen($precioUnit)*5));
	$posXTotal = 455+((5*15)-(strlen($total)*5));

	imagestring($img,1,0,$posY,elimCaracter($rowOrdenDetRep['codigo_articulo'],";"),$textColor); // <----
	imagestring($img,1,110,$posY,strtoupper(substr($rowOrdenDetRep['descripcion_articulo'],0,33)),$textColor); // <----
	imagestring($img,1,$posXCantidad,$posY,$cantidad,$textColor); // <----
	imagestring($img,1,$posXPrecio,$posY,$precioUnit,$textColor); // <----
	imagestring($img,1,$posXTotal,$posY,$total,$textColor); // <----

	$posY += 10;
}

/* DETALLES DE LOS TEMPARIOS */
$queryFactDetTemp = sprintf("SELECT
	sa_modo.descripcion_modo,
	sa_tempario.codigo_tempario,
	sa_tempario.descripcion_tempario,
	sa_det_fact_tempario.operador,
	sa_det_fact_tempario.id_tempario,
	sa_det_fact_tempario.precio,
	sa_det_fact_tempario.base_ut_precio,
	sa_det_fact_tempario.id_modo,
	(case sa_det_fact_tempario.id_modo
		when '1' then
			ROUND(sa_det_fact_tempario.ut * sa_det_fact_tempario.precio_tempario_tipo_orden / sa_det_fact_tempario.base_ut_precio,2)
		when '2' then
			sa_det_fact_tempario.precio
		when '3' then
			sa_det_fact_tempario.costo
		when '4' then
			'4'
	end) AS total_por_tipo_orden,
	(case sa_det_fact_tempario.id_modo
		when '1' then
			sa_det_fact_tempario.ut
		when '2' then
			sa_det_fact_tempario.precio
		when '3' then
			sa_det_fact_tempario.costo
		when '4' then
			'4'
	end) AS precio_por_tipo_orden,
	sa_det_fact_tempario.id_det_fact_tempario,
	empleado.cedula,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	empleado.apellido,
	empleado.id_empleado,
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
	INNER JOIN pg_empleado empleado ON (sa_mecanicos.id_empleado = empleado.id_empleado)
	LEFT OUTER JOIN sa_paquetes ON (sa_det_fact_tempario.id_paquete = sa_paquetes.id_paquete)
WHERE sa_det_fact_tempario.idFactura = %s
ORDER BY sa_det_fact_tempario.id_paquete",
	valTpDato($idDocumento,"int"));
$rsFactDetTemp = mysql_query($queryFactDetTemp, $conex) or die(mysql_error()."<br><br>Line: ".__LINE__);
$totalRowsFactDetTemp = mysql_num_rows($rsFactDetTemp);

while ($rowFactDetTemp = mysql_fetch_assoc($rsFactDetTemp)) {
	$caractCantTempario = ($rowFactDetTemp['id_modo'] == 1) ? number_format($rowFactDetTemp['precio_por_tipo_orden']/100,2,".",",") : number_format(1,2,".",",");//Es entre 100 o la base ut? : $rowFactDetTemp['precio_por_tipo_orden']/100

	$caracterPrecioTempario = ($rowFactDetTemp['id_modo'] == 1) ? $rowFactDetTemp['precio_tempario_tipo_orden'] : $rowFactDetTemp['precio_por_tipo_orden'];

	$cantidad = $caractCantTempario."/MEC:".sprintf("%04s",$rowFactDetTemp['id_mecanico']);
	$precioUnit = number_format($caracterPrecioTempario,2,".",",");
	$total = number_format($rowFactDetTemp['total_por_tipo_orden'],2,".",",");
	$posXCantidad = 290+((5*13)-(strlen($cantidad)*5))/2;
	$posXPrecio = 370+((5*15)-(strlen($precioUnit)*5));
	$posXTotal = 455+((5*15)-(strlen($total)*5));

	imagestring($img,1,0,$posY,$rowFactDetTemp['codigo_tempario'],$textColor); // <----
	imagestring($img,1,110,$posY,strtoupper(substr($rowFactDetTemp['descripcion_tempario'],0,33)),$textColor); // <----
	imagestring($img,1,$posXCantidad,$posY,$cantidad,$textColor); // <----
	imagestring($img,1,$posXPrecio,$posY,$precioUnit,$textColor); // <----
	imagestring($img,1,$posXTotal,$posY,$total,$textColor); // <----

	$posY += 10;
}

/* DETALLE DE LOS TOT */
$queryDetalleTot = sprintf("SELECT *, cp_factura.observacion_factura FROM sa_orden_tot
	INNER JOIN cp_proveedor ON (sa_orden_tot.id_proveedor = cp_proveedor.id_proveedor)
	INNER JOIN sa_det_fact_tot ON (sa_orden_tot.id_orden_tot = sa_det_fact_tot.id_orden_tot)
        INNER JOIN cp_factura ON sa_orden_tot.id_factura = cp_factura.id_factura
WHERE sa_det_fact_tot.idFactura = %s AND sa_orden_tot.monto_subtotal > 0",
	valTpDato($idDocumento,"int"));
$rsDetalleTot = mysql_query($queryDetalleTot) or die(mysql_error()."<br><br>Line: ".__LINE__);
$totalRowsDetalleTot = mysql_num_rows($rsDetalleTot);

while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
	$cantidad = "1";
	$precioUnit = number_format($rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100),2,".",",");
	$total = number_format($rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100),2,".",",");
	$posXCantidad = 290+((5*13)-(strlen($cantidad)*5))/2;
	$posXPrecio = 370+((5*15)-(strlen($precioUnit)*5));
	$posXTotal = 455+((5*15)-(strlen($total)*5));

	imagestring($img,1,0,$posY,"TOT".$rowDetalleTot['numero_tot'],$textColor); // <----
        
	$adicionalY = 1;
	if(strlen($rowDetalleTot['observacion_factura']) > 33 && $row["tot_accesorio"] == 1){
		$adicionalY = dividirObservacion($img, $rowDetalleTot['observacion_factura'], $posY, $textColor);
	}else{
		imagestring($img,1,110,$posY,strtoupper(substr($rowDetalleTot['observacion_factura'],0,33)),$textColor); // <----
	}
	
	if($row["tot_accesorio"] == 1){
		
	}else{
		imagestring($img,1,$posXCantidad,$posY,$cantidad,$textColor); // <----
		imagestring($img,1,$posXPrecio,$posY,$precioUnit,$textColor); // <----
		imagestring($img,1,$posXTotal,$posY,$total,$textColor); // <----
	}

	$posY += 10*$adicionalY;
	
	if($row["tot_accesorio"] == 1){
		$queryItemsTot = sprintf("SELECT descripcion_trabajo, monto, cantidad, id_precio_tot FROM sa_orden_tot_detalle WHERE id_orden_tot = %s",
									$rowDetalleTot['id_orden_tot']);
		$rsItemsTot = mysql_query($queryItemsTot) or die(mysql_error()."<br><br>Line: ".__LINE__);
		
//            $cantidadAcc = mysql_num_rows($rsItemsTot);
				
		while($rowItemsTot = mysql_fetch_assoc($rsItemsTot)){
//                if($cantidadAcc == 1 && $rowItemsTot['id_precio_tot'] == 1){
//                    break;
//                }
			
			$cantidadDetalle = $rowItemsTot['cantidad'];
			$precioUnitDetalle = $rowItemsTot['monto']+(($rowItemsTot['monto']*$rowDetalleTot['porcentaje_tot']/100));
			
			if($cantidadDetalle == NULL || $cantidadDetalle == "" || $cantidadDetalle == 0 ){
			   $cantidadDetalle = 1;
			}
			
			$totalDetalle = number_format($cantidadDetalle*$precioUnitDetalle,2,".",",");
			
			$precioUnitDetalle = number_format($precioUnitDetalle,2,".",",");
			
			$posXCantidad = 290+((5*13)-(strlen($cantidadDetalle)*5))/2;
			$posXPrecio = 370+((5*15)-(strlen($precioUnitDetalle)*5));
			$posXTotal = 455+((5*15)-(strlen($totalDetalle)*5));

			imagestring($img,1,0,$posY," ACC".$rowItemsTot['id_precio_tot'],$textColor);
			imagestring($img,1,110,$posY,strtoupper(substr(" - ".$rowItemsTot['descripcion_trabajo'],0,33)),$textColor);
			imagestring($img,1,$posXCantidad,$posY,$cantidadDetalle,$textColor);
			imagestring($img,1,$posXPrecio,$posY,$precioUnitDetalle,$textColor);
			imagestring($img,1,$posXTotal,$posY,$totalDetalle,$textColor);
			$posY += 10;
		}
	}
        
}

/* DETALLES DE LAS NOTAS */
$queryDetTipoDocNotas = sprintf("SELECT id_det_fact_nota AS idDetNota, descripcion_nota, precio
FROM sa_det_fact_notas
WHERE idFactura = %s",
	valTpDato($idDocumento,"int"));
$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas) or die(mysql_error()."<br><br>Line: ".__LINE__);

$totalRowsDetTipoDocNotas = mysql_num_rows($rsDetTipoDocNotas);

if($totalRowsDetTipoDocNotas >= 1 && $totalRowsDetalleTot == 0 && $totalRowsFactDetTemp == 0 && $totalRowsOrdenDetRep == 0){//si solo es notas, descripcion larga
    
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
		$precioUnit = number_format($rowDetTipoDocNotas['precio'],2,".",",");
		$total = number_format($rowDetTipoDocNotas['precio'],2,".",",");
		$posXCantidad = 290+((5*13)-(strlen($cantidad)*5))/2;
		$posXPrecio = 370+((5*15)-(strlen($precioUnit)*5));
		$posXTotal = 455+((5*15)-(strlen($total)*5));

		imagestring($img,1,0,$posY,"N".$rowDetTipoDocNotas['idDetNota'],$textColor); // <----
		imagestring($img,1,110,$posY,  strtoupper(substr($rowDetTipoDocNotas['descripcion_nota'],0,40)),$textColor); // <----
		imagestring($img,1,$posXCantidad,$posY,$cantidad,$textColor); // <----
		imagestring($img,1,$posXPrecio,$posY,$precioUnit,$textColor); // <----
		imagestring($img,1,$posXTotal,$posY,$total,$textColor); // <----

		$posY += 10;
    }

}

if($row["numeroPedido"] == ""){//sin pedido, es por cxc
	$cantidad = 1;
	$precioUnit = number_format($row["subtotalFactura"],2,".",",");
	$total = number_format($row['subtotalFactura'],2,".",",");
	$posXCantidad = 290+((5*13)-(strlen($cantidad)*5))/2;
	$posXPrecio = 370+((5*15)-(strlen($precioUnit)*5));
	$posXTotal = 455+((5*15)-(strlen($total)*5));
	
	$arrayObservacion = str_split($row['observacionFactura'], 55);
	//imagestring($img,1,0,$posY,substr(strtoupper($row['observacionFactura']),0,55),$textColor);
	imagestring($img,1,$posXCantidad,$posY,$cantidad,$textColor);
	imagestring($img,1,$posXPrecio,$posY,$precioUnit,$textColor);
	imagestring($img,1,$posXTotal,$posY,$total,$textColor);

	foreach($arrayObservacion as $observacion){
		imagestring($img,1,0,$posY,strtoupper(trim($observacion)),$textColor);
		$posY += 10;
	}
}


//$ultimoItem = $posY;

$posY = 530;
//$posY = $ultimoItem+20;
imagestring($img,1,0,$posY,"--------------------------------------------------------------",$textColor);

$posY += 10;
imagestring($img,1,0,$posY,substr(strtoupper($spanSerialCarroceria),0,6),$textColor);
imagestring($img,1,30,$posY,": ".$row['chasis'],$textColor);

imagestring($img,1,130,$posY,"MARCA",$textColor);
imagestring($img,1,160,$posY,": ".$row['nom_marca'],$textColor);

if($rowConfig403['valor'] == "3") {//puerto rico
	imagestring($img,1,230,$posY,substr(strtoupper($spanKilometraje),0,8)." E",$textColor);
	imagestring($img,1,275,$posY,":".$row['kilometraje_vale_recepcion'],$textColor);
}else{	
	imagestring($img,1,230,$posY,substr(strtoupper($spanKilometraje),0,8),$textColor);
	imagestring($img,1,270,$posY,": ".$row['kilometraje_vale_recepcion'],$textColor);
}

$posY += 10;
imagestring($img,1,0,$posY,substr(strtoupper($spanPlaca),0,6),$textColor);
imagestring($img,1,30,$posY,": ".$row['placa'],$textColor);

imagestring($img,1,130,$posY,"MODELO",$textColor);
imagestring($img,1,160,$posY,": ".$row['nom_modelo'],$textColor);

if($rowConfig403['valor'] == "3") {//puerto rico
	imagestring($img,1,230,$posY,substr(strtoupper($spanKilometraje),0,8)." S",$textColor);
	imagestring($img,1,275,$posY,":".$row['kilometraje_vale_recepcion_salida'],$textColor);
}else{
	imagestring($img,1,230,$posY,"CATALOGO",$textColor);
	imagestring($img,1,270,$posY,": ".substr($row['nom_uni_bas'],0,6),$textColor);
}

$posY += 10;
imagestring($img,1,0,$posY,"--------------------------------------------------------------",$textColor);


$posY = 530;
//$posY = $ultimoItem+20;
imagestring($img,1,315,$posY,"SUB-TOTAL",$textColor);
imagestring($img,1,400,$posY,":",$textColor);
imagestring($img,1,455,$posY,strtoupper(str_pad(number_format($row['subtotalFactura'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

$posY += 10;
imagestring($img,1,315,$posY,"DESCUENTO",$textColor);
imagestring($img,1,400,$posY,":",$textColor);
imagestring($img,1,455,$posY,strtoupper(str_pad(number_format($row['descuentoFactura'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);


$posY += 10;
if($rowConfig403['valor'] == "3" && $row['id_filtro_orden'] == 3){// puerto rico y garantia sin exento
	imagestring($img,1,315,$posY,"MONTO",$textColor);
} else { 
	imagestring($img,1,315,$posY,"MONTO EXENTO",$textColor);
}
imagestring($img,1,400,$posY,":",$textColor);
imagestring($img,1,455,$posY,strtoupper(str_pad(number_format($row['montoExento'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

$queryIvas = sprintf("SELECT 
	pg_iva.observacion,
	cj_cc_factura_iva.id_iva,
	cj_cc_factura_iva.base_imponible,
	cj_cc_factura_iva.iva,
	cj_cc_factura_iva.subtotal_iva       
FROM cj_cc_factura_iva
	INNER JOIN pg_iva ON cj_cc_factura_iva.id_iva = pg_iva.idIva
WHERE id_factura = %s",
	valTpDato($idDocumento,"int"));
$rsIvas = mysql_query($queryIvas) or die(mysql_error()."<br><br>Line: ".__LINE__);

while ($rowIvas = mysql_fetch_assoc($rsIvas)){
    $posY += 10;
	
	// TODOS UNA LINEA
    //imagestring($img,1,315,$posY,$rowIvas['observacion'],$textColor);
    //imagestring($img,1,375,$posY,str_pad(number_format($rowIvas['iva'], 2, ".", ","), 5, " ", STR_PAD_LEFT),$textColor);
    //imagestring($img,1,400,$posY,":",$textColor);
    
    //imagestring($img,1,410,$posY,strtoupper(str_pad(number_format($rowIvas['base_imponible'], 2, ".", ","), 8, " ", STR_PAD_LEFT)),$textColor);
    //imagestring($img,1,455,$posY,strtoupper(str_pad(number_format($rowIvas['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	
	// BASE IMPONIBLE SEPARADA POR LINEA    
    imagestring($img,1,315,$posY,'BASE IMPONIBLE',$textColor);
    imagestring($img,1,400,$posY,":",$textColor);    
	
    imagestring($img,1,400,$posY,strtoupper(str_pad(number_format($rowIvas['iva'], 2, ".", ","), 8, " ", STR_PAD_LEFT))."%",$textColor);	
    imagestring($img,1,455,$posY,strtoupper(str_pad(number_format($rowIvas['base_imponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	
	$posY += 10;
	imagestring($img,1,315,$posY,$rowIvas['observacion'],$textColor);
	imagestring($img,1,400,$posY,":",$textColor);
    imagestring($img,1,455,$posY,strtoupper(str_pad(number_format($rowIvas['subtotal_iva'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
	
	//coletilla si es impuesto reducido
	if ($rowConfig403['valor'] == "1" && in_array($rowIvas['id_iva'], array(18,20))) {// 1 = VENEZUELA 18,20 IVAS REDUCIDO
		$coletilla = "Se aplica rebaja de la alicuota impositiva general del IVA segun Gaceta Oficial Nro. 41.239 de fecha 19-09-2017, Decreto Nro. 3.085";
	}
}


//$posY += 10;//si se pasa el catalogo de linea
//$posY += 10;
//imagestring($img,1,315,$posY,"BASE IMPONIBLE",$textColor);
//imagestring($img,1,400,$posY,":",$textColor);
//imagestring($img,1,455,$posY,strtoupper(str_pad(number_format($row['baseImponible'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

//$posY += 10;//si la factura lleva observacion y se pasa
//$posY += 10;
//imagestring($img,1,315,$posY,$row['idIva'],$textColor);
//imagestring($img,1,400,$posY,":",$textColor);
//imagestring($img,1,410,$posY,strtoupper(str_pad(number_format($row['porcentajeIvaFactura'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
//imagestring($img,1,455,$posY,strtoupper(str_pad(number_format($row['calculoIvaFactura'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);

if($row['montoNoGravado'] != NULL && $row['montoNoGravado'] != 0){
    $posY += 10;
    imagestring($img,1,315,$posY,"MONTO NO GRAVADO",$textColor);
    imagestring($img,1,400,$posY,":",$textColor);
    imagestring($img,1,455,$posY,strtoupper(str_pad(number_format($row['montoNoGravado'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
}

if($row['porcentajeIvaDeLujoFactura'] != NULL && $row['porcentajeIvaDeLujoFactura'] != 0){
    $posY += 10;
    imagestring($img,1,315,$posY,"IMPUESTO AL LUJO",$textColor);
    imagestring($img,1,400,$posY,":",$textColor);
    imagestring($img,1,410,$posY,strtoupper(str_pad(number_format($row['porcentajeIvaDeLujoFactura'], 2, ".", ",")."%", 8, " ", STR_PAD_LEFT)),$textColor);
    imagestring($img,1,455,$posY,strtoupper(str_pad(number_format($row['calculoIvaDeLujoFactura'], 2, ".", ","), 15, " ", STR_PAD_LEFT)),$textColor);
}


			$fechaRegistroFactura = date_create_from_format('Y-m-d',$row['fechaRegistroFactura']);
			$fechaReconversion = date_create_from_format('Y-m-d','2018-08-01');

			if ($row['fecha_reconversion']== null) {
				if ($row['fechaRegistroFactura']>='2018-08-01' and $row['fechaRegistroFactura']<'2018-08-20') {
					$posY += 10;
					imagestring($img,1,315,$posY,"TOTAL",$textColor);
					imagestring($img,1,400,$posY,":",$textColor);
					imagestring($img,2,410,$posY,strtoupper(str_pad(number_format($row['montoTotalFactura'], 2, ".", ","), 20, " ", STR_PAD_LEFT)),$textColor);
					$posY += 10;
					imagestring($img,1,315,$posY,"TOTAL BS.S",$textColor);
					imagestring($img,1,400,$posY,":",$textColor);
					imagestring($img,2,410,$posY,strtoupper(str_pad(number_format(($row['montoTotalFactura']/100000), 2, ".", ","), 20, " ", STR_PAD_LEFT)),$textColor);
				}else if ($row['fechaRegistroFactura']>='2018-08-20') {
			
					$posY += 10;
					imagestring($img,1,315,$posY,"TOTAL BS.S",$textColor);
					imagestring($img,1,400,$posY,":",$textColor);
					imagestring($img,2,410,$posY,strtoupper(str_pad(number_format(($row['montoTotalFactura']), 2, ".", ","), 20, " ", STR_PAD_LEFT)),$textColor);
					$posY += 10;
					imagestring($img,1,315,$posY,"TOTAL",$textColor);
					imagestring($img,1,400,$posY,":",$textColor);
					imagestring($img,2,410,$posY,strtoupper(str_pad(number_format($row['montoTotalFactura']*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT)),$textColor);
				}else{
					$posY += 10;
					imagestring($img,1,315,$posY,"TOTAL",$textColor);
					imagestring($img,1,400,$posY,":",$textColor);
					imagestring($img,2,410,$posY,strtoupper(str_pad(number_format($row['montoTotalFactura'], 2, ".", ","), 20, " ", STR_PAD_LEFT)),$textColor);
				}
			}else{

					if ($row['fechaRegistroFactura']>='2018-08-01' and $row['fechaRegistroFactura']<'2018-08-20') {
					$posY += 10;
					imagestring($img,1,315,$posY,"TOTAL",$textColor);
					imagestring($img,1,400,$posY,":",$textColor);
					imagestring($img,2,410,$posY,strtoupper(str_pad(number_format($row['montoTotalFactura']*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT)),$textColor);
					$posY += 10;
					imagestring($img,1,315,$posY,"TOTAL BS.S",$textColor);
					imagestring($img,1,400,$posY,":",$textColor);
					imagestring($img,2,410,$posY,strtoupper(str_pad(number_format(($row['montoTotalFactura']), 2, ".", ","), 20, " ", STR_PAD_LEFT)),$textColor);
				}else if ($row['fechaRegistroFactura']>='2018-08-20') {
			
					$posY += 10;
					imagestring($img,1,315,$posY,"TOTAL BS.S",$textColor);
					imagestring($img,1,400,$posY,":",$textColor);
					imagestring($img,2,410,$posY,strtoupper(str_pad(number_format(($row['montoTotalFactura']), 2, ".", ","), 20, " ", STR_PAD_LEFT)),$textColor);
					$posY += 10;
					imagestring($img,1,315,$posY,"TOTAL",$textColor);
					imagestring($img,1,400,$posY,":",$textColor);
					imagestring($img,2,410,$posY,strtoupper(str_pad(number_format($row['montoTotalFactura']*100000, 2, ".", ","), 20, " ", STR_PAD_LEFT)),$textColor);
				}else{
					$posY += 10;
					imagestring($img,1,315,$posY,"TOTAL BS.S",$textColor);
					imagestring($img,1,400,$posY,":",$textColor);
					imagestring($img,2,410,$posY,strtoupper(str_pad(number_format($row['montoTotalFactura'], 2, ".", ","), 20, " ", STR_PAD_LEFT)),$textColor);
				}
			}


if ($coletilla) {
	$posY += 13;
	$arrayColetilla = explode("|", wordwrap($coletilla, 52, "|"));
	
	foreach ($arrayColetilla as $coletillaLinea) {
		imagestring($img,1,270,$posY,strtoupper($coletillaLinea),$textColor);
		$posY += 10;
	}	
}

// VERIFICA VALORES DE CONFIGURACION (Margen Superior para Documentos de Impresion de Repuestos)
$queryConfig2 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 10
	AND config_emp.status = 1
	AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa,"int"));
$rsConfig2 = mysql_query($queryConfig2, $conex);
if (!$rsConfig2) die(mysql_error()."<br><br>Line: ".__LINE__);
$totalRowsConfig2 = mysql_num_rows($rsConfig2);
$rowConfig2 = mysql_fetch_assoc($rsConfig2);



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
    $rutaEncabezado = "tmp/factura_venta_encabezado.png";
    imagepng($img2,$rutaEncabezado);
}

$arrayImg[] = "tmp/factura_venta".$pageNum.".png";
$r = imagepng($img,$arrayImg[count($arrayImg)-1]);


if (isset($arrayImg)) {
	foreach ($arrayImg as $indice => $rutaImagenes) {            
		$pdf->AddPage();
		$pdf->Image($rutaImagenes, 15, $rowConfig2['valor'], 580, 680);//$rowConfig2['valor'] = 80  680
		unlink($rutaImagenes);
		
        if($rowConfig403['valor'] == "3"){
            $pdf->Image($rutaEncabezado, 55, 25, 530+20, 40+5);
            $pdf->Image($rutaLogo,15,25,80);
            
            unlink($rutaEncabezado);
        }
	}
}

$pdf->SetDisplayMode(88);
//$pdf->AutoPrint(true);
$pdf->Output();


function dividirObservacion($img, $texto, $posY, $textColor){
    $array[] = substr($texto,0,33);
    $array[] = substr($texto,33,33);
    $array[] = substr($texto,66,33);
    $array[] = substr($texto,99,33);
    $array[] = substr($texto,132,33);
    $array[] = substr($texto,165,33);
    $array[] = substr($texto,198,33);
    $array[] = substr($texto,231,33);
    
    $adicional = 0;
    
    foreach($array as $texto){
        if($texto != NULL){
            imagestring($img,1,110,$posY,strtoupper($texto),$textColor); // <----  
            $posY += 10;
            $adicional++;
        }
    }
    return $adicional;
    
}

function fechaTiempo($fechaTiempo){
	if($fechaTiempo != ""){
		return date(spanDateFormat." h:i A",strtotime($fechaTiempo));
	}
}
?>
