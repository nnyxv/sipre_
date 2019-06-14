<?php


function buscarCheque($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstatus'],
		implode(",",$frmBuscar['lstEstadoCheque']),
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->loadCommands(listaCheque(0, "id_cheque", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 1) ? "multiple" : "")." id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstOrientacionPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array("V" => "Vertical", "H" => "Horizontal");
	$totalRows = count($array);
		
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirCheque(xajax.getFormValues('frmBuscar'));\"";
	
	$html = "<select id=\"lstOrientacionPDF\" name=\"lstOrientacionPDF\" ".$class." ".$onChange.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstOrientacionPDF","innerHTML",$html);
	
	return $objResponse;
}

function exportarCheque($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstatus'],
		implode(",",$frmBuscar['lstEstadoCheque']),
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/cc_cheque_historico_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function imprimirCheque($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstatus'],
		implode(",",$frmBuscar['lstEstadoCheque']),
		implode(",",$frmBuscar['lstModulo']),
		$frmBuscar['txtCriterio']);
		
	$objResponse->script(sprintf("verVentana('reportes/cc_cheque_historico_pdf.php?valBusq=%s&lstOrientacionPDF=%s',890,550)", $valBusq, $frmBuscar['lstOrientacionPDF']));
	
	$objResponse->assign("tdlstOrientacionPDF","innerHTML","");
	
	return $objResponse;
}

function listaCheque($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $raiz;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ch.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = ch.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ch.fecha_cheque BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ch.estatus = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ch.estado_cheque IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ch.id_departamento IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ch.numero_cheque LIKE %s
		OR banco.nombreBanco LIKE %s
		OR cliente.nombre LIKE %s
		OR cliente.apellido LIKE %s
		OR ch.observacion_cheque LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT
		ch.id_cheque,
		ch.tipo_cheque,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		ch.monto_neto_cheque,
		IF (ch.estatus = 1, ch.saldo_cheque, 0) AS saldo_cheque,
		ch.fecha_cheque,
		ch.numero_cheque,
		banco.nombreBanco,
		ch.id_departamento,
		ch.estatus,
		IF (ch.estatus = 1, ch.estado_cheque, NULL) AS estado_cheque,
		(CASE ch.estatus
			WHEN 1 THEN
				(CASE ch.estado_cheque
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado (No Asignado)'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
				END)
			ELSE
				'Anulado'
		END) AS descripcion_estado_cheque,
		ch.observacion_cheque,
		
		ch.id_empleado_registro AS id_empleado_creador,
		vw_pg_empleado_creador.nombre_empleado AS nombre_empleado_creador,
		ch.fecha_anulado,
		ch.id_empleado_anulado,
		vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
		ch.motivo_anulacion,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, 
			CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), 
			vw_iv_emp_suc.nombre_empresa) AS nombre_empresa		
		
	FROM cj_cc_cheque ch
		INNER JOIN cj_cc_cliente cliente ON (ch.id_cliente = cliente.id)
		INNER JOIN bancos banco ON (ch.id_banco_cliente = banco.idBanco)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_creador ON (ch.id_empleado_registro = vw_pg_empleado_creador.id_empleado)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (ch.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ch.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaCheque", "", $pageNum, "id_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "", $pageNum, "estatus", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "6%", $pageNum, "fecha_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Cheque");		
		$htmlTh .= ordenarCampo("xajax_listaCheque", "6%", $pageNum, "tipo_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Cheque");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "16%", $pageNum, "numero_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Cheque");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "30%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "8%", $pageNum, "descripcion_estado_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "8%", $pageNum, "saldo_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listaCheque", "8%", $pageNum, "monto_neto_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_departamento']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
			default : $imgDctoModulo = $row['id_departamento'];
		}
		
		if ($row['estatus'] == 0){ // 0 = ANULADO ; 1 = ACTIVO
			$imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Anulado\"/>";
		} else if ($row['estatus'] == 1){ // 0 = ANULADO ; 1 = ACTIVO
			$imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>";
		}
		
		
		if($row["tipo_cheque"] == 1){
			$tipoCheque = "Cliente";
		}elseif($row["tipo_cheque"] == 2){
			$tipoCheque = "Bono Suplidor";
		}elseif($row["tipo_cheque"] == 3){
			$tipoCheque = "PND";
		}
			
		switch($row['estado_cheque']) {
			case "" : $class = "class=\"divMsjInfo5\""; break;
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;						
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			default : $class = ""; break;
		}	
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"center\" ".((strlen($row['nombre_empleado_creador']) > 0) ? "title=\"Nro. Cheque: ".$row['numero_cheque'].". Registrado por: ".utf8_encode($row['nombre_empleado_creador'])."\"" : "")." width=\"100%\">".date(spanDateFormat, strtotime($row['fecha_cheque']))."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['fecha_anulado']) > 0) ? "<tr><td align=\"center\" class=\"textoNegritaCursiva_9px textoRojoNegrita\" ".((strlen($row['nombre_empleado_anulado']) > 0) ? "title=\"Nro. Cheque: ".$row['numero_cheque'].". Anulado por: ".utf8_encode($row['nombre_empleado_anulado'])."\"" : "").">".date(spanDateFormat, strtotime($row['fecha_anulado']))."</td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".$tipoCheque."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".utf8_encode($row['nombreBanco'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_cheque'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</div>";
				$htmlTb .= (strlen($row['observacion_cheque']) > 0) ? "<div class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_cheque'])."</div>" : "";
				$htmlTb .= (strlen($row['motivo_anulacion']) > 0) ? "<div class=\"textoNegritaCursiva_9px textoRojoNegrita\" title=\"Motivo de la Anulación\">".utf8_encode($row['motivo_anulacion'])."</div>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".utf8_encode($row['descripcion_estado_cheque'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldo_cheque'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_neto_cheque'],2,".",",")."</td>";
			$htmlTb .= "<td>";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("CH",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "CH";
				$objDcto->tipoDocumentoMovimiento = (in_array("CH",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_departamento'];
				$objDcto->idDocumento = $row['id_cheque'];
				$objDcto->mostrarDocumento = "verDetalle";
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= $aVerDcto;
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("CH",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "CH";
				$objDcto->tipoDocumentoMovimiento = (in_array("CH",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_departamento'];
				$objDcto->idDocumento = $row['id_cheque'];
				$objDcto->mostrarDocumento = "verPDF";
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= $aVerDcto;
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[10] += $row['saldo_cheque'];
		$arrayTotal[11] += $row['monto_neto_cheque'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total Página:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[10], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[10] += $row['saldo_cheque'];
				$arrayTotalFinal[11] += $row['monto_neto_cheque'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[10], 2, ".", ",")."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11], 2, ".", ",")."</td>";
				$htmlTb .= "<td colspan=\"3\"></td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCheque(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCheque(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListadoCheques","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalCheques += $row['monto_neto_cheque'];
		$totalSaldo += $row['saldo_cheque'];
	}
	
	$objResponse->assign("spnTotalCheques","innerHTML",number_format($totalCheques, 2, ".", ","));
	$objResponse->assign("spnSaldoCheques","innerHTML",number_format($totalSaldo, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarCheque");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstOrientacionPDF");
$xajax->register(XAJAX_FUNCTION,"exportarCheque");
$xajax->register(XAJAX_FUNCTION,"imprimirCheque");
$xajax->register(XAJAX_FUNCTION,"listaCheque");
?>