<?php


function activarLote($idArticuloCosto, $frmListaCostos) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_costo_list","editar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE iv_articulos_costos SET
		estatus = IF(estatus IS NULL, 1, NULL)
	WHERE id_articulo_costo = %s;",
		valTpDato($idArticuloCosto, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaCosto(
		$frmListaCostos['pageNum'],
		$frmListaCostos['campOrd'],
		$frmListaCostos['tpOrd'],
		$frmListaCostos['valBusq']));
	
	return $objResponse;
}

function asignarArticulo($idArticulo, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT 
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		seccion.descripcion AS descripcion_seccion,
		tipo_art.descripcion AS descripcion_tipo_articulo,
		tipo_unidad.unidad,
		tipo_unidad.decimales,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = art.id_articulo
			AND kardex.tipo_movimiento IN (1)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_compra,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = art.id_articulo
			AND kardex.tipo_movimiento IN (3)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_venta,
		
		(SELECT SUM(IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0))
		FROM iv_articulos_almacen art_alm
		WHERE art_alm.id_articulo = art.id_articulo) AS cantidad_disponible_fisica,
		
		(SELECT SUM(IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0) - IFNULL(art_alm.cantidad_espera, 0) - IFNULL(art_alm.cantidad_bloqueada, 0))
		FROM iv_articulos_almacen art_alm
		WHERE art_alm.id_articulo = art.id_articulo) AS cantidad_disponible_logica
	FROM iv_articulos art
		INNER JOIN iv_marcas marca ON (art.id_marca = marca.id_marca)
		INNER JOIN iv_tipos_articulos tipo_art ON (art.id_tipo_articulo = tipo_art.id_tipo_articulo)
		INNER JOIN iv_subsecciones subseccion ON (subseccion.id_subseccion = art.id_subseccion)
		INNER JOIN iv_secciones seccion ON (subseccion.id_seccion = seccion.id_seccion)
		INNER JOIN iv_tipos_unidad tipo_unidad ON (art.id_tipo_unidad = tipo_unidad.id_tipo_unidad)
	WHERE art.id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$objResponse->assign("hddIdArticulo","value",$idArticulo);
	$objResponse->assign("txtCodigoArticulo","value",elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"));
	
	$objResponse->script("
	byId('btnCancelarLista').click();");
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $asigDescuento = true) {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT
		id_proveedor,
		nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor
	WHERE id_proveedor = %s",
		valTpDato($idProveedor, "text"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtIdProv","value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",htmlentities($rowProv['nombre']));
	$objResponse->assign("txtRifProv","value",htmlentities($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccionProv","innerHTML",htmlentities($rowProv['direccion']));
	$objResponse->assign("txtContactoProv","value",htmlentities($rowProv['contacto']));
	$objResponse->assign("txtEmailContactoProv","value",htmlentities($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonosProv","value",htmlentities($rowProv['telefono']));
	
	if ($asigDescuento == true) {
		$objResponse->assign("txtDescuento","value",number_format($rowProv['descuento'], 2, ".", ","));
	}
	
	$objResponse->script("
	byId('btnCancelarLista').click();");
	
	return $objResponse;
}

function buscarArticulo($frmBuscarArticulo, $frmArticulo) {
	$objResponse = new xajaxResponse();
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $frmBuscarArticulo['hddCantCodigoBuscar']; $cont++) {
		$codArticulo .= $frmBuscarArticulo['txtCodigoArticuloBuscar'.$cont].";";
	}
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$codArticulo,
		$frmBuscarArticulo['txtCriterioBusq'],
		$frmArticulo['hddIdArticulo'],
		$frmBuscarArticulo['hddModoArticulo']);
	
	$objResponse->loadCommands(listaArticulo(0, "id_articulo", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarCosto($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstEstatus'],
		$codArticulo,
		$frmBuscar['txtCriterio']);

	$objResponse->loadCommands(listaCosto(0, "id_articulo_costo", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "", "", $valBusq));
	
	return $objResponse;
}

function formCosto($idArticulo, $idArticuloCosto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_articulo_costo_list","insertar")
	|| xvalidaAcceso($objResponse,"iv_articulo_costo_list","editar")) {
		if ($idArticuloCosto != "") {
			$queryArticuloCosto = sprintf("SELECT art_costo.*,
				prov.nombre AS nombre_proveedor,
				art.codigo_articulo
			FROM iv_articulos_costos art_costo
				INNER JOIN iv_articulos art ON (art_costo.id_articulo = art.id_articulo)
				INNER JOIN cp_proveedor prov ON (art_costo.id_proveedor = prov.id_proveedor)
			WHERE id_articulo_costo = %s;",
				valTpDato($idArticuloCosto, "int"));
			$rsArticuloCosto = mysql_query($queryArticuloCosto);
			if (!$rsArticuloCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArticuloCosto = mysql_fetch_assoc($rsArticuloCosto);
			
			$idArticulo = $rowArticuloCosto['id_articulo'];
			$codigoArticulo = $rowArticuloCosto['codigo_articulo'];
		} else {
			$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos
			WHERE id_articulo = %s;",
				valTpDato($idArticulo, "int"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArticulo = mysql_fetch_assoc($rsArticulo);
			
			$idArticulo = $rowArticulo['id_articulo'];
			$codigoArticulo = $rowArticulo['codigo_articulo'];
		}
		
		$objResponse->assign("hddIdArticuloCosto","value",$rowArticuloCosto['id_articulo_costo']);
		$objResponse->assign("hddIdArticulo","value",$idArticulo);
		$objResponse->assign("txtCodigoArticulo","value",elimCaracter($codigoArticulo,";"));
		$objResponse->assign("txtIdProv","value",$rowArticuloCosto['id_proveedor']);
		$objResponse->assign("txtNombreProv","value",$rowArticuloCosto['nombre_proveedor']);
		$objResponse->assign("txtFechaCosto","value",(($rowArticuloCosto['fecha'] != "") ? date(spanDateFormat,strtotime($rowArticuloCosto['fecha'])) : ""));
		$objResponse->assign("txtCosto","value",$rowArticuloCosto['costo']);
	}
	
	return $objResponse;
}

function exportarCosto($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstEstatus'],
		$codArticulo,
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/iv_articulo_costo_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function guardarCosto($frmCostoArticulo, $frmListaCostos, $frmBuscar) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	$idArticulo = $frmCostoArticulo['hddIdArticulo'];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_articulo = %s",
		valTpDato($idArticulo, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	// BUSCA QUE EMPRESAS TIENE DICHO ARTICULO
	$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa %s;", $sqlBusq);
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowArtEmp = mysql_fetch_assoc($rsArtEmp)) {
		$idEmpresa = $rowArtEmp['id_empresa'];
		
		if ($frmCostoArticulo['hddIdArticuloCosto'] > 0) {
			// BUSCA EL ULTIMO COSTO DEL ARTICULO
			$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos art_costo
			WHERE art_costo.id_articulo = %s
				AND art_costo.id_empresa = %s
			ORDER BY art_costo.fecha_registro DESC LIMIT 1;",
				valTpDato($idArticulo, "int"),
				valTpDato($idEmpresa, "int"));
			$rsCostoArt = mysql_query($queryCostoArt);
			if (!$rsCostoArt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			$existeCosto = false;
			while ($rowCostoArt = mysql_fetch_assoc($rsCostoArt)) {
				if (round($rowCostoArt['costo'],2) == round($frmCostoArticulo['txtCosto'],2)
				&& date("Y-m-d",strtotime($rowCostoArt['fecha'])) == date("Y-m-d",strtotime($frmCostoArticulo['txtFechaCosto']))) {
					$existeCosto = true;
					$idArticuloCosto = $rowCostoArt['id_articulo_costo'];
				}
			}
			
			if ($existeCosto == true) {
				if (!xvalidaAcceso($objResponse,"iv_articulo_costo_list","editar")) { return $objResponse; }
				
				$updateSQL = sprintf("UPDATE iv_articulos_costos SET
					id_proveedor = %s,
					id_articulo = %s,
					costo = %s,
					fecha = %s
				WHERE id_articulo_costo = %s;",
					valTpDato($frmCostoArticulo['txtIdProv'], "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($frmCostoArticulo['txtCosto'], "real_inglesa"),
					valTpDato(date("Y-m-d",strtotime($frmCostoArticulo['txtFechaCosto'])),"date"),
					valTpDato($idArticuloCosto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			} else {
				if (!xvalidaAcceso($objResponse,"iv_articulo_costo_list","insertar")) { return $objResponse; }
				
				$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, id_moneda, id_empleado_creador, motivo_creacion, fecha_registro, estatus)
				SELECT %s, %s, %s, %s, %s, moneda.idmoneda, %s, %s, %s, %s FROM pg_monedas moneda
				WHERE moneda.estatus = 1
					AND moneda.predeterminada = 1;",
					valTpDato($idEmpresa, "int"),
					valTpDato($frmCostoArticulo['txtIdProv'], "int"),
					valTpDato($idArticulo, "int"),
					valTpDato(date("Y-m-d",strtotime($frmCostoArticulo['txtFechaCosto'])),"date"),
					valTpDato($frmCostoArticulo['txtCosto'], "real_inglesa"),
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
					valTpDato($frmCostoArticulo['txtMotivoCreacion'], "text"),
					valTpDato("NOW()", "campo"),
					valTpDato("1", "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idArticuloCosto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
			$Result1 = actualizarSaldos($idArticulo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
			
			// ACTUALIZA EL COSTO PROMEDIO
			$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		} else {
			if (!xvalidaAcceso($objResponse,"iv_articulo_costo_list","insertar")) { return $objResponse; }
			
			$insertSQL = sprintf("INSERT INTO iv_articulos_costos (id_empresa, id_proveedor, id_articulo, fecha, costo, costo_promedio, id_moneda, id_empleado_creador, motivo_creacion, fecha_registro, estatus)
			SELECT %s, %s, %s, %s, %s, %s, moneda.idmoneda, %s, %s, %s, %s FROM pg_monedas moneda
			WHERE moneda.estatus = 1
				AND moneda.predeterminada = 1;",
				valTpDato($idEmpresa, "int"),
				valTpDato($frmCostoArticulo['txtIdProv'], "int"),
				valTpDato($idArticulo, "int"),
				valTpDato(date("Y-m-d",strtotime($frmCostoArticulo['txtFechaCosto'])),"date"),
				valTpDato($frmCostoArticulo['txtCosto'], "real_inglesa"),
				valTpDato($frmCostoArticulo['txtCosto'], "real_inglesa"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($frmCostoArticulo['txtMotivoCreacion'], "text"),
				valTpDato("NOW()", "campo"),
				valTpDato("1", "int")); // Null = Inactiva, 1 = Activa
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
		$Result1 = actualizarSaldos($idArticulo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA EL PRECIO DE VENTA
		$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Costo Guardado con Éxito"));
	
	$objResponse->alert(utf8_encode("Los Precios Han Sido Actualizados Con Éxito"));
	
	$objResponse->script("
	byId('btnCancelarCostoArticulo').click();");
	
	$objResponse->loadCommands(listaCosto(
		$frmListaCostos['pageNum'],
		$frmListaCostos['campOrd'],
		$frmListaCostos['tpOrd'],
		$frmListaCostos['valBusq']));
	
	return $objResponse;
}

function listaArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 18, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[0], "text"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(codigo_articulo LIKE %s
		OR descripcion LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo <> %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
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
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['foto'];
		
		$htmlTb .= "<td valign=\"top\" width=\"33%\">";
			$htmlTb .= "<table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td rowspan=\"2\">";
					$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarArticulo%s\" onclick=\"xajax_asignarArticulo('%s', xajax.getFormValues('frmBuscar'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
						$contFila,
						$row['id_articulo']);
				$htmlTb .= "</td>";
				$htmlTb .= sprintf("<td rowspan=\"2\" style=\"background-color:#FFFFFF\">%s</td>",
					"<img src=\"".$imgFoto."\" width=\"80\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>",
					"100%",
					elimCaracter(utf8_encode($row['codigo_articulo']),";"));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['descripcion']));
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"3\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaCosto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = "";
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.estatus = %s",
			valTpDato($valCadBusq[1], "int"));
	} else if ($valCadBusq[1] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_costo.estatus IS NULL");
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(art.id_articulo = %s
		OR art_costo.id_articulo_costo LIKE %s
		OR art.descripcion LIKE %s
		OR art.codigo_articulo_prov LIKE %s)",
			valTpDato($valCadBusq[3], "int"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT art_costo.*,
		art_emp.id_empresa,
		prov.id_proveedor,
		prov.nombre AS nombre_proveedor,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		art.codigo_articulo_prov,
		art.id_tipo_articulo,
		art_emp.clasificacion,
		(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0)) AS existencia,
		IFNULL(art_costo.cantidad_reservada, 0) AS cantidad_reservada,
		(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0) - IFNULL(art_costo.cantidad_reservada, 0)) AS cantidad_disponible_fisica,
		IFNULL(art_costo.cantidad_espera, 0) AS cantidad_espera,
		IFNULL(art_costo.cantidad_bloqueada, 0) AS cantidad_bloqueada,
		(IFNULL(art_costo.cantidad_inicio, 0) + IFNULL(art_costo.cantidad_entrada, 0) - IFNULL(art_costo.cantidad_salida, 0) - IFNULL(art_costo.cantidad_reservada, 0) - IFNULL(art_costo.cantidad_espera, 0) - IFNULL(art_costo.cantidad_bloqueada, 0)) AS cantidad_disponible_logica,
		moneda_local.abreviacion AS abreviacion_moneda_local,
		moneda_origen.abreviacion AS abreviacion_moneda_origen,
		vw_pg_empleado.nombre_empleado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM iv_articulos art
		INNER JOIN iv_articulos_empresa art_emp ON (art.id_articulo = art_emp.id_articulo)
		LEFT JOIN iv_articulos_costos art_costo ON (art_emp.id_empresa = art_costo.id_empresa AND art_emp.id_articulo = art_costo.id_articulo)
		LEFT JOIN cp_proveedor prov ON (art_costo.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (art_costo.id_moneda = moneda_local.idmoneda)
		LEFT JOIN pg_monedas moneda_origen ON (art_costo.id_moneda_origen = moneda_origen.idmoneda)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (art_costo.id_empleado_creador = vw_pg_empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (art_emp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCosto", "12%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "16%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "10%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "12%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Decripción"));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "8%", $pageNum, "codigo_articulo_prov", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código Prov."));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Clasif."));
		$htmlTh .= ordenarCampo("xajax_listaCosto", "6%", $pageNum, "id_articulo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Lote");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "8%", $pageNum, "costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "8%", $pageNum, "costo_promedio", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo Promedio");
		$htmlTh .= ordenarCampo("xajax_listaCosto", "6%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Unid. Disponible"));
		$htmlTh .= "<td colspan=\"2\"></td>";
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
		
		switch ($row['estatus']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$classCosto = (in_array($ResultConfig12, array(1,3))) ? "divMsjInfo" : "";
		$classCostoProm = (!in_array($ResultConfig12, array(1,3))) ? "divMsjInfo" : "";
		
		$classDisponible = ($row['cantidad_disponible_logica'] > 0 && $row['estatus'] == 1) ? "class=\"divMsjInfo\"" : "class=\"divMsjError\"";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['fecha'] != "") ? date(spanDateFormat,strtotime($row['fecha'])) : "")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['id_empleado_creador']) > 0) ? "<tr><td align=\"center\"><span class=\"texto_9px\">Registrado por:</span><br><span class=\"textoNegrita_9px\">".$row['nombre_empleado']."</span></td></tr>" : "";
				$htmlTb .= ((strlen($row['motivo_creacion']) > 0) ? "<tr><td>".utf8_encode($row['motivo_creacion'])."</td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_articulo_prov'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".htmlentities("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".htmlentities("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".htmlentities("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".htmlentities("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".htmlentities("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".htmlentities("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_articulo_costo']."</td>";
			$htmlTb .= "<td align=\"right\" class=\"".$classCosto."\">";
				$htmlTb .= $row['abreviacion_moneda_local'].number_format($row['costo'], 2, ".", ",");
				$htmlTb .= ($row['costo_origen'] != 0) ? "<br>".$row['abreviacion_moneda_origen'].number_format($row['costo_origen'], 2, ".", ",") : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\" class=\"".$classCostoProm."\">".$row['abreviacion_moneda_local'].number_format($row['costo_promedio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" ".$classDisponible.">".number_format((($row['estatus'] == 1) ? $row['cantidad_disponible_logica'] : 0), 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ((in_array($_SESSION['idMetodoCosto'], array(1,2)) && $row['estatus'] == 1)
			|| !$row['fecha']) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_formCosto('%s', '%s');\" src=\"../img/iconos/pencil.png\"/>",
					$row['id_articulo'],
					$row['id_articulo_costo']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if (in_array($_SESSION['idMetodoCosto'], array(3)) && $row['id_empleado_creador'] > 0) {
				if (!($row['cantidad_disponible_logica'] > 0) && $row['estatus'] == 1) {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarActivarLote('%s');\" src=\"../img/iconos/cancel.png\" title=\"Desactivar\"/>",
						$row['id_articulo_costo']);
				} else if ($row['estatus'] == 0) {
					$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarActivarLote('%s');\" src=\"../img/iconos/accept.png\" title=\"Activar\"/>",
						$row['id_articulo_costo']);
				}
				
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[13] += $row['cantidad_disponible_logica'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"12\">".utf8_encode("Total Página:")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"2\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[13] += $row['cantidad_disponible_logica'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"12\">".utf8_encode("Total de Totales:")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"2\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"15\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCosto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCosto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"15\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaCostos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		id_proveedor,
		nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"activarLote");
$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarCosto");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"formCosto");
$xajax->register(XAJAX_FUNCTION,"exportarCosto");
$xajax->register(XAJAX_FUNCTION,"guardarCosto");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaCosto");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
?>