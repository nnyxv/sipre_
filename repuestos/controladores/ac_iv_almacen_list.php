<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function asignarCalle($idCalle, $estatusUbicacion = false) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		calle.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
			INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
			INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
		WHERE estante.id_calle = calle.id_calle
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_calles calle
	WHERE calle.id_calle = %s;",
		valTpDato($idCalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddIdCalle","value",$idCalle);
	$objResponse->assign("txtCalle","value",utf8_encode($row['descripcion_calle']));
	
	if ($estatusUbicacion == true) {
		$objResponse->call("selectedOption","lstEstatusUbicacion",$row['estatus']);
		
		if ($row['cantidad_ocupada'] > 0 && $row['estatus'] == 1) {
			$objResponse->script("
			byId('lstEstatusUbicacion').onchange = function () {
				selectedOption(this.id,'".$row['estatus']."');
			}");
		} else {
			$objResponse->script(sprintf("
			byId('lstEstatusUbicacion').onchange = function () {}
			byId('lstEstatusUbicacion').className = 'inputHabilitado';"));
		}
	} else {
		$objResponse->call("selectedOption","lstEstatusUbicacion",1);
	}
	
	return $objResponse;
}

function asignarCasilla($idCasilla, $estatusUbicacion = false) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		casilla.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
		WHERE art_alm.id_casilla = casilla.id_casilla
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_casillas casilla
	WHERE casilla.id_casilla = %s;",
		valTpDato($idCasilla, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddIdCasilla","value",$idCasilla);
	$objResponse->assign("txtCasilla","value",utf8_encode($row['descripcion_casilla']));
	
	if ($estatusUbicacion == true) {
		$objResponse->call("selectedOption","lstEstatusUbicacion",$row['estatus']);
		
		if ($row['cantidad_ocupada'] > 0 && $row['estatus'] == 1) {
			$objResponse->script("
			byId('lstEstatusUbicacion').onchange = function () {
				selectedOption(this.id,'".$row['estatus']."');
			}");
		} else {
			$objResponse->script(sprintf("
			byId('lstEstatusUbicacion').onchange = function () {}
			byId('lstEstatusUbicacion').className = 'inputHabilitado';"));
		}
	} else {
		$objResponse->call("selectedOption","lstEstatusUbicacion",1);
	}
	
	return $objResponse;
}

function asignarEstante($idEstante, $estatusUbicacion = false) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		estante.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
			INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
		WHERE tramo.id_estante = estante.id_estante
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_estantes estante
	WHERE estante.id_estante = %s;",
		valTpDato($idEstante, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddIdEstante","value",$idEstante);
	$objResponse->assign("txtEstante","value",utf8_encode($row['descripcion_estante']));
	
	if ($estatusUbicacion == true) {
		$objResponse->call("selectedOption","lstEstatusUbicacion",$row['estatus']);
		
		if ($row['cantidad_ocupada'] > 0 && $row['estatus'] == 1) {
			$objResponse->script("
			byId('lstEstatusUbicacion').onchange = function () {
				selectedOption(this.id,'".$row['estatus']."');
			}");
		} else {
			$objResponse->script(sprintf("
			byId('lstEstatusUbicacion').onchange = function () {}
			byId('lstEstatusUbicacion').className = 'inputHabilitado';"));
		}
	} else {
		$objResponse->call("selectedOption","lstEstatusUbicacion",1);
	}
	
	return $objResponse;
}

function asignarTramo($idTramo, $estatusUbicacion = false) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		tramo.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
		WHERE casilla.id_tramo = tramo.id_tramo
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_tramos tramo
	WHERE tramo.id_tramo = %s;",
		valTpDato($idTramo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddIdTramo","value",$idTramo);
	$objResponse->assign("txtTramo","value",utf8_encode($row['descripcion_tramo']));
	
	if ($estatusUbicacion == true) {
		$objResponse->call("selectedOption","lstEstatusUbicacion",$row['estatus']);
		
		if ($row['cantidad_ocupada'] > 0 && $row['estatus'] == 1) {
			$objResponse->script("
			byId('lstEstatusUbicacion').onchange = function () {
				selectedOption(this.id,'".$row['estatus']."');
			}");
		} else {
			$objResponse->script(sprintf("
			byId('lstEstatusUbicacion').onchange = function () {}
			byId('lstEstatusUbicacion').className = 'inputHabilitado';"));
		}
	} else {
		$objResponse->call("selectedOption","lstEstatusUbicacion",1);
	}
	
	return $objResponse;
}

function buscarAlmacen($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaAlmacen(0, "", "", $valBusq));
	
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

function cargaLstCalle($idAlmacen, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		calle.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
			INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
			INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
		WHERE estante.id_calle = calle.id_calle
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_calles calle
	WHERE calle.id_almacen = %s
	ORDER BY calle.descripcion_calle;",
		valTpDato($idAlmacen, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstCalle\" name=\"lstCalle\" onChange=\"xajax_cargaLstEstante(this.value);\" class=\"inputHabilitado\">";
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($row['id_calle'] == $selId) ? "selected=\"selected\"" : "";
		
		$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
		
		$html .= "<option value=\"".$row['id_calle']."\" class=\"".$classUbic."\" ".$selected.">".utf8_encode($row['descripcion_calle'].$ocupada)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCalle","innerHTML",$html);
	
	$objResponse->loadCommands(cargaLstEstante($selId));
	
	return $objResponse;
}

function cargaLstCasilla($idTramo, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		casilla.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
		WHERE art_alm.id_casilla = casilla.id_casilla
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_casillas casilla
	WHERE casilla.id_tramo = %s
	ORDER BY casilla.descripcion_casilla;",
		valTpDato($idTramo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstCasilla\" name=\"lstCasilla\" onchange=\"xajax_verificarUbicacion(xajax.getFormValues('frmAlmacen'));\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($row['id_casilla'] == $selId) ? "selected=\"selected\"" : "";
		
		$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
		
		$html .= "<option value=\"".$row['id_casilla']."\" class=\"".$classUbic."\" ".$selected.">".utf8_encode($row['descripcion_casilla'].$ocupada)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCasilla","innerHTML",$html);
	
	$objResponse->script("xajax_verificarUbicacion(xajax.getFormValues('frmAlmacen'));");
	
	return $objResponse;
}

function cargaLstEstante($idCalle, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		estante.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
			INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
		WHERE tramo.id_estante = estante.id_estante
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_estantes estante
	WHERE estante.id_calle = %s
	ORDER BY estante.descripcion_estante;",
		valTpDato($idCalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstEstante\" name=\"lstEstante\" onChange=\"xajax_cargaLstTramo(this.value);\" class=\"inputHabilitado\">";
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($row['id_estante'] == $selId) ? "selected=\"selected\"" : "";
		
		$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
		
		$html .= "<option value=\"".$row['id_estante']."\" class=\"".$classUbic."\" ".$selected.">".utf8_encode($row['descripcion_estante'].$ocupada)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstante","innerHTML",$html);

	$objResponse->loadCommands(cargaLstTramo($selId));
	
	$objResponse->script("xajax_verificarUbicacion(xajax.getFormValues('frmAlmacen'));");
	
	return $objResponse;
}

function cargaLstTramo($idEstante, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT
		tramo.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
		WHERE casilla.id_tramo = tramo.id_tramo
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_tramos tramo
	WHERE tramo.id_estante = %s
	ORDER BY tramo.descripcion_tramo;",
		valTpDato($idEstante, "int"));	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTramo\" name=\"lstTramo\" onchange=\"xajax_cargaLstCasilla(this.value);\" class=\"inputHabilitado\">";
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($row['id_tramo'] == $selId) ? "selected=\"selected\"" : "";
		
		$classUbic = ($row['cantidad_ocupada'] > 0) ? "divMsjErrorSinBorde" : "divMsjInfoSinBorde";
		$classUbic = ($row['estatus'] == 0) ? "divMsjInfo3SinBorde" : $classUbic;
		$ocupada = ($row['cantidad_ocupada'] > 0) ? "*" : "";
		
		$html .= "<option value=\"".$row['id_tramo']."\" class=\"".$classUbic."\" ".$selected.">".utf8_encode($row['descripcion_tramo'].$ocupada)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTramo","innerHTML",$html);
		
	$objResponse->loadCommands(cargaLstCasilla($selId));
	
	$objResponse->script("xajax_verificarUbicacion(xajax.getFormValues('frmAlmacen'));");
	
	return $objResponse;
}

function eliminarAlmacen($idAlmacen, $frmListaAlmacen) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_articulo_list","eliminar")) { return $objResponse; }
	
	if (isset($idAlmacen)) {
		$queryExistencia = sprintf("SELECT existencia
		FROM vw_iv_articulos_almacen vw_iv_art_alm
			INNER JOIN vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic ON (vw_iv_art_alm.id_casilla = vw_iv_art_emp_ubic.id_casilla)
		WHERE vw_iv_art_alm.id_almacen = %s
			AND existencia > 0;",
				valTpDato($idAlmacen, "int"));
		$rsExistencia = mysql_query($queryExistencia);
		if (!$rsExistencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsExistencia = mysql_num_rows($rsExistencia);

		if ($totalRowsExistencia == 0){
			mysql_query("START TRANSACTION;");
		
			$deleteSQL = sprintf("DELETE FROM iv_almacenes WHERE iv_almacenes.id_almacen = %s",
				valTpDato($idAlmacen, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			mysql_query("COMMIT;");
			
			$objResponse->alert("Eliminacion realizada con éxito");
			
			$objResponse->loadCommands(listaAlmacen(
				$frmListaAlmacen['pageNum'],
				$frmListaAlmacen['campOrd'],
				$frmListaAlmacen['tpOrd'],
				$frmListaAlmacen['valBusq']));
		} else {
			$objResponse->alert("Este registro no puede ser eliminado debido a que tiene otros registros dependientes");
		}
	}
	
	return $objResponse;
}

function eliminarAlmacenBloque($frmListaAlmacen) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_almacen_list","eliminar")) { return $objResponse; }
		
	mysql_query("START TRANSACTION;");
			
	foreach ($frmListaAlmacen['cbxItm'] as $indiceItm => $valorItm) {
		$queryExistencia = sprintf("SELECT vw_iv_art_alm.descripcion, existencia
		FROM vw_iv_articulos_almacen vw_iv_art_alm
			INNER JOIN vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic ON (vw_iv_art_alm.id_casilla = vw_iv_art_emp_ubic.id_casilla)
		WHERE vw_iv_art_alm.id_almacen = %s
			AND existencia > 0;",
			valTpDato($valorItm, "int"));
		$rsExistencia = mysql_query($queryExistencia);
		if (!$rsExistencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowExistencia = mysql_fetch_assoc($rsExistencia);
		$totalRowsExistencia = mysql_num_rows($rsExistencia);
		
		if ($totalRowsExistencia == 0) {
			$deleteSQL = sprintf("DELETE FROM iv_almacenes WHERE id_almacen = %s;",
				valTpDato($valorItm, "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		} else {
			$objResponse->alert(sprintf("El registro (%s) no puede ser eliminado debido a que tiene otros registros dependientes",
				$rowExistencia['descripcion']));
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Eliminacion realizada con éxito");

	$objResponse->loadCommands(listaAlmacen(
		$frmListaAlmacen['pageNum'],
		$frmListaAlmacen['campOrd'],
		$frmListaAlmacen['tpOrd'],
		$frmListaAlmacen['valBusq']));
	
	return $objResponse;
}

function eliminarCalle($idCalle) {
	$objResponse = new xajaxResponse();
	
	$queryExistencia = sprintf("SELECT existencia
	FROM vw_iv_articulos_almacen vw_iv_art_alm
		INNER JOIN vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic ON (vw_iv_art_alm.id_casilla = vw_iv_art_emp_ubic.id_casilla)
	WHERE vw_iv_art_alm.id_calle = %s
		AND existencia > 0",
		valTpDato($idCalle, "int"));
	$rsExistencia = mysql_query($queryExistencia);
	if (!$rsExistencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$existencia = mysql_num_rows($rsExistencia);
	
	if ($existencia > 0) {
		$objResponse->alert("No se puede eliminar una calle con Artículos");
	} else {
		mysql_query("START TRANSACTION;");
		
		$queryAlmacen = "SELECT id_almacen FROM iv_calles WHERE id_calle = '".$idCalle."'";
		$rsAlmacen = mysql_query($queryAlmacen);
		if (!$rsAlmacen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowAlmacen = mysql_fetch_array($rsAlmacen);
		
		$query = "DELETE FROM iv_calles WHERE iv_calles.id_calle = '".$idCalle."'";
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Calle Eliminada Exitosamente");
		
		$objResponse->loadCommands(cargaLstCalle($rowAlmacen['id_almacen']));
	}
	
	return $objResponse;
}

function eliminarCasilla($idCasilla) {
	$objResponse = new xajaxResponse();
	
	$queryExistencia = sprintf("SELECT existencia
	FROM vw_iv_articulos_almacen vw_iv_art_alm
		INNER JOIN vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic ON (vw_iv_art_alm.id_casilla = vw_iv_art_emp_ubic.id_casilla)
	WHERE vw_iv_art_alm.id_casilla = %s
		AND existencia > 0",
		valTpDato($idCasilla, "int"));
	$rsExistencia = mysql_query($queryExistencia);
	if (!$rsExistencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$existencia = mysql_num_rows($rsExistencia);
	
	if ($existencia > 0) {
		$objResponse->alert("No se puede eliminar un estante con Artículos");
	} else {
		mysql_query("START TRANSACTION;");
		
		$queryTramo = sprintf("SELECT id_tramo FROM iv_casillas WHERE id_casilla = %s;",
			valTpDato($idCasilla, "int"));
		$rsTramo = mysql_query($queryTramo);
		if (!$rsTramo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowTramo = mysql_fetch_array($rsTramo);
		
		$deleteSQL = sprintf("DELETE FROM iv_casillas WHERE id_casilla = %s;",
			valTpDato($idCasilla, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) {
			if (mysql_errno() == 1451) {
				$updateSQL = sprintf("UPDATE iv_casillas SET
					estatus = 0
				WHERE id_casilla = %s;",
					valTpDato($idCasilla, "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Casilla Eliminada Exitosamente");
		
		$objResponse->loadCommands(cargaLstCasilla($rowTramo['id_tramo']));
	}
	
	return $objResponse;
}

function eliminarEstante($idEstante) {
	$objResponse = new xajaxResponse();
	
	$queryExistencia = sprintf("SELECT existencia
	FROM vw_iv_articulos_almacen vw_iv_art_alm
		INNER JOIN vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic ON (vw_iv_art_alm.id_casilla = vw_iv_art_emp_ubic.id_casilla)
	WHERE vw_iv_art_alm.id_estante = %s
		AND existencia > 0",
		valTpDato($idEstante, "int"));
	$rsExistencia = mysql_query($queryExistencia);
	if (!$rsExistencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$existencia = mysql_num_rows($rsExistencia);
	
	if ($existencia > 0) {
		$objResponse->alert("No se puede eliminar un estante con Artículos");
	} else {
		mysql_query("START TRANSACTION;");
		
		$queryCalle = "SELECT id_calle FROM iv_estantes WHERE id_estante = '".$idEstante."'";
		$rsCalle = mysql_query($queryCalle);
		if (!$rsCalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowCalle = mysql_fetch_array($rsCalle);
		
		$query = "DELETE FROM iv_estantes WHERE iv_estantes.id_estante = '".$idEstante."'";
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Estante Eliminado Exitosamente");
		
		$objResponse->loadCommands(cargaLstEstante($rowCalle['id_calle']));
	}
	
	return $objResponse;
}

function eliminarTramo($idTramo) {
	$objResponse = new xajaxResponse();
	
	$queryExistencia = sprintf("SELECT existencia
	FROM vw_iv_articulos_almacen vw_iv_art_alm
		INNER JOIN vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic ON (vw_iv_art_alm.id_casilla = vw_iv_art_emp_ubic.id_casilla)
	WHERE vw_iv_art_alm.id_tramo = %s
		AND existencia > 0",
		valTpDato($idTramo, "int"));
	$rsExistencia = mysql_query($queryExistencia);
	if (!$rsExistencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$existencia = mysql_num_rows($rsExistencia);
	
	if ($existencia > 0) {
		$objResponse->alert("No se puede eliminar un tramo con Artículos");
	} else {
		mysql_query("START TRANSACTION;");
		
		$queryEstante = "SELECT id_estante FROM iv_tramos WHERE id_tramo = '".$idTramo."'";
		$rsEstante = mysql_query($queryEstante);
		if (!$rsEstante) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowEstante = mysql_fetch_array($rsEstante);
		
		$deleteSQL = sprintf("DELETE FROM iv_tramos WHERE id_tramo = %s;",
			valTpDato($idTramo, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) {
			if (mysql_errno() == 1451) {
				$updateSQL = sprintf("UPDATE iv_casillas SET
					estatus = 0
				WHERE id_tramo = %s;",
					valTpDato($idTramo, "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				$updateSQL = sprintf("UPDATE iv_tramos SET
					estatus = 0
				WHERE id_tramo = %s;",
					valTpDato($idTramo, "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			} else {
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			}
		}
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Tramo Eliminado Exitosamente");
		
		$objResponse->loadCommands(cargaLstTramo($rowEstante['id_estante']));
	}
	
	return $objResponse;
}

function formAlmacen($idAlmacen) {
	$objResponse = new xajaxResponse();
	
	if ($idAlmacen > 0) {
		if (!xvalidaAcceso($objResponse,"iv_almacen_list","editar")) { return $objResponse; }
		
		// BUSCA LOS DATOS DEL ALMACEN
		$queryAlmacen = sprintf("SELECT * FROM iv_almacenes WHERE id_almacen = %s;",
			valTpDato($idAlmacen, "int"));
		$rsAlmacen = mysql_query($queryAlmacen);
		if (!$rsAlmacen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowAlmacen = mysql_fetch_assoc($rsAlmacen);
		
		$objResponse->assign("hddIdAlmacen","value",$idAlmacen);
		$objResponse->loadCommands(asignarEmpresaUsuario($rowAlmacen['id_empresa'], "Empresa", "ListaEmpresa", "", "false"));
		$objResponse->assign("txtDesAlmacen","value",$rowAlmacen['descripcion']);
		$objResponse->call("selectedOption","lstEstatus",$rowAlmacen['estatus']);
		$objResponse->call("selectedOption","lstEstatusVenta",$rowAlmacen['estatus_almacen_venta']);
		$objResponse->call("selectedOption","lstEstatusCompra",$rowAlmacen['estatus_almacen_compra']);
		$objResponse->loadCommands(cargaLstCalle($idAlmacen));
	} else {
		if (!xvalidaAcceso($objResponse,"iv_almacen_list","insertar")) { return $objResponse; }
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "", "false"));
	}
	
	return $objResponse;
}

function formImportarAlmacen() {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_almacen_list","insertar")) { return $objResponse; }
	
	$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "EmpresaImportarAlmacen", "ListaEmpresa", "", "false"));
	
	return $objResponse;
}

function guardarAlmacen($frmAlmacen, $frmListaAlmacen) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idAlmacen = $frmAlmacen['hddIdAlmacen'];
	$lstEstatusVenta = ($frmAlmacen['lstEstatus'] == 1) ? $frmAlmacen['lstEstatusVenta'] : 0;
	$lstEstatusCompra = ($frmAlmacen['lstEstatus'] == 1) ? $frmAlmacen['lstEstatusCompra'] : 0;
	
	if ($idAlmacen > 0) {
		if (!xvalidaAcceso($objResponse,"iv_almacen_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE iv_almacenes SET
			descripcion = %s,
			estatus = %s,
			estatus_almacen_venta = %s,
			estatus_almacen_compra = %s
		WHERE id_almacen = %s;",
			valTpDato($frmAlmacen['txtDesAlmacen'], "text"),
			valTpDato($frmAlmacen['lstEstatus'], "boolean"),
			valTpDato($lstEstatusVenta, "boolean"),
			valTpDato($lstEstatusCompra, "boolean"),
			valTpDato($frmAlmacen['hddIdAlmacen'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_almacen_list","insertar")) { return $objResponse; }
		
		$insertSQL = sprintf("INSERT INTO iv_almacenes (id_empresa , descripcion, estatus, estatus_almacen_venta, estatus_almacen_compra)
		VALUE (%s, %s, %s, %s, %s);",
			valTpDato($frmAlmacen['txtIdEmpresa'], "int"),
			valTpDato($frmAlmacen['txtDesAlmacen'], "text"),
			valTpDato($frmAlmacen['lstEstatus'], "boolean"),
			valTpDato($lstEstatusVenta, "boolean"),
			valTpDato($lstEstatusCompra, "boolean"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Almacén Guardado con Éxito");
	
	$objResponse->script("byId('imgCerrarDivFlotante1').click();");
	
	$objResponse->loadCommands(listaAlmacen(
		$frmListaAlmacen['pageNum'],
		$frmListaAlmacen['campOrd'],
		$frmListaAlmacen['tpOrd'],
		$frmListaAlmacen['valBusq']));
		
	return $objResponse;
}

function guardarUbicacion($frmUbicacionAlmacen, $frmAlmacen) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($frmUbicacionAlmacen['hddTipo'] == "Calle") {
		$idCalle = $frmUbicacionAlmacen['hddIdCalle'];
		
		if ($idCalle > 0) {
			$updateSQL = sprintf("UPDATE iv_calles SET
				descripcion_calle = %s,
				estatus = %s
			WHERE id_calle = %s;",
				valTpDato($frmUbicacionAlmacen['txtCalle'], "text"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"),
				valTpDato($idCalle, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} else {
			$insertSQL = sprintf("INSERT INTO iv_calles (id_almacen, descripcion_calle, estatus) VALUE (%s, %s, %s);",
				valTpDato($frmAlmacen['hddIdAlmacen'], "int"),
				valTpDato($frmUbicacionAlmacen['txtCalle'], "text"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idCalle = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO iv_estantes (id_calle, descripcion_estante, estatus) VALUE (%s, '[]', %s);",
				valTpDato($idCalle, "int"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idEstante = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO iv_tramos (id_estante, descripcion_tramo, estatus) VALUE (%s, '[]', %s);",
				valTpDato($idEstante, "int"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idTramo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO iv_casillas (id_tramo, descripcion_casilla, estatus) VALUE (%s, '[]', %s);",
				valTpDato($idTramo, "int"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idCasilla = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
	} else if ($frmUbicacionAlmacen['hddTipo'] == "Estante") {
		$idEstante = $frmUbicacionAlmacen['hddIdEstante'];
		
		if ($idEstante > 0) {
			$updateSQL = sprintf("UPDATE iv_estantes SET
				descripcion_estante = %s,
				estatus = %s
			WHERE id_estante = %s;",
				valTpDato($frmUbicacionAlmacen['txtEstante'], "text"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"),
				valTpDato($idEstante, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} else {
			// INSERTA EL ESTANTE
			$insertSQL = sprintf("INSERT INTO iv_estantes (id_calle, descripcion_estante, estatus) VALUE (%s, %s, %s);",
				valTpDato($frmUbicacionAlmacen['hddIdCalle'], "int"),
				valTpDato($frmUbicacionAlmacen['txtEstante'], "text"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idEstante = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA EL TRAMO
			$insertSQL = sprintf("INSERT INTO iv_tramos (id_estante, descripcion_tramo, estatus) VALUE (%s, '[]', %s);",
				valTpDato($idEstante, "int"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idTramo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA LA CASILLA
			$insertSQL = sprintf("INSERT INTO iv_casillas (id_tramo, descripcion_casilla, estatus) VALUE (%s, '[]', %s);",
				valTpDato($idTramo, "int"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idCasilla = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
	} else if ($frmUbicacionAlmacen['hddTipo'] == "Tramo") {
		$idTramo = $frmUbicacionAlmacen['hddIdTramo'];
		
		if ($idTramo > 0) {
			// ACTUALIZA EL TRAMO
			$updateSQL = sprintf("UPDATE iv_tramos SET
				descripcion_tramo = %s,
				estatus = %s
			WHERE id_tramo = %s",
				valTpDato($frmUbicacionAlmacen['txtTramo'], "text"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"),
				valTpDato($idTramo, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} else {
			// INSERTA EL TRAMO
			$insertSQL = sprintf("INSERT INTO iv_tramos (id_estante, descripcion_tramo, estatus) VALUE (%s, %s, %s);",
				valTpDato($frmUbicacionAlmacen['hddIdEstante'], "int"),
				valTpDato($frmUbicacionAlmacen['txtTramo'], "text"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idTramo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			// INSERTA LA CASILLA
			$insertSQL = sprintf("INSERT INTO iv_casillas (id_tramo, descripcion_casilla, estatus) VALUE (%s, '[]', %s);",
				valTpDato($idTramo, "int"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idCasilla = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
	} else {
		$idCasilla = $frmUbicacionAlmacen['hddIdCasilla'];
		
		if ($idCasilla > 0) {
			$updateSQL = sprintf("UPDATE iv_casillas SET
				descripcion_casilla = %s,
				estatus = %s
			WHERE id_casilla = %s;",
				valTpDato($frmUbicacionAlmacen['txtCasilla'], "text"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"),
				valTpDato($idCasilla, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		} else {
			// INSERTA LA CASILLA
			$insertSQL = sprintf("INSERT INTO iv_casillas (id_tramo, descripcion_casilla, estatus) VALUE (%s, %s, %s);",
				valTpDato($frmUbicacionAlmacen['hddIdTramo'], "int"),
				valTpDato($frmUbicacionAlmacen['txtCasilla'], "text"),
				valTpDato($frmUbicacionAlmacen['lstEstatusUbicacion'], "boolean"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idCasilla = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	
	// BUSCA LAS CALLES DEL ALMACEN
	$queryCalle = sprintf("SELECT * FROM iv_calles calle
	WHERE calle.id_almacen IN (%s);",
		valTpDato($frmAlmacen['hddIdAlmacen'], "int"));
	$rsCalle = mysql_query($queryCalle);
	if (!$rsCalle) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowCalle = mysql_fetch_assoc($rsCalle)) {
		$estatusCalle = $rowCalle['estatus'];
		
		if ($estatusCalle == 0) {
			// LE COLOCA EL ESTATUS DE LA CALLE A SUS ESTANTES
			$updateSQL = sprintf("UPDATE iv_estantes SET
				estatus = %s
			WHERE id_calle IN (%s)
				AND descripcion_estante NOT LIKE '[]';",
				valTpDato($estatusCalle, "boolean"),
				valTpDato($rowCalle['id_calle'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
		
		// VERIFICA SI LA CALLE TIENE ESTANTES ACTIVOS
		$query = sprintf("SELECT * FROM iv_estantes estante
		WHERE estante.id_calle IN (%s)
			AND estante.descripcion_estante NOT LIKE '[]'
			AND estante.estatus = 1;",
			valTpDato($rowCalle['id_calle'], "int"));
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
		$row = mysql_fetch_assoc($rs);
		
		$estatusEstante = ($totalRows == 0 && $estatusCalle == 1) ? 1 : 0;
		
		// DEPENDIENDO SI EXISTEN ESTANTES EN LA CALLE O NO MODIFICA LO QUE NO SEA ESTANTE
		$updateSQL = sprintf("UPDATE iv_estantes SET
			estatus = %s
		WHERE id_calle IN (%s)
			AND descripcion_estante LIKE '[]';",
			valTpDato($estatusEstante, "boolean"),
			valTpDato($rowCalle['id_calle'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		
		// BUSCA LAS ESTANTES DEL CALLE
		$queryEstante = sprintf("SELECT * FROM iv_estantes estante
		WHERE estante.id_calle IN (%s);",
			valTpDato($rowCalle['id_calle'], "int"));
		$rsEstante = mysql_query($queryEstante);
		if (!$rsEstante) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($rowEstante = mysql_fetch_assoc($rsEstante)) {
			$estatusEstante = $rowEstante['estatus'];
			
			if ($estatusEstante == 0) {
				// LE COLOCA EL ESTATUS DE LA ESTANTE A SUS TRAMOS
				$updateSQL = sprintf("UPDATE iv_tramos SET
					estatus = %s
				WHERE id_estante IN (%s)
					AND descripcion_tramo NOT LIKE '[]';",
					valTpDato($estatusEstante, "boolean"),
					valTpDato($rowEstante['id_estante'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
			
			// VERIFICA SI LA ESTANTE TIENE TRAMOS ACTIVOS
			$query = sprintf("SELECT * FROM iv_tramos tramo
			WHERE tramo.id_estante IN (%s)
				AND tramo.descripcion_tramo NOT LIKE '[]'
				AND tramo.estatus = 1;",
				valTpDato($rowEstante['id_estante'], "int"));
			$rs = mysql_query($query);
			if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$estatusTramo = ($totalRows == 0 && $estatusEstante == 1) ? 1 : 0;
			
			// DEPENDIENDO SI EXISTEN TRAMOS EN LA ESTANTE O NO MODIFICA LO QUE NO SEA TRAMO
			$updateSQL = sprintf("UPDATE iv_tramos SET
				estatus = %s
			WHERE id_estante IN (%s)
				AND descripcion_tramo LIKE '[]';",
				valTpDato($estatusTramo, "boolean"),
				valTpDato($rowEstante['id_estante'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			
			
			// BUSCA LAS TRAMOS DEL ESTANTE
			$queryTramo = sprintf("SELECT * FROM iv_tramos tramo
			WHERE tramo.id_estante IN (%s);",
				valTpDato($rowEstante['id_estante'], "int"));
			$rsTramo = mysql_query($queryTramo);
			if (!$rsTramo) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			while ($rowTramo = mysql_fetch_assoc($rsTramo)) {
				$estatusTramo = $rowTramo['estatus'];
				
				if ($estatusTramo == 0) {
					// LE COLOCA EL ESTATUS DE LA TRAMO A SUS CASILLAS
					$updateSQL = sprintf("UPDATE iv_casillas SET
						estatus = %s
					WHERE id_tramo IN (%s)
						AND descripcion_casilla NOT LIKE '[]';",
						valTpDato($estatusTramo, "boolean"),
						valTpDato($rowTramo['id_tramo'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
				
				// VERIFICA SI LA TRAMO TIENE CASILLA ACTIVOS
				$query = sprintf("SELECT * FROM iv_casillas casilla
				WHERE casilla.id_tramo IN (%s)
					AND casilla.descripcion_casilla NOT LIKE '[]'
					AND casilla.estatus = 1;",
					valTpDato($rowTramo['id_tramo'], "int"));
				$rs = mysql_query($query);
				if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRows = mysql_num_rows($rs);
				$row = mysql_fetch_assoc($rs);
				
				$estatusCasilla = ($totalRows == 0 && $estatusTramo == 1) ? 1 : 0;
				
				// DEPENDIENDO SI EXISTEN CASILLA EN LA TRAMO O NO MODIFICA LO QUE NO SEA CASILLA
				$updateSQL = sprintf("UPDATE iv_casillas SET
					estatus = %s
				WHERE id_tramo IN (%s)
					AND descripcion_casilla LIKE '[]';",
					valTpDato($estatusCasilla, "boolean"),
					valTpDato($rowTramo['id_tramo'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// VERIFICA SI EN EL TRAMO LO QUE NO SEA CASILLA TIENE ALGUN ARTICULO
				$query = sprintf("SELECT
					casilla.*,
					
					(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
					WHERE art_alm.id_casilla = casilla.id_casilla
						AND art_alm.estatus = 1) AS cantidad_ocupada
				FROM iv_casillas casilla
				WHERE id_tramo IN (%s)
					AND descripcion_casilla LIKE '[]'
					AND estatus = 1;",
					valTpDato($rowTramo['id_tramo'], "int"));
				$rs = mysql_query($query);
				if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRows = mysql_num_rows($rs);
				$row = mysql_fetch_array($rs);
				
				if ($row['cantidad_ocupada'] > 0) {
					if ($rowTramo['id_tramo'] == $frmUbicacionAlmacen['hddIdTramo']) {
						return $objResponse->alert("No puede crear o activar subdivision en esta ubicación debido a que hay artículos existes en la misma");
					}
				}
			}
		}
	}
	
	mysql_query("COMMIT;");
	
	switch ($frmUbicacionAlmacen['hddTipo']) {
		case "Calle"	: $objResponse->loadCommands(cargaLstCalle($frmAlmacen['hddIdAlmacen'], $idCalle)); break;
		case "Estante"	: $objResponse->loadCommands(cargaLstEstante($frmUbicacionAlmacen['hddIdCalle'], $idEstante)); break;
		case "Tramo"	: $objResponse->loadCommands(cargaLstTramo($frmUbicacionAlmacen['hddIdEstante'], $idTramo)); break;
		case "Casilla"	: $objResponse->loadCommands(cargaLstCasilla($frmUbicacionAlmacen['hddIdTramo'], $idCasilla)); break;
	}
	
	$objResponse->alert("Registro Guardado con Éxito");
	
	$objResponse->script("byId('imgCerrarDivFlotante2').click();");
	
	return $objResponse;
}

function importarAlmacen($frmImportarAlmacen, $frmListaAlmacen) {
	$objResponse = new xajaxResponse();
	
	$inputFileType = 'Excel5';
	//$inputFileType = 'Excel2007';
	//$inputFileType = 'Excel2003XML';
	//$inputFileType = 'OOCalc';
	//$inputFileType = 'Gnumeric';
	$inputFileName = 'reportes/tmp/'.$frmImportarAlmacen['hddUrlArchivo'];
	
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
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Almacén"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Almacén"))
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Almacén"))
		|| trim(strtoupper(htmlentities($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Almacén"))
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Almacen"))) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	if (isset($arrayAlmacen)) {
		mysql_query("START TRANSACTION;");
		
		$idEmpresa = $frmImportarAlmacen['txtIdEmpresaImportarAlmacen'];
			
		foreach ($arrayAlmacen as $indice => $valor) {
			$nombreAlmacen = (strlen($arrayAlmacen[$indice][0]) > 0) ? $arrayAlmacen[$indice][0] : "Sin Nombre";
			$nombreCalle = (strlen($arrayAlmacen[$indice][1]) > 0) ? $arrayAlmacen[$indice][1] : "[]";
			$nombreEstante = (strlen($arrayAlmacen[$indice][2]) > 0) ? $arrayAlmacen[$indice][2] : "[]";
			$nombreTramo = (strlen($arrayAlmacen[$indice][3]) > 0) ? $arrayAlmacen[$indice][3] : "[]";
			$nombreCasilla = (strlen($arrayAlmacen[$indice][4]) > 0) ? $arrayAlmacen[$indice][4] : "[]";
			
			// BUSCA SI EXISTE EL ALMACEN PARA LA EMPRESA
			$queryAlmacen = sprintf("SELECT * FROM iv_almacenes
			WHERE id_empresa = %s
				AND descripcion = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($nombreAlmacen, "text"));
			$rsAlmacen = mysql_query($queryAlmacen);
			if (!$rsAlmacen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsAlmacen = mysql_num_rows($rsAlmacen);
			$rowAlmacen = mysql_fetch_array($rsAlmacen);
			
			$idAlmacen = $rowAlmacen['id_almacen'];
			if ($totalRowsAlmacen == 0) {
				$insertSQL = sprintf("INSERT INTO iv_almacenes (id_empresa, descripcion, estatus, estatus_almacen_venta, estatus_almacen_compra)
				VALUE (%s, %s, %s, %s, %s);",
					valTpDato($idEmpresa, "int"),
					valTpDato($nombreAlmacen, "text"),
					valTpDato(0, "boolean"), // 0 = Inactivo, 1 = Activo
					valTpDato(0, "boolean"), // 0 = Inactivo, 1 = Activo
					valTpDato(0, "boolean")); // 0 = Inactivo, 1 = Activo
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL);
				$idAlmacen = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
			}
			
			// BUSCA SI EXISTE LA CALLE
			$queryCalle = sprintf("SELECT * FROM iv_calles
			WHERE id_almacen = %s
				AND descripcion_calle = %s;",
				valTpDato($idAlmacen, "int"),
				valTpDato($nombreCalle, "text"));
			$rsCalle = mysql_query($queryCalle);
			if (!$rsCalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsCalle = mysql_num_rows($rsCalle);
			$rowCalle = mysql_fetch_array($rsCalle);
			
			$idCalle = $rowCalle['id_calle'];
			if ($totalRowsCalle == 0) {
				$insertSQL = sprintf("INSERT INTO iv_calles (id_almacen, descripcion_calle)
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
			$queryEstante = sprintf("SELECT * FROM iv_estantes
			WHERE id_calle = %s
				AND descripcion_estante = %s;",
				valTpDato($idCalle, "int"),
				valTpDato($nombreEstante, "text"));
			$rsEstante = mysql_query($queryEstante);
			if (!$rsEstante) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsEstante = mysql_num_rows($rsEstante);
			$rowEstante = mysql_fetch_array($rsEstante);
			
			$idEstante = $rowEstante['id_estante'];
			if ($totalRowsEstante == 0) {
				$insertSQL = sprintf("INSERT INTO iv_estantes (id_calle, descripcion_estante)
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
			$queryTramo = sprintf("SELECT * FROM iv_tramos
			WHERE id_estante = %s
				AND descripcion_tramo = %s;",
				valTpDato($idEstante, "int"),
				valTpDato($nombreTramo, "text"));
			$rsTramo = mysql_query($queryTramo);
			if (!$rsTramo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsTramo = mysql_num_rows($rsTramo);
			$rowTramo = mysql_fetch_array($rsTramo);
			
			$idTramo = $rowTramo['id_tramo'];
			if ($totalRowsTramo == 0) {
				$insertSQL = sprintf("INSERT INTO iv_tramos (id_estante, descripcion_tramo)
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
			$queryCasilla = sprintf("SELECT * FROM iv_casillas
			WHERE id_tramo = %s
				AND descripcion_casilla = %s;",
				valTpDato($idTramo, "int"),
				valTpDato($nombreCasilla, "text"));
			$rsCasilla = mysql_query($queryCasilla);
			if (!$rsCasilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsCasilla = mysql_num_rows($rsCasilla);
			$rowCasilla = mysql_fetch_array($rsCasilla);
			
			$idCasilla = $rowCasilla['id_casilla'];
			if ($totalRowsCasilla == 0) {
				$insertSQL = sprintf("INSERT INTO iv_casillas (id_tramo, descripcion_casilla, estatus)
				VALUE (%s, %s, 1);",
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
		
		$objResponse->script("
		byId('btnCancelarImportarAlmacen').click();");
		
		$objResponse->loadCommands(listaAlmacen(
			$frmListaAlmacen['pageNum'],
			$frmListaAlmacen['campOrd'],
			$frmListaAlmacen['tpOrd'],
			$frmListaAlmacen['valBusq']));
	}
	
	return $objResponse;
}

function listaAlmacen($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
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
	FROM iv_almacenes alm
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

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,1);\"/></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaAlmacen", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaAlmacen", "66%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaAlmacen", "10%", $pageNum, "estatus_almacen_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Estatus para Venta");
		$htmlTh .= ordenarCampo("xajax_listaAlmacen", "10%", $pageNum, "estatus_almacen_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Estatus para Compra");
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
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblAlmacen', '%s');\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar Almacén\"/></a>",
					$contFila,
					$row['id_almacen']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a onclick=\"validarEliminar('%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Eliminar Almacén\"/></a>",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAlmacen(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAlmacen(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAlmacen(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAlmacen(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAlmacen(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaAlmacen","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function verificarUbicacion($frmAlmacen) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('aAgregarCalle').style.display = '';
	byId('aEditarCalle').style.display = 'none';
	byId('btnEliminarCalle').style.display = 'none';
	
	byId('aAgregarEstante').style.display = 'none';
	byId('aEditarEstante').style.display = 'none';
	byId('btnEliminarEstante').style.display = 'none';
	
	byId('aAgregarTramo').style.display = 'none';
	byId('aEditarTramo').style.display = 'none';
	byId('btnEliminarTramo').style.display = 'none';
	
	byId('aAgregarCasilla').style.display = 'none';
	byId('aEditarCasilla').style.display = 'none';
	byId('btnEliminarCasilla').style.display = 'none';");
	
	// BUSCA LOS DATOS DE LA CALLE
	$query = sprintf("SELECT
		calle.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
			INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
			INNER JOIN iv_estantes estante ON (tramo.id_estante = estante.id_estante)
		WHERE estante.id_calle = calle.id_calle
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_calles calle
	WHERE calle.id_calle = %s;",
		valTpDato($frmAlmacen['lstCalle'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0 && $row['descripcion_calle'] != "[]") {
		$objResponse->script("
		byId('aEditarCalle').style.display = '';
		
		byId('aAgregarEstante').style.display = '';");
		
		if ($row['cantidad_ocupada'] == 0) {
			$objResponse->script("
			byId('btnEliminarCalle').style.display = '';");
		}
	}
	
	// BUSCA LOS DATOS DEL ESTANTE
	$query = sprintf("SELECT
		estante.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
			INNER JOIN iv_tramos tramo ON (casilla.id_tramo = tramo.id_tramo)
		WHERE tramo.id_estante = estante.id_estante
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_estantes estante
	WHERE estante.id_estante = %s;",
		valTpDato($frmAlmacen['lstEstante'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0 && $row['descripcion_estante'] != "[]") {
		$objResponse->script("
		byId('aEditarEstante').style.display = '';
		
		byId('aAgregarTramo').style.display = '';");
		
		if ($row['cantidad_ocupada'] == 0) {
			$objResponse->script("
			byId('btnEliminarEstante').style.display = '';");
		}
	}
	
	// BUSCA LOS DATOS DEL TRAMO
	$query = sprintf("SELECT
		tramo.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada
		FROM iv_articulos_almacen art_alm
			INNER JOIN iv_casillas casilla ON (art_alm.id_casilla = casilla.id_casilla)
		WHERE casilla.id_tramo = tramo.id_tramo
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_tramos tramo
	WHERE tramo.id_tramo = %s;",
		valTpDato($frmAlmacen['lstTramo'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0 && $row['descripcion_tramo'] != "[]") {
		$objResponse->script("
		byId('aEditarTramo').style.display = '';
		
		byId('aAgregarCasilla').style.display = '';");
		
		if ($row['cantidad_ocupada'] == 0) {
			$objResponse->script("
			byId('btnEliminarTramo').style.display = '';");
		}
	}
	
	// BUSCA LOS DATOS DE LA CASILLA
	$query = sprintf("SELECT
		casilla.*,
		
		(SELECT COUNT(art_alm.id_casilla) AS cantidad_ocupada FROM iv_articulos_almacen art_alm
		WHERE art_alm.id_casilla = casilla.id_casilla
			AND art_alm.estatus = 1) AS cantidad_ocupada
	FROM iv_casillas casilla
	WHERE casilla.id_casilla = %s;",
		valTpDato($frmAlmacen['lstCasilla'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0 && $row['descripcion_casilla'] != "[]") {
		$objResponse->script("
		byId('aEditarCasilla').style.display = '';");
		
		if ($row['cantidad_ocupada'] == 0) {
			$objResponse->script("
			byId('btnEliminarCasilla').style.display = '';");
		}
	}
	
	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"asignarCalle");
$xajax->register(XAJAX_FUNCTION,"asignarCasilla");
$xajax->register(XAJAX_FUNCTION,"asignarEstante");
$xajax->register(XAJAX_FUNCTION,"asignarTramo");
$xajax->register(XAJAX_FUNCTION,"buscarAlmacen");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
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
$xajax->register(XAJAX_FUNCTION,"importarAlmacen");
$xajax->register(XAJAX_FUNCTION,"listaAlmacen");
$xajax->register(XAJAX_FUNCTION,"verificarUbicacion");
?>