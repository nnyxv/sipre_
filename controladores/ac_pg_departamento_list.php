<?php


function buscarDepartamento($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaDepartamento(0, "id_departamento", "ASC", $valBusq));
	
	return $objResponse;
}

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
	
		$htmlOption .= "<option ".$selected." value=\"".$rowEmpresaSuc['id_empresa_reg']."\">".htmlentities($rowEmpresaSuc['nombre_empresa'])."</option>";	
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
		
			$htmlOption .= "<option ".$selected." value=\"".$rowEmpresaSuc['id_empresa_reg']."\">".htmlentities($rowEmpresaSuc['nombre_empresa_suc'])."</option>";	
		}
	
		$htmlOption .= "</optgroup>";
	}
	
	$html = "<select id=\"lstEmpresaDepartamento\" name=\"lstEmpresaDepartamento\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdlstEmpresaDepartamento","innerHTML",$html);

	return $objResponse;
}

function eliminarDepartamento($idDepartamento, $frmListaDepartamento) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"pg_departamento_list","eliminar")){ return $objResponse; }
	
	$deleteSQL = sprintf("DELETE FROM pg_departamento WHERE id_departamento = %s;",
		valTpDato($idDepartamento, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->loadCommands(listaDepartamento(
		$frmListaDepartamento['pageNum'],
		$frmListaDepartamento['campOrd'],
		$frmListaDepartamento['tpOrd'],
		$frmListaDepartamento['valBusq']));
	
	return $objResponse;
}

function formDepartamento($idDepartamento, $frmDepartamento) {
	$objResponse = new xajaxResponse();

	if ($idDepartamento > 0) {
		if (!xvalidaAcceso($objResponse,"pg_departamento_list","editar")) { $objResponse->script("byId('btnCancelarDepartamento').click();"); return $objResponse; }
	
		$query = sprintf("SELECT * FROM pg_departamento WHERE id_departamento = %s;", valTpDato($idDepartamento, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
	
		$objResponse->loadCommands(cargaLstEmpresa($row['id_empresa']));
		$objResponse->assign("hddIdDepartamento","value",$row['id_departamento']);
		$objResponse->assign("txtDepartamento","value",$row['nombre_departamento']);
		$objResponse->assign("txtCodigo","value",$row['codigo_departamento']);
	} else {
		if (!xvalidaAcceso($objResponse,"pg_departamento_list","insertar")) { $objResponse->script("byId('btnCancelarDepartamento').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstEmpresa());
	}

	return $objResponse;
}

function guardarDepartamento($frmDepartamento, $frmListaDepartamento) {
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");

	if ($frmDepartamento['hddIdDepartamento'] > 0) {
		if (!xvalidaAcceso($objResponse,"pg_departamento_list","editar")) { errorGuardarDepartamento($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_departamento SET
			nombre_departamento = %s,
			codigo_departamento = %s,
			id_empresa = %s
		WHERE id_departamento = %s;",
			valTpDato($frmDepartamento['txtDepartamento'], "text"),
			valTpDato($frmDepartamento['txtCodigo'], "text"),
			valTpDato($frmDepartamento['lstEmpresaDepartamento'], "int"),
			valTpDato($frmDepartamento['hddIdDepartamento'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {
			errorGuardarDepartamento($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_departamento_list","insertar")) { errorGuardarDepartamento($objResponse); return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_departamento (nombre_departamento, codigo_departamento, id_empresa)
		VALUES (%s, %s, %s);",
			valTpDato($frmDepartamento['txtDepartamento'], "text"),
			valTpDato($frmDepartamento['txtCodigo'], "text"),
			valTpDato($frmDepartamento['lstEmpresaDepartamento'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			errorGuardarDepartamento($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarDepartamento($objResponse);
	$objResponse->alert("Departamento Guardado con Éxito");
	
	$objResponse->script("byId('btnCancelarDepartamento').click();");
	
	$objResponse->loadCommands(listaDepartamento(
		$frmListaDepartamento['pageNum'],
		$frmListaDepartamento['campOrd'],
		$frmListaDepartamento['tpOrd'],
		$frmListaDepartamento['valBusq']));

	return $objResponse;
}

function listaDepartamento($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("dep.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_departamento LIKE %s
		OR codigo_departamento LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		dep.id_departamento,
		dep.nombre_departamento,
		dep.codigo_departamento,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM pg_departamento dep
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dep.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);

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
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaDepartamento", "8%", $pageNum, "id_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaDepartamento", "10%", $pageNum, "codigo_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listaDepartamento", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaDepartamento", "62%", $pageNum, "nombre_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "Departamento");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['id_departamento'])."</td>";
			$htmlTb .= "<td align=\"right\">".htmlentities($row['codigo_departamento'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".($row['nombre_departamento'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblDepartamento', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_departamento']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/>",
					$row['id_departamento']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"6\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}

						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";

							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaDepartamento(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";

	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"6\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaDepartamento","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarDepartamento");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarDepartamento");
$xajax->register(XAJAX_FUNCTION,"guardarDepartamento");
$xajax->register(XAJAX_FUNCTION,"listaDepartamento");
$xajax->register(XAJAX_FUNCTION,"formDepartamento");

function errorGuardarDepartamento($objResponse) {
	$objResponse->script("
	byId('btnGuardarDepartamento').disabled = false;
	byId('btnCancelarDepartamento').disabled = false;");
}
?>