<?php

function AgregarArticulo($frmDatosArticulo, $frmListaArticulo){
	$objResponse = new xajaxResponse();
	
	//RECORRE LOS ITEM PARA LUEGO SABER CUAL ES LA CANTIDA DE ITEM AGREGADOS A LA ORDEN 
	if (isset($frmListaArticulo['cbx'])) {
		foreach($frmListaArticulo['cbx'] as $indiceItm => $valorItm) {
			$arrayObj[] = $valorItm;
			$clase = (fmod($valorItm, 2) == 0) ? "trResaltar4" : "trResaltar5";
		}
		$itmArtNuevo = count($arrayObj) + 1 ;
	}else {
		$clase = "trResaltar4";
		$itmArtNuevo = 1; 
	}

	if(is_array($frmDatosArticulo)) {
		//CONSULTA EL ARTICULO QUE SE VA A AGREGAR Y GENERO EL NUEVO TR PARA EL LISTADO DE ARTICULOS
		$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s;", 
			valTpDato($frmDatosArticulo['hddTextIdArtAsigando'], "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		// RECORRE LOS IVAS DEL ART
		if (isset($frmDatosArticulo['cbxItmImpuesto2'])) {
			foreach($frmDatosArticulo['cbxItmImpuesto2'] as $indiceItm => $valorItm) {
				$arrayIdIvaArt []= $frmDatosArticulo['textItmImpuestoId'.$valorItm];
				$arrayPorcIvaArt []= $frmDatosArticulo['textItmImpuestoIva'.$valorItm];
			}
			$idIvaArt = implode("|",$arrayIdIvaArt);
			$porcIvaArt = implode("|",$arrayPorcIvaArt);
			$verPorcIvaArt = implode(" ",$arrayPorcIvaArt);
			$estatusIva = 1;
		}else {
			$verPorcIvaArt = "N/A";			
			$idIvaArt = 0;
			$porcIvaArt = 0;
			$estatusIva = 0;
		}

	$codigoArt = $rowArticulo['codigo_articulo'];
	$descripcionArt = $rowArticulo['descripcion'];//number_format($frmDatosArticulo['txtCostoArt'], 2, ".", ",")
	$cantidaArt = str_replace(",","",$frmDatosArticulo['txtCantidadArt']);
	$cantidaRecArt = str_replace(",","",$frmDatosArticulo['txtCantidadRecibArt']);
	$costoArt = str_replace(",","",$frmDatosArticulo['txtCostoArt']);
	$porcentajeDesc = $frmDatosArticulo['txtPorcDescuentoArt'];
	$montoDesc = $frmDatosArticulo['txtMontoDescuentoArt'];
	$idArtAsignado = $frmDatosArticulo['hddTextIdArtAsigando'];
	$tipoArt = $frmDatosArticulo['rbtTipoArt'];
	$idClienteArt = $frmDatosArticulo['txtIdClienteArt'];
	$idPedDetArt = "";

	}else{
		$queryDetOrden = sprintf("SELECT id_orden_compra_detalle,id_orden_compra,ga_orden_compra_detalle.id_articulo,codigo_articulo,
				descripcion,cantidad,pendiente,precio_unitario,tipo,id_cliente,
        #id_iva
				IFNULL(IFNULL(id_iva,(SELECT GROUP_CONCAT(id_iva SEPARATOR '|') AS id_iva FROM ga_orden_compra_detalle_iva
				WHERE ga_orden_compra_detalle_iva.id_orden_compra_detalle = ga_orden_compra_detalle.id_orden_compra_detalle)),0) AS id_iva,
        #iva
				IFNULL(IFNULL(iva,(SELECT GROUP_CONCAT(iva SEPARATOR '|') AS porcentaje_iva FROM ga_orden_compra_detalle_iva
				WHERE ga_orden_compra_detalle_iva.id_orden_compra_detalle = ga_orden_compra_detalle.id_orden_compra_detalle)),0) AS iva,
				#subTotalArt
				(cantidad * precio_unitario) AS total_items,
				ga_orden_compra_detalle.estatus
			FROM ga_orden_compra_detalle
				INNER JOIN ga_articulos ON ga_articulos.id_articulo = ga_orden_compra_detalle.id_articulo
			WHERE id_orden_compra_detalle = %s" ,
				valTpDato($frmDatosArticulo,"int"));
		$rsDetOrden = mysql_query($queryDetOrden);
		if (!$rsDetOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowDetOrdenn = mysql_fetch_assoc($rsDetOrden);

	
		$idIvaArt =  $rowDetOrdenn['id_iva'];
		$porcIvaArt =  $rowDetOrdenn['iva'];
		$arrayPorcIvaArt = explode("|", $rowDetOrdenn['iva']);
		$estatusIva = $rowDetOrdenn[''];
		
		$codigoArt = $rowDetOrdenn['codigo_articulo'];
		$descripcionArt = $rowDetOrdenn['descripcion'];
		$cantidaArt = $rowDetOrdenn['cantidad'];
		$cantidaRecArt = $rowDetOrdenn['cantidad'];
		$costoArt = $rowDetOrdenn['precio_unitario'];
		$porcentajeDesc = 0;
		$montoDesc = 0;
		$idArtAsignado = $rowDetOrdenn['id_articulo'];
		$tipoArt = $rowDetOrdenn['tipo'];
		$idClienteArt = $rowDetOrdenn['id_cliente'];
		$idPedDetArt = $rowDetOrdenn['id_orden_compra_detalle'];
	}	

$objResponse->script(sprintf("$('#trItmPie').before('".
	"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
		"<td title=\"trItm:%s\">".
			"<input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
			"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
		"</td>".
		"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
		"<td><a class=\"modalImg\" id=\"aEditarItem:%s\" rel=\"#divFlotante\">".
			"<button type=\"button\" id=\"btnEditar%s\" name=\"btnEditar%s\" title=\"Editar\">".
				"<img class=\"puntero\" src=\"../img/iconos/pencil.png\"/>".
			"</button>".
		"</a></td>".
		"<td class=\"\" style=\"display:none\"><table><tr><td>".
			"<a class=\"modalImg\" id=\"aAlmacenItem:\" rel=\"#divFlotante\">".
				"<img class=\"puntero\" src=\"../img/iconos/ico_transferir_para_almacen.gif\" title=\"\"/>".
		"</td>".
	"<td id=\"spanUbicacion:\" align=\"center\" nowrap=\"nowrap\" width=\"\" title=\"spanUbicacion:\"></td></tr></table></td>".
	"<td id=\"tdCodArt:%s\" class=\"textoNegrita_9px\" align=\"center\">%s</td>".
	"<td><div id=\"tdDescArt:%s\"></div>%s</td>".
	"<td><input type=\"text\" id=\"hddCantArt%s\" name=\"hddCantArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
	"<td><input type=\"text\" id=\"hddCantRecibArt%s\" name=\"hddCantRecibArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
	"<td><input type=\"text\" id=\"hddCantPend%s\" name=\"hddCantPend%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
	"<td><input type=\"text\" id=\"hddCostoArt%s\" name=\"hddCostoArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddPorcentageDescuentoArt%s\" name=\"hddPorcentageDescuentoArt%s\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddMontoDescuentoArt%s\" name=\"hddMontoDescuentoArt%s\" readonly=\"readonly\" value=\"%s\"/></td>".
	"<td id=\"tdIvaArt%s\"><div id=\"divTotalIvaArt%s\" style=\"white-space:nowrap\" align=\"center\"></div>".//
	"<input type=\"hidden\" id=\"hddIvaArt%s\" name=\"hddIvaArt%s\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddIdIvaArt%s\" name=\"hddIdIvaArt%s\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddEstatusIvaArt%s\" name=\"hddEstatusIvaArt%s\" value=\"%s\"></td>".
	"<td><input type=\"text\" id=\"hddTotalArt%s\" name=\"hddTotalArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddIdArt%s\" name=\"hddIdArt%s\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddGastosArt%s\" name=\"hddGastosArt%s\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddTipoArt%s\" name=\"hddTipoArt%s\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddIdClienteArt%s\" name=\"hddIdClienteArt%s\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddIdPedDetArt%s\" name=\"hddIdPedDetArt%s\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" readonly=\"readonly\" value=\"%s\"/>".
	"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" readonly=\"readonly\" value=\"%s\"/></td>".
	"</tr>');
	byId('aEditarItem:%s').onclick = function() {
		abrirDivFlotante('editar',this); 
		xajax_asignarArticulo('%s', xajax.getFormValues('frmListaArticulo'),'editarArt');
	}",
			$itmArtNuevo,$clase,
				$itmArtNuevo,
					$itmArtNuevo,
					$itmArtNuevo,
			 	$itmArtNuevo,$itmArtNuevo,
					$itmArtNuevo,
						$itmArtNuevo,$itmArtNuevo,
				$itmArtNuevo,elimCaracter(utf8_encode($codigoArt), ";"),
				$itmArtNuevo,sanear_string($descripcionArt),
				$itmArtNuevo,$itmArtNuevo,number_format($cantidaArt, 2, ".", ","),
				$itmArtNuevo,$itmArtNuevo,number_format($cantidaRecArt, 2, ".", ","),
				$itmArtNuevo,$itmArtNuevo,number_format(($cantidaArt - $cantidaRecArt), 2, ".", ","),
				$itmArtNuevo,$itmArtNuevo,number_format($costoArt, 2, ".", ","),
				$itmArtNuevo,$itmArtNuevo,number_format($porcentajeDesc, 2, ".", ","),
				$itmArtNuevo,$itmArtNuevo,number_format($montoDesc, 2, ".", ","),
					$itmArtNuevo,$itmArtNuevo,//IVA
					$itmArtNuevo,$itmArtNuevo,$porcIvaArt,
					$itmArtNuevo,$itmArtNuevo,$idIvaArt,					
					$itmArtNuevo,$itmArtNuevo,$estatusIva,
					$itmArtNuevo,$itmArtNuevo,number_format(($cantidaRecArt * $costoArt), 2, ".", ","),
					$itmArtNuevo,$itmArtNuevo,$idArtAsignado,
					$itmArtNuevo,$itmArtNuevo,number_format("", 2, ".", ","),
					$itmArtNuevo,$itmArtNuevo,$tipoArt,
					$itmArtNuevo,$itmArtNuevo,$idClienteArt,
					$itmArtNuevo,$itmArtNuevo,$idPedDetArt,
					$itmArtNuevo,$itmArtNuevo,number_format("", 2, ".", ","),
					$itmArtNuevo,$itmArtNuevo,number_format("", 2, ".", ","),
					$itmArtNuevo,
						$itmArtNuevo
					));	
			foreach($arrayPorcIvaArt as $indiceIva => $valorIva){
				if($valorIva != "" || $valorIva != 0){
					$tblIva .=sprintf("<table cellspacing=\"0\" cellpadding=\"0\" align=\"center\"><tr><td>%s</td><td>%s</td></tr></table>",
						$valorIva,"%");
				}else{
					$tblIva ="N/A";
					$objResponse->script(sprintf("byId('tdIvaArt'+%s).className = 'divMsjInfo';",$itmArtNuevo));
				}
				
			$objResponse->assign("divTotalIvaArt".$itmArtNuevo,"innerHTML",$tblIva);	
			}
						
			$objResponse->script("
				if ($('#divFlotante7').is(':visible')){
					byId('btsCerraArtOrden').click();
				}else{
					byId('btnCancelarDatosArticulo').click();
				}	
			");

			$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'))");

	return	$objResponse;	
}

function asignarFechaRegistro($frmDcto) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Factura Proveedor)
	$queryConfig402 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 402 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig402 = mysql_query($queryConfig402);
	if (!$rsConfig402) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig402 = mysql_num_rows($rsConfig402);
	$rowConfig402 = mysql_fetch_assoc($rsConfig402);
	
	$valor = explode("|",$rowConfig402['valor']);
	
	$txtFechaProveedor = explode("-",date("Y-m-d",strtotime($frmDcto['txtFechaProveedor'])));
	if ($txtFechaProveedor[1] > 0 && $txtFechaProveedor[2] > 0 && $txtFechaProveedor[0] > 0) {
		if (checkdate($txtFechaProveedor[1], $txtFechaProveedor[2], $txtFechaProveedor[0])) { // EVALUA QUE LA FECHA EXISTA
			$txtFechaRegistroCompra = date(spanDateFormat);
			$txtFechaProveedor = date(spanDateFormat,strtotime($frmDcto['txtFechaProveedor']));
			if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
				if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
					&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
				|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
					if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
					|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
						$txtFechaRegistroCompra = $txtFechaProveedor;
					} else {
						$objResponse->script("byId('cbxFechaRegistro').checked = false;");
						$objResponse->alert(utf8_encode("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra)));
					}
				} else if (!(date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
					&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
				|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
					$objResponse->script("byId('cbxFechaRegistro').checked = false;");
					$objResponse->alert(utf8_encode("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])." mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra)));
				} else {
					$txtFechaRegistroCompra = $txtFechaProveedor;
				}
			} else if ($frmDcto['cbxFechaRegistro'] == 1) {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				return $objResponse->alert(utf8_encode("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
			}
			
			$objResponse->assign("txtFechaRegistroCompra","value",$txtFechaRegistroCompra);
		} else {
			$objResponse->assign("txtFechaProveedor","value","");
		}
	} else {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
	}
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $objDestino) {
	$objResponse = new xajaxResponse();
	
	$queryProv = sprintf("SELECT id_proveedor, nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion, contacto, correococtacto, telefono, fax, credito
	FROM cp_proveedor
	WHERE id_proveedor = %s AND status = 'Activo'",
		valTpDato($idProveedor, "int"));
	$rsProv = mysql_query($queryProv);
	if (!$rsProv) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowProv = mysql_fetch_assoc($rsProv);
	
	$objResponse->assign("txtId".$objDestino,"value",$rowProv['id_proveedor']);
	$objResponse->assign("txtNombre".$objDestino,"value",utf8_encode($rowProv['nombre']));
	$objResponse->assign("txtRif".$objDestino,"value",utf8_encode($rowProv['rif_proveedor']));
	$objResponse->assign("txtDireccion".$objDestino,"innerHTML",utf8_encode($rowProv['direccion']));
	$objResponse->assign("txtContacto".$objDestino,"value",utf8_encode($rowProv['contacto']));
	$objResponse->assign("txtEmailContacto".$objDestino,"value",utf8_encode($rowProv['correococtacto']));
	$objResponse->assign("txtTelefonos".$objDestino,"value",$rowProv['telefono']);
	
	if (strtoupper($rowProv['credito']) == "SI" || $rowProv['credito'] == 1) {
		$queryProvCredito = sprintf("SELECT * FROM cp_prove_credito WHERE id_proveedor = %s;",
			valTpDato($rowProv['id_proveedor'], "int"));
		$rsProvCredito = mysql_query($queryProvCredito);
		if (!$rsProvCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowProvCredito = mysql_fetch_assoc($rsProvCredito);
		
		$objResponse->assign("txtDiasCredito".$objDestino,"value",$rowProvCredito['diascredito']);
		
		$objResponse->assign("rbtTipoPagoCredito".$objDestino,"checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito".$objDestino."').disabled = false;");
		
	} else {
		$objResponse->assign("txtDiasCredito".$objDestino,"value","0");
		
		$objResponse->assign("rbtTipoPagoContado".$objDestino,"checked","checked");
		$objResponse->script("byId('rbtTipoPagoCredito".$objDestino."').disabled = true;");
	}
	
	$objResponse->script("byId('btnCerrarListaProveedor').click();");
	
	return $objResponse;
}

function asignarArticulo($hddNumeroArt, $frmListaArticulo, $accion, $idArticulo = "") {
	$objResponse = new xajaxResponse();

	$objResponse->script("
		document.forms['frmDatosArticulo'].reset();
		byId('hddIdArt').value = '';
		byId('txtCantidadRecibArt').className = 'inputHabilitado';
		byId('txtCostoArt').className = 'inputHabilitado';"
	);

	//AGREGA EL IMPUESTO QUE TENGA EL ART
	$arrayIdImpuesto = explode("|",$frmListaArticulo['hddIdIvaArt'.$hddNumeroArt]);
	foreach($arrayIdImpuesto as $indiceIdImpuesto => $valorIdImpuesto){
		$Result1 = insertarItemImpuesto($contFila, $valorIdImpuesto);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	$objResponse->script("xajax_calcularImpuesto(xajax.getFormValues('frmDatosArticulo'));");	
	}
			
	if($frmListaArticulo == false){//SI NO EXISTE NINGUN ITEM
		$idArticulo;
		$costoArt = "";
		$CantRecibArt = "";
		$PorcentageDescuentoArt = "0.00";
		$MontoDescuentoArt = "0.00";
		$CantArt = "";
		$IdClienteArt = "";
	} else {
		$idArticulo = $frmListaArticulo['hddIdArt'.$hddNumeroArt];	
		$costoArt = str_replace(",","",$frmListaArticulo['hddCostoArt'.$hddNumeroArt]);
		$CantRecibArt = str_replace(",","",$frmListaArticulo['hddCantRecibArt'.$hddNumeroArt]);
		$PorcentageDescuentoArt = str_replace(",","",$frmListaArticulo['hddPorcentageDescuentoArt'.$hddNumeroArt]);
		$MontoDescuentoArt = str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$hddNumeroArt]);
		$CantArt = str_replace(",","",$frmListaArticulo['hddCantArt'.$hddNumeroArt]);
		$IdClienteArt = $frmListaArticulo['hddIdClienteArt'.$hddNumeroArt];
	}
	
	//BUSQUEDA DEL ARTICULO POR EL ID
	$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s", valTpDato($idArticulo, "text"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	$fechaUltimaCompra = ($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx";
	$fechaUltimaVenta = ($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx";
	
	$objResponse->assign("hddIdArt","value",$rowArticulo['id_articulo']);
	$objResponse->assign("txtCodigoArt","value",utf8_encode(elimCaracter($rowArticulo['codigo_articulo'],"-")));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtUnidadArt","value",utf8_encode($rowArticulo['unidad']));
	$objResponse->assign("txtFechaUltCompraArt","value",$fechaUltimaCompra);
	$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
	$objResponse->assign("txtFechaUltVentaArt","value",$fechaUltimaVenta);
	$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['tipo_articulo']));
	$objResponse->assign("txtCantDisponible","value",$rowArticulo['existencia']);
	
	if ($rowArticulo['decimales'] == 0) {
		$objResponse->script("
		if (navigator.appName == 'Netscape') {
			byId('txtCantidadRecibArt').onkeypress = function(e){ return validarSoloNumeros(e); }
		} else if (navigator.appName == 'Microsoft Internet Explorer') {
			byId('txtCantidadRecibArt').onkeypress = function(e){ return validarSoloNumeros(event); }
		}");
	} else if ($rowArticulo['decimales'] == 1) {
		$objResponse->script("
		if (navigator.appName == 'Netscape') {
			byId('txtCantidadRecibArt').onkeypress = function(e){ return validarSoloNumerosReales(e); }
		} else if (navigator.appName == 'Microsoft Internet Explorer') {
			byId('txtCantidadRecibArt').onkeypress = function(e){ return validarSoloNumeros(event); }
		}");
	}
	
	if ($rowArticulo['existencia'] > 0) {
		$objResponse->script("byId('txtCantDisponible').className = 'inputCantidadDisponible'");
	} else {
		$objResponse->script("byId('txtCantDisponible').className = 'inputCantidadNoDisponible'");
	}
	
	$objResponse->assign("txtCostoArt","value",number_format($costoArt, 2, ".", ","));
	$objResponse->assign("txtCantidadRecibArt","value",number_format($CantRecibArt, 2, ".", ","));
	$objResponse->assign("txtPorcDescuentoArt","value",$PorcentageDescuentoArt);
	$objResponse->assign("txtMontoDescuentoArt","value",number_format($MontoDescuentoArt, 2, ".", ","));
	$objResponse->assign("txtCantidadArt","value",number_format($CantArt, 2, ".", ","));
	
	if ($IdClienteArt == NULL || $IdClienteArt == "") {
		$objResponse->script("
			byId('rbtTipoArtReposicion').checked = true;
			byId('txtIdClienteArt').disabled = 'disabled';
			byId('txtNombreClienteArt').disabled = 'disabled';
			byId('ButtInsertClienteArt').style.display = 'none'"
		);
	} else {
		$objResponse->loadCommands(asignarCliente($IdClienteArt,$_SESSION['idEmpresaUsuarioSysGts']));
		$objResponse->script("
			byId('rbtTipoArtCliente').checked = true;
			byId('txtIdClienteArt').disabled = '';
			byId('txtNombreClienteArt').disabled = '';
			byId('ButtInsertClienteArt').style.display = '';"
		);
	}
	
	$objResponse->assign("hddNumeroArt","value",$hddNumeroArt);
	$objResponse->assign("hddTextAccion","value",$accion);

	$objResponse->assign("hddTextIdArtAsigando","value",$idArticulo);
	
	$objResponse->script("
		byId('txtCantidadArt').focus();
		byId('txtCantidadArt').select();");

	return $objResponse;
}

function asignarAlmacen($valForm, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($frmListaArticulo['cbx'])) {
		foreach($frmListaArticulo['cbx'] as $indiceItm => $valorItm) {
			$arrayObj[] = $valorItm;
		}
	}
	
	$hddNumeroArt = $valForm['hddNumeroArt2'];
	
	if ($frmListaArticulo['hddIdArtSust'.$hddNumeroArt] > 0) {
		$idArticulo = $frmListaArticulo['hddIdArtSust'.$hddNumeroArt];
	} else {
		$idArticulo = $frmListaArticulo['hddIdArt'.$hddNumeroArt];
	}
	$idCasilla = $valForm['lstCasillaAct'];
	
	// VERIFICA SI ALGUN ARTICULO DE LA LISTA TIENE LA UBICACION YA OCUPADA
	$existe = false;
	/*if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if ($frmListaArticulo['hddIdCasilla'.$valor] == $idCasilla)
				$existe = true;
		}
	}*/
	
	$queryArtAlm = sprintf("SELECT * FROM vw_ga_articulos_almacen
	WHERE id_casilla = %s
		AND estatus_articulo_almacen = 1;",
		valTpDato($idCasilla, "int"));
	$rsArtAlm = mysql_query($queryArtAlm);
	if (!$rsArtAlm) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
	$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
	
	// VERIFICA SI ALGUN ARTICULO DE LA BASE DE DATOS TIENE LA UBICACION YA OCUPADA
	if ($totalRowsArtAlm > 0 && $rowArtAlm['id_articulo'] != $idArticulo)
		$existe = true;
	
	if ($existe == false) {
		$clase = (fmod($hddNumeroArt, 2) == 0) ? "trResaltar4" : "trResaltar5";
		
		// BUSCA LOS DATOS DE LA UBICACION SELECCIONADA
		$queryArtAlm = sprintf("SELECT * FROM vw_ga_casillas WHERE id_casilla = %s;",
			valTpDato($idCasilla, "int"));
		$rsArtAlm = mysql_query($queryArtAlm);
		if (!$rsArtAlm) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsArtAlm = mysql_num_rows($rsArtAlm);
		$rowArtAlm = mysql_fetch_assoc($rsArtAlm);
		
		$objResponse->assign("hddIdCasilla".$hddNumeroArt,"value",$idCasilla);
		$objResponse->assign("spanUbicacion:".$hddNumeroArt,"innerHTML",$rowArtAlm['descripcion_almacen']."<br>".str_replace("-[]", "", $rowArtAlm['ubicacion']));
	
		$objResponse->script("
		byId('btnCancelarAlmacen').click();");
	} else {
		$objResponse->alert(utf8_encode("No puede agregar una ubicación ya ocupada"));
	}
	
	return $objResponse;
}

function asignarCliente($idCliente, $idEmpresa) {
	$objResponse = new xajaxResponse();
	
	$queryCliente = sprintf("SELECT cliente_emp.id_cliente_empresa, cliente_emp.id_empresa, cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente, CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.direccion, cliente.telf, cliente.descuento, cliente.credito,
		cliente.id_clave_movimiento_predeterminado
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
	WHERE id = %s
		AND id_empresa = %s
		AND status = 'Activo';",
		valTpDato($idCliente, "int"),
		valTpDato($idEmpresa, "int"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$objResponse->assign("txtIdClienteArt","value",$rowCliente['id']);
	$objResponse->assign("txtNombreClienteArt","value",utf8_encode($rowCliente['nombre_cliente']));
	
	$objResponse->script("byId('btnCerrarListaCLiente').click();");
	
	return $objResponse;
}

function asignarServicioMantenimiento($idServicioMantenimiento){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT
		id_servicio_mantenimiento,
		descripcion_servicio_mantenimiento
	FROM al_servicio_mantenimiento 
	WHERE id_servicio_mantenimiento = %s",
		valTpDato($idServicioMantenimiento,"int"));	
	$rs = mysql_query($sql);	
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);

	$objResponse->assign("hddIdServicioMantenimiento","value",$row['id_servicio_mantenimiento']);
	$objResponse->assign("txtDescripcionServicioMantenimiento","value",utf8_encode($row['descripcion_servicio_mantenimiento']));
	
	$objResponse->script("byId('btnCancelarServicioMantenimiento').click();");
	
	return $objResponse;
}

function asignarUnidadFisica($idUnidadFisica){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT
			uni_fis.id_unidad_fisica,
			uni_fis.id_activo_fijo,
			uni_fis.serial_carroceria,
			uni_fis.serial_motor,
			uni_fis.placa,
			(CASE uni_fis.id_condicion_unidad
				WHEN 1 THEN	'NUEVO'
				WHEN 2 THEN	'USADO'
				WHEN 3 THEN	'USADO PARTICULAR'
			END) AS condicion_unidad,
			color_ext.nom_color AS color_externo1,
			color_int.nom_color AS color_interno1,
			clase.nom_clase,
			clase.id_clase,
			uni_bas.nom_uni_bas,
			uni_fis.estado_compra,
			uni_fis.estado_venta,
			uni_fis.kilometraje,
			uni_fis.id_uni_bas,
			alm.nom_almacen,
			vw_iv_modelo.nom_ano,
			vw_iv_modelo.nom_modelo,
			vw_iv_modelo.nom_marca,
			vw_iv_modelo.id_modelo,
			
			(SELECT MIN(kilometraje)
				FROM al_servicio_mantenimiento serv_mant
				INNER JOIN al_servicio_mantenimiento_marca serv_mant_marca ON (serv_mant.id_servicio_mantenimiento = serv_mant_marca.id_servicio_mantenimiento)
				WHERE serv_mant_marca.id_marca = uni_bas.mar_uni_bas
					AND uni_fis.kilometraje >= serv_mant.kilometraje_antes AND uni_fis.kilometraje <= serv_mant.kilometraje_despues) AS kilometraje_proximo,
			
			CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM an_unidad_fisica uni_fis
			INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
			INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)			
			INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
			INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
			INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
			INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (alm.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		WHERE uni_fis.id_unidad_fisica = %s",
		valTpDato($idUnidadFisica,"int"));
	
	$rs = mysql_query($sql);	
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nSql: ".$sql);
	$row = mysql_fetch_assoc($rs);

	$objResponse->assign("txtIdUnidadFisica","value",utf8_encode($row['id_unidad_fisica']));
	$objResponse->assign("txtPlacaUnidadFisica","value",utf8_encode($row['placa']));
	$objResponse->assign("txtSerialCarroceriaUnidadFisica","value",utf8_encode($row['serial_carroceria']));
	$objResponse->assign("txtDescripcionUnidadFisica","value",utf8_encode($row['vehiculo']));
	$objResponse->assign("txtKmUnidadFisica","value",$row['kilometraje']);
	$objResponse->assign("txtKmProximoUnidadFisica","value",$row['kilometraje_proximo']);
	$objResponse->assign("lstTipoFactura","value","");
	$objResponse->assign("txtDescripcionServicioMantenimiento","value","");
	$objResponse->assign("hddIdServicioMantenimiento","value","");
	$objResponse->assign("txtCostoUnitarioUnidadFisica","value","");
	
	$objResponse->script("
	byId('txtCostoUnitarioUnidadFisica').focus();
	byId('txtCostoUnitarioUnidadFisica').select();");

	return $objResponse;
}

function buscarArticulo($valFormBus, $valFrmDcto) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s|%s",
		$valFrmDcto['txtIdEmpresa'],
		$valFormBus['textCodigoArtBus'],
		$valFormBus['lstTipoArticuloBus'],
		$valFormBus['textCriterioBus']);
	
	$objResponse->loadCommands(listadoArticulos(0, "", "", $valBusq));
		
	return $objResponse;
	
}

function BuscarCliente($valFrmCliente,$valFrmDcto){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s", 
		$valFrmDcto['txtIdEmpresa'],
		$valFrmCliente['textCriterioCliente']);

	$objResponse->loadCommands(listadoCliente(0, "", "", $valBusq));
	
	return $objResponse;	
}

