<?php 

//XAJAX FUNCIONES

function actualizarAdicionales($frmAdicionales) {
	
	$objResponse = new xajaxResponse();
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmAdicionales['cbxAdicional'];
	
	if (isset($arrayObj)) {
		$i = 0;
		foreach ($arrayObj as $indice => $valor) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			$objResponse->assign("trItmAdicional".$valor,"className",$clase);
		}
	}
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa, $estatusCliente = "Activo", $condicionPago = "", $idClaveMovimiento = "", $asigDescuento = "true", $cerrarVentana = "true", $bloquearForm = "false") {
	$objResponse = new xajaxResponse();
	
	//reseteando el div compartido entre los modales
	$objResponse->assign("divLista","innerHTML","");
	///////////////////////////////////////////////////
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente.id = %s",
			valTpDato($idCliente, "int"));

	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
	}

	if ($estatusCliente != "-1" && $estatusCliente != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.status = %s",
				valTpDato($estatusCliente, "text"));
	}

	$queryCliente = sprintf("SELECT
		cliente_emp.id_cliente_empresa,
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		nit AS nit_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.status
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsCliente = mysql_num_rows($rsCliente);
	$rowCliente = mysql_fetch_assoc($rsCliente);


	if ($rowCliente['id'] > 0) {
		$tdMsjCliente = ($rowCliente['paga_impuesto'] == 0) ? "<div class=\"divMsjInfo\" style=\"padding:2px;\">Cliente exento y/o exonerado</div>" : "";
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjError\" style=\"padding:2px;\">El cliente se encuentra inactivo</div>" : "";
	} else if ($idCliente > 0 && in_array($cerrarVentana, array("1", "true"))) {
		$tdMsjCliente .= (!in_array($rowCliente['status'], array("Activo","1"))) ? "<div class=\"divMsjAlerta\" style=\"padding:2px;\">El cliente no se encuentra asociado a la empresa</div>" : "";
	}

	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	$objResponse->assign("txtDireccionCliente","value",elimCaracter(utf8_encode($rowCliente['direccion']),";"));
	$objResponse->assign("txtTelefonoCliente","value",$rowCliente['telf']);
	$objResponse->assign("txtOtroTelefonoCliente","value",$rowCliente['otrotelf']);
	$objResponse->assign("txtNITCliente","value",$rowCliente['nit_cliente']);
	$objResponse->assign("txtRifCliente","value",$rowCliente['ci_cliente']);

	//HABILITANDO BOTONES DE LA LISTA DE DOCUMENTOS

	$objResponse->script("byId('trBotonesLista').style.display = '';");
	
	 ////////////////////
	 
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}

	return $objResponse;
}

