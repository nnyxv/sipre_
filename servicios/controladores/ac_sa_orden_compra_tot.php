<?php

function actualizarObjetosExistentes($valForm){
	$objResponse = new xajaxResponse();
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	for ($cont = 0; $cont <= strlen($valForm['hddObj']); $cont++) {
		$caracter = substr($valForm['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
		}else{
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($valForm['txtDescripcion'.$valor]))
			$cadena .= "|".$valor;
	}
	$objResponse->assign("hddObj","value",$cadena);
	
	return $objResponse;
}

function actualizarTOT($frmDatosFactura, $frmDetallesTrabajoRequeridos, $frmTotalFactura, $idOrdenTOT){
	$objResponse = new xajaxResponse();
       
	if (!xvalidaAcceso($objResponse,"sa_orden_tot_list",editar)){
		return $objResponse;
	}
	
	if($frmTotalFactura['txtMontoRetencionISLR'] < 0){
		return $objResponse->alert("La retencion no puede ser negativa");
	}
	
	//si tiene accesorios todos deben serlos, tipo de orden accesorios
	$numeroItemLista = explode("|", $frmDetallesTrabajoRequeridos['hddObj']);
	array_shift($numeroItemLista);
	
	$esAccesorios = false;
	foreach($numeroItemLista as $key => $numeroItem){
		if($frmDetallesTrabajoRequeridos["idAccesorio".$numeroItem] != ""){
			$esAccesorios = true;                
		}
	}
	
	if($esAccesorios){
		foreach($numeroItemLista as $key => $numeroItem){
			if($frmDetallesTrabajoRequeridos["txtMonto".$numeroItem] == ""){
				return $objResponse->alert("Los items accesorios deben especificarse costo por detalle debido a la factura de venta");
			}
		}
		
	}
        
	mysql_query("START TRANSACTION;");

	$query = sprintf("UPDATE sa_orden_tot SET 
							monto_exento = '%s',
							monto_subtotal = '%s',
							monto_total = '%s',
							numero_factura_proveedor = '%s',
							numero_control_factura = '%s',
							fecha_factura_proveedor = '%s',
							fecha_origen = CURRENT_DATE(),
							observacion_factura = '%s',
							tipo_pago = '%s',
							aplica_libros = '%s',
							estatus = '1' 
					WHERE id_orden_tot = '%s' ;",
		$frmTotalFactura['txtMontoExento'],
		$frmTotalFactura['txtSubTotal'],
		str_replace(',','',$frmTotalFactura['txtTotalOrden']),
		$frmDatosFactura['txtNumeroFacturaProveedor'],
		$frmDatosFactura['txtNumeroControl'],
		date("Y-m-d",strtotime($frmDatosFactura['txtFechaProveedor'])),
		validaTextArea($frmDatosFactura['txtObservacionFactura']),
		(intval($frmDatosFactura['slctTipoPago']) + 1),
		$frmDatosFactura['slctAplicaLibros'],
		$idOrdenTOT);
	
	mysql_query("SET NAMES 'utf8'");

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	mysql_query("SET NAMES 'latin1';");
	
	if ($frmTotalFactura['hddObjTotalDetalles'] == 1){
		/* DESCONCATENA PARA IR INSERTANDO EL MONTO DE LAS DESCRIPCIONES EN LA BD*/
		for ($cont = 1; $cont <= strlen($frmDetallesTrabajoRequeridos['hddObj']); $cont++) {
			$caracter = substr($frmDetallesTrabajoRequeridos['hddObj'], $cont, 1);
			
			if ($caracter != "|" && $caracter != ""){
				$cadena .= $caracter;
			}else{
				$queryActualizarTrabajos = sprintf("UPDATE sa_orden_tot_detalle
                                        		SET monto = '%s',
                                            	cantidad = %s 
												WHERE id_orden_tot_detalle = '%s' ;",
												$frmDetallesTrabajoRequeridos["txtMonto".$cadena],
												valTpDato($frmDetallesTrabajoRequeridos["txtCantidad".$cadena],"int"),
												$frmDetallesTrabajoRequeridos["txtIdDescripcion".$cadena]
												);
				
				mysql_query("SET NAMES 'utf8'");
				
				$rsActualizarTrabajos = mysql_query($queryActualizarTrabajos);
				if (!$rsActualizarTrabajos) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				
				mysql_query("SET NAMES 'latin1';");
				
				$cadena = "";
			}	
		}
	}
	
	//CICLO PARA INSERTAR LOS IVAS
	if (isset($frmTotalFactura['cbx'])){
		foreach ($frmTotalFactura['cbx'] as $indice => $valor){
                    
			$verificarIva = sprintf("SELECT * FROM sa_orden_tot_iva WHERE id_orden_tot = %s AND id_iva = %s",
										$idOrdenTOT,
										$frmTotalFactura["idIva".$valor.""]);
			$rsVerificarIva = mysql_query($verificarIva);
			 if (!$rsVerificarIva) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			 
			if(!mysql_num_rows($rsVerificarIva)){//sino tiene el tipo de iva ya insertado, verificacion extra
				
				$baseImponibleIva = str_replace(',','',($frmTotalFactura['txtSubTotal'] - $frmTotalFactura['txtMontoExento']));
				$subtotalIva = str_replace(',','',$frmTotalFactura["montoPorcentajeIva".$valor.""]);
				$idIva = $frmTotalFactura["idIva".$valor.""];
		
				//verifico el monto calculado por javascript con el monto calculado segun php
				$verificacionSubtotal = round(($baseImponibleIva * ($valor/100)),2);
			   
				if("$verificacionSubtotal" != "$subtotalIva"){//string contra string
					return $objResponse->alert("Verifique el monto del iva, pre-calculado: ".$subtotalIva." calculado: ".$verificacionSubtotal);
				}
											 
				$queryInsertIva = sprintf("INSERT INTO sa_orden_tot_iva (id_orden_tot, base_imponible, subtotal_iva, id_iva, iva) 
											VALUES (%s, '%s', '%s', '%s', '%s');", 
											$idOrdenTOT,
											$baseImponibleIva,//subtotal(subtotal - exento)
											$subtotalIva,//monto del iva (subtotal(subtotal - exento) * 0.12 = total iva)
											$idIva,//id del iva (12.00 = 2) segun pg_iva
											$valor);//12.00
	   
				mysql_query("SET NAMES 'utf8'");

				$rsInsertarIva = mysql_query($queryInsertIva);
				if (!$rsInsertarIva) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

				mysql_query("SET NAMES 'latin1';");
			}
		}//fin foreach
	}
        	
	
	$arrayRespuesta = cargarAFactura($idOrdenTOT, $frmDatosFactura, $frmTotalFactura);//este tiene el commit, usado adentro porque tiene que ser antes de la funcion de ernesto        
	//mysql_query("COMMIT;");
        
	//$objResponse->script("xajax_cargarAFactura('".$idOrdenTOT."',xajax.getFormValues('frmDatosFactura'));");
	
	if(!$arrayRespuesta[0]){
		return $objResponse->alert($arrayRespuesta[1]);//el msj string si hay error
	}else{
		return $arrayRespuesta[1];//response creado interno en la funcion, redirige
	}
}

function asignarDetallesRetencion($idRetencion){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_retenciones WHERE id = '".$idRetencion."'";
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$row = mysql_fetch_array($rs);

	$objResponse->assign("hddMontoMayorAplicar","value",$row['importe']);
	$objResponse->assign("hddPorcentajeRetencion","value",$row['porcentaje']);
	$objResponse->assign("hddSustraendoRetencion","value",$row['sustraendo']);
	$objResponse->assign("hddCodigoRetencion","value",$row['codigo']);
	$objResponse->script("calcularRetencion();");
	
	return $objResponse;
}

function asignarTot($idOrden){
	$objResponse = new xajaxResponse();
	
        if(esEditar()){
            $tdEliminarTrabajos = "<td class='tituloCampo'></td>";
            $disabledTrabajo = "";
            $displayTrabajo = "";
            
            $sql = sprintf("SELECT sa_filtro_orden.tot_accesorio
							FROM sa_orden_tot 
							INNER JOIN sa_orden ON sa_orden_tot.id_orden_servicio = sa_orden.id_orden 
							INNER JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
							INNER JOIN sa_filtro_orden ON sa_tipo_orden.id_filtro_orden = sa_filtro_orden.id_filtro_orden
							WHERE id_orden_tot = %s LIMIT 1",
                    $idOrden);
            $rs = mysql_query($sql);
            if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
            
            $row = mysql_fetch_assoc($rs);
            if($row["tot_accesorio"] == 1){
               $displayAccesorio = "";
               $displayTrabajo = "style='display:none'";
            }else{                
               $displayAccesorio = "style='display:none'";
               $displayTrabajo = "";
            }
            
        }else{
            $tdEliminarTrabajos = "";
            $disabledTrabajo = "disabled='disabled'";
            $displayTrabajo = "style='display:none'";
            
            $disabledAccesorio = "disabled='disabled'";
            $displayAccesorio = "style='display:none'";
        }
        
	//cambia el tr para agregar los trabajos requeridos con su monto
	$cadena = "<form id='frmDetallesTrabajoRequeridos' name='frmDetallesTrabajoRequeridos' style='margin:0'>
		   <table border='0' width='100%'><tr>
			   <td align='left' class='textoNegrita_12px' colspan='6'>
					<hr>
					<table width='100%' >
						<tr>
							<td width='50%'>
								<button type='button' class='puntero' ".$displayTrabajo." ".$disabledTrabajo." id='btnInsertarTrabajoRequerido' name='btnInsertarTrabajoRequerido' onclick=\" $('tblTrabajoRequeridos').style.display = ''; $('divFlotante').style.display = ''; $('divFlotanteTitulo').innerHTML = 'Agregar Detalle'; $('tblListados').style.display = 'none'; centrarDiv($('divFlotante')); document.forms['frmTrabajoRequerido'].reset();\" title='Agregar Detalle'><img src='../img/iconos/ico_agregar.gif'/></button>
                                                                <button type='button' class='puntero' ".$displayAccesorio." ".$disabledAccesorio." id='btnInsertarAccesorios' name='btnInsertarAccesorios' onclick='abrirAccesorios();' title='Agregar Detalle'><img src='../img/iconos/ico_agregar.gif'/></button>
			&nbsp;
			<button type='button' class='puntero' id='btnEliminarTrabajoRequerido' name='btnEliminarTrabajoRequerido' disabled='disabled' onclick=\" xajax_eliminarTrabajoRequerido(xajax.getFormValues('frmDetallesTrabajoRequeridos'));\" title='Eliminar Detalle'><img src='../img/iconos/ico_quitar.gif'/></button>
							</td>
							<td width='50%' align='right' style='display:'>
								Costo Total<input type='radio' id='idRdo0' name='idRdo' value='0' checked='checked' onclick=\" xajax_listadoDetalles(xajax.getFormValues('frmDetallesTrabajoRequeridos'), $('idOrdenTOT').value,0); $('txtSubTotal').value = ''; $('txtTotalOrden').value = '';  $('hddObjTotalDetalles').value = '0'; $('txtMontoExento').value = 0;\" />
								Costo Por Detalle<input type='radio' id='idRdo1' name='idRdo' value='1' onclick=\" xajax_listadoDetalles(xajax.getFormValues('frmDetallesTrabajoRequeridos'), $('idOrdenTOT').value,1); $('txtSubTotal').value = ''; $('txtTotalOrden').value = '';  $('hddObjTotalDetalles').value = '1'; $('txtMontoExento').value = 0;\" />
							</td>
						</tr>
					</table>
			   </td>
		   </tr>
		   <tr>
			   <td align='center' class='tituloArea' colspan='6'>Trabajos Requeridos<input type='hidden' id='hddObj' name='hddObj' /></td>
		   </tr>
		   <tr>
			   ".$tdEliminarTrabajos."<td align='center' class='tituloCampo' width='78%'>Descripci&oacute;n</td><td align='center' class='tituloCampo' width='10%'>Cantidad</td><td align='center' class='tituloCampo' width='20%'>Costo</td>
		   </tr>
		   <tr id='trPie'>
			   <td><br /><br /><br /></td>
		   </tr>
		   </table>
		   </form>";
	
	$objResponse->assign("tdTrabajosRequeridos","innerHTML",$cadena);
	
	$queryTot = sprintf("SELECT *, CONCAT_WS('-',lrif,rif) as lrif_rif FROM vw_sa_orden_tot WHERE id_orden_tot = %s", valTpDato($idOrden,"int"));
	$rsTot = mysql_query($queryTot);
	if (!$rsTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
	$rowTot = mysql_fetch_assoc($rsTot);
	                   
	$objResponse->assign("idOrdenTOT","value",$rowTot['id_orden_tot']);
	//$objResponse->script("xajax_comboEmpresa(".$rowTot['id_empresa'].");");
	$objResponse->assign("txtIdProv","value",$rowTot['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",utf8_encode($rowTot['nombre']));
	$objResponse->assign("txtRifProv","value",$rowTot['lrif_rif']);
	$objResponse->assign("txtContactoProv","value",utf8_encode($rowTot['contacto']));
	$objResponse->assign("txtEmailContactoProv","value",utf8_encode($rowTot['correococtacto']));
	$objResponse->assign("txtDireccionProv","value",utf8_encode($rowTot['direccion']));
	$objResponse->assign("txtTelefonosProv","value",$rowTot['telefono']);
	$objResponse->assign("txtFaxProv","value",$rowTot['fax']);
	
	$objResponse->assign("txtPlaca","value",utf8_encode($rowTot['placa']));
	$objResponse->assign("numeroOrdenMostrar","value",$rowTot['numero_orden']);//nuevo gregor
	$objResponse->assign("txtOrden","value",$rowTot['id_orden_servicio']);
	$objResponse->assign("txtChasis","value",utf8_encode($rowTot['chasis']));
	$objResponse->assign("txtUnidadBasica","value",utf8_encode($rowTot['des_uni_bas']));
	$objResponse->assign("txtMarca","value",utf8_encode($rowTot['nom_marca']));
	$objResponse->assign("txtModelo","value",utf8_encode($rowTot['des_modelo']));
	$objResponse->assign("txtAno","value",$rowTot['ano_uni_bas']);
	$objResponse->assign("txtColor","value",utf8_encode($rowTot['color']));
	
	
	if ($rowTot['estatus'] >= 1){
		
		$objResponse->assign("txtNumeroFacturaProveedor","value",$rowTot['numero_factura_proveedor']);
		$objResponse->assign("txtNumeroControl","value",$rowTot['numero_control_factura']);
		$objResponse->assign("txtFechaProveedor","value",date("d-m-Y",strtotime($rowTot['fecha_factura_proveedor'])));
		$objResponse->script("xajax_comboTipoPago('".$rowTot['tipo_pago']."')");    //0 = Contado; 1 = Credito
		$objResponse->script("xajax_comboAplicaLibros(".$rowTot['aplica_libros'].")");    //1 = Si; 0 = No
		$objResponse->assign("txtFechaOrigen","value",date("d-m-Y",strtotime($rowTot['fecha_origen'])));
		$objResponse->assign("txtObservacionFactura","innerHTML", mostrarTextArea($rowTot['observacion_factura']));
		
		$objResponse->assign("txtMontoExento","value",$rowTot['monto_exento']);
		$objResponse->assign("txtSubTotal","value",$rowTot['monto_subtotal']);
		$objResponse->assign("txtTotalOrden","value",$rowTot['monto_total']);
		
		$queryRadioRetencion = sprintf("SELECT porcentajeRetencion FROM cp_retenciondetalle WHERE idFactura = %s LIMIT 1",
										valTpDato($rowTot["id_factura"],"int")); //gregor
		$sqlRadioRetencion = mysql_query($queryRadioRetencion);
		if (!$sqlRadioRetencion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
		$rowRadioRetencion = mysql_fetch_assoc($sqlRadioRetencion);
		
		$radioPorcentajeRetencion = $rowRadioRetencion["porcentajeRetencion"];
		
		if($radioPorcentajeRetencion == 75){
			$seleccionRadio = "radio2";
		}else if($radioPorcentajeRetencion == 100){
			$seleccionRadio = "radio3";
		}else{
			$seleccionRadio = "radio";
		}
		$objResponse->assign($seleccionRadio, 'checked', true );
		
		$queryIva = "SELECT * FROM sa_orden_tot_iva WHERE id_orden_tot = '".$idOrden."'";
		$rsIva = mysql_query($queryIva);
		if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
		while ($rowIva = mysql_fetch_array($rsIva)){
			$objResponse->assign("montoPorcentajeIva".$rowIva['iva'],"value",$rowIva['subtotal_iva']);
			$objResponse->script("$('cbx".$rowIva['iva']."').checked = true;");
		}
    }else{ // si esta registrando la factura-numero-pagos-contado/credito  gregor

			//poner por defecto credito-contado dependiendo del tipo de pago de proveedor
		$sql = sprintf("SELECT credito FROM cp_proveedor WHERE id_proveedor = %s LIMIT 1",
						valTpDato($rowTot['id_proveedor'],"int"));
		$query = mysql_query($sql);
		if(!$query){ return $objResponse->alert("Error buscando el tipo de pago de proveedor \n".mysql_error()." \n LINE: ".__LINE__); }
		$row = mysql_fetch_assoc($query);
		$tipoPagoProveedor = $row["credito"];
		
		if($tipoPagoProveedor == "Si" || $tipoPagoProveedor == "1"){
			$objResponse->assign("slctTipoPago","value",1);
		}
	}
	
	$queryTotDetalle = "SELECT * FROM sa_orden_tot_detalle WHERE id_orden_tot = '".$idOrden."';";
	$rsTotDetalle = mysql_query($queryTotDetalle);
	if (!$rsTotDetalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
	
	$cadena = "";
	$sigValor = 0;
	while ($rowTotDetalle = mysql_fetch_array($rsTotDetalle)){
		
		$sigValor ++;
		
		if ($rowTotDetalle['monto'] == NULL){
			$monto = "";
		}else{
			$monto = $rowTotDetalle['monto'];
		}
                
		if(esEditar()){
			$eliminarTrabajos = "new Element('td').setHTML(\"<img onClick='xajax_eliminarTrabajoExistente(".$sigValor.",".$rowTotDetalle['id_orden_tot_detalle'].");' class='puntero' src='../img/iconos/ico_quitar.gif'>\"),";
		}else{
			$eliminarTrabajos = "";
		}
		
		$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px', 'title':'trItm:%s'}).adopt([
                                %s new Element('td', {'align':'left', 'id':'tdItm:%s'}).setHTML(\"%s".
				"<input type='hidden' id='txtIdDescripcion%s' name='txtIdDescripcion%s' value='%s'/> <input type='hidden' id='idAccesorio%s' name='idAccesorio%s' value='%s'/>\"),
                                new Element('td',{'align':'right'}).setHTML(\"<input type='text' id='txtCantidad%s' name='txtCantidad%s' value='%s' size='10&permil;' readonly='readonly' style='text-align:center' />\"),
				new Element('td',{'align':'right'}).setHTML(\"<input type='text' id='txtMonto%s' name='txtMonto%s' value='%s' size='20&permil;' readonly='readonly' style='text-align:right'/>\")
			]);
			elemento.injectBefore('trPie');",
			$sigValor, $sigValor,
                        $eliminarTrabajos,
			$sigValor, str_replace("\n","<br>",utf8_encode($rowTotDetalle['descripcion_trabajo'])),
			$sigValor, $sigValor, $rowTotDetalle['id_orden_tot_detalle'],
			$sigValor, $sigValor, $rowTotDetalle['id_precio_tot'],
			$sigValor, $sigValor, $rowTotDetalle['cantidad'],
			$sigValor, $sigValor, $monto
                        ));
			$cadena .= "|".$sigValor;		
	}
	
	$objResponse->assign("hddObj","value",$cadena);
	
	return $objResponse;
}

function asignarTrabajoRequerido($frmTrabajoRequerido,$frmDetallesTrabajoRequeridos){
	$objResponse = new xajaxResponse();
	
	for ($cont = 0; $cont <= strlen($frmDetallesTrabajoRequeridos['hddObj']); $cont++) {
		$caracter = substr($frmDetallesTrabajoRequeridos['hddObj'], $cont, 1);
		
		if($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
		}else{
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	$sigValor = $arrayObj[count($arrayObj)-1] + 1;
        
	if(esEditar()){
		$sql = sprintf("INSERT INTO sa_orden_tot_detalle (id_orden_tot, descripcion_trabajo) VALUES (%s,%s)",
				valTpDato($_GET["id"],"int"),
				valTpDato($frmTrabajoRequerido['txtDescripcionTrabajoRequerido'],"text"));
		$rs = mysql_query($sql);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
		
		if(mysql_affected_rows()){
			$objResponse->script("
				$('tblListados').style.display='none';
				$('divFlotante').style.display='none';");

			$objResponse->alert("agregado correctamente");
			$objResponse->script("actualizarListadoItems();");
		}else{
			$objResponse->alert("No se pudo agregar");
		}
		return $objResponse;
		
	}
        
//        if(esEditar()){//si esta agregando por editar, colocar cantidad y monto
//            if($frmDetallesTrabajoRequeridos["idRdo"] == "1"){
//                $readonly = "";
//            }else{
//                $readonly = "readonly = 'readonly'";
//            }
//            
//             $inputsEditar = sprintf(", new Element('td',{'align':'right'}).setHTML(\"<input type='text' id='txtCantidad%s' name='txtCantidad%s' ".$readonly." size='10' onkeypress='return numeros(event);' style='text-align:right;' />\"), "
//                     . "new Element('td',{'align':'right'}).setHTML(\"<input type='text' id='txtMonto%s' name='txtMonto%s' title='txtMonto%s' ".$readonly." size='20' onkeypress='return validarSoloNumerosReales(event);' style='text-align:right' />\")",
//                     $sigValor, $sigValor,
//                     $sigValor, $sigValor, $sigValor);
//        }else{
//            $inputsEditar = "";
//        }
	
	$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px', 'title':'trItm:%s'}).adopt([
			new Element('td', {'align':'center'}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s' />\"),
			new Element('td', {'align':'left', 'id':'tdItm:%s'}).setHTML(\"%s".
			"<input type='hidden' id='txtDescripcion%s' name='txtDescripcion%s' value='%s'/>\")".$inputsEditar."
		]);
		elemento.injectBefore('trPie');",
		$sigValor, $sigValor,
		$sigValor,
		$sigValor, str_replace("\n","<br>",$frmTrabajoRequerido['txtDescripcionTrabajoRequerido']),
		$sigValor, $sigValor, str_replace("\n","<br>",$frmTrabajoRequerido['txtDescripcionTrabajoRequerido'])));
		
	$arrayObj[] = $sigValor;
	foreach($arrayObj as $indice => $valor) {
		$cadena = $frmDetallesTrabajoRequeridos['hddObj']."|".$valor;
	}
	
	$objResponse->assign("hddObj","value",$cadena);
	
	$objResponse->script("
		$('tblListados').style.display='none';
		$('divFlotante').style.display='none';");
	
	return $objResponse;
}

function asignarProveedor($idProveedor, $editar = 2){
	$objResponse = new xajaxResponse();
        
	if($editar == "1"){//si es uno actualiza sino agrega
		$queryActualizarProveedor = sprintf("UPDATE sa_orden_tot SET id_proveedor = %s WHERE id_orden_tot = %s",
									$idProveedor,
									valTpDato($_GET["id"],"int"));
		$rsActualizarProveedor = mysql_query($queryActualizarProveedor);
		if (!$rsActualizarProveedor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
		
		$objResponse->alert("Proveedor del tot actualizado");
	}
	
	$queryProveedor = sprintf("SELECT *, CONCAT_WS('-',lrif, rif) as lrif_rif FROM cp_proveedor WHERE id_proveedor = %s", 
						valTpDato($idProveedor,"text"));
	$rsProveedor = mysql_query($queryProveedor);
	if (!$rsProveedor) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
	$rowProveedor = mysql_fetch_assoc($rsProveedor);
	
	$objResponse->assign("txtIdProv","value",$rowProveedor['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",utf8_encode($rowProveedor['nombre']));
	$objResponse->assign("txtRifProv","value",$rowProveedor['lrif_rif']);
	$objResponse->assign("txtContactoProv","value",utf8_encode($rowProveedor['contacto']));
	$objResponse->assign("txtEmailContactoProv","value",utf8_encode($rowProveedor['correococtacto']));
	$objResponse->assign("txtDireccionProv","value",utf8_encode($rowProveedor['direccion']));
	$objResponse->assign("txtTelefonosProv","value",$rowProveedor['telefono']);
	$objResponse->assign("txtFaxProv","value",$rowProveedor['fax']);
	
	$objResponse->script("
			$('tblListados').style.display='none';
			$('divFlotante').style.display='none';");
	return $objResponse;
}

function asignarVehiculo($idOrden,$verificar = false, $editar = 2){
	$objResponse = new xajaxResponse();
	
	$sql = sprintf("SELECT * FROM sa_orden_tot WHERE id_orden_servicio = %s",
					$idOrden);
	$rs = mysql_query($sql);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$cantidadTot = mysql_num_rows($rs);
	
	if ($cantidadTot > 0 && $verificar == false){//si tiene mas de 1 pedir confirmacion, si es 0 o verificar true dejar pasar
		$objResponse->script("var conf = confirm('La orden ya tiene: ".$cantidadTot." tot, ¿deseas agregar otro?'); "
				. "if (conf === true){ xajax_asignarVehiculo(".$idOrden.",true, ".$editar.") }");
	}else{
		
		if($editar == "1"){
			mysql_query("START TRANSACTION");
			$sqlEliminar = sprintf("DELETE FROM sa_det_orden_tot WHERE id_orden_tot = %s",
							valTpDato($_GET['id'], "int"));
			$rsEliminar = mysql_query($sqlEliminar);
			if(!$rsEliminar) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			$sqlActualizarOrdenTot = sprintf("UPDATE sa_orden_tot SET id_orden_servicio = %s WHERE id_orden_tot = %s",
									   $idOrden,
										valTpDato($_GET['id'], "int"));
			$rsActualizarOrdenTot = mysql_query($sqlActualizarOrdenTot);
			if(!$rsActualizarOrdenTot) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			
			$objResponse->alert("El tot fue actualizado con la nueva orden");
			mysql_query("COMMIT");
		}
	
		$queryRecepcion = sprintf("SELECT vw_sa_orden.*,  sa_filtro_orden.tot_accesorio
									FROM vw_sa_orden
									INNER JOIN sa_tipo_orden ON vw_sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
									INNER JOIN sa_filtro_orden ON sa_tipo_orden.id_filtro_orden = sa_filtro_orden.id_filtro_orden
									WHERE id_orden = %s LIMIT 1", 
							valTpDato($idOrden ,"text"));
		$rsRecepcion = mysql_query($queryRecepcion);
		if (!$rsRecepcion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$rowRecepcion = mysql_fetch_assoc($rsRecepcion);

		$objResponse->assign("txtOrden","value",$idOrden);
		$objResponse->assign("numeroOrdenMostrar","value",$rowRecepcion['numero_orden']);//nuevo gregor
		$objResponse->assign("txtPlaca","value",utf8_encode($rowRecepcion['placa']));
		$objResponse->assign("txtChasis","value",utf8_encode($rowRecepcion['chasis']));
		$objResponse->assign("txtMarca","value",utf8_encode($rowRecepcion['nom_marca']));
		$objResponse->assign("txtModelo","value",utf8_encode($rowRecepcion['des_modelo']));
		$objResponse->assign("txtUnidadBasica","value",utf8_encode($rowRecepcion['des_uni_bas']));
		$objResponse->assign("txtAno","value",$rowRecepcion['ano_uni_bas']);
		$objResponse->assign("txtColor","value",utf8_encode($rowRecepcion['color']));

		if($rowRecepcion['tot_accesorio'] == 1){
			$objResponse->script("$('btnInsertarAccesorios').style.display='';");
			$objResponse->script("$('btnInsertarTrabajoRequerido').style.display='none';");
		}else{
			$objResponse->script("$('btnInsertarAccesorios').style.display='none';");
			$objResponse->script("$('btnInsertarTrabajoRequerido').style.display='';");
		}
		
		$objResponse->script("
						$('tblListados').style.display='none';
						$('divFlotante').style.display='none';");
	
	}
        
	return $objResponse;
}

function buscarProveedor($valForm, $editar = 2){
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoProveedores(0,'','','%s');",$valForm['txtCriterioBusqueda']."|".$editar));
	
	return $objResponse;
}

function buscarVehiculos($valForm, $idEmpresa,$editar = 2){
	$objResponse = new xajaxResponse();
	
	if($idEmpresa == "" || $idEmpresa == NULL || $idEmpresa == -1 || $idEmpresa == 0){
		return $objResponse->alert("Debe seleccionar empresa");
	}
	
	$objResponse->script(sprintf("xajax_listadoVehiculos(0,'numero_orden','DESC','%s', 10, '');",$valForm['txtCriterioBusqueda'].'|'.$editar.'|'.$idEmpresa));
	
	return $objResponse;
}

function calcularTotal($frmTotalFactura,$frmDetallesTrabajoRequeridos,$accion){
	$objResponse = new xajaxResponse();
	
	$subTotal = $frmTotalFactura['txtSubTotal'];
	
	if ($accion == 1){//1 individual, 2 total
		/* DESCONCATENA PARA IR calculando los montos*/
		for ($cont = 1; $cont <= strlen($frmDetallesTrabajoRequeridos['hddObj']); $cont++) {
			$caracter = substr($frmDetallesTrabajoRequeridos['hddObj'], $cont, 1);
			
			if($caracter != "|" && $caracter != ""){
				$cadena .= $caracter;
			}else{
				if($frmDetallesTrabajoRequeridos["txtCantidad".$cadena] != NULL && $frmDetallesTrabajoRequeridos["txtCantidad".$cadena] != "" && $frmDetallesTrabajoRequeridos["txtCantidad".$cadena] !="0"){
					$montoDescripcion += $frmDetallesTrabajoRequeridos["txtCantidad".$cadena]*$frmDetallesTrabajoRequeridos["txtMonto".$cadena];
				}else{
					$montoDescripcion += $frmDetallesTrabajoRequeridos["txtMonto".$cadena];
				}
				$cadena = "";
			}	
		}
		$objResponse->script("$('txtSubTotal').value = ".$montoDescripcion.";");
		$subTotal = $montoDescripcion;
	}
	
	//Limpiar los campos de ivas
	$arregloValores = explode("|",$frmTotalFactura['hddObjValoresIva']);
	for ($i = 0; $i < (count($arregloValores) - 1); $i++){
		$objResponse->script("$('montoPorcentajeIva".$arregloValores[$i]."').value = '';");
	}
	$subTotal -= $frmTotalFactura['txtMontoExento'];
	if (isset($frmTotalFactura['cbx'])){
		foreach ($frmTotalFactura['cbx'] as $indice => $valor){
			$totalIva = $subTotal * $valor / 100;
			$objResponse->assign("montoPorcentajeIva".$valor,"value",number_format($totalIva,2,".",","));
			$arrayValores .= $valor."|";
			$total += $totalIva;
		}
	
		$total += $subTotal + $frmTotalFactura['txtMontoExento'];
	}else{
		$total = $frmTotalFactura['txtSubTotal'];
		$objResponse->assign("txtMontoExento","value",$frmTotalFactura['txtSubTotal']);
	}
	
	$objResponse->assign("txtTotalOrden","value",number_format($total,2,".",","));
	$objResponse->assign("hddObjValoresIva","value",$arrayValores);
		
	if($subTotal < 0){
		$objResponse->alert("EL SubTotal debe ser mayor o igual al Monto Exento");
		$objResponse->script("$('txtMontoExento').className = 'inputErrado';
							  $('txtSubTotal').className = 'inputErrado';
							  $('btnGuardar').disabled = 'disabled'");
	}else{
		$objResponse->script("$('txtMontoExento').className = 'inputHabilitado';							  
							  $('btnGuardar').disabled = '';");
		if($frmDetallesTrabajoRequeridos['idRdo'] == 0){//si es total
			$objResponse->script("$('txtSubTotal').className = 'inputHabilitado';");
		}else{// si es individual
			$objResponse->script("$('txtSubTotal').className = '';");
		}
	}

	//copiado de base islr y calculo
	$objResponse->script("byId('txtBaseRetencionISLR').value = byId('txtSubTotal').value;
							calcularRetencion();");
	
	//gregor iva
	if($accion == 2){ // SI es click en IVA u otros eliminar el monto excento y calcular
	$objResponse->assign("txtMontoExento", "value", 0);
		$objResponse->script(" xajax_calcularTotal(xajax.getFormValues('frmTotalFactura'),xajax.getFormValues('frmDetallesTrabajoRequeridos'),0);");
	}
			
	return $objResponse;
}

function cargarAFactura($idOrdenTOT,$valForm, $frmTotalFactura){
	$objResponse = new xajaxResponse();
	
	//mysql_query("START TRANSACTION;"); //se inicia transaccion en una funcion anterior
	
	if($valForm['rbtRetencion'] == 3){
		$rbtPorcentajeRetencion = 100;
	}else if ($valForm['rbtRetencion'] == 2){
		$rbtPorcentajeRetencion = 75;
	}else{
		$rbtPorcentajeRetencion = 0;
	}
	
	$queryOrdenTOT = "SELECT *, tipo_pago-1 AS tipo_pago_numero FROM sa_orden_tot WHERE id_orden_tot = '".$idOrdenTOT."'";
	$rsOrdenTOT = mysql_query($queryOrdenTOT);
	if (!$rsOrdenTOT) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
	$rowOrdenTOT = mysql_fetch_array($rsOrdenTOT);
	
	$sqlProveedor = "SELECT * FROM cp_proveedor WHERE id_proveedor = '".$rowOrdenTOT['id_proveedor']."'";
	$mysqlProveedor = mysql_query($sqlProveedor);
	if (!$mysqlProveedor) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
	$proveedor = mysql_fetch_array($mysqlProveedor);
	
	if($proveedor['credito'] == 'Si'){		
		$sqlCredito = "SELECT * FROM cp_prove_credito WHERE id_proveedor = '".$rowOrdenTOT['id_proveedor']."'";
		$mysqlCredito = mysql_query($sqlCredito);
		if (!$mysqlCredito) { return array(false,mysql_error()."\n\nLine: ".__LINE__); } 
		$credito = mysql_fetch_array($mysqlCredito);
		
		$date_result1=dateadd($rowOrdenTOT['fecha_factura_proveedor'],0,$credito['diascredito'],0);
		$date_result1=substr($date_result1,6,4).substr($date_result1,2,4).substr($date_result1,0,2); 
	} else {		//$date_result1=substr($rowOrdenTOT['fecha_factura_proveedor'],6,4).substr($rowOrdenTOT['fecha_factura_proveedor'],2,4).substr($rowOrdenTOT['fecha_factura_proveedor'],0,2);//anterior dañado
		$date_result1= date("Y-m-d",strtotime($rowOrdenTOT['fecha_factura_proveedor']));
	}
	
	//return array(false,$date_result1);
	//valido si ya fue facturado
	$sqlYaFacturado = "SELECT * FROM cp_factura WHERE id_modulo = 1 AND id_orden_compra = '".$rowOrdenTOT['id_orden_tot']."'";
	$rsYaFacturado = mysql_query($sqlYaFacturado);
	if (!$rsYaFacturado) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
   
	if(mysql_num_rows($rsYaFacturado)){
		return array(false,"Este T.O.T ya fue facturado");
	}			

	/* INSERTA LA FACTURA*/
	$insertFacturaDatos = sprintf ( "INSERT INTO cp_factura( numero_factura_proveedor, numero_control_factura, fecha_factura_proveedor, id_proveedor, fecha_origen, fecha_vencimiento, id_modulo, estatus_factura, observacion_factura, tipo_pago, monto_exento, monto_exonerado, subtotal_factura, subtotal_descuento, saldo_factura, aplica_libros, id_empresa, id_orden_compra, porcentaje_descuento, total_cuenta_pagar, id_empleado_creador, fecha_registro) 
										VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s, NOW())",
			$rowOrdenTOT['numero_factura_proveedor'],
			$rowOrdenTOT['numero_control_factura'],
			date("Y-m-d",strtotime($rowOrdenTOT['fecha_factura_proveedor'])),
			$rowOrdenTOT['id_proveedor'],
			date("Y-m-d",strtotime($rowOrdenTOT['fecha_origen'])),
			$date_result1,
			'1',
			'0',
			utf8_encode($rowOrdenTOT['observacion_factura']),
			$rowOrdenTOT['tipo_pago_numero'],//es enum
			$rowOrdenTOT['monto_exento'],
			'0',
			$rowOrdenTOT['monto_subtotal'],
			'0',
			str_replace(',','',$rowOrdenTOT['monto_total']),
			$rowOrdenTOT['aplica_libros'],
			$rowOrdenTOT['id_empresa'],
			$rowOrdenTOT['id_orden_tot'],
			'0',
			$rowOrdenTOT['monto_total'],
			$_SESSION['idEmpleadoSysGts']);
	
	mysql_query("SET NAMES 'utf8'");
	
	$consultaFacturaDatos = mysql_query($insertFacturaDatos);
	$idFactura = mysql_insert_id();
	if (!$consultaFacturaDatos) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("SET NAMES 'latin1';");
		
	/*ACTUALIZA SA_ORDEN_TOT PARA AGREGARLE EL ID DE LA FACTURA*/
	$updateTOT = sprintf("UPDATE sa_orden_tot SET id_factura = %s WHERE id_orden_tot = %s",$idFactura,$idOrdenTOT);
	$rsUpdateTOT = mysql_query($updateTOT);
	
	if (!$rsUpdateTOT) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
	
	/* GUARDA EN LA TABLA DE IVA LOS IVA GUARDADOS */
	$queryIva = "SELECT * FROM sa_orden_tot_iva WHERE id_orden_tot = '".$idOrdenTOT."'";
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
	while ($rowIva = mysql_fetch_array($rsIva)){
		$insertivas = sprintf("INSERT INTO cp_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva) VALUE ('%s', '%s', '%s', '%s', '%s')",
				$idFactura,
				str_replace(',','',$rowIva['base_imponible']),
				str_replace(',','',$rowIva['subtotal_iva']),
				$rowIva['id_iva'],
				$rowIva['iva']);
		
		mysql_query("SET NAMES 'utf8'");
		
		$ResultInsertivas = mysql_query($insertivas);
		if (!$ResultInsertivas) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
		
		mysql_query("SET NAMES 'latin1';");
	}
	
	if ($rbtPorcentajeRetencion){
			
		// INSERTA LOS DATOS EN CP_RETENCIONCABEZERA
		
		// NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$queryNumeracion = sprintf("SELECT * 
									FROM pg_empresa_numeracion
									WHERE id_numeracion = %s
									AND (id_empresa = %s 
										OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre 
																					FROM pg_empresa suc
																					WHERE suc.id_empresa = %s)))
									ORDER BY aplica_sucursales DESC
									LIMIT 1;",
							valTpDato(2, "int"),
							valTpDato($rowOrdenTOT['id_empresa'], "int"),
							valTpDato($rowOrdenTOT['id_empresa'], "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$numeroActual = $rowNumeracion['numero_actual'];
		if($numeroActual == NULL){
			return array(false,"No se pudo generar numero de retencion, compruebe que la empresa tenga numeracion de Comprobante de Retenciones");
		}		
			
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
		if (!$rsInsertarRetencionCabezera) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
		
		mysql_query("SET NAMES 'latin1'");
		
		/* RETENCIONES POR CADA IVA EN TOT */
		mysql_data_seek($rsIva, 0);//es el query de iva tot                
		while($rowIva = mysql_fetch_assoc($rsIva)){//vuelvo a recorrer los registros
                      
			$alicuota = $rowIva['iva'];
			$montoAlicuota = $rowIva['subtotal_iva'];
			$porcentajeRetencion = ($rowIva['subtotal_iva'] * ($rbtPorcentajeRetencion / 100));                        

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
			if (!$rsInsertarRetencionDetalle) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }

			mysql_query("SET NAMES 'latin1'");

			$insertPago = sprintf("INSERT INTO cp_pagos_documentos (id_documento_pago, tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado) 
									VALUES (%s, 'FA', 'RETENCION', %s, NOW(), '%s', '-', '-', '-', '-', '%s')",
							$idFactura,
							$idRetencionCabezera,
							date("Ym",strtotime($rowOrdenTOT['fecha_origen'])).str_pad($numeroActual, 8, "0", STR_PAD_LEFT),
							$porcentajeRetencion);
			$rsInsertPago = mysql_query($insertPago);

			if (!$rsInsertPago) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }                
                        
		}		
		
		/*INCREMENTAR EL NUMERO DE COMPROBANTE DE RETENCION DE NUMERACIONES*/
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
		
		$updateFactura = sprintf("UPDATE cp_factura SET saldo_factura = '%s' WHERE id_factura = %s;",
								 str_replace(',','',($rowOrdenTOT['monto_total'] - $porcentajeRetencion)),
								 $idFactura);
		$rsUpdateFactura = mysql_query($updateFactura);		
		if (!$rsUpdateFactura) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	if ($frmTotalFactura['txtMontoRetencionISLR'] > 0){
		$queryRetencion = sprintf("INSERT INTO te_retencion_cheque (id_factura, id_retencion, base_imponible_retencion, sustraendo_retencion, porcentaje_retencion, monto_retenido, codigo, tipo, tipo_documento, fecha_registro)
									VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW());",
							valTpDato($idFactura,"int"), 
							valTpDato($frmTotalFactura['selRetencionISLR'],"int"),
							valTpDato($frmTotalFactura['txtBaseRetencionISLR'],"real_inglesa"), 
							valTpDato($frmTotalFactura['hddSustraendoRetencion'],"real_inglesa"), 
							valTpDato($frmTotalFactura['hddPorcentajeRetencion'],"real_inglesa"), 
							valTpDato($frmTotalFactura['txtMontoRetencionISLR'],"real_inglesa"), 
							valTpDato($frmTotalFactura['hddCodigoRetencion'],"text"), 
							0,// 0 = factura, 1 = nota de cargo
							2);// 0 = Cheque, 1 = Transferencia, 2 = Sin Documento

		$rsRetencion = mysql_query($queryRetencion);
		if (!$rsRetencion) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }

		$idRetencionCheque = mysql_insert_id();
		
		$queryCpPagoISLR = sprintf("INSERT INTO cp_pagos_documentos(id_documento_pago,tipo_documento_pago, tipo_pago, id_documento, fecha_pago, numero_documento, banco_proveedor, banco_compania, cuenta_proveedor, cuenta_compania, monto_cancelado, id_empleado_creador) 
									VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
							valTpDato($idFactura, "int"), 
							valTpDato('FA', "text"),
							valTpDato('ISLR', "text"),
							valTpDato($idRetencionCheque, "int"),
							'NOW()',
							valTpDato($idRetencionCheque, "int"),
							valTpDato('-', "text"),
							valTpDato('-', "text"),
							valTpDato('-', "text"),
							valTpDato('-', "text"),
							valTpDato($frmTotalFactura['txtMontoRetencionISLR'], "real_inglesa"),
							valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
		
		$consultaCpPagoISLR = mysql_query($queryCpPagoISLR);		
		if (!$consultaCpPagoISLR) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
		
		$sql = sprintf("SELECT saldo_factura, total_cuenta_pagar FROM cp_factura WHERE id_factura = %s LIMIT 1",
				valTpDato($idFactura, "int"));
		$rs = mysql_query($sql);
		if (!$rs) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
		$row = mysql_fetch_assoc($rs);
		
		$saldoFactura = $row['saldo_factura'] - $frmTotalFactura['txtMontoRetencionISLR'];

		if($saldoFactura == $row['total_cuenta_pagar']){
			$cambioEstado = 0;//0 = no cancelado, 1 = cancelado, 2 = parcialmente cancelado
		}elseif($saldoFactura == 0){
			$cambioEstado = 1;
		}else{
			$cambioEstado = 2;
		}
		
		$sql = sprintf("UPDATE cp_factura SET estatus_factura = %s, saldo_factura = %s 
						WHERE id_factura = %s ;",
				$cambioEstado,
				$saldoFactura, 
				$idFactura);	
		$rs = mysql_query($sql);
		if (!$rs) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
		
	}
	
	/* INSERTA ESTADO DE CUENTA */
	$insertEstadoCuenta = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN) 
									VALUES ('%s','%s','%s','%s')",
							'FA',
							$idFactura,
							$rowOrdenTOT['fecha_origen'],
							1);
		
	mysql_query("SET NAMES 'utf8'");
	
	$resultadoEstadoCuenta = mysql_query($insertEstadoCuenta);
	if (!$resultadoEstadoCuenta) { return array(false,mysql_error()."\n\nLine: ".__LINE__); }
	
	mysql_query("SET NAMES 'latin1'");
	
	mysql_query("COMMIT;");
		
	// MODIFICADO ERNESTO
	if(function_exists("generarComprasSe")){
		generarComprasSe($idFactura,"","");
	}
	// MODIFICADO ERNESTO
	
	$objResponse->alert("Orden guardada exitosamente");
	//$objResponse->script("borrarFormularios()");
	
	if($_GET['acc']==0){
		//FACTURA
		$objResponse->script("verVentana('sa_imprimir_registro_compra_pdf.php?valBusq=".$idOrdenTOT."',900,900);");
		 
		//RETENCION
		if($idRetencionCabezera){
			$objResponse->script("verVentana('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=".$idRetencionCabezera."',900,900);");
		}
		
		//ISLR
		if($idRetencionCheque){
		$objResponse->script("verVentana('../tesoreria/reportes/te_imprimir_constancia_retencion_pdf.php?id=".$idRetencionCheque."&documento=3',900,900);");		
		}

		
		$objResponse->script("window.open('sa_orden_tot_list.php','_self');");
	}else{
		$objResponse->script("window.open('sa_historico_tot.php','_self');");
	}
	
	return array(true,$objResponse); 
}

function cargarEmpleado(){
	$objResponse = new xajaxResponse();
	
	$queryUsuario = "SELECT nombre_empleado, nombre_cargo FROM vw_iv_usuarios WHERE id_usuario = '".$_SESSION['idUsuarioSysGts']."'";
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
	$rowUsuario = mysql_fetch_array($rsUsuario);
	
	$objResponse->assign("hddIdUsuario","value",$_SESSION['idUsuarioSysGts']);
	$objResponse->assign("tdNombreEmpleado","innerHTML",$rowUsuario['nombre_empleado']);
	$objResponse->assign("tdCargoEmpleado","innerHTML",$rowUsuario['nombre_cargo']);
	
	return $objResponse;
}


function cargarIvas(){
	$objResponse = new xajaxResponse();
	
	$porcentaje = "%&nbsp;";
	
	$queryIva = "SELECT * FROM pg_iva WHERE (tipo = '1' or tipo = '3') AND estado = '1'";
	$rsIva = mysql_query($queryIva) or die(mysql_query());
	$sigValor = 1;
	while ($rowIva = mysql_fetch_array($rsIva)){
		
		if($_GET['accion']==1 && $_GET['acc']==0 && $rowIva['idIva'] == 2){//cuando sea una nueva carga de factura por defecto iva 12 activado
			$checked = "checked='checked'";
		}else{
			$checked = "";
		}
                
		if ($_GET['accion']==0 && $_GET['acc']==1){//si esta cargando un historico buscar los iva ya guardados
			$queryIvaTot = sprintf("SELECT * FROM sa_orden_tot_iva WHERE id_orden_tot = %s AND id_iva = %s",
									valTpDato($_GET['id'],"int"),
									$rowIva['idIva']);
			$rsIvaTot = mysql_query($queryIvaTot);
			if(!$rsIvaTot) { return $objResponse->alert("Error \n\n".mysql_error()."\n\nLinea: ".__LINE__); }
			
			if(mysql_num_rows($rsIvaTot)){
				$checked = "checked='checked'";
			}else{
				$checked = "";
			}
		}
              		
		$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItmIva:%s', 'class':'textoGris_11px %s', 'title':'trItmIva:%s'}).adopt([
					new Element('td', {'align':'right','class':'tituloCampo'}).setHTML(\"%s:\"),
					new Element('td', {'align':'right'}).setHTML(\"<input id='cbx%s' name='cbx[]' type='checkbox' value='%s' ".$checked."/>\"),
					new Element('td', {'align':'right','style':'white-space:nowrap;'}).setHTML(\"<input id='porcentajeIva%s' name='porcentajeIva%s' type='text' readonly='readonly' style='text-align:right' size='5' value='%s' />%s <input type='hidden' id='idIva%s' name='idIva%s' value='%s'>\"),
					new Element('td', {'align':'right'}).setHTML(\"<input id='montoPorcentajeIva%s' name='montoPorcentajeIva%s' type='text' readonly='readonly' style='text-align:right' size='17' title='montoPorcentajeIva%s' />\")]);
				elemento.injectBefore('trPieFactura');
				
				$('cbx%s').onclick = function(){
					xajax_calcularTotal(xajax.getFormValues('frmTotalFactura'),xajax.getFormValues('frmDetallesTrabajoRequeridos'),2);
				}
				",
				$sigValor, $clase, $sigValor,
				$rowIva['observacion'],
				$sigValor, $rowIva['iva'],
				$sigValor, $sigValor, $rowIva['iva'], $porcentaje, $rowIva['iva'], $rowIva['iva'], $rowIva['idIva'],
				$rowIva['iva'], $rowIva['iva'], $rowIva['iva'],
				$sigValor));
		
		$cadena .= "|".$sigValor;
		$sigValor++;
	
	}
	$objResponse->assign("hddObjIva","value",$cadena);
        
	if($_GET['id'] > 0){
		$objResponse->script('tot(); //xajax_asignarTot('.$_GET["id"].');');
		$objResponse->loadCommands(asignarTot($_GET["id"]));
	}
        
	//tot orden editar
	 if($_GET['id'] > 0 && $_GET['accion'] == 1 && $_GET['acc'] == 0){
		 $objResponse->script("$('btnInsertarProveedor').style.display='none';
							   $('btnEditarProveedor').style.display='';
							   
								$('btnInsertarVehiculo').style.display='none';
								$('btnEditarVehiculo').style.display='';");
		
	 }
	 
	return $objResponse;
}

function comboAplicaLibros($selId){
	$objResponse = new xajaxResponse();

	if($selId == 0){
		$cero = "selected='selected'";
		$uno = "";  
	}else{
		$cero = "";
		$uno = "selected='selected'";
	}
	
	$html = "<label>";
    	$html .= "<select name='slctAplicaLibros' id='slctAplicaLibros'>";
        $html .= "<option value='1' ".$uno." >Si</option>";
        $html .= "<option value='0' ".$cero." >No</option>";
        $html .= "</select>";
	$html .= "</label>";
		
		$objResponse->assign("tdSelAplicaLibros","innerHTML",$html);
	
	return $objResponse;
}

function comboRetencionISLR(){
	$objResponse = new xajaxResponse();
	
	$idOrdenTot = $_GET['id'];
	
	if($idOrdenTot > 0){
		$queryEmpresa = sprintf("SELECT id_empresa FROM sa_orden_tot WHERE id_orden_tot = %s LIMIT 1", $idOrdenTot);
		$rsEmpresa = mysql_query($queryEmpresa);	
		if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);	
		$rowEmpresas = mysql_fetch_assoc($rsEmpresa);
	
		$pais = configPais($rowEmpresas['id_empresa']);
	}
	
	$queryRetenciones = "SELECT * FROM te_retenciones WHERE activo = 1";
	$rsRetenciones = mysql_query($queryRetenciones);
	
	$html = "<select id=\"selRetencionISLR\" name=\"selRetencionISLR\" class=\"inputHabilitado\"  onchange=\"xajax_asignarDetallesRetencion(this.value)\">";
	
	$html .= "<option value=\"\"> [ SELECCIONE ] </option>";
	while ($rowRetenciones = mysql_fetch_assoc($rsRetenciones)) {
		$selected = "";
		if($pais == 2 || $pais == 3){//si es puerto rico o panama, no obligar a que seleccione
			if($rowRetenciones['id'] == 1){ $selected = "selected=\"selected\""; }//1 sin retencion
		}
		$html .= "<option value=\"".$rowRetenciones['id']."\" ".$selected.">".utf8_encode($rowRetenciones['descripcion'])." (".$rowRetenciones['porcentaje']."%)"."</option>";
	}
	$html .= "</select>";
	//$objResponse->alert($html);
	$objResponse->assign("tdRetencionISLR","innerHTML",$html);
	$objResponse->assign("hddMontoMayorAplicar","value","0");
	$objResponse->assign("hddPorcentajeRetencion","value","0");
	$objResponse->assign("hddSustraendoRetencion","value","0");

	return $objResponse;
}

function comboTipoPago($selId){
	$objResponse = new xajaxResponse();
	
	if($selId == 'CONTADO'){
		$selId = 0;
	}else{
		$selId = 1;	
	}
	
	if($selId == 0){
		$cero = "selected='selected'";
		$uno = "";  
	}else{
		$cero = "";
		$uno = "selected='selected'";
	}
	
	$html = "<label>";
		$html .= "<select name='slctTipoPago' id='slctTipoPago'>";
			$html .= "<option  ".$cero." value='0'>Contado</option>";
			$html .= "<option ".$uno." value='1'>Credito</option>";
		$html .= "</select>";
    $html .= "</label>";

	$objResponse->assign("tdSelTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function contribuyente(){//muestra la seccion contribuyente, NO - 75% - 100%, sino es contribuyente se mancar radio NO
	$objResponse = new xajaxResponse();
	
	$idOrdenTot = $_GET['id'];
	
	$queryEmpresa = sprintf("SELECT contribuyente_especial FROM pg_empresa WHERE id_empresa = (SELECT id_empresa FROM sa_orden_tot WHERE id_orden_tot = %s)",$idOrdenTot);
	$rsEmpresa = mysql_query($queryEmpresa);	
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);	
	$rowEmpresas = mysql_fetch_array($rsEmpresa);
	
	if ($rowEmpresas['contribuyente_especial']){
		$objResponse->script("$('trRetencionIva').style.display = '';");
	}else{
		$objResponse->script("$('radio').checked = 'true';");
	}
	
	return $objResponse;
}

function eliminarTrabajoRequerido($valForm){
   
	$objResponse = new xajaxResponse();
	
	foreach($valForm['cbxItm'] as $indiceItm=>$valorItm) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
						
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItm));
	}
	
	$objResponse->script("actualizarListadoItems();");
	
	return $objResponse;
}

function eliminarTrabajoRequeridoForzado($valForm){
	$objResponse = new xajaxResponse();
	
	for ($cont = 0; $cont <= strlen($valForm['hddObj']); $cont++) {
		$caracter = substr($valForm['hddObj'], $cont, 1);

		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
		}else{
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	foreach($arrayObj as $indiceItm=>$valorItm) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
						
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItm));
	}

	$objResponse->script("xajax_actualizarObjetosExistentes(xajax.getFormValues('frmListaArticulo'))");
	
	return $objResponse;
}

function guardarOrdenCompra($idEmpresa,$idProveedor,$idValeRecepcion,$idUsuario,$formTrabajos){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"sa_orden_tot_list",insertar)){
		return $objResponse;
	}	
        
	mysql_query("START TRANSACTION");
		
	$sqlNumeroTot = sprintf("SELECT * FROM pg_empresa_numeracion
		WHERE id_numeracion = 36
			AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																			WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC
		LIMIT 1",
		valTpDato($idEmpresa,"int"),
		valTpDato($idEmpresa,"int")); 
	$rsSql = mysql_query($sqlNumeroTot);
	if (!$rsSql) return $objResponse->alert(mysql_error()."\n Error buscando numero de tot \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
	$dtSql = mysql_fetch_assoc($rsSql);
	
	$idEmpresaNumeroTot = $dtSql["id_empresa_numeracion"];
	$numeroTot = $dtSql["numero_actual"];
	
	if($numeroTot == NULL) { return $objResponse->alert("No se pudo crear el numero de tot, compruebe que la empresa tenga numeracion de tot"); }

	$query = "INSERT INTO sa_orden_tot (id_orden_tot, numero_tot, id_empresa, id_proveedor, id_orden_servicio, id_usuario, fecha_orden_tot) 
				VALUES ('','".$numeroTot."', '".$idEmpresa."', '".$idProveedor."', '".$idValeRecepcion."', '".$idUsuario."', '".date("Y-m-d")."');";
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
	
	$idOrden = mysql_insert_id();
	mysql_query("SET NAMES 'utf8'");
	
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
							WHERE id_empresa_numeracion = %s;",
					valTpDato($idEmpresaNumeroTot, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	/* DESCONCATENA PARA IR INSERTANDO LAS DESCRIPCIONES EN LA BD*/
	for ($cont = 1; $cont <= strlen($formTrabajos['hddObj']); $cont++) {
		$caracter = substr($formTrabajos['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
		}else{
			$queryInsertarTrabajos = "INSERT INTO sa_orden_tot_detalle (id_orden_tot_detalle, id_orden_tot, descripcion_trabajo, id_precio_tot)
                                                  VALUES ('', '".$idOrden."', '".str_replace("<br>","\n",$formTrabajos["txtDescripcion".$cadena])."' ,".valTpDato($formTrabajos["idAccesorio".$cadena],"int")." )";
			
			$rsInsertarTrabajos = mysql_query($queryInsertarTrabajos);
			//$objResponse->alert($queryInsertarTrabajos);//alerta
			if (!$rsInsertarTrabajos) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryInsertarTrabajos); 
			$cadena = "";
		}	
	}
	
	mysql_query("SET NAMES 'latin1'");
	
	if ($rs == true){
		$objResponse->alert("Orden guardada exitosamente");
		$objResponse->script("borrarFormularios()");
		if($_GET['acc'] == 0){                                   
			$objResponse->script("window.open('sa_orden_tot_list.php','_self');");
		}else{
			$objResponse->script("window.open('sa_historico_tot.php','_self');");
		}
	}
	
	mysql_query("COMMIT");
	
	return $objResponse;
}

function listadoDetalles($formDetalles,$idOrdenTOT,$accion){
	$objResponse = new xajaxResponse();
	
	for ($cont = 0; $cont <= strlen($formDetalles['hddObj']); $cont++) {
		$caracter = substr($formDetalles['hddObj'], $cont, 1);

		if($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
		}else{
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	foreach($arrayObj as $indiceItm=>$valorItm) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItm:%s');
						
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItm));
	}
	
	$queryTotDetalle = "SELECT * FROM sa_orden_tot_detalle WHERE id_orden_tot = '".$idOrdenTOT."';";
	$rsTotDetalle = mysql_query($queryTotDetalle);
	if (!$rsTotDetalle) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
	$sigValor = 0;
	
	if ($accion == 0){
		$objResponse->script("$('txtSubTotal').readOnly = false;
								byId('txtSubTotal').className = 'inputHabilitado'; ");
		$readonly = "readonly='readonly'";		
	}else{
		$objResponse->script("$('txtSubTotal').readOnly = true;
								byId('txtSubTotal').className = '';");
		$readonly = "";
		$claseInput = "class='inputHabilitado'";
	}
	                        
	while($rowTotDetalle = mysql_fetch_array($rsTotDetalle)){
		
		$sigValor ++;

		if(esEditar()){
			$eliminarTrabajos = "new Element('td').setHTML(\"<img onClick='xajax_eliminarTrabajoExistente(".$sigValor.",".$rowTotDetalle['id_orden_tot_detalle'].");' class='puntero' src='../img/iconos/ico_quitar.gif'>\"),";
		}else{
			$eliminarTrabajos = "";
		}
                
		$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px', 'title':'trItm:%s'}).adopt([
				%s new Element('td', {'align':'left', 'id':'tdItm:%s'}).setHTML(\"%s <input type='hidden' id='txtIdDescripcion%s' name='txtIdDescripcion%s' value='%s'/><input type='hidden' id='idAccesorio%s' name='idAccesorio%s' value='%s'/> \"),
                                new Element('td',{'align':'right'}).setHTML(\"<input type='text' id='txtCantidad%s' name='txtCantidad%s' ".$readonly." size='10' ".$claseInput." onkeypress='return numeros(event);' style='text-align:right;' />\"),
				new Element('td',{'align':'right'}).setHTML(\"<input type='text' id='txtMonto%s' name='txtMonto%s' title='txtMonto%s' ".$readonly." size='20' ".$claseInput." onkeypress='return validarSoloNumerosReales(event);' style='text-align:right' />\")
			]);
			elemento.injectBefore('trPie');

                        $('txtCantidad%s').onkeyup = function(){
				xajax_calcularTotal(xajax.getFormValues('frmTotalFactura'),xajax.getFormValues('frmDetallesTrabajoRequeridos'),1);
			}

			$('txtMonto%s').onkeyup = function(){
				xajax_calcularTotal(xajax.getFormValues('frmTotalFactura'),xajax.getFormValues('frmDetallesTrabajoRequeridos'),1);
			}",
			$sigValor, $sigValor,
                        $eliminarTrabajos,
			$sigValor, str_replace("\n"," ",utf8_encode($rowTotDetalle['descripcion_trabajo'])),			
			$sigValor, $sigValor, $rowTotDetalle['id_orden_tot_detalle'],
                        $sigValor, $sigValor, $rowTotDetalle['id_precio_tot'],
			$sigValor, $sigValor,
			$sigValor, $sigValor,
			$sigValor,
			$sigValor,
			$sigValor));
			
			$cadena .= "|".$sigValor;		
	}
        
	$objResponse->script('document.getElementById("frmTotalFactura").reset();');
	$objResponse->assign("hddObj","value",$cadena);
	$objResponse->script("byId('txtMontoExento').className = 'inputHabilitado';");
	
	return $objResponse;
}

function listadoProveedores($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	
	global $spanRIF;	
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	//valCadBusq[0] = criterio
	//valCadBusq[1] = 1 si es editar, 2 sino
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != ''){
		$condicion =  " WHERE rif LIKE '%".$valCadBusq[0]."%' OR nombre LIKE '%".$valCadBusq[0]."%'";
	}
	
	$query = "SELECT *, CONCAT_WS('-',lrif,rif) as lrif_rif FROM cp_proveedor".$condicion."";
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); 
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Proveedores");
	$objResponse->assign("tblListados","width","600px");
	
	$htmlTableIni .= "<table width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td>&nbsp;</td>
            	<td width=\"10%\">C&oacute;digo</td>
                <td width=\"53%\">Nombre</td>
                <td width=\"20%\">".$spanRIF."</td>
                <td width=\"17%\">&iquest;A Cr&eacute;dito&quest;</td>
            </tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" class=\"puntero\" onclick=\"xajax_asignarProveedor('".$row['id_proveedor']."', ".$valCadBusq[1].");\" title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td style='text-align:center;'>".$row['id_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td>".$row['lrif_rif']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['credito']."</td>";
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\">";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoProveedores(%s,'%s','%s','%s',%s)\">",
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
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	$objResponse->assign("tdListado","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("		
		$('tblListados').style.display='';
		$('tblTrabajoRequeridos').style.display='none';");
	
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
	");
	
	$objResponse->assign("tdCriterioBusqueda","innerHTML","Nombre/Rif:");
	$objResponse->script("$('txtCriterioBusqueda').onkeyup = function(){xajax_buscarProveedor(xajax.getFormValues('frmBuscar'), ".$valCadBusq[1].")}");
	$objResponse->script("$('btnBuscar').onclick = function(){xajax_buscarProveedor(xajax.getFormValues('frmBuscar'), ".$valCadBusq[1].")}");
	
	return $objResponse;
}

function listadoVehiculos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	//$valCadBusq[0] criterio numero de orden
	//$valCadBusq[1] = 1 si es editar, 2 sino
	//$valCadBusq[2] = id de la empresa
	$startRow = $pageNum * $maxRows;
        
        $idEmpresa = $valCadBusq[2];
	
	if ($valCadBusq[0] != ""){
		$condicion = " AND numero_orden LIKE '%".$valCadBusq[0]."%'";//nuevo gregor
	}else{
		$condicion = "";
	}
		
		//buscador se habia dañado, puse parentesis
	$query = "SELECT * FROM vw_sa_orden WHERE id_empresa = ".$idEmpresa." AND orden < 14 AND (id_tipo_orden <= 4 OR id_tipo_orden >=7) ".$condicion;
	
	//orden mayor de 14 es orden terminada... id_tipo_orden menor a 5 sacan las sin asignar y retrabajo
	
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
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Vehiculos");
	$objResponse->assign("tblListados","width","700px");
	
	$htmlTableIni .= "<table width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td width=\"4%\" align=\"center\">&nbsp;</td>
				<td width=\"16%\" align=\"center\">Nro Orden</td>
				<td width=\"16%\" align=\"center\">Tipo de Orden</td>
				<td width=\"16%\" align=\"center\">Estado</td>
            	<td width=\"16%\" align=\"center\">Placa</td>
                <td width=\"16%\" align=\"center\">Chasis</td>
                <td width=\"16%\" align=\"center\">Marca</td>
                <td width=\"16%\" align=\"center\">Modelo</td>
            </tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" class=\"puntero\" onclick=\"xajax_asignarVehiculo('".$row['id_orden']."',false,'".$valCadBusq[1]."');\" title=\"Seleccionar Vehiculo\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\" idordenoculta='".$row['id_orden']."'>".$row['numero_orden']."</td>";//nuevo gregor
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_estado'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['chasis'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nom_marca'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['des_modelo'])."</td>";
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\" >";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoVehiculos(%s,'%s','%s','%s',%s,'',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, $idEmpresa, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoVehiculos(%s,'%s','%s','%s',%s, '' ,%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, $idEmpresa, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoVehiculos(%s,'%s','%s','%s',%s, '' , %s)\">",
						"this.value", $campOrd, $tpOrd, $valBusq, $maxRows, $idEmpresa);
					for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
							$htmlTf.="<option value=\"".$nroPag."\"";
							if ($pageNum == $nroPag) {
								$htmlTf.="selected=\"selected\"";
							}
							$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
					}
					$htmlTf.="</select>";
					
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoVehiculos(%s,'%s','%s','%s',%s, '', %s );\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, $idEmpresa, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoVehiculos(%s,'%s','%s','%s',%s, '', %s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, $idEmpresa, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListado","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("		
		$('tblListados').style.display='';
		$('tblTrabajoRequeridos').style.display='none';");
	
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
	");
	
	$objResponse->assign("tdCriterioBusqueda","innerHTML","Nro Orden:");
	
	
	$objResponse->script("$('txtCriterioBusqueda').onkeyup = function(){xajax_buscarVehiculos(xajax.getFormValues('frmBuscar'), document.getElementById('selEmpresa').value, ".$valCadBusq[1].")}");
	$objResponse->script("$('btnBuscar').onclick = function(){xajax_buscarVehiculos(xajax.getFormValues('frmBuscar'), document.getElementById('selEmpresa').value, ".$valCadBusq[1].")}");
		
	return $objResponse;
}

function buscarAccesorio($idEmpresa, $txtCriterioBusquedaAccesorio){
    $objResponse = new xajaxResponse();
	
    $objResponse->script(sprintf("xajax_listadoAccesorios(0,'','','%s');",$idEmpresa."|".$txtCriterioBusquedaAccesorio));

    return $objResponse;
}

function listadoAccesorios($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
//	if($valCadBusq[0] == ""){
//            $valCadBusq[0] = $_SESSION['idEmpresaUsuarioSysGts'];
//	}
//	
//	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
//            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
//            $sqlBusq .= $cond.sprintf("orden.id_empresa = %s",
//                    valTpDato($valCadBusq[0], "int"));
//	}
        
	$sqlBusq = " WHERE activo = 1";
        
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sa_precios_tot.descripcion LIKE %s",
				valTpDato("%".$valCadBusq[1]."%", "text"));
	}
		
	$query = sprintf("SELECT id_precio_tot, descripcion, porcentaje 
                         FROM sa_precios_tot %s ", $sqlBusq); 
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert($queryLimit."\n".mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
                $htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoAccesorios", "10%", $pageNum, "id_precio_tot", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listadoAccesorios", "90%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripción"));
		//$htmlTh .= ordenarCampo("xajax_listadoAccesorios", "20%", $pageNum, "porcentaje", $campOrd, $tpOrd, $valBusq, $maxRows, ("Porcentaje"));		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";                
			$htmlTb .= "<td align=\"center\" ><button class=\"puntero\" title='Seleccionar Accesorio' onclick='xajax_asignarAccesorio(".$row['id_precio_tot'].",xajax.getFormValues(\"frmTrabajoRequerido\"),xajax.getFormValues(\"frmDetallesTrabajoRequeridos\"));' type='button'><img src='../img/iconos/ico_aceptar.gif'></button></td>";
			$htmlTb .= "<td align=\"center\" >".$row['id_precio_tot']."</td>";
			$htmlTb .= "<td align=\"center\" >".$row['descripcion']."</td>";
			//$htmlTb .= "<td align=\"center\" >".$row['porcentaje']."</td>";                
		$htmlTb .= "</tr>";			
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"18\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorios(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorios(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoAccesorios(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorios(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAccesorios(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
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
		$htmlTb .= "<td colspan=\"18\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoAccesorios","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
        
        $objResponse->script("
            $('divFlotante2').style.display = ''; 
            centrarDiv($('divFlotante2'));");
	
	return $objResponse;
}


function asignarAccesorio($idAccesorio,$frmTrabajoRequerido,$frmDetallesTrabajoRequeridos, $agregar = false){
	$objResponse = new xajaxResponse();
	
	//verificar repeticion del item
	$idItemAccesorios = explode("|",$frmDetallesTrabajoRequeridos['hddObj']);
	array_shift($idItemAccesorios);
	
	if($agregar === false){        
		foreach ($idItemAccesorios as $indice => $numeroItem){
			if($frmDetallesTrabajoRequeridos['idAccesorio'.$numeroItem] == $idAccesorio){
				return $objResponse->script("if(confirm('Ya se encuentra agregado, ¿Desea agregar otro?')){"
						. "xajax_asignarAccesorio(".$idAccesorio.",xajax.getFormValues('frmTrabajoRequerido'),xajax.getFormValues('frmDetallesTrabajoRequeridos'), true);"
						. " }");
			}
		}
	}
        
	$query = sprintf("SELECT descripcion FROM sa_precios_tot WHERE id_precio_tot = %s LIMIT 1",
                    $idAccesorio);
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert($query."\n".mysql_error()."\n\nLine: ".__LINE__); }
	
	$row = mysql_fetch_assoc($rs);
	$descripcionAccesorio = $row['descripcion'];
	
	if(esEditar()){
		$sql = sprintf("INSERT INTO sa_orden_tot_detalle (id_orden_tot, descripcion_trabajo, id_precio_tot) VALUES (%s,%s,%s)",
				valTpDato($_GET["id"],"int"),
				valTpDato($descripcionAccesorio,"text"),
				$idAccesorio);
		$rs = mysql_query($sql);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
		
		if(mysql_affected_rows()){
			$objResponse->script("
				$('divFlotante2').style.display='none';");

			$objResponse->alert("agregado correctamente");
			$objResponse->script("actualizarListadoItems();");
		}else{
			$objResponse->alert("No se pudo agregar");
		}
		return $objResponse;		
	}
        
	for ($cont = 0; $cont <= strlen($frmDetallesTrabajoRequeridos['hddObj']); $cont++) {
		$caracter = substr($frmDetallesTrabajoRequeridos['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
		}else{
			$arrayObj[] = $cadena;
			$cadena = "";
		}
	}
	
	$sigValor = $arrayObj[count($arrayObj)-1] + 1;
	
	$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px', 'title':'trItm:%s'}).adopt([
			new Element('td', {'align':'center'}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s' />\"),
			new Element('td', {'align':'left', 'id':'tdItm:%s'}).setHTML(\"%s".
			"<input type='hidden' id='txtDescripcion%s' name='txtDescripcion%s' value='%s'/><input type='hidden' id='idAccesorio%s' name='idAccesorio%s' value='%s'/>\")
		]);
		elemento.injectBefore('trPie');",
		$sigValor, $sigValor,
		$sigValor,
		$sigValor, $descripcionAccesorio,
		$sigValor, $sigValor, $descripcionAccesorio,
		$sigValor, $sigValor, $idAccesorio
		));
		
	$arrayObj[] = $sigValor;
	foreach($arrayObj as $indice => $valor) {
		$cadena = $frmDetallesTrabajoRequeridos['hddObj']."|".$valor;
	}
	
	$objResponse->assign("hddObj","value",$cadena);
	
	return $objResponse;
}

function eliminarTrabajoExistente($numeroItem, $idDetalleTot){//numero del listado de item en pantalla, id del detalle tot a eliminar
    
    $objResponse = new xajaxResponse();
    
    $sql = sprintf("DELETE FROM sa_orden_tot_detalle WHERE id_orden_tot_detalle = %s",
                    valTpDato($idDetalleTot,"int"));
    $rs = mysql_query($sql);
    if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLinea: ".__LINE__); }
    
    $objResponse->alert("Eliminado Correctamente");
    
    //manual visual
//    $objResponse->script(sprintf("
//                        fila = document.getElementById('trItm:%s');						
//                        padre = fila.parentNode;
//                        padre.removeChild(fila);",
//                        $numeroItem));

    $objResponse->script("actualizarListadoItems();");
    
    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentes");
$xajax->register(XAJAX_FUNCTION,"actualizarTOT");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesRetencion");
$xajax->register(XAJAX_FUNCTION,"asignarTot");
$xajax->register(XAJAX_FUNCTION,"asignarTrabajoRequerido");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarVehiculo");
$xajax->register(XAJAX_FUNCTION,"buscarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarVehiculos");
$xajax->register(XAJAX_FUNCTION,"calcularTotal");
$xajax->register(XAJAX_FUNCTION,"cargarAFactura");
$xajax->register(XAJAX_FUNCTION,"cargarEmpleado");
$xajax->register(XAJAX_FUNCTION,"cargarIvas");
$xajax->register(XAJAX_FUNCTION,"comboAplicaLibros");
$xajax->register(XAJAX_FUNCTION,"comboRetencionISLR");
$xajax->register(XAJAX_FUNCTION,"comboTipoPago");
$xajax->register(XAJAX_FUNCTION,"contribuyente");
$xajax->register(XAJAX_FUNCTION,"eliminarTrabajoRequerido");
$xajax->register(XAJAX_FUNCTION,"eliminarTrabajoRequeridoForzado");
$xajax->register(XAJAX_FUNCTION,"guardarOrdenCompra");
$xajax->register(XAJAX_FUNCTION,"listadoDetalles");
$xajax->register(XAJAX_FUNCTION,"listadoProveedores");
$xajax->register(XAJAX_FUNCTION,"listadoVehiculos");
$xajax->register(XAJAX_FUNCTION,"listadoAccesorios");
$xajax->register(XAJAX_FUNCTION,"buscarAccesorio");
$xajax->register(XAJAX_FUNCTION,"asignarAccesorio");
$xajax->register(XAJAX_FUNCTION,"eliminarTrabajoExistente");

function dateadd($date, $mm=0, $dd=0, $yy=0){

	$date_r = getdate(strtotime($date)); 
	$date_result = date("d/m/Y", mktime(($date_r["hours"]+$hh),($date_r["minutes"]+$mn),($date_r["seconds"]+$ss),($date_r["mon"]+$mm),($date_r["mday"]+$dd),($date_r["year"]+$yy)));
	
	return $date_result;
}

function completar($longitud,$relleno,$cadena){
	
	if (count($cadena) < $longitud){
		for ($i = (count($cadena)+1); $i < $longitud;$i++){
			$cadena = $relleno.$cadena;
		}
	}
	
	return $cadena;
}

function validaTextArea($textoTexarea){ //ojo si mysql tiene setnames utf8 no hay que agregaar utf8_encode, alrevez si
	return addslashes(mysql_real_escape_string($textoTexarea));
}

function mostrarTextArea($textoTexarea){
	return utf8_encode(preg_replace('@[/\\\]@', '',preg_replace('/\s+/', ' ',$textoTexarea))); 
	// EL /\s+/ Quita todos los espacios en blanco "       " => " "
	// El @[/\\\]@ quita todos los slashes y los invertidos tambien
}


function esEditar(){//cuando edita el tot, carga de factura
    if($_GET['accion'] == "1" && $_GET['acc'] == "0" && $_GET['id'] != "0"){
        return true;
    }else{
        return false;
    }
}



?>