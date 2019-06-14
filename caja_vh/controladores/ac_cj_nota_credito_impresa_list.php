<?php
function formEditarConsecutivoFiscal($idNotaCredito,$consecutivo_fiscal){
	$objResponse = new xajaxResponse();
	if (!xvalidaAcceso($objResponse,"cj_nota_credito_impresa_list","editar")){ 
		$objResponse->alert("Acceso Denegado");
		return $objResponse; 
	}
	$serialImpresora = "MANUAL";
	$fechaEdicion = date("Y-m-d");
	$horaEdicion = date("H:i:s");
	if($consecutivo_fiscal != NULL){
		$queryNotaCreditoValidar = sprintf("SELECT COUNT(idNotaCredito) AS num FROM cj_cc_notacredito WHERE consecutivo_fiscal = %s",
			valTpDato($consecutivo_fiscal, "text"));
		$rsNotaCreditoValidar = mysql_query($queryNotaCreditoValidar);
		if (!$rsNotaCreditoValidar) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowValidar = mysql_fetch_assoc($rsNotaCreditoValidar);

		if($rowValidar['num'] == 0){

			$queryNotaCredito = sprintf("UPDATE cj_cc_notacredito SET consecutivo_fiscal = %s, serial_impresora = %s, fecha_impresora = %s, hora_impresora = %s WHERE idNotaCredito = %s",
				valTpDato($consecutivo_fiscal, "text"),
				valTpDato($serialImpresora, "text"),
				valTpDato($fechaEdicion, "text"),
				valTpDato($horaEdicion, "text"),
				valTpDato($idNotaCredito, "int"));

			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$objResponse->script("byId('imgCerrarDivFlotante32').click()");
			$objResponse->script("byId('btnBuscar').click()");
		}else{
			$objResponse->alert("Introdujo un Consecutivo Fiscal ya registrado");
		}
	}else{
		$objResponse->alert("Debe introducir un Consecutivo Fiscal");		
	}
	return $objResponse;
}

function reimprimirNotaCredito($idNotaCredito){
	$objResponse = new xajaxResponse();
	if (!xvalidaAcceso($objResponse,"cj_nota_credito_impresa_list","editar")){ 
		$objResponse->alert("Acceso Denegado");
		return $objResponse; 
	}
	$queryNotaCredito=sprintf("UPDATE cj_cc_notacredito SET consecutivo_fiscal = NULL , serial_impresora = NULL, fecha_impresora = NULL, hora_impresora = NULL WHERE idNotaCredito = %s", valTpDato($idNotaCredito, "int"));
	$rsNotaCredito = mysql_query($queryNotaCredito);
	if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$objResponse->alert("Nota de crédito lista para reimprimir.");
	$objResponse->script("byId('btnBuscar').click()");
	return $objResponse;
}

function buscarNotaCreditoEditarConsecutivoFiscal($idNotaCredito){
	$objResponse = new xajaxResponse();
	$queryFactura = sprintf("SELECT cj_cc_notacredito.consecutivo_fiscal,cj_cc_notacredito.numeracion_nota_credito, cj_cc_notacredito.numeroControl, pg_empresa.nombre_empresa, cj_cc_cliente.nombre, cj_cc_cliente.apellido FROM cj_cc_notacredito 
			INNER JOIN cj_cc_cliente ON cj_cc_cliente.id = cj_cc_notacredito.idCliente
			INNER JOIN pg_empresa ON pg_empresa.id_empresa = cj_cc_notacredito.id_empresa
				WHERE idNotaCredito = %s",
		valTpDato($idNotaCredito, "int"));
	$rsFactura = mysql_query($queryFactura);
	if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowFactura = mysql_fetch_assoc($rsFactura);
	$cliente = $rowFactura['nombre'] . " " . $rowFactura['apellido'];
	$objResponse->assign ("nomEmpresa","innerHTML","<b>".$rowFactura['nombre_empresa']."</b>");
	$objResponse->assign ("nomCliente","innerHTML","<b>".$cliente."</b>");
	$objResponse->assign ("nroFactura","innerHTML","<b>".$rowFactura['numeracion_nota_credito']."</b>");
	$objResponse->assign ("nroControl","innerHTML","<b>".$rowFactura['numeroControl']."</b>");
	$objResponse->assign ("hddidNotaCredito","value",$idNotaCredito);
	$objResponse->assign ("hddConsecutivoFiscal","value",$rowFactura['consecutivo_fiscal']);

	return $objResponse;
}


