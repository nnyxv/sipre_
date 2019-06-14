<?php 

function buscarMotivo($frmBuscarMotivo,$tipoMotivo) {
	$objResponse = new xajaxResponse();

	$tipoMotivo == 'NDC' ? $tipoMotivo = 'I' : $tipoMotivo = 'E' ;
	
	$valBusq = sprintf("%s|%s|%s",
			$tipoMotivo,
			$frmBuscarMotivo['hddObjDestinoMotivo'],
			$frmBuscarMotivo['txtCriterioBuscarMotivo']);

	$objResponse->loadCommands(listaMotivo(0, "id_motivo", "ASC", $valBusq));

	return $objResponse;
}

function buscarPedido($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s",
			$frmBuscar['lstEmpresa'],
			$frmBuscar['txtFechaDesde'],
			$frmBuscar['txtFechaHasta'],
			$frmBuscar['lstEstatusPedido'],
			$frmBuscar['txtCriterio']);

	$objResponse->loadCommands(listaPedido(0, "id_pedido_financiamiento", "DESC", $valBusq));

	return $objResponse;
}

function cargarCampos($idPedido,$tipoCarga = '',$tipoDoc,$hddMostrarNCC,$flag = '') {
		
	$objResponse = new xajaxResponse();
	
	//HABILITANDO LA PESTAÑA POR DEFECTO DE LOS MOTIVOS ASOCIADOS AL PEDIDO
	
	$objResponse->script("byId('liTabNDC').click();");
	
	if(isset($idPedido['hddIdPedido']) && $flag != '' ){
		$idPedido = $idPedido['hddIdPedido'];
	}

	
	if($tipoCarga == 'Motivos'){
		
		$queryPedido = sprintf("SELECT
						pedido.monto_financiamiento_documentos AS total_inicial,
						pedido.total_monto_financiar AS total_cuotas,
						(SELECT
							SUM(saldo_documento)
						FROM
							`fi_documento`
						WHERE
							id_documento_tabla <> 0
						AND id_pedido_financiamiento = %s) AS saldo_nota_credito
					FROM fi_pedido pedido
					WHERE pedido.id_pedido_financiamiento = %s",$idPedido,$idPedido);
		

		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) { return $objResponse->alert($query.mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowPedido = mysql_fetch_assoc($rsPedido);
		

		if($tipoDoc == 'NCC' || $tipoDoc == 3){
			if($rowPedido['saldo_nota_credito']!= 0){
				if($hddMostrarNCC != 0){
					$objResponse->script("byId('liTabNCC').style.display = '';");
					$objResponse->script("byId('divTabNCC').style.display = '';");
					$objResponse->assign("hddMostrarNCC", "value", 0); //valor 0 cuando se ingresa la pestaña de motivos NCC
				}
				$objResponse->assign("txtTotalSaldoNCC", "value", number_format($rowPedido['saldo_nota_credito'], 2, ".", ","));
				$objResponse->assign("hddTotalSaldoNCC", "value", $rowPedido['saldo_nota_credito']);
				
			}else{
				$objResponse->script("byId('liTabNCC').style.display = 'none';");
				$objResponse->script("byId('divTabNCC').style.display = 'none';");
				$objResponse->assign("hddMostrarNCC", "value", 1); //valor 1 cuando se borra la pestaña de motivos NCC
			}
		}
		
		if($tipoDoc == 'NDC' || $tipoDoc == 3){
			$objResponse->assign("txtTotalSaldoNDC", "value",number_format($rowPedido['total_cuotas'], 2, ".", ","));
			$objResponse->assign("hddTotalSaldoNDC", "value",$rowPedido['total_cuotas']);
		}
		
	}
	
	return $objResponse;
}


function calcularDcto($frmListaMotivo, $tipoMotivo,$contMotivo = '') {
	
	$objResponse = new xajaxResponse();
	
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaMotivo['cbx'.$tipoMotivo];
	
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
				
			$objResponse->assign("trItmMotivo$tipoMotivo:".$valor,"className",$clase);
			$objResponse->assign("tdNumItmMotivo$tipoMotivo:".$valor,"innerHTML",$i);
		}
	}
	$objResponse->assign("hddObjItmMotivo","value",((count($arrayObj) > 0) ? implode("|",$arrayObj) : ""));


	// CALCULA EL SUBTOTAL
	$txtSubTotal = '';
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indice => $valor) {
			$txtTotalItm = str_replace(",", "", $frmListaMotivo['txtPrecioItm'.$tipoMotivo.$valor]);
				
			$txtSubTotal += $txtTotalItm;
		}
	} 

	//CALCULA EL SALDO ACTUAL
	if (isset($arrayObj)) {
		$saldo = $frmListaMotivo['hddTotalSaldo'.$tipoMotivo] - $txtSubTotal;
	}else{
		$objResponse->script(sprintf("xajax_cargarCampos(xajax.getFormValues('frmListaMotivos'),'Motivos','%s','','activo');",$tipoMotivo));
		
	}
	$objResponse->assign("txtSubTotal$tipoMotivo","value",number_format($txtSubTotal, 2, ".", ","));
	$objResponse->assign("txtTotalSaldo$tipoMotivo","value",number_format($saldo, 2, ".", ","));

	$objResponse->script(sprintf("xajax_validarFrmMotivo('%s',xajax.getFormValues('frmListaMotivos'),'%s');",$contMotivo,$tipoMotivo));
	
	return $objResponse;
}



