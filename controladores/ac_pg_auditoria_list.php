<?php


function buscarAuditoria($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstAcceso'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaAuditoria(0, "fecha", "DESC", $valBusq));
	
	return $objResponse;
}

function exportarAuditoria($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstAcceso'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/pg_auditoria_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaAuditoria($pageNum = 0, $campOrd = "fecha", $tpOrd = "DESC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("audit.id_empresa = %s",
			valTpDato($valCadBusq[0],"int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("audit.acceso = %s",
			valTpDato($valCadBusq[3],"int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_usu.cedula LIKE %s
		OR vw_iv_usu.nombre_empleado LIKE %s
		OR vw_iv_usu.nombre_departamento LIKE %s
		OR vw_iv_usu.nombre_cargo LIKE %s
		OR vw_iv_usu.nombre_usuario LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT
		audit.fecha,
		vw_iv_usu.nombre_empleado,
		vw_iv_usu.nombre_departamento,
		vw_iv_usu.nombre_cargo,
		vw_iv_usu.nombre_usuario,
		elemento_menu.id_padre,
		elemento_menu.nombre,
		audit.accion,
		audit.acceso
	FROM vw_iv_usuarios vw_iv_usu
		INNER JOIN pg_auditoria audit ON (vw_iv_usu.id_usuario = audit.id_usuario)
		INNER JOIN pg_elemento_menu elemento_menu ON (audit.id_elemento_menu = elemento_menu.id_elemento_menu) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "12%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Hora / Fecha");
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "18%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Empleado");
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "22%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Cargo");
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "8%", $pageNum, "nombre_usuario", $campOrd, $tpOrd, $valBusq, $maxRows, "Usuario");
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "34%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo / Sección");
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "6%", $pageNum, "accion", $campOrd, $tpOrd, $valBusq, $maxRows, "Acción");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$imgEstatusCargo = ($row['unipersonal'] == 1) ? "<img src=\"img/iconos/user_suit.png\" title=\"Cargo Unipersonal\"/>" : "";
		
		switch($row['accion']) {
			case "insertar" : $imgAccion = "<img src=\"img/iconos/ico_new.png\" title=\"Insertar\"/>"; break;
			case "editar" : $imgAccion = "<img src=\"img/iconos/pencil.png\" title=\"Editar\"/>"; break;
			case "eliminar" : $imgAccion = "<img src=\"img/iconos/ico_delete.png\" title=\"Editar\"/>"; break;
			default : $imgAccion = "";
		}
		
		switch($row['acceso']) {
			case 0 : $imgEstatus = "<img src=\"img/iconos/ico_rojo.gif\" title=\"Denegado\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"img/iconos/ico_verde.gif\" title=\"Permitido\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$arrayNombreElementoMenu = NULL;
		$arrayNombreElementoMenu[] = utf8_encode($row['nombre']);
		$idEmpresaPadre = $row['id_padre'];
		do {
			$queryElementoMenu = sprintf("SELECT * FROM pg_elemento_menu elemento_menu WHERE elemento_menu.id_elemento_menu = %s;",
				valTpDato($idEmpresaPadre, "int"));
			$rsElementoMenu = mysql_query($queryElementoMenu);
			if (!$rsElementoMenu) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowElementoMenu = mysql_fetch_assoc($rsElementoMenu);
			
			$arrayNombreElementoMenu[] = utf8_encode($rowElementoMenu['nombre']);
			$idEmpresaPadre = $rowElementoMenu['id_padre'];
		} while ($idEmpresaPadre > 0);
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i a",strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td colspan=\"2\">".utf8_encode($row['nombre_departamento'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_cargo'])."</td>";
					$htmlTb .= "<td>".$imgEstatusCargo."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td class=\"divMsjInfo\">".utf8_encode($row['nombre_usuario'])."</td>";
			$htmlTb .= "<td>".implode("->", array_reverse($arrayNombreElementoMenu))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table>";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgAccion."</td>";
					$htmlTb .= "<td>".utf8_encode($row['accion'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaAuditoria","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarAuditoria");
$xajax->register(XAJAX_FUNCTION,"exportarAuditoria");
$xajax->register(XAJAX_FUNCTION,"listaAuditoria");
?>