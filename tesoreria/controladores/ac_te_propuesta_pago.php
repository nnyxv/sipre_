<?php

function asignarBanco($id_banco,$id_cuenta = 0){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM bancos WHERE idBanco = '".$id_banco."'";
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);
	$objResponse->assign("txtSaldoCuenta","value","");
	$objResponse->assign("hddSaldoCuenta","value","");
	$objResponse->assign("hddIdChequera","value","");
	
	$objResponse->script("xajax_comboCuentas(xajax.getFormValues('frmBuscar'),".$row['idBanco'].",".$id_cuenta.");
						  $('txtNombreBanco').className = 'inputInicial';
						  $('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarDetallesRetencion($idRetencion, $cambioBaseImponible = "NO"){
	$objResponse = new xajaxResponse();

	$query = "SELECT id, importe, porcentaje, sustraendo, codigo FROM te_retenciones WHERE id = '".$idRetencion."'";
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

	$row = mysql_fetch_array($rs);

	$objResponse->assign("hddMontoMayorAplicar","value",$row['importe']);
	$objResponse->assign("hddPorcentajeRetencion","value",$row['porcentaje']);
	$objResponse->assign("hddSustraendoRetencion","value",$row['sustraendo']);
	$objResponse->assign("hddCodigoRetencion","value",$row['codigo']);
	$objResponse->assign("hddIdRetencion","value",$row['id']);
	$objResponse->script("calcularRetencion();");
	if($cambioBaseImponible == "SI"){
		$objResponse->script("calcularConBase();");
	}

	return $objResponse;
}

function asignarEmpresa($idEmpresa,$accion){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = ($idEmpresa == 0) ? $_SESSION['idEmpresaUsuarioSysGts'] : $idEmpresa;
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
	$nombreSucursal = "";
	
	if($rowEmpresa['id_empresa_padre_suc'] > 0){
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
	}
	
	$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
	
	$objResponse->assign("txtNombreEmpresa","value",$empresa);
	$objResponse->assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	if ($accion == 0){
		$objResponse->assign("txtNombreBanco","value","");
		$objResponse->assign("hddIdBanco","value","-1");
		$objResponse->assign("txtSaldoCuenta","value","");
		$objResponse->assign("hddSaldoCuenta","value","");
		$objResponse->assign("hddIdChequera","value","");
	}
	$objResponse->script("xajax_encabezadoEmpresa($idEmpresa)");
	$objResponse->script("$('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarFactura($valForm,$idFactura,$tipoDocumento){
	$objResponse = new xajaxResponse();
	
	$montoPagar = $valForm['txtMontoAPagar'];
	$montoRetencion = $valForm['txtMontoRetencionISLR'];
	
	if ($montoPagar < 1){
		$queryMontoPagarFacturaPropuesta = sprintf("SELECT monto_pagar, monto_retenido 
													FROM te_propuesta_pago_detalle 
													WHERE id_factura = %s 
													AND id_propuesta_pago = %s",
													valTpDato($idFactura,"int"),
													valTpDato($_GET['id_propuesta'],"int"));
		
		$rsMontoPagarFacturaPropuesta = mysql_query($queryMontoPagarFacturaPropuesta);
		if (!$rsMontoPagarFacturaPropuesta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$rowMontoPagarFacturaPropuesta = mysql_fetch_array($rsMontoPagarFacturaPropuesta);
		
		$montoPagar = $rowMontoPagarFacturaPropuesta['monto_pagar'];
		$montoRetencion = $rowMontoPagarFacturaPropuesta['monto_retenido'];
	}
        
	if($tipoDocumento==0){//factura
		$queryFactura = sprintf("SELECT id_proveedor, observacion_factura, numero_factura_proveedor, fecha_origen, saldo_factura, estatus_factura, fecha_vencimiento, DATEDIFF(NOW(), fecha_vencimiento) as dias_vencidos
								FROM cp_factura 
								WHERE id_factura = %s", 
								valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowFactura = mysql_fetch_assoc($rsFactura);
		
		$diasVencidos = ($rowFactura['dias_vencidos'] > 0) ? $rowFactura['dias_vencidos'] : 0;
		
		/* INSERTA EL ARTICULO MEDIANTE INJECT */
		$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px', 'title':'trItm:%s'}).adopt([
						new Element('td', {'align':'center', 'class':'noprint'}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s'>\"),
						new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
						new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
						new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
						new Element('td', {'align':'left', 'id':'tdItm:%s'}).setHTML(\"%s\"),
						new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
						new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
						new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s\"),
						new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s\"),
						new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s"."<input type='hidden' value='prueb'>\")
				]);
				elemento.injectBefore('trItmPie');",
				$idFactura."x0", $idFactura."x0",
				$idFactura."x0",
				$idFactura, "FA-".$idFactura,
				$idFactura, proveedor($rowFactura['id_proveedor']),
				$idFactura, utf8_encode(preg_replace('/\s+/', ' ', $rowFactura['observacion_factura'])),
				$idFactura, $rowFactura['numero_factura_proveedor'],
				$idFactura, date(spanDateFormat,strtotime($rowFactura['fecha_origen'])),
				$idFactura, $diasVencidos,
				$idFactura, number_format($rowFactura['saldo_factura'],2,'.',','),
				$idFactura, number_format($montoPagar,2,'.',','),
				$idFactura, number_format($montoRetencion,2,'.',',')));
	
	}else{//nota de cargo 
		$queryFactura = sprintf("SELECT id_proveedor, observacion_notacargo, numero_notacargo, fecha_origen_notacargo, saldo_notacargo, estatus_notacargo, fecha_vencimiento_notacargo, DATEDIFF(NOW(), fecha_vencimiento_notacargo) as dias_vencidos 
								FROM cp_notadecargo 
								WHERE id_notacargo = %s", 
								valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowFactura = mysql_fetch_assoc($rsFactura);
		
		$diasVencidos = ($rowFactura['dias_vencidos'] > 0) ? $rowFactura['dias_vencidos'] : 0;
	
		/* INSERTA EL ARTICULO MEDIANTE INJECT */
		$objResponse->script(sprintf("
                var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px', 'title':'trItm:%s'}).adopt([
			new Element('td', {'align':'center', 'class':'noprint'}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s'>\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'left', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s\"),
			new Element('td', {'align':'right', 'id':'tdItm:%s'}).setHTML(\"%s"."<input type='hidden' value='prueb'>\")
		]);
		elemento.injectBefore('trItmPie');",
		$idFactura."x1", $idFactura."x1",
		$idFactura."x1",
		$idFactura, "ND-".$idFactura,
		$idFactura, proveedor($rowFactura['id_proveedor']),
		$idFactura, utf8_encode($rowFactura['observacion_notacargo']),
		$idFactura, utf8_encode($rowFactura['numero_notacargo']),
		$idFactura, date(spanDateFormat,strtotime($rowFactura['fecha_origen_notacargo'])),
		$idFactura, $diasVencidos,
		$idFactura, number_format($rowFactura['saldo_notacargo'],2,'.',','),
		$idFactura, number_format($montoPagar,2,'.',','),
		$idFactura, number_format($montoRetencion,2,'.',',')));
	
	}
	
	$objResponse->assign("hddObj","value",$cadena);
	
	return $objResponse;
}

function asignarProveedor($id_proveedor,$id_empresa){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT id_proveedor, nombre, banco FROM cp_proveedor WHERE id_proveedor = %s",valTpDato($id_proveedor,"int"));
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$queryProveedorCredito = sprintf("SELECT reimpuesto FROM cp_prove_credito WHERE id_proveedor = %s",valTpDato($id_proveedor,"int"));
	$rsProveedorCredito = mysql_query($queryProveedorCredito);
	if(!$rsProveedorCredito){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if(mysql_num_rows($rsProveedorCredito)){
		$rowProveedorCredito = mysql_fetch_array($rsProveedorCredito);
		$objResponse->loadCommands(comboRetencionISLR(''));
		//retencion automatica configurada desde cuentas por pagar
		//$objResponse->loadCommands(asignarDetallesRetencion($rowProveedorCredito['reimpuesto']));
	}else{
		$objResponse->loadCommands(comboRetencionISLR(''));
	}
						
	$objResponse->assign("txtProveedorCabecera","value",utf8_encode($row['nombre']));
	$objResponse->assign("hddIdProveedorCabecera","value",$row['id_proveedor']);
	//$objResponse->loadCommands(asignarBanco($row['banco']));
    $objResponse->script("$('divFlotanteProv').style.display = 'none';");
	$objResponse->script("$('btnAgregarFactura').disabled = '';");
	$objResponse->script("$('btnAgregarNotaCargo').disabled = '';");
	
	return $objResponse;
}

function buscarDocumento($frmBuscar,$valFormFacturas){
	$objResponse = new xajaxResponse();
        
	$cadenaIdFacturasNotas = implode("x",explode("|",$valFormFacturas['arrayIdFactura']));
	$cadenaTipoDocumento = implode("x",explode("|",$valFormFacturas['arrayTipoDocumento']));
	
	if($frmBuscar['buscarFact'] == 1){
		$objResponse->script(sprintf("xajax_listaFacturas(0,'','','%s|%s|%s|%s|%s|%s|%s');",
				$valFormFacturas['hddIdEmpresa'],
				$valFormFacturas['hddIdProveedorCabecera'],
				$frmBuscar['lstModulo'],
				((count($frmBuscar['cbxDiasVencidos']) > 0) ? implode(",",$frmBuscar['cbxDiasVencidos']) : "-1"),
				$frmBuscar['txtCriterioBusq'],
				$cadenaIdFacturasNotas,
				$cadenaTipoDocumento));
				
	}else if($frmBuscar['buscarFact'] == 2){		
		$objResponse->script(sprintf("xajax_listaNotaCargo(0,'','','%s|%s|%s|%s|%s|%s|%s');",
				$valFormFacturas['hddIdEmpresa'],
				$valFormFacturas['hddIdProveedorCabecera'],
				$frmBuscar['lstModulo'],
				((count($frmBuscar['cbxDiasVencidos']) > 0) ? implode(",",$frmBuscar['cbxDiasVencidos']) : "-1"),
				$frmBuscar['txtCriterioBusq'],
				$cadenaIdFacturasNotas,
				$cadenaTipoDocumento));
	}
		
	return $objResponse;
}

function cargarPropuesta($idPropuesta){
	$objResponse = new xajaxResponse();
	
	$queryPropuesta = sprintf("SELECT idCuentas, idBanco, id_proveedor, id_empresa FROM vw_te_propuesta_pago WHERE id_propuesta_pago = %s",valTpDato($idPropuesta,"int"));
	$rsPropuesta = mysql_query($queryPropuesta);	
	if(!$rsPropuesta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$rowPropuesta = mysql_fetch_array($rsPropuesta);
	$objResponse->script("xajax_asignarBanco(".$rowPropuesta['idBanco'].",".$rowPropuesta['idCuentas'].");
						  xajax_asignarEmpresa(".$rowPropuesta['id_empresa'].",1);
						  xajax_asignarProveedor(".$rowPropuesta['id_proveedor'].",".$rowPropuesta['id_empresa'].");");	
	
	$queryPropuestaDetalle = sprintf("SELECT * FROM te_propuesta_pago_detalle WHERE id_propuesta_pago = %s",valTpDato($idPropuesta,"int"));
	$rsPropuestaDetalle = mysql_query($queryPropuestaDetalle);	
	if(!$rsPropuestaDetalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$monto_propuesta = 0;
	
	while ($rowPropuestaDetalle = mysql_fetch_array($rsPropuestaDetalle)){
		$arrayIdFactura .= "|".$rowPropuestaDetalle['id_factura'];
		$arrayTipoDocumento .= "|".$rowPropuestaDetalle['tipo_documento'];
		$arrayMonto .= "|".$rowPropuestaDetalle['monto_pagar'];
		$arraySustraendoRetencion .= "|".$rowPropuestaDetalle['sustraendo_retencion'];
		$arrayPorcentajeRetencion .= "|".$rowPropuestaDetalle['porcentaje_retencion'];
		$arrayMontoRetenido .= "|".$rowPropuestaDetalle['monto_retenido'];
		$monto_propuesta += $rowPropuestaDetalle['monto_pagar'];
		$arrayCodigoRetencion .= "|".$rowPropuestaDetalle['codigo'];
		$arrayIdRetencion .= "|".$rowPropuestaDetalle['id_retencion'];
		$arrayBaseImponibleRetencion .= "|".$rowPropuestaDetalle['base_imponible_retencion'];
		
		$objResponse->script("xajax_asignarFactura(xajax.getFormValues('frmBuscar'),".$rowPropuestaDetalle['id_factura'].",".$rowPropuestaDetalle['tipo_documento'].")");
	}

	$objResponse->assign("arrayIdFactura","value",$arrayIdFactura);
	$objResponse->assign("arrayTipoDocumento","value",$arrayTipoDocumento);
	$objResponse->assign("arrayMonto","value",$arrayMonto);
	$objResponse->assign("arraySustraendoRetencion","value",$arraySustraendoRetencion);
	$objResponse->assign("arrayCodigoRetencion","value",$arrayCodigoRetencion);
	$objResponse->assign("arrayIdRetencion","value",$arrayIdRetencion);
	$objResponse->assign("arrayBaseImponibleRetencion","value",$arrayBaseImponibleRetencion);
	$objResponse->assign("arrayPorcentajeRetencion","value",$arrayPorcentajeRetencion);
	$objResponse->assign("arrayMontoRetenido","value",$arrayMontoRetenido);
	$objResponse->assign("txtMontoPropuesta","value",number_format($monto_propuesta,"2",".",","));
	$objResponse->assign("hddMontoPropuesta","value",number_format($monto_propuesta,"2",".",""));

	$arraySumMonto1= explode("|", $arrayMonto);
	$SumaMonto = array_sum($arraySumMonto1);
	
	$arraySumMontoRetenido1= explode("|", $arrayMontoRetenido);
	$SumaMontoRetenido = array_sum($arraySumMontoRetenido1);
		
	$html .= "<table border=\"0\" cellpadding=\"2\" width=\"100%\" >";
	$html .= "<tr align=\"center\">";
		$html .= "<td width=\"600\" align=\"right\" class=\"tituloColumna\">Total";
		$html .= "</td>";
		$html .= "<td width=\"65\" align=\"right\">";
			$html .= htmlentities(number_format($SumaMonto,"2",".",","));
		$html .= "</td>";
		$html .= "<td width=\"65\" align=\"right\">";
			$html .= htmlentities(number_format($SumaMontoRetenido,"2",".",","));
		$html .= "</td>";		
	$html .= "</tr>";
	$html .= "</table>";
	
	$objResponse->assign("tdPrueb","innerHTML",$html);
	
	return $objResponse;
}

function cargaSaldoCuenta($id_cuenta,$valForm){
	$objResponse = new xajaxResponse();
	
	$arrayIdChequera = explode("|", $valForm['arrayIdChequera']);
	$arrayMontoCheque = explode("|", $valForm['arrayMonto']);
	$arrayMontoRetenido = explode("|", $valForm['arrayMontoRetenido']);

	$queryCuenta = sprintf("SELECT * FROM cuentas WHERE  idCuentas = '%s'",$id_cuenta);
	$rsCuenta = mysql_query($queryCuenta);
	if(!$rsCuenta){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuenta = mysql_fetch_array($rsCuenta);	

	$query = "SELECT * FROM vw_te_chequeras WHERE id_cuenta = '".$id_cuenta."' AND disponibles > 0";	
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	if(mysql_num_rows($rs) == 0){
		$objResponse->alert("La Cuenta Seleccionada no tiene chequera");
		$objResponse->assign("txtSaldoCuenta","value",number_format(0,'2','.',','));
		$objResponse->script("$('txtSaldoCuenta').className = 'inputInicial'");
		$objResponse->script("$('btnAgregarFactura').disabled = 'none';");
		$objResponse->assign("hddSaldoCuenta","value",number_format(0,'2','.',''));
		$objResponse->assign("hddIdChequera","value",0);
	}else{
		$objResponse->script("$('btnAgregarFactura').disabled = '';");
		$row = mysql_fetch_array($rs);
		$queryChequera = sprintf("SELECT id_chq FROM te_chequeras WHERE id_cuenta = '%s' AND disponibles > 0",$id_cuenta);
		$rsChequera = mysql_query($queryChequera);
		if(!$rsChequera){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$rowChequera = mysql_fetch_array($rsChequera);
		
		$Diferido = $rowCuenta['Diferido'];	
		$saldo = $rowCuenta['saldo_tem'];		
		$arrayPos = array_keys($arrayIdChequera,$rowChequera['id_chq']);
		for($i = 0; $i < count($arrayPos); $i++){
			$saldo -= $arrayMontoCheque[$arrayPos[$i]] + $arrayMontoRetenido[$arrayPos[$i]];
		}

		$objResponse->assign("txtDiferido","value",number_format($Diferido,'2','.',''));
		$objResponse->assign("hddDiferido","value",number_format($Diferido,'2','.',''));		
		$objResponse->assign("txtSaldoCuenta","value",number_format($saldo,'2','.',','));
		$objResponse->script("$('txtSaldoCuenta').className = 'inputInicial'");
		$objResponse->assign("hddSaldoCuenta","value",number_format($saldo,'2','.',''));
		$objResponse->assign("hddIdChequera","value",$rowChequera['id_chq']);
	}	

	return $objResponse;
}

function comboCuentas($valForm,$idBanco,$idCuenta = 0){
	$objResponse = new xajaxResponse();
	
	if ($valForm['hddIdBanco'] == -1){
		$disabled = "disabled=\"disabled\"";
	}else{
		if ($valForm['hddIdEmpresa'] == "" && $idCuenta != 0){
			$queryEmpresa = sprintf("SELECT id_empresa FROM cuentas WHERE idCuentas = %s",valTpDato($idCuenta,'int'));
			$rsEmpresa = mysql_query($queryEmpresa);			
			if (!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			$rowEmpresa = mysql_fetch_array($rsEmpresa);
			$idEmpresa = $rowEmpresa['id_empresa'];
		}else{
			$idEmpresa = $valForm['hddIdEmpresa'];
		}
		
		$condicion = "WHERE idBanco = '".valTpDato($idBanco,"int")."' AND id_empresa = '".valTpDato($idEmpresa,"int")."'";
		$disabled = "";
	}
	$queryCuentas = "SELECT * FROM cuentas ".$condicion."";

	$rsCuentas = mysql_query($queryCuentas);
	if(!$rsCuentas){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$html = "<select id=\"selCuenta\" name=\"selCuenta\" ".$disabled." class=\"inputHabilitado\" onchange=\"confirmarCambioCuenta(this.value); \">";
		$html .= "<option value=\"-1\">Seleccione</option>";
	while ($rowCuentas = mysql_fetch_assoc($rsCuentas)){
		if ($rowCuentas['idCuentas'] == $idCuenta){
			$selected = "selected='selected'";
			$objResponse->script("xajax_cargaSaldoCuenta(".$idCuenta.",xajax.getFormValues('frmBuscar'));");
		}else{
			$selected = "";
		}
		
		$html .= "<option value=\"".$rowCuentas['idCuentas']."\" ".$selected.">".$rowCuentas['numeroCuentaCompania']."</option>";
	}

	$html .= "</select>";
	
	$objResponse->assign("tdSelCuentas","innerHTML",$html);
		
	return $objResponse;
}

function comboRetencionISLR($id_retencion = ''){
	$objResponse = new xajaxResponse();
	
	$queryRetenciones = "SELECT * FROM te_retenciones WHERE activo = 1";
	$rsRetenciones = mysql_query($queryRetenciones);
	
	$html = "<select id=\"selRetencionISLR\" name=\"selRetencionISLR\" class=\"inputHabilitado\" onchange=\"xajax_asignarDetallesRetencion(this.value)\">";
	while($rowRetenciones = mysql_fetch_assoc($rsRetenciones)){
		if ($rowRetenciones['id'] == $id_retencion){
			$selected = " selected='selected'";
		}else{
			$selected = "";
		}
		
		$html .= "<option value=\"".$rowRetenciones['id']."\" ".$selected.">".utf8_encode($rowRetenciones['descripcion'])."</option>";
	}
	$html .= "</select>";
		
	$objResponse->assign("tdRetencionISLR","innerHTML",$html);
	$objResponse->assign("hddMontoMayorAplicar","innerHTML","0");
	$objResponse->assign("hddPorcentajeRetencion","innerHTML","0");
	$objResponse->assign("hddSustraendoRetencion","innerHTML","0");

	return $objResponse;
}

function eliminarFactura($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxItm'])) {
		foreach($valForm['cbxItm'] as $indiceItm => $valorItm){
                   
			$objResponse->script(sprintf("quitarFactura('%s')",$valorItm));
			
			$objResponse->script(sprintf("
				fila = byId('trItm:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	
	return $objResponse;
}

function facturaSeleccionada($idDocumento,$tipo){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("$('txtMontoAPagar').className = 'inputHabilitado'");
	if($tipo==0){//factura
		$query = sprintf("SELECT id_proveedor, numero_factura_proveedor, observacion_factura, saldo_factura FROM cp_factura WHERE id_factura = %s;",$idDocumento);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);		
		$row = mysql_fetch_array($rs);
		
		$objResponse->assign("hddIdFactura","value",$idDocumento);
		$objResponse->assign("txtProveedor","value",proveedor($row['id_proveedor']));
		$objResponse->assign("txtNumeroFactura","value",$row['numero_factura_proveedor']);
		$objResponse->assign("txtDescripcion","value",utf8_encode($row['observacion_factura']));
		$objResponse->assign("txtSaldoFactura","value",number_format($row['saldo_factura'],2,".",""));
		$objResponse->assign("txtMontoAPagar","value",number_format($row['saldo_factura'],2,".",""));
		$objResponse->assign("hddTipoDocumento","value","0");
		
		$queryIvaFactura = sprintf("SELECT base_imponible, iva FROM cp_factura_iva WHERE id_factura = %s ",$idDocumento);
		$rsIvaFactura = mysql_query($queryIvaFactura);		
		if(!$rsIvaFactura){ return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__); }
		
		if (mysql_num_rows($rsIvaFactura)){
			$rowIvaFactura = mysql_fetch_array($rsIvaFactura);
			$objResponse->assign("hddIva","value",$rowIvaFactura['iva']);
			$objResponse->assign("hddBaseImponible","value",$rowIvaFactura['base_imponible']);
		}else{
			$objResponse->assign("hddIva","value","0");
			$objResponse->assign("hddBaseImponible","value","0");
		}
            
	}else{
		$queryNotaCargo = sprintf("SELECT numero_notacargo,fecha_origen_notacargo, fecha_vencimiento_notacargo , observacion_notacargo, saldo_notacargo, id_proveedor FROM cp_notadecargo WHERE id_notacargo = '%s'",$idDocumento);
		$rsNotaCargo = mysql_query($queryNotaCargo);
		if(!$rsNotaCargo){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }		
		$rowNotaCargo = mysql_fetch_assoc($rsNotaCargo);
		
		$objResponse->assign("hddIdFactura","value",$idDocumento);
		$objResponse->assign("txtProveedor","value",proveedor($rowNotaCargo['id_proveedor']));
		$objResponse->assign("txtNumeroFactura","value",utf8_encode($rowNotaCargo['numero_notacargo']));
		$objResponse->assign("txtSaldoFactura","value",$rowNotaCargo['saldo_notacargo']);
		$objResponse->assign("txtMontoAPagar","value",$rowNotaCargo['saldo_notacargo']);
		$objResponse->assign("txtDescripcion","innerHTML",utf8_encode($rowNotaCargo['observacion_notacargo']));
		$objResponse->assign("hddTipoDocumento","value","1");
		
		$queryIvaNotaCargo = sprintf("SELECT baseimponible, iva  FROM cp_notacargo_iva WHERE id_notacargo = %s ",$idDocumento);
		$rsIvaNotaCargo = mysql_query($queryIvaNotaCargo);
		
		if (!$rsIvaNotaCargo) return $objResponse->alert(mysql_query()."\n\nLINE: ".__LINE__);
		
		if (mysql_num_rows($rsIvaNotaCargo)){
			$rowIvaNotaCargo = mysql_fetch_array($rsIvaNotaCargo);
			$objResponse->assign("hddIva","value",$rowIvaNotaCargo['iva']);
			$objResponse->assign("hddBaseImponible","value",$rowIvaNotaCargo['baseimponible']);
		}else{
			$objResponse->assign("hddIva","value","0");
			$objResponse->assign("hddBaseImponible","value","0");
		}
		
	}
	
	$objResponse->script("$('divFlotante').style.display = '';
						  $('tblFactura').style.display = '';
						  centrarDiv($('divFlotante'));
						  $('tdFlotanteTitulo').innerHTML = 'Seleccione Cuenta';
						  xajax_verificarRetencionISLR(".$idDocumento.",".$tipo.");");
	
	return $objResponse;
}


function guardarPropuesta($valForm){
	
	$objResponse = new xajaxResponse();
        
	mysql_query("START TRANSACTION;");

	$arrayIdFacturas = explode("|", $valForm['arrayIdFactura']);
	$arrayMontoCheque = explode("|", $valForm['arrayMonto']);
	$arraySustraendoRetencion = explode("|", $valForm['arraySustraendoRetencion']);
	$arrayPorcentajeRetencion = explode("|", $valForm['arrayPorcentajeRetencion']);
	$arrayMontoRetenido = explode("|", $valForm['arrayMontoRetenido']);
	$arrayCodigoRetencion = explode("|", $valForm['arrayCodigoRetencion']);
	$arrayIdRetencion = explode("|", $valForm['arrayIdRetencion']);
	$arrayBaseImponibleRetencion = explode("|", $valForm['arrayBaseImponibleRetencion']);
	$arrayTipoDocumento = explode("|", $valForm['arrayTipoDocumento']);
	
	if ($valForm['hddIdPropuesta'] == 0){
		$queryInsertCabecera = sprintf("INSERT INTO te_propuesta_pago (id_propuesta_pago, fecha_propuesta_pago, estatus_propuesta, id_chequera) VALUES('', NOW(), 0, %s);",
									$valForm['hddIdChequera']);

		$rsInsertCabecera = mysql_query($queryInsertCabecera);
		if (!$rsInsertCabecera){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
		$idPropuestaPago = mysql_insert_id();
		
		$queryNumCuenta = sprintf("SELECT id_cuenta FROM te_chequeras WHERE id_chq = %s",$valForm['hddIdChequera']);
		$rsNumCuenta = mysql_query($queryNumCuenta);
		$rowNumCuenta = mysql_fetch_array($rsNumCuenta);
		
		$updateSaldo_Diferido = sprintf("UPDATE cuentas SET Diferido = '%s' WHERE idCuentas = %s ;",$valForm['hddDiferido'],$rowNumCuenta['id_cuenta']);	
		$rsUpdateSaldo_Diferido = mysql_query($updateSaldo_Diferido);
		if (!$rsUpdateSaldo_Diferido){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
	
		foreach($arrayIdFacturas as $indice => $valor){
			if ($valor != ""){
				$queryInsertDetalle = sprintf(" INSERT INTO te_propuesta_pago_detalle (id_propuesta_pago_detalle, id_propuesta_pago, id_factura, monto_pagar, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, id_retencion, base_imponible_retencion, tipo_documento) VALUES('', %s, %s, '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s')",
								$idPropuestaPago,
								$valor,
								$arrayMontoCheque[$indice],
								$arraySustraendoRetencion[$indice],
								$arrayPorcentajeRetencion[$indice],
								$arrayMontoRetenido[$indice],
								$arrayCodigoRetencion[$indice],
								valTpDato($arrayIdRetencion[$indice],"int"),
								$arrayBaseImponibleRetencion[$indice],
								$arrayTipoDocumento[$indice]);
								
				$rsInsertDetalle = mysql_query($queryInsertDetalle);
				if (!$rsInsertDetalle){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			}
		}
			
		$objResponse->alert("Propuesta guardada exitosamente");	
		$objResponse->script("window.open('te_propuesta_pago_mantenimiento.php','_self');");	

	}else{
		$queryDeletePropuesta = sprintf("DELETE FROM te_propuesta_pago_detalle WHERE id_propuesta_pago = %s",$valForm['hddIdPropuesta']);
		$rsDeletePropuesta = mysql_query($queryDeletePropuesta);
		if (!$rsDeletePropuesta){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

		foreach($arrayIdFacturas as $indice => $valor){
			if ($valor != ""){
				$queryNumCuenta = sprintf("SELECT id_cuenta FROM te_chequeras WHERE id_chq = %s",$valForm['hddIdChequera']);
				$rsNumCuenta = mysql_query($queryNumCuenta);
				if(!$rsNumCuenta){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$rowNumCuenta = mysql_fetch_array($rsNumCuenta);
				
				$updateSaldo_Diferido = sprintf("UPDATE cuentas SET Diferido = '%s' WHERE idCuentas = %s ;",$valForm['hddDiferido'],$rowNumCuenta['id_cuenta']);	
				$rsUpdateSaldo_Diferido = mysql_query($updateSaldo_Diferido);				
				if (!$rsUpdateSaldo_Diferido){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				
				$queryInsertDetalle = sprintf(" INSERT INTO te_propuesta_pago_detalle (id_propuesta_pago, id_factura, monto_pagar, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, id_retencion, base_imponible_retencion, tipo_documento) VALUES(%s, %s, '%s', '%s', '%s', '%s','%s', '%s','%s','%s')",
												$valForm['hddIdPropuesta'],
												$valor,
												$arrayMontoCheque[$indice],
												$arraySustraendoRetencion[$indice],
												$arrayPorcentajeRetencion[$indice],
												$arrayMontoRetenido[$indice],
												$arrayCodigoRetencion[$indice],
												valTpDato($arrayIdRetencion[$indice],"int"),
												$arrayBaseImponibleRetencion[$indice],
												$arrayTipoDocumento[$indice]);
				$rsInsertDetalle = mysql_query($queryInsertDetalle);
				
				if (!$rsInsertDetalle){ errorGuardarDcto($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }				
				
			}
		}
		
		$objResponse->alert("Propuesta Editada exitosamente");	
		$objResponse->script("window.open('te_propuesta_pago_mantenimiento.php','_self');");
	}

	mysql_query("COMMIT;");

	return $objResponse;
}

function listaFacturas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayIdFacturas = explode("x", $valCadBusq[5]);//$valForm['arrayIdFactura']
	$arrayTipoDocumento = array_diff(explode("x", $valCadBusq[6]),array("1"));//$valForm['arrayTipoDocumento']	
	$arrayIdFacturas = array_intersect_key($arrayIdFacturas,$arrayTipoDocumento);
	
	$valCadBusq[0] = ($valCadBusq[0] == 0) ? $_SESSION['idEmpresaUsuarioSysGts'] : $valCadBusq[0];
		
	if(count($arrayIdFacturas) > 1){
		foreach($arrayIdFacturas as $indiceIdFactura => $valorIdFactura){
			$facturas .= $valorIdFactura.",";
		}
		$facturas = substr ($facturas, 1, strlen($facturas));
		$facturas = substr ($facturas, 0, strlen($facturas) - 1);
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" id_factura NOT IN (%s) ",
			valTpDato($facturas, 'campo'));
	}	 
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf(" (cxp_fact.id_empresa = %s OR cxp_fact.id_empresa IN ((SELECT id_empresa_reg 
																FROM vw_iv_empresas_sucursales 
																WHERE vw_iv_empresas_sucursales.id_empresa_padre_suc = %s )))
		AND cxp_fact.id_proveedor = %s 
		AND cxp_fact.estatus_factura <> 1 
		AND cxp_fact.id_factura NOT IN (SELECT te_propuesta_pago_detalle.id_factura 
											FROM te_propuesta_pago_detalle
											INNER JOIN te_propuesta_pago ON te_propuesta_pago_detalle.id_propuesta_pago = te_propuesta_pago.id_propuesta_pago
											WHERE te_propuesta_pago_detalle.tipo_documento <> 1 
											AND te_propuesta_pago.estatus_propuesta = 0														
		
											UNION ALL

											SELECT te_propuesta_pago_detalle_transferencia.id_factura 
											FROM te_propuesta_pago_detalle_transferencia
											INNER JOIN te_propuesta_pago_transferencia ON te_propuesta_pago_detalle_transferencia.id_propuesta_pago = te_propuesta_pago_transferencia.id_propuesta_pago
											WHERE te_propuesta_pago_detalle_transferencia.tipo_documento <> 1 
											AND te_propuesta_pago_transferencia.estatus_propuesta = 0
											) ",
	valTpDato($valCadBusq[0],"int"),
	valTpDato($valCadBusq[0],"int"),
	valTpDato($valCadBusq[1],"int"));
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_fact.id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$arrayDiasVencidos = NULL;
		if (in_array("corriente",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde1",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde2",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde3",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("masDe",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))");
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
		OR prov.nombre LIKE %s
		OR cxp_fact.numero_factura_proveedor LIKE %s
		OR cxp_fact.numero_control_factura LIKE %s
		OR cxp_fact.observacion_factura LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
		
	$query = sprintf("SELECT cxp_fact.*,
		DATEDIFF(NOW(), cxp_fact.fecha_vencimiento) as dias_vencidos,
	
		(SELECT orden_tot.id_orden_tot FROM sa_orden_tot orden_tot
		WHERE orden_tot.id_factura = cxp_fact.id_factura) AS id_orden_tot,
		
		(CASE cxp_fact.estatus_factura
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_factura,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		CONCAT_WS('-', prov.lnit, prov.nit) AS nit_proveedor,
		prov.nombre AS nombre_proveedor,
		
		(CASE id_modulo
			WHEN 1 THEN
				(SELECT COUNT(orden_tot.id_factura)
				FROM sa_orden_tot orden_tot
					INNER JOIN sa_orden_tot_detalle orden_tot_det ON (orden_tot.id_orden_tot = orden_tot_det.id_orden_tot)
				WHERE orden_tot.id_factura = cxp_fact.id_factura)
			WHEN 2 THEN
				(SELECT COUNT(cxp_fact_det_unidad.id_factura) FROM cp_factura_detalle_unidad cxp_fact_det_unidad
				WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura)
				+
				(SELECT COUNT(cxp_fact_det_acc.id_factura) FROM cp_factura_detalle_accesorio cxp_fact_det_acc
				WHERE cxp_fact_det_acc.id_factura = cxp_fact.id_factura)
			ELSE
				(SELECT COUNT(cxp_fact_det.id_factura) FROM cp_factura_detalle cxp_fact_det
				WHERE cxp_fact_det.id_factura = cxp_fact.id_factura)
		END) AS cant_items,
		
		(SELECT SUM(cxp_fact_det.cantidad) FROM cp_factura_detalle cxp_fact_det
		WHERE cxp_fact_det.id_factura = cxp_fact.id_factura) AS cant_piezas,
		
		moneda_local.abreviacion AS abreviacion_moneda_local,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.idFactura = cxp_fact.id_factura
		LIMIT 1) AS idRetencionCabezera,
		
		(SELECT reten_cheque.id_retencion_cheque FROM te_retencion_cheque reten_cheque
		WHERE reten_cheque.id_factura = cxp_fact.id_factura
			AND reten_cheque.tipo IN (0)
			AND reten_cheque.anulado IS NULL) AS id_retencion_cheque,
		
		(SELECT
			nota_cargo.id_notacargo
		FROM cp_notadecargo nota_cargo
			INNER JOIN an_unidad_fisica uni_fis ON (nota_cargo.id_detalles_pedido_compra = uni_fis.id_pedido_compra_detalle)
			INNER JOIN cp_factura_detalle_unidad cxp_fact_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = cxp_fact_det_unidad.id_factura_detalle_unidad)
			LEFT JOIN pg_motivo motivo ON (nota_cargo.id_motivo = motivo.id_motivo)
			INNER JOIN pg_modulos modulo ON (nota_cargo.id_modulo = modulo.id_modulo)
		WHERE cxp_fact_det_unidad.id_factura = cxp_fact.id_factura) AS id_nota_cargo_planmayor,
		
		(IFNULL(cxp_fact.subtotal_factura, 0)
			- IFNULL(cxp_fact.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
					FROM cp_factura_gasto cxp_fact_gasto
					WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
						AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)) AS total_neto,
		
		(IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
				FROM cp_factura_iva cxp_fact_iva
				WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total_iva,
		
		(IFNULL(cxp_fact.subtotal_factura, 0)
			- IFNULL(cxp_fact.subtotal_descuento, 0)
			+ IFNULL((SELECT SUM(cxp_fact_gasto.monto) AS total_gasto
					FROM cp_factura_gasto cxp_fact_gasto
					WHERE cxp_fact_gasto.id_factura = cxp_fact.id_factura
						AND cxp_fact_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(cxp_fact_iva.subtotal_iva) AS total_iva
					FROM cp_factura_iva cxp_fact_iva
					WHERE cxp_fact_iva.id_factura = cxp_fact.id_factura), 0)) AS total,
		
		cxp_fact.activa,
		vw_iv_usuario.nombre_empleado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_factura cxp_fact
		INNER JOIN cp_proveedor prov ON (cxp_fact.id_proveedor = prov.id_proveedor)
		LEFT JOIN pg_monedas moneda_local ON (cxp_fact.id_moneda = moneda_local.idmoneda)
		LEFT JOIN vw_iv_usuarios vw_iv_usuario ON (cxp_fact.id_empleado_creador = vw_iv_usuario.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
  
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\" class=\"texto_10px\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= "<td width=\"1%\" colspan=\"3\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "14%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "6%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "6%", $pageNum, "fecha_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Factura Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "6%", $pageNum, "fecha_vencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "8%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "28%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "8%", $pageNum, "descripcion_estado_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "2%", $pageNum, "dias_vencidos", $campOrd, $tpOrd, $valBusq, $maxRows, "D&iacute;as Vencidos");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "4%", $pageNum, "cant_items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "8%", $pageNum, "saldo_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturas", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"".utf8_encode("Repuestos")."\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"".utf8_encode("Servicios")."\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"".utf8_encode("Veh&iacute;culos")."\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"".utf8_encode("Administraci&oacute;n")."\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['cant_items'] > 0 || $row['id_orden_tot'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
		
		switch($row['activa']) {
			case "" : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Compra Registrada (Con Devoluci&oacute;n)\"/>"; break;
			case 1 : $imgEstatusRegistroCompra = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Compra Registrada\"/>"; break;
			default : $imgEstatusRegistroCompra = "";
		}
		
		switch($row['estatus_factura']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$diasVencidos = ($row['dias_vencidos'] > 0) ? $row['dias_vencidos'] : 0;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td><button type=\"button\" onclick=\"validarChequeraSeleccionada(".$row['id_factura'].",0);\" title=\"Seleccionar Factura\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button></td>";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusRegistroCompra."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado']) > 0) ? "title=\"Factura Nro: ".utf8_encode($row['numero_factura_proveedor']).". Registrado por: ".utf8_encode($row['nombre_empleado'])."\"" : "").">".date(spanDateFormat,strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat,strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat,strtotime($row['fecha_vencimiento']))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".(($row['id_nota_cargo_planmayor'] > 0) ? "<img src=\"../img/iconos/ico_plan_mayor.png\" title=\"Factura por Plan Mayor\"/>" : "")."</td>";
					$htmlTb .= "<td align=\"right\" width=\"100%\">".utf8_encode($row['numero_factura_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['observacion_factura']) > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".utf8_encode($row['observacion_factura'])."<span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['descripcion_estado_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$diasVencidos."</td>";			
			$htmlTb .= "<td align=\"right\">".number_format($row['cant_items'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['saldo_factura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['total'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"24\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturas(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"24\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
								  
							  
	$objResponse->assign("tdListadoFacNcargo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
	$objResponse->script("
		$('tblFacturasNcargos').style.display = '';");
	
	$objResponse->assign("tdFlotanteTituloDoc","innerHTML","Facturas");
	$objResponse->script("
		if ($('divFlotanteDoc').style.display == 'none') {
			$('divFlotanteDoc').style.display = '';
			centrarDiv($('divFlotanteDoc'));
			
			document.forms['frmBuscarDocumento'].reset();
			$('txtCriterioBusq').focus();
		}
	");

	return $objResponse;
}

function listaNotaCargo($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	$condicionNotIn = "";
	$arrayIdFacturas = explode("x", $valCadBusq[5]);//$valForm['arrayIdFactura']
	$arrayTipoDocumento = array_diff(explode("x", $valCadBusq[6]),array("0"));//$valForm['arrayTipoDocumento']	
	$arrayIdFacturas = array_intersect_key($arrayIdFacturas,$arrayTipoDocumento);
	
	if (count($arrayIdFacturas) > 1){
		foreach($arrayIdFacturas as $indiceIdFactura => $valorIdFactura){
			$facturas .= $valorIdFactura.",";
		}
		$facturas = substr ($facturas, 1, strlen($facturas));
		$facturas = substr ($facturas, 0, strlen($facturas) - 1);
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" id_notacargo NOT IN (%s) ",
			valTpDato($facturas, 'campo'));
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf(" (cxp_nd.id_empresa = %s OR cxp_nd.id_empresa IN ((SELECT id_empresa_reg 
																FROM vw_iv_empresas_sucursales 
																WHERE vw_iv_empresas_sucursales.id_empresa_padre_suc = %s ))) 
					AND cxp_nd.id_proveedor = %s 
					AND cxp_nd.estatus_notacargo <> 1 
					AND cxp_nd.id_notacargo NOT IN (SELECT te_propuesta_pago_detalle.id_factura
															FROM te_propuesta_pago_detalle 
															INNER JOIN te_propuesta_pago ON te_propuesta_pago_detalle.id_propuesta_pago = te_propuesta_pago.id_propuesta_pago
															WHERE te_propuesta_pago_detalle.tipo_documento <> 0
															AND te_propuesta_pago.estatus_propuesta = 0
															
															UNION ALL
															
															SELECT te_propuesta_pago_detalle_transferencia.id_factura 
															FROM te_propuesta_pago_detalle_transferencia 
															INNER JOIN te_propuesta_pago_transferencia ON te_propuesta_pago_detalle_transferencia.id_propuesta_pago = te_propuesta_pago_transferencia.id_propuesta_pago															
															WHERE te_propuesta_pago_detalle_transferencia.tipo_documento <> 0
															AND te_propuesta_pago_transferencia.estatus_propuesta = 0
															) ",
	valTpDato($valCadBusq[0],"int"),
	valTpDato($valCadBusq[0],"int"),
	valTpDato($valCadBusq[1],"int"));
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cxp_nd.id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$arrayDiasVencidos = NULL;
		if (in_array("corriente",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) < (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde1",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) >= (SELECT grupo_ec.desde1 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) <= (SELECT grupo_ec.hasta1 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde2",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) >= (SELECT grupo_ec.desde2 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) <= (SELECT grupo_ec.hasta2 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("desde3",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) >= (SELECT grupo_ec.desde3 FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1)
			AND DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) <= (SELECT grupo_ec.hasta3 FROM gruposestadocuenta grupo_ec
																WHERE grupo_ec.idGrupoEstado = 1))");
		}
		if (in_array("masDe",explode(",",$valCadBusq[3]))) {
			$arrayDiasVencidos[] = sprintf("(DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) >= (SELECT grupo_ec.masDe FROM gruposestadocuenta grupo_ec
																							WHERE grupo_ec.idGrupoEstado = 1))");
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ", $arrayDiasVencidos).")";
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', prov.lrif, prov.rif) LIKE %s
			OR prov.nombre LIKE %s
			OR cxp_nd.numero_notacargo LIKE %s
			OR cxp_nd.numero_control_notacargo LIKE %s
			OR (SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
				WHERE uni_fis.id_pedido_compra_detalle = cxp_nd.id_detalles_pedido_compra) LIKE %s
			OR observacion_notacargo LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}

		   
	$query = sprintf("SELECT cxp_nd.*,
		DATEDIFF(NOW(), cxp_nd.fecha_vencimiento_notacargo) as dias_vencidos,
	
		(CASE cxp_nd.estatus_notacargo
			WHEN 0 THEN 'No Cancelado'
			WHEN 1 THEN 'Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_nota_cargo,
		CONCAT_WS('-', prov.lrif, prov.rif) AS rif_proveedor,
		prov.nombre AS nombre_proveedor,
		
		motivo.id_motivo,
		motivo.descripcion AS descripcion_motivo,
		
		(SELECT retencion.idRetencionCabezera
		FROM cp_retenciondetalle retencion_det
			INNER JOIN cp_retencioncabezera retencion ON (retencion_det.idRetencionCabezera = retencion.idRetencionCabezera)
		WHERE retencion_det.id_nota_cargo = cxp_nd.id_notacargo
		LIMIT 1) AS idRetencionCabezera,
		
		(SELECT
			fact_comp.id_factura
		FROM an_unidad_fisica uni_fis
			INNER JOIN cp_factura_detalle_unidad fact_comp_det_unidad ON (uni_fis.id_factura_compra_detalle_unidad = fact_comp_det_unidad.id_factura_detalle_unidad)
			INNER JOIN cp_factura fact_comp ON (fact_comp_det_unidad.id_factura = fact_comp.id_factura)
		WHERE uni_fis.id_pedido_compra_detalle = cxp_nd.id_detalles_pedido_compra) AS id_factura_planmayor,
		
		(SELECT uni_fis.serial_carroceria FROM an_unidad_fisica uni_fis
		WHERE uni_fis.id_pedido_compra_detalle = cxp_nd.id_detalles_pedido_compra) AS serial_carroceria,
		
		(IFNULL(cxp_nd.subtotal_notacargo, 0)
			- IFNULL(cxp_nd.subtotal_descuento_notacargo, 0)
			+ IFNULL((SELECT SUM(cxp_nd_gasto.monto) AS total_gasto FROM cp_notacargo_gastos cxp_nd_gasto
					WHERE cxp_nd_gasto.id_notacargo = cxp_nd.id_notacargo
						AND cxp_nd_gasto.id_modo_gasto IN (1,3)), 0)
			+ IFNULL((SELECT SUM(cxp_nd_iva.subtotal_iva) AS total_iva FROM cp_notacargo_iva cxp_nd_iva
					WHERE cxp_nd_iva.id_notacargo = cxp_nd.id_notacargo), 0)) AS total,
		
		vw_iv_usuario.nombre_empleado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cp_proveedor prov
		INNER JOIN cp_notadecargo cxp_nd ON (prov.id_proveedor = cxp_nd.id_proveedor)
		LEFT JOIN pg_motivo motivo ON (cxp_nd.id_motivo = motivo.id_motivo)
		LEFT JOIN vw_iv_usuarios vw_iv_usuario ON (cxp_nd.id_empleado_creador = vw_iv_usuario.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxp_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)  %s", $sqlBusq);
  
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\" class=\"texto_10px\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= "<td width=\"1%\" colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "14%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "fecha_origen_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "fecha_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Nota de D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "fecha_vencimiento_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Venc. Nota de D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "6%", $pageNum, "numero_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Nota de D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "34%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "8%", $pageNum, "descripcion_estado_nota_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Nota de D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "2%", $pageNum, "dias_vencidos", $campOrd, $tpOrd, $valBusq, $maxRows, "D&iacute;as Vencidos");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "8%", $pageNum, "saldo_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Nota de D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaNotaCargo", "8%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Nota de D&eacute;bito");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Veh&iacute;culos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administraci&oacute;n\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"".utf8_encode("Alquiler")."\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['cant_items'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_pagar.gif\" title=\"Creada por CxP\"/>";
		
		switch($row['estatus_notacargo']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		$diasVencidos = ($row['dias_vencidos'] > 0) ? $row['dias_vencidos'] : 0;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"validarChequeraSeleccionada(".$row['id_notacargo'].",1);\" title=\"Seleccionar Nota Cargo\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";			
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado']) > 0) ? "title=\"Nro. Nota de D&eacute;bito: ".utf8_encode($row['numero_notacargo']).". Registrado por: ".utf8_encode($row['nombre_empleado'])."\"" : "").">".date(spanDateFormat,strtotime($row['fecha_origen_notacargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_notacargo']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_vencimiento_notacargo']))."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".(($row['id_factura_planmayor'] > 0 || $row['id_detalles_pedido_compra'] > 0) ? "<img src=\"../img/iconos/ico_plan_mayor.png\" title=\"Nota de D&eacute;bito de Factura por Plan Mayor\"/>" : "")."</td>";
					$htmlTb .= "<td align=\"right\" width=\"100%\">".utf8_encode($row['numero_notacargo'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['serial_carroceria']) > 0) ? "<tr><td><span class=\"textoNegrita_10px\">".utf8_encode($row['serial_carroceria'])."</span></td></tr>" : "";
				$htmlTb .= ($row['id_motivo'] > 0) ? "<tr><td><span class=\"textoNegrita_9px\">".$row['id_motivo'].".- ".utf8_encode($row['descripcion_motivo'])."</span></td></tr>" : "";
				$htmlTb .= ((strlen($row['observacion_notacargo']) > 0) ? "<tr><td>".utf8_encode($row['observacion_notacargo'])."</td></tr>" : "");
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['descripcion_estado_nota_cargo']."</td>";
			$htmlTb .= "<td align=\"center\">".$diasVencidos."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldo_notacargo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"24\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaNotaCargo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"24\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	
	}
	
	$objResponse->assign("tdListadoFacNcargo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
	$objResponse->script("
		$('tblFacturasNcargos').style.display = '';");
	
	$objResponse->assign("tdFlotanteTituloDoc","innerHTML","Notas de Cargo");
	$objResponse->script("
		if ($('divFlotanteDoc').style.display == 'none') {
			$('divFlotanteDoc').style.display = '';
			centrarDiv($('divFlotanteDoc'));
			
			document.forms['frmBuscarDocumento'].reset();
			$('txtCriterioBusq').focus();
		}
	");

	return $objResponse;
	
}



function listarProveedores($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" WHERE CONCAT_WS('-',lrif,rif) LIKE %s
		OR CONCAT_WS('',lrif,rif) LIKE %s
		OR nombre LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
						id_proveedor AS id,
						CONCAT_WS('-',lrif,rif) as rif_proveedor,
						nombre
					FROM cp_proveedor %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "20%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Cedula / RIF.");
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "65%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\"onclick=\"xajax_asignarProveedor('".$row['id']."','".$valCadBusq[0]."');\" title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarProveedores(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	
	$objResponse->assign("tdListadoProveedores","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	//$objResponse->assign("tdCabeceraEstado","innerHTML","");
	
	$objResponse->script("
		<!--$('trBuscarCliente').style.display = '';-->
		
		$('tblListadoProveedor').style.display = '';");
	
	$objResponse->assign("tdFlotanteTituloProv","innerHTML","Proveedores");
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if ($('divFlotanteProv').style.display == 'none') {
			$('divFlotanteProv').style.display = '';
			centrarDiv($('divFlotanteProv'));
			
			document.forms['frmBuscarCliente'].reset();
			$('txtCriterioBusqProveedor').focus();
		}
	");
	
	return $objResponse;
}

function listBanco($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	$queryBanco = "SELECT bancos.idBanco, bancos.nombreBanco, bancos.sucursal FROM bancos INNER JOIN cuentas ON (cuentas.idBanco = bancos.idBanco) WHERE bancos.idBanco != '1' GROUP BY idBanco";
	$rsBanco = mysql_query($queryBanco);
	if(!$rsBanco){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitBanco = sprintf(" %s %s LIMIT %d OFFSET %d", $queryBanco, $sqlOrd, $maxRows, $startRow);        
	$rsLimitBanco = mysql_query($queryLimitBanco);
	if(!$rsLimitBanco){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsBanco = mysql_query($queryBanco);
		if(!$rsBanco){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsBanco);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
		$htmlTh .= ordenarCampo("xajax_listBanco", "15%", $pageNum, "idBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Banco");
		$htmlTh .= ordenarCampo("xajax_listBanco", "40%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Banco");
		$htmlTh .= ordenarCampo("xajax_listBanco", "45%", $pageNum, "sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Sucursal");					
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowBanco = mysql_fetch_assoc($rsLimitBanco)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarBanco('".$rowBanco['idBanco']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['idBanco']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['sucursal'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listBanco(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listBanco(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("tdDescripcion","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("$('divFlotante1').style.display = '';
						  $('tblBancos').style.display = '';
						  $('tdFlotanteTitulo1').innerHTML = 'Seleccione Banco';
						  centrarDiv($('divFlotante1'));");	
		
	return $objResponse;
}

function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$valCadBusq[0] = ($valCadBusq[0] == "") ? 0 : $valCadBusq[0];
	
	if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
        
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
        
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa);
	if(!$rsLimitEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa);
		if(!$rsEmpresa){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsEmpresa);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
		$htmlTh .= ordenarCampo("xajax_listEmpresa", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
		$htmlTh .= ordenarCampo("xajax_listEmpresa", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");			
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowBanco = mysql_fetch_assoc($rsLimitEmpresa)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$rowBanco['id_empresa_reg']."',0);\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombre_empresa']." - ".$rowBanco['nombre_empresa_suc'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listEmpresa(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("tdDescripcion","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("$('divFlotante1').style.display = '';
						  $('tblBancos').style.display = '';
						  $('tdFlotanteTitulo1').innerHTML = 'Seleccione Empresa';
						  centrarDiv($('divFlotante1'))");	
	
	return $objResponse;
}

function verificarClave($valForm){
	$objResponse = new xajaxResponse();
	
	$queryClave = sprintf("SELECT contrasena FROM vw_pg_claves_modulos WHERE id_usuario = %s AND id_clave_modulo = 34",
				valTpDato($_SESSION['idUsuarioSysGts'],'int'));
	$rsClave = mysql_query($queryClave);
	if (!$rsClave) return $objResponse->alert(mysql_error()."\n\nLINE: "._LINE_);
	
	if (mysql_num_rows($rsClave)){
		$rowClave = mysql_fetch_array($rsClave);
		if ($rowClave['contrasena'] == $valForm['txtClaveAprobacion']){
			$objResponse->assign("hddPermiso","value",1);
			$objResponse->script("$('divFlotante2').style.display = 'none';");
		}
		else
			$objResponse->alert(utf8_encode("Clave Errada."));
	}
	else{
		$objResponse->alert("No tiene permiso para realizar esta accion");
		$objResponse->script("$('divFlotante').style.display = 'none';");
		$objResponse->script("$('divFlotante2').style.display = 'none';");
	}
	
	return $objResponse;
}

function verificarRetencionISLR($idDocumento, $tipoDocumento){
	$objResponse = new xajaxResponse();
	
	if($tipoDocumento == 0){//FACTURA
		$tipoDocumentoPago = "FA";
	}elseif($tipoDocumento == 1){//NOTA DE CARGO
		$tipoDocumentoPago = "ND";
	}
	
	$query = sprintf("SELECT te_retenciones.descripcion
						FROM cp_pagos_documentos pago
						INNER JOIN te_retencion_cheque ON te_retencion_cheque.id_retencion_cheque = pago.id_documento
						INNER JOIN te_retenciones ON te_retencion_cheque.id_retencion = te_retenciones.id
						WHERE pago.tipo_pago = 'ISLR' 
						AND pago.estatus = 1 
						AND pago.tipo_documento_pago = %s 
						AND pago.id_documento_pago = %s",
				valTpDato($tipoDocumentoPago,"text"),
				valTpDato($idDocumento,"int"));				
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	$row = mysql_fetch_assoc($rs);
	
	if(mysql_num_rows($rs) == 0){//sino tiene retencion, permitir agregar
		$objResponse->script("byId('selRetencionISLR').disabled = false;
							$('tdTextoRetencionISLR').style.display = 'none';
							$('tdMontoRetencionISLR').style.display = 'none';
							calcularRetencion();");							
	}else{
		$descripcionRetencion = "El documento ya posee retenci&oacute;n: <br><b>".utf8_encode($row['descripcion'])."</b>";
		$objResponse->assign("selRetencionISLR","value","1");
		$objResponse->script("byId('selRetencionISLR').disabled = true;
							xajax_asignarDetallesRetencion(1);");//lo coloca en 0 si ya habia monto
	}
	
	$objResponse->assign("tdInfoRetencionISLR","innerHTML",$descripcionRetencion);
		
	return $objResponse;
}

function buscarCliente($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBusqProveedor']);
	
	$objResponse->loadCommands(listarProveedores(0, "", "", $valBusq));
		
	return $objResponse;
}
function encabezadoEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (!($idEmpresa > 0)) {
		$idEmpresa = 100;
	}
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);

	if ($row['id_empresa'] != "") {
		$html .= "<table class=\"textoNegrita_7px\">";
		$html .= "<tr align=\"center\">";
			$html .= "<td>";
				$html .= "<img src=\"../".htmlentities($row['logo_familia'])."\" width=\"100\"/>";
			$html .= "</td>";
			$html .= "<td>";
				$html .= "<table width=\"250\">";
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= utf8_encode($row['nombre_empresa']);
					$html .= "</td>";
				$html .= "</tr>";
			if (strlen($row['rif']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>RIF: ";
						$html .= $row['rif'];
					$html .= "</td>";
				$html .= "</tr>";
			}
			if (strlen($row['direccion']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= utf8_encode($row['direccion']);
					$html .= "</td>";
				$html .= "</tr>";
			}
			if (strlen($row['web']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= htmlentities($row['web']);
					$html .= "</td>";
				$html .= "</tr>";
			}
				$html .= "</table>";
			$html .= "</td>";
		$html .= "</tr>";
		$html .= "</table>";

		$objResponse->assign("tdEncabezadoImprimir","innerHTML",$html);
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarBanco");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesRetencion");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarFactura");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarDocumento");
$xajax->register(XAJAX_FUNCTION,"cargarPropuesta");
$xajax->register(XAJAX_FUNCTION,"cargaSaldoCuenta");
$xajax->register(XAJAX_FUNCTION,"comboCuentas");
$xajax->register(XAJAX_FUNCTION,"comboRetencionISLR");
$xajax->register(XAJAX_FUNCTION,"eliminarFactura");
$xajax->register(XAJAX_FUNCTION,"encabezadoEmpresa");
$xajax->register(XAJAX_FUNCTION,"facturaSeleccionada");
$xajax->register(XAJAX_FUNCTION,"guardarPropuesta");
$xajax->register(XAJAX_FUNCTION,"listaFacturas");
$xajax->register(XAJAX_FUNCTION,"listaNotaCargo");
$xajax->register(XAJAX_FUNCTION,"listarProveedores");
$xajax->register(XAJAX_FUNCTION,"listBanco");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"verificarClave");
$xajax->register(XAJAX_FUNCTION,"verificarRetencionISLR");

function estadoFactura($estatus){

	if($estatus == 0){
		$respuesta .= " <img src=\"../img/iconos/ico_rojo.gif\">";
	}else{
		$respuesta .= " <img src=\"../img/iconos/ico_amarillo.gif\">";
	}
	
	return $respuesta;
}

function proveedor($id_proveedor){
	$queryProveedor = sprintf("SELECT nombre FROM cp_proveedor WHERE id_proveedor = %s",$id_proveedor);
	$rsProveedor = mysql_query($queryProveedor);
	
	if (!$rsProveedor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$rowProveedor = mysql_fetch_array($rsProveedor);
	
	return utf8_encode($rowProveedor['nombre']);
}

function empresa($id){
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = utf8_encode($row['nombre_empresa']);
	
	return $respuesta;
}

function errorGuardarDcto($objResponse){
    $objResponse->script("desbloquearGuardado();");
}

?>