function asignarFactura($idFactura,$frmDcto,$montoFactura,$contFila = null){
	
	$objResponse = new xajaxResponse();
	
	($contFila == null) ? $contFila = count($frmDcto['cbx']) : '';
	
	if($frmDcto != ''){
		foreach ($frmDcto['cbx'] as $valor ){
			if($idFactura == $valor){
				$existeId = true;
			}
		}
	}
	
	if($existeId){
		$objResponse->alert("La factura que selecciono ya ha sido ingresada.");	
		return $objResponse;
	}
	
	if($montoFactura == '' || $montoFactura == 0 || $montoFactura == null){
		$objResponse->alert("La factura debe tener un monto.");
		return $objResponse;
	}
	
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		
	// BUSCA LOS DATOS DE LA FACTURA
		$queryFactura = sprintf("SELECT 
									cxc_fact.idFactura,
									cxc_fact.fechaRegistroFactura,
									cxc_fact.observacionFactura,
									cxc_fact.montoTotalFactura,
									cxc_fact.saldoFactura
								FROM cj_cc_encabezadofactura cxc_fact
								WHERE idFactura = %s",
				valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowFactura = mysql_fetch_assoc($rsFactura);

		$montoFacturaSuma = str_replace(",", "", $montoFactura);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
	"<tr id=\"trItm%s\" align=\"center\" class=\"textoGris_11px %s\" title=\"trItm%s\">".
		"<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
		"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
		"<td>%s<input type=\"hidden\" id=\"hddIdDocumento%s\" name=\"hddIdDocumento%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"<td>%s<input type=\"hidden\" id=\"hddIdTipoDocumento%s\" name=\"hddIdTipoDocumento%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"<td align=\"left\">".
			"<table>".
				"<tr>".
					"<td align=\"left\">".
						"%s<input type=\"hidden\" id=\"hddIdDescripcionDocumento%s\" name=\"hddIdDescripcionDocumento%s\" readonly=\"readonly\" value=\"%s\"/>".
					"</td>".
				"</tr>".
				"<tr>".
					"<td class=\"tituloColumna\" align=\"left\">".
						"%s<input type=\"hidden\" id=\"hddIdObservacionDocumento%s\" name=\"hddIdObservacionDocumento%s\" readonly=\"readonly\" value=\"%s\"/>".
					"</td>".
				"</tr>".
			"</table>".
		"</td>".
		"<td>%s<input type=\"hidden\" id=\"hddIdFechaDocumento%s\" name=\"hddIdFechaDocumento%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"<td align=\"right\">%s<input type=\"hidden\" id=\"hddIdSaldoFactura%s\" name=\"hddIdSaldoFactura%s\" readonly=\"readonly\" value=\"%s\"/></td>".
	"</tr>');",
		$idFactura, $clase, $contFila,
			$idFactura,
				$idFactura,
			utf8_encode($rowFactura['idFactura']),$idFactura,$idFactura,utf8_encode($rowFactura['idFactura']),
			"FACTURA",$idFactura,$idFactura,"FACTURA",
			"Factura N° ".$idFactura." del cliente ",$idFactura,$idFactura,"Factura N° ".$idFactura." del cliente ",
			utf8_encode($rowFactura['observacionFactura']),$idFactura,$idFactura,utf8_encode($rowFactura['observacionFactura']),
			utf8_encode($rowFactura['fechaRegistroFactura']),$idFactura,$idFactura,utf8_encode($rowFactura['fechaRegistroFactura']),
			$montoFactura,$idFactura, $idFactura, $montoFacturaSuma);


	
	$objResponse->script(($htmlItmPie));
	
	$objResponse->script("xajax_calcularMonto(xajax.getFormValues('frmFacturas'));
						byId('trfrmFinanciamientoDetalle').style.visibility = 'visible';
						byId('aListarCliente').style.display = 'none';
						byId('aListarEmpresa').style.display = 'none';
						byId('txtIdEmpresa').className = '';
						byId('txtIdEmpresa').readOnly = true;
						byId('txtIdCliente').className = '';
						byId('txtIdCliente').readOnly = true;");
	
	return $objResponse;
}


function asignarMonto($frmMonto,$frmDcto,$contFila = null){
	
	
	$objResponse = new xajaxResponse();
	
	$idhddMonto = $frmMonto['hddIdLstMonto'] ;

	($contFila == null) ? $contFila = count($frmDcto['cbx']) : '';
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	
	$txtMonto = utf8_encode($frmMonto['txtMonto']);
	$txtMontoSuma = str_replace(",", "",$frmMonto['txtMonto']);
	$frmMonto['fecha'] != null ? $fecha = $frmMonto['fecha'] : $fecha = date("Y-m-d");
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPie').before('".
			"<tr id=\"trItm%s\" align=\"center\" class=\"textoGris_11px %s\" title=\"trItm%s\">".
			"<td><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
			"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s<input type=\"hidden\" id=\"hddIdDocumento%s\" name=\"hddIdDocumento%s\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td>%s<input type=\"hidden\" id=\"hddIdTipoDocumento%s\" name=\"hddIdTipoDocumento%s\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td align=\"left\">".
				"<table>".
					"<tr>".
						"<td align=\"left\">".
							"%s<input type=\"hidden\" id=\"hddIdDescripcionDocumento%s\" name=\"hddIdDescripcionDocumento%s\" readonly=\"readonly\" value=\"%s\"/>".
						"</td>".
					"</tr>".
					"<tr>".
						"<td class=\"tituloColumna\" align=\"left\">".
							"%s<input type=\"hidden\" id=\"hddIdObservacionDocumento%s\" name=\"hddIdObservacionDocumento%s\" readonly=\"readonly\" value=\"%s\"/>".
						"</td>".
					"</tr>".
				"</table>".
			"</td>".
			"<td>%s<input type=\"hidden\" id=\"hddIdFechaDocumento%s\" name=\"hddIdFechaDocumento%s\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td align=\"right\">%s<input type=\"hidden\" id=\"hddIdSaldoFactura%s\" name=\"hddIdSaldoFactura%s\" readonly=\"readonly\" value=\"%s\"/></td>".
			"</tr>');",
			$idhddMonto, $clase, $idhddMonto,
			$idhddMonto,
			$idhddMonto,
			"-",$idhddMonto,$idhddMonto,"-",
			"MONTO",$idhddMonto,$idhddMonto,"MONTO",
			utf8_encode($frmMonto['txtDescripcionMonto']),$idhddMonto,$idhddMonto,utf8_encode($frmMonto['txtDescripcionMonto']),
			utf8_encode($frmMonto['txtObservacionMonto']),$idhddMonto,$idhddMonto,utf8_encode($frmMonto['txtObservacionMonto']),
			$fecha,$idhddMonto,$idhddMonto,$fecha,
			$txtMonto,$idhddMonto, $idhddMonto, $txtMontoSuma);
	
	$objResponse->script(($htmlItmPie));
	
	$objResponse->script("byId('btnCancelarLista').click(); byId('btnFrmPrestamo1').style.display = 'none';
						xajax_calcularMonto(xajax.getFormValues('frmFacturas'));
						byId('aListarCliente').style.display = 'none';
						byId('aListarEmpresa').style.display = 'none';
						byId('txtIdEmpresa').className = '';
						byId('txtIdEmpresa').readOnly = true;
						byId('txtIdCliente').className = '';
						byId('txtIdCliente').readOnly = true;");
	
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

function buscarDocumento($frmBuscar,$frmBuscarDocumento){
	$objResponse = new xajaxResponse();

		$valBusq = sprintf("%s|%s|%s|%s|%s",
				$frmBuscar['txtIdCliente'],
				$frmBuscar['txtIdEmpresa'],
				$frmBuscarDocumento['lstModulo'],
				$frmBuscarDocumento['buscarFact'],
				$frmBuscarDocumento['txtCriterioBusq']);
	
	$objResponse->loadCommands(listaFacturas(0, "id", "DESC", $valBusq));
		
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

function calcularTipoAdicional ($montoAdicional, $tipoAdicional, $frmFinanciamiento) {
	
	$objResponse = new xajaxResponse();
	
	switch ($tipoAdicional){
		case 'Total':
			$montoCuota = $montoAdicional/$frmFinanciamiento['txtNumeroPagos'];
		break;
		case 'Cuota':
			$montoCuota = $montoAdicional;
		break;
	}
	
	return array("0" => $objResponse, "cuotaAdicional" => $montoCuota);
}


function calcularMonto($frmCalcular){
	$objResponse = new xajaxResponse();
	
	
		foreach($frmCalcular['cbxItm'] as $indiceItm => $valorItm) {
			$montoResta += $frmCalcular['hddIdSaldoFactura'.$valorItm];
		}
		
		foreach($frmCalcular['cbx'] as $indiceItm => $valorItm) {
			$montoTotal += $frmCalcular['hddIdSaldoFactura'.$valorItm];
		}
		
		$montoFinal = number_format($montoTotal - $montoResta, 2, ".", ",");
		
		
		$objResponse->script("$('#tdTotalFinanciamiento').text('$montoFinal');");
		$objResponse->script("$('#txtMontoPagoDocumentos').val('$montoFinal');");
		if ($montoFinal <= 0) {
			$objResponse->script("byId('trfrmFinanciamientoDetalle').style.visibility = 'hidden';");
		}else{
			$objResponse->script("byId('trfrmFinanciamientoDetalle').style.visibility = 'visible';");
		}
		
	return $objResponse;
	
}

function calcularTipoInteresEfectivo($frmFinDetalle,$tipoInteres,$fechaInicial){
	$objResponse = new xajaxResponse();
	
	//SWITCHES CONDICIONALES H1 PARA EL TIPO DE INTERES Y H2 PARA LA FECHA INICIAL DEL FINANCIAMIENTO
	$h2 = false;
	
	//VALIDANDO CORRESPONDIENCIA ENTRE LOS TIPOS DE PLAZO
	
		$idDuracion = $frmFinDetalle['selectDuracion'];
		$idFrecuencia = $frmFinDetalle['selectFrecuencia'];
		$idPlazoInteres = $frmFinDetalle['selectPlazoInteres'];
		
	if($idFrecuencia > 0 && $idDuracion  > 0 && $frmFinDetalle['selectInteres'] != -1){   
		
		if($idDuracion != $idFrecuencia){
			$queryDuracion = sprintf("SELECT * FROM fi_plazos WHERE id_plazo IN (%s,%s);",
					$idFrecuencia,$idDuracion);
		
			$rsDuracion = mysql_query($queryDuracion);
			if (!$rsDuracion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
		
			while ($row =  mysql_fetch_assoc($rsDuracion)){
				($idDuracion == $row['id_plazo']) ? $rowPlazos['duracion'] = $row : $rowPlazos['frecuencia'] = $row;
			}
			if($rowPlazos['frecuencia']['semanas'] > ($rowPlazos['duracion']['semanas']) && $frmFinDetalle['txtCuotasFinanciar'] == ''){
				$objResponse->alert("El periodo de Frecuencia debe ser menor al periodo de Duracion.");
				$objResponse->loadCommands(cargaLstPlazos('','selectDuracion','selectFrecuencia','selectPlazoInteres'));
				return $objResponse;
			}
			
			if($rowPlazos['frecuencia']['semanas'] < ($rowPlazos['duracion']['semanas']) && $frmFinDetalle['txtCuotasFinanciar'] != '' && $frmFinDetalle['txtCuotasFinanciar'] != 0){
				$semanasDuracion = ($rowPlazos['duracion']['semanas'] * $frmFinDetalle['txtCuotasFinanciar']);
				
				$semanasDuracion = ($rowPlazos['duracion']['semanas'] * $frmFinDetalle['txtCuotasFinanciar']);
				$nombreDuracion = $rowPlazos['duracion']['nombre_plazo'];
				$montoDuracion = $frmFinDetalle['txtCuotasFinanciar'];
				$nombreFrecuencia = $rowPlazos['frecuencia']['nombre_plazo'];
				
				if( ($semanasDuracion % $rowPlazos['frecuencia']['semanas']) != 0){
					$objResponse->alert("No se puede financiar un monto me $montoDuracion $nombreDuracion en pagos de  $nombreFrecuencia");
					$objResponse->script("byId('txtCuotasFinanciar').value = '';");
					$objResponse->script("byId('txtNumeroPagos').value = '';");
					$objResponse->script("byId('txtInteresFinanciarEfectivo').value = '';");
					$objResponse->loadCommands(cargaLstPlazos('','selectDuracion','selectFrecuencia','selectPlazoInteres'));
					return $objResponse;
				}
			}
			
			if($frmFinDetalle['txtCuotasFinanciar'] != '' && $frmFinDetalle['txtCuotasFinanciar'] != 0){
				
				$semanasDuracion = ($rowPlazos['duracion']['semanas'] * $frmFinDetalle['txtCuotasFinanciar']);
				$nombreDuracion = $rowPlazos['duracion']['nombre_plazo'];
				$montoDuracion = $frmFinDetalle['txtCuotasFinanciar'];
				$nombreFrecuencia = $rowPlazos['frecuencia']['nombre_plazo'];
				
				if( ($semanasDuracion % $rowPlazos['frecuencia']['semanas']) != 0){
					$objResponse->alert("No se puede financiar monto de $montoDuracion $nombreDuracion en pagos de  $nombreFrecuencia");
					$objResponse->script("byId('txtCuotasFinanciar').value = '';");
					$objResponse->script("byId('txtNumeroPagos').value = '';");
					$objResponse->script("byId('txtInteresFinanciarEfectivo').value = '';");
					$objResponse->loadCommands(cargaLstPlazos('','selectDuracion','selectFrecuencia','selectPlazoInteres'));
					return $objResponse;
				}
			}
			
		//CALCULANDO EL *NUMERO DE PAGOS:
		$plazoFinanciarAnual = $rowPlazos['frecuencia']['cuotas_anuales']; // 
		$numeroPagos = $frmFinDetalle['txtCuotasFinanciar']*$rowPlazos['duracion']['semanas']/$rowPlazos['frecuencia']['semanas'];
		
		}else{
			$queryFrecuencia = sprintf("SELECT * FROM fi_plazos WHERE id_plazo = %s;",
			$idDuracion);
		
			$rsFrecuencia = mysql_query($queryFrecuencia);
			if (!$rsFrecuencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
			$rowPlazos =  mysql_fetch_assoc($rsFrecuencia);
			
		//CALCULANDO EL *NUMERO DE PAGOS:
		$plazoFinanciarAnual = $rowPlazos['cuotas_anuales']; // 
		$numeroPagos = $frmFinDetalle['txtCuotasFinanciar'];
		
		}

		//CALCULANDO DEPENDIENDO DE LA TASA DE INTERES
		
		$interesFinanciar = $frmFinDetalle['selectInteres']/100;
		
		switch ($tipoInteres){
			
			case 1:				//TIPO DE INTERES SIMPLE
				
				//CALCULO DEPENDIENDO DEL TIPO DE INTERES
				$interesAnualFinanciar = calcularInteresAnual($interesFinanciar,$idFrecuencia,'simple'); // $idFrecuencia para interes Simple
				
				$objResponse->script("byId('txtInteresFinanciarEfectivo').value = '$interesAnualFinanciar';");
				
				//ASIGNANDO EL NUMERO DE PAGOS
				$objResponse->script("byId('txtNumeroPagos').value = '$numeroPagos';");
				$h1 = true;
			break;
			
			case 2:	//TIPO DE INTERES COMPUESTO
				
				//CALCULO DEPENDIENDO DEL TIPO DE INTERES
				$interesAnualFinanciar = calcularInteresAnual($interesFinanciar,$idPlazoInteres,'compuesto'); //$idPlazoInteres para intereses compuestos
				$montoInteresEfectivo =  floatval(pow(1+$interesAnualFinanciar, (1/$plazoFinanciarAnual)))-1;
				
				$objResponse->script("byId('txtInteresFinanciarEfectivo').value = '$montoInteresEfectivo';");
				
				//ASIGNANDO EL NUMERO DE PAGOS
				$objResponse->script("byId('txtNumeroPagos').value = '$numeroPagos';");
				$objResponse->script("validarCampo('selectTipoInteres','t','lista');");
				$h1 = true;
			break;
			default:
				$objResponse->script("validarCampo('selectTipoInteres','t','lista');");
				$objResponse->script("byId('txtCuotasFinanciar').value = '';");
				$objResponse->script("byId('txtNumeroPagos').value = '';");
				$objResponse->script("byId('txtInteresFinanciarEfectivo').value = '';");
				$objResponse->script("byId('selectInteres').value = '';");
				$objResponse->loadCommands(cargaLstPlazos('','selectDuracion','selectFrecuencia','selectPlazoInteres'));
				$objResponse->script("byId('trBotonesGenerar').style.display = none;");
			break;
		}
		
		if($fechaInicial == null || $fechaInicial == ''){
			$objResponse->script("validarCampo('txtFechaInicial','t','fecha');");
			$objResponse->script("byId('txtCuotasFinanciar').value = '';");
			$objResponse->script("byId('txtNumeroPagos').value = '';");
			$objResponse->script("byId('txtInteresFinanciarEfectivo').value = '';");
			$objResponse->script("byId('selectInteres').value = '-1';");
			$objResponse->loadCommands(cargaLstPlazos('','selectDuracion','selectFrecuencia','selectPlazoInteres'));
			$objResponse->script("byId('trBotonesGenerar').style.display = none;");
		}else{
			$objResponse->script("validarCampo('txtFechaInicial','t','fecha');");
			$h2 = true;
		}
		
		//MANDANDO A ACTUALIZAR EL BOTON DE GENERAR CUADRO DE AMORTIZACIONES
		
		if($h1 == true && $h2 == true && $frmFinDetalle['txtMontoPagoDocumentos'] != ''){
			$objResponse->script("validarCampo('txtMontoPagoDocumentos','t','');");
			$objResponse->script("byId('trBotonesGenerar').style.display = '';");
		}else{
			$objResponse->script("validarCampo('txtMontoPagoDocumentos','t','');");
		}
		
	}
		return $objResponse;
}
	//////////////////////////////////////////////////
	
function cargarCampos($idPedido = '') {
	
	$objResponse = new xajaxResponse();
	
	if($idPedido == ''){
		return $objResponse;
	}
	
	//HTML INICIAL DE EDICION
	
	$objResponse->script("byId('txtIdEmpresa').className = '';");
	$objResponse->script("byId('txtIdEmpresa').readOnly = 'readonly';");
	$objResponse->script("byId('txtIdCliente').className = '';");
	$objResponse->script("byId('txtIdCliente').readOnly = 'readonly';");
	$objResponse->script("byId('aListarEmpresa').style.visibility = 'hidden';");
	
	
	
	$queryPedido = sprintf("SELECT *
									FROM fi_pedido pedido
									WHERE pedido.id_pedido_financiamiento = %s;",
							$idPedido);
	
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsPedido);
	$rowPedido = mysql_fetch_assoc($rsPedido);
	
	$objResponse->script("byId('trNroPedido').style.display = '';");
	$objResponse->assign("txtPedido","value",$rowPedido['numeracion_pedido']);
	$idEmpresa= $rowPedido['id_empresa'];
	$idCliente = $rowPedido['id_cliente'];
	$idUsuario = $rowPedido['id_empleado'];
	
	
	//Asignando los valores del formulario a editar
	
	$objResponse->script("xajax_asignarEmpresaUsuario('$idEmpresa', 'Empresa', 'ListaEmpresa', '', '');");
	$objResponse->script("xajax_asignarCliente('$idCliente', '$idEmpresa');");
	$objResponse->loadCommands(cargarUsuario($idUsuario));
	
	$objResponse->loadCommands(cargaLstTipoInteres('','selectTipoInteres'));
	$objResponse->loadCommands(cargaLstInteresMora('','selectInteresMora'));
	$objResponse->assign("selectTipoInteres","value",$rowPedido['tipo_interes']);
	$objResponse->assign("selectInteresMora","value",$rowPedido['id_interes_mora']);
	$objResponse->assign("txtFechaPedido","value",date(spanDateFormat,strtotime($rowPedido['fecha_pedido'])));
	$objResponse->assign("txtFechaInicial","value",date(spanDateFormat,strtotime($rowPedido['fecha_financiamiento'])));
	
	$queryDocumentos = sprintf("SELECT *
									FROM fi_documento doc
									WHERE doc.id_pedido_financiamiento = %s;",
			valTpDato($idPedido, "int"));
	
	$rsDocumento = mysql_query($queryDocumentos);
	if (!$rsDocumento) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsDocumento);
	while($rowDoc = mysql_fetch_assoc($rsDocumento)){
		$idDocumento = $rowDoc['id_documento_tabla'];
		$montoFactura = number_format($rowDoc['saldo_documento'],2,".",",");
		if($rowDoc['id_documento_tabla'] == 0){
			$objResponse->loadCommands(asignarMonto(
													array("hddIdLstMonto" => "m0nt0",
														  "txtMonto" => $montoFactura,
														  "txtDescripcionMonto" => $rowDoc['descripcion_documento'],
														  "txtObservacionMonto" => $rowDoc['observacion_documento'],
														  "fecha" => $rowDoc['fecha_documento']),
													'',
													$totalRows));
		}else{
			$objResponse->loadCommands(asignarFactura($idDocumento,'',"$montoFactura",$totalRows));
		}
		$totalRows--;
	}
	
	$objResponse->loadCommands(cargaLstInteres('','selectInteres'));
	$objResponse->assign("selectInteres", "value", $rowPedido['interes_financiamiento']);			
	$objResponse->loadCommands(cargaLstPlazos('','selectDuracion','selectFrecuencia','selectPlazoInteres'));
	$objResponse->assign("selectFrecuencia", "value", $rowPedido['id_frecuencia_plazo']);			
	$objResponse->assign("selectDuracion", "value", $rowPedido['id_duracion_plazo']);		
	$objResponse->assign("txtCuotasFinanciar", "value", $rowPedido['cuotas_duracion']);		
	$objResponse->assign("txtMontoPagoDocumentos", "value", $rowPedido['monto_financiamiento_documentos']);		
	$objResponse->assign("txtInteresFinanciarEfectivo", "value", $rowPedido['interes_efectivo']);		
	$objResponse->assign("txtNumeroPagos", "value", $rowPedido['numero_pagos']);		
	
	
	//CARGANDO LISTA DE ADICIONALES DISPONIBLES HABILITADOS
	
	$objResponse->loadCommands(cargaLstAdicionales('','selectAdicionales'));
	
	//INSERTANDO LOS ADICIONALES
	
	
	$queryAdicionales = sprintf("SELECT *
									FROM fi_financiamiento_adicionales adi
									WHERE adi.id_pedido_financiamiento = %s;",
			valTpDato($idPedido, "int"));
	
	$rsAdicionales = mysql_query($queryAdicionales);
	if (!$rsAdicionales) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsAdicionales);
	
	
	if($totalRows > 0){	
		while($row = mysql_fetch_assoc($rsAdicionales)){
			$contadorAdicionales++;
			$idAdicional[] = $row['id_adicional'];
			$datosAdicionales[] = array('txtMontoAdicional' => $row['monto_adicional'],'tipoAdicional' => $row['tipo_adicional']);
			$objResponse->loadCommands(insertarAdicional(
					array('selectAdicionales' => $row['id_adicional'],
						  'id_pedido' => $idPedido),
					$contadorAdicionales));
		}
		
		//CARGANDO ADICIONALES 

		$arrayGenerarCuadro = array(
				'txtMontoPagoDocumentos' => $rowPedido['monto_financiamiento_documentos'],
				'txtInteresFinanciarEfectivo' => $rowPedido['interes_efectivo'],
				'cbxAdicional' => $idAdicional, //SI HAY ADICIONALES 
				'datosAdicionales' => $datosAdicionales,
				'txtNumeroPagos' => $rowPedido['numero_pagos'],
				'selectFrecuencia' => $rowPedido['id_frecuencia_plazo']);
	} else{
		$arrayGenerarCuadro = array(
				'txtMontoPagoDocumentos' => $rowPedido['monto_financiamiento_documentos'],
				'txtInteresFinanciarEfectivo' => $rowPedido['interes_efectivo'],
				'txtNumeroPagos' => $rowPedido['numero_pagos'],
				'selectFrecuencia' => $rowPedido['id_frecuencia_plazo']);
	}
	
	
	$objResponse->script("byId('trBotonesGenerar').style.display = '';");
	
	
	$objResponse->loadCommands(generarCuadro(array('txtFechaInicial' => date(spanDateFormat,strtotime($rowPedido['fecha_financiamiento']))),
			$arrayGenerarCuadro,
		$rowPedido['tipo_interes']));
	
	return $objResponse;
	
}	

function cargaLstAdicionales ($selId = '',$idEtiquetaAdicionales) {

	$objResponse = new xajaxResponse();

	//QUERY DE LOS ADCIONALES

	$queryAdicional = "SELECT * FROM fi_adicionales WHERE estatus_adicional = 1;";
	$rsAdicional = mysql_query($queryAdicional);
	if (!$rsAdicional) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsAdicional);
	$html = "<select id=\"".$idEtiquetaAdicionales."\" name=\"".$idEtiquetaAdicionales."\"  class=\"inputHabilitado\" style=\"width:99%\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowAdicional= mysql_fetch_assoc($rsAdicional)) {
		$selected = ($selId == $rowAdicional['id_adicional'] || $totalRows == 1) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$rowAdicional['id_adicional']."\">".utf8_encode($rowAdicional['nombre_adicional'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdAdicionalFinanciar","innerHTML",$html);

	return $objResponse;
}

function cargaLstModulo($selId = "", $accion = "onchange=\"byId('btnBuscar').click();\"" , $idEtiqueta = "lstModulo", $idObjetivo = "tdlstModulo") {
	$objResponse = new xajaxResponse();

//	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo NOT IN (5);");
	$query = sprintf("SELECT * FROM pg_modulos WHERE id_modulo = 2;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$idEtiqueta."\" name=\"".$idEtiqueta."\" ".$accion." class=\"inputHabilitado\" style=\"width:99%\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($idObjetivo,"innerHTML",$html);

	return $objResponse;
}


function cargaLstInteres ($selId = '',$idEtiquetaInteres) {

	$objResponse = new xajaxResponse();

	//Query de los plazos

	$queryInteres = "SELECT * FROM fi_interes WHERE estatus_interes = 1;";
	$rsInteres = mysql_query($queryInteres);
	if (!$rsInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsInteres);
	$html = "<select id=\"".$idEtiquetaInteres."\" name=\"".$idEtiquetaInteres."\"  class=\"inputHabilitado\" style=\"width:99%\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowInteres= mysql_fetch_assoc($rsInteres)) {
		$selected = ($selId == $rowInteres['id_interes']) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$rowInteres['valor_interes']."\">".utf8_encode($rowInteres['descripcion_interes'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdInteresFinanciar","innerHTML",$html);

	return $objResponse;
}

function cargaLstInteresMora ($selId = '',$idEtiquetaInteresMora) {

	$objResponse = new xajaxResponse();

	//Query de los plazos

	$queryInteresMora = "SELECT * FROM fi_intereses_mora WHERE estatus_interes_mora = 1;";
	$rsInteresMora = mysql_query($queryInteresMora);
	if (!$rsInteresMora) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsInteresMora);
	$htmlHeader = "<select id=\"".$idEtiquetaInteresMora."\" name=\"".$idEtiquetaInteresMora."\"  class=\"inputHabilitado\" style=\"width:99%\">";
	$htmlHeader .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowInteresMora= mysql_fetch_assoc($rsInteresMora)) {
		$selected = ($selId == $rowInteresMora['id_interes_mora']) ? "selected=\"selected\"" : "";
		if($rowInteresMora['descripcion_interes_mora'] == 1){ //Cuotas Fijas			
			$html1 .= "<option ".$selected." value=\"".$rowInteresMora['id_interes_mora']."\">".cAbrevMoneda." ".utf8_encode($rowInteresMora['valor_interes_mora'])."</option>";
			$htmlOpGroup1 =   "<optgroup label=\"Monto Fijo\">";
		}else if ($rowInteresMora['descripcion_interes_mora'] == 2){//Porcentaje Fijo
			$html12 .= "<option ".$selected." value=\"".$rowInteresMora['id_interes_mora']."\">".utf8_encode($rowInteresMora['valor_interes_mora'])." %</option>";
			$htmlOpGroup2 =   "<optgroup label=\"Porcentaje Fijo\">";
		}	
	}
	$htmlEndOptGroup = "</optgroup>";
	$htmlFooter .= "</select>";

	$objResponse->assign("tdInteresMoraFinanciar","innerHTML",$htmlHeader.$htmlOpGroup1.$html1.$htmlEndOptGroup.$htmlOpGroup2.$html12.$htmlEndOptGroup.$htmlFooter);

	return $objResponse;
}


function cargaLstPlazos($selId,$idEtiquetaDuracion,$idEtiquetaFrecuencia,$idEtiquetaInteres) {
	
	$objResponse = new xajaxResponse();
	
	//Query de los plazos
	
	$queryDuracion = "SELECT * FROM fi_plazos WHERE estatus_duracion = 1;";
	$rsDuracion = mysql_query($queryDuracion);
	if (!$rsDuracion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsDuracion);
	$html = "<select id=\"".$idEtiquetaDuracion."\" name=\"".$idEtiquetaDuracion."\"  class=\"inputHabilitado\" style=\"width:99%\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowPlazo= mysql_fetch_assoc($rsDuracion)) {
		$selected = ($selId == $rowPlazo['id_plazo'] || $totalRows == 1) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$rowPlazo['id_plazo']."\">".utf8_encode($rowPlazo['nombre_plazo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstDuracionPago","innerHTML",$html);

	$queryFrecuencia = "SELECT * FROM fi_plazos WHERE estatus_frecuencia = 1;";
	
	$rsFrecuencia = mysql_query($queryFrecuencia);
	if (!$rsFrecuencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsFrecuencia);
	$html2 = "<select id=\"".$idEtiquetaFrecuencia."\" name=\"".$idEtiquetaFrecuencia."\"  class=\"inputHabilitado\" style=\"width:99%\">";
	$html2 .= "<option value=\"-1\">[ Seleccione ]</option>";
	
	while ($rowFrecuencia = mysql_fetch_assoc($rsFrecuencia)) {
		$selected = ($selId == $rowFrecuencia['id_plazo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		$html2 .= "<option ".$selected." value=\"".$rowFrecuencia['id_plazo']."\">".utf8_encode($rowFrecuencia['nombre_plazo'])."</option>";
	}
	$html2 .= "</select>";
	
	$objResponse->assign("tdlstFrecuenciaPago","innerHTML",$html2);
	
	$queryInteres = "SELECT * FROM fi_plazos WHERE estatus_interes = 1;";
	
	$rsInteres = mysql_query($queryInteres);
	if (!$rsInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsInteres);
	$html3 = "<select id=\"".$idEtiquetaInteres."\" name=\"".$idEtiquetaInteres."\"  class=\"inputHabilitado\" style=\"width:99%\">";
	$html3 .= "<option value=\"-1\">[ Seleccione ]</option>";
	
	while ($rowInteres = mysql_fetch_assoc($rsInteres)) {
		$selected = ($selId == $rowInteres['id_plazo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		$html3 .= "<option ".$selected." value=\"".$rowInteres['id_plazo']."\">".utf8_encode($rowInteres['nombre_plazo'])."</option>";
	}
	$html3 .= "</select>";
	
	$objResponse->assign("tdPlazoInteres","innerHTML",$html3);

	return $objResponse;
}


function cargaLstTipoInteres ($selId = '',$idEtiquetaTipoInteres) {

	$objResponse = new xajaxResponse();

	//Query de los plazos

	$queryTipoInteres = "SELECT * FROM fi_tipo_interes WHERE estatus_tipo_interes = 1;";
	$rsTipoInteres = mysql_query($queryTipoInteres);
	if (!$rsTipoInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rsTipoInteres);
	$html = "<select id=\"".$idEtiquetaTipoInteres."\" name=\"".$idEtiquetaTipoInteres."\"  class=\"inputHabilitado\" onchange=\"xajax_calcularTipoInteresEfectivo(xajax.getFormValues('frmFinanciamientoDetalle'),byId('selectTipoInteres').value,byId('txtFechaInicial').value);\" style=\"width:99%\">";
	$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowTipoInteres= mysql_fetch_assoc($rsTipoInteres)) {
		$selected = ($selId == $rowTipoInteres['id_tipo_interes'] || $totalRows == 1) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$rowTipoInteres['valor_tipo_interes']."\">".utf8_encode($rowTipoInteres['descripcion_tipo_interes'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoInteres","innerHTML",$html);

	return $objResponse;
}

function cargarUsuario($idNombreUsuario) {
	
	$objResponse= new xajaxResponse();
	
	if(is_numeric($idNombreUsuario)){
		$condicional = "empleado.id_empleado";
	}else{
		$condicional = "usuario.nombre_usuario";
	}
	
 	$queryUsuario = sprintf("SELECT 
 								empleado.id_empleado,			
					 			CONCAT_WS(' ',empleado.nombre_empleado,empleado.apellido) AS nombre_usuario
 							FROM pg_usuario usuario
 							INNER JOIN pg_empleado empleado ON (usuario.id_empleado = empleado.id_empleado)
 							WHERE %s = %s;",
 					$condicional,
 					valTpDato($idNombreUsuario, "text"));
 	
 	$rsUsuario = mysql_query($queryUsuario);
 	if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$rowUsuario = mysql_fetch_assoc($rsUsuario);
	
	$objResponse->assign("txtUsuario", "value", $rowUsuario['nombre_usuario']);
	$objResponse->assign("hddIdUsuario", "value", $rowUsuario['id_empleado']);
 	
 	
	return $objResponse;
	
	
}

function dibujarCuadroAmortizacion($frmFinDetalle){
	
	$objResponse = new xajaxResponse();
	
	$pagos = $frmFinDetalle['txtNumeroPagos'] ;
	
	for($i = 0; $i <= $pagos; $i++){
		$widthInteresCuota[] = floatval(calcularWidth(str_replace(",", "", $frmFinDetalle['interes'][$i]),str_replace(",", "", $frmFinDetalle['interes'][1])));
		$widthAmortizacionCuota[] = floatval(calcularWidth(str_replace(",", "", $frmFinDetalle['amortizacion'][$i]),str_replace(",", "", $frmFinDetalle['amortizacion'][$pagos])));
		$widthCuotaFinal[] = floatval(calcularWidth($frmFinDetalle['cuota'][$i],$frmFinDetalle['cuota'][$pagos]));
		$widthCapitalVivo[] = floatval(calcularWidth(str_replace(",", "", $frmFinDetalle['capitalVivo'][$i]),str_replace(",", "", $frmFinDetalle['capitalVivo'][0])));
	}
	
	for($i = 0; $i <= $pagos; $i++){
		
		$objResponse->script("byId('interes$i').style.backgroundSize = '$widthInteresCuota[$i]% 95%';");
		$objResponse->script("byId('amortizacion$i').style.backgroundSize = '$widthAmortizacionCuota[$i]% 95%';");
		$objResponse->script("byId('capital$i').style.backgroundSize = '$widthCapitalVivo[$i]% 95%';");
		
	}
	
	
	return $objResponse;
	
}



function eliminarAdicional($idItmAdicional) {
	$objResponse = new xajaxResponse();
	
	if (isset($idItmAdicional) && $idItmAdicional > 0) {
		$objResponse->script("
				fila = document.getElementById('trItmAdicional$idItmAdicional');
		padre = fila.parentNode;
		padre.removeChild(fila);");

		$objResponse->script("xajax_eliminarAdicional('');");
	}
	$objResponse->script("xajax_actualizarAdicionales(xajax.getFormValues('frmFinanciamientoDetalle'));");

	return $objResponse;
}


function eliminarAdicionalLote($frmListaAdicional) {
	$objResponse = new xajaxResponse();
	
	
	if (isset($frmListaAdicional['cbxItmAdicional'])) {
		foreach ($frmListaAdicional['cbxItmAdicional'] as $indiceItm => $valorItm) {
			$objResponse->script("
					fila = document.getElementById('trItmAdicional$valorItm');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarAdicionalLote('');");
		
	}

	$objResponse->script("xajax_actualizarAdicionales(xajax.getFormValues('frmFinanciamientoDetalle'));");
	
	return $objResponse;
}

function eliminarFactura($frmFactura) {
	$objResponse = new xajaxResponse();

	
	if (isset($frmFactura['cbxItm'])) {
		foreach($frmFactura['cbxItm'] as $indiceItm => $valorItm) {
			if($valorItm == 'm0nt0'){
				$objResponse->script("byId('btnFrmPrestamo1').style.display = 'block';");
			}
			$objResponse->script("
			fila = document.getElementById('trItm".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");

		}
		
	$objResponse->loadCommands(calcularMonto($frmFactura));
	} else {
		if(!isset($_GET['id']))
		$objResponse->script("byId('aListarCliente').style.visibility = 'visible';
						byId('aListarCliente').style.display = 'block';
						byId('aListarEmpresa').style.display = 'block';
						byId('txtIdEmpresa').className = 'inputHabilitado';
						byId('txtIdEmpresa').readOnly = false;
						byId('txtIdCliente').className = 'inputHabilitado';
						byId('txtIdCliente').readOnly = false;");
		return $objResponse;
	}
	
	$objResponse->script("xajax_eliminarFactura(xajax.getFormValues('frmFacturas'));");
	$objResponse->assign("cbxItm", "checked", false);
	
	return $objResponse;
}

function  generarCuadro($frmDatos,$frmFinanciamiento,$tipoInteres){
	
	$objResponse = new xajaxResponse();
	
	
	//VALIDANDO EL PLAZO DEL INTERES 
	if($frmFinanciamiento['selectPlazoInteres'] == -1){
		$objResponse->script("validarCampo('selectPlazoInteres','t','lista');");		
		return  $objResponse;
	}else{
		$objResponse->script("validarCampo('selectPlazoInteres','t','lista');");
	}
	
	
	$fechaInicial = $frmDatos['txtFechaInicial'];

	$dibujar['txtNumeroPagos'] = $numeroPagos = $frmFinanciamiento['txtNumeroPagos'];
	$interesEfectivo = $frmFinanciamiento['txtInteresFinanciarEfectivo'];
	$montoTotal = str_replace(",", "", $frmFinanciamiento['txtMontoPagoDocumentos']);
	$montoTotal1 = number_format($montoTotal,2,".",",");
	//GENERANDO CUADRO DEPENDIENDO DEL TIPO DE INTERES
	
	if($tipoInteres == 1){ // INTERES SIMPLE
		$tituloTabla = "Cuadro de Amortizacion de Interes Simple";
	}else if($tipoInteres == 2){ // INTERES COMPUESTO
		$tituloTabla = "Cuadro de Amortizacion de Interes Compuesto";
	}
	
	//VERIFICANDO SI HAY ADICIONALES AL FINANCIAMIENTO
	
	
	if(count($frmFinanciamiento['cbxAdicional']) > 0) {
		$widthAnterior = 2;
		$widthAdicional = 10;
	}
	
	//CALCULANDO VALORES EN LA TABLA DE LOS ADICIONALES DE ACUERDO A SU TIPO DE CREACION
	
	$acumCuotaAdicional = 0;//INICIALIZANDO EL VALOR DE LA CUOTA ACUMULADA DE ADICIONALES
	$contAdicional = 0; //INICIALIZANDO EL VALOR SI VIENE DE CARGAR CAMPOS
	
	foreach ($frmFinanciamiento['cbxAdicional'] as $indice => $valor){
		if(isset($frmFinanciamiento['datosAdicionales'])){	
			$montoAdicional = $frmFinanciamiento['datosAdicionales'][$contAdicional]['txtMontoAdicional'];
			$tipoAdicional = $frmFinanciamiento['datosAdicionales'][$contAdicional]['tipoAdicional'];
			$contAdicional++;
		}else{
			$montoAdicional = $frmFinanciamiento['txtMontoAdicional'.$valor];
			$tipoAdicional = $frmFinanciamiento['tipoAdicional'.$valor];
		}
		$montoAdicional = str_replace(",", "", $montoAdicional);
		$cuotaAdicional = calcularTipoAdicional($montoAdicional,$tipoAdicional,$frmFinanciamiento);
		$adicionales[] = round($cuotaAdicional['cuotaAdicional'],2);//SI SE DESGLOSARA EN EL CUADRO LOS ADICIONALES
		$acumCuotaAdicional += $cuotaAdicional['cuotaAdicional'];
	}
	
	//TOTAL DE ADICIONALES
		
	$totalAdicionales = $acumCuotaAdicional*$numeroPagos;
	$totalAdicionales1 = number_format($totalAdicionales,2,".",",");
	
	//CALCULANDO TERMINO AMORTIZATIVO
	$interesDecimal = ($montoTotal*$interesEfectivo)/$numeroPagos;
	$terAmortizativo = formatoNumero(($montoTotal*$interesEfectivo)/(1-pow(1+$interesEfectivo,(-$numeroPagos))));
	
	//CALCULO INTERES DE LA PRIMERA CUOTA
	
	for($i = 0; $i <= $numeroPagos; $i++){
		//CALCULO DE INTERESES POR CUOTA
		($i == 0) ? $capVivo[$i] = $montoTotal : $capVivo[$i] = formatoNumero($capVivo[$i-1] - $amortizacionCuota[$i-1]);
		$intCuota[$i] = formatoNumero($capVivo[$i] * $interesEfectivo);
		//CALCULO DE LA CUOTA DE AMORTIZACION
		$amortizacionCuota[$i] = formatoNumero($terAmortizativo - $intCuota[$i]);
		$adicionales[$i] = formatoNumero($acumCuotaAdicional);
	}
	
	for($i = 0; $i <= $numeroPagos; $i++){
		$terAmortizativo = number_format($terAmortizativo,2,".","");
		$capVivo[$i] = number_format($capVivo[$i],2,".","");
		$intCuota[$i] = number_format($intCuota[$i],2,".","");
		$amortizacionCuota[$i] = number_format($amortizacionCuota[$i],2,".","");
		//TOTAL DE INTERES
		$totalInteres += $intCuota[$i];
	}
	
	//TOTAL DE INTERES DE FINANCIAMIENTO
	
	$totalInteres1 = number_format($totalInteres, 2 , ".",",");
		
	//TOTAL DE CUOTAS A PAGAR
	$totalCuotas = $montoTotal + $totalInteres + $totalAdicionales;
	$totalCuotas1 = number_format($totalCuotas,2,".",",");	
	
	//TOTAL DEL TERMINO AMORTIZATIVO
	
	$terAmortizativo = $terAmortizativo + round($acumCuotaAdicional,2);
	
	$idFrecuencia = $frmFinanciamiento['selectFrecuencia'];
	
	$queryFrecuencia = sprintf("SELECT * FROM fi_plazos WHERE id_plazo = %s;",
			$idFrecuencia);
		
	$rsFrecuencia = mysql_query($queryFrecuencia);
	if (!$rsFrecuencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$rowPlazos =  mysql_fetch_assoc($rsFrecuencia);
	
	$cuota = $rowPlazos['nombre_plazo'];
	
		
		
          $htmlTh = "<td>";
                   $htmlTh .="<form id=\"frmCuadro\" name=\"frmCuadro\" >";
                	$htmlTh .="<table width=\"100%\">";
                    	$htmlTh .="<tr>";
                            $htmlTh .="<td id=\"tdCuotasAmortizaciones\">";
                                $htmlTh .="<table border=\"0\" class=\"tablaStripped\" cellpadding=\"2\" width=\"100%\">";
		                  		 $htmlTh .="<caption class=\"tituloPaginaFinanciamientos\" style=\"text-align : center;\">$tituloTabla</caption>";
                                    $htmlTh .="<tr class=\"tituloColumna\">";
                                        $htmlTh .="<td width='6%'>Periodo $cuota</td>";
                                        $htmlTh .="<td width='".(13-$widthAnterior)."%'>Cuota $cuota</td>";
                                        $htmlTh .="<td width='".(12-$widthAnterior)."%'>Fecha $cuota</td>";
										if(count($frmFinanciamiento['cbxAdicional']) > 0) {
                                        	$htmlTh .="<td width='".$widthAdicional."%'>Adicionales $cuota</td>";											
										}		
                                        $htmlTh .="<td width='".(19-$widthAnterior)."%'>Intereses $cuota</td>";
                                        $htmlTh .="<td width='".(25-$widthAnterior)."%'>Cuota Amortizacion</td>";
                                        $htmlTh .="<td width='".(25-$widthAnterior)."%'>Capital Vivo</td>";
                                    $htmlTh .="</tr>";
       
                                    
         for($i = 0; $i <= $numeroPagos; $i++){
			
	         	$pos = $i;
				
	         	$claseResaltar = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
	         	 
	         	
				$numeroSemanas = $rowPlazos['semanas'];
	         	
				$clase = 'tdCuadroCapital';
				
	         	if ($i == 0){
					$pos = '';
					$interesCuota = '';
					$amortCuota = '';
					$cuotaFinal = '';
					$fecha[$i] = '';
					$capitalVivo = $capVivo[$i];
					$adicionalesFin = '';
				}else{
					$fecha[$i] = ($i == 1) ? $fechaInicial : date("d-m-Y",strtotime("$numeroSemanas week", strtotime($fecha[$i-1])));
					$cuotaFinal = $terAmortizativo;
					$interesCuota = $intCuota[$i-1];
					$amortCuota = $amortizacionCuota[$i-1];
					if ($i == $numeroPagos){
						$capitalVivo = 0;
						$clase = '';
					}else{
						$capitalVivo = $capVivo[$i];
					}
					$adicionalesFin = $adicionales[$i];
				}
	         	
				$dibujar['cuota'][] = $cuotaFinal = number_format($cuotaFinal, 2 , ".",",");
				$dibujar['interes'][] = $interesCuota = number_format($interesCuota, 2 , ".",",");
				$dibujar['amortizacion'][] = $amortCuota = number_format($amortCuota, 2 , ".",",");
				$dibujar['capitalVivo'][] = $capitalVivo = number_format($capitalVivo, 2 , ".",",");
				$adicionalesFin = number_format($adicionalesFin, 2 , ".",",");
				
	               $htmlBody .="<tr class=\"$claseResaltar\">";
	                  $htmlBody .="<td width='6%' id='periodo$i'>$pos</td><input type=\"hidden\" id=\"periodo[]\" name =\"periodo[]\"value=\"$pos\">";
	                  $htmlBody .="<td width='".(13-$widthAnterior)."%' id='cuota$i'>$cuotaFinal</td><input type=\"hidden\" id=\"cuota[]\" name =\"cuota[]\"value=\"$cuotaFinal\">";
	                  $htmlBody .="<td width='".(12-$widthAnterior)."%' id='fecha$i'>$fecha[$i]</td><input type=\"hidden\" id=\"fecha[]\" name =\"fecha[]\"value=\"$fecha[$i]\">";
	                  if(count($frmFinanciamiento['cbxAdicional']) > 0) {
	                  	$htmlBody .="<td width='".$widthAdicional."%' id='adicional$i' class=\"\">$adicionalesFin</td><input type=\"hidden\" id=\"adicional[]\" name =\"adicional[]\"value=\"$adicionalesFin\">";
	                  }
	                  $htmlBody .="<td width='".(19-$widthAnterior)."%' id='interes$i' class=\"tdCuadroIntereses\">$interesCuota</td><input type=\"hidden\" id=\"interes[]\" name =\"interes[]\"value=\"$interesCuota\">";
	                  $htmlBody .="<td width='".(25-$widthAnterior)."%' id='amortizacion$i' class=\"tdCuadroAmortizaciones\">$amortCuota<input type=\"hidden\" id=\"amortizacion[]\" name =\"amortizacion[]\"value=\"$amortCuota\"></td>";
	                  $htmlBody .="<td width='".(25-$widthAnterior)."%' id='capital$i' class=\"$clase\">$capitalVivo</td><input type=\"hidden\" id=\"capitalVivo[]\" name =\"capitalVivo[]\"value=\"$capitalVivo\">";
                  $htmlBody .="</tr>";
          }
                
          $htmlFin .="</table>";
       $htmlFin .="</form>";
     $htmlFin .="</td>";
	
     
	$objResponse->assign("cuadroAmortizaciones", "innerHTML", $htmlTh.$htmlBody.$htmlFin);
     
		$htmlPie .="<td align=\"right\">";
			$htmlPie .="<table width=\"100%\">";
				$htmlPie .="<tr align=\"right\">";
					$htmlPie .="<td width=\"76%\"></td>";
					$htmlPie .="<td width=\"12%\" class=\"tituloCampo\"><b>Total Documentos: </b></td>";
					$htmlPie .="<td width=\"12%\" id=\"tdTotalIntereses\" ><b>$montoTotal1</b>";
						$htmlPie .="<input type=\"hidden\" id=\"totalInteresPagar\" name=\"totalInteresPagar\" value=\"$montoTotal\"/>";
					$htmlPie .="</td>";
				$htmlPie .="</tr>";	
				$htmlPie .="<tr align=\"right\">";
					$htmlPie .="<td width=\"76%\"></td>";
					$htmlPie .="<td width=\"12%\" class=\"tituloCampo\"><b>Total Intereses: </b></td>";
					$htmlPie .="<td width=\"12%\" id=\"tdTotalIntereses\" ><b>$totalInteres1</b>";
						$htmlPie .="<input type=\"hidden\" id=\"totalInteresPagar\" name=\"totalInteresPagar\" value=\"$totalInteres\"/>";
					$htmlPie .="</td>";
				$htmlPie .="</tr>";	
				$htmlPie .="<tr align=\"right\">";
					$htmlPie .="<td width=\"76%\"></td>";
					$htmlPie .="<td width=\"12%\" class=\"tituloCampo\"><b>Total Adicionales: </b></td>";
					$htmlPie .="<td width=\"12%\" id=\"tdTotalAdicionales\" ><b>$totalAdicionales1</b>";
						$htmlPie .="<input type=\"hidden\" id=\"totalAdicionalesPagar\" name=\"totalAdicionalesPagar\" value=\"$totalAdicionales\"/>";
					$htmlPie .="</td>";
				$htmlPie .="</tr>";	
				$htmlPie .="<tr align=\"right\">";
					$htmlPie .="<td width=\"76%\"></td>";
					$htmlPie .="<td width=\"12%\" class=\"tituloCampo\"><b>Total Cuotas: </b></td>";
					$htmlPie .="<td width=\"12%\" id=\"tdTotalCuotas\" class=\"trResaltarTotal\">$totalCuotas1";
						$htmlPie .="<input type=\"hidden\" id=\"totalCuotasPagar\" name=\"totalCuotasPagar\" value=\"$totalCuotas\"/>";
					$htmlPie .="</td>";
				$htmlPie .="</tr>";	
			$htmlPie .="</table>";
		$htmlPie .="</td>";
	
		
	$objResponse->assign("pieAmortizaciones", "innerHTML", $htmlPie);
	$objResponse->loadCommands(dibujarCuadroAmortizacion($dibujar));
	$objResponse->script("byId('trBotones').style.visibility = 'visible';");
	
	
	return $objResponse;
}

function guardarFinanciamiento($idPedido,$frmListado,$frmDatos,$frmFinDetalle) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDatos['txtIdEmpresa'];
	$idCliente = $frmDatos['txtIdCliente'];
	($frmDatos['selectInteresMora'] == "-1") ? $idInteresMora = null : $idInteresMora = $frmDatos['selectInteresMora'];
		
	mysql_query("START TRANSACTION;");
	
		// NUMERACION DEL DOCUMENTO (PEDIDO DE FINANCIAMIENTO)
		$queryNumeracion = sprintf("SELECT *
			FROM pg_empresa_numeracion emp_num
				INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
			WHERE emp_num.id_numeracion = %s
				AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																								WHERE suc.id_empresa = %s)))
			ORDER BY aplica_sucursales DESC LIMIT 1;",
				valTpDato( 52 , "int"), // 52 = Numeracion de Pedido por financiamiento
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		$numeroSiguiente = $numeroActual+1;
		
	if($idPedido > 0){
			
		$modo = "Editado";
		$numero = $frmDatos['txtPedido'];
			// ACTUALIZA LOS DATOS DEL PEDIDO
			
			$updateSQL = sprintf("UPDATE fi_pedido SET 
					numeracion_pedido = %s,
					id_empresa = %s,
					estatus_pedido = %s,
					id_cliente = %s,
					id_empleado = %s,
					fecha_pedido = %s,
					fecha_financiamiento = %s,
					fecha_fin_financiamiento = %s,
					id_interes_mora = %s,
					tipo_interes = %s,
					interes_financiamiento = %s,
					id_frecuencia_plazo = %s,
					id_duracion_plazo = %s,
					id_interes_plazo = %s,
					cuotas_duracion = %s,
					interes_efectivo = %s,
					numero_pagos = %s,
					monto_financiamiento_documentos = %s,
					total_adicionales = %s,
					total_intereses = %s,
					total_monto_financiar = %s
				WHERE id_pedido_financiamiento = %s;",
					valTpDato($numero, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato(0, "int"), //Pedido de Financiamiento No cancelado
					valTpDato($frmDatos['txtIdCliente'], "int"),
					valTpDato($frmDatos['hddIdUsuario'], "int"),
					valTpDato(date("Y-m-d", strtotime($frmDatos['txtFechaPedido'])), "date"),
					valTpDato(date("Y-m-d", strtotime($frmDatos['txtFechaInicial'])), "date"),
					valTpDato(date("Y-m-d", strtotime($frmFinDetalle['fecha'][$frmFinDetalle['txtNumeroPagos']])), "date"),
					valTpDato($idInteresMora, "int"),
					valTpDato($frmDatos['selectTipoInteres'], "int"),
					valTpDato($frmFinDetalle['selectInteres'], "real_inglesa"),
					valTpDato($frmFinDetalle['selectFrecuencia'], "int"),
					valTpDato($frmFinDetalle['selectDuracion'], "int"),
					valTpDato($frmFinDetalle['selectPlazoInteres'], "int"),
					valTpDato($frmFinDetalle['txtCuotasFinanciar'], "int"),
					valTpDato($frmFinDetalle['txtInteresFinanciarEfectivo'], "real_inglesa"),
					valTpDato($frmFinDetalle['txtNumeroPagos'], "int"),
					valTpDato($frmFinDetalle['txtMontoPagoDocumentos'], "real_inglesa"),
					valTpDato($frmFinDetalle['totalAdicionalesPagar'], "real_inglesa"),
					valTpDato($frmFinDetalle['totalInteresPagar'], "real_inglesa"),
					valTpDato($frmFinDetalle['totalCuotasPagar'], "real_inglesa"),
					valTpDato($idPedido, "int"));
			
			
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			
			//BORRAR LOS DOCUMENTOS Y AMORTIZACIONES ANTERIORES
			
			$deleteSQL = sprintf("DELETE FROM fi_documento WHERE id_pedido_financiamiento = %s;",valTpDato($idPedido, "int")); //BORRANDO DOCUMENTOS
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$deleteSQL = sprintf("DELETE FROM fi_amortizacion WHERE id_pedido_financiamiento = %s;",valTpDato($idPedido, "int")); //BORRANDO AMORTIZACIONES
			$Result1 = mysql_query($deleteSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			if($frmFinDetalle['cbxAdicional'] > 0){
				$deleteSQL = sprintf("DELETE FROM fi_financiamiento_adicionales WHERE id_pedido_financiamiento = %s;",valTpDato($idPedido, "int")); //BORRANDO ADICIONALES
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			}			
		
	}else{
		
		$modo = "Guardado";
		$numero = $numeroActual;
			
			// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO (PEDIDO DE FINANCIAMIENTO)
			$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
					valTpDato($idEmpresaNumeracion, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	
			// INSERTA LOS DATOS DEL PEDIDO
			
			$insertSQL = sprintf("INSERT INTO fi_pedido (numeracion_pedido, id_empresa, estatus_pedido, id_cliente, id_empleado, fecha_pedido, fecha_financiamiento, fecha_fin_financiamiento, id_interes_mora, tipo_interes, interes_financiamiento, id_frecuencia_plazo, id_duracion_plazo, id_interes_plazo, cuotas_duracion, interes_efectivo, numero_pagos, monto_financiamiento_documentos, total_adicionales, total_intereses, total_monto_financiar)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($numero, "int"),
					valTpDato($idEmpresa, "int"),
					valTpDato(0, "int"), //Pedido de Financiamiento No cancelado
					valTpDato($frmDatos['txtIdCliente'], "int"),
					valTpDato($frmDatos['hddIdUsuario'], "int"),
					valTpDato(date("Y-m-d", strtotime($frmDatos['txtFechaPedido'])), "date"),
					valTpDato(date("Y-m-d", strtotime($frmDatos['txtFechaInicial'])), "date"),
					valTpDato(date("Y-m-d", strtotime($frmFinDetalle['fecha'][$frmFinDetalle['txtNumeroPagos']])), "date"),
					valTpDato($idInteresMora, "int"),
					valTpDato($frmDatos['selectTipoInteres'], "int"),
					valTpDato($frmFinDetalle['selectInteres'], "real_inglesa"),
					valTpDato($frmFinDetalle['selectFrecuencia'], "int"),
					valTpDato($frmFinDetalle['selectDuracion'], "int"),
					valTpDato($frmFinDetalle['selectPlazoInteres'], "int"),
					valTpDato($frmFinDetalle['txtCuotasFinanciar'], "int"),
					valTpDato($frmFinDetalle['txtInteresFinanciarEfectivo'], "real_inglesa"),
					valTpDato($frmFinDetalle['txtNumeroPagos'], "int"),
					valTpDato($frmFinDetalle['txtMontoPagoDocumentos'], "real_inglesa"),
					valTpDato($frmFinDetalle['totalAdicionalesPagar'], "real_inglesa"),
					valTpDato($frmFinDetalle['totalInteresPagar'], "real_inglesa"),
					valTpDato($frmFinDetalle['totalCuotasPagar'], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			
			$idPedido = mysql_insert_id();

			
	}
			//INSERTA LOS DOCUMENTOS UTILIZADOS PARA EL FINANCIAMIENTO
			
			foreach ($frmListado['cbx'] as  $id) {
				
				$insertSQL = sprintf("INSERT INTO fi_documento(id_pedido_financiamiento, id_documento_tabla, tipo_documento, descripcion_documento, observacion_documento, fecha_documento, saldo_documento)
					VALUES (%s, %s, %s, %s, %s, %s, %s)",
						valTpDato($idPedido, "int"),
						valTpDato($frmListado['hddIdDocumento'.$id], "int"),
						valTpDato($frmListado['hddIdTipoDocumento'.$id], "text"),
						valTpDato($frmListado['hddIdDescripcionDocumento'.$id], "text"),
						valTpDato($frmListado['hddIdObservacionDocumento'.$id], "text"),
						valTpDato(date("Y-m-d", strtotime($frmListado['hddIdFechaDocumento'.$id])), "date"),
						valTpDato($frmListado['hddIdSaldoFactura'.$id], "real_inglesa"));
				
				mysql_query("SET NAME 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert($insertSQL.mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }	
			}
			
			//INSERTA LAS AMORTIZACIONES UTILIZADAS PARA EL FINANCIAMIENTO
			
			foreach ($frmFinDetalle['periodo'] as $cuotas) {
				
				if($cuotas != 0){
				$insertSQL = sprintf("INSERT INTO fi_amortizacion (id_pedido_financiamiento, periodo_cuota, monto_cuota, fecha_cuota, adicional_cuota, interes_cuota, amortizacion_cuota, capital_vivo_cuota)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato($idPedido, "int"),
						valTpDato($frmFinDetalle['periodo'][$cuotas], "int"),
						valTpDato($frmFinDetalle['cuota'][$cuotas], "real_inglesa"),
						valTpDato(date("Y-m-d", strtotime($frmFinDetalle['fecha'][$cuotas])), "date"),
						valTpDato($frmFinDetalle['adicional'][$cuotas], "real_inglesa"),
						valTpDato($frmFinDetalle['interes'][$cuotas], "real_inglesa"),
						valTpDato($frmFinDetalle['amortizacion'][$cuotas], "real_inglesa"),
						valTpDato($frmFinDetalle['capitalVivo'][$cuotas], "real_inglesa"));
				
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				}
				
			}
			
			//INSERTA LOS ADICIONALES SI EL MISMO TIENE
			
			if(count($frmFinDetalle['cbxAdicional']) > 0){
				foreach ($frmFinDetalle['cbxAdicional'] as $indice => $valor) {
					$insertSQL = sprintf("INSERT INTO fi_financiamiento_adicionales (id_pedido_financiamiento, id_adicional, tipo_adicional, monto_adicional)
					VALUES (%s, %s, %s, %s)",
							valTpDato($idPedido, "int"),
							valTpDato($frmFinDetalle['hddIdAdicionalItm'.$valor], "int"),
							valTpDato($frmFinDetalle['tipoAdicional'.$valor], "text"),
							valTpDato($frmFinDetalle['txtMontoAdicional'.$valor],"real_inglesa"));
					
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				}	
				
			}
			
	mysql_query("COMMIT;");

	
	$objResponse->alert("Pedido numero $numero $modo con Exito.");
	$objResponse->script("document.location.href='fi_financiamiento_list.php';");
	
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
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_Ws(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cliente.telf LIKE %s)",
				valTpDato("%".$valCadBusq[1]."%", "text"),
				valTpDato("%".$valCadBusq[1]."%", "text"),
				valTpDato("%".$valCadBusq[1]."%", "text"),
				valTpDato("%".$valCadBusq[1]."%", "text"));
	}

	$query = sprintf("SELECT DISTINCT
		cliente_emp.id_empresa,
		cliente.id,
		cliente.tipo,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.nit AS nit_cliente,
		cliente.licencia AS licencia_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.credito,
		cliente.status,
		cliente.tipocliente,
		cliente.bloquea_venta,
		cliente.paga_impuesto,
		perfil_prospecto.compania,
		(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) AS cantidad_modelos,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				1
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0, 2, NULL)
		END) AS tipo_cuenta_cliente,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				'Prospecto'
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0,
					'Prospecto Aprobado (Cliente Venta)',
					'Sin Prospectación (Cliente Post-Venta)')
		END) AS descripcion_tipo_cuenta_cliente
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id) %s", $sqlBusq);

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
	$htmlTh .= "<td></td>";
	$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
	$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
	$htmlTh .= ordenarCampo("xajax_listaCliente", "46%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
	$htmlTh .= ordenarCampo("xajax_listaCliente", "14%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "Teléfono");
	$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		switch ($row['tipo_cuenta_cliente']) {
			case 1 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_comment.png\" title=\"".utf8_encode("Prospecto")."\"/>"; break;
			case 2 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_green.png\" title=\"".utf8_encode("Prospecto Aprobado (Cliente Venta)")."\"/>"; break;
			default : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_gray.png\" title=\"".utf8_encode("Sin Prospectación (Cliente Post-Venta)")."\"/>"; break;
		}

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
		$htmlTb .= "<td>".$imgTipoCuentaCliente."</td>";
		$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
		$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
		$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
		$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
		$htmlTb .= "<td align=\"center\" class=\"".(($row['credito'] == "no") ? "divMsjInfo" : "divMsjAlerta")."\">".$arrayTipoPago[strtoupper($row['credito'])]."</td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
	$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
		$htmlTb .= "<td colspan=\"20\">";
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

function listaFacturas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond =  " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idCliente IN (%s)",
			valTpDato($valCadBusq[0], "campo"));
	}

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = " AND ";
		$sqlBusq .= $cond.sprintf("cxc_fact.id_empresa LIKE %s",
				valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = " AND ";
		$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura LIKE %s",
				valTpDato($valCadBusq[2], "text"));
	}

	//$valCadBusq[3] es el parametro hiddnd e la factura.
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = " AND ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cxc_fact.numeroFactura LIKE %s
		OR cxc_fact.numeroControl LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		cxc_fact.idFactura,
		cxc_fact.numeroFactura,
		empresa.nombre_empresa,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
		cliente.id AS id_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cxc_fact.estadoFactura,
		(CASE cxc_fact.estadoFactura
			WHEN 0 THEN 'No Cancelado'
			WHEN 2 THEN 'Cancelado Parcial'
		END) AS descripcion_estado_factura,
		cxc_fact.observacionFactura,
		cxc_fact.montoTotalFactura,
		cxc_fact.saldoFactura,
			
		(IFNULL(cxc_fact.subtotalFactura, 0)
			- IFNULL(cxc_fact.descuentoFactura, 0)) AS total_neto,
		
		IFNULL((SELECT SUM(cxc_fact_iva.subtotal_iva) FROM cj_cc_factura_iva cxc_fact_iva
				WHERE cxc_fact_iva.id_factura = cxc_fact.idFactura), 0) AS total_iva
		
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
		LEFT JOIN iv_pedido_venta ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido_venta AND cxc_fact.idDepartamentoOrigenFactura = 0)
		LEFT JOIN sa_orden orden ON (cxc_fact.numeroPedido = orden.id_orden AND cxc_fact.idDepartamentoOrigenFactura = 1)
		INNER JOIN pg_empresa empresa ON (cxc_fact.id_empresa = empresa.id_empresa) %s AND cxc_fact.estadoFactura IN (0,2)", $sqlBusq);

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
	$htmlTh .= ordenarCampo("xajax_listaFacturas", "12%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
	$htmlTh .= ordenarCampo("xajax_listaFacturas", "8%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
	$htmlTh .= ordenarCampo("xajax_listaFacturas", "10%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
	$htmlTh .= ordenarCampo("xajax_listaFacturas", "28%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
	$htmlTh .= ordenarCampo("xajax_listaFacturas", "12%", $pageNum, "descripcion_estado_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Factura");
	$htmlTh .= ordenarCampo("xajax_listaFacturas", "9%", $pageNum, "saldoFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo Factura");
	$htmlTh .= ordenarCampo("xajax_listaFacturas", "9%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Factura");
	$htmlTh .= ordenarCampo("xajax_listaFacturas", "10%", $pageNum, "Monto a Pagar", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto a Pagar");
	$htmlTh .= "<td width=\"1%\"></td>";
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

		switch($row['estadoFactura']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}

		$idFactura = $row['idFactura'];
		$saldoFactura = $row['saldoFactura'];
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".implode("-", array_reverse(explode("-", $row['fechaRegistroFactura'])))."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numeroFactura'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_cliente'])."</td>";
					$htmlTb .= "</tr>";
						$htmlTb .= (strlen($row['observacionFactura']) > 0) ? "<tr><td class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionFactura'])."</td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['descripcion_estado_factura']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['montoTotalFactura'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= "<input type=\"text\" style=\"text-align : right;\" class=\"inputHabilitado\" onblur=\"xajax_validarMontoFactura('idFactura$idFactura','$saldoFactura',this.value),setFormatoRafk(this, 2)\" id=\"idFactura$idFactura\" name=\"idFactura$idFactura\" size=\"8\">";
			$htmlTb .= "</td>";
			$htmlTb .= "<td><button id=\"buttonidFactura$idFactura\" name=\"buttonidFactura$idFactura\" style=\"display:none;\" type=\"button\" onclick=\"xajax_asignarFactura(".$row['idFactura'].",xajax.getFormValues('frmFacturas'),byId('idFactura$idFactura').value);\" title=\"Seleccionar Factura\" ><img src=\"../img/iconos/ico_aceptar.gif\"/></button></td>";
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

		
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);


	return $objResponse;
}

function insertarAdicional($frmAdicionales,$contFila = '') {

	$objResponse = new xajaxResponse();
	
	$idAdicional = $frmAdicionales['selectAdicionales'];
	
	//VERIFICANDO QUE SE HAYA SELECCIONADO UNA ADICIONAL 
	
	if($idAdicional == "-1"){
		return $objResponse;
	}
	
	foreach ($frmAdicionales['cbxAdicional'] as $indice => $valor){
		if ($frmAdicionales['hddIdAdicionalItm'.$valor] == $idAdicional) {
			return $objResponse->alert("El adicional seleccionado ya se encuentra agregado");
		}
	}
	
	if($contFila == ''){ 
		$contFila = count($frmAdicionales['cbxAdicional']); 
		$agregar = true; 
	}else{ 
		$contFila--; 
	}
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	
	$contFila++;
	
	// BUSCA LOS DATOS DEL ADICIONAL
	if($agregar){
		$queryAdicional = sprintf("SELECT
									*
									FROM fi_adicionales 
									WHERE id_adicional = %s",
				valTpDato($idAdicional, "int"));
	}else{
		$queryAdicional = sprintf("SELECT
										adi.nombre_adicional,
										fin_adi.tipo_adicional,
										fin_adi.monto_adicional
									FROM fi_financiamiento_adicionales fin_adi
									INNER JOIN  fi_adicionales adi ON (adi.id_adicional = fin_adi.id_adicional)
									WHERE fin_adi.id_adicional = %s",
				valTpDato($idAdicional, "int"));
	}
	
	$rsAdicional = mysql_query($queryAdicional);
	if (!$rsAdicional) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowAdicional = mysql_fetch_assoc($rsAdicional);
	
	//VERIFICANDO CABECERA

	$txtNombreAdicional = utf8_encode($rowAdicional['nombre_adicional']);
	$txtTipoAdicional = utf8_encode($rowAdicional['tipo_adicional']);
	$txtMontoAdicional = number_format($rowAdicional['monto_adicional'],2,".",",");
	
	switch ($rowAdicional['tipo_adicional']){
		case 'Total': $checkTotal = "checked=\"checked\""; $checkCuota = "" ; break;
		case 'Cuota': $checkTotal = ""; $checkCuota = "checked=\"checked\""; break;
	}
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmAdicionalPie = sprintf("$('#trItmPieAdicional').before('".
			"<tr align=\"left\" id=\"trItmAdicional%s\" title=\"trItmAdicional%s\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmAdicional%s\"><input id=\"cbxItmAdicional\" name=\"cbxItmAdicional[]\" type=\"checkbox\" value=\"%s\"/>".
			"<input id=\"cbxAdicional\" name=\"cbxAdicional[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td style=\"text-align: center;\">%s</td>".
			"<td><input type=\"text\" id=\"txtNombreItm%s\" name=\"txtNombreItm%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left;font-weight: bold;\" value=\"%s\"/></td>".
			"<td align=\"center\">Total<input type=\"radio\" name=\"tipoAdicional%s\" value=\"Total\" %s />&nbsp;&nbsp;&nbsp;Cuota<input type=\"radio\" name=\"tipoAdicional%s\" value=\"Cuota\" %s /></td>".
			"<td align=\"center\"><input type=\"text\" id=\"txtMontoAdicional%s\" class=\"inputHabilitado\"name=\"txtMontoAdicional%s\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td align=\"center\"><a id=\"aEliminarItmAdicional%s\"><img class=\"puntero\" src=\"../img/iconos/delete.png\" title=\"Quitar\"/></a>".
			"<input type=\"hidden\" id=\"hddIdAdicionalItm%s\" name=\"hddIdAdicionalItm%s\" readonly=\"readonly\" value=\"%s\"></td>".
			"</tr>');

		byId('txtMontoAdicional%s').onblur = function() {
			setFormatoRafk(this,2);
		}
			
		byId('aEliminarItmAdicional%s').onclick = function() {
			xajax_eliminarAdicional('%s');
		}",
			$idAdicional, $idAdicional, $clase,
			$idAdicional, $idAdicional,
			$idAdicional,
			$idAdicional,
			$idAdicional, $idAdicional, $rowAdicional['nombre_adicional'],
			$idAdicional,$checkTotal,$idAdicional,$checkCuota,
			$idAdicional, $idAdicional, $txtMontoAdicional,
			$idAdicional,
			$idAdicional, $idAdicional, $idAdicional,
			$idAdicional,
			$idAdicional,
			$idAdicional);

	$objResponse->script(($htmlItmAdicionalPie));

	return $objResponse;
}
	


function validarMontoFactura($idFactura,$saldoTotal,$montoPago) {
	
	$objResponse = new xajaxResponse();
	
	if($montoPago > $saldoTotal){
		$objResponse->script("byId('$idFactura').value = '';");
		$objResponse->script("byId('button$idFactura').style.display = 'none';");
		$objResponse->alert("El monto ingresado excede el saldo total.");
	}else{
		$objResponse->script("byId('button$idFactura').style.display = '';");
	}
	
	return $objResponse;
}

//XAJAX REGISTROS

$xajax->register(XAJAX_FUNCTION,"actualizarAdicionales");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarFactura");
$xajax->register(XAJAX_FUNCTION,"asignarMonto");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarDocumento");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"calcularTipoAdicional");
$xajax->register(XAJAX_FUNCTION,"calcularMonto");
$xajax->register(XAJAX_FUNCTION,"calcularTipoInteresEfectivo");
$xajax->register(XAJAX_FUNCTION,"cargarCampos");
$xajax->register(XAJAX_FUNCTION,"cargaLstAdicionales");
$xajax->register(XAJAX_FUNCTION,"cargaLstInteres");
$xajax->register(XAJAX_FUNCTION,"cargaLstInteresMora");
$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstPlazos");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoInteres");
$xajax->register(XAJAX_FUNCTION,"cargarUsuario");
$xajax->register(XAJAX_FUNCTION,"dibujarCuadroAmortizacion");
$xajax->register(XAJAX_FUNCTION,"eliminarAdicional");
$xajax->register(XAJAX_FUNCTION,"eliminarAdicionalLote");
$xajax->register(XAJAX_FUNCTION,"eliminarFactura");
$xajax->register(XAJAX_FUNCTION,"generarCuadro");
$xajax->register(XAJAX_FUNCTION,"guardarFinanciamiento");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaFacturas");
$xajax->register(XAJAX_FUNCTION,"insertarAdicional");
$xajax->register(XAJAX_FUNCTION,"validarMontoFactura");

function formatoNumero($monto){
	return number_format($monto, 3, ".", "");
}

function calcularInteresAnual($interes, $idPlazoInteres, $tipoInteres) {

	$queryFrecuencia = sprintf("SELECT * FROM fi_plazos WHERE id_plazo = %s;",
			$idPlazoInteres);

	$rsFrecuencia = mysql_query($queryFrecuencia);
	if (!$rsFrecuencia) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$rowPlazos =  mysql_fetch_assoc($rsFrecuencia);

	$cuotaAnual = $rowPlazos['cuotas_anuales'];

	switch ($tipoInteres){
		
		case 'simple'; 
			$interesAnual = $interes/$cuotaAnual;
		break;
		case 'compuesto':
			if($cuotaAnual != 1){// TASAS DE INTERES != ANUAL
				$interesAnual = $interes*$cuotaAnual;
			}else{
				$interesAnual = $interes;
			}
		break;
	
	}
	return $interesAnual;

}

function calcularWidth($valorCelda,$valorFinal){
	
	$porcentajeWidth = $valorCelda*100/$valorFinal;
	
	return $porcentajeWidth;
}

?>