<?php


function buscarCliente($frmBuscarCliente) {
	$objResponse = new xajaxResponse();

	$valBusq = $frmBuscarCliente['txtCriterioBuscarCliente'];
	
	$objResponse->loadCommands(listaCliente(0, "id", "ASC", $valBusq));

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
	
	$objResponse->loadCommands(listaPedido(0, "id_pedido", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstPoliza($selId = "", $nombreObjeto = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM an_poliza WHERE estatus = 1
	ORDER BY nombre_poliza;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\"  style=\"width:99%\">";
	$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_poliza']) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$row['id_poliza']."\">".utf8_encode($row['nombre_poliza'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);

	return $objResponse;
}

function cargarContratoServ($selId = ""){

	$objResponse = new xajaxResponse();

	$query = "SELECT 
		id_emp_cont_serv,
		nombre_em_cont_serv
	FROM an_empresa_cont_servicio cont_serv
	WHERE id_empresa = 1;";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstContServicio\" name=\"lstContServicio\" class=\"inputHabilitado\" style=\"width:99%\">";
	$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_emp_cont_serv']) ? "selected=\"selected\"" : "";

		$html .= "<option ".$selected." value=\"".$row['id_emp_cont_serv']."\">".utf8_encode($row['nombre_em_cont_serv'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstContServicio","innerHTML",$html);

	return $objResponse;
}


function cargarGerenteFin($selId = ""){
	
	$objResponse = new xajaxResponse();
	
	$query = "SELECT 
		empleado.id_empleado,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		cargo.nombre_cargo,
		cargo.unipersonal
	FROM pg_empleado empleado
		INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento)
		INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
		LEFT JOIN pg_usuario usu ON (empleado.id_empleado = usu.id_empleado) 
	WHERE cargo.nombre_cargo = 'GERENTE DE FINANCIAMIENTO';";
	
	$rs = mysql_query($query);
	
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstGerenteVenta\" name=\"lstGerenteVenta\" class=\"inputHabilitado\" style=\"width:99%\">";
	$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstGerenteVenta","innerHTML",$html);
	
	return $objResponse;
}

function exportarDocumentoVenta($frmBuscar) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s|%s",
			$frmBuscar['lstEmpresa'],
			$frmBuscar['txtFechaDesde'],
			$frmBuscar['txtFechaHasta'],
			(is_array($frmBuscar['lstEstatusPedido']) ? implode(",",$frmBuscar['lstEstatusPedido']) : $frmBuscar['lstEstatusPedido']),
			$frmBuscar['txtCriterio']);

	$objResponse->script("window.open('reportes/an_documento_venta_historico_excel.php?valBusq=".$valBusq."','_self');");

	return $objResponse;
}

function formContrato($idPedido, $idContrato) {
	$objResponse = new xajaxResponse();
	
	$querySQL = "SELECT
		cont.id_gerente_fin,
		cont.id_co_cliente,
		cont.nmac_beneficiario,
		cont.mot_adquisicion,
		cont.per_credit_life,
		cont.per_gap,
		cont.per_cont_serv,
		cont.ded_credit_life,
		cont.ded_gap,
		cont.ded_cont_serv,									
		cont.sunroof,
		cont.int_cuero,
		cont.cargo_fin,
		cont.uso_personal,
		cont.uso_negocio,
		cont.uso_agricola,
		cont.id_emp_cont_serv,
		cont.nombre_agencia_seguro,
		cont.nombre_gap,
		cont.direccion_agencia_seguro,
		cont.ciudad_agencia_seguro,
		cont.pais_agencia_seguro,
		cont.telefono_agencia_seguro,
		pedido.id_poliza,
		pedido.ded_poliza,
		pedido.monto_seguro,
		pedido.meses_poliza,
		pedido.meses_financiar,
		pedido.cuotas_financiar,
		pedido.interes_cuota_financiar,
		pedido.fecha_pago_cuota,
		pedido.meses_financiar2,
		pedido.cuotas_financiar2,
		pedido.interes_cuota_financiar2,
		pedido.fecha_pago_cuota2,
		pedido.meses_financiar3,
		pedido.cuotas_financiar3,
		pedido.interes_cuota_financiar3,
		pedido.fecha_pago_cuota3,
		pedido.meses_financiar4,
		pedido.cuotas_financiar4,
		pedido.interes_cuota_financiar4,
		pedido.fecha_pago_cuota4,
		pedido.num_poliza,
		pedido.periodo_poliza,
		pedido.fech_expira,
		pedido.fech_efect,
		pedido.inicial_poliza,
		pedido.cuotas_poliza,
		CONCAT_WS(' ', emp.nombre_empleado , emp.apellido) AS nom,
		CONCAT_WS(' ', cliente.nombre , cliente.apellido) AS co_cliente
	FROM an_pedido pedido
		LEFT JOIN an_adicionales_contrato cont ON (pedido.id_pedido = cont.id_pedido)								
		LEFT JOIN cj_cc_cliente cliente ON (cont.id_co_cliente = cliente.id)
		LEFT JOIN pg_empleado emp ON (cont.id_gerente_fin = emp.id_empleado)
	WHERE pedido.id_pedido = ($idPedido)";
	mysql_query("SET NAMES 'utf8';");
	$rowq = mysql_query($querySQL);
	$totalRowsContrato = mysql_num_rows($rowq);
	if (!$rowq) $objResponse->alert(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);	
	$rw = mysql_fetch_assoc($rowq);

	//Creando o grardando valores de la poliza del vehiculo
	$objResponse->loadCommands(cargaLstPoliza($rw['id_poliza'], "lstPoliza"));
	$objResponse->assign("hddIdPoliza","value",$rw['id_poliza']);
	$objResponse->assign("txtNumPoliza","value",$rw['num_poliza']);
	$objResponse->assign("txtDeduciblePoliza","value",$rw['ded_poliza']);
	$objResponse->assign("txtPeriodoPoliza","value",$rw['periodo_poliza']);
	
	$objResponse->assign("txtFechaEfect","value",date(spanDateFormat, strtotime($rw['fech_efect'])));
	$objResponse->assign("txtFechaExpi","value",date(spanDateFormat, strtotime($rw['fech_expira'])));
	$objResponse->assign("txtInicialPoliza","value",$rw['inicial_poliza']);
	$objResponse->assign("txtMontoSeguro","value",$rw['monto_seguro']);
	$objResponse->assign("txtMesesPoliza","value",$rw['meses_poliza']);
	$objResponse->assign("txtCuotasPoliza","value",$rw['cuotas_poliza']);
	
	$objResponse->assign("txtNombreAgenciaSeguro","value",$rw['nombre_agencia_seguro']);
	$objResponse->assign("txtDireccionAgenciaSeguro","value",$rw['direccion_agencia_seguro']);
	$objResponse->assign("txtCiudadAgenciaSeguro","value",$rw['ciudad_agencia_seguro']);
	$objResponse->assign("txtPaisAgenciaSeguro","value",$rw['pais_agencia_seguro']);
	$objResponse->assign("txtTelefonoAgenciaSeguro","value",$rw['telefono_agencia_seguro']);
	$objResponse->assign("txtCuotasPoliza","value",$rw['cuotas_poliza']);
	
	//Cuotas
	($rw['meses_financiar'] > 0 ) ? $objResponse->assign("lstMesesFinanciar","value",$rw['meses_financiar']) : "";
	($rw['cuotas_financiar'] > 0 ) ? $objResponse->assign("txtCuotasFinanciar","value",$rw['cuotas_financiar']) : "";
	($rw['fecha_pago_cuota'] != null) ? $objResponse->assign("txtFechaCuotaFinanciar","value",date(spanDateFormat, strtotime($rw['fecha_pago_cuota']))): "";
	$objResponse->assign("txtInteresCuotaFinanciar","value",$rw['interes_cuota_financiar']);
	
	($rw['meses_financiar2'] > 0 ) ? $objResponse->assign("lstMesesFinanciar2","value",$rw['meses_financiar2']) : "";
	($rw['cuotas_financiar2'] > 0 ) ? $objResponse->assign("txtCuotasFinanciar2","value",$rw['cuotas_financiar2']): "";
	($rw['fecha_pago_cuota2'] != null) ? $objResponse->assign("txtFechaCuotaFinanciar2","value",date(spanDateFormat, strtotime($rw['fecha_pago_cuota2']))) : "";
	$objResponse->assign("txtInteresCuotaFinanciar2","value",$rw['interes_cuota_financiar2']);
	
	($rw['meses_financiar3'] > 0 ) ? $objResponse->assign("lstMesesFinanciar3","value",$rw['meses_financiar3']) : "";
	($rw['cuotas_financiar3'] > 0 ) ? $objResponse->assign("txtCuotasFinanciar3","value",$rw['cuotas_financiar3']): "";
	($rw['fecha_pago_cuota3'] != null) ? $objResponse->assign("txtFechaCuotaFinanciar3","value",date(spanDateFormat, strtotime($rw['fecha_pago_cuota3']))) : "";
	$objResponse->assign("txtInteresCuotaFinanciar3","value",$rw['interes_cuota_financiar3']);
	
	($rw['meses_financiar4'] > 0 ) ? $objResponse->assign("lstMesesFinanciar4","value",$rw['meses_financiar4']) : "";
	($rw['cuotas_financiar4'] > 0 ) ? $objResponse->assign("txtCuotasFinanciar4","value",$rw['cuotas_financiar4']): "";
	($rw['fecha_pago_cuota4'] != null) ? $objResponse->assign("txtFechaCuotaFinanciar4","value",date(spanDateFormat, strtotime($rw['fecha_pago_cuota4']))) : "";
	$objResponse->assign("txtInteresCuotaFinanciar4","value",$rw['interes_cuota_financiar4']);
	
	//***************************************
	$objResponse->loadCommands(cargarGerenteFin($rw['id_gerente_fin']));
	$objResponse->loadCommands(cargarContratoServ($rw['id_emp_cont_serv']));
	$objResponse->assign("txtCriterioBuscarCliente","value",$rw['co_cliente']);
	$objResponse->script("byId('btnBuscarCliente').click();");
	$objResponse->assign($rw['co_cliente'], 'checked', true );
	
	if($rw['nmac_beneficiario'] == 1){
		$objResponse->assign("rdNmacSi" , 'checked', true );
	}else if($rw['nmac_beneficiario'] == 2){
		$objResponse->assign("rdNmacNo" , 'checked', true );
	}

	if($rw['mot_adquisicion'] == 1){
		$objResponse->assign("rdMotSi" , 'checked', true );
	}else if ($rw['mot_adquisicion'] == 2){
		$objResponse->assign("rdMotNo" , 'checked', true );
	}
		
	if($rw['per_credit_life'] != null){
		$objResponse->assign("rdAct2" , 'checked', true );
		$objResponse->script("byId('trPeriodoCrLife').style.visibility = 'visible';");
		$objResponse->script("byId('trDedCrLife').style.visibility = 'visible';");
		$objResponse->assign("txtPeriodoCrLife", "value", $rw['per_credit_life'] );
		$objResponse->assign("txtDedCrLife", "value", $rw['ded_credit_life'] );
	}else{
		$objResponse->assign("rdAct2" , 'disabled', true );
		
	}

	if($rw['per_gap'] != null){
		$objResponse->assign("rdAct3" , 'checked', true );
		$objResponse->script("byId('trPeriodoGap').style.visibility = 'visible';");
		$objResponse->script("byId('trDedGap').style.visibility = 'visible';");
		$objResponse->assign("txtPeriodoGap", "value", $rw['per_gap'] );
		$objResponse->assign("txtGapAgencia", "value", $rw['nombre_gap'] );
		$objResponse->assign("txtDedGap", "value", $rw['ded_gap'] );
	}else{
		$objResponse->assign("rdAct3" , 'disabled', true );
	}
	
	if($rw['per_cont_serv'] != null){
		$objResponse->assign("rdAct4" , 'checked', true );
		$objResponse->script("byId('trPeriodoContServicio').style.visibility = 'visible';");
		$objResponse->script("byId('trDedContServicio').style.visibility = 'visible';");
		$objResponse->assign("txtPeriodoContServicio", "value", $rw['per_cont_serv'] );
		$objResponse->assign("txtDedContServicio", "value", $rw['ded_cont_serv'] );
	}else{
		$objResponse->assign("rdAct4" , 'disabled', true );
		
	}

	if($rw['sunroof'] != null){
		$objResponse->assign("rdSunRoof" , 'checked', true );
	}
		
	if($rw['int_cuero'] != null){
		$objResponse->assign("rdIntCuero" , 'checked', true );
	}	
	
	if($rw['uso_personal'] != null){
		$objResponse->assign("rdPersonal" , 'checked', true );
	}
	
	if($rw['uso_negocio'] != null){
		$objResponse->assign("rdNegocio" , 'checked', true );
	}
	
	if($rw['uso_agricola'] != null){
		$objResponse->assign("rdAgricola" , 'checked', true );
	}
	
	$querySQL = sprintf("SELECT
		acc.nom_accesorio,
		acc.id_filtro_factura,
		cxc_fact_acc.id_factura_detalle_accesorios,
		cxc_fact_acc.precio_unitario
	FROM an_pedido pedido
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (pedido.id_pedido = cxc_fact.numeroPedido)
		INNER JOIN cj_cc_factura_detalle_accesorios cxc_fact_acc ON(cxc_fact.idFactura = cxc_fact_acc.id_factura)
		INNER JOIN an_accesorio acc ON (cxc_fact_acc.id_accesorio = acc.id_accesorio)
	WHERE pedido.id_pedido = %s",
		valTpDato($idPedido, "int"),
	mysql_query("SET NAMES 'utf8';"));
	$rsAdi = mysql_query($querySQL);
	if (!$rsAdi) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$arrayCargoFinan = "";
	$arrayCreditLife= "";
	$arrayGap = "";
	$arrayContServ = "";
	while($rowAdi = mysql_fetch_assoc($rsAdi)){
		if($rowAdi['id_filtro_factura'] == 6){
			$arrayGap = $rowAdi;
		}
		if($rowAdi['id_filtro_factura'] == 7){
			$arrayCargoFinan = $rowAdi;
		}
		if($rowAdi['id_filtro_factura'] == 8){
			$arrayContServ = $rowAdi;
		}
		if($rowAdi['id_filtro_factura'] == 11){
			$arrayCreditLife = $rowAdi;
		}
	}
	
	$objResponse->assign("txtCargoPorFinan","value",number_format($arrayCargoFinan['precio_unitario'], 2, ".",","));
	
	if($arrayCreditLife != ""){
		$objResponse->assign("rdAct2" , 'checked', true );
		$objResponse->script("showContent(2)");
		$objResponse->assign("txtCrLifeNombre","value",$arrayCreditLife['nom_accesorio']);
		$objResponse->assign("txtCrLifePrecio","value",$arrayCreditLife['precio_unitario']);
	}
	
	if($arrayGap != ""){
		$objResponse->assign("rdAct3" , 'checked', true );
		$objResponse->script("showContent(3)");
		$objResponse->assign("txtGapNombre","value",$arrayGap['nom_accesorio']);
		$objResponse->assign("txtGapPrecio","value",$arrayGap['precio_unitario']);
	}
	
	if($arrayContServ != ""){
		$objResponse->assign("rdAct4" , 'checked', true );
		$objResponse->script("showContent(4)");
		$objResponse->assign("txtContServicioNombre","value",$arrayContServ['nom_accesorio']);
		$objResponse->assign("txtContServicioPrecio","value",$arrayContServ['precio_unitario']);
	}
	

	if($arrayCargoFinan == ""){		
		usleep(0.5 * 1000000);
		$objResponse->script("byId('imgCerrarDivFlotante').click();");
		$objResponse->alert("Este documento no tiene cargo por financiamiento");
	}	
	
	return $objResponse;
}

function formDocumentos($idPedido, $idContrato){
	$objResponse = new xajaxResponse();
	
	//Buscando parametros para mostrar documentos a imprimir.
	$selectSQL = sprintf("SELECT * FROM an_adicionales_contrato WHERE id_adi_contrato = %s;",
		valTpDato($idContrato, "int"),
	mysql_query("SET NAMES 'utf8';"));
	$rw = mysql_query($selectSQL);
	if (!$rw) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowAdi = mysql_fetch_assoc($rw);
	
	$html .= "<table width=\"100%\">";
	// MEMBRETE
	$html .= "<tr class=\"tituloColumna\">";
		$html .= "<td width=\"58%\">".("Documentos")."</td>";
		$html .= "<td width=\"12%\">".("Estado")."</td>";
		$html .= "<td width=\"30%\">".("Imprimir")."</td>";
	$html .= "</tr>";	
	
	// TABLAS
	if ($idContrato > 0) {
		$html .= "<tr>";
			$html .= "<td class=\"divMsjAlerta\">".("CONTRATO DE VENTA AL POR MENOR A PLAZOS CON INTERÉS SIMPLE Y CLAUSULA DE ARBITRAJE")."</td>";
			$html .= "<td class=\"divMsjInfo\">".("HABILITADO")."</td>";
			$html .= "<td class=\"divMsjInfo\">";
				$html .= "<table width=\"100%\">";
				$html .= "<tr class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_nmac_2015.php?view=print&id=".$idContrato."', 960, 550);\">";
					$html .= "<td>NMAC 2001-PR (SP) 3/15</td>";
					$html .= "<td><img src=\"../img/iconos/page_white_acrobat.png\"/></td>";
				$html .= "</tr>";
				$html .= "<tr class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_nmac_2017.php?view=print&id=".$idContrato."', 960, 550);\">";
					$html .= "<td>NMAC 2001-PR (SP) 3/17</td>";
					$html .= "<td><img src=\"../img/iconos/page_white_acrobat.png\"/></td>";
				$html .= "</tr>";
				$html .= "</table>";
				$html .= "<div>Escala de Hoja: <b>TAMAÑO REAL</b></div>";
				$html .= "<div>Tipo de Hoja: <b>CONTRATO DE VENTA</b></div>";
				$html .= "<div>Dimensiones Ancho: 21.59cm Alto: 42.16cm</div>";
			$html .= "</td>";
		$html .= "</tr>";
	}
	
	if ($idContrato > 0) {
		$html .= "<tr>";
			$html .= "<td class=\"divMsjAlerta\">".("INFORMACION SOBRE EL SEGURO")."</td>";
			$html .= "<td class=\"divMsjInfo\">".("HABILITADO")."</td>";
			$html .= "<td class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_info_seguro.php?view=print&id={$idContrato}', 960, 550);\">";
				$html .= "<img src=\"../img/iconos/page_white_acrobat.png\"/><br>";
				$html .= "Tipo de Hoja: <b>INFORMACION SOBRE EL SEGURO</b><br>";
				$html .= "Dimensiones Ancho: 21.59cm Alto: 37.04cm";
			$html .= "</td>";
		$html .= "</tr>";
	}
	
	if ($idContrato > 0) {
		$html .= "<tr>";
			$html .= "<td class=\"divMsjAlerta\">".("NOTIFICACION DE GRAVAMEN<br>(NISSAN)")."</td>";
			$html .= "<td class=\"divMsjInfo\">".("HABILITADO")."</td>";
			$html .= "<td class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_gravamen_pdf.php?view=print&id=".$idContrato."', 960, 550);\">";
				$html .= "<img src=\"../img/iconos/page_white_acrobat.png\"/><br>";
				$html .= "Tipo de Hoja: <b>LETTER</b>";
			$html .= "</td>";
		$html .= "</tr>";
	}
	
	$html .= "<tr>";
		$html .= "<td class=\"divMsjAlerta\">".("SOLICITUD PRESENTACIÓN GRAVAMEN MOBILIARIO SOBRE VEHÍCULOS DE MOTOR<br>(DTOP)")."</td>";
		$html .= "<td class=\"divMsjInfo\">".("HABILITADO")."</td>";
		$html .= "<td class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_gravamen_legal_pdf.php?view=print&id=".$idContrato."&idPedido=".$idPedido."', 960, 550);\">";
			$html .= "<img src=\"../img/iconos/page_white_acrobat.png\"/><br>";
			$html .= "Tipo de Hoja: <b>LETTER</b>";
		$html .= "</td>";
	$html .= "</tr>";
	
	if ($idContrato > 0) {
		$html .= "<tr>";
			$html .= "<td class=\"divMsjAlerta\">".("MENUDEO<br>(PROMESA DE PROPORCIONAR SEGURO)")."</td>";
			$html .= "<td class=\"divMsjInfo\">".("HABILITADO")."</td>";
			$html .= "<td class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_promesa_seguro_pdf.php?view=print&id=".$idContrato."', 960, 550);\">";
				$html .= "<img src=\"../img/iconos/page_white_acrobat.png\"/><br>";
				$html .= "Tipo de Hoja: <b>LETTER</b>";
			$html .= "</td>";
		$html .= "</tr>";
	}
	
	if ($idContrato > 0) {
		$html .= "<tr>";
			$html .= "<td class=\"divMsjAlerta\">".("ELECCION DE LA CUBIERTA DE SEGUROS")."</td>";
			$html .= "<td class=\"divMsjInfo\">".("HABILITADO")."</td>";
			$html .= "<td class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_elec_cubierta_seguros.php?view=print&id=".$idContrato."', 960, 550);\">";
				$html .= "<img src=\"../img/iconos/page_white_acrobat.png\"/><br>";
				$html .= "Tipo de Hoja: <b>LETTER</b>";
			$html .= "</td>";
		$html .= "</tr>";
	}
	
	if ($idContrato > 0) {
		$html .= "<tr>";
			$html .= "<td class=\"divMsjAlerta\">".("HUNTER")."</td>";
			$html .= "<td class=\"divMsjInfo\">".("HABILITADO")."</td>";
			$html .= "<td class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_hunter_pdf.php?view=print&id=".$idContrato."', 960, 550);\">";
				$html .= "<img src=\"../img/iconos/page_white_acrobat.png\"/><br>";
				$html .= "Tipo de Hoja: <b>LETTER</b>";
			$html .= "</td>";
		$html .= "</tr>";
	}
	
	$html .= "<tr>";
		$html .= "<td class=\"divMsjAlerta\">".("LICENCIA PROVISIONAL (DTOP)")."</td>";
		$html .= "<td class=\"divMsjInfo\">".("HABILITADO")."</td>";
		$html .= "<td class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_licencia_provisional_pdf.php?view=print&id=".$idContrato."&idPedido=".$idPedido."', 960, 550);\">";
			$html .= "<img src=\"../img/iconos/page_white_acrobat.png\"/><br>";
			$html .= "Tipo de Hoja: <b>LETTER</b>";
		$html .= "</td>";
	$html .= "</tr>";
	
	$html .= "<tr>";
		$html .= "<td class=\"divMsjAlerta\">".("MUCURA(DTOP)")."</td>";
		$html .= "<td class=\"divMsjInfo\">".("HABILITADO")."</td>";
		$html .= "<td class=\"divMsjInfo\">";
			$html .= "<table width=\"100%\">";
			$html .= "<tr class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_mucura_pdf.php?view=print&id=".$idContrato."&idPedido=".$idPedido."', 960, 550);\">";
				$html .= "<td>Rev.(Mayo2016)<br>Rev.(Julio2017)</td>";
				$html .= "<td><img src=\"../img/iconos/page_white_acrobat.png\"/></td>";
			$html .= "</tr>";
			$html .= "<tr class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_mucura_feb2017_pdf.php?view=print&id=".$idContrato."&idPedido=".$idPedido."', 960, 550);\">";
				$html .= "<td>Rev.(Febrero2017)</td>";
				$html .= "<td><img src=\"../img/iconos/page_white_acrobat.png\"/></td>";
			$html .= "</tr>";
			$html .= "</table>";
			$html .= "Tipo de Hoja: <b>LETTER</b>";
		$html .= "</td>";
	$html .= "</tr>";
	
	if ($idContrato > 0) {
		$html .= "<tr width=\"100%\">";
			$html .= "<td class=\"divMsjAlerta\">".("GRAVAMEN (DEPARTAMENTO DE TRANSPORTACION DE OBRAS PUBLICAS)")."</td>";
			$html .= "<td class=\"divMsjInfo\">".("HABILITADO")."</td>";
			$html .= "<td class=\"tdSelecBotonSucces puntero\" onclick=\"verVentana('reportes/an_contrato_venta_gravamen_obras_publicas_pdf.php?view=print&id=".$idContrato."', 960, 550);\">";
				$html .= "<img src=\"../img/iconos/page_white_acrobat.png\"/><br>";
				$html .= "Tipo de Hoja: <b>LETTER</b>";
			$html .= "</td>";
		$html .= "</tr>";
	}
	$html .= "</table>";
	
	$objResponse->assign("tblDocumentos","innerHTML",$html);
	
	return $objResponse;
}

function formPedido($idPedido) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarPedido').click();"); return $objResponse; }
	
	$query = sprintf("SELECT * FROM an_pedido
	WHERE id_pedido = %s;",
		valTpDato($idPedido, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->assign("txtIdPedido","value",$idPedido);
	$objResponse->assign("txtFechaEntrega","value",date(spanDateFormat, strtotime($row['fecha_entrega'])));
	
	return $objResponse;
}
	
function guardarContrato($frmContrato,$frmListaPedido) {
	
	$objResponse = new xajaxResponse();
	
	$idPedido = $frmContrato['hddIdPedido'];
	
	$query = sprintf("SELECT
		cont.id_adi_contrato
	FROM an_adicionales_contrato cont
	WHERE cont.id_pedido = %s;",
		valTpDato($idPedido,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);	
	$rowCont = mysql_fetch_assoc($rs);
	
	$idContrato = $rowCont['id_adi_contrato'];

	mysql_query("START TRANSACTION;");

	//Actualiza poliza
	
	$updatePolizaSQL = sprintf("UPDATE an_pedido SET
		id_poliza = %s,
		num_poliza = %s,
		ded_poliza = %s,
		periodo_poliza = %s,
		fech_expira = %s,
		fech_efect = %s,
		meses_poliza = %s,
		inicial_poliza = %s,
		cuotas_poliza = %s,
		monto_seguro = %s		   			
	WHERE id_pedido = %s;",
		valTpDato($frmContrato['lstPoliza'], "int"),
		valTpDato($frmContrato['txtNumPoliza'], "text"),
		valTpDato($frmContrato['txtDeduciblePoliza'], "int"),
		valTpDato($frmContrato['txtPeriodoPoliza'], "int"),
		valTpDato(date("Y-m-d", strtotime($frmContrato['txtFechaExpi'])), "date"),
		valTpDato(date("Y-m-d", strtotime($frmContrato['txtFechaEfect'])), "date"),
		valTpDato($frmContrato['txtMesesPoliza'], "int"),
		valTpDato($frmContrato['txtInicialPoliza'], "int"),
		valTpDato($frmContrato['txtCuotasPoliza'], "real_inglesa"),
		valTpDato($frmContrato['txtMontoSeguro'], "real_inglesa"),
		valTpDato($idPedido, "int"));
	mysql_query("SET NAMES 'utf8'");
	$resPoliza= mysql_query($updatePolizaSQL);
	if (!$resPoliza) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	$updateCuotasSQL = sprintf("UPDATE an_pedido SET
		meses_financiar = %s, 
		interes_cuota_financiar = %s, 
		cuotas_financiar = %s, 
		fecha_pago_cuota = %s, 
		meses_financiar2 = %s, 
		interes_cuota_financiar2 = %s, 
		cuotas_financiar2 = %s, 
		fecha_pago_cuota2 = %s, 
		meses_financiar3 = %s, 
		interes_cuota_financiar3 = %s, 
		cuotas_financiar3 = %s, 
		fecha_pago_cuota3 = %s, 
		meses_financiar4 = %s, 
		interes_cuota_financiar4 = %s, 
		cuotas_financiar4 = %s, 
		fecha_pago_cuota4 = %s	   			
	WHERE id_pedido = %s;",
		valTpDato($frmContrato['lstMesesFinanciar'], "int"),
		valTpDato($frmContrato['txtInteresCuotaFinanciar'], "real_inglesa"),
		valTpDato($frmContrato['txtCuotasFinanciar'], "real_inglesa"),
		($frmContrato['txtFechaCuotaFinanciar']!= '') ? valTpDato(date("Y-m-d", strtotime($frmContrato['txtFechaCuotaFinanciar'])), "date") : 'null',
		valTpDato($frmContrato['lstMesesFinanciar2'], "int"),
		valTpDato($frmContrato['txtInteresCuotaFinanciar2'], "real_inglesa"),
		valTpDato($frmContrato['txtCuotasFinanciar2'], "real_inglesa"),
		($frmContrato['txtFechaCuotaFinanciar2']!= '') ? valTpDato(date("Y-m-d", strtotime($frmContrato['txtFechaCuotaFinanciar2'])), "date") :  'null',
		valTpDato($frmContrato['lstMesesFinanciar3'], "int"),
		valTpDato($frmContrato['txtInteresCuotaFinanciar3'], "real_inglesa"),
		valTpDato($frmContrato['txtCuotasFinanciar3'], "real_inglesa"),
		($frmContrato['txtFechaCuotaFinanciar3']!= '') ? valTpDato(date("Y-m-d", strtotime($frmContrato['txtFechaCuotaFinanciar3'])), "date"): 'null',
		valTpDato($frmContrato['lstMesesFinanciar4'], "int"),
		valTpDato($frmContrato['txtInteresCuotaFinanciar4'], "real_inglesa"),
		valTpDato($frmContrato['txtCuotasFinanciar4'], "real_inglesa"),
		($frmContrato['txtFechaCuotaFinanciar4']!= '') ? valTpDato(date("Y-m-d", strtotime($frmContrato['txtFechaCuotaFinanciar4'])), "date") : 'null',
		valTpDato($idPedido, "int"));
	mysql_query("SET NAMES 'utf8'");
	$resCuotas= mysql_query($updateCuotasSQL);
	if (!$resCuotas) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	
	if($idContrato > 0){
		$updateSQL = sprintf("UPDATE an_adicionales_contrato SET
			id_gerente_fin = %s,
			id_co_cliente = %s,
			nmac_beneficiario = %s,
			mot_adquisicion = %s,
			per_credit_life = %s,
			per_gap = %s,
			per_cont_serv = %s,
			ded_credit_life = %s,
			nombre_gap = %s,
			ded_gap = %s,
			ded_cont_serv = %s,
			sunroof = %s,
			int_cuero = %s,
			cargo_fin = %s,
			uso_personal = %s,
			uso_negocio = %s,
			uso_agricola = %s,
			id_emp_cont_serv = %s,
			nombre_agencia_seguro = %s,
			direccion_agencia_seguro = %s,
			ciudad_agencia_seguro = %s,
			pais_agencia_seguro = %s,
			telefono_agencia_seguro = %s
		WHERE id_adi_contrato = %s;",
			valTpDato($frmContrato['lstGerenteVenta'], "text"),
			valTpDato($frmContrato['rdCliente'], "int"),
			valTpDato($frmContrato['rdNmac'], "int"),
			valTpDato($frmContrato['rdMot'], "int"),
			valTpDato($frmContrato['txtPeriodoCrLife'], "int"),
			valTpDato($frmContrato['txtPeriodoGap'], "int"),
			valTpDato($frmContrato['txtPeriodoContServicio'], "int"),
			valTpDato($frmContrato['txtDedCrLife'], "real_inglesa"),
			valTpDato($frmContrato['txtGapAgencia'], "text"),
			valTpDato($frmContrato['txtDedGap'], "real_inglesa"),
			valTpDato($frmContrato['txtDedContServicio'], "real_inglesa"),
			valTpDato($frmContrato['rdSunRoof'], "int"),
			valTpDato($frmContrato['rdIntCuero'], "int"),
			valTpDato($frmContrato['txtCargoPorFinan'], "real_inglesa"),
			valTpDato($frmContrato['rdPersonal'], "real_inglesa"),
			valTpDato($frmContrato['rdNegocio'], "real_inglesa"),
			valTpDato($frmContrato['rdAgricola'], "real_inglesa"),
			valTpDato($frmContrato['lstContServicio'], "int"),
			valTpDato($frmContrato['txtNombreAgenciaSeguro'], "text"),
			valTpDato($frmContrato['txtDireccionAgenciaSeguro'], "text"),
			valTpDato($frmContrato['txtCiudadAgenciaSeguro'], "text"),
			valTpDato($frmContrato['txtPaisAgenciaSeguro'], "text"),
			valTpDato($frmContrato['txtTelefonoAgenciaSeguro'], "text"),
			valTpDato($idContrato, "int"));		
		mysql_query("SET NAMES 'utf8'");
		$res= mysql_query($updateSQL);
		if (!$res) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
// 		$updatePolizaSQL = sprintf("UPDATE an_pedido SET
// 						   			num_poliza = %s,
// 						   			ded_poliza = %s,
// 						   			periodo_poliza = %s,
// 						  			fech_expira = %s,
// 						   			fech_efect = %s,
// 						 			meses_poliza = %s,
// 					   				inicial_poliza = %s,
// 									cuotas_poliza = %s,
// 									monto_seguro = %s
// 							 WHERE id_pedido = %s;",
// 				valTpDato($frmContrato['txtNumPoliza'], "text"),
// 				valTpDato($frmContrato['txtDeduciblePoliza'], "int"),
// 				valTpDato($frmContrato['txtPeriodoPoliza'], "int"),
// 				valTpDato(date("Y-m-d", strtotime($frmContrato['txtFechaExpi'])), "date"),
// 				valTpDato(date("Y-m-d", strtotime($frmContrato['txtFechaEfect'])), "date"),
// 				valTpDato($frmContrato['txtMesesPoliza'], "int"),
// 				valTpDato($frmContrato['txtInicialPoliza'], "int"),
// 				valTpDato($frmContrato['txtCuotasPoliza'], "real_inglesa"),
// 				valTpDato($frmContrato['txtMontoSeguro'], "real_inglesa"),
// 				valTpDato($idPedido, "int"));		
// 		mysql_query("SET NAMES 'utf8'");
// 		$resPoliza= mysql_query($updatePolizaSQL);
// 		if (!$resPoliza) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
// 		mysql_query("SET NAMES 'latin1';");
	} else {
		$insertSQL = sprintf("INSERT INTO an_adicionales_contrato (id_pedido, id_gerente_fin, id_co_cliente, nmac_beneficiario, mot_adquisicion, per_credit_life, per_gap, per_cont_serv,ded_credit_life, nombre_gap , ded_gap, ded_cont_serv, sunroof,int_cuero,cargo_fin, uso_personal ,uso_negocio,	uso_agricola, id_emp_cont_serv, nombre_agencia_seguro, direccion_agencia_seguro, ciudad_agencia_seguro,	pais_agencia_seguro,telefono_agencia_seguro)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s , %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idPedido, "int"),
			valTpDato($frmContrato['lstGerenteVenta'], "text"),
			valTpDato($frmContrato['rdCliente'], "int"),
			valTpDato($frmContrato['rdNmac'], "int"),
			valTpDato($frmContrato['rdMot'], "int"),
			valTpDato($frmContrato['txtPeriodoCrLife'], "int"),
			valTpDato($frmContrato['txtPeriodoGap'], "int"),
			valTpDato($frmContrato['txtPeriodoContServicio'], "int"),
			valTpDato($frmContrato['txtDedCrLife'], "real_inglesa"),
			valTpDato($frmContrato['txtGapAgencia'], "text"),
			valTpDato($frmContrato['txtDedGap'], "real_inglesa"),
			valTpDato($frmContrato['txtDedContServicio'], "real_inglesa"),
			valTpDato($frmContrato['rdSunRoof'], "int"),
			valTpDato($frmContrato['rdIntCuero'], "int"),
			valTpDato($frmContrato['txtCargoPorFinan'], "real_inglesa"),
			valTpDato($frmContrato['rdPersonal'], "real_inglesa"),
			valTpDato($frmContrato['rdNegocio'], "real_inglesa"),
			valTpDato($frmContrato['rdAgricola'], "real_inglesa"),
			valTpDato($frmContrato['lstContServicio'], "int"),
			valTpDato($frmContrato['txtNombreAgenciaSeguro'], "text"),
			valTpDato($frmContrato['txtDireccionAgenciaSeguro'], "text"),
			valTpDato($frmContrato['txtCiudadAgenciaSeguro'], "text"),
			valTpDato($frmContrato['txtPaisAgenciaSeguro'], "text"),
			valTpDato($frmContrato['txtTelefonoAgenciaSeguro'], "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$idContrato = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Datos guardados con éxito.");	
	$objResponse->script("byId('btnCancelarContrato').click();");	
	$objResponse->loadCommands(listaPedido(0, 'id_pedido', 'DESC',  $_SESSION['idEmpresaUsuarioSysGts']||+byId('lstEstatusPedido').value));	
	
	$objResponse->loadCommands(listaPedido(
		$frmListaPedido['pageNum'],
		$frmListaPedido['campOrd'],
		$frmListaPedido['tpOrd'],
		$frmListaPedido['valBusq']));
	
	return $objResponse;
}

function guardarPedido($frmPedido, $frmListaPedido) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($frmPedido['txtIdPedido'] > 0) {
		if (!xvalidaAcceso($objResponse,"an_pedido_venta_list","editar")) { return $objResponse; }
		
		$updateSQL = sprintf("UPDATE an_pedido SET
			fecha_entrega = %s
		WHERE id_pedido = %s;",
			valTpDato(date("Y-m-d", strtotime($frmPedido['txtFechaEntrega'])), "date"),
			valTpDato($frmPedido['txtIdPedido'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA EL ESTADO DE VENTA DE LA UNIDAD FISICA
		$updateSQL = sprintf("UPDATE an_unidad_fisica uni_fis SET
			uni_fis.estado_venta = %s
		WHERE id_unidad_fisica IN (SELECT an_ped_vent.id_unidad_fisica FROM an_pedido an_ped_vent
									WHERE an_ped_vent.id_pedido = %s)
			AND uni_fis.estado_venta LIKE 'VENDIDO';",
			valTpDato("ENTREGADO", "text"),
			valTpDato($frmPedido['txtIdPedido'], "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Pedido guardado con éxito.");
	
	$objResponse->script("byId('btnCancelarPedido').click();");
	
	$objResponse->loadCommands(listaPedido(
		$frmListaPedido['pageNum'],
		$frmListaPedido['campOrd'],
		$frmListaPedido['tpOrd'],
		$frmListaPedido['valBusq']));
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CREDITO");

	if ($valCadBusq[0] != "-1") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lci, ci) LIKE %s
		OR CONCAT_WS('', lci, ci) LIKE %s
		OR CONCAT_Ws(' ', nombre, apellido) LIKE %s)",
				valTpDato("%".$valCadBusq[0]."%", "text"),
				valTpDato("%".$valCadBusq[0]."%", "text"),
				valTpDato("%".$valCadBusq[0]."%", "text"));
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
		END) AS descripcion_tipo_cuenta_cliente,
		vw_pg_empleado.nombre_empleado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "46%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "14%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, ("Teléfono"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['tipo_cuenta_cliente']) {
			case 1 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_comment.png\" title=\"".("Prospecto")."\"/>"; break;
			case 2 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_green.png\" title=\"".("Prospecto Aprobado (Cliente Venta)")."\"/>"; break;
			default : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_gray.png\" title=\"".("Sin Prospectación (Cliente Post-Venta)")."\"/>"; break;
		}

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<input type=\"radio\" id=\"".utf8_encode($row['nombre_cliente'])."\" name=\"rdCliente\" value=".$row['id'].">"."</td>";
			$htmlTb .= "<td>".$imgTipoCuentaCliente."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['credito'] == "no") ? "divMsjInfo" : "divMsjAlerta")."\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"50\">";
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

function listaPedido($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $raiz;
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ped_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = ped_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ped_vent.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if ($valCadBusq[3] == "00") {
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 0)");
		} else if ($valCadBusq[3] == "22") {
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 2)");
		} else if ($valCadBusq[3] == "33") {
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 3)");
		} else {
			$sqlBusq .= $cond.sprintf("(pres_vent.estado = 1 AND estado_pedido = %s)",
				valTpDato($valCadBusq[3], "int"));
		}
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(ped_vent.id_pedido LIKE %s
		OR ped_vent.numeracion_pedido LIKE %s
		OR pres_vent.id_presupuesto LIKE %s
		OR pres_vent.numeracion_presupuesto LIKE %s
		OR cxc_fact.numeroFactura LIKE %s
		OR cxc_fact.numeroControl LIKE %s
		OR cliente.id LIKE %s
		OR CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT
		ped_vent.id_pedido,
		ped_vent.numeracion_pedido,
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		ped_vent.fecha,
		cxc_fact.idFactura,
		cxc_fact.fechaRegistroFactura,
		cxc_fact.fechaVencimientoFactura,
		cxc_fact.numeroFactura,
		cxc_fact.numeroControl,
		cxc_fact.idDepartamentoOrigenFactura AS id_modulo,
		cliente.id AS id_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT('[', uni_bas.nom_uni_bas, ']: ', marca.nom_marca, ' ', modelo.nom_modelo, ' ', vers.nom_version, ' ', ano.nom_ano) AS vehiculo,
		uni_fis.serial_carroceria,
		uni_fis.placa,
		uni_fis.estado_compra,
		uni_fis.estado_venta,
		
		(ped_vent.precio_venta
			+ IFNULL(ped_vent.precio_venta * (ped_vent.porcentaje_iva + ped_vent.porcentaje_impuesto_lujo) / 100, 0)) AS precio_venta,
		
		ped_vent.porcentaje_inicial,
		ped_vent.inicial AS monto_inicial,
		ped_vent.total_inicial_gastos AS total_general,
		
		(SELECT COUNT(cxc_fact_det_vehic.id_factura_detalle_vehiculo)
		FROM cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic
			INNER JOIN cj_cc_encabezadofactura fact_vent ON (cxc_fact_det_vehic.id_factura = fact_vent.idFactura)
		WHERE fact_vent.numeroPedido = ped_vent.id_pedido
			AND anulada <> 'SI'
			AND fact_vent.idDepartamentoOrigenFactura = 2) AS cant_vehic,
		
		pres_vent.estado AS estado_presupuesto,
		ped_vent.estado_pedido,
		CONCAT_WS(' ',empleado.nombre_empleado,empleado.apellido) AS asesor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_pedido ped_vent
		INNER JOIN cj_cc_cliente cliente ON (ped_vent.id_cliente = cliente.id)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (ped_vent.id_pedido = cxc_fact.numeroPedido AND cxc_fact.idDepartamentoOrigenFactura IN (2))
		LEFT JOIN an_unidad_fisica uni_fis ON (uni_fis.id_unidad_fisica = ped_vent.id_unidad_fisica)
		LEFT JOIN an_presupuesto pres_vent ON (pres_vent.id_presupuesto = ped_vent.id_presupuesto)
		LEFT JOIN an_uni_bas uni_bas ON (uni_bas.id_uni_bas = uni_fis.id_uni_bas)
			LEFT JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			LEFT JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			LEFT JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			LEFT JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN pg_empleado empleado ON (ped_vent.asesor_ventas = empleado.id_empleado)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ped_vent.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha  Factura");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "LPAD(CONVERT(numeroFactura, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "LPAD(CONVERT(numeroControl, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "LPAD(CONVERT(numeracion_pedido, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Pedido");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "LPAD(CONVERT(numeracion_presupuesto, SIGNED), 10, 0)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "10%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "24%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo / ".$spanSerialCarroceria." / ".$spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaPedido", "6%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPedido", "14%", $pageNum, "asesor", $campOrd, $tpOrd, $valBusq, $maxRows, "Asesor");
		$htmlTh .= "<td colspan=\"8\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$imgEstatusPedido = "";
		if ($row['estado_presupuesto'] == 0 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Presupuesto Autorizado\"/>";
		} else if ($row['estado_presupuesto'] == 1 || $row['estado_presupuesto'] == "") {
			switch ($row['estado_pedido']) {
				case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Autorizado\"/>"; break;
				case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Factura\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			}
		} else if ($row['estado_presupuesto'] == 2 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Presupuesto Anulado\"/>";
		} else if ($row['estado_presupuesto'] == 3 && $row['estado_presupuesto'] != "") {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Presupuesto Desautorizado\"/>";
		}
		
		if(is_null($row['id_modulo'])){
			$row['id_modulo'] = 2;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".(($row['fechaRegistroFactura'] != "") ? date(spanDateFormat, strtotime($row['fechaRegistroFactura'])) : "")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "FA";
				$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['idFactura'];
				$aVerDcto = $objDcto->verDocumento();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeroFactura'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numeroControl'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">";
				$objDcto = new Documento;
				$objDcto->raizDir = $raiz;
				$objDcto->tipoMovimiento = (in_array("FA",array("FA","ND","AN","CH","TB"))) ? 3 : 2;
				$objDcto->tipoDocumento = "PD";
				$objDcto->tipoDocumentoMovimiento = (in_array("FA",array("NC"))) ? 2 : 1;
				$objDcto->idModulo = $row['id_modulo'];
				$objDcto->idDocumento = $row['id_pedido'];
				$objDcto->mostrarDocumento = "verPDF";
				$aVerDcto = $objDcto->verPedido();
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr align=\"right\">";
					$htmlTb .= "<td nowrap=\"nowrap\">".$aVerDcto."</td>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['numeracion_pedido'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numeracion_presupuesto'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['id_cliente'].".- ".$row['nombre_cliente'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<div>".utf8_encode($row['vehiculo'])."</div>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr class=\"textoNegrita_10px\">";
					$htmlTb .= "<td width=\"50%\">".utf8_encode($row['serial_carroceria'])."</td>";
					$htmlTb .= "<td width=\"50%\">".utf8_encode($row['placa'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['porcentaje_inicial'] == 100) ? "divMsjInfo" : "divMsjAlerta")."\">";
				$htmlTb .= ($row['porcentaje_inicial'] == 100) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['asesor'])."</td>";
			
			$htmlTb .= "<td align=\"center\">";
			if ($row['cant_vehic'] > 0 && in_array($row['estado_venta'],array("VENDIDO"))) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblPedido', '%s');\"><img class=\"puntero\" src=\"../img/iconos/accept.png\" title=\"Vehículo Entregado\"/></a>",
					$contFila,
					$row['id_pedido']);
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('reportes/an_ventas_cartas_checklist.php?id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/pencil.png\" title=\"Editar Inspección de Pre-Entrega\"/>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('reportes/an_ventas_cartas_checklist.php?view=print&id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/aprobar_presup.png\" title=\"Inspección de Pre-Entrega\"/>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('reportes/an_ventas_cartas_bienvenida.php?view=print&id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/page.png\" title=\"Carta de Bienvenida\"/>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('reportes/an_ventas_cartas_agradecimiento.php?view=print&id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/page_green.png\" title=\"Carta de Agradecimiento\"/>";
			}
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['cant_vehic'] > 0) {
				$htmlTb .= "<img class=\"puntero\" onclick=\"verVentana('reportes/an_carta_certificado_venta_pdf.php?view=print&id=".$row['id_pedido']."', 960, 550);\" src=\"../img/iconos/page_red.png\" title=\"Certificado de Orígen\"/>";
			}
			$htmlTb .= "</td>";
			
			$queryC = sprintf("SELECT
				cont.id_adi_contrato
			FROM an_adicionales_contrato cont
				WHERE cont.id_pedido = %s;",
				$row['id_pedido']);			
			$rsCont = mysql_query($queryC);
			if (!$rsCont) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsCont = mysql_num_rows($rsCont);
			$rowCont = mysql_fetch_array($rsCont);
			
			// SOLO SE PODRA VER CONTRATO POR FINANCIAMIENTO SI ES (CREDITO) Y SI EL PEDIDO YA FUE (FACTURADO)
			if ($row['porcentaje_inicial'] != 100 && $totalRowsCont > 0 && $row['estado_pedido'] == 2) {
				$htmlTb .= "<td>";
					$htmlTb .= "<img class=\"puntero\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante2(this,'tdFlotanteTitulo','".$row['id_pedido']."','',1);\" src=\"../img/iconos/contrato_editar.png\" title=\"Editar Contrato de Venta\"/>";
				$htmlTb .= "</td>";
			} else if($row['porcentaje_inicial'] != 100 && $row['estado_pedido'] == 2) {
				$htmlTb .= "<td>";
					$htmlTb .= "<img class=\"puntero\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante2(this,'tdFlotanteTitulo','".$row['id_pedido']."','',0);\" src=\"../img/iconos/contrato.png\" title=\"Contrato de Venta\"/>";
				$htmlTb .= "</td>";
			} else {
				$htmlTb .= "<td>"."</td>";
			}
			$htmlTb .= "<td>";
			if ($row['estado_pedido'] == 2) {
				$htmlTb .= "<img class=\"puntero\" rel=\"#divFlotante\" onclick=\"abrirDivFlotante2(this,'tdFlotanteTitulo','".$row['id_pedido']."','".$rowCont['id_adi_contrato']."',2);\" src=\"../img/iconos/contrato_imprimir.png\" title=\"Imprimir Documentos del Contrato\"/>";
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"30\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPedido(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPedido(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarPedido");
$xajax->register(XAJAX_FUNCTION,"cargaLstPoliza");
$xajax->register(XAJAX_FUNCTION,"cargarContratoServ");
$xajax->register(XAJAX_FUNCTION,"cargarGerenteFin");
$xajax->register(XAJAX_FUNCTION,"exportarDocumentoVenta");
$xajax->register(XAJAX_FUNCTION,"formContrato");
$xajax->register(XAJAX_FUNCTION,"formPedido");
$xajax->register(XAJAX_FUNCTION,"guardarContrato");
$xajax->register(XAJAX_FUNCTION,"guardarPedido");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaPedido");
$xajax->register(XAJAX_FUNCTION,"formDocumentos");
?>