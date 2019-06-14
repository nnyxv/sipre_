<?php


function asignarCliente($idCliente, $idEmpresa, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.descuento,
		cliente.credito,
		cliente.id_clave_movimiento_predeterminado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	WHERE id = %s
		AND id_empresa = %s
		AND status = 'Activo';",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",$rowCliente['nombre_cliente']);
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaCliente').click();");
	}
	
	return $objResponse;
}

function asignarUnidadBasica($idUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT 
		uni_bas.nom_uni_bas,
		modelo.nom_modelo,
		vers.nom_version
	FROM an_uni_bas uni_bas
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
	WHERE id_uni_bas = %s",
		valTpDato($idUnidadBasica,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdUnidadBasica","value",$idUnidadBasica);
	$objResponse->assign("txtUnidadBasica","value",utf8_encode($row['nom_uni_bas']));
	$objResponse->assign("txtModelo","value",utf8_encode($row['nom_modelo']));
	$objResponse->assign("txtVersion","value",utf8_encode($row['nom_version']));
	
	$objResponse->script("byId('txtCantidadAsignada').focus();");
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $asigDescuento = "true", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT
		id_proveedor,
		nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor
	WHERE id_proveedor = %s",
		valTpDato($idProveedor, "text"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtIdProv","value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",utf8_encode($rowProv['nombre']));
	$objResponse->assign("txtRifProv","value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccionProv","innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContactoProv","value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContactoProv","value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonosProv","value",utf8_encode($rowProv['telefono']));
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowProv['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaProveedor').click();");
	}
	
	return $objResponse;
}

function buscarAsignacion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaAsignacion(0, "idAsignacion", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmAsignacion['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarUnidadBasica($frmBuscarUnidadBasica, $frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmAsignacion['txtIdEmpresa'],
		(is_array($frmBuscarUnidadBasica['lstMarcaBuscarUnidadBasica']) ? implode(",",$frmBuscarUnidadBasica['lstMarcaBuscarUnidadBasica']) : $frmBuscarUnidadBasica['lstMarcaBuscarUnidadBasica']),
		(is_array($frmBuscarUnidadBasica['lstModeloBuscarUnidadBasica']) ? implode(",",$frmBuscarUnidadBasica['lstModeloBuscarUnidadBasica']) : $frmBuscarUnidadBasica['lstModeloBuscarUnidadBasica']),
		(is_array($frmBuscarUnidadBasica['lstVersionBuscarUnidadBasica']) ? implode(",",$frmBuscarUnidadBasica['lstVersionBuscarUnidadBasica']) : $frmBuscarUnidadBasica['lstVersionBuscarUnidadBasica']),
		(is_array($frmBuscarUnidadBasica['lstAnoBuscarUnidadBasica']) ? implode(",",$frmBuscarUnidadBasica['lstAnoBuscarUnidadBasica']) : $frmBuscarUnidadBasica['lstAnoBuscarUnidadBasica']),
		(is_array($frmBuscarUnidadBasica['lstCatalogoBuscarUnidadBasica']) ? implode(",",$frmBuscarUnidadBasica['lstCatalogoBuscarUnidadBasica']) : $frmBuscarUnidadBasica['lstCatalogoBuscarUnidadBasica']),
		$frmBuscarUnidadBasica['txtCriterioBuscarUnidadBasica'],
		$frmBuscarUnidadBasica['hddObjDestinoUnidadBasica']);
	
	$objResponse->loadCommands(listaUnidadBasica(0, "id_uni_bas", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstMarcaModeloVersion($tpLst, $idLstOrigen, $nombreObjeto, $objetoBuscar = "false", $padreId = "", $selId = "", $onChange = "") {
	$objResponse = new xajaxResponse();
	
	$padreId = is_array($padreId) ? implode(",",$padreId) : $padreId;
	
	switch ($tpLst) {
		case "unidad_basica" : $arraySelec = array("lstPadre","lstMarca","lstModelo","lstVersion");
	}
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	// SI EL OBJETO NO ES EL ULTIMO ASIGNA LA FUNCION PARA CARGAR EL SIGUIENTE OBJETO
	if (($posList + 1) != count($arraySelec) - 1) {
		$onChange = "onchange=\"".$onChange." xajax_cargaLstMarcaModeloVersion('".$tpLst."', '".$arraySelec[$posList+1]."', '".$nombreObjeto."', '".$objetoBuscar."', getSelectValues(byId(this.id)), '', '".str_replace("'","\'",$onChange)."');\"";
	}
	
	// SI EL VALOR DEL OBJETO ES IGUAL A SELECCIONE, LIMPIARA TODOS LOS OBJETOS SIGUIENTES
	if ($padreId == '-1' && $nombreObjeto != "Buscar") {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$nombreObjeto."\" name=\"".$valor.$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
					$html .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html .= "</select>";
				$objResponse->assign("td".$valor.$nombreObjeto, 'innerHTML', $html);
			}
		}
		
		return $objResponse;
	} else {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html2 = "<select id=\"".$valor.$nombreObjeto."\" name=\"".$valor.$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
					$html2 .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html2 .= "</select>";
				$objResponse->assign("td".$valor.$nombreObjeto, 'innerHTML', $html2);
			}
		}
		
		switch ($posList) {
			case 0 :
				$query = sprintf("SELECT * FROM an_marca marca
				ORDER BY marca.nom_marca;");
				$campoId = "id_marca";
				$campoDesc = "nom_marca";
				break;
			case 1 :
				$query = sprintf("SELECT * FROM an_modelo modelo
				WHERE modelo.id_marca IN (%s)
				ORDER BY modelo.nom_modelo;",
					valTpDato($padreId, "campo"));
				$campoId = "id_modelo";
				$campoDesc = "nom_modelo";
				break;
			case 2 :
				$query = sprintf("SELECT * FROM an_version vers
				WHERE vers.id_modelo IN (%s)
				ORDER BY vers.nom_version;",
					valTpDato($padreId, "campo"));
				$campoId = "id_version";
				$campoDesc = "nom_version";
				break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select ".(($totalRows > 2 && $objetoBuscar == "true") ? "multiple=\"multiple\"": "")." id=\"".$arraySelec[$posList+1].$nombreObjeto."\" name=\"".$arraySelec[$posList+1].$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:99%\">";
			$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row[$campoId]) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$row[$campoId]."\">".utf8_encode($row[$campoDesc])."</option>";
		}
		$html .= "</select>";
	}
	
	$objResponse->assign("td".$arraySelec[$posList+1].$nombreObjeto, 'innerHTML', $html);
	
	return $objResponse;
}

