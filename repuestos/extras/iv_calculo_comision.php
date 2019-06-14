<?php
require_once("../../connections/conex.php");

if (isset($_POST['hddAccionComision']) && $_POST['hddAccionComision'] == 1) {
	$idEmpresa = $_POST['lstEmpresa'];
	$idEmpleado = $_POST['lstEmpleado'];
	$factRepuesto = $_POST['cbxDctoRepuesto'];
	$factServicio = $_POST['cbxDctoServicio'];
	$fechaDesde = $_POST['txtFechaDesde'];
	$fechaHasta = $_POST['txtFechaHasta'];
	$idFactura = $_POST['lstFactura'];
	$porcentajeComision = $_POST['txtPorcentaje'];
	
	mysql_query("START TRANSACTION;");
	
	if ($_POST['cbxTipoDctoFact'] != "") {
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("id_empresa = %s",
				valTpDato($idEmpresa, "int"));
		}
		
		if ($_POST['lstTipoEmpleado'] == "2") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("idVendedor = %s",
				valTpDato($idEmpleado, "int"));
		}
		
		if ($_POST['cbxMetodo'] == 1) {
			if ($fechaDesde != "-1" && $fechaDesde != ""
			&& $fechaHasta != "-1" && $fechaHasta != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("fechaRegistroFactura BETWEEN %s AND %s",
					valTpDato(date("Y-m-d",strtotime($fechaDesde)), "date"),
					valTpDato(date("Y-m-d",strtotime($fechaHasta)), "date"));
			}
			
			if ($factRepuesto != "-1" && $factRepuesto != ""
			&& $factServicio != "-1" && $factServicio != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
				$sqlBusq .= $cond.sprintf("idDepartamentoOrigenFactura = %s
					OR idDepartamentoOrigenFactura = %s)",
					valTpDato($factRepuesto, "int"),
					valTpDato($factServicio, "int"));
			} else if ($factRepuesto != "-1" && $factRepuesto != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("idDepartamentoOrigenFactura = %s",
					valTpDato($factRepuesto, "int"));
			} else if ($factServicio != "-1" && $factServicio != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("idDepartamentoOrigenFactura = %s",
					valTpDato($factServicio, "int"));
			}
		} else {
			if ($idFactura != "-1" && $idFactura != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("idFactura = %s",
					valTpDato($idFactura, "int"));
			}
		}
		
		$queryDcto = sprintf("SELECT * FROM cj_cc_encabezadofactura %s", $sqlBusq);
		
		$rsDcto = mysql_query($queryDcto, $conex);
		if ($transaction == true && !$rsDcto) return mysql_error()."\n\nLine: ".__LINE__;
		
		while ($rowDcto = mysql_fetch_assoc($rsDcto)) {
			$idDocumento = $rowDcto['idFactura'];
			
			$idModulo = $rowDcto['idDepartamentoOrigenFactura'];
			
			$subtotalFact = floatval($rowDcto['subtotalFactura']);
			$descuentoFact = floatval($rowDcto['descuentoFactura']);
			$totalFact = $subtotalFact - $descuentoFact;
			if ($subtotalFact > 0) {
				$porcDescuentoFact = ($descuentoFact*100)/$subtotalFact;
			} else {
				$porcDescuentoFact = 0;
			}
			
			$tipoComision = 1; // SOBRE PRECIO DE VENTA
			$baseComision = floatval($totalFact);
			
			$montoComision = floatval(($porcentajeComision*$baseComision)/100);
			
			/* GUARDA LOS DATOS DE LA COMISION */
			$sqlComision = sprintf("INSERT INTO iv_comision (id_empleado, id_factura, venta_bruta, monto_descuento, monto_comision, tipo_comision, porcentaje_comision, activa, tipo_documento, id_modulo)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idEmpleado, "int"),
				valTpDato($idDocumento, "int"),
				valTpDato($subtotalFact, "double"),
				valTpDato($descuentoFact, "double"),
				valTpDato($montoComision, "double"),
				valTpDato($tipoComision, "int"),
				valTpDato($porcentajeComision, "double"),
				valTpDato(1, "int"),
				valTpDato("FA", "text"),
				valTpDato($idModulo, "int")); // 0 = Repuestos, 1 = Servicios
			$Result1 = mysql_query($sqlComision, $conex);
			if ($transaction == true && !$Result1) return mysql_error()."\n\nLine: ".__LINE__;
			$idComision = mysql_insert_id();
			
			/* DETALLE DEL DOCUMENTO PARA CALCULAR LAS COMISIONES*/
			$queryDetalle = sprintf("SELECT * FROM cj_cc_factura_detalle WHERE id_factura = %s;",
				valTpDato($idDocumento, "int"));
			$rsDetalle = mysql_query($queryDetalle, $conex);
			if ($transaction == true && !$rsDetalle) return mysql_error()."\n\nLine: ".__LINE__;
			while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
				$idArticulo = $rowDetalle['id_articulo'];
				
				$tipoComision = 1; // SOBRE PRECIO DE VENTA
				$monto = floatval($rowDetalle['precio_unitario']);
				$monto = floatval($monto * $rowDetalle['cantidad']);
				
				$descuentoArt = ($porcDescuentoFact * $monto) / 100;
				$monto = $monto - $descuentoArt;
				
				$montoComision = floatval(($porcentajeComision * $monto) / 100);
				
				// GENERANDO LA SQL DE LA PRIMER COMISION
				$sqlComisionDet = sprintf("INSERT INTO iv_comision_detalle (id_comision, id_articulo, cantidad, costo_compra, precio_venta, monto_comision, porcentaje_comision) VALUE (%s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idComision, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($rowDetalle['cantidad'], "double"),
					valTpDato($rowDetalle['costo_compra'], "double"),
					valTpDato($rowDetalle['precio_unitario'], "double"),
					valTpDato($montoComision, "double"),
					valTpDato($porcentajeComision, "double"));
				$Result1 = mysql_query($sqlComisionDet, $conex);
				if ($transaction == true && !$Result1) return mysql_error()."\n\nLine: ".__LINE__;
			}
			
			$arrayComision[] = $sqlComision;
		}
		
		$arrayComision[] = "////////////////////////////////////////////////////////////////////////////////";
	}
	
	////////// NOTA DE CREDITO //////////
	if ($_POST['cbxTipoDctoNotaCred'] != "") {
		$sqlBusq = "";
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("id_empresa = %s",
				valTpDato($idEmpresa, "int"));
		}
		
		if ($_POST['lstTipoEmpleado'] == "2") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(SELECT idVendedor FROM cj_cc_encabezadofactura
			WHERE idFactura = cj_cc_notacredito.idDocumento) = %s",
				valTpDato($idEmpleado, "int"));
		}
		
		if ($_POST['cbxMetodo'] == 1) {
			if ($fechaDesde != "-1" && $fechaDesde != ""
			&& $fechaHasta != "-1" && $fechaHasta != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("fechaNotaCredito BETWEEN %s AND %s",
					valTpDato(date("Y-m-d",strtotime($fechaDesde)), "date"),
					valTpDato(date("Y-m-d",strtotime($fechaHasta)), "date"));
			}
			
			if ($factRepuesto != "-1" && $factRepuesto != ""
			&& $factServicio != "-1" && $factServicio != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
				$sqlBusq .= $cond.sprintf("idDepartamentoNotaCredito = %s
					OR idDepartamentoNotaCredito = %s)",
					valTpDato($factRepuesto, "int"),
					valTpDato($factServicio, "int"));
			} else if ($factRepuesto != "-1" && $factRepuesto != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("idDepartamentoNotaCredito = %s",
					valTpDato($factRepuesto, "int"));
			} else if ($factServicio != "-1" && $factServicio != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("idDepartamentoNotaCredito = %s",
					valTpDato($factServicio, "int"));
			}
		} else {
			if ($idFactura != "-1" && $idFactura != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("idDocumento = %s",
					valTpDato($idFactura, "int"));
			}
		}
		
		$queryDctoNC = sprintf("SELECT * FROM cj_cc_notacredito %s", $sqlBusq);
		
		$rsDctoNC = mysql_query($queryDctoNC, $conex);
		if ($transaction == true && !$rsDctoNC) return mysql_error()."\n\nLine: ".__LINE__;
		
		while ($rowDctoNC = mysql_fetch_assoc($rsDctoNC)) {
			$idDocumento = $rowDctoNC['idDocumento'];
			$idNotaCredito = $rowDctoNC['idNotaCredito'];
			
			$idModulo = $rowDctoNC['idDepartamentoNotaCredito'];
			
			$subtotalNC = floatval($rowDctoNC['subtotalNotaCredito']);
			$descuentoNC = floatval($rowDctoNC['subtotal_descuento']);
			$totalNC = $subtotalNC - $descuentoNC;
			if ($subtotalNC > 0) {
				$porcDescuentoNC = ($descuentoNC*100)/$subtotalNC;
			} else {
				$porcDescuentoNC = 0;
			}
			
			$tipoComision = 1; // SOBRE PRECIO DE VENTA
			$baseComision = floatval($totalNC);
			
			$montoComision = floatval(($porcentajeComision*$baseComision)/100);
			
			/* GUARDA LOS DATOS DE LA COMISION */
			$sqlComision = sprintf("INSERT INTO iv_comision (id_empleado, id_factura, id_nota_credito, venta_bruta, monto_descuento, monto_comision, tipo_comision, porcentaje_comision, activa, tipo_documento, id_modulo)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idEmpleado, "int"),
				valTpDato($idDocumento, "int"),
				valTpDato($idNotaCredito, "int"),
				valTpDato($subtotalNC, "double"),
				valTpDato($descuentoNC, "double"),
				valTpDato($montoComision, "double"),
				valTpDato($tipoComision, "int"),
				valTpDato($porcentajeComision, "double"),
				valTpDato(1, "int"),
				valTpDato("NC", "text"),
				valTpDato($idModulo, "int")); // 0 = Repuestos, 1 = Servicios
			$Result1 = mysql_query($sqlComision, $conex);
			if ($transaction == true && !$Result1) return mysql_error()."\n\nLine: ".__LINE__;
			$idComision = mysql_insert_id();
			
			/* DETALLE DEL DOCUMENTO PARA CALCULAR LAS COMISIONES*/
			$queryDetalleNC = sprintf("SELECT * FROM cj_cc_nota_credito_detalle WHERE id_nota_credito = %s;",
				valTpDato($idNotaCredito, "int"));
			$rsDetalleNC = mysql_query($queryDetalleNC, $conex);
			if ($transaction == true && !$rsDetalleNC) return mysql_error()."\n\nLine: ".__LINE__;
			while ($rowDetalleNC = mysql_fetch_assoc($rsDetalleNC)) {
				$idArticulo = $rowDetalleNC['id_articulo'];
				
				$tipoComision = 1; // SOBRE PRECIO DE VENTA
				$monto = floatval($rowDetalleNC['precio_unitario']);
				$monto = floatval($monto * $rowDetalleNC['cantidad']);
				
				$descuentoArt = ($porcDescuentoNC * $monto) / 100;
				$monto = $monto - $descuentoArt;
				
				$montoComision = floatval(($porcentajeComision * $monto) / 100);
				
				// GENERANDO LA SQL DE LA PRIMER COMISION
				$sqlComisionDet = sprintf("INSERT INTO iv_comision_detalle (id_comision, id_articulo, cantidad, costo_compra, precio_venta, monto_comision, porcentaje_comision) VALUE (%s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idComision, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($rowDetalleNC['cantidad'], "double"),
					valTpDato($rowDetalleNC['costo_compra'], "double"),
					valTpDato($rowDetalleNC['precio_unitario'], "double"),
					valTpDato($montoComision, "double"),
					valTpDato($porcentajeComision, "double"));
				$Result1 = mysql_query($sqlComisionDet, $conex);
				if ($transaction == true && !$Result1) return mysql_error()."\n\nLine: ".__LINE__;
			}
			
			$sqlComision = sprintf("UPDATE iv_comision SET 
				id_nota_credito = %s
			WHERE id_empleado = %s
				AND id_factura = %s
				AND tipo_documento = %s;",
				valTpDato($idNotaCredito, "int"),
				valTpDato($idEmpleado, "int"),
				valTpDato($idDocumento, "int"),
				valTpDato("FA", "text")); // 0 = Repuestos, 1 = Servicios
			$Result1 = mysql_query($sqlComision, $conex);
			if ($transaction == true && !$Result1) return mysql_error()."\n\nLine: ".__LINE__;
			
			$arrayComision[] = $sqlComision;
		}
	}
	
	mysql_query("COMMIT;");
}

