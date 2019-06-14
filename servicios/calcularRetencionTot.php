<?php

require_once("../connections/conex.php");
include("../contabilidad/GenerarEnviarContabilidadDirecto.php");
echo mb_internal_encoding();
echo  "<br>http: ".mb_http_output();

echo "<br>";
/*
echo "<script>";
echo "alert('".strip_tags(str_replace('<br>','\n',recalcularIvaTot(21,12.00,75)))."');";
echo "</script>";*/

//echo recalcularIvaTot(21,12.00,75);

function recalcularIvaTot($idOrdenTot,$porcentajeIva, $porcentajeRetencion, $idIva = 2){//239 ejem 12.00 $porcentajeRetencion = 0, 75, 100 si es 0 NO hacer nada
																						//$idIva = 2 es 12:00 puede haber mas registrados pero ese es por defecto
	mysql_query("START TRANSACTION");
	//tomo informacion para calcular, tot, y factura si la posee, sino solo calcula tot
	$queryFactura = "SELECT id_factura, monto_subtotal,  ROUND((monto_subtotal * (".$porcentajeIva."/100)), 2) as sub_total_iva
					 FROM sa_orden_tot WHERE id_orden_tot = ".$idOrdenTot." LIMIT 1";
	$rsFactura = mysql_query($queryFactura);
	if(!$rsFactura) { return "Error query id factura en orden: <br>".__LINE__."<br> Error: ".mysql_error(); }
	$rowFacatura = mysql_fetch_assoc($rsFactura);
	$idFactura = $rowFacatura["id_factura"];
	$subtotalTot = $rowFacatura["monto_subtotal"];//el subtotal debe estar ya en cp factura y en totiva
	$subtotalIva = $rowFacatura["sub_total_iva"];// se inserta en totiva
	
	//actualizo el tot con el nuevo iva
	$queryActualizarTot = "UPDATE sa_orden_tot SET monto_exento = 0.00, monto_total =  ROUND((monto_subtotal * (".$porcentajeIva.")/100) + monto_subtotal, 2) 
						WHERE id_orden_tot = ".$idOrdenTot."";
	$actualizarTot = mysql_query($queryActualizarTot);
	if(!$actualizarTot) { return "Error query id factura en orden: <br>".__LINE__."<br> Error: ".mysql_error(); } //importante sino afected rows devolvera -1
	$actualizoTot = mysql_affected_rows(); //OJO MYSQL COMPRUEBA SI SON IGUALES ANTES DE ACTUALIZAR, SI SON IGUALES NO ACTUALIZA Y MANDA 0
	
	//compruebo si el tot tiene detalles con monto individual, sino tiene es un monto total
	$queryDetalleTot = "SELECT * FROM sa_orden_tot_detalle WHERE id_orden_tot = ".$idOrdenTot." AND monto IS NOT NULL";
	$detalleTot = mysql_query($queryDetalleTot);
	if(!$detalleTot) { return "Error query seleccion tot detalle: <br>".__LINE__."<br> Error: ".mysql_error(); }
	if(mysql_num_rows($detalleTot)){ return "El tot posee detalles con monto, varificar y buscar otra forma de proceder"; }
	
	//para comprobar si tiene iva el tot
	$queryTotIva = "SELECT * FROM sa_orden_tot_iva WHERE id_orden_tot = ".$idOrdenTot."";
	$itemsTotIva = mysql_query($queryTotIva);
	if(!$itemsTotIva) { return "Error query seleccion iva de tot: <br>".__LINE__."<br> Error: ".mysql_error(); }
	$tieneIvaTot = mysql_num_rows($itemsTotIva);
	
	if($tieneIvaTot){ //si tiene iva no se puede recalcular, modificar o eliminar para luego calcular
		return "El tot ya tiene iva registrado en detalle"; 
	}else{//agrego el iva al tot 1 solo iva
		$queryIvaTot = "INSERT INTO sa_orden_tot_iva (id_orden_tot, base_imponible, subtotal_iva, id_iva, iva) 
						VALUES (".$idOrdenTot.", ".$subtotalTot.", ".$subtotalIva.", ".$idIva.", ".$porcentajeIva.")";
		$agregarIvaTot = mysql_query($queryIvaTot);
		if(!$agregarIvaTot)  { return "Error query ins iva de tot: <br>".__LINE__."<br> Error: ".mysql_error(); }
		$insertoIvaTot = mysql_affected_rows();
	}
	
	//si posee factura, calcular ivas en factura y en retencion
	if($idFactura && $porcentajeRetencion){
		
		//compruebo si la factura tiene iva
		$queryIvaFacturaDetalle = "SELECT * FROM cp_factura_iva WHERE id_factura = ".$idFactura."";
		$ivaFacturaDetalle = mysql_query($queryIvaFacturaDetalle);
		if(!$ivaFacturaDetalle) { return "Error query selec iva de factura: <br>".__LINE__."<br> Error: ".mysql_error(); }
		$tieneIvaFactura = mysql_num_rows($ivaFacturaDetalle);
		
		if($tieneIvaFactura){ //si tiene iva no se puede recalcular, hacerlo manual o eliminar para generarlo
			return "La factura ya tiene iva"; 
		}else{//sino posee genera el iva
			$queryIvaFactura = "INSERT INTO cp_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva) 
											VALUES (".$idFactura.", ".$subtotalTot.", ".$subtotalIva.", ".$idIva.", ".$porcentajeIva.")";
			$agregarIvaFactura = mysql_query($queryIvaFactura);
			if(!$agregarIvaFactura) { return "Error query ins iva de factura: <br>".__LINE__."<br> Error: ".mysql_error(); }
			$insertoIvaFactura = mysql_affected_rows();
		}
		
		//compruebo el detalle de la retencion
		$queryRetencionDetalle = "SELECT * FROM cp_retenciondetalle WHERE idFactura = ".$idFactura."";
		$retencionDetalle = mysql_query($queryRetencionDetalle);
		if(!$retencionDetalle) { return "Error query select retencion detalle: <br>".__LINE__."<br> Error: ".mysql_error(); }
		$cantidadRetencionDetalle = mysql_num_rows($retencionDetalle);
		
		if($cantidadRetencionDetalle == 1){//si solo es uno lo actualizo los montos
			$rowRetencionDetalle = mysql_fetch_assoc($retencionDetalle);
			$queryActualizarRetencionDetalle = "UPDATE cp_retenciondetalle SET
												totalCompraIncluyendoIva = ROUND(baseImponible + (baseImponible * (".$porcentajeIva."/100)),2),
												impuestoIva = ROUND(baseImponible * (".$porcentajeIva."/100),2),
												IvaRetenido = ROUND((baseImponible * (".$porcentajeIva."/100)) * (porcentajeRetencion / 100),2),
												porcentajeAlicuota = ".$porcentajeIva."
			 									WHERE idFactura = ".$idFactura."";
			$actualizarRetencionDetalle = mysql_query($queryActualizarRetencionDetalle);
			if(!$actualizarRetencionDetalle) { return "Error query update retencion detalle : <br>".__LINE__."<br> Error: ".mysql_error(); }
			$actualizoRetencion = mysql_affected_rows();
			
			//despues de actualizar tomo el monto del iva retenido
			$queryBuscarRetencion = "SELECT IvaRetenido FROM cp_retenciondetalle WHERE idFactura = ".$idFactura."";
			$buscarRetencion = mysql_query($queryBuscarRetencion);
			if(!$buscarRetencion) { return "Error query select buscar retencion detalle : <br>".__LINE__."<br> Error: ".mysql_error(); }
			$rowBuscarRetencion = mysql_fetch_assoc($buscarRetencion);
			$ivaRetenido = $rowBuscarRetencion["IvaRetenido"];
			
			//reviso los pagos si tiene 1 lo actualizo, sino proceder manual
			$queryDocumentoPago = "SELECT * FROM cp_pagos_documentos WHERE id_documento_pago = ".$idFactura." AND tipo_documento_pago = 'FA' AND tipo_pago = 'RETENCION'";
			$documentoPago = mysql_query($queryDocumentoPago);
			if(!$documentoPago) { return "Error query select documento pago : <br>".__LINE__."<br> Error: ".mysql_error(); }
			$cantidadDocumentoPago = mysql_num_rows($documentoPago);
			
			if($cantidadDocumentoPago == 1){//si tiene solo 1 lo actualizo
				$queryActualizarDocumentoPago = "UPDATE cp_pagos_documentos SET monto_cancelado = ".$ivaRetenido." WHERE id_documento_pago = ".$idFactura." AND tipo_documento_pago = 'FA' AND tipo_pago = 'RETENCION'";
				$actualizarDocumentoPago = mysql_query($queryActualizarDocumentoPago);
				if(!$actualizarDocumentoPago) { return "Error query select documento pago : <br>".__LINE__."<br> Error: ".mysql_error(); }
				$actualizoDocumentoPago = mysql_affected_rows();
				
				//actualizo los montos en factura
				$queryActualizaFactura = "UPDATE cp_factura SET estatus_factura = 2, monto_exento = 0.00, saldo_factura = ROUND(subtotal_factura - ".$ivaRetenido.",2) 
								WHERE id_factura = ".$idFactura.""; //0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
				$actualizaFactura = mysql_query($queryActualizaFactura);
				if(!$actualizaFactura) { return "Error query update factura : <br>".__LINE__."<br> Error: ".mysql_error(); }
				$actualizoFactura = mysql_affected_rows();
				
					//FINAL compruebo que todo se ejecuto correctamente
					if($actualizoTot && $insertoIvaTot && $insertoIvaFactura && $actualizoRetencion && $actualizoDocumentoPago && $actualizoFactura){
						mysql_query("COMMIT");
						return "Actualizado Correctamente";	
					}else{
						mysql_query("ROLLBACK");
						return "Hubo un error, no se pudo actualizar";	
					}
					
				
			}elseif($cantidadDocumentoPago > 1){//si tiene mas se debe actualizar manualmente
				return "La factura tiene mas de un documento de pago";
			}else{//sino posee no se puede actualizar
				return "La factura no posee documento de pago";
			}
			
		}elseif($cantidadRetencionDetalle > 1){//si tiene mas de una retencion hacerla manual
			return "La factura tiene mas de una retencion";	
		}else{//sino posee retencion no se puede actualizar montos
			return "La factura no posee retencion";
		}
		
	}else{//sino posee factura solo actualizo el tot y el iva tot
		if($actualizoTot && $insertoIvaTot){ mysql_query("COMMIT"); return "Se actualizo el tot y el iva tot, no posee factura"; }
	}
	
}

