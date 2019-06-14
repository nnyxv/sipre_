<?php

function anularChequera($idChequera){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_chequeras","editar")){ return $objResponse; }
	
	$queryEditar = sprintf("UPDATE te_chequeras SET activa = 'NO', disponibles = 0 WHERE id_chq = %s",
		valTpDato($idChequera, "int"));
	$rsEditar = mysql_query($queryEditar);
	if(!$rsEditar) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$objResponse->script("byId('btnBuscar').click();");	
	$objResponse->alert("Chequera Editada Exitosamente");
	
	return $objResponse;
}

function buscarChequera($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$valForm['lstEstado'],
		$valForm['selBancos'],
		$valForm['selCuentasBusq'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaChequeras(0, "id_chq", "ASC", $valBusq));
	
	return $objResponse;
}

function comboBancos($idBanco, $idTd, $idSel, $onchange){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT banco.* 
	FROM bancos banco
		INNER JOIN cuentas cuenta ON (cuenta.idBanco = banco.idBanco) 
	WHERE banco.idBanco != 1 
	GROUP BY banco.idBanco");
	$rs = mysql_query($query);
	if (!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
	$html = "<select id=\"".$idSel."\" name=\"".$idSel."\" onchange=\"".$onchange."\" class=\"inputHabilitado\" style=\"width:200px\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($row['idBanco'] == $idBanco) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$row['idBanco']."\" ".$selected.">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign($idTd,"innerHTML",$html);
	
	return $objResponse;
}

function comboCuentas($idBanco, $idCuentas){
	$objResponse = new xajaxResponse();
		
	$queryCuentas = sprintf("SELECT * FROM cuentas WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rsCuentas = mysql_query($queryCuentas);
	if (!$rsCuentas){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$html = "<select id=\"selCuentaNuevaChequera\" name=\"selCuentaNuevaChequera\" class=\"inputHabilitado\" style=\"width:200px\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowCuentas = mysql_fetch_assoc($rsCuentas)) {
		$selected = ($idCuentas == $rowCuentas['idCuentas']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$rowCuentas['idCuentas']."\" ".$selected.">".$rowCuentas['numeroCuentaCompania']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdSelCuentas","innerHTML",$html);
	
	return $objResponse;
}

function comboCuentasBusq($idBanco){
	$objResponse = new xajaxResponse();
		
	$queryCuentas = sprintf("SELECT * FROM cuentas WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rsCuentas = mysql_query($queryCuentas);
	if (!$rsCuentas){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$html = "<select id=\"selCuentasBusq\" name=\"selCuentasBusq\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowCuentas = mysql_fetch_assoc($rsCuentas)) {		
		$html .= "<option value=\"".$rowCuentas['idCuentas']."\" >".$rowCuentas['numeroCuentaCompania']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdSelCuentasBusq","innerHTML",$html);
	
	return $objResponse;
}


function guardarChequera($formChequera){
	$objResponse = new xajaxResponse();	

	if (!xvalidaAcceso($objResponse,"te_chequeras","insertar")){ return $objResponse; }

	$cantidad = $formChequera['txtNumeroFinal'] - $formChequera['txtNumeroInicial'] + 1; 
	$ultimoNum = $formChequera['txtNumeroInicial'] - 1;
		   
	$queryInsert = sprintf("INSERT INTO te_chequeras (id_cuenta, nro_inicial, nro_final, cantidad, ultimo_nro_chq, anulados, impresos, disponibles, estatus, activa) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
	valTpDato($formChequera['selCuentaNuevaChequera'], "int"),
	valTpDato($formChequera['txtNumeroInicial'], "int"),
	valTpDato($formChequera['txtNumeroFinal'], "int"),
	valTpDato($cantidad, "int"),
	valTpDato($ultimoNum, "int"),
	valTpDato(0, "int"),
	valTpDato(0, "int"),
	valTpDato($cantidad, "int"),
	valTpDato(0, "int"),
	valTpDato($formChequera['selChequeraActiva'], "text"));	
	$rs = mysql_query($queryInsert);
	if (!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$objResponse->script("byId('btnBuscar').click();
	byId('btnCancelar').click();");
	$objResponse->alert("Chequera insertada exitosamente");
	
	return $objResponse;
}

function listaChequeras($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activa = %s",
			valTpDato($valCadBusq[0], "text"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idBanco = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_cuenta = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numeroCuentaCompania LIKE %s
		OR nombreBanco LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}

	$query = sprintf("SELECT * FROM vw_te_chequeras %s", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaChequeras", "35%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listaChequeras", "20%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Número Cuenta");
		$htmlTh .= ordenarCampo("xajax_listaChequeras", "7%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Cantidad");
		$htmlTh .= ordenarCampo("xajax_listaChequeras", "7%", $pageNum, "impresos", $campOrd, $tpOrd, $valBusq, $maxRows, "Impresos");
		$htmlTh .= ordenarCampo("xajax_listaChequeras", "7%", $pageNum, "anulados", $campOrd, $tpOrd, $valBusq, $maxRows, "Anulados");
		$htmlTh .= ordenarCampo("xajax_listaChequeras", "7%", $pageNum, "disponibles", $campOrd, $tpOrd, $valBusq, $maxRows, "Disponibles");
		$htmlTh .= "<td colspan=\"2\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowChequera = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgActivo = ($rowChequera["activa"] == "SI") ? "<img src=\"../img/iconos/ico_verde.gif\">" : "<img src=\"../img/iconos/ico_rojo.gif\">";
				
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>".$imgActivo."</td>";
			$htmlTb .= "<td align='left'>".utf8_encode($rowChequera['nombreBanco'])."</td>";
			$htmlTb .= "<td>".$rowChequera['numeroCuentaCompania']."</td>";
			$htmlTb .= "<td>".$rowChequera['cantidad']."</td>";
			$htmlTb .= "<td>".$rowChequera['impresos']."</td>";
			$htmlTb .= "<td>".$rowChequera['anulados']."</td>";
			$htmlTb .= "<td>".$rowChequera['disponibles']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante1(this, 'tblChequera', %s);\"><img src='../img/iconos/ico_view.png' class=\"puntero\" title=\"Ver Chequera\"/></a>",
					$rowChequera['id_chq']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if($rowChequera['activa'] == "SI"){
				$htmlTb .= sprintf("<img class=\"puntero\" src=\"../img/iconos/ico_quitar.gif\" title=\"Anular Chequera\" onclick=\"if(confirm('¿Seguro desea anular la chequera?')){ xajax_anularChequera(%s) }\">",
					$rowChequera['id_chq']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaChequeras(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaChequeras(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaChequeras(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaChequeras(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaChequeras(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaChequeras","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function formChequera($idChequera){
	$objResponse = new xajaxResponse();
	
	$queryChequera = sprintf("SELECT * FROM vw_te_chequeras WHERE id_chq = %s",
		valTpDato($idChequera, "int"));
	$rsChequera = mysql_query($queryChequera);
	if (!$rsChequera){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowChequera = mysql_fetch_assoc($rsChequera);

	$objResponse->script("xajax_comboBancos(".$rowChequera['idBanco'].",'tdSelBancoNuevaChequera','selBancoCuentaNueva','xajax_comboCuentas(this.value)');");
	$objResponse->script("xajax_comboCuentas(".$rowChequera['idBanco'].",".$rowChequera['id_cuenta'].");");
	$objResponse->assign("selChequeraActiva","value",$rowChequera['activa']);
	$objResponse->assign("hddIdChequera","value",$rowChequera['id_chq']);
	$objResponse->assign("txtUltimoNumeroCheque","value",$rowChequera['ultimo_nro_chq']);	
	$objResponse->assign("txtDisponibles","value",$rowChequera['disponibles']);
	$objResponse->assign("txtNumeroInicial","value",$rowChequera['nro_inicial']);
	$objResponse->assign("txtNumeroFinal","value",$rowChequera['nro_final']);
	$objResponse->assign("txtImpresos","value",$rowChequera['impresos']);
	$objResponse->assign("txtAnulados","value",$rowChequera['anulados']);	
	$objResponse->assign("txtCantidadCheque","value",$rowChequera['cantidad']);
	
	$objResponse->script("byId('btnGuardar').style.display = 'none';
	byId('td1').style.display = '';
	byId('td2').style.display = '';
	byId('tr4').style.display = '';
	byId('tr5').style.display = '';");
	
	$objResponse->script("
	byId('txtNumeroInicial').className = 'inputInicial';
	byId('txtNumeroFinal').className = 'inputInicial';	
	byId('txtNumeroInicial').readOnly = true;
	byId('txtNumeroFinal').readOnly = true;
	");
						  
	if ($idChequera == ""){// NUEVO
		$objResponse->script("byId('btnGuardar').style.display = '';
		xajax_comboBancos(0,'tdSelBancoNuevaChequera','selBancoNuevaChequera','xajax_comboCuentas((this.value),0)');
		xajax_comboCuentas(0,0);							
		byId('td1').style.display = 'none';
		byId('td2').style.display = 'none';
		byId('tr4').style.display = 'none';
		byId('tr5').style.display = 'none';");		

		$objResponse->script("
		byId('txtNumeroInicial').className = 'inputHabilitado';
		byId('txtNumeroFinal').className = 'inputHabilitado';		
		byId('txtNumeroInicial').readOnly = false;
		byId('txtNumeroFinal').readOnly = false;
		");
		
		$objResponse->assign("selChequeraActiva","value",'SI');
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularChequera");
$xajax->register(XAJAX_FUNCTION,"buscarChequera");
$xajax->register(XAJAX_FUNCTION,"comboBancos");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"comboCuentasBusq");
$xajax->register(XAJAX_FUNCTION,"guardarChequera");
$xajax->register(XAJAX_FUNCTION,"listaChequeras");
$xajax->register(XAJAX_FUNCTION,"formChequera");

?>