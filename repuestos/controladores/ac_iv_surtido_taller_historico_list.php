<?php


function buscarSolicitud($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstadoSolicitud'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaSolicitud(0, "id_solicitud", "DESC", $valBusq));
		
	return $objResponse;
}

function cargaLstEstatusSolicitud($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM sa_estado_solicitud");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstEstadoSolicitud\" name=\"lstEstadoSolicitud\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_estado_solicitud']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_estado_solicitud']."\">".utf8_encode($row['descripcion_estado_solicitud'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstadoSolicitud","innerHTML",$html);
	
	return $objResponse;
}

function formSolicitudRepuesto($idSolicitud, $idAccionSolicitud) {
	$objResponse = new xajaxResponse();
	
	// BUSCA LOS DATOS DE LA SOLICITUD
	$querySolicitud = sprintf("SELECT
		orden.id_empresa,
		orden.id_orden,
		orden.numero_orden,
		orden.tiempo_orden,
		orden.id_estado_orden,
		estado_orden.nombre_estado,
		tp_orden.nombre_tipo_orden,
		uni_bas.imagen_auto,
		vw_sa_vale_recep.chasis,
		vw_sa_vale_recep.placa,
		sol_rep.id_solicitud,
		sol_rep.numero_solicitud,
		sol_rep.tiempo_solicitud,
		sol_rep.id_empleado_entrega,
		sol_rep.id_jefe_taller,
		sol_rep.id_empleado_devuelto,
		sol_rep.id_empleado_recibo,
		sol_rep.id_empleado_recibo,
		estado_sol.id_estado_solicitud,
		estado_sol.descripcion_estado_solicitud,
	
		(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
		FROM cj_cc_cliente cliente
		WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = orden.id_recepcion), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																		FROM sa_cita c
																		WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																					FROM sa_recepcion r
																					WHERE r.id_recepcion = orden.id_recepcion))) AS id_cliente)) AS nombre_cliente,
		
		CONCAT_WS(', ', CONCAT_WS(' ', 'Motor', ccc_uni_bas), CONCAT_WS(' ', cil_uni_bas, 'Cilindros')) AS motor,
		
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_empleado_entrega) AS nombre_empleado_entrega,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_jefe_taller) AS nombre_empleado_jefe_taller,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_jefe_repuesto) AS nombre_empleado_jefe_repuestos,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_gerente_postventa) AS nombre_empleado_gte_postventa,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_empleado_recibo) AS nombre_empleado_recibo,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_empleado_devuelto) AS nombre_empleado_devuelto
	FROM sa_tipo_orden tp_orden
		INNER JOIN sa_orden orden ON (tp_orden.id_tipo_orden = orden.id_tipo_orden)
		INNER JOIN sa_estado_orden estado_orden ON (orden.id_estado_orden = estado_orden.id_estado_orden)
		INNER JOIN sa_solicitud_repuestos sol_rep ON (orden.id_orden = sol_rep.id_orden)
		INNER JOIN sa_estado_solicitud estado_sol ON (sol_rep.estado_solicitud = estado_sol.id_estado_solicitud)
		INNER JOIN vw_sa_vales_recepcion vw_sa_vale_recep ON (orden.id_recepcion = vw_sa_vale_recep.id_recepcion)
		INNER JOIN an_uni_bas uni_bas ON (vw_sa_vale_recep.id_uni_bas = uni_bas.id_uni_bas)
	WHERE sol_rep.id_solicitud = %s;",
		valTpDato($idSolicitud, "int"));
	$rsSolicitud = mysql_query($querySolicitud);
	if (!$rsSolicitud) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowSolicitud = mysql_fetch_assoc($rsSolicitud);
	
	// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
	$imgFoto = (!file_exists($rowSolicitud['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $rowSolicitud['imagen_auto'];
	
	$objResponse->assign("imgUnidad","src",$imgFoto);
	$objResponse->assign("txtMotor","value",utf8_encode($rowSolicitud['motor']));
	$objResponse->assign("txtChasis","value",utf8_encode($rowSolicitud['chasis']));
	$objResponse->assign("txtPlaca","value",utf8_encode($rowSolicitud['placa']));
	
	$objResponse->assign("txtCliente","value",utf8_encode($rowSolicitud['nombre_cliente']));
	$objResponse->assign("hddIdSolicitud","value",$rowSolicitud['id_solicitud']);
	$objResponse->assign("txtNumeroSolicitud","value",$rowSolicitud['numero_solicitud']);
	$objResponse->assign("txtFechaSolicitud","value",date(spanDateFormat,strtotime($rowSolicitud['tiempo_solicitud'])));
	$objResponse->assign("hddIdEstadoSolicitud","value",$rowSolicitud['id_estado_solicitud']);
	$objResponse->assign("txtEstadoSolicitud","value",strtoupper($rowSolicitud['descripcion_estado_solicitud']));
	
	if ((strlen($idAccionSolicitud) > 0 && !($rowSolicitud['id_empleado_entrega'] > 0))) {
		// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
		$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios vw_iv_usu
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (vw_iv_usu.id_empleado = vw_pg_empleado.id_empleado)
		WHERE id_usuario = %s;",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rsUsuario = mysql_query($queryUsuario);
		if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowUsuario = mysql_fetch_assoc($rsUsuario);
		
		$idEmpleadoEntrega = $_SESSION['idEmpleadoSysGts'];
		$nomEmpleadoEntrega = utf8_encode($rowUsuario['nombre_empleado']);
	} else {
		$idEmpleadoEntrega = $rowSolicitud['id_empleado_entrega'];
		$nomEmpleadoEntrega = utf8_encode($rowSolicitud['nombre_empleado_entrega']);
	}
	$objResponse->assign("hddIdEmpleadoEntrega","value",$idEmpleadoEntrega);
	$objResponse->assign("txtEmpleadoEntrega","value",$nomEmpleadoEntrega);
	
	
	$html = sprintf("<input type=\"hidden\" id=\"hddIdEmpleadoJefeTaller\" name=\"hddIdEmpleadoJefeTaller\" readonly=\"readonly\" size=\"4\" value=\"%s\"/>",
		$rowSolicitud['id_jefe_taller']);
	$html .= sprintf("<input type=\"text\" id=\"txtEmpleadoJefeTaller\" name=\"txtEmpleadoJefeTaller\" readonly=\"readonly\" size=\"25\" value=\"%s\"/>",
		utf8_encode($rowSolicitud['nombre_empleado_jefe_taller']));
	$objResponse->assign("tdlstJefeTaller","innerHTML",$html);
	
	$html = sprintf("<input type=\"hidden\" id=\"hddIdEmpleadoRecibido\" name=\"hddIdEmpleadoRecibido\" readonly=\"readonly\" size=\"4\" value=\"%s\"/>",
		$rowSolicitud['id_empleado_recibo']);
	$html .= sprintf("<input type=\"text\" id=\"txtEmpleadoRecibido\" name=\"txtEmpleadoRecibido\" readonly=\"readonly\" size=\"25\" value=\"%s\"/>",
		utf8_encode($rowSolicitud['nombre_empleado_recibo']));
	$objResponse->assign("tdlstEmpleadoRecibido","innerHTML",$html);
	
	$html = sprintf("<input type=\"hidden\" id=\"hddIdEmpleadoDevuelto\" name=\"hddIdEmpleadoDevuelto\" readonly=\"readonly\" size=\"4\" value=\"%s\"/>",
		$rowSolicitud['id_empleado_recibo']);
	$html .= sprintf("<input type=\"text\" id=\"txtEmpleadoDevuelto\" name=\"txtEmpleadoDevuelto\" readonly=\"readonly\" size=\"25\" value=\"%s\"/>",
		utf8_encode($rowSolicitud['nombre_empleado_recibo']));
	$objResponse->assign("tdlstEmpleadoDevuelto","innerHTML",$html);
	
	$objResponse->assign("hddIdOrden","value",$rowSolicitud['id_orden']);
	$objResponse->assign("txtNumeroOrden","value",$rowSolicitud['numero_orden']);
	$objResponse->assign("txtFechaOrden","value",date(spanDateFormat,strtotime($rowSolicitud['tiempo_orden'])));
	$objResponse->assign("txtTipoOrden","value",utf8_encode($rowSolicitud['nombre_tipo_orden']));
	$objResponse->assign("hddIdEstadoOrden","value",utf8_encode($rowSolicitud['id_estado_orden']));
	$objResponse->assign("txtEstadoOrden","value",strtoupper(utf8_encode($rowSolicitud['nombre_estado'])));
	
	$objResponse->loadCommands(listaSolicitudDetalle(0,'id_det_solicitud_repuesto','DESC',$idSolicitud."|".$idAccionSolicitud));
	
	$objResponse->loadCommands(listaComprobanteSurtido(0,'fecha_movimiento','DESC',$rowSolicitud['id_orden']));
	
	return $objResponse;
}

function listaComprobanteSurtido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 100, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("sol_rep.id_orden = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT 
		sol_rep.id_orden,
		sol_rep.id_solicitud,
		sol_rep.numero_solicitud,
		kardex_surtido.fecha_movimiento,
		kardex_surtido.id_estado_solicitud,
		kardex_surtido.id_empleado_despacha,
		kardex_surtido.id_empleado_recibe,
		estado_sol.descripcion_estado_solicitud,
		
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = kardex_surtido.id_empleado_despacha) AS nombre_empleado_entrega,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = kardex_surtido.id_empleado_recibe) AS nombre_empleado_recibo_devuelto
	FROM sa_solicitud_repuestos sol_rep
		INNER JOIN iv_kardex_surtido kardex_surtido ON (sol_rep.id_solicitud = kardex_surtido.id_solicitud)
		INNER JOIN sa_estado_solicitud estado_sol ON (kardex_surtido.id_estado_solicitud = estado_sol.id_estado_solicitud) %s
	GROUP BY sol_rep.id_orden,
		sol_rep.id_solicitud,
		kardex_surtido.fecha_movimiento,
		kardex_surtido.id_estado_solicitud,
		kardex_surtido.id_empleado_despacha,
		kardex_surtido.id_empleado_recibe", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaComprobanteSurtido", "20%", $pageNum, "fecha_movimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaComprobanteSurtido", "12%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_listaComprobanteSurtido", "12%", $pageNum, "descripcion_estado_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Estatus");
		$htmlTh .= ordenarCampo("xajax_listaComprobanteSurtido", "28%", $pageNum, "id_empleado_despacha", $campOrd, $tpOrd, $valBusq, $maxRows, "Despachado por / Devuelto a");
		$htmlTh .= ordenarCampo("xajax_listaComprobanteSurtido", "28%", $pageNum, "id_empleado_recibe", $campOrd, $tpOrd, $valBusq, $maxRows, "Despachado a / Devuelto por");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_estado_solicitud']) {
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Abierta\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Aprobada\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Despachada\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Devuelto\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturada\"/>"; break;
			case 6 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulada\"/>"; break;
			case 7 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo_parcial.gif\" title=\"Aprobada Parcial\"/>"; break;
			case 8 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja_parcial.gif\" title=\"Despachada Parcial\"/>"; break;
			case 9 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris_parcial.gif\" title=\"Devolucion Parcial\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i:s a", strtotime($row['fecha_movimiento']))."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_solicitud'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_estado_solicitud'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado_entrega'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado_recibo_devuelto'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/iv_surtido_taller_pdf.php?valBusq=%s|%s|%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"PDF\"/>",
					$row['id_solicitud'],
					$row['id_estado_solicitud'],
					$row['fecha_movimiento']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr style=\"display:none\">";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComprobanteSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComprobanteSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaComprobanteSurtido(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComprobanteSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComprobanteSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaComprobantes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaSolicitudDetalle($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 1000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idAccionSolicitud = $valCadBusq[1];
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sol_rep.id_solicitud = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if (strlen($valCadBusq[1]) > 0) {
		$query = sprintf("SELECT
			det_sol_rep.id_det_solicitud_repuesto,
			sol_rep.id_empresa,
			art.id_articulo,
			art.codigo_articulo,
			art.descripcion,
			det_sol_rep.id_casilla,
			det_ord_art.id_articulo_almacen_costo,
			det_ord_art.id_articulo_costo,
			1 AS cantidad,
			det_ord_art.precio_unitario,
			det_ord_art.id_iva,
			det_ord_art.iva,
			det_sol_rep.id_estado_solicitud,
			det_sol_rep.tiempo_aprobacion,
			det_sol_rep.tiempo_despacho,
			det_sol_rep.tiempo_devolucion,
			det_sol_rep.tiempo_anulacion
		FROM sa_solicitud_repuestos sol_rep
			INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
			INNER JOIN sa_det_orden_articulo det_ord_art ON (det_sol_rep.id_det_orden_articulo = det_ord_art.id_det_orden_articulo)
			INNER JOIN iv_articulos art ON (det_ord_art.id_articulo = art.id_articulo) %s", $sqlBusq);
	} else { // MODO VISTA
		$query = sprintf("SELECT
			det_sol_rep.id_det_solicitud_repuesto,
			sol_rep.id_empresa,
			art.id_articulo,
			art.codigo_articulo,
			art.descripcion,
			det_sol_rep.id_casilla,
			det_ord_art.id_articulo_almacen_costo,
			det_ord_art.id_articulo_costo,
			COUNT(art.id_articulo) AS cantidad,
			det_ord_art.precio_unitario,
			det_ord_art.id_iva,
			det_ord_art.iva,
			det_sol_rep.id_estado_solicitud,
			det_sol_rep.tiempo_aprobacion,
			det_sol_rep.tiempo_despacho,
			det_sol_rep.tiempo_devolucion,
			det_sol_rep.tiempo_anulacion
		FROM sa_solicitud_repuestos sol_rep
			INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
			INNER JOIN sa_det_orden_articulo det_ord_art ON (det_sol_rep.id_det_orden_articulo = det_ord_art.id_det_orden_articulo)
			INNER JOIN iv_articulos art ON (det_ord_art.id_articulo = art.id_articulo) %s
		GROUP BY art.id_articulo,
			art.codigo_articulo,
			art.descripcion,
			det_sol_rep.id_casilla,
			det_ord_art.id_articulo_almacen_costo,
			det_ord_art.id_articulo_costo,
			det_ord_art.precio_unitario,
			det_ord_art.iva,
			sol_rep.id_empresa,
			det_sol_rep.id_estado_solicitud", $sqlBusq);
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "16%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "34%", $pageNum, "descripcion_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "20%", $pageNum, "id_casilla", $campOrd, $tpOrd, $valBusq, $maxRows, "Ubicación");
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "4%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Cant.");
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "10%", $pageNum, "precio_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPrecioUnitario);
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "6%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "10%", $pageNum, "precio_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
		$ResultConfig12 = valorConfiguracion(12, $row['id_empresa']);
		if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
			return $objResponse->alert($ResultConfig12[1]);
		} else if ($ResultConfig12[0] == true) {
			$ResultConfig12 = $ResultConfig12[1];
		}
		
		if ($row['id_estado_solicitud'] == 1 || $row['id_estado_solicitud'] == 6) {
			$queryArtUbic = sprintf("SELECT * FROM vw_iv_articulos_almacen
			WHERE id_articulo = %s
				AND id_empresa = %s
				AND casilla_predeterminada = 1;",
				valTpDato($row['id_articulo'], "int"),
				valTpDato($row['id_empresa'], "int"));
			$rsArtUbic = mysql_query($queryArtUbic);
			if (!$rsArtUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArtUbic = mysql_fetch_assoc($rsArtUbic);
			
			$idCasilla = $rowArtUbic['id_casilla'];
		} else {
			$queryArtUbic = sprintf("SELECT * FROM vw_iv_articulos_almacen
			WHERE id_articulo = %s
				AND id_casilla = %s
				AND id_empresa = %s;",
				valTpDato($row['id_articulo'], "int"),
				valTpDato($row['id_casilla'], "int"),
				valTpDato($row['id_empresa'], "int"));
			$rsArtUbic = mysql_query($queryArtUbic);
			if (!$rsArtUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArtUbic = mysql_fetch_assoc($rsArtUbic);
			
			$idCasilla = $rowArtUbic['id_casilla'];
		}
		
		switch($row['id_estado_solicitud']) {
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Solicitado\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Aprobado\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Despachado\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Devuelto\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
			case 6 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			case 10 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"No Despachado\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$mostrar = false;
		$porcIva = ($row['id_iva'] != "" && $row['id_iva'] != "0") ? $row['iva']."%" : "-";
		$ivaRepuesto = round(($row['precio_unitario'] * $row['iva']) / 100, 2);
		
		$hddIdArticuloAlmacenCosto = $row['id_articulo_almacen_costo'];
		$hddIdArticuloCosto = $row['id_articulo_costo'];
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= substr(utf8_encode($row['descripcion']),0,40);
				$htmlTb .= (in_array($ResultConfig12, array(1,2)) ? "" : "<br><span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</span>");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= "<span class=\"textoNegrita_10px\" id=\"spnAlmacen".$contFila."\">".utf8_encode($rowArtUbic['descripcion_almacen'])."</span>";
				$htmlTb .= "<br>";
				$htmlTb .= "<span id=\"spnUbicacion".$contFila."\">".utf8_encode(str_replace("-[]", "", $rowArtUbic['ubicacion']))."</span>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<input type=\"text\" id=\"hddCantArt%s\" name=\"hddCantArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>",
					$contFila,
					$contFila,
					$row['cantidad']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_unitario'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".$porcIva."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= number_format(($row['precio_unitario'] + $ivaRepuesto),2,".",",");
				$htmlTb .= sprintf("<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" readonly=\"readonly\" value=\"%s\"/>",
					$contFila,
					$contFila,
					$idCasilla);
				$htmlTb .= sprintf("<input type=\"hidden\" id=\"hddIdSolicitudDetalle%s\" name=\"hddIdSolicitudDetalle%s\" readonly=\"readonly\" value=\"%s\"/>",
					$contFila,
					$contFila,
					$row['id_det_solicitud_repuesto']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr style=\"display:none\">";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaSolicitudDetalle(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaSolicitudDetalle","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaSolicitud($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_estado_orden IN (SELECT id_estado_orden FROM sa_estado_orden WHERE tipo_estado LIKE 'FINALIZADO')");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("orden.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(sol_rep.tiempo_solicitud) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estado_solicitud = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(orden.numero_orden LIKE %s
		OR sol_rep.numero_solicitud LIKE %s
		OR vw_sa_vale_recep.placa LIKE %s
		OR (SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			FROM cj_cc_cliente cliente
			WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
											FROM sa_recepcion r
											WHERE r.id_recepcion = orden.id_recepcion), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																			FROM sa_cita c
																			WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																							FROM sa_recepcion r
																							WHERE r.id_recepcion = orden.id_recepcion))) AS id_cliente)) LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT
		sol_rep.id_solicitud,
		sol_rep.numero_solicitud,
		orden.id_orden,
		orden.numero_orden,
		sol_rep.tiempo_solicitud,
		sol_rep.id_usuario_bloqueo,
		
		(SELECT vw_pg_empleado.nombre_empleado
		FROM pg_usuario usuario
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (usuario.id_empleado = vw_pg_empleado.id_empleado)
		WHERE usuario.id_usuario = sol_rep.id_usuario_bloqueo) AS nombre_empleado_bloqueo,
		
		tp_orden.nombre_tipo_orden,
		vw_sa_vale_recep.placa,
		sol_rep.estado_solicitud,
		
		(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
		FROM cj_cc_cliente cliente
		WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = orden.id_recepcion), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																		FROM sa_cita c
																		WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																						FROM sa_recepcion r
																						WHERE r.id_recepcion = orden.id_recepcion))) AS id_cliente)) AS nombre_cliente,
		
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud) AS cantidad,
		
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 1) AS cantidad_solicitada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 2
			AND tiempo_aprobacion > 0) AS cantidad_aprobada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 3
			AND tiempo_despacho > 0) AS cantidad_despachada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 4
			AND tiempo_devolucion > 0) AS cantidad_devuelta,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 5) AS cantidad_facturada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 10) AS cantidad_no_despachada,
		
		orden.id_usuario_bloqueo AS id_usuario_bloqueo_orden,
		
		(SELECT vw_pg_empleado.nombre_empleado
		FROM pg_usuario usuario
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (usuario.id_empleado = vw_pg_empleado.id_empleado)
		WHERE usuario.id_usuario = orden.id_usuario_bloqueo) AS nombre_empleado_bloqueo_orden
		
	FROM sa_tipo_orden tp_orden
		INNER JOIN sa_orden orden ON (tp_orden.id_tipo_orden = orden.id_tipo_orden)
		INNER JOIN sa_solicitud_repuestos sol_rep ON (orden.id_orden = sol_rep.id_orden)
		INNER JOIN vw_sa_vales_recepcion vw_sa_vale_recep ON (orden.id_recepcion = vw_sa_vale_recep.id_recepcion) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "7%", $pageNum, "tiempo_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "10%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "10%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Orden");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "10%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Orden");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "10%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Placa");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "45%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "8%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estado_solicitud']) {
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Abierta\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Aprobada\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Despachada\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Devuelto\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturada\"/>"; break;
			case 6 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulada\"/>"; break;
			case 7 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo_parcial.gif\" title=\"Aprobada Parcial\"/>"; break;
			case 8 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja_parcial.gif\" title=\"Despachada Parcial\"/>"; break;
			case 9 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris_parcial.gif\" title=\"Devolucion Parcial\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$imgEstatusBloqueo = "";
		if ($row['id_usuario_bloqueo'] > 0) {
			$imgEstatusBloqueo = "<img src=\"../img/iconos/lock.png\" title=\"Solicitud Bloqueda por ".$row['nombre_empleado_bloqueo']."\"/>";
		}
		
		$imgEstatusBloqueoOrden = "";
		if ($row['id_usuario_bloqueo_orden'] > 0) {
			$imgEstatusBloqueoOrden = "<img src=\"../img/iconos/lock.png\" title=\"Orden Bloqueda por ".$row['nombre_empleado_bloqueo_orden']."\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$imgEstatusBloqueo."</td>";
			$htmlTb .= "<td>".$imgEstatusBloqueoOrden."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['tiempo_solicitud']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_orden']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblSolicitud', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\" title=\"Ver Detalle\"/></a>",
					$row['id_solicitud']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"16\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaSolicitud(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"16\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaSolicitud","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"accionBloque");
