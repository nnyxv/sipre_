<?php


function asignarAccesorio($idAccesorio){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	// CONSULTA DESCRIPCION
	$query = sprintf("SELECT
							*
						FROM
							an_accesorio
						WHERE
							id_accesorio = %s",
								valTpDato($idAccesorio,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$query);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("hddIdAccesorio","value",$idAccesorio);
	$objResponse->assign("txtCodigo","value",utf8_encode($row['nom_accesorio']));
	$objResponse->assign("txtAccesorio","value",utf8_encode($row['des_accesorio']));
	$objResponse->assign('txtPrecio',"value",number_format($row['precio_accesorio'], 2, ".", ","));
	
	mysql_query("COMMIT;");
							
	return $objResponse;
}

function buscarAccesorio($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioAccesorio']);
		
	$objResponse->loadCommands(listadoAccesorio(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarCombo($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterio']);
		
	$objResponse->loadCommands(listadoCombo(0, "nombre_combo", "ASC", $valBusq));
	
	return $objResponse;
}

function divAccesorio($frmCombo){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("document.forms['frmBuscarAccesorio'].reset();
						document.forms['frmAccesorio'].reset();");
						
	$objResponse->loadCommands(listadoAccesorio());
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Listado de Accesorios");
	
	$objResponse->script("if (byId('divFlotante2').style.display == 'none') {
							byId('divFlotante2').style.display = '';
							centrarDiv(byId('divFlotante2'));}");
							
	$objResponse->script("byId('txtCriterioAccesorio').focus();");
	
	return $objResponse;
}

function calcularCombo($valFormListadoAccesorio) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ÍTEMS HAY AGREGADOS
	$arrayObj = $valFormListadoAccesorio['cbx'];
	
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valor,"className",$clase." textoGris_11px");
			
			$subTotal += str_replace(",","",$valFormListadoAccesorio['hddPrecioUnitario'.$valor]);
			$totalIva += str_replace(",","",$valFormListadoAccesorio['hddPrecioCantidad'.$valor]);
			$total += str_replace(",","",$valFormListadoAccesorio['hddPrecioCantidadIva'.$valor]);
		}
	}
	
	$objResponse->assign("txtSubTotal","value",number_format($subTotal, 2, ".", ","));
	$objResponse->assign("txtTotalIva","value",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("txtTotal","value",number_format($total, 2, ".", ","));
	
	$objResponse->assign("hddObj","value",implode("|",$arrayObj));
	
	return $objResponse;
}

function divEditarCombo($nomObjeto, $idCombo, $valFormListadoAccesorio){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if (xvalidaAcceso($objResponse,"an_combo_list","editar")) {

		$objResponse->script("
		openImg(byId('".$nomObjeto."'));");
		
		// DESCONCATENA PARA SABER CUANTOS ÍTEMS HAY AGREGADOS
		$arrayObj = $valFormListadoAccesorio['cbx'];
		
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
		
		$objResponse->script("document.forms['frmCombo'].reset();
							byId('txtCombo').innerHTML = '';
							byId('divFlotante').style.display = '';
							byId('tdFlotanteTitulo').innerHTML = 'Ver Combo';
							byId('txtCombo').readOnly = false;
							byId('txtObservacion').readOnly = '';
							document.forms['frmListadoAccesorio'].reset();
							byId('hddObj').value = '';");
							
		$objResponse->assign("hddIdCombo","value",$idCombo);
		
		// CONSULTA ENCABEZADO DEL COMBO
		$queryEncabezado = sprintf("SELECT
										*
									FROM
										an_combo
									WHERE
										id_combo = %s",
											valTpDato($idCombo,"int")); 
		$rsEncabezado = mysql_query($queryEncabezado);
		if (!$rsEncabezado) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryEncabezado);
		$rowEncabezado = mysql_fetch_assoc($rsEncabezado);
		
		$objResponse->assign("txtCombo","value",$rowEncabezado['nombre_combo']);
		$objResponse->assign("txtFechaCombo","value",date(spanDateFormat, strtotime($rowEncabezado['fecha_creacion'])));
		$objResponse->assign("txtObservacion","value",utf8_encode($rowEncabezado['observacion']));
	
		// CONSULTA DETALLE DEL COMBO			
		$query = sprintf("SELECT
								*
							FROM
								an_combo_detalle
								INNER JOIN an_accesorio ON (an_combo_detalle.id_accesorio = an_accesorio.id_accesorio)							
							WHERE
								id_combo = %s",
									valTpDato($idCombo,"int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$query);
		
		
		$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryIva);		
		$rowIva = mysql_fetch_assoc($rsIva);
			
		while($row = mysql_fetch_assoc($rs)) {
			$sigValor++;
			
			$iva = $rowIva['iva'];
			
			$idAccesorio = $row['id_accesorio'];
			$precioUnitario = $row['precio_accesorio'];
			$precioCantidad = ($iva * $row['precio_accesorio']) / 100;
			$precioCantidadIva = ($rowIva['iva'] * $row['precio_accesorio']) / 100 + $row['precio_accesorio'];
			$nombccesorio = $row['nom_accesorio'];
			$desAccesorio = $row['des_accesorio'];
			
			$queryIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1");
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryIva);		
			$rowIva = mysql_fetch_assoc($rsIva);
			
			// INSERTA EL ACCESORIO MEDIANTE INJECT
			$objResponse->script(sprintf("$('#trItmPie').before('".
				"<tr id=\"trItm:%s\" class=\"textoGris_11px\" height=\"22\">".
					"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
						"<input type=\"checkbox\" id=\"cbx\" name=\"cbx[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
					"<td align=\"center\">%s</td>".
					"<td align=\"left\">%s</td>".
					"<td align=\"right\">%s</td>".
					"<td align=\"right\">%s</td>".
					"<td align=\"right\">%s".
						"<input type=\"hidden\" id=\"hddIdAccesorio%s\" name=\"hddIdAccesorio%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddIdDetalleCombo%s\" name=\"hddIdDetalleCombo%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddPrecioUnitario%s\" name=\"hddPrecioUnitario%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddPrecioCantidad%s\" name=\"hddPrecioCantidad%s\" value=\"%s\"/>".
						"<input type=\"hidden\" id=\"hddPrecioCantidadIva%s\" name=\"hddPrecioCantidadIva%s\" value=\"%s\"/>".
					"</td>".
				"</tr>');",
				$sigValor,
					$sigValor, $sigValor,
						$sigValor,
					$nombccesorio,
					preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$desAccesorio)))),
					number_format($precioUnitario, 2, ".", ","),
					number_format($precioCantidad, 2, ".", ","),
					number_format($precioCantidadIva, 2, ".", ","),
						$sigValor, $sigValor, $idAccesorio,
						$sigValor, $sigValor, $nombccesorio,
						$sigValor, $sigValor, $precioUnitario,
						$sigValor, $sigValor, $precioCantidad,
						$sigValor, $sigValor, $precioCantidadIva));		
		}
		
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Datos del Combo");
		
		$objResponse->script("if (byId('divFlotante').style.display == 'none') {
								byId('divFlotante').style.display = '';
								byId('lstEstatus').disabled = true;
								centrarDiv(byId('divFlotante'));
							}");
									
		$objResponse->script("xajax_calcularCombo(xajax.getFormValues('frmListadoAccesorio'));");
	}
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function eliminarCombo($idAccesorio, $valFormListaAccesorio) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_combo_list","eliminar")) {
		mysql_query("START TRANSACTION;");
		
		$deleteSQL = sprintf("DELETE FROM an_combo WHERE id_combo = %s",
			valTpDato($idAccesorio, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("COMMIT;");
		
		$objResponse->loadCommands(listadoCombo(
			$valFormListaAccesorio['pageNum'],
			$valFormListaAccesorio['campOrd'],
			$valFormListaAccesorio['tpOrd'],
			$valFormListaAccesorio['valBusq']));
			
		$objResponse->alert("Combo eliminado exitosamente.");
	
	}
	
	return $objResponse;
}

