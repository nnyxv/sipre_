<?php


function asignarAccesorio($idAccesorio) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	// ASIGNA ACCESORIO AL SELECCIONAR
	$queryAccesorio = sprintf("SELECT *
	FROM an_accesorio
	WHERE id_accesorio = %s",
		valTpDato($idAccesorio,"int"));
	$rsAccesorio = mysql_query($queryAccesorio);
	if (!$rsAccesorio) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryAccesorio);
	$rowAccesorio = mysql_fetch_assoc($rsAccesorio);
	
	$objResponse->assign("hddIdAccesorio","value",$idAccesorio);
	$objResponse->assign("txtCodigo","value",utf8_encode($rowAccesorio['nom_accesorio']));
	$objResponse->assign("txtAccesorio","value",utf8_encode($rowAccesorio['des_accesorio']));
	$objResponse->assign('txtPrecio',"value",number_format($rowAccesorio['precio_accesorio'], 2, ".", ","));
	
	mysql_query("COMMIT;");
							
	return $objResponse;
}

function asignarPresupuesto($idPresupuesto, $nombreObjeto, $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("pres_vent.id_presupuesto = %s",
		valTpDato($idPresupuesto, "int"));
	
	$query = sprintf("SELECT * FROM an_presupuesto pres_vent %s;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtId".$nombreObjeto,"value",$row['id_presupuesto']);
	
	$objResponse->loadCommands(cargarPresupuesto($row['id_presupuesto']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarListaPresupuesto').click();");
	}
	
	return $objResponse;
}

function buscarAccesorio($frmBuscarAccesorio) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarAccesorio['txtCriterioBuscarAccesorio']);
		
	$objResponse->loadCommands(listaAccesorio(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarCombo($frmBuscarCombo) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarCombo['txtCriterioBuscarCombo']);
		
	$objResponse->loadCommands(listaCombo(0, "nombre_combo", "ASC", $valBusq));
	
	return $objResponse;
}

function buscarPresupuesto($frmBuscarPresupuesto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarPresupuesto['hddObjDestinoPresupuesto'],
		$frmBuscarPresupuesto['txtCriterioBuscarPresupuesto']);
	
	$objResponse->loadCommands(listaPresupuesto(0, "id_presupuesto", "DESC", $valBusq));
		
	return $objResponse;
}

function calcularCombo($frmListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ÍTEMS HAY AGREGADOS
	$arrayObj = $frmListaAccesorio['cbx'];
	
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
				
			$subTotal += str_replace(",","",$frmListaAccesorio['hddPrecioUnitario'.$valor]);
			$totalIva += str_replace(",","",$frmListaAccesorio['hddIvaUnitario'.$valor]);
			$total += str_replace(",","",$frmListaAccesorio['hddPrecioFinal'.$valor]);
		}
	}
	
	$objResponse->assign("txtSubTotal","value",number_format($subTotal, 2, ".", ","));
	$objResponse->assign("txtTotalIva","value",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("txtTotal","value",number_format($total, 2, ".", ","));
	
	if (isset($arrayObj))
		$objResponse->assign("hddObj","value",implode("|",$arrayObj));
	
	return $objResponse;
}

