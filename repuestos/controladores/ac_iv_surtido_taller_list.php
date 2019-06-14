<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function accionBloque($frmListaSolicitudDetalle, $accionDeshacer = false) {
	$objResponse = new xajaxResponse();
	
	// VERIFICA LA CANTIDAD DE ITEMS QUE HAN SIDO MODIFICADOS
	$arrayObj = $frmListaSolicitudDetalle['cbx'];
	$arrayObjItm = $frmListaSolicitudDetalle['cbxItm'];
	
	$idAccionSolicitud = $frmListaSolicitudDetalle['hddIdAccionRealizada'];
	
	if (isset($arrayObjItm)) {
		foreach ($arrayObjItm as $indiceItm => $valorItm) {
			$idSolicitudDetalle = $valorItm;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					if ($frmListaSolicitudDetalle['hddIdSolicitudDetalle'.$valor] == $idSolicitudDetalle) {
						$valorItm = $valor;
					}
				}
			}
			
			switch ($idAccionSolicitud) {
				case 1 : // 2 = DESAPROBAR
					if ($accionDeshacer == false && $frmListaSolicitudDetalle['hddSolicitudDetalleAccion'.$valorItm] != 1) {
						 $objResponse->loadCommands(aprobarRepuesto($valorItm, $frmListaSolicitudDetalle, false));
					} else if ($accionDeshacer == true && $frmListaSolicitudDetalle['hddSolicitudDetalleAccion'.$valorItm] == 1) {
						 $objResponse->loadCommands(aprobarRepuesto($valorItm, $frmListaSolicitudDetalle, false));
					}
					break;
				case 2 : // 2 = APROBAR
					if ($accionDeshacer == false && $frmListaSolicitudDetalle['hddSolicitudDetalleAccion'.$valorItm] != 2) {
						 $objResponse->loadCommands(aprobarRepuesto($valorItm, $frmListaSolicitudDetalle, false));
					} else if ($accionDeshacer == true && $frmListaSolicitudDetalle['hddSolicitudDetalleAccion'.$valorItm] == 2) {
						 $objResponse->loadCommands(aprobarRepuesto($valorItm, $frmListaSolicitudDetalle, false));
					}
					break;
				case 3 : // 3 = DESPACHAR
					if ($accionDeshacer == false) {
						$objResponse->loadCommands(despacharRepuesto($valorItm, $frmListaSolicitudDetalle, false));
					} else if ($accionDeshacer == true && $frmListaSolicitudDetalle['hddSolicitudDetalleAccion'.$valorItm] == 3) {
						$objResponse->loadCommands(aprobarRepuesto($valorItm, $frmListaSolicitudDetalle, false));
					}
					break;
				case 4 : // 4 = DEVOLVER
					if ($accionDeshacer == false) {
						$objResponse->loadCommands(devolverRepuesto($valorItm, $frmListaSolicitudDetalle, false));
					} else if ($accionDeshacer == true && $frmListaSolicitudDetalle['hddSolicitudDetalleAccion'.$valorItm] == 4) {
						$objResponse->loadCommands(despacharRepuesto($valorItm, $frmListaSolicitudDetalle, false));
					}
					break;
				case 6 : // 6 = ANULAR
					if ($accionDeshacer == false) {
						$arrayItemAnular[] = $valorItm;
						//$objResponse->loadCommands(anularRepuesto($valorItm, $frmListaSolicitudDetalle, false));
					} else if ($accionDeshacer == true && $frmListaSolicitudDetalle['hddSolicitudDetalleAccion'.$valorItm] == 6) {
						$arrayItemAnularDeshacer[] = $valorItm;
						//$objResponse->loadCommands(aprobarRepuesto($valorItm, $frmListaSolicitudDetalle, false));
					}
					break;
			}
		}
		
		if (isset($arrayItemAnular)) {
			$objResponse->loadCommands(anularRepuesto($arrayItemAnular, $frmListaSolicitudDetalle, false));
		}
		if (isset($arrayItemAnularDeshacer)) {
			$objResponse->loadCommands(aprobarRepuesto($arrayItemAnularDeshacer, $frmListaSolicitudDetalle, false));
		}
	}
	
	$objResponse->script("xajax_validarAccion(xajax.getFormValues('frmListaSolicitudDetalle'));");
	
	return $objResponse;
}

