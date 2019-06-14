<?php


function anularPresupuesto($idPresupuesto, $frmListaPresupuesto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_presupuesto_venta_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// ANULA EL PRESUPUESTO
	$updateSQL = sprintf("UPDATE an_presupuesto SET
		estado = 2
	WHERE id_presupuesto = %s;",
		valTpDato($idPresupuesto, "int")); // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPresupuesto(
		$frmListaPresupuesto['pageNum'],
		$frmListaPresupuesto['campOrd'],
		$frmListaPresupuesto['tpOrd'],
		$frmListaPresupuesto['valBusq']));
	
	return $objResponse;
}

function autorizarPresupuesto($idPresupuesto, $frmListaPresupuesto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_presupuesto_venta_list","desautorizar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE an_presupuesto SET
		estado = 0
	WHERE id_presupuesto = %s;",
		valTpDato($idPresupuesto, "int")); // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPresupuesto(
		$frmListaPresupuesto['pageNum'],
		$frmListaPresupuesto['campOrd'],
		$frmListaPresupuesto['tpOrd'],
		$frmListaPresupuesto['valBusq']));
	
	return $objResponse;
}

function buscarPresupuesto($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstatusPedido'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPresupuesto(0, "id_presupuesto", "DESC", $valBusq));
	
	return $objResponse;
}

function desautorizarPresupuesto($idPresupuesto, $frmListaPresupuesto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_presupuesto_venta_list","desautorizar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE an_presupuesto SET
		estado = 3
	WHERE id_presupuesto = %s;",
		valTpDato($idPresupuesto, "int")); // 0 = Pendiente, 1 = Pedido, 2 = Anulado, 3 = Desautorizado
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaPresupuesto(
		$frmListaPresupuesto['pageNum'],
		$frmListaPresupuesto['campOrd'],
		$frmListaPresupuesto['tpOrd'],
		$frmListaPresupuesto['valBusq']));
	
	return $objResponse;
}

