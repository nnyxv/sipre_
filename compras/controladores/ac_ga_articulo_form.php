<?php
function cargarArticulo($idArticulo, $idEmpresa, $bloquearObj = "false") {
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos_empresa WHERE id_articulo = %s AND id_empresa = %s",
			$idArticulo,
			$idEmpresa);
	} else {
		$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s", $idArticulo);
	}
	
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	if ($rowArticulo['existencia'] || $rowArticulo['cantidad_reservada']
	|| $rowArticulo['cantidad_disponible_fisica'] || $rowArticulo['cantidad_espera']
	|| $rowArticulo['cantidad_disponible_logica'] || $rowArticulo['cantidad_pedida']
	|| $rowArticulo['cantidad_futura']) {
		$objResponse->script(sprintf("xajax_objetoCodigoDinamicoCompras('tdCodigoArt','%s','%s','%s','','%s');",
			$idEmpresa,
			$rowArticulo['id_empresa_creador'],
			$rowArticulo['codigo_articulo'],
			"true"));
		
		$objResponse->script("
			$('txtDescripcion').readOnly = true;
		");
	} else {
		$objResponse->script(sprintf("xajax_objetoCodigoDinamicoCompras('tdCodigoArt','%s','%s','%s','','%s');",
			$idEmpresa,
			$rowArticulo['id_empresa_creador'],
			$rowArticulo['codigo_articulo'],
			$bloquearObj));
	}
	
	$objResponse->assign("hddIdArticulo","value",$rowArticulo['id_articulo']);
	$objResponse->script("$('imgCodigoBarra').src = '../clases/barcode128.php?type=B&bw=2&pc=1&codigo=".$rowArticulo['id_articulo']."'");
	$objResponse->assign("txtCodigoProveedor","value",$rowArticulo['codigo_articulo_prov']);
	$objResponse->assign("txtDescripcion","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtStockMaximo","value",$rowArticulo['stock_maximo']);
	$objResponse->assign("txtStockMinimo","value",$rowArticulo['stock_minimo']);
	
	// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
	$imgFoto = $rowArticulo['foto'];
	if(!file_exists($rowArticulo['foto']))
		$imgFoto = "../".$_SESSION['logoEmpresaSysGts'];
	
	$objResponse->assign("imgArticulo","src",$imgFoto);
	$objResponse->assign("hddUrlImagen","value",$rowArticulo['foto']);
	
	$objResponse->script(sprintf("xajax_cargaLstArticulo('%s','%s','%s','%s','%s');",
		$rowArticulo['id_marca'],
		$rowArticulo['id_tipo_articulo'],
		$rowArticulo['id_tipo_unidad'],
		$rowArticulo['id_seccion'],
		$rowArticulo['id_subseccion']));

	
	// INSERTA LOS ARTICULOS SUSTITUTOS
	$queryCodigoSustituto = sprintf("SELECT * FROM ga_articulos_codigos_sustitutos WHERE id_articulo = %s", $idArticulo);
	$rsCodigoSustituto = mysql_query($queryCodigoSustituto);
	if (!$rsCodigoSustituto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$sigValor = 1;
	$arrayObj = NULL;
	$clase = "";
	while ($rowCodigoSustituto = mysql_fetch_assoc($rsCodigoSustituto)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s", $rowCodigoSustituto['id_articulo_sustituto']);
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trArtSus:%s', 'class':'textoGris_11px %s', 'title':'trArtSus:%s'}).adopt([
				new Element('td', {'align':'center'}).setHTML(\"<input id='cbxArtSus' name='cbxArtSus[]' type='checkbox' value='%s'/>\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s".
				"<input type='hidden' id='hddIdArtSus%s' name='hddIdArtSus%s' value='%s'/>".
				"<input type='hidden' id='hddIdArtCodigoSustituto%s' name='hddIdArtCodigoSustituto%s' value='%s'/>\"),
				new Element('td', {'align':'center'}).setHTML(\"<button type='button' id='btnArtSus:%s' title='Ver Articulo'><img src='../img/iconos/ico_view.png' align='absmiddle'/></button>\")
			]);
			elemento.injectBefore('trItmPieArtSus');
			
			$('btnArtSus:%s').onclick = function() {xajax_verArticulo('%s','divFlotanteContenido','YES');}",
			$sigValor, $clase, $sigValor,
			$sigValor,
			elimCaracter($rowArticulo['codigo_articulo'],"-"),
			str_replace("\n",", ",htmlentities($rowArticulo['descripcion'])),
			$rowArticulo['existencia'],
			$sigValor, $sigValor, $rowArticulo['id_articulo'],
			$sigValor, $sigValor, $rowCodigoSustituto['id_articulo_codigo_sustituto'],
			$sigValor, $sigValor,
			
			$sigValor, $rowArticulo['id_articulo']));
			
		$arrayObj[] = $sigValor;
		$sigValor++;		
	}
	
	$cadena = "";
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$cadena .= "|".$valor;
		}
	}
	$objResponse->assign("hddObjArtSus","value",$cadena);
	
	
	// INSERTA LOS ARTICULOS ALTERNOS
	$queryCodigoAlterno = sprintf("SELECT * FROM ga_articulos_codigos_alternos WHERE id_articulo = %s", $idArticulo);
	$rsCodigoAlterno = mysql_query($queryCodigoAlterno);
	if (!$rsCodigoAlterno) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$sigValor = 1;
	$arrayObj = NULL;
	$clase = "";
	while ($rowCodigoAlterno = mysql_fetch_assoc($rsCodigoAlterno)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s", $rowCodigoAlterno['id_articulo_alterno']);
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trArtAlt:%s', 'class':'textoGris_11px %s', 'title':'trArtAlt:%s'}).adopt([
				new Element('td', {'align':'center'}).setHTML(\"<input id='cbxArtAlt' name='cbxArtAlt[]' type='checkbox' value='%s'/>\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s".
				"<input type='hidden' id='hddIdArtAlt%s' name='hddIdArtAlt%s' value='%s'/>".
				"<input type='hidden' id='hddIdArtCodigoAlterno%s' name='hddIdArtCodigoAlterno%s' value='%s'/>\"),
				new Element('td', {'align':'center'}).setHTML(\"<button type='button' id='btnArtAlt:%s' title='Ver Articulo'><img src='../img/iconos/ico_view.png'/></button>\")
			]);
			elemento.injectBefore('trItmPieArtAlt');
			
			$('btnArtAlt:%s').onclick = function() {xajax_verArticulo('%s','divFlotanteContenido','YES');}",
			$sigValor, $clase, $sigValor,
			$sigValor,
			elimCaracter($rowArticulo['codigo_articulo'],"-"),
			str_replace("\n",", ",htmlentities($rowArticulo['descripcion'])),
			$rowArticulo['existencia'],
			$sigValor, $sigValor, $rowArticulo['id_articulo'],
			$sigValor, $sigValor, $rowCodigoAlterno['id_articulo_codigo_alterno'],
			$sigValor, $sigValor,
			
			$sigValor, $rowArticulo['id_articulo']));
			
		$arrayObj[] = $sigValor;
		$sigValor++;		
	}
	
	$cadena = "";
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$cadena .= "|".$valor;
		}
	}
	$objResponse->assign("hddObjArtAlt","value",$cadena);
	
	
	// INSERTA LOS MODELOS COMPATIBLES
	$queryModeloCompatible = sprintf("SELECT * FROM vw_ga_articulos_modelos_compatibles WHERE id_articulo = %s", $idArticulo);
	$rsModeloCompatible = mysql_query($queryModeloCompatible);
	if (!$rsModeloCompatible) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$sigValor = 1;
	$arrayObj = NULL;
	$clase = "";
	while ($rowModeloCompatible = mysql_fetch_assoc($rsModeloCompatible)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trModComp:%s', 'class':'textoGris_11px %s', 'title':'trModComp:%s'}).adopt([
				new Element('td', {'align':'center'}).setHTML(\"<input id='cbxModComp' name='cbxModComp[]' type='checkbox' value='%s'/>\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s".
				"<input type='hidden' id='hddIdUnidadBasica%s' name='hddIdUnidadBasica%s' value='%s'/>".
				"<input type='hidden' id='hddIdArtModComp%s' name='hddIdArtModComp%s' value='%s'/>\")
			]);
			elemento.injectBefore('trItmPieModComp');
			
			$('btnArtAlt:%s').onclick = function() {xajax_verArticulo('%s','divFlotanteContenido','YES');}",
			$sigValor, $clase, $sigValor,
			$sigValor,
			$rowModeloCompatible['nom_uni_bas'],
			$rowModeloCompatible['nom_marca'],
			$rowModeloCompatible['nom_modelo'],
			$rowModeloCompatible['nom_version'],
			$sigValor, $sigValor, $rowModeloCompatible['id_uni_bas'],
			$sigValor, $sigValor, $rowModeloCompatible['id_articulo_modelo_compatible'],
			
			$sigValor, $rowArticulo['id_articulo']));
		$arrayObj[] = $sigValor;
		$sigValor++;		
	}
	
	$cadena = "";
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$cadena .= "|".$valor;
		}
	}
	$objResponse->assign("hddObjModComp","value",$cadena);
	
	
	// INSERTA LOS ALMACENES
	$queryAlm = sprintf("SELECT 
		vw_ga_art_alm.*,
		vw_ga_art_emp_por_ubic.existencia,
		vw_ga_art_emp_por_ubic.cantidad_reservada,
		vw_ga_art_emp_por_ubic.cantidad_disponible_fisica,
		vw_ga_art_emp_por_ubic.cantidad_espera,
		vw_ga_art_emp_por_ubic.cantidad_disponible_logica,
		vw_ga_art_emp_por_ubic.cantidad_pedida,
		vw_ga_art_emp_por_ubic.cantidad_futura
	FROM vw_ga_articulos_almacen vw_ga_art_alm
		INNER JOIN vw_ga_articulos_empresa_ubicacion vw_ga_art_emp_por_ubic ON (vw_ga_art_alm.id_casilla = vw_ga_art_emp_por_ubic.id_casilla)
	WHERE vw_ga_art_alm.id_articulo = %s",
		valTpDato($idArticulo,"int"));
	$rsAlm = mysql_query($queryAlm);
	if (!$rsAlm) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$sigValor = 1;
	$arrayObj = NULL;
	$clase = "";
	while ($rowAlm = mysql_fetch_assoc($rsAlm)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$nombreSucursal = "";
		if ($rowAlm['id_empresa_padre_suc'] > 0)
			$nombreSucursal = $rowAlm['nombre_empresa_suc']." (".$rowAlm['sucursal'].")";
		
		$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trAlm:%s', 'class':'textoGris_11px %s', 'title':'trAlm:%s'}).adopt([
				new Element('td', {'align':'center'}).setHTML(\"<input id='cbxAlm' name='cbxAlm[]' type='checkbox' value='%s'/>\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s".
				"<input type='hidden' id='hddIdCasilla%s' name='hddIdCasilla%s' value='%s'/>".
				"<input type='hidden' id='hddIdArtAlm%s' name='hddIdArtAlm%s' value='%s'/>\")
			]);
			elemento.injectBefore('trItmPieAlm');",
			$sigValor, $clase, $sigValor,
			$sigValor,
			$rowAlm['nombre_empresa'],
			$nombreSucursal,
			$rowAlm['descripcion'],
			$rowAlm['ubicacion'],
			$rowAlm['cantidad_disponible_logica'],
			$sigValor, $sigValor, $rowAlm['id_casilla'],
			$sigValor, $sigValor, $rowAlm['id_articulo_almacen']));
		
		$arrayObj[] = $sigValor;
		$sigValor++;
	}
	
	$cadena = "";
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$cadena .= "|".$valor;
		}
	}
	$objResponse->assign("hddObjAlm","value",$cadena);
	
	
	$objResponse->script(sprintf("
	$('btnEtiqueta').onclick = function(){verVentana('reportes/ga_articulo_etiqueta_pdf.php?id=%s&ide=%s',400,300);}",
		$rowArticulo['id_articulo'],
		$_SESSION['idEmpresaUsuarioSysGts']));
	
	//$objResponse->script("xajax_listadoCostosProveedores('".$idArticulo."');");
	
	
	$objResponse->script("
	if ($('hddTipoVista').value == 'v')
		bloquearForm();
	");
	
	return $objResponse;
}