$xajax->register(XAJAX_FUNCTION,"aprobarRepuesto");
$xajax->register(XAJAX_FUNCTION,"anularRepuesto");
$xajax->register(XAJAX_FUNCTION,"asignarAlmacen");
$xajax->register(XAJAX_FUNCTION,"asignarDisponibilidadUbicacion");
$xajax->register(XAJAX_FUNCTION,"bloquearSolicitud");
$xajax->register(XAJAX_FUNCTION,"buscarSolicitud");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstatusSolicitud");
$xajax->register(XAJAX_FUNCTION,"desbloquearSolicitud");
$xajax->register(XAJAX_FUNCTION,"despacharRepuesto");
$xajax->register(XAJAX_FUNCTION,"devolverRepuesto");
$xajax->register(XAJAX_FUNCTION,"formAlmacen");
$xajax->register(XAJAX_FUNCTION,"formSolicitudRepuesto");
$xajax->register(XAJAX_FUNCTION,"guardarSolicitud");
$xajax->register(XAJAX_FUNCTION,"listaComprobanteSurtido");
$xajax->register(XAJAX_FUNCTION,"listaSolicitud");
$xajax->register(XAJAX_FUNCTION,"listaSolicitudDetalle");
$xajax->register(XAJAX_FUNCTION,"validarAccion");
?>