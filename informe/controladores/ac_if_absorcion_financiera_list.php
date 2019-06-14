<?php
set_time_limit(0);

function asignarEmpresa($idEmpresa, $idObjetoDestino = ""){
	$objResponse = new xajaxResponse();

	$idEmpresa = ($idEmpresa != "") ? $idEmpresa : $_SESSION['idEmpresaUsuarioSysGts'];

	$query = sprintf("SELECT id_empresa_reg, id_empresa_suc, CONCAT_WS(' - ',nombre_empresa,nombre_empresa_suc) AS nombre_empresa, sucursal
							FROM vw_iv_empresas_sucursales
							WHERE id_empresa_reg = %s",
			valTpDato($idEmpresa,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rows = mysql_fetch_array($rs);

	$inputText = "txtEmpresa2";

	$objResponse->assign($inputText,"value",sprintf(" %s (%s)",$rows['nombre_empresa'],$rows['sucursal']));

	return $objResponse;
}

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("$('.btnReportes').hide();");
	global $mes;
	$objResponse->script("  document.forms['divListaTotalVentas'].reset();
							document.forms['divListaTotalCosto'].reset();
							document.forms['divListaUtilidad'].reset();
							document.forms['divListaGastoGeneral'].reset();
							document.forms['divListaIndicador'].reset()");
	
	$arrayFecha = explode("-", $frmBuscar['txtFecha']);
	$mesCierre = $arrayFecha[0];
	$anoCierre = $arrayFecha[1];

	$idEmpresa = $frmBuscar['lstEmpresa'];
	$meses = sprintf("'%s'", date("m", strtotime("01-".$frmBuscar['txtFecha'])));
	$anio = sprintf("'%s'", date("Y", strtotime("01-".$frmBuscar['txtFecha'])));
	
	$objResponse->loadCommands(datosGenerales($idEmpresa, $mesCierre, $anoCierre));
	$objResponse->loadCommands(totalVentas($idEmpresa, $mesCierre, $anoCierre));
	$objResponse->loadCommands(totalCosto($idEmpresa, $mesCierre, $anoCierre));
	$objResponse->loadCommands(totalCosto($idEmpresa, $mesCierre, $anoCierre));
	
	$objResponse->script("xajax_utilidad(xajax.getFormValues('divListaTotalVentas'), xajax.getFormValues('divListaTotalCosto'))");
	$objResponse->script("xajax_gastosGeneral($idEmpresa, $meses, $anio, xajax.getFormValues('divListaTotalVentas'), xajax.getFormValues('divListaTotalCosto'))");
// 	$objResponse->script("xajax_indicadorDesemp(xajax.getFormValues('divListaTotalVentas'), xajax.getFormValues('divListaTotalCosto'),)");

	return $objResponse;
}

function buscarCierre($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf(" %s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstMes'],
		$frmBuscar['lstAno']);
	
	$objResponse->loadCommands(listaCierreMensual(0, "id_cierre_mensual", "DESC", $valBusq));
	
	return $objResponse;
}

function buscarCuenta($frmMantenimiento, $frmBuscar, $tipo, $codigo = '') {
	$objResponse = new xajaxResponse();
	
	if($tipo != 1 && $tipo != 2 && $codigo == ''){
		$codigo = $frmMantenimiento['btnBuscarCuentaConcep'];
	}
	$valBusq = sprintf(" %s|%s|%s|%s|%s|%s|%s",
			$frmBuscar['lstEmpresa'],
			$frmMantenimiento['txtCodigo'],
			($frmMantenimiento['txtDescripcion'] != '') ? $frmMantenimiento['txtDescripcion'] : $frmMantenimiento['txtBusConcepto'],
			$codigo,
			$frmMantenimiento['txtConcepto'],
			"", //ocultar boton de guardar concepto
			$frmMantenimiento['tipoVenta']);

	// tipo = 1 Absorcion; 2 Contabilidad; 3 Concepto Genereales
	if($tipo == 1){
		$objResponse->loadCommands(listaCuenta(0, "id_absorcion","ASC", $valBusq));
	} elseif($tipo == 2){
		$objResponse->loadCommands(listaCuentaContabilidad(0, "codigo","ASC", $valBusq));
	} else {
		$objResponse->loadCommands(listaCuentaConcepto(0, "id_concepto","ASC", $valBusq));
	}
	return $objResponse;
}

function buscarCuentaConcepto($frmMantenimiento, $frmBuscar, $tipo, $codigo = '') {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf(" %s|%s|%s|%s|%s|%s|%s",
			$frmBuscar['lstEmpresa'],
			$frmMantenimiento['txtCodigo'],
			($frmMantenimiento['txtDescripcion'] != '') ? $frmMantenimiento['txtDescripcion'] : $frmMantenimiento['txtBusConcepto2'],
			$codigo,
			$frmMantenimiento['textConcepto'],
			1, //Mostrar boton de guardar concepto
			$frmMantenimiento['tipoVenta2']);

	$objResponse->loadCommands(listaCuentaConcepto(0, "id_concepto","ASC", $valBusq));
	return $objResponse;
}

function buscarTiposCuenta($frmTiposCuenta) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s",
			$_SESSION['idEmpresaUsuarioSysGts'],
			$frmTiposCuenta['lstTiposCuenta']);

	$objResponse->loadCommands(listaTiposCuenta(0, "numero_identificador","ASC", $valBusq));
	return $objResponse;
}
function cargaLstDecimalPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();

	$array = array("0" => "Sin Decimales", "1" => "Con Decimales");
	$totalRows = count($array);

	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirReporteAFP(xajax.getFormValues('frmBuscar'), xajax.getFormValues('divListaTotalVentas'), xajax.getFormValues('divListaTotalCosto'), xajax.getFormValues('divListaUtilidad'), xajax.getFormValues('divListaIndicador'), xajax.getFormValues('divListaGastoGeneral'), true);\"";

	$html = "<select id=\"lstDecimalPDF\" name=\"lstDecimalPDF\" ".$class." ".$onChange.">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstDecimalPDF","innerHTML",$html);

	return $objResponse;
}

function cargaLstAno($selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAno\" name=\"lstAno\" class=\"inputHabilitado\" onchange=\"byId('btnBuscarCierre').click();\" style=\"width:99%\">";
	$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['nom_ano']) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$row['nom_ano']."\">".utf8_encode($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAno","innerHTML",$html);

	return $objResponse;
}

function cargaLstMes($selId = "") {
	$objResponse = new xajaxResponse();

	global $mes;

	$html = "<select id=\"lstMes\" name=\"lstMes\" class=\"inputHabilitado\" onchange=\"byId('btnBuscarCierre').click();\" style=\"width:99%\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	for ($contMes = 1; $contMes <= 12; $contMes++) {
		$selected = ($selId == $contMes) ? "selected=\"selected\"" : "";

		$html .= "<option ".$disabled." value=\"".$contMes."\">".str_pad($contMes, 2, "0", STR_PAD_LEFT).".- ".$mes[$contMes]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMes","innerHTML",$html);

	return $objResponse;
}

function cargaLstMesAno($idEmpresa) {
	$objResponse = new xajaxResponse();

	if (date("m") == 1) {
		$anoInicio = date("Y")-1;
		$anoLimite = date("Y");
	} else {
		$anoInicio = date("Y");
		$anoLimite = date("Y");
	}

	$html = "<select id=\"lstMesAno\" class=\"inputHabilitado\" name=\"lstMesAno\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		for ($ano = $anoInicio; $ano <= $anoLimite; $ano++) {
			for ($mes = 1; (($mes <= 12 && $anoInicio != $anoLimite && $ano == $anoInicio) || ($mes <= date("m") && $ano == $anoLimite)); $mes++) {
				$query = sprintf("SELECT * FROM if_cierre_mensual cierre_mens
									WHERE cierre_mens.mes = %s
										AND cierre_mens.ano = %s
										AND id_empresa = %s;",
							valTpDato(intval($mes), "int"),
							valTpDato($ano, "int"),
							valTpDato($idEmpresa, "int"));
				$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRows = mysql_num_rows($rs);
				$row = mysql_fetch_assoc($rs);
					
				$disabled = ($totalRows > 0) ? "disabled=\"disabled\"" : "";
					
				$html .= "<option ".$disabled." value=\"".str_pad($mes, 2, "0", STR_PAD_LEFT)."/".$ano."\">".str_pad($mes, 2, "0", STR_PAD_LEFT)."/".$ano."</option>";
			}
		}
	$html .= "</select>";
	$objResponse->assign("tdlstMesAno","innerHTML",$html);

	return $objResponse;
}

function datosGenerales($idEmpresa, $meses, $anio) {
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	mysql_query("SET GLOBAL innodb_stats_on_metadata = 0;");
	
	$htmlMsj = "<table width=\"100%\">";
		$htmlMsj .= "<tr>";
			$htmlMsj .= "<td>";
				$htmlMsj .= "<p style=\"font-size:24px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; text-shadow:3px 3px 0 rgba(51,51,51,0.8);\">";
					$htmlMsj .= "<span style=\"display:inline-block; text-transform:uppercase; color:#38A6F0; padding-left:2px;\">".$mes[intval($meses)]." ".$anio."</span>";
					/*$htmlMsj .= "<br>";
					$htmlMsj .= "<span style=\"font-size:18px; display:inline-block; text-transform:uppercase; color:#B7D154; padding-left:2px;\">Versión 3.0</span>";*/
				$htmlMsj .= "</p>";
			$htmlMsj .= "</td>";
		$htmlMsj .= "</tr>";
	$htmlMsj .= "</table>";
	
	$objResponse->assign("divMsjCierre","innerHTML",$htmlMsj);
	
	$objResponse->script("$('#tblMsj').hide();");
	$objResponse->script("$('#tblInforme').show();");
	
	return $objResponse;
}