function BuscarGastos($valFrmGastos){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		$valFrmGastos['selctModoGastos'],
		$valFrmGastos['selctAfectaDoct'],
		$valFrmGastos['txtBuscarCriterio']);	
		
	$objResponse->loadCommands(listadoGastos(0,"",'ASC',$valBusq));
	
	return $objResponse;	
}

function buscarNumOrden($valorFrom){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
		if ($('#tdListItemsOrden').is(':visible')){
			byId('tdListItemsOrden').style.display = 'none';
		}	
	");
	
	$numOrden = (strpos($valorFrom["textNumOrden"],"-")) ? substr($valorFrom["textNumOrden"], 4) : $valorFrom["textNumOrden"] ;

	$valBusq = sprintf("%s",
		$numOrden);	
		
	$objResponse->loadCommands(listadoOrdenes(0,"",'ASC',$valBusq));
	
	return $objResponse;
}

function buscarProveedor($valFrmBuscarProveedor) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s",$valFrmBuscarProveedor['textCriterioProveed']);
	
	$objResponse->loadCommands(listProveedores(0, "", "", $valBusq));
		
	return $objResponse;
	
}

function buscarServicioMantenimiento($frmBuscarServicioMantenimiento, $frmDatosUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscarServicioMantenimiento['txtCriterioBuscarServicioMantenimiento'],
		$frmDatosUnidadFisica['txtIdUnidadFisica']);
	
	$objResponse->loadCommands(listaServicioMantenimiento(0, "id_servicio_mantenimiento", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarUnidadFisica($frmBuscarVehiculo, $frmDcto, $frmListaUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarVehiculo['txtCriterioBuscarUnidadFisica'],
		implode(",", $frmListaUnidadFisica['hddIdUnidadFisica']));
	
	$objResponse->loadCommands(listaUnidadFisica(0, "id_unidad_fisica", "ASC", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	//RECORRE LOS IVAS DEL DOCUMENTO PARA ELIMINARLOS EN CADA RECARGA
	if(isset($frmTotalDcto['hddIdIva'])){
		foreach($frmTotalDcto['hddIdIva'] as $indeceIdIva => $valorIdIva){
			$objResponse->script(sprintf("
			fila = document.getElementById('trIva:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
			$valorIdIva));
		}
	}

	//DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbx'];
	if (isset($arrayObj)) {
		foreach ($arrayObj as $indiceItm => $valorItm) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			
			$objResponse->assign("trItm:".$valorItm,"className",$clase." textoGris_11px");
			$objResponse->assign("tdNumItm:".$valorItm,"innerHTML",$i);
		}
		$hddObj = implode("|", $arrayObj);
	}
	
	if (isset($arrayObj))
		$objResponse->assign("hddObj","value",implode("|", $arrayObj));
	
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	$idMoneda = $frmDcto['hddIdMoneda'];
	$idMonedaFactura = ($frmDcto['hddIdMoneda'] == $frmDcto['lstMoneda']) ? $frmDcto['hddIdMoneda'] : $frmDcto['lstMoneda'];
	
	//VERIFICA SI LA FACTURA ES DE IMPORTACION
	$idModoCompra = ($idMoneda == $idMonedaFactura) ? 1 : 2; // 1 = Normal, 2 = Importacion
	$txtTasaCambio = str_replace(",","",$frmDcto['txtTasaCambio']);
	
	//BUSCA LOS DATOS DE LA MONEDA DE LA FACTURA
	$queryMoneda = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", valTpDato($idMonedaFactura, "int"));
	$rsMoneda = mysql_query($queryMoneda);
	if (!$rsMoneda) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMoneda = mysql_fetch_assoc($rsMoneda);
	
	//BUSCA LOS DATOS DE LA MONEDA NACIONAL
	$queryMonedaNacional = sprintf("SELECT * FROM pg_monedas WHERE idmoneda = %s;", valTpDato($idMoneda, "int"));
	$rsMonedaNacional = mysql_query($queryMonedaNacional);
	if (!$rsMonedaNacional) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowMonedaNacional = mysql_fetch_assoc($rsMonedaNacional);
	
	//VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IVA Y EL SUBTOTAL
	$subTotal = 0;
	$totalExento = 0;
	$totalExonerado = 0;
	$arrayIva = NULL;
	$arrayDetalleIva = NULL;
	
	//DEL DESCUENTO INDIVIDUAL SACA EL GENERAL
	//DESCUENTO POR ART
	if (isset($arrayObj)) {
		$totalArticulo = 0;
		$totalDescPorArt = 0;
		foreach($arrayObj as $indice => $valor) {
			$totalDescPorArt += $frmListaArticulo['hddMontoDescuentoArt'.$valor] * $frmListaArticulo['hddCantRecibArt'.$valor];			
			$totalArticulo += $frmListaArticulo['hddTotalArt'.$valor];
		}
		if($totalDescPorArt){
			$frmTotalDcto['txtDescuento'] = ($totalDescPorArt * 100) / $totalArticulo;
		} 
	}
	//DESCUENTO GENERAL
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			//DESCUNETO DEL DOCUMENTO
			$subTotalArt = str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
			if ($totalDescPorArt > 0) { //DESCUENTO DE ART
				$totalDescuentoArt = str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]);
			} else { //DESCUENTO GLOBAL
				$totalDescuentoArt = (str_replace(",","",$frmTotalDcto['txtDescuento']) * $subTotalArt) / 100;
			}
			$subTotalArt = ($subTotalArt - $totalDescuentoArt);
			//CALCULO DEL IVA Y EL EXENTO
		 	if ($frmListaArticulo['hddIdIvaArt'.$valor] == 0 || $frmListaArticulo['hddIdIvaArt'.$valor] == "") {//ART SIN IVA
				$totalExento += $subTotalArt;
			} else {//ART CON IVA
				//CORTO LA CADENA PARA SABER LOS ID E IVA DE CADA ITEMS
				$cadenaIdIva = explode("|",$frmListaArticulo['hddIdIvaArt'.$valor]);
				$cadenaIva = explode("|",$frmListaArticulo['hddIvaArt'.$valor]);
				$arrayIdIva = array();
				$arrayPorcIva = array();
				foreach($cadenaIdIva as $indiceCadena => $valorCadena){
					if($valorCadena != 0 && $valorCadena != ""){
						$arrayIdIva []= $valorCadena;
						$arrayPorcIva []= $cadenaIva[$indiceCadena];
					}
				}
				
				//RECORRE EL ARRAY DE LOS IVAS PARA ARMAR UN ARRAY CON EL DETALLE DE CADA IVA
				foreach($arrayIdIva as $indicesIdIva => $valorIdIva){
					$arrayDetalleIva[0] = $valorIdIva;
					$arrayDetalleIva[1] = $subTotalArt;
					$arrayDetalleIva[2] = ($subTotalArt * $arrayPorcIva[$indicesIdIva]) /100;//BASE IMPONIBLE IVA
					$arrayDetalleIva[3] = $arrayPorcIva[$indicesIdIva];
					
					$existe = false;
					foreach ($arrayIva as $indice => $arrayIvas){
						if($arrayIva[$indice][0] == $valorIdIva){ //PARA VERIFICAR ID IVA TOTAL AGREGADO
							$arrayIva[$indice][1] += $subTotalArt;
							$arrayIva[$indice][2] += ($subTotalArt * $arrayPorcIva[$indicesIdIva]) /100;//BASE IMPONIBLE IVA
							$existe = true;
						}
					}
//print_r($arrayIva);					
					if($existe == false){
						$arrayIva[] = $arrayDetalleIva;
					}
				}
			}
			
			$subTotalDescuentoArt += str_replace(",","",$frmListaArticulo['hddCantRecibArt'.$valor]) * str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]);
			
			$subTotal += str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
		}
	}

	//CALCULA LOS GASTOS DE CADA ARTICULO
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {//RECORRE LOS ITEM DE LA FACTURA
			$totGastosArt = 0;
			$totGastosImportNacionalArt = 0;
			$totGastosImportArt = 0;
			
			if (isset($frmListaArticulo['hddIdArt'.$valor])) {
				$hddTotalArt = str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
				$hddTotalDescuentoArt = str_replace(",","",$frmListaArticulo['hddMontoDescuentoArt'.$valor]);

				//GASTOS INCLUIDOS EN FACTURA, CALCULA LOS GASTO SEGUN EL TIPO DE GASTO
				if(isset($frmTotalDcto['hddItemsGasto'])){
					foreach($frmTotalDcto['hddItemsGasto'] as $indiceGasto => $valorGasto){//RECORRE LOS GASTOS
						if($frmTotalDcto['txtMontoGasto'.$valorGasto] > 0){
							//BUSCA LOS DATOS DEL GASTO
							$sqlGastosTipo = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s",
								valTpDato($frmTotalDcto['checkHddnGasto'.$valorGasto],"int"));
							$rsGastosTipo = mysql_query($sqlGastosTipo);
							if(!$rsGastosTipo)return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine_".__LINE__);
							$rowsGastoTipo = mysql_fetch_assoc($rsGastosTipo);	
							switch($rowsGastoTipo['id_modo_gasto']){
								case 1://1 = Nacional
									$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valorGasto]);
									$gastosArt = (($hddTotalArt - $hddTotalDescuentoArt) * $montoGasto) / $subTotal;
									
									$totGastosArt += round($gastosArt, 2);	
									break;
								case 3://3 = Nacional por Importacion
									$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valorGasto]);
									$gastosArt = ((($hddTotalArt - $hddTotalDescuentoArt) * $txtTasaCambio) * $montoGasto) / ($subTotal * $txtTasaCambio);
		
									$totGastosImportNacionalArt += round($gastosArt, 2);
									break;	
							}
						}
					}	
				}

				$frmListaArticulo['hddGastosArt'.$valor] = $totGastosArt;
				$objResponse->assign("hddGastosArt".$valor,"value",number_format($totGastosArt, 2, ".", ","));
				$objResponse->assign("hddGastosImportNacArt".$valor,"value",number_format($totGastosImportNacionalArt, 2, ".", ","));
				
				// GASTOS DE IMPORTACION
				/*if (isset($arrayObjGastoImport)) {
					foreach($arrayObjGastoImport as $indiceGastoImport => $valorGastoImport) {
						$montoGasto = str_replace(",","",$frmTotalDcto['hddSubTotalFacturaGasto'.$valorGastoImport]);
						$gastosImportArt = (($hddTotalArt - $hddTotalDescuentoArt) * $montoGasto) / $subTotal;
						
						$totGastosImportArt += round($gastosImportArt, 2);
					}
				}
				$objResponse->assign("hddGastosImportArt".$valor,"value",number_format($totGastosImportArt, 2, ".", ","));*/
			}
		}
	}
	

	//SACA LA CUENTA DE LOS GASTOS QUE LLEVAN Y NO LLEVAN IVA
	$gastosConIva = array();
	$gastosSinIva = array();
	if(isset($frmTotalDcto['hddItemsGasto'])){
		foreach($frmTotalDcto['hddItemsGasto'] as $indiceGasto => $valorGasto){//RECORRE LOS GASTOS
			switch($frmTotalDcto['hddTipoGasto'.$valorGasto]){
				case 0://SACA EL MONTO MEDIANTE EL PORCENTAJE
					$porcentaje = ($subTotal == 0) ? 0 : str_replace(",","",$frmTotalDcto['txtPorcMontoGasto'.$valorGasto]);
					$monto = ($subTotal == 0) ? 0 : $porcentaje * ($subTotal / 100);
					$objResponse->assign('txtMontoGasto'.$valorGasto,"value",number_format($monto, 2, ".", ","));
						break;
				case 1://SACA EL PORCENTAJE MEDIANTE EL MONTO
					$monto = ($subTotal == 0) ? 0 : str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valorGasto]);
					$porcentaje = ($subTotal == 0) ? 0 : $monto * (100 / $subTotal);
					$objResponse->assign('txtPorcMontoGasto'.$valorGasto,"value",number_format($porcentaje, 2, ".", ","));					
						break;
			}
			$monto =str_replace(",","",$monto);
			
			//BUSCA LOS DATOS DEL GASTO
			$sqlGastos = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s",
				valTpDato($frmTotalDcto['checkHddnGasto'.$valorGasto],"int"));
			$rsGastos = mysql_query($sqlGastos);
			if(!$rsGastos)return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine_".__LINE__);
			$rowsGasto = mysql_fetch_assoc($rsGastos);	

			//RECORE LOS IVAS DEL GASTO
			if($frmTotalDcto['txtMontoGasto'.$valorGasto] != 0){
				$cadenaIdIvaGasto = explode("|",$frmTotalDcto['hddIdIvaGasto'.$valorGasto]);
				$cadenaIvaGasto = explode("|",$frmTotalDcto['hddIvaGasto'.$valorGasto]);
				$arrayIdIvaGasto = array();
				$arrayIvaGasto = array();
				foreach($cadenaIdIvaGasto as $indiceIdIvaGasto => $valorIdIvaGasto){
					$arrayIdIvaGasto[] = $valorIdIvaGasto;
					$arrayIvaGasto[] = $cadenaIvaGasto[$indiceIdIvaGasto];
				}
				if($frmTotalDcto['hddEstatusIvaGasto'.$valorGasto] == 0 ){//GASTOS SIN IVA
					if($rowsGasto['id_modo_gasto'] == 1){//1 = Nacional
						$gastosSinIva[] = $monto;//ES EL MONTO DE CADA GASTO
					}
				}else{//GASTOS CON IVA
					foreach($arrayIdIvaGasto as $indiceIdIvaGasto => $valorIdIvaGasto){//RECORRE LOS ID IVA DEL GASTO
						$arrayDetalleIva[0] = $valorIdIvaGasto;
						$arrayDetalleIva[1] = $monto; 
						$arrayDetalleIva[2] = ($monto * ($arrayIvaGasto[$indiceIdIvaGasto] / 100));
						$arrayDetalleIva[3] = $arrayIvaGasto[$indiceIdIvaGasto];

						$existe = false;
							if(isset($arrayIva)){
							foreach($arrayIva as $indiceIvaGasto => $valorIvaGasto){//RECORRE ARRAIVA PARA VALIDAR LOS EXISTENTES
								if($arrayIva[$indiceIvaGasto][0] == $valorIdIvaGasto){
									$arrayIva[$indiceIvaGasto][1] += $monto; 
									$arrayIva[$indiceIvaGasto][2] += ($monto * ($arrayIvaGasto[$indiceIdIvaGasto] / 100));
									$existe = true;
								}
							}
						}
						if($existe == false){
							$arrayIva[] = $arrayDetalleIva;
						}
						
					}
					if ($rowsGasto['id_modo_gasto'] == 1) { //1 = Nacional
						$gastosConIva[] = $monto;//ES EL MONTO DE CADA GASTO
					}					
				}
		}
	}
			}

