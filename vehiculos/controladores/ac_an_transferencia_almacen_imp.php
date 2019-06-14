<?php
function cargarTransferenciaAlmacen($idAuditoriaAlmacen) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_an_auditoria_almacen WHERE id_auditoria_almacen = %s",
		valTpDato($idAuditoriaAlmacen,"int"));
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("spnFecha","innerHTML",date("d/m/Y", strtotime($row['fecha'])));
	$objResponse->assign("spnIdUnidadFisica","innerHTML",$row['id_unidad_fisica']);
	$objResponse->assign("spnPlaca","innerHTML",$row['placa']);
	$objResponse->assign("spnAlmacenAnterior","innerHTML",$row['nom_almacen_origen']);
	$objResponse->assign("spnAlmacenDestino","innerHTML",$row['nom_almacen_destino']);
	$objResponse->assign("spnEstadoVentaAnterior","innerHTML",$row['estado_venta_origen']);
	$objResponse->assign("spnEstadoVentaDestino","innerHTML",$row['estado_venta_destino']);
	$objResponse->assign("tdElaborado","innerHTML",utf8_encode($row['nombre_empleado_elaborado']." ".$row['apellido_empleado_elaborado']));
	$objResponse->assign("tdAutorizado","innerHTML",utf8_encode($row['nombre_empleado_autorizado']." ".$row['apellido_empleado_autorizado']));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargarTransferenciaAlmacen");
?>