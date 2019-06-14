<?php

function validarTipoDocumento($tipoDocumento, $idDocumento, $idEmpresa, $accion, $valFormDcto)
{
	
		$objResponse = new xajaxResponse();
			
			if($tipoDocumento==1)
			{
				$objResponse->script("
						$('divFlotante2').style.display='none';
						$('divFlotante').style.display='none';
						$('btnGuardar').disabled = '';
						$('tdEtiqTipoDocumento').innerHTML = 'Neto Cotizacion:';
						$('lydTipoDocumento').innerHTML = 'Datos de la Cotizaci&oacute;n:';
						$('tdIdDocumento').innerHTML = 'Nro Cotizaci&oacute;n:';
						$('tdFechaVecDoc').style.display='';
						$('btnImprimirDoc').style.display = '';
						$('tdPresupuestosPendientes').style.display = 'none';");
						
						$objResponse->assign("tdNroDiasVencPresupuesto","innerHTML", diasVencimientoPresupuesto(valTpDato($idEmpresa, "int")));


			}
			else
			{	
				//dependiendo si se muestra o no el mecanico por parametros generales coloco el display		
				$objResponse->script("
						$('divFlotante2').style.display='none';
						$('divFlotante').style.display='none';
						$('btnGuardar').disabled = '';
						$('btnCancelar').disabled = '';
						$('tdEtiqTipoDocumento').innerHTML = 'Neto Orden:';
						$('lydTipoDocumento').innerHTML = 'Datos de la Orden:';
						$('tdIdDocumento').innerHTML = 'Id Orden:';
						$('tdFechaVecDoc').style.display='none';
						$('btnImprimirDoc').style.display = '';");
			}
			
			if($accion==1 || $accion==3)
			{
			
					if($accion==1)
					{
						$objResponse->script("xajax_nuevoDcto(); ");
						if($tipoDocumento==1)
							$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Nueva Cotizacion';
							$('btnGuardar').style.display = '';
							
							");
						if($tipoDocumento==2)
							$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Nueva Orden de Servicio';");
					}
					else 
					if($accion==3)
					{
						$objResponse->script("xajax_cargarDcto('".$idDocumento."', xajax.getFormValues('frmTotalPresupuesto'));	desbloquearForm();");
						if($tipoDocumento==1)
							$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Editar Cotizacion';");
						if($tipoDocumento==2)
							$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Editar Orden de Servicio';
								$('btnInsertarCliente').disabled = true;");
					}

				    $objResponse->script("
						$('tdInsElimPaq').style.display = ''; 
						$('tdInsElimRep').style.display = '';
						$('tdInsElimManoObra').style.display = ''; 
						$('tdInsElimNota').style.display = '';");	
			}
			else if($accion==2){				
					if($tipoDocumento==1)
						$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Visualizar Cotizaci&oacute;n';");
					if($tipoDocumento==2)
						$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Visualizar Orden de Servicio';");
					if($tipoDocumento==3)
						$objResponse->script("
							$('tdTituloPaginaServicios').innerHTML = 'Facturacion';
							$('tdCodigoBarraPresupuesto').style.display = 'none';
							$('btnImprimirDoc').style.display = 'none';
							$('tdNroControl').style.display = '';
							$('tdTxtNroControl').style.display = '';");
					if($tipoDocumento==4)
						$objResponse->script("
							$('tdTituloPaginaServicios').innerHTML = 'Presupuesto';
							$('tdCodigoBarraPresupuesto').style.display = 'none';
							$('btnImprimirDoc').style.display = 'none';
							$('tdNroControl').style.display = 'none';
							$('tdTxtNroControl').style.display = 'none';
							
							$('tdEtiqTipoDocumento').innerHTML = 'Neto Presupuesto:';
							");
											
					$objResponse->script("
						xajax_cargarDcto('".$idDocumento."',
						xajax.getFormValues('frmTotalPresupuesto'));
						bloquearForm();
						$('tdNotaAprob').style.display = ''; 
						$('tdRepAprob').style.display = '';
						$('tdPaqAprob').style.display = ''; 
						$('tdTempAprob').style.display = '';
						$('tdTotAprob').style.display = '';
						$('tdInsElimPaq').style.display = 'none'; 
						$('tdInsElimRep').style.display = 'none';
						$('tdInsElimManoObra').style.display = 'none'; 
						$('tdInsElimNota').style.display = 'none';
						$('tdInsElimTot').style.display = 'none';
						$('trCodigoBarraPresupuesto').style.display = '';
						$('btnInsertarPaq').style.display = 'none';
						$('btnEliminarPaq').style.display = 'none';
						$('btnInsertarArt').style.display = 'none';
						$('btnEliminarArt').style.display = 'none';
						$('btnInsertarTemp').style.display = 'none';
						$('btnEliminarTemp').style.display = 'none';
						$('btnInsertarNota').style.display = 'none';
						$('btnEliminarNota').style.display = 'none';
						$('btnInsertarTot').style.display = 'none';
						$('btnEliminarTot').style.display = 'none';");
						
					$objResponse->assign("tdCodigoBarraPresupuesto","innerHTML","<img border='0' src='clases/barcode128.php?codigo=".$idDocumento."&bw=2&bh=30&pc=1&type=B' />");
			}		
			else if($accion==4)
				{
					if($tipoDocumento==1)
						$objResponse->script("
							$('tdTituloPaginaServicios').innerHTML = 'Aprobar Presupuesto de Venta';
						");
						
					else
						$objResponse->script("
							$('tdTituloPaginaServicios').innerHTML = 'Aprobar Orden de Servicio';
							");
							
					$objResponse->script("
						xajax_cargarDcto('".$idDocumento."', 
						xajax.getFormValues('frmTotalPresupuesto'));
						desbloquearForm();
						
						$('btnInsertarPaq').style.display = 'none';
						$('btnEliminarPaq').style.display = 'none';
						$('btnInsertarArt').style.display = 'none';
						$('btnEliminarArt').style.display = 'none';
						$('btnInsertarTemp').style.display = 'none';
						$('btnEliminarTemp').style.display = 'none';
						$('btnInsertarNota').style.display = 'none';
						$('btnEliminarNota').style.display = 'none';
						$('btnInsertarTot').style.display = 'none';
						$('btnEliminarTot').style.display = 'none';
						
						$('tdInsElimPaq').style.display = 'none'; 
						$('tdInsElimRep').style.display = 'none';
						$('tdInsElimManoObra').style.display = 'none'; 
						$('tdInsElimNota').style.display = 'none';
						$('tdNotaAprob').style.display = ''; 
						$('tdRepAprob').style.display = '';
						$('tdPaqAprob').style.display = ''; 
						$('tdTempAprob').style.display = '';
						$('tdTotAprob').style.display = '';
						$('btnInsertarCliente').disabled = true;");
				}
				
		return $objResponse;
}
function nuevoDcto() {
	$objResponse = new xajaxResponse();
	
	$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = ".$_SESSION['idUsuarioSysGts']);
	$rsUsuario = mysql_query($queryUsuario) or die(mysql_error().$queryUsuario);
	$rowUsuario = mysql_fetch_assoc($rsUsuario);	 
	
	/*
	OJO CON ESTO
	
	$objResponse->script("
		selecAllChecks(true,'cbxItm',1);
		xajax_eliminarArticulo(xajax.getFormValues('frmListaArticulo'));
		selecAllChecks(false,'cbxItm',1);");*/
	
	$objResponse->script("
		xajax_cargaLstMoneda();
		document.forms['frmPresupuesto'].reset();
		document.forms['frmTotalPresupuesto'].reset();
		$('txtDireccionCliente').innerHTML = '';
		$('txtFechaVencimientoPresupuesto').readOnly = false;
		$('imgFechaVencimientoPresupuesto').style.visibility = '';
		$('txtNumeroPresupuestoPropio').readOnly = false;
		$('txtNumeroReferencia').readOnly = false;
		$('lstMoneda').disabled = false;
		$('btnInsertarPaq').disabled = false;
		$('btnEliminarPaq').disabled = false;
		$('btnInsertarEmp').disabled = false;
		$('btnInsertarCliente').disabled = false;
		$('btnInsertarUnidad').disabled = false;

		$('btnInsertarTemp').disabled = false;
		$('btnEliminarTemp').disabled = false;
		//$('btnPendiente').disabled = true;
		$('btnInsertarArt').disabled = false;
		$('btnEliminarTemp').disabled = false;
		$('btnInsertarTemp').disabled = false;
		$('btnInsertarNota').disabled = false;
		$('btnEliminarNota').disabled = false;
		$('btnInsertarTot').disabled = false;
		$('btnEliminarTot').disabled = false;
		$('btnImprimirDoc').disabled = true;
		$('btnEliminarArt').disabled = false;
		$('btnGuardar').disabled = '';
		$('btnCancelar').disabled = '';");
	
	
	
	$objResponse->assign("txtFechaPresupuesto","value",date("d-m-Y"));
	$objResponse->assign("hddIdEmpleado","value",$rowUsuario['id_empleado']);
	$objResponse->assign("txtNombreEmpleado","value",utf8_encode($rowUsuario['nombre_empleado']." ".$rowUsuario['apellido']));
		
	return $objResponse;
}


function cargarDcto($idDocumento, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	for ($contRep = 0; $contRep <= strlen($valFormTotalDcto['hddObj']); $contRep++) {
		$caracterRep = substr($valFormTotalDcto['hddObj'], $contRep, 1);
		
		if ($caracterRep != "|" && $caracterRep != "")
			$cadenaRep .= $caracterRep;
		else {
			$arrayObj[] = $cadenaRep;
			$cadenaRep = "";
		}	
	}
	
	for ($contPaq = 0; $contPaq <= strlen($valFormTotalDcto['hddObjPaquete']); $contPaq++) {
		$caracterPaq = substr($valFormTotalDcto['hddObjPaquete'], $contPaq, 1);
		
		if ($caracterPaq != "|" && $caracterPaq != "")
			$cadenaPaq .= $caracterPaq;
		else {
			$arrayObjPaq[] = $cadenaPaq;
			$cadenaPaq = "";
		}	
	}
	
	for ($contTot = 0; $contTot <= strlen($valFormTotalDcto['hddObjTot']); $contTot++) {
		$caracterTot = substr($valFormTotalDcto['hddObjTot'], $contTot, 1);
		
		if ($caracterTot != "|" && $caracterTot != "")
			$cadenaTot .= $caracterTot;
		else {
			$arrayObjTot[] = $cadenaTot;
			$cadenaTot = "";
		}	
	}
	
	for ($contNota = 0; $contNota <= strlen($valFormTotalDcto['hddObjNota']); $contNota++) {
		$caracterNota = substr($valFormTotalDcto['hddObjNota'], $contNota, 1);
		
		if ($caracterNota != "|" && $caracterNota != "")
			$cadenaNota .= $caracterNota;
		else {
			$arrayObjNota[] = $cadenaNota;
			$cadenaNota = "";
		}
	}

	for ($contTemp = 0; $contTemp <= strlen($valFormTotalDcto['hddObjTempario']); $contTemp++) {
		$caracterTemp = substr($valFormTotalDcto['hddObjTempario'], $contTemp, 1);
		
		if ($caracterTemp != "|" && $caracterTemp != "")
			$cadenaTemp .= $caracterTemp;//caracterTempario
		else {
			$arrayObjTemp[] = $cadenaTemp;
			$cadenaTemp = "";
		}
	}
	
	for ($contDcto = 0; $contDcto <= strlen($valFormTotalDcto['hddObjDescuento']); $contDcto++) {
		$caracterDcto = substr($valFormTotalDcto['hddObjDescuento'], $contDcto, 1);
		
		if ($caracterDcto != "|" && $caracterDcto != "")
			$cadenaDcto .= $caracterDcto;
		else {
			$arrayObjDcto[] = $cadenaDcto;
			$cadenaDcto = "";
		}
	}
		
	foreach($arrayObj as $indiceItmRep=>$valorItmRep) {
		$objResponse->script(sprintf("
			fila = document.getElementById('tdItmRep:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmRep));
	}
	
	foreach($arrayObjTot as $indiceItmTot=>$valorItmTot) {
		$objResponse->script(sprintf("
			fila = document.getElementById('tdItmTot:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmTot));
	}
	
	foreach($arrayObjPaq as $indiceItmPaq=>$valorItmPaq) {
		$objResponse->script(sprintf("
			fila = document.getElementById('tdItmPaq:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmPaq));
	}
	
	foreach($arrayObjNota as $indiceItmNota=>$valorItmNota) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmNota:%s');
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmNota));
	}
	
	foreach($arrayObjTemp as $indiceItmTemp=>$valorItmTemp) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmTemp:%s');	
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmNota));
	}
	
	foreach($arrayObjDcto as $indiceItmDcto=>$valorItmDcto) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmDcto:%s');	
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItmDcto));
	}

	$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = ".$_SESSION['idUsuarioSysGts']);
	$rsUsuario = mysql_query($queryUsuario) or die(mysql_error().$queryUsuario);
	$rowUsuario = mysql_fetch_assoc($rsUsuario);
	
	if($valFormTotalDcto['hddTipoDocumento']==1)//PRESUPUESTO
	{
		$query = sprintf("SELECT * FROM vw_sa_cotizacion WHERE vw_sa_cotizacion.id_presupuesto = %s", valTpDato($idDocumento,"int"));
		$rs = mysql_query($query) or die(mysql_error().$query);
		$row = mysql_fetch_assoc($rs);
				
		$numeroCotizacion = $row["numero_presupuesto"];
				
		$tablaEnc = "sa_presupuesto";
		$campoIdEnc = "id_presupuesto";
		$tablaDocDetArt = "sa_det_presup_articulo";
		$campoTablaIdDetArt = "id_det_presup_articulo";
		$tablaDocDetTemp = "sa_det_presup_tempario";
		$campoTablaIdDetTemp = "id_det_presup_tempario";
		
		$tablaDocDetTot = "sa_det_presup_tot";
		$campoTablaIdDetTot = "id_det_presup_tot";
		
		$tablaDocDetNota = "sa_det_presup_notas";
		$campoTablaIdDetNota = "id_det_presup_nota";
		
		$campoTablaIdDetNotaRelacOrden = "id_det_presup_nota AS id_det_orden_nota_ref";
		$campoTablaIdDetTotRelacOrden = "id_det_presup_tot AS id_det_orden_tot_ref";
		$campoTablaIdDetTempRelacOrden = "id_det_presup_tempario AS id_det_orden_tempario_ref";
		$campoTablaIdDetArtRelacOrden = "id_det_presup_articulo AS id_det_orden_articulo_ref";
		
		$fechaDocumento = $row['fecha_presup'];
		$descuento = $row['porcentaje_descuento'];
		$idTipoOrden = $row['id_tipo_orden'];
		$estado_orden = $row['nombre_estado'];
		
		$fecha_vencimiento = $row['fecha_vencimiento'];
		
		$id_iva = $row['idIva'];
		$iva = $row['iva'];
	}
	else
	{
		$query= sprintf("SELECT * FROM vw_sa_orden WHERE id_orden = %s", valTpDato($idDocumento,"int"));
		$rs= mysql_query($query) or die(mysql_error().$query);
		$row = mysql_fetch_assoc($rs);
		
		$tablaEnc = "sa_orden";
		$campoIdEnc = "id_orden";
		$tablaDocDetArt = "sa_det_orden_articulo";
		$campoTablaIdDetArt = "id_det_orden_articulo";
		$tablaDocDetTemp = "sa_det_orden_tempario";
		$campoTablaIdDetTemp = "id_det_orden_tempario";

		$tablaDocDetTot = "sa_det_orden_tot";
		$campoTablaIdDetTot = "id_det_orden_tot";
		
		$tablaDocDetNota = "sa_det_orden_notas";
		$campoTablaIdDetNota = "id_det_orden_nota";
		
		$campoTablaIdDetNotaRelacOrden = "id_det_orden_nota AS id_det_orden_nota_ref";
		$campoTablaIdDetTotRelacOrden = "id_det_orden_tot AS id_det_orden_tot_ref";
		$campoTablaIdDetTempRelacOrden = "id_det_orden_tempario AS id_det_orden_tempario_ref";
		$campoTablaIdDetArtRelacOrden = "id_det_orden_articulo AS id_det_orden_articulo_ref";

		$fechaDocumento = $row['fecha_orden'];
		$descuento = $row['porcentaje_descuento'];
		$idTipoOrden = $row['id_tipo_orden'];
		$estado_orden = $row['nombre_estado'];
		
		$id_iva = $row['idIva'];
		$iva = $row['iva'];

	}
		
		$itemsNoChecados = 0;
		
		$objResponse->script(sprintf("xajax_asignarCliente(%s)", $row['id_cliente']));
		$objResponse->script(sprintf("xajax_asignarUnidadBasica(%s, xajax.getFormValues('frmTotalPresupuesto'))", $row['id_unidad_basica']));
		
		if($valFormTotalDcto['hddAccionTipoDocumento'] == 2)
			$check_disabled = "disabled='disabled'";
		else
			$check_disabled = "";
		$queryRepuestosGenerales = sprintf("
			SELECT
			%s.%s,
			%s.cantidad,
			%s.precio_unitario,
			%s.costo,			
			%s.id_iva,
			%s.iva,
			%s.id_articulo,
			%s.%s,
			%s.aprobado,
			iv_tipos_articulos.descripcion AS descripcion_tipo,
			iv_articulos.descripcion AS descripcion_articulo,
			iv_secciones.descripcion AS descripcion_seccion,
			iv_subsecciones.id_subseccion,
			iv_articulos.codigo_articulo
			FROM
			iv_articulos
			INNER JOIN %s ON (iv_articulos.id_articulo = %s.id_articulo)
			INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
			INNER JOIN iv_tipos_articulos ON (iv_articulos.id_tipo_articulo = iv_tipos_articulos.id_tipo_articulo)
			INNER JOIN iv_secciones ON (iv_subsecciones.id_seccion = iv_secciones.id_seccion)
			WHERE %s.%s = %s AND %s.id_paquete IS NULL",
			$tablaDocDetArt, $campoTablaIdDetArtRelacOrden,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,			
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt, $campoTablaIdDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$campoIdEnc,
			valTpDato($idDocumento,"int"),
			$tablaDocDetArt);
			
			$rsDetRep = mysql_query($queryRepuestosGenerales) or die(mysql_error().$queryRepuestosGenerales." LINEA: ".__LINE__);
			$sigValor = 1;
			$arrayObj = NULL;
			
			$readonly_check_ppal_list_repuesto = 0;
			
			while ($rowDetRep = mysql_fetch_assoc($rsDetRep)) {
				$repuestosTomadosEnSolicitud2 = 0;
				
				$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				$caracterIva = ($rowDetRep['id_iva'] != "") ? $rowDetRep['iva']."%" : "NA";
				
				$query = sprintf("SELECT *
					FROM
					sa_det_orden_articulo
					INNER JOIN sa_det_solicitud_repuestos ON (sa_det_orden_articulo.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
					WHERE 
					sa_det_orden_articulo.id_det_orden_articulo = %s", valTpDato($rowDetRep['id_det_orden_articulo_ref'],"int"));
				
				$rs = mysql_query($query) or die(mysql_error().$query);
				$row = mysql_fetch_assoc($rs);
				
				if($row['id_det_solicitud_repuesto'] != '')
					$repuestosTomadosEnSolicitud2 = 1;
			
				if($rowDetRep['aprobado']==1)
				{
					//si hay por lo menos uno aprobado que desabilite directamente el check principal, ya q los valores tienden a reemplazarlos
					$checkedArt = "checked='checked'";
					$value_checkedArt = 1;
					$disabledArt = "disabled='disabled'";
					
					if ($valFormTotalDcto['hddAccionTipoDocumento']!=4)
					{
						$readonly_check_ppal_list_repuesto = 1;
						$displayArt = "style='display:none;'";
						$imgCheckDisabledArt = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
					}
					else
					{
						if($repuestosTomadosEnSolicitud2 == 1)
						{
							$readonly_check_ppal_list_repuesto = 1;
							$displayArt = "style='display:none;'";
							$imgCheckDisabledArt = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
						}
						else
						{
							$displayArt = "";
							$imgCheckDisabledArt = "";
						
						}
					}
				}
				else
				{
					$itemsNoChecados = 1;
					$checkedArt = " ";
					$value_checkedArt = 0;
					$disabledArt = "";
					if ($valFormTotalDcto['hddAccionTipoDocumento']!=4)
					{
						$displayArt = "style='display:none;'";
						$imgCheckDisabledArt = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
						$readonly_check_ppal_list_repuesto = 1;

					}
					else
					{
						$displayArt = "";
						$imgCheckDisabledArt = "";
					}
				}
							
				
								
				$objResponse->script(preg_replace('/\s+/', ' ', sprintf("
				var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px %s', 'title':'trItm:%s'}).adopt([
					new Element('td', {'align':'right', 'id':'tdItmRep:%s', 'class':'color_column_insertar_eliminar_item'
}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s' %s />\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDet%s' name='hddIdPedDet%s' value='%s'/><input type='hidden' id='hddIdArt%s' name='hddIdArt%s' value='%s'/>
					<input type='hidden' id='hddCantArt%s' name='hddCantArt%s' value='%s'/>
					<input type='hidden' id='hddIdPrecioArt%s' name='hddIdPrecioArt%s' value='%s'/>
					<input type='hidden' id='hddPrecioArt%s' name='hddPrecioArt%s' value='%s'/>
					<input type='hidden' id='hddCostoArt%s' name='hddCostoArt%s' value='%s'/>					
					<input type='hidden' id='hddIdIvaArt%s' name='hddIdIvaArt%s' value='%s'/>
					<input type='hidden' id='hddIvaArt%s' name='hddIvaArt%s' value='%s'/>
					<input type='hidden' id='hddTotalArt%s' name='hddTotalArt%s' value='%s'/>\"),
					new Element('td', {'align':'center', 'id':'tdItmRepAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"
					<input id='cbxItmAprob' name='cbxItmAprob[]' type='checkbox' value='%s' %s onclick='xajax_calcularTotalDcto();' %s  /> %s 
					<input type='hidden' id='hddValorCheckAprobRpto%s' name='hddValorCheckAprobRpto%s' value='%s'/>
					<input type='hidden' id='hddRptoEnSolicitud%s' name='hddRptoEnSolicitud%s' value='%s'/>
					<input type='hidden' id='hddRptoTomadoSolicitud2%s' name='hddRptoTomadoSolicitud2%s' value='%s'/>\")]);
				elemento.injectBefore('trItmPie');
				",
				$sigValor, $clase, $sigValor,
				$sigValor, $sigValor, $disabledArt,
				$rowDetRep['descripcion_seccion'],
				$rowDetRep['descripcion_tipo'],
				$rowDetRep['codigo_articulo'],
				$rowDetRep['descripcion_articulo'], 
				$rowDetRep['cantidad'],
				number_format($rowDetRep['precio_unitario'],2,".",","),
				$caracterIva,
				number_format(($rowDetRep['cantidad']*$rowDetRep['precio_unitario']),2,".",","),
				$sigValor, $sigValor, $rowDetRep['id_det_orden_articulo_ref'],
				$sigValor, $sigValor, $rowDetRep['id_articulo'],
				$sigValor, $sigValor, $rowDetRep['cantidad'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $rowDetRep['precio_unitario'],
				$sigValor, $sigValor, $rowDetRep['costo'],
				
				$sigValor, $sigValor, $rowDetRep['id_iva'],
				$sigValor, $sigValor, $rowDetRep['iva'],
				$sigValor, $sigValor, ($rowDetRep['cantidad']*$rowDetRep['precio_unitario']),
				$sigValor, $sigValor, $checkedArt, $displayArt,
				$imgCheckDisabledArt,
				$sigValor, $sigValor, $value_checkedArt,
				$sigValor, $sigValor, $row['id_det_solicitud_repuesto'],
				$sigValor, $sigValor, $repuestosTomadosEnSolicitud2)));
				
				if($valFormTotalDcto['hddAccionTipoDocumento']==1)
					$objResponse->script(sprintf("
						$('tdInsElimRep').style.display = '';
						$('tdItmRep:%s').style.display = '';
						$('tdItmRepAprob:%s').style.display='none';",
						$sigValor,
						$sigValor));	
				else if ($valFormTotalDcto['hddAccionTipoDocumento']==2 )
					$objResponse->script(sprintf("
						$('tdInsElimRep').style.display = 'none';
						$('tdItmRep:%s').style.display = 'none';
						$('tdItmRepAprob:%s').style.display='';
						$('cbxItmAprob').disabled = true;",
						$sigValor,
						$sigValor));	
				else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
					$objResponse->script(sprintf("
						$('tdInsElimRep').style.display = 'none'; 
						$('tdItmRep:%s').style.display = 'none'; 
						$('tdItmRepAprob:%s').style.display='';",
						$sigValor,
						$sigValor));
						else
						if ($valFormTotalDcto['hddAccionTipoDocumento']==3)
							$objResponse->script(sprintf("
							$('tdInsElimRep').style.display = '';
							$('tdItmRep:%s').style.display = '';
							$('tdItmRepAprob:%s').style.display='';",
							$sigValor,
							$sigValor));	
				$arrayObj[] = $sigValor;
				$sigValor++;
			}
			
			if($readonly_check_ppal_list_repuesto == 1)
			{
				$objResponse->script("
					$('cbxItmAprob').style.display = 'none';");
					$objResponse->assign("tdRepAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
					$objResponse->assign("tdInsElimRep","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
			}
						
			if($valFormTotalDcto['hddAccionTipoDocumento']==2 || $valFormTotalDcto['hddAccionTipoDocumento']==4)
			{
				if($sigValor==1)
				{
					$objResponse->script("
						$('frmListaArticulo').style.display='none';
						$('tblListaArticulo').style.display='none';");
				}
			}
			else
			{
				if($valFormTotalDcto['hddTipoDocumento']==1)//PRESUPUESTO
				{
					if($sigValor==1)
					{
						$objResponse->script("
							$('frmListaArticulo').style.display='none';
							$('tblListaArticulo').style.display='none';");
					}
				}
			}
                        
                        $adnlQuery = sprintf("SELECT 
				SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
			FROM sa_paquetes
				INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			WHERE %s.%s = %s
				AND %s.id_paquete = idPaq
				AND %s.estado_articulo <> 'DEVUELTO'", 
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetArt, $tablaDocDetArt, $tablaDocDetArt);
			
		$queryDetPaq = sprintf("SELECT 
			sa_paquetes.id_paquete AS idPaq,
			sa_paquetes.codigo_paquete,
			sa_paquetes.descripcion_paquete,
			
			(CASE (SELECT id_estado_orden FROM %s WHERE %s = %s LIMIT 1)
				WHEN 13 THEN
					(SELECT SUM(%s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
						INNER JOIN sa_det_solicitud_repuestos ON (%s.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND %s.id_iva IS NULL
						AND %s.estado_articulo <> 'DEVUELTO'
						AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
							OR sa_det_solicitud_repuestos.id_estado_solicitud = 5))
				ELSE
					(SELECT SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND %s.id_iva IS NULL
						AND %s.estado_articulo <> 'DEVUELTO')
			END) AS total_art_exento,
			
			(CASE (SELECT id_estado_orden FROM %s WHERE %s = %s LIMIT 1)
				WHEN 13 THEN
					(SELECT SUM(%s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
						INNER JOIN sa_det_solicitud_repuestos ON (%s.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND %s.id_iva IS NOT NULL
						AND %s.estado_articulo <> 'DEVUELTO'
						AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
							OR sa_det_solicitud_repuestos.id_estado_solicitud = 5))
				ELSE
					(SELECT SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND %s.id_iva IS NOT NULL
						AND %s.estado_articulo <> 'DEVUELTO')
			END) AS total_art_con_iva,
				
			(SELECT SUM(
				CASE %s.id_modo
					when '1' then %s.ut * %s.precio_tempario_tipo_orden/ %s.base_ut_precio 
					when '2' then %s.precio
					when '3' then %s.costo end) AS total
			FROM sa_paquetes
				INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			WHERE %s.%s = %s
				AND %s.id_paquete = idPaq
				AND %s.estado_tempario <> 'DEVUELTO') AS total_tmp,
			(%s) AS total_rpto,
			
			(IFNULL((SELECT SUM(
				CASE %s.id_modo
					when '1' then %s.ut * %s.precio_tempario_tipo_orden/ %s.base_ut_precio 
					when '2' then %s.precio
					when '3' then %s.costo
				END) AS total
			FROM sa_paquetes
				INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			WHERE %s.%s = %s
				AND %s.id_paquete = idPaq
				AND %s.estado_tempario <> 'DEVUELTO'),0)
			+
			IFNULL((%s),0)) AS precio_paquete 
		FROM sa_paquetes
			LEFT JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			LEFT JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			#LEFT JOIN %s ON (%s.%s = %s.%s) OR (%s.%s = %s.%s) #donde va OR antes iba AND, cambiado para que tome solo 1, cambiado 3inner por left ELIMINADO
		#WHERE %s.%s = %s #ELIMINADO
		WHERE sa_paquetes.id_paquete IN (
			SELECT id_paquete FROM %s WHERE %s.%s = %s AND %s.id_paquete IS NOT NULL
			UNION
			SELECT id_paquete FROM %s WHERE %s.%s = %s AND %s.id_paquete IS NOT NULL
			)
		GROUP BY sa_paquetes.id_paquete",
		//select id_estado_orden
		$tablaEnc, $campoIdEnc, valTpDato($idDocumento,"int"),
		
		
			$tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
		 		$tablaDocDetArt, 
				$tablaDocDetArt,
				$tablaDocDetArt,
				
			$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
		 		$tablaDocDetArt, 
				$tablaDocDetArt,
				$tablaDocDetArt,
		
		//select id_estado_orden
		$tablaEnc, $campoIdEnc, valTpDato($idDocumento,"int"),
		
				
			$tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
				$tablaDocDetArt,
				$tablaDocDetArt,
				$tablaDocDetArt,
			
			$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
				$tablaDocDetArt,
				$tablaDocDetArt,
				$tablaDocDetArt,
			
			
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp, $tablaDocDetTemp,
			$adnlQuery,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp,  $tablaDocDetTemp,
			$adnlQuery,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaEnc, $tablaDocDetTemp, $campoIdEnc, $tablaEnc, $campoIdEnc,
			$tablaEnc, $campoIdEnc, $tablaDocDetArt, $campoIdEnc,
			$tablaEnc, $campoIdEnc, valTpDato($idDocumento,"int"),
			//aqui nuevo gregor UNION 
			$tablaDocDetArt, $tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetArt,
			$tablaDocDetTemp, $tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp
			);
						
		$rsDetPaq = mysql_query($queryDetPaq) or die(mysql_error().$queryDetPaq);
		$sigValor = 1;
		$arrayObjPaq = NULL;
	
		$readonly_check_ppal_list_paquete = 0;
	
	while ($rowDetPaq = mysql_fetch_assoc($rsDetPaq)) {
	
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			
				$sqlManoObraPaq = sprintf("SELECT 
				%s.id_tempario,
				%s.aprobado
				FROM
				%s
				WHERE 
				%s.%s = %s AND %s.id_paquete = %s",
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp, valTpDato($rowDetPaq['idPaq'],"int"));
					
			$rsManoObraPaq = mysql_query($sqlManoObraPaq) or die(mysql_error().$sqlManoObraPaq);
			
			//VALIDACION DE APROBADO POR MANO DE OBRA. DE ESTO DEPENDE SI EL PAQUETE ES O NO APROBADO
			
				//SI LA ACCION ES APROBAR QUE NO LO MUESTRE ASI...
				$sqlNroManoObraPaq = sprintf("SELECT 
					count(*) AS numeroMo
					FROM
					%s
					WHERE 
					%s.%s = %s AND %s.id_paquete = %s AND %s.aprobado = 1 AND %s.estado_tempario <> 'DEVUELTO'",
					$tablaDocDetTemp,
					$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"), $tablaDocDetTemp, valTpDato($rowDetPaq['idPaq'],"int"), $tablaDocDetTemp, $tablaDocDetTemp);
									
				$rsNroManoObraPaq = mysql_query($sqlNroManoObraPaq) or die(mysql_error().$sqlNroManoObraPaq);
				$rowNroManoObraPaq = mysql_fetch_array($rsNroManoObraPaq);
					
				//CUANDO LO VAYA A GUARDAR NO LO PUEDE VOLVER A TOMAR PUESTO QUE YA ESTA GUARDADO...
				//ESTA MISMA CONSULTA COLOCARLA EN GUARDAR 
				
				$repuestosTomadosEnSolicitud = 0;
				
				$sqlRepuestoPaq = sprintf("SELECT 
					%s.id_articulo,
					%s.%s
					FROM
					%s
					WHERE 
					%s.%s = %s
					AND %s.id_paquete = %s",
					$tablaDocDetArt,
					$tablaDocDetArt, $campoTablaIdDetArtRelacOrden,
					$tablaDocDetArt, 
					$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento,"int"),
					$tablaDocDetArt, valTpDato($rowDetPaq['idPaq'],"int"));
									
					$rsRepuestoPaq = mysql_query($sqlRepuestoPaq) or die(mysql_error().$sqlRepuestoPaq);
							
					$cadenaRepPaq = "";
					while ($valorRepuestoPaq = mysql_fetch_array($rsRepuestoPaq))
					{
						$cadenaRepPaq .= "|".$valorRepuestoPaq['id_articulo'];
						$query = sprintf("SELECT  
							sa_det_solicitud_repuestos.id_det_solicitud_repuesto
							FROM
							sa_det_orden_articulo
							INNER JOIN sa_det_solicitud_repuestos ON (sa_det_orden_articulo.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
							WHERE 
							sa_det_orden_articulo.id_det_orden_articulo = %s", valTpDato($valorRepuestoPaq['id_det_orden_articulo_ref'],"int"));
						
						$rs = mysql_query($query)or die(mysql_error().$query);
						$row = mysql_fetch_assoc($rs);
		
						if($row['id_det_solicitud_repuesto'] != '')
							$repuestosTomadosEnSolicitud = 1;
					}
								
				if($rowNroManoObraPaq['numeroMo'] > 0)
				{
					$checkedPaq = "checked='checked'"; 
					$value_checkedPaq = 1;
					$disabledPaq = "disabled='disabled'";
					
					if ($valFormTotalDcto['hddAccionTipoDocumento']!=4)
					{
						$readonly_check_ppal_list_paquete = 1;
						$displayPaq = "style='display:none;'";
						$imgCheckDisabledPaq = sprintf("<input id='cbxItmAprobDisabled%s' name='cbxItmAprobDisabled%s' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor, $sigValor, $sigValor);
					}
					else
					{
						if($repuestosTomadosEnSolicitud == 1)
						{
							$readonly_check_ppal_list_paquete = 1;
							$displayPaq = "style='display:none;'";
							$imgCheckDisabledPaq = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
						}
						else
						{
							$displayPaq = "";
							$imgCheckDisabledPaq = "";
						
						}
					}
				}
				else
				{
					$itemsNoChecados = 1;
					$checkedPaq = " ";
					$value_checkedPaq = 0;
					$disabledPaq = "";
					if ($valFormTotalDcto['hddAccionTipoDocumento']!=4)
					{
						$displayPaq = "style='display:none;'";
						$imgCheckDisabledPaq = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
						$readonly_check_ppal_list_paquete = 1;
					}
					else
					{
						$displayPaq = "";
						$imgCheckDisabledPaq = "";
					}
				}
			
		$cadenaManoPaq = "";
		while ($valorManoObraPaq = mysql_fetch_array($rsManoObraPaq))
			$cadenaManoPaq .= "|".$valorManoObraPaq['id_tempario'];
		
			$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmPaq:%s', 'class':'textoGris_11px %s' , 'title':'trItmPaq:%s'}).adopt([
				new Element('td', {'align':'center', 'id':'tdItmPaq:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmPaq' name='cbxItmPaq[]' type='checkbox' value='%s' %s /> \"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\" %s <img id='img:%s' src='../img/iconos/ico_view.png' id='imgFechaVencimientoPresupuesto' name='imgFechaVencimientoPresupuesto' class='puntero noprint' title='Paquete:%s' />".
				"<input type='hidden' id='hddIdPedDetPaq%s' name='hddIdPedDetPaq%s' value='%s'/><input type='hidden' id='hddIdPaq%s' name='hddIdPaq%s' value='%s'/><input type='hidden' id='hddCodPaq%s' name='hddCodPaq%s' value='%s'/><input type='hidden' id='hddDesPaq%s' name='hddDesPaq%s' value='%s'/><input type='hidden' id='hddRepPaqAsig%s' name='hddRepPaqAsig%s' value='%s'/><input type='hidden' id='hddTempPaqAsig%s' name='hddTempPaqAsig%s' value='%s'/><input type='hidden' id='hddRepPaqAsigEdit%s' name='hddRepPaqAsigEdit%s' value='%s'/><input type='hidden' id='hddTempPaqAsigEdit%s' name='hddTempPaqAsigEdit%s' value='%s'/><input type='hidden' id='hddIvaPaq%s' name='hddIvaPaq%s' value='%s'/><input type='hidden' id='hddPrecPaq%s' name='hddPrecPaq%s' value='%s'/>\"),
				new Element('td', {'align':'center', 'id':'tdItmPaqAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmPaqAprob' name='cbxItmPaqAprob[]' type='checkbox' value='%s' %s %s onclick='xajax_calcularTotalDcto();' /> %s <input type='hidden' id='hddValorCheckAprobPaq%s' name='hddValorCheckAprobPaq%s' value='%s'/><input type='hidden' id='hddRptoPaqEnSolicitud%s' name='hddRptoPaqEnSolicitud%s' value='%s'/><input type='hidden' id='hddTotalRptoPqte%s' name='hddTotalRptoPqte%s' value='%s'/><input type='hidden' id='hddTotalTempPqte%s' name='hddTotalTempPqte%s' value='%s'/><input type='hidden' id='hddRptoTomadoSolicitud%s' name='hddRptoTomadoSolicitud%s' value='%s'/><input type='hidden' id='hddTotalExentoRptoPqte%s' name='hddTotalExentoRptoPqte%s' value='%s'/><input type='hidden' id='hddTotalConIvaRptoPqte%s' name='hddTotalConIvaRptoPqte%s' value='%s'/>\")
			]);	
			elemento.injectBefore('trm_pie_paquete');
			
			$('img:%s').onclick=function(){
				xajax_buscar_mano_obra_repuestos_por_paquete('%s','%s','%s','%s','%s','%s','%s','');
				$('tdListadoPaquetes').style.display='none';
				$('tblBusquedaPaquete').style.display='none';
				$('tdHrTblPaquetes').style.display='';
				$('tdListadoTemparioPorUnidad').style.display='none';
				$('tblListadoTempario').style.display='none'; 
				$('tblTemparios').style.display='none';
				$('tdBtnAccionesPaq').style.display='';
				$('tblNotas').style.display='none';
				$('tblListadoRepuestosPorPaquete').style.display='';
				$('tdListadoArticulos').style.display='none';
				$('tblArticulo').style.display='none';
				$('tblGeneralPaquetes').style.display='';
				$('tblListadoTempario').style.display='none';  
				$('tblListadoTemparioPorPaquete').style.display='';
				$('tblPaquetes2').style.display='';	
			}",
			$sigValor, $clase, $sigValor,
			$sigValor, $sigValor, $disabledPaq,
			utf8_encode($rowDetPaq['codigo_paquete']),
			utf8_encode($rowDetPaq['descripcion_paquete']),
			number_format($rowDetPaq['precio_paquete'],2,".",","),
			$estado_paquete, $sigValor, $rowDetPaq['idPaq'],
			$sigValor, $sigValor, $sigValor,
			$sigValor, $sigValor, $rowDetPaq['idPaq'],
			$sigValor, $sigValor, utf8_encode($rowDetPaq['codigo_paquete']),
			$sigValor, $sigValor, utf8_encode($rowDetPaq['descripcion_paquete']),
			$sigValor, $sigValor, $cadenaRepPaq,
			$sigValor, $sigValor, $cadenaManoPaq,
			$rowDetPaq['idPaq'], $rowDetPaq['idPaq'], $cadenaRepPaq,
			$rowDetPaq['idPaq'], $rowDetPaq['idPaq'], $cadenaManoPaq,
			$sigValor, $sigValor, "",
			$sigValor, $sigValor, $rowDetPaq['precio_paquete'],
			$sigValor, $sigValor, $checkedPaq, $displayPaq,
			$imgCheckDisabledPaq,
			$sigValor, $sigValor, $value_checkedPaq,
			$sigValor, $sigValor, $row['id_det_solicitud_repuesto'],
			$sigValor, $sigValor, $rowDetPaq['total_rpto'],
			$sigValor, $sigValor, $rowDetPaq['total_tmp'],
			$sigValor, $sigValor, $repuestosTomadosEnSolicitud,
			$sigValor, $sigValor, $rowDetPaq['total_art_exento'],
			$sigValor, $sigValor, $rowDetPaq['total_art_con_iva'],
			$sigValor,
			$rowDetPaq['idPaq'],utf8_encode($rowDetPaq['codigo_paquete']),utf8_encode($rowDetPaq['descripcion_paquete']),$rowDetPaq['precio_paquete'], 1, '0',''));
		
			/*if($imgCheckDisabled != " ")
				$objResponse->script(sprintf("$('imgCheckDisabled:%s').style.display='';",$sigValor));*/
			
			if($valFormTotalDcto['hddAccionTipoDocumento']==1)// || $valFormTotalDcto['hddAccionTipoDocumento']==3
				$objResponse->script(sprintf("$('tdInsElimPaq').style.display = ''; $('tdItmPaq:%s').style.display = ''; $('tdItmPaqAprob:%s').style.display='none'",$sigValor,$sigValor));	
		else if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
				$objResponse->script(sprintf("$('tdInsElimPaq').style.display = 'none'; $('tdItmPaq:%s').style.display = 'none'; $('tdItmPaqAprob:%s').style.display='';",$sigValor,$sigValor));	
		else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
				$objResponse->script(sprintf("$('tdInsElimPaq').style.display = 'none';  $('tdItmPaq:%s').style.display = 'none';   $('tdItmPaqAprob:%s').style.display=''",$sigValor,$sigValor));	
				
	else
		if ($valFormTotalDcto['hddAccionTipoDocumento']==3)
			$objResponse->script(sprintf("
			$('tdInsElimPaq').style.display = '';
			$('tdItmPaq:%s').style.display = '';
			$('tdItmPaqAprob:%s').style.display='';",
			$sigValor,
			$sigValor));	
				
		
			$arrayObjPaq[] = $sigValor;
			$sigValor++;
	}
	
	if($readonly_check_ppal_list_paquete == 1)
	{
		$objResponse->script("
			$('cbxItmPaqAprob').style.display = 'none';");
			$objResponse->assign("tdPaqAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			$objResponse->assign("tdInsElimPaq","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
			
	}
							
	if($valFormTotalDcto['hddAccionTipoDocumento']==2 || $valFormTotalDcto['hddAccionTipoDocumento']==4)
	{
		if($sigValor==1)
		{
			$objResponse->script("
			$('frm_agregar_paq').style.display='none';");
		}
	}
	else
	{
		if($valFormTotalDcto['hddTipoDocumento']==1)//PRESUPUESTO
		{
			if($sigValor==1)
			{
				$objResponse->script("
					$('frmListaArticulo').style.display='none';
					$('tblListaArticulo').style.display='none';");
			}
		}
	}

		/*$objResponse -> alert($queryDetPaq);
		return $objResponse;*/
			
		$queryDetalleTot = sprintf("SELECT *,
			%s.%s
			FROM
			sa_orden_tot
			INNER JOIN %s ON (sa_orden_tot.id_orden_tot = %s.id_orden_tot)
			INNER JOIN cp_proveedor ON (sa_orden_tot.id_proveedor = cp_proveedor.id_proveedor)
			WHERE %s.%s = %s",
			$tablaDocDetTot, $campoTablaIdDetTotRelacOrden,
			$tablaDocDetTot, $tablaDocDetTot,
			$tablaDocDetTot, $campoIdEnc, valTpDato($idDocumento,"int"));
			
		$rsDetalleTot = mysql_query($queryDetalleTot) or die(mysql_error().$queryDetalleTot);
		$sigValor = 1;
		$arrayObjTot = NULL;
		
  		while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
		
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
			if($rowDetalleTot['aprobado'] == 1)
			{
				$checkedTot = "checked='checked'";
				$value_checkedTot = 1;
				$disabledTot = "disabled='disabled'";
			if ($valFormTotalDcto['hddAccionTipoDocumento']!=4)
				{
					$readonly_check_ppal_list_tot = 1;
					$displayTot = "style='display:none;'";
					$imgCheckDisabledTot = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
				}
				else
				{
					$displayTot = "";
					$imgCheckDisabledTot = "";
				}
			}
			else
			{
				$itemsNoChecados = 1;
				$checkedTot = " ";
				$value_checkedTot = 0;
				$disabledTot = "";
				if ($valFormTotalDcto['hddAccionTipoDocumento']!=4)
				{
					$displayTot = "style='display:none;'";
					$imgCheckDisabledTot = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
				}
			}
			
			$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItmTot:%s', 'class':'textoGris_11px %s', 'title':'trItmTot:%s'}).adopt([
					new Element('td', {'align':'center', 'id':'tdItmTot:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmTot' name='cbxItmTot[]' type='checkbox' value='%s' onclick='xajax_calcularTotalDcto();' %s />\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDetTot%s' name='hddIdPedDetTot%s' value='%s'/><input type='hidden' id='hddIdTot%s' name='hddIdTot%s' value='%s'/><input type='hidden' id='hddIdPorcTot%s' name='hddIdPorcTot%s' value='%s'/><input type='hidden' id='hddPrecTot%s' name='hddPrecTot%s' value='%s'/><input type='hidden' id='hddPorcTot%s' name='hddPorcTot%s' value='%s'/><input type='hidden' id='hddMontoTotalTot%s' name='hddMontoTotalTot%s' value='%s'/>\"),
					new Element('td', {'align':'center', 'id':'tdItmTotAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmTotAprob' name='cbxItmTotAprob[]'  type='checkbox' value='%s' %s onclick='xajax_calcularTotalDcto();' %s />%s<input type='hidden' id='hddValorCheckAprobTot%s' name='hddValorCheckAprobTot%s' value='%s'/>\")]); 
				elemento.injectBefore('trm_pie_tot');
				",
				$sigValor, $clase, $sigValor,
				$sigValor,  $sigValor, $disabledTot,
				$rowDetalleTot['id_orden_tot'],
				$rowDetalleTot['nombre'],
				$rowDetalleTot['tipo_pago'],
				number_format($rowDetalleTot['monto_total'],2,".",","),
				number_format($rowDetalleTot['porcentaje_tot'],2,".",",")."%",
				number_format($rowDetalleTot['monto_total']+($rowDetalleTot['monto_total']*$rowDetalleTot['porcentaje_tot']/100),2,".",","),
				$sigValor, $sigValor, $rowDetalleTot['id_det_orden_tot_ref'],//sprintf('%s', $campoTablaIdDetTot)
				$sigValor, $sigValor, $rowDetalleTot['id_orden_tot'],
				$sigValor, $sigValor, $rowDetalleTot['id_porcentaje_tot'],
				$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['monto_total']),
				$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['porcentaje_tot']),
				$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['monto_total']+($rowDetalleTot['monto_total']*$rowDetalleTot['porcentaje_tot']/100)),
				$sigValor, $sigValor, $checkedTot, $displayTot, $imgCheckDisabledTot,
				$sigValor, $sigValor, $value_checkedTot));
				
			if($valFormTotalDcto['hddAccionTipoDocumento']==1)
				$objResponse->script(sprintf("$('tdInsElimTot').style.display = ''; $('tdItmTot:%s').style.display = ''; $('tdItmTotAprob:%s').style.display='none'",$sigValor,$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
				$objResponse->script(sprintf("$('tdInsElimTot').style.display = 'none'; $('tdItmTot:%s').style.display = 'none'; $('tdItmTotAprob:%s').style.display=''; $('cbxItmTotAprob').disabled = true;",$sigValor,$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
				$objResponse->script(sprintf("$('tdInsElimTot').style.display = 'none';  $('tdItmTot:%s').style.display = 'none';   $('tdItmTotAprob:%s').style.display=''",$sigValor,$sigValor));	
			else
				if ($valFormTotalDcto['hddAccionTipoDocumento']==3)
					$objResponse->script(sprintf("
					$('tdInsElimTot').style.display = '';
					$('tdItmTot:%s').style.display = '';
					$('tdItmTotAprob:%s').style.display='';",
					$sigValor,
					$sigValor));
							
			$arrayObjTot[] = $sigValor;
			$sigValor++;
		}
		if($valFormTotalDcto['hddAccionTipoDocumento']==2 || $valFormTotalDcto['hddAccionTipoDocumento']==4)
		{
			if($sigValor==1)
			{
				$objResponse->script("
				$('frmListaTot').style.display='none';");
			}
		}
		else
		{
			if($valFormTotalDcto['hddTipoDocumento']==1)//PRESUPUESTO
			{
				if($sigValor==1)
				{
					$objResponse->script("
						$('frmListaTot').style.display='none';");
				}
			}
		}
		
		if($readonly_check_ppal_list_tempario == 1)
		{
			$objResponse->script("
				$('cbxItmTotAprob').style.display = 'none';");
				$objResponse->assign("tdTotAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
				$objResponse->assign("tdInsElimTot","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");	
		}
	
		$queryDetTemp = sprintf("SELECT 
			%s.%s,
			sa_modo.descripcion_modo,
			sa_tempario.codigo_tempario,
			sa_tempario.descripcion_tempario,
			%s.operador,
			sa_operadores.descripcion_operador,
			%s.id_tempario,
			%s.precio,
			%s.base_ut_precio,
			%s.id_modo,
			(case %s.id_modo when '1' then %s.ut * %s.precio_tempario_tipo_orden/%s.base_ut_precio when '2' then %s.precio when '3' then %s.costo when '4' then '4' end) AS total_por_tipo_orden,
			(case %s.id_modo when '1' then %s.ut when '2' then %s.precio when '3' then %s.costo when '4' then '4' end) AS precio_por_tipo_orden,
			%s.%s,
			pg_empleado.nombre_empleado,
			pg_empleado.apellido,
			pg_empleado.id_empleado,
			sa_mecanicos.id_mecanico,
			sa_seccion.descripcion_seccion,
			sa_subseccion.id_seccion,
			sa_subseccion.descripcion_subseccion,
			sa_seccion.id_seccion,
			%s.aprobado,
			%s.origen_tempario,
			%s.origen_tempario + 0 AS idOrigen
			FROM
			%s
			INNER JOIN sa_tempario ON (%s.id_tempario = sa_tempario.id_tempario)
			INNER JOIN sa_operadores ON (%s.operador = sa_operadores.id_operador)
			INNER JOIN sa_modo ON (%s.id_modo = sa_modo.id_modo)
			LEFT JOIN sa_mecanicos ON (%s.id_mecanico = sa_mecanicos.id_mecanico)
			LEFT JOIN pg_empleado ON (sa_mecanicos.id_empleado = pg_empleado.id_empleado)
			INNER JOIN sa_subseccion ON (sa_tempario.id_subseccion = sa_subseccion.id_subseccion)
  			INNER JOIN sa_seccion ON (sa_subseccion.id_seccion = sa_seccion.id_seccion)
			WHERE
			%s.id_paquete IS NULL AND %s.%s = %s",
			$tablaDocDetTemp, $campoTablaIdDetTempRelacOrden,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoTablaIdDetTemp, 
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento,"int"));
						
	$rsDetTemp = mysql_query($queryDetTemp) or die(mysql_error().$queryDetTemp." Linea: ".__LINE__);
	$sigValor = 1;
	$arrayObjTemp = NULL; 
	
	while ($rowDetTemp = mysql_fetch_assoc($rsDetTemp)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
		if($rowDetTemp['aprobado']==1)
		{
			$checkedTemp = "checked='checked'";
			$value_checkedTemp = 1;
			$disabledTemp = "disabled='disabled'";
			if ($valFormTotalDcto['hddAccionTipoDocumento']!=4)
			{
				$readonly_check_ppal_list_tempario = 1;
				$displayTemp = "style='display:none;'";
				$imgCheckDisabledTemp = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
			}
			else
			{
				$displayTemp = "";
				$imgCheckDisabledTemp = "";							       
		    }
		}
		else
		{
			$itemsNoChecados = 1;
			$checkedTemp = " ";
			$value_checkedTemp = 0;
			$disabledTemp = "";
			if ($valFormTotalDcto['hddAccionTipoDocumento']!=4)			
			{
				$displayTemp = "style='display:none;'";
				$imgCheckDisabledTemp = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
				$readonly_check_ppal_list_tempario = 1;
			}
			/*else
			{
				$display = "";
				$imgCheckDisabled = "";
			
			}*/
		}
		
		$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmTemp:%s', 'class':'textoGris_11px %s', 'title':'trItmTemp:%s'}).adopt([
				new Element('td', {'align':'center', 'id':'tdItmTemp:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmTemp' name='cbxItmTemp[]' type='checkbox' value='%s' %s />\"),
				new Element('td', {'align':'center', 'id':'tdItmCodMecanico:%s'}).setHTML(\"%s\"),
				new Element('td', {'align':'center', 'id':'tdItmNomMecanico:%s'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s".
				"<input type='hidden' id='hddIdPedDetTemp%s' name='hddIdPedDetTemp%s' value='%s'/><input type='hidden' id='hddIdTemp%s' name='hddIdTemp%s' value='%s'/><input type='hidden' id='hddIdMec%s' name='hddIdMec%s' value='%s'/><input type='hidden' id='hddIvaTemp%s' name='hddIvaTemp%s' value='%s'/><input type='hidden' id='hddPrecTemp%s' name='hddPrecTemp%s' value='%s'/><input type='hidden' id='hddIdModo%s' name='hddIdModo%s' value='%s'/>\"),
				new Element('td', {'align':'center', 'id':'tdItmTempAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmTempAprob' name='cbxItmTempAprob[]' 'title':'cbxItmTempAprob:%s' type='checkbox' value='%s' %s %s onclick='xajax_calcularTotalDcto();' />%s<input type='hidden' id='hddValorCheckAprobTemp%s' name='hddValorCheckAprobTemp%s' value='%s'/><input type='hidden' id='hddIdOrigen%s' name='hddIdOrigen%s' value='%s'/>\")
			]);
			elemento.injectBefore('trm_pie_tempario');",
			$sigValor, $clase, $sigValor,
			$sigValor, $sigValor, $disabledTemp,
			$sigValor, $rowDetTemp['id_mecanico'],
			$sigValor, utf8_encode($rowDetTemp['nombre_empleado']." ".$rowDetTemp['apellido']),
			utf8_encode($rowDetTemp['descripcion_seccion']),
			utf8_encode($rowDetTemp['descripcion_subseccion']),
			$rowDetTemp['codigo_tempario'],
			utf8_encode($rowDetTemp['descripcion_tempario']),
			utf8_encode($rowDetTemp['descripcion_modo']),
			$rowDetTemp['descripcion_operador'],
			$rowDetTemp['precio_por_tipo_orden'],
			number_format($rowDetTemp['total_por_tipo_orden'],2,".",","),
			$rowDetTemp['origen_tempario'],
			$sigValor, $sigValor, $rowDetTemp['id_det_orden_tempario_ref'],//sprintf('%s', $campoTablaIdDetTemp), $campoTablaIdDetTempRelacOrden)
			$sigValor, $sigValor, $rowDetTemp['id_tempario'],
			$sigValor, $sigValor, $rowDetTemp['id_mecanico'],
			$sigValor, $sigValor, utf8_encode($rowDetTemp['nombre_empleado']." ".$rowDetTemp['apellido']),
			$sigValor, $sigValor, $rowDetTemp['total_por_tipo_orden'],
			$sigValor, $sigValor, utf8_encode($rowDetTemp['descripcion_modo']),
			$sigValor, $sigValor, $sigValor, $checkedTemp, $displayTemp, $imgCheckDisabledTemp,
			$sigValor, $sigValor, $value_checkedTemp,
			$sigValor, $sigValor, $rowDetTemp['idOrigen']));
			
    		//dependiendo si se muestra o no el mecanico por parametros generales coloco la validacion  $valFormTotalDcto['hddTipoDocumento']==2

			if($valFormTotalDcto['hddTipoDocumento']==1) {
				$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display='none'",$sigValor));
				$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display='none'",$sigValor));
			}
			else
			{
				if($valFormTotalDcto['hddMecanicoEnOrden'] == 1)
				{	
					$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display=''",$sigValor));
					$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display=''",$sigValor));
				}	
				else
				{
					$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display='none'",$sigValor));
					$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display='none'",$sigValor));
				}
			}
			if($valFormTotalDcto['hddAccionTipoDocumento']==1)
				$objResponse->script(sprintf("$('tdInsElimManoObra').style.display = ''; $('tdItmTemp:%s').style.display = ''; $('tdItmTempAprob:%s').style.display='none'",$sigValor,$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
				$objResponse->script(sprintf("$('tdInsElimManoObra').style.display = 'none'; $('tdItmTemp:%s').style.display = 'none'; $('tdItmTempAprob:%s').style.display=''; $('cbxItmTempAprob').disabled = true;",$sigValor,$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
				$objResponse->script(sprintf("$('tdInsElimManoObra').style.display = 'none';  $('tdItmTemp:%s').style.display = 'none';   $('tdItmTempAprob:%s').style.display=''",$sigValor,$sigValor));	
			else
				if ($valFormTotalDcto['hddAccionTipoDocumento']==3)
					$objResponse->script(sprintf("
					$('tdInsElimTot').style.display = '';
					$('tdItmTot:%s').style.display = '';
					$('tdItmTotAprob:%s').style.display='';",
					$sigValor,
					$sigValor));
		$arrayObjTemp[] = $sigValor;
		$sigValor++;
	}
	
	if($readonly_check_ppal_list_tempario == 1)
	{
		$objResponse->script("
			$('cbxItmTempAprob').style.display = 'none';");
			$objResponse->assign("tdTempAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			$objResponse->assign("tdInsElimManoObra","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
			
	}
	
	if($valFormTotalDcto['hddAccionTipoDocumento']==2 || $valFormTotalDcto['hddAccionTipoDocumento']==4)
	{
		if($sigValor==1)
		{
			$objResponse->script("
			$('frmListaManoObra').style.display='none';");
		}
	}
	else
	{
		if($valFormTotalDcto['hddTipoDocumento']==1)//PRESUPUESTO
		{
			if($sigValor==1)
			{
				$objResponse->script("
					$('frmListaManoObra').style.display='none';");
			}
		}
	}
	
	$queryDetTipoDocNotas = sprintf("
			SELECT
			%s.%s,
			%s AS idDetNota,
			%s AS idDoc,
			descripcion_nota,
			precio, 
			aprobado
			FROM %s 
			WHERE
			%s = %s",
			$tablaDocDetNota, $campoTablaIdDetNotaRelacOrden, 
			$campoTablaIdDetNota,
			$campoIdEnc,
			$tablaDocDetNota,
			$campoIdEnc, valTpDato($idDocumento,"int"));
						
		$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas) or die(mysql_error().$queryDetTipoDocNotas);
	
	$sigValor = 1;
	$arrayObjNota = NULL;
	while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
	
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		//$caracterIva = ($rowPresupuestoDet['id_iva'] != "" && $rowPresupuestoDet['id_iva'] != "0") ? $rowPresupuestoDet['iva']."%" : "NA";
		if($rowDetTipoDocNotas['aprobado']==1)
		{
			$checkedNota = "checked='checked'"; 
			$value_checkedNota = 1;
			$disabledNota = "disabled='disabled'";
			if ($valFormTotalDcto['hddAccionTipoDocumento']!=4)
			{
				$readonly_check_ppal_list_nota = 1;
				$displayNota = "style='display:none;'";
				$imgCheckDisabledNota = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
			}
			else
			{
				$displayNota = "";
				$imgCheckDisabledNota = "";
			}
		}
		else
		{
			$itemsNoChecados = 1;
			$checkedNota = " ";
			$value_checkedNota = 0;
			$disabledNota = "";
			/*$display = "style='display:none;'";
			$imgCheckDisabled = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";*/
			if ($valFormTotalDcto['hddAccionTipoDocumento']!=4)
			{
				$displayNota = "style='display:none;'";
				$imgCheckDisabledNota = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
				$readonly_check_ppal_list_nota = 1;
			}
			
			/*else
			{
				$display = "";
				$imgCheckDisabled = "";
				$display = "style='display:none;'";
					$imgCheckDisabled = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
			}*/
		}
			
		$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmNota:%s', 'class':'textoGris_11px %s', 'title':'trItmNota:%s'}).adopt([
				new Element('td', {'align':'center', 'id':'tdItmNota:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmNota' name='cbxItmNota[]' type='checkbox' value='%s' %s />\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s".
				"<input type='hidden' id='hddIdPedDetNota%s' name='hddIdPedDetNota%s' value='%s'/><input type='hidden' id='hddIdNota%s' name='hddIdNota%s' value='%s'/><input type='hidden' id='hddDesNota%s' name='hddDesNota%s' value='%s'/><input type='hidden' id='hddPrecNota%s' name='hddPrecNota%s' value='%s'/>\"),
				new Element('td', {'align':'center' , 'id':'tdItmNotaAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmNotaAprob' name='cbxItmNotaAprob[]' type='checkbox' value='%s' %s %s onclick='xajax_calcularTotalDcto();'/>%s<input type='hidden' id='hddValorCheckAprobNota%s' name='hddValorCheckAprobNota%s' value='%s' />\")
			]);
			elemento.injectBefore('trm_pie_nota');",
			$sigValor, $clase, $sigValor,
			$sigValor, $sigValor, $disabledNota,
			utf8_encode($rowDetTipoDocNotas['descripcion_nota']),
			number_format($rowDetTipoDocNotas['precio'],2,".",","),
			$sigValor, $sigValor, $rowDetTipoDocNotas['id_det_orden_nota_ref'],//'idDetNota'
			$sigValor, $sigValor, $sigValor,
			$sigValor, $sigValor, utf8_encode($rowDetTipoDocNotas['descripcion_nota']),
			$sigValor, $sigValor, $rowDetTipoDocNotas['precio'],
			$sigValor, $sigValor, $checkedNota, $displayNota, $imgCheckDisabledNota,
			$sigValor, $sigValor, $value_checkedNota));
			
			if($valFormTotalDcto['hddAccionTipoDocumento']==1)
				$objResponse->script(sprintf("$('tdInsElimNota').style.display = ''; $('tdItmNota:%s').style.display = ''; $('tdItmNotaAprob:%s').style.display='none'",$sigValor,$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
				$objResponse->script(sprintf("$('tdInsElimNota').style.display = 'none'; $('tdItmNota:%s').style.display = 'none'; $('tdItmNotaAprob:%s').style.display=''; $('cbxItmNotaAprob').disabled = true;",$sigValor,$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
				$objResponse->script(sprintf("$('tdInsElimNota').style.display = 'none';  $('tdItmNota:%s').style.display = 'none';   $('tdItmNotaAprob:%s').style.display=''",$sigValor,$sigValor));	
				
			$arrayObjNota[] = $sigValor;
			$sigValor++;
	}
	
	if($valFormTotalDcto['hddAccionTipoDocumento']==2 || $valFormTotalDcto['hddAccionTipoDocumento']==4)
	{
		if($sigValor==1)
		{
			$objResponse->script("
				$('frmListaNota').style.display='none';");
		}
	}
	else
	{
		if($valFormTotalDcto['hddTipoDocumento']==1)
		{
			if($sigValor==1)
			{
				$objResponse->script("
					$('frmListaNota').style.display='none';");
			}
		}
	}
	if($readonly_check_ppal_list_nota == 1)
	{
		$objResponse->script("
			$('cbxItmNotaAprob').style.display = 'none';");
			$objResponse->assign("tdNotaAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			$objResponse->assign("tdInsElimNota","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
	}
	
	$queryDetPorcentajeAdicional = sprintf("SELECT 
		sa_det_presup_descuento.id_presupuesto,
		sa_det_presup_descuento.id_porcentaje_descuento,
		sa_det_presup_descuento.porcentaje,
		sa_det_presup_descuento.id_det_presup_descuento,
		sa_porcentaje_descuento.descripcion
		FROM
		sa_porcentaje_descuento
		INNER JOIN sa_det_presup_descuento ON (sa_porcentaje_descuento.id_porcentaje_descuento = sa_det_presup_descuento.id_porcentaje_descuento)
		WHERE 
		sa_det_presup_descuento.id_presupuesto = %s",
		valTpDato($idDocumento,"int"));
		
	$rsDetPorcentajeAdicional = mysql_query($queryDetPorcentajeAdicional) or die(mysql_error().$queryDetPorcentajeAdicional);
	
	$sigValor = 1;
	$arrayObjDcto = NULL;
	
	while ($rowDetPorcentajeAdicional = mysql_fetch_assoc($rsDetPorcentajeAdicional)) {
			
		$objResponse -> script(sprintf("
			var elemento = new Element('tr', {'id':'trItmDcto:%s', 'class':'textoGris_11px', 'title':'trItmDcto:%s'}).adopt([
				new Element('td', {'align':'right', 'id':'tdItmDcto:%s', 'class':'tituloCampo'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"<input type='text' id='hddPorcDcto%s' name='hddPorcDcto%s' size='6' style='text-align:right' readonly='readonly' value='%s'/>%s\"),
				new Element('td', {'align':'right'}).setHTML(\"<img id='imgElimDcto:%s' name='imgElimDcto:%s' src='../img/iconos/ico_quitar.gif' class='puntero noprint' title='Porcentaje Adcl:%s' />".
				"<input type='hidden' id='hddIdDetDcto%s' name='hddIdDetDcto%s' value='%s'/><input type='hidden' id='hddIdDcto%s' name='hddIdDcto%s' value='%s'/>\"),
				new Element('td', {'align':'right' , 'id':'tdItmNotaAprob:%s'}).setHTML(\"<input type='text' id='txtTotalDctoAdcl%s' name='txtTotalDctoAdcl%s' readonly='readonly' style='text-align:right' size='18'/>\")]);
			elemento.injectBefore('trm_pie_dcto');
			
			$('imgElimDcto:%s').onclick=function(){
				xajax_eliminarDescuentoAdicional(%s);			
			}
			",
			$sigValor, $sigValor,
			$sigValor, $rowDetPorcentajeAdicional['descripcion'].":",
			"",
			$sigValor, $sigValor, $rowDetPorcentajeAdicional['porcentaje'], "%",
			$sigValor, $sigValor, $sigValor,
			$sigValor, $sigValor, $rowDetPorcentajeAdicional['id_det_presup_descuento'],
			$sigValor, $sigValor, $rowDetPorcentajeAdicional['id_porcentaje_descuento'],
			$sigValor, 
			$sigValor, $sigValor,
			$sigValor,
			$sigValor));
		
		if($valFormTotalDcto['hddAccionTipoDocumento'] == 1 || $valFormTotalDcto['hddAccionTipoDocumento'] == 3)
				$objResponse -> script(sprintf("$('imgElimDcto:%s').style.display = '';
				$('imgAgregarPorcAdnl').style.display = '';", $sigValor));	
		else if ($valFormTotalDcto['hddAccionTipoDocumento']==2 || $valFormTotalDcto['hddAccionTipoDocumento']==4)
			$objResponse->script(sprintf("$('imgElimDcto:%s').style.display = 'none'; $('imgAgregarPorcAdnl').style.display = 'none';", $sigValor));
	
		$arrayObjDcto[] = $sigValor;
		$sigValor++;
	}
	
	$cadenaRep = "";
	if(isset($arrayObj)){
		foreach($arrayObj as $indiceRep => $valorRep) {
			$cadenaRep .= "|".$valorRep;
		}
		$objResponse->assign("hddObj","value",$cadenaRep);
	}	
	
	$cadenaPaq = "";
	if(isset($arrayObjPaq)){
		foreach($arrayObjPaq as $indicePaq => $valorPaq) {
			$cadenaPaq .= "|".$valorPaq;
		}
		$objResponse->assign("hddObjPaquete","value",$cadenaPaq);
	}	
	
	$cadenaTot = "";
	if(isset($arrayObjTot)){
		foreach($arrayObjTot as $indiceTot => $valorTot) {
			$cadenaTot .= "|".$valorTot;
		}
		$objResponse->assign("hddObjTot","value",$cadenaTot);
	}	
	
	$cadenaTemp = "";
	if(isset($arrayObjTemp)){
		foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
			$cadenaTemp .= "|".$valorTemp;
		}
		$objResponse->assign("hddObjTempario","value",$cadenaTemp);
	}	
	
	$cadenaNota = "";
	if(isset($arrayObjNota)){
		foreach($arrayObjNota as $indiceNota => $valorNota) {
			$cadenaNota .= "|".$valorNota;
		}
		$objResponse->assign("hddObjNota","value",$cadenaNota);
	}
	
	$cadenaDcto = "";
	if(isset($arrayObjDcto)){
		foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
			$cadenaDcto .= "|".$valorDcto;
		}
		$objResponse->assign("hddObjDescuento", "value", $cadenaDcto);
	}
	
	$objResponse->assign("txtFechaPresupuesto","value", $fechaDocumento);
	$objResponse->assign("txtIdPresupuesto","value",utf8_encode($idDocumento));
	
	//numeracion cotizacion
	$objResponse->assign("numeroCotizacionMostrar","value",$numeroCotizacion);
	
	$objResponse->assign("txtDescuento","value", $descuento);
		
	$objResponse->assign("txtFechaVencimientoPresupuesto","value", $fecha_vencimiento); 
	
	$objResponse->assign("hddIdIvaVenta","value", $id_iva);
	$objResponse->assign("txtIvaVenta","value", $iva);

	$objResponse->assign("hddItemsNoAprobados","value", $itemsNoChecados);
	
	//OJO CON ESTO... PUEDO ESTAR HACIENDO ALGUNA ACTUALIZACION QUE SEA DE ORDEN Y LA HAGA DE PRESUPUESTO O VICEVERSA...
	$objResponse->assign("hddIdOrden","value",utf8_encode($idDocumento));
	
	//numeracion cotizacion en barra
		
	$objResponse->assign("tdCodigoBarraPresupuesto","innerHTML","<img border='0' src='clases/barcode128.php?codigo=".$numeroCotizacion."&bw=2&bh=30&pc=1&type=B' />");

		
	$objResponse->script(sprintf("
		$('fldPresupuesto').style.display='';
		$('lstTipoOrden').disabled = true;
		$('trCodigoBarraPresupuesto').style.display = '';
		xajax_cargaLstTipoOrden('%s');
		",$idTipoOrden));
	
	$objResponse->assign("hddIdEmpleado","value",$rowUsuario['id_empleado']);
		
	return $objResponse;
}

function guardarDcto($valFormDcto, $valFormPaq, $valFormListaArt, $valFormListTemp, $valFormListNota, $valFormListTot, $valFormTotalDcto) {

	$objResponse = new xajaxResponse();
        
        mysql_query("START TRANSACTION");
	
		/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
		for ($contPaq = 0; $contPaq <= strlen($valFormTotalDcto['hddObjPaquete']); $contPaq++) {
			$caracterPaq = substr($valFormTotalDcto['hddObjPaquete'], $contPaq, 1);
			
			if ($caracterPaq != "|" && $caracterPaq != "")
				$cadenaPaq .= $caracterPaq;
			else {
				$arrayObjPaq[] = $cadenaPaq;
				$cadenaPaq = "";
			}	
		}
		
		for ($contTemp = 0; $contTemp <= strlen($valFormTotalDcto['hddObjTempario']); $contTemp++) {
			$caracterTemp = substr($valFormTotalDcto['hddObjTempario'], $contTemp, 1);
			
			if ($caracterTemp != "|" && $caracterTemp != "")
				$cadenaTemp .= $caracterTemp;
			else {
				$arrayObjTemp[] = $cadenaTemp;
				$cadenaTemp = "";
			}	
		}
		
		for ($cont = 0; $cont <= strlen($valFormTotalDcto['hddObj']); $cont++) {
			$caracter = substr($valFormTotalDcto['hddObj'], $cont, 1);
			
			if ($caracter != "|" && $caracter != "")
				$cadena .= $caracter;
			else {
				$arrayObj[] = $cadena;
				$cadena = "";
			}	
		}
		
		for ($contNota = 0; $contNota <= strlen($valFormTotalDcto['hddObjNota']); $contNota++) {
			$caracterNota = substr($valFormTotalDcto['hddObjNota'], $contNota, 1);
			
			if ($caracterNota != "|" && $caracterNota != "")
				$cadenaNota .= $caracterNota;
			else {
				$arrayObjNota[] = $cadenaNota;
				$cadenaNota = "";
			}	
		}
	
		for ($contDcto = 0; $contDcto <= strlen($valFormTotalDcto['hddObjDescuento']); $contDcto++) {
			$caracterDcto = substr($valFormTotalDcto['hddObjDescuento'], $contDcto, 1);
			
			if ($caracterDcto != "|" && $caracterDcto != "")
				$cadenaDcto .= $caracterDcto;
			else {
				$arrayObjDcto[] = $cadenaDcto;
				$cadenaDcto = "";
			}	
		}
	
	
		if ($valFormDcto['txtIdPresupuesto']  > 0) {
                    
                } else {
			
			$fecha_vencimiento = dateadd($valFormDcto['txtFechaPresupuesto'], diasVencimientoPresupuesto(valTpDato($valFormDcto['txtIdEmpresa'], "int")),0,0);	
			
			if($valFormTotalDcto['hddTipoDocumento']==1)
			{
				if (!xvalidaAcceso($objResponse,PAGE_PRIV,insertar)){
					//$c->rollback();
					return $objResponse;
				}
							
				//numeracion cotizacion gregor
				/*
				$sqlNumeroCotizacion = sprintf("SELECT IFNULL(MAX(numero_presupuesto)+1, 1) as numero_presupuesto from sa_presupuesto 
										 WHERE id_empresa = %s AND tipo_presupuesto = 0 LIMIT 1", 
										 valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
				$rsSql = mysql_query($sqlNumeroCotizacion);
				if (!$rsSql) return $objResponse->alert(mysql_error()." Error generando numero de cotizacion \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
				$dtSql = mysql_fetch_assoc($rsSql);				
				$numeroCotizacion = $dtSql["numero_presupuesto"];*/
				
				$sqlNumeroCotizacion = sprintf("SELECT * FROM pg_empresa_numeracion
				WHERE id_numeracion = 35
					AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																					WHERE suc.id_empresa = %s)))
				ORDER BY aplica_sucursales DESC
				LIMIT 1",
				valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"),
				valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));
				$rsSql = mysql_query($sqlNumeroCotizacion);
				if (!$rsSql) return $objResponse->alert(mysql_error()."\n Error buscando numero de cotizacion \n\nLine: ".__LINE__."\n\n File: ".__FILE__);
				$dtSql = mysql_fetch_assoc($rsSql);
					
				$idEmpresaNumeroCotizacion = $dtSql["id_empresa_numeracion"];
				$numeroCotizacion = $dtSql["numero_actual"];
				
				if($numeroCotizacion == NULL) { return $objResponse->alert("No se pudo crear el numero de cotizacion, compruebe que la empresa tenga numeracion de presupuesto-cotizacion"); }
				
				//GUARDADO DE COTIZACION
			
				$insertSQL = sprintf("INSERT INTO sa_presupuesto (numero_presupuesto,fecha_presupuesto, id_cliente, id_tipo_orden, id_empresa, tiempo_orden, id_empleado, subtotal, porcentaje_descuento, subtotal_descuento, estado_presupuesto, tipo_presupuesto, id_unidad_basica, fecha_vencimiento, idIva, iva, base_imponible, subtotal_iva) VALUE (%s,%s, %s, %s, %s, NOW(), %s, %s, %s, %s, %s, %s, %s, str_to_date('%s', '%s'), %s, %s, %s, %s);",
				valTpDato($numeroCotizacion,"int"),
				valTpDato(date("Y-m-d",strtotime($valFormDcto['txtFechaPresupuesto'])), "date"),
				valTpDato($valFormDcto['txtIdCliente'], "int"),
				valTpDato($valFormDcto['lstTipoOrden'], "int"),
				valTpDato($valFormDcto['txtIdEmpresa'], "int"),
				valTpDato($valFormDcto['hddIdEmpleado'], "int"),
				valTpDato($valFormTotalDcto['txtSubTotal'], "real_inglesa"),
				valTpDato($valFormTotalDcto['txtDescuento'], "real_inglesa"),
				valTpDato($valFormTotalDcto['txtSubTotalDescuento'], "real_inglesa"),
				1,
				0,
				valTpDato($valFormDcto['hddIdUnidadBasica'], "int"),
				$fecha_vencimiento, '%d-%m-%Y',
				valTpDato($valFormTotalDcto['hddIdIvaVenta'], "int"),
				valTpDato($valFormTotalDcto['txtIvaVenta'], "real_inglesa"),
				valTpDato($valFormTotalDcto['txtBaseImponible'], "real_inglesa"),
				valTpDato($valFormTotalDcto['txtTotalIva'], "real_inglesa")); 
				
				//return $objResponse -> alert($valFormTotalDcto['txtSubTotal']);
				mysql_query("SET NAMES 'utf8';");
									
				$Result1 = mysql_query($insertSQL) or die(mysql_error().$insertSQL);
				$idDocumentoVenta = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				
				// ACTUALIZA numeracion cotizacion
				$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
				WHERE id_empresa_numeracion = %s;",
					valTpDato($idEmpresaNumeroCotizacion, "int"));
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				
				
			}
		}
	
			$queryTipoOrden = sprintf("SELECT 
				sa_tipo_orden.id_tipo_orden,
				sa_tipo_orden.precio_tempario
				FROM
				sa_tipo_orden
				WHERE
				sa_tipo_orden.id_tipo_orden = %s",
				valTpDato($valFormDcto['lstTipoOrden'], "int"));
			
			$rsTipoOrden = mysql_query($queryTipoOrden) or die(mysql_error().$queryTipoOrden);
			$rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);	
			
			if (isset($arrayObjTemp)) {
			
				foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
					if (strlen($valFormListTemp['hddIdTemp'.$valorTemp]) > 0) {
						if ($valFormListTemp['hddIdPedDetTemp'.$valorTemp] == "") {
							
							$insertSQL = sprintf("
								INSERT INTO sa_det_presup_tempario (id_presupuesto, id_tempario, precio, costo, id_modo,  base_ut_precio, operador, ut, tiempo_asignacion, id_mecanico, id_empleado_aprobacion, aprobado, precio_tempario_tipo_orden, origen_tempario)
								SELECT 
								".$idDocumentoVenta.",
								vw_sa_temparios_por_unidad.id_tempario,
								vw_sa_temparios_por_unidad.precio,
								vw_sa_temparios_por_unidad.costo,
								vw_sa_temparios_por_unidad.id_modo,
								vw_sa_temparios_por_unidad.base_ut,
								vw_sa_temparios_por_unidad.operador,
								vw_sa_temparios_por_unidad.ut,
								NOW(),
								%s,
								%s,
								%s,
								%s,
								%s
								FROM
								vw_sa_temparios_por_unidad
								WHERE vw_sa_temparios_por_unidad.id_tempario_det = %s",
								valTpDato($valFormListTemp['hddIdMec'.$valorTemp], "int"),
								valTpDato($valFormDcto['hddIdEmpleado'], "int"),
								valTpDato($valFormListTemp['hddValorCheckAprobTemp'.$valorTemp], "int"),
								$rowTipoOrden['precio_tempario'],
								valTpDato($valFormListTemp['hddIdOrigen'.$valorTemp], "int"),
								valTpDato($valFormListTemp['hddDetTemp'.$valorTemp], "int"));		
							
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL) or die(mysql_error().$insertSQL);
											
							$idDocumentoDetalle = mysql_insert_id();
							mysql_query("SET NAMES 'latin1';");
							$objResponse->assign("hddIdPedDetTemp".$valorTemp,"value",$idDocumentoDetalle);	
						}
					}
				}
			}
			
		
			
			if (isset($arrayObj)) {
	
				foreach($arrayObj as $indice => $valor) {
					
					if (strlen($valFormListaArt['hddIdArt'.$valor]) > 0) {
						if ($valFormListaArt['hddIdPedDet'.$valor] == "") {	
							
								$insertSQL = sprintf("INSERT INTO sa_det_presup_articulo (id_presupuesto, id_articulo, cantidad, precio_unitario, costo, id_iva, iva, tiempo_asignacion, id_empleado_aprobacion, aprobado) VALUE (%s, %s, %s, %s, %s, %s, %s, NOW(), %s, %s);",
									valTpDato($idDocumentoVenta, "int"),
									valTpDato($valFormListaArt['hddIdArt'.$valor], "int"),
									valTpDato($valFormListaArt['hddCantArt'.$valor], "int"),
									valTpDato($valFormListaArt['hddPrecioArt'.$valor], "real_inglesa"),
									valTpDato($valFormListaArt['hddCostoArt'.$valor], "real_inglesa"),									
									valTpDato($valFormListaArt['hddIdIvaArt'.$valor], "int"),
									valTpDato($valFormListaArt['hddIvaArt'.$valor], "real_inglesa"),
									valTpDato($valFormDcto['hddIdEmpleado'], "int"),
									valTpDato($valFormListaArt['hddValorCheckAprobRpto'.$valor], "int"));
									
								mysql_query("SET NAMES 'utf8';");
															
								$Result1 = mysql_query($insertSQL) or die(mysql_error().$insertSQL."linea: ".__LINE__);
								$idDocumentoDetalle = mysql_insert_id();
								mysql_query("SET NAMES 'latin1';");
								
								
								
								$objResponse->assign("hddIdPedDet".$valor,"value",$idDocumentoDetalle);
							}
								
						}
					}
				}
		
			
			if (isset($arrayObjNota)) {
				foreach($arrayObjNota as $indiceNota => $valorNota) {
					if (strlen($valFormListNota['hddIdNota'.$valorNota]) > 0) {
						if ($valFormListNota['hddIdPedDetNota'.$valorNota] == "") {
						
							$insertSQL = sprintf("INSERT INTO sa_det_presup_notas (id_presupuesto, descripcion_nota, precio, aprobado) VALUE (%s, %s, %s, %s);",
								valTpDato($idDocumentoVenta, "int"),
								valTpDato($valFormListNota['hddDesNota'.$valorNota], "text"),
								valTpDato($valFormListNota['hddPrecNota'.$valorNota], "real_inglesa"),
								valTpDato($valFormListNota['hddValorCheckAprobNota'.$valorNota], "int"));
							
							mysql_query("SET NAMES 'utf8';");						
							$Result1 = mysql_query($insertSQL) or die(mysql_error().$insertSQL);
							$idDocumentoDetalleNota = mysql_insert_id();
							mysql_query("SET NAMES 'latin1';");
							
							$objResponse->assign("hddIdPedDetNota".$valorNota,"value",$idDocumentoDetalleNota);
						}
					}
				}
			}
			
			
			if (isset($arrayObjDcto)) {
				foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
				
					if (strlen($valFormTotalDcto['hddIdDcto'.$valorDcto]) > 0) {
						if ($valFormTotalDcto['hddIdDetDcto'.$valorDcto] == "") {
						
							$insertSQL = sprintf("INSERT INTO sa_det_presup_descuento (id_presupuesto, id_porcentaje_descuento, porcentaje) VALUE (%s, %s, %s);",
								valTpDato($idDocumentoVenta, "int"),
								valTpDato($valFormTotalDcto['hddIdDcto'.$valorDcto], "int"),
								valTpDato($valFormTotalDcto['hddPorcDcto'.$valorDcto], "real_inglesa"));
								
							mysql_query("SET NAMES 'utf8';");							
							$Result1 = mysql_query($insertSQL) or die(mysql_error().$insertSQL);
							$idDocumentoDetalleDcto = mysql_insert_id();
							mysql_query("SET NAMES 'latin1';");
							
							$objResponse->assign("hddIdDetDcto".$valorDcto,"value",$idDocumentoDetalleDcto);
						}
					}
				}
			}
		
		
					
		$queryTipoOrden = sprintf("SELECT 
			sa_tipo_orden.id_tipo_orden,
			sa_tipo_orden.precio_tempario
			FROM
			sa_tipo_orden
			WHERE
			sa_tipo_orden.id_tipo_orden = %s",
		valTpDato($valFormDcto['lstTipoOrden'], "int"));
		 
		 $rsTipoOrden = mysql_query($queryTipoOrden) or die(mysql_error().$queryTipoOrden);
		 $rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
				 
		 $queryBaseUt = sprintf("SELECT 
			pg_parametros_empresas.valor_parametro
			FROM
			pg_parametros_empresas
			WHERE
			pg_parametros_empresas.descripcion_parametro = 1 AND 
			pg_parametros_empresas.id_empresa = %s",
			valTpDato($valFormDcto['txtIdEmpresa'], "int"));
			
		 $rsBaseUt = mysql_query($queryBaseUt) or die(mysql_error().$queryBaseUt);
		 $rowBaseUt = mysql_fetch_assoc($rsBaseUt);
		 
			if (isset($arrayObjPaq)) { 
				foreach($arrayObjPaq as $indicePaq => $valorPaq) {
					$contRepPaq = 0;
					if (strlen($valFormPaq['hddIdPaq'.$valorPaq]) > 0) {
							
						if ($valFormPaq['hddIdPedDetPaq'.$valorPaq] == "") {
								
								$arrayManoObraAprobada = $valFormPaq['hddTempPaqAsig'.$valorPaq];
								$arrayManoObraAprobada[0] = ' ';
								$arrayManoObraAprobada = str_replace("|",",",$arrayManoObraAprobada);	
								
								$arrayRepuestoAprobado = $valFormPaq['hddRepPaqAsig'.$valorPaq];
								$arrayRepuestoAprobado[0] = ' ';
								$arrayRepuestoAprobado = str_replace("|",",",$arrayRepuestoAprobado);	
                                                                
                                                                //Si esta en blanco los array no se convierten a string porque no hay conversion en el reemplazo, si no hay que reemplazo
                                                                if(is_array($arrayManoObraAprobada)){//valido que se le envie texto al query
                                                                        $arrayManoObraAprobada = "0";//con cero para que busque vacio y funcione el query
                                                                }

                                                                if(is_array($arrayRepuestoAprobado)){//valido que se le envie texto al query
                                                                        $arrayRepuestoAprobado = "0";//con cero para que busque vacio y funcione el query
                                                                }
																
									$query = sprintf("SELECT *,
										sa_tempario.operador AS id_operador
										FROM
										sa_paquetes
										INNER JOIN sa_paq_unidad ON (sa_paquetes.id_paquete = sa_paq_unidad.id_paquete)
										INNER JOIN sa_paq_tempario ON (sa_paquetes.id_paquete = sa_paq_tempario.id_paquete)
										INNER JOIN sa_tempario ON (sa_paq_tempario.id_tempario = sa_tempario.id_tempario)
										INNER JOIN sa_modo ON (sa_tempario.id_modo = sa_modo.id_modo)
										WHERE 
										sa_paquetes.id_empresa = %s AND sa_paquetes.id_paquete = %s AND sa_paq_unidad.id_unidad_basica = %s AND sa_tempario.id_tempario IN (%s)
									", 
									valTpDato($valFormDcto['txtIdEmpresa'], "int"),
									valTpDato($valFormPaq['hddIdPaq'.$valorPaq], "int"),
									valTpDato($valFormDcto['hddIdUnidadBasica'], "int"),
									$arrayManoObraAprobada);
								
									$rs = mysql_query($query) or die(mysql_error().$query);
								
									while($row = mysql_fetch_assoc($rs))
									{
										$query = sprintf("SELECT *
											FROM
											sa_tempario_det
											WHERE
											sa_tempario_det.id_tempario = %s AND
											sa_tempario_det.id_unidad_basica = %s", 
										valTpDato($row['id_tempario'],"int"),
										valTpDato($row['id_unidad_basica'],"int"));
																				
										$rsValor = mysql_query($query)or die(mysql_error().$query);
										$rowValor = mysql_fetch_assoc($rsValor);
										
										$insertSQL = sprintf("
										INSERT INTO sa_det_presup_tempario (
										id_presupuesto,
										id_paquete,
										id_tempario, 
										precio, 
										costo,
										id_modo,
										base_ut_precio,
										operador,
										ut, 
										tiempo_asignacion,
										id_mecanico, 
										id_empleado_aprobacion,
										aprobado,
										precio_tempario_tipo_orden,
										origen_tempario)
										VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NULL, %s, %s, %s, %s)", 
										$idDocumentoVenta,
										valTpDato($valFormPaq['hddIdPaq'.$valorPaq], "int"),
										$row['id_tempario'],
										$row['precio'],
										$row['costo'],
										$row['id_modo'],
										$rowBaseUt['valor_parametro'],
										$row['id_operador'],
										valTpDato($rowValor['ut'], "int"),
										valTpDato($valFormDcto['hddIdEmpleado'], "int"),
										$valFormPaq['hddValorCheckAprobPaq'.$valorPaq],
										$rowTipoOrden['precio_tempario'],
										1) ;//ORIGEN TEMPARIO
										
										mysql_query("SET NAMES 'utf8';");									
										$Result1 = mysql_query($insertSQL) or die(mysql_error().$insertSQL);
										$idDocumentoDetalleTemp = mysql_insert_id();
										mysql_query("SET NAMES 'latin1';");
									}
							
							$insertSQL = sprintf("
								INSERT INTO sa_det_presup_articulo (id_presupuesto, id_articulo, id_paquete, cantidad, precio_unitario, costo, id_iva, iva, tiempo_asignacion, id_empleado_aprobacion, aprobado) 		
								SELECT 
								%s,
								iv_articulos.id_articulo,
								%s,
								sa_paquete_repuestos.cantidad,
								iv_articulos_precios.precio,
                                                                
                                                                (SELECT
                                                                if((SELECT valor FROM pg_configuracion_empresa config_emp
                                                                INNER JOIN pg_configuracion config ON config_emp.id_configuracion = config.id_configuracion
                                                                WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s) = 1, iv_articulos_costos.costo, iv_articulos_costos.costo_promedio) AS costo
                                                                FROM iv_articulos_costos 
                                                                WHERE iv_articulos_costos.id_articulo = iv_articulos.id_articulo ORDER BY id_articulo_costo DESC LIMIT 1) AS costo,

								#(case iv_articulos.posee_iva when '1' then (SELECT idIva FROM pg_iva WHERE estado = 1 AND tipo = 6) when '0' then '0' end) AS id_iva_repuesto,
								#(case iv_articulos.posee_iva when '1' then (SELECT iva FROM pg_iva WHERE estado = 1 AND tipo = 6) when '0' then '0' end) AS iva_repuesto,

                                                                (CASE (SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1)
                                                                        WHEN '1' THEN
                                                                                (SELECT idIva FROM pg_iva WHERE estado = 1 AND (tipo = 6) ORDER BY iva)
                                                                END) AS id_iva,

                                                                (CASE (SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1)
                                                                        WHEN '1' THEN
                                                                                (SELECT iva FROM pg_iva WHERE estado = 1 AND (tipo = 6) ORDER BY iva)
                                                                END) AS iva,

								NOW(),
								%s,
								%s
								FROM
								sa_paquetes
								INNER JOIN sa_paquete_repuestos ON (sa_paquetes.id_paquete = sa_paquete_repuestos.id_paquete)
								INNER JOIN iv_articulos ON (sa_paquete_repuestos.id_articulo = iv_articulos.id_articulo)
								INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
								INNER JOIN iv_marcas ON (iv_articulos.id_marca = iv_marcas.id_marca)
								INNER JOIN iv_articulos_precios ON (iv_articulos.id_articulo = iv_articulos_precios.id_articulo)
								INNER JOIN sa_tipo_orden ON (iv_articulos_precios.id_precio = sa_tipo_orden.id_precio_repuesto)
								LEFT JOIN vw_iv_articulos_empresa ON (iv_articulos.id_articulo = vw_iv_articulos_empresa.id_articulo)
								WHERE sa_paquetes.id_empresa = %s AND sa_paquetes.id_paquete= %s AND sa_tipo_orden.id_tipo_orden= %s
								AND iv_articulos.id_articulo IN (%s)",
								$idDocumentoVenta,
								valTpDato($valFormPaq['hddIdPaq'.$valorPaq], "int"),
								valTpDato($valFormDcto['hddIdEmpleado'], "int"),
                                                                valTpDato($valFormDcto['txtIdEmpresa'], "int"), //costo costo promedio
								$valFormPaq['hddValorCheckAprobPaq'.$valorPaq],
								valTpDato($valFormDcto['txtIdEmpresa'], "int"),
								valTpDato($valFormPaq['hddIdPaq'.$valorPaq], "int"),
								valTpDato($valFormDcto['lstTipoOrden'],"int"),
								$arrayRepuestoAprobado);
							
							mysql_query("SET NAMES 'utf8';");
							$Result1 = mysql_query($insertSQL) or die(mysql_error().$insertSQL." Linea: ".__LINE__);
							$idDocumentoDetalleRep = mysql_insert_id();
							mysql_query("SET NAMES 'latin1';");
							
							$objResponse->assign("hddIdPedDetPaq".$valorPaq,"value",$idDocumentoDetalleTemp);// 28-08-2009 estaba $valor
							
						}
						
					}
				}
			}
			
		if($valFormTotalDcto['hddTipoDocumento']==1)
		{
			//MOSTRAR MENSAJE DEPENDIENDO DE LA ACCION
			$objResponse->assign("txtIdPresupuesto","value",$idDocumentoVenta);
			
			$objResponse->assign("txtFechaVencimientoPresupuesto","value", $fecha_vencimiento);
		
			$objResponse->assign("tdCodigoBarraPresupuesto","innerHTML","<img border='0' src='clases/barcode128.php?codigo=".$idDocumentoVenta."&bw=2&bh=30&pc=1&type=B' />");
			//$objResponse->assign("txtFechaVencimientoPresupuesto","value", $fechaDocumento);

			$objResponse->alert("Cotizacion Guardado con Exito");
			$objResponse -> script(sprintf("window.location.href = 'sa_formato_orden.php?id=%s&doc_type=1&acc=1'", $idDocumentoVenta));

		}
			
		$objResponse->script("
				bloquearForm();
				$('txtDescuento').readOnly = true;
				$('btnImprimirDoc').disabled = false;
				$('btnImprimirDoc').disabled = false; 
				$('fldPresupuesto').style.display='';
				$('trCodigoBarraPresupuesto').style.display = '';");
                
         mysql_query("COMMIT");
		
	return $objResponse;
}

function calcularDcto($valFormDcto, $valForm, $valFormTotalDcto,$valFormPaq,$valFormTemp,$valFormNotas, $valFormTot){
	$objResponse = new xajaxResponse();
	
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	for ($cont = 0; $cont <= strlen($valFormTotalDcto['hddObj']); $cont++) {
		$caracter = substr($valFormTotalDcto['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	for ($contPaq = 0; $contPaq <= strlen($valFormTotalDcto['hddObjPaquete']); $contPaq++) {
		$caracterPaq = substr($valFormTotalDcto['hddObjPaquete'], $contPaq, 1);
		
		if ($caracterPaq != "|" && $caracterPaq != "")
			$cadenaPaq .= $caracterPaq;
		else {
			$arrayObjPaq[] = $cadenaPaq;
			$cadenaPaq = "";
		}	
	}
	
	for ($contTemp = 0; $contTemp <= strlen($valFormTotalDcto['hddObjTempario']); $contTemp++) {
		$caracterTemp = substr($valFormTotalDcto['hddObjTempario'], $contTemp, 1);
		
		if ($caracterTemp != "|" && $caracterTemp != "")
			$cadenaTemp .= $caracterTemp;
		else {
			$arrayObjTemp[] = $cadenaTemp;
			$cadenaTemp = "";
		}	
	}
	
	for ($contTot = 0; $contTot <= strlen($valFormTotalDcto['hddObjTot']); $contTot++) {
		$caracterTot = substr($valFormTotalDcto['hddObjTot'], $contTot, 1);
		
		if ($caracterTot != "|" && $caracterTot != "")
			$cadenaTot .= $caracterTot;
		else {
			$arrayObjTot[] = $cadenaTot;
			$cadenaTot = "";
		}	
	}
	
	for ($contNota = 0; $contNota <= strlen($valFormTotalDcto['hddObjNota']); $contNota++) {
		$caracterNota = substr($valFormTotalDcto['hddObjNota'], $contNota, 1);
		
		if ($caracterNota != "|" && $caracterNota != "")
			$cadenaNota .= $caracterNota;
		else {
			$arrayObjNota[] = $cadenaNota;
			$cadenaNota = "";
		}	
	}
	
	for ($contDcto = 0; $contDcto <= strlen($valFormTotalDcto['hddObjDescuento']); $contDcto++) {
		$caracterDcto = substr($valFormTotalDcto['hddObjDescuento'], $contDcto, 1);
		
		if ($caracterDcto != "|" && $caracterDcto != "")
			$cadenaDcto .= $caracterDcto;
		else {
			$arrayObjDcto[] = $cadenaDcto;
			$cadenaDcto = "";
		}	
	}

	$subTotal = 0;
	$totalExento = 0;
	$totalExonerado = 0;
	$arrayIva = NULL;
	$arrayDetalleIva = NULL;
	$montoExento = 0;
	$baseImponible = 0;
	$totalTempTotal = 0;
	$totalArtTotal = 0;
	$totalExentoRptoPqte = 0;
	
	if (isset($arrayObjPaq)) {
		foreach($arrayObjPaq as $indicePaq => $valorPaq) {
			$objResponse->assign(sprintf("hddValorCheckAprobPaq%s",$valorPaq),"value", 0);
			
			if (isset($valFormPaq['cbxItmPaqAprob'])) {
			
				foreach($valFormPaq['cbxItmPaqAprob'] as $indiceAprob => $valorAprob) {
					if($valorPaq == $valorAprob) {
							$objResponse->assign(sprintf("hddValorCheckAprobPaq%s",$valorPaq),"value",1);
							//$baseImponible = $baseImponible + $valFormPaq['hddPrecPaq'.$valorPaq];
							$subTotalPaq = $valFormPaq['hddPrecPaq'.$valorPaq];//$valForm['hddCantArt'.$valor]*
							$descuentoPaq = ($valFormTotalDcto['txtDescuento']*$subTotalPaq)/100;
							$subTotalPaq = $subTotalPaq - $descuentoPaq;
							
							//$totalExento += $subTotalPaq;
							$subTotal += doubleval($valFormPaq['hddPrecPaq'.$valorPaq]);
							
							$totalTempTotal += doubleval($valFormPaq['hddTotalTempPqte'.$valorPaq]);
							$totalArtTotal += doubleval($valFormPaq['hddTotalRptoPqte'.$valorPaq]);
							
							//$totalExento += doubleval($valFormPaq['hddTotalExentoRptoPqte'.$valorPaq]);
							
							
							$totalExento += doubleval($valFormPaq['hddTotalExentoRptoPqte'.$valorPaq]);
							
							$baseImponible += doubleval($valFormPaq['hddTotalConIvaRptoPqte'.$valorPaq]);
							$baseImponible += doubleval($valFormPaq['hddTotalTempPqte'.$valorPaq]);
							
							
						}	
					}
				}
			}
		}	
	
	if (isset($arrayObjTot)) {
		foreach($arrayObjTot as $indiceTot => $valorTot) {
			$objResponse->assign(sprintf("hddValorCheckAprobTot%s",$valorTot),"value", 0);
			if (isset($valFormTot['cbxItmTotAprob'])) {
				foreach($valFormTot['cbxItmTotAprob'] as $indiceAprob => $valorAprob) {
					if($valorTot == $valorAprob) {	
							$objResponse->assign(sprintf("hddValorCheckAprobTot%s",$valorTot),"value",1);
							$baseImponible = $baseImponible + $valFormTot['hddMontoTotalTot'.$valorTot];

							$subTotalPaq = $valFormTot['hddMontoTotalTot'.$valorTot];//$valForm['hddCantArt'.$valor]*
							$descuentoPaq = ($valFormTotalDcto['txtDescuento']*$subTotalPaq)/100;
							$subTotalPaq = $subTotalPaq - $descuentoPaq;
							
							//$totalExento += $subTotalPaq;
							$subTotal += doubleval($valFormTot['hddMontoTotalTot'.$valorTot]);

						}	
					}
				}
			}
		}		

	if (isset($arrayObjNota)) {
		foreach($arrayObjNota as $indiceNota => $valorNota) {
		
			//SE INICIALIZAN EN 0 Y DESPUES SE LES COLOCA EL CHECK
			$objResponse -> assign(sprintf("hddValorCheckAprobNota%s",$valorNota),"value", 0);
			if (isset($valFormNotas['cbxItmNotaAprob'])) {
				foreach($valFormNotas['cbxItmNotaAprob'] as $indiceAprob => $valorAprob) {
					if($valorNota == $valorAprob) {
						$objResponse -> assign(sprintf("hddValorCheckAprobNota%s",$valorNota),"value", 1);
						$baseImponible = $baseImponible + $valFormNotas['hddPrecNota'.$valorNota];

						$subTotalNota= ($valFormNotas['hddPrecNota'.$valorNota]);
						$descuentoNota = ($valFormTotalDcto['txtDescuento']*$subTotalNota)/100;
						$subTotalNota = $subTotalNota - $descuentoNota;
						//$totalExento += $subTotalNota;
						$subTotal += doubleval($valFormNotas['hddPrecNota'.$valorNota]);
					}
				}
			}
		}
	}	
	
	if (isset($arrayObjTemp)) {
		foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
			$objResponse->assign(sprintf("hddValorCheckAprobTemp%s",$valorTemp),"value", 0);

			if (isset($valFormTemp['cbxItmTempAprob'])) {
				foreach($valFormTemp['cbxItmTempAprob'] as $indiceAprob => $valorAprob) {
					if($valorTemp == $valorAprob) {
						$objResponse->assign(sprintf("hddValorCheckAprobTemp%s",$valorTemp),"value", 1);

							$baseImponible = $baseImponible + $valFormTemp['hddPrecTemp'.$valorTemp];

							$subTotalTemp = $valFormTemp['hddPrecTemp'.$valorTemp];
							$descuentoTemp = ($valFormTotalDcto['txtDescuento']*$subTotalTemp)/100;
							$subTotalTemp = $subTotalTemp - $descuentoTemp;
							
							//$totalExento += $subTotalTemp;
						$subTotal += doubleval($valFormTemp['hddPrecTemp'.$valorTemp]);
						
						$totalTempTotal += doubleval($valFormTemp['hddPrecTemp'.$valorTemp]);
					}
				}
			}
		}
	}	
		
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
		$objResponse->assign(sprintf("hddValorCheckAprobRpto%s", $valor),"value", 0);
		
		if (isset($valForm['cbxItmAprob'])) {

			foreach($valForm['cbxItmAprob'] as $indiceAprob => $valorAprob) {
				if($valor == $valorAprob) {
					$objResponse->assign(sprintf("hddValorCheckAprobRpto%s", $valor),"value", 1);
					
					
					$queryIva = sprintf("SELECT * FROM pg_iva WHERE tipo = '%s' and activo = '%s' and estado = '%s'" , 6,1,1);
					$rsIva = mysql_query($queryIva) or die(mysql_error().$queryIva);
					$rowIva = mysql_fetch_assoc($rsIva);
					
					
					if ($valForm['hddIdIvaArt'.$valor] == 0 && $valForm['hddIvaArt'.$valor] == "") {
						$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
						
						$totalExento += $subTotalArt;
						
						$montoExento = $montoExento + $valForm['hddPrecioArt'.$valor];
						$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
						$subTotalArt = $subTotalArt - $descuentoArt;
						
						
					} else {
						$ivaArt =($valFormDcto['txtIdPresupuesto'] != "") ? $valForm['hddIvaArt'.$valor] : $rowIva['iva'];
						
						$existIva = "NO";
						if (isset($arrayIva)) {
							foreach($arrayIva as $indiceIva => $valorIva) {
								if($arrayIva[$indiceIva][0] == $valForm['hddIdIvaArt'.$valor]) {
									$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
									$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
									$subTotalArt = $subTotalArt - $descuentoArt;
									$subTotalIvaArt = ($subTotalArt*$ivaArt)/100;
									
									$arrayIva[$indiceIva][1] += $subTotalArt;
									$arrayIva[$indiceIva][2] += $subTotalIvaArt;
									$existIva = "SI";
								}
							}
						}
						
						if ($rowIva['idIva'] != "" && $existIva == "NO" && ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]) > 0) {
							$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
							$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
							$subTotalArt = $subTotalArt - $descuentoArt;
							$subTotalIvaArt = ($subTotalArt*$ivaArt)/100;
							
							$arrayDetalleIva[0] = $valForm['hddIdIvaArt'.$valor];
							$arrayDetalleIva[1] = $subTotalArt;
							$arrayDetalleIva[2] = $subTotalIvaArt;
							$arrayDetalleIva[3] = $ivaArt;
							$arrayIva[] = $arrayDetalleIva;
						}
						$baseImponible = $baseImponible + ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
					}
					
					$subTotal += doubleval($valForm['hddTotalArt'.$valor]);
					
					$totalArtTotal += doubleval($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);

					}
				}
			}
		}
	}	
		
	if (isset($arrayObjDcto)) {
		foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
			
			if($valFormTotalDcto['hddIdDcto'.$valorDcto] != "")
			{
				if($valFormTotalDcto['hddIdDcto'.$valorDcto] == 1)//DCTO MANO OBRA
					$descuentoAdicional = ($valFormTotalDcto['hddPorcDcto'.$valorDcto] * $totalTempTotal)/100; 
				else 
					if($valFormTotalDcto['hddIdDcto'.$valorDcto] == 2)// DCTO REPUESTO
						$descuentoAdicional = ($valFormTotalDcto['hddPorcDcto'.$valorDcto] * $totalArtTotal)/100;
						
				$objResponse -> assign(sprintf("txtTotalDctoAdcl%s", $valorDcto),"value", number_format($descuentoAdicional,2,".",","));
			}
			$totalDescuentoAdicional += doubleval($descuentoAdicional);
		}
	}
		
	if($valFormTotalDcto['hddAccionTipoDocumento'] != 2)
	{
		$query = sprintf("SELECT 
			sa_tipo_orden.posee_iva
			FROM
			sa_tipo_orden
			WHERE
			sa_tipo_orden.id_tipo_orden = %s", $valFormDcto['lstTipoOrden']);
		
		$rs = mysql_query($query) or die(mysql_error().$query);
		$row = mysql_fetch_assoc($rs);
		
		if($row['posee_iva'] == 0)
		{
			$queryIva = "SELECT idIva, iva FROM pg_iva WHERE activo = 1 AND estado = 1 AND tipo = 6";
			$rsIva = mysql_query($queryIva) or die(mysql_error().$queryIva);
			$rowIva = mysql_fetch_assoc($rsIva);
			$id_iva_venta = $rowIva['idIva'];
			$iva_venta = $rowIva['iva'];
		}	
		else
		{
			$queryIva = "SELECT idIva, iva FROM pg_iva WHERE activo = 1 AND estado = 1 AND tipo = 6";
			$rsIva = mysql_query($queryIva) or die(mysql_error().$queryIva);
			$rowIva = mysql_fetch_assoc($rsIva);
			$id_iva_venta = $rowIva['idIva'];
			$iva_venta = $rowIva['iva'];
		}
	}	
	else
	{
		$id_iva_venta = $valFormTotalDcto['hddIdIvaVenta'];
		$iva_venta = $valFormTotalDcto['txtIvaVenta'];
	}
		
	$totalIva = $baseImponible * ($iva_venta/100);
	$subTotalDescuento = $subTotal * ($valFormTotalDcto['txtDescuento']/100);
	$totalPresupuesto = doubleval($subTotal) - doubleval($subTotalDescuento) - doubleval($totalDescuentoAdicional) + doubleval($gastosConIva) + doubleval($subTotalIva) +  doubleval($gastosSinIva) + doubleval($totalIva);
	
	$objResponse->assign("hddIdIvaVenta","value",$id_iva_venta);
	$objResponse->assign("txtSubTotal","value",number_format($subTotal,2,".",","));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($subTotalDescuento,2,".",","));
	$objResponse->assign("txtIvaVenta","value", $iva_venta);
	$objResponse->assign("txtTotalIva","value",number_format($totalIva,2,".",","));
	$objResponse->assign("txtTotalPresupuesto","value",number_format($totalPresupuesto,2,".",","));	
	$objResponse->assign('txtGastosConIva',"value",number_format($gastosConIva,2,".",","));
	
	
	
	
	$objResponse->assign('txtMontoExento',"value",number_format($totalExento,2,".",","));
	$objResponse->assign('txtBaseImponible',"value",number_format($baseImponible,2,".",","));

	$cadena = "";
	foreach($arrayObj as $indice => $valor) {
		if (isset($valForm['hddIdArt'.$valor]))
			$cadena .= "|".$valor;
	}
	$objResponse->assign("hddObj","value",$cadena);

	$cadenaPaq = "";
	foreach($arrayObjPaq as $indicePaq => $valorPaq) {
		if (isset($valFormPaq['hddIdPaq'.$valorPaq]))
			$cadenaPaq .= "|".$valorPaq;
	}
	$objResponse->assign("hddObjPaquete","value",$cadenaPaq);
	
	$cadenaTemp = "";
	foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
		if (isset($valFormTemp['hddIdTemp'.$valorTemp]))
			$cadenaTemp .= "|".$valorTemp;
	}
	$objResponse->assign("hddObjTempario","value",$cadenaTemp);
	
	$cadenaNota = "";
	foreach($arrayObjNota as $indiceNota => $valorNota) {
		if (isset($valFormNotas['hddIdNota'.$valorNota]))
			$cadenaNota .= "|".$valorNota;
	}
	$objResponse->assign("hddObjNota","value",$cadenaNota);
	
	$cadenaDcto = "";
	foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
		if (isset($valFormTotalDcto['hddIdDcto'.$valorDcto]))
			$cadenaDcto .= "|".$valorDcto;
	}
	$objResponse->assign("hddObjDescuento","value", $cadenaDcto);
	$objResponse -> script("xajax_contarItemsDcto(xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'));");

	
	return $objResponse;
}

function insertarArticulo($valForm, $valFormArtAgregados, $valFormTotalDcto) {
		$objResponse = new xajaxResponse();
	
		for ($cont = 0; $cont <= strlen($valFormTotalDcto['hddObj']); $cont++) {
			$caracter = substr($valFormTotalDcto['hddObj'], $cont, 1);
						
			if ($caracter != "|" && $caracter != "")
				$cadena .= $caracter;
			else {
				$arrayObj[] = $cadena;
				$cadena = "";
			}	
		}
		
		$sw = 0;
		foreach($arrayObj as $indice => $valor) {
			if ($valFormArtAgregados['hddIdArt'.$valor]!= ""){
				if($valFormArtAgregados['hddIdArt'.$valor] == $valForm['hddIdArt'])
					$sw = 1;
			}
		}
		
		if($sw == 1)
		{
			$objResponse ->alert("El Articulo que desea agregar ya se encuentra en la lista. Escoja otro.");
		}
		else
		{
			
			
			// BUSCA EL ULTIMO COSTO DEL ARTICULO
			$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos
			WHERE id_articulo = %s
			ORDER BY fecha_registro DESC
			LIMIT 1;",
				valTpDato($valForm['hddIdArt'], "int"));
			$rsCostoArt = mysql_query($queryCostoArt);
			if (!$rsCostoArt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowCostoArt = mysql_fetch_assoc($rsCostoArt);
			
			$costoArt = round($rowCostoArt['costo'],2);
			
			
			$sigValor = $arrayObj[count($arrayObj)-1] + 1;
			
			$queryPrecioArt = sprintf("SELECT * FROM vw_iv_articulos_precios WHERE id_articulo_precio = %s", valTpDato($valForm['hddIdPrecioRepuesto'],"int"));
			$rsPrecioArt = mysql_query($queryPrecioArt) or die(mysql_error().$queryPrecioArt);
			$rowPrecioArt = mysql_fetch_assoc($rsPrecioArt);
							
			$queryIva = sprintf("SELECT * FROM pg_iva WHERE idIva = %s", valTpDato($valForm['hddIdIvaRepuesto'], "int"));
			$rsIva = mysql_query($queryIva) or die(mysql_error().$queryIva);
			$rowIva = mysql_fetch_assoc($rsIva);
			
			$caracterIva = ($rowIva['idIva'] != "") ? $rowIva['iva']."%" : "NA";
			
			$objResponse->script(preg_replace('/\s+/', ' ', sprintf("
				var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px', 'title':'trItm:%s'}).adopt([
					new Element('td', {'align':'right', 'id':'tdItmRep:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s' />\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDet%s' name='hddIdPedDet%s' value='%s'/>
					<input type='hidden' id='hddIdArt%s' name='hddIdArt%s' value='%s'/>
					<input type='hidden' id='hddCantArt%s' name='hddCantArt%s' value='%s'/>
					<input type='hidden' id='hddIdPrecioArt%s' name='hddIdPrecioArt%s' value='%s'/>
					<input type='hidden' id='hddPrecioArt%s' name='hddPrecioArt%s' value='%s'/>
					<input type='hidden' id='hddCostoArt%s' name='hddCostoArt%s' value='%s'/>
					<input type='hidden' id='hddCostoPromedioArt%s' name='hddCostoPromedioArt%s' value='%s'/>
					<input type='hidden' id='hddIdIvaArt%s' name='hddIdIvaArt%s' value='%s'/>
					<input type='hidden' id='hddIvaArt%s' name='hddIvaArt%s' value='%s'/>
					<input type='hidden' id='hddTotalArt%s' name='hddTotalArt%s' value='%s'/>\"),
					new Element('td', {'align':'center', 'id':'tdItmRepAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"
					<input id='cbxItmAprob' name='cbxItmAprob[]' type='checkbox' value='%s' checked='checked' onclick='xajax_calcularTotalDcto();'  />
					<input type='hidden' id='hddValorCheckAprobRpto%s' name='hddValorCheckAprobRpto%s'/>
					<input type='hidden' id='hddRptoEnSolicitud%s' name='hddRptoEnSolicitud%s' value='%s'/>\")	
				]);
				elemento.injectBefore('trItmPie');
				",
				$sigValor, $sigValor,
				$sigValor, $sigValor,
				$valForm['txtSeccionArt'],
				$valForm['txtTipoPiezaArt'],
				$valForm['txtCodigoArt'],
				$valForm['txtDescripcionArt'],
				$valForm['txtCantidadArt'],
				$valForm['txtPrecioRepuesto'],
				$caracterIva,
				number_format(((($valForm['txtCantidadArt'] * str_replace(",","", $valForm['txtPrecioRepuesto']))* ($rowIva['iva']/100)) + ($valForm['txtCantidadArt'] * str_replace(",","", $valForm['txtPrecioRepuesto']))),2,".",","),
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $valForm['hddIdArt'],
				$sigValor, $sigValor, $valForm['txtCantidadArt'],
				$sigValor, $sigValor, $valForm['hddIdPrecioRepuesto'],
				$sigValor, $sigValor, str_replace(",","", $valForm['txtPrecioRepuesto']),
				$sigValor, $sigValor, $costoArt,
				$sigValor, $sigValor, $costoPromedioArt,
				$sigValor, $sigValor, $valForm['hddIdIvaRepuesto'],
				$sigValor, $sigValor, $rowIva['iva'],
				$sigValor, $sigValor, ($valForm['txtCantidadArt'] * str_replace(",","", $valForm['txtPrecioRepuesto'])),
				$sigValor, $sigValor,
				$sigValor, $sigValor,
				$sigValor, $sigValor, "")));
				
				if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
					$objResponse->script(sprintf("
						$('tdInsElimRep').style.display = 'none';
						$('tdItmRep:%s').style.display = 'none';
						$('tdItmRepAprob:%s').style.display='none';",
						$sigValor,
						$sigValor));	
				else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
					$objResponse->script(sprintf("
						$('tdInsElimRep').style.display = 'none'; 
						$('tdItmRep:%s').style.display = 'none'; 
						$('tdItmRepAprob:%s').style.display='';",
						$sigValor,
						$sigValor));
				else if($valFormTotalDcto['hddAccionTipoDocumento']==1)
					$objResponse->script(sprintf("
						$('tdInsElimRep').style.display = '';
						$('tdItmRep:%s').style.display = '';
						$('tdItmRepAprob:%s').style.display='none';",
						$sigValor,
						$sigValor));
			
			$arrayObj[] = $sigValor;
			foreach($arrayObj as $indice => $valor) {
				$cadena = $valFormTotalDcto['hddObj']."|".$valor;
			}
			$objResponse->assign("hddObj","value",$cadena);
			
			$objResponse->script("
				$('tblListados').style.display='none';
				$('tblArticulo').style.display='none';
				$('tblLogoGotoSystems').style.display='';
				
				$('divFlotante2').style.display='none';
				$('divFlotante').style.display='none';");
			
			$objResponse->script("xajax_calcularTotalDcto();");
		}
		
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
		}
	}
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'),xajax.getFormValues('frm_agregar_paq'),xajax.getFormValues('frmListaManoObra'),xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmListaTot'));");
		
	return $objResponse;
}

function eliminarPaquete($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxItmPaq'])) {
		foreach($valForm['cbxItmPaq'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trItmPaq:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	
$objResponse->script("xajax_calcularTotalDcto();");		
	return $objResponse;
}

function listadoEmpresas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Empresas");
	$objResponse->assign("tblListados","width","600px");
	
	$htmlTableIni .= "<table width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td></td>
            	<td width=\"15%\">".("C&oacute;digo")."</td>
                <td width=\"20%\">".utf8_encode("RIF")."</td>
                <td width=\"35%\">".utf8_encode("Empresa")."</td>
                <td width=\"30%\">".utf8_encode("Sucursal")."</td>
            </tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
	
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = $row['nombre_empresa_suc']." (".$row['sucursal'].")";
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$row['id_empresa_reg']."');\" title=\"Seleccionar Empresa\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td>".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($nombreSucursal)."</td>";
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s)\">",
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"10\" class=\"divMsjError\">No se encontraron registros.</td>";
	
	$objResponse->assign("tdListado","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("
		$('trBuscarPresupuesto').style.display='none';
		
		$('tblArticulo').style.display='none';
		$('tblListados').style.display='';
		$('tblLogoGotoSystems').style.display='none';
		$('tblPaquetes2').style.display='none';
		$('tblPaquetes').style.display='none';
		");
		
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
	");
	
	return $objResponse;
}

function listadoClientes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf(" CONCAT_WS('', cj_cc_cliente.nombre, cj_cc_cliente.apellido) LIKE %s OR cj_cc_cliente.ci LIKE %s)",
		    valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"));
	}
	
	$query = ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") ? "
		SELECT 
		*
		FROM
		cj_cc_cliente" : "SELECT 
		*
		FROM
		cj_cc_cliente";
	$query .= $sqlBusq;
				
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML", utf8_encode("Listado de Clientes"));
	$objResponse->assign("tblListados","width","990px");
	
	$htmlTableIni .= "<table width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "7%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Cliente"));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "15%", $pageNum, "ci", $campOrd, $tpOrd, $valBusq, $maxRows, "Cedula/R.I.F");	
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "47%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));	
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "16%", $pageNum, "ciudad", $campOrd, $tpOrd, $valBusq, $maxRows, "Ciudad");
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "15%", $pageNum, "reputacionCliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo");
		$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$estado = ($row['estado'] == 0) ? "<img src='../img/iconos/ico_verde.gif' width='12' height='12' />" : "<img src='../img/iconos/ico_rojo.gif' width='12' height='12' />";
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."');\" title=\"Seleccionar Cliente\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['lci'].$row['ci']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre']." ".$row['apellido'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['ciudad'])."</td>";
			$htmlTb .= "<td>".$row['reputacionCliente']."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\" >";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoClientes(%s,'%s','%s','%s',%s)\">",
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"12\" class=\"divMsjError\">No se encontraron registros.</td>";
	
	$objResponse->assign("tdListado","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("
		$('trBuscarPresupuesto').style.display='none';
		$('tblArticulo').style.display='none';
		$('tblListados').style.display='';
		$('tblBusquedaPaquete').style.display='none';
		$('btnAsignarPaquete').style.display='none';
		$('tdListadoPaquetes').style.display='none';
		$('tblListadoTempario').style.display='none';  
		$('tblTemparios').style.display='none';  
		$('tdHrTblPaquetes').style.display='none';
		$('tblGeneralPaquetes').style.display='none';
		$('tblNotas').style.display='none';
		$('tblListadoTot').style.display='none';
		$('tblBusquedaTot').style.display='none';
		$('tblLogoGotoSystems').style.display='none';");
		
	$objResponse -> script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
		$('divFlotante2').style.display='none';
	");
	
	return $objResponse;
}




function buscarArticulo($valForm, $valFormEnc){
	$objResponse = new xajaxResponse();

	$codArticulo = "";
	for ($cont = 0; $cont <= $valForm['hddCantCodigo']; $cont++) {
		$codArticulo .= $valForm['txtCodigoArticulo'.$cont].";";
	}
	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);
		
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
				$codArticulo,
				$valForm['txtDescripcionBusq'],
				$valForm['lstMarcaBusq'],
				$valForm['lstTipoArticuloBusq'],
				$valForm['lstSeccionBusq'],
				$valForm['lstSubSeccionBusq'],
				$valFormEnc['hddIdUnidadBasica']);
	
	$objResponse->script("xajax_listadoArticulos('0','','',$('txtIdEmpresa').value + '|".$valBusq."')");
	
	return $objResponse;	
}

function buscarPaquete($valForm){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
				$valForm['txtDescripcionBusq']);
	
	$objResponse->script("xajax_listado_paquetes_por_unidad('0','','',$('txtIdEmpresa').value + '|' + $('hddIdUnidadBasica').value + '|' + $('lstTipoOrden').value + '|".$valBusq."');");
	return $objResponse;	
}

function buscarTempario($valForm,$valFormTotalDocumento){
	$objResponse = new xajaxResponse();
	
	if($valFormTotalDocumento['hddTipoDocumento']==1)
		 $objResponse->script("
			  $('tdlstMecanico').style.display='none';
			  $('tdMecanico').style.display='none';");
	else
	{
		if($valFormTotalDcto['hddMecanicoEnOrden'] == 1)
			$objResponse -> script("
				$('tdlstMecanico').style.display='';
				$('tdMecanico').style.display='';");
		else
			$objResponse -> script("
				$('tdlstMecanico').style.display='none';
				$('tdMecanico').style.display='none';");
	}
	
	$valBusq = sprintf("%s|%s|%s",
		$valForm['txtDescripcionBusq'],
		$valForm['lstSeccionTemp'],
		$valForm['lstSubseccionTemp']);
	
	$objResponse->script("xajax_listado_tempario('0','','',$('txtIdEmpresa').value + '|' + $('hddIdUnidadBasica').value + '|' + $('lstTipoOrden').value + '|".$valBusq."');");
	return $objResponse;	
}
function buscarTot($valForm,$valFormTotalDocumento){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
				$valForm['txtDescripcionBusq']);
	
	$objResponse->script("xajax_listado_tot('0','','',$('txtIdEmpresa').value + '|' + $('txtIdPresupuesto').value + '|' + $('lstTipoOrden').value + '|".$valBusq."');");
	return $objResponse;		
}

function listadoArticulos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0)
		$sqlBusq = sprintf(" WHERE vw_iv_articulos_empresa.id_empresa = %s",
			valTpDato($valCadBusq[0],"int"));
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_empresa.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_modelos_compatibles.id_articulo = %s OR vw_iv_articulos_empresa.descripcion LIKE %s)",
			valTpDato($valCadBusq[2],"int"),
			valTpDato("%".$valCadBusq[2]."%","text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_empresa.id_marca = %s", valTpDato($valCadBusq[3],"int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_empresa.tipo_articulo LIKE %s", valTpDato($valCadBusq[4],"text"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT id_seccion FROM iv_subsecciones WHERE id_subseccion = vw_iv_articulos_empresa.id_subseccion) = %s", valTpDato($valCadBusq[5],"int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_empresa.id_subseccion = %s", valTpDato($valCadBusq[6],"text"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_modelos_compatibles.id_uni_bas = %s", valTpDato($valCadBusq[7],"text"));
	}
	
	
	$query = sprintf("
		SELECT
		vw_iv_articulos_empresa.id_articulo_empresa,
		vw_iv_articulos_empresa.id_empresa,
		
		vw_iv_articulos_modelos_compatibles.id_articulo,
		vw_iv_articulos_empresa.codigo_articulo,
		vw_iv_articulos_empresa.descripcion,
		vw_iv_articulos_empresa.id_marca,
		#vw_iv_articulos_empresa.marca,
		vw_iv_articulos_empresa.id_tipo_articulo,
		#vw_iv_articulos_empresa.tipo_articulo,
		vw_iv_articulos_empresa.codigo_articulo_prov,
		(SELECT id_seccion FROM iv_subsecciones WHERE iv_subsecciones.id_subseccion= vw_iv_articulos_empresa.id_subseccion) AS id_seccion,
		  (SELECT descripcion FROM iv_secciones WHERE iv_secciones.id_seccion= (SELECT id_seccion FROM iv_subsecciones WHERE iv_subsecciones.id_subseccion= vw_iv_articulos_empresa.id_subseccion)) AS descripcion_seccion,
		
		vw_iv_articulos_empresa.id_subseccion,
		(SELECT descripcion FROM iv_subsecciones WHERE iv_subsecciones.id_subseccion= vw_iv_articulos_empresa.id_subseccion) AS descripcion_subseccion,
		vw_iv_articulos_empresa.stock_minimo,
		vw_iv_articulos_empresa.stock_maximo,
		vw_iv_articulos_empresa.id_casilla_predeterminada,
		vw_iv_articulos_empresa.clasificacion,
		#vw_iv_articulos_empresa.unidad,
		#vw_iv_articulos_empresa.foto,
		#vw_iv_articulos_empresa.id_empresa_creador,


		vw_iv_articulos_empresa.existencia,
		vw_iv_articulos_empresa.cantidad_reservada,
		vw_iv_articulos_empresa.cantidad_disponible_fisica,
		vw_iv_articulos_empresa.cantidad_espera,
		vw_iv_articulos_empresa.cantidad_disponible_logica,
		vw_iv_articulos_empresa.cantidad_pedida,
		vw_iv_articulos_empresa.cantidad_futura,
		vw_iv_articulos_modelos_compatibles.des_uni_bas,
		vw_iv_articulos_modelos_compatibles.nom_uni_bas,
		vw_iv_articulos_modelos_compatibles.id_modelo,
		vw_iv_articulos_modelos_compatibles.nom_modelo,
		vw_iv_articulos_modelos_compatibles.des_modelo,
		vw_iv_articulos_modelos_compatibles.id_version,
		vw_iv_articulos_modelos_compatibles.nom_version,
		vw_iv_articulos_modelos_compatibles.des_version,
		vw_iv_articulos_modelos_compatibles.id_articulo_modelo_compatible,
		vw_iv_articulos_modelos_compatibles.id_uni_bas,
		vw_iv_articulos_modelos_compatibles.nom_marca,
		vw_iv_articulos_modelos_compatibles.des_marca
		FROM
		vw_iv_articulos_modelos_compatibles
		INNER JOIN vw_iv_articulos_empresa ON (vw_iv_articulos_modelos_compatibles.id_articulo = vw_iv_articulos_empresa.id_articulo)");
		
		
		
	$query .= $sqlBusq;	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		if (!$rs) return $objResponse->alert(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Articulos");
	
        $htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
        $htmlTh .= "<td></td>";
//	$htmlTblIni .= "<table border=\"1\" class=\"tabla texto_9px\" cellpadding=\"2\" width=\"100%\">";
//	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna texto_10px\">
//				<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Codigo"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "40%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripcion"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "10%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Marca"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "6%", $pageNum, "existencia", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "5%", $pageNum, "cantidad_reservada", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Reservadas (Servicios)"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "8%", $pageNum, "cantidad_espera", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Espera por Facturar"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "6%", $pageNum, "cantidad_disponible_logica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Disponible"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "6%", $pageNum, "cantidad_pedida", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Pedida a Proveedor"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "5%", $pageNum, "cantidad_futura", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Futura"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "2%", $pageNum, "clasificacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Clasif."));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		
		$contFila ++;
		
		if ($row['cantidad_disponible_logica'] == "" || $row['cantidad_disponible_logica'] == 0)
			$srcIcono = "../img/iconos/ico_error.gif";
		else if ($row['cantidad_disponible_logica'] <= $row['stock_minimo'])
			$srcIcono = "../img/iconos/ico_alerta.gif";
		else if ($row['cantidad_disponible_logica'] > $row['stock_minimo'])
			$srcIcono = "../img/iconos/ico_aceptar.gif";
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarArticulo('".$row['id_articulo']."', xajax.getFormValues('frmPresupuesto'));\" title=\"Seleccionar Articulo\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'], ";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca'])." (".$row['tipo_articulo'].")</td>";
			$htmlTb .= "<td align=\"center\">".$row['existencia']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad_reservada']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad_espera']."</td>";
			$htmlTb .= "<td align=\"center\" style=\"background-color:#A8FF99\">".$row['cantidad_disponible_logica']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad_pedida']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad_futura']."</td>";
			$htmlTb .= "<td align=\"center\">";
				switch($row['clasificacion']) {
					case 'A' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_a.gif\" title=\"".utf8_encode("Clasificacion A")."\"/>"; break;
					case 'B' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_b.gif\" title=\"".utf8_encode("Clasificacion B")."\"/>"; break;
					case 'C' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_c.gif\" title=\"".utf8_encode("Clasificacion C")."\"/>"; break;
					case 'D' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_d.gif\" title=\"".utf8_encode("Clasificacion D")."\"/>"; break;
					case 'E' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_e.gif\" title=\"".utf8_encode("Clasificacion E")."\"/>"; break;
					case 'F' : $htmlTb .= "<img src=\"../img/iconos/ico_clasificacion_f.gif\" title=\"".utf8_encode("Clasificacion F")."\"/>"; break;
				}
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
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
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	//$objResponse->assign("tdListadoArticulos","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
        $objResponse->assign("tdListadoArticulos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("
		$('tblListados').style.display='none';
		$('tblArticulo').style.display='';
		$('tblLogoGotoSystems').style.display='none';");
	
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
			$('txtDescripcionBusq').focus();
		}
		$('divFlotante2').style.display='none';
	");
	
	return $objResponse;
}

function listadoArticulosSustitutos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 2, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT * FROM vw_iv_articulos_sustitutos_empresa WHERE id_articulo_ppal = %s AND id_empresa = %s",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[1],"int"));
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Articulos Sustitutos y Alternos");
	
	$htmlTableIni .= "<table width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
		if ($row['cantidad_disponible_logica'] == "" || $row['cantidad_disponible_logica'] <= 0)
			$srcIcono = "../img/iconos/ico_error.gif";
		else if ($row['cantidad_disponible_logica'] > 5)
			$srcIcono = "../img/iconos/ico_aceptar.gif";
		else if ($row['cantidad_disponible_logica'] <= 5)
			$srcIcono = "../img/iconos/ico_alerta.gif";
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= sprintf("<td align=\"center\" rowspan=\"5\" valign=\"top\"><img class=\"imgBorde\" src=\"%s\" border=\"0\" width=\"100\" /></td>",
				$row['foto']);
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" width=\"18%\">".("C&oacute;digo:")."</td>";
			$htmlTb .= "<td width=\"50%\">".$row['codigo_articulo']."</td>";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" width=\"18%\">".utf8_encode("Disponibilidad:")."</td>";
			$htmlTb .= "<td width=\"14%\">".$row['cantidad_disponible_logica']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Marca:")."</td>";
			$htmlTb .= "<td colspan=\"3\">".$row['marca']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Tipo Articulo:")."</td>";
			$htmlTb .= "<td colspan=\"3\">".$row['tipo_articulo']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Descripci&oacute;n:")."</td>";
			$htmlTb .= "<td colspan=\"3\">".$row['descripcion']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"right\" colspan=\"4\">"."<button type=\"button\" onclick=\"$('divFlotante2').style.display='none'; xajax_asignarArticulo('".$row['id_articulo']."', xajax.getFormValues('frmPresupuesto'));\" title=\"Seleccionar Articulo\"><img src=\"".$srcIcono."\"/></button>"."</td>";
		$htmlTb .= "</tr>";
		$htmlTb.= "<tr>";
			$htmlTb .= "<td colspan=\"5\"><hr></td>";
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulosSustitutos(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulosSustitutos(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoArticulosSustitutos(%s,'%s','%s','%s',%s)\">",
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulosSustitutos(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulosSustitutos(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"2\" class=\"divMsjError\">No se encontraron registros.</td>";
	
	$objResponse->assign("tdContenidoTabs","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("
		if ($('divFlotante2').style.display == 'none') {
			$('divFlotante2').style.display='';
			/*init();*/
			centrarDiv($('divFlotante2'));
		}
	");
	
	return $objResponse;
}

function listadoArticulosAlternos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 2, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
	$query = sprintf("SELECT * FROM vw_iv_articulos_alternos_empresa WHERE id_articulo_ppal = %s AND id_empresa = %s",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[1],"int"));
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Articulos Sustitutos y Alternos");
	
	$htmlTableIni .= "<table width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		
		if ($row['cantidad_disponible_logica'] == "" || $row['cantidad_disponible_logica'] <= 0)
			$srcIcono = "../img/iconos/ico_error.gif";
		else if ($row['cantidad_disponible_logica'] > 5)
			$srcIcono = "../img/iconos/ico_aceptar.gif";
		else if ($row['cantidad_disponible_logica'] <= 5)
			$srcIcono = "../img/iconos/ico_alerta.gif";
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= sprintf("<td align=\"center\" rowspan=\"5\" valign=\"top\"><img class=\"imgBorde\" src=\"%s\" border=\"0\" width=\"100\" /></td>",
				$row['foto']);
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" width=\"18%\">".utf8_encode("C&oacute;digo:")."</td>";
			$htmlTb .= "<td width=\"50%\">".$row['codigo_articulo']."</td>";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\" width=\"18%\">".utf8_encode("Disponibilidad:")."</td>";
			$htmlTb .= "<td width=\"14%\">".$row['cantidad_disponible_logica']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Marca:")."</td>";
			$htmlTb .= "<td colspan=\"3\">".$row['marca']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".utf8_encode("Tipo Articulo:")."</td>";
			$htmlTb .= "<td colspan=\"3\">".$row['tipo_articulo']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">".("Descripci&oacute;n:")."</td>";
			$htmlTb .= "<td colspan=\"3\">".$row['descripcion']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"right\" colspan=\"4\">"."<button type=\"button\" onclick=\"$('divFlotante2').style.display='none'; xajax_asignarArticulo('".$row['id_articulo']."', xajax.getFormValues('frmPresupuesto'));\" title=\"Seleccionar Articulo\"><img src=\"".$srcIcono."\"/></button>"."</td>";
		$htmlTb .= "</tr>";
		$htmlTb.= "<tr>";
			$htmlTb .= "<td colspan=\"5\"><hr></td>";
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
					$htmlTf .= sprintf("<a class=\"puntero\" &nbsp;onclick=\"xajax_listadoArticulosAlternos(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulosAlternos(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoArticulosAlternos(%s,'%s','%s','%s',%s)\">",
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulosAlternos(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulosAlternos(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"2\" class=\"divMsjError\">No se encontraron registros.</td>";
	
	$objResponse->assign("tdContenidoTabs","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("
		if ($('divFlotante2').style.display == 'none') {
			$('divFlotante2').style.display='';
			/*init();*/
			centrarDiv($('divFlotante2'));
		}
	");
	
	return $objResponse;
}

function buscarDcto($valForm){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s",
		$valForm['lstEmpresaBusq']);
	
	$objResponse->script("xajax_listadoDctos('0','','','".$valBusq."')");
	
	return $objResponse;	
}

function buscarCliente($valForm, $valFormVale){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s",
		$valForm['txtIdEmpresa'],
		$valFormVale['txtBusq']);
		
		$objResponse->script("$('trBuscarCliente').style.display='';");
		$objResponse->script("$('trBuscarUnidad').style.display='none';");
	
	$objResponse->script("xajax_listadoClientes('0','','','".$valBusq."')");
	return $objResponse;	
}
function buscarUnidad($valForm, $valFormVale){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s",
		$valForm['txtIdEmpresa'],
		$valFormVale['txtPalabra']);
		
	$objResponse->script("$('trBuscarUnidad').style.display='';");
	$objResponse->script("$('trBuscarCliente').style.display='none';");
	$objResponse->script("xajax_listadoUnidades('0','','','".$valBusq."')");
	return $objResponse;	
}

function listadoUnidades($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("%s (sa_v_unidad_basica.nombre_unidad_basica LIKE %s OR sa_v_unidad_basica.nom_modelo LIKE %s ) )",
			"sa_unidad_empresa.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']." AND",
		    valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"));
	}else{
		$sqlBusq =" WHERE sa_unidad_empresa.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']." ";
	}
	
	$query = ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") ? 
		"SELECT * FROM sa_v_unidad_basica LEFT JOIN sa_unidad_empresa ON sa_v_unidad_basica.id_unidad_basica = sa_unidad_empresa.id_unidad_basica 
		LEFT JOIN pg_empresa ON sa_unidad_empresa.id_empresa = pg_empresa.id_empresa " : 
		"SELECT * FROM sa_v_unidad_basica LEFT JOIN sa_unidad_empresa ON sa_v_unidad_basica.id_unidad_basica = sa_unidad_empresa.id_unidad_basica
		LEFT JOIN pg_empresa ON sa_unidad_empresa.id_empresa = pg_empresa.id_empresa ";
		
	$query .= $sqlBusq;
				
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	//$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	$rsLimit = mysql_query($queryLimit);
	
	if(!$rsLimit){
		return $objResponse->alert("Error seleccionando las unidades basicas para el listado. \n Error: ".mysql_error()." \n Nº Error: ".mysql_errno()." \n Query: ".$queryLimit." \n Archivo: ".__FILE__." \n Linea: ".__LINE__."");	
	}
	
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML", utf8_encode("Listado de Unidades"));
	$objResponse->assign("tblListados","width","990px");
	
	$htmlTableIni .= "<table width=\"100%\">";

	$htmlTh .= "<tr class=\"tituloColumna\">
				<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "7%", $pageNum, "id_unidad_basica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "10%", $pageNum, "nombre_unidad_basica", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");	
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "16%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Modelo"));	
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "30%", $pageNum, "unidad_completa", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion");
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "5%", $pageNum, "nom_combustible", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Combustible"));	
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "10%", $pageNum, "nom_transmision", $campOrd, $tpOrd, $valBusq, $maxRows, "Transmision");
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "7%", $pageNum, "nom_version", $campOrd, $tpOrd, $valBusq, $maxRows, "Version");
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "15%", $pageNum, "ano", $campOrd, $tpOrd, $valBusq, $maxRows, "Año");
		
		$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$estado = ($row['estado'] == 0) ? "<img src='../img/iconos/ico_verde.gif' width='12' height='12' />" : "<img src='../img/iconos/ico_rojo.gif' width='12' height='12' />";
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarUnidadBasica('".$row['id_unidad_basica']."', xajax.getFormValues('frmTotalPresupuesto'));\" title=\"Seleccionar Cliente\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_unidad_basica']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_unidad_basica'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_modelo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['unidad_completa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_combustible'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_transmision'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_version'])."</td>";
			$htmlTb .= "<td empresa = '".$row['nombre_empresa']."'>".$row['ano']."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\" >";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidades(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidades(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoUnidades(%s,'%s','%s','%s',%s)\">",
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidades(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidades(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"12\" class=\"divMsjError\">No se encontraron registros.</td>";
	
	$objResponse->assign("tdListado","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("
		$('trBuscarPresupuesto').style.display='none';
		$('tblArticulo').style.display='none';
		$('tblListados').style.display='';
		$('tblBusquedaPaquete').style.display='none';
		$('btnAsignarPaquete').style.display='none';
		$('tdListadoPaquetes').style.display='none';
		$('tblListadoTempario').style.display='none';  
		$('tblTemparios').style.display='none';  
		$('tdHrTblPaquetes').style.display='none';
		$('tblGeneralPaquetes').style.display='none';
		$('tblNotas').style.display='none';
		$('tblListadoTot').style.display='none';
		$('tblBusquedaTot').style.display='none';
		$('tblLogoGotoSystems').style.display='none';");
		
	$objResponse -> script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
		$('divFlotante2').style.display='none';
	");
	
	return $objResponse;
}




function listadoDctos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 8, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$query = sprintf("SELECT * FROM vw_sa_presupuesto_venta WHERE estatus_presupuesto_venta = 0 AND id_empresa_reg = %s",
		$valCadBusq[0]);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Presupuestos de Venta");
	$objResponse->assign("tblListados","width","980px");
	
	$objResponse->script("
		$('trBuscarPresupuesto').style.display='';
		$('tblListados').style.display='';
		$('tblArticulo').style.display='none';
		$('tblLogoGotoSystems').style.display='none';");
	
	$htmlTableIni = "<table width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td></td>
            	<td>".utf8_encode("Presupuesto")."</td>
				<td>".utf8_encode("Tipo de Orden")."</td>
                <td>".utf8_encode("Fecha")."</td>
				<td>".utf8_encode("Fecha Vencimiento")."</td>
                <td>".utf8_encode("Cliente")."</td>
                <td>".utf8_encode("Presupuestos")."</td>
            </tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_cargarDcto('".$row['id_presupuesto_venta']."', xajax.getFormValues('frmTotalPresupuesto'));\" title=\"Seleccionar Presupuesto\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>";
			$htmlTb .= "<td align=\"right\">".$row['id_presupuesto_venta']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_vencimiento']))."</td>";
			$htmlTb .= "<td>".$row['nombre']." ".$row['apellido']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_presupuesto_venta']."</td>";

		$html .= "</tr>";
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\">";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDctos(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDctos(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoDctos(%s,'%s','%s','%s',%s)\">",
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDctos(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDctos(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"10\" class=\"divMsjError\">No se encontraron registros.</td>";
	
	$objResponse->assign("tdListado","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			$('tblNotas').style.display='none';
			$('tblListadoTempario').style.display='none';
			$('tblTemparios').style.display='none';
			centrarDiv($('divFlotante'));
		}");
	
	return $objResponse;
}


function asignarEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_empresa_reg = %s", valTpDato($idEmpresa,"text"));
	$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error().$queryEmpresa);//
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$objResponse->assign("txtIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	
	$nombreSucursal = "";
	if ($rowEmpresa['id_empresa_padre_suc'] > 0)
		$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";
	
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal));
		
	$objResponse->script("
		$('tblListados').style.display='none';
		$('tblArticulo').style.display='none';
		$('tblLogoGotoSystems').style.display='';
		$('divFlotante2').style.display='none';
		$('divFlotante').style.display='none';");
	$objResponse->script("xajax_calcularTotalDcto();");
	
	return $objResponse;
}

function asignarCliente($idCliente, $accion = "") {
	$objResponse = new xajaxResponse();
	
	if($accion == "" || $accion == 1)
	{
		$queryCliente = sprintf("SELECT * FROM cj_cc_cliente WHERE id = %s", valTpDato($idCliente, "int"));
		$rsCliente = mysql_query($queryCliente) or die(mysql_error().$queryCliente);
		$rowCliente = mysql_fetch_assoc($rsCliente);
			
		$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
		$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre'])." ".utf8_encode($rowCliente['apellido']));
		$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
		$objResponse->assign("txtTelefonosCliente","value",$rowCliente['telf']);
		$objResponse->assign("txtRifCliente","value",$rowCliente['lci'].$rowCliente['ci']);
	
		$objResponse->script("
			$('tblListados').style.display='none';
			$('tblArticulo').style.display='none';
			$('tblLogoGotoSystems').style.display='';
			$('divFlotante2').style.display='none';
			$('divFlotante').style.display='none';");
		$objResponse->script("xajax_calcularTotalDcto();");
	}
	
	return $objResponse;
}
function asignarUnidadBasica($idUniBas, $valFormTotalDcto, $accion = "") {
	$objResponse = new xajaxResponse();
	
	
		if($accion == "" || $accion == 1)
		{
			if($valFormTotalDcto['hddItemsCargados'] > 0)
				$objResponse -> alert('La orden tiene items cargados. Si desea escoger otro vale de recepcion, elimine los items cargados e intente nuevamente.');
			else
			{
				$query = sprintf("SELECT 
					*
					FROM
					sa_v_unidad_basica WHERE sa_v_unidad_basica.id_unidad_basica = %s", valTpDato($idUniBas, "int"));
				$rs = mysql_query($query) or die(mysql_error().$query);
				$row = mysql_fetch_assoc($rs);
		
				$objResponse->assign("txtUnidadBasica","value", utf8_encode($row['nombre_unidad_basica']));
				$objResponse->assign("txtMarca","value", utf8_encode($row['nom_marca']));
				$objResponse->assign("txtModeloUnidadBasica","value", utf8_encode($row['nom_modelo']));
				$objResponse->assign("txt_ano_vehiculo","value",$row['ano']);
				$objResponse->assign("hdd_id_modelo","value",$row['id_modelo']);
				$objResponse->assign("hddIdUnidadBasica","value",$row['id_unidad_basica']);
				
				$objResponse->script("
					$('tblListados').style.display='none';
					$('tblArticulo').style.display='none';
					$('tblLogoGotoSystems').style.display='';
					$('divFlotante2').style.display='none';
					$('divFlotante').style.display='none';");
				$objResponse->script("xajax_calcularTotalDcto();");
			}
		}
	
	return $objResponse;
}

function asignarArticulo($idArticulo, $valFormDcto) {
	$objResponse = new xajaxResponse();

	$queryArticulo = sprintf("SELECT 
		#(case iv_articulos.posee_iva when '1' then(SELECT idIva FROM pg_iva WHERE estado = 1 AND tipo = 6) when '0' then '0' end) AS iva_repuesto,
                
                (CASE (SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1)
			WHEN '1' THEN
				(SELECT idIva FROM pg_iva WHERE estado = 1 AND (tipo = 6) ORDER BY iva)
		END) AS id_iva,
		
		(CASE (SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = iv_articulos.id_articulo LIMIT 1)
			WHEN '1' THEN
				(SELECT iva FROM pg_iva WHERE estado = 1 AND (tipo = 6) ORDER BY iva)
		END) AS iva,
                
		  vw_iv_articulos_empresa.id_articulo_empresa,
		  vw_iv_articulos_empresa.id_empresa,
		  vw_iv_articulos_empresa.id_articulo,
		  vw_iv_articulos_empresa.codigo_articulo,
		  vw_iv_articulos_empresa.descripcion,
		  vw_iv_articulos_empresa.id_marca,
		  #vw_iv_articulos_empresa.marca,
		  vw_iv_articulos_empresa.id_tipo_articulo,
		  #vw_iv_articulos_empresa.tipo_articulo,
		  vw_iv_articulos_empresa.codigo_articulo_prov,
		  (SELECT id_seccion FROM iv_subsecciones WHERE iv_subsecciones.id_subseccion= vw_iv_articulos_empresa.id_subseccion) AS id_seccion,
		  (SELECT descripcion FROM iv_secciones WHERE iv_secciones.id_seccion= (SELECT id_seccion FROM iv_subsecciones WHERE iv_subsecciones.id_subseccion= vw_iv_articulos_empresa.id_subseccion)) AS descripcion_seccion,
		  vw_iv_articulos_empresa.id_subseccion,
		  (SELECT descripcion FROM iv_subsecciones WHERE iv_subsecciones.id_subseccion= vw_iv_articulos_empresa.id_subseccion) AS descripcion_subseccion,
		  vw_iv_articulos_empresa.stock_minimo,
		  vw_iv_articulos_empresa.stock_maximo,
		  vw_iv_articulos_empresa.id_casilla_predeterminada,
		  vw_iv_articulos_empresa.clasificacion,
		  #vw_iv_articulos_empresa.unidad,
		  #vw_iv_articulos_empresa.foto,
		  #vw_iv_articulos_empresa.existencia,
		  vw_iv_articulos_empresa.cantidad_reservada,
		  vw_iv_articulos_empresa.cantidad_disponible_fisica,
		  vw_iv_articulos_empresa.cantidad_espera,
		  vw_iv_articulos_empresa.cantidad_disponible_logica,
		  vw_iv_articulos_empresa.cantidad_pedida,
		  vw_iv_articulos_empresa.cantidad_futura,
		  iv_articulos_precios.id_articulo_precio,
		  iv_articulos_precios.id_precio,
		  iv_articulos_precios.precio,
		  iv_articulos.descripcion,
		  iv_articulos.posee_iva,
                  iv_articulos.id_precio_predeterminado
		FROM
		iv_articulos
		INNER JOIN vw_iv_articulos_empresa ON (iv_articulos.id_articulo = vw_iv_articulos_empresa.id_articulo)
		INNER JOIN iv_articulos_precios ON (iv_articulos.id_articulo = iv_articulos_precios.id_articulo) WHERE iv_articulos.id_articulo = %s AND vw_iv_articulos_empresa.id_empresa = %s 
                AND iv_articulos_precios.id_precio = vw_iv_articulos_empresa.id_precio_predeterminado ",
	valTpDato($idArticulo,"int"),
	valTpDato($valFormDcto['txtIdEmpresa'],"int")
	//,valTpDato($valFormDcto['lstTipoOrden'],"int")
                );	
	
	$rsArticulo = mysql_query($queryArticulo) or die(mysql_error().$queryArticulo);
	$rowArticulo = mysql_fetch_assoc($rsArticulo);

	if($rowArticulo['id_articulo'] == "")
	{
		$objResponse->alert("El Articulo no tiene Precio asignado");
		$objResponse->assign("txtPrecioRepuesto","value","");
		return $objResponse;
	}
	else
	{
	  if($rowArticulo['id_iva']!=0)
	  {
	    $queryIva ="SELECT * FROM pg_iva WHERE tipo= 6 AND activo= 1 AND estado= 1";
		$rsIva = mysql_query($queryIva) or die(mysql_error().$queryIva);
		$rowIva = mysql_fetch_assoc($rsIva);
	 }
//	 else
//	 {
//	 	$queryIva ="SELECT * FROM pg_iva WHERE tipo= 6 AND activo= 1 AND estado= 1";
//		$rsIva = mysql_query($queryIva) or die(mysql_error().$queryIva);
//		$rowIva = mysql_fetch_assoc($rsIva);
//	}

	if ($rowArticulo['id_articulo'] != "") {

		$precio_articulo = $rowArticulo['precio'];
		$queryExtraerPrecioEspecial = sprintf("SELECT
			pg_precios.id_precio
			FROM
			pg_precios
			INNER JOIN iv_articulos_precios_cliente ON (pg_precios.id_precio = iv_articulos_precios_cliente.id_precio)
			WHERE
			iv_articulos_precios_cliente.id_cliente = %s AND
			iv_articulos_precios_cliente.id_articulo = %s",
		valTpDato($valFormDcto['txtIdCliente'],"int"),
		$rowArticulo['id_articulo']);

		$rsExtraerPrecioEspecial = mysql_query($queryExtraerPrecioEspecial) or die(mysql_error().$queryExtraerPrecioEspecial);
		$rowExtraerPrecioEspecial = mysql_fetch_assoc($rsExtraerPrecioEspecial);

		if($rowExtraerPrecioEspecial['id_precio'] != "")
		{
			$queryPrecioEspecial = sprintf("SELECT
				*
				FROM
				iv_articulos_precios
				WHERE
				iv_articulos_precios.id_precio = %s AND iv_articulos_precios.id_articulo = %s",
				$rowExtraerPrecioEspecial['id_precio'],
				 $rowArticulo['id_articulo']);
			$rsPrecioEspecial = mysql_query($queryPrecioEspecial)or die(mysql_error().$queryPrecioEspecial);
			$rowPrecioEspecial = mysql_fetch_assoc($rsPrecioEspecial);
			$precio_articulo = $rowPrecioEspecial['precio'];
		}

		/* BUSCA EL ULTIMO COSTO DEL ARTICULO */
		$queryCostoArt = sprintf("SELECT * FROM iv_articulos_costos
		WHERE id_articulo = %s
		ORDER BY id_articulo_costo
		DESC LIMIT 1;",
			valTpDato($rowArticulo['id_articulo'],"int"));
		$rsCostoArt = mysql_query($queryCostoArt);
		if (!$rsCostoArt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowCostoArt = mysql_fetch_assoc($rsCostoArt);

		$fechaUltimaCompra = ($rowCostoArt['fecha'] != "") ? date("d-m-Y",strtotime($rowCostoArt['fecha'])) : "----------";
		$fechaUltimaVenta = ($rowArticulo['fecha_ultima_venta'] != "") ? date("d-m-Y",strtotime($rowArticulo['fecha_ultima_venta'])) : "----------";

		$objResponse->assign("hddIdArt","value",$rowArticulo['id_articulo']);
		$objResponse->assign("txtCodigoArt","value",utf8_encode(elimCaracter($rowArticulo['codigo_articulo'], ";")));
		$objResponse->assign("txtDescripcionArt","innerHTML",utf8_encode($rowArticulo['descripcion']));
		$objResponse->assign("txtFechaUltCompraArt","value",$fechaUltimaCompra);
		$objResponse->assign("txtSeccionArt","value",utf8_encode($rowArticulo['descripcion_seccion']));
		$objResponse->assign("txtFechaUltVentaArt","value",$fechaUltimaVenta);
		$objResponse->assign("txtTipoPiezaArt","value",utf8_encode($rowArticulo['tipo_articulo']));
		$objResponse->assign("txtCantDisponible","value",$rowArticulo['cantidad_disponible_logica']);
		$objResponse->assign("hddIdPrecioRepuesto","value",$rowArticulo['id_articulo_precio']);
                $objResponse->assign("hddPrecioRepuestoDB","value",$precio_articulo);
		$objResponse->assign("txtPrecioRepuesto","value",$precio_articulo);
		$objResponse->assign("hddIdIvaRepuesto","value",$rowIva['idIva']);
		$objResponse->assign("txtIvaRepuesto","value",$rowIva['iva']."%");

		if ($rowArticulo['cantidad_disponible_logica'] > 0) {
			$objResponse->script("$('txtCantDisponible').className = 'inputCantidadDisponible'");
			$objResponse->script("$('tdMsjArticulo').style.display='none';");
		} else {
			$objResponse->script("$('txtCantDisponible').className = 'inputCantidadNoDisponible'");

			$htmlMsj = "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlMsj .= "<tr>";
				$htmlMsj .= "<td width=\"25\"><img src=\"../img/iconos/ico_alert.gif\" width=\"25\"/></td>";
				$htmlMsj .= "<td align=\"center\">";
					$htmlMsj .= utf8_encode(sprintf("El producto ( %s ) no tiene disponibilidad. Para ver los articulos sustitutos y alternos del m&iacute;smo, presione ",
						$rowArticulo['codigo_articulo']));
					$htmlMsj .= sprintf("<span class=\"linkAzulUnderline puntero\" onclick=\"
						xajax_listadoArticulosSustitutos(0,'','','%s|%s');
						$('tdArticulosAlternos').className = 'rafktabs_title';
						$('tdArticulosSustitutos').className = 'rafktabs_titleActive';

						$('tdArticulosSustitutos').onclick = function() {
							xajax_listadoArticulosSustitutos(0,'','','%s|%s');
							$('tdArticulosAlternos').className = 'rafktabs_title';
							$('tdArticulosSustitutos').className = 'rafktabs_titleActive';
						};
						$('tdArticulosAlternos').onclick = function() {
							xajax_listadoArticulosAlternos(0,'','','%s|%s');
							$('tdArticulosAlternos').className = 'rafktabs_titleActive';
							$('tdArticulosSustitutos').className = 'rafktabs_title';
						}\">".utf8_encode("aqu&iacute;")."</span>",
						$rowArticulo['id_articulo'], $valFormDcto['txtIdEmpresa'],
						$rowArticulo['id_articulo'], $valFormDcto['txtIdEmpresa'],
						$rowArticulo['id_articulo'], $valFormDcto['txtIdEmpresa']);
				$htmlMsj .= "</td>";
			$htmlMsj .= "</tr>";
			$htmlMsj .= "</table>";

			$objResponse->script("$('tdMsjArticulo').style.display='';");
			$objResponse->assign("tdMsjArticulo","innerHTML",$htmlMsj);
		}

		$objResponse->assign("txtCostoArt","value",$rowArticulo['costo_ultima_venta']);

		$objResponse->script("$('txtCantidadArt').focus();");

		/* VERIFICACION PARA EL MANEJEO DEL PRECIO ESPECIAL PARA CLIENTES */
                $precioPredet = $rowArticulo['id_precio_predeterminado'];
                $queryPrecioArticuloCliente = sprintf("SELECT * FROM iv_articulos_precios_cliente
                WHERE id_cliente = %s
                        OR (id_cliente = %s AND id_articulo = %s);",
                        valTpDato($valFormDcto['txtIdCliente'], "int"),
                        valTpDato($valFormDcto['txtIdCliente'], "int"),
                        valTpDato($idArticulo, "int"));
                $rsPrecioArticuloCliente = mysql_query($queryPrecioArticuloCliente);
                if (!$rsPrecioArticuloCliente) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
                while ($rowPrecioArticuloCliente = mysql_fetch_assoc($rsPrecioArticuloCliente)) {
			if ($rowPrecioArticuloCliente['id_articulo'] == $rowArticulo['id_articulo']) {
                                $precioPredet = $rowPrecioArticuloCliente['id_precio'];
				$precioArt = true;
			} else if ($rowPrecioArticuloCliente['id_articulo'] == "" && !isset($precioArt))
                                $precioPredet = $rowPrecioArticuloCliente['id_precio'];
		}
        }
        $objResponse->loadCommands(asignarPrecio($idArticulo, $precioPredet));

	$objResponse->script("$('divFlotante2').style.display='none';");

	}
        //---------------------------------------------------------------------------------------------------------------------------
        $queryPrecios = sprintf("SELECT * FROM pg_precios WHERE estatus = 1");
	$rsPrecios = mysql_query($queryPrecios);
	if (!$rsPrecios) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$htmlPrecios = "";
	while ($rowPrecios = mysql_fetch_assoc($rsPrecios)) {
		$seleccionPrecios = "";
		if (($selId == $row['id_precio'] && $selId != "") || $rowPrecios['id_precio'] == $precioPredet) {
			$seleccionPrecios = "selected='selected'";

			if ($rowPrecios['id_precio'] == $rowArticulo['id_articulo_precio'])
				$valorSelecPredPrecios = $rowPrecios['id_articulo_precio'];
				}

		$htmlPrecios .= "<option value=\"".$rowPrecios['id_precio']."\" ".$seleccionPrecios.">".htmlentities($rowPrecios['descripcion_precio'])."</option>";
		}

	$onChangePrecios = sprintf("
	if(this.value != 6 && this.value != %s){
		selectedOption(this.id,'%s');
	}
	xajax_asignarPrecio('%s',this.value);",
		$precioPredet,
		$valorSelecPredPrecios,
		$rowArticulo['id_articulo']);

	$htmlLstIniPrecios = "<select id=\"lstPrecioArt\" name=\"lstPrecioArt\" onchange=\"".$onChangePrecios."\">";
	$htmlLstFinPrecios = "</select>";

	$objResponse->assign("selectPrecio","innerHTML",$htmlLstIniPrecios.$htmlPrecios.$htmlLstFinPrecios);
        $objResponse->script("$('txtPrecioRepuesto').style.background=''");
        //----------------------------------------------------------------------------------------------------------------------------
	return $objResponse;
}

function asignarPrecio($idArticulo, $idPrecio) {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM iv_articulos_precios
	WHERE id_articulo = %s
		AND id_precio = %s;",
		valTpDato($idArticulo,"int"),
		valTpDato($idPrecio,"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);

	$objResponse->assign("hddIdPrecioRepuesto","value",$row['id_articulo_precio']);
        $objResponse->assign("hddIdPrecioRepuestoBD","value",$row['id_articulo_precio']);
	$objResponse->assign("txtPrecioRepuesto","value",$row['precio']);

	if ($idPrecio == 6) {
		$objResponse->script("abrir();");
	} else {
		$objResponse->script("$('txtPrecioRepuesto').readOnly = false;");

	}

	return $objResponse;
}


function cargaLstBusq() {
	$objResponse = new xajaxResponse();
	
	$queryMarca = sprintf("SELECT * FROM iv_marcas").$sqlBusq;
	$rsMarca = mysql_query($queryMarca);
	if (!$rsMarca) return $objResponse->alert(mysql_error());
	$html = "<select id=\"lstMarcaBusq\" name=\"lstMarcaBusq\" onchange=\"$('btnBuscarArticulo').click();\">";
		$html .= "<option value=\"-1\">Todos...</option>";
	while ($rowMarca = mysql_fetch_assoc($rsMarca)) {
		$html .= "<option value=\"".$rowMarca['id_marca']."\">".utf8_encode($rowMarca['marca'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMarcaBusq","innerHTML",$html);		
	
	$queryTipoArticulo = sprintf("SELECT * FROM iv_tipos_articulos").$sqlBusq;
	$rsTipoArticulo = mysql_query($queryTipoArticulo);
	if (!$rsTipoArticulo) return $objResponse->alert(mysql_error());
	$html = "<select id=\"lstTipoArticuloBusq\" name=\"lstTipoArticuloBusq\" onchange=\"$('btnBuscarArticulo').click();\">";
		$html .= "<option value=\"-1\">Todos...</option>";
	while ($rowTipoArticulo = mysql_fetch_assoc($rsTipoArticulo)) {
		$html .= "<option value=\"".utf8_encode($rowTipoArticulo['descripcion'])."\">".utf8_encode($rowTipoArticulo['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoArticuloBusq","innerHTML",$html);
	
	
	$querySeccion = sprintf("SELECT * FROM iv_secciones ORDER BY descripcion").$sqlBusq;
	$rsSeccion = mysql_query($querySeccion);
	if (!$rsSeccion) return $objResponse->alert(mysql_error());
	$html = "<select id=\"lstSeccionBusq\" name=\"lstSeccionBusq\" onchange=\"xajax_cargaLstSubSecciones(this.value); $('btnBuscarArticulo').click();\">";
		$html .= "<option value=\"-1\">Todos...</option>";
	while ($rowSeccion = mysql_fetch_assoc($rsSeccion)) {
		$html .= "<option value=\"".$rowSeccion['id_seccion']."\">".utf8_encode($rowSeccion['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSeccionBusq","innerHTML",$html);
	
	/**/
	
	$queryIva = sprintf("SELECT * FROM pg_iva WHERE estado = 1 AND (tipo = 6 OR tipo = 2) ORDER BY iva");
	$rsIva = mysql_query($queryIva);
	if (!$rsIva) return $objResponse->alert(mysql_error());
	$html = "<select id=\"lstIvaArt\" name=\"lstIvaArt\">";
		$html .= "<option value=\"-1\">Todos...</option>";
		$html .= "<option value=\"0\">NA</option>";
	while ($rowIva = mysql_fetch_assoc($rsIva)) {
		$html .= "<optgroup label=\"".utf8_encode($rowIva['observacion'])."\">";
			$selected = "";
			if ($rowIva['tipo'] == 1 && $rowIva['activo'] == 1)
				$selected = "selected='selected'";
			$html .= "<option ".$selected." value=\"".$rowIva['idIva']."\">".utf8_encode($rowIva['iva'])."%</option>";
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstIvaArt","innerHTML",$html);
	
	return $objResponse;
}


function cargaLstSubSecciones($idSeccion) {
	$objResponse = new xajaxResponse();
	
	$querySubSeccion = sprintf("SELECT * FROM iv_subsecciones WHERE id_seccion = %s", valTpDato($idSeccion,"int"));
	$rsSubSeccion = mysql_query($querySubSeccion) or die(mysql_error().$querySubSeccion);
	$html = "<select id=\"lstSubSeccionBusq\" name=\"lstSubSeccionBusq\" onchange=\"$('btnBuscarArticulo').click();\">";
		$html .= "<option value=\"-1\">Todos...</option>";
	while ($rowSubSeccion = mysql_fetch_assoc($rsSubSeccion)) {
		$html .= "<option value=\"".$rowSubSeccion['id_subseccion']."\">".utf8_encode($rowSubSeccion['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSubSeccionBusq","innerHTML",$html);
	
	return $objResponse;
}


function calculoPorcentajeDcto($valFormTotalDcto, $tpCalculo, $valor, $destino) {
	$objResponse = new xajaxResponse();
	
	$subTotal = str_replace(",","",$valFormTotalDcto['txtSubTotal']);
	$valor = str_replace(",","",$valor);
	$monto = ($tpCalculo == "Porc") ? $valor * ($subTotal/100) : $valor * (100/$subTotal);
	$objResponse->assign($destino,"value",number_format($monto,2,".",","));
		
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'),xajax.getFormValues('frm_agregar_paq'),xajax.getFormValues('frmListaManoObra'),xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmListaTot'));");
	
	return $objResponse;
}


function cargaLstMoneda($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_monedas ORDER BY descripcion");
	$rs = mysql_query($query) or die(mysql_error().$query);
	$html = "<select id=\"lstMoneda\" name=\"lstMoneda\">";
		$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['idmoneda'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['idmoneda']."\" ".$seleccion.">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMoneda","innerHTML",$html);
	
	return $objResponse;
}


function cargaLstEmpresaBusq($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	$rs = mysql_query($query) or die(mysql_error().$query);
	$html = "<select id=\"lstEmpresaBusq\" name=\"lstEmpresaBusq\" onchange=\"$('btnBuscarPresupuesto').click();\">";
	while ($row = mysql_fetch_assoc($rs)) {
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
	
		$selected = "";
		if ($selId == $row['id_empresa_reg'] || $idEmpresa == $row['id_empresa_reg'] || $_SESSION['idEmpresaUsuarioSysGts'] == $row['id_empresa_reg'])
			$selected = "selected='selected'";
		
		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpresaBusq","innerHTML",$html);
	
	return $objResponse;
}
function listado_paquetes_por_unidad($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 3, $totalRows = NULL)
{
	$objResponse = new xajaxResponse();
	$valCadBusq = explode("|", $valBusq);
	
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0)
		$sqlBusq = sprintf(" WHERE sa_paquetes.id_empresa = %s AND sa_paq_unidad.id_unidad_basica= %s",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[1],"int")
		);
	
	if ($valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("sa_paquetes.codigo_paquete LIKE %s OR sa_paquetes.descripcion_paquete LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato("%".$valCadBusq[3]."%","text"));
	}
	
	$queryTipoOrden = sprintf("SELECT 
		sa_tipo_orden.id_tipo_orden,
		sa_tipo_orden.precio_tempario,
		sa_tipo_orden.id_precio_repuesto
		FROM
		sa_tipo_orden
		WHERE
		sa_tipo_orden.id_tipo_orden = %s",
	   valTpDato($valCadBusq[2],"int"));
	 
	 $rsTipoOrden = mysql_query($queryTipoOrden) or die(mysql_error().$queryTipoOrden);
	 $rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
	 
	 $queryBaseUt = sprintf("SELECT 
		pg_parametros_empresas.valor_parametro
		FROM
		pg_parametros_empresas
		WHERE
		pg_parametros_empresas.descripcion_parametro = 1 AND 
		pg_parametros_empresas.id_empresa = %s",
		valTpDato($valCadBusq[0],"int"));
		
	 $rsBaseUt = mysql_query($queryBaseUt) or die(mysql_error().$queryBaseUt);
	 $rowBaseUt = mysql_fetch_assoc($rsBaseUt);
	 	
	$sqlBusq .=" GROUP BY 
		sa_paquetes.id_paquete 
		ORDER BY 
		sa_paquetes.codigo_paquete";
	
        $query = sprintf("SELECT 
		sa_paquetes.id_paquete AS id_paq,
		sa_paquetes.codigo_paquete,
		sa_paquetes.descripcion_paquete,
		(SELECT SUM(vw_sa_temparios.ut) AS duracion_paq FROM vw_sa_temparios
		WHERE vw_sa_temparios.id_paquete = id_paq
			#AND vw_sa_temparios.id_empresa = %s #NO NECESARIO ID ES UNICO
                        ) AS duracion_aprox,
		(IFNULL((SELECT 
				SUM(
				(CASE vw_sa_temparios.id_modo
					WHEN '1' THEN vw_sa_temparios.importe_por_tipo_orden * %s/%s
					WHEN '2' THEN vw_sa_temparios.importe_por_tipo_orden
					WHEN '3' THEN vw_sa_temparios.importe_por_tipo_orden
					WHEN '4' THEN vw_sa_temparios.importe_por_tipo_orden
				END)) AS total_paquete
			FROM vw_sa_temparios
			WHERE vw_sa_temparios.id_paquete = id_paq
				#AND vw_sa_temparios.id_empresa = %s #NO NECESARIO ID ES UNICO
                                ),0) 
		+
		(IFNULL((SELECT
				SUM(vw_sa_precios_articulos_por_paquete.subtotal) AS total_repuestos
				FROM vw_sa_precios_articulos_por_paquete
				WHERE vw_sa_precios_articulos_por_paquete.id_paquete = id_paq
					AND vw_sa_precios_articulos_por_paquete.id_empresa = %s
					AND vw_sa_precios_articulos_por_paquete.id_precio_repuesto= %s
					AND vw_sa_precios_articulos_por_paquete.id_tipo_orden = %s), 0))) AS total_paquete
	FROM sa_paquetes
		INNER JOIN sa_paq_unidad ON (sa_paquetes.id_paquete = sa_paq_unidad.id_paquete)
		INNER JOIN an_uni_bas ON (sa_paq_unidad.id_unidad_basica = an_uni_bas.id_uni_bas) %s",
		valTpDato($valCadBusq[0],"int"),
		$rowTipoOrden['precio_tempario'],
		$rowBaseUt['valor_parametro'],
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[0],"int"),
		$rowTipoOrden['id_precio_repuesto'],
		$rowTipoOrden['id_tipo_orden'],
		$sqlBusq);
		
        
//	$query = sprintf("SELECT 
//		  sa_paquetes.id_paquete AS id_paq,
//		  sa_paquetes.codigo_paquete,
//		  sa_paquetes.descripcion_paquete,
//		  (SELECT SUM(vw_sa_temparios.ut) AS duracion_paq
//			FROM
//			vw_sa_temparios
//			WHERE
//			vw_sa_temparios.id_paquete = id_paq AND 
//			vw_sa_temparios.id_empresa = %s) AS duracion_aprox,
//			(IFNULL((SELECT 
//			SUM(
//            (case vw_sa_temparios.`id_modo`
//				  when '1' then vw_sa_temparios.importe_por_tipo_orden * %s/%s
//				  when '2' then vw_sa_temparios.importe_por_tipo_orden
//				  when '3' then vw_sa_temparios.importe_por_tipo_orden
//				  when '4' then vw_sa_temparios.importe_por_tipo_orden
//				end)) AS total_paquete
//			FROM
//			vw_sa_temparios
//			WHERE
//			vw_sa_temparios.id_paquete = id_paq AND 
//			vw_sa_temparios.id_empresa = %s),0) 
//			+
//			(IFNULL((SELECT 
//			SUM(vw_sa_precios_articulos_por_paquete.subtotal) AS total_repuestos
//			FROM
//			vw_sa_precios_articulos_por_paquete
//			WHERE
//			vw_sa_precios_articulos_por_paquete.id_paquete = id_paq AND 
//			vw_sa_precios_articulos_por_paquete.id_empresa = %s AND vw_sa_precios_articulos_por_paquete.id_precio_repuesto= %s)
//			,0))) AS total_paquete
//			FROM
//			sa_paquetes
//			INNER JOIN sa_paq_unidad ON (sa_paquetes.id_paquete = sa_paq_unidad.id_paquete)
//			INNER JOIN an_uni_bas ON (sa_paq_unidad.id_unidad_basica   = an_uni_bas.id_uni_bas)",
//			valTpDato($valCadBusq[0],"int"),
//			$rowTipoOrden['precio_tempario'],
//			$rowBaseUt['valor_parametro'],
//			valTpDato($valCadBusq[0],"int"),
//			valTpDato($valCadBusq[0],"int"),
//			$rowTipoOrden['id_precio_repuesto']).$sqlBusq;
						
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Paquetes");

	$htmlTableIni .= "<table width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td width='5%'></td>";
    $htmlTh .= ordenarCampo("xajax_listado_paquetes_por_unidad", "5%", $pageNum, "codigo_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, "Codigo");
	$htmlTh .= ordenarCampo("xajax_listado_paquetes_por_unidad", "20%", $pageNum, "descripcion_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripcion");
	$htmlTh .= ordenarCampo("xajax_listado_paquetes_por_unidad", "40%", $pageNum, "duracion_aprox", $campOrd, $tpOrd, $valBusq, $maxRows, "Duracion Aprox.");
	$htmlTh .= ordenarCampo("xajax_listado_paquetes_por_unidad", "20%", $pageNum, "total_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");	
    $htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$srcIcono = "../img/iconos/ico_aceptar.gif";
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<input type=\"hidden\" id=\"hdd_id_paquete\" name=\"hdd_id_paquete\" value=\"".$row['id_paq']."\"/>"."<button id=\"btnMostrarInfoPaq\" type=\"button\" onclick=\"xajax_buscar_mano_obra_repuestos_por_paquete('".$row['id_paq']."','".utf8_encode($row['codigo_paquete'])."','".utf8_encode($row['descripcion_paquete'])."','".$row['total_paquete']."', 0, '".""."', '".""."',0);\" title=\"Seleccionar Paquete\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td>".$row['id_paq']."-".utf8_encode($row['codigo_paquete'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_paquete'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['duracion_aprox'])."</td>";
			$htmlTb .= "<td align='right'>".number_format($row['total_paquete'],2,'.',',')."</td>";
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_paquetes_por_unidad('%s','%s','%s','%s','%s');\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_paquetes_por_unidad('%s','%s','%s','%s','%s');\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listado_paquetes_por_unidad('%s','%s','%s','%s','%s')\">",
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_paquetes_por_unidad('%s','%s','%s','%s','%s');\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_paquetes_por_unidad('%s','%s','%s','%s','%s');\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"10\" class=\"divMsjError\">No se encontraron registros.</td>";
	
	$objResponse->assign("tdListadoPaquetes","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("
		$('tblListados').style.display='none';
		$('tblPaquetes').style.display='';
		$('tblPaquetes2').style.display='';
		$('tblLogoGotoSystems').style.display='none';");
	
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
			$('txtDescripcionBusq').focus();
		}
		$('divFlotante2').style.display='none';
	");
	
	return $objResponse;
}

function listado_tempario_por_paquetes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 7, $totalRows = NULL)
{
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	for ($contTemp = 0; $contTemp <= strlen($valCadBusq[6]); $contTemp++) {
		$caracterTemp = substr($valCadBusq[6], $contTemp, 1);
		
		if ($caracterTemp != "|" && $caracterTemp != "")
			$cadenaTemp .= $caracterTemp;
		else {
			$arrayObjTemp[] = $cadenaTemp;
			$cadenaTemp = "";
		}
	}
	
	if($valCadBusq[4]==0)
	{
		$checked = "checked=\"checked\"";
		$objResponse ->script("
			$('tdDivMsjInfoRpto').style.display = '';
		");

		if (strlen($valCadBusq[0]) > 0)
			$sqlBusq = sprintf(" WHERE sa_paquetes.id_empresa = %s AND sa_paquetes.id_paquete = %s AND sa_paq_unidad.id_unidad_basica = %s",
			valTpDato($valCadBusq[0],"int"),
			valTpDato($valCadBusq[1],"int"),
			valTpDato($valCadBusq[9],"int"));
		
			$query = sprintf("SELECT *
				FROM
				sa_paquetes
				INNER JOIN sa_paq_unidad ON (sa_paquetes.id_paquete = sa_paq_unidad.id_paquete)
				INNER JOIN sa_paq_tempario ON (sa_paquetes.id_paquete = sa_paq_tempario.id_paquete)
				INNER JOIN sa_tempario ON (sa_paq_tempario.id_tempario = sa_tempario.id_tempario)
				INNER JOIN sa_modo ON (sa_tempario.id_modo = sa_modo.id_modo)
				INNER JOIN sa_operadores ON (sa_tempario.operador = sa_operadores.id_operador)
			").$sqlBusq;
			$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
			
	}
	else
	{
			$checked = "checked=\"checked\"";
			
			$objResponse ->script("
				$('tdDivMsjInfoRpto').style.display = 'none';
			");
			
			if($_GET['doc_type']==1)//TIPO DE DOCUMENTO
			{
				$campoIdEnc = "id_presupuesto";
				$tablaDocDetTemp = "sa_det_presup_tempario";
				$campoTablaIdDetTemp = "id_det_presup_tempario";
			}
			else
			{
				$campoIdEnc = "id_orden";
				$tablaDocDetTemp = "sa_det_orden_tempario";
				$campoTablaIdDetTemp = "id_det_orden_tempario";			
			}
			
			if (strlen($valCadBusq[0]) > 0)
				$sqlBusq = sprintf(" WHERE %s.%s = %s AND %s.id_paquete= %s ORDER BY %s.%s",
				$tablaDocDetTemp, $campoIdEnc, valTpDato($valCadBusq[5],"int"), $tablaDocDetTemp, valTpDato($valCadBusq[1],"int"), $tablaDocDetTemp, $campoTablaIdDetTemp);
			
			$query = sprintf("SELECT 
					sa_tempario.id_tempario,
					sa_tempario.codigo_tempario,
					sa_tempario.descripcion_tempario,
					sa_tempario.id_modo,
					%s.base_ut_precio,
					sa_modo.descripcion_modo,
					%s.operador,
					sa_operadores.descripcion_operador,
					(case sa_tempario.id_modo
					when '1' then %s.ut
					when '2' then %s.precio
					when '3' then %s.costo
					when '4' then '4'
					end) AS precio,
					%s.ut,
					(case sa_tempario.id_modo
					when '1' then round((%s.ut * %s.precio_tempario_tipo_orden/%s.base_ut_precio), 2)
					when '2' then %s.precio
					when '3' then %s.costo
					when '4' then '4'
					end) AS importe_por_tipo_orden,
					(case %s.id_modo
					when '1' then %s.base_ut_precio
					when '2' then '-'
					when '3' then '-'
					when '4' then '-'
					end) AS base_ut
					FROM
					sa_modo
					INNER JOIN %s ON (sa_modo.id_modo = %s.id_modo)
					INNER JOIN sa_tempario ON (%s.id_tempario = sa_tempario.id_tempario)
					INNER JOIN sa_operadores ON (%s.operador = sa_operadores.id_operador)", 
					$tablaDocDetTemp,
					$tablaDocDetTemp,
					$tablaDocDetTemp,
					$tablaDocDetTemp,
					$tablaDocDetTemp,
					$tablaDocDetTemp,
					$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
					$tablaDocDetTemp,
					$tablaDocDetTemp,
					$tablaDocDetTemp,
					$tablaDocDetTemp,
					$tablaDocDetTemp, $tablaDocDetTemp,
					$tablaDocDetTemp,
					$tablaDocDetTemp).$sqlBusq;
					
			$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);			
	}
	
	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Paquetes");
	
	$htmlTableIni .= "<table width=\"98%\">";
	$htmlTh .= sprintf("<tr>
					<td colspan=\"10\" class=\"tituloPaginaServicios\" id=\"tdEncabPaquete\"></td>
				</tr>
				<tr>
					<td colspan=\"10\" ></td>
				</tr>
				<tr>
					 <td colspan=\"10\" class=\"tituloArea\" align=\"center\">Mano de Obra</td>
				</tr>
				<tr class=\"tituloColumna\">
					<td>".("C&oacute;digo")."</td>
					<td>".("Descripci&oacute;n")."</td>
					<td align=\"center\">Modo</td>
					<td align=\"center\">Operador</td>
					<td align=\"center\">UT</td>
					<td align=\"center\">Precio</td>
					<td align=\"center\">Base UT</td>
					<td align=\"center\">Importe</td>
					<td align=\"center\"></td>
					<td align=\"center\" id=\"tdChkManoObra\" >"."<input type=\"checkbox\" id=\"chk_tempPaq\" %s onclick=\"selecAllChecks(this.checked,this.id,12); xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'));\"/>"."</td>
            	</tr>",$checked);
	
	$sigValor = 1;
	$arrayObjTemp = NULL; 
	
	$cadenaMo=NULL;
	
	if ($valCadBusq[7] > 0)
	{
		for ($contTempAprob = 0; $contTempAprob <= strlen($valCadBusq[7]); $contTempAprob++) {
			$caracterTempAprob = substr($valCadBusq[7], $contTempAprob, 1);
			
			if ($caracterTempAprob != "," && $caracterTempAprob != "")
				$cadenaTempAprob .= $caracterTempAprob;
			else {
				$arrayObjTempAprob[] = $cadenaTempAprob;
				$cadenaTempAprob = "";
			}
		}
	}
	
	$totalMo = 0;
	if($valCadBusq[4]==0)
	{
		
		$queryTipoOrden = sprintf("SELECT 
			sa_tipo_orden.id_tipo_orden,
			sa_tipo_orden.precio_tempario
			FROM
			sa_tipo_orden
			WHERE
			sa_tipo_orden.id_tipo_orden = %s",
		   valTpDato($valCadBusq[2],"int"));
		 
		 $rsTipoOrden = mysql_query($queryTipoOrden) or die(mysql_error().$queryTipoOrden);
		 $rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
		 
		 $queryBaseUt = sprintf("SELECT 
			pg_parametros_empresas.valor_parametro
			FROM
			pg_parametros_empresas
			WHERE
			pg_parametros_empresas.descripcion_parametro = 1 AND 
			pg_parametros_empresas.id_empresa = %s",
			valTpDato($valCadBusq[0],"int"));
			
		 $rsBaseUt = mysql_query($queryBaseUt) or die(mysql_error().$queryBaseUt);
		 $rowBaseUt = mysql_fetch_assoc($rsBaseUt);
	 }
	 
	while ($row = mysql_fetch_assoc($rsLimit)) {
	
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$srcIcono = "../img/iconos/ico_aceptar.gif";
		
		if($valCadBusq[4]==1)//CARGADO EN BD
		{
			$checked = "checked=\"checked\"";
			$display = "style=\"display:none\"";
			
			$base_ut = $row['base_ut'];
			$importe_por_tipo_orden = $row['importe_por_tipo_orden'];
			
			if($row['id_modo']==1)
			{
				$precio = "";
				$ut = $row['ut'];
			}
			else
			{
				$precio = number_format($row['precio'],2,".",",");
				$ut = "";
			
			}
		}
		else// SI ES NUEVO DOC
		{
			if($valCadBusq[8] == 0 || $valCadBusq[8] == "")
			{
				$checked = "checked=\"checked\"";
			}
			else
			{
				$checked = " ";
			
				if(isset($arrayObjTempAprob)){
					foreach($arrayObjTempAprob as $indiceTempAprob => $valorTempAprob) {
						if($valorTempAprob == $row['id_tempario']) {
							$checked = "checked=\"checked\"";
						}
					}
				}
			}
			$display = "";
			
			if($row['id_modo']==1)
			{
				$query = sprintf("SELECT *
					FROM
					sa_tempario_det
					WHERE
					sa_tempario_det.id_tempario = %s AND
					sa_tempario_det.id_unidad_basica = %s", 
				valTpDato($row['id_tempario'],"int"),
				valTpDato($row['id_unidad_basica'],"int"));
								
				$rsValor = mysql_query($query)or die(mysql_error().$query);
				$rowValor = mysql_fetch_assoc($rsValor);
				
				$ut = $rowValor['ut'];
				$precio = "";
				$importe_por_tipo_orden = $rowValor['ut'] * $rowTipoOrden['precio_tempario'] / $rowBaseUt['valor_parametro'];
				$base_ut = $rowBaseUt['valor_parametro'];
				
				if($ut == '')
					$paqConTempSinPrecio = 1;
				
				$asignacion_precio_tempario = ($ut == '') ? "<img src='../img/iconos/50.png' width='16' height='16' />" : "";
			}
			else 
			{
				if($row['id_modo']==2)//PRECIO
				{
					$importe_por_tipo_orden = $row['precio'];
					$precio = number_format($row['precio'],2,".",",");
					$base_ut = $row['base_ut'];
					$ut = "";//NO ES MODO UT LE COLOCO VACIO
				}
				else
				{
					if($row['id_modo']==3)//COSTO
					{
						$importe_por_tipo_orden = $row['costo'];
						$precio = number_format($row['costo'],2,".",",");
						$base_ut = $row['base_ut'];
						$ut = "";//NO ES MODO UT LE COLOCO VACIO
					}
				}
			}
		}
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>".$row['id_tempario']."-".$row['codigo_tempario']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_tempario'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_modo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_operador'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($ut)."</td>";
			$htmlTb .= "<td align=\"center\">".$precio."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($base_ut)."</td>";
			$htmlTb .= sprintf("<td align=\"right\">".number_format($importe_por_tipo_orden,2,".",",")."<input type=\"hidden\" id=\"txtPrecTempPaq%s\" name=\"txtPrecTempPaq%s\"
			 value=\"%s\"/></td>", $row['id_tempario'], $row['id_tempario'], $importe_por_tipo_orden);
			
			 $htmlTb .= "<td align=\"center\">".$asignacion_precio_tempario."</td>";
			$htmlTb .= sprintf("<td align=\"center\" %s><input type=\"checkbox\" id=\"chk_tempPaq\" %s name=\"chk_tempPaq[]\" onclick=\"xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'));\" value=\"%s\"/><input type=\"hidden\" id=\"txtIdeTempPaq%s\" name=\"txtIdeTempPaq%s\" value=\"%s\"/></td>",$display, $checked, $row['id_tempario'], $row['id_tempario'], $row['id_tempario'], $row['id_tempario']);
			$htmlTb .= "</tr>";
	
		$arrayObjTempAprob[] = $row['id_tempario'];
		$arrayObjTemp[] = $row['id_tempario'];		
		$totalMo = $totalMo + $importe_por_tipo_orden;
		$sigValor++;
	}

	$cadenaTempAprob = "";
	if(isset($arrayObjTempAprob)){
		foreach($arrayObjTempAprob as $indiceTempAprob => $valorTempAprob) {
			$cadenaTempAprob .= "|".$valorTempAprob;
		}
		$objResponse->assign("hddManObraAproXpaq","value",$cadenaTempAprob);
	}
	
	$cadenaTemp = "";
	if(isset($arrayObjTemp)){
		foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
			$cadenaTemp .= "|".$valorTemp;
		}
		$objResponse->assign("hddObjTemparioPaq","value",$cadenaTemp);
	}
	
	if ($paqConTempSinPrecio == 1)
		$objResponse -> script("$('hddTempEnPaqSinPrecio').value = 1;");
	else
		$objResponse -> script("$('hddTempEnPaqSinPrecio').value = 0;");

	$htmlTf .= "<tr class=\"tituloColumna\">";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";//value=\"".$valBusq."\"
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario_por_paquetes(%s,'%s','%s','%s','%s');\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
				$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listado_tempario_por_paquetes(%s,'%s','%s','%s',%s)\" >",
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"10\" class=\"divMsjError\">No se encontraron registros.</td>";

	$objResponse->assign("tdListadoTempario","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	if($valCadBusq[4]==1)
		 $objResponse->script("
			$('tdChkManoObra').style.display='none';");	
	
	$objResponse->script("
		$('tblListados').style.display='none';
		$('tblPaquetes').style.display='';
		$('tblPaquetes2').style.display='';
		$('tblListadoTot').style.display='none';
		$('tblBusquedaTot').style.display='none';
		$('tblLogoGotoSystems').style.display='none';");
		
	//$objResponse->assign("txtTotalMoPaq","value",$totalMo);

	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
			$('txtDescripcionBusq').focus();
		}
	");

	$objResponse->script("
					$('tdEncabPaquete').innerHTML='PAQUETE '+$('txtCodigoPaquete').value+ ' '+$('txtDescripcionPaquete').value;
					xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'));

	");
	
	return $objResponse;
}

function listado_repuestos_por_paquetes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 7, $totalRows = NULL)
{
	$objResponse = new xajaxResponse();
	$valCadBusq = explode("|", $valBusq);
	
	$startRow = $pageNum * $maxRows;	
	
	for ($contRep = 0; $contRep <= strlen($valCadBusq[6]); $contRep++) {
		$caracterRep = substr($valCadBusq[6], $contRep, 1);
		
		if ($caracterRep != "|" && $caracterRep != "")
			$cadenaRep .= $caracterRep;
		else {
			$arrayObjRep[] = $cadenaRep;
			$cadenaRep = "";
		}
	}
	$checked = "checked=\"checked\"";

	if($valCadBusq[4]==0)
	{
		if (strlen($valCadBusq[0]) > 0) 
		$sqlBusq = sprintf(" WHERE sa_paquetes.id_empresa = %s AND sa_paquetes.id_paquete= %s",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[1],"int"));
		$query = sprintf("
			SELECT 
			  sa_paquete_repuestos.id_paquete,
			  iv_articulos.id_articulo,
			  vw_iv_articulos.codigo_articulo,
			  vw_iv_articulos.cantidad_disponible_logica,
			  sa_paquete_repuestos.cantidad,
			  vw_iv_articulos.descripcion,
			  vw_iv_articulos.marca,
			  sa_paquete_repuestos.id_paq_repuesto
			FROM
			  iv_articulos
			  INNER JOIN vw_iv_articulos ON (iv_articulos.id_articulo = vw_iv_articulos.id_articulo)
			  INNER JOIN sa_paquete_repuestos ON (iv_articulos.id_articulo = sa_paquete_repuestos.id_articulo)
			  INNER JOIN iv_marcas ON (iv_articulos.id_marca = iv_marcas.id_marca)
			  INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
			  INNER JOIN sa_paquetes ON (sa_paquete_repuestos.id_paquete = sa_paquetes.id_paquete)
		").$sqlBusq;
	}
	else
	{
		if($_GET['doc_type']==1)
		{
			$tablaEnc = "sa_presupuesto";
			$campoIdEnc = "id_presupuesto";
			$tablaDocDetArt = "sa_det_presup_articulo";
		}
		else
		{
			$tablaEnc = "sa_orden";
			$campoIdEnc = "id_orden";
			$tablaDocDetArt = "sa_det_orden_articulo";
		}
		
		$query = sprintf("SELECT 
			%s.id_det_orden_articulo,
			iv_articulos.id_articulo,
			iv_articulos.codigo_articulo,
			iv_marcas.marca,
			iv_marcas.id_marca,
			%s.cantidad,
			%s.precio_unitario AS precio,
			iv_articulos.descripcion
			FROM
			iv_marcas
			INNER JOIN iv_articulos ON (iv_marcas.id_marca = iv_articulos.id_marca)
			INNER JOIN %s ON (iv_articulos.id_articulo = %s.id_articulo)
			INNER JOIN %s ON (%s.%s = %s.%s)
			WHERE
			%s.%s = %s AND 
			%s.id_paquete = %s",
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaEnc, $tablaEnc, $campoIdEnc, $tablaDocDetArt, $campoIdEnc,
			$tablaDocDetArt, $campoIdEnc, valTpDato($valCadBusq[5],"int"),
			$tablaDocDetArt, valTpDato($valCadBusq[1],"int"));	
	}
		
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	 
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Paquetes");
	
	$htmlTableIni .= "<table width=\"98%\">";
	$htmlTh .= sprintf("<tr>
				<td colspan=\"22\" class=\"tituloArea\" align=\"center\">Repuestos</td>
				</tr>
				<tr class=\"tituloColumna\">
				<td>".("C&oacute;digo")."</td>
                <td>".("Descripci&oacute;n")."</td>
				<td>Marca</td>
                <td>Cantidad</td>
				<td>Prec/Unit</td>
				<td>Total</td>
				<td colspan=\"4\"></td>
				<td align='center' id=\"tdChkRep\" >"."<input type=\"checkbox\" id=\"chk_repPaq\" %s onclick=\"selecAllChecks(this.checked,this.id,12); xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'));\"/>"."</td>
            	</tr>", $checked);
				
	$articuloSinDisponibilidad=0;
	$sigValor = 1;
	$arrayObjRep = NULL; 
	$cadenaRe=NULL;
	
	if ($valCadBusq[7] > 0)
	{
		for ($contRepAprob = 0; $contRepAprob <= strlen($valCadBusq[7]); $contRepAprob++) {
			$caracterRepAprob = substr($valCadBusq[7], $contRepAprob, 1);
			
			if ($caracterRepAprob != "," && $caracterRepAprob != "")
				$cadenaRepAprob .= $caracterRepAprob;
			else {
				$arrayObjRepAprob[] = $cadenaRepAprob;
				$cadenaRepAprob = "";
			}
		}
	}
	
	$totalRe = 0;
	
	$montoExentoArt = 0;
	$montoArtConIva = 0;
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		
		if($valCadBusq[4]==0)
		{
			$query = sprintf("SELECT *
				FROM
				vw_iv_articulos_precios
				INNER JOIN sa_tipo_orden ON (vw_iv_articulos_precios.id_precio = sa_tipo_orden.id_precio_repuesto)
				WHERE 
				sa_tipo_orden.id_tipo_orden = %s AND vw_iv_articulos_precios.id_articulo = %s",
				valTpDato($valCadBusq[2],"int"),
				$row['id_articulo']);
			
			$rs = mysql_query($query)or die(mysql_error().$query);
			$rowPrecioArticulo = mysql_fetch_assoc($rs);
			
			$precio = $rowPrecioArticulo['precio'];
		}
		else
			$precio = $row['precio'];
		
		if($precio == 0)
			$paqConArtSinPrecio = 1;
			
		if($_GET['cons'] == 1)
		{
			//SI ESTAN EN SOLICITUD Y APROBADO
			$queryCont = sprintf("SELECT 
				COUNT(*) AS nro_rpto_desp
				FROM
				sa_det_solicitud_repuestos
				WHERE
				sa_det_solicitud_repuestos.id_det_orden_articulo = %s AND 
				sa_det_solicitud_repuestos.id_estado_solicitud = 3", 
			$row['id_det_orden_articulo']);
			
			$rsCont = mysql_query($queryCont);
			$rowCont = mysql_fetch_assoc($rsCont);

			if (!$rsCont) return $objResponse->alert(mysql_error().$rsCont);
			else
			{
				if($rowCont['nro_rpto_desp'] != NULL || $rowCont['nro_rpto_desp'] != "")
					$cantidad_art = $rowCont['nro_rpto_desp'];
				else
					$cantidad_art = 0;
			}	
		}
		else
			$cantidad_art = $row['cantidad'];	
			
		$asignacion_precio_articulo = ($precio == 0) ? "<img src='../img/iconos/50.png' width='16' height='16' />" : "";
		
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		
		if($valCadBusq[4]==1)
		{
			$checked = "checked=\"checked\"";
			$display = "style=\"display:none\"";
			
		}
		else
		{			
			if($valCadBusq[8] == 0 || $valCadBusq[8] == "")
				$checked = "checked=\"checked\"";
			else
			{			
				$checked = " ";
				if(isset($arrayObjRepAprob)){
					foreach($arrayObjRepAprob as $indiceRepAprob => $valorRepAprob) {
						if($valorRepAprob == $row['id_articulo']) {
							$checked = "checked=\"checked\"";
						}
					}
				}
			}
			$display = "style=' '";
		}
		
		$cadenaRe = $cadenaRe."|".$row['id_articulo'];
		
                //si el articulo posee iva:
                $QueryPoseeIva = sprintf("SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = %s LIMIT 1",
                                        valTpDato($row['id_articulo'], "int"));
                $rsPoseeIva = mysql_query($QueryPoseeIva);
                $poseeIva = mysql_num_rows($rsPoseeIva);
                
		if($poseeIva != ""){
			$montoArtConIva += doubleval($cantidad_art * $precio);
                        $srcIconoExento = "";
                }else{
			$montoExentoArt += doubleval($cantidad_art * $precio);
                        $srcIconoExento =  "<img src='../img/iconos/e_icon.png' />";
                }
                        
		if ($row['cantidad_disponible_logica'] == "" || $row['cantidad_disponible_logica'] <= 0)
		{
			$srcIcono = "../img/iconos/ico_error.gif";
			$articuloSinDisponibilidad=1;
			$artPaqNoDisp = $artPaqNoDisp."|".$row['id_articulo'];
		}
		else if ($row['cantidad_disponible_logica'] > 5)
			$srcIcono = "../img/iconos/ico_aceptar.gif";
		else if ($row['cantidad_disponible_logica'] <= 5)
			$srcIcono = "../img/iconos/ico_alerta.gif";
			
		$htmlTb.= "<tr class=\"".$clase."\">";
			
			$htmlTb .= "<td>".$row['id_articulo']."-".$row['codigo_articulo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca'])."</td>";
			$htmlTb .= "<td align='center'>".$cantidad_art."</td>";
			$htmlTb .= "<td align='center'>".number_format($precio,2,".",",")."</td>";
			$htmlTb .= sprintf("<td align='right'>".number_format($cantidad_art*$precio,2,".",",")."<input type=\"hidden\" id=\"txtPrecRepPaq%s\" name=\"txtPrecRepPaq%s\"
			 value=\"%s\"/></td>", $row['id_articulo'], $row['id_articulo'], $cantidad_art*$precio);
                        
                        $htmlTb .= "<td align='center'>".$asignacion_precio_articulo."</td>";
                        $htmlTb .= sprintf("<td align='center' id='tdDisponibilidadRep' %s><img src='%s'/></td>",$display,$srcIcono);

                        $htmlTb .= "<td align=\"center\" id=\"tdPoseeIvaRep\">".$srcIconoExento."</td>";
                        $htmlTb .= "<td align=\"center\" ".$display.">";

			$htmlTb .= sprintf("<td align='center' %s><input type=\"checkbox\" id=\"chk_repPaq\" %s  name=\"chk_repPaq[]\"  onclick=\"xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'));\" value=\"%s\"/><input type=\"hidden\" id=\"txtIdeRepPaq%s\" name=\"txtIdeRepPaq%s\"
			 value=\"%s\"/></td>",$display, $checked, $row['id_articulo'], $row['id_articulo'], $row['id_articulo'], $row['id_articulo']);
			$htmlTb .= "</tr>";
			
		$arrayObjRepAprob[] = $row['id_articulo'];
		$arrayObjRep[] = $row['id_articulo'];
		$totalRe = $totalRe + ($row['cantidad']*$precio);

		$sigValor++;
		
	}	
	
	$objResponse->assign("hddTotalArtExento","value", $montoExentoArt);
	$objResponse->assign("hddTotalArtConIva","value", $montoArtConIva);
	
	
	$cadenaRepAprob = "";
	if(isset($arrayObjRepAprob)){
		foreach($arrayObjRepAprob as $indiceRepAprob => $valorRepAprob) {
			$cadenaRepAprob .= "|".$valorRepAprob;
		}
		$objResponse->assign("hddRepAproXpaq","value",$cadenaRepAprob);
	}
	
	$cadenaRep = "";
	if(isset($arrayObjRep)){
		foreach($arrayObjRep as $indiceRep => $valorRep) {
			$cadenaRep .= "|".$valorRep;
		}
		$objResponse->assign("hddObjRepuestoPaq","value",$cadenaRep);
	}	
	
	if ($paqConArtSinPrecio == 1)
		$objResponse -> script("$('hddArtEnPaqSinPrecio').value = 1;");
	else
		$objResponse -> script("$('hddArtEnPaqSinPrecio').value = 0;");

	
	$htmlTf .= "<tr class=\"tituloColumna\">";
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_repuestos_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
				
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_repuestos_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listado_repuestos_por_paquetes(%s,'%s','%s', '%s', %s);\" >",
						"this.value", $campOrd, $tpOrd, $valBusq,  $maxRows);
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
					
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_repuestos_por_paquetes(%s,'%s','%s','%s',%s);\">%s</a>",
							min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_repuestos_por_paquetes(%s,'%s','%s','%s', %s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
		
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"10\" class=\"divMsjError\">No se encontraron registros.</td>";
	
	$objResponse->assign("tdListadoRepuestos","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	if($valCadBusq[4]==1)
	 $objResponse->script("
				$('tdDisponibilidadRep').style.display='none';
				$('tdChkRep').style.display='none';");	
				
	//$objResponse->assign("txtTotalRePaq","value",$totalRe);
	
	$objResponse->script("
		
	    $('hddArticuloSinDisponibilidad').value='$articuloSinDisponibilidad';
		$('hddArtNoDispPaquete').value = '$artPaqNoDisp';
		$('tblListados').style.display='none';
		$('tblPaquetes').style.display='';
		$('tblPaquetes2').style.display='';

		$('trPieTotalPaq').style.display='';
		$('tblLogoGotoSystems').style.display='none';
		xajax_calcularTotalPaquete(xajax.getFormValues('frmDatosPaquete'));");

	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
			$('txtDescripcionBusq').focus();
		}
	");
	
	return $objResponse;
}

function buscar_mano_obra_repuestos_por_paquete($idPaquete,$codigoPaquete,$descripcionPaquete,$precio_paquete,$origen, $itemManoObraAprob, $itemRepAprob, $accionVistaPaquete){
	$objResponse = new xajaxResponse();
		
	$itemManoObraAprob2 = $itemManoObraAprob;
	$itemRepAprob2 = $itemRepAprob;
	
	if($itemManoObraAprob == "")//CONTROLAR SI SE VA AGREGAR UN PAQ O SE VA A VISUALIZAR
		$objResponse -> script("
			$('btnCancelarDivSecPaq').style.display='';
			$('btnCancelarDivPpalPaq').style.display='none';
			$('btnAsignarPaquete').style.display='';");
	else
		$objResponse -> script("
			$('btnCancelarDivSecPaq').style.display='none';
			$('btnCancelarDivPpalPaq').style.display='';
			$('btnAsignarPaquete').style.display='none';");
	
	if($itemManoObraAprob2!="")
	{
		$itemManoObraAprob2[0] = ' ';
		$itemManoObraAprob2 = str_replace("|",",",$itemManoObraAprob2);
	}
	else
	{
		$itemManoObraAprob2 = 0;
	}
	if($itemRepAprob2!="")
	{
		$itemRepAprob2[0] = ' ';
		$itemRepAprob2 = str_replace("|",",",$itemRepAprob2);
	}
	else
	{
		$itemRepAprob2 = 0;
	
	}
		
	$objResponse->script("	
			$('tdListadoTempario').style.display = '';
			$('tdListadoRepuestos').style.display = '';	
			
			$('tdListadoPaquetes').style.display='none';
			$('tblBusquedaPaquete').style.display='none';
				
			var arrayTempaPaq = $('hddObjTemparioPaq').value;
			arrayTempaPaq = arrayTempaPaq.substring(1);
			var arrayTempaPaqComp = arrayTempaPaq.split('|');
			
			var arrayRepPaq = $('hddObjRepuestoPaq').value;
			arrayRepPaq = arrayRepPaq.substring(1);
			var arrayRepPaqComp = arrayRepPaq.split('|');
			
			var arrayMaObAp = [".$itemManoObraAprob2."];
			var arrayReAp = [".$itemRepAprob2."];
		
			$('txtDescripcionPaquete').style.display='';
			$('txtCodigoPaquete').style.display='';
			$('tblListadoTemparioPorPaquete').style.display='';
			$('tblListadoRepuestosPorPaquete').style.display='';
			$('txtDescripcionPaquete').value='".$descripcionPaquete."';
			$('txtCodigoPaquete').value='".$codigoPaquete."';
			$('hddEscogioPaquete').value='".$idPaquete."';
			
			//alert(JSON.stringify(OBJETO AQUI, null, 4));
			
			xajax_listado_tempario_por_paquetes('0','','',$('txtIdEmpresa').value + '|".$idPaquete."|' + $('lstTipoOrden').value + '|' + $('hddAccionTipoDocumento').value + '|".$origen."|' + $('txtIdPresupuesto').value + '|' + arrayTempaPaqComp + '|' + arrayMaObAp + '|".$accionVistaPaquete."|' + $('hddIdUnidadBasica').value);
			
			xajax_listado_repuestos_por_paquetes('0','','', $('txtIdEmpresa').value + '|".$idPaquete."|' + $('lstTipoOrden').value + '|' + $('hddAccionTipoDocumento').value + '|".$origen."|' + $('txtIdPresupuesto').value  + '|' + arrayRepPaqComp + '|' + arrayReAp + '|".$accionVistaPaquete."');");
		
		$objResponse->assign("hddEscogioPaquete","value",$idPaquete);
	return $objResponse;	
}

function insertarDescuento($valFormPaq, $valFormArt, $valFormTemp, $valFormTot, $valFormNota, $valFormTotalDcto, $valFormDctoAgregar){

		$objResponse = new xajaxResponse();
	
			for ($cont = 0; $cont <= strlen($valFormTotalDcto['hddObjDescuento']); $cont++) {
				$caracter = substr($valFormTotalDcto['hddObjDescuento'], $cont, 1);
							
				if ($caracter != "|" && $caracter != "")
					$cadena .= $caracter;
				else {
					$arrayObjDcto[] = $cadena;
					$cadena = "";
				}	
			}
		
			$sigValor = $arrayObjDcto[count($arrayObjDcto)-1] + 1;
				
			$sw = 0;
			foreach($arrayObjDcto as $indice => $valor) {
				if ($valFormTotalDcto['hddIdDcto'.$valor] != "")
				{
					if($valFormTotalDcto['hddIdDcto'.$valor] == $valFormDctoAgregar['lstTipoDescuentos'])
						$sw = 1;
				}
			}
			
			if($sw == 1)
				$objResponse ->alert("El Descuento que desea agregar ya se encuentra en la lista. Escoja otro.");
			else
			{
					$objResponse -> script(sprintf("
					var elemento = new Element('tr', {'id':'trItmDcto:%s', 'class':'textoGris_11px', 'title':'trItmDcto:%s'}).adopt([
						new Element('td', {'align':'right', 'id':'tdItmDcto:%s', 'class':'tituloCampo'}).setHTML(\"%s\"),
						new Element('td', {'align':'center'}).setHTML(\"%s\"),
						new Element('td', {'align':'right'}).setHTML(\"<input type='text' id='hddPorcDcto%s' name='hddPorcDcto%s' size='6' style='text-align:right' readonly='readonly' value='%s'/>%s\"),
						new Element('td', {'align':'right'}).setHTML(\"<img id='imgElimDcto:%s' name='imgElimDcto:%s' src='../img/iconos/ico_quitar.gif' class='puntero noprint' title='Porcentaje Adcl:%s' />".
						"<input type='hidden' id='hddIdDetDcto%s' name='hddIdDetDcto%s' value='%s'/><input type='hidden' id='hddIdDcto%s' name='hddIdDcto%s' value='%s'/>\"),
						new Element('td', {'align':'right' , 'id':'tdItmNotaAprob:%s'}).setHTML(\"<input type='text' id='txtTotalDctoAdcl%s' name='txtTotalDctoAdcl%s' readonly='readonly' style='text-align:right' size='18'/>\")]);
					elemento.injectBefore('trm_pie_dcto');
					
					$('imgElimDcto:%s').onclick=function(){
						xajax_eliminarDescuentoAdicional(%s);			
					}
					",
					$sigValor, $sigValor,
					$sigValor, $valFormDctoAgregar['txtDescripcionPorcDctoAdicional'].":",
					"",
					$sigValor, $sigValor, $valFormDctoAgregar['txtPorcDctoAdicional'], "%",
					$sigValor, $sigValor, $sigValor,
					$sigValor, $sigValor, "",
					$sigValor, $sigValor, $valFormDctoAgregar['lstTipoDescuentos'],
					$sigValor, 
					$sigValor, $sigValor,
					$sigValor,
					$sigValor));
					
					$arrayObjDcto[] = $sigValor;
					foreach($arrayObjDcto as $indice => $valor) {
						$cadena = $valFormTotalDcto['hddObjDescuento']."|".$valor;
					}
				
					$objResponse->assign("hddObjDescuento","value",$cadena);
						
					$objResponse->script("
					$('divFlotante2').style.display='none';
					$('divFlotante').style.display='none';");
						
					$objResponse->script("xajax_calcularTotalDcto();");	
			}
		return $objResponse;
}

function insertarPaquete($valForm, $valFormTotalDcto, $valFormListPaq, $valFormDcto) {

		$objResponse = new xajaxResponse();
	
		/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
		for ($cont = 0; $cont <= strlen($valFormTotalDcto['hddObjPaquete']); $cont++) {
			$caracter = substr($valFormTotalDcto['hddObjPaquete'], $cont, 1);
						
			if ($caracter != "|" && $caracter != "")
				$cadena .= $caracter;
			else {
				$arrayObj[] = $cadena;
				$cadena = "";
			}	
		}

		$sw = 0;
		
		foreach($arrayObj as $indice => $valor) {
			if ($valFormListPaq['hddIdPaq'.$valor]!= "")
			{
				if($valFormListPaq['hddIdPaq'.$valor] == $valForm['hddEscogioPaquete'])
					$sw = 1;
			}
		}
		if($sw == 1)
			$objResponse ->alert("El Paquete que desea agregar ya se encuentra en la lista. Escoja otro.");
		else
		{
				if($valForm['hddArticuloSinDisponibilidad']==1)			
					$estado_paquete = "<img  src='../img/iconos/ico_alerta.gif' title='Contiene Articulos sin disponibilidad' />";
				else
					$estado_paquete = "<img  src='../img/iconos/ico_aceptar.gif' title='Los repuestos del paquete tienen stock' />";
				
				$sigValor = $arrayObj[count($arrayObj)-1] + 1;
				
				$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
				$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItmPaq:%s', 'class':'textoGris_11px %s' , 'title':'trItmPaq:%s'}).adopt([
					new Element('td', {'align':'center', 'id':'tdItmPaq:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmPaq' name='cbxItmPaq[]' type='checkbox' value='%s' />\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\" %s <img id='img:%s' src='../img/iconos/ico_view.png' id='imgFechaVencimientoPresupuesto' name='imgFechaVencimientoPresupuesto' class='puntero noprint' title='Paquete:%s' />".
					"<input type='hidden' id='hddIdPedDetPaq%s' name='hddIdPedDetPaq%s' value='%s'/><input type='hidden' id='hddIdPaq%s' name='hddIdPaq%s' value='%s'/><input type='hidden' id='hddCodPaq%s' name='hddCodPaq%s' value='%s'/><input type='hidden' id='hddDesPaq%s' name='hddDesPaq%s' value='%s'/><input type='hidden' id='hddIvaPaq%s' name='hddIvaPaq%s' value='%s'/><input type='hidden' id='hddPrecPaq%s' name='hddPrecPaq%s' value='%s'/><input type='hidden' id='hddManoObraAproPaq%s' name='hddManoObraAproPaq%s' value='%s'/><input type='hidden' id='hddRepPaqAsig%s' name='hddRepPaqAsig%s' value='%s'/><input type='hidden' id='hddTempPaqAsig%s' name='hddTempPaqAsig%s' value='%s'/><input type='hidden' id='hddRepPaqAsigEdit%s' name='hddRepPaqAsigEdit%s' value='%s'/><input type='hidden' id='hddTempPaqAsigEdit%s' name='hddTempPaqAsigEdit%s' value='%s'/><input type='hidden' id='hddMecAsig%s' name='hddMecAsig%s' value='%s'/>\"),
					new Element('td', {'align':'center', 'id':'tdItmPaqAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmPaqAprob' name='cbxItmPaqAprob[]' type='checkbox' value='%s' checked='checked' onclick='xajax_calcularTotalDcto();' /><input type='hidden' id='hddValorCheckAprobPaq%s' name='hddValorCheckAprobPaq%s'/><input type='hidden' id='hddRptoPaqEnSolicitud%s' name='hddRptoPaqEnSolicitud%s' value='%s'/><input type='hidden' id='hddTotalRptoPqte%s' name='hddTotalRptoPqte%s' value='%s'/><input type='hidden' id='hddTotalTempPqte%s' name='hddTotalTempPqte%s' value='%s'/><input type='hidden' id='hddTotalExentoRptoPqte%s' name='hddTotalExentoRptoPqte%s' value='%s'/><input type='hidden' id='hddTotalConIvaRptoPqte%s' name='hddTotalConIvaRptoPqte%s' value='%s'/>\")
				]);	
				elemento.injectBefore('trm_pie_paquete');
				
				$('img:%s').onclick=function(){
					xajax_buscar_mano_obra_repuestos_por_paquete('%s','%s','%s','%s','%s','%s','%s',1);
					$('tdListadoPaquetes').style.display='none';
					$('tblBusquedaPaquete').style.display='none';
					$('btnAsignarPaquete').style.display='none';
					
					$('tdHrTblPaquetes').style.display='';
					$('tdListadoTemparioPorUnidad').style.display='none';
					$('tblListadoTempario').style.display='none'; 
					$('tblTemparios').style.display='none';
					$('tdBtnAccionesPaq').style.display='';
					$('tblNotas').style.display='none';
					$('tblListadoRepuestosPorPaquete').style.display='';
					$('tdListadoArticulos').style.display='none';
					$('tblArticulo').style.display='none';
					$('tblGeneralPaquetes').style.display='';
					$('tblListadoTempario').style.display='none';  
					$('tblListadoTemparioPorPaquete').style.display='';
					$('tblPaquetes2').style.display='';			
				}",
				$sigValor, $clase, $sigValor,
				$sigValor, $sigValor,
				$valForm['txtCodigoPaquete'],
				$valForm['txtDescripcionPaquete'],
				$valForm['txtPrecioPaquete'],
				$estado_paquete, $sigValor, $valForm['hddEscogioPaquete'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $valForm['hddEscogioPaquete'],
				$sigValor, $sigValor, $valForm['txtCodigoPaquete'],
				$sigValor, $sigValor, $valForm['txtDescripcionPaquete'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor,str_replace(",","",$valForm['txtPrecioPaquete']),
				$valForm['hddEscogioPaquete'], $valForm['hddEscogioPaquete'], "",
				$sigValor, $sigValor, $valForm['hddRepAproXpaq'],
				$sigValor, $sigValor, $valForm['hddManObraAproXpaq'],
				$valForm['hddEscogioPaquete'], $valForm['hddEscogioPaquete'], $valForm['hddRepAproXpaq'],
				$valForm['hddEscogioPaquete'], $valForm['hddEscogioPaquete'], $valForm['hddManObraAproXpaq'],
				$valForm['hddEscogioPaquete'], $valForm['hddEscogioPaquete'], $valForm['hddManObraAproXpaq'],
				$sigValor, $sigValor,
				$sigValor, $sigValor,
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, str_replace(",","",$valForm['txtTotalRepPaq']),
				$sigValor, $sigValor, str_replace(",","",$valForm['txtTotalManoObraPaq']),
				$sigValor, $sigValor, str_replace(",","",$valForm['hddTotalArtExento']),
				$sigValor, $sigValor, str_replace(",","",$valForm['hddTotalArtConIva']),
				$sigValor,
				$valForm['hddEscogioPaquete'],$valForm['txtCodigoPaquete'],$valForm['txtDescripcionPaquete'],$valForm['txtPrecioPaquete'], 0, $valForm['hddManObraAproXpaq'], $valForm['hddRepAproXpaq']));
					
				if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
					$objResponse->script(sprintf("
						$('tdInsElimPaq').style.display = 'none';
						$('tdItmPaq:%s').style.display = 'none';
						$('tdItmPaqAprob:%s').style.display='none';",
						$sigValor,
						$sigValor));	
				else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
					$objResponse->script(sprintf("
						$('tdInsElimPaq').style.display = 'none'; 
						$('tdItmPaq:%s').style.display = 'none'; 
						$('tdItmPaqAprob:%s').style.display='';",
						$sigValor,
						$sigValor));
						else if($valFormTotalDcto['hddAccionTipoDocumento']==1)
							$objResponse->script(sprintf("
							$('tdInsElimPaq').style.display = '';
							$('tdItmPaq:%s').style.display = '';
							$('tdItmPaqAprob:%s').style.display='none';",
							$sigValor,
							$sigValor));	
				
				$arrayObj[] = $sigValor;
				foreach($arrayObj as $indice => $valor) {
					$cadena = $valFormTotalDcto['hddObjPaquete']."|".$valor;
				}
				
				$objResponse->assign("hddObjPaquete","value",$cadena);
							
				$objResponse->script("
				$('tblListados').style.display='none';
				$('tblArticulo').style.display='none';
				$('tblLogoGotoSystems').style.display='';
				$('divFlotante2').style.display='none';
				$('divFlotante').style.display='none';
				xajax_calcularTotalDcto();");				
	}

	return $objResponse;
}

function cargaLstTipoOrden($selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM sa_tipo_orden WHERE nombre_tipo_orden <> 'SIN ASIGNAR' AND id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']." ORDER BY nombre_tipo_orden ");
	//$rs = mysql_query($query) or die(mysql_error().$query);
	$rs = mysql_query($query);
	if(!$rs){
		return $objResponse->alert("Error seleccionando el tipo de orden para el listado. \n Error: ".mysql_error()." \n Nº Error: ".mysql_errno()." \n Query: ".$query." \n Archivo: ".__FILE__." \n Linea: ".__LINE__."");	
	}
	
	$html = "<select id=\"lstTipoOrden\" name=\"lstTipoOrden\" onchange=\"xajax_verificarTipoOrdenPorVale(xajax.getFormValues('frmPresupuesto'),this.value);
	if($('hddItemsCargados').value > 0){
				if($('hddOrdenEscogida').value != this.value)
				{
					alert('El Documento tiene items cargados con un tipo de orden. Si desea cambiarla, elimine los items cargados');
					xajax_cargaLstTipoOrden($('hddOrdenEscogida').value);
				}
			}
			else
				$('hddOrdenEscogida').value = this.value;
				
			if($('txtTotalPresupuesto').value == 0)	
			{
				
		xajax_calcularDcto(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'),xajax.getFormValues('frm_agregar_paq'),xajax.getFormValues('frmListaManoObra'),xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmListaTot'));
			
			
			}\">";
		$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_tipo_orden'])
		{
			$seleccion = "selected='selected'";
			$tipo_orden = $row['nombre_tipo_orden'];
		}
		
		$html .= "<option value=\"".$row['id_tipo_orden']."\" ".$seleccion.">".utf8_encode($row['nombre_tipo_orden'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoOrden","innerHTML",$html);
	
	if($selId != "")
	{
		$objResponse->assign("txtDescripcionTipoOrden","value", $tipo_orden);
		$objResponse -> script("
		 if($('hddAccionTipoDocumento').value != 1)
		 {
			$('tdlstTipoOrden').style.display='none';
			$('tdDescripcionTipoOrden').style.display='';
		 }");
	}
	else
	{
		$objResponse -> script("
			$('tdlstTipoOrden').style.display='';
			$('tdDescripcionTipoOrden').style.display='none';");
	}
	
	return $objResponse;
}

function verificarCliente($valFormDcto, $idTipoOrden)
{
	$objResponse = new xajaxResponse();
	
	if($valFormDcto['txtIdCliente']=='')
	{
		$objResponse -> alert('Escoja el Cliente');
		$objResponse->script("
			$('lstTipoOrden').value = '-1';
			$('btnInsertarCliente').focus();");
	}
	
	return $objResponse;
}

function listado_tot($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL)
{
	$objResponse = new xajaxResponse();
	$valCadBusq = explode("|", $valBusq);
	
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0)
	{
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf(" vw_sa_orden_tot.estatus = 1 AND vw_sa_orden_tot.id_empresa = %s AND vw_sa_orden_tot.id_orden_servicio= %s)",
		valTpDato($valCadBusq[0],"int"),
		valTpDato($valCadBusq[1],"int"));
	}

	if ($valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("vw_sa_orden_tot.id_orden_servicio LIKE %s OR vw_sa_orden_tot.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[3]."%","text"),
			valTpDato("%".$valCadBusq[3]."%","text"));
	}
	
	$query = sprintf("SELECT *
		FROM
		vw_sa_orden_tot").$sqlBusq;
		
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Trabajo Otros Talleres (T.O.T)");
	
	$htmlTableIni .= "<table width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td ></td>
            	<td >".utf8_encode("Nro. T.O.T")."</td>
                <td >".utf8_encode("Fecha")."</td>
				<td  align='center'>".utf8_encode("Proveedor")."</td>
			    <td  align='center'>".utf8_encode("Nro. Factura")."</td>
				<td  align='center'>".utf8_encode("Tipo Pago")."</td>
				<td align='right'>".utf8_encode("Monto")."</td>
            	</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		
		$srcIcono = "../img/iconos/ico_aceptar.gif";
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarTot('".$row['id_orden_tot']."');\" title=\"Seleccionar T.O.T\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td>".$row['id_orden_tot']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['fecha_factura_proveedor'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['numero_factura_proveedor'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['tipo_pago'])."</td>";
			$htmlTb .= "<td align='right'>".utf8_encode(number_format($row['monto_total'],2,".",","))."</td>";
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
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
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"10\" class=\"divMsjError\">No se encontraron registros.</td>";
	
	$objResponse->assign("tdListadoTot","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	
	$objResponse->script("
		$('tblListados').style.display='none';
		$('tblPaquetes').style.display='';
		$('tblPaquetes2').style.display='';

		$('tblLogoGotoSystems').style.display='none';");
	
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
			$('txtDescripcionBusq').focus();
		}
		$('divFlotante2').style.display='none';
	");
	return $objResponse;
}
function cargaLstSeccionTemp($selId = "") {
	$objResponse = new xajaxResponse();
			
	$query = "SELECT *
		FROM
		sa_seccion
		ORDER BY sa_seccion.descripcion_seccion";
			
	$rs = mysql_query($query) or die(mysql_error().$query);
	$html = "<select id=\"lstSeccionTemp\" name=\"lstSeccionTemp\" onchange=\"xajax_cargaLstSubseccionTemp(this.value); $('btnBuscarTempario').click();\">";
		$html .= "<option value=\"\">Todos...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_seccion']."\">".utf8_encode($row['descripcion_seccion'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdListSeccionTemp","innerHTML",$html);
	
	return $objResponse;
}
function cargaLstSubseccionTemp($selId = "") {

	$objResponse = new xajaxResponse();

	$query = sprintf("
		SELECT 
		*
		FROM
		sa_subseccion
		WHERE sa_subseccion.id_seccion = %s ORDER BY sa_subseccion.descripcion_subseccion",  valTpDato($selId,"int"));
			
	$rs = mysql_query($query) or die(mysql_error().$query);
	$html = "<select id=\"lstSubseccionTemp\" name=\"lstSubseccionTemp\" onchange=\"$('btnBuscarTempario').click();\">";
		$html .= "<option value=\"\">Todos...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_subseccion']."\">".utf8_encode($row['descripcion_subseccion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdListSubseccionTemp","innerHTML",$html);
	
	return $objResponse;
}

function listado_tempario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL)
{
	$objResponse = new xajaxResponse();
	$valCadBusq = explode("|", $valBusq);

	$startRow = $pageNum * $maxRows;

	$queryTipoOrden = sprintf("SELECT
		sa_tipo_orden.id_tipo_orden,
		sa_tipo_orden.precio_tempario
		FROM
		sa_tipo_orden
		WHERE
		sa_tipo_orden.id_tipo_orden = %s",
	   valTpDato($valCadBusq[2],"int"));

	 $rsTipoOrden = mysql_query($queryTipoOrden) or die(mysql_error().$queryTipoOrden);
	 $rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);

	 $queryBaseUt = sprintf("SELECT
		pg_parametros_empresas.valor_parametro
		FROM
		pg_parametros_empresas
		WHERE
		pg_parametros_empresas.descripcion_parametro = 1 AND
		pg_parametros_empresas.id_empresa = %s",
		valTpDato($valCadBusq[0],"int"));

	 $rsBaseUt = mysql_query($queryBaseUt) or die(mysql_error().$queryBaseUt);
	 $rowBaseUt = mysql_fetch_assoc($rsBaseUt);

	 $queryTempDiagnostico = sprintf("SELECT *
			FROM
			pg_parametros_empresas
			WHERE
			pg_parametros_empresas.id_empresa = %s AND
			pg_parametros_empresas.descripcion_parametro = 'MANO OBRA DIAGNOSTICO'", $_SESSION['idEmpresaUsuarioSysGts']);

		$rsTempDiagnostico = mysql_query($queryTempDiagnostico) or die(mysql_error().$queryTempDiagnostico);
	$rowTempDiagnostico = mysql_fetch_assoc($rsTempDiagnostico);

	if (strlen($valCadBusq[0]) > 0)
		$sqlBusq = sprintf(" WHERE vw_sa_temparios_por_unidad.id_unidad_basica= %s",
		valTpDato($valCadBusq[1],"int"));// valTpDato($valCadBusq[2],"int")

	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf(" vw_sa_temparios_por_unidad.codigo_tempario LIKE %s OR vw_sa_temparios_por_unidad.descripcion_tempario LIKE %s)",
				valTpDato("%".$valCadBusq[3]."%","text"),
				valTpDato("%".$valCadBusq[3]."%","text"));
	}

	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf(" vw_sa_temparios_por_unidad.id_seccion = %s)",
				valTpDato($valCadBusq[4],"text"));
	}

	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf(" vw_sa_temparios_por_unidad.id_subseccion = %s)",
				valTpDato($valCadBusq[5],"text"));
	}

	$query = sprintf("SELECT
		vw_sa_temparios_por_unidad.id_tempario_det,
		vw_sa_temparios_por_unidad.id_tempario,
		vw_sa_temparios_por_unidad.codigo_tempario,
		vw_sa_temparios_por_unidad.descripcion_tempario,
		vw_sa_temparios_por_unidad.id_modo,
		vw_sa_temparios_por_unidad.id_unidad_basica,
		vw_sa_temparios_por_unidad.importe_por_tipo_orden,
		(case vw_sa_temparios_por_unidad.`id_modo`
			  when '1' then vw_sa_temparios_por_unidad.precio_por_tipo_orden * %s / %s
			  when '2' then vw_sa_temparios_por_unidad.precio_por_tipo_orden
			  when '3' then vw_sa_temparios_por_unidad.precio_por_tipo_orden
			  when '4' then vw_sa_temparios_por_unidad.precio_por_tipo_orden
		end) AS total_por_tipo_orden,
		vw_sa_temparios_por_unidad.precio_por_tipo_orden,
		vw_sa_temparios_por_unidad.base_ut,
		vw_sa_temparios_por_unidad.ut,
		vw_sa_temparios_por_unidad.precio,
		vw_sa_temparios_por_unidad.total_importe,
		vw_sa_temparios_por_unidad.descripcion_modo,
		vw_sa_temparios_por_unidad.operador,
		vw_sa_temparios_por_unidad.descripcion_operador,
		vw_sa_temparios_por_unidad.costo,
		vw_sa_temparios_por_unidad.descripcion_subseccion,
		vw_sa_temparios_por_unidad.descripcion_seccion,
		vw_sa_temparios_por_unidad.abreviatura_seccion,
		vw_sa_temparios_por_unidad.id_seccion,
		vw_sa_temparios_por_unidad.id_subseccion
		FROM
		vw_sa_temparios_por_unidad",
		$rowTipoOrden['precio_tempario'],
		$rowBaseUt['valor_parametro']).$sqlBusq;

	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$objResponse->assign("tdFlotanteTitulo","innerHTML","Mano de Obra");

	$htmlTableIni .= "<table width=\"98%\">";

	$htmlTh .= "<tr class=\"tituloColumna\">
				<td></td>
            	<td width='10%' align='center'>Código</td>
                <td width='27%' align='center'>Descripción</td>
				<td width='20%' align='center'>Sección</td>
				<td width='10%' align='center'>Subsección</td>
				<td width='5%' align='center'>Modo</td>
			    <td width='10%' align='center'>Operador</td>
				<td width='8%' align='center'>Precio</td>
				<td width='10%' align='center'>Total</td>
            	</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";

		$srcIcono = "../img/iconos/ico_aceptar.gif";

		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<input type=\"hidden\" id=\"hdd_id_paquete\" name=\"hdd_id_paquete\" value=\"".$row['id_paq']."\"/>"."<button type=\"button\" onclick=\"xajax_asignarTempario('".$row['id_tempario_det']."',xajax.getFormValues('frmPresupuesto'));\" title=\"Seleccionar Tempario\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td align='center'>".$row['codigo_tempario']."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['descripcion_tempario'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['descripcion_seccion'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['descripcion_subseccion'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['descripcion_modo'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['descripcion_operador'])."</td>";
			$htmlTb .= "<td align='center'>".utf8_encode($row['precio_por_tipo_orden'])."</td>";
			$htmlTb .= "<td align='right'>".utf8_encode(number_format($row['total_por_tipo_orden'],2,".",","))."</td>";
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";

					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listado_tempario(%s,'%s','%s','%s',%s)\">",
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
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";

	$htmlTableFin .= "</table>";

	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"10\" class=\"divMsjError\">No se encontraron registros.</td>";

	$objResponse->assign("tdListadoTemparioPorUnidad","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);

	$objResponse->script("
		$('tblListados').style.display='none';
		$('tblPaquetes').style.display='';
						$('tblPaquetes2').style.display='';
		$('tblLogoGotoSystems').style.display='none';");

	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
			$('txtDescripcionBusq').focus();
		}
		$('divFlotante2').style.display='none';");

	return $objResponse;


}
function asignarTempario($idTemparioDet, $valFormDcto) {
	$objResponse = new xajaxResponse();
	
	$queryTipoOrden = sprintf("SELECT 
		sa_tipo_orden.id_tipo_orden,
		sa_tipo_orden.precio_tempario
		FROM
		sa_tipo_orden
		WHERE
		sa_tipo_orden.id_tipo_orden = %s",
	   valTpDato($valFormDcto['lstTipoOrden'], "int"));
	 
	 $rsTipoOrden = mysql_query($queryTipoOrden) or die(mysql_error().$queryTipoOrden);
	 $rowTipoOrden = mysql_fetch_assoc($rsTipoOrden);
	 
	 $queryBaseUt = sprintf("SELECT 
		pg_parametros_empresas.valor_parametro
		FROM
		pg_parametros_empresas
		WHERE
		pg_parametros_empresas.descripcion_parametro = 1 AND 
		pg_parametros_empresas.id_empresa = %s",
		valTpDato($valFormDcto['txtIdEmpresa'],"int"));
		
	 $rsBaseUt = mysql_query($queryBaseUt) or die(mysql_error().$queryBaseUt);
	 $rowBaseUt = mysql_fetch_assoc($rsBaseUt);
	
	$queryTempario = sprintf("SELECT 
		vw_sa_temparios_por_unidad.id_tempario_det,
		vw_sa_temparios_por_unidad.id_tempario,
		vw_sa_temparios_por_unidad.codigo_tempario,
		vw_sa_temparios_por_unidad.descripcion_tempario,
		vw_sa_temparios_por_unidad.id_modo,
		vw_sa_temparios_por_unidad.id_unidad_basica,
		vw_sa_temparios_por_unidad.importe_por_tipo_orden,
			(case vw_sa_temparios_por_unidad.`id_modo`
			  when '1' then vw_sa_temparios_por_unidad.precio_por_tipo_orden * %s / %s
			  when '2' then vw_sa_temparios_por_unidad.precio_por_tipo_orden
			  when '3' then vw_sa_temparios_por_unidad.precio_por_tipo_orden
			  when '4' then vw_sa_temparios_por_unidad.precio_por_tipo_orden
			end) AS total_por_tipo_orden,
		vw_sa_temparios_por_unidad.precio_por_tipo_orden,
		vw_sa_temparios_por_unidad.base_ut,
		vw_sa_temparios_por_unidad.ut,
		vw_sa_temparios_por_unidad.precio,
		vw_sa_temparios_por_unidad.total_importe,
		vw_sa_temparios_por_unidad.descripcion_modo,
		vw_sa_temparios_por_unidad.operador,
		vw_sa_temparios_por_unidad.descripcion_operador,
		vw_sa_temparios_por_unidad.costo,
		vw_sa_temparios_por_unidad.id_seccion,
		vw_sa_temparios_por_unidad.id_subseccion,
		vw_sa_temparios_por_unidad.descripcion_seccion,
		vw_sa_temparios_por_unidad.descripcion_subseccion
		FROM 
		vw_sa_temparios_por_unidad WHERE vw_sa_temparios_por_unidad.id_tempario_det = %s",
		 $rowTipoOrden['precio_tempario'],
		 $rowBaseUt['valor_parametro'],
		valTpDato($idTemparioDet,"int"));
			
	$rsTempario = mysql_query($queryTempario) or die(mysql_error().$queryTempario);
	$rowTempario = mysql_fetch_assoc($rsTempario);
	
	if ($rowTempario['id_tempario'] != "")
	{
		$objResponse->assign("txtCodigoTemp","value",$rowTempario['codigo_tempario']);
		$objResponse->assign("txtDescripcionTemp","value",utf8_encode($rowTempario['descripcion_tempario']));
		$objResponse->assign("hddIdTemp","value",$rowTempario['id_tempario']);
		$objResponse->assign("txtIdModoTemp","value",$rowTempario['id_modo']);
		
		$objResponse->assign("txtOperador","value",$rowTempario['operador']);
		$objResponse->assign("txtDescripcionOperador","value",utf8_encode($rowTempario['descripcion_operador']));
		
		$objResponse->assign("txtModoTemp","value",utf8_encode($rowTempario['descripcion_modo']));
		$objResponse->assign("hddOrigenTempario","value","ORDEN");
		$objResponse->assign("txtPrecio","value",$rowTempario['precio_por_tipo_orden']);
		$objResponse->assign("txtPrecioTemp","value",number_format($rowTempario['total_por_tipo_orden'],2,".",","));
		$objResponse->assign("hddIdDetTemp","value",$rowTempario['id_tempario_det']);
		$objResponse->assign("txtSeccionTempario","value", utf8_encode($rowTempario['descripcion_seccion']));
		$objResponse->assign("hddSeccionTempario","value",$rowTempario['id_seccion']);
		$objResponse->assign("txtSubseccionTempario","value", utf8_encode($rowTempario['descripcion_subseccion']));
		$objResponse->assign("hddIdSubseccionTempario","value",$rowTempario['id_subseccion']);
		$objResponse->script("$('lstMecanico').focus();");
	}
	
		$query = sprintf("SELECT 
			pg_empleado.nombre_empleado,
			pg_empleado.apellido,
			sa_mecanicos.id_mecanico
			FROM
			pg_empleado
			INNER JOIN sa_mecanicos ON (pg_empleado.id_empleado = sa_mecanicos.id_empleado)
			INNER JOIN pg_cargo_departamento ON (pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento)
			INNER JOIN pg_cargo ON (pg_cargo_departamento.id_cargo = pg_cargo.id_cargo)
			INNER JOIN pg_departamento ON (pg_cargo_departamento.id_departamento = pg_departamento.id_departamento) WHERE pg_departamento.id_empresa = %s", valTpDato($valFormDcto['txtIdEmpresa'],"int"));
		$rs = mysql_query($query) or die(mysql_error().$query);
		
	$html = "<select id=\"lstMecanico\" name=\"lstMecanico\" >";
		$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_mecanico']."\">".utf8_encode($row['nombre_empleado'])." ".utf8_encode($row['apellido'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstMecanico","innerHTML",$html);	
	
	$objResponse->script("
		$('divFlotante2').style.display='none';");
	
	return $objResponse;
}

function asignarTot($id_tot) {
	$objResponse = new xajaxResponse();

	$queryTot = sprintf("
		SELECT *
		FROM
		sa_orden
		INNER JOIN vw_sa_orden_tot ON (sa_orden.id_orden = vw_sa_orden_tot.id_orden_servicio) WHERE vw_sa_orden_tot.id_orden_tot = %s",
	valTpDato($id_tot,"int"));
	
	$rsTot = mysql_query($queryTot) or die(mysql_error().$queryTot);
	$rowTot = mysql_fetch_assoc($rsTot);
	
	$queryPorcentajeTot = sprintf("SELECT 
		pg_precios.id_precio,
		pg_precios.porcentaje_tot
		FROM
		pg_precios WHERE pg_precios.id_precio = %s",$rowTot['id_tipo_orden']);
	
	$rsPorcentajeTot =mysql_query($queryPorcentajeTot) or die(mysql_error().$queryPorcentajeTot);
	$rowPorcentajeTot = mysql_fetch_assoc($rsPorcentajeTot);
	
	//SELECT PORCENTAJE DEL TOT PARA EL TIPO DE ORDEN
	if ($rowTot['id_orden_tot'] != "")
	{
		$objResponse->assign("txtNumeroTot","value",$rowTot['id_orden_tot']);
		$objResponse->assign("txtProveedor","value",utf8_encode($rowTot['nombre']));
		$objResponse->assign("txtMonto","value",number_format($rowTot['monto_total'],2,".",","));
		$objResponse->assign("txtFechaTot","value",$rowTot['fecha_factura_proveedor']);
		$objResponse->assign("txtTipoPagoTot","value", utf8_encode($rowTot['tipo_pago']));
		$objResponse->assign("hddIdPorcentajeTot","value",$rowPorcentajeTot['id_precio']);
		$objResponse->assign("txtPorcentaje","value",number_format($rowPorcentajeTot['porcentaje_tot'],2,".",","));
		$objResponse->assign("txtMontoTotalTot","value",number_format($rowTot['monto_total'] + ($rowPorcentajeTot['porcentaje_tot']*$rowTot['monto_total']/100),2,".",","));
	}	
		
	$objResponse->script("
		$('divFlotante2').style.display='none';");
	
	return $objResponse;
}

function insertarTot($valForm, $valFormTotAgreg, $valFormTotalDcto) {
		$objResponse = new xajaxResponse();
	
		for ($contTot = 0; $contTot <= strlen($valFormTotalDcto['hddObjTot']); $contTot++) {
			$caracterTot = substr($valFormTotalDcto['hddObjTot'], $contTot, 1);
					
			if ($caracterTot != "|" && $caracterTot != "")
				$cadenaTot.= $caracterTot;
			else {
				$arrayObjTot[] = $cadenaTot;
				$cadenaTot = "";
			}	
		}
		
		$sw = 0;
		foreach($arrayObjTot as $indice => $valor) {
			if ($valFormTotAgreg['hddIdTot'.$valor] != "")
			{
				if($valFormTotAgreg['hddIdTot'.$valor] == $valForm['txtNumeroTot'])
					$sw = 1;
			}
		}
		
		if($sw == 1)
		{
			$objResponse ->alert("El T.O.T que desea agregar ya se encuentra en la lista. Escoja otro.");
		}
		else
		{
			$sigValor = $arrayObjTot[count($arrayObjTot)-1] + 1;
					
				$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItmTot:%s', 'class':'textoGris_11px', 'title':'trItmTot:%s'}).adopt([
					new Element('td', {'align':'center', 'id':'tdItmTot:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmTot' name='cbxItmTot[]' type='checkbox' value='%s' onclick='xajax_calcularTotalDcto();' />\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDetTot%s' name='hddIdPedDetTot%s' value='%s'/><input type='hidden' id='hddIdTot%s' name='hddIdTot%s' value='%s'/><input type='hidden' id='hddIdPorcTot%s' name='hddIdPorcTot%s' value='%s'/><input type='hidden' id='hddPrecTot%s' name='hddPrecTot%s' value='%s'/><input type='hidden' id='hddPorcTot%s' name='hddPorcTot%s' value='%s'/><input type='hidden' id='hddMontoTotalTot%s' name='hddMontoTotalTot%s' value='%s'/>\"),
					new Element('td', {'align':'center', 'id':'tdItmTotAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmTotAprob' name='cbxItmTotAprob[]' 'title':'cbxItmTotAprob:%s' type='checkbox' value='%s' checked='checked' onclick='xajax_calcularTotalDcto();' /><input type='hidden' id='hddValorCheckAprobTot%s' name='hddValorCheckAprobTot%s'/>\")]); 
				elemento.injectBefore('trm_pie_tot');
				",
				$sigValor, $sigValor,
				$sigValor,  $sigValor,
				$valForm['txtNumeroTot'],
				$valForm['txtProveedor'],
				$valForm['txtTipoPagoTot'],
				$valForm['txtMonto'],
				$valForm['txtPorcentaje'],
				$valForm['txtMontoTotalTot'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $valForm['txtNumeroTot'],
				$sigValor, $sigValor, $valForm['hddIdPorcentajeTot'],
				$sigValor, $sigValor, str_replace(",","", $valForm['txtMonto']),
				$sigValor, $sigValor, str_replace(",","", $valForm['txtPorcentaje']),
				$sigValor, $sigValor, str_replace(",","", $valForm['txtMontoTotalTot']),
				$sigValor, $sigValor, $sigValor, $sigValor,
				$sigValor, $sigValor));
				
				if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
					$objResponse->script(sprintf("$('tdInsElimTot').style.display = 'none'; $('trItmTot:%s').style.display = 'none'; $('tdItmTotAprob:%s').style.display=''",$sigValor,$sigValor));	
				else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
					$objResponse->script(sprintf("$('tdInsElimTot').style.display = 'none';  $('trItmTot:%s').style.display = 'none';   $('tdItmTotAprob:%s').style.display=''",$sigValor,$sigValor));
			
			$arrayObjTot[] = $sigValor;
			foreach($arrayObjTot as $indiceTot => $valorTot) {
				$cadenaTot = $valFormTotalDcto['hddObjTot']."|".$valorTot;
			}
			$objResponse->assign("hddObjTot","value",$cadenaTot);
				
			$objResponse->script("
				$('tblListados').style.display='none';
				$('tblArticulo').style.display='none';
				$('tblLogoGotoSystems').style.display='';
				$('divFlotante2').style.display='none';
				$('divFlotante').style.display='none';
				xajax_calcularTotalDcto();");
	}
	return $objResponse;
}

function insertarTempario($valForm, $valFormManoObraAgreg, $valFormTotalDcto) {
		$objResponse = new xajaxResponse();
	
		for ($contTemp = 0; $contTemp <= strlen($valFormTotalDcto['hddObjTempario']); $contTemp++) {
			$caracterTemp = substr($valFormTotalDcto['hddObjTempario'], $contTemp, 1);
			
			if ($caracterTemp != "|" && $caracterTemp != "")
				$cadenaTemp.= $caracterTemp;
			else {
				$arrayObjTemp[] = $cadenaTemp;
				$cadenaTemp = "";
			}	
		}
		
		$sw = 0;
		foreach($arrayObjTemp as $indice => $valor) {
			if ($valFormManoObraAgreg['hddIdTemp'.$valor] != "")
			{
				if($valFormManoObraAgreg['hddIdTemp'.$valor] == $valForm['hddIdTemp'])
					$sw = 1;
			}
		}
		
		if($sw == 1)
			$objResponse ->alert("La Mano de Obra que desea agregar ya se encuentra en la lista. Escoja otro.");
		else
		{
			$sigValor = $arrayObjTemp[count($arrayObjTemp)-1] + 1;
	
			$queryMecanico = sprintf("SELECT 
				pg_empleado.nombre_empleado,
				pg_empleado.apellido,
				sa_mecanicos.id_mecanico
				FROM
				pg_empleado
				INNER JOIN sa_mecanicos ON (pg_empleado.id_empleado = sa_mecanicos.id_empleado) WHERE sa_mecanicos.id_mecanico = %s", valTpDato($valForm['lstMecanico'], "int"));
			$rsMecanico = mysql_query($queryMecanico) or die(mysql_error().$queryMecanico);
			$rowMecanico = mysql_fetch_assoc($rsMecanico);
			
			$queryTempDiagnostico = sprintf("SELECT *
					FROM
					pg_parametros_empresas
					WHERE
					pg_parametros_empresas.id_empresa = %s AND 
					pg_parametros_empresas.descripcion_parametro + 0 = 5", $_SESSION['idEmpresaUsuarioSysGts']);
			$rsTempDiagnostico = mysql_query($queryTempDiagnostico) or die(mysql_error().$queryTempDiagnostico);
			$rowTempDiagnostico = mysql_fetch_assoc($rsTempDiagnostico);
			
			if($valForm['hddIdTemp'] == $rowTempDiagnostico['valor_parametro'])
			{
				$checked = "checked='checked'";
				$display = "style='display:none'";
				//$disabled = "disabled='disabled'";
				$readonly_check_ppal_list_tempario = 1;
				$imgCheckDisabled = sprintf("<input id='cbxItmTempAprobDisabled' name='cbxItmTempAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
				$value_checked = 1;
			}
			else
			{
				$checked = " ";
				$display = " ";
				$disabled = " ";
				$imgCheckDisabled = " ";
				$readonly_check_ppal_list_tempario = 0;
				$value_checked = 0;
			}
				
			$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItmTemp:%s', 'class':'textoGris_11px', 'title':'trItmTemp:%s'}).adopt([
					new Element('td', {'align':'center', 'id':'tdItmTemp:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmTemp' name='cbxItmTemp[]' type='checkbox' value='%s' onclick='xajax_calcularTotalDcto();' %s />\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDetTemp%s' name='hddIdPedDetTemp%s' value='%s'/><input type='hidden' id='hddIdTemp%s' name='hddIdTemp%s' value='%s'/><input type='hidden' id='hddIdMec%s' name='hddIdMec%s' value='%s'/><input type='hidden' id='hddIvaTemp%s' name='hddIvaTemp%s' value='%s'/><input type='hidden' id='hddPrecTemp%s' name='hddPrecTemp%s' value='%s'/><input type='hidden' id='hddIdModo%s' name='hddIdModo%s' value='%s'/><input type='hidden' id='hddDetTemp%s' name='hddDetTemp%s' value='%s'/>\"),
					new Element('td', {'align':'center', 'id':'tdItmTempAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmTempAprob' name='cbxItmTempAprob[]' 'title':'cbxItmTempAprob:%s' type='checkbox' value='%s' checked='checked' onclick='xajax_calcularTotalDcto();' %s /><input type='hidden' id='hddValorCheckAprobTemp%s' name='hddValorCheckAprobTemp%s'/>%s<input type='hidden' id='hddIdOrigen%s' name='hddIdOrigen%s' value='%s'/>\")]); 
				elemento.injectBefore('trm_pie_tempario');
				",
				$sigValor, $sigValor,
				$sigValor,  $sigValor, $disabled,
				$valForm['txtSeccionTempario'],
				$valForm['txtSubseccionTempario'],
				$valForm['txtCodigoTemp'],
				$valForm['txtDescripcionTemp'],
				$valForm['txtModoTemp'],
				$valForm['txtDescripcionOperador'],
				$valForm['txtPrecio'],
				$valForm['txtPrecioTemp'],
				$valForm['hddOrigenTempario'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, $valForm['hddIdTemp'],//hddIdDetTemp
				$sigValor, $sigValor, $rowMecanico['id_mecanico'],
				$sigValor, $sigValor, "",
				$sigValor, $sigValor, str_replace(",","", $valForm['txtPrecioTemp']),
				$sigValor, $sigValor, $valForm['txtIdModoTemp'],
				$sigValor, $sigValor, $valForm['hddIdDetTemp'],
				$sigValor, $sigValor, $sigValor, $display,
				$sigValor, $sigValor,
				$imgCheckDisabled,
				$sigValor, $sigValor, 0));
				
			    if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
					$objResponse->script(sprintf("$('tdInsElimManoObra').style.display = 'none'; $('tdItmTemp:%s').style.display = 'none'; $('tdItmTempAprob:%s').style.display=''",$sigValor,$sigValor));	
				else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
					$objResponse->script(sprintf("$('tdInsElimManoObra').style.display = 'none';  $('tdItmTemp:%s').style.display = 'none';   $('tdItmTempAprob:%s').style.display=''",$sigValor,$sigValor));				
			else if($valFormTotalDcto['hddAccionTipoDocumento']==1)
							$objResponse->script(sprintf("
							$('tdInsElimManoObra').style.display = '';
							$('tdItmTemp:%s').style.display = '';
							$('tdItmTempAprob:%s').style.display='none';",
							$sigValor,
							$sigValor));
			
			$arrayObjTemp[] = $sigValor;
			foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
				$cadenaTemp = $valFormTotalDcto['hddObjTempario']."|".$valorTemp;
			}
			$objResponse->assign("hddObjTempario","value",$cadenaTemp);
				
			$objResponse->script("
				$('tblListados').style.display='none';
				$('tblArticulo').style.display='none';
				$('tblLogoGotoSystems').style.display='';
				$('divFlotante2').style.display='none';
				$('divFlotante').style.display='none';
				xajax_calcularTotalDcto();");
	}
	return $objResponse;
}

function eliminarTempario($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxItmTemp'])) {
		foreach($valForm['cbxItmTemp'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trItmTemp:%s');	
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	$objResponse->script("xajax_calcularTotalDcto();");
	return $objResponse;
}
function eliminarTot($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxItmTot'])) {
		foreach($valForm['cbxItmTot'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trItmTot:%s');		
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	
	$objResponse->script("xajax_calcularTotalDcto();");
	return $objResponse;
}


function calcularTotalDcto() {
	$objResponse = new xajaxResponse();
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'),xajax.getFormValues('frm_agregar_paq'),xajax.getFormValues('frmListaManoObra'),xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmListaTot'));");
		
	return $objResponse;
}


function insertarNota($valForm, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();
	
		for ($contNota = 0; $contNota <= strlen($valFormTotalDcto['hddObjNota']); $contNota++) {
			$caracterNota = substr($valFormTotalDcto['hddObjNota'], $contNota, 1);
						
			if ($caracterNota != "|" && $caracterNota != "")
				$cadenaNota.= $caracterNota;
			else {
				$arrayObjNota[] = $cadenaNota;
				$cadenaNota = "";
			}	
		}
		
		$sigValor = $arrayObjNota[count($arrayObjNota)-1] + 1;
		
		$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmNota:%s', 'class':'textoGris_11px', 'title':'trItmNota:%s'}).adopt([
				new Element('td', {'align':'center', 'id':'tdItmNota:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmNota' name='cbxItmNota[]' type='checkbox' value='%s' />\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s".
				"<input type='hidden' id='hddIdPedDetNota%s' name='hddIdPedDetNota%s' value='%s'/><input type='hidden' id='hddIdNota%s' name='hddIdNota%s' value='%s'/><input type='hidden' id='hddDesNota%s' name='hddDesNota%s' value='%s'/><input type='hidden' id='hddPrecNota%s' name='hddPrecNota%s' value='%s'/>\"),
				new Element('td', {'align':'center' , 'id':'tdItmNotaAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmNotaAprob' name='cbxItmNotaAprob[]' type='checkbox' value='%s' checked='checked' onclick='xajax_calcularTotalDcto();'  /><input type='hidden' id='hddValorCheckAprobNota%s' name='hddValorCheckAprobNota%s'/>\")]);
			elemento.injectBefore('trm_pie_nota');",
			$sigValor, $sigValor,
			$sigValor, $sigValor,
			 $valForm['txtDescripcionNota'],
			 number_format($valForm['txtPrecioNota'],2,".",","),
			$sigValor, $sigValor, "",
			$sigValor, $sigValor, $sigValor,
			$sigValor, $sigValor, $valForm['txtDescripcionNota'],
			$sigValor, $sigValor, $valForm['txtPrecioNota'],
			$sigValor, $sigValor,
			$sigValor, $sigValor));
				
			/*if( $valFormTotalDcto['hddAccionTipoDocumento']==3)//$valFormTotalDcto['hddAccionTipoDocumento']==1 ||
				$objResponse->script(sprintf("$('tdInsElimNota').style.display = ''; $('tdItmNota:%s').style.display = ''; $('tdItmNotaAprob:%s').style.display='none'",$sigValor,$sigValor));	
			else */if ($valFormTotalDcto['hddAccionTipoDocumento']==2)
				$objResponse->script(sprintf("$('tdInsElimNota').style.display = 'none'; $('tdItmNota:%s').style.display = 'none'; $('tdItmNotaAprob:%s').style.display=''",$sigValor,$sigValor));	
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==4)
				$objResponse->script(sprintf("$('tdInsElimNota').style.display = 'none';  $('tdItmNota:%s').style.display = 'none';   $('tdItmNotaAprob:%s').style.display=''",$sigValor,$sigValor));	
				else if($valFormTotalDcto['hddAccionTipoDocumento']==1)
							$objResponse->script(sprintf("
							$('tdInsElimNota').style.display = '';
							$('tdItmNota:%s').style.display = '';
							$('tdItmNotaAprob:%s').style.display='none';",
							$sigValor,
							$sigValor));
				
				
				
			$arrayObjNota[] = $sigValor;
			foreach($arrayObjNota as $indiceNota => $valorNota) {
				$cadenaNota = $valFormTotalDcto['hddObjNota']."|".$valorNota;
			}
		$objResponse->assign("hddObjNota","value",$cadenaNota);
		
		$objResponse->script("
			$('tblListados').style.display='none';
			$('tblArticulo').style.display='none';
			$('tblLogoGotoSystems').style.display='';
			
			$('divFlotante2').style.display='none';
			$('divFlotante').style.display='none';");
			
		
		
		$objResponse->script("xajax_calcularTotalDcto();");


	
	return $objResponse;
}

function eliminarNota($valForm) {
	$objResponse = new xajaxResponse();
	
	if (isset($valForm['cbxItmNota'])) {
		foreach($valForm['cbxItmNota'] as $indiceItm=>$valorItm) {
			$objResponse->script(sprintf("
				fila = document.getElementById('trItmNota:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$valorItm));
		}
	}
	
	$objResponse->script("xajax_calcularTotalDcto();");
	return $objResponse;
}


function calcularTotalPaquete($valFormPaquete)
{
	$objResponse = new xajaxResponse();

	
	//$objResponse -> alert("Calcular Paq".$valFormPaquete['hddManObraAproXpaq']);

	for ($contTemp = 0; $contTemp <= strlen($valFormPaquete['hddObjTemparioPaq']); $contTemp++) {
		$caracterTemp = substr($valFormPaquete['hddObjTemparioPaq'], $contTemp, 1);
		
		if ($caracterTemp != "|" && $caracterTemp != "")
			$cadenaTemp .= $caracterTemp;
		else {
			$arrayObjTemp[] = $cadenaTemp;
			$cadenaTemp = "";
		}	
	}
	
	$cadenaMo=NULL;
	$cadenaRe=NULL;
	

	for ($contRep = 0; $contRep <= strlen($valFormPaquete['hddObjRepuestoPaq']); $contRep++) {
		$caracterRep = substr($valFormPaquete['hddObjRepuestoPaq'], $contRep, 1);
		
		if ($caracterRep != "|" && $caracterRep != "")
			$cadenaRep .= $caracterRep;
		else {
			$arrayObjRep[] = $cadenaRep;
			$cadenaRep = "";
		}	
	}

	$subTotal = 0;
	$totalManoObra = 0;
	$totalTempario = 0;
	$nroRepAprobPaq = 0;
	$nroManoObraAprobPaq = 0;
        
        $totalConIvaRepuestos = 0;
        $totalSinIvaRepuestos = 0;
	
	if (isset($arrayObjTemp)) {
		foreach($arrayObjTemp as $indiceTemp => $valorTemp) {
		
			if (isset($valFormPaquete['chk_tempPaq'])) {
			
				foreach($valFormPaquete['chk_tempPaq'] as $indiceAprob => $valorAprob) {
					//$objResponse ->alert($valorTemp."-".$valorAprob."Valor a Concatenar".$valFormPaquete['txtIdeTempPaq'.$valorTemp]);

					if($valorTemp == $valorAprob) {	
					
						$nroManoObraAprobPaq ++;
						$cadenaMo = $cadenaMo."|".$valFormPaquete['txtIdeTempPaq'.$valorTemp];
						$totalManoObra += doubleval($valFormPaquete['txtPrecTempPaq'.$valorTemp]);
						
						
						
					}
				}
			}
		}
	}		
	
	if (isset($arrayObjRep)) {
		foreach($arrayObjRep as $indiceRep => $valorRep) {
			if (isset($valFormPaquete['chk_repPaq'])) {
				foreach($valFormPaquete['chk_repPaq'] as $indiceApro => $valorApro) {
					if($valorRep == $valorApro) {	
						$nroRepAprobPaq ++;
						$cadenaRe = $cadenaRe."|".$valFormPaquete['txtIdeRepPaq'.$valorRep];

						//$cadenaMo = $valFormTotalDcto['hddManObraAproXpaq']."|".$valorRep;
                                                //es paquete tiene el nobmre de la variable MAL
						$totalTempario += doubleval($valFormPaquete['txtPrecRepPaq'.$valorRep]);
                                                
                                                //si el articulo posee iva:
                                                $QueryPoseeIva = sprintf("SELECT id_impuesto FROM iv_articulos_impuesto WHERE id_impuesto = 1 AND id_articulo = %s LIMIT 1",
                                                                        valTpDato($valorRep, "int"));
                                                $rsPoseeIva = mysql_query($QueryPoseeIva);
                                                $poseeIva = mysql_num_rows($rsPoseeIva);

                                                if($poseeIva){
                                                    $totalConIvaRepuestos += $totalTempario;
                                                }else{
                                                    $totalSinIvaRepuestos += $totalTempario;
                                                }
                                                
					}
				}
			}
		}
	}	
	
		
	$objResponse->assign("hddManObraAproXpaq","value",$cadenaMo);
	$objResponse->assign("hddRepAproXpaq","value",$cadenaRe);
	
	$objResponse->assign("txtNroManoObraAprobPaq","value",$nroManoObraAprobPaq);
	$objResponse->assign("txtNroRepuestoAprobPaq","value",$nroRepAprobPaq);
	$objResponse->assign("txtTotalManoObraPaq","value",number_format($totalManoObra,2,".",","));
	$objResponse->assign("txtTotalRepPaq","value",number_format($totalTempario,2,".",","));
	$objResponse->assign("txtTotalItemPaq","value", $valFormPaquete['txtNroManoObraPaq']+$valFormPaquete['txtNroRepuestoPaq']);
	$objResponse->assign("txtTotalItemAprobPaq","value", $nroRepAprobPaq + $nroManoObraAprobPaq);
        
        $objResponse->assign("hddTotalArtExento","value",number_format($totalSinIvaRepuestos,2,".",","));//."AQUI EXCENTO"
	$objResponse->assign("hddTotalArtConIva","value",number_format($totalConIvaRepuestos,2,".",","));
	
	$objResponse->assign("txtPrecioPaquete","value",number_format(($totalManoObra + $totalTempario),2,".",","));
	
	return $objResponse;


}

function dateadd($date, $dd=0, $mm=0, $yy=0){

	$date_r = getdate(strtotime($date)); 
	$date_result = date("d-m-Y", mktime(($date_r["hours"]+$hh),($date_r["minutes"]+$mn),($date_r["seconds"]+$ss),($date_r["mon"]+$mm),($date_r["mday"]+$dd),($date_r["year"]+$yy)));
	return $date_result;
	
}


function generarPresupuestoApartirDeOrden($valForm, $valFormTotalDcto)
{
	$objResponse = new xajaxResponse();
	
$insertSQL = sprintf("
		INSERT INTO sa_presupuesto (fecha_presupuesto, id_orden, id_recepcion, id_tipo_orden, id_empresa, id_estado_orden, id_empleado, subtotal, porcentaje_descuento, subtotal_descuento, tiempo_orden, tiempo_inicio, tiempo_fin, prioridad, tiempo_promesa, id_puesto, id_piramide, id_usuario_bloqueo, id_mecanico_revision, id_empleado_aprobacion_factura, estado_presupuesto, tipo_presupuesto, idIva, iva, base_imponible, subtotal_iva) 
		SELECT 
		  NOW(),	
		  %s,
		  sa_orden.id_recepcion,
		  sa_orden.id_tipo_orden,
		  sa_orden.id_empresa,
		  sa_orden.id_estado_orden,
		  sa_orden.id_empleado,
		  sa_orden.subtotal,
		  sa_orden.porcentaje_descuento,
		  sa_orden.subtotal_descuento,
		  sa_orden.tiempo_orden,
		  sa_orden.tiempo_inicio,
		  sa_orden.tiempo_fin,
		  sa_orden.prioridad,
		  sa_orden.tiempo_promesa,
		  sa_orden.id_puesto,
		  sa_orden.id_piramide,
		  sa_orden.id_usuario_bloqueo,
		  sa_orden.id_mecanico_revision,
		  sa_orden.id_empleado_aprobacion_factura,
		  0,
		  0,
		  sa_orden.idIva,
		  sa_orden.iva,
		  sa_orden.base_imponible,
		  sa_orden.subtotal_iva
		FROM
		  sa_orden
		  WHERE sa_orden.id_empresa = %s AND sa_orden.id_orden= %s",
		valTpDato($valForm['txtIdPresupuesto'], "int"),
		valTpDato($valForm['txtIdEmpresa'], "int"),
		valTpDato($valForm['txtIdPresupuesto'], "int"));
		
		mysql_query("SET NAMES 'utf8';");	
		$Result1 = mysql_query($insertSQL) or die(mysql_error().$insertSQL);
		$idPresupuesto = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		$insertSQL = sprintf("
			INSERT INTO sa_det_presup_articulo(id_presupuesto,
			id_articulo,
			id_paquete,
			cantidad,
			precio_unitario,
			costo,
			id_iva,
			iva,
			aprobado,
			tiempo_asignacion,
			tiempo_aprobacion,
			id_empleado_aprobacion,
			estado_articulo,
			id_det_orden_articulo) 
			SELECT 
			%s,
			sa_det_orden_articulo.id_articulo,
			sa_det_orden_articulo.id_paquete,
			sa_det_orden_articulo.cantidad,
			sa_det_orden_articulo.precio_unitario,
			sa_det_orden_articulo.costo,			
			sa_det_orden_articulo.id_iva,
			sa_det_orden_articulo.iva,
			sa_det_orden_articulo.aprobado,
			sa_det_orden_articulo.tiempo_asignacion,
			sa_det_orden_articulo.tiempo_aprobacion,
			sa_det_orden_articulo.id_empleado_aprobacion,
			sa_det_orden_articulo.estado_articulo,
			sa_det_orden_articulo.id_det_orden_articulo
			FROM
			sa_orden
			INNER JOIN sa_det_orden_articulo ON (sa_orden.id_orden = sa_det_orden_articulo.id_orden) WHERE sa_orden.id_empresa = %s AND sa_orden.id_orden= %s AND sa_det_orden_articulo.aprobado = 1 AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO'",
		$idPresupuesto,
		valTpDato($valForm['txtIdEmpresa'], "int"),
		valTpDato($valForm['txtIdPresupuesto'], "int"));
		
		mysql_query("SET NAMES 'utf8';");
		$rs = mysql_query($insertSQL) or die(mysql_error().$insertSQL);
		mysql_query("SET NAMES 'latin1';");
		
		$insertSQL = sprintf("
			INSERT INTO sa_det_presup_tempario (id_presupuesto,
			  id_paquete,
			  id_tempario,
			  precio,
			  costo,			 
			  id_modo,
			  base_ut_precio,
			  operador,
			  aprobado,
			  ut,
			  tiempo_aprobacion,
			  tiempo_asignacion,
			  tiempo_inicio,
			  tiempo_fin,
			  id_mecanico,
			  id_empleado_aprobacion,
			  origen_tempario,
			  estado_tempario,
			  precio_tempario_tipo_orden,
			  id_det_orden_tempario)
			SELECT 
			  %s,
			  sa_det_orden_tempario.id_paquete,
			  sa_det_orden_tempario.id_tempario,
			  sa_det_orden_tempario.precio,
			  sa_det_orden_tempario.costo,			  
			  sa_det_orden_tempario.id_modo,
			  sa_det_orden_tempario.base_ut_precio,
			  sa_det_orden_tempario.operador,
			  sa_det_orden_tempario.aprobado,
			  sa_det_orden_tempario.ut,
			  sa_det_orden_tempario.tiempo_aprobacion,
			  sa_det_orden_tempario.tiempo_asignacion,
			  sa_det_orden_tempario.tiempo_inicio,
			  sa_det_orden_tempario.tiempo_fin,
			  sa_det_orden_tempario.id_mecanico,
			  sa_det_orden_tempario.id_empleado_aprobacion,
			  sa_det_orden_tempario.origen_tempario,
			  sa_det_orden_tempario.estado_tempario,
			  sa_det_orden_tempario.precio_tempario_tipo_orden,
			  sa_det_orden_tempario.id_det_orden_tempario
			FROM
			  sa_orden
			  INNER JOIN sa_det_orden_tempario ON (sa_orden.id_orden = sa_det_orden_tempario.id_orden) WHERE sa_orden.id_empresa = %s AND sa_orden.id_orden= %s AND sa_det_orden_tempario.aprobado = 1 AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'",
		$idPresupuesto,
		valTpDato($valForm['txtIdEmpresa'], "int"),
		valTpDato($valForm['txtIdPresupuesto'], "int"));
		
		mysql_query("SET NAMES 'utf8';");
		$rs = mysql_query($insertSQL) or die(mysql_error().$insertSQL);
		mysql_query("SET NAMES 'latin1';");
	
		$insertSQL = sprintf("
			INSERT INTO sa_det_presup_tot (id_presupuesto,
			id_orden_tot,
			id_porcentaje_tot,
			porcentaje_tot,
			aprobado,
			id_det_orden_tot)
			SELECT 
			%s,
			sa_det_orden_tot.id_orden_tot,
			sa_det_orden_tot.id_porcentaje_tot,
			sa_det_orden_tot.porcentaje_tot,
			sa_det_orden_tot.aprobado,
			sa_det_orden_tot.id_det_orden_tot
			FROM
			sa_orden
			INNER JOIN sa_det_orden_tot ON (sa_orden.id_orden = sa_det_orden_tot.id_orden) WHERE sa_orden.id_empresa = %s AND sa_orden.id_orden= %s AND sa_det_orden_tot.aprobado = 1",
		$idPresupuesto,
		valTpDato($valForm['txtIdEmpresa'], "int"),
		valTpDato($valForm['txtIdPresupuesto'], "int"));
		
		mysql_query("SET NAMES 'utf8';");
		$rs = mysql_query($insertSQL) or die(mysql_error().$insertSQL);
		mysql_query("SET NAMES 'latin1';");
		
		$insertSQL = sprintf("
			INSERT INTO sa_det_presup_notas (id_presupuesto,
			descripcion_nota,
			precio,
			aprobado,
			id_det_orden_nota)
			SELECT 
			%s,
			sa_det_orden_notas.descripcion_nota,
			sa_det_orden_notas.precio,
			sa_det_orden_notas.aprobado,
			sa_det_orden_notas.id_det_orden_nota
			FROM
			sa_orden
			INNER JOIN sa_det_orden_notas ON (sa_orden.id_orden = sa_det_orden_notas.id_orden) WHERE sa_orden.id_empresa = %s AND sa_orden.id_orden= %s AND sa_det_orden_notas.aprobado = 1",
		$idPresupuesto,
		valTpDato($valForm['txtIdEmpresa'], "int"),
		valTpDato($valForm['txtIdPresupuesto'], "int"));
		
		mysql_query("SET NAMES 'utf8';");
		$rs = mysql_query($insertSQL) or die(mysql_error().$insertSQL);
		mysql_query("SET NAMES 'latin1';");
		
		
		$query = sprintf("UPDATE sa_orden SET sa_orden.id_estado_orden = 3 WHERE sa_orden.id_orden = %s",
				 valTpDato($valForm['txtIdPresupuesto'], "int"));
		
		mysql_query("SET NAMES 'utf8';");
		$rs = mysql_query($query) or die(mysql_error().$query);
		mysql_query("SET NAMES 'latin1';");
		
		$objResponse -> script("
			$('tdCodigoBarraPresupuesto').style.display = '';
			$('tdNroPresupuesto').style.display = '';
			$('tdTxtNroPresupuesto').style.display = '';
			$('btnImprimirDoc').style.display = '';");
			
		$objResponse -> assign("txtNroPresupuesto","value", $idPresupuesto);
								
		$objResponse -> assign("tdCodigoBarraPresupuesto","innerHTML","<img border='0' src='clases/barcode128.php?codigo=".$idPresupuesto."&bw=2&bh=30&pc=1&type=B' />");
		
		$objResponse -> alert('El presupuesto ha sido Generado');
		
	return $objResponse;

}


function formClave($valFormTotalDcto, $accForm)
{
	$objResponse = new xajaxResponse();
			
		$query = sprintf("
		SELECT 
		*
		FROM
		pg_empleado
		INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
		INNER JOIN sa_claves ON (pg_empleado.id_empleado = sa_claves.id_empleado)
		WHERE
		sa_claves.modulo = '%s' AND 
		pg_usuario.id_usuario = %s",
		$accForm,
		$_SESSION['idUsuarioSysGts']);	
						
		$rs = mysql_query($query) or die(mysql_error().$query);
		$num_rows = mysql_num_rows($rs);
		
		if($num_rows > 0)
		{
			$objResponse->script("
				if ($('divFlotante2').style.display == 'none') {
					$('divFlotante2').style.display='';
					centrarDiv($('divFlotante2'));
					$('tblArticulosSustitutos').style.display = 'none';
				}
				document.forms['frmConfClave'].reset();");
			if($accForm != 'agreg_vr_fact')
			$objResponse -> script("if ($('divFlotante').style.display == '') {
					$('divFlotante').style.display='none';
					centrarDiv($('divFlotante'));
				}");
							
			$objResponse->assign("hddAccionObj","value", $accForm);
			
			if($accForm == 'edc_dcto_ord')
				$objResponse->assign("tdFlotanteTitulo2","innerHTML",utf8_encode("Acceso a Descuento"));
			else
				if($accForm == 'elim_tot')
					$objResponse->assign("tdFlotanteTitulo2","innerHTML",("Acceso Eliminaci&oacute;n de T.O.T"));
				else
					if($accForm == 'agreg_dcto_adnl')
						$objResponse->assign("tdFlotanteTitulo2","innerHTML",utf8_encode("Acceso Descuento Adicional"));
					else
						if($accForm == 'agreg_vr_fact')
						{
							$objResponse->assign("tdFlotanteTitulo2","innerHTML",utf8_encode("Acceso Agregar Vale Facturado"));
							$objResponse -> script("$('tblClaveDescuento').style.display = '';");
						}
							
		}
		else
		{
			$objResponse -> alert(("Usted no tiene acceso para realizar esta accion."));
		}
		
	return $objResponse;

}
function validarClaveDescuento($valFormDescuento, $valFormTotalDcto)
{
	$objResponse = new xajaxResponse();
	
	$query = sprintf("
		SELECT 
		sa_claves.clave AS contrasena
		FROM
		pg_empleado
		INNER JOIN pg_usuario ON (pg_empleado.id_empleado = pg_usuario.id_empleado)
		INNER JOIN sa_claves ON (pg_empleado.id_empleado = sa_claves.id_empleado)
		WHERE
		sa_claves.modulo = '%s' AND 
		pg_usuario.id_usuario = %s",
		$valFormDescuento['hddAccionObj'],
		$_SESSION['idUsuarioSysGts']);
		
		$rs = mysql_query($query) or die(mysql_error().$query);
		$row = mysql_fetch_assoc($rs);
		
		if($row['contrasena'] == md5($valFormDescuento['txtContrasenaAcceso']))
		{
			$objResponse ->	alert('Acceso concedido.');
			
			if($valFormDescuento['hddAccionObj'] == 'edc_dcto_ord')
			{
				$objResponse -> script("
					$('divFlotante2').style.display='none';
					$('txtDescuento').readOnly = false;
					$('txtDescuento').focus();
					");
			}
			else
			{
				if($valFormDescuento['hddAccionObj'] == 'elim_tot')
				{
					$objResponse -> script("
						$('divFlotante2').style.display='none';
						$('btnEliminarTot').readOnly = false;
						$('btnEliminarTot').focus();
						");
				}
				else
				{
					if($valFormDescuento['hddAccionObj'] == 'agreg_dcto_adnl')
					{
						$objResponse->assign("tdFlotanteTitulo2","innerHTML",utf8_encode("Descuentos Adicionales"));
						
						if($valFormTotalDcto['hddPuedeAgregarDescuentoAdicional'] == '')
						{
							$objResponse -> script("
								$('hddPuedeAgregarDescuentoAdicional').value = 1;");
						}
						$objResponse -> script("
						$('tblPorcentajeDescuento').style.display='';
						$('tblClaveDescuento').style.display = 'none';");
					}
					else
					{
						if($valFormDescuento['hddAccionObj'] == 'agreg_vr_fact')
						{
							$objResponse -> script("
								$('divFlotante2').style.display='none';
								$('hddAgregarOrdenFacturada').value = 1;
								");
						}
					}
				}
			}
		}		
		else
		{
			$objResponse -> alert(("Clave Invalida"));
			$objResponse -> script("$('txtContrasenaAcceso').focus();");
		}
	
	return $objResponse;
}
function contarItemsDcto($valFormTotalDcto, $valFormPaq)
{
	//ANTES DE INSERTAR UN ITEM
	
	//DEL PAQUETE TOMAR LOS QUE SE TOMARON DEL PAQUETE
	$objResponse = new xajaxResponse();
	
	$totalItem=0;
	
	$arrayObjPaq = explode("|",$valFormTotalDcto['hddObjPaquete']);
	$cont = 0;
	if (isset($arrayObjPaq)) {
		foreach($arrayObjPaq as $indice => $valor){
			if ($valor > 0 && $valor != "") {
				$array = explode("|",$valFormPaq['hddTempPaqAsig'.$valor]);
				$arrayTemparioPaq = NULL;
				if (isset($array)) {
					foreach ($array as $indice2 => $valor2) {
						if ($valor2 > 0 && $valor2 != "")
							$arrayTemparioPaq[] = $valor2;
					}
				}
				
				$array = explode("|",$valFormPaq['hddRepPaqAsig'.$valor]);
				$arrayRepuestosPaq = NULL;
				if (isset($array)) {
					foreach ($array as $indice2 => $valor2) {
						if ($valor2 > 0 && $valor2 != "")
							$arrayRepuestosPaq[] = $valor2;
					}
				}
				
				$arrayDetPaq[0] = $arrayTemparioPaq;
				$arrayDetPaq[1] = $arrayRepuestosPaq;
				
				$arrayPaquete[] = $arrayDetPaq;
				
				$cont += count($arrayTemparioPaq);
				$cont += count($arrayRepuestosPaq);
			}
		}
	}
	
	$arrayObj = explode("|", $valFormTotalDcto['hddObj']);
	$arrayRepuestos = NULL;
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayRepuestos[] = $valor;
		}
	}
	$cont += count($arrayRepuestos);
	
	$arrayObjTemp = explode("|", $valFormTotalDcto['hddObjTempario']);
	$arrayTempario = NULL;
	if (isset($arrayObjTemp)) {
		foreach($arrayObjTemp as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayTempario[] = $valor;
		}
	}
	$cont += count($arrayTempario);
	
	$arrayObjTot = explode("|", $valFormTotalDcto['hddObjTot']);
	$arrayTot = NULL;
	if (isset($arrayObjTot)) {
		foreach($arrayObjTot as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayTot[] = $valor;
		}
	}
	$cont += count($arrayTot);
	
	$arrayObjNota = explode("|", $valFormTotalDcto['hddObjNota']);
	$arrayNota = NULL;
	if (isset($arrayObjNota)) {
		foreach($arrayObjNota as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayNota[] = $valor;
		}
	}
	$cont += count($arrayNota);
	
	$objResponse -> script(sprintf("
			$('hddItemsCargados').value = %s;", $cont));
			
	return $objResponse;

}
function cargaLstDescuentos() {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT *
		FROM
		sa_porcentaje_descuento WHERE sa_porcentaje_descuento.activo = 1 AND sa_porcentaje_descuento.idModulo = 1 AND sa_porcentaje_descuento.id_empresa = %s ORDER BY sa_porcentaje_descuento.descripcion",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
		
	$rs = mysql_query($query) or die(mysql_error().$query);
	
	$html = "<select id=\"lstTipoDescuentos\" name=\"lstTipoDescuentos\" onchange=\"xajax_cargarPorcAdicional(this.value);\">";
		$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<option value=\"".$row['id_porcentaje_descuento']."\">".utf8_encode($row['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdLstTipoDescuentos","innerHTML",$html); 
	
	return $objResponse;
}

function cargarPorcAdicional($idPorcDctoAdnl)
{
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT 
		sa_porcentaje_descuento.porcentaje,
		sa_porcentaje_descuento.descripcion
		FROM
		sa_porcentaje_descuento
		WHERE
		sa_porcentaje_descuento.id_porcentaje_descuento = %s",
		valTpDato($idPorcDctoAdnl, "int"));
		
	$rs = mysql_query($query) or die(mysql_error().$query);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse -> assign("txtPorcDctoAdicional", "value", $row["porcentaje"]);
	$objResponse -> assign("txtDescripcionPorcDctoAdicional", "value", utf8_encode($row["descripcion"]));
	
	return $objResponse;
}

function eliminarDescuentoAdicional($idItemDescuento) {

	$objResponse = new xajaxResponse();
		
	$objResponse->script(sprintf("
		fila = document.getElementById('trItmDcto:%s');
		padre = fila.parentNode;
		padre.removeChild(fila);",
	$idItemDescuento));
	
	$objResponse->script("xajax_calcularTotalDcto();");		
	return $objResponse;
}

function diasVencimientoPresupuesto($idEmpresa)
{
	//$idEmpresa=$_SESSION['idEmpresaUsuarioSysGts'];//no es necesario lo toma automatico y cuando le dan a "ver" busca por empresa
	$buscarParametroFechaVencimiento = sprintf("SELECT valor_parametro FROM pg_parametros_empresas WHERE descripcion_parametro + 0 = 2 AND id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$Result1 = mysql_query($buscarParametroFechaVencimiento) or die(mysql_error().$buscarParametroFechaVencimiento);
	$parametro_venc = mysql_fetch_array($Result1);

	return $parametro_venc['valor_parametro'];
}

function verificarTipoOrdenPorVale($valFormDcto, $idTipoOrden)
{
	$objResponse = new xajaxResponse();
	
	if($valFormDcto['txtIdCliente']=='')
	{
		$objResponse -> alert('Escoja el cliente');
		$objResponse->script("
			$('lstTipoOrden').value = '-1';
			$('btnInsertarCliente').focus();");
		return $objResponse;
	}

	$query = sprintf("SELECT 
		pg_parametros_empresas.valor_parametro
		FROM
		 pg_parametros_empresas
		WHERE
		pg_parametros_empresas.descripcion_parametro + 0 = 4
		AND pg_parametros_empresas.id_empresa = %s",$valFormDcto['txtIdEmpresa']);
	$rs = mysql_query($query) or die(mysql_error().$query);
	$row = mysql_fetch_assoc($rs);
	
	if($row["valor_parametro"] != "" || $row["valor_parametro"] != NULL)
	{
	
		if($row['valor_parametro']==1)//UNO POR ORDEN
		{
			$queryVerificarSiExisteOrdenConEseTipoOrden = sprintf("SELECT *
				FROM
				sa_orden
				INNER JOIN sa_tipo_orden ON (sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden)
				WHERE
				sa_orden.id_recepcion = %s AND sa_orden.id_tipo_orden = %s",$valFormDcto['txt_id_vale_recepcion'], $idTipoOrden);
				
			$rsVerificarSiExisteOrdenConEseTipoOrden = mysql_query($queryVerificarSiExisteOrdenConEseTipoOrden) or die(mysql_error().$queryVerificarSiExisteOrdenConEseTipoOrden);
			$numOrdenPorVale = mysql_num_rows($rsVerificarSiExisteOrdenConEseTipoOrden);
			
			if($numOrdenPorVale > 0)
			{
				if($row = mysql_fetch_assoc($rsVerificarSiExisteOrdenConEseTipoOrden))
				{
					$objResponse -> alert(sprintf("Este Vale ya tiene asociada un tipo de Orden %s. Por favor verifique y escoja otro tipo de Orden.", utf8_encode($row['nombre_tipo_orden'])));
					$objResponse->script("
						$('lstTipoOrden').value = '-1';
						$('lstTipoOrden').focus();");
						
					return $objResponse;
				}
			}
		}
	}
	else
	{
		$objResponse -> alert(utf8_encode("Este parametro no esta configurado en parametros generales. Por favor Contacte con el equipo del sistema ERP"));
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"validarTipoDocumento");

$xajax->register(XAJAX_FUNCTION,"nuevoDcto");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"guardarDcto");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"insertarArticulo");
$xajax->register(XAJAX_FUNCTION,"eliminarArticulo");

$xajax->register(XAJAX_FUNCTION,"listadoEmpresas");
$xajax->register(XAJAX_FUNCTION,"listadoClientes");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"listadoArticulos");
$xajax->register(XAJAX_FUNCTION,"listadoArticulosSustitutos");
$xajax->register(XAJAX_FUNCTION,"listadoArticulosAlternos");
$xajax->register(XAJAX_FUNCTION,"buscarDcto");
$xajax->register(XAJAX_FUNCTION,"listadoDctos");

$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarArticulo");

$xajax->register(XAJAX_FUNCTION,"cargaLstBusq");
$xajax->register(XAJAX_FUNCTION,"cargaLstSubSecciones");
$xajax->register(XAJAX_FUNCTION,"calculoPorcentajeDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstMoneda");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresaBusq");

$xajax->register(XAJAX_FUNCTION,"listado_paquetes_por_unidad");
$xajax->register(XAJAX_FUNCTION,"buscarPaquete");
$xajax->register(XAJAX_FUNCTION,"buscar_mano_obra_repuestos_por_paquete");

$xajax->register(XAJAX_FUNCTION,"listado_tempario_por_paquetes");

$xajax->register(XAJAX_FUNCTION,"listado_repuestos_por_paquetes");

$xajax->register(XAJAX_FUNCTION,"insertarPaquete");
$xajax->register(XAJAX_FUNCTION,"eliminarPaquete");

$xajax->register(XAJAX_FUNCTION,"cargaLstTipoOrden");
$xajax->register(XAJAX_FUNCTION,"asignarCliente");

$xajax->register(XAJAX_FUNCTION,"listadoClientes");

$xajax->register(XAJAX_FUNCTION,"buscarTempario");
$xajax->register(XAJAX_FUNCTION,"listado_tempario");
$xajax->register(XAJAX_FUNCTION,"asignarTempario");
$xajax->register(XAJAX_FUNCTION,"insertarTempario");
$xajax->register(XAJAX_FUNCTION,"eliminarTempario");
$xajax->register(XAJAX_FUNCTION,"insertarNota");
$xajax->register(XAJAX_FUNCTION,"eliminarNota");
$xajax->register(XAJAX_FUNCTION,"calcularTotalDcto");
$xajax->register(XAJAX_FUNCTION,"calcularTotalPaquete");
$xajax->register(XAJAX_FUNCTION,"verificarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarTot");
$xajax->register(XAJAX_FUNCTION,"listado_tot");
$xajax->register(XAJAX_FUNCTION,"asignarTot");
$xajax->register(XAJAX_FUNCTION,"insertarTot");
$xajax->register(XAJAX_FUNCTION,"eliminarTot");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");

$xajax->register(XAJAX_FUNCTION,"formClave");
$xajax->register(XAJAX_FUNCTION,"validarClaveDescuento");
$xajax->register(XAJAX_FUNCTION,"generarPresupuestoApartirDeOrden");
//$xajax->register(XAJAX_FUNCTION,"cargaTemparioDiagnostico");
$xajax->register(XAJAX_FUNCTION,"cargaLstSeccionTemp");
$xajax->register(XAJAX_FUNCTION,"cargaLstSubseccionTemp");
$xajax->register(XAJAX_FUNCTION,"contarItemsDcto");
$xajax->register(XAJAX_FUNCTION,"insertarDescuento");
$xajax->register(XAJAX_FUNCTION,"cargaLstDescuentos");
$xajax->register(XAJAX_FUNCTION,"cargarPorcAdicional");
$xajax->register(XAJAX_FUNCTION,"eliminarDescuentoAdicional");

$xajax->register(XAJAX_FUNCTION,"buscarUnidad");
$xajax->register(XAJAX_FUNCTION,"listadoUnidades");
$xajax->register(XAJAX_FUNCTION,"asignarUnidadBasica");

$xajax->register(XAJAX_FUNCTION,"diasVencimientoPresupuesto");
$xajax->register(XAJAX_FUNCTION,"verificarTipoOrdenPorVale");

$xajax->register(XAJAX_FUNCTION,"asignarPrecio");

                
?>