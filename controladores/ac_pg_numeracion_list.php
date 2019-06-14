<?php


function asignarNumeracion($idNumeracion) {
	$objResponse = new xajaxResponse();
	
	$queryMoneda = sprintf("SELECT * FROM pg_numeracion WHERE id_numeracion = %s;", valTpDato($idNumeracion, "int"));
	$rsMoneda = mysql_query($queryMoneda);
	if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMoneda = mysql_fetch_assoc($rsMoneda);
	
	$objResponse->assign("txtIdNumeracion","value",$rowMoneda['id_numeracion']);
	$objResponse->assign("txtNumeracion","value",utf8_encode($rowMoneda['nombreNumeraciones']));
	
	$objResponse->script("
	byId('btnCancelarListaNumeracion').click();");
	
	return $objResponse;
}

function buscarEmpresa($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$valForm['hddObjDestino'],
		$valForm['hddNomVentana'],
		$valForm['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarNumeracion($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarNumeracion']);
	
	$objResponse->loadCommands(listaNumeracion(0, "id_numeracion", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarEmpresaNumeracion($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaEmpresaNumeracion(0, "id_numeracion", "ASC", $valBusq));
	
	return $objResponse;
}

function cargarEmpresaNumeracion($nomObjeto, $idEmpresaNumeracion) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"pg_numeracion_list","editar")) {
		$objResponse->script("
		document.forms['frmEmpresaNumeracion'].reset();
		byId('hddIdEmpresaNumeracion').value = '';
		
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdNumeracion').className = 'inputHabilitado';
		byId('txtNumeroInicio').className = 'inputHabilitado';
		byId('txtNumeroActual').className = 'inputHabilitado';
		byId('lstAplicaSucursales').className = 'inputHabilitado';");
	
		$query = sprintf("SELECT * FROM pg_empresa_numeracion
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdEmpresaNumeracion","value",$row['id_empresa_numeracion']);
		$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'],'Empresa','ListaEmpresa'));
		$objResponse->loadCommands(asignarNumeracion($row['id_numeracion']));
		$objResponse->assign("txtNumeroInicio","value",number_format($row['numero_inicio'],2,".",","));
		$objResponse->assign("txtNumeroActual","value",number_format($row['numero_actual'],2,".",","));
		$objResponse->call("selectedOption","lstAplicaSucursales",$row['aplica_sucursales']);
		
		$objResponse->script("openImg(byId('".$nomObjeto."'));");
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Tasa de Cambio");
		$objResponse->script("
		byId('txtIdMonedaExtranjera').focus();
		byId('txtIdMonedaExtranjera').select();");
	}
	
	return $objResponse;
}

function eliminarEmpresaNumeracion($idEmpresaNumeracion, $valFormListaEmpresaNumeracion) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"pg_numeracion_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM pg_empresa_numeracion WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
	
		$objResponse->alert("Eliminación Realizada con Éxito");
		
		$objResponse->loadCommands(listaEmpresaNumeracion(
			$valFormListaEmpresaNumeracion['pageNum'],
			$valFormListaEmpresaNumeracion['campOrd'],
			$valFormListaEmpresaNumeracion['tpOrd'],
			$valFormListaEmpresaNumeracion['valBusq']));
	}
	
	return $objResponse;
}

