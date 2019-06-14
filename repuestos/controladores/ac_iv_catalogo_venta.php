<?php


function asignarCliente($idCliente, $idEmpresa, $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
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
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$idClaveMovimiento = ($idClaveMovimiento == "") ? $rowCliente['id_clave_movimiento_predeterminado'] : $idClaveMovimiento;
	
	if (strtoupper($rowCliente['credito']) == "SI" || $rowCliente['credito'] == 1) {
		$queryClienteCredito = sprintf("SELECT * FROM cj_cc_credito WHERE id_cliente_empresa = %s;",
			valTpDato($rowCliente['id_cliente_empresa'], "int"));
		$rsClienteCredito = mysql_query($queryClienteCredito);
		if (!$rsClienteCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowClienteCredito = mysql_fetch_assoc($rsClienteCredito);
		
		$txtDiasCreditoCliente = ($rowClienteCredito['diascredito'] > 0) ? $rowClienteCredito['diascredito'] : 0;
		
		$fechaVencimiento = suma_fechas(spanDateFormat,date(spanDateFormat),$txtDiasCreditoCliente);
		
		$objResponse->assign("txtDiasCreditoCliente","value",number_format($txtDiasCreditoCliente, 0));
		
		$objResponse->assign("rbtTipoPagoCredito","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = false;");
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "0", "1", $idClaveMovimiento, "onchange=\"xajax_bloquearLstClaveMovimiento(this.value);\""));
		
	} else {
		$fechaVencimiento = date(spanDateFormat);
		
		$objResponse->assign("txtDiasCreditoCliente","value","0");
		
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", $idClaveMovimiento, "onchange=\"xajax_bloquearLstClaveMovimiento(this.value);\""));
	}
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowCliente['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function asignarPrecio($idArticulo, $idPrecio, $precioPredet = false, $precioUnitario = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_articulos_precios
	WHERE id_articulo = %s
		AND id_precio = %s;",
		valTpDato($idArticulo, "int"),
		valTpDato($idPrecio, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdArtPrecio","value",$row['id_articulo_precio']);
	$precioUnitario = ($row['precio'] > 0 && $row['id_precio'] != 6 && $row['id_precio'] != 7 && $row['id_precio'] != 12) ? $row['precio'] : $precioUnitario;
	$objResponse->assign("txtPrecioArt","value",$precioUnitario);
	$objResponse->assign("hddBajarPrecio","value","");
	$objResponse->script("byId('txtPrecioArt').readOnly = true;");
	
	if ($precioPredet == true) {
		$objResponse->assign("hddPrecioArtPredet","value",$precioUnitario);
	}
	
	switch($idPrecio) {
		case 6 :
			$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
			
			$objResponse->assign("tdDesbloquearPrecio","innerHTML","<img id=\"imgDesbloquearPrecio\" src=\"../img/iconos/lock_go.png\" onclick=\"xajax_formValidarPermisoEdicion('iv_catalogo_venta_precio_editado');\"/>");
			/*$objResponse->assign("tdDesbloquearPrecio","innerHTML","<a class=\"modalImg\" id=\"aDesbloquearPrecio\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblPermiso', 'iv_catalogo_venta_precio_editado');\"><img src=\"../img/iconos/lock_go.png\" style=\"cursor:pointer\" title=\"Desbloquear\"/></a>");*/
			break;
		case 7 :
			$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
			
			$objResponse->assign("tdDesbloquearPrecio","innerHTML","<img id=\"imgDesbloquearPrecio\" src=\"../img/iconos/lock_go.png\" onclick=\"xajax_formValidarPermisoEdicion('iv_catalogo_venta_precio_editado_bajar');\"/>");
			/*$objResponse->assign("tdDesbloquearPrecio","innerHTML","<a class=\"modalImg\" id=\"aDesbloquearPrecio\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblPermiso', 'iv_catalogo_venta_precio_editado_bajar');\"><img src=\"../img/iconos/lock_go.png\" style=\"cursor:pointer\" title=\"Desbloquear\"/></a>");*/
			break;
		case 12 :
			$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
			
			$objResponse->assign("tdDesbloquearPrecio","innerHTML","<a class=\"modalImg\" id=\"aDesbloquearPrecio\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblPermiso', 'iv_precio_editado_debajo_costo');\"><img src=\"../img/iconos/lock_go.png\" style=\"cursor:pointer\" title=\"Desbloquear\"/></a>");
			break;
		default :
			$objResponse->assign("txtPrecioArt","value",number_format($precioUnitario, 2, ".", ","));
			
			$objResponse->assign("tdDesbloquearPrecio","innerHTML","");
	}
	
	return $objResponse;
}

function bloquearLstClaveMovimiento($idClaveMovimiento, $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_clave_movimiento
	WHERE id_clave_movimiento = %s;",
		valTpDato($idClaveMovimiento, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['pago_contado'] == 1 && $row['pago_credito'] == 1) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoCredito".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 1 && $row['pago_credito'] == 0) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 0 && $row['pago_credito'] == 1) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoCredito".$nombreObjeto."').checked = true;";
	} else if ($row['pago_contado'] == 0 && $row['pago_credito'] == 0) {
		$accion = "
		byId('rbtTipoPagoCredito".$nombreObjeto."').disabled = true;
		byId('rbtTipoPagoContado".$nombreObjeto."').disabled = false;
		byId('rbtTipoPagoContado".$nombreObjeto."').checked = true;";
	}
	
	$objResponse->script($accion);

	return $objResponse;
}

