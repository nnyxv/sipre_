<?php
//ES UNA COPIA COMPLETA DE VEHICULOS

function asignarArancelFamilia($idArancelFamilia, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryArancelFamilia = sprintf("SELECT 
		arancel_familia.id_arancel_familia,
		arancel_familia.id_arancel_grupo,
		arancel_familia.codigo_familia,
		arancel_familia.codigo_arancel,
		arancel_familia.descripcion_arancel,
		arancel_grupo.codigo_grupo,
		arancel_grupo.porcentaje_grupo
	FROM pg_arancel_familia arancel_familia
		INNER JOIN pg_arancel_grupo arancel_grupo ON (arancel_familia.id_arancel_grupo = arancel_grupo.id_arancel_grupo)
	WHERE arancel_familia.id_arancel_familia = %s;",
		valTpDato($idArancelFamilia, "int"));
	$rsArancelFamilia = mysql_query($queryArancelFamilia);
	if (!$rsArancelFamilia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArancelFamilia = mysql_fetch_assoc($rsArancelFamilia);
	
	$objResponse->assign("txtIdArancelFamilia","value",$rowArancelFamilia['id_arancel_familia']);
	$objResponse->assign("txtCodigoArancelFamilia","value",utf8_encode($rowArancelFamilia['codigo_arancel']));
	$objResponse->assign("txtDescripcionArancelFamilia","value",utf8_encode($rowArancelFamilia['descripcion_arancel']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarArancelFamilia').click();");
	}
	
	return $objResponse;
}

function buscarArancelFamilia($frmBuscarArancelFamilia) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarArancelFamilia['txtCriterioBuscarArancelFamilia']);
	
	$objResponse->loadCommands(listaArancelFamilia(0, "codigo_arancel", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listaEmpresa(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarImpuesto($frmBuscarImpuesto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarImpuesto['txtCriterioBuscarImpuesto']);
	
	$objResponse->loadCommands(listaImpuesto(0, "idIva", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarUnidadBasica($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstCatalogoBuscar'],
		$frmBuscar['lstMarcaBuscar'],
		$frmBuscar['lstModeloBuscar'],
		$frmBuscar['lstVersionBuscar'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaUnidadBasica(0, "uni_bas.id_uni_bas", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstAno($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAno\" name=\"lstAno\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_ano']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_ano']."\">".utf8_encode($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAno","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstClase($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_clase ORDER BY nom_clase");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstClase\" name=\"lstClase\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_clase']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_clase']."\">".utf8_encode($row['nom_clase'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstClase","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCombustible($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_combustible ORDER BY nom_combustible");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstCombustible\" name=\"lstCombustible\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_combustible']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_combustible']."\">".utf8_encode($row['nom_combustible'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCombustible","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMarcaModeloVersion($tpLst, $idLstOrigen, $nombreObjeto, $padreId = "", $selId = "") {
	$objResponse = new xajaxResponse();
	
	switch ($tpLst) {
		case "unidad_basica" : $arraySelec = array("lstPadre","lstMarca","lstModelo","lstVersion");
	}
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	// SI EL OBJETO NO ES EL ULTIMO ASIGNA LA FUNCION PARA CARGAR EL SIGUIENTE OBJETO
	if (($posList + 1) != count($arraySelec) - 1)
		$onChange = "onchange=\"xajax_cargaLstMarcaModeloVersion('".$tpLst."', '".$arraySelec[$posList+1]."', '".$nombreObjeto."', this.value);\"";
	
	
	$html = "<select id=\"".$arraySelec[$posList+1].$nombreObjeto."\" name=\"".$arraySelec[$posList+1].$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:99%\">";
	
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
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
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
				WHERE modelo.id_marca = %s
				ORDER BY modelo.nom_modelo;",
					valTpDato($padreId, "int"));
				$campoId = "id_modelo";
				$campoDesc = "nom_modelo";
				break;
			case 2 :
				$query = sprintf("SELECT * FROM an_version vers
				WHERE vers.id_modelo = %s
				ORDER BY vers.nom_version;",
					valTpDato($padreId, "int"));
				$campoId = "id_version";
				$campoDesc = "nom_version";
				break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row[$campoId]) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$row[$campoId]."\">".utf8_encode($row[$campoDesc])."</option>";
		}
	}
	$html .= "</select>";
	
	$objResponse->assign("td".$arraySelec[$posList+1].$nombreObjeto, 'innerHTML', $html);
	
	return $objResponse;
}

function cargaLstPaisOrigen($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_origen ORDER BY nom_origen");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstPaisOrigen\" name=\"lstPaisOrigen\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_origen']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_origen']."\">".utf8_encode($row['nom_origen'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstPaisOrigen","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTransmision($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_transmision ORDER BY nom_transmision");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTransmision\" name=\"lstTransmision\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_transmision']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_transmision']."\">".utf8_encode($row['nom_transmision'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTransmision","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstUso($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_uso ORDER BY nom_uso");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstUso\" name=\"lstUso\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_uso']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_uso']."\">".utf8_encode($row['nom_uso'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstUso","innerHTML",$html);
	
	return $objResponse;
}

function eliminarUnidadBasica($idUnidadBasica, $frmListaUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"sa_mantenimiento_unidad_basica","eliminar")) { return $objResponse; }
	
	if (isset($idUnidadBasica)) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM an_uni_bas WHERE id_uni_bas = %s;",
			valTpDato($idUnidadBasica, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Eliminacion realizada con éxito");
		
		$objResponse->loadCommands(listaUnidadBasica(
			$frmListaUnidadBasica['pageNum'],
			$frmListaUnidadBasica['campOrd'],
			$frmListaUnidadBasica['tpOrd'],
			$frmListaUnidadBasica['valBusq']));
	}
		
	return $objResponse;
}

function eliminarUnidadBasicaEmpresa($frmUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmUnidadBasica['cbxItm'])) {
		foreach($frmUnidadBasica['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
		$objResponse->script("xajax_eliminarUnidadBasicaEmpresa(xajax.getFormValues('frmUnidadBasica'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUnidadBasica['cbx'];
	
	return $objResponse;
}

function eliminarUnidadBasicaImpuesto($frmUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmUnidadBasica['cbxItmImpuesto'])) {
		foreach($frmUnidadBasica['cbxItmImpuesto'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmImpuesto:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarUnidadBasicaImpuesto(xajax.getFormValues('frmUnidadBasica'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUnidadBasica['cbx2'];
	
	return $objResponse;
}

function formUnidadBasica($idUnidadBasica, $frmUnidadBasica, $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUnidadBasica['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmUnidadBasica['cbx2'];
	
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
			fila = document.getElementById('trItmImpuesto:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	if ($idUnidadBasica > 0) {
		if (!xvalidaAcceso($objResponse,"sa_mantenimiento_unidad_basica","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarUnidadBasica').click();"); return $objResponse; }
		
		$query = sprintf("SELECT * FROM an_uni_bas
		WHERE id_uni_bas = %s;",
			valTpDato($idUnidadBasica, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->assign("hddIdUnidadBasica", "value", $row['id_uni_bas']);
		$objResponse->assign("txtNombreUnidadBasica", "value", utf8_encode($row['nom_uni_bas']));
		$objResponse->assign("txtClaveUnidadBasica", "value", utf8_encode($row['clv_uni_bas']));
		$objResponse->assign("txtDescripcion", "innerHTML", utf8_encode($row['des_uni_bas']));
		$objResponse->loadCommands(cargaLstMarcaModeloVersion("unidad_basica", "lstPadre", "UnidadBasica", "", $row['mar_uni_bas']));
		$objResponse->loadCommands(cargaLstMarcaModeloVersion('unidad_basica', 'lstMarca', 'UnidadBasica', $row['mar_uni_bas'], $row['mod_uni_bas']));
		$objResponse->loadCommands(cargaLstMarcaModeloVersion('unidad_basica', 'lstModelo', 'UnidadBasica', $row['mod_uni_bas'], $row['ver_uni_bas']));
		$objResponse->loadCommands(cargaLstAno($row['ano_uni_bas']));
		$objResponse->call("selectedOption","lstCatalogo",$row['catalogo']);
		
		$objResponse->loadCommands(asignarArancelFamilia($row['id_arancel_familia'], "false"));
		
		$objResponse->loadCommands(cargaLstPaisOrigen($row['ori_uni_bas']));
		$objResponse->loadCommands(cargaLstClase($row['cla_uni_bas']));
		$objResponse->loadCommands(cargaLstUso($row['tip_uni_bas']));
		$objResponse->loadCommands(cargaLstTransmision($row['trs_uni_bas']));
		$objResponse->loadCommands(cargaLstCombustible($row['com_uni_bas']));
	
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		$objResponse->assign("imgArticulo","src",$imgFoto);
		$objResponse->assign("hddUrlImagen","value",$row['imagen_auto']);
		
		$objResponse->assign("txtNumeroPuertas", "value", $row['pto_uni_bas']);
		$objResponse->assign("txtNumeroCilindros", "value", $row['cil_uni_bas']);
		$objResponse->assign("txtCilindrada", "value", $row['ccc_uni_bas']);
		$objResponse->assign("txtCaballosFuerza", "value", $row['cab_uni_bas']);
		$objResponse->assign("txtCapacidad", "value", $row['cap_uni_bas']);
		$objResponse->assign("txtUnidad", "value", $row['uni_uni_bas']);
		$objResponse->assign("txtAnoGarantia", "value", $row['anos_de_garantia']);
		$objResponse->assign("txtKmGarantia", "value", number_format($row['kilometraje'], 2, ".", ","));
		
		$objResponse->assign("txtFechaLista", "value", date("d-m-Y", strtotime($row['pvp_fecha'])));
		$objResponse->assign("txtPrecio1", "value", number_format($row['pvp_venta1'], 2, ".", ","));
		$objResponse->assign("txtPrecio2", "value" ,number_format($row['pvp_venta2'], 2, ".", ","));
		$objResponse->assign("txtPrecio3", "value", number_format($row['pvp_venta3'], 2, ".", ","));
		$objResponse->assign("txtCosto", "value", number_format($row['pvp_costo'], 2, ".", ","));
		
		$queryUnidadBasicaImpuesto = sprintf("SELECT * FROM an_unidad_basica_impuesto uni_bas_impuesto WHERE uni_bas_impuesto.id_unidad_basica = %s;",
			valTpDato($idUnidadBasica, "int"));
		$rsUnidadBasicaImpuesto = mysql_query($queryUnidadBasicaImpuesto);
		if (!$rsUnidadBasicaImpuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowUnidadBasicaImpuesto = mysql_fetch_assoc($rsUnidadBasicaImpuesto)) {
			$Result1 = insertarItemImpuesto($contFila2, $rowUnidadBasicaImpuesto['id_unidad_basica_impuesto'], $rowUnidadBasicaImpuesto['id_impuesto']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila2 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj2[] = $contFila2;
			}
		}
		
		$queryUnidadEmp = sprintf("SELECT * FROM sa_unidad_empresa unidad_emp
		WHERE unidad_emp.id_unidad_basica = %s;",
			valTpDato($idUnidadBasica, "int"));
		$rsUnidadEmp = mysql_query($queryUnidadEmp);
		if (!$rsUnidadEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowUnidadEmp = mysql_fetch_assoc($rsUnidadEmp)) {
			$Result1 = insertarItemEmpresa($contFila, $rowUnidadEmp['id_unidad_empresa'], $rowUnidadEmp['id_empresa']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		}
		
		if ($bloquearObj == true) {
			$objResponse->script("
			byId('fleUrlImagen').style.display = 'none';
			byId('aListarArancelFamilia').style.display = 'none';
			byId('aNuevoImpuesto').style.display = 'none';
			byId('btnEliminarImpuesto').style.display = 'none';
			byId('aNuevoEmpresa').style.display = 'none';
			byId('btnEliminarEmpresa').style.display = 'none';
			byId('opciones_copia_p').style.display = 'none';
			byId('btnGuardarUnidadBasica').style.display = 'none';");
		}
		
		$readOnly = ($bloquearObj == true) ? "true" : "false";
		$disabled = ($bloquearObj == true) ? "true" : "false";
		
		$objResponse->script(sprintf("
		byId('txtNombreUnidadBasica').readOnly = %s;
		byId('txtClaveUnidadBasica').readOnly = %s;
		byId('txtDescripcion').readOnly = %s;
		byId('lstMarcaUnidadBasica').disabled = %s;
		byId('lstModeloUnidadBasica').disabled = %s;
		byId('lstVersionUnidadBasica').disabled = %s;
		byId('lstAno').disabled = %s;
		byId('lstCatalogo').disabled = %s;
		
		byId('lstPaisOrigen').disabled = %s;
		byId('lstClase').disabled = %s;
		byId('lstUso').disabled = %s;
		byId('txtNumeroPuertas').readOnly = %s;
		byId('txtNumeroCilindros').readOnly = %s;
		byId('txtCilindrada').readOnly = %s;
		byId('txtCaballosFuerza').readOnly = %s;
		byId('lstTransmision').disabled = %s;
		byId('lstCombustible').disabled = %s;
		byId('txtCapacidad').readOnly = %s;
		byId('txtUnidad').readOnly = %s;
		byId('txtAnoGarantia').readOnly = %s;
		byId('txtKmGarantia').readOnly = %s;
		
		byId('txtFechaLista').readOnly = %s;
		byId('txtPrecio1').readOnly = %s;
		byId('txtPrecio2').readOnly = %s;
		byId('txtPrecio3').readOnly = %s;
		byId('txtCosto').readOnly = %s;",
			$readOnly,
			$readOnly,
			$readOnly,
			$disabled,
			$disabled,
			$disabled,
			$disabled,
			$disabled,
			
			$disabled,
			$disabled,
			$disabled,
			$readOnly,
			$readOnly,
			$readOnly,
			$readOnly,
			$disabled,
			$disabled,
			$readOnly,
			$readOnly,
			$readOnly,
			$readOnly,
			
			$readOnly,
			$readOnly,
			$readOnly,
			$readOnly,
			$readOnly));
	} else {
		if (!xvalidaAcceso($objResponse,"sa_mantenimiento_unidad_basica","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarUnidadBasica').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstMarcaModeloVersion('unidad_basica', 'lstPadre', 'UnidadBasica'));
		$objResponse->loadCommands(cargaLstAno());
		$objResponse->loadCommands(cargaLstPaisOrigen());
		$objResponse->loadCommands(cargaLstClase());
		$objResponse->loadCommands(cargaLstUso());
		$objResponse->loadCommands(cargaLstTransmision());
		$objResponse->loadCommands(cargaLstCombustible());
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		$objResponse->assign("imgArticulo","src",$imgFoto);
		
		// INSERTA LOS IMPUESTOS DEL ARTICULO
		$query = sprintf("SELECT * FROM pg_iva iva WHERE iva.estado = 1 AND iva.tipo IN (1,6) AND iva.activo = 1;");
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemImpuesto($contFila2, "", $row['idIva']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila2 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj2[] = $contFila2;
			}
		}
		
		$objResponse->script("xajax_insertarEmpresa(".$idEmpresa.", xajax.getFormValues('frmUnidadBasica'));");
	}
	
	return $objResponse;
}

function guardarUnidadBasica($frmUnidadBasica, $frmListaUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUnidadBasica['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmUnidadBasica['cbx2'];
	
	mysql_query("START TRANSACTION;");
	
	$idUnidadBasica = $frmUnidadBasica['hddIdUnidadBasica'];
	
	$copia = ($frmUnidadBasica['unidad_copy'] == 1) ? true: false;
	
	// VERIFICA QUE NO EXISTA EL NOMBRE O LA CLAVE DE LA UNIDAD BASICA
	$query = sprintf("SELECT * FROM an_uni_bas
	WHERE nom_uni_bas LIKE %s
		OR clv_uni_bas LIKE %s;",
		valTpDato($frmUnidadBasica['txtNombreUnidadBasica'], "text"),
		valTpDato($frmUnidadBasica['txtClaveUnidadBasica'], "text"));
	$rs = mysql_query($query);
	if (!$rs) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0 && (!($idUnidadBasica > 0) || $copia == true)) {
		errorGuardarUnidadBasica($objResponse);
		return $objResponse->alert("Ya existe una unidad con alguno de los datos de Nombre o Clave ingresados");
	}
	
	if ($copia == true) {
		$queryUnidad = sprintf("SELECT * FROM an_uni_bas uni_bas
		WHERE id_uni_bas = %s;",
			valTpDato($idUnidadBasica, "int"));
		$rsUnidad = mysql_query($queryUnidad);
		if (!$rsUnidad) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowUnidad = mysql_fetch_assoc($rsUnidad);
		
		if ($frmUnidadBasica['copy_paquete']) {
			//verificambio el cambio de version
			if ($rowUnidad['ver_uni_bas'] != $frmUnidadBasica['lstVersionUnidadBasica']) {
				errorGuardarUnidadBasica($objResponse);
				return $objResponse->alert("No se pueden copiar los Paquetes de Servicios si cambia la versión de la unidad básica a copiar.");
			}
			//verificando la copia de paquetes y repuestos previa
			if ($frmUnidadBasica['copy_repuesto'] != '1' || $frmUnidadBasica['copy_tempario'] != '1') {		
				errorGuardarUnidadBasica($objResponse);			
				return $objResponse->alert("Para copiar los Paquetes debe especificar la copia de Repuestos y Tempario para garantizar compatibilidad");
			}
		}
		
		$idUnidadBasicaOriginal = $idUnidadBasica;
		$idUnidadBasica = "";
	}
	
	if ($idUnidadBasica > 0) {
		if (!xvalidaAcceso($objResponse,"sa_mantenimiento_unidad_basica","editar")) { errorGuardarUnidadBasica($objResponse); return $objResponse; }
		
		$updateSQL = sprintf("UPDATE an_uni_bas SET
			nom_uni_bas = %s,
			des_uni_bas = %s,
			mar_uni_bas = %s,
			mod_uni_bas = %s,
			ver_uni_bas = %s,
			ano_uni_bas = %s,
			cla_uni_bas = %s,
			tip_uni_bas = %s,
			clv_uni_bas = %s,
			cc_uni_bas = %s,
			pto_uni_bas = %s,
			cil_uni_bas = %s,
			trs_uni_bas = %s,
			com_uni_bas = %s,
			cab_uni_bas = %s,
			ccc_uni_bas = %s,
			ori_uni_bas = %s,
			uni_uni_bas = %s,
			cap_uni_bas = %s,
			pvp_fecha = %s,
			pvp_costo = %s,
			pvp_venta1 = %s,
			pvp_venta2 = %s,
			pvp_venta3 = %s,
			id_empresa = %s,
			catalogo = %s,
			imagen_auto = %s,
			anos_de_garantia = %s,
			kilometraje = %s,
			id_arancel_familia = %s
		WHERE id_uni_bas = %s;",
			valTpDato($frmUnidadBasica['txtNombreUnidadBasica'], "text"),
			valTpDato($frmUnidadBasica['txtDescripcion'], "text"),
			valTpDato($frmUnidadBasica['lstMarcaUnidadBasica'], "int"),
			valTpDato($frmUnidadBasica['lstModeloUnidadBasica'], "int"),
			valTpDato($frmUnidadBasica['lstVersionUnidadBasica'], "int"),
			valTpDato($frmUnidadBasica['lstAno'], "int"),
			valTpDato($frmUnidadBasica['lstClase'], "int"),
			valTpDato($frmUnidadBasica['lstUso'], "int"),
			valTpDato($frmUnidadBasica['txtClaveUnidadBasica'], "text"),
			valTpDato($frmUnidadBasica['txtCilindrada'], "text"),
			valTpDato($frmUnidadBasica['txtNumeroPuertas'], "int"),
			valTpDato($frmUnidadBasica['txtNumeroCilindros'], "text"),
			valTpDato($frmUnidadBasica['lstTransmision'], "int"),
			valTpDato($frmUnidadBasica['lstCombustible'], "int"),
			valTpDato($frmUnidadBasica['txtCaballosFuerza'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtCilindrada'], "text"),
			valTpDato($frmUnidadBasica['lstPaisOrigen'], "int"),
			valTpDato($frmUnidadBasica['txtUnidad'], "text"),
			valTpDato($frmUnidadBasica['txtCapacidad'], "real_inglesa"),
			valTpDato(date("Y-m-d", strtotime($frmUnidadBasica['txtFechaLista'])), "date"),
			valTpDato($frmUnidadBasica['txtCosto'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtPrecio1'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtPrecio2'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtPrecio3'], "real_inglesa"),
			valTpDato($idEmpresa, "int"),
			valTpDato($frmUnidadBasica['lstCatalogo'], "boolean"),
			valTpDato($frmUnidadBasica['hddUrlImagen'], "text"),
			valTpDato($frmUnidadBasica['txtAnoGarantia'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtKmGarantia'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtIdArancelFamilia'], "int"),
			valTpDato($idUnidadBasica, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"sa_mantenimiento_unidad_basica","insertar")) { errorGuardarUnidadBasica($objResponse); return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO an_uni_bas (nom_uni_bas, des_uni_bas, mar_uni_bas, mod_uni_bas, ver_uni_bas, ano_uni_bas, cla_uni_bas, tip_uni_bas, clv_uni_bas, cc_uni_bas, pto_uni_bas, cil_uni_bas, trs_uni_bas, com_uni_bas, cab_uni_bas, ccc_uni_bas, ori_uni_bas, uni_uni_bas, cap_uni_bas, pvp_fecha, pvp_costo, pvp_venta1, pvp_venta2, pvp_venta3, id_empresa, catalogo, imagen_auto, anos_de_garantia, kilometraje, id_arancel_familia)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($frmUnidadBasica['txtNombreUnidadBasica'], "text"),
			valTpDato($frmUnidadBasica['txtDescripcion'], "text"),
			valTpDato($frmUnidadBasica['lstMarcaUnidadBasica'], "int"),
			valTpDato($frmUnidadBasica['lstModeloUnidadBasica'], "int"),
			valTpDato($frmUnidadBasica['lstVersionUnidadBasica'], "int"),
			valTpDato($frmUnidadBasica['lstAno'], "int"),
			valTpDato($frmUnidadBasica['lstClase'], "int"),
			valTpDato($frmUnidadBasica['lstUso'], "int"),
			valTpDato($frmUnidadBasica['txtClaveUnidadBasica'], "text"),
			valTpDato($frmUnidadBasica['txtCilindrada'], "text"),
			valTpDato($frmUnidadBasica['txtNumeroPuertas'], "int"),
			valTpDato($frmUnidadBasica['txtNumeroCilindros'], "text"),
			valTpDato($frmUnidadBasica['lstTransmision'], "int"),
			valTpDato($frmUnidadBasica['lstCombustible'], "int"),
			valTpDato($frmUnidadBasica['txtCaballosFuerza'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtCilindrada'], "text"),
			valTpDato($frmUnidadBasica['lstPaisOrigen'], "int"),
			valTpDato($frmUnidadBasica['txtUnidad'], "text"),
			valTpDato($frmUnidadBasica['txtCapacidad'], "real_inglesa"),
			valTpDato(date("Y-m-d", strtotime($frmUnidadBasica['txtFechaLista'])), "date"),
			valTpDato($frmUnidadBasica['txtCosto'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtPrecio1'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtPrecio2'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtPrecio3'], "real_inglesa"),
			valTpDato($idEmpresa, "int"),
			valTpDato($frmUnidadBasica['lstCatalogo'], "boolean"),
			valTpDato($frmUnidadBasica['hddUrlImagen'], "text"),
			valTpDato($frmUnidadBasica['txtAnoGarantia'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtKmGarantia'], "real_inglesa"),
			valTpDato($frmUnidadBasica['txtIdArancelFamilia'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idUnidadBasica = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// VERIFICA SI LOS IMPUESTOS ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryUnidadImpuesto = sprintf("SELECT * FROM an_unidad_basica_impuesto uni_bas_impuesto WHERE id_unidad_basica = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rsUnidadImpuesto = mysql_query($queryUnidadImpuesto);
	if (!$rsUnidadImpuesto) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowUnidadImpuesto = mysql_fetch_assoc($rsUnidadImpuesto)) {
		$existRegDet = false;
		if (isset($arrayObj2)) {
			foreach($arrayObj2 as $indice => $valor) {
				if ($rowUnidadImpuesto['id_unidad_basica_impuesto'] == $frmUnidadBasica['hddIdUnidadBasicaImpuesto'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM an_unidad_basica_impuesto WHERE id_unidad_basica_impuesto = %s;",
				valTpDato($rowUnidadImpuesto['id_unidad_basica_impuesto'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA LOS IMPUESTOS NUEVOS
	if (isset($arrayObj2)) {
		foreach($arrayObj2 as $indice => $valor) {
			$idImpuesto = $frmUnidadBasica['hddIdImpuesto'.$valor];
			$frmUnidadBasica['hddIdUnidadBasicaImpuesto'.$valor] = ($copia == true) ? "" : $frmUnidadBasica['hddIdUnidadBasicaImpuesto'.$valor];
			
			if ($idImpuesto > 0 && $frmUnidadBasica['hddIdUnidadBasicaImpuesto'.$valor] == "") {
				$insertSQL = sprintf("INSERT INTO an_unidad_basica_impuesto (id_unidad_basica, id_impuesto)
				VALUE (%s, %s);",
					valTpDato($idUnidadBasica, "int"),
					valTpDato($idImpuesto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// VERIFICA SI LAS EMPRESAS ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryUnidadEmp = sprintf("SELECT * FROM sa_unidad_empresa unidad_emp
	WHERE id_unidad_basica = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rsUnidadEmp = mysql_query($queryUnidadEmp);
	if (!$rsUnidadEmp) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowUnidadEmp = mysql_fetch_assoc($rsUnidadEmp)) {
		$existRegDet = false;
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				if ($rowUnidadEmp['id_unidad_empresa'] == $frmUnidadBasica['hddIdUnidadEmpresa'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM sa_unidad_empresa WHERE id_unidad_empresa = %s;",
				valTpDato($rowUnidadEmp['id_unidad_empresa'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		}
	}
	
	// INSERTA EL DETALLE
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$idEmpresa = $frmUnidadBasica['hddIdEmpresa'.$valor];
			$frmUnidadBasica['hddIdUnidadEmpresa'.$valor] = ($copia == true) ? "" : $frmUnidadBasica['hddIdUnidadEmpresa'.$valor];
			
			if ($idEmpresa > 0 && $frmUnidadBasica['hddIdUnidadEmpresa'.$valor] == "") {
				$insertSQL = sprintf("INSERT INTO sa_unidad_empresa (id_unidad_basica, id_empresa)
				VALUE (%s, %s);",
					valTpDato($idUnidadBasica, "int"),
					valTpDato($idEmpresa, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// ACTUALIZA SI TIENE IMPUESTO O NO
	$updateSQL = sprintf("UPDATE an_uni_bas SET
		isan_uni_bas = IF((SELECT COUNT(id_unidad_basica)
							FROM an_unidad_basica_impuesto uni_bas_impuesto
								INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
							WHERE iva.tipo IN (1,6) AND iva.estado = 1
								AND uni_bas_impuesto.id_unidad_basica = an_uni_bas.id_uni_bas) > 0, 1, NULL),
		impuesto_lujo = IF((SELECT COUNT(id_unidad_basica)
							FROM an_unidad_basica_impuesto uni_bas_impuesto
								INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
							WHERE iva.tipo IN (3,2) AND iva.estado = 1
								AND uni_bas_impuesto.id_unidad_basica = an_uni_bas.id_uni_bas) > 0, 1, NULL)
	WHERE id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	if ($copia == true) {
		//confirmando copiado de temparios
		if ($frmUnidadBasica['copy_tempario'] == 1) {
			//ejecutando copia de temparios:
			$insertSQL = sprintf("INSERT INTO sa_tempario_det (id_unidad_basica, id_tempario, ut)
			SELECT %s AS idub, origen.id_tempario, origen.ut FROM sa_tempario_det AS origen WHERE id_unidad_basica = %s;",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idUnidadBasicaOriginal, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		//confirmando copiado de repuestos
		if ($frmUnidadBasica['copy_repuesto'] == 1) {
			//ejecutando copia de temparios:
			$insertSQL = sprintf("INSERT INTO iv_articulos_modelos_compatibles (id_unidad_basica, id_articulo, descripcion)
			SELECT %s AS idub, origen.id_articulo, origen.descripcion FROM iv_articulos_modelos_compatibles AS origen WHERE id_unidad_basica = %s;",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idUnidadBasicaOriginal, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		//confirmando copiado de paquetes
		if ($frmUnidadBasica['copy_paquete'] == 1) {
			//ejecutando copia de temparios:
			$insertSQL = sprintf("INSERT INTO sa_paq_unidad (id_unidad_basica, id_paquete)
			SELECT %s AS idub, origen.id_paquete FROM sa_paq_unidad AS origen WHERE id_unidad_basica = %s;",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idUnidadBasicaOriginal, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	mysql_query("COMMIT;");
	
	errorGuardarUnidadBasica($objResponse);
	$objResponse->alert("Registro Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarUnidadBasica').click();");
	
	$objResponse->loadCommands(listaUnidadBasica(
		$frmListaUnidadBasica['pageNum'],
		$frmListaUnidadBasica['campOrd'],
		$frmListaUnidadBasica['tpOrd'],
		$frmListaUnidadBasica['valBusq']));
	
	return $objResponse;
}

function insertarEmpresa($idEmpresa, $frmUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUnidadBasica['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idEmpresa > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmUnidadBasica['hddIdEmpresa'.$valor] == $idEmpresa) {
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

function insertarImpuesto($idImpuesto, $frmUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmUnidadBasica['cbx2'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idImpuesto > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($frmUnidadBasica['hddIdImpuesto'.$valor] == $idImpuesto) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemImpuesto($contFila, "", $idImpuesto);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				// DESBLOQUEA LOS BOTONES DEL LISTADO
				for ($cont = 1; $cont <= 20; $cont++) {
					$objResponse->script(sprintf("byId('btnInsertarImpuesto%s').disabled = false;",
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
		$objResponse->script(sprintf("byId('btnInsertarImpuesto%s').disabled = false;",
			$cont));
	}
	
	return $objResponse;
}

function listaArancelFamilia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(arancel_familia.codigo_familia LIKE %s
		OR arancel_familia.codigo_arancel LIKE %s
		OR arancel_familia.descripcion_arancel LIKE %s
		OR arancel_grupo.codigo_grupo LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		arancel_familia.id_arancel_familia,
		arancel_familia.id_arancel_grupo,
		arancel_familia.codigo_familia,
		arancel_familia.codigo_arancel,
		arancel_familia.descripcion_arancel,
		arancel_grupo.codigo_grupo,
		arancel_grupo.porcentaje_grupo
	FROM pg_arancel_familia arancel_familia
		INNER JOIN pg_arancel_grupo arancel_grupo ON (arancel_familia.id_arancel_grupo = arancel_grupo.id_arancel_grupo) %s", $sqlBusq);
	
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaArancelFamilia", "10%", $pageNum, "codigo_grupo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Grupo");
		$htmlTh .= ordenarCampo("xajax_listaArancelFamilia", "10%", $pageNum, "codigo_familia", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Familia");
		$htmlTh .= ordenarCampo("xajax_listaArancelFamilia", "14%", $pageNum, "codigo_arancel", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Arancelario");
		$htmlTh .= ordenarCampo("xajax_listaArancelFamilia", "56%", $pageNum, "descripcion_arancel", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaArancelFamilia", "10%", $pageNum, "porcentaje_grupo", $campOrd, $tpOrd, $valBusq, $maxRows, "% Arancelario");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarArancelFamilia('".$row['id_arancel_familia']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_grupo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_familia'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_arancel'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_arancel'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['porcentaje_grupo'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArancelFamilia","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, $spanRIF);
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "33%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "33%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, ("Sucursal"));
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
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

function listaImpuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iva.estado = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iva.tipo IN (1,2,3,6)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(observacion LIKE %s
		OR tipo_impuesto LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		iva.idIva,
		iva.iva,
		iva.observacion,
		tipo_imp.tipo_impuesto,
		iva.estado,
		iva.activo
	FROM pg_iva iva
		INNER JOIN pg_tipo_impuesto tipo_imp ON (iva.tipo = tipo_imp.id_tipo_impuesto) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "8%", $pageNum, "idIva", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "24%", $pageNum, "tipo_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "44%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "12%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaImpuesto", "12%", $pageNum, "activo", $campOrd, $tpOrd, $valBusq, $maxRows, "Predeterminado");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$activo = ($row['activo'] == 1) ? "SI" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarImpuesto%s\" onclick=\"validarInsertarImpuesto('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['idIva']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['idIva']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['tipo_impuesto'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['iva'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($activo)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"6\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
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
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaImpuesto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPrecios($idUnidadBasica) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	if (!xvalidaAcceso($objResponse,"sa_mantenimiento_unidad_basica","")) { return $objResponse; }
	
	// INSERTA LOS PRECIOS DEL ARTICULO
	$queryUnidadPrecio = sprintf("SELECT
		'Precio 1' AS descripcion_precio,
		uni_bas.pvp_venta1 AS precio,
		
		(uni_bas.pvp_venta1 * (SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (6)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100) AS monto_iva,
		
		(uni_bas.pvp_venta1 * (SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (2)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100) AS monto_iva_lujo,
									
		(uni_bas.pvp_venta1
		+
		(uni_bas.pvp_venta1 * IFNULL((SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (6)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100, 0))
		+
		(uni_bas.pvp_venta1 * IFNULL((SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (2)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100, 0))) AS precio_con_iva
	FROM an_uni_bas uni_bas
	WHERE uni_bas.id_uni_bas = %s
		AND uni_bas.pvp_venta1 > 0
	
	UNION
	
	SELECT
		'Precio 2' AS descripcion_precio,
		uni_bas.pvp_venta2 AS precio,
		
		(uni_bas.pvp_venta2 * (SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (6)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100) AS monto_iva,
		
		(uni_bas.pvp_venta2 * (SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (2)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100) AS monto_iva_lujo,
									
		(uni_bas.pvp_venta2
		+
		(uni_bas.pvp_venta2 * IFNULL((SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (6)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100, 0))
		+
		(uni_bas.pvp_venta2 * IFNULL((SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (2)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100, 0))) AS precio_con_iva
	FROM an_uni_bas uni_bas
	WHERE uni_bas.id_uni_bas = %s
		AND uni_bas.pvp_venta2 > 0
	
	UNION
	
	SELECT
		'Precio 3' AS descripcion_precio,
		uni_bas.pvp_venta3 AS precio,
		
		(uni_bas.pvp_venta3 * (SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (6)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100) AS monto_iva,
		
		(uni_bas.pvp_venta3 * (SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (2)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100) AS monto_iva_lujo,
									
		(uni_bas.pvp_venta3
		+
		(uni_bas.pvp_venta3 * IFNULL((SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (6)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100, 0))
		+
		(uni_bas.pvp_venta3 * IFNULL((SELECT SUM(iva.iva)
								FROM pg_iva iva
									INNER JOIN an_unidad_basica_impuesto uni_bas_impuesto ON (iva.idIva = uni_bas_impuesto.id_impuesto)
								WHERE iva.tipo IN (2)
									AND uni_bas_impuesto.id_unidad_basica = uni_bas.id_uni_bas) / 100, 0))) AS precio_con_iva
	FROM an_uni_bas uni_bas
	WHERE uni_bas.id_uni_bas = %s
		AND uni_bas.pvp_venta3 > 0;",
		valTpDato($idUnidadBasica, "int"),
		valTpDato($idUnidadBasica, "int"),
		valTpDato($idUnidadBasica, "int"));
	$rsUnidadPrecio = mysql_query($queryUnidadPrecio);
	if (!$rsUnidadPrecio) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRowsUnidadPrecio = mysql_num_rows($rsUnidadPrecio);
	$htmlTblIni = "<table border=\"0\" width=\"598\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"40%\">Descripción</td>";
		$htmlTh .= "<td width=\"15%\">".$spanPrecioUnitario."</td>";
		$htmlTh .= "<td width=\"15%\">Impuesto</td>";
		$htmlTh .= "<td width=\"15%\">Impuesto Lujo</td>";
		$htmlTh .= "<td width=\"15%\">Total</td>";
	$htmlTh .= "</tr>";
	
	while ($rowUnidadPrecio = mysql_fetch_assoc($rsUnidadPrecio)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".utf8_encode($rowUnidadPrecio['descripcion_precio'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowUnidadPrecio['precio'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowUnidadPrecio['monto_iva'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($rowUnidadPrecio['monto_iva_lujo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" class=\"divMsjInfo\">".number_format($rowUnidadPrecio['precio_con_iva'], 2, ".", ",")."</td>";
		$htmlTb .= "</ttr>";
	}
	$htmlTblFin .= "</table>";

	if (!($totalRowsUnidadPrecio > 0)) {
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divPrecios","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaUnidadBasica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 21, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("unidad_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_bas.catalogo = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca = %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT vw_iv_modelo.*
	FROM an_uni_bas uni_bas
		LEFT JOIN sa_unidad_empresa unidad_emp ON (uni_bas.id_uni_bas = unidad_emp.id_unidad_basica)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas) %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		
		$htmlTb .= "<td valign=\"top\" width=\"33%\">";
			$htmlTb .= "<table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td rowspan=\"5\" title=\"Id ".$row['id_uni_bas']."\" valign=\"top\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
					$row['id_uni_bas']);
				$htmlTb .= sprintf("<td rowspan=\"5\" style=\"background-color:#FFFFFF\">%s</td>", "<img src=\"".$imgFoto."\" width=\"120\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>", "100%",
					utf8_encode($row['nom_uni_bas']));
				$htmlTb .= "<td rowspan=\"5\">";
					$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onmouseover=\"Tip('<div id=divPrecios></div>', TITLE, 'Lista de Precios de la Unidad %s', WIDTH, 600); xajax_listaPrecios('%s');\" onmouseout=\"UnTip();\"><img class=\"puntero\" src=\"../img/iconos/money.png\" title=\"Ver Precios\"/></a>",
						utf8_encode($row['nom_uni_bas']),
						$row['id_uni_bas']);
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUnidadBasica', '%s', true);\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Unidad Básica\"/></a>",
						$contFila,
						$row['id_uni_bas']);
					$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblUnidadBasica', '%s', false);\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar Unidad Básica\"/></a>",
						$contFila,
						$row['id_uni_bas']);
					$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar Unidad Básica\"/></a>",
						$row['id_uni_bas']);
				$htmlTb .= "</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_marca']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_modelo']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_version']));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>Año %s</td>", utf8_encode($row['nom_ano']));
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
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

$xajax->register(XAJAX_FUNCTION,"asignarArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"buscarArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarImpuesto");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"cargaLstAno");
$xajax->register(XAJAX_FUNCTION,"cargaLstClase");
$xajax->register(XAJAX_FUNCTION,"cargaLstCombustible");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarcaModeloVersion");
$xajax->register(XAJAX_FUNCTION,"cargaLstPaisOrigen");
$xajax->register(XAJAX_FUNCTION,"cargaLstTransmision");
$xajax->register(XAJAX_FUNCTION,"cargaLstUso");
$xajax->register(XAJAX_FUNCTION,"eliminarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"eliminarUnidadBasicaEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarUnidadBasicaImpuesto");
$xajax->register(XAJAX_FUNCTION,"formUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"guardarUnidadBasica");
$xajax->register(XAJAX_FUNCTION,"insertarEmpresa");
$xajax->register(XAJAX_FUNCTION,"insertarImpuesto");
$xajax->register(XAJAX_FUNCTION,"listaArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaImpuesto");
$xajax->register(XAJAX_FUNCTION,"listaPrecios");
$xajax->register(XAJAX_FUNCTION,"listaUnidadBasica");

function buscarEnArray($array, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($array as $indice => $valor) {
		if ($valor == $dato) return $x;
		
		$x++;
	}
	return NULL;
}

function insertarItemEmpresa($contFila, $idUnidadEmpresa = "", $idEmpresa = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales
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
			"<td>%s</td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdUnidadEmpresa%s\" name=\"hddIdUnidadEmpresa%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdEmpresa%s\" name=\"hddIdEmpresa%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['rif']),
			utf8_encode($row['nombre_empresa']),
			utf8_encode($row['nombre_empresa_suc']),
				$contFila, $contFila, $idUnidadEmpresa,
				$contFila, $contFila, $idEmpresa);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemImpuesto($contFila, $hddIdUnidadBasicaImpuesto = "", $idImpuesto = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	// BUSCA LOS DATOS DEL TIPO DE ORDEN
	$query = sprintf("SELECT
		iva.idIva,
		iva.iva,
		iva.observacion,
		tipo_imp.tipo_impuesto,
		iva.estado,
		iva.activo
	FROM pg_iva iva
		INNER JOIN pg_tipo_impuesto tipo_imp ON (iva.tipo = tipo_imp.id_tipo_impuesto)
	WHERE iva.idIva = %s;",
		valTpDato($idImpuesto, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieImpuesto').before('".
		"<tr id=\"trItmImpuesto:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmImpuesto:%s\"><input id=\"cbxItmImpuesto\" name=\"cbxItmImpuesto[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx2\" name=\"cbx2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"right\">%s".
				"<input type=\"hidden\" id=\"hddIdUnidadBasicaImpuesto%s\" name=\"hddIdUnidadBasicaImpuesto%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdImpuesto%s\" name=\"hddIdImpuesto%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['tipo_impuesto']),
			utf8_encode($row['observacion']),
			utf8_encode($row['iva']),
				$contFila, $contFila, $hddIdUnidadBasicaImpuesto,
				$contFila, $contFila, $idImpuesto);
	
	return array(true, $htmlItmPie, $contFila);
}

function errorGuardarUnidadBasica($objResponse) {
	$objResponse->script("
	byId('btnGuardarUnidadBasica').disabled = false;
	byId('btnCancelarUnidadBasica').disabled = false;");
}
?>