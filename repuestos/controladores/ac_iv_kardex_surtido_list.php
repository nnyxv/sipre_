<?php


function buscarKardexSurtido($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoReciboDevuelto'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaKardexSurtido(0, "kardex_surtido.fecha_movimiento", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM iv_kardex_surtido kardex_surtido
		INNER JOIN vw_pg_empleados empleado ON (kardex_surtido.id_empleado_recibe = empleado.id_empleado)
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

function listaKardexSurtido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 1, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("orden.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(kardex_surtido.fecha_movimiento) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("kardex_surtido.id_empleado_recibe = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[4], "text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.descripcion LIKE %s
		OR kardex_surtido.id_solicitud LIKE %s
		OR sol_rep.numero_solicitud LIKE %s
		OR (SELECT COUNT(sol_rep.id_orden) FROM sa_solicitud_repuestos sol_rep
			WHERE sol_rep.id_solicitud = kardex_surtido.id_solicitud
				AND sol_rep.id_orden LIKE %s) > 0
		OR orden.numero_orden LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT 
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion
	FROM iv_kardex_surtido kardex_surtido
		INNER JOIN sa_solicitud_repuestos sol_rep ON (kardex_surtido.id_solicitud = sol_rep.id_solicitud)
		INNER JOIN sa_orden orden ON (sol_rep.id_orden = orden.id_orden)
		INNER JOIN sa_estado_solicitud estado_sol ON (kardex_surtido.id_estado_solicitud = estado_sol.id_estado_solicitud)
		INNER JOIN iv_articulos art ON (kardex_surtido.id_articulo = art.id_articulo) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$htmlTh = "<tr align=\"left\" height=\"22\">";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">".("Código:")."</a></td>";
			$htmlTh .= "<td colspan=\"9\">".elimCaracter(htmlentities($row['codigo_articulo']),";")."</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"left\" height=\"22\">";
			$htmlTh .= "<td align=\"right\" class=\"tituloCampo\" colspan=\"2\">".("Descripción:")."</td>";
			$htmlTh .= "<td colspan=\"9\">".htmlentities($row['descripcion'])."</td>";
		$htmlTh .= "</tr>";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td></td>";
			$htmlTh .= "<td width=\"8%\">Fecha</td>";
			$htmlTh .= "<td width=\"14%\">Empresa</td>";
			$htmlTh .= "<td width=\"12%\">Ubicacion</td>";
			$htmlTh .= "<td width=\"8%\">T</td>";
			$htmlTh .= "<td width=\"8%\">Nro. Solicitud</td>";
			$htmlTh .= "<td width=\"8%\">Nro. Orden</td>";
			$htmlTh .= "<td width=\"10%\">C/P/M</td>";
			$htmlTh .= "<td width=\"8%\">E/S</td>";
			$htmlTh .= "<td width=\"12%\">Despachado por / Devuelto a</td>";
			$htmlTh .= "<td width=\"12%\">Despachado a / Devuelto por</td>";
		$htmlTh .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("kardex_surtido.id_articulo = %s",
			valTpDato($row['id_articulo'], "int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("orden.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("DATE(kardex_surtido.fecha_movimiento) BETWEEN %s AND %s",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("kardex_surtido.id_empleado_recibe = %s",
				valTpDato($valCadBusq[3], "int"));
		}
		
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("((SELECT COUNT(id_articulo) FROM iv_articulos art WHERE art.descripcion LIKE %s) > 0
			OR kardex_surtido.id_solicitud LIKE %s
			OR sol_rep.numero_solicitud LIKE %s
			OR orden.id_orden LIKE %s
			OR orden.numero_orden LIKE %s)",
				valTpDato("%".$valCadBusq[5]."%", "text"),
				valTpDato("%".$valCadBusq[5]."%", "text"),
				valTpDato("%".$valCadBusq[5]."%", "text"),
				valTpDato("%".$valCadBusq[5]."%", "text"),
				valTpDato("%".$valCadBusq[5]."%", "text"));
		}
		
		$queryKardexSurtido = sprintf("SELECT 
			kardex_surtido.id_kardex_surtido,
			kardex_surtido.id_solicitud,
			sol_rep.numero_solicitud,
			orden.id_orden,
			orden.numero_orden,
			(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido) FROM cj_cc_cliente cliente
			WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago FROM sa_recepcion r
											WHERE r.id_recepcion = orden.id_recepcion), (SELECT c.id_cliente_contacto AS id_cliente_contacto FROM sa_cita c
																			WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																							FROM sa_recepcion r
																							WHERE r.id_recepcion = orden.id_recepcion))) AS id_cliente)) AS nombre_cliente,
			(SELECT CONCAT_WS('-', cliente.lci, cliente.ci) FROM cj_cc_cliente cliente
			WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago FROM sa_recepcion r
											WHERE r.id_recepcion = orden.id_recepcion), (SELECT c.id_cliente_contacto AS id_cliente_contacto FROM sa_cita c
																			WHERE c.id_cita = (SELECT r.id_cita AS id_cita FROM sa_recepcion r
																							WHERE r.id_recepcion = orden.id_recepcion))) AS id_cliente)) AS ci_cliente,
			kardex_surtido.id_estado_solicitud,
			estado_sol.descripcion_estado_solicitud,
			kardex_surtido.fecha_movimiento,
			(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = kardex_surtido.id_empleado_despacha) AS nombre_empleado_entrega,
			(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = kardex_surtido.id_empleado_recibe) AS nombre_empleado_recibo_devuelto,
			kardex_surtido.id_casilla,
			kardex_surtido.id_articulo_costo,
			vw_iv_casilla.descripcion_almacen,
			vw_iv_casilla.ubicacion,
			SUM(kardex_surtido.cantidad) AS cantidad,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM iv_kardex_surtido kardex_surtido
			INNER JOIN sa_solicitud_repuestos sol_rep ON (kardex_surtido.id_solicitud = sol_rep.id_solicitud)
			INNER JOIN sa_orden orden ON (sol_rep.id_orden = orden.id_orden)
			INNER JOIN sa_estado_solicitud estado_sol ON (kardex_surtido.id_estado_solicitud = estado_sol.id_estado_solicitud)
			INNER JOIN vw_iv_casillas vw_iv_casilla ON (kardex_surtido.id_casilla = vw_iv_casilla.id_casilla)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (orden.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		GROUP BY kardex_surtido.id_solicitud,
			orden.id_orden,
			kardex_surtido.id_estado_solicitud,
			estado_sol.descripcion_estado_solicitud,
			kardex_surtido.fecha_movimiento,
			kardex_surtido.id_casilla,
			vw_iv_casilla.descripcion_almacen,
			vw_iv_casilla.ubicacion
		ORDER BY kardex_surtido.id_kardex_surtido ASC", $sqlBusq2);
		$rsKardexSurtido = mysql_query($queryKardexSurtido);
		if (!$rsKardexSurtido) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowKardexSurtido = mysql_fetch_assoc($rsKardexSurtido)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			switch ($rowKardexSurtido['id_estado_solicitud']) {
				case 3 : $totalCantEntrada += $rowKardexSurtido['cantidad']; break;
				case 4 : $totalCantSalida += $rowKardexSurtido['cantidad']; break;
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\" title=\"".$rowKardexSurtido['id_kardex_surtido']."\">".$contFila."</td>";
				$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($rowKardexSurtido['fecha_movimiento']))."</td>";
				$htmlTb .= "<td>".htmlentities($rowKardexSurtido['nombre_empresa'])."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\" title=\"".$rowKardexSurtido['id_casilla']."\">";
					$htmlTb .= "<span class=\"textoNegrita_10px\">".htmlentities(strtoupper($rowKardexSurtido['descripcion_almacen']))."</span>";
					$htmlTb .= "<br>";
					$htmlTb .= utf8_encode(str_replace("-[]", "", $rowKardexSurtido['ubicacion']));
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\">".$rowKardexSurtido['descripcion_estado_solicitud']."</td>";
				$htmlTb .= "<td align=\"right\">".$rowKardexSurtido['numero_solicitud']."</td>";
				$htmlTb .= "<td align=\"right\">".$rowKardexSurtido['numero_orden']."</td>";
				$htmlTb .= "<td align=\"right\" title=\"".htmlentities($rowKardexSurtido['nombre_cliente'])."\">".$rowKardexSurtido['ci_cliente']."</td>";
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td>".number_format($rowKardexSurtido['cantidad'], 2, ".", ",")."</td>";
					$htmlTb .= "</tr>";
						$htmlTb .= ($rowKardexSurtido['id_articulo_costo'] > 0) ? "<tr><td><span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$rowKardexSurtido['id_articulo_costo']."</span></td><tr>" : "";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".$rowKardexSurtido['nombre_empleado_entrega']."</td>";
				$htmlTb .= "<td>".$rowKardexSurtido['nombre_empleado_recibo_devuelto']."</td>";
			$htmlTb .= "</tr>";
		}
		
		$htmlTb .= "<tr class=\"trResaltarTotal3\" height=\"22\">";
			$htmlTb .= "<td align=\"right\" colspan=\"7\" class=\"tituloCampo\">Totales:<br>".elimCaracter(htmlentities($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td align=\"right\" class=\"tituloColumna\">E #:<br>S #:</td>";
			$htmlTb .= "<td align=\"right\">".number_format($totalCantEntrada, 2, ".", ",")."<br>".number_format($totalCantSalida, 2, ".", ",")."</td>";
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td></td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardexSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardexSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaKardexSurtido(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardexSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaKardexSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaKardexSurtido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarKardexSurtido");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaKardexSurtido");
?>