<?php


function buscarDeposito($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaDeposito(0, "CONCAT_WS(' ', deposito.idPlanilla, deposito_det.idDeposito)", "DESC", $valBusq));
		
	return $objResponse;
}

function cargarPagina($idEmpresa){
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}
	
	if ($rowConfig400['valor'] == 0) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, "onchange=\"selectedOption(this.id,'".$idEmpresa."');\""));
	} else {
		$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa));
	}
	
	$objResponse->script("xajax_buscarDeposito(xajax.getFormValues('frmBuscar'));");
	
	return $objResponse;
}

function listaDeposito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $idCajaPpal;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("deposito.idCaja = %s",
		valTpDato($idCajaPpal, "int")); // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(deposito.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = deposito.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("deposito.fechaPlanilla BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
		
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = sprintf(" AND deposito.numeroDeposito LIKE %s",
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		deposito.idPlanilla,
		deposito.id_empresa,
		deposito.fechaPlanilla,
		deposito.numeroDeposito,
		deposito_det.numeroDeposito AS numero_planilla,
		deposito_det.anulada,
		usuario.id_usuario, 
		usuario.nombre_usuario,
		
		(SELECT SUM(deposito_det2.monto) FROM an_detalledeposito deposito_det2
		WHERE deposito_det2.idPlanilla = deposito.idPlanilla
			AND deposito_det2.formaPago IN (1)
			AND deposito_det2.anulada LIKE 'NO') AS total_efectivo,
			
		(SELECT SUM(deposito_det2.monto) FROM an_detalledeposito deposito_det2
		WHERE deposito_det2.idPlanilla = deposito.idPlanilla
			AND deposito_det2.formaPago IN (2)
			AND deposito_det2.anulada LIKE 'NO') AS total_cheque,
			
		(SELECT SUM(deposito_det2.monto) FROM an_detalledeposito deposito_det2
		WHERE deposito_det2.idPlanilla = deposito.idPlanilla
			AND deposito_det2.formaPago IN (1,2)
			AND deposito_det2.anulada LIKE 'NO') AS total_planilla,
			
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_encabezadodeposito deposito
		INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		INNER JOIN pg_usuario usuario ON (deposito.id_usuario = usuario.id_usuario)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (deposito.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse ->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "10%", $pageNum, "fechaPlanilla", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Dep&oacute;sito"));
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "12%", $pageNum, "numeroDeposito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Dep&oacute;sito"));
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "12%", $pageNum, "numero_planilla", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Planilla"));
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "14%", $pageNum, "total_efectivo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total en Efectivo"));
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "14%", $pageNum, "total_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total en Cheque(s)"));
		$htmlTh .= ordenarCampo("xajax_listaDeposito", "14%", $pageNum, "total_planilla", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total en Planilla(s)"));
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['anulada']) {
			case "SI" : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Planilla Anulada\"/>"; break;
			case "NO" : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Planilla Activa\"/>"; break;
			default : $row['anulada'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaPlanilla']))."</td>";
			$htmlTb .= "<td align=\"center\">".($row['numeroDeposito'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['numero_planilla'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_efectivo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_cheque'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_planilla'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
			if ($row['anulada'] == "NO") { // EDITAR
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_formListaPlanilla('%s');\" src=\"../img/iconos/pencil.png\" title=\"Editar Planilla\"/>",
					$row['idPlanilla']); 
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['anulada'] == "NO") { // IMPRIMIR
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/cjvh_impresion_planilla_deposito_pdf.php?valBusq=%s|%s', 1010, 500);\" src=\"../img/iconos/ico_print.png\" title=\"Imprimir\"/>",
					$row['id_empresa'],
					$row['idPlanilla']); 
			}
			$htmlTb .= "</td>";
			//$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" title=\"Imprimir Planilla\" src=\"../img/iconos/ico_print.png\" border=\"0\" onclick=\"window.open('reimpresionPlanillasAdepositar.php?idPlanilla=".$row['idPlanilla']."','_blank');\"></td>"; //IMPRIMIR
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"12\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaDeposito(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf.= "<option value=\"".$nroPag."\"";
								if ($pageNum == $nroPag) {
									$htmlTf.= "selected=\"selected\"";
								}
								$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaDeposito","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPlanilla($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	$query = sprintf("SELECT DISTINCT
		deposito.idPlanilla,
		deposito.fechaPlanilla,
		deposito_det.idDeposito,
		deposito_det.numeroDeposito,
		deposito_det.numeroCuentaBancoAdepositar,
		deposito_det.anulada,
		banco.idBanco,
		banco.nombreBanco
	FROM an_encabezadodeposito deposito
		INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		INNER JOIN bancos banco ON (deposito_det.idBancoAdepositar = banco.idBanco)
	WHERE deposito.idPlanilla = %s
		AND deposito.id_empresa = %s
	GROUP BY deposito_det.numeroDeposito",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($idEmpresa, "int"));
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse ->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPlanilla", "12%", $pageNum, "fechaPlanilla", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Planilla"));
		$htmlTh .= ordenarCampo("xajax_listaPlanilla", "20%", $pageNum, "numeroDeposito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Planilla"));
		$htmlTh .= ordenarCampo("xajax_listaPlanilla", "30%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Banco"));
		$htmlTh .= ordenarCampo("xajax_listaPlanilla", "24%", $pageNum, "numeroCuentaBancoAdepositar", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Cuenta"));
		$htmlTh .= ordenarCampo("xajax_listaPlanilla", "10%", $pageNum, "anulada", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Anulada?"));
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaPlanilla']))."</td>";
			$htmlTb .= "<td align=\"center\">".($row['numeroDeposito'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['numeroCuentaBancoAdepositar'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['anulada'])."</td>";
			$htmlTb .= "<td>";
			if ($row['anulada'] == 'NO') {
				$htmlTb .= "<img class=\"puntero\" onclick=\"xajax_formPlanilla('".$row['idPlanilla']."')\" src=\"../img/iconos/pencil.png\"/>";
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"right\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPlanilla(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPlanilla(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPlanilla(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf.= "<option value=\"".$nroPag."\"";
								if ($pageNum == $nroPag) {
									$htmlTf.= "selected=\"selected\"";
								}
								$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";

						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPlanilla(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPlanilla(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cj_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"14\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdlistaPlanilla","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function formListaPlanilla($idPlanilla){
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(listaPlanilla(0,"","",$idPlanilla));
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Listado de Planillas");
	$objResponse->script("
	if (byId('divFlotante').style.display == 'none') {
		byId('divFlotante').style.display = '';
		centrarDiv(byId('divFlotante'));
	}");
	
	return $objResponse;
}

function formPlanilla($idPlanilla){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmNroPlanilla'].reset();
	byId('txtNroPlanilla').readOnly = true;
	byId('txtNuevoNroPlanilla').readOnly = false;");

	$queryPlanilla = sprintf("SELECT
		deposito.idPlanilla,
		deposito.fechaPlanilla,
		deposito_det.idDeposito,
		deposito_det.numeroDeposito,
		deposito_det.numeroCuentaBancoAdepositar,
		deposito_det.anulada,
		banco.idBanco,
		banco.nombreBanco
	FROM an_encabezadodeposito deposito
		INNER JOIN an_detalledeposito deposito_det ON (deposito.idPlanilla = deposito_det.idPlanilla)
		INNER JOIN bancos banco ON (deposito_det.idBancoAdepositar = banco.idBanco)
	WHERE deposito.idPlanilla = %s
		AND deposito_det.anulada LIKE 'NO';",
		valTpDato($idPlanilla, "int"));
	$rsPlanilla = mysql_query($queryPlanilla);
	if(!$rsPlanilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPlanilla = mysql_fetch_array($rsPlanilla);
	
	$objResponse->assign("hddIdPlanilla","value",$rowPlanilla['idPlanilla']);
	$objResponse->assign("hddIdBanco","value",$rowPlanilla['idBanco']);
	$objResponse->assign("txtNroPlanilla","value",$rowPlanilla['numeroDeposito']);
	$objResponse->assign("txtNuevoNroPlanilla","value","");
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Editar Nro. Planilla");
	$objResponse->script("
	if (byId('divFlotante2').style.display == 'none') {
		byId('divFlotante2').style.display = '';
		centrarDiv(byId('divFlotante2'));
	}");
	
	$objResponse->script("
	byId('txtNuevoNroPlanilla').className = 'inputHabilitado';
	byId('txtNuevoNroPlanilla').focus();
	byId('txtNuevoNroPlanilla').select();");
	
	return $objResponse;
}

function guardarPlanilla($frmPlanilla, $frmListaDeposito){
	$objResponse = new xajaxResponse();
	
	// VERIFICAR SI YA EXISTE EL NUMERO DE PLANILLA (DEL MISMO BANCO) A REGISTRAR EN SISTEMA
	$sqlVerificar = sprintf("SELECT * FROM an_detalledeposito
	WHERE idBancoAdepositar = %s
		AND numeroDeposito LIKE %s
		AND anulada LIKE 'NO'",
		valTpDato($frmPlanilla['hddIdBanco'], "int"),
		valTpDato($frmPlanilla['txtNuevoNroPlanilla'], "text"));
	$rsVerificar = mysql_query($sqlVerificar);
	if (!$rsVerificar) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsVerificar = mysql_num_rows($rsVerificar);
	
	if ($totalRowsVerificar > 0) {
		$objResponse->alert("Ya existe una planilla de deposito con el Nro. ".$frmPlanilla['txtNuevoNroPlanilla']);
	} else {
		mysql_query("START TRANSACTION;");
		
		// COPIA LA PLANILLA AGREGADA Y LA INSERTA NUEVAMENTE CON ESTADO "NO" anulado
		$sqlCopiarNuevaPlanilla = sprintf("INSERT INTO an_detalledeposito (idPagoRelacionadoConNroCheque, numeroCheque, banco, numeroCuenta, monto, idBancoAdepositar, numeroCuentaBancoAdepositar, formaPago, conformado, tipoDeCheque, numeroDeposito, idTipoDocumento, idPlanilla, anulada, idCaja)
		SELECT
			idPagoRelacionadoConNroCheque,
			numeroCheque,
			banco,
			numeroCuenta,
			monto,
			idBancoAdepositar,
			numeroCuentaBancoAdepositar,
			formaPago,
			conformado,
			tipoDeCheque,
			%s,
			idTipoDocumento,
			idPlanilla,
			'NO',
			idCaja
		FROM an_detalledeposito
		WHERE idPlanilla = %s
			AND idBancoAdepositar = %s
			AND anulada LIKE 'NO'",
			valTpDato($frmPlanilla['txtNuevoNroPlanilla'], "text"),
			valTpDato($frmPlanilla['hddIdPlanilla'], "text"),
			valTpDato($frmPlanilla['hddIdBanco'], "int"));
		$rsCopiarNuevaPlanilla = mysql_query($sqlCopiarNuevaPlanilla);
		if (!$rsCopiarNuevaPlanilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// ANULA LOS DETALLES QUE TENIAN EL NRO DE DEPOSITO ANTERIOR
		$sqlUpdate = sprintf("UPDATE an_detalledeposito SET
			anulada = 'SI'
		WHERE idPlanilla = %s
			AND idBancoAdepositar = %s
			AND numeroDeposito LIKE %s",
			valTpDato($frmPlanilla['hddIdPlanilla'], "text"),
			valTpDato($frmPlanilla['hddIdBanco'], "int"),
			valTpDato($frmPlanilla['txtNroPlanilla'], "text"));
		$rsUpdate = mysql_query($sqlUpdate);
		if (!$rsUpdate) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		// EDITAR PLANILLA TESORERIA
		$sqlUpdateTesoreriaDeposito = sprintf("UPDATE te_depositos SET
			numero_deposito_banco = %s
		WHERE numero_deposito_banco = %s
			AND (SELECT idBanco FROM cuentas WHERE idCuentas = id_numero_cuenta) = %s",
			valTpDato($frmPlanilla['txtNuevoNroPlanilla'], "text"),
			valTpDato($frmPlanilla['txtNroPlanilla'], "text"),
			valTpDato($frmPlanilla['hddIdBanco'], "int"));
		$rsUpdateTesoreriaDeposito = mysql_query($sqlUpdateTesoreriaDeposito);
		if (!$rsUpdateTesoreriaDeposito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$sqlUpdateTesoreriaEstadoCuenta = sprintf("UPDATE te_estado_cuenta SET
			numero_documento = %s
		WHERE numero_documento = %s
			AND (SELECT idBanco FROM cuentas WHERE idCuentas = id_cuenta) = %s
			AND tipo_documento LIKE 'DP'",
			valTpDato($frmPlanilla['txtNuevoNroPlanilla'], "text"),
			valTpDato($frmPlanilla['txtNroPlanilla'], "text"),
			valTpDato($frmPlanilla['hddIdBanco'], "int"));
		$rsUpdateTesoreriaEstadoCuenta = mysql_query($sqlUpdateTesoreriaEstadoCuenta);
		if (!$rsUpdateTesoreriaEstadoCuenta) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Nro. de Planilla editado exitosamente.");
		
		$objResponse->script("byId('divFlotante2').style.display = 'none'");
		
		$objResponse->script("xajax_formListaPlanilla('".$frmPlanilla['hddIdPlanilla']."')");
		
		$objResponse->loadCommands(listaDeposito(
			$frmListaDeposito['pageNum'],
			$frmListaDeposito['campOrd'],
			$frmListaDeposito['tpOrd'],
			$frmListaDeposito['valBusq']));
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"listaDeposito");
$xajax->register(XAJAX_FUNCTION,"listaPlanilla");
$xajax->register(XAJAX_FUNCTION,"formListaPlanilla");
$xajax->register(XAJAX_FUNCTION,"formPlanilla");
$xajax->register(XAJAX_FUNCTION,"guardarPlanilla");
?>