<?php


function editarPerfil($idPerfil) {
	$objResponse = new xajaxResponse();

	if (xvalidaAcceso($objResponse,"pg_perfil_list","editar")) { return $objResponse; }
	
	$queryPerfil = sprintf("SELECT
		id_perfil,
		nombre_perfil
	FROM pg_perfil
	WHERE id_perfil = %s",
		valTpDato($idPerfil, "int"));
	$rsPerfil = mysql_query($queryPerfil);
	if (!$rsPerfil) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowPerfil = mysql_fetch_array($rsPerfil);

	$objResponse->script("limpiarForm()");
	$objResponse->assign("hddIdPerfil","value",$rowPerfil['id_perfil']);
	$objResponse->assign("txtPerfil","value",$rowPerfil['nombre_perfil']);

	$objResponse->script("
	$('divFlotante').style.display = '';
	centrarDiv($('divFlotante'));");

	$objResponse->script("
	$('tdFlotanteTitulo').innerHTML = 'Editar Perfil';
	$('btnGuardar').style.display = ''");

	$objResponse->script("$('txtPerfil').readOnly = false;");
	
	return $objResponse;
}

function eliminarPerfil($idPerfil) {
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"pg_perfil_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM pg_perfil WHERE id_perfil = %s;",
		valTpDato($idPerfil, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Perfil eliminado exitosamente.");
	
	$objResponse->script("xajax_listarPerfil(0,'','','')");
	
	return $objResponse;
}

function guardarPerfil($frmPerfil) {
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");
	
	if ($frmPerfil['hddIdPerfil'] > 0) {
		if (!xvalidaAcceso($objResponse,"pg_perfil_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_perfil SET
			nombre_perfil = %s
		WHERE id_perfil = %s",
			valTpDato($frmPerfil['txtPerfil'],"text"),
			valTpDato($frmPerfil['hddIdPerfil'],"int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_perfil_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_perfil (nombre_perfil)
		VALUES (%s);",
			valTpDato($frmPerfil['txtPerfil'],"text"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) {
			if (mysql_errno() == 1062) {
				return $objResponse->alert("Ya se encuentra registrado el Cargo: ".$frmPerfil['txtPerfil'].".");
			} else {
				return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			}
		}
		mysql_query("SET NAMES 'latin1';");
	}

	mysql_query("COMMIT;");

	$objResponse->alert("Perfil Guardado con Éxito");

	$objResponse->script("
	xajax_listarPerfil(0,'id_perfil','','');
	$('divFlotante').style.display = 'none';");

	return $objResponse;
}

function levantarDivFlotante() {
	$objResponse = new xajaxResponse();

	if (xvalidaAcceso($objResponse,"pg_perfil_list","insertar")) {
		$objResponse->script("document.forms['frmPerfil'].reset();
		$('divFlotante').style.display = '';
		centrarDiv($('divFlotante'));
		$('btnGuardar').style.display = '';
		$('hddIdPerfil').value = 0;");

		$objResponse->assign("tdFlotanteTitulo","innerHTML","Nuevo Perfil");
	}
	
	return $objResponse;
}

function listarPerfil($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != '') {//Busqueda(Perfil/ID)
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_perfil LIKE %s
		OR id_perfil LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}

	$queryPerfil = sprintf("SELECT * FROM pg_perfil %s",$sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimitPerfil = sprintf(" %s %s LIMIT %d OFFSET %d", $queryPerfil, $sqlOrd, $maxRows, $startRow);
	$rsLimitPerfil = mysql_query($queryLimitPerfil);
	if (!$rsLimitPerfil) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryLimitPerfil);
	if ($totalRows == NULL) {
		$rsPerfil = mysql_query($queryPerfil);
		if (!$rsPerfil) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rsPerfil);
	}
	
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarPerfil", "6%", $pageNum, "id_perfil", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listarPerfil", "94%", $pageNum, "nombre_perfil",$campOrd, $tpOrd,$valBusq, $maxRows, "Perfiles de Usuario");
		$htmlTh .= "<td colspan=\"4\">Acciones</td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowPerfil = mysql_fetch_assoc($rsLimitPerfil)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".($rowPerfil['id_perfil'])."</td>";
			$htmlTb .= "<td>".($rowPerfil['nombre_perfil'])."</td>";
			//$htmlTb .= "<td><img src='img/iconos/edit_privilegios.png' class=\"puntero\" title=\"Editar Privilegios\" onclick='xajax_editarPrivilegios(".$rowPerfil['id_perfil'].")' /></td>";
			$htmlTb .= sprintf("<td align=\"center\" title=\"Editar Privilegios\"><a href=\"pg_perfil_privilegio_form.php?idPerfil=".$rowPerfil['id_perfil']."\"><img src='img/iconos/edit_privilegios.png'></a></td>",$rowPerfil['id_perfil']);
			$htmlTb .= "<td><img src='img/iconos/pencil.png' class=\"puntero\" title=\"Editar\" onclick='xajax_editarPerfil(".$rowPerfil['id_perfil'].")' /></td>";
			$htmlTb .= "<td><img src='img/iconos/delete.png' class=\"puntero\" title=\"Eliminar\" onclick=\"if (confirm('Desea eliminar ".htmlentities($rowPerfil['nombre_perfil'])."?') == true) {xajax_eliminarPerfil(".$rowPerfil['id_perfil'].");}\"/></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPerfil(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}

						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPerfil(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";

							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarPerfil(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPerfil(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPerfil(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("tdlistarPerfil","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.utf8_encode($htmlTb).$htmlTf.$htmlTblFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"editarPerfil");
$xajax->register(XAJAX_FUNCTION,"eliminarPerfil");
$xajax->register(XAJAX_FUNCTION,"guardarPerfil");
$xajax->register(XAJAX_FUNCTION,"levantarDivFlotante");
$xajax->register(XAJAX_FUNCTION,"listarPerfil");

?>