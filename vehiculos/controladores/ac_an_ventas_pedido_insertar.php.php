<?php


function asignarBanco($idBanco, $valores = "") {
	$objResponse = new xajaxResponse();
	
	$valores = explode("|",$valores);
	if (isset($valores)) {
		foreach ($valores as $indice => $valor) {
			$valor = explode("*",$valor);
			$arrayFinal[$valor[0]] = $valor[1];
		}
	}
	
	$objResponse->script("
	byId('trCuotasFinanciar2').style.display = 'none';
	byId('trCuotasFinanciar3').style.display = 'none';
	byId('trCuotasFinanciar4').style.display = 'none';");
	
	$queryBanco = sprintf("SELECT nombreBanco, porcentaje_flat FROM bancos WHERE idBanco = %s;",
		valTpDato($idBanco, "int"));
	$rsBanco = mysql_query($queryBanco);
	if (!$rsBanco) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsBanco = mysql_num_rows($rsBanco);
	$rowBanco = mysql_fetch_array($rsBanco);
	
	$queryFactor = sprintf("SELECT
		mes,
		factor,
		CONCAT(mes, ' Meses / ', tasa, '%s') AS financiamento
	FROM an_banco_factor
	WHERE id_banco = %s
	ORDER BY tasa;",
		valTpDato("%", "campo"),
		valTpDato($idBanco, "int"));
	$rsFactor = @mysql_query($queryFactor);
	if (!$rsFactor) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFactor = mysql_num_rows($rsFactor);
	if ($totalRowsBanco > 0) {
		if ($totalRowsFactor > 0) {
			$lstMesesFinanciar = "<select id=\"lstMesesFinanciar\" name=\"lstMesesFinanciar\" class=\"inputHabilitado\" onchange=\"percent();\">";
				$lstMesesFinanciar .= "<option value=\"\">[ Seleccione ]</option>";
			while($rowFactor = @mysql_fetch_assoc($rsFactor)) {
				$factores .= ",".$rowFactor['mes'].":".$rowFactor['factor'];
				
				$selected = ($arrayFinal['lstMesesFinanciar'] == $rowFactor['mes']) ? "selected=\"selected\"" : "";
				
				$lstMesesFinanciar .= "<option ".$selected." value=\"".$rowFactor['mes']."\">".$rowFactor['financiamento']."</option>";
			}
			$lstMesesFinanciar .= "</select>";
			
			$lstMesesFinanciar2 = "";
			$lstMesesFinanciar3 = "";
			$lstMesesFinanciar4 = "";
		} else {
			$objResponse->script("
			byId('trCuotasFinanciar2').style.display = '';
			byId('trCuotasFinanciar3').style.display = '';
			byId('trCuotasFinanciar4').style.display = '';
			
			byId('tdFinanciamiento').rowSpan = '4';");
			
			$lstMesesFinanciar = "<table border=\"0\">".
				"<tr align=\"right\">".
					"<td><input type=\"text\" id=\"lstMesesFinanciar\" name=\"lstMesesFinanciar\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right; width:40px;\" value=\"".($arrayFinal['lstMesesFinanciar'])."\"/></td>".
					"<td>"." Meses"."</td>".
					"<td>"."&nbsp;/&nbsp;"."</td>".
					"<td><input type=\"text\" id=\"txtInteresCuotaFinanciar\" name=\"txtInteresCuotaFinanciar\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right; width:60px;\" value=\"".($arrayFinal['txtInteresCuotaFinanciar'])."\"/></td>".
					"<td>"."%"."</td>".
				"</tr>".
				"<tr align=\"right\">".
					"<td colspan=\"2\">Fecha Pago:</td>".
					"<td colspan=\"3\">"."<input type=\"text\" id=\"txtFechaCuotaFinanciar\" name=\"txtFechaCuotaFinanciar\" autocomplete=\"off\" class=\"inputHabilitado\" size=\"10\" style=\"text-align:center\" value=\"".(($arrayFinal['txtFechaCuotaFinanciar'] != "") ? date(spanDateFormat, strtotime($arrayFinal['txtFechaCuotaFinanciar'])) : "")."\"/>"."</td>".
				"</tr>".
				"</table>";
			
			$lstMesesFinanciar2 = "<table border=\"0\">".
				"<tr>".
					"<td><input type=\"text\" id=\"lstMesesFinanciar2\" name=\"lstMesesFinanciar2\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right; width:40px;\" value=\"".($arrayFinal['lstMesesFinanciar2'])."\"/></td>".
					"<td>"." Meses"."</td>".
					"<td>"."&nbsp;/&nbsp;"."</td>".
					"<td><input type=\"text\" id=\"txtInteresCuotaFinanciar2\" name=\"txtInteresCuotaFinanciar2\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right; width:60px;\" value=\"".($arrayFinal['txtInteresCuotaFinanciar2'])."\"/></td>".
					"<td>"."%"."</td>".
				"</tr>".
				"<tr align=\"right\">".
					"<td colspan=\"2\">Fecha Pago:</td>".
					"<td colspan=\"3\">"."<input type=\"text\" id=\"txtFechaCuotaFinanciar2\" name=\"txtFechaCuotaFinanciar2\" autocomplete=\"off\" class=\"inputHabilitado\" size=\"10\" style=\"text-align:center\" value=\"".(($arrayFinal['txtFechaCuotaFinanciar2'] != "") ? date(spanDateFormat, strtotime($arrayFinal['txtFechaCuotaFinanciar2'])) : "")."\"/>"."</td>".
				"</tr>".
				"</table>";
			
			$lstMesesFinanciar3 = "<table border=\"0\">".
				"<tr>".
					"<td><input type=\"text\" id=\"lstMesesFinanciar3\" name=\"lstMesesFinanciar3\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right; width:40px;\" value=\"".($arrayFinal['lstMesesFinanciar3'])."\"/></td>".
					"<td>"." Meses"."</td>".
					"<td>"."&nbsp;/&nbsp;"."</td>".
					"<td><input type=\"text\" id=\"txtInteresCuotaFinanciar3\" name=\"txtInteresCuotaFinanciar3\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right; width:60px;\" value=\"".($arrayFinal['txtInteresCuotaFinanciar3'])."\"/></td>".
					"<td>"."%"."</td>".
				"</tr>".
				"<tr align=\"right\">".
					"<td colspan=\"2\">Fecha Pago:</td>".
					"<td colspan=\"3\">"."<input type=\"text\" id=\"txtFechaCuotaFinanciar3\" name=\"txtFechaCuotaFinanciar3\" autocomplete=\"off\" class=\"inputHabilitado\" size=\"10\" style=\"text-align:center\" value=\"".(($arrayFinal['txtFechaCuotaFinanciar3'] != "") ? date(spanDateFormat, strtotime($arrayFinal['txtFechaCuotaFinanciar3'])) : "")."\"/>"."</td>".
				"</tr>".
				"</table>";
			
			$lstMesesFinanciar4 = "<table border=\"0\">".
				"<tr>".
					"<td><input type=\"text\" id=\"lstMesesFinanciar4\" name=\"lstMesesFinanciar4\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right; width:40px;\" value=\"".($arrayFinal['lstMesesFinanciar4'])."\"/></td>".
					"<td>"." Meses"."</td>".
					"<td>"."&nbsp;/&nbsp;"."</td>".
					"<td><input type=\"text\" id=\"txtInteresCuotaFinanciar4\" name=\"txtInteresCuotaFinanciar4\" class=\"inputHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right; width:60px;\" value=\"".($arrayFinal['txtInteresCuotaFinanciar4'])."\"/></td>".
					"<td>"."%"."</td>".
				"</tr>".
				"<tr align=\"right\">".
					"<td colspan=\"2\">Fecha Pago:</td>".
					"<td colspan=\"3\">"."<input type=\"text\" id=\"txtFechaCuotaFinanciar4\" name=\"txtFechaCuotaFinanciar4\" autocomplete=\"off\" class=\"inputHabilitado\" size=\"10\" style=\"text-align:center\" value=\"".(($arrayFinal['txtFechaCuotaFinanciar4'] != "") ? date(spanDateFormat, strtotime($arrayFinal['txtFechaCuotaFinanciar4'])) : "")."\"/>"."</td>".
				"</tr>".
				"</table>";
		}
		$factores[0] = " ";
	}
	
	/*$objResponse->assign("txtMontoSeguro","value",numformat(floatval($rowBanco['porcentaje_flat']),2,'.',','));
	$objResponse->assign("txtInicialPoliza","value",numformat(floatval($rowBanco[1]),2,'.',','));*/
	$objResponse->assign("porcentaje_flat","value",$rowBanco['porcentaje_flat']);
	
	$objResponse->assign("capaporcentaje_flat","innerHTML",$rowBanco['porcentaje_flat']);
	$objResponse->assign("capameses_financiar","innerHTML",$lstMesesFinanciar);
	$objResponse->assign("capameses_financiar2","innerHTML",$lstMesesFinanciar2);
	$objResponse->assign("capameses_financiar3","innerHTML",$lstMesesFinanciar3);
	$objResponse->assign("capameses_financiar4","innerHTML",$lstMesesFinanciar4);
	if ($totalRowsBanco > 0) {
		if ($totalRowsFactor > 0) {
			$objResponse->assign("tdtxtCuotasFinanciar","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar\" name=\"txtCuotasFinanciar\" class=\"inputCompleto\" onchange=\"setformato(this);\" readonly=\"readonly\" style=\"border:0px; text-align:right;\"/>");
			$objResponse->assign("tdtxtCuotasFinanciar2","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar2\" name=\"txtCuotasFinanciar2\" class=\"inputCompleto\" onchange=\"setformato(this);\" readonly=\"readonly\" style=\"border:0px; text-align:right;\"/>");
			$objResponse->assign("tdtxtCuotasFinanciar3","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar3\" name=\"txtCuotasFinanciar3\" class=\"inputCompleto\" onchange=\"setformato(this);\" readonly=\"readonly\" style=\"border:0px; text-align:right;\"/>");
			$objResponse->assign("tdtxtCuotasFinanciar4","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar4\" name=\"txtCuotasFinanciar4\" class=\"inputCompleto\" onchange=\"setformato(this);\" readonly=\"readonly\" style=\"border:0px; text-align:right;\"/>");
		} else  {
			$objResponse->assign("tdtxtCuotasFinanciar","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar\" name=\"txtCuotasFinanciar\" class=\"inputCompletoHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right;\" value=\"".($arrayFinal['txtCuotasFinanciar'])."\"/>");
			$objResponse->assign("tdtxtCuotasFinanciar2","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar2\" name=\"txtCuotasFinanciar2\" class=\"inputCompletoHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right;\" value=\"".($arrayFinal['txtCuotasFinanciar2'])."\"/>");
			$objResponse->assign("tdtxtCuotasFinanciar3","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar3\" name=\"txtCuotasFinanciar3\" class=\"inputCompletoHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right;\" value=\"".($arrayFinal['txtCuotasFinanciar3'])."\"/>");
			$objResponse->assign("tdtxtCuotasFinanciar4","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar4\" name=\"txtCuotasFinanciar4\" class=\"inputCompletoHabilitado\" onchange=\"percent(); asignarPrecio(); setformato(this);\" onkeypress=\"return inputnum(event);\" style=\"text-align:right;\" value=\"".($arrayFinal['txtCuotasFinanciar4'])."\"/>");
		}
	} else {
		$objResponse->assign("tdtxtCuotasFinanciar","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar\" name=\"txtCuotasFinanciar\" class=\"inputCompleto\" onchange=\"setformato(this);\" readonly=\"readonly\" style=\"text-align:right;\" value=\"0.00\"/>");
		$objResponse->assign("tdtxtCuotasFinanciar2","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar2\" name=\"txtCuotasFinanciar2\" class=\"inputCompleto\" onchange=\"setformato(this);\" readonly=\"readonly\" style=\"text-align:right;\" value=\"0.00\"/>");
		$objResponse->assign("tdtxtCuotasFinanciar3","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar3\" name=\"txtCuotasFinanciar3\" class=\"inputCompleto\" onchange=\"setformato(this);\" readonly=\"readonly\" style=\"text-align:right;\" value=\"0.00\"/>");
		$objResponse->assign("tdtxtCuotasFinanciar4","innerHTML","<input type=\"text\" id=\"txtCuotasFinanciar4\" name=\"txtCuotasFinanciar4\" class=\"inputCompleto\" onchange=\"setformato(this);\" readonly=\"readonly\" style=\"text-align:right;\" value=\"0.00\"/>");
	}
	//$objResponse->assign("financiada","innerHTML",$rowBanco['nombreBanco']);
	
	$objResponse->script("
	factor={".$factores."};
	percent();");
	
	$objResponse->script("
	jQuery(function($){
		$(\"#txtFechaCuotaFinanciar\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaCuotaFinanciar2\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaCuotaFinanciar3\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
		$(\"#txtFechaCuotaFinanciar4\").maskInput(\"".spanDateMask."\",{placeholder:\" \"});
	});

	new JsDatePick({
		useMode:2,
		target:\"txtFechaCuotaFinanciar\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});

	new JsDatePick({
		useMode:2,
		target:\"txtFechaCuotaFinanciar2\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});

	new JsDatePick({
		useMode:2,
		target:\"txtFechaCuotaFinanciar3\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});

	new JsDatePick({
		useMode:2,
		target:\"txtFechaCuotaFinanciar4\",
		dateFormat:\"".spanDatePick."\", 
		cellColorScheme:\"orange\"
	});");
	
	return $objResponse;
}

function asignarPoliza($idPoliza) {
	$objResponse = new xajaxResponse();
	
	$queryPoliza = sprintf("SELECT * FROM an_poliza WHERE id_poliza = %s;",
		valTpDato($idPoliza, "int"));
	$rsPoliza = mysql_query($queryPoliza);
	if (!$rsPoliza) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPoliza = mysql_fetch_assoc($rsPoliza);
	
	$objResponse->assign("txtNombreAgenciaSeguro","value",$rowPoliza['nom_comp_seguro']);
	$objResponse->assign("txtDireccionAgenciaSeguro","value",$rowPoliza['dir_agencia']);
	$objResponse->assign("txtCiudadAgenciaSeguro","value",$rowPoliza['ciudad_agencia']);
	$objResponse->assign("txtPaisAgenciaSeguro","value",$rowPoliza['pais_agencia']);
	$objResponse->assign("txtTelefonoAgenciaSeguro","value",$rowPoliza['telf_agencia']);
	$objResponse->assign("txtMontoSeguro","value",number_format($rowPoliza['contado_poliza'], 2, ".", ","));
	$objResponse->assign("txtInicialPoliza","value",number_format($rowPoliza['inicial_poliza'], 2, ".", ","));
	$objResponse->assign("txtMesesPoliza","value",$rowPoliza['meses_poliza']);
	$objResponse->assign("txtCuotasPoliza","value",number_format($rowPoliza['cuotas_poliza'], 2, ".", ","));
	
	$objResponse->assign("cheque_poliza","value",$rowPoliza['cheque_poliza']);
	$objResponse->assign("financiada","value",$rowPoliza['financiada']);
	
	return $objResponse;
}

function asignarSinBancoFinanciar($frmPedido) {
	$objResponse = new xajaxResponse();
	
	if ($frmPedido['cbxSinBancoFinanciar'] == 1) {
		if ($frmPedido['hddSinBancoFinanciar'] == 1) {
			$objResponse->script("
			selectedOption('lstBancoFinanciar','-1');
			byId('lstBancoFinanciar').onchange();");
		} else {
			$objResponse->script("
			byId('cbxSinBancoFinanciar').checked = false;
			byId('aDesbloquearSinBancoFinanciar').click();");
		}
	}
	
	return $objResponse;
}

function asignarUnidadBasica($idUnidadBasica, $idEmpresa, $lstPrecioVenta) {
	$objResponse = new xajaxResponse();
	
	$queryUnidadBasica = sprintf("SELECT *,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		(SELECT des_version FROM an_version WHERE an_version.id_version = uni_bas.ver_uni_bas) AS desc_version
	FROM sa_unidad_empresa unidad_emp
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (unidad_emp.id_unidad_basica = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_uni_bas uni_bas ON (unidad_emp.id_unidad_basica = uni_bas.id_uni_bas)
		INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
	WHERE uni_bas.id_uni_bas = %s
		AND unidad_emp.id_empresa = %s;",
		valTpDato($idUnidadBasica,"int"),
		valTpDato($idEmpresa,"int"));
	$rsUnidadBasica = @mysql_query($queryUnidadBasica);
	if (!$rsUnidadBasica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowUnidadBasica = mysql_fetch_assoc($rsUnidadBasica);
	
	$objResponse->assign("tdUnidadBasica","innerHTML",htmlentities($rowUnidadBasica['vehiculo']));
	
	$html = "<select id=\"lstPrecioVenta\" name=\"lstPrecioVenta\" class=\"inputHabilitado\" onchange=\"asignarPrecio(this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
		$html .= "<option value=\"".$lstPrecioVenta."\">Sin Actualizar: (".number_format($lstPrecioVenta, 2, ".", ",").")</option>";
		$html .= "<option value=\"".$rowUnidadBasica['pvp_venta1']."\">Precio 1: (".number_format($rowUnidadBasica['pvp_venta1'], 2, ".", ",").")</option>";
		$html .= "<option value=\"".$rowUnidadBasica['pvp_venta2']."\">Precio 2: (".number_format($rowUnidadBasica['pvp_venta2'], 2, ".", ",").")</option>";
		$html .= "<option value=\"".$rowUnidadBasica['pvp_venta3']."\">Precio 3: (".number_format($rowUnidadBasica['pvp_venta3'], 2, ".", ",").")</option>";
	$html .= "</select>";
	$objResponse->assign("tdlstPrecioVenta","innerHTML",$html);
	
	return $objResponse;
}

function buscarUnidadFisica($frmPedido) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmPedido['txtIdEmpresa'],
		$frmPedido['txtIdUnidadBasica'],
		$frmPedido['txtIdUnidadFisica']);
	
	$objResponse->loadCommands(listaUnidadFisica(0, "CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)", "ASC", $valBusq));
	
	return $objResponse;
}

function cargaLstBancoFinanciar($selId = "", $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE nombreBanco <> '-'
	ORDER BY nombreBanco;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"xajax_asignarBanco(this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idBanco']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['idBanco']."\">".utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	$idModulo = (is_array($idModulo)) ? implode(",",$idModulo) : $idModulo;
	$idTipoClave = (is_array($idTipoClave)) ? implode(",",$idTipoClave) : $idTipoClave;
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Débito, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $claveFiltro = "") {
	$objResponse = new xajaxResponse();
	
	$claveFiltro = (is_array($claveFiltro)) ? implode(",",$claveFiltro) : $claveFiltro;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(empleado.activo = 1
	OR empleado.id_empleado = %s)",
		valTpDato($selId, "int"));
	
	if ($claveFiltro != "-1" && $claveFiltro != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("empleado.clave_filtro IN (%s)",
			valTpDato($claveFiltro, "campo"));
	}
	
	$query = sprintf("SELECT
		empleado.id_empleado,
		empleado.nombre_empleado,
		empleado.nombre_departamento,
		empleado.nombre_cargo,
		empleado.clave_filtro,
		empleado.nombre_filtro
	FROM vw_pg_empleados empleado %s
	ORDER BY empleado.nombre_empleado;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstPoliza($selId = "", $nombreObjeto = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_poliza WHERE estatus = 1
	ORDER BY nombre_poliza;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"xajax_asignarPoliza(this.value);\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_poliza']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_poliza']."\">".utf8_encode($row['nombre_poliza'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function formPedido($idEmpresa, $numeroPresupuesto, $idPresupuesto, $idPedido, $idFactura, $frmPedido) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	byId('trFrmPerdido').style.display = '';
	byId('trBuscarPerdido').style.display = 'none';");
	
	$objResponse->assign("txtIdFactura","value",$idFactura);
	
	if (($idPresupuesto > 0 || ($idEmpresa > 0 && $numeroPresupuesto > 0)) && !($idPedido > 0)) {
		$objResponse->script("byId('frmPedido').action = 'an_ventas_pedido_guardar.php';");
		
		// BUSCA LOS DATOS DEL PRESUPUESTO
		$queryPresupuesto = sprintf("SELECT
			pres_vent.id_presupuesto,
			pres_vent.numeracion_presupuesto,
			pres_vent.id_empresa,
			pres_vent.id_cliente,
			pres_vent.id_uni_bas,
			pres_vent.asesor_ventas,
			pres_vent.estado,
			pres_vent.fecha,
			pres_vent.precio_venta,
			pres_vent.monto_descuento,
			pres_vent.porcentaje_iva,
			pres_vent.porcentaje_impuesto_lujo,
			pres_vent.tipo_inicial,
			pres_vent.porcentaje_inicial,
			pres_vent.monto_inicial,
			pres_vent.id_banco_financiar,
			pres_vent.saldo_financiar,
			pres_vent.meses_financiar,
			pres_vent.interes_cuota_financiar,
			pres_vent.cuotas_financiar,
			pres_vent.meses_financiar2,
			pres_vent.interes_cuota_financiar2,
			pres_vent.cuotas_financiar2,
			pres_vent.total_accesorio,
			pres_vent.total_inicial_gastos,
			pres_vent.total_adicional_contrato,
			pres_vent.total_general,
			pres_vent.porcentaje_flat,
			pres_vent.monto_flat,
			pres_vent.empresa_accesorio,
			pres_vent.exacc1,
			pres_vent.exacc2,
			pres_vent.exacc3,
			pres_vent.exacc4,
			pres_vent.vexacc1,
			pres_vent.vexacc2,
			pres_vent.vexacc3,
			pres_vent.vexacc4,
			pres_vent.id_poliza,
			pres_vent.monto_seguro,
			pres_vent.periodo_poliza,
			pres_vent.contado_poliza,
			pres_vent.inicial_poliza,
			pres_vent.meses_poliza,
			pres_vent.cuotas_poliza,
			pres_vent.observacion
		FROM an_presupuesto pres_vent
		WHERE pres_vent.id_presupuesto = %s
			OR (pres_vent.id_empresa = %s
				AND pres_vent.numeracion_presupuesto LIKE %s);",
			valTpDato($idPresupuesto, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($numeroPresupuesto, "text"));
		$rsPresupuesto = mysql_query($queryPresupuesto);
		if (!$rsPresupuesto) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowPresupuesto = mysql_fetch_assoc($rsPresupuesto);
		
		$idEmpresa = $rowPresupuesto['id_empresa'];
		$idPresupuesto = $rowPresupuesto['id_presupuesto'];
		$idCliente = $rowPresupuesto['id_cliente'];
		$idUnidadBasica = $rowPresupuesto['id_uni_bas'];
		
		// BUSCA LOS DATOS DE LA EMPRESA
		$queryEmp = sprintf("SELECT *,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		WHERE vw_iv_emp_suc.id_empresa_reg = %s",
			valTpDato($idEmpresa, "int"));
		$rsEmp = mysql_query($queryEmp);
		if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowEmp = mysql_fetch_assoc($rsEmp);
		
		if ($rowPresupuesto['estado'] == 3) {
			return $objResponse->script("
			alert('El presupuesto ".$numeroPresupuesto." está desautorizado');
			window.location = 'an_ventas_pedido_insertar.php';");
		}
		
		if (getmysql(sprintf("SELECT COUNT(*) FROM an_unidad_fisica uni_fis
							WHERE uni_fis.id_uni_bas = %s
								AND uni_fis.estado_venta IN ('POR REGISTRAR','DISPONIBLE')
								AND uni_fis.propiedad = 'PROPIO';",
								valTpDato($idUnidadBasica, "int"))) == 0) {
			return $objResponse->script("
			alert('No existen unidades físicas disponibles para el presupuesto: ".$rowPresupuesto['numeracion_presupuesto']."');
			window.location = 'an_ventas_pedido_insertar.php';");
		}
		
		if (getmysql(sprintf("SELECT id_pedido FROM an_pedido WHERE id_presupuesto = %s;", valTpDato($idPresupuesto, "int"))) > 0) {
			return $objResponse->script("
			alert('El pedido del presupuesto ".$rowPresupuesto['numeracion_presupuesto']." ya ha sido generado');
			window.location = 'an_ventas_pedido_editar.php?view=import&id=".$idPresupuesto."';");
		}
		
		$txtPrecioBase = $rowPresupuesto['precio_venta'];
		$txtDescuento = $rowPresupuesto['monto_descuento'];
		$txtPorcIva = $rowPresupuesto['porcentaje_iva'];
		$txtPorcIvaLujo = $rowPresupuesto['porcentaje_impuesto_lujo'];

		// BUSCA LOS DATOS DEL CLIENTE
		$queryCliente = sprintf("SELECT
			cliente.id,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			cliente.nombre,
			cliente.apellido,
			CONCAT_WS(': ', CONCAT_WS('-', cliente.lci, cliente.ci), CONCAT_WS(' ', cliente.nombre, cliente.apellido)) AS nombre_cliente,
			cliente.telf,
			cliente.direccion,
			cliente.correo,
			cliente.ciudad,
			cliente.otrotelf,
			IF(cliente.tipo = 'Natural', IF(perfil_prospecto.sexo = 'M', 'Masculino', 'Femenino'),'') AS sexo_cliente,
			cliente.reputacionCliente + 0 AS id_reputacion_cliente,
			cliente.reputacionCliente,
			cliente.tipo_cuenta_cliente,
			cliente.tipo,
			cliente.paga_impuesto
		FROM cj_cc_cliente cliente
			LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado)
		WHERE cliente.id = %s;",
			valTpDato($idCliente, "int"));
		$rsCliente = mysql_query($queryCliente);
		if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowCliente = mysql_fetch_assoc($rsCliente);
		
		$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
		$objResponse->assign("capadatoscliente","innerHTML",utf8_encode($rowCliente['nombre_cliente']));
		$hddPagaImpuesto = ($rowCliente['paga_impuesto']);
		$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0 && $totalRowsCliente > 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));

		$most = "false";
		$tipoCuentaCliente = $rowCliente['tipo_cuenta_cliente'];
		if ($tipoCuentaCliente == 1) {
			$rep_val = '#FFFFCC'; $rep_tipo = $rowCliente['reputacionCliente']; $most = "true";
			return $objResponse->script("
			alert('El Prospecto perteneciente a este Presupuesto, no está Aprobado como Cliente. Recomendamos lo apruebe en la pantalla de Prospectación, para así generar dicho Presupuesto');
			window.location = 'an_ventas_pedido_insertar.php';");
		} else {
			switch ($rowCliente['id_reputacion_cliente']) {
				case 1 : $rep_val = '#FFEEEE'; $rep_tipo = $rowCliente['reputacionCliente']; $most = "true"; break;
				case 2 : $rep_val = '#DDEEFF'; $rep_tipo = $rowCliente['reputacionCliente']; break;
				case 3 : $rep_val = '#E6FFE6'; $rep_tipo = $rowCliente['reputacionCliente']; break;
			}
		}
		$objResponse->call(reputacion,$rep_val,$rep_tipo,$most,$tipoCuentaCliente);
		
		// VERIFICA SI TIENE IMPUESTO
		if (getmysql("SELECT UPPER(isan_uni_bas) FROM an_uni_bas WHERE id_uni_bas = ".valTpDato($idUnidadBasica,"int").";") == 1 && $hddPagaImpuesto == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (6)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			$txtNuevoPorcIva = $row['iva'];
			$cond = (strlen($eviva) > 0) ? " e " : "";
			$eviva .= $cond.$row['observacion'];
		} else {
			$txtNuevoPorcIva = 0;
			$eviva .= "Exento";
		}
		
		if (getmysql("SELECT impuesto_lujo FROM an_uni_bas WHERE id_uni_bas = ".valTpDato($idUnidadBasica,"int").";") == 1 && $hddPagaImpuesto == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (2)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			$txtNuevoPorcIvaLujo = $row['iva'];
			$cond = (strlen($eviva) > 0) ? " e " : "";
			$eviva .= $cond.$row['observacion'];
		} else {
			$txtNuevoPorcIvaLujo = 0;
		}
		
		if ($idUnidadBasica > 0) {
			if ($txtPorcIva != $txtNuevoPorcIva) {
				$txtPorcIva = $txtNuevoPorcIva;
				$arrayMsg[] = "Se ha actualizado el Impuesto en: ".$txtPorcIva."%";
			}
			if ($txtPorcIvaLujo != $txtNuevoPorcIvaLujo) {
				$txtPorcIvaLujo = $txtNuevoPorcIvaLujo;
				$arrayMsg[] = "Se ha actualizado el Impuesto al lujo en: ".$txtPorcIvaLujo."%";
			}
			(count($arrayMsg) > 0) ? $objResponse->alert(implode($arrayMsg,"\n")) : "";
		}
		
		$txtSubTotalIva = (($txtPrecioBase - $txtDescuento) * $txtPorcIva) / 100;
		$txtSubTotalIvaLujo = (($txtPrecioBase - $txtDescuento) * $txtPorcIvaLujo) / 100;
		$txtMontoImpuesto = $txtSubTotalIva + $txtSubTotalIvaLujo;
		$txtPrecioVenta = ($txtPrecioBase - $txtDescuento) + $txtMontoImpuesto;
		
		// ADICIONALES SIMPLES
		$sqla = sprintf("SELECT
			acc_pres.id_accesorio_presupuesto,
			acc_pres.id_presupuesto,
			acc_pres.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc.id_tipo_accesorio,
			(CASE acc.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			acc_pres.iva_accesorio,
			acc_pres.porcentaje_iva_accesorio,
			(acc_pres.precio_accesorio + (acc_pres.precio_accesorio * acc_pres.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_pres.costo_accesorio
		FROM an_accesorio_presupuesto acc_pres
			INNER JOIN an_accesorio acc ON (acc_pres.id_accesorio = acc.id_accesorio)
		WHERE acc_pres.id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int"));
		$ra = mysql_query($sqla);
		if (!$ra) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowa = mysql_fetch_assoc($ra)) {
			$objResponse->script(sprintf("newacc('acc%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '', '');",
				$rowa['id_accesorio'],
				"",
				$rowa['precio_con_iva'],
				utf8_encode($rowa['nom_accesorio']),
				"1",
				$rowa['iva_accesorio'],
				$rowa['costo_accesorio'],
				$rowa['porcentaje_iva_accesorio'],
				$rowa['id_tipo_accesorio']));
		}
		
		// ADICIONALES POR PAQUETE
		$sqlp = sprintf("SELECT
			paq_pres.id_paquete_presupuesto,
			paq_pres.id_presupuesto,
			paq_pres.id_acc_paq,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (paq_pres.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc.id_tipo_accesorio,
			(CASE acc.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			paq_pres.iva_accesorio,
			paq_pres.porcentaje_iva_accesorio,
			(paq_pres.precio_accesorio + (paq_pres.precio_accesorio * paq_pres.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			paq_pres.costo_accesorio
		FROM an_paquete_presupuesto paq_pres
			INNER JOIN an_acc_paq acc_paq ON (paq_pres.id_acc_paq = acc_paq.id_acc_paq)
			INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
		WHERE paq_pres.id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int"));
		$rp = mysql_query($sqlp);
		if (!$rp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowa = mysql_fetch_assoc($rp)) {
			$objResponse->script(sprintf("newacc('acc%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '', '');",
				$rowa['id_accesorio'],
				$rowa['id_acc_paq'],
				$rowa['precio_con_iva'],
				utf8_encode($rowa['nom_accesorio']),
				"1",
				$rowa['iva_accesorio'],
				$rowa['costo_accesorio'],
				$rowa['porcentaje_iva_accesorio'],
				$rowa['id_tipo_accesorio']));
		}
		
		// DATOS DEL PEDIDO
		$objResponse->assign("txtIdPedido","value",$rowPresupuesto['id_pedido']);
		$objResponse->assign("txtNumeroPedido","value",$rowPresupuesto['numeracion_pedido']);
		$objResponse->assign("txtIdPresupuesto","value",$rowPresupuesto['id_presupuesto']);
		$objResponse->assign("txtNumeroPresupuesto","value",$rowPresupuesto['numeracion_presupuesto']);
		$objResponse->assign("txtIdEmpresa","value",$rowEmp['id_empresa_reg']);
		$objResponse->assign("txtEmpresa","value",$rowEmp['nombre_empresa']);
		
		$objResponse->loadCommands(cargaLstEmpleado($rowPresupuesto['asesor_ventas'], "lstAsesorVenta", "1,2"));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", "3", "-1", "1", $rowPresupuesto['id_clave_movimiento']));
		
		$objResponse->assign("txtIdUnidadBasica","value",$idUnidadBasica);
		$objResponse->loadCommands(asignarUnidadBasica($idUnidadBasica, $idEmpresa, $rowPresupuesto['precio_venta']));
		
		if ($idUnidadBasica > 0) {
			$valBusq = sprintf("%s|%s|%s",
				$idEmpresa,
				$idUnidadBasica,
				$idUnidadFisica);
			
			$objResponse->loadCommands(listaUnidadFisica(0, "CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)", "ASC", $valBusq));
		}
		
		// VENTA DE LA UNIDAD
		$objResponse->assign("txtPrecioBase","value",number_format($rowPresupuesto['precio_venta'], 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($rowPresupuesto['monto_descuento'], 2, ".", ","));
		$objResponse->assign("porcentaje_iva","value",number_format($txtPorcIva, 2, ".", ","));
		$objResponse->assign("porcentaje_impuesto_lujo","value",number_format($txtPorcIvaLujo, 2, ".", ","));
		$objResponse->assign("eviva","innerHTML",((strlen($eviva) > 0) ? "(".$eviva.")" : ""));
		$objResponse->assign("txtPrecioVenta","value",number_format($txtPrecioVenta, 2, ".", ","));
		
		$objResponse->assign("hddTipoInicial","value",$rowPresupuesto['tipo_inicial']);
		$objResponse->assign("txtPorcInicial","value",number_format($rowPresupuesto['porcentaje_inicial'], 2, ".", ","));
		$objResponse->assign("txtMontoInicial","value",number_format($rowPresupuesto['monto_inicial'], 2, ".", ","));
		
		$objResponse->assign("txtTotalInicialGastos","value",number_format($rowPresupuesto['total_inicial_gastos'], 2, ".", ","));
		$objResponse->assign("txtSaldoFinanciar","value",number_format($rowPresupuesto['saldo_financiar'], 2, ".", ","));
		$objResponse->assign("txtTotalAdicionalContrato","value",number_format($rowPresupuesto['total_adicional_contrato'], 2, ".", ","));
		
		// BUSCA LOS DATOS DEL PRESUPUESTO DE ACCESORIOS
		$queryPresupuestoAccesorio = sprintf("SELECT * FROM an_presupuesto_accesorio pres_acc WHERE pres_acc.id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int"));
		$rsPresupuestoAccesorio = mysql_query($queryPresupuestoAccesorio);
		if (!$rsPresupuestoAccesorio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowPresupuestoAccesorio = mysql_fetch_assoc($rsPresupuestoAccesorio);
		
		// PRESUPUESTO ACCESORIOS
		$objResponse->assign("vexacc1","value",number_format($rowPresupuestoAccesorio['subtotal'], 2, ".", ","));
		$objResponse->assign("txtTotalImpuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
		$objResponse->assign("txtTotalPresupuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal'] + $rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
		
		$objResponse->assign("exacc2","value",$rowPresupuesto['exacc2']);
		$objResponse->assign("vexacc2","value",number_format($rowPresupuesto['vexacc2'], 2, ".", ","));
		$objResponse->assign("exacc3","value",$rowPresupuesto['exacc3']);
		$objResponse->assign("vexacc3","value",number_format($rowPresupuesto['vexacc3'], 2, ".", ","));
		$objResponse->assign("exacc4","value",$rowPresupuesto['exacc4']);
		$objResponse->assign("vexacc4","value",number_format($rowPresupuesto['vexacc4'], 2, ".", ","));
		$objResponse->assign("txtTotalAccesorio","value",number_format($rowPresupuesto['total_accesorio'], 2, ".", ","));
		$objResponse->assign("empresa_accesorio","value",$rowPresupuesto['empresa_accesorio']);
		
		// FORMA DE PAGO
		$objResponse->assign("txtMontoAnticipo","value",number_format($rowPresupuesto['anticipo'], 2, ".", ","));
		$objResponse->assign("txtMontoComplementoInicial","value",number_format($rowPresupuesto['complemento_inicial'], 2, ".", ","));
		
		// FINANCIAMIENTO
		$objResponse->loadCommands(cargaLstBancoFinanciar($rowPresupuesto['id_banco_financiar'], "lstBancoFinanciar"));
		$valores = array(
			"lstMesesFinanciar*".$rowPresupuesto['meses_financiar'],
			"txtInteresCuotaFinanciar*".$rowPresupuesto['interes_cuota_financiar'],
			"txtCuotasFinanciar*".$rowPresupuesto['cuotas_financiar'],
			"txtFechaCuotaFinanciar*".$rowPresupuesto['fecha_pago_cuota'],
			"lstMesesFinanciar2*".$rowPresupuesto['meses_financiar2'],
			"txtInteresCuotaFinanciar2*".$rowPresupuesto['interes_cuota_financiar2'],
			"txtCuotasFinanciar2*".$rowPresupuesto['cuotas_financiar2'],
			"txtFechaCuotaFinanciar2*".$rowPresupuesto['fecha_pago_cuota2'],
			"lstMesesFinanciar3*".$rowPresupuesto['meses_financiar3'],
			"txtInteresCuotaFinanciar3*".$rowPresupuesto['interes_cuota_financiar3'],
			"txtCuotasFinanciar3*".$rowPresupuesto['cuotas_financiar3'],
			"txtFechaCuotaFinanciar3*".$rowPresupuesto['fecha_pago_cuota3'],
			"lstMesesFinanciar4*".$rowPresupuesto['meses_financiar4'],
			"txtInteresCuotaFinanciar4*".$rowPresupuesto['interes_cuota_financiar4'],
			"txtCuotasFinanciar4*".$rowPresupuesto['cuotas_financiar4'],
			"txtFechaCuotaFinanciar4*".$rowPresupuesto['fecha_pago_cuota4']);
		$objResponse->loadCommands(asignarBanco($rowPresupuesto['id_banco_financiar'], implode("|",$valores)));
		$objResponse->assign("txtCuotasFinanciar","value",number_format($rowPresupuesto['cuotas_financiar'], 2, ".", ","));
		$objResponse->assign("txtCuotasFinanciar2","value",number_format($rowPresupuesto['cuotas_financiar2'], 2, ".", ","));
		$objResponse->assign("txtCuotasFinanciar3","value",number_format($rowPresupuesto['cuotas_financiar3'], 2, ".", ","));
		$objResponse->assign("txtCuotasFinanciar4","value",number_format($rowPresupuesto['cuotas_financiar4'], 2, ".", ","));
		$objResponse->assign("porcentaje_flat","value",$rowPresupuesto['porcentaje_flat']);
		$objResponse->assign("capaporcentaje_flat","innerHTML",number_format($rowPresupuesto['porcentaje_flat'], 2, ".", ","));
		$objResponse->assign("txtMontoFLAT","value",number_format($rowPresupuesto['monto_flat'], 2, ".", ","));
		
		$objResponse->assign("txtPrecioTotal","value",number_format($rowPresupuesto['forma_pago_precio_total'], 2, ".", ","));
		
		// SEGURO
		$objResponse->loadCommands(cargaLstPoliza($rowPresupuesto['id_poliza'], "lstPoliza"));
		$objResponse->assign("txtNombreAgenciaSeguro","value",$rowPresupuesto['nombre_agencia_seguro']);
		$objResponse->assign("txtDireccionAgenciaSeguro","value",$rowPresupuesto['direccion_agencia_seguro']);
		$objResponse->assign("txtCiudadAgenciaSeguro","value",$rowPresupuesto['ciudad_agencia_seguro']);
		$objResponse->assign("txtPaisAgenciaSeguro","value",$rowPresupuesto['pais_agencia_seguro']);
		$objResponse->assign("txtTelefonoAgenciaSeguro","value",$rowPresupuesto['telefono_agencia_seguro']);
		$objResponse->assign("txtNumPoliza","value",$rowPresupuesto['num_poliza']);
		$objResponse->assign("txtMontoSeguro","value",number_format($rowPresupuesto['monto_seguro'], 2, ".", ","));
		$objResponse->assign("txtPeriodoPoliza","value",$rowPresupuesto['periodo_poliza']);
		$objResponse->assign("txtDeduciblePoliza","value",$rowPresupuesto['ded_poliza']);
		$objResponse->assign("txtFechaEfect","value",(($rowPresupuesto['fech_efect'] != "") ? date(spanDateFormat, strtotime($rowPresupuesto['fech_efect'])) : ""));
		$objResponse->assign("txtFechaExpi","value",(($rowPresupuesto['fech_expira'] != "") ? date(spanDateFormat, strtotime($rowPresupuesto['fech_expira'])) : ""));
		$objResponse->assign("txtInicialPoliza","value",number_format($rowPresupuesto['inicial_poliza'], 2, ".", ","));
		$objResponse->assign("txtMesesPoliza","value",$rowPresupuesto['meses_poliza']);
		$objResponse->assign("txtCuotasPoliza","value",number_format($rowPresupuesto['cuotas_poliza'], 2, ".", ","));
		
		$objResponse->assign("observaciones","innerHTML",$rowPresupuesto['observacion']);
		
		$objResponse->loadCommands(cargaLstEmpleado("", "lstGerenteVenta", ((in_array(idArrayPais, array(2))) ? "2" : "2,300")));
		$objResponse->loadCommands(cargaLstEmpleado("", "lstGerenteAdministracion", "3"));
		
		$objResponse->call(asignarPrecio);

	} else if ($idPedido > 0) {
		$objResponse->script("byId('frmPedido').action = 'an_ventas_pedido_guardar.php';");
		
		// BUSCA LOS DATOS DE LA FACTURA DE IMPORTACION PARA SABER EL SALDO POR PAGAR
		$queryPedido = sprintf("SELECT
			ped_vent.id_pedido,
			ped_vent.numeracion_pedido,
			ped_vent.id_empresa,
			ped_vent.id_cliente,
			ped_vent.id_factura_cxc,
			ped_vent.id_presupuesto,
			pres_vent.numeracion_presupuesto,
			ped_vent.id_clave_movimiento,
			ped_vent.id_unidad_fisica,
			uni_fis.id_uni_bas,
			ped_vent.fecha,
			ped_vent.gerente_ventas,
			ped_vent.fecha_gerente_ventas,
			ped_vent.administracion,
			ped_vent.fecha_administracion,
			ped_vent.precio_retoma,
			ped_vent.fecha_retoma,
			ped_vent.estado_pedido,
			ped_vent.precio_venta,
			ped_vent.monto_descuento,
			ped_vent.tipo_inicial,
			ped_vent.porcentaje_inicial,
			ped_vent.inicial,
			
			ped_vent.saldo_financiar,
			ped_vent.meses_financiar,
			ped_vent.interes_cuota_financiar,
			ped_vent.cuotas_financiar,
			ped_vent.fecha_pago_cuota,
			ped_vent.meses_financiar2,
			ped_vent.interes_cuota_financiar2,
			ped_vent.cuotas_financiar2,
			ped_vent.fecha_pago_cuota2,
			ped_vent.meses_financiar3,
			ped_vent.interes_cuota_financiar3,
			ped_vent.cuotas_financiar3,
			ped_vent.fecha_pago_cuota3,
			ped_vent.meses_financiar4,
			ped_vent.interes_cuota_financiar4,
			ped_vent.cuotas_financiar4,
			ped_vent.fecha_pago_cuota4,
			ped_vent.id_banco_financiar,
			
			ped_vent.total_inicial_gastos,
			ped_vent.total_adicional_contrato,
			ped_vent.monto_flat,
			ped_vent.total_accesorio,
			ped_vent.observaciones,
			ped_vent.asesor_ventas,
			ped_vent.anticipo,
			ped_vent.complemento_inicial,
			ped_vent.forma_pago_precio_total,
			
			ped_vent.id_poliza,
			ped_vent.num_poliza,
			ped_vent.monto_seguro,
			ped_vent.periodo_poliza,
			ped_vent.ded_poliza,
			ped_vent.fech_expira,
			ped_vent.fech_efect,
			ped_vent.inicial_poliza,
			ped_vent.meses_poliza,
			ped_vent.cuotas_poliza,
			
			ped_vent.fecha_reserva_venta,
			ped_vent.fecha_entrega,
			ped_vent.total_pedido,
			ped_vent.porcentaje_iva,
			ped_vent.porcentaje_impuesto_lujo,
			ped_vent.porcentaje_flat,
			ped_vent.exacc1,
			ped_vent.exacc2,
			ped_vent.exacc3,
			ped_vent.exacc4,
			ped_vent.vexacc1,
			ped_vent.vexacc2,
			ped_vent.vexacc3,
			ped_vent.vexacc4,
			ped_vent.empresa_accesorio,
			adicional_contrato.nombre_agencia_seguro,
			adicional_contrato.direccion_agencia_seguro,
			adicional_contrato.ciudad_agencia_seguro,
			adicional_contrato.pais_agencia_seguro,
			adicional_contrato.telefono_agencia_seguro
		FROM an_pedido ped_vent
			LEFT JOIN an_adicionales_contrato adicional_contrato ON (ped_vent.id_pedido = adicional_contrato.id_pedido)
			LEFT JOIN an_presupuesto pres_vent ON (ped_vent.id_presupuesto = pres_vent.id_presupuesto)
			LEFT JOIN an_unidad_fisica uni_fis ON (ped_vent.id_unidad_fisica = uni_fis.id_unidad_fisica)
		WHERE ped_vent.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$rsPedido = mysql_query($queryPedido);
		if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowPedido = mysql_fetch_assoc($rsPedido);
		
		$idEmpresa = $rowPedido['id_empresa'];
		$idPresupuesto = $rowPedido['id_presupuesto'];
		$idFactura = ($idFactura > 0) ? $idFactura : $rowPedido['id_factura_cxc'];
		$idCliente = $rowPedido['id_cliente'];
		$idUnidadBasica = $rowPedido['id_uni_bas'];
		$idUnidadFisica = $rowPedido['id_unidad_fisica'];
		
		// BUSCA LOS DATOS DE LA EMPRESA
		$queryEmp = sprintf("SELECT *,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		WHERE vw_iv_emp_suc.id_empresa_reg = %s",
			valTpDato($idEmpresa, "int"));
		$rsEmp = mysql_query($queryEmp);
		if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowEmp = mysql_fetch_assoc($rsEmp);
		
		if ($rowPedido['estado_pedido'] == 3) {
			$objResponse->script("
			alert('El pedido ".$rowPedido['numeracion_pedido']." está desautorizado');");
		}
		
		$txtPrecioBase = $rowPedido['precio_venta'];
		$txtDescuento = $rowPedido['monto_descuento'];
		$txtPorcIva = $rowPedido['porcentaje_iva'];
		$txtPorcIvaLujo = $rowPedido['porcentaje_impuesto_lujo'];

		// BUSCA LOS DATOS DEL CLIENTE
		$queryCliente = sprintf("SELECT
			cliente.id,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			cliente.nombre,
			cliente.apellido,
			CONCAT_WS(': ', CONCAT_WS('-', cliente.lci, cliente.ci), CONCAT_WS(' ', cliente.nombre, cliente.apellido)) AS nombre_cliente,
			cliente.telf,
			cliente.direccion,
			cliente.correo,
			cliente.ciudad,
			cliente.otrotelf,
			IF(cliente.tipo = 'Natural', IF(perfil_prospecto.sexo = 'M', 'Masculino', 'Femenino'),'') AS sexo_cliente,
			cliente.reputacionCliente + 0 AS id_reputacion_cliente,
			cliente.reputacionCliente,
			cliente.tipo_cuenta_cliente,
			cliente.tipo,
			cliente.paga_impuesto
		FROM cj_cc_cliente cliente
			LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado)
		WHERE cliente.id = %s;",
			valTpDato($idCliente, "int"));
		$rsCliente = mysql_query($queryCliente);
		if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowCliente = mysql_fetch_assoc($rsCliente);
		
		$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
		$objResponse->assign("capadatoscliente","innerHTML",utf8_encode($rowCliente['nombre_cliente']));
		$hddPagaImpuesto = ($rowCliente['paga_impuesto']);
		$objResponse->assign("tdMsjCliente","innerHTML",(($rowCliente['paga_impuesto'] == 0 && $totalRowsCliente > 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente Exento y/o Exonerado</div>" : ""));

		$most = "false";
		$tipoCuentaCliente = $rowCliente['tipo_cuenta_cliente'];
		if ($tipoCuentaCliente == 1) {
			$rep_val = '#FFFFCC'; $rep_tipo = $rowCliente['reputacionCliente']; $most = "true";
		} else {
			switch ($rowCliente['id_reputacion_cliente']) {
				case 1 : $rep_val = '#FFEEEE'; $rep_tipo = $rowCliente['reputacionCliente']; $most = "true"; break;
				case 2 : $rep_val = '#DDEEFF'; $rep_tipo = $rowCliente['reputacionCliente']; break;
				case 3 : $rep_val = '#E6FFE6'; $rep_tipo = $rowCliente['reputacionCliente']; break;
			}
		}
		$objResponse->call(reputacion,$rep_val,$rep_tipo,$most,$tipoCuentaCliente);
		
		// VERIFICA SI TIENE IMPUESTO
		if (getmysql("SELECT UPPER(isan_uni_bas)
		FROM an_uni_bas
			INNER JOIN an_unidad_fisica ON (an_uni_bas.id_uni_bas = an_unidad_fisica.id_uni_bas)
		WHERE id_unidad_fisica = ".valTpDato($idUnidadFisica,"int").";") == 1 && $hddPagaImpuesto == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (6)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			$txtNuevoPorcIva = $row['iva'];
			$cond = (strlen($eviva) > 0) ? " e " : "";
			$eviva .= $cond.$row['observacion'];
		} else {
			$txtNuevoPorcIva = 0;
			$eviva .= "Exento";
		}
		
		if (getmysql("SELECT impuesto_lujo
		FROM an_uni_bas
			INNER JOIN an_unidad_fisica ON (an_uni_bas.id_uni_bas = an_unidad_fisica.id_uni_bas)
		WHERE id_unidad_fisica = ".valTpDato($idUnidadFisica,"int").";") == 1 && $hddPagaImpuesto == 1) {
			$query = sprintf("SELECT
				iva.iva,
				iva.observacion
			FROM an_unidad_basica_impuesto uni_bas_impuesto
				INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
			WHERE uni_bas_impuesto.id_unidad_basica = %s
				AND iva.tipo IN (2)
				AND uni_bas_impuesto.id_impuesto NOT IN (SELECT cliente_imp_exento.id_impuesto FROM cj_cc_cliente_impuesto_exento cliente_imp_exento
														WHERE cliente_imp_exento.id_cliente = %s);",
				valTpDato($idUnidadBasica, "int"),
				valTpDato($idCliente, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			$txtNuevoPorcIvaLujo = $row['iva'];
			$cond = (strlen($eviva) > 0) ? " e " : "";
			$eviva .= $cond.$row['observacion'];
		} else {
			$txtNuevoPorcIvaLujo = 0;
		}
		
		if ($idUnidadBasica > 0 && $idUnidadFisica > 0) {
			if ($txtPorcIva != $txtNuevoPorcIva) {
				$txtPorcIva = $txtNuevoPorcIva;
				$arrayMsg[] = "Se ha actualizado el Impuesto en: ".$txtPorcIva."%";
			}
			if ($txtPorcIvaLujo != $txtNuevoPorcIvaLujo) {
				$txtPorcIvaLujo = $txtNuevoPorcIvaLujo;
				$arrayMsg[] = "Se ha actualizado el Impuesto al lujo en: ".$txtPorcIvaLujo."%";
			}
			(count($arrayMsg) > 0) ? $objResponse->alert(implode($arrayMsg,"\n")) : "";
		}
		
		$txtSubTotalIva = (($txtPrecioBase - $txtDescuento) * $txtPorcIva) / 100;
		$txtSubTotalIvaLujo = (($txtPrecioBase - $txtDescuento) * $txtPorcIvaLujo) / 100;
		$txtMontoImpuesto = $txtSubTotalIva + $txtSubTotalIvaLujo;
		$txtPrecioVenta = ($txtPrecioBase - $txtDescuento) + $txtMontoImpuesto;
		
		// ADICIONALES SIMPLES
		$sqla = sprintf("SELECT
			acc_ped.id_accesorio_pedido,
			acc_ped.id_pedido,
			acc_ped.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (acc_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			acc_ped.id_tipo_accesorio,
			(CASE acc_ped.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			acc_ped.iva_accesorio,
			acc_ped.porcentaje_iva_accesorio,
			(acc_ped.precio_accesorio + (acc_ped.precio_accesorio * acc_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			acc_ped.costo_accesorio,
			acc_ped.id_condicion_pago,
			acc_ped.id_condicion_mostrar
		FROM an_accesorio_pedido acc_ped
			INNER JOIN an_accesorio acc ON (acc_ped.id_accesorio = acc.id_accesorio)
		WHERE acc_ped.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$ra = mysql_query($sqla);
		if (!$ra) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowa = mysql_fetch_assoc($ra)) {
			$objResponse->script(sprintf("newacc('acc%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');",
				$rowa['id_accesorio'],
				"",
				$rowa['precio_con_iva'],
				utf8_encode($rowa['nom_accesorio']),
				"3",
				$rowa['iva_accesorio'],
				$rowa['costo_accesorio'],
				$rowa['porcentaje_iva_accesorio'],
				$rowa['id_tipo_accesorio'],
				$rowa['id_condicion_pago'],
				$rowa['id_condicion_mostrar'],
				$rowa['id_accesorio_pedido']));
		}
		
		// ADICIONALES POR PAQUETE
		$sqlp = sprintf("SELECT
			paq_ped.id_paquete_pedido,
			paq_ped.id_pedido,
			paq_ped.id_acc_paq,
			acc.id_accesorio,
			CONCAT(acc.nom_accesorio, IF (paq_ped.iva_accesorio = 1, ' (Incluye Impuesto)', ' (E)')) AS nom_accesorio,
			acc.des_accesorio,
			paq_ped.id_tipo_accesorio,
			(CASE paq_ped.id_tipo_accesorio
				WHEN 1 THEN	'Adicional'
				WHEN 2 THEN 'Accesorio'
				WHEN 3 THEN 'Contrato'
			END) AS descripcion_tipo_accesorio,
			paq_ped.iva_accesorio,
			paq_ped.porcentaje_iva_accesorio,
			(paq_ped.precio_accesorio + (paq_ped.precio_accesorio * paq_ped.porcentaje_iva_accesorio / 100)) AS precio_con_iva,
			paq_ped.costo_accesorio,
			paq_ped.id_condicion_pago,
			paq_ped.id_condicion_mostrar
		FROM an_paquete_pedido paq_ped
			INNER JOIN an_acc_paq acc_paq ON (paq_ped.id_acc_paq = acc_paq.id_acc_paq)
			INNER JOIN an_accesorio acc ON (acc_paq.id_accesorio = acc.id_accesorio)
		WHERE paq_ped.id_pedido = %s;",
			valTpDato($idPedido, "int"));
		$rp = mysql_query($sqlp);
		if (!$rp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowa = mysql_fetch_assoc($rp)) {
			$objResponse->script(sprintf("newacc('acc%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');",
				$rowa['id_accesorio'],
				$rowa['id_acc_paq'],
				$rowa['precio_con_iva'],
				utf8_encode($rowa['nom_accesorio']),
				"3",
				$rowa['iva_accesorio'],
				$rowa['costo_accesorio'],
				$rowa['porcentaje_iva_accesorio'],
				$rowa['id_tipo_accesorio'],
				$rowa['id_condicion_pago'],
				$rowa['id_condicion_mostrar'],
				$rowa['id_paquete_pedido']));
		}
		
		// DATOS DEL PEDIDO
		$objResponse->assign("txtIdPedido","value",$rowPedido['id_pedido']);
		$objResponse->assign("txtNumeroPedido","value",$rowPedido['numeracion_pedido']);
		$objResponse->assign("txtIdPresupuesto","value",$rowPedido['id_presupuesto']);
		$objResponse->assign("txtNumeroPresupuesto","value",$rowPedido['numeracion_presupuesto']);
		$objResponse->assign("txtIdEmpresa","value",$rowEmp['id_empresa_reg']);
		$objResponse->assign("txtEmpresa","value",$rowEmp['nombre_empresa']);
		
		$objResponse->loadCommands(cargaLstEmpleado($rowPedido['asesor_ventas'], "lstAsesorVenta", "1,2"));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", "3", "-1", "1", $rowPedido['id_clave_movimiento']));
		
		if ($idFactura > 0) {
			// BUSCA LOS DATOS DE LA FACTURA
			$queryFactura = sprintf("SELECT *,
				(CASE cxc_fact.estadoFactura
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END) AS estado_fact_vent
			FROM cj_cc_encabezadofactura cxc_fact
			WHERE cxc_fact.idFactura = %s
				AND cxc_fact.anulada LIKE 'NO';",
				valTpDato($idFactura, "int"));
			$rsFactura = mysql_query($queryFactura);
			if (!$rsFactura) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowFactura = mysql_fetch_array($rsFactura);
			
			$aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
				$rowFactura['idFactura']);
			$aVerDcto .= sprintf("<a href=\"javascript:verVentana('../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".("Factura Venta PDF")."\"/></a>", $rowFactura['idFactura']);
			
			$html = "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo4\" width=\"100%\">".
			"<tr align=\"center\">".
				"<td height=\"25\" width=\"25\"><img src=\"../img/iconos/exclamation.png\"/></td>".
				"<td>".
					"<table>".
					"<tr align=\"right\">".
						"<td nowrap=\"nowrap\">"."Edición de la Factura Nro. "."</td>".
						"<td nowrap=\"nowrap\">".$aVerDcto."</td>".
						"<td>".$rowFactura['numeroFactura']."</td>".
					"</tr>".
					"</table>".
				"</td>".
			"</tr>".
			"</table>";
			$objResponse->assign("tdMsjPedido","innerHTML",$html);
		}
		
		$objResponse->assign("txtIdUnidadBasica","value",$idUnidadBasica);
		$objResponse->assign("txtIdUnidadFisica","value",$idUnidadFisica);
		$objResponse->loadCommands(asignarUnidadBasica($idUnidadBasica, $idEmpresa, $rowPedido['precio_venta']));
		
		if ($idUnidadBasica > 0) {
			$valBusq = sprintf("%s|%s|%s",
				$idEmpresa,
				$idUnidadBasica,
				$idUnidadFisica);
			
			$objResponse->loadCommands(listaUnidadFisica(0, "CONCAT(vw_iv_modelo.nom_uni_bas, vw_iv_modelo.nom_modelo, vw_iv_modelo.nom_version)", "ASC", $valBusq));
		}
		
		// VENTA DE LA UNIDAD
		$objResponse->assign("txtPrecioBase","value",number_format($rowPedido['precio_venta'], 2, ".", ","));
		$objResponse->assign("txtDescuento","value",number_format($rowPedido['monto_descuento'], 2, ".", ","));
		$objResponse->assign("porcentaje_iva","value",number_format($txtPorcIva, 2, ".", ","));
		$objResponse->assign("porcentaje_impuesto_lujo","value",number_format($txtPorcIvaLujo, 2, ".", ","));
		$objResponse->assign("eviva","innerHTML",((strlen($eviva) > 0) ? "(".$eviva.")" : ""));
		$objResponse->assign("txtPrecioVenta","value",number_format($txtPrecioVenta, 2, ".", ","));
		
		$objResponse->assign("hddTipoInicial","value",$rowPedido['tipo_inicial']);
		$objResponse->assign("txtPorcInicial","value",number_format($rowPedido['porcentaje_inicial'], 2, ".", ","));
		$objResponse->assign("txtMontoInicial","value",number_format($rowPedido['inicial'], 2, ".", ","));
		
		$objResponse->assign("txtTotalInicialGastos","value",number_format($rowPedido['total_inicial_gastos'], 2, ".", ","));
		$objResponse->assign("txtSaldoFinanciar","value",number_format($rowPedido['saldo_financiar'], 2, ".", ","));
		$objResponse->assign("txtTotalAdicionalContrato","value",number_format($rowPedido['total_adicional_contrato'], 2, ".", ","));
		
		$objResponse->assign("txtTotalPedido","value",number_format($rowPedido['total_pedido'], 2, ".", ","));
		
		// BUSCA LOS DATOS DEL PRESUPUESTO DE ACCESORIOS
		$queryPresupuestoAccesorio = sprintf("SELECT * FROM an_presupuesto_accesorio pres_acc WHERE pres_acc.id_presupuesto = %s;",
			valTpDato($idPresupuesto, "int"));
		$rsPresupuestoAccesorio = mysql_query($queryPresupuestoAccesorio);
		if (!$rsPresupuestoAccesorio) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowPresupuestoAccesorio = mysql_fetch_assoc($rsPresupuestoAccesorio);
		
		// PRESUPUESTO ACCESORIOS
		$objResponse->assign("vexacc1","value",number_format($rowPresupuestoAccesorio['subtotal'], 2, ".", ","));
		$objResponse->assign("txtTotalImpuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
		$objResponse->assign("txtTotalPresupuestoAccesorio","value",number_format($rowPresupuestoAccesorio['subtotal'] + $rowPresupuestoAccesorio['subtotal_iva'], 2, ".", ","));
		
		$objResponse->assign("exacc2","value",$rowPedido['exacc2']);
		$objResponse->assign("vexacc2","value",number_format($rowPedido['vexacc2'], 2, ".", ","));
		$objResponse->assign("exacc3","value",$rowPedido['exacc3']);
		$objResponse->assign("vexacc3","value",number_format($rowPedido['vexacc3'], 2, ".", ","));
		$objResponse->assign("exacc4","value",$rowPedido['exacc4']);
		$objResponse->assign("vexacc4","value",number_format($rowPedido['vexacc4'], 2, ".", ","));
		$objResponse->assign("txtTotalAccesorio","value",number_format($rowPedido['total_accesorio'], 2, ".", ","));
		$objResponse->assign("empresa_accesorio","value",$rowPedido['empresa_accesorio']);
		
		// FORMA DE PAGO
		$objResponse->assign("txtMontoAnticipo","value",number_format($rowPedido['anticipo'], 2, ".", ","));
		$objResponse->assign("txtMontoComplementoInicial","value",number_format($rowPedido['complemento_inicial'], 2, ".", ","));
		
		// FINANCIAMIENTO
		$objResponse->loadCommands(cargaLstBancoFinanciar($rowPedido['id_banco_financiar'], "lstBancoFinanciar"));
		if ($rowPedido['porcentaje_inicial'] < 100) {
			if ($rowPedido['id_banco_financiar'] > 0) {
			} else {
				$objResponse->script("
				byId('cbxSinBancoFinanciar').checked = true;
				byId('aDesbloquearSinBancoFinanciar').style.display = 'none';
				byId('hddSinBancoFinanciar').value = 1;");
			}
		}
		$valores = array(
			"lstMesesFinanciar*".$rowPedido['meses_financiar'],
			"txtInteresCuotaFinanciar*".$rowPedido['interes_cuota_financiar'],
			"txtCuotasFinanciar*".$rowPedido['cuotas_financiar'],
			"txtFechaCuotaFinanciar*".$rowPedido['fecha_pago_cuota'],
			"lstMesesFinanciar2*".$rowPedido['meses_financiar2'],
			"txtInteresCuotaFinanciar2*".$rowPedido['interes_cuota_financiar2'],
			"txtCuotasFinanciar2*".$rowPedido['cuotas_financiar2'],
			"txtFechaCuotaFinanciar2*".$rowPedido['fecha_pago_cuota2'],
			"lstMesesFinanciar3*".$rowPedido['meses_financiar3'],
			"txtInteresCuotaFinanciar3*".$rowPedido['interes_cuota_financiar3'],
			"txtCuotasFinanciar3*".$rowPedido['cuotas_financiar3'],
			"txtFechaCuotaFinanciar3*".$rowPedido['fecha_pago_cuota3'],
			"lstMesesFinanciar4*".$rowPedido['meses_financiar4'],
			"txtInteresCuotaFinanciar4*".$rowPedido['interes_cuota_financiar4'],
			"txtCuotasFinanciar4*".$rowPedido['cuotas_financiar4'],
			"txtFechaCuotaFinanciar4*".$rowPedido['fecha_pago_cuota4']);
		$objResponse->loadCommands(asignarBanco($rowPedido['id_banco_financiar'], implode("|",$valores)));
		$objResponse->assign("txtCuotasFinanciar","value",number_format($rowPedido['cuotas_financiar'], 2, ".", ","));
		$objResponse->assign("txtCuotasFinanciar2","value",number_format($rowPedido['cuotas_financiar2'], 2, ".", ","));
		$objResponse->assign("txtCuotasFinanciar3","value",number_format($rowPedido['cuotas_financiar3'], 2, ".", ","));
		$objResponse->assign("txtCuotasFinanciar4","value",number_format($rowPedido['cuotas_financiar4'], 2, ".", ","));
		$objResponse->assign("porcentaje_flat","value",$rowPedido['porcentaje_flat']);
		$objResponse->assign("capaporcentaje_flat","innerHTML",number_format($rowPedido['porcentaje_flat'], 2, ".", ","));
		$objResponse->assign("txtMontoFLAT","value",number_format($rowPedido['monto_flat'], 2, ".", ","));
		
		$objResponse->assign("txtPrecioTotal","value",number_format($rowPedido['forma_pago_precio_total'], 2, ".", ","));
		
		// SEGURO
		$objResponse->loadCommands(cargaLstPoliza($rowPedido['id_poliza'], "lstPoliza"));
		$objResponse->assign("txtNombreAgenciaSeguro","value",$rowPedido['nombre_agencia_seguro']);
		$objResponse->assign("txtDireccionAgenciaSeguro","value",$rowPedido['direccion_agencia_seguro']);
		$objResponse->assign("txtCiudadAgenciaSeguro","value",$rowPedido['ciudad_agencia_seguro']);
		$objResponse->assign("txtPaisAgenciaSeguro","value",$rowPedido['pais_agencia_seguro']);
		$objResponse->assign("txtTelefonoAgenciaSeguro","value",$rowPedido['telefono_agencia_seguro']);
		$objResponse->assign("txtNumPoliza","value",$rowPedido['num_poliza']);
		$objResponse->assign("txtMontoSeguro","value",number_format($rowPedido['monto_seguro'], 2, ".", ","));
		$objResponse->assign("txtPeriodoPoliza","value",$rowPedido['periodo_poliza']);
		$objResponse->assign("txtDeduciblePoliza","value",$rowPedido['ded_poliza']);
		$objResponse->assign("txtFechaEfect","value",(($rowPedido['fech_efect'] != "") ? date(spanDateFormat, strtotime($rowPedido['fech_efect'])) : ""));
		$objResponse->assign("txtFechaExpi","value",(($rowPedido['fech_expira'] != "") ? date(spanDateFormat, strtotime($rowPedido['fech_expira'])) : ""));
		$objResponse->assign("txtInicialPoliza","value",number_format($rowPedido['inicial_poliza'], 2, ".", ","));
		$objResponse->assign("txtMesesPoliza","value",$rowPedido['meses_poliza']);
		$objResponse->assign("txtCuotasPoliza","value",number_format($rowPedido['cuotas_poliza'], 2, ".", ","));
		
		$objResponse->assign("observaciones","innerHTML",$rowPedido['observaciones']);
		
		$objResponse->loadCommands(cargaLstEmpleado($rowPedido['gerente_ventas'], "lstGerenteVenta", ((in_array(idArrayPais, array(2))) ? "2" : "2,300")));
		$objResponse->assign("txtFechaVenta","value",date(spanDateFormat, strtotime($rowPedido['fecha_gerente_ventas'])));
		$objResponse->loadCommands(cargaLstEmpleado($rowPedido['administracion'], "lstGerenteAdministracion", "3"));
		$objResponse->assign("txtFechaAdministracion","value",date(spanDateFormat, strtotime($rowPedido['fecha_administracion'])));
		
		$objResponse->assign("txtFechaReserva","value",date(spanDateFormat, strtotime($rowPedido['fecha_reserva_venta'])));
		$objResponse->assign("txtFechaEntrega","value",date(spanDateFormat, strtotime($rowPedido['fecha_entrega'])));
		
		$objResponse->assign("txtPrecioRetoma","value",number_format($rowPedido['precio_retoma'], 2, ".", ","));
		$objResponse->assign("txtFechaRetoma","value",(($rowPedido['fecha_retoma'] != "") ? date(spanDateFormat, strtotime($rowPedido['fecha_retoma'])) : ""));
		
		$objResponse->call(asignarPrecio);
	} else {
		if ($frmPedido['hddTipoPedido'] == "i") {
			$objResponse->script("
			byId('frmPedido').onsubmit = function () { return false; };
			
			byId('trFrmPerdido').style.display = 'none';
			byId('trBuscarPerdido').style.display = '';
			
			byId('txtBuscarPresupuesto').className = 'inputHabilitado';
			byId('txtBuscarPresupuesto').focus();");
		} else {
			$objResponse->script("byId('frmPedido').action = 'an_ventas_pedido_guardar.php';");
		}
		
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		
		// BUSCA LOS DATOS DE LA EMPRESA
		$queryEmp = sprintf("SELECT *,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		WHERE vw_iv_emp_suc.id_empresa_reg = %s",
			valTpDato($idEmpresa, "int"));
		$rsEmp = mysql_query($queryEmp);
		if (!$rsEmp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$rowEmp = mysql_fetch_assoc($rsEmp);
		
		// DATOS DEL PEDIDO
		$objResponse->assign("txtIdEmpresa","value",$rowEmp['id_empresa_reg']);
		$objResponse->assign("txtEmpresa","value",$rowEmp['nombre_empresa']);
		
		$objResponse->assign("txtMontoAnticipo","value",number_format(0, 2, ".", ","));
		
		$objResponse->loadCommands(cargaLstEmpleado("", "lstAsesorVenta", "1,2"));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "2", "3", "-1", "1"));
		
		$objResponse->loadCommands(cargaLstBancoFinanciar("", "lstBancoFinanciar"));
		$objResponse->loadCommands(cargaLstPoliza("", "lstPoliza"));
		
		$objResponse->loadCommands(cargaLstEmpleado("", "lstGerenteVenta", ((in_array(idArrayPais, array(2))) ? "2" : "2,300")));
		$objResponse->loadCommands(cargaLstEmpleado("", "lstGerenteAdministracion", "3"));
	}
	
	// BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaLocal = sprintf("SELECT * FROM pg_monedas WHERE predeterminada = 1;");
	$rsMonedaLocal = mysql_query($queryMonedaLocal);
	if (!$rsMonedaLocal) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowMonedaLocal = mysql_fetch_assoc($rsMonedaLocal);
	
	$abrevMonedaLocal = $rowMonedaLocal['abreviacion'];
	
	$objResponse->assign("tdPrecioBaseMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdDescuentoMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdPrecioVentaMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdMontoInicialMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdTotalInicialGastosMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdSaldoFinanciarMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdTotalAdicionalContratoMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdMontoAnticipoMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdMontoComplementoInicialMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdCuotasFinanciarMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdCuotasFinanciarMoneda2","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdCuotasFinanciarMoneda3","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdCuotasFinanciarMonedaFinal","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdMontoFLATMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdPrecioTotalMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdvexacc1Moneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdTotalImpuestoAccesorioMoneda","innerHTML",$abrevMonedaLocal);
	$objResponse->assign("tdTotalPresupuestoAccesorioMoneda","innerHTML",$abrevMonedaLocal);
	
	return $objResponse;
}

function formValidarPermisoEdicion($hddModulo) {
	$objResponse = new xajaxResponse();
	
	$queryPermiso = sprintf("SELECT * FROM pg_claves_modulos WHERE modulo LIKE %s;",
		valTpDato($hddModulo, "text"));
	$rsPermiso = mysql_query($queryPermiso);
	if (!$rsPermiso) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowPermiso = mysql_fetch_assoc($rsPermiso);
	
	$objResponse->assign("txtDescripcionPermiso","value",utf8_encode($rowPermiso['descripcion']));
	$objResponse->assign("hddModulo","value",$hddModulo);
	
	return $objResponse;
}

function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 1000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = alm.id_empresa)
		OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
				WHERE suc.id_empresa_padre = alm.id_empresa)
		OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
												WHERE suc.id_empresa = alm.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
		
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_uni_bas = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.id_unidad_fisica = %s",
			valTpDato($valCadBusq[2], "int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('POR REGISTRAR','DISPONIBLE')");
	}
	
	$query = sprintf("SELECT DISTINCT
		vw_iv_modelo.id_uni_bas,
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
	FROM an_unidad_fisica uni_fis
		INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
		INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
		INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
		LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
		LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
		LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
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
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "2%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Unidad Física");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "9%", $pageNum, "serial_motor", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialMotor);
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "10%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, "Color");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Ingreso");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "4%", $pageNum, "(TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)))", $campOrd, $tpOrd, $valBusq, $maxRows, "Días");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "estado_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Venta");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "6%", $pageNum, "asig.idAsignacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Asignación");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "14%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Almacén");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "7%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Fact. Compra");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "precio_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"15\">".utf8_encode($row['vehiculo'])."</td>";
		$htmlTb .= "</tr>";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
			valTpDato($row['id_uni_bas'], "int"));
		
		$queryUnidadFisica = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.serial_chasis,
			uni_fis.placa,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			(CASE
				WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
					IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
				WHEN (an_ve.fecha IS NOT NULL) THEN
					an_ve.fecha
			END) AS fecha_origen,
			IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
				(CASE
					WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
					WHEN (an_ve.fecha IS NOT NULL) THEN
						TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
				END),
			0) AS dias_inventario,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			asig.idAsignacion,
			alm.nom_almacen,
			cxp_fact.numero_factura_proveedor,
			uni_fis.costo_compra,
			uni_fis.precio_compra,
			uni_fis.costo_agregado,
			uni_fis.costo_depreciado,
			uni_fis.costo_trade_in,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
				AND an_ve.fecha IS NOT NULL
				AND an_ve.tipo_vale_entrada = 1
				AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $sqlBusq2);
		$queryUnidadFisica .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsUnidadFisica = mysql_query($queryUnidadFisica);
		if (!$rsUnidadFisica) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$contFila2 = 0;
		$subTotalCosto = 0;
		while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			$checked = ($valCadBusq[2] > 0) ?  "checked=\"checked\"" : "";
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td><input type=\"radio\" id=\"rbtUnidadFisica\" name=\"rbtUnidadFisica[]\" ".$checked." value=\"".$rowUnidadFisica['id_unidad_fisica']."\"/></td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['id_unidad_fisica'])."</td>";
				$htmlTb .= "<td>";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['serial_carroceria'])."</div>";
					$htmlTb .= "<div class=\"textoNegrita_10px\">".utf8_encode($rowUnidadFisica['condicion_unidad'])."</div>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_motor'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['color_externo1'])."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".utf8_encode($rowUnidadFisica['placa'])."</td>";
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= (($rowUnidadFisica['fecha_origen'] != "") ? "<div>".date(spanDateFormat, strtotime($rowUnidadFisica['fecha_origen']))."</div>" : "");
					$htmlTb .= (($rowUnidadFisica['dias_inventario'] > 0) ? "<div class=\"textoNegrita_9px\">".($rowUnidadFisica['dias_inventario']." días")."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['dias_inventario'])."</td>";
				$htmlTb .= "<td align=\"center\">";
					$htmlTb .= "<div>".utf8_encode($rowUnidadFisica['estado_venta'])."</div>";
					$htmlTb .= (($rowUnidadFisica['estado_venta'] == "RESERVADO" && $rowUnidadFisica['estado_compra'] != "REGISTRADO") ? "<div class=\"textoNegrita_9px\">(".utf8_encode($rowUnidadFisica['estado_compra']).")</div>" : "");
					$htmlTb .= (($rowUnidadFisica['id_activo_fijo'] > 0) ? "<div class=\"textoNegrita_9px\">Código: ".$rowUnidadFisica['id_activo_fijo']."</div>" : "");
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['idAsignacion'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
				$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
				$htmlTb .= "<td align=\"right\">".($rowUnidadFisica['numero_factura_proveedor'])."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($rowUnidadFisica['precio_compra'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
			
			$subTotalCosto += $rowUnidadFisica['precio_compra'];
		}
		
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"12\">"."Subtotal:<br>".utf8_encode($row['vehiculo'])."</td>";
			$htmlTb .= "<td>".number_format($contFila2, 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($subTotalCosto, 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	if ($pageNum == $totalPages) {
		$query = sprintf("SELECT DISTINCT
			vw_iv_modelo.id_uni_bas,
			CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
			LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
			LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
			LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("uni_fis.id_uni_bas = %s",
				valTpDato($row['id_uni_bas'], "int"));
				
			$queryUnidadFisica = sprintf("SELECT
				uni_fis.id_unidad_fisica,
				uni_fis.serial_carroceria,
				uni_fis.serial_motor,
				uni_fis.serial_chasis,
				uni_fis.placa,
				color_ext.nom_color AS color_externo1,
				color_int.nom_color AS color_interno1,
				(CASE
					WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
						IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen)
					WHEN (an_ve.fecha IS NOT NULL) THEN
						an_ve.fecha
				END) AS fecha_origen,
				IF (uni_fis.estado_venta IN ('SINIESTRADO','DISPONIBLE','RESERVADO','ACTIVO FIJO'), 
					(CASE
						WHEN (cxp_fact.fecha_origen IS NOT NULL) THEN
							TO_DAYS(NOW()) - TO_DAYS(IF (an_ve.fecha IS NOT NULL AND an_ve.fecha > cxp_fact.fecha_origen, an_ve.fecha, cxp_fact.fecha_origen))
						WHEN (an_ve.fecha IS NOT NULL) THEN
							TO_DAYS(NOW()) - TO_DAYS(an_ve.fecha)
					END),
				0) AS dias_inventario,
				uni_fis.estado_compra,
				uni_fis.estado_venta,
				asig.idAsignacion,
				alm.nom_almacen,
				cxp_fact.numero_factura_proveedor,
				uni_fis.costo_compra,
				uni_fis.precio_compra,
				uni_fis.costo_agregado,
				uni_fis.costo_depreciado,
				uni_fis.costo_trade_in,
				IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
			FROM an_unidad_fisica uni_fis
				INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
				INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
				INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
				INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
				LEFT JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
				LEFT JOIN cp_factura cxp_fact ON (cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
				LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
				LEFT JOIN an_pedido_compra ped_comp ON (ped_comp_det.idPedidoCompra = ped_comp.idPedidoCompra)
				LEFT JOIN an_asignacion asig ON (ped_comp.idAsignacion = asig.idAsignacion)
				LEFT JOIN an_vale_entrada an_ve ON (an_ve.id_unidad_fisica = uni_fis.id_unidad_fisica
					AND an_ve.fecha IS NOT NULL
					AND an_ve.tipo_vale_entrada = 1
					AND DATE(an_ve.fecha) = DATE(uni_fis.fecha_ingreso))
				INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s %s", $sqlBusq, $sqlBusq2);
			$rsUnidadFisica = mysql_query($queryUnidadFisica);
			$arrayTotal = NULL;
			$contFila2 = 0;
			while ($rowUnidadFisica = mysql_fetch_assoc($rsUnidadFisica)) {
				$contFila2++;
				
				$arrayTotal[12] = $contFila2;
				$arrayTotal[13] += ($rowUnidadFisica['precio_compra'] + $rowUnidadFisica['costo_agregado'] - $rowUnidadFisica['costo_depreciado'] - $rowUnidadFisica['costo_trade_in']);
			}
				
			$arrayTotalFinal[12] += $arrayTotal[12];
			$arrayTotalFinal[13] += $arrayTotal[13];
		}
		
		/*$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"11\">"."Total de Totales:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal[12], 2, ".", ",")."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalFinal[13], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";*/
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"14\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaUnidadFisica","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
		
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
	
	if ($rowPermiso['id_clave_usuario'] > 0) {
		if ($frmPermiso['hddModulo'] == "an_pedido_venta_form_entidad_bancaria") {
			$objResponse->script("byId('btnCancelarPermiso').click();");
			
			$objResponse->assign("hddSinBancoFinanciar","value","1");
			$objResponse->script("byId('aDesbloquearSinBancoFinanciar').style.display = 'none';");
		}
	} else {
		$objResponse->alert("Permiso No Autorizado");
		$objResponse->script("byId('btnCancelarPermiso').click();");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarPoliza");
$xajax->register(XAJAX_FUNCTION,"asignarSinBancoFinanciar");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoFinanciar");
$xajax->register(XAJAX_FUNCTION,"cargaLstBancoFinanciar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargaLstPoliza");
$xajax->register(XAJAX_FUNCTION,"formPedido");
$xajax->register(XAJAX_FUNCTION,"formValidarPermisoEdicion");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"validarPermiso");
?>