<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
$raiz = "../";
require_once("../../connections/conex.php");

session_start();

require ('../../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../../controladores/xajax/');

include("../../controladores/ac_if_generar_cierre_mensual.php");
include("../../controladores/ac_iv_general.php"); 

$xajax->processRequest();

$xajax->printJavascript('../../controladores/xajax/');

mysql_query("START TRANSACTION;");

$idEmpresa = $_POST['lstEmpresa'];
$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
$idClaveMovimientoSalida = $_POST['lstClaveMovimientoSalida'];
$idClaveMovimientoEntrada = $_POST['lstClaveMovimientoEntrada'];
$txtIdCliente = $_POST['lstCliente'];
$lstTipoVale = 1;
$txtFecha = date(spanDateFormat);
$frmTotalDcto['txtObservacion'] = "AJUSTE POR DPTO. DE CONTROL Y GESTION";
$lstIncluirCl = $_POST['lstIncluirCl'];
$porcAjuste = str_replace(",","",$_POST['txtPorcentajeAjuste']); // <========== PORCENTAJE DE AUMENTO

if ($porcAjuste) {
	echo "<h2>REVALORALIZACION INVENTARIO DISPONIBLE POR LOTE (".number_format($porcAjuste, 2, ".", ",")."%)</h2>";
} else {
	echo "<h2>Debe escribir porcentaje de aumento</h2>";
}

// QUE TENGAN DISPONIBILIDAD
$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");

if($lstIncluirCl == "" || $lstIncluirCl == "0" || $lstIncluirCl == "-1") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.codigo_articulo NOT LIKE %s",
		valTpDato("CL%", "text"));
}

if ($idEmpresa != "-1" && $idEmpresa != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(vw_iv_art_almacen_costo.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vw_iv_art_almacen_costo.id_empresa))",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
}