function buscarNotaCredito($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstEmpleadoVendedor']),
		$frmBuscar['lstAplicaLibro'],
		implode(",",$frmBuscar['lstEstadoNotaCredito']),
		implode(",",$frmBuscar['lstModulo']),
		implode(",",$frmBuscar['lstEstadoFiscal']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaNotaCredito(0, "idNotaCredito", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo IN (%s)", valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM cj_cc_notacredito nota_cred
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (nota_cred.id_empleado_vendedor = vw_pg_empleado.id_empleado)
	WHERE nota_cred.idDepartamentoNotaCredito IN (%s)
	ORDER BY nombre_empleado",
		valTpDato($idModuloPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstOrientacionPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array("V" => "Vertical", "H" => "Horizontal");
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirNotaCredito(xajax.getFormValues('frmBuscar'));\"";
	
	$html = "<select id=\"lstOrientacionPDF\" name=\"lstOrientacionPDF\" ".$class." ".$onChange.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstOrientacionPDF","innerHTML",$html);
	
	return $objResponse;
}

function exportarNotaCredito($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstEmpleadoVendedor']),
		$frmBuscar['lstAplicaLibro'],
		implode(",",$frmBuscar['lstEstadoNotaCredito']),
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/cjvh_nota_credito_impresa_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function imprimirNotaCredito($frmBuscar){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		implode(",",$frmBuscar['lstEmpleadoVendedor']),
		$frmBuscar['lstAplicaLibro'],
		implode(",",$frmBuscar['lstEstadoNotaCredito']),
		implode(",",$frmBuscar['lstModulo']),
		implode(",",$frmBuscar['lstEstadoFiscal']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cjvh_nota_credito_impresa_pdf.php?valBusq=%s&lstOrientacionPDF=%s',890,550)", $valBusq, $frmBuscar['lstOrientacionPDF']));
	
	$objResponse->assign("tdlstOrientacionPDF","innerHTML","");
	
	return $objResponse;
}

function listaNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $idModuloPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_nc.estatus_nota_credito IN (2)
	AND idDepartamentoNotaCredito IN (%s)",
		valTpDato($idModuloPpal, "campo"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.fechaNotaCredito BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.id_empleado_vendedor IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.aplicaLibros = %s",
			valTpDato($valCadBusq[4], "boolean"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.estadoNotaCredito IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxc_nc.idDepartamentoNotaCredito IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$arrayConsecutivoFiscal = explode(",",$valCadBusq[7]);
		if (count($arrayConsecutivoFiscal) == 1) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			if (in_array(1,$arrayConsecutivoFiscal) && !in_array(2,$arrayConsecutivoFiscal)) {
				$sqlBusq .= $cond.sprintf("cxc_nc.consecutivo_fiscal IS NOT NULL");
			} else if (!in_array(1,$arrayConsecutivoFiscal) && in_array(2,$arrayConsecutivoFiscal)) {
				$sqlBusq .= $cond.sprintf("cxc_nc.consecutivo_fiscal IS NULL");
			}
		}
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_nc.numeracion_nota_credito LIKE %s
		OR cxc_nc.numeroControl LIKE %s
		OR cxc_fact.numeroFactura LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cxc_nc.observacionesNotaCredito LIKE %s
		OR (SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
			FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
				INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
			WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) LIKE %s
		OR cxc_nc.consecutivo_fiscal LIKE %s
		OR cxc_nc.serial_impresora LIKE %s)",
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cxc_fact.fechaRegistroFactura,
		cxc_fact.numeroFactura,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_nc.idNotaCredito,
		cxc_nc.numeracion_nota_credito,
		cxc_nc.numeroControl,
		cxc_nc.fechaNotaCredito,
		cxc_nc.idDepartamentoNotaCredito AS id_modulo,
		cxc_nc.observacionesNotaCredito,
		cxc_nc.subtotalNotaCredito,
		cxc_nc.subtotal_descuento,
		
		(IFNULL(cxc_nc.subtotalNotaCredito, 0)
			- IFNULL(cxc_nc.subtotal_descuento, 0)) AS total_neto,
		
		IFNULL((SELECT SUM(cxc_nc_iva.subtotal_iva) FROM cj_cc_nota_credito_iva cxc_nc_iva
				WHERE cxc_nc_iva.id_nota_credito = cxc_nc.idNotaCredito), 0) AS total_impuestos,
		
		cxc_nc.montoNetoNotaCredito,
		cxc_nc.saldoNotaCredito,
		cxc_nc.estadoNotaCredito,
		(CASE cxc_nc.estadoNotaCredito
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado No Asignado'
			WHEN 2 THEN 'Asignado Parcial'
			WHEN 3 THEN 'Asignado'
		END) AS descripcion_estado_nota_credito,
		cxc_nc.aplicaLibros,
		
		(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
		FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
			INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
		WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito) AS descripcion_motivo,
		
		(CASE cxc_nc.idDepartamentoNotaCredito
			WHEN 0 THEN
				IFNULL((SELECT COUNT(cxc_nc_det.id_nota_credito) FROM cj_cc_nota_credito_detalle cxc_nc_det
						WHERE cxc_nc_det.id_nota_credito = cxc_nc.idNotaCredito), 0)
			WHEN 1 THEN
				(IFNULL((SELECT COUNT(sa_fact_det_art.idFactura) FROM sa_det_fact_articulo sa_fact_det_art
						WHERE sa_fact_det_art.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_nota.idFactura) FROM sa_det_fact_notas sa_fact_det_nota
							WHERE sa_fact_det_nota.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_temp.idFactura) FROM sa_det_fact_tempario sa_fact_det_temp
							WHERE sa_fact_det_temp.idFactura = cxc_fact.idFactura), 0)
					+ IFNULL((SELECT COUNT(sa_fact_det_tot.idFactura) FROM sa_det_fact_tot sa_fact_det_tot
							WHERE sa_fact_det_tot.idFactura = cxc_fact.idFactura), 0))
			WHEN 2 THEN
				(IFNULL((SELECT COUNT(cxc_nc_det_acc.id_nota_credito) FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
						WHERE cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito), 0)
					+ IFNULL((SELECT COUNT(cxc_nc_det_vehic.id_nota_credito) FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
							WHERE cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito), 0))
			WHEN 3 THEN
				IFNULL((SELECT COUNT(cxc_nc_det_adm.id_nota_credito) FROM cj_cc_nota_credito_detalle_adm cxc_nc_det_adm
						WHERE cxc_nc_det_adm.id_nota_credito = cxc_nc.idNotaCredito), 0)
		END) AS cant_items,
		
		cxc_nc.consecutivo_fiscal,
		cxc_nc.serial_impresora,
		cxc_nc.fecha_impresora,
		cxc_nc.hora_impresora,
		CONCAT_WS('/', cxc_nc.consecutivo_fiscal, cxc_nc.serial_impresora) AS consecutivo_serial,
		CONCAT_WS(' ', cxc_nc.fecha_impresora, cxc_nc.hora_impresora) AS fecha_hora,
		
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_notacredito cxc_nc
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN cj_cc_cliente cliente ON (cxc_nc.idCliente = cliente.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (cxc_nc.id_empleado_creador = vw_pg_empleado_creador.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "fechaNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "numeracion_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota de Crédito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "cxc_nc.numeroControl", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Factura");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "6%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "descripcion_estado_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Nota de Crédito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "4%", $pageNum, "fecha_hora", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha / Hora Impresora");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "4%", $pageNum, "consecutivo_serial", $campOrd, $tpOrd, $valBusq, $maxRows, "Consecutivo Fiscal / Serial Impresora");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "saldoNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Nota de Crédito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCredito", "8%", $pageNum, "montoNetoNotaCredito", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Nota de Crédito");
		$htmlTh .= "<td colspan='2'></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)){
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
		
		switch($row['estadoNotaCredito']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$imgDctoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModuloCondicion."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"Nota de Crédito Nro: ".utf8_encode($row['numeracion_nota_credito']).". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "").">".date(spanDateFormat, strtotime($row['fechaNotaCredito']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_nota_credito']."</td>";
			$htmlTb .= "<td align=\"right\" nowrap=\"nowrap\">".$row['numeroControl']."</td>";
			$htmlTb .= "<td align=\"center\">".((strtotime($row['fechaRegistroFactura'])) ? date(spanDateFormat, strtotime($row['fechaRegistroFactura'])) : "")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['descripcion_motivo']) > 0) ? "<tr><td><span class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= (strlen($row['observacionesNotaCredito']) > 0) ? "<tr><td class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionesNotaCredito'])."</td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicion_pago'] == 0) ? "divMsjAlerta" : "divMsjInfo")."\">";
				$htmlTb .= ($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['descripcion_estado_nota_credito']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['fecha_hora']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['consecutivo_serial'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoNotaCredito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoNetoNotaCredito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">";
			
			$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditarNotaCredito\" rel=\"#divFlotante3\" onclick=\"abrirDivFlotante3(this, 'tblEditarConsecutivo', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>", 
					
					$row['idNotaCredito']);
			$htmlTb .= "</td>";

			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">";
			$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aReimprimirNotaCredito\" rel=\"\" onclick=\"reimprimirNotaCredito(this, 'tblPrivilegio', '%s');\"><img class=\"puntero\" src=\"../img/cc.png\" title=\"Reimprimir\"/></a>", 
					
					$row['idNotaCredito']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[12] += $row['cant_items'];
		$arrayTotal[13] += $row['saldoNotaCredito'];
		$arrayTotal[14] += $row['montoNetoNotaCredito'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"14\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[14], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"4\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[12] += $row['cant_items'];
				$arrayTotalFinal[13] += $row['saldoNotaCredito'];
				$arrayTotalFinal[14] += $row['montoNetoNotaCredito'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"14\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[14], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"4\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaNotaCreditoCxC","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_impuestos'];
		$totalNotasCredito += $row['montoNetoNotaCredito'];
		$totalSaldo += $row['saldoNotaCredito'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalNotasCredito","innerHTML",number_format($totalNotasCredito, 2, ".", ","));
	$objResponse->assign("spnSaldoNotasCredito","innerHTML",number_format($totalSaldo, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstOrientacionPDF");
$xajax->register(XAJAX_FUNCTION,"exportarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"imprimirNotaCredito");
$xajax->register(XAJAX_FUNCTION,"listaNotaCredito");
$xajax->register(XAJAX_FUNCTION,"reimprimirNotaCredito");
$xajax->register(XAJAX_FUNCTION,"buscarNotaCreditoEditarConsecutivoFiscal");
$xajax->register(XAJAX_FUNCTION,"formEditarConsecutivoFiscal");
?>