function eliminarMotivo($trItmMotivo, $frmListaMotivos,$tipoMotivo) {
	$objResponse = new xajaxResponse();

	if (isset($trItmMotivo) && $trItmMotivo > 0) {
		$objResponse->script("
		fila = document.getElementById('trItmMotivo$tipoMotivo:".$trItmMotivo."');
		padre = fila.parentNode;
		padre.removeChild(fila);");

		$objResponse->script(sprintf("xajax_eliminarMotivo('', xajax.getFormValues('frmListaMotivos'),'%s');",$tipoMotivo));
	}

	$objResponse->script(sprintf("xajax_calcularDcto(xajax.getFormValues('frmListaMotivos'),'%s','');",$tipoMotivo));
	
	return $objResponse;
}


function eliminarMotivoLote($frmListaMotivos,$tipoMotivo) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmListaMotivos['cbxItm'.$tipoMotivo])) {
		foreach ($frmListaMotivos['cbxItm'.$tipoMotivo] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmMotivo$tipoMotivo:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script(sprintf("xajax_calcularDcto(xajax.getFormValues('frmListaMotivos'),'%s','');",$tipoMotivo));
	
	return $objResponse;
}


function listaMotivo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("modulo LIKE 'CC'
	AND ingreso_egreso LIKE '%s'", $valCadBusq[0]);

	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
				valTpDato("%".$valCadBusq[2]."%", "text"));
	}

	$query = sprintf("SELECT motivo.*,
		(CASE motivo.modulo
			WHEN 'CC' THEN	'Cuentas por Cobrar'
			WHEN 'CP' THEN	'Cuentas por Pagar'
			WHEN 'CJ' THEN	'Caja'
			WHEN 'TE' THEN	'Tesorería'
		END) AS descripcion_modulo_transaccion,

		(CASE motivo.ingreso_egreso
			WHEN 'I' THEN	'Ingreso'
			WHEN 'E' THEN	'Egreso'
		END) AS descripcion_tipo_transaccion
	FROM pg_motivo motivo %s", $sqlBusq);

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
	$htmlTh .= ordenarCampo("xajax_listaMotivo", "10%", $pageNum, "id_motivo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
	$htmlTh .= ordenarCampo("xajax_listaMotivo", "54%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre"));
	$htmlTh .= ordenarCampo("xajax_listaMotivo", "20%", $pageNum, "modulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Módulo"));
	$htmlTh .= ordenarCampo("xajax_listaMotivo", "16%", $pageNum, "ingreso_egreso", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Transacción"));
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		switch($row['modulo']) {
			case "CC" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"".utf8_encode("CxC")."\"/>"; break;
			case "CP" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"".utf8_encode("CxP")."\"/>"; break;
			case "CJ" : break;
			case "TE" : $imgPedidoModulo = "<img src=\"../img/iconos/ico_tesoreria.gif\" title=\"".utf8_encode("Tesorería")."\"/>"; break;
			default : $imgPedidoModulo = "";
		}

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td>";
		$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarMotivo%s\" onclick=\"validarInsertarMotivo('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$contFila,
				$row['id_motivo']);
		$htmlTb .= "</td>";
		$htmlTb .= "<td align=\"right\">".$row['id_motivo']."</td>";
		$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
		$htmlTb .= "<td>";
		$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\">";
		$htmlTb .= "<tr>";
		$htmlTb .= "<td>".$imgPedidoModulo."</td>";
		$htmlTb .= "<td>".utf8_encode($row['descripcion_modulo_transaccion'])."</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td align=\"center\">".($row['descripcion_tipo_transaccion'])."</td>";
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
				0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum > 0) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
				max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"100\">";

	$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMotivo(%s,'%s','%s','%s',%s)\">",
			"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
	for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
		$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
	}
	$htmlTf .= "</select>";
		
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum < $totalPages) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
				min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum < $totalPages) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMotivo(%s,'%s','%s','%s',%s);\">%s</a>",
				$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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

	$objResponse->assign("divListaMotivo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}



function listaPedido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pedido.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = pedido.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pedido.fecha_financiamiento BETWEEN %s AND %s)",
				valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
				valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}


	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pedido.estatus_pedido = %s)",
				valTpDato($valCadBusq[3], "int"));
	}

	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pedido.numeracion_pedido LIKE %s
		OR pedido.id_pedido_financiamiento LIKE %s
		OR pedido.id_cliente LIKE %s
		OR pedido.id_empresa LIKE %s
		OR cliente.ci LIKE %s
		OR cliente.telf LIKE %s
		OR cliente.nombre LIKE %s)",
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"),
				valTpDato("%".$valCadBusq[4]."%", "text"));
	}

	$query = sprintf("SELECT 
						pedido.id_pedido_financiamiento,
						pedido.estatus_pedido,
						pedido.id_cliente,
						pedido.id_notadecargo_cxc,
						pedido.id_empresa,
						pedido.fecha_financiamiento,
						cliente.nombre,
						cliente.ci,
						cliente.telf,
						pedido.numeracion_pedido AS numeracion,
						empresa.nombre_empresa AS empresa,
						CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombre_cliente,
						pedido.fecha_financiamiento AS fecha_inicial,
						pedido.fecha_fin_financiamiento AS fecha_final,
						pedido.tipo_interes,
						CONCAT_WS(' ',pedido.interes_financiamiento,'%s',
							(SELECT DISTINCT fi_plazos.nombre_plazo 
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_interes_plazo)) AS interes,
						CONCAT_WS(' ',pedido.cuotas_duracion, 
							(SELECT DISTINCT fi_plazos.nombre_plazo 
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_duracion_plazo)) AS duracion,
						CONCAT_WS(' ',pedido.numero_pagos,'pagos en',
							(SELECT DISTINCT fi_plazos.nombre_plazo 
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_frecuencia_plazo)) AS frecuencia,
						pedido.monto_financiamiento_documentos AS total_inicial,
						pedido.total_adicionales AS total_adicionales,
						pedido.total_intereses AS total_intereses,
						pedido.total_monto_financiar AS total_cuotas
					FROM fi_pedido pedido
						INNER JOIN cj_cc_cliente cliente ON (pedido.id_cliente = cliente.id)
						INNER JOIN pg_empresa empresa ON (pedido.id_empresa = empresa.id_empresa)
						INNER JOIN fi_plazos plazo ON (pedido.id_duracion_plazo = plazo.id_plazo)
					%s", '%', $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf("ORDER BY %s %s", $campOrd, $tpOrd) : "";
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
	$htmlTh .= "<td width=\"4%\"></td>";
	$htmlTh .= "<td width=\"4%\">Estatus</td>";
	$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "numeracion_pedido", $campOrd, $tpOrd, $valBusq, $maxRows, "Numeracion");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "8%", $pageNum, "fecha_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Inicial");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "8%", $pageNum, "fecha_final", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Final");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "tipo_interes", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Interes");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "interes", $campOrd, $tpOrd, $valBusq, $maxRows, "Interes");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "8%", $pageNum, "duracion", $campOrd, $tpOrd, $valBusq, $maxRows, "Duracion");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "8%", $pageNum, "frecuencia", $campOrd, $tpOrd, $valBusq, $maxRows, "Frecuencia");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "total_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Inicial");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "total_intereses", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Intereses");
	$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "total_cuotas", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Cuotas");
	$htmlTh .= "<td class=\"noprint\" colspan=\"4\"></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		//validando el estatus del pedido
		
		switch ($row['estatus_pedido']){
			case 0 : $classEstatus = "divResaltarRojo"; $estatusPedido = "NO APROBADO"; break;
			case 1 : $classEstatus = "divResaltarAmarillo"; $estatusPedido = "PARCIALMENTE PAGADO"; break;
			case 2 : $classEstatus = "divResaltarVerde"; $estatusPedido = "PAGADO"; break;
			case 3 : $classEstatus = "divResaltarAzul"; $estatusPedido = "APROBADO"; break;
			case 4 : $classEstatus = "divResaltarNaranja"; $estatusPedido = "ATRASADO"; break;
		}

		//Eligiendo el tipo de interes
		
		switch ($row['tipo_interes']){
			case 1 : $tipoInteres = "SIMPLE";  break;
			case 2 : $tipoInteres = "COMPUESTO"; break;
		}
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td align=\"center\" class=\"$classEstatus\">$estatusPedido</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['empresa'])."</td>";
			$htmlTb .= "<td align=\"left\">".strtoupper($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_inicial']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_final']))."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoInteres."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode(strtoupper($row['interes']))."</td>";
			$htmlTb .= "<td align=\"center\">".strtoupper($row['duracion'])."</td>";
			$htmlTb .= "<td align=\"center\">".strtoupper($row['frecuencia'])."</td>";
			$htmlTb .= "<td align=\"right\" class=\"textoNegrita_9px\">".number_format($row['total_inicial']+$row['total_adicionales'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" class=\"textoNegrita_9px\">".number_format($row['total_intereses'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\" class=\"textoNegrita_9px\">".number_format($row['total_cuotas'], 2, ".", ",")."</td>";
			
			$htmlTb .= "<td>";
			if ($row['estatus_pedido'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('fi_form_financiamiento.php?id=%s');\" src=\"../img/iconos/pencil.png\" title=\"Editar Pedido\"/>",
						$row['id_pedido_financiamiento']);
			}else{
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/fi_pedido_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Imprimir Pedido\"/>",
						$row['id_pedido_financiamiento']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_pedido'] == 0) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aValidar%s\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante(this, 'tdFlotanteTitulo', '%s',byId('hddMostrarNCC').value);\"><img class=\"puntero\"   src=\"../img/iconos/accept.png\" title=\"Cerrar Pedido\"/></a>",
						$contFila,
						$row['id_pedido_financiamiento']);
			}else{
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAprobarPedido%s\" onclick=\"verVentana('../cxc/cc_nota_debito_form.php?id=%s', 960, 550);\" src=\"../img/iconos/ico_view.png\" title=\"Ver Nota de Debito CxC\"/>",
						$contFila,
						$row['id_notadecargo_cxc']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['estatus_pedido'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAnularPedido%s\" onclick=\"xajax_validarPedidoDesaprobado('%s');\" src=\"../img/iconos/cancel.png\" title=\"Anular Pedido\"/>",
						$contFila,
						$row['id_pedido_financiamiento']);
			}else{
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAprobarPedido%s\" onclick=\"verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/mp_facturado.png\" title=\"Imprimir Nota Debito Asociada\"/>",
						$contFila,
						$row['id_notadecargo_cxc']);
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
				0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum > 0) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
				max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"100\">";

	$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s)\">",
			"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
	for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
		$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
	}
	$htmlTf .= "</select>";
		
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum < $totalPages) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
				min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum < $totalPages) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedidoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
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

	$objResponse->assign("divListaPedido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);


	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalDocumentos += $row['total_inicial'];
		$totalInteres += $row['total_intereses'];
		$totalAdicionales += $row['total_adicionales'];
		$totalMontoFinanciar += $row['total_cuotas'];
	}

	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalDocumentos, 2, ".", ","));
	$objResponse->assign("spnTotalInteres","innerHTML",number_format($totalInteres, 2, ".", ","));
	if($totalAdicionales > 0){
		$objResponse->script("byId('trTotalAdicionales').style.display = '';");
		$objResponse->assign("spnTotalAdicionales","innerHTML",number_format($totalAdicionales, 2, ".", ","));
	}
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalMontoFinanciar, 2, ".", ","));

	return $objResponse;
}



