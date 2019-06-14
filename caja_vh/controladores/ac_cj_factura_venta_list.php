<?php


function buscar($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaPedidoVenta(0, "id_pedido", "DESC", $valBusq));
	$objResponse->loadCommands(listaPedidoVentaAlquiler(0, "numero_contrato_venta", "DESC", $valBusq));
	$objResponse->loadCommands(listaPedidoVentaAdmon(0, "numero_pedido", "DESC", $valBusq));
	
	return $objResponse;
}

function cargarPagina($idEmpresa){
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	if ($rowConfig400['valor'] == 0) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, "onchange=\"selectedOption(this.id,'".$idEmpresa."');\""));
	} else {
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa));
	}
	
	$objResponse->script("xajax_buscar(xajax.getFormValues('frmBuscar'));");
	
	$Result1 = validarAperturaCaja($idEmpresa, date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	return $objResponse;
}


function devolverContrato($idContrato){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE al_contrato_venta SET estatus_contrato_venta = 1 WHERE id_contrato_venta = %s;",
		valTpDato($idContrato, "int"));
		
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnBuscar').click();");
	$objResponse->alert('Contrato de alquiler devuelto con exito');
	
	return $objResponse;
}

function imprimirPedido($frmBuscar){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjvh_factura_venta_v_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function imprimirPedidoAlquiler($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjvh_factura_venta_al_pdf.php?valBusq=%s',890,550)", $valBusq));
	
	return $objResponse;
}

function listaPedidoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	global $spanInicial;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((an_ped_vent.estado_pedido IN (1,2,4)
		AND ((SELECT COUNT(acc_ped.id_pedido) FROM an_accesorio_pedido acc_ped
				WHERE acc_ped.id_pedido = an_ped_vent.id_pedido
					AND acc_ped.estatus_accesorio_pedido = 0) > 0
			OR (SELECT COUNT(paq_ped.id_pedido) FROM an_paquete_pedido paq_ped
				WHERE paq_ped.id_pedido = an_ped_vent.id_pedido
					AND paq_ped.estatus_paquete_pedido = 0) > 0
			OR ((SELECT COUNT(uni_fis.id_unidad_fisica) FROM an_unidad_fisica uni_fis
				WHERE uni_fis.id_unidad_fisica = an_ped_vent.id_unidad_fisica
					AND uni_fis.estado_venta = 'RESERVADO') > 0
				AND an_ped_vent.estatus_unidad_fisica IN (0))))
	OR (an_ped_vent.estado_pedido IN (1)
		AND an_ped_vent.id_factura_cxc IS NOT NULL))");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(an_ped_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = an_ped_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("an_ped_vent.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(an_ped_vent.id_pedido LIKE %s
		OR an_ped_vent.id_presupuesto LIKE %s
		OR an_ped_vent.id_cliente LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT an_ped_vent.*,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		cxc_fact.idFactura AS id_factura_reemplazo,
		cxc_fact.numeroFactura AS numero_factura_reemplazo,
		pres_vent_acc.id_presupuesto_accesorio,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS(' ', cliente.direccion, CONCAT('Edo. ', cliente.estado)) AS direccion_cliente,
		CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_contacto,
		cliente.tipo,
		cliente.ciudad,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.reputacionCliente + 0 AS id_reputacion_cliente,
		cliente.tipo_cuenta_cliente,
		clave_mov.id_clave_movimiento,
		clave_mov.clave,
		clave_mov.descripcion AS descripcion_clave_movimiento,
		IFNULL(uni_fis.id_uni_bas, pres_vent.id_uni_bas) AS id_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		uni_fis.estado_venta,
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo,
		vw_pg_empleado.telefono,
		vw_pg_empleado.celular,
		vw_pg_empleado.email,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.idmoneda, moneda_local.idmoneda) AS id_moneda,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.descripcion, moneda_local.descripcion) AS descripcion_moneda,
		IF(moneda_extranjera.idmoneda > 0, moneda_extranjera.abreviacion, moneda_local.abreviacion) AS abreviacion_moneda,
		pres_vent.id_banco_financiar,
		banco.nombreBanco,
		pres_vent.estado AS estado_presupuesto,
		an_ped_vent.estado_pedido,
		ped_financ.id_pedido_financiamiento,
		ped_financ.numeracion_pedido AS numeracion_pedido_financiamiento,
		ped_financ.estatus_pedido AS estatus_pedido_financiamiento,
		ped_financ_det.id_empleado_firma,
		adicional_contrato.nombre_agencia_seguro,
		adicional_contrato.direccion_agencia_seguro,
		adicional_contrato.ciudad_agencia_seguro,
		adicional_contrato.pais_agencia_seguro,
		adicional_contrato.telefono_agencia_seguro,
		
		IFNULL(an_ped_vent.precio_venta * (an_ped_vent.porcentaje_iva + an_ped_vent.porcentaje_impuesto_lujo) / 100, 0) AS monto_impuesto,	
		(IFNULL(an_ped_vent.precio_venta, 0)
			+ IFNULL(an_ped_vent.precio_venta * (an_ped_vent.porcentaje_iva + an_ped_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta_impuesto,
		
		(SELECT an_cxc_fact.tipo_factura FROM an_factura_venta an_cxc_fact
		WHERE an_cxc_fact.numeroPedido = an_ped_vent.id_pedido
			AND (SELECT COUNT(an_cxc_fact2.numeroPedido) FROM an_factura_venta an_cxc_fact2
				WHERE an_cxc_fact.numeroPedido = an_ped_vent.id_pedido
					AND an_cxc_fact.tipo_factura IN (1,2)) = 1) AS tipo_factura,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_pedido an_ped_vent
		INNER JOIN cj_cc_cliente cliente ON (an_ped_vent.id_cliente = cliente.id)
		INNER JOIN pg_monedas moneda_local ON (an_ped_vent.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (an_ped_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		INNER JOIN pg_clave_movimiento clave_mov ON (an_ped_vent.id_clave_movimiento = clave_mov.id_clave_movimiento)
		LEFT JOIN an_unidad_fisica uni_fis ON (an_ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
			LEFT JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		LEFT JOIN bancos banco ON (an_ped_vent.id_banco_financiar = banco.idBanco)
		LEFT JOIN an_adicionales_contrato adicional_contrato ON (an_ped_vent.id_pedido = adicional_contrato.id_pedido)
		LEFT JOIN an_presupuesto pres_vent ON (an_ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
			LEFT JOIN fi_documento ped_financ_det ON (pres_vent.id_presupuesto = ped_financ_det.id_presupuesto)
				LEFT JOIN fi_pedido ped_financ ON (ped_financ_det.id_pedido_financiamiento = ped_financ.id_pedido_financiamiento)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (an_ped_vent.asesor_ventas = vw_pg_empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (an_ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (an_ped_vent.id_factura_cxc = cxc_fact.idFactura) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "", $pageNum, "estado_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "LPAD(CONVERT(numeracion_pedido, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "LPAD(CONVERT(numeracion_presupuesto, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "id_presupuesto_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto Accesorios");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "10%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "18%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo / ".$spanSerialCarroceria." / ".$spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Empleado Creador");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "6%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Venta / ".$spanInicial);
		$htmlTh .= ordenarCampo("xajax_listaPedidoVenta", "8%", $pageNum, "total_inicial_gastos", $campOrd, $tpOrd, $valBusq, $maxRows, "Total General");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusPedido = "";
		if ($row['estado_presupuesto'] == 0 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Presupuesto Autorizado\"/>";
		} else if ($row['estado_presupuesto'] == 1 || $row['estado_presupuesto'] == "") {
			switch ($row['estado_pedido']) {
				case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Autorizado\"/>"; break;
				case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			}
		} else if ($row['estado_presupuesto'] == 2 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Presupuesto Anulado\"/>";
		} else if ($row['estado_presupuesto'] == 3 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Presupuesto Desautorizado\"/>";
		}
		
		$rowspan = (strlen($row['observaciones']) > 0) ? "rowspan=\"2\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td ".$rowspan.">".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "PD";
				$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = 2;
				$objDcto->idDocumento = $row['id_pedido'];
				$objDcto->mostrarDocumento = "verPDF";
				$aVerDcto = $objDcto->verPedido();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeracion_pedido'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">";
				$htmlTb .= "<div>".$row['numeracion_presupuesto']."</div>";
				$htmlTb .= ($row['id_pedido_financiamiento'] > 0) ? "<div><span class=\"textoNegrita_10px\">Nro. Fmto.: </span>".$row['numeracion_pedido_financiamiento']."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_presupuesto_accesorio']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['vehiculo'])."</div>";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "FA";
				$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = 2;
				$objDcto->idDocumento = $row['id_factura_reemplazo'];
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr class=\"textoNegrita_10px\">";
					$htmlTb .= "<td width=\"50%\">".utf8_encode($row['serial_carroceria'])."</td>";
					$htmlTb .= "<td width=\"50%\">".utf8_encode($row['placa'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
				$htmlTb .= ($row['numero_factura_reemplazo'] > 0) ? 
					"<div>".
						"<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo4\" width=\"100%\">".
						"<tr align=\"center\">".
							"<td height=\"25\" width=\"25\"><img src=\"../img/iconos/exclamation.png\"/></td>".
							"<td>".
								"<table>".
								"<tr align=\"right\">".
									"<td nowrap=\"nowrap\">"."Edición de la Factura Nro. "."</td>".
									"<td nowrap=\"nowrap\">".$aVerDcto."</td>".
									"<td>".$row['numero_factura_reemplazo']."</td>".
								"</tr>".
								"</table>".
							"</td>".
						"</tr>".
						"</table>".
					"</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['id_empleado'].".- ".$row['nombre_empleado'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['porcentaje_inicial'] == 100) ? "divMsjInfo" : "divMsjAlerta")."\" ".$rowspan.">";
				$htmlTb .= ($row['porcentaje_inicial'] == 100) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">";
				$htmlTb .= "<div>".$row['abreviacion_moneda'].number_format($row['precio_venta_impuesto'], 2, ".", ",")."</div>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td class=\"textoNegrita_10px\" width=\"50%\">"."(".number_format($row['porcentaje_inicial'], 2, ".", ",")."%)"."</td>";
					$htmlTb .= "<td width=\"50%\">".$row['abreviacion_moneda'].number_format($row['inicial'], 2, ".", ",")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".$row['abreviacion_moneda'].number_format($row['total_pedido'], 2, ".", ",")."</td>";
			$htmlTb .= "<td ".$rowspan.">";
			// 0 = No Aprobado, 1 = Parcialmente Pagado, 2 = Pagado, 3 = Aprobado, 4 = Atrasado
			if ((!($row['id_pedido_financiamiento'] > 0) && $row['tipo_factura'] == "")
			|| ($row['id_pedido_financiamiento'] > 0 && in_array($row['estatus_pedido_financiamiento'],array(3)) && $row['id_empleado_firma'] > 0)) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('cj_facturacion_vehiculos_form.php?id=%s', '_self');\" src=\"../img/iconos/book_next.png\" title=\"Facturar\"/>",
				$row['id_pedido']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if (strlen($rowspan) > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td colspan=\"8\">";
					$htmlTb .= ((strlen($row['observaciones']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observaciones'])."</div>" : "");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf.="<option value=\"".$nroPag."\"";
								if ($pageNum == $nroPag) {
									$htmlTf.="selected=\"selected\"";
								}
								$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPedidoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPedidoVentaAdmon($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_pedido.estado_pedido IN (1)");// 0 = Pendiente, 1 = Autorizado, 2 = Facturado, 3 = Desautorizado, 4 = Devuelta, 5 = Anulada
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_pedido.id_modulo IN (5)");// 5 = Financiamiento
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_pedido.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_pedido.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_pedido.fecha_registro BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));		
	}
		
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR cxc_pedido.numero_pedido LIKE %s
		OR cxc_pedido.observacion LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		cxc_pedido.id_pedido,
		cxc_pedido.id_empresa,
		cxc_pedido.fecha_registro,
		cxc_pedido.numero_pedido,
		cxc_pedido.id_modulo,
		vw_pg_empleado_vendedor.id_empleado AS id_empleado_vendedor,
		vw_pg_empleado_vendedor.nombre_empleado AS nombre_empleado_vendedor,
		cxc_pedido.condicion_pago,		
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_pedido.estado_pedido,
		(CASE cxc_pedido.estado_pedido
			WHEN 0 THEN 'Pendiente'
			WHEN 1 THEN 'Autorizado'
			WHEN 2 THEN 'Facturado'
			WHEN 3 THEN 'Desautorizado'
			WHEN 4 THEN 'Devuelta'
			WHEN 5 THEN 'Anulada'
		END) AS descripcion_estado_pedido,
		cxc_pedido.subtotal,
		cxc_pedido.subtotal_descuento,
		
		(IFNULL(cxc_pedido.subtotal, 0)
			- IFNULL(cxc_pedido.subtotal_descuento, 0)) AS total_neto,
			
		IFNULL((SELECT SUM(cxc_pedido_imp.subtotal_impuesto) FROM cj_cc_pedido_impuesto cxc_pedido_imp
				WHERE cxc_pedido_imp.id_pedido = cxc_pedido.id_pedido), 0) AS total_impuestos,
		
		cxc_pedido.monto_total,
		cxc_pedido.observacion,		
		vw_pg_empleado_creador.id_empleado AS id_empleado_creador,
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
				
		IFNULL((SELECT COUNT(cxc_pedido_det.id_pedido) FROM cj_cc_pedido_detalle cxc_pedido_det
						WHERE cxc_pedido_det.id_pedido = cxc_pedido.id_pedido), 0) AS cant_items
		
	FROM cj_cc_pedido cxc_pedido
		INNER JOIN cj_cc_cliente cliente ON (cxc_pedido.id_cliente = cliente.id)
		INNER JOIN vw_pg_empleados vw_pg_empleado_vendedor ON (cxc_pedido.id_vendedor = vw_pg_empleado_vendedor.id_empleado)
		INNER JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_pedido.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_pedido.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"3\" width=\"1%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "12%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "4%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "4%", $pageNum, "numero_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "25%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");		
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "4%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAdmon", "6%", $pageNum, "monto_total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Pedido");
		$htmlTh .= "<td colspan=\"10\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
			default : $imgDctoModulo = $row['id_modulo'];
		}
		
		$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
		
		switch ($row['estado_pedido']) { 
			case 0 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Pedido Pendiente\"/>"; break;
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Autorizado\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
		}
		
		$rowspan = (strlen($row['observacion']) > 0) ? "rowspan=\"2\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td ".$rowspan.">".$imgEstatusPedido."</td>";
			$htmlTb .= "<td ".$rowspan.">".$imgDctoModuloCondicion."</td>";
			$htmlTb .= "<td ".$rowspan.">".$imgDctoModulo."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"Pedido Nro: ".utf8_encode($row['numero_pedido']).". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "").">".date(spanDateFormat, strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_pedido']."</td>";
			$htmlTb .= "<td nowrap=\"nowrap\">";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicion_pago'] == 1) ? "divMsjInfo" : "divMsjAlerta")."\" ".$rowspan.">";
				$htmlTb .= ($row['condicion_pago'] == 1) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".number_format($row['monto_total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td ".$rowspan." align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('cj_facturacion_admon_form.php?id=%s','_self');\" src=\"../img/iconos/book_next.png\" title=\"Facturar\"/>",
					$row['id_pedido']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if (strlen($rowspan) > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td colspan=\"4\">";
					$htmlTb .= ((strlen($row['observacion']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion'])."</div>" : "");
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
		}		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAdmon(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAdmon(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVentaAdmon(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAdmon(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAdmon(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPedidoVentaAdmon","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);;
	
	return $objResponse;
}

function listaPedidoVentaAlquiler($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("contrato.estatus_contrato_venta IN (2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(contrato.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = contrato.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(contrato.fecha_creacion) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(contrato.numero_contrato_venta LIKE %s		
		OR presupuesto.numero_presupuesto_venta LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		contrato.id_contrato_venta,
		contrato.numero_contrato_venta,
		contrato.id_tipo_contrato,
		contrato.id_empresa,
		contrato.id_cliente,
		contrato.observacion,
		contrato.id_presupuesto_venta,
		contrato.id_unidad_fisica,
		contrato.condicion_pago,
		contrato.estatus_contrato_venta,
		contrato.fecha_creacion,
		contrato.fecha_salida,
		contrato.fecha_entrada,
		contrato.dias_contrato,
		presupuesto.numero_presupuesto_venta,
		tipo_contrato.nombre_tipo_contrato,
		empleado.nombre_empleado,
		unidad.placa,
		unidad.serial_carroceria,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		
		IF(contrato.id_cliente != contrato.id_cliente_pago,
			(SELECT CONCAT_WS(' ', cliente_pago.nombre, cliente_pago.apellido) 
				FROM cj_cc_cliente cliente_pago 
				WHERE cliente_pago.id = contrato.id_cliente_pago),
			NULL) AS nombre_cliente_pago,
		
		(contrato.subtotal - contrato.subtotal_descuento) AS total_neto,
		contrato.total_contrato AS total,
		
		(SELECT SUM(al_contrato_venta_iva.subtotal_iva) FROM al_contrato_venta_iva
		WHERE al_contrato_venta_iva.id_contrato_venta = contrato.id_contrato_venta) AS total_iva,
				
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa		
		FROM al_contrato_venta contrato
		INNER JOIN al_tipo_contrato tipo_contrato ON (contrato.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (contrato.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN vw_pg_empleados empleado ON (contrato.id_empleado_creador = empleado.id_empleado)
		INNER JOIN cj_cc_cliente cliente ON (contrato.id_cliente = cliente.id)
		INNER JOIN an_unidad_fisica unidad ON (contrato.id_unidad_fisica = unidad.id_unidad_fisica)
		LEFT JOIN al_presupuesto_venta presupuesto ON (contrato.id_presupuesto_venta = presupuesto.id_presupuesto_venta) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "15%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "4%", $pageNum, "numero_contrato_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Contrato");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "4%", $pageNum, "numero_presupuesto_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "6%", $pageNum, "nombre_tipo_contrato", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Contrato");		
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "25%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "6%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "6%", $pageNum, "fecha_salida", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Salida");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "6%", $pageNum, "fecha_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Entrada");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "1%", $pageNum, "dias_contrato", $campOrd, $tpOrd, $valBusq, $maxRows, "D&iacute;as Contrato");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "6%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedidoVentaAlquiler", "6%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Contrato");
		$htmlTh .= "<td class=\"noprint\" colspan=\"4\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusContrato = "";
		switch ($row['estatus_contrato_venta']){			
			case 1: $imgEstatusContrato = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Contrato Activo\"/>"; break;
			case 2: $imgEstatusContrato = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Contrato Cerrado\"/>"; break;
			case 3:	$imgEstatusContrato = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
			case 4:	$imgEstatusContrato = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
			case 5:	$imgEstatusContrato = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Vale de Salida\"/>"; break;
			case 6:	$imgEstatusContrato = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Vale de Entrada\"/>"; break;
			default : $imgEstatusContrato = "";
		}
		
		$clientePago = "";
		if($row['nombre_cliente_pago']){
			$clientePago = "<br><span class=\"textoNegrita_9px\">".utf8_encode($row["nombre_cliente_pago"])."</span>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusContrato."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i:s a", strtotime($row['fecha_creacion']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_contrato_venta']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_presupuesto_venta']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_contrato'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente']).$clientePago."</td>";
			$htmlTb .= "<td>".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['serial_carroceria'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i:s a", strtotime($row['fecha_salida']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i:s a", strtotime($row['fecha_entrada']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['dias_contrato']."</td>";
			$htmlTb .= "<td align=\"center\"".(($row['condicion_pago'] == 0) ? "class=\"divMsjAlerta\"" : "class=\"divMsjInfo\"").">";
				$htmlTb .= ($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarDevolverContrato(%s);\" src=\"../img/iconos/ico_return.png\" title=\"Devolver\"/>",
					$row['id_contrato_venta']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('cj_facturacion_alquiler_form.php?id=%s','_self');\" src=\"../img/iconos/book_next.png\" title=\"Facturar\"/>",
					$row['id_contrato_venta']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAlquiler(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAlquiler(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVentaAlquiler(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAlquiler(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVentaAlquiler(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"25\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPedidoVentaAlquiler","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_iva'];
		$totalFacturas += $row['total'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalFacturas, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"devolverContrato");
$xajax->register(XAJAX_FUNCTION,"imprimirPedido");
$xajax->register(XAJAX_FUNCTION,"imprimirPedidoAlquiler");
$xajax->register(XAJAX_FUNCTION,"listaPedidoVenta");
$xajax->register(XAJAX_FUNCTION,"listaPedidoVentaAdmon");
$xajax->register(XAJAX_FUNCTION,"listaPedidoVentaAlquiler");

function validarAperturaCaja($idEmpresa, $fecha) {
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM ".$apertCajaPpal." ape
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND id_empresa = %s;",
		valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$totalRowsCierreCaja = mysql_num_rows($rsCierreCaja);
	$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
	
	if ($totalRowsCierreCaja > 0) {
		return array(false, "Debe cerrar la caja del dia: ".date(spanDateFormat, strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("SELECT * FROM ".$apertCajaPpal." ape
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND id_empresa = %s;",
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$totalRowsVerificarApertura = mysql_num_rows($rsVerificarApertura);
		
		return ($totalRowsVerificarApertura > 0) ? array(true, "") : array(false, "Esta caja no tiene apertura");
	}
}
?>