function informacionTot($idOrdenTot){
	
	$query = "SHOW COLUMNS FROM sa_orden_tot";//Field 	Type 	Null 	Key 	Default 	Extra
	$rs = mysql_query($query);
	if(!$rs) { return "Error query sel columnas tot : <br>".__LINE__."<br> Error: ".mysql_error(); }
	
	$columnasTot = array();
	while($row = mysql_fetch_assoc($rs)){
		$columnasTot[] = $row["Field"];
	}
	
	//estatus 0 = No Registrado; 1 = Registrado; 2 = Asignado; 3 = Facturado
	/*$queryTot = "SELECT  
	
				 sa_orden_tot.id_orden_tot  	
				 sa_orden_tot.numero_tot 
				 sa_orden_tot.id_empresa 	
				 sa_orden_tot.id_factura 	
				 sa_orden_tot.id_proveedor
				 sa_orden_tot.aplica_libros, 
				 sa_orden_tot.observacion_factura, 
				 sa_orden_tot.tipo_pago, 
				 sa_orden_tot.estatus,
				 
				 sa_orden_tot.monto_exento,
				 sa_orden_tot.monto_subtotal,
				 sa_orden_tot.monto_total,
				 sa_orden_tot.numero_factura_proveedor,
				 sa_orden_tot.numero_control_factura
				 
				FROM sa_orden_tot
				LEFT JOIN WHERE id_orden_tot = ".$idOrdenTot."";*/
				
	$queryTot = "SELECT * FROM sa_orden_tot WHERE id_orden_tot = ".$idOrdenTot." LIMIT 1";
	$rsTot = mysql_query($queryTot);
	if(!$rsTot) { return "Error query sel tot : <br>".__LINE__."<br> Error: ".mysql_error(); }
	$row = mysql_fetch_assoc($rsTot);
	
	$tablaTot = "<table><tr>";
	foreach($columnasTot as $columnas){
		$tablaTot .= "<th>".$columnas."</th>";
	}
	$tablaTot .= "</tr><tr>";
	
	foreach($row as $columna => $dato){
		$tablaTot .= "<td>".$dato."</td>";
	}
	$tablaTot .= "</tr></table>";
	
	return $tablaTot;
	
}