function cargarPresupuesto($idPresupuesto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_combo_presupuesto_list","editar")) { return $objResponse; }
	
	// CONSULTA ENCABEZADO DEL PRESUPUESTO
	$queryEncabezado = sprintf("SELECT *
	FROM cj_cc_cliente cliente
		INNER JOIN an_presupuesto presupuesto ON (cliente.id = presupuesto.id_cliente)
		LEFT JOIN an_presupuesto_accesorio presupuesto_accesorio ON (presupuesto.id_presupuesto = presupuesto_accesorio.id_presupuesto)
	WHERE presupuesto.id_presupuesto = %s;",
		valTpDato($idPresupuesto, "int"));
	$rsEncabezado = mysql_query($queryEncabezado);
	if (!$rsEncabezado) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryEncabezado);	
	$rowEncabezado = mysql_fetch_assoc($rsEncabezado);
	
	$idPresupuestoAccesorio = $rowEncabezado['id_presupuesto_accesorio'];
	$objResponse->assign("hddIdPresupuestoAccesorio","value",$idPresupuestoAccesorio);
	$objResponse->assign("txtIdPresupuesto","value",$idPresupuesto);
	$objResponse->assign("txtIdCliente","value",$rowEncabezado['id_cliente']);
	$objResponse->assign("txtCliente","value",utf8_encode($rowEncabezado['nombre'])." ".htmlentities($rowEncabezado['apellido']));
	$objResponse->assign("txtFecha","value",date(spanDateFormat));
	
	// DESCONCATENA PARA SABER CUANTOS ÍTEMS HAY AGREGADOS
	$arrayObj = $frmListaAccesorio['cbx'];
	if (isset($arrayObj)) {
		foreach($arrayObj as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
	}
	
	$arrayObj = NULL;
	$sigValor = 0;
	
	// CONSULTA DETALLE DEL PRESUPUESTO			
	$queryDetalle = sprintf("SELECT * FROM an_presupuesto_accesorio_detalle
		INNER JOIN an_accesorio ON (an_presupuesto_accesorio_detalle.id_accesorio = an_accesorio.id_accesorio)							
	WHERE id_presupuesto_accesorio = %s",
		valTpDato($idPresupuestoAccesorio,"int"));
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryDetalle);	
	while($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		$sigValor++;
		
		$idAccesorio = $rowDetalle['id_accesorio'];
		$codigo = $rowDetalle['nom_accesorio'];
		$descripcion = $rowDetalle['des_accesorio'];
		$precioUnitario = $rowDetalle['precio_unitario'];
		$ivaUnitario = $rowDetalle['iva_unitario'];
		$precioFinal = $rowDetalle['precio_unitario'] + $rowDetalle['iva_unitario'];
		
		// INSERTA EL ACCESORIO MEDIANTE INJECT
		$objResponse->script(sprintf("$('#trItmPie').before('".
			"<tr align=\"left\" id=\"trItm:%s\" class=\"textoGris_11px\" height=\"22\">".
				"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
					"<input type=\"checkbox\" id=\"cbx\" name=\"cbx[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"<td align=\"center\">%s</td>".
				"<td>%s</td>".
				"<td align=\"right\">%s</td>".
				"<td align=\"right\">%s</td>".
				"<td align=\"right\">%s".
					"<input type=\"hidden\" id=\"hddIdAccesorio%s\" name=\"hddIdAccesorio%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdDescripcion%s\" name=\"hddIdDescripcion%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddPrecioUnitario%s\" name=\"hddPrecioUnitario%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIvaUnitario%s\" name=\"hddIvaUnitario%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddPrecioFinal%s\" name=\"hddPrecioFinal%s\" value=\"%s\"/>".
				"</td>".
			"</tr>');",
			$sigValor,
				$sigValor, $sigValor,
					$sigValor,
				utf8_encode($codigo),
				preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$descripcion)))),
				number_format($precioUnitario, 2, ".", ","),
				number_format($ivaUnitario, 2, ".", ","),
				number_format($precioFinal, 2, ".", ","),
					$sigValor, $sigValor, $idAccesorio,
					$sigValor, $sigValor, utf8_encode($codigo),
					$sigValor, $sigValor, $precioUnitario,
					$sigValor, $sigValor, $ivaUnitario,
					$sigValor, $sigValor, $precioFinal));
	}
	
	if ($idPresupuestoAccesorio > 0) {
		$objResponse->script("byId('aListarPresupuesto').style.display = 'none'");
	}
	
	$objResponse->script("xajax_calcularCombo(xajax.getFormValues('frmListaAccesorio'));");
	
	return $objResponse;
}

