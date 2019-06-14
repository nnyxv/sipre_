<?php
function cargarValeSalida($idValeSalida) {
	$objResponse = new xajaxResponse();
	
	$queryValeSalida = sprintf("SELECT 
		an_vale_salida.id_vale_salida,
		an_vale_salida.numeracion_vale_salida,
		an_vale_salida.fecha,
		an_vale_salida.id_unidad_fisica,
		an_vale_salida.id_cliente,
		cj_cc_cliente.nombre,
		cj_cc_cliente.apellido,
		cj_cc_cliente.lci,
		cj_cc_cliente.ci,
		cj_cc_cliente.direccion,
		an_vale_salida.subtotal_factura,
		an_vale_salida.observacion
	FROM an_vale_salida
		INNER JOIN cj_cc_cliente ON (an_vale_salida.id_cliente = cj_cc_cliente.id) WHERE id_vale_salida = %s",
		valTpDato($idValeSalida,"int"));
	$rsValeSalida = mysql_query($queryValeSalida);
	if (!$rsValeSalida) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowValeSalida = mysql_fetch_assoc($rsValeSalida);
	
	
	$queryUnidadFisica = sprintf("SELECT
		uni_fis.id_unidad_fisica AS id_unidad_fisica,
		uni_bas.id_uni_bas AS id_uni_bas,
		uni_bas.nom_uni_bas AS nom_uni_bas,
		uni_bas.des_uni_bas AS des_uni_bas,
		almacen.id_almacen AS id_almacen,
		almacen.nom_almacen AS nom_almacen,
		almacen.des_almacen AS des_almacen,
		origen.id_origen AS id_origen,
		origen.nom_origen AS nom_origen,
		uso.id_uso AS id_uso,
		uso.nom_uso AS nom_uso,
		clase.id_clase AS id_clase,
		clase.nom_clase AS nom_clase,
		uni_fis.fecha_ingreso AS fecha_ingreso,
		cond_unidad.descripcion AS condicion_unidad,
		uni_fis.kilometraje,
		ano.nom_ano,
		uni_fis.id_color_externo1 AS id_color_externo1,
		(select an_color.nom_color AS nom_color from an_color where (an_color.id_color = uni_fis.id_color_externo1)) AS color_externo1,
		uni_fis.id_color_externo2 AS id_color_externo2,
		(select an_color.nom_color AS nom_color from an_color where (an_color.id_color = uni_fis.id_color_externo2)) AS color_externo2,
		uni_fis.id_color_interno1 AS id_color_interno1,
		(select an_color.nom_color AS nom_color from an_color where (an_color.id_color = uni_fis.id_color_interno1)) AS color_interno1,
		uni_fis.id_color_interno2 AS id_color_interno2,
		(select an_color.nom_color AS nom_color from an_color where (an_color.id_color = uni_fis.id_color_interno2)) AS color_interno2,
		uni_fis.registro_federal AS registro_federal,
		uni_fis.placa AS placa,
		uni_fis.serial_chasis AS serial_chasis,
		uni_fis.serial_carroceria AS serial_carroceria,
		uni_fis.serial_motor AS serial_motor,
		uni_fis.capacidad AS capacidad,
		uni_fis.fecha_fabricacion AS fecha_fabricacion,
		uni_fis.registro_legalizacion AS registro_legalizacion,
		moneda_precio_compra.idmoneda AS id_moneda_precio_compra,
		moneda_precio_compra.descripcion AS moneda_precio_compra,
		uni_fis.tasa_cambio_precio_compra AS tasa_cambio_precio_compra,
		moneda_costo_compra.idmoneda AS id_moneda_costo_compra,
		moneda_costo_compra.descripcion AS moneda_costo_compra,
		uni_fis.tasa_cambio_costo_compra AS tasa_cambio_costo_compra,
		uni_fis.precio_compra AS precio_compra,
		uni_fis.costo_compra AS costo_compra,
		uni_fis.iva_compra AS iva_compra,
		uni_fis.porcentaje_iva_compra AS porcentaje_iva_compra,
		uni_fis.impuesto_lujo_compra AS impuesto_lujo_compra,
		uni_fis.porcentaje_impuesto_lujo_compra AS porcentaje_impuesto_lujo_compra,
		uni_fis.propiedad AS propiedad,
		uni_fis.descuento_compra AS descuento_compra,
		uni_fis.estado_compra AS estado_compra,
		uni_fis.estado_venta AS estado_venta,
		uni_fis.fecha_pago_venta AS fecha_pago_venta,
		reg_comp_uni_fis.totalPaquete AS totalPaquete,
		marca.id_marca AS id_marca,
		marca.nom_marca AS nom_marca,
		marca.des_marca AS des_marca,
		modelo.id_modelo AS id_modelo,
		modelo.nom_modelo AS nom_modelo,
		modelo.des_modelo AS des_modelo,
		vers.id_version AS id_version,
		vers.nom_version AS nom_version,
		vers.des_version AS des_version,
		uni_bas.pvp_venta1 AS pvp_venta1
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_almacen almacen ON (uni_fis.id_almacen = almacen.id_almacen)
		LEFT JOIN an_origen origen ON (uni_fis.id_origen = origen.id_origen)
		INNER JOIN an_uso uso ON (uni_fis.id_uso = uso.id_uso)
		LEFT JOIN pg_monedas moneda_costo_compra ON (uni_fis.moneda_costo_compra = moneda_costo_compra.idmoneda)
		LEFT JOIN pg_monedas moneda_precio_compra ON (uni_fis.moneda_precio_compra = moneda_precio_compra.idmoneda)
		LEFT JOIN an_registro_compras_unidades_fisicas reg_comp_uni_fis ON (uni_fis.id_unidad_fisica = reg_comp_uni_fis.idUnidadFisica)
	 WHERE uni_fis.id_unidad_fisica = %s",
		valTpDato($rowValeSalida['id_unidad_fisica'],"int"));
	$rsUnidadFisica = mysql_query($queryUnidadFisica);
	if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica);
	
	$queryUnidadBasica = sprintf("SELECT * FROM vw_an_unidad_basica WHERE id_uni_bas = %s",
		valTpDato($rowUnidadFisica['id_uni_bas'],"int"));
	$rsUnidadBasica = mysql_query($queryUnidadBasica);
	if (!$rsUnidadBasica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica);
	
	$objResponse->assign("tdIdValeSalida","innerHTML",strtoupper($rowValeSalida['id_vale_salida']));
	$objResponse->assign("tdFechaEmision","innerHTML",date(spanDateFormat, strtotime($rowValeSalida['fecha'])));
	$objResponse->assign("tdIdPedidoCompra","innerHTML",strtoupper($rowValeSalida['id_pedido_compra']));
	$objResponse->assign("tdNombreCliente","innerHTML",strtoupper(utf8_encode($rowValeSalida['nombre']." ".$rowValeSalida['apellido'])));
	$objResponse->assign("tdRifCliente","innerHTML",strtoupper($rowValeSalida['lci']."-".$rowValeSalida['ci']));
	$objResponse->assign("tdDireccionCliente","innerHTML",strtoupper(utf8_encode($rowValeSalida['direccion'])));
	
	$objResponse->assign("tdUnidadBasica","innerHTML",strtoupper($rowUnidadBasica['nom_uni_bas']));
	
	$objResponse->assign("tdMarca","innerHTML",strtoupper($rowUnidadBasica['nom_marca']));
	$objResponse->assign("tdModelo","innerHTML",strtoupper($rowUnidadBasica['nom_modelo']));
	$objResponse->assign("tdVersion","innerHTML",htmlentities(strtoupper($rowUnidadBasica['nom_version'])));
	$objResponse->assign("tdPlaca","innerHTML",strtoupper($rowUnidadFisica['placa']));
	$objResponse->assign("tdAno","innerHTML",strtoupper($rowUnidadFisica['nom_ano']));
	$objResponse->assign("tdCarroceria","innerHTML",strtoupper($rowUnidadFisica['serial_carroceria']));
	$objResponse->assign("tdMotor","innerHTML",strtoupper($rowUnidadFisica['serial_motor']));
	$objResponse->assign("tdColor","innerHTML",strtoupper($rowUnidadFisica['color_externo1']));
	
	$objResponse->assign("tdMontoVehiculo","innerHTML",number_format($rowValeSalida['subtotal_factura'], 2, ".", ","));
	
	$objResponse->assign("tdSubTotal","innerHTML",number_format($rowValeSalida['subtotal_factura'], 2, ".", ","));
	$objResponse->assign("tdTotal","innerHTML",number_format($rowValeSalida['subtotal_factura'], 2, ".", ","));
	
	$objResponse->assign("tdObservacion","innerHTML",strtoupper(utf8_encode($rowValeSalida['observacion'])));
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargarValeSalida");
?>