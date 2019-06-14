<?php


function asignarProveedor($idProveedor, $nombreObjeto, $asigDescuento = "true", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor prov
	WHERE id_proveedor = %s",
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombre".$nombreObjeto,"value",utf8_encode($rowProv['nombre_proveedor']));
	$objResponse->assign("txtRif".$nombreObjeto,"value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtNit".$nombreObjeto,"value",utf8_encode($rowProv['nit_proveedor']));
	$objResponse->assign("txtDireccion".$nombreObjeto,"innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContacto".$nombreObjeto,"value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContacto".$nombreObjeto,"value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonos".$nombreObjeto,"value",$rowProv['telefono']);
	
	if (strtoupper($rowProv['credito']) == "SI" || $rowProv['credito'] == 1) {
		$queryProvCredito = sprintf("SELECT * FROM cp_prove_credito WHERE id_proveedor = %s;",
			valTpDato($rowProv['id_proveedor'], "int"));
		$rsProvCredito = mysql_query($queryProvCredito);
		if (!$rsProvCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProvCredito = mysql_fetch_assoc($rsProvCredito);
		
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value",$rowProvCredito['diascredito']);
		
		$objResponse->call("selectedOption","lstTipoPago",1);
	} else {
		$objResponse->assign("txtDiasCredito".$nombreObjeto,"value","0");
		
		$objResponse->call("selectedOption","lstTipoPago",0);
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaProveedor').click();");
	}
							
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarEstadoCuenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdProv'],
		$frmBuscar['txtFecha'],
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"));
	
	switch ($frmBuscar['radioOpcion']) {
		case 1 : $objResponse->loadCommands(listaFacturaIndividual(0, "vw_cxp.id_factura", "DESC", $valBusq)); break;
		case 2 : $objResponse->loadCommands(listaFacturaIndividual(0, "vw_cxp.id_factura", "DESC", $valBusq)); break;
		case 3 : $objResponse->loadCommands(listaNotaCargoIndividual(0, "vw_cxp_nd.id_notacargo", "DESC", $valBusq)); break;
		case 4 : $objResponse->loadCommands(listaNotaCargoIndividual(0, "vw_cxp_nd.id_notacargo", "DESC", $valBusq)); break;
	}
	
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarProveedor['hddObjDestinoProveedor'],
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "ASC", $valBusq));
		
	return $objResponse;
}

function cargarModulos(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td width=\"50%\"><label><input type=\"checkbox\" id=\"cbxModulo\" name=\"cbxModulo[]\" checked=\"checked\" value=\"".$row['id_modulo']."\"/> ".$row['descripcionModulo']."</label></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdModulos","innerHTML",$html);
	
	return $objResponse;
}