function eliminarAccesorio($valFormListadoAccesorio){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");

	if (isset($valFormListadoAccesorio['cbxItm'])) {
		foreach($valFormListadoAccesorio['cbxItm'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trItm:%s');
				
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
		$objResponse->script("xajax_eliminarAccesorio(xajax.getFormValues('frmListadoAccesorio'));");
	}
	
	$objResponse->script("xajax_calcularCombo(xajax.getFormValues('frmListadoAccesorio'));");
		
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function divNuevoCombo($nomObjeto, $valFormListadoAccesorio) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"an_combo_list","insertar")) {	
	
	$objResponse->script("
	openImg(byId('".$nomObjeto."'));");
	
	// DESCONCATENA PARA SABER CUANTOS ÍTEMS HAY AGREGADOS
	$arrayObj = $valFormListadoAccesorio['cbx'];
		
		if (isset($arrayObj)) {
			foreach($arrayObj as $indiceItm => $valorItm) {
				$objResponse->script(sprintf("
				fila = document.getElementById('trItm:%s');
				padre = fila.parentNode;
				padre.removeChild(fila);",
					$valorItm));
			}
		}

		$objResponse->script("byId('hddIdCombo').value = '';");		
		
		$objResponse->script("document.forms['frmCombo'].reset();
							document.forms['frmListadoAccesorio'].reset();
							document.forms['frmObservacion'].reset();");
							
		$objResponse->script("byId('txtCombo').readOnly = true;
							byId('txtObservacion').readOnly = '';
							byId'hddObj').value = '';");
		
		$objResponse->assign("txtFechaCombo","value",date(spanDateFormat));			
		$objResponse->script("byId('txtCombo').className = 'inputHabilitado';");
	
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Accesorio para Combo");
		$objResponse->script("if (byId('divFlotante').style.display == 'none') {
								byId('divFlotante').style.display = '';
								centrarDiv(byId('divFlotante'));}");
	}
	
	return $objResponse;
}

function guardarCombo($valForm, $valFormListadoAccesorio, $frmObservacion){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	// DESCONCATENA PARA SABER CUANTOS ÍTEMS HAY AGREGADOS
	$arrayObj = $valFormListadoAccesorio['cbx'];
	
	if ($valForm['hddIdCombo'] == '') {
		// INSERTA LOS DATOS DEL COMBO
		$insertSQL = sprintf("INSERT INTO an_combo (nombre_combo, fecha_creacion, total_sin_iva, total_iva, total_con_iva, observacion)
									VALUE (%s, NOW(), %s, %s, %s, %s);",
										valTpDato($valForm['txtCombo'], "text"),
										valTpDato($valFormListadoAccesorio['txtSubTotal'], "real_inglesa"),
										valTpDato($valFormListadoAccesorio['txtTotalIva'], "real_inglesa"),
										valTpDato($valFormListadoAccesorio['txtTotal'], "real_inglesa"),
										valTpDato($frmObservacion['txtObservacion'],"text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$insertSQL);
			$idCombo = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
	}

	$totalCombo = str_replace(",","",$valFormListadoAccesorio['txtTotal']);

	if ($totalCombo > 0){
			// ACTUALIZA NOMBRE, TOTAL DEL COMBO Y OBSERVACION
			$queryTotal = sprintf("UPDATE an_combo SET
											nombre_combo = %s,
											total_sin_iva = %s,
											total_iva = %s,
											total_con_iva = %s,
											observacion = %s
										WHERE
											id_combo = %s",
											valTpDato($valForm['txtCombo'],"text"),
											valTpDato($valFormListadoAccesorio['txtSubTotal'],"real_inglesa"),
											valTpDato($valFormListadoAccesorio['txtTotalIva'],"real_inglesa"),
											valTpDato($valFormListadoAccesorio['txtTotal'],"real_inglesa"),
											valTpDato($frmObservacion['txtObservacion'],"text"),
											valTpDato($valForm['hddIdCombo'],"int"));
			mysql_query("SET NAMES 'utf8';");
			$rsTotal = mysql_query($queryTotal);
			if (!$rsTotal) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryTotal);
			mysql_query("SET NAMES 'latin1';");
		}	


	// VERIFICAR SI EXISTEN AUN LOS ACCESORIOS QUE ESTABAN EN LA BD
	$queryAccesorio = sprintf("SELECT * FROM an_combo_detalle
								WHERE id_combo = %s",
									valTpDato($valForm['hddIdCombo'], "int"));
	$rsAccesorio = mysql_query($queryAccesorio);
	if (!$rsAccesorio) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryAccesorio);
	
	while ($rowAccesorio = mysql_fetch_assoc($rsAccesorio)) {
		$existAccesorio = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($rowAccesorio['id_accesorio'] == $valFormListadoAccesorio['hddIdAccesorio'.$valor]) {
					$existAccesorio = true;
				}
			}
		}

		if ($existAccesorio == false) {
			$deleteSQL = sprintf("DELETE FROM an_combo_detalle
									WHERE id_combo_detalle = %s",
										valTpDato($rowAccesorio['id_combo_detalle'], "int"));
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$deleteSQL);
		}
	}
		
	if ($valForm['hddIdCombo'] == '')
		$combo = $idCombo;
	 else
		$combo = $valForm['hddIdCombo'];


		// INSERTA LOS ACCESORIOS
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if ($valor != "") {
					if ($valFormListadoAccesorio['hddIdDetalleCombo'.$valor] == '') {
						$insertSQL = sprintf("INSERT INTO an_combo_detalle (id_combo, id_accesorio)
													VALUE (%s, %s);",
														valTpDato($combo, "int"),
														valTpDato($valFormListadoAccesorio['hddIdAccesorio'.$valor], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$insertSQL);
						mysql_query("SET NAMES 'latin1';");
					}
				}
			}
		}
		
	$objResponse->script(sprintf("byId('divFlotante').style.display = 'none';
								byId('divFlotante2').style.display = 'none';"));
								
	$objResponse->alert("Combo: ".$valForm['txtCombo']." guardado con éxito.");
	
	$objResponse->script("byId('btnCancelar').click();");
	
	$objResponse->loadCommands(listadoCombo(0, "", "", ""));	
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function habilitaBoton($objResponse){
		
		$objResponse->script("byId('btnAgregar').disabled = false;");
		$objResponse->script("byId('btnCerrar').disabled = false;");
}

function insertarAccesorio($valForm, $valFormListadoAccesorio){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ÍTEMS HAY AGREGADOS
	$arrayObj = $valFormListadoAccesorio['cbx'];
	$sigValor = $arrayObj[count($arrayObj)-1];
	
	if (isset($arrayObj)){
		foreach ($arrayObj as $indice => $valor) {
			if ($valFormListadoAccesorio['hddIdAccesorio'.$valor] == $valForm['hddIdAccesorio']) {
				habilitaBoton($objResponse);
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
	
	// CONSULTA EL IVA DEL ACCESORIO
	$queryAccesorio = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.tipo IN (6) AND iva.estado = 1 AND iva.activo = 1");		
	$rsAccesorio = mysql_query($queryAccesorio);
	if (!$rsAccesorio) { habilitaBoton($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__."\n\nSQL: ".$queryAccesorio); }
	$rowAccesorio = mysql_fetch_assoc($rsAccesorio);
	
	$sigValor++;
	
	$precioUnidad = str_replace(',','',$valForm['txtPrecio']);
	$precioCantidad = (str_replace(',','',$rowAccesorio['iva']) * str_replace(',','',$valForm['txtPrecio'])) / 100;
	$accesorioConIva = ((str_replace(',','',$rowAccesorio['iva']) * str_replace(',','',$valForm['txtPrecio'])) / 100) + str_replace(",","",$valForm['txtPrecio']);
	
	// INSERTA EL ACCESORIO MEDIANTE INJECT
	$objResponse->script(sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" class=\"textoGris_11px\" height=\"22\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input type=\"checkbox\" id=\"cbx\" name=\"cbx[]\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s".
				"<input type=\"hidden\" id=\"hddIdAccesorio%s\" name=\"hddIdAccesorio%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdDetalleCombo%s\" name=\"hddIdDetalleCombo%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddPrecioUnitario%s\" name=\"hddPrecioUnitario%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddPrecioCantidad%s\" name=\"hddPrecioCantidad%s\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddPrecioCantidadIva%s\" name=\"hddPrecioCantidadIva%s\" value=\"%s\"/>".
			"</td>".
		"</tr>');",
		$sigValor,
			$sigValor, $sigValor,
				$sigValor,
			$valForm['txtCodigo'],
			preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("\"","",$valForm['txtAccesorio'])))),
			number_format($precioUnidad, 2, ".", ","),
			number_format($precioCantidad, 2, ".", ","),
			number_format($accesorioConIva, 2, ".", ","),
				$sigValor, $sigValor, $valForm['hddIdAccesorio'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $precioUnidad,
				$sigValor, $sigValor, $precioCantidad,
				$sigValor, $sigValor, $accesorioConIva));
	
	//$objResponse->script("byId('divFlotante2').style.display = 'none';");

	$objResponse->script("document.forms['frmAccesorio'].reset();");
	
	$objResponse->loadCommands(habilitaBoton($objResponse));
	
	$objResponse->script("xajax_calcularCombo(xajax.getFormValues('frmListadoAccesorio'));");
	
	return $objResponse;
}

function listadoCombo($pageNum = 0, $campOrd = "nombre_combo", $tpOrd = "ASC", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("nombre_combo LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM an_combo 
						%s", $sqlBusq);
	
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
	$htmlTableIni .= "<table border=\"0\" style=\"border:1px solid #CCCCCC\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoCombo", "20%", $pageNum, "fecha_creacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listadoCombo", "50%", $pageNum, "nombre_combo", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
		$htmlTh .= ordenarCampo("xajax_listadoCombo", "10%", $pageNum, "total_sin_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Sin I.V.A");
		$htmlTh .= ordenarCampo("xajax_listadoCombo", "10%", $pageNum, "total_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "I.V.A 12%");
		$htmlTh .= ordenarCampo("xajax_listadoCombo", "10%", $pageNum, "total_con_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Final");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
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
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_creacion']))."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre_combo'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_sin_iva'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_iva'], 2, ".", ",")."</td>";			
			$htmlTb .= "<td align=\"right\">".number_format($row['total_con_iva'], 2, ".", ",")."</td>";			
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante\" onclick=\"xajax_divEditarCombo(this.id,".$row['id_combo'].",xajax.getFormValues('frmListadoAccesorio'));\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\"/></a>",
					$contFila,
					$row['id_accesorio']);
			$htmlTb .= "</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"validarEliminar('%s')\" src=\"../img/iconos/ico_delete.png\"/></td>",
				$row['id_combo']); // Eliminar Combo
			$htmlTb .= "<td><img class=\"puntero\" onclick=\"verVentana('reportes/an_combo_pdf.php?view=print&idCombo=".$row['id_combo']."', 960, 550);\" src=\"../img/iconos/ico_print.png\"/></td>"; // Imprimir Combo
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCombo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCombo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoCombo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCombo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCombo(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTableFin = "</table>";
	
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
	
	$objResponse->assign("tdListaAccesorio","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
		
	return $objResponse;
}

function listadoAccesorio($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 12, $totalRows = NULL){

	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_tipo_accesorio = 2");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_modulo IN (2)");
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nom_accesorio LIKE %s
									OR des_accesorio LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%","text"),
			valTpDato("%".$valCadBusq[0]."%","text"));
	}
			
	// LISTADO DE ACCESORIOS
	$query = sprintf("SELECT *
						FROM
							an_accesorio
	 							%s", $sqlBusq);
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
	$contFila = 0;
				
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila ++;
			
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr>" : "";
			$class = "class=\"divGris\"";
			$htmlTb .= "<td width=\"33%\" valign=\"top\">";
				$htmlTb .= sprintf("<table border=\"0\" %s width=\"%s\">",$class,"100%");
					$htmlTb .= "<tr>";
						$htmlTb .= "<td width=\"18%\" rowspan=\"4\">"."<button type=\"button\" onclick=\"xajax_asignarAccesorio('".$row['id_accesorio']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr>";
						$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['nom_accesorio']));
						$htmlTb .= "<td width=\"5%\" rowspan=\"5\" align=\"left\">".utf8_encode($estatus)."</td>";						
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr>";
						$htmlTb .= sprintf("<td>%s</td>", utf8_encode($row['des_accesorio']));
					$htmlTb .= "</tr>";
					$htmlTb .= "<tr>";
						$htmlTb .= sprintf("<td>%s</td>", number_format($row['precio_accesorio'], 2, ".", ","."Bs"));
					$htmlTb .= "</tr>";	
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\">";
		$htmlTf .= "<td align=\"center\" colspan=\"3\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",$contFila,$totalRows);
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				
				$htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
					$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$htmlTf .= "<tr align=\"center\">";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoAccesorio(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
						$htmlTf .= "</td>";
						
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorio(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListadoAccesorio","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}

//
$xajax->register(XAJAX_FUNCTION,"asignarAccesorio");
$xajax->register(XAJAX_FUNCTION,"buscarCombo");
$xajax->register(XAJAX_FUNCTION,"buscarAccesorio");
$xajax->register(XAJAX_FUNCTION,"cargarAccesorio");
$xajax->register(XAJAX_FUNCTION,"calcularCombo");
$xajax->register(XAJAX_FUNCTION,"divAccesorio");
$xajax->register(XAJAX_FUNCTION,"divEditarCombo");
$xajax->register(XAJAX_FUNCTION,"eliminarCombo");
$xajax->register(XAJAX_FUNCTION,"eliminarAccesorio");
$xajax->register(XAJAX_FUNCTION,"divNuevoCombo");
$xajax->register(XAJAX_FUNCTION,"guardarCombo");
$xajax->register(XAJAX_FUNCTION,"habilitaBoton");
$xajax->register(XAJAX_FUNCTION,"insertarAccesorio");
$xajax->register(XAJAX_FUNCTION,"listadoCombo");
$xajax->register(XAJAX_FUNCTION,"listadoAccesorio");
?>