$query = sprintf("SELECT vw_iv_art_almacen_costo.*,
	
	(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
	WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
	
	(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_almacen_costo.id_empresa)
		WHEN 1 THEN	vw_iv_art_almacen_costo.costo
		WHEN 2 THEN	vw_iv_art_almacen_costo.costo_promedio
		WHEN 3 THEN	vw_iv_art_almacen_costo.costo
	END) AS costo,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
	INNER JOIN iv_articulos art ON (vw_iv_art_almacen_costo.id_articulo = art.id_articulo)
	INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_art_almacen_costo.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
$rs = mysql_query($query);
if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>SQL: ".$query);
$totalRows = mysql_num_rows($rs);

if (isset($idEmpresa) && $idEmpresa > 0 && $totalRows > 0) {
	////////
	//////// SALIDA 
	////////
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimientoSalida, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	
	$insertSQL = sprintf("INSERT INTO iv_vale_salida (numeracion_vale_salida, id_empresa, fecha, id_documento, id_cliente, subtotal_documento, tipo_vale_salida, observacion, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActual, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFecha)),"date"),
		valTpDato($frmDcto['hddIdDcto'], "int"),
		valTpDato($txtIdCliente, "int"),
		valTpDato(0, "real_inglesa"),
		valTpDato($lstTipoVale, "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$idValeSalida = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
		
	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	VALUE (%s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
		valTpDato(4, "int"), // 1 = COMPRA, 2 = ENTRADA, 3 = VENTA, 4 = SALIDA
		valTpDato($idClaveMovimientoSalida, "int"),
		valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato($idValeSalida, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFecha)), "date"),
		valTpDato($txtIdCliente, "int"),
		valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato(1, "boolean")); // 0 = Credito, 1 = Contado
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$idMovimientoSalida = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	
	////////
	//////// ENTRADA 
	////////
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimientoEntrada, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	
	$insertSQL = sprintf("INSERT INTO iv_vale_entrada (numeracion_vale_entrada, id_empresa, fecha, id_documento, id_cliente, subtotal_documento, tipo_vale_entrada, observacion, id_empleado_creador)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActual, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFecha)),"date"),
		valTpDato($frmDcto['hddIdDcto'], "int"),
		valTpDato($txtIdCliente, "int"),
		valTpDato(0, "real_inglesa"),
		valTpDato($lstTipoVale, "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
		valTpDato($frmTotalDcto['txtObservacion'], "text"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$idValeEntrada = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
		
	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
	VALUE (%s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
		valTpDato(2, "int"), // 1 = COMPRA, 2 = ENTRADA, 3 = VENTA, 4 = SALIDA
		valTpDato($idClaveMovimientoEntrada, "int"),
		valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato($idValeEntrada, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFecha)), "date"),
		valTpDato($txtIdCliente, "int"),
		valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato(1, "boolean")); // 0 = Credito, 1 = Contado
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$idMovimientoEntrada = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
}

echo
"<table border=\"1\" style=\"font-size:12px\" width=\"100%\">".
"<tr align=\"center\">".
	"<td>"."</td>".
	"<td>"."EMPRESA"."</td>".
	"<td>"."CODIGO"."</td>".
	"<td>"."CLASIFICACION"."</td>".
	"<td>"."LOTE"."</td>".
	"<td>"."COSTO"."</td>".
	"<td>"."DISPONIBLE"."</td>".
	"<td>"."VALOR DISPONIBLE"."</td>".
	"<td>"."NUEVO LOTE"."</td>".
	"<td>"."NUEVO COSTO"."</td>".
	"<td>"."DISPONIBLE"."</td>".
	"<td>"."NUEVO VALOR DISPONIBLE"."</td>".
"</tr>";
while ($row = mysql_fetch_assoc($rs)) {
	$contFila++;
	
	$idArticulo = $row['id_articulo'];
	$idCasilla = $row['id_casilla'];
	$idArticuloAlmacenCosto = $row['id_articulo_almacen_costo'];
	$idArticuloCosto = $row['id_articulo_costo'];
	
	$costoUnitario = $row['costo'];
	
	$cantDisponible = $row['cantidad_disponible_logica']; // Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
	$subTotalDisponible = $cantDisponible * $costoUnitario;
	
	switch($row['clasificacion']) {
		case 'A' : $imgClasificacion = "<img src=\"../../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificación A\"/>"; break;
		case 'B' : $imgClasificacion = "<img src=\"../../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificación B\"/>"; break;
		case 'C' : $imgClasificacion = "<img src=\"../../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificación C\"/>"; break;
		case 'D' : $imgClasificacion = "<img src=\"../../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificación D\"/>"; break;
		case 'E' : $imgClasificacion = "<img src=\"../../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificación E\"/>"; break;
		case 'F' : $imgClasificacion = "<img src=\"../../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificación F\"/>"; break;
	}
	
	if ($idValeSalida > 0 && $idValeEntrada > 0 && $idArticulo > 0) {
		$cantidadRecibida = $cantDisponible;
		
		$precioUnitario = $row['costo'];
		$costoUnitario = $row['costo'];
		
		// AJUSTA EL COSTO COMO UN NUEVO LOTE
		$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, costo_promedio, precio_justo, id_moneda, costo_origen, id_moneda_origen, id_empleado_creador, motivo_creacion, fecha_registro, estatus)
		SELECT
			id_empresa,
			id_proveedor,
			id_articulo,
			NOW(),
			costo + ((%s * costo) / 100),
			costo + ((%s * costo) / 100),
			precio_justo,
			id_moneda,
			costo_origen,
			id_moneda_origen,
			id_empleado_creador,
			%s,
			NOW(),
			1
		FROM iv_articulos_costos art_costo
		WHERE art_costo.id_articulo_costo = %s;",
			valTpDato($porcAjuste, "real_inglesa"),
			valTpDato($porcAjuste, "real_inglesa"),
			valTpDato($frmTotalDcto['txtObservacion'], "text"),
			valTpDato($row['id_articulo_costo'], "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>SQL: ".$insertSQL);
		$idArticuloCostoNuevo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// VERIFICA SI EL LOTE TIENE LA UBICACION ASIGNADA
		$queryArtAlmCosto = sprintf("SELECT *
		FROM iv_articulos_almacen art_almacen
			INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
		WHERE art_almacen.id_articulo = %s
			AND art_almacen.id_casilla = %s
			AND art_almacen_costo.id_articulo_costo = %s;",
			valTpDato($idArticulo, "int"),
			valTpDato($idCasilla, "int"),
			valTpDato($idArticuloCostoNuevo, "int"));
		$rsArtAlmCosto = mysql_query($queryArtAlmCosto);
		if (!$rsArtAlmCosto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsArtAlm = mysql_num_rows($rsArtAlmCosto);
		$rowArtAlmCosto = mysql_fetch_assoc($rsArtAlmCosto);
		
		$idArticuloAlmacenCostoNuevo = $rowArtAlmCosto['id_articulo_almacen_costo'];
		
		if ($totalRowsArtAlm > 0) {
			// ACTUALIZA EL ESTATUS DE LA UBICACION DEL LOTE
			$updateSQL = sprintf("UPDATE iv_articulos_almacen_costo SET
				estatus = 1
			WHERE id_articulo_almacen_costo = %s;",
				valTpDato($idArticuloAlmacenCostoNuevo, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		} else {
			// LE ASIGNA EL LOTE A LA UBICACION
			$insertSQL = sprintf("INSERT INTO iv_articulos_almacen_costo (id_articulo_almacen, id_articulo_costo, estatus)
			SELECT art_almacen.id_articulo_almacen, %s, art_almacen.estatus FROM iv_articulos_almacen art_almacen
			WHERE art_almacen.id_casilla = %s
				AND art_almacen.id_articulo = %s
				AND (art_almacen.estatus = 1
					OR (art_almacen.estatus IS NULL AND (art_almacen.cantidad_inicio + art_almacen.cantidad_entrada - art_almacen.cantidad_salida) > 0));",
					valTpDato($idArticuloCostoNuevo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($idArticulo, "int"));$queryAux = $insertSQL;
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$idArticuloAlmacenCostoNuevo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
		
		// BUSCA LOS DATOS DEL LOTE NUEVO
		$queryArticuloCosto = sprintf("SELECT art_costo.*,
			prov.nombre AS nombre_proveedor,
			art.codigo_articulo
		FROM iv_articulos_costos art_costo
			INNER JOIN iv_articulos art ON (art_costo.id_articulo = art.id_articulo)
			INNER JOIN cp_proveedor prov ON (art_costo.id_proveedor = prov.id_proveedor)
		WHERE id_articulo_costo = %s;",
			valTpDato($idArticuloCostoNuevo, "int"));
		$rsArticuloCosto = mysql_query($queryArticuloCosto);
		if (!$rsArticuloCosto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowArticuloCosto = mysql_fetch_assoc($rsArticuloCosto);
		
		
		////////
		//////// SALIDA 
		////////
		$insertSQL = sprintf("INSERT INTO iv_vale_salida_detalle (id_vale_salida, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio_venta, costo_compra)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idValeSalida, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($idCasilla, "int"),
			valTpDato($idArticuloAlmacenCosto, "int"),
			valTpDato($idArticuloCosto, "int"),
			valTpDato($cantidadRecibida, "real_inglesa"),
			valTpDato($precioUnitario, "real_inglesa"),
			valTpDato($costoUnitario, "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
		$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
			valTpDato($idModulo, "int"),
			valTpDato($idValeSalida, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($idCasilla, "int"),
			valTpDato($idArticuloAlmacenCosto, "int"),
			valTpDato($idArticuloCosto, "int"),
			valTpDato(4, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
			valTpDato($idClaveMovimientoSalida, "int"),
			valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
			valTpDato($cantidadRecibida, "real_inglesa"),
			valTpDato($precioUnitario, "real_inglesa"),
			valTpDato($costoUnitario, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
			valTpDato($frmTotalDcto['txtObservacion'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$idKardex = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// REGISTRA EL DETALLE DEL MOVIMIENTO DEL ARTICULO
		$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idMovimientoSalida, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($idKardex, "int"),
			valTpDato($idArticuloAlmacenCosto, "int"),
			valTpDato($idArticuloCosto, "int"),
			valTpDato($cantidadRecibida, "real_inglesa"),
			valTpDato($precioUnitario, "real_inglesa"),
			valTpDato($costoUnitario, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(((0 * $costoUnitario) / 100), "real_inglesa"),
			valTpDato(0, "int"), // 0 = Unitario, 1 = Import
			valTpDato(0, "boolean"), // 0 = No, 1 = Si
			valTpDato("", "int"),
			valTpDato("", "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		
		$precioUnitarioNuevo = $rowArticuloCosto['costo'];
		$costoUnitarioNuevo = $rowArticuloCosto['costo'];
	
		$cantDisponibleNuevo = $row['cantidad_disponible_logica']; // Saldo - Reservada (Serv.) - Espera por Facturar - Bloqueada
		$subTotalDisponibleNuevo = $cantDisponibleNuevo * $costoUnitarioNuevo;
		
		////////
		//////// ENTRADA 
		////////
		$insertSQL = sprintf("INSERT INTO iv_vale_entrada_detalle (id_vale_entrada, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio_venta, costo_compra)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idValeEntrada, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($idCasilla, "int"),
			valTpDato($idArticuloAlmacenCostoNuevo, "int"),
			valTpDato($idArticuloCostoNuevo, "int"),
			valTpDato($cantidadRecibida, "real_inglesa"),
			valTpDato($precioUnitarioNuevo, "real_inglesa"),
			valTpDato($costoUnitarioNuevo, "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
		$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
			valTpDato($idModulo, "int"),
			valTpDato($idValeEntrada, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($idCasilla, "int"),
			valTpDato($idArticuloAlmacenCostoNuevo, "int"),
			valTpDato($idArticuloCostoNuevo, "int"),
			valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
			valTpDato($idClaveMovimientoEntrada, "int"),
			valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
			valTpDato($cantidadRecibida, "real_inglesa"),
			valTpDato($precioUnitarioNuevo, "real_inglesa"),
			valTpDato($costoUnitarioNuevo, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
			valTpDato($frmTotalDcto['txtObservacion'], "text"));//$queryMostrar = $insertSQL;
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__."<br>SQL:".$insertSQL."<br><br>".$queryAux);
		$idKardex = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// REGISTRA EL DETALLE DEL MOVIMIENTO DEL ARTICULO
		$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idMovimientoEntrada, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($idKardex, "int"),
			valTpDato($idArticuloAlmacenCostoNuevo, "int"),
			valTpDato($idArticuloCostoNuevo, "int"),
			valTpDato($cantidadRecibida, "real_inglesa"),
			valTpDato($precioUnitarioNuevo, "real_inglesa"),
			valTpDato($costoUnitarioNuevo, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(0, "real_inglesa"),
			valTpDato(((0 * $costoUnitario) / 100), "real_inglesa"),
			valTpDato(0, "int"),
			valTpDato(0, "boolean"),
			valTpDato("", "int"),
			valTpDato("", "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	echo
	"<tr>".
		"<td align=\"center\">".$contFila."</td>".
		"<td>".utf8_encode($row['nombre_empresa'])."</td>".
		"<td>".elimCaracter(htmlentities($row['codigo_articulo']),";")."</td>".
		"<td align=\"center\">".$imgClasificacion."</td>".
		"<td align=\"right\">".utf8_encode($row['id_articulo_costo'])."</td>".
		"<td align=\"right\">".number_format($costoUnitario, 2, ".", ",")."</td>".
		"<td align=\"right\">".number_format($cantDisponible, 2, ".", ",")."</td>".
		"<td align=\"right\">".number_format($subTotalDisponible, 2, ".", ",")."</td>".
		"<td align=\"right\" class=\"divMsjInfoSinBorde\">".$idArticuloCostoNuevo."</td>".
		"<td align=\"right\" class=\"divMsjInfoSinBorde\">".number_format($costoUnitarioNuevo, 2, ".", ",")."</td>".
		"<td align=\"right\" class=\"divMsjInfoSinBorde\">".number_format($cantDisponibleNuevo, 2, ".", ",")."</td>".
		"<td align=\"right\" class=\"divMsjInfoSinBorde\">".number_format($subTotalDisponibleNuevo, 2, ".", ",")."</td>".
		"<td style=\"font-size:11px\">".$queryMostrar."</td>".
	"</tr>";
	
	$arrayTotal['costo'] += $costoUnitario;
	$arrayTotal['cantidad_disponible_logica'] += $cantDisponible;
	$arrayTotal['valor_disponible_logica'] += $subTotalDisponible;
	$arrayTotal['costo_nuevo'] += $costoUnitarioNuevo;
	$arrayTotal['cantidad_disponible_logica_nuevo'] += $cantDisponibleNuevo;
	$arrayTotal['valor_disponible_logica_nuevo'] += $subTotalDisponibleNuevo;
}
echo
"<tr>".
	"<td align=\"right\" colspan=\"6\">"."TOTALES:"."</td>".
	"<td align=\"right\">".number_format($arrayTotal['cantidad_disponible_logica'], 2, ".", ",")."</td>".
	"<td align=\"right\">".number_format($arrayTotal['valor_disponible_logica'], 2, ".", ",")."</td>".
	"<td>"."</td>".
	"<td>"."</td>".
	"<td align=\"right\">".number_format($arrayTotal['cantidad_disponible_logica_nuevo'], 2, ".", ",")."</td>".
	"<td align=\"right\">".number_format($arrayTotal['valor_disponible_logica_nuevo'], 2, ".", ",")."</td>".
"</tr>".
"</table>";

if ($idValeSalida > 0) {
	// ACTUALIZA EL TOTAL DEL VALE
	$updateSQL = sprintf("UPDATE iv_vale_salida iv_vs SET
		subtotal_documento = IFNULL((SELECT SUM(iv_vs_det.cantidad * iv_vs_det.precio_venta) FROM iv_vale_salida_detalle iv_vs_det
									WHERE iv_vs_det.id_vale_salida = iv_vs.id_vale_salida),0)
	WHERE id_vale_salida = %s;",
		valTpDato($idValeSalida, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
}

if ($idValeEntrada > 0) {
	$updateSQL = sprintf("UPDATE iv_vale_entrada iv_ve SET
		subtotal_documento = IFNULL((SELECT SUM(iv_ve_det.cantidad * iv_ve_det.precio_venta) FROM iv_vale_entrada_detalle iv_ve_det
									WHERE iv_ve_det.id_vale_entrada = iv_ve.id_vale_entrada),0)
	WHERE id_vale_entrada = %s;",
		valTpDato($idValeEntrada, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA LOS SALDOS DE LAS PIEZAS QUE ESTAN EN EL VALE DE ENTRADA
	$query = sprintf("SELECT *
	FROM iv_vale_entrada iv_ve
		INNER JOIN iv_vale_entrada_detalle iv_ve_det ON (iv_ve.id_vale_entrada = iv_ve_det.id_vale_entrada)
	WHERE iv_ve.id_vale_entrada = %s;",
		valTpDato($idValeEntrada, "int"));
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$idEmpresa = $row['id_empresa'];
		$idArticulo = $row['id_articulo'];
		
		// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
		$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
		$Result1 = actualizarSaldos($idArticulo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }
		
		// ACTUALIZA EL COSTO PROMEDIO
		$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }
		
		// ACTUALIZA EL PRECIO DE VENTA
		$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { die($Result1[1]); }
	}
}

mysql_query("COMMIT;");
?>

<link rel="stylesheet" type="text/css" href="../../style/styleRafk.css"/>
<script type="text/javascript" language="javascript" src="../../js/scriptRafk.js"></script>
<script type="text/javascript" language="javascript" src="../../js/validaciones.js"></script>

<form method="POST" id="frmInventario" name="frmInventario" onsubmit="return false;">
	<table>
    <tr>
    	<td><span class="textoRojoNegrita">*</span>Empresa:</td>
    	<td>
        	 <?php
            $query = sprintf("SELECT
				vw_iv_emp_suc.id_empresa_reg,
				vw_iv_emp_suc.rif,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM vw_iv_empresas_sucursales vw_iv_emp_suc
			WHERE id_empresa_reg <> 100 ORDER BY id_empresa_reg", $sqlBusq); 
			$rs = mysql_query($query);
			if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" class=\"inputHabilitado\" style=\"width:99%\">";
				$html .= "<option value=\"-1\">[ Seleccione ]</option>";
			while ($row = mysql_fetch_assoc($rs)) {
				$selected = ($selId == $row['id_empresa_reg']) ? "selected=\"selected\"" : "";
				
				$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".($row['rif'].") ".$row['nombre_empresa'])."</option>";
			}
			$html .= "</select>";
			echo $html;
			?>
        </td>
    </tr>
    <tr>
    	<td><span class="textoRojoNegrita">*</span>Clave Mov. Salida:</td>
    	<td>
            <?php
            $query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_modulo IN (0) AND tipo IN (4) AND documento_genera IN (5) ORDER BY clave", $sqlBusq); 
			$rs = mysql_query($query);
			if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$html = "<select id=\"lstClaveMovimientoSalida\" name=\"lstClaveMovimientoSalida\" class=\"inputHabilitado\" style=\"width:200px\">";
				$html .= "<option value=\"-1\">[ Seleccione ]</option>";
			while ($row = mysql_fetch_assoc($rs)) {
				$selected = ($selId == $row['id_clave_movimiento']) ? "selected=\"selected\"" : "";
				
				$html .= "<option ".$selected." value=\"".$row['id_clave_movimiento']."\">".($row['clave'].") ".$row['descripcion'])."</option>";
			}
			$html .= "</select>";
			echo $html;
			?>
        </td>
    	<td><span class="textoRojoNegrita">*</span>Clave Mov. Entrada:</td>
    	<td>
        	<?php
            $query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_modulo IN (0) AND tipo IN (2) AND documento_genera IN (6) ORDER BY clave", $sqlBusq); 
			$rs = mysql_query($query);
			if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$html = "<select id=\"lstClaveMovimientoEntrada\" name=\"lstClaveMovimientoEntrada\" class=\"inputHabilitado\" style=\"width:200px\">";
				$html .= "<option value=\"-1\">[ Seleccione ]</option>";
			while ($row = mysql_fetch_assoc($rs)) {
				$selected = ($selId == $row['id_clave_movimiento']) ? "selected=\"selected\"" : "";
				
				$html .= "<option ".$selected." value=\"".$row['id_clave_movimiento']."\">".($row['clave'].") ".$row['descripcion'])."</option>";
			}
			$html .= "</select>";
			echo $html;
			?>
        </td>
    </tr>
    <tr>
    	<td><span class="textoRojoNegrita">*</span>Cliente:</td>
    	<td>
			<?php
            $query = sprintf("SELECT
				cliente_emp.id_empresa,
				cliente.id,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cliente.credito
			FROM cj_cc_cliente cliente
				INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
			WHERE status = 'Activo'
				AND (
					cliente.ci IN (SELECT vw_iv_emp_suc.rif FROM vw_iv_empresas_sucursales vw_iv_emp_suc) OR
					CONCAT_WS('-', cliente.lci, cliente.ci) IN (SELECT vw_iv_emp_suc.rif FROM vw_iv_empresas_sucursales vw_iv_emp_suc) OR					
					CONCAT_WS(' ', cliente.lci, cliente.ci) IN (SELECT vw_iv_emp_suc.rif FROM vw_iv_empresas_sucursales vw_iv_emp_suc) OR
					CONCAT_WS('', cliente.lci, cliente.ci) IN (SELECT vw_iv_emp_suc.rif FROM vw_iv_empresas_sucursales vw_iv_emp_suc)
				)
			ORDER BY CONCAT_WS(' ', cliente.nombre, cliente.apellido)", $sqlBusq); 
			$rs = mysql_query($query);
			if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$html = "<select id=\"lstCliente\" name=\"lstCliente\" class=\"inputHabilitado\" style=\"width:200px\">";
				$html .= "<option value=\"-1\">[ Seleccione ]</option>";
			while ($row = mysql_fetch_assoc($rs)) {
				$selected = ($selId == $row['id']) ? "selected=\"selected\"" : "";
				
				$html .= "<option ".$selected." value=\"".$row['id']."\">".($row['nombre_cliente']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['ci_cliente'])."</option>";
			}
			$html .= "</select>";
			echo $html;
			?>
        </td>
    </tr>
    <tr>
    	<td><span class="textoRojoNegrita">*</span>Incluir CL:</td>
    	<td>
        	<select id="lstIncluirCl" name="lstIncluirCl" class="inputHabilitado">
	            <option value="-1">[ Seleccione ]</option>
            	<option value="0">NO</option>
				<option value="1">SI</option>
            </select>
        </td>
    </tr>
    <tr>
    	<td><span class="textoRojoNegrita">*</span>Porcentaje Ajuste:</td>
    	<td>
        	<input type="text" id="txtPorcentajeAjuste" name="txtPorcentajeAjuste" onblur="setFormatoRafk(this,2);" onkeypress="return validarSoloNumerosReales(event);" value="" class="inputHabilitado"/>
        </td>    
    </tr>
    <tr>
    	<td colspan="4"><hr>
        	<button type="button" id="btnGenerar" onclick="validarFrmInventario();">Generar</button>
        </td>
    </tr>
    </table>
</form>

<script>

function validarFrmInventario(){	
	if (validarCampo('lstEmpresa','t','lista') == true
	&& validarCampo('lstClaveMovimientoSalida','t','lista') == true
	&& validarCampo('lstClaveMovimientoEntrada','t','lista') == true
	&& validarCampo('lstCliente','t','lista') == true
	&& validarCampo('lstIncluirCl','t','listaExceptCero') == true
	&& validarCampo('txtPorcentajeAjuste','t','') == true) {
		if (confirm('¿Desea aplicar el porcentaje de ajuste: '+byId('txtPorcentajeAjuste').value+'?') == true) {
			byId('btnGenerar').disabled = true;
			byId('frmInventario').submit();
		}
	} else {
		validarCampo('lstEmpresa','t','lista');
		validarCampo('lstClaveMovimientoSalida','t','lista');
		validarCampo('lstClaveMovimientoEntrada','t','lista');
		validarCampo('lstCliente','t','lista');
		validarCampo('lstIncluirCl','t','listaExceptCero');
		validarCampo('txtPorcentajeAjuste','t','');
		
		alert("Los campos señalados en rojo son requeridos");
		return false;
	}
}

</script>