/*define('precioventa',1);
define('precioutilidad',2);
define('costocompra',3);
define('totalventa',4);

$idDocumentoVenta = $_GET['idDocumentoVenta'];
$idEmpleado = $_GET['idEmpleado'];

function calcular_comision($idDocumento, $idEmpleado, $debug = false, $transaction = true){
	global $conex;
	
	if ($transaction) {
		mysql_query("START TRANSACTION;");
	}
	
	$queryDcto = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s",
		valTpDato($idDocumento, "int"));
	$rsDcto = mysql_query($queryDcto, $conex);
	if ($transaction == true && !$rsDcto) return mysql_error()."\n\nLine: ".__LINE__;
	$rowDcto = mysql_fetch_assoc($rsDcto);
	
	/* DATOS DE COMISION DEL EMPLEADO A QUIEN SE LE SACARAN LAS COMISIONES */
	/*$queryEmpleado = sprintf("SELECT
		pg_empleado.id_empleado,
		pg_comision_cargo.tipo_comision as tipo,
		pg_comision_cargo.porcentaje_venta as pv,
		pg_comision_cargo.porcentaje_costo as pc,
		pg_comision_cargo.porcentaje_utilidad as pu,
		pg_comision_cargo.porcentaje_totalventa as ptv
	FROM pg_comision_cargo
		INNER JOIN pg_empleado ON (pg_comision_cargo.id_cargo_departamento = pg_empleado.id_cargo_departamento)
	WHERE id_empleado = %s",
		valTpDato($idEmpleado, "int"));
	$rsEmpleado = mysql_query($queryEmpleado, $conex);
	if ($transaction == true && !$rsEmpleado) return mysql_error()."\n\nLine: ".__LINE__;
		
	while ($rowEmpleado = mysql_fetch_assoc($rsEmpleado)) {
		$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura WHERE idFactura = %s;",
			valTpDato($idDocumento, "int"));
		$rsFact = mysql_query($queryFact, $conex);
		if ($transaction == true && !$rsFact) return mysql_error()."\n\nLine: ".__LINE__;
		$rowFact = mysql_fetch_assoc($rsFact);
		
		$subtotalFact = floatval($rowFact['subtotalFactura']);
		$descuentoFact = floatval($rowFact['descuentoFactura']);
		$totalFact = $subtotalFact - $descuentoFact;
		$porcDescuentoFact = ($descuentoFact*100)/$subtotalFact;
		
		$tipoComision = 1; // SOBRE PRECIO DE VENTA
		$baseComision = floatval($totalFact);
		$porcentajeComision = $rowEmpleado['pv'];
		
		$montoComision = floatval(($porcentajeComision*$baseComision)/100);
		
		/* GUARDA LOS DATOS DE LA COMISION */
		/*$sqlComision = sprintf("INSERT INTO iv_comision (id_empleado, id_factura, venta_bruta, monto_descuento, monto_comision, tipo_comision, porcentaje_comision, activa, tipo_documento, id_modulo)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpleado, "int"),
			valTpDato($idDocumento, "int"),
			valTpDato($subtotalFact, "double"),
			valTpDato($descuentoFact, "double"),
			valTpDato($montoComision, "double"),
			valTpDato($tipoComision, "int"),
			valTpDato($porcentajeComision, "double"),
			valTpDato(1, "int"),
			valTpDato("FA", "text"),
			valTpDato(0, "int")); // 0 = Repuestos, 1 = Servicios
		$Result1 = mysql_query($sqlComision, $conex);
		if ($transaction == true && !$Result1) return mysql_error()."\n\nLine: ".__LINE__;
		$idComision = mysql_insert_id();
		
		echo $sqlComision."<br>";
		
		/* DETALLE DEL DOCUMENTO PARA CALCULAR LAS COMISIONES*/
		/*$queryDetalle = sprintf("SELECT * FROM cj_cc_factura_detalle WHERE id_factura = %s;",
			valTpDato($idDocumento, "int"));
		$rsDetalle = mysql_query($queryDetalle, $conex);
		if ($transaction == true && !$rsDetalle) return mysql_error()."\n\nLine: ".__LINE__;
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
			
			$tipoComision = 1; // SOBRE PRECIO DE VENTA
			$monto = floatval($rowDetalle['precio_unitario']);
			$monto = floatval($monto*$rowDetalle['cantidad']);
			$porcentajeComision = $rowEmpleado['pv'];
			
			$descuentoArt = ($porcDescuentoFact*$monto)/100;
			$monto = $monto - $descuentoArt;
			
			$montoComision = floatval(($porcentajeComision*$monto)/100);
			
			// GENERANDO LA SQL DE LA PRIMER COMISION
			/*$sqlComisionDet = sprintf("INSERT INTO iv_comision_detalle (id_comision, id_articulo, cantidad, costo_compra, precio_venta, monto_comision, porcentaje_comision) VALUE (%s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idComision, "int"),
				valTpDato($rowDetalle['id_articulo'], "int"),
				valTpDato($rowDetalle['cantidad'], "double"),
				valTpDato($rowDetalle['costo_compra'], "double"),
				valTpDato($rowDetalle['precio_unitario'], "double"),
				valTpDato($montoComision, "double"),
				valTpDato($porcentajeComision, "double"));
			$Result1 = mysql_query($sqlComisionDet, $conex);
			if ($transaction == true && !$Result1) return mysql_error()."\n\nLine: ".__LINE__;
		}
	}
	
	if ($transaction) {
		mysql_query("COMMIT;");
	}
	
	return true;
}

///////////* CALCULO COMISIONES INDIVIDUAL *///////////
/*echo "Documento de Venta: ".$idDocumentoVenta;
echo "Empleado: ".$idEmpleado;*/