function formEmpresaNumeracion($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"pg_numeracion_list","insertar")) {
		$objResponse->script("
		document.forms['frmEmpresaNumeracion'].reset();
		byId('hddIdEmpresaNumeracion').value = '';
		
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdNumeracion').className = 'inputHabilitado';
		byId('txtNumeroInicio').className = 'inputHabilitado';
		byId('txtNumeroActual').className = 'inputHabilitado';
		byId('lstAplicaSucursales').className = 'inputHabilitado';");
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'],'Empresa','ListaEmpresa'));
		
		$objResponse->script("openImg(byId('".$nomObjeto."'));");
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Tasa de Cambio");
		$objResponse->script("
		byId('txtIdMonedaExtranjera').focus();
		byId('txtIdMonedaExtranjera').select();");
	}
	
	return $objResponse;
}

function guardarEmpresaNumeracion($valForm, $valFormListaEmpresaNumeracion) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdEmpresaNumeracion'] > 0) {
		if (xvalidaAcceso($objResponse,"pg_numeracion_list","editar")) {
			$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET
				id_empresa = %s,
				id_numeracion = %s,
				numero_inicio = %s,
				numero_actual = %s,
				aplica_sucursales = %s
			WHERE id_empresa_numeracion = %s;",
				valTpDato($valForm['txtIdEmpresa'], "int"),
				valTpDato($valForm['txtIdNumeracion'], "int"),
				valTpDato($valForm['txtNumeroInicio'], "real_inglesa"),
				valTpDato($valForm['txtNumeroActual'], "real_inglesa"),
				valTpDato($valForm['lstAplicaSucursales'], "boolean"),
				valTpDato($valForm['hddIdEmpresaNumeracion'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) {
				if (mysql_errno() == 1062) {
					return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
				} else {
					return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"pg_numeracion_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO pg_empresa_numeracion (id_empresa, id_numeracion, numero_inicio, numero_actual, aplica_sucursales)
			VALUE (%s, %s, %s, %s, %s);",
				valTpDato($valForm['txtIdEmpresa'], "int"),
				valTpDato($valForm['txtIdNumeracion'], "int"),
				valTpDato($valForm['txtNumeroInicio'], "real_inglesa"),
				valTpDato($valForm['txtNumeroActual'], "real_inglesa"),
				valTpDato($valForm['lstAplicaSucursales'], "boolean"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) {
				if (mysql_errno() == 1062) {
					return $objResponse->alert("Registro Duplicado"."\n\nLine: ".__LINE__);
				} else {
					return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				}
			}
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Numeración Guardada con Éxito");
	
	$objResponse->script("
	byId('btnCancelarEmpresaNumeracion').click();");
	
	$objResponse->loadCommands(listaEmpresaNumeracion(
		$valFormListaEmpresaNumeracion['pageNum'],
		$valFormListaEmpresaNumeracion['campOrd'],
		$valFormListaEmpresaNumeracion['tpOrd'],
		$valFormListaEmpresaNumeracion['valBusq']));
	
	return $objResponse;
}

function listaEmpresaNumeracion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("emp_num.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(num.id_numeracion LIKE %s
		OR num.nombreNumeraciones LIKE %s
		OR num.prefijo_numeracion LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		emp_num.*,
		num.nombreNumeraciones,
		num.prefijo_numeracion,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM pg_empresa_numeracion emp_num
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (emp_num.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion) %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaEmpresaNumeracion", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaEmpresaNumeracion", "46%", $pageNum, "nombreNumeraciones", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaEmpresaNumeracion", "6%", $pageNum, "prefijo_numeracion", $campOrd, $tpOrd, $valBusq, $maxRows, "Prefijo");
		$htmlTh .= ordenarCampo("xajax_listaEmpresaNumeracion", "12%", $pageNum, "numero_inicio", $campOrd, $tpOrd, $valBusq, $maxRows, "Número Inicio");
		$htmlTh .= ordenarCampo("xajax_listaEmpresaNumeracion", "12%", $pageNum, "numero_actual", $campOrd, $tpOrd, $valBusq, $maxRows, "Número Actual");
		$htmlTh .= ordenarCampo("xajax_listaEmpresaNumeracion", "10%", $pageNum, "aplica_sucursales", $campOrd, $tpOrd, $valBusq, $maxRows, "Aplicar a sus Sucursales");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['id_numeracion'].".- ".$row['nombreNumeraciones'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['prefijo_numeracion'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['numero_inicio'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['numero_actual'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['aplica_sucursales'] == 1) ? "Si": "-")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_cargarEmpresaNumeracion(this.id,'%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_empresa_numeracion']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/></td>",
				$row['id_empresa_numeracion']);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresaNumeracion(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEmpresaNumeracion","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listaNumeracion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombreNumeraciones LIKE %s
		OR prefijo_numeracion LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM pg_numeracion %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaNumeracion", "8%", $pageNum, "id_numeracion", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaNumeracion", "78%", $pageNum, "nombreNumeraciones", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaNumeracion", "14%", $pageNum, "prefijo_numeracion", $campOrd, $tpOrd, $valBusq, $maxRows, "Prefijo");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarNumeracion('".$row['id_numeracion']."');\" title=\"Seleccionar\"><img src=\"img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_numeracion']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombreNumeraciones'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['prefijo_numeracion'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNumeracion(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNumeracion(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaNumeracion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarNumeracion");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresaNumeracion");
$xajax->register(XAJAX_FUNCTION,"buscarNumeracion");
$xajax->register(XAJAX_FUNCTION,"cargarEmpresaNumeracion");
$xajax->register(XAJAX_FUNCTION,"eliminarEmpresaNumeracion");
$xajax->register(XAJAX_FUNCTION,"formEmpresaNumeracion");
$xajax->register(XAJAX_FUNCTION,"guardarEmpresaNumeracion");
$xajax->register(XAJAX_FUNCTION,"listaEmpresaNumeracion");
$xajax->register(XAJAX_FUNCTION,"listaNumeracion");
?>