function aprobarRepuesto($hddNroItm, $frmListaSolicitudDetalle, $validarAccion = true) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_surtido_taller_list","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	if (is_array($hddNroItm)) {
		if (isset($hddNroItm)) {
			foreach ($hddNroItm as $indiceNroItm => $valorNroItm) {
				$arrayIdSolicitudDetalle[] = $frmListaSolicitudDetalle['hddIdSolicitudDetalle'.$valorNroItm];
			}
		}
		$idSolicitudDetalle = implode(",",$arrayIdSolicitudDetalle);
	} else {
		$idSolicitudDetalle = $frmListaSolicitudDetalle['hddIdSolicitudDetalle'.$hddNroItm];
	}
	
	$querySolDet = sprintf("SELECT
		sol_rep.id_empresa,
		det_ord_art.id_det_orden_articulo,
		det_ord_art.id_articulo,
		det_sol_rep.id_casilla,
		det_ord_art.id_articulo_almacen_costo,
		det_ord_art.id_articulo_costo,
		det_sol_rep.id_estado_solicitud
	FROM sa_solicitud_repuestos sol_rep
		INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
		INNER JOIN sa_det_orden_articulo det_ord_art ON (det_sol_rep.id_det_orden_articulo = det_ord_art.id_det_orden_articulo)
	WHERE det_sol_rep.id_det_solicitud_repuesto IN (%s)",
		valTpDato($idSolicitudDetalle, "campo"));
	$rsSolDet = mysql_query($querySolDet);
	if (!$rsSolDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowSolDet = mysql_fetch_assoc($rsSolDet)) {
		$idEmpresa = $rowSolDet['id_empresa'];
		$idOrdenDetalle = $rowSolDet['id_det_orden_articulo'];
		$idArticulo = $rowSolDet['id_articulo'];
		$idCasillaAnt = $rowSolDet['id_casilla'];
		$idCasilla = $frmListaSolicitudDetalle['hddIdCasilla'.$hddNroItm];
		$cantidadArt = $frmListaSolicitudDetalle['hddCantArt'.$hddNroItm];
		$hddIdArticuloAlmacenCosto = $rowSolDet['id_articulo_almacen_costo'];
		$hddIdArticuloCosto = $rowSolDet['id_articulo_costo'];
		
		if (in_array($rowSolDet['id_estado_solicitud'], array(1,3))) { // 1 = SOLICITADO, 3 = DESPACHADO
			// BUSCA LOS SALDOS DEL ARTICULO EN LA UBICACION
			$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
			WHERE vw_iv_art_almacen_costo.id_articulo = %s
				AND vw_iv_art_almacen_costo.id_casilla = %s
				AND vw_iv_art_almacen_costo.id_articulo_costo = %s
				AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
				AND vw_iv_art_almacen_costo.estatus_almacen_venta = 1;",
				valTpDato($idArticulo, "int"),
				valTpDato($idCasilla, "int"),
				valTpDato($hddIdArticuloCosto, "int"));
			$rsArtEmp = mysql_query($queryArtEmp);
			if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
			
			if ($cantidadArt <= $rowArtEmp['cantidad_disponible_logica'] && $rowArtEmp['cantidad_disponible_logica'] > 0) {
				// ACTUALIZA EL ESTATUS Y LA CASILLA DEL ARTICULO DESPACHADO
				$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
					id_estado_solicitud = 2,
					id_casilla = %s
				WHERE id_det_solicitud_repuesto = %s;",
					valTpDato($idCasilla, "int"),
					valTpDato($idSolicitudDetalle, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
				// BUSCA LA UBICACION DEL LOTE
				$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
				WHERE vw_iv_art_almacen_costo.id_articulo_costo = %s
					AND vw_iv_art_almacen_costo.id_casilla = %s
					AND vw_iv_art_almacen_costo.estatus_articulo_almacen = 1;",
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($idCasilla, "int"));
				$rsArtCosto = mysql_query($queryArtCosto);
				if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
				$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
			
				// ACTUALIZA LA UBICACION DEL LOTE
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					id_articulo_almacen_costo = %s
				WHERE id_det_orden_articulo = %s;",
					valTpDato($rowArtCosto['id_articulo_almacen_costo'], "int"),
					valTpDato($idOrdenDetalle, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			} else {
				// VERIFICA SI TIENE EL ARTICULO BLOQUEADO
				$queryBloqVentDet = sprintf("SELECT * FROM iv_bloqueo_venta_detalle bloq_vent_det
				WHERE bloq_vent_det.id_articulo = %s
					AND bloq_vent_det.id_casilla = %s
					AND bloq_vent_det.id_articulo_costo = %s
					AND bloq_vent_det.cantidad > 0;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloCosto, "int"));
				$rsBloqVentDet = mysql_query($queryBloqVentDet);
				if (!$rsBloqVentDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsBloqVentDet = mysql_num_rows($rsBloqVentDet);
				$rowBloqVentDet = mysql_fetch_assoc($rsBloqVentDet);
				
				$idBloqueoVentaDetalle = $rowBloqVentDet['id_bloqueo_venta_detalle'];
				
				if ($totalRowsBloqVentDet > 0) {
					$contDesbloqueado++;
					
					$updateSQL = sprintf("UPDATE iv_bloqueo_venta_detalle SET
						cantidad = cantidad - 1,
						id_empleado_desbloqueo = %s
					WHERE id_bloqueo_venta_detalle = %s;",
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($idBloqueoVentaDetalle, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
					
					$updateSQL = sprintf("UPDATE iv_bloqueo_venta_detalle SET
						estatus = (CASE 
										WHEN cantidad_bloquear = cantidad THEN 1
										WHEN cantidad_bloquear > cantidad THEN 3
										WHEN cantidad = 0 THEN 2
									END)
					WHERE id_bloqueo_venta_detalle = %s;",
						valTpDato($idBloqueoVentaDetalle, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
								
					// ACTUALIZA LOS SALDOS DEL ARTICULO (BLOQUEADA)
					$Result1 = actualizarBloqueada($idArticulo, $idCasilla);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
					
					$msjArticulo .= ($msjArticulo != "") ? "": "El(Los) registro(s):\n";
					$msjArticulo .= ($contDesbloqueado % 4 == 1) ? "\n" : "";
					
					$msjArticulo .= str_pad("(".elimCaracter($rowArtEmp['codigo_articulo'],";").")", 30, " ", STR_PAD_RIGHT);
					
					$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
						id_estado_solicitud = 2,
						id_casilla = %s
					WHERE id_det_solicitud_repuesto = %s;",
						valTpDato($idCasilla, "int"),
						valTpDato($idSolicitudDetalle, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				
					// BUSCA LA UBICACION DEL LOTE
					$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
					WHERE vw_iv_art_almacen_costo.id_articulo_costo = %s
						AND vw_iv_art_almacen_costo.id_casilla = %s
						AND vw_iv_art_almacen_costo.estatus_articulo_almacen = 1;",
						valTpDato($hddIdArticuloCosto, "int"),
						valTpDato($idCasilla, "int"));
					$rsArtCosto = mysql_query($queryArtCosto);
					if (!$rsArtCosto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
					$rowArtCosto = mysql_fetch_assoc($rsArtCosto);
				
					// ACTUALIZA LA UBICACION DEL LOTE
					$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
						id_articulo_almacen_costo = %s
					WHERE id_det_orden_articulo = %s;",
						valTpDato($rowArtCosto['id_articulo_almacen_costo'], "int"),
						valTpDato($idOrdenDetalle, "int"));
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				} else {
					$objResponse->alert("No tiene disponibilidad del artículo: ".elimCaracter($rowArtEmp['codigo_articulo'],";")."en esta ubicación para hacer esta aprobación");
				}
			}
		} else if (in_array($rowSolDet['id_estado_solicitud'], array(2,6))) { // 2 = APROBADO, 6 = ANULADA
			$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
				id_estado_solicitud = 1
			WHERE id_det_solicitud_repuesto = %s;",
				valTpDato($idSolicitudDetalle, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		// ACTUALIZA LOS SALDOS DEL ARTICULO (RESERVADA)
		$Result1 = actualizarReservada($idArticulo, $idCasilla, $idCasillaAnt);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaSolicitudDetalle(
		$frmListaSolicitudDetalle['pageNum'],
		$frmListaSolicitudDetalle['campOrd'],
		$frmListaSolicitudDetalle['tpOrd'],
		$frmListaSolicitudDetalle['valBusq']));
	
	if ($validarAccion == true) {
		$objResponse->script("xajax_validarAccion(xajax.getFormValues('frmListaSolicitudDetalle'));");
	}
	
	if ($contDesbloqueado > 0) {
		$msjArticulo .= "\n\nse desbloqueó automáticamente para poder surtir esta solicitud";
		$objResponse->alert($msjArticulo);
	}
	
	return $objResponse;
}

function anularRepuesto($hddNroItm, $frmListaSolicitudDetalle, $validarAccion = true) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_surtido_taller_list","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	if (is_array($hddNroItm)) {
		if (isset($hddNroItm)) {
			foreach ($hddNroItm as $indiceNroItm => $valorNroItm) {
				$arrayIdSolicitudDetalle[] = $frmListaSolicitudDetalle['hddIdSolicitudDetalle'.$valorNroItm];
			}
		}
		$idSolicitudDetalle = implode(",",$arrayIdSolicitudDetalle);
	} else {
		$idSolicitudDetalle = $frmListaSolicitudDetalle['hddIdSolicitudDetalle'.$hddNroItm];
	}
	
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 6
	WHERE id_det_solicitud_repuesto IN (%s);",
		valTpDato($idSolicitudDetalle, "campo"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaSolicitudDetalle(
		$frmListaSolicitudDetalle['pageNum'],
		$frmListaSolicitudDetalle['campOrd'],
		$frmListaSolicitudDetalle['tpOrd'],
		$frmListaSolicitudDetalle['valBusq']));
	
	if ($validarAccion == true) {
		$objResponse->script("xajax_validarAccion(xajax.getFormValues('frmListaSolicitudDetalle'));");
	}
	
	return $objResponse;
}

function asignarAlmacen($frmAlmacen) {
	$objResponse = new xajaxResponse();
	
	$hddNroItm = $frmAlmacen['hddNroItm'];
	
	$queryArtUbic = sprintf("SELECT * FROM vw_iv_articulos_almacen
	WHERE id_articulo = %s
		AND id_casilla = %s;",
		valTpDato($frmAlmacen['hddIdArticulo'], "int"),
		valTpDato($frmAlmacen['lstCasillaArt'], "int"));
	$rsArtUbic = mysql_query($queryArtUbic);
	if (!$rsArtUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArtUbic = mysql_fetch_assoc($rsArtUbic);
	
	$objResponse->assign("spnAlmacen".$hddNroItm,"innerHTML",utf8_encode($rowArtUbic['descripcion_almacen']));
	$objResponse->assign("spnUbicacion".$hddNroItm,"innerHTML",utf8_encode(str_replace("-[]", "", $rowArtUbic['ubicacion'])));
	$objResponse->assign("hddIdCasilla".$hddNroItm,"value",$frmAlmacen['lstCasillaArt']);
	
	$objResponse->script("
	byId('imgCerrarDivFlotante2').click();");
	
	return $objResponse;
}

function asignarDisponibilidadUbicacion($idCasilla, $objetoDestino) {
	$objResponse = new xajaxResponse();
	
	$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_casilla = %s
		AND estatus_articulo_almacen = 1;",
		valTpDato($idCasilla, "int"));
	$rsUbic = mysql_query($queryUbic);
	if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUbic = mysql_fetch_assoc($rsUbic);
	
	$objResponse->assign($objetoDestino,"value",$rowUbic['cantidad_disponible_logica']);
	
	return $objResponse;
}

function bloquearSolicitud($idSolicitud, $idOrden, $frmSolicitud = ""){
	$objResponse = new xajaxResponse();
	
	$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
		id_usuario_bloqueo = %s
	WHERE id_solicitud = %s;",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($idSolicitud, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	return $objResponse;
}

function buscarSolicitud($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstadoSolicitud'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaSolicitud(0, "id_solicitud", "DESC", $valBusq));
		
	return $objResponse;
}

function cargaLstEmpleado($idClaveFiltro, $selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM vw_pg_empleados
	WHERE clave_filtro IN (%s)
		AND activo = 1
	ORDER BY nombre_empleado",
		valTpDato($idClaveFiltro, "campo"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstatusSolicitud($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM sa_estado_solicitud WHERE id_estado_solicitud NOT IN (5,10)");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstEstadoSolicitud\" name=\"lstEstadoSolicitud\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_estado_solicitud']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_estado_solicitud']."\">".utf8_encode($row['descripcion_estado_solicitud'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstadoSolicitud","innerHTML",$html);
	
	return $objResponse;
}

function desbloquearSolicitud($frmSolicitud, $frmListaSolicitud){
	$objResponse = new xajaxResponse();
	
	$idSolicitud = $frmSolicitud['hddIdSolicitud'];
	$idOrden = $frmSolicitud['hddIdOrden'];
	
	$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
		id_usuario_bloqueo = NULL
	WHERE id_solicitud = %s;",
		valTpDato($idSolicitud, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DE LA SOLICITUD
	$Result1 = actualizarEstatusSolicitudRepuestos($idSolicitud);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	$queryDetalleSolicitud = sprintf("SELECT orden_det.id_articulo
	FROM sa_det_solicitud_repuestos sol_rep_det
		INNER JOIN sa_det_orden_articulo orden_det ON (sol_rep_det.id_det_orden_articulo = orden_det.id_det_orden_articulo)
	WHERE sol_rep_det.id_solicitud = %s
	GROUP BY orden_det.id_articulo;",
		valTpDato($idSolicitud, "int"));
	$rsDetalleSolicitud = mysql_query($queryDetalleSolicitud);
	if (!$rsDetalleSolicitud) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while($rowDetalleSolicitud = mysql_fetch_assoc($rsDetalleSolicitud)) {
		// ACTUALIZA LOS SALDOS DEL ARTICULO (RESERVADA)
		$Result1 = actualizarReservada($rowDetalleSolicitud['id_articulo']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	$objResponse->loadCommands(listaSolicitud(
		$frmListaSolicitud['pageNum'],
		$frmListaSolicitud['campOrd'],
		$frmListaSolicitud['tpOrd'],
		$frmListaSolicitud['valBusq']));
	
	return $objResponse;
}

function despacharRepuesto($hddNroItm, $frmListaSolicitudDetalle, $validarAccion = true) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_surtido_taller_list","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$idSolicitudDetalle = $frmListaSolicitudDetalle['hddIdSolicitudDetalle'.$hddNroItm];
	
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 3
	WHERE id_det_solicitud_repuesto = %s;",
		valTpDato($idSolicitudDetalle, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaSolicitudDetalle(
		$frmListaSolicitudDetalle['pageNum'],
		$frmListaSolicitudDetalle['campOrd'],
		$frmListaSolicitudDetalle['tpOrd'],
		$frmListaSolicitudDetalle['valBusq']));
	
	if ($validarAccion == true) {
		$objResponse->script("xajax_validarAccion(xajax.getFormValues('frmListaSolicitudDetalle'));");
	}
	
	return $objResponse;
}

function devolverRepuesto($hddNroItm, $frmListaSolicitudDetalle, $validarAccion = true) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_surtido_taller_list","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	$idSolicitudDetalle = $frmListaSolicitudDetalle['hddIdSolicitudDetalle'.$hddNroItm];
	
	$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
		id_estado_solicitud = 4
	WHERE id_det_solicitud_repuesto = %s;",
		valTpDato($idSolicitudDetalle, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(listaSolicitudDetalle(
		$frmListaSolicitudDetalle['pageNum'],
		$frmListaSolicitudDetalle['campOrd'],
		$frmListaSolicitudDetalle['tpOrd'],
		$frmListaSolicitudDetalle['valBusq']));
	
	if ($validarAccion == true) {
		$objResponse->script("xajax_validarAccion(xajax.getFormValues('frmListaSolicitudDetalle'));");
	}
	
	return $objResponse;
}

function formAlmacen($hddNroItm, $frmListaSolicitudDetalle) {
	$objResponse = new xajaxResponse();
	
	$idSolicitudDetalle = $frmListaSolicitudDetalle['hddIdSolicitudDetalle'.$hddNroItm];
	
	$querySolDet = sprintf("SELECT *
	FROM sa_solicitud_repuestos sol_rep
		INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
		INNER JOIN sa_det_orden_articulo det_ord_art ON (det_sol_rep.id_det_orden_articulo = det_ord_art.id_det_orden_articulo)
		INNER JOIN vw_iv_articulos_datos_basicos vw_iv_art_datos_bas ON (det_ord_art.id_articulo = vw_iv_art_datos_bas.id_articulo)
	WHERE det_sol_rep.id_det_solicitud_repuesto = %s",
		valTpDato($idSolicitudDetalle, "int"));
	$rsSolDet = mysql_query($querySolDet);
	if (!$rsSolDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowSolDet = mysql_fetch_assoc($rsSolDet);
	
	$idArticulo = $rowSolDet['id_articulo'];
	$idEmpresa = $rowSolDet['id_empresa'];
	$idCasilla = $frmListaSolicitudDetalle['hddIdCasilla'.$hddNroItm];
	
	$objResponse->assign("hddNroItm","value",$hddNroItm);
	$objResponse->assign("hddIdArticulo","value",$rowSolDet['id_articulo']);
	$objResponse->assign("txtCodigoArt","value",elimCaracter($rowSolDet['codigo_articulo'],";"));
	$objResponse->assign("txtUnidadArt","value",$rowSolDet['unidad']);
	
	$selIdCasilla = $idCasilla;
	
	// BUSCA LAS UBICACIONES PARA LA VENTA DEL ARTICULO
	$queryUbic = sprintf("SELECT * FROM vw_iv_articulos_empresa_ubicacion
	WHERE id_articulo = %s
		AND id_empresa = %s
		AND estatus_almacen_venta = 1
		AND estatus_articulo_almacen = 1;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsUbic = mysql_query($queryUbic);
	if (!$rsUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsUbic = mysql_num_rows($rsUbic);
	while ($rowUbic = mysql_fetch_assoc($rsUbic)) {
		$arrayUbicacion = NULL;
		$existeUbicacion = false;
		if (isset($arrayAlmacen)) {
			foreach($arrayAlmacen as $indice => $valor) {
				if ($arrayAlmacen[$indice][0] == $rowUbic['id_almacen']) {
					$existeUbicacion = true;
					
					$arrayUbicacion = $arrayAlmacen[$indice][2];
					
					$arrayDetalleUbicacion['id_casilla'] = $rowUbic['id_casilla'];
					$arrayDetalleUbicacion['ubicacion'] = $rowUbic['ubicacion'];
					$arrayUbicacion[] = $arrayDetalleUbicacion;
					$arrayAlmacen[$indice][2] = $arrayUbicacion;
				}
			}
		}
		
		if ($existeUbicacion == false) {
			$arrayDetalleUbicacion['id_casilla'] = $rowUbic['id_casilla'];
			$arrayDetalleUbicacion['ubicacion'] = $rowUbic['ubicacion'];
			$arrayUbicacion[] = $arrayDetalleUbicacion;
			$arrayAlmacen[] = array(
				$rowUbic['id_almacen'],
				$rowUbic['descripcion_almacen'],
				$arrayUbicacion);
		}
	}
	
	$htmlLstIni = "<select id=\"lstCasillaArt\" name=\"lstCasillaArt\" class=\"inputHabilitado\" onchange=\"xajax_asignarDisponibilidadUbicacion(this.value,'txtCantidadUbicacion');\" style=\"width:99%\">";
		$htmlLst = "<option value=\"-1\">[ Seleccione ]</option>";
	if (isset($arrayAlmacen)) {
		foreach($arrayAlmacen as $indice => $valor) {
			$arrayUbicacion = $arrayAlmacen[$indice][2];
			
			$htmlLst .= "<optgroup label=\"".utf8_encode($arrayAlmacen[$indice][1])."\">";
			if (isset($arrayUbicacion)) {
				foreach($arrayUbicacion as $indice2 => $valor2) {
					$selected = "";
					if (in_array($arrayUbicacion[$indice2]['id_casilla'], array($selIdCasilla))
					|| !($totalRowsUbic > 1)) {
						$selected = "selected=\"selected\"";
						$objResponse->script("xajax_asignarDisponibilidadUbicacion('".$arrayUbicacion[$indice2]['id_casilla']."','txtCantidadUbicacion');");
					}
					
					$htmlLst .= "<option ".$selected." value=\"".$arrayUbicacion[$indice2]['id_casilla']."\">".utf8_encode(str_replace("-[]", "", $arrayUbicacion[$indice2]['ubicacion']))."</option>";
				}
			}
			$htmlLst .= "</optgroup>";
		}
	}
	$htmlLstFin = "</select>";
	$objResponse->assign("tdlstCasillaArt","innerHTML",$htmlLstIni.$htmlLst.$htmlLstFin);
	
	$objResponse->loadCommands(asignarDisponibilidadUbicacion($selId,'txtCantDisponible'));
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Listado de Ubicaciones");
	
	return $objResponse;
}

function formSolicitudRepuesto($idSolicitud, $idAccionSolicitud) {
	$objResponse = new xajaxResponse();
	
	// DESBLOQUEA LA SOLICITUD
	$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
		id_usuario_bloqueo = NULL
	WHERE id_solicitud = %s
		AND id_usuario_bloqueo = %s;",
		valTpDato($idSolicitud, "int"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	// ACTUALIZA EL ESTATUS DE LA SOLICITUD
	$Result1 = actualizarEstatusSolicitudRepuestos($idSolicitud);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// BUSCA LOS DATOS DE LA SOLICITUD
	$querySolicitud = sprintf("SELECT
		orden.id_empresa,
		orden.id_orden,
		orden.numero_orden,
		orden.tiempo_orden,
		orden.id_estado_orden,
		estado_orden.nombre_estado,
		tp_orden.nombre_tipo_orden,
		uni_bas.imagen_auto,
		vw_sa_vale_recep.chasis,
		vw_sa_vale_recep.placa,
		sol_rep.id_solicitud,
		sol_rep.numero_solicitud,
		sol_rep.tiempo_solicitud,
		sol_rep.id_empleado_entrega,
		sol_rep.id_jefe_taller,
		sol_rep.id_empleado_devuelto,
		sol_rep.id_empleado_recibo,
		sol_rep.id_empleado_recibo,
		estado_sol.id_estado_solicitud,
		estado_sol.descripcion_estado_solicitud,
	
		(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
		FROM cj_cc_cliente cliente
		WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = orden.id_recepcion), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																		FROM sa_cita c
																		WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																					FROM sa_recepcion r
																					WHERE r.id_recepcion = orden.id_recepcion))) AS id_cliente)) AS nombre_cliente,
		
		CONCAT_WS(', ', CONCAT_WS(' ', 'Motor', ccc_uni_bas), CONCAT_WS(' ', cil_uni_bas, 'Cilindros')) AS motor,
		
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_empleado_entrega) AS nombre_empleado_entrega,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_jefe_taller) AS nombre_empleado_jefe_taller,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_jefe_repuesto) AS nombre_empleado_jefe_repuestos,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_gerente_postventa) AS nombre_empleado_gte_postventa,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_empleado_recibo) AS nombre_empleado_recibo,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = sol_rep.id_empleado_devuelto) AS nombre_empleado_devuelto
	FROM sa_tipo_orden tp_orden
		INNER JOIN sa_orden orden ON (tp_orden.id_tipo_orden = orden.id_tipo_orden)
		INNER JOIN sa_estado_orden estado_orden ON (orden.id_estado_orden = estado_orden.id_estado_orden)
		INNER JOIN sa_solicitud_repuestos sol_rep ON (orden.id_orden = sol_rep.id_orden)
		INNER JOIN sa_estado_solicitud estado_sol ON (sol_rep.estado_solicitud = estado_sol.id_estado_solicitud)
		INNER JOIN vw_sa_vales_recepcion vw_sa_vale_recep ON (orden.id_recepcion = vw_sa_vale_recep.id_recepcion)
		INNER JOIN an_uni_bas uni_bas ON (vw_sa_vale_recep.id_uni_bas = uni_bas.id_uni_bas)
	WHERE sol_rep.id_solicitud = %s;",
		valTpDato($idSolicitud, "int"));
	$rsSolicitud = mysql_query($querySolicitud);
	if (!$rsSolicitud) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowSolicitud = mysql_fetch_assoc($rsSolicitud);
		
	$idEmpresa = $rowSolicitud['id_empresa'];
	
	$mesCierreInvFis = (date("m") == "01") ? 12 : (date("m") - 1);
	$anoCierreInvFis = (date("m") == "01") ? (date("Y") - 1) : date("Y");
	
	// VERIFICA SI SE REALIZO EL CIERRE MENSUAL DEL MES ANTERIOR
	$queryCierreMensual = sprintf("SELECT
		(SELECT COUNT(*) FROM iv_cierre_mensual
		WHERE mes = %s
			AND ano = %s
			AND id_empresa = %s
			AND estatus = 1) AS cierre_mes_anterior_realizado,
		(SELECT COUNT(*) FROM iv_cierre_mensual
		WHERE mes = %s
			AND ano = %s
			AND estatus = 0) AS cierre_mes_actual_pendiente;",
		valTpDato($mesCierreInvFis, "int"),
		valTpDato($anoCierreInvFis, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato(date("m"), "int"),
		valTpDato(date("Y"), "int"));
	$rsCierreMensual = mysql_query($queryCierreMensual);
	if (!$rsCierreMensual) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCierreMensual = mysql_num_rows($rsCierreMensual);
	$rowCierreMensual = mysql_fetch_assoc($rsCierreMensual);
	
	if (!($rowCierreMensual['cierre_mes_anterior_realizado'] > 0 && $rowCierreMensual['cierre_mes_actual_pendiente'] == 0)) {
		$objResponse->alert("No puede surtir a ordenes de servicio, debido a que aún no se ha realizado el cierre del mes anterior");
		usleep(0.5 * 1000000); $objResponse->script("byId('imgCerrarDivFlotante1').click();"); return $objResponse;
	}
	
	$queryDetalleSolicitud = sprintf("SELECT orden_det.id_articulo
	FROM sa_det_solicitud_repuestos sol_rep_det
		INNER JOIN sa_det_orden_articulo orden_det ON (sol_rep_det.id_det_orden_articulo = orden_det.id_det_orden_articulo)
	WHERE sol_rep_det.id_solicitud = %s
	GROUP BY orden_det.id_articulo;",
		valTpDato($idSolicitud, "int"));
	$rsDetalleSolicitud = mysql_query($queryDetalleSolicitud);
	if (!$rsDetalleSolicitud) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while($rowDetalleSolicitud = mysql_fetch_assoc($rsDetalleSolicitud)) {
		// ACTUALIZA LOS SALDOS DEL ARTICULO (RESERVADA)
		$Result1 = actualizarReservada($rowDetalleSolicitud['id_articulo']);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
	$imgFoto = (!file_exists($rowSolicitud['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $rowSolicitud['imagen_auto'];
	
	$objResponse->assign("imgUnidad","src",$imgFoto);
	$objResponse->assign("txtMotor","value",utf8_encode($rowSolicitud['motor']));
	$objResponse->assign("txtChasis","value",utf8_encode($rowSolicitud['chasis']));
	$objResponse->assign("txtPlaca","value",utf8_encode($rowSolicitud['placa']));
	
	$objResponse->assign("txtCliente","value",utf8_encode($rowSolicitud['nombre_cliente']));
	$objResponse->assign("hddIdSolicitud","value",$rowSolicitud['id_solicitud']);
	$objResponse->assign("txtNumeroSolicitud","value",$rowSolicitud['numero_solicitud']);
	$objResponse->assign("txtFechaSolicitud","value",date(spanDateFormat,strtotime($rowSolicitud['tiempo_solicitud'])));
	$objResponse->assign("hddIdEstadoSolicitud","value",$rowSolicitud['id_estado_solicitud']);
	$objResponse->assign("txtEstadoSolicitud","value",strtoupper($rowSolicitud['descripcion_estado_solicitud']));
	
	if ((strlen($idAccionSolicitud) > 0 && !($rowSolicitud['id_empleado_entrega'] > 0))) {
		// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
		$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios vw_iv_usu
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (vw_iv_usu.id_empleado = vw_pg_empleado.id_empleado)
		WHERE id_usuario = %s;",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rsUsuario = mysql_query($queryUsuario);
		if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowUsuario = mysql_fetch_assoc($rsUsuario);
		
		$idEmpleadoEntrega = $_SESSION['idEmpleadoSysGts'];
		$nomEmpleadoEntrega = utf8_encode($rowUsuario['nombre_empleado']);
	} else {
		$idEmpleadoEntrega = $rowSolicitud['id_empleado_entrega'];
		$nomEmpleadoEntrega = utf8_encode($rowSolicitud['nombre_empleado_entrega']);
	}
	$objResponse->assign("hddIdEmpleadoEntrega","value",$idEmpleadoEntrega);
	$objResponse->assign("txtEmpleadoEntrega","value",$nomEmpleadoEntrega);
	
	
	if ($idAccionSolicitud == 2 || $idAccionSolicitud == 3) {
		$objResponse->loadCommands(cargaLstEmpleado("6", $rowSolicitud['id_jefe_taller'], "lstJefeTaller", "tdlstJefeTaller"));
	} else {
		$html = sprintf("<input type=\"hidden\" id=\"hddIdEmpleadoJefeTaller\" name=\"hddIdEmpleadoJefeTaller\" readonly=\"readonly\" size=\"4\" value=\"%s\"/>",
			$rowSolicitud['id_jefe_taller']);
		$html .= sprintf("<input type=\"text\" id=\"txtEmpleadoJefeTaller\" name=\"txtEmpleadoJefeTaller\" readonly=\"readonly\" size=\"25\" value=\"%s\"/>",
			utf8_encode($rowSolicitud['nombre_empleado_jefe_taller']));
		$objResponse->assign("tdlstJefeTaller","innerHTML",$html);
	}
	
	if ($idAccionSolicitud == 2 || $idAccionSolicitud == 3) {
		$objResponse->loadCommands(cargaLstEmpleado("80, 501", $rowSolicitud['id_empleado_recibo'], "lstEmpleadoRecibido", "tdlstEmpleadoRecibido"));
	} else {
		$html = sprintf("<input type=\"hidden\" id=\"hddIdEmpleadoRecibido\" name=\"hddIdEmpleadoRecibido\" readonly=\"readonly\" size=\"4\" value=\"%s\"/>",
			$rowSolicitud['id_empleado_recibo']);
		$html .= sprintf("<input type=\"text\" id=\"txtEmpleadoRecibido\" name=\"txtEmpleadoRecibido\" readonly=\"readonly\" size=\"25\" value=\"%s\"/>",
			utf8_encode($rowSolicitud['nombre_empleado_recibo']));
		$objResponse->assign("tdlstEmpleadoRecibido","innerHTML",$html);
	}
	
	if ($idAccionSolicitud == 4) {
		$objResponse->loadCommands(cargaLstEmpleado("80, 501", $rowSolicitud['id_empleado_devuelto'], "lstEmpleadoDevuelto", "tdlstEmpleadoDevuelto"));
	} else {
		$html = sprintf("<input type=\"hidden\" id=\"hddIdEmpleadoDevuelto\" name=\"hddIdEmpleadoDevuelto\" readonly=\"readonly\" size=\"4\" value=\"%s\"/>",
			$rowSolicitud['id_empleado_recibo']);
		$html .= sprintf("<input type=\"text\" id=\"txtEmpleadoDevuelto\" name=\"txtEmpleadoDevuelto\" readonly=\"readonly\" size=\"25\" value=\"%s\"/>",
			utf8_encode($rowSolicitud['nombre_empleado_recibo']));
		$objResponse->assign("tdlstEmpleadoDevuelto","innerHTML",$html);
	}
	
	$objResponse->assign("hddIdOrden","value",$rowSolicitud['id_orden']);
	$objResponse->assign("txtNumeroOrden","value",$rowSolicitud['numero_orden']);
	$objResponse->assign("txtFechaOrden","value",date(spanDateFormat,strtotime($rowSolicitud['tiempo_orden'])));
	$objResponse->assign("txtTipoOrden","value",utf8_encode($rowSolicitud['nombre_tipo_orden']));
	$objResponse->assign("hddIdEstadoOrden","value",utf8_encode($rowSolicitud['id_estado_orden']));
	$objResponse->assign("txtEstadoOrden","value",strtoupper(utf8_encode($rowSolicitud['nombre_estado'])));
	
	$arrayAccionRealizada = array(1 => "DESAPROBAR", 2 => "APROBAR", 3 => "DESPACHAR", 4 => "DEVOLVER", 6 => "ANULAR");
	$objResponse->assign("hddIdAccionRealizada","value",$idAccionSolicitud);
	$objResponse->assign("txtAccionRealizada","value",$arrayAccionRealizada[$idAccionSolicitud]);
	$objResponse->assign("hddCantActivas","value","");
	
	if (strlen($idAccionSolicitud) > 0) {
		// VERIFICA SI LA ORDEN ESTA SIENDO EDITADA O NO
		$bloqueado = verificarBloqueoOrden($rowSolicitud['id_orden']);
		if ($bloqueado == true && strlen($idAccionSolicitud) > 0){
			sleep(1);
			$objResponse->alert("La orden de servicio asociada a esta solicitud se encuentra en edicion por otro usuario espera que la misma sea terminada");
			$objResponse->script("byId('imgCerrarDivFlotante1').click();");
			return $objResponse;
		} else {
			$objResponse->loadCommands(bloquearSolicitud($idSolicitud, $rowSolicitud['id_orden']));
		}
		
		$objResponse->script("byId('trAccionRealizada').style.display = '';");
		$objResponse->script("byId('btnGuardarSolicitud').style.display = '';");
	} else {
		$objResponse->script("byId('trAccionRealizada').style.display = 'none';");
		$objResponse->script("byId('btnGuardarSolicitud').style.display = 'none';");
	}
	
	switch ($idAccionSolicitud) {
		case 1 : $objResponse->script("byId('btnDesaprobar').style.display = '';"); break;
		case 2 : $objResponse->script("byId('btnAprobar').style.display = '';"); break;
		case 3 : $objResponse->script("byId('btnDespachar').style.display = '';"); break;
		case 4 : $objResponse->script("byId('btnDevolver').style.display = '';"); break;
		case 6 : $objResponse->script("byId('btnAnular').style.display = '';"); break;
	}
	
	$objResponse->loadCommands(listaSolicitudDetalle(0,'id_det_solicitud_repuesto','DESC',$idSolicitud."|".$idAccionSolicitud));
	
	$objResponse->loadCommands(listaComprobanteSurtido(0,'fecha_movimiento','DESC',$rowSolicitud['id_orden']));
	
	return $objResponse;
}

function guardarSolicitud($frmSolicitud, $frmListaSolicitudDetalle, $frmListaSolicitud) {
	$objResponse = new xajaxResponse();
	
	$idSolicitud = $frmSolicitud['hddIdSolicitud'];
	$idOrden = $frmSolicitud['hddIdOrden'];
	$hddIdAccionRealizada = $frmListaSolicitudDetalle['hddIdAccionRealizada'];
	
	$idEmpleadoEntrega = $frmSolicitud['hddIdEmpleadoEntrega'];
	$idJefeTaller = ($frmSolicitud['hddIdEmpleadoJefeTaller'] > 0) ? $frmSolicitud['hddIdEmpleadoJefeTaller'] : $frmSolicitud['lstJefeTaller'];
	$idEmpleadoRecibido = ($frmSolicitud['hddIdEmpleadoRecibido'] > 0) ? $frmSolicitud['hddIdEmpleadoRecibido'] : $frmSolicitud['lstEmpleadoRecibido'];
	$idEmpleadoDevuelto = ($frmSolicitud['hddIdEmpleadoDevuelto'] > 0) ? $frmSolicitud['hddIdEmpleadoDevuelto'] : $frmSolicitud['lstEmpleadoDevuelto'];
	
	mysql_query("START TRANSACTION;");
	
	// ACTUALIZA EL TIEMPO DE APROBACION, DESPACHO O DEVOLUCION
	if ($hddIdAccionRealizada == 1) { // 1 = SOLICITADO
		$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
			tiempo_aprobacion = NULL,
			id_casilla = NULL
		WHERE id_solicitud = %s
			AND id_estado_solicitud = 1
			AND (tiempo_aprobacion IS NOT NULL OR tiempo_aprobacion <> 0);",
			valTpDato($idSolicitud, "int"));
	} else if ($hddIdAccionRealizada == 2) { // 2 = APROBADO
		$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
			tiempo_aprobacion = NOW()
		WHERE id_solicitud = %s
			AND id_estado_solicitud = 2
			AND (tiempo_aprobacion IS NULL OR tiempo_aprobacion = 0);",
			valTpDato($idSolicitud, "int"));
	} else if ($hddIdAccionRealizada == 3) { // 3 = DESPACHADO
		$fechaMovimiento = date("Y-m-d H:i:s");
		
		// INSERTA LOS DATOS EN EL KARDEX DE SURTIDO
		$insertSQL = sprintf("INSERT INTO iv_kardex_surtido (id_solicitud, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, cantidad, fecha_movimiento, id_estado_solicitud, id_empleado_despacha, id_empleado_recibe) 
		SELECT
			det_sol_rep.id_solicitud,
			det_ord_art.id_articulo,
			det_sol_rep.id_casilla,
			det_ord_art.id_articulo_almacen_costo,
			det_ord_art.id_articulo_costo,
			1,
			%s,
			det_sol_rep.id_estado_solicitud,
			%s,
			%s
		FROM sa_det_solicitud_repuestos det_sol_rep
			INNER JOIN sa_det_orden_articulo det_ord_art ON (det_sol_rep.id_det_orden_articulo = det_ord_art.id_det_orden_articulo)
		WHERE det_sol_rep.id_solicitud = %s
			AND det_sol_rep.id_estado_solicitud = 3
			AND (tiempo_despacho IS NULL OR tiempo_despacho = 0);",
			valTpDato($fechaMovimiento, "date"),
			valTpDato($idEmpleadoEntrega, "int"),
			valTpDato($idEmpleadoRecibido, "int"),
			valTpDato($idSolicitud, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
			tiempo_despacho = %s
		WHERE id_solicitud = %s
			AND id_estado_solicitud = 3
			AND (tiempo_despacho IS NULL OR tiempo_despacho = 0);",
			valTpDato($fechaMovimiento, "date"),
			valTpDato($idSolicitud, "int"));
	} else if ($hddIdAccionRealizada == 4) { // 4 = DEVUELTO
		$fechaMovimiento = date("Y-m-d H:i:s");
		
		// INSERTA LOS DATOS EN EL KARDEX DE SURTIDO
		$insertSQL = sprintf("INSERT INTO iv_kardex_surtido (id_solicitud, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, cantidad, fecha_movimiento, id_estado_solicitud, id_empleado_despacha, id_empleado_recibe) 
		SELECT
			det_sol_rep.id_solicitud,
			det_ord_art.id_articulo,
			det_sol_rep.id_casilla,
			det_ord_art.id_articulo_almacen_costo,
			det_ord_art.id_articulo_costo,
			1,
			%s,
			det_sol_rep.id_estado_solicitud,
			%s,
			%s
		FROM sa_det_solicitud_repuestos det_sol_rep
			INNER JOIN sa_det_orden_articulo det_ord_art ON (det_sol_rep.id_det_orden_articulo = det_ord_art.id_det_orden_articulo)
		WHERE det_sol_rep.id_solicitud = %s
			AND det_sol_rep.id_estado_solicitud = 4
			AND (tiempo_devolucion IS NULL OR tiempo_devolucion = 0);",
			valTpDato($fechaMovimiento, "date"),
			valTpDato($idEmpleadoEntrega, "int"),
			valTpDato($idEmpleadoDevuelto, "int"),
			valTpDato($idSolicitud, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
			tiempo_devolucion = %s
		WHERE id_solicitud = %s
			AND id_estado_solicitud = 4
			AND (tiempo_devolucion IS NULL OR tiempo_devolucion = 0);",
			valTpDato($fechaMovimiento, "date"),
			valTpDato($idSolicitud, "int"));
	} else if ($hddIdAccionRealizada == 6) { // 6 = ANULADA
		$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
			tiempo_anulacion = NOW(),
			id_casilla = NULL
		WHERE id_solicitud = %s
			AND id_estado_solicitud = 6
			AND (tiempo_anulacion IS NULL OR tiempo_anulacion = 0);",
			valTpDato($idSolicitud, "int"));
	}
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	
	// ACTUALIZA EL ESTATUS DE LA SOLICITUD
	$Result1 = actualizarEstatusSolicitudRepuestos($idSolicitud);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	
	// ACTUALIZA LOS DATOS DE LA SOLICITUD
	if ($hddIdAccionRealizada == 2) { // 2 = APROBADO
		$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
			id_jefe_taller = %s,
			tiempo_recibo_jefe_taller = NOW(),
			id_empleado_recibo = %s
		WHERE id_solicitud = %s;",
			valTpDato($idJefeTaller, "int"),
			valTpDato($idEmpleadoRecibido, "int"),
			valTpDato($idSolicitud, "int"));
	} else if ($hddIdAccionRealizada == 3) { // 3 = DESPACHADO
		$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
			id_jefe_taller = %s,
			tiempo_recibo_jefe_taller = NOW(),
			id_empleado_recibo = %s,
			id_empleado_entrega = %s
		WHERE id_solicitud = %s;",
			valTpDato($idJefeTaller, "int"),
			valTpDato($idEmpleadoRecibido, "int"),
			valTpDato($idEmpleadoEntrega, "int"),
			valTpDato($idSolicitud, "int"));
	} else if ($hddIdAccionRealizada == 4) { // 4 = DEVUELTO
		$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
			id_empleado_devuelto = %s
		WHERE id_solicitud = %s;",
			valTpDato($idEmpleadoDevuelto, "int"),
			valTpDato($idSolicitud, "int"));
	}
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	
	// ACTUALIZA EL DETALLE DE LA ORDEN
	if ($hddIdAccionRealizada == 1 || $hddIdAccionRealizada == 2) { // 1 = SOLICITUD O DESAPROBADO, 2 = APROBADO
		$query = sprintf("SELECT
			det_sol_rep.id_det_orden_articulo,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
				AND det_sol_rep2.id_estado_solicitud IN (2,3,5)
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad_aprobada,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
				AND det_sol_rep2.id_estado_solicitud NOT IN (2,3,5)
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad_no_aprobada
		
		FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = %s
		GROUP BY det_sol_rep.id_det_orden_articulo",
			valTpDato($idSolicitud, "int"));
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($row = mysql_fetch_assoc($rs)) {
			// estado_articulo: 1 = PENDIENTE, 2 = PROCESO, 3 = DETENIDO, 4 = TERMINADO, 5 = DEVUELTO, 6 = FACTURADO
			if ($row['cantidad_no_aprobada'] == $row['cantidad']) {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 0,
					estado_articulo = 5
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['id_det_orden_articulo'], "int"));
			} else if ($row['cantidad_aprobada'] == $row['cantidad']) {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 1,
					estado_articulo = 1
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['id_det_orden_articulo'], "int"));
			} else {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 1,
					estado_articulo = 1,
					cantidad = %s
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['cantidad_aprobada'], "real_inglesa"),
					valTpDato($row['id_det_orden_articulo'], "int"));
			}
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	} else if ($hddIdAccionRealizada == 3) { // 3 = DESPACHADO
		$query = sprintf("SELECT
			det_sol_rep.id_det_orden_articulo,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
				AND det_sol_rep2.id_estado_solicitud IN (3)
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad_despachada,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
				AND det_sol_rep2.id_estado_solicitud NOT IN (3)
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad_no_despachada
		
		FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = %s
		GROUP BY det_sol_rep.id_det_orden_articulo",
			valTpDato($idSolicitud, "int"));
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($row = mysql_fetch_assoc($rs)) {
			if ($row['cantidad_no_despachada'] == $row['cantidad']) {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 0,
					estado_articulo = 5
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['id_det_orden_articulo'], "int"));
			} else if ($row['cantidad_despachada'] == $row['cantidad']) {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 1,
					estado_articulo = 1,
					cantidad = %s
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['cantidad_despachada'], "real_inglesa"),
					valTpDato($row['id_det_orden_articulo'], "int"));
			} else {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 1,
					estado_articulo = 1,
					cantidad = %s
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['cantidad_despachada'], "real_inglesa"),
					valTpDato($row['id_det_orden_articulo'], "int"));
			}
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	} else if ($hddIdAccionRealizada == 4) { // 4 = DEVUELTO
		$query = sprintf("SELECT
			det_sol_rep.id_det_orden_articulo,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
				AND det_sol_rep2.id_estado_solicitud IN (3)
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad_despachada,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
				AND det_sol_rep2.id_estado_solicitud IN (4,6)
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad_devuelta
		
		FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = %s
		GROUP BY det_sol_rep.id_det_orden_articulo",
			valTpDato($idSolicitud, "int"));
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($row = mysql_fetch_assoc($rs)) {
			if ($row['cantidad_devuelta'] == $row['cantidad']) {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 0,
					estado_articulo = 5
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['id_det_orden_articulo'], "int"));
			} else if ($row['cantidad_despachada'] == $row['cantidad']) {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 1,
					estado_articulo = 1,
					cantidad = %s
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['cantidad_despachada'], "real_inglesa"),
					valTpDato($row['id_det_orden_articulo'], "int"));
			} else if ($row['cantidad_despachada'] > 0) {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 1,
					estado_articulo = 1,
					cantidad = %s
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['cantidad_despachada'], "real_inglesa"),
					valTpDato($row['id_det_orden_articulo'], "int"));
			}
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	} else if ($hddIdAccionRealizada == 6) { // 6 = ANULADA
		$query = sprintf("SELECT
			det_sol_rep.id_det_orden_articulo,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
				AND det_sol_rep2.id_estado_solicitud IN (3)
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad_despachada,
			
			IFNULL((SELECT COUNT(det_sol_rep2.id_det_orden_articulo) FROM sa_det_solicitud_repuestos det_sol_rep2
			WHERE det_sol_rep2.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo
				AND det_sol_rep2.id_solicitud = det_sol_rep.id_solicitud
				AND det_sol_rep2.id_estado_solicitud IN (6,4)
			GROUP BY det_sol_rep2.id_det_orden_articulo), 0) AS cantidad_anulada
		
		FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = %s
		GROUP BY det_sol_rep.id_det_orden_articulo",
			valTpDato($idSolicitud, "int"));
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($row = mysql_fetch_assoc($rs)) {
			if ($row['cantidad_anulada'] == $row['cantidad']) {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 0,
					estado_articulo = 5
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['id_det_orden_articulo'], "int"));
			} else if ($row['cantidad_despachada'] == $row['cantidad']) {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 1,
					estado_articulo = 1,
					cantidad = %s
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['cantidad_despachada'], "real_inglesa"),
					valTpDato($row['id_det_orden_articulo'], "int"));
			} else if ($row['cantidad_despachada'] > 0) {
				$updateSQL = sprintf("UPDATE sa_det_orden_articulo SET
					aprobado = 1,
					estado_articulo = 1,
					cantidad = %s
				WHERE id_det_orden_articulo = %s;",
					valTpDato($row['cantidad_despachada'], "real_inglesa"),
					valTpDato($row['id_det_orden_articulo'], "int"));
			}
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	
	// BUSCA LOS DATOS DE LA SOLICITUD
	$querySolicitud = sprintf("SELECT
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud) AS cantidad,
		
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 1) AS cantidad_solicitada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 2
			AND tiempo_aprobacion > 0) AS cantidad_aprobada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 3
			AND tiempo_despacho > 0) AS cantidad_despachada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 4
			AND tiempo_devolucion > 0) AS cantidad_devuelta,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 5) AS cantidad_facturada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 10) AS cantidad_no_despachada
		
	FROM sa_solicitud_repuestos sol_rep
	WHERE sol_rep.id_solicitud = %s;",
		valTpDato($idSolicitud, "int"));
	$rsSolicitud = mysql_query($querySolicitud);
	if (!$rsSolicitud) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowSolicitud = mysql_fetch_assoc($rsSolicitud);
	
	
	// ACTUALIZA EL ESTADO DE LA ORDEN DE SERVICIO
	if ($rowSolicitud['cantidad_solicitada'] == $rowSolicitud['cantidad']) {
		$updateSQL = sprintf("UPDATE sa_orden SET
			sa_orden.id_estado_orden = %s
		WHERE sa_orden.id_orden = %s",
			valTpDato(4, "int"), // 4 = SOLICITUD REPUESTO
			valTpDato($idOrden, "int"));
	} else if ($rowSolicitud['cantidad_aprobada'] == $rowSolicitud['cantidad']) {
		$updateSQL = sprintf("UPDATE sa_orden SET
			sa_orden.id_estado_orden = %s
		WHERE sa_orden.id_orden = %s",
			valTpDato(5, "int"), // 5 = EN ESPERA DE REPUESTO
			valTpDato($idOrden, "int"));
	} else if ($rowSolicitud['cantidad_aprobada'] > 0 || $rowSolicitud['cantidad_despachada'] > 0 || $rowSolicitud['cantidad_devuelta'] > 0) {
		$updateSQL = sprintf("UPDATE sa_orden SET
			sa_orden.id_estado_orden = %s
		WHERE sa_orden.id_orden = %s",
			valTpDato(6, "int"), // 6 = PROCESO
			valTpDato($idOrden, "int"));
	}
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");
	
	$Result1 = actualizarOrdenServicio($idOrden);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(("Solicitud guardada con éxito"));
	
	if ($hddIdAccionRealizada == 3 || $hddIdAccionRealizada == 4) { // 3 = DESPACHADO, 4 = DEVUELTO
		$objResponse->script(sprintf("verVentana('reportes/iv_surtido_taller_pdf.php?valBusq=%s|%s|%s', 960, 550);",
			$idSolicitud,
			$hddIdAccionRealizada,
			$fechaMovimiento));
		
		$Result1 = actualizarReservada();
		if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	}
	
	$objResponse->loadCommands(desbloquearSolicitud($frmSolicitud, $frmListaSolicitud));
		
	$objResponse->script("
	byId('imgCerrarDivFlotante1').click();");
	
	return $objResponse;
}

