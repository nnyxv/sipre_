<?php
function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	$queryEmpresaSuc = sprintf("SELECT DISTINCT
		id_empresa_reg,
		nombre_empresa
	FROM vw_iv_empresas_sucursales
	WHERE id_empresa_padre_suc IS NULL
		AND id_empresa_reg <> 100
	ORDER BY nombre_empresa_suc ASC",
		valTpDato($row['id_empresa'], "int"));
	$rsEmpresaSuc = mysql_query($queryEmpresaSuc);
	if (!$rsEmpresaSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowEmpresaSuc = mysql_fetch_assoc($rsEmpresaSuc)) {
		$selected = ($selId == $rowEmpresaSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
	
		$htmlOption .= "<option ".$selected." value=\"".$rowEmpresaSuc['id_empresa_reg']."\">".utf8_encode($rowEmpresaSuc['nombre_empresa'])."</option>";	
	}
	
	$query = sprintf("SELECT DISTINCT
		id_empresa,
		nombre_empresa
	FROM vw_iv_empresas_sucursales
	WHERE id_empresa_padre_suc IS NOT NULL
		AND id_empresa_reg <> 100
	ORDER BY nombre_empresa");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$htmlOption .= "<optgroup label=\"".$row['nombre_empresa']."\">";
		
		$queryEmpresaSuc = sprintf("SELECT DISTINCT
			id_empresa_reg,
			nombre_empresa_suc,
			sucursal
		FROM vw_iv_empresas_sucursales
		WHERE id_empresa_padre_suc = %s
			AND id_empresa_reg <> 100
		ORDER BY nombre_empresa_suc ASC",
			valTpDato($row['id_empresa'], "int"));
		$rsEmpresaSuc = mysql_query($queryEmpresaSuc);
		if (!$rsEmpresaSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowEmpresaSuc = mysql_fetch_assoc($rsEmpresaSuc)) {
			$selected = ($selId == $rowEmpresaSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
		
			$htmlOption .= "<option ".$selected." value=\"".$rowEmpresaSuc['id_empresa_reg']."\">".utf8_encode($rowEmpresaSuc['nombre_empresa_suc'])."</option>";	
		}
	
		$htmlOption .= "</optgroup>";
	}
	
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstDepartamento(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdSelEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstDepartamento($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM pg_departamento WHERE id_empresa = %s ORDER BY nombre_departamento", valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$html = "<select id=\"lstDepartamento\" name=\"lstDepartamento\">";
			$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$seleccion = "";
			if ($selId == $row['id_departamento'])
				$seleccion = "selected='selected'";
	
			$html .= "<option value=\"".$row['id_departamento']."\" ".$seleccion.">".htmlentities($row['nombre_departamento'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdSelDepartamento","innerHTML",$html);

	return $objResponse;
}

function eliminarCentroCosto($idCentroCosto) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"pg_centro_costo_list","eliminar")){
		return $objResponse;
	}

	mysql_query("START TRANSACTION;");

	$queryEliminar = "DELETE FROM pg_unidad_centro_costo WHERE id_unidad_centro_costo = '".$idCentroCosto."'";
	if ($rs = mysql_query($queryEliminar) == true){
		$objResponse->script("xajax_listarCentroCosto(0,'id_unidad_centro_costo','','')");
		$objResponse->alert("Centro de Costo eliminado exitosamente.");
	}
	if (!$rs)
			$objResponse->alert("No se puede eliminar el Centro de Costo ya que existen otros registros dependientes.");
	mysql_query("COMMIT;");

	return $objResponse;
}

function guardarCentroCosto($frmCentroCosto) {
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");

	if ($frmCentroCosto['hddIdCentroCosto'] == 0){
		$cadena = "insertado";

		$queryCentroCosto = sprintf("INSERT INTO pg_unidad_centro_costo (codigo_unidad_centro_costo, nombre_unidad_centro_costo, id_departamento) VALUES (%s, %s, %s);",
			valTpDato($frmCentroCosto['txtCodigo'],"text"),
			valTpDato($frmCentroCosto['txtNombre'],"text"),
			valTpDato($frmCentroCosto['lstDepartamento'],"int"));

	} else {

		$cadena = "modificado";

		$queryCentroCosto = sprintf("UPDATE pg_unidad_centro_costo SET
													codigo_unidad_centro_costo = %s,
													nombre_unidad_centro_costo = %s,
													id_departamento = %s
									 WHERE
											id_unidad_centro_costo = %s",
											valTpDato($frmCentroCosto['txtCodigo'],"text"),
											valTpDato($frmCentroCosto['txtNombre'],"text"),
											valTpDato($frmCentroCosto['lstDepartamento'],"int"),
											$frmCentroCosto['hddIdCentroCosto']);
	}

	$rsCentroCosto = mysql_query($queryCentroCosto);

//----Verifica existencia Codigo------//
	if (!$rsCentroCosto) {
		if (mysql_errno() == 1062) {
			return $objResponse->alert("Ya existe el Código '".$frmCentroCosto['txtCodigo']."'");
		} else {
			return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryCentroCosto);
		}
	}

	$objResponse->script("
	xajax_listarCentroCosto(0,'id_unidad_centro_costo','','');
	$('divFlotante').style.display = 'none';");

	$objResponse->alert("Centro de costo ".$cadena." exitosamente");

	mysql_query("COMMIT;");

	return $objResponse;
}

function levantarDivFlotante() {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"pg_centro_costo_list","insertar")){
		return $objResponse;
	}
	$objResponse->loadCommands(cargaLstEmpresa());
		$objResponse->loadCommands(cargaLstDepartamento(0));

	$objResponse->script("document.forms['frmCentroCosto'].reset();
						$('divFlotante').style.display = '';
						centrarDiv($('divFlotante'));
						$('btnGuardar').style.display = '';
						$('hddIdCentroCosto').value = 0;");

	$objResponse->assign("tdFlotanteTitulo","innerHTML","Nuevo Centro de Costo");

	return $objResponse;
}

function listarCentroCosto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"pg_centro_costo_list")){
		$objResponse->assign("tdListarCentroCosto","innerHTML","Acceso Denegado");
		return $objResponse;
	}

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != '') //Busqueda(Codigo/Nombre/Departamento)
		$sqlBusq .= "WHERE (codigo_unidad_centro_costo LIKE '%".$valCadBusq[0]."%'
			OR nombre_unidad_centro_costo LIKE '%".$valCadBusq[0]."%'
			OR nombre_departamento LIKE '%".$valCadBusq[0]."%')";

	$queryCentroCosto = sprintf("SELECT
									pg_unidad_centro_costo.id_unidad_centro_costo,
									pg_unidad_centro_costo.codigo_unidad_centro_costo,
									pg_unidad_centro_costo.nombre_unidad_centro_costo,
									pg_empresa.id_empresa,
									pg_empresa.nombre_empresa,
									pg_departamento.id_departamento,
									pg_departamento.nombre_departamento
								FROM
									pg_unidad_centro_costo
								INNER JOIN pg_departamento ON (pg_unidad_centro_costo.id_departamento = pg_departamento.id_departamento)
								INNER JOIN pg_empresa ON (pg_departamento.id_empresa = pg_empresa.id_empresa) %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimitCentroCosto = sprintf(" %s %s LIMIT %d OFFSET %d", $queryCentroCosto, $sqlOrd, $maxRows, $startRow);

	$rsLimitCentroCosto = mysql_query($queryLimitCentroCosto);

	if (!$rsLimitCentroCosto)
		return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryLimitCentroCosto);

	if ($totalRows == NULL) {
		$rsCentroCosto = mysql_query($queryCentroCosto);
		if (!$rsCentroCosto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsCentroCosto);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTableIni .= "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarCentroCosto", "8%", $pageNum, "id_unidad_centro_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "ID");
		$htmlTh .= ordenarCampo("xajax_listarCentroCosto", "22%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listarCentroCosto", "36%", $pageNum, "nombre_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "Departamento");
		$htmlTh .= ordenarCampo("xajax_listarCentroCosto", "8%", $pageNum, "codigo_unidad_centro_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listarCentroCosto", "26%", $pageNum, "nombre_unidad_centro_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= "<td colspan=\"3\">Acciones</td>";
	$htmlTh .= "</tr>";

	$contFila = 0;
	while ($rowCentroCosto = mysql_fetch_assoc($rsLimitCentroCosto)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila ++;

		$htmlTb .= "<tr class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".htmlentities($rowCentroCosto['id_unidad_centro_costo'])."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($rowCentroCosto['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($rowCentroCosto['nombre_departamento'])."</td>";
			$htmlTb .= "<td>".utf8_decode($rowCentroCosto['codigo_unidad_centro_costo'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_decode($rowCentroCosto['nombre_unidad_centro_costo'])."</td>";

			$htmlTb .= "<td><img src='img/iconos/ico_view.png' class=\"puntero\" title=\"Ver\" onclick='xajax_verCentroCosto(".$rowCentroCosto['id_unidad_centro_costo'].",1)'/></td>";
			$htmlTb .= "<td><img src='img/iconos/pencil.png' class=\"puntero\" title=\"Editar\" onclick='xajax_verCentroCosto(".$rowCentroCosto['id_unidad_centro_costo'].",2)'/></td>";
			$htmlTb .= "<td><img src='img/iconos/delete.png' class=\"puntero\" title=\"Eliminar\" onclick=\"if (confirm('Desea eliminar ".utf8_decode($rowCentroCosto['nombre_unidad_centro_costo'])."?') == true) {xajax_eliminarCentroCosto(".$rowCentroCosto['id_unidad_centro_costo'].");}\"/></td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf .= "<tr class=\"tituloColumna\">";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCentroCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}

						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCentroCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";

							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarCentroCosto(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCentroCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCentroCosto(%s,'%s','%s','%s',%s);\">%s</a>",
							$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	$htmlTableFin .= "</table>";

	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("tdListarCentroCosto","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.utf8_encode($htmlTb).$htmlTf.$htmlTableFin);
	return $objResponse;
}

function verCentroCosto($idCentroCosto,$accion) {
	$objResponse = new xajaxResponse();

	if ($accion == 2){
		if (!xvalidaAcceso($objResponse,"pg_centro_costo_list","editar")){
			return $objResponse;
		}
	}

	$queryCentroCosto = sprintf("SELECT
										pg_unidad_centro_costo.id_unidad_centro_costo,
										pg_unidad_centro_costo.codigo_unidad_centro_costo,
										pg_unidad_centro_costo.nombre_unidad_centro_costo,
										pg_empresa.id_empresa,
										pg_empresa.nombre_empresa,
										pg_departamento.id_departamento,
										pg_departamento.nombre_departamento
									FROM
										pg_unidad_centro_costo
									INNER JOIN pg_departamento ON (pg_unidad_centro_costo.id_departamento = pg_departamento.id_departamento)
									INNER JOIN pg_empresa ON (pg_departamento.id_empresa = pg_empresa.id_empresa)
									WHERE
										pg_unidad_centro_costo.id_unidad_centro_costo = '".$idCentroCosto."'");

	$rsCentroCosto = mysql_query($queryCentroCosto);
	if (!$rsCentroCosto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryCentroCosto);

	$rowCentroCosto = mysql_fetch_array($rsCentroCosto);

	$objResponse->script("limpiarForm()");

	$objResponse->loadCommands(cargaLstEmpresa($rowCentroCosto['id_empresa']));
	$objResponse->loadCommands(cargaLstDepartamento($rowCentroCosto['id_empresa'], $rowCentroCosto['id_departamento']));

	$objResponse->assign("hddIdCentroCosto","value",$rowCentroCosto['id_unidad_centro_costo']);
	$objResponse->assign("txtCodigo","value",$rowCentroCosto['codigo_unidad_centro_costo']);
	$objResponse->assign("txtNombre","value",$rowCentroCosto['nombre_unidad_centro_costo']);

	$objResponse->script("
	$('divFlotante').style.display = '';
	centrarDiv($('divFlotante'));");

	if ($accion == 1){
		$objResponse->script("
		$('tdFlotanteTitulo').innerHTML = 'Ver Centro de Costo';
		$('btnGuardar').style.display = 'none'");

		$objResponse->script("
		$('lstEmpresa').disabled = true;
		$('lstDepartamento').disabled = true;
		$('txtCodigo').readOnly = true;
		$('txtNombre').readOnly = true;");
	} else {

		$objResponse->script("
		$('tdFlotanteTitulo').innerHTML = 'Editar Centro de Costo';
		$('btnGuardar').style.display = ''");

		$objResponse->script("
		$('lstEmpresa').readOnly = false;
		$('lstDepartamento').readOnly = false;
		$('txtCodigo').readOnly = false;
		$('txtNombre').readOnly = false;");
	}
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstDepartamento");
$xajax->register(XAJAX_FUNCTION,"eliminarCentroCosto");
$xajax->register(XAJAX_FUNCTION,"guardarCentroCosto");
$xajax->register(XAJAX_FUNCTION,"levantarDivFlotante");
$xajax->register(XAJAX_FUNCTION,"listarCentroCosto");
$xajax->register(XAJAX_FUNCTION,"verCentroCosto");
?>