function exportarAntiguedadSaldo($frmBuscar){
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['cbxModulo'])) {
		foreach ($frmBuscar['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr($idModulos, 0, (strlen($idModulos)-1));
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtIdProv'],
		$frmBuscar['txtFecha'],
		$frmBuscar['radioOpcion'],
		$idModulos);
	
	$objResponse->script("window.open('reportes/cc_antiguedad_saldo_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanProvCxP;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		prov.id_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor prov %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(strtoupper($arrayTipoPago[strtoupper($row['credito'])]))."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaFacturaIndividual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 2000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxp.fecha_factura_proveedor <= %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxp.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp.id_proveedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp.id_modulo IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	$query = sprintf("SELECT
		vw_cxp.id_factura,
		vw_cxp.numero_factura_proveedor,
		vw_cxp.id_modulo,
		vw_cxp.fecha_origen,
		vw_cxp.fecha_vencimiento,
		vw_cxp.fecha_factura_proveedor,
		prov.id_proveedor,
		prov.nombre AS nombre_proveedor,
		vw_cxp.total_cuenta_pagar,
		
		(CASE
			WHEN ((SELECT COUNT(id_pago) FROM cp_pagos_documentos cxp_pago
					WHERE cxp_pago.id_documento_pago = vw_cxp.id_factura
						AND cxp_pago.tipo_documento_pago LIKE 'FA'
						AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND fecha_anulado <= %s))) = 0 AND saldo_factura = 0) THEN
				vw_cxp.total_cuenta_pagar
			ELSE
				(SELECT IFNULL(SUM(cxp_pago.monto_cancelado), 0) FROM cp_pagos_documentos cxp_pago
				WHERE cxp_pago.id_documento_pago = vw_cxp.id_factura
					AND cxp_pago.tipo_documento_pago LIKE 'FA'
					AND cxp_pago.fecha_pago <= %s
					AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND fecha_anulado <= %s)))
		END) AS total_pagos,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cp_cuentas_por_pagar vw_cxp
		INNER JOIN cp_proveedor prov ON (vw_cxp.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	GROUP BY vw_cxp.id_proveedor, vw_cxp.id_factura
	HAVING (total_cuenta_pagar - total_pagos) > 0",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq);
	
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaFacturaIndividual", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFacturaIndividual", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaFacturaIndividual", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Factura Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturaIndividual", "6%", $pageNum, "fecha_vencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaIndividual", "12%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaIndividual", "4%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaFacturaIndividual", "24%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturaIndividual", "8%", $pageNum, "total_pagos", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Pagado");
		$htmlTh .= ordenarCampo("xajax_listaFacturaIndividual", "8%", $pageNum, "total_pagos", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaIndividual", "8%", $pageNum, "total_cuenta_pagar", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$idFactura = $row['id_factura'];
		$montoTotal = $row['total_cuenta_pagar'];
		$totalPagos = $row['total_pagos'];
		$totalSaldo = $montoTotal - $totalPagos;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 1 : 4;
		$objDcto->tipoDocumento = "FA";
		$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $row['id_modulo'];
		$objDcto->idDocumento = $row['id_factura'];
		$aVerDcto = $objDcto->verDocumento();
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("fact_comp_det_unidad.id_factura = %s",
			valTpDato($idFactura, "int"));
		
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cargo.fecha_origen_notacargo <= %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("nota_cargo.id_modulo IN (%s)",
				valTpDato($valCadBusq[3], "campo"));
		}
		
		// VERIFICA SI EL DOCUMENTO DE VEHICULO CANCELADO CON PLAN MAYOR (SI TIENE NOTA DE CARGO DE PLAN MAYOR)
		$queryNotaPlanMayor = sprintf("SELECT
			nota_cargo.id_notacargo,
			nota_cargo.numero_notacargo,
			nota_cargo.numero_control_notacargo,
			nota_cargo.fecha_origen_notacargo,
			nota_cargo.fecha_notacargo,
			nota_cargo.estatus_notacargo,
			nota_cargo.subtotal_notacargo,
			nota_cargo.id_modulo,
			(CASE nota_cargo.estatus_notacargo
				WHEN 0 THEN 'No Cancelado'
				WHEN 1 THEN 'Cancelado'
				WHEN 2 THEN 'Cancelado Parcial'
			END) AS estado_nota_cargo,
			(CASE nota_cargo.tipo_pago_notacargo
				WHEN 0 THEN 'Contado'
				WHEN 1 THEN 'Credito'
			END) AS tipo_pago_notacargo,
			nota_cargo.saldo_notacargo,
			modulo.descripcionModulo,
			(CASE nota_cargo.aplica_libros_notacargo
				WHEN 0 THEN 'NO'
				WHEN 1 THEN 'SI'
			END) AS aplica_libros_notacargo,
			motivo.id_motivo,
			motivo.descripcion
		FROM cp_notadecargo nota_cargo
			INNER JOIN an_unidad_fisica uni_fis ON (nota_cargo.id_detalles_pedido_compra = uni_fis.id_pedido_compra_detalle)
			INNER JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
			INNER JOIN pg_modulos modulo ON (nota_cargo.id_modulo = modulo.id_modulo) 
			LEFT JOIN pg_motivo motivo ON (nota_cargo.id_motivo = motivo.id_motivo) %s;", $sqlBusq2);
		$rsNotaPlanMayor = mysql_query($queryNotaPlanMayor);
		if (!$rsNotaPlanMayor) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNotaPlanMayor = mysql_num_rows($rsNotaPlanMayor);
		$rowNotaPlanMayor = mysql_fetch_array($rsNotaPlanMayor);
		$imgDevuelta = "";
		if ($totalRowsNotaPlanMayor > 0) {
			$imgDevuelta = "<img align=\"left\" title=\"Factura Devuelta\" src=\"../img/iconos/ico_plan_mayor.png\">";
			$montoNotaPlanMayor = $rowNotaPlanMayor['subtotal_notacargo'];
			$totalSaldo = $montoTotal - $montoNotaPlanMayor;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_vencimiento']))."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".$imgDevuelta."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numero_factura_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
				$htmlTb .= ($dias > 0) ? "<div class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['observacion_factura']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_factura'])."<span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalPagos, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalSaldo, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($montoTotal, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	
		$arrayTotal[10] += $totalPagos;
		$arrayTotal[11] += $totalSaldo;
		$arrayTotal[12] += $montoTotal;
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"8\">Totales:</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaIndividual(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaNotaCargoIndividual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 2000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_cxp_nd.fecha_origen_notacargo <= %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_nd.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxp_nd.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_nd.id_proveedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_nd.id_modulo IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	$query = sprintf("SELECT
		vw_cxp_nd.id_notacargo,
		vw_cxp_nd.numero_notacargo,
		vw_cxp_nd.id_modulo,
		vw_cxp_nd.fecha_origen_notacargo,
		vw_cxp_nd.fecha_vencimiento_notacargo,
		vw_cxp_nd.fecha_notacargo,
		prov.id_proveedor,
		prov.nombre AS nombre_proveedor,
		vw_cxp_nd.total_cuenta_pagar,
		
		(CASE
			WHEN ((SELECT COUNT(id_pago) FROM cp_pagos_documentos cxp_pago
					WHERE cxp_pago.id_documento_pago = vw_cxp_nd.id_notacargo
						AND cxp_pago.tipo_documento_pago LIKE 'ND'
						AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND fecha_anulado <= %s))) = 0 AND saldo_notacargo = 0) THEN
				SUM(vw_cxp_nd.total)
			ELSE
				(SELECT IFNULL(SUM(cxp_pago.monto_cancelado), 0) FROM cp_pagos_documentos cxp_pago
				WHERE cxp_pago.id_documento_pago = vw_cxp_nd.id_notacargo
					AND cxp_pago.tipo_documento_pago LIKE 'ND'
					AND cxp_pago.fecha_pago <= %s
					AND (cxp_pago.estatus = 1 OR (cxp_pago.estatus IS NULL AND fecha_anulado <= %s)))
		END) AS total_pagos,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cp_cuentas_por_pagar_nd vw_cxp_nd
		INNER JOIN cp_proveedor prov ON (vw_cxp_nd.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	GROUP BY vw_cxp_nd.id_proveedor, vw_cxp_nd.id_notacargo
	HAVING (total_cuenta_pagar - total_pagos) > 0",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "date"),
		$sqlBusq);
	
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCargoIndividual", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCargoIndividual", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Registro"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCargoIndividual", "6%", $pageNum, "fecha_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Nota de Débito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCargoIndividual", "6%", $pageNum, "fecha_vencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Venc. Nota de Débito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCargoIndividual", "12%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Nota de Débito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCargoIndividual", "4%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCargoIndividual", "24%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Proveedor"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCargoIndividual", "8%", $pageNum, "total_pagos", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Monto Pagado"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCargoIndividual", "8%", $pageNum, "total_pagos", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo Nota de Débito"));
		$htmlTh .= ordenarCampo("xajax_listaNotaCargoIndividual", "8%", $pageNum, "total_cuenta_pagar", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total Nota de Débito"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_array($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$idNotaCargo = $row['id_notacargo'];
		$montoTotal = $row['total_cuenta_pagar'];
		$totalPagos = $row['total_pagos'];
		$totalSaldo = $montoTotal - $totalPagos;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$objDcto = new Documento;
		$objDcto->raizDir = $raiz;
		$objDcto->tipoMovimiento = (in_array("ND",array("FA","ND","AN","CH","TB"))) ? 1 : 4;
		$objDcto->tipoDocumento = "ND";
		$objDcto->tipoDocumentoMovimiento = (in_array("ND",array("NC"))) ? 2 : 1;
		$objDcto->idModulo = $row['id_modulo'];
		$objDcto->idDocumento = $row['id_notacargo'];
		$aVerDcto = $objDcto->verDocumento();
		
		// VERIFICA SI EL DOCUMENTO ESTÁ DEVUELTO (SI TIENE NOTA DE CREDIO)
		/*$queryNotaCredito = sprintf("SELECT idDocumento, montoNetoNotaCredito
		FROM cj_cc_notacredito
		WHERE idDocumento = %s
			AND fechaNotaCredito <= %s
			AND idDepartamentoNotaCredito IN (%s)",
			valTpDato($idNotaCargo, "int"),
			valTpDato($fechaCxC_nv, "date"),
			valTpDato($valor, "int"));
		$rsNotaCredito = mysql_query($queryNotaCredito);
		if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsNotaCredito = mysql_num_rows($rsNotaCredito);
		$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
		$imgDevuelta = "";
		if ($totalRowsNotaCredito > 0) {
			$imgDevuelta = "<img align=\"left\" title=\"Factura Devuelta\" src=\"../img/iconos/ico_plan_mayor.png\">";
			$montoNotaCredito = $rowNotaCredito['montoNetoNotaCredito'];
			$totalSaldo = $montoTotal - $montoNotaCredito;
		}*/
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen_notacargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_notacargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_vencimiento_notacargo']))."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".$imgDevuelta."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numero_notacargo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
				$htmlTb .= ($dias > 0) ? "<div class=\"textoNegrita_9px\">".$dias.utf8_encode(" días vencidos")."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalPagos, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalSaldo, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($montoTotal, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	
		$arrayTotal[10] += $totalPagos;
		$arrayTotal[11] += $totalSaldo;
		$arrayTotal[12] += $montoTotal;
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">Totales:</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargoIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargoIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCargoIndividual(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargoIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargoIndividual(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"exportarAntiguedadSaldo");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"listaFacturaIndividual");
$xajax->register(XAJAX_FUNCTION,"listaNotaCargoIndividual");
?>