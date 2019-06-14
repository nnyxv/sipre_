<?php

function agregarNuevaTarjeta($idCuenta){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_cuentas","insertar")){ return $objResponse; }
	
	$objResponse->script("byId('GuardarModifica').style.display = 'none';
		byId('GuardarNuevo').style.display = '';
		byId('txtComision').className = 'inputHabilitado';
		byId('txtISLR').className = 'inputHabilitado';
		xajax_comboTarjetas();");
	
	$objResponse->assign("hddId","value",$idCuenta);
	
	return $objResponse;
}

function buscarCuenta($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$valForm['selEmpresa'],				
		$valForm['selBancos'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listaCuentas(0, "idCuentas", "ASC", $valBusq));
	
	return $objResponse;
}

function comboBancos($idBanco, $idTd, $idSel, $onchange){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM bancos WHERE idBanco != 1");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$html = "<select id=\"".$idSel."\" name=\"".$idSel."\" onchange=\"".$onchange."\" style=\"width:200px\" class=\"inputHabilitado\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($row['idBanco'] == $idBanco) ? "selected=\"selected\"" : "";
		$html .= "<option value=\"".$row['idBanco']."\" ".$selected.">".  utf8_encode($row['nombreBanco'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign($idTd,"innerHTML",$html);
	
	return $objResponse;
}

function comboEmpresa(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY id_empresa_reg", 
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$html = "<select id=\"selEmpresa\" name=\"selEmpresa\" onChange=\"byId('btnBuscar').click();\" class=\"inputHabilitado\">";
	$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
		
		$selected = "";
		if ($selId == $row['id_empresa_reg'] || $_SESSION['idEmpresaUsuarioSysGts'] == $row['id_empresa_reg'])
			$selected = "selected='selected'";
	
		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdSelEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function comboMonedas($idMoneda){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM pg_monedas";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
			
	$html = "<select id=\"selMonedas\" name=\"selMonedas\" class=\"inputHabilitado\">";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($row['idmoneda'] == $idMoneda) ? "selected=\"selected\"" : "";
		$html .= "<option value=\"".$row['idmoneda']."\" ".$selected.">".htmlentities($row['descripcion'])."</option>";
	}	
	$html .= "</select>";
	
	$objResponse->assign("tdSelMonedas","innerHTML",$html);
	
	return $objResponse;
}

function comboTarjetas($selId){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM tipotarjetacredito");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$html = "<select id=\"selTarjeta\" name=\"selTarjeta\" class=\"inputHabilitado\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($row['idTipoTarjetaCredito'] == $selId) ? "selected=\"selected\"" : "";
		$html .= "<option value=\"".$row['idTipoTarjetaCredito']."\" ".$selected.">".htmlentities($row['descripcionTipoTarjetaCredito'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdSelTarjetas","innerHTML",$html);
	
	return $objResponse;
}

function editarTarjeta($idTarjeta){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_cuentas","editar")){ return $objResponse; }
	
	$query = sprintf("SELECT 
					ret_punto.id_retencion_punto,
					ret_punto.porcentaje_comision,
					ret_punto.porcentaje_islr,
					tipo_tarjeta.descripcionTipoTarjetaCredito 
				FROM te_retencion_punto ret_punto
				INNER JOIN tipotarjetacredito tipo_tarjeta ON (ret_punto.id_tipo_tarjeta = tipo_tarjeta.idTipoTarjetaCredito)
				WHERE ret_punto.id_retencion_punto = %s",
			valTpDato($idTarjeta, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	$row = mysql_fetch_assoc($rs);
		
	$select = "<select id=\"selTarjeta\" name=\"selTarjeta\" disabled=\"disabled\">";
	$select .= "<option value=\"".$idTarjeta."\">".$row['descripcionTipoTarjetaCredito']."</option>";
	$select .= "</select>";
    $objResponse->assign("tdSelTarjetas","innerHTML",$select);
	
	$objResponse->assign("txtComision","value",$row['porcentaje_comision']);
	$objResponse->assign("txtISLR","value",$row['porcentaje_islr']);
 	$objResponse->assign("hddId","value",$idTarjeta);
	
	$objResponse->script("byId('txtComision').className = 'inputHabilitado';
		byId('txtISLR').className = 'inputHabilitado';
		byId('GuardarModifica').style.display = '';
		byId('GuardarNuevo').style.display = 'none';");
						  
	return $objResponse;
}

function guardarCambioTarjeta($valfrom){
	$objResponse = new xajaxResponse();

	$query = "UPDATE te_retencion_punto SET 
		porcentaje_comision = '".$valfrom['txtComision']."',
		porcentaje_islr = '".$valfrom['txtISLR']."'
		WHERE id_retencion_punto = ".$valfrom['hddId'].";";	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$queryCuenta = "SELECT id_cuenta FROM te_retencion_punto WHERE id_retencion_punto= ".$valfrom['hddId'].";";	
	$rsCuenta = mysql_query($queryCuenta);
	if (!$rsCuenta) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	$rowCuenta = mysql_fetch_assoc($rsCuenta);
	
	$objResponse->script("xajax_listaTarjetas(".$rowCuenta['id_cuenta'].");");
	$objResponse->script("byId('btnCancelarTarjeta').click();");
						  
	return $objResponse;
}

function guardarCuenta($formCuenta){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	//* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS	*/
	for ($cont = 1; $cont <= strlen($formCuenta['hddObj']); $cont++) {
		$caracter = substr($formCuenta['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "") {
			$cadena .= $caracter;				
		} else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}
	}
	
	if (strpos($formCuenta['txtSaldoLibros'], ',') !== false || strpos($formCuenta['txtSaldoAnteriorConciliado'], ',')) {
		return $objResponse->alert("No puedes usar comas en los saldos");
	}
	
	if($formCuenta['hddIdCuenta'] == 0){//NUEVA CUENTA
		$query = sprintf("INSERT INTO cuentas (idBanco, id_empresa, numeroCuentaCompania, estatus, firma_electronica, nro_cuenta_contable_debitos, debito_bancario, tipo_cuenta, id_moneda, firma_1, firma_2, firma_3, firma_4, firma_5, firma_6, tipo_firma_1, tipo_firma_2, tipo_firma_3, tipo_firma_4, tipo_firma_5, tipo_firma_6, comb_1, comb_2, comb_3, restriccion_1, restriccion_2, restriccion_3, saldo, saldo_tem) 
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($formCuenta['selBancoCuentaNueva'], "int"),
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"),
		valTpDato($formCuenta['txtNumeroCuenta'], "text"),
		valTpDato($formCuenta['selEstatus'], "int"),
		valTpDato($formCuenta['txtFirmaElectronica'], "text"),
		valTpDato($formCuenta['txtCuentaDebitosBancarios'], "text"),
		valTpDato($formCuenta['selAplicaDebito'], "int"),
		valTpDato($formCuenta['selTipoCuenta'], "text"),
		valTpDato($formCuenta['selMonedas'], "int"),
		valTpDato($formCuenta['txtFirmante1'], "text"),
		valTpDato($formCuenta['txtFirmante2'], "text"),
		valTpDato($formCuenta['txtFirmante3'], "text"),
		valTpDato($formCuenta['txtFirmante4'], "text"),
		valTpDato($formCuenta['txtFirmante5'], "text"),
		valTpDato($formCuenta['txtFirmante6'], "text"),
		valTpDato($formCuenta['txtTipoFirmante1'], "text"),
		valTpDato($formCuenta['txtTipoFirmante2'], "text"),
		valTpDato($formCuenta['txtTipoFirmante3'], "text"),
		valTpDato($formCuenta['txtTipoFirmante4'], "text"),
		valTpDato($formCuenta['txtTipoFirmante5'], "text"),
		valTpDato($formCuenta['txtTipoFirmante6'], "text"),
		valTpDato($formCuenta['txtCombinacion1'], "text"),
		valTpDato($formCuenta['txtCombinacion2'], "text"),
		valTpDato($formCuenta['txtCombinacion3'], "text"),
		valTpDato($formCuenta['txtRestriccionCombinacion1'], "text"),
		valTpDato($formCuenta['txtRestriccionCombinacion2'], "text"),
		valTpDato($formCuenta['txtRestriccionCombinacion3'], "text"),
		valTpDato($formCuenta['txtSaldoLibros'], "real_inglesa"),
		valTpDato($formCuenta['txtSaldoAnteriorConciliado'], "real_inglesa"));
		
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__."\n\n".$query);
		$idCuenta = mysql_insert_id();
		
		if(isset($arrayObj)){
			foreach($arrayObj as $indice => $valor){
				$queryTarjetas = sprintf("INSERT INTO te_retencion_punto (id_cuenta, id_tipo_tarjeta, porcentaje_comision, porcentaje_islr ) VALUES (%s, %s, %s, %s)",
				valTpDato($idCuenta, "int"),
				valTpDato($formCuenta['hddBancoCheque'.$valor], "int"),
				valTpDato($formCuenta['txtComision'.$valor], "real_inglesa"),
				valTpDato($formCuenta['txtISLR'.$valor], "real_inglesa"));
				
				$rsTarjetas = mysql_query($queryTarjetas);
				if (!$rsTarjetas) return $objResponse->alert(mysql_error());
			}
		}
	}else{//EDITANDO CUENTA            
		//actualizo saldo si anteriormente estaba en cero
		$querySaldo = sprintf("SELECT saldo, saldo_tem FROM cuentas WHERE idCuentas = %s",
			valTpDato($formCuenta['hddIdCuenta'], "int"));
		$rs = mysql_query($querySaldo);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__."\n\nQuery:".$querySaldo); }
		
		$row = mysql_fetch_assoc($rs);
		
		//solo actualizar si la cuenta esta en cero, sin movimientos
		$saldoLibro = $row["saldo"];
		$saldoConciliado = $row["saldo_tem"];
		
		if($row["saldo"] == 0){
			$saldoLibro = $formCuenta['txtSaldoLibros'];
		}
		
		if($row["saldo_tem"] == 0){
			$saldoConciliado = $formCuenta['txtSaldoAnteriorConciliado'];
		}
	
		$query = sprintf("UPDATE cuentas SET 
			idBanco = %s,
			numeroCuentaCompania = %s,
			estatus = %s,
			firma_electronica = %s,
			nro_cuenta_contable_debitos = %s,
			debito_bancario = %s,
			tipo_cuenta = %s,
			id_moneda = %s,
			firma_1 = %s,
			firma_2 = %s,
			firma_3 = %s,
			firma_4 = %s,
			firma_5 = %s,
			firma_6 = %s,
			tipo_firma_1 = %s,
			tipo_firma_2 = %s,
			tipo_firma_3 = %s,
			tipo_firma_4 = %s,
			tipo_firma_5 = %s,
			tipo_firma_6 = %s,
			comb_1 = %s,
			comb_2 = %s,
			comb_3 = %s,
			restriccion_1 = %s,
			restriccion_2 = %s,
			restriccion_3 = %s,
			saldo = %s,
			saldo_tem = %s
		WHERE idCuentas = %s;",
		valTpDato($formCuenta['selBancoCuentaNueva'], "int"),
		valTpDato($formCuenta['txtNumeroCuenta'], "text"),
		valTpDato($formCuenta['selEstatus'], "int"),
		valTpDato($formCuenta['txtFirmaElectronica'], "text"),
		valTpDato($formCuenta['txtCuentaDebitosBancarios'], "text"),
		valTpDato($formCuenta['selAplicaDebito'], "int"),
		valTpDato($formCuenta['selTipoCuenta'], "text"),
		valTpDato($formCuenta['selMonedas'], "int"),
		valTpDato($formCuenta['txtFirmante1'], "text"),
		valTpDato($formCuenta['txtFirmante2'], "text"),
		valTpDato($formCuenta['txtFirmante3'], "text"),
		valTpDato($formCuenta['txtFirmante4'], "text"),
		valTpDato($formCuenta['txtFirmante5'], "text"),
		valTpDato($formCuenta['txtFirmante6'], "text"),
		valTpDato($formCuenta['txtTipoFirmante1'], "text"),
		valTpDato($formCuenta['txtTipoFirmante2'], "text"),
		valTpDato($formCuenta['txtTipoFirmante3'], "text"),
		valTpDato($formCuenta['txtTipoFirmante4'], "text"),
		valTpDato($formCuenta['txtTipoFirmante5'], "text"),
		valTpDato($formCuenta['txtTipoFirmante6'], "text"),
		valTpDato($formCuenta['txtCombinacion1'], "text"),
		valTpDato($formCuenta['txtCombinacion2'], "text"),
		valTpDato($formCuenta['txtCombinacion3'], "text"),
		valTpDato($formCuenta['txtRestriccionCombinacion1'], "text"),
		valTpDato($formCuenta['txtRestriccionCombinacion2'], "text"),
		valTpDato($formCuenta['txtRestriccionCombinacion3'], "text"),
		valTpDato($saldoLibro, "real_inglesa"),
		valTpDato($saldoConciliado, "real_inglesa"),
		valTpDato($formCuenta['hddIdCuenta'], "int"));
		
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	}
	
	mysql_query("COMMIT;");
	$objResponse->script("byId('btnBuscar').click();
		byId('btnCancelar').click();");
	
	$objResponse->alert("Cuenta guardada exitosamente");
	
	return $objResponse;
}

function insertarNuevaTarjeta($valfrom){
	$objResponse = new xajaxResponse();
	
	$query = "INSERT INTO te_retencion_punto(id_retencion_punto, id_cuenta, id_tipo_tarjeta, porcentaje_comision, porcentaje_islr) VALUES ('', '".$valfrom['hddId']."', '".$valfrom['selTarjeta']."', '".$valfrom['txtComision']."', '".$valfrom['txtISLR']."');";
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$objResponse->script("xajax_listaTarjetas(".$valfrom['hddId'].");");
	$objResponse->script("byId('btnCancelarTarjeta').click();");
						  
	return $objResponse;
}

function listaCuentas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cuenta.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cuenta.idBanco = %s",
			valTpDato($valCadBusq[1], "int"));
	}
		
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(banco.nombreBanco LIKE %s
			OR cuenta.numeroCuentaCompania LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	$query = sprintf("SELECT cuenta.*, banco.nombreBanco, moneda.descripcion 
		FROM cuentas cuenta
		INNER JOIN bancos banco ON (cuenta.idBanco = banco.idBanco)
		INNER JOIN pg_monedas moneda ON (cuenta.id_moneda = moneda.idmoneda) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);	
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaCuentas", "20%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaCuentas", "30%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco");
		$htmlTh .= ordenarCampo("xajax_listaCuentas", "20%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Cuenta");
		$htmlTh .= ordenarCampo("xajax_listaCuentas", "10%", $pageNum, "tipo_cuenta", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Cuenta");
		$htmlTh .= ordenarCampo("xajax_listaCuentas", "10%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Moneda");
		$htmlTh .= "<td width='2%'></td>";
		$htmlTh .= "<td width='2%'></td>";	
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowCuenta = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$queryBanco = "SELECT nombreBanco FROM bancos WHERE idBanco = '".$rowCuenta['idBanco']."'";
		$rsBanco = mysql_query($queryBanco) or die(mysql_error());
		$rowBanco = mysql_fetch_assoc($rsBanco);
		
		$queryEmpresa = "SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$rowCuenta['id_empresa']."'";
		$rsEmpresa = mysql_query($queryEmpresa) or die (mysql_error());
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);

		$nombreSucursal = "";
		if ($rowEmpresa['id_empresa_padre_suc'] > 0){
			$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
		}
		
		$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>".$empresa."</td>";
			$htmlTb .= "<td>".utf8_encode($rowBanco['nombreBanco'])."</td>";
			$htmlTb .= "<td>".htmlentities($rowCuenta['numeroCuentaCompania'])."</td>";
			$htmlTb .= "<td>".$rowCuenta['tipo_cuenta']."</td>";
			$htmlTb .= "<td>".$rowCuenta['descripcion']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante1(this, '', 'ver', %s);\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver\" /></a>",
					$rowCuenta['idCuentas']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante1(this, '', 'editar', %s);\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\" /></a>",
					$rowCuenta['idCuentas']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCuentas(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCuentas(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"25\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaCuentas","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

function listaTarjetas($idCuenta){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_retencion_punto WHERE id_cuenta=".$idCuenta."";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$htmlTblIni .="<fieldset>";
	$htmlTblIni .="<legend><b>Comisiones y ISLR Puntos de Venta</b></legend>";
	$htmlTblIni .= "<table border=\"0\" width=\"100%\" >";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width='30%' align=\"center\">Tarjeta</td>";
		$htmlTh .= "<td width='30%' align=\"center\">Comisi&oacute;n</td>";
		$htmlTh .= "<td width='30%' align=\"center\">ISLR</td>";
		$htmlTh .= "<td width='5%'>";
			$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aNuevoItem\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblComisionPunto', 'nuevo', %s);\"><img class=\"puntero\" src=\"../img/iconos/cita_add.png\" title=\"Nuevo\" /></a>",
				$idCuenta);
		$htmlTh .= "</td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$queryNomTarjeta = "SELECT descripcionTipoTarjetaCredito FROM tipotarjetacredito WHERE idTipoTarjetaCredito = '".$row['id_tipo_tarjeta']."'";
		$rsNomTarjeta = mysql_query($queryNomTarjeta) or die(mysql_error());
		$rowNomTarjeta = mysql_fetch_assoc($rsNomTarjeta);		
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowNomTarjeta['descripcionTipoTarjetaCredito'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['porcentaje_comision']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['porcentaje_islr']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar\" rel=\"#divFlotante2\" onclick=\"abrirDivFlotante2(this, 'tblComisionPunto', 'editar', %s);\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\" title=\"Editar\" /></a>",
					$row['id_retencion_punto']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTblFin .= "</table>";
	$htmlTblFin .= "</fieldset>";
	
	$objResponse->assign("tblpunto","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);

	return $objResponse;
}

function nuevaCuenta(){
	$objResponse = new xajaxResponse();
	
	if(!xvalidaAcceso($objResponse,"te_cuentas","insertar")){ return $objResponse; }
	
	$objResponse->script("byId('btnGuardar').style.display = '';
						
						xajax_comboBancos(0,'tdSelBancoCuentaNueva','selBancoCuentaNueva','');
						xajax_comboMonedas(0);
						byId('tblpunto').style.display = 'none';"
						);
						
	$objResponse->assign("selEstatus","value","1");
	$objResponse->assign("selTipoCuenta","value","Corriente");
	$objResponse->script("byId('selBancoCuentaNueva').className = 'inputHabilitado'");
	
	return $objResponse;
}

function verCuenta($idCuentas, $accion){
	$objResponse = new xajaxResponse();
	
	if ($accion == 2){
		if (!xvalidaAcceso($objResponse,"te_cuentas","editar")){ return $objResponse; }
	}
	
	$query = "SELECT * FROM vw_te_cuentas WHERE idcuentas = ".$idCuentas."";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->loadCommands(comboBancos($row['idBanco'],'tdSelBancoCuentaNueva','selBancoCuentaNueva',''));	
	$objResponse->loadCommands(comboMonedas($row['id_moneda']));
	
	$objResponse->assign("selEstatus","value",$row['estatus']);
	$objResponse->assign("selTipoCuenta","value",$row['tipo_cuenta']);
	$objResponse->assign("hddIdCuenta","value",$row['idCuentas']);
	$objResponse->assign("selAplicaDebito","value",$row['debito_bancario']);
	$objResponse->assign("txtNumeroCuenta","value",$row['numeroCuentaCompania']);     
	$objResponse->assign("txtFirmaElectronica","value",$row['firma_electronica']);  
	$objResponse->assign("txtCuentaDebitosBancarios","value",$row['nro_cuenta_contable_debitos']);   
	$objResponse->assign("txtSaldoLibros","value",$row['saldo']);    
	$objResponse->assign("txtSaldoAnteriorConciliado","value",$row['saldo_tem']);     
	
	if ($row['ultimo_nro_chq'] != null) {
		$objResponse->assign("txtProximoNroCheque","value",$row['ultimo_nro_chq']);
	} else {
		$objResponse->assign("txtProximoNroCheque","value","");
	}
	
	$objResponse->assign("txtFirmante1","value",$row['firma_1']);
	$objResponse->assign("txtTipoFirmante1","value",$row['tipo_firma_1']);
	$objResponse->assign("txtFirmante2","value",$row['firma_2']);
	$objResponse->assign("txtTipoFirmante2","value",$row['tipo_firma_2']);
	$objResponse->assign("txtFirmante3","value",$row['firma_3']);
	$objResponse->assign("txtTipoFirmante3","value",$row['tipo_firma_3']);
	$objResponse->assign("txtFirmante4","value",$row['firma_4']);
	$objResponse->assign("txtTipoFirmante4","value",$row['tipo_firma_4']);
	$objResponse->assign("txtFirmante5","value",$row['firma_5']);
	$objResponse->assign("txtTipoFirmante5","value",$row['tipo_firma_5']);
	$objResponse->assign("txtFirmante6","value",$row['firma_6']);
	$objResponse->assign("txtTipoFirmante6","value",$row['tipo_firma_6']);
	$objResponse->assign("txtCombinacion1","value",$row['comb_1']);
	$objResponse->assign("txtRestriccionCombinacion1","value",$row['restriccion_1']);
	$objResponse->assign("txtCombinacion2","value",$row['comb_2']);
	$objResponse->assign("txtRestriccionCombinacion2","value",$row['restriccion_2']);
	$objResponse->assign("txtCombinacion3","value",$row['comb_3']);
	$objResponse->assign("txtRestriccionCombinacion3","value",$row['restriccion_3']);
		
	if ($accion == 1) {
		$objResponse->script("xajax_verTarjetas(".$idCuentas.");");
		$objResponse->script("byId('btnGuardar').style.display = 'none';
							  byId('tblpunto').style.display= ''");
		$objResponse->script("byId('selBancoCuentaNueva').className = 'inputInicial';
							byId('selMonedas').className = 'inputInicial'");
	} else {
		$objResponse->script("xajax_listaTarjetas(".$idCuentas.");");
		$objResponse->script("byId('btnGuardar').style.display = '';
							  byId('tblpunto').style.display= ''");
		$objResponse->script("byId('selBancoCuentaNueva').className = 'inputHabilitado';
							byId('selMonedas').className = 'inputHabilitado'");
		
		if($row['saldo'] == 0){
			$objResponse->script("byId('txtSaldoLibros').className = 'inputHabilitado';
								byId('txtSaldoLibros').readOnly = false;");
		}else{
			$objResponse->script("byId('txtSaldoLibros').className = 'inputInicial';
								byId('txtSaldoLibros').readOnly = true;");
		}
		
		if($row['saldo_tem'] == 0){
			$objResponse->script("byId('txtSaldoAnteriorConciliado').className = 'inputHabilitado';
								byId('txtSaldoAnteriorConciliado').readOnly = false;");
		}else{
			$objResponse->script("byId('txtSaldoAnteriorConciliado').className = 'inputInicial';
								byId('txtSaldoAnteriorConciliado').readOnly = true;");
		}
	}
	
	return $objResponse;
}

function verTarjetas($idCuenta){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_retencion_punto WHERE id_cuenta=".$idCuenta."";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLinea:".__LINE__);
	
	$htmlTableIni .="<fieldset>";
    $htmlTableIni .="<legend><b>Comisiones y ISLR Puntos de Venta</b></legend>";
	$htmlTableIni .= "<table border=\"0\" width=\"100%\" >";
	
	$htmlTh .= "<tr class=\"tituloColumna\">
					<td width='30%' align=\"center\">Tarjeta</td>
					<td width='30%' align=\"center\">Comisi&oacute;n</td>
					<td width='30%' align=\"center\">ISLR</td>
				</tr>";
	        
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$queryNomTarjeta = "SELECT descripcionTipoTarjetaCredito FROM tipotarjetacredito WHERE idTipoTarjetaCredito = '".$row['id_tipo_tarjeta']."'";
		$rsNomTarjeta = mysql_query($queryNomTarjeta) or die(mysql_error());
		$rowNomTarjeta = mysql_fetch_assoc($rsNomTarjeta);
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowNomTarjeta['descripcionTipoTarjetaCredito'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['porcentaje_comision']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['porcentaje_islr']."</td>";
		$htmlTb .= "</tr>";		
	}
	
	$htmlTableFin .= "</table>";
	$htmlTableFin .= "</fieldset>";
	
	$objResponse->assign("tblpunto","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTableFin);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"agregarNuevaTarjeta");
$xajax->register(XAJAX_FUNCTION,"buscarCuenta");
$xajax->register(XAJAX_FUNCTION,"comboBancos");
$xajax->register(XAJAX_FUNCTION,"comboEmpresa");
$xajax->register(XAJAX_FUNCTION,"comboMonedas");
$xajax->register(XAJAX_FUNCTION,"comboTarjetas");
$xajax->register(XAJAX_FUNCTION,"editarTarjeta");
$xajax->register(XAJAX_FUNCTION,"guardarCambioTarjeta");
$xajax->register(XAJAX_FUNCTION,"guardarCuenta");
$xajax->register(XAJAX_FUNCTION,"insertarNuevaTarjeta");
$xajax->register(XAJAX_FUNCTION,"listaCuentas");
$xajax->register(XAJAX_FUNCTION,"listaTarjetas");
$xajax->register(XAJAX_FUNCTION,"nuevaCuenta");
$xajax->register(XAJAX_FUNCTION,"verCuenta");
$xajax->register(XAJAX_FUNCTION,"verTarjetas");

?>