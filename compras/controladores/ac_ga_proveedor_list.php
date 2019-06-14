<?php

function buscarProveedor($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

	$valBusq = sprintf("%s|%s|%s|%s",
		$idEmpresa,
		$frmBuscar['lstTipoPago'],
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstBancos($selId = "") {
	$objResponse = new xajaxResponse();
	
	$queryBanco = sprintf("SELECT * FROM bancos ORDER BY nombreBanco");
	$rsBanco = mysql_query($queryBanco);
	if (!$rsBanco) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstBanco\" name=\"lstBanco\" class=\"inputHabilitado\" style=\"width:99%\">";
	while ($rowBanco = mysql_fetch_assoc($rsBanco)) {
		$selected = ($selId == $rowBanco['idBanco']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$rowBanco['idBanco']."\">".utf8_encode($rowBanco['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstBanco","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstFormaPago($selId = "") {
	$objResponse = new xajaxResponse();
	
	$arrayFormaPago[] = "Abono en Cuenta";
	$arrayFormaPago[] = "Cheque";
	$arrayFormaPago[] = "Deposito";
	$arrayFormaPago[] = "Efectivo";
	$arrayFormaPago[] = "Transferencia";
	
	$html = "<select id=\"lstFormaPago\" name=\"lstFormaPago\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	foreach ($arrayFormaPago as $indice => $valor) {
		$selected = ($selId == $arrayFormaPago[$indice]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".utf8_encode($arrayFormaPago[$indice])."\">".utf8_encode($arrayFormaPago[$indice])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstFormaPago","innerHTML",$html);
	
	return $objResponse;
}

function cargalstMonedas($selId = "") {
	$objResponse = new xajaxResponse();
		
	$queryMoneda = sprintf("SELECT * FROM pg_monedas ORDER BY descripcion");
	$rsMoneda = mysql_query($queryMoneda);
	if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($rowMoneda = mysql_fetch_assoc($rsMoneda)) {
		$selected = ($selId == $rowMoneda['idmoneda']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$rowMoneda['idmoneda']."\">".utf8_encode($rowMoneda['descripcion'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstMoneda","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstPais($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_origen ORDER BY nom_origen");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstPais\" name=\"lstPais\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_origen']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_origen']."\">".utf8_encode($row['nom_origen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstPais","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstRetencion($selId = "") {
	$objResponse = new xajaxResponse();
	
	$queryRetencion = sprintf("SELECT * FROM te_retenciones ORDER BY id");
	$rsRetencion = mysql_query($queryRetencion);
	if (!$rsRetencion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstRetencion\" name=\"lstRetencion\" class=\"inputHabilitado\" style=\"width:99%\">";
	while ($rowRetencion = mysql_fetch_assoc($rsRetencion)) {
		$selected = ($selId == $rowRetencion['id']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$rowRetencion['id']."\">".utf8_encode($rowRetencion['descripcion'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstRetencion","innerHTML",$html);
	
	return $objResponse;
}

function formProveedor($idProveedor, $frmProveedor) {
	$objResponse = new xajaxResponse();
	
	if ($idProveedor > 0) {
		if (!xvalidaAcceso($objResponse,"cp_proveedor_list","editar")) { $objResponse->script("byId('btnCancelarProveedor').click();"); return $objResponse; }
		
		// BUSCA LOS DATOS DEL PROVEEDOR
		$queryProveedor = sprintf("SELECT *,
			IF(LENGTH(rif) > 0, CONCAT_WS('-', lrif, rif), NULL) AS rif_proveedor,
			IF(LENGTH(nit) > 0, CONCAT_WS('-', lnit, nit), NULL) AS nit_proveedor,
			IF(LENGTH(cicontacto) > 0, CONCAT_WS('-', lcicontacto, cicontacto), NULL) AS ci_contacto
		FROM cp_proveedor WHERE id_proveedor = %s",
			valTpDato($idProveedor, "int"));
		$rsProveedor = mysql_query($queryProveedor);
		if (!$rsProveedor) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProveedor = mysql_fetch_array($rsProveedor);
		
		$objResponse->assign("hddIdProveedor","value",$rowProveedor['id_proveedor']);
		
		$objResponse->call("selectedOption","lstTipoProveedor",$rowProveedor['credito']);
		$objResponse->call("selectedOption","lstContribuyente",$rowProveedor['contribuyente']);
		$objResponse->call("selectedOption","lstEstatus",$rowProveedor['status']);
		$objResponse->assign("txtNombreProveedor","value",utf8_encode($rowProveedor['nombre']));
		$objResponse->assign("txtCiRif","value",$rowProveedor['rif_proveedor']);
		$objResponse->assign("txtNit","value",$rowProveedor['nit_proveedor']);
		$objResponse->loadCommands(cargalstPais($rowProveedor['pais']));
		$objResponse->assign("txtEstado","value",$rowProveedor['estado']);
		$objResponse->assign("txtCiudad","value",utf8_encode($rowProveedor['ciudad']));
		$objResponse->assign("txtDireccion","value",utf8_encode($rowProveedor['direccion']));
		$objResponse->assign("txtTelefono","value",$rowProveedor['telefono']);
		$objResponse->assign("txtFax","value",$rowProveedor['fax']);
		$objResponse->assign("txtOtroTelf","value",$rowProveedor['otrotelf']);
		$objResponse->assign("txtEmail","value",utf8_encode($rowProveedor['correo']));
		$objResponse->call("selectedOption","lstTipoProveedorNacImp",$rowProveedor['nacioimpor']);
		$objResponse->call("selectedOption","lstTipo",$rowProveedor['tipo']);
		
		
		$objResponse->assign("txtNombreContacto","value",utf8_decode($rowProveedor['contacto']));
		$objResponse->assign("txtCedulaContacto","value",$rowProveedor['ci_contacto']);
		$objResponse->assign("txtTelefonoContacto","value",$rowProveedor['telfcontacto']);
		$objResponse->assign("txtCorreoContacto","value",utf8_encode($rowProveedor['correococtacto']));
		
		$objResponse->loadCommands(cargaLstFormaPago($rowProveedor['fpago']));
		$objResponse->assign("txtFechaCracion","value",date(spanDateFormat, strtotime($rowProveedor['fechacreacion'])));
		$objResponse->call("selectedOption","lstDescuento",$rowProveedor['descuento']);
		$objResponse->loadCommands(cargalstMonedas($rowProveedor['tipomoneda']));
		$objResponse->loadCommands(cargaLstBancos($rowProveedor['banco']));
		$objResponse->assign("txtSucursal","value",$rowProveedor['sucursal']);
		$objResponse->call("selectedOption","lstTipoCuenta",$rowProveedor['tipocuenta']);
		$objResponse->assign("txtNumeroCuenta","value",$rowProveedor['ncuenta']);
		
		$objResponse->assign("txtObservaciones","innerHTML",$rowProveedor['observaciones']);
		
		if ($rowProveedor['credito'] == 'Si') {
			$objResponse->script("byId('divProveedorCredito').style.display = '';");
			
			$queryProveedorCredito = sprintf("SELECT * FROM cp_prove_credito WHERE id_proveedor = %s",
				valTpDato($rowProveedor['id_proveedor'], "int"));
			$rsProveedorCredito = mysql_query($queryProveedorCredito);
			if (!$rsProveedorCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowProveedorCredito = mysql_fetch_array($rsProveedorCredito);
			
			$objResponse->call("selectedOption","lstDiasCredito",$rowProveedorCredito['diascredito']);
			$objResponse->assign("txtLimiteCredito","value",number_format($rowProveedorCredito['limitecredito'], 2, ".", ","));
			$objResponse->call("selectedOption","lstPlanMayor",$rowProveedorCredito['planMayor']);
			$objResponse->loadCommands(cargaLstRetencion($rowProveedorCredito['reimpuesto']));
			
			$objResponse->assign("d1","checked",(($rowProveedorCredito['d1'] == 1) ? "checked" : ""));
			$objResponse->assign("d2","checked",(($rowProveedorCredito['d2'] == 1) ? "checked" : ""));
			$objResponse->assign("d3","checked",(($rowProveedorCredito['d3'] == 1) ? "checked" : ""));
			$objResponse->assign("d4","checked",(($rowProveedorCredito['d4'] == 1) ? "checked" : ""));
			$objResponse->assign("d5","checked",(($rowProveedorCredito['d5'] == 1) ? "checked" : ""));
			$objResponse->assign("d6","checked",(($rowProveedorCredito['d6'] == 1) ? "checked" : ""));
			$objResponse->assign("d7","checked",(($rowProveedorCredito['d7'] == 1) ? "checked" : ""));
			$objResponse->assign("d8","checked",(($rowProveedorCredito['d8'] == 1) ? "checked" : ""));
			$objResponse->assign("d9","checked",(($rowProveedorCredito['d9'] == 1) ? "checked" : ""));
		} else {
			$objResponse->script("byId('divProveedorCredito').style.display = 'none';");
		}
	} else {
		if (!xvalidaAcceso($objResponse,"cp_proveedor_list","insertar")) { $objResponse->script("byId('btnCancelarProveedor').click();"); return $objResponse; }
			
		$objResponse->assign("txtFechaCracion","value",date(spanDateFormat));
		$objResponse->call("selectedOption","lstContribuyente","No");
		$objResponse->loadCommands(cargaLstPais());
		$objResponse->loadCommands(cargaLstFormaPago());
		$objResponse->loadCommands(cargalstMonedas());
		$objResponse->loadCommands(cargaLstBancos());
		$objResponse->loadCommands(cargaLstRetencion());
	
		$objResponse->script("byId('divProveedorCredito').style.display = 'none';");
	}
	
	return $objResponse;
}

function exportarProveedor($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

	$valBusq = sprintf("%s|%s|%s|%s",
		$idEmpresa,
		$frmBuscar['lstTipoPago'],
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/ga_provedor_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function guardarProveedor($frmProveedor, $frmListaProveedor) {
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	global $arrayValidarCI;
	global $arrayValidarRIF;
	global $arrayValidarNIT;

	@mysql_query('START TRANSACTION;');
	
	$idProveedor = $frmProveedor['hddIdProveedor'];
	
	$arrayValidar = $arrayValidarRIF;
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmProveedor['txtCiRif'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("byId('txtCiRif').className = 'inputErrado'");
			return $objResponse->alert(utf8_encode("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$arrayValidar = $arrayValidarNIT;
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmProveedor['txtNit'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("byId('txtNit').className = 'inputErrado'");
			return $objResponse->alert(utf8_encode("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$arrayValidar = array_merge($arrayValidarCI, $arrayValidarRIF, $arrayValidarNIT);
	if (isset($arrayValidar)) {
		if (strlen($frmProveedor['txtCedulaContacto']) > 0) {
			$valido = false;
			foreach ($arrayValidar as $indice => $valor) {
				if (preg_match($valor, $frmProveedor['txtCedulaContacto'])) {
					$valido = true;
				}
			}
			
			if ($valido == false) {
				$objResponse->script("byId('txtCedulaContacto').className = 'inputErrado'");
				return $objResponse->alert(utf8_encode("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
			}
		}
	}
	
	$txtCiProveedor = explode("-",$frmProveedor['txtCiRif']);
	if (is_numeric($txtCiProveedor[0]) == true) {
		$txtCiProveedor = implode("-",$txtCiProveedor);
	} else {
		$txtLciProveedor = $txtCiProveedor[0];
		array_shift($txtCiProveedor);
		$txtCiProveedor = implode("-",$txtCiProveedor);
	}
	
	$txtNITProveedor = explode("-",$frmProveedor['txtNit']);
	if (is_numeric($txtNITProveedor[0]) == true) {
		$txtNITProveedor = implode("-",$txtNITProveedor);
	} else {
		$txtLNITProveedor = $txtNITProveedor[0];
		array_shift($txtNITProveedor);
		$txtNITProveedor = implode("-",$txtNITProveedor);
	}
	
	$txtCedulaContacto = explode("-",$frmProveedor['txtCedulaContacto']);
	if (is_numeric($txtCedulaContacto[0]) == true) {
		$txtCedulaContacto = implode("-",$txtCedulaContacto);
	} else {
		$txtLciContacto = $txtCedulaContacto[0];
		array_shift($txtCedulaContacto);
		$txtCedulaContacto = implode("-",$txtCedulaContacto);
	}
	
	// VERIFICA QUE NO EXISTA LA CEDULA
	$query = sprintf("SELECT * FROM cp_proveedor
	WHERE ((lrif IS NULL AND %s IS NULL AND rif LIKE %s)
			OR (lrif IS NOT NULL AND lrif LIKE %s AND rif LIKE %s))
		AND (id_proveedor <> %s OR %s IS NULL)
		AND status = 'Activo';",
		valTpDato($txtLciProveedor, "text"),
		valTpDato($txtCiProveedor, "text"),
		valTpDato($txtLciProveedor, "text"),
		valTpDato($txtCiProveedor, "text"),
		valTpDato($idProveedor, "int"),
		valTpDato($idProveedor, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0) {
		return $objResponse->alert("Ya existe la ".$spanProvCxP." ingresada");
	}
	
	if ($frmProveedor['hddIdProveedor'] != "") {
		if (!xvalidaAcceso($objResponse,"cp_proveedor_list_contado","editar")
		&& !xvalidaAcceso($objResponse,"cp_proveedor_list_credito","editar")) { return $objResponse; }
		
		$updateSQL = sprintf ("UPDATE cp_proveedor SET
			tipo2 = %s,
			lrif = %s,
			rif = %s,
			nombre = %s,
			lnit = %s,
			nit = %s,
			pais = %s,
			estado = %s,
			ciudad = %s,
			telefono = %s,
			direccion = %s,
			correo = %s,
			fax = %s,
			contacto = %s,
			otrotelf = %s,
			lcicontacto = %s,
			cicontacto = %s,
			contribuyente = '%s',
			telfcontacto = '%s',
			nacioimpor = '%s',
			correococtacto = '%s',
			tipo = '%s',
			status = %s,
			fechacreacion = '%s',
			fpago = '%s',
			tipomoneda = '%s',
			descuento = '%s',
			banco = '%s',
			sucursal = '%s',
			tipocuenta = '%s',
			ncuenta = '%s',
			observaciones = '%s',
			credito = '%s'
		WHERE id_proveedor = '%s'",
			valTpDato($frmProveedor['lstTipoProveedor'], "text"),
			valTpDato($txtLciProveedor, "text"),
			valTpDato($txtCiProveedor, "text"),
			valTpDato($frmProveedor['txtNombreProveedor'], "text"),
			valTpDato($txtLNITProveedor, "text"),
			valTpDato($txtNITProveedor, "text"),
			valTpDato($frmProveedor['lstPais'], "int"),
			valTpDato($frmProveedor['txtEstado'], "text"),
			valTpDato($frmProveedor['txtCiudad'], "text"),
			valTpDato($frmProveedor['txtTelefono'], "text"),
			valTpDato($frmProveedor['txtDireccion'], "text"),
			valTpDato($frmProveedor['txtEmail'], "text"),
			valTpDato($frmProveedor['txtFax'], "text"),
			valTpDato($frmProveedor['txtNombreContacto'], "text"),
			valTpDato($frmProveedor['txtOtroTelf'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCedulaContacto, "text"),
			$frmProveedor['lstContribuyente'],
			$frmProveedor['txtTelefonoContacto'],
			$frmProveedor['lstTipoProveedorNacImp'],
			$frmProveedor['txtCorreoContacto'],
			$frmProveedor['lstTipo'],
			valTpDato($frmProveedor['lstEstatus'], "text"),
			date("Y-m-d", strtotime($frmProveedor['txtFechaCracion'])),
			$frmProveedor['lstFormaPago'],
			$frmProveedor['lstMoneda'],
			$frmProveedor['lstDescuento'],
			$frmProveedor['lstBanco'],
			$frmProveedor['txtSucursal'],
			$frmProveedor['lstTipoCuenta'],
			$frmProveedor['txtNumeroCuenta'],
			$frmProveedor['txtObservaciones'],
			$frmProveedor['lstTipoProveedor'],
			$frmProveedor['hddIdProveedor']);
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		if ($frmProveedor['lstTipoProveedor'] == "Si") {
			$queryProvCred = sprintf("SELECT * FROM cp_prove_credito
			WHERE id_proveedor = %s",
				valTpDato($idProveedor, "int"));
			$rsProvCred = mysql_query($queryProvCred);
			if (!$rsProvCred) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowProvCred = mysql_fetch_assoc($rsProvCred);
			
			if ($rowProvCred['id_credito'] > 0) {
				if (!xvalidaAcceso($objResponse,"cp_proveedor_list_credito","editar")) { return $objResponse; }
				
				$updateSQL = sprintf("UPDATE cp_prove_credito SET
					diascredito = %s,
					limitecredito = %s,
					saldoDisponible = %s,
					d1 = '%s',
					d2 = '%s',
					d3 = '%s',
					d4 = '%s',
					d5 = '%s',
					d6 = '%s',
					d7 = '%s',
					d8 = '%s',
					d9 = '%s',
					reimpuesto = %s,
					planMayor = %s
				WHERE id_proveedor = %s",
					valTpDato($frmProveedor['lstDiasCredito'], "int"),
					valTpDato($frmProveedor['txtLimiteCredito'], "real_inglesa"),
					valTpDato($frmProveedor['txtLimiteCredito'], "real_inglesa"),
					$frmProveedor['d1'],
					$frmProveedor['d2'],
					$frmProveedor['d3'],
					$frmProveedor['d4'],
					$frmProveedor['d5'],
					$frmProveedor['d6'],
					$frmProveedor['d7'],
					$frmProveedor['d8'],
					$frmProveedor['d9'],
					valTpDato($frmProveedor['lstRetencion'], "int"),
					valTpDato($frmProveedor['lstPlanMayor'],"boolean"),
					valTpDato($idProveedor, "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			} else {
				if (!xvalidaAcceso($objResponse,"cp_proveedor_list_credito","insertar")) { return $objResponse; }
				
				$insertSQL = sprintf("INSERT INTO cp_prove_credito (id_proveedor, diascredito, limitecredito, saldoDisponible, diapago, d1, d2, d3, d4, d5, d6, d7, d8, d9, reimpuesto, planMayor) VALUE (%s, %s, %s, %s, %s, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s);",
					valTpDato($idProveedor, "int"),
					valTpDato($frmProveedor['lstDiasCredito'], "int"),
					valTpDato($frmProveedor['txtLimiteCredito'], "real_inglesa"),
					valTpDato($frmProveedor['txtLimiteCredito'], "real_inglesa"),
					valTpDato(" ","text"),
					$frmProveedor['d1'],
					$frmProveedor['d2'],
					$frmProveedor['d3'],
					$frmProveedor['d4'],
					$frmProveedor['d5'],
					$frmProveedor['d6'],
					$frmProveedor['d7'],
					$frmProveedor['d8'],
					$frmProveedor['d9'],
					valTpDato($frmProveedor['lstRetencion'], "int"),
					valTpDato($frmProveedor['lstPlanMayor'],"boolean"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
			
			if ($frmProveedor['lstPlanMayor'] == 1) {
				$queryProvCredFormaPago = sprintf("SELECT * FROM formapagoasignacion forma_pago_asig
				WHERE idProveedor = %s;",
					valTpDato($idProveedor, "int"));
				$rsProvCredFormaPago = mysql_query($queryProvCredFormaPago);
				if (!$rsProvCredFormaPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsProvCredFormaPago = mysql_num_rows($rsProvCredFormaPago);
				$rowProvCredFormaPago = mysql_fetch_assoc($rsProvCredFormaPago);
				
				if ($totalRowsProvCredFormaPago == 0) {
					$insertSQL = sprintf("INSERT INTO formapagoasignacion (descripcionFormaPagoAsignacion, idProveedor, alias)
					VALUE (%s, %s, %s);",
						valTpDato("Plan Mayor","text"),
						valTpDato($idProveedor, "int"),
						valTpDato("PM","text"));
					mysql_query("SET NAMES 'utf8'");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			} else {
				// ELIMINA EL PLAN DE PAGO "PLAN MAYOR" DEBIDO A QUE EL PROVEEDOR YA NO ES DE PLAN MAYOR
				$deleteSQL = sprintf("DELETE FROM formapagoasignacion WHERE idProveedor = %s;",
					valTpDato($idProveedor, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		} else {
			// ELIMINA EL PLAN DE PAGO "PLAN MAYOR" DEBIDO A QUE EL PROVEEDOR YA NO ES A CRÉDITO
			$deleteSQL = sprintf("DELETE FROM formapagoasignacion WHERE idProveedor = %s;",
				valTpDato($idProveedor, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	} else {
		if (!xvalidaAcceso($objResponse,"cp_proveedor_list_contado","insertar")
		&& !xvalidaAcceso($objResponse,"cp_proveedor_list_credito","insertar")) { return $objResponse; }
					           
		$insertSQL = sprintf ("INSERT INTO cp_proveedor(tipo2, lrif, rif, nombre, lnit, nit, pais, estado, ciudad, telefono, direccion, correo, fax, contacto, otrotelf, lcicontacto, cicontacto, contribuyente, telfcontacto, nacioimpor, correococtacto, tipo, status, fechacreacion, fpago, tipomoneda, descuento, banco, sucursal, tipocuenta, ncuenta, observaciones, credito)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, '%s', '%s', '%s', '%s','%s', %s, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
			valTpDato($frmProveedor['lstTipoProveedor'], "text"),
			valTpDato($txtLciProveedor, "text"),
			valTpDato($txtCiProveedor, "text"),
			valTpDato($frmProveedor['txtNombreProveedor'], "text"),
			valTpDato($txtLNITProveedor, "text"),
			valTpDato($txtNITProveedor, "text"),
			valTpDato($frmProveedor['lstPais'], "int"),
			valTpDato($frmProveedor['txtEstado'], "text"),
			valTpDato($frmProveedor['txtCiudad'], "text"),
			valTpDato($frmProveedor['txtTelefono'], "text"),
			valTpDato($frmProveedor['txtDireccion'], "text"),
			valTpDato($frmProveedor['txtEmail'], "text"),
			valTpDato($frmProveedor['txtFax'], "text"),
			valTpDato($frmProveedor['txtNombreContacto'], "text"),
			valTpDato($frmProveedor['txtOtroTelf'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCedulaContacto, "text"),
			$frmProveedor['lstContribuyente'],
			$frmProveedor['txtTelefonoContacto'],
			$frmProveedor['lstTipoProveedorNacImp'],
			$frmProveedor['txtCorreoContacto'],
			$frmProveedor['lstTipo'],
			valTpDato($frmProveedor['lstEstatus'], "text"),
			date("Y-m-d", strtotime($frmProveedor['txtFechaCracion'])),
			$frmProveedor['lstFormaPago'],
			$frmProveedor['lstMoneda'],
			$frmProveedor['lstDescuento'],
			$frmProveedor['lstBanco'],
			$frmProveedor['txtSucursal'],
			$frmProveedor['lstTipoCuenta'],
			$frmProveedor['txtNumeroCuenta'],
			$frmProveedor['txtObservaciones'],
			$frmProveedor['lstTipoProveedor']);
		mysql_query("SET NAMES 'utf8'"); 
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idProveedor = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		if ($frmProveedor['lstTipoProveedor'] == "Si") { // SI ES CREDITO
			if (!xvalidaAcceso($objResponse,"cp_proveedor_list_credito","insertar")) { return $objResponse; }
			
			$queryProveedoresCredito = sprintf ("INSERT INTO cp_prove_credito(id_proveedor, diascredito, limitecredito, saldoDisponible, d1, d2, d3, d4, d5, d6, d7, d8, d9, reimpuesto, planMayor)
			VALUE (%s, %s, %s, %s, '%s','%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
				valTpDato($idProveedor, "int"),
				valTpDato($frmProveedor['lstDiasCredito'], "real_inglesa"),
				valTpDato($frmProveedor['txtLimiteCredito'], "real_inglesa"),
				valTpDato($frmProveedor['txtLimiteCredito'], "real_inglesa"),
				$frmProveedor['d1'],
				$frmProveedor['d2'],
				$frmProveedor['d3'],
				$frmProveedor['d4'],
				$frmProveedor['d5'],
				$frmProveedor['d6'],
				$frmProveedor['d7'],
				$frmProveedor['d8'],
				$frmProveedor['d9'],
				$frmProveedor['lstRetencion'],
				$frmProveedor['lstPlanMayor']);
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($queryProveedoresCredito);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			if ($frmProveedor['lstPlanMayor'] == 1) {
				$insertSQL = sprintf("INSERT INTO formapagoasignacion (descripcionFormaPagoAsignacion, idProveedor, alias)
				VALUE (%s, %s, %s);",
					valTpDato("Plan Mayor","text"),
					valTpDato($idProveedor, "int"),
					valTpDato("PM","text"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	@mysql_query('COMMIT;');
	
	$objResponse->alert(utf8_encode("Proveedor Guardado con Éxito"));
	
	$objResponse->script("byId('btnCancelarProveedor').click();");
	
	$objResponse->loadCommands(listaProveedor(
		$frmListaProveedor['pageNum'],
		$frmListaProveedor['campOrd'],
		$frmListaProveedor['tpOrd'],
		$frmListaProveedor['valBusq']));
	
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanProvCxP;
	
	$arrayTipoPago = array("NO" => "Contado", "SI" => "Crédito");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("prov.credito LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && strlen($valCadBusq[1]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(prov.status LIKE %s
		OR prov.status IS NULL AND %s IS NULL)",
			valTpDato($valCadBusq[2], "text"),
			valTpDato($valCadBusq[2], "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR CONCAT_WS('', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT
		prov.id_proveedor,
		prov.tipo,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif,
		prov.nombre,
		prov.telefono,
		prov.correo,
		prov.credito,
		prov.status,
		prov.direccion,
		pais.nom_origen AS pais_prov
	FROM cp_proveedor prov
		INNER JOIN an_origen pais ON (prov.pais = pais.id_origen) %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "8%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "32%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "telefono", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Teléfono"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "24%", $pageNum, "correo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Correo"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['status']) {
			case "Activo" :	$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_proveedor'])."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telefono']."</td>";
			$htmlTb .= "<td><a class=\"linkAzulUnderline\" href=\"mailto:".utf8_encode($row['correo'])."\">".utf8_encode($row['correo'])."</a></td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(strtoupper($arrayTipoPago[strtoupper($row['credito'])]))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblProveedor', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_proveedor']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function tipoProveedor($val){
	$objResponse = new xajaxResponse();
	
	if ($val == "Si") {
		$objResponse->script("byId('divProveedorCredito').style.display = '';");
		
		$objResponse->assign("tdProveedoresBotones","innerHTML",$html);
		
	} else if ($val == "No") {
		$objResponse->script("byId('divProveedorCredito').style.display = 'none';");
		
		$objResponse->assign("tdProveedoresBotones","innerHTML",$html);
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarProveedor");

$xajax->register(XAJAX_FUNCTION,"cargaLstBancos");
$xajax->register(XAJAX_FUNCTION,"cargaLstFormaPago");
$xajax->register(XAJAX_FUNCTION,"cargalstMonedas");
$xajax->register(XAJAX_FUNCTION,"cargaLstPais");
$xajax->register(XAJAX_FUNCTION,"cargaLstRetencion");

$xajax->register(XAJAX_FUNCTION,"formProveedor");
$xajax->register(XAJAX_FUNCTION,"exportarProveedor");
$xajax->register(XAJAX_FUNCTION,"guardarProveedor");

$xajax->register(XAJAX_FUNCTION,"listaProveedor");

$xajax->register(XAJAX_FUNCTION,"tipoProveedor");

function checked($value){
	$checked = ($value == 1) ? "checked=\"checked\"" : '';

	return $checked;
}
?>