function listaComprobanteSurtido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 100, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("sol_rep.id_orden = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$query = sprintf("SELECT 
		sol_rep.id_orden,
		sol_rep.id_solicitud,
		sol_rep.numero_solicitud,
		kardex_surtido.fecha_movimiento,
		kardex_surtido.id_estado_solicitud,
		kardex_surtido.id_empleado_despacha,
		kardex_surtido.id_empleado_recibe,
		estado_sol.descripcion_estado_solicitud,
		
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = kardex_surtido.id_empleado_despacha) AS nombre_empleado_entrega,
		(SELECT nombre_empleado FROM vw_pg_empleados WHERE id_empleado = kardex_surtido.id_empleado_recibe) AS nombre_empleado_recibo_devuelto
	FROM sa_solicitud_repuestos sol_rep
		INNER JOIN iv_kardex_surtido kardex_surtido ON (sol_rep.id_solicitud = kardex_surtido.id_solicitud)
		INNER JOIN sa_estado_solicitud estado_sol ON (kardex_surtido.id_estado_solicitud = estado_sol.id_estado_solicitud) %s
	GROUP BY sol_rep.id_orden,
		sol_rep.id_solicitud,
		kardex_surtido.fecha_movimiento,
		kardex_surtido.id_estado_solicitud,
		kardex_surtido.id_empleado_despacha,
		kardex_surtido.id_empleado_recibe", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaComprobanteSurtido", "20%", $pageNum, "fecha_movimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaComprobanteSurtido", "12%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_listaComprobanteSurtido", "12%", $pageNum, "descripcion_estado_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Estatus");
		$htmlTh .= ordenarCampo("xajax_listaComprobanteSurtido", "28%", $pageNum, "id_empleado_despacha", $campOrd, $tpOrd, $valBusq, $maxRows, "Despachado por / Devuelto a");
		$htmlTh .= ordenarCampo("xajax_listaComprobanteSurtido", "28%", $pageNum, "id_empleado_recibe", $campOrd, $tpOrd, $valBusq, $maxRows, "Despachado a / Devuelto por");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_estado_solicitud']) {
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Abierta\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Aprobada\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Despachada\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Devuelto\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturada\"/>"; break;
			case 6 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulada\"/>"; break;
			case 7 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo_parcial.gif\" title=\"Aprobada Parcial\"/>"; break;
			case 8 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja_parcial.gif\" title=\"Despachada Parcial\"/>"; break;
			case 9 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris_parcial.gif\" title=\"Devolucion Parcial\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i:s a", strtotime($row['fecha_movimiento']))."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numero_solicitud'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_estado_solicitud'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado_entrega'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado_recibo_devuelto'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/iv_surtido_taller_pdf.php?valBusq=%s|%s|%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"PDF\"/>",
					$row['id_solicitud'],
					$row['id_estado_solicitud'],
					$row['fecha_movimiento']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr style=\"display:none\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComprobanteSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComprobanteSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaComprobanteSurtido(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComprobanteSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComprobanteSurtido(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"6\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaComprobantes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaSolicitudDetalle($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 100, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanPrecioUnitario;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idAccionSolicitud = $valCadBusq[1];
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sol_rep.id_solicitud = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if (strlen($valCadBusq[1]) > 0) {
		$query = sprintf("SELECT
			det_sol_rep.id_det_solicitud_repuesto,
			sol_rep.id_empresa,
			art.id_articulo,
			art.codigo_articulo,
			art.descripcion,
			det_sol_rep.id_casilla,
			det_ord_art.id_articulo_almacen_costo,
			det_ord_art.id_articulo_costo,
			1 AS cantidad,
			det_ord_art.precio_unitario,
			det_ord_art.id_iva,
			det_ord_art.iva,
			det_sol_rep.id_estado_solicitud,
			det_sol_rep.tiempo_aprobacion,
			det_sol_rep.tiempo_despacho,
			det_sol_rep.tiempo_devolucion,
			det_sol_rep.tiempo_anulacion
		FROM sa_solicitud_repuestos sol_rep
			INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
			INNER JOIN sa_det_orden_articulo det_ord_art ON (det_sol_rep.id_det_orden_articulo = det_ord_art.id_det_orden_articulo)
			INNER JOIN iv_articulos art ON (det_ord_art.id_articulo = art.id_articulo) %s", $sqlBusq);
	} else { // MODO VISTA
		$query = sprintf("SELECT
			det_sol_rep.id_det_solicitud_repuesto,
			sol_rep.id_empresa,
			art.id_articulo,
			art.codigo_articulo,
			art.descripcion,
			det_sol_rep.id_casilla,
			det_ord_art.id_articulo_almacen_costo,
			det_ord_art.id_articulo_costo,
			COUNT(art.id_articulo) AS cantidad,
			det_ord_art.precio_unitario,
			det_ord_art.id_iva,
			det_ord_art.iva,
			det_sol_rep.id_estado_solicitud,
			det_sol_rep.tiempo_aprobacion,
			det_sol_rep.tiempo_despacho,
			det_sol_rep.tiempo_devolucion,
			det_sol_rep.tiempo_anulacion
		FROM sa_solicitud_repuestos sol_rep
			INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
			INNER JOIN sa_det_orden_articulo det_ord_art ON (det_sol_rep.id_det_orden_articulo = det_ord_art.id_det_orden_articulo)
			INNER JOIN iv_articulos art ON (det_ord_art.id_articulo = art.id_articulo) %s
		GROUP BY art.id_articulo,
			art.codigo_articulo,
			art.descripcion,
			det_sol_rep.id_casilla,
			det_ord_art.id_articulo_almacen_costo,
			det_ord_art.id_articulo_costo,
			det_ord_art.precio_unitario,
			det_ord_art.iva,
			sol_rep.id_empresa,
			det_sol_rep.id_estado_solicitud", $sqlBusq);
	}
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItm\" onclick=\"selecAllChecks(this.checked,this.id,'frmListaSolicitudDetalle');\"/></td>";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "16%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "34%", $pageNum, "descripcion_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "20%", $pageNum, "id_casilla", $campOrd, $tpOrd, $valBusq, $maxRows, "Ubicación");
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "4%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Cant.");
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "10%", $pageNum, "precio_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPrecioUnitario);
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "6%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaSolicitudDetalle", "10%", $pageNum, "precio_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
		$htmlTh .= "<td colspan=\"6\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		// VERIFICA VALORES DE CONFIGURACION (Método de Costo de Repuesto)
		$ResultConfig12 = valorConfiguracion(12, $row['id_empresa']);
		if ($ResultConfig12[0] != true && strlen($ResultConfig12[1]) > 0) {
			return $objResponse->alert($ResultConfig12[1]);
		} else if ($ResultConfig12[0] == true) {
			$ResultConfig12 = $ResultConfig12[1];
		}
		
		if ($row['id_estado_solicitud'] == 1 || $row['id_estado_solicitud'] == 6) {
			$queryArtUbic = sprintf("SELECT * FROM vw_iv_articulos_almacen
			WHERE id_articulo = %s
				AND id_empresa = %s
				AND casilla_predeterminada = 1;",
				valTpDato($row['id_articulo'], "int"),
				valTpDato($row['id_empresa'], "int"));
			$rsArtUbic = mysql_query($queryArtUbic);
			if (!$rsArtUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArtUbic = mysql_fetch_assoc($rsArtUbic);
			
			$idCasilla = $rowArtUbic['id_casilla'];
		} else {
			$queryArtUbic = sprintf("SELECT * FROM vw_iv_articulos_almacen
			WHERE id_articulo = %s
				AND id_casilla = %s
				AND id_empresa = %s;",
				valTpDato($row['id_articulo'], "int"),
				valTpDato($row['id_casilla'], "int"),
				valTpDato($row['id_empresa'], "int"));
			$rsArtUbic = mysql_query($queryArtUbic);
			if (!$rsArtUbic) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArtUbic = mysql_fetch_assoc($rsArtUbic);
			
			$idCasilla = $rowArtUbic['id_casilla'];
		}
		
		switch($row['id_estado_solicitud']) {
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Solicitado\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Aprobado\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Despachado\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Devuelto\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
			case 6 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			case 10 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"No Despachado\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$mostrar = false;
		$porcIva = ($row['id_iva'] != "" && $row['id_iva'] != "0") ? $row['iva']."%" : "-";
		$ivaRepuesto = round(($row['precio_unitario'] * $row['iva']) / 100, 2);
		
		$btnAprobar = "";
		$btnDesaprobar = "";
		$btnDespachar = "";
		$btnDevolver = "";
		$btnAnular = "";
		if ($idAccionSolicitud == 2 && $row['id_estado_solicitud'] == 1) {
			$mostrar = true;
			$btnAprobar = sprintf("<img class=\"puntero\" id=\"imgAprobarRepuesto%s\" onclick=\"xajax_aprobarRepuesto('%s',xajax.getFormValues('frmListaSolicitudDetalle'));\" src=\"../img/iconos/accept.png\" title=\"Aprobar\"/>",
				$contFila,
				$contFila);
		} else if ($idAccionSolicitud == 2 && $row['id_estado_solicitud'] == 2 && strtotime($row['tiempo_aprobacion']) == false) {
			$mostrar = true;
			$btnAprobar = sprintf("<img class=\"puntero\" id=\"imgDeshacerAprobarRepuesto%s\" onclick=\"xajax_aprobarRepuesto('%s',xajax.getFormValues('frmListaSolicitudDetalle'));\" src=\"../img/iconos/return.png\" title=\"Deshacer\"/>",
				$contFila,
				$contFila);
		}
		if ($idAccionSolicitud == 1 && $row['id_estado_solicitud'] == 2) {
			$mostrar = true;
			$btnDesaprobar = sprintf("<img class=\"puntero\" id=\"imgDesaprobarRepuesto%s\" onclick=\"xajax_aprobarRepuesto('%s',xajax.getFormValues('frmListaSolicitudDetalle'));\" src=\"../img/iconos/delete.png\" title=\"Desaprobar\"/>",
				$contFila,
				$contFila);
		} else if ($idAccionSolicitud == 1 && $row['id_estado_solicitud'] == 1 && strtotime($row['tiempo_aprobacion']) != false) {
			$mostrar = true;
			$btnDesaprobar = sprintf("<img class=\"puntero\" id=\"imgDeshacerDesaprobarRepuesto%s\" onclick=\"xajax_aprobarRepuesto('%s',xajax.getFormValues('frmListaSolicitudDetalle'));\" src=\"../img/iconos/return.png\" title=\"Deshacer\"/>",
				$contFila,
				$contFila);
		}
		if ($idAccionSolicitud == 3 && $row['id_estado_solicitud'] == 2) {
			$mostrar = true;
			$btnDespachar = sprintf("<img class=\"puntero\" id=\"imgDespacharRepuesto%s\" onclick=\"xajax_despacharRepuesto('%s',xajax.getFormValues('frmListaSolicitudDetalle'));\" src=\"../img/iconos/ico_aceptar_azul.png\" title=\"Despachar\"/>",
				$contFila,
				$contFila);
		} else if ($idAccionSolicitud == 3 && $row['id_estado_solicitud'] == 3 && strtotime($row['tiempo_despacho']) == false) {
			$mostrar = true;
			$btnDespachar = sprintf("<img class=\"puntero\" id=\"imgDeshacerDespacharRepuesto%s\" onclick=\"xajax_aprobarRepuesto('%s',xajax.getFormValues('frmListaSolicitudDetalle'));\" src=\"../img/iconos/return.png\" title=\"Deshacer\"/>",
				$contFila,
				$contFila);
		}
		if ($idAccionSolicitud == 4 && $row['id_estado_solicitud'] == 3) {
			$mostrar = true;
			$btnDevolver = sprintf("<img class=\"puntero\" id=\"imgDevolverRepuesto%s\" onclick=\"xajax_devolverRepuesto('%s',xajax.getFormValues('frmListaSolicitudDetalle'));\" src=\"../img/iconos/ico_aceptar_f2.gif\" title=\"Devolver\"/>",
				$contFila,
				$contFila);
		} else if ($idAccionSolicitud == 4 && $row['id_estado_solicitud'] == 4 && strtotime($row['tiempo_devolucion']) == false) {
			$mostrar = true;
			$btnDevolver = sprintf("<img class=\"puntero\" id=\"imgDeshacerDevolverRepuesto%s\" onclick=\"xajax_despacharRepuesto('%s',xajax.getFormValues('frmListaSolicitudDetalle'));\" src=\"../img/iconos/return.png\" title=\"Deshacer\"/>",
				$contFila,
				$contFila);
		}
		if ($idAccionSolicitud == 6 && $row['id_estado_solicitud'] == 1) {
			$mostrar = true;
			$btnAnular = sprintf("<img class=\"puntero\" id=\"imgAnularRepuesto%s\" onclick=\"xajax_anularRepuesto('%s',xajax.getFormValues('frmListaSolicitudDetalle'));\" src=\"../img/iconos/cross.png\" title=\"Anular\"/>",
				$contFila,
				$contFila);
		} else if ($idAccionSolicitud == 6 && $row['id_estado_solicitud'] == 6 && strtotime($row['tiempo_anulacion']) == false) {
			$mostrar = true;
			$btnAnular = sprintf("<img class=\"puntero\" id=\"imgDeshacerAnularRepuesto%s\" onclick=\"xajax_aprobarRepuesto('%s',xajax.getFormValues('frmListaSolicitudDetalle'));\" src=\"../img/iconos/return.png\" title=\"Deshacer\"/>",
				$contFila,
				$contFila);
		}
		
		$hddIdArticuloAlmacenCosto = $row['id_articulo_almacen_costo'];
		$hddIdArticuloCosto = $row['id_articulo_costo'];
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td nowrap=\"nowrap\">";
			if ($mostrar == true) {
				$htmlTb .= "<input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"".$row['id_det_solicitud_repuesto']."\"/>";
				$htmlTb .= "<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"".$contFila."\">";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= substr(utf8_encode($row['descripcion']),0,40);
				$htmlTb .= (in_array($ResultConfig12, array(1,2)) ? "" : "<br><span id=\"spnLote".$contFila."\" class=\"textoNegrita_9px\">LOTE: ".$hddIdArticuloCosto."</span>");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= "<span class=\"textoNegrita_10px\" id=\"spnAlmacen".$contFila."\">".utf8_encode($rowArtUbic['descripcion_almacen'])."</span>";
				$htmlTb .= "<br>";
				$htmlTb .= "<span id=\"spnUbicacion".$contFila."\">".utf8_encode(str_replace("-[]", "", $rowArtUbic['ubicacion']))."</span>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<input type=\"text\" id=\"hddCantArt%s\" name=\"hddCantArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>",
					$contFila,
					$contFila,
					$row['cantidad']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_unitario'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".$porcIva."</td>";
			$htmlTb .= "<td align=\"right\">".number_format(($row['precio_unitario'] + $ivaRepuesto),2,".",",")."</td>";
			$htmlTb .= "<td>";
			if ($idAccionSolicitud == 2 && $row['id_estado_solicitud'] == 1) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"imgCambiarUbic%s\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblAlmacen', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_cambio.png\" title=\"Cambiar Ubicación\"/></a>",
					$contFila,
					$contFila);
			}
				$htmlTb .= sprintf(
				"<input type=\"hidden\" id=\"hddIdArticuloAlmacenCosto%s\" name=\"hddIdArticuloAlmacenCosto%s\" title=\"Ubicacion del Lote\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArticuloCosto%s\" name=\"hddIdArticuloCosto%s\" title=\"Lote\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" title=\"Casilla\" value=\"%s\"/>",
					$contFila, $contFila, $hddIdArticuloAlmacenCosto,
					$contFila, $contFila, $hddIdArticuloCosto,
					$contFila, $contFila, $idCasilla);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".$btnAprobar."</td>";
			$htmlTb .= "<td>".$btnDesaprobar."</td>";
			$htmlTb .= "<td>".$btnDespachar."</td>";
			$htmlTb .= "<td>".$btnDevolver."</td>";
			$htmlTb .= "<td>".$btnAnular;
				$htmlTb .= sprintf("<input type=\"hidden\" id=\"hddIdSolicitudDetalle%s\" name=\"hddIdSolicitudDetalle%s\" readonly=\"readonly\" value=\"%s\"/>",
					$contFila,
					$contFila,
					$row['id_det_solicitud_repuesto']);
			if ($mostrar == true) {
				$htmlTb .= sprintf("<input type=\"hidden\" id=\"hddSolicitudDetalleAccion%s\" name=\"hddSolicitudDetalleAccion%s\" readonly=\"readonly\" size=\"2\" value=\"%s\"/>",
					$contFila,
					$contFila,
					$row['id_estado_solicitud']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaSolicitudDetalle(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitudDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaSolicitudDetalle","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaSolicitud($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// ACTUALIZA EL ESTATUS DE LA SOLICITUD
	/*$Result1 = actualizarEstatusSolicitudRepuestos();
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	// ACTUALIZA LOS SALDOS DEL ARTICULO (RESERVADA)
	$Result1 = actualizarReservada();
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }*/
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(orden.id_estado_orden NOT IN (SELECT id_estado_orden FROM sa_estado_orden WHERE tipo_estado LIKE 'FINALIZADO'))");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("orden.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(sol_rep.tiempo_solicitud) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estado_solicitud = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(orden.numero_orden LIKE %s
		OR sol_rep.numero_solicitud LIKE %s
		OR vw_sa_vale_recep.placa LIKE %s
		OR (SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
			FROM cj_cc_cliente cliente
			WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
											FROM sa_recepcion r
											WHERE r.id_recepcion = orden.id_recepcion), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																			FROM sa_cita c
																			WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																							FROM sa_recepcion r
																							WHERE r.id_recepcion = orden.id_recepcion))) AS id_cliente)) LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT
		orden.id_orden,
		orden.numero_orden,
		orden.tiempo_orden,
		tp_orden.nombre_tipo_orden,
		orden.id_estado_orden,
		estado_orden.nombre_estado AS descripcion_estado_orden,
		sol_rep.id_solicitud,
		sol_rep.numero_solicitud,
		sol_rep.tiempo_solicitud,
		sol_rep.estado_solicitud,
		sol_rep.id_usuario_bloqueo,
		
		(SELECT vw_pg_empleado.nombre_empleado
		FROM pg_usuario usuario
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (usuario.id_empleado = vw_pg_empleado.id_empleado)
		WHERE usuario.id_usuario = sol_rep.id_usuario_bloqueo) AS nombre_empleado_bloqueo,
		
		vw_sa_vale_recep.placa,
		
		(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
		FROM cj_cc_cliente cliente
		WHERE cliente.id = (SELECT IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
										FROM sa_recepcion r
										WHERE r.id_recepcion = orden.id_recepcion), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																		FROM sa_cita c
																		WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																						FROM sa_recepcion r
																						WHERE r.id_recepcion = orden.id_recepcion))) AS id_cliente)) AS nombre_cliente,
		
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud) AS cantidad,
		
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 1) AS cantidad_solicitada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 2
			AND tiempo_aprobacion > 0) AS cantidad_aprobada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 3
			AND tiempo_despacho > 0) AS cantidad_despachada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 4
			AND tiempo_devolucion > 0) AS cantidad_devuelta,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 5) AS cantidad_facturada,
			
		(SELECT COUNT(det_sol_rep.id_estado_solicitud) FROM sa_det_solicitud_repuestos det_sol_rep
		WHERE det_sol_rep.id_solicitud = sol_rep.id_solicitud
			AND det_sol_rep.id_estado_solicitud = 10) AS cantidad_no_despachada,
		
		orden.id_usuario_bloqueo AS id_usuario_bloqueo_orden,
		
		(SELECT vw_pg_empleado.nombre_empleado
		FROM pg_usuario usuario
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (usuario.id_empleado = vw_pg_empleado.id_empleado)
		WHERE usuario.id_usuario = orden.id_usuario_bloqueo) AS nombre_empleado_bloqueo_orden,
		
		(SELECT estado_orden.color_estado FROM sa_estado_orden estado_orden
		WHERE estado_orden.id_estado_orden = orden.id_estado_orden) AS color_estado,
		
		(SELECT estado_orden.color_fuente FROM sa_estado_orden estado_orden
		WHERE estado_orden.id_estado_orden = orden.id_estado_orden) AS color_fuente
		
	FROM sa_orden orden
		INNER JOIN sa_tipo_orden tp_orden ON (orden.id_tipo_orden = tp_orden.id_tipo_orden)
		INNER JOIN sa_estado_orden estado_orden ON (orden.id_estado_orden = estado_orden.id_estado_orden)
		INNER JOIN vw_sa_vales_recepcion vw_sa_vale_recep ON (orden.id_recepcion = vw_sa_vale_recep.id_recepcion)
		INNER JOIN sa_solicitud_repuestos sol_rep ON (orden.id_orden = sol_rep.id_orden) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "7%", $pageNum, "tiempo_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "10%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "10%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Orden");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "10%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Orden");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "10%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Placa");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "45%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaSolicitud", "8%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= "<td colspan=\"6\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estado_solicitud']) {
			case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Abierta\"/>"; break;
			case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Aprobada\"/>"; break;
			case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Despachada\"/>"; break;
			case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Devuelto\"/>"; break;
			case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturada\"/>"; break;
			case 6 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulada\"/>"; break;
			case 7 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo_parcial.gif\" title=\"Aprobada Parcial\"/>"; break;
			case 8 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja_parcial.gif\" title=\"Despachada Parcial\"/>"; break;
			case 9 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris_parcial.gif\" title=\"Devolucion Parcial\"/>"; break;
			default : $imgEstatusPedido = "";
		}
		
		$imgEstatusBloqueo = "";
		if ($row['id_usuario_bloqueo'] > 0) {
			$imgEstatusBloqueo = "<img src=\"../img/iconos/lock.png\" title=\"Solicitud Bloqueda por ".utf8_encode($row['nombre_empleado_bloqueo'])."\"/>";
		}
		
		$imgEstatusBloqueoOrden = "";
		if ($row['id_usuario_bloqueo_orden'] > 0) {
			$imgEstatusBloqueoOrden = "<img src=\"../img/iconos/lock.png\" title=\"Orden Bloqueda por ".utf8_encode($row['nombre_empleado_bloqueo_orden'])."\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$imgEstatusBloqueo."</td>";
			$htmlTb .= "<td>".$imgEstatusBloqueoOrden."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['tiempo_solicitud']))."</td>";
			$htmlTb .= "<td align=\"right\" title=\"Id Solicitud: ".$row['id_solicitud']."\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td align=\"right\" title=\"Id Orden: ".$row['id_orden']."\">".$row['numero_orden']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"center\" width=\"100%\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
				$htmlTb .= "<tr>";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td align=\"center\" style=\"background:#".$row['color_estado']."; color:#".$row['color_fuente']."\">".utf8_encode($row['descripcion_estado_orden'])."</td>";
				$htmlTb .= "<tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblSolicitud', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_examinar.png\" title=\"Ver Detalle\"/></a>",
					$row['id_solicitud']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cantidad_solicitada'] > 0) {
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblSolicitud', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/accept.png\" title=\"Aprobar\"/></a>",
					$row['id_solicitud'],
					2); // 2 = APROBAR
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cantidad_aprobada'] > 0) {
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblSolicitud', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Desaprobar\"/></a>",
					$row['id_solicitud'],
					1); // 1 = SOLICITADO
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cantidad_aprobada'] > 0) {
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblSolicitud', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_despachar.png\" title=\"Despachar\"/></a>",
					$row['id_solicitud'],
					3); // 3 = DESPACHADO
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cantidad_despachada'] > 0) {
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblSolicitud', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/ico_transferir_para_almacen.gif\" title=\"Devolver\"/></a>",
					$row['id_solicitud'],
					4); // 4 = DEVUELTO
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cantidad_solicitada'] > 0) {
				$htmlTb .= sprintf("<a class=\"modalImg\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblSolicitud', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/cross.png\" title=\"Anular\"/></a>",
					$row['id_solicitud'],
					6); // 4 = ANULADA
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"16\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaSolicitud(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"16\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaSolicitud","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function validarAccion($frmListaSolicitudDetalle) {
	$objResponse = new xajaxResponse();
	
	// VERIFICA LA CANTIDAD DE ITEMS QUE HAN SIDO MODIFICADOS
	$arrayObj = $frmListaSolicitudDetalle['cbx'];
	
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmListaSolicitudDetalle['hddIdAccionRealizada'] == ""
			|| $frmListaSolicitudDetalle['hddSolicitudDetalleAccion'.$valor] == $frmListaSolicitudDetalle['hddIdAccionRealizada']) {
				$contObj++;
			}
		}
	}
	
	$objResponse->assign("hddCantActivas","value",$contObj);
	
	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"accionBloque");
