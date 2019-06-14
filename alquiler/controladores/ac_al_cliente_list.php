<?php


function asignarCredito($frmCredito) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_clientes_credito","editar")) { return $objResponse; }
	
	$hddNumeroItm = $frmCredito['hddNumeroItm'];
	
	$objResponse->assign("txtDiasCredito".$hddNumeroItm,"value",number_format(str_replace(",","",$frmCredito['txtDiasCredito']),0,".",","));
	$objResponse->assign("txtLimiteCredito".$hddNumeroItm,"value",number_format(str_replace(",","",$frmCredito['txtLimiteCredito']),2,".",","));
	$objResponse->assign("txtFormaPago".$hddNumeroItm,"value",$frmCredito['lstFormaPago']);
	
	$objResponse->script("byId('btnCancelarCredito').click();");
	
	return $objResponse;
}

function buscarCliente($frmBuscar){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstTipoPago'],
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['lstPagaImpuesto'],
		$frmBuscar['lstModulo'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listaEmpresa(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularEdad($fechaNacimiento){
	$objResponse = new xajaxResponse();
	
	if($fechaNacimiento != ""){
		$y = date("Y", strtotime($fechaNacimiento));
		$m = date("m", strtotime($fechaNacimiento));
		$d = date("d", strtotime($fechaNacimiento));
		
		$edad = date("Y") - $y -1;
		
		if(date("m") > $m){
			$edad = $edad + 1;
		}else if(date("m") == $m){
			if(date("d") >= $d){
				$edad = $edad + 1;
			}
		}
		
		
		$edad = "Edad: ".$edad;
		
		//$date = new DateTime($fechaNacimiento);
		//$now = new DateTime('today');
		//$interval = $now->diff($date);
		//$calculoEdad = "Edad: ".$interval->y;		
	}
	
	$objResponse->assign("spanEdad", "innerHTML", $edad);
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Débito, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCredito($selId = "") {
	$objResponse = new xajaxResponse();
	
	$arrayDetCredito[0] = "1";
	$arrayDetCredito[1] = "Contado";
	$arrayCredito[] = $arrayDetCredito;
	$arrayDetCredito[0] = "0";
	$arrayDetCredito[1] = "Crédito";
	$arrayCredito[] = $arrayDetCredito;
	
	if ($selId == "0") { // 0 = Crédito
		$onChange = sprintf("selectedOption('lstCredito', 0);");
	} else if ($selId == "1") { // 1 = Contado
		$onChange = sprintf("selectedOption('lstCredito', 1);");
	}
	$onChange .= sprintf("xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', '3', this.value, '1', '%s');",
		"19"); // 19 = Mostrador Público Contado
	
	$html = "<select id=\"lstCredito\" name=\"lstCredito\" onchange=\"".$onChange."\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($arrayCredito as $indice => $valor) {
		$selected = ($selId == $arrayCredito[$indice][0]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$arrayCredito[$indice][0]."\">".$arrayCredito[$indice][1]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCredito","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstFormaPago($selId = "") {
	$objResponse = new xajaxResponse();
	
	$array[] = "Cheque";			$array[] = "Abono en Cuenta";	$array[] = "Transferencia";		$array[] = "Deposito";
	$array[] = "Efectivo";
	
	$html = "<select id=\"lstFormaPago\" name=\"lstFormaPago\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstFormaPago","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['descripcionModulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['descripcionModulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function eliminarClienteEmpresa($frmCliente){
	$objResponse = new xajaxResponse();
	
	if (isset($frmCliente['cbxItm'])) {
		foreach($frmCliente['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
		$objResponse->script("xajax_eliminarClienteEmpresa(xajax.getFormValues('frmCliente'));");
	}
	
	return $objResponse;
}

function exportarCliente($frmBuscar) {
	$objResponse = new xajaxResponse();
		
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstTipoPago'],
		$frmBuscar['lstEstatusBuscar'],
		$frmBuscar['lstPagaImpuesto'],
		$frmBuscar['lstModulo'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('../repuestos/reportes/iv_cliente_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formCliente($idCliente, $frmCliente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmCliente['cbx'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	if ($idCliente > 0) {
		if (!xvalidaAcceso($objResponse,"cc_cliente_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarCliente').click();"); return $objResponse; }
		
		// BUSCA LOS DATOS DEL CLIENTE
		$query = sprintf("SELECT *,
			(CASE
				WHEN lci IS NOT NULL THEN
					CONCAT_WS('-',lci,ci)
				ELSE 
					ci
			END) AS ci_cliente,
			
			(CASE
				WHEN lci2 IS NOT NULL THEN
					CONCAT_WS('-',lci2,cicontacto)
				ELSE 
					cicontacto
			END) AS ci_contacto,
			
			(SELECT perfil.fecha_nacimiento 
				FROM crm_perfil_prospecto perfil 
				WHERE perfil.id = cj_cc_cliente.id) AS fecha_nacimiento
		FROM cj_cc_cliente
		WHERE id = %s;",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdCliente","value",$row['id']);
		
		$tipoPago = ($row['credito'] == "si") ? "0" : "1";
		$objResponse->loadCommands(cargaLstCredito($tipoPago));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", $tipoPago, "1", $row['id_clave_movimiento_predeterminado']));
		
		$objResponse->script("selectedOption('lstTipo', '".$row['tipo']."')");
		$objResponse->script("byId('lstTipo').onchange = function() { selectedOption(this.id, '".$row['tipo']."'); }");
		$objResponse->assign("txtCedula","value",$row['ci_cliente']);
		$objResponse->assign("txtNombre","value",utf8_encode($row['nombre']));
		$objResponse->assign("txtNit","value",$row['nit']);
		$objResponse->assign("txtApellido","value",utf8_encode($row['apellido']));
		$objResponse->script("selectedOption('lstContribuyente', '".$row['contribuyente']."')");
		$objResponse->assign("txtEstado","value",utf8_encode($row['estado']));
		$objResponse->assign("txtCiudad","value",utf8_encode($row['ciudad']));
		
		$arrayDireccion = explode(";",utf8_encode($row['direccion']));
		$objResponse->assign("txtUrbanizacion","value",trim($arrayDireccion[0]));
		$objResponse->assign("txtCalle","value",trim($arrayDireccion[1]));
		$objResponse->assign("txtCasa","value",trim($arrayDireccion[2]));
		$objResponse->assign("txtMunicipio","value",trim($arrayDireccion[3]));
		
		$objResponse->assign("txtTelefono","value",$row['telf']);
		$objResponse->assign("txtOtroTelefono","value",$row['otrotelf']);
		$objResponse->assign("txtCorreo","value",$row['correo']);
		
		$objResponse->script("selectedOption('lstReputacionCliente', '".$row['reputacionCliente']."');");
		$objResponse->script("selectedOption('lstTipoCliente', '".$row['tipocliente']."');");
		$objResponse->script("selectedOption('lstDescuento', '".$row['descuento']."');");
		$objResponse->script("
		byId('lstDescuento').onchange = function() {
			selectedOption(this.id, ".$row['descuento'].");
		}");
		$objResponse->script("selectedOption('lstEstatus', '".$row['status']."');");
		$objResponse->script("byId('cbxPagaImpuesto').checked = ".(($row['paga_impuesto'] == "1") ? 'true' : 'false'));
		$objResponse->script("byId('cbxBloquearVenta').checked = ".(($row['bloquea_venta'] == "1") ? 'true' : 'false'));
				
		$objResponse->assign("txtLicencia","value",$row["licencia"]);
		$objResponse->assign("txtFechaExpiracionLicencia","value",date(spanDateFormat,strtotime($row['fecha_expiracion_licencia'])));
		$objResponse->assign("txtFechaNacimiento","value",date(spanDateFormat,strtotime($row['fecha_nacimiento'])));
				
		$objResponse->assign("txtFechaCreacion","value",date(spanDateFormat,strtotime($row['fcreacion'])));
		$objResponse->assign("txtFechaDesincorporar","value",date(spanDateFormat,strtotime($row['fdesincorporar'])));
		
		$objResponse->assign("txtCedulaContacto","value",$row['ci_contacto']);
		$objResponse->assign("txtNombreContacto","value",utf8_encode($row['contacto']));
		$objResponse->assign("txtTelefonoContacto","value",$row['telfcontacto']);
		$objResponse->assign("txtCorreoContacto","value",$row['correocontacto']);
		
		$queryClienteEmpresa = sprintf("SELECT * FROM cj_cc_cliente_empresa cliente_emp
		WHERE cliente_emp.id_cliente = %s
		ORDER BY cliente_emp.id_empresa ASC;",
			valTpDato($idCliente, "int"));
		$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
		if (!$rsClienteEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClienteEmpresa = mysql_fetch_array($rsClienteEmpresa)) {
			$Result1 = insertarItemClienteEmpresa($contFila, $rowClienteEmpresa['id_cliente_empresa']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
	} else {
		if (!xvalidaAcceso($objResponse,"cc_cliente_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarCliente').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstCredito("1"));
		$objResponse->call("selectedOption","lstContribuyente","No");
		$objResponse->call("selectedOption","lstEstatus","Activo");
		$objResponse->script("byId('lstTipo').onchange = function() { }");
		$objResponse->assign("txtFechaCreacion","value",date(spanDateFormat));
		$objResponse->assign("txtFechaDesincorporar","value",date(spanDateFormat,dateAddLab(strtotime(date(spanDateFormat)),364,false)));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", "19"));		
		$objResponse->call("selectedOption","lstDescuento",0);
		$objResponse->script("
		byId('lstDescuento').onchange = function() {
			selectedOption(this.id, ".(0).");
		}");
		$objResponse->call("selectedOption","lstTipoCliente","Vehiculos");
		$objResponse->call("selectedOption","lstReputacionCliente","CLIENTE B");
		
		$objResponse->script("xajax_insertarClienteEmpresa(".$idEmpresa.", xajax.getFormValues('frmCliente'));");
	}
	
	$objResponse->script("xajax_calcularEdad(byId('txtFechaNacimiento').value)");
	return $objResponse;
}

function formCredito($hddNumeroItm, $frmCliente) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_clientes_credito")) { $objResponse->script("byId('btnCancelarCredito').click();"); return $objResponse; }
	
	if ($frmCliente['lstCredito'] != "0") {
		sleep(1);
		$objResponse->alert("Tipo de cliente inválido para esta acción");
		$objResponse->script("byId('btnCancelarCredito').click();");
		return $objResponse;
	}
	
	$objResponse->assign("hddNumeroItm","value",$hddNumeroItm);
	$objResponse->assign("txtDiasCredito","value",$frmCliente['txtDiasCredito'.$hddNumeroItm]);
	$objResponse->assign("txtLimiteCredito","value",$frmCliente['txtLimiteCredito'.$hddNumeroItm]);
	$objResponse->loadCommands(cargaLstFormaPago($frmCliente['txtFormaPago'.$hddNumeroItm]));
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	return $objResponse;
}

function guardarCliente($frmCliente, $frmListaCliente) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	global $arrayValidarCI;
	global $arrayValidarRIF;
	global $arrayValidarNIT;
	global $spanEstado;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmCliente['cbx'];
	
	mysql_query("START TRANSACTION;");
	
	$idCliente = $frmCliente['hddIdCliente'];
	
	switch($frmCliente['lstTipo']) {
		case 1 :
			$lstTipo = "Natural";
			$arrayValidar = $arrayValidarCI;
			break;
		case 2 :
			$lstTipo = "Juridico";
			$arrayValidar = $arrayValidarRIF;
			break;
	}
	
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmCliente['txtCedula'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("byId('txtCedula').className = 'inputErrado'");
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$arrayValidar = $arrayValidarNIT;
	if (isset($arrayValidar)) {
		if (strlen($frmCliente['txtNit']) > 0) {
			$valido = false;
			foreach ($arrayValidar as $indice => $valor) {
				if (preg_match($valor, $frmCliente['txtNit'])) {
					$valido = true;
				}
			}
			
			if ($valido == false) {
				$objResponse->script("byId('txtNit').className = 'inputErrado'");
				return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
			}
		}
	}
	
	$arrayValidar = array_merge($arrayValidarCI, $arrayValidarRIF);
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmCliente['txtCedulaContacto'])) {
				$valido = true;
			}
		}
		
		if ($valido == false && strlen($frmCliente['txtCedulaContacto']) > 0) {
			$objResponse->script("byId('txtCedulaContacto').className = 'inputErrado'");
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$txtCiCliente = explode("-", $frmCliente['txtCedula']);
	if (is_numeric($txtCiCliente[0]) == true) {
		$txtCiCliente = implode("-",$txtCiCliente);
	} else {
		$txtLciCliente = $txtCiCliente[0];
		array_shift($txtCiCliente);
		$txtCiCliente = implode("-",$txtCiCliente);
	}
	
	$txtCiContacto = explode("-", $frmCliente['txtCedulaContacto']);
	if (is_numeric($txtCiContacto[0]) == true) {
		$txtCiContacto = implode("-",$txtCiContacto);
	} else {
		$txtLciContacto = $txtCiContacto[0];
		array_shift($txtCiContacto);
		$txtCiContacto = implode("-",$txtCiContacto);
	}
	
	// VERIFICA QUE NO EXISTA LA CEDULA
	$query = sprintf("SELECT * FROM cj_cc_cliente
	WHERE ((lci IS NULL AND %s IS NULL AND ci LIKE %s)
			OR (lci IS NOT NULL AND lci LIKE %s AND ci LIKE %s))
		AND (id <> %s OR %s IS NULL);",
		valTpDato($txtLciCliente, "text"),
		valTpDato($txtCiCliente, "text"),
		valTpDato($txtLciCliente, "text"),
		valTpDato($txtCiCliente, "text"),
		valTpDato($idCliente, "int"),
		valTpDato($idCliente, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0) {
		return $objResponse->alert("Ya existe la ".$spanClienteCxC." ingresada");
	}
	
	$frmCliente['txtUrbanizacion'] = trim(str_replace(",", "", $frmCliente['txtUrbanizacion']));
	$frmCliente['txtCalle'] = trim(str_replace(",", "", $frmCliente['txtCalle']));
	$frmCliente['txtCasa'] = trim(str_replace(",", "", $frmCliente['txtCasa']));
	$frmCliente['txtMunicipio'] = trim(str_replace(",", "", $frmCliente['txtMunicipio']));
	$frmCliente['txtCiudad'] = trim(str_replace(",", "", $frmCliente['txtCiudad']));
	$frmCliente['txtEstado'] = trim(str_replace(",", "", $frmCliente['txtEstado']));
	
	$txtDireccion = implode("; ", array(
		$frmCliente['txtUrbanizacion'],
		$frmCliente['txtCalle'],
		$frmCliente['txtCasa'],
		$frmCliente['txtMunicipio'],
		$frmCliente['txtCiudad'],
		((strlen($frmCliente['txtEstado']) > 0) ? $spanEstado : "")." ".$frmCliente['txtEstado']));
	
	$lstCredito = ($frmCliente['lstCredito'] == "0") ? "si" : "no";
	$cbxPagaImpuesto = (isset($frmCliente['cbxPagaImpuesto'])) ? 1 : 0;
	$cbxBloquearVenta = (isset($frmCliente['cbxBloquearVenta'])) ? 1 : 0;

	if ($idCliente > 0) {
		if (!((($frmCliente['lstCredito'] == "0" || $frmCliente['lstCredito'] == "Si") && xvalidaAcceso($objResponse,"cc_clientes_credito","editar"))
		|| (($frmCliente['lstCredito'] != "0" || $frmCliente['lstCredito'] != "Si") && xvalidaAcceso($objResponse,"cc_clientes_contado","editar")))) {
			return $objResponse;
		}
		
		$updateSQL = sprintf("UPDATE cj_cc_cliente SET
			tipo = %s,
			nombre = %s,
			apellido = %s,
			lci = %s,
			ci = %s,
			nit = %s,
			contribuyente = %s,
			urbanizacion = %s,
			calle = %s,
			casa = %s,
			municipio = %s,
			ciudad = %s,
			estado = %s,
			direccion = %s,
			telf = %s,
			otrotelf = %s,
			correo = %s,
			contacto = %s,
			lci2 = %s,
			cicontacto = %s,
			telfcontacto = %s,
			correocontacto = %s,
			reputacionCliente = %s,
			descuento = %s,
			fcreacion = %s,
			status = %s,
			credito = %s,
			tipocliente = %s,
			fdesincorporar = %s,
			id_clave_movimiento_predeterminado = %s,
			licencia = %s,
			fecha_expiracion_licencia = %s,
			paga_impuesto = %s,
			bloquea_venta = %s,
			tipo_cuenta_cliente = %s
		WHERE id = %s;",
			valTpDato($lstTipo, "text"),
			valTpDato($frmCliente['txtNombre'], "text"),
			valTpDato($frmCliente['txtApellido'], "text"),
			valTpDato($txtLciCliente, "text"),
			valTpDato($txtCiCliente, "text"),
			valTpDato($frmCliente['txtNit'], "text"),
			valTpDato($frmCliente['lstContribuyente'], "text"),
			valTpDato($frmCliente['txtUrbanizacion'], "text"),
			valTpDato($frmCliente['txtCalle'], "text"),
			valTpDato($frmCliente['txtCasa'], "text"),
			valTpDato($frmCliente['txtMunicipio'], "text"),
			valTpDato($frmCliente['txtCiudad'], "text"),
			valTpDato($frmCliente['txtEstado'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmCliente['txtTelefono'], "text"),
			valTpDato($frmCliente['txtOtroTelefono'], "text"),
			valTpDato($frmCliente['txtCorreo'], "text"),
			valTpDato($frmCliente['txtNombreContacto'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCiContacto, "text"),
			valTpDato($frmCliente['txtTelefonoContacto'], "text"),
			valTpDato($frmCliente['txtCorreoContacto'], "text"),
			valTpDato($frmCliente['lstReputacionCliente'], "text"),
			valTpDato($frmCliente['lstDescuento'], "text"),
			valTpDato(date("Y-m-d",strtotime($frmCliente['txtFechaCreacion'])),"date"),
			valTpDato($frmCliente['lstEstatus'], "text"),
			valTpDato($lstCredito, "text"),
			valTpDato($frmCliente['lstTipoCliente'], "text"),
			valTpDato(date("Y-m-d",strtotime($frmCliente['txtFechaDesincorporar'])),"date"),
			valTpDato($frmCliente['lstClaveMovimiento'], "int"),
			valTpDato($frmCliente['txtLicencia'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaExpiracionLicencia'])), "date"),
			valTpDato($cbxPagaImpuesto, "int"),
			valTpDato($cbxBloquearVenta, "int"),
			valTpDato(2, "int"), // 1 = Prospecto, 2 = Cliente
			valTpDato($idCliente, "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		$query = sprintf("SELECT * FROM crm_perfil_prospecto WHERE id = %s",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$tienePerfil = mysql_num_rows($rs);
		
		if($tienePerfil){
			$insertSQL = sprintf("UPDATE crm_perfil_prospecto SET fecha_nacimiento = %s 
				WHERE id = %s",
				valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaNacimiento'])), "date"),
				valTpDato($idCliente, "int"));				
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}else{
			if($frmCliente['txtFechaNacimiento'] != ""){
				$insertSQL = sprintf("INSERT INTO crm_perfil_prospecto (id, fecha_nacimiento) 
					VALUES (%s, %s)",
					valTpDato($idCliente, "int"),
					valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaNacimiento'])), "date"));				
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}		
	} else {
		if (!((($frmCliente['lstCredito'] == "0" || $frmCliente['lstCredito'] == "Si") && xvalidaAcceso($objResponse,"cc_clientes_credito","insertar"))
		|| (($frmCliente['lstCredito'] != "0" || $frmCliente['lstCredito'] != "Si") && xvalidaAcceso($objResponse,"cc_clientes_contado","insertar")))) {
			return $objResponse;
		}
		
		$insertSQL = sprintf("INSERT INTO cj_cc_cliente (tipo, nombre, apellido, lci, ci, nit, contribuyente, urbanizacion, calle, casa, municipio, ciudad, estado, direccion, telf, otrotelf, correo, contacto, lci2, cicontacto, telfcontacto, correocontacto, reputacionCliente, descuento, fcreacion, status, credito, tipocliente, fdesincorporar, id_clave_movimiento_predeterminado, licencia, fecha_expiracion_licencia, paga_impuesto, bloquea_venta, tipo_cuenta_cliente)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($lstTipo, "text"),
			valTpDato($frmCliente['txtNombre'], "text"),
			valTpDato($frmCliente['txtApellido'], "text"),
			valTpDato($txtLciCliente, "text"),
			valTpDato($txtCiCliente, "text"),
			valTpDato($frmCliente['txtNit'], "text"),
			valTpDato($frmCliente['lstContribuyente'], "text"),
			valTpDato($frmCliente['txtUrbanizacion'], "text"),
			valTpDato($frmCliente['txtCalle'], "text"),
			valTpDato($frmCliente['txtCasa'], "text"),
			valTpDato($frmCliente['txtMunicipio'], "text"),
			valTpDato($frmCliente['txtCiudad'], "text"),
			valTpDato($frmCliente['txtEstado'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmCliente['txtTelefono'], "text"),
			valTpDato($frmCliente['txtOtroTelefono'], "text"),
			valTpDato($frmCliente['txtCorreo'], "text"),
			valTpDato($frmCliente['txtNombreContacto'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCiContacto, "text"),
			valTpDato($frmCliente['txtTelefonoContacto'], "text"),
			valTpDato($frmCliente['txtCorreoContacto'], "text"),
			valTpDato($frmCliente['lstReputacionCliente'], "text"),
			valTpDato($frmCliente['lstDescuento'], "text"),
			valTpDato(date("Y-m-d",strtotime($frmCliente['txtFechaCreacion'])),"date"),
			valTpDato($frmCliente['lstEstatus'], "text"),
			valTpDato($lstCredito, "text"),
			valTpDato($frmCliente['lstTipoCliente'], "text"),
			valTpDato(date("Y-m-d",strtotime($frmCliente['txtFechaDesincorporar'])),"date"),
			valTpDato($frmCliente['lstClaveMovimiento'], "int"),
			valTpDato($frmCliente['txtLicencia'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaExpiracionLicencia'])), "date"),
			valTpDato($cbxPagaImpuesto, "int"),
			valTpDato($cbxBloquearVenta, "int"),
			valTpDato(2, "int")); // 1 = Prospecto, 2 = Cliente
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idCliente = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		if($frmCliente['txtFechaNacimiento'] != ""){
			$insertSQL = sprintf("INSERT INTO crm_perfil_prospecto (id, fecha_nacimiento) 
				VALUES (%s, %s)",
				valTpDato($idCliente, "int"),
				valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaNacimiento'])), "date"));				
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	// VERIFICA SI LAS EMPRESAS ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryClienteEmpresa = sprintf("SELECT * FROM cj_cc_cliente_empresa cliente_emp
	WHERE id_cliente = %s;",
		valTpDato($idCliente, "int"));
	$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
	if (!$rsClienteEmpresa) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowClienteEmpresa = mysql_fetch_assoc($rsClienteEmpresa)) {
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if ($rowClienteEmpresa['id_cliente_empresa'] == $frmCliente['hddIdClienteEmpresa'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM cj_cc_credito
			WHERE id_cliente_empresa = %s
				AND creditoreservado = 0;",
				valTpDato($rowClienteEmpresa['id_cliente_empresa'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			$deleteSQL = sprintf("DELETE FROM cj_cc_cliente_empresa WHERE id_cliente_empresa = %s;",
				valTpDato($rowClienteEmpresa['id_cliente_empresa'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA LAS EMPRESAS PARA EL CLIENTE
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$idClienteEmpresa = $frmCliente['hddIdClienteEmpresa'.$valor];
			$idEmpresa = $frmCliente['hddIdEmpresa'.$valor];
			$idCredito = $frmCliente['hddIdCredito'.$valor];
			
			if ($idClienteEmpresa > 0) {
			} else {
				$insertSQL = sprintf("INSERT INTO cj_cc_cliente_empresa (id_cliente, id_empresa)
				VALUE (%s, %s);",
					valTpDato($idCliente, "int"),
					valTpDato($idEmpresa, "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idClienteEmpresa = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			if (in_array($frmCliente['lstCredito'], array("0","Si"))) {
				if ($idCredito > 0) {
					if (!xvalidaAcceso($objResponse,"cc_clientes_credito","editar")) { return $objResponse; }
					
					if ($frmCliente['txtDiasCredito'.$valor] == 0 && $frmCliente['txtLimiteCredito'.$valor] == 0) {
						$deleteSQL = sprintf("DELETE FROM cj_cc_credito
						WHERE id = %s
							AND creditoreservado = 0;",
							valTpDato($idCredito, "int"));
						$Result1 = mysql_query($deleteSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					} else {
						$updateSQL = sprintf("UPDATE cj_cc_credito SET
							diascredito = %s,
							limitecredito = %s,
							fpago = %s
						WHERE id = %s;",
							valTpDato($frmCliente['txtDiasCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtLimiteCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtFormaPago'.$valor], "text"),
							valTpDato($idCredito, "int"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				} else {
					if (str_replace(",","",$frmCliente['txtLimiteCredito'.$valor]) > 0) {
						if (!xvalidaAcceso($objResponse,"cc_clientes_credito","insertar")) { return $objResponse; }
						
						$insertSQL = sprintf("INSERT INTO cj_cc_credito (id_cliente_empresa, diascredito, limitecredito, fpago)
						VALUE (%s, %s, %s, %s);",
							valTpDato($idClienteEmpresa, "int"),
							valTpDato($frmCliente['txtDiasCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtLimiteCredito'.$valor], "real_inglesa"),
							valTpDato($frmCliente['txtFormaPago'.$valor], "text"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}	
				}
			}
			
			// ACTUALIZA EL CREDITO DISPONIBLE
			$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
				creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
															WHERE fact_vent.idCliente = cliente_emp.id_cliente
																AND fact_vent.id_empresa = cliente_emp.id_empresa
																AND fact_vent.estadoFactura IN (0,2)), 0)
													+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
															WHERE nota_cargo.idCliente = cliente_emp.id_cliente
																AND nota_cargo.id_empresa = cliente_emp.id_empresa
																AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
													- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
															WHERE anticip.idCliente = cliente_emp.id_cliente
																AND anticip.id_empresa = cliente_emp.id_empresa
																AND anticip.estadoAnticipo IN (1,2)
																AND anticip.estatus = 1), 0)
													- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
															WHERE nota_cred.idCliente = cliente_emp.id_cliente
																AND nota_cred.id_empresa = cliente_emp.id_empresa
																AND nota_cred.estadoNotaCredito IN (1,2)), 0)
													+ IFNULL((SELECT
																SUM(IFNULL(ped_vent.subtotal, 0)
																	- IFNULL(ped_vent.subtotal_descuento, 0)
																	+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																			WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
																	+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																			WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
															FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_cliente = cliente_emp.id_cliente
																AND ped_vent.id_empresa = cliente_emp.id_empresa
																AND ped_vent.estatus_pedido_venta IN (2)), 0)),
				creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
											WHERE fact_vent.idCliente = cliente_emp.id_cliente
												AND fact_vent.id_empresa = cliente_emp.id_empresa
												AND fact_vent.estadoFactura IN (0,2)), 0)
									+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
											WHERE nota_cargo.idCliente = cliente_emp.id_cliente
												AND nota_cargo.id_empresa = cliente_emp.id_empresa
												AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
									- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
											WHERE anticip.idCliente = cliente_emp.id_cliente
												AND anticip.id_empresa = cliente_emp.id_empresa
												AND anticip.estadoAnticipo IN (1,2)
												AND anticip.estatus = 1), 0)
									- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
											WHERE nota_cred.idCliente = cliente_emp.id_cliente
												AND nota_cred.id_empresa = cliente_emp.id_empresa
												AND nota_cred.estadoNotaCredito IN (1,2)), 0)
									+ IFNULL((SELECT
												SUM(IFNULL(ped_vent.subtotal, 0)
													- IFNULL(ped_vent.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
															WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
													+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
															WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
											FROM iv_pedido_venta ped_vent
											WHERE ped_vent.id_cliente = cliente_emp.id_cliente
												AND ped_vent.id_empresa = cliente_emp.id_empresa
												AND ped_vent.estatus_pedido_venta IN (2)
												AND id_empleado_aprobador IS NOT NULL), 0))
			WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
				AND cliente_emp.id_cliente = %s
				AND cliente_emp.id_empresa = %s;",
				valTpDato($idCliente, "int"),
				valTpDato($idEmpresa, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Cliente Guardado con Éxito");
	
	$objResponse->script("byId('btnCancelarCliente').click();");
	
	$objResponse->loadCommands(listaCliente(
		$frmListaCliente['pageNum'],
		$frmListaCliente['campOrd'],
		$frmListaCliente['tpOrd'],
		$frmListaCliente['valBusq']));
	
	return $objResponse;
}

function insertarClienteEmpresa($idEmpresa, $frmCliente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmCliente['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idEmpresa > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmCliente['hddIdEmpresa'.$valor] == $idEmpresa) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemClienteEmpresa($contFila, "", $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert("Este item ya se encuentra incluido");
		}
	}
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa LIKE %s",
			valTpDato($valCadBusq[0], "text"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("credito LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("status LIKE %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("paga_impuesto = %s ",
			valTpDato($valCadBusq[3], "boolean"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipocliente LIKE %s",
			valTpDato($valCadBusq[4], "text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', nombre, apellido) LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cliente.id,
		cliente.tipo,
		cliente.nombre,
		cliente.apellido,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		cliente.telf,
		cliente.credito,
		cliente.status,
		cliente.tipocliente,
		bloquea_venta,
		paga_impuesto
	FROM cj_cc_cliente cliente
		LEFT JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, $spanClienteCxC);
		$htmlTh .= ordenarCampo("xajax_listaCliente", "24%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "24%", $pageNum, "apellido", $campOrd, $tpOrd, $valBusq, $maxRows, "Apellido");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "Teléfono");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "6%", $pageNum, "paga_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Paga Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['status']) {
			case "Inactivo" : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case "Activo" : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['ci_cliente'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['apellido'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['telf'])."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['paga_impuesto'] == 1) ? "SI" : "NO")."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo'])."</td>";
			$htmlTb .= "<td align=\"center\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblCliente', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id']);
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("divListaCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanRIF;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("id_empresa_reg <> 100");
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_empresa LIKE %s
		OR nombre_empresa_suc LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "8%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanRIF));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "36%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "36%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, ("Sucursal"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarEmpresa%s\" onclick=\"validarInsertarEmpresa('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$contFila,
				$row['id_empresa_reg']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($nombreSucursal)."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_al.gif\"/>");
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
	
	$objResponse->assign("divListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarPermiso($frmPermiso) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] > 0) {
		if ($frmPermiso['hddModulo'] == "cc_cliente_list_cedula") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			
			$objResponse->script("
			byId('lstTipo').className = 'inputHabilitado';
			byId('lstTipo').onchange = function() { }
			byId('txtCedula').className = 'inputHabilitado';
			byId('txtCedula').readOnly = false;
			byId('txtCedula').focus();
			byId('txtCedula').select();
			
			byId('aDesbloquearCedula').style.display = 'none';");
		} else if ($frmPermiso['hddModulo'] == "cc_cliente_list_nombre") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			
			$objResponse->script("
			byId('txtNombre').className = 'inputHabilitado';
			byId('txtApellido').className = 'inputHabilitado';
			byId('txtNombre').readOnly = false;
			byId('txtApellido').readOnly = false;
			byId('txtNombre').focus();
			byId('txtNombre').select();
			
			byId('aDesbloquearNombre').style.display = 'none';");
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCredito");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"calcularEdad");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstCredito");
$xajax->register(XAJAX_FUNCTION,"cargaLstFormaPago");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"eliminarClienteEmpresa");
$xajax->register(XAJAX_FUNCTION,"exportarCliente");
$xajax->register(XAJAX_FUNCTION,"formCliente");
$xajax->register(XAJAX_FUNCTION,"formCredito");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"guardarCliente");
$xajax->register(XAJAX_FUNCTION,"insertarClienteEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function dateadd($date, $dd = 0, $mm = 0, $yy = 0) {
	$date_r = getdate(strtotime($date));
	
	$date_result = date(spanDateFormat, mktime(($date_r["hours"]+$hh),($date_r["minutes"]+$mn),($date_r["seconds"]+$ss),($date_r["mon"] + $mm),($date_r["mday"] + $dd),($date_r["year"] + $yy)));
	
	return $date_result;
}

function insertarItemClienteEmpresa($contFila, $idClienteEmpresa = "", $idEmpresa = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idClienteEmpresa > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryClienteEmpresa = sprintf("SELECT
			cliente_emp.id_cliente_empresa,
			cliente_emp.id_empresa,
			cred.id AS id_credito,
			cred.diascredito,
			cred.fpago,
			cred.limitecredito,
			cred.creditoreservado,
			cred.creditodisponible,
			cred.intereses
		FROM cj_cc_credito cred
			RIGHT JOIN cj_cc_cliente_empresa cliente_emp ON (cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
		WHERE cliente_emp.id_cliente_empresa = %s;",
			valTpDato($idClienteEmpresa, "int"));
		$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
		if (!$rsClienteEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsClienteEmpresa = mysql_num_rows($rsClienteEmpresa);
		$rowClienteEmpresa = mysql_fetch_assoc($rsClienteEmpresa);
	}
	
	$idEmpresa = ($idEmpresa == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['id_empresa'] : $idEmpresa;
	$idCredito = ($idCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['id_credito'] : $idCredito;
	$txtDiasCredito = ($txtDiasCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['diascredito'] : $txtDiasCredito;
	$txtFormaPago = ($txtFormaPago == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['fpago'] : $txtFormaPago;
	$txtLimiteCredito = ($txtLimiteCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['limitecredito'] : $txtLimiteCredito;
	$txtCreditoReservado = ($txtCreditoReservado == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['creditoreservado'] : $txtCreditoReservado;
	$txtCreditoDisponible = ($txtCreditoDisponible == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['creditodisponible'] : $txtCreditoDisponible;
	$txtIntereses = ($txtIntereses == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['intereses'] : $txtIntereses;
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE id_empresa_reg = %s;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDiasCredito%s\" name=\"txtDiasCredito%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtFormaPago%s\" name=\"txtFormaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtLimiteCredito%s\" name=\"txtLimiteCredito%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCreditoReservado%s\" name=\"txtCreditoReservado%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCreditoDisponible%s\" name=\"txtCreditoDisponible%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td><a id=\"aEditarCredito%s\" class=\"modalImg\" rel=\"#divFlotante2\"><img class=\"puntero\" src=\"../img/iconos/edit_privilegios.png\" title=\"Editar Crédito\"/></a>".
				"<input type=\"hidden\" id=\"hddIdClienteEmpresa%s\" name=\"hddIdClienteEmpresa%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCredito%s\" name=\"hddIdCredito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdEmpresa%s\" name=\"hddIdEmpresa%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aEditarCredito%s').onclick = function() {
			abrirDivFlotante2(this, 'tblCredito', '%s');
		}",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['nombre_empresa']),
			$contFila, $contFila, number_format($txtDiasCredito, 0, ".", ","),
			$contFila, $contFila, $txtFormaPago,
			$contFila, $contFila, number_format($txtLimiteCredito, 2, ".", ","),
			$contFila, $contFila, number_format($txtCreditoReservado, 2, ".", ","),
			$contFila, $contFila, number_format($txtCreditoDisponible, 2, ".", ","),
			$contFila,
				$contFila, $contFila, $idClienteEmpresa,
				$contFila, $contFila, $idCredito,
				$contFila, $contFila, $idEmpresa,
			
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>