function insertarMotivo($idMotivo, $frmListaMotivos,$tipoMotivo) {
	$objResponse = new xajaxResponse();


		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		$arrayObj = $frmListaMotivos['cbx'.$tipoMotivo];
		$contFila = $arrayObj[count($arrayObj)-1];
		foreach ($arrayObj as $indice => $valor){
			if ($frmListaMotivos['hddIdMotivoItm'.$tipoMotivo.$valor] == $idMotivo) {
				return $objResponse->alert("El motivo seleccionado ya se encuentra agregado");
			}
		}

		$Result1 = insertarItemMotivo($contFila, "", $idMotivo, $precioUnitario,$tipoMotivo);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}

		$objResponse->script(sprintf("xajax_calcularDcto(xajax.getFormValues('frmListaMotivos'),'%s','');",$tipoMotivo));
		
	return $objResponse;
}

function validarFrmMotivo($idMotivo,$frmMotivos,$tipoMotivo) {
	$objResponse = new xajaxResponse();
	
	$saldoActual = str_replace(",", "", $frmMotivos['txtTotalSaldo'.$tipoMotivo]);
	$tipoMotivo == 'NCC' ? $doc = 'Nota de Credito' : $doc = 'Nota de Debito';
	
	if($saldoActual < 0){
		$objResponse->script("byId('$idMotivo').value = '0.00';");
		$objResponse->script(sprintf("xajax_calcularDcto(xajax.getFormValues('frmListaMotivos'),'%s','');",$tipoMotivo));
		$objResponse->alert("El subtotal es mayor al saldo de la $doc");
	}
	
	return $objResponse;
}

