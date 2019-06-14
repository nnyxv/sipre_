<?php


function buscarUnidadBasica($frmBuscar) {
	$objResponse = new xajaxResponse();
		
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstAlmacen'],
		$frmBuscar['lstModalidad'],
		$frmBuscar['lstModelo'],
		$frmBuscar['lstUnidadBasica'],
		$frmBuscar['lstEstadoVenta']);
	
	$objResponse->loadCommands(listaExistencia(0, "vw_iv_modelo.nom_uni_bas", "ASC", $valBusq));
	$objResponse->loadCommands(listaResumenExistencia(0, "vw_iv_modelo.nom_uni_bas", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstAlmacen($idEmpresa = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$query = sprintf("SELECT * FROM an_almacen %s ORDER BY nom_almacen", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstAlmacen\" name=\"lstAlmacen\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_almacen']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\">".utf8_encode($row['nom_almacen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAlmacen","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModelo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_modelo ORDER BY nom_modelo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstModelo\" name=\"lstModelo\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstUnidadBasica(this.value); byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modelo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modelo']."\">".utf8_encode($row['nom_modelo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModelo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstUnidadBasica($idModelo = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModelo != "-1" && $idModelo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("mod_uni_bas = %s",
			valTpDato($idModelo, "int"));
	}
	
	$query = sprintf("SELECT * FROM an_uni_bas %s ORDER BY nom_uni_bas", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstUnidadBasica\" name=\"lstUnidadBasica\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_uni_bas']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_uni_bas']."\">".utf8_encode($row['nom_uni_bas'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstUnidadBasica","innerHTML",$html);
	
	return $objResponse;
}

function listaExistencia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 1000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta NOT IN ('VENDIDO', 'PRESTADO', 'ACTIVO FIJO', 'INTERCAMBIO', 'DEVUELTO', 'ERROR EN TRASPASO')");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo IS NOT NULL");

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = alm.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_bas.id_uni_bas = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$sqlBusq .= sprintf(" AND uni_fis.estado_venta = %s",
			valTpDato($valCadBusq[5], "text"));
	}
	
	if ($valCadBusq[2] == "Unidad Básica") {
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusqAlm) > 0) ? " AND " : " AND ";
			$sqlBusqAlm .= $cond.sprintf("uni_fis.id_almacen LIKE %s",
				valTpDato($valCadBusq[1], "int"));
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_unidad_fisica) FROM vw_an_existencia_lista
		WHERE id_modelo = vw_iv_modelo.id_modelo
			AND id_uni_bas = uni_bas.id_uni_bas
			AND id_unidad_fisica = uni_fis.id_unidad_fisica
			AND estado_venta = uni_fis.estado_venta %s) > 0", $sqlBusqAlm);
		
		$query = sprintf("SELECT 
			uni_bas.id_uni_bas,
			vw_iv_modelo.nom_uni_bas,
			vw_iv_modelo.id_marca,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			vw_iv_modelo.nom_modelo,
			transmision.id_transmision,
			transmision.nom_transmision,
			CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
			uni_fis.estado_venta
		FROM an_uni_bas uni_bas
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas)
			LEFT JOIN an_unidad_fisica uni_fis ON (uni_bas.id_uni_bas = uni_fis.id_uni_bas)
			INNER JOIN an_transmision transmision ON (uni_bas.trs_uni_bas = transmision.id_transmision)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen) %s
		GROUP BY uni_bas.id_uni_bas,
			vw_iv_modelo.nom_uni_bas,
			vw_iv_modelo.id_marca,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			vw_iv_modelo.nom_modelo,
			transmision.id_transmision,
			transmision.nom_transmision,
			CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version),
			uni_fis.estado_venta", $sqlBusq);
			
	} else if ($valCadBusq[2] == "Modelo") {
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusqAlm) > 0) ? " AND " : " AND ";
			$sqlBusqAlm .= $cond.sprintf("uni_fis.id_almacen LIKE %s",
				valTpDato($valCadBusq[1], "int"));
		}
	
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_unidad_fisica) FROM vw_an_existencia_lista
		WHERE id_modelo = vw_iv_modelo.id_modelo
			AND id_uni_bas = uni_bas.id_uni_bas
			AND id_unidad_fisica = uni_fis.id_unidad_fisica
			AND estado_venta = uni_fis.estado_venta %s) > 0", $sqlBusqAlm);
		
		$query = sprintf("SELECT 
			vw_iv_modelo.id_marca,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			vw_iv_modelo.nom_modelo,
			uni_fis.estado_venta
		FROM an_uni_bas uni_bas
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas)
			LEFT JOIN an_unidad_fisica uni_fis ON (uni_bas.id_uni_bas = uni_fis.id_uni_bas)
			INNER JOIN an_transmision transmision ON (uni_bas.trs_uni_bas = transmision.id_transmision)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen) %s
		GROUP BY vw_iv_modelo.id_marca,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			vw_iv_modelo.nom_modelo,
			uni_fis.estado_venta", $sqlBusq);
	}
		
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
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("id_modelo = %s",
			valTpDato($row['id_modelo'], "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("estado_venta = %s",
			valTpDato($row['estado_venta'], "text"));
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("id_almacen = %s",
				valTpDato($valCadBusq[1], "int"));
		}
		
		if ($valCadBusq[2] == "Unidad Básica") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("id_uni_bas = %s",
				valTpDato($row['id_uni_bas'], "int"));
		}
		
		$queryUniFis = sprintf("SELECT *,
			(IFNULL((SELECT SUM(fact_comp_det_unidad.costo_unitario)
			FROM cp_factura_detalle_unidad fact_comp_det_unidad
				INNER JOIN an_unidad_fisica uni_fis ON (fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad)
			WHERE uni_fis.id_unidad_fisica = vw_an_exist_lista.id_unidad_fisica), 0)
			+
			IFNULL((SELECT SUM(fact_comp_det_acc.costo_unitario)
			FROM cp_factura_detalle_unidad fact_comp_det_unidad
				INNER JOIN cp_factura_detalle_accesorio fact_comp_det_acc ON (fact_comp_det_unidad.id_factura = fact_comp_det_acc.id_factura)
				INNER JOIN an_unidad_fisica uni_fis ON (fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad)
			WHERE uni_fis.id_unidad_fisica = vw_an_exist_lista.id_unidad_fisica), 0)) AS total_costo
		FROM vw_an_existencia_lista vw_an_exist_lista %s", $sqlBusq2);
		$rsUniFis = mysql_query($queryUniFis);
		if (!$rsUniFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsUniFis = mysql_num_rows($rsUniFis);
		
		switch ($valCadBusq[2]) {
			case "Unidad Básica" : $campoVehiculo = $row['vehiculo']; break;
			case "Modelo" : $campoVehiculo = $row['nom_modelo']; break;
		}
		$campoVehiculo .= "<br><span class=\"textoNegroNegrita_10px\">(".valCrtrEsp($row['estado_venta']).")</span>";
		
		$htmlTb .= "<tr>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
					$htmlTb .= "<tr class=\"tituloColumna\">
							<td width=\"6%\">Nro. Factura Compra</td>
							<td width=\"7%\">F. Compra</td>
							<td width=\"7%\">F. Recep. PDI</td>
							<td width=\"7%\">Unidad Básica</td>
							<td width=\"3%\">Año</td>
							<td width=\"11%\">".$spanSerialCarroceria."</td>
							<td width=\"5%\">".$spanPlaca."</td>
							<td width=\"6%\">Trsm</td>
							<td width=\"6%\">Color Ext.</td>
							<td width=\"3%\">Días</td>
							<td width=\"7%\">Costo</td>
							<td width=\"7%\">Estado Venta</td>
							<td width=\"10%\">Vendedor</td>
							<td width=\"8%\">Almacén</td>
					</tr>";
					$arrayTotal = NULL;
					$contFila2 = 0;
					while ($rowUniFis = mysql_fetch_assoc($rsUniFis)) {
						$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
						$contFila2++;
						
						$campoModelo = ($valCadBusq[2] == "Modelo") ? $rowUniFis['nom_modelo'] : $rowUniFis['nom_uni_bas'];
						
						$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
							$htmlTb .= sprintf("<td align=\"right\">%s</td>", utf8_encode($rowUniFis['numero_factura_proveedor']));
							$htmlTb .= sprintf("<td align=\"center\">%s</td>", utf8_encode(date(spanDateFormat, strtotime($rowUniFis['fecha_factura_proveedor']))));
							$htmlTb .= sprintf("<td align=\"center\">%s</td>", ($rowUniFis['fecha_registro_pdi'] != "") ? utf8_encode(date(spanDateFormat, strtotime($rowUniFis['fecha_registro_pdi']))) : "");
							$htmlTb .= sprintf("<td>%s</td>", utf8_encode($campoModelo));
							$htmlTb .= sprintf("<td align=\"center\">%s</td>", utf8_encode($rowUniFis['nom_ano']));
							$htmlTb .= sprintf("<td align=\"center\">%s</td>", utf8_encode($rowUniFis['serial_carroceria']));
							$htmlTb .= sprintf("<td align=\"center\" nowrap=\"nowrap\">%s</td>", utf8_encode($rowUniFis['placa']));
							$htmlTb .= sprintf("<td>%s</td>", valCrtrEsp(utf8_encode($rowUniFis['nom_transmision'])));
							$htmlTb .= sprintf("<td>%s</td>", utf8_encode($rowUniFis['nom_color_ext1']));
							$htmlTb .= sprintf("<td align=\"right\">%s</td>", utf8_encode($rowUniFis['dias_inventario']));
							$htmlTb .= sprintf("<td align=\"right\">%s</td>", number_format($rowUniFis['total_costo'], 2, ".", ","));
							$htmlTb .= sprintf("<td align=\"center\">%s</td>", utf8_encode($rowUniFis['estado_venta']));
							$htmlTb .= sprintf("<td class=\"texto_9px\">%s</td>", utf8_encode($rowUniFis['nombre_empleado']." ".$rowUniFis['apellido_empleado_vendedor']));
							$htmlTb .= sprintf("<td align=\"center\">%s</td>", utf8_encode($rowUniFis['nom_almacen']));
						$htmlTb .= "</tr>";
						
						$arrayTotal[1] += $contFila2;
						$arrayTotal[2] += $rowUniFis['total_costo'];
					}
					
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\">";
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"3\">Subtotal:</td>";
					$htmlTb .= sprintf("<td align=\"left\" colspan=\"3\">%s</td>", $campoVehiculo);
					$htmlTb .= "<td class=\"tituloCampo\" colspan=\"2\">Clv. Veh.:</td>";
					$htmlTb .= sprintf("<td colspan=\"2\">%s</td>", number_format($arrayTotal[1], 2, ".", ","));
					$htmlTb .= sprintf("<td>%s</td>", number_format($arrayTotal[2], 2, ".", ","));
					$htmlTb .= "<td colspan=\"4\"></td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		if ($contFila < $totalRows) {
			$htmlTb .= "<tr>";
				$htmlTb .= "<td>&nbsp;</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoExistencia","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaResumenExistencia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 1000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta NOT IN ('VENDIDO', 'PRESTADO', 'ACTIVO FIJO', 'INTERCAMBIO', 'DEVUELTO', 'ERROR EN TRASPASO')");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo IS NOT NULL");

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_bas.id_uni_bas = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_venta LIKE %s",
			valTpDato($valCadBusq[5], "text"));
	}
	
	if ($valCadBusq[2] == "Unidad Básica") {
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusqAlm) > 0) ? " AND " : " AND ";
			$sqlBusqAlm .= $cond.sprintf("uni_fis.id_almacen LIKE %s",
				valTpDato($valCadBusq[1], "int"));
		}
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_unidad_fisica) FROM vw_an_existencia_lista
		WHERE id_modelo = vw_iv_modelo.id_modelo
			AND id_uni_bas = uni_bas.id_uni_bas
			AND id_unidad_fisica = uni_fis.id_unidad_fisica
			AND estado_venta = uni_fis.estado_venta %s) > 0", $sqlBusqAlm);
		
		$query = sprintf("SELECT 
			uni_bas.id_uni_bas,
			vw_iv_modelo.nom_uni_bas,
			vw_iv_modelo.id_marca,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			vw_iv_modelo.nom_modelo,
			transmision.id_transmision,
			transmision.nom_transmision,
			CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
			COUNT(vw_iv_modelo.id_uni_bas) AS cantidad_vehiculos,
			
			SUM((IFNULL((SELECT SUM(fact_comp_det_unidad.costo_unitario)
			FROM cp_factura_detalle_unidad fact_comp_det_unidad
			WHERE fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad), 0)
			+
			IFNULL((SELECT SUM(fact_comp_det_acc.costo_unitario)
			FROM cp_factura_detalle_unidad fact_comp_det_unidad
				INNER JOIN cp_factura_detalle_accesorio fact_comp_det_acc ON (fact_comp_det_unidad.id_factura = fact_comp_det_acc.id_factura)
			WHERE fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad), 0))) AS total_costo,
			
			SUM(uni_bas.pvp_venta1) AS total_venta,
			uni_fis.estado_venta
		FROM an_uni_bas uni_bas
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas)
			LEFT JOIN an_unidad_fisica uni_fis ON (uni_bas.id_uni_bas = uni_fis.id_uni_bas)
			INNER JOIN an_transmision transmision ON (uni_bas.trs_uni_bas = transmision.id_transmision)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen) %s
		GROUP BY uni_bas.id_uni_bas,
			vw_iv_modelo.nom_uni_bas,
			vw_iv_modelo.id_marca,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			vw_iv_modelo.nom_modelo,
			CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version),
			transmision.id_transmision,
			transmision.nom_transmision,
			uni_fis.estado_venta", $sqlBusq);
	} else if ($valCadBusq[2] == "Modelo") {
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusqAlm) > 0) ? " AND " : " AND ";
			$sqlBusqAlm .= $cond.sprintf("uni_fis.id_almacen LIKE %s",
				valTpDato($valCadBusq[1], "int"));
		}
	
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT COUNT(id_unidad_fisica) FROM vw_an_existencia_lista
		WHERE id_modelo = vw_iv_modelo.id_modelo
			AND id_uni_bas = uni_bas.id_uni_bas
			AND id_unidad_fisica = uni_fis.id_unidad_fisica
			AND estado_venta = uni_fis.estado_venta %s) > 0", $sqlBusqAlm);
		
		$query = sprintf("SELECT 
			vw_iv_modelo.id_marca,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			vw_iv_modelo.nom_modelo,
			COUNT(vw_iv_modelo.id_uni_bas) AS cantidad_vehiculos,
			
			SUM((IFNULL((SELECT SUM(fact_comp_det_unidad.costo_unitario)
			FROM cp_factura_detalle_unidad fact_comp_det_unidad
			WHERE fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad), 0)
			+
			IFNULL((SELECT SUM(fact_comp_det_acc.costo_unitario)
			FROM cp_factura_detalle_unidad fact_comp_det_unidad
				INNER JOIN cp_factura_detalle_accesorio fact_comp_det_acc ON (fact_comp_det_unidad.id_factura = fact_comp_det_acc.id_factura)
			WHERE fact_comp_det_unidad.id_factura_detalle_unidad = uni_fis.id_factura_compra_detalle_unidad), 0))) AS total_costo,
			
			SUM(uni_bas.pvp_venta1) AS total_venta,
			uni_fis.estado_venta
		FROM an_uni_bas uni_bas
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas)
			LEFT JOIN an_unidad_fisica uni_fis ON (uni_bas.id_uni_bas = uni_fis.id_uni_bas)
			INNER JOIN an_transmision transmision ON (uni_bas.trs_uni_bas = transmision.id_transmision)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen) %s
		GROUP BY vw_iv_modelo.id_marca,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			vw_iv_modelo.nom_modelo,
			uni_fis.estado_venta", $sqlBusq);
	}
		
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
		$htmlTh .= ordenarCampo("xajax_listaResumenExistencia", "60%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, ("Unidad Básica"));
		$htmlTh .= ordenarCampo("xajax_listaResumenExistencia", "10%", $pageNum, "cantidad_vehiculos", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cantidad"));
		$htmlTh .= ordenarCampo("xajax_listaResumenExistencia", "15%", $pageNum, "total_costo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Valor Costo"));
		$htmlTh .= ordenarCampo("xajax_listaResumenExistencia", "15%", $pageNum, "total_venta", $campOrd, $tpOrd, $valBusq, $maxRows, ("Valor Precio"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($valCadBusq[2]) {
			case "Unidad Básica" : $campoVehiculo = utf8_encode($row['vehiculo']); break;
			case "Modelo" : $campoVehiculo = utf8_encode($row['nom_modelo']); break;
		}
		$campoVehiculo .= "<br><span class=\"textoNegroNegrita_10px\">(".valCrtrEsp($row['estado_venta']).")</span>";
			
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td>%s</td>", $campoVehiculo);
			$htmlTb .= sprintf("<td align=\"right\">%s</td>", utf8_encode($row['cantidad_vehiculos']));
			$htmlTb .= sprintf("<td align=\"right\">%s</td>", utf8_encode(number_format($row['total_costo'], 2, ".", ",")));
			$htmlTb .= sprintf("<td align=\"right\">%s</td>", utf8_encode(number_format($row['total_venta'], 2, ".", ",")));
		$htmlTb .= "</tr>";
		
		$arrayTotal[1] += $row['cantidad_vehiculos'];
		$arrayTotal[2] += $row['total_costo'];
		$arrayTotal[3] += $row['total_venta'];
	}
	
	$htmlTf .= "<tr align=\"right\" class=\"trResaltarTotal\">";
		$htmlTf .= "<td class=\"tituloCampo\">Gran Total:</td>";
		$htmlTf .= sprintf("<td>%s</td>", number_format($arrayTotal[1], 2, ".", ","));
		$htmlTf .= sprintf("<td>%s</td>", number_format($arrayTotal[2], 2, ".", ","));
		$htmlTf .= sprintf("<td>%s</td>", number_format($arrayTotal[3], 2, ".", ","));
	$htmlTf .= "</tr>";
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoResumenExistencia","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacen");
$xajax->register(XAJAX_FUNCTION,"cargaLstModelo");
$xajax->register(XAJAX_FUNCTION,"cargaLstUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"listaExistencia");
$xajax->register(XAJAX_FUNCTION,"listaResumenExistencia");
?>