function guardarArticulo($valForm, $valFormArtSustituto, $valFormArtAlterno, $valFormListadoModComp) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS */
	$arrayVal = explode("|",$valFormArtSustituto['hddObjArtSus']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjArtSus[] = $valor;
	}
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS */
	$arrayVal = explode("|",$valFormArtAlterno['hddObjArtAlt']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS */
	$arrayVal = explode("|",$valFormListadoModComp['hddObjModComp']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjModComp[] = $valor;
	}
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $valForm['hddCantCodigo']; $cont++) {
		$codArticulo .= $valForm['txtCodigoArticulo'.$cont]."-";
	}
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	
	if ($valForm['hddIdArticulo'] > 0) {
		$updateSQL = sprintf("UPDATE ga_articulos SET
			codigo_articulo= %s,
			id_marca = %s,
			id_tipo_articulo = %s,
			codigo_articulo_prov = %s,
			descripcion = %s,
			id_subseccion = %s,
			foto = %s,
			id_tipo_unidad = %s,
			id_cuenta_contable = %s
		WHERE id_articulo = %s",
			valTpDato($codArticulo,"text"),
			valTpDato($valForm['lstMarcaArt'],"int"),
			valTpDato($valForm['lstTipoArticuloArt'],"int"),
			valTpDato($valForm['txtCodigoProveedor'],"text"),
			valTpDato($valForm['txtDescripcion'],"text"),
			valTpDato($valForm['lstSubSeccionArt'],"int"),
			valTpDato($valForm['hddUrlImagen'],"text"),
			valTpDato($valForm['lstTipoUnidad'],"text"),
			valTpDato($valForm['hddIdCuentaContable'],"int"),
			valTpDato($valForm['hddIdArticulo'],"text"));
		if (xvalidaAcceso($objResponse,"ga_articulo_list","editar")){
			mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idArticulo = $valForm['hddIdArticulo'];
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		 //$objResponse->alert($valForm['lstSubSeccionArt']);
		
		$insertSQL = sprintf("INSERT INTO ga_articulos (codigo_articulo, id_marca, id_tipo_articulo, codigo_articulo_prov, descripcion, id_subseccion, foto, id_tipo_unidad, id_cuenta_contable, id_empresa_creador) VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($codArticulo,"text"),
			valTpDato($valForm['lstMarcaArt'],"int"),
			valTpDato($valForm['lstTipoArticuloArt'],"int"),
			valTpDato($valForm['txtCodigoProveedor'],"text"),
			valTpDato($valForm['txtDescripcion'],"text"),
			valTpDato($valForm['lstSubSeccionArt'],"int"),
			valTpDato($valForm['hddUrlImagen'],"text"),
			valTpDato($valForm['lstTipoUnidad'],"int"),
			valTpDato($valForm['hddIdCuentaContable'],"int"),
			valTpDato($valForm['hddIdEmpresa'],"int"));
		if (xvalidaAcceso($objResponse,"ga_articulo_list","insertar")){
			@mysql_query("SET NAMES 'utf8'");
			$Result1 = mysql_query($insertSQL);
				//$objResponse->alert($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idArticulo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	/* VERIFICAR SI EXISTEN AUN LOS ARTICULOS SUSTITUTOS QUE ESTABAN EN LA BD */
	$queryCodigoSustituto = sprintf("SELECT * FROM ga_articulos_codigos_sustitutos WHERE id_articulo = %s", $idArticulo);
	$rsCodigoSustituto = mysql_query($queryCodigoSustituto);
	if (!$rsCodigoSustituto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	while ($rowCodigoSustituto = mysql_fetch_assoc($rsCodigoSustituto)) {
		$existCodigoSustituto = "NO";
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($rowCodigoSustituto['id_articulo_codigo_sustituto'] == $valFormArtSustituto['hddIdArtCodigoSustituto'.$valor]) {
					$existCodigoSustituto = "SI";
				}
			}
		}
		if ($existCodigoSustituto == "NO") {
			$deleteSQL = sprintf("DELETE FROM ga_articulos_codigos_sustitutos WHERE id_articulo_codigo_sustituto = %s",
				$rowCodigoSustituto['id_articulo_codigo_sustituto']);
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		}
	}
	
	/* VERIFICAR SI EXISTEN AUN LOS ARTICULOS ALTERNOS QUE ESTABAN EN LA BD */
	$queryCodigoAlterno = sprintf("SELECT * FROM ga_articulos_codigos_alternos WHERE id_articulo = %s", $idArticulo);
	$rsCodigoAlterno = mysql_query($queryCodigoAlterno);
	if (!$rsCodigoAlterno) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	while ($rowCodigoAlterno = mysql_fetch_assoc($rsCodigoAlterno)) {
		$existCodigoAlterno = "NO";
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($rowCodigoAlterno['id_articulo_codigo_alterno'] == $valFormArtAlterno['hddIdArtCodigoAlterno'.$valor]) {
					$existCodigoAlterno = "SI";
				}
			}
		}
		
		if ($existCodigoAlterno == "NO") {
			$deleteSQL = sprintf("DELETE FROM ga_articulos_codigos_alternos WHERE id_articulo_codigo_alterno = %s",
				$rowCodigoAlterno['id_articulo_codigo_alterno']);
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		}
	}
	
	/* VERIFICAR SI EXISTEN AUN LOS MODELOS COMPATIBLES QUE ESTABAN EN LA BD */
	$queryModeloCompatible = sprintf("SELECT * FROM vw_ga_articulos_modelos_compatibles WHERE id_articulo = %s", $idArticulo);
	$rsModeloCompatible = mysql_query($queryModeloCompatible);
	if (!$rsModeloCompatible) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	while ($rowModeloCompatible = mysql_fetch_assoc($rsModeloCompatible)) {
		$existModeloCompatible = "NO";
		if (isset($arrayObjModComp)) {
			foreach ($arrayObjModComp as $indice => $valor) {
				if ($rowModeloCompatible['id_articulo_modelo_compatible'] == $valFormListadoModComp['hddIdArtModComp'.$valor]) {
					$existModeloCompatible = "SI";
				}
			}
		}
		
		if ($existModeloCompatible == "NO") {
			$deleteSQL = sprintf("DELETE FROM ga_articulos_modelos_compatibles WHERE id_articulo_modelo_compatible = %s",
				$rowModeloCompatible['id_articulo_modelo_compatible']);
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		}
	}
	
	
	
	/* INSERTA LOS ARTICULOS SUSTITUTOS NUEVOS */
	if (isset($arrayObjArtSus)) {
		foreach ($arrayObjArtSus as $indice => $valor) {
			if ($valor != "") {
				if ($valFormArtSustituto['hddIdArtCodigoSustituto'.$valor] == "" || !isset($valFormArtSustituto['hddIdArtCodigoSustituto'.$valor])) {
					$insertSQL = sprintf("INSERT INTO ga_articulos_codigos_sustitutos (id_articulo, id_articulo_sustituto) VALUE (%s, %s);", 
						$idArticulo,
						$valFormArtSustituto['hddIdArtSus'.$valor]);
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				}
			}
		}
	}
	
	/* INSERTA LOS ARTICULOS ALTERNOS NUEVOS */
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($valor != "") {
				if ($valFormArtAlterno['hddIdArtCodigoAlterno'.$valor] == "" || !isset($valFormArtAlterno['hddIdArtCodigoAlterno'.$valor])) {
					$insertSQL = sprintf("INSERT INTO ga_articulos_codigos_alternos (id_articulo, id_articulo_alterno) VALUE (%s, %s);", 
						$idArticulo,
						$valFormArtAlterno['hddIdArtAlt'.$valor]);
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				}
			}
		}
	}
	
	/* INSERTA LOS MODELOS COMPATIBLES NUEVOS */
	if (isset($arrayObjModComp)) {
		foreach ($arrayObjModComp as $indice => $valor) {
			if ($valor != "") {
				if ($valFormListadoModComp['hddIdArtModComp'.$valor] == "" || !isset($valFormListadoModComp['hddIdArtModComp'.$valor])) {
					$insertSQL = sprintf("INSERT INTO ga_articulos_modelos_compatibles (id_articulo, id_unidad_basica) VALUE (%s, %s);",
						$idArticulo,
						$valFormListadoModComp['hddIdUnidadBasica'.$valor]);
					
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				}
			}
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("hddIdArticulo","value",$idArticulo);
	
	$objResponse->alert("Articulo Guardado con Exito");
	
	$objResponse->script("window.open('ga_articulo_list.php','_self')");
		
	return $objResponse;
}


function formArticuloSustituto($valForm) {
	$objResponse = new xajaxResponse();
	
	if ($valForm['hddIdArticulo'] != "")
		 $sqlCond = sprintf("WHERE id_articulo <> %s", $valForm['hddIdArticulo']);
		
	$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos %s", $sqlCond);
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$html = "<table border=\"0\" width=\"800\">";
	$html .= "<tr>";
		$html .= "<td>";
		$html .= "<form id=\"frmArtSus\" name=\"frmArtSus\" style=\"margin:0\">";
			$html .= "<table border=\"0\" width=\"100%\">";
			$html .= "<tr>";
				$html .= "<td align=\"right\" class=\"tituloCampo\" width=\"20%\"><span class=\"textoRojoNegrita\">*</span>Articulo Sustituto</td>";
				$html .= "<td width=\"80%\">";
					$html .= "<select id=\"lstArticuloSus\" name=\"lstArticuloSus\" onchange=\"xajax_verArticulo(this.value, 'tdArticuloAlterno','NO')\">";
						$html .= "<option value=\"-1\">[ Seleccione ]</option>";
					while ($rowArticulo = mysql_fetch_assoc($rsArticulo)) {
						$html .= "<option value=\"".$rowArticulo['id_articulo']."\">".htmlentities(elimCaracter($rowArticulo['codigo_articulo'],"-"))."</option>";
					}
					$html .= "</select>";
				$html .= "</td>";
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</form>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "<tr>";
		$html .= "<td id=\"tdArticuloAlterno\"></td>";
	$html .= "</tr>";
	$html .= "<tr>";
		$html .= "<td align=\"right\">";
			$html .= "<hr>";
			$html .= "<button type=\"button\" id=\"btnGuardar2\" name=\"btnGuardar2\" onclick=\"validarFormArtSustituto();\">
							<table align=\"center\" cellpadding=\"0\" cellspacing=\"0\">
								<tr><td>&nbsp;</td><td><img class=\"puntero\" src=\"../img/iconos/ico_save.png\"/></td><td>&nbsp;</td><td>Guardar</td></tr>
							</table>
						</button> ";
			$html .= "<button type=\"button\" id=\"btnCancelar2\" name=\"btnCancelar2\" onclick=\"$('divFlotante').style.display='none';\" class=\"close\">
							<table align=\"center\" cellpadding=\"0\" cellspacing=\"0\">
								<tr><td>&nbsp;</td><td><img class=\"puntero\" src=\"../img/iconos/ico_error.gif\"/></td><td>&nbsp;</td><td>Cancelar</td></tr>
							</table>
					  </button>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	$objResponse->assign("divFlotanteContenido","innerHTML",$html);
	
	$objResponse->script("
		$('divFlotanteContenido').style.display = '';
		$('tblModComp').style.display = 'none';
		$('tblAlmacen').style.display = 'none';
		$('tblCuentaContable').style.display = 'none';
	");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Articulo Sustituto");
	$objResponse->script("		
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
	");
	
	return $objResponse;
}


function formArticuloAlterno($valForm) {
	$objResponse = new xajaxResponse();
		
	if ($valForm['hddIdArticulo'] != "")
		 $sqlCond = sprintf("WHERE id_articulo <> %s", $valForm['hddIdArticulo']);
		
	$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos %s", $sqlCond);
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$html = "<table border=\"0\" width=\"800\">";
	$html .= "<tr>";
		$html .= "<td>";
		$html .= "<form id=\"frmArtAlt\" name=\"frmArtAlt\" style=\"margin:0\">";
			$html .= "<table border=\"0\" width=\"100%\">";
			$html .= "<tr>";
				$html .= "<td align=\"right\" class=\"tituloCampo\" width=\"20%\"><span class=\"textoRojoNegrita\">*</span>Articulo Alterno</td>";
				$html .= "<td width=\"80%\">";
					$html .= "<select id=\"lstArticuloAlt\" name=\"lstArticuloAlt\" onchange=\"xajax_verArticulo(this.value, 'tdArticuloAlterno','NO')\">";
						$html .= "<option value=\"-1\">[ Seleccione ]</option>";
					while ($rowArticulo = mysql_fetch_assoc($rsArticulo)) {
						$html .= "<option value=\"".$rowArticulo['id_articulo']."\">".htmlentities(elimCaracter($rowArticulo['codigo_articulo'],"-"))."</option>";
					}
					$html .= "</select>";
				$html .= "</td>";
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</form>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "<tr>";
		$html .= "<td id=\"tdArticuloAlterno\"></td>";
	$html .= "</tr>";
	$html .= "<tr>";
		$html .= "<td align=\"right\">";
			$html .= "<hr>";
			$html .= "<button type=\"button\" id=\"btnGuardar3\" name=\"btnGuardar3\" onclick=\"validarFormArtAlterno();\">
							<table align=\"center\" cellpadding=\"0\" cellspacing=\"0\">
								<tr><td>&nbsp;</td><td><img class=\"puntero\" src=\"../img/iconos/ico_save.png\"/></td><td>&nbsp;</td><td>Guardar</td></tr>
							</table>
						</button> ";
			$html .= "<button type=\"button\" id=\"btnCancelar3\" name=\"btnCancelar3\" onclick=\"$('divFlotante').style.display='none';\" class=\"close\">
							<table align=\"center\" cellpadding=\"0\" cellspacing=\"0\">
								<tr><td>&nbsp;</td><td><img class=\"puntero\" src=\"../img/iconos/ico_error.gif\"/></td><td>&nbsp;</td><td>Cancelar</td></tr>
							</table>
					  </button>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	$objResponse->assign("divFlotanteContenido","innerHTML",$html);
	
	$objResponse->script("
		$('divFlotanteContenido').style.display = '';
		$('tblModComp').style.display = 'none';
		$('tblAlmacen').style.display = 'none';
		$('tblCuentaContable').style.display = 'none';
	");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Articulo Alterno");
	$objResponse->script("		
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
	");
	
	return $objResponse;
}


function buscarModCompatibleEliminar($id, $valFormModComp = "", $tipo = "") {
	$objResponse = new xajaxResponse();
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS */
	$arrayVal = explode("|",$valFormModComp['hddObjModCompPreseleccionado']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjModCompPreseleccionado[] = $valor;
	}
	
	$nuevoArray = NULL;
	if (isset($arrayObjModCompPreseleccionado)){
		foreach ($arrayObjModCompPreseleccionado as $indide => $valor) {
			if ($valor != $id)
				$nuevoArray[] = $valor;
		}
	}
	
	
	$cadena = "";
	foreach ($nuevoArray as $indice => $valor) {
		if ($valor != "")
			$cadena .= $valor."|";
	}
	$objResponse->assign("hddObjModCompPreseleccionado","value",$cadena);
	
	$valBusq = sprintf("%s",
		$valFormModComp['txtTexto']);
	
	$objResponse->script("xajax_formModeloCompatible(xajax.getFormValues('frmModComp'),'".$valBusq."');");
	
	return $objResponse;
}


function buscarModeloCompatible($valFormModComp, $valFormListadoModComp = "", $tipo = "") {
	$objResponse = new xajaxResponse();
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS */
	$arrayVal = explode("|",$valFormListadoModComp['hddObjModComp']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjModComp[] = $valor;
	}
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS */
	$arrayVal = explode("|",$valFormModComp['hddObjModCompPreseleccionado']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjModCompPreseleccionado[] = $valor;
	}
	
	
	$arrayObjSeleccionados = NULL;
	if ($tipo == "inicio") {
		$objResponse->assign("txtTexto","value","");
		if (isset($arrayObjModComp)) {
			foreach ($arrayObjModComp as $indice => $valor) {
				$arrayObjSeleccionados[] = $valFormListadoModComp['hddIdUnidadBasica'.$valor];
			}
		}
	}
	
	if ($tipo != "inicio") {
		if (isset($arrayObjModCompPreseleccionado)) {
			foreach ($arrayObjModCompPreseleccionado as $indice => $valor) {
				$existe = false;
				if (isset($arrayObjSeleccionados)) {
					foreach ($arrayObjSeleccionados as $indice2 => $valor2) {
						if ($valor2 == $valor)
							$existe = true;
					}
				}
				
				if ($existe == false)
					$arrayObjSeleccionados[] = $valor;
			}
		}
		
		
		if (isset($valFormModComp['cbxItm'])) {
			foreach ($valFormModComp['cbxItm'] as $indice => $valor) {
				$existe = false;
				if (isset($arrayObjSeleccionados)) {
					foreach ($arrayObjSeleccionados as $indice2 => $valor2) {
						if ($valor2 == $valor)
							$existe = true;
					}
				}
				
				if ($existe == false)
					$arrayObjSeleccionados[] = $valor;
			}
		}
		
		$valBusq = sprintf("%s",
			$valFormModComp['txtTexto']);
	}
	
	$cadena = "";
	if (isset($arrayObjSeleccionados)) {
		foreach ($arrayObjSeleccionados as $indice => $valor) {
			if ($valor != "")
				$cadena .= $valor."|";
		}
	}
	$objResponse->assign("hddObjModCompPreseleccionado","value",$cadena);
	
	$objResponse->script("xajax_formModeloCompatible(xajax.getFormValues('frmModComp'),'".$valBusq."');");
	
	return $objResponse;
}


function formModeloCompatible($valForm = "", $valBusq = "") {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS */
	$arrayVal = explode("|",$valForm['hddObjModCompPreseleccionado']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("nom_uni_bas LIKE %s OR nom_modelo LIKE %s OR nom_version LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%","text"),
			valTpDato("%".$valCadBusq[0]."%","text"),
			valTpDato("%".$valCadBusq[0]."%","text"));
	}
	
	$queryUnidadBasica = sprintf("SELECT * FROM vw_iv_modelos %s", $sqlBusq);
	$rsUnidadBasica = mysql_query($queryUnidadBasica);
	if (!$rsUnidadBasica) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$html .= "<table border=\"0\" width=\"98%\">";
	$cont = 1;
	while ($rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica)) {
		if (fmod($cont, 3) == 1)
			$html .= "<tr>";
		
		$checked = "";
		$class = "class=\"divGris\"";
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($valor == $rowUnidadBasica['id_uni_bas']) {
					$checked = "checked=\"checked\"";
					$class = "class=\"divMsjInfo2\"";
				}
			}
		}
		
		$html .=  sprintf("<td width=\"%s\" onclick=\"cambiarStyle('%s','%s')\" valign=\"top\">",
			"25%",
			$cont,
			$rowUnidadBasica['id_uni_bas']);
			$html .= sprintf("<table border=\"0\" %s id=\"tblUnidadBas%s\" width=\"%s\">",
				$class,
				$cont,
				"100%");
			$html .= "<tr>";
				// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
				$imgFoto = $rowUnidadBasica['imagen_auto'];
				if(!file_exists($rowUnidadBasica['imagen_auto']))
					$imgFoto = "../".$_SESSION['logoEmpresaSysGts'];
				
				$html .= sprintf("<td rowspan=\"5\" style=\"background-color:#FFFFFF\">%s</td>", "<img src=\"".$imgFoto."\" width=\"80\"/>");
				$html .= sprintf("<td width=\"%s\">%s</td>", "100%", htmlentities($rowUnidadBasica['nom_uni_bas']));
				$html .= sprintf("<td rowspan=\"2\" valign=\"top\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" %s value=\"%s\"/></td>",
					$checked,
					$rowUnidadBasica['id_uni_bas']);
			$html .= "</tr>";
			$html .= "<tr>";
				$html .= sprintf("<td>%s</td>", htmlentities($rowUnidadBasica['nom_marca']));
			$html .= "</tr>";
			$html .= "<tr>";
				$html .= sprintf("<td colspan=\"2\">%s</td>", htmlentities($rowUnidadBasica['nom_modelo']));
			$html .= "</tr>";
			$html .= "<tr>";
				$html .= sprintf("<td colspan=\"2\">%s</td>", htmlentities($rowUnidadBasica['nom_version']));
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</td>";
		
		if (fmod($cont, 3) == 0)
			$html .= "</tr>";
	
		$cont++;
	}
	$html .= "</table>";
	
	$objResponse->assign("divListaModeloCompatible","innerHTML",$html);
	
	$objResponse->script("
	$('divFlotanteContenido').style.display = 'none';
	$('tblModComp').style.display = '';
	$('tblAlmacen').style.display = 'none';
	$('tblCuentaContable').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Modelo Compatible");
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
	}");
	
	return $objResponse;
}

