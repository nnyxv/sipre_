<?php
set_time_limit(0);

function buscar($frmBuscar, $a = '') {
	$objResponse = new xajaxResponse();

	if ($a == 1) {
	$objResponse->loadCommands(reporteVentareconvercion($frmBuscar));
		# code...
	}else{
	$objResponse->loadCommands(reporteVenta($frmBuscar));


	}

	return $objResponse;
}

function cargaLstDecimalPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array("0" => "Sin Decimales", "1" => "Con Decimales", "2" => "Sin Decimales MILES", "3" => "Con Decimales MILES");
	$totalRows = count($array);
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirReporteVenta(xajax.getFormValues('frmBuscar'));\"";
	
	$html = "<select id=\"lstDecimalPDF\" name=\"lstDecimalPDF\" ".$class." ".$onChange.">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstDecimalPDF","innerHTML",$html);
	
	return $objResponse;
}

function exportarReporteVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha']);
	
	$objResponse->script("window.open('reportes/if_reporte_venta_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formGrafico($tipoGrafico, $tituloVentana, $categoria, $titulo1, $data1, $titulo2 = "", $data2 = "", $abrevMonedaLocal = "Bs.") {
	$objResponse = new xajaxResponse();
	
	if ($tipoGrafico == "Pie with legend") {
		// GRAFICO
		$data1 = "
		highchartsDarkBlue();
		
		$(function () {
			var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					chart: {
						renderTo: '"."tdGrafico"."',
						plotBackgroundColor: null,
						plotBorderWidth: null,
						plotShadow: false,
						width: 780
					},
					title: {
						text: '".$tituloVentana."'
					},
					tooltip: {
						valueDecimals: 2,
						valueSuffix: '".$abrevMonedaLocal."'
					},
					plotOptions: {
						pie: {
							allowPointSelect: true,
							cursor: 'pointer',
							dataLabels: {
								enabled: true,
								color: '#FFFFFF',
								connectorColor: '#FFFFFF',
								formatter: function() {
									return this.percentage + '%';
								}
							},
							showInLegend: true
						}
					},
					series: [{
						type: 'pie',
						name: '".$titulo1."',
						data: [".str_replace("|*|","'",$data1)."]
					}]
				});
			});
		});";
	} else if ($tipoGrafico == "Donut chart") {
		$arrayEquipo = explode(",",$data1);
		foreach ($arrayEquipo as $indice => $valor) {
			$arrayDetEquipo = NULL;
			$arrayDetEquipo = explode("=*=", $arrayEquipo[$indice]);
			
			foreach ($arrayDetEquipo as $indice2 => $valor2) {
				$arrayMec = explode("-*-",$arrayDetEquipo[2]);
				
				$arrayCategories = NULL;
				$arrayData = NULL;
				foreach ($arrayMec as $indice3 => $valor3) {
					$arrayDetMec = explode("+*+",$arrayMec[$indice3]);
					
					$arrayCategories[] = $arrayDetMec[0];
					$arrayData[] = $arrayDetMec[1];
				}
			}
			
			$arrayDataEquipo[] = "{
				y: ".$arrayDetEquipo[1].",
				color: colors[".$indice."],
				drilldown: {
					name: '".$arrayDetEquipo[0]."',
					categories: ['".implode("','",$arrayCategories)."'],
					data: [".implode(",",$arrayData)."],
					color: colors[".$indice."]
				}
			}";
			
			$arrayCategoriesEquipo[] = $arrayDetEquipo[0];
		}
		
		$data1 = "
		highchartsDarkBlue();
		
		$(function () {
			var colors = Highcharts.getOptions().colors,
				categories = ['".implode("','", $arrayCategoriesEquipo)."'],
				name = '".$tituloVentana."',
				data = [".implode(",", $arrayDataEquipo)."];
		
		
			// Build the data arrays
			var browserData = [];
			var versionsData = [];
			for (var i = 0; i < data.length; i++) {
				// add browser data
				browserData.push({
					name: categories[i],
					y: data[i].y,
					color: data[i].color
				});
		
				// add version data
				for (var j = 0; j < data[i].drilldown.data.length; j++) {
					var brightness = 0.2 - (j / data[i].drilldown.data.length) / 1 ;
					versionsData.push({
						name: data[i].drilldown.categories[j],
						y: data[i].drilldown.data[j],
						color: Highcharts.Color(data[i].color).brighten(brightness).get()
					});
				}
			}
		
			// Create the chart
			var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					chart: {
						renderTo: 'tdGrafico',
						type: 'pie'
					},
					title: {
						text: '".$tituloVentana."'
					},
					yAxis: {
						title: {
							text: 'Total percent market share'
						}
					},
					plotOptions: {
						pie: {
							shadow: false,
							center: ['50%', '50%']
						}
					},
					tooltip: {
						valueSuffix: '%'
					},
					series: [{
						name: '".$titulo1."',
						data: browserData,
						size: '60%',
						dataLabels: {
							formatter: function() {
								return this.y > 5 ? this.point.name : null;
							},
							color: 'white',
							distance: -30,
							style: {
								fontWeight: 'bold',
							},
							enabled: true,
							borderRadius: 5,
							backgroundColor: 'gray',
							borderWidth: 1,
							borderColor: '#AAA'
						}
					}, {
						name: '".$titulo2."',
						data: versionsData,
						size: '80%',
						innerSize: '60%',
						dataLabels: {
							formatter: function() {
								// display only if larger than 1
								return this.y > 1 ? '<b>'+ this.point.name +':</b> '+ this.y +'%'  : null;
							},
							color: 'white'
						}
					}]
				});
			});
		});";
	} else if ($tipoGrafico == "Column with negative values") {
		$arrayClave = explode(",",$data1);
		foreach ($arrayClave as $indice => $valor) {
			$arrayDetClave = NULL;
			$arrayDetClave = explode("=*=", $arrayClave[$indice]);
			
			$arrayMes = explode("-*-", $arrayDetClave[1]);
			$arrayCategories = NULL;
			$arrayData = NULL;
			foreach ($arrayMes as $indice2 => $valor2) {
				$arrayDetMes = NULL;
				$arrayDetMes = explode("+*+", $arrayMes[$indice2]);
				
				$arrayCategories[] = $arrayDetMes[0];
				$arrayData[] = $arrayDetMes[1];
			}
			
			$arrayDataEquipo[] = "{
				name: '".$arrayDetClave[0]."',
				data: [".implode(",",$arrayData)."]
			}";
		}
		
		$data1 = "
		highchartsDarkBlue();
		
		$(function () {
			var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					chart: {
						renderTo: 'tdGrafico',
						type: 'column'
					},
					title: {
						text: '".$tituloVentana."'
					},
					xAxis: {
						categories: ['".implode("','",$arrayCategories)."']
					},
					credits: {
						enabled: false
					},
					series: [".implode(",", $arrayDataEquipo)."]
				});
			});
		});";
	} else if ($tipoGrafico == "Basic column") {
		$arrayClave = explode(",",$data1);
		foreach ($arrayClave as $indice => $valor) {
			$arrayDetClave = NULL;
			$arrayDetClave = explode("=*=", $arrayClave[$indice]);
			
			$arrayMes = explode("-*-", $arrayDetClave[1]);
			$arrayCategories = NULL;
			$arrayData = NULL;
			foreach ($arrayMes as $indice2 => $valor2) {
				$arrayDetMes = NULL;
				$arrayDetMes = explode("+*+", $arrayMes[$indice2]);
				
				$arrayCategories[] = $arrayDetMes[0];
				$arrayData[] = $arrayDetMes[1];
			}
			
			$arrayDataEquipo[] = "{
				name: '".$arrayDetClave[0]."',
				data: [".implode(",",$arrayData)."]
			}";
		}
		
		$data1 = "
		highchartsDarkBlue();
		
		$(function () {
			var chart;
			$(document).ready(function() {
				chart = new Highcharts.Chart({
					chart: {
						renderTo: 'tdGrafico',
						type: 'column'
					},
					title: {
						text: '".$tituloVentana."'
					},
					xAxis: {
						categories: ['".implode("','",$arrayCategories)."']
					},
					yAxis: {
						min: 0,
						title: {
							text: '".$abrevMonedaLocal."'
						}
					},
					legend: {
						layout: 'vertical',
						backgroundColor: '#FFFFFF',
						align: 'left',
						verticalAlign: 'top',
						x: 100,
						y: 70,
						floating: true,
						shadow: true
					},
					tooltip: {
						formatter: function() {
							var num = this.y;
							num += '';
							var splitStr = num.split('.');
							var splitLeft = splitStr[0];
							var splitRight = splitStr.length > 1 ? '.' + splitStr[1] : '';
							var regx = /(\d+)(\d{3})/;
							while (regx.test(splitLeft)) {
								splitLeft = splitLeft.replace(regx, '$1' + ',' + '$2');
							}
							return '' + this.x + ': ' + splitLeft + splitRight + ' ".$abrevMonedaLocal."';
						}
					},
					plotOptions: {
						column: {
							pointPadding: 0.2,
							borderWidth: 0
						}
					},
					series: [".implode(",", $arrayDataEquipo)."]
				});
			});
		});";
	}
	
	$objResponse->script($data1);
	
	$objResponse->assign("tdFlotanteTitulo1","innerHTML",$tituloVentana);
	
	return $objResponse;
}

function imprimirReporteVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha']);
	if ($frmBuscar['lstDecimalPDF'] == 2 or $frmBuscar['lstDecimalPDF'] == 3 ) {

		$objResponse->script(sprintf("verVentana('reportes/if_reporte_venta_reconvercion_pdf.php?valBusq=%s&lstDecimalPDF=%s', 1000, 500);", $valBusq, $frmBuscar['lstDecimalPDF']));

		
	}else{


		$objResponse->script(sprintf("verVentana('reportes/if_reporte_venta_pdf.php?valBusq=%s&lstDecimalPDF=%s', 1000, 500);", $valBusq, $frmBuscar['lstDecimalPDF']));

	}

	
	$objResponse->assign("tdlstDecimalPDF","innerHTML","");
	
	return $objResponse;
}
function reporteVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	global $mes;

	$idEmpresa = $frmBuscar['lstEmpresa'];
	$valFecha[0] = date("m", strtotime("01-".$frmBuscar['txtFecha']));
	$valFecha[1] = date("Y", strtotime("01-".$frmBuscar['txtFecha']));
	
	$htmlMsj = "<table width=\"100%\">";
	$htmlMsj .= "<tr>";
		$htmlMsj .= "<td>";
			$htmlMsj .= "<p style=\"font-size:24px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; text-shadow:3px 3px 0 rgba(51,51,51,0.8);\">";
				$htmlMsj .= "<span style=\"display:inline-block; text-transform:uppercase; color:#38A6F0; padding-left:2px;\">".$mes[intval($valFecha[0])]." ".$valFecha[1]."</span>";
				/*$htmlMsj .= "<br>";
				$htmlMsj .= "<span style=\"font-size:18px; display:inline-block; text-transform:uppercase; color:#B7D154; padding-left:2px;\">Versión 3.0</span>";*/
			$htmlMsj .= "</p>";
		$htmlMsj .= "</td>";
	$htmlMsj .= "</tr>";
	$htmlMsj .= "</table>";
	
	$objResponse->assign("divMsjCierre","innerHTML",$htmlMsj);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// RESUMEN MENSUAL
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayMov = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// RESUMEN MENSUAL
	$queryVentaMensual = sprintf("SELECT
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact.idFactura
		END)) AS nro_unidades_vendidas,
		
		COUNT(cxc_fact.idFactura) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact_det_vehic.precio_unitario
		END)) AS monto_facturado_vehiculo,
		
		SUM(cxc_fact_det_vehic.precio_unitario) AS monto_facturado_vehiculo_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud) %s
		AND cxc_fact.fechaRegistroFactura <= '2018-08-20'
	GROUP BY uni_fis.id_condicion_unidad 
	
	UNION
	
	SELECT 
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc.idNotaCredito
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(cxc_nc.idNotaCredito)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc_det_vehic.precio_unitario
		END))) AS monto_facturado_vehiculo,
		
		((-1) * SUM(cxc_nc_det_vehic.precio_unitario)) AS monto_facturado_vehiculo_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA') %s
		AND cxc_nc.fechaNotaCredito <= '2018-08-20'
	GROUP BY uni_fis.id_condicion_unidad;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);

	//$objResponse->alert($queryVentaMensual);
	$rsVentaMensual = mysql_query($queryVentaMensual);
	if (!$rsVentaMensual) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaMensual = mysql_fetch_assoc($rsVentaMensual)) {
		$existe = false;
		$arrayDetalleMensual = NULL;
		if (isset($arrayVentaMensual)) {
			foreach ($arrayVentaMensual as $indice => $valor) {
				if ($arrayVentaMensual[$indice]['id_condicion_unidad'] == $rowVentaMensual['id_condicion_unidad']) {
					$existe = true;
					
					$arrayVentaMensual[$indice]['nro_unidades_vendidas'] += round($rowVentaMensual['nro_unidades_vendidas'],2);
					$arrayVentaMensual[$indice]['nro_unidades_vendidas_acumulado'] += round($rowVentaMensual['nro_unidades_vendidas_acumulado'],2);
					$arrayVentaMensual[$indice]['monto_facturado_vehiculo'] += round($rowVentaMensual['monto_facturado_vehiculo'],2);
					$arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado'] += round($rowVentaMensual['monto_facturado_vehiculo_acumulado'],2);
				}
			}
		}
		
		if ($existe == false) {
			$arrayVentaMensual[] = array(
				"id_condicion_unidad" => $rowVentaMensual['id_condicion_unidad'],
				"condicion_unidad" => $rowVentaMensual['condicion_unidad'],
				"nro_unidades_vendidas" => round($rowVentaMensual['nro_unidades_vendidas'],2),
				"nro_unidades_vendidas_acumulado" => round($rowVentaMensual['nro_unidades_vendidas_acumulado'],2),
				"monto_facturado_vehiculo" => round($rowVentaMensual['monto_facturado_vehiculo'],2),
				"monto_facturado_vehiculo_acumulado" => round($rowVentaMensual['monto_facturado_vehiculo_acumulado'],2));
		}
		
		$arrayTotalVentaMensual['nro_unidades_vendidas'] += round($rowVentaMensual['nro_unidades_vendidas'],2);
		$arrayTotalVentaMensual['nro_unidades_vendidas_acumulado'] += round($rowVentaMensual['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaMensual['monto_facturado_vehiculo'] += round($rowVentaMensual['monto_facturado_vehiculo'],2);
		$arrayTotalVentaMensual['monto_facturado_vehiculo_acumulado'] += round($rowVentaMensual['monto_facturado_vehiculo_acumulado'],2);
	}

	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td colspan=\"2\">Cant. Vehículos</td>";
		$htmlTh .= "<td colspan=\"2\">Facturación</td>";
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\">Conceptos</td>";
		$htmlTh .= "<td width=\"15%\">"."Cant. Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"15%\">"."Cant. Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"15%\">"."Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"15%\">"."Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// RESUMEN MENSUAL
	$contFila = 0;
	if (isset($arrayVentaMensual)) {
		foreach ($arrayVentaMensual as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcParticipacion = ($arrayTotalVentaMensual['nro_unidades_vendidas_acumulado'] > 0) ? (($arrayVentaMensual[$indice]['nro_unidades_vendidas_acumulado'] * 100) / $arrayTotalVentaMensual['nro_unidades_vendidas_acumulado']) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayVentaMensual[$indice]['condicion_unidad'])."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMensual[$indice]['nro_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMensual[$indice]['nro_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMensual[$indice]['monto_facturado_vehiculo'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalVentaMensual['porcentaje_participacion'] += $porcParticipacion;
			
			$arrayClave[] = (count($arrayClave) > 0) ? "['".utf8_encode($arrayVentaMensual[$indice]['condicion_unidad'])."', ".$arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado']."]" : "{ name: '".utf8_encode($arrayVentaMensual[$indice]['condicion_unidad'])."', y: ".$arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado'].", sliced: true, selected: true }";
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	// TOTAL RESUMEN MENSUAL
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL RESUMEN MENSUAL:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaMensual['nro_unidades_vendidas'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaMensual['nro_unidades_vendidas_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaMensual['monto_facturado_vehiculo'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaMensual['monto_facturado_vehiculo_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaMensual['porcentaje_participacion'], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">RESUMEN MENSUAL (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						1,
						"Pie with legend",
						"RESUMEN MENSUAL (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Monto Acumulado",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";

	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaResumenMensual","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ACCESORIOS INSTALADOS POR ASESOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.vexacc1 > 0");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.vexacc1 > 0
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// ACCESORIOS INSTALADOS POR ASESOR
	$queryVentaAccesorio = sprintf("SELECT
    	vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.activo,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				ped_vent.vexacc1
		END)) AS monto_facturado_accesorio,
		
		SUM(ped_vent.vexacc1) AS monto_facturado_accesorio_acumulado
	FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
		AND cxc_fact.fechaRegistroFactura <= '2018-08-20'
	GROUP BY vw_pg_empleado.id_empleado
	UNION
	
	SELECT 
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.activo,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				ped_vent.vexacc1
		END))) AS monto_facturado_accesorio,
		
		((-1) * SUM(ped_vent.vexacc1)) AS monto_facturado_accesorio_acumulado
	FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
		AND cxc_nc.fechaNotaCredito <= '2018-08-20'
	GROUP BY vw_pg_empleado.id_empleado
	
	ORDER BY 2;",
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaAccesorio = mysql_query($queryVentaAccesorio);
	if (!$rsVentaAccesorio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaAccesorio = mysql_fetch_assoc($rsVentaAccesorio)) {
		$existe = false;
		if (isset($arrayVentaAccesorio)) {
			foreach ($arrayVentaAccesorio as $indice => $valor) {
				if ($arrayVentaAccesorio[$indice]['nombre_empleado'] == $rowVentaAccesorio['nombre_empleado']) {
					$existe = true;
					
					$arrayVentaAccesorio[$indice]['monto_facturado_accesorio'] += round($rowVentaAccesorio['monto_facturado_accesorio'],2);
					$arrayVentaAccesorio[$indice]['monto_facturado_accesorio_acumulado'] += round($rowVentaAccesorio['monto_facturado_accesorio_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayVentaAccesorio[] = array(
				'nombre_empleado' => $rowVentaAccesorio['nombre_empleado'],
				'activo' => $rowVentaAccesorio['activo'],
				'monto_facturado_accesorio' => round($rowVentaAccesorio['monto_facturado_accesorio'],2),
				'monto_facturado_accesorio_acumulado' => round($rowVentaAccesorio['monto_facturado_accesorio_acumulado'],2));
		}
		
		$arrayTotalVentaAccesorio[1] += round($rowVentaAccesorio['monto_facturado_accesorio'],2);
		$arrayTotalVentaAccesorio[2] += round($rowVentaAccesorio['monto_facturado_accesorio_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"50%\">Asesor</td>";
		$htmlTh .= "<td width=\"20%\">Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"20%\">Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// ACCESORIOS INSTALADOS POR ASESOR
	$contFila = 0;
	if (isset($arrayVentaAccesorio)) {
		foreach ($arrayVentaAccesorio as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$classEmpleado = ($arrayVentaAccesorio[$indice]['activo'] == 1) ? "" : "class=\"textoRojoNegrita\"";
			
			$porcParticipacion = ($arrayTotalVentaAccesorio[2] > 0) ? (($arrayVentaAccesorio[$indice]['monto_facturado_accesorio_acumulado'] * 100) / $arrayTotalVentaAccesorio[2]) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\" ".$classEmpleado.">".utf8_encode($arrayVentaAccesorio[$indice]['nombre_empleado'])."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAccesorio[$indice]['monto_facturado_accesorio'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAccesorio[$indice]['monto_facturado_accesorio_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalVentaAccesorio[3] += $porcParticipacion;
			
			$arrayClave[] = (count($arrayClave) > 0) ? "['".utf8_encode($arrayVentaAccesorio[$indice]['nombre_empleado'])."', ".$arrayVentaAccesorio[$indice]['monto_facturado_accesorio_acumulado']."]" : "{ name: '".utf8_encode($arrayVentaAccesorio[$indice]['nombre_empleado'])."', y: ".$arrayVentaAccesorio[$indice]['monto_facturado_accesorio_acumulado'].", sliced: true, selected: true }";
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	// TOTAL ACCESORIOS INSTALADOS POR ASESOR
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL ACCESORIOS INSTALADOS POR ASESOR:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAccesorio[1], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAccesorio[2], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAccesorio[3], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"4\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">ACCESORIOS INSTALADOS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						2,
						"Pie with legend",
						"ACCESORIOS INSTALADOS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Monto Acumulado",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaResumenAccesorios","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ADICIONALES INSTALADOS POR ASESOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_fact_det_acc.id_tipo_accesorio IN (1,3)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc_det_acc.id_tipo_accesorio IN (1,3)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// ADICIONALES INSTALADOS POR ASESOR
	$queryVentaAdicional = sprintf("SELECT
		query.id_empleado,
		query.nombre_empleado,
		query.activo,
		query.id_accesorio,
		query.id_tipo_accesorio,
		query.nom_accesorio,
		SUM(query.cant_facturado_accesorio) AS cant_facturado_accesorio,
		SUM(query.cant_facturado_accesorio_acumulado) AS cant_facturado_accesorio_acumulado,
		SUM(query.monto_facturado_accesorio) AS monto_facturado_accesorio,
		SUM(query.monto_costo_facturado_accesorio) AS monto_costo_facturado_accesorio,
		SUM(query.monto_facturado_accesorio_acumulado) AS monto_facturado_accesorio_acumulado,
		SUM(query.monto_facturado_costo_accesorio_acumulado) AS monto_facturado_costo_accesorio_acumulado
	FROM (
		SELECT
			vw_pg_empleado.id_empleado,
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado.activo,
			acc.id_accesorio,
			acc.id_tipo_accesorio,
			acc.nom_accesorio,
			
			SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
				WHEN %s THEN
					cxc_fact_det_acc.cantidad
			END)) AS cant_facturado_accesorio,
			
			COUNT(cxc_fact_det_acc.cantidad) AS cant_facturado_accesorio_acumulado,
			
			SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
				WHEN %s THEN
					cxc_fact_det_acc.precio_unitario
			END)) AS monto_facturado_accesorio,
			
			SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
				WHEN %s THEN
					cxc_fact_det_acc.costo_compra
			END)) AS monto_costo_facturado_accesorio,
			
			SUM(cxc_fact_det_acc.precio_unitario) AS monto_facturado_accesorio_acumulado,
			SUM(cxc_fact_det_acc.costo_compra) AS monto_facturado_costo_accesorio_acumulado
		FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
			INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
			INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
			INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
			AND cxc_fact.fechaRegistroFactura <= '2018-08-20'
		GROUP BY vw_pg_empleado.id_empleado, cxc_fact_det_acc.id_accesorio
	
		UNION
		
		SELECT 
			vw_pg_empleado.id_empleado,
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado.activo,
			acc.id_accesorio,
			acc.id_tipo_accesorio,
			acc.nom_accesorio,
			
			((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
				WHEN %s THEN
					cxc_nc_det_acc.cantidad
			END))) AS cant_facturado_accesorio,
			
			((-1) * COUNT(cxc_nc_det_acc.cantidad)) AS cant_facturado_accesorio_acumulado,
			
			((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
				WHEN %s THEN
					cxc_nc_det_acc.precio_unitario
			END))) AS monto_facturado_accesorio,
			
			((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
				WHEN %s THEN
					cxc_nc_det_acc.costo_compra
			END))) AS monto_costo_facturado_accesorio,
			
			((-1) * SUM(cxc_nc_det_acc.precio_unitario)) AS monto_facturado_accesorio_acumulado,
			((-1) * SUM(cxc_nc_det_acc.costo_compra)) AS monto_facturado_costo_accesorio_acumulado
		FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
			INNER JOIN an_accesorio acc ON (cxc_nc_det_acc.id_accesorio = acc.id_accesorio)
			RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito)
			LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
			INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
			AND cxc_nc.fechaNotaCredito <= '2018-08-20'
		GROUP BY vw_pg_empleado.id_empleado, cxc_nc_det_acc.id_accesorio
		
		ORDER BY 2) AS query
	GROUP BY query.id_empleado, query.id_accesorio, query.id_tipo_accesorio;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaAdicional = mysql_query($queryVentaAdicional);
	if (!$rsVentaAdicional) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaAdicional = mysql_fetch_assoc($rsVentaAdicional)) {
		$existe = false;
		$arrayDetalleAdicional = NULL;
		if (isset($arrayVentaAdicional)) {
			foreach ($arrayVentaAdicional as $indice => $valor) {
				if ($arrayVentaAdicional[$indice]['id_empleado'] == $rowVentaAdicional['id_empleado']) {
					$existe = true;
					
					$existeAdicional = false;
					$arrayDetalleAdicional = NULL;
					if (isset($arrayVentaAdicional[$indice]['array_adicional'])) {
						foreach ($arrayVentaAdicional[$indice]['array_adicional'] as $indice2 => $valor2) {
							$arrayDetalleAdicional = $valor2;
							if ($arrayDetalleAdicional['id_accesorio'] == $rowVentaAdicional['id_accesorio']) {
								$existeAdicional = true;
								
								$arrayDetalleAdicional['cant_facturado_accesorio'] += round($rowVentaAdicional['cant_facturado_accesorio'],2);
								$arrayDetalleAdicional['cant_facturado_accesorio_acumulado'] += round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2);
								$arrayDetalleAdicional['monto_facturado_accesorio'] += round($rowVentaAdicional['monto_facturado_accesorio'],2);
								$arrayDetalleAdicional['monto_facturado_accesorio_acumulado'] += round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2);
							}
							
							$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['cant_facturado_accesorio'] = $arrayDetalleAdicional['cant_facturado_accesorio'];
							$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['cant_facturado_accesorio_acumulado'] = $arrayDetalleAdicional['cant_facturado_accesorio_acumulado'];
							$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['monto_facturado_accesorio'] = $arrayDetalleAdicional['monto_facturado_accesorio'];
							$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['monto_facturado_accesorio_acumulado'] = $arrayDetalleAdicional['monto_facturado_accesorio_acumulado'];
						}
					}
					
					if ($existeAdicional == false) {
						$arrayDetalleAdicional = array(
							"id_accesorio" => $rowVentaAdicional['id_accesorio'],
							"id_tipo_accesorio" => $rowVentaAdicional['id_tipo_accesorio'],
							"nom_accesorio" => $rowVentaAdicional['nom_accesorio'],
							"cant_facturado_accesorio" => round($rowVentaAdicional['cant_facturado_accesorio'],2),
							"cant_facturado_accesorio_acumulado" => round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2),
							"monto_facturado_accesorio" => round($rowVentaAdicional['monto_facturado_accesorio'],2),
							"monto_facturado_accesorio_acumulado" => round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2));
						
						$arrayVentaAdicional[$indice]['array_adicional'][] = $arrayDetalleAdicional;
					}
					
					$arrayVentaAdicional[$indice]['cant_facturado_accesorio'] += round($rowVentaAdicional['cant_facturado_accesorio'],2);
					$arrayVentaAdicional[$indice]['cant_facturado_accesorio_acumulado'] += round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2);
					$arrayVentaAdicional[$indice]['monto_facturado_accesorio'] += round($rowVentaAdicional['monto_facturado_accesorio'],2);
					$arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'] += round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayDetalleAdicional[] = array(
				"id_accesorio" => $rowVentaAdicional['id_accesorio'],
				"id_tipo_accesorio" => $rowVentaAdicional['id_tipo_accesorio'],
				"nom_accesorio" => $rowVentaAdicional['nom_accesorio'],
				"cant_facturado_accesorio" => round($rowVentaAdicional['cant_facturado_accesorio'],2),
				"cant_facturado_accesorio_acumulado" => round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2),
				"monto_facturado_accesorio" => round($rowVentaAdicional['monto_facturado_accesorio'],2),
				"monto_facturado_accesorio_acumulado" => round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2));
				
			$arrayVentaAdicional[] = array(
				"id_empleado" => $rowVentaAdicional['id_empleado'],
				"nombre_empleado" => $rowVentaAdicional['nombre_empleado'],
				"activo" => $rowVentaAdicional['activo'],
				"array_adicional" => $arrayDetalleAdicional,
				"cant_facturado_accesorio" => round($rowVentaAdicional['cant_facturado_accesorio'],2),
				"cant_facturado_accesorio_acumulado" => round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2),
				"monto_facturado_accesorio" => round($rowVentaAdicional['monto_facturado_accesorio'],2),
				"monto_facturado_accesorio_acumulado" => round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2));
		}
		
		$arrayTotalVentaAdicional['cant_facturado_accesorio'] += round($rowVentaAdicional['cant_facturado_accesorio'],2);
		$arrayTotalVentaAdicional['cant_facturado_accesorio_acumulado'] += round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2);
		$arrayTotalVentaAdicional['monto_facturado_accesorio'] += round($rowVentaAdicional['monto_facturado_accesorio'],2);
		$arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] += round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	// ADICIONALES INSTALADOS POR ASESOR
	$contFila = 0;
	$arrayEquipos = NULL;
	if (isset($arrayVentaAdicional)) {
		foreach ($arrayVentaAdicional as $indice => $valor) {
			
			$classEmpleado = ($arrayVentaAdicional[$indice]['activo'] == 1) ? "" : "class=\"textoRojoNegrita\"";
			
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td ".$classEmpleado." colspan=\"6\" title=\"".$arrayVentaAdicional[$indice]['id_empleado']."\">".utf8_encode($arrayVentaAdicional[$indice]['nombre_empleado'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"30%\">Adicional</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"10%\">%</td>";
			$htmlTb .= "</tr>";
			
			$arrayMec = NULL;
			if (isset($arrayVentaAdicional[$indice]['array_adicional'])) {
				foreach ($arrayVentaAdicional[$indice]['array_adicional'] as $indice2 => $valor2) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$porcParticipacion = ($arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] > 0) ? (($valor2['monto_facturado_accesorio_acumulado'] * 100) / $arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado']) : 0;
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
						$htmlTb .= "<td align=\"left\" title=\"".$valor2['id_accesorio']."\">".utf8_encode($valor2['nom_accesorio'])."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['cant_facturado_accesorio'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['cant_facturado_accesorio_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_accesorio'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_accesorio_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$arrayMec[] = implode("+*+",array(
						utf8_encode($valor2['nom_accesorio']),
						round($porcParticipacion,2)));
				}
			}
			
			$porcParticipacion = ($arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] > 0) ? (($arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'] * 100) / $arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado']) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td>Total Facturación ".utf8_encode($arrayVentaAdicional[$indice]['nombre_empleado']).":</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAdicional[$indice]['cant_facturado_accesorio'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAdicional[$indice]['cant_facturado_accesorio_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAdicional[$indice]['monto_facturado_accesorio'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayEquipos[] = implode("=*=",array(
				utf8_encode($arrayVentaAdicional[$indice]['nombre_empleado']),
				($arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] > 0) ? round($arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'] * 100 / $arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'],2) : 0,
				(count($arrayMec) > 0) ? implode("-*-",$arrayMec) : NULL));
			
			$arrayTotalVentaAdicional['porcentaje_participacion'] += $porcParticipacion;
		}
	}
	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
	
	// TOTAL ADICIONALES INSTALADOS POR ASESOR
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL ADICIONALES INSTALADOS POR ASESOR:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAdicional['cant_facturado_accesorio'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAdicional['cant_facturado_accesorio_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAdicional['monto_facturado_accesorio'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAdicional['porcentaje_participacion'], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">ADICIONALES INSTALADOS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						2,
						"Donut chart",
						"ADICIONALES INSTALADOS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Facturación Asesor",
						str_replace("'","|*|",$data1),
						"Facturación Accesorio",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaResumenAdicionales","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VENTAS POR ASESOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayMov = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// VENTAS POR ASESOR
	$queryVentaAsesor = sprintf("SELECT
    	vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.activo,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END)) AS nro_unidades_vendidas,
		
		COUNT(uni_fis.id_unidad_fisica) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact_det_vehic.precio_unitario
		END)) AS monto_facturado_vehiculo,
		
		SUM(cxc_fact_det_vehic.precio_unitario) AS monto_facturado_vehiculo_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
		AND cxc_fact.fechaRegistroFactura <= '2018-08-20'
	GROUP BY vw_pg_empleado.id_empleado, uni_fis.id_condicion_unidad

	UNION
	
	SELECT 
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.activo,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(uni_fis.id_unidad_fisica)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc_det_vehic.precio_unitario
		END))) AS monto_facturado_vehiculo,
		
		((-1) * SUM(cxc_nc_det_vehic.precio_unitario)) AS monto_facturado_vehiculo_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
		AND cxc_nc.fechaNotaCredito <= '2018-08-20'
	GROUP BY vw_pg_empleado.id_empleado, uni_fis.id_condicion_unidad
	
	ORDER BY 4,2;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaAsesor = mysql_query($queryVentaAsesor);
	if (!$rsVentaAsesor) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaAsesor = mysql_fetch_assoc($rsVentaAsesor)) {
		$existe = false;
		$arrayDetalleAsesor = NULL;
		if ($arrayVentaAsesor) {
			foreach ($arrayVentaAsesor as $indice => $valor) {
				if ($arrayVentaAsesor[$indice]['id_condicion_unidad'] == $rowVentaAsesor['id_condicion_unidad']) {
					$existe = true;
					
					$existeAsesor = false;
					$arrayDetalleAsesor = NULL;
					if (isset($arrayVentaAsesor[$indice]['array_asesor'])) {
						foreach ($arrayVentaAsesor[$indice]['array_asesor'] as $indice2 => $valor2) {
							$arrayDetalleAsesor = $valor2;
							if ($arrayDetalleAsesor['id_empleado'] == $rowVentaAsesor['id_empleado']) {
								$existeAsesor = true;
								
								$arrayDetalleAsesor['nro_unidades_vendidas'] += round($rowVentaAsesor['nro_unidades_vendidas'],2);
								$arrayDetalleAsesor['nro_unidades_vendidas_acumulado'] += round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2);
								$arrayDetalleAsesor['monto_facturado_vehiculo'] += round($rowVentaAsesor['monto_facturado_vehiculo'],2);
								$arrayDetalleAsesor['monto_facturado_vehiculo_acumulado'] += round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2);
							}
							
							$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['nro_unidades_vendidas'] = round($arrayDetalleAsesor['nro_unidades_vendidas'],2);
							$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['nro_unidades_vendidas_acumulado'] = round($arrayDetalleAsesor['nro_unidades_vendidas_acumulado'],2);
							$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['monto_facturado_vehiculo'] = round($arrayDetalleAsesor['monto_facturado_vehiculo'],2);
							$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['monto_facturado_vehiculo_acumulado'] = round($arrayDetalleAsesor['monto_facturado_vehiculo_acumulado'],2);
						}
					}
					
					if ($existeAsesor == false) {
						$arrayDetalleAsesor = array(
							"id_empleado" => $rowVentaAsesor['id_empleado'],
							"nombre_empleado" => $rowVentaAsesor['nombre_empleado'],
							"activo" => $rowVentaAsesor['activo'],
							"nro_unidades_vendidas" => round($rowVentaAsesor['nro_unidades_vendidas'],2),
							"nro_unidades_vendidas_acumulado" => round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2),
							"monto_facturado_vehiculo" => round($rowVentaAsesor['monto_facturado_vehiculo'],2),
							"monto_facturado_vehiculo_acumulado" => round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2));
						
						$arrayVentaAsesor[$indice]['array_asesor'][] = $arrayDetalleAsesor;
					}
					
					$arrayVentaAsesor[$indice]['nro_unidades_vendidas'] += round($rowVentaAsesor['nro_unidades_vendidas'],2);
					$arrayVentaAsesor[$indice]['nro_unidades_vendidas_acumulado'] += round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2);
					$arrayVentaAsesor[$indice]['monto_facturado_vehiculo'] += round($rowVentaAsesor['monto_facturado_vehiculo'],2);
					$arrayVentaAsesor[$indice]['monto_facturado_vehiculo_acumulado'] += round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayDetalleAsesor[] = array(
				"id_empleado" => $rowVentaAsesor['id_empleado'],
				"nombre_empleado" => $rowVentaAsesor['nombre_empleado'],
				"activo" => $rowVentaAsesor['activo'],
				"nro_unidades_vendidas" => round($rowVentaAsesor['nro_unidades_vendidas'],2),
				"nro_unidades_vendidas_acumulado" => round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2),
				"monto_facturado_vehiculo" => round($rowVentaAsesor['monto_facturado_vehiculo'],2),
				"monto_facturado_vehiculo_acumulado" => round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2));
			
			$arrayVentaAsesor[] = array(
				"id_condicion_unidad" => $rowVentaAsesor['id_condicion_unidad'],
				"condicion_unidad" => $rowVentaAsesor['condicion_unidad'],
				"array_asesor" => $arrayDetalleAsesor,
				"nro_unidades_vendidas" => round($rowVentaAsesor['nro_unidades_vendidas'],2),
				"nro_unidades_vendidas_acumulado" => round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2),
				"monto_facturado_vehiculo" => round($rowVentaAsesor['monto_facturado_vehiculo'],2),
				"monto_facturado_vehiculo_acumulado" => round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2));
		}
		
		$arrayTotalVentaAsesor['nro_unidades_vendidas'] += round($rowVentaAsesor['nro_unidades_vendidas'],2);
		$arrayTotalVentaAsesor['nro_unidades_vendidas_acumulado'] += round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaAsesor['monto_facturado_vehiculo'] += round($rowVentaAsesor['monto_facturado_vehiculo'],2);
		$arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'] += round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	// VENTAS POR ASESOR
	$contFila = 0;
	$arrayEquipos = NULL;
	if (isset($arrayVentaAsesor)) {
		foreach ($arrayVentaAsesor as $indice => $valor) {
			
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td colspan=\"6\" title=\"".$arrayVentaAsesor[$indice]['id_condicion_unidad']."\">".utf8_encode($arrayVentaAsesor[$indice]['condicion_unidad'])."</td>";
			$htmlTb .= "<tr align=\"center\" class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"30%\">"."Asesor"."</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"10%\">"."%"."</td>";
			$htmlTb .= "</tr>";
			
			$arrayMec = NULL;
			if (isset($arrayVentaAsesor[$indice]['array_asesor'])) {
				foreach ($arrayVentaAsesor[$indice]['array_asesor'] as $indice2 => $valor2) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$porcParticipacion = ($arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'] > 0) ? (($valor2['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado']) : 0;
					
					$classEmpleado = ($valor2['activo'] == 1) ? "" : "class=\"textoRojoNegrita\"";
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
						$htmlTb .= "<td align=\"left\" ".$classEmpleado." title=\"".$valor2['id_empleado']."\">".utf8_encode($valor2['nombre_empleado'])."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['nro_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['nro_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_vehiculo'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_vehiculo_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$arrayMec[] = implode("+*+",array(
						substr(utf8_encode($valor2['nombre_empleado']),0,50),
						round($porcParticipacion,2)));
				}
			}
			
			$porcParticipacion = ($arrayTotalVentaAsesor['monto_unidades_vendidas_acumulado'] > 0) ? (($arrayVentaAsesor[$indice]['monto_unidades_vendidas_acumulado'] * 100) / $arrayTotalVentaAsesor['monto_unidades_vendidas_acumulado']) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td>Total Facturación ".utf8_encode($arrayVentaAsesor[$indice]['condicion_unidad']).":</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['nro_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['nro_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['monto_facturado_vehiculo'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['monto_facturado_vehiculo_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayEquipos[] = implode("=*=",array(
				utf8_encode($arrayVentaAsesor[$indice]['condicion_unidad']),
				($arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'] > 0) ? round($arrayVentaAsesor[$indice]['monto_facturado_vehiculo_acumulado'] * 100 / $arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'],2) : 0,
				(count($arrayMec) > 0) ? implode("-*-",$arrayMec) : NULL));
			
			$arrayTotalVentaAsesor['porcentaje_participacion'] += $porcParticipacion;
		}
	}
	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
	
	// TOTAL VENTAS POR ASESOR
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL VENTAS POR ASESOR:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAsesor['nro_unidades_vendidas'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAsesor['nro_unidades_vendidas_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAsesor['monto_facturado_vehiculo'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAsesor['porcentaje_participacion'], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">VENTAS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						3,
						"Donut chart",
						"VENTAS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Facturación Condicion Unidad",
						str_replace("'","|*|",$data1),
						"Facturación Asesor",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaVentaAsesor","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VENTAS POR MODELO DE VEHÍCULO
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayMov = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// VENTAS POR MODELO DE VEHÍCULO
	$queryVentaModelo = sprintf("SELECT
		uni_bas.id_uni_bas,
		CONCAT(uni_bas.nom_uni_bas, ': ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END)) AS cant_unidades_vendidas,
		
		COUNT(uni_fis.id_unidad_fisica) AS cant_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact_det_vehic.precio_unitario
		END)) AS monto_unidades_vendidas,
		
		SUM(cxc_fact_det_vehic.precio_unitario) AS monto_unidades_vendidas_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano) %s
		AND cxc_fact.fechaRegistroFactura <= '2018-08-20'
	GROUP BY uni_bas.id_uni_bas, uni_fis.id_condicion_unidad
	
	UNION
	
	SELECT
		uni_bas.id_uni_bas,
		CONCAT(uni_bas.nom_uni_bas, ': ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END))) AS cant_unidades_vendidas,
		
		((-1) * COUNT(uni_fis.id_unidad_fisica)) AS cant_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc_det_vehic.precio_unitario
		END))) AS monto_unidades_vendidas,
		
		((-1) * SUM(cxc_nc_det_vehic.precio_unitario)) AS monto_unidades_vendidas_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano) %s
		AND cxc_nc.fechaNotaCredito <= '2018-08-20'
	GROUP BY uni_bas.id_uni_bas, uni_fis.id_condicion_unidad
	
	ORDER BY 4,2;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaModelo = mysql_query($queryVentaModelo);
	if (!$rsVentaModelo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaModelo = mysql_fetch_assoc($rsVentaModelo)) {
		$existe = false;
		$arrayDetalleModelo = NULL;
		if (isset($arrayVentaModelo)) {
			foreach ($arrayVentaModelo as $indice => $valor) {
				if ($arrayVentaModelo[$indice]['id_condicion_unidad'] == $rowVentaModelo['id_condicion_unidad']) {
					$existe = true;
					
					$existeModelo = false;
					$arrayDetalleModelo = NULL;
					if (isset($arrayVentaModelo[$indice]['array_modelo'])) {
						foreach ($arrayVentaModelo[$indice]['array_modelo'] as $indice2 => $valor2) {
							$arrayDetalleModelo = $valor2;
							if ($arrayDetalleModelo['id_uni_bas'] == $rowVentaModelo['id_uni_bas']) {
								$existeModelo = true;
								
								$arrayDetalleModelo['cant_unidades_vendidas'] += round($rowVentaModelo['cant_unidades_vendidas'],2);
								$arrayDetalleModelo['cant_unidades_vendidas_acumulado'] += round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2);
								$arrayDetalleModelo['monto_unidades_vendidas'] += round($rowVentaModelo['monto_unidades_vendidas'],2);
								$arrayDetalleModelo['monto_unidades_vendidas_acumulado'] += round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2);
							}
							
							$arrayVentaModelo[$indice]['array_modelo'][$indice2]['cant_unidades_vendidas'] = round($arrayDetalleModelo['cant_unidades_vendidas'],2);
							$arrayVentaModelo[$indice]['array_modelo'][$indice2]['cant_unidades_vendidas_acumulado'] = round($arrayDetalleModelo['cant_unidades_vendidas_acumulado'],2);
							$arrayVentaModelo[$indice]['array_modelo'][$indice2]['monto_unidades_vendidas'] = round($arrayDetalleModelo['monto_unidades_vendidas'],2);
							$arrayVentaModelo[$indice]['array_modelo'][$indice2]['monto_unidades_vendidas_acumulado'] = round($arrayDetalleModelo['monto_unidades_vendidas_acumulado'],2);
						}
					}
					
					if ($existeModelo == false) {
						$arrayDetalleModelo = array(
							"id_uni_bas" => $rowVentaModelo['id_uni_bas'],
							"vehiculo" => $rowVentaModelo['vehiculo'],
							"cant_unidades_vendidas" => round($rowVentaModelo['cant_unidades_vendidas'],2),
							"cant_unidades_vendidas_acumulado" => round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2),
							"monto_unidades_vendidas" => round($rowVentaModelo['monto_unidades_vendidas'],2),
							"monto_unidades_vendidas_acumulado" => round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2));
						
						$arrayVentaModelo[$indice]['array_modelo'][] = $arrayDetalleModelo;
					}
					
					$arrayVentaModelo[$indice]['cant_unidades_vendidas'] += round($rowVentaModelo['cant_unidades_vendidas'],2);
					$arrayVentaModelo[$indice]['cant_unidades_vendidas_acumulado'] += round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2);
					$arrayVentaModelo[$indice]['monto_unidades_vendidas'] += round($rowVentaModelo['monto_unidades_vendidas'],2);
					$arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'] += round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayDetalleModelo[] = array(
				"id_uni_bas" => $rowVentaModelo['id_uni_bas'],
				"vehiculo" => $rowVentaModelo['vehiculo'],
				"cant_unidades_vendidas" => round($rowVentaModelo['cant_unidades_vendidas'],2),
				"cant_unidades_vendidas_acumulado" => round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2),
				"monto_unidades_vendidas" => round($rowVentaModelo['monto_unidades_vendidas'],2),
				"monto_unidades_vendidas_acumulado" => round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2));
			
			$arrayVentaModelo[] = array(
				"id_condicion_unidad" => $rowVentaModelo['id_condicion_unidad'],
				"condicion_unidad" => $rowVentaModelo['condicion_unidad'],
				"array_modelo" => $arrayDetalleModelo,
				"cant_unidades_vendidas" => round($rowVentaModelo['cant_unidades_vendidas'],2),
				"cant_unidades_vendidas_acumulado" => round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2),
				"monto_unidades_vendidas" => round($rowVentaModelo['monto_unidades_vendidas'],2),
				"monto_unidades_vendidas_acumulado" => round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2));
		}
		
		$arrayTotalVentaModelo['cant_unidades_vendidas'] += round($rowVentaModelo['cant_unidades_vendidas'],2);
		$arrayTotalVentaModelo['cant_unidades_vendidas_acumulado'] += round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaModelo['monto_unidades_vendidas'] += round($rowVentaModelo['monto_unidades_vendidas'],2);
		$arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] += round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	// VENTAS POR MODELO DE VEHÍCULO
	$contFila = 0;
	$arrayEquipos = NULL;
	if (isset($arrayVentaModelo)) {
		foreach ($arrayVentaModelo as $indice => $valor) {
			
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td colspan=\"6\" title=\"".$arrayVentaModelo[$indice]['id_condicion_unidad']."\">".utf8_encode($arrayVentaModelo[$indice]['condicion_unidad'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"30%\">Modelo</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"10%\">%</td>";
			$htmlTb .= "</tr>";
			
			$arrayMec = NULL;
			if (isset($arrayVentaModelo[$indice]['array_modelo'])) {
				foreach ($arrayVentaModelo[$indice]['array_modelo'] as $indice2 => $valor2) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$porcParticipacion = ($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] > 0) ? (($valor2['monto_unidades_vendidas_acumulado'] * 100) / $arrayTotalVentaModelo['monto_unidades_vendidas_acumulado']) : 0;
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
						$htmlTb .= "<td align=\"left\" title=\"".$valor2['id_unidad_basica']."\">".utf8_encode($valor2['vehiculo'])."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['cant_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['cant_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$arrayMec[] = implode("+*+",array(
						substr(utf8_encode($valor2['vehiculo']),0,50),
						round($porcParticipacion,2)));
				}
			}
			
			$porcParticipacion = ($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] > 0) ? (($arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'] * 100) / $arrayTotalVentaModelo['monto_unidades_vendidas_acumulado']) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td>Total Facturación ".utf8_encode($arrayVentaModelo[$indice]['condicion_unidad']).":</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaModelo[$indice]['cant_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaModelo[$indice]['cant_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaModelo[$indice]['monto_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayEquipos[] = implode("=*=",array(
				utf8_encode($arrayVentaModelo[$indice]['condicion_unidad']),
				($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] > 0) ? round($arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'] * 100 / $arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'],2) : 0,
				(count($arrayMec) > 0) ? implode("-*-",$arrayMec) : NULL));
			
			$arrayTotalVentaModelo['porcentaje_participacion'] += $porcParticipacion;
		}
	}
	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
	
	// TOTAL VENTAS POR MODELO DE VEHÍCULO
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL VENTAS POR MODELO DE VEHÍCULO:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaModelo['cant_unidades_vendidas'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaModelo['cant_unidades_vendidas_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaModelo['monto_unidades_vendidas'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaModelo['porcentaje_participacion'], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">VENTAS POR MODELO DE VEHÍCULO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						4,
						"Donut chart",
						"VENTAS POR MODELO DE VEHÍCULO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Facturación Condicion Unidad",
						str_replace("'","|*|",$data1),
						"Facturación Modelo",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaDetalleModelo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// OPERACIONES A CRÉDITO
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.porcentaje_inicial < 100");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.porcentaje_inicial < 100
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// OPERACIONES A CRÉDITO
	$queryVentaCredito = sprintf("SELECT 
		banco.idBanco AS id_banco,
		banco.nombreBanco AS nombre_banco,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END)) AS nro_unidades_vendidas,
		
		COUNT(uni_fis.id_unidad_fisica) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				ped_vent.saldo_financiar
		END)) AS monto_financiado,
		
		SUM(ped_vent.saldo_financiar) AS monto_financiado_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
			AND cxc_fact.fechaRegistroFactura <= '2018-08-20'
	GROUP BY banco.idBanco
	
	UNION
	
	SELECT 
		banco.idBanco AS id_banco,
		banco.nombreBanco AS nombre_banco,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(uni_fis.id_unidad_fisica)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				ped_vent.saldo_financiar
		END))) AS monto_financiado,
		
		((-1) * SUM(ped_vent.saldo_financiar)) AS monto_financiado_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
		AND cxc_nc.fechaNotaCredito <= '2018-08-20'
	GROUP BY banco.idBanco
	
	ORDER BY 2;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaCredito = mysql_query($queryVentaCredito);
	if (!$rsVentaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaCredito = mysql_fetch_assoc($rsVentaCredito)) {
		$existe = false;
		if (isset($arrayVentaCredito)) {
			foreach ($arrayVentaCredito as $indice => $valor) {
				if ($arrayVentaCredito[$indice][0] == $rowVentaCredito['nombre_banco']) {
					$existe = true;
					
					$arrayVentaCredito[$indice][1] += round($rowVentaCredito['nro_unidades_vendidas'],2);
					$arrayVentaCredito[$indice][2] += round($rowVentaCredito['nro_unidades_vendidas_acumulado'],2);
					$arrayVentaCredito[$indice][3] += round($rowVentaCredito['monto_financiado'],2);
					$arrayVentaCredito[$indice][4] += round($rowVentaCredito['monto_financiado_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayVentaCredito[] = array(
				$rowVentaCredito['nombre_banco'],
				round($rowVentaCredito['nro_unidades_vendidas'],2),
				round($rowVentaCredito['nro_unidades_vendidas_acumulado'],2),
				round($rowVentaCredito['monto_financiado'],2),
				round($rowVentaCredito['monto_financiado_acumulado'],2));
		}
		
		$arrayTotalVentaCredito[1] += round($rowVentaCredito['nro_unidades_vendidas'],2);
		$arrayTotalVentaCredito[2] += round($rowVentaCredito['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaCredito[3] += round($rowVentaCredito['monto_financiado'],2);
		$arrayTotalVentaCredito[4] += round($rowVentaCredito['monto_financiado_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\">Banco</td>";
		$htmlTh .= "<td width=\"15%\">Cant. Mes</td>";
		$htmlTh .= "<td width=\"15%\">Cant. Acumulada</td>";
		$htmlTh .= "<td width=\"15%\">Monto Financiado Mes</td>";
		$htmlTh .= "<td width=\"15%\">Monto Financiado Acumulado</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// OPERACIONES A CRÉDITO
	$contFila = 0;
	if (isset($arrayVentaCredito)) {
		foreach ($arrayVentaCredito as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcParticipacion = ($arrayTotalVentaCredito[4] > 0) ? (($arrayVentaCredito[$indice][4] * 100) / $arrayTotalVentaCredito[4]) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".$arrayVentaCredito[$indice][0]."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaCredito[$indice][1], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaCredito[$indice][2], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaCredito[$indice][3], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaCredito[$indice][4], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalVentaCredito[5] += $porcParticipacion;
			
			$arrayClave[] = (count($arrayClave) > 0) ? "['".utf8_encode($arrayVentaCredito[$indice][0])."', ".$arrayVentaCredito[$indice][4]."]" : "{ name: '".utf8_encode($arrayVentaCredito[$indice][0])."', y: ".$arrayVentaCredito[$indice][4].", sliced: true, selected: true }";
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	// TOTAL OPERACIONES A CRÉDITO
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL OPERACIONES A CRÉDITO:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaCredito[1], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaCredito[2], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaCredito[3], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaCredito[4], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaCredito[5], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">OPERACIONES A CRÉDITO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						5,
						"Pie with legend",
						"OPERACIONES A CRÉDITO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Monto Financiado Acumulado",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaDetalleOperacionesCredito","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// OPERACIONES A CONTADO
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.porcentaje_inicial = 100");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.porcentaje_inicial = 100
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}

	// OPERACIONES A CONTADO
	$queryVentaContado = sprintf("SELECT 
		'Contado' AS nombre_banco,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END)) AS nro_unidades_vendidas,
		
		COUNT(uni_fis.id_unidad_fisica) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				ped_vent.inicial
		END)) AS monto_contado,
		
		SUM(ped_vent.inicial) AS monto_contado_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
		AND cxc_fact.fechaRegistroFactura <= '2018-08-20'
	
	UNION
	
	SELECT 
		'Contado' AS nombre_banco,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(uni_fis.id_unidad_fisica)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				ped_vent.inicial
		END))) AS monto_contado,
		
		((-1) * SUM(ped_vent.inicial)) AS monto_contado_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
		AND cxc_nc.fechaNotaCredito <= '2018-08-20'
	GROUP BY 1
	
	ORDER BY 2;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaContado = mysql_query($queryVentaContado);
	if (!$rsVentaContado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaContado = mysql_fetch_assoc($rsVentaContado)) {
		$existe = false;
		if (isset($arrayVentaContado)) {
			foreach ($arrayVentaContado as $indice => $valor) {
				if ($arrayVentaContado[$indice][0] == $rowVentaContado['nombre_banco']) {
					$existe = true;
					
					$arrayVentaContado[$indice][1] += round($rowVentaContado['nro_unidades_vendidas'],2);
					$arrayVentaContado[$indice][2] += round($rowVentaContado['nro_unidades_vendidas_acumulado'],2);
					$arrayVentaContado[$indice][3] += round($rowVentaContado['monto_contado'],2);
					$arrayVentaContado[$indice][4] += round($rowVentaContado['monto_contado_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayVentaContado[] = array(
				$rowVentaContado['nombre_banco'],
				round($rowVentaContado['nro_unidades_vendidas'],2),
				round($rowVentaContado['nro_unidades_vendidas_acumulado'],2),
				round($rowVentaContado['monto_contado'],2),
				round($rowVentaContado['monto_contado_acumulado'],2));
		}
		
		$arrayTotalVentaContado[1] += round($rowVentaContado['nro_unidades_vendidas'],2);
		$arrayTotalVentaContado[2] += round($rowVentaContado['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaContado[3] += round($rowVentaContado['monto_contado'],2);
		$arrayTotalVentaContado[4] += round($rowVentaContado['monto_contado_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\"></td>";
		$htmlTh .= "<td width=\"15%\">Cant. Mes</td>";
		$htmlTh .= "<td width=\"15%\">Cant. Acumulada</td>";
		$htmlTh .= "<td width=\"15%\">Monto Contado Mes</td>";
		$htmlTh .= "<td width=\"15%\">Monto Contado Acumulado</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// OPERACIONES A CONTADO
	$contFila = 0;
	if (isset($arrayVentaContado)) {
		foreach ($arrayVentaContado as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcParticipacion = ($arrayTotalVentaContado[4] > 0) ? (($arrayVentaContado[$indice][4] * 100) / $arrayTotalVentaContado[4]) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".$arrayVentaContado[$indice][0]."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaContado[$indice][1], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaContado[$indice][2], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaContado[$indice][3], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaContado[$indice][4], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalVentaContado[5] += $porcParticipacion;
			
			$arrayClave[] = (count($arrayClave) > 0) ? "['".utf8_encode($arrayVentaContado[$indice][0])."', ".$arrayVentaContado[$indice][4]."]" : "{ name: '".utf8_encode($arrayVentaContado[$indice][0])."', y: ".$arrayVentaContado[$indice][4].", sliced: true, selected: true }";
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	// TOTAL OPERACIONES A CONTADO
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL OPERACIONES A CONTADO:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaContado[1], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaContado[2], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaContado[3], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaContado[4], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaContado[5], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">OPERACIONES A CONTADO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						6,
						"Pie with legend",
						"OPERACIONES A CONTADO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Monto Contado Acumulado",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaDetalleOperacionesContado","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// COMPAÑIA DE SEGUROS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// COMPAÑIA DE SEGUROS
	$querySeguros = sprintf("SELECT 
		poliza.id_poliza,
		poliza.nombre_poliza,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				poliza.id_poliza
		END)) AS nro_unidades_vendidas,
		
		COUNT(poliza.id_poliza) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				ped_vent.monto_seguro
		END)) AS monto_asegurado,
		
		SUM(ped_vent.monto_seguro) AS monto_asegurado_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN an_poliza poliza ON (ped_vent.id_poliza = poliza.id_poliza) %s
		AND cxc_fact.fechaRegistroFactura <= '2018-08-20'
	GROUP BY poliza.id_poliza
	
	UNION
	
	SELECT 
		poliza.id_poliza,
		poliza.nombre_poliza,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				poliza.id_poliza
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(poliza.id_poliza)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				ped_vent.monto_seguro
		END))) AS monto_asegurado,
		
		((-1) * SUM(ped_vent.monto_seguro)) AS monto_asegurado_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN an_poliza poliza ON (ped_vent.id_poliza = poliza.id_poliza) %s
		AND cxc_nc.fechaNotaCredito <= '2018-08-20'
	GROUP BY poliza.id_poliza
	
	ORDER BY 2;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsSeguros = mysql_query($querySeguros);
	if (!$rsSeguros) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowSeguros = mysql_fetch_assoc($rsSeguros)) {
		$existe = false;
		if (isset($arraySeguros)) {
			foreach ($arraySeguros as $indice => $valor) {
				if ($arraySeguros[$indice][0] == $rowSeguros['nombre_poliza']) {
					$existe = true;
					
					$arraySeguros[$indice][1] += round($rowSeguros['nro_unidades_vendidas'],2);
					$arraySeguros[$indice][2] += round($rowSeguros['nro_unidades_vendidas_acumulado'],2);
					$arraySeguros[$indice][3] += round($rowSeguros['monto_asegurado'],2);
					$arraySeguros[$indice][4] += round($rowSeguros['monto_asegurado_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arraySeguros[] = array(
				$rowSeguros['nombre_poliza'],
				round($rowSeguros['nro_unidades_vendidas'],2),
				round($rowSeguros['nro_unidades_vendidas_acumulado'],2),
				round($rowSeguros['monto_asegurado'],2),
				round($rowSeguros['monto_asegurado_acumulado'],2));
		}
		
		$arrayTotalSeguros[1] += round($rowSeguros['nro_unidades_vendidas'],2);
		$arrayTotalSeguros[2] += round($rowSeguros['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalSeguros[3] += round($rowSeguros['monto_asegurado'],2);
		$arrayTotalSeguros[4] += round($rowSeguros['monto_asegurado_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\">".("Aseguradora")."</td>";
		$htmlTh .= "<td width=\"15%\">".("Cant. Mes")."</td>";
		$htmlTh .= "<td width=\"15%\">".("Cant. Acumulada")."</td>";
		$htmlTh .= "<td width=\"15%\">".("Monto Mes")."</td>";
		$htmlTh .= "<td width=\"15%\">".("Monto Acumulado")."</td>";
		$htmlTh .= "<td width=\"10%\">".("%")."</td>";
	$htmlTh .= "</tr>";
	
	// COMPAÑIA DE SEGUROS
	$contFila = 0;
	if (isset($arraySeguros)) {
		foreach ($arraySeguros as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcParticipacion = ($arrayTotalSeguros[4] > 0) ? (($arraySeguros[$indice][4] * 100) / $arrayTotalSeguros[4]) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".$arraySeguros[$indice][0]."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arraySeguros[$indice][1], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arraySeguros[$indice][2], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arraySeguros[$indice][3], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arraySeguros[$indice][4], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalSeguros[5] += $porcParticipacion;
			
			$arrayClave[] = (count($arrayClave) > 0) ? "['".utf8_encode($arraySeguros[$indice][0])."', ".$arraySeguros[$indice][4]."]" : "{ name: '".utf8_encode($arraySeguros[$indice][0])."', y: ".$arraySeguros[$indice][4].", sliced: true, selected: true }";
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	// TOTAL COMPAÑIA DE SEGUROS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL COMPAÑIA DE SEGUROS:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalSeguros[1], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalSeguros[2], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalSeguros[3], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalSeguros[4], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalSeguros[5], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">COMPAÑIA DE SEGUROS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						7,
						"Pie with legend",
						"COMPAÑIA DE SEGUROS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Monto Acumulado",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaDetalleSeguros","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VENTA POR EMPRESA
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayMov = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// VENTA POR EMPRESA
	$queryVentaEmpresa = sprintf("SELECT
		cxc_fact.id_empresa,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact.idFactura
		END)) AS nro_unidades_vendidas,
		
		COUNT(cxc_fact.idFactura) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact_det_vehic.precio_unitario
		END)) AS monto_facturado_vehiculo,
		
		SUM(cxc_fact_det_vehic.precio_unitario) AS monto_facturado_vehiculo_acumulado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
			AND cxc_fact.fechaRegistroFactura <= '2018-08-20'
	GROUP BY cxc_fact.id_empresa, uni_fis.id_condicion_unidad
	
	UNION
	
	SELECT 
		cxc_nc.id_empresa,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc.idNotaCredito
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(cxc_nc.idNotaCredito)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc_det_vehic.precio_unitario
		END))) AS monto_facturado_vehiculo,
		
		((-1) * SUM(cxc_nc_det_vehic.precio_unitario)) AS monto_facturado_vehiculo_acumulado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
		AND cxc_nc.fechaNotaCredito <= '2018-08-20'
	GROUP BY cxc_nc.id_empresa, uni_fis.id_condicion_unidad
	
	ORDER BY 3,8;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaEmpresa = mysql_query($queryVentaEmpresa);
	if (!$rsVentaEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayVentaEmpresa = array();
	while ($rowVentaEmpresa = mysql_fetch_assoc($rsVentaEmpresa)) {
		$existe = false;
		$arrayDetalleEmpresa = NULL;
		if (isset($arrayVentaEmpresa)) {
			foreach ($arrayVentaEmpresa as $indice => $valor) {
				if ($arrayVentaEmpresa[$indice]['id_condicion_unidad'] == $rowVentaEmpresa['id_condicion_unidad']) {
					$existe = true;
					
					$existeEmpresa = false;
					$arrayDetalleEmpresa = NULL;
					if (isset($arrayVentaEmpresa[$indice]['array_empresa'])) {
						foreach ($arrayVentaEmpresa[$indice]['array_empresa'] as $indice2 => $valor2) {
							$arrayDetalleEmpresa = $valor2;
							if ($arrayDetalleEmpresa['id_empresa'] == $rowVentaEmpresa['id_empresa']) {
								$existeEmpresa = true;
								
								$arrayDetalleEmpresa['nro_unidades_vendidas'] += round($rowVentaEmpresa['nro_unidades_vendidas'],2);
								$arrayDetalleEmpresa['nro_unidades_vendidas_acumulado'] += round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2);
								$arrayDetalleEmpresa['monto_facturado_vehiculo'] += round($rowVentaEmpresa['monto_facturado_vehiculo'],2);
								$arrayDetalleEmpresa['monto_facturado_vehiculo_acumulado'] += round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2);
							}
							
							$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['nro_unidades_vendidas'] = round($arrayDetalleEmpresa['nro_unidades_vendidas'],2);
							$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['nro_unidades_vendidas_acumulado'] = round($arrayDetalleEmpresa['nro_unidades_vendidas_acumulado'],2);
							$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['monto_facturado_vehiculo'] = round($arrayDetalleEmpresa['monto_facturado_vehiculo'],2);
							$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['monto_facturado_vehiculo_acumulado'] = round($arrayDetalleEmpresa['monto_facturado_vehiculo_acumulado'],2);
						}
					}
					
					if ($existeEmpresa == false) {
						$arrayDetalleEmpresa = array(
							"id_empresa" => $rowVentaEmpresa['id_empresa'],
							"nombre_empresa" => $rowVentaEmpresa['nombre_empresa'],
							"nro_unidades_vendidas" => round($rowVentaEmpresa['nro_unidades_vendidas'],2),
							"nro_unidades_vendidas_acumulado" => round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2),
							"monto_facturado_vehiculo" => round($rowVentaEmpresa['monto_facturado_vehiculo'],2),
							"monto_facturado_vehiculo_acumulado" => round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2));
						
						$arrayVentaEmpresa[$indice]['array_empresa'][] = $arrayDetalleEmpresa;
					}
					
					$arrayVentaEmpresa[$indice]['nro_unidades_vendidas'] += round($rowVentaEmpresa['nro_unidades_vendidas'],2);
					$arrayVentaEmpresa[$indice]['nro_unidades_vendidas_acumulado'] += round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2);
					$arrayVentaEmpresa[$indice]['monto_facturado_vehiculo'] += round($rowVentaEmpresa['monto_facturado_vehiculo'],2);
					$arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'] += round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayDetalleEmpresa[] = array(
				"id_empresa" => $rowVentaEmpresa['id_empresa'],
				"nombre_empresa" => $rowVentaEmpresa['nombre_empresa'],
				"nro_unidades_vendidas" => round($rowVentaEmpresa['nro_unidades_vendidas'],2),
				"nro_unidades_vendidas_acumulado" => round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2),
				"monto_facturado_vehiculo" => round($rowVentaEmpresa['monto_facturado_vehiculo'],2),
				"monto_facturado_vehiculo_acumulado" => round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2));
		
			$arrayVentaEmpresa[] = array(
				"id_condicion_unidad" => $rowVentaEmpresa['id_condicion_unidad'],
				"condicion_unidad" => $rowVentaEmpresa['condicion_unidad'],
				"array_empresa" => $arrayDetalleEmpresa,
				"nro_unidades_vendidas" => round($rowVentaEmpresa['nro_unidades_vendidas'],2),
				"nro_unidades_vendidas_acumulado" => round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2),
				"monto_facturado_vehiculo" => round($rowVentaEmpresa['monto_facturado_vehiculo'],2),
				"monto_facturado_vehiculo_acumulado" => round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2));
		}
		
		$arrayTotalVentaEmpresa['nro_unidades_vendidas'] += round($rowVentaEmpresa['nro_unidades_vendidas'],2);
		$arrayTotalVentaEmpresa['nro_unidades_vendidas_acumulado'] += round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaEmpresa['monto_facturado_vehiculo'] += round($rowVentaEmpresa['monto_facturado_vehiculo'],2);
		$arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] += round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2);
	}

	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	// VENTAS POR EMPRESA
	$contFila = 0;
	$arrayEquipos = NULL;
	if (isset($arrayVentaEmpresa)) {
		foreach ($arrayVentaEmpresa as $indice => $valor) {
			
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td colspan=\"6\" title=\"".$arrayVentaEmpresa[$indice]['id_condicion_unidad']."\">".utf8_encode($arrayVentaEmpresa[$indice]['condicion_unidad'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td colspan=\"2\">Cant. Vehículos</td>";
				$htmlTb .= "<td colspan=\"2\">Facturación</td>";
				$htmlTb .= "<td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"30%\">Empresa</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"10%\">%</td>";
			$htmlTb .= "</tr>";
			
			$arrayMec = NULL;
			if (isset($arrayVentaEmpresa[$indice]['array_empresa'])) {
				foreach ($arrayVentaEmpresa[$indice]['array_empresa'] as $indice2 => $valor2) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$porcParticipacion = ($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] > 0) ? (($valor2['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado']) : 0;
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
						$htmlTb .= "<td align=\"left\" title=\"".$valor2['id_empresa']."\">".utf8_encode($valor2['nombre_empresa'])."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['nro_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['nro_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_vehiculo'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_vehiculo_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$arrayMec[] = implode("+*+",array(
						substr(utf8_encode($valor2['nombre_empresa']),0,50),
						round($porcParticipacion,2)));
				}
			}
			
			$porcParticipacion = ($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] > 0) ? (($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado']) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td>Total Facturación ".utf8_encode($arrayVentaEmpresa[$indice]['condicion_unidad']).":</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaEmpresa[$indice]['nro_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaEmpresa[$indice]['nro_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayEquipos[] = implode("=*=",array(
				utf8_encode($arrayVentaEmpresa[$indice]['condicion_unidad']),
				($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] > 0) ? round($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'] * 100 / $arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'],2) : 0,
				(count($arrayMec) > 0) ? implode("-*-",$arrayMec) : NULL));
			
			$arrayTotalVentaEmpresa['porcentaje_participacion'] += $porcParticipacion;
		}
	}
	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
	
	// TOTAL VENTAS POR EMPRESA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL VENTAS POR EMPRESA:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaEmpresa['nro_unidades_vendidas'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaEmpresa['nro_unidades_vendidas_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaEmpresa['monto_facturado_vehiculo'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaEmpresa['porcentaje_participacion'], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
			
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">VENTA POR EMPRESA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						1,
						"Donut chart",
						"VENTAS POR EMPRESA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Facturación Condicion Unidad",
						str_replace("'","|*|",$data1),
						"Facturación Empresa",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";

	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaVentaEmpresa","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	$objResponse->script("
	byId('tblMsj').style.display = 'none';
	byId('tblInforme').style.display = '';");

	return $objResponse;
}
function reporteVentareconvercion($frmBuscar){
	$objResponse = new xajaxResponse();
	
	global $mes;

	$idEmpresa = $frmBuscar['lstEmpresa'];
	$valFecha[0] = date("m", strtotime("01-".$frmBuscar['txtFecha']));
	$valFecha[1] = date("Y", strtotime("01-".$frmBuscar['txtFecha']));
	
	$htmlMsj = "<table width=\"100%\">";
	$htmlMsj .= "<tr>";
		$htmlMsj .= "<td>";
			$htmlMsj .= "<p style=\"font-size:24px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; text-shadow:3px 3px 0 rgba(51,51,51,0.8);\">";
				$htmlMsj .= "<span style=\"display:inline-block; text-transform:uppercase; color:#38A6F0; padding-left:2px;\">".$mes[intval($valFecha[0])]." ".$valFecha[1]."</span>";
				/*$htmlMsj .= "<br>";
				$htmlMsj .= "<span style=\"font-size:18px; display:inline-block; text-transform:uppercase; color:#B7D154; padding-left:2px;\">Versión 3.0</span>";*/
			$htmlMsj .= "</p>";
		$htmlMsj .= "</td>";
	$htmlMsj .= "</tr>";
	$htmlMsj .= "</table>";
	
	$objResponse->assign("divMsjCierre","innerHTML",$htmlMsj);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// RESUMEN MENSUAL
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayMov = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// RESUMEN MENSUAL
	$queryVentaMensual = sprintf("SELECT
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact.idFactura
		END)) AS nro_unidades_vendidas,
		
		COUNT(cxc_fact.idFactura) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				cxc_fact_det_vehic.precio_unitario / 100000, 
				cxc_fact_det_vehic.precio_unitario
			)
		END)) AS monto_facturado_vehiculo,
		
		SUM(if (
			cxc_fact.fechaRegistroFactura <= '2018-08-20', 
			cxc_fact_det_vehic.precio_unitario / 100000, 
			cxc_fact_det_vehic.precio_unitario
		)) AS monto_facturado_vehiculo_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud) %s
	GROUP BY uni_fis.id_condicion_unidad
	
	UNION
	
	SELECT 
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc.idNotaCredito
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(cxc_nc.idNotaCredito)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
			IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				cxc_nc_det_vehic.precio_unitario / 100000, 
				cxc_nc_det_vehic.precio_unitario
			)
		END))) AS monto_facturado_vehiculo,
		
		((-1) * SUM( if (
			cxc_nc.fechaNotaCredito <= '2018-08-20', 
			cxc_nc_det_vehic.precio_unitario / 100000, 
			cxc_nc_det_vehic.precio_unitario
		))) AS monto_facturado_vehiculo_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA') %s
	GROUP BY uni_fis.id_condicion_unidad;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaMensual = mysql_query($queryVentaMensual);
	if (!$rsVentaMensual) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaMensual = mysql_fetch_assoc($rsVentaMensual)) {
		$existe = false;
		$arrayDetalleMensual = NULL;
		if (isset($arrayVentaMensual)) {
			foreach ($arrayVentaMensual as $indice => $valor) {
				if ($arrayVentaMensual[$indice]['id_condicion_unidad'] == $rowVentaMensual['id_condicion_unidad']) {
					$existe = true;
					
					$arrayVentaMensual[$indice]['nro_unidades_vendidas'] += round($rowVentaMensual['nro_unidades_vendidas'],2);
					$arrayVentaMensual[$indice]['nro_unidades_vendidas_acumulado'] += round($rowVentaMensual['nro_unidades_vendidas_acumulado'],2);
					$arrayVentaMensual[$indice]['monto_facturado_vehiculo'] += round($rowVentaMensual['monto_facturado_vehiculo'],2);
					$arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado'] += round($rowVentaMensual['monto_facturado_vehiculo_acumulado'],2);
				}
			}
		}
		
		if ($existe == false) {
			$arrayVentaMensual[] = array(
				"id_condicion_unidad" => $rowVentaMensual['id_condicion_unidad'],
				"condicion_unidad" => $rowVentaMensual['condicion_unidad'],
				"nro_unidades_vendidas" => round($rowVentaMensual['nro_unidades_vendidas'],2),
				"nro_unidades_vendidas_acumulado" => round($rowVentaMensual['nro_unidades_vendidas_acumulado'],2),
				"monto_facturado_vehiculo" => round($rowVentaMensual['monto_facturado_vehiculo'],2),
				"monto_facturado_vehiculo_acumulado" => round($rowVentaMensual['monto_facturado_vehiculo_acumulado'],2));
		}
		
		$arrayTotalVentaMensual['nro_unidades_vendidas'] += round($rowVentaMensual['nro_unidades_vendidas'],2);
		$arrayTotalVentaMensual['nro_unidades_vendidas_acumulado'] += round($rowVentaMensual['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaMensual['monto_facturado_vehiculo'] += round($rowVentaMensual['monto_facturado_vehiculo'],2);
		$arrayTotalVentaMensual['monto_facturado_vehiculo_acumulado'] += round($rowVentaMensual['monto_facturado_vehiculo_acumulado'],2);
	}

	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= "<td colspan=\"2\">Cant. Vehículos</td>";
		$htmlTh .= "<td colspan=\"2\">Facturación</td>";
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\">Conceptos</td>";
		$htmlTh .= "<td width=\"15%\">"."Cant. Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"15%\">"."Cant. Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"15%\">"."Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"15%\">"."Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// RESUMEN MENSUAL
	$contFila = 0;
	if (isset($arrayVentaMensual)) {
		foreach ($arrayVentaMensual as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcParticipacion = ($arrayTotalVentaMensual['nro_unidades_vendidas_acumulado'] > 0) ? (($arrayVentaMensual[$indice]['nro_unidades_vendidas_acumulado'] * 100) / $arrayTotalVentaMensual['nro_unidades_vendidas_acumulado']) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayVentaMensual[$indice]['condicion_unidad'])."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMensual[$indice]['nro_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMensual[$indice]['nro_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMensual[$indice]['monto_facturado_vehiculo'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalVentaMensual['porcentaje_participacion'] += $porcParticipacion;
			
			$arrayClave[] = (count($arrayClave) > 0) ? "['".utf8_encode($arrayVentaMensual[$indice]['condicion_unidad'])."', ".$arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado']."]" : "{ name: '".utf8_encode($arrayVentaMensual[$indice]['condicion_unidad'])."', y: ".$arrayVentaMensual[$indice]['monto_facturado_vehiculo_acumulado'].", sliced: true, selected: true }";
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	// TOTAL RESUMEN MENSUAL
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL RESUMEN MENSUAL:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaMensual['nro_unidades_vendidas'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaMensual['nro_unidades_vendidas_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaMensual['monto_facturado_vehiculo'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaMensual['monto_facturado_vehiculo_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaMensual['porcentaje_participacion'], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">RESUMEN MENSUAL (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						1,
						"Pie with legend",
						"RESUMEN MENSUAL (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Monto Acumulado",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";

	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaResumenMensual","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ACCESORIOS INSTALADOS POR ASESOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.vexacc1 > 0");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.vexacc1 > 0
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// ACCESORIOS INSTALADOS POR ASESOR
	$queryVentaAccesorio = sprintf("SELECT
    	vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.activo,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN if (
			cxc_fact.fechaRegistroFactura <= '2018-08-20', 
			ped_vent.vexacc1 / 100000, 
			ped_vent.vexacc1
		)
		END)) AS monto_facturado_accesorio,
		
		SUM(if (
			cxc_fact.fechaRegistroFactura <= '2018-08-20', 
			ped_vent.vexacc1 / 100000, 
			ped_vent.vexacc1
		)) AS monto_facturado_accesorio_acumulado
	FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
		INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
	GROUP BY vw_pg_empleado.id_empleado

	UNION
	
	SELECT 
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.activo,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN if (
			cxc_nc.fechaNotaCredito<= '2018-08-20', 
			ped_vent.vexacc1 / 100000, 
			ped_vent.vexacc1
		)
		END))) AS monto_facturado_accesorio,
		
		((-1) * SUM(if (
			cxc_nc.fechaNotaCredito<= '2018-08-20', 
			ped_vent.vexacc1 / 100000, 
			ped_vent.vexacc1
		))) AS monto_facturado_accesorio_acumulado
	FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
	GROUP BY vw_pg_empleado.id_empleado
	
	ORDER BY 2;",
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaAccesorio = mysql_query($queryVentaAccesorio);
	if (!$rsVentaAccesorio) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaAccesorio = mysql_fetch_assoc($rsVentaAccesorio)) {
		$existe = false;
		if (isset($arrayVentaAccesorio)) {
			foreach ($arrayVentaAccesorio as $indice => $valor) {
				if ($arrayVentaAccesorio[$indice]['nombre_empleado'] == $rowVentaAccesorio['nombre_empleado']) {
					$existe = true;
					
					$arrayVentaAccesorio[$indice]['monto_facturado_accesorio'] += round($rowVentaAccesorio['monto_facturado_accesorio'],2);
					$arrayVentaAccesorio[$indice]['monto_facturado_accesorio_acumulado'] += round($rowVentaAccesorio['monto_facturado_accesorio_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayVentaAccesorio[] = array(
				'nombre_empleado' => $rowVentaAccesorio['nombre_empleado'],
				'activo' => $rowVentaAccesorio['activo'],
				'monto_facturado_accesorio' => round($rowVentaAccesorio['monto_facturado_accesorio'],2),
				'monto_facturado_accesorio_acumulado' => round($rowVentaAccesorio['monto_facturado_accesorio_acumulado'],2));
		}
		
		$arrayTotalVentaAccesorio[1] += round($rowVentaAccesorio['monto_facturado_accesorio'],2);
		$arrayTotalVentaAccesorio[2] += round($rowVentaAccesorio['monto_facturado_accesorio_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"50%\">Asesor</td>";
		$htmlTh .= "<td width=\"20%\">Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"20%\">Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// ACCESORIOS INSTALADOS POR ASESOR
	$contFila = 0;
	if (isset($arrayVentaAccesorio)) {
		foreach ($arrayVentaAccesorio as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$classEmpleado = ($arrayVentaAccesorio[$indice]['activo'] == 1) ? "" : "class=\"textoRojoNegrita\"";
			
			$porcParticipacion = ($arrayTotalVentaAccesorio[2] > 0) ? (($arrayVentaAccesorio[$indice]['monto_facturado_accesorio_acumulado'] * 100) / $arrayTotalVentaAccesorio[2]) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\" ".$classEmpleado.">".utf8_encode($arrayVentaAccesorio[$indice]['nombre_empleado'])."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAccesorio[$indice]['monto_facturado_accesorio'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAccesorio[$indice]['monto_facturado_accesorio_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalVentaAccesorio[3] += $porcParticipacion;
			
			$arrayClave[] = (count($arrayClave) > 0) ? "['".utf8_encode($arrayVentaAccesorio[$indice]['nombre_empleado'])."', ".$arrayVentaAccesorio[$indice]['monto_facturado_accesorio_acumulado']."]" : "{ name: '".utf8_encode($arrayVentaAccesorio[$indice]['nombre_empleado'])."', y: ".$arrayVentaAccesorio[$indice]['monto_facturado_accesorio_acumulado'].", sliced: true, selected: true }";
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	// TOTAL ACCESORIOS INSTALADOS POR ASESOR
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL ACCESORIOS INSTALADOS POR ASESOR:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAccesorio[1], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAccesorio[2], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAccesorio[3], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"4\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">ACCESORIOS INSTALADOS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						2,
						"Pie with legend",
						"ACCESORIOS INSTALADOS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Monto Acumulado",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaResumenAccesorios","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ADICIONALES INSTALADOS POR ASESOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_fact_det_acc.id_tipo_accesorio IN (1,3)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc_det_acc.id_tipo_accesorio IN (1,3)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// ADICIONALES INSTALADOS POR ASESOR
	$queryVentaAdicional = sprintf("SELECT
		query.id_empleado,
		query.nombre_empleado,
		query.activo,
		query.id_accesorio,
		query.id_tipo_accesorio,
		query.nom_accesorio,
		query.fecha,
		SUM(query.cant_facturado_accesorio) AS cant_facturado_accesorio,
		SUM(query.cant_facturado_accesorio_acumulado) AS cant_facturado_accesorio_acumulado,
		SUM(query.monto_facturado_accesorio) AS monto_facturado_accesorio,
		SUM(query.monto_costo_facturado_accesorio) AS monto_costo_facturado_accesorio,
		SUM(query.monto_facturado_accesorio_acumulado) AS monto_facturado_accesorio_acumulado,
		SUM(query.monto_facturado_costo_accesorio_acumulado) AS monto_facturado_costo_accesorio_acumulado
	FROM (
		SELECT
			vw_pg_empleado.id_empleado,
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado.activo,
			acc.id_accesorio,
			acc.id_tipo_accesorio,
			acc.nom_accesorio,
			cxc_fact.fechaRegistroFactura as fecha,
			
			SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
				WHEN %s THEN
					cxc_fact_det_acc.cantidad
			END)) AS cant_facturado_accesorio,
			
			COUNT(cxc_fact_det_acc.cantidad) AS cant_facturado_accesorio_acumulado,
			
			SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
				WHEN %s THEN
					cxc_fact_det_acc.precio_unitario
			END)) AS monto_facturado_accesorio,
			
			SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
				WHEN %s THEN
					cxc_fact_det_acc.costo_compra
			END)) AS monto_costo_facturado_accesorio,
			
			SUM(cxc_fact_det_acc.precio_unitario) AS monto_facturado_accesorio_acumulado,
			SUM(cxc_fact_det_acc.costo_compra) AS monto_facturado_costo_accesorio_acumulado
		FROM cj_cc_factura_detalle_accesorios cxc_fact_det_acc
			INNER JOIN an_accesorio acc ON (cxc_fact_det_acc.id_accesorio = acc.id_accesorio)
			INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_fact_det_acc.id_factura = cxc_fact.idFactura)
			INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
		GROUP BY vw_pg_empleado.id_empleado, cxc_fact_det_acc.id_accesorio
	
		UNION
		
		SELECT 
			vw_pg_empleado.id_empleado,
			vw_pg_empleado.nombre_empleado,
			vw_pg_empleado.activo,
			acc.id_accesorio,
			acc.id_tipo_accesorio,
			acc.nom_accesorio,
			cxc_nc.fechaNotaCredito as fecha,
			
			((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
				WHEN %s THEN
					cxc_nc_det_acc.cantidad
			END))) AS cant_facturado_accesorio,
			
			((-1) * COUNT(cxc_nc_det_acc.cantidad)) AS cant_facturado_accesorio_acumulado,
			
			((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
				WHEN %s THEN
					cxc_nc_det_acc.precio_unitario
			END))) AS monto_facturado_accesorio,
			
			((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
				WHEN %s THEN
					cxc_nc_det_acc.costo_compra
			END))) AS monto_costo_facturado_accesorio,
			
			((-1) * SUM(cxc_nc_det_acc.precio_unitario)) AS monto_facturado_accesorio_acumulado,
			((-1) * SUM(cxc_nc_det_acc.costo_compra)) AS monto_facturado_costo_accesorio_acumulado
		FROM cj_cc_nota_credito_detalle_accesorios cxc_nc_det_acc
			INNER JOIN an_accesorio acc ON (cxc_nc_det_acc.id_accesorio = acc.id_accesorio)
			RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_acc.id_nota_credito = cxc_nc.idNotaCredito)
			LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
			INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
			INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
		GROUP BY vw_pg_empleado.id_empleado, cxc_nc_det_acc.id_accesorio
		
		ORDER BY 2) AS query
	GROUP BY query.id_empleado, query.id_accesorio, query.id_tipo_accesorio;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaAdicional = mysql_query($queryVentaAdicional);
	if (!$rsVentaAdicional) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaAdicional = mysql_fetch_assoc($rsVentaAdicional)) {
		$existe = false;
		$arrayDetalleAdicional = NULL;
		if (isset($arrayVentaAdicional)) {
			foreach ($arrayVentaAdicional as $indice => $valor) {
				if ($arrayVentaAdicional[$indice]['id_empleado'] == $rowVentaAdicional['id_empleado']) {
					$existe = true;
					
					$existeAdicional = false;
					$arrayDetalleAdicional = NULL;
					if (isset($arrayVentaAdicional[$indice]['array_adicional'])) {
						foreach ($arrayVentaAdicional[$indice]['array_adicional'] as $indice2 => $valor2) {
							$arrayDetalleAdicional = $valor2;
							if ($arrayDetalleAdicional['id_accesorio'] == $rowVentaAdicional['id_accesorio']) {
								$existeAdicional = true;
								
								$arrayDetalleAdicional['cant_facturado_accesorio'] += round($rowVentaAdicional['cant_facturado_accesorio'],2);
								$arrayDetalleAdicional['cant_facturado_accesorio_acumulado'] += round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2);
								$arrayDetalleAdicional['monto_facturado_accesorio'] += round($rowVentaAdicional['monto_facturado_accesorio'],2);
								$arrayDetalleAdicional['monto_facturado_accesorio_acumulado'] += round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2);
							}
							
							$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['cant_facturado_accesorio'] = $arrayDetalleAdicional['cant_facturado_accesorio'];
							$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['cant_facturado_accesorio_acumulado'] = $arrayDetalleAdicional['cant_facturado_accesorio_acumulado'];
							$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['monto_facturado_accesorio'] = $arrayDetalleAdicional['monto_facturado_accesorio'];
							$arrayVentaAdicional[$indice]['array_adicional'][$indice2]['monto_facturado_accesorio_acumulado'] = $arrayDetalleAdicional['monto_facturado_accesorio_acumulado'];
						}
					}
					
					if ($existeAdicional == false) {
						$arrayDetalleAdicional = array(
							"id_accesorio" => $rowVentaAdicional['id_accesorio'],
							"id_tipo_accesorio" => $rowVentaAdicional['id_tipo_accesorio'],
							"fecha" => $rowVentaAdicional['fecha'],
							"nom_accesorio" => $rowVentaAdicional['nom_accesorio'],
							"cant_facturado_accesorio" => round($rowVentaAdicional['cant_facturado_accesorio'],2),
							"cant_facturado_accesorio_acumulado" => round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2),
							"monto_facturado_accesorio" => round($rowVentaAdicional['monto_facturado_accesorio'],2),
							"monto_facturado_accesorio_acumulado" => round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2));
						
						$arrayVentaAdicional[$indice]['array_adicional'][] = $arrayDetalleAdicional;
					}
					
					$arrayVentaAdicional[$indice]['cant_facturado_accesorio'] += round($rowVentaAdicional['cant_facturado_accesorio'],2);
					$arrayVentaAdicional[$indice]['cant_facturado_accesorio_acumulado'] += round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2);
					$arrayVentaAdicional[$indice]['monto_facturado_accesorio'] += round($rowVentaAdicional['monto_facturado_accesorio'],2);
					$arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'] += round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayDetalleAdicional[] = array(
				"id_accesorio" => $rowVentaAdicional['id_accesorio'],
				"id_tipo_accesorio" => $rowVentaAdicional['id_tipo_accesorio'],
				"fecha" => $rowVentaAdicional['fecha'],
				"nom_accesorio" => $rowVentaAdicional['nom_accesorio'],
				"cant_facturado_accesorio" => round($rowVentaAdicional['cant_facturado_accesorio'],2),
				"cant_facturado_accesorio_acumulado" => round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2),
				"monto_facturado_accesorio" => round($rowVentaAdicional['monto_facturado_accesorio'],2),
				"monto_facturado_accesorio_acumulado" => round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2));
				
			$arrayVentaAdicional[] = array(
				"id_empleado" => $rowVentaAdicional['id_empleado'],
				"nombre_empleado" => $rowVentaAdicional['nombre_empleado'],
				"activo" => $rowVentaAdicional['activo'],
				"array_adicional" => $arrayDetalleAdicional,
				"cant_facturado_accesorio" => round($rowVentaAdicional['cant_facturado_accesorio'],2),
				"cant_facturado_accesorio_acumulado" => round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2),
				"monto_facturado_accesorio" => round($rowVentaAdicional['monto_facturado_accesorio'],2),
				"monto_facturado_accesorio_acumulado" => round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2));
		}
		
		$arrayTotalVentaAdicional['cant_facturado_accesorio'] += round($rowVentaAdicional['cant_facturado_accesorio'],2);
		$arrayTotalVentaAdicional['cant_facturado_accesorio_acumulado'] += round($rowVentaAdicional['cant_facturado_accesorio_acumulado'],2);
		$arrayTotalVentaAdicional['monto_facturado_accesorio'] += round($rowVentaAdicional['monto_facturado_accesorio'],2);
		$arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] += round($rowVentaAdicional['monto_facturado_accesorio_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	// ADICIONALES INSTALADOS POR ASESOR
	$contFila = 0;
	$arrayEquipos = NULL;
	if (isset($arrayVentaAdicional)) {
		foreach ($arrayVentaAdicional as $indice => $valor) {
			
			$classEmpleado = ($arrayVentaAdicional[$indice]['activo'] == 1) ? "" : "class=\"textoRojoNegrita\"";
			
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td ".$classEmpleado." colspan=\"6\" title=\"".$arrayVentaAdicional[$indice]['id_empleado']."\">".utf8_encode($arrayVentaAdicional[$indice]['nombre_empleado'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"30%\">Adicional</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"10%\">%</td>";
			$htmlTb .= "</tr>";
			
			$arrayMec = NULL;
			if (isset($arrayVentaAdicional[$indice]['array_adicional'])) {
				foreach ($arrayVentaAdicional[$indice]['array_adicional'] as $indice2 => $valor2) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$porcParticipacion = ($arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] > 0) ? (($valor2['monto_facturado_accesorio_acumulado'] * 100) / $arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado']) : 0;
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
						$htmlTb .= "<td align=\"left\" title=\"".$valor2['id_accesorio']."\">".utf8_encode($valor2['nom_accesorio'])."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['cant_facturado_accesorio'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['cant_facturado_accesorio_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
							if ($valor2['fecha'] <= '2018-08-20') {

								$monto_facturado_accesorio = $valor2['monto_facturado_accesorio']/100000;
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_accesorio']/100000, 2, ".", ","),"cero_por_vacio")."</td>";

								$monto_facturado_accesorio_acumulado = $valor2['monto_facturado_accesorio_acumulado']/100000;
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_accesorio_acumulado']/100000, 2, ".", ","),"cero_por_vacio")."</td>";

								
							
							}else{
								$monto_facturado_accesorio = $valor2['monto_facturado_accesorio'];
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_accesorio'], 2, ".", ","),"cero_por_vacio")."</td>";
								$monto_facturado_accesorio_acumulado = $valor2['monto_facturado_accesorio_acumulado'];

						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_accesorio_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";

								
							}
						$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$arrayMec[] = implode("+*+",array(
						utf8_encode($valor2['nom_accesorio']),
						round($porcParticipacion,2)));
					$totalmonto_facturado_accesorio += $monto_facturado_accesorio;
				$totalmonto_facturado_accesorio_acumulado += $monto_facturado_accesorio_acumulado;

				}
				
			}
			
			$porcParticipacion = ($arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] > 0) ? (($arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'] * 100) / $arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado']) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td>Total Facturación ".utf8_encode($arrayVentaAdicional[$indice]['nombre_empleado']).":</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAdicional[$indice]['cant_facturado_accesorio'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAdicional[$indice]['cant_facturado_accesorio_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($totalmonto_facturado_accesorio, 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($totalmonto_facturado_accesorio_acumulado, 2, ".", ","),"cero_por_vacio")."</td>";
				$total1 += $totalmonto_facturado_accesorio;
				$total2 += $totalmonto_facturado_accesorio_acumulado;
					$totalmonto_facturado_accesorio = 0;
				$totalmonto_facturado_accesorio_acumulado = 0;

				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayEquipos[] = implode("=*=",array(
				utf8_encode($arrayVentaAdicional[$indice]['nombre_empleado']),
				($arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'] > 0) ? round($arrayVentaAdicional[$indice]['monto_facturado_accesorio_acumulado'] * 100 / $arrayTotalVentaAdicional['monto_facturado_accesorio_acumulado'],2) : 0,
				(count($arrayMec) > 0) ? implode("-*-",$arrayMec) : NULL));
			
			$arrayTotalVentaAdicional['porcentaje_participacion'] += $porcParticipacion;
		}
	}

	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
	
	// TOTAL ADICIONALES INSTALADOS POR ASESOR
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL ADICIONALES INSTALADOS POR ASESOR:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAdicional['cant_facturado_accesorio'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAdicional['cant_facturado_accesorio_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($total1, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($total2, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAdicional['porcentaje_participacion'], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">ADICIONALES INSTALADOS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						2,
						"Donut chart",
						"ADICIONALES INSTALADOS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Facturación Asesor",
						str_replace("'","|*|",$data1),
						"Facturación Accesorio",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaResumenAdicionales","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VENTAS POR ASESOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayMov = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// VENTAS POR ASESOR
	$queryVentaAsesor = sprintf("SELECT
    	vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.activo,
		uni_fis.id_condicion_unidad,
		cxc_fact.fechaRegistroFactura as fecha,
		cond_unidad.descripcion AS condicion_unidad,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END)) AS nro_unidades_vendidas,
		
		COUNT(uni_fis.id_unidad_fisica) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact_det_vehic.precio_unitario
		END)) AS monto_facturado_vehiculo,
		
		SUM(cxc_fact_det_vehic.precio_unitario) AS monto_facturado_vehiculo_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
	GROUP BY vw_pg_empleado.id_empleado, uni_fis.id_condicion_unidad

	UNION
	
	SELECT 
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.activo,
		uni_fis.id_condicion_unidad,
		cxc_nc.fechaNotaCredito as fecha,
		cond_unidad.descripcion AS condicion_unidad,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(uni_fis.id_unidad_fisica)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc_det_vehic.precio_unitario
		END))) AS monto_facturado_vehiculo,
		
		((-1) * SUM(cxc_nc_det_vehic.precio_unitario)) AS monto_facturado_vehiculo_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN vw_pg_empleados vw_pg_empleado ON (cxc_fact.idVendedor = vw_pg_empleado.id_empleado) %s
	GROUP BY vw_pg_empleado.id_empleado, uni_fis.id_condicion_unidad
	
	ORDER BY 4,2;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaAsesor = mysql_query($queryVentaAsesor);
	if (!$rsVentaAsesor) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaAsesor = mysql_fetch_assoc($rsVentaAsesor)) {
		$existe = false;
		$arrayDetalleAsesor = NULL;
		if ($arrayVentaAsesor) {
			foreach ($arrayVentaAsesor as $indice => $valor) {
				if ($arrayVentaAsesor[$indice]['id_condicion_unidad'] == $rowVentaAsesor['id_condicion_unidad']) {
					$existe = true;
					
					$existeAsesor = false;
					$arrayDetalleAsesor = NULL;
					if (isset($arrayVentaAsesor[$indice]['array_asesor'])) {
						foreach ($arrayVentaAsesor[$indice]['array_asesor'] as $indice2 => $valor2) {
							$arrayDetalleAsesor = $valor2;
							if ($arrayDetalleAsesor['id_empleado'] == $rowVentaAsesor['id_empleado']) {
								$existeAsesor = true;
								
								$arrayDetalleAsesor['nro_unidades_vendidas'] += round($rowVentaAsesor['nro_unidades_vendidas'],2);
								$arrayDetalleAsesor['nro_unidades_vendidas_acumulado'] += round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2);
								$arrayDetalleAsesor['monto_facturado_vehiculo'] += round($rowVentaAsesor['monto_facturado_vehiculo'],2);
								$arrayDetalleAsesor['monto_facturado_vehiculo_acumulado'] += round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2);
							}
							
							$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['nro_unidades_vendidas'] = round($arrayDetalleAsesor['nro_unidades_vendidas'],2);
							$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['nro_unidades_vendidas_acumulado'] = round($arrayDetalleAsesor['nro_unidades_vendidas_acumulado'],2);
							$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['monto_facturado_vehiculo'] = round($arrayDetalleAsesor['monto_facturado_vehiculo'],2);
							$arrayVentaAsesor[$indice]['array_asesor'][$indice2]['monto_facturado_vehiculo_acumulado'] = round($arrayDetalleAsesor['monto_facturado_vehiculo_acumulado'],2);
						}
					}
					
					if ($existeAsesor == false) {
						$arrayDetalleAsesor = array(
							"id_empleado" => $rowVentaAsesor['id_empleado'],
							"fecha" => $rowVentaAsesor['fecha'],
							"nombre_empleado" => $rowVentaAsesor['nombre_empleado'],
							"activo" => $rowVentaAsesor['activo'],
							"nro_unidades_vendidas" => round($rowVentaAsesor['nro_unidades_vendidas'],2),
							"nro_unidades_vendidas_acumulado" => round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2),
							"monto_facturado_vehiculo" => round($rowVentaAsesor['monto_facturado_vehiculo'],2),
							"monto_facturado_vehiculo_acumulado" => round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2));
						
						$arrayVentaAsesor[$indice]['array_asesor'][] = $arrayDetalleAsesor;
					}
					
					$arrayVentaAsesor[$indice]['nro_unidades_vendidas'] += round($rowVentaAsesor['nro_unidades_vendidas'],2);
					$arrayVentaAsesor[$indice]['nro_unidades_vendidas_acumulado'] += round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2);
					$arrayVentaAsesor[$indice]['monto_facturado_vehiculo'] += round($rowVentaAsesor['monto_facturado_vehiculo'],2);
					$arrayVentaAsesor[$indice]['monto_facturado_vehiculo_acumulado'] += round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayDetalleAsesor[] = array(
				"id_empleado" => $rowVentaAsesor['id_empleado'],
				"fecha" => $rowVentaAsesor['fecha'],
				"nombre_empleado" => $rowVentaAsesor['nombre_empleado'],
				"activo" => $rowVentaAsesor['activo'],
				"nro_unidades_vendidas" => round($rowVentaAsesor['nro_unidades_vendidas'],2),
				"nro_unidades_vendidas_acumulado" => round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2),
				"monto_facturado_vehiculo" => round($rowVentaAsesor['monto_facturado_vehiculo'],2),
				"monto_facturado_vehiculo_acumulado" => round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2));
			
			$arrayVentaAsesor[] = array(
				"fecha" => $rowVentaAsesor['fecha'],
				"id_condicion_unidad" => $rowVentaAsesor['id_condicion_unidad'],
				"condicion_unidad" => $rowVentaAsesor['condicion_unidad'],
				"array_asesor" => $arrayDetalleAsesor,
				"nro_unidades_vendidas" => round($rowVentaAsesor['nro_unidades_vendidas'],2),
				"nro_unidades_vendidas_acumulado" => round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2),
				"monto_facturado_vehiculo" => round($rowVentaAsesor['monto_facturado_vehiculo'],2),
				"monto_facturado_vehiculo_acumulado" => round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2));
		}
		
		$arrayTotalVentaAsesor['nro_unidades_vendidas'] += round($rowVentaAsesor['nro_unidades_vendidas'],2);
		$arrayTotalVentaAsesor['nro_unidades_vendidas_acumulado'] += round($rowVentaAsesor['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaAsesor['monto_facturado_vehiculo'] += round($rowVentaAsesor['monto_facturado_vehiculo'],2);
		$arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'] += round($rowVentaAsesor['monto_facturado_vehiculo_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	// VENTAS POR ASESOR
	$contFila = 0;
	$arrayEquipos = NULL;
	if (isset($arrayVentaAsesor)) {
		foreach ($arrayVentaAsesor as $indice => $valor) {
			
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td colspan=\"6\" title=\"".$arrayVentaAsesor[$indice]['id_condicion_unidad']."\">".utf8_encode($arrayVentaAsesor[$indice]['condicion_unidad'])."</td>";
			$htmlTb .= "<tr align=\"center\" class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"30%\">"."Asesor"."</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"10%\">"."%"."</td>";
			$htmlTb .= "</tr>";
			
			$arrayMec = NULL;
			if (isset($arrayVentaAsesor[$indice]['array_asesor'])) {
				foreach ($arrayVentaAsesor[$indice]['array_asesor'] as $indice2 => $valor2) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$porcParticipacion = ($arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'] > 0) ? (($valor2['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado']) : 0;
					
					$classEmpleado = ($valor2['activo'] == 1) ? "" : "class=\"textoRojoNegrita\"";
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
						$htmlTb .= "<td align=\"left\" ".$classEmpleado." title=\"".$valor2['id_empleado']."\">".utf8_encode($valor2['nombre_empleado'])."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['nro_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";

						$htmlTb .= "<td>".valTpDato(number_format($valor2['nro_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";


						if ($valor2['fecha'] <= '2018-08-20') {

						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_vehiculo']/100000, 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_vehiculo_acumulado']/100000, 2, ".", ","),"cero_por_vacio")."</td>";
								$monto_facturado_vehiculo = $valor2['monto_facturado_vehiculo']/100000;
								$monto_facturado_vehiculo_acumulado = $valor2['monto_facturado_vehiculo_acumulado']/100000;

								
							
							}else{

								$monto_facturado_vehiculo = $valor2['monto_facturado_vehiculo'];
								$monto_facturado_vehiculo_acumulado = $valor2['monto_facturado_vehiculo_acumulado'];
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_vehiculo'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_vehiculo_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
								
							}
						
						$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$arrayMec[] = implode("+*+",array(
						substr(utf8_encode($valor2['nombre_empleado']),0,50),
						round($porcParticipacion,2)));
					$totalmonto_facturado_vehiculo *= $monto_facturado_vehiculo;
				$totalmonto_facturado_vehiculo_acumulado += $monto_facturado_vehiculo_acumulado;
				}

				
			}
			
			$porcParticipacion = ($arrayTotalVentaAsesor['monto_unidades_vendidas_acumulado'] > 0) ? (($arrayVentaAsesor[$indice]['monto_unidades_vendidas_acumulado'] * 100) / $arrayTotalVentaAsesor['monto_unidades_vendidas_acumulado']) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td>Total Facturación ".utf8_encode($arrayVentaAsesor[$indice]['condicion_unidad']).":</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['nro_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaAsesor[$indice]['nro_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($totalmonto_facturado_vehiculo, 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($totalmonto_facturado_vehiculo_acumulado, 2, ".", ","),"cero_por_vacio")."</td>";
				
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayEquipos[] = implode("=*=",array(
				utf8_encode($arrayVentaAsesor[$indice]['condicion_unidad']),
				($arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'] > 0) ? round($arrayVentaAsesor[$indice]['monto_facturado_vehiculo_acumulado'] * 100 / $arrayTotalVentaAsesor['monto_facturado_vehiculo_acumulado'],2) : 0,
				(count($arrayMec) > 0) ? implode("-*-",$arrayMec) : NULL));
			
			$arrayTotalVentaAsesor['porcentaje_participacion'] += $porcParticipacion;
			$total3 += $totalmonto_facturado_vehiculo;
				$total4 += $totalmonto_facturado_vehiculo_acumulado;
		}

	}
	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
	
	// TOTAL VENTAS POR ASESOR
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL VENTAS POR ASESOR:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAsesor['nro_unidades_vendidas'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAsesor['nro_unidades_vendidas_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($total3, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($total4, 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaAsesor['porcentaje_participacion'], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">VENTAS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						3,
						"Donut chart",
						"VENTAS POR ASESOR (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Facturación Condicion Unidad",
						str_replace("'","|*|",$data1),
						"Facturación Asesor",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaVentaAsesor","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VENTAS POR MODELO DE VEHÍCULO
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayMov = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// VENTAS POR MODELO DE VEHÍCULO
	$queryVentaModelo = sprintf("SELECT
		uni_bas.id_uni_bas,
		CONCAT(uni_bas.nom_uni_bas, ': ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END)) AS cant_unidades_vendidas,
		
		COUNT(uni_fis.id_unidad_fisica) AS cant_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				cxc_fact_det_vehic.precio_unitario / 100000, 
				cxc_fact_det_vehic.precio_unitario
			)
		END)) AS monto_unidades_vendidas,
		
		SUM(IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				cxc_fact_det_vehic.precio_unitario / 100000, 
				cxc_fact_det_vehic.precio_unitario
			)) AS monto_unidades_vendidas_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano) %s
	GROUP BY uni_bas.id_uni_bas, uni_fis.id_condicion_unidad
	
	UNION
	
	SELECT
		uni_bas.id_uni_bas,
		CONCAT(uni_bas.nom_uni_bas, ': ', modelo.nom_modelo, ' - ', vers.nom_version) AS vehiculo,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END))) AS cant_unidades_vendidas,
		
		((-1) * COUNT(uni_fis.id_unidad_fisica)) AS cant_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				cxc_nc_det_vehic.precio_unitario /100000, 
				cxc_nc_det_vehic.precio_unitario
			)
		END))) AS monto_unidades_vendidas,
		
		((-1) * SUM(IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				cxc_nc_det_vehic.precio_unitario /100000, 
				cxc_nc_det_vehic.precio_unitario
			))) AS monto_unidades_vendidas_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN an_uni_bas uni_bas ON (uni_fis.id_uni_bas = uni_bas.id_uni_bas)
		INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
		INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
		INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_ano ano ON (uni_fis.ano = ano.id_ano) %s
	GROUP BY uni_bas.id_uni_bas, uni_fis.id_condicion_unidad
	
	ORDER BY 4,2;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaModelo = mysql_query($queryVentaModelo);
	if (!$rsVentaModelo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaModelo = mysql_fetch_assoc($rsVentaModelo)) {
		$existe = false;
		$arrayDetalleModelo = NULL;
		if (isset($arrayVentaModelo)) {
			foreach ($arrayVentaModelo as $indice => $valor) {
				if ($arrayVentaModelo[$indice]['id_condicion_unidad'] == $rowVentaModelo['id_condicion_unidad']) {
					$existe = true;
					
					$existeModelo = false;
					$arrayDetalleModelo = NULL;
					if (isset($arrayVentaModelo[$indice]['array_modelo'])) {
						foreach ($arrayVentaModelo[$indice]['array_modelo'] as $indice2 => $valor2) {
							$arrayDetalleModelo = $valor2;
							if ($arrayDetalleModelo['id_uni_bas'] == $rowVentaModelo['id_uni_bas']) {
								$existeModelo = true;
								
								$arrayDetalleModelo['cant_unidades_vendidas'] += round($rowVentaModelo['cant_unidades_vendidas'],2);
								$arrayDetalleModelo['cant_unidades_vendidas_acumulado'] += round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2);
								$arrayDetalleModelo['monto_unidades_vendidas'] += round($rowVentaModelo['monto_unidades_vendidas'],2);
								$arrayDetalleModelo['monto_unidades_vendidas_acumulado'] += round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2);
							}
							
							$arrayVentaModelo[$indice]['array_modelo'][$indice2]['cant_unidades_vendidas'] = round($arrayDetalleModelo['cant_unidades_vendidas'],2);
							$arrayVentaModelo[$indice]['array_modelo'][$indice2]['cant_unidades_vendidas_acumulado'] = round($arrayDetalleModelo['cant_unidades_vendidas_acumulado'],2);
							$arrayVentaModelo[$indice]['array_modelo'][$indice2]['monto_unidades_vendidas'] = round($arrayDetalleModelo['monto_unidades_vendidas'],2);
							$arrayVentaModelo[$indice]['array_modelo'][$indice2]['monto_unidades_vendidas_acumulado'] = round($arrayDetalleModelo['monto_unidades_vendidas_acumulado'],2);
						}
					}
					
					if ($existeModelo == false) {
						$arrayDetalleModelo = array(
							"id_uni_bas" => $rowVentaModelo['id_uni_bas'],
							"vehiculo" => $rowVentaModelo['vehiculo'],
							"cant_unidades_vendidas" => round($rowVentaModelo['cant_unidades_vendidas'],2),
							"cant_unidades_vendidas_acumulado" => round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2),
							"monto_unidades_vendidas" => round($rowVentaModelo['monto_unidades_vendidas'],2),
							"monto_unidades_vendidas_acumulado" => round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2));
						
						$arrayVentaModelo[$indice]['array_modelo'][] = $arrayDetalleModelo;
					}
					
					$arrayVentaModelo[$indice]['cant_unidades_vendidas'] += round($rowVentaModelo['cant_unidades_vendidas'],2);
					$arrayVentaModelo[$indice]['cant_unidades_vendidas_acumulado'] += round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2);
					$arrayVentaModelo[$indice]['monto_unidades_vendidas'] += round($rowVentaModelo['monto_unidades_vendidas'],2);
					$arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'] += round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayDetalleModelo[] = array(
				"id_uni_bas" => $rowVentaModelo['id_uni_bas'],
				"vehiculo" => $rowVentaModelo['vehiculo'],
				"cant_unidades_vendidas" => round($rowVentaModelo['cant_unidades_vendidas'],2),
				"cant_unidades_vendidas_acumulado" => round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2),
				"monto_unidades_vendidas" => round($rowVentaModelo['monto_unidades_vendidas'],2),
				"monto_unidades_vendidas_acumulado" => round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2));
			
			$arrayVentaModelo[] = array(
				"id_condicion_unidad" => $rowVentaModelo['id_condicion_unidad'],
				"condicion_unidad" => $rowVentaModelo['condicion_unidad'],
				"array_modelo" => $arrayDetalleModelo,
				"cant_unidades_vendidas" => round($rowVentaModelo['cant_unidades_vendidas'],2),
				"cant_unidades_vendidas_acumulado" => round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2),
				"monto_unidades_vendidas" => round($rowVentaModelo['monto_unidades_vendidas'],2),
				"monto_unidades_vendidas_acumulado" => round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2));
		}
		
		$arrayTotalVentaModelo['cant_unidades_vendidas'] += round($rowVentaModelo['cant_unidades_vendidas'],2);
		$arrayTotalVentaModelo['cant_unidades_vendidas_acumulado'] += round($rowVentaModelo['cant_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaModelo['monto_unidades_vendidas'] += round($rowVentaModelo['monto_unidades_vendidas'],2);
		$arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] += round($rowVentaModelo['monto_unidades_vendidas_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	// VENTAS POR MODELO DE VEHÍCULO
	$contFila = 0;
	$arrayEquipos = NULL;
	if (isset($arrayVentaModelo)) {
		foreach ($arrayVentaModelo as $indice => $valor) {
			
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td colspan=\"6\" title=\"".$arrayVentaModelo[$indice]['id_condicion_unidad']."\">".utf8_encode($arrayVentaModelo[$indice]['condicion_unidad'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"30%\">Modelo</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"10%\">%</td>";
			$htmlTb .= "</tr>";
			
			$arrayMec = NULL;
			if (isset($arrayVentaModelo[$indice]['array_modelo'])) {
				foreach ($arrayVentaModelo[$indice]['array_modelo'] as $indice2 => $valor2) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$porcParticipacion = ($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] > 0) ? (($valor2['monto_unidades_vendidas_acumulado'] * 100) / $arrayTotalVentaModelo['monto_unidades_vendidas_acumulado']) : 0;
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
						$htmlTb .= "<td align=\"left\" title=\"".$valor2['id_unidad_basica']."\">".utf8_encode($valor2['vehiculo'])."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['cant_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['cant_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$arrayMec[] = implode("+*+",array(
						substr(utf8_encode($valor2['vehiculo']),0,50),
						round($porcParticipacion,2)));
				}
			}
			
			$porcParticipacion = ($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] > 0) ? (($arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'] * 100) / $arrayTotalVentaModelo['monto_unidades_vendidas_acumulado']) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td>Total Facturación ".utf8_encode($arrayVentaModelo[$indice]['condicion_unidad']).":</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaModelo[$indice]['cant_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaModelo[$indice]['cant_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaModelo[$indice]['monto_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayEquipos[] = implode("=*=",array(
				utf8_encode($arrayVentaModelo[$indice]['condicion_unidad']),
				($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'] > 0) ? round($arrayVentaModelo[$indice]['monto_unidades_vendidas_acumulado'] * 100 / $arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'],2) : 0,
				(count($arrayMec) > 0) ? implode("-*-",$arrayMec) : NULL));
			
			$arrayTotalVentaModelo['porcentaje_participacion'] += $porcParticipacion;
		}
	}
	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
	
	// TOTAL VENTAS POR MODELO DE VEHÍCULO
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL VENTAS POR MODELO DE VEHÍCULO:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaModelo['cant_unidades_vendidas'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaModelo['cant_unidades_vendidas_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaModelo['monto_unidades_vendidas'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaModelo['monto_unidades_vendidas_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaModelo['porcentaje_participacion'], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">VENTAS POR MODELO DE VEHÍCULO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						4,
						"Donut chart",
						"VENTAS POR MODELO DE VEHÍCULO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Facturación Condicion Unidad",
						str_replace("'","|*|",$data1),
						"Facturación Modelo",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaDetalleModelo","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// OPERACIONES A CRÉDITO
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.porcentaje_inicial < 100");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.porcentaje_inicial < 100
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// OPERACIONES A CRÉDITO
	$queryVentaCredito = sprintf("SELECT 
		banco.idBanco AS id_banco,
		banco.nombreBanco AS nombre_banco,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END)) AS nro_unidades_vendidas,
		
		COUNT(uni_fis.id_unidad_fisica) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.saldo_financiar / 100000, 
				ped_vent.saldo_financiar
			)
		END)) AS monto_financiado,
		
		SUM(IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.saldo_financiar / 100000, 
				ped_vent.saldo_financiar
			)) AS monto_financiado_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
	GROUP BY banco.idBanco
	
	UNION
	
	SELECT 
		banco.idBanco AS id_banco,
		banco.nombreBanco AS nombre_banco,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(uni_fis.id_unidad_fisica)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.saldo_financiar / 100000, 
				ped_vent.saldo_financiar
			)
		END))) AS monto_financiado,
		
		((-1) * SUM(IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.saldo_financiar / 100000, 
				ped_vent.saldo_financiar
			))) AS monto_financiado_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
	GROUP BY banco.idBanco
	
	ORDER BY 2;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaCredito = mysql_query($queryVentaCredito);
	if (!$rsVentaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaCredito = mysql_fetch_assoc($rsVentaCredito)) {
		$existe = false;
		if (isset($arrayVentaCredito)) {
			foreach ($arrayVentaCredito as $indice => $valor) {
				if ($arrayVentaCredito[$indice][0] == $rowVentaCredito['nombre_banco']) {
					$existe = true;
					
					$arrayVentaCredito[$indice][1] += round($rowVentaCredito['nro_unidades_vendidas'],2);
					$arrayVentaCredito[$indice][2] += round($rowVentaCredito['nro_unidades_vendidas_acumulado'],2);
					$arrayVentaCredito[$indice][3] += round($rowVentaCredito['monto_financiado'],2);
					$arrayVentaCredito[$indice][4] += round($rowVentaCredito['monto_financiado_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayVentaCredito[] = array(
				$rowVentaCredito['nombre_banco'],
				round($rowVentaCredito['nro_unidades_vendidas'],2),
				round($rowVentaCredito['nro_unidades_vendidas_acumulado'],2),
				round($rowVentaCredito['monto_financiado'],2),
				round($rowVentaCredito['monto_financiado_acumulado'],2));
		}
		
		$arrayTotalVentaCredito[1] += round($rowVentaCredito['nro_unidades_vendidas'],2);
		$arrayTotalVentaCredito[2] += round($rowVentaCredito['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaCredito[3] += round($rowVentaCredito['monto_financiado'],2);
		$arrayTotalVentaCredito[4] += round($rowVentaCredito['monto_financiado_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\">Banco</td>";
		$htmlTh .= "<td width=\"15%\">Cant. Mes</td>";
		$htmlTh .= "<td width=\"15%\">Cant. Acumulada</td>";
		$htmlTh .= "<td width=\"15%\">Monto Financiado Mes</td>";
		$htmlTh .= "<td width=\"15%\">Monto Financiado Acumulado</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// OPERACIONES A CRÉDITO
	$contFila = 0;
	if (isset($arrayVentaCredito)) {
		foreach ($arrayVentaCredito as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcParticipacion = ($arrayTotalVentaCredito[4] > 0) ? (($arrayVentaCredito[$indice][4] * 100) / $arrayTotalVentaCredito[4]) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".$arrayVentaCredito[$indice][0]."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaCredito[$indice][1], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaCredito[$indice][2], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaCredito[$indice][3], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaCredito[$indice][4], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalVentaCredito[5] += $porcParticipacion;
			
			$arrayClave[] = (count($arrayClave) > 0) ? "['".utf8_encode($arrayVentaCredito[$indice][0])."', ".$arrayVentaCredito[$indice][4]."]" : "{ name: '".utf8_encode($arrayVentaCredito[$indice][0])."', y: ".$arrayVentaCredito[$indice][4].", sliced: true, selected: true }";
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	// TOTAL OPERACIONES A CRÉDITO
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL OPERACIONES A CRÉDITO:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaCredito[1], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaCredito[2], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaCredito[3], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaCredito[4], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaCredito[5], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">OPERACIONES A CRÉDITO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						5,
						"Pie with legend",
						"OPERACIONES A CRÉDITO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Monto Financiado Acumulado",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaDetalleOperacionesCredito","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// OPERACIONES A CONTADO
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.porcentaje_inicial = 100");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND ped_vent.porcentaje_inicial = 100
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}

	// OPERACIONES A CONTADO
	$queryVentaContado = sprintf("SELECT 
		'Contado' AS nombre_banco,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END)) AS nro_unidades_vendidas,
		
		COUNT(uni_fis.id_unidad_fisica) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.inicial / 100000, 
				ped_vent.inicial
			)
		END)) AS monto_contado,
		
		SUM(IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.inicial / 100000, 
				ped_vent.inicial
			)) AS monto_contado_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
	
	UNION
	
	SELECT 
		'Contado' AS nombre_banco,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				uni_fis.id_unidad_fisica
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(uni_fis.id_unidad_fisica)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.inicial / 100000, 
				ped_vent.inicial
			)
		END))) AS monto_contado,
		
		((-1) * SUM(IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.inicial /100000, 
				ped_vent.inicial
			))) AS monto_contado_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN bancos banco ON (ped_vent.id_banco_financiar = banco.idBanco) %s
	GROUP BY 1
	
	ORDER BY 2;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaContado = mysql_query($queryVentaContado);
	if (!$rsVentaContado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowVentaContado = mysql_fetch_assoc($rsVentaContado)) {
		$existe = false;
		if (isset($arrayVentaContado)) {
			foreach ($arrayVentaContado as $indice => $valor) {
				if ($arrayVentaContado[$indice][0] == $rowVentaContado['nombre_banco']) {
					$existe = true;
					
					$arrayVentaContado[$indice][1] += round($rowVentaContado['nro_unidades_vendidas'],2);
					$arrayVentaContado[$indice][2] += round($rowVentaContado['nro_unidades_vendidas_acumulado'],2);
					$arrayVentaContado[$indice][3] += round($rowVentaContado['monto_contado'],2);
					$arrayVentaContado[$indice][4] += round($rowVentaContado['monto_contado_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayVentaContado[] = array(
				$rowVentaContado['nombre_banco'],
				round($rowVentaContado['nro_unidades_vendidas'],2),
				round($rowVentaContado['nro_unidades_vendidas_acumulado'],2),
				round($rowVentaContado['monto_contado'],2),
				round($rowVentaContado['monto_contado_acumulado'],2));
		}
		
		$arrayTotalVentaContado[1] += round($rowVentaContado['nro_unidades_vendidas'],2);
		$arrayTotalVentaContado[2] += round($rowVentaContado['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaContado[3] += round($rowVentaContado['monto_contado'],2);
		$arrayTotalVentaContado[4] += round($rowVentaContado['monto_contado_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\"></td>";
		$htmlTh .= "<td width=\"15%\">Cant. Mes</td>";
		$htmlTh .= "<td width=\"15%\">Cant. Acumulada</td>";
		$htmlTh .= "<td width=\"15%\">Monto Contado Mes</td>";
		$htmlTh .= "<td width=\"15%\">Monto Contado Acumulado</td>";
		$htmlTh .= "<td width=\"10%\">%</td>";
	$htmlTh .= "</tr>";
	
	// OPERACIONES A CONTADO
	$contFila = 0;
	if (isset($arrayVentaContado)) {
		foreach ($arrayVentaContado as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcParticipacion = ($arrayTotalVentaContado[4] > 0) ? (($arrayVentaContado[$indice][4] * 100) / $arrayTotalVentaContado[4]) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".$arrayVentaContado[$indice][0]."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaContado[$indice][1], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaContado[$indice][2], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaContado[$indice][3], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaContado[$indice][4], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalVentaContado[5] += $porcParticipacion;
			
			$arrayClave[] = (count($arrayClave) > 0) ? "['".utf8_encode($arrayVentaContado[$indice][0])."', ".$arrayVentaContado[$indice][4]."]" : "{ name: '".utf8_encode($arrayVentaContado[$indice][0])."', y: ".$arrayVentaContado[$indice][4].", sliced: true, selected: true }";
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	// TOTAL OPERACIONES A CONTADO
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL OPERACIONES A CONTADO:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaContado[1], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaContado[2], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaContado[3], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaContado[4], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaContado[5], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">OPERACIONES A CONTADO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						6,
						"Pie with legend",
						"OPERACIONES A CONTADO (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Monto Contado Acumulado",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaDetalleOperacionesContado","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// COMPAÑIA DE SEGUROS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// COMPAÑIA DE SEGUROS
	$querySeguros = sprintf("SELECT 
		poliza.id_poliza,
		poliza.nombre_poliza,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				poliza.id_poliza
		END)) AS nro_unidades_vendidas,
		
		COUNT(poliza.id_poliza) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.monto_seguro / 100000, 
				ped_vent.monto_seguro
			)
			
		END)) AS monto_asegurado,
		
		SUM(IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				ped_vent.monto_seguro / 100000, 
				ped_vent.monto_seguro
			)
			) AS monto_asegurado_acumulado
	FROM cj_cc_encabezadofactura cxc_fact
		LEFT JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN an_poliza poliza ON (ped_vent.id_poliza = poliza.id_poliza) %s
	GROUP BY poliza.id_poliza
	
	UNION
	
	SELECT 
		poliza.id_poliza,
		poliza.nombre_poliza,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				poliza.id_poliza
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(poliza.id_poliza)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.monto_seguro / 100000, 
				ped_vent.monto_seguro
			)
		END))) AS monto_asegurado,
		
		((-1) * SUM(IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				ped_vent.monto_seguro / 100000, 
				ped_vent.monto_seguro
			))) AS monto_asegurado_acumulado
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		RIGHT JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		LEFT JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		INNER JOIN an_poliza poliza ON (ped_vent.id_poliza = poliza.id_poliza) %s
	GROUP BY poliza.id_poliza
	
	ORDER BY 2;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsSeguros = mysql_query($querySeguros);
	if (!$rsSeguros) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowSeguros = mysql_fetch_assoc($rsSeguros)) {
		$existe = false;
		if (isset($arraySeguros)) {
			foreach ($arraySeguros as $indice => $valor) {
				if ($arraySeguros[$indice][0] == $rowSeguros['nombre_poliza']) {
					$existe = true;
					
					$arraySeguros[$indice][1] += round($rowSeguros['nro_unidades_vendidas'],2);
					$arraySeguros[$indice][2] += round($rowSeguros['nro_unidades_vendidas_acumulado'],2);
					$arraySeguros[$indice][3] += round($rowSeguros['monto_asegurado'],2);
					$arraySeguros[$indice][4] += round($rowSeguros['monto_asegurado_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arraySeguros[] = array(
				$rowSeguros['nombre_poliza'],
				round($rowSeguros['nro_unidades_vendidas'],2),
				round($rowSeguros['nro_unidades_vendidas_acumulado'],2),
				round($rowSeguros['monto_asegurado'],2),
				round($rowSeguros['monto_asegurado_acumulado'],2));
		}
		
		$arrayTotalSeguros[1] += round($rowSeguros['nro_unidades_vendidas'],2);
		$arrayTotalSeguros[2] += round($rowSeguros['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalSeguros[3] += round($rowSeguros['monto_asegurado'],2);
		$arrayTotalSeguros[4] += round($rowSeguros['monto_asegurado_acumulado'],2);
	}
	
	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"30%\">".("Aseguradora")."</td>";
		$htmlTh .= "<td width=\"15%\">".("Cant. Mes")."</td>";
		$htmlTh .= "<td width=\"15%\">".("Cant. Acumulada")."</td>";
		$htmlTh .= "<td width=\"15%\">".("Monto Mes")."</td>";
		$htmlTh .= "<td width=\"15%\">".("Monto Acumulado")."</td>";
		$htmlTh .= "<td width=\"10%\">".("%")."</td>";
	$htmlTh .= "</tr>";
	
	// COMPAÑIA DE SEGUROS
	$contFila = 0;
	if (isset($arraySeguros)) {
		foreach ($arraySeguros as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$porcParticipacion = ($arrayTotalSeguros[4] > 0) ? (($arraySeguros[$indice][4] * 100) / $arrayTotalSeguros[4]) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".$arraySeguros[$indice][0]."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arraySeguros[$indice][1], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arraySeguros[$indice][2], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arraySeguros[$indice][3], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arraySeguros[$indice][4], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalSeguros[5] += $porcParticipacion;
			
			$arrayClave[] = (count($arrayClave) > 0) ? "['".utf8_encode($arraySeguros[$indice][0])."', ".$arraySeguros[$indice][4]."]" : "{ name: '".utf8_encode($arraySeguros[$indice][0])."', y: ".$arraySeguros[$indice][4].", sliced: true, selected: true }";
		}
	}
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	// TOTAL COMPAÑIA DE SEGUROS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL COMPAÑIA DE SEGUROS:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalSeguros[1], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalSeguros[2], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalSeguros[3], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalSeguros[4], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalSeguros[5], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">COMPAÑIA DE SEGUROS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						7,
						"Pie with legend",
						"COMPAÑIA DE SEGUROS (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Monto Acumulado",
						str_replace("'","|*|",$data1),
						" ",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaDetalleSeguros","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VENTA POR EMPRESA
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	$arrayDet = NULL;
	$arrayMov = NULL;
	$arrayClave = NULL;
	
	$sqlBusq = "";
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)");
	
	$sqlBusq2 = "";
	$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
	$sqlBusq2 .= $cond.sprintf("cxc_fact.idDepartamentoOrigenFactura IN (2)
	AND cxc_nc.idDepartamentoNotaCredito IN (2)
	AND cxc_nc.tipoDocumento LIKE 'FA'");
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cxc_fact.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_fact.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("(cxc_nc.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = cxc_nc.id_empresa))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	if ($valFecha[0] != "-1" && $valFecha[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("MONTH(cxc_fact.fechaRegistroFactura) <= %s
		AND YEAR(cxc_fact.fechaRegistroFactura) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
			
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("MONTH(cxc_nc.fechaNotaCredito) <= %s
		AND YEAR(cxc_nc.fechaNotaCredito) = %s",
			valTpDato($valFecha[0], "date"),
			valTpDato($valFecha[1], "date"));
	}
	
	// VENTA POR EMPRESA
	$queryVentaEmpresa = sprintf("SELECT
		cxc_fact.id_empresa,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		COUNT((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN
				cxc_fact.idFactura
		END)) AS nro_unidades_vendidas,
		
		COUNT(cxc_fact.idFactura) AS nro_unidades_vendidas_acumulado,
		
		SUM((CASE MONTH(cxc_fact.fechaRegistroFactura)
			WHEN %s THEN IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				cxc_fact_det_vehic.precio_unitario / 100000, 
				cxc_fact_det_vehic.precio_unitario
			)
		END)) AS monto_facturado_vehiculo,
		
		SUM(IF (
				cxc_fact.fechaRegistroFactura <= '2018-08-20', 
				cxc_fact_det_vehic.precio_unitario / 100000, 
				cxc_fact_det_vehic.precio_unitario
			)) AS monto_facturado_vehiculo_acumulado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN cj_cc_factura_detalle_vehiculo cxc_fact_det_vehic ON (cxc_fact.idFactura = cxc_fact_det_vehic.id_factura)
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_fact_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		INNER JOIN an_pedido ped_vent ON (cxc_fact.numeroPedido = ped_vent.id_pedido)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	GROUP BY cxc_fact.id_empresa, uni_fis.id_condicion_unidad
	
	UNION
	
	SELECT 
		cxc_nc.id_empresa,
		uni_fis.id_condicion_unidad,
		cond_unidad.descripcion AS condicion_unidad,
		
		((-1) * COUNT((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN
				cxc_nc.idNotaCredito
		END))) AS nro_unidades_vendidas,
		
		((-1) * COUNT(cxc_nc.idNotaCredito)) AS nro_unidades_vendidas_acumulado,
		
		((-1) * SUM((CASE MONTH(cxc_nc.fechaNotaCredito)
			WHEN %s THEN IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				cxc_nc_det_vehic.precio_unitario / 100000, 
				cxc_nc_det_vehic.precio_unitario
			)
		END))) AS monto_facturado_vehiculo,
		
		((-1) * SUM(IF (
				cxc_nc.fechaNotaCredito <= '2018-08-20', 
				cxc_nc_det_vehic.precio_unitario / 100000, 
				cxc_nc_det_vehic.precio_unitario
			))) AS monto_facturado_vehiculo_acumulado,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM cj_cc_nota_credito_detalle_vehiculo cxc_nc_det_vehic
		INNER JOIN cj_cc_notacredito cxc_nc ON (cxc_nc_det_vehic.id_nota_credito = cxc_nc.idNotaCredito)
		INNER JOIN an_unidad_fisica uni_fis ON (cxc_nc_det_vehic.id_unidad_fisica = uni_fis.id_unidad_fisica)
		INNER JOIN an_condicion_unidad cond_unidad ON (uni_fis.id_condicion_unidad = cond_unidad.id_condicion_unidad)
		LEFT JOIN an_solicitud_factura ped_comp_det ON (uni_fis.id_pedido_compra_detalle = ped_comp_det.idSolicitud)
		LEFT JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura AND cxc_nc.tipoDocumento LIKE 'FA')
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nc.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	GROUP BY cxc_nc.id_empresa, uni_fis.id_condicion_unidad
	
	ORDER BY 3,8;",
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq,
		valTpDato($valFecha[0], "date"),
		valTpDato($valFecha[0], "date"),
		$sqlBusq2);
	$rsVentaEmpresa = mysql_query($queryVentaEmpresa);
	if (!$rsVentaEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$arrayVentaEmpresa = array();
	while ($rowVentaEmpresa = mysql_fetch_assoc($rsVentaEmpresa)) {
		$existe = false;
		$arrayDetalleEmpresa = NULL;
		if (isset($arrayVentaEmpresa)) {
			foreach ($arrayVentaEmpresa as $indice => $valor) {
				if ($arrayVentaEmpresa[$indice]['id_condicion_unidad'] == $rowVentaEmpresa['id_condicion_unidad']) {
					$existe = true;
					
					$existeEmpresa = false;
					$arrayDetalleEmpresa = NULL;
					if (isset($arrayVentaEmpresa[$indice]['array_empresa'])) {
						foreach ($arrayVentaEmpresa[$indice]['array_empresa'] as $indice2 => $valor2) {
							$arrayDetalleEmpresa = $valor2;
							if ($arrayDetalleEmpresa['id_empresa'] == $rowVentaEmpresa['id_empresa']) {
								$existeEmpresa = true;
								
								$arrayDetalleEmpresa['nro_unidades_vendidas'] += round($rowVentaEmpresa['nro_unidades_vendidas'],2);
								$arrayDetalleEmpresa['nro_unidades_vendidas_acumulado'] += round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2);
								$arrayDetalleEmpresa['monto_facturado_vehiculo'] += round($rowVentaEmpresa['monto_facturado_vehiculo'],2);
								$arrayDetalleEmpresa['monto_facturado_vehiculo_acumulado'] += round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2);
							}
							
							$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['nro_unidades_vendidas'] = round($arrayDetalleEmpresa['nro_unidades_vendidas'],2);
							$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['nro_unidades_vendidas_acumulado'] = round($arrayDetalleEmpresa['nro_unidades_vendidas_acumulado'],2);
							$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['monto_facturado_vehiculo'] = round($arrayDetalleEmpresa['monto_facturado_vehiculo'],2);
							$arrayVentaEmpresa[$indice]['array_empresa'][$indice2]['monto_facturado_vehiculo_acumulado'] = round($arrayDetalleEmpresa['monto_facturado_vehiculo_acumulado'],2);
						}
					}
					
					if ($existeEmpresa == false) {
						$arrayDetalleEmpresa = array(
							"id_empresa" => $rowVentaEmpresa['id_empresa'],
							"nombre_empresa" => $rowVentaEmpresa['nombre_empresa'],
							"nro_unidades_vendidas" => round($rowVentaEmpresa['nro_unidades_vendidas'],2),
							"nro_unidades_vendidas_acumulado" => round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2),
							"monto_facturado_vehiculo" => round($rowVentaEmpresa['monto_facturado_vehiculo'],2),
							"monto_facturado_vehiculo_acumulado" => round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2));
						
						$arrayVentaEmpresa[$indice]['array_empresa'][] = $arrayDetalleEmpresa;
					}
					
					$arrayVentaEmpresa[$indice]['nro_unidades_vendidas'] += round($rowVentaEmpresa['nro_unidades_vendidas'],2);
					$arrayVentaEmpresa[$indice]['nro_unidades_vendidas_acumulado'] += round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2);
					$arrayVentaEmpresa[$indice]['monto_facturado_vehiculo'] += round($rowVentaEmpresa['monto_facturado_vehiculo'],2);
					$arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'] += round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2);
				}
			}
		}
			
		if ($existe == false) {
			$arrayDetalleEmpresa[] = array(
				"id_empresa" => $rowVentaEmpresa['id_empresa'],
				"nombre_empresa" => $rowVentaEmpresa['nombre_empresa'],
				"nro_unidades_vendidas" => round($rowVentaEmpresa['nro_unidades_vendidas'],2),
				"nro_unidades_vendidas_acumulado" => round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2),
				"monto_facturado_vehiculo" => round($rowVentaEmpresa['monto_facturado_vehiculo'],2),
				"monto_facturado_vehiculo_acumulado" => round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2));
		
			$arrayVentaEmpresa[] = array(
				"id_condicion_unidad" => $rowVentaEmpresa['id_condicion_unidad'],
				"condicion_unidad" => $rowVentaEmpresa['condicion_unidad'],
				"array_empresa" => $arrayDetalleEmpresa,
				"nro_unidades_vendidas" => round($rowVentaEmpresa['nro_unidades_vendidas'],2),
				"nro_unidades_vendidas_acumulado" => round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2),
				"monto_facturado_vehiculo" => round($rowVentaEmpresa['monto_facturado_vehiculo'],2),
				"monto_facturado_vehiculo_acumulado" => round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2));
		}
		
		$arrayTotalVentaEmpresa['nro_unidades_vendidas'] += round($rowVentaEmpresa['nro_unidades_vendidas'],2);
		$arrayTotalVentaEmpresa['nro_unidades_vendidas_acumulado'] += round($rowVentaEmpresa['nro_unidades_vendidas_acumulado'],2);
		$arrayTotalVentaEmpresa['monto_facturado_vehiculo'] += round($rowVentaEmpresa['monto_facturado_vehiculo'],2);
		$arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] += round($rowVentaEmpresa['monto_facturado_vehiculo_acumulado'],2);
	}

	// CABECERA DE LA TABLA
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	
	// VENTAS POR EMPRESA
	$contFila = 0;
	$arrayEquipos = NULL;
	if (isset($arrayVentaEmpresa)) {
		foreach ($arrayVentaEmpresa as $indice => $valor) {
			
			$htmlTb .= "<tr class=\"tituloColumna\" height=\"24\">";
				$htmlTb .= "<td colspan=\"6\" title=\"".$arrayVentaEmpresa[$indice]['id_condicion_unidad']."\">".utf8_encode($arrayVentaEmpresa[$indice]['condicion_unidad'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td></td>";
				$htmlTb .= "<td colspan=\"2\">Cant. Vehículos</td>";
				$htmlTb .= "<td colspan=\"2\">Facturación</td>";
				$htmlTb .= "<td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr class=\"tituloColumna\">";
				$htmlTb .= "<td width=\"30%\">Empresa</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Cant. Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta de<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"15%\">"."Monto Venta Acumulado hasta<br>".$mes[intval($valFecha[0])]." ".$valFecha[1]."</td>";
				$htmlTb .= "<td width=\"10%\">%</td>";
			$htmlTb .= "</tr>";
			
			$arrayMec = NULL;
			if (isset($arrayVentaEmpresa[$indice]['array_empresa'])) {
				foreach ($arrayVentaEmpresa[$indice]['array_empresa'] as $indice2 => $valor2) {
					$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
					$contFila++;
					
					$porcParticipacion = ($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] > 0) ? (($valor2['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado']) : 0;
					
					$htmlTb .= "<tr align=\"right\" class=\"".$clase."\">";
						$htmlTb .= "<td align=\"left\" title=\"".$valor2['id_empresa']."\">".utf8_encode($valor2['nombre_empresa'])."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['nro_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['nro_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_vehiculo'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".valTpDato(number_format($valor2['monto_facturado_vehiculo_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
						$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
					$htmlTb .= "</tr>";
					
					$arrayMec[] = implode("+*+",array(
						substr(utf8_encode($valor2['nombre_empresa']),0,50),
						round($porcParticipacion,2)));
				}
			}
			
			$porcParticipacion = ($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] > 0) ? (($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'] * 100) / $arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado']) : 0;
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
				$htmlTb .= "<td>Total Facturación ".utf8_encode($arrayVentaEmpresa[$indice]['condicion_unidad']).":</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaEmpresa[$indice]['nro_unidades_vendidas'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaEmpresa[$indice]['nro_unidades_vendidas_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".valTpDato(number_format($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'], 2, ".", ","),"cero_por_vacio")."</td>";
				$htmlTb .= "<td>".number_format($porcParticipacion, 2, ".", ",")."%</td>";
			$htmlTb .= "</tr>";
			
			$arrayEquipos[] = implode("=*=",array(
				utf8_encode($arrayVentaEmpresa[$indice]['condicion_unidad']),
				($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'] > 0) ? round($arrayVentaEmpresa[$indice]['monto_facturado_vehiculo_acumulado'] * 100 / $arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'],2) : 0,
				(count($arrayMec) > 0) ? implode("-*-",$arrayMec) : NULL));
			
			$arrayTotalVentaEmpresa['porcentaje_participacion'] += $porcParticipacion;
		}
	}
	$data1 = (count($arrayEquipos) > 0) ? implode(",",$arrayEquipos) : "";
	
	// TOTAL VENTAS POR EMPRESA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td class=\"tituloCampo\">"."TOTAL VENTAS POR EMPRESA:"."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaEmpresa['nro_unidades_vendidas'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaEmpresa['nro_unidades_vendidas_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaEmpresa['monto_facturado_vehiculo'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaEmpresa['monto_facturado_vehiculo_acumulado'], 2, ".", ",")."</td>";
		$htmlTb .= "<td>".number_format($arrayTotalVentaEmpresa['porcentaje_participacion'], 2, ".", ",")."%</td>";
	$htmlTb .= "</tr>";
			
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"6\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">VENTA POR EMPRESA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						1,
						"Donut chart",
						"VENTAS POR EMPRESA (".$mes[intval($valFecha[0])]." ".$valFecha[1].")",
						str_replace("'","|*|",array("")),
						"Facturación Condicion Unidad",
						str_replace("'","|*|",$data1),
						"Facturación Empresa",
						str_replace("'","|*|",array("")),
						cAbrevMoneda);
				$htmlTh .= "</td>";
			$htmlTh .= "</tr>";
			$htmlTh .= "</table>";
		$htmlTh .= "</td>";
	$htmlTh .= "</thead>";

	$htmlTableFin.= "</table>";
	
	$objResponse->assign("divListaVentaEmpresa","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	$objResponse->script("
	byId('tblMsj').style.display = 'none';
	byId('tblInforme').style.display = '';");

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstDecimalPDF");
$xajax->register(XAJAX_FUNCTION,"exportarReporteVenta");
$xajax->register(XAJAX_FUNCTION,"formGrafico");
$xajax->register(XAJAX_FUNCTION,"imprimirReporteVenta");
$xajax->register(XAJAX_FUNCTION,"reporteVentareconvercion");
$xajax->register(XAJAX_FUNCTION,"reporteVenta");
?>