function editarCuenta($idAbs){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT *
						FROM if_absorcion_financiera
						WHERE id_absorcion = %s;",
					valTpDato($idAbs, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$row = mysql_fetch_assoc($rs);
	$codigo = $row['codigo'];
	$concepto = $row['concepto'];
	
	$objResponse->script("byId('txtCodigoNew').className = 'inputHabilitado';");
	$objResponse->script("byId('txtDescripcionNew').className = 'inputHabilitado';");
	$objResponse->script("$('#txtCodigoNew').val('$codigo');");
	$objResponse->script("$('#txtDescripcionNew').val('$concepto');");
	$objResponse->script("$('#btnAddCuenta').val('2');");
	$objResponse->script("$('.textBtnCuenta').text('Editar Cuenta');");
	$objResponse->script("$('#imgAdd').hide();");
	$objResponse->script("$('#imgCerrar').show();");
	$objResponse->script("$('#agregarCuenta').show();");
	$objResponse->script("$('#btnGuardarCuenta').val($idAbs);");
	
	return $objResponse;
}

function confirmarCuenta($idAbs, $id_concepto = ''){
	$objResponse = new xajaxResponse();

	if ($idTotal == '' && $idAbs != ''){
		$objResponse->script("
			if (confirm('¿Desea Eliminar la cuenta Seleccionada?') == true) {
				xajax_eliminarCuenta($idAbs, '');
			}
		");
	} else{
		$objResponse->script("
				if (confirm('¿Desea Eliminar Este concepto con todas las cuentas Seleccionada?') == true) {
				xajax_eliminarCuenta('', $id_concepto);
		}
				");
	}
	return $objResponse;
}

function confirmarConcepto($id_concepto = ''){
	$objResponse = new xajaxResponse();
	
	if ($id_concepto > 0){
		$objResponse->script("
				if (confirm('¿Desea Eliminar Este concepto?') == true) 
					xajax_eliminarConcepto($id_concepto);
				");
	}
	return $objResponse;
}

function confirmarTipoCuenta($numero_identificador = 0){
	$objResponse = new xajaxResponse();
	
	if ($numero_identificador > 0){
		$objResponse->script("
				if (confirm('¿Desea Eliminar Este Tipo de Cuenta?') == true) 
					xajax_eliminarTiposCuenta($numero_identificador);
				");
	}
	return $objResponse;
}

function eliminarConcepto($id_concepto = ''){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM if_absorcion_financiera
						WHERE id_concepto = %s",
				valTpDato($id_concepto, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	
	if($totalRows > 0){
		$objResponse->alert("El concepto no puede ser eliminado, tiene una cuenta asociada");
		return $objResponse;
	}
	
	mysql_query("START TRANSACTION;");

	if ($id_concepto != '') {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_concepto = %s",
				valTpDato($id_concepto, "int"));
	}

	$query = sprintf("DELETE FROM if_absorcion_conceptos
						%s",
				$sqlBusq);

	$rs = mysql_query($query);
	if (!$rs) {return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);}

	mysql_query("COMMIT;");

	$objResponse->script("byId('btnBusCuentaConcep').click()");
	$objResponse->alert("El concepto ha sido Eliminado con Éxito");

	return $objResponse;
}

function eliminarCuenta($idAbs, $id_concepto = ''){
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");

	if ($id_concepto == '') {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_absorcion = %s",
				valTpDato($idAbs, "int"));
	} else{
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_concepto = %s",
				valTpDato($id_concepto, "int"));
	}
	
	$query = sprintf("DELETE FROM if_absorcion_financiera
						%s",
				$sqlBusq);

	$rs = mysql_query($query);
	if (!$rs) {return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);}

	mysql_query("COMMIT;");

	$objResponse->script("byId('imgCerrarDivFlotante5').click()");
	$objResponse->script("byId('btnBuscarCat').click()");
	$objResponse->alert("La cuenta ha sido Eliminada");
	
	return $objResponse;
}

function eliminarTiposCuenta($numero_identificador){
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");

	
	$query = sprintf("SELECT * FROM if_absorcion_financiera
						WHERE SUBSTRING(codigo, 1, 1) = %s
						LIMIT 1",
			valTpDato($numero_identificador, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
	$totalRows = mysql_num_rows($rs);
	
	if($totalRows > 0){
		$objResponse->alert("El Tipo de Cuenta esta siendo usada, por favor Desasociar todas las Cuentas relacionadas.");
		return $objResponse;
	}
	
	$query = sprintf("DELETE FROM if_absorcion_tipos_cuenta
						WHERE numero_identificador = %s",
				valTpDato($numero_identificador, "int"));

	$rs = mysql_query($query);
	if (!$rs) {return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);}

	mysql_query("COMMIT;");

	$objResponse->script("byId('btnBuscarTiposCuenta').click()");
	$objResponse->script("byId('aTiposCuenta').click();");
	$objResponse->alert("El Tipo de Cuenta ha sido Eliminada con Éxito");
	
	return $objResponse;
}

function frmEditConcepto($idConcepto){
	$objResponse = new xajaxResponse();

		$query = sprintf("SELECT * FROM if_absorcion_conceptos
							WHERE id_concepto = %s",
				valTpDato($idConcepto, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->script("$('#editConceptoFrm').val('{$row['nom_concepto']}')");
		$objResponse->script("$('#editVentaConcep').val({$row['tipo']})");
		
	return $objResponse;
}

function frmEditTipoCuenta($numIdentificador){
	$objResponse = new xajaxResponse();

		$query = sprintf("SELECT * FROM if_absorcion_tipos_cuenta
							WHERE numero_identificador = %s",
				valTpDato($numIdentificador, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$objResponse->script("$('#textEditTipoCuenta').val('{$row['nombre_cuenta']}')");
		$objResponse->script("$('#lstTiposCuenta3').val({$row['numero_identificador']})");
		
	return $objResponse;
}

function gastosGeneral($idEmpresa, $meses, $anio, $formTotalVentas = '', $formTotalCostos = ''){
	$objResponse = new xajaxResponse();

	global $mes;
	$mesAnio = "$anio-$meses";

	$query = sprintf("SELECT 		af.codigo, 
									af.descripcion,
									ac.nom_concepto, 
									ac.tipo,
									mov.fecha,
									((SELECT 					
												(CASE 
														WHEN (SUM(mov2.debe) + SUM(mov2.haber)) IS NULL THEN 0
														ELSE (SUM(mov2.debe) + SUM(mov2.haber))
													END)
											FROM sipre_contabilidad.vmovimientodo mov2
												LEFT JOIN if_absorcion_financiera af2 ON af2.codigo = mov2.codigo
												LEFT JOIN if_absorcion_conceptos ac2 ON af2.id_concepto = ac2.id_concepto
											WHERE mov2.fecha LIKE %s AND ac2.nom_concepto LIKE ac.nom_concepto AND ac2.tipo = ac.tipo
											GROUP BY ac2.nom_concepto, ac2.tipo
										))As saldo,
										((SELECT 					
													(CASE 
															WHEN ROUND(((SUM(mov2.debe) + SUM(mov2.haber))/ %s)*100, 1) IS NULL THEN 0
															ELSE ROUND(((SUM(mov2.debe) + SUM(mov2.haber))/ %s)*100, 1)
														END)
												FROM sipre_contabilidad.vmovimientodo mov2
														LEFT JOIN if_absorcion_financiera af2 ON af2.codigo = mov2.codigo
													LEFT JOIN if_absorcion_conceptos ac2 ON af2.id_concepto = ac2.id_concepto
												WHERE mov2.fecha LIKE %s AND ac2.nom_concepto LIKE ac.nom_concepto AND ac2.tipo = ac.tipo
												GROUP BY ac2.nom_concepto, ac2.tipo 
										))As porSaldo
						FROM if_absorcion_financiera af
							LEFT JOIN if_absorcion_conceptos ac ON af.id_concepto = ac.id_concepto
							LEFT JOIN sipre_contabilidad.vmovimientodo mov ON mov.codigo = af.codigo
						GROUP BY ac.nom_concepto, ac.tipo ORDER BY ac.tipo;",
				valTpDato(" %$mesAnio%", "text"),
				valTpDato($formTotalVentas['totalVentas'], "float"),
				valTpDato($formTotalVentas['totalVentas'], "float"),
				valTpDato(" %$mesAnio%", "text"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	
	if($totalRows > 0){
		while ($row = mysql_fetch_assoc($rs)){
			$concepto = utf8_encode($row['nom_concepto']);
			
			if($row['saldo'] != null){
				$gastos["{$concepto}"]["{$row['tipo']}"] = valTpDato(number_format("{$row['saldo']}", 2, ".", ","),"cero_por_vacio");
				$gastos2["{$concepto}"]["{$row['tipo']}"] = $row['saldo'];
			} else{
				$gastos["{$concepto}"]["{$row['tipo']}"] = "-&nbsp&nbsp&nbsp";
			}
			
			if($row['saldo'] == '') $row['saldo'] = 0;
			
			if($row['tipo'] == 1){
				$gastos3[] = $concepto."_".$row['saldo'];
			}elseif($row['tipo'] == 2){
				$gastos4[] = $concepto."_".$row['saldo'];
			}else{
				$gastos5[] = $concepto."_".$row['saldo'];
			}
			
			if($row['saldo'] != null){
				$gastos["{$concepto}"]["{$row['tipo']}"."{$row['tipo']}"] = valTpDato(number_format("{$row['porSaldo']}", 1, ".", ","),"cero_por_vacio");
			} else{
				$gastos["{$concepto}"]["{$row['tipo']}"."{$row['tipo']}"] = "0.0";
			}
		}
	}
	$cont_rows = 0;
	
	foreach ($gastos3 as $valor){
		$cont_rows++;
		$ventas .= "$valor";
			
		if ($valor !== end($gastos3)) {
			$ventas .= "|";
		}
	}

	$cont_rows = 0;

	foreach ($gastos4 as $valor){
		$cont_rows++;
		$postVentas .= "$valor";
			
		if ($valor !== end($gastos4)) {
			$postVentas .= "|";
		}
	}
	
	$cont_rows = 0;

	foreach ($gastos5 as $valor){
		$cont_rows++;
		$nomina .= "$valor";
			
		if ($valor !== end($gastos5)) {
			$nomina .= "|";
		}
	}
// 	$objResponse->alert(print_r($postVentas, true));
	$htmlTb .= "<input type='hidden' id='ventas' name='ventas' value='{$ventas}'></input>";
	$htmlTb .= "<input type='hidden' id='postVenta' name='postVenta' value='{$postVentas}'></input>";
	$htmlTb .= "<input type='hidden' id='nomina' name='nomina' value='{$nomina}'></input>";
	
	//SUB TOTAL SALARIOS VENTAS
	$subSalarioVent = 	$gastos2['Empleados de Mantenimiento y Seguridad'][1]+
						$gastos2['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)'][1]+
						$gastos2['Salario Fijo de Vendedores'][1]+
						$gastos2['Salario Gerente de Ventas'][1];
	
	//SUB TOTAL SALARIOS POST VENTAS
	$subSalarioPost =	$gastos2['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)'][2]+
						$gastos2['Salarios Vendedores de Piezas (P&A)'][2]+
						$gastos2['Salario de Asesores de Servicio'][2]+
						$gastos2['Salario Gerente de Servicio'][2]+
						$gastos2['Salario Jefe de Taller'][2]+
						$gastos2['Empleados de Mantenimiento y Seguridad'][2]+
						$gastos2['Salario Gerente de Piezas'][2];

	//TOTAL VENTAS
	$totalVentas = 	$subSalarioVent +
					$gastos2['Desempleo (Federal, Estatal)'][1]+
					$gastos2['Seguro de Incapacidad'][1]+
					$gastos2['Beneficios generales empelados'][1]+
					$gastos2['Seguro Social'][1]+
					$gastos2['Renta'][1]+
					$gastos2['Property Taxes'][1]+
					$gastos2['Teléfono'][1]+
					$gastos2['Utilidades'][1]+
					$gastos2['Depreciación'][1]+
					$gastos2['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)'][1]+
					$gastos2['Seguros de Propiedad, Transportación, entre otros'][1]+
					$gastos2['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)'][1]+
					$gastos2['Misceláneos'][1];

					
	//TOTAL GENERALES DEL CONCESIONARIO
	$totalGenConce = 	$gastos2['Materiales de Oficina'][3]+
						$gastos2['Publicidad'][3]+
						$gastos2['Mercadeo'][3]+
						$gastos2['Contabilidad'][3]+
						$gastos2['Recursos Humanos'][3]+
						$gastos2['Sistema, Informática, Asistencia Técnica'][3]+
						$gastos2['Legal & Audit Fees'][3]+
						$gastos2['Alta Gerencia'][3];
	
	$totalPostV = 	$subSalarioPost+
					$gastos2['Misceláneos'][2]+
					$gastos2['Suplidores Externos (si no se considera en piezas o servicios)'][2]+
					$gastos2['Servicios Externos de Servicio'][2]+
					$gastos2['Seguros de Propiedad, Transportación, entre otros'][2]+
					$gastos2['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)'][2]+
					$gastos2['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)'][2]+
					$gastos2['Utilidades'][2]+
					$gastos2['Teléfono'][2]+
					$gastos2['Mantenimiento de Equipos, Facilidades y Reparaciones'][2]+
					$gastos2['Depreciación'][2]+
					$gastos2['Property Taxes'][2]+
					$gastos2['Renta'][2]+
					$gastos2['Seguro Social'][2]+
					$gastos2['Beneficios generales empleados'][2]+
					$gastos2['Seguro de Incapacidad'][2]+
					$gastos2['Desempleo (Federal, Estatal)'][2];
	
	$totalGasto = $totalVentas + $totalGenConce + $totalPostV;

	$contFila = 0;
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\" style=\"font-size:10px; font-weight:bold; padding:8px; text-shadow:3px 3px 0;\">";
		$htmlTh .= "<td width=\"2%\"><p class=\"textoAzul\">Posventas</p></td>
					<td width=\"2%\"><p class=\"textoAzul\">Ventas</p></td>";
	$htmlTh .= "</tr>";

	// SUB-CABECERA DE LA TABLA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr class=\"tituloColumna\">";
					$htmlTb .= "<td align=\"center\" width=\"65%\">Conceptos</td>";
					$htmlTb .= "<td align=\"center\" width=\"25%\">Gasto</td>";
					$htmlTb .= "<td align=\"center\" width=\"10%\">%</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"tituloColumna\">";
					$htmlTb .= "<td align=\"center\" width=\"65%\">Conceptos</td>";
					$htmlTb .= "<td align=\"center\" width=\"25%\">Gasto</td>";
					$htmlTb .= "<td align=\"center\" width=\"10%\">%</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
	
	// SALARIO GERENTE DE PIEZAS Y VENTAS
	$contFila++;
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Salario Gerente de Piezas</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Salario Gerente de Piezas'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Salario Gerente de Piezas'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Salario Gerente de Ventas</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Salario Gerente de Ventas'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Salario Gerente de Ventas'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// SALARIO JEFE DE TALLER
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Salario Jefe de Taller</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Salario Jefe de Taller'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Salario Jefe de Taller'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\"></td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\"></td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\"></td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// SALARIO GERENTE DE SERVICIO Y SALARIO FIJO DE VENDEDORES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Salario Gerente de Servicio</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">-&nbsp&nbsp&nbsp</td>"; //$gastos['Salario Gerente de Servicio'][2]*/
					$htmlTb .= "<td align=\"right\" width=\"10%\"> 0.0 %</td>"; //$gastos['Salario Gerente de Servicio'][22]
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Salario Fijo de Vendedores</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Salario Fijo de Vendedores'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Salario Fijo de Vendedores'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// SALARIO DE ASESORES DE SERVICIO Y SALARIOS CLERICALES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Salario de Asesores de Servicio</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Salario de Asesores de Servicio'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Salario de Asesores de Servicio'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// SALARIOS VENDEDORES DE PIEZAS (P&A) Y EMPLEADOS DE MANTENIMIENTO Y SEGURIDAD
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Salarios Vendedores de Piezas (P&A)</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Salarios Vendedores de Piezas (P&A)'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Salarios Vendedores de Piezas (P&A)'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Empleados de Mantenimiento y Seguridad</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Empleados de Mantenimiento y Seguridad'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Empleados de Mantenimiento y Seguridad'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// SALARIOS CLERICALES POSVENTA Y SUBTOTAL SALARIO VENTAS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Salarios Clericales (Cajeras, Secretarias, Recepcionista, etc.)'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\">"."Sub Total Salarios:"."</td>";
					$htmlTb .= "<td>".valTpDato(number_format("{$subSalarioVent}", 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// EMPLEADOS DE MANTENIMIENTO Y SEGURIDAD Y DESEMPLEO (FEDERAL, ESTATAL)
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Empleados de Mantenimiento y Seguridad</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Empleados de Mantenimiento y Seguridad'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Empleados de Mantenimiento y Seguridad'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Desempleo (Federal, Estatal)</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Desempleo (Federal, Estatal)'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Desempleo (Federal, Estatal)'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// SUB TOTAL SALARIOS POSVENTA Y SEGURO DE INCAPACIDAD
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\">"."Sub Total Salarios:"."</td>";
					$htmlTb .= "<td>".valTpDato(number_format("{$subSalarioPost}", 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Seguro de Incapacidad</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Seguro de Incapacidad'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Seguro de Incapacidad'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// DESEMPLEO (FEDERAL, ESTATAL) Y BENEFICIOS GENERALES EMPELADOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Desempleo (Federal, Estatal)</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Desempleo (Federal, Estatal)'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Desempleo (Federal, Estatal)'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Beneficios generales empelados</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Beneficios generales empelados'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Beneficios generales empelados'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// SEGURO DE INCAPACIDAD Y SEGURO SOCIAL
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Seguro de Incapacidad</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Seguro de Incapacidad'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Seguro de Incapacidad'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Seguro Social</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Seguro Social'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Seguro Social'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// BENEFICIOS GENERALES EMPLEADOS Y RENTA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Beneficios generales empleados</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Beneficios generales empleados'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Beneficios generales empleados'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Renta</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Renta'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Renta'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// SEGURO SOCIAL Y PROPERTY TAXES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Seguro Social</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Seguro Social'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Seguro Social'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Property Taxes</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Property Taxes'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Property Taxes'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// RENTA Y TELÉFONO
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Renta</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Renta'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Renta'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Teléfono</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Teléfono'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Teléfono'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// PROPERTY TAXES Y UTILIDADES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Property Taxes</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Property Taxes'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Property Taxes'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Utilidades</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Utilidades'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Utilidades'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// DEPRECIACIÓN Y DEPRECIACIÓN
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Depreciación</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Depreciación'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Depreciación'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Depreciación</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Depreciación'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Depreciación'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// TELÉFONO Y PATENTES, CONTRIBUCIONES Y GASTOS GUBERNAMENTALES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Teléfono</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Teléfono'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Teléfono'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// UTILIDADES, SEGUROS DE PROPIEDAD, TRANSPORTACIÓN, ENTRE OTROS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Utilidades</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Utilidades'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Utilidades'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Seguros de Propiedad, Transportación, entre otros</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Seguros de Propiedad, Transportación, entre otros'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Seguros de Propiedad, Transportación, entre otros'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// PATENTES, CONTRIBUCIONES Y GASTOS GUBERNAMENTALES,Company CARS Y GASTOS ASOCIADOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Patentes, Contribuciones y Gastos Gubernamentales (fijos y semifijos)'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// SEGUROS DE PROPIEDAD, TRANSPORTACIÓN, MISCELÁNEOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Company Cars y Gastos Asociados (Gasolina, Mant, entre otros)'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Misceláneos</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Misceláneos'][1]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Misceláneos'][11]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// COMPANY CARS Y GASTOS ASOCIADOS, TOTAL VENTAS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Seguros de Propiedad, Transportación, entre otros</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Seguros de Propiedad, Transportación, entre otros'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Seguros de Propiedad, Transportación, entre otros'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\">"."Total:"."</td>";
					$htmlTb .= "<td>".valTpDato(number_format("{$totalVentas}", 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// Mantenimiento de Equipos, Facilidades y Reparaciones, TRANSPORTACIÓN, MISCELÁNEOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Mantenimiento de Equipos, Facilidades y Reparaciones</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Mantenimiento de Equipos, Facilidades y Reparaciones'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Mantenimiento de Equipos, Facilidades y Reparaciones'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"center\" width=\"100%\"><p class=\"textoAzul\">Generales del Concesionario</p></td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// Servicios Externos de Servicio, ALTA GERENCIA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Servicios Externos de Servicio</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Servicios Externos de Servicio'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Servicios Externos de Servicio'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Alta Gerencia</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Alta Gerencia'][3]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Alta Gerencia'][33]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	//Suplidores Externos, LEGAL & AUDIT FEES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Suplidores Externos (si no se considera en piezas o servicios)</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Suplidores Externos (si no se considera en piezas o servicios)'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Suplidores Externos (si no se considera en piezas o servicios)'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Legal & Audit Fees</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Legal & Audit Fees'][3]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Legal & Audit Fees'][33]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// Misceláneos / SISTEMA, INFORMÁTICA, ASISTENCIA TÉCNICA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Misceláneos</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Misceláneos'][2]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Misceláneos'][22]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Sistema, Informática, Asistencia Técnica</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Sistema, Informática, Asistencia Técnica'][3]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Sistema, Informática, Asistencia Técnica'][33]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// RECURSOS HUMANOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"center\" width=\"100%\"></td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Recursos Humanos</td>";  //".$gastos['Recursos Humanos'][3]."
					$htmlTb .= "<td align=\"right\" width=\"25%\">-&nbsp;&nbsp;&nbsp;</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">0.0 %</td>"; //$gastos['Recursos Humanos'][33]
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// CONTABILIDAD
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"center\" width=\"100%\"></td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Contabilidad</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">-&nbsp;&nbsp;&nbsp;</td>";   //".$gastos['Contabilidad'][3]."
					$htmlTb .= "<td align=\"right\" width=\"10%\">0.0 %</td>"; //$gastos['Contabilidad'][33]
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// MERCADEO
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"center\" width=\"100%\"></td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Mercadeo</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">-&nbsp;&nbsp;&nbsp;</td>";  //".$gastos['Mercadeo'][3]."
					$htmlTb .= "<td align=\"right\" width=\"10%\">0.0 %</td>"; //$gastos['Mercadeo'][33]
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// TOTAL, PUBLICIDAD
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\">"."Total:"."</td>";
					$htmlTb .= "<td>".valTpDato(number_format("{$totalPostV}", 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Publicidad</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Publicidad'][3]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Publicidad'][33]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// MATERIALES DE OFICINA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"center\" width=\"100%\"></td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"left\" width=\"65%\">Materiales de Oficina</td>";
					$htmlTb .= "<td align=\"right\" width=\"25%\">".$gastos['Materiales de Oficina'][3]."</td>";
					$htmlTb .= "<td align=\"right\" width=\"10%\">".$gastos['Materiales de Oficina'][33]." %</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// TOTAL
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"center\" width=\"100%\"></td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\">"."Total:"."</td>";
					$htmlTb .= "<td>".valTpDato(number_format("{$totalGenConce}", 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// ESPACIO EN BLANCO
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"center\" width=\"100%\"></td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td width=\"50%\">";
			$htmlTb .= "<table cellpadding=\"2\" width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
					$htmlTb .= "<td align=\"center\" width=\"100%\"></td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	// TOTAL GASTOS GENERALES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\">";
		$htmlTb .= "<td colspan='2' width=\"50%\">";
			$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
					$htmlTb .= "<td class=\"tituloCampo\">"."Total Gastos Generales:"."</td>";
					$htmlTb .= "<td>".valTpDato(number_format("{$totalGasto}", 2, ".", ","),"cero_por_vacio")."</td>";
					$htmlTb .= "<input type='hidden' id='totalGasto' name='totalVentaRep' value='{$totalGasto}'></input>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";

	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
				$htmlTh .= "<tr>";
					$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
					$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">Gastos Generales (Todo gasto referente a la operación general del concesionario)</p>"."</td>";
				$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";

	$htmlTableFin.= "</table>";

	$objResponse->assign("divListaGastoGeneral","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);

	$htmlTblIni="";
	$htmlTh="";
	$htmlTb="";
	$htmlTblFin="";
	
	//CALCULAR INDICADORES
	//CALCULO DE UTILIDADES
	$utlServicio = $formTotalVentas['totalVentaServ'] - $formTotalCostos['totalCostoMo'];
	$utlServicioCon = valTpDato(number_format($utlServicio, 2, ".", ","),"cero_por_vacio");
	
	$utlRespuesto = $formTotalVentas['totalVentaRep'] - $formTotalCostos['totalCostoRep'];
	$utlRespuestoCon = valTpDato(number_format($utlRespuesto, 2, ".", ","),"cero_por_vacio");
	
	$totalUtl = $utlServicio + $utlRespuesto;
	$totalUtlCon = valTpDato(number_format($totalUtl, 2, ".", ","),"cero_por_vacio");
	
	$totalUtlInc = $formTotalVentas['totalVentas'] - $formTotalCostos['totalCostoIncTo'];
	$totalUtlIncCon = valTpDato(number_format($totalUtlInc, 2, ".", ","),"cero_por_vacio");
	
	$utlServicioPor = ($utlServicio / $totalUtl)*100;
	$utlServicioPorCon = valTpDato(number_format($utlServicioPor, 0, ".", ","),"cero_por_vacio");
	
	$utlRespuestoPor = ($utlRespuesto / $totalUtl)*100;
	$utlRespuestoPorCon = valTpDato(number_format($utlRespuestoPor, 0, ".", ","),"cero_por_vacio");
	
	//CALCULO DE DESEMPEÑO
	
	$TotalUtLPos = valTpDato(number_format($totalUtl, 2, ".", ","),"cero_por_vacio");
	$TotalUtLPosInc = valTpDato(number_format($totalUtlInc, 2, ".", ","),"cero_por_vacio");
	$TotalGasto = valTpDato(number_format($totalGasto, 2, ".", ","),"cero_por_vacio");
	
	$absPostventa = (($TotalUtLPos/$TotalGasto)*100);
	$absMO = (($TotalUtLPos/$TotalGasto)*100);
	$absorcionPostventa = valTpDato(number_format($absPostventa, 0, ".", ","),"cero_por_vacio");
	$absorcionMO = valTpDato(number_format($absMO, 0, ".", ","),"cero_por_vacio");
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
	$htmlTh .= "<td width=\"60%\">Conceptos</td>
						<td width=\"40%\">Facturado</td>";
	$htmlTh .= "</tr>";
	
	// TOTAL UTILIDAD POSVENTAS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
	$htmlTb .= "<td align=\"left\">Total Utilidad Posventas</td>";
	$htmlTb .= "<td>$TotalUtLPos</td>";
	$htmlTb .= "<input type='hidden' id='TotalUtLPos' name='TotalUtLPos' value='{$totalUtl}'></input>";
	$htmlTb .= "</tr>";
	
	// TOTAL UTILIDAD POSVENTA CONSIDERANDO INCENTIVOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
	$htmlTb .= "<td align=\"left\">Total Utilidad Posventa Considerando Incentivos</td>";
	$htmlTb .= "<td>$TotalUtLPosInc</td>";
	$htmlTb .= "<input type='hidden' id='totalUtlInc' name='totalUtlInc' value='{$totalUtlInc}'></input>";
	$htmlTb .= "</tr>";
	
	// TOTAL GASTOS GENERALES
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
	$htmlTb .= "<td align=\"left\">Total Gastos Generales</td>";
	$htmlTb .= "<td>".$TotalGasto."</td>";
	$htmlTb .= "<input type='hidden' id='totalGastoGen' name='totalGastoGen' value='{$totalGasto}'></input>";
	$htmlTb .= "</tr>";
	
	// especio en blanco
	$htmlTb .= "<tr align=\"right\" height=\"24\">";
	$htmlTb .= "<td></td>";
	$htmlTb .= "<td></td>";
	$htmlTb .= "</tr>";
	
	// ABSORCIÓN FINANCIERA DE POSVENTA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
	$htmlTb .= "<td class=\"tituloCampo\">Absorción Financiera de Posventa</td>";
	$htmlTb .= "<td>".$absorcionPostventa."&nbsp;%&nbsp;&nbsp;&nbsp;</td>";
	$htmlTb .= "<input type='hidden' id='totalAFP' name='totalAFP' value='{$absPostventa}'></input>";
	$htmlTb .= "</tr>";
	
	// ABSORCIÓN FINANCIERA CONSIDERANDO COMISION MANO DE OBRA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
	$htmlTb .= "<td class=\"tituloCampo\">Absorción Financiera Considerando Comision Mano de Obra</td>";
	$htmlTb .= "<td>".$absorcionMO."&nbsp;%&nbsp;&nbsp;&nbsp;</td>";
	$htmlTb .= "<input type='hidden' id='totalAFPMO' name='totalAFPMO' value='{$absMO}'></input>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
	$htmlTh .= "<td colspan=\"14\">";
	$htmlTh .= "<table width=\"100%\">";
	$htmlTh .= "<tr>";
	$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
	$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">Indicador de Desempeño</p>"."</td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "</table>";
	$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaIndicador","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	$objResponse->script("$('.btnReportes').show();");
	
	return $objResponse;
}

function generarGastosGenerales($frmCierreMensual) {

	$totalGasto = '';

	foreach ($frmCierreMensual As $key => $value){

		$var_comp = substr($key, -4);

		if($var_comp == 'Post'){
			$valuePost = valTpDato(getmysqlnum($value),"cero_por_vacio");
			$totalGasto['totalGastoPost'] += $valuePost;
		} elseif($var_comp == 'Vent'){
			$valueVent = valTpDato(getmysqlnum($value),"cero_por_vacio");
			$totalGasto['totalGastoVent'] += $valueVent;
		} elseif($var_comp == 'Cons'){
			$valueCons = valTpDato(getmysqlnum($value),"cero_por_vacio");
			$totalGasto['totalGastoCons'] += $valueCons;
		}
	}
	return $totalGasto;
}

function guardarCierreMensual($frmCierreMensual) {
	$objResponse = new xajaxResponse();

	$idEmpresa = $frmCierreMensual['txtIdEmpresa'];
	$arrayFecha = explode("/", $frmCierreMensual['lstMesAno']);
	$mesCierre = $arrayFecha[0];
	$anoCierre = $arrayFecha[1];

	mysql_query("START TRANSACTION;");

	$totalGasto = generarGastosGenerales($frmCierreMensual);

	// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
	$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s;",
			valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowUsuario = mysql_fetch_assoc($rsUsuario);

	// INSERTA LOS DATOS DEL CIERRE MENSUAL
	$insertSQL = sprintf("INSERT INTO if_cierre_mensual (id_empresa, mes, ano, id_empleado_creador)
							SELECT
								config_emp.id_empresa,
								%s,
								%s,
								%s
							FROM pg_configuracion_empresa config_emp
							WHERE config_emp.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato($mesCierre, "int"),
			valTpDato($anoCierre, "int"),
			valTpDato($rowUsuario['id_empleado'], "int"),
			valTpDato($idEmpresa, "int"));

	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idCierreMensual = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");

	mysql_query("COMMIT;");

	$objResponse->alert("Cierre Creado Satisfactoriamente");
	$objResponse->script("byId('btnCerrarCierre').click();");

	return $objResponse;
}

function guardarConcepto($frmConcepto, $editConcepto = '') {
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");

	// BUSCA SI EL CONCEPTO ESTA REGISTRADO
	$queryConcepto = sprintf("SELECT * FROM if_absorcion_conceptos
								WHERE nom_concepto LIKE %s 
									AND tipo = %s;",
						valTpDato($frmConcepto['textConcepto'], "text"),
						valTpDato($frmConcepto['tipoVentaConcep'], "int"));
	
	$rsConcepto = mysql_query($queryConcepto);
	if (!$rsConcepto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$num_rows = mysql_num_rows($rsConcepto);

	if($num_rows > 0 && $editConcepto == ''){
		$objResponse->alert("El Concepto con el tipo de Venta ya se encuentra Registrado");
		return $objResponse;
	}
	
	if($editConcepto != ''){
		$updateSQL = sprintf("UPDATE if_absorcion_conceptos SET
									nom_concepto = %s, 
									tipo = %s
								WHERE id_concepto = %s",
						valTpDato($frmConcepto['editConceptoFrm'], "text"),
						valTpDato($frmConcepto['editVentaConcep'], "int"),
						valTpDato($frmConcepto['id_conceptoEdit'], "int"));
		
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		mysql_query("COMMIT;");

		$objResponse->alert("Concepto editado Satisfactoriamente");
		$objResponse->script("$('#frmEditConcepto').hide(); $('#frmConceptoCuenta').show();");
		$objResponse->script("byId('aNuevoConcepto').click();");
	
		return $objResponse;
	} else{
		
		$insertSQL = sprintf("INSERT INTO if_absorcion_conceptos (nom_concepto, tipo)
								VALUE (%s, %s);",
						valTpDato($frmConcepto['textConcepto'], "text"),
						valTpDato($frmConcepto['tipoVentaConcep'], "int"));
	
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		
		mysql_query("COMMIT;");

		$objResponse->script("byId('apConceptos').click();");
		$objResponse->alert("Concepto creado Satisfactoriamente");
		$objResponse->script("byId('aNuevoConcepto').click();");
	
		return $objResponse;
	}
}

function guardarTipoCuenta($frmTiposCuenta, $editTipoCuenta = '') {
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");

	$nombreCuenta = $frmTiposCuenta['textAddTipoCuenta'];
	$editNombreCuenta = $frmTiposCuenta['textEditTipoCuenta'];
	$numeroIdentificador = $frmTiposCuenta['lstTiposCuenta2'];
	$editNumeroIdentificador = $frmTiposCuenta['lstTiposCuenta3'];
	
	if($editTipoCuenta == ''){
	// BUSCA SI EL TIPO DE CUENTA ESTA REGISTRADO
		$queryConcepto = sprintf("SELECT * FROM if_absorcion_tipos_cuenta
									WHERE nombre_cuenta LIKE %s
										OR numero_identificador = %s;",
							valTpDato($nombreCuenta, "text"),
							valTpDato($numeroIdentificador, "int"));
	
		$rsConcepto = mysql_query($queryConcepto);
		if (!$rsConcepto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$num_rows = mysql_num_rows($rsConcepto);
	}
	if($num_rows > 0 && $editTipoCuenta == ''){
		$objResponse->alert("El Nombre o Tipo de Cuenta ya se encuentra Registrado en el Sistema.");
		return $objResponse;
	}
	$fechaModificacion = date("Y-m-d H:i:s");
	if($editTipoCuenta != ''){
		$updateSQL = sprintf("UPDATE 	if_absorcion_tipos_cuenta SET
										nombre_cuenta = %s,
										numero_identificador = %s,
										fecha_modificacion = %s,
										id_usuario_ultima_modificacion = %s
									WHERE numero_identificador = %s",
						valTpDato($editNombreCuenta, "text"),
						valTpDato($editNumeroIdentificador, "int"),
						valTpDato($fechaModificacion, "text"),
						valTpDato($_SESSION['idUsuarioSysGts'], "int"),
						valTpDato($frmTiposCuenta['id_editTipoCuenta'], "int"));

		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");

		mysql_query("COMMIT;");

		$objResponse->alert("EL Tipo de Cuenta a sido editado Satisfactoriamente");
		
		$objResponse->script("$('#editarTiposCuenta').hide(); $('#principalTiposCuenta').show()");
		$objResponse->script("document.forms['frmTiposCuenta'].reset();");
		$objResponse->script("byId('aTiposCuenta').click();");
		
		return $objResponse;
	} else{

		$insertSQL = sprintf("INSERT INTO if_absorcion_tipos_cuenta (nombre_cuenta, numero_identificador, id_usuario_creador, id_empresa)
								VALUE (%s, %s, %s, %s);",
						valTpDato($nombreCuenta, "text"),
						valTpDato($numeroIdentificador, "int"),
						valTpDato($_SESSION['idUsuarioSysGts'], "int"),
						valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");

		mysql_query("COMMIT;");

		$objResponse->alert("Tipo de Cuenta creado Satisfactoriamente");
		$objResponse->script("byId('apTiposCuenta').click();");
		$objResponse->script("byId('aTiposCuenta').click();");
		$objResponse->script("document.forms['frmTiposCuenta'].reset();");

		return $objResponse;
	}
}

function imprimirReporteAFP($frmBuscar, $frmVentas, $frmCostos, $frmUtilidad, $frmIndicadores, $frmGastoGeneral, $pdf) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s",
			$frmBuscar['lstEmpresa'],
			$frmBuscar['txtFecha']);

	$valBusq2 = sprintf("%s|%s|%s",
			$frmVentas['totalVentaServ'],
			$frmVentas['totalVentaRep'],
			$frmVentas['totalVentas']);

	$valBusq3 = sprintf("%s|%s|%s|%s|%s",
			$frmCostos['totalCostoMo'],
			$frmCostos['totalCostoInc'],
			$frmCostos['totalCostoRep'],
			$frmCostos['totalCostos'],
			$frmCostos['totalCostoIncTo']);

	$valBusq4 = sprintf("%s|%s|%s|%s",
			$frmUtilidad['utlServicio'],
			$frmUtilidad['utlRespuesto'],
			$frmUtilidad['totalUtl'],
			$frmUtilidad['totalUtlInc']);

	$valBusq5 = sprintf("%s|%s|%s|%s|%s",
			$frmIndicadores['TotalUtLPos'],
			$frmIndicadores['totalUtlInc'],
			$frmIndicadores['totalGastoGen'],
			$frmIndicadores['totalAFP'],
			$frmIndicadores['totalAFPMO']);
	
	$valBusq6 = sprintf("%s!%s!%s",
			$frmGastoGeneral['ventas'],
			$frmGastoGeneral['postVenta'],
			$frmGastoGeneral['nomina']);
	
	$cmb = "&";
	$sust = "*";
	
	$valBusq6 = str_replace($cmb, $sust, $valBusq6);
	
	if($pdf){
		$objResponse->script(sprintf("verVentana('reportes/if_reporte_financiera_pdf.php?valBusq=%s&lstDecimalPDF=%s&valBusq2=%s&valBusq3=%s&valBusq4=%s&valBusq5=%s&valBusq6=%s', 1000, 500);", $valBusq, $frmBuscar['lstDecimalPDF'],$valBusq2, $valBusq3, $valBusq4, $valBusq5, $valBusq6));
	} else {
		$objResponse->script(sprintf("window.open('reportes/if_reporte_financiera_excel.php?valBusq=%s&lstDecimalPDF=%s&valBusq2=%s&valBusq3=%s&valBusq4=%s&valBusq5=%s&valBusq6=%s', '_self');", $valBusq, $frmBuscar['lstDecimalPDF'], $valBusq2, $valBusq3, $valBusq4, $valBusq5, $valBusq6));
	}

	$objResponse->assign("tdlstDecimalPDF","innerHTML","");

	return $objResponse;
}

function insertarCuenta($codCuenta, $idConcepto) {

	$objResponse = new xajaxResponse();
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	mysql_query("START TRANSACTION;");
	
	$query = sprintf("SELECT *
						FROM if_absorcion_financiera
						WHERE (descripcion LIKE %s)",
					valTpDato($codCuenta, "text"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$num_rows = mysql_num_rows($rs);

	if(!($num_rows > 0)){
		
		$query = sprintf("SELECT * FROM sipre_contabilidad.cuenta 
							WHERE SUBSTRING(codigo, 1, 1) IN (SELECT numero_identificador FROM if_absorcion_tipos_cuenta) 
							AND LENGTH(codigo) > 10
							AND codigo = '%s'",
					$codCuenta);
		mysql_query("SET NAMES 'utf8';");
	
		$Result1 = mysql_query($query);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
		$rowCuenta = mysql_fetch_assoc($Result1);
		
		$insertSQL = sprintf("INSERT INTO if_absorcion_financiera (descripcion, codigo, id_empresa, id_concepto)
								VALUE (%s, %s, %s, %s);",
						valTpDato($rowCuenta['descripcion'], "text"),
						valTpDato($rowCuenta['codigo'], "text"),
						valTpDato($idEmpresa, "int"),
						valTpDato($idConcepto, "int"));

		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
			
		$objResponse->alert("La Cuenta ha sido Asociada Exitosamente");
		$objResponse->script("byId('btnBuscarCat').click();");
		$objResponse->script("byId('btnCerrarCuentaConta').click();");
	} else{
		$objResponse->alert("Esta Cuenta ya se encuentra incluida");
		return $objResponse;
	}

	mysql_query("COMMIT;");
	
	return $objResponse;
	
}

function listaCierreMensual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	global $mes;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre_mensual.id_empresa = %s",
				valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("mes LIKE %s",
				valTpDato($valCadBusq[1], "text"));
	}

	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ano < %s",
				valTpDato($valCadBusq[2], "text"));
	}

	$query = sprintf("SELECT
							cierre_mensual.*,
							CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
					
							IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
					
						FROM vw_iv_empresas_sucursales vw_iv_emp_suc
							INNER JOIN if_cierre_mensual cierre_mensual ON (vw_iv_emp_suc.id_empresa_reg = cierre_mensual.id_empresa)
							INNER JOIN pg_empleado empleado ON (cierre_mensual.id_empleado_creador = empleado.id_empleado) %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" class='form-4' width=\"100%\">";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "8%", $pageNum, "fecha_creacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
			$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "32%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
			$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "30%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado Creador"));
			$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "10%", $pageNum, "mes", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Mes"));
			$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "10%", $pageNum, "ano", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("A&ntilde;o"));
// 			$htmlTh .= "<td></td>";
		$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_creacion']))."<br>".date("h:ia", strtotime($row['fecha_creacion']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td align=\"center\">".str_pad($row['mes'], 2, "0", STR_PAD_LEFT)."<br>(".$mes[$row['mes']].")</td>";
			$htmlTb .= "<td align=\"center\">".$row['ano']."</td>";
// 			$htmlTb .= "<td align=\"center\">";
// 				if ($row['estatus'] == 0) {
// 					$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAprobarCierre%s\" src=\"../img/iconos/accept.png\" onclick=\"validarFrmAprobarCierre('%s');\" title=\"".utf8_encode("Aprobar Cierre")."\">",
// 							$contFila,
// 							$row['id_cierre_mensual']);
// 				}
// 			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
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
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
												0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum > 0) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
												max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"100\">";

									$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s)\">",
											"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
										for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
											$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
										}
									$htmlTf .= "</select>";
										
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
												min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaCierreMensual","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}


function listaConcepGasto(){
	$objResponse = new xajaxResponse();
		
	$query = '';
	
	$html = "<select id='concepGasto' name='concepGasto' style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
			foreach ($array as $indice => $valor) {
				$selected = ($selId == $indice || count($array) == 1) ? "selected=\"selected\"" : "";
		
				$html .= "<option ".$selected." value=\"".($indice)."\">".$indice.".- ".($array[$indice])."</option>";
			}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	
	return $objResponse;
}

function listaCuenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo LIKE %s",
				valTpDato($valCadBusq[1].'%', "text"));
	}
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nom_concepto LIKE %s",
				valTpDato('%'.$valCadBusq[4].'%', "text"));
	}

	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo = %s",
				valTpDato($valCadBusq[6], "int"));
	}
	
	$query = sprintf("SELECT * FROM vw_if_absorcion_cuentas 
						%s GROUP BY id_concepto ",
				$sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		$rs2 = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" class='form-4' width=\"100%\">";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td></td>";
			$htmlTh .= ordenarCampo("xajax_listaCuenta", "8%", $pageNum, "id_absorcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Id ");
			$htmlTh .= ordenarCampo("xajax_listaCuenta", "55%", $pageNum, "nom_concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Concepto");
			$htmlTh .= ordenarCampo("xajax_listaCuenta", "15%", $pageNum, "tipo_gasto", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Gasto");
			$htmlTh .= "<td></td>";
			$htmlTh .= "<td></td>";
			$htmlTh .= "<td></td>";
		$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$idAbs = $row['id_absorcion'];
		$row['nom_concepto'] = utf8_encode($row['nom_concepto']);
		
		$htmlTb.= sprintf("<tr class='$clase' height=\"22\">");
			$htmlTb .= "<td></td>";
			$htmlTb .= "<td align='right'>{$row['id_absorcion']}&nbsp;&nbsp;</td>";
			$htmlTb .= "<td align='left'>&nbsp;&nbsp;{$row['nom_concepto']}</td>";
			$htmlTb .= "<td align='left'>&nbsp;&nbsp;{$row['tipo_gasto']}</td>";
			$htmlTb .= "<td><a class='modalImg' id='aEditar6' rel='#divFlotante6' onclick=\"abrirDivFlotante(this,'frmAddCuenta','tdFlotanteTitulo6', {$row['id_concepto']}, 'tblListaConceptoAddCuenta');\"><img class='puntero' src=\"../img/iconos/add.png\" title='Agregar'></a></td>";
			$htmlTb .= "<td><a class='modalImg' id='aEditar5' rel='#divFlotante5' onclick=\"abrirDivFlotante(this,'frmListaCuenta','tdFlotanteTitulo5', {$row['id_concepto']}, 'tblListaCuenta');\"><img class='puntero' src=\"../img/iconos/pencil.png\" title='Editar'></a></td>";
			$htmlTb .= "<td><img class='puntero' onclick=\"xajax_confirmarCuenta('',{$row['id_concepto']});\" src='../img/iconos/delete.png' title='Eliminar'/></td>";
		$htmlTb .="</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"17\">";
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
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
												0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum > 0) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
												max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"100\">";
									$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCuenta(%s,'%s','%s','%s',%s)\">",
											"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
										for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
											$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
										}
									$htmlTf .= "</select>";
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
												min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaCuentas","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

function listaCuentaPorConcepto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT * FROM vw_if_absorcion_cuentas
						WHERE id_concepto = %s",
				valTpDato($valBusq, "int"));
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);	
	
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		$rs2 = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class='form-4' width=\"100%\">";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td width='6%'></td>";
			$htmlTh .= ordenarCampo("xajax_listaCuentaPorConcepto", "15%", $pageNum, "codigo", $campOrd, $tpOrd, $valBusq, $maxRows, "Codigo");
			$htmlTh .= ordenarCampo("xajax_listaCuentaPorConcepto", "42%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
			$htmlTh .= ordenarCampo("xajax_listaCuentaPorConcepto", "42%", $pageNum, "fecha_creacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha de Creación");
			$htmlTh .= "<td></td>";
		$htmlTh .= "</tr>";
	
		while ($row = mysql_fetch_assoc($rsLimit)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
		
			$htmlTb.= sprintf("<tr class='$clase' height=\"22\">");
				$nom_descripcion = utf8_encode($row['descripcion']);
				$codigo = valTpDato($row['codigo'], "text");
				
				$date = new DateTime($row['fecha_creacion']);
				$fecha_creacion = $date->format(spanDateFormat);
		
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td align='right'>{$row['codigo']}&nbsp;</td>";
				$htmlTb .= "<td align='left'>&nbsp;&nbsp;{$nom_descripcion}</td>";
				$htmlTb .= "<td align='center'>&nbsp;&nbsp;{$fecha_creacion}</td>";
				$htmlTb .= "<td><img class='puntero' onclick='xajax_confirmarCuenta({$row['id_absorcion']});' src='../img/iconos/delete.png' title='Eliminar'/></td>";
			$htmlTb .="</tr>";
		}
	
		$htmlTf = "<tr>";
			$htmlTf .= "<td align=\"center\" colspan=\"17\">";
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
											$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaPorConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
													0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
										}
									$htmlTf .= "</td>";
									$htmlTf .= "<td width=\"25\">";
										if ($pageNum > 0) {
											$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaPorConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
													max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
										}
									$htmlTf .= "</td>";
									$htmlTf .= "<td width=\"100\">";
										$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCuentaPorConcepto(%s,'%s','%s','%s',%s)\">",
												"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
										for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
											$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
										}
										$htmlTf .= "</select>";
									$htmlTf .= "</td>";
									$htmlTf .= "<td width=\"25\">";
										if ($pageNum < $totalPages) {
											$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaPorConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
													min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
										}
									$htmlTf .= "</td>";
									$htmlTf .= "<td width=\"25\">";
										if ($pageNum < $totalPages) {
											$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaPorConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaCuentaPorConceptos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaCuentaConcepto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	$tipoLista = $valCadBusq[5];

	$objResponse->script("$('#addCuenta').hide();$('#addConcepto').show();");
	$objResponse->script("$('#btnBuscarCuentaConcep').val('{$valCadBusq[3]}');");
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("nom_concepto LIKE %s",
				valTpDato('%'.$valCadBusq[2].'%', "text"));
	}
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo LIKE %s",
				valTpDato('%'.$valCadBusq[6].'%', "text"));
	}
	
	$query = sprintf("SELECT * FROM if_absorcion_conceptos
						%s",
				$sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		$rs2 = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" class='form-4' width=\"100%\">";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			if($tipoLista == 0) $htmlTh .= "<td width='6%'></td>";
			$htmlTh .= ordenarCampo("xajax_listaCuentaConcepto", "15%", $pageNum, "id_concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
			$htmlTh .= ordenarCampo("xajax_listaCuentaConcepto", "40%", $pageNum, "nom_concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Concepto");
			$htmlTh .= ordenarCampo("xajax_listaCuentaConcepto", "40%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Gasto");
			if($tipoLista != 0) $htmlTh .= "<td ></td>";
			if($tipoLista != 0) $htmlTh .= "<td ></td>";
		$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$funConcepto = "xajax_insertarCuenta('{$valCadBusq['3']}','{$row['id_concepto']}');";

		$htmlTb.= sprintf("<tr class='$clase' height=\"22\">");
			if($tipoLista == 0){
				$htmlTb .= "<td><button class='puntero' title='Seleccionar' onclick=$funConcepto type='button' /><img src='../img/iconos/tick.png'/></button></td>";
			}
			if($row['tipo'] == 1){
				$tipoVenta = "VENTAS";
			} elseif ($row['tipo'] == 2){
				$tipoVenta = "POSTVENTA";
			}else{
				$tipoVenta = "GENERALES";
			}
				
			$nom_concepto = utf8_encode($row['nom_concepto']);
			
			$htmlTb .= "<td align='right'>{$row['id_concepto']}&nbsp;</td>";
			$htmlTb .= "<td align='left'>&nbsp;&nbsp;{$nom_concepto}</td>";
			$htmlTb .= "<td align='left'>&nbsp;&nbsp;{$tipoVenta}</td>";
			if($tipoLista != 0) $htmlTb .= "<td><img class='puntero' onclick=\"showViewConceptos({$row['id_concepto']}, 'editar')\" src='../img/iconos/pencil.png' title='Editar'/></td>";
			if($tipoLista != 0) $htmlTb .= "<td><img class='puntero' onclick=\"xajax_confirmarConcepto({$row['id_concepto']});\" src='../img/iconos/delete.png' title='Eliminar'/></td>";
		$htmlTb .="</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"17\">";
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
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
												0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum > 0) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
												max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"100\">";
									$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCuentaConcepto(%s,'%s','%s','%s',%s)\">",
											"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
											for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
												$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
											}
									$htmlTf .= "</select>";
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
												min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaConcepto(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("txtBusCodigo","value", $valCadBusq[3]);
	
	$objResponse->assign("divListaCuentasConceptos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	$objResponse->assign("divListaCuentasConceptos2","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

function listaCuentaContabilidad($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = "AND ";
		$sqlBusq .= $cond.sprintf("codigo LIKE %s",
				valTpDato($valCadBusq[1].'%', "text"));
	}
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = "AND ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
				valTpDato('%'.$valCadBusq[2].'%', "text"));
	}
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo = %s",
				valTpDato($valCadBusq[6], "int"));
	}
	
	$query = sprintf("SELECT * FROM sipre_contabilidad.cuenta 
						WHERE SUBSTRING(codigo, 1, 1) IN (SELECT numero_identificador FROM if_absorcion_tipos_cuenta) 
							AND LENGTH(codigo) > 10 
							AND codigo NOT IN ( (SELECT descripcion FROM if_absorcion_financiera)) %s",
				$sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		$rs2 = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" class='form-4' width=\"100%\">";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td width='6%'></td>";
			$htmlTh .= ordenarCampo("xajax_listaCuentaContabilidad", "30%", $pageNum, "codigo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
			$htmlTh .= ordenarCampo("xajax_listaCuentaContabilidad", "56%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
			$htmlTh .= "<td></td>";
		$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$idAbs = $row['id_absorcion'];
		$codigo = "xajax_listaCuentaConcepto(0,'id_concepto','ASC','|||{$row['codigo']}')";

		$htmlTb.= sprintf("<tr class='$clase' height=\"22\">");
				$htmlTb .= "<td><button class='puntero' title='Seleccionar' onclick=$codigo type='button' /><img src='../img/iconos/tick.png'/></button></td>";
				$htmlTb .= "<td align='right'>{$row['codigo']}&nbsp;</td>";
				$htmlTb .= "<td align='left'>&nbsp;&nbsp;{$row['descripcion']}</td>";
				$htmlTb .= "<td></td>";
		$htmlTb .="</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"17\">";
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
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaContabilidad(%s,'%s','%s','%s',%s);\">%s</a>",
												0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum > 0) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaContabilidad(%s,'%s','%s','%s',%s);\">%s</a>",
												max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"100\">";
									$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCuentaContabilidad(%s,'%s','%s','%s',%s)\">",
											"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
											for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
												$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
											}
									$htmlTf .= "</select>";
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaContabilidad(%s,'%s','%s','%s',%s);\">%s</a>",
												min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaContabilidad(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaCuentasContables","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}


function listaAddCuentaContabilidad($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = "AND ";
		$sqlBusq .= $cond.sprintf("codigo LIKE %s",
				valTpDato($valCadBusq[1].'%', "text"));
	}
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = "AND ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
				valTpDato('%'.$valCadBusq[2].'%', "text"));
	}
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo = %s",
				valTpDato($valCadBusq[6], "int"));
	}

	$query = sprintf("SELECT * FROM sipre_contabilidad.cuenta
						WHERE SUBSTRING(codigo, 1, 1) IN (SELECT numero_identificador FROM if_absorcion_tipos_cuenta) 
							AND LENGTH(codigo) > 10
							AND codigo NOT IN ( (SELECT descripcion FROM if_absorcion_financiera)) %s",
			$sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		$rs2 = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" class='form-4' width=\"100%\">";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= "<td width='6%'></td>";
			$htmlTh .= ordenarCampo("xajax_listaCuentaContabilidad", "30%", $pageNum, "codigo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
			$htmlTh .= ordenarCampo("xajax_listaCuentaContabilidad", "56%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción");
			$htmlTh .= "<td></td>";
		$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$idAbs = $row['id_absorcion'];

		$funConcepto = "xajax_insertarCuenta('{$row['codigo']}','{$valCadBusq[7]}');";
		
		$htmlTb.= sprintf("<tr class='$clase' height=\"22\">");
			$htmlTb .= "<td><button class='puntero' title='Seleccionar' onclick=$funConcepto type='button' /><img src='../img/iconos/tick.png'/></button></td>";
			$htmlTb .= "<td align='right'>{$row['codigo']}&nbsp;</td>";
			$htmlTb .= "<td align='left'>&nbsp;&nbsp;{$row['descripcion']}</td>";
			$htmlTb .= "<td></td>";
		$htmlTb .="</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"17\">";
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
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaContabilidad(%s,'%s','%s','%s',%s);\">%s</a>",
												0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum > 0) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaContabilidad(%s,'%s','%s','%s',%s);\">%s</a>",
												max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"100\">";
									$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCuentaContabilidad(%s,'%s','%s','%s',%s)\">",
											"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
									for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
										$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
									}
									$htmlTf .= "</select>";
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaContabilidad(%s,'%s','%s','%s',%s);\">%s</a>",
												min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentaContabilidad(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divListaAddCuentasContables","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

function listaTiposCuenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
				valTpDato($valCadBusq[0].'%', "int"));
	}
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("numero_identificador = %s",
				valTpDato($valCadBusq[1].'%', "int"));
	}

	$query = sprintf("SELECT * FROM if_absorcion_tipos_cuenta 
						%s",
				$sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		$rs2 = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$queryLimit);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" class='form-4' width=\"100%\">";
		$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
			$htmlTh .= ordenarCampo("xajax_listaTiposCuenta", "10%", $pageNum, "numero_identificador", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
			$htmlTh .= ordenarCampo("xajax_listaTiposCuenta", "30%", $pageNum, "nombre_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre de Cuenta");
			$htmlTh .= ordenarCampo("xajax_listaTiposCuenta", "30%", $pageNum, "fecha_creacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha de Creación");
			$htmlTh .= "<td width='10%'></td>";
			$htmlTh .= "<td width='10%'></td>";
		$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$idAbs = $row['id_absorcion'];
		$codigo = "xajax_listaCuentaConcepto(0,'id_concepto','ASC','|||{$row['codigo']}')";

		$date = new DateTime($row['fecha_creacion']);
		$fecha_creacion = $date->format(spanDateFormat);
		
		$htmlTb.= sprintf("<tr class='$clase' height=\"22\">");
			$htmlTb .= "<td align='right'>{$row['numero_identificador']}&nbsp;</td>";
			$htmlTb .= "<td align='left'>&nbsp;&nbsp;{$row['nombre_cuenta']}</td>";
			$htmlTb .= "<td align='center'>&nbsp;&nbsp;{$fecha_creacion}</td>";
			$htmlTb .= "<td onclick=\"showViewTipos({$row['numero_identificador']}, 'editar')\"><img title='Editar' src='../img/iconos/pencil.png'/></td>";
			$htmlTb .= "<td onclick=\"xajax_confirmarTipoCuenta({$row['numero_identificador']})\"><img title='Eliminar' src='../img/iconos/delete.png'/></td>";
		$htmlTb .="</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"17\">";
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
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTiposCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
												0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum > 0) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTiposCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
												max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"100\">";
									$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaTiposCuenta(%s,'%s','%s','%s',%s)\">",
											"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
										for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
											$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
										}
									$htmlTf .= "</select>";
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTiposCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
												min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig.gif\"/>");
									}
								$htmlTf .= "</td>";
								$htmlTf .= "<td width=\"25\">";
									if ($pageNum < $totalPages) {
										$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaTiposCuenta(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"17\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("divTiposCuenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

function listaSelectTiposCuenta($numSelect){
	$objResponse = new xajaxResponse();
	
	if($numSelect == 1){
		$query = sprintf("SELECT * FROM if_absorcion_tipos_cuenta 
							WHERE id_empresa = %s ORDER BY numero_identificador ASC",
					valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	} else{
		$query = sprintf("SELECT DISTINCT SUBSTRING(codigo, 1, 1) AS numero_identificador 
							FROM sipre_contabilidad.cuenta;");
	}
	
	$result = mysql_query($query);
	if (!$result) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query);
	
	$selId = 0;
	if($numSelect == 1) $onChange = "onChange=\"xajax_buscarTiposCuenta(xajax.getFormValues('frmTiposCuenta'));\"";
		else $onChange = '';
	
	$html = "<select id=\"lstTiposCuenta$numSelect\" $onChange class=\"inputHabilitado\" name=\"lstTiposCuenta$numSelect\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
			while($row = mysql_fetch_assoc($result)) {
				
				if($numSelect != 1){
					$query2 = sprintf("SELECT * FROM if_absorcion_tipos_cuenta 
											WHERE numero_identificador = %s",
									valTpDato($row['numero_identificador'], "int"));
					$rs2 = mysql_query($query2);
					if(!$rs2) return $objResponse->alert("Error ".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\n".$query2);
					$numRow = mysql_num_rows($rs2);
					
					$class = ($numRow > 0) ? "style='background-color:#FFCCCC';" : "style='background-color:#ECFCFF';" ;
				}
				
				if($numSelect == 1) $selected = ($selId == $row['id_tipo_cuenta']) ? "selected=\"selected\"" : "";
					else $selected = "";
				
				$nombre = ($numSelect == 1) ? $row['numero_identificador']."- ".utf8_encode($row['nombre_cuenta']): $row['numero_identificador'];
				$html .= sprintf("<option %s %s value=\"%s\">%s</option>",$selected, $class, $row['numero_identificador'], $nombre);
			}
	$html .= "</select>";
	
	$objResponse->assign("listaSelectTiposCuenta".$numSelect,"innerHTML",$html);

	return $objResponse;
}

function totalVentas($idEmpresa, $meses, $anio){
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	mysql_query("SET GLOBAL innodb_stats_on_metadata = 0;");
	
	$sqlBusq = " ";
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = (SELECT emp.id_empresa
															FROM pg_empresa emp
																LEFT JOIN pg_empresa emp_ppal ON (emp.id_empresa_padre = emp_ppal.id_empresa)
															ORDER BY emp.id_empresa_padre ASC
															LIMIT 1)");
	}
	
	// VERIFICA VALORES DE CONFIGURACION (Incluir Notas en el Informe Gerencial)
	$queryConfig300 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
										INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
									WHERE config.id_configuracion = 300 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig300 = mysql_query($queryConfig300);
	if (!$rsConfig300) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig300 = mysql_num_rows($rsConfig300);
	$rowConfig300 = mysql_fetch_assoc($rsConfig300);
	
	// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Producción Taller))
	$queryConfig301 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
										INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
									WHERE config.id_configuracion = 301 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig301 = mysql_query($queryConfig301);
	if (!$rsConfig301) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig301 = mysql_num_rows($rsConfig301);
	$rowConfig301 = mysql_fetch_assoc($rsConfig301);
	
	// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Servicios))
	$queryConfig302 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
										INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
									WHERE config.id_configuracion = 302 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig302 = mysql_query($queryConfig302);
	if (!$rsConfig302) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig302 = mysql_num_rows($rsConfig302);
	$rowConfig302 = mysql_fetch_assoc($rsConfig302);
	
	// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Producción Otros))
	$queryConfig304 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
									INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
								WHERE config.id_configuracion = 304 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig304 = mysql_query($queryConfig304);
	if (!$rsConfig304) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig304 = mysql_num_rows($rsConfig304);
	$rowConfig304 = mysql_fetch_assoc($rsConfig304);
	
	//VENTA TOTAL DE SERVICIOS
	
	//MANO DE OBRA
	$query = sprintf("SELECT * FROM sa_operadores operador WHERE id_operador IN (1);");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$cont = 0;
	
	while ($row = mysql_fetch_assoc($rs)) {
	
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
			
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
					valTpDato($rowConfig302['valor'], "campo"));
		}
			
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
								OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
							WHERE suc.id_empresa = a.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
		}
		
		if ($meses != "-1" && $meses != ""
			&& $anio != "-1" && $anio != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
							AND YEAR(a.fecha_filtro) = %s",
						valTpDato($meses, "date"),
						valTpDato($anio, "date"));
		}
			
		// SOLO APLICA PARA LAS MANO DE OBRA
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("operador = %s
					AND a.estado_tempario IN ('FACTURADO','TERMINADO')",
				valTpDato($row['id_operador'],"int"));
	
		$queryMO = sprintf("SELECT
									operador,
									SUM(total_tempario_orden) AS total_tempario_orden
								FROM (
									SELECT a.operador,
										(CASE a.id_modo
											WHEN 1 THEN -- UT
												(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
											WHEN 2 THEN -- PRECIO
												a.precio
										END) AS total_tempario_orden
									FROM sa_v_informe_final_tempario a %s %s
							
									UNION ALL
							
									SELECT
										a.operador,
										(-1) * (CASE a.id_modo
											WHEN 1 THEN -- UT
												(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
											WHEN 2 THEN -- PRECIO
												a.precio
										END) AS total_tempario_orden
									FROM sa_v_informe_final_tempario_dev a %s %s
									
									UNION ALL
							
									SELECT
										a.operador,
										(CASE a.id_modo
											WHEN 1 THEN -- UT
												(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
											WHEN 2 THEN -- PRECIO
												a.precio
										END) AS total_tempario_orden
									FROM sa_v_vale_informe_final_tempario a %s %s
							
									UNION ALL
							
									SELECT
										a.operador,
										(-1) * (CASE a.id_modo
											WHEN 1 THEN -- UT
												(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
											WHEN 2 THEN -- PRECIO
												a.precio
										END) AS total_tempario_orden
								
									FROM sa_v_vale_informe_final_tempario_dev a %s %s) AS q
								GROUP BY operador",
						$sqlBusq, $sqlBusq2,
						$sqlBusq, $sqlBusq2,
						$sqlBusq, $sqlBusq2,
						$sqlBusq, $sqlBusq2);
		$rsMO = mysql_query($queryMO);
		if (!$rsMO) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMO = mysql_fetch_assoc($rsMO);
			
		$totalMoServ[] = round($rowMO['total_tempario_orden'],2);
	}
	
	// VENTA DE REPUESTOS POR SERVICIOS
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");

	if (strlen($rowConfig302['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
	}

	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
	}

	if ($meses != "-1" && $meses != ""
		&& $anio != "-1" && $anio != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
					AND YEAR(a.fecha_filtro) = %s",
				valTpDato($meses, "date"),
				valTpDato($anio, "date"));
	}

	$queryRep = sprintf("SELECT
									SUM(total_repuesto_orden) AS total_repuesto_orden
								FROM (
									SELECT
										(precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
									FROM sa_v_informe_final_repuesto a %s
								
									UNION ALL
								
									SELECT
										(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
									FROM sa_v_informe_final_repuesto_dev a %s
								
									UNION ALL
								
									SELECT
										(precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
									FROM sa_v_vale_informe_final_repuesto a %s
								
									UNION ALL
								
									SELECT
										(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
									FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
								$sqlBusq,
								$sqlBusq,
								$sqlBusq,
								$sqlBusq);
	$rsRep = mysql_query($queryRep);
	if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowRep = mysql_fetch_assoc($rsRep);
		
	$totalRepServ = round($rowRep['total_repuesto_orden'],2);
	
	// VENTA DE TOT POR SERVICIOS
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");

	if (strlen($rowConfig302['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
	}

	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
	}

	if ($meses != "-1" && $meses != ""
		&& $anio != "-1" && $anio != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
						AND YEAR(a.fecha_filtro) = %s",
					valTpDato($meses, "date"),
					valTpDato($anio, "date"));
	}

	$queryTot = sprintf("SELECT
								SUM(total_tot_orden) AS total_tot_orden
							FROM (
								SELECT
									(monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
								FROM sa_v_informe_final_tot a %s
						
								UNION ALL
							
								SELECT
									(-1) * (monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
								FROM sa_v_informe_final_tot_dev a %s
							
								UNION ALL
							
								SELECT
									(monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
								FROM sa_v_vale_informe_final_tot a %s
							
								UNION ALL
							
								SELECT
									(-1) * (monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
								FROM sa_v_vale_informe_final_tot_dev a %s) AS q",
							$sqlBusq,
							$sqlBusq,
							$sqlBusq,
							$sqlBusq);
	$rsTot = mysql_query($queryTot);
	if (!$rsTot) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowTot = mysql_fetch_assoc($rsTot);

	$totalTotServ = round($rowTot['total_tot_orden'],2);
	
	// VENTA DE NOTAS POR SERVICIOS
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");

	if (strlen($rowConfig302['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
	}

	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
	}

	if ($meses != "-1" && $meses != ""
		&& $anio != "-1" && $anio != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
						AND YEAR(a.fecha_filtro) = %s",
					valTpDato($meses, "date"),
					valTpDato($anio, "date"));
	}

	$queryNota = sprintf("SELECT
								SUM(precio) AS total_notas_orden
							FROM (
								SELECT
									precio
								FROM sa_v_informe_final_notas a %s
						
								UNION ALL
							
								SELECT
									(-1) * precio
								FROM sa_v_informe_final_notas_dev a %s
							
								UNION ALL
							
								SELECT
									precio
								FROM sa_v_vale_informe_final_notas a %s
							
								UNION ALL
							
								SELECT
									(-1) * precio
								FROM sa_v_vale_informe_final_notas_dev a %s) AS q",
						$sqlBusq,
						$sqlBusq,
						$sqlBusq,
						$sqlBusq);
	$rsNota = mysql_query($queryNota);
	if (!$rsNota) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNota = mysql_fetch_assoc($rsNota);

	$totalNotaServ = round($rowNota['total_notas_orden'],2);
	
	$totalServicios = array_sum($totalMoServ) + $totalTotServ;
	$totalServicios += ($rowConfig300['valor'] == 1) ? $totalNotaServ : 0;
	
	//VENTA TOTAL DE REPUESTO SERVICIOS
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig302['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
					OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
							WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
	}
	
	if ($meses != "-1" && $meses != ""
		&& $anio != "-1" && $anio != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
					AND YEAR(a.fecha_filtro) = %s",
					valTpDato($meses, "date"),
					valTpDato($anio, "date"));
	}
	
	$queryRep = sprintf("SELECT
								SUM(total_repuesto_orden) AS total_repuesto_orden
							FROM (
								SELECT
									(precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
								FROM sa_v_informe_final_repuesto a %s
				
								UNION ALL
				
								SELECT
									(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
								FROM sa_v_informe_final_repuesto_dev a %s
				
								UNION ALL
				
								SELECT
									(precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
								FROM sa_v_vale_informe_final_repuesto a %s
				
								UNION ALL
				
								SELECT
									(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
								FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
						$sqlBusq,
						$sqlBusq,
						$sqlBusq,
						$sqlBusq);
	$rsRep = mysql_query($queryRep);
	if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowRep = mysql_fetch_assoc($rsRep);

	$repServ = round($rowRep['total_repuesto_orden'],2);

	//VENTA TOTAL DE REPUESTO MOSTRADOR
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
						AND fact_vent.aplicaLibros = 1");

	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
						AND nota_cred.tipoDocumento = 'FA'
						AND nota_cred.aplicaLibros = 1
						AND nota_cred.estatus_nota_credito = 2");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = fact_vent.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
	
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(nota_cred.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = nota_cred.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
	}
	
	if ($meses != "-1" && $meses != ""
		&& $anio != "-1" && $anio != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
						AND YEAR(fact_vent.fechaRegistroFactura) = %s",
					valTpDato($meses, "date"),
					valTpDato($anio, "date"));
				
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
					AND YEAR(nota_cred.fechaNotaCredito) = %s",
				valTpDato($meses, "date"),
				valTpDato($anio, "date"));
	}

	$query = sprintf("SELECT
							IFNULL(fact_vent.subtotalFactura,0) - IFNULL(fact_vent.descuentoFactura,0) AS neto
						FROM cj_cc_encabezadofactura fact_vent %s
						
						UNION ALL
						
						SELECT
							(-1) * (IFNULL(nota_cred.subtotalNotaCredito,0) - IFNULL(nota_cred.subtotal_descuento,0)) AS neto
						FROM cj_cc_notacredito nota_cred %s;",
					$sqlBusq,
					$sqlBusq2);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalVentaRepuesto = 0;
	while ($rowDetalle = mysql_fetch_assoc($rs)) {
		$repMost += round($rowDetalle['neto'],2);
	}

	$totalRespuestos = $repServ + $repMost;
	//$totalRespuestos = $repMost;

	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
		$htmlTh = "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"60%\">Conceptos</td>
						<td width=\"40%\">Facturado</td>";
		$htmlTh .= "</tr>";
		
		// VENTA TOTAL DE SERVICIOS
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\">"."Venta Total de Servicio"."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($totalServicios, 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<input type='hidden' id='totalVentaServ' name='totalVentaServ' value='{$totalServicios}'></input>";
		$htmlTb .= "</tr>";
		
		// LINEA EN BLANCO
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
			$htmlTb .= "<td>"."&nbsp"."</td>";
			$htmlTb .= "<td>"."&nbsp"."</td>";
		$htmlTb .= "</tr>";

		// TOTAL REPUESTOS
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\">"."Venta Total de Piezas"."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($totalRespuestos, 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<input type='hidden' id='totalVentaRep' name='totalVentaRep' value='{$totalRespuestos}'></input>";
		$htmlTb .= "</tr>";
		
		// LINEA EN BLANCO
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
			$htmlTb .= "<td>"."&nbsp"."</td>";
			$htmlTb .= "<td>"."&nbsp"."</td>";
		$htmlTb .= "</tr>";
		
		$totalVentas = $totalServicios + $totalRespuestos;
		// TOTAL
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\">"."Total:"."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($totalVentas, 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<input type='hidden' id='totalVentas' name='totalVentas' value='{$totalVentas}'></input>";
		$htmlTb .= "</tr>";

		$htmlTh .= "<thead>";
			$htmlTh .= "<td colspan=\"14\">";
				$htmlTh .= "<table width=\"100%\">";
					$htmlTh .= "<tr>";
						$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
						$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">Ventas</p>"."</td>";
					$htmlTh .= "</tr>";
				$htmlTh .= "</table>";
			$htmlTh .= "</td>";
		$htmlTh .= "</thead>";

	$htmlTableFin.= "</table>";
	
	
	$objResponse->assign("divListaTotalVentas","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	return $objResponse;
}

function totalCosto($idEmpresa, $meses, $anio){
	$objResponse = new xajaxResponse();

	global $mes;
	
	mysql_query("SET GLOBAL innodb_stats_on_metadata = 0;");
	
	$sqlBusq = " ";
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = (SELECT emp.id_empresa
															FROM pg_empresa emp
																LEFT JOIN pg_empresa emp_ppal ON (emp.id_empresa_padre = emp_ppal.id_empresa)
															ORDER BY emp.id_empresa_padre ASC
															LIMIT 1)");
	}
	
	// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Servicios))
	$queryConfig302 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
										INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
									WHERE config.id_configuracion = 302 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig302 = mysql_query($queryConfig302);
	if (!$rsConfig302) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig302 = mysql_num_rows($rsConfig302);
	$rowConfig302 = mysql_fetch_assoc($rsConfig302);
	
	// TOTAL RESPUESTOS (SERVICIOS)
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("a.aprobado = 1");
	
	if (strlen($rowConfig302['valor']) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
	}
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
									OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
											WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
	}
	
	if ($meses != "-1" && $meses != ""
		&& $anio != "-1" && $anio != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
								AND YEAR(a.fecha_filtro) = %s",
					valTpDato($meses, "date"),
					valTpDato($anio, "date"));
		}

	$queryRep = sprintf("SELECT
						SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
					FROM (
						SELECT
							(costo_unitario * cantidad) AS total_costo_repuesto_orden
						FROM sa_v_informe_final_repuesto a %s
		
						UNION ALL
		
						SELECT
							(-1) * (costo_unitario * cantidad) AS total_costo_repuesto_orden
						FROM sa_v_informe_final_repuesto_dev a %s
		
						UNION ALL
		
						SELECT
							(costo_unitario * cantidad) AS total_costo_repuesto_orden
						FROM sa_v_vale_informe_final_repuesto a %s
		
						UNION ALL
		
						SELECT
							(-1) * (costo_unitario * cantidad) AS total_costo_repuesto_orden
						FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
				$sqlBusq,
				$sqlBusq,
				$sqlBusq,
				$sqlBusq);
	$rsRep = mysql_query($queryRep);
	if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowRep = mysql_fetch_assoc($rsRep);

	$arrayCostoRepServ = round($rowRep['total_costo_repuesto_orden'],2);

	// TOTAL RESPUESTOS (MOSTRADOR)
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
					AND fact_vent.aplicaLibros = 1");

	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
							AND nota_cred.tipoDocumento = 'FA'
							AND nota_cred.aplicaLibros = 1
							AND nota_cred.estatus_nota_credito = 2");

	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
					OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
							WHERE suc.id_empresa = fact_vent.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
	
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(nota_cred.id_empresa = %s
					OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
							WHERE suc.id_empresa = nota_cred.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
	}
	
	if ($meses != "-1" && $meses != ""
		&& $anio != "-1" && $anio != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
						AND YEAR(fact_vent.fechaRegistroFactura) = %s",
					valTpDato($meses, "date"),
					valTpDato($anio, "date"));

			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
						AND YEAR(nota_cred.fechaNotaCredito) = %s",
					valTpDato($meses, "date"),
					valTpDato($anio, "date"));
	}

	$query = sprintf("SELECT
								(SELECT
									SUM((fact_vent_det.costo_compra * fact_vent_det.cantidad)) AS costo_total
								FROM cj_cc_factura_detalle fact_vent_det
								WHERE fact_vent_det.id_factura = fact_vent.idFactura) AS neto
							FROM cj_cc_encabezadofactura fact_vent %s
				
							UNION ALL
			
							SELECT
								((SELECT
									SUM((nota_cred_det.costo_compra * nota_cred_det.cantidad)) AS costo_total
								FROM cj_cc_nota_credito_detalle nota_cred_det
								WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito) * (-1)) AS neto
							FROM cj_cc_notacredito nota_cred %s;",
					$sqlBusq,
					$sqlBusq2);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalCostoRepMost = 0;
	
	while ($rowDetalle = mysql_fetch_assoc($rs)) {
		$totalCostoRepMost += round($rowDetalle['neto'],2);
	}
	
	//TOTAL DE COSTO POR PIEZAS
	$costoRepuesto = $totalCostoRepMost + $arrayCostoRepServ;
	
	// MANO DE OBRA SERVICIOS
	$query = sprintf("SELECT * FROM sa_operadores operador WHERE id_operador IN (1);");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$cont = 0;
	
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayMODet = NULL;
	
		$arrayMODet[0] = $row['descripcion_operador'];
	
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
			
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
							valTpDato($rowConfig302['valor'], "campo"));
		}
			
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
						OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
								WHERE suc.id_empresa = a.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
		}

		if ($meses != "-1" && $meses != ""
			&& $anio != "-1" && $anio != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
							AND YEAR(a.fecha_filtro) = %s",
						valTpDato($meses, "date"),
						valTpDato($anio, "date"));
		}
					
		// SOLO APLICA PARA LAS MANO DE OBRA
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("operador = %s
							AND a.estado_tempario IN ('FACTURADO','TERMINADO')",
						valTpDato($row['id_operador'],"int"));

		$queryMO = sprintf("SELECT
								operador,
								SUM(total_tempario_orden) AS total_tempario_orden
							FROM (
								SELECT 	a.operador,
										a.costo AS total_tempario_orden
								FROM sa_v_informe_final_tempario a %s %s
										
								UNION ALL
										
								SELECT
									a.operador,
									((-1) * a.costo) AS total_tempario_orden
								FROM sa_v_informe_final_tempario_dev a %s %s
											
								UNION ALL
								
								SELECT
									a.operador,
									a.costo AS total_tempario_orden
								FROM sa_v_vale_informe_final_tempario a %s %s
								
								UNION ALL
								
								SELECT
									a.operador,
									((-1) * a.costo) AS total_tempario_orden
									
								FROM sa_v_vale_informe_final_tempario_dev a %s %s) AS q
								GROUP BY operador",
						$sqlBusq, $sqlBusq2,
						$sqlBusq, $sqlBusq2,
						$sqlBusq, $sqlBusq2,
						$sqlBusq, $sqlBusq2);
		
		$rsMO = mysql_query($queryMO);
		if (!$rsMO) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowMO = mysql_fetch_assoc($rsMO);
			
		$arrayMODet[] = round($rowMO['total_tempario_orden'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
		$htmlTh = "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"60%\">Conceptos</td>
						<td width=\"40%\">Facturado</td>";
		$htmlTh .= "</tr>";
	
		// COSTO DE MANO DE OBRA
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\">"."Costo de Mano de Obra (Técnicos)"."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($arrayMODet[1], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<input type='hidden' id='totalCostoMo' name='totalCostoMo' value='{$arrayMODet[1]}'></input>";
		$htmlTb .= "</tr>";
		
		if(isset($costoIncentivo)){
			$costoIncentivo = valTpDato(number_format($costoIncentivo, 2, ".", ","),"cero_por_vacio");
			$costoTotal = $arrayMODet[1] + $costoRepuesto;
			$costoTotalIncentivos = $arrayMODet[1] + $costoIncentivo + $costoRepuesto;
		} else{
			$costoIncentivo = "-";
			$costoTotal = $arrayMODet[1] + $costoRepuesto;
			$costoTotalIncentivos = $arrayMODet[1] + $costoRepuesto;
		}

		// COSTOS POR INCENTIVO
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\">"."Costos por incentivo"."</td>";
			$htmlTb .= "<td>".$costoIncentivo."</td>";
			$htmlTb .= "<input type='hidden' id='totalCostoInc' name='totalCostoInc' value='{$costoIncentivo}'></input>";
		$htmlTb .= "</tr>";

		// COSTO TOTAL DE PIEZAS
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\">"."Costo Total de Piezas"."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($costoRepuesto, 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<input type='hidden' id='totalCostoRep' name='totalCostoRep' value='{$costoRepuesto}'></input>";
		$htmlTb .= "</tr>";

		// TOTAL
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\">"."Total:"."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($costoTotal, 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<input type='hidden' id='totalCostos' name='totalCostos' value='{$costoTotal}'></input>";
		$htmlTb .= "</tr>";

		// TOTAL CON INCENTIVOS
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\">"."Total con Incentivos:"."</td>";
			$htmlTb .= "<td>".valTpDato(number_format($costoTotalIncentivos, 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "<input type='hidden' id='totalCostoIncTo' name='totalCostoIncTo' value='{$costoTotalIncentivos}'></input>";
		$htmlTb .= "</tr>";

		$htmlTh .= "<thead>";
			$htmlTh .= "<td colspan=\"14\">";
				$htmlTh .= "<table width=\"100%\">";
					$htmlTh .= "<tr>";
						$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
						$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">Costos</p>"."</td>";
					$htmlTh .= "</tr>";
				$htmlTh .= "</table>";
			$htmlTh .= "</td>";
		$htmlTh .= "</thead>";

	$htmlTableFin.= "</table>";


	$objResponse->assign("divListaTotalCosto","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);

	return $objResponse;
}

function utilidad($formTotalVentas = '', $formTotalCostos = ''){

	$objResponse = new xajaxResponse();
	
	global $mes;

	//CALCULO DE UTILIDADES
		$utlServicio = $formTotalVentas['totalVentaServ'] - $formTotalCostos['totalCostoMo'];
		$utlServicioCon = valTpDato(number_format($utlServicio, 2, ".", ","),"cero_por_vacio");
	
		$utlRespuesto = $formTotalVentas['totalVentaRep'] - $formTotalCostos['totalCostoRep'];
		$utlRespuestoCon = valTpDato(number_format($utlRespuesto, 2, ".", ","),"cero_por_vacio");
		
		$totalUtl = $utlServicio + $utlRespuesto;
		$totalUtlCon = valTpDato(number_format($totalUtl, 2, ".", ","),"cero_por_vacio");
		
		$totalUtlInc = $formTotalVentas['totalVentas'] - $formTotalCostos['totalCostoIncTo'];
		$totalUtlIncCon = valTpDato(number_format($totalUtlInc, 2, ".", ","),"cero_por_vacio");
	
		$utlServicioPor = ($utlServicio / $totalUtl)*100;
		$utlServicioPorCon = valTpDato(number_format($utlServicioPor, 0, ".", ","),"cero_por_vacio");
	
		$utlRespuestoPor = ($utlRespuesto / $totalUtl)*100;
		$utlRespuestoPorCon = valTpDato(number_format($utlRespuestoPor, 0, ".", ","),"cero_por_vacio");
		
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
		$htmlTh = "<tr class=\"tituloColumna\">";
			$htmlTh .= "<td width=\"35%\">Conceptos</td>
						<td width=\"35%\">Conceptos</td>
						<td width=\"30%\">%</td>";
		$htmlTh .= "</tr>";
		
		// DEPARTAMENTO DE SERVICIO
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\">"."Departamento de Servicio"."</td>";
			$htmlTb .= "<td>$utlServicioCon</td>";
			$htmlTb .= "<td>$utlServicioPorCon%</td>";
			$htmlTb .= "<input type='hidden' id='utlServicio' name='utlServicio' value='{$utlServicio}'></input>";
		$htmlTb .= "</tr>";
		
		// DEPARTAMENTO DE PIEZAS
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\">"."Departamento de Piezas"."</td>";
			$htmlTb .= "<td>$utlRespuestoCon</td>";
			$htmlTb .= "<td>$utlRespuestoPorCon%</td>";
			$htmlTb .= "<input type='hidden' id='utlRespuesto' name='utlRespuesto' value='{$utlRespuesto}'></input>";
		$htmlTb .= "</tr>";

		// TOTAL
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\">"."Total:"."</td>";
			$htmlTb .= "<td>$totalUtlCon</td>";
			$htmlTb .= "<td> </td>";
			$htmlTb .= "<input type='hidden' id='totalUtl' name='totalUtl' value='{$totalUtl}'></input>";
		$htmlTb .= "</tr>";
		
		// TOTAL CON INCENTIVOS
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
			$htmlTb .= "<td class=\"tituloCampo\">"."Utilidad Total Considerando Incentivos:"."</td>";
			$htmlTb .= "<td>$totalUtlIncCon</td>";
			$htmlTb .= "<td> </td>";
			$htmlTb .= "<input type='hidden' id='totalUtlInc' name='totalUtlInc' value='{$totalUtlInc}'></input>";
		$htmlTb .= "</tr>";

		$htmlTh .= "<thead>";
			$htmlTh .= "<td colspan=\"14\">";
				$htmlTh .= "<table width=\"100%\">";
					$htmlTh .= "<tr>";
						$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
						$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">Utilidad (Ventas - Costos)</p></td>";
	// 					$htmlTh .= "<td align=\"right\" width=\"5%\">";
	// 						$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
	// 							9,
	// 							"Pie with legend",
	// 							"Ventas (".$mes[intval($meses)]." ".$anio.")",
	// 							str_replace("'","|*|",array("")),
	// 							"Monto",
	// 							str_replace("'","|*|",$data1),
	// 							" ",
	// 							str_replace("'","|*|",array("")),
	// 							cAbrevMoneda);
	// 					$htmlTh .= "</td>";
					$htmlTh .= "</tr>";
				$htmlTh .= "</table>";
			$htmlTh .= "</td>";
		$htmlTh .= "</thead>";
	$htmlTableFin.= "</table>";

	$objResponse->assign("divListaUtilidad","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);

	return $objResponse;
}

function validarCierreMensual($frmCierreMensual) {
	$objResponse = new xajaxResponse();

	$idEmpresa = $frmCierreMensual['txtIdEmpresa'];
	$arrayFecha = explode("/", $frmCierreMensual['lstMesAno']);
	$mesCierre = $arrayFecha[0];
	$anoCierre = $arrayFecha[1];

	// BUSCA QUE NO EXISTA UN MES ATRASADO AL CIERRE DEL MES ACTUAL
	$query = sprintf("SELECT * FROM if_cierre_mensual
						WHERE mes < %s AND ano = %s;",
				valTpDato($mesCierre, "int"),
				valTpDato($anoCierre, "int"));
	$rs = mysql_query($query);

	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);

	$valCierre = (($mesCierre - 1) <= $totalRows)? true : false;

	if ($idEmpresa > 0 && $arrayFecha != '' && $valCierre) {

		$objResponse->script("
			if (confirm('¿Desea Crear el Cierre del Mes Seleccionado?') == true) {
				$('#ventas').hide();
				$('#trCierreMensual').hide();
				$('#consecionario').hide();
				$('#postventas').show();
				$('#btnAtras').show();
				$('#btnAtras').val(1);
				$('#btnSiguiente').val(3);
			}
		");
	} else{
		$objResponse->alert("Aún existen Cierres Mensuales anteriores que deben ser gestionados");
	}
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"buscarCierre");
$xajax->register(XAJAX_FUNCTION,"buscarCuenta");
$xajax->register(XAJAX_FUNCTION,"buscarCuentaConcepto");
$xajax->register(XAJAX_FUNCTION,"buscarTiposCuenta");
$xajax->register(XAJAX_FUNCTION,"cargaLstDecimalPDF");
$xajax->register(XAJAX_FUNCTION,"cargaLstAno");
$xajax->register(XAJAX_FUNCTION,"cargaLstMes");
$xajax->register(XAJAX_FUNCTION,"cargaLstMesAno");
$xajax->register(XAJAX_FUNCTION,"confirmarConcepto");
$xajax->register(XAJAX_FUNCTION,"confirmarCuenta");
$xajax->register(XAJAX_FUNCTION,"confirmarTipoCuenta");
$xajax->register(XAJAX_FUNCTION,"datosGenerales");
$xajax->register(XAJAX_FUNCTION,"editarCuenta");
$xajax->register(XAJAX_FUNCTION,"eliminarConcepto");
$xajax->register(XAJAX_FUNCTION,"eliminarCuenta");
$xajax->register(XAJAX_FUNCTION,"eliminarTiposCuenta");
$xajax->register(XAJAX_FUNCTION,"frmEditConcepto");
$xajax->register(XAJAX_FUNCTION,"frmEditTipoCuenta");
$xajax->register(XAJAX_FUNCTION,"gastosGeneral");
$xajax->register(XAJAX_FUNCTION,"guardarCierreMensual");
$xajax->register(XAJAX_FUNCTION,"guardarConcepto");
$xajax->register(XAJAX_FUNCTION,"guardarTipoCuenta");
$xajax->register(XAJAX_FUNCTION,"indicadorDesemp");
$xajax->register(XAJAX_FUNCTION,"imprimirReporteAFP");
$xajax->register(XAJAX_FUNCTION,"insertarCuenta");
$xajax->register(XAJAX_FUNCTION,"listaAddCuentaContabilidad");
$xajax->register(XAJAX_FUNCTION,"listaCierreMensual");
$xajax->register(XAJAX_FUNCTION,"listaCuenta");
$xajax->register(XAJAX_FUNCTION,"listaCuentaConcepto");
$xajax->register(XAJAX_FUNCTION,"listaCuentaPorConcepto");
$xajax->register(XAJAX_FUNCTION,"listaCuentaContabilidad");
$xajax->register(XAJAX_FUNCTION,"listaSelectTiposCuenta");
$xajax->register(XAJAX_FUNCTION,"listaTiposCuenta");
$xajax->register(XAJAX_FUNCTION,"totalVentas");
$xajax->register(XAJAX_FUNCTION,"totalCosto");
$xajax->register(XAJAX_FUNCTION,"utilidad");
$xajax->register(XAJAX_FUNCTION,"validarCierreMensual");
?>