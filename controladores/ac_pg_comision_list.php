<?php


function buscarAccesorio($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterioBuscarArticulo']);
	
	$objResponse->loadCommands(listaAccesorio(0, "nom_accesorio", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarComision($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresaBuscar'],
		(is_array($frmBuscar['lstCargoBuscar']) ? implode(",",$frmBuscar['lstCargoBuscar']) : $frmBuscar['lstCargoBuscar']),
		(is_array($frmBuscar['lstModuloBuscar']) ? implode(",",$frmBuscar['lstModuloBuscar']) : $frmBuscar['lstModuloBuscar']),
		(is_array($frmBuscar['lstTipoComisionBuscar']) ? implode(",",$frmBuscar['lstTipoComisionBuscar']) : $frmBuscar['lstTipoComisionBuscar']),
		(is_array($frmBuscar['lstTipoPorcentajeBuscar']) ? implode(",",$frmBuscar['lstTipoPorcentajeBuscar']) : $frmBuscar['lstTipoPorcentajeBuscar']),
		(is_array($frmBuscar['lstModoComisionBuscar']) ? implode(",",$frmBuscar['lstModoComisionBuscar']) : $frmBuscar['lstModoComisionBuscar']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaComision(0, "nombre_cargo", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listaEmpresa(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarTipoOrden($frmBuscarTipoOrden) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarTipoOrden['txtCriterioBuscarTipoOrden']);
	
	$objResponse->loadCommands(listaTipoOrden(0, "id_filtro_orden", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstCargo($idDepartamento, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT 
		cargo_dep.id_cargo_departamento,
		cargo.nombre_cargo
	FROM pg_cargo_departamento cargo_dep
		INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
	WHERE cargo_dep.id_departamento = %s ORDER BY nombre_cargo", valTpDato($idDepartamento, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstCargo\" name=\"lstCargo\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = ($selId == $row['id_cargo_departamento']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_cargo_departamento']."\" ".$seleccion.">".utf8_encode($row['nombre_cargo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCargo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCargoBuscar($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("dep.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	}
	
	$query = sprintf("SELECT DISTINCT 
		cargo.id_cargo,
		cargo.nombre_cargo
	FROM pg_cargo_departamento cargo_dep
		INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
		INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento) %s
	ORDER BY nombre_cargo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstCargoBuscar\" name=\"lstCargoBuscar\" class=\"inputCompletoHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = ($selId == $row['id_cargo']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_cargo']."\" ".$seleccion.">".utf8_encode($row['nombre_cargo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCargoBuscar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstDepartamento($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_departamento WHERE id_empresa = %s ORDER BY nombre_departamento", valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstDepartamento\" name=\"lstDepartamento\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstCargo(this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = ($selId == $row['id_departamento']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_departamento']."\" ".$seleccion.">".utf8_encode($row['nombre_departamento'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstDepartamento","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstModulo\" name=\"lstModulo\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_enlace_concepto']) ? "selected='selected'" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_enlace_concepto']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModuloBuscar($selId = ""){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM pg_modulos ORDER BY descripcionModulo");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstModuloBuscar\" name=\"lstModuloBuscar\" class=\"inputCompletoHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_enlace_concepto']) ? "selected='selected'" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_enlace_concepto']."\">".utf8_encode($row['descripcionModulo'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstModuloBuscar","innerHTML",$html);

	return $objResponse;
}

function cargaLstTipoComision($selId = "") {
	$objResponse = new xajaxResponse();
	
	$array[1] = "M.O";			$array[2] = "T.O.T.";		$array[3] = "Nota";			$array[4] = "Repuesto";
	$array[5] = "Vehículo";		$array[6] = "Accesorio";	$array[7] = "Arbitrario";	$array[8] = "Facturado";
	
	$html = "<select id=\"lstTipoComision\" name=\"lstTipoComision\" onchange=\"asignarTipoPorcentaje();\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".$indice.".- ".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoComision","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoComisionBuscar($selId = "") {
	$objResponse = new xajaxResponse();
	
	$array[1] = "M.O";			$array[2] = "T.O.T.";		$array[3] = "Nota";			$array[4] = "Repuesto";
	$array[5] = "Vehículo";		$array[6] = "Accesorio";	$array[7] = "Arbitrario";	$array[8] = "Facturado";
	$totalRows = count($array);
	
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstTipoComisionBuscar\" name=\"lstTipoComisionBuscar\" class=\"inputCompletoHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($indice)."\">".$indice.".- ".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoComisionBuscar","innerHTML",$html);
	
	return $objResponse;
}

function editarNivelProductividad($frmNivelProductiviada){
	$objResponse = new xajaxResponse();
	
	$idItems = $frmNivelProductiviada["hddIdNivelProductividad"];
	
	$objResponse->assign("hddPorcMayor".$idItems,"value",$frmNivelProductiviada["txtProductividadMayor"]);
	$objResponse->assign("divMayor".$idItems,"innerHTML",$frmNivelProductiviada["txtProductividadMayor"]."%");
	$objResponse->assign("hddPorcMenor".$idItems,"value",$frmNivelProductiviada["txtProductividadMenor"]);
	$objResponse->assign("divMenor".$idItems,"innerHTML",$frmNivelProductiviada["txtProductividadMenor"]."%");
	$objResponse->assign("hddPorc".$idItems,"value",$frmNivelProductiviada["txtPorcentajeProductividad"]);
	$objResponse->assign("divNivelPorcentaje".$idItems,"innerHTML",$frmNivelProductiviada["txtPorcentajeProductividad"]."%");
	
	$objResponse->script("byId('btnCancelarNivelProductividad').click();");
	
	return $objResponse;
}

function editarNivelProdUnidad($frmNivelProdUnidad){
	$objResponse = new xajaxResponse();
	
	$idItems = $frmNivelProdUnidad["hddIdNivelProductividadUnidad"];
	
	$arrayLisTipo = array('-1' => "-", 0 => "Todo", 1 => "Nuevo", 2 => "Usado",3 => "Usado Particular");
	$arrayLisTipo2 = array('-1' => "-", 0 => "Todo", 1 => "Nuevo", 2 => "Usado",3 => "Usado Particular");

	$objResponse->assign("hddTipo".$idItems,"value",$frmNivelProdUnidad["slctTipo"]);
	$objResponse->assign("divTipo".$idItems,"innerHTML",$arrayLisTipo[$frmNivelProdUnidad["slctTipo"]]);
	$objResponse->assign("hddMayor".$idItems,"value",$frmNivelProdUnidad["txtMayoIgual"]);
	$objResponse->assign("divMayor".$idItems,"innerHTML",number_format($mayor = ($frmNivelProdUnidad["txtMayoIgual"] == "") ? "0" : $frmNivelProdUnidad["txtMayoIgual"], 2, ".", ","));
	$objResponse->assign("hddMenor".$idItems,"value",$frmNivelProdUnidad["txtMenorIgual"]);
	$objResponse->assign("divMenor".$idItems,"innerHTML",number_format($menor = ($frmNivelProdUnidad["txtMenorIgual"] == "") ? "0" : $frmNivelProdUnidad["txtMenorIgual"], 2, ".", ","));
	$objResponse->assign("hddTipo2".$idItems,"value",$frmNivelProdUnidad["slctTipo2"]);
	$objResponse->assign("divTipo2".$idItems,"innerHTML",$arrayLisTipo2[$frmNivelProdUnidad["slctTipo2"]]);
	$objResponse->assign("hddMayor2".$idItems,"value",$frmNivelProdUnidad["txtMayoIgual2"]);
	$objResponse->assign("divMayor2".$idItems,"innerHTML",number_format($mayor2 = ($frmNivelProdUnidad["txtMayoIgual2"] == "") ? "0" : $frmNivelProdUnidad["txtMayoIgual2"], 2, ".", ","));
	$objResponse->assign("hddMenor2".$idItems,"value",$frmNivelProdUnidad["txtMenorIgual2"]);
	$objResponse->assign("divMenor2".$idItems,"innerHTML",number_format($menor2 = ($frmNivelProdUnidad["txtMenorIgual2"] == "") ? "0" : $frmNivelProdUnidad["txtMenorIgual2"], 2, ".", ","));
	$objResponse->assign("hddPorcent".$idItems,"value",$frmNivelProdUnidad["txPorcentaje"]);
	$objResponse->assign("divPorcent".$idItems,"innerHTML",number_format($frmNivelProdUnidad["txPorcentaje"], 2, ".", ","));
	$objResponse->script("byId('btnCnlNivelProducUnidad').click();");
	
	return $objResponse;
}

function eliminarComision($idComision, $frmListaComision) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"pg_comision_list","eliminar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$deleteSQL = sprintf("DELETE FROM pg_comision WHERE id_comision = %s",
		valTpDato($idComision, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaComision(
		$frmListaComision['pageNum'],
		$frmListaComision['campOrd'],
		$frmListaComision['tpOrd'],
		$frmListaComision['valBusq']));
	
	return $objResponse;
}

function eliminarComisionArticulo($frmComision) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmComision['cbxItmArt'])) {
		foreach($frmComision['cbxItmArt'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmArt:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("
		if(byId('cbxItmArt').checked == true) {
			byId('cbxItmArt').checked = false;
		}");
	}
	
	return $objResponse;
}

function eliminarComisionEmpresa($frmComision) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmComision['cbxItm'])) {
		foreach($frmComision['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarComisionEmpresa(xajax.getFormValues('frmComision'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmComision['cbx'];
	
	return $objResponse;
}

function eliminarComisionTipoOrden($frmComision) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmComision['cbxItmTipoOrden'])) {
		foreach($frmComision['cbxItmTipoOrden'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmTipoOrden:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarComisionTipoOrden(xajax.getFormValues('frmComision'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmComision['cbx2'];
	
	return $objResponse;
}

function eliminarNivelProductividad($frmComision) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmComision['cbxNivelProductividad'])) {
		foreach($frmComision['cbxNivelProductividad'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmNivelProductividad:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("
		if(byId('cbxNivelProductividad').checked == true) {
			byId('cbxNivelProductividad').checked = false;
		}");
	}
	
	return $objResponse;
}

function eliminarNivelProdUnidad($frmComision){
	$objResponse = new xajaxResponse();
	
	if (isset($frmComision['cbxNivelProductividadUnidad'])) {
		foreach($frmComision['cbxNivelProductividadUnidad'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItemPieNivelProdUnidad".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("
		if(byId('cbxNivelProductividadUnidad').checked == true) {
			byId('cbxNivelProductividadUnidad').checked = false;
		}");
	}

	return $objResponse;
}

function formComision($idComision, $frmComision) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmComision['cbx'];
	$arrayObj2 = $frmComision['cbx2'];
	$arrayObj3 = $frmComision['cbx3'];
	$arrayObj4 = $frmComision['cbxArt'];
	$arrayObj5 = $frmComision['cbxItemNivelProdUnidad'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj2)) {
		foreach($arrayObj2 as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItmTipoOrden:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj3)) {
		foreach($arrayObj3 as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItmNivelProductividad:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj4)) {
		foreach($arrayObj4 as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItmArt:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObj5)) {
		foreach($arrayObj5 as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trItemPieNivelProdUnidad".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	if ($idComision > 0) {
		if (!xvalidaAcceso($objResponse,"pg_comision_list","editar")) { $objResponse->script("byId('btnCancelarComision').click();"); return $objResponse; }
		
		$query = sprintf("SELECT
			comision.id_comision,
			comision.porcentaje_comision,
			comision.tipo_porcentaje,
			comision.tipo_importe,
			comision.aplica_iva,
			comision.tipo_comision,
			comision.modo_comision,
			comision.id_modulo,
			cargo_dep.id_cargo,
			cargo_dep.id_departamento,
			cargo_dep.id_cargo_departamento,
			(SELECT dep.id_empresa FROM pg_departamento dep WHERE cargo_dep.id_departamento = dep.id_departamento) AS id_empresa
		FROM pg_comision comision
			INNER JOIN pg_cargo_departamento cargo_dep ON (comision.id_cargo_departamento = cargo_dep.id_cargo_departamento)
		WHERE id_comision = %s;",
			valTpDato($idComision, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdComision","value",$idComision);
		$objResponse->loadCommands(cargaLstEmpresaFinal($row['id_empresa'], "onchange=\"xajax_cargaLstDepartamento(this.value);\"", "lstEmpresa"));
		$objResponse->loadCommands(cargaLstModulo($row['id_modulo']));
		$objResponse->loadCommands(cargaLstDepartamento($row['id_empresa'], $row['id_departamento']));
		$objResponse->loadCommands(cargaLstCargo($row['id_departamento'], $row['id_cargo_departamento']));
		$objResponse->call("selectedOption","lstTipoPorcentaje",$row['tipo_porcentaje']);
		$objResponse->call("selectedOption","lstTipoImporte",$row['tipo_importe']);
		$objResponse->call("selectedOption","lstAplicaIva",$row['aplica_iva']);
		$objResponse->loadCommands(cargaLstTipoComision($row['tipo_comision']));
		$objResponse->assign("txtPorcentajeComision","value",number_format($row['porcentaje_comision'],3));
		$objResponse->call("selectedOption","lstModoComision",$row['modo_comision']);
		
		$objResponse->call(asignarTipoPorcentaje);
		
		$queryComisionEmp = sprintf("SELECT * FROM pg_comision_empresa comision_emp
		WHERE comision_emp.id_comision = %s;",
			valTpDato($idComision, "int"));
		$rsComisionEmp = mysql_query($queryComisionEmp);
		if (!$rsComisionEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowComisionEmp = mysql_fetch_assoc($rsComisionEmp)) {
			$Result1 = insertarItemEmpresa($contFila, $rowComisionEmp['id_comision_empresa'], $rowComisionEmp['id_empresa']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		$queryComisionTipoOrden = sprintf("SELECT * FROM pg_comision_tipo_orden comision_tipo_orden
		WHERE comision_tipo_orden.id_comision = %s;",
			valTpDato($idComision, "int"));
		$rsComisionTipoOrden = mysql_query($queryComisionTipoOrden);
		if (!$rsComisionTipoOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowComisionTipoOrden = mysql_fetch_assoc($rsComisionTipoOrden)) {
			$Result1 = insertarItemTipoOrden($contFila, $rowComisionTipoOrden['id_comision_tipo_orden'], $rowComisionTipoOrden['id_tipo_orden']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj2[] = $contFila;
			}
		}
		
		$query = sprintf("SELECT * FROM pg_comision_productividad WHERE id_comision = %s",
			valTpDato($idComision, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while($rowNivel = mysql_fetch_assoc($rs)){
			$Result1 = insertarItemNivelProductividad($contFila,"",$rowNivel['id_comision_productividad']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj3[] = $contFila;
			}	
		}

		$queryArt = sprintf("SELECT * FROM pg_comision_articulo WHERE id_comision = %s",
			valTpDato($idComision, "int"));
		$rsArt = mysql_query($queryArt);
		if (!$rsArt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while($rowArt = mysql_fetch_assoc($rsArt)){
			$Result1 = insertarItemArticulo($contFilaArt, $rowArt['id_comision_articulo'], $rowArt['id_articulo']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFilaArt = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFilaArt;
			}	
		}

		$queryUnidad = sprintf("SELECT * FROM pg_comision_productividad_unidad WHERE id_comision = %s;",
			valTpDato($idComision, "int"));
		$rsUnidad = mysql_query($queryUnidad);
		if (!$rsUnidad) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while($rowUnidad = mysql_fetch_assoc($rsUnidad)){
			$Result1 = insertarItemNivelProductividadUnidad($contFilaUnidad, "", $rowUnidad['id_comision_productividad_unidad']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFilaUnidad = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFilaUnidad;
			}
		}
	} else {
		if (!xvalidaAcceso($objResponse,"pg_comision_list","insertar")) { $objResponse->script("byId('btnCancelarComision').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstEmpresaFinal("", "onchange=\"xajax_cargaLstDepartamento(this.value);\"", "lstEmpresa"));
		$objResponse->loadCommands(cargaLstModulo());
		$objResponse->loadCommands(cargaLstDepartamento(-1));
		$objResponse->loadCommands(cargaLstCargo(-1));
		$objResponse->loadCommands(cargaLstTipoComision());
	}
	
	return $objResponse;
}

function formNivelProductividad($idNivelProductividad, $frmComision){
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("hddIdNivelProductividad","value",$idNivelProductividad);
	$objResponse->assign("txtProductividadMayor","value",number_format($frmComision["hddPorcMayor".$idNivelProductividad], 2, ".", ","));
	$objResponse->assign("txtProductividadMenor","value",number_format($frmComision["hddPorcMenor".$idNivelProductividad], 2, ".", ","));
	$objResponse->assign("txtPorcentajeProductividad","value",number_format($frmComision["hddPorc".$idNivelProductividad], 2, ".", ","));
	
	return $objResponse;	
}

function formNivelProdUnidad($idItemNivelProdUnidad, $frmComision){
	$objResponse = new xajaxResponse();

	$objResponse->assign("hddIdNivelProductividadUnidad","value",$idItemNivelProdUnidad);
	$objResponse->call("selectedOption","slctTipo",$frmComision["hddTipo".$idItemNivelProdUnidad]);
	$objResponse->assign("txtMayoIgual","value",number_format($frmComision["hddMayor".$idItemNivelProdUnidad], 2, ".", ","));
	$objResponse->assign("txtMenorIgual","value",number_format($frmComision["hddMenor".$idItemNivelProdUnidad], 2, ".", ","));
	$objResponse->call("selectedOption","slctTipo2",$frmComision["hddTipo2".$idItemNivelProdUnidad]);
	$objResponse->assign("txtMayoIgual2","value",number_format($frmComision["hddMayor2".$idItemNivelProdUnidad], 2, ".", ","));
	$objResponse->assign("txtMenorIgual2","value",number_format($frmComision["hddMenor2".$idItemNivelProdUnidad], 2, ".", ","));
	$objResponse->assign("txPorcentaje","value",number_format($frmComision["hddPorcent".$idItemNivelProdUnidad], 2, ".", ","));

	return $objResponse;	
}

function guardarComision($frmComision, $frmListaComision) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmComision['cbx'];
	$arrayObj2 = $frmComision['cbx2'];
	$arrayObj3 = $frmComision['cbx3'];
	$arrayObj4 = $frmComision['cbxArt'];
	$arrayObj5 = $frmComision['cbxItemNivelProdUnidad'];
	
	mysql_query("START TRANSACTION;");
	
	$idComision = $frmComision['hddIdComision'];
	
	if ($frmComision['hddIdComision'] > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " AND ";
		$sqlBusq .= $cond.sprintf("id_comision <> %s",
			valTpDato($idComision, "int"));
	}
	
	// VERIFICA SI LA COMISION YA EXISTE
	$sqlVerificarComision = sprintf("SELECT * FROM pg_comision
	WHERE id_cargo_departamento = %s
		AND id_modulo = %s
		AND porcentaje_comision = %s
		AND tipo_comision = %s
		AND modo_comision = %s %s",
		valTpDato($frmComision['lstCargo'], "int"),
		valTpDato($frmComision['lstModulo'], "int"),
		valTpDato($frmComision['txtPorcentajeComision'], "real_inglesa"),
		valTpDato($frmComision['lstTipoComision'], "int"),
		valTpDato($frmComision['lstModoComision'], "int"),
		$sqlBusq);
	$rsVerificarComision = mysql_query($sqlVerificarComision);
	if (!$rsVerificarComision) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	if (mysql_num_rows($rsVerificarComision)) { errorGuardarComision($objResponse); return $objResponse->alert("Registro ya existente"); }
	
	if ($idComision > 0) {
		if (!xvalidaAcceso($objResponse,"pg_comision_list","editar")) { errorGuardarComision($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE pg_comision SET
			id_cargo_departamento = %s,
			id_modulo = %s,
			porcentaje_comision = %s,
			tipo_porcentaje = %s,
			tipo_importe = %s,
			aplica_iva = %s,
			tipo_comision = %s,
			modo_comision = %s
		WHERE id_comision = %s;",
			valTpDato($frmComision['lstCargo'], "int"),
			valTpDato($frmComision['lstModulo'], "int"),
			valTpDato($frmComision['txtPorcentajeComision'], "real_inglesa"),
			valTpDato($frmComision['lstTipoPorcentaje'], "int"),
			valTpDato($frmComision['lstTipoImporte'], "int"),
			valTpDato($frmComision['lstAplicaIva'], "int"),
			valTpDato($frmComision['lstTipoComision'], "int"),
			valTpDato($frmComision['lstModoComision'], "int"),
			valTpDato($idComision, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"pg_comision_list","insertar")) { errorGuardarComision($objResponse); return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO pg_comision (id_cargo_departamento, id_modulo, porcentaje_comision, tipo_porcentaje, tipo_importe, aplica_iva, tipo_comision, modo_comision)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($frmComision['lstCargo'], "int"),
			valTpDato($frmComision['lstModulo'], "int"),
			valTpDato($frmComision['txtPorcentajeComision'], "real_inglesa"),
			valTpDato($frmComision['lstTipoPorcentaje'], "int"),
			valTpDato($frmComision['lstTipoImporte'], "int"),
			valTpDato($frmComision['lstAplicaIva'], "int"),
			valTpDato($frmComision['lstTipoComision'], "int"),
			valTpDato($frmComision['lstModoComision'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idComision = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// VERIFICA SI LAS EMPRESAS ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryComisionEmp = sprintf("SELECT * FROM pg_comision_empresa comision_emp
	WHERE id_comision = %s;",
		valTpDato($idComision, "int"));
	$rsComisionEmp = mysql_query($queryComisionEmp);
	if (!$rsComisionEmp) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowComisionEmp = mysql_fetch_assoc($rsComisionEmp)) {
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if ($rowComisionEmp['id_comision_empresa'] == $frmComision['hddIdComisionEmpresa'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM pg_comision_empresa WHERE id_comision_empresa = %s;",
				valTpDato($rowComisionEmp['id_comision_empresa'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA EL DETALLE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$idEmpresa = $frmComision['hddIdEmpresa'.$valor];
			
			if ($idEmpresa > 0 && $frmComision['hddIdComisionEmpresa'.$valor] == "") {
				$insertSQL = sprintf("INSERT INTO pg_comision_empresa (id_comision, id_empresa)
				VALUE (%s, %s);",
					valTpDato($idComision, "int"),
					valTpDato($idEmpresa, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// VERIFICA SI LOS TIPO DE ORDEN ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryComisionTipoOrden = sprintf("SELECT * FROM pg_comision_tipo_orden comision_tipo_orden
	WHERE id_comision = %s;",
		valTpDato($idComision, "int"));
	$rsComisionTipoOrden = mysql_query($queryComisionTipoOrden);
	if (!$rsComisionTipoOrden) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowComisionTipoOrden = mysql_fetch_assoc($rsComisionTipoOrden)) {
		$existRegDet = false;
		if (isset($arrayObj2)) {
			foreach($arrayObj2 as $indice => $valor) {
				if ($rowComisionTipoOrden['id_comision_tipo_orden'] == $frmComision['hddIdComisionTipoOrden'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM pg_comision_tipo_orden WHERE id_comision_tipo_orden = %s;",
				valTpDato($rowComisionTipoOrden['id_comision_tipo_orden'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA EL DETALLE
	if (isset($arrayObj2)) {
		foreach($arrayObj2 as $indice => $valor) {
			$idTipoOrden = $frmComision['hddIdTipoOrden'.$valor];
			
			if ($idTipoOrden > 0 && $frmComision['hddIdComisionTipoOrden'.$valor] == "") {
				$insertSQL = sprintf("INSERT INTO pg_comision_tipo_orden (id_comision, id_tipo_orden)
				VALUE (%s, %s);",
					valTpDato($idComision, "int"),
					valTpDato($idTipoOrden, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// VERIFICA SI LOS NIVELES DE PRODUCTIVIDAD ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryComisionProductividad = sprintf("SELECT * FROM pg_comision_productividad comision_productividad
	WHERE id_comision = %s;",
		valTpDato($idComision, "int"));
	$rsComisionProductividad = mysql_query($queryComisionProductividad);
	if (!$rsComisionProductividad) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowComisionProductividad = mysql_fetch_assoc($rsComisionProductividad)) {
		$existRegDet = false;
		if (isset($arrayObj3)) {
			foreach($arrayObj3 as $indice => $valor) {
				if ($rowComisionProductividad['id_comision_productividad'] == $frmComision['hddIdComisionProductividadItm'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM pg_comision_productividad WHERE id_comision_productividad = %s;",
				valTpDato($rowComisionProductividad['id_comision_productividad'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}

	// INSERTA LOS NIVELES DE COMISION
	if(isset($arrayObj3)) {
		foreach($arrayObj3 as $indice => $valor) {
			if ($frmComision['hddIdComisionProductividadItm'.$valor] > 0) {
				$updateNivel = sprintf("UPDATE pg_comision_productividad SET 
					mayor = %s,
					menor = %s,
					porcentaje = %s
				WHERE id_comision_productividad = %s;",
					valTpDato($frmComision['hddPorcMayor'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddPorcMenor'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddPorc'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddIdComisionProductividadItm'.$valor], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateNivel);
				if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			} else {
				$insertNivel = sprintf("INSERT INTO pg_comision_productividad (id_comision, mayor, menor, porcentaje)
				VALUES (%s, %s, %s, %s)",
					valTpDato($idComision, "int"),
					valTpDato($frmComision['hddPorcMayor'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddPorcMenor'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddPorc'.$valor], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertNivel);
				if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// VERIFICA SI LOS ARTICULOS ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
		$queryComisionArticulo = sprintf("SELECT * FROM pg_comision_articulo comision_articulo
		WHERE id_comision = %s;",
		valTpDato($idComision, "int"));
	$rsComisionArticulo = mysql_query($queryComisionArticulo);
	if (!$rsComisionArticulo) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowComisionArticulo = mysql_fetch_assoc($rsComisionArticulo)) {
		$existRegDet = false;
		if (isset($arrayObj4)) {
			foreach($arrayObj4 as $indice => $valor) {
				if ($rowComisionArticulo['id_comision_articulo'] == $frmComision['hddIdComisionArticulo'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) { // ELIMINA SI NO EXISTE EL ARTICULO EN LE FORMULARIO
			$deleteSQL = sprintf("DELETE FROM pg_comision_articulo WHERE id_comision_articulo = %s;",
				valTpDato($rowComisionArticulo['id_comision_articulo'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}else{ // SI EXISTE COMPRA CON EL PORCENTAJE DE LA BD CON EL DEL FORMULARIO
			if (isset($arrayObj4)) {
				foreach($arrayObj4 as $indice => $valor) {
					if($frmComision['textPorcentajeArt'.$valor] == ""){ // VALIDA QUE EL PORCENTAJE NO ESTE EN BLANCO
					
						$objResponse->script("document.getElementById('textPorcentajeArt".$valor."').className = 'inputErrado' ");
						errorGuardarComision($objResponse);
						return $objResponse->alert("EL porcentaje del Articulo no debe estar en blanco ");
					} 
					if ($rowComisionArticulo['porcentaje'] != $frmComision['textPorcentajeArt'.$valor]) {// ACTUALIZA EL PORCENTAJE SI ES DISTINTO AL QUE EXISTEN EN LA BD
						$updatePorcSQL = sprintf("UPDATE pg_comision_articulo SET
								porcentaje = %s, monto = %s
							WHERE id_comision_articulo = %s",
								valTpDato($frmComision['textPorcentajeArt'.$valor], "real_inglesa"),
								valTpDato($frmComision['textMonto'.$valor], "real_inglesa"),
								valTpDato($frmComision['hddIdComisionArticulo'.$valor], "int"));
						$ResultUp1 = mysql_query($updatePorcSQL);
					if (!$ResultUp1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
								
					}
				}	
			}
		}
	}
	
	// INSERTA LOS ARTICULO DE LA COMISION
	if (isset($arrayObj4)){
		foreach($arrayObj4 as $indice => $valor){
			$idArticulo = $frmComision['hddIdArticulo'.$valor];
			if($frmComision['textPorcentajeArt'.$valor] == " " && $frmComision['textMonto'.$valor] == " "){
				$objResponse->script("document.getElementById('textMonto".$valor."').className = 'inputErrado' ");
				errorGuardarComision($objResponse);
				return $objResponse->alert("EL Monto del Articulo no debe estar en blanco ");
			 }
			 if($frmComision['textPorcentajeArt'.$valor] == "0.00"){
				$objResponse->script("document.getElementById('textPorcentajeArt".$valor."').className = 'inputErrado' ");
				errorGuardarComision($objResponse);
				if($frmComision['textMonto'.$valor] == "0.00"){
					return $objResponse->alert("EL Porcentaje del Articulo no debe ser 0(cero) ");
				}
			 } 
			 if($frmComision['textMonto'.$valor] == "0.00"){
				$objResponse->script("document.getElementById('textMonto".$valor."').className = 'inputErrado' ");
				errorGuardarComision($objResponse);
				if($frmComision['textPorcentajeArt'.$valor] == "0.00"){
					return $objResponse->alert("EL Monto del Articulo no debe ser 0(cero) "); 
				}
			 }
			 
			if($idArticulo > 0 && $frmComision['hddIdComisionArticulo'.$valor] == ""){
				$insetArtSQL = sprintf("INSERT INTO pg_comision_articulo (id_comision, id_Articulo, porcentaje, monto) 
					VALUE (%s,%s,%s,%s)",	
						valTpDato($idComision, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($frmComision['textPorcentajeArt'.$valor], "real_inglesa"),
						valTpDato($frmComision['textMonto'.$valor], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$ResultArt1 = mysql_query($insetArtSQL);
				if (!$ResultArt1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");				
			}
		}
	}

	// VERIFICA QUE LOS NIVELES DE PRODUCTIVIDAD ALMANECENAS EN BD AUN ESTEN AGREGADO EN EL FORMULARIO
	$queryComisionPorUnidad = sprintf("SELECT * FROM pg_comision_productividad_unidad WHERE id_comision = %s",
		valTpDato($idComision, "int"));
	$rsComisionPorUnidad = mysql_query($queryComisionPorUnidad);
	if(!$rsComisionPorUnidad){errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
	while($rowsComisionPorUnidad = mysql_fetch_array($rsComisionPorUnidad)){
		$existRegDet = false;
		if (isset($arrayObj5)) {
			foreach($arrayObj5 as $indice => $valor) {
				if ($rowsComisionPorUnidad['id_comision_productividad_unidad'] == $frmComision['hddIdItemNivelProdUnidad'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		if($existRegDet == false){
			$queruDelete = sprintf("DELETE FROM pg_comision_productividad_unidad WHERE id_comision_productividad_unidad = %s",
				valTpDato($rowsComisionPorUnidad['id_comision_productividad_unidad'],"int"));
			$rsDelete = mysql_query($queruDelete);
			if(!$rsDelete){errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
		}		
	}
	
	// INSERTA LOS NIEVES DE COMISION POR UNIDAD
	if(isset($arrayObj5)){
		foreach($arrayObj5 as $indice => $valor){
			if($frmComision['hddIdItemNivelProdUnidad'.$valor] > 0){ //edita
				$updateNivelUnidad = sprintf("UPDATE pg_comision_productividad_unidad SET 
					id_comision = %s,
					tipo = %s, 
					mayor_igual = %s, 
					menor_igual = %s, 
					tipo_2 = %s, 
					mayor_igual_2 = %s,
					menor_igual_2 = %s, 
					porcentaje = %s
				WHERE id_comision_productividad_unidad = %s;",
					valTpDato($idComision, "int"),
					valTpDato($frmComision['hddTipo'.$valor], "int"),
					valTpDato($frmComision['hddMayor'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddMenor'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddTipo2'.$valor], "int"),
					valTpDato($frmComision['hddMayor2'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddMenor2'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddPorcent'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddIdItemNivelProdUnidad'.$valor], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateNivelUnidad);
				if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			} else { //inserta
				$insertNivelUnidad = sprintf("INSERT INTO pg_comision_productividad_unidad (id_comision, tipo, mayor_igual, menor_igual, tipo_2, mayor_igual_2, menor_igual_2, porcentaje) VALUE (%s,%s,%s,%s,%s,%s,%s,%s)",
					valTpDato($idComision, "int"),
					valTpDato($frmComision['hddTipo'.$valor], "int"),
					valTpDato($frmComision['hddMayor'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddMenor'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddTipo2'.$valor], "int"),
					valTpDato($frmComision['hddMayor2'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddMenor2'.$valor], "real_inglesa"),
					valTpDato($frmComision['hddPorcent'.$valor], "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertNivelUnidad);
				if (!$Result1) { errorGuardarComision($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}	
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarComision($objResponse);
	$objResponse->alert("Registro Guardado con Éxito");
	
	$objResponse->script("byId('btnCancelarComision').click();");
	
	$objResponse->loadCommands(listaComision(
		$frmListaComision['pageNum'],
		$frmListaComision['campOrd'],
		$frmListaComision['tpOrd'],
		$frmListaComision['valBusq']));
	
	return $objResponse;
}

function insertarArticulo($idArticulo, $frmComision) {
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmComision['cbxArt'];
	$contFila = $arrayObj[count($arrayObj)-1];

	if ($idArticulo > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmComision['hddIdArticulo'.$valor] == $idArticulo) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemArticulo($contFila, "", $idArticulo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				// DESBLOQUEA LOS BOTONES DEL LISTADO
				for ($cont = 1; $cont <= 20; $cont++) {
					$objResponse->script(sprintf("byId('btnInsertarArt%s').disabled = false;",
						$cont));
				}
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
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	for ($cont = 1; $cont <= 20; $cont++) {
		$objResponse->script(sprintf("byId('btnInsertarArt%s').disabled = false;",
			$cont));
	}
	
	return $objResponse;
}

function insertarEmpresa($idEmpresa, $frmComision) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmComision['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idEmpresa > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmComision['hddIdEmpresa'.$valor] == $idEmpresa) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemEmpresa($contFila, "", $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				// DESBLOQUEA LOS BOTONES DEL LISTADO
				for ($cont = 1; $cont <= 20; $cont++) {
					$objResponse->script(sprintf("byId('btnInsertarEmpresa%s').disabled = false;",
						$cont));
				}
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
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	for ($cont = 1; $cont <= 20; $cont++) {
		$objResponse->script(sprintf("byId('btnInsertarEmpresa%s').disabled = false;",
			$cont));
	}
	
	return $objResponse;
}

function insertarTipoOrden($idTipoOrden, $frmComision) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmComision['cbx2'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idTipoOrden > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmComision['hddIdTipoOrden'.$valor] == $idTipoOrden) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemTipoOrden($contFila, "", $idTipoOrden);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				// DESBLOQUEA LOS BOTONES DEL LISTADO
				for ($cont = 1; $cont <= 20; $cont++) {
					$objResponse->script(sprintf("byId('btnInsertarTipoOrden%s').disabled = false;",
						$cont));
				}
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
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	for ($cont = 1; $cont <= 20; $cont++) {
		$objResponse->script(sprintf("byId('btnInsertarTipoOrden%s').disabled = false;",
			$cont));
	}
	
	return $objResponse;
}

function insertarNivelProductividad($frmComision, $frmNivelProductividad) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmComision['cbx3'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$Result1 = insertarItemNivelProductividad($contFila, $frmNivelProductividad);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj[] = $contFila;
	}
		
	$objResponse->script("byId('btnCancelarNivelProductividad').click();");
	
	return $objResponse;
}

function insertaNivelProductUnidad($frmComision, $frmNivelProductividadUnidad){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmComision['cbxItemNivelProdUnidad'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	$Result1 = insertarItemNivelProductividadUnidad($contFila, $frmNivelProductividadUnidad);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObj[] = $contFila;
	}
		
	$objResponse->script("byId('btnCnlNivelProducUnidad').click();");

	return $objResponse;
}

function listaAccesorio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" nom_accesorio LIKE %s OR des_accesorio LIKE %s ",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_accesorio %s", $sqlBusq);

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
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "8%", $pageNum, "id_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "40%", $pageNum, "nom_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, ("Articulo"));
		$htmlTh .= ordenarCampo("xajax_listaAccesorio", "52%", $pageNum, "des_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripcion"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><button id=\"btnInsertarArt%s\" title=\"Seleccionar\" onclick=\"validarInsertarArticulo('%s')\" type=\"button\"><img src=\"img/iconos/tick.png\"></button></td>",$row['id_accesorio'],$row['id_accesorio']);
			$htmlTb .= sprintf("<td align=\"right\">%s</td>",$row['id_accesorio']);
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",utf8_encode($row['nom_accesorio']));
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",utf8_encode($row['des_accesorio']));
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_pg.gif\"/>");
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
	
	$objResponse->assign("divListaPorcentajeArt","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}



function listaComision($pageNum = 0, $campOrd = "", $tpOrd = "ASC", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$arrayTipoComision = array(
		1 => "M.O.",
		2 => "T.O.T.",
		3 => "Nota",
		4 => "Repuesto",
		5 => "Vehículo",
		6 => "Accesorio",
		7 => "Arbitrario",
		8 => "Facturado");
	
	$arrayTipoImporte = array(
		1 => "Precio",
		2 => "Costo",
		3 => "Monto Fijo",
		4 => "UT",
		5 => "Utilidad",
		6 => "Productividad");
	
	$arrayTipoPorcentaje = array(
		1 => "Simple",
		2 => "Por Productividad",
		3 => "Por Rango",
		4 => "Por Item");
	
	$modoComision = array(
		1 => "Por Venta Propia",
		2 => "Por Venta General",
		3 => "Por Venta Subordinada");
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("dep.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cargo.id_cargo IN (%s)",
			valTpDato($valCadBusq[1], "campo"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.tipo_comision IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.tipo_porcentaje IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("comision.modo_comision IN (%s)",
			valTpDato($valCadBusq[5], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cargo.nombre_cargo LIKE %s
		OR modu.descripcionModulo LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT
		comision.id_comision,
		modu.id_modulo,
		modu.descripcionModulo AS descripcion_modulo,
		comision.porcentaje_comision,
		comision.tipo_porcentaje,
		comision.tipo_importe,
		comision.aplica_iva,
		comision.tipo_comision,
		comision.modo_comision,
		dep.nombre_departamento,
		cargo.nombre_cargo,
		
		(SELECT COUNT(id_comision) FROM pg_comision_tipo_orden comision_tipo_orden
		WHERE comision_tipo_orden.id_comision = comision.id_comision) AS cantidad_tipo_orden,
		
		(SELECT COUNT(id_comision) FROM pg_comision_empresa comision_emp
		WHERE comision_emp.id_comision = comision.id_comision) AS cantidad_empresas,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM pg_comision comision
		INNER JOIN pg_cargo_departamento cargo_dep ON (comision.id_cargo_departamento = cargo_dep.id_cargo_departamento)
		INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
		INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento)
		INNER JOIN pg_modulos modu ON (comision.id_modulo = modu.id_enlace_concepto)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (dep.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaComision", "4%", $pageNum, "comision.id_comision", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaComision", "18%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaComision", "16%", $pageNum, "CONCAT(dep.nombre_departamento, cargo.nombre_cargo)", $campOrd, $tpOrd, $valBusq, $maxRows, "Departamento / Cargo");
		$htmlTh .= ordenarCampo("xajax_listaComision", "10%", $pageNum, "descripcion_modulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Módulo");
		$htmlTh .= ordenarCampo("xajax_listaComision", "6%", $pageNum, "tipo_importe", $campOrd, $tpOrd, $valBusq, $maxRows, "Comision Sobre");
		$htmlTh .= ordenarCampo("xajax_listaComision", "4%", $pageNum, "aplica_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Aplica Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaComision", "8%", $pageNum, "tipo_comision", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo");
		$htmlTh .= ordenarCampo("xajax_listaComision", "4%", $pageNum, "porcentaje_comision", $campOrd, $tpOrd, $valBusq, $maxRows, "%");
		$htmlTh .= ordenarCampo("xajax_listaComision", "8%", $pageNum, "modo_comision", $campOrd, $tpOrd, $valBusq, $maxRows, "Modo Comisión");
		$htmlTh .= ordenarCampo("xajax_listaComision", "8%", $pageNum, "cantidad_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Filtro de Orden");
		$htmlTh .= ordenarCampo("xajax_listaComision", "14%", $pageNum, "cantidad_empresas", $campOrd, $tpOrd, $valBusq, $maxRows, "Dctos. de la(s) Empresa(s)");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Vehículos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administración")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		switch($row['aplica_iva']) {
			case 0 : $aplicaIva = "No"; break;
			case 1 : $aplicaIva = "Si"; break;
			default : $aplicaIva = "";
		}
		
		$queryComisionEmp = sprintf("SELECT
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_iv_empresas_sucursales vw_iv_emp_suc
			INNER JOIN pg_comision_empresa comision_emp ON (vw_iv_emp_suc.id_empresa_reg = comision_emp.id_empresa)
		WHERE comision_emp.id_comision = %s;",
			valTpDato($row['id_comision'], "int"));
		$rsComisionEmp = mysql_query($queryComisionEmp);
		if (!$rsComisionEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayComisionEmp = NULL;
		while ($rowComisionEmp = mysql_fetch_assoc($rsComisionEmp)) {
			$arrayDet[0] = $rowComisionEmp['nombre_empresa'];
			$arrayComisionEmp[] = $arrayDet;
		}
		
		$queryComisionTipoOrden = sprintf("SELECT
			comision_tipo_orden.*,
			tipo_orden.descripcion AS nombre_tipo_orden
		FROM pg_comision_tipo_orden comision_tipo_orden
			INNER JOIN sa_filtro_orden tipo_orden ON (comision_tipo_orden.id_tipo_orden = tipo_orden.id_filtro_orden)
		WHERE comision_tipo_orden.id_comision = %s;",
			valTpDato($row['id_comision'], "int"));
		$rsComisionTipoOrden = mysql_query($queryComisionTipoOrden);
		if (!$rsComisionTipoOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$arrayComisionTipoOrden = NULL;
		while ($rowComisionTipoOrden = mysql_fetch_assoc($rsComisionTipoOrden)) {
			$arrayDet[0] = $rowComisionTipoOrden['nombre_tipo_orden'];
			$arrayComisionTipoOrden[] = $arrayDet;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_comision'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_departamento'])."<br><b>".utf8_encode($row['nombre_cargo'])."</b></td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($row['id_modulo'].".- ".$row['descripcion_modulo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".($arrayTipoImporte[$row['tipo_importe']])."</td>";
			$htmlTb .= "<td>".utf8_encode($aplicaIva)."</td>";
			$htmlTb .= "<td>".($arrayTipoComision[$row['tipo_comision']])."</td>";
			$htmlTb .= "<td align=\"right\">";
				if ($row['tipo_porcentaje'] > 1) {
					$htmlTb .= $arrayTipoPorcentaje[$row['tipo_porcentaje']];
				} else {
					$htmlTb .= number_format($row['porcentaje_comision'],3);
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".($modoComision[$row['modo_comision']])."</td>";
			$htmlTb .= "<td>";
				if (isset($arrayComisionTipoOrden)) {
					$htmlTb .= "<table>";
					foreach ($arrayComisionTipoOrden as $indice => $valor) {
						$htmlTb .= "<tr>";
							$htmlTb .= "<td>".$arrayComisionTipoOrden[$indice][0]."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				if (isset($arrayComisionEmp)) {
					$htmlTb .= "<table>";
					foreach ($arrayComisionEmp as $indice => $valor) {
						$htmlTb .= "<tr>";
							$htmlTb .= "<td>".$arrayComisionEmp[$indice][0]."</td>";
						$htmlTb .= "</tr>";
					}
					$htmlTb .= "</table>";
				}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblComision', '%s');\"><img class=\"puntero\" src=\"img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_comision']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"img/iconos/ico_delete.png\"/></td>",
				$row['id_comision']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComision(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComision(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaComision(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComision(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComision(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaComision","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
		
	return $objResponse;
}

function listaEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("id_empresa_reg <> 100");
	
	if (strlen($valCadBusq[1]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_empresa LIKE %s
		OR nombre_empresa_suc LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
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
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, ("R.I.F."));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "36%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "36%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, ("Sucursal"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarEmpresa%s\" onclick=\"validarInsertarEmpresa('%s');\" title=\"Seleccionar\"><img src=\"img/iconos/tick.png\"/></button>",
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_pg.gif\"/>");
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

function listaTipoOrden($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(descripcion LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM sa_filtro_orden %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaTipoOrden", "8%", $pageNum, "id_filtro_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaTipoOrden", "92%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarTipoOrden%s\" onclick=\"validarInsertarTipoOrden('%s');\" title=\"Seleccionar\"><img src=\"img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_filtro_orden']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_filtro_orden'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_pri_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ant_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaTipoOrden(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_sig_pg.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTipoOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"img/iconos/ico_reg_ult_pg.gif\"/>");
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
	
	$objResponse->assign("divListaTipoOrden","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarAccesorio");
$xajax->register(XAJAX_FUNCTION,"buscarComision");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarTipoOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstCargo");
$xajax->register(XAJAX_FUNCTION,"cargaLstCargoBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstDepartamento");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstModuloBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoComision");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoComisionBuscar");
$xajax->register(XAJAX_FUNCTION,"editarNivelProductividad");
$xajax->register(XAJAX_FUNCTION,"editarNivelProdUnidad");
$xajax->register(XAJAX_FUNCTION,"eliminarComision");
$xajax->register(XAJAX_FUNCTION,"eliminarComisionArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarComisionEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarComisionTipoOrden");
$xajax->register(XAJAX_FUNCTION,"eliminarNivelProductividad");
$xajax->register(XAJAX_FUNCTION,"eliminarNivelProdUnidad");
$xajax->register(XAJAX_FUNCTION,"formComision");
$xajax->register(XAJAX_FUNCTION,"formNivelProductividad");
$xajax->register(XAJAX_FUNCTION,"formNivelProdUnidad");
$xajax->register(XAJAX_FUNCTION,"guardarComision");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarEmpresa");
$xajax->register(XAJAX_FUNCTION,"insertarTipoOrden");
$xajax->register(XAJAX_FUNCTION,"insertarNivelProductividad");
$xajax->register(XAJAX_FUNCTION,"insertaNivelProductUnidad");
$xajax->register(XAJAX_FUNCTION,"listaAccesorio");
$xajax->register(XAJAX_FUNCTION,"listaComision");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaTipoOrden");

function insertarItemArticulo($contFila = "", $idComisionArticulo = "", $idArticulo = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idComisionArticulo != "" ){
		$queryComisionArt = sprintf("SELECT * FROM pg_comision_articulo WHERE id_comision_articulo = %s;",
			valTpDato($idComisionArticulo, "int"));
		$rsComisionArt = mysql_query($queryComisionArt);
		if (!$rsComisionArt) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$rowComisionArt = mysql_fetch_assoc($rsComisionArt);
		$textPorcentajeArt = $rowComisionArt['porcentaje'];
		$textMonto = $rowComisionArt['monto'];
	}else{
		$textPorcentajeArt = "";
		$textMonto = "";
	}
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT * FROM an_accesorio WHERE id_accesorio = %s;",
		valTpDato($idArticulo, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPorcentajeArt').before('".
		"<tr id=\"trItmArt:%s\" align=\"left\" class=\"textoGris_11px %s\" title=\"trItmArt:%s\">".
			"<td><input id=\"cbxItmArt\" name=\"cbxItmArt[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxArt\" name=\"cbxArt[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td align=\"right\">%s</td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"center\" onClick=\"return habilitarInputPorcentaje(%s)\"><input type=\"text\" class=\"inputHabilitado\" id=\"textPorcentajeArt%s\" style=\"text-align:right\" onblur=\"setFormatoRafk(this, 2)\" onkeypress=\"return validarSoloNumerosReales(event);\" name=\"textPorcentajeArt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdComisionArticulo%s\" name=\"hddIdComisionArticulo%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticulo%s\" name=\"hddIdArticulo%s\" readonly=\"readonly\" value=\"%s\"/></td>".
				
			"<td align=\"center\" onClick=\"return habilitarInputMonto(%s)\"><input type=\"text\" class=\"inputHabilitado\" id=\"textMonto%s\" style=\"text-align:right\" onkeypress=\"return validarSoloNumerosReales(event);\" onblur=\"setFormatoRafk(this, 2)\" name=\"textMonto%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdComisionArticulo%s\" name=\"hddIdComisionArticulo%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticulo%s\" name=\"hddIdArticulo%s\" readonly=\"readonly\" value=\"%s\"/></td>".
				
		"</tr>');",
		$contFila, $clase, $contFila,//tr 
			$contFila,//input check
				$contFila,//input check
			$contFila,/*utf8_encode($row['id_accesorio']),*///id
			utf8_encode($row['nom_accesorio']),//art
			utf8_encode($row['des_accesorio']),//des
				$contFila, $contFila, $contFila, $textPorcentajeArt,
				$contFila, $contFila, $idComisionArticulo,
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, $contFila, $textMonto,
				$contFila, $contFila, $idComisionArticulo,
				$contFila, $contFila, $idArticulo);
	
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemEmpresa($contFila, $idComisionEmpresa = "", $idEmpresa = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales
	WHERE id_empresa_reg = %s;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\" title=\"trItm:%s\">".
			"<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdComisionEmpresa%s\" name=\"hddIdComisionEmpresa%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdEmpresa%s\" name=\"hddIdEmpresa%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase, $contFila,
			$contFila,
				$contFila,
			utf8_encode($row['rif']),
			utf8_encode($row['nombre_empresa']),
			utf8_encode($row['nombre_empresa_suc']),
				$contFila, $contFila, $idComisionEmpresa,
				$contFila, $contFila, $idEmpresa);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemTipoOrden($contFila, $idComisionTipoOrden = "", $idTipoOrden = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// BUSCA LOS DATOS DEL TIPO DE ORDEN
	$query = sprintf("SELECT * FROM sa_filtro_orden
	WHERE id_filtro_orden = %s;",
		valTpDato($idTipoOrden, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieTipoOrden').before('".
		"<tr id=\"trItmTipoOrden:%s\" align=\"left\" class=\"textoGris_11px %s\" title=\"trItmTipoOrden:%s\">".
			"<td><input id=\"cbxItmTipoOrden\" name=\"cbxItmTipoOrden[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx2\" name=\"cbx2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td align=\"right\">%s</td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdComisionTipoOrden%s\" name=\"hddIdComisionTipoOrden%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdTipoOrden%s\" name=\"hddIdTipoOrden%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase, $contFila,
			$contFila,
				$contFila,
			utf8_encode($row['id_filtro_orden']),
			utf8_encode($row['descripcion']),
				$contFila, $contFila, $idComisionTipoOrden,
				$contFila, $contFila, $idTipoOrden);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemNivelProductividad($contFila, $arrayItemNivel = "", $idNivelProductividad = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idNivelProductividad > 0) {
		$query = sprintf("SELECT * FROM pg_comision_productividad WHERE id_comision_productividad = %s",
			valTpDato($idNivelProductividad, "int"));
		$rs = mysql_query($query);
		if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$row = mysql_fetch_assoc($rs);
		
		$arrayNuevoNivel = array(
			0 => $row['mayor'],
			1 => $row['menor'], 
			2 => $row['porcentaje'],
			3 => $row['id_comision_productividad']);		
	} else {
		$arrayNuevoNivel = array(
			0 => $arrayItemNivel['txtProductividadMayor'],
			1 => $arrayItemNivel['txtProductividadMenor'],
			2 => $arrayItemNivel['txtPorcentajeProductividad'],
			3 => "");
	}
	
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieNivelProductividad').before('".
		"<tr id=\"trItmNivelProductividad:%s\" align=\"left\" class=\"textoGris_11px %s\" title=\"trItmNivelProductividad:%s\">".
			"<td><input id=\"cbxNivelProductividad\" name=\"cbxNivelProductividad[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx3\" name=\"cbx3[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
			"</td>".
			"<td align=\"center\"><div id=\"divNivelId%s\">%s</div></td>".//id
			"<td align=\"center\"><div id=\"divMayor%s\">%s</div>".//mayor
				"<input type=\"hidden\" id=\"hddPorcMayor%s\" name=\"hddPorcMayor%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
			"<td align=\"center\"><div id=\"divMenor%s\">%s</div>".//menor
				"<input type=\"hidden\" id=\"hddPorcMenor%s\" name=\"hddPorcMenor%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
			"<td align=\"center\"><div id=\"divNivelPorcentaje%s\">%s</div>".//nivelPorcentaje
				"<input type=\"hidden\" id=\"hddPorc%s\" name=\"hddPorc%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
			"<td>".//imgEdita
				"<a id=\"aEditarNivel%s\" class=\"modalImg\" rel=\"#divFlotante2\">".
					"<img id=\"idEditar%s\" name=\"imgEditar%s\" class=\"puntero\" title=\"Editar\" src=\"img/iconos/pencil.png\">".
				"</a>".
				"<input type=\"hidden\" id=\"hddIdComisionProductividadItm%s\" name=\"hddIdComisionProductividadItm%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
		"</tr>');
		
		byId('aEditarNivel%s').onclick = function() {
			abrirDivFlotante2(this, 'tblNivelProdutividad', 'Editar Nivel Productividad', 'editarNivelProdutividad');
			xajax_formNivelProductividad(%s, xajax.getFormValues('frmComision'));
		}",
			$contFila, $clase, $contFila, //tr
				$contFila,//check
					$contFila,//check2
				$contFila, $contFila,//id
				$contFila, number_format($arrayNuevoNivel[0], 2, ".", ",")."%",
					$contFila, $contFila, $arrayNuevoNivel[0],//mayor
				$contFila, number_format($arrayNuevoNivel[1], 2, ".", ",")."%",
					$contFila, $contFila, $arrayNuevoNivel[1],//menor
				$contFila, number_format($arrayNuevoNivel[2], 2, ".", ",")."%",
					$contFila, $contFila, $arrayNuevoNivel[2],//porcentaje				
				$contFila,//a
					$contFila, $contFila,//img
					$contFila, $contFila, $arrayNuevoNivel[3],
				$contFila,
					$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemNivelProductividadUnidad($contFila, $arrayItemNivelProductUnidad = "", $idNivelProductividadUnidad = ""){
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if($idNivelProductividadUnidad > 0){
		
		$query = sprintf("SELECT * FROM pg_comision_productividad_unidad WHERE id_comision_productividad_unidad = %s",
			valTpDato($idNivelProductividadUnidad, "int"));
		$rs = mysql_query($query);
		if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
		$row = mysql_fetch_assoc($rs);

		$arrayNivelUnidad = array(
			0 => $row['tipo'],
			1 => $row['mayor_igual'],
			2 => $row['menor_igual'],
			3 => $row['tipo_2'],
			4 => $row['mayor_igual_2'],
			5 => $row['menor_igual_2'],
			6 => $row['porcentaje'],
			7 => $row['id_comision_productividad_unidad'],
			8 => $row['id_comision']
		);
		
	}else{
		$arrayNivelUnidad = array(
			0 => $arrayItemNivelProductUnidad['slctTipo'],
			1 => $arrayItemNivelProductUnidad['txtMayoIgual'],
			2 => $arrayItemNivelProductUnidad['txtMenorIgual'],
			3 => $arrayItemNivelProductUnidad['slctTipo2'],
			4 => $arrayItemNivelProductUnidad['txtMayoIgual2'],
			5 => $arrayItemNivelProductUnidad['txtMenorIgual2'],
			6 => $arrayItemNivelProductUnidad['txPorcentaje'],
			7 => "",
			8 => ""
		);
	}
	
	$arrayLisTipo = array('-1' => "-", 0 => "Todo", 1 => "Nuevo", 2 => "Usado",3 => "Usado Particular");
	$arrayLisTipo2 = array('-1' => "-", 0 => "Todo", 1 => "Nuevo", 2 => "Usado",3 => "Usado Particular");

	$htmlItmPie = sprintf("$('#trItmPieNivelProductividadUnidad').before('".
		"<tr id=\"trItemPieNivelProdUnidad%s\"  align=\"center\" class=\"%s\">".
			"<td>".
				"<input type=\"checkbox\" id=\"cbxNivelProductividadUnidad\" name =\"cbxNivelProductividadUnidad[]\" value=\"%s\" />".
				"<input type=\"checkbox\" id=\"cbxItemNivelProdUnidad%s\" name =\"cbxItemNivelProdUnidad[]\" checked=\"checked\" style=\"display:none\" value =\"%s\" />".
			"</td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">".
				"<div id=\"divTipo%s\">%s</div>".
				"<input type=\"hidden\" id=\"hddTipo%s\" name=\"hddTipo%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
			"<td align=\"center\">".
				"<div id=\"divMayor%s\">%s</div>".
				"<input type=\"hidden\" id=\"hddMayor%s\" name=\"hddMayor%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
			"<td align=\"center\">".
				"<div id=\"divMenor%s\">%s</div>".
				"<input type=\"hidden\" id=\"hddMenor%s\" name=\"hddMenor%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
			"<td align=\"center\">".
				"<div id=\"divTipo2%s\">%s</div>".
				"<input type=\"hidden\" id=\"hddTipo2%s\" name=\"hddTipo2%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
			"<td align=\"center\">".
				"<div id=\"divMayor2%s\">%s</div>".
				"<input type=\"hidden\" id=\"hddMayor2%s\" name=\"hddMayor2%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
			"<td align=\"center\">".
				"<div id=\"divMenor2%s\">%s</div>".
				"<input type=\"hidden\" id=\"hddMenor2%s\" name=\"hddMenor2%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
			"<td align=\"center\">".
				"<div id=\"divPorcent%s\">%s</div>".
				"<input type=\"hidden\" id=\"hddPorcent%s\" name=\"hddPorcent%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
			"<td>".
				"<a id=\"aEditarNivelUnidad%s\" class=\"modalImg\" rel=\"#divFlotante2\">".
					"<img id=\"idEditar%s\" name=\"imgEditar%s\" class=\"puntero\" title=\"Editar\" src=\"img/iconos/pencil.png\">".
				"</a>".
				"<input type=\"hidden\" id=\"hddIdItemNivelProdUnidad%s\" name=\"hddIdItemNivelProdUnidad%s\" readonly=\"readonly\" value=\"%s\"/>".
			"</td>".
		"</tr>');
		byId('aEditarNivelUnidad%s').onclick = function() {
			abrirDivFlotante2(this, 'tblNivelProductividadUnidad', 'Editar Nivel Productividad Por Unidad', 'editarNivelProdutividadUnidad');
			xajax_formNivelProdUnidad(%s, xajax.getFormValues('frmComision'));
			
		}",
		$contFila,$clase, //tr
			$contFila,
			$contFila, //check
			$contFila,$contFila, //check
			$contFila,$arrayLisTipo[$arrayNivelUnidad[0]], //divTpo
			$contFila,$contFila,$arrayNivelUnidad[0], //hddTipo
			$contFila,number_format(($mayor = ($arrayNivelUnidad[1] == "") ? "0" : $arrayNivelUnidad[1]), 2, ".", ","), //divMayor
			$contFila,$contFila,$arrayNivelUnidad[1], //hddMayor
			$contFila,number_format(($menor = ($arrayNivelUnidad[2] == "") ? "0" : $arrayNivelUnidad[2]), 2, ".", ","), //divMenor
			$contFila,$contFila,$arrayNivelUnidad[2], //hddMenor
			$contFila,$arrayLisTipo2[$arrayNivelUnidad[3]], //divTipo2
			$contFila,$contFila,$arrayNivelUnidad[3], //hddTipo2
			$contFila,number_format(($mayor2 = ($arrayNivelUnidad[4] == "") ? "0" : $arrayNivelUnidad[4]), 2, ".", ","), //divMayor
			$contFila,$contFila,$arrayNivelUnidad[4], //hddMayor
			$contFila,number_format(($menor2 = ($arrayNivelUnidad[5] == "") ? "0" : $arrayNivelUnidad[5]), 2, ".", ","), //divMenor
			$contFila,$contFila,$arrayNivelUnidad[5], //hddMenor
			$contFila,number_format(($porce = ($arrayNivelUnidad[6] == "") ? "0" : $arrayNivelUnidad[6]), 2, ".", ","), //divPorc
			$contFila,$contFila,$arrayNivelUnidad[6], //hddPorc
			$contFila, //aEditar
				$contFila,$contFila, //imgEditar
			$contFila,$contFila,$arrayNivelUnidad[7],
			$contFila,
				$contFila
		);
		
		return array(true, $htmlItmPie, $contFila);
	
}

function errorGuardarComision($objResponse) {
	$objResponse->script("
	byId('btnGuardarComision').disabled = false;
	byId('btnCancelarComision').disabled = false;");
}
?>