function generarPresupuesto($idPresupuesto) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_presupuesto
		INNER JOIN cj_cc_cliente ON an_presupuesto.id_cliente = cj_cc_cliente.id
	WHERE id_presupuesto = %s;",
		valTpDato($idPresupuesto,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['tipo_cuenta_cliente'] == 2 || $row['tipo_cuenta_cliente'] == "") {
		$objResponse->script("window.location.href='an_pedido_venta_form.php?idPresupuesto=".$idPresupuesto."';");
	} else if ($row['tipo_cuenta_cliente'] == 1) {
		$updateSQL = sprintf("UPDATE cj_cc_cliente SET
			tipo_cuenta_cliente = 1
		WHERE id = %s;",
			valTpDato($row['id_cliente'], "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$objResponse->alert("El prospecto perteneciente a este pedido no está aprobado como cliente. Recomendamos lo apruebe en la pantalla de Prospectación");
	}
	
	return $objResponse;
}

function listaPresupuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanInicial;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("pres_vent.estado IN (0,3)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pres_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = pres_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pres_vent.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($valCadBusq[3]) {
			case "00" : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 0)");
			case "22" : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 2)");
			case "33" : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 3)");
			default : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 1 AND estado_pedido = %s)",
						valTpDato($valCadBusq[3], "int"));
		}
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pres_vent.id_presupuesto LIKE %s
		OR ped_vent.id_cliente LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT(vw_iv_modelo.nom_uni_bas,': ', vw_iv_modelo.nom_modelo,' - ', vw_iv_modelo.nom_version) LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT pres_vent.*,
		pres_vent.estado AS estado_presupuesto,
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
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		vw_iv_modelo.nom_uni_bas,
		vw_iv_modelo.nom_marca,
		vw_iv_modelo.nom_modelo,
		vw_iv_modelo.nom_version,
		vw_iv_modelo.nom_ano,
		vw_iv_modelo.imagen_auto,
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
		ped_vent.estado_pedido,
		ped_financ.id_pedido_financiamiento,
		ped_financ.numeracion_pedido AS numeracion_pedido_financiamiento,
		ped_financ.estatus_pedido AS estatus_pedido_financiamiento,
		
		IFNULL(pres_vent.precio_venta * (pres_vent.porcentaje_iva + pres_vent.porcentaje_impuesto_lujo) / 100, 0) AS monto_impuesto,	
		(IFNULL(pres_vent.precio_venta, 0)
			+ IFNULL(pres_vent.precio_venta * (pres_vent.porcentaje_iva + pres_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta_impuesto,
		
		(SELECT COUNT(*)
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		WHERE id_uni_bas = pres_vent.id_uni_bas
			AND (alm.id_empresa = pres_vent.id_empresa
				OR pres_vent.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = alm.id_empresa)
				OR pres_vent.id_empresa IN (SELECT suc.id_empresa FROM pg_empresa suc
						WHERE suc.id_empresa_padre = alm.id_empresa)
				OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = pres_vent.id_empresa) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
																	WHERE suc.id_empresa = alm.id_empresa))
			AND estado_venta IN ('POR REGISTRAR','DISPONIBLE')
			AND propiedad = 'PROPIO') AS ud,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_presupuesto pres_vent
		INNER JOIN cj_cc_cliente cliente ON (pres_vent.id_cliente = cliente.id)
		LEFT JOIN pg_monedas moneda_local ON (pres_vent.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_extranjera ON (pres_vent.id_moneda_tasa_cambio = moneda_extranjera.idmoneda)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (pres_vent.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (pres_vent.asesor_ventas = vw_pg_empleado.id_empleado)
		LEFT JOIN an_pedido ped_vent ON (pres_vent.id_presupuesto = ped_vent.id_presupuesto)
		LEFT JOIN bancos banco ON (pres_vent.id_banco_financiar = banco.idBanco)
		LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto)
		LEFT JOIN fi_documento ped_financ_det ON (pres_vent.id_presupuesto = ped_financ_det.id_presupuesto)
			LEFT JOIN fi_pedido ped_financ ON (ped_financ_det.id_pedido_financiamiento = ped_financ.id_pedido_financiamiento)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (pres_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "6%", $pageNum, "numeracion_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto / Nro. Financiamiento");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "6%", $pageNum, "id_presupuesto_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto Accesorios");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "18%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "22%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "8%", $pageNum, "precio_venta_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Venta");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "8%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, $spanInicial);
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "8%", $pageNum, "total_general", $campOrd, $tpOrd, $valBusq, $maxRows, "Total General");
		$htmlTh .= "<td colspan=\"7\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusPedido = "";
		if ($row['estado_presupuesto'] == 0) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Presupuesto Autorizado\"/>";
		} else if ($row['estado_presupuesto'] == 1) {
			switch ($row['estado_pedido']) {
				case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Autorizado\"/>"; break;
				case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			}
		} else if ($row['estado_presupuesto'] == 2) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Presupuesto Anulado\"/>";
		} else if ($row['estado_presupuesto'] == 3) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Presupuesto Desautorizado\"/>";
		}
		
		$rowspan = (strlen($row['observacion']) > 0) ? "rowspan=\"2\"" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" ".$rowspan.">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td ".$rowspan.">".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
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
				$htmlTb .= ($row['ud'] > 0) ? "<div class=\"textoNegrita_10px\">Disponible: ".number_format($row['ud'], 2, ".", ",")."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".$row['abreviacion_moneda'].number_format($row['precio_venta_impuesto'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">";
				$htmlTb .= "<div>".$row['abreviacion_moneda'].number_format($row['monto_inicial'], 2, ".", ",")."</div>";
				$htmlTb .= "<div class=\"textoNegrita_10px\">".number_format($row['porcentaje_inicial'], 2, ".", ",")."%</div>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" ".$rowspan.">".$row['abreviacion_moneda'].number_format($row['total_general'], 2, ".", ",")."</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if (!($row['id_pedido_financiamiento'] > 0)) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('../financiamientos/fi_form_financiamiento.php?idPresupuestoVehiculo=%s','_self');\" src=\"../img/iconos/coins.png\" title=\"Generar Financiamiento\"/>",
					$row['id_presupuesto']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if (!($row['id_pedido_financiamiento'] > 0) && $row['estado_presupuesto'] == 0 && $row['ud'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_generarPresupuesto('%s')\" src=\"../img/iconos/book_next.png\" title=\"Generar Pedido\"/>",
					$row['id_presupuesto']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if (!($row['id_pedido_financiamiento'] > 0)) {
				if ($row['estado_presupuesto'] == 0) {
					$presupuestoVentaForm = (in_array(idArrayPais,array(1,2,3))) ?
						sprintf("an_ventas_presupuesto_editar.php?id=%s",
							$row['id_presupuesto']) :
						sprintf("an_presupuesto_venta_form.php?id=%s",
							$row['id_presupuesto']);
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('%s','_self')\" src=\"../img/iconos/pencil.png\" title=\"Editar Presupuesto\"/>",
						$presupuestoVentaForm);
				} else if($row['estado_presupuesto'] == 3) {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarAutorizar('%s')\" src=\"../img/iconos/accept.png\" title=\"Autorizar Presupuesto\"/>",
						$row['id_presupuesto']);
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if (!($row['id_pedido_financiamiento'] > 0) && $row['id_presupuesto_accesorio'] > 0){
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('an_combo_presupuesto_list.php?view=1&id=%s','_self');\" src=\"../img/iconos/generarPresupuesto.png\" title=\"Editar Presupuesto Accesorios\"/>",
					$row['id_presupuesto']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if (!($row['id_pedido_financiamiento'] > 0)) {
				if ($row['estado_presupuesto'] == 0) {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarDesautorizar('%s')\" src=\"../img/iconos/cancel.png\" title=\"Desautorizar Presupuesto\"/>",
						$row['id_presupuesto']);
				} else if ($row['estado_presupuesto'] == 3) {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarAnular('%s','%s')\" src=\"../img/iconos/ico_delete.png\" title=\"Anular Presupuesto\"/>",
						$row['id_presupuesto'],
						$row['id_presupuesto']);
				}
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
				$presupuestoVentaForm = (in_array(idArrayPais,array(1,2,3))) ?
					sprintf("an_ventas_presupuesto_editar.php?view=print&id=%s",
						$row['id_presupuesto']) :
					sprintf("reportes/an_presupuesto_venta_pdf.php?id=%s",
						$row['id_presupuesto']);
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('%s', 960, 550);\"
				src=\"../img/iconos/".((in_array(idArrayPais,array(1,3))) ? "ico_print.png" : "page_white_acrobat.png")."\"
				title=\"".((in_array(idArrayPais,array(1,3))) ? "Imprimir Presupuesto" : "Presupuesto Venta PDF")."\"/>",
					$presupuestoVentaForm);
			$htmlTb .= "</td>";
			$htmlTb .= "<td ".$rowspan.">";
			if ($row['id_presupuesto_accesorio'] > 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_combo_presupuesto_pdf.php?idPresupuesto=%s', 960, 550);\" src=\"../img/iconos/page_green.png\" title=\"Presupuesto Accesorio PDF\"/>",
					$row['id_presupuesto_accesorio']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if (strlen($rowspan) > 0) {
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td colspan=\"6\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPresupuesto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularPresupuesto");
$xajax->register(XAJAX_FUNCTION,"autorizarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"buscarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"desautorizarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"generarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"listaPresupuesto");
?>