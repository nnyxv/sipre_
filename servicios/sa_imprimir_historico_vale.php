<?php
//ESTE ARCHIVO LO USA ac_sa_devolucion_vale_salida_list.php y ac_sa_vale_salida_historico_list.php ESTE ES PDF DE VALE SALIDA
require_once("../connections/conex.php");
require_once('clases/fpdf/fpdf.php');
require_once('clases/fpdf/fpdf_print.inc.php');
require_once('clases/barcode128.inc.php');

$ruta = "clases/temp_codigo/img_codigo.png";

$valCadBusq = explode("|",$_GET['valBusq']);

$idDocumento = $valCadBusq[0];

//numeracion vale salida barra
$queryNumeroValeSalida = sprintf("SELECT numero_vale FROM sa_vale_salida WHERE id_vale_salida = %s LIMIT 1",
							valTpDato($idDocumento,"int"));
$rs = mysql_query($queryNumeroValeSalida) or die(mysql_error(). "<br>Linea: ".__LINE__);
$row = mysql_fetch_assoc($rs);

$numeroValeSalida = $row["numero_vale"];
$aux = getBarcode($numeroValeSalida,'clases/temp_codigo/img_codigo');

if ($valCadBusq[1] == 1){
	$queryPrincipal = sprintf("SELECT tipo_presupuesto FROM sa_presupuesto WHERE id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
	$rsPrincipal = mysql_query($queryPrincipal) or die(mysql_error());
	$rowPrincipal = mysql_fetch_array($rsPrincipal);

	if ($rowPrincipal['tipo_presupuesto'] == 1) {
		$queryCliente = sprintf("SELECT
		  sa_presupuesto.id_presupuesto,
		  sa_presupuesto.fecha_presupuesto,
		  sa_presupuesto.fecha_vencimiento,
		  CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
		  CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
		  cj_cc_cliente.direccion AS direccion_cliente,
		  cj_cc_cliente.telf AS telf_cliente,
		  vw_sa_vales_recepcion.nom_uni_bas,
		  vw_sa_vales_recepcion.des_uni_bas,
		  vw_sa_vales_recepcion.nom_marca,
		  vw_sa_vales_recepcion.color,
		  vw_sa_vales_recepcion.placa,
		  vw_sa_vales_recepcion.chasis,
		  vw_sa_vales_recepcion.des_modelo,
		  pg_empleado.nombre_empleado,
		  pg_empleado.apellido AS apellido_empleado,
		  sa_presupuesto.subtotal,
		  sa_presupuesto.porcentaje_descuento,
		  sa_presupuesto.subtotal_descuento,
		  sa_presupuesto.id_empresa,
		  sa_presupuesto.tipo_presupuesto,
		  sa_presupuesto.id_orden,
		  sa_presupuesto.idIva,
		  sa_presupuesto.iva,
		  sa_presupuesto.id_tipo_orden,
		  vw_sa_vales_recepcion.ano_uni_bas,
		  an_transmision.nom_transmision
		FROM sa_presupuesto
		  INNER JOIN cj_cc_cliente ON (sa_presupuesto.id_cliente = cj_cc_cliente.id)
		  INNER JOIN sa_orden ON (sa_presupuesto.id_orden = sa_orden.id_orden)
		  INNER JOIN vw_sa_vales_recepcion ON (sa_orden.id_recepcion = vw_sa_vales_recepcion.id_recepcion)
		  INNER JOIN pg_empleado ON (sa_presupuesto.id_empleado = pg_empleado.id_empleado)
		  INNER JOIN an_uni_bas ON (vw_sa_vales_recepcion.id_uni_bas = an_uni_bas.id_uni_bas)
		  INNER JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
		WHERE sa_presupuesto.id_presupuesto = %s",
		valTpDato($idDocumento,"int"));

		$texto_presupuesto_cotizacion = "PRESUPUESTO";
	} else{
		$queryCliente = sprintf("SELECT
			sa_presupuesto.id_presupuesto,
			sa_presupuesto.fecha_presupuesto,
			sa_presupuesto.fecha_vencimiento,
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
			cj_cc_cliente.direccion AS direccion_cliente,
			cj_cc_cliente.telf AS telf_cliente,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido AS apellido_empleado,
			sa_presupuesto.subtotal,
			sa_presupuesto.porcentaje_descuento,
			sa_presupuesto.subtotal_descuento,
			sa_presupuesto.id_empresa,
			sa_presupuesto.tipo_presupuesto,
			sa_presupuesto.id_orden,
			sa_presupuesto.idIva,
			sa_presupuesto.iva,
			sa_presupuesto.id_tipo_orden,
			vw_sa_vales_recepcion.ano_uni_bas,
			an_marca.nom_marca,
			an_modelo.des_modelo,
			an_transmision.nom_transmision
		FROM sa_presupuesto
			INNER JOIN cj_cc_cliente ON (sa_presupuesto.id_cliente = cj_cc_cliente.id)
			INNER JOIN pg_empleado ON (sa_presupuesto.id_empleado = pg_empleado.id_empleado)
			INNER JOIN an_uni_bas ON (sa_presupuesto.id_unidad_basica = an_uni_bas.id_uni_bas)
			INNER JOIN an_marca ON (an_uni_bas.mar_uni_bas = an_marca.id_marca)
			INNER JOIN an_modelo ON (an_uni_bas.mod_uni_bas = an_modelo.id_modelo)
			INNER JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
		WHERE sa_presupuesto.id_presupuesto =  %s",
			valTpDato($idDocumento,"int"));

		$texto_presupuesto_cotizacion = "COTIZACION";
	}
} else {
	if($valCadBusq[2] == 3) {
		$queryCliente = sprintf("SELECT
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
			cj_cc_cliente.direccion AS direccion_cliente,
			cj_cc_cliente.telf AS telf_cliente,
			vw_sa_vales_recepcion.nom_uni_bas,
			vw_sa_vales_recepcion.des_uni_bas,
			vw_sa_vales_recepcion.nom_marca,
			vw_sa_vales_recepcion.color,
			vw_sa_vales_recepcion.placa,
			vw_sa_vales_recepcion.chasis,
			vw_sa_vales_recepcion.des_modelo,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido AS apellido_empleado,
			sa_orden.id_empresa,
			sa_orden.id_orden,
			sa_orden.subtotal,
			sa_orden.porcentaje_descuento,
			sa_orden.subtotal_descuento,
			sa_orden.idIva,
			sa_orden.iva,
			sa_orden.id_tipo_orden,
			vw_sa_vales_recepcion.ano_uni_bas,
			an_transmision.nom_transmision,
			sa_orden.tiempo_orden AS fecha_presupuesto,
			sa_vale_salida.id_vale_salida,
			sa_vale_salida.numero_vale,
			sa_vale_salida.fecha_vale,
			sa_vale_salida.estado_vale,
			sa_vale_salida.motivo_vale
		FROM sa_orden
			INNER JOIN vw_sa_vales_recepcion ON (sa_orden.id_recepcion = vw_sa_vales_recepcion.id_recepcion)
			INNER JOIN pg_empleado ON (sa_orden.id_empleado = pg_empleado.id_empleado)
			INNER JOIN an_uni_bas ON (vw_sa_vales_recepcion.id_uni_bas = an_uni_bas.id_uni_bas)
			INNER JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
			INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
			INNER JOIN cj_cc_cliente ON (IFNULL(vw_sa_vales_recepcion.id_cliente_pago, vw_sa_vales_recepcion.id) = cj_cc_cliente.id)
		WHERE sa_vale_salida.id_vale_salida = %s",
			valTpDato($idDocumento,"int"));

		$texto_presupuesto_cotizacion = "VALE DE SALIDA";
	} else {
		$queryCliente = sprintf("SELECT
			CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
			CONCAT_WS('-', cj_cc_cliente.lci, cj_cc_cliente.ci) AS cedula_cliente,
			cj_cc_cliente.direccion AS direccion_cliente,
			cj_cc_cliente.telf AS telf_cliente,
			vw_sa_vales_recepcion.nom_uni_bas,
			vw_sa_vales_recepcion.des_uni_bas,
			vw_sa_vales_recepcion.nom_marca,
			vw_sa_vales_recepcion.color,
			vw_sa_vales_recepcion.placa,
			vw_sa_vales_recepcion.chasis,
			vw_sa_vales_recepcion.des_modelo,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido AS apellido_empleado,
			sa_orden.id_empresa,
			sa_orden.id_orden,
			sa_orden.subtotal,
			sa_orden.porcentaje_descuento,
			sa_orden.subtotal_descuento,
			sa_orden.id_tipo_orden,
			sa_orden.idIva,
			sa_orden.iva,
			vw_sa_vales_recepcion.ano_uni_bas,
			an_transmision.nom_transmision,
			sa_orden.tiempo_orden as fecha_presupuesto
		FROM sa_orden
			INNER JOIN vw_sa_vales_recepcion ON (sa_orden.id_recepcion = vw_sa_vales_recepcion.id_recepcion)
			INNER JOIN pg_empleado ON (sa_orden.id_empleado = pg_empleado.id_empleado)
			INNER JOIN an_uni_bas ON (vw_sa_vales_recepcion.id_uni_bas = an_uni_bas.id_uni_bas)
			INNER JOIN an_transmision ON (an_uni_bas.trs_uni_bas = an_transmision.id_transmision)
			INNER JOIN cj_cc_cliente ON (IFNULL(vw_sa_vales_recepcion.id_cliente_pago, vw_sa_vales_recepcion.id) = cj_cc_cliente.id)
		WHERE sa_orden.id_orden = %s",
			valTpDato($idDocumento,"int"));

		$texto_presupuesto_cotizacion = "ORDEN DE SERVICIO";
	}

}
$rsCliente = mysql_query($queryCliente, $conex);
$rowCliente = mysql_fetch_assoc($rsCliente);

$queryEmpresa = sprintf("SELECT logo_familia, nombre_empresa, rif, direccion, telefono1, fax FROM vw_iv_empresas_sucursales
WHERE id_empresa_reg = %s",
	$rowCliente['id_empresa']);

$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
$rowEmpresa = mysql_fetch_array($rsEmpresa);

$ruta_logo = $rowEmpresa['logo_familia'];

//$img = @imagecreate(816, 1092) or die("No se puede crear la imagen");
$img = @imagecreate(612, 792) or die("No se puede crear la imagen");
//estableciendo los colores de la paleta:
$backgroundColor = imagecolorallocate($img, 255, 255, 255);
$textColor = imagecolorallocate($img, 0, 0, 0);

//NOMBRE EMPRESA
imagestring($img,1,135,10,$rowEmpresa['nombre_empresa'],$textColor);

//RIF EMPRESA
imagestring($img,1,135,20,"RIF: ".$rowEmpresa['rif'],$textColor);


$queryTipoOrden = sprintf("SELECT nombre_tipo_orden FROM sa_tipo_orden WHERE id_tipo_orden = %s",valTpDato($rowCliente['id_tipo_orden'],"int"));
$rsTipoOrden = mysql_query($queryTipoOrden);
$rowTipoOrden = mysql_fetch_array($rsTipoOrden);
//TEXTO "PRESUPUESTOS o COTIZACION o ORDEN DE SERVICIO"
imagestring($img,2,250,15,$texto_presupuesto_cotizacion." (".$rowTipoOrden['nombre_tipo_orden'].")",$textColor);

//TEXTO "DATOS DEL CLIENTE"
imageline($img,10,50,602,50,$textColor);
imagestring($img,1,250,52,"DATOS DEL CLIENTE",$textColor);
imageline($img,10,60,602,60,$textColor);
imageline($img,10,50,10,150,$textColor);
imageline($img,602,50,602,150,$textColor);

//TEXTO "NOMBRE"
imageline($img,10,60,10,70,$textColor);
imageline($img,70,60,70,140,$textColor);
imagestring($img,1,10,62,str_pad("NOMBRE", 12, " ", STR_PAD_BOTH),$textColor);
//NOMBRE
imagestring($img,1,80,62,$rowCliente['nombre_cliente'],$textColor);
imageline($img,10,70,602,70,$textColor);

//TEXTO "DIRECCION"
imagestring($img,1,10,72,str_pad("DIRECCION", 12, " ", STR_PAD_BOTH),$textColor);
//DIRECCION
imagestring($img,1,80,72,$rowCliente['direccion_cliente'],$textColor);
imageline($img,10,80,602,80,$textColor);

//TEXTO "TLFS"
imagestring($img,1,10,82,str_pad("TLFS", 12, " ", STR_PAD_BOTH),$textColor);
//TLFS
imagestring($img,1,80,82,$rowCliente['telf_cliente'],$textColor);
//TEXTO "RIF/CIV"
imageline($img,167,80,167,90,$textColor);
imagestring($img,1,170,82,str_pad($spanCI."/".$spanRIF, 12, " ", STR_PAD_BOTH),$textColor);
//RIF/CEDULA
imageline($img,236,80,236,90,$textColor);
imagestring($img,1,240,82,$rowCliente['cedula_cliente'],$textColor);
//TEXTO "O.R."
imageline($img,306,80,306,90,$textColor);
if($valCadBusq[2] == 3)
	imagestring($img,1,310,82,str_pad("NRO.VALE", 12, " ", STR_PAD_BOTH),$textColor);
else
	imagestring($img,1,310,82,str_pad("O.R.", 12, " ", STR_PAD_BOTH),$textColor);
//ORDEN
imageline($img,371,80,371,90,$textColor);
if($valCadBusq[2] == 3)
	imagestring($img,1,375,82,$rowCliente['numero_vale'],$textColor);  // <----- Numero de Orden
else
	imagestring($img,1,375,82,$rowCliente['id_orden'],$textColor);
//TEXTO "FECHA"
imageline($img,430,80,430,90,$textColor);
imagestring($img,1,435,82,str_pad("FECHA", 12, " ", STR_PAD_BOTH),$textColor);
//FECHA
imageline($img,497,80,497,90,$textColor);
if($valCadBusq[2] == 3)
	imagestring($img,1,500,82,date("d-m-Y",strtotime($rowCliente['fecha_vale'])),$textColor);
else
	imagestring($img,1,500,82,date("d-m-Y",strtotime($rowCliente['fecha_presupuesto'])),$textColor);
imageline($img,10,90,602,90,$textColor);

//TEXTO "ASESOR"
imagestring($img,1,10,92,str_pad("ASESOR", 12, " ", STR_PAD_BOTH),$textColor);
//ASESOR
imagestring($img,1,80,92,$rowCliente['nombre_empleado']." ".$rowCliente['apellido_empleado'],$textColor);
imageline($img,10,100,602,100,$textColor);

//TEXTO "DATOS DEL VEHICULO"
imagestring($img,1,250,102,"DATOS DEL VEHICULO",$textColor);
imageline($img,10,110,602,110,$textColor);

//TEXTO "MARCA"
imagestring($img,1,10,112,str_pad("MARCA", 12, " ", STR_PAD_BOTH),$textColor);
//MARCA
imagestring($img,1,80,112,$rowCliente['nom_marca'],$textColor);
//TEXTO "AÑO"
imageline($img,249,110,249,140,$textColor);
imagestring($img,1,250,112,str_pad("A".utf8_decode("Ñ")."O", 12, " ", STR_PAD_BOTH),$textColor);
//AÑO
imageline($img,307,110,307,140,$textColor);
imagestring($img,1,310,112,$rowCliente['ano_uni_bas'],$textColor);
//TEXTO "MOTOR"
imageline($img,416,110,416,140,$textColor);
imagestring($img,1,420,112,str_pad("MOTOR", 12, " ", STR_PAD_BOTH),$textColor);
//MOTOR
imageline($img,506,110,506,140,$textColor);
imagestring($img,1,510,112,"--",$textColor);
imageline($img,10,120,602,120,$textColor);

//TEXTO "MODELO"
imagestring($img,1,10,122,str_pad("MODELO", 12, " ", STR_PAD_BOTH),$textColor);
//MODELO
imagestring($img,1,80,122,$rowCliente['des_modelo'],$textColor);
//TEXTO "PLACAS"
imagestring($img,1,250,122,str_pad("PLACAS", 12, " ", STR_PAD_BOTH),$textColor);
//PLACA
imagestring($img,1,310,122,$rowCliente['placa'],$textColor);
//TEXTO "FECHA DE VENTA"
imagestring($img,1,420,122,str_pad("FECHA DE VENTA", 12, " ", STR_PAD_BOTH),$textColor);
//FECHA DE VENTA
imagestring($img,1,510,122,"--",$textColor);
imageline($img,10,130,602,130,$textColor);

//TEXTO "COLOR"
imagestring($img,1,10,132,str_pad("COLOR", 12, " ", STR_PAD_BOTH),$textColor);
//COLOR
imagestring($img,1,80,132,$rowCliente['color'],$textColor);
//TEXTO "CHASIS"
imagestring($img,1,250,132,str_pad("CHASIS", 12, " ", STR_PAD_BOTH),$textColor);
//CHASIS
imagestring($img,1,310,132,$rowCliente['chasis'],$textColor);
//TEXTO "TRANSMISION"
imagestring($img,1,420,132,str_pad("TRANSMISION", 12, " ", STR_PAD_BOTH),$textColor);
//TRANSMISION
imagestring($img,1,510,132,$rowCliente['nom_transmision'],$textColor);
imageline($img,10,140,602,140,$textColor);

//ENCABEZADO LISTA
imagestring($img,1,12,142,str_pad("CODIGO", 18, " ", STR_PAD_RIGHT),$textColor);
imageline($img,98,140,98,150,$textColor);
imagestring($img,1,100,142,str_pad("DESCRIPCI".utf8_decode("Ó")."N", 32, " ", STR_PAD_RIGHT),$textColor);
imageline($img,368,140,368,150,$textColor);
imagestring($img,1,370,142,str_pad("CANTIDAD/MECANICO", 17, "-", STR_PAD_LEFT),$textColor);
imageline($img,458,140,458,150,$textColor);
imagestring($img,1,460,142,str_pad("PRECIO UNITARIO", 12, " ", STR_PAD_LEFT),$textColor);
imageline($img,541,140,541,150,$textColor);
imagestring($img,1,543,142,str_pad("PRECIO TOTAL", 12, " ", STR_PAD_LEFT),$textColor);
imageline($img,10,150,602,150,$textColor);

$i = 1;
// DETALLES DE LOS REPUESTOS
if ($valCadBusq[1] == 1){
	$queryRepuestosGenerales = sprintf("SELECT
	  sa_det_presup_articulo.cantidad,
	  sa_det_presup_articulo.precio_unitario,
	  vw_iv_articulos.codigo_articulo,
	  vw_iv_articulos.descripcion,
	  sa_det_presup_articulo.iva
	FROM
	  sa_presupuesto
	  INNER JOIN sa_det_presup_articulo ON (sa_presupuesto.id_presupuesto = sa_det_presup_articulo.id_presupuesto)
	  INNER JOIN vw_iv_articulos ON (sa_det_presup_articulo.id_articulo = vw_iv_articulos.id_articulo)
	WHERE
	  sa_presupuesto.id_presupuesto = %s AND sa_det_presup_articulo.estado_articulo <> 'DEVUELTO' AND sa_det_presup_articulo.aprobado = 1",
		valTpDato($idDocumento,"int"));
}
else{
	if($valCadBusq[2] == 3)
	{
		$queryRepuestosGenerales = sprintf("	SELECT
			sa_det_orden_articulo.cantidad,
			sa_det_orden_articulo.precio_unitario,
			vw_iv_articulos.codigo_articulo,
			vw_iv_articulos.descripcion,
			sa_det_orden_articulo.iva
			FROM
			vw_iv_articulos
			INNER JOIN sa_det_orden_articulo ON (vw_iv_articulos.id_articulo = sa_det_orden_articulo.id_articulo)
			INNER JOIN sa_orden ON (sa_det_orden_articulo.id_orden = sa_orden.id_orden)
			INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
			WHERE
			sa_vale_salida.id_vale_salida = %s AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO' AND sa_det_orden_articulo.aprobado = 1",
		valTpDato($idDocumento,"int"));
	}
	else
	{
			$queryRepuestosGenerales = sprintf("SELECT
				sa_det_orden_articulo.cantidad,
				sa_det_orden_articulo.precio_unitario,
				vw_iv_articulos.codigo_articulo,
				vw_iv_articulos.descripcion,
				sa_det_orden_articulo.iva
				FROM
				vw_iv_articulos
				INNER JOIN sa_det_orden_articulo ON (vw_iv_articulos.id_articulo = sa_det_orden_articulo.id_articulo)
				WHERE
				sa_det_orden_articulo.id_orden = %s AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO' AND sa_det_orden_articulo.aprobado = 1",
			valTpDato($idDocumento,"int"));
	 }
}
$rsOrdenDetRep = mysql_query($queryRepuestosGenerales, $conex) or die(mysql_error());
$posY = 152;
if (mysql_num_rows($rsOrdenDetRep) > 0){
	imagestring($img,1,300,$posY,"REPUESTOS",$textColor);
	$posY += 10;
	while ($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)) {
		if ($rowOrdenDetRep['iva'] == ''){
			$montoExento += $rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario'];
		}
		$anex = (strlen($rowOrdenDetRep['descripcion']) > 53) ? "..." : "";

		//$pdf->Cell($arrayTamCol[0],16,$i,'1',0,'L',false);
		//SI ES UN PRESUPUESTO OCULTA EL CODIGO DE LOS ARTICULOS
		if ($valCadBusq[1] == 1)
			$codigoRepuesto = "";
		else
			$codigoRepuesto = elimCaracter($rowOrdenDetRep['codigo_articulo'],";");

		imagestring($img,1,10,$posY,str_pad($codigoRepuesto, 18, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,100,$posY,str_pad(strtoupper(substr($rowOrdenDetRep['descripcion'],0,50).$anex), 53, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,370,$posY,str_pad($rowOrdenDetRep['cantidad'], 17, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad(number_format($rowOrdenDetRep['precio_unitario'],2,".",","), 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,543,$posY,str_pad(number_format(($rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario']),2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
		$totalRepuestos += $rowOrdenDetRep['cantidad']*$rowOrdenDetRep['precio_unitario'];
		$posY += 10;
		$i++;
	}
	imagestring($img,1,445,$posY,str_pad("SUB-TOTAL REPUESTOS", 12, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,543,$posY,str_pad(number_format($totalRepuestos,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
	$posY += 10;
}

// DETALLES DE LOS TEMPARIOS
if ($valCadBusq[1] == 1){
	$queryFactDetTemp = sprintf("SELECT
	  sa_modo.descripcion_modo,
	  sa_tempario.codigo_tempario,
	  sa_tempario.descripcion_tempario,
	  sa_det_presup_tempario.operador,
	  sa_det_presup_tempario.id_tempario,
	  sa_det_presup_tempario.precio,
	  sa_det_presup_tempario.base_ut_precio,
	  sa_det_presup_tempario.id_modo,
	  (case sa_det_presup_tempario.id_modo when '1' then sa_det_presup_tempario.ut * sa_det_presup_tempario.precio_tempario_tipo_orden / sa_det_presup_tempario.base_ut_precio when '2' then sa_det_presup_tempario.precio when '3' then sa_det_presup_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
	  (case sa_det_presup_tempario.id_modo when '1' then sa_det_presup_tempario.ut when '2' then sa_det_presup_tempario.precio when '3' then sa_det_presup_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
	  sa_det_presup_tempario.id_det_presup_tempario,
	  sa_det_presup_tempario.aprobado,
	  sa_det_presup_tempario.origen_tempario,
	  sa_det_presup_tempario.origen_tempario + 0 AS idOrigen,
	  sa_paquetes.codigo_paquete,
	  sa_paquetes.id_paquete,
	  sa_det_presup_tempario.precio_tempario_tipo_orden,
          IFNULL(sa_det_presup_tempario.id_mecanico, 0) AS id_mecanico
	FROM
	  sa_det_presup_tempario
	  INNER JOIN sa_tempario ON (sa_det_presup_tempario.id_tempario = sa_tempario.id_tempario)
	  INNER JOIN sa_modo ON (sa_det_presup_tempario.id_modo = sa_modo.id_modo)
	  LEFT OUTER JOIN sa_paquetes ON (sa_det_presup_tempario.id_paquete = sa_paquetes.id_paquete)
	WHERE
	  sa_det_presup_tempario.id_presupuesto = %s AND sa_det_presup_tempario.estado_tempario <> 'DEVUELTO'
	ORDER BY
	  sa_det_presup_tempario.id_paquete",
		valTpDato($idDocumento,"int"));
}
else{
	if($valCadBusq[2] == 3)
	{
		$queryFactDetTemp = sprintf("SELECT
  sa_modo.descripcion_modo,
  sa_tempario.codigo_tempario,
  sa_tempario.descripcion_tempario,
  sa_det_orden_tempario.operador,
  sa_det_orden_tempario.id_tempario,
  sa_det_orden_tempario.precio,
  sa_det_orden_tempario.base_ut_precio,
  sa_det_orden_tempario.id_modo,
  (case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut * sa_det_orden_tempario.precio_tempario_tipo_orden / sa_det_orden_tempario.base_ut_precio when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
  (case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
  sa_det_orden_tempario.id_det_orden_tempario,
  sa_det_orden_tempario.aprobado,
  sa_det_orden_tempario.origen_tempario,
  sa_det_orden_tempario.origen_tempario + 0 AS idOrigen,
  sa_paquetes.codigo_paquete,
  sa_paquetes.id_paquete,
  sa_det_orden_tempario.precio_tempario_tipo_orden,
  IFNULL(sa_det_orden_tempario.id_mecanico, 0) AS id_mecanico
FROM
  sa_det_orden_tempario
  INNER JOIN sa_tempario ON (sa_det_orden_tempario.id_tempario = sa_tempario.id_tempario)
  INNER JOIN sa_modo ON (sa_det_orden_tempario.id_modo = sa_modo.id_modo)
  LEFT OUTER JOIN sa_paquetes ON (sa_det_orden_tempario.id_paquete = sa_paquetes.id_paquete)
  INNER JOIN sa_orden ON (sa_orden.id_orden = sa_det_orden_tempario.id_orden)
  INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
	WHERE
	  sa_vale_salida.id_vale_salida = %s AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'
	ORDER BY
	  sa_det_orden_tempario.id_paquete",
		valTpDato($idDocumento,"int"));
	}
	else
	{
	$queryFactDetTemp = sprintf("SELECT
	  sa_modo.descripcion_modo,
	  sa_tempario.codigo_tempario,
	  sa_tempario.descripcion_tempario,
	  sa_det_orden_tempario.operador,
	  sa_det_orden_tempario.id_tempario,
	  sa_det_orden_tempario.precio,
	  sa_det_orden_tempario.base_ut_precio,
	  sa_det_orden_tempario.id_modo,
	  (case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut * sa_det_orden_tempario.precio_tempario_tipo_orden / sa_det_orden_tempario.base_ut_precio when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
	  (case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
	  sa_det_orden_tempario.id_det_orden_tempario,
	  sa_det_orden_tempario.aprobado,
	  sa_det_orden_tempario.origen_tempario,
	  sa_det_orden_tempario.origen_tempario + 0 AS idOrigen,
	  sa_paquetes.codigo_paquete,
	  sa_paquetes.id_paquete,
	  sa_det_orden_tempario.precio_tempario_tipo_orden,
          IFNULL(sa_det_orden_tempario.id_mecanico, 0) AS id_mecanico
	FROM
	  sa_det_orden_tempario
	  INNER JOIN sa_tempario ON (sa_det_orden_tempario.id_tempario = sa_tempario.id_tempario)
	  INNER JOIN sa_modo ON (sa_det_orden_tempario.id_modo = sa_modo.id_modo)
	  LEFT OUTER JOIN sa_paquetes ON (sa_det_orden_tempario.id_paquete = sa_paquetes.id_paquete)
	WHERE
	  sa_det_orden_tempario.id_orden = %s  AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'
	ORDER BY
	  sa_det_orden_tempario.id_paquete",
		valTpDato($idDocumento,"int"));
	}
}
$rsFactDetTemp = mysql_query($queryFactDetTemp, $conex) or die(mysql_error());
if (mysql_num_rows($rsFactDetTemp) > 0){
	imagestring($img,1,300,$posY,"MANOS DE OBRA",$textColor);
	$posY += 10;

	while ($rowFactDetTemp = mysql_fetch_assoc($rsFactDetTemp)) {
		$anex = (strlen($rowFactDetTemp['descripcion_tempario']) > 53) ? "..." : "";

		$caractCantTempario = ($rowFactDetTemp['id_modo'] == 1) ? number_format($rowFactDetTemp['precio_por_tipo_orden']/100,2,".",",") : number_format(1,2,".",",");//Es entre 100 o la base ut? : $rowFactDetTemp['precio_por_tipo_orden']/100

		$caracterPrecioTempario = ($rowFactDetTemp['id_modo'] == 1) ? number_format($rowFactDetTemp['precio_tempario_tipo_orden'],2,".",",") : $rowFactDetTemp['precio_por_tipo_orden'];

		$cantidad = $caractCantTempario."/MEC:".sprintf("%04s",$rowFactDetTemp['id_mecanico']);
		$precioUnit = number_format($caracterPrecioTempario,2,".",",");
		$total = number_format($rowFactDetTemp['total_por_tipo_orden'],2,".",",");

		imagestring($img,1,10,$posY,str_pad($rowFactDetTemp['codigo_tempario'], 18, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,100,$posY,str_pad(strtoupper(substr($rowFactDetTemp['descripcion_tempario'],0,50).$anex), 53, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,370,$posY,str_pad($cantidad, 17, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad($precioUnit, 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,543,$posY,str_pad($total, 12, " ", STR_PAD_LEFT),$textColor);
		$totalManoDeObra += $rowFactDetTemp['total_por_tipo_orden'];
		$posY += 10;
		$i++;
	}
	imagestring($img,1,445,$posY,str_pad("SUB-TOTAL TEMPARIOS", 12, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,543,$posY,str_pad(number_format($totalManoDeObra,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
	$posY += 10;
}
// DETALLE DE LOS TOT
if ($valCadBusq[1] == 1){
	$queryDetalleTot = sprintf("SELECT
	  sa_orden_tot.monto_subtotal,
	  sa_det_presup_tot.id_orden_tot,
   	  sa_det_presup_tot.porcentaje_tot
	FROM
	  sa_det_presup_tot
	  INNER JOIN sa_orden_tot ON (sa_det_presup_tot.id_orden_tot = sa_orden_tot.id_orden_tot)
	WHERE
	  sa_det_presup_tot.id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
}
else{
	if($valCadBusq[2] == 3)
	{
		$queryDetalleTot = sprintf("SELECT
		sa_orden_tot.monto_subtotal,
		sa_det_orden_tot.id_orden_tot,
		sa_det_orden_tot.porcentaje_tot
		FROM
		sa_det_orden_tot
		INNER JOIN sa_orden_tot ON (sa_det_orden_tot.id_orden_tot = sa_orden_tot.id_orden_tot)
		INNER JOIN sa_orden ON (sa_det_orden_tot.id_orden = sa_orden.id_orden)
		INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
		WHERE
		sa_vale_salida.id_vale_salida = %s",
		valTpDato($idDocumento,"int"));
	}
	else
	{
		$queryDetalleTot = sprintf("SELECT
		sa_orden_tot.monto_subtotal,
		sa_det_orden_tot.id_orden_tot,
		sa_det_orden_tot.porcentaje_tot
		FROM
		sa_det_orden_tot
		INNER JOIN sa_orden_tot ON (sa_det_orden_tot.id_orden_tot = sa_orden_tot.id_orden_tot)
		WHERE
		sa_det_orden_tot.id_orden = %s",
		valTpDato($idDocumento,"int"));


	}
}
$rsDetalleTot = mysql_query($queryDetalleTot) or die(mysql_error());
if (mysql_num_rows($rsDetalleTot) > 0){
	imagestring($img,1,250,$posY,"TRABAJOS OTROS TALLERES (T.O.T)",$textColor);
	$posY += 10;
	while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
		$cantidad = "1";
		$precioUnit = number_format($rowDetalleTot['monto_subtotal'] + ($rowDetalleTot['monto_subtotal'] * $rowDetalleTot['porcentaje_tot'] / 100),2,".",",");
		$total = number_format($rowDetalleTot['monto_subtotal'] + ($rowDetalleTot['monto_subtotal'] * $rowDetalleTot['porcentaje_tot'] / 100),2,".",",");

		imagestring($img,1,10,$posY,str_pad($rowDetalleTot['id_orden_tot'], 18, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,100,$posY,str_pad("T.O.T.", 53, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,370,$posY,str_pad($cantidad, 17, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad($precioUnit, 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,543,$posY,str_pad($total, 12, " ", STR_PAD_LEFT),$textColor);
		$totalTOT += $rowDetalleTot['monto_subtotal'] + ($rowDetalleTot['monto_subtotal'] * $rowDetalleTot['porcentaje_tot'] / 100);
		$posY += 10;
		$i++;
	}
	imagestring($img,1,335,$posY,str_pad("SUB-TOTAL TRABAJOS OTROS TALLERES (T.O.T)", 12, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,543,$posY,str_pad(number_format($totalTOT,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
	$posY += 10;
}

// DETALLES DE LAS NOTAS
if ($valCadBusq[1] == 1){
	$queryDetTipoDocNotas = sprintf("SELECT
	  sa_det_presup_notas.descripcion_nota,
	  sa_det_presup_notas.precio,
	  sa_det_presup_notas.id_det_presup_nota
	FROM
	  sa_presupuesto
	  INNER JOIN sa_det_presup_notas ON (sa_presupuesto.id_presupuesto = sa_det_presup_notas.id_presupuesto)
	WHERE
	  sa_presupuesto.id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
}
else{
	if($valCadBusq[2] == 3)
	{
		$queryDetTipoDocNotas = sprintf("SELECT
		sa_det_orden_notas.descripcion_nota,
		sa_det_orden_notas.precio,
		sa_det_orden_notas.id_det_orden_nota
		FROM
		sa_orden
		INNER JOIN sa_det_orden_notas ON (sa_orden.id_orden = sa_det_orden_notas.id_orden)
		INNER JOIN sa_vale_salida ON (sa_orden.id_orden = sa_vale_salida.id_orden)
		WHERE
		sa_vale_salida.id_vale_salida = %s",
		valTpDato($idDocumento,"int"));
	}
	else
	{
		$queryDetTipoDocNotas = sprintf("SELECT
		sa_det_orden_notas.descripcion_nota,
		sa_det_orden_notas.precio,
		sa_det_orden_notas.id_det_orden_nota
		FROM
		sa_det_orden_notas
		WHERE
		sa_det_orden_notas.id_orden = %s",
		valTpDato($idDocumento,"int"));
	}
}
$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas) or die(mysql_error());
if (mysql_num_rows($rsDetTipoDocNotas) > 0){
	imagestring($img,1,300,$posY,"NOTAS",$textColor);
	$posY += 10;
	while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
		$anex = (strlen($rowDetTipoDocNotas['descripcion_nota']) > 53) ? "..." : "";

		$cantidad = "1";
		$precioUnit = number_format($rowDetTipoDocNotas['precio'],2,".",",");

		$total = number_format($rowDetTipoDocNotas['precio'],2,".",",");

		imagestring($img,1,10,$posY,str_pad("N".$rowDetTipoDocNotas['id_det_presup_nota'], 18, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,100,$posY,str_pad(strtoupper(substr($rowDetTipoDocNotas['descripcion_nota'],0,50).$anex), 53, " ", STR_PAD_RIGHT),$textColor);
		imagestring($img,1,370,$posY,str_pad($cantidad, 17, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,460,$posY,str_pad($precioUnit, 15, " ", STR_PAD_LEFT),$textColor);
		imagestring($img,1,543,$posY,str_pad($total, 12, " ", STR_PAD_LEFT),$textColor);
		$totalNotas += $rowDetTipoDocNotas['precio'];
		$posY += 10;
		$i++;
	}
	imagestring($img,1,450,$posY,str_pad("SUB-TOTAL NOTAS", 12, " ", STR_PAD_LEFT),$textColor);
	imagestring($img,1,543,$posY,str_pad(number_format($totalNotas,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
	$posY += 10;
}

// DETALLES DE LOS DESCUENTOS
if ($valCadBusq[1] == 1){
	$queryDetDescuentos = sprintf("SELECT
	  sa_det_presup_descuento.porcentaje,
	  sa_det_presup_descuento.id_porcentaje_descuento
	FROM
	  sa_det_presup_descuento
	WHERE
	  sa_det_presup_descuento.id_presupuesto = %s",
	valTpDato($idDocumento,"int"));
}
else{
	$queryDetDescuentos = sprintf("SELECT
	  sa_det_orden_descuento.porcentaje,
	  sa_det_orden_descuento.id_porcentaje_descuento
	FROM
	  sa_det_orden_descuento
	WHERE
	  sa_det_orden_descuento.id_orden = %s",
		valTpDato($idDocumento,"int"));
}
$rsDetDescuentos = mysql_query($queryDetDescuentos) or die(mysql_error());
while ($rowDetDescuentos = mysql_fetch_assoc($rsDetDescuentos)) {
	if ($rowDetDescuentos['id_porcentaje_descuento'] == 1)
		$descuentoDetallesManoDeObra += $totalManoDeObra * $rowDetDescuentos['porcentaje'] / 100;
	else
		$descuentoDetallesRepuestos += $totalRepuestos * $rowDetDescuentos['porcentaje'] / 100;
}

//for (; $i <= 40; $i++){
//imagestring($img,1,10,$posY,str_pad("-", 18, " ", STR_PAD_BOTH),$textColor);
//imagestring($img,1,100,$posY,str_pad("-", 53, " ", STR_PAD_BOTH),$textColor);
//imagestring($img,1,350,$posY,str_pad("-", 21, " ", STR_PAD_BOTH),$textColor);
//imagestring($img,1,460,$posY,str_pad("-", 15, " ", STR_PAD_BOTH),$textColor);
//imagestring($img,1,543,$posY,str_pad("-", 12, " ", STR_PAD_BOTH),$textColor);
//$posY += 10;
//}

$posY = 550;
$auxY1 = $posY;
imageline($img,412,$posY,602,$posY,$textColor);
$posY += 2;
//TEXTO "SUB-TOTAL"
imagestring($img,1,415,$posY,str_pad("SUB-TOTAL:", 25, " ", STR_PAD_LEFT),$textColor);
//SUB-TOTAL
imagestring($img,1,540,$posY,str_pad(number_format($rowCliente['subtotal'],2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
$posY += 8;
imageline($img,412,$posY,602,$posY,$textColor);
$posY += 2;

//TEXTO "DESCUENTO"
imagestring($img,1,415,$posY,str_pad("DESCUENTO:", 25, " ", STR_PAD_LEFT),$textColor);
//DESCUENTO
imagestring($img,1,540,$posY,str_pad(number_format($rowCliente['subtotal_descuento'] + $descuentoDetalles,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
$posY += 8;
imageline($img,412,$posY,602,$posY,$textColor);
$posY += 2;

//TEXTO "DESCUENTO X MANO DE OBRA"
imagestring($img,1,415,$posY,str_pad("DESCUENTO X MANO DE OBRA:", 25, " ", STR_PAD_LEFT),$textColor);
//DESCUENTO
imagestring($img,1,540,$posY,str_pad(number_format($descuentoDetallesManoDeObra,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
$posY += 8;
imageline($img,412,$posY,602,$posY,$textColor);
$posY += 2;

//TEXTO "DESCUENTO X REPUESTOS"
imagestring($img,1,415,$posY,str_pad("DESCUENTO X REPUESTOS:", 25, " ", STR_PAD_LEFT),$textColor);
//DESCUENTO
imagestring($img,1,540,$posY,str_pad(number_format($descuentoDetallesRepuestos,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
$posY += 8;
imageline($img,412,$posY,602,$posY,$textColor);
$posY += 2;

//TEXTO "BASE IMPONIBLE"
imagestring($img,1,415,$posY,str_pad("BASE IMPONIBLE:", 25, " ", STR_PAD_LEFT),$textColor);
//BASE IMPONIBLE
//$baseImponible = $rowCliente['subtotal'] ;
$baseImponible = $rowCliente['subtotal'] - $rowCliente['subtotal_descuento'] - $montoExento;
imagestring($img,1,540,$posY,str_pad(number_format($baseImponible,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
$posY += 8;
imageline($img,412,$posY,602,$posY,$textColor);
$posY += 2;

//TEXTO "MONTO EXENTO"
imagestring($img,1,415,$posY,str_pad("MONTO EXENTO:", 25, " ", STR_PAD_LEFT),$textColor);
//MONTO EXENTO
imagestring($img,1,540,$posY,str_pad(number_format($montoExento,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
$posY += 8;
imageline($img,412,$posY,602,$posY,$textColor);
$posY += 2;

//TEXTO "IVA"
imagestring($img,1,415,$posY,str_pad(nombreIva($rowCliente['idIva']).":"/*"IVA:"*/, 25, " ", STR_PAD_LEFT),$textColor);
//IVA
$iva = $baseImponible * $rowCliente['iva'] / 100;
imagestring($img,1,540,$posY,str_pad(number_format($iva,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
$posY += 8;
imageline($img,412,$posY,602,$posY,$textColor);
$posY += 2;

//TEXTO "TOTAL"
imagestring($img,1,415,$posY,str_pad("TOTAL:", 25, " ", STR_PAD_LEFT),$textColor);
//TOTAL
$totalPresupuesto = $rowCliente['subtotal'] - $rowCliente['subtotal_descuento'] - $descuentoDetalles + $iva;
imagestring($img,1,540,$posY,str_pad(number_format($totalPresupuesto,2,".",","), 12, " ", STR_PAD_LEFT),$textColor);
$posY += 8;
$auxY2 = $posY;
imageline($img,412,$posY,602,$posY,$textColor);
imageline($img,412,$auxY1,412,$auxY2,$textColor);
imageline($img,540,$auxY1,540,$auxY2,$textColor);
imageline($img,602,$auxY1,602,$auxY2,$textColor);
$posY += 2;

$posY = 550;
$auxY1 = $posY;
imageline($img,10,$posY,160,$posY,$textColor);
$posY += 4;
//TEXTO "FECHA APROBACIÓN"
imagestring($img,1,12,$posY,str_pad("FECHA APROBACI".utf8_decode("Ó")."N", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 16;
imageline($img,10,$posY,160,$posY,$textColor);
$posY += 4;

//TEXTO "HORA APROBACIÓN"
imagestring($img,1,12,$posY,str_pad("HORA APROBACI".utf8_decode("Ó")."N", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 16;
imageline($img,10,$posY,160,$posY,$textColor);
$posY += 4;

//TEXTO "CLIENTE"
imagestring($img,1,12,$posY,str_pad("CLIENTE", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 16;
imageline($img,10,$posY,160,$posY,$textColor);
$posY += 4;

//TEXTO "FIRMA"
imagestring($img,1,12,$posY,str_pad("FIRMA", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 16;
imageline($img,10,$posY,160,$posY,$textColor);
$auxY2 = $posY;
imageline($img,10,$auxY1,10,$auxY2,$textColor);
imageline($img,160,$auxY1,160,$auxY2,$textColor);
imageline($img,95,$auxY1,95,$auxY2,$textColor);
$posY += 4;

//TEXTO "SERVICIO"
imagestring($img,1,100,$posY,str_pad("SERVICIO", 16, " ", STR_PAD_LEFT),$textColor);

//TEXTO "REPUESTO"
imagestring($img,1,400,$posY,str_pad("REPUESTO", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 10;

//TEXTO "NOMBRE FECHA"
imagestring($img,1,50,$posY,str_pad("NOMBRE", 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,150,$posY,str_pad("FECHA", 16, " ", STR_PAD_LEFT),$textColor);

//TEXTO "NOMBRE FECHA"
imagestring($img,1,350,$posY,str_pad("NOMBRE", 16, " ", STR_PAD_LEFT),$textColor);
imagestring($img,1,450,$posY,str_pad("FECHA", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 12;

//RECTANGULO
$auxY1 = $posY;
imageline($img,50,$posY,250,$posY,$textColor);
imageline($img,350,$posY,550,$posY,$textColor);
$posY += 12;
imageline($img,50,$posY,250,$posY,$textColor);
imageline($img,350,$posY,550,$posY,$textColor);
$auxY2 =$posY;
imageline($img,175,$auxY1,175,$auxY2,$textColor);
imageline($img,50,$auxY1,50,$auxY2,$textColor);
imageline($img,250,$auxY1,250,$auxY2,$textColor);

imageline($img,475,$auxY1,475,$auxY2,$textColor);
imageline($img,350,$auxY1,350,$auxY2,$textColor);
imageline($img,550,$auxY1,550,$auxY2,$textColor);
$posY += 4;

//TEXTO "HORA"
imagestring($img,1,450,$posY,str_pad("HORA", 16, " ", STR_PAD_LEFT),$textColor);

//TEXTO "HORA"
imagestring($img,1,450,$posY,str_pad("HORA", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 12;

//RECTANGULO
$auxY1 = $posY;
imageline($img,175,$posY,250,$posY,$textColor);
imageline($img,475,$posY,550,$posY,$textColor);
$posY += 12;
imageline($img,50,$posY,250,$posY,$textColor);
imageline($img,350,$posY,550,$posY,$textColor);
$auxY2 =$posY;
imageline($img,175,$auxY1,175,$auxY2,$textColor);
imageline($img,250,$auxY1,250,$auxY2,$textColor);

imageline($img,475,$auxY1,475,$auxY2,$textColor);
imageline($img,550,$auxY1,550,$auxY2,$textColor);
$posY += 4;

//TEXTO "FIRMA"
imagestring($img,1,50,$posY,str_pad("FIRMA", 16, " ", STR_PAD_LEFT),$textColor);

//TEXTO "FIRMA"
imagestring($img,1,350,$posY,str_pad("FIRMA", 16, " ", STR_PAD_LEFT),$textColor);
$posY += 12;

//NOMBRE EMPRESA DIRECCION EMPRESA TELEFONO FAX
imagestring($img,1,10,$posY,str_pad($rowEmpresa['nombre_empresa']." ".$rowEmpresa['direccion'], 115, " ", STR_PAD_BOTH),$textColor);
$posY += 10;
imagestring($img,1,10,$posY,str_pad("TELEFONO. ".$rowEmpresa['telefono1']." FAX. ".$rowEmpresa['fax'], 115, " ", STR_PAD_BOTH),$textColor);
$posY += 10;

if ($valCadBusq[1] == 1){
//TEXTO "PRESUPUESTO VÁLIDO POR TANTOS DÍAS"
//$dias = (strtotime($rowCliente['fecha_vencimiento']) - strtotime(date("Y-m-d",strtotime($rowCliente['fecha_presupuesto'])))/ 86400);
//imagestring($img,1,10,$posY,str_pad($texto_presupuesto_cotizacion." V".utf8_decode("Á")."LIDO POR ".($dias / 86400)." D".utf8_decode("Í")."AS", 115, " ", STR_PAD_BOTH),$textColor);

}

$r = imagepng($img,"img/tmp/orden.png");

$pdf = new PDF_AutoPrint('P','cm','LETTER');
//$pdf->AutoPrint(true);
$pdf->AddPage('P',$tamanoPaginaPixel);//$tamanoPaginaPixel

$pdf->Image("img/tmp/orden.png", 0, 0);

//LOGO EMPRESA
$pdf->Image("../".$ruta_logo, '0.2', '0.2', '4', '1.5', '','');

//CODIGO DE BARRA
$pdf->Image($ruta, 17, 0.3, '', '', '','');

$pdf->SetDisplayMode("fullwidth");
$pdf->SetDisplayMode(70,'single');

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