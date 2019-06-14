<?php


function buscarCargoDepartamento($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstDepartamentoBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaCargoDepartamento(0, "id_cargo_departamento", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstCargo($selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT id_cargo, nombre_cargo FROM pg_cargo ORDER BY nombre_cargo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstCargo\" name=\"lstCargo\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_cargo']) ? "selected=\"selected\"" : "";
			
		$html .= "<option ".$selected." value=\"".$row['id_cargo']."\">".utf8_encode($row['nombre_cargo'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstCargo","innerHTML",$html);

	return $objResponse;
}

function cargaLstClaveFiltro($selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT filtro, nombre_filtro FROM pg_cargo_filtro ORDER BY nombre_filtro");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstClaveFiltro\" name=\"lstClaveFiltro\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['filtro']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['filtro']."\">".utf8_encode($row['nombre_filtro'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstClaveFiltro","innerHTML",$html);

	return $objResponse;
}

function cargaLstDepartamento($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM pg_departamento WHERE id_empresa = %s ORDER BY nombre_departamento", valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstDepartamento\" name=\"lstDepartamento\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_departamento']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_departamento']."\">".utf8_encode($row['nombre_departamento'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstDepartamento","innerHTML",$html);

	return $objResponse;
}

function cargaLstDepartamentoBuscar($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM pg_departamento WHERE id_empresa = %s ORDER BY nombre_departamento", valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstDepartamentoBuscar\" name=\"lstDepartamentoBuscar\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_departamento']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_departamento']."\">".utf8_encode($row['nombre_departamento'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstDepartamentoBuscar","innerHTML",$html);

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
	
	$html = "<select id=\"lstEmpresaCargoDep\" name=\"lstEmpresaCargoDep\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstDepartamento(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdlstEmpresaCargoDep","innerHTML",$html);
	
	return $objResponse;
}

function eliminarCargoDepartamento($idCargoDepartamento, $frmListaCargoDepartamento) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"pg_cargo_departamento_list","eliminar")){ return $objResponse; }
	
	$deleteSQL = sprintf("DELETE FROM pg_cargo_departamento WHERE id_cargo_departamento = %s;",
		valTpDato($idCargoDepartamento, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");

	$objResponse->alert("Eliminación Realizada con Éxito");
	
	$objResponse->loadCommands(listaCargoDepartamento(
		$frmListaCargoDepartamento['pageNum'],
		$frmListaCargoDepartamento['campOrd'],
		$frmListaCargoDepartamento['tpOrd'],
		$frmListaCargoDepartamento['valBusq']));
	
	return $objResponse;
}

function formCargoDepartamento($idCargoDepartamento, $frmCargoDepartamento) {
	$objResponse = new xajaxResponse();
	
	if ($idCargoDepartamento > 0) {
		if (!xvalidaAcceso($objResponse,"pg_cargo_departamento_list","editar")) { $objResponse->script("byId('btnCancelarCargoDepartamento').click();"); return $objResponse; }
		
		$query = sprintf("SELECT
			pg_departamento.nombre_departamento,
			pg_cargo.nombre_cargo,
			pg_cargo_filtro.nombre_filtro,
			pg_cargo_departamento.id_cargo_departamento,
			pg_empresa.nombre_empresa,
			pg_empresa.id_empresa,
			pg_cargo.id_cargo,
			pg_cargo_departamento.clave_filtro,
			pg_departamento.id_departamento
		FROM pg_cargo_departamento
			INNER JOIN pg_departamento ON (pg_cargo_departamento.id_departamento = pg_departamento.id_departamento)
			INNER JOIN pg_cargo ON (pg_cargo_departamento.id_cargo = pg_cargo.id_cargo)
			INNER JOIN pg_empresa ON (pg_empresa.id_empresa = pg_departamento.id_empresa)
			LEFT JOIN pg_cargo_filtro ON (pg_cargo_departamento.clave_filtro = pg_cargo_filtro.filtro)
		WHERE pg_cargo_departamento.id_cargo_departamento = %s;",
			valTpDato($idCargoDepartamento, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		$objResponse->loadCommands(cargaLstEmpresa($row['id_empresa']));
		$objResponse->loadCommands(cargaLstDepartamento($row['id_empresa'], $row['id_departamento']));
		$objResponse->loadCommands(cargaLstCargo($row['id_cargo']));
		$objResponse->loadCommands(cargaLstClaveFiltro($row['clave_filtro']));
	
		$objResponse->assign("hddIdCargoDepartamento","value",$row['id_cargo_departamento']);
	} else {
		if (!xvalidaAcceso($objResponse,"pg_cargo_departamento_list","insertar")) { $objResponse->script("byId('btnCancelarCargoDepartamento').click();"); return $objResponse; }
	
		$objResponse->loadCommands(cargaLstEmpresa());
		$objResponse->loadCommands(cargaLstDepartamento(-1));
		$objResponse->loadCommands(cargaLstCargo());
		$objResponse->loadCommands(cargaLstClaveFiltro());
	}

	return $objResponse;
}

function guardarCargoDepartamento($frmCargoDepartamento, $frmListaCargoDepartamento) {
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");

	if ($frmCargoDepartamento['hddIdCargoDepartamento'] > 0) {
		$updateSQL = sprintf("UPDATE pg_cargo_departamento SET
			id_cargo = %s,
			id_departamento = %s,
			clave_filtro = %s
		WHERE id_cargo_departamento = %s;",
			valTpDato($frmCargoDepartamento['lstCargo'],"int"),
			valTpDato($frmCargoDepartamento['lstDepartamento'],"int"),
			valTpDato($frmCargoDepartamento['lstClaveFiltro'],"int"),
			valTpDato($frmCargoDepartamento['hddIdCargoDepartamento'],"int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) {
			errorGuardarCargoDepartamento($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	} else {
		$insertSQL = sprintf("INSERT INTO pg_cargo_departamento (id_cargo, id_departamento, clave_filtro)
		VALUES (%s, %s, %s);",
			valTpDato($frmCargoDepartamento['lstCargo'],"int"),
			valTpDato($frmCargoDepartamento['lstDepartamento'],"int"),
			valTpDato($frmCargoDepartamento['lstClaveFiltro'],"int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			errorGuardarCargoDepartamento($objResponse);
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarCargoDepartamento($objResponse);
	$objResponse->alert("Cargo por Departamento Guardado con Éxito");
	
	$objResponse->script("byId('btnCancelarCargoDepartamento').click();");
	
	$objResponse->loadCommands(listaCargoDepartamento(
		$frmListaCargoDepartamento['pageNum'],
		$frmListaCargoDepartamento['campOrd'],
		$frmListaCargoDepartamento['tpOrd'],
		$frmListaCargoDepartamento['valBusq']));

	return $objResponse;
}

function listaCargoDepartamento($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
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
		$sqlBusq .= $cond.sprintf("dep.id_departamento = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_cargo LIKE %s
		OR nombre_departamento LIKE %s
		OR nombre_filtro LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT
		dep.nombre_departamento,
		cargo.nombre_cargo,
		cargo_filtro.filtro,
		cargo_filtro.nombre_filtro,
		cargo_dep.id_cargo_departamento,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM pg_cargo_departamento cargo_dep
		INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento)
		INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)						
		LEFT JOIN pg_cargo_filtro cargo_filtro ON (cargo_dep.clave_filtro = cargo_filtro.filtro)
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
		$htmlTh .= ordenarCampo("xajax_listaCargoDepartamento", "8%", $pageNum, "id_cargo_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaCargoDepartamento", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCargoDepartamento", "24%", $pageNum, "nombre_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "Departamento");
		$htmlTh .= ordenarCampo("xajax_listaCargoDepartamento", "24%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Cargo");
		$htmlTh .= ordenarCampo("xajax_listaCargoDepartamento", "24%", $pageNum, "nombre_filtro", $campOrd, $tpOrd, $valBusq, $maxRows, "Clave de Filtro");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_cargo_departamento'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".($row['nombre_departamento'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['filtro'].".- ".$row['nombre_filtro'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblCargoDepartamento', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_cargo_departamento']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/>",
					$row['id_cargo_departamento']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCargoDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}

						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCargoDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";

							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCargoDepartamento(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCargoDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCargoDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaCargoDepartamento","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarCargoDepartamento");
$xajax->register(XAJAX_FUNCTION,"cargaLstCargo");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveFiltro");
$xajax->register(XAJAX_FUNCTION,"cargaLstDepartamento");
$xajax->register(XAJAX_FUNCTION,"cargaLstDepartamentoBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarCargoDepartamento");
$xajax->register(XAJAX_FUNCTION,"formCargoDepartamento");
$xajax->register(XAJAX_FUNCTION,"guardarCargoDepartamento");
$xajax->register(XAJAX_FUNCTION,"listaCargoDepartamento");

function errorGuardarCargoDepartamento($objResponse) {
	$objResponse->script("
	byId('btnGuardarCargoDepartamento').disabled = false;
	byId('btnCancelarCargoDepartamento').disabled = false;");
}
?>