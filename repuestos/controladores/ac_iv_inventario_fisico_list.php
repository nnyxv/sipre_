<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function anularInventarioFisico($idInvFisico, $hddIdItm, $frmListaInventarioFisico) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	// ACTUALIZA EL ESTATUS DEL PRESUPUESTO DE VENTA
	$updateSQL = sprintf("UPDATE iv_inventario_fisico SET
		estatus = %s
	WHERE id_inventario_fisico = %s;",
		valTpDato(2, "int"), // 0 = En Proceso, 1 = Culminado, 2 = Cancelado
		valTpDato($idInvFisico, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Inventario Físico Anulado con Éxito"));
	
	$objResponse->loadCommands(listaInventarioFisico(
		$frmListaInventarioFisico['pageNum'],
		$frmListaInventarioFisico['campOrd'],
		$frmListaInventarioFisico['tpOrd'],
		$frmListaInventarioFisico['valBusq']));
	
	return $objResponse;
}

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstTipoMovimiento'],
		$frmBuscar['txtCriterio']);

	$objResponse->loadCommands(listaInventarioFisico(0, "", "", $valBusq));
		
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

function formInventario() {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_inventario_fisico_list","insertar")) { return $objResponse; }
	
	$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
	
	return $objResponse;
}

function listaInventarioFisico($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_inv_fis.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_movimiento = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$query = sprintf("SELECT
		vw_iv_inv_fis.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_inventario_fisico vw_iv_inv_fis
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_inv_fis.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaInventarioFisico", "8%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaInventarioFisico", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaInventarioFisico", "30%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Creador"));
		$htmlTh .= ordenarCampo("xajax_listaInventarioFisico", "12%", $pageNum, "cant_articulos", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cantidad de Artículo(s)"));
		$htmlTh .= ordenarCampo("xajax_listaInventarioFisico", "12%", $pageNum, "tipo_conteo_descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Conteo"));
		$htmlTh .= ordenarCampo("xajax_listaInventarioFisico", "12%", $pageNum, "filtro_conteo_descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Filtro del Conteo"));
		$htmlTh .= ordenarCampo("xajax_listaInventarioFisico", "12%", $pageNum, "orden_conteo_descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Ordenado por"));
		$htmlTh .= "<td class=\"noprint\" colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"En Proceso\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Culminado\"/>"; break;
			case 2 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Cancelado\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cant_articulos']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_conteo_descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['filtro_conteo_descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['orden_conteo_descripcion'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"window.open('iv_inventario_fisico_form.php?id=%s','_self');\" src=\"../img/iconos/ico_view.png\" title=\"Ver\"/></td>",
				$row['id_inventario_fisico']);
			$htmlTb .= "<td>";
			if ($row['estatus'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAnularInventarioFisico%s\" onclick=\"validarInventarioFisicoAnulado('%s', '%s')\" src=\"../img/iconos/cancel.png\" title=\"".utf8_encode("Anular")."\"/>",
					$contFila,
					$row['id_inventario_fisico'],
					$contFila);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaInventarioFisico(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaInventarioFisico(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaInventarioFisico(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaInventarioFisico(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaInventarioFisico(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaInventarioFisico","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function guardarInventario($frmInventario, $cancelarInvAbierto = false) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_inventario_fisico_list","insertar")) { return $objResponse; }
	
	$idEmpresa = $frmInventario['txtIdEmpresa'];
	
	// BUSCA LOS DOCUMENTOS DE VENTAS PENDIENTES POR FACTURAR O ANULAR
	$query = sprintf("SELECT id_pedido_venta FROM vw_iv_pedidos_venta
	WHERE id_empresa = %s
		AND (estatus_pedido_venta = 0
			OR (estatus_pedido_venta = 1 OR (estatus_pedido_venta = 2 AND id_empleado_aprobador IS NULL))
			OR (estatus_pedido_venta = 2 AND id_empleado_aprobador IS NOT NULL));",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	if ($totalRows > 0) { // SI NO DOCUMENTOS PENDIENTES
		return $objResponse->alert(utf8_encode("Existen pedidos que deben ser facturados o anulados"));
	}
	
	// BUSCA LOS ARTICULOS BLOQUEADOS
	$query = sprintf("SELECT *
	FROM iv_bloqueo_venta bloq_vent
		INNER JOIN iv_bloqueo_venta_detalle bloq_vent_det ON (bloq_vent.id_bloqueo_venta = bloq_vent_det.id_bloqueo_venta)
	WHERE bloq_vent.id_empresa = %s
		AND bloq_vent_det.cantidad > 0;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	if ($totalRows > 0) { // SI NO HAY ARTICULOS BLOQUEADOS
		return $objResponse->alert(utf8_encode("Existen artículos bloqueados"));
	}
	
	mysql_query("START TRANSACTION;");
	
	if ($cancelarInvAbierto == true) {
		$updateSQL = sprintf("UPDATE iv_inventario_fisico SET estatus = 2
		WHERE estatus = 0
			AND id_empresa = %s
			AND id_empleado = %s;",
			valTpDato($idEmpresa, "int"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	$fecha = date(spanDateFormat);
	$hora = date("H:i:s");
	$lstTipoProceso = $frmInventario['lstTipoProceso']; // 1 = Manual, 2 = Automatico
	$numeroConteo = 1;
	
	// ALMACENA LOS DATOS DEL INVENTARIO FISICO
	$insertSQL = sprintf("INSERT INTO iv_inventario_fisico (id_empresa, fecha, hora, estatus, id_empleado, id_tipo_costo, tipo_conteo, filtro_conteo, orden_conteo, numero_conteo, cantidad_conteo)
	SELECT
		config_emp.id_empresa,
		%s,
		%s,
		%s,
		%s,
		config_emp.valor,
		%s,
		%s,
		%s,
		%s,
		%s
	FROM pg_configuracion_empresa config_emp
	WHERE config_emp.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($hora, "date"),
		valTpDato(0, "int"), // 0 = En Proceso, 1 = Culminado, 2 = Cancelado
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($lstTipoProceso, "int"),
		valTpDato($frmInventario['lstFiltroArticulos'], "int"),
		valTpDato($frmInventario['lstOrdenArticulos'], "int"),
		valTpDato($numeroConteo, "int"),
		valTpDato($frmInventario['lstCantidadConteo'], "int"),
		valTpDato($idEmpresa, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$idInventarioFisico = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_casilla IS NOT NULL");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
		AND vw_iv_art_almacen_costo.id_articulo_costo IS NOT NULL)
	OR (vw_iv_art_almacen_costo.estatus_articulo_almacen = 1
		AND vw_iv_art_almacen_costo.estatus_articulo_almacen_costo IS NULL
		AND vw_iv_art_almacen_costo.estatus_articulo_costo IS NULL
		AND vw_iv_art_almacen_costo.id_articulo_costo IS NULL)
	OR (vw_iv_art_almacen_costo.cantidad_disponible_logica > 0)
	OR (vw_iv_art_almacen_costo.cantidad_reservada > 0))");
	
	switch($frmInventario['lstFiltroArticulos']) {
		case 1 : // Todos
			$filtroArticulos = "Todos";
			break;
		case 2 : // Con Movimientos
			$filtroArticulos = "Con Movimientos";
			
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(SELECT COUNT(kardex.id_articulo) FROM iv_kardex kardex
			WHERE kardex.id_articulo = vw_iv_art_almacen_costo.id_articulo
				AND kardex.id_casilla = vw_iv_art_almacen_costo.id_casilla) > 0");
			break;
		case 3 : // Con Saldo
			$filtroArticulos = "Con Disponibilidad";
			
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
			break;
	}
	
	switch($frmInventario['lstOrdenArticulos']) {
		case 1: // Código Artículo
			$ordenArticulos = "Código de Artículo";
			$sqlOrd = "ORDER BY vw_iv_art_almacen_costo.codigo_articulo ASC";
			break;
		case 2 : // Código Proveedor
			$ordenArticulos = "Código de Proveedor";
			$sqlOrd = "ORDER BY vw_iv_art_almacen_costo.codigo_articulo_prov ASC";
			break;
		case 3 : // Localización
			$ordenArticulos = "Por Localización";
			$sqlOrd = "ORDER BY CONCAT(vw_iv_art_almacen_costo.descripcion_almacen, vw_iv_art_almacen_costo.ubicacion) ASC";
			break;
	}
	
	// BUSCA LOS ARTICULOS DE LA EMPRESA DEPENDIENDO DE LOS FILTROS
	$queryArtEmp = sprintf("SELECT vw_iv_art_almacen_costo.*,
		
		(SELECT tipo_unidad.unidad FROM iv_tipos_unidad tipo_unidad
		WHERE tipo_unidad.id_tipo_unidad = art.id_tipo_unidad) AS unidad,
		
		(CASE (SELECT valor FROM pg_configuracion_empresa config_emp INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
				WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = vw_iv_art_almacen_costo.id_empresa)
			WHEN 1 THEN	vw_iv_art_almacen_costo.costo
			WHEN 2 THEN	vw_iv_art_almacen_costo.costo_promedio
			WHEN 3 THEN	vw_iv_art_almacen_costo.costo
		END) AS costo
	FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
		INNER JOIN iv_articulos art ON (vw_iv_art_almacen_costo.id_articulo = art.id_articulo) %s %s", $sqlBusq, $sqlOrd);
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$contFila = 0;
	$contSinCosto = 0;
	while ($rowArtEmp = mysql_fetch_assoc($rsArtEmp)) {
		$contFila++;
		
		$idArticulo = $rowArtEmp['id_articulo'];
		$idCasilla = $rowArtEmp['id_casilla'];
		$hddIdArticuloAlmacenCosto = $rowArtEmp['id_articulo_almacen_costo'];
		$hddIdArticuloCosto = $rowArtEmp['id_articulo_costo'];
		$costoUnitario = $rowArtEmp['costo'];
		
		if ($costoUnitario > 0) {
			$insertSQL = sprintf("INSERT INTO iv_inventario_fisico_detalle (numero, id_inventario_fisico, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, existencia_kardex, costo_proveedor, habilitado)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s, true);",
				valTpDato($contFila, "int"),
				valTpDato($idInventarioFisico, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($hddIdArticuloAlmacenCosto, "int"),
				valTpDato($hddIdArticuloCosto, "int"),
				valTpDato($rowArtEmp['cantidad_disponible_logica'], "int"),
				valTpDato($costoUnitario, "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		} else {
			$arraySinCosto[] = str_pad(elimCaracter($rowArtEmp['codigo_articulo'],";"), 30, " ", STR_PAD_RIGHT);
		}
	}
	
	if (count($arraySinCosto) > 0) {
		return $objResponse->alert((utf8_encode("El Inventario Físico no puede ser creado debido a que ".count($arraySinCosto)." registro(s) no tiene(n) costo asignado: ".implode(", ",$arraySinCosto))));
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->script("
	byId('btnCancelarInventario').click();");
	
	$objResponse->script("verVentana('reportes/iv_inventario_fisico_todo_pdf.php?valBusq=".$idInventarioFisico."|".$idEmpresa."|1', 950, 600);");
	
	$objResponse->script("window.open('iv_inventario_fisico_form.php?id=".$idInventarioFisico."','_self');");
	
	return $objResponse;
}

function verificarExistenciaInvFisico($idEmpresa, $idUsuario) {
	$objResponse = new xajaxResponse();
	
	// BUSCA EL INVENTARIO FISICO QUE AUN ESTE EN PROCESO
	$query = sprintf("SELECT * FROM iv_inventario_fisico
	WHERE estatus = 0
		AND id_empresa = %s
		AND id_empleado = %s
	ORDER BY id_inventario_fisico DESC
	LIMIT 1",
		$idEmpresa,
		$_SESSION['idEmpleadoSysGts']);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['id_inventario_fisico'] != "") {
		$objResponse->script(utf8_encode("
		if (confirm('Existe abierto un proceso de Inventario Físico, ¿Desea Cargarlo?') == true) {
			window.open('iv_inventario_fisico_form.php?id=".$row['id_inventario_fisico']."','_self');
		}"));
	}
	
	return $objResponse;
}

function verificarNuevoInventario($frmInventario) {
	$objResponse = new xajaxResponse();
	
	// BUSCA EL INVENTARIO FISICO QUE AUN ESTE EN PROCESO
	$query = sprintf("SELECT * FROM iv_inventario_fisico
	WHERE estatus = 0
		AND id_empresa = %s
		AND id_empleado = %s
	ORDER BY id_inventario_fisico DESC
	LIMIT 1;",
		valTpDato($frmInventario['txtIdEmpresa'], "int"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['id_inventario_fisico'] != "") {
		$objResponse->script(utf8_encode("
		if (confirm('Ya hay abierto un proceso de Inventario Físico, ¿Desea Cancelarlo y Crear uno Nuevo?') == true) {
			xajax_guardarInventario(xajax.getFormValues('frmInventario'), true);
		}"));
	} else {
		$objResponse->script(utf8_encode("
		xajax_guardarInventario(xajax.getFormValues('frmInventario'), true);"));
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularInventarioFisico");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"formInventario");
$xajax->register(XAJAX_FUNCTION,"listaInventarioFisico");
$xajax->register(XAJAX_FUNCTION,"guardarInventario");
$xajax->register(XAJAX_FUNCTION,"verificarExistenciaInvFisico");
$xajax->register(XAJAX_FUNCTION,"verificarNuevoInventario");
?>