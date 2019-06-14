<?php
//LO USA AL GUARDAR FACTURA Y DEVOLVER FACTURA
//FUNCION AGREGADA EL 17-09-2012
function actualizarNumeroControl($idEmpresa, $idClaveMovimiento){
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
	WHERE id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
							WHERE clave_mov.id_clave_movimiento = %s)
		AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																		WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC
	LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$numeroActual = $rowNumeracion['numero_actual'];
			
	// ACTUALIZA LA NUMERACION DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) array(false, mysql_error()."\n\nLine: ".__LINE__."\nFile: ".basename(__FILE__));
	
	return array(true, "");
}

//ASIGNA Y MUESTRA LA INFORMACION AL SELECCIONARLO EN EL LISTADO DE RECEPCION, LO AGREGA A LA ORDEN
function asignarValeRecepcion($idRecepcion, $accion = "", $valFormTotalDcto){
	$objResponse = new xajaxResponse();
		
	if ($accion == "" || $accion == 1) {
		$queryVerificarSiTieneUnaOrdenSinAsignar = sprintf("SELECT
			COUNT(*) AS nr_items
		FROM sa_orden
		WHERE sa_orden.id_recepcion = %s
			AND sa_orden.id_tipo_orden = 5",
			$idRecepcion);
		$rsVerificarSiTieneUnaOrdenSinAsignar = mysql_query($queryVerificarSiTieneUnaOrdenSinAsignar);
		if (!$rsVerificarSiTieneUnaOrdenSinAsignar) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowVerificarSiTieneUnaOrdenSinAsignar = mysql_fetch_assoc($rsVerificarSiTieneUnaOrdenSinAsignar);
		
		if ($rowVerificarSiTieneUnaOrdenSinAsignar['nr_items'] > 0) {
			$objResponse->alert("No puede escoger este vale, debido a que tiene asociado un tipo de Orden SIN ASIGNAR.");
			return $objResponse;
		}
	}
	
	if ($valFormTotalDcto['hddItemsCargados'] > 0) {
		$objResponse->alert('La orden tiene items cargados. Si desea escoger otro vale de recepcion, elimine los items cargados e intente nuevamente.');
	} else {
		$queryRecepcion = sprintf("SELECT *
									FROM vw_sa_vales_recepcion
									WHERE id_recepcion = %s",
										valTpDato($idRecepcion,"text"));
		$rsRecepcion = mysql_query($queryRecepcion);
		if (!$rsRecepcion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowRecepcion = mysql_fetch_assoc($rsRecepcion);
		
		$queryCliente = "SELECT cj_cc_cliente.*, CONCAT_WS('-',lci, ci) AS ci_cliente
		FROM sa_orden
		INNER JOIN cj_cc_cliente ON sa_orden.id_cliente = cj_cc_cliente.id
		WHERE id_orden =".$_GET["id"];
		$rsCliente = mysql_query($queryCliente);
		if (!$rsCliente) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowCliente = mysql_fetch_assoc($rsCliente);



		$objResponse->assign("txtIdValeRecepcion","value",$rowRecepcion['id_recepcion']);
		$objResponse->assign("numeracionRecepcionMostrar","value",$rowRecepcion['numeracion_recepcion']);
		$objResponse->assign("txtFechaRecepcion","value",$rowRecepcion['fecha_entrada']);
		$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
		$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre']." ".$rowCliente['apellido']));
		$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($rowCliente['direccion']));
		$objResponse->assign("txtTelefonosCliente","value",utf8_encode($rowCliente['telf']));
		$objResponse->assign("txtChasisVehiculo","value",utf8_encode($rowRecepcion['chasis']));
		$objResponse->assign("txtPlacaVehiculo","value",utf8_encode($rowRecepcion['placa']));	
		$objResponse->assign("hddIdUnidadBasica","value",$rowRecepcion['id_uni_bas']);
		$objResponse->assign("txtUnidadBasica","value",utf8_encode($rowRecepcion['nom_uni_bas']));
		$objResponse->assign("txtMarcaVehiculo","value",utf8_encode($rowRecepcion['nom_marca']));
		$objResponse->assign("hddIdModelo","value",utf8_encode($rowRecepcion['id_modelo']));
		$objResponse->assign("txtModeloVehiculo","value",utf8_encode($rowRecepcion['des_modelo']));
		$objResponse->assign("txtAnoVehiculo","value",utf8_encode($rowRecepcion['ano_uni_bas']));
		$objResponse->assign("txtColorVehiculo","value",utf8_encode($rowRecepcion['color']));
		$objResponse->assign("txtRifCliente","value",utf8_encode($rowCliente['ci_cliente']));
		$objResponse->assign("txtKilometrajeVehiculo","value",utf8_encode($rowRecepcion['kilometraje']));
				
		$anio=substr($rowRecepcion['fecha_venta'],0,4);
		$mes=substr($rowRecepcion['fecha_venta'],5,2);
		$dia=substr($rowRecepcion['fecha_venta'],8,2);
		$fecha=$dia."-".$mes."-".$anio; 
		
		$objResponse->assign("txtFechaVentaVehiculo","value",$fecha);
		
		if ($_GET["acc"] == 1) {
			if($rowCliente['descuento'] > 0)
				$objResponse->script(sprintf("
				if(confirm('El Cliente tiene %s%s de Descuento Directo. Desea agregarlo?'))
					$('txtDescuento').value = %s;", $rowCliente['descuento'], "%", $rowCliente['descuento']));
		}
		
		/*
		if ($_GET['ret'] != 5){//este es el listado que molesta, gregor carga al iniciar y siempre elimina 
			if($_GET['doc_type'] != 3) {// 3 es facturacion			
				$objResponse->script("
				$('lstTipoOrden').value = '-1';
				$('lstTipoOrden').focus();");
			}
		}
		*/
			
		$objResponse->script("
		$('lstTipoOrden').focus();
		$('divFlotante2').style.display = 'none';
		$('divFlotante').style.display = 'none';");
		
		$objResponse->script("xajax_calcularTotalDcto();");
	}	
	
	return $objResponse;
}

//BUSCA LOS MOVIMIENTOS DEL ARTICULO, OJO EN REPUESTO EN ORDEN Y LUEGO MUESTRA EL LISTADO
function buscarMtoArticulo($idDetalleOrden, $codigoArticulo, $descripcionArticulo){
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		$idDetalleOrden,
		$codigoArticulo,
		$descripcionArticulo);
	
	$objResponse->script("xajax_verMovimientosArticulo('0','id_solicitud','DESC','".$valBusq."')");
	
	return $objResponse;	
}

//SE EJECUTA 2 VECES, NO CUANDO ES NUEVO Y CALCULA EL TOTAL AL ABRIR LA ORDEN
function calcularDcto($valFormDcto, $valForm, $valFormTotalDcto, $valFormPaq, $valFormTemp, $valFormNotas, $valFormTot){
	$objResponse = new xajaxResponse();
        
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
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
        
        $arrayIvasOrden = $valFormTotalDcto["ivaActivo"];//array con los checkbox activos ivas
        foreach($arrayIvasOrden as $indice => $idIvaActivo){//lleno array con los ivas disponibles
            $arrayIvasBaseImponible[$idIvaActivo] = 0;
            $arrayIvasSubTotal[$idIvaActivo] = 0;
        }

	$subTotal = 0;
	$totalExento = 0;
//	$totalExonerado = 0;
//	$arrayIva = NULL;
//	$arrayDetalleIva = NULL;
	$montoExento = 0;
	$baseImponible = 0;
	
	$totalTempTotal = 0;
	$totalArtTotal = 0;
	
        //PAQUETES
	if (isset($arrayObjPaq)) {
		foreach($arrayObjPaq as $indicePaq => $valorPaq) {
			$objResponse->assign(sprintf("hddValorCheckAprobPaq%s",$valorPaq),"value", 0);
			
			if (isset($valFormPaq['cbxItmPaqAprob'])) {
				foreach($valFormPaq['cbxItmPaqAprob'] as $indiceAprob => $valorAprob) {
					if ($valorPaq == $valorAprob) {
						$objResponse->assign(sprintf("hddValorCheckAprobPaq%s",$valorPaq),"value",1);
						
						$subTotalPaq = $valFormPaq['hddPrecPaq'.$valorPaq];//$valForm['hddCantArt'.$valor]*
						$descuentoPaq = ($valFormTotalDcto['txtDescuento'] * $subTotalPaq) / 100;
						$subTotalPaq = $subTotalPaq - $descuentoPaq;
						
						
						$subTotal += doubleval($valFormPaq['hddPrecPaq'.$valorPaq]);
						
						$totalTempTotal += doubleval($valFormPaq['hddTotalTempPqte'.$valorPaq]);
						$totalArtTotal += doubleval($valFormPaq['hddTotalRptoPqte'.$valorPaq]);
						
						$totalExento += doubleval($valFormPaq['hddTotalExentoRptoPqte'.$valorPaq]);
						
						$baseImponible += doubleval($valFormPaq['hddTotalTempPqte'.$valorPaq]);
                                                //$baseImponible += doubleval($valFormPaq['hddTotalConIvaRptoPqte'.$valorPaq]);//anterior directo
                                                
                                                //Nuevo por ivas
                                                $arrayIdIvasRepuestoPaquete = explode(",",$valFormPaq['hddIdIvasRepuestoPaquete'.$valorPaq]);
                                                $arrayIvasRepuestoPaquete = explode(",",$valFormPaq['hddIvasRepuestoPaquete'.$valorPaq]);//base imponible por iva
                                                $arrayPorcentajesRepuestoPaquete = explode(",",$valFormPaq['hddPorcentajesIvasRepuestoPaquete'.$valorPaq]);
                                                foreach($arrayIdIvasRepuestoPaquete as $key => $idIva){

                                                    $subTotalArtPaquete = $arrayIvasRepuestoPaquete[$key];
                                                    $descuentoArtPaquete = ($valFormTotalDcto['txtDescuento']*$subTotalArtPaquete)/100;
                                                    $subTotalArtPaquete = $subTotalArtPaquete - $descuentoArtPaquete;
                                                    $subTotalIvaArtPaquete = ($subTotalArtPaquete*$arrayPorcentajesRepuestoPaquete[$key])/100;//rollo devuelve 4 decimales y hace la orden 1 decimal de mas
                                                    

                                                    $arrayIvasBaseImponible[$idIva] += $subTotalArtPaquete;
                                                    $arrayIvasSubTotal[$idIva] += $subTotalIvaArtPaquete;
                                                }
						
						
					}	
				}
			}
		}
	}	
	
	//return $objResponse->alert($subTotal);
	//TOT
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
                
        //NOTAS
	if (isset($arrayObjNota)) {
		foreach($arrayObjNota as $indiceNota => $valorNota) {
		
			//SE INICIALIZAN EN 0 Y DESPUES SE LES COLOCA EL CHECK
			$objResponse->assign(sprintf("hddValorCheckAprobNota%s",$valorNota),"value", 0);
			if (isset($valFormNotas['cbxItmNotaAprob'])) {
				foreach($valFormNotas['cbxItmNotaAprob'] as $indiceAprob => $valorAprob) {
					if($valorNota == $valorAprob) {
						$objResponse->assign(sprintf("hddValorCheckAprobNota%s",$valorNota),"value", 1);
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
        
        //TEMPARIOS MANOS DE OBRA
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
		
        //REPUESTOS
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
		$objResponse->assign(sprintf("hddValorCheckAprobRpto%s", $valor),"value", 0);
		
		if (isset($valForm['cbxItmAprob'])) {

			foreach($valForm['cbxItmAprob'] as $indiceAprob => $valorAprob) {
				if($valor == $valorAprob) {//solo calcular los parobados
					$objResponse->assign(sprintf("hddValorCheckAprobRpto%s", $valor),"value", 1);

                                        //YA NO REQUERIDO COMENTADO
//					$queryIva = sprintf("SELECT * FROM pg_iva WHERE tipo= %s AND activo= %s AND estado= %s", 6, 1, 1);
//					$rsIva = mysql_query($queryIva);
//					if (!$rsIva) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
//					$rowIva = mysql_fetch_assoc($rsIva);
					
					if ($valForm['hddIdIvaArt'.$valor] == 0 && $valForm['hddIvaArt'.$valor] == "") {//SIN IVA EXENTO
						$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
						$totalExento += doubleval($subTotalArt);
						
						$montoExento = $montoExento + $valForm['hddPrecioArt'.$valor];
						$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
						$subTotalArt = $subTotalArt - $descuentoArt;
						
						
					} else {//CON IVA CALCULAR
                                        
                                            $arrayIdIvasRepuesto = explode(",",$valForm['hddIdIvaArt'.$valor]);
                                            $arrayIvasRepuesto = explode(",",$valForm['hddIvaArt'.$valor]);
                                            foreach($arrayIdIvasRepuesto as $key => $idIva){
                                                
                                                $subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
                                                $descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
                                                $subTotalArt = $subTotalArt - $descuentoArt;
                                                $subTotalIvaArt = ($subTotalArt*$arrayIvasRepuesto[$key])/100;
                                                
                                                $arrayIvasBaseImponible[$idIva] += $subTotalArt;
                                                $arrayIvasSubTotal[$idIva] += $subTotalIvaArt;
                                            }
                                            
                                            
                                            //COMENTADO GREGOR
//						$ivaArt =($valFormDcto['txtIdPresupuesto'] != "") ? $valForm['hddIvaArt'.$valor] : $rowIva['iva'];
//						
//						$existIva = "NO";
//						if (isset($arrayIva)) {
//							foreach($arrayIva as $indiceIva => $valorIva) {
//								if($arrayIva[$indiceIva][0] == $valForm['hddIdIvaArt'.$valor]) {
//									$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
//									$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
//									$subTotalArt = $subTotalArt - $descuentoArt;
//									$subTotalIvaArt = round(($subTotalArt*$ivaArt)/100,2);
//									
//									$arrayIva[$indiceIva][1] += $subTotalArt;
//									$arrayIva[$indiceIva][2] += $subTotalIvaArt;
//									$existIva = "SI";
//                                                                        $objResponse->alert("array iva ejecucion?");
//								}
//							}
//						}
//						
//						if ($rowIva['idIva'] != "" && $existIva == "NO" && ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]) > 0) {
//							$subTotalArt = ($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
//							$descuentoArt = ($valFormTotalDcto['txtDescuento']*$subTotalArt)/100;
//							$subTotalArt = $subTotalArt - $descuentoArt;
//							$subTotalIvaArt = round(($subTotalArt*$ivaArt)/100,2);
//							
//							$arrayDetalleIva[0] = $valForm['hddIdIvaArt'.$valor];							
//							$arrayDetalleIva[1] = $subTotalArt;
//							$arrayDetalleIva[2] = $subTotalIvaArt;
//							$arrayDetalleIva[3] = $ivaArt;
//							$arrayIva[] = $arrayDetalleIva;
//						}
						//COMENTADO, creo que debe usarse el del array
                                            //$baseImponible = $baseImponible + ($valForm['hddPrecioArt'.$valor] * $valForm['hddCantArt'.$valor]) ;
					}
					
					$subTotal += doubleval($valForm['hddTotalArt'.$valor]);
					$totalArtTotal += doubleval($valForm['hddCantArt'.$valor]*$valForm['hddPrecioArt'.$valor]);
                                        
					}
				}
			}
		}
	}	
        
	//print_r("-----------------------------".$totalArtTotal."-----------------------------------");
	if (isset($arrayObjDcto)) {
		foreach($arrayObjDcto as $indiceDcto => $valorDcto) {
			
			if($valFormTotalDcto['hddIdDcto'.$valorDcto] != "")
			{
				if($valFormTotalDcto['hddIdDcto'.$valorDcto] == 1)//DCTO MANO OBRA
					$descuentoAdicional = ($valFormTotalDcto['hddPorcDcto'.$valorDcto] * $totalTempTotal)/100;
				else 
					if($valFormTotalDcto['hddIdDcto'.$valorDcto] == 2)// DCTO REPUESTO
						$descuentoAdicional = ($valFormTotalDcto['hddPorcDcto'.$valorDcto] * $totalArtTotal)/100;
						
				$objResponse->assign(sprintf("txtTotalDctoAdcl%s", $valorDcto),"value", number_format($descuentoAdicional,2,".",","));
			}
			$totalDescuentoAdicional += doubleval($descuentoAdicional);
		}
	}
		
	//SIEMPRE TOMAR EL DEL DOCUMENTO        
        $iva_venta = count($arrayIvasOrden);
	
	//print_r("************************************".$totalArtTotal."**********************************************");
	$subTotalDescuento = ($subTotal * ($valFormTotalDcto['txtDescuento']/100));
        
        
        //contiene las bases imponibles de todos los items exceptuando repuestos que se calculan a parte
        $baseImponible -= $baseImponible*($valFormTotalDcto['txtDescuento']/100);//segun impresora
        
        if($iva_venta != 0 && $iva_venta != ""){//si tiene iva tomar repuestos con descuento
            $totalExento -= $totalExento*($valFormTotalDcto['txtDescuento']/100);//segun impresora            
        }else{//sino tiene iva todo seva a la base imponible
        //anterior
//            $baseImponible += $totalExento;
//            $totalExento = 0;
            //ahora
            $totalExento +=$baseImponible;
            $totalExento -= $totalExento*($valFormTotalDcto['txtDescuento']/100);//segun impresora 2
            $baseImponible = 0;            
        }
               
        //resumando los items restantes, a los repuestos de las bases imponibles
        foreach($arrayIvasBaseImponible as $keyIdIva => $baseImponibleIvas){
            $arrayIvasBaseImponible[$keyIdIva] += $baseImponible;
            $arrayIvasSubTotal[$keyIdIva] += ($baseImponible*$valFormTotalDcto["txtIvaVenta".$keyIdIva])/100;            
        }

$recalculoIvas = array();

	foreach($arrayIvasBaseImponible as $keyIdIva => $baseImponibleIvas){
            $recalculoIvas[$keyIdIva] += round(($baseImponibleIvas*$valFormTotalDcto["txtIvaVenta".$keyIdIva])/100,2);            
        }


$objResponse->script("console.log(".json_encode($arrayIvasSubTotal).")");
        
	//$totalIva = $baseImponible * ($iva_venta/100);
        $totalIva = array_sum($recalculoIvas);          //redondeo subtotal descuento sino da 1 decimal de mas
	$totalPresupuesto = doubleval($subTotal) - doubleval(round($subTotalDescuento,2)) - doubleval($totalDescuentoAdicional) + doubleval($gastosConIva) + doubleval($subTotalIva) +  doubleval($gastosSinIva) + doubleval($totalIva);
	$objResponse->script("console.log(".json_encode($totalIva).")");
        
        //se le resta el % de descuento
//        $descuentoExento = round($totalExento*($nuevoPorcentajeDescuento/100),2);
//        $totalExento = round($totalExento-$descuentoExento,2);
	
        function totalesOrden($total){
            //return round($total,2);
            return number_format(round($total,2),2,".",",");
        }
	//$objResponse->assign("hddIdIvaVenta","value",$id_iva_venta);
	$objResponse->assign("txtSubTotal","value",totalesOrden($subTotal));
	$objResponse->assign("txtSubTotalDescuento","value",totalesOrden($subTotalDescuento));
	//$objResponse->assign("txtIvaVenta","value", $iva_venta);
	//$objResponse->assign("txtTotalIva","value",totalesOrden($totalIva));
	$objResponse->assign("txtTotalPresupuesto","value",totalesOrden($totalPresupuesto));	
	$objResponse->assign('txtGastosConIva',"value",totalesOrden($gastosConIva));
	$objResponse->assign('txtMontoExento',"value",totalesOrden($totalExento));
	$objResponse->assign('txtBaseImponible',"value",totalesOrden($baseImponible));
        
        $objResponse->assign("txtTotalFactura","value",totalesOrden($totalPresupuesto));
	$objResponse->assign("txtMontoPorPagar","value",totalesOrden($totalPresupuesto));
	
        foreach($arrayIvasBaseImponible as $keyIdIva => $baseImponibleIvas){
            $objResponse->assign('txtBaseImponibleIva'.$keyIdIva,"value",totalesOrden($baseImponibleIvas));
            $objResponse->assign('txtTotalIva'.$keyIdIva,"value",totalesOrden($recalculoIvas[$keyIdIva]));            
	    
	    $objResponse->assign('txtBaseImponible',"value",totalesOrden($baseImponibleIvas));//solo rrellenar, usado en vzla simple iva
        }
        
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
	
	$objResponse->script("xajax_contarItemsDcto(xajax.getFormValues('frmTotalPresupuesto'), xajax.getFormValues('frm_agregar_paq'));");
	
	return $objResponse;
}

//SE EJECUTA 2 VECES, NO CUANDO ES NUEVO, LLAMA CALCULARDCTO DE ARRIBA OTRA VEZ
function calcularTotalDcto(){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmPresupuesto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalPresupuesto'),xajax.getFormValues('frm_agregar_paq'),xajax.getFormValues('frmListaManoObra'),xajax.getFormValues('frmListaNota'), xajax.getFormValues('frmListaTot'));");
		
	return $objResponse;
}

//ES UN LIST, CARGA EN EL DOCUMENTO, NO LO MUESTRA, LO USA GUARDAR FACTURA Y DEVOLVER VALE DE SALIDA
function cargaLstClaveMovimiento($idTipoClave, $selId = ""){
	$objResponse = new xajaxResponse();

	if ($selId != "") {
		
		$queryClaveMto = sprintf("SELECT 
			sa_tipo_orden.id_clave_movimiento,
			sa_tipo_orden.id_clave_movimiento_dev
		FROM sa_tipo_orden
		WHERE sa_tipo_orden.id_tipo_orden = %s", $selId);
		$rsClaveMto = mysql_query($queryClaveMto);
		$rowClaveMto = mysql_fetch_assoc($rsClaveMto); 
		
		if($idTipoClave == "ORDEN"){
			$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s ORDER BY descripcion",
			valTpDato($rowClaveMto['id_clave_movimiento_dev'], "int"));
		}else{
			$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s ORDER BY descripcion",
			valTpDato($rowClaveMto['id_clave_movimiento'], "int"));
		}
	} else {
		$query = sprintf("SELECT * FROM pg_clave_movimiento WHERE tipo = %s AND id_modulo = 0 ORDER BY descripcion",
			valTpDato($idTipoClave, "int"));
	}
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert("Error cargaLstClaveMovimiento \n".mysql_error()."\n Linea: ".__LINE__);
	
	$html = "<select id=\"lstClaveMovimiento\" name=\"lstClaveMovimiento\">";
		//$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['tipo']) ? "selected='selected'" : "";
		if($idTipoClave == "ORDEN"){//dev
			$html .= "<option ".$selected." value=\"".$row['id_clave_movimiento']."\">".utf8_encode($row['descripcion'])."</option>";
		}else{
			$html .= "<option ".$selected." value=\"".$row['id_clave_movimiento']."\">".utf8_encode($row['descripcion'])."</option>";
		}
	}
	$html .= "</select>";
	
	$objResponse->script("$('lstTipoClave').disabled = true;");
	$objResponse->assign("tdlstClaveMovimiento","innerHTML",$html);
	
	if($idTipoClave == "ORDEN"){
		$objResponse->script("xajax_buscarNumeroControl($('txtIdEmpresa').value, ".$rowClaveMto['id_clave_movimiento_dev'].", 'txtNroControl');");
	}else{
		$objResponse->script("xajax_buscarNumeroControl($('txtIdEmpresa').value, ".$rowClaveMto['id_clave_movimiento'].", 'txtNroControl');");
	}
	return $objResponse;
}

//ES EL LISTADO DE TIPOS DE ORDEN, MODIFICADO PARA QUE TRAIGA SIEMPRE EL DE LA ORDEN
function cargaLstTipoOrden($selId = ""){
	$objResponse = new xajaxResponse();
	
	$html = "<select id=\"lstTipoOrden\" name=\"lstTipoOrden\" class=\"divMsjInfo2\">";
	
	if($selId != "" || $selId != NULL || $selId != "-1"){
		$sql = sprintf("SELECT id_tipo_orden, nombre_tipo_orden FROM sa_tipo_orden WHERE id_tipo_orden = %s LIMIT 1",
									valTpDato($selId, "int"));
		$query = mysql_query($sql);
		if(! $query) { return $objResponse->alert("Error listado tipo de orden: \n".mysql_error()."\n Sql: ".$sql."\n Linea: ".__LINE__); }
		
		$row = mysql_fetch_assoc($query);
		
		$html .= sprintf("<option selected=\"selected\" value=\"%s\">%s</option>",
									$row["id_tipo_orden"],
									utf8_encode($row["nombre_tipo_orden"]));
	}
		
	$html .= "</select>";
	
	$objResponse->assign("tdlstTipoOrden","innerHTML",$html);
	
	return $objResponse;
}

//CARGA TODA LA INFORMACION DEL DOCUMENTO, SI ES NUEVO DOCUMENTO NO SE USA
function cargarDcto($idDocumento, $valFormTotalDcto) {
	$objResponse = new xajaxResponse();

	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
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
	$rsUsuario = mysql_query($queryUsuario);
	if (!$rsUsuario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowUsuario = mysql_fetch_assoc($rsUsuario);
	
	if ($valFormTotalDcto['hddTipoDocumento'] == 1) { //PRESUPUESTO
		//solo servicios		
	} else {
		
		$query = sprintf("SELECT * FROM vw_sa_orden WHERE id_orden = %s",
			valTpDato($idDocumento, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		$numeroOrdenMostrar = $row["numero_orden"];
		$idEmpresaOrden = $row["id_empresa"];
		
		$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
			valTpDato($idEmpresaOrden, "int"));
		$rsEmp = mysql_query($queryEmp);
		if (!$rsEmp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowEmp = mysql_fetch_assoc($rsEmp);
		
		$empresaOrden = $rowEmp["nombre_empresa"];
		
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
		
		$tablaDocDetDescuento = "sa_det_orden_descuento";
		$campoTablaIdDetDescuento = "id_det_orden_descuento";
		
		$campoTablaIdDetNotaRelacOrden = "id_det_orden_nota AS id_det_orden_nota_ref";
		$campoTablaIdDetTotRelacOrden = "id_det_orden_tot AS id_det_orden_tot_ref";
		$campoTablaIdDetTempRelacOrden = "id_det_orden_tempario AS id_det_orden_tempario_ref";
		$campoTablaIdDetArtRelacOrden = "id_det_orden_articulo AS id_det_orden_articulo_ref";
		
		$fechaDocumento = $row['fecha_orden'];
		
		if ($row['porcentaje_descuento'] != NULL || $row['porcentaje_descuento'] != "")
			$descuento = $row['porcentaje_descuento'];
		else
			$descuento = 0;
			
		$idTipoOrden = $row['id_tipo_orden'];
		$estado_orden = $row['nombre_estado'];
               
		$id_iva = $row['idIva'];
		$iva = $row['iva'];
		
		//////////
		//CONSULTO LA CLAVE DE MOVIMIENTO SEGUN EL TIPO DE LA ORDEN
		$queryTipoDoc = sprintf("SELECT * FROM sa_tipo_orden WHERE id_tipo_orden = %s",
			valTpDato($idTipoOrden, "int"));
		$rsTipoDoc = mysql_query($queryTipoDoc);
		if (!$rsTipoDoc) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowTipoDoc = mysql_fetch_assoc($rsTipoDoc);
		
		$idClaveMovimiento = $rowTipoDoc["id_clave_movimiento"];
		$idFiltroTipoOrden =  $rowTipoDoc['id_filtro_orden'];
		
		//CONSULTO EL TIPO DE DOCUMENTO QUE GENERA Y SI ES DE CONTADO
		$queryDocGenera = sprintf("SELECT * FROM pg_clave_movimiento
		WHERE id_clave_movimiento = %s
			AND documento_genera = %s
			AND pago_contado = %s",
			valTpDato($idClaveMovimiento, "int"),
			valTpDato(1, "int"), // 1 = FACTURA
			valTpDato(1, "int")); // 0 = NO ; 1 = SI
		$rsDocGenera = mysql_query($queryDocGenera);
		if (!$rsDocGenera) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowDocGenera = mysql_fetch_assoc($rsDocGenera);
		if (mysql_num_rows($rsDocGenera) > 0 && $idFiltroTipoOrden != 3) {
			
	//SOLO MUESTRA FORMA DE PAGO Y DESGLOSE DE PAGOS SI ES UNA ORDEN DE TIPO SEA DE CONTADO Y NO ES UNA DEVOLUCION (DEV), Y NO SEA GARANTIA
			if (!isset($_GET['dev'])) {
				$objResponse->script("byId('trFormaDePago').style.display = '';
									byId('trDesgloseDePagos').style.display = '';");		
			} else { //NO MUESTRA EL FORMA DE PAGO Y DESGLOSE DE PAGOS MIENTRAS NO SEA DE CONTADO Y SEA UNA DEVOLUCION (DEV)
				$objResponse->script("byId('trFormaDePago').style.display = 'none';
									byId('trDesgloseDePagos').style.display = 'none';");
			}
		}
		///////
	}
		
	$itemsNoChecados = 0;
	
	if ($_GET['acc_ret'] == 2 || $_GET['ret'] != 5) { //RETRABAJO
		$objResponse->script(sprintf("xajax_asignarValeRecepcion(%s, %s, xajax.getFormValues('frmTotalPresupuesto'))", $row['id_recepcion'], $_GET['acc']));
	} else {
		$objResponse->script("xajax_calcularTotalDcto();");
	}
	
	if($valFormTotalDcto['hddAccionTipoDocumento'] == 2)
		$check_disabled = "disabled='disabled'";
	else
		$check_disabled = "";
		
	if($_GET['dev'] == 1)
		$condicionMostrarArticulosFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetArt);
	else
		$condicionMostrarArticulosFacturados = "";
		
	if($_GET['acc_ret'] != 1) { //CUANDO SE GENERA LA ORDEN RETRABAJO
		$queryRepuestosGenerales = sprintf("SELECT
			%s.%s,                        
			%s.cantidad,
			%s.id_articulo_costo,
			%s.id_articulo_almacen_costo,
			%s.id_precio,
			%s.precio_unitario,
			(SELECT GROUP_CONCAT(id_iva) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.%s) as id_iva,
			(SELECT GROUP_CONCAT(iva) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.%s) as iva,
			%s.id_articulo,
			%s.%s,
			%s.aprobado,
			%s.costo,			
			iv_tipos_articulos.descripcion AS descripcion_tipo,
			iv_articulos.descripcion AS descripcion_articulo,
			iv_secciones.descripcion AS descripcion_seccion,
			iv_subsecciones.id_subseccion,
			iv_articulos.codigo_articulo
		FROM iv_articulos
			INNER JOIN %s ON (iv_articulos.id_articulo = %s.id_articulo)
			INNER JOIN iv_subsecciones ON (iv_articulos.id_subseccion = iv_subsecciones.id_subseccion)
			INNER JOIN iv_tipos_articulos ON (iv_articulos.id_tipo_articulo = iv_tipos_articulos.id_tipo_articulo)
			INNER JOIN iv_secciones ON (iv_subsecciones.id_seccion = iv_secciones.id_seccion)
		WHERE %s.%s = %s
			AND %s.id_paquete IS NULL %s
			AND %s.estado_articulo <> 'DEVUELTO'",
			$tablaDocDetArt, $campoTablaIdDetArtRelacOrden,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt, $campoTablaIdDetArt,//id ivas
			$tablaDocDetArt, $campoTablaIdDetArt,//porc ivas
			$tablaDocDetArt,
			$tablaDocDetArt, $campoTablaIdDetArt,
			$tablaDocDetArt, //costo no estaba y se lo agregue
			$tablaDocDetArt,
			$tablaDocDetArt,
			$tablaDocDetArt,
			
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento, "int"),
			$tablaDocDetArt, $condicionMostrarArticulosFacturados,
			$tablaDocDetArt);	
		$rsDetRep = mysql_query($queryRepuestosGenerales);
		
		if (!$rsDetRep) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$sigValor = 1;
		$arrayObj = NULL;
		
		$readonly_check_ppal_list_repuesto = 0;
		$tieneRptoSinSolicitud = 0;
		while ($rowDetRep = mysql_fetch_assoc($rsDetRep)) {
			$repuestosTomadosEnSolicitud2 = 0;
			
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			$caracterIva = ($rowDetRep['id_iva'] != "") ? str_replace(",","% - ",$rowDetRep['iva'])."%" : "NA";
			
                        
			if ($_GET['cons'] == 1) { // FACTURACION
				// SI ESTAN EN SOLICITUD Y APROBADO
				$queryCont = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
				WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s
					AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3 OR sa_det_solicitud_repuestos.id_estado_solicitud = 5) ", 
				$rowDetRep['id_det_orden_articulo_ref']);
				$rsCont = mysql_query($queryCont);
				$rowCont = mysql_fetch_assoc($rsCont);
                                
                                //OJO SE USA TAMBIEN PARA CALCULAR TOTALES
				if (!$rsCont)
					return $objResponse->alert("Error cargarDcto \n".mysql_error().$rsCont."\n Linea: ".__LINE__);
				else {
					if ($rowCont['nro_rpto_desp'] != NULL || $rowCont['nro_rpto_desp'] != "")
						$cantidad_art = $rowCont['nro_rpto_desp'];
					else
						$cantidad_art = 0;
				}
				
				$queryContTotal = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
				WHERE sa_det_solicitud_repuestos.id_det_orden_articulo = %s", 
					$rowDetRep['id_det_orden_articulo_ref']);
				$rsContTotal = mysql_query($queryContTotal);
				$rowContTotal = mysql_fetch_assoc($rsContTotal);
				
				$cantidad_art_total = $rowContTotal['nro_rpto_desp'];
			} else {
				$cantidad_art_total = $rowDetRep['cantidad'];
				$cantidad_art = $rowDetRep['cantidad'];
			}	
			
			$query = sprintf("SELECT *
			FROM sa_det_orden_articulo
				INNER JOIN sa_det_solicitud_repuestos ON (sa_det_orden_articulo.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
				INNER JOIN sa_solicitud_repuestos ON (sa_det_solicitud_repuestos.id_solicitud = sa_solicitud_repuestos.id_solicitud)
			WHERE sa_det_orden_articulo.id_det_orden_articulo = %s
				AND sa_det_solicitud_repuestos.id_estado_solicitud IS NOT NULL
				AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO'",
				valTpDato($rowDetRep['id_det_orden_articulo_ref'], "int"));//AND sa_solicitud_repuestos.estado_solicitud != 0
			$rs = mysql_query($query);
                        
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
					
			if ($row['id_det_solicitud_repuesto'] != '')
				$repuestosTomadosEnSolicitud2 = 1;
				
			if ($rowDetRep['aprobado'] == 1) {
				//si hay por lo menos uno aprobado que desabilite directamente el check principal, ya q los valores tienden a reemplazarlos
				$checkedArt = "checked='checked'";
				$value_checkedArt = 1;

				if($repuestosTomadosEnSolicitud2 == 1)
					$disabledArt = "disabled='disabled'";
				
				if ($valFormTotalDcto['hddAccionTipoDocumento']!=4) {
					$readonly_check_ppal_list_repuesto = 1;
					$displayArt = "style='display:none;'";
					$imgCheckDisabledArt = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
				} else {
					if($repuestosTomadosEnSolicitud2 == 1) {
						$readonly_check_ppal_list_repuesto = 1;
						$displayArt = "style='display:none;'";
						$imgCheckDisabledArt = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
					} else {
						$displayArt = "";
						$imgCheckDisabledArt = "";
						$tieneRptoSinSolicitud = 1;
					}
				}
			} else {
				$itemsNoChecados = 1;
				$checkedArt = " ";
				$value_checkedArt = 0;
				$disabledArt = "";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayArt = "style='display:none;'";
					$imgCheckDisabledArt = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
					$readonly_check_ppal_list_repuesto = 1;
				} else {
					$displayArt = "";
					$imgCheckDisabledArt = "";
					$tieneRptoSinSolicitud = 1;
				}
			}

			$solicitudRep = "";

			$sqlSolicitudRep= "SELECT * FROM sa_det_solicitud_repuestos
			WHERE id_det_orden_articulo = ".$rowDetRep['id_det_orden_articulo_ref']."
				AND id_estado_solicitud IN (1,2)";
			$rsSolicitudRep= mysql_query($sqlSolicitudRep);
			if (!$rsSolicitudRep) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowSolicitudRep= mysql_fetch_assoc($rsSolicitudRep);

			if ($rowSolicitudRep) {
				$solicitudRep= "<span style='color:red'> (S) </span>";
			}
                        
                        //CALCULOS MULTIPLES IVAS
                        $arrayIdIvasRep = explode(",",$rowDetRep['id_iva']);
                        $arrayPorcIvasRep = explode(",",$rowDetRep['iva']);
                        $montoMultipleIva = 0;
                        foreach($arrayIdIvasRep as $key => $idIvasRep){
                            $montoMultipleIva += ($cantidad_art * $rowDetRep['precio_unitario'] * $arrayPorcIvasRep[$key] / 100);
                        }
                        $montoMultipleIva = $montoMultipleIva + $cantidad_art * $rowDetRep['precio_unitario'];

					//imprime lo de solicitud, costo promedio tambien gregor
			$objResponse->script(sprintf("			
			var elemento = new Element('tr', {'id':'trItm:%s', 'class':'textoGris_11px %s', 'height':'24', 'title':'trItm:%s'}).adopt([
				new Element('td', {'align':'right', 'id':'tdItmRep:%s', 'class':'color_column_insertar_eliminar_item'
}).setHTML(\"<input id='cbxItm' name='cbxItm[]' type='checkbox' value='%s' %s />\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center', 'class':'noRomper'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"<b>%s</b>\"),
				new Element('td', {'align':'center'}).setHTML(\"<img id='imgVerMtoArticulo:%s' src='../img/iconos/ico_view.png' class='puntero noprint'/>".
					"<input type='hidden' id='hddIdPedDet%s' name='hddIdPedDet%s' value='%s'/>".
					"<input type='hidden' id='hddIdArt%s' name='hddIdArt%s' value='%s'/>".
					"<input type='hidden' id='hddIdArtCosto%s' name='hddIdArtCosto%s' value='%s'/>".
					"<input type='hidden' id='hddIdArtAlmacenCosto%s' name='hddIdArtAlmacenCosto%s' value='%s'/>".
					"<input type='hidden' id='hddCantArt%s' name='hddCantArt%s' value='%s'/>".
					"<input type='hidden' id='hddIdPrecioArt%s' name='hddIdPrecioArt%s' value='%s'/>".
					"<input type='hidden' id='hddPrecioArt%s' name='hddPrecioArt%s' value='%s'/>".
					"<input type='hidden' id='hddCostoArt%s' name='hddCostoArt%s' value='%s'/>".//costo
					"<input type='hidden' id='hddIdIvaArt%s' name='hddIdIvaArt%s' value='%s'/>".
					"<input type='hidden' id='hddIvaArt%s' name='hddIvaArt%s' value='%s'/>".
					"<input type='hidden' id='hddTotalArt%s' name='hddTotalArt%s' value='%s'/>\"),
				new Element('td', {'align':'center', 'id':'tdItmRepAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmAprob' name='cbxItmAprob[]' type='checkbox' value='%s' %s onclick='xajax_calcularTotalDcto();' %s/> %s".
					"<input type='hidden' id='hddValorCheckAprobRpto%s' name='hddValorCheckAprobRpto%s' value='%s'/>".
					"<input type='hidden' id='hddRptoEnSolicitud%s' name='hddRptoEnSolicitud%s' value='%s'/>".
					"<input type='hidden' id='hddRptoTomadoSolicitud2%s' name='hddRptoTomadoSolicitud2%s' value='%s'/>\")	
			]);
			elemento.injectBefore('trItmPie');
			
			$('imgVerMtoArticulo:%s').onclick=function(){
				xajax_buscarMtoArticulo('%s', '%s', '%s');
			}",
			$sigValor, $clase, $sigValor,
				$sigValor, $sigValor, $disabledArt,
				elimCaracter($rowDetRep['codigo_articulo'], ";"),
				utf8_encode($solicitudRep.$rowDetRep['descripcion_articulo']),
				$rowDetRep['id_articulo_costo'],
				$cantidad_art_total,
				//$sigValor, $cantidad_art, // Articulos Despachados
				number_format($rowDetRep['precio_unitario'],2,".",","),
				$caracterIva,
				//number_format(($cantidad_art * $rowDetRep['precio_unitario']),2,".",","),//anterior ahora:
				//agregado iva nuevo gregor
				number_format(($cantidad_art * $rowDetRep['precio_unitario']),2,".",","),
				number_format($montoMultipleIva,2,".",","),
				
				$sigValor, 
					$sigValor, $sigValor, $rowDetRep['id_det_orden_articulo_ref'],
					$sigValor, $sigValor, $rowDetRep['id_articulo'],
					$sigValor, $sigValor, $rowDetRep['id_articulo_costo'],
					$sigValor, $sigValor, $rowDetRep['id_articulo_almacen_costo'],
					$sigValor, $sigValor, $cantidad_art,
					$sigValor, $sigValor, $rowDetRep['id_precio'],
					$sigValor, $sigValor, $rowDetRep['precio_unitario'],
					$sigValor, $sigValor, $rowDetRep['costo'],					
					$sigValor, $sigValor, $rowDetRep['id_iva'],
					$sigValor, $sigValor, $rowDetRep['iva'],
					$sigValor, $sigValor, ($cantidad_art*$rowDetRep['precio_unitario']),
				$sigValor, $sigValor, $checkedArt, $displayArt, $imgCheckDisabledArt,
					$sigValor, $sigValor, $value_checkedArt,
					$sigValor, $sigValor, $row['id_det_solicitud_repuesto'],
					$sigValor, $sigValor, $repuestosTomadosEnSolicitud2,
					
				$sigValor,
					$rowDetRep['id_det_orden_articulo_ref'], $rowDetRep['codigo_articulo'], utf8_encode($rowDetRep['descripcion_articulo'])));
			
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1)
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
			else if ($valFormTotalDcto['hddAccionTipoDocumento']==3)
				$objResponse->script(sprintf("
				$('tdInsElimRep').style.display = '';
				$('tdItmRep:%s').style.display = '';
				$('tdItmRepAprob:%s').style.display='';",
					$sigValor,
					$sigValor));
			
//			if($_GET['cons'] == 1) {
//				$objResponse->script(sprintf("$('tdCantidadArtDesp%s').style.display = '';",
//					$sigValor));
//			} else {
//				$objResponse->script(sprintf("$('tdCantidadArtDesp%s').style.display = 'none';",
//					$sigValor));
//			}
			
			$arrayObj[] = $sigValor;
			$sigValor++;
		}
		
//		if ($_GET['cons'] == 1) {
//			$objResponse->script(sprintf("$('tdDespachado').style.display = '';"));
//		} else {
//			$objResponse->script(sprintf("$('tdDespachado').style.display = 'none';"));
//		}
			
		if ($repuestosTomadosEnSolicitud2 == 1) {
			$objResponse->assign("tdInsElimRep","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
		
		
		if($readonly_check_ppal_list_repuesto == 1) {
			$objResponse->script("$('cbxItmAprob').style.display = 'none';");
			$objResponse->assign("tdRepAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			//$objResponse->assign("tdInsElimRep","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
				
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if ($sigValor == 1) {
				$objResponse->script("
				$('frmListaArticulo').style.display='none';
				$('tblListaArticulo').style.display='none';");
			}
			
			/*if($tieneRptoSinSolicitud == 1)
				$objResponse->script("
					$('lstMecSolRptoBusq').disabled = false;");*/
		} else if ($valFormTotalDcto['hddTipoDocumento'] == 1) { //PRESUPUESTO
			if ($sigValor == 1) {
				$objResponse->script("
				$('frmListaArticulo').style.display='none';
				$('tblListaArticulo').style.display='none';");
			}
		}
		
		if ($_GET['cons'] == 1) { // CONTROL DE ARTICULOS DESPACHADOS (LOS QUE SE VAN A FACTURAR)
			$adnlQuery = sprintf("SELECT 
				SUM(sa_det_orden_articulo.precio_unitario) AS TOTAL
			FROM sa_paquetes
				INNER JOIN sa_det_orden_articulo ON (sa_paquetes.id_paquete = sa_det_orden_articulo.id_paquete)
				INNER JOIN sa_det_solicitud_repuestos ON (sa_det_orden_articulo.id_det_orden_articulo = sa_det_solicitud_repuestos.id_det_orden_articulo)
			WHERE sa_det_orden_articulo.id_orden = %s
				AND sa_det_orden_articulo.id_paquete = idPaq
				AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO'
				AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
					OR sa_det_solicitud_repuestos.id_estado_solicitud = 5) ",
				valTpDato($idDocumento, "int"));
		} else {
			$adnlQuery = sprintf("SELECT 
				SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
			FROM sa_paquetes
				INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
			WHERE %s.%s = %s
				AND %s.id_paquete = idPaq
				AND %s.estado_articulo <> 'DEVUELTO'", 
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento, "int"), $tablaDocDetArt, $tablaDocDetArt, $tablaDocDetArt);
		}
				
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
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) = 0
						AND %s.estado_articulo <> 'DEVUELTO'
						AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
							OR sa_det_solicitud_repuestos.id_estado_solicitud = 5))
				ELSE
					(SELECT SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) = 0
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
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) > 0
						AND %s.estado_articulo <> 'DEVUELTO'
						AND (sa_det_solicitud_repuestos.id_estado_solicitud = 3
							OR sa_det_solicitud_repuestos.id_estado_solicitud = 5))
				ELSE
					(SELECT SUM(%s.cantidad * %s.precio_unitario) AS TOTAL
					FROM sa_paquetes
						INNER JOIN %s ON (sa_paquetes.id_paquete = %s.id_paquete)
					WHERE %s.%s = %s
						AND %s.id_paquete = idPaq
						AND (SELECT COUNT(*) FROM sa_det_orden_articulo_iva WHERE sa_det_orden_articulo_iva.id_det_orden_articulo = %s.id_det_orden_articulo) > 0
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
		$tablaEnc, $campoIdEnc, valTpDato($idDocumento, "int"),
		
		
			$tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento, "int"),
		 		$tablaDocDetArt, 
				$tablaDocDetArt,
				$tablaDocDetArt,
				
			$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento, "int"),
		 		$tablaDocDetArt, 
				$tablaDocDetArt,
				$tablaDocDetArt,
		
		//select id_estado_orden
		$tablaEnc, $campoIdEnc, valTpDato($idDocumento, "int"),
		
				
			$tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento, "int"),
				$tablaDocDetArt,
				$tablaDocDetArt,
				$tablaDocDetArt,
			
			$tablaDocDetArt, $tablaDocDetArt,
				$tablaDocDetArt, $tablaDocDetArt,
			$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento, "int"),
				$tablaDocDetArt,
				$tablaDocDetArt,
				$tablaDocDetArt,
			
			
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento, "int"), $tablaDocDetTemp, $tablaDocDetTemp,
			$adnlQuery,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento, "int"), $tablaDocDetTemp,  $tablaDocDetTemp,
			$adnlQuery,
			$tablaDocDetTemp, $tablaDocDetTemp,
			$tablaDocDetArt, $tablaDocDetArt,
			$tablaEnc, $tablaDocDetTemp, $campoIdEnc, $tablaEnc, $campoIdEnc,
			$tablaEnc, $campoIdEnc, $tablaDocDetArt, $campoIdEnc,
			$tablaEnc, $campoIdEnc, valTpDato($idDocumento, "int"),
			//aqui nuevo gregor UNION 
			$tablaDocDetArt, $tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento, "int"), $tablaDocDetArt,
			$tablaDocDetTemp, $tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento, "int"), $tablaDocDetTemp
			);
			
		$rsDetPaq = mysql_query($queryDetPaq);
		if (!$rsDetPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryDetPaq);
		
		$sigValor = 1;
		$arrayObjPaq = NULL;
		
		$readonly_check_ppal_list_paquete = 0;
		$tieneRptoSinSolicitudPaq = 0;
		while ($rowDetPaq = mysql_fetch_assoc($rsDetPaq)) {//de cada paquete se consulta sus items
			
			//$objResponse->alert($rowDetPaq['precio_paquete']);
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
			$sqlManoObraPaq = sprintf("SELECT 
				%s.id_tempario,
				%s.aprobado,
				%s.id_det_orden_tempario
			FROM %s
			WHERE %s.%s = %s
				AND %s.id_paquete = %s
				AND %s.estado_tempario <> 'DEVUELTO'",
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento, "int"), $tablaDocDetTemp, valTpDato($rowDetPaq['idPaq'], "int"),  $tablaDocDetTemp);
			$rsManoObraPaq = mysql_query($sqlManoObraPaq);
			if (!$rsManoObraPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					
			//VALIDACION DE APROBADO POR MANO DE OBRA. DE ESTO DEPENDE SI EL PAQUETE ES O NO APROBADO
			
			// SI LA ACCION ES APROBAR QUE NO LO MUESTRE ASI
			$sqlNroManoObraPaq = sprintf("SELECT 
				COUNT(*) AS numeroMo,
				%s.id_det_orden_tempario
			FROM %s
			WHERE %s.%s = %s
				AND %s.id_paquete = %s
				AND %s.aprobado = 1
				AND %s.estado_tempario <> 'DEVUELTO'
			GROUP BY id_det_orden_tempario",
				$tablaDocDetTemp,
				$tablaDocDetTemp,
				$tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento, "int"),
				$tablaDocDetTemp, valTpDato($rowDetPaq['idPaq'], "int"),
				$tablaDocDetTemp,
				$tablaDocDetTemp);				
			$rsNroManoObraPaq = mysql_query($sqlNroManoObraPaq);
			if (!$rsNroManoObraPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowNroManoObraPaq = mysql_fetch_array($rsNroManoObraPaq);
							
			// CUANDO LO VAYA A GUARDAR NO LO PUEDE VOLVER A TOMAR PUESTO QUE YA ESTA GUARDADO, ESTA MISMA CONSULTA COLOCARLA EN GUARDAR 
			
			$repuestosTomadosEnSolicitud = 0;
				
			$sqlRepuestoPaq = sprintf("SELECT 
				%s.id_articulo,
				%s.%s
			FROM %s
			WHERE %s.%s = %s
				AND %s.id_paquete = %s
				AND estado_articulo <> 'DEVUELTO'",
				$tablaDocDetArt,
				$tablaDocDetArt, $campoTablaIdDetArtRelacOrden,
				$tablaDocDetArt, 
				$tablaDocDetArt, $campoIdEnc, valTpDato($idDocumento, "int"),
				$tablaDocDetArt, valTpDato($rowDetPaq['idPaq'], "int"));
			$rsRepuestoPaq = mysql_query($sqlRepuestoPaq);
			if (!$rsRepuestoPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
						
			$tieneRepuestos = mysql_num_rows($rsRepuestoPaq);
						
			$cadenaRepPaq = "";
			while ($valorRepuestoPaq = mysql_fetch_array($rsRepuestoPaq)) {
				$cadenaRepPaq .= "|".$valorRepuestoPaq['id_articulo'];
				
				$query = sprintf("SELECT det_sol_rep.id_det_solicitud_repuesto
				FROM sa_det_orden_articulo det_orden_art
					INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (det_orden_art.id_det_orden_articulo = det_sol_rep.id_det_orden_articulo)
					INNER JOIN sa_solicitud_repuestos sol_rep ON (det_sol_rep.id_solicitud = sol_rep.id_solicitud)
				WHERE det_orden_art.id_det_orden_articulo = %s
					AND sol_rep.estado_solicitud != 4;",
					valTpDato($valorRepuestoPaq['id_det_orden_articulo_ref'], "int"));
				$rs = mysql_query($query);
				if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$row = mysql_fetch_assoc($rs);
													
				if ($row['id_det_solicitud_repuesto'] != '') {
					$repuestosTomadosEnSolicitud = 1;
				}
			}
										
			if($rowNroManoObraPaq['numeroMo'] > 0 || $tieneRepuestos > 0) {
				$checkedPaq = "checked='checked'";  // o se coloca vacio?
				$value_checkedPaq = 1;
				if($repuestosTomadosEnSolicitud == 1){
					$disabledPaq = "disabled='disabled'";   
				}else{//else sino el while toma el ultimo establecido
                                    $disabledPaq = "";
                                }
						
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_paquete = 1;
					$displayPaq = "style='display:none;'";
					$imgCheckDisabledPaq = sprintf("<input id='cbxItmAprobDisabled%s' name='cbxItmAprobDisabled%s' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor, $sigValor, $sigValor);
				} else {
					if($repuestosTomadosEnSolicitud == 1) {
						$readonly_check_ppal_list_paquete = 1;
						$displayPaq = "style='display:none;'";
						$imgCheckDisabledPaq = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
					} else {
						$displayPaq = "";
						$imgCheckDisabledPaq = "";
						$tieneRptoSinSolicitudPaq = 1;
					}
				}
			} else {
				//$itemsNoChecados = 1;//error sino tiene repuestos ni mano de obra cae aqui
				$checkedPaq = " ";
				$value_checkedPaq = 0;
				
				if($repuestosTomadosEnSolicitud == 1){
					$disabledPaq = "disabled='disabled'";   
				}else{//else sino el while toma el ultimo establecido
                                    $disabledPaq = "";
                                }
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayPaq = "style='display:none;'";
					$imgCheckDisabledPaq = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
					$readonly_check_ppal_list_paquete = 1;
				} else {
					$displayPaq = "";
					$imgCheckDisabledPaq = "";
					$tieneRptoSinSolicitudPaq = 1;
				}
			}
						
			// HACER CONSULTA SI MUESTRA O NO EL PAQUETE DESAPROBADO
			$cadenaManoPaq = "";
			while ($valorManoObraPaq = mysql_fetch_array($rsManoObraPaq)) {
				$cadenaManoPaq .= "|".$valorManoObraPaq['id_tempario'];
			}
					
			if ($value_checkedPaq == 1) {
                            
                            $sqlIvasIdPorcPaq = sprintf("SELECT GROUP_CONCAT(sa_det_orden_articulo_iva.id_iva) AS id_iva_paquete, GROUP_CONCAT(sa_det_orden_articulo_iva.iva) AS porc_iva_paquete, GROUP_CONCAT(sa_det_orden_articulo.precio_unitario*sa_det_orden_articulo.cantidad) AS base_imponible_paquete
                                                        FROM sa_det_orden_articulo
                                                        INNER JOIN sa_det_orden_articulo_iva ON sa_det_orden_articulo.id_det_orden_articulo = sa_det_orden_articulo_iva.id_det_orden_articulo
                                                        WHERE id_orden = %s AND id_paquete = %s AND estado_articulo != 'DEVUELTO'",
                                                valTpDato($idDocumento, "int"),
                                                valTpDato($rowDetPaq['idPaq'], "int"));
                            $rsIvasIdPorcPaq = mysql_query($sqlIvasIdPorcPaq);
                            if (!$rsIvasIdPorcPaq) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$sqlIvasIdPorcPaq);
                            
                            $rowIvasIdPorcPaq = mysql_fetch_assoc($rsIvasIdPorcPaq);
                            
				$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItmPaq:%s', 'class':'textoGris_11px %s', 'height':'24', 'title':'trItmPaq:%s'}).adopt([
					new Element('td', {'align':'center', 'id':'tdItmPaq:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmPaq' name='cbxItmPaq[]' type='checkbox' value='%s' %s /> \"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'left'}).setHTML(\"%s\"),
					new Element('td', {'align':'right'}).setHTML(\"%s\"),
					new Element('td', {'align':'center'}).setHTML(\"%s".
						"<img class='puntero noprint' id='img:%s' src='../img/iconos/ico_view.png' title='Paquete:%s'/>".
						"<input type='hidden' id='hddIdPedDetPaq%s' name='hddIdPedDetPaq%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddIdPaq%s' name='hddIdPaq%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddRepPaqAsig%s' name='hddRepPaqAsig%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddTempPaqAsig%s' name='hddTempPaqAsig%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddRepPaqAsigEdit%s' name='hddRepPaqAsigEdit%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddTempPaqAsigEdit%s' name='hddTempPaqAsigEdit%s' readonly='readonly' value='%s'/>".
						"<input type='hidden' id='hddPrecPaq%s' name='hddPrecPaq%s' readonly='readonly' value='%s'/>\"),
					new Element('td', {'align':'center', 'id':'tdItmPaqAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"%s".
						"<input type='checkbox' id='cbxItmPaqAprob%s' name='cbxItmPaqAprob[]' value='%s' %s %s onchange='xajax_validarSiTieneAlmacenAsignado(%s, %s, %s, this.id)'>".
						
						"<input type='hidden' id='hddValorCheckAprobPaq%s' name='hddValorCheckAprobPaq%s' value='%s'/>".
						"<input type='hidden' id='hddRptoPaqEnSolicitud%s' name='hddRptoPaqEnSolicitud%s' value='%s'/>".
						"<input type='hidden' id='hddTotalRptoPqte%s' name='hddTotalRptoPqte%s' value='%s'/>".
						"<input type='hidden' id='hddTotalTempPqte%s' name='hddTotalTempPqte%s' value='%s'/>".
						"<input type='hidden' id='hddRptoTomadoSolicitud%s' name='hddRptoTomadoSolicitud%s' value='%s'/>".
						"<input type='hidden' id='hddTotalExentoRptoPqte%s' name='hddTotalExentoRptoPqte%s' value='%s'/>".
						"<input type='hidden' id='hddIdIvasRepuestoPaquete%s' name='hddIdIvasRepuestoPaquete%s' value='%s'/>".
						"<input type='hidden' id='hddIvasRepuestoPaquete%s' name='hddIvasRepuestoPaquete%s' value='%s'/>".
						"<input type='hidden' id='hddPorcentajesIvasRepuestoPaquete%s' name='hddPorcentajesIvasRepuestoPaquete%s' value='%s'/>".
						"<input type='hidden' id='hddTotalConIvaRptoPqte%s' name='hddTotalConIvaRptoPqte%s' value='%s'/>\")
				]);	
				elemento.injectBefore('trm_pie_paquete');
				
				$('img:%s').onclick = function() {
					$('tblGeneralPaquetes').style.display = '';
					
					xajax_buscar_mano_obra_repuestos_por_paquete('%s','%s','%s','%s','%s','%s','%s','');
				}",
				$sigValor, $clase, $sigValor,
					$sigValor, $sigValor, $disabledPaq,
					utf8_encode($rowDetPaq['codigo_paquete']),
					utf8_encode($rowDetPaq['descripcion_paquete']),
					number_format($rowDetPaq['precio_paquete'],2,".",","),
					$estado_paquete,
						$sigValor, $rowDetPaq['idPaq'],
						$sigValor, $sigValor, $sigValor,
						$sigValor, $sigValor, $rowDetPaq['idPaq'],
						$sigValor, $sigValor, $cadenaRepPaq,
						$sigValor, $sigValor, $cadenaManoPaq,
						$rowDetPaq['idPaq'], $rowDetPaq['idPaq'], $cadenaRepPaq,
						$rowDetPaq['idPaq'], $rowDetPaq['idPaq'], $cadenaManoPaq,
						$sigValor, $sigValor, round($rowDetPaq['precio_paquete'], 2),
					$sigValor, $imgCheckDisabledPaq,
						$sigValor, $sigValor, $checkedPaq, $displayPaq, $sigValor, $rowDetPaq['idPaq'], $idDocumento,
						$sigValor, $sigValor, $value_checkedPaq,
						$sigValor, $sigValor, $row['id_det_solicitud_repuesto'],
						$sigValor, $sigValor, round($rowDetPaq['total_rpto'], 2),
						$sigValor, $sigValor, round($rowDetPaq['total_tmp'], 2),
						$sigValor, $sigValor, $repuestosTomadosEnSolicitud,
						$sigValor, $sigValor, round($rowDetPaq['total_art_exento'], 2),
						$sigValor, $sigValor, $rowIvasIdPorcPaq['id_iva_paquete'],
						$sigValor, $sigValor, $rowIvasIdPorcPaq['base_imponible_paquete'],
						$sigValor, $sigValor, $rowIvasIdPorcPaq['porc_iva_paquete'],
						$sigValor, $sigValor, round($rowDetPaq['total_art_con_iva'], 2),
				
				$sigValor,
					$rowDetPaq['idPaq'], utf8_encode($rowDetPaq['codigo_paquete']), utf8_encode($rowDetPaq['descripcion_paquete']),$rowDetPaq['precio_paquete'], 1, '0',''));
				// EL CERO EN LA SEXTA POSICION INDICA LA VISUALIZACION DE LOS BOTONES ACEPTAR Y CANCELAR EN LOS PAQUETES GUARDADOS...
		
				/*if($imgCheckDisabled != " ")
					$objResponse->script(sprintf("$('imgCheckDisabled:%s').style.display='';",$sigValor));*/
			
				if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) { // || $valFormTotalDcto['hddAccionTipoDocumento']==3
					$objResponse->script(sprintf("
					$('tdInsElimPaq').style.display = '';
					$('tdItmPaq:%s').style.display = '';
					$('tdItmPaqAprob:%s').style.display = 'none'",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
					$objResponse->script(sprintf("
					$('tdInsElimPaq').style.display = 'none';
					$('tdItmPaq:%s').style.display = 'none';
					$('tdItmPaqAprob:%s').style.display = '';",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
					$objResponse->script(sprintf("
					$('tdInsElimPaq').style.display = 'none';
					$('tdItmPaq:%s').style.display = 'none';
					$('tdItmPaqAprob:%s').style.display = ''",
						$sigValor,
						$sigValor));	
				} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
					$objResponse->script(sprintf("
					$('tdInsElimPaq').style.display = '';
					$('tdItmPaq:%s').style.display = '';
					$('tdItmPaqAprob:%s').style.display = '';",
						$sigValor,
						$sigValor));
				}
			}
				
			$arrayObjPaq[] = $sigValor;
			$sigValor++;
		}
	
		if ($readonly_check_ppal_list_paquete == 1) {
			$objResponse->script("
			$('cbxItmPaqAprob').style.display = 'none';");
			
			$objResponse->assign("tdPaqAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			$objResponse->assign("tdInsElimPaq","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
		
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if($sigValor == 1) {
				$objResponse->script("
				$('frm_agregar_paq').style.display='none';");
			}
			/*if($tieneRptoSinSolicitudPaq == 1)
				$objResponse->script("
					$('lstMecSolRptoBusq').disabled = false;");*/
		} else {
			if ($valFormTotalDcto['hddTipoDocumento' ]== 1) { //PRESUPUESTO
				if ($sigValor == 1) {
					$objResponse->script("
					$('frmListaArticulo').style.display='none';
					$('tblListaArticulo').style.display='none';");
				}
			}
		}
		
		if($_GET['dev'] == 1)
			$condicionMostrarTotFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetTot);
		else
			$condicionMostrarTotFacturados = "";
	
		$queryDetalleTot = sprintf("SELECT *,
			%s.%s
		FROM sa_orden_tot
			INNER JOIN %s ON (sa_orden_tot.id_orden_tot = %s.id_orden_tot)
			INNER JOIN cp_proveedor ON (sa_orden_tot.id_proveedor = cp_proveedor.id_proveedor)
		WHERE %s.%s = %s %s",
			$tablaDocDetTot, $campoTablaIdDetTotRelacOrden,
			$tablaDocDetTot, $tablaDocDetTot,
			$tablaDocDetTot, $campoIdEnc, valTpDato($idDocumento, "int"), $condicionMostrarTotFacturados);
		$rsDetalleTot = mysql_query($queryDetalleTot);
		if (!$rsDetalleTot) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			
		$sigValor = 1;
		$arrayObjTot = NULL;
		while ($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)) {
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			
			if($rowDetalleTot['aprobado'] == 1) {
				$checkedTot = "checked='checked'";
				$value_checkedTot = 1;
				//$disabledTot = "disabled='disabled'";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_tot = 1;
					$displayTot = "style='display:none;'";
					$imgCheckDisabledTot = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
				} else {
					$displayTot = "";
					$imgCheckDisabledTot = "";
				}
			} else {
				$itemsNoChecados = 1;
				$checkedTot = " ";
				$value_checkedTot = 0;
				$disabledTot = "";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayTot = "style='display:none;'";
					$imgCheckDisabledTot = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
					$readonly_check_ppal_list_tot = 1;
				}
			}
			
			$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmTot:%s', 'class':'textoGris_11px %s', 'height':'24', 'title':'trItmTot:%s'}).adopt([
				new Element('td', {'align':'center', 'id':'tdItmTot:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmTot' name='cbxItmTot[]' type='checkbox' value='%s' onclick='xajax_calcularTotalDcto();' %s />\"),
				new Element('td', {'align':'right'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDetTot%s' name='hddIdPedDetTot%s' value='%s'/>".
					"<input type='hidden' id='hddIdTot%s' name='hddIdTot%s' value='%s'/>".
					"<input type='hidden' id='hddIdPorcTot%s' name='hddIdPorcTot%s' value='%s'/>".
					"<input type='hidden' id='hddPrecTot%s' name='hddPrecTot%s' value='%s'/>".
					"<input type='hidden' id='hddPorcTot%s' name='hddPorcTot%s' value='%s'/>".
					"<input type='hidden' id='hddMontoTotalTot%s' name='hddMontoTotalTot%s' value='%s'/>\"),
				new Element('td', {'align':'center', 'id':'tdItmTotAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmTotAprob' name='cbxItmTotAprob[]' type='checkbox' value='%s' %s onclick='xajax_calcularTotalDcto();' %s/>".
					"%s".
					"<input type='hidden' id='hddValorCheckAprobTot%s' name='hddValorCheckAprobTot%s' value='%s'/>\")
			]);
			elemento.injectBefore('trm_pie_tot');",
			$sigValor, $clase, $sigValor,
				$sigValor,  $sigValor, $disabledTot,
				"<idtotoculta style='display:none'>".$rowDetalleTot['id_orden_tot']."</idtotoculta>".$rowDetalleTot['numero_tot'],
				utf8_encode($rowDetalleTot['nombre']),
				$rowDetalleTot['tipo_pago'],
				number_format($rowDetalleTot['monto_subtotal'],2,".",","),
				number_format($rowDetalleTot['porcentaje_tot'],2,".",",")."% ".$rowDetalleTot['descripcion'],
				number_format($rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100),2,".",","),
					$sigValor, $sigValor, $rowDetalleTot['id_det_orden_tot_ref'],//sprintf('%s', $campoTablaIdDetTot)
					$sigValor, $sigValor, $rowDetalleTot['id_orden_tot'],
					$sigValor, $sigValor, $rowDetalleTot['id_porcentaje_tot'],
					$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['monto_subtotal']),
					$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['porcentaje_tot']),
					$sigValor, $sigValor, str_replace(",","", $rowDetalleTot['monto_subtotal']+($rowDetalleTot['monto_subtotal']*$rowDetalleTot['porcentaje_tot']/100)),
			$sigValor, $sigValor, $checkedTot, $displayTot,
				$imgCheckDisabledTot,
				$sigValor, $sigValor, $value_checkedTot));
			
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
				$objResponse->script(sprintf("$('tdInsElimTot').style.display = '';
				$('tdItmTot:%s').style.display = '';
				$('tdItmTotAprob:%s').style.display = 'none'",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
				$objResponse->script(sprintf("$('tdInsElimTot').style.display = 'none';
				$('tdItmTot:%s').style.display = 'none';
				$('tdItmTotAprob:%s').style.display = '';
				$('cbxItmTotAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
				$objResponse->script(sprintf("$('tdInsElimTot').style.display = 'none';
				$('tdItmTot:%s').style.display = 'none';
				$('tdItmTotAprob:%s').style.display = ''",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
				$objResponse->script(sprintf("
				$('tdInsElimTot').style.display = '';
				$('tdItmTot:%s').style.display = '';
				$('tdItmTotAprob:%s').style.display = '';",
					$sigValor,
					$sigValor));
			}
							
			$arrayObjTot[] = $sigValor;
			$sigValor++;
		}
		
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if ($sigValor == 1) {
				$objResponse->script("
				$('frmListaTot').style.display = 'none';");
			}
		} else {
			if ($valFormTotalDcto['hddTipoDocumento'] == 1) { //PRESUPUESTO
				if ($sigValor == 1) {
					$objResponse->script("
					$('frmListaTot').style.display = 'none';");
				}
			}
		}
		
		if ($readonly_check_ppal_list_tot == 1) {
			$objResponse->script("
			$('cbxItmTotAprob').style.display = 'none';");
			$objResponse->assign("tdTotAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			//$objResponse->assign("tdInsElimTot","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");	
		}
		
		if ($_GET['dev'] == 1)
			$condicionMostrarTemparioFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetTemp);
		else
			$condicionMostrarTemparioFacturados = "";		
		
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
			(case %s.id_modo
				when '1' then
					%s.ut * %s.precio_tempario_tipo_orden/%s.base_ut_precio
				when '2' then
					%s.precio
				when '3' then
					%s.costo
				when '4' then
					'4'
			end) AS total_por_tipo_orden,
			(case %s.id_modo
				when '1' then
					%s.ut
				when '2' then
					%s.precio
				when '3' then
					%s.costo
				when '4' then
					'4'
			end) AS precio_por_tipo_orden,
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
		FROM %s
			INNER JOIN sa_tempario ON (%s.id_tempario = sa_tempario.id_tempario)
			INNER JOIN sa_operadores ON (%s.operador = sa_operadores.id_operador)
			INNER JOIN sa_modo ON (%s.id_modo = sa_modo.id_modo)
			LEFT JOIN sa_mecanicos ON (%s.id_mecanico = sa_mecanicos.id_mecanico)
			LEFT JOIN pg_empleado ON (sa_mecanicos.id_empleado = pg_empleado.id_empleado)
			INNER JOIN sa_subseccion ON (sa_tempario.id_subseccion = sa_subseccion.id_subseccion)
			INNER JOIN sa_seccion ON (sa_subseccion.id_seccion = sa_seccion.id_seccion)
		WHERE %s.id_paquete IS NULL
			AND %s.%s = %s %s",
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
			$tablaDocDetTemp, $tablaDocDetTemp, $campoIdEnc, valTpDato($idDocumento, "int"), $condicionMostrarTemparioFacturados);
		$rsDetTemp = mysql_query($queryDetTemp);
		if (!$rsDetTemp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		$sigValor = 1;
		$arrayObjTemp = NULL;
		while ($rowDetTemp = mysql_fetch_assoc($rsDetTemp)) {
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
			if ($rowDetTemp['aprobado'] == 1) {
				$checkedTemp = "checked='checked'";
				$value_checkedTemp = 1;
				//$disabledTemp = "disabled='disabled'";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_tempario = 1;
					$displayTemp = "style='display:none;'";
					$imgCheckDisabledTemp = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
				} else {
					$displayTemp = "";
					$imgCheckDisabledTemp = "";							       
				}
			} else {
				$itemsNoChecados = 1;
				$checkedTemp = " ";
				$value_checkedTemp = 0;
				$disabledTemp = "";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayTemp = "style='display:none;'";
					$imgCheckDisabledTemp = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
					$readonly_check_ppal_list_tempario = 1;
				}/*else {
					$display = "";
					$imgCheckDisabled = "";
				}*/
			}
		
			if ($rowDetTemp['origen_tempario'] == 0) {
				$origen = "ORDEN";
			} else {
				$origen = "CONTROL TALLER";
			}
		
			$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmTemp:%s', 'class':'textoGris_11px %s', 'height':'24', 'title':'trItmTemp:%s'}).adopt([
				new Element('td', {'align':'center', 'id':'tdItmTemp:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmTemp' name='cbxItmTemp[]' type='checkbox' value='%s' %s />\"),
				new Element('td', {'align':'center', 'id':'tdItmCodMecanico:%s'}).setHTML(\"%s\"),
				new Element('td', {'align':'center', 'id':'tdItmNomMecanico:%s'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDetTemp%s' name='hddIdPedDetTemp%s' value='%s'/>".
					"<input type='hidden' id='hddIdTemp%s' name='hddIdTemp%s' value='%s'/>".
					"<input type='hidden' id='hddIdMec%s' name='hddIdMec%s' value='%s'/>".
					"<input type='hidden' id='hddIvaTemp%s' name='hddIvaTemp%s' value='%s'/>".
					"<input type='hidden' id='hddPrecTemp%s' name='hddPrecTemp%s' value='%s'/>".
					"<input type='hidden' id='hddIdModo%s' name='hddIdModo%s' value='%s'/>\"),
				new Element('td', {'align':'center', 'id':'tdItmTempAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmTempAprob' name='cbxItmTempAprob[]' 'title':'cbxItmTempAprob:%s' type='checkbox' value='%s' %s %s onclick='xajax_calcularTotalDcto();' />".
					"%s".
					"<input type='hidden' id='hddValorCheckAprobTemp%s' name='hddValorCheckAprobTemp%s' value='%s'/>".
					"<input type='hidden' id='hddIdOrigen%s' name='hddIdOrigen%s' value='%s'/>\")
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
				$origen,
				utf8_encode($rowDetTemp['descripcion_modo']),
				$rowDetTemp['descripcion_operador'],
				$rowDetTemp['precio_por_tipo_orden'],
				number_format($rowDetTemp['total_por_tipo_orden'],2,".",","),
					$sigValor, $sigValor, $rowDetTemp['id_det_orden_tempario_ref'],//sprintf('%s', $campoTablaIdDetTemp), $campoTablaIdDetTempRelacOrden)
					$sigValor, $sigValor, $rowDetTemp['id_tempario'],
					$sigValor, $sigValor, $rowDetTemp['id_mecanico'],
					$sigValor, $sigValor, utf8_encode($rowDetTemp['nombre_empleado']." ".$rowDetTemp['apellido']),
					$sigValor, $sigValor, $rowDetTemp['total_por_tipo_orden'],
					$sigValor, $sigValor, utf8_encode($rowDetTemp['descripcion_modo']),
				$sigValor, $sigValor, $sigValor, $checkedTemp, $displayTemp,
					$imgCheckDisabledTemp,
					$sigValor, $sigValor, $value_checkedTemp,
					$sigValor, $sigValor, $rowDetTemp['idOrigen']));
			
			//dependiendo si se muestra o no el mecanico por parametros generales coloco la validacion  $valFormTotalDcto['hddTipoDocumento']==2

			if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
				$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display='none'",$sigValor));
				$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display='none'",$sigValor));
			} else {
				if($valFormTotalDcto['hddMecanicoEnOrden'] == 1) {	
					$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display=''",$sigValor));
					$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display=''",$sigValor));
				} else {
					$objResponse->script(sprintf("$('tdItmCodMecanico:%s').style.display='none'",$sigValor));
					$objResponse->script(sprintf("$('tdItmNomMecanico:%s').style.display='none'",$sigValor));
				}
			}
		
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
				$objResponse->script(sprintf("
				$('tdInsElimManoObra').style.display = '';
				$('tdItmTemp:%s').style.display = '';
				$('tdItmTempAprob:%s').style.display = 'none'",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
				$objResponse->script(sprintf("
				$('tdInsElimManoObra').style.display = 'none';
				$('tdItmTemp:%s').style.display = 'none';
				$('tdItmTempAprob:%s').style.display = '';
				$('cbxItmTempAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
				$objResponse->script(sprintf("
				$('tdInsElimManoObra').style.display = 'none';
				$('tdItmTemp:%s').style.display = 'none';
				$('tdItmTempAprob:%s').style.display = ''",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
				$objResponse->script(sprintf("
				$('tdInsElimTot').style.display = '';
				$('tdItmTot:%s').style.display = '';
				$('tdItmTotAprob:%s').style.display='';",
					$sigValor,
					$sigValor));
			}
					
			$arrayObjTemp[] = $sigValor;
			$sigValor++;
		}
	
		if ($readonly_check_ppal_list_tempario == 1) {
			$objResponse->script("
			$('cbxItmTempAprob').style.display = 'none';");
			$objResponse->assign("tdTempAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked' />");
			//$objResponse->assign("tdInsElimManoObra","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
				
		}
			
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if($sigValor==1) {
				$objResponse->script("
				$('frmListaManoObra').style.display='none';");
			}
		} else {
			if($valFormTotalDcto['hddTipoDocumento']==1) { //PRESUPUESTO
				if($sigValor==1) {
					$objResponse->script("
					$('frmListaManoObra').style.display='none';");
				}
			}
		}
	
		if($_GET['dev'] == 1) {
			$condicionMostrarNotaFacturados = sprintf(" AND %s.aprobado = 1", $tablaDocDetNota);
		} else {
			$condicionMostrarNotaFacturados = "";
		}
	
		$queryDetTipoDocNotas = sprintf("SELECT
			%s.%s,
			%s AS idDetNota,
			%s AS idDoc,
			descripcion_nota,
			precio, 
			aprobado
		FROM %s
		WHERE %s = %s %s",
			$tablaDocDetNota, $campoTablaIdDetNotaRelacOrden, 
			$campoTablaIdDetNota,
			$campoIdEnc,
			$tablaDocDetNota,
			$campoIdEnc, valTpDato($idDocumento, "int"), $condicionMostrarNotaFacturados);
		$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas);
		if (!$rsDetTipoDocNotas) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

		$sigValor = 1;
		$arrayObjNota = NULL;
		while ($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)) {
			$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
				
			//$caracterIva = ($rowPresupuestoDet['id_iva'] != "" && $rowPresupuestoDet['id_iva'] != "0") ? $rowPresupuestoDet['iva']."%" : "NA";
			if ($rowDetTipoDocNotas['aprobado'] == 1) {
				$checkedNota = "checked='checked'"; 
				$value_checkedNota = 1;
				//$disabledNota = "disabled='disabled'";
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$readonly_check_ppal_list_nota = 1;
					$displayNota = "style='display:none;'";
					$imgCheckDisabledNota = sprintf("<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' value='%s' checked='checked' />", $sigValor);
				} else {
					$displayNota = "";
					$imgCheckDisabledNota = "";
				}
			} else {
				$itemsNoChecados = 1;
				$checkedNota = " ";
				$value_checkedNota = 0;
				$disabledNota = "";
				/*$display = "style='display:none;'";
				$imgCheckDisabled = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";*/
				if ($valFormTotalDcto['hddAccionTipoDocumento'] != 4) {
					$displayNota = "style='display:none;'";
					$imgCheckDisabledNota = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
					$readonly_check_ppal_list_nota = 1;
				}/*else {
					$display = "";
					$imgCheckDisabled = "";
					$display = "style='display:none;'";
					$imgCheckDisabled = "<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>";
				}*/
			}
					
			$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmNota:%s', 'class':'textoGris_11px %s', 'height':'24', 'title':'trItmNota:%s'}).adopt([
				new Element('td', {'id':'tdItmNota:%s', 'class':'color_column_insertar_eliminar_item'}).setHTML(\"<input id='cbxItmNota' name='cbxItmNota[]' type='checkbox' value='%s' %s />\"),
				new Element('td', {'align':'left'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"%s".
					"<input type='hidden' id='hddIdPedDetNota%s' name='hddIdPedDetNota%s' value='%s'/>".
					"<input type='hidden' id='hddIdNota%s' name='hddIdNota%s' value='%s'/>".
					"<input type='hidden' id='hddDesNota%s' name='hddDesNota%s' value='%s'/>".
					"<input type='hidden' id='hddPrecNota%s' name='hddPrecNota%s' value='%s'/>\"),
				new Element('td', {'align':'center' , 'id':'tdItmNotaAprob:%s', 'class':'color_column_aprobacion_item'}).setHTML(\"<input id='cbxItmNotaAprob' name='cbxItmNotaAprob[]' type='checkbox' value='%s' %s %s onclick='xajax_calcularTotalDcto();'/>".
					"%s".
					"<input type='hidden' id='hddValorCheckAprobNota%s' name='hddValorCheckAprobNota%s' value='%s'/>\")
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
				$sigValor, $sigValor, $checkedNota, $displayNota,
					$imgCheckDisabledNota,
					$sigValor, $sigValor, $value_checkedNota));
					
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1) {
				$objResponse->script(sprintf("
				$('tdInsElimNota').style.display = '';
				$('tdItmNota:%s').style.display = '';
				$('tdItmNotaAprob:%s').style.display = 'none'",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2) {
				$objResponse->script(sprintf("
				$('tdInsElimNota').style.display = 'none';
				$('tdItmNota:%s').style.display = 'none'; 
				$('tdItmNotaAprob:%s').style.display = '';
				$('cbxItmNotaAprob').disabled = true;",
					$sigValor,
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
				$objResponse->script(sprintf("
				$('tdInsElimNota').style.display = 'none';
				$('tdItmNota:%s').style.display = 'none';
				$('tdItmNotaAprob:%s').style.display = '';",
					$sigValor,
					$sigValor));
			}
					
			$arrayObjNota[] = $sigValor;
			$sigValor++;
		}
	
		if ($valFormTotalDcto['hddAccionTipoDocumento'] == 2 || $valFormTotalDcto['hddAccionTipoDocumento'] == 4) {
			if ($sigValor == 1) {
				$objResponse->script("
				$('frmListaNota').style.display='none';");
			}
		} else {
			if ($valFormTotalDcto['hddTipoDocumento'] == 1) {
				if ($sigValor == 1) {
					$objResponse->script("
					$('frmListaNota').style.display='none';");
				}
			}
		}
		
		if ($readonly_check_ppal_list_nota == 1) {
			$objResponse->script("
			$('cbxItmNotaAprob').style.display = 'none';");
			$objResponse->assign("tdNotaAprob","innerHTML","<input id='cbxItmAprobDisabled' name='cbxItmAprobDisabled[]' disabled='disabled' type='checkbox' checked='checked'/>");
			//$objResponse->assign("tdInsElimNota","innerHTML","<input id='cbxItmAprobDisabledNoChecked' name='cbxItmAprobDisabledNoChecked[]' disabled='disabled' type='checkbox'/>");
		}
	
		$queryDetPorcentajeAdicional = sprintf("SELECT 
			%s.%s,
			%s.id_porcentaje_descuento,
			%s.porcentaje,
			%s.%s AS idDetDcto,
			sa_porcentaje_descuento.descripcion
		FROM sa_porcentaje_descuento
			INNER JOIN %s ON (sa_porcentaje_descuento.id_porcentaje_descuento = %s.id_porcentaje_descuento)
		WHERE %s.%s = %s",
			$tablaDocDetDescuento, $campoIdEnc,
			$tablaDocDetDescuento,
			$tablaDocDetDescuento,
			$tablaDocDetDescuento, $campoTablaIdDetDescuento,
			$tablaDocDetDescuento, $tablaDocDetDescuento,
			$tablaDocDetDescuento, $campoIdEnc, valTpDato($idDocumento, "int"));
		$rsDetPorcentajeAdicional = mysql_query($queryDetPorcentajeAdicional);
		if (!$rsDetPorcentajeAdicional) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
		$sigValor = 1;
		$arrayObjDcto = NULL;
		while ($rowDetPorcentajeAdicional = mysql_fetch_assoc($rsDetPorcentajeAdicional)) {
			$objResponse->script(sprintf("
			var elemento = new Element('tr', {'id':'trItmDcto:%s', 'class':'textoGris_11px', 'height':'24', 'title':'trItmDcto:%s'}).adopt([
				new Element('td', {'align':'right', 'id':'tdItmDcto:%s', 'class':'tituloCampo'}).setHTML(\"%s\"),
				new Element('td', {'align':'center'}).setHTML(\"%s\"),
				new Element('td', {'align':'right'}).setHTML(\"<input type='text' id='hddPorcDcto%s' name='hddPorcDcto%s' size='6' style='text-align:right' readonly='readonly' value='%s'/>%s\"),
				new Element('td', {'align':'right'}).setHTML(\"<img id='imgElimDcto:%s' name='imgElimDcto:%s' src='../img/iconos/delete.png' class='puntero noprint' title='Porcentaje Adcl:%s' />".
					"<input type='hidden' id='hddIdDetDcto%s' name='hddIdDetDcto%s' value='%s'/>".
					"<input type='hidden' id='hddIdDcto%s' name='hddIdDcto%s' value='%s'/>\"),
				new Element('td', {'align':'right', 'id':'tdItmNotaAprob:%s'}).setHTML(\"<input type='text' id='txtTotalDctoAdcl%s' name='txtTotalDctoAdcl%s' readonly='readonly' style='text-align:right' size='18'/>\")]);
			elemento.injectBefore('trm_pie_dcto');
			
			$('imgElimDcto:%s').onclick = function() {
				xajax_eliminarDescuentoAdicional(%s);			
			}",
			$sigValor, $sigValor,
				$sigValor, $rowDetPorcentajeAdicional['descripcion'].":",
				"",
				$sigValor, $sigValor, $rowDetPorcentajeAdicional['porcentaje'], "%",
				$sigValor, $sigValor, $sigValor,
					$sigValor, $sigValor, $rowDetPorcentajeAdicional['idDetDcto'],
					$sigValor, $sigValor, $rowDetPorcentajeAdicional['id_porcentaje_descuento'],
				$sigValor, $sigValor, $sigValor,
			
			$sigValor,
				$sigValor));
			
			if ($valFormTotalDcto['hddAccionTipoDocumento'] == 1 || $valFormTotalDcto['hddAccionTipoDocumento'] == 3) {
				$objResponse->script(sprintf("
				$('imgElimDcto:%s').style.display = '';
				$('imgAgregarDescuento').style.display = '';",
					$sigValor));	
			} else if ($valFormTotalDcto['hddAccionTipoDocumento']==2 || $valFormTotalDcto['hddAccionTipoDocumento']==4) {
				$objResponse->script(sprintf("
				$('imgElimDcto:%s').style.display = 'none';
				$('imgAgregarDescuento').style.display = 'none';",
					$sigValor));
			}
		
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
	}
	
	
	
	if (isset($_GET['dev'])) {
		if ($_GET['dev'] == 1) {
			$query = sprintf("SELECT 
				cj_cc_encabezadofactura.idFactura,
				cj_cc_encabezadofactura.numeroControl,
				cj_cc_encabezadofactura.numeroFactura,
				cj_cc_encabezadofactura.fechaRegistroFactura
			FROM cj_cc_encabezadofactura
			WHERE cj_cc_encabezadofactura.idFactura = %s",
				$_GET['idfct']);
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			//$('trTipoClave').style.display='none';
			//$('trClaveMov').style.display='none';
			$objResponse->script(sprintf("
			$('tdNroFacturaVenta').style.display='';
			$('tdTxtNroFacturaVenta').style.display='';
			
			$('tblMotivoRetrabajo').style.display = '';
			$('tblLeyendaOrden').style.display='none';
			
			$('txtNroFacturaVentaServ').readOnly = true;
			
			$('txtNroFacturaVentaServ').value='%s';
			$('tdNroControl').style.display='';
			$('tdTxtNroControl').style.display='';",
				$row['numeroFactura']));
		} else {//cargar vale de salida
			//solo servicios
		}
	}
		
	$objResponse->assign("txtFechaPresupuesto","value", $fechaDocumento);
	$objResponse->assign("txtIdPresupuesto","value",utf8_encode($idDocumento));
	$objResponse->assign("numeroOrdenMostrar","value",utf8_encode($numeroOrdenMostrar));

	$objResponse->assign("txtIdEmpresaOrden","value",utf8_encode($idEmpresaOrden));
	$objResponse->assign("txtEmpresaOrden","value",utf8_encode($empresaOrden));
	
	//numeracion de orden y presupuesto a mostrar:
	
	if($valFormTotalDcto['hddTipoDocumento'] == 1){//1 es Presupuesto
		$numeroOrdenPresupuestoMostrar = $numeroPresupuestoMostrar;
	}else{ //Cualquier otra cosa es Orden
		$numeroOrdenPresupuestoMostrar = $numeroOrdenMostrar;
	}
	$objResponse->assign("numeroOrdenPresupuestoMostrar","value",$numeroOrdenPresupuestoMostrar);
	//
	
	$objResponse->assign("txtDescuento","value", $descuento);
	
	$objResponse->assign("hddItemsNoAprobados","value", $itemsNoChecados);
	
        
	//$objResponse->assign("hddIdIvaVenta","value", $id_iva);
	//$objResponse->assign("txtIvaVenta","value", $iva);
		
	/*$id_iva = $row['idIva'];
	$iva = $row['iva'];
	$objResponse->alert($row['idIva']."".$row['iva']);*/
	
	//OJO CON ESTO... PUEDO ESTAR HACIENDO ALGUNA ACTUALIZACION QUE SEA DE ORDEN Y LA HAGA DE PRESUPUESTO O VICEVERSA...
	//$objResponse->assign("hddIdOrden","value",utf8_encode($idDocumento));
	
	$objResponse->assign("txtEstadoOrden","value", utf8_encode($estado_orden));
	$objResponse->assign("txtFechaVencimientoPresupuesto","value", $fechaDocumento);
		
	if ($_GET['dev'] == 1) {	
		$objResponse->script(sprintf("
		$('fldPresupuesto').style.display='';
		xajax_cargaLstClaveMovimiento('ORDEN', %s);",
			$idTipoOrden));
	}else{	
		$objResponse->script(sprintf("
		$('fldPresupuesto').style.display='';
		xajax_cargaLstClaveMovimiento($('lstTipoClave').value, %s);",
			$idTipoOrden));
	}
	
	$objResponse->script(sprintf("
	xajax_cargaLstTipoOrden('%s');",$idTipoOrden));
	
	
	$objResponse->assign("hddIdEmpleado","value",$rowUsuario['id_empleado']);
		
	$objResponse->script("xajax_calcularTotalDcto();");
		
	return $objResponse;
}


//CUENTA ITEMS AGREGADOS AL DOCUMENTO
function contarItemsDcto($valFormTotalDcto, $valFormPaq){
	$objResponse = new xajaxResponse();
	
	$cont = 0;
	
	$arrayObjPaq = explode("|",$valFormTotalDcto['hddObjPaquete']);
	
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
				
				$cont += intval(count($arrayTemparioPaq));
				$cont += intval(count($arrayRepuestosPaq));
			}
		}
	}
	
	$arrayObj = explode("|", $valFormTotalDcto['hddObj']);
	
	if (isset($arrayObj)) {
		$arrayRepuestos = NULL;
		foreach($arrayObj as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayRepuestos[] = $valor;
		}
		$cont += intval(count($arrayRepuestos));
	}
	
	$arrayObjTemp = explode("|", $valFormTotalDcto['hddObjTempario']);
	
	if (isset($arrayObjTemp)) {
		$arrayTempario = NULL;
		foreach($arrayObjTemp as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayTempario[] = $valor;
		}
		$cont += intval(count($arrayTempario));
	}
	
	$arrayObjTot = explode("|", $valFormTotalDcto['hddObjTot']);
	
	if (isset($arrayObjTot)) {
		$arrayTot = NULL;
		foreach($arrayObjTot as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayTot[] = $valor;
		}
		$cont += intval(count($arrayTot));
	}
	
	$arrayObjNota = explode("|", $valFormTotalDcto['hddObjNota']);
	
	if (isset($arrayObjNota)) {
		$arrayNota = NULL;
		foreach($arrayObjNota as $indice => $valor){
			if ($valor > 0 && $valor != "")
				$arrayNota[] = $valor;
		}
		$cont += intval(count($arrayNota));
	}
	$objResponse->assign("hddItemsCargados","value", $cont);
			
	return $objResponse;
}

//DESBLOQUEA LA ORDEN AL DAR CLIC EN "CANCELAR" corregido con xajax sincrono para que devuelva true si lo hizo
function desbloquearOrden($id_orden){
	$objResponse = new xajaxResponse();

	mysql_query("START TRANSACTION;");
	
	$updateSQL = "UPDATE sa_orden SET
		id_usuario_bloqueo = null
	WHERE id_orden = ".$id_orden;
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
			
	return $objResponse->setReturnValue(true);
}

//DEVUELVE LA FACTURA Y CREA NOTA DE CREDITO - FACTURACION
function devolverFacturaVenta($valForm, $valFormTotalDcto){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cjrs_factura_venta_list","insertar")) return $objResponse;
	
	$objResponse->loadCommands(validarAperturaCaja());
	
	$queryVerif = sprintf("SELECT * FROM cj_cc_notacredito
		WHERE id_orden = %s LIMIT 1",
	$valForm['txtIdPresupuesto']);
	$rsVerif = mysql_query($queryVerif);
	if (!$rsVerif) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	if(mysql_num_rows($rsVerif) != 0){
		return $objResponse->alert('Esta factura ya ha sido devuelta.');
	}
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS DATOS DE LA FACTURA QUE ESTA SIENDO DEVUELTA
	$queryFact = sprintf("SELECT cj_cc_encabezadofactura.*, id_tipo_orden FROM cj_cc_encabezadofactura
						LEFT JOIN sa_orden ON cj_cc_encabezadofactura.numeroPedido = sa_orden.id_orden
	WHERE idFactura = %s LIMIT 1",
		$_GET['idfct']);
	$rsFact = mysql_query($queryFact);
	if (!$rsFact) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowFact = mysql_fetch_assoc($rsFact);
	
	$idEmpresa = $rowFact['id_empresa'];
	$idModulo = $rowFact['idDepartamentoOrigenFactura']; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idFactura = $rowFact['idFactura'];
	$idOrden = $rowFact['numeroPedido'];
	$tipoPago = $rowFact['condicionDePago'];
	$idTipoOrden = $rowFact['id_tipo_orden'];//gregor
	
		// BUSCA LA CLAVE DE MOVIMIENTO DE LA DEVOLUCION SEGUN ES TIPO DE ORDEN
	$queryClaveMov = sprintf("SELECT
		tipo_orden.id_clave_movimiento_dev,
		clave_mov.tipo
	FROM sa_tipo_orden tipo_orden
		INNER JOIN pg_clave_movimiento clave_mov ON (tipo_orden.id_clave_movimiento_dev = clave_mov.id_clave_movimiento)
	WHERE id_tipo_orden = %s;",
		valTpDato($idTipoOrden, "int"));//antes $valForm['lstTipoOrden'] a veces no se envia sino tiene el permiso del tipo de orden.
	$rsClaveMov = mysql_query($queryClaveMov);
	if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$rsClaveMov);
	$rowClaveMov= mysql_fetch_assoc($rsClaveMov);
	
	$idClaveMovimiento = $rowClaveMov['id_clave_movimiento_dev'];
	$idTipoMovimiento = $rowClaveMov['tipo'];
	
	//EL TOTAL PAGADO DE LA FACT = EL SALDO NC
	$saldoNotaCredito = $rowFact['montoTotalFactura'] - $rowFact['saldoFactura'];
	
	//$subtotalNotaCredito = str_replace(",","", $rowFact['montoTotalFactura']) - (str_replace(",","", $rowFact['calculoIvaFactura']) + str_replace(",","", $rowFact['calculoIvaDeLujoFactura']));
	
	if ($saldoNotaCredito == 0) {
		$estadoNotaCredito = 3;
	} else if($saldoNotaCredito > 0) {
		$estadoNotaCredito = 2;
	}
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
	WHERE id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
							WHERE clave_mov.id_clave_movimiento = %s)
		AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																		WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC
	LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	$numeroActual = $rowNumeracion['numero_actual'];
	
	// ACTUALIZA LA NUMERACION DEL DOCUMENTO
	$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
	WHERE id_empresa_numeracion = %s;",
		valTpDato($idEmpresaNumeracion, "int"));
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
        
	if($numeroActual == ""){ 
		return $objResponse->alert("La numeracion de las notas de credito no se encuentra configurada");
	}
	
	$insertSQLNc = sprintf("INSERT INTO cj_cc_notacredito (numeracion_nota_credito, idCliente, montoNetoNotaCredito, saldoNotaCredito, fechaNotaCredito,observacionesNotaCredito, estadoNotaCredito, idDocumento, tipoDocumento, porcentajeIvaNotaCredito, ivaNotaCredito, subtotalNotaCredito, porcentaje_descuento, subtotal_descuento, ivaLujoNotaCredito,idDepartamentoNotaCredito, montoExoneradoCredito, montoExentoCredito, aplicaLibros, baseimponibleNotaCredito, numeroControl, id_empresa, id_orden)
	VALUES (%s, %s, %s, %s, '%s', '%s', %s, %s, 'FA', %s, %s, %s, %s, %s, %s, 1, %s, %s, 1, %s, '%s', %s, %s)",
		valTpDato($numeroActual, "text"),
		valTpDato($rowFact['idCliente'], "int"),
		valTpDato($rowFact['montoTotalFactura'], "real_inglesa"), 
		$saldoNotaCredito,
		date("Y-m-d"),
		$valFormTotalDcto['txtMotivoRetrabajo'],
		$estadoNotaCredito,
		valTpDato($idFactura, "int"),
		str_replace(",","", $rowFact['porcentajeIvaFactura']),
		str_replace(",","", $rowFact['calculoIvaFactura']),
		str_replace(",","", $rowFact['subtotalFactura']),
		str_replace(",","", $rowFact['porcentaje_descuento']),
		str_replace(",","", $rowFact['descuentoFactura']),                 	
		str_replace(",","", $rowFact['calculoIvaDeLujoFactura']),
		str_replace(",","", $rowFact['montoExonerado']),
		str_replace(",","", $rowFact['montoExento']),
		str_replace(",","", $rowFact['baseImponible']), 
		str_replace(",","", $valForm['txtNroControl']),
		valTpDato($idEmpresa, "int"),
		$valForm['txtIdPresupuesto']);
	mysql_query("SET NAMES 'utf8';");
	$rsInsertarNc = mysql_query($insertSQLNc);
	if (!$rsInsertarNc) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$insertSQLNc);
	$idNotaCredito = mysql_insert_id();
	mysql_query("SET NAMES 'latin1';");
	
	//INSERTA IVAS DE LA FACTURA A NOTA DE CREDITO
	$ivasSql = sprintf("INSERT INTO cj_cc_nota_credito_iva (id_nota_credito, base_imponible, subtotal_iva, id_iva, iva, lujo)
						SELECT %s, base_imponible, subtotal_iva, id_iva, iva, lujo
						FROM cj_cc_factura_iva
						WHERE id_factura = %s",
			valTpDato($idNotaCredito, "int"),
			valTpDato($idFactura, "int"));
	$rsIvasSql = mysql_query($ivasSql);
	if (!$rsIvasSql) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$ivasSql); }
        
	if($rowFact['saldoFactura'] > 0){
		
		$idCaja = 2; // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		
		// CONSULTA FECHA DE APERTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
		$queryAperturaCaja = sprintf("SELECT * FROM sa_iv_apertura
		WHERE idCaja = %s
			AND statusAperturaCaja IN (1,2)
			AND (sa_iv_apertura.id_empresa = %s
				OR sa_iv_apertura.id_empresa IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
												WHERE suc.id_empresa = %s));",
			valTpDato($idCaja, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsAperturaCaja = mysql_query($queryAperturaCaja);
		if (!$rsAperturaCaja) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$queryAperturaCaja); }
		$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
		
		$fechaRegistroPago = $rowAperturaCaja["fechaAperturaCaja"];
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT *
		FROM pg_empresa_numeracion emp_num
			INNER JOIN pg_numeracion num ON (emp_num.id_numeracion = num.id_numeracion)
		WHERE emp_num.id_numeracion = %s
			AND (emp_num.id_empresa = %s OR (aplica_sucursales = 1 AND emp_num.id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																							WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC LIMIT 1;",
			valTpDato(44, "int"), // 44 = Recibo de Pago Repuestos y Servicios
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$queryNumeracion); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$idNumeraciones = $rowNumeracion['id_numeracion'];
		$numeroActual = $rowNumeracion['prefijo_numeracion'].$rowNumeracion['numero_actual'];
		
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$updateSQL); }
		
		$numeroActualPago = $numeroActual;
		
		// INSERTA EL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento)
		VALUES (%s, %s, %s, %s, %s, %s)",
			valTpDato($numeroActualPago, "int"),
			valTpDato($fechaRegistroPago, "date"),
			valTpDato(1, "int"), // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = OT
			valTpDato(0, "int"),		
			valTpDato($idFactura, "int"),
			valTpDato($idModulo, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$insertSQL); }
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$insertSQL = sprintf("INSERT INTO cj_cc_encabezado_pago_rs (id_factura, fecha_pago)
		VALUES (%s, %s)",
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago, "date"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$insertSQL); }
		$idEncabezadoPago = mysql_insert_id();
		
		//INSERTA EL PAGO
		$insertSQL = sprintf("INSERT INTO sa_iv_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, montoPagado, numeroFactura, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, id_encabezado_rs)
			VALUES (%s, '%s', 8, %s, 1, 1, %s, %s, 1, 0, 2, 0, %s);",
							$idFactura,
							date("Y-m-d"),
							$idNotaCredito,
							valTpDato($rowFact['saldoFactura'], "real_inglesa"),
							$valForm['txtNroFacturaVentaServ'],
							valTpDato($idEncabezadoPago, "int"));
		$rsInsertSql = mysql_query($insertSQL);
		if (!$rsInsertSql) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL);	
		
		$idPago = mysql_insert_id();
		
		// INSERTA EL DETALLE DEL RECIBO DE PAGO
		$insertSQL = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
		VALUES (%s, %s)",
			valTpDato($idEncabezadoReciboPago, "int"),
			valTpDato($idPago, "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nLine: ".__LINE__."\nSql: ".$insertSQL); }
	}
	
	// BUSCA LOS REPUESTOS FACTURADOS DE LA ORDEN PARA DEVOLVERLOS
	$queryArt = sprintf("SELECT 
		sa_det_solicitud_repuestos.id_det_orden_articulo,
		COUNT(sa_det_solicitud_repuestos.id_det_orden_articulo) AS nro_rpto_desp,
		sa_det_solicitud_repuestos.id_casilla,
		sa_det_orden_articulo.id_articulo,
		sa_det_orden_articulo.costo,
		sa_det_orden_articulo.id_articulo_costo,
		sa_det_orden_articulo.id_articulo_almacen_costo
	FROM sa_solicitud_repuestos
		INNER JOIN sa_det_solicitud_repuestos ON (sa_solicitud_repuestos.id_solicitud = sa_det_solicitud_repuestos.id_solicitud)
		INNER JOIN sa_orden ON (sa_solicitud_repuestos.id_orden = sa_orden.id_orden)
		INNER JOIN sa_det_orden_articulo ON (sa_det_solicitud_repuestos.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo)
	WHERE sa_solicitud_repuestos.estado_solicitud = 5
		AND sa_det_solicitud_repuestos.id_estado_solicitud = 5
		AND sa_orden.id_empresa = %s
		AND sa_orden.id_orden = %s
	GROUP BY sa_det_solicitud_repuestos.id_det_orden_articulo,
		sa_det_solicitud_repuestos.id_casilla",
		valTpDato($idEmpresa, "int"),
		valTpDato($idOrden, "int"));
	$rsArt = mysql_query($queryArt);
	if (!$rsArt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryArt);
	$contArt = mysql_num_rows($rsArt);
	
		// INSERTA EL MOVIMIENTO
	if ($contArt > 0) {
		$insertSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento,id_clave_movimiento, tipo_documento_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
		VALUE (%s, %s, %s, %s, NOW(), %s, %s, NOW(), %s, %s)",
			valTpDato($idTipoMovimiento, "int"), //gregor
			valTpDato($idClaveMovimiento, "int"),
			valTpDato(2, "int"), // 1 = Vale Entrada / Salida, 2 = Nota Credito
			valTpDato($idNotaCredito, "int"),
			valTpDato($rowFact['idCliente'], "int"), //id cliente proveedor
			valTpDato(0, "boolean"), // tipo costo
			valTpDato($_SESSION['idUsuarioSysGts'], "int"),
			valTpDato($tipoPago, "boolean"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$idMovimiento = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	while ($rowArt = mysql_fetch_array($rsArt)) {
		$idArticulo = $rowArt['id_articulo'];
		
		if ($rowArt['nro_rpto_desp'] > 0) {
			$idCasilla = $rowArt['id_casilla'];
			
			$contArt++;

			// VERIFICA EL ESTATUS DE LA CASILLA CON LA CUAL SE HABIA DESPACHADO EL ARTICULO
			$estatusCasillaSQL = sprintf("SELECT estatus FROM iv_articulos_almacen
			WHERE id_articulo = %s
				AND id_casilla = %s",
				$idArticulo,
				$idCasilla);
			$rsEstatusCasillaSQL = mysql_query($estatusCasillaSQL);
			if (!$rsEstatusCasillaSQL) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$estatusCasillaSQL);
			$rowEstatusCasillaSQL = mysql_fetch_assoc($rsEstatusCasillaSQL);
			if ($rowEstatusCasillaSQL['estatus']) {
				$idCasilla = $rowArt['id_casilla'];
			} else {
				$nuevaCasillaSQL = sprintf("SELECT id_casilla_predeterminada FROM iv_articulos_empresa
				WHERE id_articulo = %s
					AND id_empresa = %s",
					$idArticulo,
					$idEmpresa);
				$rsNuevaCasillaSQL = mysql_query($nuevaCasillaSQL);
				if (!$rsNuevaCasillaSQL) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$nuevaCasillaSQL);
				$rowNuevaCasillaSQL = mysql_fetch_assoc($rsNuevaCasillaSQL);
				
				$idCasilla = $rowNuevaCasillaSQL['id_casilla_predeterminada'];
			}
			
			if ($idCasilla == NULL || $idCasilla == ""){
				$articuloSql = sprintf("SELECT
					codigo_articulo,
					descripcion
				FROM iv_articulos
				WHERE id_articulo = %s",
					$idArticulo);
				$rsArticuloSql = mysql_query($articuloSql);
				if (!$rsArticuloSql) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$articuloSql);
				$rowArticuloSql = mysql_fetch_assoc($rsArticuloSql);
				
				return $objResponse->alert("No se puede realizar la Nota de Credito debido a que Existen Repuestos Sin Ubicacion:\n\tCodigo: ".$rowArticuloSql['codigo_articulo']."\n\tDescripcion: ".utf8_encode($rowArticuloSql['descripcion']));
			} else {
				
				$sqlIndividual = "";
				if($rowArt['id_articulo_costo'] > 0){
					$sqlIndividual = sprintf(" AND fact_vent_det.id_articulo_costo = %s 
									AND fact_vent_det.id_articulo_almacen_costo = %s ",
									$rowArt['id_articulo_costo'],
									$rowArt['id_articulo_almacen_costo']);
				}
				
				// busco costo precio con el que salio el repuesto, se usa en el movimiento detalle y iv_kardex
				$queryFactDet = sprintf("SELECT  
					fact_vent_det.id_articulo,
					fact_vent_det.cantidad,
					fact_vent_det.precio_unitario,
					fact_vent_det.id_articulo_costo,
					fact_vent_det.id_articulo_almacen_costo,
					fact_vent_det.costo_compra,
					(SELECT valor FROM pg_configuracion_empresa config_emp
						INNER JOIN pg_configuracion config ON config_emp.id_configuracion = config.id_configuracion
						WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = fact_vent.id_empresa) as modo_costo,
						
					(SELECT costo_promedio FROM iv_articulos_costos 
							WHERE id_articulo = fact_vent_det.id_articulo ORDER BY id_articulo_costo DESC LIMIT 1) AS costo_promedio
						
				FROM cj_cc_encabezadofactura fact_vent
					INNER JOIN cj_cc_factura_detalle fact_vent_det ON (fact_vent.idFactura = fact_vent_det.id_factura)
				WHERE fact_vent.idFactura = %s 
				AND fact_vent_det.id_articulo = %s %s LIMIT 1",
					valTpDato($idFactura, "int"),
					valTpDato($idArticulo, "int"),
					$sqlIndividual);//COSTO INDIVIDUAL, ARTICULO AGRUPA
				$rsFactDet = mysql_query($queryFactDet);
				if (!$rsFactDet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$rowFactDet = mysql_fetch_assoc($rsFactDet);
				
				$queryCompruebaLote = "SHOW TABLES LIKE 'vw_iv_articulos_almacen_costo'";
				$rsCompruebaLote = mysql_query($queryCompruebaLote);
				if (!$rsCompruebaLote){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				
				$compruebaLote = mysql_num_rows($rsCompruebaLote);
				
				if($compruebaLote > 0){//USA LOTES				
				
					//buscar id costo id almacen actual
					if($rowFactDet["modo_costo"] == 1 || $rowFactDet["modo_costo"] == 2 || !($rowFactDet['id_articulo_almacen_costo'] > 0)){// 1 = Reposici�n, 2 = Promedio, 3 = FIFO

						$queryArtCosto = sprintf("SELECT * FROM vw_iv_articulos_almacen_costo vw_iv_art_almacen_costo
								WHERE vw_iv_art_almacen_costo.id_articulo = %s
										AND vw_iv_art_almacen_costo.id_casilla = %s
										AND vw_iv_art_almacen_costo.estatus_articulo_costo = 1
								ORDER BY vw_iv_art_almacen_costo.id_articulo_costo ASC;",
										valTpDato($idArticulo, "int"),
										valTpDato($idCasilla, "int"));
						$rsArtCosto = mysql_query($queryArtCosto);
						if (!$rsArtCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$totalRowsArtCosto = mysql_num_rows($rsArtCosto);
						$rowArtCosto = mysql_fetch_assoc($rsArtCosto);

						$idArticuloAlmacenCosto = $rowArtCosto['id_articulo_almacen_costo'];
						$idArticuloCosto = $rowArtCosto['id_articulo_costo'];                        

					}else{//si es fifo 3 se mantiene
						$idArticuloAlmacenCosto = $rowFactDet['id_articulo_almacen_costo'];
						$idArticuloCosto = $rowFactDet['id_articulo_costo'];       
					}
				
					$queryCostoActual = sprintf("SELECT costo, costo_promedio FROM vw_iv_articulos_almacen_costo 
										WHERE id_articulo_almacen_costo = %s AND id_articulo_costo = %s LIMIT 1",
										$idArticuloAlmacenCosto, 
										$idArticuloCosto);
		
					$rsCostoActual = mysql_query($queryCostoActual);
					if (!$rsCostoActual) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryCostoActual); }
					$rowCostoActual = mysql_fetch_assoc($rsCostoActual);
                                
                                
					if($rowFactDet["modo_costo"] == 1 || $rowFactDet["modo_costo"] == 3){
						$costoPromedioCompra = $rowCostoActual["costo"];
					}else{
						$costoPromedioCompra = $rowCostoActual["costo_promedio"];
					}
				
				}else{//NO USA LOTES
					if($rowFactDet["modo_costo"] == 1){
						$costoPromedioCompra = $rowFactDet["costo_compra"];
					}else{
						$costoPromedioCompra = $rowFactDet["costo_promedio"];
					}
				}
				
				if($compruebaLote > 0){//USA LOTES
					// VERIFICA SI EL LOTE TIENE LA UBICACION ASIGNADA
					$queryArtAlmCosto = sprintf("SELECT *
					FROM iv_articulos_almacen art_almacen
						INNER JOIN iv_articulos_almacen_costo art_almacen_costo ON (art_almacen.id_articulo_almacen = art_almacen_costo.id_articulo_almacen)
					WHERE art_almacen.id_articulo = %s
						AND art_almacen.id_casilla = %s
						AND art_almacen_costo.id_articulo_costo = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"),
						valTpDato($idArticuloCosto, "int"));
					$rsArtAlmCosto = mysql_query($queryArtAlmCosto);
					if (!$rsArtAlmCosto) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$totalRowsArtAlm = mysql_num_rows($rsArtAlmCosto);
					$rowArtAlmCosto = mysql_fetch_assoc($rsArtAlmCosto);
					
					$hddIdArticuloAlmacenCosto = $rowArtAlmCosto['id_articulo_almacen_costo'];
				
					if ($totalRowsArtAlm > 0) {
						// ACTUALIZA EL ESTATUS DE LA UBICACION DEL LOTE
						$updateSQL = sprintf("UPDATE iv_articulos_almacen_costo SET
							estatus = 1
						WHERE id_articulo_almacen_costo = %s;",
							valTpDato($hddIdArticuloAlmacenCosto, "int"));
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					} else {
						// LE ASIGNA EL LOTE A LA UBICACION
						$insertSQL = sprintf("INSERT INTO iv_articulos_almacen_costo (id_articulo_almacen, id_articulo_costo, estatus)
						SELECT art_almacen.id_articulo_almacen, %s, 1 FROM iv_articulos_almacen art_almacen
						WHERE art_almacen.id_casilla = %s
							AND art_almacen.id_articulo = %s
							AND art_almacen.estatus = 1;",
								valTpDato($idArticuloCosto, "int"),
								valTpDato($idCasilla, "int"),
								valTpDato($idArticulo, "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$hddIdArticuloAlmacenCosto = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
					}
					
					$idArticuloAlmacenCosto = $hddIdArticuloAlmacenCosto;
					 
					if($idArticuloAlmacenCosto == ""){
						return $objResponse->alert("No se pudo tomar la relacion con almacen en la devolucion \nLote: ".$idArticuloCosto." \nAlm Cost: ".$idArticuloAlmacenCosto." \nLinea: ".__LINE__);
					}
				}
				
				// REGISTRA EL MOVIMIENTO DEL ARTICULO
				$insertSQLKardex = sprintf("INSERT INTO iv_kardex (id_documento, id_modulo, id_articulo, id_casilla, tipo_movimiento, tipo_documento_movimiento, cantidad, precio, id_articulo_costo, id_articulo_almacen_costo, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, id_clave_movimiento, estado, fecha_movimiento, hora_movimiento)
				VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())",
					valTpDato($idNotaCredito, "int"),
					valTpDato("1", "int"), // 0 = Repuestos, 1 = Servicios, 2 = Vehiculos, 3 = Administracion
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"),
					valTpDato($rowClaveMov['tipo'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
					valTpDato(2, "int"), // 2 DEVOLUCION FACTURA = NOTA CREDITO, 1 DEVOLUCION VALE SALIDA = VALE ENTRADA
					valTpDato($rowArt['nro_rpto_desp'], "int"),
					valTpDato($rowFactDet['precio_unitario'], "real_inglesa"),
					valTpDato($idArticuloCosto, "int"),
					valTpDato($idArticuloAlmacenCosto, "int"),
					valTpDato($costoPromedioCompra, "real_inglesa"),
					valTpDato(0, "int"),//costo cargo
					valTpDato($valFormTotalDcto['txtDescuento'], "text"),
					valTpDato((($valFormTotalDcto['txtDescuento'] * $rowFactDet['precio_unitario']) / 100), "real_inglesa"),
					valTpDato($idClaveMovimiento, "int"),
					valTpDato(0, "int")); // 0 = Entrada, 1 = Salida
					
				mysql_query("SET NAMES 'utf8';");
				$Result1Kardex = mysql_query($insertSQLKardex);
				if (!$Result1Kardex) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				$idKardex = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");				
				
				
				// HACER UN SELECT DE LOS ARTICULOS QUE FUERON FACTURADOS TRAERLOS DE LA TABLA cj_cc_factura_detalle
				// QUE PASA SI EL ARTICULO NO TIENE DESCUENTO INDIVIDUAL?				
				
				
				$insertSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, id_articulo_costo, id_articulo_almacen_costo, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, correlativo1, correlativo2, tipo_costo, llave_costo_identificado, promocion, id_moneda_costo, id_moneda_costo_cambio)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
						valTpDato($idMovimiento, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($idKardex, "int"),
						valTpDato($rowArt['nro_rpto_desp'], "int"),//antes $rowFactDet['cantidad']
						valTpDato($rowFactDet['precio_unitario'], "real_inglesa"),
						valTpDato($idArticuloCosto, "int"),
						valTpDato($idArticuloAlmacenCosto, "int"),
						valTpDato($costoPromedioCompra, "real_inglesa"),
						valTpDato(0, "int"),//costo cargo
						valTpDato($valFormTotalDcto['txtDescuento'], "text"),
						valTpDato((($valFormTotalDcto['txtDescuento'] * $rowFactDet['precio_unitario']) / 100), "real_inglesa"),
						valTpDato("", "int"),
						valTpDato("", "int"),
						valTpDato(0, "int"),
						valTpDato("", "text"),
						valTpDato(0, "boolean"),
						valTpDato("", "int"),
						valTpDato("", "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					mysql_query("SET NAMES 'latin1';");
				
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS)
				
				// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO - Roger
				$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
				$Result1 = actualizarSaldos($idArticulo, $idCasilla);
				if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
				
				/*$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
					cantidad_entrada = (SELECT SUM(kardex.cantidad)
										FROM iv_kardex kardex
										WHERE (kardex.tipo_movimiento = 1 OR kardex.tipo_movimiento = 2)
											AND kardex.id_articulo = iv_articulos_almacen.id_articulo
											AND kardex.id_casilla = iv_articulos_almacen.id_casilla)
				WHERE iv_articulos_almacen.id_articulo = %s
					AND iv_articulos_almacen.id_casilla = %s;",
					valTpDato($idArticulo, "int"),
					valTpDato($idCasilla, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");

				// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO (ENTRADAS)
				$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
					cantidad_entrada = (cantidad_entrada + %s),
					valor_entrada = (valor_entrada + %s)
				WHERE id_articulo = %s
					AND id_empresa = %s;",
					valTpDato($rowArt['nro_rpto_desp'], "double"),
					valTpDato(($rowArt['nro_rpto_desp'] * $rowArt['costo']), "double"),
					valTpDato($idArticulo, "int"),
					valTpDato($idEmpresa, "int"));
				mysql_query("SET NAMES 'utf8';");
				$Result1 = mysql_query($updateSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
				mysql_query("SET NAMES 'latin1';");*/
			}
		}
	}
	
	// REGISTRA EL ESTADO DE CUENTA
	$insertSQLEdoCuenta = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);", 
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d"), "date"),
		valTpDato(3, "int"));// 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB
	mysql_query("SET NAMES 'utf8';");
	$Result1EdoCuenta = mysql_query($insertSQLEdoCuenta);
	if (!$Result1EdoCuenta) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	
	//empleado factura gregor
	$sqlIdEmpleado = sprintf("SELECT id_empleado FROM pg_usuario WHERE id_usuario = %s LIMIT 1",
						valTpDato($_SESSION["idUsuarioSysGts"], "int"));
	$queryIdEmpleado = mysql_query($sqlIdEmpleado);
	if(!$queryIdEmpleado){ $objResponse->alert("Error buscando id empleado para anular factura \n".mysql_error()."\n Linea:".__LINE__); }
	$rowIdEmpleado = mysql_fetch_assoc($queryIdEmpleado);
	$idEmpleadoFactura = $rowIdEmpleado["id_empleado"];
	
	// ACTUALIZA LOS DATOS DE LA FACTURA DE VENTA
	$queryActualizarEdoFactura = sprintf("UPDATE cj_cc_encabezadofactura SET
		saldoFactura = '0',
		estadoFactura = %s,
		anulada = 'SI',
		id_empleado_anulacion = %s,
		fecha_anulacion = NOW()
	WHERE idFactura = %s;",
		valTpDato(1, "int"), // 0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		valTpDato($idEmpleadoFactura, "int"),
		valTpDato($idFactura, "int"));
	mysql_query("SET NAMES 'utf8';");
	$rsActualizarEdoFactura = mysql_query($queryActualizarEdoFactura);
	if (!$rsActualizarEdoFactura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	devolverComisionNC($idNotaCredito, $idFactura);
	
	if ($rowFact['condicionDePago'] == 0) {
		$sqlActualizarSaldo = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
				creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
															WHERE fact_vent.idCliente = cliente_emp.id_cliente
																AND fact_vent.id_empresa = cliente_emp.id_empresa
																AND fact_vent.estadoFactura IN (0,2)), 0)
													+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
															WHERE nota_cargo.idCliente = cliente_emp.id_cliente
																AND nota_cargo.id_empresa = cliente_emp.id_empresa
																AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
													- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
															WHERE anticip.idCliente = cliente_emp.id_cliente
																AND anticip.id_empresa = cliente_emp.id_empresa
																AND anticip.estadoAnticipo IN (1,2)
																AND anticip.estatus = 1), 0)
													- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
															WHERE nota_cred.idCliente = cliente_emp.id_cliente
																AND nota_cred.id_empresa = cliente_emp.id_empresa
																AND nota_cred.estadoNotaCredito IN (1,2)), 0)
													+ IFNULL((SELECT
																SUM(IFNULL(ped_vent.subtotal, 0)
																	- IFNULL(ped_vent.subtotal_descuento, 0)
																	+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																			WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
																	+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																			WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
															FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_cliente = cliente_emp.id_cliente
																AND ped_vent.id_empresa = cliente_emp.id_empresa
																AND ped_vent.estatus_pedido_venta IN (2)), 0)),
				creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
											WHERE fact_vent.idCliente = cliente_emp.id_cliente
												AND fact_vent.id_empresa = cliente_emp.id_empresa
												AND fact_vent.estadoFactura IN (0,2)), 0)
									+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
											WHERE nota_cargo.idCliente = cliente_emp.id_cliente
												AND nota_cargo.id_empresa = cliente_emp.id_empresa
												AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
									- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
											WHERE anticip.idCliente = cliente_emp.id_cliente
												AND anticip.id_empresa = cliente_emp.id_empresa
												AND anticip.estadoAnticipo IN (1,2)
												AND anticip.estatus = 1), 0)
									- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
											WHERE nota_cred.idCliente = cliente_emp.id_cliente
												AND nota_cred.id_empresa = cliente_emp.id_empresa
												AND nota_cred.estadoNotaCredito IN (1,2)), 0)
									+ IFNULL((SELECT
												SUM(IFNULL(ped_vent.subtotal, 0)
													- IFNULL(ped_vent.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
															WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
													+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
															WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
											FROM iv_pedido_venta ped_vent
											WHERE ped_vent.id_cliente = cliente_emp.id_cliente
												AND ped_vent.id_empresa = cliente_emp.id_empresa
												AND ped_vent.estatus_pedido_venta IN (2)
												AND id_empleado_aprobador IS NOT NULL), 0))
			WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
				AND cliente_emp.id_cliente = %s
				AND cliente_emp.id_empresa = %s;",
			valTpDato($rowFact['idCliente'], "int"),
			valTpDato($idEmpresa, "int"));
		$rsActualizarSaldo = mysql_query($sqlActualizarSaldo);
		if (!$rsActualizarSaldo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQL);
	}
		
	$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	}
	
	mysql_query("COMMIT;");
		
	//MODIFICADO ERNESTO
	if (function_exists("generarNotasVentasSe")) { generarNotasVentasSe($idNotaCredito,"",""); }
	//MODIFICADO ERNESTO
	
	$objResponse->alert("Nota De Credito Guardada Exitosamente");

	$objResponse->script(sprintf("window.location.href='cjrs_devolucion_venta_list.php';"));
	
	$objResponse->script(sprintf("verVentana('../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s', 960, 550);",
		$idNotaCredito));
		
	return $objResponse;
}


//CREA LA FACTURA EN CC ENCABEZADO FACTURA
function guardarFactura($frmPresupuesto, $frmTotalPresupuesto, $frmDetallePago, $frmListadoPagos){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cjrs_factura_venta_list","insertar")) { errorGuardarFactura($objResponse); return $objResponse; }
	
	$objResponse->loadCommands(validarAperturaCaja());
	
	$idEmpresa = $frmPresupuesto['txtIdEmpresa'];
	$idModulo = 1; // 0 = Repuestos, 1 = Sevicios, 2 = Vehiculos, 3 = Administracion
	$idOrden = $frmPresupuesto['txtIdPresupuesto'];
	$idTipoOrden = $frmPresupuesto['lstTipoOrden'];
	$idClaveMovimiento = $frmPresupuesto['lstClaveMovimiento'];

	//VERIFICA SI EL DOCUMENTO YA HA SIDO FACTURADO
	$queryVerif = sprintf("SELECT * FROM cj_cc_encabezadofactura
		WHERE numeroPedido = %s
		AND idDepartamentoOrigenFactura IN (%s) LIMIT 1",
	valTpDato($idOrden, "int"),
	valTpDato($idModulo, "int"));
	$rsVerif = mysql_query($queryVerif);
	if (!$rsVerif) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	if(mysql_num_rows($rsVerif) != 0){
		return $objResponse->alert('Este documento ya ha sido facturado');
	}
	
	//CONSULTO LA CLAVE DE MOVIMIENTO SEGUN EL TIPO DE LA ORDEN
	$queryTipoDoc = sprintf("SELECT * FROM sa_tipo_orden WHERE id_tipo_orden = %s",
		valTpDato($idTipoOrden, "int"));
	$rsTipoDoc = mysql_query($queryTipoDoc);
	if (!$rsTipoDoc) { errorGuardarFactura($objResponse);  return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowTipoDoc = mysql_fetch_assoc($rsTipoDoc);
	
	$idClaveMovimiento = $rowTipoDoc["id_clave_movimiento"];
	$idFiltroOrden = $rowTipoDoc['id_filtro_orden'];
	
	//CONSULTO EL TIPO DE DOCUMENTO QUE GENERA Y SI ES DE CONTADO
	$queryDocGenera = sprintf("SELECT * FROM pg_clave_movimiento
	WHERE id_clave_movimiento = %s
		AND documento_genera = %s
		AND pago_contado = %s",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato(1, "int"), // 1 = FACTURA
		valTpDato(1, "int")); // 0 = NO ; 1 = SI
	$rsDocGenera = mysql_query($queryDocGenera);
	if (!$rsDocGenera) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowDocGenera = mysql_fetch_assoc($rsDocGenera);
	if (mysql_num_rows($rsDocGenera) > 0) {
		
		//VERIFICA QUE EL DOOCUMENTO A CONTADO ESTE CANCELADO EN TU TOTALIDAD
		if ($frmListadoPagos['txtMontoPorPagar'] <> 0) {
			errorGuardarFactura($objResponse); return $objResponse->alert('Debe cancelar el monto total de la factura');
		}
	}
			
	
	
	//VERIFICAR SI YA LA ORDEN FUE FACTURADA
	$sqlVerificarEstatusOrden = sprintf("SELECT * FROM sa_orden WHERE id_orden = %s AND id_estado_orden IN (18, 24);",
			valTpDato($idOrden, "int"));
	$rsVerificarEstatusOrden = mysql_query($sqlVerificarEstatusOrden);
	if (!$rsVerificarEstatusOrden) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlVerificarEstatusOrden); }
	if (mysql_num_rows($rsVerificarEstatusOrden) > 0){
		$objResponse->script("$('btnGuardar').style.display = 'none'");
		return $objResponse->alert("La Orden ".$idOrden." ya fue facturada");
	}
	
	$query = sprintf("SELECT idFactura FROM cj_cc_encabezadofactura
	WHERE numeroControl = %s
		AND idDepartamentoOrigenFactura = 1",
		valTpDato($frmPresupuesto['txtNroControl'], "text"));
	$rs = mysql_query($query);
	if (!$rs)  { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);	
	
	if ($totalRows > 0) {
		errorGuardarFactura($objResponse); $objResponse->alert("El Numero de Control que Desea Registrar ya Existe Registrado en la Base de Datos");
	} else {
		
		mysql_query("START TRANSACTION;");
		
		//Puesto adelante para que ocurra primero y no quede la orden en el aire = 13
		// MODIFICA EL ESTADO DE LA ORDEN A FINALIZADA
		$queryActOrden = sprintf("UPDATE sa_orden SET
			fecha_factura = NOW(),
			id_estado_orden = 18
		WHERE id_orden = %s",
			 valTpDato($idOrden, "int"));
		//mysql_query("SET NAMES 'utf8';");
		$rsActOrden = mysql_query($queryActOrden);
		if (!$rsActOrden) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryActOrden); }
		//mysql_query("SET NAMES 'latin1';");
		
		// MODIFICA EL ESTADO DEL VALE DE RECEPCION
		$queryActualizarEdoValeAfact = sprintf("UPDATE sa_recepcion SET
			estado = 1
		WHERE id_recepcion = %s",
			valTpDato($frmPresupuesto['txtIdValeRecepcion'], "int"));
		//mysql_query("SET NAMES 'utf8';");
		$rsActualizarActualizarEdoValeAfact = mysql_query($queryActualizarEdoValeAfact);
		if (!$rsActualizarActualizarEdoValeAfact) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryActualizarEdoValeAfact); }
		//mysql_query("SET NAMES 'latin1';");
				
		// BUSCA LOS DATOS DE LA ORDEN
		$queryOrden = sprintf("SELECT * FROM sa_orden WHERE id_orden = %s;",
			valTpDato($idOrden, "int"));
		$rsOrden = mysql_query($queryOrden);
		if (!$rsOrden) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryClaveMov); }
		$rowOrden = mysql_fetch_assoc($rsOrden);
		
		$idEmpleado = $rowOrden['id_empleado'];
		$idTipoOrden = $rowOrden['id_tipo_orden'];
		
		// BUSCA LOS DIAS DE CREDITO DEL CLIENTE
		$queryDiasCre = sprintf("SELECT cliente_cred.diascredito
			FROM cj_cc_credito cliente_cred
				INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente_cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
			WHERE cliente_emp.id_cliente = %s
			AND cliente_emp.id_empresa = %s",
			valTpDato($frmPresupuesto['txtIdCliente'], "int"),
			valTpDato($idEmpresa, "int"));		
		$rsDiasCre = mysql_query($queryDiasCre);
		if (!$rsDiasCre) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryDiasCre); }
		$rowClienteCredito = mysql_fetch_assoc($rsDiasCre);
		
		$diasCreditoCliente = ($rowClienteCredito['diascredito'] == '') ? 0 : $rowClienteCredito['diascredito'];
		$fechaVencimientoFactura = suma_fechas("d-m-Y",date("d-m-Y"), $rowClienteCredito['diascredito']);
		
		// BUSCA LA CLAVE DE MOVIMIENTO PARA SABER EL TIPO DE PAGO DE LA ORDEN
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento WHERE id_clave_movimiento = %s;",
			valTpDato($idClaveMovimiento, "int"));
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryClaveMov); }
		$rowClaveMov = mysql_fetch_assoc($rsClaveMov);
		
		$idTipoMovimiento = $rowClaveMov["tipo"];
		
		if ($rowClaveMov['pago_credito'] == 1) {
			$tipoPago = 0; // 0 = Credito, 1 = Contado
		} else if ($rowClaveMov['pago_contado'] == 1) {
			$tipoPago = 1; // 0 = Credito, 1 = Contado
		} else {
			$tipoPago = 1; // 0 = Credito, 1 = Contado
		}
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
		WHERE id_numeracion = (SELECT clave_mov.id_numeracion_documento FROM pg_clave_movimiento clave_mov
								WHERE clave_mov.id_clave_movimiento = %s)
			AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																			WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC
		LIMIT 1;",
			valTpDato($idClaveMovimiento, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		
		if (!$rsNumeracion) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$numeroActual = $rowNumeracion['numero_actual'];
		
		//empleado factura gregor
		$sqlIdEmpleado = sprintf("SELECT id_empleado FROM pg_usuario WHERE id_usuario = %s LIMIT 1",
							valTpDato($_SESSION["idUsuarioSysGts"], "int"));
		$queryIdEmpleado = mysql_query($sqlIdEmpleado);
		if(!$queryIdEmpleado) { errorGuardarFactura($objResponse); $objResponse->alert("Error buscando id empleado para factura \n".mysql_error()."\n Linea:".__LINE__); }
		$rowIdEmpleado = mysql_fetch_assoc($queryIdEmpleado);
		
		$idEmpleadoFactura = $rowIdEmpleado["id_empleado"];

		if($frmTotalPresupuesto['txtIvaVenta'] == NULL){
			$frmTotalPresupuesto['txtIvaVenta'] = 0;
		}
		
                $totalIvas = 0;
                foreach ($frmTotalPresupuesto["ivaActivo"] as $key => $idIvaActivo){
                    $totalIvas += str_replace(",","", $frmTotalPresupuesto["txtTotalIva".$idIvaActivo]);
                }
		
		//del id array de ivas, busco el primero de todos los ivas, lo coloco para averiguar base imponible y total iva
		if($totalIvas > 0){
			$idIvaSimple = array_shift(array_values($frmTotalPresupuesto["ivaActivo"]));
			$porcentajeIvaSimple = $frmTotalPresupuesto["txtIvaVenta".$idIvaSimple];
			$baseImponibleSimple = $frmTotalPresupuesto["txtBaseImponibleIva".$idIvaSimple];
		}
		
		$insertSQLFactura = sprintf("INSERT INTO cj_cc_encabezadofactura (numeroControl, fechaRegistroFactura, numeroFactura, fechaVencimientoFactura, montoTotalFactura, saldoFactura, estadoFactura, idVendedor, idCliente, numeroPedido, idDepartamentoOrigenFactura, descuentoFactura, porcentaje_descuento, porcentajeIvaFactura, calculoIvaFactura, subtotalFactura, condicionDePago, baseImponible, diasDeCredito, montoExento, anulada, aplicaLibros, id_empresa, id_empleado_creador)
		VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
			valTpDato($frmPresupuesto['txtNroControl'], "text"),
			valTpDato(date("Y-m-d"), "date"),
			valTpDato($numeroActual, "text"),
			valTpDato(date("Y-m-d",strtotime($fechaVencimientoFactura)), "date"),
			str_replace(",","", $frmTotalPresupuesto['txtTotalPresupuesto']),
			str_replace(",","", $frmTotalPresupuesto['txtTotalPresupuesto']),
			valTpDato(0, "int"), // 0 = No Cancelada, 1 = Cancelada , 2 = Parcialmente Cancelada
			valTpDato($idEmpleado, "int"),
			valTpDato($frmPresupuesto['txtIdCliente'], "int"),
			valTpDato($idOrden, "int"),
			valTpDato(1, "int"), // 0 = Repuesto, 1 = Sevicios, 2 = Autos, 3 = Administracion
			str_replace(",","", $frmTotalPresupuesto['txtSubTotalDescuento']),
			valTpDato(str_replace(",","", $frmTotalPresupuesto['txtDescuento']),"double"),
			valTpDato(str_replace(",","", $porcentajeIvaSimple),"double"),//solo simple impuestos 
			str_replace(",","", $totalIvas),
			str_replace(",","", $frmTotalPresupuesto['txtSubTotal']),
			valTpDato($tipoPago, "int"),
			str_replace(",","", $baseImponibleSimple),//solo simple impuestos 
			valTpDato($diasCreditoCliente, "int"),
			str_replace(",","", $frmTotalPresupuesto['txtMontoExento']),
			valTpDato("NO", "text"),
			valTpDato(1, "int"), // 0 = No, 1 = Si
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpleadoFactura, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQLFactura);
		if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$insertSQLFactura); }
		$idFactura = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
                //IVAS ORDEN
                $query = sprintf("INSERT INTO cj_cc_factura_iva (id_factura, base_imponible, subtotal_iva, id_iva, iva, lujo)
                    SELECT %s, base_imponible, subtotal_iva, id_iva, iva, lujo
                    FROM sa_orden_iva 
                    WHERE id_orden = %s 
                    ",
                    $idFactura,
                    valTpDato($idOrden, "int"));
                $rs = mysql_query($query);
                if(!$rs) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                
		// BUSCA LOS ARTICULOS DE LA ORDEN
		$queryArt = sprintf("SELECT
			det_orden_art.id_articulo,
			det_orden_art.id_paquete,
			det_orden_art.cantidad,
			det_orden_art.id_precio,
			det_orden_art.precio_unitario,
			det_orden_art.id_articulo_costo,
			det_orden_art.id_articulo_almacen_costo,
			
			(SELECT
			if((SELECT valor FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON config_emp.id_configuracion = config.id_configuracion
			WHERE config.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s) IN(1,3), 
                                                                                (SELECT vw_iv_articulos_almacen_costo.costo FROM vw_iv_articulos_almacen_costo WHERE vw_iv_articulos_almacen_costo.id_articulo_costo = det_orden_art.id_articulo_costo LIMIT 1), 
                                                                                (SELECT vw_iv_articulos_almacen_costo.costo_promedio FROM vw_iv_articulos_almacen_costo WHERE vw_iv_articulos_almacen_costo.id_articulo_costo = det_orden_art.id_articulo_costo LIMIT 1)
                        )) AS costo,
						
			det_orden_art.id_iva,
			det_orden_art.iva,
			det_orden_art.aprobado,
			det_orden_art.tiempo_asignacion,
			det_orden_art.tiempo_aprobacion,
			det_orden_art.id_empleado_aprobacion,
			det_orden_art.estado_articulo,
			det_orden_art.estado_articulo + 0 AS edo_articulo,
			det_orden_art.id_det_orden_articulo,
			orden.porcentaje_descuento,
			(((det_orden_art.precio_unitario * det_orden_art.cantidad) * orden.porcentaje_descuento) / 100) AS subtotal_descuento
		FROM sa_orden orden
			INNER JOIN sa_det_orden_articulo det_orden_art ON (orden.id_orden = det_orden_art.id_orden)
		WHERE orden.id_empresa = %s
			AND orden.id_orden = %s
			AND det_orden_art.aprobado = 1
			AND det_orden_art.estado_articulo <> 'DEVUELTO';",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idOrden, "int"));
		$rsArt = mysql_query($queryArt);
		if (!$rsArt) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryArt); }
		
		//*************************************************************************************************************
		if (mysql_num_rows($rsArt) > 0) {
                    
                    /************************************************************************************************/
                    //COMPRUEBO QUE TENGA REPUESTO EN SOLICITUD COMO DESPACHADO, POR LO MENOS 1                                
                    $queryComprobacion = sprintf("SELECT sa_orden.id_orden 
                                        FROM sa_orden 
                                        LEFT JOIN sa_solicitud_repuestos ON sa_orden.id_orden = sa_solicitud_repuestos.id_orden
                                        LEFT JOIN sa_det_solicitud_repuestos ON sa_solicitud_repuestos.id_solicitud = sa_det_solicitud_repuestos.id_solicitud
                                        WHERE sa_orden.id_orden = %s AND sa_det_solicitud_repuestos.id_estado_solicitud = 3", //3 DESPACHADO
                                        valTpDato($idOrden, "int"));

                    $rsComprobacion = mysql_query($queryComprobacion);
                    if(!$rsComprobacion){ errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."sele comprob repst \n\nLine: ".__LINE__); }
                    
                    if(mysql_num_rows($rsComprobacion)){//si tiene despachado, crear movimiento
			// INSERTA EL MOVIMIENTO
			$insertMovimientoSQL = sprintf("INSERT INTO iv_movimiento (id_tipo_movimiento, id_clave_movimiento, id_documento, fecha_movimiento, id_cliente_proveedor, tipo_costo, fecha_captura, id_usuario, credito)
			VALUE (%s, %s, %s, NOW(), %s, %s, NOW(), %s, %s)",
				valTpDato($idTipoMovimiento, "int"),
				valTpDato($idClaveMovimiento, "int"),
				valTpDato($idFactura, "int"),
				valTpDato($frmPresupuesto['txtIdCliente'], "int"),
				valTpDato(0, "boolean"),
				valTpDato($_SESSION['idUsuarioSysGts'], "int"),
				valTpDato($tipoPago, "boolean"));
			$ResultMovimiento1 = mysql_query($insertMovimientoSQL);
			if (!$ResultMovimiento1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
			$idMovimiento = mysql_insert_id();
                    }
                    /**************************************************************************************************/
                    
			// INSERTA EL DETALLE DEL MOVIMIENTO
			while($rowArt = mysql_fetch_array($rsArt)) {
				$idArticulo = $rowArt['id_articulo'];
				$costoArt = $rowArt['costo'];
				
				// BUSCA LA CANTIDAD DE REPUESTOS DESPACHADOS
				$queryCont = sprintf("SELECT COUNT(*) AS nro_rpto_desp FROM sa_det_solicitud_repuestos
				WHERE id_det_orden_articulo = %s
					AND id_estado_solicitud = 3", 
					valTpDato($rowArt['id_det_orden_articulo'], "int"));
				$rsCont = mysql_query($queryCont);
				if (!$rsCont) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				$rowCont = mysql_fetch_assoc($rsCont);
				
				if ($rowCont['nro_rpto_desp'] > 0) {
					$insertSQLFacturaDetalle = sprintf("INSERT INTO cj_cc_factura_detalle (id_factura, id_articulo, cantidad, pendiente, estatus, precio_unitario, id_articulo_costo, id_articulo_almacen_costo, costo_compra, id_iva, iva)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s,  %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($rowCont['nro_rpto_desp'], "int"),
						0,//pendiente
						1,//estatus
						valTpDato($rowArt['precio_unitario'], "real_inglesa"),
                                                valTpDato($rowArt['id_articulo_costo'], "int"),
                                                valTpDato($rowArt['id_articulo_almacen_costo'], "int"),
						valTpDato($costoArt, "real_inglesa"),
						valTpDato($rowArt['id_iva'], "int"),
						valTpDato($rowArt['iva'], "real_inglesa"));
					mysql_query("SET NAMES 'utf8';");
					$Result1DetFact = mysql_query($insertSQLFacturaDetalle);
					if (!$Result1DetFact) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					$idDocumentoDetalleFactura = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					$insertSQLDetFact = sprintf("INSERT INTO sa_det_fact_articulo (idFactura, id_articulo, id_paquete, cantidad, id_precio, precio_unitario, id_articulo_costo, id_articulo_almacen_costo, costo, id_iva, iva, aprobado, tiempo_asignacion,  tiempo_aprobacion, id_empleado_aprobacion, estado_articulo, id_factura_detalle)
					VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, '%s', '%s', %s, %s, %s);",
						valTpDato($idFactura, "int"),
						valTpDato($idArticulo, "int"),
						valTpDato($rowArt['id_paquete'], "int"),
						valTpDato($rowCont['nro_rpto_desp'], "int"),
						valTpDato($rowArt['id_precio'], "int"),
						valTpDato($rowArt['precio_unitario'], "real_inglesa"),
                                                valTpDato($rowArt['id_articulo_costo'], "int"),
                                                valTpDato($rowArt['id_articulo_almacen_costo'], "int"),
						valTpDato($costoArt, "real_inglesa"),						
						valTpDato($rowArt['id_iva'], "int"),
						valTpDato($rowArt['iva'], "real_inglesa"),
						$rowArt['aprobado'], 
						$rowArt['tiempo_asignacion'],
						$rowArt['tiempo_aprobacion'],
						$rowArt['id_empleado_aprobacion'],
						$rowArt['edo_articulo'],
						$idDocumentoDetalleFactura);
					mysql_query("SET NAMES 'utf8';");
					$rsDetFact = mysql_query($insertSQLDetFact);
					if (!$rsDetFact) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					$idDetalleArtFact = mysql_insert_id();
                                        mysql_query("SET NAMES 'latin1';");
                                        
                                        //GUARDANDO MULTIPLES IVAS DE LOS REPUESTOS
                                        $query = sprintf("SELECT base_imponible, subtotal_iva, id_iva, iva, lujo 
                                                        FROM sa_det_orden_articulo_iva 
                                                        WHERE id_det_orden_articulo = %s",
                                                $rowArt['id_det_orden_articulo']);
                                        $rs3 = mysql_query($query);
                                        if(!$rs3) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
                                        
                                        while($rowArtIvaOrden = mysql_fetch_assoc($rs3)){
                                            $query = sprintf("INSERT INTO sa_det_fact_articulo_iva (id_det_fact_articulo, base_imponible, subtotal_iva, id_iva, iva, lujo)
                                                                    VALUES(%s, %s, %s, %s, %s, %s)",
                                            $idDetalleArtFact,
                                            $rowArtIvaOrden["base_imponible"],
                                            $rowArtIvaOrden["subtotal_iva"],
                                            $rowArtIvaOrden["id_iva"],
                                            $rowArtIvaOrden["iva"],
                                            valTpDato($rowArtIvaOrden["lujo"], "int")
                                                    );
                                            $rs4 = mysql_query($query);
                                            if(!$rs4) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query); }
                                        }
                                        
                                        
					// BUSCA LOS ARTICULOS DESPACHADOS POR UBICACION
					$queryContCas = sprintf("SELECT 
						id_casilla,
						COUNT(*) AS nro_rpto_desp_cas
					FROM sa_det_solicitud_repuestos
					WHERE id_det_orden_articulo = %s
						AND id_estado_solicitud = 3
					GROUP BY id_casilla", 
						valTpDato($rowArt['id_det_orden_articulo'], "int"));
					$rsContCas = mysql_query($queryContCas);
					if (!$rsContCas) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					while($rowContCas = mysql_fetch_assoc($rsContCas)) {
						$idCasilla = $rowContCas['id_casilla'];
						$cantidadDespachada = $rowContCas['nro_rpto_desp_cas'];
						
						// REGISTRA EL MOVIMIENTO DEL ARTICULO 
						$insertSQLKardex = sprintf("INSERT INTO iv_kardex (id_documento, id_modulo, id_articulo, id_casilla, tipo_movimiento, cantidad, precio, id_articulo_costo, id_articulo_almacen_costo, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, id_clave_movimiento, estado, fecha_movimiento, hora_movimiento)
						VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())",
							valTpDato($idFactura, "int"),
							valTpDato("1", "int"), // 0 = Repuestos, 1 = Servicios, 2 = Vehiculos, 3 = Administracion
							valTpDato($idArticulo, "int"),
							valTpDato($idCasilla, "int"),
							valTpDato($rowClaveMov['tipo'], "int"), // 1 = Compra, 2 = Entrada, 3 = Venta, 4 = Salida
							valTpDato($cantidadDespachada, "int"),
							valTpDato($rowArt['precio_unitario'], "real_inglesa"),
							valTpDato($rowArt['id_articulo_costo'], "int"),	
							valTpDato($rowArt['id_articulo_almacen_costo'], "int"),	
							valTpDato($costoArt, "real_inglesa"),	
							valTpDato(0, "int"),//costo_cargo
							valTpDato($rowArt['porcentaje_descuento'], "text"),
							valTpDato($rowArt['subtotal_descuento'], "real_inglesa"),
							valTpDato($idClaveMovimiento, "int"),
							valTpDato(1, "int")); // 0 = Entrada, 1 = Salida
						mysql_query("SET NAMES 'utf8';");
						$Result1Kardex = mysql_query($insertSQLKardex);
						if (!$Result1Kardex) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
						$idKardex = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
						
						
						// INSERTA EL DETALLE DEL MOVIMIENTO
						$insertMovimientoDetalleSQL = sprintf("INSERT INTO iv_movimiento_detalle (id_movimiento, id_articulo, id_kardex, cantidad, precio, id_articulo_costo, id_articulo_almacen_costo, costo, costo_cargo, porcentaje_descuento, subtotal_descuento, correlativo1, correlativo2, tipo_costo, llave_costo_identificado, promocion, id_moneda_costo, id_moneda_costo_cambio)
						VALUE (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
							valTpDato($idMovimiento, "int"),
							valTpDato($idArticulo, "int"),
							valTpDato($idKardex, "int"),
							valTpDato($cantidadDespachada, "int"),
							valTpDato($rowArt['precio_unitario'], "real_inglesa"),
							valTpDato($rowArt['id_articulo_costo'], "int"),
							valTpDato($rowArt['id_articulo_almacen_costo'], "int"),
							valTpDato($costoArt, "real_inglesa"),
							valTpDato(0, "int"), //costo cargo
							valTpDato($rowArt['porcentaje_descuento'], "text"),
							valTpDato($rowArt['subtotal_descuento'], "real_inglesa"),
							valTpDato("", "int"),
							valTpDato("", "int"),
							valTpDato(0, "int"),
							valTpDato("", "text"),
							valTpDato(0, "boolean"),
							valTpDato("", "int"),
							valTpDato("", "int"));
						$ResultMovimientoDetalle1 = mysql_query($insertMovimientoDetalleSQL);
						if (!$ResultMovimientoDetalle1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
					}
					
					// BUSCA LOS ARTICULOS EN LAS SOLICITUDES DONDE FUERON DESPACHADAS
					$queryEdoDetSolicitudDespachado = sprintf("SELECT 
						id_det_solicitud_repuesto,
						id_solicitud
					FROM sa_det_solicitud_repuestos
					WHERE id_det_orden_articulo = %s
						AND id_estado_solicitud = 3;", 
						valTpDato($rowArt['id_det_orden_articulo'], "int"));
					$rsEdoDetSolicitudDespachado = mysql_query($queryEdoDetSolicitudDespachado);
					if (!$rsEdoDetSolicitudDespachado) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					while ($rowEdoDetSolicitudDespachado = mysql_fetch_assoc($rsEdoDetSolicitudDespachado)) {
						// ACTUALIZA EL ESTADO DEL ARTICULO EN EL DETALLE DE LA SOLICITUD COMO FACTURADO
						$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos SET
							id_estado_solicitud = 5
						WHERE id_det_solicitud_repuesto = %s;",
							$rowEdoDetSolicitudDespachado['id_det_solicitud_repuesto']);
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						// ACTUALIZA EL ESTADO DE LA SOLICITUD COMO FACTURADO
						$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
							estado_solicitud = 5
						WHERE id_solicitud = %s;",
							 valTpDato($rowEdoDetSolicitudDespachado['id_solicitud'], "int"));
						mysql_query("SET NAMES 'utf8';");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
					}
					
										
					// ACTUALIZA EL ESTADO DEL DETALLE DE LA ORDEN COMO FACTURADO
					$query = sprintf("UPDATE sa_det_orden_articulo SET
						estado_articulo = 6
					WHERE id_det_orden_articulo = %s",
						valTpDato($rowArt['id_det_orden_articulo'], "int"));
					mysql_query("SET NAMES 'utf8';");
					$rs1 = mysql_query($query);
					if (!$rs1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
					mysql_query("SET NAMES 'latin1';");
				}
				
				// BUSCA LOS ARTICULOS DE LA ORDEN POR UBICACION
				$queryContCas = sprintf("SELECT 
					id_casilla,
					COUNT(*) AS nro_rpto_desp_cas
				FROM sa_det_solicitud_repuestos
				WHERE id_det_orden_articulo = %s
				GROUP BY id_casilla", 
					valTpDato($rowArt['id_det_orden_articulo'], "int"));
				$rsContCas = mysql_query($queryContCas);
				if (!$rsContCas) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				while($rowContCas = mysql_fetch_assoc($rsContCas)) {
					$idCasilla = $rowContCas['id_casilla'];
					$cantidadDespachada = $rowContCas['nro_rpto_desp_cas'];
					
					// ACTUALIZA LOS SALDOS DEL ARTICULO (SALIDAS, RESERVADAS)
					
					
					// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO - Roger
					$Result1 = actualizarMovimientoTotal($idArticulo, $idEmpresa);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarFactura($objResponse); return $objResponse->alert($Result1[1]); }
					
					// ACTUALIZA LOS SALDOS DEL ARTICULO (ENTRADAS, SALIDAS, RESERVADAS Y ESPERA)
					$Result1 = actualizarSaldos($idArticulo, $idCasilla);
					if ($Result1[0] != true && strlen($Result1[1]) > 0) { errorGuardarFactura($objResponse); return $objResponse->alert($Result1[1]); }
				
				/*
					$updateSQL = sprintf("UPDATE iv_articulos_almacen SET
						cantidad_salida = (SELECT SUM(kardex.cantidad)
											FROM iv_kardex kardex
											WHERE (kardex.tipo_movimiento = 3 OR kardex.tipo_movimiento = 4)
												AND kardex.id_articulo = iv_articulos_almacen.id_articulo
												AND kardex.id_casilla = iv_articulos_almacen.id_casilla),
						cantidad_reservada = (SELECT COUNT(det_orden_art.id_articulo) AS cantidad_reservada
											FROM (sa_det_solicitud_repuestos det_solicitud_rep
												JOIN sa_det_orden_articulo det_orden_art ON ((det_solicitud_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)))
											WHERE ((det_orden_art.id_articulo = iv_articulos_almacen.id_articulo)
												AND (det_solicitud_rep.id_casilla = iv_articulos_almacen.id_casilla)
												AND (det_solicitud_rep.id_estado_solicitud = 2 OR det_solicitud_rep.id_estado_solicitud = 3)))
					WHERE id_articulo = %s
						AND id_casilla = %s;",
						valTpDato($idArticulo, "int"),
						valTpDato($idCasilla, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
	
					// ACTUALIZA LOS MOVIMIENTOS TOTALES DEL ARTICULO (VENTAS)
					$updateSQL = sprintf("UPDATE iv_articulos_empresa SET
						cantidad_venta = (cantidad_venta + %s),
						valor_venta = (valor_venta + %s)
					WHERE id_articulo = %s
						AND id_empresa = %s;",
						valTpDato($cantidadDespachada, "double"),
						valTpDato(($cantidadDespachada * $costoArt), "double"),
						valTpDato($idArticulo, "int"),
						valTpDato($idEmpresa, "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					*/
				}
			}
		}
		//*************************************************************************************************************
		
		// INSERTA LAS MANO DE OBRA
		$insertSQLTempario = sprintf("INSERT INTO sa_det_fact_tempario (idFactura, id_paquete, id_tempario, precio, costo, costo_orden, id_modo, base_ut_precio, operador, aprobado, ut, tiempo_aprobacion, tiempo_asignacion, tiempo_inicio, tiempo_fin, id_mecanico, id_empleado_aprobacion, origen_tempario, estado_tempario, precio_tempario_tipo_orden) 
		SELECT 
			%s,
			sa_det_orden_tempario.id_paquete,
			sa_det_orden_tempario.id_tempario,
			sa_det_orden_tempario.precio,
			sa_det_orden_tempario.costo,
			sa_det_orden_tempario.costo_orden,
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
			sa_det_orden_tempario.precio_tempario_tipo_orden
		FROM sa_orden
			INNER JOIN sa_det_orden_tempario ON (sa_orden.id_orden = sa_det_orden_tempario.id_orden)
		WHERE sa_orden.id_empresa = %s
			AND sa_orden.id_orden= %s
			AND sa_det_orden_tempario.aprobado = 1
			AND estado_tempario <> 'DEVUELTO'",
			valTpDato($idFactura, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idOrden, "int"));
		mysql_query("SET NAMES 'utf8';");
		$rsTempario = mysql_query($insertSQLTempario);
		if (!$rsTempario) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$insertSQLTempario); }
		mysql_query("SET NAMES 'latin1';");

		$queryDet = sprintf("UPDATE sa_det_orden_tempario SET
			estado_tempario = 7
		WHERE id_det_orden_tempario IN (SELECT id_det_orden_tempario FROM
											(SELECT sa_det_orden_tempario.id_det_orden_tempario
											FROM sa_det_orden_tempario
											WHERE sa_det_orden_tempario.aprobado = 1
												AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'
												AND sa_det_orden_tempario.id_orden = %s) AS id_det);",
			valTpDato($idOrden, "int"));
		mysql_query("SET NAMES 'utf8';");	
		$rsDet = mysql_query($queryDet);
		if (!$rsDet) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryDet); }
		mysql_query("SET NAMES 'latin1';");
		
		
		$insertSQLTot = sprintf("SELECT 
			sa_det_orden_tot.id_orden_tot,
			sa_det_orden_tot.id_porcentaje_tot,
			sa_det_orden_tot.porcentaje_tot,
			sa_det_orden_tot.aprobado
		FROM sa_orden
			INNER JOIN sa_det_orden_tot ON (sa_orden.id_orden = sa_det_orden_tot.id_orden)
		WHERE sa_orden.id_empresa = %s
			AND sa_orden.id_orden= %s
			AND sa_det_orden_tot.aprobado = 1",
			valTpDato($idEmpresa, "int"),
			valTpDato($idOrden, "int"));
		$rsTot = mysql_query($insertSQLTot);
		if (!$rsTot) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$insertSQLTot); }
		while($rowTot = mysql_fetch_assoc($rsTot)) {
			// INSERTA LOS T.O.T.
			$insertSQLFactTot = sprintf("INSERT INTO sa_det_fact_tot (idFactura, id_orden_tot, id_porcentaje_tot, porcentaje_tot, aprobado)
			VALUE (%s, %s, %s, %s, %s)",
				$idFactura,
				valTpDato($rowTot['id_orden_tot'], "int"),
				valTpDato($rowTot['id_porcentaje_tot'], "int"),
				valTpDato($rowTot['porcentaje_tot'], "double"),
				valTpDato($rowTot['aprobado'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$rsInsertTot = mysql_query($insertSQLFactTot);
			if (!$rsInsertTot) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$insertSQLFactTot); }
			mysql_query("SET NAMES 'latin1';");
			
			$queryTot = sprintf("UPDATE sa_orden_tot SET
				estatus = 3
			WHERE sa_orden_tot.id_orden_tot = %s",
				valTpDato($rowTot['id_orden_tot'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$rsTotUpd = mysql_query($queryTot);
			if (!$rsTotUpd) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$queryTot); }
			mysql_query("SET NAMES 'latin1';");
		}
		
		// INSERTA LAS NOTAS
		$insertSQLNota = sprintf("INSERT INTO sa_det_fact_notas (idFactura, descripcion_nota, precio, aprobado) 
		SELECT 
			%s,
			sa_det_orden_notas.descripcion_nota,
			sa_det_orden_notas.precio,
			sa_det_orden_notas.aprobado
		FROM sa_orden
			INNER JOIN sa_det_orden_notas ON (sa_orden.id_orden = sa_det_orden_notas.id_orden)
		WHERE sa_orden.id_empresa = %s
			AND sa_orden.id_orden= %s
			AND sa_det_orden_notas.aprobado = 1",
			valTpDato($idFactura, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idOrden, "int"));
		mysql_query("SET NAMES 'utf8';");
		$rsNota = mysql_query($insertSQLNota);
		if (!$rsNota) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$insertSQLNota); }
		mysql_query("SET NAMES 'latin1';");
		
		// INSERTA LOS DESCUENTOS
		$insertSQLDcto = sprintf("INSERT INTO sa_det_fact_descuento(idFactura, id_porcentaje_descuento, porcentaje)
		SELECT 
			%s,
			sa_det_orden_descuento.id_porcentaje_descuento,
			sa_det_orden_descuento.porcentaje
		FROM sa_orden
			INNER JOIN sa_det_orden_descuento ON (sa_orden.id_orden = sa_det_orden_descuento.id_orden)
		WHERE sa_orden.id_empresa = %s
			AND sa_orden.id_orden= %s",
			valTpDato($idFactura, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idOrden, "int"));
		mysql_query("SET NAMES 'utf8';");
		$rsDcto = mysql_query($insertSQLDcto);
		if (!$rsDcto) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$insertSQLDcto); }
		mysql_query("SET NAMES 'latin1';");
		
		// REGISTRA EL ESTADO DE CUENTA
		$insertSQL = sprintf("INSERT INTO cj_cc_estadocuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
		VALUE (%s, %s, %s, %s);",
			valTpDato("FA", "text"),
			valTpDato($idFactura, "int"),
			valTpDato(date("Y-m-d"), "date"),
			valTpDato("1", "int"));
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

		
		if ($tipoPago == 0) { // 0 = Credito, 1 = Contado
			$sqlActualizarSaldo = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
				creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
															WHERE fact_vent.idCliente = cliente_emp.id_cliente
																AND fact_vent.id_empresa = cliente_emp.id_empresa
																AND fact_vent.estadoFactura IN (0,2)), 0)
													+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
															WHERE nota_cargo.idCliente = cliente_emp.id_cliente
																AND nota_cargo.id_empresa = cliente_emp.id_empresa
																AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
													- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
															WHERE anticip.idCliente = cliente_emp.id_cliente
																AND anticip.id_empresa = cliente_emp.id_empresa
																AND anticip.estadoAnticipo IN (1,2)
																AND anticip.estatus = 1), 0)
													- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
															WHERE nota_cred.idCliente = cliente_emp.id_cliente
																AND nota_cred.id_empresa = cliente_emp.id_empresa
																AND nota_cred.estadoNotaCredito IN (1,2)), 0)
													+ IFNULL((SELECT
																SUM(IFNULL(ped_vent.subtotal, 0)
																	- IFNULL(ped_vent.subtotal_descuento, 0)
																	+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																			WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
																	+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																			WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
															FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_cliente = cliente_emp.id_cliente
																AND ped_vent.id_empresa = cliente_emp.id_empresa
																AND ped_vent.estatus_pedido_venta IN (2)), 0)),
				creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
											WHERE fact_vent.idCliente = cliente_emp.id_cliente
												AND fact_vent.id_empresa = cliente_emp.id_empresa
												AND fact_vent.estadoFactura IN (0,2)), 0)
									+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
											WHERE nota_cargo.idCliente = cliente_emp.id_cliente
												AND nota_cargo.id_empresa = cliente_emp.id_empresa
												AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
									- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
											WHERE anticip.idCliente = cliente_emp.id_cliente
												AND anticip.id_empresa = cliente_emp.id_empresa
												AND anticip.estadoAnticipo IN (1,2)
												AND anticip.estatus = 1), 0)
									- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
											WHERE nota_cred.idCliente = cliente_emp.id_cliente
												AND nota_cred.id_empresa = cliente_emp.id_empresa
												AND nota_cred.estadoNotaCredito IN (1,2)), 0)
									+ IFNULL((SELECT
												SUM(IFNULL(ped_vent.subtotal, 0)
													- IFNULL(ped_vent.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
															WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
													+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
															WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
											FROM iv_pedido_venta ped_vent
											WHERE ped_vent.id_cliente = cliente_emp.id_cliente
												AND ped_vent.id_empresa = cliente_emp.id_empresa
												AND ped_vent.estatus_pedido_venta IN (2)
												AND id_empleado_aprobador IS NOT NULL), 0))
			WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
				AND cliente_emp.id_cliente = %s
				AND cliente_emp.id_empresa = %s;",
			valTpDato($frmPresupuesto['txtIdCliente'], "int"),
			valTpDato($idEmpresa, "int"));
			$rsActualizarSaldo = mysql_query($sqlActualizarSaldo);
			if (!$rsActualizarSaldo) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$sqlActualizarSaldo); }
		}
		
		// ELIMINA DE LA ORDEN LOS REPUESTOS QUE NO HAYAN SIDO DESPACHADOS
		$deleteSQL = sprintf("DELETE FROM sa_det_orden_articulo 
		WHERE (estado_articulo = 'PENDIENTE' OR estado_articulo = 1)
			AND id_orden = %s",
			valTpDato($idOrden, "int"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__.$deleteSQL); }
		
		// ASIGNA EL ESTATUS ANULADO A LOS ARTICULOS DE LAS SOLICITUDES QUE NO HAYAN SIDO DESPACHADAS
		$updateSQL = sprintf("UPDATE sa_det_solicitud_repuestos, sa_solicitud_repuestos SET
			id_estado_solicitud = 6
		WHERE sa_det_solicitud_repuestos.id_solicitud = sa_solicitud_repuestos.id_solicitud
			AND sa_solicitud_repuestos.id_orden = %s
			AND (sa_det_solicitud_repuestos.id_estado_solicitud = 1 OR sa_det_solicitud_repuestos.id_estado_solicitud = 2);",
			valTpDato($idOrden, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		// ASIGNA EL ESTATUS FACTURADO A LAS SOLICITUDES DE LA ORDEN
		$updateSQL = sprintf("UPDATE sa_solicitud_repuestos SET
			estado_solicitud = 5
		WHERE sa_solicitud_repuestos.id_orden = %s;",
			valTpDato($idOrden, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
				
		// CALCULO DE LAS COMISIONES
		$Result1 = calcular_comision_factura($idFactura);
		if ($Result1[0] != true) { return $objResponse->alert($Result1[1]); }
		
		$Result1 = actualizarNumeroControl($idEmpresa, $idClaveMovimiento);
		if ($Result1[0] != true && strlen($Result1[1]) > 0) {
			return $objResponse->alert($Result1[1]);
		}
				
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
	//INSERTA EL PAGO DEL DOCUMENTO (PAGO DE FACTURAS) SOLO SI ES DE CONTADO Y NO ES UNA DEVOLUCION
	if (in_array($idFiltroOrden, array(1,7)) && !isset($_GET['dev'])) { // 1 = CONTADO, 7 = LAT/PINTURA CONTADO
					
		// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
		$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
			INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
		WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
			valTpDato(1, "int")); // 1 = Empresa cabecera
		$rsConfig400 = mysql_query($queryConfig400);
		if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsConfig400 = mysql_num_rows($rsConfig400);
		$rowConfig400 = mysql_fetch_assoc($rsConfig400);
			
		if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
			$andEmpresa = sprintf(" AND sa_iv_apertura.id_empresa = %s",
				valTpDato($idEmpresa, "int"));
				
		} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
			$andEmpresa = '';
		}
		
		//CONSULTA FECHA DE APRTURA PARA SABER LA FECHA DE REGISTRO DE LOS DOCUMENTOS
		$sqlFechaAperturaCaja = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura WHERE idCaja = %s AND statusAperturaCaja IN(1,2) %s", //AND sa_iv_apertura.id_empresa = %s
			valTpDato(2, "int"), $andEmpresa);
		$rsFechaAperturaCaja = mysql_query($sqlFechaAperturaCaja);
		if (!$rsFechaAperturaCaja) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlFechaAperturaCaja); }
		if($rowFechaAperturaCaja = mysql_fetch_array($rsFechaAperturaCaja)){
			$fechaRegistroPago = $rowFechaAperturaCaja["fechaAperturaCaja"];
		}
		
		// NUMERACION DEL DOCUMENTO
		$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
		WHERE id_numeracion = %s
			AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																			WHERE suc.id_empresa = %s)))
		ORDER BY aplica_sucursales DESC
		LIMIT 1;",
			valTpDato(44, "int"), // 5 = Recibos de Pago
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
		$rsNumeracion = mysql_query($queryNumeracion);
		if (!$rsNumeracion) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
		
		$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
		$numeroActualPago = $rowNumeracion['numero_actual'];
		
                if($numeroActualPago == ""){ errorGuardarFactura($objResponse); return $objResponse->alert("No se ha configurado numeracion de comprobantes de pago"); }
                
		// INSERTA EL RECIBO DE PAGO
		$sqlInsertEncabezadoReciboPago = sprintf("INSERT INTO cj_encabezadorecibopago (numeroComprobante, fechaComprobante, idTipoDeDocumento, idConcepto, numero_tipo_documento, id_departamento)
		VALUES (%s, %s, %s, %s, %s, %s)",
			valTpDato($numeroActualPago, "int"),
			valTpDato($fechaRegistroPago,"date"),
			valTpDato(1, "int"),
			valTpDato(0, "int"),		
			valTpDato($idFactura, "int"),
			valTpDato(1, "int"));
		$rsInsertEncabezadoReciboPago = mysql_query($sqlInsertEncabezadoReciboPago);
		if (!$rsInsertEncabezadoReciboPago) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertEncabezadoReciboPago); }
		$idEncabezadoReciboPago = mysql_insert_id();
		
		// ACTUALIZA LA NUMERACION DEL DOCUMENTO (Recibos de Pago)
		$updateSQL = sprintf("UPDATE pg_empresa_numeracion SET numero_actual = (numero_actual + 1)
		WHERE id_empresa_numeracion = %s;",
			valTpDato($idEmpresaNumeracion, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		
		//FUNCION AGREGADA EL 26/06/2014
		// INSERTA EL ENCABEZADO DEL PAGO (PARA AGRUPAR LOS PAGOS, AFECTA CONTABILIDAD)
		$sqlInsertEncabezadoPago = sprintf("INSERT INTO cj_cc_encabezado_pago_rs (id_factura, fecha_pago)
		VALUES (%s, %s)",
			valTpDato($idFactura, "int"),
			valTpDato($fechaRegistroPago,"date"));
		$rsInsertEncabezadoPago = mysql_query($sqlInsertEncabezadoPago);
		if (!$rsInsertEncabezadoPago) { errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertEncabezadoPago); }
		$idEncabezadoPago = mysql_insert_id();
		
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		for ($cont = 0; $cont <= strlen($frmDetallePago['hddObjDetallePago']); $cont++) {
			$caracter = substr($frmDetallePago['hddObjDetallePago'], $cont, 1);
			
			if ($caracter != "|" && $caracter != "")
				$cadena .= $caracter;
			else {
				$arrayObjPago[] = $cadena;
				$cadena = "";
			}	
		}
		
		$cadena = '';
		foreach($arrayObjPago as $indicePago => $valorPago) {
			if (isset($frmListadoPagos['txtFormaPago'.$valorPago])){
				if ($frmListadoPagos['txtFormaPago'.$valorPago] == 1){//EFECTIVO
					$bancoCliente = 1;
					$bancoCompania = 1;
					$numeroCuenta = "-";
					$numeroDocumento = "-";
					$tipoCheque = "-";
					$campo = "saldoEfectivo";
					$txtMonto = $frmListadoPagos['txtMonto'.$valorPago];
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 2){//CHEQUE
					$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valorPago];
					$bancoCompania = 1;
					$numeroCuenta = $frmListadoPagos['txtNumeroCuenta'.$valorPago];
					$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "0";
					$campo = "saldoCheques";
					$txtMonto = $frmListadoPagos['txtMonto'.$valorPago];
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 3){//DEPOSITO
					$bancoCliente = '1';
					$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valorPago];
					$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valorPago]);
					$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoDepositos";
					$txtMonto = $frmListadoPagos['txtMonto'.$valorPago];
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 4){//TRANSFERENCIA BANCARIA
					$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valorPago];
					$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valorPago];
					$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valorPago]);
					$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoTransferencia";
					$txtMonto = $frmListadoPagos['txtMonto'.$valorPago];
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 5){//TARJETA DE CREDITO
					$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valorPago];
					$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valorPago];
					$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valorPago]);
					$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoTarjetaCredito";
					$txtMonto = $frmListadoPagos['txtMonto'.$valorPago];
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 6){//TARJETA DE DEBITO
					$bancoCliente = $frmListadoPagos['txtBancoCliente'.$valorPago];
					$bancoCompania = $frmListadoPagos['txtBancoCompania'.$valorPago];
					$numeroCuenta = numeroCuenta($frmListadoPagos['txtNumeroCuenta'.$valorPago]);
					$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoTarjetaDebito";
					$txtMonto = $frmListadoPagos['txtMonto'.$valorPago];
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 7){//ANTICIPO
					$bancoCliente = 1;
					$bancoCompania = 1;
					$numeroCuenta = "-";
					$numeroDocumento = $frmListadoPagos['txtIdDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoAnticipo";
					$txtMonto = 0;
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 8){//NOTA CREDITO
					$bancoCliente = 1;
					$bancoCompania = 1;
					$numeroCuenta = "-";
					$numeroDocumento = $frmListadoPagos['txtIdDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoNotaCredito";
					$txtMonto = $frmListadoPagos['txtMonto'.$valorPago];
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 9){//RETENCION
					$bancoCliente = 1;
					$bancoCompania = 1;
					$numeroCuenta = "-";
					$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoRetencion";
					$txtMonto = $frmListadoPagos['txtMonto'.$valorPago];
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 10){//RETENCION ISLR
					$bancoCliente = 1;
					$bancoCompania = 1;
					$numeroCuenta = "-";
					$numeroDocumento = $frmListadoPagos['txtNumeroDocumento'.$valorPago];
					$tipoCheque = "-";
					$campo = "saldoRetencion";
					$txtMonto = $frmListadoPagos['txtMonto'.$valorPago];
				}
				
				// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
				$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
					INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
				WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
					valTpDato(1, "int")); // 1 = Empresa cabecera
				$rsConfig400 = mysql_query($queryConfig400);
				if (!$rsConfig400) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$totalRowsConfig400 = mysql_num_rows($rsConfig400);
				$rowConfig400 = mysql_fetch_assoc($rsConfig400);
					
				if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
					$andEmpresa = sprintf(" AND id_empresa = %s",
						valTpDato($idEmpresa, "int"));
						
				} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
					$andEmpresa = '';
				}
				
				$sqlSelectDatosAperturaCaja = sprintf("SELECT saldoCaja, id, %s
				FROM sa_iv_apertura
				WHERE idCaja = 2
					AND statusAperturaCaja IN (1,2) %s",
					$campo, $andEmpresa);
				$rsSelectDatosAperturaCaja = mysql_query($sqlSelectDatosAperturaCaja);
				if (!$rsSelectDatosAperturaCaja) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectDatosAperturaCaja); }
				$rowSelectDatosAperturaCaja = mysql_fetch_array($rsSelectDatosAperturaCaja);
				
				// NO SUMA ANTICIPOS(7) EN EL SALDO DE LA CAJA, YA QUE ESTOS SE SUMAN EN LAS DEMAS FORMAS DE PAGO (EF, CH, DP, TB, TC, TD)
				$sqlUpdateDatosAperturaCaja = sprintf("UPDATE sa_iv_apertura SET
					%s = %s,
					saldoCaja = %s
				WHERE id = %s",
					$campo,
					valTpDato($rowSelectDatosAperturaCaja[$campo] + $frmListadoPagos['txtMonto'.$valorPago],"double"),
					valTpDato($rowSelectDatosAperturaCaja['saldoCaja'] + $txtMonto,"double"),
					valTpDato($rowSelectDatosAperturaCaja['id'], "int"));
				$rsUpdateDatosAperturaCaja = mysql_query($sqlUpdateDatosAperturaCaja);
				if (!$rsUpdateDatosAperturaCaja) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateDatosAperturaCaja); }
				
				// INSERTA LOS PAGOS DEL DOCUMENTO
				$sqlInsertPago = sprintf("INSERT INTO sa_iv_pagos (id_factura, fechaPago, formaPago, numeroDocumento, bancoOrigen, bancoDestino, cuentaEmpresa, montoPagado, numeroFactura, tipoCheque, tomadoEnComprobante, tomadoEnCierre, idCaja, idCierre, id_encabezado_rs)
				VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);",
					valTpDato($idFactura, "int"),
					valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)),"date"),
					valTpDato($frmListadoPagos['txtFormaPago'.$valorPago], "int"),
					valTpDato($numeroDocumento,"text"),
					valTpDato($bancoCliente, "int"),
					valTpDato($bancoCompania, "int"),
					valTpDato($numeroCuenta,"text"),
					valTpDato($frmListadoPagos['txtMonto'.$valorPago],"double"),
					valTpDato($numeroActual,"text"), //$frmDcto['txtNumeroFactura']
					valTpDato($tipoCheque,"text"),
					valTpDato(1, "int"),
					valTpDato(0, "int"),
					valTpDato(2, "int"),
					valTpDato(0, "int"),
					valTpDato($idEncabezadoPago, "int"));
				$rsInsertPago = mysql_query($sqlInsertPago);
				if (!$rsInsertPago) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertPago); }
				$idPago = mysql_insert_id();
			
				$arrayDetIdDctoContabilidadPago[0] = $idPago;
				$arrayDetIdDctoContabilidadPago[1] = $idFactura;
				$arrayIdDctoContabilidadPago[] = $arrayDetIdDctoContabilidadPago;
				
				if ($frmListadoPagos['txtFormaPago'.$valorPago] == 3){ //DEPOSITO
					$arrayPosiciones = explode("|",$frmDetallePago['hddObjDetalleDeposito']);
					$arrayFormaPago = explode("|",$frmDetallePago['hddObjDetalleDepositoFormaPago']);
					$arrayBanco = explode("|",$frmDetallePago['hddObjDetalleDepositoBanco']);
					$arrayNroCuenta = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCuenta']);
					$arrayNroCheque = explode("|",$frmDetallePago['hddObjDetalleDepositoNroCheque']);
					$arrayMonto = explode("|",$frmDetallePago['hddObjDetalleDepositoMonto']);
					
					foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
						if ($valorDeposito == $valorPago){
							if ($arrayFormaPago[$indiceDeposito] == 1){
								$bancoDetalleDeposito = "";
								$nroCuentaDetalleDeposito = "";
								$nroChequeDetalleDeposito = "";
							} else {
								$bancoDetalleDeposito = $arrayBanco[$indiceDeposito];
								$nroCuentaDetalleDeposito = $arrayNroCuenta[$indiceDeposito];
								$nroChequeDetalleDeposito = $arrayNroCheque[$indiceDeposito];
							}
							
							$sqlInsertDetalleDeposito = sprintf("INSERT INTO an_det_pagos_deposito_factura (idPago, fecha_deposito, idFormaPago, idBanco, numero_cuenta, numero_cheque, monto, id_tipo_documento, idCaja)
							VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
								valTpDato($idPago, "int"),
								valTpDato(date("Y-m-d",strtotime($frmListadoPagos['txtFechaDeposito'.$valorPago])),"date"),
								valTpDato($arrayFormaPago[$indiceDeposito], "int"),
								valTpDato($bancoDetalleDeposito, "int"),
								valTpDato($nroCuentaDetalleDeposito,"text"),
								valTpDato($nroChequeDetalleDeposito,"text"),
								valTpDato($arrayMonto[$indiceDeposito],"double"),
								valTpDato(1, "int"),
								valTpDato(2, "int"));
							$rsInsertDetalleDeposito = mysql_query($sqlInsertDetalleDeposito);
							if (!$rsInsertDetalleDeposito) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertDetalleDeposito); }
						}
					}
				} else if($frmListadoPagos['txtFormaPago'.$valorPago] == 5 || $frmListadoPagos['txtFormaPago'.$valorPago] == 6){ //T. CREDITO Y DEBITO
					$sqlSelectRetencionPunto = sprintf("SELECT id_retencion_punto FROM te_retencion_punto
					WHERE id_cuenta = %s
						AND id_tipo_tarjeta = %s",
						valTpDato($frmListadoPagos['txtNumeroCuenta'.$valorPago], "int"),
						valTpDato($frmListadoPagos['txtTipoTarjeta'.$valorPago], "int"));
					$rsSelectRetencionPunto = mysql_query($sqlSelectRetencionPunto);
					if (!$rsSelectRetencionPunto) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectRetencionPunto); }
					$rowSelectRetencionPunto = mysql_fetch_array($rsSelectRetencionPunto);
					
					$sqlInsertRetencionPuntoPago = sprintf("INSERT INTO cj_cc_retencion_punto_pago (id_caja, id_pago, id_tipo_documento, id_retencion_punto)
					VALUES (%s, %s, %s, %s)",
						valTpDato(2, "int"),
						valTpDato($idPago, "int"),
						valTpDato(1, "int"),
						valTpDato($rowSelectRetencionPunto['id_retencion_punto'], "int"));
					$rsInsertRetencionPuntoPago = mysql_query($sqlInsertRetencionPuntoPago);
					if (!$rsInsertRetencionPuntoPago) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertRetencionPuntoPago); }
					
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 7){ //ANTICIPO
					$sqlSelectAnticipo = sprintf("SELECT * FROM cj_cc_anticipo WHERE idAnticipo = %s",
						$frmListadoPagos['txtIdDocumento'.$valorPago]);
					$rsSelectAnticipo = mysql_query($sqlSelectAnticipo);
					if (!$rsSelectAnticipo) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectAnticipo); }
					$rowSelectAnticipo = mysql_fetch_array($rsSelectAnticipo);
					
					$totalAnticipo = $rowSelectAnticipo['saldoAnticipo'] - $frmListadoPagos['txtMonto'.$valorPago];
					$estatusAnticipo = ($totalAnticipo == 0) ? 3 : 2;
					
					$sqlUpdateAnticipo = sprintf("UPDATE cj_cc_anticipo SET
						saldoAnticipo = %s,
						estadoAnticipo = %s
					WHERE idAnticipo = %s",
						valTpDato($totalAnticipo,"double"),
						valTpDato($estatusAnticipo, "int"),
						$frmListadoPagos['txtIdDocumento'.$valorPago]);
					$rsUpdateAnticipo = mysql_query($sqlUpdateAnticipo);
					if (!$rsUpdateAnticipo) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateAnticipo); }
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 8) { //NOTA CREDITO
					$sqlSelectNotaCredito = sprintf("SELECT * FROM cj_cc_notacredito WHERE idNotaCredito = %s",
						$frmListadoPagos['txtIdDocumento'.$valorPago]);
					$rsSelectNotaCredito = mysql_query($sqlSelectNotaCredito);
					if (!$rsSelectNotaCredito) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectNotaCredito); }
					$rowSelectNotaCredito = mysql_fetch_array($rsSelectNotaCredito);
					
					$totalNotaCredito = $rowSelectNotaCredito['saldoNotaCredito'] - $frmListadoPagos['txtMonto'.$valorPago];
					$estatusNotaCredito = ($totalNotaCredito == 0) ? 3 : 2;
					
					$sqlUpdateNotaCredito = sprintf("UPDATE cj_cc_notacredito SET
						saldoNotaCredito = %s,
						estadoNotaCredito = %s
					WHERE idNotaCredito = %s",
						valTpDato($totalNotaCredito,"double"),
						valTpDato($estatusNotaCredito, "int"),
						$frmListadoPagos['txtIdDocumento'.$valorPago]);
					$rsUpdateNotaCredito = mysql_query($sqlUpdateNotaCredito);
					if (!$rsUpdateNotaCredito) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateNotaCredito); }
				} else if ($frmListadoPagos['txtFormaPago'.$valorPago] == 9){ //RETENCION
					$sqlSelectFactura = sprintf("SELECT * FROM cj_cc_encabezadofactura
					WHERE idFactura = %s",
						valTpDato($idFactura, "int"));
					$rsSelectFactura = mysql_query($sqlSelectFactura);
					if (!$rsSelectFactura) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlSelectFactura); }
					$rowSelectFactura = mysql_fetch_array($rsSelectFactura);
					
					$porcentajeAlicuota = $rowSelectFactura['porcentajeIvaFactura'] + $rowSelectFactura['porcentajeIvaDeLujoFactura'];
					$impuestoIva = $rowSelectFactura['calculoIvaFactura'] + $rowSelectFactura['calculoIvaDeLujoFactura'];
					$porcentajeRetenido = ($impuestoIva > 0) ? $frmListadoPagos['txtMonto'.$valor] * 100 / $impuestoIva : 0;
					
					$sqlInsertRetencionCabecera = sprintf("INSERT INTO cj_cc_retencioncabezera (numeroComprobante, fechaComprobante, anoPeriodoFiscal, mesPeriodoFiscal, idCliente, idRegistrosUnidadesFisicas)
					VALUES (%s, %s, %s, %s, %s, %s)",
						valTpDato($frmListadoPagos['txtNumeroDocumento'.$valorPago],"text"),
						valTpDato(date("Y-m-d",strtotime($fechaRegistroPago)),"date"),
						valTpDato(date("Y",strtotime($fechaRegistroPago)),"text"),
						valTpDato(date("m",strtotime($fechaRegistroPago)),"text"),
						valTpDato($frmPresupuesto['txtIdCliente'], "int"),
						valTpDato(0, "int"));
					$rsInsertRetencionCabecera = mysql_query($sqlInsertRetencionCabecera);
					if (!$rsInsertRetencionCabecera) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertRetencionCabecera); }
					$idRetencionCabecera = mysql_insert_id();

					
					$sqlInsertRetencionDetalle = sprintf("INSERT INTO cj_cc_retenciondetalle (idRetencionCabezera, fechaFactura, idFactura, numeroControlFactura, numeroNotaDebito, numeroNotaCredito, tipoDeTransaccion, numeroFacturaAfectada, totalCompraIncluyendoIva, comprasSinIva, baseImponible, porcentajeAlicuota, impuestoIva, IvaRetenido, porcentajeRetencion)
					VALUES (%s, %s, %s, %s, '', '', '', '', %s, %s, %s, %s, %s, %s, %s)",
						valTpDato($idRetencionCabecera, "int"),
						valTpDato($rowSelectFactura['fechaRegistroFactura'],"date"),
						valTpDato($rowSelectFactura['idFactura'], "int"),
						valTpDato($rowSelectFactura['numeroControl'],"text"),
						valTpDato($rowSelectFactura['montoTotalFactura'],"double"),
						valTpDato($rowSelectFactura['subtotalFactura'],"double"),
						valTpDato($rowSelectFactura['baseImponible'],"double"),
						valTpDato($porcentajeAlicuota,"double"),
						valTpDato($impuestoIva,"double"),
						valTpDato($frmListadoPagos['txtMonto'.$valorPago],"double"),
						valTpDato($porcentajeRetenido, "int"));
					$rsInsertRetencionDetalle = mysql_query($sqlInsertRetencionDetalle);
					if (!$rsInsertRetencionDetalle) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertRetencionDetalle); }
				}
				
				$sqlInsertDetalleReciboPago = sprintf("INSERT INTO cj_detallerecibopago (idComprobantePagoFactura, idPago)
				VALUES (%s, %s)",
					valTpDato($idEncabezadoReciboPago, "int"),
					valTpDato($idPago, "int"));
				$rsInsertDetalleReciboPago = mysql_query($sqlInsertDetalleReciboPago);
				if (!$rsInsertDetalleReciboPago) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlInsertDetalleReciboPago); }
			}
		}
		
		//ACTUALIZA SALDOS Y ESTATUS DEL DOCUMENTO
		//0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado
		if ($frmListadoPagos['txtMontoPorPagar'] == 0) {
			$sqlUpdateFactura = sprintf("UPDATE cj_cc_encabezadofactura SET
				saldoFactura = 0,
				estadoFactura = 1
			WHERE idFactura = %s;",
				valTpDato($idFactura, "int"));
		} else {
			$montoPorPagar = str_replace(",","",$frmListadoPagos['txtMontoPorPagar']);
			$sqlUpdateFactura = sprintf("UPDATE cj_cc_encabezadofactura SET
				saldoFactura = %s,
				estadoFactura = 2
			WHERE idFactura = %s;",
				valTpDato($montoPorPagar,"double"),
				valTpDato($idFactura, "int"));
		}
		$rsUpdateFactura = mysql_query($sqlUpdateFactura);
		if (!$rsUpdateFactura) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlUpdateFactura);	}
		
		// ACTUALIZA EL ESTATUS DE LA FACTURA (0 = No Cancelado, 1 = Cancelado, 2 = Parcialmente Cancelado)
		$updateSQL = sprintf("UPDATE cj_cc_encabezadofactura cxc_fact SET
			estadoFactura = (CASE
								WHEN (ROUND(saldoFactura, 2) <= 0) THEN
									1
								WHEN (ROUND(saldoFactura, 2) > 0 AND ROUND(saldoFactura, 2) < ROUND(montoTotalFactura, 2)) THEN
									2
								ELSE
									0
							END),
			fecha_pagada = (CASE
								WHEN (ROUND(cxc_fact.saldoFactura, 2) <= 0 AND cxc_fact.fecha_pagada IS NULL) THEN
									(CASE
										WHEN (cxc_fact.idDepartamentoOrigenFactura IN (2,4,5)) THEN
											CONCAT(
												(SELECT MAX(cxc_pago.fechaPago) FROM an_pagos cxc_pago
												WHERE cxc_pago.id_factura = cxc_fact.idFactura),
												' ',
												DATE_FORMAT(NOW(),'%s'))
										WHEN (cxc_fact.idDepartamentoOrigenFactura IN (0,1,3)) THEN
											CONCAT(
												(SELECT MAX(cxc_pago.fechaPago) FROM sa_iv_pagos cxc_pago
												WHERE cxc_pago.id_factura = cxc_fact.idFactura),
												' ',
												DATE_FORMAT(NOW(),'%s'))
									END)
								WHEN (ROUND(cxc_fact.saldoFactura, 2) <= 0 AND cxc_fact.fecha_pagada IS NOT NULL) THEN
									cxc_fact.fecha_pagada
							END)
		WHERE idFactura = %s;",
			valTpDato("%H:%i:%s", "campo"),
			valTpDato("%H:%i:%s", "campo"),
			valTpDato($idFactura, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }

		// ACTUALIZA EL CREDITO DISPONIBLE
		$queryFactura = sprintf("SELECT idCliente, id_empresa FROM cj_cc_encabezadofactura
		WHERE idFactura = %s",
			valTpDato($idFactura, "int"));
		$rsFactura = mysql_query($queryFactura);
		if (!$rsFactura) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryFactura); }
		$rowFactura = mysql_fetch_array($rsFactura);
		
		// ACTUALIZA EL CREDITO DISPONIBLE
		$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
				creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
															WHERE fact_vent.idCliente = cliente_emp.id_cliente
																AND fact_vent.id_empresa = cliente_emp.id_empresa
																AND fact_vent.estadoFactura IN (0,2)), 0)
													+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
															WHERE nota_cargo.idCliente = cliente_emp.id_cliente
																AND nota_cargo.id_empresa = cliente_emp.id_empresa
																AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
													- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
															WHERE anticip.idCliente = cliente_emp.id_cliente
																AND anticip.id_empresa = cliente_emp.id_empresa
																AND anticip.estadoAnticipo IN (1,2)
																AND anticip.estatus = 1), 0)
													- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
															WHERE nota_cred.idCliente = cliente_emp.id_cliente
																AND nota_cred.id_empresa = cliente_emp.id_empresa
																AND nota_cred.estadoNotaCredito IN (1,2)), 0)
													+ IFNULL((SELECT
																SUM(IFNULL(ped_vent.subtotal, 0)
																	- IFNULL(ped_vent.subtotal_descuento, 0)
																	+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																			WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
																	+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																			WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
															FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_cliente = cliente_emp.id_cliente
																AND ped_vent.id_empresa = cliente_emp.id_empresa
																AND ped_vent.estatus_pedido_venta IN (2)), 0)),
				creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
											WHERE fact_vent.idCliente = cliente_emp.id_cliente
												AND fact_vent.id_empresa = cliente_emp.id_empresa
												AND fact_vent.estadoFactura IN (0,2)), 0)
									+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
											WHERE nota_cargo.idCliente = cliente_emp.id_cliente
												AND nota_cargo.id_empresa = cliente_emp.id_empresa
												AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
									- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
											WHERE anticip.idCliente = cliente_emp.id_cliente
												AND anticip.id_empresa = cliente_emp.id_empresa
												AND anticip.estadoAnticipo IN (1,2)
												AND anticip.estatus = 1), 0)
									- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
											WHERE nota_cred.idCliente = cliente_emp.id_cliente
												AND nota_cred.id_empresa = cliente_emp.id_empresa
												AND nota_cred.estadoNotaCredito IN (1,2)), 0)
									+ IFNULL((SELECT
												SUM(IFNULL(ped_vent.subtotal, 0)
													- IFNULL(ped_vent.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
															WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
													+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
															WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
											FROM iv_pedido_venta ped_vent
											WHERE ped_vent.id_cliente = cliente_emp.id_cliente
												AND ped_vent.id_empresa = cliente_emp.id_empresa
												AND ped_vent.estatus_pedido_venta IN (2)
												AND id_empleado_aprobador IS NOT NULL), 0))
			WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
				AND cliente_emp.id_cliente = %s
				AND cliente_emp.id_empresa = %s;",
			valTpDato($rowFactura['idCliente'], "int"),
			valTpDato($rowFactura['id_empresa'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { errorGuardarFactura($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	}
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//--//
		
		mysql_query("COMMIT;");
		
		//CONTABILIZA EL DOCUMENTO
		//MODIFICADO ERNESTO
		if (function_exists("generarVentasSe")) { generarVentasSe($idFactura,"",""); }
		//MODIFICADO ERNESTO
		
		//VERIFICA, SI EL DOCUMENTO ES DE CONTADO CONTABILIZA, YA QUE EL PAGO SE REALIZA AL MOMENTO
		/*if ($tipoPago == 1) { // 0 = Credito, 1 = Contado
			//CONTABILIZA PAGOS DEL DOCUMENTO
			if (isset($arrayIdDctoContabilidadPago)) {
				foreach ($arrayIdDctoContabilidadPago as $indice => $valor) {			
					// MODIFICADO ERNESTO
						$idPago = $arrayIdDctoContabilidadPago[$indice][0];
							if (function_exists("generarCajasEntradaRe")) { generarCajasEntradaRe($idPago,"",""); }
					// MODIFICADO ERNESTO
				}
			}
		}*/	
		
		errorGuardarFactura($objResponse);
		$objResponse->alert("Factura Guardada con Exito");
		
		if ($idTipoOrden == 1) { // 0 = Credito, 1 = Contado
			$objResponse->script(sprintf("window.location.href='cjrs_factura_venta_list.php';"));
		} else { 
			$objResponse->script(sprintf("window.location.href='cjrs_factura_venta_list.php';"));	
			//$objResponse->script(sprintf("window.location.href='cjrs_facturas_por_pagar_form.php?id_factura=%s';", $idFactura));
		}
		
		$objResponse->script(sprintf("verVentana('../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s', 960, 550);",
			$idFactura));
						
		if ($idPago > 0) {
			$objResponse->script(sprintf("verVentana('reportes/cjrs_comprobante_pago_factura_pdf.php?valBusq=%s|%s|%s',960,550)", $idEmpresa, $idFactura,$numeroActualPago));
		}
	}
	
	return $objResponse;
}

//VALIDA QUE LA CAJA ESTE ABIERTA, LO USA FACTURACION
function validarAperturaCaja(){
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	$fecha = date("Y-m-d");
	
	//VERIFICA SI LA CAJA TIENE CIERRE - Verifica alguna caja abierta con fecha diferente a la actual.
	//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
	$queryCierreCaja = sprintf("SELECT fechaAperturaCaja FROM sa_iv_apertura WHERE statusAperturaCaja <> 0 AND fechaAperturaCaja NOT LIKE %s AND id_empresa = %s",
		valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
		valTpDato($idEmpresa, "int"));
	$rsCierreCaja = mysql_query($queryCierreCaja);
	if (!$rsCierreCaja) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$queryCierreCaja);
	
	if (mysql_num_rows($rsCierreCaja) > 0){
		$rowCierreCaja = mysql_fetch_array($rsCierreCaja);
		$fechaUltimaApertura = date("d-m-Y",strtotime($rowCierreCaja['fechaAperturaCaja']));
		$objResponse->alert("Debe cerrar la caja del dia: ".$fechaUltimaApertura.".");
		$objResponse->script("location.href='cjrs_factura_venta_list.php'");
		
	} else {
		//VERIFICA SI LA CAJA TIENE APERTURA
		//statusAperturaCaja: 0 = CERRADA ; 1 = ABIERTA ; 2 = CERRADA PARCIAL
		$queryVerificarApertura = sprintf("SELECT * FROM sa_iv_apertura WHERE fechaAperturaCaja = %s AND statusAperturaCaja <> 0 AND id_empresa = %s",
			valTpDato(date("Y-m-d", strtotime($fecha)), "date"),
			valTpDato($idEmpresa, "int"));
		$rsVerificarApertura = mysql_query($queryVerificarApertura);
		if (!$rsVerificarApertura) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL:".$queryVerificarApertura);
		
		if (mysql_num_rows($rsVerificarApertura) == 0){
			$objResponse->alert("Esta caja no tiene apertura.");
			$objResponse->script("location.href='cjrs_factura_venta_list.php'");//ARCHIVO FACTURACION
		}
	}
	return $objResponse;
}

//NUEVO O EDITAR, SE USA PARA MOSTRAR/DESACTIVAR SECCIONES BOTONES Y TODO DEPENDIENDO DEL DOCUMENTO
function validarTipoDocumento($tipoDocumento, $idDocumento, $idEmpresa, $accion, $valFormDcto){
	$objResponse = new xajaxResponse();
	
	if ($tipoDocumento == 1) {
		$objResponse->script("
		$('divFlotante2').style.display='none';
		$('divFlotante').style.display='none';
		$('tdCodigoMecanico').style.display='none';
		$('tdNombreMecanico').style.display='none';
		$('btnGuardar').disabled = '';
		$('btnCancelar').disabled = '';
		$('tdEtiqTipoDocumento').innerHTML = 'Neto Presupuesto:';
		$('lydTipoDocumento').innerHTML = 'Datos del Presupuesto:';
		$('tdIdDocumento').innerHTML = 'Id Presupuesto:';
		$('tdFechaVecDoc').style.display='';		
		$('tdPresupuestosPendientes').style.display = 'none';");
	} else {	
		//dependiendo si se muestra o no el mecanico por parametros generales coloco el display		
		$objResponse->script("
		$('divFlotante2').style.display='none';
		$('divFlotante').style.display='none';
		$('btnGuardar').disabled = '';
		$('btnCancelar').disabled = '';
		$('tdEtiqTipoDocumento').innerHTML = 'Total:';
		$('lydTipoDocumento').innerHTML = 'Datos de la Factura';
		$('tdIdDocumento').innerHTML = 'Nro. Orden:';
		$('tdFechaVecDoc').style.display='none';");
	}
			
	if ($accion==1 || $accion==3) {
		if ($accion==1) {			
			if($tipoDocumento==1)
				$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Nuevo Presupuesto';");
			if($tipoDocumento==2)
				$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Nueva Orden de Servicio';");
				
			 $objResponse->script("
				$('tdRepAprob').style.display = 'none';
				$('tdPaqAprob').style.display = 'none';
				$('tdNotaAprob').style.display = 'none';
				$('tdTempAprob').style.display = 'none';
				$('tdTotAprob').style.display = 'none';");	
		} else {
			if ($accion==3) {
				$objResponse->script("xajax_cargarDcto('".$idDocumento."', xajax.getFormValues('frmTotalPresupuesto'));	desbloquearForm();");
				
				if ($tipoDocumento==1)
					$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Editar Presupuesto de Venta';");
				if ($tipoDocumento==2) {
					if($_GET['ret'] != 5) {
						$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Editar Orden de Servicio';
							$('tblLeyendaOrden').style.display = '';");
					} else {
						$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Retrabajo Orden de Servicio';
							desbloquearForm();
							$('tblMotivoRetrabajo').style.display = '';
							$('tblLeyendaOrden').style.display = 'none';
							
							$('imgAgregarDescuento').style.display = 'none';");
					}
				}
				$objResponse->script("
				$('tdRepAprob').style.display = '';
				$('tdPaqAprob').style.display = '';
				$('tdNotaAprob').style.display = '';
				$('tdTempAprob').style.display = '';
				$('tdTotAprob').style.display = '';");	
			}
		}	

		$objResponse->script("
		$('tdInsElimPaq').style.display = ''; 
		$('tdInsElimRep').style.display = '';
		$('tdInsElimManoObra').style.display = ''; 
		$('tdInsElimNota').style.display = '';");	
	} else if($accion == 2) {				
		if ($tipoDocumento==1)
			$objResponse->script("$('tdTituloPaginaServicios').innerHTML = 'Visualizar Presupuesto de Venta';
			$('btnGuardar').disabled = true;");
			
		if ($tipoDocumento==2)
			$objResponse->script("$('tituloPaginaCajaRS').innerHTML = 'Visualizar Orden de Servicio';
				$('btnGuardar').disabled = true;");
		else
			if ($tipoDocumento==3)
				$objResponse->script("
				if($('hddDevolucionFactura').value != '') {
					if($('hddDevolucionFactura').value == 1){
						$('tituloPaginaCajaRS').innerHTML = 'Nota de Credito de Servicios';
					}
					else
						$('tdTituloPaginaServicios').innerHTML = 'Devolucion Vale Salida';
				} else {
					$('tituloPaginaCajaRS').innerHTML = 'Pago y Facturacion de Servicios';
				}
										
				$('tdNroControl').style.display = '';
				$('tdTxtNroControl').style.display = '';
				$('tdTipoMov').style.display = '';
				$('tdLstTipoClave').style.display = '';
				$('tdClave').style.display = '';
				$('tdlstClaveMovimiento').style.display = '';");
							
		if ($tipoDocumento==4)
			$objResponse->script("
			$('tdTituloPaginaServicios').innerHTML = 'Generar Presupuesto';			
			$('tdNroControl').style.display = 'none';
			$('tdTxtNroControl').style.display = 'none';
			$('tdEtiqTipoDocumento').innerHTML = 'Neto Presupuesto:';");
							
		$objResponse->script("
		xajax_cargarDcto('".$idDocumento."',
		xajax.getFormValues('frmTotalPresupuesto'));
		$('tdNotaAprob').style.display = ''; 
		$('tdRepAprob').style.display = '';
		$('tdPaqAprob').style.display = ''; 
		$('tdTempAprob').style.display = '';
		$('tdTotAprob').style.display = '';
		$('tdInsElimPaq').style.display = 'none'; 
		$('tdInsElimRep').style.display = 'none';
		$('tdInsElimManoObra').style.display = 'none'; 
		$('tdInsElimNota').style.display = 'none';
		$('tdInsElimTot').style.display = 'none';");	
	} else if($accion == 4) {
		if ($tipoDocumento == 1)
			$objResponse->script("
			$('tdTituloPaginaServicios').innerHTML = 'Aprobar Presupuesto de Venta';");
		else
			$objResponse->script("
			$('tdTituloPaginaServicios').innerHTML = 'Aprobar Orden de Servicio';");
							
		$objResponse->script("
		xajax_cargarDcto('".$idDocumento."', 
		xajax.getFormValues('frmTotalPresupuesto'));
		desbloquearForm();
		$('tdInsElimPaq').style.display = 'none'; 
		$('tdInsElimRep').style.display = 'none';
		$('tdInsElimManoObra').style.display = 'none'; 
		$('tdInsElimNota').style.display = 'none';
		$('tdNotaAprob').style.display = ''; 
		$('tdRepAprob').style.display = '';
		$('tdPaqAprob').style.display = ''; 
		$('tdTempAprob').style.display = '';
		$('tdTotAprob').style.display = '';");
	}
	
	return $objResponse;
}

//LO USA CARGALSTCLAVEMOVIMIENTO() CREO EN FACTURACION
//FUNCION AGREGADA EL 17-09-2012
function buscarNumeroControl($idEmpresa, $idClaveMovimiento, $nombreContenedor){
	$objResponse = new xajaxResponse();
	
	// VERIFICA VALORES DE CONFIGURACION (Formato Nro. Control)
	$queryConfig401 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 401 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig401 = mysql_query($queryConfig401);
	if (!$rsConfig401) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig401 = mysql_num_rows($rsConfig401);
	$rowConfig401 = mysql_fetch_assoc($rsConfig401);
	
	if (!($totalRowsConfig401 > 0)) return $objResponse->alert("No existe un formato de numero de control establecido");
		
	$valor = explode("|",$rowConfig401['valor']);
	$separador = $valor[0];
	$formato = (strlen($separador) > 0) ? explode($separador,$valor[1]) : $valor[1];
	
	// NUMERACION DEL DOCUMENTO
	$queryNumeracion = sprintf("SELECT * FROM pg_empresa_numeracion
	WHERE id_numeracion = (SELECT clave_mov.id_numeracion_control FROM pg_clave_movimiento clave_mov
							WHERE clave_mov.id_clave_movimiento = %s)
		AND (id_empresa = %s OR (aplica_sucursales = 1 AND id_empresa = (SELECT suc.id_empresa_padre FROM pg_empresa suc
																		WHERE suc.id_empresa = %s)))
	ORDER BY aplica_sucursales DESC
	LIMIT 1;",
		valTpDato($idClaveMovimiento, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	$rsNumeracion = mysql_query($queryNumeracion);
	if (!$rsNumeracion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowNumeracion = mysql_fetch_assoc($rsNumeracion);
	
	if (strlen($separador) > 0 && isset($formato)) {
		foreach($formato as $indice => $valor) {
			$numeroActualFormato[] = ($indice == count($formato)-1) ? str_pad($rowNumeracion['numero_actual'],strlen($valor),"0",STR_PAD_LEFT) : str_pad(0,strlen($valor),"0",STR_PAD_LEFT);
		}
		$numeroActualFormato = implode($separador, $numeroActualFormato);
	} else {
		$numeroActualFormato = str_pad($rowNumeracion['numero_actual'],strlen($formato),"0",STR_PAD_LEFT);
	}
	
	$idEmpresaNumeracion = $rowNumeracion['id_empresa_numeracion'];
	
	$objResponse->assign($nombreContenedor,"value",$numeroActualFormato);
	
	return $objResponse;
}

//ES EL LISTADO DE SOLICITUD - ALMACEN - UBICACION AL ABRIR EL REPUESTO, EL OJO EN LA SECCION DE REPUESTOS EN LA ORDEN
function verMovimientosArticulo($pageNum = 0, $campOrd = "id_solicitud", $tpOrd = "DESC", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if (strlen($valCadBusq[0]) > 0) {
		$sqlBusq = sprintf(" WHERE det_sol_rep.id_det_orden_articulo = %s",
			valTpDato($valCadBusq[0], "int"));	
	}
	
	$query = sprintf("SELECT
		sol_rep.id_solicitud,
		vw_iv_art_emp_ubic.descripcion_almacen,
		vw_iv_art_emp_ubic.ubicacion,
		estado_sol.descripcion_estado_solicitud
	FROM sa_solicitud_repuestos sol_rep
		INNER JOIN sa_det_solicitud_repuestos det_sol_rep ON (sol_rep.id_solicitud = det_sol_rep.id_solicitud)
		INNER JOIN sa_estado_solicitud estado_sol ON (det_sol_rep.id_estado_solicitud = estado_sol.id_estado_solicitud)
		INNER JOIN sa_det_orden_articulo det_orden_art ON (det_sol_rep.id_det_orden_articulo = det_orden_art.id_det_orden_articulo)
		LEFT JOIN vw_iv_articulos_empresa_ubicacion vw_iv_art_emp_ubic ON (det_orden_art.id_articulo = vw_iv_art_emp_ubic.id_articulo)
			AND (vw_iv_art_emp_ubic.id_casilla = det_sol_rep.id_casilla) %s", $sqlBusq);
	
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
	
	
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "20%", $pageNum, "id_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Solicitud");
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "35%", $pageNum, "descripcion_almacen", $campOrd, $tpOrd, $valBusq, $maxRows, "Almac&eacute;n");
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "20%", $pageNum, "ubicacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Ubicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_verMovimientosArticulo", "25%", $pageNum, "descripcion_estado_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb.= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\">";
			$htmlTb .= "<td align=\"right\">".$row['id_solicitud']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_almacen'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['ubicacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_estado_solicitud'])."</td>";
		$htmlTb .= "</tr>";
			
		$id_solicitud = $row['id_solicitud'];
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"10\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_verMovimientosArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"10\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoEstadoMtoArt","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	
	$objResponse->assign("tdCodigoArticuloMto","innerHTML",utf8_encode(elimCaracter($valCadBusq[1], ";")));			 
	$objResponse->assign("tdFlotanteTitulo2","innerHTML",utf8_encode("Estado Solicitud Articulo"));
	$objResponse->script("
	if ($('divFlotante2').style.display == 'none') {
		$('divFlotante2').style.display = '';
		centrarDiv($('divFlotante2'));
	}");
	
	return $objResponse;
}

// FUNCIONES AGREGADAS EL 10-03-2014
function actualizarObjetosExistentes($valForm, $valFormListadoPagos, $montoEliminado){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($valForm['hddObjDetallePago']); $cont++) {
		$caracter = substr($valForm['hddObjDetallePago'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}
	}
	
	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($valFormListadoPagos['txtFormaPago'.$valor])){
			$cadena .= "|".$valor;
		}
	}
	
	$objResponse->assign("hddObjDetallePago","value",$cadena);
	
	//RECALCULA EL MONTO QUE FALTA POR PAGAR
	$montoPorPagar = str_replace(",","",$valFormListadoPagos['txtMontoPorPagar']) + str_replace(",","",$montoEliminado);
	$montoPagado = str_replace(",","",$valFormListadoPagos['txtMontoPagadoFactura']) - str_replace(",","",$montoEliminado);
	
	$objResponse->assign("txtMontoPorPagar","value",number_format($montoPorPagar,2,".",","));
	$objResponse->assign("txtMontoPagadoFactura","value",number_format($montoPagado,2,".",","));
	
	return $objResponse;
}

function actualizarObjetosExistentesDetalleDeposito($formDetalleDeposito, $montoEliminado){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($formDetalleDeposito['hddObjDetallePagoDeposito']); $cont++) {
		$caracter = substr($formDetalleDeposito['hddObjDetallePagoDeposito'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}
	}
	
	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($formDetalleDeposito['txtFormaPagoDetalleDeposito'.$valor])){
			$cadena .= "|".$valor;
		}
	}
	
	$objResponse->assign("hddObjDetallePagoDeposito","value",$cadena);
	
	//RECALCULA EL MONTO QUE FALTA POR PAGAR
	$montoPorPagar = str_replace(",","",$formDetalleDeposito['txtSaldoDepositoBancario']) + str_replace(",","",$montoEliminado);
	$montoPagado = str_replace(",","",$formDetalleDeposito['txtTotalDeposito']) - str_replace(",","",$montoEliminado);
	
	$objResponse->assign("txtSaldoDepositoBancario","value",number_format($montoPorPagar,2,".",","));
	$objResponse->assign("txtTotalDeposito","value",number_format($montoPagado,2,".",","));
	
	return $objResponse;
}

function buscarAnticipoNotaCredito($valForm, $idCliente, $tipoPago, $valFormObj, $valFormListado){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($valFormObj['hddObjDetallePago']); $cont++) {
		$caracter = substr($valFormObj['hddObjDetallePago'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}
	}
	
	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($valFormListado['txtFormaPago'.$valor])){
			if ($valFormListado['txtFormaPago'.$valor] == $tipoPago)
			$cadena .= ",".$valFormListado['txtIdDocumento'.$valor];
		}
	}
	
	if ($cadena != '')
		$cadena = substr($cadena,1,strlen($cadena));
		
	$valBusq = sprintf("%s|%s|%s|%s",
		$valForm['txtCriterioBusqCliente'],
		$idCliente,
		$tipoPago,
		$cadena);
		
	$objResponse->loadCommands(listadoAnticipoNotaCredito(0,"","",$valBusq));
	
	return $objResponse;
}

function cargarBancoCliente($idTd, $idSelect){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT idBanco, nombreBanco FROM bancos WHERE idBanco <> 1 ORDER BY nombreBanco ASC");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<select name='%s' id='%s'>",$idSelect,$idSelect);
		$html .= sprintf("<option value = ''>Seleccione");
		while ($row = mysql_fetch_array($rs)){
			$html .= sprintf("<option value = '%s'>%s",$row["idBanco"],utf8_encode($row["nombreBanco"]));
		}
	$html .= "</select>";
	
	mysql_query("COMMIT");
	
	$objResponse->assign($idTd,"innerHTML",$html);
	
	return $objResponse;
}

function cargarBancoCompania($tipoPago){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT idBanco, (SELECT nombreBanco FROM bancos WHERE bancos.idBanco = cuentas.idBanco) AS banco FROM cuentas GROUP BY cuentas.idBanco ORDER BY banco");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<select name='selBancoCompania' id='selBancoCompania' onchange='xajax_cargarCuentasCompania(this.value,".$tipoPago.");' >");
		$html .= sprintf("<option value = ''>Seleccione");
		while ($row = mysql_fetch_array($rs)){
			$html .= sprintf("<option value = '%s'>%s",$row["idBanco"],utf8_encode($row["banco"]));
		}
	$html .= "</select>";
	
	mysql_query("COMMIT");
	
	$objResponse->assign("tdBancoCompania","innerHTML",$html);
	
	return $objResponse;
}

function cargarCuentasCompania($idBanco, $tipoPago){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT idCuentas, numeroCuentaCompania FROM cuentas WHERE idBanco = %s AND estatus = 1", valTpDato($idBanco, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<div align='justify'><strong>
						<select name='selNumeroCuenta' id='selNumeroCuenta' onchange='xajax_cargarTarjetaCuenta(this.value,".$tipoPago.");'>");
	$registros = mysql_num_rows($rs);
	if ($registros > 1)
		$html .= sprintf("<option value = ''>Seleccione");
		
	while ($row = mysql_fetch_array($rs)){
		$html .= sprintf("<option value = '%s'>%s",$row["idCuentas"],utf8_encode($row["numeroCuentaCompania"]));
		if ($registros == 1)
			$objResponse->loadCommands(cargarTarjetaCuenta($row["idCuentas"],$tipoPago));
	}
	$html .= "</select></strong></div>";
	
	mysql_query("COMMIT");
	
	$objResponse->assign("tdNumeroCuentaSelect","innerHTML",$html);
	
	return $objResponse;
}

function cargarPago($frmListadoPagos, $formDetallePago, $formDetalleDeposito, $frmTotalPresupuesto){
	$objResponse = new xajaxResponse();
	
	if (str_replace(",","",$frmTotalPresupuesto['txtTotalPresupuesto']) < str_replace(",","",$formDetallePago['montoPago'])){
		errorCargarPago($objResponse);
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo de la Factura.");
	}
	
	for ($cont = 0; $cont <= strlen($formDetallePago['hddObjDetallePago']); $cont++) {
			$caracter = substr($formDetallePago['hddObjDetallePago'], $cont, 1);
			
			if ($caracter != "|" && $caracter != "")
				$cadenaDetallePago.= $caracter;
			else {
				$arrayObjDetallePago[] = $cadenaDetallePago;
				$cadenaDetallePago = "";
				$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			}
		}
		
	$sigValor = $arrayObjDetallePago[count($arrayObjDetallePago)-1] + 1;
	
	if ($formDetallePago['selTipoPago'] == 3 || $formDetallePago['selTipoPago'] == 4|| $formDetallePago['selTipoPago'] == 5|| $formDetallePago['selTipoPago'] == 6){
		$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",$formDetallePago['selNumeroCuenta']);
		$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta);
		if (!$rsBuscarNumeroCuenta){ errorCargarPago($objResponse); return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$sqlBuscarNumeroCuenta); }
		$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	}
	
	if($formDetallePago['selTipoPago'] == 1){
		$tipoPago = "Efectivo";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = "-";
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 2){
		$tipoPago = "Cheque";
		$tipoTarjetaCredito = "-";
		$bancoCliente = nombreBanco($formDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $formDetallePago['numeroCuenta'];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = $formDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = $formDetallePago['numeroCuenta'];
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 3){
		$tipoPago = "Deposito";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = $formDetallePago['txtFechaDeposito'];
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = nombreBanco($formDetallePago['selBancoCompania']);
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = $formDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $formDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 4){
		$tipoPago = "Transferencia Bancaria";
		$tipoTarjetaCredito = "-";
		$bancoCliente = nombreBanco($formDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = nombreBanco($formDetallePago['selBancoCompania']);
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = $formDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = $formDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $formDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 5){
		$tipoPago = "Tarjeta de Credito";
		$tipoTarjetaCredito = $formDetallePago['tarjeta'];
		$bancoCliente = nombreBanco($formDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = $formDetallePago['porcentajeRetencion'];
		$montoTotalRetencion = $formDetallePago['montoTotalRetencion'];
		$bancoCompania = nombreBanco($formDetallePago['selBancoCompania']);
		$porcentajeComision = $formDetallePago['porcentajeComision'];
		$montoTotalComision = $formDetallePago['montoTotalComision'];
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = $formDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = $formDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $formDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = $formDetallePago['tarjeta'];
	}
	else if($formDetallePago['selTipoPago'] == 6){
		$tipoPago = "Tarjeta de Debito";
		$tipoTarjetaCredito = "-";
		$bancoCliente = nombreBanco($formDetallePago['selBancoCliente']);
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = nombreBanco($formDetallePago['selBancoCompania']);
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = $rowBuscarNumeroCuenta['numeroCuentaCompania'];
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = $formDetallePago['selBancoCliente'];
		$bancoCompaniaOculto = $formDetallePago['selBancoCompania'];
		$numeroCuentaOculto = $formDetallePago['selNumeroCuenta'];
		$tipoTarjetaOculto = 6;
	}
	else if($formDetallePago['selTipoPago'] == 7){
		$tipoPago = "Anticipo";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = $formDetallePago['hddIdAnticipoNotaCredito'];
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 8){
		$tipoPago = "Nota de Credito";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = $formDetallePago['hddIdAnticipoNotaCredito'];
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 9){
		$tipoPago = "Retencion";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	else if($formDetallePago['selTipoPago'] == 10){
		$tipoPago = "Retencion ISLR";
		$tipoTarjetaCredito = "-";
		$bancoCliente = "-";
		$fechaDeposito = "-";
		$porcentajeRetencion = "-";
		$montoTotalRetencion = "-";
		$bancoCompania = "-";
		$porcentajeComision = "-";
		$montoTotalComision = "-";
		$numeroCuenta = "-";
		$numeroControl = $formDetallePago['numeroControl'];
		$idDocumento = "-";
		$montoPagado = str_replace(",","",$formDetallePago['montoPago']);
		
		$bancoClienteOculto = "-";
		$bancoCompaniaOculto = "-";
		$numeroCuentaOculto = "-";
		$tipoTarjetaOculto = "-";
	}
	
	$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trItmPago:%s', 'align':'left', 'class':'textoGris_11px %s', 'height':'24', 'title':'trItmPago:%s'}).adopt([
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'right'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"<button type='button' onclick='confirmarEliminarPago(%s);' title='Eliminar'><img src='../img/iconos/delete.png'/></button>".
			"<input type='hidden' id='txtFechaDeposito%s' name='txtFechaDeposito%s' readonly='readonly' value='%s' title='fechaDeposito'/>".
			"<input type='hidden' id='txtFormaPago%s' name='txtFormaPago%s' readonly='readonly' value='%s' title='txtFormaPago'/>".
			"<input type='hidden' id='txtNumeroDocumento%s' name='txtNumeroDocumento%s' readonly='readonly' value='%s' title='txtNumeroDocumento'/>".
			"<input type='hidden' id='txtIdDocumento%s' name='txtIdDocumento%s' readonly='readonly' value='%s' title='txtIdDocumento'/>".
			"<input type='hidden' id='txtBancoCompania%s' name='txtBancoCompania%s' readonly='readonly' value='%s' title='txtBanco'/>".
			"<input type='hidden' id='txtBancoCliente%s' name='txtBancoCliente%s' readonly='readonly' value='%s' title='txtBancoCliente'/>".
			"<input type='hidden' id='txtNumeroCuenta%s' name='txtNumeroCuenta%s' readonly='readonly' value='%s' title='txtNumeroCuenta'/>".
			"<input type='hidden' id='txtMonto%s' name='txtMonto%s' readonly='readonly' value='%s' title='txtMonto'/>".
			"<input type='hidden' id='txtTipoTarjeta%s' name='txtTipoTarjeta%s' value='%s' title='txtTipoTarjeta'/>\")
			]);
			elemento.injectBefore('trItmPiePago');",
			$sigValor, $clase, $sigValor,
			$tipoPago,
			$bancoCliente,
			$bancoCompania,
			$numeroCuenta,
			$numeroControl,
			number_format($montoPagado,2,'.',','),
			$sigValor, $sigValor,
			$sigValor, $sigValor, $fechaDeposito,
			$sigValor, $sigValor, $formDetallePago['selTipoPago'],
			$sigValor, $sigValor, $numeroControl,
			$sigValor, $sigValor, $idDocumento,
			$sigValor, $sigValor, $bancoCompaniaOculto,
			$sigValor, $sigValor, $bancoClienteOculto,
			$sigValor, $sigValor, $numeroCuentaOculto,
			$sigValor, $sigValor, $montoPagado,
			$sigValor, $sigValor, $tipoTarjetaOculto));
			
	if($formDetallePago['selTipoPago'] == 3){
		// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
		for ($cont = 0; $cont <= strlen($formDetalleDeposito['hddObjDetallePagoDeposito']); $cont++) {
			$caracter = substr($formDetalleDeposito['hddObjDetallePagoDeposito'], $cont, 1);
			
			if ($caracter != "|" && $caracter != "")
				$cadena .= $caracter;
			else {
				$arrayObj[] = $cadena;
				$cadena = "";
			}	
		}
		
		$cadena = '';
		$cadenaFormaPagoDeposito = '';
		$cadenaNroDocumentoDeposito = '';
		$cadenaBancoClienteDeposito = '';
		$cadenaNroCuentaDeposito = '';
		$cadenaMontoDeposito = '';
		foreach($arrayObj as $indice => $valor) {
			if (isset($formDetalleDeposito['txtFormaPagoDetalleDeposito'.$valor])){
				$cadenaPosicionDeposito .= $sigValor."|";
				$cadenaFormaPagoDeposito .= $formDetalleDeposito['txtFormaPagoDetalleDeposito'.$valor]."|";				
				$cadenaNroDocumentoDeposito .= $formDetalleDeposito['txtNumeroDocumentoDetalleDeposito'.$valor]."|";
				$cadenaBancoClienteDeposito .= $formDetalleDeposito['txtBancoClienteDetalleDeposito'.$valor]."|";
				$cadenaNroCuentaDeposito .= $formDetalleDeposito['txtNumeroCuentaDetalleDeposito'.$valor]."|";
				$cadenaMontoDeposito .= $formDetalleDeposito['txtMontoDetalleDeposito'.$valor]."|";
			}
		}
		$cadenaPosicionDeposito = $formDetallePago['hddObjDetalleDeposito'].$cadenaPosicionDeposito;
		$cadenaFormaPagoDeposito = $formDetallePago['hddObjDetalleDepositoFormaPago'].$cadenaFormaPagoDeposito;
		$cadenaBancoClienteDeposito = $formDetallePago['hddObjDetalleDepositoBanco'].$cadenaBancoClienteDeposito;
		$cadenaNroCuentaDeposito = $formDetallePago['hddObjDetalleDepositoNroCuenta'].$cadenaNroCuentaDeposito;
		$cadenaNroDocumentoDeposito = $formDetallePago['hddObjDetalleDepositoNroCheque'].$cadenaNroDocumentoDeposito;
		$cadenaMontoDeposito = $formDetallePago['hddObjDetalleDepositoMonto'].$cadenaMontoDeposito;
	}
	
	$arrayObjDetallePago[] = $sigValor;
	foreach($arrayObjDetallePago as $indiceDetallePago => $valorDetallePago) {
		$cadena = $formDetallePago['hddObjDetallePago']."|".$valorDetallePago;
	}
	
	$objResponse->script("document.forms['frmDetallePago'].reset();
							byId('btnAgregarDetDeposito').style.display = 'none';
							byId('agregar').style.display = 'none';
							byId('btnAgregarDetAnticipoNotaCredito').style.display = 'none';");
	$objResponse->script("byId('divFlotanteDep').style.display='none'; xajax_eliminarPagoDetalleDepositoForzado(xajax.getFormValues('frmDetalleDeposito'));");
	
	$objResponse->assign("hddObjDetallePago","value",$cadena);
	if($formDetallePago['selTipoPago'] == 3){
		$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosicionDeposito);
		$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPagoDeposito);
		$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBancoClienteDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuentaDeposito);
		$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroDocumentoDeposito);
		$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMontoDeposito);
	}
	
	//RECALCULA EL MONTO QUE FALTA POR PAGAR
	$montoPorPagar = str_replace(",","",$frmListadoPagos['txtMontoPorPagar']) - str_replace(",","",$formDetallePago['montoPago']);
	$montoPagado = str_replace(",","",$frmListadoPagos['txtMontoPagadoFactura']) + str_replace(",","",$formDetallePago['montoPago']);
	
	$objResponse->assign("txtMontoPorPagar","value",number_format($montoPorPagar,2,".",","));
	$objResponse->assign("txtMontoPagadoFactura","value",number_format($montoPagado,2,".",","));
	
	errorCargarPago($objResponse);
	
	return $objResponse;
}

function cargarPagoDetalleDeposito($formDetalleDeposito){
	$objResponse = new xajaxResponse();
		
	if (str_replace(",","",$formDetalleDeposito['txtMontoDeposito']) > str_replace(",","",$formDetalleDeposito['txtSaldoDepositoBancario'])){
		errorCargarPagoDetalleDeposito($objResponse);
		return $objResponse->alert("El monto a pagar no puede ser mayor que el saldo del Deposito.");
	}
	
	for ($cont = 0; $cont <= strlen($formDetalleDeposito['hddObjDetallePagoDeposito']); $cont++) {
			$caracter = substr($formDetalleDeposito['hddObjDetallePagoDeposito'], $cont, 1);
			
			if ($caracter != "|" && $caracter != "")
				$cadenaDetallePago.= $caracter;
			else {
				$arrayObjDetallePago[] = $cadenaDetallePago;
				$cadenaDetallePago = "";
				$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
			}
		}
		
	$sigValor = $arrayObjDetallePago[count($arrayObjDetallePago)-1] + 1;
	
	if($formDetalleDeposito['lstTipoPago'] == 1){
		$tipoPago = "Efectivo";
		$bancoCliente = "-";
		$numeroCuenta = "-";
		$numeroControl = "-";
		$montoPagado = str_replace(",","",$formDetalleDeposito['txtMontoDeposito']);
		$bancoClienteOculto = "-";
	}
	else if($formDetalleDeposito['lstTipoPago'] == 2){
		$tipoPago = "Cheque";
		$bancoCliente = nombreBanco($formDetalleDeposito['lstBancoDeposito']);
		$numeroCuenta = $formDetalleDeposito['txtNroCuentaDeposito'];
		$numeroControl = $formDetalleDeposito['txtNroChequeDeposito'];
		$montoPagado = str_replace(",","",$formDetalleDeposito['txtMontoDeposito']);
		$bancoClienteOculto = $formDetalleDeposito['lstBancoDeposito'];
	}			
	
	$objResponse->script(sprintf("
		var elemento = new Element('tr', {'id':'trItmDetalle:%s', 'align':'left', 'class':'textoGris_11px %s', 'height':'24', 'title':'trItmDetalle:%s'}).adopt([
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'center'}).setHTML(\"%s\"),
			new Element('td', {'align':'right'}).setHTML(\"%s\"),
			new Element('td', {'align':'center', 'id':'tdItm:%s'}).setHTML(\"<button type='button' onclick='confirmarEliminarPagoDetalleDeposito(%s);' title='Eliminar'><img src='../img/iconos/delete.png'/></button>".
			"<input type='hidden' id='txtFormaPagoDetalleDeposito%s' name='txtFormaPagoDetalleDeposito%s' readonly='readonly' value='%s'/>".
			"<input type='hidden' id='txtNumeroDocumentoDetalleDeposito%s' name='txtNumeroDocumentoDetalleDeposito%s' readonly='readonly' value='%s'/>".
			"<input type='hidden' id='txtBancoClienteDetalleDeposito%s' name='txtBancoClienteDetalleDeposito%s' readonly='readonly' value='%s'/>".
			"<input type='hidden' id='txtNumeroCuentaDetalleDeposito%s' name='txtNumeroCuentaDetalleDeposito%s' readonly='readonly' value='%s'/>".
			"<input type='hidden' id='txtMontoDetalleDeposito%s' name='txtMontoDetalleDeposito%s' readonly='readonly' value='%s'/>\")
			]);
			elemento.injectBefore('trItmPieDeposito');",
			$sigValor, $clase, $sigValor,
			$tipoPago,
			$bancoCliente,
			$numeroCuenta,
			$numeroControl,
			number_format($montoPagado,2,'.',','),
			$sigValor, $sigValor,
			$sigValor, $sigValor, $formDetalleDeposito['lstTipoPago'],
			$sigValor, $sigValor, $numeroControl,
			$sigValor, $sigValor, $bancoClienteOculto,
			$sigValor, $sigValor, $numeroCuenta,
			$sigValor, $sigValor, $montoPagado));
			
	$arrayObjDetallePago[] = $sigValor;
	foreach($arrayObjDetallePago as $indiceDetallePago => $valorDetallePago) {
		$cadena = $formDetalleDeposito['hddObjDetallePagoDeposito']."|".$valorDetallePago;
	}
	
	$objResponse->assign("txtMontoDeposito","value","");
	$objResponse->assign("txtNroCuentaDeposito","value","");
	$objResponse->assign("txtNroChequeDeposito","value","");
	
	$objResponse->assign("hddObjDetallePagoDeposito","value",$cadena);
	
	//RECALCULA EL MONTO QUE FALTA POR PAGAR
	$montoPorPagar = str_replace(",","",$formDetalleDeposito['txtSaldoDepositoBancario']) - str_replace(",","",$formDetalleDeposito['txtMontoDeposito']);
	$montoPagado = str_replace(",","",$formDetalleDeposito['txtTotalDeposito']) + str_replace(",","",$formDetalleDeposito['txtMontoDeposito']);
	
	$objResponse->assign("txtSaldoDepositoBancario","value",number_format($montoPorPagar,2,".",","));
	$objResponse->assign("txtTotalDeposito","value",number_format($montoPagado,2,".",","));
	
	errorCargarPagoDetalleDeposito($objResponse);
	
	return $objResponse;
}

function cargarPorcentajeTarjetaCredito($idCuenta, $idTarjeta){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT porcentaje_comision, porcentaje_islr FROM te_retencion_punto 
	WHERE id_cuenta = %s 
		AND id_tipo_tarjeta = %s",
		valTpDato($idCuenta,'int'),
		valTpDato($idTarjeta,'int'));
	$rsQuery = mysql_query($query);
	if (!$rsQuery) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
	$rowQuery = mysql_fetch_array($rsQuery);
	
	$objResponse->assign("porcentajeRetencion","value",$rowQuery['porcentaje_islr']);
	$objResponse->assign("porcentajeComision","value",$rowQuery['porcentaje_comision']);
	
	$objResponse->script("calcularMontoTotalTarjetaCredito();");
	
	return $objResponse;
}

function cargarSaldoDocumento($idDocumento, $formaPago){
	$objResponse = new xajaxResponse();
	
	if ($formaPago == 7) {
		$query = sprintf("SELECT saldoAnticipo AS saldoDocumento, numeroAnticipo AS numeroDocumento
		FROM cj_cc_anticipo WHERE idAnticipo = %s", $idDocumento);
		$documento = "Anticipo";
	} else {
		$query = sprintf("SELECT saldoNotaCredito AS saldoDocumento, numeracion_nota_credito AS numeroDocumento
		FROM cj_cc_notacredito WHERE idNotaCredito = %s", $idDocumento);
		$documento = "Nota de Credito";
	}
	$rsSelectDocumento = mysql_query($query);
	if (!$rsSelectDocumento) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
	$rowSelectDocumento = mysql_fetch_array($rsSelectDocumento);
	
	$objResponse->assign("hddIdDocumento","value",$idDocumento);
	$objResponse->assign("txtNroDocumento","value",$rowSelectDocumento['numeroDocumento']);
	$objResponse->assign("txtSaldoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'],2,'.',','));
	$objResponse->assign("txtMontoDocumento","value",number_format($rowSelectDocumento['saldoDocumento'],2,'.',','));
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML",$documento);
	$objResponse->script("
	if ($('divFlotante1').style.display == 'none') {
		$('divFlotante1').style.display = '';
		centrarDiv($('divFlotante1'));
		
		$('txtMontoDocumento').focus();}");
		
	return $objResponse;
}

function cargarTarjetaCuenta($idCuenta, $tipoPago){
	$objResponse = new xajaxResponse();
	
	if ($tipoPago == 5) {
		$query = sprintf("SELECT idTipoTarjetaCredito, descripcionTipoTarjetaCredito
                                    FROM tipotarjetacredito 
                                    WHERE idTipoTarjetaCredito IN (SELECT id_tipo_tarjeta
                                                                    FROM te_retencion_punto
                                                                    WHERE id_cuenta = %s AND porcentaje_islr IS NOT NULL AND id_tipo_tarjeta NOT IN(6))",$idCuenta);
		$rsQuery = mysql_query($query);
		if (!$rsQuery) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
		
		$html = "<select id='tarjeta' name='tarjeta' onchange='xajax_cargarPorcentajeTarjetaCredito(".$idCuenta.",this.value)'>
				 	<option value=''>Seleccione...</option>";
		
		while($rowQuery = mysql_fetch_array($rsQuery)){
			$html .= sprintf("<option value='%s'>%s</option>",$rowQuery['idTipoTarjetaCredito'],$rowQuery['descripcionTipoTarjetaCredito']);
		}
		$html .= "</select>";
		
		$objResponse->assign("tdTipoTarjetaCredito","innerHTML",$html);
	} else if ($tipoPago == 6) {
		$query = sprintf("SELECT porcentaje_comision FROM te_retencion_punto WHERE id_cuenta = %s AND id_tipo_tarjeta = 6",
							valTpDato($idCuenta,'int'));
		$rsQuery = mysql_query($query);
		if (!$rsQuery) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSQL: ".$query);
		
		$rowQuery = mysql_fetch_array($rsQuery);
		
		$objResponse->assign("porcentajeComision","value",$rowQuery['porcentaje_comision']);
	}
	
	return $objResponse;
}

function cargarTipoPago(){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");

	$query = sprintf("SELECT * FROM formapagos WHERE idFormaPago <> 11 ORDER BY nombreFormaPago ASC;");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<div align='justify'>
						<select name='selTipoPago' id='selTipoPago' onChange='cambiar()'>
							<option value=''>Seleccione...</option>");
	
	while ($row = mysql_fetch_array($rs)){
		$html .= sprintf("<option value = '%s'>%s",$row["idFormaPago"],$row["nombreFormaPago"]);
	}
	$html .= sprintf("</select>
						</div>");
	
	mysql_query("COMMIT");
	
	$objResponse->assign("tdselTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function cargarTipoPagoDetalleDeposito(){
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT * FROM formapagos where idFormaPago <= 2");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query);
	
	$html = sprintf("<div align='justify'>
						<select name='lstTipoPago' id='lstTipoPago' onChange='cambiarTipoPagoDetalleDeposito()'>
							<option value=''>Seleccione...</option>");
	
	while ($row = mysql_fetch_array($rs)){
		$html .= sprintf("<option value = '%s'>%s",$row["idFormaPago"],$row["nombreFormaPago"]);
	}
	$html .= sprintf("</select>
						</div>");
	
	mysql_query("COMMIT");
	
	$objResponse->assign("tdlstTipoPago","innerHTML",$html);
	
	return $objResponse;
}

function eliminarDetalleDeposito($pos, $valForm){
	$objResponse = new xajaxResponse();
	
	$arrayPosiciones = explode("|",$valForm['hddObjDetalleDeposito']);
	$arrayFormaPago = explode("|",$valForm['hddObjDetalleDepositoFormaPago']);
	$arrayBanco = explode("|",$valForm['hddObjDetalleDepositoBanco']);
	$arrayNroCuenta = explode("|",$valForm['hddObjDetalleDepositoNroCuenta']);
	$arrayNroCheque = explode("|",$valForm['hddObjDetalleDepositoNroCheque']);
	$arrayMonto = explode("|",$valForm['hddObjDetalleDepositoMonto']);
	
	$cadenaPosiciones = "";
	$cadenaFormaPago = "";
	$cadenaBanco = "";
	$cadenaNroCuenta = "";
	$cadenaNroCheque = "";
	$cadenaMonto = "";
	
	foreach($arrayPosiciones as $indiceDeposito => $valorDeposito) {
		if ($valorDeposito != $pos && $valorDeposito != ''){
			$cadenaPosiciones .= $valorDeposito."|";
			$cadenaFormaPago .= $arrayFormaPago[$indiceDeposito]."|";
			$cadenaBanco .= $arrayBanco[$indiceDeposito]."|";
			$cadenaNroCuenta .= $arrayNroCuenta[$indiceDeposito]."|";
			$cadenaNroCheque .= $arrayNroCheque[$indiceDeposito]."|";
			$cadenaMonto .= $arrayMonto[$indiceDeposito]."|";
		}
	}
	
	$objResponse->assign("hddObjDetalleDeposito","value",$cadenaPosiciones);
	$objResponse->assign("hddObjDetalleDepositoFormaPago","value",$cadenaFormaPago);
	$objResponse->assign("hddObjDetalleDepositoBanco","value",$cadenaBanco);
	$objResponse->assign("hddObjDetalleDepositoNroCuenta","value",$cadenaNroCuenta);
	$objResponse->assign("hddObjDetalleDepositoNroCheque","value",$cadenaNroCheque);
	$objResponse->assign("hddObjDetalleDepositoMonto","value",$cadenaMonto);
	
	return $objResponse;
}

function eliminarPago($frmListadoPagos, $pos){
	$objResponse = new xajaxResponse();
	
	if ($frmListadoPagos['txtFormaPago'.$pos] == 3)
		$objResponse->script("xajax_eliminarDetalleDeposito(".$pos.",xajax.getFormValues('frmDetallePago'))");
		
	$objResponse->script(sprintf("
				fila = document.getElementById('trItmPago:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$pos));
	
	$montoEliminado = $frmListadoPagos['txtMonto'.$pos];
	$objResponse->script("xajax_actualizarObjetosExistentes(xajax.getFormValues('frmDetallePago'),xajax.getFormValues('frmListadoPagos'),".$montoEliminado.")");
	
	return $objResponse;
}

function eliminarPagoDetalleDeposito($formDetalleDeposito, $pos){
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("
				fila = document.getElementById('trItmDetalle:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
			$pos));
			
	$montoEliminado = $formDetalleDeposito['txtMontoDetalleDeposito'.$pos];
	
	$objResponse->script("xajax_actualizarObjetosExistentesDetalleDeposito(xajax.getFormValues('frmDetalleDeposito'),".$montoEliminado.")");
	
	return $objResponse;
}

function eliminarPagoDetalleDepositoForzado($valForm){
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	for ($cont = 0; $cont <= strlen($valForm['hddObjDetallePagoDeposito']); $cont++) {
		$caracter = substr($valForm['hddObjDetallePagoDeposito'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	$cadena = '';
	foreach($arrayObj as $indice => $valor) {
		if (isset($valForm['txtFormaPagoDetalleDeposito'.$valor])){
			$objResponse->script(sprintf("
				fila = document.getElementById('trItmDetalle:%s');
							
				padre = fila.parentNode;
				padre.removeChild(fila);",
					$valor));
		}
	}
	$objResponse->assign("hddObjDetallePagoDeposito","value","");
	
	return $objResponse;
}

function listadoAnticipoNotaCredito($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("idCliente = %s
	AND (id_empresa = %s
		OR %s IN (SELECT suc.id_empresa FROM pg_empresa suc
			WHERE suc.id_empresa_padre = id_empresa)
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = id_empresa)
		OR (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = %s) IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
			WHERE suc.id_empresa = id_empresa))",
		valTpDato($valCadBusq[1], "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"));
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	if($valCadBusq[2] == 7) {
		$sqlBusq .= $cond.sprintf("estadoAnticipo IN (1,2)");
		
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estatus = 1"); // 0 = Anulado ; 1 = Activo
	} else {
		$sqlBusq .= $cond.sprintf("estadoNotaCredito IN (1,2)");
	}
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if($valCadBusq[2] == 7)
			$sqlBusq .= $cond.sprintf(" numeroAnticipo = %s ",
				valTpDato($valCadBusq[2], "int"));
		else
			$sqlBusq .= $cond.sprintf(" numeracion_nota_credito = %s ",
				valTpDato($valCadBusq[2], "int"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if($valCadBusq[2] == 7)
			$sqlBusq .= $cond.sprintf(" idAnticipo NOT IN (%s) ",
			$valCadBusq[3]);
		else
			$sqlBusq .= $cond.sprintf(" idNotaCredito NOT IN (%s) ",
			$valCadBusq[3]);
	}
	
	if($valCadBusq[2] == 7)
		$query = sprintf("SELECT
			idAnticipo AS idDocumento,
			saldoAnticipo AS saldoDocumento,
			numeroAnticipo AS numeroDocumento,
			fechaAnticipo AS fechaDocumento,
			observacionesAnticipo AS observacionDocumento
		FROM
			cj_cc_anticipo %s", $sqlBusq);
	else
		$query = sprintf("SELECT
			idNotaCredito AS idDocumento,
			saldoNotaCredito AS saldoDocumento,
			numeracion_nota_credito AS numeroDocumento,
			fechaNotaCredito AS fechaDocumento,
			observacionesNotaCredito AS observacionDocumento
		FROM
			cj_cc_notacredito %s", $sqlBusq);
			
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
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCredito", "20%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro. Documento"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCredito", "15%", $pageNum, "fechaDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCredito", "40%", $pageNum, "observacionDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Observaci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoAnticipoNotaCredito", "25%", $pageNum, "saldoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Saldo"));
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_cargarSaldoDocumento('".$row['idDocumento']."','".$valCadBusq[2]."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroDocumento']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fechaDocumento']))."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['observacionDocumento'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldoDocumento'],2,'.',',')."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoAnticipoNotaCredito(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_cj_rs.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoAnticipoNotaCredito(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_cj_rs.gif\"/>");
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
	
	$objResponse->assign("tdListadoAnticipoNotaCredito","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("
	$('trBuscarAnticipoNotaCredito').style.display = '';
	
	$('tblDetallePago').style.display = 'none';
	$('tblListadosAnticipoNotaCredito').style.display = '';");
	
	$objResponse->assign("tdFlotanteTituloDep","innerHTML","Listado");
	$objResponse->assign("tblListadosAnticipoNotaCredito","width","700");
	$objResponse->script("
	if ($('divFlotanteDep').style.display == 'none') {
		$('divFlotanteDep').style.display = '';
		centrarDiv($('divFlotanteDep'));
		
		document.forms['frmBuscarAnticipoNotaCredito'].reset();
		$('txtCriterioBusqAnticipoNotaCredito').focus();
		$('txtCriterioBusqAnticipoNotaCredito').select();
	}");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"actualizarNumeroControl");
$xajax->register(XAJAX_FUNCTION,"asignarValeRecepcion");
$xajax->register(XAJAX_FUNCTION,"buscarMtoArticulo");
$xajax->register(XAJAX_FUNCTION,"calcularDcto");
$xajax->register(XAJAX_FUNCTION,"calcularTotalDcto");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoOrden");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"contarItemsDcto");
$xajax->register(XAJAX_FUNCTION,"desbloquearOrden");
$xajax->register(XAJAX_FUNCTION,"devolverFacturaVenta");
$xajax->register(XAJAX_FUNCTION,"guardarAprobacionDesAprobacion");
$xajax->register(XAJAX_FUNCTION,"guardarFactura");
$xajax->register(XAJAX_FUNCTION,"listadoDctos");
$xajax->register(XAJAX_FUNCTION,"validarAperturaCaja");
$xajax->register(XAJAX_FUNCTION,"validarTipoDocumento");
$xajax->register(XAJAX_FUNCTION,"buscarNumeroControl");
$xajax->register(XAJAX_FUNCTION,"verMovimientosArticulo");
// FUNCIONES AGREGADAS EL 10-03-2014
$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentes");
$xajax->register(XAJAX_FUNCTION,"actualizarObjetosExistentesDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"buscarAnticipoNotaCredito");
$xajax->register(XAJAX_FUNCTION,"cargarBancoCliente");
$xajax->register(XAJAX_FUNCTION,"cargarBancoCompania");
$xajax->register(XAJAX_FUNCTION,"cargarCuentasCompania");
$xajax->register(XAJAX_FUNCTION,"cargarPago");
$xajax->register(XAJAX_FUNCTION,"cargarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"cargarPorcentajeTarjetaCredito");
$xajax->register(XAJAX_FUNCTION,"cargarSaldoDocumento");
$xajax->register(XAJAX_FUNCTION,"cargarTarjetaCuenta");
$xajax->register(XAJAX_FUNCTION,"cargarTipoPago");
$xajax->register(XAJAX_FUNCTION,"cargarTipoPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPago");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDeposito");
$xajax->register(XAJAX_FUNCTION,"eliminarPagoDetalleDepositoForzado");
$xajax->register(XAJAX_FUNCTION,"listadoAnticipoNotaCredito");


// FUNCION AGREGADAS EL 10-03-2014
function nombreBanco($idBanco){
	$query = sprintf("SELECT nombreBanco FROM bancos WHERE idBanco = %s",$idBanco);
	$rsQuery = mysql_query($query) or die(mysql_error());
	$rowQuery = mysql_fetch_array($rsQuery);
	
	return $rowQuery['nombreBanco'];
}

function numeroCuenta($idCuenta){
	$sqlBuscarNumeroCuenta = sprintf("SELECT numeroCuentaCompania FROM cuentas WHERE idCuentas = %s",$idCuenta);
	$rsBuscarNumeroCuenta = mysql_query($sqlBuscarNumeroCuenta) or die(mysql_error());
	$rowBuscarNumeroCuenta = mysql_fetch_array($rsBuscarNumeroCuenta);
	
	return $rowBuscarNumeroCuenta['numeroCuentaCompania'];
}

function errorCargarPago($objResponse){
	$objResponse->script("
	byId('agregar').disabled = false;
	byId('btnGuardar').disabled = false;
	byId('btnCancelar').disabled = false;");
}

function errorCargarPagoDetalleDeposito($objResponse){
	$objResponse->script("
	byId('btnAgregarMontoDeposito').disabled = false;
	byId('btnGuardarDetalleDeposito').disabled = false;
	byId('btnCancelarDetalleDeposito').disabled = false;");
}

function errorGuardarFactura($objResponse){
	$objResponse->script("
	byId('btnGuardar').disabled = false;
	byId('btnCancelar').disabled = false;");
}

/**
 * Busca solo los ivas cargados en la orden ya guardada, por si a futuro cambian los ivas, traer el de la orden
 * tambien se utiliza para todos los historicos o los que no sean nuevos. Vistas de orden.
 * @param int $idOrden Id de la orden a cargar
 * @return Array Es un array de arrays con indice como idIva y formato array(array(idIva,iva,observacion))
 */
function cargarIvasOrden($idOrden){
    $arrayIva = array();
    
    $query = sprintf("SELECT pg_iva.idIva, sa_orden_iva.iva, pg_iva.observacion
                      FROM sa_orden_iva 
                      INNER JOIN pg_iva ON sa_orden_iva.id_iva = pg_iva.idIva
                      WHERE id_orden = %s",
                valTpDato($idOrden, "int"));
    $rs = mysql_query($query);
    if(!$rs) { return die(mysql_error()."\nLinea: ".__LINE__."\nArchivo: ".__FILE__); }
    
    while($row = mysql_fetch_assoc($rs)){
        $arrayIva[$row["idIva"]] = array("idIva" => $row["idIva"],
                                         "iva" => $row["iva"],
                                         "observacion" => $row["observacion"]);
    }
    
    return $arrayIva;
}
?>