function buscarCatalogo($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmBuscar['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscar['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscar['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscar['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$busq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$codArticulo,
		$frmBuscar['lstModelo'],
		$frmBuscar['lstUnidadBasica'],
		$frmBuscar['lstMarca'],
		$frmBuscar['lstTipo'],
		$frmBuscar['lstSeccion'],
		$frmBuscar['lstSubSeccion'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaCatalogo(0, "id_articulo", "DESC", $busq));
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstBusq($selIdEmpresa = "", $selIdModelo = "", $selIdSeccion = "", $selIdMarca = "", $selIdTipo = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT DISTINCT id_modelo, nom_modelo FROM vw_iv_modelos ORDER BY nom_modelo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstModelo\" name=\"lstModelo\" onchange=\"xajax_cargaLstUnidadBasica(this.value); byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selIdModelo == $row['id_modelo']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_modelo']."\">".utf8_encode($row['nom_modelo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstModelo","innerHTML",$html);
	
	
	$querySeccion = sprintf("SELECT * FROM iv_secciones ORDER BY descripcion");
	$rsSeccion = mysql_query($querySeccion);
	if (!$rsSeccion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstSeccion\" name=\"lstSeccion\" onchange=\"xajax_cargaLstSubSeccion(this.value); byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowSeccion = mysql_fetch_assoc($rsSeccion)) {
		$selected = ($selIdSeccion == $rowSeccion['id_seccion']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$rowSeccion['id_seccion']."\">".utf8_encode($rowSeccion['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSeccion","innerHTML",$html);
	
	
	$query = sprintf("SELECT * FROM iv_marcas ORDER BY marca");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstMarca\" name=\"lstMarca\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selIdMarca == $row['id_marca']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_marca']."\">".utf8_encode($row['marca'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMarca","innerHTML",$html);
	
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY id_tipo_articulo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstTipo\" name=\"lstTipo\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selIdTipo == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_articulo']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipo","innerHTML",$html);
	
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
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Cargo, 5 = Vale Salida, 6 = Vale Entrada
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
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
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
			
			$selected = "";
			if ($selId == $rowClaveMov['id_clave_movimiento']) {
				$selected = "selected=\"selected\"";
				
				$objResponse->loadCommands(bloquearLstClaveMovimiento($rowClaveMov['id_clave_movimiento']));
			}
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['idmoneda']) {
			$selected = "selected=\"selected\"";
		} else if ($row['predeterminada'] == 1 && $selId == "") {
			$selected = "selected=\"selected\"";
		}
		
		$html .= "<option ".$selected." value=\"".$row['idmoneda']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMoneda","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstSubSeccion($idSeccion, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$querySubSeccion = sprintf("SELECT * FROM iv_subsecciones WHERE id_seccion = %s", valTpDato($idSeccion, "int"));
	$rsSubSeccion = mysql_query($querySubSeccion);
	if (!$rsSubSeccion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstSubSeccion\" name=\"lstSubSeccion\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowSubSeccion = mysql_fetch_assoc($rsSubSeccion)) {
		$selected = ($selId == $rowSubSeccion['id_subseccion']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$rowSubSeccion['id_subseccion']."\">".utf8_encode($rowSubSeccion['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSubSeccion","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstUnidadBasica($idModelo, $selId = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT * FROM vw_iv_modelos
	WHERE id_modelo = %s
	ORDER BY nom_uni_bas",
		valTpDato($idModelo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstUnidadBasica\" name=\"lstUnidadBasica\" onchange=\"byId('btnBuscar').click();\" style=\"width:200px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_uni_bas']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_uni_bas']."\">".utf8_encode($row['nom_uni_bas'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstUnidadBasica","innerHTML",$html);
	
	return $objResponse;
}

function eliminarArticuloPreseleccionado($idPreseleccionVenta) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if (isset($idPreseleccionVenta)) {
		$deleteSQL = sprintf("DELETE FROM iv_preseleccion_venta WHERE id_preseleccion_venta = %s",
			valTpDato($idPreseleccionVenta, "int"));
		
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaArticuloPreseleccionado());
	
	return $objResponse;
}

function formArticuloPreseleccionado($idArticulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmArticuloPreseleccionado'].reset();
	
	byId('txtCantidadArt').className = 'inputInicial';
	byId('txtPrecioArt').className = 'inputInicial';
	
	byId('txtPrecioArt').readOnly = true;");
	
	$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos WHERE id_articulo = %s",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$objResponse->assign("hddIdArticulo","value",$idArticulo);
	$objResponse->assign("txtCodigoArt","value",elimCaracter($rowArticulo['codigo_articulo'],";"));
	$idPrecioSelec = $rowArticulo['id_precio_predeterminado'];
	$objResponse->loadCommands(asignarPrecio($idArticulo, $idPrecioSelec));
	
	// CARGA LAS EMPRESAS QUE TIENEN EL ARTICULO
	$query = sprintf("SELECT DISTINCT
		vw_iv_art_emp.id_empresa,
		(SELECT pg.nombre_empresa FROM pg_empresa pg WHERE pg.id_empresa= vw_iv_art_emp.id_empresa) AS nombre_empresa
	FROM pg_usuario_empresa
		INNER JOIN vw_iv_articulos_empresa vw_iv_art_emp ON (pg_usuario_empresa.id_empresa = vw_iv_art_emp.id_empresa)
	WHERE id_articulo = %s
		AND pg_usuario_empresa.id_usuario = %s
	ORDER BY nombre_empresa",
		valTpDato($idArticulo, "int"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$query);
	$htmlLstIniEmp = "<select id=\"lstEmpresaArt\" name=\"lstEmpresaArt\"byId('hddIdEmpresa').value = this.value;\" style=\"width:200px\">";
		$htmlEmp .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($_SESSION['idEmpresaUsuarioSysGts'] == $row['id_empresa']) {
			$selected = "selected=\"selected\"";
			$objResponse->assign("hddIdEmpresa","value",$row['id_empresa']);
		}
		
		$htmlEmp .= "<option ".$selected." value=\"".$row['id_empresa']."\">".utf8_encode($row['nombre_empresa'])."</option>";
	}
	$htmlLstFinEmp = "</select>";
	$objResponse->assign("tdlstEmpresaArt","innerHTML",$htmlLstIniEmp.$htmlEmp.$htmlLstFinEmp);
	
	
	// CARGA LOS PRECIOS DEL ARTICULO
	$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.estatus = 1 ORDER BY precio.porcentaje DESC, precio.id_precio ASC;");
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$htmlLst = "";
	while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
		$selected = "";
		if (($selId == $rowPrecio['id_precio'] && $selId != "") || $rowPrecio['id_precio'] == $idPrecioSelec) {
			$selected = "selected=\"selected\"";
			
			if ($rowPrecio['id_precio'] == $idPrecioSelec)
				$valorSelecPred = $rowPrecio['id_precio'];
		}
		
		$htmlLst .= "<option ".$selected." value=\"".$rowPrecio['id_precio']."\">".utf8_encode($rowPrecio['descripcion_precio'])."</option>";
	}
	
	$onChange = "
	if (this.value != 6 && this.value != 7 && this.value != 12 && this.value != ".$idPrecioSelec."){
		xajax_formValidarPermisoEdicion('iv_catalogo_venta_precio_venta');
		selectedOption(this.id,'".$valorSelecPred."');
	}
	xajax_asignarPrecio('".$idArticulo."',this.value);";
	
	$htmlLstIni = "<select id=\"lstPrecioArt\" name=\"lstPrecioArt\" onchange=\"".$onChange."\" style=\"width:200px\">";
	$htmlLstFin = "</select>";
	
	$objResponse->assign("tdlstPrecioArt","innerHTML",$htmlLstIni.$htmlLst.$htmlLstFin);
	
	$objResponse->script("
	byId('tblPermiso').style.display = 'none';
	byId('tblArticuloPreseleccionado').style.display = '';
	byId('tblDcto').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Preseleccionar Artículo");
	$objResponse->script("
	byId('divFlotante').style.display='';
	centrarDiv(byId('divFlotante'));
	
	byId('txtCantidadArt').focus();");
	
	return $objResponse;
}

function formPresupuesto($frmListaArticuloPreseleccionado) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	if (isset($frmListaArticuloPreseleccionado['cbxArtPreselec'])) {
		$objResponse->assign("rbtTipoPagoContado","checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito').disabled = true;");
		
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", "", "onchange=\"xajax_bloquearLstClaveMovimiento(this.value);\""));
	
		foreach ($frmListaArticuloPreseleccionado['cbxArtPreselec'] as $indice => $valor) {
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			$contFila++;
			
			$query = sprintf("SELECT * FROM vw_iv_preseleccion_venta WHERE id_preseleccion_venta = %s;",
				valTpDato($valor, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= sprintf("<td><input type=\"hidden\" id=\"hddIdPreseleccionVenta%s\" name=\"hddIdPreseleccionVenta%s\" value=\"%s\">%s</td>",
					$contFila,
					$contFila,
					$row['id_preseleccion_venta'],
					elimCaracter($row['codigo_articulo'],";"));
				$htmlTb .= sprintf("<td align=\"center\">%s</td>",$row['cantidad']);
				$htmlTb .= sprintf("<td align=\"right\">%s</td>",number_format($row['precio_unitario'], 2, ".", ","));
				$htmlTb .= sprintf("<td align=\"center\">%s</td>", cargaLstIva($contFila));
				$htmlTb .= sprintf("<td align=\"right\">%s</td>",number_format(($row['cantidad']*$row['precio_unitario']), 2, ".", ","));
			$htmlTb .= "</tr>";
			
			$subTotal += $row['cantidad']*$row['precio_unitario'];
			
			if ($contFila == 1) {
				$idEmpresa = $row['id_empresa_reg'];
			} else {
				if ($idEmpresa != $row['id_empresa_reg']) {
					$objResponse->alert("Para poder crear un presupuesto los articulos seleccionados deben pertenecer a la misma empresa");
					return $objResponse;
				}	
			}
		}
		
		$objResponse->loadCommands(cargaLstMoneda());
	
		$objResponse->script("
		byId('tblArticuloPreseleccionado').style.display='none';
		byId('tblDcto').style.display='';
		
		document.forms['frmDcto'].reset();
		
		byId('txtIdCliente').className = 'inputInicial';
		byId('txtNumeroSiniestro').className = 'inputHabilitado';
		
		byId('tdFechaVencimiento').style.display = '';
		byId('tdFechaVencimientoObj').style.display = '';
		
		byId('btnAceptarDcto').onclick = function() { validarFrmDcto(); }");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Guardar Presupuesto");
		$objResponse->script("
		byId('divFlotante').style.display='';
		centrarDiv(byId('divFlotante'));");
		
		$htmlTblIni = "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">
			<td width=\"32%\">".("Código")."</td>
			<td width=\"12%\">".("Catidad")."</td>
			<td width=\"12%\">".($spanPrecioUnitario)."</td>
			<td width=\"24%\">".("Impuesto")."</td>
			<td width=\"20%\">".("Total")."</td>
		</tr>";
		$htmlTblFin = "</table>";
		
		$objResponse->assign("tdListadoArticulosPresupuesto","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa", ""));
		$objResponse->assign("txtFechaPresupuesto","value",date(spanDateFormat));
		$objResponse->assign("txtFechaVencimientoPresupuesto","value",date(spanDateFormat,dateAddLab(time(),5,true)));
		
		$objResponse->assign("txtSubTotal","value", number_format($subTotal, 2, ".", ","));
	} else {
		$objResponse->alert("Debe seleccionar articulos para poder crear un presupuesto");
	}
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmPermiso'].reset();
	
	byId('txtContrasena').className = 'inputInicial';");
	
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	$objResponse->script("
	byId('tblPermiso').style.display = '';
	byId('tblArticuloPreseleccionado').style.display = 'none';
	byId('tblDcto').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Ingreso de Clave Especial");
	$objResponse->script("		
	byId('divFlotante').style.display = '';
	centrarDiv(byId('divFlotante'));
	
	byId('txtContrasena').focus();");
	
	return $objResponse;
}

function guardarArticuloPreseleccionado($frmArticuloPreseleccionado) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$insertSQL = sprintf("INSERT INTO iv_preseleccion_venta (id_usuario, id_articulo, id_precio, precio_unitario, id_empresa, cantidad, fecha)
	VALUE (%s, %s, %s, %s, %s, %s, %s);",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmArticuloPreseleccionado['hddIdArticulo'], "int"),
		valTpDato($frmArticuloPreseleccionado['lstPrecioArt'], "int"),
		valTpDato($frmArticuloPreseleccionado['txtPrecioArt'], "double"),
		valTpDato($frmArticuloPreseleccionado['hddIdEmpresa'], "int"),
		valTpDato($frmArticuloPreseleccionado['txtCantidadArt'], "int"),
		valTpDato(date("Y-m-d"), "date"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('divFlotante').style.display = 'none';");
	
	$objResponse->loadCommands(listaArticuloPreseleccionado());
	
	$objResponse->alert("Artículo agregado con Éxito");
	
	return $objResponse;
}

function guardarPresupuesto($frmDcto) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT *
	FROM pg_empresa_numeracion emp_num
		INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
	WHERE emp_num.id_numeracion = %s
		AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																						WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC LIMIT 1;",
		valTpDato(21, "int"), // 21 = Presupuesto Venta Repuestos
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$idNumeraciones = $rowNumeracion['id_numeracion'];
	$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// INSERTA LOS DATOS DEL PRESUPUESTO
	$insertSQL = sprintf("INSERT INTO iv_presupuesto_venta (numeracion_presupuesto, numero_siniestro, id_empresa, id_cliente, fecha, fecha_vencimiento, id_moneda, condicion_pago, id_clave_movimiento, estatus_presupuesto_venta, id_modulo, id_empleado_preparador, subtotal)
	VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($numeroActual, "text"),
		valTpDato($frmDcto['txtNumeroSiniestro'], "text"),
		valTpDato($frmDcto['txtIdEmpresa'], "int"),
		valTpDato($frmDcto['txtIdCliente'], "int"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaPresupuesto'])), "date"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaVencimientoPresupuesto'])), "date"),
		valTpDato($frmDcto['lstMoneda'], "int"),
		valTpDato($frmDcto['rbtTipoPago'], "int"),
		valTpDato($frmDcto['lstClaveMovimiento'], "int"),
		valTpDato(0, "int"), // 0 = Pendiente, 1 = Pedido
		valTpDato(0, "int"), // 0 = Repuestos, 1 = Servicios, 2 = Vehiculos, 3 = Administracion
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
		valTpDato($frmDcto['txtSubTotal'], "real_inglesa"));
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$idDocumentoVenta = mysql_insert_id();
	
	for ($cont = 1; isset($frmDcto['hddIdPreseleccionVenta'.$cont]); $cont++) {
		$query = sprintf("SELECT * FROM iv_preseleccion_venta WHERE id_preseleccion_venta = %s",
			valTpDato($frmDcto['hddIdPreseleccionVenta'.$cont], "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$idArticulo = $row['id_articulo'];
		$idPrecio = $row['id_precio'];
		
		// BUSCA LOS DATOS DEL IMPUESTO QUE TIENE EL ARTICULO
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = %s;", valTpDato($frmDcto['lstIvaArt'.$cont], "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowIva = mysql_fetch_assoc($rsIva);
		
		if (strlen($row['id_articulo']) > 0) {
			$insertSQL = sprintf("INSERT INTO iv_presupuesto_venta_detalle (id_presupuesto_venta, id_articulo, cantidad, pendiente, id_precio, precio_unitario, id_iva, iva)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
				valTpDato($idDocumentoVenta, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($row['cantidad'], "int"),
				valTpDato($row['cantidad'], "int"),
				valTpDato($idPrecio, "int"),
				valTpDato($row['precio_unitario'], "real_inglesa"),
				valTpDato($rowIva['idIva'], "int"),
				valTpDato($rowIva['iva'], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		}
		
		$deleteSQL = sprintf("DELETE FROM iv_preseleccion_venta WHERE id_preseleccion_venta = %s",
			valTpDato($frmDcto['hddIdPreseleccionVenta'.$cont], "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->script(sprintf("window.open('iv_presupuesto_venta_form.php?id=%s','_self');", $idDocumentoVenta));
	
	return $objResponse;
}

function listaArticuloPreseleccionado() {
	$objResponse = new xajaxResponse();
	
	$sqlBusq = sprintf(" WHERE id_usuario = %s", valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	
	$query = sprintf("SELECT * FROM vw_iv_preseleccion_venta %s", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	
	$htmlTblIni .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";	
	while ($row = mysql_fetch_assoc($rs)) {
		$claseLinea = ($clase == "trResaltar4") ? "trResaltar5Linea" : "trResaltar4"; 
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = "<br>".utf8_encode($row['nombre_empresa_suc']." (".$row['sucursal'].")");
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= sprintf("<td rowspan=\"3\" valign=\"top\"><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td align=\"center\"><input type=\"checkbox\" id=\"cbxArtPreselec\" name=\"cbxArtPreselec[]\" value=\"%s\"/></td></tr><tr><td>&nbsp;</td></tr><tr><td align=\"center\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" onclick=\"validarEliminarArticuloPreseleccionado('%s');\"/></td></tr></table></td>",
						$row['id_preseleccion_venta'],
						$row['id_preseleccion_venta']);
					$htmlTb .= sprintf("<td class=\"textoNegrita_10px\" colspan=\"2\">%s</td>",
						elimCaracter($row['codigo_articulo'],";"));
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr>";
					$htmlTb .= sprintf("<td width=\"%s\">Cant.: %s</td>", "50%",utf8_encode($row['cantidad']));
					$htmlTb .= sprintf("<td align=\"right\" width=\"%s\">%s</td>", "50%",utf8_encode($row['precio_unitario']));
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr>";
					$htmlTb .= sprintf("<td class=\"texto_9px\" colspan=\"2\" width=\"%s\">%s</td>", "50%",utf8_encode($row['nombre_empresa']).$nombreSucursal);
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)){
		$htmlTb .= "<td colspan=\"10\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoArticulosPreseleccionados","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaCatalogo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = vw_iv_art.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		
		if ($valCadBusq[3] == "-1" || $valCadBusq[3] == "") {
			$sqlBusq .= $cond.sprintf("(SELECT DISTINCT COUNT(art_modelo_comp.id_articulo)
					FROM vw_iv_modelos vw_iv_modelo
						INNER JOIN iv_articulos_modelos_compatibles art_modelo_comp ON (vw_iv_modelo.id_uni_bas = art_modelo_comp.id_unidad_basica)
					WHERE art_modelo_comp.id_articulo = vw_iv_art.id_articulo
						AND vw_iv_modelo.id_modelo = %s) > 0",
				valTpDato($valCadBusq[2], "int"));
		}
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT DISTINCT COUNT(art_modelo_comp.id_articulo)
				FROM iv_articulos_modelos_compatibles art_modelo_comp
				WHERE art_modelo_comp.id_articulo = vw_iv_art.id_articulo
					AND art_modelo_comp.id_unidad_basica = %s) > 0",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art.id_marca = %s", valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art.id_tipo_articulo = %s", valTpDato($valCadBusq[5], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT subseccion.id_seccion FROM iv_subsecciones subseccion WHERE subseccion.id_subseccion = vw_iv_art.id_subseccion) = %s",
			valTpDato($valCadBusq[6], "int"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art.id_subseccion = %s", valTpDato($valCadBusq[7], "int"));
	}
	
	if ($valCadBusq[8] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_art.id_articulo = %s
		OR vw_iv_art.codigo_articulo_prov LIKE %s
		OR vw_iv_art.descripcion LIKE %s)",
			valTpDato($valCadBusq[8], "int"),
			valTpDato("%".$valCadBusq[8]."%", "text"),
			valTpDato("%".$valCadBusq[8]."%", "text"));
	}
	
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") { // POR EMPRESA
		$query = sprintf("SELECT
			(SELECT marca.marca FROM iv_marcas marca
			WHERE marca.id_marca = vw_iv_art.id_marca) AS marca,
			
			(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
			WHERE tipo_art.id_tipo_articulo = vw_iv_art.id_tipo_articulo) AS tipo_articulo,
			
			stock_maximo,
			stock_minimo,
			clasificacion,
			id_articulo,
			codigo_articulo,
			descripcion,
			codigo_articulo_prov,
			existencia,
			
			(SELECT art.foto FROM iv_articulos art WHERE art.id_articulo = vw_iv_art.id_articulo) AS foto,
			
			SUM(vw_iv_art.cantidad_reservada) AS cantidad_reservada,
			SUM(vw_iv_art.cantidad_disponible_fisica) AS cantidad_disponible_fisica,
			SUM(vw_iv_art.cantidad_espera) AS cantidad_espera,
			SUM(vw_iv_art.cantidad_bloqueada) AS cantidad_bloqueada,
			SUM(vw_iv_art.cantidad_disponible_logica) AS cantidad_disponible_logica,
			SUM(vw_iv_art.cantidad_pedida) AS cantidad_pedida,
			SUM(vw_iv_art.cantidad_futura) AS cantidad_futura
		FROM vw_iv_articulos_empresa vw_iv_art %s
		GROUP BY stock_maximo,
			stock_minimo,
			clasificacion,
			id_articulo,
			codigo_articulo,
			descripcion,
			codigo_articulo_prov,
			existencia,
			cantidad_reservada,
			cantidad_disponible_fisica,
			cantidad_espera,
			cantidad_bloqueada,
			cantidad_disponible_logica,
			cantidad_pedida,
			cantidad_futura", $sqlBusq);
	} else { // TODOS
		$query = sprintf("SELECT *
		FROM vw_iv_articulos vw_iv_art %s", $sqlBusq);
	}
	
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";	
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$claseLinea = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5Linea";
		$contFila++;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_iv_art_emp.id_articulo = %s",
			valTpDato($row['id_articulo'], "int"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_iv_art_emp.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_iv_art_emp.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		$queryEmpArt = sprintf("SELECT
			cantidad_disponible_logica,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_iv_articulos_empresa vw_iv_art_emp
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_iv_art_emp.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq2);
		$rsEmpArt = mysql_query($queryEmpArt);
		if (!$rsEmpArt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRowsEmpArt = mysql_num_rows($rsEmpArt);
		
		$style = ($totalRowsEmpArt > 0) ? "style=\"border:1px solid #999999; background-color:#FFFFFF\"" : "";
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['foto'];
				
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\" class=\"".$claseLinea."\">";
				$htmlTb .= "<table border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"center\" width=\"16%\">";
						$htmlTb .= sprintf("<a href=\"%s\" id=\"mb%s\" class=\"mb\" title=\"%s\"><img class=\"imgBorde\" src=\"%s\" border=\"0\" width=\"90\" /></a>",
							$imgFoto,
							$contFila,
							elimCaracter($row['codigo_articulo'],";"),
							$imgFoto);
						$htmlTb .= sprintf("<div class=\"multiBoxDesc mb%s\">&nbsp;<br></div>", $contFila);
					$htmlTb .= "</td>";
					$htmlTb .= "<td valign=\"top\">";
						$htmlTb .= "<table border=\"0\" width=\"100%\">";
						$htmlTb .= "<tr>";
							$htmlTb .= sprintf("<td class=\"textoAzulNegrita_12px\" colspan=\"2\">%s</td>",
								elimCaracter($row['codigo_articulo'],";"));
						$htmlTb .= "</tr>";
						$htmlTb .= "<tr>";
							$htmlTb .= sprintf("<td width=\"%s\">%s</td>", "82%", utf8_encode($row['descripcion']));
							$htmlTb .= sprintf("<td valign=\"top\" width=\"%s\">%s<br>(%s)</td>",
								"18%",
								utf8_encode($row['marca']),
								utf8_encode($row['tipo_articulo']));
						$htmlTb .= "</tr>";
						$htmlTb .= "<tr>";
							$htmlTb .= "<td class=\"texto_9px\" ".$style.">";
								$htmlTb .= "<table border=\"0\" width=\"100%\">";
								while ($rowEmpArt = mysql_fetch_assoc($rsEmpArt)) {
									$claseEmpArt = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
									$contFila2++;
									
									$htmlTb .= "<tr class=\"".$claseEmpArt."\">";
										$htmlTb .= sprintf("<td width=\"%s\">%s</td>", "82%", $rowEmpArt['nombre_empresa']);
										$htmlTb .= sprintf("<td align=\"right\" class=\"textoGrisNegrita_9px\" width=\"%s\">%s</td>", "10%", "Disponibilidad:");
										$htmlTb .= sprintf("<td align=\"right\" width=\"%s\">%s</td>", "8%", $rowEmpArt['cantidad_disponible_logica']);
									$htmlTb .= "</tr>";
								}
								$htmlTb .= "</table>";
							$htmlTb .= "</td>";
							$htmlTb .= "<td align=\"right\" valign=\"bottom\">";
								$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_formArticuloPreseleccionado('%s');\" src=\"../img/iconos/basket_add.png\" title=\"Agregar al Carrito de Compra\" />",
									$row['id_articulo']);
							$htmlTb .= "</td>";
						$htmlTb .= "</tr>";
						$htmlTb .= "</table>";
					$htmlTb .= "</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCatalogo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCatalogo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
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
	
	if (!($totalRows > 0)){
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoArticulos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("
		var box = {};
		window.addEvent('domready', function(){
			box = new MultiBox('mb', {descClassName: 'multiBoxDesc', useOverlay: true});
		});");
	
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
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lci, ci) LIKE %s
		OR CONCAT_WS('', lci, ci) LIKE %s
		OR CONCAT_Ws(' ', nombre, apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.credito
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("Código"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".(strtoupper($arrayTipoPago[strtoupper($row['credito'])]))."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult.gif\"/>");
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
	
	$objResponse->assign("tdListado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Clientes");
	$objResponse->assign("tblLista","width","760");
	$objResponse->script("
	if (byId('divFlotante2').style.display == 'none') {
		byId('divFlotante2').style.display='';
		centrarDiv(byId('divFlotante2'));
		
		byId('txtCriterioBuscarCliente').focus();
	}");
	
	return $objResponse;
}

function validarPermiso($frmPermiso, $frmArticuloPreseleccionado) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM vw_pg_claves_modulos
	WHERE id_usuario = %s
		AND contrasena = %s
		AND modulo LIKE %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmPermiso['txtContrasena'], "text"),
		valTpDato($frmPermiso['hddModulo'], "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
			
	if ($rowPermiso['id_clave_usuario'] != "") {
		if ($frmPermiso['hddModulo'] == "iv_catalogo_venta_precio_editado") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			$objResponse->script("byId('txtPrecioArt').readOnly = false;");
			$objResponse->script("byId('imgDesbloquearPrecio').style.display = 'none';");
			$objResponse->script("byId('txtPrecioArt').focus();");
			
		} else if ($frmPermiso['hddModulo'] == "iv_catalogo_venta_precio_editado_bajar") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			$objResponse->script("byId('txtPrecioArt').readOnly = false;");
			$objResponse->script("byId('imgDesbloquearPrecio').style.display = 'none';");
			$objResponse->script("byId('txtPrecioArt').focus();");
			
		} else if ($frmPermiso['hddModulo'] == "iv_precio_editado_debajo_costo") {
			$objResponse->assign("hddBajarPrecio","value",true);
			$objResponse->script("byId('txtPrecioArt').readOnly = false;");
			$objResponse->script("byId('aDesbloquearPrecio').style.display = 'none';");
			$objResponse->script("
			byId('txtPrecioArt').focus();
			byId('txtPrecioArt').select();");
			
		} else if ($frmPermiso['hddModulo'] == "iv_catalogo_venta_precio_venta") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			$objResponse->script(sprintf("byId('lstPrecioArt').onchange = function(){ xajax_asignarPrecio('%s',this.value); }",
				$frmArticuloPreseleccionado['hddIdArticulo']));
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarPrecio");
$xajax->register(XAJAX_FUNCTION,"bloquearLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"buscarCatalogo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");

$xajax->register(XAJAX_FUNCTION,"cargaLstBusq");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstSubSeccion");
$xajax->register(XAJAX_FUNCTION,"cargaLstUnidadBasica");

$xajax->register(XAJAX_FUNCTION,"eliminarArticuloPreseleccionado");

$xajax->register(XAJAX_FUNCTION,"formArticuloPreseleccionado");
$xajax->register(XAJAX_FUNCTION,"formPresupuesto");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");

$xajax->register(XAJAX_FUNCTION,"guardarArticuloPreseleccionado");
$xajax->register(XAJAX_FUNCTION,"guardarPresupuesto");

$xajax->register(XAJAX_FUNCTION,"listaArticuloPreseleccionado");
$xajax->register(XAJAX_FUNCTION,"listaCatalogo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");

$xajax->register(XAJAX_FUNCTION,"validarPermiso");


function cargaLstIva($id, $selId = "") {
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6,9,2) AND iva.estado = 1 ORDER BY iva");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstIvaArt".$id."\" name=\"lstIvaArt".$id."\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
	$selected = "";
	if ($selId == 0 && $selId != "") {
		$selected = "selected=\"selected\"";
		$opt = "Si";
	}
		$html .= "<option ".$selected." value=\"0\">NA</option>";
	
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if ($selVal == $rowIva['iva'] && $selId == $rowIva['idIva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if ($selVal == $rowIva['iva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if ($selId == $rowIva['idIva'] && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			} else if (($rowIva['tipo'] == 6 && $rowIva['activo'] == 1) && $opt == "") {
				$selected = "selected=\"selected\"";
				$opt = "Si";
			}
			
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
			
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	
	return $html;
}
?>