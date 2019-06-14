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

include("../../controladores/ac_iv_general.php");

$xajax->processRequest();

$xajax->printJavascript('../../controladores/xajax/');


$idEmpresa = $_GET['lstEmpresa'];
$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
$idClaveMovimientoSalida = $_GET['lstClaveMovimientoSalida'];
$idClaveMovimientoEntrada = $_GET['lstClaveMovimientoEntrada'];
$txtIdCliente = $_GET['lstCliente'];
$lstTipoVale = 1;
$txtFecha = date(spanDateFormat);

if (isset($idEmpresa) && $idEmpresa > 0) {
	// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
	$queryConfig12 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig12 = mysql_query($queryConfig12);
	if (!$rsConfig12) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig12 = mysql_num_rows($rsConfig12);
	$rowConfig12 = mysql_fetch_assoc($rsConfig12);
	
	$hddIdEmpleado = $_SESSION['idEmpleadoSysGts'];
	
	mysql_query("START TRANSACTION;");
	
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
	if (!$rsNumeracion) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
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
		valTpDato($hddIdEmpleado, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
	if (!$rsNumeracion) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
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
		valTpDato($hddIdEmpleado, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
	if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idMovimientoEntrada = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	
	// ARTICULOS CON EXISTENCIA Y UBICACION
	$query = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic
	WHERE vw_iv_art_emp_ubic.existencia > 0
		AND id_empresa = %s
		AND id_articulo = 482;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query,$conex);
	if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$idArticulo = $row['id_articulo'];
		$idCasilla = $row['id_casilla'];
		
		// BUSCA EL ULTIMO COSTO DEL ARTICULO
		$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos WHERE id_articulo = %s AND id_empresa = %s ORDER BY fecha_registro DESC LIMIT 1;",
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
		$rsCostoArt = mysql_query($queryCostoArt);
		if (!$rsCostoArt) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowCostoArt = mysql_fetch_assoc($rsCostoArt);
		
		$costoUnitario = ($rowConfig12['valor'] == 1) ? round($rowCostoArt['costo'],3) : round($rowCostoArt['costo_promedio'],3);
		
		$cantidadRecibida = $row['existencia'];
		$precioUnitario = $costoUnitario;
		
		if ($idArticulo > 0) {
			////////
			//////// SALIDA 
			////////
			$insertSQL = sprintf("INSERT INTO iv_vale_salida_detalle (id_vale_salida, id_articulo, id_casilla, cantidad, precio_venta, costo_compra)
			VALUE (%s, %s, %s, %s, %s, %s);",
				valTpDato($idValeSalida, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($cantidadRecibida, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
				valTpDato($idModulo, "int"),
				valTpDato($idValeSalida, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
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
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$idKardex = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// REGISTRA EL DETALLE DEL MOVIMIENTO DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idMovimientoSalida, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idKardex, "int"),
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
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			
			////////
			//////// ENTRADA 
			////////
			$insertSQL = sprintf("INSERT INTO iv_vale_entrada_detalle (id_vale_entrada, id_articulo, id_casilla, cantidad, precio_venta, costo_compra)
			VALUE (%s, %s, %s, %s, %s, %s);",
				valTpDato($idValeEntrada, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($cantidadRecibida, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), %s, SYSDATE());",
				valTpDato($idModulo, "int"),
				valTpDato($idValeEntrada, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
				valTpDato($idClaveMovimientoEntrada, "int"),
				valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
				valTpDato($cantidadRecibida, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
				valTpDato($frmTotalDcto['txtObservacion'], "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$idKardex = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// REGISTRA EL DETALLE DEL MOVIMIENTO DEL ARTICULO
			$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idMovimientoEntrada, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idKardex, "int"),
				valTpDato($cantidadRecibida, "real_inglesa"),
				valTpDato($precioUnitario, "real_inglesa"),
				valTpDato($costoUnitario, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato(0, "real_inglesa"),
				valTpDato(((0 * $costoUnitario) / 100), "real_inglesa"),
				valTpDato(0, "int"),
				valTpDato(0, "boolean"),
				valTpDato("", "int"),
				valTpDato("", "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
			/*$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); die($Result1[1]); }*/
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
			/*$Result1 = actualizarSaldos($idArticulo, $idCasilla);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarDcto($objResponse); die($Result1[1]); }*/
		}
	}
	
	mysql_query("COMMIT;");
	
	echo "<h1>AJUSTADO</h1>";
}

?>

<form method="get">
	<table>
    <tr>
    	<td>Empresa:</td>
    	<td>
        	 <?php
            $query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg <> 100 ORDER BY id_empresa_reg", $sqlBusq); 
			$rs = mysql_query($query);
			if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" class=\"inputHabilitado\" style=\"width:200px\">";
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
    	<td>Clave Mov. Salida:</td>
    	<td>
            <?php
            $query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_modulo IN (0) AND tipo IN (4) AND documento_genera IN (5) ORDER BY clave", $sqlBusq); 
			$rs = mysql_query($query);
			if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
    	<td>Clave Mov. Entrada:</td>
    	<td>
        	<?php
            $query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_modulo IN (0) AND tipo IN (2) AND documento_genera IN (6) ORDER BY clave", $sqlBusq); 
			$rs = mysql_query($query);
			if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
    	<td>Cliente:</td>
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
			ORDER BY CONCAT_Ws(' ', cliente.nombre, cliente.apellido)", $sqlBusq); 
			$rs = mysql_query($query);
			if (!$rs) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
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
    	<td colspan="4"><hr>
        	<button type="submit">Generar</button>
        </td>
    </tr>
    </table>
</form>