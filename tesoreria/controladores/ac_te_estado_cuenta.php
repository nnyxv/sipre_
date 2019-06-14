<?php

function asignarBanco($idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	
	$objResponse->script("xajax_cargaLstCuenta(byId('lstEmpresa').value, ".$row['idBanco'].")");
	$objResponse->script("byId('btnCancelarBanco').click();");	
	
	return $objResponse;
}

function buscarBanco($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarBanco']);
	
	$objResponse->loadCommands(listaBanco(0, "idBanco", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarEstadoCuenta($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['lstCuenta'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['lstEstado'],
		$valForm['lstTipoDcto']);
		
	$objResponse->loadCommands(listaEstadoCuenta(0, "id_estado_cuenta", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstCuenta($idEmpresa, $idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM cuentas WHERE id_empresa = %s AND idBanco = %s",
		valTpDato($idEmpresa, "int"),
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstCuenta\" name=\"lstCuenta\" onchange=\"byId('btnBuscar').click();\" class=\"inputHabilitado\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)){
		$html .= "<option value=\"".$row['idCuentas']."\">".$row['numeroCuentaCompania']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdLstCuenta","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstado(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_estados_principales ORDER BY id_estados_principales");
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstEstado\" name=\"lstEstado\" onchange=\"byId('btnBuscar').click();\" class=\"inputHabilitado\">";
	$html .="<option selected=\"selected\" value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_estados_principales']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdLstEstado","innerHTML",$html);
	
	return $objResponse;
}

function exportarEstadoCuentaExcel($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['lstCuenta'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['lstEstado'],
		$valForm['lstTipoDcto']);
	
	$objResponse->script("window.open('reportes/te_estado_cuenta_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listaEstadoCuenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.estados_principales != 0");
        
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.id_cuenta = %s",
		valTpDato($valCadBusq[1], "int"));
		
	if ($valCadBusq[2] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusqFecha .= $cond.sprintf("DATE(te_estado_cuenta.fecha_registro) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_estado_cuenta.estados_principales = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_estado_cuenta.tipo_documento = %s",
			valTpDato($valCadBusq[5], "text"));
	}
        
	$querySaldo = sprintf("SELECT saldo FROM cuentas WHERE idCuentas = %s",
		valTpDato($valCadBusq[1], "int"));
	$rsSaldo = mysql_query($querySaldo);
	if (!$rsSaldo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowSaldo = mysql_fetch_assoc($rsSaldo);
	$saldo = $rowSaldo['saldo'];
	
	$query = sprintf("SELECT 
		te_estado_cuenta.id_estado_cuenta,
		te_estado_cuenta.id_empresa,
		te_estado_cuenta.tipo_documento,
		te_estado_cuenta.id_documento,
		te_estado_cuenta.fecha_registro,
		te_estado_cuenta.id_cuenta,
		te_estado_cuenta.id_empresa,
		te_estado_cuenta.monto,                                                       
		te_estado_cuenta.suma_resta,
		te_estado_cuenta.numero_documento,
		te_estado_cuenta.desincorporado,
		te_estado_cuenta.observacion,
		te_estado_cuenta.estados_principales,
		#SOLO PARA ORDENAMIENTO
		if(suma_resta = 0, monto, 0)as debito,                                                  
		if(suma_resta = 1, monto, 0)as credito     
	FROM te_estado_cuenta %s %s", $sqlBusq, $sqlBusqFecha);
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
	//acumulado de la paginas anteriores para que el saldo de correcto y no por pagina:
	if($startRow){
		$saldo = saldoPorPagina($pageNum-1, "", "", $valBusq, $maxRows*$pageNum, $totalRows);
	}
                
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuenta", "1%", $pageNum, "estados_principales", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuenta", "10%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuenta", "1%", $pageNum, "id_estado_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "T.D.");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuenta", "7%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto.");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuenta", "17%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Beneficiario");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuenta", "30%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuenta", "10%", $pageNum, "debito", $campOrd, $tpOrd, $valBusq, $maxRows, "D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuenta", "10%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Cr&eacute;dito");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuenta", "10%", $pageNum, "monto", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
	$htmlTh .= "</tr>";
	
	$conta = 0; 
	$contb = 0;
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estados_principales']){
			case 1 : $titleEstado = "Por Aplicar"; $imgEstado = "<img src=\"../img/iconos/ico_rojo.gif\">"; break;
			case 2 : $titleEstado = "Aplicado"; $imgEstado = "<img src=\"../img/iconos/ico_amarillo.gif\">"; break;
			case 3 : $titleEstado = "Conciliado"; $imgEstado = "<img src=\"../img/iconos/ico_verde.gif\">"; break;
			default : $titleEstado = ""; $imgEstado = ""; break;
		}
			
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" title=\"".$titleEstado."\">".$imgEstado."</td>";
			$htmlTb .= "<td align=\"center\" title=\"id_empresa: ".$row['id_empresa']." \">".date(spanDateFormat." h:i a",strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\" title=\"id_estado_cuenta: ".$row['id_estado_cuenta']." suma_resta: ".$row['suma_resta']." \">".tipoDocumento($row['id_estado_cuenta'])."</td>";
			$htmlTb .= "<td align=\"right\" title=\"id_documento: ".$row['id_documento']." \">".$row['numero_documento']."</td>";
			$htmlTb .= "<td align=\"left\">".strtoupper(beneficiario($row['tipo_documento'],$row['id_documento']))."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['observacion'])."</td>";
			if($row['suma_resta'] == 0){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
				$saldo = $saldo - $row['monto'];
				$htmlTb .= "<td align=\"right\">".number_format($saldo,'2','.',',')."</td>";
				$conta +=  $row['monto'];
			} else {
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			}
			if($row['suma_resta'] == 1){
				if($row['tipo_documento'] == "CH ANULADO"){
					$htmlTb .= "<td align=\"right\">0.00</td>";
					$htmlTb .= "<td align=\"right\">".number_format($saldo,'2','.',',')."</td>";				
				} else {
					$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
					$saldo = $saldo + $row['monto'];
					$htmlTb .= "<td align=\"right\">".number_format($saldo,'2','.',',')."</td>";
					$contb +=  $row['monto'];                                    
				}
			}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEstadoCuenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	$queryTotales = sprintf("SELECT 
		te_estado_cuenta.id_estado_cuenta,
		te_estado_cuenta.tipo_documento,
		te_estado_cuenta.id_documento,
		te_estado_cuenta.fecha_registro,
		te_estado_cuenta.id_cuenta,
		te_estado_cuenta.id_empresa,
		te_estado_cuenta.monto,                   
		te_estado_cuenta.suma_resta,
		te_estado_cuenta.numero_documento,
		te_estado_cuenta.desincorporado,
		te_estado_cuenta.observacion,
		te_estado_cuenta.estados_principales
	FROM te_estado_cuenta %s
	AND te_estado_cuenta.desincorporado != 0", $sqlBusq); //SIN FECHAS, desincorporado = cheques anulados
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	while($rowTotales = mysql_fetch_assoc($rsTotales)){	
		if($rowTotales['suma_resta'] == 0){
			$contTotales1 +=  $rowTotales['monto'];
		}else if($rowTotales['suma_resta'] == 1){
			$contTotales2 +=  $rowTotales['monto'];
		}
	}        
	
	//TOTALES PERO EN RANGO DE FECHA gregor        
	$contTotalesFecha1 = 0;
	$contTotalesFecha2 = 0;
	$queryTotales = sprintf("SELECT 
		te_estado_cuenta.id_estado_cuenta,
		te_estado_cuenta.tipo_documento,
		te_estado_cuenta.id_documento,
		te_estado_cuenta.fecha_registro,
		te_estado_cuenta.id_cuenta,
		te_estado_cuenta.id_empresa,
		te_estado_cuenta.monto,                   
		te_estado_cuenta.suma_resta,
		te_estado_cuenta.numero_documento,
		te_estado_cuenta.desincorporado,
		te_estado_cuenta.observacion,
		te_estado_cuenta.estados_principales
	FROM te_estado_cuenta %s %s
	AND te_estado_cuenta.desincorporado != 0", $sqlBusq, $sqlBusqFecha);
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	while($rowTotales = mysql_fetch_assoc($rsTotales)){
		if($rowTotales['suma_resta'] == 0){
			$contTotalesFecha1 +=  $rowTotales['monto'];
		}else if($rowTotales['suma_resta'] == 1){
			$contTotalesFecha2 +=  $rowTotales['monto'];
		}
	}
	
	$htmlx.="<table align=\"center\" border=\"0\" height=\"24\" width=\"60%\">";
		$htmlx.="<tr class=\"tituloColumna\">
					<td width=\"20%\"></td>
					<td width=\"20%\">"."D&eacute;bito"."</td>
					<td width=\"20%\">"."Cr&eacute;dito"."</td>
				</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total por P&aacute;gina:</td>";
			$htmlx.="<td align=\"right\" width=\"83\">".number_format($conta,'2','.',',')."</td>";
			$htmlx.="<td align=\"right\" width=\"80\">".number_format($contb,'2','.',',')."</td>";
		$htmlx.="</tr>";
		if($valCadBusq[2] != ''){
			$htmlx.="<tr>";
				$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total Entre Fechas:</td>";
				$htmlx.="<td align=\"right\" width=\"83\">".number_format($contTotalesFecha1,'2','.',',')."</td>";
				$htmlx.="<td align=\"right\" width=\"80\">".number_format($contTotalesFecha2,'2','.',',')."</td>";
			$htmlx.="</tr>";
		}
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\" width=\"83\">".number_format($contTotales1,'2','.',',')."</td>";
			$htmlx.="<td align=\"right\" width=\"80\">".number_format($contTotales2,'2','.',',')."</td>";
		$htmlx.="</tr>";
	$htmlx.="</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("tdListaEstadoCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlx);
	
	return $objResponse;
}

function listaBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("banco.idBanco != 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("banco.nombreBanco LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		banco.idBanco, 
		banco.nombreBanco, 
		banco.sucursal 
	FROM bancos banco
		INNER JOIN cuentas cuenta ON (cuenta.idBanco = banco.idBanco) %s GROUP BY banco.idBanco", $sqlBusq);
	
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
		$htmlTh .= "<td width=\"5%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaBanco", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listaBanco", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'><button type=\"button\" onclick=\"xajax_asignarBanco('".$row['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/select.png\"/></button></td>";
			$htmlTb .= "<td align=\"center\">".$row['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['sucursal'])."</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaBanco(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaBanco(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
				
	$objResponse->assign("tdListaBanco","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"buscarBanco");
$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstado");
$xajax->register(XAJAX_FUNCTION,"exportarEstadoCuentaExcel");
$xajax->register(XAJAX_FUNCTION,"listaEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"listaBanco");

function tipoDocumento($idEstadoCuenta){	
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$idEstadoCuenta);

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if($row['tipo_documento'] == 'NC'){
		$queryNC = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = %s", 
			valTpDato($row['id_documento'], "int"));
		$rsNC = mysql_query($queryNC);
		if (!$rsNC) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowNC = mysql_fetch_assoc($rsNC);
		if($rowNC['tipo_nota_credito'] == '1'){
			$respuesta = "NC";
		}else if($rowNC['tipo_nota_credito'] == '2'){
			$respuesta = "NC/TD";
		}else if($rowNC['tipo_nota_credito'] == '3'){
			$respuesta = "NC/TC";
		}else if($rowNC['tipo_nota_credito'] == '4'){
			$respuesta = "NC/TR";
		}
	}else{
		$respuesta = $row['tipo_documento']; //DP, ND, TR, CH, CH ANULADO
	}
	
	return $respuesta;
}

function beneficiario($tipoDocumento, $id){
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = %s",
		valTpDato($id, "int"));

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if($tipoDocumento == 'DP'){
		$respuesta = "DEPÓSITO";
	}	
	if($tipoDocumento == 'NC'){
		$respuesta = "NOTA DE CRÉDITO";
	}			
	if($tipoDocumento == 'ND'){
		$respuesta = "NOTA DE DÉBITO";
	}
		
	if($tipoDocumento == 'TR'){		
		$query = sprintf("SELECT * FROM te_transferencia WHERE id_transferencia = %s",
			valTpDato($id, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if($row['beneficiario_proveedor'] == 1){
			$respuesta = nombreP($row['id_beneficiario_proveedor']);	
		}else{
			$respuesta = nombreB($row['id_beneficiario_proveedor']);
		}
	}
	
	if($tipoDocumento == 'CH'){		
		$query = sprintf("SELECT * FROM te_cheques WHERE id_cheque = %s",
			valTpDato($id, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if($row['beneficiario_proveedor'] == 1){
			$respuesta = nombreP($row['id_beneficiario_proveedor']);
		}else{
			$respuesta = nombreB($row['id_beneficiario_proveedor']);
		}
	}
	
	if($tipoDocumento == 'CH ANULADO'){			
		$query = sprintf("SELECT * FROM te_cheques_anulados WHERE id_cheque = %s",
			valTpDato($id, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
	
		if($row['beneficiario_proveedor'] == 1){
			$respuesta = nombreP($row['id_beneficiario_proveedor']);
		}else{		
			$respuesta = nombreB($row['id_beneficiario_proveedor']);
		}
	}
	
	return $respuesta;
}

function nombreB($id){
	$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$id);
	$rsBeneficiario = mysql_query($queryBeneficiario);
	if (!$rsBeneficiario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowBeneficiario = mysql_fetch_assoc($rsBeneficiario);
	
	$respuesta = utf8_encode($rowBeneficiario['nombre_beneficiario']);

	return $respuesta;
}

function nombreP($id){	
	$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$id);
	$rsProveedor = mysql_query($queryProveedor);
	if (!$rsProveedor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowProveedor = mysql_fetch_assoc($rsProveedor);
	
	$respuesta = utf8_encode($rowProveedor['nombre']);
	
	return $respuesta;
}

//hace el conteo para llevar exacto la columna de saldo a sumar o restar por pagina
function saldoPorPagina($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL){	
    $valCadBusq = explode("|", $valBusq);
    $startRow = 0;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.estados_principales != 0 AND te_estado_cuenta.desincorporado != 0");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.id_cuenta = %s",
		valTpDato($valCadBusq[1], "int"));
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(te_estado_cuenta.fecha_registro) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_estado_cuenta.estados_principales = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_estado_cuenta.tipo_documento = %s",
			valTpDato($valCadBusq[5], "text"));
	}
	
	$querySaldo = sprintf("SELECT saldo FROM cuentas WHERE idCuentas = %s",
		valTpDato($valCadBusq[1], "int"));
	$rsSaldo = mysql_query($querySaldo);
	if (!$rsSaldo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowSaldo = mysql_fetch_assoc($rsSaldo);
	$saldo = $rowSaldo['saldo'];
	
	$query = sprintf("SELECT 
		te_estado_cuenta.id_estado_cuenta,
		te_estado_cuenta.tipo_documento,
		te_estado_cuenta.id_documento,
		te_estado_cuenta.fecha_registro,
		te_estado_cuenta.id_cuenta,
		te_estado_cuenta.id_empresa,
		te_estado_cuenta.monto,                                
		te_estado_cuenta.suma_resta,
		te_estado_cuenta.numero_documento,
		te_estado_cuenta.desincorporado,
		te_estado_cuenta.observacion,
		te_estado_cuenta.estados_principales,
		#SOLO PARA ORDENAMIENTO
		if(suma_resta = 0, monto, 0)as debito,                                                  
		if(suma_resta = 1, monto, 0)as credito     
	FROM te_estado_cuenta %s ", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
    
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
        
	$conta = 0; 
	$contb = 0;
        
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;

		if($row['suma_resta'] == 0){
			$saldo = $saldo - $row['monto'];
			$conta +=  $row['monto'];
		} else {

		}
		if($row['suma_resta'] == 1){
			if($row['tipo_documento'] == "CH ANULADO"){
			
			} else {
				$saldo = $saldo + $row['monto'];
				$contb +=  $row['monto'];                                    
			}
		}
	}
	
	return $saldo;
}

?>