/* CALCULO DE LAS COMISIONES */
/*$Result1 = calcular_comision($idDocumentoVenta, $idEmpleado, false, true);
if ($Result1 != true) { echo $Result1; exit; }*/




///////////* CALCULO COMISIONES DEL JEFE DE REPUESTOS Y GERENTE DE OPERACIONES *///////////
/*$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura fact
WHERE fact.fechaRegistroFactura <= %s
	AND fact.idDepartamentoOrigenFactura = 0
	AND (SELECT count(fact_det.id_factura) FROM cj_cc_factura_detalle fact_det
		WHERE fact_det.id_factura = fact.idFactura);",
	valTpDato(date("Y-m-d", strtotime("30-06-2010")), "date"));
//echo $queryFact;
$rsFact = mysql_query($queryFact, $conex);
if ($transaction == true && !$rsFact) return mysql_error()."\n\nLine: ".__LINE__;
while ($rowFact = mysql_fetch_assoc($rsFact)) {
	echo $rowFact['numeroFactura']."<br>";
	$idDocumentoVenta = $rowFact['idFactura'];
	
	echo "Documento de Venta: ".$idDocumentoVenta." // ";
	echo "Empleado: ".$idEmpleado;*/

	/* CALCULO DE LAS COMISIONES */
	/*$Result1 = calcular_comision($idDocumentoVenta, $idEmpleado, false, true);
	if ($Result1 != true) { echo $Result1; exit; }
}*/