//CREA LOS ELEMENTOS DE IVA DEL DOCUMENTO
	if (isset($arrayIva)) { 
//print_r($arrayIva);
		foreach($arrayIva as $indiceIva => $valorIva) {
			$queryIva = sprintf("SELECT * FROM pg_iva WHERE idIva = %s", valTpDato($arrayIva[$indiceIva][0], "int"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$ivaArt = ($frmDcto['txtIdFactura'] != "") ? $arrayIva[$indiceIva][3] : $rowIva['iva'];
			
			
			if ($arrayIva[$indiceIva][2] > 0) {
				$baseRetencion = round($arrayIva[$indiceIva][1],2);
$txtSubTotalIva = ($frmTotalDcto['cbxRedondeoIvaLocal'.$arrayIva[$indiceIva][0]] > 0) ? round(doubleval($arrayIva[$indiceIva][2]), 2) : truncateFloat(doubleval($arrayIva[$indiceIva][2]), 2);
				
				// INSERTA EL ARTICULO SIN INJECT 
				$objResponse->script(sprintf("var elemento = '".
					"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"tdIva:%s\">".
							"<table width=\"%s\"><tr>".
								"<td align=\"left\" class=\"textoNegrita_10px\">Redondear</td>".
								"<td align=\"left\"><input type=\"checkbox\" id=\"cbxRedondeoIvaLocal%s\" name=\"cbxRedondeoIvaLocal%s\" %s value=\"1\"></td>".
								"<td width=\"%s\" align=\"right\">&nbsp;%s:</td>".
							"</tr></table>".
						"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva[]\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" readonly=\"readonly\" size=\"16\" style=\"text-align:right\" value=\"%s\"/> *</td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td></td>".
						"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" readonly=\"readonly\" size=\"16\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
				obj = byId('trIva:%s');
				if(obj == undefined){
					$('#trGastosSinIva').before(elemento);
				}
				byId('cbxRedondeoIvaLocal%s').onclick = function() {
					xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
				}",
				$arrayIva[$indiceIva][0],//trIva idIva
					$arrayIva[$indiceIva][0],
						'100%', //tdIva
							$arrayIva[$indiceIva][0],$arrayIva[$indiceIva][0],(($frmTotalDcto['cbxRedondeoIvaLocal'.$arrayIva[$indiceIva][0]] > 0) ? "checked=\"checked\"" : ""),//tdchek idIva
						'100%',utf8_encode($rowIva['observacion']),//td Observacion
					$arrayIva[$indiceIva][0], $arrayIva[$indiceIva][0],//hddIdIva
					$arrayIva[$indiceIva][0], $arrayIva[$indiceIva][0], number_format(round($arrayIva[$indiceIva][1],2), 2, ".", ","),//txtBaseImpIva
					$arrayIva[$indiceIva][0], $arrayIva[$indiceIva][0], $ivaArt, "%",//txtIva
					$arrayIva[$indiceIva][0], $arrayIva[$indiceIva][0],number_format($txtSubTotalIva, 2, ".", ","),//txtSubTotalIva
				$arrayIva[$indiceIva][0],//obj
				$arrayIva[$indiceIva][0]));//cbxRedondeoIvaLocal
			}
			
			$subTotalIva += doubleval($txtSubTotalIva);
		}
	}
	
	if ($subTotalDescuentoArt > 0) {//SI EXISTE DESCUENTO POR ART

		$porcDescuento = ($subTotalDescuentoArt * 100) / $subTotal;
		$subTotalDescuento = $subTotalDescuentoArt;
		
		$objResponse->script("
			byId('txtDescuento').readOnly = true;
			byId('txtDescuento').className = 'inputInicial'");
							  
		$objResponse->assign("txtDescuento","value",number_format($porcDescuento, 2, ".", ","));
	} else {//SI NO EXISTE DESCUENTO POR ART
		
		$porcDescuento = str_replace(",","",$frmTotalDcto['txtDescuento']);
		$objResponse->script("
			byId('txtDescuento').readOnly = false;
			byId('txtDescuento').className = 'inputHabilitado'");
	}

	$subTotalDescuento = $subTotal * ($porcDescuento / 100);
	$txtTotalOrden = doubleval($subTotal) - doubleval($subTotalDescuento);

	if ($idModoCompra == 1) { // 1 = Normal
		$txtTotalOrden += doubleval($subTotalIva) +  doubleval(array_sum($gastosConIva));
	} else if ($idModoCompra == 2) { // 2 = Importacion
		$txtTotalOrden += 0;
	}
	
	$txtTotalOrden += doubleval(array_sum($gastosSinIva));

	$objResponse->assign("txtSubTotal","value",number_format($subTotal, 2, ".", ","));
	$objResponse->assign("txtSubTotalDescuento","value", number_format($subTotalDescuento, 2, ".", ","));
	$objResponse->assign("txtTotalOrden","value",number_format($txtTotalOrden, 2, ".", ","));
	
	$objResponse->assign('txtGastosConIva',"value",number_format(array_sum($gastosConIva), 2, ".", ","));
	$objResponse->assign('txtGastosSinIva',"value",number_format(array_sum($gastosSinIva), 2, ".", ","));
	
	$objResponse->assign("txtTotalExento","value",number_format(($totalExento + array_sum($gastosSinIva)), 2, ".", ","));
	$objResponse->assign("txtTotalExonerado","value",number_format($totalExonerado, 2, ".", ","));
	
	// BUSCA LOS DATOS DE LA RETENCION
	$queryRetencionISLR = sprintf("SELECT * FROM te_retenciones WHERE id = %s;",
		valTpDato($frmTotalDcto['lstRetencionISLR'], "int"));
	$rsRetencionISLR = mysql_query($queryRetencionISLR);
	if (!$rsRetencionISLR) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$totalRowsRetencionISLR = mysql_num_rows($rsRetencionISLR);
	$rowRetencionISLR = mysql_fetch_assoc($rsRetencionISLR);
	
	if ($txtTotalOrden > 0) {
		$objResponse->script("
		byId('trRetencionISLR').style.display = '';
		byId('trBaseImponibleISLR').style.display = '';
		byId('trMontoMayorISLR').style.display = '';");
		
		if ($rowRetencionISLR['porcentaje'] == 0) {
			$objResponse->script("
			byId('trBaseImponibleISLR').style.display = 'none';
			byId('trMontoMayorISLR').style.display = 'none';");
			
			$txtBaseImpISLR = 0;
			$txtTotalRetencionISLR = 0;
		} else {
			$txtBaseImpISLR = str_replace(",","",$frmTotalDcto['txtBaseRetencionISLR']);
			if ($txtBaseImpISLR == 0) {
				$txtBaseImpISLR = $baseRetencion;
			}
		}
		
		if ($rowRetencionISLR['importe'] == 0 || ($rowRetencionISLR['importe'] > 0 && $txtTotalOrden > $rowRetencionISLR['importe'])) {
			$txtTotalRetencionISLR = $txtBaseImpISLR * ($rowRetencionISLR['porcentaje'] / 100) - $rowRetencionISLR['sustraendo'];
		} else {
			$txtBaseImpISLR = 0;
			$txtTotalRetencionISLR = 0;
			$objResponse->alert(utf8_encode("La retención seleccionada no aplica para este registro de compra"));
		}
	} else {
		$objResponse->script("
		byId('trRetencionISLR').style.display = 'none';
		byId('trBaseImponibleISLR').style.display = 'none';
		byId('trMontoMayorISLR').style.display = 'none';");
		
		$txtBaseImpISLR = 0;
		$txtTotalRetencionISLR = 0;
	}
	
	$objResponse->assign("txtPorcentajeISLR","value",number_format($rowRetencionISLR['porcentaje'], 2, ".", ","));
	$objResponse->assign("txtMontoMayorISLR","value",number_format($rowRetencionISLR['importe'], 2, ".", ","));
	$objResponse->assign("txtSustraendoISLR","value",number_format($rowRetencionISLR['sustraendo'], 2, ".", ","));
	$objResponse->assign("txtBaseRetencionISLR","value",number_format($txtBaseImpISLR, 2, ".", ","));
	$objResponse->assign("txtTotalMontoRetencionISLR","value",number_format($txtTotalRetencionISLR, 2, ".", ","));

	return $objResponse;
}

function calcularImpuesto($frmDatosArticulo){
	$objResponse = new xajaxResponse();
	//$totalIva = array();
	if(isset($frmDatosArticulo['cbxItmImpuesto2'])){
		foreach($frmDatosArticulo['cbxItmImpuesto2'] as $indiceItm => $valorItm){
			$totalIva [] =  $frmDatosArticulo['textItmImpuestoIva'.$valorItm];
		}
	}
	
	$objResponse->assign("textTotaIva","value",number_format(array_sum($totalIva), 2, ".", ",")."%");
	
	return $objResponse;
}

function calcularMontoGastos($frmTotalDoc){
	$objResponse = new xajaxResponse();

	if(isset($frmTotalDoc['checkHddnGasto'])){
		foreach($frmTotalDoc['checkHddnGasto'] as $indiceItm => $valorItm){
			$totalMonto [] =  str_replace(",","", $frmTotalDoc['txtMontoGasto'.$valorItm]);
		}
	}
	
	$objResponse->assign("txtTotalGasto","value",number_format(array_sum($totalMonto), 2, ".", ","));

	return $objResponse;
}

function calcularMontoRetencionISLR($frmTotalDcto){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM te_retenciones WHERE id = %s",
		valTpDato($frmTotalDcto['lstRetencionISLR'],"int"));
	$rs = mysql_query($query);
	$rows = mysql_fetch_array($rs);

	if(str_replace(",","", $frmTotalDcto['txtBaseRetencionISLR']) >= $rows['importe']){
		$baseRetencion = str_replace(",","", $frmTotalDcto['txtBaseRetencionISLR']);
		$RetencionISLR = (($baseRetencion * $rows['porcentaje']) / 100);

		$totalRetencionISLR =  ($rows['sustraendo'] != 0) ? $RetencionISLR - $rows['sustraendo'] : $RetencionISLR;
	}

	$objResponse->assign("txtTotalMontoRetencionISLR","value",number_format($totalRetencionISLR, 2, ".", ","));
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}


function cargaLst($tpLst, $idLstOrigen, $adjLst, $padreId, $nivReg, $selId){
	$objResponse = new xajaxResponse();
	
	switch ($tpLst) {
		case "almacenes" : 	$arraySelec = array("lstPadre","lstAlmacen","lstCalle","lstEstante","lstTramo","lstCasilla"); break;
	}
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	if (($posList+1) != count($arraySelec)-1)
		$onChange = "onchange=\"xajax_cargaLst('".$tpLst."', '".$arraySelec[$posList+1]."', '".$adjLst."', this.value, 'null', 'null');\"";
	else if (($posList+1) == count($arraySelec)-1)
		$onChange = "onchange=\"xajax_listadoArticulosAlmacen(xajax.getFormValues('frmAlmacen'));\"";
	
	$html = "<select id=\"".$arraySelec[$posList+1].$adjLst."\" name=\"".$arraySelec[$posList+1].$adjLst."\" ".$onChange.">";
	
	if ($padreId == '-1') {
		foreach ($arraySelec as $indice=>$valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\">";
					$html .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html .= "</select>";
				$objResponse->assign("td".$valor.$adjLst, 'innerHTML', $html);
			}
		}
		
		$objResponse->script("xajax_listadoArticulosAlmacen(xajax.getFormValues('frmAlmacen'));");
		
		return $objResponse;
	} else {
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
		foreach ($arraySelec as $indice=>$valor) {
			if ($indice > $posList) {
				$html2 = "<select id=\"".$valor.$adjLst."\" name=\"".$valor.$adjLst."\">";
					$html2 .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html2 .= "</select>";
				$objResponse->assign("td".$valor.$adjLst, 'innerHTML', $html2);
			}
		}
		
		switch ($posList) {
			case 0 : $query = sprintf("SELECT * FROM ga_almacenes WHERE estatus = 1 AND id_empresa = %s ORDER BY descripcion", valTpDato($padreId, "int"));
				$campoId = "id_almacen";
				$campoDesc = "descripcion"; break;
			case 1 : $query = sprintf("SELECT * FROM ga_calles WHERE id_almacen = %s ORDER BY descripcion_calle", valTpDato($padreId, "int"));
				$campoId = "id_calle";
				$campoDesc = "descripcion_calle";  break;
			case 2 : $query = sprintf("SELECT * FROM ga_estantes WHERE id_calle = %s ORDER BY descripcion_estante", valTpDato($padreId, "int"));
				$campoId = "id_estante";
				$campoDesc = "descripcion_estante";  break;
			case 3 : $query = sprintf("SELECT * FROM ga_tramos WHERE id_estante = %s ORDER BY descripcion_tramo", valTpDato($padreId, "int"));
				$campoId = "id_tramo";
				$campoDesc = "descripcion_tramo";  break;
			case 4 : $query = sprintf("SELECT * FROM ga_casillas WHERE id_tramo = %s ORDER BY descripcion_casilla", valTpDato($padreId, "int"));
				$campoId = "id_casilla";
				$campoDesc = "descripcion_casilla";  break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		
		while ($row = mysql_fetch_array($rs)) {
			$seleccion = ($selId == $row[$campoId]) ? "selected='selected'" : "";
			
			$html .= "<option value=\"".$row[$campoId]."\" ".$seleccion.">".utf8_encode($row[$campoDesc])."</option>";
		}
	}
	$html .= "</select>";
	
	$objResponse->assign("td".$arraySelec[$posList+1].$adjLst, 'innerHTML', $html);
	
	$objResponse->script("xajax_listadoArticulosAlmacen(xajax.getFormValues('frmAlmacen'));");
	
	return $objResponse;
}

function cargaLstEmpresa($idEmpresa, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
		valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s 
			AND (id_empresa_reg = %s OR id_empresa_padre_suc = %s) 
		ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($rowEmpresa['id_empresa_padre_suc'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" onchange=\"xajax_cargaLst('almacenes', 'lstPadre', 'Act', this.value, 'null', 'null'); xajax_listadoArticulosAlmacen(xajax.getFormValues('frmAlmacen'));\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
		
		$selected = ($selId == $row['id_empresa_reg'] || $idEmpresa == $row['id_empresa_reg']) ? "selected='selected'" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoMovimiento(){
	$objResponse = new xajaxResponse();	
	
	$query = "SELECT DISTINCT tipo,
				(CASE tipo
				WHEN 1 THEN 'COMPRA'
				WHEN 2 THEN 'ENTRADA'
				WHEN 3 THEN 'VENTA'
				WHEN 4 THEN 'SALIDA'
				END) AS tipo_movimiento
			FROM pg_clave_movimiento 
				WHERE id_modulo IN (3) AND tipo IN (1,2)
			ORDER BY tipo";
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<select id=\"lstTipoClave\" name=\"lstTipoClave\" onchange=\"selectedOption(this.id,1); \">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($row['tipo'] == 1) ? "selected='selected'" : "";
		
		$html .= "<option ".$selected." value=\"".$row['tipo']."\">".utf8_encode($row['tipo_movimiento'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoClave","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstClaveMovimiento($idTipoClave, $idModulo = 3, $selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE tipo = %s 
						AND id_modulo = %s ORDER BY descripcion",
				valTpDato($idTipoClave, "int"),valTpDato($idModulo, "int"));

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstClaveMovimiento\" name=\"lstClaveMovimiento\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['tipo']) ? "selected='selected'" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_clave_movimiento']."\">".utf8_encode($row['descripcion'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstClaveMovimiento","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstTipoArt() {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM ga_tipos_articulos");

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTipoArticuloBus\" name=\"lstTipoArticuloBus\" class=\"inputHabilitado\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		
		$html .= "<option value=\"".$row['id_tipo_articulo']."\">".utf8_encode($row['descripcion'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdTipoArticulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstRetencionImpuesto($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_iva iva
	WHERE tipo = 5
		AND estado = 1
	ORDER BY iva");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstRetencionImpuesto\" name=\"lstRetencionImpuesto\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		$html .= "<option ".(($selId == 0 && strlen($selId) > 0) ? "selected=\"selected\"" : "")." value=\"0\">".("Sin Retención")."</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['iva']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['iva']."\">".utf8_encode($row['observacion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstRetencionImpuesto","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstRetencionISLR($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM te_retenciones WHERE activo = 1");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstRetencionISLR\" name=\"lstRetencionISLR\" class=\"inputHabilitado\" onchange=\"xajax_calcularMontoRetencionISLR(xajax.getFormValues('frmTotalDcto'))\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id']) ? "selected=\"selected\"" : "";
		$html .= "<option ".$selected." value=\"".$row['id']."\">".utf8_encode($row['descripcion'])."  ".number_format($row['porcentaje'], 2, ".", ",")."%"."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstRetencionISLR","innerHTML",$html);
	
	return $objResponse;
}



function cargaLstUbicacion($tpLst, $adjLst, $idEmpresa, $idAlmacen, $idCalle, $idEstante, $idTramo, $idCasilla) {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM ga_almacenes WHERE estatus = 1 AND id_empresa = %s ORDER BY descripcion", valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAlmacen".$adjLst."\" name=\"lstAlmacen".$adjLst."\" onchange=\"xajax_cargaLst('".$tpLst."', 'lstAlmacen', '".$adjLst."', this.value, 'null', 'null');\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$seleccion = ($idAlmacen == $row['id_almacen']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_almacen']."\" ".$seleccion.">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAlmacen".$adjLst,"innerHTML",$html);
	
	
	$query = sprintf("SELECT * FROM ga_calles WHERE id_almacen = %s ORDER BY descripcion_calle", valTpDato($idAlmacen, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstCalle".$adjLst."\" name=\"lstCalle".$adjLst."\"  onchange=\"xajax_cargaLst('".$tpLst."', 'lstCalle', '".$adjLst."', this.value, 'null', 'null');\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$seleccion = ($idCalle == $row['id_calle']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_calle']."\" ".$seleccion.">".utf8_encode($row['descripcion_calle'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCalle".$adjLst,"innerHTML",$html);
	
	
	$query = sprintf("SELECT * FROM ga_estantes WHERE id_calle = %s ORDER BY descripcion_estante", valTpDato($idCalle, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstEstante".$adjLst."\" name=\"lstEstante".$adjLst."\" onchange=\"xajax_cargaLst('".$tpLst."', 'lstEstante', '".$adjLst."', this.value, 'null', 'null');\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$seleccion = ($idEstante == $row['id_estante']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_estante']."\" ".$seleccion.">".utf8_encode($row['descripcion_estante'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstante".$adjLst,"innerHTML",$html);
	
	
	$query = sprintf("SELECT * FROM ga_tramos WHERE id_estante = %s ORDER BY descripcion_tramo", valTpDato($idEstante, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstTramo".$adjLst."\" name=\"lstTramo".$adjLst."\" onchange=\"xajax_cargaLst('".$tpLst."', 'lstTramo', '".$adjLst."', this.value, 'null', 'null');\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$seleccion = ($idTramo == $row['id_tramo']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_tramo']."\" ".$seleccion.">".utf8_encode($row['descripcion_tramo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTramo".$adjLst,"innerHTML",$html);
	
	
	$query = sprintf("SELECT * FROM ga_casillas WHERE id_tramo = %s ORDER BY descripcion_casilla", valTpDato($idTramo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstCasilla".$adjLst."\" name=\"lstCasilla".$adjLst."\" onchange=\"xajax_listadoArticulosAlmacen(xajax.getFormValues('frmAlmacen'));\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_array($rs)) {
		$seleccion = ($idCasilla == $row['id_casilla']) ? "selected='selected'" : "";
		
		$html .= "<option value=\"".$row['id_casilla']."\" ".$seleccion.">".utf8_encode($row['descripcion_casilla'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCasilla".$adjLst,"innerHTML",$html);
	
	$objResponse->script("xajax_listadoArticulosAlmacen(xajax.getFormValues('frmAlmacen'));");
	
	return $objResponse;
}

function cargarDcto($idOrden, $frmListaArticulo, $frmTotalDcto) {

	$objResponse = new xajaxResponse();
	
	$objResponse->script("habilitar('btnAgregarProv', 'btnAgregarProv', 'hide');"); // PARA HABILITAR EL BOTTON DE PROVEEDORES
	$btn ="<a class=\"modalImg\" id=\"AgregarArtSol\" rel=\"#divFlotante7\" onclick=\"abrirDivFlotante('Mostrarsolicitud',this);\">
			<button type=\"button\" id=\"btnAgregarArtSol\" name=\"btnAgregarArtSol\" style=\"cursor:default\" title=\"Agregar\">
				<table align=\"center\" cellpadding=\"0\" cellspacing=\"0\">
					<tr><td>&nbsp;</td><td><img src=\"../img/iconos/add.png\"/></td><td>&nbsp;</td><td>Agregar</td></tr>
				</table>
			</button>
		</a>";
	$objResponse->assign("divBtnAgregar","innerHTML",$btn);
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($frmListaArticulo['cbx'])) {
		foreach($frmListaArticulo['cbx'] as $indiceItm => $valorItm) {
			$arrayObj[] = $valorItm;
		}
	}
	
	// ELIMINA LOS DETALLES DEL PEDIDO QUE SE CARGARON EN PANTALLA
	if (isset($arrayObj)) {
		foreach($arrayObj as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
		}
	}
	
	$objResponse->script("
	document.forms['frmDcto'].reset();
	document.forms['frmTotalDcto'].reset();");
	
	$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat));
	
	$queryOrden = sprintf("SELECT vw_ga_orden_comp.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		vw_iv_emp_suc.contribuyente_especial
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		INNER JOIN vw_ga_ordenes_compra vw_ga_orden_comp ON (vw_iv_emp_suc.id_empresa_reg = vw_ga_orden_comp.id_empresa)
	WHERE id_orden_compra = %s;",
		valTpDato($idOrden, "int"));
	$rsOrden = mysql_query($queryOrden);
	if (!$rsOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowOrden = mysql_fetch_assoc($rsOrden);
	
	$queryOrdenDet = sprintf("SELECT ga_orden_compra_detalle.id_orden_compra_detalle, id_orden_compra, id_articulo, cantidad, pendiente, precio_unitario,
				(SELECT GROUP_CONCAT(id_iva SEPARATOR '|') AS idIva
					FROM ga_orden_compra_detalle_iva
						WHERE ga_orden_compra_detalle_iva.id_orden_compra_detalle = ga_orden_compra_detalle.id_orden_compra_detalle
					GROUP BY id_orden_compra_detalle) AS id_iva,
				(SELECT GROUP_CONCAT(FORMAT(iva, 2) SEPARATOR '|') AS iva
					FROM ga_orden_compra_detalle_iva
						WHERE ga_orden_compra_detalle_iva.id_orden_compra_detalle = ga_orden_compra_detalle.id_orden_compra_detalle
					GROUP BY id_orden_compra_detalle) AS iva, tipo,id_cliente,estatus
				FROM ga_orden_compra_detalle
			WHERE id_orden_compra = %s  AND pendiente != 0",
		valTpDato($rowOrden['id_orden_compra'], "int"));
	$rsOrdenDet = mysql_query($queryOrdenDet);
	if (!$rsOrdenDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayObj = NULL;
	while ($rowOrdenDet = mysql_fetch_assoc($rsOrdenDet)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		//BUSCA LOS DATOS DEL IVA
		$queryIva = sprintf("SELECT * FROM pg_iva WHERE idIva = %s;", valTpDato($rowOrdenDet['id_iva'], "int"));
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowIva = mysql_fetch_assoc($rsIva);
		
		$idArticulo = $rowOrdenDet['id_articulo'];
		$cantPedida = $rowOrdenDet['cantidad'];
		$cantRecibida = $rowOrdenDet['pendiente'];
		$costoUnitario = $rowOrdenDet['precio_unitario'];
		
		$idIvaArt =($rowOrdenDet['id_iva'] != "" && $rowOrdenDet['id_iva'] != "0") ? $rowOrdenDet['id_iva'] : 0; 
		$porcIvaArt = ($rowOrdenDet['iva'] != "" && $rowOrdenDet['iva'] != "0") ? $rowOrdenDet['iva'] : 0;
		
		$hddEstatusIvaArt = ($rowOrdenDet['id_iva'] != "" && $rowOrdenDet['id_iva'] != "0") ? 1 : 0;
		$lujoIva = ($rowIva['tipo'] == 2 || $rowIva['tipo'] == 3) ? "1" : "";
		
		if($cantPedida != $cantRecibida){
			$cantPendiente = ($cantRecibida - $rowOrdenDet['pendiente']);	
		} else {
			$cantPendiente = ($cantPedida - $cantRecibida);
		}
		
		$queryArtEmp = sprintf("SELECT * FROM ga_articulos_empresa
		WHERE id_empresa = %s
			AND id_articulo = %s;",
			valTpDato($rowOrden['id_empresa_reg'], "int"),
			valTpDato($idArticulo, "int"));
		$rsArtEmp = mysql_query($queryArtEmp);
		if (!$rsArtEmp) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
		
		$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s;",
			valTpDato($idArticulo, "int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		$objResponse->script(sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbx\" name=\"cbx[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td><a class=\"modalImg\" id=\"aEditarItem:%s\" rel=\"#divFlotante\" >".
				"<button type=\"button\" id=\"btnEditar%s\" name=\"btnEditar%s\" title=\"Editar\"><img class=\"puntero\" src=\"../img/iconos/pencil.png\"/></button>".
			"</a></td>".
			"<td class=\"%s\" style=\"display:none\"><table><tr><td>".
				"<a class=\"modalImg\" id=\"aAlmacenItem:%s\" rel=\"#divFlotante\"><img class=\"puntero\" src=\"../img/iconos/ico_transferir_para_almacen.gif\" title=\"%s\"/>".
			"</td><td id=\"spanUbicacion:%s\" align=\"center\" nowrap=\"nowrap\" width=\"%s\" title=\"spanUbicacion:%s\">%s</td></tr></table></td>".
			"<td id=\"tdCodArt:%s\" class=\"textoNegrita_9px\" align=\"center\" >%s</td>".
			"<td><div id=\"tdDescArt:%s\">%s</div>%s</td>".
			"<td><input type=\"text\" id=\"hddCantArt%s\" name=\"hddCantArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"hddCantRecibArt%s\" name=\"hddCantRecibArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td><input type=\"text\" id=\"hddCantPend%s\" name=\"hddCantPend%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/></td>".
		"<td><input type=\"text\" id=\"hddCostoArt%s\" name=\"hddCostoArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddPorcentageDescuentoArt%s\" name=\"hddPorcentageDescuentoArt%s\" readonly=\"readonly\" value=\"%s\"/>".
		"<input type=\"hidden\" id=\"hddMontoDescuentoArt%s\" name=\"hddMontoDescuentoArt%s\" readonly=\"readonly\" value=\"%s\"/></td>".
			"<td id=\"tdIvaArt%s\"><div id=\"divTotalIvaArt%s\" style=\"white-space:nowrap\" align=\"center\"></div>".
				"<input type=\"hidden\" id=\"hddIvaArt%s\" name=\"hddIvaArt%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdIvaArt%s\" name=\"hddIdIvaArt%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddEstatusIvaArt%s\" name=\"hddEstatusIvaArt%s\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"hddTotalArt%s\" name=\"hddTotalArt%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdArt%s\" name=\"hddIdArt%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddGastosArt%s\" name=\"hddGastosArt%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddTipoArt%s\" name=\"hddTipoArt%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdClienteArt%s\" name=\"hddIdClienteArt%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdPedDetArt%s\" name=\"hddIdPedDetArt%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCasilla%s\" name=\"hddIdCasilla%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddLujoIva%s\" name=\"hddLujoIva%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aEditarItem:%s').onclick = function() {
			abrirDivFlotante('editar',this); 
			xajax_asignarArticulo('%s', xajax.getFormValues('frmListaArticulo'),'editarArt');
		}
		byId('aAlmacenItem:%s').onclick = function() {
			openImg(this); xajax_formAlmacen('%s', xajax.getFormValues('frmListaArticulo'));
		}",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			$contFila, $contFila,
			$contFila,
			$contFila,$contFila,
			$claseAlmacen,
				$contFila, ("Ubicación"),
				$contFila, "100%", $contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",utf8_encode(str_replace("-[]", "", $ubicacion)))),
			$contFila, elimCaracter(utf8_encode($rowArticulo['codigo_articulo']), ";"),
			$contFila, preg_replace("/[\"?]/","''",preg_replace("/[\r?|\n?]/","<br>",sanear_string($rowArticulo['descripcion']))), $arancelArticulo,
			$contFila, $contFila, number_format($cantPedida, 2, ".", ","),
			$contFila, $contFila, number_format($cantRecibida, 2, ".", ","),
		/**/$contFila,$contFila, number_format($cantPendiente, 2, ".", ","),
			$contFila, $contFila, number_format($costoUnitario, 2, ".", ","),
				$contFila, $contFila, number_format(0, 2, ".", ","),
				$contFila, $contFila, number_format(0, 2, ".", ","),
			$contFila,$contFila, //totalIva
				$contFila, $contFila,$porcIvaArt,
				$contFila, $contFila, $idIvaArt,
				$contFila, $contFila, $hddEstatusIvaArt,
			$contFila, $contFila, number_format(($cantRecibida * $costoUnitario), 2, ".", ","),
				$contFila, $contFila, $idArticulo,
				$contFila, $contFila, number_format($cantRecibida * $rowPedidoDet['gasto_unitario'], 2, ".", ","),
				$contFila, $contFila, $rowOrdenDet['tipo'],
				$contFila, $contFila, $rowOrdenDet['id_cliente'],
				$contFila, $contFila, $rowOrdenDet['id_orden_compra_detalle'],
				$contFila, $contFila, $idCasilla,
				$contFila, $contFila, $lujoIva,
		$contFila,
			$contFila,
		$contFila,
			$contFila));
	
		$arraIdIva = explode("|",$rowOrdenDet['id_iva']);
		$arraIva = explode("|",$rowOrdenDet['iva']);
		$tblIva ="";
		foreach($arraIdIva as $indiceIdIva => $valorIdIva){//RECORRE LOS IVAS 
			$valorIva = $arraIva[$indiceIdIva];
			if($valorIdIva != "" || $valorIdIva != NULL || $valorIdIva != 0){
				$tblIva .=sprintf("<table cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
								<tr>
									<td>%s</td>
									<td>%s</td>
								</tr>
							</table>",$valorIva,"%");
			}else{
				$tblIva = "N/A";
				$objResponse->script(sprintf("byId('tdIvaArt'+%s).className = 'divMsjInfo';",$contFila));
			}		
			$objResponse->assign("divTotalIvaArt".$contFila,"innerHTML",$tblIva);	
		}
		$arrayObj[] = $contFila;
	}

	// INSERTA EL GASTO EN CERO "0"
	$Result1 = insertarItemGastos(0,"");
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
	}
	
	$objResponse->assign("txtIdEmpresa","value",utf8_encode($rowOrden['id_empresa']));
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowOrden['nombre_empresa']));
	
	if ($rowOrden['contribuyente_especial'] == 1) {
		$objResponse->loadCommands(cargaLstRetencionImpuesto());
		$objResponse->script("byId('trRetencionIva').style.display = '';");
	} else {
		$objResponse->loadCommands(cargaLstRetencionImpuesto(0));
		$objResponse->script("byId('trRetencionIva').style.display = 'none';");
	}
	
	$objResponse->loadCommands(cargaLstRetencionISLR());	
	
	// DATOS DE LA ORDEN DE COMPRA
	$objResponse->assign("txtIdOrdenCompra","value",utf8_encode($rowOrden['id_orden_compra']));
	$objResponse->assign("txtFechaOrden","value",date(spanDateFormat,strtotime($rowOrden['fecha_orden'])));
	if ($rowOrden['tipo_pago'] == 0)
		$objResponse->assign("rbtTipoPagoCredito","checked","true");
	else if ($rowOrden['tipo_pago'] == 1)
		$objResponse->assign("rbtTipoPagoContado","checked","true");
	
	// DATOS DEL PEDIDO DE COMPRA
	$objResponse->assign("txtIdPedido","value",utf8_encode($rowOrden['id_solicitud_compra']));
	
	$objResponse->loadCommands(asignarProveedor($rowOrden['id_proveedor'],"Prov"));
	
	$objResponse->assign("txtFechaPedido","value",date(spanDateFormat,strtotime($rowOrden['fecha_solicitud'])));
	$objResponse->assign("hddIdEmpleado","value",$rowOrden['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowOrden['nombre_empleado']));
	$objResponse->assign("txtNumeroPedidoPropio","value",$rowOrden['id_pedido_compra_propio']);
	$objResponse->assign("txtNumeroReferencia","value",$rowOrden['id_pedido_compra_referencia']);
	$objResponse->assign("txtDescuento","value",$rowOrden['porcentaje_descuento']);
	$objResponse->assign("txtObservacionFactura","value",utf8_encode($rowOrden['observaciones']));
	$objResponse->loadCommands(cargaLstClaveMovimiento('1', '3', ''));
	$objResponse->loadCommands(cargaLstTipoMovimiento(""));
	
	
	$objResponse->script("
	byId('txtNumeroFacturaProveedor').readOnly = false;
	byId('txtNumeroControl').readOnly = false;
	byId('txtFechaProveedor').readOnly = false;
	byId('imgFechaProveedor').style.visibility = '';
	
	byId('lstClaveMovimiento').disabled = false;
	
	byId('rbtTipoPagoCredito').disabled = false;
	byId('rbtTipoPagoContado').disabled = false;
	byId('txtObservacionFactura').readOnly = false;
	
	byId('btnEliminarArt').disabled = false;
	
	byId('btnGuardar').disabled = false;");
	
	$objResponse->script("
		xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));
		abrirDivFlotante('totalFactura', divFlotante8);
		");
	
	return $objResponse;
}

function editarArticulo($valForm, $valFormDoct) {
	$objResponse = new xajaxResponse();
	// 	IDENTIFICA EL ARTICULO EN EL LISTADO
	$hddNumeroArt = $valForm['hddNumeroArt']; 

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo_articulo = %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if($_GET['id'] != ""){
		$sqlBusq .=sprintf(" AND id_orden_compra = %s",
			$_GET['id']);	
	}
	
	//CONSULTA EL ARTICULO PARA SABER CUANTOS QUEDAN PENDIENTE
	$queryArt = sprintf("SELECT ga_articulos.id_articulo, codigo_articulo, pendiente,id_orden_compra  
							FROM ga_articulos
						LEFT JOIN ga_orden_compra_detalle ON ga_orden_compra_detalle.id_articulo = ga_articulos.id_articulo
							WHERE ga_articulos.id_articulo = %s %s",
				$valForm['hddIdArt'],
				$sqlBusq);
	$rsqueryArt= mysql_query($queryArt);
	if(!$rsqueryArt) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowsArt = mysql_fetch_array($rsqueryArt);
	
	$txtCantidadArt = str_replace(",","",$valForm['txtCantidadArt']);
	$txtCantidadRecibArt = str_replace(",","",$valForm['txtCantidadRecibArt']);
	$txtCostoArt = str_replace(",","",$valForm['txtCostoArt']);	
	
	if($txtCantidadArt != $txtCantidadRecibArt){
		$cantPendiente = (abs($txtCantidadRecibArt - $rowsArt['pendiente']));	
	} else {
		$cantPendiente = ($txtCantidadArt - $txtCantidadRecibArt);
	}
	
	$objResponse->assign("hddCantRecibArt".$hddNumeroArt,"value",number_format($txtCantidadRecibArt, 2, ".", ","));//
	$objResponse->assign("hddCantPend".$hddNumeroArt,"value",number_format($cantPendiente, 2, ".", ","));
	$objResponse->assign("hddCostoArt".$hddNumeroArt,"value",number_format($txtCostoArt, 2, ".", ","));
	$objResponse->assign("hddTotalArt".$hddNumeroArt,"value",number_format(($txtCantidadRecibArt * $txtCostoArt), 2, ".", ","));
	
	// RECORRE LOS IVAS DEL ART
	if (isset($valForm['cbxItmImpuesto2'])) {
		$objResponse->script(sprintf("
			if(byId('tdIvaArt'+%s).className == 'divMsjInfo'){
				$('#tdIvaArt'+%s).removeClass('divMsjInfo');
			}",
		$hddNumeroArt,$hddNumeroArt));
		foreach($valForm['cbxItmImpuesto2'] as $indiceItm => $valorItm) {
			if($valForm['textItmImpuestoId'.$valorItm] != 0){
				$queryIva = sprintf("SELECT * FROM pg_iva WHERE idIva = %s", valTpDato($valForm['textItmImpuestoId'.$valorItm], "int"));
				$rsIva = mysql_query($queryIva);
				if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$rowIva = mysql_fetch_assoc($rsIva);	
				
				$arrayIdIva []= $rowIva['idIva'];
				$arrayIva []= $rowIva['iva'];
				
				$arrayIdIvaItem =  implode("|",$arrayIdIva) ;
				$arrayIvaItem =  implode("|",$arrayIva);
				$tblIva ="";
				foreach($arrayIva as $indiceIva => $valorIva){// INSERTA EL / LOS IVAS DEL ART
					if($valorIva != 0 || $valorIva != ""){
						$tblIva .=sprintf("<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
							<tr><td>%s</td><td>%s</td></tr></table>",
						$valorIva,"%");
					}else{
						$tblIva ="N/A";
						$objResponse->script(sprintf("byId('tdIvaArt'+%s).className = 'divMsjInfo';",$hddNumeroArt));
					}
				$objResponse->assign("divTotalIvaArt".$hddNumeroArt,"innerHTML",$tblIva);
				}			
			}else{
				$arrayIdIvaItem =  0;
				$arrayIvaItem =  0;
				$objResponse->assign("divTotalIvaArt".$hddNumeroArt,"innerHTML","N/A");
				$objResponse->script(sprintf("byId('tdIvaArt'+%s).className = 'divMsjInfo';",$hddNumeroArt));
			}
		}
	}else{
		$arrayIdIvaItem =  0;
		$arrayIvaItem =  0;
		$objResponse->assign("divTotalIvaArt".$hddNumeroArt,"innerHTML","N/A");
		$objResponse->script(sprintf("byId('tdIvaArt'+%s).className = 'divMsjInfo';",$hddNumeroArt));
	}

	$objResponse->assign("hddIdIvaArt".$hddNumeroArt,"value",$arrayIdIvaItem);
	$objResponse->assign("hddIvaArt".$hddNumeroArt,"value",$arrayIvaItem);
	
	$objResponse->assign("hddTipoArt".$hddNumeroArt,"value",$valForm['rbtTipoArt']);
	$objResponse->assign("hddIdClienteArt".$hddNumeroArt,"value",$valForm['txtIdClienteArt']);
	$objResponse->assign("hddPorcentageDescuentoArt".$hddNumeroArt,"value",$valForm['txtPorcDescuentoArt']);
	$objResponse->assign("hddMontoDescuentoArt".$hddNumeroArt,"value",$valForm['txtMontoDescuentoArt']);
	
	$objResponse->script("byId('btnCancelarDatosArticulo').click();");

	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'))");

	return $objResponse;
}

function eliminarArticulo($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxItm'])) {
		foreach($valForm['cbxItm'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
				$valorItm));
				
		$objResponse->script("
			if(byId('cbxItm').checked == true){
				byId('cbxItm').checked = false;
			}");
		}
	} else {
		$objResponse->alert("Debe seleccionar registro(s) para poder eliminar(los)");
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
	return $objResponse;
}

function eliminarGastos($frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	if (isset($frmTotalDcto['checkItemGasto'])) {
		foreach($frmTotalDcto['checkItemGasto'] as $indiceGasto => $valorGasto){
			$objResponse->script(sprintf("
				fila = document.getElementById('trItmGasto%s');
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorGasto));
		}		
		$objResponse->script("byId('checkGastoItemFactura').checked = false;
			xajax_calcularMontoGastos(xajax.getFormValues('frmTotalDcto'));");
	} else {
	 	$objResponse->alert("Debe seleccionar almenos un Item para eliminar");
	}

	$objResponse->script("xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Cant', this.value, 'txtPorcGasto%s');");
	
	return $objResponse;
}

function eliminarImpuesto($valorForm, $elimBloque = NULL){
	$objResponse = new xajaxResponse();
	
	switch($elimBloque){
		case 1:
			if(isset($valorForm['cbxItmImpuesto'])){
				foreach($valorForm['cbxItmImpuesto'] as $indiceItem => $valorItem){
					$objResponse->script(sprintf("
						fila = document.getElementById('trItemsImpuesto:%s');
						padre = fila.parentNode;
						padre.removeChild(fila);
					",$valorItem));
				}
			$objResponse->script("xajax_calcularImpuesto(xajax.getFormValues('frmDatosArticulo'));");
			$objResponse->script("
				if(byId('cbxItmsImpuesto').checked == true){
					byId('cbxItmsImpuesto').checked = false;
				}
			");
			}else {
				$objResponse->alert("Debe Seleccioanr uno Item Si Desea Eliminar");	
			}
			break;
		default:
			if(isset($valorForm['cbxItmImpuesto2'])){
				foreach($valorForm['cbxItmImpuesto2'] as $indiceItem => $valorItem){
					$objResponse->script(sprintf("
						fila = document.getElementById('trItemsImpuesto:%s');
						padre = fila.parentNode;
						padre.removeChild(fila);
					",$valorItem));
				}
			}
			break;	
	}

	return $objResponse;
}

// INSERTA LOS GATOS
function insertarGastos($idGasto, $frmTotalDcto){
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmTotalDcto['hddItemsGasto'];
	$contFila = $arrayObj[count($arrayObj)-1];

	if ($idGasto > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {
				if($valor == 0){
					$objResponse->script("xajax_eliminarGastos(xajax.getFormValues('frmTotalDcto'))");	
				}
				if ($frmTotalDcto['checkHddnGasto'.$valor] == $idGasto) {
					$existe = true;
				}
			}
		}
		if ($existe == false) {
			$Result1 = insertarItemGastos($contFila,$idGasto);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			$objResponse->script("RecorrerForm('frmLstGasto',false);");// DESBLOQUEA LOS BOTONES DEL LISTADO
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert("Este item ya se encuentra incluido");
		}
	}
	$objResponse->script("RecorrerForm('frmLstGasto',false)");
	
	return $objResponse;
}

// INSERTA LOS IMPUESTO SOBRE EL DETALLE DEL DOCUMENTO
function insertarImpuestoBloque($frmDatosArt, $frmLstImpuesto){
	$objResponse = new xajaxResponse();
	
	$arrayImpuesto = $frmLstImpuesto['checkLstImpuesto'];
	$arrayItemArts = $frmDatosArt['cbxItm'];

	if(isset($arrayImpuesto) ){
		foreach($arrayImpuesto as $indiceImpuesto => $valorImpuesto){
			// CONSUTLAS EL IMPUESTO 
			$query = sprintf("SELECT * FROM pg_iva WHERE idIva = %s",
				valTpDato($valorImpuesto, "int"));
			$rsIva = mysql_query($query);
			if (!$rsIva) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowsIva = mysql_fetch_assoc($rsIva);
			$idIva []= $rowsIva['idIva'];
			$iva []= $rowsIva['iva'];
		}
	}else{
		$objResponse->alert("Debe Seleccionar al menos un impuesto");	
	}
	
	// RECORRE LOS ART EXISTENTES DE LA ORDER
	if(isset($arrayItemArts)){
		foreach($arrayItemArts as $indiceArt => $valorArt){
			$objResponse->script(sprintf("
			if(byId('tdIvaArt'+%s).className == 'divMsjInfo'){
				byId('tdIvaArt'+%s).className = '';
			}",
		$valorArt,$valorArt));
			$tblIva ="";
			foreach($iva as $indice => $valorIva){
				$tblIva .=sprintf("<table cellspacing=\"0\" cellpadding=\"0\" align=\"center\"><tr><td>%s</td><td>%s</td></tr></table>",
				$valorIva,"%");
				$objResponse->assign("divTotalIvaArt".$valorArt,"innerHTML",$tblIva);
			}

			$objResponse->assign("hddIvaArt".$valorArt,"value",implode("|", $iva));
			$objResponse->assign("hddIdIvaArt".$valorArt,"value",implode("|", $idIva));
		}
	} else {
		return $objResponse->alert("Debe Seleccionar al menos un articulo");	
	}

	$objResponse->script("if(byId('cbxItm').checked == true){byId('cbxItm').click();}");
	$objResponse->script("byId('btsCerraImpuesto').click();");
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'))");

	return	$objResponse;
}

// INSERTA LOS IMPUESTO SOBRE LA DESCRIPCION DEL ART (EDITAR ART)
function insertarImpuesto($idImpuesto, $frmListaArticulo) {
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObj = $frmListaArticulo['cbxItmImpuesto2'];
	$contFila = $arrayObj[count($arrayObj)-1];
	
	if ($idImpuesto > 0) {
		$existe = false;
		if (isset($arrayObj)) {
			foreach ($arrayObj as $indice => $valor) {	
				if($valor == 0){
					$objResponse->script("xajax_eliminarImpuesto(xajax.getFormValues('frmDatosArticulo'))");	
				}
				if ($frmListaArticulo['textItmImpuestoId'.$valor] == $idImpuesto) {
					$existe = true;
				}
			}
		}
		if ($existe == false) {
			$Result1 = insertarItemImpuesto($contFila, $idImpuesto);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			$objResponse->script("RecorrerForm('frmLstImpuesto',false);");// DESBLOQUEA LOS BOTONES DEL LISTADO
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObj[] = $contFila;
			}
		} else {
			$objResponse->alert("Este item ya se encuentra incluido");
		}
	}
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO 
	$objResponse->script("RecorrerForm('frmLstImpuesto',false);
	xajax_calcularImpuesto(xajax.getFormValues('frmDatosArticulo'));");
	
	return $objResponse;
}

function insertarUnidadFisica($frmDatosUnidadFisica, $frmListaUnidadFisica){
	$objResponse = new xajaxResponse();
	
	$idUnidadFisica = $frmDatosUnidadFisica["txtIdUnidadFisica"];
	$costoUnitarioUnidadFisica = str_replace(",", "", $frmDatosUnidadFisica["txtCostoUnitarioUnidadFisica"]);
	
	foreach($frmListaUnidadFisica["hddIdUnidadFisica"] as $idUnidadFisicaExistente){
		if($idUnidadFisica == $idUnidadFisicaExistente){
			return $objResponse->alert("Esta unidad ya se encuentra agregada");
		}
	}
	
	$query = sprintf("SELECT
		uni_fis.id_unidad_fisica,
		uni_fis.serial_carroceria,
		uni_fis.placa,
		uni_fis.kilometraje,
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo
	FROM an_unidad_fisica uni_fis
	INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
	WHERE uni_fis.id_unidad_fisica = %s", 
	valTpDato($idUnidadFisica, "int"));
	
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$row = mysql_fetch_assoc($rs);
	
	$htmlItmPie = sprintf("$('#trItmPieUnidadFisica').before('".
		"<tr>".
			"<td><input type=\"checkbox\" value=\"%s\" id=\"cbxItmUnidad\" name=\"cbxItmUnidad[]\" />".
			"<td align=\"center\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"left\">%s</td>".
			"<td align=\"right\">%s</td>".
			"<td align=\"right\">%s</td>".			
			"<td align=\"right\">%s".
				"<input type=\"hidden\" name=\"hddIdDetalleUnidadFisica[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdUnidadFisica[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddIdServicioMantenimiento[]\" value=\"%s\" />".
				"<input type=\"hidden\" name=\"hddCostoAsociado[]\" value=\"%s\" />".
			"</td>".
		"</tr>');",
		'',
		//td
		utf8_encode($row['id_unidad_fisica']),
		utf8_encode($row['placa']),
		utf8_encode($row['serial_carroceria']),
		utf8_encode($row['vehiculo']),
		utf8_encode($row['kilometraje']),
		utf8_encode($frmDatosUnidadFisica['txtDescripcionServicioMantenimiento']),		
		number_format($costoUnitarioUnidadFisica, 2, ".", ","),
		//hidden
		'',
		$idUnidadFisica,
		$frmDatosUnidadFisica['hddIdServicioMantenimiento'],
		$costoUnitarioUnidadFisica);

	$objResponse->script($htmlItmPie);
	$objResponse->script("document.forms['frmDatosUnidadFisica'].reset();
	byId('btnBuscarUnidadFisica').click();");
	
	return $objResponse;
}

function formAlmacen($hddNumeroArt, $frmListaArticulo) {
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("tdMsj","innerHTML","");
	$objResponse->script("document.forms['frmAlmacen'].reset();");
	
	$idDetallePedido = $frmListaArticulo['hddIdPedDetArt'.$hddNumeroArt];
	
	if ($frmListaArticulo['hddIdArtSust'.$hddNumeroArt] > 0) {
		$idArticulo = $frmListaArticulo['hddIdArtSust'.$hddNumeroArt];
	} else {
		$idArticulo = $frmListaArticulo['hddIdArt'.$hddNumeroArt];
	}
	$idCasilla = $frmListaArticulo['hddIdCasilla'.$hddNumeroArt];
	
	// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
	$queryDctoDet = sprintf("SELECT *
	FROM ga_orden_compra orden_comp
		INNER JOIN ga_orden_compra_detalle orden_comp_det ON (orden_comp.id_orden_compra = orden_comp_det.id_orden_compra)
	WHERE id_orden_compra_detalle = %s",
		valTpDato($idDetallePedido, "int"));
	$rsDctoDet = mysql_query($queryDctoDet);
	if (!$rsDctoDet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowDctoDet = mysql_fetch_assoc($rsDctoDet);
	
	// BUSCA LOS DATOS DEL ARTICULO
	$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s",
		valTpDato($idArticulo, "int"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	// BUSCA LOS DATOS DEL ALMACEN EN EL CUAL SE GUARDARA LA EXISTENCIA
	$queryCasilla = sprintf("SELECT * FROM vw_ga_casillas WHERE id_casilla = %s",
		valTpDato($idCasilla, "int"));
	$rsCasilla = mysql_query($queryCasilla);
	if (!$rsCasilla) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCasilla = mysql_fetch_assoc($rsCasilla);
	
	$idEmpresa = ($rowCasilla['id_casilla'] != "") ? $rowCasilla['id_empresa'] : $rowDctoDet['id_empresa'];
	
	//$objResponse->loadCommands(cargaLstEmpresa($idEmpresa));
	$objResponse->loadCommands(cargaLstEmpresaFinal($idEmpresa, "onchange=\"xajax_cargaLst('almacenes', 'lstPadre', 'Act', this.value, 'null', 'null');\""));
	
	$objResponse->loadCommands(cargaLstUbicacion("almacenes", "Act",
		$idEmpresa,
		$rowCasilla['id_almacen'],
		$rowCasilla['id_calle'],
		$rowCasilla['id_estante'],
		$rowCasilla['id_tramo'],
		$rowCasilla['id_casilla']));
	
	$objResponse->assign("hddNumeroArt2","value",$hddNumeroArt);
	$objResponse->assign("txtCodigoArticulo","value",elimCaracter($rowArticulo['codigo_articulo'],"-"));
	$objResponse->assign("txtArticulo","value",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtCantidadDisponible","value",$rowDctoDet['cantidad']);
	
	$objResponse->assign("hddIdDetallePedido","value",$idDetallePedido);
	$objResponse->assign("hddIdArticulo","value",$rowArticulo['id_articulo']);
	
	/*$objResponse->script("
	byId('tblArticulo').style.display = 'none';
	byId('tblAlmacen').style.display = '';");*/
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML",("Distribuir Artículo en Almacen"));
	$objResponse->script("
	if (byId('divFlotante').style.display == 'none') {
		byId('divFlotante').style.display='';
		centrarDiv(byId('divFlotante'));
	}");
	
	return $objResponse;
}

function guardarDcto($frmDcto, $frmListaArticulo, $frmTotalDcto, $frmListaUnidadFisica) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_registro_compra_list","insertar")) { return $objResponse; }

	//DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($frmListaArticulo['cbx'])) {
		foreach($frmListaArticulo['cbx'] as $indiceItm => $valorItm) {
			$arrayObj[] = $valorItm; 
		}
	}else{
		errorGuardarDcto($objResponse,__LINE__);
		return $objResponse->alert("Debe Existir al menos un Items");	
	}
	$idEmpresa = $frmDcto['txtIdEmpresa'];
	
	$idModulo = 3; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion

	// VERIFICA VALORES DE CONFIGURACION (Asignar a Fecha de Registro la Fecha Factura Proveedor)
	$queryConfig402 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 402 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig402 = mysql_query($queryConfig402);
	if (!$rsConfig402) { 
		errorGuardarDcto($objResponse,__LINE__);
		return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); 
	}
	$totalRowsConfig402 = mysql_num_rows($rsConfig402);
	$rowConfig402 = mysql_fetch_assoc($rsConfig402);
	
	$valor = explode("|",$rowConfig402['valor']);
	
	$txtFechaRegistroCompra = date(spanDateFormat);
	$txtFechaProveedor = $frmDcto['txtFechaProveedor'];
	if ($frmDcto['cbxFechaRegistro'] == 1 && $valor[0] == 1) {
		if ((date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
			&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") <= $valor[2]) { // VERIFICA SI ES DE MESES ANTERIORES
			if (restaFechas(spanDateFormat, date(str_replace("d","01",spanDateFormat)), date(spanDateFormat), "dias") <= $valor[1]
			|| date("m", strtotime($txtFechaProveedor)) == date("m")) { // VERIFICA SI EL REGISTRO DE COMPRA ESTA ENTRE LOS DIAS PERMITIDOS DEL MES EN CURSO
				$txtFechaRegistroCompra = $txtFechaProveedor;
			} else {
				$objResponse->script("byId('cbxFechaRegistro').checked = false;");
				$objResponse->alert(utf8_encode("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que ya pasaron los ".($valor[1])." primeros días del mes en curso. Por lo que se registrará con fecha ".($txtFechaRegistroCompra)));
			}
		} else if (!(date("Y", strtotime($txtFechaProveedor)) == date("Y", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat))))
			&& date("m", strtotime($txtFechaProveedor)) == date("m", strtotime("-".$valor[2]." month", strtotime(date(spanDateFormat)))))
		|| restaFechas(spanDateFormat, $txtFechaProveedor, date(spanDateFormat), "meses") > $valor[2]) {
			$objResponse->script("byId('cbxFechaRegistro').checked = false;");
			$objResponse->alert(utf8_encode("El registro de compra no podrá tener como fecha de registro ".($txtFechaProveedor)." debido a que supera ".($valor[2])."  mes(es) de diferencia. Por lo que se registrará con fecha ".($txtFechaRegistroCompra)));
		} else {
			$txtFechaRegistroCompra = $txtFechaProveedor;
		}
	} else if ($frmDcto['cbxFechaRegistro'] == 1) {
		$objResponse->script("byId('cbxFechaRegistro').checked = false;");
		return $objResponse->alert(utf8_encode("No tiene habilitada la opción para asignar esta fecha como fecha de registro"));
	}

	// VALIDA EL TOTAL DE LA FACTURA
	if($frmTotalDcto['txtTotalOrdenValidar'] != $frmTotalDcto['txtTotalOrden']){
		errorGuardarDcto($objResponse,__LINE__);
		$objResponse->script("
			document.getElementById('txtTotalOrden').className = 'inputErrado';
			document.getElementById('txtTotalOrdenValidar').className = 'inputErrado';
		abrirDivFlotante('totalFactura', divFlotante8);");
		return $objResponse->alert("El total de la factura indicado no conincide con el calculo; indique el total correcto.");		
	}
	
	//COMO NO SE MANEJAN UBICACIONES, AUTOMATICAMENTE AGREGA LA PRIMERA QUE ENCUENTRA DE LA EMPRESA
	$queryCasilla = sprintf("SELECT 
		casilla.id_casilla
	FROM ga_calles calle
		INNER JOIN ga_almacenes alm ON (calle.id_almacen = alm.id_almacen)
		INNER JOIN ga_estantes estante ON (calle.id_calle = estante.id_calle)
		INNER JOIN ga_tramos tramo ON (estante.id_estante = tramo.id_estante)
		INNER JOIN ga_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
	WHERE alm.id_empresa = %s
	LIMIT 1;",
		valTpDato($idEmpresa, "int"));
	$rsCasilla = mysql_query($queryCasilla);
	if (!$rsCasilla) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$rowCasilla = mysql_fetch_assoc($rsCasilla);
	
	$hddIdCasilla = $rowCasilla['id_casilla'];
	
	//VERIFICA SI ALGUN ARTICULO NO TIENE UNA UBICACIÓN ASIGNADA EN EL ALMACEN
	$sinAlmacen = false;
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$frmListaArticulo['hddIdCasilla'.$valor] = $hddIdCasilla;
			if ($valor > 0 && strlen($frmListaArticulo['hddIdCasilla'.$valor]) == "") {
				$sinAlmacen = true;
			}
		}
	}// FIN VERIFICAR LA UBICACION DEL ART EN EL ALMACEN

	if ($sinAlmacen == true) {
		errorGuardarDcto($objResponse,__LINE__);
		return $objResponse->alert(utf8_encode("Existen artículos los cuales no tienen una ubicación asignada"));		
	}
	
	mysql_query("START TRANSACTION;");
	$queryProveedor = sprintf("SELECT cp_proveedor.credito, cp_prove_credito.*
		FROM cp_proveedor
			LEFT OUTER JOIN cp_prove_credito ON (cp_proveedor.id_proveedor = cp_prove_credito.id_proveedor)
		WHERE cp_proveedor.id_proveedor = %s;",
			valTpDato($frmDcto['txtIdProv'], "int"));
	$rsProveedor = mysql_query($queryProveedor);
	if (!$rsProveedor) {errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
	$rowProveedor = mysql_fetch_assoc($rsProveedor);
	
	$fechaVencimiento = ($rowProveedor['credito'] == "Si") ? suma_fechas(spanDateFormat,$txtFechaProveedor,$rowProveedor['diascredito']) : $txtFechaProveedor;
	
	//INSERTA LOS DATOS DE LA FACTURA
	$insertSQL = sprintf("INSERT INTO cp_factura (id_empresa, numero_factura_proveedor, numero_control_factura, fecha_factura_proveedor, id_proveedor, fecha_origen, fecha_vencimiento, id_modulo, id_orden_compra, estatus_factura, observacion_factura, tipo_pago, monto_exento, monto_exonerado, subtotal_factura, porcentaje_descuento, subtotal_descuento, total_cuenta_pagar, saldo_factura, aplica_libros, fecha_registro, id_empleado_creador) 
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($idEmpresa, "int"),
		valTpDato($frmDcto['txtNumeroFacturaProveedor'], "text"),
		valTpDato($frmDcto['txtNumeroControl'], "text"),
		valTpDato(date("Y-m-d",strtotime($txtFechaProveedor)), "date"),
		valTpDato($frmDcto['txtIdProv'], "int"),
		valTpDato(date("Y-m-d",strtotime($txtFechaRegistroCompra)), "date"),
		valTpDato(date("Y-m-d",strtotime($fechaVencimiento)), "date"),
		valTpDato($idModulo, "int"),
		valTpDato($frmTotalDcto['txtIdOrdenCompra'], "int"),
		valTpDato(0, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		valTpDato($frmTotalDcto['txtObservacionFactura'], "text"),
		valTpDato($frmDcto['rbtTipoPago'], "int"), // 0 = Contado, 1 = Credito
		valTpDato($frmTotalDcto['txtTotalExento'], "real_inglesa"),
		valTpDato(0, "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),//total_cuenta_pagar
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),//saldo_factura
		valTpDato("1", "boolean"),
		valTpDato("NOW()", "campo"),
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idFactura = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	$arrayDetIdDctoContabilidad[0] = $idFactura;
	$arrayDetIdDctoContabilidad[1] = $idModulo;
	$arrayDetIdDctoContabilidad[2] = "COMPRA";
	$arrayIdDctoContabilidad[] = $arrayDetIdDctoContabilidad;
	
	// RECORRE EL DETALLE DE LA FACTURA
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$idArticulo = $frmListaArticulo['hddIdArt'.$valor];
			$idCasilla = $frmListaArticulo['hddIdCasilla'.$valor];
			if (strlen($idArticulo) > 0) {
				$cantidadPedida = $frmListaArticulo['hddCantArt'.$valor];
				$cantidadRecibida = $frmListaArticulo['hddCantRecibArt'.$valor];
				$cantidadPendiente = $frmListaArticulo['hddCantPend'.$valor];
				
				$gastoUnitario = $frmListaArticulo['hddGastosArt'.$valor] / $cantidadRecibida;
				$costoUnitarioAcumulado = $frmListaArticulo['hddCostoArt'.$valor] + $gastoUnitario;
				
				$descuentoCostoUnitario = ($frmTotalDcto['txtDescuento'] * $costoUnitarioAcumulado) / 100;
				$costoUnitarioAcumuladoConDescuento = $costoUnitarioAcumulado - $descuentoCostoUnitario;
				
				$estatusDet = (($frmListaArticulo['hddCantArt'.$valor]-$frmListaArticulo['hddCantRecibArt'.$valor]) == 0) ? 1 : 0; //0 = En Espera, 1 = Recibido
				
				// CREA EL ARRAY CON LOS IVAS DEL DETALLE	
				$cadenaIdIva = strstr($frmListaArticulo['hddIdIvaArt'.$valor],"|");
				$cadenaIva = strstr($frmListaArticulo['hddIvaArt'.$valor],"|");

				//INSERTA EL DETALLE DE LA FACTURA
				$insertDetFA = sprintf("INSERT INTO cp_factura_detalle (id_factura, id_articulo,  cantidad, pendiente, precio_unitario, tipo, id_cliente, estatus, por_distribuir)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($cantidadRecibida, "real_inglesa"),
					valTpDato($cantidadPendiente, "real_inglesa"),
					valTpDato($frmListaArticulo['hddCostoArt'.$valor], "real_inglesa"),
					valTpDato($frmListaArticulo['hddTipoArt'.$valor], "int"),
					valTpDato($frmListaArticulo['hddIdClienteArt'.$valor], "int"),
					valTpDato($estatusDet, "boolean"),
					valTpDato($cantidadRecibida, "int"));
			
				mysql_query("SET NAMES 'utf8';");
				$rsDetFA = mysql_query($insertDetFA);
				$idFacturaDetalle = mysql_insert_id();
				if (!$rsDetFA) { 
					errorGuardarDcto($objResponse,__LINE__); 
					return $objResponse->alert($insertDetFA."\n".mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); 
				}
				$idFacturaDetalle = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
					
				//RECORRE LA CANTIDAD DE IVAS DEL DETALLE
				$cadenaIdIva = explode("|",$frmListaArticulo['hddIdIvaArt'.$valor]);
				$cadenaIva = explode("|",$frmListaArticulo['hddIvaArt'.$valor]);
				$baseImponible = str_replace(",","",$frmListaArticulo['hddTotalArt'.$valor]);
				$subTotalIva = NULL;
				foreach($cadenaIdIva as $indeceIdIva => $valorIdIva){
					$porcIva = $cadenaIva[$indeceIdIva];
					
					//INSERTA LOS IVAS DE CADA DETALLE
					if($porcIva != 0){
						$subTotalIva = (($baseImponible * $porcIva) /100);
						$idIva = valTpDato($valorIdIva, "int");	
					}else{
						$subTotalIva =  0.00;
						$idIva = valTpDato(NULL, "text");	
					}

					$insertSqlIvaArt = sprintf("INSERT INTO ga_factura_detalle_iva(id_factura_detalle, id_iva, iva, base_imponible, sub_total_iva) 
					VALUE (%s,%s,%s,%s,%s)",
						valTpDato($idFacturaDetalle, "int"),
						$idIva,
						valTpDato($porcIva, "real_inglesa"),
						valTpDato($baseImponible, "real_inglesa"),
						valTpDato($subTotalIva, "real_inglesa"));
					$ResultSqlIva = mysql_query($insertSqlIvaArt);
					if(!$ResultSqlIva){ errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				}//FIN DEL RECORRIDO DE LA CANTIDAD DE IVAS DEL DETALLE

				//REGISTRA EL COSTO DE COMPRA DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO ga_articulos_costos (id_proveedor, id_articulo, precio, fecha)
				VALUE (%s, %s, %s, %s);",
					valTpDato($frmDcto['txtIdProv'], "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($costoUnitarioAcumuladoConDescuento, "real_inglesa"),
					valTpDato("NOW()", "campo"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse,_LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");

				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$insertSQL = sprintf("INSERT INTO ga_kardex (id_documento, id_articulo, id_casilla, tipo_movimiento, cantidad, id_clave_movimiento, estado, fecha_movimiento, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, SYSDATE());",
					valTpDato($idFactura, "int"),//<- el ultimo id insertado cp_factura
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato(1, "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato($cantidadRecibida, "int"),
					valTpDato($frmDcto['lstClaveMovimiento'], "int"),
					valTpDato(0, "int"), // 0 = Entrada, 1 = Salida
					valTpDato("NOW()", "campo"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				// VERIFICA SI EL ARTICULO YA ESTA REGISTRADO EN DICHA UBICACION
				$queryArtAlmacen = sprintf("SELECT * FROM vw_ga_articulos_almacen
					WHERE id_articulo = %s
						AND id_casilla = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"));
				$rsArtAlmacen = mysql_query($queryArtAlmacen);
				if (!$rsArtAlmacen) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArtAlmacen = mysql_fetch_assoc($rsArtAlmacen);
					
				// SI NO ESTA REGISTRADO, LO REGISTRA
				if ($rowArtAlmacen['id_articulo_almacen'] == "") {
					$insertSQL = sprintf("INSERT INTO ga_articulos_almacen (id_casilla, id_articulo) VALUE (%s, %s);",
						valTpDato($idCasilla, "int"),
						valTpDato($idArticulo, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}

				// ALMACENA LA CANTIDAD FALTANTE POR DISTRIBUIR DENTRO DE LOS ALMACENES DE LA EMPRESA
				$updateSQL = sprintf("UPDATE cp_factura_detalle SET
					por_distribuir = %s
				WHERE id_factura_detalle = %s;",
					valTpDato((doubleval($cantidadRecibida)-doubleval($cantidadRecibida)), "int"),
					valTpDato($idFacturaDetalle, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");					

				//VERIFICA SI EL ARTICULO YA ESTA REGISTRADO PARA LA EMPRESA
				$queryArtEmp = sprintf("SELECT * FROM ga_articulos_empresa WHERE id_empresa = %s AND id_articulo = %s",
					valTpDato($idEmpresa,"int"),
					valTpDato($idArticulo,"int"));
				$rsArtEmp = mysql_query($queryArtEmp);
				if (!$rsArtEmp){ errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
				
				//SI NO ESTA REGISTRADO, LO REGISTRA
				if ($rowArtEmp['id_articulo_empresa'] == "") {
					$insertSQL = sprintf("INSERT INTO ga_articulos_empresa (id_empresa, id_articulo, clasificacion) VALUE (%s, %s, %s)",
						valTpDato($idEmpresa,"int"),
						valTpDato($idArticulo,"int"),
						valTpDato("F","text"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1){ errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}

				//VERIFICA SI EL ARTICULO TIENE UNA UBICACION PREDETERMINADA EN UN ALMACEN DE LA EMPRESA
				$queryArtEmp = sprintf("SELECT * FROM ga_articulos_empresa
					WHERE id_empresa = %s AND id_articulo = %s;",
						valTpDato($idEmpresa, "int"),
						valTpDato($idArticulo, "int"));
				$rsArtEmp = mysql_query($queryArtEmp);
				if (!$rsArtEmp) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
				
				if ($rowArtEmp['id_casilla_predeterminada'] == "") {
					$updateSQL = sprintf("UPDATE ga_articulos_empresa SET
						id_casilla_predeterminada = %s 
					WHERE id_articulo_empresa = %s;",
						valTpDato($idCasilla,"int"),
						valTpDato($rowArtEmp['id_articulo_empresa'],"int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
					
				// INSERTA LOS PRECIOS DE LOS ARTICULOS
				$queryPrecios = sprintf("SELECT * FROM pg_precios WHERE id_precio <> 6");
				$rsPrecios = mysql_query($queryPrecios);
				if (!$rsPrecios) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				while ($rowPrecios = mysql_fetch_assoc($rsPrecios)) {
					$queryArtPrecio = sprintf("SELECT * FROM ga_articulos_precios
					WHERE id_articulo = %s
						AND id_precio = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($rowPrecios['id_precio'], "int"));
					$rsArtPrecio = mysql_query($queryArtPrecio);
					if (!$rsArtPrecio) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$rowArtPrecio = mysql_fetch_assoc($rsArtPrecio);
					if ($rowPrecios['tipo'] == 0){ // PRECIO SOBRE VENTA
						$montoGanancia = ($costoUnitarioAcumuladoConDescuento * ($rowPrecios['porcentaje']/100)) + $costoUnitarioAcumuladoConDescuento;
					} else if ($rowPrecios['tipo'] == 1) { // PRECIO SOBRE COSTO
						$montoGanancia = ($costoUnitarioAcumuladoConDescuento * 100) / ( 100 - $rowPrecios['porcentaje']);
					}

					//SI PRECIO ES BASIO LO INSERTA
					if ($rowArtPrecio['id_articulo_precio'] == "") {
						$insertSQL = sprintf("INSERT INTO ga_articulos_precios (id_articulo, id_precio, precio)
						VALUE (%s, %s, %s);",
							valTpDato($idArticulo, "int"),
							valTpDato($rowPrecios['id_precio'], "int"),
							valTpDato($montoGanancia, "real_inglesa"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					} else { // SI NO LO ACTUALIZA
						$updateSQL = sprintf("UPDATE ga_articulos_precios SET
							precio = %s
						WHERE id_articulo_precio = %s;",
							valTpDato($montoGanancia, "real_inglesa"),
							valTpDato($rowArtPrecio['id_articulo_precio'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
					
				//ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS)
				$updateSQL = sprintf("UPDATE ga_articulos_almacen SET
					cantidad_entrada = (SELECT SUM(kardex.cantidad)
						FROM ga_kardex kardex
						WHERE (kardex.tipo_movimiento = 1 OR kardex.tipo_movimiento = 2)
							AND kardex.id_articulo = ga_articulos_almacen.id_articulo
							AND kardex.id_casilla = ga_articulos_almacen.id_casilla)
				WHERE (ga_articulos_almacen.id_articulo = %s
						OR ga_articulos_almacen.id_articulo = %s)
					AND (ga_articulos_almacen.id_casilla = %s
						OR ga_articulos_almacen.id_casilla = %s);",
					valTpDato($idArticulo, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($idCasillaPredet, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
				
				//CONSULTA SI EL ARTICULO ES UN ACTIVO 
				$queryActivo = sprintf("SELECT
					art.descripcion, tipo_activo.Codigo
				FROM ga_articulos art
					INNER JOIN ga_subsecciones subsec ON (subsec.id_subseccion = art.id_subseccion)
					INNER JOIN ".DBASE_CONTAB.".tipoactivo tipo_activo ON (tipo_activo.id = subsec.tipo_activo)
				WHERE subsec.tipo_activo > 0
					AND id_articulo = %s;",
					valTpDato($idArticulo, "int"));
				$rsActivo = mysql_query($queryActivo);
				if (!$rsActivo) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$totalRows = mysql_num_rows($rsActivo);
				if ($totalRows > 0) {//SI ES UN ACTIVO LO INSERTA
					$rowActivo = mysql_fetch_assoc($rsActivo);
					
					for ($cuenta = 1; $cuenta <= $cantidadRecibida; $cuenta++){
						$insertSQL = sprintf("INSERT INTO ".DBASE_CONTAB.".deprecactivos (Fecha, Tipo, CompAdquisicion, Comprobante, Descripcion, Proveedor, estatus)
						VALUE (%s, %s, %s, %s, %s, %s, %s);",
							valTpDato(date("Y-m-d",strtotime($txtFechaRegistroCompra)), "date"),
							valTpDato($rowActivo['Codigo'], "text"),
							valTpDato($costoUnitarioAcumulado, "real_inglesa"),
							valTpDato($frmDcto['txtNumeroFacturaProveedor'], "text"),
							valTpDato($rowActivo['descripcion']."-".$cuenta, "text"),
							valTpDato($frmDcto['txtNombreProv'], "text"),
							valTpDato(1 ,"int")); // 1 = DATOS IMCOMPLETOS, 2 = DATOS COMPLETOS
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
				}
			}
		} // FIN RECORRE EL DETALLE DE LA FACTURA
	}
	
	// INSERTA LOS GASTOS DEL PEDIDO
	if(isset($frmTotalDcto['hddItemsGasto'])){
	// RECORRE LOS GASTO AGREGADO A LA FACTURA
		foreach($frmTotalDcto['hddItemsGasto'] as $indiceGasto => $valorGasto){
			$queryGastos = sprintf("SELECT * FROM pg_gastos WHERE id_gasto = %s;",// BUSCA LOS DATOS DEL GASTO
					valTpDato($frmTotalDcto['hddIdGasto'.$valorGasto], "int"));
			$rsGastos = mysql_query($queryGastos);
			if (!$rsGastos) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowGastos = mysql_fetch_assoc($rsGastos);
			
			$montoGasto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$valorGasto]);
			$porcMontoGasto = str_replace(",","",$frmTotalDcto['txtPorcGasto'.$valorGasto]);
			
			//AGREGAR EL CAMPO DE RETENCION
			if (round($montoGasto, 2) != 0) {
				$insertSQL = sprintf("INSERT INTO cp_factura_gasto (id_factura, id_gasto, tipo, estatus_iva, id_modo_gasto)
				VALUE (%s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($frmTotalDcto['hddIdGasto'.$valorGasto], "int"),
					valTpDato($frmTotalDcto['hddTipoGasto'.$valorGasto], "int"),
					valTpDato($frmTotalDcto['hddEstatusIvaGasto'.$valorGasto], "boolean"),
					valTpDato($rowGastos['id_modo_gasto'], "int")); // 1 = Normal, 2 = Importacion, 3 = Normal de Importacion
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idFacturaGasto = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				// RECORRE LOS IMPUESTO DEL GASTO
				$arrayIdIvaGasto = explode("|",$frmTotalDcto['hddIdIvaGasto'.$valorGasto]);
				$arrayIvaGasto = explode("|",$frmTotalDcto['hddIvaGasto'.$valorGasto]);
				foreach($arrayIdIvaGasto as $indeceIdIvaGasto => $valorIdIvaGasto){
					$porcIvaGasto = $arrayIvaGasto[$indeceIdIvaGasto];
					$insertSqlIvaGasto = sprintf("INSERT INTO ga_factura_detalle_iva_gasto (id_factura_gasto, id_iva, iva, porcentaje_monto, monto) VALUES(%s, %s, %s, %s, %s)",
						valTpDato($idFacturaGasto,"int"),
						valTpDato($valorIdIvaGasto,"int"),
						valTpDato($porcIvaGasto,"real_inglesa"),
						valTpDato($porcMontoGasto, "real_inglesa"),
						valTpDato($montoGasto, "real_inglesa"));
					mysql_query("SET NAMES 'latin1';");
					$ResultGastosIva = mysql_query($insertSqlIvaGasto);
					if(!$ResultGastosIva){errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}// FIN FOREACH RECORIDO DE LOS GASTO DE LA FACTURA
	} 
	
	// RECORRE LOS IVAS DEL PEDIDO
	if(isset($frmTotalDcto['hddIdIva'])){
		foreach($frmTotalDcto['hddIdIva'] as $indiceIdIva => $valorIdIva){
			if ($frmTotalDcto['txtSubTotalIva'.$valorIdIva] != 0) {
				//INSERTA LOS IVA DEL PEDIDO
				$insertSQL = sprintf("INSERT INTO cp_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
				VALUE (%s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato($frmTotalDcto['txtBaseImpIva'.$valorIdIva], "real_inglesa"),
					valTpDato($frmTotalDcto['txtSubTotalIva'.$valorIdIva], "real_inglesa"),
					valTpDato($valorIdIva, "int"),
					valTpDato($frmTotalDcto['txtIva'.$valorIdIva],"real_inglesa"),
					valTpDato($frmTotalDcto['hddLujoIva'.$valorIdIva], "boolean"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}		
		}
	}//FIN RECORRE LOS IVA DEL PEDIDO

	// REGISTRA EL ESTADO DE CUENTA
	$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("FA", "text"),
		valTpDato($idFactura, "int"),
		valTpDato(date("Y-m-d",strtotime($txtFechaRegistroCompra)), "date"),
		valTpDato(1, "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	mysql_query("SET NAMES 'latin1';");

	// INSERTA EL MOVIMIENTO
	$insertSQL = sprintf("INSERT INTO ga_movimiento (id_tipo_clave_movimiento, id_clave_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, referencia1, referencia2, remision, tipo_costo, fecha_captura, ultima_partida_usada, id_usuario, credito, id_moneda_extranjera_doc, id_moneda_extranjera_doc_cambio, id_moneda_extranjera_ref, id_moneda_extranjera_ref_cambio) 
	VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
		valTpDato($frmDcto['lstTipoClave'], "int"),
		valTpDato($frmDcto['lstClaveMovimiento'], "int"),
		valTpDato($idFactura, "int"),
		valTpDato("NOW()", "campo"),
		valTpDato($frmDcto['txtIdProv'], "int"),
		valTpDato("", "text"),
		valTpDato("", "text"),
		valTpDato("", "text"),
		valTpDato(0, "boolean"),
		valTpDato("NOW()", "campo"),
		valTpDato("", "int"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($frmDcto['rbtTipoPago'], "boolean"),
		valTpDato("", "int"),
		valTpDato("", "int"),
		valTpDato("", "int"),
		valTpDato("", "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$idMovimiento = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	// INSERTA EL DETALLE DEL MOVIMIENTO
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$idArticulo = $frmListaArticulo['hddIdArt'.$valor];
			if (strlen($idArticulo) > 0) {
				$cantidadPedida = $frmListaArticulo['hddCantArt'.$valor];
				$cantidadRecibida = $frmListaArticulo['hddCantRecibArt'.$valor];
				$cantidadPendiente = doubleval($cantidadPedida) - doubleval($cantidadRecibida);
				
				$gastoUnitario = $frmListaArticulo['hddGastosArt'.$valor] / $cantidadRecibida;
				$costoUnitarioAcumulado = $frmListaArticulo['hddCostoArt'.$valor] + $gastoUnitario;
				
				$descuentoCostoUnitario = ($frmTotalDcto['txtDescuento'] * $costoUnitarioAcumulado) / 100;
				$costoUnitarioAcumuladoConDescuento = $costoUnitarioAcumulado - $descuentoCostoUnitario;
				
				$totalArticulo = $cantidadRecibida * $costoUnitarioAcumulado;
				
				$insertSQL = sprintf("INSERT INTO ga_movimiento_detalle (id_movimiento, id_articulo, cantidad, precio, costo, porcentaje_descuento, subtotal_descuento, correlativo1, correlativo2, tipo_costo, llave_costo_identificado, promocion, id_moneda_costo, id_moneda_costo_cambio)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idMovimiento, "int"),
					valTpDato($idArticulo, "int"),
					valTpDato($cantidadRecibida, "double"),
					valTpDato($costoUnitarioAcumulado, "real_inglesa"),
					valTpDato($costoUnitarioAcumulado, "real_inglesa"),
					valTpDato($frmTotalDcto['txtDescuento'], "text"),
					valTpDato((($frmTotalDcto['txtDescuento']*$totalArticulo)/100), "real_inglesa"),
					valTpDato("", "int"),
					valTpDato("", "int"),
					valTpDato(0, "int"),
					valTpDato("", "text"),
					valTpDato(0, "boolean"),
					valTpDato("", "int"),
					valTpDato("", "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");
			}
		}// FIN DEL FOREACH DEL DETALLER DE MOVIMIENTO
	}
	
	$existeIva = false;
	if(isset($frmTotalDcto['hddIdIva'])){
		foreach($frmTotalDcto['hddIdIva'] as $indice){
			$existeIva = true;
		}	
	}
	
	// CREACION DE LA RETENCION DEL IVA
	if ($frmTotalDcto['lstRetencionImpuesto'] > 0 && $existeIva == true) {
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
				WHERE id_numeracion = 2 AND (id_empresa = %s 
						OR (aplica_sucursales = 1 AND id_empresa = 
									 (SELECT suc.id_empresa_padre FROM pg_empresa suc 
												 WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC
				LIMIT 1;",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$numeroActual = $rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACIÓN DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

		$insertSQL = sprintf("INSERT INTO cp_retencioncabezera (id_empresa, numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idProveedor)
		VALUE (%s, %s, %s, %s, %s, %s);",
			valTpDato($idEmpresa, "int"),
			valTpDato(date("Ym").str_pad($numeroActual, 8, "0", STR_PAD_LEFT), "text"),
			valTpDato("NOW()", "campo"),
			valTpDato(date("Y"), "int"),
			valTpDato(date("m"), "int"),
			valTpDato($frmDcto['txtIdProv'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idRetencionCabezera = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$porcRetencion = $frmTotalDcto['lstRetencionImpuesto'];
		
		$comprasSinIva = str_replace(",","",$frmTotalDcto['txtTotalExento']) + 
						str_replace(",","",$frmTotalDcto['txtTotalExonerado']) + 
						str_replace(",","",$frmTotalDcto['txtGastosSinIva']);
		//RECORRE LOS IVA DEL PEDIDO PARA CREARLE SU RETENCION
		foreach($frmTotalDcto['hddIdIva'] as $indice => $valorIva) {
			if ($frmTotalDcto['txtSubTotalIva'.$valorIva] > 0) {
				$ivaRetenido = round((doubleval($porcRetencion) * str_replace(",","",$frmTotalDcto['txtSubTotalIva'.$valorIva])) / 100,2);
				$insertSQL = sprintf("INSERT INTO cp_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idRetencionCabezera, "int"),
					valTpDato(date("Y-m-d",strtotime($txtFechaProveedor)), "date"),
					valTpDato($idFactura, "int"),
					valTpDato($frmDcto['txtNumeroControl'], "text"),
					valTpDato(" ", "text"),
					valTpDato(" ", "text"),
					valTpDato("01", "text"), // 01 = FACTURA, 02 = NOTA DEBITO, 03 = NOTA CREDITO
					valTpDato(" ", "text"), // CUANDO ES NOTA DE CREDITO O DE DEBITO
					valTpDato($frmTotalDcto['txtTotalOrden'], "real_inglesa"),
					valTpDato($comprasSinIva, "real_inglesa"),
					valTpDato($frmTotalDcto['txtBaseImpIva'.$valorIva], "real_inglesa"),
					valTpDato($frmTotalDcto['txtIva'.$valorIva], "real_inglesa"),
					valTpDato($frmTotalDcto['txtSubTotalIva'.$valorIva], "real_inglesa"),
					valTpDato($ivaRetenido, "real_inglesa"),
					valTpDato($porcRetencion, "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");

				//INSERTA EL PAGO DEBIDO A LA RETENCION
				$insertPagoDoc = sprintf("INSERT INTO cp_pagos_documentos ( id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado)
				VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato("FA", "text"),
					valTpDato("RETENCION", "text"),
					valTpDato($idRetencionCabezera, "int"),
					valTpDato("NOW()", "campo"),
					valTpDato(date("Ym").str_pad($numeroActual, 8, "0", STR_PAD_LEFT), "text"),
					valTpDato("-", "text"),
					valTpDato("-", "text"),
					valTpDato("-", "text"),
					valTpDato("-", "text"),
					valTpDato($ivaRetenido, "real_inglesa"));
				mysql_query("SET NAMES 'utf8';");
				$rsPagoDoc = mysql_query($insertPagoDoc);
				if (!$rsPagoDoc) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				mysql_query("SET NAMES 'latin1';");

/*					// ACTUALIZA EL SALDO DE LA FACTURA
				$updateSQL = sprintf("UPDATE cp_factura SET
					saldo_factura = (saldo_factura - %s)
				WHERE id_factura = %s",
					valTpDato($ivaRetenido, "double"),
					valTpDato($idFactura, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
$objResponse->alert("ACTUALIZA EL SALDO DE LA FACTURA \n".$updateSQL);
*/				}
		}
	}
	
	// GUARDANDO LA RETENCION DE LA IRSL 
	if($frmTotalDcto['lstRetencionISLR'] != "-1" && $frmTotalDcto['txtBaseRetencionISLR'] == ""){
		$objResponse->script("byId('txtBaseRetencionISLR').className = 'inputErrado';");
		errorGuardarDcto($objResponse,__LINE__);
		return $objResponse->alert("Debe Espesificar Una Base Retencion ISLR");	
	}
	
	if(str_replace(",","", $frmTotalDcto['txtTotalMontoRetencionISLR']) != 0){
		$selectIRLR = sprintf("SELECT * FROM te_retenciones WHERE id = %s",
			valTpDato($frmTotalDcto['lstRetencionISLR'],"int"));
		$rsISLR = mysql_query($selectIRLR);
		if (!$rsISLR) {errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
		$rowsISLR = mysql_fetch_array($rsISLR);
		
		$insertISLR = sprintf("INSERT INTO te_retencion_cheque (id_factura, id_retencion, base_imponible_retencion, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo,tipo_documento, tipo, fecha_registro) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($idFactura,"int"), 
			valTpDato($frmTotalDcto['lstRetencionISLR'],"int"),
			valTpDato($frmTotalDcto['txtBaseRetencionISLR'],"real_inglesa"),
			valTpDato($rowsISLR['sustraendo'],"real_inglesa"),
			valTpDato($rowsISLR['porcentaje'],"real_inglesa"),
			valTpDato($frmTotalDcto['txtTotalMontoRetencionISLR'],"real_inglesa"),
			valTpDato($rowsISLR['codigo'],"real_inglesa"),
			valTpDato(2,"int"),
			valTpDato(0,"int"),
			valTpDato(date("Y-m-d",strtotime($txtFechaRegistroCompra)), "date"));
		$rsISLR = mysql_query($insertISLR);
		if (!$rsISLR) {errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
		$idISLR = mysql_insert_id();
		
		$insertCpPagoISLR = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago,tipo_documento_pago, tipo_pago, id_documento,  numero_documento, fecha_pago, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($idFactura,"int"), 
			valTpDato("FA","text"),
			valTpDato("ISLR","text"),
			valTpDato($idISLR,"int"),
			valTpDato($idISLR,"int"),
			valTpDato(date("Y-m-d",strtotime($txtFechaRegistroCompra)), "date"),
			valTpDato("-","text"),
			valTpDato("-","text"),
			valTpDato("-","text"),
			valTpDato("-","text"),
			valTpDato($frmTotalDcto['txtTotalMontoRetencionISLR'],"real_inglesa"),
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"));
		$rsCpPagoISLR = mysql_query($insertCpPagoISLR);
		if (!$rsCpPagoISLR) {errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
	
/*		// MODIFICA EL ESTADO DE LA FACTURA
		$selectFA = sprintf("SELECT saldo_factura FROM cp_factura WHERE id_factura = %s",
			valTpDato($idFactura,"int"));
		$rsFA = mysql_query($selectFA);
		if (!$rsFA) {errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
		$rowsFA = mysql_fetch_array($rsFA);
		
		$saldoFactura = $rowsFA['saldo_factura'] - str_replace(",","", $frmTotalDcto['txtTotalMontoRetencionISLR']);			
		
		$estatusFA = ($saldoFactura == 0) ? 1 : 2;// 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		
		$updateFa = sprintf("UPDATE cp_factura SET 
			estatus_factura = %s,
			saldo_factura =  %s
		WHERE id_factura = %s",
			valTpDato($estatusFA,"int"), 
			valTpDato($saldoFactura,"real_inglesa"), 
			valTpDato($idFactura,"int"));
		$rsFaUpdate = mysql_query($updateFa);
		if (!$rsFaUpdate) {errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
*/		}
	
	// RECORRE EL DETALLE DE LA ORDEN PARA VERIFICAR Y ASI ACTULIZAR LOS PENDIENTES
	if (isset($arrayObj)) { 
		foreach($arrayObj as $indice => $valor) {
			$valorArt = $valor;
			
			$queryPedidoDet = sprintf("SELECT * 
				FROM ga_orden_compra_detalle 
				WHERE id_orden_compra_detalle = %s AND id_articulo = %s;",
				valTpDato($frmListaArticulo['hddIdPedDetArt'.$valorArt], "int"),
				valTpDato($frmListaArticulo['hddIdArt'.$valorArt], "int"));						
			$rsPedidoDet = mysql_query($queryPedidoDet);
			if (!$rsPedidoDet) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);			
			
			$cantidadPendiente = $frmListaArticulo['hddCantPend'.$valorArt];
			
			$estatusDet = (($cantidadPendiente) == 0) ? 1 : 0; //0= pendiente; 1= recibidos
			$arrayOrdenDuplicadas [] = $rowPedidoDet['id_orden_compra'];
			
			if($frmListaArticulo['hddIdArt'.$valorArt] == $rowPedidoDet['id_articulo']){
			$updateSQL = sprintf("UPDATE ga_orden_compra_detalle SET
				pendiente = %s,
				estatus = %s
				WHERE id_orden_compra_detalle = %s ;",
				valTpDato($cantidadPendiente, "int"),
				valTpDato($estatusDet, "boolean"),
				valTpDato($rowPedidoDet['id_orden_compra_detalle'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
			}
		}// FIN DEL FOREACH
	}
	
	//VERIFICA EL DETALLE DEL PEDIDO PARA ACTUALIZAR LA CANTIDAD DE ARTICULOS PENDIENTES
	$queryPedidoDet = sprintf("SELECT * FROM ga_orden_compra_detalle
		WHERE id_orden_compra = %s;",
			valTpDato($frmTotalDcto['txtIdOrdenCompra'], "int"));
		$rsPedidoDet = mysql_query($queryPedidoDet);
		if (!$rsPedidoDet) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
		$existArt = false;
		if (isset($arrayObj)) {
			foreach($arrayObj as $indice => $valor) {
				//if ($frmListaArticulo[''.$valor] == $rowPedidoDet['id_orden_compra_detalle']) {
					$existArt = true;
					$valorArt = $valor;
				//}
				if ($existArt == true) {
					$cantidadPendiente = $frmListaArticulo['hddCantPend'.$valorArt];
					
					//$cantidadPendiente = $frmListaArticulo['hddCantArt'.$valorArt]-$frmListaArticulo['hddCantRecibArt'.$valorArt];
					//$frmListaArticulo['hddCantArt'.$valorArt]-$frmListaArticulo['hddCantRecibArt'.$valorArt]
					$estatusDet = (($cantidadPendiente) == 0) ? 1 : 0; //0= pendiente; 1= recibidas
					//ACTUALIZA LA CANTIDAD DE ARTICULOS PENDIENTES
					$updateSQL = sprintf("UPDATE ga_orden_compra_detalle SET
						pendiente = %s,
						estatus = %s
					WHERE id_orden_compra_detalle = %s AND 
						id_articulo = %s;",
						valTpDato($cantidadPendiente, "int"),
						valTpDato($estatusDet, "boolean"),
						valTpDato($rowPedidoDet['id_orden_compra_detalle'], "int"),
						valTpDato($frmListaArticulo['hddIdArt'.$valorArt], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
				}
			}// FIN DEL FOREACH
		}
	}
	
	//ACTUALIZA EL ESTADO DE LA ORDEN
	$arrayaOrdenUnica = array_keys(array_count_values($arrayOrdenDuplicadas)); //elimina los elementos iguales
	if(isset($arrayaOrdenUnica)){
		foreach($arrayaOrdenUnica as $indice => $valorOrdenUnico){
			$queryPedidoDetValida = sprintf("SELECT * FROM ga_orden_compra_detalle WHERE id_orden_compra = %s AND pendiente != 0 ;",
				valTpDato($valorOrdenUnico, "int"));
			$rsPedidoDetValida = mysql_query($queryPedidoDetValida);
			if (!$Result1) {
				errorGuardarDcto($objResponse,__LINE__); 
				return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); 
			}
			$totalRowsPedidoDetValida = mysql_num_rows($rsPedidoDetValida);
			if($totalRowsPedidoDetValida == 0){
				if($frmTotalDcto['txtIdOrdenCompra'] == $valorOrdenUnico){ //3 = Facturado
					$updateSQL = sprintf("UPDATE ga_orden_compra SET estatus_orden_compra = 3 WHERE id_orden_compra = %s;",
					valTpDato($valorOrdenUnico, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { 
						errorGuardarDcto($objResponse,__LINE__); 
						return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					}
					mysql_query("SET NAMES 'latin1';");
				}else{ //5 = Anulado
					$updateSQL = sprintf("UPDATE ga_orden_compra SET estatus_orden_compra = 5 WHERE id_orden_compra = %s;",
					valTpDato($valorOrdenUnico, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { 
						errorGuardarDcto($objResponse,__LINE__); 
						return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					}
					mysql_query("SET NAMES 'latin1';");
				}
			}	
		}
	}
	
	// ACTUALIZA EL SALDO DE LA FACTURA
	$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
			saldo_factura = (IFNULL(cxp_fact.total_cuenta_pagar, 2)
								- IFNULL((SELECT SUM(pago_dcto.monto_cancelado) FROM cp_pagos_documentos pago_dcto
										WHERE pago_dcto.id_documento_pago = cxp_fact.id_factura
											AND pago_dcto.tipo_documento_pago LIKE 'FA'
											AND pago_dcto.estatus = 1), 0))
		WHERE id_factura = %s
			AND estatus_factura NOT IN (1);",
	valTpDato($idFactura, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	// ACTUALIZA EL ESTADO DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
	$updateSQL = sprintf("UPDATE cp_factura cxp_fact SET
			estatus_factura = (CASE
								WHEN (ROUND(saldo_factura, 2) = 0 OR ROUND(saldo_factura, 2) < 0) THEN
									1
								WHEN (ROUND(saldo_factura, 2) > 0
									AND ROUND(saldo_factura, 2) < ROUND(cxp_fact.total_cuenta_pagar, 2)) THEN
									2
								ELSE
									0
							END)
		WHERE id_factura = %s;",
	valTpDato($idFactura, "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");

	//$objResponse->assign("txtIdFactura","value",$idFactura);
	/*if (isset($arrayIdDctoContabilidad)) {
		foreach ($arrayIdDctoContabilidad as $indice => $valor) {
			$idModulo = $arrayIdDctoContabilidad[$indice][1];
			$tipoDcto = $arrayIdDctoContabilidad[$indice][2];
			 //MODIFICADO ERNESTO
			if ($tipoDcto == "COMPRA") {
				$idFactura = $arrayIdDctoContabilidad[$indice][0];
				switch ($idModulo) {
					case 0 : if (function_exists("generarComprasRe")) { generarComprasRe($idFactura,"",""); } break;
					case 1 : if (function_exists("generarComprasSe")) { generarComprasSe($idFactura,"",""); } break;
					case 2 : if (function_exists("generarComprasVe")) { generarComprasVe($idFactura,"",""); } break;
					case 3 : if (function_exists("generarComprasAd")) { generarComprasAd($idFactura,"",""); } break;
				}
			}
			 //MODIFICADO ERNESTO
		}
	}*/
	
	// GUARDAR ASOCIACION DE GASTOS DE ALQUILER
	foreach($frmListaUnidadFisica['hddIdDetalleUnidadFisica'] as $indice => $valor){
		$query = sprintf("INSERT INTO al_servicio_mantenimiento_compra (id_factura, id_unidad_fisica, id_servicio_mantenimiento, costo) 
			VALUES (%s, %s, %s, %s)",
			valTpDato($idFactura,"int"), 
			valTpDato($frmListaUnidadFisica['hddIdUnidadFisica'][$indice],"int"),
			valTpDato($frmListaUnidadFisica['hddIdServicioMantenimiento'][$indice],"int"),
			valTpDato($frmListaUnidadFisica['hddCostoAsociado'][$indice],"real_inglesa"));
		$rs = mysql_query($query);
		if (!$rs) {errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);}
	}	
	
	mysql_query("COMMIT;");

	errorGuardarDcto($objResponse,__LINE__);
	
	$objResponse->alert("Registro de Compra Registrado con Exito");
	
	$comprobanteRetencion = ($frmTotalDcto['lstRetencionImpuesto'] > 0) ? 1 : 0;
	$comprobanteRetencionISLR = ($frmTotalDcto['txtTotalMontoRetencionISLR'] != 0) ? 1 : 0;
	
	$objResponse->script(sprintf("window.location.href='ga_registro_compra_formato_pdf.php?valBusq=%s|%s|%s|%s|%s';",
		$comprobanteRetencion,
		$comprobanteRetencionISLR,
		$idFactura,
		$idRetencionCabezera,
		$idISLR));
		
	return $objResponse;
}

function listadoArticulos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 8, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus_articulo = 1");
		
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("codigo_articulo = %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tipo_articulo = %s",
			valTpDato($valCadBusq[2], "text"));
	}	
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(codigo_articulo LIKE %s
		OR descripcion LIKE %s
		OR tipo_articulo LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato("%".$valCadBusq[3]."%","text"));
	}
	
	$query = sprintf("SELECT * FROM vw_ga_articulos %s", $sqlBusq);
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "15%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Código"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "50%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripción"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "25%", $pageNum, "tipo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Artículo"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "10%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, "Disponible");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .=sprintf("<a class=\"modalImg\" id=\"AgregarListArt\" rel=\"#divFlotante\" onclick=\"cargaDatosArt(%s,'AgregarListArt',this);\">",$row['id_articulo']);
					$htmlTb .= "<button type=\"button\" id=\"btnAgregarArt\" name=\"btnAgregarArt\" onclick=\"\" title=\"Agregar Art\" \">";
					$htmlTb .= "<img src=\"../img/iconos/add.png\" >";
				$htmlTb .= "</button></a>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td class=\"textoNegrita_9px\" align=\"center\">".elimCaracter(utf8_encode($row['codigo_articulo']), ";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo_articulo']."</td>";
			$htmlTb .= "<td align=\"right\" class=\"divMsjInfo\">".valTpDato($row['cantidad_disponible_logica'],"cero_por_vacio")."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("tdListadoArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listadoArticulosAlmacen($valForm) {
	$objResponse = new xajaxResponse();
	
	$sqlBusq = sprintf(" WHERE id_empresa = %s", valTpDato($valForm['lstEmpresa'], "int"));
	
	if ($valForm['lstAlmacenAct'] != "-1" && $valForm['lstAlmacenAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_almacen = %s", valTpDato($valForm['lstAlmacenAct'], "int"));
	}
	
	if ($valForm['lstCalleAct'] != "-1" && $valForm['lstCalleAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_calle = %s", valTpDato($valForm['lstCalleAct'], "int"));
	}
	
	if ($valForm['lstEstanteAct'] != "-1" && $valForm['lstEstanteAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_estante = %s", valTpDato($valForm['lstEstanteAct'], "int"));
	}
	
	if ($valForm['lstTramoAct'] != "-1" && $valForm['lstTramoAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_tramo = %s", valTpDato($valForm['lstTramoAct'], "int"));
	}
	
	if ($valForm['lstCasillaAct'] != "-1" && $valForm['lstCasillaAct'] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_casilla = %s", valTpDato($valForm['lstCasillaAct'], "int"));
	}
	
	$htmlTblIni = "<table border=\"0\" width=\"96%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"38%\">".("Código")."</td>";
		$htmlTh .= "<td width=\"50%\">".("Descripción")."</td>";
		$htmlTh .= "<td width=\"12%\">".("Exist.")."</td>";
	$htmlTh .= "</tr>";
	
	$query = sprintf("SELECT * FROM vw_ga_articulos_almacen %s ORDER BY id_articulo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s", $row['id_articulo']);
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
		if ($rowArticulo['id_articulo'] == $valForm['hddIdArticulo']) {
			$claseAnt = $clase;
			$clase = "trResaltar";
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>".elimCaracter($rowArticulo['codigo_articulo'],"-")."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($rowArticulo['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['existencia']."</td>";
		$htmlTb .= "</tr>";
		
		if ($rowArticulo['id_articulo'] == $valForm['hddIdArticulo'])
			$clase = $claseAnt;
	}
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"3\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divArticulosAlmacen","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanClienteCxC;
	
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
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lci, ci) LIKE %s
		OR CONCAT_WS('', lci, ci) LIKE %s
		OR CONCAT_Ws(' ', nombre, apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.credito
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "58%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/add.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListaCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function  listadoGastos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modo_gasto = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("afecta_documento = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		id_gasto, 
		nombre,
		estatus_iva,
		id_modo_gasto,
		afecta_documento,
		asocia_documento,
		IFNULL((SELECT GROUP_CONCAT(iva SEPARATOR '|') AS iva 
				FROM pg_gastos_impuesto
        		INNER JOIN pg_iva ON pg_iva.idIva = pg_gastos_impuesto.id_impuesto
        		WHERE tipo in (1,3,8) 
				AND pg_gastos_impuesto.id_gasto = pg_gastos.id_gasto
          		GROUP BY id_gasto), 
			(SELECT iva FROM pg_iva WHERE pg_iva.idIva = pg_gastos.id_iva)) AS iva
	FROM pg_gastos %s", $sqlBusq);

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
		$htmlTh .= "<td></td>";//<input type=\"checkbox\" id=\"cbxItmGasto\" onclick=\"seleccionarTodosCheckbox('cbxItmGasto','cbxItmClaseGasto');\"/>
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "44%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "8%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "% Impuesto");
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "14%", $pageNum, "id_modo_gasto", $campOrd, $tpOrd, $valBusq, $maxRows, "Modo");
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "12%", $pageNum, "afecta_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Afecta Documento");
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "12%", $pageNum, "asocia_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Asociar Documento");
		$htmlTh .= ordenarCampo("xajax_listadoGastos", "10%", $pageNum, "porcentaje", $campOrd, $tpOrd, $valBusq, $maxRows, ("Retenciónes"));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['estatus_iva']) {
			case 1 : $imgEstatusIva = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatusIva = "";
		}	
		switch ($row['afecta_documento']) {
			case 0 : $imgAfectaDoc = "<img src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>"; break;
			default : $imgAfectaDoc = "";
		}			
		switch ($row['id_modo_gasto']) {
			case 1 : $modoGasto = "Gastos"; break;
			case 2 : $modoGasto = "Otros Cargos"; break;
			case 3 : $modoGasto = "Gastos por Importación"; break;
			default : $modoGasto = "";
		}
//<input id=\"cbxItmGasto%s\" name=\"cbxItmGasto[]\" class=\"cbxItmClaseGasto\" type=\"checkbox\" value=\"%s\">	
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><button id=\"btnItmGasto%s\" name=\"btnItmGasto%s\" onclick=\"RecorrerForm('frmLstGasto',true);
			xajax_insertarGastos(%s, xajax.getFormValues('frmTotalDcto'));\"><img src=\"../img/iconos/add.png\"></button></td>",
				$row['id_gasto'],$row['id_gasto'],$row['id_gasto']);
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td>".$imgAfectaDoc."</td>";
						$htmlTb .= "<td align=\"right\" width=\"100%\">";
							$porcIva = explode("|",$row['iva']);
							foreach($porcIva as $indice => $valor){
								$htmlTb .= "<table width=\"100%\">";
									$htmlTb .= "<tr>";
										$htmlTb .= "<td>".$imgEstatusIva."</td>";
										$htmlTb .= "<td align=\"right\" width=\"100%\">".$valor."</td>";
									$htmlTb .= "</tr>";
								$htmlTb .= "</table>";
							}
						$htmlTb .= "</td>";
					$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($modoGasto)."</td>";
			
			$htmlTb .= "<td align=\"center\">".(($row['afecta_documento'] == 1) ? "Si" : "-")."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['asocia_documento'] == 1) ? "Si" : "-")."</td>";
			$htmlTb .= "<td align=\"center\">".(($row['porcentaje'] == NULL) ? "-" : $row['porcentaje'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoGastos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoGastos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoGastos(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoGastos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoGastos(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"9\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLstGastos","innerHTML",$htmlTableIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	return $objResponse;
}

function listImpuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	global $spanClienteCxC;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			switch($valCadBusq[0]){
				case "impuestoBloque":
					$objHTML = sprintf("<input type=\"checkbox\" id=\"checkLstImpuesto\" name=\"checkHhdIpuesto\" onclick=\"seleccionarTodosCheckbox('checkLstImpuesto','checkLstImpuesto');\"/>");
					break;
				default:
					$objHTML = "";
					break;	
			}
		}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("tipo IN (1,3,8)");
	
	$query = sprintf("SELECT idIva, iva, observacion, tipo_impuesto 
	FROM pg_iva 
		INNER JOIN pg_tipo_impuesto ON (pg_iva.tipo = pg_tipo_impuesto.id_tipo_impuesto) %s", $sqlBusq);
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
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";//
		$htmlTh .= sprintf("<td>%s</td>",$objHTML);
		$htmlTh .= ordenarCampo("xajax_listImpuesto", "8%", $pageNum, "idIva", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listImpuesto", "15%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Iva");
		$htmlTh .= ordenarCampo("xajax_listImpuesto", "50%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Observación"));
		$htmlTh .= ordenarCampo("xajax_listImpuesto", "27%", $pageNum, "tipo_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Impuesto"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			switch($valCadBusq[0]){
				case "impuestoBloque":
					$objHTML = sprintf("<input type=\"checkbox\" name=\"checkLstImpuesto[]\" class=\"checkLstImpuesto\" id=\"checkLstImpuesto\" value=\"%s\" />",
						$row['idIva']);
					break;
				case "impuestoItems":
					$objHTML = sprintf("<button id=\"btnImpuesto%s\" name=\"btnImpuesto%s\" onclick=\"RecorrerForm('frmLstImpuesto',true);xajax_insertarImpuesto(%s, xajax.getFormValues('frmDatosArticulo'))\" type=\"button\" title=\"Seleccionar\">".
							"<img src=\"../img/iconos/add.png\"/>".
						"</button>",$row['idIva'],$row['idIva'],$row['idIva']);
					break;	
			}
		}

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
		  $htmlTb .= sprintf("<td>%s</td>",$objHTML);
			$htmlTb .= "<td align=\"right\">".$row['idIva']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['iva']."%"."</td>";
			$htmlTb .= "<td>".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_impuesto'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listImpuesto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listImpuesto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListIpmuesto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;	
}

function listProveedores($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanClienteCxC;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(id_proveedor LIKE %s 
		OR rif LIKE %s
		OR nombre LIKE %s
		OR correococtacto LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM cp_proveedor %s", $sqlBusq);

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
		$htmlTh .= ordenarCampo("xajax_listProveedores", "5%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listProveedores", "15%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listProveedores", "50%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nombre Proveedor"));
		$htmlTh .= ordenarCampo("xajax_listProveedores", "30%", $pageNum, "correococtacto", $campOrd, $tpOrd, $valBusq, $maxRows, ("Correo de Contacto"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";//
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor(".$row['id_proveedor'].",'Prov');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['rif']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['correococtacto'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listProveedores(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"5\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListProveedores","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
	
}

function listadoOrdenes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf(" estatus_orden_compra = 2 ");
	
	if($valCadBusq[0] != "" && $valCadBusq[0] != ""){
		$cond = (strlen($sqlBusq) > 0) ? "AND" : "WHERE";
		$sqlBusq .= $cond.sprintf(" id_orden_compra = %s ",
			valTpDato($valCadBusq[0], "int"));
		
	
		$query = sprintf("SELECT 
			id_orden_compra,
			codigo_empresa, 
			CONCAT_WS('-',codigo_empresa,id_orden_compra) AS nume_orden_compra, 
			id_solicitud_compra,
			ga_orden_compra.id_empresa,
			nombre_empresa,
			ga_orden_compra.id_proveedor,
			nombre, 
			fecha,
			estatus_orden_compra,
			(SELECT COUNT(id_articulo) AS items FROM ga_orden_compra_detalle
				WHERE ga_orden_compra_detalle.id_orden_compra = ga_orden_compra.id_orden_compra AND estatus = 0) AS items,
	
				#CALCULO DEL TOTAL DE LA ORDEN
				((IFNULL((subtotal - subtotal_descuento),0))
					+
				(IFNULL((SELECT SUM(monto) AS total_gasto
				FROM ga_orden_compra_gasto
				WHERE ga_orden_compra_gasto.id_orden_compra = ga_orden_compra.id_orden_compra),0))
					+
				(IFNULL((SELECT SUM(subtotal_iva) AS subtotal_iva FROM ga_orden_compra_iva
				WHERE ga_orden_compra_iva.id_orden_compra = ga_orden_compra.id_orden_compra),0))) AS TotalOrden
			
			FROM ga_orden_compra
				INNER JOIN pg_empresa ON pg_empresa.id_empresa = ga_orden_compra.id_empresa
				INNER JOIN cp_proveedor ON cp_proveedor.id_proveedor = ga_orden_compra.id_proveedor %s", $sqlBusq);		
		$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		
		$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
		$rsLimit = mysql_query($queryLimit);
		if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		if ($totalRows == NULL) {
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
		}	
	}
	
	$objResponse->script("habilitar('tdListOrdenes', 'mostrarOrden', 'show')");
	
	$totalPages = ceil($totalRows/$maxRows)-1;
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td class=\"noprint\"></td>";
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "5%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Ordenes");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "5%", $pageNum, "nume_orden_compra", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Ordenes");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "60%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "5%", $pageNum, "items", $campOrd, $tpOrd, $valBusq, $maxRows, "Items");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "5%", $pageNum, "TotalOrden", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Orden");
		$htmlTh .= "<td class=\"noprint\" colspan=\"2\"></td>";
    $htmlTh .="</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch($row['estatus_orden_compra']){
			case 2: $estatus ="<img title=\"Ordenado\" src=\"../img/iconos/ico_amarillo.gif\"/>"; break;
		}

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$estatus."</td>";
			$htmlTb .= "<td align=\"left\">".$row['nombre_empresa']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['nume_orden_compra']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['items']."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['TotalOrden'],2,".",",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnAgregarSolicitud%s\" name=\"btnAgregarSolicitud%s\" onclick=\"xajax_listadoOrdenItems(0,'','','%s|%s')\">",$row['id_orden_compra'],$row['id_orden_compra'],$row['id_orden_compra'],$row['codigo_empresa']);
					$htmlTb .= "<img src=\"../img/iconos/accept.png\">";
				$htmlTb .="</button>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
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
		
	$objResponse->assign("divLstOrden","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listadoOrdenItems($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	  $sqlBusq .= $cond.sprintf("  ga_orden_compra_detalle.estatus = 0 ");
	  
	if($valCadBusq[0] != "" && $valCadBusq[0] != ""){
		$cond = (strlen($sqlBusq) > 0) ? "AND" : "WHERE";
		$sqlBusq .= $cond.sprintf(" id_orden_compra = %s ",
			valTpDato($valCadBusq[0], "int"));
	
		$query = sprintf("SELECT
			id_orden_compra_detalle,
			id_orden_compra,
			ga_orden_compra_detalle.id_articulo,
			codigo_articulo,
			descripcion,
			cantidad,
			pendiente,
			precio_unitario,
			IFNULL(IFNULL(iva,(SELECT GROUP_CONCAT(iva SEPARATOR '|') AS porcentaje_iva 
				FROM ga_orden_compra_detalle_iva
				WHERE ga_orden_compra_detalle_iva.id_orden_compra_detalle = ga_orden_compra_detalle.id_orden_compra_detalle)),0) AS iva,
			(cantidad * precio_unitario) AS total_items,
			ga_orden_compra_detalle.estatus
		FROM ga_orden_compra_detalle
			INNER JOIN ga_articulos ON ga_articulos.id_articulo = ga_orden_compra_detalle.id_articulo %s", $sqlBusq);
		
		$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

		$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
		$rsLimit = mysql_query($queryLimit);
		if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		if ($totalRows == NULL) {
			$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
		}
	}
	
	$totalPages = ceil($totalRows/$maxRows)-1;
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td class=\"noprint\"></td>";
		$htmlTh .= ordenarCampo("xajax_listadoOrdenItems", "10%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Cod. Articulo");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenItems", "60%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion Articulo");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenItems", "5%", $pageNum, "cantidad", $campOrd, $tpOrd, $valBusq, $maxRows, "Ped");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenItems", "5%", $pageNum, "pendiente", $campOrd, $tpOrd, $valBusq, $maxRows, "Pend");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenItems", "5%", $pageNum, "precio_unitario", $campOrd, $tpOrd, $valBusq, $maxRows, "Costo");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenItems", "10%", $pageNum, "iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenItems", "15%", $pageNum, "total_items", $campOrd, $tpOrd, $valBusq, $maxRows, "SubTotal");
		$htmlTh .= "<td class=\"noprint\" colspan=\"2\"></td>";
    $htmlTh .="</tr>";
	
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch($row['estatus']){
			case 1: $estatus ="<img title=\"Recibido\" src=\"../img/iconos/ico_aceptar.gif\"/>"; break;
			default: $estatus ="<img title=\"En espera\" src=\"../img/iconos/ico_aceptar_amarillo.png\"/>"; break;
		}
		
		$tblImpuesto="";
		$arrayIva = explode("|",$row['iva']);
		foreach($arrayIva as $indece => $valor){
			$tblImpuesto .= sprintf("<table>	
										<tr>
											<td>%s</td>
											<td>%s</td>
										</tr>
								</table>", number_format($valor,2,".",","),"%");
		}
		$objResponse->script("habilitar('tdListItemsOrden', 'mostrarOrden', 'show')");
		$objResponse->assign("lgdOrden","innerHTML","Item/s de la Orden (".$valCadBusq[1]."-".$valCadBusq[0].")");

		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$estatus."</td>";
			$htmlTb .= "<td align=\"center\">".$row['codigo_articulo']."</td>";
			$htmlTb .= "<td align=\"left\">".htmlentities($row['descripcion'])."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['cantidad'],2,".",",")."</td>";
			$htmlTb .= "<td>".number_format($row['pendiente'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['precio_unitario'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"center\">".$tblImpuesto."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['total_items'],2,".",",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnAgregarSolicitud%s\" name=\"btnAgregarSolicitud%s\" 
				onclick=\"xajax_validarArt(xajax.getFormValues('frmListaArticulo'),%s,%s,xajax.getFormValues('frmDcto'))\">",
					$row['id_articulo'],$row['id_articulo'],$row['id_articulo'],$row['id_orden_compra_detalle']);
					$htmlTb .= "<img src=\"../img/iconos/accept.png\">";
				$htmlTb .="</button>";
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenItems(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenItems(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoOrdenItems(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf.="selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenItems(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenItems(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
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
		
	$objResponse->assign("divLstOrdenDetalle","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaServicioMantenimiento($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanKilometraje;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("activo = 1");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_servicio_mantenimiento IN (SELECT id_servicio_mantenimiento 
																FROM al_servicio_mantenimiento_marca
																WHERE id_marca IN (SELECT an_uni_bas.mar_uni_bas
																					FROM an_unidad_fisica
																					INNER JOIN an_uni_bas ON (an_unidad_fisica.id_uni_bas = an_uni_bas.id_uni_bas)
																					WHERE id_unidad_fisica = %s))",
		valTpDato($valCadBusq[1], "int"));
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(descripcion_servicio_mantenimiento LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM al_servicio_mantenimiento %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__.$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaServicioMantenimiento", "25%", $pageNum, "descripcion_servicio_mantenimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Servicio / Mantenimiento");
		$htmlTh .= ordenarCampo("xajax_listaServicioMantenimiento", "10%", $pageNum, "kilometraje", $campOrd, $tpOrd, $valBusq, $maxRows, $spanKilometraje);
		$htmlTh .= ordenarCampo("xajax_listaServicioMantenimiento", "10%", $pageNum, "kilometraje_antes", $campOrd, $tpOrd, $valBusq, $maxRows, $spanKilometraje." Antes");
		$htmlTh .= ordenarCampo("xajax_listaServicioMantenimiento", "10%", $pageNum, "kilometraje_despues", $campOrd, $tpOrd, $valBusq, $maxRows, $spanKilometraje." Después");
	$htmlTh .= "</tr>";	
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><button title=\"Seleccionar\" onclick=\"xajax_asignarServicioMantenimiento(%s);\" type=\"button\"><img src=\"../img/iconos/tick.png\"></button></td>",
						$row['id_servicio_mantenimiento']);
			$htmlTb .= "<td>".utf8_encode($row['descripcion_servicio_mantenimiento'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['kilometraje'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['kilometraje_antes'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['kilometraje_despues'])."</td>";
		$htmlTb .= "</tr>";
	}	
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"15\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaServicioMantenimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaServicioMantenimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaServicioMantenimiento(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf.="</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaServicioMantenimiento(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaServicioMantenimiento(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"15\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoServicioMantenimiento","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listaUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 5, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	// TRANSITO, POR REGISTRAR, SINIESTRADO, DISPONIBLE, RESERVADO, VENDIDO, ENTREGADO, PRESTADO, ACTIVO FIJO, INTERCAMBIO, DEVUELTO, ERROR DE TRASPASO
	//$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	//$sqlBusq .= $cond.sprintf("uni_fis.estado_venta IN ('ACTIVO FIJO')");
		
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(alm.id_empresa = %s
	OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = alm.id_empresa))",
		valTpDato($valCadBusq[0], "int"),
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(uni_fis.id_unidad_fisica LIKE %s
		OR vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s
		OR uni_fis.serial_motor LIKE %s
		OR uni_fis.serial_carroceria LIKE %s
		OR uni_fis.placa LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("uni_fis.id_unidad_fisica NOT IN(%s)",
			valTpDato($valCadBusq[2], "campo"));
	}

	
	$query = sprintf("SELECT
		uni_fis.id_unidad_fisica,
		uni_fis.id_activo_fijo,
		uni_fis.serial_carroceria,
		uni_fis.serial_motor,
		uni_fis.placa,
		(CASE uni_fis.id_condicion_unidad
			WHEN 1 THEN	'NUEVO'
			WHEN 2 THEN	'USADO'
			WHEN 3 THEN	'USADO PARTICULAR'
		END) AS condicion_unidad,
		color_ext.nom_color AS color_externo1,
		color_int.nom_color AS color_interno1,
		clase.nom_clase,
		uni_fis.estado_compra,
		uni_fis.estado_venta,
		alm.nom_almacen,
		CONCAT(vw_iv_modelo.nom_uni_bas, ': ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' - ', vw_iv_modelo.nom_version) AS vehiculo,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM an_unidad_fisica uni_fis
	INNER JOIN an_almacen alm ON (uni_fis.id_almacen = alm.id_almacen)
	INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_fis.id_uni_bas = vw_iv_modelo.id_uni_bas)
	INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
	INNER JOIN an_clase clase ON (uni_bas.cla_uni_bas = clase.id_clase)
	INNER JOIN an_color color_ext ON (uni_fis.id_color_externo1 = color_ext.id_color)
	INNER JOIN an_color color_int ON (uni_fis.id_color_interno1 = color_int.id_color)
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
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "1%", $pageNum, "id_unidad_fisica", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Uni");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "8%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanPlaca));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanSerialCarroceria));
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "12%", $pageNum, "color_ext.nom_color", $campOrd, $tpOrd, $valBusq, $maxRows, "Color");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "32%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Unidad");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "20%", $pageNum, "alm.id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaUnidadFisica", "15%", $pageNum, "nom_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Almac&eacute;n");
	$htmlTh .= "</tr>";	

	$contFila = 0;
	while ($rowUnidadFisica = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($rowUnidadFisica['estado_venta']) {
			case "SINIESTRADO" : $class = "class=\"divMsjError\""; break;
			case "DISPONIBLE" : $class = "class=\"divMsjInfo\""; break;
			case "RESERVADO" : $class = "class=\"divMsjAlerta\""; break;
			case "VENDIDO" : $class = "class=\"divMsjInfo3\""; break;
			case "ENTREGADO" : $class = "class=\"divMsjInfo4\""; break;
			case "ACTIVO FIJO" : $class = "class=\"divMsjInfo5\""; break;
			default : $class = ""; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= sprintf("<td><button title=\"Seleccionar\" onclick=\"xajax_asignarUnidadFisica(%s);\" type=\"button\"><img src=\"../img/iconos/tick.png\"></button></td>",
						$rowUnidadFisica['id_unidad_fisica']);
			$htmlTb .= "<td align=\"center\">".($rowUnidadFisica['id_unidad_fisica'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowUnidadFisica['placa'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['serial_carroceria'])."</td>";
				$htmlTb .= "</tr>";					
				$htmlTb .= "<tr class=\"textoNegrita_10px\">";
					$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['condicion_unidad'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['color_externo1'])."</td>";				
			$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['vehiculo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($rowUnidadFisica['nom_almacen'])."</td>";
		$htmlTb .= "</tr>";
	}	
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"15\">";
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
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"15\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoUnidadFisica","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function nuevoDcto() {
	$objResponse = new xajaxResponse();
	
	$querEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",$_SESSION['idEmpresaUsuarioSysGts']);
	$rsEmpre = mysql_query($querEmp);
	if(!$rsEmpre) return $objResponse->alert(mysql_query()."\nError Nro.: " .mysql_errno()."\nLine: ".__LINE__);
	$rowsEemp = mysql_fetch_assoc($rsEmpre);
	
	if ($rowsEemp['contribuyente_especial'] == 1) {
		$objResponse->loadCommands(cargaLstRetencionImpuesto());
		$objResponse->script("byId('trRetencionIva').style.display = '';");
	} else {
		$objResponse->loadCommands(cargaLstRetencionImpuesto(0));
		$objResponse->script("byId('trRetencionIva').style.display = 'none';");
	}
	
	$objResponse->assign("txtIdEmpresa","value",$rowsEemp['id_empresa']);
	$objResponse->assign("txtEmpresa","value",$rowsEemp['nombre_empresa']);
	$objResponse->assign("txtFechaRegistroCompra","value",date(spanDateFormat));
	$objResponse->loadCommands(cargaLstTipoMovimiento());
	$objResponse->loadCommands(cargaLstClaveMovimiento(1));
	$objResponse->loadCommands(cargaLstRetencionISLR());
	
	$objResponse->assign("txtDescuento","value",number_format(0, 2, ".", ","));
	
	$objResponse->script("habilitar('btnAgregarProv', 'btnAgregarProv', 'show');");
	
	$btn ="<a class=\"modalImg\" id=\"AgregarArt\" rel=\"#divFlotante4\" onclick=\"abrirDivFlotante('agregar',this);\">
			<button type=\"button\" id=\"btnAgregarArt\" name=\"btnAgregarArt\" style=\"cursor:default\" title=\"Agregar Articulo\">
				<table align=\"center\" cellpadding=\"0\" cellspacing=\"0\">
					<tr><td>&nbsp;</td><td><img src=\"../img/iconos/add.png\"/></td><td>&nbsp;</td><td>Agregar</td></tr>
				</table>
			</button>
		</a>";
	$objResponse->assign("divBtnAgregar","innerHTML",$btn);
	
	// INSERTA EL GASTO EN CERO "0"
	$Result1 = insertarItemGastos(0,"");
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
	}

	return $objResponse;
}

function validarArt($varlfrmListaArticulo,$varlfrmDatosArticulo, $numOrdenDetalle = NULL, $varlDoct = NULL){
	$objResponse = new xajaxResponse();

	if (isset($varlfrmListaArticulo['cbx'])) {
		$arrayArtExi = array();
		foreach($varlfrmListaArticulo['cbx'] as $indiceItm => $valorItm) {
			$arrayArtExi[] = $varlfrmListaArticulo['hddIdArt'.$valorItm];
		}
	}

	$IdArt = ($varlfrmDatosArticulo['hddIdArt'] != "") ? $varlfrmDatosArticulo['hddIdArt'] : $varlfrmDatosArticulo ;
	if(in_array($IdArt,$arrayArtExi)){
		return $objResponse->alert("Este articulo ya esta existe en la factura");
	}
	if($numOrdenDetalle == NULL){
		$objResponse->script("xajax_AgregarArticulo(xajax.getFormValues('frmDatosArticulo'),xajax.getFormValues('frmListaArticulo'));");	
	}else{
		$sqlValidar = sprintf("SELECT ga_orden_compra_detalle.id_orden_compra,
		  (SELECT id_proveedor FROM ga_orden_compra
			WHERE ga_orden_compra.id_orden_compra = ga_orden_compra_detalle.id_orden_compra) AS proveedore
		  FROM ga_orden_compra_detalle  WHERE id_orden_compra_detalle =  %s;",
			valTpDato($numOrdenDetalle, "int"));
		$rsValidar = mysql_query($sqlValidar);
		if(!$rsValidar) return $objResponse->alert(mysql_query()."\nError Nro.: " .mysql_errno()."\nLine: ".__LINE__);
		$rowsValidar = mysql_fetch_assoc($rsValidar);
		
		if($varlDoct['txtIdProv'] != $rowsValidar['proveedore']) return $objResponse->alert("Los proveedores de la orden no deben ser distintos");

		$objResponse->script(sprintf("xajax_AgregarArticulo(%s,xajax.getFormValues('frmListaArticulo'));",
			$numOrdenDetalle));
	}
	
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"AgregarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarAlmacen");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarFechaRegistro");
$xajax->register(XAJAX_FUNCTION,"asignarServicioMantenimiento");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadFisica");

$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"BuscarCliente");
$xajax->register(XAJAX_FUNCTION,"BuscarGastos");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarNumOrden");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"buscarServicioMantenimiento");

$xajax->register(XAJAX_FUNCTION,"cargaLst");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoArt");
$xajax->register(XAJAX_FUNCTION,"cargaLstRetencionImpuesto");
$xajax->register(XAJAX_FUNCTION,"cargaLstRetencionISLR");

$xajax->register(XAJAX_FUNCTION,"cargaLstUbicacion");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"calcularImpuesto");
$xajax->register(XAJAX_FUNCTION,"calcularMontoGastos");
$xajax->register(XAJAX_FUNCTION,"calcularMontoRetencionISLR");

$xajax->register(XAJAX_FUNCTION,"editarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarGastos");
$xajax->register(XAJAX_FUNCTION,"eliminarImpuesto");

$xajax->register(XAJAX_FUNCTION,"formAlmacen");

$xajax->register(XAJAX_FUNCTION,"guardarDcto");

$xajax->register(XAJAX_FUNCTION,"insertarGastos");
$xajax->register(XAJAX_FUNCTION,"insertarImpuestoBloque");
$xajax->register(XAJAX_FUNCTION,"insertarImpuesto");
$xajax->register(XAJAX_FUNCTION,"insertarUnidadFisica");

$xajax->register(XAJAX_FUNCTION,"listadoArticulosAlmacen");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listadoGastos");
$xajax->register(XAJAX_FUNCTION,"listadoArticulos");
$xajax->register(XAJAX_FUNCTION,"listProveedores");
$xajax->register(XAJAX_FUNCTION,"listadoOrdenes");
$xajax->register(XAJAX_FUNCTION,"listadoOrdenItems");
$xajax->register(XAJAX_FUNCTION,"listImpuesto");
$xajax->register(XAJAX_FUNCTION,"listaServicioMantenimiento");
$xajax->register(XAJAX_FUNCTION,"listaUnidadFisica");

$xajax->register(XAJAX_FUNCTION,"nuevoDcto");

$xajax->register(XAJAX_FUNCTION,"validarArt");

function buscarEnArray($arrays, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x=0;
	foreach ($arrays as $indice=>$valor) {
		if($valor == $dato)
			return $x;
		
		$x++;
	}
	return null;
}

function errorGuardarDcto($objResponse, $line) {
	//$objResponse->script(sprintf("alert('%s');",$line));
	$objResponse->script("
		RecorrerForm('frmDcto',false);
		RecorrerForm('frmListaArticulo',false);
		RecorrerForm('frmTotalDcto',false);
	");
}

function insertarItemImpuesto($contFila, $idImpuesto = "", $idTipoImpuesto = "", $idImpuestoPredeterminado = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idImpuesto != "" && $idImpuesto != 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("idIva = %s",
			valTpDato($idImpuesto, "int"));
	}
	
	if ($idTipoImpuesto != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo = %s",
			valTpDato($idTipoImpuesto, "int"));
	}
	
	if ($idImpuestoPredeterminado != "" && $idTipoImpuesto != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = %s",
			valTpDato(1, "int"));
	}
	
	if ($idImpuestoPredeterminado != "" && $idTipoImpuesto != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estado = %s",
			valTpDato(1, "int"));
	}
	
	// BUSCA LOS DATOS DEL TIPO DE ORDEN
	$query = sprintf("SELECT idIva,tipo,tipo_impuesto,observacion,iva,activo,estado FROM pg_iva 
		INNER JOIN pg_tipo_impuesto ON pg_tipo_impuesto.id_tipo_impuesto = pg_iva.tipo %s", $sqlBusq,
			valTpDato($idImpuesto, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL IMPUESTO MEDIANTE INJECT
	if($idImpuesto > 0){
		$htmlItmPie = sprintf("$('#trItemArtImpuesto').before('".
		"<tr id=\"trItemsImpuesto:%s\" align=\"left\" class=\"textoGris_11px %s\" title=\"trItemsImpuesto:%s\">".
			"<td align=\"center\" >".
				"<input id=\"cbxItmImpuesto\" name=\"cbxItmImpuesto[]\" class=\"cbxItmImpuesto\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxItmImpuesto2\" name=\"cbxItmImpuesto2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
				"<input id=\"textItmImpuestoId%s\" name=\"textItmImpuestoId%s\" type=\"hidden\" value=\"%s\">".
				"<input id=\"textItmImpuestoIva%s\" name=\"textItmImpuestoIva%s\" type=\"hidden\"  value=\"%s\">".
			"</td>".
			"<td align=\"center\">%s</td>".
			"<td align=\"center\">%s</td>".
		"</tr>');",
		$contFila, $clase, $contFila,//tr
			$contFila,//check
			$contFila,//check
			$contFila,$contFila,$row['idIva'],
			$contFila,$contFila,$row['iva'],
		$contFila,				
			$row['iva']."%");
	}else{
		$htmlItmPie = sprintf("$('#trItemArtImpuesto').before('".
		"<tr id=\"trItemsImpuesto:%s\" align=\"left\" title=\"trItemsImpuesto:%s\">".
			"<td align=\"center\" colspan=\"3\">".
				"<input id=\"cbxItmImpuesto2\" name=\"cbxItmImpuesto2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
				"<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo\" width=\"%s\">".
					"<tr><td width=\"25\"><img src=\"../img/iconos/ico_info2.gif\" width=\"25\"/></td>".
					"<td align=\"center\">No aplica Impuesto</td></tr>".
				"</table>".
			"</td>".
		"</tr>');",
			0,0,
				0,
				"100%");
	}

	return array(true, $htmlItmPie, $contFila, $query);
}

function insertarItemGastos($contFila, $idGasto = ""){
	
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++; 
	
	//CONSULTA LOS DATOS DEL GASTO 
	$sqlGastos = sprintf("SELECT id_gasto, nombre,estatus_iva,id_modo_gasto, afecta_documento, asocia_documento,
					IFNULL((SELECT GROUP_CONCAT(idIva SEPARATOR '|') AS id_iva 
				FROM pg_gastos_impuesto
					INNER JOIN pg_iva ON pg_iva.idIva = pg_gastos_impuesto.id_impuesto
					WHERE tipo in (1,3,8) AND pg_gastos_impuesto.id_gasto = pg_gastos.id_gasto
				GROUP BY id_gasto), (
					SELECT idIva FROM pg_iva WHERE pg_iva.idIva = pg_gastos.id_iva)) AS id_iva,
						IFNULL((SELECT GROUP_CONCAT(iva SEPARATOR '|') AS iva 
					FROM pg_gastos_impuesto
						INNER JOIN pg_iva ON pg_iva.idIva = pg_gastos_impuesto.id_impuesto
						WHERE tipo in (1,3,8) AND pg_gastos_impuesto.id_gasto = pg_gastos.id_gasto
					GROUP BY id_gasto), (
						SELECT iva FROM pg_iva WHERE pg_iva.idIva = pg_gastos.id_iva)) AS iva
				FROM pg_gastos
					WHERE id_gasto = %s",
		valTpDato($idGasto,  "int"));
	$rsGatos = mysql_query($sqlGastos);
	if (!$rsGatos) {array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);}
	$rowsGastos = mysql_fetch_assoc($rsGatos);
	
	if ($idGasto != "" || $idGasto != 0) {
	// PARA MOSTRAR EL PORCENTAJE DEL IVA
		$arrayIvaPorc = explode("|",$rowsGastos['iva']);
		foreach($arrayIvaPorc as $indicePorcIva => $valorPorcIva) {
			if($valorPorcIva != 0 || $valorPorcIva != ""){
				$MuestraPorcIva .= sprintf("<table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\">".
					"<tr><td><img id=\"imgIvaGasto%s\" src=\"../img/iconos/accept.png\" title=\"Aplica impuesto\"/></td>".
						"<td>%s</td></tr></table>",
				'100%',
					$indicePorcIva,
				$valorPorcIva."%");
			} else {
				$MuestraPorcIva = "";
			}
		}
		
		if ($rowsGastos['id_modo_gasto'] == 1 && $rowsGastos['afecta_documento'] == 0) {
			$imgAfectaDoc =sprintf("<img id=\"imgNoAfectaCxP%s\" src=\"../img/iconos/stop.png\" title=\"No afecta cuenta por pagar\"/>",
				$rowsGastos['id_gasto']);	
		} else {
			$imgAfectaDoc = "";	
		}
		
		// TR GASTOS
		$htmlItmPie = sprintf("$('#trItmPieGastos').before('".
		"<tr id=\"trItmGasto%s\" class=\"%s textoGris_11px\" align=\"right\">".
			"<td align=\"center\">".
				"<input type=\"checkbox\" id=\"checkItemGasto%s\" name=\"checkItemGasto[]\" class=\"checkItemClaseGasto\" value = \"%s\"/>".
				"<input id=\"hddItemsGasto%s\" type=\"hidden\" value=\"%s\" name=\"hddItemsGasto[]\">".
				"<input type=\"checkbox\" id=\"checkHddnGasto%s\" name=\"checkHddnGasto%s\" checked=\"checked\" style=\"display:none\" value = \"%s\"/>".
			"</td>".
			"<td class=\"textoNegrita_9px\" align=\"center\">%s</td>".
			"<td class=\"tituloCampo\">%s:".
				"<input id=\"hddIdGasto%s\" type=\"hidden\" value=\"%s\" name=\"hddIdGasto%s\">".
				"<input id=\"hddTipoGasto%s\" type=\"hidden\" value=\"%s\" name=\"hddTipoGasto%s\">".
			"</td>".
			"<td align=\"center\">".
				"<input  name=\"txtPorcGasto%s\" id=\"txtPorcGasto%s\" type=\"text\" value=\"0.00\" style=\"text-align:right\" size=\"6\" maxlength=\"8\" class=\"inputInicial\" >%s".
			"</td>".
			"<td align=\"center\">".
				"<input name=\"txtMontoGasto%s\" id=\"txtMontoGasto%s\" type=\"text\" class=\"inputHabilitado\" value=\"0.00\" style=\"text-align:right\" size=\"\" maxlength=\"12\" >".
			"</td>".
			"<td >".
				"<div id=\"divImpuesto%s\" align=\"right\">%s</div>".
				"<input id=\"hddIdIvaGasto%s\" name=\"hddIdIvaGasto%s\" type=\"hidden\" value=\"%s\" >".
				"<input id=\"hddIvaGasto%s\" name=\"hddIvaGasto%s\" type=\"hidden\" value=\"%s\" >".
				"<input id=\"hddEstatusIvaGasto%s\" name=\"hddEstatusIvaGasto%s\" type=\"hidden\" value=\"%s\" >".
			"</td>".
			"<td>%s</td>".
		"</tr>');
				byId('txtPorcGasto%s').onblur = function() {
					setFormatoRafk(this,2);
					xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Cant', this.value, 'txtMontoGasto%s');
				} 							   
				byId('txtPorcGasto%s').onfocus = function() {
					if (byId('txtPorcGasto%s').value <= 0){
						byId('txtPorcGasto%s').select();
					}	
				}
				byId('txtPorcGasto%s').onkeypress = function(event) {
					return validarSoloNumerosReales(event);	
				}

				byId('txtMontoGasto%s').onblur = function() {
					setFormatoRafk(this,2);
						xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Cant', this.value, 'txtPorcGasto%s');
					
					xajax_calcularMontoGastos(xajax.getFormValues('frmTotalDcto'));
				}
				byId('txtMontoGasto%s').onfocus = function() {
					if (byId('txtMontoGasto%s').value <= 0){
						byId('txtMontoGasto%s').select();
					}	
				}
				byId('txtMontoGasto%s').onkeypress = function(event) {
					return validarSoloNumerosReales(event);}",
			$contFila,$clase,//tr
				$contFila,$contFila,//checkGastoItemFactura
				$contFila,$contFila,
				$contFila,$contFila,$rowsGastos['id_gasto'],//checkHddnGasto
				$contFila,
			$rowsGastos['nombre'],//nombreGasto
				$contFila,$rowsGastos['id_gasto'],$contFila,//hddIdGasto
				$contFila,$rowsGastos['id_modo_gasto'],$contFila,//hddTipoGasto
				$contFila,$contFila,"%",//porcInput
				$contFila,$contFila,//montoInput
				$contFila,$MuestraPorcIva,//divImpuesto
				$contFila,$contFila,$rowsGastos['id_iva'],//hddIdIvaGasto
				$contFila,$contFila,$rowsGastos['iva'],//hddIvaGasto
				$contFila,$contFila,$rowsGastos['estatus_iva'],//hddEstatusIvaGasto
				$imgAfectaDoc,//imgAfectaDoc
					$contFila,//funciones onblur
					$contFila,$contFila,
					$contFila,//funciones onfocus
					$contFila,
					$contFila,
					$contFila,//funciones onkeypress
					$contFila,//funciones onblur
					$contFila,
					$contFila,//funciones onfocus
					$contFila,
					$contFila,
					$contFila//funciones onkeypress
				);
	} else {
		$htmlItmPie = sprintf("$('#trItmPieGastos').before('".
		"<tr id=\"trItmGasto0\">".
			"<td colspan=\"7\">".
				"<input id=\"hddItemsGasto0\" type=\"hidden\" value=\"0\" name=\"hddItemsGasto[]\">".
				"<input type=\"checkbox\" id=\"checkItemGasto0\" name=\"checkItemGasto[]\" checked=\"checked\" style=\"display:none\" value = \"0\"/>".
				"<table width=\"%s\" class=\"divMsjInfo\">".
					"<tr>".
						"<td width=\"25\"><img width=\"25\" src=\"../img/iconos/ico_info2.gif\"></td>".
						"<td align=\"center\">No Aplica Gasto</td>".
					"</tr>".
				"</table>".	
			"</td>".
		"</tr>');","100%");
	}
	
	return array(true, $htmlItmPie, $contFila, $sqlGastos);
}

function sanear_string($string){//ELIMINAR CARACTERES ESPECIALES

    $string = trim($string);

    $string = str_replace(
        array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
        array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
        $string
    );

    $string = str_replace(
        array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
        array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
        $string
    );

    $string = str_replace(
        array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
        array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
        $string
    );

    $string = str_replace(
        array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
        array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
        $string
    );

    $string = str_replace(
        array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
        array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
        $string
    );

    $string = str_replace(
        array('ñ', 'Ñ', 'ç', 'Ç'),
        array('n', 'N', 'c', 'C',),
        $string
    );
	
    //Esta parte se encarga de eliminar cualquier caracter extraño
    $string = str_replace(
        array("\\", "¨", "º", "-", "~", "#", "@", "|", "!", "\"", "·", "$", "%", "&", /*"/",*/ "(", ")", "?",
		   "'","¡", "¿","[", "^", "`", "]","+", "}", "{", "¨", "´",">", "< ", ";", /*",",*/ ":","."/*, " "*/),
		'',
        $string
    );

    return $string;
}

?>