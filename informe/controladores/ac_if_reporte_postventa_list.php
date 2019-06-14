<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function buscar($frmBuscar, $a='') {
	$objResponse = new xajaxResponse();

	if ($a == 1) {
	$objResponse->loadCommands(reporteServiciosreconvercion($frmBuscar));
		# code...
	}else{

	$objResponse->loadCommands(reporteServicios($frmBuscar));

	}

	return $objResponse;
}

function cargaLstDecimalPDF($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$array = array("0" => "Sin Decimales", "1" => "Con Decimales", "2" => "Sin Decimales MILES", "3" => "Con Decimales MILES");
	$totalRows = count($array);
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"xajax_imprimirReporte(xajax.getFormValues('frmBuscar'));\"";
	
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

function exportarReporte($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha']);
	
	$objResponse->script("window.open('reportes/if_reporte_postventa_excel.php?valBusq=".$valBusq."','_self');");
	
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
					var brightness = 0.2 - (j / data[i].drilldown.data.length) / 5 ;
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
							distance: -30
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
							}
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

function imprimirReporte($frmBuscar) {
	$objResponse = new xajaxResponse();

	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha']);
	
		if ($frmBuscar['lstDecimalPDF'] == 2 ) {


		$objResponse->script(sprintf("verVentana('reportes/if_reporte_postventa_reconvercion_pdf.php?valBusq=%s&lstDecimalPDF=%s', 1000, 500);", $valBusq, 0));
	

		
	}else if($frmBuscar['lstDecimalPDF'] == 3){

		$objResponse->script(sprintf("verVentana('reportes/if_reporte_postventa_reconvercion_pdf.php?valBusq=%s&lstDecimalPDF=%s', 1000, 500);", $valBusq,1));
	


	}else{


	$objResponse->script(sprintf("verVentana('reportes/if_reporte_postventa_pdf.php?valBusq=%s&lstDecimalPDF=%s', 1000, 500);", $valBusq, $frmBuscar['lstDecimalPDF']));
	

	}

	
	$objResponse->assign("tdlstDecimalPDF","innerHTML","");
	
	return $objResponse;
}

function reporteServicios($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	$anoCierre = $frmBuscar['txtFecha'];
	
	$htmlMsj = "<table width=\"100%\">";
	$htmlMsj .= "<tr>";
		$htmlMsj .= "<td>";
			$htmlMsj .= "<p style=\"font-size:24px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; text-shadow:3px 3px 0 rgba(51,51,51,0.8);\">";
				$htmlMsj .= "<span style=\"display:inline-block; text-transform:uppercase; color:#38A6F0; padding-left:2px;\">".$anoCierre."</span>";
				/*$htmlMsj .= "<br>";
				$htmlMsj .= "<span style=\"font-size:18px; display:inline-block; text-transform:uppercase; color:#B7D154; padding-left:2px;\">Versión 3.0</span>";*/
			$htmlMsj .= "</p>";
		$htmlMsj .= "</td>";
	$htmlMsj .= "</tr>";
	$htmlMsj .= "</table>";
	
	$objResponse->assign("divMsjCierre","innerHTML",$htmlMsj);
	
	$sqlBusq = " ";
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = (SELECT emp.id_empresa
															FROM pg_empresa emp
																LEFT JOIN pg_empresa emp_ppal ON (emp.id_empresa_padre = emp_ppal.id_empresa)
															ORDER BY emp.id_empresa_padre ASC
															LIMIT 1)");
	}
	
	// VERIFICA VALORES DE CONFIGURACION (Incluir Notas en el Informe Gerencial)
	$queryConfig300 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 300 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig300 = mysql_query($queryConfig300);
	if (!$rsConfig300) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig300 = mysql_num_rows($rsConfig300);
	$rowConfig300 = mysql_fetch_assoc($rsConfig300);
	
	// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Servicios))
	$queryConfig302 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 302 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig302 = mysql_query($queryConfig302);
	if (!$rsConfig302) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig302 = mysql_num_rows($rsConfig302);
	$rowConfig302 = mysql_fetch_assoc($rsConfig302);
	
	// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Latoneria y Pintura))
	$queryConfig303 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 303 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig303 = mysql_query($queryConfig303);
	if (!$rsConfig303) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig303 = mysql_num_rows($rsConfig303);
	$rowConfig303 = mysql_fetch_assoc($rsConfig303);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// SERVICIOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(recepcion.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = recepcion.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(recepcion.fecha_entrada) = %s
			AND YEAR(recepcion.fecha_entrada) = %s AND recepcion.fecha_entrada <= '2018-08-20'",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		// ENTRADA DE VEHICULOS
		$queryValeRecepcion = sprintf("SELECT tipo_vale.*
		FROM sa_recepcion recepcion
			LEFT JOIN sa_tipo_vale tipo_vale ON (recepcion.id_tipo_vale = tipo_vale.id_tipo_vale) %s", $sqlBusq);
		$rsValeRecepcion = mysql_query($queryValeRecepcion);
		if (!$rsValeRecepcion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalTipoOrdenAbierta = 0;
		while ($rowValeRecepcion = mysql_fetch_assoc($rsValeRecepcion)) {
			$arrayValeRecepcion[$mesCierre][$rowValeRecepcion['id_tipo_vale']] = array(
				$rowValeRecepcion['descripcion'],
				$arrayValeRecepcion[$mesCierre][$rowValeRecepcion['id_tipo_vale']][1] + 1);
		}
	}
	
	// MANO DE OBRA SERVICIOS
	$query = sprintf("SELECT * FROM sa_operadores operador WHERE id_operador IN (1);");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$cont = 0;
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayMODet = NULL;
		
		$arrayMODet[0] = $row['descripcion_operador'];
		
		for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
			$sqlBusq = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.aprobado = 1");
			
			if (strlen($rowConfig302['valor']) > 0) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
					valTpDato($rowConfig302['valor'], "campo"));
			}
			
			if ($idEmpresa != "-1" && $idEmpresa != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = a.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
			}
		
			if ($mesCierre != "-1" && $mesCierre != ""
			&& $anoCierre != "-1" && $anoCierre != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf(" MONTH(a.fecha_filtro) = %s
				AND YEAR(a.fecha_filtro) = %s 	AND a.fecha_filtro <= '2018-08-20'",
					valTpDato($mesCierre, "date"),
					valTpDato($anoCierre, "date"));
			}
			
			// SOLO APLICA PARA LAS MANO DE OBRA

			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("operador = %s
			AND a.estado_tempario IN ('FACTURADO','TERMINADO')",
				valTpDato($row['id_operador'],"int"));
				
			$queryMO = sprintf("SELECT
				operador,
				SUM(total_tempario_orden) AS total_tempario_orden
			FROM (
				SELECT a.operador,
					(CASE a.id_modo
						WHEN 1 THEN -- UT
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
						WHEN 2 THEN -- PRECIO
							a.precio
					END) AS total_tempario_orden
				FROM sa_v_informe_final_tempario a %s %s
						
				UNION ALL
						
				SELECT
					a.operador,
					(-1) * (CASE a.id_modo
						WHEN 1 THEN -- UT
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
						WHEN 2 THEN -- PRECIO
							a.precio
					END) AS total_tempario_orden
				FROM sa_v_informe_final_tempario_dev a %s %s
							
				UNION ALL
				
				SELECT
					a.operador,
					(CASE a.id_modo
						WHEN 1 THEN -- UT
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
						WHEN 2 THEN -- PRECIO
							a.precio
					END) AS total_tempario_orden
				FROM sa_v_vale_informe_final_tempario a %s %s
				
				UNION ALL
				
				SELECT
					a.operador,
					(-1) * (CASE a.id_modo
						WHEN 1 THEN -- UT
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
						WHEN 2 THEN -- PRECIO
							a.precio
					END) AS total_tempario_orden
					
				FROM sa_v_vale_informe_final_tempario_dev a %s %s) AS q
			GROUP BY operador",
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2);
			$rsMO = mysql_query($queryMO);
			//return $objResponse->alert($queryMO);
			if (!$rsMO) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowMO = mysql_fetch_assoc($rsMO);
			
			$arrayMODet[$mesCierre] = round($rowMO['total_tempario_orden'],2);
		}
		
		$arrayMOServ[] = $arrayMODet;
	}
	
	// VENTA DE REPUESTOS POR SERVICIOS
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s AND a.fecha_filtro <= '2018-08-20'",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryRep = sprintf("SELECT
			SUM(total_repuesto_orden) AS total_repuesto_orden
		FROM (
			SELECT
				(precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
			FROM sa_v_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
				(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
			FROM sa_v_informe_final_repuesto_dev a %s
			
			UNION ALL
			
			SELECT
				(precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
				(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100) AS total_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsRep = mysql_query($queryRep);
		if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowRep = mysql_fetch_assoc($rsRep);
			
		$arrayRepServ[$mesCierre] = round($rowRep['total_repuesto_orden'],2);
	}
	
	// VENTA DE TOT POR SERVICIOS
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s AND a.fecha_filtro <= '2018-08-20'",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryTot = sprintf("SELECT
			SUM(total_tot_orden) AS total_tot_orden
		FROM (
			SELECT
				(monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
			FROM sa_v_informe_final_tot a %s
				
			UNION ALL
			
			SELECT
				(-1) * (monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
			FROM sa_v_informe_final_tot_dev a %s
			
			UNION ALL
			
			SELECT
				(monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
			FROM sa_v_vale_informe_final_tot a %s
			
			UNION ALL
			
			SELECT
				(-1) * (monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
			FROM sa_v_vale_informe_final_tot_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsTot = mysql_query($queryTot);
		if (!$rsTot) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowTot = mysql_fetch_assoc($rsTot);
		
		$arrayTotServ[$mesCierre] = round($rowTot['total_tot_orden'],2);
	}
	
	// VENTA DE NOTAS POR SERVICIOS
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s AND a.fecha_filtro <= '2018-08-20'",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryNota = sprintf("SELECT
			SUM(precio) AS total_notas_orden
		FROM (
			SELECT
				precio
			FROM sa_v_informe_final_notas a %s
				
			UNION ALL
			
			SELECT
				(-1) * precio
			FROM sa_v_informe_final_notas_dev a %s
			
			UNION ALL
			
			SELECT
				precio
			FROM sa_v_vale_informe_final_notas a %s
			
			UNION ALL
			
			SELECT
				(-1) * precio
			FROM sa_v_vale_informe_final_notas_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsNota = mysql_query($queryNota);
		if (!$rsNota) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNota = mysql_fetch_assoc($rsNota);
		
		$arrayNotasServ[$mesCierre] = round($rowNota['total_notas_orden'],2);
	}
	
	// COSTO DE VENTAS REPUESTOS POR SERVICIOS
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s AND a.fecha_filtro <= '2018-08-20'",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryRep = sprintf("SELECT
			SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
		FROM (
			SELECT
				(costo_unitario * cantidad) AS total_costo_repuesto_orden
			FROM sa_v_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
				(-1) * (costo_unitario * cantidad) AS total_costo_repuesto_orden
			FROM sa_v_informe_final_repuesto_dev a %s
			
			UNION ALL
			
			SELECT
				(costo_unitario * cantidad) AS total_costo_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
				(-1) * (costo_unitario * cantidad) AS total_costo_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsRep = mysql_query($queryRep);
		if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowRep = mysql_fetch_assoc($rsRep);
		
		$arrayCostoRepServ[$mesCierre] = round($rowRep['total_costo_repuesto_orden'],2);
	}
	
		
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// ENTRADA DE VEHICULOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Entrada de Vehículos</td>";
		$totalValeRecepcion = 0;
		for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
			$htmlTb .= "<td>".number_format($arrayValeRecepcion[$mesCierre][1][1],2,".",",")."</td>";
			$totalValeRecepcion += $arrayValeRecepcion[$mesCierre][1][1];
		}
		$htmlTb .= "<td>".number_format($totalValeRecepcion,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// MANO DE OBRA SERVICIOS
	if (isset($arrayMOServ)) {
		foreach ($arrayMOServ as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".htmlentities($arrayMOServ[$indice][0])."</td>";
				for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
					$htmlTb .= "<td>".number_format($arrayMOServ[$indice][$mesCierre],2,".",",")."</td>";
				}
				$htmlTb .= "<td>".number_format(array_sum($arrayMOServ[$indice]),2,".",",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	// VENTA DE REPUESTOS POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Repuestos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayRepServ[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayRepServ),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// TOT POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Trabajos Otros Talleres"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayTotServ[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayTotServ),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// NOTAS POR SERVICIOS
	if ($rowConfig300['valor'] == 1) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">"."Notas"."</td>";
		for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
			$htmlTb .= "<td>".number_format($arrayNotasServ[$mesCierre],2,".",",")."</td>";
		}
			$htmlTb .= "<td>".number_format(array_sum($arrayNotasServ),2,".",",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	// TOTAL SERVICIOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total Servicios"."</td>";
	$totalMOServ = NULL;
	$arrayMes = NULL;
	$arrayClave = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOServ)) {
			foreach ($arrayMOServ as $indice => $valor) {
				$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
			}
		}
		
		$totalServicios = $totalMOServ[$mesCierre] + $arrayRepServ[$mesCierre] + $arrayTotServ[$mesCierre];
		$totalServicios += ($rowConfig300['valor'] == 1) ? $arrayNotasServ[$mesCierre] : 0;
		
		$htmlTb .= "<td>".number_format($totalServicios,2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalServicios;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Total Servicios");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	
	$totalTotalServicios = array_sum($totalMOServ) + array_sum($arrayRepServ) + array_sum($arrayTotServ);
	$totalTotalServicios += ($rowConfig300['valor'] == 1) ? array_sum($arrayNotasServ) : 0;
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalTotalServicios,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// COSTO DE VENTAS REPUESTOS POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Costo de Ventas Repuestos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayCostoRepServ[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayCostoRepServ),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD REPUESTOS POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Utilidad Bruta Rep."."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format(($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre]),2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format((array_sum($arrayRepServ) - array_sum($arrayCostoRepServ)),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD DEPARTAMENTO DE SERVICIOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Utilidad Bruta Dep."."</td>";
	$totalMOServ = NULL;
	$arrayMes = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOServ)) {
			foreach ($arrayMOServ as $indice => $valor) {
				$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
			}
		}
		
		$totalUtilidadBrutaDep = $totalMOServ[$mesCierre] + ($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre]);
		$totalUtilidadBrutaDep += ($rowConfig300['valor'] == 1) ? $arrayNotasServ[$mesCierre] : 0;
		
		$htmlTb .= "<td>".number_format($totalUtilidadBrutaDep,2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalUtilidadBrutaDep;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Utilidad Bruta Dep.");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	$totalTotalUtilidadBrutaDep = array_sum($totalMOServ) + (array_sum($arrayRepServ) - array_sum($arrayCostoRepServ));
	$totalTotalUtilidadBrutaDep += ($rowConfig300['valor'] == 1) ? array_sum($arrayNotasServ) : 0;
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalTotalUtilidadBrutaDep,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// REPUESTO VS TOTAL POR SERVICIOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Repuestos Vs Total"."</td>";
	$totalMOServ = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOServ)) {
			foreach ($arrayMOServ as $indice => $valor) {
				$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
			}
		}
		
		$repVsTotal = (($totalMOServ[$mesCierre] + $arrayRepServ[$mesCierre]) > 0) ? (($arrayRepServ[$mesCierre] * 100) / ($totalMOServ[$mesCierre] + $arrayRepServ[$mesCierre])) : 0;
		
		$htmlTb .= "<td>".number_format($repVsTotal,2,".",",")."%</td>";
	}
		$totalRepVsTotal = ((array_sum($totalMOServ) + array_sum($arrayRepServ)) > 0) ? ((array_sum($arrayRepServ) * 100) / (array_sum($totalMOServ) + array_sum($arrayRepServ))) : 0;
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalRepVsTotal,2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">SERVICIOS (".$anoCierre.")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						1,
						"Basic column",
						"SERVICIOS (".$anoCierre.")",
						str_replace("'","|*|",array("")),
						"Importe ".cAbrevMoneda,
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
	
	$objResponse->assign("divListaReporteServicios","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// MOSTRADOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	
	// VENTA DE REPUESTO
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
		AND fact_vent.aplicaLibros = 1");
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
		AND nota_cred.tipoDocumento LIKE 'FA'
		AND nota_cred.aplicaLibros = 1
		AND nota_cred.estatus_nota_credito = 2");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = fact_vent.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
				
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(nota_cred.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = nota_cred.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
			AND YEAR(fact_vent.fechaRegistroFactura) = %s AND  fact_vent.fechaRegistroFactura <= '2018-08-20'",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
			AND YEAR(nota_cred.fechaNotaCredito) = %s AND nota_cred.fechaNotaCredito <= '2018-08-20'",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$query = sprintf("SELECT
			IFNULL(fact_vent.subtotalFactura,0) - IFNULL(fact_vent.descuentoFactura,0) AS neto
		FROM cj_cc_encabezadofactura fact_vent %s
		
		UNION ALL
		
		SELECT
			(-1) * (IFNULL(nota_cred.subtotalNotaCredito,0) - IFNULL(nota_cred.subtotal_descuento,0)) AS neto
		FROM cj_cc_notacredito nota_cred %s;",
			$sqlBusq,
			$sqlBusq2);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalVentaRepuesto = 0;
		while ($rowDetalle = mysql_fetch_assoc($rs)) {
			$totalVentaRepuesto += round($rowDetalle['neto'],2);
		}
		
		$arrayRepMost[$mesCierre] = $totalVentaRepuesto;
	}
	
	// COSTO DE VENTAS REPUESTOS
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
		AND fact_vent.aplicaLibros = 1");
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
		AND nota_cred.tipoDocumento LIKE 'FA'
		AND nota_cred.aplicaLibros = 1
		AND nota_cred.estatus_nota_credito = 2");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = fact_vent.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
				
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(nota_cred.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = nota_cred.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
			AND YEAR(fact_vent.fechaRegistroFactura) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
			AND YEAR(nota_cred.fechaNotaCredito) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$query = sprintf("SELECT
			(SELECT
				SUM((fact_vent_det.costo_compra * fact_vent_det.cantidad)) AS costo_total
			FROM cj_cc_factura_detalle fact_vent_det
			WHERE fact_vent_det.id_factura = fact_vent.idFactura) AS neto
		FROM cj_cc_encabezadofactura fact_vent %s
			
		UNION ALL
		
		SELECT
			((SELECT
				SUM((nota_cred_det.costo_compra * nota_cred_det.cantidad)) AS costo_total
			FROM cj_cc_nota_credito_detalle nota_cred_det
			WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito) * (-1)) AS neto
		FROM cj_cc_notacredito nota_cred %s;",
			$sqlBusq,
			$sqlBusq2);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalCostoRepMost = 0;
		while ($rowDetalle = mysql_fetch_assoc($rs)) {
			$totalCostoRepMost += round($rowDetalle['neto'],2);
		}
		
		$arrayCostoRepMost[$mesCierre] = $totalCostoRepMost;
	}
	
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// VENTA DE REPUESTOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total Repuestos"."</td>";
	$arrayMes = NULL;
	$arrayClave = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayRepMost[$mesCierre],2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $arrayRepMost[$mesCierre];
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Total Repuestos");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
		$htmlTb .= "<td class=\"tituloCampo\">".number_format(array_sum($arrayRepMost),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// COSTO DE VENTAS REPUESTOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Costo de Ventas Repuestos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayCostoRepMost[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayCostoRepMost),2,".",",")."</td>";
	$htmlTb .= "</tr>";
		
	// UTILIDAD DEPARTAMENTO
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Utilidad Bruta Dep."."</td>";
	$arrayMes = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format(($arrayRepMost[$mesCierre] - $arrayCostoRepMost[$mesCierre]),2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $arrayRepMost[$mesCierre] - $arrayCostoRepMost[$mesCierre];
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Utilidad Bruta Dep.");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
		$htmlTb .= "<td class=\"tituloCampo\">".number_format(array_sum($arrayRepMost) - array_sum($arrayCostoRepMost),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">MOSTRADOR (".$anoCierre.")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						2,
						"Basic column",
						"MOSTRADOR (".$anoCierre.")",
						str_replace("'","|*|",array("")),
						"Importe ".cAbrevMoneda,
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

	$objResponse->assign("divListaReporteMostrador","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// LATONERÍA Y PINTURA
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	
	// MANO DE OBRA LATONERÍA Y PINTURA
	$query = sprintf("SELECT * FROM sa_operadores operador WHERE id_operador IN (2,3);");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$cont = 0;
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayMODet = NULL;
		$arrayCostoMODet = NULL;
		
		$arrayMODet[0] = $row['descripcion_operador'];
		$arrayCostoMODet[0] = "costo";
		
		for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
			$sqlBusq = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.aprobado = 1");
			
			if ($idEmpresa != "-1" && $idEmpresa != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = a.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
			}
		
			if ($mesCierre != "-1" && $mesCierre != ""
			&& $anoCierre != "-1" && $anoCierre != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
				AND YEAR(a.fecha_filtro) = %s",
					valTpDato($mesCierre, "date"),
					valTpDato($anoCierre, "date"));
			}
			
			// SOLO APLICA PARA LAS MANO DE OBRA
			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("operador = %s
			AND a.estado_tempario IN ('FACTURADO','TERMINADO')",
				valTpDato($row['id_operador'],"int"));
			
			$queryMO = sprintf("SELECT
				operador,
				SUM(total_tempario_orden) AS total_tempario_orden,
				SUM(total_costo_tempario_orden) AS total_costo_tempario_orden
			FROM (
				SELECT a.operador,
					(CASE a.id_modo
						WHEN 1 THEN
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
						WHEN 2 THEN
							a.precio
					END) AS total_tempario_orden,
					(CASE a.id_modo
					WHEN 1 THEN -- UT
						a.costo
					WHEN 2 THEN -- PRECIO
						a.costo
				END) AS total_costo_tempario_orden
				FROM sa_v_informe_final_tempario a %s %s
						
				UNION ALL
						
				SELECT
					a.operador,
					(-1) * (CASE a.id_modo
						WHEN 1 THEN
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
						WHEN 2 THEN
							a.precio
					END) AS total_tempario_orden,
					(-1) * (CASE a.id_modo
						WHEN 1 THEN -- UT
							a.costo
						WHEN 2 THEN -- PRECIO
							a.costo
					END) AS total_costo_tempario_orden
				FROM sa_v_informe_final_tempario_dev a %s %s
							
				UNION ALL
				
				SELECT
					a.operador,
					(CASE a.id_modo
						WHEN 1 THEN
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
						WHEN 2 THEN
							a.precio
					END) AS total_tempario_orden,
					(CASE a.id_modo
						WHEN 1 THEN -- UT
							a.costo
						WHEN 2 THEN -- PRECIO
							a.costo
					END) AS total_costo_tempario_orden
				FROM sa_v_vale_informe_final_tempario a %s %s
							
				UNION ALL
				
				SELECT
					a.operador,
					(-1) * (CASE a.id_modo
						WHEN 1 THEN
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
						WHEN 2 THEN
							a.precio
					END) AS total_tempario_orden,
					(-1) * (CASE a.id_modo
						WHEN 1 THEN -- UT
							a.costo
						WHEN 2 THEN -- PRECIO
							a.costo
					END) AS total_costo_tempario_orden
				FROM sa_v_vale_informe_final_tempario_dev a %s %s) AS q
			GROUP BY operador",
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2);
			$rsMO = mysql_query($queryMO);
			if (!$rsMO) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowMO = mysql_fetch_assoc($rsMO);
			
			$arrayMODet[$mesCierre] = round($rowMO['total_tempario_orden'],2);
			
			// COSTO MANO DE OBRA (Incluye Materiales) NO DISCRIMINA POR OPERADOR
			$arrayCostoMOLatPint[$mesCierre] += round($rowMO['total_costo_tempario_orden'],2);
		}
		
		$arrayMOLatPint[] = $arrayMODet;
	}
	
	// VENTA DE REPUESTOS POR LATONERÍA Y PINTURA
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig303['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig303['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s AND a.fecha_filtro <= '2018-08-20'",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryRep = sprintf("SELECT
			SUM(total_repuesto_orden) AS total_repuesto_orden
		FROM (
			SELECT
				(precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS total_repuesto_orden
			FROM sa_v_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
				(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS total_repuesto_orden
			FROM sa_v_informe_final_repuesto_dev a %s
			
			UNION ALL
			
			SELECT
				(precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS total_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
				(-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100) AS total_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsRep = mysql_query($queryRep);
		if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowRep = mysql_fetch_assoc($rsRep);
		
		$arrayRepLatPint[$mesCierre] = round($rowRep['total_repuesto_orden'],2);
	}
	
	// VENTA DE TOT POR LATONERÍA Y PINTURA
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig303['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig303['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s AND a.fecha_filtro <= '2018-08-20'",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryTot = sprintf("SELECT
			SUM(total_tot_orden) AS total_tot_orden
		FROM (
			SELECT
				monto_total + ((porcentaje_tot * monto_total) / 100) AS total_tot_orden
			FROM sa_v_informe_final_tot a %s
				
			UNION ALL
			
			SELECT
				(-1) * (monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
			FROM sa_v_informe_final_tot_dev a %s
			
			UNION ALL
			
			SELECT
				monto_total + ((porcentaje_tot * monto_total) / 100) AS total_tot_orden
			FROM sa_v_vale_informe_final_tot a %s
			
			UNION ALL
			
			SELECT
				(-1) * (monto_total + ((porcentaje_tot * monto_total) / 100)) AS total_tot_orden
			FROM sa_v_vale_informe_final_tot_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsTot = mysql_query($queryTot);
		if (!$rsTot) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowTot = mysql_fetch_assoc($rsTot);
		
		$arrayTotLatPint[$mesCierre] = round($rowTot['total_tot_orden'],2);
	}
	
	// COSTO DE VENTAS REPUESTOS POR LATONERÍA Y PINTURA
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig303['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig303['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s AND a.fecha_filtro <= '2018-08-20'",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryRep = sprintf("SELECT
			SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
		FROM (
			SELECT
				(costo_unitario * cantidad) AS total_costo_repuesto_orden
			FROM sa_v_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
				(-1) * (costo_unitario * cantidad) AS total_costo_repuesto_orden
			FROM sa_v_informe_final_repuesto_dev a %s
			
			UNION ALL
			
			SELECT
				(costo_unitario * cantidad) AS total_costo_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
				(-1) * (costo_unitario * cantidad) AS total_costo_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsRep = mysql_query($queryRep);
		if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowRep = mysql_fetch_assoc($rsRep);
		
		$arrayCostoRepLatPint[$mesCierre] = round($rowRep['total_costo_repuesto_orden'],2);
	}
	
		
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// ENTRADA DE VEHICULOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Entrada de Vehículos</td>";
		$totalValeRecepcion = 0;
		for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
			$htmlTb .= "<td>".number_format($arrayValeRecepcion[$mesCierre][2][1] + $arrayValeRecepcion[$mesCierre][3][1],2,".",",")."</td>";
			$totalValeRecepcion += $arrayValeRecepcion[$mesCierre][2][1] + $arrayValeRecepcion[$mesCierre][3][1];
		}
		$htmlTb .= "<td>".number_format($totalValeRecepcion,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// MANO DE OBRA LATONERÍA Y PINTURA
	if (isset($arrayMOLatPint)) {
		foreach ($arrayMOLatPint as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">Mano de Obra ".htmlentities($arrayMOLatPint[$indice][0])."</td>";
				for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
					$htmlTb .= "<td>".number_format($arrayMOLatPint[$indice][$mesCierre],2,".",",")."</td>";
				}
				$htmlTb .= "<td>".number_format(array_sum($arrayMOLatPint[$indice]),2,".",",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	// VENTA DE REPUESTOS POR LATONERÍA Y PINTURA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Repuestos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayRepLatPint[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayRepLatPint),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// TOT POR LATONERÍA Y PINTURA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Trabajos Otros Talleres"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayTotLatPint[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayTotLatPint),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// TOTAL LATONERÍA Y PINTURA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total Latonería y Pintura"."</td>";
	$totalMOLatPint = NULL;
	$arrayMes = NULL;
	$arrayClave = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOLatPint)) {
			foreach ($arrayMOLatPint as $indice => $valor) {
				$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
			}
		}
		
		$totalLatoneriaPintura = $totalMOLatPint[$mesCierre] + $arrayRepLatPint[$mesCierre] + $arrayTotLatPint[$mesCierre];
		
		$htmlTb .= "<td>".number_format($totalLatoneriaPintura,2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalLatoneriaPintura;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Total Latonería y Pintura");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	
	$totalTotalLatoneriaPintura = array_sum($totalMOLatPint) + array_sum($arrayRepLatPint) + array_sum($arrayTotLatPint);
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalTotalLatoneriaPintura,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// COSTO MANO DE OBRA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Costo Mano de Obra (Incluye Materiales)"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayCostoMOLatPint[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayCostoMOLatPint),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD MANO DE OBRA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Utilidad Bruta Mano de Obra"."</td>";
	$totalMOLatPint = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOLatPint)) {
			foreach ($arrayMOLatPint as $indice => $valor) {
				$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
			}
		}
		
		$htmlTb .= "<td>".number_format(($totalMOLatPint[$mesCierre] - $arrayCostoMOLatPint[$mesCierre]),2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format((array_sum($totalMOLatPint) + array_sum($arrayCostoMOLatPint)),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// COSTO DE VENTAS REPUESTOS POR LATONERÍA Y PINTURA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Costo de Ventas Repuestos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayCostoRepLatPint[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayCostoRepLatPint),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD REPUESTOS POR LATONERÍA Y PINTURA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Utilidad Bruta Rep."."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format(($arrayRepLatPint[$mesCierre] - $arrayCostoRepLatPint[$mesCierre]),2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format((array_sum($arrayRepLatPint) - array_sum($arrayCostoRepLatPint)),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD DEPARTAMENTO LATONERÍA Y PINTURA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Utilidad Bruta Dep."."</td>";
	$totalMOLatPint = NULL;
	$arrayMes = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOLatPint)) {
			foreach ($arrayMOLatPint as $indice => $valor) {
				$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
			}
		}
		
		$totalUtilidadBrutaDepLatPint = $totalMOLatPint[$mesCierre] + ($arrayRepLatPint[$mesCierre] - $arrayCostoRepLatPint[$mesCierre]);
		
		$htmlTb .= "<td>".number_format($totalUtilidadBrutaDepLatPint,2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalUtilidadBrutaDepLatPint;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Utilidad Bruta Dep.");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	$totalTotalUtilidadBrutaDepLatPint = array_sum($totalMOLatPint) + array_sum($arrayRepLatPint) - array_sum($arrayCostoRepLatPint);
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalTotalUtilidadBrutaDepLatPint,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">LATONERÍA Y PINTURA (".$anoCierre.")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						3,
						"Basic column",
						"LATONERÍA Y PINTURA (".$anoCierre.")",
						str_replace("'","|*|",array("")),
						"Importe ".cAbrevMoneda,
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

	$objResponse->assign("divListaReporteLatoneriaPintura","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// TOTAL FACTURACIÓN
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
		
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// TOTAL FACTURACIÓN
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total Facturación"."</td>";
	$totalMOServ = NULL;
	$totalMOLatPint = NULL;
	$arrayMes = NULL;
	$arrayClave = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOServ)) {
			foreach ($arrayMOServ as $indice => $valor) {
				$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
			}
		}
		if (isset($arrayMOLatPint)) {
			foreach ($arrayMOLatPint as $indice => $valor) {
				$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
			}
		}
		
		$totalFacturacionMes = ($totalMOServ[$mesCierre] + $arrayRepServ[$mesCierre] + $arrayTotServ[$mesCierre]) + $arrayRepMost[$mesCierre] + ($totalMOLatPint[$mesCierre] + $arrayRepLatPint[$mesCierre] + $arrayTotLatPint[$mesCierre]);
		
		$htmlTb .= "<td>".number_format($totalFacturacionMes,2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalFacturacionMes;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Total Facturación");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	
	$totalTotalFacturacionMes = (array_sum($totalMOServ) + array_sum($arrayRepServ) + array_sum($arrayTotServ)) + array_sum($arrayRepMost) + (array_sum($totalMOLatPint) + array_sum($arrayRepLatPint) + array_sum($arrayTotLatPint));
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalTotalFacturacionMes,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD POST VENTA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Utilidad Bruta Post-Venta"."</td>";
	$totalMOServ = NULL;
	$totalMOLatPint = NULL;
	$arrayMes = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOServ)) {
			foreach ($arrayMOServ as $indice => $valor) {
				$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
			}
		}
		
		if (isset($arrayMOLatPint)) {
			foreach ($arrayMOLatPint as $indice => $valor) {
				$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
			}
		}
		
		$utilidadServ = $totalMOServ[$mesCierre] + ($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre]);
		$utilidadRep = $arrayRepMost[$mesCierre] - $arrayCostoRepMost[$mesCierre];
		$utilidadLatPint = $totalMOLatPint[$mesCierre] + ($arrayRepLatPint[$mesCierre] - $arrayCostoRepLatPint[$mesCierre]);
		$totalUtilidadMes = $utilidadServ + $utilidadRep + $utilidadLatPint;
		
		$htmlTb .= "<td>".number_format($totalUtilidadMes,2,".",",")."</td>";
		
		$totalFinalUtilidad += $totalUtilidadMes;
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalUtilidadMes;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Utilidad Bruta Post-Venta");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalFinalUtilidad,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">TOTAL FACTURACIÓN (".$anoCierre.")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						4,
						"Basic column",
						"TOTAL FACTURACIÓN (".$anoCierre.")",
						str_replace("'","|*|",array("")),
						"Importe ".cAbrevMoneda,
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

	$objResponse->assign("divListaReporteTotalFacturacion","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// INVENTARIO
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
		
	$arrayClasifInvDet[0] = "A";
	$arrayClasifInv[] = $arrayClasifInvDet;
	$arrayClasifInvDet[0] = "B";
	$arrayClasifInv[] = $arrayClasifInvDet;
	$arrayClasifInvDet[0] = "C";
	$arrayClasifInv[] = $arrayClasifInvDet;
	$arrayClasifInvDet[0] = "D";
	$arrayClasifInv[] = $arrayClasifInvDet;
	$arrayClasifInvDet[0] = "E";
	$arrayClasifInv[] = $arrayClasifInvDet;
	$arrayClasifInvDet[0] = "F";
	$arrayClasifInv[] = $arrayClasifInvDet;
	
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayClasifInv)) {
			foreach ($arrayClasifInv as $indice => $valor) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq = $cond.sprintf("(cierre_mens.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cierre_mens.id_empresa))",
					valTpDato($idEmpresa,"int"),
					valTpDato($idEmpresa,"int"));
				
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(cierre_mens.mes = %s
				AND cierre_mens.ano = %s)",
					valTpDato($mesCierre,"int"),
					valTpDato($anoCierre,"int"));
				
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("((analisis_inv_det.clasificacion = %s AND %s IS NOT NULL)
				OR analisis_inv_det.clasificacion IS NULL AND %s IS NULL)",
					valTpDato($arrayClasifInv[$indice][0],"text"), valTpDato($arrayClasifInv[$indice][0],"text"),
					valTpDato($arrayClasifInv[$indice][0],"text"));
		
				$queryDetalle = sprintf("SELECT
					analisis_inv_det.id_analisis_inventario,
					analisis_inv_det.cantidad_existencia,
					analisis_inv_det.cantidad_disponible_logica,
					analisis_inv_det.cantidad_disponible_fisica,
					analisis_inv_det.costo,
					(analisis_inv_det.costo * analisis_inv_det.cantidad_existencia) AS costo_total,
					(analisis_inv_det.cantidad_existencia / analisis_inv_det.promedio_mensual) AS meses_existencia,
					analisis_inv_det.promedio_diario,
					analisis_inv_det.promedio_mensual,
					(analisis_inv_det.promedio_mensual * 2) AS inventario_recomendado,
					(analisis_inv_det.cantidad_existencia - (analisis_inv_det.promedio_mensual * 2)) AS sobre_stock,
					((analisis_inv_det.promedio_mensual * 2) - analisis_inv_det.cantidad_existencia) AS sugerido,
					analisis_inv_det.clasificacion
				FROM iv_analisis_inventario_detalle analisis_inv_det
					INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
					INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
					INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual) %s", $sqlBusq);
				$rsDetalle = mysql_query($queryDetalle);
				if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$nroArt = 0;
				$exist = 0;
				$costoInv = 0;
				$promVenta = 0;
				$mesesExist = 0;
				while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
					$costoInv += $rowDetalle['costo_total'];
				}
				
				$arrayClasifInv[$indice][$mesCierre] = $costoInv;
			}
		}
	}
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<thead>";
		$htmlTh .= "<td align=\"center\" colspan=\"14\">"."<p class=\"textoAzul\">INVENTARIO (".$anoCierre.")</p>"."</td>";
	$htmlTh .= "</thead>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// CLASIFICACION INVENTARIO
	if (isset($arrayClasifInv)) {
		foreach ($arrayClasifInv as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\">".$arrayClasifInv[$indice][0]."</td>";
			for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
				$htmlTb .= "<td>".number_format($arrayClasifInv[$indice][$mesCierre],2,".",",")."</td>";
			}
				$htmlTb .= "<td>".number_format(array_sum($arrayClasifInv[$indice]),2,".",",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	// TOTAL INVENTARIO
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total Inventario"."</td>";
	$totalClasifInv = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayClasifInv)) {
			foreach ($arrayClasifInv as $indice => $valor) {
				$totalClasifInv[$mesCierre] += $arrayClasifInv[$indice][$mesCierre];
			}
		}
		$htmlTb .= "<td>".number_format($totalClasifInv[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td class=\"tituloCampo\">".number_format(array_sum($totalClasifInv),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// SUMA CLASIFICAICON SELECCIONADA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."A + B + C + F"."</td>";
	$totalClasifInvSelec = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (($arrayClasifInv[0][$mesCierre] + $arrayClasifInv[1][$mesCierre] + $arrayClasifInv[2][$mesCierre] + $arrayClasifInv[3][$mesCierre] + $arrayClasifInv[4][$mesCierre] + $arrayClasifInv[5][$mesCierre]) > 0) {
			$totalClasifInvSelec[$mesCierre] += ($arrayClasifInv[0][$mesCierre] + $arrayClasifInv[1][$mesCierre] + $arrayClasifInv[2][$mesCierre] + $arrayClasifInv[5][$mesCierre]) * 100 / ($arrayClasifInv[0][$mesCierre] + $arrayClasifInv[1][$mesCierre] + $arrayClasifInv[2][$mesCierre] + $arrayClasifInv[3][$mesCierre] + $arrayClasifInv[4][$mesCierre] + $arrayClasifInv[5][$mesCierre]);
			
			$cantClasifInvSelec++;
		}
		
		$htmlTb .= "<td>".number_format($totalClasifInvSelec[$mesCierre],2,".",",")."%</td>";
	}
		$htmlTb .= "<td>";
			$htmlTb .= ($cantClasifInvSelec > 0) ? number_format((array_sum($totalClasifInvSelec) / $cantClasifInvSelec),2,".",",") : number_format(0,2,".",",");
		$htmlTb .= "%</td>";
	$htmlTb .= "</tr>";
	
	// MESES COBERTURA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Meses Cobertura"."</td>";
	$totalClasifInv = NULL;
	$totalMesesCobertura = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayClasifInv)) {
			foreach ($arrayClasifInv as $indice => $valor) {
				$totalClasifInv[$mesCierre] += $arrayClasifInv[$indice][$mesCierre];
			}
		}
		
		if ($totalClasifInv[$mesCierre] > 0 && ($arrayCostoRepLatPint[$mesCierre] + $arrayCostoRepMost[$mesCierre] + $arrayCostoRepServ[$mesCierre]) > 0) {
			$totalMesesCobertura[$mesCierre] += $totalClasifInv[$mesCierre] / ($arrayCostoRepLatPint[$mesCierre] + $arrayCostoRepMost[$mesCierre] + $arrayCostoRepServ[$mesCierre]);
			
			$cantMesesCoberturaSelec++;
		}
		
		$htmlTb .= "<td>".number_format($totalMesesCobertura[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>";
			$htmlTb .= ($cantMesesCoberturaSelec > 0) ? number_format((array_sum($totalMesesCobertura) / $cantMesesCoberturaSelec),2,".",",") : number_format(0,2,".",",");
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaReporteInventario","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// MARGENES
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// MARGEN REPUESTOS / SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Margen Rptos/Serv."."</td>";
	$totalMargenRepServ = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if ($arrayRepServ[$mesCierre] > 0) {
			$totalMargenRepServ[$mesCierre] += ($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre]) * 100 / $arrayRepServ[$mesCierre];
			
			$cantMargenRepServ++;
		}
		
		$htmlTb .= "<td>".number_format($totalMargenRepServ[$mesCierre],2,".",",")."%</td>";
	}
		$htmlTb .= "<td>";
			$htmlTb .= ($cantMargenRepServ > 0) ? number_format((array_sum($totalMargenRepServ) / $cantMargenRepServ),2,".",",") : number_format(0,2,".",",");
		$htmlTb .= "%</td>";
	$htmlTb .= "</tr>";
	
	// MARGEN REPUESTOS / MOSTRADOR
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Margen Rptos/Most."."</td>";
	$totalMargenRepMost = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if ($arrayRepMost[$mesCierre] > 0) {
			$totalMargenRepMost[$mesCierre] = ($arrayRepMost[$mesCierre] - $arrayCostoRepMost[$mesCierre]) * 100 / $arrayRepMost[$mesCierre];
			
			$cantMargenRepMost++;
		}
		
		$htmlTb .= "<td>".number_format($totalMargenRepMost[$mesCierre],2,".",",")."%</td>";
	}
		$htmlTb .= "<td>";
			$htmlTb .= ($cantMargenRepMost > 0) ? number_format((array_sum($totalMargenRepMost) / $cantMargenRepMost),2,".",",") : number_format(0,2,".",",");
		$htmlTb .= "%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaReporteMargenes","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	$objResponse->script("
	byId('tblMsj').style.display = 'none';
	byId('tblInforme').style.display = '';");

	return $objResponse;
}
function reporteServiciosreconvercion($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$idEmpresa = $frmBuscar['lstEmpresa'];
	$anoCierre = $frmBuscar['txtFecha'];
	
	$htmlMsj = "<table width=\"100%\">";
	$htmlMsj .= "<tr>";
		$htmlMsj .= "<td>";
			$htmlMsj .= "<p style=\"font-size:24px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; text-shadow:3px 3px 0 rgba(51,51,51,0.8);\">";
				$htmlMsj .= "<span style=\"display:inline-block; text-transform:uppercase; color:#38A6F0; padding-left:2px;\">".$anoCierre."</span>";
				/*$htmlMsj .= "<br>";
				$htmlMsj .= "<span style=\"font-size:18px; display:inline-block; text-transform:uppercase; color:#B7D154; padding-left:2px;\">Versión 3.0</span>";*/
			$htmlMsj .= "</p>";
		$htmlMsj .= "</td>";
	$htmlMsj .= "</tr>";
	$htmlMsj .= "</table>";
	
	$objResponse->assign("divMsjCierre","innerHTML",$htmlMsj);
	
	$sqlBusq = " ";
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("config_emp.id_empresa = (SELECT emp.id_empresa
															FROM pg_empresa emp
																LEFT JOIN pg_empresa emp_ppal ON (emp.id_empresa_padre = emp_ppal.id_empresa)
															ORDER BY emp.id_empresa_padre ASC
															LIMIT 1)");
	}
	
	// VERIFICA VALORES DE CONFIGURACION (Incluir Notas en el Informe Gerencial)
	$queryConfig300 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 300 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig300 = mysql_query($queryConfig300);
	if (!$rsConfig300) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig300 = mysql_num_rows($rsConfig300);
	$rowConfig300 = mysql_fetch_assoc($rsConfig300);
	
	// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Servicios))
	$queryConfig302 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 302 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig302 = mysql_query($queryConfig302);
	if (!$rsConfig302) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig302 = mysql_num_rows($rsConfig302);
	$rowConfig302 = mysql_fetch_assoc($rsConfig302);
	
	// VERIFICA VALORES DE CONFIGURACION (Filtros de Orden en el Inf. Gerencial (Latoneria y Pintura))
	$queryConfig303 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 303 AND config_emp.status = 1 %s;", $sqlBusq);
	$rsConfig303 = mysql_query($queryConfig303);
	if (!$rsConfig303) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsConfig303 = mysql_num_rows($rsConfig303);
	$rowConfig303 = mysql_fetch_assoc($rsConfig303);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// SERVICIOS
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(recepcion.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = recepcion.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
		
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(recepcion.fecha_entrada) = %s
			AND YEAR(recepcion.fecha_entrada) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		// ENTRADA DE VEHICULOS
		$queryValeRecepcion = sprintf("SELECT tipo_vale.*
		FROM sa_recepcion recepcion
			LEFT JOIN sa_tipo_vale tipo_vale ON (recepcion.id_tipo_vale = tipo_vale.id_tipo_vale) %s", $sqlBusq);
		$rsValeRecepcion = mysql_query($queryValeRecepcion);
		if (!$rsValeRecepcion) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalTipoOrdenAbierta = 0;
		while ($rowValeRecepcion = mysql_fetch_assoc($rsValeRecepcion)) {
			$arrayValeRecepcion[$mesCierre][$rowValeRecepcion['id_tipo_vale']] = array(
				$rowValeRecepcion['descripcion'],
				$arrayValeRecepcion[$mesCierre][$rowValeRecepcion['id_tipo_vale']][1] + 1);
		}
	}
	
	// MANO DE OBRA SERVICIOS
	$query = sprintf("SELECT * FROM sa_operadores operador WHERE id_operador IN (1);");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$cont = 0;
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayMODet = NULL;
		
		$arrayMODet[0] = $row['descripcion_operador'];
		
		for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
			$sqlBusq = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.aprobado = 1");
			
			if (strlen($rowConfig302['valor']) > 0) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
					valTpDato($rowConfig302['valor'], "campo"));
			}
			
			if ($idEmpresa != "-1" && $idEmpresa != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = a.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
			}
		
			if ($mesCierre != "-1" && $mesCierre != ""
			&& $anoCierre != "-1" && $anoCierre != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
				AND YEAR(a.fecha_filtro) = %s",
					valTpDato($mesCierre, "date"),
					valTpDato($anoCierre, "date"));
			}
			
			// SOLO APLICA PARA LAS MANO DE OBRA
			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("operador = %s
			AND a.estado_tempario IN ('FACTURADO','TERMINADO')",
				valTpDato($row['id_operador'],"int"));
				
			$queryMO = sprintf("SELECT
				operador,
				SUM(total_tempario_orden) AS total_tempario_orden
			FROM (
				SELECT a.operador,
					(CASE a.id_modo
						WHEN 1 THEN -- UT
                     if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio) / 100000, 
                           (a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
                         
						)	
						WHEN 2 THEN -- PRECIO
                     if (
                             a.fecha_filtro <= '2018-08-20', 
                           a.precio / 100000, 
                           a.precio
                         
						)
							
					END) AS total_tempario_orden
				FROM sa_v_informe_final_tempario a %s %s
						
				UNION ALL
						
				SELECT
					a.operador,
					(-1) * (CASE a.id_modo
						WHEN 1 THEN -- UT
                     if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio) / 100000, 
                           (a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
                         
						)	
						WHEN 2 THEN -- PRECIO
                     if (
                             a.fecha_filtro <= '2018-08-20', 
                           a.precio / 100000, 
                           a.precio
                         
						)
							
					END) AS total_tempario_orden
				FROM sa_v_informe_final_tempario_dev a %s %s
							
				UNION ALL
				
				SELECT
					a.operador,
					(CASE a.id_modo
						WHEN 1 THEN -- UT
                     if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio) / 100000, 
                           (a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
                         
						)	
						WHEN 2 THEN -- PRECIO
                     if (
                             a.fecha_filtro <= '2018-08-20', 
                           a.precio / 100000, 
                           a.precio
                         
						)
							
					END) AS total_tempario_orden
				FROM sa_v_vale_informe_final_tempario a %s %s
				
				UNION ALL
				
				SELECT
					a.operador,
					(-1) * (CASE a.id_modo
						WHEN 1 THEN -- UT
                     if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio) / 100000, 
                           (a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio
                         
						)	
						WHEN 2 THEN -- PRECIO
                     if (
                             a.fecha_filtro <= '2018-08-20', 
                           a.precio / 100000, 
                           a.precio
                         
						)
							
					END) AS total_tempario_orden
					
				FROM sa_v_vale_informe_final_tempario_dev a %s %s) AS q
			GROUP BY operador",
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2);
			//return $objResponse->alert($queryMO);
			$rsMO = mysql_query($queryMO);
			if (!$rsMO) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowMO = mysql_fetch_assoc($rsMO);
			
			$arrayMODet[$mesCierre] = round($rowMO['total_tempario_orden'],2);
		}
		
		$arrayMOServ[] = $arrayMODet;
	}
	
	// VENTA DE REPUESTOS POR SERVICIOS
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryRep = sprintf("SELECT
			SUM(total_repuesto_orden) AS total_repuesto_orden
		FROM (
			SELECT
					    if (
                             a.fecha_filtro <= '2018-08-20', 
                          ((precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100)) / 100000, 
                          (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100)
                         
						)
				 AS total_repuesto_orden
			FROM sa_v_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
					    if (
                             a.fecha_filtro <= '2018-08-20', 
                          ((-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100)) / 100000, 
                          (-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100)
                         
						)
				 AS total_repuesto_orden
			FROM sa_v_informe_final_repuesto_dev a %s
			
			UNION ALL
			
			SELECT
					    if (
                             a.fecha_filtro <= '2018-08-20', 
                          ((precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100)) / 100000, 
                          (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100)
                         
						)
				 AS total_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
					    if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100)) / 100000, 
                           (-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * IFNULL(porcentaje_descuento_orden,0) / 100)
                         
						)
				 AS total_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsRep = mysql_query($queryRep);
		//return 	$objResponse->alert($queryRep);
		if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowRep = mysql_fetch_assoc($rsRep);
			
		$arrayRepServ[$mesCierre] = round($rowRep['total_repuesto_orden'],2);
	}
	
	// VENTA DE TOT POR SERVICIOS
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryTot = sprintf("SELECT
			SUM(total_tot_orden) AS total_tot_orden
		FROM (
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((monto_total + ((porcentaje_tot * monto_total) / 100))) / 100000, 
                           (monto_total + ((porcentaje_tot * monto_total) / 100))
                         
						)
				 AS total_tot_orden
			FROM sa_v_informe_final_tot a %s
				
			UNION ALL
			
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((-1) * (monto_total + ((porcentaje_tot * monto_total) / 100))) / 100000, 
                           (-1) * (monto_total + ((porcentaje_tot * monto_total) / 100))
                         
						)
				 AS total_tot_orden
			FROM sa_v_informe_final_tot_dev a %s
			
			UNION ALL
			
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((monto_total + ((porcentaje_tot * monto_total) / 100))) / 100000, 
                           (monto_total + ((porcentaje_tot * monto_total) / 100))
                         
						)
				 AS total_tot_orden
			FROM sa_v_vale_informe_final_tot a %s
			
			UNION ALL
			
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((-1) * (monto_total + ((porcentaje_tot * monto_total) / 100))) / 100000, 
                           (-1) * (monto_total + ((porcentaje_tot * monto_total) / 100))
                         
						)
				 AS total_tot_orden
			FROM sa_v_vale_informe_final_tot_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsTot = mysql_query($queryTot);
		//return $objResponse->alert($queryTot);
		if (!$rsTot) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowTot = mysql_fetch_assoc($rsTot);
		
		$arrayTotServ[$mesCierre] = round($rowTot['total_tot_orden'],2);
	}
	
	// VENTA DE NOTAS POR SERVICIOS
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryNota = sprintf("SELECT
			SUM(precio) AS total_notas_orden
		FROM (
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           (precio) / 100000, 
                           precio
                         
						)as



				precio
			FROM sa_v_informe_final_notas a %s
				
			UNION ALL
			
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           (	(-1) * precio) / 100000, 
                           	(-1) * precio
                         
						)as 
						precio

			
			FROM sa_v_informe_final_notas_dev a %s
			
			UNION ALL
			
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           (precio) / 100000, 
                           precio
                         
						) as 

				precio
			FROM sa_v_vale_informe_final_notas a %s
			
			UNION ALL
			
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((-1) * precio) / 100000, 
                           (-1) * precio
                         
						)
						as precio

				
			FROM sa_v_vale_informe_final_notas_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsNota = mysql_query($queryNota);
		if (!$rsNota) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowNota = mysql_fetch_assoc($rsNota);
		
		$arrayNotasServ[$mesCierre] = round($rowNota['total_notas_orden'],2);
	}
	
	// COSTO DE VENTAS REPUESTOS POR SERVICIOS
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig302['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig302['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryRep = sprintf("SELECT
			SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
		FROM (
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           (costo_unitario * cantidad)/ 100000, 
                           (costo_unitario * cantidad)
                         
						)

				 AS total_costo_repuesto_orden
			FROM sa_v_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((-1) * (costo_unitario * cantidad)) / 100000, 
                           (-1) * (12123)
                         
						)
				 AS total_costo_repuesto_orden
			FROM sa_v_informe_final_repuesto_dev a %s
			
			UNION ALL
			
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((costo_unitario * cantidad)) / 100000, 
                           (costo_unitario * cantidad)
                         
						)
				 AS total_costo_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
			 if (
                             a.fecha_filtro <= '2018-08-20', 
                           ((-1) * (costo_unitario * cantidad)) / 100000, 
                           (-1) * (costo_unitario * cantidad)
                         
						)
				 AS total_costo_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsRep = mysql_query($queryRep);
		//return $objResponse->alert($queryRep);
		if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowRep = mysql_fetch_assoc($rsRep);
		
		$arrayCostoRepServ[$mesCierre] = round($rowRep['total_costo_repuesto_orden'],2);
	}
	
		
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// ENTRADA DE VEHICULOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Entrada de Vehículos</td>";
		$totalValeRecepcion = 0;
		for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
			$htmlTb .= "<td>".number_format($arrayValeRecepcion[$mesCierre][1][1],2,".",",")."</td>";
			$totalValeRecepcion += $arrayValeRecepcion[$mesCierre][1][1];
		}
		$htmlTb .= "<td>".number_format($totalValeRecepcion,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// MANO DE OBRA SERVICIOS
	if (isset($arrayMOServ)) {
		foreach ($arrayMOServ as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">".htmlentities($arrayMOServ[$indice][0])."</td>";
				for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
					$htmlTb .= "<td>".number_format($arrayMOServ[$indice][$mesCierre],2,".",",")."</td>";
				}
				$htmlTb .= "<td>".number_format(array_sum($arrayMOServ[$indice]),2,".",",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	// VENTA DE REPUESTOS POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Repuestos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayRepServ[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayRepServ),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// TOT POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Trabajos Otros Talleres"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayTotServ[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayTotServ),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// NOTAS POR SERVICIOS
	if ($rowConfig300['valor'] == 1) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">"."Notas"."</td>";
		for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
			$htmlTb .= "<td>".number_format($arrayNotasServ[$mesCierre],2,".",",")."</td>";
		}
			$htmlTb .= "<td>".number_format(array_sum($arrayNotasServ),2,".",",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	// TOTAL SERVICIOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total Servicios"."</td>";
	$totalMOServ = NULL;
	$arrayMes = NULL;
	$arrayClave = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOServ)) {
			foreach ($arrayMOServ as $indice => $valor) {
				$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
			}
		}
		
		$totalServicios = $totalMOServ[$mesCierre] + $arrayRepServ[$mesCierre] + $arrayTotServ[$mesCierre];
		$totalServicios += ($rowConfig300['valor'] == 1) ? $arrayNotasServ[$mesCierre] : 0;
		
		$htmlTb .= "<td>".number_format($totalServicios,2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalServicios;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Total Servicios");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	
	$totalTotalServicios = array_sum($totalMOServ) + array_sum($arrayRepServ) + array_sum($arrayTotServ);
	$totalTotalServicios += ($rowConfig300['valor'] == 1) ? array_sum($arrayNotasServ) : 0;
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalTotalServicios,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// COSTO DE VENTAS REPUESTOS POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Costo de Ventas Repuestos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayCostoRepServ[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayCostoRepServ),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD REPUESTOS POR SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Utilidad Bruta Rep."."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format(($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre]),2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format((array_sum($arrayRepServ) - array_sum($arrayCostoRepServ)),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD DEPARTAMENTO DE SERVICIOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Utilidad Bruta Dep."."</td>";
	$totalMOServ = NULL;
	$arrayMes = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOServ)) {
			foreach ($arrayMOServ as $indice => $valor) {
				$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
			}
		}
		
		$totalUtilidadBrutaDep = $totalMOServ[$mesCierre] + ($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre]);
		$totalUtilidadBrutaDep += ($rowConfig300['valor'] == 1) ? $arrayNotasServ[$mesCierre] : 0;
		
		$htmlTb .= "<td>".number_format($totalUtilidadBrutaDep,2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalUtilidadBrutaDep;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Utilidad Bruta Dep.");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	$totalTotalUtilidadBrutaDep = array_sum($totalMOServ) + (array_sum($arrayRepServ) - array_sum($arrayCostoRepServ));
	$totalTotalUtilidadBrutaDep += ($rowConfig300['valor'] == 1) ? array_sum($arrayNotasServ) : 0;
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalTotalUtilidadBrutaDep,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// REPUESTO VS TOTAL POR SERVICIOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Repuestos Vs Total"."</td>";
	$totalMOServ = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOServ)) {
			foreach ($arrayMOServ as $indice => $valor) {
				$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
			}
		}
		
		$repVsTotal = (($totalMOServ[$mesCierre] + $arrayRepServ[$mesCierre]) > 0) ? (($arrayRepServ[$mesCierre] * 100) / ($totalMOServ[$mesCierre] + $arrayRepServ[$mesCierre])) : 0;
		
		$htmlTb .= "<td>".number_format($repVsTotal,2,".",",")."%</td>";
	}
		$totalRepVsTotal = ((array_sum($totalMOServ) + array_sum($arrayRepServ)) > 0) ? ((array_sum($arrayRepServ) * 100) / (array_sum($totalMOServ) + array_sum($arrayRepServ))) : 0;
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalRepVsTotal,2,".",",")."%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">SERVICIOS (".$anoCierre.")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						1,
						"Basic column",
						"SERVICIOS (".$anoCierre.")",
						str_replace("'","|*|",array("")),
						"Importe ".cAbrevMoneda,
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
	
	$objResponse->assign("divListaReporteServicios","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// MOSTRADOR
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	
	// VENTA DE REPUESTO
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
		AND fact_vent.aplicaLibros = 1");
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
		AND nota_cred.tipoDocumento LIKE 'FA'
		AND nota_cred.aplicaLibros = 1
		AND nota_cred.estatus_nota_credito = 2");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = fact_vent.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
				
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(nota_cred.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = nota_cred.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
			AND YEAR(fact_vent.fechaRegistroFactura) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
			AND YEAR(nota_cred.fechaNotaCredito) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$query = sprintf("SELECT
			 if (
                             fact_vent.fechaRegistroFactura <= '2018-08-20', 
                           (IFNULL(fact_vent.subtotalFactura,0) - IFNULL(fact_vent.descuentoFactura,0) ) / 100000, 
                           IFNULL(fact_vent.subtotalFactura,0) - IFNULL(fact_vent.descuentoFactura,0) 
                         
						)

			AS neto
		FROM cj_cc_encabezadofactura fact_vent %s
		
		UNION ALL
		
		SELECT
		 if (
                             nota_cred.fechaNotaCredito <= '2018-08-20', 
                           ((-1) * (IFNULL(nota_cred.subtotalNotaCredito,0) - IFNULL(nota_cred.subtotal_descuento,0)) ) / 100000, 
                           (-1) * (IFNULL(nota_cred.subtotalNotaCredito,0) - IFNULL(nota_cred.subtotal_descuento,0)) 
                         
						)

			 AS neto
		FROM cj_cc_notacredito nota_cred %s;",
			$sqlBusq,
			$sqlBusq2);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalVentaRepuesto = 0;
		while ($rowDetalle = mysql_fetch_assoc($rs)) {
			$totalVentaRepuesto += round($rowDetalle['neto'],2);
		}
		
		$arrayRepMost[$mesCierre] = $totalVentaRepuesto;
	}
	
	// COSTO DE VENTAS REPUESTOS
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fact_vent.idDepartamentoOrigenFactura IN (0)
		AND fact_vent.aplicaLibros = 1");
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("nota_cred.idDepartamentoNotaCredito IN (0)
		AND nota_cred.tipoDocumento LIKE 'FA'
		AND nota_cred.aplicaLibros = 1
		AND nota_cred.estatus_nota_credito = 2");
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(fact_vent.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = fact_vent.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
				
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(nota_cred.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = nota_cred.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(fact_vent.fechaRegistroFactura) = %s
			AND YEAR(fact_vent.fechaRegistroFactura) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
			
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("MONTH(nota_cred.fechaNotaCredito) = %s
			AND YEAR(nota_cred.fechaNotaCredito) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$query = sprintf("SELECT
			(SELECT
				SUM(

				if ( fact_vent.fechaRegistroFactura <='2018-08-20',

					(fact_vent_det.costo_compra * fact_vent_det.cantidad)/100000,
					(fact_vent_det.costo_compra * fact_vent_det.cantidad)



				)





				) AS costo_total
			FROM cj_cc_factura_detalle fact_vent_det
			WHERE fact_vent_det.id_factura = fact_vent.idFactura) AS neto
		FROM cj_cc_encabezadofactura fact_vent %s
			
		UNION ALL
		
		SELECT
			((SELECT
				SUM(

					if ( nota_cred.fechaNotaCredito <='2018-08-20',

						(nota_cred_det.costo_compra * nota_cred_det.cantidad)/100000,
						(nota_cred_det.costo_compra * nota_cred_det.cantidad)



				)




			



				) AS costo_total
			FROM cj_cc_nota_credito_detalle nota_cred_det
			WHERE nota_cred_det.id_nota_credito = nota_cred.idNotaCredito) * (-1)) AS neto
		FROM cj_cc_notacredito nota_cred %s;",
			$sqlBusq,
			$sqlBusq2);
		//return $objResponse->alert($query);
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalCostoRepMost = 0;
		while ($rowDetalle = mysql_fetch_assoc($rs)) {
			$totalCostoRepMost += round($rowDetalle['neto'],2);
		}
		
		$arrayCostoRepMost[$mesCierre] = $totalCostoRepMost;
	}
	
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// VENTA DE REPUESTOS
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total Repuestos"."</td>";
	$arrayMes = NULL;
	$arrayClave = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayRepMost[$mesCierre],2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $arrayRepMost[$mesCierre];
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Total Repuestos");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
		$htmlTb .= "<td class=\"tituloCampo\">".number_format(array_sum($arrayRepMost),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// COSTO DE VENTAS REPUESTOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Costo de Ventas Repuestos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayCostoRepMost[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayCostoRepMost),2,".",",")."</td>";
	$htmlTb .= "</tr>";
		
	// UTILIDAD DEPARTAMENTO
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Utilidad Bruta Dep."."</td>";
	$arrayMes = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format(($arrayRepMost[$mesCierre] - $arrayCostoRepMost[$mesCierre]),2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $arrayRepMost[$mesCierre] - $arrayCostoRepMost[$mesCierre];
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Utilidad Bruta Dep.");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
		$htmlTb .= "<td class=\"tituloCampo\">".number_format(array_sum($arrayRepMost) - array_sum($arrayCostoRepMost),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">MOSTRADOR (".$anoCierre.")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						2,
						"Basic column",
						"MOSTRADOR (".$anoCierre.")",
						str_replace("'","|*|",array("")),
						"Importe ".cAbrevMoneda,
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

	$objResponse->assign("divListaReporteMostrador","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// LATONERÍA Y PINTURA
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	
	// MANO DE OBRA LATONERÍA Y PINTURA
	$query = sprintf("SELECT * FROM sa_operadores operador WHERE id_operador IN (2,3);");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$cont = 0;
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayMODet = NULL;
		$arrayCostoMODet = NULL;
		
		$arrayMODet[0] = $row['descripcion_operador'];
		$arrayCostoMODet[0] = "costo";
		
		for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
			$sqlBusq = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.aprobado = 1");
			
			if ($idEmpresa != "-1" && $idEmpresa != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = a.id_empresa))",
					valTpDato($idEmpresa, "int"),
					valTpDato($idEmpresa, "int"));
			}
		
			if ($mesCierre != "-1" && $mesCierre != ""
			&& $anoCierre != "-1" && $anoCierre != "") {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
				AND YEAR(a.fecha_filtro) = %s",
					valTpDato($mesCierre, "date"),
					valTpDato($anoCierre, "date"));
			}
			
			// SOLO APLICA PARA LAS MANO DE OBRA
			$sqlBusq2 = "";
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("operador = %s
			AND a.estado_tempario IN ('FACTURADO','TERMINADO')",
				valTpDato($row['id_operador'],"int"));
			
			$queryMO = sprintf("SELECT
				operador,
				SUM(total_tempario_orden) AS total_tempario_orden,
				SUM(total_costo_tempario_orden) AS total_costo_tempario_orden
			FROM (
				SELECT a.operador,
					(CASE a.id_modo
						WHEN 1 THEN
							if( a.fecha_filtro <='2018-08-20',

							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio/100000,
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio



							)
							
						WHEN 2 THEN

						if( a.fecha_filtro <='2018-08-20',

								a.precio/100000,
								a.precio



							)
							
							
					END) AS total_tempario_orden,
					(CASE a.id_modo
					WHEN 1 THEN -- UT
							if( a.fecha_filtro <='2018-08-20',

							a.costo/100000,
							a.costo



							)
							
						
					WHEN 2 THEN -- PRECIO
					if( a.fecha_filtro <='2018-08-20',

							a.costo/100000,
							a.costo



							)
							
				END) AS total_costo_tempario_orden
				FROM sa_v_informe_final_tempario a %s %s
						
				UNION ALL
						
				SELECT
					a.operador,
					(-1) * (CASE a.id_modo
						WHEN 1 THEN
							if( a.fecha_filtro <='2018-08-20',

							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio/100000,
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio



							)
							
						WHEN 2 THEN

						if( a.fecha_filtro <='2018-08-20',

								a.precio/100000,
								a.precio



							)
							
							
					END) AS total_tempario_orden,
					(-1) * (CASE a.id_modo
					WHEN 1 THEN -- UT
							if( a.fecha_filtro <='2018-08-20',

							a.costo/100000,
							a.costo



							)
							
						
					WHEN 2 THEN -- PRECIO
					if( a.fecha_filtro <='2018-08-20',

							a.costo/100000,
							a.costo



							)
							
				END) AS total_costo_tempario_orden
				FROM sa_v_informe_final_tempario_dev a %s %s
							
				UNION ALL
				
				SELECT
					a.operador,
					(CASE a.id_modo
						WHEN 1 THEN
							if( a.fecha_filtro <='2018-08-20',

							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio/100000,
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio



							)
							
						WHEN 2 THEN

						if( a.fecha_filtro <='2018-08-20',

								a.precio/100000,
								a.precio



							)
							
							
					END) AS total_tempario_orden,
					(CASE a.id_modo
					WHEN 1 THEN -- UT
							if( a.fecha_filtro <='2018-08-20',

							a.costo/100000,
							a.costo



							)
							
						
					WHEN 2 THEN -- PRECIO
					if( a.fecha_filtro <='2018-08-20',

							a.costo/100000,
							a.costo



							)
							
				END) 
					 AS total_costo_tempario_orden
				FROM sa_v_vale_informe_final_tempario a %s %s
							
				UNION ALL
				
				SELECT
					a.operador,
					(-1) * (CASE a.id_modo
						WHEN 1 THEN
							if( a.fecha_filtro <='2018-08-20',

							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio/100000,
							(a.precio_tempario_tipo_orden * a.ut) / a.base_ut_precio



							)
							
						WHEN 2 THEN

						if( a.fecha_filtro <='2018-08-20',

								a.precio/100000,
								a.precio



							)
							
							
					END)  AS total_tempario_orden,
					(-1) *(CASE a.id_modo
					WHEN 1 THEN -- UT
							if( a.fecha_filtro <='2018-08-20',

							a.costo/100000,
							a.costo



							)
							
						
					WHEN 2 THEN -- PRECIO
					if( a.fecha_filtro <='2018-08-20',

							a.costo/100000,
							a.costo



							)
							
				END) AS total_costo_tempario_orden
				FROM sa_v_vale_informe_final_tempario_dev a %s %s) AS q
			GROUP BY operador",
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2,
				$sqlBusq, $sqlBusq2);
			$rsMO = mysql_query($queryMO);
			if (!$rsMO) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$rowMO = mysql_fetch_assoc($rsMO);
			
			$arrayMODet[$mesCierre] = round($rowMO['total_tempario_orden'],2);
			
			// COSTO MANO DE OBRA (Incluye Materiales) NO DISCRIMINA POR OPERADOR
			$arrayCostoMOLatPint[$mesCierre] += round($rowMO['total_costo_tempario_orden'],2);
		}
		
		$arrayMOLatPint[] = $arrayMODet;
	}
	
	// VENTA DE REPUESTOS POR LATONERÍA Y PINTURA
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig303['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig303['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryRep = sprintf("SELECT
			SUM(total_repuesto_orden) AS total_repuesto_orden
		FROM (
			SELECT
				if( a.fecha_filtro <= '2018-08-20',
					((precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100))/100000,
					((precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100))

				)
				 AS total_repuesto_orden
			FROM sa_v_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
				if( a.fecha_filtro <= '2018-08-20',
					((-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100))/100000,
					((-1) * (precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100))

				)
				AS total_repuesto_orden
			FROM sa_v_informe_final_repuesto_dev a %s
			
			UNION ALL
			
			SELECT
				if( a.fecha_filtro <= '2018-08-20',
					((precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100))/100000,
					((precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100))

				)
			AS total_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
				if( a.fecha_filtro <= '2018-08-20',
					((-1) *(precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100))/100000,
					((-1) *(precio_unitario * cantidad) - ((precio_unitario * cantidad) * porcentaje_descuento_orden / 100))

				)
				AS total_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsRep = mysql_query($queryRep);
		if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowRep = mysql_fetch_assoc($rsRep);
		
		$arrayRepLatPint[$mesCierre] = round($rowRep['total_repuesto_orden'],2);
	}
	
	// VENTA DE TOT POR LATONERÍA Y PINTURA
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig303['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig303['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryTot = sprintf("SELECT
			SUM(total_tot_orden) AS total_tot_orden
		FROM (
			SELECT
			if( a.fecha_filtro <= '2018-08-20',
					(monto_total + ((porcentaje_tot * monto_total) / 100)	)/100000,
					(monto_total + ((porcentaje_tot * monto_total) / 100)	)

				)
				 AS total_tot_orden
			FROM sa_v_informe_final_tot a %s
				
			UNION ALL
			
			SELECT
			if( a.fecha_filtro <= '2018-08-20',
						(-1) *(monto_total + ((porcentaje_tot * monto_total) / 100)	)/100000,
						(-1) *(monto_total + ((porcentaje_tot * monto_total) / 100)	)

				)
			AS total_tot_orden
			FROM sa_v_informe_final_tot_dev a %s
			
			UNION ALL
			
			SELECT
			if( a.fecha_filtro <= '2018-08-20',
					(monto_total + ((porcentaje_tot * monto_total) / 100)	)/100000,
					(monto_total + ((porcentaje_tot * monto_total) / 100)	)

				)
				AS total_tot_orden
			FROM sa_v_vale_informe_final_tot a %s
			
			UNION ALL
			
			SELECT
			if( a.fecha_filtro <= '2018-08-20',
					(-1) *(monto_total + ((porcentaje_tot * monto_total) / 100)	)/100000,
					(-1) *(monto_total + ((porcentaje_tot * monto_total) / 100)	)

				)
				AS total_tot_orden
			FROM sa_v_vale_informe_final_tot_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsTot = mysql_query($queryTot);
		if (!$rsTot) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowTot = mysql_fetch_assoc($rsTot);
		
		$arrayTotLatPint[$mesCierre] = round($rowTot['total_tot_orden'],2);
	}
	
	// COSTO DE VENTAS REPUESTOS POR LATONERÍA Y PINTURA
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$sqlBusq = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("a.aprobado = 1");
		
		if (strlen($rowConfig303['valor']) > 0) {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("a.id_filtro_orden IN (%s)",
				valTpDato($rowConfig303['valor'], "campo"));
		}
		
		if ($idEmpresa != "-1" && $idEmpresa != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("(a.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = a.id_empresa))",
				valTpDato($idEmpresa, "int"),
				valTpDato($idEmpresa, "int"));
		}
	
		if ($mesCierre != "-1" && $mesCierre != ""
		&& $anoCierre != "-1" && $anoCierre != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("MONTH(a.fecha_filtro) = %s
			AND YEAR(a.fecha_filtro) = %s",
				valTpDato($mesCierre, "date"),
				valTpDato($anoCierre, "date"));
		}
		
		$queryRep = sprintf("SELECT
			SUM(total_costo_repuesto_orden) AS total_costo_repuesto_orden
		FROM (
			SELECT
			if( a.fecha_filtro <= '2018-08-20',
						(costo_unitario * cantidad)/100000,
						(costo_unitario * cantidad)

				)
			 AS total_costo_repuesto_orden
			FROM sa_v_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
			if( a.fecha_filtro <= '2018-08-20',
						(-1) *(costo_unitario * cantidad)/100000,
						(-1) *(costo_unitario * cantidad)

				)
				 AS total_costo_repuesto_orden
			FROM sa_v_informe_final_repuesto_dev a %s
			
			UNION ALL
			
			SELECT
			if( a.fecha_filtro <= '2018-08-20',
						(costo_unitario * cantidad)/100000,
						(costo_unitario * cantidad)

				)
				AS total_costo_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto a %s
			
			UNION ALL
			
			SELECT
			if( a.fecha_filtro <= '2018-08-20',
						(-1) *(costo_unitario * cantidad)/100000,
						(-1) *(costo_unitario * cantidad)

				)
				 AS total_costo_repuesto_orden
			FROM sa_v_vale_informe_final_repuesto_dev a %s) AS q",
			$sqlBusq,
			$sqlBusq,
			$sqlBusq,
			$sqlBusq);
		$rsRep = mysql_query($queryRep);
		if (!$rsRep) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowRep = mysql_fetch_assoc($rsRep);
		
		$arrayCostoRepLatPint[$mesCierre] = round($rowRep['total_costo_repuesto_orden'],2);
	}
	
		
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// ENTRADA DE VEHICULOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">Entrada de Vehículos</td>";
		$totalValeRecepcion = 0;
		for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
			$htmlTb .= "<td>".number_format($arrayValeRecepcion[$mesCierre][2][1] + $arrayValeRecepcion[$mesCierre][3][1],2,".",",")."</td>";
			$totalValeRecepcion += $arrayValeRecepcion[$mesCierre][2][1] + $arrayValeRecepcion[$mesCierre][3][1];
		}
		$htmlTb .= "<td>".number_format($totalValeRecepcion,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// MANO DE OBRA LATONERÍA Y PINTURA
	if (isset($arrayMOLatPint)) {
		foreach ($arrayMOLatPint as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"left\">Mano de Obra ".htmlentities($arrayMOLatPint[$indice][0])."</td>";
				for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
					$htmlTb .= "<td>".number_format($arrayMOLatPint[$indice][$mesCierre],2,".",",")."</td>";
				}
				$htmlTb .= "<td>".number_format(array_sum($arrayMOLatPint[$indice]),2,".",",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	// VENTA DE REPUESTOS POR LATONERÍA Y PINTURA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Repuestos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayRepLatPint[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayRepLatPint),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// TOT POR LATONERÍA Y PINTURA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Trabajos Otros Talleres"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayTotLatPint[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayTotLatPint),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// TOTAL LATONERÍA Y PINTURA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total Latonería y Pintura"."</td>";
	$totalMOLatPint = NULL;
	$arrayMes = NULL;
	$arrayClave = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOLatPint)) {
			foreach ($arrayMOLatPint as $indice => $valor) {
				$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
			}
		}
		
		$totalLatoneriaPintura = $totalMOLatPint[$mesCierre] + $arrayRepLatPint[$mesCierre] + $arrayTotLatPint[$mesCierre];
		
		$htmlTb .= "<td>".number_format($totalLatoneriaPintura,2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalLatoneriaPintura;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Total Latonería y Pintura");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	
	$totalTotalLatoneriaPintura = array_sum($totalMOLatPint) + array_sum($arrayRepLatPint) + array_sum($arrayTotLatPint);
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalTotalLatoneriaPintura,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// COSTO MANO DE OBRA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Costo Mano de Obra (Incluye Materiales)"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayCostoMOLatPint[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayCostoMOLatPint),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD MANO DE OBRA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Utilidad Bruta Mano de Obra"."</td>";
	$totalMOLatPint = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOLatPint)) {
			foreach ($arrayMOLatPint as $indice => $valor) {
				$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
			}
		}
		
		$htmlTb .= "<td>".number_format(($totalMOLatPint[$mesCierre] - $arrayCostoMOLatPint[$mesCierre]),2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format((array_sum($totalMOLatPint) + array_sum($arrayCostoMOLatPint)),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// COSTO DE VENTAS REPUESTOS POR LATONERÍA Y PINTURA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Costo de Ventas Repuestos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format($arrayCostoRepLatPint[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format(array_sum($arrayCostoRepLatPint),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD REPUESTOS POR LATONERÍA Y PINTURA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Utilidad Bruta Rep."."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTb .= "<td>".number_format(($arrayRepLatPint[$mesCierre] - $arrayCostoRepLatPint[$mesCierre]),2,".",",")."</td>";
	}
		$htmlTb .= "<td>".number_format((array_sum($arrayRepLatPint) - array_sum($arrayCostoRepLatPint)),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD DEPARTAMENTO LATONERÍA Y PINTURA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Utilidad Bruta Dep."."</td>";
	$totalMOLatPint = NULL;
	$arrayMes = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOLatPint)) {
			foreach ($arrayMOLatPint as $indice => $valor) {
				$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
			}
		}
		
		$totalUtilidadBrutaDepLatPint = $totalMOLatPint[$mesCierre] + ($arrayRepLatPint[$mesCierre] - $arrayCostoRepLatPint[$mesCierre]);
		
		$htmlTb .= "<td>".number_format($totalUtilidadBrutaDepLatPint,2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalUtilidadBrutaDepLatPint;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Utilidad Bruta Dep.");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
	
	$totalTotalUtilidadBrutaDepLatPint = array_sum($totalMOLatPint) + array_sum($arrayRepLatPint) - array_sum($arrayCostoRepLatPint);
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalTotalUtilidadBrutaDepLatPint,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">LATONERÍA Y PINTURA (".$anoCierre.")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						3,
						"Basic column",
						"LATONERÍA Y PINTURA (".$anoCierre.")",
						str_replace("'","|*|",array("")),
						"Importe ".cAbrevMoneda,
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

	$objResponse->assign("divListaReporteLatoneriaPintura","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// TOTAL FACTURACIÓN
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
		
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh = "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// TOTAL FACTURACIÓN
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal2\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total Facturación"."</td>";
	$totalMOServ = NULL;
	$totalMOLatPint = NULL;
	$arrayMes = NULL;
	$arrayClave = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOServ)) {
			foreach ($arrayMOServ as $indice => $valor) {
				$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
			}
		}
		if (isset($arrayMOLatPint)) {
			foreach ($arrayMOLatPint as $indice => $valor) {
				$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
			}
		}
		
		$totalFacturacionMes = ($totalMOServ[$mesCierre] + $arrayRepServ[$mesCierre] + $arrayTotServ[$mesCierre]) + $arrayRepMost[$mesCierre] + ($totalMOLatPint[$mesCierre] + $arrayRepLatPint[$mesCierre] + $arrayTotLatPint[$mesCierre]);
		
		$htmlTb .= "<td>".number_format($totalFacturacionMes,2,".",",")."</td>";
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalFacturacionMes;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Total Facturación");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	
	$totalTotalFacturacionMes = (array_sum($totalMOServ) + array_sum($arrayRepServ) + array_sum($arrayTotServ)) + array_sum($arrayRepMost) + (array_sum($totalMOLatPint) + array_sum($arrayRepLatPint) + array_sum($arrayTotLatPint));
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalTotalFacturacionMes,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// UTILIDAD POST VENTA
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Utilidad Bruta Post-Venta"."</td>";
	$totalMOServ = NULL;
	$totalMOLatPint = NULL;
	$arrayMes = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayMOServ)) {
			foreach ($arrayMOServ as $indice => $valor) {
				$totalMOServ[$mesCierre] += $arrayMOServ[$indice][$mesCierre];
			}
		}
		
		if (isset($arrayMOLatPint)) {
			foreach ($arrayMOLatPint as $indice => $valor) {
				$totalMOLatPint[$mesCierre] += $arrayMOLatPint[$indice][$mesCierre];
			}
		}
		
		$utilidadServ = $totalMOServ[$mesCierre] + ($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre]);
		$utilidadRep = $arrayRepMost[$mesCierre] - $arrayCostoRepMost[$mesCierre];
		$utilidadLatPint = $totalMOLatPint[$mesCierre] + ($arrayRepLatPint[$mesCierre] - $arrayCostoRepLatPint[$mesCierre]);
		$totalUtilidadMes = $utilidadServ + $utilidadRep + $utilidadLatPint;
		
		$htmlTb .= "<td>".number_format($totalUtilidadMes,2,".",",")."</td>";
		
		$totalFinalUtilidad += $totalUtilidadMes;
		
		$arrayDet2[0] = $mes[$mesCierre]." ".$anoCierre;
		$arrayDet2[1] = $totalUtilidadMes;
		$arrayMes[] = implode("+*+",$arrayDet2);
	}	
	$arrayDet[0] = ("Utilidad Bruta Post-Venta");
	$arrayDet[1] = implode("-*-",$arrayMes);
	$arrayClave[] = implode("=*=",$arrayDet);
	$data1 = (count($arrayClave) > 0) ? implode(",",$arrayClave) : "";
		$htmlTb .= "<td class=\"tituloCampo\">".number_format($totalFinalUtilidad,2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	$htmlTh .= "<thead>";
		$htmlTh .= "<td colspan=\"14\">";
			$htmlTh .= "<table width=\"100%\">";
			$htmlTh .= "<tr>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">"."</td>";
				$htmlTh .= "<td align=\"center\" width=\"90%\">"."<p class=\"textoAzul\">TOTAL FACTURACIÓN (".$anoCierre.")</p>"."</td>";
				$htmlTh .= "<td align=\"right\" width=\"5%\">";
					$htmlTh .= sprintf("<a class=\"modalImg\" id=\"aGrafico%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblGrafico', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');\"><img class=\"puntero\" src=\"../img/iconos/chart_pie.png\" title=\"Gráficos\"/></a>",
						4,
						"Basic column",
						"TOTAL FACTURACIÓN (".$anoCierre.")",
						str_replace("'","|*|",array("")),
						"Importe ".cAbrevMoneda,
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

	$objResponse->assign("divListaReporteTotalFacturacion","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// INVENTARIO
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
		
	$arrayClasifInvDet[0] = "A";
	$arrayClasifInv[] = $arrayClasifInvDet;
	$arrayClasifInvDet[0] = "B";
	$arrayClasifInv[] = $arrayClasifInvDet;
	$arrayClasifInvDet[0] = "C";
	$arrayClasifInv[] = $arrayClasifInvDet;
	$arrayClasifInvDet[0] = "D";
	$arrayClasifInv[] = $arrayClasifInvDet;
	$arrayClasifInvDet[0] = "E";
	$arrayClasifInv[] = $arrayClasifInvDet;
	$arrayClasifInvDet[0] = "F";
	$arrayClasifInv[] = $arrayClasifInvDet;
	
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayClasifInv)) {

			foreach ($arrayClasifInv as $indice => $valor) {
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq = $cond.sprintf("(cierre_mens.id_empresa = %s
				OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
						WHERE suc.id_empresa = cierre_mens.id_empresa))",
					valTpDato($idEmpresa,"int"),
					valTpDato($idEmpresa,"int"));
				
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("(cierre_mens.mes = %s
				AND cierre_mens.ano = %s)",
					valTpDato($mesCierre,"int"),
					valTpDato($anoCierre,"int"));
				
				$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
				$sqlBusq .= $cond.sprintf("((analisis_inv_det.clasificacion = %s AND %s IS NOT NULL)
				OR analisis_inv_det.clasificacion IS NULL AND %s IS NULL)",
					valTpDato($arrayClasifInv[$indice][0],"text"), valTpDato($arrayClasifInv[$indice][0],"text"),
					valTpDato($arrayClasifInv[$indice][0],"text"));
		
				$queryDetalle = sprintf("SELECT
					analisis_inv_det.id_analisis_inventario,
					analisis_inv_det.cantidad_existencia,
					analisis_inv_det.cantidad_disponible_logica,
					analisis_inv_det.cantidad_disponible_fisica,
					analisis_inv_det.costo,
					if ( (cierre_mens.mes <8 AND cierre_mens.ano <= 2018) or (cierre_mens.ano < 2018) ,
					 (analisis_inv_det.costo * analisis_inv_det.cantidad_existencia)/100000, 
					 (analisis_inv_det.costo * analisis_inv_det.cantidad_existencia)
					) AS costo_total,
					(analisis_inv_det.cantidad_existencia / analisis_inv_det.promedio_mensual) AS meses_existencia,
					analisis_inv_det.promedio_diario,
					analisis_inv_det.promedio_mensual,
					(analisis_inv_det.promedio_mensual * 2) AS inventario_recomendado,
					(analisis_inv_det.cantidad_existencia - (analisis_inv_det.promedio_mensual * 2)) AS sobre_stock,
					((analisis_inv_det.promedio_mensual * 2) - analisis_inv_det.cantidad_existencia) AS sugerido,
					analisis_inv_det.clasificacion
				FROM iv_analisis_inventario_detalle analisis_inv_det
					INNER JOIN iv_articulos art ON (analisis_inv_det.id_articulo = art.id_articulo)
					INNER JOIN iv_analisis_inventario analisis_inv ON (analisis_inv_det.id_analisis_inventario = analisis_inv.id_analisis_inventario)
					INNER JOIN iv_cierre_mensual cierre_mens ON (analisis_inv.id_cierre_mensual = cierre_mens.id_cierre_mensual) %s", $sqlBusq);
				//return $objResponse->alert($queryDetalle);
				$rsDetalle = mysql_query($queryDetalle);
				if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				$nroArt = 0;
				$exist = 0;
				$costoInv = 0;
				$promVenta = 0;
				$mesesExist = 0;
				while ($rowDetalle = mysql_fetch_array($rsDetalle)) {
					$costoInv += $rowDetalle['costo_total'];

				}
				//return $objResponse->alert($costoInv);
				$arrayClasifInv[$indice][$mesCierre] = $costoInv;
			}
		}
	}
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<thead>";
		$htmlTh .= "<td align=\"center\" colspan=\"14\">"."<p class=\"textoAzul\">INVENTARIO (".$anoCierre.")</p>"."</td>";
	$htmlTh .= "</thead>";
	$htmlTh .= "</tr>";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// CLASIFICACION INVENTARIO

	if (isset($arrayClasifInv)) {
		foreach ($arrayClasifInv as $indice => $valor) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\">".$arrayClasifInv[$indice][0]."</td>";
			for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
				$htmlTb .= "<td>".number_format($arrayClasifInv[$indice][$mesCierre],2,".",",")."</td>";
			}
				$htmlTb .= "<td>".number_format(array_sum($arrayClasifInv[$indice]),2,".",",")."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	// TOTAL INVENTARIO
	$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"24\">";
		$htmlTb .= "<td align=\"left\" class=\"tituloCampo\">"."Total Inventario"."</td>";
	$totalClasifInv = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayClasifInv)) {
			foreach ($arrayClasifInv as $indice => $valor) {

				$totalClasifInv[$mesCierre] += $arrayClasifInv[$indice][$mesCierre];

			}
		}
		$htmlTb .= "<td>".number_format($totalClasifInv[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td class=\"tituloCampo\">".number_format(array_sum($totalClasifInv),2,".",",")."</td>";
	$htmlTb .= "</tr>";
	
	// SUMA CLASIFICAICON SELECCIONADA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."A + B + C + F"."</td>";
	$totalClasifInvSelec = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (($arrayClasifInv[0][$mesCierre] + $arrayClasifInv[1][$mesCierre] + $arrayClasifInv[2][$mesCierre] + $arrayClasifInv[3][$mesCierre] + $arrayClasifInv[4][$mesCierre] + $arrayClasifInv[5][$mesCierre]) > 0) {
			$totalClasifInvSelec[$mesCierre] += ($arrayClasifInv[0][$mesCierre] + $arrayClasifInv[1][$mesCierre] + $arrayClasifInv[2][$mesCierre] + $arrayClasifInv[5][$mesCierre]) * 100 / ($arrayClasifInv[0][$mesCierre] + $arrayClasifInv[1][$mesCierre] + $arrayClasifInv[2][$mesCierre] + $arrayClasifInv[3][$mesCierre] + $arrayClasifInv[4][$mesCierre] + $arrayClasifInv[5][$mesCierre]);
			
			$cantClasifInvSelec++;
		}
		
		$htmlTb .= "<td>".number_format($totalClasifInvSelec[$mesCierre],2,".",",")."%</td>";
	}
		$htmlTb .= "<td>";
			$htmlTb .= ($cantClasifInvSelec > 0) ? number_format((array_sum($totalClasifInvSelec) / $cantClasifInvSelec),2,".",",") : number_format(0,2,".",",");
		$htmlTb .= "%</td>";
	$htmlTb .= "</tr>";
	
	// MESES COBERTURA
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Meses Cobertura"."</td>";
	$totalClasifInv = NULL;
	$totalMesesCobertura = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if (isset($arrayClasifInv)) {
			foreach ($arrayClasifInv as $indice => $valor) {
				$totalClasifInv[$mesCierre] += $arrayClasifInv[$indice][$mesCierre];
			}
		}
		
		if ($totalClasifInv[$mesCierre] > 0 && ($arrayCostoRepLatPint[$mesCierre] + $arrayCostoRepMost[$mesCierre] + $arrayCostoRepServ[$mesCierre]) > 0) {
			$totalMesesCobertura[$mesCierre] += $totalClasifInv[$mesCierre] / ($arrayCostoRepLatPint[$mesCierre] + $arrayCostoRepMost[$mesCierre] + $arrayCostoRepServ[$mesCierre]);
			
			$cantMesesCoberturaSelec++;
		}
		
		$htmlTb .= "<td>".number_format($totalMesesCobertura[$mesCierre],2,".",",")."</td>";
	}
		$htmlTb .= "<td>";
			$htmlTb .= ($cantMesesCoberturaSelec > 0) ? number_format((array_sum($totalMesesCobertura) / $cantMesesCoberturaSelec),2,".",",") : number_format(0,2,".",",");
		$htmlTb .= "</td>";
	$htmlTb .= "</tr>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaReporteInventario","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// MARGENES
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$htmlTblIni = "";
	$htmlTh = "";
	$htmlTb = "";
	$htmlTblFin = "";
	
	$htmlTblIni .= "<table class=\"texto_9px\" cellpadding=\"2\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"10%\">"."Conceptos"."</td>";
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		$htmlTh .= "<td width=\"7%\">".$mes[$mesCierre]."</td>";
	}
		$htmlTh .= "<td width=\"6%\">"."Totales Año"."</td>";
	$htmlTh .= "</tr>";
	
	// MARGEN REPUESTOS / SERVICIOS
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Margen Rptos/Serv."."</td>";
	$totalMargenRepServ = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if ($arrayRepServ[$mesCierre] > 0) {
			$totalMargenRepServ[$mesCierre] += ($arrayRepServ[$mesCierre] - $arrayCostoRepServ[$mesCierre]) * 100 / $arrayRepServ[$mesCierre];
			
			$cantMargenRepServ++;
		}
		
		$htmlTb .= "<td>".number_format($totalMargenRepServ[$mesCierre],2,".",",")."%</td>";
	}
		$htmlTb .= "<td>";
			$htmlTb .= ($cantMargenRepServ > 0) ? number_format((array_sum($totalMargenRepServ) / $cantMargenRepServ),2,".",",") : number_format(0,2,".",",");
		$htmlTb .= "%</td>";
	$htmlTb .= "</tr>";
	
	// MARGEN REPUESTOS / MOSTRADOR
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	$htmlTb .= "<tr align=\"right\" class=\"".$clase."\" height=\"24\">";
		$htmlTb .= "<td align=\"left\">"."Margen Rptos/Most."."</td>";
	$totalMargenRepMost = NULL;
	for ($mesCierre = 1; $mesCierre <= 12; $mesCierre++) {
		if ($arrayRepMost[$mesCierre] > 0) {
			$totalMargenRepMost[$mesCierre] = ($arrayRepMost[$mesCierre] - $arrayCostoRepMost[$mesCierre]) * 100 / $arrayRepMost[$mesCierre];
			
			$cantMargenRepMost++;
		}
		
		$htmlTb .= "<td>".number_format($totalMargenRepMost[$mesCierre],2,".",",")."%</td>";
	}
		$htmlTb .= "<td>";
			$htmlTb .= ($cantMargenRepMost > 0) ? number_format((array_sum($totalMargenRepMost) / $cantMargenRepMost),2,".",",") : number_format(0,2,".",",");
		$htmlTb .= "%</td>";
	$htmlTb .= "</tr>";
	
	$htmlTableFin .= "</table>";

	$objResponse->assign("divListaReporteMargenes","innerHTML",$htmlTblIni.$htmlTh.$htmlTb.$htmlTblFin);
	
	$objResponse->script("
	byId('tblMsj').style.display = 'none';
	byId('tblInforme').style.display = '';");

	return $objResponse;
}
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstDecimalPDF");
$xajax->register(XAJAX_FUNCTION,"exportarReporte");
$xajax->register(XAJAX_FUNCTION,"formGrafico");
$xajax->register(XAJAX_FUNCTION,"imprimirReporte");
$xajax->register(XAJAX_FUNCTION,"reporteServicios");
$xajax->register(XAJAX_FUNCTION,"reporteServiciosreconvercion");
?>