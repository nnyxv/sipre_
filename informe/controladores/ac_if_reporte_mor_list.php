<?php
set_time_limit(0);

function buscarFacturaVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha']);
	
	$objResponse->loadCommands(listaCapacityWorkshop($frmBuscar['lstEmpresa'], $frmBuscar['txtFecha']));
	$objResponse->loadCommands(listaExpressService($frmBuscar['lstEmpresa'], $frmBuscar['txtFecha']));
	$objResponse->loadCommands(listaMaintenanceAndPublicMechanics($frmBuscar['lstEmpresa'], $frmBuscar['txtFecha']));
	$objResponse->loadCommands(listaOrderModelsByYear($frmBuscar['lstEmpresa'], $frmBuscar['txtFecha']));
	$objResponse->loadCommands(listaPublicMechanics($frmBuscar['lstEmpresa'], $frmBuscar['txtFecha']));
	
	return $objResponse;
}

function listaCapacityWorkshop($idEmpresa, $txtFecha){
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$valFecha[0] = date("m", strtotime("01-".$txtFecha));
	$valFecha[1] = date("Y", strtotime("01-".$txtFecha));
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"20\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">CAPACITY WORKSHOP REPORT - PUBLIC (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$objMOR = new ModeloMOR();
	$objMOR->campOrd = "descripcion";
	$objMOR->tpOrd = "ASC";
	$queryFiltroOrden = $objMOR->queryFiltroOrden(array(
		"idEmpresa" => $idEmpresa,
		"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($queryFiltroOrden);
	$rsFiltroOrden = mysql_query($queryFiltroOrden);
	if (!$rsFiltroOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFiltroOrden = mysql_num_rows($rsFiltroOrden);
	while ($rowFiltroOrden = mysql_fetch_assoc($rsFiltroOrden)) {
		$arrayFiltroOrden[] = $rowFiltroOrden['descripcion'];
	}
	
	$htmlTh .= "<tr>";
		$htmlTh .= "<td class=\"textoBlancoNegrita_10px\" colspan=\"20\">".implode(", ", $arrayFiltroOrden)."</td>";
	$htmlTh .= "</tr>";
	
	$objMOR->campOrd = "";
	$objMOR->tpOrd = "";
	$arrayCapacityWorkshopAux = $objMOR->CapacityWorkshop(array(
		"idEmpresa" => $idEmpresa,
		"fechaCierre" => $txtFecha,
		"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($arrayCapacityWorkshopAux);
	if (!is_array($arrayCapacityWorkshopAux)) return $objResponse->alert($arrayCapacityWorkshopAux);
	
	$arrayCapacityWorkshop['average_days_of_stay']['descripcion'] = "Average Days Of Stay:";
	$arrayCapacityWorkshop['average_days_of_stay']['title'] = "Días Promedio de Estadía";
	$arrayCapacityWorkshop['average_days_of_stay']['resultado'] = $arrayCapacityWorkshopAux['average_days_of_stay'];
	
	$arrayCapacityWorkshop['total_number_of_service_advisor']['descripcion'] = "Total Number Of Service Advisor:";
	$arrayCapacityWorkshop['total_number_of_service_advisor']['title'] = "Número Total de Asesores de Servicio";
	$arrayCapacityWorkshop['total_number_of_service_advisor']['resultado'] = $arrayCapacityWorkshopAux['total_number_of_service_advisor'];
	
	$arrayCapacityWorkshop['number_of_controlist']['descripcion'] = "Number Of Controlist:";
	$arrayCapacityWorkshop['number_of_controlist']['title'] = "Número de Controladores";
	$arrayCapacityWorkshop['number_of_controlist']['resultado'] = "-";
	
	$arrayCapacityWorkshop['number_of_cases_reported_as_vor']['descripcion'] = "Number Of Cases Reported As VOR:";
	$arrayCapacityWorkshop['number_of_cases_reported_as_vor']['title'] = "Número de Casos Notificados como VOR";
	$arrayCapacityWorkshop['number_of_cases_reported_as_vor']['resultado'] = "-";
	
	$arrayCapacityWorkshop['total_number_of_bays']['descripcion'] = "Total Number Of Bays:";
	$arrayCapacityWorkshop['total_number_of_bays']['title'] = "Número Total de Pinos";
	$arrayCapacityWorkshop['total_number_of_bays']['resultado'] = $arrayCapacityWorkshopAux['total_number_of_bays'];
	
	$arrayCapacityWorkshop['number_consult_iii_plus']['descripcion'] = "# Consult III Plus:";
	$arrayCapacityWorkshop['number_consult_iii_plus']['title'] = "# Consult III Plus";
	$arrayCapacityWorkshop['number_consult_iii_plus']['resultado'] = "-";
	
	$arrayCapacityWorkshop['total_number_of_technical']['descripcion'] = "Total Number Of Technical:";
	$arrayCapacityWorkshop['total_number_of_technical']['title'] = "Número Total de Técnicos";
	$arrayCapacityWorkshop['total_number_of_technical']['resultado'] = $arrayCapacityWorkshopAux['total_number_of_technical'];
	
	$arrayCapacityWorkshop['bays_support']['descripcion'] = "Bays Support (No Parking):";
	$arrayCapacityWorkshop['bays_support']['title'] = "Soporte para Pinos (Sin Aparcamiento)";
	$arrayCapacityWorkshop['bays_support']['resultado'] = "-";
	
	$arrayCapacityWorkshop['number_of_car_washers']['descripcion'] = "Number Of Car Washers:";
	$arrayCapacityWorkshop['number_of_car_washers']['title'] = "Número de Lavadoras de Coche";
	$arrayCapacityWorkshop['number_of_car_washers']['resultado'] = $arrayCapacityWorkshopAux['number_of_car_washers'];
	
	if (isset($arrayCapacityWorkshop)) {
		foreach($arrayCapacityWorkshop as $indiceCapacityWorkshop => $valorCapacityWorkshop) {
			$clase = (fmod($contFila, 6) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"right\" class=\"".$clase."\" height=\"22\">" : "";
				
				$htmlTb .= sprintf("<td %s %s width=\"%s\">%s</td>",
					((strlen($valorCapacityWorkshop['descripcion']) > 1) ? "class=\"tituloCampo\"" : ""),
					((strlen($valorCapacityWorkshop['title']) > 1) ? "title=\"".$valorCapacityWorkshop['title']."\"" : ""),
					(100 / 6)."%",
					$valorCapacityWorkshop['descripcion']);
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>",
					(100 / 6)."%",
					((!in_array($valorCapacityWorkshop['resultado'],array("-",""))) ? number_format($valorCapacityWorkshop['resultado'], 2, ".", ",") : $valorCapacityWorkshop['resultado']));
					
			$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
		}
	}
	
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaCapacityWorkshop","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaExpressService($idEmpresa, $txtFecha){
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$valFecha[0] = date("m", strtotime("01-".$txtFecha));
	$valFecha[1] = date("Y", strtotime("01-".$txtFecha));
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"20\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">EXPRESS SERVICE (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$objMOR = new ModeloMOR();
	$objMOR->campOrd = "descripcion";
	$objMOR->tpOrd = "ASC";
	$queryFiltroOrden = $objMOR->queryFiltroOrden(array(
		"idEmpresa" => $idEmpresa,
		"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($queryFiltroOrden);
	$rsFiltroOrden = mysql_query($queryFiltroOrden);
	if (!$rsFiltroOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFiltroOrden = mysql_num_rows($rsFiltroOrden);
	while ($rowFiltroOrden = mysql_fetch_assoc($rsFiltroOrden)) {
		$arrayFiltroOrden[] = $rowFiltroOrden['descripcion'];
	}
	
	$htmlTh .= "<tr>";
		$htmlTh .= "<td class=\"textoBlancoNegrita_10px\" colspan=\"20\">".implode(", ", $arrayFiltroOrden)."</td>";
	$htmlTh .= "</tr>";
	
	$objMOR->campOrd = "descripcion_tempario";
	$objMOR->tpOrd = "ASC";
	$arrayExpressServiceAux = $objMOR->ServicioExpreso(array(
		"idEmpresa" => $idEmpresa,
		"fechaCierre" => $txtFecha,
		"idFiltroOrden" => "1,2,7,8",
		"estatusExpressService" => 1));//$objResponse->alert($arrayExpressServiceAux);
	if (!is_array($arrayExpressServiceAux)) return $objResponse->alert($arrayExpressServiceAux);
	
	$arrayExpressService['numero_puentes']['descripcion'] = "Number Of Bays:";
	$arrayExpressService['numero_puentes']['title'] = "Numero de Pinos";
	$arrayExpressService['numero_puentes']['resultado'] = $arrayExpressServiceAux['numero_puentes'];
	
	$arrayExpressService['horas_facturadas_servicio_expreso']['descripcion'] = "Hours Billed To Public:";
	$arrayExpressService['horas_facturadas_servicio_expreso']['title'] = "Horas Facturadas al Público";
	$arrayExpressService['horas_facturadas_servicio_expreso']['resultado'] = $arrayExpressServiceAux['horas_facturadas_servicio_expreso'];
	
	$arrayExpressService['numero_llamadas_clientes_expreso']['descripcion'] = "Number Of Call To E.S. Customers:";
	$arrayExpressService['numero_llamadas_clientes_expreso']['title'] = "Número de Llamadas para Clientes de Servicio Rápido";
	$arrayExpressService['numero_llamadas_clientes_expreso']['resultado'] = "-";
	
	$arrayExpressService['dinero_facturado_labor']['descripcion'] = "Sale Of Labor:";
	$arrayExpressService['dinero_facturado_labor']['title'] = "Venta de Mano de Obra";
	$arrayExpressService['dinero_facturado_labor']['resultado'] = $arrayExpressServiceAux['dinero_facturado_labor'];
	
	$arrayExpressService['numero_ordenes_citadas']['descripcion'] = "Number Of R.O. Scheduled:";
	$arrayExpressService['numero_ordenes_citadas']['title'] = "Número de Órdenes de Reparación Programadas";
	$arrayExpressService['numero_ordenes_citadas']['resultado'] = $arrayExpressServiceAux['numero_ordenes_citadas'];
	
	$arrayExpressService['dinero_facturado_piezas']['descripcion'] = "Sale Of Part:";
	$arrayExpressService['dinero_facturado_piezas']['title'] = "Venta de Pieza";
	$arrayExpressService['dinero_facturado_piezas']['resultado'] = $arrayExpressServiceAux['dinero_facturado_piezas'];
	
	$arrayExpressService['numero_ordenes_realizadas']['descripcion'] = "Number Of R.O. Realized:";
	$arrayExpressService['numero_ordenes_realizadas']['title'] = "Número de Órdenes de Reparación Realizadas";
	$arrayExpressService['numero_ordenes_realizadas']['resultado'] = $arrayExpressServiceAux['numero_ordenes_realizadas'];
	
	$arrayExpressService['dinero_facturado_labor_adicional']['descripcion'] = "Aditional Sale Of Labor:";
	$arrayExpressService['dinero_facturado_labor_adicional']['title'] = "Venta Adicional de Mano de Obra";
	$arrayExpressService['dinero_facturado_labor_adicional']['resultado'] = "-";
	
	$arrayExpressService['numero_ordenes_a_tiempo']['descripcion'] = "Number Of R.O. Realized On Time:";
	$arrayExpressService['numero_ordenes_a_tiempo']['title'] = "Número de Órdenes de Reparación Realizadas a Tiempo";
	$arrayExpressService['numero_ordenes_a_tiempo']['resultado'] = $arrayExpressServiceAux['numero_ordenes_a_tiempo'];
	
	$arrayExpressService['dinero_facturado_piezas_adicional']['descripcion'] = "Aditional Sale Of Part:";
	$arrayExpressService['dinero_facturado_piezas_adicional']['title'] = "Venta Adicional de Pieza";
	$arrayExpressService['dinero_facturado_piezas_adicional']['resultado'] = "-";
	
	$arrayExpressService['numero_horas_disponibles']['descripcion'] = "Number Of Available Hours:";
	$arrayExpressService['numero_horas_disponibles']['title'] = "Número de Horas Disponibles";
	$arrayExpressService['numero_horas_disponibles']['resultado'] = "-";
	
	$arrayExpressService['-']['descripcion'] = "";
	$arrayExpressService['-']['title'] = "";
	$arrayExpressService['-']['resultado'] = "";
	
	$arrayExpressService['horas_trabajadas']['descripcion'] = "Hours Worked:";
	$arrayExpressService['horas_trabajadas']['title'] = "Horas Trabajadas";
	$arrayExpressService['horas_trabajadas']['resultado'] = "-";
	
	$arrayExpressService['satisfaccion_cliente']['descripcion'] = "Customer Satisfaction Index (CSI):";
	$arrayExpressService['satisfaccion_cliente']['title'] = "Índice de Satisfacción del Cliente";
	$arrayExpressService['satisfaccion_cliente']['resultado'] = "-";
	
	if (isset($arrayExpressService)) {
		foreach($arrayExpressService as $indiceExpressService => $valorExpressService) {
			$clase = (fmod($contFila, 4) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= (fmod($contFila, 2) == 1) ? "<tr align=\"right\" class=\"".$clase."\" height=\"22\">" : "";
				
				$htmlTb .= sprintf("<td %s %s width=\"%s\">%s</td>",
					((strlen($valorExpressService['descripcion']) > 1) ? "class=\"tituloCampo\"" : ""),
					((strlen($valorExpressService['title']) > 1) ? "title=\"".$valorExpressService['title']."\"" : ""),
					(100 / 4)."%",
					$valorExpressService['descripcion']);
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>",
					(100 / 4)."%",
					((!in_array($valorExpressService['resultado'],array("-",""))) ? number_format($valorExpressService['resultado'], 2, ".", ",") : $valorExpressService['resultado']));
					
			$htmlTb .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
		}
	}
	
	$htmlTblFin = "</table>";
	
	$objResponse->assign("divListaExpressService","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaMaintenanceAndPublicMechanics($idEmpresa, $txtFecha){
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$valFecha[0] = date("m", strtotime("01-".$txtFecha));
	$valFecha[1] = date("Y", strtotime("01-".$txtFecha));
	
	$tipoRepairModelsByYear = 1; // 1 = VALE DE RECEPCION, 2 = ORDEN DE SERVICIO
	$arrayRepairModelsByYear = array(1 => "VALE DE RECEPCIÓN", 2 => "ORDEN DE SERVICIO");
	
	$mostrarMotivoCita = true;
	$mostrarTempario = true;
	$mostrarTipoOrden = true;
	
	$objResponse->script("byId('trListaTipoOrdenPorModelo').style.display = '';");
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"50\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">MAINTENANCE AND PUBLIC MECHANICS REPAIR ORDERS TABLE (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$objMOR = new ModeloMOR();
	$objMOR->campOrd = "descripcion";
	$objMOR->tpOrd = "ASC";
	$queryFiltroOrden = $objMOR->queryFiltroOrden(array(
		"idEmpresa" => $idEmpresa,
		"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($queryFiltroOrden);
	$rsFiltroOrden = mysql_query($queryFiltroOrden);
	if (!$rsFiltroOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFiltroOrden = mysql_num_rows($rsFiltroOrden);
	while ($rowFiltroOrden = mysql_fetch_assoc($rsFiltroOrden)) {
		$arrayFiltroOrden[] = $rowFiltroOrden['descripcion'];
	}
	
	$htmlTh .= "<tr>";
		$htmlTh .= "<td class=\"textoBlancoNegrita_10px\" colspan=\"20\">".implode(", ", $arrayFiltroOrden)."</td>";
	$htmlTh .= "</tr>";
	
	$htmlTh .= "<tr>";
		$htmlTh .= "<td class=\"textoBlancoNegrita_10px\" colspan=\"20\">".$arrayRepairModelsByYear[$tipoRepairModelsByYear]." DE UNIDADES FACTURADAS"."</td>";
	$htmlTh .= "</tr>";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"14%\">"."</td>";
		
		$objMOR = new ModeloMOR();
		$objMOR->campOrd = "nom_modelo";
		$objMOR->tpOrd = "ASC";
		if ($tipoRepairModelsByYear == 1) {
			$queryModeloServicio = $objMOR->queryModeloPorRecepcionServicio(array(
				"idEmpresa" => $idEmpresa,
				"fechaCierre" => $txtFecha,
				"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($queryModeloServicio);
		} else {
			$queryModeloServicio = $objMOR->queryModeloPorOrdenServicio(array(
				"idEmpresa" => $idEmpresa,
				"fechaCierre" => $txtFecha,
				"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($queryModeloServicio);
		}
		$rsModeloServicio = mysql_query($queryModeloServicio);
		if (!$rsModeloServicio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsModeloServicio = mysql_num_rows($rsModeloServicio);
		while ($rowModeloServicio = mysql_fetch_assoc($rsModeloServicio)) {
			$htmlTh .= "<td title=\"Id Modelo: ".$rowModeloServicio['id_modelo']."\" width=\"".(80 / $totalRowsModeloServicio)."%\">".$rowModeloServicio['nom_modelo']."</td>";
			
			$arrayModeloServicio[$rowModeloServicio['id_modelo']] = array(
				"id_modelo" => $rowModeloServicio['id_modelo'],
				"cantidad_ordenes_motivo" => 0,
				"cantidad_ordenes_tempario" => 0,
				"cantidad_ordenes_tipo_orden" => 0);
		}
		
		$htmlTh .= "<td width=\"6%\">"."Total"."</td>";
	$htmlTh .= "</tr>";
	
	
	if ($mostrarMotivoCita == true) {
		$htmlTb .= "<tr class=\"tituloColumna\">";
			$htmlTb .= "<td colspan=\"50\">MOTIVOS DE CITA</td>";
		$htmlTb .= "</tr>";
		$objMOR->campOrd = "descripcion_submotivo";
		$objMOR->tpOrd = "ASC";
		if ($tipoRepairModelsByYear == 1) {
			$queryMotivoServicio = $objMOR->queryMotivoPorRecepcionServicio(array(
				"idEmpresa" => $idEmpresa,
				"fechaCierre" => $txtFecha,
				"idFiltroOrden" => "1,2,7,8"));
		} else {
			$queryMotivoServicio = $objMOR->queryMotivoPorOrdenServicio(array(
				"idEmpresa" => $idEmpresa,
				"fechaCierre" => $txtFecha,
				"idFiltroOrden" => "1,2,7,8"));
		}
		$rsMotivoServicio = mysql_query($queryMotivoServicio);
		if (!$rsMotivoServicio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsMotivoServicio = mysql_num_rows($rsMotivoServicio);
		while ($rowMotivoServicio = mysql_fetch_assoc($rsMotivoServicio)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\" title=\"Id Submotivo: ".$rowMotivoServicio['id_submotivo']."\">".utf8_encode($rowMotivoServicio['descripcion_submotivo'])."</td>";
				if (isset($arrayModeloServicio)) {
					foreach ($arrayModeloServicio as $indice => $valor) {
						$idModelo = $arrayModeloServicio[$indice]['id_modelo'];
						
						$objMOR->campOrd = "descripcion_submotivo";
						$objMOR->tpOrd = "ASC";
						if ($tipoRepairModelsByYear == 1) {
							$queryMotivoPorModelo = $objMOR->queryMotivoPorModeloRecepcion(array(
								"idEmpresa" => $idEmpresa,
								"fechaCierre" => $txtFecha,
								"idFiltroOrden" => "1,2,7,8",
								"idSubMotivo" => $rowMotivoServicio['id_submotivo'],
								"idModelo" => $idModelo));
						} else {
							$queryMotivoPorModelo = $objMOR->queryMotivoPorModeloOrden(array(
								"idEmpresa" => $idEmpresa,
								"fechaCierre" => $txtFecha,
								"idFiltroOrden" => "1,2,7,8",
								"idSubMotivo" => $rowMotivoServicio['id_submotivo'],
								"idModelo" => $idModelo));
						}
						$rsMotivoPorModelo = mysql_query($queryMotivoPorModelo);//$objResponse->alert($queryMotivoPorModelo);
						if (!$rsMotivoPorModelo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRowsMotivoPorModelo = mysql_num_rows($rsMotivoPorModelo);
						$rowMotivoPorModelo = mysql_fetch_assoc($rsMotivoPorModelo);
						
						$htmlTb .= "<td>".valTpDato(number_format($rowMotivoPorModelo['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
						
						$arrayModeloServicio[$idModelo]['cantidad_ordenes_motivo'] += $rowMotivoPorModelo['cantidad_ordenes'];
					}
				}
				$htmlTb .= "<td class=\"trResaltarTotal\">".valTpDato(number_format($rowMotivoServicio['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";
		}
		
		if (isset($arrayModeloServicio)) {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\">"."Total:"."</td>";
			foreach ($arrayModeloServicio as $indice => $valor) {
				$htmlTb .= "<td>".valTpDato(number_format($arrayModeloServicio[$indice]['cantidad_ordenes_motivo'], 2, ".", ","),"cero_por_vacio")."</td>";
				
				$arrayTotal['cantidad_ordenes_motivo'] += $arrayModeloServicio[$indice]['cantidad_ordenes_motivo'];
			}
				$htmlTb .= "<td>".valTpDato(number_format($arrayTotal['cantidad_ordenes_motivo'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	
	$objMOR->campOrd = "nom_modelo";
	$objMOR->tpOrd = "ASC";
	if ($tipoRepairModelsByYear == 1) {
		$queryModeloServicio = $objMOR->queryModeloPorRecepcionServicio(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $txtFecha,
			"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($queryModeloServicio);
	} else {
		$queryModeloServicio = $objMOR->queryModeloPorOrdenServicio(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $txtFecha,
			"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($queryModeloServicio);
	}
	$rsModeloServicio = mysql_query($queryModeloServicio);
	if (!$rsModeloServicio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsModeloServicio = mysql_num_rows($rsModeloServicio);
	while ($rowModeloServicio = mysql_fetch_assoc($rsModeloServicio)) {
		$arrayModeloServicio[$rowModeloServicio['id_modelo']]['cantidad_ordenes_tipo_orden'] += $rowModeloServicio['cantidad_ordenes'];
	}
	
	if ($mostrarTempario == true) {
		$htmlTb .= "<tr class=\"tituloColumna\">";
			$htmlTb .= "<td colspan=\"50\">MANOS DE OBRA</td>";
		$htmlTb .= "</tr>";
		$objMOR->campOrd = "descripcion_tempario";
		$objMOR->tpOrd = "ASC";
		$queryTemparioServicio = $objMOR->queryTemparioServicio(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $txtFecha,
			"idFiltroOrden" => "1,2,7,8"));
		$rsTemparioServicio = mysql_query($queryTemparioServicio);
		if (!$rsTemparioServicio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTemparioServicio = mysql_num_rows($rsTemparioServicio);
		while ($rowTemparioServicio = mysql_fetch_assoc($rsTemparioServicio)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\" title=\"Id Tempario: ".$rowTemparioServicio['id_tempario']."\">".utf8_encode($rowTemparioServicio['descripcion_tempario'])."</td>";
				if (isset($arrayModeloServicio)) {
					foreach ($arrayModeloServicio as $indice => $valor) {
						$idModelo = $arrayModeloServicio[$indice]['id_modelo'];
						
						$objMOR->campOrd = "nom_modelo";
						$objMOR->tpOrd = "ASC";
						$queryTemparioPorModelo = $objMOR->queryTemparioPorModelo(array(
							"idEmpresa" => $idEmpresa,
							"fechaCierre" => $txtFecha,
							"idFiltroOrden" => "1,2,7,8",
							"idTempario" => $rowTemparioServicio['id_tempario'],
							"idModelo" => $idModelo));
						$rsTemparioPorModelo = mysql_query($queryTemparioPorModelo);
						if (!$rsTemparioPorModelo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRowsTemparioPorModelo = mysql_num_rows($rsTemparioPorModelo);
						$rowTemparioPorModelo = mysql_fetch_assoc($rsTemparioPorModelo);
						
						$htmlTb .= "<td>".valTpDato(number_format($rowTemparioPorModelo['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
						
						$arrayModeloServicio[$idModelo]['cantidad_ordenes_tempario'] += $rowTemparioPorModelo['cantidad_ordenes'];
					}
				}
				$htmlTb .= "<td class=\"trResaltarTotal\">".valTpDato(number_format($rowTemparioServicio['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";
		}
	
		if (isset($arrayModeloServicio)) {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\">"."Total:"."</td>";
			foreach ($arrayModeloServicio as $indice => $valor) {
				$htmlTb .= "<td>".valTpDato(number_format($arrayModeloServicio[$indice]['cantidad_ordenes_tempario'], 2, ".", ","),"cero_por_vacio")."</td>";
				
				$arrayTotal['cantidad_ordenes_tempario'] += $arrayModeloServicio[$indice]['cantidad_ordenes_tempario'];
			}
				$htmlTb .= "<td>".valTpDato(number_format($arrayTotal['cantidad_ordenes_tempario'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	
	if ($mostrarTipoOrden == true) {
		$htmlTb .= "<tr class=\"tituloColumna\">";
			$htmlTb .= "<td colspan=\"50\">TABLE FOR OTHER REPAIR ORDERS</td>";
		$htmlTb .= "</tr>";
		$objMOR->campOrd = "nombre_tipo_orden";
		$objMOR->tpOrd = "ASC";
		$queryTipoOrdenServicio = $objMOR->queryTipoOrdenServicio(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $txtFecha));
		$rsTipoOrdenServicio = mysql_query($queryTipoOrdenServicio);
		if (!$rsTipoOrdenServicio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsTipoOrdenServicio = mysql_num_rows($rsTipoOrdenServicio);
		while ($rowTipoOrdenServicio = mysql_fetch_assoc($rsTipoOrdenServicio)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\" title=\"Id Filtro Orden: ".$rowTipoOrdenServicio['id_filtro_orden']."\nId Tipo Orden: ".$rowTipoOrdenServicio['id_tipo_orden']."\">".$rowTipoOrdenServicio['nombre_tipo_orden']."</td>";
				if (isset($arrayModeloServicio)) {
					foreach ($arrayModeloServicio as $indice => $valor) {
						$idModelo = $arrayModeloServicio[$indice]['id_modelo'];
						
						$objMOR->campOrd = "nom_modelo";
						$objMOR->tpOrd = "ASC";
						$queryTipoOrdenPorModelo = $objMOR->queryTipoOrdenPorModelo(array(
							"idEmpresa" => $idEmpresa,
							"fechaCierre" => $txtFecha,
							"idFiltroOrden" => $rowTipoOrdenServicio['id_filtro_orden'],
							"idModelo" => $idModelo,
							"estatusExpressService" => $rowTipoOrdenServicio['serviexp']));
						$rsTipoOrdenPorModelo = mysql_query($queryTipoOrdenPorModelo);
						if (!$rsTipoOrdenPorModelo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
						$totalRowsTipoOrdenPorModelo = mysql_num_rows($rsTipoOrdenPorModelo);
						$rowTipoOrdenPorModelo = mysql_fetch_assoc($rsTipoOrdenPorModelo);
						
						$htmlTb .= "<td>".valTpDato(number_format($rowTipoOrdenPorModelo['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
					}
				}
				$htmlTb .= "<td class=\"trResaltarTotal\">".valTpDato(number_format($rowTipoOrdenServicio['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";
		}
		
		if (isset($arrayModeloServicio)) {
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td class=\"tituloCampo\">"."Total:"."</td>";
			foreach ($arrayModeloServicio as $indice => $valor) {
				$htmlTb .= "<td>".valTpDato(number_format($arrayModeloServicio[$indice]['cantidad_ordenes_tipo_orden'], 2, ".", ","),"cero_por_vacio")."</td>";
				
				$arrayTotal['cantidad_ordenes_tipo_orden'] += $arrayModeloServicio[$indice]['cantidad_ordenes_tipo_orden'];
			}
				$htmlTb .= "<td>".valTpDato(number_format($arrayTotal['cantidad_ordenes_tipo_orden'], 2, ".", ","),"cero_por_vacio")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	$htmlTblFin = "</table>";
	
	$objResponse->assign("divListaTipoOrdenPorModelo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaOrderModelsByYear($idEmpresa, $txtFecha){
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$valFecha[0] = date("m", strtotime("01-".$txtFecha));
	$valFecha[1] = date("Y", strtotime("01-".$txtFecha));
	
	$tipoRepairModelsByYear = 1; // 1 = VALE DE RECEPCION, 2 = ORDEN DE SERVICIO
	$arrayRepairModelsByYear = array(1 => "VALE DE RECEPCIÓN", 2 => "ORDEN DE SERVICIO");
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"20\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">REPAIR ORDER MODELS BY YEAR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$objMOR = new ModeloMOR();
	$objMOR->campOrd = "descripcion";
	$objMOR->tpOrd = "ASC";
	$queryFiltroOrden = $objMOR->queryFiltroOrden(array(
		"idEmpresa" => $idEmpresa,
		"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($queryFiltroOrden);
	$rsFiltroOrden = mysql_query($queryFiltroOrden);
	if (!$rsFiltroOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFiltroOrden = mysql_num_rows($rsFiltroOrden);
	while ($rowFiltroOrden = mysql_fetch_assoc($rsFiltroOrden)) {
		$arrayFiltroOrden[] = $rowFiltroOrden['descripcion'];
	}
	
	$htmlTh .= "<tr>";
		$htmlTh .= "<td class=\"textoBlancoNegrita_10px\" colspan=\"20\">".implode(", ", $arrayFiltroOrden)."</td>";
	$htmlTh .= "</tr>";
	
	$htmlTh .= "<tr>";
		$htmlTh .= "<td class=\"textoBlancoNegrita_10px\" colspan=\"20\">".$arrayRepairModelsByYear[$tipoRepairModelsByYear]." DE UNIDADES FACTURADAS"."</td>";
	$htmlTh .= "</tr>";
	
	$objMOR = new ModeloMOR();
	$objMOR->campOrd = "nom_ano";
	$objMOR->tpOrd = "DESC";
	if ($tipoRepairModelsByYear == 1) {
		$query = $objMOR->queryRecepcionPorAno(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $txtFecha,
			"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($query);
	} else {
		$query = $objMOR->queryOrdenPorAno(array(
			"idEmpresa" => $idEmpresa,
			"fechaCierre" => $txtFecha,
			"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($query);
	}
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= (fmod($contFila, 5) == 1) ? "<tr align=\"right\" class=\"".$clase."\" height=\"22\">" : "";
			
			$htmlTb .= "<td class=\"tituloCampo\" width=\"14%\">Repair Orders Models ".$row['nom_ano'].":</td>";
			$htmlTb .= "<td width=\"6%\">".$row['cantidad_ordenes']."</td>";
				
		$htmlTb .= (fmod($contFila, 5) == 0) ? "</tr>" : "";
		
		$arrayTotal['cantidad_ordenes'] += $row['cantidad_ordenes'];
		
		if ($contFila == $totalRows) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= (fmod($contFila, 5) == 1) ? "<tr align=\"right\" class=\"".$clase."\" height=\"22\">" : "";
				$htmlTb .= "<td class=\"tituloCampo\" width=\"14%\">Total:</td>";
				$htmlTb .= "<td class=\"trResaltarTotal\" width=\"6%\">".$arrayTotal['cantidad_ordenes']."</td>";
			$htmlTb .= (fmod($contFila, 5) == 0) ? "</tr>" : "";
		}
	}
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaOrderModelsByYear","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaPublicMechanics($idEmpresa, $txtFecha){
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$valFecha[0] = date("m", strtotime("01-".$txtFecha));
	$valFecha[1] = date("Y", strtotime("01-".$txtFecha));
	
	$objResponse->script("byId('trListaPublicMechanics').style.display = '';");
	
	$htmlTblIni .= "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"20\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">PUBLIC MECHANICS - WORKSHOP (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$objMOR = new ModeloMOR();
	$objMOR->campOrd = "descripcion";
	$objMOR->tpOrd = "ASC";
	$queryFiltroOrden = $objMOR->queryFiltroOrden(array(
		"idEmpresa" => $idEmpresa,
		"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($queryFiltroOrden);
	$rsFiltroOrden = mysql_query($queryFiltroOrden);
	if (!$rsFiltroOrden) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsFiltroOrden = mysql_num_rows($rsFiltroOrden);
	while ($rowFiltroOrden = mysql_fetch_assoc($rsFiltroOrden)) {
		$arrayFiltroOrden[] = $rowFiltroOrden['descripcion'];
	}
	
	$htmlTh .= "<tr>";
		$htmlTh .= "<td class=\"textoBlancoNegrita_10px\" colspan=\"20\">".implode(", ", $arrayFiltroOrden)."</td>";
	$htmlTh .= "</tr>";
	
	$objMOR->campOrd = "descripcion_tempario";
	$objMOR->tpOrd = "ASC";
	$arrayPublicMechanicsAux = $objMOR->PublicMechanics(array(
		"idEmpresa" => $idEmpresa,
		"fechaCierre" => $txtFecha,
		"idFiltroOrden" => "1,2,7,8"));//$objResponse->alert($arrayPublicMechanicsAux);
	if (!is_array($arrayPublicMechanicsAux)) return $objResponse->alert($arrayPublicMechanicsAux);
	
	$arrayPublicMechanics['sale_of_mechanical_labor']['descripcion'] = "Sale Of Mechanical Labor:";
	$arrayPublicMechanics['sale_of_mechanical_labor']['title'] = "Venta de Mano de Obra Mecánica";
	$arrayPublicMechanics['sale_of_mechanical_labor']['resultado'] = $arrayPublicMechanicsAux['sale_of_mechanical_labor'];
	
	$arrayPublicMechanics['cost_of_mechanical_labor']['descripcion'] = "Cost Of Mechanical Labor:";
	$arrayPublicMechanics['cost_of_mechanical_labor']['title'] = "Costo de Mano de Obra Mecánica";
	$arrayPublicMechanics['cost_of_mechanical_labor']['resultado'] = $arrayPublicMechanicsAux['cost_of_mechanical_labor'];
	
	$arrayPublicMechanics['sale_of_mechanical_part']['descripcion'] = "Sale Of Mechanical Parts:";
	$arrayPublicMechanics['sale_of_mechanical_part']['title'] = "Venta de Piezas Mecánicas";
	$arrayPublicMechanics['sale_of_mechanical_part']['resultado'] = $arrayPublicMechanicsAux['sale_of_mechanical_part'];
	
	$arrayPublicMechanics['cost_of_mechanical_part']['descripcion'] = "Cost Of Mechanical Parts:";
	$arrayPublicMechanics['cost_of_mechanical_part']['title'] = "Costo de Piezas Mecánicas";
	$arrayPublicMechanics['cost_of_mechanical_part']['resultado'] = $arrayPublicMechanicsAux['cost_of_mechanical_part'];
	
	$arrayPublicMechanics['number_of_availabre_hours']['descripcion'] = "Number Of Available Hours:";
	$arrayPublicMechanics['number_of_availabre_hours']['title'] = "Número de Horas Disponibles";
	$arrayPublicMechanics['number_of_availabre_hours']['resultado'] =  $arrayPublicMechanicsAux['number_of_availabre_hours'];
	
	$arrayPublicMechanics['-']['descripcion'] = "";
	$arrayPublicMechanics['-']['title'] = "";
	$arrayPublicMechanics['-']['resultado'] = "";
	
	$arrayPublicMechanics['hours_worked']['descripcion'] = "Hours Worked:";
	$arrayPublicMechanics['hours_worked']['title'] = "Horas Trabajadas";
	$arrayPublicMechanics['hours_worked']['resultado'] = "-";
	
	$arrayPublicMechanics['--']['descripcion'] = "";
	$arrayPublicMechanics['--']['title'] = "";
	$arrayPublicMechanics['--']['resultado'] = "";
	
	$arrayPublicMechanics['hours_billed_to_public']['descripcion'] = "Hours Billed To Public:";
	$arrayPublicMechanics['hours_billed_to_public']['title'] = "Horas Facturadas al Público";
	$arrayPublicMechanics['hours_billed_to_public']['resultado'] = $arrayPublicMechanicsAux['hours_billed_to_public'];
	
	if (isset($arrayPublicMechanics)) {
		foreach($arrayPublicMechanics as $indicePublicMechanics => $valorPublicMechanics) {
			$clase = (fmod($contFila, 4) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$htmlTb .= (fmod($contFila, 2) == 1) ? "<tr align=\"right\" class=\"".$clase."\" height=\"22\">" : "";
				
				$htmlTb .= sprintf("<td %s %s width=\"%s\">%s</td>",
					((strlen($valorPublicMechanics['descripcion']) > 1) ? "class=\"tituloCampo\"" : ""),
					((strlen($valorPublicMechanics['title']) > 1) ? "title=\"".$valorPublicMechanics['title']."\"" : ""),
					(100 / 4)."%",
					$valorPublicMechanics['descripcion']);
				$htmlTb .= sprintf("<td width=\"%s\">%s</td>",
					(100 / 4)."%",
					((!in_array($valorPublicMechanics['resultado'],array("-",""))) ? number_format($valorPublicMechanics['resultado'], 2, ".", ",") : $valorPublicMechanics['resultado']));
					
			$htmlTb .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
		}
	}
	
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaPublicMechanics","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarFacturaVenta");
$xajax->register(XAJAX_FUNCTION,"listaCapacityWorkshop");
$xajax->register(XAJAX_FUNCTION,"listaExpressService");
$xajax->register(XAJAX_FUNCTION,"listaMaintenanceAndPublicMechanics");
$xajax->register(XAJAX_FUNCTION,"listaOrderModelsByYear");
$xajax->register(XAJAX_FUNCTION,"listaPublicMechanics");
?>