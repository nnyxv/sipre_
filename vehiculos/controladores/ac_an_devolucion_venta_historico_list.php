<?php


function buscarDevolucionVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['lstAplicaLibro'],
		$frmBuscar['cbxNoCancelado'],
		$frmBuscar['cbxCancelado'],
		$frmBuscar['cbxParcialCancelado'],
		$frmBuscar['cbxAsignado'],
		$frmBuscar['lstItemFactura'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaDevolucionVenta(0, "numeroControl", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM cj_cc_notacredito nota_cred
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (nota_cred.id_empleado_vendedor = vw_pg_empleado.id_empleado)
	WHERE nota_cred.idDepartamentoNotaCredito IN (2)
	ORDER BY nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function listaDevolucionVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((fact_vent.idDepartamentoOrigenFactura IN (2)
		AND nota_cred.idDepartamentoNotaCredito IN (2)
		AND nota_cred.tipoDocumento LIKE 'FA')
	OR nota_cred.idDepartamentoNotaCredito IN (2)
		AND nota_cred.tipoDocumento LIKE 'NC')");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nota_cred.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = nota_cred.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nota_cred.fechaNotaCredito BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nota_cred.id_empleado_vendedor = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nota_cred.aplicaLibros = %s",
			valTpDato($valCadBusq[4], "boolean"));
	}
	
	if (($valCadBusq[5] != "-1" && $valCadBusq[5] != "")
	|| ($valCadBusq[6] != "-1" && $valCadBusq[6] != "")
	|| ($valCadBusq[7] != "-1" && $valCadBusq[7] != "")
	|| ($valCadBusq[8] != "-1" && $valCadBusq[8] != "")) {
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") $array[] = $valCadBusq[5];
		if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") $array[] = $valCadBusq[6];
		if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") $array[] = $valCadBusq[7];
		if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") $array[] = $valCadBusq[8];
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nota_cred.estadoNotaCredito IN (%s)",
			valTpDato(implode(",",$array), "campo"));
	}
	
	if ($valCadBusq[9] != "-1" && $valCadBusq[9] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[9] == 1) {
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios
			FROM cj_cc_factura_detalle_vehiculo fact_det_acc2 WHERE fact_det_acc2.id_factura = fact_vent.idFactura) > 0");
		} else {
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(fact_det_acc2.id_factura) AS cantidad_accesorios
			FROM cj_cc_factura_detalle_accesorios fact_det_acc2 WHERE fact_det_acc2.id_factura = fact_vent.idFactura) > 0");
		}
	}
	
	if ($valCadBusq[10] != "-1" && $valCadBusq[10] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numeracion_nota_credito LIKE %s
		OR numeroFactura LIKE %s
		OR nota_cred.numeroControl LIKE %s
		OR ped_vent.id_pedido LIKE %s
		OR ped_vent.numeracion_pedido LIKE %s
		OR pres_vent.id_presupuesto LIKE %s
		OR pres_vent.numeracion_presupuesto LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR placa LIKE %s)",
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"),
			valTpDato("%".$valCadBusq[10]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		nota_cred.idNotaCredito,
		nota_cred.fechaNotaCredito,
		nota_cred.numeracion_nota_credito,
		nota_cred.numeroControl,
		nota_cred.idDepartamentoNotaCredito AS id_modulo,
		fact_vent.idFactura,
		fact_vent.fechaRegistroFactura,
		fact_vent.numeroFactura,
		fact_vent.condicionDePago,
		ped_vent.id_pedido,
		ped_vent.numeracion_pedido,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		uni_fis.placa,
		
		(SELECT COUNT(nota_cred_det_acc2.id_nota_credito) AS cantidad_accesorios
		FROM cj_cc_nota_credito_detalle_accesorios nota_cred_det_acc2
		WHERE nota_cred_det_acc2.id_nota_credito = nota_cred.idNotaCredito) AS cantidad_accesorios,
		
		(SELECT vale_ent.id_vale_entrada FROM an_vale_entrada vale_ent
		WHERE vale_ent.id_documento = nota_cred.idNotaCredito
			AND vale_ent.tipo_vale_entrada = 3) AS id_vale,
		
		(IFNULL(nota_cred.subtotalNotaCredito, 0)
			- IFNULL(nota_cred.subtotal_descuento, 0)
			+ IFNULL(nota_cred.ivaNotaCredito, 0)
			+ IFNULL(nota_cred.ivaLujoNotaCredito, 0)
		) AS total,
		
		fact_vent.anulada,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_notacredito nota_cred
		LEFT JOIN cj_cc_nota_credito_detalle_accesorios nota_cred_det_acc ON (nota_cred.idNotaCredito = nota_cred_det_acc.id_nota_credito)
		LEFT JOIN cj_cc_nota_credito_detalle_vehiculo nota_cred_det_vehic ON (nota_cred.idNotaCredito = nota_cred_det_vehic.id_nota_credito)
		INNER JOIN cj_cc_cliente cliente ON (nota_cred.idCliente = cliente.id)
		LEFT JOIN an_unidad_fisica uni_fis ON (nota_cred_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN cj_cc_encabezadofactura fact_vent ON (nota_cred.idDocumento = fact_vent.idFactura)
		LEFT JOIN an_pedido ped_vent ON (fact_vent.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (nota_cred.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"4\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "6%", $pageNum, "fechaNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Nota Créd.");
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "6%", $pageNum, "numeracion_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota Créd");
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "6%", $pageNum, "numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "6%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "6%", $pageNum, "CONVERT(numeracion_pedido, SIGNED)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "18%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "6%", $pageNum, "condicionDePago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "8%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "8%", $pageNum, "cantidad_accesorios", $campOrd, $tpOrd, $valBusq, $maxRows, "Accesorios");
		$htmlTh .= ordenarCampo("xajax_listaDevolucionVenta", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['id_pedido'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
			
		$imgEstatusPedido = ($row['anulada'] == "SI") ? "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>" : "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
			
		switch ($row['flotilla']) {
			case 0 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>"; break;
			case 1 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>"; break;
			default : $imgEstatusUnidadAsignacion = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$imgEstatusUnidadAsignacion."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaNotaCredito']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_nota_credito']."</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['fechaRegistroFactura'] != "") ? date(spanDateFormat,strtotime($row['fechaRegistroFactura'])) : "")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_pedido']."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicionDePago'] == 1) ? "divMsjInfo" : "divMsjAlerta")."\">";
				$htmlTb .= ($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".$row['placa']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad_accesorios']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('reportes/an_devolucion_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Nota Crédito Venta PDF\"/></a>",
					$row['idNotaCredito']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['id_vale'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/iv_ajuste_inventario_pdf.php?valBusq=%s|2', 960, 550);\" src=\"../img/iconos/ico_view.png\" title=\"Vale Entrada PDF\"/>",
				$row['id_vale']);
			}
			$htmlTb .= "</td>";
			// MODIFICADO ERNESTO
			$sPar = "idobject=".$row['idNotaCredito'];
			$sPar .= "&ct=02";
			$sPar .= "&dt=01";
			$sPar .= "&cc=02";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a href=\"javascript:verVentana('../contabilidad/RepComprobantesDiariosDirecto.php?".$sPar."', 960, 550);\"><img src=\"../img/iconos/new_window.png\" title=\"Movimiento Contable\"/></a>");
			$htmlTb .= "</td>";
			// MODIFICADO ERNESTO
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"19\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDevolucionVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDevolucionVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaDevolucionVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDevolucionVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDevolucionVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"19\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaDevolucionVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarDevolucionVenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaDevolucionVenta");
?>