function divVerCombo($idCombo) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
						
	// CONSULTA ENCABEZADO DEL COMBO
	$queryEncabezado = sprintf("SELECT * FROM an_combo
	WHERE id_combo = %s;",
		valTpDato($idCombo,"int"));
	$rsEncabezado = mysql_query($queryEncabezado);
	if (!$rsEncabezado) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryEncabezado);
	$rowEncabezado = mysql_fetch_assoc($rsEncabezado);
	
	$objResponse->loadCommands(listaComboDetalle(0, "nombre_combo", "ASC", $idCombo));
	
	$objResponse->assign("txtCombo","value",utf8_encode($rowEncabezado['nombre_combo']));
	$objResponse->assign("txtFechaCombo","value",date(spanDateFormat, strtotime($rowEncabezado['fecha_creacion'])));
	$objResponse->assign("txtObservacion","value",utf8_encode($rowEncabezado['observacion']));
	
	$objResponse->assign('txtSubTotalC',"value",number_format($rowEncabezado['total_sin_iva'], 2, ".", ","));	
	$objResponse->assign('txtTotalIvaC',"value",number_format($rowEncabezado['total_iva'], 2, ".", ","));	
	$objResponse->assign('txtTotalC',"value",number_format($rowEncabezado['total_con_iva'], 2, ".", ","));	
						
	$objResponse->script("xajax_calcularCombo(xajax.getFormValues('frmListaAccesorio'));");
	
	mysql_query("COMMIT;");
			
	return $objResponse;
}

