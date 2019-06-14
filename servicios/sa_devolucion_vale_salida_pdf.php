<?php
//ESTE ARCHIVO LO USA sa_formato_vale_salida.php ESTE SERIA EL VALE DE ENTRADA QUE MUESTRA
require_once ("../connections/conex.php");
$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$tpDocumento = $valCadBusq[0];// 0 = busca por vale de salida, 1 = busca por vale entrada
$idDocumento = $valCadBusq[1];//es el id del vale de salida o el id vale entrada

if($tpDocumento == 1){
		$queryIdValeSalida = sprintf("SELECT id_vale_salida				
							FROM sa_vale_entrada WHERE id_vale_entrada = %s LIMIT 1",
				valTpDato($idDocumento,"int"));
		$rs = mysql_query($queryIdValeSalida);
		if(!$rs) { die("Error buscando id vale salida en vales de entrada. <br>".mysql_error()." <br> Linea:".__LINE__); }
		$row = mysql_fetch_assoc($rs);
		
		$idDocumento = $row["id_vale_salida"];
		
}
/*
$query = sprintf("SELECT 
	*,
	cj_cc_cliente.direccion AS direccion_cliente,
	pg_empleado.apellido AS apellido_empleado
FROM sa_recepcion
	LEFT OUTER JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
	LEFT OUTER JOIN en_registro_placas ON (sa_cita.id_registro_placas = en_registro_placas.id_registro_placas)
	LEFT OUTER JOIN an_uni_bas ON (en_registro_placas.id_unidad_basica = an_uni_bas.id_uni_bas)
	LEFT OUTER JOIN an_marca ON (an_uni_bas.mar_uni_bas = an_marca.id_marca)
	LEFT OUTER JOIN an_modelo ON (an_uni_bas.mod_uni_bas = an_modelo.id_modelo)
	INNER JOIN vw_sa_orden ON (sa_recepcion.id_recepcion = vw_sa_orden.id_recepcion)
	INNER JOIN sa_vale_salida ON (sa_vale_salida.id_orden = vw_sa_orden.id_orden)
	LEFT OUTER JOIN cj_cc_notacredito ON (sa_vale_salida.id_vale_salida = cj_cc_notacredito.idDocumento)
	INNER JOIN cj_cc_cliente ON (cj_cc_notacredito.idCliente = cj_cc_cliente.id)
	INNER JOIN sa_tipo_orden ON (vw_sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden)
	LEFT OUTER JOIN pg_empleado ON (sa_vale_salida.id_empleado = pg_empleado.id_empleado)
WHERE cj_cc_notacredito.idNotaCredito = %s",
	valTpDato($idDocumento,"int"));*/
	$query = sprintf("SELECT *, cj_cc_cliente.direccion As direccion_cliente, pg_empleado.apellido AS apellido_empleado,
					sa_vale_entrada.numero_vale_entrada,
					sa_vale_entrada.fecha_creada,
					sa_vale_entrada.monto_exento,
					sa_vale_entrada.subtotal
					FROM sa_vale_salida 
						LEFT JOIN sa_vale_entrada ON sa_vale_salida.id_vale_salida = sa_vale_entrada.id_vale_salida
						LEFT JOIN sa_orden ON (sa_vale_salida.id_orden = sa_orden.id_orden)
						LEFT JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
						LEFT JOIN sa_cita ON (sa_recepcion.id_cita = sa_cita.id_cita)
						LEFT JOIN en_registro_placas ON (sa_cita.id_registro_placas = en_registro_placas.id_registro_placas)
						LEFT JOIN an_uni_bas ON (en_registro_placas.id_unidad_basica = an_uni_bas.id_uni_bas)
						LEFT JOIN an_marca ON (an_uni_bas.mar_uni_bas = an_marca.id_marca)
						LEFT JOIN an_modelo ON (an_uni_bas.mod_uni_bas = an_modelo.id_modelo)
						LEFT JOIN cj_cc_cliente ON (sa_cita.id_cliente_contacto = cj_cc_cliente.id)
						LEFT JOIN sa_tipo_orden ON (sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden)
						LEFT JOIN pg_empleado ON (sa_vale_salida.id_empleado = pg_empleado.id_empleado)
					WHERE sa_vale_salida.id_vale_salida = %s",
				valTpDato($idDocumento,"int"));