function validarPedidoDesaprobado ($idPedido){
	
	$objResponse = new xajaxResponse();
	
	//borrando documentos asociados al pedido
	$deleteSQL = sprintf("DELETE FROM fi_documento WHERE id_pedido_financiamiento = %s;",valTpDato($idPedido, "int")); //BORRANDO DOCUMENTOS
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	//borrando amortizaciones asociados al pedido
	$deleteSQL = sprintf("DELETE FROM fi_amortizacion WHERE id_pedido_financiamiento = %s;",valTpDato($idPedido, "int")); //BORRANDO AMORTIZACIONES
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	//borrando adicionales asociados al pedido
	$deleteSQL = sprintf("DELETE FROM fi_financiamiento_adicionales WHERE id_pedido_financiamiento = %s;",valTpDato($idPedido, "int")); //BORRANDO AMORTIZACIONES
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	//borrando el pedido
	$deleteSQL = sprintf("DELETE FROM fi_pedido WHERE id_pedido_financiamiento = %s;",valTpDato($idPedido, "int"));// BORRANDO EL PEDIDO AUN NO APROBADO
	$Result1 = mysql_query($deleteSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$objResponse->assign("lstEstatusPedido", "value", -1);
	$objResponse->script("byId('btnBuscar').click();");
	
	$objResponse->alert("Pedido Eliminado con Exito.");
	
	
	return $objResponse;
	
}

function validarPedidoListo ($idPedido,$frmMotivos){
	
	
	$objResponse = new xajaxResponse();
	

	//QUERY DEL PEDIDO CON TODOS LOS DATOS
	
	mysql_query("START TRANSACTION;");
	
	$queryPedido = sprintf("SELECT
						pedido.id_pedido_financiamiento,
						pedido.estatus_pedido,
						pedido.id_cliente,
						pedido.id_empresa,
						pedido.fecha_financiamiento,
						cliente.nombre,
						cliente.ci,
						cliente.telf,
						pedido.numeracion_pedido AS numeracion,
						empresa.nombre_empresa AS empresa,
						CONCAT_WS(' ',cliente.nombre,cliente.apellido) AS nombre_cliente,
						pedido.fecha_financiamiento AS fecha_inicial,
						pedido.fecha_fin_financiamiento AS fecha_final,
						pedido.tipo_interes,
						CONCAT_WS(' ',pedido.interes_financiamiento,'%s') AS interes,
						CONCAT_WS(' ',pedido.cuotas_duracion,
							(SELECT DISTINCT fi_plazos.nombre_plazo
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_duracion_plazo)) AS duracion,
						CONCAT_WS(' ',pedido.numero_pagos,'pagos en',
							(SELECT DISTINCT fi_plazos.nombre_plazo
							 FROM fi_plazos
						   WHERE fi_plazos.id_plazo = pedido.id_frecuencia_plazo)) AS frecuencia,
						pedido.monto_financiamiento_documentos AS total_inicial,
						pedido.total_intereses AS total_intereses,
						pedido.total_monto_financiar AS total_cuotas
					FROM fi_pedido pedido
						INNER JOIN cj_cc_cliente cliente ON (pedido.id_cliente = cliente.id)
						INNER JOIN pg_empresa empresa ON (pedido.id_empresa = empresa.id_empresa)
						INNER JOIN fi_plazos plazo ON (pedido.id_duracion_plazo = plazo.id_plazo)
					WHERE pedido.id_pedido_financiamiento = %s",'%',$idPedido);
	
	
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) { return $objResponse->alert($query.mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	//VERIFICANDO QUE LAS CAJAS ESTEN ABIERTAS AL DIA
	
	$Result1 = validarAperturaCaja($rowPedido['id_empresa'], date("Y-m-d"));
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	
	//CREAR NOTAS DE CREDITO ASOCIADAS A LA FACTURA (SI HAY)
	$objResponse->loadCommands(crearNotaDeCreditoFactura($rowPedido,$frmMotivos));
	
	//SE CREA LA NOTA DE CARGO
	$objResponse->loadCommands(crearNotaDeCargoFinanciamiento($rowPedido,$frmMotivos));
	
	//CAMBIA EL ESTADO DEL PEDIDO NO APROBADO A APROBADO
	$cambiarEstatusSQL = sprintf("UPDATE fi_pedido SET
					estatus_pedido = %s
					WHERE id_pedido_financiamiento = %s;",
			valTpDato(3, "int"), //Cambiando estatus a APROBADO
			valTpDato($idPedido, "int"));
	$rs = mysql_query($cambiarEstatusSQL);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	
	
	mysql_query("COMMIT;");
	
	$objResponse->script("byId('btnCancelarContrato').click();");
	$objResponse->assign("lstEstatusPedido", "value", 3);
	$objResponse->script("byId('btnBuscar').click();");
	
	return $objResponse;
	
}

function crearNotaDeCargoFinanciamiento($rowPedido,$frmMotivos) {
	
	$objResponse = new xajaxResponse();
	
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS DE MOTIVOS HAY AGREGADOS
	$arrayObj = $frmMotivos['cbxNDC'];
	
	
	// NUMERACION DE NOTA DE CARGO FINANCIAMIENTO (PEDIDO DE FINANCIAMIENTO)
	$queryNumeracionNDC = sprintf("SELECT *
			FROM pg_empresa_numeracion emp_num
				INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
			WHERE emp_num.id_numeracion = %s
				AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																								WHERE suc.id_empresa = %s)))
			ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato( 53 , "int"), // 53 = Numeracion de Nota de Cargo Financiamiento
			valTpDato($rowPedido['id_empresa'], "int"),
			valTpDato($rowPedido['id_empresa'], "int"));
	
	$rsNumeracionNDC = mysql_query($queryNumeracionNDC);
	if (!$rsNumeracionNDC) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowNumeracionNDC = mysql_fetch_assoc($rsNumeracionNDC);
	
	$idEmpresaNumeracionNDC = $rowNumeracionNDC['id_empresa_numeracion'];
	$idNumeracionesNDC = $rowNumeracionNDC['id_numeracion'];
	$numeroActualNDC = $rowNumeracionNDC['prefijo_numeracion'].$rowNumeracionNDC['numero_actual'];
	$numeroSiguienteNDC = $numeroActualNDC+1;
	
	
	// ACTUALIZA LA NUMERACIÓN DE LA NOTA DE CARGO (PEDIDO DE FINANCIAMIENTO)
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracionNDC, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	//CREANDO LA NOTA DE CARGO ASOCIADA AL PEDIDO DE FINANCIAMIENTO
		
	$observacionNDC = 'NOTA DE CARGO POR FINANCIAMIENTO CON UNA DURACION DE '.strtoupper($rowPedido['duracion']).' Y UNA FRECUENCIA DE '.strtoupper($rowPedido['frecuencia']);
	
	$insertSQL = sprintf("INSERT INTO cj_cc_notadecargo (id_empresa, idCliente, numeroNotaCargo, numeroControlNotaCargo, fechaRegistroNotaCargo, fechaVencimientoNotaCargo, idDepartamentoOrigenNotaCargo, tipoNotaCargo, estadoNotaCargo, montoTotalNotaCargo, saldoNotaCargo, subtotalNotaCargo, observacionNotaCargo, id_empleado_creador)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($rowPedido['id_empresa'], "int"),
			valTpDato($rowPedido['id_cliente'], "int"),
			valTpDato($numeroActualNDC, "text"),
			valTpDato($numeroActualNDC, "text"),
			valTpDato(date("Y-m-d", strtotime($rowPedido['fecha_inicial'])), "date"),
			valTpDato(date("Y-m-d", strtotime($rowPedido['fecha_final'])), "date"),
			valTpDato(5, "int"), // 5 = MODULO DE FINANCIAMIENTO
			valTpDato(0, "int"), // 0 = Credito
			valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada, 2 = Parcialmente Cancelada
			valTpDato($rowPedido['total_cuotas'], "real_inglesa"),
			valTpDato($rowPedido['total_cuotas'], "real_inglesa"),
			valTpDato($rowPedido['total_cuotas'], "real_inglesa"),
			valTpDato($observacionNDC, "text"),
			valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idNotaCargo = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("ND", "text"),
		valTpDato($idNotaCargo, "int"),
		valTpDato(date("Y-m-d", strtotime($rowPedido['fecha_financiamiento'])), "date"),
		valTpDato("2", "int")); //1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	
	// INSERTA EL DETALLE DEL DOCUMENTO MOTIVOS
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$idMotivo = $frmMotivos['hddIdMotivoItmNDC'.$valor];
			$precioUnitario = str_replace(",", "", $frmMotivos['txtPrecioItmNDC'.$valor]);
				
			$insertSQL = sprintf("INSERT INTO cj_cc_nota_cargo_detalle_motivo (id_nota_cargo, id_motivo, precio_unitario)
										VALUE (%s, %s, %s);",
					valTpDato($idNotaCargo, "int"),
					valTpDato($idMotivo, "int"),
					valTpDato($precioUnitario, "real_inglesa"));
			
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idNotaDebitoDetalle = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
		}
	
	}
	
	//ACTUALIZANDO EL ID DE LA NOTA DE CARGO EN EL PEDIDO
	
	$updateSQL = sprintf("UPDATE fi_pedido SET
					id_notadecargo_cxc = %s
				WHERE id_pedido_financiamiento = %s;",
			valTpDato($idNotaCargo, "int"),
			valTpDato($rowPedido['id_pedido_financiamiento'], "int"));
		
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	
	return $objResponse;
	
}

