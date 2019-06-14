<?php
include("../clases/num2letras.php");
set_time_limit(0);
ini_set('memory_limit', '-1');
function asignarArticulo($idArticulo, $frmDcto, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// VERIFICA SI ALGUN ARTICULO YA ESTA INCLUIDO EN EL DOCUMENTO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			if ($frmListaArticulo['hddIdArticuloItm'.$valor] == $idArticulo) {
				$hddNumeroArt = $valor;
			}
		}
	}
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$contFila = $hddNumeroArt;
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT 
		art.id_articulo,
		art.codigo_articulo,
		art.descripcion,
		seccion.descripcion AS descripcion_seccion,
		tipo_art.descripcion AS descripcion_tipo_articulo,
		tipo_unidad.unidad,
		tipo_unidad.decimales,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = art.id_articulo
			AND kardex.tipo_movimiento IN (1)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_compra,
		
		(SELECT DATE(kardex.fecha_movimiento) AS fecha_movimiento FROM iv_kardex kardex
		WHERE kardex.id_articulo = art.id_articulo
			AND kardex.tipo_movimiento IN (3)
		ORDER BY kardex.id_kardex DESC LIMIT 1) AS fecha_ultima_venta,
		
		(SELECT SUM(IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0))
		FROM iv_articulos_almacen art_alm
		WHERE art_alm.id_articulo = art.id_articulo) AS cantidad_disponible_fisica,
		
		(SELECT SUM(IFNULL(art_alm.cantidad_entrada, 0) - IFNULL(art_alm.cantidad_salida, 0) - IFNULL(art_alm.cantidad_reservada, 0) - IFNULL(art_alm.cantidad_espera, 0) - IFNULL(art_alm.cantidad_bloqueada, 0))
		FROM iv_articulos_almacen art_alm
		WHERE art_alm.id_articulo = art.id_articulo) AS cantidad_disponible_logica
	FROM iv_articulos_empresa art_emp
		INNER JOIN iv_articulos art ON (art_emp.id_articulo = art.id_articulo)
		INNER JOIN iv_marcas marca ON (art.id_marca = marca.id_marca)
		INNER JOIN iv_tipos_articulos tipo_art ON (art.id_tipo_articulo = tipo_art.id_tipo_articulo)
		INNER JOIN iv_subsecciones subseccion ON (subseccion.id_subseccion = art.id_subseccion)
		INNER JOIN iv_secciones seccion ON (subseccion.id_seccion = seccion.id_seccion)
		INNER JOIN iv_tipos_unidad tipo_unidad ON (art.id_tipo_unidad = tipo_unidad.id_tipo_unidad)
	WHERE art.id_articulo = %s
		AND art_emp.id_empresa = %s
		AND art_emp.estatus = 1;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA EL ULTIMO COSTO DEL ARTICULO
	$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos art_costo
	WHERE art_costo.id_articulo = %s
		AND art_costo.id_empresa = %s
	ORDER BY art_costo.fecha_registro DESC LIMIT 1;",
		valTpDato($idArticulo, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCostoArt = mysql_query($queryCostoArt);
	if (!$rsCostoArt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCostoArt = mysql_fetch_assoc($rsCostoArt);
	
	$objResponse->assign("hddIdEmpresa","value",$idEmpresa);
	$objResponse->assign("hddIdArticulo","value",$idArticulo);
	$objResponse->assign("txtCodigoArt","value",elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtUnidadArt","value",utf8_encode($rowArticulo['unidad']));
	$objResponse->assign("txtFechaUltCompraArt","value",(($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtFechaUltVentaArt","value",(($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx"));
	$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['descripcion_tipo_articulo']));
	$objResponse->assign("txtCantDisponible","value",number_format($rowArticulo['cantidad_disponible_logica'], 2, ".", ","));
	
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
	
	$objResponse->script("byId('txtCantDisponible').className = '".(($rowArticulo['cantidad_disponible_logica'] > 0) ? "inputCantidadDisponible" : "inputCantidadNoDisponible")."'");
	
	if ($contFila > 0) {
		$objResponse->assign("hddNumeroArt","value",$contFila);
		
		// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
		$arrayPosIvaItm = array(-1);
		$arrayIdIvaItm = array(-1);
		$arrayIvaItm = array(-1);
		if (isset($arrayObjIvaItm)) {
			foreach ($arrayObjIvaItm as $indice1 => $valor1) {
				$valor1 = explode(":", $valor1);
				
				if ($valor1[0] == $contFila) {
					$arrayPosIvaItm[$frmListaArticulo['hddIdIvaItm'.$contFila.':'.$valor1[1]]] = $valor1[1];
					$arrayIdIvaItm[] = $frmListaArticulo['hddIdIvaItm'.$contFila.':'.$valor1[1]];
					$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$contFila.':'.$valor1[1]];
				}
			}
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
		$hddIvaItm = implode(",",$arrayIvaItm);
		
		$objResponse->assign("txtCantidadArt","value",number_format((str_replace(",","",$frmListaArticulo['txtCantItm'.$contFila]) + 1), 2, ".", ","));
		
		$costoUnitario = str_replace(",","",$frmListaArticulo['txtCostoItm'.$contFila]);
		$objResponse->assign("txtCostoArt","value",number_format($costoUnitario, 2, ".", ","));
		$objResponse->loadCommands(cargaLstIva("lstIvaArt", $hddIdIvaItm, $hddIvaItm));
		
		$objResponse->script("xajax_insertarArticulo(xajax.getFormValues('frmArticulo'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	} else { // NO EXISTE EL ARTICULO EN LA LISTA DEL PEDIDO
		$objResponse->assign("hddNumeroArt","value","");
		$objResponse->assign("txtCantidadArt","value",number_format(0, 2, ".", ","));
		
		$costoUnitario = ($rowCostoArt['costo'] > 0) ? $rowCostoArt['costo'] : 0;
		$objResponse->assign("txtCostoArt","value",number_format($costoUnitario, 2, ".", ","));
		$objResponse->loadCommands(cargaLstIva("lstIvaArt", $hddIdIvaItm, $hddIvaItm));
		
		$objResponse->script("
		byId('txtCantidadArt').value++;
		
		if (byId('hddNumeroArt').value > 0) {
			byId('aAgregarArticulo').click();
		}");
	}
	
	$objResponse->script("
	byId('txtCantidadArt').focus();
	byId('txtCantidadArt').select();");
	
	return $objResponse;
}

function asignarArticuloImpuesto($frmArticuloImpuesto, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$hddIdIvaItm = implode(",",$frmArticuloImpuesto['lstIvaCbx']);
	
	if (isset($frmListaArticulo['cbxItm'])) {
		foreach ($frmListaArticulo['cbxItm'] as $indiceItm => $valorItm) {
			$contFila = $valorItm;
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (1,8,3)
				AND iva.idIva IN (%s);",
				valTpDato($hddIdIvaItm, "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$contIva = 0;
			$ivaUnidad = "";
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$contIva++;
				
				$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
				"<input id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
					$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
					$contFila.":".$contIva);
			}
			
			$objResponse->assign("divIvaItm".$contFila,"innerHTML",$ivaUnidad);
		}
	}
	
	$hddIdIvaGasto = implode(",",$frmArticuloImpuesto['lstIvaCbx']);
	
	if (isset($frmTotalDcto['cbxItmGasto'])) {
		foreach ($frmTotalDcto['cbxItmGasto'] as $indiceItm => $valorItm) {
			$contFila = $valorItm;
			
			// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
			WHERE iva.tipo IN (1,8,3)
				AND iva.idIva IN (%s);",
				valTpDato($hddIdIvaGasto, "campo"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$contIva = 0;
			$ivaUnidad = "";
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$contIva++;
				
				$ivaUnidad .= sprintf("<table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr><td><img id=\"imgIvaGasto%s:%s\" src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/></td><td width=\"%s\">".
				"<input type=\"text\" id=\"hddIvaGasto%s:%s\" name=\"hddIvaGasto%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdIvaGasto%s:%s\" name=\"hddIdIvaGasto%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddLujoIvaGasto%s:%s\" name=\"hddLujoIvaGasto%s:%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusIvaGasto%s:%s\" name=\"hddEstatusIvaGasto%s:%s\" value=\"%s\">".
				"<input id=\"cbxIvaGasto\" name=\"cbxIvaGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td></tr></table>", 
					"100%", $contFila, $contIva, "100%",
					$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
					$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
					$contFila.":".$contIva);
			}
			
			$objResponse->assign("divIvaGasto".$contFila,"innerHTML",$ivaUnidad);
		}
	}
	
	$objResponse->script("
	byId('btnCancelarArticuloImpuesto').click();");
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa) {
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
	
	$objResponse->assign("txtIdClienteArt","value",$rowCliente['id']);
	$objResponse->assign("txtNombreClienteArt","value",$rowCliente['nombre_cliente']);
		
	$objResponse->script("byId('btnCancelarListaCliente').click();");
	
	return $objResponse;
}

function asignarMoneda($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	
	$objResponse->script("
	byId('aImpuestoArticulo').style.display = 'none';");
	
	if ($idModoCompra == 1) { // 1 = Nacional, 2 = Importacion
		$objResponse->assign("txtTasaCambio","value",number_format(0, 3, ".", ","));
	} else {
		$queryTasaCambio = sprintf("SELECT * FROM pg_tasa_cambio
		WHERE id_moneda_extranjera = %s
			AND id_moneda_nacional = %s
			AND id_tasa_cambio = %s;",
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($frmDcto['hddIdMoneda'], "int"),
			valTpDato($frmDcto['lstTasaCambio'], "int"));
		$rsTasaCambio = mysql_query($queryTasaCambio);
		if (!$rsTasaCambio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowTasaCambio = mysql_fetch_assoc($rsTasaCambio);
		
		$objResponse->assign("txtTasaCambio", "value", number_format($rowTasaCambio['monto_tasa_cambio'], 3, ".", ","));
	}
	
	// BUSCA LOS DATOS DE LA MONEDA DE ORIGEN
	$queryMonedaOrigen = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", 
		valTpDato($idMonedaOrigen, "int"));
	$rsMonedaOrigen = mysql_query($queryMonedaOrigen);
	if (!$rsMonedaOrigen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaOrigen = mysql_fetch_assoc($rsMonedaOrigen);
	
	$abrevMonedaOrigen = $rowMonedaOrigen['abreviacion'];
	
	$objResponse->assign("hddIncluirImpuestos", "value", $rowMonedaOrigen['incluir_impuestos']);
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", 
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	
	if ($rowMonedaOrigen['incluir_impuestos'] == 1) {
		$objResponse->script("
		byId('aImpuestoArticulo').style.display = '';");
	}
	
	// HABILITA O INHABILITA POR GASTO EL IMPUESTO DEPENDIENDO SI SE INCLUYE O NO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos WHERE pg_gastos.id_gasto = %s;", 
				valTpDato($frmTotalDcto['hddIdGasto'.$valor], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowGasto = mysql_fetch_assoc($rsGasto);
			
			if ($rowGasto['id_modo_gasto'] == 1) { // 1 = Gastos
				$objResponse->assign("spnGastoMoneda".$valor, "innerHTML", $abrevMonedaOrigen);
			} else if ($rowGasto['id_modo_gasto'] == 3) { // 3 = Gastos por Importacion
				$objResponse->assign("spnGastoMoneda".$valor, "innerHTML", $abrevMonedaLocal);
			}
			
			if (($rowGasto['id_modo_gasto'] == 1 && $rowMonedaOrigen['incluir_impuestos'] == 0)			// 1 = Gastos && 0 = No
			|| ($rowGasto['id_modo_gasto'] == 3 && $rowMonedaLocal['incluir_impuestos'] == 0)) {		// 3 = Gastos por Importacion && 0 = No
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						
						if ($valor1[0] == $valor) {
							$objResponse->script(sprintf("
							byId('imgIvaGasto%s:%s').style.visibility = '%s';
							byId('hddIvaGasto%s:%s').style.visibility = '%s';
							if (byId('hddIdIvaGasto%s:%s').value > 0) {
								byId('hddEstatusIvaGasto%s:%s').value = %s;
							}",
								$valor, $valor1[1], "hidden",
								$valor, $valor1[1], "hidden",
								$valor, $valor1[1],
								$valor, $valor1[1], 0));
						}
					}
				}
			} else if (($rowGasto['id_modo_gasto'] == 1 && $rowMonedaOrigen['incluir_impuestos'] == 1)	// 1 = Gastos && 1 = Si
			|| ($rowGasto['id_modo_gasto'] == 3 && $rowMonedaLocal['incluir_impuestos'] == 1)) {		// 3 = Gastos por Importacion && 1 = Si
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						
						if ($valor1[0] == $valor) {
							$objResponse->script(sprintf("
							byId('imgIvaGasto%s:%s').style.visibility = '%s';
							byId('hddIvaGasto%s:%s').style.visibility = '%s';
							if (byId('hddIdIvaGasto%s:%s').value > 0) {
								byId('hddEstatusIvaGasto%s:%s').value = %s;
							}",
								$valor, $valor1[1], "",
								$valor, $valor1[1], "",
								$valor, $valor1[1],
								$valor, $valor1[1], 1));
						}
					}
				}
			}
			$objResponse->script("byId('txtMontoGasto".$valor."').className = 'inputHabilitado';");
			
			$objResponse->script("
			byId('txtMedidaGasto".$valor."').style.display = '".(($rowGasto['id_tipo_medida'] > 0) ? "" : "none")."';
			byId('txtMedidaGasto".$valor."').className = 'inputCompletoHabilitado';");
			
			$existeTipoMedidaPeso = ($rowGasto['id_tipo_medida'] == 1 && str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor]) > 0) ? true : $existeTipoMedidaPeso;
		}
	}
	
	// HABILITA O INHABILITA POR ARTICULO EL IMPUESTO Y EL ARANCEL DEPENDIENDO SI SE INCLUYE O NO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$objResponse->script(sprintf("
						byId('hddIvaItm%s:%s').style.visibility = '%s';
						if (byId('hddIdIvaItm%s:%s').value > 0) {
							byId('hddEstatusIvaItm%s:%s').value = %s;
						}",
							$valor, $valor1[1], (($rowMonedaOrigen['incluir_impuestos'] == 1) ? "" : "hidden"), // 0 = No , 1 = Si
							$valor, $valor1[1],
							$valor, $valor1[1], (($rowMonedaOrigen['incluir_impuestos'] == 1) ? 1 : 0)));
					}
				}
			}
		}
	}
	
	$objResponse->assign("tdSubTotalMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdDescuentoMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdGastoConIvaMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdGastoSinIvaMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdTotalRegistroMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdTotalFacturaMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdExentoMoneda","innerHTML",$abrevMonedaOrigen);
	$objResponse->assign("tdExoneradoMoneda","innerHTML",$abrevMonedaOrigen);
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $asigDescuento = "true", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT
		prov.id_proveedor,
		prov.nombre,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.direccion,
		prov.contacto,
		prov.correococtacto,
		prov.telefono,
		prov.fax,
		prov.credito
	FROM cp_proveedor prov
	WHERE prov.id_proveedor = %s;",
		valTpDato($idProveedor, "text"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtIdProv","value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",utf8_encode($rowProv['nombre']));
	$objResponse->assign("txtRifProv","value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccionProv","innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContactoProv","value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContactoProv","value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonoProv","value",utf8_encode($rowProv['telefono']));
	
	if (in_array($asigDescuento, array("1", "true"))) {
		$objResponse->assign("txtDescuento","value",number_format($rowProv['descuento'], 2, ".", ","));
	}
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function buscarArticulo($frmBuscarArticulo, $frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	$codArticulo = "";
	for ($cont = 0; $cont <= $frmBuscarArticulo['hddCantCodigo']; $cont++) {
		$codArticulo .= $frmBuscarArticulo['txtCodigoArticulo'.$cont].";";
	}
	$auxCodArticulo = $codArticulo;
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
	
	if (strlen($frmDcto['txtIdEmpresa']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("id_empresa = %s",
			valTpDato($frmDcto['txtIdEmpresa'], "int"));
	}
	
	if ($auxCodArticulo != "---") {
		if ($codArticulo != "-1" && $codArticulo != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("codigo_articulo REGEXP %s",
				valTpDato($codArticulo, "text"));
		}
	}
	
	if ($frmDcto['hddModoCompra'] != "-1" && $frmDcto['hddModoCompra'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modo_compra = %s",
			valTpDato($frmDcto['hddModoCompra'], "int"));
	}
	
	if (strlen($frmBuscarArticulo['txtCriterioBuscarArticulo']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($frmBuscarArticulo['lstBuscarArticulo']) {
			case 1 : $sqlBusq .= $cond.sprintf("marca LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 2 : $sqlBusq .= $cond.sprintf("tipo_articulo LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 3 : $sqlBusq .= $cond.sprintf("descripcion_seccion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 4 : $sqlBusq .= $cond.sprintf("descripcion_subseccion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 5 : $sqlBusq .= $cond.sprintf("descripcion LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
			case 6 : $sqlBusq .= $cond.sprintf("id_articulo = %s", valTpDato($frmBuscarArticulo['txtCriterioBuscarArticulo'], "int")); break;
			case 7 : $sqlBusq .= $cond.sprintf("codigo_articulo_prov LIKE %s", valTpDato("%".$frmBuscarArticulo['txtCriterioBuscarArticulo']."%", "text")); break;
		}
	}
	
	$objResponse->assign("divListaArticulo","innerHTML","");
	
	if ($auxCodArticulo != "---" || strlen($frmBuscarArticulo['txtCriterioBuscarArticulo']) > 0) {
		$query = sprintf("SELECT id_articulo FROM vw_iv_articulos_empresa_datos_basicos %s", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		
		if ($totalRows == 1) {
			$row = mysql_fetch_assoc($rs);
			
			$objResponse->loadCommands(asignarArticulo($row['id_articulo'], $frmDcto, $frmListaArticulo));
			
			$objResponse->script("byId('txtCriterioBuscarArticulo').value = '';");
		} else if ($totalRows > 1) {
			$valBusq = sprintf("%s|%s|%s|%s|%s",
				$frmDcto['txtIdEmpresa'],
				$frmDcto['hddModoCompra'],
				$codArticulo,
				$frmBuscarArticulo['lstBuscarArticulo'],
				$frmBuscarArticulo['txtCriterioBuscarArticulo']);
			
			$objResponse->loadCommands(listaArticulo(0, "id_articulo", "DESC", $valBusq));
		} else {
			$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
			$htmlTb .= "<td colspan=\"11\">";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTblFin .= "</table>";
			
			$objResponse->assign("divListaArticulo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		}
	}
	
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

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarGasto($frmBuscarGasto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarGasto['txtCriterioBuscarGasto']);
	
	$objResponse->loadCommands(listaGasto(0, "nombre", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarProveedor($frmBuscarProveedor) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarProveedor['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "id_proveedor", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			$objResponse->script("
			fila = document.getElementById('trIva:".$valor."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObj","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	if (isset($arrayObjGasto)) {
		$i = 0;
		foreach ($arrayObjGasto as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItmGasto:".$valor, "className", $clase." textoGris_11px");
		}
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$txtIdFactura = $frmDcto['txtIdPedido'];
	$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
	$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
	
	$idMonedaLocal = $frmDcto['hddIdMoneda'];
	$idMonedaOrigen = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	// VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
	$txtTasaCambio = (str_replace(",", "", $frmDcto['txtTasaCambio']) > 0) ? str_replace(",", "", $frmDcto['txtTasaCambio']) : 1;
	
	// BUSCA LOS DATOS DE LA MONEDA DE ORIGEN
	$queryMonedaOrigen = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaOrigen, "int"));
	$rsMonedaOrigen = mysql_query($queryMonedaOrigen);
	if (!$rsMonedaOrigen) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaOrigen = mysql_fetch_assoc($rsMonedaOrigen);
	
	$abrevMonedaOrigen = $rowMonedaOrigen['abreviacion'];
	$incluirIvaMonedaOrigen = $rowMonedaOrigen['incluir_impuestos'];
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;",
		valTpDato($idMonedaLocal, "int"));
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	$incluirIvaMonedaLocal = $rowMonedaLocal['incluir_impuestos'];
	
	// CALCULA EL SUBTOTAL
	$txtSubTotal = 0;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantRecibItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
			$hddMontoDescuentoItm = str_replace(",", "", $frmListaArticulo['hddMontoDescuentoItm'.$valor]);
			$txtTotalItm = $txtCantRecibItm * $txtCostoItm;
			
			$txtSubTotal += $txtTotalItm;
			$subTotalDescuentoItm += $txtCantRecibItm * $hddMontoDescuentoItm;
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		$txtDescuento = ($subTotalDescuentoItm * 100) / $txtSubTotal;
		$txtSubTotalDescuento = $subTotalDescuentoItm;
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$txtDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']);
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtDescuento']) * $txtSubTotal / 100;
		} else {
			$txtDescuento = ($txtSubTotal > 0) ? ($txtSubTotalDescuento * 100) / $txtSubTotal : 0;
			$txtSubTotalDescuento = str_replace(",", "", $frmTotalDcto['txtSubTotalDescuento']);
		}
	}
	
	// VERIFICA LOS VALORES DE CADA ITEM PARA CALCULAR EL IMPUESTO Y EL SUBTOTAL
	$txtTotalExentoOrigen = 0;
	$txtTotalExoneradoOrigen = 0;
	$arrayIva = NULL;
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtCantRecibItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
			$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
			$txtTotalItm = $txtCantRecibItm * $txtCostoItm;
			$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
			
			$hddTotalDescuentoItm = ($subTotalDescuentoItm > 0) ? $hddTotalDescuentoItm : ($txtTotalItm * $txtSubTotalDescuento) / $txtSubTotal; // VERIFICA SI EL DESCUENTO ES INDIVIDUAL O DESCUENTO PRORATEADO
			$txtTotalNetoItm = $txtTotalItm - $hddTotalDescuentoItm;
			
			// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
			$arrayPosIvaItm = array(-1);
			$arrayIdIvaItm = array(-1);
			$arrayIvaItm = array(-1);
			if (isset($arrayObjIvaItm)) {
				foreach ($arrayObjIvaItm as $indice1 => $valor1) {
					$valor1 = explode(":", $valor1);
					
					if ($valor1[0] == $valor) {
						$arrayPosIvaItm[$frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]]] = $valor1[1];
						$arrayIdIvaItm[] = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
						$arrayIvaItm[] = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
					}
				}
			}
			
			if ($idModoCompra == 2 && $incluirIvaMonedaLocal == 1) { // 2 = Importacion
				// BUSCA LOS DATOS DEL IMPUESTO DE COMPRA POR DEFECTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1;");
			} else {
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);", 
					valTpDato(implode(",", $arrayIdIvaItm), "campo"));
			}
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsIva = mysql_num_rows($rsIva);
			while ($rowIva = mysql_fetch_assoc($rsIva)) {
				$idIva = $rowIva['idIva'];
				$porcIva = $rowIva['iva'];
				$lujoIva = $rowIva['lujo'];
				$estatusIva = ($incluirIvaMonedaOrigen == 1) ? $frmListaArticulo['hddEstatusIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]] : 0;
				
				// 1 = IVA COMPRA, 6 = IVA VENTA, 3 = LUJO COMPRA, 2 = LUJO VENTA
				if ($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
					$txtTotalExentoOrigen += $txtTotalNetoItm;
				} else if ($estatusIva != 0) {
					$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmListaArticulo['hddIvaItm'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
					$subTotalIvaItm = ($txtTotalNetoItm * $porcIva) / 100;
					
					$existIva = false;
					if (isset($arrayIva)) {
						foreach ($arrayIva as $indiceIva => $valorIva) {
							if ($arrayIva[$indiceIva][0] == $idIva) {
								$arrayIva[$indiceIva][1] += $txtTotalNetoItm;
								$arrayIva[$indiceIva][2] += $subTotalIvaItm;
								$existIva = true;
							}
						}
					}
					
					if ($idIva > 0 && $existIva == false && $txtTotalNetoItm > 0) {
						$arrayIva[] = array(
							$idIva,
							$txtTotalNetoItm,
							$subTotalIvaItm,
							$porcIva,
							$lujoIva,
							$rowIva['observacion']);
					}
				}
			}
			
			if ($totalRowsIva == 0) {
				$txtTotalExentoOrigen += $txtTotalNetoItm;
			}
			
			$objResponse->assign("txtTotalItm".$valor, "value", number_format($txtTotalItm, 2, ".", ","));
		}
	}
	
	// CALCULA LOS GASTOS DE CADA ARTICULO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtGastosItm = 0;
			$hddGastosImportNacItm = 0;
			$hddGastosImportItm = 0;
			
			if ($frmListaArticulo['hddIdArticuloItm'.$valor] > 0) {
				$txtCantRecibItm = str_replace(",", "", $frmListaArticulo['txtCantItm'.$valor]);
				$txtCostoItm = str_replace(",", "", $frmListaArticulo['txtCostoItm'.$valor]);
				$txtPesoItm = str_replace(",", "", $frmListaArticulo['txtPesoItm'.$valor]);
				$hddTotalDescuentoItm = str_replace(",", "", $frmListaArticulo['hddTotalDescuentoItm'.$valor]);
				$txtTotalItm = $txtCantRecibItm * $txtCostoItm;
				
				$txtSubTotalDescuentoItm = ($subTotalDescuentoItm > 0) ? $txtSubTotal - $txtSubTotalDescuento : $txtSubTotal;
				// GASTOS INCLUIDOS EN FACTURA
				if (isset($arrayObjGasto)) {
					foreach ($arrayObjGasto as $indice2 => $valor2) {
						$idGasto = $frmTotalDcto['hddIdGasto'.$valor2];
						$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor2]);
						$txtMedidaGasto = str_replace(",", "", $frmTotalDcto['txtMedidaGasto'.$valor2]);
						$hddIdModoGasto = $frmTotalDcto['hddIdModoGasto'.$valor2];
						
						if (round($txtMontoGasto, 2) != 0) {
							if ($hddIdModoGasto == 1) { // 1 = Gastos
								if ($txtMedidaGasto > 0) {
									$gastosItm = $txtMontoGasto / $txtMedidaGasto * ($txtCantRecibItm * $txtPesoItm);
								} else {
									$gastosItm = ($txtSubTotalDescuentoItm > 0) ? (($txtTotalItm - $hddTotalDescuentoItm) * $txtMontoGasto) / $txtSubTotalDescuentoItm : ($txtTotalItm * $txtMontoGasto) / $txtSubTotal;
								}
								
								$txtGastosItm += $gastosItm;
							} else if ($hddIdModoGasto == 3) { // 3 = Gastos por Importacion
								$gastosItm = (($txtSubTotalDescuentoItm * $txtTasaCambio) > 0) ? ((($txtTotalItm - $hddTotalDescuentoItm) * $txtTasaCambio) * $txtMontoGasto) / ($txtSubTotalDescuentoItm * $txtTasaCambio) : (($txtTotalItm * $txtTasaCambio) * $txtMontoGasto) / ($txtSubTotal * $txtTasaCambio);
								
								$hddGastosImportNacItm += $gastosItm;
							}
						}
					}
				}
				$txtGastosItm = ($frmTotalDcto['lstGastoItem'] == 1) ? str_replace(",", "", $frmListaArticulo['txtGastosItm'.$valor]) : $txtGastosItm;
				
				$frmListaArticulo['txtGastosItm'.$valor] = $txtGastosItm;
				$objResponse->assign("txtGastosItm".$valor, "value", number_format($txtGastosItm, 3, ".", ","));
				$objResponse->assign("hddGastosImportNacItm".$valor, "value", number_format($hddGastosImportNacItm, 2, ".", ","));
				
				$txtTotalPesoItem += $txtCantRecibItm * $txtPesoItm;
				$txtTotalGastoItem += $txtGastosItm;
				
				// OTROS CARGOS
				if (isset($arrayObjOtroCargo)) {
					$spnTotalOtrosCargos = 0;
					foreach ($arrayObjOtroCargo as $indice2 => $valor2) {
						$hddSubTotalFacturaGasto = str_replace(",", "", $frmTotalDcto['hddSubTotalFacturaGasto'.$valor2]);
						$montoOtrosCargosItm = (($txtTotalItm - $hddTotalDescuentoItm) * $hddSubTotalFacturaGasto) / $txtSubTotal;
						
						$hddGastosImportItm += $montoOtrosCargosItm;
						
						$spnTotalOtrosCargos += $hddSubTotalFacturaGasto;
					}
				}
				$objResponse->assign("hddGastosImportItm".$valor, "value", number_format($hddGastosImportItm, 2, ".", ","));
			}
		}
	}
	
	// SACA LA CUENTA DE LOS GASTOS QUE LLEVAN Y NO LLEVAN IMPUESTO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			// BUSCA LOS DATOS DEL GASTO
			$queryGasto = sprintf("SELECT * FROM pg_gastos
			WHERE id_gasto = %s
				AND id_modo_gasto IN (1);", 
				valTpDato($frmTotalDcto['hddIdGasto'.$valor], "int"));
			$rsGasto = mysql_query($queryGasto);
			if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while ($rowGasto = mysql_fetch_assoc($rsGasto)) {
				if ($frmTotalDcto['hddTipoGasto'.$valor] == 0) { // SACA EL MONTO MEDIANTE EL PORCENTAJE
					$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
					$txtMontoGasto = ($txtSubTotal == 0) ? 0 : $txtPorcGasto * ($txtSubTotal / 100);
					$objResponse->assign('txtMontoGasto'.$valor, "value", number_format($txtMontoGasto, 2, ".", ","));
				} else if ($frmTotalDcto['hddTipoGasto'.$valor] == 1) { // SACA EL PORCENTAJE MEDIANTE EL MONTO
					$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
					$txtPorcGasto = ($txtSubTotal == 0) ? 0 : $txtMontoGasto * (100 / $txtSubTotal);
					$objResponse->assign('txtPorcGasto'.$valor, "value", number_format($txtPorcGasto, 2, ".", ","));
				}
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$arrayPosIvaItm = array(-1);
				$arrayIdIvaItm = array(-1);
				$arrayIvaItm = array(-1);
				$arrayEstatusIvaItm = array(-1);
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						
						if ($valor1[0] == $valor) {
							$arrayPosIvaItm[$frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valor1[1]]] = $valor1[1];
							$arrayIdIvaItm[] = $frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valor1[1]];
							$arrayIvaItm[] = $frmTotalDcto['hddIvaGasto'.$valor.':'.$valor1[1]];
							$arrayEstatusIvaItm[] = $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$valor1[1]];
						}
					}
				}
				
				$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva IN (%s);",
					valTpDato(implode(",", $arrayIdIvaItm), "campo"));
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsIva = mysql_num_rows($rsIva);
				while ($rowIva = mysql_fetch_assoc($rsIva)) {
					$idIva = $rowIva['idIva'];
					$porcIva = $rowIva['iva'];
					$lujoIva = $rowIva['lujo'];
					$estatusIva = ($incluirIvaMonedaOrigen == 1) ? $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$arrayPosIvaItm[$idIva]] : 0;
					
					if ($estatusIva == 0 && $rowIva['tipo'] == 1 && $rowIva['estado'] == 1 && $rowIva['activo'] == 1) {
						switch ($rowGasto['afecta_documento']) {
							case 1 : $txtGastosSinIvaOrigen += $txtMontoGasto; break;
							default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
						}
					} else if ($estatusIva != 0) {
						$porcIva = ($txtIdFactura > 0) ? str_replace(",", "", $frmTotalDcto['hddIvaGasto'.$valor.':'.$arrayPosIvaItm[$idIva]]) : $porcIva;
						$subTotalIvaGasto = ($txtMontoGasto * $porcIva) / 100;
						
						$existIva = false;
						if (isset($arrayIva)) {
							foreach ($arrayIva as $indiceIva => $valorIva) {
								if ($arrayIva[$indiceIva][0] == $idIva) {
									$arrayIva[$indiceIva][1] += $txtMontoGasto;
									$arrayIva[$indiceIva][2] += $subTotalIvaGasto;
									$existIva = true;
								}
							}
						}
						
						if ($idIva > 0 && $existIva == false
						&& $txtMontoGasto > 0) {
							$arrayIva[] = array(
								$idIva,
								$txtMontoGasto,
								$subTotalIvaGasto,
								$porcIva,
								$lujoIva,
								$rowIva['observacion']);
						}
					}
				}
				
				if ($totalRowsIva > 0 && in_array(1,$arrayEstatusIvaItm)) {
					switch ($rowGasto['afecta_documento']) {
						case 1 : $txtGastosConIvaOrigen += $txtMontoGasto; break;
						default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
					}
				} else if (!($totalRowsIva > 0)) {
					switch ($rowGasto['afecta_documento']) {
						case 1 : $txtGastosSinIvaOrigen += $txtMontoGasto; break;
						default : $gastosNoAfectaOrigen += $txtMontoGasto; break;
					}
				}
				
				$htmlGastos .= "<td width=\"".(100 / count($arrayObjGasto))."%\">".$rowGasto['nombre']."</td>";
			}
			
			$txtTotalGasto += ($frmTotalDcto['hddIdModoGasto'.$valor] == 1) ? str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]) : 0;
			$txtTotalGastoImportacion += ($frmTotalDcto['hddIdModoGasto'.$valor] == 3) ? str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]) : 0;
		}
		
		$htmlGastos = ($frmTotalDcto['lstGastoItem'] == 1) ? "<td></td>" : $htmlGastos;
		
		$objResponse->assign("tdGastosArancel","innerHTML","<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr align=\"center\">".$htmlGastos."</tr></table>");
	}
	
	// CREA LOS ELEMENTOS DE IMPUESTO
	if (isset($arrayIva)) {
		foreach ($arrayIva as $indiceIva => $valorIva) {
			if ($arrayIva[$indiceIva][2] > 0) {
				// INSERTA EL ARTICULO SIN INJECT
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"trIva:%s\">%s:".
							"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva%s\" value=\"%s\"/>".
							"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" value=\"%s\"/>".
							"<input type=\"checkbox\" id=\"cbxIva\" name=\"cbxIva[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td>%s</td>".
						"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
					
					obj = byId('trIva:%s');
					if (obj == undefined)
						$('#trGastosSinIva').before(elemento);", 
					$indiceIva, 
						$indiceIva, utf8_encode($arrayIva[$indiceIva][5]), 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][0], 
							$indiceIva, $indiceIva, $arrayIva[$indiceIva][4], 
							$indiceIva,
						$indiceIva, $indiceIva, number_format($arrayIva[$indiceIva][1], 2, ".", ","), 
						$indiceIva, $indiceIva, $arrayIva[$indiceIva][3], "%", 
						$abrevMonedaLocal, 
						$indiceIva, $indiceIva, number_format($arrayIva[$indiceIva][2], 2, ".", ","), 
					
					$indiceIva));
			}
			
			$subTotalIva += round(doubleval($arrayIva[$indiceIva][2]), 2);
		}
	}
	
	if ($subTotalDescuentoItm > 0) {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$objResponse->script("
			byId('txtDescuento').readOnly = true;
			byId('txtDescuento').className = 'inputInicial';");
		} else if ($frmTotalDcto['rbtInicial'] == 2) {
			$objResponse->script("
			byId('txtSubTotalDescuento').readOnly = true;
			byId('txtSubTotalDescuento').className = 'inputInicial';");
		}
	} else {
		if ($frmTotalDcto['rbtInicial'] == 1) {
			$objResponse->script("
			byId('txtDescuento').readOnly = false;
			byId('txtDescuento').className = 'inputHabilitado';");
		} else if ($frmTotalDcto['rbtInicial'] == 2) {
			$objResponse->script("
			byId('txtSubTotalDescuento').readOnly = false;
			byId('txtSubTotalDescuento').className = 'inputHabilitado';");
		}
	}
	$txtDescuento = ($txtDescuento > 0) ? $txtDescuento : 0;
	$txtSubTotalDescuento = ($txtSubTotalDescuento > 0) ? $txtSubTotalDescuento : 0;
	
	$txtTotalOrden = doubleval($txtSubTotal) - doubleval($txtSubTotalDescuento);
	$txtTotalOrden += round(doubleval($subTotalIva) + doubleval($txtGastosConIvaOrigen) + doubleval($txtGastosSinIvaOrigen), 2);
	
	$objResponse->assign("hddModoCompra","value",$idModoCompra);
	$objResponse->assign("txtSubTotal", "value", number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtDescuento", "value", number_format($txtDescuento, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento", "value", number_format($txtSubTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalOrden", "value", number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign('txtGastosConIva', "value", number_format($txtGastosConIvaOrigen, 2, ".", ","));
	$objResponse->assign('txtGastosSinIva', "value", number_format($txtGastosSinIvaOrigen, 2, ".", ","));
	
	$objResponse->assign("txtTotalExento", "value", number_format(($txtTotalExentoOrigen + $txtGastosSinIvaOrigen), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado", "value", number_format($txtTotalExoneradoOrigen, 2, ".", ","));
	
	$objResponse->assign("txtTotalGasto", "value", number_format($txtTotalGasto, 2, ".", ","));
	
	$objResponse->assign('txtMontoEnLetras',"innerHTML",num2letras(number_format($txtTotalOrden, 2, ".", ""), false, true));
	
	if (count($arrayObj) > 0) { // SI TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputInicial';
		byId('txtIdEmpresa').readOnly = true;
		byId('aListarEmpresa').style.display = 'none';
		 
		byId('lstMoneda').className = 'inputInicial';
		byId('lstMoneda').onchange = function () {
			selectedOption(this.id,".$idMonedaOrigen.");
		}");
	} else { // SI NO TIENE ITEMS AGREGADOS
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpresa').readOnly = false;
		byId('aListarEmpresa').style.display = '';
		
		byId('lstMoneda').className = 'inputHabilitado';
		byId('lstMoneda').onchange = function () { xajax_cargaLstTasaCambio(this.value); xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto')); }");
	}

	return $objResponse;
}

function cargaLstArancelGrupoBuscar($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputCompleto\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"buscarEnColumna(this.value, 'porcentaje_grupo');\"";
	
	$query = sprintf("SELECT * FROM pg_arancel_grupo ORDER BY porcentaje_grupo;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"min-width:60px\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId != "" && doubleval($selId) == doubleval($row['porcentaje_grupo'])) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['porcentaje_grupo']."\">".number_format($row['porcentaje_grupo'], 2, ".", ",")."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstArancelGrupoBuscar","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstIva($nombreObjeto, $selId = "", $selVal = "", $bloquearObj = false, $alturaObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	$style = ($alturaObj == true) ? "style=\"height:200px; width:99%\"" : " style=\"width:99%\"";
	
	// 1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1,8,3) AND iva.estado = 1 ORDER BY iva;");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select multiple id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." ".$style.">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	$selected = "";
	if ((in_array(0,explode(",",$selId)) || in_array(-1,explode(",",$selId))) && !(count(explode(",",$selId)) > 1) && $selId != "") {
		$selected = "selected=\"selected\"";
	}
		$html .= "<option ".$selected." value=\"0\">-</option>";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if (in_array($rowIva['iva'],explode(",",$selVal)) && in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['iva'],explode(",",$selVal))) {
				$selected = "selected=\"selected\"";
			} else if (in_array($rowIva['idIva'],explode(",",$selId))) {
				$selected = "selected=\"selected\"";
			} else if ($selId == "" && in_array($rowIva['tipo'],array(1,6)) && $rowIva['activo'] == 1) { // IMPUESTO PREDETERMINADO
				$selected = "selected=\"selected\"";
			}
			
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
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
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\" class=\"inputHabilitado\" onchange=\"xajax_cargaLstTasaCambio(this.value); xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['idmoneda']) {
			$selected = "selected=\"selected\"";
		} else if ($row['predeterminada'] == 1 && $selId == "") {
			$selected = "selected=\"selected\"";
		}
		
		$html .= "<option ".$selected." value=\"".$row['idmoneda']."\">".utf8_encode($row['descripcion']." (".$row['abreviacion'].")")."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMoneda","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTasaCambio($idMoneda, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT *
	FROM pg_tasa_cambio tasa_cambio
		INNER JOIN pg_monedas moneda_local ON (tasa_cambio.id_moneda_nacional = moneda_local.idmoneda)
	WHERE tasa_cambio.id_moneda_extranjera = %s;",
		valTpDato($idMoneda, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstTasaCambio\" name=\"lstTasaCambio\" class=\"inputHabilitado\" onchange=\"xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));\" style=\"width:150px\">";
	if ($totalRows > 0) {
			$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($selId == $row['id_tasa_cambio']) ? "selected=\"selected\"" : "";
			
			$html .= "<optgroup label=\"".$row['abreviacion']." ".$row['monto_tasa_cambio']."\">";
				$html .= "<option ".$selected." value=\"".$row['id_tasa_cambio']."\">".utf8_encode($row['nombre_tasa_cambio'])."</option>";
			$html .= "</optgroup>";
		}
	} else {
		$html .= "<option value=\"\"></option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTasaCambio","innerHTML",$html);
	
	$objResponse->script((($totalRows > 0) ? "byId('lstTasaCambio').style.display = ''" : "byId('lstTasaCambio').style.display = 'none'"));
	
	return $objResponse;
}

function cargaLstTipoPedido($selId = "", $bloquear = false) {
	$objResponse = new xajaxResponse();
	
	if ($bloquear == true)
		$disabled = "disabled=\"disabled\"";
	
	$query = sprintf("SELECT * FROM iv_tipo_pedido_compra
	ORDER BY clave");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoPedido\" name=\"lstTipoPedido\" ".$disabled." class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_tipo_pedido_compra']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_tipo_pedido_compra']."\">".utf8_encode("(".$row['clave'].") - ".$row['tipo_pedido_compra'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoPedido","innerHTML",$html);
	
	return $objResponse;
}

function cargarDcto($idDocumento, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	$contFilaGasto = $arrayObjGasto[count($arrayObjGasto)-1];
	
	$objResponse->script("
	document.forms['frmDcto'].reset();
	byId('txtDireccionProv').innerHTML = '';
	document.forms['frmTotalDcto'].reset();
	
	byId('txtIdEmpresa').className = 'inputHabilitado';
	byId('txtFecha').className = 'inputHabilitado';
	byId('txtIdProv').className = 'inputHabilitado';
	byId('lstTipoPedido').className = 'inputHabilitado';
	byId('lstMoneda').className = 'inputHabilitado';
	byId('txtNumeroReferencia').className = 'inputHabilitado';");
	
	if ($idDocumento > 0) {
		// BUSCA LOS DATOS DEL PEDIDO DE COMPRA
		$queryPedido = sprintf("SELECT * FROM vw_iv_pedidos_compra WHERE id_pedido_compra = %s;",
			valTpDato($idDocumento, "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsPedido = mysql_num_rows($rsPedido);
		$rowPedido = mysql_fetch_assoc($rsPedido);
		
		if ($totalRowsPedido > 0) {
			// BUSCA EL DETALLE DEL PEDIDO
			$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle ped_comp_det WHERE id_pedido_compra = %s
			ORDER BY id_pedido_compra_detalle ASC;",
				valTpDato($idDocumento, "int"));
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$arrayObj = NULL;
			while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
				$Result1 = insertarItemArticulo($contFila, $rowPedidoDet['id_pedido_compra_detalle']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFila = $Result1[2];
					$objResponse->script($Result1[1]);
					$arrayObj[] = $contFila;
				}
			}
			
			// BUSCA LOS GASTOS DEL PEDIDO
			$queryFacturaDetalle = sprintf("SELECT * FROM iv_pedido_compra_gasto ped_comp_gasto WHERE id_pedido_compra = %s
			ORDER BY id_pedido_compra_gasto ASC;",
				valTpDato($idDocumento, "int"));
			$rsFacturaDetalle = mysql_query($queryFacturaDetalle);
			if (!$rsFacturaDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			while ($rowFacturaDetalle = mysql_fetch_assoc($rsFacturaDetalle)) {
				$Result1 = insertarItemGasto($contFilaGasto, "", $rowFacturaDetalle['id_pedido_compra_gasto']);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) {
					return $objResponse->alert($Result1[1]);
				} else if ($Result1[0] == true) {
					$contFilaGasto = $Result1[2];
					$frmListaArticulo['hddIdPedidoGasto'.$contFilaGasto] = $rowFacturaDetalle['id_pedido_compra_gasto'];
					$objResponse->script($Result1[1]);
					$arrayObjGasto[] = $contFilaGasto;
				}
			}
			
			$idMonedaLocal = $rowPedido['id_moneda_origen'];
			$idMonedaOrigen = ($rowPedido['id_moneda_tasa_cambio'] > 0) ? $rowPedido['id_moneda_tasa_cambio'] : $rowPedido['id_moneda_origen'];
			
			// VERIFICA SI LA FACTURA ES DE IMPORTACION
			$idModoCompra = ($idMonedaLocal == $idMonedaOrigen) ? 1 : 2; // 1 = Nacional, 2 = Importacion
			
			$txtTasaCambio = ($rowPedido['monto_tasa_cambio'] >= 0) ? $rowPedido['monto_tasa_cambio'] : 0;
			$objResponse->loadCommands(cargaLstMoneda($idMonedaOrigen));
			$objResponse->assign("txtTasaCambio","value",number_format($txtTasaCambio, 3, ".", ","));
			$objResponse->assign("hddIdMoneda","value",$idMonedaLocal);
			$objResponse->loadCommands(cargaLstTasaCambio($idMonedaOrigen, $rowPedido['id_tasa_cambio']));
			
			$objResponse->assign("txtDescuento","value",$rowPedido['porcentaje_descuento']);
			$objResponse->assign("txtSubTotalDescuento","value",number_format($rowPedido['subtotal_descuento'], 2, ".", ","));
			
			// VERIFICA SI LA FACTURA ES DE IMPORTACION
			$idModoCompra = $rowPedido['id_modo_compra'];
			
			$objResponse->assign("txtIdPedido","value",utf8_encode($rowPedido['id_pedido_compra']));
			$objResponse->assign("txtFecha","value",date(spanDateFormat,strtotime($rowPedido['fecha'])));
			$objResponse->loadCommands(cargaLstTipoPedido($rowPedido['id_tipo_pedido_compra'],"true"));
			$objResponse->assign("txtNumeroPedidoPropio","value",$rowPedido['id_pedido_compra_propio']);
			$objResponse->assign("txtNumeroReferencia","value",$rowPedido['id_pedido_compra_referencia']);
			$objResponse->call("selectedOption","lstGastoItem",0);
			
			$objResponse->loadCommands(objetoCodigoDinamico("tdCodigoArt", $rowEmpresa['id_empresa_reg']));
			
			$objResponse->assign("hddIdEmpleado","value",$rowPedido['id_empleado_preparador']);
			$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowPedido['nombre_empleado']));
			
			$objResponse->loadCommands(asignarEmpresaUsuario($rowPedido['id_empresa'], "Empresa", "ListaEmpresa", "xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));"));
			$objResponse->loadCommands(asignarProveedor($rowPedido['id_proveedor'], "false", "false"));
			
			$objResponse->script("
			cerrarVentana = false;");
		}
	} else {
		// BUSCA LOS DATOS DE LA MONEDA POR DEFECTO
		$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE estatus = 1 AND predeterminada = 1;");
		$rsMoneda = mysql_query($queryMoneda);
		if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMoneda = mysql_fetch_assoc($rsMoneda);
		
		$idMoneda = $rowMoneda['idmoneda'];
		
		// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
		$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
		$rsUsuario = mysql_query($queryUsuario);
		if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowUsuario = mysql_fetch_assoc($rsUsuario);
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));"));
		$objResponse->assign("txtFecha","value",date(spanDateFormat));
		$objResponse->assign("hddIdEmpleado","value",$_SESSION['idEmpleadoSysGts']);
		$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']));
		$objResponse->loadCommands(cargaLstTipoPedido());
		$objResponse->loadCommands(cargaLstMoneda($idMoneda));
		$objResponse->assign("txtTasaCambio","value",number_format(0, 3, ".", ","));
		$objResponse->assign("hddIdMoneda","value",$idMoneda);
		$objResponse->assign("txtDescuento","value",number_format(0, 2, ".", ","));
		$objResponse->assign("txtSubTotalDescuento","value",number_format(0, 2, ".", ","));
	}
	
	return $objResponse;
}

function eliminarArticulo($frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaArticulo['cbxItm'])) {
		foreach ($frmListaArticulo['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
	return $objResponse;
}

function eliminarGasto($frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmTotalDcto['cbxItmGasto'])) {
		foreach ($frmTotalDcto['cbxItmGasto'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmGasto:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarGasto(xajax.getFormValues('frmTotalDcto'));");
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function formArticuloImpuesto() {
	$objResponse = new xajaxResponse();
	
	$objResponse->loadCommands(cargaLstIva("lstIvaCbx", "", "", false, true));
	
	return $objResponse;
}

function formDatosCliente($idCliente, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
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
	
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","innerHTML",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
		
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaItm = $frmListaArticulo['cbxIvaItm'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIva = $frmTotalDcto['cbxIva'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjIvaGasto = $frmTotalDcto['cbxIvaGasto'];
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	$idModulo = 0; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idPedido = $frmDcto['txtIdPedido'];
	
	mysql_query("START TRANSACTION;");
	
	if ($idPedido > 0) {
		if (!xvalidaAcceso($objResponse,"iv_pedido_compra_list","editar")) { return $objResponse; }
		
		// EDITA LOS DATOS DEL PEDIDO
		$updateSQL = sprintf("UPDATE iv_pedido_compra SET
			id_pedido_compra_propio = %s,
			id_pedido_compra_referencia = %s,
			id_empresa = %s,
			id_proveedor = %s,
			fecha = %s,
			id_moneda = %s,
			id_moneda_tasa_cambio = %s,
			id_tasa_cambio = %s,
			monto_tasa_cambio = %s,
			estatus_pedido_compra= %s,
			id_modulo = %s,
			id_empleado_preparador = %s,
			monto_exento = %s,
			subtotal = %s,
			porcentaje_descuento = %s,
			subtotal_descuento = %s
		WHERE id_pedido_compra = %s;",
			valTpDato($frmDcto['txtNumeroPedidoPropio'], "text"),
			valTpDato($frmDcto['txtNumeroReferencia'], "text"),
			valTpDato($frmDcto['txtIdEmpresa'], "int"),
			valTpDato($frmDcto['txtIdProv'], "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato($frmDcto['hddIdMoneda'], "int"),
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($frmDcto['lstTasaCambio'], "int"),
			valTpDato($frmDcto['txtTasaCambio'], "real_inglesa"),
			valTpDato($frmDcto['rbtEstatusPedido'], "int"),
			valTpDato($idModulo, "int"),
			valTpDato($frmDcto['hddIdEmpleado'], "int"),
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
			valTpDato($idPedido, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!xvalidaAcceso($objResponse,"iv_pedido_compra_list","insertar")) { return $objResponse; }
		
		// BUSCA LOS DATOS DEL TIPO DE PEDIDO
		$query = sprintf("SELECT * FROM iv_tipo_pedido_compra WHERE id_tipo_pedido_compra = %s;",
			valTpDato($frmDcto['lstTipoPedido'], "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
			
		$idTipoPedidoCompra = $row['id_tipo_pedido_compra'];
		$numeroActual = $row['numeracion'];
		
		// EDITA LA NUMERACION DEL TIPO DE PEDIDO DE COMPRA
		$updateSQL = sprintf("UPDATE iv_tipo_pedido_compra SET numeracion = (numeracion + 1) WHERE id_tipo_pedido_compra = %s;",
			valTpDato($idTipoPedidoCompra, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA LOS DATOS DEL PEDIDO
		$insertSQL = sprintf("INSERT INTO iv_pedido_compra (id_pedido_compra_propio, id_pedido_compra_referencia, id_empresa, id_proveedor, id_tipo_pedido_compra, fecha, id_moneda, id_moneda_tasa_cambio, id_tasa_cambio, monto_tasa_cambio, estatus_pedido_compra, id_modulo, id_empleado_preparador, monto_exento, subtotal, porcentaje_descuento, subtotal_descuento)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($numeroActual, "text"),
			valTpDato($frmDcto['txtNumeroReferencia'], "text"),
			valTpDato($frmDcto['txtIdEmpresa'], "int"),
			valTpDato($frmDcto['txtIdProv'], "int"),
			valTpDato($frmDcto['lstTipoPedido'], "int"),
			valTpDato(date("Y-m-d",strtotime($frmDcto['txtFecha'])), "date"),
			valTpDato($frmDcto['hddIdMoneda'], "int"),
			valTpDato($frmDcto['lstMoneda'], "int"),
			valTpDato($frmDcto['lstTasaCambio'], "int"),
			valTpDato($frmDcto['txtTasaCambio'], "real_inglesa"),
			valTpDato($frmDcto['rbtEstatusPedido'], "int"),
			valTpDato(0, "int"),
			valTpDato($frmDcto['hddIdEmpleado'], "int"),
			valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
			valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idPedido = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// VERIFICA SI LOS ARTICULOS ALMACENADOS EN LA BD EN EL PEDIDO AUN ESTAN AGREGADOS EN EL FORMULARIO
	$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle WHERE id_pedido_compra = %s;",
		valTpDato($idPedido, "int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
		$existeDet = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				$hddIdPedidoDetItm = $frmListaArticulo['hddIdPedidoDetItm'.$valor];
				$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$valor];
				
				$hddIdArancelFamiliaItm = $frmListaArticulo['hddIdArancelFamiliaItm'.$valor];
				$lstTarifaAdValorem = $frmListaArticulo['lstTarifaAdValorem'.$valor];
				
				$txtGastosItm = $frmListaArticulo['txtGastosItm'.$valor] / str_replace(",","",$frmListaArticulo['txtCantItm'.$valor]);
				
				if ($rowPedidoDet['id_pedido_compra_detalle'] == $hddIdPedidoDetItm) {
					$existeDet = true;
					
					// ELIMINA LOS IMPUESTOS DEL DETALLE DEL PEDIDO
					$deleteSQL = sprintf("DELETE FROM iv_pedido_compra_detalle_impuesto WHERE id_pedido_compra_detalle = %s;",
						valTpDato($hddIdPedidoDetItm, "int"));
					$Result1 = mysql_query($deleteSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					
					// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
					$contIvaItm = 0;
					if (isset($arrayObjIvaItm)) {
						foreach ($arrayObjIvaItm as $indice1 => $valor1) {
							$valor1 = explode(":", $valor1);
							if ($valor1[0] == $valor) {
								$contIvaItm++;
								
								$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
								$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
								$hddEstatusIvaItm = $frmListaArticulo['hddEstatusIvaItm'.$valor.':'.$valor1[1]];
								
								$insertSQL = sprintf("INSERT INTO iv_pedido_compra_detalle_impuesto (id_pedido_compra_detalle, id_impuesto, impuesto) 
								VALUE (%s, %s, %s);",
									valTpDato($hddIdPedidoDetItm, "int"),
									valTpDato($hddIdIvaItm, "int"),
									valTpDato($hddIvaItm, "real_inglesa"));
								mysql_query("SET NAMES 'utf8';");
								$Result1 = mysql_query($insertSQL);
								if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
								mysql_query("SET NAMES 'latin1';");
							}
						}
					}
					
					$hddIdIvaItm = ($contIvaItm == 1) ? $hddIdIvaItm : "";
					$hddIvaItm = ($contIvaItm == 1) ? $hddIvaItm : 0;
					$hddEstatusIvaItm = ($contIvaItm == 1) ? $hddEstatusIvaItm : "1";
					
					// EDITA EL IMPUESTO DEL GASTO
					$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
						gasto_unitario = %s,
						estatus_iva = %s,
						id_iva = %s,
						iva = %s,
						id_arancel_familia = %s,
						porcentaje_grupo = %s
					WHERE id_pedido_compra_detalle = %s;",
						valTpDato($txtGastosItm, "real_inglesa"),
						valTpDato($hddEstatusIvaItm, "boolean"),
						valTpDato($hddIdIvaItm, "int"),
						valTpDato($hddIvaItm, "real_inglesa"),
						valTpDato($hddIdArancelFamiliaItm, "int"),
						valTpDato($lstTarifaAdValorem, "real_inglesa"),
						valTpDato($hddIdPedidoDetItm, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
		
		if ($existeDet == false) {
			$deleteSQL = sprintf("DELETE FROM iv_pedido_compra_detalle WHERE id_pedido_compra_detalle = %s;",
				valTpDato($rowPedidoDet['id_pedido_compra_detalle'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
			$Result1 = actualizarPedidas($idArticulo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
	}
	
	// INSERTA EL DETALLE DEL PEDIDO
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$hddIdPedidoDetItm = $frmListaArticulo['hddIdPedidoDetItm'.$valor];
			$idArticulo = $frmListaArticulo['hddIdArticuloItm'.$valor];
			
			$hddIdArancelFamiliaItm = $frmListaArticulo['hddIdArancelFamiliaItm'.$valor];
			$lstTarifaAdValorem = $frmListaArticulo['lstTarifaAdValorem'.$valor];
			
			$txtGastosItm = $frmListaArticulo['txtGastosItm'.$valor] / str_replace(",","",$frmListaArticulo['txtCantItm'.$valor]);
			
			if ($idArticulo > 0 && !($hddIdPedidoDetItm > 0)) {
				$insertSQL = sprintf("INSERT INTO iv_pedido_compra_detalle (id_pedido_compra, id_articulo, cantidad, pendiente, precio_unitario, gasto_unitario, id_arancel_familia, porcentaje_grupo, tipo, id_cliente)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idPedido, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($frmListaArticulo['txtCantItm'.$valor], "real_inglesa"),
					valTpDato($frmListaArticulo['txtCantItm'.$valor], "real_inglesa"),
					valTpDato($frmListaArticulo['txtCostoItm'.$valor], "real_inglesa"),
					valTpDato($txtGastosItm, "real_inglesa"),
					valTpDato($hddIdArancelFamiliaItm, "int"),
					valTpDato($lstTarifaAdValorem, "real_inglesa"),
					valTpDato($frmListaArticulo['hddTipoItm'.$valor], "boolean"), // 0 = Reposicion, 1 = Cliente
					valTpDato($frmListaArticulo['hddIdClienteItm'.$valor], "text"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$hddIdPedidoDetItm = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
					
				// ELIMINA LOS IMPUESTOS DEL DETALLE DEL PEDIDO
				$deleteSQL = sprintf("DELETE FROM iv_pedido_compra_detalle_impuesto WHERE id_pedido_compra_detalle = %s;",
					valTpDato($hddIdPedidoDetItm, "int"));
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$contIvaItm = 0;
				if (isset($arrayObjIvaItm)) {
					foreach ($arrayObjIvaItm as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor) {
							$contIvaItm++;
							
							$hddIdIvaItm = $frmListaArticulo['hddIdIvaItm'.$valor.':'.$valor1[1]];
							$hddIvaItm = $frmListaArticulo['hddIvaItm'.$valor.':'.$valor1[1]];
							$hddEstatusIvaItm = $frmListaArticulo['hddEstatusIvaItm'.$valor.':'.$valor1[1]];
							
							$insertSQL = sprintf("INSERT INTO iv_pedido_compra_detalle_impuesto (id_pedido_compra_detalle, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($hddIdPedidoDetItm, "int"),
								valTpDato($hddIdIvaItm, "int"),
								valTpDato($hddIvaItm, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
						}
					}
				}
				
				$hddIdIvaItm = ($contIvaItm == 1) ? $hddIdIvaItm : "";
				$hddIvaItm = ($contIvaItm == 1) ? $hddIvaItm : 0;
				$hddEstatusIvaItm = ($contIvaItm == 1) ? $hddEstatusIvaItm : "1";
				
				// EDITA EL IMPUESTO
				$updateSQL = sprintf("UPDATE iv_pedido_compra_detalle SET
					id_iva = %s,
					iva = %s,
					estatus_iva = %s
				WHERE id_pedido_compra_detalle = %s;",
					valTpDato($hddIdIvaItm, "int"),
					valTpDato($hddIvaItm, "real_inglesa"),
					valTpDato($hddEstatusIvaItm, "boolean"),
					valTpDato($hddIdPedidoDetItm, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
			
			// VERIFICA SI EL ARTICULO YA ESTA REGISTRADO PARA LA EMPRESA
			$queryArtEmp = sprintf("SELECT * FROM iv_articulos_empresa
			WHERE id_empresa = %s
				AND id_articulo = %s;",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"));
			$rsArtEmp = mysql_query($queryArtEmp);
			if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
			$totalRowsArtEmp = mysql_num_rows($rsArtEmp);
			
			if ($totalRowsArtEmp > 0) { // EN CASO DE QUE YA EXISTIA LO COLOCA COMO ACTIVO NUEVAMENTE
				$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
					estatus = 1
				WHERE id_articulo_empresa = %s;",
					valTpDato($rowArtEmp['id_articulo_empresa'], "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			} else { // SI NO EXISTE EL ARTICULO PARA LA EMPRESA
				$insertSQL = sprintf("INSERT INTO iv_articulos_empresa (id_empresa, id_articulo, estatus)
				VALUE (%s, %s, %s);",
					valTpDato($idEmpresa, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato(1, "boolean")); // 0 = Inactivo, 1 = Activo
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
			
			// ACTUALIZA LOS SALDOS DEL ARTICULO (PEDIDAS)
			$Result1 = actualizarPedidas($idArticulo);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
		}
	}
	
	// ELIMINA LOS GASTOS DEL PEDIDO
	$deleteSQL = sprintf("DELETE FROM iv_pedido_compra_gasto WHERE id_pedido_compra = %s",
		valTpDato($idPedido, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// INSERTA LOS GASTOS DEL PEDIDO
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			$idGasto = $frmTotalDcto['hddIdGasto'.$valor];
			
			$txtPorcGasto = str_replace(",", "", $frmTotalDcto['txtPorcGasto'.$valor]);
			$txtMontoGasto = str_replace(",", "", $frmTotalDcto['txtMontoGasto'.$valor]);
			
			if (round($txtMontoGasto, 2) != 0) {
				$insertSQL = sprintf("INSERT INTO iv_pedido_compra_gasto (id_pedido_compra, id_gasto, tipo, porcentaje_monto, monto)
				SELECT %s, id_gasto, %s, %s, %s FROM pg_gastos WHERE id_gasto = %s;",
					valTpDato($idPedido, "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valor], "int"),
					valTpDato($txtPorcGasto, "real_inglesa"),
					valTpDato($txtMontoGasto, "real_inglesa"),
					valTpDato($idGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$hddIdPedidoGasto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// RECOLECTA LOS IMPUESTOS INCLUIDOS DE CADA ITEM
				$contIvaGasto = 0;
				if (isset($arrayObjIvaGasto)) {
					foreach ($arrayObjIvaGasto as $indice1 => $valor1) {
						$valor1 = explode(":", $valor1);
						if ($valor1[0] == $valor) {
							$contIvaGasto++;
							
							$hddIdIvaGasto = $frmTotalDcto['hddIdIvaGasto'.$valor.':'.$valor1[1]];
							$hddIvaGasto = $frmTotalDcto['hddIvaGasto'.$valor.':'.$valor1[1]];
							$hddEstatusIvaGasto = $frmTotalDcto['hddEstatusIvaGasto'.$valor.':'.$valor1[1]];
							
							$insertSQL = sprintf("INSERT INTO iv_pedido_compra_gasto_impuesto (id_pedido_compra_gasto, id_impuesto, impuesto) 
							VALUE (%s, %s, %s);",
								valTpDato($hddIdPedidoGasto, "int"),
								valTpDato($hddIdIvaGasto, "int"),
								valTpDato($hddIvaGasto, "real_inglesa"));
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL);
							if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
							mysql_query("SET NAMES 'latin1';");
						}
					}
				}
				
				$hddIdIvaGasto = ($contIvaGasto == 1) ? $hddIdIvaGasto : "";
				$hddIvaGasto = ($contIvaGasto == 1) ? $hddIvaGasto : 0;
				$hddEstatusIvaGasto = ($contIvaGasto == 1) ? $hddEstatusIvaGasto : "1";
				
				// EDITA EL IMPUESTO
				$updateSQL = sprintf("UPDATE iv_pedido_compra_gasto SET
					id_iva = %s,
					iva = %s,
					estatus_iva = %s
				WHERE id_pedido_compra_gasto = %s;",
					valTpDato($hddIdIvaGasto, "int"),
					valTpDato($hddIvaGasto, "real_inglesa"),
					valTpDato($hddEstatusIvaGasto, "boolean"),
					valTpDato($hddIdPedidoGasto, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}
	
	// ELIMINA LOS IMPUESTOS DEL PEDIDO
	$deleteSQL = sprintf("DELETE FROM iv_pedido_compra_iva WHERE id_pedido_compra = %s",
		valTpDato($idPedido, "int"));
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// INSERTA LOS IMPUESTOS DEL PEDIDO
	if (isset($arrayObjIva)) {
		foreach ($arrayObjIva as $indice => $valor) {
			$insertSQL = sprintf("INSERT INTO iv_pedido_compra_iva (id_pedido_compra, base_imponible, subtotal_iva, id_iva, iva)
			VALUE (%s, %s, %s, %s, %s);",
				valTpDato($idPedido, "int"),
				valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
				valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
				valTpDato($frmTotalDcto['hddIdIva'.$valor], "int"),
				valTpDato($frmTotalDcto['txtIva'.$valor], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->assign("txtIdPedido","value",$idPedido);
	$objResponse->assign("txtNumeroPedidoPropio","value",$numeroPedidoPropio);
	
	$objResponse->alert(utf8_encode("Pedido Guardado con Éxito"));
	
	if ($frmDcto['rbtEstatusPedido'] == 1) {
		$objResponse->script("
		cerrarVentana = true;
		window.location.href='iv_pedido_compra_formato_pdf.php?valBusq=".$idPedido."';");
	} else {
		$objResponse->script("
		cerrarVentana = true;
		window.location.href='iv_pedido_compra_list.php';");
	}
	
	return $objResponse;
}

function importarDcto($frmImportarPedido, $frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$inputFileType = 'Excel5';
	//$inputFileType = 'Excel2007';
	//$inputFileType = 'Excel2003XML';
	//$inputFileType = 'OOCalc';
	//$inputFileType = 'Gnumeric';
	$inputFileName = 'reportes/tmp/'.$frmImportarPedido['hddUrlArchivo'];
	
	$phpExcel = new PHPExcel_Reader_Excel2007();
	$archivoExcel = $phpExcel->load($inputFileName);
	
	$archivoExcel->setActiveSheetIndex(0);
	$i = 1;
	while (strlen($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()) > 0) {
		$cantPedida = $archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue();
		
		if ($itemExcel == true && doubleval($cantPedida) > 0) {
			$arrayFilaImportar[] = array(
				"codigo_articulo" => $archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue(),
				"codigo_arancel" => $archivoExcel->getActiveSheet()->getCell('B'.$i)->getValue(),
				"porcentaje_grupo" => $archivoExcel->getActiveSheet()->getCell('C'.$i)->getValue(),
				"cantidad" => $archivoExcel->getActiveSheet()->getCell('D'.$i)->getValue(),
				"costo_unitario" => $archivoExcel->getActiveSheet()->getCell('E'.$i)->getValue(),
				"id_cliente" => $archivoExcel->getActiveSheet()->getCell('F'.$i)->getValue());
		}
		
		if (trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_decode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper(utf8_encode($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue()))) == trim(strtoupper("Código"))
		|| trim(strtoupper($archivoExcel->getActiveSheet()->getCell('A'.$i)->getValue())) == trim(strtoupper("Codigo"))) {
			$itemExcel = true;
		}
		
		$i++;
	}
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if (isset($arrayFilaImportar)) {
		foreach ($arrayFilaImportar as $indiceFilaImportar => $valorFilaImportar) {
			// RUTINA PARA AGREGAR EL ARTICULO
			$idEmpresa = ($frmDcto['txtIdEmpresa'] > 0) ? $frmDcto['txtIdEmpresa'] : $_SESSION['idEmpresaUsuarioSysGts'];
				
			// BUSCA LOS DATOS DEL ARTICULO
			$queryArticulo = sprintf("SELECT 
				art.id_articulo,
				art.codigo_articulo
			FROM iv_articulos art
				INNER JOIN iv_marcas marca ON (art.id_marca = marca.id_marca)
				INNER JOIN iv_tipos_articulos tipo_art ON (art.id_tipo_articulo = tipo_art.id_tipo_articulo)
				INNER JOIN iv_subsecciones subseccion ON (subseccion.id_subseccion = art.id_subseccion)
				INNER JOIN iv_secciones seccion ON (subseccion.id_seccion = seccion.id_seccion)
			WHERE art.codigo_articulo LIKE %s;",
				valTpDato($arrayFilaImportar[$indiceFilaImportar]['codigo_articulo'], "text"));
			$rsArticulo = mysql_query($queryArticulo);
			if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsArticulo = mysql_num_rows($rsArticulo);
			$rowArticulo = mysql_fetch_assoc($rsArticulo);
			
			$idArticulo = $rowArticulo['id_articulo'];

			// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Compra)
			$queryConfig6 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
				INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
			WHERE config.id_configuracion = 6 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato($idEmpresa, "int"));
			$rsConfig6 = mysql_query($queryConfig6);
			if (!$rsConfig6) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowConfig6 = mysql_fetch_assoc($rsConfig6);
			
			$existe = false;
			if (isset($arrayObj)) {
				foreach ($arrayObj as $indice => $valor) {
					if ($frmListaArticulo['hddIdArticuloItm'.$valor] == $idArticulo && $idArticulo > 0) {
						$existe = true;
					}
				}
			}
			
			$idMoneda = $frmDcto['hddIdMoneda'];
			$idMonedaFactura = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
			
			if ($existe == false) {
				if (count($arrayObj) < $rowConfig6['valor']) {
					if ($totalRowsArticulo > 0) {
						// BUSCA LOS DATOS DEL ARANCEL
						$queryArancelFamilia = sprintf("SELECT 
							arancel_fam.id_arancel_familia,
							arancel_fam.id_arancel_grupo,
							arancel_fam.codigo_familia,
							arancel_fam.codigo_arancel,
							arancel_fam.descripcion_arancel,
							arancel_grupo.codigo_grupo,
							arancel_grupo.porcentaje_grupo
						FROM pg_arancel_familia arancel_fam
							INNER JOIN pg_arancel_grupo arancel_grupo ON (arancel_fam.id_arancel_grupo = arancel_grupo.id_arancel_grupo)
						WHERE arancel_fam.codigo_arancel LIKE %s;", 
							valTpDato($arrayFilaImportar[$indiceFilaImportar]['codigo_arancel'], "text"));
						$rsArancelFamilia = mysql_query($queryArancelFamilia);
						if (!$rsArancelFamilia) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
						$rowArancelFamilia = mysql_fetch_assoc($rsArancelFamilia);
						
						$hddIdArancelFamiliaItm = ($arrayFilaImportar[$indiceFilaImportar]['codigo_arancel'] == -1) ? $arrayFilaImportar[$indiceFilaImportar]['codigo_arancel'] : $rowArancelFamilia['id_arancel_familia'];
						
						// BUSCA LOS DATOS DEL IVA DE VENTA PREDETERMINADO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
						$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (1) AND iva.estado = 1 AND iva.activo = 1;");
						$rsIva = mysql_query($queryIva);
						if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$arrayIdIvaItm = array(-1);
						while ($rowIva = mysql_fetch_assoc($rsIva)) {
							$arrayIdIvaItm[] = $rowPedidoDetImpuesto['id_impuesto'];
						}
						
						$cantPedida = $arrayFilaImportar[$indiceFilaImportar]['cantidad'];
						$costoUnitario = $arrayFilaImportar[$indiceFilaImportar]['costo_unitario'];
						$hddIdIvaItm = implode(",",$arrayIdIvaItm);
						$gastoUnitario = 0;
						$lstTarifaAdValorem = $arrayFilaImportar[$indiceFilaImportar]['porcentaje_grupo'];
						$hddTipoItm = 0;
						$idClienteItm = $arrayFilaImportar[$indiceFilaImportar]['id_cliente'];
						
						$Result1 = insertarItemArticulo($contFila, "", $idArticulo, $cantPedida, $costoUnitario, $hddIdIvaItm, $gastoUnitario, $hddIdArancelFamiliaItm, $lstTarifaAdValorem, $hddTipoItm, $idClienteItm);
						if ($Result1[0] != true && strlen($Result1[1]) > 0) {
							return $objResponse->alert($Result1[1]);
						} else if ($Result1[0] == true) {
							$contFila = $Result1[2];
							$frmListaArticulo['hddIdArticuloItm'.$contFila] = $idArticulo;
							$objResponse->script($Result1[1]);
							$arrayObj[] = $contFila;
						}
					} else {
						$arrayObjNoExiste[] = $arrayFilaImportar[$indiceFilaImportar]['codigo_articulo'];
					}
				} else {
					$msjCantidadExcedida = "Solo puede agregar un máximo de ".$rowConfig6['valor']." items por Pedido";
				}
			} else {
				$arrayObjExiste[] = $arrayFilaImportar[$indiceFilaImportar]['codigo_articulo'];
			}
		}
		$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
		if (strlen($msjCantidadExcedida) > 0)
			$objResponse->alert(utf8_encode($msjCantidadExcedida));
			
		if (count($arrayObjNoExiste) > 0)
			$objResponse->alert(utf8_encode("No existe(n) en el sistema ".count($arrayObjNoExiste)." items: ".implode(", ",$arrayObjNoExiste)));
			
		if (count($arrayObjExiste) > 0) {
			$objResponse->alert(utf8_encode("Ya se encuentra(n) incluido(s) ".count($arrayObjExiste)." items: ".implode(", ",$arrayObjExiste)));
		} else if (count($arrayObj) > 0) {
			$objResponse->alert(utf8_encode("Pedido Importado con Éxito"));
		} else {
			$objResponse->alert(utf8_encode("No se pudo importar el archivo"));
		}
		
		$objResponse->script("
		byId('btnCancelarImportarPedido').click();");
	} else {
		$objResponse->alert(utf8_encode("Verifique que el Pedido tenga Cantidades Solicitadas"));
	}
	
	return $objResponse;
}

function insertarArticulo($frmArticulo, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	$contFila = $frmArticulo['hddNumeroArt'];
	$idEmpresa = $frmArticulo['hddIdEmpresa'];
	$idArticulo = $frmArticulo['hddIdArticulo'];
	$idCasilla = $frmArticulo['lstCasillaArt'];
	$cantPedida = str_replace(",","",$frmArticulo['txtCantidadArt']);
	
	$costoUnitario = str_replace(",","",$frmArticulo['txtCostoArt']);
	$gastoUnitario = str_replace(",","",$frmListaArticulo['txtGastosItm'.$contFila]);
	$hddIdIvaItm = implode(",",$frmArticulo['lstIvaArt']);
	
	$hddTipoItm = (!$frmArticulo['rbtTipoArt']) ? 0 : $frmArticulo['rbtTipoArt'];
	$idClienteItm = $frmArticulo['txtIdClienteArt'];

	// VERIFICA VALORES DE CONFIGURACION (Cantidad de Items para Compra)
	$queryConfigItemCompra = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 6 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfigItemCompra = mysql_query($queryConfigItemCompra);
	if (!$rsConfigItemCompra) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowConfig6 = mysql_fetch_assoc($rsConfigItemCompra);
	
	if ($contFila > 0) {
		// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
		WHERE iva.tipo IN (1,8,3)
			AND iva.idIva IN (%s);",
			valTpDato($hddIdIvaItm, "campo"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contIva = 0;
		$ivaUnidad = "";
		while ($rowIva = mysql_fetch_assoc($rsIva)) {
			$contIva++;
			
			$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
			"<input id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
				$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
				$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
				$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
				$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
				$contFila.":".$contIva);
		}
			
		$objResponse->assign("txtCantItm".$contFila,"value",number_format($cantPedida, 2, ".", ","));
		$objResponse->assign("tdCantPendItm".$contFila,"innerHTML",$cantPedida);
		$objResponse->assign("txtCostoItm".$contFila,"value",number_format($costoUnitario, 2, ".", ","));
		$objResponse->assign("txtTotalItm".$contFila,"value",number_format(($cantPedida * $costoUnitario), 2, ".", ","));
		
		$objResponse->assign("divIvaItm".$contFila,"innerHTML",$ivaUnidad);
		
		$objResponse->script("
		if (byId('hddNumeroArt').value > 0) {
		} else {
			document.forms['frmArticulo'].reset();
			byId('txtDescripcionArt').innerHTML = '';
			
			byId('rbtTipoArtReposicion').click();
		}
		
		if (byId('txtCodigoArticulo0').value.length > 0) {
			byId('txtCodigoArticulo0').focus();
			byId('txtCodigoArticulo0').select();
		} else if (byId('txtCriterioBuscarArticulo').value.length > 0) {
			byId('txtCriterioBuscarArticulo').focus();
			byId('txtCriterioBuscarArticulo').select();
		} else {
			byId('txtCodigoArticulo0').focus();
			byId('txtCodigoArticulo0').select();
		}");
		
		$objResponse->assign("divListaArticulo","innerHTML","");
	} else {
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObj = $frmListaArticulo['cbx'];
		$contFila = $arrayObj[count($arrayObj)-1];
		
		if (count($arrayObj) < $rowConfig6['valor']) {
			$Result1 = insertarItemArticulo($contFila, "", $idArticulo, $cantPedida, $costoUnitario, $hddIdIvaItm, $gastoUnitario, $hddIdArancelFamiliaItm, $lstTarifaAdValorem, $hddTipoItm, $idClienteItm);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
			
			$objResponse->script("
			if (byId('hddNumeroArt').value > 0) {
			} else {
				document.forms['frmArticulo'].reset();
				byId('txtDescripcionArt').innerHTML = '';
				
				byId('rbtTipoArtReposicion').click();
			}
				
			if (byId('txtCodigoArticulo0').value.length > 0) {
				byId('txtCodigoArticulo0').focus();
				byId('txtCodigoArticulo0').select();
			} else if (byId('txtCriterioBuscarArticulo').value.length > 0) {
				byId('txtCriterioBuscarArticulo').focus();
				byId('txtCriterioBuscarArticulo').select();
			} else {
				byId('txtCodigoArticulo0').focus();
				byId('txtCodigoArticulo0').select();
			}");
			
			$objResponse->assign("divListaArticulo","innerHTML","");
		} else {
			$objResponse->alert(utf8_encode("Solo puede agregar un máximo de ".$rowConfig6['valor']." items por Pedido"));
		}
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function insertarGasto($idGasto, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjGasto = $frmTotalDcto['cbxGasto'];
	$contFilaGasto = $arrayObjGasto[count($arrayObjGasto)-1];
	
	$existe = false;
	if (isset($arrayObjGasto)) {
		foreach ($arrayObjGasto as $indice => $valor) {
			if ($frmTotalDcto['hddIdGasto'.$valor] == $idGasto) {
				$existe = true;
			}
		}
	}
	
	if ($existe == false) {
		$Result1 = insertarItemGasto($contFilaGasto, $idGasto);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFilaGasto = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObjGasto[] = $contFilaGasto;
		}
	} else {
		$objResponse->alert("Este item ya se encuentra incluido");
	}
	
	$objResponse->script("xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function listaArticulo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("art_emp.estatus = 1");
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.id_modo_compra = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("art.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	if (strlen($valCadBusq[4]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($valCadBusq[3]) {
			case 1 : 
				$sqlBusq .= $cond.sprintf("(SELECT marca.marca FROM iv_marcas marca
				WHERE marca.id_marca = art.id_marca) LIKE %s", valTpDato("%".$valCadBusq[4]."%", "text"));
				break;
			case 2 : 
				$sqlBusq .= $cond.sprintf("(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
				WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) LIKE %s", valTpDato("%".$valCadBusq[4]."%", "text"));
				break;
			case 3 : 
				$sqlBusq .= $cond.sprintf("(SELECT sec.descripcion
				FROM iv_subsecciones subsec
					INNER JOIN iv_secciones sec ON (subsec.id_seccion = sec.id_seccion)
				WHERE subsec.id_subseccion = vw_iv_articulos_empresa.id_subseccion) LIKE %s", valTpDato("%".$valCadBusq[4]."%", "text"));
				break;
			case 4 : 
				$sqlBusq .= $cond.sprintf("(SELECT subsec.descripcion
				FROM iv_subsecciones subsec
				WHERE subsec.id_subseccion = vw_iv_articulos_empresa.id_subseccion) LIKE %s", valTpDato("%".$valCadBusq[4]."%", "text"));
				break;
			case 5 : $sqlBusq .= $cond.sprintf("art.descripcion LIKE %s", valTpDato("%".$valCadBusq[4]."%", "text")); break;
			case 6 : $sqlBusq .= $cond.sprintf("art.id_articulo = %s", valTpDato($valCadBusq[4], "int")); break;
			case 7 : $sqlBusq .= $cond.sprintf("art.codigo_articulo_prov LIKE %s", valTpDato("%".$valCadBusq[4]."%", "text")); break;
		}
	}
	
	$query = sprintf("SELECT art_emp.*,
		art.codigo_articulo,
		art.descripcion,
		art.codigo_articulo_prov,
		
		(SELECT marca.marca FROM iv_marcas marca
		WHERE marca.id_marca = art.id_marca) AS marca,
		
		(SELECT tipo_art.descripcion FROM iv_tipos_articulos tipo_art
		WHERE tipo_art.id_tipo_articulo = art.id_tipo_articulo) AS tipo_articulo,
		
		art_emp.clasificacion,
		arancel_fam.codigo_arancel,
		arancel_fam.descripcion_arancel,
		arancel_grup.porcentaje_grupo
	FROM iv_articulos_empresa art_emp
		INNER JOIN iv_articulos art ON (art_emp.id_articulo = art.id_articulo)
		LEFT JOIN pg_arancel_familia arancel_fam ON (art.id_arancel_familia = arancel_fam.id_arancel_familia)
		LEFT JOIN pg_arancel_grupo arancel_grup ON (arancel_fam.id_arancel_grupo = arancel_grup.id_arancel_grupo) %s", $sqlBusq);
	
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna texto_10px\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "14%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "56%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "12%", $pageNum, "codigo_articulo_prov", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código Prov."));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "14%", $pageNum, "descripcion_tipo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo Artículo"));
		$htmlTh .= ordenarCampo("xajax_listaArticulo", "4%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Clasif."));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarArticulo('".$row['id_articulo']."', xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'],";")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
				$htmlTb .= "</tr>";
			if (strlen($row['codigo_arancel']) > 0) {
				$htmlTb .= "<tr class=\"textoNegrita_10px\">";
					$htmlTb .= "<td title=\"".utf8_encode($row['descripcion_arancel'])."\" width=\"100%\">".utf8_encode($row['codigo_arancel'])." (".number_format($row['porcentaje_grupo'], 2, ".", ",")."%)</td>";
				$htmlTb .= "</tr>";
			}
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['codigo_articulo_prov'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['tipo_articulo']." (".$row['marca'].")")."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".utf8_encode("Clasificación A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".utf8_encode("Clasificación B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".utf8_encode("Clasificación C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".utf8_encode("Clasificación D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".utf8_encode("Clasificación E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".utf8_encode("Clasificación F")."\"/>"; break;
				}
			$htmlTb .= "</td>";
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
	
	$objResponse->assign("divListaCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaGasto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modo_gasto IN (1)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nombre LIKE %s",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		gasto.id_gasto,
		gasto.nombre,
		gasto.estatus_iva,
		gasto.id_modo_gasto,
		gasto.afecta_documento,
		gasto.asocia_documento,
		
		(SELECT SUM(iva.iva)
		FROM pg_gastos_impuesto gasto_impuesto
			INNER JOIN pg_iva iva ON (gasto_impuesto.id_impuesto = iva.idIva)
		WHERE gasto_impuesto.id_gasto = gasto.id_gasto
			AND iva.tipo IN (1,8,3)) AS iva
	FROM pg_gastos gasto %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaGasto", "60%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaGasto", "20%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaGasto", "20%", $pageNum, "afecta_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Afecta Cuenta por Pagar");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$afectaCuentaPorPagar = ($row['afecta_documento'] == 1) ? "Si" : "-";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarGasto%s\" onclick=\"validarInsertarGasto('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
					$contFila,
					$row['id_gasto']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"right\">".(($row['iva'] > 0) ? number_format($row['iva'], 2, ".", ",") : "-")."</td>";
			$htmlTb .= "<td align=\"center\">".($afectaCuentaPorPagar)."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"4\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaGasto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaGasto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaGasto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanProvCxP;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		id_proveedor,
		nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "10%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "18%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "56%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarMoneda");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarGasto");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstArancelGrupoBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstIva");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstTasaCambio");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoPedido");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarGasto");
$xajax->register(XAJAX_FUNCTION,"formArticuloImpuesto");
$xajax->register(XAJAX_FUNCTION,"formDatosCliente");
$xajax->register(XAJAX_FUNCTION,"importarDcto");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"insertarGasto");
$xajax->register(XAJAX_FUNCTION,"listaArticulo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaGasto");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");

function cargaLstArancelGrupoItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$class = ($bloquearObj == true) ? "" : "class=\"inputCompleto\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM pg_arancel_grupo ORDER BY porcentaje_grupo;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"min-width:60px\">";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId != "" && doubleval($selId) == doubleval($row['porcentaje_grupo'])) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['porcentaje_grupo']."\">".number_format($row['porcentaje_grupo'], 2, ".", ",")."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function insertarItemArticulo($contFila, $idPedidoDetalle = "", $idArticulo = "", $cantPedida = "", $costoUnitario = "", $hddIdIvaItm = "", $gastoUnitario = 0, $hddIdArancelFamiliaItm = "", $lstTarifaAdValorem = "", $hddTipoItm = "0", $idClienteItm = "") {
	$contFila++;
	
	if ($idPedidoDetalle > 0) {
		// BUSCA EL DETALLE DEL PEDIDO
		$queryPedidoDet = sprintf("SELECT * FROM iv_pedido_compra_detalle WHERE id_pedido_compra_detalle = %s;",
			valTpDato($idPedidoDetalle, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		// BUSCA LOS IMPUESTOS DEL DETALLE
		$queryPedidoDetImpuesto = sprintf("SELECT * FROM iv_pedido_compra_detalle_impuesto WHERE id_pedido_compra_detalle = %s;",
			valTpDato($idPedidoDetalle, "int"));
		$rsPedidoDetImpuesto = mysql_query($queryPedidoDetImpuesto);
		if (!$rsPedidoDetImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsPedidoDetImpuesto = mysql_num_rows($rsPedidoDetImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowPedidoDetImpuesto = mysql_fetch_assoc($rsPedidoDetImpuesto)) {
			$arrayIdIvaItm[] = $rowPedidoDetImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	} else {
		// BUSCA LOS IMPUESTOS DEL GASTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryArticuloImpuesto = sprintf("SELECT art_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
		FROM pg_iva iva
  			INNER JOIN iv_articulos_impuesto art_impuesto ON (iva.idIva = art_impuesto.id_impuesto)
		WHERE iva.tipo IN (1,8,3) AND iva.estado = 1
			AND art_impuesto.id_articulo = %s
		ORDER BY iva;",
			valTpDato($idArticulo, "int"));
		$rsArticuloImpuesto = mysql_query($queryArticuloImpuesto);
		if (!$rsArticuloImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsArticuloImpuesto = mysql_num_rows($rsArticuloImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowArticuloImpuesto = mysql_fetch_assoc($rsArticuloImpuesto)) {
			$arrayIdIvaItm[] = $rowArticuloImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	}
	
	$idArticulo = ($idArticulo == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_articulo'] : $idArticulo;
	$cantPedida = ($cantPedida == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['cantidad'] : $cantPedida;
	$cantRecibida = ($cantRecibida == "" && $totalRowsPedidoDet > 0) ? ($rowPedidoDet['cantidad'] - $rowPedidoDet['pendiente']) : $cantRecibida;
	$costoUnitario = ($costoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['precio_unitario'] : $costoUnitario;
	$gastoUnitario = ($gastoUnitario == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['gasto_unitario'] : $gastoUnitario;
	$hddIdArancelFamiliaItm = ($hddIdArancelFamiliaItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_arancel_familia'] : $hddIdArancelFamiliaItm;
	$lstTarifaAdValorem = ($lstTarifaAdValorem == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['porcentaje_grupo'] : $lstTarifaAdValorem;
	$hddTipoItm = ($hddTipoItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['tipo'] : $hddTipoItm;
	$idClienteItm = ($idClienteItm == "" && $totalRowsPedidoDet > 0) ? $rowPedidoDet['id_cliente'] : $idClienteItm;
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM iv_articulos WHERE id_articulo = %s;",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
	$totalRowsArticulo = mysql_num_rows($rsArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
	WHERE iva.tipo IN (1,8,3)
		AND iva.idIva IN (%s);",
		valTpDato($hddIdIvaItm, "campo"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
	$contIva = 0;
	$ivaUnidad = "";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$contIva++;
		
		$ivaUnidad .= sprintf("<input type=\"text\" id=\"hddIvaItm%s:%s\" name=\"hddIvaItm%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddIdIvaItm%s:%s\" name=\"hddIdIvaItm%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddLujoIvaItm%s:%s\" name=\"hddLujoIvaItm%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddEstatusIvaItm%s:%s\" name=\"hddEstatusIvaItm%s:%s\" value=\"%s\">".
		"<input id=\"cbxIvaItm\" name=\"cbxIvaItm[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">", 
			$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
			$contFila.":".$contIva);
	}
	
	if ((!($totalRowsPedidoDet > 0) && !($hddIdArancelFamiliaItm > 0) && $rowArticulo['id_arancel_familia'] > 0) || $hddIdArancelFamiliaItm == -1 || $hddIdArancelFamiliaItm == "") {
		$hddIdArancelFamiliaItm = $rowArticulo['id_arancel_familia'];
		$lstTarifaAdValorem = "";
	}
	
	// BUSCA LOS DATOS DEL ARANCEL
	$queryArancelFamilia = sprintf("SELECT 
		arancel_fam.id_arancel_familia,
		arancel_fam.id_arancel_grupo,
		arancel_fam.codigo_familia,
		arancel_fam.codigo_arancel,
		arancel_fam.descripcion_arancel,
		arancel_grupo.codigo_grupo,
		arancel_grupo.porcentaje_grupo
	FROM pg_arancel_familia arancel_fam
		INNER JOIN pg_arancel_grupo arancel_grupo ON (arancel_fam.id_arancel_grupo = arancel_grupo.id_arancel_grupo)
	WHERE arancel_fam.id_arancel_familia = %s;", 
		valTpDato($hddIdArancelFamiliaItm, "int"));
	$rsArancelFamilia = mysql_query($queryArancelFamilia);
	if (!$rsArancelFamilia) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
	$rowArancelFamilia = mysql_fetch_assoc($rsArancelFamilia);
	
	$lstTarifaAdValorem = ((!($totalRowsPedidoDet > 0) && !($lstTarifaAdValorem > 0) && $rowArticulo['id_arancel_familia'] > 0) || $lstTarifaAdValorem == -1 || $lstTarifaAdValorem == "") ? $rowArancelFamilia['porcentaje_grupo'] : $lstTarifaAdValorem;
	
	$arancelArticulo = sprintf("<span class=\"textoNegroNegrita_10px\" title=\"%s\">%s</span>",
		utf8_encode($rowArancelFamilia['descripcion_arancel']),
		utf8_encode($rowArancelFamilia['codigo_arancel']));
	
	if ($idClienteItm > 0) {
		$imgCliente = sprintf("<a class=\"modalImg\" id=\"aClienteItem:%s\" rel=\"#divFlotante1\"><img class=\"puntero\" src=\"../img/iconos/ico_cliente.gif\" title=\"Ver Cliente\"/>",
			$contFila);
	}
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtCodigoArticuloItm%s\" name=\"txtCodigoArticuloItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left;\" value=\"%s\"></td>".
			"<td><div id=\"divDescripcionArticuloItm%s\">%s</div>%s</td>".
			"<td><input type=\"text\" id=\"txtCantItm%s\" name=\"txtCantItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"txtCantRecibItm%s\" name=\"txtCantRecibItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td id=\"tdCantPendItm%s\" align=\"right\">%s</td>".
			"<td><input type=\"text\" id=\"txtCostoItm%s\" name=\"txtCostoItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddMontoDescuentoItm%s\" name=\"hddMontoDescuentoItm%s\" value=\"%s\"/></td>".
			"<td><div id=\"divIvaItm%s\">%s</div></td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdArancelFamiliaItm%s\" name=\"hddIdArancelFamiliaItm%s\" class=\"inputSinFondo\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtTotalItm%s\" name=\"txtTotalItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdPedidoDetItm%s\" name=\"hddIdPedidoDetItm%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdArticuloItm%s\" name=\"hddIdArticuloItm%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"txtGastosItm%s\" name=\"txtGastosItm%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddGastoUnitItm%s\" name=\"hddGastoUnitItm%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddTipoItm%s\" name=\"hddTipoItm%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdClienteItm%s\" name=\"hddIdClienteItm%s\" value=\"%s\">".
			"</td>".
		"</tr>');
		
		byId('%s').onchange = function() {
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}",
		$contFila,
			$contFila, $contFila,
				 $contFila,
			$contFila, $contFila,
			$imgCliente,
			$contFila, $contFila, elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),";"),
			$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowArticulo['descripcion'])))), $arancelArticulo,
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$contFila, $contFila, number_format($cantRecibida, 2, ".", ","),
			$contFila, number_format(($cantPedida - $cantRecibida), 2, ".", ","),
			$contFila, $contFila, number_format($costoUnitario, 2, ".", ","),
				$contFila, $contFila, number_format(0, 2, ".", ","),
			$contFila, $ivaUnidad,
			cargaLstArancelGrupoItm("lstTarifaAdValorem".$contFila, $lstTarifaAdValorem),
				$contFila, $contFila, $hddIdArancelFamiliaItm,
			$contFila, $contFila, number_format(($cantPedida * $costoUnitario), 2, ".", ","),
				$contFila, $contFila, $idPedidoDetalle,
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, number_format($cantPedida * $gastoUnitario, 2, ".", ","),
				$contFila, $contFila, number_format($gastoUnitario, 2, ".", ","),
				$contFila, $contFila, $hddTipoItm, // 0 = Reposicion, 1 = Cliente
				$contFila, $contFila, $idClienteItm,
			
		"lstTarifaAdValorem".$contFila);
	
	if ($idClienteItm > 0) {
		$htmlItmPie .= sprintf("
		byId('aClienteItem:%s').onclick = function() { abrirDivFlotante1(this, 'tblCliente', '%s'); }",
			$contFila, $idClienteItm);
	}
	
	return array(true, $htmlItmPie, $contFila, $arrayObjUbicacion);
}

function insertarItemGasto($contFila, $hddIdGasto, $hddIdPedidoGasto = "") {
	$contFila++;
	
	if ($hddIdPedidoGasto > 0) {
		$queryPedidoDet = sprintf("SELECT ped_comp_gasto.*
		FROM pg_iva iva
			RIGHT JOIN pg_gastos gasto ON (iva.idIva = gasto.id_iva)
			INNER JOIN iv_pedido_compra_gasto ped_comp_gasto ON (gasto.id_gasto = ped_comp_gasto.id_gasto)
		WHERE ped_comp_gasto.id_pedido_compra_gasto = %s;", 
			valTpDato($hddIdPedidoGasto, "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDet = mysql_num_rows($rsPedidoDet);
		$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
		
		$hddIdGasto = $rowPedidoDet['id_gasto'];
		$txtPorcGasto = $rowPedidoDet['porcentaje_monto'];
		$txtMontoGasto = $rowPedidoDet['monto'];
		$txtMedidaGasto = $rowPedidoDet['monto_medida'];
		
		// BUSCA LOS IMPUESTOS DEL GASTO
		$queryPedidoDetImpuesto = sprintf("SELECT * FROM iv_pedido_compra_gasto_impuesto WHERE id_pedido_compra_gasto = %s;",
			valTpDato($hddIdPedidoGasto, "int"));
		$rsPedidoDetImpuesto = mysql_query($queryPedidoDetImpuesto);
		if (!$rsPedidoDetImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsPedidoDetImpuesto = mysql_num_rows($rsPedidoDetImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowPedidoDetImpuesto = mysql_fetch_assoc($rsPedidoDetImpuesto)) {
			$arrayIdIvaItm[] = $rowPedidoDetImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	} else {
		// BUSCA LOS IMPUESTOS DEL GASTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
		$queryGastoImpuesto = sprintf("SELECT gasto_impuesto.*, iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo
		FROM pg_iva iva
  			INNER JOIN pg_gastos_impuesto gasto_impuesto ON (iva.idIva = gasto_impuesto.id_impuesto)
		WHERE iva.tipo IN (1,8,3) AND iva.estado = 1
			AND gasto_impuesto.id_gasto = %s
		ORDER BY iva;",
			valTpDato($hddIdGasto, "int"));
		$rsGastoImpuesto = mysql_query($queryGastoImpuesto);
		if (!$rsGastoImpuesto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
		$totalRowsGastoImpuesto = mysql_num_rows($rsGastoImpuesto);
		$arrayIdIvaItm = array(-1);
		while ($rowGastoImpuesto = mysql_fetch_assoc($rsGastoImpuesto)) {
			$arrayIdIvaItm[] = $rowGastoImpuesto['id_impuesto'];
		}
		$hddIdIvaItm = implode(",",$arrayIdIvaItm);
	}
	
	// BUSCA LOS DATOS DEL GASTO
	$queryGasto = sprintf("SELECT *
	FROM pg_gastos gasto
		LEFT JOIN pg_iva iva ON (gasto.id_iva = iva.idIva)
	WHERE id_gasto = %s;",
		valTpDato($hddIdGasto, "int"));
	$rsGasto = mysql_query($queryGasto);
	if (!$rsGasto) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$rowGasto = mysql_fetch_assoc($rsGasto);
	
	// BUSCA LOS DATOS DEL IMPUESTO (1 = IVA COMPRA, 3 = IMPUESTO LUJO COMPRA, 6 = IVA VENTA, 2 = IMPUESTO LUJO VENTA)
	$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva
	WHERE iva.tipo IN (1,8,3)
		AND iva.idIva IN (%s);",
		valTpDato($hddIdIvaItm, "campo"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$contIva = 0;
	$ivaUnidad = "";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$contIva++;
		
		$ivaUnidad .= sprintf("<table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr><td><img id=\"imgIvaGasto%s:%s\" src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/></td><td width=\"%s\">".
		"<input type=\"text\" id=\"hddIvaGasto%s:%s\" name=\"hddIvaGasto%s:%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddIdIvaGasto%s:%s\" name=\"hddIdIvaGasto%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddLujoIvaGasto%s:%s\" name=\"hddLujoIvaGasto%s:%s\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddEstatusIvaGasto%s:%s\" name=\"hddEstatusIvaGasto%s:%s\" value=\"%s\">".
		"<input id=\"cbxIvaGasto\" name=\"cbxIvaGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td></tr></table>", 
			"100%", $contFila, $contIva, "100%",
			$contFila, $contIva, $contFila, $contIva, $rowIva['iva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['idIva'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['lujo'], 
			$contFila, $contIva, $contFila, $contIva, $rowIva['estado'], 
			$contFila.":".$contIva);
	}
	
	$hddIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIvaGasto : $rowGasto['iva'];
	$hddIdIvaGasto = ($totalRowsPedidoDet > 0) ? $hddIdIvaGasto : $rowGasto['id_iva'];
	$hddEstatusIvaGasto = ($hddEstatusIvaGasto != "" && $totalRowsPedidoDet > 0) ? $hddEstatusIvaGasto : $rowGasto['estatus_iva'];
	
	$displayTblAfectaGasto = ($rowGasto['id_modo_gasto'] == 1 && $rowGasto['afecta_documento'] == 0) ? "" : "style=\"display:none\"";
	
	$htmlAfecta .= sprintf("<table id=\"tblAfectaGasto%s\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" %s width=\"%s\">",
		$contFila,
		$displayTblAfectaGasto,
		"100%");
	$htmlAfecta .= "<tr>";
		$htmlAfecta .= "<td>"."<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"."</td>";
	$htmlAfecta .= "</tr>";
	$htmlAfecta .= "</table>";
	
	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPieGasto').before('".
		"<tr align=\"right\" id=\"trItmGasto:%s\">".
			"<td title=\"trItmGasto:%s\"><input id=\"cbxItmGasto\" name=\"cbxItmGasto[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxGasto\" name=\"cbxGasto[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td class=\"tituloCampo\">%s:</td>".
			"<td><input type=\"text\" id=\"txtPorcGasto%s\" name=\"txtPorcGasto%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\">%s</td>".
			"<td id=\"spnGastoMoneda%s\"></td>".
			"<td><input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td %s><input type=\"text\" id=\"txtMedidaGasto%s\" name=\"txtMedidaGasto%s\" class=\"inputCompleto\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><div id=\"divIvaGasto%s\">%s</div>%s".
				"<input type=\"hidden\" id=\"hddIdGasto%s\" name=\"hddIdGasto%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdModoGasto%s\" name=\"hddIdModoGasto%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdTipoMedida%s\" name=\"hddIdTipoMedida%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddTipoGasto%s\" name=\"hddTipoGasto%s\" value=\"%s\">".
				"<input type=\"hidden\" id=\"hddIdPedidoGasto%s\" name=\"hddIdPedidoGasto%s\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('txtMontoGasto%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Cant', this.value, 'txtPorcGasto%s');
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}
		
		byId('txtMedidaGasto%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_asignarMoneda(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		}",
		$contFila,
			$contFila, $contFila,
				$contFila,
			$rowGasto['nombre'],
			$contFila, $contFila, number_format($txtPorcGasto, 2, ".", ","), "%",
			$contFila,
			$contFila, $contFila, number_format($txtMontoGasto, 2, ".", ","),
			(($rowGasto['id_tipo_medida'] == 1) ? "title=\"Peso Total (g)\"" : ""), $contFila, $contFila, number_format($txtMedidaGasto, 2, ".", ","),
			$contFila, $ivaUnidad, $htmlAfecta,
				$contFila, $contFila, $hddIdGasto,
				$contFila, $contFila, $rowGasto['id_modo_gasto'],
				$contFila, $contFila, $rowGasto['id_tipo_medida'],
				$contFila, $contFila, 1,
				$contFila, $contFila, $hddIdPedidoGasto,
		
		$contFila,
			$contFila,
		
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>