$queryEmpleado = sprintf("SELECT * FROM vw_pg_empleados ORDER BY nombre_empleado");
$rsEmpleado = mysql_query($queryEmpleado, $conex);
if (!$rsEmpleado) { echo mysql_error()."\n\nLine: ".__LINE__; return; }

$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa <> 100 ORDER BY nombre_empresa");
$rsEmpresa = mysql_query($queryEmpresa, $conex);
if (!$rsEmpresa) { echo mysql_error()."\n\nLine: ".__LINE__; return; }

$queryFact = sprintf("SELECT * FROM cj_cc_encabezadofactura
WHERE idDepartamentoOrigenFactura = 0
	OR idDepartamentoOrigenFactura = 1
ORDER BY numeroFactura");
$rsFact = mysql_query($queryFact, $conex);
if (!$rsFact) { echo mysql_error()."\n\nLine: ".__LINE__; return; }
?>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="../../style/styleRafk.css">
    
	<script type="text/javascript" language="javascript" src="../../js/mootools.v1.11.js"></script>
    <script type="text/javascript" language="javascript" src="../../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../../js/calendar-green.css"/> 
    <script type="text/javascript" language="javascript" src="../../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../../js/calendar-setup.js"></script>
        
    <script>
	function validarFormComision() {
		if (validarCampo('lstEmpresa','t','lista') == true
		&& validarCampo('lstTipoEmpleado','t','lista') == true
		&& validarCampo('lstEmpleado','t','lista') == true
		&& validarCampo('txtPorcentaje','t','monto') == true
		) {
			if ($('cbxMetodoFecha').checked == true) {
				if ($('cbxDctoRepuesto').checked == true
				|| $('cbxDctoServicio').checked == true) {
					if (validarCampo('txtFechaDesde','t','') == true
					&& validarCampo('txtFechaHasta','t','') == true
					) {
						return true;
					} else {
						validarCampo('txtFechaDesde','t','');
						validarCampo('txtFechaHasta','t','');
						
						alert("Los campos señalados en rojo son requeridos");
						return false;
					}
				} else {
					alert("Los campos señalados en rojo son requeridos");
					return false;
				}
			} else {
				if (validarCampo('lstFactura','t','lista') == true) {
					return true;
				} else {
					validarCampo('lstFactura','t','lista');
					
					alert("Los campos señalados en rojo son requeridos");
					return false;
				}
			}
		} else {
			validarCampo('lstEmpresa','t','lista');
			validarCampo('lstTipoEmpleado','t','lista');
			validarCampo('lstEmpleado','t','lista');
			validarCampo('txtPorcentaje','t','monto');
			
			if ($('cbxMetodoFecha').checked == true) {
				if (!(validarCampo('txtFechaDesde','t','') == true
				&& validarCampo('txtFechaHasta','t','') == true)
				) {
					validarCampo('txtFechaDesde','t','');
					validarCampo('txtFechaHasta','t','');
				}
				validarCampo('lstFactura','','');
			} else {
				if (!(validarCampo('lstFactura','t','lista') == true)) {
					validarCampo('lstFactura','t','lista');
				}
				validarCampo('txtFechaDesde','','');
				validarCampo('txtFechaHasta','','');
			}
			
			
			alert("Los campos señalados en rojo son requeridos");
			return false;
		}
	}
	
	function cambiarMetodo(value) {
		$('cbxDctoRepuesto').disabled = true;
		$('cbxDctoServicio').disabled = true;
		$('imgFechaDesde').style.visibility = 'hidden';
		$('imgFechaHasta').style.visibility = 'hidden';
		$('lstFactura').disabled = true;
		
		if (value == 1) {
			$('cbxDctoRepuesto').disabled = false;
			$('cbxDctoServicio').disabled = false;
			$('imgFechaDesde').style.visibility = '';
			$('imgFechaHasta').style.visibility = '';
		} else {
			$('lstFactura').disabled = false;
		}
	}
	</script>
</head>

<body class="bodyVehiculos">
<div id="divGeneralVehiculos">
	<div id="divInfo" class="print">
    <form id="frmCalculoComision" name="frmCalculoComision" onSubmit="return validarFormComision();" method="post">
        <table align="center" border="0">
        <tr>
            <td align="right" class="tituloCampo" width="120">Empresa:</td>
            <td>
                <select id="lstEmpresa" name="lstEmpresa">
                    <option>[ Seleccione ]</option>
                <?php while ($rowEmpresa = mysql_fetch_assoc($rsEmpresa)) {
                        $nombreSucursal = "";
                        if ($rowEmpresa['id_empresa_padre_suc'] > 0)
                            $nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";
                ?>
                    <option value="<?php echo $rowEmpresa['id_empresa_reg']; ?>"><?php echo $rowEmpresa['nombre_empresa'].$nombreSucursal; ?></option>
                <?php } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td align="right" class="tituloCampo">Empleado:</td>
            <td>
                <select id="lstEmpleado" name="lstEmpleado">
                    <option>[ Seleccione ]</option>
                <?php while ($rowEmpleado = mysql_fetch_assoc($rsEmpleado)) { ?>
                    <option value="<?php echo $rowEmpleado['id_empleado']; ?>"><?php echo strtoupper($rowEmpleado['nombre_empleado']); ?></option>
                <?php } ?>
                </select>
            </td>
        </tr>
        <tr>
        	<td align="right" class="tituloCampo">Tipo Empleado:</td>
            <td>
            	<select id="lstTipoEmpleado" name="lstTipoEmpleado">
                	<option value="-1">[ Seleccione ]</option>
                    <option value="1">Otro</option>
                    <option value="2">Vendedor</option>
                </select>
            </td>
        </tr>
        <tr>
        	<td align="right" class="tituloCampo">Tipo Documento:</td>
            <td>
                <label><input type="checkbox" id="cbxTipoDctoFact" name="cbxTipoDctoFact" value="0" /> Factura</label>
                &nbsp;/&nbsp;
                <label><input type="checkbox" id="cbxTipoDctoNotaCred" name="cbxTipoDctoNotaCred" value="1" /> Nota Crédito</label>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table>
                <tr>
                    <td><input type="radio" id="cbxMetodoFecha" name="cbxMetodo" onClick="cambiarMetodo(this.value);" value="1" checked="checked"/></td>
                    <td>
                    <fieldset><legend class="legend">Por Fecha</legend>
                        <table width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="80">Módulo:</td>
                            <td>
                                <label><input type="checkbox" id="cbxDctoRepuesto" name="cbxDctoRepuesto" value="0" /> Repuestos</label>
                                &nbsp;/&nbsp;
                                <label><input type="checkbox" id="cbxDctoServicio" name="cbxDctoServicio" value="1" /> Servicios</label>
                            </td>
                        </tr>
                        <tr>
                            <td align="right" class="tituloCampo">Período:</td>
                            <td>
                                <table>
                                <tr>
                                    <td>Desde:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" id="txtFechaDesde" name="txtFechaDesde" size="10" readonly="readonly" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../../img/iconos/ico_date.png" id="imgFechaDesde" name="imgFechaDesde" class="puntero noprint"/>
                                        <script type="text/javascript">
                                        Calendar.setup({
                                            inputField : "txtFechaDesde",
                                            ifFormat : "%d-%m-%Y",
                                            button : "imgFechaDesde"
                                        });
                                        </script>
                                    </div>
                                    </td>
                                    <td>Hasta:</td>
                                    <td>
                                    <div style="float:left">
                                        <input type="text" id="txtFechaHasta" name="txtFechaHasta" size="10" readonly="readonly" style="text-align:center"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero noprint"/>
                                        <script type="text/javascript">
                                        Calendar.setup({
                                        inputField : "txtFechaHasta",
                                        ifFormat : "%d-%m-%Y",
                                        button : "imgFechaHasta"
                                        });
                                        </script>
                                    </div>
                                    </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                <tr>
                    <td><input type="radio" id="cbxMetodoFactura" name="cbxMetodo" onClick="cambiarMetodo(this.value);" value="2"/></td>
                    <td>
                    <fieldset><legend class="legend">Por Factura</legend>
                        <table width="100%">
                        <tr>
                            <td align="right" class="tituloCampo" width="80">Nro. Factura:</td>
                            <td>
                                <select id="lstFactura" name="lstFactura">
                                    <option>[ Seleccione ]</option>
                                <?php while ($rowFact = mysql_fetch_assoc($rsFact)) { ?>
                                    <option value="<?php echo $rowFact['idFactura']; ?>"><?php echo $rowFact['numeroFactura']; ?></option>
                                <?php } ?>
                                </select>
                            </td>
                        </tr>
                        </table>
                    </fieldset>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right" class="tituloCampo">Porcentaje:</td>
            <td>
                <input type="text" id="txtPorcentaje" name="txtPorcentaje">
            </td>
        </tr>
        <tr>
            <td align="right" colspan="2">
                <input type="hidden" id="hddAccionComision" name="hddAccionComision" value="1"/>
                <button type="submit">Aceptar</button>
            </td>
        </tr>
        </table>
    </form>
    <?php
    if (isset($arrayComision)) {
        foreach ($arrayComision as $indice => $valor) {
            echo "(".$indice.") ".$valor."<br><br>";
        }
    }
    ?>
	</div>
</div>
</body>
</html>
<script>
cambiarMetodo(1);
</script>