function formAlmacen($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(cargaLstEmpresaFinal($valForm['hddIdEmpresa'], "onchange=\"xajax_cargaLst('almacenes', 'lstPadre', 'Act', this.value, 'null', 'null'); xajax_listadoArticulosAlmacen(xajax.getFormValues('frmAlmacen'));\""));
	
	$objResponse->loadCommands(cargaLst("almacenes", "lstPadre", "Act", $valForm['hddIdEmpresa'], "null", "null"));
	
	$objResponse->script("
	document.forms['frmAlmacen'].reset();

	$('divFlotanteContenido').style.display = 'none';
	$('tblModComp').style.display = 'none';
	$('tblAlmacen').style.display = '';
	$('tblCuentaContable').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Distribuir Articulo en Almacen");
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display='';
		centrarDiv($('divFlotante'));
	}");
	
	return $objResponse;
}

function insertarArticuloSustituto($valForm, $valFormArtSustituto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valFormArtSustituto['hddObjArtSus']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s", $valForm['lstArticuloSus']);
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$sigValor = $arrayObj[count($arrayObj)-1] + 1;
	
	$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trArtSus:%s', 'class':'".parOimpar($sigValor)."', 'title':'trArtSus:%s'}).adopt([
			new Element('td', {'align':'center'}).setHTML(\"<input id='cbxArtSus' name='cbxArtSus[]' type='checkbox' value='%s'/>\"),
			new Element('td', {'align':'left'}).setHTML(\"%s\"),
			new Element('td', {'align':'left'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s".
			"<input type='hidden' id='hddIdArtSus%s' name='hddIdArtSus%s' value='%s'/>".
			"<input type='hidden' id='hddIdArtCodigoSustituto%s' name='hddIdArtCodigoSustituto%s' value='%s'/>\"),
			new Element('td', {'align':'center'}).setHTML(\"<button type='button' id='btnArtSus:%s' title='Ver Articulo'><img src='../img/iconos/ico_view.png' width='16' height='16' align='absmiddle'/></button>\")
		]);
		elemento.injectBefore('trItmPieArtSus');
		
		$('btnArtSus:%s').onclick = function() {xajax_verArticulo('%s','divFlotanteContenido','YES');}", /*rebisar*/
		$sigValor, $sigValor,
		$sigValor,
		elimCaracter($rowArticulo['codigo_articulo'],"-"),
		str_replace("\n",", ",htmlentities($rowArticulo['descripcion'])),
		$rowArticulo['existencia'],
		$sigValor, $sigValor, $rowArticulo['id_articulo'],
		$sigValor, $sigValor, "",
		$sigValor, $sigValor,
		
		$sigValor, $rowArticulo['id_articulo']));
	
	$arrayObj[] = $sigValor;
	foreach($arrayObj as $indice => $valor) {
		$cadena = $valFormArtSustituto['hddObjArtSus']."-|".$valor;
	}
	$objResponse->assign("hddObjArtSus","value",$cadena);
	
	return $objResponse;
}

function insertarArticuloAlterno($valForm, $valFormArtAlterno) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valFormArtAlterno['hddObjArtAlt']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s", $valForm['lstArticuloAlt']);
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$sigValor = $arrayObj[count($arrayObj)-1] + 1;
		
	$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trArtAlt:%s', 'class':'".parOimpar($sigValor)."', 'title':'trArtAlt:%s'}).adopt([
			new Element('td', {'align':'center'}).setHTML(\"<input id='cbxArtAlt' name='cbxArtAlt[]' type='checkbox' value='%s'/>\"),
			new Element('td', {'align':'left'}).setHTML(\"%s\"),
			new Element('td', {'align':'left'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s".
			"<input type='hidden' id='hddIdArtAlt%s' name='hddIdArtAlt%s' value='%s'/>".
			"<input type='hidden' id='hddIdArtCodigoAlterno%s' name='hddIdArtCodigoAlterno%s' value='%s'/>\"),
			new Element('td', {'align':'center'}).setHTML(\"<button type='button' id='btnArtAlt:%s'><img src='../img/iconos/ico_view.png' width='16' height='16' align='absmiddle'/></button>\")
		]);
		elemento.injectBefore('trItmPieArtAlt');
		
		$('btnArtAlt:%s').onclick = function() {xajax_verArticulo('%s','divFlotanteContenido','YES');}",
		$sigValor, $sigValor,
		$sigValor,
		elimCaracter($rowArticulo['codigo_articulo'],"-"),
		str_replace("\n",", ",htmlentities($rowArticulo['descripcion'])),
		$rowArticulo['existencia'],
		$sigValor, $sigValor, $rowArticulo['id_articulo'],
		$sigValor, $sigValor, "",
		$sigValor, $sigValor,
		
		$sigValor, $rowArticulo['id_articulo']));
	
	$arrayObj[] = $sigValor;
	foreach($arrayObj as $indice => $valor) {
		$cadena = $valFormArtAlterno['hddObjArtAlt']."|".$valor;
	}
	$objResponse->assign("hddObjArtAlt","value",$cadena);
	
	return $objResponse;
}

function insertarModeloCompatible($valFormModComp, $valFormListadoModComp) {
	$objResponse = new xajaxResponse();
			
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valFormListadoModComp['hddObjModComp']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valFormModComp['hddObjModCompPreseleccionado']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObjModCompPreseleccionado[] = $valor;
	}
	
	$arrayObjNew = NULL;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice2 => $valor2) {
			$existe = false;
			if (isset($arrayObjModCompPreseleccionado)) {
				foreach ($arrayObjModCompPreseleccionado as $indice => $valor) {
					if ($valFormListadoModComp['hddIdUnidadBasica'.$valor2] == $valor) {
						$existe = true;
					}
				}
			}
			
			if ($existe == false) {
				$objResponse->script(sprintf("
					fila = document.getElementById('trModComp:%s');
								
					padre = fila.parentNode;
					padre.removeChild(fila);",
				$valor2));
			} else {
				$arrayObjNew[] = $valor2;
			}
		}
	}
	
	$arrayObj = NULL;
	$arrayObj = $arrayObjNew;
	
	$sigValor = $arrayObj[count($arrayObj)-1];
	if (isset($arrayObjModCompPreseleccionado)) {
		foreach ($arrayObjModCompPreseleccionado as $indice => $valor) {
			$queryModeloCompatible = sprintf("SELECT 
				uni_bas.id_uni_bas,
				uni_bas.nom_uni_bas,
				uni_bas.des_uni_bas,
				marca.id_marca,
				marca.nom_marca,
				marca.des_marca,
				modelo.id_modelo,
				modelo.nom_modelo,
				modelo.des_modelo,
				vers.id_version,
				vers.nom_version,
				vers.des_version
			FROM an_uni_bas uni_bas
				INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
				INNER JOIN an_modelo modelo ON (uni_bas.mod_uni_bas = modelo.id_modelo)
				INNER JOIN an_marca marca ON (uni_bas.mar_uni_bas = marca.id_marca)
			WHERE id_uni_bas = %s",
				valTpDato($valor, "int"));
			$rsModeloCompatible = mysql_query($queryModeloCompatible);
			if (!$rsModeloCompatible) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowModeloCompatible = mysql_fetch_assoc($rsModeloCompatible);
			
			$existe = false;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice2 => $valor2) {
					if ($valFormListadoModComp['hddIdUnidadBasica'.$valor2] == $valor) {
						$existe = true;
					}
				}
			}
			
			if ($existe == false) {
				$sigValor = $sigValor + 1;
				
				$objResponse->script(sprintf("
					var elemento = new Element('tr', {'id':'trModComp:%s', 'class':'".parOimpar($sigValor)."', 'title':'trModComp:%s'}).adopt([
						new Element('td', {'align':'center'}).setHTML(\"<input id='cbxModComp' name='cbxModComp[]' type='checkbox' value='%s'/>\"),
						new Element('td', {'align':'left'}).setHTML(\"%s\"),
						new Element('td', {'align':'left'}).setHTML(\"%s\"),
						new Element('td', {'align':'center'}).setHTML(\"%s\"),
						new Element('td', {'align':'center'}).setHTML(\"%s".
						"<input type='hidden' id='hddIdUnidadBasica%s' name='hddIdUnidadBasica%s' value='%s'/>".
						"<input type='hidden' id='hddIdArtModComp%s' name='hddIdArtModComp%s' value='%s'/>\")
					]);
					elemento.injectBefore('trItmPieModComp');",
					$sigValor, $sigValor,
					$sigValor,
					$rowModeloCompatible['nom_uni_bas'],
					$rowModeloCompatible['nom_marca'],
					$rowModeloCompatible['nom_modelo'],
					$rowModeloCompatible['nom_version'],
					$sigValor, $sigValor, $rowModeloCompatible['id_uni_bas'],
					$sigValor, $sigValor, "",
					
					$sigValor, $rowArticulo['id_articulo']));
				
				$arrayObj[] = $sigValor;
			}
		}
	}
	
	$cadena = "";
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if ($valor != "")
				$cadena .= "|".$valor;
		}
	}
	$objResponse->assign("hddObjModComp","value",$cadena);
	
	return $objResponse;
}