$rs = mysql_query($query, $conex);
if(!$rs) die ("Error ".mysql_error());
//if (!$rsAlmacen) return $objResponse->alert(mysql_error());
$row = mysql_fetch_assoc($rs);

$img = @imagecreate(600, 640) or die("No se puede crear la imagen");

//estableciendo los colores de la paleta:
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);


imagestring($img,1,380,0,/*"NOTA CREDITO"*/"VALE DE ENTRADA",$textColor);

imagestring($img,1,340,10,"SERIE-SR",$textColor);

imagestring($img,1,340,20,"NRO VALE ENTRADA",$textColor);
imagestring($img,1,430,20,":",$textColor);
imagestring($img,1,440,20,$row['numero_vale_entrada'],$textColor);

imagestring($img,1,340,30,"ORDEN DE TRABAJO",$textColor);
imagestring($img,1,430,30,":",$textColor);
imagestring($img,1,440,30,$row['numero_orden']." ".utf8_encode($row['nombre_tipo_orden']),$textColor); // <----

imagestring($img,1,340,40,"NRO VALE SALIDA.",$textColor);
imagestring($img,1,430,40,":",$textColor);
imagestring($img,1,440,40,$row['numero_vale'],$textColor); // <----

imagestring($img,1,340,50,"FECHA EMISION",$textColor);
imagestring($img,1,430,50,":",$textColor);
imagestring($img,1,440,50,date("d-m-Y", strtotime($row['fecha_creada'])),$textColor); // <----
/*
imagestring($img,1,340,60,"FECHA VENCIMIENTO",$textColor);
imagestring($img,1,430,60,":",$textColor);
imagestring($img,1,440,60,date("d-m-Y", strtotime($row['fecha_vale']))." CRED. ".$row['diasDeCredito']." D�AS",$textColor); // <----
*/
imagestring($img,1,340,60,"ASESOR",$textColor);
imagestring($img,1,430,60,":",$textColor);
imagestring($img,1,440,60,strtoupper($row['nombre_empleado']." ".$row['apellido_empleado']),$textColor); // <----

$dirCliente = explode(",",strtoupper($row['direccion_cliente']));

imagestring($img,1,15,40,strtoupper($row['nombre']." ".$row['apellido']),$textColor); // <----
imagestring($img,1,170,40,"CODIGO",$textColor);
imagestring($img,1,200,40,":",$textColor);
imagestring($img,1,210,40,$row['id'],$textColor); // <----

imagestring($img,1,15,50,substr($dirCliente[0],0,60),$textColor); // <----

imagestring($img,1,15,60, substr($dirCliente[1]." ".$dirCliente[2],0,30),$textColor); // <----
imagestring($img,1,170,60,"TELEF.",$textColor);
imagestring($img,1,200,60,":",$textColor);
imagestring($img,1,210,60,$row['telf'],$textColor); // <----

imagestring($img,1,15,70,  substr($dirCliente[3]." ".$dirCliente[4]." ".$dirCliente[5],0,25),$textColor); // <----
imagestring($img,1,150,70,$spanCI."/".$spanRIF,$textColor);
imagestring($img,1,200,70,":",$textColor);
imagestring($img,1,210,70,$row['lci']."-".$row['ci'],$textColor); // <----

imagestring($img,1,5,110,"-------------------------------------------------------------------------------------------------------------------",$textColor);
imagestring($img,1,30,120,"CODIGO",$textColor); // <----
imagestring($img,1,190,120,"DESCRIPCION",$textColor); // <----
imagestring($img,1,330,120,"CANTIDAD/MECANICO",$textColor); // <----
imagestring($img,1,435,120,"PRECIO UNIT.",$textColor); // <----
imagestring($img,1,525,120,"TOTAL",$textColor); // <----
imagestring($img,1,5,130,"-------------------------------------------------------------------------------------------------------------------",$textColor);

