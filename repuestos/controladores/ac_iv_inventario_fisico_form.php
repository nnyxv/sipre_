<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function asignarArticulo($frmInventario, $frmBuscarNumeroPosicion) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmInventario['txtIdEmpresa'];
	$idInventarioFisico = $frmInventario['txtIdInventarioFisico'];
	
	if ($frmInventario['hddTipoConteo'] == 1) {
		$numeroPosicion = $frmBuscarNumeroPosicion['txtNumero'];
		
		// BUSCA SI EXISTE EL NUMERO DEL ARTICULO EN EL LISTADO DEL INVENTARIO FISICO
		$queryInvFisDetalle = sprintf("SELECT * FROM iv_inventario_fisico_detalle
		WHERE id_inventario_fisico = %s
			AND numero = %s;",
			valTpDato($idInventarioFisico, "int"),
			valTpDato($numeroPosicion, "int"));
		$rsInvFisDetalle = mysql_query($queryInvFisDetalle);
		if (!$rsInvFisDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsInvFisDetalle = mysql_num_rows($rsInvFisDetalle);
		$rowInvFisDetalle = mysql_fetch_assoc($rsInvFisDetalle);
		
	} else if ($frmInventario['hddTipoConteo'] == 2) {
		$idArticulo = $frmBuscarNumeroPosicion['txtNumero'];
		
		$queryArtEmp = sprintf("SELECT * FROM vw_iv_articulos_empresa
		WHERE id_empresa = %s
			AND id_articulo = %s;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"));
		$rsArtEmp = mysql_query($queryArtEmp);
		if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
		
		// BUSCA SI EXISTE EL NUMERO DEL ARTICULO EN EL LISTADO DEL INVENTARIO FISICO
		$queryInvFisDetalle = sprintf("SELECT * FROM iv_inventario_fisico_detalle
		WHERE id_inventario_fisico = %s
			AND id_articulo = %s;",
			valTpDato($idInventarioFisico, "int"),
			valTpDato($idArticulo, "int"));
		$rsInvFisDetalle = mysql_query($queryInvFisDetalle);
		if (!$rsInvFisDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsInvFisDetalle = mysql_num_rows($rsInvFisDetalle);
		$rowInvFisDetalle = mysql_fetch_assoc($rsInvFisDetalle);
	}
	
	if ($totalRowsInvFisDetalle > 0) {
		if ($rowInvFisDetalle['habilitado'] == "1") {
			$objResponse->script("
			document.forms['frmArticuloManual'].reset();
			byId('txtDescripcionArt').innerHTML = '';");
			
			// BUSCA LOS DATOS DEL INVENTARIO FISICO
			$queryInvFis = sprintf("SELECT * FROM iv_inventario_fisico WHERE id_inventario_fisico = %s;",
				valTpDato($idInventarioFisico, "int"));
			$rsInvFis = mysql_query($queryInvFis);
			if (!$rsInvFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowInvFis = mysql_fetch_assoc($rsInvFis);
		
			// BUSCA LOS DATOS DEL ARTICULO
			$queryArticulo = sprintf("SELECT * FROM vw_iv_articulos_datos_basicos WHERE id_articulo = %s",
				valTpDato($rowInvFisDetalle['id_articulo'], "int"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArticulo = mysql_fetch_assoc($rsArticulo);
			
			$objResponse->script("$('#trInventarioFisicoDetalle".$rowInvFisDetalle['numero']."').find('td').attr('class', 'divMsjInfo');");
			
			$objResponse->assign("txtCodigoArticulo","value",elimCaracter($rowArticulo['codigo_articulo'],";"));
			$objResponse->assign("hddIdInvFisicoDet","value",$rowInvFisDetalle['id_inventario_fisico_detalle']);
			$objResponse->assign("hddIdArticulo","value",$rowArticulo['id_articulo']);
			$objResponse->assign("txtCantidadArt","value",valTpDato($rowInvFisDetalle['conteo_'.$rowInvFis['numero_conteo']],"cero_por_vacio"));
			
			$objResponse->assign("txtCodigoArt","value",elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"));
			$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
			$objResponse->assign("txtMarcaArt","value",utf8_encode($rowArticulo['marca']));
			$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['tipo_articulo']));
			
			$objResponse->script(sprintf("
			if (navigator.appName == 'Netscape') {
				byId('txtCantidadArt').onblur = function(e){ %s }
				byId('txtCantidadArt').onkeypress = function(e){ %s }
			} else if (navigator.appName == 'Microsoft Internet Explorer') {
				byId('txtCantidadArt').onblur = function(e){ %s }
				byId('txtCantidadArt').onkeypress = function(e){ %s }
			}",
				(($rowArticulo['decimales'] == 0) ? "setFormatoRafk(this,0);" : "setFormatoRafk(this,2);"),
				(($rowArticulo['decimales'] == 0) ? "return validarSoloNumeros(e);" : "return validarSoloNumerosReales(e);"),
				(($rowArticulo['decimales'] == 0) ? "setFormatoRafk(this,0);" : "setFormatoRafk(this,2);"),
				(($rowArticulo['decimales'] == 0) ? "return validarSoloNumeros(event);" : "return validarSoloNumerosReales(event);")));
			
			$objResponse->script("
			byId('tblDatosArticulo').style.display = '';
			
			byId('trCantidad').style.display = '';
			
			byId('txtNumero').readOnly = true;
			byId('txtNumero').className = '';
			byId('txtCantidadArt').focus();
			byId('txtCantidadArt').select();");
		} else if ($rowInvFisDetalle['habilitado'] == "0") {
			$objResponse->script(sprintf("
			alertaJquery('divMsj','%s','2500');",
				utf8_encode("Este Artículo no está Habilitado para el Conteo Actual")));
			
			$objResponse->assign("txtNumero","value",$numeroPosicion + 1);
		}
	} else {
		$objResponse->script("
		byId('tblDatosArticulo').style.display = 'none';
		
		byId('trCantidad').style.display = 'none';
		
		byId('txtNumero').readOnly = false;
		byId('txtNumero').className = 'inputHabilitado';
		byId('txtNumero').focus();
		byId('txtNumero').select();");
		
		$objResponse->script("document.forms['frmBuscarNumeroPosicion'].reset();");
		$objResponse->script(sprintf("errorJquery('divMsj','%s','2500');",
			utf8_encode("El Código de Artículo no está Incluido para este Inventario")));
	}
	
	return $objResponse;
}

function buscarCodigoArticulo($frmBuscarCodigoArticulo, $frmInventario) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmInventario['txtIdEmpresa'];
	$idInventarioFisico = $frmInventario['txtIdInventarioFisico'];
	
	if (isset($frmBuscarCodigoArticulo['hddCantCodigo'])){
		for ($cont = 0; $cont <= $frmBuscarCodigoArticulo['hddCantCodigo']; $cont++) {
			$codArticulo .= $frmBuscarCodigoArticulo['txtCodigoArticulo'.$cont].";";
			$codArticuloAux .= $frmBuscarCodigoArticulo['txtCodigoArticulo'.$cont];
		}
		$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
		$codArticulo = (strlen($codArticuloAux) > 0) ? codArticuloExpReg($codArticulo) : "";
	}
	
	$query = sprintf("SELECT * FROM vw_iv_inventario_fisico_detalle
	WHERE id_empresa = %s
		AND id_inventario_fisico = %s
		AND codigo_articulo REGEXP %s
	ORDER BY numero ASC;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idInventarioFisico, "int"),
		valTpDato($codArticulo, "text"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td width=\"6%\">".utf8_encode("Nro.")."</td>";
		$htmlTh .= "<td width=\"18%\">".utf8_encode("Ubicación")."</td>";
		$htmlTh .= "<td width=\"18%\">".utf8_encode("Código")."</td>";
		$htmlTh .= "<td width=\"44%\">".utf8_encode("Descripción")."</td>";
		$htmlTh .= "<td width=\"14%\">".utf8_encode("Lote")."</td>";
	$htmlTh .= "</tr>";
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		//$classEstatusAlmacen = ($row['estatus_articulo_almacen'] == 1) ? "class=\"texto_9px\"" : "class=\"divMsjError texto_9px\"";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"byId('txtNumero').value = ".$row['numero']."; byId('btnCancelarListaCodigoArticulo').click(); byId('btnBuscarNumeroPosicion').click();\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".$row['numero']."</td>";
			$htmlTb .= "<td align=\"center\" ".$classEstatusAlmacen." nowrap=\"nowrap\">";
				$htmlTb .= utf8_encode($row['descripcion_almacen'])."<br><span class=\"textoNegrita_10px\">".utf8_encode(str_replace("-[]", "", $row['ubicacion']))."</span>";
				//$htmlTb .= ($row['estatus_articulo_almacen'] == 1) ? "" : "<br><span class=\"textoRojoNegrita_10px\">".utf8_encode("Relacion Inactiva")."</span>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_articulo_costo'])."</td>";
		$htmlTb .= "</tr>";
	}
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"15\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaCodigoArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function cargarInventarioFisico($idInventarioFisico) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_inventario_fisico WHERE id_inventario_fisico = %s;",
		valTpDato($idInventarioFisico, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	// DATOS DE LA EMPRESA
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_empresa_reg = %s;",
		valTpDato($row['id_empresa'], "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$nombreSucursal = ($rowEmpresa['id_empresa_padre_suc'] > 0) ? $rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")" : "";
	
	$objResponse->assign("txtIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal));
	
	$objResponse->loadCommands(encabezadoEmpresa($rowEmpresa['id_empresa']));
	
	$objResponse->loadCommands(objetoCodigoDinamico("tdCodigoArt", $rowEmpresa['id_empresa_reg']));
	
	$objResponse->assign("txtIdEmpleado","value",$row['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($row['nombre_empleado']));
	$objResponse->assign("txtIdInventarioFisico","value",$row['id_inventario_fisico']);
	
	$objResponse->assign("txtFecha","value",date(spanDateFormat, strtotime($row['fecha'])));
	$objResponse->assign("txtHora","value",$row['hora']);
	
	$objResponse->assign("hddTipoConteo","value",$row['tipo_conteo']);
	$objResponse->assign("txtTipoConteo","value",utf8_encode($row['tipo_conteo_descripcion']));
	
	$objResponse->assign("txtFiltroArticulos","value",utf8_encode($row['filtro_conteo_descripcion']));
	$objResponse->assign("txtOrdenArticulos","value",utf8_encode($row['orden_conteo_descripcion']));
	$objResponse->assign("txtCantidadConteos","value",utf8_encode($row['cantidad_conteo']));
	
	$objResponse->assign("hddNumeroConteo","value",$row['numero_conteo']);
	
	if ($row['tipo_conteo'] == 1) {
		$objResponse->assign("spanTituloBuscar","innerHTML",utf8_encode("Nro."));
	} else if ($row['tipo_conteo'] == 2) {
		$objResponse->assign("spanTituloBuscar","innerHTML",utf8_encode("Código Barra"));
	}
	
	$valBusq = sprintf("%s|%s",
		$row['id_empresa'],
		$row['id_inventario_fisico']);
		
	$objResponse->loadCommands(listaArticulosInventario(0, "numero", "ASC", $valBusq));
	
	if ($row['estatus'] == 0) {
		if ($row['cantidad_conteo'] == 2) {
			if ($row['numero_conteo'] == 2) {
				$objResponse->script("
				byId('btnNuevoConteo').style.display = 'none';
				byId('aVerComparativo').style.display = '';
				byId('aAjusteInventario').style.display = '';");
			} else {
				$objResponse->script("
				byId('btnNuevoConteo').style.display = '';
				byId('aVerComparativo').style.display = '';
				byId('aAjusteInventario').style.display = 'none';");
			}
		} else if ($row['cantidad_conteo'] == 3) {
			if ($row['numero_conteo'] == 3) {
				$objResponse->script("
				byId('btnNuevoConteo').style.display = 'none';
				byId('aVerComparativo').style.display = '';
				byId('aAjusteInventario').style.display = '';");
			} else {
				$objResponse->script("
				byId('btnNuevoConteo').style.display = '';
				byId('aVerComparativo').style.display = 'none';
				byId('aAjusteInventario').style.display = 'none';");
			}
		}
		
		$objResponse->script("
		byId('trFormConteo').style.display = '';
	
		byId('txtNumero').focus();
		byId('txtNumero').select();");
	} else {
		$objResponse->script("
		byId('trFormConteo').style.display = 'none';
		
		byId('btnNuevoConteo').style.display = 'none';
		byId('aVerComparativo').style.display = '';
		byId('aAjusteInventario').style.display = 'none';");
	}
	
	return $objResponse;
}

function formImprimir($frmInventario) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_inventario_fisico_imprimir")) { return $objResponse; }
	
	$query = sprintf("SELECT * FROM vw_iv_inventario_fisico WHERE id_inventario_fisico = %s;",
		valTpDato($frmInventario['txtIdInventarioFisico'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['numero_conteo'] == 1) {
		$objResponse->script("
		byId('cbxImpConteo1').checked = true;
		
		byId('cbxImpConteo1').disabled = false;
		byId('cbxImpConteo2').disabled = true;
		byId('cbxImpConteo3').disabled = true;
		byId('cbxImpInvFisico').disabled = true;
		byId('cbxImpFaltantes').disabled = true;
		byId('cbxImpSobrantes').disabled = true;
		byId('cbxImpSalida').disabled = true;
		byId('cbxImpEntrada').disabled = true;");
	}
	
	if ($row['cantidad_conteo'] == 2) {
		$objResponse->script("byId('legendCbxImpConteo3').style.display = 'none';");
		if ($row['numero_conteo'] == 2) {
			if ($row['estatus'] == 0) {
				$objResponse->script("
				byId('cbxImpConteo2').checked = true;
				
				byId('cbxImpConteo1').disabled = false;
				byId('cbxImpConteo2').disabled = false;
				byId('cbxImpConteo3').disabled = true;
				byId('cbxImpInvFisico').disabled = true;
				byId('cbxImpFaltantes').disabled = true;
				byId('cbxImpSobrantes').disabled = true;
				byId('cbxImpSalida').disabled = true;
				byId('cbxImpEntrada').disabled = true;");
			} else if ($row['estatus'] == 1) {
				$objResponse->script("
				byId('cbxImpConteo1').disabled = false;
				byId('cbxImpConteo2').disabled = false;
				byId('cbxImpConteo3').disabled = true;
				byId('cbxImpInvFisico').disabled = false;
				byId('cbxImpFaltantes').disabled = false;
				byId('cbxImpSobrantes').disabled = false;
				byId('cbxImpSalida').disabled = false;
				byId('cbxImpEntrada').disabled = false;");
			}
		}
	} else if ($row['cantidad_conteo'] == 3) {
		$objResponse->script("byId('legendCbxImpConteo3').style.display = '';");
		if ($row['numero_conteo'] == 2) {
			$objResponse->script("
			byId('cbxImpConteo2').checked = true;
			
			byId('cbxImpConteo1').disabled = false;
			byId('cbxImpConteo2').disabled = false;
			byId('cbxImpConteo3').disabled = true;
			byId('cbxImpInvFisico').disabled = true;
			byId('cbxImpFaltantes').disabled = true;
			byId('cbxImpSobrantes').disabled = true;
			byId('cbxImpSalida').disabled = true;
			byId('cbxImpEntrada').disabled = true;");
		} else if ($row['numero_conteo'] == 3) {
			if ($row['estatus'] == 0) {
				$objResponse->script("
				byId('cbxImpConteo3').checked = true;
				
				byId('cbxImpConteo1').disabled = false;
				byId('cbxImpConteo2').disabled = false;
				byId('cbxImpConteo3').disabled = false;
				byId('cbxImpInvFisico').disabled = true;
				byId('cbxImpFaltantes').disabled = true;
				byId('cbxImpSobrantes').disabled = true;
				byId('cbxImpSalida').disabled = true;
				byId('cbxImpEntrada').disabled = true;");
			} else if ($row['estatus'] == 1) {
				$objResponse->script("
				byId('cbxImpConteo1').disabled = false;
				byId('cbxImpConteo2').disabled = false;
				byId('cbxImpConteo3').disabled = false;
				byId('cbxImpInvFisico').disabled = false;
				byId('cbxImpFaltantes').disabled = false;
				byId('cbxImpSobrantes').disabled = false;
				byId('cbxImpSalida').disabled = false;
				byId('cbxImpEntrada').disabled = false;");
			}
		}
	}
	
	return $objResponse;
}

function formImprimirInvComparativo($frmInventario) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"iv_inventario_fisico_imprimir")) { return $objResponse; }
	
	$query = sprintf("SELECT * FROM vw_iv_inventario_fisico WHERE id_inventario_fisico = %s;",
		valTpDato($frmInventario['txtIdInventarioFisico'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['estatus'] == 1) {
		if ($row['cantidad_conteo'] == 2) {
			$objResponse->script("byId('tdRbtImpConteo3').style.display = 'none';");
			
			$objResponse->script("
			byId('rbtImpConteo1').checked = false;
			byId('rbtImpConteo2').checked = true;
			
			byId('rbtImpConteo1').disabled = false;
			byId('rbtImpConteo2').disabled = false;");
		} else if ($row['cantidad_conteo'] == 3) {
			$objResponse->script("
			byId('rbtImpConteo1').checked = false;
			byId('rbtImpConteo2').checked = false;
			byId('rbtImpConteo3').checked = true;
			
			byId('rbtImpConteo1').disabled = false;
			byId('rbtImpConteo2').disabled = false;
			byId('rbtImpConteo3').disabled = false;");
		}
	} else {
		if ($row['numero_conteo'] == 1) {
			$objResponse->script("
			byId('rbtImpConteo1').checked = false;
			byId('rbtImpConteo2').disabled = true;");
		}
		
		if ($row['cantidad_conteo'] == 2) {
			$objResponse->script("byId('tdRbtImpConteo3').style.display = 'none';");
			if ($row['numero_conteo'] == 2) {
				if ($row['estatus'] == 0) {
					$objResponse->script("
					byId('rbtImpConteo2').disabled = false;
					byId('rbtImpConteo2').checked = true;");
				}
			}
		} else if ($row['cantidad_conteo'] == 3) {
			$objResponse->script("byId('tdRbtImpConteo3').style.display = '';");
			if ($row['numero_conteo'] == 2) {
				$objResponse->script("
				byId('rbtImpConteo2').disabled = false;
				byId('rbtImpConteo2').checked = true;");
			} else if ($row['numero_conteo'] == 3) {
				if ($row['estatus'] == 0) {
					$objResponse->script("
					byId('rbtImpConteo2').disabled = false;
					byId('rbtImpConteo3').checked = true;");
				}
			}
		}
	}
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	return $objResponse;
}

function guardarAjusteInventario($frmInventario) {
	$objResponse = new xajaxResponse();
	
	$idInventarioFisico = $frmInventario['txtIdInventarioFisico'];
	
	$queryInvFis = sprintf("SELECT * FROM iv_inventario_fisico WHERE id_inventario_fisico = %s",
		valTpDato($idInventarioFisico, "int"));
	$rsInvFis = mysql_query($queryInvFis);
	if (!$rsInvFis) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowInvFis = mysql_fetch_assoc($rsInvFis);
	
	$idEmpresa = $rowInvFis['id_empresa'];
	$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idClaveMovimientoEntrada = 18;
	$idClaveMovimientoSalida = 34;
	
	if ($rowInvFis['estatus'] == 0) {
		mysql_query("START TRANSACTION;");
	
		$query = sprintf("SELECT * FROM vw_iv_inventario_fisico_detalle WHERE id_inventario_fisico = %s
		ORDER BY numero ASC;",
			valTpDato($idInventarioFisico, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$idArticulo = $row['id_articulo'];
			$idCasilla = $row['id_casilla'];
			$hddIdArticuloAlmacenCosto = $row['id_articulo_almacen_costo'.$valor];
			$hddIdArticuloCosto = $row['id_articulo_costo'.$valor];
		
			$cantConteo = $row['conteo_'.$rowInvFis['numero_conteo']];
			$cantDiferencia = $cantConteo - $row['existencia_kardex'];
			$costoUnitario = $row['costo_proveedor'];
			
			$totalArticulo = $cantConteo * $costoUnitario;
			
			if ($cantDiferencia < 0) {
				$cantDiferencia = (-1) * $cantDiferencia;
				
				if (!($idValeSalida)) {
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
					
					// INSERTA LOS DATOS DEL VALE DE SALIDA
					$insertSQL = sprintf("INSERT INTO iv_vale_salida (numeracion_vale_salida, id_empresa, fecha, id_documento, id_cliente, tipo_vale_salida, observacion, id_empleado_creador)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($numeroActual, "int"),
						valTpDato($idEmpresa, "int"),
						valTpDato(date("Y-m-d"),"date"),
						valTpDato($idInventarioFisico, "int"),
						valTpDato($frmInventario['txtIdEmpleado'], "int"),
						valTpDato(5, "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
						valTpDato("INVENTARIO FISICO", "text"),
						valTpDato($frmInventario['txtIdEmpleado'], "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$idValeSalida = mysql_insert_id();
					
					$arrayIdDctoContabilidad[] = array(
						$idValeSalida,
						$idModulo,
						"SALIDA");
					
					// INSERTA EL MOVIMIENTO
					$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
					VALUE (%s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
						valTpDato(4, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($idClaveMovimientoSalida, "int"),
						valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
						valTpDato($idValeSalida, "int"),
						valTpDato(date("Y-m-d"), "date"),
						valTpDato($frmInventario['txtIdEmpleado'], "int"),
						valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
						valTpDato($_SESSION['idUsuarioSysGts'], "int"),
						valTpDato(1, "boolean")); // 0 = Credito, 1 = Contado
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$idMovimientoSalida = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
				
				// INSERTA EL DETALLE DEL DOCUMENTO
				$insertSQL = sprintf("INSERT INTO iv_vale_salida_detalle (id_vale_salida, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio_venta, costo_compra)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idValeSalida, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($cantDiferencia, "int"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"));
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idModulo, "int"),
					valTpDato($idValeSalida, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato(4, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($idClaveMovimientoSalida, "int"),
					valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
					valTpDato($cantDiferencia, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(1, "int"), // 0 = Entrada, 1 = Salida
					valTpDato("NOW()", "campo"),
					valTpDato("INVENTARIO FISICO", "text"),
					valTpDato("SYSDATE()", "campo"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// INSERTA EL DETALLE DEL MOVIMIENTO
				$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idMovimientoSalida, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idKardex, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($cantDiferencia, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(((0 * $costoUnitario) / 100), "real_inglesa"),
					valTpDato(0, "int"), // 0 = Unitario, 1 = Import
					valTpDato(0, "boolean"), // 0 = No, 1 = Si
					valTpDato("", "int"),
					valTpDato("", "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
				$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
				$Result1 = actualizarSaldos($idArticulo, $idCasilla);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// VERIFICA EL ALGUN ARTICULO TIENE ACTIVA LA UBICACION
				$queryUbicAct = sprintf("SELECT * FROM iv_articulos_almacen
				WHERE id_casilla = %s
					AND estatus = 1;",
					valTpDato($idCasilla, "int"));
				$rsUbicAct = mysql_query($queryUbicAct);
				if (!$rsUbicAct) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsUbicAct = mysql_num_rows($rsUbicAct);
				
				if ($totalRowsUbicAct == 0) {
					// ACTUALIZA EN ACTIVA LA UBICACION PARA EL ARTICULO
					$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
						estatus = 1
					WHERE id_articulo = %s
						AND id_casilla = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
					
				$subTotalSalida += $cantDiferencia * $costoUnitario;
				
			} else if ($cantDiferencia > 0) {
				if (!($idValeEntrada)) {
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
					
					// INSERTA LOS DATOS DEL VALE DE ENTRADA
					$insertSQL = sprintf("INSERT INTO iv_vale_entrada (numeracion_vale_entrada, id_empresa, fecha, id_documento, id_cliente, tipo_vale_entrada, observacion, id_empleado_creador)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($numeroActual, "int"),
						valTpDato($idEmpresa, "int"),
						valTpDato(date("Y-m-d"),"date"),
						valTpDato($idInventarioFisico, "int"),
						valTpDato($frmInventario['txtIdEmpleado'], "int"),
						valTpDato(5, "int"), // 1 = Normal, 2 = Factura, 3 = Nota Credito, 4 = Mov. Inter-Almacen 5 = Ajuste Inventario Fisico
						valTpDato("INVENTARIO FISICO", "text"),
						valTpDato($frmInventario['txtIdEmpleado'], "int"));
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$idValeEntrada = mysql_insert_id();
					
					$arrayIdDctoContabilidad[] = array(
						$idValeEntrada,
						$idModulo,
						"ENTRADA");
					
					// INSERTA EL MOVIMIENTO
					$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
					VALUE (%s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
						valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
						valTpDato($idClaveMovimientoEntrada, "int"),
						valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
						valTpDato($idValeEntrada, "int"),
						valTpDato(date("Y-m-d"), "date"),
						valTpDato($frmInventario['txtIdEmpleado'], "int"),
						valTpDato(0, "boolean"), // 0 = Unitario, 1 = Importe
						valTpDato($_SESSION['idUsuarioSysGts'], "int"),
						valTpDato(1, "boolean")); // 0 = Credito, 1 = Contado
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$idMovimientoEntrada = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
				}
				
				// INSERTA EL DETALLE DEL DOCUMENTO
				$insertSQL = sprintf("INSERT INTO iv_vale_entrada_detalle (id_vale_entrada, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio_venta, costo_compra)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idValeEntrada, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($cantDiferencia, "int"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				// REGISTRA EL MOVIMIENTO KARDEX DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO iv_kardex (id_modulo, id_documento, id_articulo, id_casilla, id_articulo_almacen_costo, id_articulo_costo, tipo_movimiento, id_clave_movimiento, tipo_documento_movimiento, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, estado, fecha_movimiento, observacion, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idModulo, "int"),
					valTpDato($idValeEntrada, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato(2, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($idClaveMovimientoEntrada, "int"),
					valTpDato(1, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
					valTpDato($cantDiferencia, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
					valTpDato("NOW()", "campo"),
					valTpDato("INVENTARIO FISICO", "text"),
					valTpDato("SYSDATE()", "campo"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// INSERTA EL DETALLE DEL MOVIMIENTO
				$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, id_articulo_almacen_costo, id_articulo_costo, cantidad, precio, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, tipo_costo, promocion, id_moneda_costo, id_moneda_costo_cambio)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idMovimientoEntrada, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idKardex, "int"),
					valTpDato($hddIdArticuloAlmacenCosto, "int"),
					valTpDato($hddIdArticuloCosto, "int"),
					valTpDato($cantDiferencia, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato($costoUnitario, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(0, "real_inglesa"),
					valTpDato(((0 * $costoUnitario) / 100), "real_inglesa"),
					valTpDato(0, "int"), // 0 = Unitario, 1 = Import
					valTpDato(0, "boolean"), // 0 = No, 1 = Si
					valTpDato("", "int"),
					valTpDato("", "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO
				$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS, ESPERA Y BLOQUEADAS)
				$Result1 = actualizarSaldos($idArticulo, $idCasilla);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// VERIFICA SI ALGUN ARTICULO TIENE ACTIVA LA UBICACION
				$queryUbicAct = sprintf("SELECT * FROM iv_articulos_almacen
				WHERE id_casilla = %s
					AND estatus = 1;",
					valTpDato($idCasilla, "int"));
				$rsUbicAct = mysql_query($queryUbicAct);
				if (!$rsUbicAct) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsUbicAct = mysql_num_rows($rsUbicAct);
				
				if ($totalRowsUbicAct == 0) {
					// ACTUALIZA EN ACTIVA LA UBICACION PARA EL ARTICULO
					$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
						estatus = 1
					WHERE id_articulo = %s
						AND id_casilla = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
				
				$subTotalEntrada += $cantDiferencia * $costoUnitario;
			}
		}
		
		// ACTUALIZA EL SUBTOTAL DEL DOCUMENTO
		if ($idValeEntrada > 0) {
			$updateSQL = sprintf("UPDATE iv_vale_entrada SET
				subtotal_documento = %s
			WHERE id_vale_entrada = %s;",
				valTpDato($subTotalEntrada, "real_inglesa"),
				valTpDato($idValeEntrada, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		// ACTUALIZA EL SUBTOTAL DEL DOCUMENTO
		if ($idValeSalida > 0) {
			$updateSQL = sprintf("UPDATE iv_vale_salida SET
				subtotal_documento = %s
			WHERE id_vale_salida = %s;",
				valTpDato($subTotalSalida, "real_inglesa"),
				valTpDato($idValeSalida, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
		
		// ACTUALIZA EL ESTATUS DEL INVENTARIO FISICO
		$updateSQL = sprintf("UPDATE iv_inventario_fisico SET
			estatus = 1
		WHERE id_inventario_fisico = %s;",
			valTpDato($idInventarioFisico, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->alert(utf8_encode("Ajuste de Inventario Realizado con Éxito"));
		
		$objResponse->script("byId('btnCancelarPermiso').click();");
		
		switch ($rowInvFis['cantidad_conteo']) {
			case 2 :
				$objResponse->script(sprintf("verVentana('reportes/iv_inventario_fisico_todo_pdf.php?valBusq=%s|%s|K|1|2|4|5|6|7|8',950,600);",
					$idInventarioFisico,
					$idEmpresa));
				break;
			case 3 :
				$objResponse->script(sprintf("verVentana('reportes/iv_inventario_fisico_todo_pdf.php?valBusq=%s|%s|K|1|2|3|4|5|6|7|8',950,600);",
					$idInventarioFisico,
					$idEmpresa));
				break;
		}
		
		if (isset($arrayIdDctoContabilidad)) {
			foreach ($arrayIdDctoContabilidad as $indice => $valor) {
				$idModulo = $arrayIdDctoContabilidad[$indice][1];
				$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
				
				// MODIFICADO ERNESTO
				if ($tipoDcto == "ENTRADA") {
					$idVale = $arrayIdDctoContabilidad[$indice][0];
					switch ($idModulo) {
						case 0 : if (function_exists("generarValeEntradaRe")) { generarValeEntradaRe($idVale,"",""); } break;
						case 2 : if (function_exists("generarValeEntradaVe")) { generarValeEntradaVe($idVale,"",""); } break;
					}
				} else if ($tipoDcto == "SALIDA") {
					$idVale = $arrayIdDctoContabilidad[$indice][0];
					switch ($idModulo) {
						case 0 : if (function_exists("generarValeSalidaRe")) { generarValeSalidaRe($idVale,"",""); } break;
						case 1 : if (function_exists("generarValeSe")) { generarValeSe($idVale,"",""); } break;
						case 2 : if (function_exists("generarValeSalidaVe")) { generarValeSalidaVe($idVale,"",""); } break;
					}
				}
				// MODIFICADO ERNESTO
			}
		}
	} else {	
		$objResponse->alert(utf8_encode("No Se Puede Realizar Otro Ajunte Con Este Inventario Fisico"));
	}
	
	$objResponse->loadCommands(cargarInventarioFisico($idInventarioFisico));
	
	return $objResponse;
}

function imprimirInventario($frmImprimir, $frmInventario) {
	$objResponse = new xajaxResponse();
	
	$valCad = NULL;
	if (isset($frmImprimir['cbxImp'])) {
		foreach ($frmImprimir['cbxImp'] as $indice => $valor) {
			$valCad .= $valor."|";
		}
	}
	
	$objResponse->script(sprintf("verVentana('reportes/iv_inventario_fisico_todo_pdf.php?valBusq=%s|%s|%s', 960, 550);",
		$frmInventario['txtIdInventarioFisico'],
		$frmInventario['txtIdEmpresa'],
		$valCad));
	
	return $objResponse;
}

function imprimirInventarioComparativo($frmImprimirInvComparativo, $frmInventario) {
	$objResponse = new xajaxResponse();
	
	$valCad = NULL;
	if (isset($frmImprimirInvComparativo['cbxImpInvComp'])) {
		foreach ($frmImprimirInvComparativo['cbxImpInvComp'] as $indice => $valor) {
			$valCad .= $valor."|";
		}
	}
	if (isset($frmImprimirInvComparativo['rbtImp'])) {
		foreach ($frmImprimirInvComparativo['rbtImp'] as $indice => $valor) {
			$valCad .= $valor."|";
		}
	}
	
	// SE LE ASIGNA LA ACCION AL BOTON PARA EL AJUSTE DE INVENTARIO
	$objResponse->script(sprintf("verVentana('reportes/iv_inventario_fisico_ajuste_pdf.php?valBusq=%s|%s',1000,500);",
		$frmInventario['txtIdInventarioFisico'],
		$valCad));
	
	return $objResponse;
}

function insertarArticuloManual($frmInventario, $frmBuscarNumeroPosicion, $frmArticuloManual, $frmListaArticulosInventario) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmInventario['txtIdEmpresa'];
	$idInventarioFisico = $frmInventario['txtIdInventarioFisico'];
	$idArticulo = $frmArticuloManual['hddIdArticulo'];
	
	if ($idArticulo != "") {
		mysql_query("START TRANSACTION;");
		
		$queryInvFisDetalle = sprintf("SELECT * FROM iv_inventario_fisico_detalle
		WHERE id_inventario_fisico = %s
			AND id_inventario_fisico_detalle = %s
			AND id_articulo = %s;",
			valTpDato($idInventarioFisico, "int"),
			valTpDato($frmArticuloManual['hddIdInvFisicoDet'], "int"),
			valTpDato($idArticulo, "int"));
		$rsInvFisDetalle = mysql_query($queryInvFisDetalle);
		if (!$rsInvFisDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsInvFisDetalle = mysql_num_rows($rsInvFisDetalle);
		$rowInvFisDetalle = mysql_fetch_assoc($rsInvFisDetalle);
		
		if ($totalRowsInvFisDetalle > 0) {
			if ($rowInvFisDetalle['habilitado'] == "1") {
				if (!xvalidaAcceso($objResponse,"iv_inventario_fisico_conteo","insertar")) { return $objResponse; }
				
				$updateSQL = sprintf("UPDATE iv_inventario_fisico_detalle SET
					conteo_%s = %s
				WHERE id_inventario_fisico_detalle = %s;",
					valTpDato($frmInventario['hddNumeroConteo'], "int"),
					valTpDato($frmArticuloManual['txtCantidadArt'], "int"),
					valTpDato($rowInvFisDetalle['id_inventario_fisico_detalle'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
				
				mysql_query("COMMIT;");
				
				$objResponse->script(sprintf("mensajeJquery('divMsj','%s','2500');",
					utf8_encode("Se ha Guardado con Éxito")));
				
				$objResponse->loadCommands(listaArticulosInventario(
					$frmListaArticulosInventario['pageNum'],
					$frmListaArticulosInventario['campOrd'],
					$frmListaArticulosInventario['tpOrd'],
					$frmListaArticulosInventario['valBusq']));
			} else if ($rowInvFisDetalle['habilitado'] == "0") {
				$objResponse->script(sprintf("alertaJquery('divMsj','%s','2500');",
					utf8_encode("Este Artículo no está Habilitado para el Conteo Actual")));
			}
		} else {
			$objResponse->script("document.forms['frmBuscarNumeroPosicion'].reset();");
			$objResponse->script(sprintf("errorJquery('divMsj','%s','2500');",
				utf8_encode("El Código de Artículo no está Incluido para este Inventario")));
		}
		
		if ($frmInventario['hddTipoConteo'] == 1) {
			$numeroPosicion = $frmBuscarNumeroPosicion['txtNumero'];
			
			$objResponse->script("
			byId('tblDatosArticulo').style.display = 'none';
			byId('trCantidad').style.display = 'none';
			
			byId('txtNumero').readOnly = false;
			byId('txtNumero').className = 'inputHabilitado';
			byId('txtNumero').focus();
			byId('txtNumero').select();
			
			document.forms['frmArticuloManual'].reset();");
			
			$objResponse->assign("txtNumero","value",$numeroPosicion + 1);
		} else {
			$objResponse->script("
			byId('tblDatosArticulo').style.display = 'none';
			byId('trCantidad').style.display = 'none';
			
			byId('txtNumero').readOnly = false;
			byId('txtNumero').className = 'inputHabilitado';
			byId('txtNumero').focus();
			byId('txtNumero').select();
			
			document.forms['frmBuscarNumeroPosicion'].reset();
			document.forms['frmArticuloManual'].reset();");
		}
	}
	
	return $objResponse;
}

function listaArticulosInventario($pageNum = 0, $campOrd = "numero", $tpOrd = "ASC", $valBusq = "", $maxRows = 100, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// BUSCA LOS DATOS DEL INVENTARIO FISICO
	$queryInv = sprintf("SELECT * FROM vw_iv_inventario_fisico WHERE id_inventario_fisico = %s;",
		valTpDato($valCadBusq[1], "int"));
	$rsInv = mysql_query($queryInv);
	if (!$rsInv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowInv = mysql_fetch_assoc($rsInv);
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_inventario_fisico = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_inventario_fisico_detalle %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaArticulosInventario", "4%", $pageNum, "numero", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro."));		
		$htmlTh .= ordenarCampo("xajax_listaArticulosInventario", "14%", $pageNum, "CONCAT(descripcion_almacen, ubicacion, id_articulo_costo)", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Ubicación"));
		$htmlTh .= ordenarCampo("xajax_listaArticulosInventario", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaArticulosInventario", "38%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaArticulosInventario", "8%", $pageNum, "tipo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo");
		$htmlTh .= ordenarCampo("xajax_listaArticulosInventario", "6%", $pageNum, "id_articulo_costo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Lote"));
		$htmlTh .= ordenarCampo("xajax_listaArticulosInventario", "6%", $pageNum, "conteo_1", $campOrd, $tpOrd, $valBusq, $maxRows, "Conteo 1");
		$htmlTh .= ordenarCampo("xajax_listaArticulosInventario", "6%", $pageNum, "conteo_2", $campOrd, $tpOrd, $valBusq, $maxRows, "Conteo 2");
	if ($rowInv['cantidad_conteo'] == 3) {
		$htmlTh .= ordenarCampo("xajax_listaArticulosInventario", "6%", $pageNum, "conteo_3", $campOrd, $tpOrd, $valBusq, $maxRows, "Conteo 3");
	}
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr id=\"trInventarioFisicoDetalle".$row['numero']."\" align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px ".(($row['habilitado'] == 1) ? "" : "divMsjInfo3")."\">".$row['numero']."</td>";
			$htmlTb .= "<td align=\"center\" ".$classEstatusAlmacen." nowrap=\"nowrap\">";
				$htmlTb .= utf8_encode($row['descripcion_almacen'])."<br><span class=\"textoNegrita_10px\">".utf8_encode(str_replace("-[]", "", $row['ubicacion']))."</span>";
				//$htmlTb .= ($row['estatus_articulo_almacen'] == 1) ? "" : "<br><span class=\"textoRojoNegrita_10px\">".utf8_encode("Relacion Inactiva")."</span>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".elimCaracter(utf8_encode($row['codigo_articulo']),";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_articulo'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id_articulo_costo'])."</td>";
			$htmlTb .= "<td align=\"center\" ".(($rowInv['numero_conteo'] == 1) ? "class=\"divMsjInfo\"" : "").">";
				$htmlTb .= valTpDato($row['conteo_1'],"cero_por_vacio");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".(($rowInv['numero_conteo'] == 2) ? (($row['habilitado'] == 1) ? "class=\"divMsjInfo\"" : "class=\"divMsjInfo3\"") : "").">";
				$htmlTb .= valTpDato($row['conteo_2'],"cero_por_vacio");
			$htmlTb .= "</td>";
		if ($rowInv['cantidad_conteo'] == 3) {
			$htmlTb .= "<td align=\"center\" ".(($rowInv['numero_conteo'] == 3) ? (($row['habilitado'] == 1) ? "class=\"divMsjInfo\"" : "class=\"divMsjInfo3\"") : "").">";
				$htmlTb .= valTpDato($row['conteo_3'],"cero_por_vacio");
			$htmlTb .= "</td>";
		}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulosInventario(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulosInventario(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaArticulosInventario(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulosInventario(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaArticulosInventario(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divListaArticulosInventario","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function nuevoConteo($frmInventario, $frmListaArticulosInventario) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$idInventarioFisico = $frmInventario['txtIdInventarioFisico'];
	
	// BUSCA LOS DATOS DEL INVENTARIO FISICO
	$query = sprintf("SELECT * FROM iv_inventario_fisico WHERE id_inventario_fisico = %s;",
		valTpDato($idInventarioFisico, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$idEmpresa = $row['id_empresa'];
	
	$numeroConteo = intval($row['numero_conteo']) + 1;
	
	if ($row['cantidad_conteo'] == 2) {
		if ($numeroConteo == 2) {
			$updateSQL = sprintf("UPDATE iv_inventario_fisico_detalle SET
				conteo_2 = conteo_1,
				habilitado = false
			WHERE id_inventario_fisico = %s
				AND existencia_kardex = conteo_1;",
				valTpDato($idInventarioFisico, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	} else if ($row['cantidad_conteo'] == 3) {
		if ($numeroConteo == 3) {
			$updateSQL = sprintf("UPDATE iv_inventario_fisico_detalle SET
				conteo_3 = conteo_2,
				habilitado = false
			WHERE id_inventario_fisico = %s
				AND (((conteo_1 > 0 AND conteo_2 > 0) AND (conteo_1 = conteo_2))
					OR (conteo_1 = 0 AND conteo_2 = 0));",
				valTpDato($idInventarioFisico, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	// ACTUALIZA EL NUMERO DE CONTEO DEL INVENTARIO
	$updateSQL = sprintf("UPDATE iv_inventario_fisico SET
		numero_conteo = %s
	WHERE id_inventario_fisico = %s;",
		valTpDato($numeroConteo, "int"),
		valTpDato($idInventarioFisico, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	mysql_query("COMMIT;");
	
	$objResponse->loadCommands(cargarInventarioFisico($idInventarioFisico));
	
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
	$totalRowsPermiso = mysql_num_rows($rsPermiso);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	if ($totalRowsPermiso > 0) {
		if ($frmPermiso['hddModulo'] == "iv_inventario_fisico_form") {
			$objResponse->script("
			byId('aVerComparativo').style.display = 'none';
			byId('aAjusteInventario').style.display = 'none';
			
			xajax_guardarAjusteInventario(xajax.getFormValues('frmInventario'));");
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
	}
	
	$objResponse->script("byId('btnCancelarPermiso').click();");
	
	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"asignarArticulo");

$xajax->register(XAJAX_FUNCTION,"buscarCodigoArticulo");

$xajax->register(XAJAX_FUNCTION,"cargarInventarioFisico");

$xajax->register(XAJAX_FUNCTION,"formImprimir");
$xajax->register(XAJAX_FUNCTION,"formImprimirInvComparativo");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");

$xajax->register(XAJAX_FUNCTION,"guardarAjusteInventario");

$xajax->register(XAJAX_FUNCTION,"imprimirInventario");
$xajax->register(XAJAX_FUNCTION,"imprimirInventarioComparativo");
$xajax->register(XAJAX_FUNCTION,"insertarArticuloManual");

$xajax->register(XAJAX_FUNCTION,"listaArticulosInventario");

$xajax->register(XAJAX_FUNCTION,"nuevoConteo");

$xajax->register(XAJAX_FUNCTION,"validarPermiso");
?>