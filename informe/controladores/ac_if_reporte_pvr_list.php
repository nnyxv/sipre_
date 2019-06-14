<?php
set_time_limit(0);

function buscarFacturaVenta($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFecha']);
	
	$objResponse->loadCommands(listaFacturaVenta(0, "numeroControl", "DESC", $valBusq));
	
	return $objResponse;
}


function listaFacturaVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanSerialMotor;
	global $spanPlaca;
	global $mes;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$objPVR = new ModeloPVR();
	$objPVR->campOrd = $campOrd;
	$objPVR->tpOrd = $tpOrd;
	$query = $objPVR->queryPVR(array(
		"idEmpresa" => $valCadBusq[0],
		"fechaCierre" => $valCadBusq[1]));
	
	$queryLimit = sprintf("%s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
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
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td colspan=\"4\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "14%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "fechaRegistroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "numeroFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "18%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "condicionDePago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Entidad Bancaria");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "14%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Vendedor");
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "6%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, "Modelo");
		
		$objPVR->campOrd = "nom_accesorio";
		$objPVR->tpOrd = "ASC";
		$queryAdicional = $objPVR->queryAdicional();//$objResponse->alert($queryAdicional);
		$rsAdicional = mysql_query($queryAdicional);
		if (!$rsAdicional) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsAdicional = mysql_num_rows($rsAdicional);
		while ($rowAdicional = mysql_fetch_assoc($rsAdicional)) {
			$htmlTh .= "<td title=\"Id Adicional: ".$rowAdicional['id_accesorio']."\" width=\"".(10 / $totalRowsAdicional)."%\">".$rowAdicional['nom_accesorio']."</td>";
			
			$arrayAdicional[$rowAdicional['id_accesorio']] = array(
				"id_accesorio" => $rowAdicional['id_accesorio'],
				"cantidad_facturas" => 0);
		}
		
		$htmlTh .= ordenarCampo("xajax_listaFacturaVenta", "14%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "PVR");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila ++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$imgPedidoModuloCondicion = ($row['id_pedido'] > 0) ? "" : "<img src=\"../img/iconos/ico_cuentas_cobrar.gif\" title=\"Creada por CxC\"/>";
			
		$imgEstatusPedido = ($row['anulada'] == "SI") ? "<img src=\"../img/iconos/ico_gris.gif\" title=\"Factura (Con Devolución)\"/>" : "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>";
			
		switch ($row['flotilla']) {
			case 0 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_normal.png\" title=\"Vehículo Normal\"/>"; break;
			case 1 : $imgEstatusUnidadAsignacion = "<img src=\"../img/iconos/ico_vehiculo_flotilla.png\" title=\"Vehículo por Flotilla\"/>"; break;
			default : $imgEstatusUnidadAsignacion = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td>".$imgPedidoModuloCondicion."</td>";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td>".$imgEstatusUnidadAsignacion."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaRegistroFactura']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeroFactura']."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_cliente'])."</td>";
				$htmlTb .= "</tr>";
				//$htmlTb .= ((strlen($row['observacionFactura']) > 0) ? "<tr><td class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacionFactura'])."</td></tr>" : "");
				$htmlTb .= "</table>";
			/*if (in_array(idArrayPais,array(3)) && $row['estatus_factura'] == 2) {
				$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo6\" width=\"100%\">";
				$htmlTb .= "<tr align=\"center\">";
					$htmlTb .= "<td height=\"25\" width=\"25\"><img src=\"../img/iconos/lock.png\"/></td>";
					$htmlTb .= "<td>Venta Cerrada</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= "</table>";
			}*/
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['condicionDePago'] == 1) ? "divMsjInfo" : "divMsjAlerta")."\">";
				$htmlTb .= ($row['condicionDePago'] == 1) ? "CONTADO" : "CRÉDITO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".$row['nom_modelo']."</td>";
			
			if (isset($arrayAdicional)) {
				foreach ($arrayAdicional as $indiceAdicional => $valorAdicional) {
					$idModelo = $arrayAdicional[$indiceAdicional]['id_accesorio'];
					
					/*$objMOR->campOrd = "descripcion_submotivo";
					$objMOR->tpOrd = "ASC";
					$queryMotivoPorModelo = $objMOR->queryMotivoPorModelo(array(
						"idEmpresa" => $idEmpresa,
						"fechaCierre" => $txtFecha,
						"idSubMotivo" => $rowMotivoServicio['id_submotivo'],
						"idModelo" => $idModelo));
					$rsMotivoPorModelo = mysql_query($queryMotivoPorModelo);//$objResponse->alert($queryMotivoPorModelo);
					if (!$rsMotivoPorModelo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
					$totalRowsMotivoPorModelo = mysql_num_rows($rsMotivoPorModelo);
					$rowMotivoPorModelo = mysql_fetch_assoc($rsMotivoPorModelo);*/
					
					$htmlTb .= "<td>".valTpDato(number_format($rowMotivoPorModelo['cantidad_ordenes'], 2, ".", ","),"cero_por_vacio")."</td>";
					
					$arrayAdicional[$idModelo]['cantidad_ordenes_motivo'] += $rowMotivoPorModelo['cantidad_ordenes'];
				}
			}
			
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"100\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaFacturaVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin = "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"100\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaFacturaVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	/*
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_iva'];
		$totalFacturas += $row['total'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalFacturas, 2, ".", ","));*/
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarFacturaVenta");
$xajax->register(XAJAX_FUNCTION,"listaFacturaVenta");
?>