function insertarAlmacen($valForm, $valFormListadoAlm) {
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valFormListadoAlm['hddObjAlm']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$query = sprintf("SELECT * FROM vw_ga_casillas
	WHERE id_casilla = %s",
		$valForm['lstCasillaAct']);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$existe = false;
	foreach($arrayObj as $indice => $valor) {
		if ($valFormListadoAlm['hddIdCasilla'.$valor] == $row['id_casilla'])
			$existe = true;
	}
	
	$queryArtAlm = sprintf("SELECT * FROM vw_ga_articulos_almacen
	WHERE id_casilla = %s",
		$valForm['lstCasillaAct']);
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rsArtAlm);
	$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
	
	if ($totalRows > 0)
		$existe = true;
	
	if ($existe == false) {
		$sigValor = $arrayObj[count($arrayObj)-1] + 1;
		
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = $row['nombre_empresa_suc']." (".$row['sucursal'].")";
		
		$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trAlm:%s', 'class':'".parOimpar($sigValor)."', 'title':'trAlm:%s'}).adopt([
				new Element('td', {'align':'center'}).setHTML(\"<input id='cbxAlm' name='cbxAlm[]' type='checkbox' value='%s'/>\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s".
				"<input type='hidden' id='hddIdCasilla%s' name='hddIdCasilla%s' value='%s'/>".
				"<input type='hidden' id='hddIdArtAlm%s' name='hddIdArtAlm%s' value='%s'/>\")
			]);
			elemento.injectBefore('trItmPieAlm');",
			$sigValor, $sigValor,
			$sigValor,
			$row['nombre_empresa'],
			$nombreSucursal,
			$row['descripcion'],
			($row['descripcion_calle']."-".$row['descripcion_estante']."-".$row['descripcion_tramo']."-".$row['descripcion_casilla']),
			$row['existencia'],
			$sigValor, $sigValor, $row['id_casilla'],
			$sigValor, $sigValor, ""));
		
		$arrayObj[] = $sigValor;
		foreach($arrayObj as $indice => $valor) {
			$cadena = $valFormListadoAlm['hddObjAlm']."|".$valor;
		}
		$objResponse->assign("hddObjAlm","value",$cadena);
		
		$objResponse->script("$('divFlotante').style.display='none';");
	} else {
		$objResponse->alert("No puede agregar una ubicación ya ocupada");
	}

	return $objResponse;
}