function crearNotaDeCreditoFactura($rowPedido,$frmListaMotivo) {
	
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS DE MOTIVOS HAY AGREGADOS
	$arrayObj = $frmListaMotivo['cbxNCC'];
	
	//VARIABLES DEL INSERT NOTA DE CREDITO
	$idCliente = $rowPedido['id_cliente'];
	$idEmpresa = $rowPedido['id_empresa'];
	$fechaInicial = $rowPedido['fecha_inicial'];
	
	
	//BUSCANDO FACURAS ASOCIADAS
	
	$queryFacturas = sprintf("SELECT *
						FROM fi_documento doc
						WHERE doc.id_pedido_financiamiento = %s
						AND id_documento_tabla <> 0;", // id_documento_tabla <> 0 identifica que el financiamiento no sea un monto
				valTpDato($rowPedido['id_pedido_financiamiento'], "int"));
	
	mysql_query("SET NAMES 'utf8';");
	$ResultFactura = mysql_query($queryFacturas);
	if (!$ResultFactura) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
	$totalRows = mysql_num_rows($ResultFactura);
	

	//CREANDO NOTAS DE CREDITO PARA EL PAGO DE LA FACTURA
	
	
	if($totalRows > 0){
		//FACTURAS EN ARRAY
		
			while($row = mysql_fetch_assoc($ResultFactura)){
				$rowFactura[] = $row;
				$sumaSaldo += $row['saldo_documento'];
			}
			
				// NUMERACION DEL DOCUMENTO
			
				$queryNumeracionNCC= sprintf("SELECT *
				FROM pg_empresa_numeracion emp_num
					INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
				WHERE emp_num.id_numeracion = %s
					AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																									WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC LIMIT 1;",
						valTpDato(22, "int"), //Nota de Credito
						valTpDato($idEmpresa, "int"),
						valTpDato($idEmpresa, "int"));
				$rsNumeracionNCC = mysql_query($queryNumeracionNCC);
				if (!$rsNumeracionNCC) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowNumeracionNCC = mysql_fetch_assoc($rsNumeracionNCC);
					
				$idEmpresaNumeracion = $rowNumeracionNCC['id_empresa_numeracion'];
				$idNumeraciones = $rowNumeracionNCC['id_numeracion'];
				$numeroActual = $rowNumeracionNCC['prefijo_numeracion'].$rowNumeracionNCC['numero_actual'];
					
				
				// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
				$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
						valTpDato($idEmpresaNumeracion, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
				
				//INSERTANDO NOTA DE CREDITO DE LA FACTURA

				$observacionNDC = 'NOTA DE CREDITO POR FINANCIAMIENTO CON UNA DURACION DE '.strtoupper($rowPedido['duracion']).' Y UNA FRECUENCIA DE '.strtoupper($rowPedido['frecuencia'].'. '.$frmListaMotivo['txtObservacionNCC']);
				
				$insertSQL = sprintf("INSERT INTO cj_cc_notacredito (id_empresa, idCliente, id_empleado_vendedor, numeracion_nota_credito, numeroControl, fechaNotaCredito, idDepartamentoNotaCredito,  idDocumento, tipoDocumento, estadoNotaCredito, montoNetoNotaCredito, saldoNotaCredito, observacionesNotaCredito, subtotalNotaCredito,  estatus_nota_credito, id_empleado_creador)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
						valTpDato($idEmpresa, "int"),
						valTpDato($idCliente, "int"),
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
						valTpDato($numeroActual, "text"),
						valTpDato($numeroActual, "text"),
						valTpDato(date("Y-m-d", strtotime($fechaInicial)), "date"),
						valTpDato(5, "int"), // 5 = Modulo de Financiamiento
						valTpDato(0, "int"), // Cero porque no es para devolucion
						valTpDato("NC", "text"), // Nota de credito 
						valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado No Asignado, 2 = Parcialmente Asignado, 3 = Asignado
						valTpDato($sumaSaldo, "real_inglesa"),
						valTpDato($sumaSaldo, "real_inglesa"),
						valTpDato($observacionNDC, "text"),
						valTpDato($sumaSaldo, "real_inglesa"),
						valTpDato(2, "int"), // 1 = Aprobada, 2 = Aplicada
						valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
				mysql_query("SET NAMES 'utf8';");
				
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idNotaCredito = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				
				// INSERTA EL DETALLE DEL DOCUMENTO
				if (isset($arrayObj)) {
					foreach($arrayObj as $indice => $valor) {
						$idMotivo = $frmListaMotivo['hddIdMotivoItmNCC'.$valor];
						$precioUnitario = str_replace(",", "", $frmListaMotivo['txtPrecioItmNCC'.$valor]);
							
						$insertSQL = sprintf("INSERT INTO cj_cc_nota_credito_detalle_motivo (id_nota_credito, id_motivo, precio_unitario)
										VALUE (%s, %s, %s);",
								valTpDato($idNotaCredito, "int"),
								valTpDato($idMotivo, "int"),
								valTpDato($precioUnitario, "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idNotaCreditoDetalle[] = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
					}
						
				}
				
				
				//TOMANDO LOS VALORES DE LAS FACTURAS ASOCIADAS
				for($i = 0; $i < count($rowFactura); $i++){
					$updateNCC = sprintf("UPDATE fi_documento doc SET id_notadecredito_cxc = %s
									WHERE doc.id_documento_tabla = %s;",
								valTpDato($idNotaCredito, "int"),
								valTpDato($rowFactura[$i]['id_documento_tabla'], "int"));
						
						$Result1 = mysql_query($updateNCC);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				}
				//ARRAY QUE CONTIENE LOS VALORES DE LAS NOTAS DE CREDITO
				
				
	//PAGAR FACTURAS CON LAS NOTAS DE CREDITO
		
	$objResponse->loadCommands(pagarFacturaConNCC($rowPedido,$rowFactura,$idNotaCredito));
	
	}
	
	return $objResponse;
	
}

$xajax->register(XAJAX_FUNCTION,"buscarMotivo");
$xajax->register(XAJAX_FUNCTION,"buscarPedido");
$xajax->register(XAJAX_FUNCTION,"cargarCampos");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"eliminarMotivo");
$xajax->register(XAJAX_FUNCTION,"eliminarMotivoLote");
$xajax->register(XAJAX_FUNCTION,"listaMotivo");
$xajax->register(XAJAX_FUNCTION,"listaPedido");
$xajax->register(XAJAX_FUNCTION,"insertarMotivo");
$xajax->register(XAJAX_FUNCTION,"pagarFacturaConNCC");
$xajax->register(XAJAX_FUNCTION,"validarFrmMotivo");
$xajax->register(XAJAX_FUNCTION,"validarPedidoDesaprobado");
$xajax->register(XAJAX_FUNCTION,"validarPedidoListo");



function insertarItemMotivo($contFila, $hddIdNotaCreditoDet = "", $idMotivo = "", $precioUnitario = "",$tipoMotivo = "") {
	$contFila++;
	
	if ($hddIdNotaCreditoDet > 0) {

	}

	$idMotivo = ($idMotivo == "" && $totalRowsNotaDebitoDet > 0) ? $rowNotaDebitoDet['id_motivo'] : $idMotivo;
	$precioUnitario = ($precioUnitario == "" && $totalRowsNotaDebitoDet > 0) ? $rowNotaDebitoDet['precio_unitario'] : $precioUnitario;
	$aClassReadonly = ($hddIdNotaCreditoDet > 0) ? "class=\"inputSinFondo\" readonly=\"readonly\"" : "class=\"inputCompleto\"";
	$aEliminar = ($hddIdNotaCreditoDet > 0) ? "" :
	sprintf("<a id=\"aEliminarItm%s:%s\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>",
			$tipoMotivo,$contFila);

	// BUSCA LOS DATOS DEL ARTICULO
	$queryMotivo = sprintf("SELECT motivo.*,
		(CASE motivo.modulo
			WHEN 'CC' THEN	'Cuentas por Cobrar'
			WHEN 'CP' THEN	'Cuentas por Pagar'
			WHEN 'CJ' THEN	'Caja'
			WHEN 'TE' THEN	'Tesorería'
		END) AS descripcion_modulo_transaccion,

		(CASE motivo.ingreso_egreso
			WHEN 'I' THEN	'Ingreso'
			WHEN 'E' THEN	'Egreso'
		END) AS descripcion_tipo_transaccion
	FROM pg_motivo motivo WHERE id_motivo = %s;",
			valTpDato($idMotivo, "int"));
	$rsMotivo = mysql_query($queryMotivo);
	if (!$rsMotivo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsMotivo = mysql_num_rows($rsMotivo);
	$rowMotivo = mysql_fetch_assoc($rsMotivo);

	// INSERTA EL ARTICULO SIN INJECT
	$htmlItmPie = sprintf("$('#trItmPie%s').before('".
			"<tr align=\"left\" id=\"trItmMotivo%s:%s\" title=\"trItmMotivo:%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmMotivo%s:%s\"><input id=\"cbxItm%s\" name=\"cbxItm%s[]\" type=\"checkbox\" value=\"%s\"/>".
			"<input id=\"cbx%s\" name=\"cbx%s[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItmMotivo%s:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDescItm%s%s\" name=\"txtDescItm%s%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"/></td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioItm%s%s\" class=\"inputHabilitado\"name=\"txtPrecioItm%s%s\" %s onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td>%s".
			"<input type=\"hidden\" id=\"hddIdNotaCreditoDet%s%s\" name=\"hddIdNotaCreditoDet%s%s\" readonly=\"readonly\" value=\"%s\"/>".
			"<input type=\"hidden\" id=\"hddIdMotivoItm%s%s\" name=\"hddIdMotivoItm%s%s\" readonly=\"readonly\" value=\"%s\"></td>".
			"</tr>');

		byId('txtPrecioItm%s%s').onblur = function() {
			setFormatoRafk(this,2);
			xajax_calcularDcto(xajax.getFormValues('frmListaMotivos'),'%s',this.id);
		}
		byId('aEliminarItm%s:%s').onclick = function() {
			xajax_eliminarMotivo('%s', xajax.getFormValues('frmListaMotivos'),'%s');
		}",
			$tipoMotivo,
			$tipoMotivo,$contFila, $contFila, $clase,
			$tipoMotivo,$contFila,$tipoMotivo, $tipoMotivo,$contFila,
			$tipoMotivo,$tipoMotivo,$contFila,
			$tipoMotivo,$contFila, $contFila,
			$rowMotivo['id_motivo'],
			$tipoMotivo,$contFila, $tipoMotivo,$contFila, $rowMotivo['descripcion'],
			$rowMotivo['descripcion_modulo_transaccion'],
			$rowMotivo['descripcion_tipo_transaccion'],
			$tipoMotivo,$contFila,$tipoMotivo, $contFila, $aClassReadonly, number_format($precioUnitario, 2, ".", ","),
			$aEliminar,
			$tipoMotivo,$contFila,$tipoMotivo, $contFila, $hddIdNotaCreditoDet,
			$tipoMotivo,$contFila,$tipoMotivo, $contFila, $idMotivo,

			$tipoMotivo,$contFila,
			$tipoMotivo,
			$tipoMotivo,$contFila,
			$contFila,$tipoMotivo);

	return array(true, $htmlItmPie, $contFila);
}


function validarAperturaCaja($idEmpresa, $fecha) {

	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato($idEmpresa, "int"));
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);

	if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
		$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
				valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);

		$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
	}

	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM an_apertura ape
	WHERE statusAperturaCaja IN (%s)
		AND fechaAperturaCaja NOT LIKE %s
		AND id_empresa = %s;",
			valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$totalRowsCierreCaja = mysql_num_rows($rsCierreCaja);
	$rowCierreCaja = mysql_fetch_array($rsCierreCaja);

	if ($totalRowsCierreCaja > 0) {
		return array(false, "Debe cerrar la caja del dia: ".date(spanDateFormat, strtotime($rowCierreCaja['fechaAperturaCaja'])));
	} else {
		// VERIFICA SI LA CAJA TIENE APERTURA
		$queryVerificarApertura = sprintf("SELECT * FROM an_apertura ape
		WHERE statusAperturaCaja IN (%s)
			AND fechaAperturaCaja LIKE %s
			AND id_empresa = %s;",
				valTpDato("1,2", "campo"), // 0 = CERRADA, 1 = ABIERTA, 2 = CERRADA PARCIAL
				valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
				valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
		$totalRowsVerificarApertura = mysql_num_rows($rsVerificarApertura);

		return ($totalRowsVerificarApertura > 0) ? array(true, "") : array(false, "Esta caja no tiene apertura");
	}
}

function pagarFacturaConNCC($rowPedido,$frmFacturas,$idNotaCredito) {
	
	$objResponse = new xajaxResponse();
	
			$queryNCC = sprintf("SELECT 
									doc.saldo_documento AS monto_pago_factura,
									cxc_fact.numeroFactura AS numero_factura
								FROM fi_documento doc
								INNER JOIN cj_cc_encabezadofactura cxc_fact ON (doc.id_documento_tabla = cxc_fact.idFactura)
								WHERE doc.id_notadecredito_cxc = %s;", //
						valTpDato($idNotaCredito, "int"));
			mysql_query("SET NAMES 'utf8';");
			$ResultNCC = mysql_query($queryNCC);
			if (!$ResultNCC) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

	for($i = 0; $i < count($frmFacturas);$i++){
		
			
			
			$facturasNotasCredito = mysql_fetch_assoc($ResultNCC);
			
			$idEmpresa = $rowPedido['id_empresa'];
			$fechaFinanciamiento = $rowPedido['fecha_financiamiento'];
			$idFactura = $frmFacturas[$i]['id_documento_tabla'];
			$numeroFactura = $facturasNotasCredito['numero_factura'];
		
			
			$queryAperturaCaja = sprintf("SELECT * FROM an_apertura ape
			WHERE idCaja = %s
				AND statusAperturaCaja IN (1,2)
				AND (ape.id_empresa = %s
					OR ape.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
											WHERE suc.id_empresa = %s));",
				valTpDato(1, "int"), // 1 = CAJA DE VEHICULOS
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
			$rsAperturaCaja = mysql_query($queryAperturaCaja);
			if (!$rsAperturaCaja) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
			
			$idApertura = $rowAperturaCaja['id'];
			
			// NUMERACION DEL DOCUMENTO
			$queryNumeracion = sprintf("SELECT *
				FROM pg_empresa_numeracion emp_num
					INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
				WHERE emp_num.id_numeracion = %s
					AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																									WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC LIMIT 1;",
					valTpDato(45, "int"), // 45 = Recibo de Pago Vehículos
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
			$rsNumeracion = mysql_query($queryNumeracion);
			if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
			
			$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
			$idNumeraciones = $rowNumeracion['id_numeracion'];
			$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
			
			// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
			$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
					valTpDato($idEmpresaNumeracion, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			$numeroActualPago = $numeroActual;
			
			// INSERTA EL RECIBO DE PAGO
			$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento, id_empleado_creador)
				VALUES (%s, %s, %s, %s, %s, %s, %s)",
					valTpDato($numeroActualPago, "int"),
					valTpDato($fechaFinanciamiento, "date"),
					valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Relacion Tabla tipodedocumentos)
					valTpDato(0, "int"),
					valTpDato($idFactura, "int"),
					valTpDato(2, "int"), // 2 = MODULO DE DONDE VIENE EL PAGO EN ESTE CASO VIENEN DE VEHICULOS
					/*EN REALIDAD VIENEN DE FINANCIAMIENTO PERO CADA PAGO DE CADA MODULO ES INDEPENDIENTE DEL OTRO
					 Y an_pagos SON AQUELLOS PAGOS QUE VIENEN DEL MODULO DE VEHICULOS
					 * */
					valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idEncabezadoReciboPago = mysql_insert_id();
			
			/*************POSIBLES CAMBIOS QUE AFECTAN A CONTABILIDAD *****************/
			
			// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
			$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_v (id_factura, fecha_pago)
				VALUES (%s, %s)",
					valTpDato($idFactura, "int"),
					valTpDato($fechaFinanciamiento, "date"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idEncabezadoPago = mysql_insert_id();
			
			//VARIABLES PARA INSERTAR LOS PAGOS DE LAS FACTURAS ASOCIADAS
			
			$idCheque = "";
			$tipoCheque = "-";
			$idTransferencia = "";
			$tipoTransferencia = "-";
			$estatusPago = 1;
			$idBancoCliente = 1;
			$txtCuentaClientePago = "-";
			$idBancoCompania = 1;
			$txtCuentaCompaniaPago = "-";
			$txtIdNumeroDctoPago = $idNotaCredito;
			$campo = "saldoNotaCredito";
			$tomadoEnCierre = 0; // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
			$txtMonto = str_replace(",", "", $facturasNotasCredito['monto_pago_factura']);
			$txtMontoSaldoCaja = $txtMonto;
			
			// ACTUALIZA LOS SALDOS EN LA APERTURA (NO TOMA EN CUENTA 7 = Anticipo EN EL SALDO DE LA CAJA)
			$updateSQL = sprintf("UPDATE an_apertura SET
						%s = %s + %s,
						saldoCaja = saldoCaja + %s
					WHERE id = %s;",
					$campo, $campo, valTpDato($txtMonto, "real_inglesa"),
					valTpDato($txtMontoSaldoCaja, "real_inglesa"),
					valTpDato($idApertura, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			// INSERTA LOS PAGOS DEL DOCUMENTO
			$insertSQL = sprintf("INSERT INTO an_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, numero_cuenta_cliente, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, id_cheque, tipo_transferencia, id_transferencia, tomadoEnComprobante, tomadoEnCierre, idCaja, id_apertura, estatus, id_condicion_mostrar, id_mostrar_contado, id_encabezado_v)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato(date("Y-m-d", strtotime($fechaFinanciamiento)), "date"),
					valTpDato(8, "int"), // 8 = FORMA DE PAGO (NOTA DE CREDITO)
					valTpDato($txtIdNumeroDctoPago, "text"),
					valTpDato($idBancoCliente, "int"),
					valTpDato($txtCuentaClientePago, "text"),
					valTpDato($idBancoCompania, "int"),
					valTpDato($txtCuentaCompaniaPago, "text"),
					valTpDato($txtMonto, "real_inglesa"),
					valTpDato($numeroFactura, "text"),
					valTpDato($tipoCheque, "text"),
					valTpDato($idCheque, "int"),
					valTpDato($tipoTransferencia, "text"),
					valTpDato($idTransferencia, "int"),
					valTpDato(1, "int"),
					valTpDato($tomadoEnCierre, "int"), // 0 = Pago Insertado, 1 = Pendiente por Depositar, 2 = Pago Depositado
					valTpDato(1, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
					valTpDato($idApertura, "int"),
					valTpDato($estatusPago, "int"), // Null = Anulado, 1 = Activo, 2 = Pendiente
					valTpDato(1, "int"), // Null = No, 1 = Si
					valTpDato(1, "int"), // Null = No, 1 = Si
					valTpDato($idEncabezadoPago, "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$idPago = mysql_insert_id();
			
			
			// ACTUALIZA EL SALDO DEL NOTA CREDITO DEPENDIENDO DE SUS PAGOS (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
			$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
							saldoNotaCredito = montoNetoNotaCredito
						WHERE idNotaCredito = %s
							AND estadoNotaCredito IN (0,1,2,3,4);",
					valTpDato($txtIdNumeroDctoPago, "int")); // AND (cxc_nc_det.id_concepto IS NULL OR cxc_nc_det.id_concepto NOT IN (6))
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						
	
			
			// ACTUALIZA EL SALDO DEL NOTA CREDITO SEGUN LOS PAGOS QUE HA REALIZADO CON ESTE
			$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
					saldoNotaCredito = saldoNotaCredito
										- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
												WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
													AND cxc_pago.formaPago IN (8)
													AND cxc_pago.estatus IN (1,2)), 0)
											+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
													WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
														AND cxc_pago.formaPago IN (8)
														AND cxc_pago.estatus IN (1,2)), 0)
											+ IFNULL((SELECT SUM(cxc_pago.monto_pago) FROM cj_det_nota_cargo cxc_pago
													WHERE cxc_pago.numeroDocumento = cxc_nc.idNotaCredito
														AND cxc_pago.idFormaPago IN (8)
														AND cxc_pago.estatus IN (1,2)), 0)
											+ IFNULL((SELECT SUM(cxc_pago.montoDetalleAnticipo) FROM cj_cc_detalleanticipo cxc_pago
													WHERE cxc_pago.numeroControlDetalleAnticipo = cxc_nc.idNotaCredito
														AND cxc_pago.id_forma_pago IN (8)
														AND cxc_pago.estatus IN (1,2)), 0))
				WHERE cxc_nc.idNotaCredito = %s
					AND cxc_nc.estadoNotaCredito IN (0,1,2,3,4);",
					valTpDato($txtIdNumeroDctoPago, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
			
			// ACTUALIZA EL ESTATUS DEL NOTA CREDITO (0 = No Cancelado, 1 = Cancelado (No Asignado), 2 = Parcialmente Asignado, 3 = Asignado, 4 = No Cancelado (Asignado))
			$updateSQL = sprintf("UPDATE cj_cc_notacredito cxc_nc SET
					estadoNotaCredito = (CASE
										WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
											AND ROUND(saldoNotaCredito, 2) > 0) THEN
											0
										WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
											AND ROUND(saldoNotaCredito, 2) <= 0
											AND cxc_nc.idNotaCredito IN (SELECT *
																		FROM (SELECT cxc_pago.numeroDocumento FROM an_pagos cxc_pago
																			WHERE cxc_pago.formaPago IN (8)
																				AND cxc_pago.estatus = 1
									
																			UNION
									
																			SELECT cxc_pago.numeroDocumento FROM sa_iv_pagos cxc_pago
																			WHERE cxc_pago.formaPago IN (8)
																				AND cxc_pago.estatus = 1
									
																			UNION
									
																			SELECT cxc_pago.numeroDocumento FROM cj_det_nota_cargo cxc_pago
																			WHERE cxc_pago.idFormaPago IN (8)
																				AND cxc_pago.estatus = 1
									
																			UNION
									
																			SELECT cxc_pago.numeroControlDetalleAnticipo FROM cj_cc_detalleanticipo cxc_pago
																			WHERE cxc_pago.id_forma_pago IN (8)
																				AND cxc_pago.estatus = 1) AS q)) THEN
											3
										WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
											AND ROUND(montoNetoNotaCredito, 2) = ROUND(saldoNotaCredito, 2)) THEN
											1
										WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
											AND ROUND(montoNetoNotaCredito, 2) > ROUND(saldoNotaCredito, 2)
											AND ROUND(saldoNotaCredito, 2) > 0) THEN
											2
										WHEN (ROUND(montoNetoNotaCredito, 2) = ROUND(montoNetoNotaCredito, 2)
											AND ROUND(saldoNotaCredito, 2) <= 0) THEN
											3
										WHEN (ROUND(montoNetoNotaCredito, 2) > ROUND(montoNetoNotaCredito, 2)
											AND ROUND(saldoNotaCredito, 2) <= 0) THEN
											4
									END)
				WHERE cxc_nc.idNotaCredito = %s
					AND cxc_nc.estadoNotaCredito IN (0,1,2,3,4);",
					valTpDato($txtIdNumeroDctoPago, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
			
			// INSERTA EL DETALLE DEL RECIBO DE PAGO
			$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
			VALUES (%s, %s)",
					valTpDato($idEncabezadoReciboPago, "int"),
					valTpDato($idPago, "int"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			
			// ACTUALIZA EL SALDO DE LA FACTURA
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
				saldoFactura = IFNULL(cxc_fact.subtotalFactura, 0)
									- IFNULL(cxc_fact.descuentoFactura, 0)
									+ IFNULL((SELECT SUM(cxc_fact_gasto.monto) FROM cj_cc_factura_gasto cxc_fact_gasto
											WHERE cxc_fact_gasto.id_factura = cxc_fact.idFactura), 0)
									+ IFNULL(IF(cxc_fact.idDepartamentoOrigenFactura = 1
											AND (SELECT COUNT(cxc_fact_impuesto.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_impuesto
												WHERE cxc_fact_impuesto.id_factura = cxc_fact.idFactura) = 0,
													(cxc_fact.calculoIvaFactura + cxc_fact.calculoIvaDeLujoFactura),
													(SELECT SUM(cxc_fact_impuesto.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_impuesto
													WHERE cxc_fact_impuesto.id_factura = cxc_fact.idFactura)), 0)
			WHERE idFactura = %s;",
				valTpDato($idFactura, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			// ACTUALIZA EL SALDO DE LA FACTURA DEPENDIENDO DE SUS PAGOS
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
				saldoFactura = IFNULL(saldoFactura, 0)
									- (IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM an_pagos cxc_pago
												WHERE cxc_pago.id_factura = cxc_fact.idFactura
													AND cxc_pago.estatus IN (1)), 0)
										+ IFNULL((SELECT SUM(cxc_pago.montoPagado) FROM sa_iv_pagos cxc_pago
												WHERE cxc_pago.id_factura = cxc_fact.idFactura
													AND cxc_pago.estatus IN (1)), 0))
			WHERE idFactura = %s;",
				valTpDato($idFactura, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
			$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
				estadoFactura = (CASE
									WHEN (ROUND(saldoFactura, 2) <= 0) THEN
										1
									WHEN (ROUND(saldoFactura, 2) > 0 AND ROUND(saldoFactura, 2) < ROUND(montoTotalFactura, 2)) THEN
										2
									ELSE
										0
								END),
				fecha_pagada = (CASE
									WHEN (ROUND(cxc_fact.saldoFactura, 2) <= 0 AND cxc_fact.fecha_pagada IS NULL) THEN
										(CASE
											WHEN (cxc_fact.idDepartamentoOrigenFactura IN (2,4,5)) THEN
												CONCAT(
													(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
													WHERE cxc_pago.id_factura = cxc_fact.idFactura),
													' ',
													DATE_FORMAT(NOW(),'%s'))
											WHEN (cxc_fact.idDepartamentoOrigenFactura IN (0,1,3)) THEN
												CONCAT(
													(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
													WHERE cxc_pago.id_factura = cxc_fact.idFactura),
													' ',
													DATE_FORMAT(NOW(),'%s'))
										END)
									WHEN (ROUND(cxc_fact.saldoFactura, 2) <= 0 AND cxc_fact.fecha_pagada IS NOT NULL) THEN
										cxc_fact.fecha_pagada
								END)
			WHERE idFactura = %s;",
				valTpDato("%H:%i:%s", "campo"),
				valTpDato("%H:%i:%s", "campo"),
				valTpDato($idFactura, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
	
	}
	

	return $objResponse;
	
}


?>