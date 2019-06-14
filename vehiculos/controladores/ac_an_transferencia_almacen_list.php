<?php


function asignarEmpleado($idEmpleado, $nombreObjeto, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_pg_empleado.id_empleado = %s", 
		valTpDato($idEmpleado, "int"));
	
	$queryEmpleado = sprintf("SELECT *, 
		(SELECT codigo_empleado FROM pg_empleado
		WHERE id_empleado = vw_pg_empleado.id_empleado) AS codigo_empleado
	FROM vw_pg_empleados vw_pg_empleado
		LEFT JOIN pg_usuario usuario ON (vw_pg_empleado.id_empleado = usuario.id_empleado) %s;", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("hddId".$nombreObjeto, "value", $rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombre".$nombreObjeto, "value", utf8_encode($rowEmpleado['nombre_empleado']));
	$objResponse->assign("hddIdUsuario", "value", $rowEmpleado['id_usuario']);
	$objResponse->assign("txtNombreUsuario", "value", utf8_encode($rowEmpleado['nombre_usuario']));
	$objResponse->assign("txtContrasena", "value", utf8_encode($rowEmpleado['codigo_empleado']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaEmpleado').click();");
	}
	
	return $objResponse;
}

function buscarEmpleado($frmBuscarEmpleado) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s", 
		$frmBuscarEmpleado['txtCriterioBuscarEmpleado'],
		$frmBuscarEmpleado['hddObjDestino']);
		
	$objResponse->loadCommands(listaEmpleado(0, "id_empleado", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarUnidadFisica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstMarcaBuscar']) ? implode(",",$frmBuscar['lstMarcaBuscar']) : $frmBuscar['lstMarcaBuscar']),
		(is_array($frmBuscar['lstModeloBuscar']) ? implode(",",$frmBuscar['lstModeloBuscar']) : $frmBuscar['lstModeloBuscar']),
		(is_array($frmBuscar['lstVersionBuscar']) ? implode(",",$frmBuscar['lstVersionBuscar']) : $frmBuscar['lstVersionBuscar']),
		(is_array($frmBuscar['lstEstadoCompraBuscar']) ? implode(",",$frmBuscar['lstEstadoCompraBuscar']) : $frmBuscar['lstEstadoCompraBuscar']),
		(is_array($frmBuscar['lstEstadoVentaBuscar']) ? implode(",",$frmBuscar['lstEstadoVentaBuscar']) : $frmBuscar['lstEstadoVentaBuscar']),
		(is_array($frmBuscar['lstCondicionBuscar']) ? implode(",",$frmBuscar['lstCondicionBuscar']) : $frmBuscar['lstCondicionBuscar']),
		(is_array($frmBuscar['lstAlmacenBuscar']) ? implode(",",$frmBuscar['lstAlmacenBuscar']) : $frmBuscar['lstAlmacenBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaUnidadFisica(0, "CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstAlmacen($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($idEmpresa, "text"));
	}
	
	$query = sprintf("SELECT * FROM an_almacen %s ORDER BY nom_almacen", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAlmacen\" name=\"lstAlmacen\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_almacen']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\">".utf8_encode($row['nom_almacen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAlmacen","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstAlmacenBuscar($nombreObjeto, $idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$query = sprintf("SELECT * FROM an_almacen alm %s ORDER BY alm.nom_almacen", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_almacen'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_almacen']."\">".utf8_encode($row['nom_almacen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "") {
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
		$sqlBusq .= $cond.sprintf("(pago_contado = 1 OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1 AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Débito, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento %s ORDER BY clave", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['tipo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_clave_movimiento']."\">".utf8_encode($row['clave'].") ".$row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCondicionBuscar($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_condicion_unidad ORDER BY descripcion;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstCondicionBuscar\" name=\"lstCondicionBuscar\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_condicion_unidad'] || $totalRows == 1) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_condicion_unidad']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCondicionBuscar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoCompraBuscar($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	($accion != "Ajuste") ? $array[] = "ALTA" : "";
	($accion != "Ajuste") ? $array[] = "IMPRESO" : "";
	$array[] = "COMPRADO";
	$array[] = "REGISTRADO";
	($accion != "Ajuste") ? $array[] = "CANCELADO" : "";
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoVenta($nombreObjeto, $accion = "", $selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."'); ".$onChange."\"" : "onchange=\"".$onChange."\"";
	
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "TRANSITO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "POR REGISTRAR" : "";
	($accion != "Ajuste") ? $array[] = "SINIESTRADO" : "";
	$array[] = "DISPONIBLE";
	($accion != "Venta") ? $array[] = "RESERVADO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "VENDIDO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "ENTREGADO" : "";
	($accion != "Venta") ? $array[] = "PRESTADO" : "";
	($accion != "Venta") ? $array[] = "ACTIVO FIJO" : "";
	($accion != "Venta") ? $array[] = "INTERCAMBIO" : "";
	($accion != "Venta") ? $array[] = "DEVUELTO" : "";
	($accion != "Venta") ? $array[] = "ERROR EN TRASPASO" : "";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstadoVentaBuscar($nombreObjeto, $accion = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "TRANSITO" : "";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "POR REGISTRAR" : "";
	($accion != "Ajuste") ? $array[] = "SINIESTRADO" : "";
	$array[] = "DISPONIBLE";
	($accion != "Ajuste" && $accion != "Venta") ? $array[] = "RESERVADO" : "";
	($accion != "Ajuste" && $accion != "Venta" && $accion != "Existencia") ? $array[] = "VENDIDO" : "";
	($accion != "Ajuste" && $accion != "Venta" && $accion != "Existencia") ? $array[] = "ENTREGADO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "PRESTADO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "ACTIVO FIJO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "INTERCAMBIO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "DEVUELTO" : "";
	($accion != "Venta" && $accion != "Existencia") ? $array[] = "ERROR EN TRASPASO" : "";
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
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

function formEstadoVenta($frmUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$idUnidadFisica = $frmUnidadFisica['txtIdUnidadFisica'];
	
	// BUSCA LOS DATOS DEL ALMACEN DE ORIGEN
	$query = sprintf("SELECT * FROM an_almacen WHERE id_almacen = %s",
		valTpDato($frmUnidadFisica['hddIdAlmacen'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAlmacenOrigen = mysql_fetch_assoc($rs);
	
	// BUSCA LOS DATOS DEL ALMACEN DE DESTINO
	$queryAlmacenDestino = sprintf("SELECT * FROM an_almacen WHERE id_almacen = %s;",
		valTpDato($frmUnidadFisica['lstAlmacen'], "int"));
	$rsAlmacenDestino = mysql_query($queryAlmacenDestino);
	if (!$rsAlmacenDestino) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAlmacenDestino = mysql_fetch_assoc($rsAlmacenDestino);
	
	// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
	$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUsuario = mysql_fetch_assoc($rsUsuario);
	
	// BUSCA LOS DATOS DE LA UNIDAD
	$query = sprintf("SELECT 
		alm.id_empresa,
		uni_fis.costo_compra,
		uni_fis.precio_compra,
		uni_fis.costo_agregado,
		uni_fis.costo_depreciado,
		uni_fis.costo_trade_in
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
		INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
		LEFT JOIN an_color color_ext2 ON (uni_fis.id_color_externo2 = color_ext2.id_color)
		LEFT JOIN an_color color_int2 ON (uni_fis.id_color_interno2 = color_int2.id_color)
		LEFT JOIN an_origen pais_origen ON (uni_fis.id_origen = pais_origen.id_origen)
		INNER JOIN an_uso uso ON (uni_fis.id_uso = uso.id_uso)
		INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
		INNER JOIN an_combustible comb ON (uni_bas.com_uni_bas = comb.id_combustible)
	WHERE uni_fis.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdEmpleadoElaborado","value",$_SESSION['idEmpleadoSysGts']);
	$objResponse->assign("txtNombreEmpleadoElaborado","value",utf8_encode($rowUsuario['nombre_empleado']));
	
	$observacion = sprintf("En el día %s fue cambiado la Unidad Física %s con ".$spanPlaca." %s del Almacén: %s por: %s, Estado de Venta: %s por: %s",
		date(spanDateFormat),
		$frmUnidadFisica['txtIdUnidadFisica'],
		$frmUnidadFisica['txtPlaca'],
		$rowAlmacenOrigen['nom_almacen'],
		$rowAlmacenDestino['nom_almacen'],
		$frmUnidadFisica['hddEstadoVenta'],
		$frmUnidadFisica['lstEstadoVenta']);
	$objResponse->assign("txtObservacion","innerHTML",($observacion));
	$objResponse->assign("txtSubTotal","value",number_format($row['precio_compra'], 2, ".", ","));
	
	$objResponse->loadCommands(asignarEmpresaUsuario($rowAlmacenOrigen['id_empresa'],"EmpresaValeSalida","ListaEmpresaValeSalida"));
	$objResponse->loadCommands(asignarEmpresaUsuario($rowAlmacenDestino['id_empresa'],"EmpresaValeEntrada","ListaEmpresaValeEntrada"));
	
	$objResponse->assign("hddIdAlmacenOrigen","value",$rowAlmacenOrigen['id_almacen']);
	$objResponse->assign("txtAlmacenOrigen","value",$rowAlmacenOrigen['nom_almacen']);
	
	$objResponse->assign("hddIdAlmacenDestino","value",$rowAlmacenDestino['id_almacen']);
	$objResponse->assign("txtAlmacenDestino","value",$rowAlmacenDestino['nom_almacen']);
	
	$objResponse->script("
	selectedOption('lstTipoMovimientoSalida',4);
	byId('lstTipoMovimientoSalida').onchange = function() {
		selectedOption(this.id,4);
		xajax_cargaLstClaveMovimiento('lstClaveMovimientoSalida', '2', this.value, '', '5,6');
	}
	selectedOption('lstTipoMovimientoEntrada',2);
	byId('lstTipoMovimientoEntrada').onchange = function() {
		selectedOption(this.id,2);
		xajax_cargaLstClaveMovimiento('lstClaveMovimientoEntrada', '2', this.value, '', '5,6');
	}");
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimientoSalida", "2", "4", "", "5,6"));
	$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimientoEntrada", "2", "2", "", "5,6"));
	
	return $objResponse;
}

function formUnidadFisica($idUnidadFisica, $hddModulo) {
	$objResponse = new xajaxResponse();
	
	if ($idUnidadFisica > 0) {
		if (!xvalidaAcceso($objResponse,"an_transferencia_almacen_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarUnidadFisica').click();"); return $objResponse; }
		
		$query = sprintf("SELECT 
			uni_fis.id_unidad_fisica,
			uni_bas.id_uni_bas,
			uni_bas.nom_uni_bas,
			uni_bas.clv_uni_bas,
			uni_bas.des_uni_bas,
			marca.nom_marca,
			modelo.nom_modelo,
			vers.nom_version,
			ano.id_ano,
			ano.nom_ano,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			uni_fis.id_condicion_unidad,
			cond_unidad.descripcion AS condicion_unidad,
			uni_fis.kilometraje,
			uni_fis.fecha_fabricacion,
			uni_bas.imagen_auto,
			alm.id_empresa,
			alm.id_almacen,
			alm.nom_almacen,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			color_ext1.id_color AS id_color_externo,
			color_ext1.nom_color AS color_externo,
			color_int1.id_color AS id_color_interno,
			color_int1.nom_color AS color_interno,
			color_ext2.id_color AS id_color_externo2,
			color_ext2.nom_color AS color_externo2,
			color_int2.id_color AS id_color_interno2,
			color_int2.nom_color AS color_interno2,
			uni_fis.registro_legalizacion,
			uni_fis.registro_federal,
			pais_origen.id_origen,
			pais_origen.nom_origen,
			clase.nom_clase,
			uso.id_uso,
			uso.nom_uso,
			uni_bas.pto_uni_bas,
			uni_bas.cil_uni_bas,
			uni_bas.ccc_uni_bas,
			uni_bas.cab_uni_bas,
			trans.nom_transmision,
			comb.nom_combustible,
			uni_bas.cap_uni_bas,
			uni_bas.uni_uni_bas,
			uni_bas.anos_de_garantia,
			uni_bas.kilometraje AS kilometraje_garantia,
			uni_fis.fecha_fabricacion,
			uni_fis.serial1,
			uni_fis.codigo_unico_conversion,
			uni_fis.marca_kit,
			uni_fis.modelo_regulador,
			uni_fis.serial_regulador,
			uni_fis.marca_cilindro,
			uni_fis.capacidad_cilindro,
			uni_fis.fecha_elaboracion_cilindro
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
				INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
				INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
				INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
				INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext1 ON (uni_fis.id_color_externo1 = color_ext1.id_color)
			INNER JOIN an_color color_int1 ON (uni_fis.id_color_interno1 = color_int1.id_color)
			LEFT JOIN an_color color_ext2 ON (uni_fis.id_color_externo2 = color_ext2.id_color)
			LEFT JOIN an_color color_int2 ON (uni_fis.id_color_interno2 = color_int2.id_color)
			LEFT JOIN an_origen pais_origen ON (uni_fis.id_origen = pais_origen.id_origen)
			INNER JOIN an_uso uso ON (uni_fis.id_uso = uso.id_uso)
			INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
			INNER JOIN an_combustible comb ON (uni_bas.com_uni_bas = comb.id_combustible)
		 WHERE uni_fis.id_unidad_fisica = %s
			AND estado_venta IN ('SINIESTRADO', 'DISPONIBLE', 'RESERVADO', 'ACTIVO FIJO');",
			valTpDato($idUnidadFisica, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddAccionFrmUnidadFisica","value",$hddModulo);
		$objResponse->assign("txtIdUnidadFisica", "value", $row['id_unidad_fisica']);
		$objResponse->assign("hddIdUnidadBasica", "value", $row['id_uni_bas']);
		$objResponse->assign("txtNombreUnidadBasica", "value", utf8_encode($row['nom_uni_bas']));
		$objResponse->assign("txtClaveUnidadBasica", "value", utf8_encode($row['clv_uni_bas']));
		$objResponse->assign("txtDescripcion", "innerHTML", utf8_encode($row['des_uni_bas']));
		$objResponse->assign("txtMarcaUnidadBasica", "value", utf8_encode($row['nom_marca']));
		$objResponse->assign("txtModeloUnidadBasica", "value", utf8_encode($row['nom_modelo']));
		$objResponse->assign("txtVersionUnidadBasica", "value", utf8_encode($row['nom_version']));
		$objResponse->assign("txtAno", "value", utf8_encode($row['nom_ano']));
		$objResponse->assign("txtPlaca", "value", utf8_encode($row['placa']));
		$objResponse->assign("txtCondicion", "value", utf8_encode($row['condicion_unidad']));
		$objResponse->assign("txtFechaFabricacion", "value", (($row['fecha_fabricacion'] != "") ? date(spanDateFormat, strtotime($row['fecha_fabricacion'])) : ""));
		$objResponse->loadCommands(cargaLstAlmacen($row['id_empresa'], $row['id_almacen']));
		$objResponse->assign("hddIdAlmacen", "value", $row['id_almacen']);
		$objResponse->assign("txtEstadoCompra", "value", utf8_encode($row['estado_compra']));
		$objResponse->loadCommands(cargaLstEstadoVenta("lstEstadoVenta", (($hddModulo == "an_transferencia_almacen_form.php") ? "Ajuste" : "Venta"), utf8_encode($row['estado_venta']), "", (($hddModulo == "an_transferencia_almacen_form.php") ? true : false)));
		$objResponse->assign("hddEstadoVenta", "value", utf8_encode($row['estado_venta']));
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		$objResponse->assign("imgArticulo","src",$imgFoto);
		$objResponse->assign("hddUrlImagen","value",$row['imagen_auto']);
		
		$objResponse->assign("txtColorExterno1", "value", utf8_encode($row['color_externo']));
		$objResponse->assign("txtColorInterno1", "value", utf8_encode($row['color_interno']));
		$objResponse->assign("txtColorExterno2", "value", utf8_encode($row['color_externo2']));
		$objResponse->assign("txtColorInterno2", "value", utf8_encode($row['color_interno2']));
		
		$objResponse->assign("txtSerialCarroceria", "value", utf8_encode($row['serial_carroceria']));
		$objResponse->assign("txtSerialMotor", "value", utf8_encode($row['serial_motor']));
		$objResponse->assign("txtNumeroVehiculo", "value", utf8_encode($row['serial_chasis']));
		$objResponse->assign("txtRegistroLegalizacion", "value", utf8_encode($row['registro_legalizacion']));
		$objResponse->assign("txtRegistroFederal", "value", utf8_encode($row['registro_federal']));
		
		$objResponse->assign("txtPaisOrigen", "value", utf8_encode($row['nom_origen']));
		$objResponse->assign("txtClase", "value", utf8_encode($row['nom_clase']));
		$objResponse->assign("txtUso", "value", utf8_encode($row['nom_uso']));
		$objResponse->assign("txtNumeroPuertas", "value", utf8_encode($row['pto_uni_bas']));
		$objResponse->assign("txtNumeroCilindros", "value", utf8_encode($row['cil_uni_bas']));
		$objResponse->assign("txtCilindrada", "value", utf8_encode($row['ccc_uni_bas']));
		$objResponse->assign("txtCaballosFuerza", "value", utf8_encode($row['cab_uni_bas']));
		$objResponse->assign("txtTransmision", "value", utf8_encode($row['nom_transmision']));
		$objResponse->assign("txtCombustible", "value", utf8_encode($row['nom_combustible']));
		$objResponse->assign("txtCapacidad", "value", $row['cap_uni_bas']);
		$objResponse->assign("txtUnidad", "value", $row['uni_uni_bas']);
		$objResponse->assign("txtAnoGarantia", "value", $row['anos_de_garantia']);
		$objResponse->assign("txtKmGarantia", "value", number_format($row['kilometraje_garantia'], 2, ".", ","));
		
		if (strlen($row['serial1']) > 0) {
			$objResponse->script("byId('trSistemaGNV').style.display = '';");
		}
		$objResponse->assign("txtSerial1", "value", utf8_encode($row['serial1']));
		$objResponse->assign("txtCodigoUnico", "value", utf8_encode($row['codigo_unico_conversion']));
		$objResponse->assign("txtMarcaKit", "value", utf8_encode($row['marca_kit']));
		$objResponse->assign("txtModeloRegulador", "value", utf8_encode($row['modelo_regulador']));
		$objResponse->assign("txtSerialRegulador", "value", utf8_encode($row['serial_regulador']));
		$objResponse->assign("txtMarcaCilindro", "value", utf8_encode($row['marca_cilindro']));
		$objResponse->assign("txtCapacidadCilindro", "value", utf8_encode($row['capacidad_cilindro']));
		$txtFechaCilindro = ($row['fecha_elaboracion_cilindro'] != "") ? date(spanDateFormat, strtotime($row['fecha_elaboracion_cilindro'])) : "";
		$objResponse->assign("txtFechaCilindro", "value", $txtFechaCilindro);
		
		$objResponse->assign("txtKilometraje", "value", number_format($row['kilometraje'],0));
		
		if ($hddModulo == "an_estado_venta_unidad_fisica_form.php") {
			$onChange = sprintf("onchange=\"selectedOption(this.id,%s);\"",
				$row['id_empresa']);
			$objResponse->loadCommands(cargaLstEmpresaFinal($row['id_empresa'], $onChange, "lstEmpresaUnidadFisica"));
			
			$objResponse->script("
			byId('lstAlmacen').onchange = function() {
				selectedOption(this.id,'".$row['id_almacen']."');
			}
			byId('lstEmpresaUnidadFisica').className = 'inputInicial';
			byId('lstAlmacen').className = 'inputInicial';");
			
		} else if ($hddModulo == "an_transferencia_almacen_form.php") {
			$onChange = sprintf("onchange=\"xajax_cargaLstAlmacen(this.value);\"");
			$objResponse->loadCommands(cargaLstEmpresaFinal($row['id_empresa'], $onChange, "lstEmpresaUnidadFisica"));
			
			$objResponse->script("
			byId('lstAlmacen').className = 'inputHabilitado';");
		}
		
		
		$objResponse->script("
		byId('trtxtAllowanceAnt').style.display = 'none';
		byId('trtxtAcvAnt').style.display = 'none';
		byId('trtxtPayoffAnt').style.display = 'none';
		byId('trtxtCreditoNetoAnt').style.display = 'none';
		
		byId('txtAllowance').className = 'inputInicial';
		byId('txtAllowance').readOnly = true;
		byId('txtAcv').className = 'inputInicial';
		byId('txtAcv').readOnly = true;
		byId('txtPayoff').className = 'inputInicial';
		byId('txtPayoff').readOnly = true;");
		
		$query = sprintf("SELECT 
			tradein.id_tradein,
			tradein.id_unidad_fisica,
			tradein.allowance,
			tradein.payoff,
			tradein.acv,
			tradein.total_credito,
			tradein.id_proveedor,
			cxc_ant.montoNetoAnticipo,
			cxc_ant.numeroAnticipo,
			cxc_ant.idDepartamento,
			cxc_ant.id_empresa,
			cxc_ant.observacionesAnticipo,
			pg_modulos.descripcionModulo,
			cliente.id,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) as nombre_cliente,
			cj_cc_detalleanticipo.id_concepto,
			cj_conceptos_formapago.descripcion,
			formapagos.idFormaPago,
			formapagos.nombreFormaPago,
			(SELECT cp_proveedor.nombre FROM cp_proveedor WHERE cp_proveedor.id_proveedor = tradein.id_proveedor) as nombre_cliente_adeudado
		FROM an_tradein tradein
			INNER JOIN cj_cc_anticipo cxc_ant ON (tradein.id_anticipo = cxc_ant.idAnticipo)
			INNER JOIN pg_modulos ON (cxc_ant.idDepartamento = pg_modulos.id_modulo)
			INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
			INNER JOIN cj_cc_detalleanticipo ON (cxc_ant.idAnticipo = cj_cc_detalleanticipo.idAnticipo)
			INNER JOIN cj_conceptos_formapago ON (cj_cc_detalleanticipo.id_concepto = cj_conceptos_formapago.id_concepto)
			INNER JOIN formapagos ON (cj_conceptos_formapago.id_formapago = formapagos.idFormaPago)
		WHERE tradein.id_unidad_fisica = %s
		LIMIT 1;",
			valTpDato($idUnidadFisica, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("txtAllowance", "value", number_format($row['allowance'], 2, ".", ","));
		$objResponse->assign("txtPayoff", "value", number_format($row['payoff'], 2, ".", ","));
		$objResponse->assign("txtAcv", "value", number_format($row['acv'], 2, ".", ","));
		$objResponse->assign("txtCreditoNeto", "value", number_format($row['total_credito'], 2, ".", ","));
	}
	
	return $objResponse;
}

function formUnidadFisicaAgregado($idUnidadFisica, $frmUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUnidadFisica['cbx'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// BUSCA LOS DATOS DE LA UNIDAD FISICA
	$queryUniFis = sprintf("SELECT uni_fis.*,
		alm.id_empresa
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		LEFT JOIN an_tradein tradein ON (uni_fis.id_unidad_fisica = tradein.id_unidad_fisica)
	WHERE uni_fis.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rsUniFis = mysql_query($queryUniFis);
	if (!$rsUniFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUniFis = mysql_fetch_assoc($rsUniFis);
	
	$objResponse->assign("hddIdEmpresaUnidadFisicaAgregado","value",$rowUniFis['id_empresa']);
	$objResponse->assign("hddIdUnidadFisicaAgregado","value",$idUnidadFisica);
	
	$queryUniFisAgregado = sprintf("SELECT uni_fis_agregado.*,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			1
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		2
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	4
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			3
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		4
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	2
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			4
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		2
		END) AS id_tipo_movimiento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			'1.- Compra'
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		'2.- Entrada'
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	'4.- Salida'
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			'3.- Venta'
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		'4.- Salida'
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	'2.- Entrada'
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			'4.- Salida'
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		'2.- Entrada'
		END) AS descripcion_tipo_movimiento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			'FA'
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		'ND'
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	'NC'
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			'FA'
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		'ND'
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	'NC'
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			'VS'
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		'VE'
		END) AS tipoDocumento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.id_factura
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.id_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.id_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.idNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.idNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.id_vale_salida
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.id_vale_entrada
		END) AS id_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.numero_factura_proveedor
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.numero_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.numero_nota_credito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.numeroNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.numeracion_nota_credito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.numero_vale
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.numero_vale_entrada
		END) AS numero_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.fecha_origen
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.fecha_origen_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.fecha_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.fechaRegistroNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.fechaNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			DATE(sa_vs.fecha_vale)
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		DATE(sa_ve.fecha_creada)
		END) AS fecha_origen,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.id_modulo
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.id_modulo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.id_departamento_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.idDepartamentoOrigenNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.idDepartamentoNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			1
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		1
		END) AS id_modulo,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.estatus_factura
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.estatus_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.estado_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.estadoNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.estadoNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.estado_vale
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.estado_vale
		END) AS estatus_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL
			OR uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL
			OR uni_fis_agregado.id_factura_cxc IS NOT NULL
			OR uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN
				(CASE (CASE 
							WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.estatus_factura
							WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.estatus_notacargo
							WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
							WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.estadoNotaCargo
						END)
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END)
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL
			OR uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN
				(CASE (CASE 
							WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.estado_notacredito
							WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.estadoNotaCredito
						END)
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado No Asignado'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
				END)
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL
			OR uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN
				(CASE (CASE 
							WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.estado_vale
							WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.estado_vale
						END)
					WHEN 0 THEN 'Generado'
					WHEN 1 THEN 'Devuelto'
				END)
		END) AS descripcion_estatus_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.observacion_factura
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.observacion_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.observacion_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.observacionNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.observacionesNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			sa_vs.motivo_vale
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		sa_ve.motivo_vale
		END) AS observacion_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			cxp_fact.saldo_factura
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.saldo_notacargo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.saldo_notacredito
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cxc_nd.saldoNotaCargo
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cxc_nc.saldoNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		NULL
		END) AS saldo_documento,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			prov.id_proveedor
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		prov.id_proveedor
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	prov.id_proveedor
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			cliente.id
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		cliente.id
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	cliente.id
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			cliente.id
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		cliente.id
		END) AS id_cliente,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			CONCAT_WS('-', prov.lrif, prov.rif)
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		CONCAT_WS('-', prov.lrif, prov.rif)
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	CONCAT_WS('-', prov.lrif, prov.rif)
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			CONCAT_WS('-', cliente.lci, cliente.ci)
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		CONCAT_WS('-', cliente.lci, cliente.ci)
		END) AS ci_cliente,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			prov.nombre
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		prov.nombre
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	prov.nombre
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN		CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN	CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		CONCAT_WS(' ', cliente.nombre, cliente.apellido)
		END) AS nombre_cliente,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		cxp_nd.id_motivo
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	cxp_nc.id_motivo
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		NULL
		END) AS id_motivo,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN		motivo.descripcion
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN	motivo.descripcion
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_cargo_detalle_motivo cxc_nd_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nd_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nd_det_motivo.id_nota_cargo = cxc_nd.idNotaCargo)
				
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN
				(SELECT GROUP_CONCAT(CONCAT(motivo.id_motivo, '.- ', motivo.descripcion) SEPARATOR ', ')
				FROM cj_cc_nota_credito_detalle_motivo cxc_nc_det_motivo
					INNER JOIN pg_motivo motivo ON (cxc_nc_det_motivo.id_motivo = motivo.id_motivo)
				WHERE cxc_nc_det_motivo.id_nota_credito = cxc_nc.idNotaCredito)
				
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN			NULL
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN		NULL
		END) AS descripcion_motivo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.serial_chasis,
		uni_fis.placa,
		(CASE 
			WHEN (uni_fis_agregado.id_factura_cxp IS NOT NULL) THEN		0
			WHEN (uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL) THEN
				(IFNULL(cxp_nd.subtotal_notacargo, 0)
				- IFNULL(cxp_nd.subtotal_descuento_notacargo, 0)
				+ IFNULL((SELECT SUM(cxp_nd_gasto.monto) AS total_gasto FROM cp_notacargo_gastos cxp_nd_gasto
						WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo
							AND cxp_nd_gasto.id_modo_gasto IN (1,3)), 0)
				+ IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva) AS total_iva FROM cp_notacargo_iva cxp_nd_iva
						WHERE cxp_nd_iva.id_notacargo = cxp_nd.id_notacargo), 0))
			WHEN (uni_fis_agregado.id_nota_credito_cxp IS NOT NULL) THEN
				(IFNULL(cxp_nc.subtotal_notacredito, 0)
				- IFNULL(cxp_nc.subtotal_descuento, 0)
				+ IFNULL((SELECT SUM(cxp_nc_gasto.monto_gasto_notacredito) AS total_gasto FROM cp_notacredito_gastos cxp_nc_gasto
						WHERE cxp_nc_gasto.id_notacredito = cxp_nc.id_notacredito
							AND cxp_nc_gasto.id_modo_gasto IN (1,3)), 0)
				+ IFNULL((SELECT SUM(cxp_nc_iva.subtotal_iva_notacredito) AS total_iva FROM cp_notacredito_iva cxp_nc_iva
						WHERE cxp_nc_iva.id_notacredito = cxp_nc.id_notacredito), 0))
			WHEN (uni_fis_agregado.id_factura_cxc IS NOT NULL) THEN		0
			WHEN (uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL) THEN
				(IFNULL(cxc_nd.subtotalNotaCargo, 0)
				- IFNULL(cxc_nd.descuentoNotaCargo, 0)
				+ (IFNULL(cxc_nd.calculoIvaNotaCargo, 0)
					+ IFNULL(cxc_nd.ivaLujoNotaCargo, 0)))
			WHEN (uni_fis_agregado.id_nota_credito_cxc IS NOT NULL) THEN
				cxc_nc.montoNetoNotaCredito
			WHEN (uni_fis_agregado.id_vale_salida IS NOT NULL) THEN
				sa_vs.monto_total
			WHEN (uni_fis_agregado.id_vale_entrada IS NOT NULL) THEN
				sa_ve.monto_total
		END) AS total_documento,
		vw_pg_empleado_registro.nombre_empleado AS nombre_empleado_registro,
		vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_unidad_fisica_agregado uni_fis_agregado ON (uni_fis.id_unidad_fisica = uni_fis_agregado.id_unidad_fisica)
		LEFT JOIN cp_factura cxp_fact ON (uni_fis_agregado.id_factura_cxp = cxp_fact.id_factura AND uni_fis_agregado.id_factura_cxp IS NOT NULL)
		LEFT JOIN cp_notadecargo cxp_nd ON (uni_fis_agregado.id_nota_cargo_cxp = cxp_nd.id_notacargo AND uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL)
		LEFT JOIN cp_notacredito cxp_nc ON (uni_fis_agregado.id_nota_credito_cxp = cxp_nc.id_notacredito AND uni_fis_agregado.id_nota_credito_cxp IS NOT NULL)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (uni_fis_agregado.id_factura_cxc = cxc_fact.idFactura AND uni_fis_agregado.id_factura_cxc IS NOT NULL)
		LEFT JOIN cj_cc_notadecargo cxc_nd ON (uni_fis_agregado.id_nota_cargo_cxc = cxc_nd.idNotaCargo AND uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL)
		LEFT JOIN cj_cc_notacredito cxc_nc ON (uni_fis_agregado.id_nota_credito_cxc = cxc_nc.idNotaCredito AND uni_fis_agregado.id_nota_credito_cxc IS NOT NULL)
		LEFT JOIN sa_vale_salida sa_vs ON (uni_fis_agregado.id_vale_salida = sa_vs.id_vale_salida AND uni_fis_agregado.id_vale_salida IS NOT NULL)
		LEFT JOIN sa_vale_entrada sa_ve ON (uni_fis_agregado.id_vale_entrada = sa_ve.id_vale_entrada AND uni_fis_agregado.id_vale_entrada IS NOT NULL)
		LEFT JOIN sa_orden orden ON ((sa_vs.id_orden = orden.id_orden AND uni_fis_agregado.id_vale_salida IS NOT NULL)
			OR (sa_ve.id_orden = orden.id_orden AND uni_fis_agregado.id_vale_entrada IS NOT NULL))
		LEFT JOIN cp_proveedor prov ON ((cxp_fact.id_proveedor = prov.id_proveedor AND uni_fis_agregado.id_factura_cxp IS NOT NULL)
			OR (cxp_nd.id_proveedor = prov.id_proveedor AND uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL)
			OR (cxp_nc.id_proveedor = prov.id_proveedor AND uni_fis_agregado.id_nota_credito_cxp IS NOT NULL))
		LEFT JOIN cj_cc_cliente cliente ON ((cxc_fact.idCliente = uni_fis_agregado.id_factura_cxc IS NOT NULL)
			OR (cxc_nd.idCliente = cliente.id AND uni_fis_agregado.id_nota_cargo_cxc IS NOT NULL)
			OR (cxc_nc.idCliente = cliente.id AND uni_fis_agregado.id_nota_credito_cxc IS NOT NULL)
			OR (orden.id_cliente = cliente.id AND uni_fis_agregado.id_vale_salida IS NOT NULL))
		LEFT JOIN pg_motivo motivo ON ((cxp_nd.id_motivo = motivo.id_motivo AND uni_fis_agregado.id_nota_cargo_cxp IS NOT NULL)
			OR (cxp_nc.id_motivo = motivo.id_motivo AND uni_fis_agregado.id_nota_credito_cxp IS NOT NULL))
		LEFT JOIN vw_pg_empleados vw_pg_empleado_registro ON (uni_fis_agregado.id_empleado_registro = vw_pg_empleado_registro.id_empleado)
		LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (uni_fis_agregado.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
	WHERE uni_fis_agregado.id_unidad_fisica = %s;",
		valTpDato($idUnidadFisica, "int"));
	$rsUniFisAgregado = mysql_query($queryUniFisAgregado);
	if (!$rsUniFisAgregado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsUniFisAgregado = mysql_num_rows($rsUniFisAgregado);
	while ($rowUniFisAgregado = mysql_fetch_array($rsUniFisAgregado)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$hddIdUnidadFisicaAgregado = $rowUniFisAgregado['id_unidad_fisica_agregado'];
		$idTipoMovimientoAgregado = $rowUniFisAgregado['id_tipo_movimiento'];
		$tipoDctoAgregado = $rowUniFisAgregado['tipoDocumento'];
		$idDocumento = $rowUniFisAgregado['id_documento'];
		
		$fechaDcto = $rowUniFisAgregado['fecha_origen'];
		$nroDcto = $rowUniFisAgregado['numero_documento'];
		$idModulo = $rowUniFisAgregado['id_modulo'];
		$nombreCliente = $rowUniFisAgregado['nombre_cliente'];
		$serialChasis = $rowUniFisAgregado['serial_carroceria'];
		$numeroPlaca = $rowUniFisAgregado['placa'];
		$estadoDcto = $rowUniFisAgregado['estatus_documento'];
		$descripcionEstadoDcto = $rowUniFisAgregado['descripcion_estatus_documento'];
		$observacionDcto = $rowUniFisAgregado['observacion_documento'];
		$saldoDcto = $rowUniFisAgregado['saldo_documento'];
		$txtTotalDcto = $rowUniFisAgregado['total_documento'];
		
		if (in_array($idModulo,array(1)) && in_array($tipoDctoAgregado,array('VS','VE'))) {
			switch($estadoDcto) {
				case 0 : $class = "class=\"divMsjInfo\""; break;
				case 1 : $class = "class=\"divMsjAlerta\""; break;
			}
		} else {
			switch($estadoDcto) {
				case 0 : $class = "class=\"divMsjError\""; break;
				case 1 : $class = "class=\"divMsjInfo\""; break;
				case 2 : $class = "class=\"divMsjAlerta\""; break;
				case 3 : $class = "class=\"divMsjInfo3\""; break;
				case 4 : $class = "class=\"divMsjInfo4\""; break;
			}
		}
		
		switch($idModulo) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgDctoModulo = $idModulo;
		}
		
		if ($totalRowsUniFisAgregado > 0) {
			$classUniFisAgregado = ($rowUniFisAgregado['estatus'] != 1) ? "class=\"divMsjError\"" : "";
			$estatusUniFisAgregado = ($rowUniFisAgregado['estatus'] != 1) ? "<div align=\"center\">RELACION ANULADA</div>" : "";
			$empleadoAnuladoUniFisAgregado = (strlen($rowUniFisAgregado['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por:</span> <span class=\"textoNegrita_9px\">".$rowUniFisAgregado['nombre_empleado_anulado']."<br>(".date(spanDateFormat, strtotime($rowUniFisAgregado['fecha_anulado'])).")</span></div>" : "";
		}
		
		switch ($idTipoMovimientoAgregado) {
			case 1 : // 1 = COMPRA
				switch ($idModulo) {
					case 0 : $aVerDctoAux = "../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=".$idDocumento.""; break;
					case 2 : $aVerDctoAux = "../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=".$idDocumento.""; break;
					default : $aVerDctoAux = "";
				}
				$aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"Ver Registro Compra PDF\"/><a>" : "";
				break;
			case 2 : // 2 = ENTRADA
				switch ($tipoDctoAgregado) {
					case "ND" :
						$aVerDcto = sprintf("<a href=\"javascript:verVentana(\'../cxp/reportes/cp_nota_cargo_pdf.php?valBusq=%s\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/></a>",
							$idDocumento);
						break;
					case "NC" :
						$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/><a>",
							$idDocumento);
						switch ($idModulo) {
							case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
							case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
							case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
							case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
							case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $idDocumento); break;
							default : $aVerDctoAux = "";
						}
						$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana(\'".$aVerDctoAux."\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
						break;
					default : $aVerDcto = "";
				}
				break;
			case 3 : // 3 = VENTA
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
					$idDocumento);
				break;
			case 4 : // 4 = SALIDA
				switch ($tipoDctoAgregado) {
					case "ND" :
						$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_debito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Débito")."\"/><a>",
							$idDocumento);
						$aVerDcto .= sprintf("<a href=\"javascript:verVentana(\'../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/></a>",
							$idDocumento);
						break;
					case "NC" :
						$aVerDcto = sprintf("<a href=\"javascript:verVentana(\'../cxp/reportes/cp_nota_credito_pdf.php?valBusq=%s\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>",
							$idDocumento);
						break;
					case "VS" :
						$aVerDcto = sprintf("<a href=\"javascript:verVentana(\'../servicios/reportes/sa_imprimir_presupuesto_pdf.php?valBusq=%s|2|3\', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Vale de Salida PDF")."\"/></a>",
							$idDocumento);
						break;
					default : $aVerDcto = "";
				}
				break;
			default : $aVerDcto = "";
		}
		
		$cbxItm = ($totalRowsUniFisAgregado > 0) ? "" : sprintf("<input id=\"cbxItm%s\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>",
			$contFila, $contFila);
		
		// INSERTA EL ARTICULO SIN INJECT
		$objResponse->script(sprintf("$('#trItmPie').before('".
			"<tr align=\"left\" id=\"trItm:%s\" class=\"%s\">".
				"<td title=\"trItm:%s\">%s".
					"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
				"<td align=\"center\">%s".
					"<input type=\"text\" id=\"txtTipoDcto%s\" name=\"txtTipoDcto%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:center\" value=\"%s\"/></td>".
				"<td align=\"center\" title=\"%s\">%s</td>".
				"<td %s>".
					"<table width=\"%s\">".
					"<tr align=\"right\">".
						"<td nowrap=\"nowrap\">%s</td>".
						"<td>%s</td>".
						"<td width=\"%s\">%s</td>".
					"</tr>".
					"</table>".
					"%s".
					"%s</td>".
				"<td>".
					"<table width=\"%s\"><tr><td colspan=\"2\">%s</td></tr>".
					"<tr class=\"textoNegrita_10px\">".
						"%s".
						"%s".
					"</tr>".
					"%s".
					"%s".
					"</table>".
				"</td>".
				"<td align=\"center\" %s>%s</td>".
				"<td align=\"right\">%s</td>".
				"<td><input type=\"text\" id=\"txtTotalDcto%s\" name=\"txtTotalDcto%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdUnidadFisicaAgregado%s\" name=\"hddIdUnidadFisicaAgregado%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdDctoAgregado%s\" name=\"hddIdDctoAgregado%s\" value=\"%s\"/></td>".
			"</tr>');",
			$contFila, $clase,
				$contFila, $cbxItm,
					$contFila,
				$contFila, $contFila,
				utf8_encode($rowUniFisAgregado['descripcion_tipo_movimiento']),
					$contFila, $contFila, utf8_encode($tipoDctoAgregado),
				"Registrado por: ".$rowUniFisAgregado['nombre_empleado_registro'], date(spanDateFormat, strtotime($fechaDcto)),
				$classUniFisAgregado,
					"100%",
						$aVerDcto,
						$imgDctoModulo,
						"100%", $nroDcto,
					$estatusUniFisAgregado,
					$empleadoAnuladoUniFisAgregado,
				"100%", $nombreCliente,
					((strlen($serialChasis) > 0) ? "<td width=\"50%\">".$serialChasis."</td>" : ""),
					((strlen($numeroPlaca) > 0) ? "<td width=\"50%\">".$numeroPlaca."</td>" : ""),
					((strlen($rowUniFisAgregado['descripcion_motivo']) > 0) ? "<tr><td colspan=\"2\"><span class=\"textoNegrita_9px textoAzulNegrita\">".utf8_encode($rowUniFisAgregado['descripcion_motivo'])."</span></td></tr>" : ""),
					((strlen($observacionDcto) > 0) ? "<tr><td colspan=\"2\"><span class=\"textoNegritaCursiva_9px\">".utf8_encode($observacionDcto)."</span></td></tr>" : ""),
				$class, $descripcionEstadoDcto,
				number_format($saldoDcto, 2, ".", ","),
				$contFila, $contFila, number_format(((
						(in_array($idTipoMovimientoAgregado,array(1)) && in_array($tipoDctoAgregado,array("FA")))
						|| (in_array($idTipoMovimientoAgregado,array(2)) && in_array($tipoDctoAgregado,array("ND")))
						|| (in_array($idTipoMovimientoAgregado,array(2)) && in_array($tipoDctoAgregado,array("NC")))
						|| (in_array($idTipoMovimientoAgregado,array(4)) && in_array($tipoDctoAgregado,array("VS")))
					) ? 1 : (-1)) * $txtTotalDcto, 2, ".", ","),
					$contFila, $contFila, $hddIdUnidadFisicaAgregado,
					$contFila, $contFila, $idDocumento));
	}
	
	$objResponse->script("xajax_calcularAgregado(xajax.getFormValues('frmUnidadFisica'));");
	
	//$objResponse->assign("divListaUniFisAgregado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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

function guardarEstadoVenta($frmUnidadFisica, $frmEstadoVenta){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_transferencia_almacen_list","editar")) { return $objResponse; }
	
	$idEmpresaSalida = $frmEstadoVenta['txtIdEmpresaValeSalida'];
	$idEmpresaEntrada = $frmEstadoVenta['txtIdEmpresaValeEntrada'];
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idClaveMovimientoSalida = $frmEstadoVenta['lstClaveMovimientoSalida'];
	$idClaveMovimientoEntrada = $frmEstadoVenta['lstClaveMovimientoEntrada'];
	$idUnidadFisica = $frmUnidadFisica['txtIdUnidadFisica'];
	
	mysql_query("START TRANSACTION;");
	
	// REGISTRA LA AUDITORIA DE LA TRANSFERENCIA
	$insertSQL = sprintf("INSERT INTO an_auditoria_almacen (id_unidad_fisica, fecha, hora, id_almacen_origen, id_almacen_destino, estado_venta_origen, estado_venta_destino, id_empleado_elaborado, id_empleado_autorizado)
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($frmUnidadFisica['txtIdUnidadFisica'], "int"),
		valTpDato("NOW()", "campo"),
		valTpDato("NOW()", "campo"),
		valTpDato($frmUnidadFisica['hddIdAlmacen'], "int"),
		valTpDato($frmUnidadFisica['lstAlmacen'], "int"),
		valTpDato($frmUnidadFisica['hddEstadoVenta'], "text"),
		valTpDato($frmUnidadFisica['lstEstadoVenta'], "text"),
		valTpDato($frmEstadoVenta['hddIdEmpleadoElaborado'], "int"),
		valTpDato($frmEstadoVenta['hddIdEmpleadoAutorizado'], "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idAuditoriaAlmacen = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	if ($frmUnidadFisica['lstAlmacen'] != $frmUnidadFisica['hddIdAlmacen']) {
		if (in_array($frmUnidadFisica['lstEstadoVenta'], array("ACTIVO FIJO"))) {
			$Result1 = guardarValeEntrada($frmUnidadFisica, $frmEstadoVenta);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[3]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"VALE_ENTRADA");
				$idValeEntrada = $Result1[1];
			}
			
			$Result1 = guardarValeSalida($frmUnidadFisica, $frmEstadoVenta);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[3]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"VALE_SALIDA");
				$idValeSalida = $Result1[1];
			}
		} else {
			$Result1 = guardarValeSalida($frmUnidadFisica, $frmEstadoVenta);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[3]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"VALE_SALIDA");
				$idValeSalida = $Result1[1];
			}
			
			$Result1 = guardarValeEntrada($frmUnidadFisica, $frmEstadoVenta);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$objResponse->script($Result1[3]);
				$arrayIdDctoContabilidad[] = array(
					$Result1[1],
					$Result1[2],
					"VALE_ENTRADA");
				$idValeEntrada = $Result1[1];
			}
		}
		
		// ACTUALIZA EL ALMACEN DE LA UNIDAD FISICA
		$updateSQL = sprintf("UPDATE an_unidad_fisica SET
			id_almacen = %s
		WHERE id_unidad_fisica = %s",
			valTpDato($frmUnidadFisica['lstAlmacen'], "int"),
			valTpDato($idUnidadFisica, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
	
	// ACTUALIZA EL ESTADO DE VENTA DE LA UNIDAD FISICA
	$updateSQL = sprintf("UPDATE an_unidad_fisica SET
		estado_venta = %s
	WHERE id_unidad_fisica = %s",
		valTpDato($frmUnidadFisica['lstEstadoVenta'], "text"),
		valTpDato($frmUnidadFisica['txtIdUnidadFisica'], "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			
			// MODIFICADO ERNESTO
			if ($tipoDcto == "VALE_ENTRADA") {
				$idValeEntrada = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarValeEntradaRe")) { generarValeEntradaRe($idValeEntrada,"",""); } break;
					case 2 : if (function_exists("generarValeEntradaVe")) { generarValeEntradaVe($idValeEntrada,"",""); } break;
				}
			} else if ($tipoDcto == "VALE_SALIDA") {
				$idValeSalida = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarValeSalidaRe")) { generarValeSalidaRe($idValeSalida,"",""); } break;
					case 1 : if (function_exists("generarValeSe")) { generarValeSe($idValeSalida,"",""); } break;
					case 2 : if (function_exists("generarValeSalidaVe")) { generarValeSalidaVe($idValeSalida,"",""); } break;
				}
			}
			// MODIFICADO ERNESTO
		}
	}
	
	$objResponse->alert(("Transferencia de ubicación guardada"));
	
	$objResponse->script(sprintf("
	abrirDivFlotante1(null, 'tblVistaTransferenciaAlmacen', '%s');",
		$idAuditoriaAlmacen));
	
	return $objResponse;
}

function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
		OR vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_empleado.nombre_cargo LIKE %s)", 
			valTpDato("%".$valCadBusq[0]."%", "text"), 
			valTpDato("%".$valCadBusq[0]."%", "text"), 
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_empleado.id_empleado, 
		vw_pg_empleado.cedula, 
		vw_pg_empleado.nombre_empleado, 
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleado('".$row['id_empleado']."','".$valCadBusq[1]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s)\">", 
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s, '%s', '%s', '%s', %s);\">%s</a>", 
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
	
	$objResponse->assign("divListaEmpleado", "innerHTML", $htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('SINIESTRADO', 'DISPONIBLE', 'RESERVADO', 'ACTIVO FIJO')");
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = alm.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
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
		$sqlBusq .= $cond.sprintf("uni_fis.estado_compra IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[4])."'", "defined", "'".str_replace(",","','",$valCadBusq[4])."'"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[5])."'", "defined", "'".str_replace(",","','",$valCadBusq[5])."'"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.id_condicion_unidad IN (%s)",
			valTpDato($valCadBusq[6], "campo"));
	}
		
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_almacen IN (%s)",
			valTpDato($valCadBusq[7], "campo"));
	}
	
	if ($valCadBusq[8] != "-1" && $valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_chasis LIKE %s
		OR uni_fis.placa LIKE %s
		OR cxp_fact.numero_factura_proveedor LIKE %s)",
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		vw_iv_modelo.id_uni_bas,
		vw_iv_modelo.nom_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		(CASE vw_iv_modelo.catalogo
			WHEN 0 THEN ''
			WHEN 1 THEN 'En Catálogo'
		END) AS mostrar_catalogo
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
		
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "2%", $pageNum, "uni_fis.id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id Unidad Física"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "9%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanSerialMotor));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, ("Color"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Ingreso"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, ("Estado Venta"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "14%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, ("Almacén"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "asig.idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Asignación"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "7%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro. Fact. Compra"));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "precio_compra", $campOrd, $tpOrd, $valBusq, $maxRows, ("Costo"));
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"30\">".($row['vehiculo'].((strlen($row['mostrar_catalogo']) > 0) ? " <b>[".$row['mostrar_catalogo']."]</b>" : ""))."</td>";
		$htmlTb .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
			valTpDato($row['id_uni_bas'], "int"));
		
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
				WHEN (an_ve.fecha IS NOT NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
				(CASE
					WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
					WHEN (an_ve.fecha IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
				END),
			0) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			cxp_fact.id_factura,
			cxp_fact.numero_factura_proveedor,
			cxp_fact.id_modulo,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			
			(SELECT COUNT(uni_fis_agregado.id_unidad_fisica) FROM an_unidad_fisica_agregado uni_fis_agregado
			WHERE uni_fis_agregado.id_unidad_fisica = uni_fis.id_unidad_fisica) AS cant_agregado,
			
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND an_ve.fecha IS NOT NULL
				AND an_ve.tipo_vale_entrada = 1
				AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $sqlBusq2);
		$queryUnidadFisica .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayTotal = NULL;
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			switch($rowUnidadFisica['estado_venta']) {
				case "SINIESTRADO" : $class = "class=\"divMsjError\""; break;
				case "DISPONIBLE" : $class = "class=\"divMsjInfo\""; break;
				case "RESERVADO" : $class = "class=\"divMsjAlerta\""; break;
				case "VENDIDO" : $class = "class=\"divMsjInfo3\""; break;
				case "ENTREGADO" : $class = "class=\"divMsjInfo4\""; break;
				case "ACTIVO FIJO" : $class = "class=\"divMsjInfo5\""; break;
				default : $class = ""; break;
			}
			
			$aVerDcto = "";
			if ($rowUnidadFisica['id_factura'] > 0) {
				$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxp/cp_factura_form.php?id=%s&vw=v\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Registro Compra")."\"/><a>",
					$rowUnidadFisica['id_factura']);
				switch ($rowUnidadFisica['id_modulo']) {
					case 0: $aVerDctoAux = sprintf("../repuestos/reportes/iv_registro_compra_pdf.php?valBusq=%s", $rowUnidadFisica['id_factura']); break;
					case 2: $aVerDctoAux = sprintf("../vehiculos/reportes/an_registro_compra_pdf.php?valBusq=%s", $rowUnidadFisica['id_factura']); break;
				}
				$aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a id=\"aVerDcto\" href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Registro Compra PDF")."\"/></a>" : "";
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['id_unidad_fisica'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['serial_carroceria'])."</div>";
					$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode($rowUnidadFisica['condicion_unidad'])."</div>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['color_externo1'])."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".utf8_encode($rowUnidadFisica['placa'])."</td>";
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= (($rowUnidadFisica['fecha_origen'] != "") ? "<div>".date(spanDateFormat, strtotime($rowUnidadFisica['fecha_origen']))."</div>" : "");
					$htmlTb .= (($rowUnidadFisica['dias_inventario'] > 0) ? "<div class=\"textoNegrita_9px\">".($rowUnidadFisica['dias_inventario']." días")."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['estado_venta'])."</div>";
					$htmlTb .= (($rowUnidadFisica['estado_venta'] == "RESERVADO" && $rowUnidadFisica['estado_compra'] != "REGISTRADO") ? "<div class=\"textoNegrita_9px\">(".utf8_encode($rowUnidadFisica['estado_compra']).")</div>" : "");
					$htmlTb .= (($rowUnidadFisica['id_activo_fijo'] > 0) ? "<div class=\"textoNegrita_9px\">Código: ".$rowUnidadFisica['id_activo_fijo']."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['idAsignacion'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr align=\"right\">";
						$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
						$htmlTb .= "<td>".$imgModuloDcto."</td>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($rowUnidadFisica['numero_factura_proveedor'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">";
					$htmlTb .= "<div>".number_format($rowUnidadFisica['precio_compra'], 2, ".", ",")."</div>";
					$htmlTb .= (($rowUnidadFisica['costo_agregado'] != 0) ? "<div class=\"".(($rowUnidadFisica['costo_agregado'] > 0) ? "textoVerdeNegrita_10px" : "textoRojoNegrita_10px")."\" title=\"".htmlentities("Total Agregados")."\">[".number_format($rowUnidadFisica['costo_agregado'], 2, ".", ",")."]</div>" : "");
					$htmlTb .= (($rowUnidadFisica['costo_depreciado'] != 0) ? "<div class=\"textoRojoNegrita_10px\" title=\"".htmlentities("Total Depreciación")."\">[-".number_format($rowUnidadFisica['costo_depreciado'], 2, ".", ",")."]</div>" : "");
					$htmlTb .= (($rowUnidadFisica['costo_trade_in'] != 0) ? "<div class=\"".(($rowUnidadFisica['costo_trade_in'] > 0) ? "textoRojoNegrita_10px" : "textoVerdeNegrita_10px")."\" title=\"".htmlentities("Total Depreciación Ingreso por Trade In")."\">[".number_format(((-1) * $rowUnidadFisica['costo_trade_in']), 2, ".", ",")."]</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
				if (!in_array($rowUnidadFisica['estado_venta'],array("ACTIVO FIJO"))) {
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEstadoVenta%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUnidadFisica', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/page_refresh.png\" title=\"Cambiar Estado de Venta\"/></a>",
						$contFila,
						$rowUnidadFisica['id_unidad_fisica'],
						"an_estado_venta_unidad_fisica_form.php");
				}
				$htmlTb .= "</td>";
				$htmlTb .= "<td>";
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEstadoVenta%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUnidadFisica', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_movimiento_almacen.gif\" title=\"Transferencia de Almacén\"/></a>",
						$contFila,
						$rowUnidadFisica['id_unidad_fisica'],
						"an_transferencia_almacen_form.php");
				$htmlTb .= "</td>";
				$htmlTb .= "<td>"."</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotal[13] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">"."Subtotal:<br>".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td>".number_format($contFila2, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
	}
	if ($pageNum == $totalPages) {
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			cond_unidad.descripcion AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
				WHEN (an_ve.fecha IS NOT NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
				(CASE
					WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
					WHEN (an_ve.fecha IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
				END),
			0) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			cxp_fact.id_factura,
			cxp_fact.numero_factura_proveedor,
			cxp_fact.id_modulo,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND an_ve.fecha IS NOT NULL
				AND an_ve.tipo_vale_entrada = 1
				AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s;", $sqlBusq);
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		$contFila2 = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$contFila2++;
			
			$arrayTotalFinal[12] = $contFila2;
			$arrayTotalFinal[13] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">"."Total de Totales:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
			$htmlTb .= "<td colspan=\"3\"></td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"30\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadFisica","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function validarPermiso($frmPermiso) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos WHERE id_usuario = %s AND contrasena = %s AND modulo = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'],"text"),
		valTpDato($frmPermiso['hddModulo'],"text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		switch ($frmPermiso['hddModulo']) {
			case "an_transferencia_almacen_form.php" : $objResponse->script("abrirDivFlotante1(null, 'tblEstadoVenta');"); break;
			case "an_estado_venta_unidad_fisica_form.php" : $objResponse->script("abrirDivFlotante1(null, 'tblEstadoVenta');"); break;
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacen");
$xajax->register(XAJAX_FUNCTION,"cargaLstAlmacenBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstCondicionBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCompraBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoVentaBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarcaModeloVersion");
$xajax->register(XAJAX_FUNCTION,"formEstadoVenta");
$xajax->register(XAJAX_FUNCTION,"formUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"formUnidadFisicaAgregado");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"guardarEstadoVenta");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function buscarEnArray($array, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($array as $indice => $valor) {
		if ($valor == $dato) return $x;
		
		$x++;
	}
	return NULL;
}

function guardarValeEntrada($frmUnidadFisica, $frmEstadoVenta) {
	$idEmpresaSalida = $frmEstadoVenta['txtIdEmpresaValeSalida'];
	$idEmpresaEntrada = $frmEstadoVenta['txtIdEmpresaValeEntrada'];
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idClaveMovimientoSalida = $frmEstadoVenta['lstClaveMovimientoSalida'];
	$idClaveMovimientoEntrada = $frmEstadoVenta['lstClaveMovimientoEntrada'];
	$idUnidadFisica = $frmUnidadFisica['txtIdUnidadFisica'];
	
	///////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////// VALE DE ENTRADA /////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimientoEntrada, "int"),
		valTpDato($idEmpresaEntrada, "int"),
		valTpDato($idEmpresaEntrada, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// REGISTRA EL VALE DE ENTRADA
	$insertSQL = sprintf("INSERT INTO an_vale_entrada (numeracion_vale_entrada, id_empresa, fecha, id_unidad_fisica, id_cliente, subtotal_factura, tipo_vale_entrada, observacion)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActual, "int"),
		valTpDato($idEmpresaEntrada, "int"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato($idUnidadFisica, "int"),
		valTpDato($frmEstadoVenta['hddIdEmpleadoElaborado'], "int"),
		valTpDato($frmEstadoVenta['txtSubTotal'], "real_inglesa"),
		valTpDato(4, "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
		valTpDato($frmEstadoVenta['txtObservacion'], "text"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idValeEntrada = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL MOVIMIENTO DEL ARTICULO
	$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, tipo_documento_movimiento, claveKardex, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idValeEntrada, "int"),
		valTpDato($frmUnidadFisica['hddIdUnidadBasica'], "int"),
		valTpDato($idUnidadFisica, "int"),
		valTpDato($frmEstadoVenta['lstTipoMovimientoEntrada'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato($idClaveMovimientoEntrada, "int"),
		valTpDato(1, "real_inglesa"),
		valTpDato($frmEstadoVenta['txtSubTotal'], "real_inglesa"),
		valTpDato($frmEstadoVenta['txtSubTotal'], "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
		valTpDato("NOW()", "campo"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	return array(true, $idValeEntrada, $idModulo, $script);
}

function guardarValeSalida($frmUnidadFisica, $frmEstadoVenta) {
	$idEmpresaSalida = $frmEstadoVenta['txtIdEmpresaValeSalida'];
	$idEmpresaEntrada = $frmEstadoVenta['txtIdEmpresaValeEntrada'];
	$idModulo = 2; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idClaveMovimientoSalida = $frmEstadoVenta['lstClaveMovimientoSalida'];
	$idClaveMovimientoEntrada = $frmEstadoVenta['lstClaveMovimientoEntrada'];
	$idUnidadFisica = $frmUnidadFisica['txtIdUnidadFisica'];
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////// VALE DE SALIDA /////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
									WHERE clave_mov.id_clave_movimiento = %s)
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato($idClaveMovimientoSalida, "int"),
		valTpDato($idEmpresaSalida, "int"),
		valTpDato($idEmpresaSalida, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	// REGISTRA EL VALE DE SALIDA
	$insertSQL = sprintf("INSERT INTO an_vale_salida (numeracion_vale_salida, id_empresa, fecha, id_unidad_fisica, id_cliente, subtotal_factura, tipo_vale_salida, observacion)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActual, "int"),
		valTpDato($idEmpresaSalida, "int"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato($idUnidadFisica, "int"),
		valTpDato($frmEstadoVenta['hddIdEmpleadoElaborado'], "int"),
		valTpDato($frmEstadoVenta['txtSubTotal'], "real_inglesa"),
		valTpDato(4, "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
		valTpDato($frmEstadoVenta['txtObservacion'], "text"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idValeSalida = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL MOVIMIENTO DEL ARTICULO
	$insertSQL = sprintf("INSERT INTO an_kardex (id_documento, idUnidadBasica, idUnidadFisica, tipoMovimiento, claveKardex, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estadoKardex, fechaMovimiento)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idValeSalida, "int"),
		valTpDato($frmUnidadFisica['hddIdUnidadBasica'], "int"),
		valTpDato($idUnidadFisica, "int"),
		valTpDato($frmEstadoVenta['lstTipoMovimientoSalida'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
		valTpDato($idClaveMovimientoSalida, "int"),
		valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
		valTpDato(1, "real_inglesa"),
		valTpDato($frmEstadoVenta['txtSubTotal'], "real_inglesa"),
		valTpDato($frmEstadoVenta['txtSubTotal'], "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
		valTpDato("NOW()", "campo"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	return array(true, $idValeSalida, $idModulo, $script);
}
?>