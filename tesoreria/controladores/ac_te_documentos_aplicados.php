<?php

function aplicarBloque($valForm){
	$objResponse = new xajaxResponse();

	if(!xvalidaAcceso($objResponse,"te_documentos_aplicados","insertar")) { return $objResponse; }
	
	if (isset($valForm['cbxItmDesaplicado'])) {		
		mysql_query("START TRANSACTION;");
		
		foreach($valForm['cbxItmDesaplicado'] as $indice => $idEstadoCuenta) {
			$queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = %s", 
				valTpDato($idEstadoCuenta, "int"));
			$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
			if (!$rsEstadoCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowEstadoCuenta = mysql_fetch_assoc($rsEstadoCuenta);
			
			$queryAuditoria = sprintf("INSERT INTO te_auditoria_aplicacion(id_estado_de_cuenta, id_usuario, tipo_accion, fecha_cambio, observacion) 
			VALUES (%s, %s, %s, %s, %s)",
				valTpDato($rowEstadoCuenta['id_estado_cuenta'], "int"),
				valTpDato($_SESSION['idUsuarioSysGts'], "int"),
				valTpDato(1, "int"),
				valTpDato("NOW()", "campo"),
				valTpDato('APLICADO POR LOTE', "text"));
			$rsAuditoria = mysql_query($queryAuditoria);
			if (!$rsAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			if($rowEstadoCuenta['tipo_documento'] == 'DP'){
				$query = sprintf("UPDATE te_depositos SET estado_documento = %s
				WHERE id_deposito = %s",
					valTpDato(2, "int"),
					valTpDato($rowEstadoCuenta['id_documento'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
			
			if($rowEstadoCuenta['tipo_documento'] == 'ND'){
				$query = sprintf("UPDATE te_nota_debito SET estado_documento = %s
				WHERE id_nota_debito = %s",
					valTpDato(2, "int"),
					valTpDato($rowEstadoCuenta['id_documento'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
			
			if($rowEstadoCuenta['tipo_documento'] == 'TR'){
				$query = sprintf("UPDATE te_transferencia SET estado_documento = %s
				WHERE id_transferencia = %s",
					valTpDato(2, "int"),
					valTpDato($rowEstadoCuenta['id_documento'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
			
			if($rowEstadoCuenta['tipo_documento'] == 'NC'){
				$query = sprintf("UPDATE te_nota_credito SET estado_documento = %s
				WHERE id_nota_credito = %s",
					valTpDato(2, "int"),
					valTpDato($rowEstadoCuenta['id_documento'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
			}
			
			if($rowEstadoCuenta['tipo_documento'] == 'CH'){
				$query = sprintf("UPDATE te_cheques SET estado_documento = %s
				WHERE id_cheque = %s",
					valTpDato(2, "int"),
					valTpDato($rowEstadoCuenta['id_documento'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
			
			$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = %s
			WHERE id_estado_cuenta = %s",
				valTpDato(2, "int"),
				valTpDato($idEstadoCuenta, "int"));
			$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
			if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Aplicado correctamente.");
		$objResponse->script("byId('btnBuscarDesaplicado').click(); 
		byId('btnBuscarAplicado').click();");
	}
	
	return $objResponse;
}

function aplicarEstadoCuenta($frmAplicarDesaplicarDcto){
	$objResponse = new xajaxResponse();
	
	if(!xvalidaAcceso($objResponse,"te_documentos_aplicados","insertar")) { return $objResponse; }
	
	$idEstadoCuenta = $frmAplicarDesaplicarDcto["hddIdEstadoCuenta"];
	$fechaAplicar = date("Y-m-d", strtotime($frmAplicarDesaplicarDcto["txtFechaAplicar"]));	
	
	mysql_query("START TRANSACTION;");
	
	$queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = %s", 
		valTpDato($idEstadoCuenta, "int"));
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if (!$rsEstadoCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEstadoCuenta = mysql_fetch_assoc($rsEstadoCuenta);
	
	$queryAuditoria = sprintf("INSERT INTO te_auditoria_aplicacion(id_estado_de_cuenta, id_usuario, tipo_accion, fecha_cambio, observacion) 
	VALUES (%s, %s, %s, %s, %s)",
		valTpDato($rowEstadoCuenta['id_estado_cuenta'], "int"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato(1, "int"),
		valTpDato("NOW()", "campo"),
		valTpDato($frmAplicarDesaplicarDcto["txtObservacion"], "text"));
	$rsAuditoria = mysql_query($queryAuditoria);
	if (!$rsAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if($rowEstadoCuenta['tipo_documento'] == 'DP'){
		$query = sprintf("UPDATE te_depositos SET estado_documento = %s, fecha_aplicacion = %s 
		WHERE id_deposito = %s",
			valTpDato(2, "int"),
			valTpDato($fechaAplicar, "date"),
			valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rs = mysql_query($query);
		if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'ND'){
		$query = sprintf("UPDATE te_nota_debito SET estado_documento = %s, fecha_aplicacion = %s 
		WHERE id_nota_debito = %s",
			valTpDato(2, "int"),
			valTpDato($fechaAplicar, "date"),
			valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rs = mysql_query($query);
		if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'TR'){
		$query = sprintf("UPDATE te_transferencia SET estado_documento = %s, fecha_aplicacion = %s 
		WHERE id_transferencia = %s",
			valTpDato(2, "int"),
			valTpDato($fechaAplicar, "date"),
			valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rs = mysql_query($query);
		if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'NC'){
		$query = sprintf("UPDATE te_nota_credito SET estado_documento = %s, fecha_aplicacion = %s 
		WHERE id_nota_credito = %s",
			valTpDato(2, "int"),
			valTpDato($fechaAplicar, "date"),
			valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rs = mysql_query($query);
		if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'CH'){
		$query = sprintf("UPDATE te_cheques SET estado_documento = %s, fecha_aplicacion = %s 
		WHERE id_cheque = %s",
			valTpDato(2, "int"),
			valTpDato($fechaAplicar, "date"),
			valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rs = mysql_query($query);
		if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = %s, fecha_registro = %s
	WHERE id_estado_cuenta = %s",
		valTpDato(2, "int"),
		valTpDato($fechaAplicar, "date"),
		valTpDato($idEstadoCuenta, "int"));
	$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
	if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnBuscarDesaplicado').click(); 
	byId('btnBuscarAplicado').click();");
	
	$objResponse->script("byId('btnCancelarAplicarDesaplicarDcto').click();");
	
	return $objResponse;
}

function asignarBanco($idBanco){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE idBanco = %s",
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtNombreBanco","value",  utf8_encode($row['nombreBanco']));
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

function buscarEstadoCuentaAplicado($valForm, $idCuenta) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$idCuenta,
		$valForm['txtFechaDesde2'],
		$valForm['txtFechaHasta2'],
		$valForm['txtCriterioAplicado']);
	
	$objResponse->loadCommands(listaEstadoCuentaAplicado(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarEstadoCuentaDesaplicado($valForm, $idCuenta) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$idCuenta,
		$valForm['txtFechaDesde1'],
		$valForm['txtFechaHasta1'],
		$valForm['txtCriterioDesaplicado']);
	
	$objResponse->loadCommands(listaEstadoCuentaDesaplicado(0, "", "", $valBusq));
	
	return $objResponse;
}

function cargaLstCuenta($idEmpresa, $idBanco, $selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM cuentas WHERE id_empresa = %s AND idBanco = %s",
		valTpDato($idEmpresa, "int"),
		valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"lstCuenta\" name=\"lstCuenta\" onchange=\"byId('btnBuscarDesaplicado').click(); byId('btnBuscarAplicado').click();\" class=\"inputHabilitado\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)){
		$html .= "<option ".$selected." value=\"".$row['idCuentas']."\">".$row['numeroCuentaCompania']."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdLstCuenta","innerHTML",$html);
	
	return $objResponse;
}

function desaplicarBloque($valForm){
	$objResponse = new xajaxResponse();

	if(!xvalidaAcceso($objResponse,"te_documentos_aplicados","editar")) { return $objResponse; }
	
	if (isset($valForm['cbxItmAplicado'])) {
		mysql_query("START TRANSACTION;");
		
		foreach($valForm['cbxItmAplicado'] as $indice => $idEstadoCuenta) {
			$queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = %s", 
				valTpDato($idEstadoCuenta, "int"));
			$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
			if (!$rsEstadoCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$rowEstadoCuenta = mysql_fetch_assoc($rsEstadoCuenta);
			
			$queryAuditoria = sprintf("INSERT INTO te_auditoria_aplicacion(id_estado_de_cuenta, id_usuario, tipo_accion, fecha_cambio, observacion) 
			VALUES (%s, %s, %s, %s, %s)",
				valTpDato($rowEstadoCuenta['id_estado_cuenta'], "int"),
				valTpDato($_SESSION['idUsuarioSysGts'], "int"),
				valTpDato(0, "int"),
				valTpDato("NOW()", "campo"),
				valTpDato('DESAPLICADO POR LOTE', "text"));
			$rsAuditoria = mysql_query($queryAuditoria);
			if (!$rsAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					
			if($rowEstadoCuenta['tipo_documento'] == 'DP'){
				$query = sprintf("UPDATE te_depositos SET estado_documento = %s WHERE id_deposito = %s",
					valTpDato(1, "int"),
					valTpDato($rowEstadoCuenta['id_documento'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
			
			if($rowEstadoCuenta['tipo_documento'] == 'ND'){
				$query = sprintf("UPDATE te_nota_debito SET estado_documento = %s WHERE id_nota_debito = %s",
					valTpDato(1, "int"),
					valTpDato($rowEstadoCuenta['id_documento'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
			
			if($rowEstadoCuenta['tipo_documento'] == 'TR'){
				$query = sprintf("UPDATE te_transferencia SET estado_documento = %s WHERE id_transferencia = %s",
					valTpDato(1, "int"),
					valTpDato($rowEstadoCuenta['id_documento'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
			
			if($rowEstadoCuenta['tipo_documento'] == 'NC'){
				$query = sprintf("UPDATE te_nota_credito SET estado_documento = %s WHERE id_nota_credito = %s",
					valTpDato(1, "int"),
					valTpDato($rowEstadoCuenta['id_documento'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
			
			if($rowEstadoCuenta['tipo_documento'] == 'CH'){
				$query = sprintf("UPDATE te_cheques SET estado_documento = %s WHERE id_cheque = %s",
					valTpDato(1, "int"),
					valTpDato($rowEstadoCuenta['id_documento'], "int"));
				$rs = mysql_query($query);
				if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
			
			$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = %s 
				WHERE id_estado_cuenta = %s",
				valTpDato(1, "int"),
				valTpDato($idEstadoCuenta, "int"));
			$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
			if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Desaplicado correctamente.");
		$objResponse->script("byId('btnBuscarDesaplicado').click(); 
		byId('btnBuscarAplicado').click();");
	}
	
	return $objResponse;
}

function desaplicarEstadoCuenta($frmAplicarDesaplicarDcto){
	$objResponse = new xajaxResponse();
	
	if(!xvalidaAcceso($objResponse,"te_documentos_aplicados","editar")) { return $objResponse; }
	
	$idEstadoCuenta = $frmAplicarDesaplicarDcto["hddIdEstadoCuenta"];
	
	mysql_query("START TRANSACTION;");
	
	$queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = %s",
		valTpDato($idEstadoCuenta, "int"));
	$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
	if (!$rsEstadoCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEstadoCuenta = mysql_fetch_assoc($rsEstadoCuenta);
	
	$queryAuditoria = sprintf("INSERT INTO te_auditoria_aplicacion(id_estado_de_cuenta, id_usuario, tipo_accion, fecha_cambio, observacion) 
	VALUES (%s, %s, %s, %s, %s)",
		valTpDato($rowEstadoCuenta['id_estado_cuenta'], "int"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato(0, "int"),
		valTpDato("NOW()", "campo"),
		valTpDato($frmAplicarDesaplicarDcto['txtObservacion'], "text"));
	$rsAuditoria = mysql_query($queryAuditoria);
	if (!$rsAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if($rowEstadoCuenta['tipo_documento'] == 'DP'){
		$query = sprintf("UPDATE te_depositos SET estado_documento = %s WHERE id_deposito = %s",
			valTpDato(1, "int"),
			valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rs = mysql_query($query);
		if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'ND'){
		$query = sprintf("UPDATE te_nota_debito SET estado_documento = %s WHERE id_nota_debito = %s",
			valTpDato(1, "int"),
			valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rs = mysql_query($query);
		if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'TR'){
		$query = sprintf("UPDATE te_transferencia SET estado_documento = %s WHERE id_transferencia = %s",
			valTpDato(1, "int"),
			valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rs = mysql_query($query);
		if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'NC'){
		$query = sprintf("UPDATE te_nota_credito SET estado_documento = %s WHERE id_nota_credito = %s",
			valTpDato(1, "int"),
			valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rs = mysql_query($query);
		if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	if($rowEstadoCuenta['tipo_documento'] == 'CH'){
		$query = sprintf("UPDATE te_cheques SET estado_documento = %s WHERE id_cheque = %s",
			valTpDato(1, "int"),
			valTpDato($rowEstadoCuenta['id_documento'], "int"));
		$rs = mysql_query($query);
		if (!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = %s 
		WHERE id_estado_cuenta = %s",
		valTpDato(1, "int"),
		valTpDato($idEstadoCuenta, "int"));
	$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
	if (!$rsEstadoCuentaActualiza){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnBuscarDesaplicado').click(); 
	byId('btnBuscarAplicado').click();");
	
	$objResponse->script("byId('btnCancelarAplicarDesaplicarDcto').click();");
	
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

function listaEstadoCuentaAplicado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.desincorporado != 0 
	AND te_estado_cuenta.estados_principales = 2");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.id_cuenta = %s",
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(te_estado_cuenta.fecha_registro) BETWEEN %s AND %s ",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date")); 
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(te_estado_cuenta.numero_documento LIKE %s 
		OR te_estado_cuenta.observacion LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}

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
		DATE(fecha_registro) AS fecha_registro,
		(SELECT COUNT(*) 
			FROM te_auditoria_aplicacion 
			WHERE id_estado_de_cuenta = te_estado_cuenta.id_estado_cuenta) AS cantidad_auditoria,
		#SOLO PARA ORDENAMIENTO
		if(suma_resta = 0, monto, 0)as debito,                                                  
		if(suma_resta = 1, monto, 0)as credito
	FROM te_estado_cuenta %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);        
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$querySaldoIni = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valCadBusq[0]);
	$rsSaldoIni = mysql_query($querySaldoIni);
	if (!$rsSaldoIni){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowSaldoIni = mysql_fetch_assoc($rsSaldoIni);
	
	$htmlSalIni = "<table border=\"0\" width=\"100%\">";
	$htmlSalIni .= "<tr>";
		$htmlSalIni .= "<td align=\"right\">";
			$htmlSalIni .= "<table class=\"tabla\" border=\"0\" cellpadding=\"2\">";
			$htmlSalIni .= "<tr align=\"right\">";
				$htmlSalIni .= "<td width=\"100\" style=\"border:none;\" class=\"tituloCampo\">Saldo:</td>";
				$htmlSalIni .= "<td><input style=\"text-align:right\" class=\"trResaltarTotal\" type=\"text\" id=\"txtSaldoInicial\" name=\"txtSaldoInicial\" size=\"25\" readonly=\"readonly\" value = \"".$rowSaldoIni['saldo']."\"/></td>";
			$htmlSalIni .= "</tr>";
			$htmlSalIni .= "</table>";
		$htmlSalIni .= "</td>";
	$htmlSalIni .= "</tr>";
	$htmlSalIni .= "</table>";
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItmAplicado\" onclick=\"selecAllChecks(this.checked,this.id,2);\"/></td>";
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicado", "1%", $pageNum, "estados_principales", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicado", "1%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicado", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicado", "50%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicado", "35%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicado", "3%", $pageNum, "debito", $campOrd, $tpOrd, $valBusq, $maxRows, "D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicado", "3%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Cr&eacute;dito");
		$htmlTh .="<td colspan=\"2\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	$conta = 0;
	$contb = 0;
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$fechaRegistro = "-";
		if($row['fecha_registro'] != ""){ 
			$fechaRegistro = date(spanDateFormat,strtotime($row['fecha_registro']));
		}
		
		switch($row['estados_principales']){
			case 1: $imgEstado = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Por Aplicar\">"; break;
			case 2: $imgEstado = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Aplicado\">"; break;
			case 3: $imgEstado = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Conciliado\">"; break;
			default : $imgEstado = "";
		}
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td><input id=\"cbxItmAplicado\" name=\"cbxItmAplicado[]\" type=\"checkbox\" value=\"".$row['id_estado_cuenta']."\"></td>";		
			$htmlTb .= "<td align=\"center\">".$imgEstado."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaRegistro."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo_documento']."</td>";
			$htmlTb .= "<td align=\"left\" class=\"texto_9px\">".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_documento']."</td>";
			if($row['suma_resta'] == 0){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$conta +=  $row['monto'];
			}else{
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			}
			if($row['suma_resta'] == 1){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$contb +=  $row['monto'];
			}else{
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			}
						
			$htmlTb .= sprintf("<td align=\"center\"><a class=\"modalImg\" id=\"aAbrirAplicacion\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAplicacionDesaplicacion', %s, %s);\"><img class=\"puntero\" src=\"../img/iconos/ico_quitar.gif\" title=\"Desaplicar Documento\"/></a></td>",
				$row['id_estado_cuenta'],
				1);
			
			if($row['cantidad_auditoria'] == 0){
				$htmlTb .= "<td align=\"center\"><img src=\"../img/iconos/ico_comentario_f2.png\" title=\"Sin Comentarios\"/></td>";
			}else{
				$htmlTb .= sprintf("<td align=\"center\"><a class=\"modalImg\" id=\"aVerAuditoria\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblListaAuditoria', %s);\"><img class=\"puntero\" src=\"../img/iconos/ico_comentario.png\" title=\"Ver Comentarios\"/></a></td>",
					$row['id_estado_cuenta']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaAplicado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaAplicado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEstadoCuentaAplicado(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaAplicado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaAplicado(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "<br><br>";
	
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
		te_estado_cuenta.estados_principales,
		te_estado_cuenta.fecha_registro
	FROM te_estado_cuenta %s", $sqlBusq);
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	while($rowTotales = mysql_fetch_assoc($rsTotales)){	
		if($rowTotales['suma_resta'] == 0){
			$contTotales1 += $rowTotales['monto'];
		}else if($rowTotales['suma_resta'] == 1){
			$contTotales2 += $rowTotales['monto'];
		}
	}
	
	$queryCuentas = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valCadBusq[0]);
	$rsCuentas = mysql_query($queryCuentas);
	if(!$rsCuentas){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuentas = mysql_fetch_assoc($rsCuentas);
		
	$saldoTotal = ($rowCuentas['saldo'] + $contTotales2) - $contTotales1; 
	
	$htmlx.="<table align=\"right\" border=\"0\" width=\"60%\">";
		$htmlx.="<tr class=\"tituloColumna\">
					<td width=\"20%\"></td>
					<td align=\"center\">"."D&eacute;bito"."</td>
					<td align=\"center\">"."Cr&eacute;dito"."</td>
					<td align=\"center\">"."Saldo"."</td>
				</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\" class=\"trResaltarTotal\">".number_format($contTotales1,'2','.',',')."</td>";
			$htmlx.="<td align=\"right\" class=\"trResaltarTotal\">".number_format($contTotales2,'2','.',',')."</td>";
			$htmlx.="<td align=\"right\" class=\"trResaltarTotal\">".number_format($saldoTotal,'2','.',',')."</td>";
		$htmlx.="</tr>";
	$htmlx.="</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"9\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaEstadoAplicado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlSalIni.$htmlx);
	
	return $objResponse;
}

function listaEstadoCuentaDesaplicado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.desincorporado != 0 
	AND te_estado_cuenta.estados_principales = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.id_cuenta = %s",
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(te_estado_cuenta.fecha_registro) BETWEEN %s AND %s ",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date")); 
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(te_estado_cuenta.numero_documento LIKE %s 
		OR te_estado_cuenta.observacion LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}

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
		DATE(fecha_registro) AS fecha_registro,
		(SELECT COUNT(*) 
			FROM te_auditoria_aplicacion 
			WHERE id_estado_de_cuenta = te_estado_cuenta.id_estado_cuenta) AS cantidad_auditoria,
		#SOLO PARA ORDENAMIENTO
		if(suma_resta = 0, monto, 0)as debito,                                                  
		if(suma_resta = 1, monto, 0)as credito      
	FROM te_estado_cuenta %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);        
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItmDesaplicado\" onclick=\"selecAllChecks(this.checked,this.id,1);\"/></td>";
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaDesaplicado", "1%", $pageNum, "estados_principales", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaDesaplicado", "1%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaDesaplicado", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaDesaplicado", "50%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaDesaplicado", "35%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaDesaplicado", "3%", $pageNum, "debito", $campOrd, $tpOrd, $valBusq, $maxRows, "D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaDesaplicado", "3%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Cr&eacute;dito");
		$htmlTh .="<td colspan=\"2\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
                
	$conta = 0;
	$contb = 0;
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$fechaRegistro = "-";
		if($row['fecha_registro'] != ""){ 
			$fechaRegistro = date(spanDateFormat,strtotime($row['fecha_registro']));
		}
		
		switch($row['estados_principales']){
			case 1: $imgEstado = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Por Aplicar\">"; break;
			case 2: $imgEstado = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Aplicado\">"; break;
			case 3: $imgEstado = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Conciliado\">"; break;
			default : $imgEstado = "";
		}
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td><input id=\"cbxItmDesaplicado\" name=\"cbxItmDesaplicado[]\" type=\"checkbox\" value=\"".$row['id_estado_cuenta']."\"></td>";
			$htmlTb .= "<td align=\"center\">".$imgEstado."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaRegistro."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo_documento']."</td>";
			$htmlTb .= "<td align=\"left\" class=\"texto_9px\">".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_documento']."</td>";
			if($row['suma_resta'] == 0){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$conta +=  $row['monto'];
			}else{
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			}
			if($row['suma_resta'] == 1){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$contb +=  $row['monto'];
			}else{
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			}
						
			$htmlTb .= sprintf("<td align=\"center\"><a class=\"modalImg\" id=\"aAbrirAplicacion\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAplicacionDesaplicacion', %s, %s);\"><img class=\"puntero\" src=\"../img/iconos/ico_agregar.gif\" title=\"Aplicar Documento\"/></a></td>",
				$row['id_estado_cuenta'],
				2);
		
			if($row['cantidad_auditoria'] == 0){
				$htmlTb .= "<td align=\"center\"><img src=\"../img/iconos/ico_comentario_f2.png\" title=\"Sin Comentarios\"/></td>";
			}else{
				$htmlTb .= sprintf("<td align=\"center\"><a class=\"modalImg\" id=\"aVerAuditoria\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblListaAuditoria', %s);\"><img class=\"puntero\" src=\"../img/iconos/ico_comentario.png\" title=\"Ver Comentarios\"/></a></td>",
					$row['id_estado_cuenta']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaDesaplicado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaDesaplicado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEstadoCuentaDesaplicado(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaDesaplicado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaDesaplicado(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "<br><br>";
	
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
		te_estado_cuenta.estados_principales,
		te_estado_cuenta.fecha_registro
	FROM te_estado_cuenta %s", $sqlBusq);
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	while($rowTotales = mysql_fetch_assoc($rsTotales)){	
		if($rowTotales['suma_resta'] == 0){
			$contTotales1 += $rowTotales['monto'];
		}else if($rowTotales['suma_resta'] == 1){
			$contTotales2 += $rowTotales['monto'];
		}
	}
	
	//$queryCuentas = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valCadBusq[0]);
	//$rsCuentas = mysql_query($queryCuentas);
	//if(!$rsCuentas){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	//$rowCuentas = mysql_fetch_assoc($rsCuentas);
	
	$htmlx.="<table align=\"right\" border=\"0\" width=\"60%\">";
		$htmlx.="<tr class=\"tituloColumna\">";
			$htmlx.="<td align=\"center\"></td>";
			$htmlx.="<td align=\"center\">D&eacute;bito</td>";
			$htmlx.="<td align=\"center\">Cr&eacute;dito</td>";
			//$htmlx.="<td width=\"10%\">".htmlentities("Saldo")."</td>";
		$htmlx.="</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\" class=\"trResaltarTotal\">".number_format($contTotales1,'2','.',',')."</td>";
			$htmlx.="<td align=\"right\" class=\"trResaltarTotal\">".number_format($contTotales2,'2','.',',')."</td>";
			//$htmlx.="<td align=\"right\" >".number_format($rowCuentas['saldo_tem'],'2','.',',')."</td>";
		$htmlx.="</tr>";
	$htmlx.="</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"9\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaEstadoCuentaDesaplicado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlx);
	
	return $objResponse;
}

function formAplicarDesaplicarDcto($idEstadoCuenta, $accionAplicarDesaplicar) {
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT 
		te_estado_cuenta.numero_documento AS numero_documento_estado_cuenta,
		te_estado_cuenta.tipo_documento AS tipo_documento_estado_cuenta,
		te_estado_cuenta.fecha_registro AS fecha_aplicacion_estado_cuenta,
		
		(CASE te_estado_cuenta.tipo_documento
			WHEN 'DP' THEN (SELECT fecha_registro FROM te_depositos WHERE id_deposito = te_estado_cuenta.id_documento)
			WHEN 'ND' THEN (SELECT fecha_registro FROM te_nota_debito WHERE id_nota_debito = te_estado_cuenta.id_documento)
			WHEN 'NC' THEN (SELECT fecha_registro FROM te_nota_credito WHERE id_nota_credito = te_estado_cuenta.id_documento)
			WHEN 'CH' THEN (SELECT fecha_registro FROM te_cheques WHERE id_cheque = te_estado_cuenta.id_documento)
			WHEN 'TR' THEN (SELECT fecha_registro FROM te_transferencia WHERE id_transferencia = te_estado_cuenta.id_documento)
			ELSE ''
		END) AS fecha_registro_documento			
	FROM te_estado_cuenta 
	WHERE id_estado_cuenta = %s",
		valTpDato($idEstadoCuenta, "int"));
	$rs = mysql_query($sql);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	if($accionAplicarDesaplicar == 1){// 1 = Desaplicar, 2 = Aplicar
		$objResponse->script("byId('trFechaAplicar').style.display = 'none';");
	}else if ($accionAplicarDesaplicar == 2){
		$objResponse->script("byId('trFechaAplicar').style.display = '';
		byId('txtObservacion').value = 'DOCUMENTO APLICADO'");
	}
	
	$objResponse->assign('txtNroDocumento','value',$row["numero_documento_estado_cuenta"]);
	$objResponse->assign('txtTipoDocumento','value',$row["tipo_documento_estado_cuenta"]);
	$objResponse->assign('txtFechaRegistro','value',fecha($row["fecha_registro_documento"]));
	$objResponse->assign('txtFechaAplicado','value',fecha($row["fecha_aplicacion_estado_cuenta"]));
	$objResponse->assign('txtFechaAplicar','value',fecha($row["fecha_aplicacion_estado_cuenta"]));
	
	$objResponse->assign('hddAccAplicarDesaplicar','value',$accionAplicarDesaplicar);
	$objResponse->assign('hddIdEstadoCuenta','value',$idEstadoCuenta);
	
	return $objResponse;
}

function listaAuditoria($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_estado_de_cuenta = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$queryAuditoria = sprintf("SELECT 
		auditoria.fecha_cambio,
		auditoria.id_usuario,
		auditoria.tipo_accion,
		auditoria.observacion,
		usuario.nombre_empleado
	FROM te_auditoria_aplicacion auditoria
	INNER JOIN vw_iv_usuarios usuario ON usuario.id_usuario = auditoria.id_usuario %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitAuditoria = sprintf(" %s %s LIMIT %d OFFSET %d", $queryAuditoria, $sqlOrd, $maxRows, $startRow);
	$rsLimitAuditoria = mysql_query($queryLimitAuditoria);
	if(!$rsLimitAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	if ($totalRows == NULL) {
		$rsAuditoria = mysql_query($queryAuditoria);
		if(!$rsAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsAuditoria);
	}
	
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "5%", $pageNum, "fecha_cambio", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "15%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Empleado");			
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "5%", $pageNum, "tipo_accion", $campOrd, $tpOrd, $valBusq, $maxRows, "Acci&oacute;n");			
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "40%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaciones");			
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowAuditoria = mysql_fetch_assoc($rsLimitAuditoria)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		if($rowAuditoria['tipo_accion'] == 1){
			$tipoAccion = "Aplicado";
		}else{
			$tipoAccion = "Desaplicado";
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($rowAuditoria['fecha_cambio']))."</td>";
			$htmlTb .= "<td align=\"left\">".$rowAuditoria['nombre_empleado']."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoAccion."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($rowAuditoria['observacion'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}		
	
	$objResponse->assign("tdListaAuditoria","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"aplicarBloque");
$xajax->register(XAJAX_FUNCTION,"aplicarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"buscarBanco");
$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuentaAplicado");
$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuentaDesaplicado");
$xajax->register(XAJAX_FUNCTION,"cargaLstCuenta");
$xajax->register(XAJAX_FUNCTION,"desaplicarBloque");
$xajax->register(XAJAX_FUNCTION,"desaplicarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"formAplicarDesaplicarDcto");
$xajax->register(XAJAX_FUNCTION,"listaAuditoria");
$xajax->register(XAJAX_FUNCTION,"listaBanco");
$xajax->register(XAJAX_FUNCTION,"listaEstadoCuentaAplicado");
$xajax->register(XAJAX_FUNCTION,"listaEstadoCuentaDesaplicado");

function fecha($fecha){
	if($fecha != ""){
		$fecha = date(spanDateFormat,strtotime($fecha));
	}
	return $fecha;
}

?>