function cargaLstAnoBuscar($nombreObjeto = "", $selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_ano'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_ano']."\">".utf8_encode($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cerrarAsignacion($idAsignacion, $frmListaAsignacion) {
	$objResponse = new xajaxResponse();
	
	if ($idAsignacion > 0) {
		$query = sprintf("SELECT * FROM an_det_asignacion det_asig
		WHERE det_asig.idAsignacion = %s",
			valTpDato($idAsignacion,"int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
		if ($totalRows > 0) {
			if (!xvalidaAcceso($objResponse,"an_asignacion_list","editar")) { return $objResponse; }
			
			mysql_query("START TRANSACTION;");
			
			$updateSQL = sprintf("UPDATE an_asignacion SET
				estatus_asignacion = %s
			WHERE idAsignacion = %s;",
				valTpDato(1, "int"), // 0 = Abierta, 1 = Cerrada, 2 = Aceptada, 3 = Confirmada
				valTpDato($idAsignacion, "int"));
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			mysql_query("COMMIT;");
		} else {
			return $objResponse->alert("Usted no puede cerrar una asignación sin unidades");
		}
		
		$objResponse->alert("Asignación cerrada con éxito.");
	
		$objResponse->loadCommands(listaAsignacion(
			$frmListaAsignacion['pageNum'],
			$frmListaAsignacion['campOrd'],
			$frmListaAsignacion['tpOrd'],
			$frmListaAsignacion['valBusq']));
	}
	
	return $objResponse;
}

function eliminarAsignacion($idAsignacion, $frmListaAsignacion) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_asignacion_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM an_asignacion WHERE idAsignacion = %s;",
		valTpDato($idAsignacion, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaAsignacion(
		$frmListaAsignacion['pageNum'],
		$frmListaAsignacion['campOrd'],
		$frmListaAsignacion['tpOrd'],
		$frmListaAsignacion['valBusq']));
	
	return $objResponse;
}

function eliminarUnidadAsignacion($hddNumeroArt, $frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_asignar_vehiculo","eliminar")) { return $objResponse; }
	
	if ($hddNumeroArt > 0) {
		$objResponse->script("
		fila = document.getElementById('trItm:".$hddNumeroArt."');
		padre = fila.parentNode;
		padre.removeChild(fila);");
		
		$objResponse->script("xajax_validarInsertarUnidadAsignacion(xajax.getFormValues('frmAsignacion'));");
	}
	
	return $objResponse;
}

function formAsignacion($idAsignacion, $frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAsignacion['cbx'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	if ($idAsignacion > 0) {
		$query = sprintf("SELECT * FROM an_asignacion
		WHERE idAsignacion = %s;",
			valTpDato($idAsignacion, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdAsignacion","value",utf8_encode($idAsignacion));
		$objResponse->loadCommands(asignarEmpresaUsuario($row['id_empresa'], "Empresa", "ListaEmpresa", "", false));
		$objResponse->loadCommands(asignarProveedor($row['id_proveedor'], "true", "false"));
		$objResponse->assign("txtFecha","value",utf8_encode(date(spanDateFormat, strtotime($row['fecha_asignacion']))));
		$objResponse->assign("txtReferencia","value",utf8_encode($row['referencia_asignacion']));
		$objResponse->assign("txtAsignacion","value",utf8_encode($row['asunto_asignacion']));
		$objResponse->assign("txtFechaCierreCompra","value",utf8_encode(date(spanDateFormat, strtotime($row['fecha_cierre_compra']))));
		$objResponse->assign("txtFechaCierreVenta","value",utf8_encode(date(spanDateFormat, strtotime($row['fecha_cierre_venta']))));
		$objResponse->assign("spanTituloUnidadAsignacion","innerHTML",utf8_encode($row['referencia_asignacion']));
		
		$queryPedidoDet = sprintf("SELECT * FROM an_det_asignacion det_asig
		WHERE idAsignacion = %s
		ORDER BY idDetalleAsignacion ASC;",
			valTpDato($idAsignacion, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayObj = NULL;
		while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
			$Result1 = insertarItemUnidadBasica($contFila, $rowPedidoDet['idDetalleAsignacion']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				errorImportarDcto($objResponse);
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		$objResponse->script("xajax_validarInsertarUnidadAsignacion(xajax.getFormValues('frmAsignacion'));");
		
		if ($row['estatus_asignacion'] == 0) { // 0 = Abierta, 1 = Cerrada, 2 = Aceptada, 3 = Confirmada
			if (!xvalidaAcceso($objResponse,"an_asignacion_list","editar")) { $objResponse->script("byId('btnCancelarAsignacion').click();"); return $objResponse; }
			
			$objResponse->script("
			byId('txtIdEmpresa').className = 'inputInicial';
			
			byId('txtIdEmpresa').readOnly = true;
			
			byId('aListarEmpresa').style.display = 'none';
			byId('aNuevoUnidadAsignacion').style.display = '';
			byId('aImportar').style.display = '';");
		} else if ($row['estatus_asignacion'] == 1) { // 0 = Abierta, 1 = Cerrada, 2 = Aceptada, 3 = Confirmada
			if (!xvalidaAcceso($objResponse,"an_respuesta","editar")) { $objResponse->script("byId('btnCancelarAsignacion').click();"); return $objResponse; }
			
			$objResponse->script("
			byId('txtIdEmpresa').className = 'inputInicial';
			byId('txtIdProv').className = 'inputInicial';
			
			byId('txtIdEmpresa').readOnly = true;
			byId('txtIdProv').readOnly = true;
			
			byId('aListarEmpresa').style.display = 'none';
			byId('aListarProv').style.display = 'none';
			byId('aNuevoUnidadAsignacion').style.display = 'none';
			byId('aImportar').style.display = 'none';");
			
			$objResponse->script("
			byId('txtCantidadAceptada1').focus();
			byId('txtCantidadAceptada1').select();");
		} else if ($row['estatus_asignacion'] == 2) { // 0 = Abierta, 1 = Cerrada, 2 = Aceptada, 3 = Confirmada
			if (!xvalidaAcceso($objResponse,"an_confirmacion","editar")) { $objResponse->script("byId('btnCancelarAsignacion').click();"); return $objResponse; }
			
			$objResponse->script("
			byId('aNuevoUnidadAsignacion').style.display = 'none';
			byId('aImportar').style.display = 'none';");
			
			$objResponse->script("
			byId('txtCantidadConfirmada1').focus();
			byId('txtCantidadConfirmada1').select();");
		}
	} else {
		if (!xvalidaAcceso($objResponse,"an_asignacion_list","insertar")) { $objResponse->script("byId('btnCancelarAsignacion').click();"); return $objResponse; }
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "", false));
		
		$objResponse->assign("txtFecha","value",date(spanDateFormat));
	}
	
	return $objResponse;
}

function formUnidadAsignacion() {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_asignar_vehiculo")) { return $objResponse; }
	
	$objResponse->script("byId('btnBuscarUnidadBasica').click();");
	$objResponse->loadCommands(cargaLstMarcaModeloVersion("unidad_basica", "lstPadre", "BuscarUnidadBasica", "true", "", "", "byId('btnBuscarUnidadBasica').click();"));
	$objResponse->loadCommands(cargaLstAnoBuscar('lstAnoBuscarUnidadBasica', '', "byId('btnBuscarUnidadBasica').click();"));
	$objResponse->call("selectedOption","lstTipoAsignacion",0);
	$objResponse->call("seleccionarLstTipoAsignacion",0);
	
	return $objResponse;
}

function guardarAsignacion($frmAsignacion, $frmListaAsignacion) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAsignacion['cbx'];
	
	mysql_query("START TRANSACTION;");
	
	$idAsignacion = $frmAsignacion['hddIdAsignacion'];
	
	if ($idAsignacion > 0) {
		if (!xvalidaAcceso($objResponse,"an_asignacion_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE an_asignacion SET
			id_empresa = %s,
			id_proveedor = %s,
			asunto_asignacion = %s,
			referencia_asignacion = %s,
			fecha_asignacion = %s,
			fecha_cierre_venta = %s,
			fecha_cierre_compra = %s
		WHERE idAsignacion = %s;",
			valTpDato($frmAsignacion['txtIdEmpresa'], "int"),
			valTpDato($frmAsignacion['txtIdProv'], "int"),
			valTpDato($frmAsignacion['txtAsignacion'], "text"),
			valTpDato($frmAsignacion['txtReferencia'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmAsignacion['txtFecha'])), "date"),
			valTpDato(date("Y-m-d", strtotime($frmAsignacion['txtFechaCierreVenta'])), "date"),
			valTpDato(date("Y-m-d", strtotime($frmAsignacion['txtFechaCierreCompra'])), "date"),
			valTpDato($idAsignacion, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		$cantDetAgregados = 0;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$objResponse->script("byId('txtCantidadAceptada".$valor."').className = 'inputSinFondo'");
				$objResponse->script("byId('txtCantidadConfirmada".$valor."').className = 'inputSinFondo'");
				
				if (doubleval($frmAsignacion['txtCantidadAceptada'.$valor]) > doubleval($frmAsignacion['hddCantidadAsignada'.$valor])
				|| (isset($frmAsignacion['txtCantidadAceptada'.$valor]) && $frmAsignacion['txtCantidadAceptada'.$valor] == "")) {
					$arrayCantidadInvalida[] = "txtCantidadAceptada".$valor;
				} else if (doubleval($frmAsignacion['txtCantidadConfirmada'.$valor]) > doubleval($frmAsignacion['hddCantidadAsignada'.$valor])
				|| (isset($frmAsignacion['txtCantidadConfirmada'.$valor]) && $frmAsignacion['txtCantidadConfirmada'.$valor] == "")) {
					$arrayCantidadInvalida[] = "txtCantidadConfirmada".$valor;
				}
				
				if ($frmAsignacion['txtCantidadAceptada'.$valor] >= 0 || $frmAsignacion['txtCantidadConfirmada'.$valor] >= 0) {
					$cantDetAgregados++;
				}
			}
		}
		
		$query = sprintf("SELECT * FROM an_asignacion WHERE idAsignacion = %s;",
			valTpDato($idAsignacion, "int"));
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$row = mysql_fetch_assoc($rs);
		
		if (count($arrayCantidadInvalida) > 0) {
			if (count($arrayCantidadInvalida) > 0) {
				foreach ($arrayCantidadInvalida as $indice => $valor) {
					$objResponse->script("byId('".$valor."').className = 'inputErrado';");
				}
			}
			
			return $objResponse->alert("Los campos señalados en rojo son invalidos");
		} else if ($cantDetAgregados > 0) {
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					$idDetalleAsignacion = $frmAsignacion['hddIdDetalleAsignacion'.$valor];
					
					if ($row['estatus_asignacion'] == 1) { // 0 = Abierta, 1 = Cerrada, 2 = Aceptada, 3 = Confirmada
						if (!xvalidaAcceso($objResponse,"an_respuesta","editar")) { return $objResponse; }
						
						$updateSQL = sprintf("UPDATE an_det_asignacion SET
							cantidadAceptada = %s
						WHERE idDetalleAsignacion = %s;",
							valTpDato($frmAsignacion['txtCantidadAceptada'.$valor], "int"),
							valTpDato($idDetalleAsignacion, "int"));
					} else if ($row['estatus_asignacion'] == 2) { // 0 = Abierta, 1 = Cerrada, 2 = Aceptada, 3 = Confirmada
						if (!xvalidaAcceso($objResponse,"an_confirmacion","editar")) { return $objResponse; }
						
						$updateSQL = sprintf("UPDATE an_det_asignacion SET
							cantidadConfirmada = %s
						WHERE idDetalleAsignacion = %s;",
							valTpDato($frmAsignacion['txtCantidadConfirmada'.$valor], "int"),
							valTpDato($idDetalleAsignacion, "int"));
					}
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				}
			}
		}
	} else {
		if (!xvalidaAcceso($objResponse,"an_asignacion_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO an_asignacion (id_empresa, id_proveedor, asunto_asignacion, referencia_asignacion, fecha_asignacion, fecha_cierre_venta, fecha_cierre_compra, estatus_asignacion)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($frmAsignacion['txtIdEmpresa'], "int"),
			valTpDato($frmAsignacion['txtIdProv'], "int"),
			valTpDato($frmAsignacion['txtAsignacion'], "text"),
			valTpDato($frmAsignacion['txtReferencia'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmAsignacion['txtFecha'])), "date"),
			valTpDato(date("Y-m-d", strtotime($frmAsignacion['txtFechaCierreVenta'])), "date"),
			valTpDato(date("Y-m-d", strtotime($frmAsignacion['txtFechaCierreCompra'])), "date"),
			valTpDato(0, "int")); // 0 = Abierta, 1 = Cerrada, 2 = Aceptada, 3 = Confirmada
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idAsignacion = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// VERIFICA SI LOS ARTICULOS ALMACENADOS EN LA BD EN EL PEDIDO AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryPedidoDet = sprintf("SELECT * FROM an_det_asignacion WHERE idAsignacion = %s;",
		valTpDato($idAsignacion, "int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($rowPedidoDet['idDetalleAsignacion'] == $frmAsignacion['hddIdDetalleAsignacion'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM an_det_asignacion WHERE idDetalleAsignacion = %s;",
				valTpDato($rowPedidoDet['idDetalleAsignacion'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA EL DETALLE DEL PEDIDO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if (strlen($frmAsignacion['hddIdUnidadBasica'.$valor]) > 0 && !($frmAsignacion['hddIdDetalleAsignacion'.$valor] > 0)) {
				if (!xvalidaAcceso($objResponse,"an_asignar_vehiculo","editar")) { return $objResponse; }
				
				$insertSQL = sprintf("INSERT INTO an_det_asignacion (idAsignacion, idUnidadesBasicas, idCliente, cantidadAsignada, cantidadAceptada, cantidadConfirmada, flotilla)
				VALUE (%s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idAsignacion, "int"),
					valTpDato($frmAsignacion['hddIdUnidadBasica'.$valor], "double"),
					valTpDato($frmAsignacion['hddIdCliente'.$valor], "int"),
					valTpDato($frmAsignacion['hddCantidadAsignada'.$valor], "double"),
					valTpDato($frmAsignacion['txtCantidadAceptada'.$valor], "double"),
					valTpDato($frmAsignacion['txtCantidadConfirmada'.$valor], "double"),
					valTpDato($frmAsignacion['hddTipoAsignacion'.$valor], "int")); // 0 = Normal, 1 = Flotilla
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
			
			$totalFinalCantidadAsignada += $frmAsignacion['hddCantidadAsignada'.$valor];
			$totalFinalCantidadAceptada += $frmAsignacion['txtCantidadAceptada'.$valor];
			$totalFinalCantidadConfirmada += $frmAsignacion['txtCantidadConfirmada'.$valor];
		}
	}
	
	if ($totalFinalCantidadConfirmada > 0) {
		$estadoAsignacion = 3;
	} else if ($totalFinalCantidadAceptada > 0) {
		$estadoAsignacion = 2;
	}
	
	// ACTUALIZA EL ESTADO DE LA ASIGNACION
	$updateSQL = sprintf("UPDATE an_asignacion SET
		estatus_asignacion = %s
	WHERE idAsignacion = %s;",
		valTpDato($estadoAsignacion, "int"),
		valTpDato($idAsignacion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	mysql_query("COMMIT;");
	
	if ($estadoAsignacion == 3) { // 0 = Abierta, 1 = Cerrada, 2 = Aceptada, 3 = Confirmada
		$objResponse->alert("Confirmación de Unidades guardada con éxito.");
		
		$objResponse->script("window.location.href='an_asignar_plan_pago_list.php';");
	} else if ($estadoAsignacion == 2) { // 0 = Abierta, 1 = Cerrada, 2 = Aceptada, 3 = Confirmada
		$objResponse->alert("Aceptación de Unidades guardada con éxito.");
	} else {
		$objResponse->alert("Asignación guardado con éxito.");
	}
	
	$objResponse->script("byId('btnCancelarAsignacion').click();");
	
	$objResponse->loadCommands(listaAsignacion(
		$frmListaAsignacion['pageNum'],
		$frmListaAsignacion['campOrd'],
		$frmListaAsignacion['tpOrd'],
		$frmListaAsignacion['valBusq']));
	
	return $objResponse;
}

function importarDcto($frmImportarPedido, $frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	$inputFileType = 'Excel5';
	//$inputFileType = 'Excel2007';
	//$inputFileType = 'Excel2003XML';
	//$inputFileType = 'OOCalc';
	//$inputFileType = 'Gnumeric';
	$inputFileName = 'reportes/tmp/'.$frmImportarPedido['hddUrlArchivo'];
	
	$phpExcel = new PHPExcel_Reader_Excel2007();
	$archivoExcel = $phpExcel->load($inputFileName);
	
	$archivoExcel->setActiveSheetIndex(0);
	$i = 1;
	while (strlen($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()) > 0) {
		$cantidadAsignada = $archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue();
		$cantidadAceptada = $archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue();
		$cantidadConfirmada = $archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue();
		
		if ($itemExcel == true && doubleval($cantidadAsignada) > 0 && doubleval($cantidadAceptada) > 0 && doubleval($cantidadConfirmada) > 0) {
			$arrayFilaImportar[] = array(
				$archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue(), // Código Unidad
				$archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue(), // Cedula
				$archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue(), // Cantidad Asignados
				$archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue(), // Cantidad Aceptada
				$archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue()); // Cantidad Confirmada
		}
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == strtoupper("Código Unidad")
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == strtoupper("Código Unidad")
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == strtoupper("Código Unidad")
		|| trim(strtoupper(htmlentities($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == strtoupper("Código Unidad")
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == strtoupper("Codigo Unidad")) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAsignacion['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if (isset($arrayFilaImportar)) {
		foreach ($arrayFilaImportar as $indiceFila => $valorFila) {
			// RUTINA PARA AGREGAR EL ARTICULO
			$idEmpresa = ($frmAsignacion['txtIdEmpresa'] > 0) ? $frmAsignacion['txtIdEmpresa'] : $_SESSION['idEmpresaUsuarioSysGts'];
				
			// BUSCA LOS DATOS DE LA UNIDAD BASICA
			$queryUnidadBasica = sprintf("SELECT 
				uni_bas.id_uni_bas,
				uni_bas.nom_uni_bas
			FROM an_uni_bas uni_bas
			WHERE uni_bas.nom_uni_bas LIKE %s;",
				valTpDato($arrayFilaImportar[$indiceFila][0], "text"));
			$rsUnidadBasica = mysql_query($queryUnidadBasica);
			if (!$rsUnidadBasica) { errorImportarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsUnidadBasica = mysql_num_rows($rsUnidadBasica);
			$rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica);
			
			// BUSCA LOS DATOS DEL CLIENTE
			$queryCliente = sprintf("SELECT
				cliente.id,
				CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
				CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
				cliente.credito
			FROM cj_cc_cliente cliente
			WHERE CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s;",
				valTpDato($arrayFilaImportar[$indiceFila][1], "text"));
			$rsCliente = mysql_query($queryCliente);
			if (!$rsCliente) { errorImportarDcto($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRowsCliente = mysql_num_rows($rsCliente);
			$rowCliente = mysql_fetch_assoc($rsCliente);
			
			$idUnidadBasica = $rowUnidadBasica['id_uni_bas'];
			$idCliente = $rowCliente['id'];
			
			$existe = false;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice2 => $valor2) {
					if ($frmAsignacion['hddIdUnidadBasica'.$valor2] == $idUnidadBasica && $idUnidadBasica > 0) {
						//$existe = true;
					}
				}
			}
			
			if ($existe == false) {
				if ($totalRowsUnidadBasica > 0) {								
					$cantidadAsignada = $arrayFilaImportar[$indiceFila][2];
					$cantidadAceptada = $arrayFilaImportar[$indiceFila][3];
					$cantidadConfirmada = $arrayFilaImportar[$indiceFila][4];
					
					$Result1 = insertarItemUnidadBasica($contFila, "", $idUnidadBasica, $cantidadAsignada, $cantidadAceptada, $cantidadConfirmada, $idCliente);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) {
						errorImportarDcto($objResponse);
						return $objResponse->alert($Result1[1]);
					} else if ($Result1[0] == true) {
						$contFila = $Result1[2];
						$frmAsignacion['hddIdUnidadBasica'.$contFila] = $idUnidadBasica;
						$frmAsignacion['hddIdCliente'.$contFila] = $idCliente;
						$objResponse->script($Result1[1]);
						$arrayObj[] = $contFila;
					}
				} else {
					$arrayObjNoExiste[] = $arrayFilaImportar[$indiceFila][0];
				}
			} else {
				$arrayObjExiste[] = $arrayFilaImportar[$indiceFila][0];
			}
		}
		
		errorImportarDcto($objResponse);
		if (strlen($msjCantidadExcedida) > 0)
			$objResponse->alert(utf8_encode($msjCantidadExcedida));
			
		if (count($arrayObjNoExiste) > 0)
			$objResponse->alert(utf8_encode("No existe(n) en el sistema ".count($arrayObjNoExiste)." items:\n".implode("\n",$arrayObjNoExiste)));
			
		if (count($arrayObjExiste) > 0) {
			$objResponse->alert(utf8_encode("Ya se encuentra(n) incluido(s) ".count($arrayObjExiste)." items:\n".implode("\n",$arrayObjExiste)));
		} else if (count($arrayObj) > 0) {
			$objResponse->alert(("Pedido Importado con éxito."));
		} else {
			$objResponse->alert(utf8_encode("No se pudo importar el archivo"));
		}
		
		$objResponse->script("
		byId('btnCancelarImportarPedido').click();");
		
		$objResponse->script("xajax_validarInsertarUnidadAsignacion(xajax.getFormValues('frmAsignacion'));");
	} else {
		$objResponse->alert(utf8_encode("Verifique que el Pedido tenga Cantidades Solicitadas"));
		
		errorImportarDcto($objResponse);
	}
	
	return $objResponse;
}

function insertarUnidadAsignacion($frmUnidadAsignacion, $frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAsignacion['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$idEmpresa = $frmAsignacion['txtIdEmpresa'];
	$idUnidadBasica = $frmUnidadAsignacion['hddIdUnidadBasica'];
	$cantidadAsignada = $frmUnidadAsignacion['txtCantidadAsignada'];
	$idCliente = $frmUnidadAsignacion['txtIdCliente'];

	// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Compra)
	$queryConfig203 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 203 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig203 = mysql_query($queryConfig203);
	if (!$rsConfig203) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConfig203 = mysql_fetch_assoc($rsConfig203);
	
	$existe = false;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmAsignacion['hddIdUnidadBasica'.$valor] == $idUnidadBasica) {
				$existe = true;
			}
		}
	}
	
	if (count($arrayObj) < $rowConfig203['valor']) {
		if ($existe == false) {
			$Result1 = insertarItemUnidadBasica($contFila, "", $idUnidadBasica, $cantidadAsignada, 0, 0, $idCliente);
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
		$objResponse->script("xajax_validarInsertarUnidadAsignacion(xajax.getFormValues('frmAsignacion'));");
		
		$objResponse->alert("Unidad Asignada con éxito.");
		
		$objResponse->script("byId('btnCancelarModelo').click();");
	} else {
		$objResponse->alert(("Solo puede agregar un máximo de ".number_format($rowConfig203['valor'], 2, ".", ",")." items por Pedido"));
	}
	
	return $objResponse;
}

function listaAsignacion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_asignacion NOT IN (3,4,5)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("asig.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha_asignacion BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(asunto_asignacion LIKE %s
		OR referencia_asignacion LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"),
			valTpDato("%".$valCadBusq[3]."%", "text"));
	}
	
	$query = sprintf("SELECT asig.*,
		prov.nombre AS nombre_proveedor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_asignacion asig
		INNER JOIN cp_proveedor prov ON (asig.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (asig.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaAsignacion", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaAsignacion", "8%", $pageNum, "fecha_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaAsignacion", "8%", $pageNum, "idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Asignacion");
		$htmlTh .= ordenarCampo("xajax_listaAsignacion", "14%", $pageNum, "referencia_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Referencia");
		$htmlTh .= ordenarCampo("xajax_listaAsignacion", "22%", $pageNum, "asunto_asignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Asignación");
		$htmlTh .= ordenarCampo("xajax_listaAsignacion", "34%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= "<td colspan=\"6\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(date(spanDateFormat, strtotime($row['fecha_asignacion'])))."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['idAsignacion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['referencia_asignacion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['asunto_asignacion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_asignacion'] == 0) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAceptar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAsignacion', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar Asignación\"/></a>",
					$contFila,
					$row['idAsignacion']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_asignacion'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarCerrarAsignacion('%s')\" src=\"../img/iconos/accept.png\" title=\"Cerrar Asignación\"/>",
					$row['idAsignacion']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_asignacion'] == 1) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAceptar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAsignacion', '%s');\"><img class=\"puntero\" src=\"../img/iconos/aprob_mecanico.png\" title=\"Aceptar Unidades\"/></a>",
					$contFila,
					$row['idAsignacion']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_asignacion'] == 2) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aConfirmar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAsignacion', '%s');\"><img class=\"puntero\" src=\"../img/iconos/aprob_control_calidad.png\" title=\"Confirmar Unidades\"/></a>",
					$contFila,
					$row['idAsignacion']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/an_asignacion_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Asignación PDF\"/>",
					$row['idAsignacion']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_asignacion'] == 0 ) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\" title=\"Eliminar Asignación\"/>",
					$row['idAsignacion']);
			}
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAsignacion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAsignacion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAsignacion(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAsignacion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAsignacion(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaAsignacion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_Ws(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cliente.telf LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cliente_emp.id_empresa,
		cliente.id,
		cliente.tipo,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.nit AS nit_cliente,
		cliente.licencia AS licencia_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.credito,
		cliente.status,
		cliente.tipocliente,
		cliente.bloquea_venta,
		cliente.paga_impuesto,
		perfil_prospecto.compania,
		(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) AS cantidad_modelos,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				1
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0, 2, NULL)
		END) AS tipo_cuenta_cliente,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				'Prospecto'
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0,
					'Prospecto Aprobado (Cliente Venta)',
					'Sin Prospectación (Cliente Post-Venta)')
		END) AS descripcion_tipo_cuenta_cliente,
		vw_pg_empleado.nombre_empleado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado) %s", $sqlBusq);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "46%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "14%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, ("Teléfono"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['tipo_cuenta_cliente']) {
			case 1 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_comment.png\" title=\"".("Prospecto")."\"/>"; break;
			case 2 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_green.png\" title=\"".("Prospecto Aprobado (Cliente Venta)")."\"/>"; break;
			default : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_gray.png\" title=\"".("Sin Prospectación (Cliente Post-Venta)")."\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".$imgTipoCuentaCliente."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['credito'] == "no") ? "divMsjInfo" : "divMsjAlerta")."\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"20\">";
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

function listaUnidadBasica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 12, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.catalogo = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("unidad_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca IN (%s)",
			valTpDato($valCadBusq[1], "campo"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_ano IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT vw_iv_modelo.*,
		uni_bas.clv_uni_bas
	FROM an_uni_bas uni_bas
		LEFT JOIN sa_unidad_empresa unidad_emp ON (uni_bas.id_uni_bas = unidad_emp.id_unidad_basica)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas) %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		switch($row['catalogo']) {
			case 0 : $classCatalogo = ""; break;
			case 1 : $classCatalogo = "class=\"divMsjInfo6\""; break;
			default : $classCatalogo = ""; break;
		}

		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		
		$htmlTb .= "<td valign=\"top\" width=\"33%\">";
			$htmlTb .= "<table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td ".$classCatalogo." title=\"Id ".$row['id_uni_bas']."\" valign=\"top\">"."<button type=\"button\" onclick=\"xajax_asignarUnidadBasica('".$row['id_uni_bas']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
				$htmlTb .= sprintf("<td style=\"background-color:#FFFFFF\">%s</td>", "<img src=\"".$imgFoto."\" width=\"80\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">".
						"<div align=\"center\" class=\"divGris\">%s</div>".
						"<div>%s</div>".
						"<div>%s</div>".
						"<div>%s</div>".
						"<div>%s</div>".
					"</td>", "100%",
						utf8_encode($row['nom_uni_bas']),
					utf8_encode($row['nom_marca']),
					utf8_encode($row['nom_modelo']),
					utf8_encode($row['nom_version']),
					"Año ".utf8_encode($row['nom_ano']));
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
		$htmlTb .= "<td colspan=\"4\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadBasica","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		id_proveedor,
		nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarInsertarUnidadAsignacion($frmAsignacion) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAsignacion['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	
	if (count($arrayObj) > 0) { // SI TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';");
	} else { // SI NO TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpresa').readOnly = false;
		byId('aListarEmpresa').style.display = '';");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarAsignacion");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarcaModeloVersion");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnoBuscar");
$xajax->register(XAJAX_FUNCTION,"cerrarAsignacion");
$xajax->register(XAJAX_FUNCTION,"eliminarAsignacion");
$xajax->register(XAJAX_FUNCTION,"eliminarUnidadAsignacion");
$xajax->register(XAJAX_FUNCTION,"formAsignacion");
$xajax->register(XAJAX_FUNCTION,"formUnidadAsignacion");
$xajax->register(XAJAX_FUNCTION,"guardarAsignacion");
$xajax->register(XAJAX_FUNCTION,"importarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarUnidadAsignacion");
$xajax->register(XAJAX_FUNCTION,"listaAsignacion");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");
$xajax->register(XAJAX_FUNCTION,"validarInsertarUnidadAsignacion");

function buscarEnArray($array, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($array as $indice => $valor) {
		if ($valor == $dato) return $x;
		
		$x++;
	}
	return NULL;
}

function errorImportarDcto($objResponse) {
	$objResponse->script("
	byId('btnGuardarImportarPedido').disabled = false;
	byId('btnCancelarImportarPedido').disabled = false;");
}

function insertarItemUnidadBasica($contFila, $idDetalleAsignacion = "", $idUnidadBasica = "", $cantidadAsignada = "", $cantidadAceptada = "", $cantidadConfirmada = "", $idCliente = "") {
	$contFila++;
	
	if ($idDetalleAsignacion > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT det_asig.*,
			(SELECT asig.estatus_asignacion FROM an_asignacion asig
			WHERE asig.idAsignacion = det_asig.idAsignacion) AS estatus_asignacion
		FROM an_det_asignacion det_asig
		WHERE idDetalleAsignacion = %s;",
			valTpDato($idDetalleAsignacion, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$estatusAsignacion = $rowPedidoDet['estatus_asignacion'];
	}
	
	$idUnidadBasica = ($idUnidadBasica == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['idUnidadesBasicas'] : $idUnidadBasica;
	$cantidadAsignada = ($cantidadAsignada == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidadAsignada'] : $cantidadAsignada;
	$cantidadAceptada = ($cantidadAceptada == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidadAceptada'] : $cantidadAceptada;
	$cantidadConfirmada = ($cantidadConfirmada == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidadConfirmada'] : $cantidadConfirmada;
	$idCliente = ($idCliente == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['idCliente'] : $idCliente;
	
	// BUSCA LOS DATOS DE LA UNIDAD
	$queryUnidadBasica = sprintf("SELECT * FROM vw_an_unidad_basica WHERE id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rsUnidadBasica = mysql_query($queryUnidadBasica);
	if (!$rsUnidadBasica) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsUnidadBasica = mysql_num_rows($rsUnidadBasica);
	$rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica);
	
	// BUSCA LOS DATOS DEL CLIENTE
	$queryCliente = sprintf("SELECT
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.credito
	FROM cj_cc_cliente cliente
	WHERE id = %s;",
		valTpDato($idCliente, "int"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$imgEstatusUnidadAsignacion = ($idCliente > 0) ? "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>" : "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>";
	
	$hddTipoAsignacion = ($idCliente > 0) ? 1 : 0;
	
	if (!in_array($estatusAsignacion, array(1,2))) {
		$imgEliminar = sprintf("<img class=\"puntero\" onclick=\"validarEliminarUnidadAsignacion(\'%s\')\" src=\"../img/iconos/ico_delete.png\" title=\"Eliminar Unidad\"/>",
			$contFila);
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\">".
			"<td title=\"trItm:%s\"><input type=\"checkbox\" id=\"cbxItm\" name=\"cbxItm[]\" value=\"%s\"/>".
				"<input type=\"checkbox\" id=\"cbx\" name=\"cbx[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"right\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"hddCantidadAsignada%s\" name=\"hddCantidadAsignada%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtCantidadAceptada%s\" name=\"txtCantidadAceptada%s\" %s style=\"text-align:right\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtCantidadConfirmada%s\" name=\"txtCantidadConfirmada%s\" %s style=\"text-align:right\" value=\"%s\"></td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdUnidadBasica%s\" name=\"hddIdUnidadBasica%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdCliente%s\" name=\"hddIdCliente%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddTipoAsignacion%s\" name=\"hddTipoAsignacion%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdDetalleAsignacion%s\" name=\"hddIdDetalleAsignacion%s\" value=\"%s\">".
			"</td>".
		"</tr>');",
		$contFila,
			$contFila, $contFila,
				 $contFila,
			$contFila, $contFila,
			$imgEstatusUnidadAsignacion,
			utf8_encode($rowUnidadBasica['nom_uni_bas']),
			utf8_encode($rowUnidadBasica['nom_modelo']),
			utf8_encode($rowUnidadBasica['nom_version']),
			utf8_encode($rowCliente['ci_cliente']),
			utf8_encode($rowCliente['nombre_cliente']),
			$contFila, $contFila, number_format($cantidadAsignada, 2, ".", ","),
			$contFila, $contFila, ($estatusAsignacion == 1 ? " size=\"8\"" : " class=\"inputSinFondo\" readonly=\"readonly\""), number_format($cantidadAceptada, 2, ".", ","),
			$contFila, $contFila, ($estatusAsignacion == 2 ? " size=\"8\"" : " class=\"inputSinFondo\" readonly=\"readonly\""), number_format($cantidadConfirmada, 2, ".", ","),
			$imgEliminar,
				$contFila, $contFila, $idUnidadBasica,
				$contFila, $contFila, $idCliente,
				$contFila, $contFila, $hddTipoAsignacion,
				$contFila, $contFila, $idDetalleAsignacion);
	
	return array(true, $htmlItmPie, $contFila);
}
?>