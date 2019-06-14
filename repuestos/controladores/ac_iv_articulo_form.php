<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
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
	$objResponse->assign("txtPorcArancelFamilia","value",utf8_encode($rowArancelFamilia['porcentaje_grupo']));
	
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

function buscarArticulo($frmBuscarArticulo, $frmArticulo) {
	$objResponse = new xajaxResponse();
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $frmBuscarArticulo['hddCantCodigoBuscar']; $cont++) {
		$codArticulo .= $frmBuscarArticulo['txtCodigoArticuloBuscar'.$cont].";";
	}
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$codArticulo,
		$frmBuscarArticulo['txtCriterioBuscarArticulo'],
		$frmArticulo['hddIdArticulo'],
		$frmBuscarArticulo['hddModoArticulo']);
	
	$objResponse->loadCommands(listaArticulo(0, "id_articulo", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarImpuesto($frmBuscarImpuesto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarImpuesto['txtCriterioBuscarImpuesto']);
	
	$objResponse->loadCommands(listaImpuesto(0, "idIva", "ASC", $valBusq));
		
	return $objResponse;
}

function cargaLstArticulo ($idMarca = "", $idTipoArticulo = "", $idTipoUnidad = "", $idSeccion = "", $idSubSeccion = "", $idPrecioPredet = "", $hddTipoVista = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$onChange = ($hddTipoVista != "") ? "onchange=\"selectedOption(this.id,'".$idMarca."');\"" : "class=\"inputHabilitado\"";
	
	$query = sprintf("SELECT * FROM iv_marcas ORDER BY marca");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMarcaArt\" name=\"lstMarcaArt\" ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($idMarca == $row['id_marca']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_marca']."\">".utf8_encode($row['marca'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMarcaArt","innerHTML",$html);
	
	
	$onChange = ($hddTipoVista != "") ? "onchange=\"selectedOption(this.id,'".$idTipoArticulo."');\"" : "class=\"inputHabilitado\"";
	
	$query = sprintf("SELECT * FROM iv_tipos_articulos ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoArticuloArt\" name=\"lstTipoArticuloArt\" ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($idTipoArticulo == $row['id_tipo_articulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_articulo']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticuloArt","innerHTML",$html);
	
	
	$onChange = ($hddTipoVista != "") ? "onchange=\"selectedOption(this.id,'".$idTipoUnidad."');\"" : "class=\"inputHabilitado\"";
	
	$query = sprintf("SELECT * FROM iv_tipos_unidad ORDER BY unidad");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoUnidad\" name=\"lstTipoUnidad\" ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($idTipoUnidad == $row['id_tipo_unidad']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_unidad']."\">".utf8_encode($row['unidad'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoUnidad","innerHTML",$html);
	
	
	$onChange = ($hddTipoVista != "") ? "onchange=\"selectedOption(this.id,'".$idSeccion."');\"" : "class=\"inputHabilitado\"";
	
	$query = sprintf("SELECT * FROM iv_secciones ORDER BY descripcion");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstSeccionArt\" name=\"lstSeccionArt\" ".$onChange." onchange=\"xajax_cargaLstSubSeccion(this.value);\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($idSeccion == $row['id_seccion']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_seccion']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSeccionArt","innerHTML",$html);
	
	
	$onChange = ($hddTipoVista != "") ? "onchange=\"selectedOption(this.id,'".$idSubSeccion."');\"" : "class=\"inputHabilitado\"";
	
	$query = sprintf("SELECT * FROM iv_subsecciones WHERE id_seccion = %s ORDER BY descripcion", valTpDato($idSeccion, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstSubSeccionArt\" name=\"lstSubSeccionArt\" ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($idSubSeccion == $row['id_subseccion']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_subseccion']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSubSeccionArt","innerHTML",$html);
	
	
	$onChange = ($bloquearObj == true) ? "onchange=\"byId('aDesbloquearPrecio').click(); selectedOption(this.id,'".$idPrecioPredet."');\"" : "";
	
	$queryPrecio = sprintf("SELECT * FROM pg_precios precio WHERE precio.porcentaje <> 0 AND precio.estatus IN (1) ORDER BY precio.id_precio ASC;");
	$rsPrecio = mysql_query($queryPrecio);
	if (!$rsPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstPrecioPredet\" name=\"lstPrecioPredet\" class=\"inputHabilitado\" ".$onChange." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowPrecio = mysql_fetch_assoc($rsPrecio)) {
		$selected = ($idPrecioPredet == $rowPrecio['id_precio']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$rowPrecio['id_precio']."\">".utf8_encode($rowPrecio['descripcion_precio'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstPrecioPredet","innerHTML",$html);
	
	
	$objResponse->script("
	if (byId('hddTipoVista').value == 'v')
		bloquearForm();");
	
	return $objResponse;
}

function cargaLstSubSeccion($idSeccion, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM iv_subsecciones WHERE id_seccion = %s ORDER BY descripcion", valTpDato($idSeccion, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstSubSeccionArt\" name=\"lstSubSeccionArt\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_subseccion']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_subseccion']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSubSeccionArt","innerHTML",$html);
	
	return $objResponse;
}

function cargarArticulo($idArticulo, $idEmpresa, $hddTipoVista = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('aNuevoImpuesto').style.display = 'none';
	byId('btnEliminarImpuesto').style.display = 'none';");
	
	if ($idArticulo > 0) {
		if ($hddTipoVista == "") {
			$objResponse->script("
			byId('txtDescripcion').className = 'inputHabilitado';
			byId('txtCodigoProveedor').className = 'inputHabilitado';
			byId('lstModoCompra').className = 'inputHabilitado';");
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$queryArticulo = sprintf("SELECT art.*,
				(SELECT subseccion.id_seccion FROM iv_subsecciones subseccion
				WHERE subseccion.id_subseccion = art.id_subseccion) AS id_seccion,
				
				(SELECT COUNT(art_cod_sust.id_articulo_sustituido) FROM iv_articulos_codigos_sustitutos art_cod_sust
				WHERE art_cod_sust.id_articulo_sustituido = art.id_articulo) AS cant_sustituido,
				
				(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
				WHERE kardex.id_articulo = art.id_articulo
					AND kardex.tipo_movimiento IN (1)
				ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_compra,
				
				(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
				WHERE kardex.id_articulo = art.id_articulo
					AND kardex.tipo_movimiento IN (3)
				ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_venta,
				
				vw_iv_art_emp.existencia,
				vw_iv_art_emp.cantidad_reservada,
				vw_iv_art_emp.cantidad_disponible_fisica,
				vw_iv_art_emp.cantidad_espera,
				vw_iv_art_emp.cantidad_bloqueada,
				vw_iv_art_emp.cantidad_disponible_logica,
				vw_iv_art_emp.cantidad_pedida,
				vw_iv_art_emp.cantidad_futura
			FROM vw_iv_articulos_empresa vw_iv_art_emp
				INNER JOIN iv_articulos art ON (vw_iv_art_emp.id_articulo = art.id_articulo)
			WHERE vw_iv_art_emp.id_empresa = %s
				AND art.id_articulo = %s",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
		} else {
			$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
			
			$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos WHERE id_articulo = %s;",
				valTpDato($idArticulo, "int"));
		}
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$objResponse->loadCommands(asignarEmpresaUsuario($idEmpresa, "Empresa", "ListaEmpresa"));
		
		$objResponse->script("byId('tdMsjSustituido').style.display = '".(($rowArticulo['cant_sustituido'] > 0) ? "" : "none")."'");
		
		if ($rowArticulo['existencia'] || $rowArticulo['cantidad_reservada'] || $rowArticulo['cantidad_disponible_fisica'] || $rowArticulo['cantidad_espera']
		|| $rowArticulo['cantidad_disponible_logica'] || $rowArticulo['cantidad_pedida'] || $rowArticulo['cantidad_futura']) {
			$objResponse->loadCommands(objetoCodigoDinamico("tdCodigoArt", $idEmpresa, $rowArticulo['id_empresa_creador'], $rowArticulo['codigo_articulo'], "", true));
			
			$objResponse->script("
			byId('txtDescripcion').readOnly = true;
			byId('txtDescripcion').className = 'inputInicial';
			byId('aDesbloquearDescripcion').style.display = '';");
		} else {
			$objResponse->loadCommands(objetoCodigoDinamico("tdCodigoArt", $idEmpresa, $rowArticulo['id_empresa_creador'], $rowArticulo['codigo_articulo'], "", $bloquearObj));
			
			$objResponse->script("
			byId('aDesbloquearDescripcion').style.display = 'none';");
		}
		
		$objResponse->assign("hddIdArticulo","value",$idArticulo);
		$objResponse->script("byId('imgCodigoBarra').src = '../clases/barcode128.php?type=B&bw=2&pc=1&codigo=".$idArticulo."'");
		$objResponse->assign("txtCodigoProveedor","value",utf8_encode($rowArticulo['codigo_articulo_prov']));
		$objResponse->assign("txtDescripcion","innerHTML",utf8_encode($rowArticulo['descripcion']));
		$objResponse->call("selectedOption","lstModoCompra",$rowArticulo['id_modo_compra']);
		$objResponse->loadCommands(asignarArancelFamilia($rowArticulo['id_arancel_familia'], "false"));
		$objResponse->script("byId('aDesbloquearIva').style.display = '';");
		$objResponse->script("
		byId('lstIvaArt').onchange = function(){
			selectedOption(this.id,'".$rowArticulo['posee_iva']."');
		}");
		$objResponse->call("selectedOption","lstIvaArt",$rowArticulo['posee_iva']);
		$objResponse->script("byId('aDesbloquearComision').style.display = '';");
		$objResponse->script("
		byId('lstGeneraComision').onchange = function(){
			byId('aDesbloquearComision').click();
			selectedOption(this.id,'".$rowArticulo['genera_comision']."');
		}");
		$objResponse->call("selectedOption","lstGeneraComision",$rowArticulo['genera_comision']);
		
		$objResponse->assign("spnStockMaximo","innerHTML",$rowArticulo['stock_maximo']);
		$objResponse->assign("spnStockMinimo","innerHTML",$rowArticulo['stock_minimo']);
		
		$objResponse->assign("spnSaldo","innerHTML",number_format($rowArticulo['existencia'], 2, ".", ","));
		$objResponse->assign("spnCantReservada","innerHTML",number_format($rowArticulo['cantidad_reservada'], 2, ".", ","));
		$objResponse->assign("spnCantEspera","innerHTML",number_format($rowArticulo['cantidad_espera'], 2, ".", ","));
		$objResponse->assign("spnCantBloqueada","innerHTML",number_format($rowArticulo['cantidad_bloqueada'], 2, ".", ","));
		$objResponse->assign("spnCantDisponible","innerHTML",number_format($rowArticulo['cantidad_disponible_logica'], 2, ".", ","));
		$objResponse->assign("spnCantPedida","innerHTML",number_format($rowArticulo['cantidad_pedida'], 2, ".", ","));
		$objResponse->assign("spnCantFutura","innerHTML",number_format($rowArticulo['cantidad_futura'], 2, ".", ","));
		
		$objResponse->assign("spnFechaUltCompraArt","innerHTML",(($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx"));
		$objResponse->assign("spnFechaUltVentaArt","innerHTML",(($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx"));
		
		$objResponse->assign("txtLargo","value",number_format($rowArticulo['largo_volumen'], 2, ".", ","));
		$objResponse->assign("txtAncho","value",number_format($rowArticulo['ancho_volumen'], 2, ".", ","));
		$objResponse->assign("txtAlto","value",number_format($rowArticulo['alto_volumen'], 2, ".", ","));
		$objResponse->assign("txtPeso","value",number_format($rowArticulo['peso_articulo'], 2, ".", ","));
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($rowArticulo['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $rowArticulo['foto'];
		
		switch($rowArticulo['clasificacion']) {
			case 'A' : $imgClasif = "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"Clasificación A\"/>"; break;
			case 'B' : $imgClasif = "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"Clasificación B\"/>"; break;
			case 'C' : $imgClasif = "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"Clasificación C\"/>"; break;
			case 'D' : $imgClasif = "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"Clasificación D\"/>"; break;
			case 'E' : $imgClasif = "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"Clasificación E\"/>"; break;
			case 'F' : $imgClasif = "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"Clasificación F\"/>"; break;
		}
		
		$objResponse->assign("imgArticulo","src",$imgFoto);
		$objResponse->assign("hddUrlImagen","value",$rowArticulo['foto']);
		$objResponse->assign("spnFechaRegistro","innerHTML",date(spanDateFormat." H:i:s",strtotime($rowArticulo['fecha_registro'])));
		$objResponse->assign("divClasificacion","innerHTML",$imgClasif);
		$objResponse->assign("hddClasificacion","value",$rowArticulo['clasificacion']);
		
		$objResponse->loadCommands(cargaLstArticulo($rowArticulo['id_marca'], $rowArticulo['id_tipo_articulo'], $rowArticulo['id_tipo_unidad'], $rowArticulo['id_seccion'], $rowArticulo['id_subseccion'], $rowArticulo['id_precio_predeterminado'], $hddTipoVista, $bloquearObj));
		
		// BUSCA LOS LOTES ACTIVOS
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.id_articulo = %s
		AND vw_iv_art_almacen_costo.id_empresa = %s
		AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
		AND vw_iv_art_almacen_costo.estatus_almacen_venta = 1", 
			valTpDato($idArticulo, "int"),
			valTpDato($idEmpresa, "int"));
			
		if (!in_array($ResultConfig12, array(1,2))) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_iv_art_almacen_costo.cantidad_disponible_logica > 0");
		}
		$limitArtCosto = (in_array($ResultConfig12, array(1,2))) ? 1 : 4;
		
		$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo %s
		ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;", $sqlBusq);
		$rsArtCosto = mysql_query($queryArtCosto);
		if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
		$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
		while ($rowArtCosto = mysql_fetch_assoc($rsArtCosto)) {
			$htmlTb .= "<tr align=\"center\" class=\"tituloColumna\">";
				$htmlTb .= "<td colspan=\"4\">"."LOTE: ".utf8_encode($rowArtCosto['id_articulo_costo']).", Unid. Disponible: ".utf8_encode($rowArtCosto['cantidad_disponible_logica']).""."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr align=\"center\" class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"55%\">Descripción</td>";
				$htmlTb .= "<td width=\"15%\">Precio</td>";
				$htmlTb .= "<td width=\"15%\">Impuesto</td>";
				$htmlTb .= "<td width=\"15%\">Total</td>";
			$htmlTb .= "</tr>";
			
			// BUSCA LOS PRECIOS DEL LOTE
			$queryArtPrecio = sprintf("SELECT
				precio.descripcion_precio,
				art_precio.precio AS precio_unitario,
				
				(SELECT iva.observacion
				FROM iv_articulos_impuesto art_impsto
					INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
				WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1
					AND art_impsto.id_articulo = art_precio.id_articulo
				LIMIT 1) AS descripcion_impuesto,
				
				(SELECT SUM(iva.iva)
				FROM iv_articulos_impuesto art_impsto
					INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
				WHERE iva.tipo IN (6,9,2)
					AND art_impsto.id_articulo = art_precio.id_articulo) AS porcentaje_impuesto,
				
				(art_precio.precio * (SELECT SUM(iva.iva)
									FROM iv_articulos_impuesto art_impsto
										INNER JOIN pg_iva iva ON (art_impsto.id_impuesto = iva.idIva)
									WHERE iva.tipo IN (6,9,2)
										AND art_impsto.id_articulo = art_precio.id_articulo) / 100) AS monto_impuesto,
				
				moneda.abreviacion AS abreviacion_moneda
			FROM pg_precios precio
  				INNER JOIN iv_articulos_precios art_precio ON (precio.id_precio = art_precio.id_precio)
				INNER JOIN pg_monedas moneda ON (art_precio.id_moneda = moneda.idmoneda)
			WHERE art_precio.id_articulo_costo = %s
			ORDER BY precio.porcentaje DESC;",
				valTpDato($rowArtCosto['id_articulo_costo'], "int"));
			$rsArtPrecio = mysql_query($queryArtPrecio);
			if (!$rsArtPrecio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while ($rowArtPrecio = mysql_fetch_assoc($rsArtPrecio)) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
					$htmlTb .= "<td>".$rowArtPrecio['descripcion_precio']."</td>";
					$htmlTb .= "<td align=\"right\">".$rowArtPrecio['abreviacion_moneda'].number_format($rowArtPrecio['precio_unitario'], 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".$rowArtPrecio['abreviacion_moneda'].number_format($rowArtPrecio['monto_impuesto'], 2, ".", ",")."</td>";
					$htmlTb .= "<td align=\"right\">".$rowArtPrecio['abreviacion_moneda'].number_format($rowArtPrecio['precio_unitario'] + $rowArtPrecio['monto_impuesto'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
			}
		}
		$htmlTblFin .= "</table>";
		
		// INSERTA LOS PRECIOS DEL ARTICULO
		/*$queryArtPrecio = sprintf("SELECT * FROM vw_iv_articulos_precios
		WHERE id_articulo = %s
			AND estatus = 1
		ORDER BY porcentaje DESC;",
			valTpDato($idArticulo, "int"));*/
		$objResponse->assign("tdListaPrecio","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
		
		// INSERTA LOS ARTICULOS SUSTITUTOS
		$query = sprintf("SELECT * FROM iv_articulos_codigos_sustitutos art_sustituto
		WHERE id_articulo = %s
		ORDER BY id_articulo_codigo_sustituto ASC;",
			valTpDato($idArticulo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemArticuloSust($contFila2, $row['id_articulo_codigo_sustituto']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila2 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj2[] = $contFila2;
			}
		}
		
		// INSERTA LOS ARTICULOS ALTERNOS
		$query = sprintf("SELECT * FROM iv_articulos_codigos_alternos art_alterno
		WHERE id_articulo = %s
		ORDER BY id_articulo_codigo_alterno ASC;",
			valTpDato($idArticulo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemArticuloAlt($contFila3, $row['id_articulo_codigo_alterno']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila3 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj3[] = $contFila3;
			}
		}
		
		// INSERTA LOS IMPUESTOS DEL ARTICULO
		$query = sprintf("SELECT * FROM iv_articulos_impuesto art_impuesto
		WHERE id_articulo = %s
		ORDER BY id_articulo_impuesto ASC;",
			valTpDato($idArticulo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemImpuesto($contFila4, $row['id_articulo_impuesto'], $row['id_impuesto']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila4 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj4[] = $contFila4;
			}
		}
	
		$objResponse->script("
		xajax_eliminarArticuloSustituto(xajax.getFormValues('frmListaArtSust'));
		xajax_eliminarArticuloAlterno(xajax.getFormValues('frmListaArtAlt'));");
		
		if ($hddTipoVista != "") {
			$objResponse->script("
			byId('lstModoCompra').onchange = function(){
				selectedOption(this.id,'".$rowArticulo['id_modo_compra']."');
			}");
		}
	} else {
		if (!xvalidaAcceso($objResponse,"iv_articulo_list","insertar")) { $objResponse->script("byId('btnCancelar').click();"); return $objResponse; }
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
		$objResponse->loadCommands(objetoCodigoDinamico("tdCodigoArt", $_SESSION['idEmpresaUsuarioSysGts']));
		
		$objResponse->script("
		byId('lstIvaArt').onchange = function(){
			selectedOption(this.id,'1');
		}");
		$objResponse->call("selectedOption","lstIvaArt",1);
		
		$objResponse->loadCommands(cargaLstArticulo("","","","","","1","",true));
		$objResponse->script("
		byId('txtCodigoProveedor').className = 'inputHabilitado';
		byId('txtDescripcion').className = 'inputHabilitado';
		byId('lstModoCompra').className = 'inputHabilitado';
		byId('lstGeneraComision').className = 'inputHabilitado';
		
		byId('hddClasificacion').value = 'F';
		byId('aDesbloquearDescripcion').style.display = 'none';
		byId('aDesbloquearIva').style.display = '';
		byId('aDesbloquearComision').style.display = '';
		byId('lstGeneraComision').onchange = function(){ xajax_formValidarPermisoEdicion('iv_articulo_form_genera_comision'); selectedOption(this.id,'1'); };
		selectedOption('lstGeneraComision','1');");
		
		// INSERTA LOS IMPUESTOS DEL ARTICULO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$query = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1,6) AND iva.estado = 1 AND iva.activo = 1;");
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemImpuesto($contFila4, "", $row['idIva']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila4 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj4[] = $contFila4;
			}
		}
	}
	
	$objResponse->script("
	xajax_eliminarImpuestoArticulo(xajax.getFormValues('frmArticulo'));
	
	if (byId('hddTipoVista').value == 'v') {
		bloquearForm();
	} else {
		byId('btnGuardar').style.display = '';
	}");
	
	return $objResponse;
}

function eliminarArticuloAlterno($frmListaArtAlt) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaArtAlt['cbxItmArtAlt'])) {
		foreach ($frmListaArtAlt['cbxItmArtAlt'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmArtAlt:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}		
		$objResponse->script("xajax_eliminarArticuloAlterno(xajax.getFormValues('frmListaArtAlt'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmListaArtAlt['cbx3'];
	if (isset($arrayObj3)) {
		$i = 0;
		foreach ($arrayObj3 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmArtAlt:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObjArtAlt","value",((count($arrayObj3) > 0) ? implode("|",$arrayObj3) : ""));
	
	return $objResponse;
}

function eliminarArticuloSustituto($frmListaArtSust) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaArtSust['cbxItmArtSust'])) {
		foreach ($frmListaArtSust['cbxItmArtSust'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmArtSust:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}		
		$objResponse->script("xajax_eliminarArticuloSustituto(xajax.getFormValues('frmListaArtSust'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaArtSust['cbx2'];
	if (isset($arrayObj2)) {
		$i = 0;
		foreach ($arrayObj2 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmArtSust:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObjArtSust","value",((count($arrayObj2) > 0) ? implode("|",$arrayObj2) : ""));
	
	return $objResponse;
}

function eliminarImpuestoArticulo($frmArticulo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmArticulo['cbxItmImpuesto'])) {
		foreach($frmArticulo['cbxItmImpuesto'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmImpuesto:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarImpuestoArticulo(xajax.getFormValues('frmArticulo'));");
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj4 = $frmArticulo['cbx4'];
	if (isset($arrayObj4)) {
		$i = 0;
		foreach ($arrayObj4 as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmImpuesto:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	
	return $objResponse;
}

function formArticuloSustAlt($hddModoArticulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("hddModoArticulo","value",$hddModoArticulo);
	$objResponse->loadCommands(objetoCodigoDinamico('tdCodigoArtBuscar', $_SESSION['idEmpresaUsuarioSysGts'], '', '', '', false, 'Buscar'));
	
	$objResponse->assign("divListaArtSustAlt","innerHTML","");
	
	return $objResponse;
}

function guardarArticulo($frmArticulo, $frmListaArtSust, $frmListaArtAlt) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaArtSust['cbx2'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmListaArtAlt['cbx3'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj4 = $frmArticulo['cbx4'];
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $frmArticulo['hddCantCodigo']; $cont++) {
		$codArticulo .= str_replace(" ","",$frmArticulo['txtCodigoArticulo'.$cont].";");
	}
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	
	$idEmpresa = $frmArticulo['txtIdEmpresa'];
	
	if ($frmArticulo['hddIdArticulo'] > 0) {
		if (!xvalidaAcceso($objResponse,"iv_articulo_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE iv_articulos SET
			codigo_articulo = %s,
			id_modo_compra = %s,
			id_marca = %s,
			id_tipo_articulo = %s,
			codigo_articulo_prov = %s,
			descripcion = %s,
			id_subseccion = %s,
			foto = %s,
			id_tipo_unidad = %s,
			peso_articulo = %s,
			largo_volumen = %s,
			ancho_volumen = %s,
			alto_volumen = %s,
			id_arancel_familia = %s,
			id_precio_predeterminado = %s,
			genera_comision = %s
		WHERE id_articulo = %s;",
			valTpDato($codArticulo, "text"),
			valTpDato($frmArticulo['lstModoCompra'], "int"),
			valTpDato($frmArticulo['lstMarcaArt'], "int"),
			valTpDato($frmArticulo['lstTipoArticuloArt'], "int"),
			valTpDato($frmArticulo['txtCodigoProveedor'], "text"),
			valTpDato(str_replace("\n",". ",$frmArticulo['txtDescripcion']), "text"),
			valTpDato($frmArticulo['lstSubSeccionArt'], "int"),
			valTpDato($frmArticulo['hddUrlImagen'], "text"),
			valTpDato($frmArticulo['lstTipoUnidad'], "int"),
			valTpDato($frmArticulo['txtPeso'], "real_inglesa"),
			valTpDato($frmArticulo['txtLargo'], "real_inglesa"),
			valTpDato($frmArticulo['txtAncho'], "real_inglesa"),
			valTpDato($frmArticulo['txtAlto'], "real_inglesa"),
			valTpDato($frmArticulo['txtIdArancelFamilia'], "int"),
			valTpDato($frmArticulo['lstPrecioPredet'], "int"),
			valTpDato($frmArticulo['lstGeneraComision'], "boolean"),
			valTpDato($frmArticulo['hddIdArticulo'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idArticulo = $frmArticulo['hddIdArticulo'];
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_articulo_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO iv_articulos (codigo_articulo, id_modo_compra, id_marca, id_tipo_articulo, codigo_articulo_prov, descripcion, id_subseccion, clasificacion, foto, id_tipo_unidad, id_empresa_creador, peso_articulo, largo_volumen, ancho_volumen, alto_volumen, id_arancel_familia, id_precio_predeterminado, genera_comision)
		VALUE (TRIM(%s), %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($codArticulo, "text"),
			valTpDato($frmArticulo['lstModoCompra'], "int"),
			valTpDato($frmArticulo['lstMarcaArt'], "int"),
			valTpDato($frmArticulo['lstTipoArticuloArt'], "int"),
			valTpDato($frmArticulo['txtCodigoProveedor'], "text"),
			valTpDato(str_replace("\n",". ",$frmArticulo['txtDescripcion']), "text"),
			valTpDato($frmArticulo['lstSubSeccionArt'], "int"),
			valTpDato($frmArticulo['hddClasificacion'], "text"),
			valTpDato($frmArticulo['hddUrlImagen'], "text"),
			valTpDato($frmArticulo['lstTipoUnidad'], "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($frmArticulo['txtPeso'], "real_inglesa"),
			valTpDato($frmArticulo['txtLargo'], "real_inglesa"),
			valTpDato($frmArticulo['txtAncho'], "real_inglesa"),
			valTpDato($frmArticulo['txtAlto'], "real_inglesa"),
			valTpDato($frmArticulo['txtIdArancelFamilia'], "int"),
			valTpDato($frmArticulo['lstPrecioPredet'], "int"),
			valTpDato($frmArticulo['lstGeneraComision'], "boolean"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idArticulo = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// VERIFICAR SI EXISTEN AUN LOS ARTICULOS SUSTITUTOS QUE ESTABAN EN LA BD
	$queryCodigoSustituto = sprintf("SELECT * FROM iv_articulos_codigos_sustitutos
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsCodigoSustituto = mysql_query($queryCodigoSustituto);
	if (!$rsCodigoSustituto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowCodigoSustituto = mysql_fetch_assoc($rsCodigoSustituto)) {
		$existCodigoSustituto = false;
		if (isset($arrayObj2)) {
			foreach ($arrayObj2 as $indice => $valor) {
				if ($rowCodigoSustituto['id_articulo_codigo_sustituto'] == $frmListaArtSust['hddIdArtCodigoSustituto'.$valor]) {
					$existCodigoSustituto = true;
				}
			}
		}
		
		if ($existCodigoSustituto == false) {
			$deleteSQL = sprintf("DELETE FROM iv_articulos_codigos_sustitutos WHERE id_articulo_codigo_sustituto = %s",
				valTpDato($rowCodigoSustituto['id_articulo_codigo_sustituto'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	// VERIFICAR SI EXISTEN AUN LOS ARTICULOS ALTERNOS QUE ESTABAN EN LA BD
	$queryCodigoAlterno = sprintf("SELECT * FROM iv_articulos_codigos_alternos
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsCodigoAlterno = mysql_query($queryCodigoAlterno);
	if (!$rsCodigoAlterno) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowCodigoAlterno = mysql_fetch_assoc($rsCodigoAlterno)) {
		$existCodigoAlterno = false;
		if (isset($arrayObj3)) {
			foreach ($arrayObj3 as $indice => $valor) {
				if ($rowCodigoAlterno['id_articulo_codigo_alterno'] == $frmListaArtAlt['hddIdArtCodigoAlterno'.$valor]) {
					$existCodigoAlterno = true;
				}
			}
		}
		
		if ($existCodigoAlterno == false) {
			$deleteSQL = sprintf("DELETE FROM iv_articulos_codigos_alternos WHERE id_articulo_codigo_alterno = %s",
				valTpDato($rowCodigoAlterno['id_articulo_codigo_alterno'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	// VERIFICA SI LOS IMPUESTOS ALMACENADAS EN LA BD AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryArticuloImpuesto = sprintf("SELECT * FROM iv_articulos_impuesto art_impuesto
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticuloImpuesto = mysql_query($queryArticuloImpuesto);
	if (!$rsArticuloImpuesto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowArticuloImpuesto = mysql_fetch_assoc($rsArticuloImpuesto)) {
		$existRegDet = false;
		if (isset($arrayObj4)) {
			foreach($arrayObj4 as $indice => $valor) {
				if ($rowArticuloImpuesto['id_articulo_impuesto'] == $frmArticulo['hddIdArticuloImpuesto'.$valor]) {
					$existRegDet = true;
				}
			}
		}
		
		if ($existRegDet == false) {
			$deleteSQL = sprintf("DELETE FROM iv_articulos_impuesto WHERE id_articulo_impuesto = %s;",
				valTpDato($rowArticuloImpuesto['id_articulo_impuesto'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	// INSERTA LOS ARTICULOS SUSTITUTOS NUEVOS
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indice => $valor) {
			if ($frmListaArtSust['hddIdArtCodigoSustituto'.$valor] == "") {
				$insertSQL = sprintf("INSERT INTO iv_articulos_codigos_sustitutos (id_articulo, id_articulo_sustituido)
				VALUE (%s, %s);", 
					valTpDato($idArticulo, "int"),
					valTpDato($frmListaArtSust['hddIdArtSust'.$valor], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// INSERTA LOS ARTICULOS ALTERNOS NUEVOS
	if (isset($arrayObj3)) {
		foreach ($arrayObj3 as $indice => $valor) {
			if ($frmListaArtAlt['hddIdArtCodigoAlterno'.$valor] == "") {
				$insertSQL = sprintf("INSERT INTO iv_articulos_codigos_alternos (id_articulo, id_articulo_alterno)
				VALUE (%s, %s);", 
					valTpDato($idArticulo, "int"),
					valTpDato($frmListaArtAlt['hddIdArtAlt'.$valor], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// INSERTA LOS IMPUESTOS NUEVOS
	if (isset($arrayObj4)) {
		foreach($arrayObj4 as $indice => $valor) {
			$idImpuesto = $frmArticulo['hddIdImpuesto'.$valor];
			
			if ($idImpuesto > 0 && $frmArticulo['hddIdArticuloImpuesto'.$valor] == "") {
				$insertSQL = sprintf("INSERT INTO iv_articulos_impuesto (id_articulo, id_impuesto)
				VALUE (%s, %s);",
					valTpDato($idArticulo, "int"),
					valTpDato($idImpuesto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// ACTUALIZA SI TIENE IMPUESTO O NO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$updateSQL = sprintf("UPDATE iv_articulos SET
		posee_iva = IF((SELECT COUNT(id_articulo)
							FROM iv_articulos_impuesto art_impuesto
								INNER JOIN pg_iva iva ON (art_impuesto.id_impuesto = iva.idIva)
							WHERE iva.tipo IN (1,6) AND iva.estado = 1 AND iva.activo = 1
								AND art_impuesto.id_articulo = iv_articulos.id_articulo) > 0, 1, NULL)
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { errorGuardarUnidadBasica($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	// VERIFICA SI EL ARTICULO YA ESTA REGISTRADO PARA LA EMPRESA
	$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
	WHERE id_empresa = %s
		AND id_articulo = %s",
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"));
	$rsArtEmp = mysql_query($queryArtEmp);
	if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
	$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
	
	if ($totalRowsArtEmp == 0) { // SI NO EXISTE EL ARTICULO PARA LA EMPRESA LO INSERTA
		$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, clasificacion, estatus)
		VALUE (%s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($frmArticulo['hddClasificacion'], "text"),
			valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
		$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA EL COSTO PROMEDIO
		$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA EL PRECIO DE VENTA
		$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	} else { // EN CASO DE QUE YA EXISTIA LO COLOCA COMO ACTIVO NUEVAMENTE
		$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
			estatus = 1
		WHERE id_articulo_empresa = %s;",
			valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
		$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA EL COSTO PROMEDIO
		$Result1 = actualizarCostoPromedio($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		
		// ACTUALIZA EL PRECIO DE VENTA
		$Result1 = actualizarPrecioVenta($idArticulo, $idEmpresa);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
	$Result1 = actualizarPedidas($idArticulo);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("hddIdArticulo","value",$idArticulo);
	
	$objResponse->alert("Artículo Guardado con Éxito");
	
	$objResponse->script("byId('btnCancelar').click();");
	
	return $objResponse;
}

function insertarArticuloAlterno($idArticulo, $frmListaArtAlt) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj3 = $frmListaArtAlt['cbx3'];
	$contFila3 = $arrayObj3[count($arrayObj3)-1];
	
	$existe = false;
	if (isset($arrayObj3)) {
		foreach ($arrayObj3 as $indice => $valor) {
			if ($frmListaArtAlt['hddIdArtAlt'.$valor] == $idArticulo)
				$existe = true;
		}
	}
	
	if ($existe == false) {
		$Result1 = insertarItemArticuloAlt($contFila3, "", $idArticulo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila3 = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj3[] = $contFila3;
		}
	} else {
		$objResponse->alert("El(Los) registro(s) ya se encuentra(n) incluido(s)");
	}
	
	$objResponse->script("xajax_eliminarArticuloAlterno(xajax.getFormValues('frmListaArtAlt'));");
	
	return $objResponse;
}

function insertarArticuloSustituto($idArticulo, $frmListaArtSust) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj2 = $frmListaArtSust['cbx2'];
	$contFila2 = $arrayObj2[count($arrayObj2)-1];
	
	$existe = false;
	if (isset($arrayObj2)) {
		foreach ($arrayObj2 as $indice => $valor) {
			if ($frmListaArtSust['hddIdArtSust'.$valor] == $idArticulo)
				$existe = true;
		}
	}
	
	if ($existe == false) {
		$Result1 = insertarItemArticuloSust($contFila2, "", $idArticulo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila2 = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj2[] = $contFila2;
		}
	} else {
		$objResponse->alert("El(Los) registro(s) ya se encuentra(n) incluido(s)");
	}
	
	$objResponse->script("xajax_eliminarArticuloSustituto(xajax.getFormValues('frmListaArtSust'));");
	
	return $objResponse;
}

function insertarImpuesto($idImpuesto, $frmArticulo) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj4 = $frmArticulo['cbx4'];
	$contFila4 = $arrayObj4[count($arrayObj4)-1];
	
	if ($idImpuesto > 0) {
		$existe = false;
		if (isset($arrayObj4)) {
			foreach ($arrayObj4 as $indice => $valor) {
				if ($frmArticulo['hddIdImpuesto'.$valor] == $idImpuesto) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemImpuesto($contFila4, "", $idImpuesto);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila4 = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj4[] = $contFila4;
			}
		} else {
			$objResponse->alert("Este item ya se encuentra incluido");
		}
	}
	
	$objResponse->script("xajax_eliminarImpuestoArticulo(xajax.getFormValues('frmArticulo'));");
	
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArancelFamilia(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("divListaArancelFamilia","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 18, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[0], "text"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(codigo_articulo LIKE %s
		OR descripcion LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_articulo <> %s",
			valTpDato($valCadBusq[2], "int"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['foto'];
		
		$htmlTb .= "<td valign=\"top\" width=\"33%\">";
			$htmlTb .= "<table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td rowspan=\"2\">";
					$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarArticulo%s\" onclick=\"validarInsertarArticulo('%s', '%s')\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
						$contFila,
						$row['id_articulo'],
						$valCadBusq[3]);
				$htmlTb .= "</td>";
				$htmlTb .= sprintf("<td rowspan=\"2\" style=\"background-color:#FFFFFF\">%s</td>",
					"<img src=\"".$imgFoto."\" width=\"80\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>",
					"100%",
					elimCaracter(utf8_encode($row['codigo_articulo']),";"));
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['descripcion']));
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"3\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArtSustAlt","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaImpuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iva.estado = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("iva.tipo IN (1,8,3,6,2)");
	
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
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

function verArticulo($idArticulo, $objDestino) {
	$objResponse = new xajaxResponse();
	
	$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos
	WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
	$imgFoto = (!file_exists($rowArticulo['foto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $rowArticulo['foto'];
	
	$html = "<table border=\"0\" width=\"100%\">";
	$html .= "<tr>";
		$html .= "<td width=\"20%\">";
			$html .= "<img src=\"".$imgFoto."\" width=\"240\"/>";
		$html .= "</td>";
		$html .= "<td valign=\"top\" width=\"80%\">";
			$html .= "<table border=\"0\" width=\"100%\">";
			$html .= "<tr align=\"left\" height=\"24\">";
				$html .= "<td align=\"right\" class=\"tituloCampo\" width=\"16%\">Código:</td>";
				$html .= "<td width=\"24%\">".elimCaracter($rowArticulo['codigo_articulo'],";")."</td>";
				$html .= "<td align=\"right\" class=\"tituloCampo\" width=\"16%\">Cod. Artículo:<br><span class=\"textoNegrita_10px\">(Proveedor)</span></td>";
				$html .= "<td width=\"24%\">".$rowArticulo['codigo_articulo_prov']."</td>";
			$html .= "</tr>";
			$html .= "<tr align=\"left\" height=\"24\">";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Marca:</td>";
				$html .= "<td>".$rowArticulo['marca']."</td>";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Tipo de Artículo:</td>";
				$html .= "<td>".$rowArticulo['tipo_articulo']."</td>";
			$html .= "</tr>";
			$html .= "<tr align=\"left\" height=\"24\">";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Descripcion:</td>";
				$html .= "<td colspan=\"3\">".utf8_encode($rowArticulo['descripcion'])."</td>";
			$html .= "</tr>";
			$html .= "<tr align=\"left\" height=\"24\">";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Sección:</td>";
				$html .= "<td>".utf8_encode($rowArticulo['descripcion_seccion'])."</td>";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Sub-Sección:</td>";
				$html .= "<td>".utf8_encode($rowArticulo['descripcion_subseccion'])."</td>";
			$html .= "</tr>";
			$html .= "<tr align=\"left\" height=\"24\">";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Stock Máximo:</td>";
				$html .= "<td>".$rowArticulo['stock_maximo']."</td>";
				$html .= "<td align=\"right\" class=\"tituloCampo\">Stock Minimo:</td>";
				$html .= "<td>".$rowArticulo['stock_minimo']."</td>";
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	$objResponse->assign($objDestino,"innerHTML",$html);
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("hddModulo","value",$hddModulo);
	
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
	
	if ($rowPermiso['id_clave_usuario'] != "") {
		if ($frmPermiso['hddModulo'] == "iv_articulo_form_descripcion") {
			$objResponse->script("byId('txtDescripcion').readOnly = false;");
			$objResponse->script("byId('aDesbloquearDescripcion').style.display = 'none';");
			
		} else if ($frmPermiso['hddModulo'] == "iv_articulo_form_aplica_iva") {
			$objResponse->script("
			byId('aNuevoImpuesto').style.display = '';
			byId('btnEliminarImpuesto').style.display = '';");
			$objResponse->script("byId('aDesbloquearIva').style.display = 'none';");
		
		} else if ($frmPermiso['hddModulo'] == "iv_articulo_form_genera_comision") {
			$objResponse->script("byId('lstGeneraComision').onchange = function(){};");
			$objResponse->script("byId('aDesbloquearComision').style.display = 'none';");
			
		} else if ($frmPermiso['hddModulo'] == "iv_articulo_form_precio") {
			$objResponse->script("byId('lstPrecioPredet').onchange = function(){};");
			$objResponse->script("byId('aDesbloquearPrecio').style.display = 'none';");
		}
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"buscarArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarImpuesto");
$xajax->register(XAJAX_FUNCTION,"cargaLstArticulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstSubSeccion");
$xajax->register(XAJAX_FUNCTION,"cargarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarArticuloAlterno");
$xajax->register(XAJAX_FUNCTION,"eliminarArticuloSustituto");
$xajax->register(XAJAX_FUNCTION,"eliminarImpuestoArticulo");
$xajax->register(XAJAX_FUNCTION,"formArticuloSustAlt");
$xajax->register(XAJAX_FUNCTION,"guardarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarArticuloAlterno");
$xajax->register(XAJAX_FUNCTION,"insertarArticuloSustituto");
$xajax->register(XAJAX_FUNCTION,"insertarImpuesto");
$xajax->register(XAJAX_FUNCTION,"listaArancelFamilia");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaImpuesto");
$xajax->register(XAJAX_FUNCTION,"verArticulo");

$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");

function insertarItemArticuloAlt($contFila, $hddIdArticuloCodigoAlterno = "", $idArticulo = "") {
	$contFila++;
	
	if ($hddIdArticuloCodigoAlterno > 0) {
		// BUSCA LOS DATOS DEL CODIGO ALTERNO
		$queryCodigoAlt = sprintf("SELECT * FROM iv_articulos_codigos_alternos
		WHERE id_articulo_codigo_alterno = %s;",
			valTpDato($hddIdArticuloCodigoAlterno, "int"));
		$rsCodigoAlt = mysql_query($queryCodigoAlt);
		if (!$rsCodigoAlt) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsCodigoAlt = mysql_num_rows($rsCodigoAlt);
		$rowCodigoAlt = mysql_fetch_assoc($rsCodigoAlt);
	}
	
	$idArticulo = ($idArticulo == "" && $totalRowsCodigoAlt > 0) ? $rowCodigoAlt['id_articulo_alterno'] : $idArticulo;
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsArticulo = mysql_num_rows($rsArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieArtAlt').before('".
		"<tr align=\"left\" id=\"trItmArtAlt:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmArtAlt:%s\"><input id=\"cbxItmArtAlt\" name=\"cbxItmArtAlt[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx3\" name=\"cbx3[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>".
				"<a class=\"modalImg\" id=\"aArtAlt:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Artículo\"/></a>".
				"<input type=\"hidden\" id=\"hddIdArtAlt%s\" name=\"hddIdArtAlt%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArtCodigoAlterno%s\" name=\"hddIdArtCodigoAlterno%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aArtAlt:%s').onclick = function() { abrirDivFlotante1(this, 'tblFlotanteContenido', '%s'); }",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			elimCaracter($rowArticulo['codigo_articulo'],";"),
			str_replace("\n",", ",utf8_encode($rowArticulo['descripcion'])),
			$contFila, 
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, $hddIdArticuloCodigoAlterno,
		
		$contFila, $rowArticulo['id_articulo']);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemArticuloSust($contFila, $hddIdArticuloCodigoSustituto = "", $idArticulo = "") {
	$contFila++;
	
	if ($hddIdArticuloCodigoSustituto > 0) {
		// BUSCA LOS DATOS DEL CODIGO SUSTITUTO
		$queryCodigoSust = sprintf("SELECT * FROM iv_articulos_codigos_sustitutos
		WHERE id_articulo_codigo_sustituto = %s;",
			valTpDato($hddIdArticuloCodigoSustituto, "int"));
		$rsCodigoSust = mysql_query($queryCodigoSust);
		if (!$rsCodigoSust) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsCodigoSust = mysql_num_rows($rsCodigoSust);
		$rowCodigoSust = mysql_fetch_assoc($rsCodigoSust);
	}
	
	$idArticulo = ($idArticulo == "" && $totalRowsCodigoSust > 0) ? $rowCodigoSust['id_articulo_sustituido'] : $idArticulo;
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsArticulo = mysql_num_rows($rsArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieArtSust').before('".
		"<tr align=\"left\" id=\"trItmArtSust:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmArtSust:%s\"><input id=\"cbxItmArtSust\" name=\"cbxItmArtSust[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx2\" name=\"cbx2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>".
				"<a class=\"modalImg\" id=\"aArtSust:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Artículo\"/></a>".
				"<input type=\"hidden\" id=\"hddIdArtSust%s\" name=\"hddIdArtSust%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArtCodigoSustituto%s\" name=\"hddIdArtCodigoSustituto%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aArtSust:%s').onclick = function() {
			abrirDivFlotante1(this, 'tblFlotanteContenido', '%s');
		}",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			elimCaracter($rowArticulo['codigo_articulo'],";"),
			str_replace("\n",", ",utf8_encode($rowArticulo['descripcion'])),
			$contFila, 
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, $hddIdArticuloCodigoSustituto,
		
		$contFila, $rowArticulo['id_articulo']);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemImpuesto($contFila, $hddIdArticuloImpuesto = "", $idImpuesto = "") {
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
				"<input id=\"cbx4\" name=\"cbx4[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td align=\"right\">%s".
				"<input type=\"hidden\" id=\"hddIdArticuloImpuesto%s\" name=\"hddIdArticuloImpuesto%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdImpuesto%s\" name=\"hddIdImpuesto%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['tipo_impuesto']),
			utf8_encode($row['observacion']),
			utf8_encode($row['iva']),
				$contFila, $contFila, $hddIdArticuloImpuesto,
				$contFila, $contFila, $idImpuesto);
	
	return array(true, $htmlItmPie, $contFila);
}
?>