function eliminarAccesorio($frmListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");

	if (isset($frmListaAccesorio['cbxItm'])) {
		foreach($frmListaAccesorio['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
		$objResponse->script("xajax_eliminarAccesorio(xajax.getFormValues('frmListaAccesorio'));");
	}
	
	$objResponse->script("xajax_calcularCombo(xajax.getFormValues('frmListaAccesorio'));");
		
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function guardarPresupuesto($valForm, $frmListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_combo_presupuesto_list","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// DESCONCATENA PARA SABER CUANTOS ÍTEMS HAY AGREGADOS
	$arrayObj = $frmListaAccesorio['cbx'];
	
	$idPresupuestoAccesorio = $valForm['hddIdPresupuestoAccesorio'];
	
	if ($idPresupuestoAccesorio > 0) {
		// INSERTA LOS DATOS DEL PRESUPUESTO
		$updateSQL = sprintf("UPDATE an_presupuesto_accesorio SET
			subtotal = %s,
			subtotal_iva = %s
		WHERE id_presupuesto_accesorio = %s;",
			valTpDato($frmListaAccesorio['txtSubTotal'], "real_inglesa"),
			valTpDato($frmListaAccesorio['txtTotalIva'], "real_inglesa"),
			valTpDato($idPresupuestoAccesorio, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$updateSQL);
		mysql_query("SET NAMES 'latin1';");
	} else {
		// INSERTA LOS DATOS DEL PRESUPUESTO
		$insertSQL = sprintf("INSERT INTO an_presupuesto_accesorio (id_presupuesto, fecha_creacion, subtotal, subtotal_iva)
									VALUE (%s, NOW(), %s, %s);",
										valTpDato($valForm['txtIdPresupuesto'], "text"),
										valTpDato($frmListaAccesorio['txtSubTotal'], "real_inglesa"),
										valTpDato($frmListaAccesorio['txtTotalIva'], "real_inglesa"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$insertSQL);
			$idPresupuestoAccesorio = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
	}
	
	// VERIFICAR SI EXISTEN AUN LOS ACCESORIOS QUE ESTABAN EN LA BD
	$queryAccesorio = sprintf("SELECT * FROM an_presupuesto_accesorio_detalle
								WHERE id_presupuesto_accesorio = %s",
									valTpDato($idPresupuestoAccesorio, "int"));
	$rsAccesorio = mysql_query($queryAccesorio);
	if (!$rsAccesorio) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryAccesorio);
	
	while ($rowAccesorio = mysql_fetch_assoc($rsAccesorio)) {	
		$existAccesorio = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($rowAccesorio['id_accesorio'] == $frmListaAccesorio['hddIdAccesorio'.$valor]) {
					$existAccesorio = true;
				}
			}
		}
		if ($existAccesorio == false) {
			$deleteSQL = sprintf("DELETE FROM an_presupuesto_accesorio_detalle
									WHERE id_presupuesto_accesorio_detalle = %s",
										valTpDato($rowAccesorio['id_presupuesto_accesorio_detalle'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$deleteSQL);
		}
	}
	
		// INSERTA LOS ACCESORIOS
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($valor != "") {
					if ($frmListaAccesorio['hddIdDescripcion'.$valor] == '') {
						$insertSQL = sprintf("INSERT INTO an_presupuesto_accesorio_detalle (id_presupuesto_accesorio, id_accesorio, precio_unitario, iva_unitario)
													VALUE (%s, %s, %s, %s);",
														valTpDato($idPresupuestoAccesorio, "int"),
														valTpDato($frmListaAccesorio['hddIdAccesorio'.$valor], "int"),
														valTpDato($frmListaAccesorio['hddPrecioUnitario'.$valor], "real_inglesa"),
														valTpDato($frmListaAccesorio['hddIvaUnitario'.$valor], "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$insertSQL);
						mysql_query("SET NAMES 'latin1';");
					}
				}
			}
		}
		
	$objResponse->alert("Presupuesto de Accesorios guardado con éxito.");
	
	$objResponse->script(sprintf("verVentana('reportes/an_combo_presupuesto_pdf.php?idPresupuesto=%s', 960, 550)",$idPresupuestoAccesorio));
	$objResponse->script(sprintf("window.location.href = 'an_presupuesto_venta_list.php';"));
	
	mysql_query("COMMIT;");
		
	return $objResponse;
}

function insertarAccesorio($valForm, $frmListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ÍTEMS HAY AGREGADOS
	$arrayObj = $frmListaAccesorio['cbx'];
	$sigValor = $arrayObj[count($arrayObj)-1];
	
	if (isset($arrayObj)){
		foreach ($arrayObj as $indice => $valor) {
			if ($frmListaAccesorio['hddIdAccesorio'.$valor] == $valForm['hddIdAccesorio']) {
				errorInsertarAccesorio($objResponse);
				return $objResponse->alert('El accesorio seleccionado ya se encuentra registrado en el combo.');
			}
		}
	}
	
	// CONSULTA EL CODIGO DEL ACCESORIO
	$queryCodigo = sprintf("SELECT * FROM an_accesorio
								WHERE id_tipo_accesorio = 2");		
	$rsCodigo = mysql_query($queryCodigo);
	if (!$rsCodigo) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryCodigo);
	$rowCodigo = mysql_fetch_assoc($rsCodigo);
	
	// CONSULTA EL IVA DEL ACCESORIOS
	$queryAccesorio = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1");		
	$rsAccesorio = mysql_query($queryAccesorio);
	if (!$rsAccesorio) { errorInsertarAccesorio($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryAccesorio);}
	$rowAccesorio = mysql_fetch_assoc($rsAccesorio);
	
	$sigValor++;
	
	$precioUnidad = str_replace(',','',$valForm['txtPrecio']);
	$ivaUnitario = (str_replace(',','',$rowAccesorio['iva']) * str_replace(',','',$valForm['txtPrecio'])) / 100;
	$accesorioConIva = ((str_replace(',','',$rowAccesorio['iva']) * str_replace(',','',$valForm['txtPrecio'])) / 100) + str_replace(",","",$valForm['txtPrecio']);
	
	// INSERTA EL ACCESORIO MEDIANTE INJECT
	$objResponse->script(sprintf("$('#trItmPie').before('".
		"<tr align=\"left\" id=\"trItm:%s\" class=\"textoGris_11px\" height=\"22\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input type=\"checkbox\" id=\"cbx\" name=\"cbx[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td align=\"center\">%s</td>".
			"<td>%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s".
				"<input type=\"hidden\" id=\"hddIdAccesorio%s\" name=\"hddIdAccesorio%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdDescripcion%s\" name=\"hddIdDescripcion%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddPrecioUnitario%s\" name=\"hddPrecioUnitario%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIvaUnitario%s\" name=\"hddIvaUnitario%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddPrecioFinal%s\" name=\"hddPrecioFinal%s\" value=\"%s\"/>".
			"</td>".
		"</tr>');",
		$sigValor,
			$sigValor, $sigValor,
				$sigValor,
			utf8_encode($valForm['txtCodigo']),
			preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$valForm['txtAccesorio'])))),
			number_format($precioUnidad, 2, ".", ","),
			number_format($ivaUnitario, 2, ".", ","),
			number_format($accesorioConIva, 2, ".", ","),
				$sigValor, $sigValor, $valForm['hddIdAccesorio'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $precioUnidad,
				$sigValor, $sigValor, $ivaUnitario,
				$sigValor, $sigValor, $accesorioConIva));
				
	$objResponse->script("document.forms['frmAccesorio'].reset();");
	
	$objResponse->loadCommands(errorInsertarAccesorio($objResponse));
	
	$objResponse->script("xajax_calcularCombo(xajax.getFormValues('frmListaAccesorio'));");
	
	return $objResponse;
}

function insertarCombo($idCombo, $frmListaAccesorio) {
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ÍTEMS HAY AGREGADOS
	$arrayObj = $frmListaAccesorio['cbx'];
	$sigValor = $arrayObj[count($arrayObj)-1];
	
	/*if (isset($arrayObj)){
		foreach ($arrayObj as $indice => $valor) {
			if ($frmListaAccesorio['hddIdAccesorio'.$valor] == $valForm['hddIdAccesorio']) {
				errorInsertarAccesorio($objResponse);
				return $objResponse->alert('El accesorio seleccionado ya se encuentra registrado en el combo.');
			}
		}
	}*/
	
	// CONSULTA EL DETALLE DEL COMBO
	$queryDetalle = sprintf("SELECT *
								FROM
									an_combo_detalle
								INNER JOIN an_accesorio ON (an_combo_detalle.id_accesorio = an_accesorio.id_accesorio)
								WHERE
									id_combo = %s",
										valTpDato($idCombo,"int"));	
	$rsDetalle = mysql_query($queryDetalle);
	if (!$rsDetalle) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryDetalle);
	
	// CONSULTA EL IVA DEL ACCESORIO
	$queryAccesorio = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1");
	$rsAccesorio = mysql_query($queryAccesorio);
	if (!$rsAccesorio) { errorInsertarAccesorio($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryAccesorio);}
	$rowAccesorio = mysql_fetch_assoc($rsAccesorio);

	while($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
		$sigValor++;
		
		$precioUnidad = str_replace(',','',$rowDetalle['precio_accesorio']);
		$ivaUnitario = (str_replace(',','',$rowAccesorio['iva']) * str_replace(',','',$rowDetalle['precio_accesorio'])) / 100;
		$accesorioConIva = ((str_replace(',','',$rowAccesorio['iva']) * str_replace(',','',$rowDetalle['precio_accesorio'])) / 100) + str_replace(",","",$rowDetalle['precio_accesorio']);
		
		// INSERTA EL ACCESORIO MEDIANTE INJECT
		$objResponse->script(sprintf("$('#trItmPie').before('".
			"<tr align=\"left\" id=\"trItm:%s\" class=\"textoGris_11px\" height=\"22\">".
				"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
					"<input type=\"checkbox\" id=\"cbx\" name=\"cbx[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
				"<td align=\"center\">%s</td>".
				"<td>%s</td>".
				"<td align=\"right\">%s</td>".
				"<td align=\"right\">%s</td>".
				"<td align=\"right\">%s".
					"<input type=\"hidden\" id=\"hddIdAccesorio%s\" name=\"hddIdAccesorio%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIdDescripcion%s\" name=\"hddIdDescripcion%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddPrecioUnitario%s\" name=\"hddPrecioUnitario%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddIvaUnitario%s\" name=\"hddIvaUnitario%s\" value=\"%s\"/>".
					"<input type=\"hidden\" id=\"hddPrecioFinal%s\" name=\"hddPrecioFinal%s\" value=\"%s\"/>".
				"</td>".
			"</tr>');",
			$sigValor,
				$sigValor, $sigValor,
					$sigValor,
				utf8_encode($rowDetalle['nom_accesorio']),
				preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$rowDetalle['des_accesorio'])))),
				number_format($precioUnidad, 2, ".", ","),
				number_format($ivaUnitario, 2, ".", ","),
				number_format($accesorioConIva, 2, ".", ","),
					$sigValor, $sigValor, $rowDetalle['id_accesorio'],
					$sigValor, $sigValor, "",
					$sigValor, $sigValor, $precioUnidad,
					$sigValor, $sigValor, $ivaUnitario,
					$sigValor, $sigValor, $accesorioConIva));
	}
	
	$objResponse->script("document.forms['frmAccesorio'].reset();");
	
	$objResponse->loadCommands(errorInsertarAccesorio($objResponse));
	
	$objResponse->script("xajax_calcularCombo(xajax.getFormValues('frmListaAccesorio'));");
	
	return $objResponse;
}

