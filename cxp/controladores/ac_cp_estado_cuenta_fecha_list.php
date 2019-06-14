<?php

function buscar($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['txtIdEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		((count($frmBuscar['cbxModulo']) > 0) ? implode(",",$frmBuscar['cbxModulo']) : "-1"));
	
	$objResponse->loadCommands(listaECGeneral(0, "id_proveedor", "ASC", $valBusq));
	
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

function cargarModulos(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 4) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
		
			$html .= "<td width=\"25%\"><label><input type=\"checkbox\" id=\"cbxModulo\" name=\"cbxModulo[]\" checked=\"checked\" value=\"".$row['id_modulo']."\"/> ".$row['descripcionModulo']."</label></td>";
				
		$html .= (fmod($contFila, 4) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdModulos","innerHTML",$html);
	
	return $objResponse;
}

function listaECGeneral($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$txtFechaDesde = $valCadBusq[1];
	$txtFechaHasta = $valCadBusq[2];
	$idModulos .= $valCadBusq[3];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(cxp_fact.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_fact.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("(cxp_nd.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_nd.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
	$sqlBusq3 .= $cond.sprintf("(cxp_ant.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_ant.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
	$sqlBusq4 .= $cond.sprintf("(cxp_nc.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = cxp_nc.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("cxp_nd.id_modulo IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq3) > 0) ? " AND " : " WHERE ";
		$sqlBusq3 .= $cond.sprintf("cxp_ant.idDepartamento IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq4) > 0) ? " AND " : " WHERE ";
		$sqlBusq4 .= $cond.sprintf("cxp_nc.id_departamento_notacredito IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	$query = "
	SELECT
		query.id_empresa,
		prov.id_proveedor,
		prov.nombre AS nombre,
		
		(IFNULL((SELECT SUM(cxp_fact.subtotal_factura) FROM cp_factura cxp_fact
				WHERE cxp_fact.id_empresa = query.id_empresa
					AND cxp_fact.id_proveedor = prov.id_proveedor
					AND cxp_fact.id_modulo IN (".$idModulos.")
					AND cxp_fact.fecha_origen BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
					AND cxp_fact.estatus_factura IN (1)), 0)
			+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva)
					FROM cp_factura_iva cxp_fact_iva
						INNER JOIN cp_factura cxp_fact ON (cxp_fact_iva.id_factura = cxp_fact.id_factura)
					WHERE cxp_fact.id_empresa = query.id_empresa
						AND cxp_fact.id_proveedor = prov.id_proveedor
						AND cxp_fact.id_modulo IN (".$idModulos.")
						AND cxp_fact.fecha_origen BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
						AND cxp_fact.estatus_factura IN (1)), 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto)
					FROM cp_factura_gasto cxp_fact_gasto
						INNER JOIN cp_factura cxp_fact ON (cxp_fact_gasto.id_factura = cxp_fact.id_factura)
					WHERE cxp_fact_gasto.id_modo_gasto IN (1,3)
						AND cxp_fact.id_empresa = query.id_empresa
						AND cxp_fact.id_proveedor = prov.id_proveedor
						AND cxp_fact.id_modulo IN (".$idModulos.")
						AND cxp_fact.fecha_origen BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
						AND cxp_fact.estatus_factura IN (1)), 0)) AS total_factura,
		
		(IFNULL((SELECT SUM(cxp_nd.subtotal_notacargo - cxp_nd.subtotal_descuento_notacargo) FROM cp_notadecargo cxp_nd
				WHERE cxp_nd.id_empresa = query.id_empresa
					AND cxp_nd.id_proveedor = prov.id_proveedor
					AND cxp_nd.id_modulo IN (".$idModulos.")
					AND cxp_nd.fecha_origen_notacargo BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
					AND cxp_nd.estatus_notacargo IN (1)), 0)
			+ IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva)
					FROM cp_notadecargo cxp_nd
						INNER JOIN cp_notacargo_iva cxp_nd_iva ON (cxp_nd.id_notacargo = cxp_nd_iva.id_notacargo)
					WHERE cxp_nd.id_empresa = query.id_empresa
						AND cxp_nd.id_proveedor = prov.id_proveedor
						AND cxp_nd.id_modulo IN (".$idModulos.")
						AND cxp_nd.fecha_origen_notacargo BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
						AND cxp_nd.estatus_notacargo IN (1)), 0)
			+ IFNULL((SELECT SUM(cxp_nd_gasto.monto)
					FROM cp_notadecargo cxp_nd
						INNER JOIN cp_notacargo_gastos cxp_nd_gasto ON (cxp_nd.id_notacargo = cxp_nd_gasto.id_notacargo)
					WHERE cxp_nd_gasto.id_modo_gasto IN (1,3)
						AND cxp_nd.id_empresa = query.id_empresa
						AND cxp_nd.id_proveedor = prov.id_proveedor
						AND cxp_nd.id_modulo IN (".$idModulos.")
						AND cxp_nd.fecha_origen_notacargo BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
						AND cxp_nd.estatus_notacargo IN (1)), 0)) AS total_nota_cargo,
		
		IFNULL((SELECT SUM(cxp_ant.total) FROM cp_anticipo cxp_ant
				WHERE cxp_ant.id_empresa = query.id_empresa
					AND cxp_ant.id_proveedor = prov.id_proveedor
					AND cxp_ant.idDepartamento IN (".$idModulos.")
					AND cxp_ant.fechaanticipo BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
					AND cxp_ant.estado IN (3)), 0) AS total_anticipo,
		
		(IFNULL((SELECT SUM(cxp_nc.subtotal_notacredito - cxp_nc.subtotal_descuento) FROM cp_notacredito cxp_nc
				WHERE cxp_nc.id_empresa = query.id_empresa
					AND cxp_nc.id_proveedor = prov.id_proveedor
					AND cxp_nc.id_departamento_notacredito IN (".$idModulos.")
					AND cxp_nc.fecha_registro_notacredito BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
					AND cxp_nc.estado_notacredito IN (3)), 0)
			+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito)
					FROM cp_notacredito cxp_nc
						INNER JOIN cp_notacredito_iva cxp_nc_iva ON (cxp_nc.id_notacredito = cxp_nc_iva.id_notacredito)
					WHERE cxp_nc.id_empresa = query.id_empresa
						AND cxp_nc.id_proveedor = prov.id_proveedor
						AND cxp_nc.id_departamento_notacredito IN (".$idModulos.")
						AND cxp_nc.fecha_registro_notacredito BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
						AND cxp_nc.estado_notacredito IN (3)), 0)
			+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito)
					FROM cp_notacredito cxp_nc
						INNER JOIN cp_notacredito_gastos cxp_nc_gasto ON (cxp_nc.id_notacredito = cxp_nc_gasto.id_notacredito)
					WHERE cxp_nc_gasto.id_modo_gasto IN (1,3)
						AND cxp_nc.id_empresa = query.id_empresa
						AND cxp_nc.id_proveedor = prov.id_proveedor
						AND cxp_nc.id_departamento_notacredito IN (".$idModulos.")
						AND cxp_nc.fecha_registro_notacredito BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
						AND cxp_nc.estado_notacredito IN (3)), 0)) AS total_nota_credito
	FROM cp_proveedor prov
		INNER JOIN (
			SELECT
				cxp_fact.id_empresa AS id_empresa,
				cxp_fact.id_proveedor AS id_proveedor
			FROM cp_factura cxp_fact ".$sqlBusq."
				AND cxp_fact.fecha_origen BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
				AND cxp_fact.estatus_factura IN (1)
				
			UNION
			
			SELECT
				cxp_nd.id_empresa,
				cxp_nd.id_proveedor
			FROM cp_notadecargo cxp_nd ".$sqlBusq2."
				AND cxp_nd.fecha_origen_notacargo BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
				AND cxp_nd.estatus_notacargo IN (1)
				
			UNION
			
			SELECT
				cxp_ant.id_empresa,
				cxp_ant.id_proveedor
			FROM cp_anticipo cxp_ant ".$sqlBusq3."
				AND cxp_ant.fechaanticipo BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
				AND cxp_ant.estado IN (3)
			
			UNION
			
			SELECT
				cxp_nc.id_empresa,
				cxp_nc.id_proveedor
			FROM cp_notacredito cxp_nc ".$sqlBusq4."
				AND cxp_nc.fecha_registro_notacredito BETWEEN '".date('Y-m-d', strtotime($txtFechaDesde))."' AND '".date('Y-m-d', strtotime($txtFechaHasta))."'
				AND cxp_nc.estado_notacredito IN (3)
			
			GROUP BY 1,2) AS query ON (query.id_proveedor = prov.id_proveedor)";
	
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
		$htmlTh .= ordenarCampo("xajax_listaECGeneral", "4%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaECGeneral", "32%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaECGeneral", "12%", $pageNum, "total_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Factura<br>(Estatus: Cancelado)");
		$htmlTh .= ordenarCampo("xajax_listaECGeneral", "12%", $pageNum, "total_nota_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nota de Débito<br>(Estatus: Cancelado)");
		$htmlTh .= ordenarCampo("xajax_listaECGeneral", "12%", $pageNum, "total_anticipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Anticipo<br>(Estatus: Asignado)");
		$htmlTh .= ordenarCampo("xajax_listaECGeneral", "12%", $pageNum, "total_nota_credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Nota Credito<br>(Estatus: Asignado)");
		$htmlTh .= "<td width=\"12%\">"."Total"."</td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_array($rsLimit)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$totalProveedor = $row['total_factura'] + $row['total_nota_cargo'] - $row['total_anticipo'] - $row['total_nota_credito'];
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_factura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_nota_cargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_anticipo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_nota_credito'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalProveedor, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[4] += $row['total_factura'];
		$arrayTotal[5] += $row['total_nota_cargo'];
		$arrayTotal[6] += $row['total_anticipo'];
		$arrayTotal[7] += $row['total_nota_credito'];
		$arrayTotal[8] += $totalProveedor;
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"3\">Totales:</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[4], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[5], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[6], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[7], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[8], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}

	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
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

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"listaECGeneral");
?>