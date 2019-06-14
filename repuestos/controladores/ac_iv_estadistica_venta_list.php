<?php


function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN vw_pg_empleados empleado ON (fact_vent.idVendedor = empleado.id_empleado)
	WHERE fact_vent.idDepartamentoOrigenFactura IN (0,1,2)
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function exportarEstadisticaVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("window.open('reportes/iv_estadistica_venta_excel.php?frmBuscar=".json_encode($frmBuscar)."','_self');");
	
	return $objResponse;
}

function listaEstadisticaVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0,1,2)");
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0,1,2)");
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.estatus_nota_credito IN (2)");
	
	if ($frmBuscar['lstEmpresa'] != "-1" && $frmBuscar['lstEmpresa'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.id_empresa = %s",
			valTpDato($frmBuscar['lstEmpresa'], "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.id_empresa = %s",
			valTpDato($frmBuscar['lstEmpresa'], "int"));
	}
	
	if ($frmBuscar['lstAplicaLibro'] != "-1" && $frmBuscar['lstAplicaLibro'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.aplicaLibros = %s",
			valTpDato($frmBuscar['lstAplicaLibro'], "boolean"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.aplicaLibros = %s",
			valTpDato($frmBuscar['lstAplicaLibro'], "boolean"));
	}
	
	if (($frmBuscar['cbxNoCancelado'] != "-1" && $frmBuscar['cbxNoCancelado'] != "")
	|| ($frmBuscar['cbxCancelado'] != "-1" && $frmBuscar['cbxCancelado'] != "")
	|| ($frmBuscar['cbxParcialCancelado'] != "-1" && $frmBuscar['cbxParcialCancelado'] != "")
	|| ($frmBuscar['cbxNoCanceladoNC'] != "-1" && $frmBuscar['cbxNoCanceladoNC'] != "")
	|| ($frmBuscar['cbxCanceladoNC'] != "-1" && $frmBuscar['cbxCanceladoNC'] != "")
	|| ($frmBuscar['cbxParcialCanceladoNC'] != "-1" && $frmBuscar['cbxParcialCanceladoNC'] != "")
	|| ($frmBuscar['cbxAsignadoNC'] != "-1" && $frmBuscar['cbxAsignadoNC'] != "")) {
		if ($frmBuscar['cbxNoCancelado'] != "-1" && $frmBuscar['cbxNoCancelado'] != "") $array[] = $frmBuscar['cbxNoCancelado'];
		if ($frmBuscar['cbxCancelado'] != "-1" && $frmBuscar['cbxCancelado'] != "") $array[] = $frmBuscar['cbxCancelado'];
		if ($frmBuscar['cbxParcialCancelado'] != "-1" && $frmBuscar['cbxParcialCancelado'] != "") $array[] = $frmBuscar['cbxParcialCancelado'];
		(!$array) ? $array[] = "-1": "";
	
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.estadoFactura IN (%s)",
			valTpDato(implode(",",$array), "campo"));
		
		if ($frmBuscar['cbxNoCanceladoNC'] != "-1" && $frmBuscar['cbxNoCanceladoNC'] != "") $array2[] = $frmBuscar['cbxNoCanceladoNC'];
		if ($frmBuscar['cbxCanceladoNC'] != "-1" && $frmBuscar['cbxCanceladoNC'] != "") $array2[] = $frmBuscar['cbxCanceladoNC'];
		if ($frmBuscar['cbxParcialCanceladoNC'] != "-1" && $frmBuscar['cbxParcialCanceladoNC'] != "") $array2[] = $frmBuscar['cbxParcialCanceladoNC'];
		if ($frmBuscar['cbxAsignadoNC'] != "-1" && $frmBuscar['cbxAsignadoNC'] != "") $array2[] = $frmBuscar['cbxAsignadoNC'];
		(!$array2) ? $array2[] = "-1": "";
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.estadoNotaCredito IN (%s)",
			valTpDato(implode(",",$array2), "campo"));
	}
	
	if ($frmBuscar['txtFechaDesde'] != "" && $frmBuscar['txtFechaHasta'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($frmBuscar['txtFechaDesde'])),"date"),
			valTpDato(date("Y-m-d", strtotime($frmBuscar['txtFechaHasta'])),"date"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.fechaNotaCredito BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($frmBuscar['txtFechaDesde'])),"date"),
			valTpDato(date("Y-m-d", strtotime($frmBuscar['txtFechaHasta'])),"date"));
	}
	
	if ($frmBuscar['lstEmpleadoVendedor'] != "-1" && $frmBuscar['lstEmpleadoVendedor'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.idVendedor = %s",
			valTpDato($frmBuscar['lstEmpleadoVendedor'], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("fact_vent.idVendedor = %s",
			valTpDato($frmBuscar['lstEmpleadoVendedor'], "int"));
	}
	
	if ($frmBuscar['lstClaveMovimiento'] != "-1" && $frmBuscar['lstClaveMovimiento'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
		WHERE mov.id_tipo_movimiento = 3
			AND mov.id_documento = fact_vent.idFactura) = %s",
			valTpDato($frmBuscar['lstClaveMovimiento'], "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
		WHERE mov.id_tipo_movimiento = 2
			AND mov.tipo_documento_movimiento = 2
			AND mov.id_documento = nota_cred.idNotaCredito
		LIMIT 1) = %s",
			valTpDato($frmBuscar['lstClaveMovimiento'], "int"));
	}
	
	if ($frmBuscar['txtCriterio'] != "-1" && $frmBuscar['txtCriterio'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numeroFactura LIKE %s
		OR numeroControl LIKE %s)",
			valTpDato("%".$frmBuscar['txtCriterio']."%", "text"),
			valTpDato("%".$frmBuscar['txtCriterio']."%", "text"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(nota_cred.numeracion_nota_credito LIKE %s
		OR nota_cred.numeroControl LIKE %s
		OR numeroFactura LIKE %s)",
			valTpDato("%".$frmBuscar['txtCriterio']."%", "text"),
			valTpDato("%".$frmBuscar['txtCriterio']."%", "text"),
			valTpDato("%".$frmBuscar['txtCriterio']."%", "text"));
	}
	
	$query = sprintf("SELECT
		fact_vent.fechaRegistroFactura,
		empleado.cedula AS ci_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		
		COUNT(fact_vent.idFactura) AS cantidad_facturas,
		
		SUM(IFNULL((SELECT COUNT(fact_vent_det.id_factura) FROM cj_cc_factura_detalle fact_vent_det
		WHERE fact_vent_det.id_factura = fact_vent.idFactura), 0)) AS cant_items,
		
		SUM(IFNULL((SELECT SUM(fact_vent_det.cantidad) FROM cj_cc_factura_detalle fact_vent_det
		WHERE fact_vent_det.id_factura = fact_vent.idFactura), 0)) AS cant_piezas,
		
		SUM((IFNULL(fact_vent.subtotalFactura, 0)
			- IFNULL(fact_vent.descuentoFactura, 0))) AS total_neto_factura_venta,
		
		SUM(IFNULL((SELECT SUM(fact_vent_det.cantidad * fact_vent_det.costo_compra) FROM cj_cc_factura_detalle fact_vent_det
		WHERE fact_vent_det.id_factura = fact_vent.idFactura), 0)) AS total_costo_factura_venta,
		
		(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
		WHERE mov.id_tipo_movimiento = 3
			AND mov.id_documento = fact_vent.idFactura) AS id_clave_movimiento,
		
		(SELECT clave_mov.descripcion
		FROM iv_movimiento mov
			INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento)
		WHERE mov.id_tipo_movimiento = 3
			AND mov.id_documento = fact_vent.idFactura) AS descripcion_clave_movimiento
		
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN pg_empleado empleado ON (fact_vent.idVendedor = empleado.id_empleado) %s
	GROUP BY
		fact_vent.fechaRegistroFactura,
		empleado.cedula,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido),
		(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
		WHERE mov.id_tipo_movimiento = 3
			AND mov.id_documento = fact_vent.idFactura)
	
	UNION
	
	SELECT 
		nota_cred.fechaNotaCredito AS fechaRegistroFactura,
		empleado.cedula AS ci_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		
		(-1) * COUNT(nota_cred.idNotaCredito) AS cantidad_notas_credito,
		
		(-1) * SUM(IFNULL((SELECT COUNT(nota_cred_det.id_nota_credito) FROM cj_cc_nota_credito_detalle nota_cred_det
		WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito), 0)) AS cant_items,
		
		(-1) * SUM(IFNULL((SELECT SUM(nota_cred_det.cantidad) FROM cj_cc_nota_credito_detalle nota_cred_det
		WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito), 0)) AS cant_piezas,
		
		(-1) * SUM((IFNULL(nota_cred.subtotalNotaCredito, 0)
			- IFNULL(nota_cred.subtotal_descuento, 0))) AS total_neto_devolucion_venta,
		
		(-1) * SUM(IFNULL((SELECT SUM(nota_cred_det.cantidad * nota_cred_det.costo_compra) FROM cj_cc_nota_credito_detalle nota_cred_det
		WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito), 0)) AS total_costo_devolucion_venta,
		
		(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
		WHERE mov.id_tipo_movimiento = 2
			AND mov.tipo_documento_movimiento = 2
			AND mov.id_documento = nota_cred.idNotaCredito
		LIMIT 1) AS id_clave_movimiento,
		
		(SELECT clave_mov.descripcion
		FROM iv_movimiento mov
			INNER JOIN pg_clave_movimiento clave_mov ON (mov.id_clave_movimiento = clave_mov.id_clave_movimiento)
		WHERE mov.id_tipo_movimiento = 2
			AND mov.tipo_documento_movimiento = 2
			AND mov.id_documento = nota_cred.idNotaCredito
		LIMIT 1) AS descripcion_clave_movimiento
		
	FROM cj_cc_notacredito nota_cred
		LEFT JOIN cj_cc_encabezadofactura fact_vent ON (nota_cred.idDocumento = fact_vent.idFactura)
			AND (nota_cred.tipoDocumento LIKE 'FA')
		INNER JOIN pg_empleado empleado ON (fact_vent.idVendedor = empleado.id_empleado) %s
	GROUP BY
		nota_cred.fechaNotaCredito,
		empleado.cedula,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido),
	(SELECT mov.id_clave_movimiento FROM iv_movimiento mov
	WHERE mov.id_tipo_movimiento = 2
		AND mov.tipo_documento_movimiento = 2
		AND mov.id_documento = nota_cred.idNotaCredito
	LIMIT 1)
	
	ORDER BY 1 DESC, 2 DESC", $sqlBusq, $sqlBusq2);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$existeEstadisticaVenta = false;
		if (isset($arrayEstadisticaVenta)) {
			foreach ($arrayEstadisticaVenta as $indice => $valor) {
				if ($row['fechaRegistroFactura'] == $arrayEstadisticaVenta[$indice]['fechaRegistroFactura']
				&& $row['ci_empleado'] == $arrayEstadisticaVenta[$indice]['ci_empleado']
				&& $row['id_clave_movimiento'] == $arrayEstadisticaVenta[$indice]['id_clave_movimiento']) {
					$existeEstadisticaVenta = true;
					
					$arrayEstadisticaVenta[$indice]['total_neto_factura_venta'] += $row['total_neto_factura_venta'];
					$arrayEstadisticaVenta[$indice]['total_costo_factura_venta'] += $row['total_costo_factura_venta'];
					$arrayEstadisticaVenta[$indice]['cantidad_facturas'] += $row['cantidad_facturas'];
					$arrayEstadisticaVenta[$indice]['cant_items'] += $row['cant_items'];
					$arrayEstadisticaVenta[$indice]['cant_piezas'] += $row['cant_piezas'];
				}
			}
		}
		
		if ($existeEstadisticaVenta == false) {
			$arrayEstadisticaVenta[] = array(
				"fechaRegistroFactura" => $row['fechaRegistroFactura'],
				"ci_empleado" => $row['ci_empleado'],
				"nombre_empleado" => $row['nombre_empleado'],
				"total_neto_factura_venta" => $row['total_neto_factura_venta'],
				"total_costo_factura_venta" => $row['total_costo_factura_venta'],
				"cantidad_facturas" => $row['cantidad_facturas'],
				"cant_items" => $row['cant_items'],
				"cant_piezas" => $row['cant_piezas'],
				"id_clave_movimiento" => $row['id_clave_movimiento'],
				"descripcion_clave_movimiento" => $row['descripcion_clave_movimiento']
			);
			
			$existeClaveMov = false;
			if (isset($arrayClaveMov)) {
				foreach ($arrayClaveMov as $indice => $valor) {
					if ($row['id_clave_movimiento'] == $arrayClaveMov[$indice]['id_clave_movimiento']) {
						$existeClaveMov = true;
					}
				}
			}
			
			if ($existeClaveMov == false) {
				$arrayClaveMov[] = array(
					"id_clave_movimiento" => $row['id_clave_movimiento'],
					"descripcion_clave_movimiento" => $row['descripcion_clave_movimiento']);
			}
		}
	}
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
        $htmlTh .= "<td width=\"4%\">".utf8_encode("Nro.")."</td>";
		$htmlTh .= "<td width=\"6%\">".utf8_encode("Fecha Venta")."</td>";
		$htmlTh .= "<td width=\"8%\">".utf8_encode($spanCI)."</td>";
		$htmlTh .= "<td width=\"12%\">".utf8_encode("Empleado")."</td>";
		$htmlTh .= "<td width=\"8%\">".utf8_encode("Monto Venta")."</td>";
		$htmlTh .= "<td width=\"8%\">".utf8_encode("Costo Venta")."</td>";
		$htmlTh .= "<td width=\"8%\">".utf8_encode("Cantidad Facturas (Nro Facturas emitidas)")."</td>";
		$htmlTh .= "<td width=\"8%\">".utf8_encode("Cantidad Items (Sumatoria de los renglones Facturados)")."</td>";
		$htmlTh .= "<td width=\"8%\">".utf8_encode("Cantidad Piezas (Sumatorias de las Piezas Facturadas)")."</td>";
		if (isset($arrayClaveMov)) {
			foreach($arrayClaveMov as $indiceClaveMov => $valorClaveMov) {
				$htmlTh .= "<td width=\"".(30 / count($arrayClaveMov))."%\">".utf8_encode($arrayClaveMov[$indiceClaveMov]['descripcion_clave_movimiento'])."</td>";
			}
		}
	$htmlTh .= "</tr>";
	
	if (isset($arrayEstadisticaVenta)) {
		foreach ($arrayEstadisticaVenta as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$clase = ($arrayEstadisticaVenta[$indice]['cantidad_facturas'] >= 0) ? $clase : "divMsjError";
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".$contFila."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat,strtotime($arrayEstadisticaVenta[$indice]['fechaRegistroFactura']))."</td>";
				$htmlTb .= "<td align=\"right\">".utf8_encode($arrayEstadisticaVenta[$indice]['ci_empleado'])."</td>";
				$htmlTb .= "<td>".utf8_encode($arrayEstadisticaVenta[$indice]['nombre_empleado'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($arrayEstadisticaVenta[$indice]['total_neto_factura_venta'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($arrayEstadisticaVenta[$indice]['total_costo_factura_venta'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($arrayEstadisticaVenta[$indice]['cantidad_facturas'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($arrayEstadisticaVenta[$indice]['cant_items'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($arrayEstadisticaVenta[$indice]['cant_piezas'], 2, ".", ",")."</td>";
				if (isset($arrayClaveMov)) {
					foreach($arrayClaveMov as $indiceClaveMov => $valorClaveMov) {
						$htmlTb .= "<td align=\"center\">";
						if ($arrayEstadisticaVenta[$indice]['id_clave_movimiento'] == $arrayClaveMov[$indiceClaveMov]['id_clave_movimiento']) {
							$htmlTb .= "1";
							
							$arrayTotal[8 + $indiceClaveMov] += 1;
						}
						$htmlTb .= "</td>";
					}
				}
			$htmlTb .= "</tr>";
			
			$arrayTotal[3] += $arrayEstadisticaVenta[$indice]['total_neto_factura_venta'];
			$arrayTotal[4] += $arrayEstadisticaVenta[$indice]['total_costo_factura_venta'];
			$arrayTotal[5] += $arrayEstadisticaVenta[$indice]['cantidad_facturas'];
			$arrayTotal[6] += $arrayEstadisticaVenta[$indice]['cant_items'];
			$arrayTotal[7] += $arrayEstadisticaVenta[$indice]['cant_piezas'];
		}
	}
	if (count($arrayEstadisticaVenta) > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"4\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[3],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[4],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[5],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[6],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[7],2)."</td>";
			if (isset($arrayClaveMov)) {
				foreach($arrayClaveMov as $indiceClaveMov => $valorClaveMov) {
					$htmlTb .= "<td>".number_format($arrayTotal[8 + $indiceClaveMov],2)."</td>";
				}
			}
		$htmlTb .= "</tr>";
	}
	$htmlTblFin .= "</table>";
	
	if (!(count($arrayEstadisticaVenta) > 0)) {
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaFacturaVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"exportarEstadisticaVenta");
$xajax->register(XAJAX_FUNCTION,"listaEstadisticaVenta");
?>