<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function asignarCalle($idCalle) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_calles WHERE id_calle = %s;",
		valTpDato($idCalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddIdCalle","value",$idCalle);
	$objResponse->assign("txtCalle","value",utf8_encode($row['descripcion_calle']));
	
	return $objResponse;
}

function asignarCasilla($idCasilla) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_casillas WHERE id_casilla = %s;",
		valTpDato($idCasilla, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddIdCasilla","value",$idCasilla);
	$objResponse->assign("txtCasilla","value",utf8_encode($row['descripcion_casilla']));
	
	return $objResponse;
}

function asignarEstante($idEstante) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_estantes WHERE id_estante = %s;",
		valTpDato($idEstante, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddIdEstante","value",$idEstante);
	$objResponse->assign("txtEstante","value",utf8_encode($row['descripcion_estante']));
	
	return $objResponse;
}

function asignarTramo($idTramo) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_tramos WHERE id_tramo = %s;",
		valTpDato($idTramo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddIdTramo","value",$idTramo);
	$objResponse->assign("txtTramo","value",utf8_encode($row['descripcion_tramo']));
	
	return $objResponse;
}

function buscarAlmacen($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listadoAlmacenes(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$valForm['hddObjDestino'],
		$valForm['hddNomVentana'],
		$valForm['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function cargarAlmacen($nomObjeto, $idAlmacen) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"ga_almacen_list","editar")) {
		$objResponse->script("
		document.forms['frmAlmacen'].reset();
		byId('hddIdAlmacen').value = '';
		
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtDesAlmacen').className = 'inputHabilitado';
		byId('lstEstatus').className = 'inputHabilitado';
		byId('lstEstatusVenta').className = 'inputHabilitado';
		byId('lstEstatusCompra').className = 'inputHabilitado';
		
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';");
		
		$objResponse->script("
		byId('trlstCalle').style.display = '';
		byId('trlstEstante').style.display = '';
		byId('trlstTramo').style.display = '';
		byId('trlstCasilla').style.display = '';");
		
		// BUSCA LOS DATOS DEL ALMACEN
		$queryAlmacen = sprintf("SELECT * FROM ga_almacenes WHERE id_almacen = %s",
			valTpDato($idAlmacen, "int"));
		$rsAlmacen = mysql_query($queryAlmacen);
		if (!$rsAlmacen) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowAlmacen = mysql_fetch_assoc($rsAlmacen);
		
		$objResponse->assign("hddIdAlmacen","value",$idAlmacen);
		$objResponse->assign("txtDesAlmacen","value",$rowAlmacen['descripcion']);
		$objResponse->loadCommands(asignarEmpresaUsuario($rowAlmacen['id_empresa'],"Empresa","ListaEmpresa"));
		$objResponse->call("selectedOption","lstEstatus",$rowAlmacen['estatus']);
		$objResponse->call("selectedOption","lstEstatusVenta",$rowAlmacen['estatus_almacen_venta']);
		$objResponse->call("selectedOption","lstEstatusCompra",$rowAlmacen['estatus_almacen_compra']);
		$objResponse->loadCommands(cargaLstCalle($idAlmacen, -1));
		
		$objResponse->script("
		byId('tblAlmacen').style.display = '';
		byId('tblImportarAlmacen').style.display = 'none';");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Almacén");
		
		$objResponse->script("
		byId('txtDesAlmacen').focus();
		byId('txtDesAlmacen').select();");
		
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
	}
	
	return $objResponse;
}

function cargaLstCalle($idAlmacen, $idCalle) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		ga_calles.id_calle,
		ga_calles.descripcion_calle
	FROM ga_calles
		INNER JOIN ga_almacenes ON (ga_calles.id_almacen = ga_almacenes.id_almacen)
	WHERE ga_almacenes.id_almacen = %s
	ORDER BY ga_calles.descripcion_calle",
		valTpDato($idAlmacen, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstCalle\" name=\"lstCalle\" onChange=\"xajax_cargaLstEstante((this.value),-1)\">";
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($row['id_calle'] == $idCalle) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_calle']."\" ".$selected.">".utf8_encode($row['descripcion_calle'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCalle","innerHTML",$html);
	
	$objResponse->loadCommands(cargaLstEstante($idCalle, -1));
	
	return $objResponse;
}

function cargaLstCasilla($idTramo, $idCasilla) {
	$objResponse = new xajaxResponse();
	
	$queryTramo = sprintf("SELECT * FROM ga_tramos WHERE id_tramo = %s",
		valTpDato($idTramo, "int"));
	$rsTramo = mysql_query($queryTramo);
	if (!$rsTramo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowTramo = mysql_fetch_array($rsTramo);
	
	if ($idTramo == -1 || $rowTramo['descripcion_tramo'] == "[]") {
		$objResponse->script("
		byId('btnAgregarCasilla').disabled = true;
		byId('btnEditarTramo').disabled = true;
		byId('btnEliminarTramo').disabled = true;");
	
		$objResponse->loadCommands(habilitarBotonesCasilla(-1));
			
		$html = "<select id=\"lstCasilla\" name=\"lstCasilla\"><option value=\"-1\">[ Seleccione ]</option></select>";
	} else {
		$objResponse->script("
		byId('btnAgregarCasilla').disabled = false;
		byId('btnEditarTramo').disabled = false;
		byId('btnEliminarTramo').disabled = false;");
	
		$objResponse->loadCommands(habilitarBotonesCasilla($idCasilla));
		
		$query = sprintf("SELECT
			ga_casillas.id_casilla,
			ga_casillas.descripcion_casilla
		FROM ga_tramos
			INNER JOIN ga_casillas ON (ga_tramos.id_tramo = ga_casillas.id_tramo)
		WHERE ga_tramos.id_tramo = %s
		ORDER BY ga_casillas.descripcion_casilla",
			valTpDato($idTramo, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$html = "<select id=\"lstCasilla\" name=\"lstCasilla\" onChange=\"xajax_habilitarBotonesCasilla(this.value)\">";
			$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($row['id_casilla'] == $idCasilla) ? "selected='selected'" : "";
			
			$html .= "<option value=\"".$row['id_casilla']."\" ".$selected.">".utf8_encode($row['descripcion_casilla'])."</option>";
		}
		$html .= "</select>";
	}
	$objResponse->assign("tdlstCasilla","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstante($idCalle, $idEstante) {
	$objResponse = new xajaxResponse();
	
	$queryCalle = sprintf("SELECT * FROM ga_calles WHERE id_calle = %s",
		valTpDato($idCalle, "int"));
	$rsCalle = mysql_query($queryCalle);
	if (!$rsCalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowCalle = mysql_fetch_array($rsCalle);
	
	if ($idCalle == -1 || $rowCalle['descripcion_calle'] == "[]") {
		$objResponse->script("
		byId('btnAgregarEstante').disabled = true;
		byId('btnEditarCalle').disabled = true;
		byId('btnEliminarCalle').disabled = true;");
		
		$objResponse->loadCommands(cargaLstTramo(-1, -1));
							
		$html = "<select id=\"lstEstante\" name=\"lstEstante\"><option value=\"-1\">[ Seleccione ]</option></select>";
	} else {
		$objResponse->script("
		byId('btnAgregarEstante').disabled = false;
		byId('btnEditarCalle').disabled = false;
		byId('btnEliminarCalle').disabled = false;");
		
		$objResponse->loadCommands(cargaLstTramo($idEstante, -1));
		
		$query = sprintf("SELECT
			ga_estantes.id_estante,
			ga_estantes.descripcion_estante
		FROM ga_calles
			INNER JOIN ga_estantes ON (ga_calles.id_calle = ga_estantes.id_calle)
		WHERE ga_calles.id_calle = %s
		ORDER BY ga_estantes.descripcion_estante",
			valTpDato($idCalle, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$html = "<select id=\"lstEstante\" name=\"lstEstante\" onChange=\"xajax_cargaLstTramo((this.value),-1)\">";
			$html .="<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($row['id_estante'] == $idEstante) ? "selected='selected'" : "";
			
			$html .= "<option value=\"".$row['id_estante']."\"".$selected.">".utf8_encode($row['descripcion_estante'])."</option>";
		}
		$html .= "</select>";
	}
	$objResponse->assign("tdlstEstante","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTramo($idEstante, $idTramo) {
	$objResponse = new xajaxResponse();
	
	$queryEstante = sprintf("SELECT * FROM ga_estantes WHERE id_estante = %s",
		valTpDato($id, "int"));
	$rsEstante = mysql_query($queryEstante);
	if (!$rsEstante) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEstante = mysql_fetch_array($rsEstante);
	
	if ($idEstante == -1 || $rowEstante['descripcion_estante'] == "[]") {
		$objResponse->script("
		byId('btnAgregarTramo').disabled = true;
		byId('btnEditarEstante').disabled = true;
		byId('btnEliminarEstante').disabled = true;");
		
		$objResponse->loadCommands(cargaLstCasilla(-1, -1));
			
		$html = "<select id=\"lstTramo\" name=\"lstTramo\"><option value=\"-1\">[ Seleccione ]</option></select>";
	} else {
		$objResponse->script("
		byId('btnAgregarTramo').disabled = false;
		byId('btnEditarEstante').disabled = false;
		byId('btnEliminarEstante').disabled = false;");
		
		$objResponse->loadCommands(cargaLstCasilla($idTramo, -1));
		
		$query = sprintf("SELECT
			ga_tramos.id_tramo,
			ga_tramos.descripcion_tramo
		FROM ga_estantes
			INNER JOIN ga_tramos ON (ga_estantes.id_estante = ga_tramos.id_estante)
		WHERE ga_estantes.id_estante = %s
		ORDER BY ga_tramos.descripcion_tramo",
			valTpDato($idEstante, "int"));	
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$html = "<select id=\"lstTramo\" name=\"lstTramo\" onChange=\"xajax_cargaLstCasilla(this.value,-1)\">";
			$html .="<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($row['id_tramo'] == $idTramo) ? "selected='selected'" : "";
			
			$html .= "<option value=\"".$row['id_tramo']."\" ".$selected.">".utf8_encode($row['descripcion_tramo'])."</option>";
		}
		$html .= "</select>";
	}
	$objResponse->assign("tdlstTramo","innerHTML",$html);
	
	return $objResponse;
}

function eliminarAlmacen($idAlmacen, $valFormListaAlmacen) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"ga_articulo_list","eliminar")) {
		if (isset($idAlmacen)) {
			$queryExistencia = sprintf("SELECT existencia
			FROM vw_ga_articulos_almacen vw_ga_art_alm
				INNER JOIN vw_ga_articulos_empresa_ubicacion vw_ga_art_emp_ubic ON (vw_ga_art_alm.id_casilla = vw_ga_art_emp_ubic.id_casilla)
			WHERE vw_ga_art_alm.id_almacen = %s
				AND existencia > 0;",
					valTpDato($idAlmacen, "int"));
			$rsExistencia = mysql_query($queryExistencia);
			if (!$rsExistencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$totalRowsExistencia = mysql_num_rows($rsExistencia);
	
			if ($totalRowsExistencia == 0){
				mysql_query("START TRANSACTION;");
			
				$deleteSQL = sprintf("DELETE FROM ga_almacenes WHERE ga_almacenes.id_almacen = %s",
					valTpDato($idAlmacen, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				
				mysql_query("COMMIT;");
				
				$objResponse->alert("Eliminacion realizada con éxito");
				
				$objResponse->loadCommands(listadoAlmacenes(
					$valFormListaAlmacen['pageNum'],
					$valFormListaAlmacen['campOrd'],
					$valFormListaAlmacen['tpOrd'],
					$valFormListaAlmacen['valBusq']));
			} else {
				$objResponse->alert("Este registro no puede ser eliminado debido a que tiene otros registros dependientes");
			}
		}
	}
	
	return $objResponse;
}

function eliminarAlmacenBloque($valFormListaAlmacen) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"ga_almacen_list","eliminar")) {
		mysql_query("START TRANSACTION;");
				
		foreach($valFormListaAlmacen['cbxItm'] as $indiceItm => $valorItm) {
			$queryExistencia = sprintf("SELECT vw_ga_art_alm.descripcion, existencia
			FROM vw_ga_articulos_almacen vw_ga_art_alm
				INNER JOIN vw_ga_articulos_empresa_ubicacion vw_ga_art_emp_ubic ON (vw_ga_art_alm.id_casilla = vw_ga_art_emp_ubic.id_casilla)
			WHERE vw_ga_art_alm.id_almacen = %s
				AND existencia > 0;",
				valTpDato($valorItm, "int"));
			$rsExistencia = mysql_query($queryExistencia);
			if (!$rsExistencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowExistencia = mysql_fetch_assoc($rsExistencia);
			$totalRowsExistencia = mysql_num_rows($rsExistencia);
			
			if ($totalRowsExistencia == 0) {
				$deleteSQL = sprintf("DELETE FROM ga_almacenes WHERE id_almacen = %s;",
					valTpDato($valorItm, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			} else {
				$objResponse->alert(sprintf("El registro (%s) no puede ser eliminado debido a que tiene otros registros dependientes",
					$rowExistencia['descripcion']));
			}
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Eliminacion realizada con éxito");
	
		$objResponse->loadCommands(listadoAlmacenes(
			$valFormListaAlmacen['pageNum'],
			$valFormListaAlmacen['campOrd'],
			$valFormListaAlmacen['tpOrd'],
			$valFormListaAlmacen['valBusq']));
	}
	
	return $objResponse;
}

function eliminarCalle($idCalle) {
	$objResponse = new xajaxResponse();
	
	$queryExistencia = sprintf("SELECT existencia
	FROM vw_ga_articulos_almacen vw_ga_art_alm
		INNER JOIN vw_ga_articulos_empresa_ubicacion vw_ga_art_emp_ubic ON (vw_ga_art_alm.id_casilla = vw_ga_art_emp_ubic.id_casilla)
	WHERE vw_ga_art_alm.id_calle = %s
		AND existencia > 0",
		valTpDato($idCalle, "int"));
	$rsExistencia = mysql_query($queryExistencia);
	if (!$rsExistencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$existencia = mysql_num_rows($rsExistencia);
	
	if ($existencia > 0) {
		$objResponse->alert("No se puede eliminar una calle con Artículos");
	} else {
		mysql_query("START TRANSACTION;");
		
		$queryAlmacen = "SELECT id_almacen FROM ga_calles WHERE id_calle = '".$idCalle."'";
		$rsAlmacen = mysql_query($queryAlmacen);
		if (!$rsAlmacen) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowAlmacen = mysql_fetch_array($rsAlmacen);
		
		$query = "DELETE FROM ga_calles WHERE ga_calles.id_calle = '".$idCalle."'";
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Calle Eliminada Exitosamente");
		
		$objResponse->loadCommands(cargaLstCalle($rowAlmacen['id_almacen'],-1));
	}
	
	return $objResponse;
}

function eliminarCasilla($idCasilla) {
	$objResponse = new xajaxResponse();
	
	$queryExistencia = sprintf("SELECT existencia
	FROM vw_ga_articulos_almacen vw_ga_art_alm
		INNER JOIN vw_ga_articulos_empresa_ubicacion vw_ga_art_emp_ubic ON (vw_ga_art_alm.id_casilla = vw_ga_art_emp_ubic.id_casilla)
	WHERE vw_ga_art_alm.id_casilla = %s
		AND existencia > 0",
		valTpDato($idCasilla, "int"));
	$rsExistencia = mysql_query($queryExistencia);
	if (!$rsExistencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$existencia = mysql_num_rows($rsExistencia);
	
	if ($existencia > 0) {
		$objResponse->alert("No se puede eliminar un estante con Artículos");
	} else {
		mysql_query("START TRANSACTION;");
		
		$queryTramo = "SELECT id_tramo FROM ga_casillas WHERE id_casilla = '".$idCasilla."'";
		$rsTramo = mysql_query($queryTramo);
		if (!$rsTramo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowTramo = mysql_fetch_array($rsTramo);
		
		$query = "DELETE FROM ga_casillas WHERE ga_casillas.id_casilla = '".$idCasilla."'";
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Casilla Eliminada Exitosamente");
		
		$objResponse->loadCommands(cargaLstCasilla($rowTramo['id_tramo'],-1));
	}
	
	return $objResponse;
}

function eliminarEstante($idEstante) {
	$objResponse = new xajaxResponse();
	
	$queryExistencia = sprintf("SELECT existencia
	FROM vw_ga_articulos_almacen vw_ga_art_alm
		INNER JOIN vw_ga_articulos_empresa_ubicacion vw_ga_art_emp_ubic ON (vw_ga_art_alm.id_casilla = vw_ga_art_emp_ubic.id_casilla)
	WHERE vw_ga_art_alm.id_estante = %s
		AND existencia > 0",
		valTpDato($idEstante, "int"));
	$rsExistencia = mysql_query($queryExistencia);
	if (!$rsExistencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$existencia = mysql_num_rows($rsExistencia);
	
	if ($existencia > 0) {
		$objResponse->alert("No se puede eliminar un estante con Artículos");
	} else {
		mysql_query("START TRANSACTION;");
		
		$queryCalle = "SELECT id_calle FROM ga_estantes WHERE id_estante = '".$idEstante."'";
		$rsCalle = mysql_query($queryCalle);
		if (!$rsCalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowCalle = mysql_fetch_array($rsCalle);
		
		$query = "DELETE FROM ga_estantes WHERE ga_estantes.id_estante = '".$idEstante."'";
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Estante Eliminado Exitosamente");
		
		$objResponse->loadCommands(cargaLstEstante($rowCalle['id_calle'],-1));
	}
	
	return $objResponse;
}

function eliminarTramo($idTramo) {
	$objResponse = new xajaxResponse();
	
	$queryExistencia = sprintf("SELECT existencia
	FROM vw_ga_articulos_almacen vw_ga_art_alm
		INNER JOIN vw_ga_articulos_empresa_ubicacion vw_ga_art_emp_ubic ON (vw_ga_art_alm.id_casilla = vw_ga_art_emp_ubic.id_casilla)
	WHERE vw_ga_art_alm.id_tramo = %s
		AND existencia > 0",
		valTpDato($idTramo, "int"));
	$rsExistencia = mysql_query($queryExistencia);
	if (!$rsExistencia) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$existencia = mysql_num_rows($rsExistencia);
	
	if ($existencia > 0) {
		$objResponse->alert("No se puede eliminar un tramo con Artículos");
	} else {
		mysql_query("START TRANSACTION;");
		
		$queryEstante = "SELECT id_estante FROM ga_tramos WHERE id_tramo = '".$idTramo."'";
		$rsEstante = mysql_query($queryEstante);
		if (!$rsEstante) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowEstante = mysql_fetch_array($rsEstante);
		
		$query = "DELETE FROM ga_tramos WHERE ga_tramos.id_tramo = '".$idTramo."'";
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Tramo Eliminado Exitosamente");
		
		$objResponse->loadCommands(cargaLstTramo($rowEstante['id_estante'],-1));
	}
	
	return $objResponse;
}

function formAlmacen($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"ga_almacen_list","insertar")) {
		$objResponse->script("
		document.forms['frmAlmacen'].reset();
		byId('hddIdAlmacen').value = '';
		
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtDesAlmacen').className = 'inputHabilitado';
		byId('lstEstatus').className = 'inputHabilitado';
		byId('lstEstatusVenta').className = 'inputHabilitado';
		byId('lstEstatusCompra').className = 'inputHabilitado';
		
		byId('trlstCalle').style.display = 'none';
		byId('trlstEstante').style.display = 'none';
		byId('trlstTramo').style.display = 'none';
		byId('trlstCasilla').style.display = 'none';
		
		byId('txtIdEmpresa').readOnly = false;
		byId('aListarEmpresa').style.display = '';");
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'],"Empresa","ListaEmpresa"));
		
		$objResponse->script("
		byId('tblAlmacen').style.display = '';
		byId('tblImportarAlmacen').style.display = 'none';");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Almacén");
		
		$objResponse->script("
		byId('txtDesAlmacen').focus();
		byId('txtDesAlmacen').select();");
		
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
	}
	
	return $objResponse;
}

function formImportarAlmacen($nomObjeto) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"ga_almacen_list","insertar")) {		
		$objResponse->script("
		document.forms['frmImportarAlmacen'].reset();
		byId('hddUrlArchivo').value = '';
		
		byId('txtIdEmpresaImportarAlmacen').className = 'inputHabilitado';
		byId('fleUrlArchivo').className = 'inputHabilitado';");
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'],"EmpresaImportarAlmacen","ListaEmpresa"));
		
		$objResponse->script("
		byId('tblAlmacen').style.display = 'none';
		byId('tblImportarAlmacen').style.display = '';");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Importar Almacén");
		
		$objResponse->script("
		byId('fleUrlArchivo').focus();
		byId('fleUrlArchivo').select();");
		
		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
	}
	
	return $objResponse;
}

function guardarAlmacen($valFormAlmacen, $valFormListaAlmacen) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valFormAlmacen['hddIdAlmacen'] > 0) {
		if (xvalidaAcceso($objResponse,"ga_almacen_list","editar")) {
			$updateSQL = sprintf("UPDATE ga_almacenes SET
				descripcion = %s,
				estatus = %s,
				estatus_almacen_venta = %s,
				estatus_almacen_compra = %s
			WHERE id_almacen = %s;",
				valTpDato($valFormAlmacen['txtDesAlmacen'], "text"),
				valTpDato($valFormAlmacen['lstEstatus'], "boolean"),
				valTpDato($valFormAlmacen['lstEstatusVenta'], "boolean"),
				valTpDato($valFormAlmacen['lstEstatusCompra'], "boolean"),
				valTpDato($valFormAlmacen['hddIdAlmacen'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"ga_almacen_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO ga_almacenes (id_empresa , descripcion, estatus, estatus_almacen_venta, estatus_almacen_compra)
			VALUE (%s, %s, %s, %s, %s);",
				valTpDato($valFormAlmacen['txtIdEmpresa'], "int"),
				valTpDato($valFormAlmacen['txtDesAlmacen'], "text"),
				valTpDato($valFormAlmacen['lstEstatus'], "boolean"),
				valTpDato($valFormAlmacen['lstEstatusVenta'], "boolean"),
				valTpDato($valFormAlmacen['lstEstatusCompra'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Almacén Guardado con Éxito");
	
	$objResponse->script("
	byId('btnCancelarAlmacen').click();");
	
	$objResponse->loadCommands(listadoAlmacenes(
		$valFormListaAlmacen['pageNum'],
		$valFormListaAlmacen['campOrd'],
		$valFormListaAlmacen['tpOrd'],
		$valFormListaAlmacen['valBusq']));
		
	return $objResponse;
}

function guardarUbicacion($valFormDetalles, $valFormAlmacen) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valFormDetalles['hddTipo'] == 0) {
		if ($valFormDetalles['hddIdCalle'] == "") {
			$insertSQL = sprintf("INSERT INTO ga_calles (id_almacen, descripcion_calle) value (%s, %s)",
				valTpDato($valFormAlmacen['hddIdAlmacen'], "int"),
				valTpDato($valFormDetalles['txtCalle'], "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idCalle = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO ga_estantes (id_calle, descripcion_estante) value (%s, '[]')",
				valTpDato($idCalle, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idEstante = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO ga_tramos (id_estante, descripcion_tramo) value (%s, '[]')",
				valTpDato($idEstante, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idTramo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO ga_casillas (id_tramo, descripcion_casilla) value (%s, '[]')",
				valTpDato($idTramo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$objResponse->loadCommands(cargaLstCalle($valFormAlmacen['hddIdAlmacen'], $idCalle));
		} else {
			$updateSQL = sprintf("UPDATE ga_calles SET descripcion_calle = %s WHERE id_calle = %s",
				valTpDato($valFormDetalles['txtCalle'], "text"),
				valTpDato($valFormDetalles['hddIdCalle'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$objResponse->loadCommands(cargaLstCalle($valFormAlmacen['hddIdAlmacen'], $valFormDetalles['hddIdCalle']));
		}
	} else if ($valFormDetalles['hddTipo'] == 1) {
		if ($valFormDetalles['hddIdEstante'] == "") {
			$insertSQL = sprintf("INSERT INTO ga_estantes (id_calle, descripcion_estante) value (%s, %s)",
				valTpDato($valFormDetalles['hddIdCalle'], "int"),
				valTpDato($valFormDetalles['txtEstante'], "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idEstante = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO ga_tramos (id_estante, descripcion_tramo) value (%s, '[]')",
				valTpDato($idEstante, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idTramo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO ga_casillas (id_tramo, descripcion_casilla) value (%s, '[]')",
				valTpDato($idTramo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$objResponse->script("xajax_cargaLstEstante(".$valFormDetalles['hddIdCalle'].",".$idEstante.")");
		} else {
			$updateSQL = sprintf("UPDATE ga_estantes SET descripcion_estante = %s WHERE id_estante = %s",
				valTpDato($valFormDetalles['txtEstante'], "text"),
				valTpDato($valFormDetalles['hddIdEstante'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$objResponse->script("xajax_cargaLstEstante(".$valFormDetalles['hddIdCalle'].",".$valFormDetalles['hddIdEstante'].")");
		}
	} else if ($valFormDetalles['hddTipo'] == 2) {
		if ($valFormDetalles['hddIdTramo'] == "") {
			$insertSQL = sprintf("INSERT INTO ga_tramos (id_estante, descripcion_tramo) value (%s, %s)",
				valTpDato($valFormDetalles['hddIdEstante'], "int"),
				valTpDato($valFormDetalles['txtTramo'], "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idTramo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO ga_casillas (id_tramo, descripcion_casilla) value (%s, '[]')",
				valTpDato($idTramo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$objResponse->script("xajax_cargaLstTramo(".$valFormDetalles['hddIdEstante'].",".$idTramo.")");
		} else {
			$updateSQL = sprintf("UPDATE ga_tramos SET descripcion_tramo = %s WHERE id_tramo = %s",
				valTpDato($valFormDetalles['txtTramo'], "text"),
				valTpDato($valFormDetalles['hddIdTramo'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$objResponse->script("xajax_cargaLstTramo(".$valFormDetalles['hddIdEstante'].",".$valFormDetalles['hddIdTramo'].")");
		}
	} else {
		if ($valFormDetalles['hddIdCasilla'] == "") {	
			$insertSQL = sprintf("INSERT INTO ga_casillas (id_tramo, descripcion_casilla) value (%s, %s);",
				valTpDato($valFormDetalles['hddIdTramo'], "int"),
				valTpDato($valFormDetalles['txtCasilla'], "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idCasilla = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$objResponse->script("xajax_cargaLstCasilla(".$valFormDetalles['hddIdTramo'].",".$idCasilla.")");
		} else {
			$updateSQL = sprintf("UPDATE ga_casillas SET descripcion_casilla = %s WHERE id_casilla = %s;",
				valTpDato($valFormDetalles['txtCasilla'], "text"),
				valTpDato($valFormDetalles['hddIdCasilla'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
			
			$objResponse->script("xajax_cargaLstCasilla('".$valFormDetalles['hddIdTramo']."','".$valFormDetalles['hddIdCasilla']."')");
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Registro Guardado con Éxito");
	
	$objResponse->script("byId('btnCancelarUbicacionAlmacen').click();");
	
	return $objResponse;
}

function habilitarBotonesCasilla($id) {
	$objResponse = new xajaxResponse();
	
	$queryCasilla = "SELECT * FROM ga_casillas WHERE id_casilla = '".$id."'";
	$rsCasilla = mysql_query($queryCasilla);
	if (!$rsCasilla) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowCasilla = mysql_fetch_array($rsCasilla);
	
	if ($id == -1 || $rowCasilla['descripcion_casilla'] == "[]") {
		$objResponse->script("
		byId('btnEliminarCasilla').disabled = true;
		byId('btnEditarCasilla').disabled = true;");
	} else {
		$objResponse->script("
		byId('btnEliminarCasilla').disabled = false;
		byId('btnEditarCasilla').disabled = false;");
	}
	
	return $objResponse;
}

function importarAlmacen($valForm, $valFormListaAlmacen) {
	$objResponse = new xajaxResponse();
	
	$inputFileType = 'Excel5';
	//$inputFileType = 'Excel2007';
	//$inputFileType = 'Excel2003XML';
	//$inputFileType = 'OOCalc';
	//$inputFileType = 'Gnumeric';
	$inputFileName = 'reportes/tmp/'.$valForm['hddUrlArchivo'];
	
	$phpExcel = new PHPExcel_Reader_Excel2007();
	$archivoExcel = $phpExcel->load($inputFileName);
	
	$archivoExcel->setActiveSheetIndex(0);
	$i = 1;
	while ($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue() != '') {
		if ($itemExcel == true) {
			$arrayAlmacenDetalle[0] = $archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue();
			$arrayAlmacenDetalle[1] = $archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue();
			$arrayAlmacenDetalle[2] = $archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue();
			$arrayAlmacenDetalle[3] = $archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue();
			$arrayAlmacenDetalle[4] = $archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue();
			$arrayAlmacenDetalle[5] = $archivoExcel->getActiveSheet()->getCell('F'.$i)->getValue();
		
			$arrayAlmacen[] = $arrayAlmacenDetalle;
		}
		
		if ($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue() == "Almacén") {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	if (isset($arrayAlmacen)) {
		mysql_query("START TRANSACTION;");
		
		$idEmpresa = $valForm['txtIdEmpresaImportarAlmacen'];
			
		foreach ($arrayAlmacen as $indice => $valor) {
			$nombreAlmacen = (strlen($arrayAlmacen[$indice][0]) > 0) ? $arrayAlmacen[$indice][0] : "Sin Nombre";
			$nombreCalle = (strlen($arrayAlmacen[$indice][1]) > 0) ? $arrayAlmacen[$indice][1] : "[]";
			$nombreEstante = (strlen($arrayAlmacen[$indice][2]) > 0) ? $arrayAlmacen[$indice][2] : "[]";
			$nombreTramo = (strlen($arrayAlmacen[$indice][3]) > 0) ? $arrayAlmacen[$indice][3] : "[]";
			$nombreCasilla = (strlen($arrayAlmacen[$indice][4]) > 0) ? $arrayAlmacen[$indice][4] : "[]";
			
			// BUSCA SI EXISTE EL ALMACEN PARA LA EMPRESA
			$queryAlmacen = sprintf("SELECT * FROM ga_almacenes
			WHERE id_empresa = %s
				AND descripcion = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($nombreAlmacen, "text"));
			$rsAlmacen = mysql_query($queryAlmacen);
			if (!$rsAlmacen) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$totalRowsAlmacen = mysql_num_rows($rsAlmacen);
			$rowAlmacen = mysql_fetch_array($rsAlmacen);
			
			$idAlmacen = $rowAlmacen['id_almacen'];
			if ($totalRowsAlmacen == 0) {
				$insertSQL = sprintf("INSERT INTO ga_almacenes (id_empresa, descripcion, estatus)
				VALUE (%s, %s, %s);",
					valTpDato($idEmpresa, "int"),
					valTpDato($nombreAlmacen, "text"),
					valTpDato(0, "int")); // 0 = Inactivo, 1 = Activo
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL);
				$idAlmacen = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			// BUSCA SI EXISTE LA CALLE
			$queryCalle = sprintf("SELECT * FROM ga_calles
			WHERE id_almacen = %s
				AND descripcion_calle = %s;",
				valTpDato($idAlmacen, "int"),
				valTpDato($nombreCalle, "text"));
			$rsCalle = mysql_query($queryCalle);
			if (!$rsCalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$totalRowsCalle = mysql_num_rows($rsCalle);
			$rowCalle = mysql_fetch_array($rsCalle);
			
			$idCalle = $rowCalle['id_calle'];
			if ($totalRowsCalle == 0) {
				$insertSQL = sprintf("INSERT INTO ga_calles (id_almacen, descripcion_calle)
				VALUE (%s, %s);",
					valTpDato($idAlmacen, "int"),
					valTpDato($nombreCalle, "text"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL);
				$idCalle = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			// BUSCA SI EXISTE EL ESTANTE
			$queryEstante = sprintf("SELECT * FROM ga_estantes
			WHERE id_calle = %s
				AND descripcion_estante = %s;",
				valTpDato($idCalle, "int"),
				valTpDato($nombreEstante, "text"));
			$rsEstante = mysql_query($queryEstante);
			if (!$rsEstante) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$totalRowsEstante = mysql_num_rows($rsEstante);
			$rowEstante = mysql_fetch_array($rsEstante);
			
			$idEstante = $rowEstante['id_estante'];
			if ($totalRowsEstante == 0) {
				$insertSQL = sprintf("INSERT INTO ga_estantes (id_calle, descripcion_estante)
				VALUE (%s, %s);",
					valTpDato($idCalle, "int"),
					valTpDato($nombreEstante, "text"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL);
				$idEstante = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			// BUSCA SI EXISTE EL TRAMO
			$queryTramo = sprintf("SELECT * FROM ga_tramos
			WHERE id_estante = %s
				AND descripcion_tramo = %s;",
				valTpDato($idEstante, "int"),
				valTpDato($nombreTramo, "text"));
			$rsTramo = mysql_query($queryTramo);
			if (!$rsTramo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$totalRowsTramo = mysql_num_rows($rsTramo);
			$rowTramo = mysql_fetch_array($rsTramo);
			
			$idTramo = $rowTramo['id_tramo'];
			if ($totalRowsTramo == 0) {
				$insertSQL = sprintf("INSERT INTO ga_tramos (id_estante, descripcion_tramo)
				VALUE (%s, %s);",
					valTpDato($idEstante, "int"),
					valTpDato($nombreTramo, "text"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL);
				$idTramo = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			// BUSCA SI EXISTE EL CASILLA
			$queryCasilla = sprintf("SELECT * FROM ga_casillas
			WHERE id_tramo = %s
				AND descripcion_casilla = %s;",
				valTpDato($idTramo, "int"),
				valTpDato($nombreCasilla, "text"));
			$rsCasilla = mysql_query($queryCasilla);
			if (!$rsCasilla) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$totalRowsCasilla = mysql_num_rows($rsCasilla);
			$rowCasilla = mysql_fetch_array($rsCasilla);
			
			$idCasilla = $rowCasilla['id_casilla'];
			if ($totalRowsCasilla == 0) {
				$insertSQL = sprintf("INSERT INTO ga_casillas (id_tramo, descripcion_casilla)
				VALUE (%s, %s);",
					valTpDato($idTramo, "int"),
					valTpDato($nombreCasilla, "text"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL);
				$idCasilla = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Almacén Importado con Éxito");
		
		errorImportarAlmacen($objResponse);
		
		$objResponse->script("
		byId('btnCancelarImportarAlmacen').click();");
		
		$objResponse->loadCommands(listadoAlmacenes(
			$valFormListaAlmacen['pageNum'],
			$valFormListaAlmacen['campOrd'],
			$valFormListaAlmacen['tpOrd'],
			$valFormListaAlmacen['valBusq']));
	}
	
	return $objResponse;
}

function listadoAlmacenes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("alm.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		alm.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM ga_almacenes alm
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,1);\"/></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoAlmacenes", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoAlmacenes", "66%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listadoAlmacenes", "10%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Estatus para Venta");
		$htmlTh .= ordenarCampo("xajax_listadoAlmacenes", "10%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Estatus para Compra");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus']) {
			case 0 : $imgEstatusAlmacen = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatusAlmacen = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatusAlmacen = "";
		}
		
		switch ($row['estatus_almacen_venta']) {
			case 0 : $imgEstatusAlmacenVenta = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatusAlmacenVenta = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatusAlmacenVenta = "";
		}
		
		switch ($row['estatus_almacen_compra']) {
			case 0 : $imgEstatusAlmacenCompra = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatusAlmacenCompra = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatusAlmacenCompra = "";
		}	
			
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"></td>",
				$row['id_almacen']);
			$htmlTb .= "<td>".$imgEstatusAlmacen."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgEstatusAlmacenVenta."</td>";
					$htmlTb .= "<td width=\"100%\">".(($row['estatus_almacen_venta'] == 1) ? "Activo" : "Inactivo")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".$imgEstatusAlmacenCompra."</td>";
					$htmlTb .= "<td width=\"100%\">".(($row['estatus_almacen_compra'] == 1) ? "Activo" : "Inactivo")."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_cargarAlmacen(this.id,'%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_almacen']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar Almacen\"/></a>",
					$row['id_almacen']);
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAlmacenes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAlmacenes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoAlmacenes(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAlmacenes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAlmacenes(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaAlmacen","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"asignarCalle");
$xajax->register(XAJAX_FUNCTION,"asignarCasilla");
$xajax->register(XAJAX_FUNCTION,"asignarEstante");
$xajax->register(XAJAX_FUNCTION,"asignarTramo");
$xajax->register(XAJAX_FUNCTION,"buscarAlmacen");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarAlmacen");
$xajax->register(XAJAX_FUNCTION,"cargaLstCalle");
$xajax->register(XAJAX_FUNCTION,"cargaLstCasilla");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstante");
$xajax->register(XAJAX_FUNCTION,"cargaLstTramo");
$xajax->register(XAJAX_FUNCTION,"eliminarAlmacen");
$xajax->register(XAJAX_FUNCTION,"eliminarAlmacenBloque");
$xajax->register(XAJAX_FUNCTION,"eliminarCalle");
$xajax->register(XAJAX_FUNCTION,"eliminarCasilla");
$xajax->register(XAJAX_FUNCTION,"eliminarEstante");
$xajax->register(XAJAX_FUNCTION,"eliminarTramo");
$xajax->register(XAJAX_FUNCTION,"formAlmacen");
$xajax->register(XAJAX_FUNCTION,"formImportarAlmacen");
$xajax->register(XAJAX_FUNCTION,"guardarAlmacen");
$xajax->register(XAJAX_FUNCTION,"guardarUbicacion");
$xajax->register(XAJAX_FUNCTION,"habilitarBotonesCasilla");
$xajax->register(XAJAX_FUNCTION,"importarAlmacen");
$xajax->register(XAJAX_FUNCTION,"listadoAlmacenes");

function errorImportarAlmacen($objResponse) {
	$objResponse->script("
	byId('btnGuardarImportarAlmacen').disabled = '';
	byId('btnCancelarImportarAlmacen').disabled = '';");
}
?>