function listaAccesorio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 12, $totalRows = NULL) {

	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_accesorio = 2");
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_accesorio LIKE %s
		OR des_accesorio LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%","text"),
			valTpDato("%".$valCadBusq[0]."%","text"));
	}
			
	// LISTADO DE ACCESORIOS
	$query = sprintf("SELECT * FROM an_accesorio %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
				
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila ++;
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		$htmlTb .= "<td valign=\"top\" width=\"33%\">";
			$htmlTb .= "<table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
				$htmlTb .= "<tr align=\"left\">";
					$htmlTb .= "<td rowspan=\"4\">"."<button type=\"button\" onclick=\"xajax_asignarAccesorio('".$row['id_accesorio']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
					$htmlTb .= sprintf("<td width=\"%s\">%s</td>", "80%", utf8_encode($row['nom_accesorio']));
					$htmlTb .= sprintf("<td align=\"right\" rowspan=\"4\" width=\"%s\">%s</td>", "20%", number_format($row['precio_accesorio'], 2, ".", ","."Bs"));
				$htmlTb .= "</tr>";
				$htmlTb .= "<tr align=\"left\">";
					$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['des_accesorio']));
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaAccesorio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function listaCombo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_combo LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_combo %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCombo", "40%", $pageNum, "nombre_combo", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaCombo", "20%", $pageNum, "total_sin_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio");
		$htmlTh .= ordenarCampo("xajax_listaCombo", "20%", $pageNum, "total_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaCombo", "20%", $pageNum, "total_con_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Final");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		if ($row['iva_accesorio'] == 1) {
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1",
				valTpDato($idAccesorio, "int"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryIva);		
			$rowIva = mysql_fetch_assoc($rsIva);
		}
		
		$iva = ($rowIva['iva'] * $row['precio_accesorio']) / 100;
		$accesorioIva = ($rowIva['iva'] * $row['precio_accesorio']) / 100 + $row['precio_accesorio'];
		$accesorio = $row['precio_accesorio'];
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><button type=\"button\" onclick=\"validarInsertar('%s')\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button></td>",$row['id_combo']);
			$htmlTb .= "<td>".htmlentities($row['nombre_combo'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_sin_iva'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_iva'], 2, ".", ",")."</td>";			
			$htmlTb .= "<td align=\"right\">".number_format($row['total_con_iva'], 2, ".", ",")."</td>";			
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aVer%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblCombo', %s);\"><img class=\"puntero\" src=\"../img/iconos/view.png\" title=\"Ver Combo\"/></a>",
					$contFila,
					$row['id_combo']);
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCombo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCombo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCombo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCombo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCombo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("divListaCombo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaComboDetalle($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("an_combo.id_combo = %s",
		valTpDato($valCadBusq[0], "int"));

	$query = sprintf("SELECT *
	FROM an_combo
		INNER JOIN an_combo_detalle ON (an_combo.id_combo = an_combo_detalle.id_combo)
		INNER JOIN an_accesorio ON (an_combo_detalle.id_accesorio = an_accesorio.id_accesorio) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaComboDetalle", "20%", $pageNum, "nom_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listaComboDetalle", "50%", $pageNum, "des_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listaComboDetalle", "10%", $pageNum, "total_sin_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio");
		$htmlTh .= ordenarCampo("xajax_listaComboDetalle", "10%", $pageNum, "total_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaComboDetalle", "10%", $pageNum, "total_con_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Final");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		if ($row['iva_accesorio'] == 1) {
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1",
				valTpDato($idAccesorio, "int"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryIva);		
			$rowIva = mysql_fetch_assoc($rsIva);
		}
		
		$accesorio = $row['precio_accesorio'];
		$iva = ($rowIva['iva'] * $row['precio_accesorio']) / 100;
		$accesorioIva = ($rowIva['iva'] * $row['precio_accesorio']) / 100 + $row['precio_accesorio'];
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".htmlentities($row['nom_accesorio'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['des_accesorio'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($accesorio, 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($iva, 2, ".", ",")."</td>";			
			$htmlTb .= "<td align=\"right\">".number_format($accesorioIva, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComboDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComboDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaComboDetalle(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComboDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaComboDetalle(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaComboDetalle","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaPresupuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanInicial;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("pres_vent.estado IN (0,3)");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("pres_vent.id_presupuesto NOT IN (SELECT pres_vent_acc.id_presupuesto FROM an_presupuesto_accesorio pres_vent_acc)");
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pres_vent.id_presupuesto LIKE %s
		OR pres_vent.numeracion_presupuesto LIKE %s
		OR ped.id_cliente LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR CONCAT(uni_bas.nom_uni_bas,': ', modelo.nom_modelo,' - ', vers.nom_version) LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		pres_vent.fecha,
		pres_vent_acc.id_presupuesto_accesorio,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT('[', uni_bas.nom_uni_bas, ']: ', marca.nom_marca, ' ', modelo.nom_modelo, ' ', vers.nom_version, ' ', ano.nom_ano) AS vehiculo,
		
		(pres_vent.precio_venta + (SELECT SUM(pres_vent.precio_venta * iva.iva / 100)
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = pres_vent.id_uni_bas
				AND iva.tipo IN (6,9,2))) AS precio_venta,
		
		pres_vent.porcentaje_inicial,
		pres_vent.monto_inicial,
		pres_vent.total_general,
		
		(SELECT COUNT(*) FROM an_unidad_fisica
		WHERE id_uni_bas = pres_vent.id_uni_bas
			AND propiedad = 'PROPIO'
			AND estado_venta = 'DISPONIBLE') AS ud,
		
		pres_vent.estado AS estado_presupuesto,
		ped.estado_pedido
	FROM an_presupuesto pres_vent
		INNER JOIN cj_cc_cliente cliente ON (pres_vent.id_cliente = cliente.id)
		INNER JOIN an_uni_bas uni_bas ON (pres_vent.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
		LEFT JOIN an_pedido ped ON (pres_vent.id_presupuesto = ped.id_presupuesto)
		LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto) %s", $sqlBusq);
	
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
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "7%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "8%", $pageNum, "numeracion_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "28%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "30%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "9%", $pageNum, "precio_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Venta");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "9%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, $spanInicial);
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "9%", $pageNum, "total_general", $campOrd, $tpOrd, $valBusq, $maxRows, "Total General");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusPedido = "";
		if ($row['estado_presupuesto'] == 0) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Presupuesto Autorizado\"/>";
		} else if ($row['estado_presupuesto'] == 1) {
			switch ($row['estado_pedido']) {
				case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Autorizado\"/>"; break;
				case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			}
		} else if ($row['estado_presupuesto'] == 2) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Presupuesto Anulado\"/>";
		} else if ($row['estado_presupuesto'] == 3) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Presupuesto Desautorizado\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarPresupuesto('".$row['id_presupuesto']."','".$valCadBusq[0]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_presupuesto']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= utf8_encode($row['vehiculo']);
				$htmlTb .= ($row['ud'] > 0) ? "<br><span class=\"textoNegrita_10px\">Disponible: ".number_format($row['ud'], 2, ".", ",")."</span>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_venta'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= number_format($row['monto_inicial'], 2, ".", ",");
				$htmlTb .= "<br><span class=\"textoNegrita_10px\">".number_format($row['porcentaje_inicial'], 2, ".", ",")."%</span>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_general'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPresupuesto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarAccesorio");
$xajax->register(XAJAX_FUNCTION,"asignarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"buscarAccesorio");
$xajax->register(XAJAX_FUNCTION,"buscarCombo");
$xajax->register(XAJAX_FUNCTION,"buscarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"calcularCombo");
$xajax->register(XAJAX_FUNCTION,"cargarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"divVerCombo");
$xajax->register(XAJAX_FUNCTION,"eliminarAccesorio");
$xajax->register(XAJAX_FUNCTION,"guardarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"insertarAccesorio");
$xajax->register(XAJAX_FUNCTION,"insertarCombo");
$xajax->register(XAJAX_FUNCTION,"listaAccesorio");
$xajax->register(XAJAX_FUNCTION,"listaCombo");
$xajax->register(XAJAX_FUNCTION,"listaCombo");
$xajax->register(XAJAX_FUNCTION,"listaComboDetalle");
$xajax->register(XAJAX_FUNCTION,"listaPresupuesto");

function errorInsertarAccesorio($objResponse) {
	$objResponse->script("
	byId('btnAgregar').disabled = false;
	byId('btnCerrar').disabled = false;");
}
?>