function eliminarArticuloSustituto($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxArtSus'])) {
		foreach($valForm['cbxArtSus'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trArtSus:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}		
		$objResponse->script("xajax_eliminarArticuloSustituto(xajax.getFormValues('frmListadoArtSus'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valForm['hddObjArtSus']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$cadena = "";
	foreach($arrayObj as $indice => $valor) {
		if ($valForm['hddIdArtSus'.$valor] != "")
			$cadena .= "|".$valor;
	}
	$objResponse->assign("hddObjArtSus","value",$cadena);
			
	return $objResponse;
}

function eliminarArticuloAlterno($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxArtAlt'])) {
		foreach($valForm['cbxArtAlt'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trArtAlt:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}		
		$objResponse->script("xajax_eliminarArticuloAlterno(xajax.getFormValues('frmListadoArtAlt'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valForm['hddObjArtAlt']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$cadena = "";
	foreach($arrayObj as $indice => $valor) {
		if ($valForm['hddIdArtAlt'.$valor] != "")
			$cadena .= "|".$valor;
	}
	$objResponse->assign("hddObjArtAlt","value",$cadena);
			
	return $objResponse;
}

function eliminarModeloCompatible($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxModComp'])) {
		foreach($valForm['cbxModComp'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trModComp:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}		
		$objResponse->script("xajax_eliminarModeloCompatible(xajax.getFormValues('frmListadoModComp'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valForm['hddObjModComp']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$cadena = "";
	foreach($arrayObj as $indice => $valor) {
		if ($valForm['hddIdUnidadBasica'.$valor] != "")
			$cadena .= "|".$valor;
	}
	$objResponse->assign("hddObjModComp","value",$cadena);
			
	return $objResponse;
}

function eliminarAlmacen($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxAlm'])) {
		foreach($valForm['cbxAlm'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trAlm:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}		
		$objResponse->script("xajax_eliminarAlmacen(xajax.getFormValues('frmListadoAlm'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayVal = explode("|",$valForm['hddObjAlm']);
	foreach ($arrayVal as $indice => $valor) {
		if ($valor > 0)
			$arrayObj[] = $valor;
	}
	
	$cadena = "";
	foreach($arrayObj as $indice => $valor) {
		if ($valForm['hddIdCasilla'.$valor] != "")
			$cadena .= "|".$valor;
	}
	$objResponse->assign("hddObjAlm","value",$cadena);
			
	return $objResponse;
}

function listadoCostosProveedores($idArticulo) {
	$objResponse = new xajaxResponse();
			
	$html .= "<table width=\"100%\">";
	$html .= "<tr class=\"tituloColumna\">
            	<td width=\"50%\">".htmlentities("Proveedor")."</td>
                <td width=\"25%\">".htmlentities("Costo")."</td>
                <td width=\"25%\">".htmlentities("Fecha")."</td>
            </tr>";
	
	$queryCostos = sprintf("SELECT * FROM vw_ga_articulos_costos WHERE id_articulo = %s", $idArticulo);
	$rsCostos = mysql_query($queryCostos);
	if (!$rsCostos) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	while ($rowCostos = mysql_fetch_assoc($rsCostos)) {
		$html .= "<tr>";
			$html .= "<td align=\"left\">".$rowCostos['nombre']."</td>";
			$html .= "<td align=\"right\">".$rowCostos['precio']."</td>";
			$html .= "<td align=\"center\">".date(spanDateFormat, strtotime($rowCostos['fecha']))."</td>";
		$html .= "</tr>";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdListadoCostosProveedores","innerHTML",$html);
		
	return $objResponse;
}

function listadoArticulosAlmacen($valForm) {
	$objResponse = new xajaxResponse();
	
	$sqlBusq = sprintf(" WHERE id_empresa_reg = %s", valTpDato($valForm['lstEmpresa'], "int"));
	
	if ($valForm['lstAlmacenAct'] != "-1" && $valForm['lstAlmacenAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_almacen = %s", valTpDato($valForm['lstAlmacenAct'], "int"));
	}
	
	if ($valForm['lstCalleAct'] != "-1" && $valForm['lstCalleAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_calle = %s", valTpDato($valForm['lstCalleAct'], "int"));
	}
	
	if ($valForm['lstEstanteAct'] != "-1" && $valForm['lstEstanteAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_estante = %s", valTpDato($valForm['lstEstanteAct'], "int"));
	}
	
	if ($valForm['lstTramoAct'] != "-1" && $valForm['lstTramoAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tramo = %s", valTpDato($valForm['lstTramoAct'], "int"));
	}
	
	if ($valForm['lstCasillaAct'] != "-1" && $valForm['lstCasillaAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_casilla = %s", valTpDato($valForm['lstCasillaAct'], "int"));
	}
	
	$htmlTblIni = "<table border=\"1\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"25%\">"."Código"."</td>";
		$htmlTh .= "<td width=\"59%\">"."Descripción"."</td>";
		$htmlTh .= "<td width=\"16%\">"."Existencia"."</td>";
	$htmlTh .= "</tr>";
	
	$query = sprintf("SELECT * FROM vw_ga_articulos_almacen %s ORDER BY id_articulo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s", $row['id_articulo']);
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		if ($rowArticulo['id_articulo'] == $valForm['hddIdArticulo']) {
			$claseAnt = $clase;
			$clase = "trResaltar";
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>".elimCaracter($rowArticulo['codigo_articulo'],"-")."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($rowArticulo['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['existencia']."</td>";
		$htmlTb .= "</tr>";
		
		if ($rowArticulo['id_articulo'] == $valForm['hddIdArticulo'])
			$clase = $claseAnt;
	}
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divArticulosAlmacen","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	return $objResponse;
}

function listadoCuentaContable($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT * FROM co_cuentas_contables");
	
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

	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoCuentaContable", "20%", $pageNum, "numero_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Numero Cuenta");
		$htmlTh .= ordenarCampo("xajax_listadoCuentaContable", "33%", $pageNum, "descripcion_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion");
		$htmlTh .= ordenarCampo("xajax_listadoCuentaContable", "14%", $pageNum, "id_tipo_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Cuenta");
		$htmlTh .= ordenarCampo("xajax_listadoCuentaContable", "18%", $pageNum, "id_subtipo_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Sub Tipo Cuenta");
		$htmlTh .= ordenarCampo("xajax_listadoCuentaContable", "15%", $pageNum, "debe_haber", $campOrd, $tpOrd, $valBusq, $maxRows, "Debe/Haber");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCuentaContable('".$row['id_cuenta_contable']."');\" title=\"Seleccionar Cuenta Contable\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"left\">".$row['numero_cuenta']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['descripcion_cuenta']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_tipo_cuenta']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_subtipo_cuenta']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['debe_haber']."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSubSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSubSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoSubSecciones(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSubSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSubSecciones(%s,'%s','%s','%s',%s);\">%s</a>",
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
	$objResponse->assign("tdCuentaContable","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("
	$('divFlotanteContenido').style.display = 'none';
	$('tblModComp').style.display = 'none';
	$('tblAlmacen').style.display = 'none';
	$('tblCuentaContable').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Cuentas Contables");
	$objResponse->script("
	if ($('divFlotante').style.display == 'none') {
		$('divFlotante').style.display = '';
		centrarDiv($('divFlotante'));
	}");
	
	return $objResponse;
}

function asignarCuentaContable($idCuentaContable){
	$objResponse = new xajaxResponse();
	
	$queryCuentaContable = sprintf("SELECT * FROM co_cuentas_contables
	WHERE id_cuenta_contable = %s",
		valTpDato($idCuentaContable,"int"));
	$rsCuentaContable = mysql_query($queryCuentaContable);
	$rowCuentaContable = mysql_fetch_array($rsCuentaContable);
	if (!$rowCuentaContable) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$objResponse->assign("hddIdCuentaContable","value",$rowCuentaContable['id_cuenta_contable']);
	$objResponse->assign("txtCuentaContable","value",$rowCuentaContable['numero_cuenta']);
	
	$objResponse->script("$('divFlotante').style.display='none';");
	
	return $objResponse;
}

function verArticulo($idArticulo, $elementoDestino, $tpVista) {
	$objResponse = new xajaxResponse();
	
	$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s", $idArticulo);
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$html = "<table border=\"0\" width=\"900\">";
	$html .= "<tr>";
		$html .= "<td>";
			$html .= "<table border=\"0\" width=\"100%\">";
			$html .= "<tr>";
				$html .= "<td align=\"right\" class=\"tituloCampo\" width=\"15%\">Código:</td>";
				$html .= "<td width=\"20%\">".elimCaracter($rowArticulo['codigo_articulo'],"-")."</td>";
				$html .= "<td align=\"right\" class=\"tituloCampo\" width=\"25%\">Codigo del Articulo (Proveedor):</td>";
				$html .= "<td width=\"20%\">".$rowArticulo['codigo_articulo_prov']."</td>";
				$html .= "<td align=\"center\" rowspan=\"5\" width=\"20%\"><img src=\"".$rowArticulo['foto']."\" height=\"100\"/></td>";
			$html .= "</tr>";
			$html .= "<tr>";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Marca:</td>";
				$html .= "<td>".$rowArticulo['marca']."</td>";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Tipo de Articulo:</td>";
				$html .= "<td>".$rowArticulo['tipo_articulo']."</td>";
			$html .= "</tr>";
			$html .= "<tr>";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Descripcion:</td>";
				$html .= "<td colspan=\"3\">".htmlentities($rowArticulo['descripcion'])."</td>";
			$html .= "</tr>";
			$html .= "<tr>";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Sección:</td>";
				$html .= "<td>".htmlentities($rowArticulo['descripcion_seccion'])."</td>";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Sub-Sección:</td>";
				$html .= "<td>".htmlentities($rowArticulo['descripcion_subseccion'])."</td>";
			$html .= "</tr>";
			$html .= "<tr>";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Stock Máximo:</td>";
				$html .= "<td>".$rowArticulo['stock_maximo']."</td>";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Stock Minimo:</td>";
				$html .= "<td>".$rowArticulo['stock_minimo']."</td>";
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</td>";
	$html .= "</tr>";
if ($tpVista == "YES") {
	$html .= "<tr>";
		$html .= "<td align=\"right\">";
			 $html .= "<hr>";
			 $html .= "<input type=\"button\" onclick=\"$('divFlotante').style.display='none';\" value=\"Cancelar\">";
		$html .= "</td>";
	$html .= "</tr>";
}
	$html .= "</table>";
	
	$objResponse->assign($elementoDestino,"innerHTML",$html);
	
	$objResponse->script("
	$('divFlotanteContenido').style.display = '';
	$('tblModComp').style.display = 'none';
	$('tblAlmacen').style.display = 'none';
	$('tblCuentaContable').style.display = 'none';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Ver Articulo");
	$objResponse->script("		
	$('divFlotante').style.display='';
	centrarDiv($('divFlotante'));");
	
	return $objResponse;
}

function buscarEnArray($arrays, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x=0;
	foreach ($arrays as $indice=>$valor) {
		if($valor == $dato)
			return $x;
		
		$x++;
	}
	return null;
}

function cargaLst($tpLst, $idLstOrigen, $adjLst, $padreId, $nivReg, $selId){
	$objResponse = new xajaxResponse();
	
	switch ($tpLst) {
		case "almacenes" : 	$arraySelec = array("lstPadre","lstAlmacen","lstCalle","lstEstante","lstTramo","lstCasilla"); break;
	}
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	if (($posList+1) != count($arraySelec)-1)
		$onChange = "onchange=\"xajax_cargaLst('".$tpLst."', '".$arraySelec[$posList+1]."', '".$adjLst."', this.value, 'null', 'null');\"";
	else if (($posList+1) == count($arraySelec)-1)
		$onChange = "onchange=\"xajax_listadoArticulosAlmacen(xajax.getFormValues('frmAlmacen'));\"";
	
	$html = "<select id=\"".$arraySelec[$posList+1].$adjLst."\" name=\"".$arraySelec[$posList+1].$adjLst."\" ".$onChange.">";
	
	if ($padreId == '-1') {
		foreach ($arraySelec as $indice=>$valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\">";
					$html .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html .= "</select>";
				$objResponse->assign("td".$valor.$adjLst, 'innerHTML', $html);
			}
		}
		
		$objResponse->script("xajax_listadoArticulosAlmacen(xajax.getFormValues('frmAlmacen'));");
		
		return $objResponse;
	} else {
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
		foreach ($arraySelec as $indice=>$valor) {
			if ($indice > $posList) {
				$html2 = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\">";
					$html2 .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html2 .= "</select>";
				$objResponse->assign("td".$valor.$adjLst, 'innerHTML', $html2);
			}
		}
		
		switch ($posList) {
			case 0 : $query = sprintf("SELECT * FROM ga_almacenes WHERE estatus = 1 AND id_empresa = %s ORDER BY descripcion", valTpDato($padreId, "int"));
				$campoId = "id_almacen";
				$campoDesc = "descripcion"; break;
			case 1 : $query = sprintf("SELECT * FROM ga_calles WHERE id_almacen = %s AND descripcion_calle <> 'NA' ORDER BY descripcion_calle", valTpDato($padreId, "int"));
				$campoId = "id_calle";
				$campoDesc = "descripcion_calle";  break;
			case 2 : $query = sprintf("SELECT * FROM ga_estantes WHERE id_calle = %s AND descripcion_estante <> 'NA' ORDER BY descripcion_estante", valTpDato($padreId, "int"));
				$campoId = "id_estante";
				$campoDesc = "descripcion_estante";  break;
			case 3 : $query = sprintf("SELECT * FROM ga_tramos WHERE id_estante = %s AND descripcion_tramo <> 'NA' ORDER BY descripcion_tramo", valTpDato($padreId, "int"));
				$campoId = "id_tramo";
				$campoDesc = "descripcion_tramo";  break;
			case 4 : $query = sprintf("SELECT * FROM ga_casillas WHERE id_tramo = %s AND descripcion_casilla <> 'NA' ORDER BY descripcion_casilla", valTpDato($padreId, "int"));
				$campoId = "id_casilla";
				$campoDesc = "descripcion_casilla";  break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		while ($row = mysql_fetch_array($rs)) {
			$seleccion = ($selId == $row[$campoId]) ? "selected='selected'" : "";
			
			$html .= "<option value=\"".$row[$campoId]."\" ".$seleccion.">".htmlentities($row[$campoDesc])."</option>";
		}
	}
	$html .= "</select>";
	
	$objResponse->assign("td".$arraySelec[$posList+1].$adjLst, 'innerHTML', $html);
	
	$objResponse->script("xajax_listadoArticulosAlmacen(xajax.getFormValues('frmAlmacen'));");
	
	return $objResponse;
}

function cargaLstMarca($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_marcas ORDER BY marca");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstMarcaArt\" name=\"lstMarcaArt\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_marca'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_marca']."\" ".$seleccion.">".htmlentities($row['marca'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMarcaArt","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoUnidad($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_tipos_unidad ORDER BY unidad");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoUnidad\" name=\"lstTipoUnidad\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_tipo_unidad'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_tipo_unidad']."\" ".$seleccion.">".htmlentities($row['unidad'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoUnidad","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoArticulo($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoArticuloArt\" name=\"lstTipoArticuloArt\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_tipo_articulo'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_tipo_articulo']."\" ".$seleccion.">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticuloArt","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstSeccion($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_secciones WHERE estatu = 1 ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstSeccionArt\" name=\"lstSeccionArt\" onchange=\"xajax_cargaLstSubSeccion(this.value);\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_seccion'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_seccion']."\" ".$seleccion.">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSeccionArt","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstSubSeccion($idSeccion, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_subsecciones WHERE estatu = 1 AND id_seccion = %s", valTpDato($idSeccion,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstSubSeccionArt\" name=\"lstSubSeccionArt\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_subseccion'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_subseccion']."\" ".$seleccion.">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSubSeccionArt","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstArticulo ($idMarca, $idTipoArticulo, $idTipoUnidad, $idSeccion, $idSubSeccion) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_marcas ORDER BY marca");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstMarcaArt\" name=\"lstMarcaArt\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = ($idMarca == $row['id_marca']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_marca']."\" ".$seleccion.">".htmlentities($row['marca'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMarcaArt","innerHTML",$html);
	
	
	$query = sprintf("SELECT * FROM ga_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoArticuloArt\" name=\"lstTipoArticuloArt\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = ($idTipoArticulo == $row['id_tipo_articulo']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_tipo_articulo']."\" ".$seleccion.">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticuloArt","innerHTML",$html);
	
	
	$query = sprintf("SELECT * FROM ga_tipos_unidad ORDER BY unidad");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoUnidad\" name=\"lstTipoUnidad\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = ($idTipoUnidad == $row['id_tipo_unidad']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_tipo_unidad']."\" ".$seleccion.">".htmlentities($row['unidad'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoUnidad","innerHTML",$html);
	
	
	$query = sprintf("SELECT * FROM ga_secciones ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstSeccionArt\" name=\"lstSeccionArt\" onchange=\"xajax_cargaLstSubSeccion(this.value);\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = ($idSeccion == $row['id_seccion']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_seccion']."\" ".$seleccion.">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSeccionArt","innerHTML",$html);
	
	
	$query = sprintf("SELECT * FROM ga_subsecciones WHERE id_seccion = %s", valTpDato($idSeccion,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstSubSeccionArt\" name=\"lstSubSeccionArt\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = ($idSubSeccion == $row['id_subseccion']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_subseccion']."\" ".$seleccion.">".htmlentities($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSubSeccionArt","innerHTML",$html);
	
	
	$objResponse->script("
	if ($('hddTipoVista').value == 'v')
		bloquearForm();");
	
	return $objResponse;
}

function eliminar_articulo($id){
	$objResponse = new xajaxResponse();

	global $conex;
	
	if (xvalidaAcceso($objResponse,"ga_articulo",eliminar)){
		$ret = mysql_query("DELETE FROM ga_articulos WHERE id_articulo = ".$id.";", $conex);
		
		if ($ret) {
			$objResponse->alert("Eliminado satisfactoriamente");
			$objResponse->script('window.location=ga_articulo_list.php');
		} else {
			$objResponse->alert("No se puede eliminar, es posible que existan registro relacionados");
		}
	} else {
		return $objResponse;
	}
	
	return $objResponse;	
}

$xajax->register(XAJAX_FUNCTION,"cargarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminar_articulo");
$xajax->register(XAJAX_FUNCTION,"guardarArticulo");

$xajax->register(XAJAX_FUNCTION,"formArticuloSustituto");
$xajax->register(XAJAX_FUNCTION,"formArticuloAlterno");
$xajax->register(XAJAX_FUNCTION,"buscarModCompatibleEliminar");
$xajax->register(XAJAX_FUNCTION,"buscarModeloCompatible");
$xajax->register(XAJAX_FUNCTION,"formModeloCompatible");
$xajax->register(XAJAX_FUNCTION,"formAlmacen");

$xajax->register(XAJAX_FUNCTION,"insertarArticuloSustituto");
$xajax->register(XAJAX_FUNCTION,"insertarArticuloAlterno");
$xajax->register(XAJAX_FUNCTION,"insertarModeloCompatible");
$xajax->register(XAJAX_FUNCTION,"insertarAlmacen");
$xajax->register(XAJAX_FUNCTION,"eliminarArticuloSustituto");
$xajax->register(XAJAX_FUNCTION,"eliminarArticuloAlterno");
$xajax->register(XAJAX_FUNCTION,"eliminarModeloCompatible");
$xajax->register(XAJAX_FUNCTION,"eliminarAlmacen");

$xajax->register(XAJAX_FUNCTION,"listadoCostosProveedores");
$xajax->register(XAJAX_FUNCTION,"listadoArticulosAlmacen");
$xajax->register(XAJAX_FUNCTION,"listadoCuentaContable");
$xajax->register(XAJAX_FUNCTION,"asignarCuentaContable");

$xajax->register(XAJAX_FUNCTION,"verArticulo");

$xajax->register(XAJAX_FUNCTION,"cargaLst");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarca");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoUnidad");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArticulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstSeccion");
$xajax->register(XAJAX_FUNCTION,"cargaLstSubSeccion");
$xajax->register(XAJAX_FUNCTION,"cargaLstArticulo");
   
function parOimpar($numero){ 
	$resto = $numero%2; 
	if (($resto==0) && ($numero!=0)) { 
		 $class = "trResaltar5";
	} else { 
		 $class = "trResaltar4";
	}  
	return $class;
}
?>