/* DETALLES DE LOS REPUESTOS */
//if ($tpDocumento == 0) { // 0 = Repuestos
	$queryRepuestosGenerales = sprintf("SELECT 
		iv_subsecciones.id_subseccion,
		iv_articulos.codigo_articulo,
		iv_tipos_articulos.descripcion AS descripcion_tipo,
		iv_articulos.descripcion AS descripcion_articulo,
		iv_secciones.descripcion AS descripcion_seccion,
		sa_det_vale_salida_articulo.cantidad,
		sa_det_vale_salida_articulo.precio_unitario,
		sa_det_vale_salida_articulo.id_iva,
		sa_det_vale_salida_articulo.iva,
		sa_det_vale_salida_articulo.id_articulo
	FROM iv_articulos
		INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
		INNER JOIN iv_tipos_articulos ON (iv_articulos.id_tipo_articulo = iv_tipos_articulos.id_tipo_articulo)
		INNER JOIN iv_secciones ON (iv_subsecciones.id_seccion = iv_secciones.id_seccion)
		INNER JOIN sa_det_vale_salida_articulo ON (iv_articulos.id_articulo = sa_det_vale_salida_articulo.id_articulo)
		INNER JOIN sa_vale_salida ON (sa_vale_salida.id_vale_salida = sa_det_vale_salida_articulo.id_vale_salida)
		#INNER JOIN cj_cc_notacredito ON (cj_cc_notacredito.idDocumento = sa_vale_salida.id_vale_salida)
	WHERE sa_vale_salida.id_vale_salida = %s",
		valTpDato($idDocumento,"int"));
//} 

