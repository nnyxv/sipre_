<?php

function listarCheques($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$queryChequeras = sprintf("SELECT 
		te_estado_cuenta.id_cuenta,
		te_estado_cuenta.tipo_documento,
		te_estado_cuenta.id_documento,
		te_estado_cuenta.id_estado_cuenta,
		te_estado_cuenta.estados_principales,
		te_estado_cuenta.monto,
		te_estado_cuenta.numero_documento,
		te_cheques.beneficiario_proveedor,
		te_cheques.id_beneficiario_proveedor,	
		te_cheques.id_cheque,
		te_cheques.entregado
	FROM te_estado_cuenta
		INNER JOIN te_cheques ON (te_estado_cuenta.id_documento = te_cheques.id_cheque)
	WHERE te_estado_cuenta.id_cuenta = '%s' 
	AND te_estado_cuenta.tipo_documento = 'CH' 
	AND te_estado_cuenta.estados_principales IN (1,2)", $valCadBusq[0]);	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitChequera = sprintf("%s %s LIMIT %d OFFSET %d", $queryChequeras, $sqlOrd, $maxRows, $startRow);        
	$rsLimitChequera = mysql_query($queryLimitChequera) or die(mysql_error());			
	if ($totalRows == NULL) {
		$rsChequera = mysql_query($queryChequeras) or die(mysql_error());
		$totalRows = mysql_num_rows($rsChequera);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";	
        $htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarCheques", "", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero de Cheque");
		$htmlTh .= ordenarCampo("xajax_listarCheques", "", $pageNum, "id_beneficiario_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Beneficiario");
		$htmlTh .= ordenarCampo("xajax_listarCheques", "", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= ordenarCampo("xajax_listarCheques", "", $pageNum, "entregado", $campOrd, $tpOrd, $valBusq, $maxRows, "Entregado");		
	$htmlTh .= "</tr>";
        
	$count = 0;

	$contFila = 0;
	while ($rowChequera = mysql_fetch_assoc($rsLimitChequera)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$count++;
		
		if($rowChequera['entregado'] == 1){
			$entregado="Si";
		}else{
			$entregado="No";
		}
		
		if($rowChequera['beneficiario_proveedor'] == 1){		
			$queryProveedor = sprintf("SELECT nombre FROM cp_proveedor WHERE id_proveedor = '%s'",$rowChequera['id_beneficiario_proveedor']);
			$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
			$rowProveedor = mysql_fetch_array($rsProveedor);
			
			$nombreBeneficiarioProveedor = $rowProveedor['nombre'];
		
		}else if($rowChequera['beneficiario_proveedor'] == 0){
			$queryBeneficiario = sprintf("SELECT nombre_beneficiario FROM te_beneficiarios WHERE id_beneficiario = '%s'",$rowChequera['id_beneficiario_proveedor']);
			$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
			$rowBeneficiario = mysql_fetch_array($rsBeneficiario);	
			
			$nombreBeneficiarioProveedor = $rowBeneficiario['nombre_beneficiario'];
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
		$htmlTb .= "<td>".$rowChequera['numero_documento']."</td>";
		$htmlTb .= "<td>".utf8_encode($nombreBeneficiarioProveedor)."</td>";
		$htmlTb .= "<td>".$rowChequera['monto']."</td>";
		$htmlTb .= "<td>".$entregado."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarCheques(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCheques(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarCheques(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdCheques","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}


function listarTransferencia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$queryChequeras = sprintf("SELECT 
		te_estado_cuenta.id_cuenta,
		te_estado_cuenta.tipo_documento,
		te_estado_cuenta.id_documento,
		te_estado_cuenta.id_estado_cuenta,
		te_estado_cuenta.estados_principales,
		te_estado_cuenta.monto,
		te_estado_cuenta.numero_documento,
		te_transferencia.beneficiario_proveedor,
		te_transferencia.id_beneficiario_proveedor,	
		te_transferencia.id_transferencia
	FROM te_estado_cuenta
		INNER JOIN te_transferencia ON (te_estado_cuenta.id_documento = te_transferencia.id_transferencia)
	WHERE te_estado_cuenta.id_cuenta = '%s' 
	AND te_estado_cuenta.tipo_documento = 'TR' 
	AND te_estado_cuenta.estados_principales IN (1,2)",$valCadBusq[0]);	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitChequera = sprintf("%s %s LIMIT %d OFFSET %d", $queryChequeras, $sqlOrd, $maxRows, $startRow);        
	$rsLimitChequera = mysql_query($queryLimitChequera) or die(mysql_error());			
	if ($totalRows == NULL) {
		$rsChequera = mysql_query($queryChequeras) or die(mysql_error());
		$totalRows = mysql_num_rows($rsChequera);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";	
        $htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarTransferencia", "", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Transferencia");
		$htmlTh .= ordenarCampo("xajax_listarTransferencia", "", $pageNum, "id_beneficiario_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Beneficiario");
		$htmlTh .= ordenarCampo("xajax_listarTransferencia", "", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");		
	$htmlTh .= "</tr>";
        
	$count = 0;
	while ($rowChequera = mysql_fetch_assoc($rsLimitChequera)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$count++;
	
		if($rowChequera['beneficiario_proveedor'] == 1 ){		
			$queryProveedor = sprintf("SELECT nombre FROM cp_proveedor WHERE id_proveedor = '%s'",$rowChequera['id_beneficiario_proveedor']);
			$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
			$rowProveedor = mysql_fetch_array($rsProveedor);
			
			$nombreBeneficiarioProveedor = $rowProveedor['nombre'];
		}else if($rowChequera['beneficiario_proveedor'] == 0 ){		
			$queryBeneficiario = sprintf("SELECT nombre_beneficiario FROM te_beneficiarios WHERE id_beneficiario = '%s'",$rowChequera['id_beneficiario_proveedor']);
			$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
			$rowBeneficiario = mysql_fetch_array($rsBeneficiario);	
			
			$nombreBeneficiarioProveedor = $rowBeneficiario['nombre_beneficiario'];
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
		$htmlTb .= "<td>".$rowChequera['numero_documento']."</td>";
		$htmlTb .= "<td>".utf8_encode($nombreBeneficiarioProveedor)."</td>";
		$htmlTb .= "<td>".$rowChequera['monto']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarTransferencia(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarTransferencia(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdTransferencia","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listarNotaDebito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;	
	
	$queryChequeras = sprintf("SELECT 
		te_estado_cuenta.id_cuenta,
		te_estado_cuenta.tipo_documento,
		te_estado_cuenta.id_documento,
		te_estado_cuenta.id_estado_cuenta,
		te_estado_cuenta.estados_principales,
		te_estado_cuenta.monto,
		te_estado_cuenta.numero_documento,
		te_nota_debito.control_beneficiario_proveedor,
		te_nota_debito.id_beneficiario_proveedor,	
		te_nota_debito.id_nota_debito
	FROM te_estado_cuenta
		INNER JOIN te_nota_debito ON (te_estado_cuenta.id_documento = te_nota_debito.id_nota_debito)
	WHERE te_estado_cuenta.id_cuenta = '%s' 
	AND te_estado_cuenta.tipo_documento = 'ND' 
	AND te_estado_cuenta.estados_principales IN (1,2)",$valCadBusq[0]);
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitChequera = sprintf("%s %s LIMIT %d OFFSET %d", $queryChequeras, $sqlOrd, $maxRows, $startRow);        
	$rsLimitChequera = mysql_query($queryLimitChequera) or die(mysql_error());			
	if ($totalRows == NULL) {
		$rsChequera = mysql_query($queryChequeras) or die(mysql_error());
		$totalRows = mysql_num_rows($rsChequera);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
        $htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarNotaDebito", "", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero de Nota de De&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listarNotaDebito", "", $pageNum, "id_beneficiario_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Beneficiario");
		$htmlTh .= ordenarCampo("xajax_listarNotaDebito", "", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");		
	$htmlTh .= "</tr>";
        
	$count = 0;
	while ($rowChequera = mysql_fetch_assoc($rsLimitChequera)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$count++;
		
		if($rowChequera['beneficiario_proveedor'] == 1 ){		
			$queryProveedor = sprintf("SELECT nombre FROM cp_proveedor WHERE id_proveedor = '%s'",$rowChequera['id_beneficiario_proveedor']);
			$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
			$rowProveedor = mysql_fetch_array($rsProveedor);
			
			$nombreBeneficiarioProveedor = $rowProveedor['nombre'];
		}else if($rowChequera['beneficiario_proveedor'] == 0 ){		
			$queryBeneficiario = sprintf("SELECT nombre_beneficiario FROM te_beneficiarios WHERE id_beneficiario = '%s'",$rowChequera['id_beneficiario_proveedor']);
			$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
			$rowBeneficiario = mysql_fetch_array($rsBeneficiario);	
			
			$nombreBeneficiarioProveedor = $rowBeneficiario['nombre_beneficiario'];
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
		$htmlTb .= "<td>".$rowChequera['numero_documento']."</td>";
		$htmlTb .= "<td>".utf8_encode($nombreBeneficiarioProveedor)."</td>";
		$htmlTb .= "<td>".$rowChequera['monto']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarNotaDebito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaDebito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdDebito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listarNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;	
	
	$queryChequeras = sprintf("SELECT 
		te_estado_cuenta.id_cuenta,
		te_estado_cuenta.tipo_documento,
		te_estado_cuenta.id_documento,
		te_estado_cuenta.id_estado_cuenta,
		te_estado_cuenta.estados_principales,
		te_estado_cuenta.monto,
		te_estado_cuenta.numero_documento,
		te_nota_credito.control_beneficiario_proveedor,
		te_nota_credito.id_beneficiario_proveedor,	
		te_nota_credito.id_nota_credito
	FROM te_estado_cuenta
		INNER JOIN te_nota_credito ON (te_estado_cuenta.id_documento = te_nota_credito.id_nota_credito)
	WHERE te_estado_cuenta.id_cuenta = '%s' 
	AND te_estado_cuenta.tipo_documento = 'NC' 
	AND te_estado_cuenta.estados_principales IN(1,2)",$valCadBusq[0]);	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitChequera = sprintf("%s %s LIMIT %d OFFSET %d", $queryChequeras, $sqlOrd, $maxRows, $startRow);        
	$rsLimitChequera = mysql_query($queryLimitChequera) or die(mysql_error());			
	if ($totalRows == NULL) {
		$rsChequera = mysql_query($queryChequeras) or die(mysql_error());
		$totalRows = mysql_num_rows($rsChequera);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";	
        $htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarNotaCredito", "", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero de Nota de Cr&eacute;dito");
		$htmlTh .= ordenarCampo("xajax_listarNotaCredito", "", $pageNum, "id_beneficiario_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Beneficiario");
		$htmlTh .= ordenarCampo("xajax_listarNotaCredito", "", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");		
	$htmlTh .= "</tr>";
        
	$count = 0;	
	while ($rowChequera = mysql_fetch_assoc($rsLimitChequera)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$count++;
	
		if($rowChequera['beneficiario_proveedor'] == 1 ){		
			$queryProveedor = sprintf("SELECT nombre FROM cp_proveedor WHERE id_proveedor = '%s'",$rowChequera['id_beneficiario_proveedor']);
			$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
			$rowProveedor = mysql_fetch_array($rsProveedor);
			
			$nombreBeneficiarioProveedor = $rowProveedor['nombre'];
		}else if($rowChequera['beneficiario_proveedor'] == 0 ){		
			$queryBeneficiario = sprintf("SELECT nombre_beneficiario FROM te_beneficiarios WHERE id_beneficiario = '%s'",$rowChequera['id_beneficiario_proveedor']);
			$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
			$rowBeneficiario = mysql_fetch_array($rsBeneficiario);	
			
			$nombreBeneficiarioProveedor = $rowBeneficiario['nombre_beneficiario'];
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
		$htmlTb .= "<td>".$rowChequera['numero_documento']."</td>";
		$htmlTb .= "<td>".utf8_encode($nombreBeneficiarioProveedor)."</td>";
		$htmlTb .= "<td>".$rowChequera['monto']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarNotaCredito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("tdCredito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listarDeposito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;	
	
	$queryChequeras = sprintf("SELECT * FROM te_estado_cuenta WHERE id_cuenta = '%s' AND tipo_documento = 'DP' AND estados_principales IN (1,2)",
		$valCadBusq[0]);
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimitChequera = sprintf("%s %s LIMIT %d OFFSET %d", $queryChequeras, $sqlOrd, $maxRows, $startRow);
	$rsLimitChequera = mysql_query($queryLimitChequera) or die(mysql_error());			
	if ($totalRows == NULL) {
		$rsChequera = mysql_query($queryChequeras) or die(mysql_error());
		$totalRows = mysql_num_rows($rsChequera);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
			
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";	
        $htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listarDeposito", "", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero de D&eacute;posito");
		$htmlTh .= ordenarCampo("xajax_listarDeposito", "", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Beneficiario");
		$htmlTh .= ordenarCampo("xajax_listarDeposito", "", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");		
	$htmlTh .= "</tr>";
        
	$count = 0;
	while ($rowChequera = mysql_fetch_assoc($rsLimitChequera)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$count++;
		
		$queryEmpresa = sprintf("SELECT nombre_empresa FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$rowChequera['id_empresa']);
		$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error());
		$rowEmpresa = mysql_fetch_array($rsEmpresa);	
		
		$htmlTb .= "<tr class=\"".$clase."\">";
		$htmlTb .= "<td>".$rowChequera['numero_documento']."</td>";
		$htmlTb .= "<td>".utf8_encode($rowEmpresa['nombre_empresa']."-".$rowEmpresa['nombre_empresa_suc'])."</td>";
		$htmlTb .= "<td>".$rowChequera['monto']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarDeposito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarDeposito(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("tdDeposito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}
function cargarDatosCuenta($id){
	$objResponse = new xajaxResponse();
	
	$queryCuenta = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$id);
	$rsCuenta = mysql_query($queryCuenta) or die(mysql_error());
	$rowCuenta = mysql_fetch_array($rsCuenta);
	
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_cuenta = '%s' AND estados_principales = '1'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	
	$countTotalCheques = 0;
	$countTotalDebito = 0;
	$countTotalCredito = 0;
	$countTotalDeposito = 0;
	$countTotalTransferencia = 0;
	$countCH = 0;
	$countND = 0;
	$countDP = 0;
	$countNC = 0;
	$countTR = 0;
	
	while($row = mysql_fetch_array($rs)){		
		if($row['tipo_documento'] =='CH'){
			$countTotalCheques = $countTotalCheques + $row['monto'];
			$countCH++;			
		}
		if($row['tipo_documento'] =='DP'){
			$countTotalDeposito = $countTotalDeposito + $row['monto'];
			$countDP++;
		}
		if($row['tipo_documento'] =='ND'){
			$countTotalDebito = $countTotalDebito + $row['monto'];
			$countND++;
		}
		if($row['tipo_documento'] =='NC'){
			$countTotalCredito = $countTotalCredito + $row['monto'];
			$countNC++;
		}
		if($row['tipo_documento'] =='TR'){
			$countTotalTransferencia = $countTotalTransferencia + $row['monto'];
			$countTR++;
		}
	}
	
	$montoTotal = ($countTotalDeposito+$countTotalCredito)-($countTotalCheques + $countTotalDebito + $countTotalTransferencia);
	
	$htmlTotalCheques = "<table align =\"right\"><tr><td>".$countTotalCheques."</td></tr></table>";
	$htmlCountCH = "<table><tr><td>".$countCH."</td></tr></table>";
	$htmlTotalDeposito = "<table align =\"right\"><tr><td>".$countTotalDeposito."</td></tr></table>";
	$htmlCountDP = "<table><tr><td>".$countDP."</td></tr></table>";
	$htmlTotalDebito = "<table align =\"right\"><tr><td>".$countTotalDebito."</td></tr></table>";
	$htmlCountND = "<table><tr><td>".$countND."</td></tr></table>";
	$htmlTotalCredito = "<table align =\"right\"><tr><td>".$countTotalCredito."</td></tr></table>";
	$htmlCountNC = "<table><tr><td>".$countNC."</td></tr></table>";
	$htmlTotalTransferencia = "<table align =\"right\"><tr><td>".$countTotalTransferencia."</td></tr></table>";
	$htmlCountTR = "<table><tr><td>".$countTR."</td></tr></table>";
	$htmlSaldoCuenta = "<table><tr><td>".$rowCuenta['saldo_tem']."</td></tr></table>";
	$htmlConciliado = "<table><tr><td>".$rowCuenta['saldo']."</td></tr></table>";
	$htmlMontoTotal = "<table><tr><td>".$montoTotal."</td></tr></table>";
	$htmlCuenta = "<table align = \"right\"><tr><td>Resumen de la Cuenta: ". $rowCuenta['numeroCuentaCompania']."</td></tr></table>";
	
	$objResponse->assign("tdSaldoLibro","innerHTML",$htmlSaldoCuenta);
	$objResponse->assign("tdSaldoConciliado","innerHTML",$htmlConciliado);
	$objResponse->assign("tdMovNoApli","innerHTML",$htmlMontoTotal);
	$objResponse->assign("tdTotalCheques","innerHTML",$htmlTotalCheques);
	$objResponse->assign("tdTCheques","innerHTML",$htmlCountCH);
	$objResponse->assign("tdTotalDeposito","innerHTML",$htmlTotalDeposito);
	$objResponse->assign("tdTDeposito","innerHTML",$htmlCountDP);
	$objResponse->assign("tdTotalDebitos","innerHTML",$htmlTotalDebito);
	$objResponse->assign("tdTDebitos","innerHTML",$htmlCountND);
	$objResponse->assign("tdTotalCredito","innerHTML",$htmlTotalCredito);
	$objResponse->assign("tdTCredito","innerHTML",$htmlCountNC);
	$objResponse->assign("tdTotalTransferencia","innerHTML",$htmlTotalTransferencia);
	$objResponse->assign("tdTTransferencia","innerHTML",$htmlCountTR);
	$objResponse->assign("tdResumenCuenta","innerHTML",$htmlCuenta);
	
	/////////////////////////////
		
	$query2 = sprintf("SELECT * FROM te_estado_cuenta WHERE id_cuenta = '%s' AND estados_principales = '2'",$id);
	$rs2 = mysql_query($query2) or die(mysql_error());
	
	$countTotalChequesApl = 0;
	$countTotalDebitoApl = 0;
	$countTotalCreditoApl = 0;
	$countTotalDepositoApl = 0;
	$countTotalTransferenciaApl = 0;
	$countCHApl = 0;
	$countNDApl = 0;
	$countDPApl = 0;
	$countNCApl = 0;
	$countTRApl = 0;
	
	while($row2 = mysql_fetch_array($rs2)){		
		if($row2['tipo_documento'] =='CH'){
			$countTotalChequesApl = $countTotalChequesApl + $row2['monto'];
			$countCHApl++;			
		}
		if($row2['tipo_documento'] =='DP'){
			$countTotalDepositoApl = $countTotalDepositoApl + $row2['monto'];
			$countDPApl++;
		}
		if($row2['tipo_documento'] =='ND'){
			$countTotalDebitoApl = $countTotalDebitoApl + $row2['monto'];
			$countNDApl++;
		}
		if($row2['tipo_documento'] =='NC'){
			$countTotalCreditoApl = $countTotalCreditoApl + $row2['monto'];
			$countNCApl++;
		}
		if($row2['tipo_documento'] =='TR'){
			$countTotalTransferenciaApl = $countTotalTransferenciaApl + $row2['monto'];
			$countTRApl++;
		}
	}	

	$montoTotalApl = ($countTotalDepositoApl+$countTotalCreditoApl)-($countTotalChequesApl + $countTotalDebitoApl + $countTotalTransferenciaApl);
	
	$htmlTotalChequesApl = "<table align =\"right\"><tr><td>".$countTotalChequesApl."</td></tr></table>";
	$htmlCountCHApl = "<table><tr><td>".$countCHApl."</td></tr></table>";
	$htmlTotalDepositoApl = "<table align =\"right\"><tr><td>".$countTotalDepositoApl."</td></tr></table>";
	$htmlCountDPApl = "<table><tr><td>".$countDPApl."</td></tr></table>";
	$htmlTotalDebitoApl = "<table align =\"right\"><tr><td>".$countTotalDebitoApl."</td></tr></table>";
	$htmlCountNDApl = "<table><tr><td>".$countNDApl."</td></tr></table>";
	$htmlTotalCreditoApl = "<table align =\"right\"><tr><td>".$countTotalCreditoApl."</td></tr></table>";
	$htmlCountNCApl = "<table><tr><td>".$countNCApl."</td></tr></table>";
	$htmlTotalTransferenciaApl = "<table align =\"right\"><tr><td>".$countTotalTransferenciaApl."</td></tr></table>";
	$htmlCountTRApl = "<table><tr><td>".$countTRApl."</td></tr></table>";
	$htmlMontoTotalApl = "<table><tr><td>".$montoTotalApl."</td></tr></table>";
	
	$objResponse->assign("tdMovApli","innerHTML",$htmlMontoTotalApl);
	$objResponse->assign("tdTotalChequesApl","innerHTML",$htmlTotalChequesApl);
	$objResponse->assign("tdTChequesApl","innerHTML",$htmlCountCHApl);
	$objResponse->assign("tdTotalDepositoApl","innerHTML",$htmlTotalDepositoApl);
	$objResponse->assign("tdTDepositoApl","innerHTML",$htmlCountDPApl);
	$objResponse->assign("tdTotalDebitosApl","innerHTML",$htmlTotalDebitoApl);
	$objResponse->assign("tdTDebitosApl","innerHTML",$htmlCountNDApl);
	$objResponse->assign("tdTotalCreditoApl","innerHTML",$htmlTotalCreditoApl);
	$objResponse->assign("tdTCreditoApl","innerHTML",$htmlCountNCApl);
	$objResponse->assign("tdTotalTransferenciaApl","innerHTML",$htmlTotalTransferenciaApl);
	$objResponse->assign("tdTTransferenciaApl","innerHTML",$htmlCountTRApl);
	$objResponse->assign("tdResumenCuentaApl","innerHTML",$htmlCuentaApl);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"listarCheques");
$xajax->register(XAJAX_FUNCTION,"listarNotaDebito");
$xajax->register(XAJAX_FUNCTION,"listarNotaCredito");
$xajax->register(XAJAX_FUNCTION,"listarDeposito");
$xajax->register(XAJAX_FUNCTION,"listarTransferencia");
$xajax->register(XAJAX_FUNCTION,"cargarDatosCuenta");

?>