$xajax->register(XAJAX_FUNCTION,"aprobarRepuesto");
$xajax->register(XAJAX_FUNCTION,"anularRepuesto");
$xajax->register(XAJAX_FUNCTION,"asignarAlmacen");
$xajax->register(XAJAX_FUNCTION,"asignarDisponibilidadUbicacion");
$xajax->register(XAJAX_FUNCTION,"bloquearSolicitud");
$xajax->register(XAJAX_FUNCTION,"buscarSolicitud");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstatusSolicitud");
$xajax->register(XAJAX_FUNCTION,"desbloquearSolicitud");
$xajax->register(XAJAX_FUNCTION,"despacharRepuesto");
$xajax->register(XAJAX_FUNCTION,"devolverRepuesto");
$xajax->register(XAJAX_FUNCTION,"formAlmacen");
$xajax->register(XAJAX_FUNCTION,"formSolicitudRepuesto");
$xajax->register(XAJAX_FUNCTION,"guardarSolicitud");
$xajax->register(XAJAX_FUNCTION,"listaComprobanteSurtido");
$xajax->register(XAJAX_FUNCTION,"listaSolicitud");
$xajax->register(XAJAX_FUNCTION,"listaSolicitudDetalle");
$xajax->register(XAJAX_FUNCTION,"validarAccion");


function verificarBloqueoOrden($idOrden){
	$query = sprintf("SELECT id_usuario_bloqueo FROM sa_orden WHERE id_orden = %s;",
		valTpDato($idOrden, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);

	$bloqueado = ($row['id_usuario_bloqueo'] > 0) ? true : false;

	return $bloqueado;
}
?>