function cargarAFactura($idOrdenTOT){//IMPORTANTE $idOrdenTot
	
	$valForm['rbtRetencion'] = ""; //MANUAL ESTABLECEMOS TIPO DE RETENCION == 3 ==2 o cualquiera para cualquiera 
	
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['rbtRetencion'] == 3)
		$rbtPorcentajeRetencion = 100;
	else if ($valForm['rbtRetencion'] == 2)
		$rbtPorcentajeRetencion = 75;
	else
		$rbtPorcentajeRetencion = 0;
	
	$queryOrdenTOT = "SELECT *, tipo_pago+0 FROM sa_orden_tot WHERE id_orden_tot = '".$idOrdenTOT."'";
	$rsOrdenTOT = mysql_query($queryOrdenTOT);
	if (!$rsOrdenTOT) return (mysql_error()."\n\nLine: ".__LINE__); 
	$rowOrdenTOT = mysql_fetch_array($rsOrdenTOT);
	
	$sqlProveedor = "SELECT * FROM cp_proveedor WHERE id_proveedor = '".$rowOrdenTOT['id_proveedor']."'";
	$mysqlProveedor = mysql_query($sqlProveedor);
	if (!$mysqlProveedor) return (mysql_error()."\n\nLine: ".__LINE__); 
	$proveedor = mysql_fetch_array($mysqlProveedor);
	
	if($proveedor['credito'] == 'Si'){
		
		$sqlCredito = "SELECT * FROM cp_prove_credito WHERE id_proveedor = '".$rowOrdenTOT['id_proveedor']."'";
		$mysqlCredito = mysql_query($sqlCredito);
		if (!$mysqlCredito) return (mysql_error()."\n\nLine: ".__LINE__); 
		$credito = mysql_fetch_array($mysqlCredito);
		
		$date_result1=dateadd($rowOrdenTOT['fecha_factura_proveedor'],0,$credito['diascredito'],0);
		$date_result1=substr($date_result1,6,4).substr($date_result1,2,4).substr($date_result1,0,2); 
	} else {
		$date_result1=substr($rowOrdenTOT['fecha_factura_proveedor'],6,4).substr($rowOrdenTOT['fecha_factura_proveedor'],2,4).substr($rowOrdenTOT['fecha_factura_proveedor'],0,2);
	}
	$zero=0;
	

		/* INSERTA LA FACTURA*/
		
		/*
		$insertFacturaDatos = sprintf ( "INSERT INTO cp_factura( numero_factura_proveedor, numero_control_factura, fecha_factura_proveedor, id_proveedor, fecha_origen, fecha_vencimiento, id_modulo, estatus_factura, observacion_factura, tipo_pago, monto_exento, monto_exonerado, subtotal_factura, subtotal_descuento, saldo_factura, aplica_libros, id_empresa, id_orden_compra, porcentaje_descuento)
		VALUE('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
			$rowOrdenTOT['numero_factura_proveedor'],
			$rowOrdenTOT['numero_control_factura'],
			date("Y-m-d",strtotime($rowOrdenTOT['fecha_factura_proveedor'])),
			$rowOrdenTOT['id_proveedor'],
			date("Y-m-d",strtotime($rowOrdenTOT['fecha_origen'])),
			$date_result1,
			'1',
			'0',
			utf8_encode($rowOrdenTOT['observacion_factura']),
			$rowOrdenTOT['tipo_pago+0'],
			$rowOrdenTOT['monto_exento'],
			'0',
			$rowOrdenTOT['monto_subtotal'],
			'0',
			str_replace(',','',$rowOrdenTOT['monto_total']),
			$rowOrdenTOT['aplica_libros'],
			$rowOrdenTOT['id_empresa'],
			$rowOrdenTOT['id_orden_tot'],
			'0');
		
		mysql_query("SET NAMES 'utf8'");
		
		$consultaFacturaDatos = mysql_query($insertFacturaDatos);
		$idFactura = mysql_insert_id();
		if (!$consultaFacturaDatos) return (mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("SET NAMES 'latin1';");
		*/
		$idFactura = ""; //MANUAL YA QUE LA DEBERIA AGARRAR DE ARRIBA
		
	/*ACTUALIZA SA_ORDEN_TOT PARA AGREGARLE EL ID DE LA FACTURA*/
	//$updateTOT = sprintf("UPDATE sa_orden_tot SET id_factura = %s WHERE id_orden_tot = %s",$idFactura,$idOrdenTOT);
	//$rsUpdateTOT = mysql_query($updateTOT);
	
	//if (!$rsUpdateTOT) return (mysql_error()."\n\nLine: ".__LINE__);
	
	 //GUARDA EN LA TABLA DE IVA LOS IVA GUARDADOS 
	$queryIva = "SELECT * FROM sa_orden_tot_iva WHERE id_orden_tot = '".$idOrdenTOT."'";
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return (mysql_error()."\n\nLine: ".__LINE__); 
	while ($rowIva = mysql_fetch_array($rsIva)){
		/*$insertivas = sprintf("INSERT INTO cp_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva) VALUE ('%s', '%s', '%s', '%s', '%s')",
				$idFactura,
				str_replace(',','',$rowIva['base_imponible']),
				str_replace(',','',$rowIva['subtotal_iva']),
				$rowIva['id_iva'],
				$rowIva['iva']);
		
		mysql_query("SET NAMES 'utf8'");
		
		$ResultInsertivas = mysql_query($insertivas);
		if (!$ResultInsertivas) return (mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("SET NAMES 'latin1';");
		*/
		$queryConfirmarIva = sprintf("SELECT * FROM pg_iva WHERE idIva = '%s';",$rowIva['id_iva']);
		$rsConfirmarIva = mysql_query($queryConfirmarIva);
		if (!$rsConfirmarIva) return (mysql_error()."\n\nLine: ".__LINE__); 
		$rowConfirmarIva = mysql_fetch_array($rsConfirmarIva);
				
		if ($rowConfirmarIva['tipo'] == 1 && $rowConfirmarIva['activo'] == 1 && $rowConfirmarIva['estado'] == 1){
			$alicuota = $rowIva['iva'];
			$montoAlicuota = $rowIva['subtotal_iva'];
			$porcentajeRetencion = ($rowIva['subtotal_iva'] * ($rbtPorcentajeRetencion / 100));
		}
	}
	
	if ($rbtPorcentajeRetencion){

		
		if (1){
		// INSERTA LOS DATOS EN CP_RETENCIONCABEZERA
		
		// NUMERACION DEL DOCUMENTO (Recibos de Pago)
			$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
			WHERE id_numeracion = %s
				AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																				WHERE suc.id_empresa = %s)))
			ORDER BY aplica_sucursales DESC
			LIMIT 1;",
				valTpDato(2, "int"),
				valTpDato($rowOrdenTOT['id_empresa'], "int"),
				valTpDato($rowOrdenTOT['id_empresa'], "int"));
			$rsNumeracion = mysql_query($queryNumeracion);
			if (!$rsNumeracion) { return (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$numeroActual = $rowNumeracion['numero_actual'];
		
		
			
			$queryInsertarRetencionCabezera = sprintf("INSERT INTO cp_retencioncabezera(
				idRetencionCabezera,
				numeroComprobante,
				fechaComprobante,
				anoPeriodoFiscal,
				mesPeriodoFiscal,
				idProveedor,
				idRegistrosUnidadesFisicas,
				id_empresa)
			VALUES ('' , '%s', '%s', '%s', '%s', '%s', NULL, %s);",
				date("Ym",strtotime($rowOrdenTOT['fecha_origen'])).str_pad($numeroActual, 8, "0", STR_PAD_LEFT),
				date("Y-m-d",strtotime($rowOrdenTOT['fecha_origen'])),
				date("Y",strtotime($rowOrdenTOT['fecha_origen'])),
				date("m",strtotime($rowOrdenTOT['fecha_origen'])),
				$rowOrdenTOT['id_proveedor'],
				$rowOrdenTOT['id_empresa']);
		
		mysql_query("SET NAMES 'utf8'");
		
		$rsInsertarRetencionCabezera = mysql_query($queryInsertarRetencionCabezera);
		$idRetencionCabezera = mysql_insert_id();
		if (!$rsInsertarRetencionCabezera) return (mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("SET NAMES 'latin1'");
		
		}else{
			$rowRetencion = mysql_fetch_array($rsRetencion);
			$idRetencionCabezera = $rowRetencion['idRetencionCabezera'];
		}
		
		$insertRetencionDetalle = sprintf("INSERT INTO cp_retenciondetalle(idRetencionDetalle, idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva , IvaRetenido , porcentajeRetencion) VALUES ('', '%s', '%s', '%s', '%s', '', '', '1', '', '%s', '0', '%s', '%s', '%s', '%s', '%s');",
			$idRetencionCabezera,
			date("Y-m-d",strtotime($rowOrdenTOT['fecha_factura_proveedor'])),
			$idFactura,
			$rowOrdenTOT['numero_control_factura'],
			str_replace(',','',$rowOrdenTOT['monto_total']),
			str_replace(',','',$rowOrdenTOT['monto_subtotal']),
			str_replace(',','',$alicuota),
			str_replace(',','',$montoAlicuota),
			$porcentajeRetencion,
			$rbtPorcentajeRetencion);
		
		mysql_query("SET NAMES 'utf8'");
		
		$rsInsertarRetencionDetalle = mysql_query($insertRetencionDetalle);
		if (!$rsInsertarRetencionDetalle) return (mysql_error()."\n\nLine: ".__LINE__);
		
		mysql_query("SET NAMES 'latin1'");
		
		$insertPago = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado) 
		VALUES (%s, 'FA', 'RETENCION', %s, NOW(), '%s', '-', '-', '-', '-', '%s')",
					$idFactura,
					$idRetencionCabezera,
					date("Ym",strtotime($rowOrdenTOT['fecha_origen'])).str_pad($numeroActual, 8, "0", STR_PAD_LEFT),
					$porcentajeRetencion);
		$rsInsertPago = mysql_query($insertPago);
		
		if (!$rsInsertPago) return (mysql_error()."\n\nLine: ".__LINE__);
		
		
		/*INCREMENTAR EL NUMERO DE COMPROBANTE DE RETENCION DE NUMERACIONES*/
			$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
			WHERE id_empresa_numeracion = %s;",
				valTpDato($idEmpresaNumeracion, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return (mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		$updateFactura = sprintf("UPDATE cp_factura SET saldo_factura = '%s' WHERE id_factura = %s;",
								 str_replace(',','',($rowOrdenTOT['monto_total'] - $porcentajeRetencion)),
								 $idFactura);
		$rsUpdateFactura = mysql_query($updateFactura);
		
		if (!$rsUpdateFactura) return (mysql_error()."\n\nLine: ".__LINE__);
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/* INSERTA ESTADO DE CUENTA */
	$insertEstadoCuenta = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN) VALUE ('%s','%s','%s','%s')",
		'FA',
		$idFactura,
		$rowOrdenTOT['fecha_origen'],
		1);
		
	mysql_query("SET NAMES 'utf8'");
	
	$resultadoEstadoCuenta = mysql_query($insertEstadoCuenta);
	if (!$resultadoEstadoCuenta) return (mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("SET NAMES 'latin1'");
	
	//mysql_query("COMMIT;");
		
	//mysql_query("ROLLBACK;");
		
	// MODIFICADO ERNESTO
	//generarComprasSe($idFactura,"","");//SOLO SINO SE A GENERADO LA FACTURA
	// MODIFICADO ERNESTO
	
	
	return "todo correcto";
}

function dateadd($date, $mm=0, $dd=0, $yy=0){

$date_r = getdate(strtotime($date)); 
$date_result = date("d/m/Y", mktime(($date_r["hours"]+$hh),($date_r["minutes"]+$mn),($date_r["seconds"]+$ss),($date_r["mon"]+$mm),($date_r["mday"]+$dd),($date_r["year"]+$yy)));

return $date_result;
}

//echo cargarAFactura(20);//ID DEL TOT en ordentot

?>