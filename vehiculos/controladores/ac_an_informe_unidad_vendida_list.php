<?php


function buscarUnidadVendida($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleado'],
		$frmBuscar['lstEstadoFactura'],
		$frmBuscar['lstItemFactura'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaUnidadVendida(0, "numeroControl", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstVendedor($selId = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM cj_cc_encabezadofactura fact_vent
		INNER JOIN vw_pg_empleados empleado ON (fact_vent.idVendedor = empleado.id_empleado)
	WHERE fact_vent.idDepartamentoOrigenFactura = 2
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstEmpleado\" name=\"lstEmpleado\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpleado","innerHTML",$html);
	
	return $objResponse;
}

function exportarUnidadVendida($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleado'],
		$frmBuscar['lstEstadoFactura'],
		$frmBuscar['lstItemFactura'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/an_informe_unidad_vendida_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaUnidadVendida($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = fact_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.fechaRegistroFactura BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.idVendedor LIKE %s",
			valTpDato($valCadBusq[3],"text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.anulada LIKE %s",
			valTpDato($valCadBusq[4],"text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($valCadBusq[5]) {
			case 1 : // Vehiculo
				$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_vehic2.id_factura)
				FROM cj_cc_factura_detalle_vehiculo fact_det_vehic2 WHERE fact_det_vehic2.id_factura = fact_vent.idFactura) > 0");
				break;
			case 2 : // Adicionales
				$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura)
				FROM cj_cc_factura_detalle_accesorios fact_det_acc2
					INNER JOIN an_accesorio acc ON (fact_det_acc2.id_accesorio = acc.id_accesorio)
				WHERE fact_det_acc2.id_factura = fact_vent.idFactura
					AND acc.id_tipo_accesorio IN (1)) > 0");
				break;
			case 3 : // Accesorios
				$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura)
				FROM cj_cc_factura_detalle_accesorios fact_det_acc2
					INNER JOIN an_accesorio acc ON (fact_det_acc2.id_accesorio = acc.id_accesorio)
				WHERE fact_det_acc2.id_factura = fact_vent.idFactura
					AND acc.id_tipo_accesorio IN (2)) > 0");
				break;
		}
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_vent.numeroFactura LIKE %s
		OR fact_vent.numeroControl LIKE %s
		OR numeroPedido LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT(uni_bas.nom_uni_bas,': ', modelo.nom_modelo, ' - ', vers.nom_version) LIKE %s
		OR placa LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		fact_comp.id_factura AS id_factura_compra,
		fact_vent.idFactura,
		fact_vent.fechaRegistroFactura,
		fact_vent.numeroFactura,
		fact_vent.numeroControl,
		fact_vent.numeroPedido,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT('[', uni_bas.nom_uni_bas, ']: ', marca.nom_marca, ' ', modelo.nom_modelo, ' ', vers.nom_version, ' ', ano.nom_ano) AS vehiculo,
		uni_fis.placa,
		ped_comp_det.flotilla,
		
		ano.nom_ano,
		uni_fis.serial_carroceria,
		cliente.correo,
		cliente.ciudad,
		cliente.telf,
		ped_vent.id_pedido,
		ped_vent.numeracion_pedido,
		
		(SELECT SUM(fact_vent_det_acc.cantidad * fact_vent_det_acc.precio_unitario)
		FROM cj_cc_factura_detalle_accesorios fact_vent_det_acc
		WHERE fact_vent_det_acc.id_factura = fact_vent.idFactura) AS total_neto_accesorios,
		
		(SELECT fact_vent_det_vehic.precio_unitario
		FROM cj_cc_factura_detalle_vehiculo fact_vent_det_vehic
		WHERE fact_vent_det_vehic.id_factura = fact_vent.idFactura) AS total_neto_vehiculo,
		
		(fact_vent.subtotalFactura - fact_vent.descuentoFactura) AS total_neto_factura_venta,
		(fact_vent.calculoIvaFactura + fact_vent.calculoIvaDeLujoFactura) AS total_iva_factura_venta,
		
		(IFNULL(fact_vent.subtotalFactura, 0)
			- IFNULL(fact_vent.descuentoFactura, 0)
			+ IFNULL(fact_vent.calculoIvaFactura, 0)
			+ IFNULL(fact_vent.calculoIvaDeLujoFactura, 0)
		) AS total_factura_venta,
		
		(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios
		FROM cj_cc_factura_detalle_accesorios fact_det_acc2 WHERE fact_det_acc2.id_factura = fact_vent.idFactura) AS cantidad_accesorios,
		fact_vent.anulada,
		
		(IFNULL(fact_comp.subtotal_factura, 0)
			- IFNULL(fact_comp.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(fact_compra_gasto.monto) AS total_gasto
					FROM cp_factura_gasto fact_compra_gasto
					WHERE (fact_compra_gasto.id_factura = fact_comp.id_factura)), 0)
			+ IFNULL((SELECT SUM(fact_compra_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva fact_compra_iva
					WHERE (fact_compra_iva.id_factura = fact_comp.id_factura)), 0)
		) AS total_compra
	FROM cj_cc_factura_detalle_accesorios fact_vent_det_acc
		RIGHT JOIN cj_cc_encabezadofactura fact_vent ON (fact_vent_det_acc.id_factura = fact_vent.idFactura)
		LEFT JOIN cj_cc_factura_detalle_vehiculo fact_vent_det_vehic ON (fact_vent.idFactura = fact_vent_det_vehic.id_factura)
		INNER JOIN cj_cc_cliente cliente ON (fact_vent.idCliente = cliente.id)
		LEFT JOIN an_unidad_fisica uni_fis ON (fact_vent_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
				LEFT JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
				LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
				LEFT JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
				LEFT JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
		INNER JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
		INNER JOIN an_pedido ped_vent ON (fact_vent.numeroPedido = ped_vent.id_pedido) %s", $sqlBusq);
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "6%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, $spanClienteCxC);
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "12%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "14%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "6%", $pageNum, "nom_ano", $campOrd, $tpOrd, $valBusq, $maxRows, "Año");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venta");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "2%", $pageNum, "total_neto_accesorios", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Adicionales");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "6%", $pageNum, "total_neto_vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Unidad");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "8%", $pageNum, "total_iva_factura_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "8%", $pageNum, "total_factura_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "2%", $pageNum, "correo", $campOrd, $tpOrd, $valBusq, $maxRows, "Correo");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "2%", $pageNum, "ciudad", $campOrd, $tpOrd, $valBusq, $maxRows, "Ciudad");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "2%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "Teléfono");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "5%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura Venta");
		$htmlTh .= ordenarCampo("xajax_listaUnidadVendida", "5%", $pageNum, "numeracion_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido Venta");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgPedidoModulo = "";
		if ($row['numeroPedido'] == "" || $row['numeroPedido'] == "0") {
			$imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Factura CxC\"/>";
		} else {
			$imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Factura Vehículos\"/>";
		}
			
		$imgEstatusPedido = ($row['anulada'] == "SI") ? "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>" : "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
			
		switch ($row['flotilla']) {
			case 0 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>"; break;
			case 1 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>"; break;
			default : $imgEstatusUnidadAsignacion = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$imgEstatusUnidadAsignacion."</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".utf8_encode($row['ci_cliente'])."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['nom_ano'])."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['serial_carroceria'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_neto_accesorios'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_neto_vehiculo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_iva_factura_venta'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_factura_venta'], 2, ".", ",")."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\"><a class=\"linkAzulUnderline\" href=\"mailto:".utf8_encode($row['correo'])."\">".utf8_encode($row['correo'])."</a></td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['ciudad'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['telf'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numeroFactura'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numeracion_pedido'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Factura Venta PDF\"/>",
					$row['idFactura']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
						$contFila,
						$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadVendida(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadVendida(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadVendida(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadVendida(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadVendida(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadFisica","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUnidadVendida");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"exportarUnidadVendida");
$xajax->register(XAJAX_FUNCTION,"listaUnidadVendida");
?>