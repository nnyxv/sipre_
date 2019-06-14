<?php
include("../clases/num2letras.php");
function asignarArticulo($idPedidoDet, $numeroArt, $valForm) {
	$objResponse = new xajaxResponse();

	//CONSULTA EL DETALLE DE SOLICITUD
	$queryPedidoDet = sprintf("SELECT * FROM ga_detalle_solicitud_compra WHERE id_detalle_solicitud_compra = %s",
		valTpDato($idPedidoDet,"text"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowPedidoDet = mysql_fetch_assoc($rsPedidoDet);
	
	//CONSULTA LA DESCRIPCION DEL ARTI
	$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s",
		valTpDato($rowPedidoDet['id_articulo'],"text"));
	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);
	
	//CONSULTA LOS DATOS DEL CLIENTE PARA SABER A QUE CLIETNE SE LE RESERBO EL ART
	$queryCliente = sprintf("SELECT * FROM cj_cc_cliente WHERE id = %s",
		valTpDato($valForm['hddIdClienteArtItm'.$numeroArt],"text"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$fechaUltimaCompra = ($rowArticulo['fecha_ultima_compra'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_compra'])) : "xx-xx-xxxx";
	$fechaUltimaVenta = ($rowArticulo['fecha_ultima_venta'] != "") ? date(spanDateFormat,strtotime($rowArticulo['fecha_ultima_venta'])) : "xx-xx-xxxx";
	
	$objResponse->assign("hddIdArt","value",$rowArticulo['id_articulo']);
	$objResponse->assign("txtCodigoArt","value",utf8_encode($rowArticulo['codigo_articulo']));
	$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
	$objResponse->assign("txtFechaUltCompraArt","value",$fechaUltimaCompra);
	$objResponse->assign("txtSeccionArt","value",$rowArticulo['descripcion_seccion']);
	$objResponse->assign("txtFechaUltVentaArt","value",$fechaUltimaVenta);
	$objResponse->assign("txtTipoPiezaArt","value",$rowArticulo['tipo_articulo']);
	$objResponse->assign("txtExistencia","value",$rowArticulo['existencia']);
	
	$objResponse->assign("txtCantidadArt","value",$valForm['hddCantArtItm:'.$numeroArt]);
	$objResponse->assign("txtCostoArt","value",$valForm['hddCostoArtItm:'.$numeroArt]);
	
	$arrayIdIva = explode("|",$valForm['hddIdIvaArtImt:'.$numeroArt]);
	//RECORRO EL ARRAY QUE CONTIEN LA CADENA
	foreach($arrayIdIva as $indiceArrayIdIva => $valorIdImpuesto){
		$Result1 = insertarItemImpuesto($contFila, $valorIdImpuesto);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		} else if ($Result1[0] == true) {
			$contFila = $Result1[2];
			$objResponse->script($Result1[1]);
			$arrayObj[] = $contFila;
		}
	}
	
	if ($valForm['hddTipoArtItm:'.$numeroArt] == 0) {
		$objResponse->script("
		byId('rbtTipoArtReposicion').checked = true;
		byId('btnInsertarClienteArt').style.display = 'none';");
	} else {
		$objResponse->script("
		byId('rbtTipoArtCliente').checked = true;
		byId('btnInsertarClienteArt').style.display = '';");
	}
	
	$objResponse->assign("txtIdClienteArt","value",$rowCliente['id']);
	$objResponse->assign("txtNombreClienteArt","value",$rowCliente['nombre']." ".$rowCliente['apellido']);
	
	$objResponse->assign("hddNumeroArt","value",$numeroArt);
	
	$objResponse->script("xajax_calcularImpuesto(xajax.getFormValues('frmDatosArticulo'))");
	
	return $objResponse;
}

function asignarContacto($idEmpleado) {
	$objResponse = new xajaxResponse();
	
	$queryEmpleado = sprintf("SELECT * FROM vw_pg_empleados WHERE id_empleado = %s",
		valTpDato($idEmpleado,"int"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("txtCargo","value",$rowEmpleado['nombre_cargo']);
	$objResponse->assign("txtEmail","value",$rowEmpleado['email']);
	
	return $objResponse;
}

function asignarEmpleado($idEmpleado, $valBus) {
	$objResponse = new xajaxResponse();
	
	$queryEmpleado = sprintf("SELECT * FROM vw_pg_empleados WHERE id_empleado = %s", valTpDato($idEmpleado,"text"));
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("textId".$valBus['txtBuscarEmpleado'],"value",$rowEmpleado['id_empleado']);
	$objResponse->assign("textNombre".$valBus['txtBuscarEmpleado'],"value",utf8_encode($rowEmpleado['nombre_empleado']));
	
	if($valBus['txtBuscarEmpleado'] == "Contacto"){
		$objResponse->assign("txtCargo","value",$rowEmpleado['nombre_cargo']);
		$objResponse->assign("txtEmail","value",$rowEmpleado['email']);
	}
		
	$objResponse->script("byId('btnCerraListEmpleado').click();");
	
	return $objResponse;
}

function asignarCliente($idCliente) {
	$objResponse = new xajaxResponse();
	
	$queryCliente = sprintf("SELECT * FROM cj_cc_cliente WHERE id = %s", valTpDato($idCliente,"text"));
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);
	
	$objResponse->assign("txtIdClienteArt","value",$rowCliente['id']);
	$objResponse->assign("txtNombreClienteArt","value",$rowCliente['nombre']." ".$rowCliente['apellido']);
		
	$objResponse->script("byId('btnCerraListCliente').click();");
	
	return $objResponse;
}

function asignarProveedor($idProveedor) {
	$objResponse = new xajaxResponse();
	
	$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = %s", valTpDato($idProveedor,"text"));
	$rsProveedor = mysql_query($queryProveedor);
	if (!$rsProveedor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowProveedor = mysql_fetch_assoc($rsProveedor);
	
	$objResponse->assign("txtIdProv","value",$rowProveedor['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",utf8_encode($rowProveedor['nombre']));
	$objResponse->assign("txtRifProv","value",$rowProveedor['lrif'].$rowProveedor['rif']);
	$objResponse->assign("txtContactoProv","value",utf8_encode($rowProveedor['contacto']));
	$objResponse->assign("txtCargoContactoProv","value","");
	$objResponse->assign("txtEmailContactoProv","value",utf8_encode($rowProveedor['correococtacto']));
	$objResponse->assign("txtDireccionProv","innerHTML",utf8_encode($rowProveedor['direccion']));
	$objResponse->assign("txtTelefonosProv","value",$rowProveedor['telefono']);
	$objResponse->assign("txtFaxProv","value",$rowProveedor['fax']);
	
	$objResponse->assign("txtDescuento","value",$rowProveedor['descuento']);
	
	$objResponse->script("byId('btnCerraLstProvee').click();");
	
	$objResponse->script("xajax_calcularDcto(
								xajax.getFormValues('frmPedido'),
								xajax.getFormValues('frmListaArticulo'),
								xajax.getFormValues('frmTotalDcto'));
	");
	
	return $objResponse;
}

function buscarEmpleado($frmBuscarEmpleado, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarEmpleado['txtCriterioBuscarEmpleado']);

	$objResponse->loadCommands(listaEmpleado(0, "", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarCliente($frmBuscarCliente, $frmDcto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmDcto['txtIdEmpresa'],
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarProveedor($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$valForm['txtCriterioBuscarProveedor']);
	
	$objResponse->loadCommands(listaProveedor(0, "", "", $valBusq));
		
	return $objResponse;
}

function calcularDcto($frmDcto, $frmListaArticulo, $frmTotalDcto){
	$objResponse = new xajaxResponse();
	
	//RECORRE LOS IVAS DEL DOCUMENTO PARA ELIMINARLOS
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
	if (isset($frmListaArticulo['hddcbxItm'])) {
		foreach($frmListaArticulo['hddcbxItm'] as $indiceItm => $valorItm) {
			$clase = (fmod($i, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$i++;
			$arrayObj[] = $valorItm;
			$objResponse->assign("trItm:".$valorItm,"className",$clase);
			$objResponse->assign("tdNumItm:".$valorItm,"innerHTML",$i);//NUM ITEM EN EL TD
		}
		$hddObj = implode("|", $arrayObj);
	}
	$objResponse->assign("hddObj","value",$hddObj); // NUM ITEMS EN INPUT
	
	//VERIFICA LOS VALORES DE CADA ITEM, PARA SACAR EL IVA Y EL SUBTOTAL
	$subTotal = 0;
	$totalExento = 0;
	$totalExonerado = 0;
	$arrayIva = NULL;
	$arrayDetalleIva = NULL;
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			
			//CORTO LA CADENA PARA SABER LOS ID E IVA DE CADA ITEMS
			$cadenaIdIva = explode("|",$frmListaArticulo['hddIdIvaArtImt:'.$valor]);
			$cadenaIva = explode("|",$frmListaArticulo['hddIvaArtItm:'.$valor]);
			$arrayIdIva = array();
			$arrayPorcIva = array();
			foreach($cadenaIdIva as $indiceCadena => $valorCadena){
				$arrayIdIva []= $valorCadena;
				$arrayPorcIva []= $cadenaIva[$indiceCadena];
			}

			//TOTAL DE ART
			$subTotalArt = ($frmListaArticulo['hddCantArtItm:'.$valor] * $frmListaArticulo['hddCostoArtItm:'.$valor]);
			
			//CALCULO DEL EXENTO Y EL IVA
			if ($frmListaArticulo['hddIdIvaArtImt:'.$valor] == 0 && $frmListaArticulo['hddIvaArtItm:'.$valor] == "") {
				$descuentoArt = ($frmTotalDcto['txtDescuento'] * $subTotalArt) /100;
				$subTotalArt = $subTotalArt - $descuentoArt;
				$totalExento += $subTotalArt;
				
			} else { //ART CON IVAS
				//$ivaArt =($frmDcto['txtIdPedido'] != "") ? $frmListaArticulo['hddIvaArtItm:'.$valor] : $rowIva['iva'];
				
				foreach($arrayIdIva as $indicesIdIva => $valorIdIva){ //RECOORE EL ARRAY DELO ID IVAS
					$arrayDetalleIva[0] = $valorIdIva;
					$arrayDetalleIva[1] = $subTotalArt;
					$arrayDetalleIva[2] = ($subTotalArt * $arrayPorcIva[$indicesIdIva]) /100;//BASE IMPONIBLE IVA
					$arrayDetalleIva[3] = $arrayPorcIva[$indicesIdIva];
					
					$existe = false;
					foreach ($arrayIva as $indice => $arrayIvas){
						if($arrayIva[$indice][0] == $valorIdIva){ //id iva total agregado
							$arrayIva[$indice][1] += $subTotalArt;
							$arrayIva[$indice][2] += ($subTotalArt * $arrayPorcIva[$indicesIdIva]) /100;//BASE IMPONIBLE IVA
							$existe = true;
						}
					}
					
					if($existe == false){
						$arrayIva[] = $arrayDetalleIva;
					}
				}
			}
			
			$subTotal += doubleval($frmListaArticulo['hddTotalArtItm:'.$valor]);
		}
	}
	// SACA LA CUENTA DE LOS GASTOS QUE LLEVAN Y NO LLEVAN IVA
	$gastosConIva = 0;
	$gastosSinIva = 0;
	for ($cont = 1; isset($frmTotalDcto['hddIdGasto'.$cont]); $cont++) {
		if($frmTotalDcto['hddTipoGasto'.$cont] == 0) { //SACA EL MONTO MEDIANTE EL PORCENTAJE
			$porcentaje = str_replace(",","",$frmTotalDcto['txtPorcMontoGasto'.$cont]);
			$monto = $porcentaje * ($subTotal/100);
			$objResponse->assign('txtMontoGasto'.$cont,"value",number_format($monto,2,".",","));
		} else if($frmTotalDcto['hddTipoGasto'.$cont] == 1) { //SACA EL PORCENTAJE MEDIANTE EL MONTO
			$monto = str_replace(",","",$frmTotalDcto['txtMontoGasto'.$cont]);
			$porcentaje = $monto * (100/$subTotal);
			$objResponse->assign('txtPorcMontoGasto'.$cont,"value",number_format($porcentaje,2,".",","));
		}
		
		$monto = str_replace(",","",$monto);
		
		if ($frmTotalDcto['hddEstatusIvaGasto'.$cont] == 1)
			$gastosConIva += $monto;
		else
			$gastosSinIva += $monto;
		
		if ($frmTotalDcto['hddEstatusIvaGasto'.$cont] == 1 && $monto > 0) {
			$queryIva = sprintf("SELECT * FROM pg_iva WHERE idIva = %s", valTpDato($frmTotalDcto['hddIdIvaGasto'.$cont], "int"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$ivaArt = ($frmDcto['txtIdPedido'] != "") ? $frmTotalDcto['hddIvaGasto'.$cont] : $rowIva['iva'];
			
			$existIva = "NO";
			if (isset($arrayIva)) {
				foreach($arrayIva as $indiceIva => $valorIva) {
					if($arrayIva[$indiceIva][0] == $frmTotalDcto['hddIdIvaGasto'.$cont]) {
						$arrayIva[$indiceIva][1] += $monto;
						$arrayIva[$indiceIva][2] += ($monto * ($ivaArt/100));
						$existIva = "SI";
					}
				}
			}
			
			if ($rowIva['idIva'] != "" && $existIva == "NO" && $monto > 0) {
				$arrayDetalleIva[0] = $frmTotalDcto['hddIdIvaGasto'.$cont];
				$arrayDetalleIva[1] = $monto;
				$arrayDetalleIva[2] = ($monto * ($ivaArt/100));
				$arrayDetalleIva[3] = $ivaArt;
				
				$arrayIva[] = $arrayDetalleIva;
			}
		}
	}
	
	//CREA LOS ELEMENTOS DE IVA
	if (isset($arrayIva)) {
		foreach($arrayIva as $indiceIva => $valorIva) {
			$queryIva = sprintf("SELECT * FROM pg_iva WHERE idIva = %s", valTpDato($arrayIva[$indiceIva][0], "int"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$ivaArt = ($frmDcto['txtIdPedido'] != "") ? $arrayIva[$indiceIva][3] : $rowIva['iva'];
			
			if ($arrayIva[$indiceIva][2] > 0) {		
				$objResponse->script(sprintf("
				var elemento = '".
					"<tr align=\"right\" id=\"trIva:%s\" class=\"textoGris_11px\">".
						"<td class=\"tituloCampo\" title=\"tdIva:%s\"><div>%s:</div>".
							"<input type=\"hidden\" id=\"hddIdIva%s\" name=\"hddIdIva[]\" value=\"%s\"/></td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtBaseImpIva%s\" name=\"txtBaseImpIva%s\" readonly=\"readonly\" size=\"16\" style=\"text-align:right\" value=\"%s\"/> *</td>".
						"<td nowrap=\"nowrap\"><input type=\"text\" id=\"txtIva%s\" name=\"txtIva%s\" readonly=\"readonly\" size=\"6\" style=\"text-align:right\" value=\"%s\"/>%s</td>".
						"<td></td>".
						"<td><input type=\"text\" id=\"txtSubTotalIva%s\" name=\"txtSubTotalIva%s\" readonly=\"readonly\" size=\"16\" style=\"text-align:right\" value=\"%s\"/></td>".
					"</tr>';
					
					obj = byId('trIva:%s');
					if(obj == undefined)
						$('#trGastosSinIva').before(elemento);",
					$arrayIva[$indiceIva][0],//trIva
						$arrayIva[$indiceIva][0], utf8_encode($rowIva['observacion']),//tdIva
							$arrayIva[$indiceIva][0], $arrayIva[$indiceIva][0],//hddIdIva
						$arrayIva[$indiceIva][0], $arrayIva[$indiceIva][0], number_format(round($arrayIva[$indiceIva][1],2), 2, ".", ","),//txtBaseImpIva
						$arrayIva[$indiceIva][0], $arrayIva[$indiceIva][0], $ivaArt, "%",//txtIva
						$arrayIva[$indiceIva][0], $arrayIva[$indiceIva][0], number_format(round($arrayIva[$indiceIva][2],2), 2, ".", ","),//txtSubTotalIva
					$arrayIva[$indiceIva][0]));
			}
			$subTotalIva += doubleval($arrayIva[$indiceIva][2]);
		}
	}
	
	
	$subTotalDescuento = $subTotal*($frmTotalDcto['txtDescuento']/100);
	$totalPedido = doubleval($subTotal) - doubleval($subTotalDescuento) + doubleval($gastosConIva) + doubleval($subTotalIva) +  doubleval($gastosSinIva);
	
	$objResponse->assign("txtSubTotal","value",number_format($subTotal,2,".",","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($subTotalDescuento,2,".",","));	
	$objResponse->assign("txtTotalOrden","value",number_format($totalPedido,2,".",","));
	
	$objResponse->assign('txtGastosConIva',"value",number_format($gastosConIva,2,".",","));
	$objResponse->assign('txtGastosSinIva',"value",number_format($gastosSinIva,2,".",","));
	
	$montoEnLetras = num2letras(number_format($totalPedido,2,".",""), false, true);
	$objResponse->assign('txtMontoEnLetras',"innerHTML",$montoEnLetras);
	
	return $objResponse;
}

function calcularImpuesto($frmDatosArticulo){
	$objResponse = new xajaxResponse();
	
	$totalIva = array();
	if(isset($frmDatosArticulo['cbxItmImpuesto2'])){
		foreach($frmDatosArticulo['cbxItmImpuesto2'] as $indiceItm => $valorItm){
			$totalIva[] = str_replace(",","",$frmDatosArticulo['textItmImpuestoIva'.$valorItm]);
		}
	}
		$objResponse->assign("textTotaIva","value",number_format(array_sum($totalIva), 2, ".", ",")."%");
	
	return $objResponse;
}

function cargarDcto($idPedido, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($frmListaArticulo['hddcbxItm'])) {
		foreach($frmListaArticulo['hddcbxItm'] as $indiceItm => $valorItm) {
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
	
	//CONSULTA LOS DATOS DEL ENCABEZADO Y EL PIE DE LA FACTURA (DATOS DE APROVACION) Y EL DESCUENTO
	$queryPedido = sprintf("SELECT vw_ga_sol_comp.*,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		vw_iv_emp_suc.rif,
		vw_iv_emp_suc.direccion
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		INNER JOIN vw_ga_solicitudes_compra vw_ga_sol_comp ON (vw_iv_emp_suc.id_empresa_reg = vw_ga_sol_comp.id_empresa)
	WHERE id_solicitud_compra = %s",
		valTpDato($idPedido,"int"));
	$rsPedido = mysql_query($queryPedido);
	if (!$rsPedido) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowPedido = mysql_fetch_assoc($rsPedido);

	// SE COMIENZA A CARGAR EL DETALLE DEL PEDIDO
	$queryPedidoDet = sprintf("SELECT * FROM ga_detalle_solicitud_compra WHERE id_solicitud_compra = %s",
		valTpDato($idPedido,"int"));
	$rsPedidoDet = mysql_query($queryPedidoDet);
	if (!$rsPedidoDet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$arrayObj = NULL;
	while ($rowPedidoDet = mysql_fetch_assoc($rsPedidoDet)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		//CONSULTA LA DESCRIPCION DE LOS ART QUE CONTENGA LA SOLICITUD
		$queryArticulo = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s",
			valTpDato($rowPedidoDet['id_articulo'],"int"));
		$rsArticulo = mysql_query($queryArticulo);
		if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowArticulo = mysql_fetch_assoc($rsArticulo);
		
	//IJECCION DEL DETALLE DE LA SOLICITU (ARTS)
	$objResponse->script(sprintf("$('#trItmPie').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\">".
				"<input id=\"cbxItm%s\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\" class=\"cbxArt\"/>".
				"<input id=\"hddcbxItm\" name=\"hddcbxItm[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\">".
			"</td>".
			"<td id=\"tdNumItm:%s\" align=\"center\" class=\"textoNegrita_9px\">%s</td>".
			"<td id=\"tdNumbtn:%s\">".
				"<a class=\"modalImg\" id=\"EditarArt:%s\" rel=\"#divFlotante2\">".
					"<button type=\"button\" id=\"btnEnviar:%s\">".
							"<img src=\"../img/iconos/ico_edit.png\" width=\"16\" height=\"16\" align=\"absmiddle\" alt=\"Editar\"/>".
					"</button>".
				"</a>".			
			"</td>".
			"<td id=\"tdCodItm:%s\" class=\"textoNegrita_9px\" align=\"center\">%s</td>".
			"<td id=\"tdDescItm:%s\" align=\"left\">%s</td>".
			"<td id=\"tdUnidItm:%s\" align=\"center\">%s</td>".
			"<td id=\"tdCatItm:%s\" align=\"center\">%s</td>".
			"<td id=\"tdPrecioItm:%s\" align=\"center\">%s</td>".
			"<td id=\"tdIvaItm:%s\" align=\"center\"><div id=\"divIvaItm:%s\" style=\"white-space:nowrap\" align=\"center\"></div></td>".
			"<td align=\"right\">".
				"<span id=\"spanTotal:%s\" title=\"spanTotal:%s\">%s</span>".
				"<input id=\"hddIdArItm:%s\" type=\"hidden\" value=\"%s\" readonly=\"readonly\" name=\"hddIdArItm:%s\">".
				"<input id=\"hddCantArtItm:%s\" type=\"hidden\" value=\"%s\" readonly=\"readonly\" name=\"hddCantArtItm:%s\">".
				"<input id=\"hddCostoArtItm:%s\" type=\"hidden\" value=\"%s\" readonly=\"readonly\" name=\"hddCostoArtItm:%s\">".
				"<input id=\"hddIdIvaArtImt:%s\" type=\"hidden\" value=\"\" readonly=\"readonly\" name=\"hddIdIvaArtImt:%s\">".
				"<input id=\"hddIvaArtItm:%s\" type=\"hidden\" value=\"\" readonly=\"readonly\" name=\"hddIvaArtItm:%s\">".
				"<input id=\"hddTotalArtItm:%s\" type=\"hidden\" value=\"%s\" readonly=\"readonly\" name=\"hddTotalArtItm:%s\">".
				"<input id=\"hddTipoArtItm:%s\" type=\"hidden\" value=\"%s\" readonly=\"readonly\" name=\"hddTipoArtItm:%s\">".
				"<input id=\"hddIdClienteArtItm:%s\"  type=\"hidden\" value=\"%s\" readonly=\"readonly\" name=\"hddIdClienteArtItm:%s\">".
			"</td>".
		"</tr>');
		
		byId('EditarArt:%s').onclick = function() {
			abrirDivFlotante('EditarArt',this)
			xajax_asignarArticulo('%s','%s',xajax.getFormValues('frmListaArticulo'));
			xajax_listImpuesto(0,'iva','ASC','impuestoItems');
		}",
		$contFila, $clase,//tr 301
			$contFila,//302
				$contFila,$contFila,//303
				$contFila,//304
			$contFila,$contFila,//306
			$contFila,//307
				$contFila,//308
					$contFila,//309
				$contFila,elimCaracter(utf8_encode($rowArticulo['codigo_articulo']),"-"),//314
				$contFila,preg_replace("/[\r?|\n?]/","<br>",elimCaracter(utf8_encode($rowArticulo['descripcion']))),//317
				$contFila,$rowArticulo['unidad'],//315				
				$contFila,$rowPedidoDet['cantidad'],//316
				$contFila,number_format($rowPedidoDet['precio_sugerido'],2,".",","),//318
				$contFila,$contFila, //319
					$contFila,$contFila,number_format(($rowPedidoDet['cantidad'] * $rowPedidoDet['precio_sugerido']),2,".",","),//320
					$contFila,$rowArticulo['id_articulo'],$contFila,//321
					$contFila,$rowPedidoDet['cantidad'],$contFila,//322
					$contFila,$rowPedidoDet['precio_sugerido'],$contFila,//323
					$contFila,$contFila,//324
					$contFila,$contFila,//325
					$contFila,($rowPedidoDet['cantidad'] * $rowPedidoDet['precio_sugerido']),$contFila,//326
					$contFila,$rowPedidoDet['tipo'],$contFila,//327
					$contFila,$rowPedidoDet['nombre_cliente'],$contFila,//328correjir no es nombre no es el nombre esel ID del ciente
			$contFila,//332
				$rowPedidoDet['id_detalle_solicitud_compra'], $contFila//334
		));

		//BUSCA EL IVA DE COMPRAS PREDETERMINADO
		$queryIva = sprintf("SELECT * FROM pg_iva WHERE tipo = 1 AND activo = 1 AND estado = 1 ORDER BY iva");
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$arrayIdIva = array();
		$arrayIva = array();
		$tblIva ="";
		while($rowIva = mysql_fetch_assoc($rsIva)){
			$arrayIdIva []= $rowIva['idIva'];
			$arrayIva []= $rowIva['iva'];
			
			$tblIva .=sprintf("<table cellspacing=\"0\" cellpadding=\"0\" align=\"center\"><tr><td>%s</td><td>%s</td></tr></table>",
					$rowIva['iva'],"%");
			$objResponse->assign("divIvaItm:".$contFila,"innerHTML",$tblIva);
		}

		$objResponse->assign("hddIdIvaArtImt:".$contFila,"value",implode("|", $arrayIdIva));
		$objResponse->assign("hddIvaArtItm:".$contFila,"value",implode("|", $arrayIva));

		$arrayObj[] = $contFila;
		
	}
	
	//CARGA LOS POSIBLES GASTO DE LA SOLICITUD
	$objResponse->script("RecorrerForm('frmTotalDcto',true);");
	
	if ($rowPedido['id_proveedor'] == "") {//SI NO EXISTE UN PROVEEDOR DESDE LA SOLICITUD ACTIVA EL BTN Y CALCULA
		$objResponse->script("byId('btnInsertarProv').style.display = '';");
		$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmPedido'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'))");
	} else if ($rowPedido['id_proveedor'] != "") {//SI EXISTE UN PROVEEDOR DESDE LA SOLICITUD DESACTIVA EL BTN Y CARGA ASIGNARPROVEEDOR Y CALCULA 
		$objResponse->script("byId('btnInsertarProv').style.display = 'none';");
		$objResponse->loadCommands(asignarProveedor($rowPedido['id_proveedor']));
	}

	//ASIGNA LOS DATOS AL EMCABEZADO DE LA ORDEN
	$objResponse->assign("txtFechaOrdenCompra","value",date(spanDateFormat));
	$objResponse->assign("txtIdPedido","value",utf8_encode($rowPedido['id_solicitud_compra']));
	
	//DATOS DE LA COMPRAS
	$objResponse->assign("txtIdEmpresa","value",$rowPedido['id_empresa']);
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowPedido['nombre_empresa']));
	$objResponse->assign("txtRif","value",utf8_encode($rowPedido['rif']));
	$objResponse->assign("txtDireccion","innerHTML",utf8_encode($rowPedido['direccion']));

	$objResponse->assign("txtIdUnidadCentroCosto","value",utf8_encode($rowPedido['id_unidad_centro_costo']));
	
	$objResponse->assign("txtDescuento","value",$rowPedido['porcentaje_descuento']);
	$objResponse->assign("txtDescuento","value",number_format($rowPedido['porcentaje_descuento'], 2, ".", ","));
	
	//DATOS DEL EMPLEADO QUE CARGO LA SOLICITUD
	$objResponse->assign("hddIdEmpleadoPreparado","value",$rowPedido['id_empleado']);
	$objResponse->assign("txtNombreEmpleadoPreparado","value",utf8_encode($rowPedido['nombre_empleado']." ".$rowPedido['apellido']));
	$objResponse->assign("txtFechaPreparado","value",date(spanDateFormat, strtotime($rowPedido['fecha_solicitud'])));
	$objResponse->assign("txtObservaciones","value",utf8_encode($rowPedido['observaciones']));

	//BUSCA LOS DATOS DEL USUARIO APROBADOR PARA SABER SUS DATOS PERSONALES
	$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
		valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowUsuario = mysql_fetch_assoc($rsUsuario);
	
	$objResponse->assign("hddIdEmpleadoAprobado","value",$rowUsuario['id_empleado']);
	$objResponse->assign("txtNombreEmpleadoAprobado","value",utf8_encode($rowUsuario['nombre_empleado']));
	$objResponse->assign("txtFechaAprobado","value",date(spanDateFormat));
	
	$objResponse->script("
	byId('txtFechaEntrega').readOnly = false;
	byId('imgFechaEntrega').style.visibility = 'visible';
	byId('lstTipoTransporte').disabled = false;
	
	byId('txtCotizacion').readOnly = false;
	byId('txtFechaCotizacion').readOnly = false;
	byId('imgFechaCotizacion').style.visibility = 'visible';
	byId('rbtTipoPagoCredito').disabled = false;
	byId('rbtTipoPagoContado').disabled = false;
	byId('txtCondicionesPago').readOnly = false;
	byId('txtObservaciones').readOnly = false;
	");
	
	return $objResponse;
}

function editarArticulo($valForm, $valFormTotalFactura) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxItmImpuesto2'])) { // SI EXISTE UN IMPUESTO EN LOS DATOS DEL ART
		$objResponse->script(sprintf("
			if(byId('tdIvaItm:'+%s).className == 'divMsjInfo'){
				byId('tdIvaItm:'+%s).className ='';
			}",
		$valForm['hddNumeroArt'],$valForm['hddNumeroArt']));
		
		foreach($valForm['cbxItmImpuesto2'] as $indiceItm => $valorItm) { // RECORRE LOS IMPUESTO EXISTENTE EN EL ART
			$queryIva = sprintf("SELECT * FROM pg_iva WHERE idIva = %s", 
				valTpDato($valForm['textItmImpuestoId'.$valorItm], "int"));
			$rsIva = mysql_query($queryIva);
			if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowIva = mysql_fetch_assoc($rsIva);	
			if($valorItm != 0){
				$IdIva []= $rowIva['idIva'];
				$Iva []= $rowIva['iva'];
				$tblIVa .=sprintf("<table cellpadding=\"0\" cellspacing=\"0\" width=\"%s\"><tr><td align=\"center\">%s</td></tr></table>",
						"100%",$rowIva['iva']."%");
			}else{
				$tblIVa ="N/A";
				$objResponse->script(sprintf("byId('tdIvaItm:'+%s).className = 'divMsjInfo';",$valForm['hddNumeroArt']));
			}
		}
			$arrayIdIva = implode("|",$IdIva);
			$arrayIvas = implode("|",$Iva);
	} else {
		$tblIVa ="N/A";
		$objResponse->script(sprintf("byId('tdIvaItm:'+%s).className = 'divMsjInfo';",$valForm['hddNumeroArt']));
		$arrayIdIva = 0;
		$arrayIvas = 0;
	}
	
	$objResponse->assign("divIvaItm:".$valForm['hddNumeroArt'],"innerHTML",$tblIVa);
	
	$queryIva = sprintf("SELECT * FROM pg_iva WHERE idIva = %s", valTpDato($valForm['lstIvaArt'], "int"));
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowIva = mysql_fetch_assoc($rsIva);
	$caracterIva = ($rowIva['idIva'] != "") ? $rowIva['iva']."%" : "NA"; 
	
	$objResponse->assign("tdCatItm:".$valForm['hddNumeroArt'],"innerHTML",$valForm['txtCantidadArt']);
	$objResponse->assign("tdPrecioItm:".$valForm['hddNumeroArt'],"innerHTML",number_format($valForm['txtCostoArt'],2,".",","));
	$objResponse->assign("spanTotal:".$valForm['hddNumeroArt'],"innerHTML",number_format(($valForm['txtCantidadArt']*$valForm['txtCostoArt']),2,".",","));
	
	$objResponse->assign("hddCantArtItm:".$valForm['hddNumeroArt'],"value",$valForm['txtCantidadArt']);//
	$objResponse->assign("hddCostoArtItm:".$valForm['hddNumeroArt'],"value",str_replace(",","",$valForm['txtCostoArt']));
	
	$objResponse->assign("hddIdIvaArtImt:".$valForm['hddNumeroArt'],"value",$arrayIdIva);
	$objResponse->assign("hddIvaArtItm:".$valForm['hddNumeroArt'],"value",$arrayIvas);
	$objResponse->assign("hddTotalArtItm:".$valForm['hddNumeroArt'],"value",str_replace(",","",($valForm['txtCantidadArt']*$valForm['txtCostoArt'])));
	$objResponse->assign("hddTipoArtItm:".$valForm['hddNumeroArt'],"value",$valForm['rbtTipoArt']);
	$objResponse->assign("hddIdClienteArtItm:".$valForm['hddNumeroArt'],"value",$valForm['txtIdClienteArt']);
	
	$objResponse->script("xajax_eliminarImpuesto(xajax.getFormValues('frmDatosArticulo'));
	xajax_calcularDcto(xajax.getFormValues('frmPedido'),xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");

	$objResponse->script("
	RecorrerForm('frmDatosArticulo',false);
	RecorrerForm('frmPedido',false);
	RecorrerForm('frmListaArticulo',false);
	RecorrerForm('frmTotalDcto',false,{0:'btnQuitarGasto',1:'btnAgregarGasto'});
	RecorrerForm('frmbtn',false);
	byId('btnCancelarEditarArt').click();");
	
	return $objResponse;
}

function eliminarArticulo($valForm) {
	$objResponse = new xajaxResponse();
	
	foreach($valForm['hddcbxItmItm'] as $indiceItm=>$valorItm) {
		$objResponse->script(sprintf("
		fila = document.getElementById('trItm:%s');
		padre = fila.parentNode;
		padre.removeChild(fila);",
			$valorItm));
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmPedido'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
		
	return $objResponse;
}

function eliminarImpuesto($valorForm = "", $elimBloque = NULL){
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

function insertarImpuestoBloque($frmDatosArt, $frmImpuesto){
	$objResponse = new xajaxResponse();
	
	if(isset($frmImpuesto['checkLstImpuesto'])){
		foreach($frmImpuesto['checkLstImpuesto'] as $indiceImpuesto => $valorImpuesto){
			// CONSUTLAS EL IMPUESTO 
			$query = sprintf("SELECT * FROM pg_iva WHERE idIva = %s",
				valTpDato($valorImpuesto, "int"));
			$rsIva = mysql_query($query);
			if (!$rsIva) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			$rowsIva = mysql_fetch_assoc($rsIva);
			$idIva []=$rowsIva['idIva'];
			$iva []= $rowsIva['iva'];
		}
		
		// RECORRE LOS ART EXISTENTES DE LA ORDER
		if(isset($frmDatosArt['cbxItm'])){
			foreach($frmDatosArt['cbxItm'] as $indiceArt => $valorArt){
				$tblIva ="";
				foreach($iva as $indice => $valorIva){
					$tblIva .=sprintf("<table cellspacing=\"0\" cellpadding=\"0\" align=\"center\"><tr><td>%s</td><td>%s</td></tr></table>",
					$valorIva,"%");
					$objResponse->assign("divIvaItm:".$valorArt,"innerHTML",$tblIva);
				}

				$objResponse->assign("hddIdIvaArtImt:".$valorArt,"value",implode("|", $idIva));
				$objResponse->assign("hddIvaArtItm:".$valorArt,"value",implode("|", $iva));
				$objResponse->script("byId('cbxItm').click();");
			}
		} else {
			return $objResponse->alert("Debe Seleccionar al menos un articulo");	
		}
	}else{
		return $objResponse->alert("Debe Seleccionar al Menos un Items");
	}

	return	$objResponse;
}

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
					$objResponse->script(sprintf("xajax_eliminarImpuesto(xajax.getFormValues('frmDatosArticulo'))"));	
				}
				if ($frmListaArticulo['textItmImpuestoId'.$valor] == $idImpuesto) {
					$existe = true;
				}
			}
		}
		if ($existe == false) {
			$Result1 = insertarItemImpuesto($contFila, $idImpuesto);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			$objResponse->script("RecorrerForm('frmImpuesto',false);");// DESBLOQUEA LOS BOTONES DEL LISTADO
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
	$objResponse->script("xajax_calcularImpuesto(xajax.getFormValues('frmDatosArticulo'));
	RecorrerForm('frmImpuesto',false);");
	
	return $objResponse;
}

function guardarOrden($frmDcto, $frmListaArticulo, $frmTotalDcto) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"ga_orden_compra_list","insertar")) { return $objResponse; }
	
	mysql_query("START TRANSACTION;");
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	if (isset($frmListaArticulo['hddcbxItm'])) {
		foreach($frmListaArticulo['hddcbxItm'] as $indiceItm => $valorItm) {
			$arrayObj[] = $valorItm;
		}
	}
	
	if($frmTotalDcto['txtFechaCotizacion'] != ""){
		$fechaContizacion = date("Y-m-d",strtotime($frmTotalDcto['txtFechaCotizacion']));
	}
	
	//INSERTA LOS DATOS DE LA ORDEN
	$insertSQL = sprintf("INSERT INTO ga_orden_compra (
		id_solicitud_compra,
		id_empleado_aprobador,
		fecha,
		id_empleado_contacto,
		id_empleado_recepcion,
		fecha_entrega,
		tipo_transporte,
		segun_cotizacion,
		fecha_cotizacion,
		tipo_pago,
		condiciones_pago,
		monto_letras,
		observaciones,
		id_empresa,
		id_proveedor,
		subtotal,
		porcentaje_descuento,
		subtotal_descuento,
		estatus_orden_compra
	) VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($frmDcto['txtIdPedido'], "int"),
		valTpDato($frmTotalDcto['hddIdEmpleadoAprobado'], "int"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaOrdenCompra'])), "date"),
		valTpDato($frmDcto['textIdContacto'], "int"),
		valTpDato($frmDcto['textIdResponsable'], "int"),
		valTpDato(date("Y-m-d",strtotime($frmDcto['txtFechaEntrega'])), "date"),
		valTpDato($frmDcto['lstTipoTransporte'], "int"),
		valTpDato($frmTotalDcto['txtCotizacion'], "text"),
		valTpDato($fechaCotizacion, "date"),
		valTpDato($frmDcto['rbtTipoPago'], "int"),
		valTpDato($frmTotalDcto['txtCondicionesPago'], "text"),
		valTpDato($frmTotalDcto['txtMontoEnLetras'], "text"),
		valTpDato($frmTotalDcto['txtObservaciones'], "text"),
		valTpDato($frmDcto['txtIdEmpresa'], "int"),
		valTpDato($frmDcto['txtIdProv'], "int"),
		valTpDato($frmTotalDcto['txtSubTotal'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtDescuento'], "real_inglesa"),
		valTpDato($frmTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
		valTpDato(2,"int")); // 0 = Pendiente, 1 = Pedido, 2 = Orden, 3 = Facturado

	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1){errorGuardarDcto($objResponse,__LINE__);return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
	}
	$idOrdenCompra = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	//INSERTA EL DETALLE DE LA ORDEN
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if ($frmListaArticulo['hddIdArItm:'.$valor] > 0) {
				
				$insertSQL = sprintf("INSERT INTO ga_orden_compra_detalle (id_orden_compra, id_articulo, cantidad, pendiente, precio_unitario,  tipo, id_cliente, estatus) VALUE (%s, %s, %s, %s, %s, %s, %s, %s)",
					valTpDato($idOrdenCompra, "int"),
					valTpDato($frmListaArticulo['hddIdArItm:'.$valor], "int"),
					valTpDato($frmListaArticulo['hddCantArtItm:'.$valor], "real_inglesa"),
					valTpDato($frmListaArticulo['hddCantArtItm:'.$valor], "real_inglesa"),
					valTpDato($frmListaArticulo['hddCostoArtItm:'.$valor], "real_inglesa"),
					valTpDato($frmListaArticulo['hddTipoArtItm:'.$valor], "int"),
					valTpDato($frmListaArticulo['hddIdClienteArtItm:'.$valor], "int"),
					valTpDato(0, "int")); //0 = En Espera, 1 = Recibido, 2 = Anulado
				
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1){errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);}
				$ordenCompraDetalle = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				$arrayIdIva= explode("|",$frmListaArticulo['hddIdIvaArtImt:'.$valor]);
				$arrayIva = explode("|",$frmListaArticulo['hddIvaArtItm:'.$valor]);
				
				//RECORRE LA CANTIDAD DE IVAS DEL DETALLE DE LA ORDEN
				foreach($arrayIdIva as $keyIdIva => $valueIdIva){
					$porcIva = $arrayIva[$keyIdIva];
					
					$baseImponible = ($frmListaArticulo['hddCantArtItm:'.$valor] * $frmListaArticulo['hddCostoArtItm:'.$valor]);
					
					if($porcIva != 0){
						$subTotalIva = $baseImponible * ($porcIva /100);
						$idIva = valTpDato($valueIdIva, "int");	
					}else{
						$subTotalIva =  0.00;
						$idIva = valTpDato(NULL, "text");	
					}
					
					$insertDetalleIva = sprintf("INSERT INTO ga_orden_compra_detalle_iva (id_orden_compra_detalle, id_iva, iva, base_imponible, sub_total_iva) VALUE (%s, %s, %s, %s, %s)",
						valTpDato($ordenCompraDetalle, "int"),
						$idIva,
						valTpDato($porcIva, "real_inglesa"),
						valTpDato($baseImponible, "real_inglesa"),
						valTpDato($subTotalIva, "real_inglesa"));

					$rsDetalleIva = mysql_query($insertDetalleIva);
					if (!$rsDetalleIva){errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);}
				}
				
				//VERIFICA SI EL ARTICULO YA ESTA REGISTRADO PARA LA EMPRESA
				$queryArtEmp = sprintf("SELECT * FROM ga_articulos_empresa WHERE id_empresa = %s AND id_articulo = %s",
					valTpDato($frmDcto['txtIdEmpresa'],"int"),
					valTpDato($frmListaArticulo['hddIdArItm:'.$valor],"int"));
				mysql_query("SET NAMES 'utf8';");
				$rsArtEmp = mysql_query($queryArtEmp);
				if (!$rsArtEmp){errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);}
				$rowArtEmp = mysql_fetch_assoc($rsArtEmp);
				mysql_query("SET NAMES 'latin1';");
				
				//SI NO ESTA REGISTRADO, LO REGISTRA
				if ($rowArtEmp['id_articulo_empresa'] == "") {
					$insertSQL = sprintf("INSERT INTO ga_articulos_empresa (id_empresa, id_articulo, clasificacion) VALUE (%s, %s, %s)",
						valTpDato($frmDcto['txtIdEmpresa'],"int"),
						valTpDato($frmListaArticulo['hddIdArItm:'.$valor],"int"),
						valTpDato("F","text"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1){errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);}
					mysql_query("SET NAMES 'latin1';");
				}
			}
		}
	}
	
	//INSERTA LOS GASTOS DEL PEDIDO
	for ($cont = 1; isset($frmTotalDcto['hddIdGasto'.$cont]); $cont++) {
		$frmTotalDcto['txtMontoGasto'.$cont] = ($frmTotalDcto['txtMontoGasto'.$cont] == "") ? 0 : $frmTotalDcto['txtMontoGasto'.$cont];
		$frmTotalDcto['txtPorcMontoGasto'.$cont] = ($frmTotalDcto['txtPorcMontoGasto'.$cont] == "") ? 0 : $frmTotalDcto['txtPorcMontoGasto'.$cont];
		
		if (round($frmTotalDcto['txtMontoGasto'.$cont],2) != 0) {
			$insertSQL = sprintf("INSERT INTO ga_orden_compra_gasto (id_orden_compra, id_gasto, tipo, porcentaje_monto, monto, estatus_iva, id_iva, iva)
			VALUE (%s, %s, %s, %s, %s, %s, %s, %s)",
				valTpDato($idOrdenCompra, "int"),
				valTpDato($frmTotalDcto['hddIdGasto'.$cont], "int"),
				valTpDato($frmTotalDcto['hddTipoGasto'.$cont], "int"),
				valTpDato($frmTotalDcto['txtPorcMontoGasto'.$cont], "real_inglesa"),
				valTpDato($frmTotalDcto['txtMontoGasto'.$cont], "real_inglesa"),
				valTpDato($frmTotalDcto['hddEstatusIvaGasto'.$cont],"boolean"),
				valTpDato($frmTotalDcto['hddIdIvaGasto'.$cont],"int"),
				valTpDato($frmTotalDcto['hddIvaGasto'.$cont],"int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1){errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);}
			mysql_query("SET NAMES 'latin1';");
		}
	}
	
	//INSERTA LOS IVA DEL PEDIDO 
	if(isset($frmTotalDcto['hddIdIva'])){
		foreach($frmTotalDcto['hddIdIva'] as $indice => $valor){
			if ($frmTotalDcto['txtSubTotalIva'.$valor] != 0) {
				$insertSQL = sprintf("INSERT INTO ga_orden_compra_iva (id_orden_compra, base_imponible, subtotal_iva, id_iva, iva) VALUE (%s, %s, %s, %s, %s)",
					valTpDato($idOrdenCompra, "int"),
					valTpDato($frmTotalDcto['txtBaseImpIva'.$valor], "real_inglesa"),
					valTpDato($frmTotalDcto['txtSubTotalIva'.$valor], "real_inglesa"),
					valTpDato($valor, "int"),
					valTpDato($frmTotalDcto['txtIva'.$valor],"real_inglesa"));

				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1){errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);}
				mysql_query("SET NAMES 'latin1';");
			}
		}
	}

	// MAYCOL ULTIMO PASO estado de la solicitud = (4) "PROCESO":
	$updateSQL = sprintf("UPDATE ga_solicitud_compra SET id_estado_solicitud_compras = 5
	WHERE id_solicitud_compra = %s",
		valTpDato($frmDcto['txtIdPedido'], "int"));
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1){errorGuardarDcto($objResponse,__LINE__); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	}
	mysql_query("SET NAMES 'latin1';");

	$objResponse->assign("txtIdOrdenCompra","value",$idOrdenCompra);
	
	$objResponse->script("RecorrerForm('frmPedido',false);RecorrerForm('frmListaArticulo',false);RecorrerForm('frmTotalDcto',false,{0:'btnQuitarGasto',1:'btnAgregarGasto'});");

	for ($cont = 1; isset($frmTotalDcto['hddIdGasto'.$cont]); $cont++) {
		$objResponse->script("byId('rbtGastoPorc".$cont."').disabled = true;");
		$objResponse->script("byId('rbtGastoMonto".$cont."').disabled = true;");	
		$objResponse->script("
			byId('txtPorcMontoGasto".$cont."').readOnly = true;
			byId('txtMontoGasto".$cont."').readOnly = true;");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Orden de Compra Aprobada con Exito");

	$objResponse->script(sprintf("window.open('reportes/ga_orden_compra_pdf.php?idOrdenComp=%s&session=%s', 960, 550)",
		$idOrdenCompra,
		$_SESSION['idEmpresaUsuarioSysGts']));

	$objResponse->script("window.location.href='ga_orden_compra_list.php';");
		
	return $objResponse;
}

function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" activo = %s",
			valTpDato(1, "int"));
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" nombre_cargo LIKE %s
		OR nombre_empleado LIKE %s",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT id_empleado,nombre_cargo,nombre_empleado,id_empresa,activo FROM vw_pg_empleados %s ORDER BY nombre_empleado ",
					 $sqlBusq);
//$objResponse->alert($query);
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "5%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "40%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Cargo");
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "55%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empleado");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= sprintf("<tr align=\"left\" class=\"%s\" height=\"24\">",$clase);
			$htmlTb .= "<td>".sprintf("<button type=\"button\" onclick=\"xajax_asignarEmpleado(%s,xajax.getFormValues('frmBuscarEmpleado'));\" title=\"Seleccionar Empleado\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>",$row['id_empleado'])."</td>";
			$htmlTb .= sprintf("<td align=\"center\">%s</td>",$row['id_empleado']);
			$htmlTb .= sprintf("<td align=\"left\">%s</td>",$row['nombre_cargo']);
			$htmlTb .= sprintf("<td>%s</td>",utf8_encode($row['nombre_empleado']));
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"4\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoEmpleado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

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
//$objResponse->alert($query);
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= sprintf("<tr align=\"left\" class=\"%s\" height=\"24\">",$clase);
			$htmlTb .= "<td>".sprintf("<button type=\"button\" onclick=\"xajax_asignarCliente(%s);\" title=\"Seleccionar Cliente\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>",$row['id'])."</td>";
			$htmlTb .= sprintf("<td align=\"right\">%s</td>",$row['id']);
			$htmlTb .= sprintf("<td align=\"right\">%s</td>",$row['ci_cliente']);
			$htmlTb .= sprintf("<td>%s</td>",utf8_encode($row['nombre_cliente']));
			$htmlTb .= sprintf("<td align=\"center\">%s</td>",utf8_encode($arrayTipoPago[strtoupper($row['credito'])]));
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
		$htmlTb .= "<td colspan=\"4\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoClientes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Clientes");
	$objResponse->script("
	if (byId('divFlotante2').style.display == 'none') {
		byId('divFlotante2').style.display = '';
		centrarDiv(byId('divFlotante2'));
		
		byId('txtCriterioBuscarCliente').focus();
	}");
	
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
					$objHTML = sprintf("<input type=\"checkbox\" id=\"checkHhdIpuesto\" name=\"checkHhdIpuesto\" onclick=\"seleccionarTodosCheckbox('checkHhdIpuesto','checkImpuestoLst');\"/>");
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
		$htmlTh .= ordenarCampo("xajax_listImpuesto", "50%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Observación"));
		$htmlTh .= ordenarCampo("xajax_listImpuesto", "27%", $pageNum, "tipo_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo de Impuesto"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			switch($valCadBusq[0]){
				case "impuestoBloque":
					$objHTML = sprintf("<input type=\"checkbox\" name=\"checkLstImpuesto[]\" class=\"checkLstImpuesto\" id=\"checkLstIpuesto\" value=\"%s\" />",
						$row['idIva']);
					break;
				case "impuestoItems":
					$objHTML = sprintf("<button id=\"btnImpuesto%s\" name=\"btnImpuesto%s\" onclick=\"RecorrerForm('frmImpuesto',true);xajax_insertarImpuesto(%s, xajax.getFormValues('frmDatosArticulo'))\" type=\"button\" title=\"Seleccionar\">".
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
	
	$objResponse->assign("tdListIpmuesto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;	
}

function listaProveedor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lrif, rif) LIKE %s
		OR CONCAT_WS('', lrif, rif) LIKE %s
		OR nombre LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		id_proveedor,
		nombre,
		CONCAT_WS('-', lrif, rif) AS rif_proveedor,
		direccion,
		contacto,
		correococtacto,
		telefono,
		fax,
		credito
	FROM cp_proveedor %s", $sqlBusq);
	
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
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "5%", $pageNum, "id_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Código"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "20%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities($spanProvCxP));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "65%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listaProveedor", "20%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."');\" title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_proveedor']."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities($row['rif_proveedor'])."</td>";
			$htmlTb .= "<td>".htmlentities($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".htmlentities(strtoupper($arrayTipoPago[strtoupper($row['credito'])]))."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"7\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaProveedor(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaProveedor(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function nuevoDcto() {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
		document.getElementById('btnAgregarGasto').disabled=fasle;
		document.getElementById('btnQuitarGasto').disabled=fasle;
	");
	
	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"asignarArticulo");
$xajax->register(XAJAX_FUNCTION,"asignarContacto");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"insertarImpuestoBloque");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");

$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");

$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"calcularImpuesto");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstRespRecepcion");

$xajax->register(XAJAX_FUNCTION,"editarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarImpuesto");

$xajax->register(XAJAX_FUNCTION,"insertarImpuesto");
$xajax->register(XAJAX_FUNCTION,"guardarOrden");

$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listImpuesto");
$xajax->register(XAJAX_FUNCTION,"listaProveedor");

$xajax->register(XAJAX_FUNCTION,"nuevoDcto");

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
	
	if($idImpuesto != 0){
	// BUSCA LOS DATOS DEL TIPO DE ORDEN
	$query = sprintf("SELECT idIva,tipo,tipo_impuesto,observacion,iva,activo,estado FROM pg_iva 
		INNER JOIN pg_tipo_impuesto ON pg_tipo_impuesto.id_tipo_impuesto = pg_iva.tipo %s", $sqlBusq,
			valTpDato($idImpuesto, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".basename(__FILE__), $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	
		$htmlItmPie = sprintf("$('#trItemArtIva').before('".
		"<tr id=\"trItemsImpuesto:%s\" align=\"left\" class=\"textoGris_11px %s\" title=\"trItemsImpuesto:%s\">".
			"<td align=\"center\">".
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
		$htmlItmPie = sprintf("$('#trItemArtIva').before('".
		"<tr id=\"trItemsImpuesto:0\" align=\"left\" title=\"trItemsImpuesto:0\">".
			"<td align=\"center\" colspan=\"3\">".
			"<input id=\"cbxItmImpuesto2\" name=\"cbxItmImpuesto2[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"0\">".
				"<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo\" width=\"%s\">".
					"<tr><td width=\"25\"><img src=\"../img/iconos/ico_info2.gif\" width=\"25\"/></td>".
					"<td align=\"center\">No aplica Impuesto</td></tr>".
				"</table>".
				
			"</td>".
		"</tr>');",
				"100%");
		
	}

	return array(true, $htmlItmPie, $contFila, $query);
}

function errorGuardarDcto($objResponse, $line) {
	//$objResponse->script(sprintf("alert('%s');",$line));
	$objResponse->script("
		RecorrerForm('frmPedido',false);
		RecorrerForm('frmListaArticulo',false);
		RecorrerForm('frmTotalDcto',false,{0:'btnQuitarGasto',1:'btnAgregarGasto'});
		RecorrerForm('frmbtn',false);
	");
}
?>