/*else if ($tpDocumento == 1) { // 1 = Servicios
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
		valTpDato($idDocumento,"int"));
}*/
$rsOrdenDetRep = mysql_query($queryRepuestosGenerales, $conex) or die(mysql_error()."<br>Linea:".__LINE__);
$posY = 140;
while ($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)) {
	$anex = (strlen($rowOrdenDetRep['descripcion_articulo']) > 40) ? "..." : "";
	
	$cantidad = $rowOrdenDetRep['cantidad'];
	$precioUnit = number_format($rowOrdenDetRep['precio_unitario'],2,".",",");
	$total = number_format(($rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario']),2,".",",");
	$posXCantidad = 340+((5*13)-(strlen($cantidad)*5))/2;
	$posXPrecio = 425+((5*15)-(strlen($precioUnit)*5));
	$posXTotal = 500+((5*15)-(strlen($total)*5));
	
	imagestring($img,1,10,$posY,$rowOrdenDetRep['codigo_articulo'],$textColor); // <----
	imagestring($img,1,110,$posY,strtoupper(substr($rowOrdenDetRep['descripcion_articulo'],0,40).$anex),$textColor); // <----
	imagestring($img,1,$posXCantidad,$posY,$cantidad,$textColor); // <----
	imagestring($img,1,$posXPrecio,$posY,$precioUnit,$textColor); // <----
	imagestring($img,1,$posXTotal,$posY,$total,$textColor); // <----
	
	$posY += 10;
}

/* DETALLES DE LOS TEMPARIOS */
$queryFactDetTemp = sprintf("
		SELECT 
		sa_modo.descripcion_modo,
		sa_tempario.codigo_tempario,
		sa_tempario.descripcion_tempario,
		sa_det_vale_salida_tempario.operador,
		sa_det_vale_salida_tempario.id_tempario,
		sa_det_vale_salida_tempario.precio,
		sa_det_vale_salida_tempario.base_ut_precio,
		sa_det_vale_salida_tempario.id_modo,
		(case sa_det_vale_salida_tempario.id_modo when '1' then sa_det_vale_salida_tempario.ut * sa_det_vale_salida_tempario.precio_tempario_tipo_orden / sa_det_vale_salida_tempario.base_ut_precio when '2' then sa_det_vale_salida_tempario.precio when '3' then sa_det_vale_salida_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
		(case sa_det_vale_salida_tempario.id_modo when '1' then sa_det_vale_salida_tempario.ut when '2' then sa_det_vale_salida_tempario.precio when '3' then sa_det_vale_salida_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
		sa_det_vale_salida_tempario.id_det_vale_salida_tempario,
		pg_empleado.nombre_empleado,
		pg_empleado.apellido,
		pg_empleado.id_empleado,
		sa_mecanicos.id_mecanico,
		sa_det_vale_salida_tempario.aprobado,
		sa_det_vale_salida_tempario.origen_tempario,
		sa_det_vale_salida_tempario.origen_tempario + 0 AS idOrigen,
		sa_paquetes.codigo_paquete,
		sa_paquetes.id_paquete,
		sa_det_vale_salida_tempario.precio_tempario_tipo_orden
		FROM
		sa_mecanicos
		INNER JOIN pg_empleado ON (sa_mecanicos.id_empleado = pg_empleado.id_empleado)
		INNER JOIN sa_det_vale_salida_tempario ON (sa_mecanicos.id_mecanico = sa_det_vale_salida_tempario.id_mecanico)
		INNER JOIN sa_vale_salida ON (sa_vale_salida.id_vale_salida = sa_det_vale_salida_tempario.id_vale_salida)
		#INNER JOIN cj_cc_notacredito ON (cj_cc_notacredito.idDocumento = sa_vale_salida.id_vale_salida)
		INNER JOIN sa_modo ON (sa_modo.id_modo = sa_det_vale_salida_tempario.id_modo)
		INNER JOIN sa_tempario ON (sa_tempario.id_tempario = sa_det_vale_salida_tempario.id_tempario)
		LEFT OUTER JOIN sa_paquetes ON (sa_paquetes.id_paquete = sa_det_vale_salida_tempario.id_paquete)
		WHERE sa_vale_salida.id_vale_salida = %s
		ORDER BY sa_det_vale_salida_tempario.id_paquete",
	valTpDato($idDocumento,"int"));
$rsFactDetTemp = mysql_query($queryFactDetTemp, $conex) or die(mysql_error()."<br>Linea:".__LINE__);


while ($rowFactDetTemp = mysql_fetch_assoc($rsFactDetTemp)) {
	$anex = (strlen($rowFactDetTemp['descripcion_tempario']) > 42) ? "..." : "";
	
	$caractCantTempario = ($rowFactDetTemp['id_modo'] == 1) ? number_format($rowFactDetTemp['precio_por_tipo_orden']/100,2,".",",") : number_format(1,2,".",",");//Es entre 100 o la base ut? : $rowFactDetTemp['precio_por_tipo_orden']/100
		
	$caracterPrecioTempario = ($rowFactDetTemp['id_modo'] == 1) ? $rowFactDetTemp['precio_tempario_tipo_orden'] : $rowFactDetTemp['precio_por_tipo_orden'];
	
	$cantidad = $caractCantTempario."/MEC:".sprintf("%04s",$rowFactDetTemp['id_mecanico']);
	$precioUnit = number_format($caracterPrecioTempario,2,".",",");
	$total = number_format($rowFactDetTemp['total_por_tipo_orden'],2,".",",");
	$posXCantidad = 340+((5*13)-(strlen($cantidad)*5))/2;
	$posXPrecio = 425+((5*15)-(strlen($precioUnit)*5));
	$posXTotal = 500+((5*15)-(strlen($total)*5));
	
	imagestring($img,1,10,$posY,$rowFactDetTemp['codigo_tempario'],$textColor); // <----
	imagestring($img,1,110,$posY,strtoupper(substr($rowFactDetTemp['descripcion_tempario'],0,42).$anex),$textColor); // <----
	imagestring($img,1,$posXCantidad,$posY,$cantidad,$textColor); // <----
	imagestring($img,1,$posXPrecio,$posY,$precioUnit,$textColor); // <----
	imagestring($img,1,$posXTotal,$posY,$total,$textColor); // <----
	
	$posY += 10;
}

/* DETALLE DE LOS TOT */
$queryDetalleTot = sprintf("SELECT *
FROM
  sa_orden_tot
  INNER JOIN cp_proveedor ON (sa_orden_tot.id_proveedor = cp_proveedor.id_proveedor)
  INNER JOIN sa_det_vale_salida_tot ON (sa_orden_tot.id_orden_tot = sa_det_vale_salida_tot.id_orden_tot)
  INNER JOIN sa_vale_salida ON (sa_vale_salida.id_vale_salida = sa_det_vale_salida_tot.id_vale_salida)
  #INNER JOIN cj_cc_notacredito ON (cj_cc_notacredito.idDocumento = sa_vale_salida.id_vale_salida)
WHERE sa_vale_salida.id_vale_salida = %s",
	valTpDato($idDocumento,"int"));
$rsDetalleTot = mysql_query($queryDetalleTot) or die(mysql_error()."<br>Linea:".__LINE__);
while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
	$cantidad = "1";
	$precioUnit = number_format($rowDetalleTot['monto_total']+($rowDetalleTot['monto_total']*$rowDetalleTot['porcentaje_tot']/100),2,".",",");
	$total = number_format($rowDetalleTot['monto_total']+($rowDetalleTot['monto_total']*$rowDetalleTot['porcentaje_tot']/100),2,".",",");
	$posXCantidad = 340+((5*13)-(strlen($cantidad)*5))/2;
	$posXPrecio = 425+((5*15)-(strlen($precioUnit)*5));
	$posXTotal = 500+((5*15)-(strlen($total)*5));
	
	imagestring($img,1,10,$posY,$rowDetalleTot['id_orden_tot'],$textColor); // <----
	imagestring($img,1,110,$posY,"T.O.T",$textColor); // <----
	imagestring($img,1,$posXCantidad,$posY,$cantidad,$textColor); // <----
	imagestring($img,1,$posXPrecio,$posY,$precioUnit,$textColor); // <----
	imagestring($img,1,$posXTotal,$posY,$total,$textColor); // <----
	
	$posY += 10;
}

/* DETALLES DE LAS NOTAS */
$queryDetTipoDocNotas = sprintf("
	SELECT 
	sa_det_vale_salida_notas.id_det_vale_salida_nota AS idDetNota,
	sa_det_vale_salida_notas.descripcion_nota,
	sa_det_vale_salida_notas.precio
	FROM
	sa_vale_salida
	#INNER JOIN sa_vale_salida ON (cj_cc_notacredito.idDocumento = sa_vale_salida.id_vale_salida)
	INNER JOIN sa_det_vale_salida_notas ON (sa_vale_salida.id_vale_salida = sa_det_vale_salida_notas.id_vale_salida)
	WHERE sa_vale_salida.id_vale_salida = %s",
	valTpDato($idDocumento,"int"));
$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas) or die(mysql_error()."<br>Linea:".__LINE__);
while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
	$cantidad = "1";
	$precioUnit = number_format($rowDetTipoDocNotas['precio'],2,".",",");
	$total = number_format($rowDetTipoDocNotas['precio'],2,".",",");
	$posXCantidad = 340+((5*13)-(strlen($cantidad)*5))/2;
	$posXPrecio = 425+((5*15)-(strlen($precioUnit)*5));
	$posXTotal = 500+((5*15)-(strlen($total)*5));
	
	imagestring($img,1,10,$posY,"N".$rowDetTipoDocNotas['idDetNota'],$textColor); // <----
	imagestring($img,1,110,$posY,$rowDetTipoDocNotas['descripcion_nota'],$textColor); // <----
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
imagestring($img,1,315,570,substr($row['nom_uni_bas'],0,6),$textColor); // <----

imagestring($img,1,5,580,"----------------------------------------------------------------------",$textColor);

$posY = 550;
$subTotal = number_format($row['subtotal'],2,".",",");
$posX = 510+((5*13)-(strlen($subTotal)*5));
imagestring($img,1,360,$posY,"SUB-TOTAL",$textColor);
imagestring($img,1,515,$posY,":",$textColor);
imagestring($img,1,$posX,$posY,$subTotal,$textColor); // <----

$posY += 10;
$descuento = number_format($row['descuento'],2,".",",");
$posX = 510+((5*13)-(strlen($descuento)*5));
imagestring($img,1,360,$posY,"DESCUENTO",$textColor);
imagestring($img,1,515,$posY,":",$textColor);
imagestring($img,1,$posX,$posY,$descuento,$textColor); // <----

$posY += 10;
$montoExento = number_format($row['monto_exento'],2,".",",");
$posX = 510+((5*13)-(strlen($montoExento)*5));
imagestring($img,1,360,$posY,"MONTO EXENTO",$textColor);
imagestring($img,1,515,$posY,":",$textColor);
imagestring($img,1,$posX,$posY,$montoExento,$textColor); // <----

$queryIvas = sprintf("SELECT
                        pg_iva.observacion,
                        sa_vale_salida_iva.base_imponible, 
                        sa_vale_salida_iva.subtotal_iva, 
                        sa_vale_salida_iva.id_iva, 
                        sa_vale_salida_iva.iva 
                        FROM sa_vale_salida_iva
                        INNER JOIN pg_iva ON sa_vale_salida_iva.id_iva = pg_iva.idIva 
                        WHERE id_vale_salida = %s",
                valTpDato($idDocumento, "int"));

$rsIvas = mysql_query($queryIvas);
if(!$rsIvas) { die(mysql_error()."<br><br>Line: ".__LINE__."<br>Query: ".$queryIvas); }

while($rowIvas = mysql_fetch_assoc($rsIvas)){
    $posY += 10;
    $porcentajeIva = number_format($rowIvas['iva'],2,".",",")."%";
    $calculoIva = number_format($rowIvas['subtotal_iva'],2,".",",");
    $posXIva = 470+((5*8)-(strlen($porcentajeIva)*5));
    $posX = 510+((5*13)-(strlen($calculoIva)*5));
    imagestring($img,1,360,$posY,$rowIvas["observacion"],$textColor);
    imagestring($img,1,420,$posY,number_format($rowIvas["base_imponible"],2,".",","),$textColor);
    imagestring($img,1,$posXIva,$posY,$porcentajeIva,$textColor);
    imagestring($img,1,515,$posY,":",$textColor);
    imagestring($img,1,$posX,$posY,$calculoIva,$textColor); // <----
}

//$baseImponible = number_format($row['baseImponible'],2,".",",");
//$posX = 510+((5*13)-(strlen($baseImponible)*5));
//imagestring($img,1,360,580,"BASE IMPONIBLE",$textColor);
//imagestring($img,1,485,580,":",$textColor);
//imagestring($img,1,$posX,580,$baseImponible,$textColor); // <----
//
//$porcentajeIva = number_format($row['porcentajeIva'],2,".",",")."%";
//$calculoIva = number_format($row['calculoIva'],2,".",",");
//$posXIva = 440+((5*8)-(strlen($porcentajeIva)*5));
//$posX = 510+((5*13)-(strlen($calculoIva)*5));
//imagestring($img,1,360,600,nombreIva($row['idIva'])/*"I.V.A."*/,$textColor);
//imagestring($img,1,$posXIva,600,$porcentajeIva,$textColor);
//imagestring($img,1,485,600,":",$textColor);
//imagestring($img,1,$posX,600,$calculoIva,$textColor); // <----

//$montoNoGravado = number_format($row['monto_exento'],2,".",",");
//$posX = 510+((5*13)-(strlen($montoNoGravado)*5));
//imagestring($img,1,360,610,"MONTO NO GRAVADO",$textColor);
//imagestring($img,1,485,610,":",$textColor);
//imagestring($img,1,$posX,610,$montoNoGravado ,$textColor); // <----

//$porcentajeIva = number_format($row['porcentajeIvaDeLujoFactura'],2,".",",")."%";
//$calculoImpuestoLujo = number_format($row['calculoIvaDeLujoFactura'],2,".",",");
//$posXIva = 440+((5*8)-(strlen($porcentajeIva)*5));
//$posX = 510+((5*13)-(strlen($calculoImpuestoLujo)*5));
//imagestring($img,1,360,620,"IMPUESTO AL LUJO",$textColor);
//imagestring($img,1,$posXIva,620,$porcentajeIva,$textColor);
//imagestring($img,1,485,620,":",$textColor);
//imagestring($img,1,$posX,620,$calculoImpuestoLujo,$textColor); // <----


$posY += 10;
$totalFactura = number_format($row['monto_total'],2,".",",");
$posX = 510+((5*13)-(strlen($totalFactura)*5));
imagestring($img,1,360,$posY,"TOTAL",$textColor);
imagestring($img,1,515,$posY,":",$textColor);
imagestring($img,1,$posX,$posY,$totalFactura,$textColor); // <----




$r = imagepng($img,"img/tmp/"."factura_venta".'.png');


/* ARCHIVO PDF */
require('clases/fpdf/fpdf.php');
require('clases/fpdf/fpdf_print.inc.php');

$pdf = new PDF_AutoPrint('P','pt','Letter');
$pdf->SetMargins("0","0","0");
$pdf->SetAutoPageBreak(1,"0");

$pdf->AddPage();

$pdf->Image("img/tmp/factura_venta.png", 0, 95, 600, 640);

$pdf->SetDisplayMode(88);
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
?>