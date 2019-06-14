<?php 

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$numSolicitud = (strpos($frmBuscar['txtNroSolicitud'],"-")) ? substr($frmBuscar['txtNroSolicitud'], 4) : $frmBuscar['txtNroSolicitud'];

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$numSolicitud,
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['tipoPago'],
		$frmBuscar['estatusOrden'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listHistCompOrden(0, "fecha", "DESC", $valBusq));
	
	return $objResponse;
}

function verHistCompOrden($idOrdenCompra){
	$objResponse = new xajaxResponse();
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Ver Historico Orden de compra");
	
	$objResponse->loadCommands(orderCompraHead($idOrdenCompra));
	$objResponse->loadCommands(datosProveedor($idOrdenCompra));
	$objResponse->loadCommands(datosCompras($idOrdenCompra));
	$objResponse->loadCommands(detallesOrdenCompras($idOrdenCompra));
	$objResponse->script("openImg(byId('divFlotante'));");
	
	return $objResponse;
}

//CABESERA DE LA ORDEN DE COMPRA 
function orderCompraHead($idOrdenCompra){
	$objResponse = new xajaxResponse();
	
	$sqlOrdCompa = sprintf("SELECT 
		id_orden_compra, 
		vw_ga_historico_ordenes.id_solicitud_compra, 
		fecha,
		vw_ga_historico_ordenes.id_empresa, 
		CONCAT_WS('-',codigo_empresa,numero_solicitud) AS numero_solicitud, 
		logo_familia
	FROM vw_ga_historico_ordenes
		LEFT JOIN ga_solicitud_compra ON ga_solicitud_compra.id_solicitud_compra = vw_ga_historico_ordenes.id_solicitud_compra
		LEFT JOIN pg_empresa ON  pg_empresa.id_empresa = vw_ga_historico_ordenes.id_empresa
	WHERE id_orden_compra = %s",
	valTpDato($idOrdenCompra, "int"));
	$queryOrdCompa = mysql_query($sqlOrdCompa);
	if (!$queryOrdCompa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowsOrdCompa = mysql_fetch_array($queryOrdCompa);

	$tabla = "<table width=\"50%\" border=\"0\" align=\"right\">";
		$tabla .= "<tr>";
			$tabla .= "<td align='right'><strong>Orden Compra N:</strong></td>";
			$tabla .= "<td width=\"\">".$rowsOrdCompa['id_orden_compra']."</td>";
		$tabla .= "</tr>";
		$tabla .= "<tr>";
			$tabla .= "<td align='right'><strong>fecha:</strong></td>";
			$tabla .= "<td>".$rowsOrdCompa['fecha']."</td>";
		$tabla .= "</tr>";
		$tabla .= "<tr>";
			$tabla .= "<td align='right'><strong>Solicitud Compra N:</strong></td>";
			$tabla .= "<td>".$rowsOrdCompa['numero_solicitud']."</td>";
		$tabla .= "</tr>";
	$tabla .= "</table>";
		
	$img = "<img width=\"15%\" height=\"10%\" src=\"../".$rowsOrdCompa['logo_familia']."\">";
			
	$objResponse->assign("tdDatosOrde","innerHTML",$tabla);	
	$objResponse->assign("tdLogo","innerHTML",$img);	
	
	return $objResponse;
}

function datosProveedor($idOrdenCompra){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT 
		id_orden_compra, 
		ga_orden_compra.id_proveedor,
		cp_proveedor.nombre AS nombre_proveedor,
		cp_proveedor.rif AS rif_proveedor,
		cp_proveedor.correo AS correo_proveedor,
		contacto AS persona_contacto,
		cp_proveedor.telefono AS telf_proveedor,
		CONCAT_WS(' ',cp_proveedor.telfcontacto, otrotelf) AS telf_contacto_proveedor,
		cp_proveedor.direccion AS direccion_proveedor,
		cp_proveedor.fax AS fax_proveedor
	FROM ga_orden_compra
		LEFT JOIN cp_proveedor ON cp_proveedor.id_proveedor = ga_orden_compra.id_proveedor
	WHERE id_orden_compra = %s",
	valTpDato($idOrdenCompra, "int"));
	$rsProveedor = mysql_query($sql);
	if (!$rsProveedor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowsProveedor = mysql_fetch_array($rsProveedor);
		
	$tabla = "<table width=\"90%\" border=\"0\" align=\"center\">";
		$tabla .= "<tr>";
			$tabla .= "<td align=\"right\"><strong>Nombre / Razon social:</strong></td>";
			$tabla .= "<td align=\"left\">".utf8_encode($rowsProveedor['nombre_proveedor'])."</td>";
			$tabla .= "<td colspan=\"3\"align=\"right\"><strong>Rif:</strong></td>"; //".$spanProvCxP."
			$tabla .= "<td align=\"left\">".$rowsProveedor['rif_proveedor']."</td>";
		$tabla .= "</tr>";
		$tabla .= "<tr>";
			$tabla .= "<td align=\"right\"><strong>Persona de contacto:</strong></td>";
			$tabla .= "<td align=\"left\">".utf8_encode($rowsProveedor['persona_contacto'])."</td>";
			$tabla .= "<td align=\"right\"><strong>Email:</strong></td>";
			$tabla .= "<td colspan=\"3\" align=\"left\">".$rowsProveedor['correo_proveedor']."</td>";
		$tabla .= "</tr>";
		$tabla .= "<tr>";
			$tabla .= "<td align=\"right\"><strong>Direccion</strong></td>";
			$tabla .= "<td colspan=\"6\" align=\"left\">".utf8_encode($rowsProveedor['direccion_proveedor'])."</td>";
		$tabla .= "</tr>";
		$tabla .= "<tr>";					
			$tabla .= "<td align=\"right\"><strong>Telf:</strong></td>";
			$tabla .= "<td align=\"left\">".$rowsProveedor['telf_proveedor']."</td>";
			$tabla .= "<td align=\"right\"><strong>Telf Contacto:</strong></td>";
			$tabla .= "<td align=\"left\">".$rowsProveedor['telf_contacto_proveedor']."</td>";
			$tabla .= "<td align=\"right\"><strong>Fax:</strong></td>";
			$tabla .= "<td colspan=\"2\" align=\"left\">".$rowsProveedor['fax_proveedor']."</td>";
		$tabla .= "</tr>";
	$tabla .= "</table>";
			
	$objResponse->assign("tdDatosProveedor","innerHTML",$tabla);			
	return $objResponse;
}

function datosCompras($idOrdeCompra){
	$objResponse = new xajaxResponse();

	$sql = sprintf("SELECT 
		id_orden_compra,
		fecha_entrega,
		fecha_cotizacion,
		tipo_transporte,
		ga_orden_compra.id_solicitud_compra,
		numero_solicitud,
		fecha_solicitud,
		ga_orden_compra.id_empresa,
		nombre_empresa,
		pg_empresa.rif AS rif_empresa,
		pg_empresa.direccion AS direccion_empresa,
		id_empleado_contacto,CONCAT_WS(' ',contacto.nombre_empleado, contacto.apellido) AS nombre_contacto,
		contacto.email AS email_contacto,
		contacto.id_cargo_departamento,
		pg_cargo_departamento.id_cargo, 
		nombre_cargo AS nombre_cargo_contacto,
		id_empleado_recepcion,CONCAT_WS(' ',contacto.nombre_empleado, contacto.apellido) AS nombre_recepcion
	FROM ga_orden_compra
		LEFT JOIN ga_solicitud_compra ON ga_solicitud_compra.id_solicitud_compra = ga_orden_compra.id_solicitud_compra
		LEFT JOIN pg_empresa ON pg_empresa.id_empresa = ga_orden_compra.id_empresa
		LEFT JOIN pg_empleado contacto ON contacto.id_empleado = ga_orden_compra.id_empleado_contacto
		LEFT JOIN pg_cargo_departamento ON pg_cargo_departamento.id_cargo_departamento = contacto.id_cargo_departamento
		LEFT JOIN pg_cargo ON pg_cargo.id_cargo = pg_cargo_departamento.id_cargo
		LEFT JOIN pg_empleado recepcion ON recepcion.id_empleado = ga_orden_compra.id_empleado_recepcion
	WHERE id_orden_compra = %s",
	valTpDato($idOrdeCompra, "int"));	
					
	$queryOrdCompa = mysql_query($sql);
	//$objResponse->alert($sqlOrdCompa);
	if (!$queryOrdCompa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowsOrdCompa = mysql_fetch_array($queryOrdCompa);

	$tablaDtaCmp = "<table width=\"90%\" border=\"0\" align=\"center\">";
		$tablaDtaCmp .= "<tr>";
			$tablaDtaCmp .= "<td align=\"right\"><strong>Factura a nombre de:</strong></td>";
			$tablaDtaCmp .= "<td align=\"left\">".utf8_encode($rowsOrdCompa['nombre_empresa'])."</td>";
			$tablaDtaCmp .= "<td colspan=\"4\" align=\"right\"><strong>Rif:</strong></td>";//".$spanProvCxP."
			$tablaDtaCmp .= "<td align=\"left\">".$rowsOrdCompa['rif_empresa']."</td>";
		$tablaDtaCmp .= "</tr>";
		$tablaDtaCmp .= "<tr>";
			$tablaDtaCmp .= "<td align=\"right\"><strong>Persona Contacto:</strong></td>";
			$tablaDtaCmp .= "<td align=\"left\">".utf8_encode($rowsOrdCompa['nombre_contacto'])."</td>";
			$tablaDtaCmp .= "<td align=\"right\"><strong>Cargo:</strong></td>";
			$tablaDtaCmp .= "<td align=\"left\">".utf8_encode($rowsOrdCompa['nombre_cargo_contacto'])."</td>";
			$tablaDtaCmp .= "<td align=\"right\"><strong>Email:</strong></td>";
			$tablaDtaCmp .= "<td colspan=\"2\" align=\"left\">".$rowsOrdCompa['email_contacto']."</td>";
		$tablaDtaCmp .= "</tr>";
		$tablaDtaCmp .= "<tr>";
			$tablaDtaCmp .= "<td align=\"right\"><strong>Direccion de Entrega:</strong></td>";
			$tablaDtaCmp .= "<td colspan=\"6\" align=\"left\">".utf8_encode($rowsOrdCompa['direccion_empresa'])."</td>";
		$tablaDtaCmp .= "</tr>";
		$tablaDtaCmp .= "<tr>";
			$tablaDtaCmp .= "<td align=\"right\"><strong>Resp. De la Recepcion:</strong></td>";
			$tablaDtaCmp .= "<td align=\"left\">".utf8_encode($rowsOrdCompa['nombre_recepcion'])."</td>";
			$tablaDtaCmp .= "<td align=\"right\"><strong>Fecha de Entrega:</strong></td>";
			$tablaDtaCmp .= "<td align=\"left\">".date(spanDateFormat,strtotime($rowsOrdCompa['fecha_entrega']))."</td>";
			$tablaDtaCmp .= "<td colspan=\"2\" align=\"right\"><strong>Transporte a cargo de:</strong></td>";
				switch($rowsOrdCompa['tipo_transporte']){
					case 1: $transporte = "Propio"; break;
					case 2: $transporte = "Terceros"; break; 	
				}
			$tablaDtaCmp .= "<td align=\"left\">".$transporte."</td>";
		$tablaDtaCmp .= "</tr>";
	$tablaDtaCmp .= "</table>";
			
	$objResponse->assign("tdDatosCompras","innerHTML",$tablaDtaCmp);			
	return $objResponse;
}

function detallesOrdenCompras($idOrdenCompra){
	$objResponse = new xajaxResponse();
	
	$sqlOrdCompDetalles = sprintf("SELECT
		ga_orden_compra_detalle.id_orden_compra_detalle,
		ga_orden_compra_detalle.id_orden_compra,
		ga_orden_compra_detalle.id_articulo,
		descripcion,
		codigo_articulo,
		ga_orden_compra_detalle.cantidad,
		ga_orden_compra_detalle.pendiente,
		ga_orden_compra_detalle.precio_unitario,
		ga_orden_compra_detalle.id_iva,
		ga_orden_compra_detalle.iva,
		ga_orden_compra_detalle.tipo,
		ga_orden_compra_detalle.id_cliente,
		(precio_unitario * cantidad) AS subtotal,
		ga_articulos.codigo_articulo,
		ga_articulos.descripcion,
		ga_tipos_unidad.id_tipo_unidad,
		ga_tipos_unidad.unidad
	FROM ga_orden_compra_detalle
		INNER JOIN ga_articulos ON (ga_orden_compra_detalle.id_articulo = ga_articulos.id_articulo)
		INNER JOIN ga_tipos_unidad ON (ga_articulos.id_tipo_unidad = ga_tipos_unidad.id_tipo_unidad)
	WHERE id_orden_compra = %s",
		valTpDato($idOrdenCompra, "int"));
					
	$queryOrdCompDetalles = mysql_query($sqlOrdCompDetalles);
	//$objResponse->alert($sqlOrdCompa);
	if (!$queryOrdCompDetalles) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$numRows = mysql_num_rows($queryOrdCompDetalles);
	
	$sql = sprintf("SELECT 
		id_orden_compra,
		fecha_entrega,
		fecha_cotizacion,
		tipo_transporte,
		tipo_pago,
		subtotal,
		subtotal_descuento, 
		porcentaje_descuento,
		monto_letras,
		condiciones_pago,
		ga_orden_compra.observaciones,
		ga_orden_compra.id_solicitud_compra,
		numero_solicitud,
		fecha_solicitud
	FROM ga_orden_compra
		LEFT JOIN ga_solicitud_compra ON ga_solicitud_compra.id_solicitud_compra = ga_orden_compra.id_solicitud_compra
	WHERE id_orden_compra = %s",
	valTpDato($idOrdenCompra, "int"));
					
	$queryOrdCompa = mysql_query($sql);
	//$objResponse->alert($sql);
	if (!$queryOrdCompa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowsOrdCompa = mysql_fetch_array($queryOrdCompa);
	
	//CONSULTA EL IVA DE LA FACTURA
	$sqlIva = sprintf("SELECT 
		ga_orden_compra_iva.iva as iva, 
		sum(ga_orden_compra_iva.`base_imponible`) AS base,
		SUM(ga_orden_compra_iva.`subtotal_iva`) as subtotal,
		observacion
	FROM ga_orden_compra_iva 
		INNER JOIN pg_iva on (pg_iva.idIva = ga_orden_compra_iva.id_iva) 
	WHERE id_orden_compra= %s GROUP BY ga_orden_compra_iva.id_iva ORDER BY ga_orden_compra_iva.iva;",
	valTpDato($idOrdenCompra, "int"));
	$queryIva = mysql_query($sqlIva);
	//$objResponse->alert($sqlIva);
	if (!$queryIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	while($rowsIva = mysql_fetch_array($queryIva)){
		$iva[] = $rowsIva;
		$subtotalIva += $rowsIva['subtotal'];
	}
	
	//CONSULTA EL GASTO DE LA FACTURA
	$sqlGastos= sprintf("SELECT 
		ga_orden_compra_gasto.porcentaje_monto AS porcentaje_monto,
		ga_orden_compra_gasto.monto AS monto,
		pg_gastos.nombre as nombre_gasto,
		if(pg_gastos.estatus_iva = 1,'*','') AS iva
	FROM ga_orden_compra_gasto
		INNER JOIN pg_gastos ON (ga_orden_compra_gasto.id_gasto = pg_gastos.id_gasto)
	WHERE id_orden_compra = %s;",
	valTpDato($idOrdenCompra, "int"));
	$queryGasto = mysql_query($sqlGastos);
	//$objResponse->alert($sqlIva);
	if (!$queryGasto) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	while($rowsGastos = mysql_fetch_array($queryGasto)){
		$gastos[]=$rowsGastos;
		$subtotalGastos += $rowsGastos['monto'];
	}
	
	$tablaContCmp = "<table width=\"90%\" border=\"1\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\">";	
	$tablaContCmp .= "<tr>";
		$tablaContCmp .= "<td align=\"center\"><strong>N&deg;</strong></td>";
		$tablaContCmp .= "<td align=\"center\"><strong>CANTIDAD</strong></td>";
		$tablaContCmp .= "<td align=\"center\"><strong>UNIDAD</strong></td>";
		$tablaContCmp .= "<td align=\"center\"><strong>CODIGO</strong></td>";
		$tablaContCmp .= "<td colspan=\"4\" align=\"center\"><strong>DESCRIPCION</strong></td>";
		$tablaContCmp .= "<td align=\"center\"><strong>PRECIO POR UNIDAD</strong></td>";
		$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\"><strong>SUBTOTAL</strong></td>";
	$tablaContCmp .= "</tr>";
	
	$num = 0;			
	while($rowsOrdCompDetalles = mysql_fetch_array($queryOrdCompDetalles)){
		$num++;
		$detallesSubTotal[] = $rowsOrdCompDetalles['subtotal']; 
		$tablaContCmp .= "<tr>";
			$tablaContCmp .= "<td align=\"center\">".$num."</td>";
			$tablaContCmp .= "<td>".$rowsOrdCompDetalles['cantidad']."</td>";
			$tablaContCmp .= "<td align=\"center\">".$rowsOrdCompDetalles['unidad']."</td>";
			$tablaContCmp .= "<td>".utf8_encode($rowsOrdCompDetalles['codigo_articulo'])."</td>";
			$tablaContCmp .= "<td colspan=\"4\" align=\"left\">".utf8_encode($rowsOrdCompDetalles['descripcion'])."</td>";
			$tablaContCmp .= "<td align=\"center\">".$rowsOrdCompDetalles['precio_unitario']."</td>";
			$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\">".$rowsOrdCompDetalles['subtotal']."</td>";
		$tablaContCmp .= "</tr>";
	}
	
	$numTd = ($numRows + 1);
	for($td = $numTd; $td <= 20; $td++){
		$tablaContCmp .= "<tr>";
			$tablaContCmp .= "<td align=\"center\">".$numTd++."</td>";
			$tablaContCmp .= "<td></td>";
			$tablaContCmp .= "<td align=\"center\"></td>";
			$tablaContCmp .= "<td></td>";
			$tablaContCmp .= "<td colspan=\"4\" align=\"center\"></td>";
			
			$tablaContCmp .= "<td align=\"center\"></td>";
			$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\">-</td>";
		$tablaContCmp .= "</tr>";
	}

	$tablaContCmp .= "<tr>";
		$tablaContCmp .= "<td colspan=\"2\" align=\"left\">Según Cotización N&deg;: &nbsp;&nbsp;</td>";
		$tablaContCmp .= "<td colspan=\"4\">De Fecha: ".$rowsOrdCompa['fecha_cotizacion']."</td>";
		
		$tablaContCmp .= "<td align=\"center\" colspan=\"2\" rowspan=\"3\">";
			$tablaContCmp .= "
				<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
					<tr>
						<td bgcolor=\"#A0A0A4\" align=\"center\" colspan=\"2\"><strong>Gasto</strong></td>
					</tr>";
			if($gastos != NULL){
				foreach($gastos as $key => $value){
					$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td align=\"right\">".htmlentities($value['nombre_gasto']).$value['iva']. ":</td>";
						$tablaContCmp .= "<td bgcolor=\"#FFFF00\" align=\"left\" width=\"100\"> " .$value['porcentaje_monto']."</td>";
					$tablaContCmp .= "<tr>";
				}
			}
		$tablaContCmp .= "<tr>
							<td width=\"50%\" align=\"right\">Sub-Total Gastos:</td>
							<td width=\"50%\" align=\"left\" bgcolor=\"#FFFF00\">".$subtotalGastos."</td>
						</tr>
						<tr>
						  <td colspan=\"2\">*Incluye Iva:</td>
						</tr>
						
					</table>";
		$tablaContCmp .= "</td>";//
		$tablaContCmp .= "<td align=\"right\"><strong>Sub_total Bs:</strong></td>";
		$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\" align=\"right\"><strong>".$rowsOrdCompa['subtotal']."</strong></td>";
	$tablaContCmp .= "</tr>";
	
	switch($rowsOrdCompa['tipo_pago']){
		case 0:
			$tipoPago = "Crédito"; 
				break;
		case 1:
			$tipoPago = "Contado";
				break;
	}
					
	if($rowsOrdCompa['subtotal_descuento'] != 0){
		$subTotalDescuento = $rowsOrdCompa['subtotal_descuento']; 
	} else {
		$subTotalDescuento = "";
	}

	$totalOrdenCompra = ($rowsOrdCompa['subtotal'] - $subTotalDescuento) + $subtotalIva +$subtotalGastos; /*total final de la orden*/
	$tablaContCmp .= "<tr>";
		$tablaContCmp .= "<td colspan=\"2\" align=\"left\">Tipo de Pago:</td>";
		$tablaContCmp .= "<td colspan=\"4\" align=\"left\">".$tipoPago."</td>";
	/*
		$tablaContCmp .= "<td>Sub-Total Gastos:</td>";
		$tablaContCmp .= "<td bgcolor=\"#FFFF00\" width=\"100\">".$subtotalGastos."</td>";
	*/			
		$tablaContCmp .= "<td align=\"right\">Desceunto de:</td>";
		$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\">".$subTotalDescuento."</td>";
	$tablaContCmp .= "</tr>";
	$tablaContCmp .= "<tr>";
		$tablaContCmp .= "<td colspan=\"2\" align=\"left\">Condicion de Pago:</td>";
		$tablaContCmp .= "<td colspan=\"4\" align=\"center\">".utf8_encode($rowsOrdCompa['condiciones_pago'])."</td>";
		$tablaContCmp .= "<td colspan=\"2\" align=\"left\">";
			$tablaContCmp .= "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
			
			if ($iva != NULL){
				foreach($iva as $key=>$value){
					$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td align=\"right\" width=\"67%\">".utf8_encode($value['observacion']).' '.$value['iva'].'%'."</td>";
						$tablaContCmp .= "<td align=\"center\" bgcolor=\"#FFFF00\" width=\"33%\">".$value['subtotal']."</td>";
					$tablaContCmp .= "</td>";
				}
			}							
			$tablaContCmp .= "</table>";
			$tablaContCmp .= "</td>";
						

		$tablaContCmp .= "</tr>";
		$tablaContCmp .= "<tr>";
			$tablaContCmp .= "<td colspan=\"2\" align=\"left\">Son:</td>";
			$tablaContCmp .= "<td colspan=\"6\" align=\"left\">".utf8_encode($rowsOrdCompa['monto_letras'])."</td>";
			$tablaContCmp .= "<td align=\"right\"><strong>Total Bs</strong></td>";
			$tablaContCmp .= "<td align=\"right\" bgcolor=\"#FFFF00\"><strong>".$totalOrdenCompra."</strong></td>";
		$tablaContCmp .= "</tr>";
		$tablaContCmp .= "<tr>";
			$tablaContCmp .= "<td colspan=\"10\" align=\"left\">Observacion: " .utf8_encode($rowsOrdCompa['observaciones'])."</td>";
		$tablaContCmp .= "</tr>";
			/*$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td class=\"tituloArea\" colspan=\"10\">Aprobación</td>";
			$tablaContCmp .= "</tr>";
			$tablaContCmp .= "<tr>";
						$tablaContCmp .= "<td colspan=\"10\" id=\"tdAprobacion\"></td>";
			$tablaContCmp .= "</tr>";*/
	$tablaContCmp .= "</table>";
			
	$objResponse->assign("tdDetallesCompras","innerHTML",$tabla.$br.$tablaDtaCmp.$tablaContCmp);			
	return $objResponse;
}

function listHistCompOrden($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;		
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" ga_orden_compra.id_empresa = %s ", 
			valTpDato($valCadBusq[0], "text"));
	}
	
	if($valCadBusq[1] != ""){// solicitud
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numero_solicitud = %s
		OR ga_orden_compra.id_orden_compra = %s)",
			valTpDato($valCadBusq[1], "text"),
			valTpDato($valCadBusq[1], "text"));	
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "" ) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" fecha BETWEEN %s AND %s ", 
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])), "text"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])), "text"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" tipo_pago = %s ", 
			valTpDato($valCadBusq[4], "text"));
	}

	if($valCadBusq[5] != "-1" && $valCadBusq[5] != ""){
		$cond = (strlen($sqlBusq) > 0) ? "AND" : "WHERE";
		$sqlBusq .= $cond.sprintf(" estatus_orden_compra = %s ",
			valTpDato($valCadBusq[5], "int"));		
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cp_proveedor.id_proveedor LIKE %s 
			OR cp_proveedor.nombre LIKE %s 
			OR nombre_empresa LIKE %s
			OR tipo_pago LIKE %s 
			OR ga_orden_compra.observaciones LIKE %s)", 
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"),
			valTpDato("%".$valCadBusq[6]."%","text"));
	}
		
	$query = sprintf("SELECT 
		ga_orden_compra.id_orden_compra,
		ga_orden_compra.id_empresa, 
		nombre_empresa, ga_orden_compra.fecha, 
		ga_orden_compra.id_orden_compra AS num_orden,
		numero_solicitud,
		CONCAT_WS('-',codigo_empresa,numero_solicitud) AS num_solicitud,
		(CASE tipo_pago
			WHEN 0 THEN 'Credito'
			WHEN 1 THEN 'Contado'
		END) AS tipo_pago,
		ga_orden_compra.id_proveedor,cp_proveedor.nombre,ga_orden_compra.observaciones,
		(SELECT COUNT(ga_orden_compra_detalle.id_orden_compra) AS ordenes FROM ga_orden_compra_detalle
			WHERE ga_orden_compra_detalle.id_orden_compra = ga_orden_compra.id_orden_compra
		GROUP BY ga_orden_compra_detalle.id_orden_compra) AS items,
		(subtotal  + IFNULL(
				(SELECT SUM(ga_orden_compra_iva.subtotal_iva) iva_orden  
						FROM ga_orden_compra_iva 
							WHERE ga_orden_compra_iva.id_orden_compra = ga_orden_compra.id_orden_compra),0)
		) AS total_orden,
		estatus_orden_compra
	FROM ga_orden_compra
		INNER JOIN ga_solicitud_compra ON ga_orden_compra.id_solicitud_compra = ga_solicitud_compra.id_solicitud_compra
		INNER JOIN pg_empresa ON pg_empresa.id_empresa = ga_orden_compra.id_empresa
		INNER JOIN cp_proveedor ON cp_proveedor.id_proveedor = ga_orden_compra.id_proveedor
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
	
	$htmlTableIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listHistCompOrden", "15%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listHistCompOrden", "9%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro de Orden");
		$htmlTh .= ordenarCampo("xajax_listHistCompOrden", "5%", $pageNum, "num_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro de Solicitud");
		$htmlTh .= ordenarCampo("xajax_listHistCompOrden", "5%", $pageNum, "id_orden_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro de Orden");
		$htmlTh .= ordenarCampo("xajax_listHistCompOrden", "7%", $pageNum, "tipo_pago",$campOrd,$tpOrd,$valBusq,$maxRows,"Tipo Pago");
		$htmlTh .= ordenarCampo("xajax_listHistCompOrden", "25%", $pageNum, "nombre",$campOrd,$tpOrd,$valBusq,$maxRows,"Proveedor");
		$htmlTh .= ordenarCampo("xajax_listHistCompOrden", "25%", $pageNum, "observaciones",$campOrd,$tpOrd,$valBusq,$maxRows,"Observacion");
		$htmlTh .= ordenarCampo("xajax_listHistCompOrden", "5%", $pageNum, "items",$campOrd,$tpOrd,$valBusq,$maxRows,"Items");
		$htmlTh .= ordenarCampo("xajax_listHistCompOrden", "5%", $pageNum, "total_orden",$campOrd,$tpOrd,$valBusq,$maxRows,"Total orden");
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['estatus_orden_compra']){
			case 2: $estatus ="<img title=\"Ordenado\" src=\"../img/iconos/aprob_mecanico.png\"/>"; break;// 2 = Orden
			case 3: $estatus ="<img title=\"Facturado\" src=\"../img/iconos/aprob_control_calidad.png\"/>"; break;//3 = Facturado
			case 5: $estatus ="<img title=\"Anulado\" src=\"../img/iconos/aprob_jefe_taller.png\"/>"; break;// 5 = Anulado
		}
		
		$tipoPago = ($row['tipo_pago'] == 0) ? "Creito" :"Contado";//0 = Creito, 1 = Contado
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$estatus."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['num_solicitud']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['num_orden']."</td>";
			$htmlTb .= "<td align=\"left\">".$tipoPago."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row["id_proveedor"].".- ".$row['nombre'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['observaciones'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['total_orden'],2,".",",")."</td>";			
			$htmlTb .= sprintf("<td><img title=\"Ver Orden de Compra\" class=\"puntero\" src=\"../img/iconos/ico_view.png\" class=\"modalImg\" onclick=\"xajax_verHistCompOrden(%s)\"/></td>",
				$row['id_orden_compra']);//window.open('ga_orden_compra_imprimir_pdf.php?view=1&id=".$row['id_orden_compra']."');
			$htmlTb .= sprintf("<td><img title=\"Orden de Compra en PDF\" class=\"puntero\" src=\"../img/iconos/page_white_acrobat.png\" onclick=\"window.open('reportes/ga_orden_compra_pdf.php?idOrdenComp=%s&session=%s');\"/></td>",
				$row['id_orden_compra'],$_SESSION['idEmpresaUsuarioSysGts']);//ga_orden_compra_imprimir_pdf.php?view=2&id=%s'
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listHistCompOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listHistCompOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listHistCompOrden(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listHistCompOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listHistCompOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListHistOrdenCompa","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"datosCompras");
$xajax->register(XAJAX_FUNCTION,"datosProveedor");
$xajax->register(XAJAX_FUNCTION,"detallesOrdenCompras");
$xajax->register(XAJAX_FUNCTION,"listHistCompOrden");
$xajax->register(XAJAX_FUNCTION,"orderCompraHead");
$xajax->register(XAJAX_FUNCTION,"verHistCompOrden");

?>