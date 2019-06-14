<?php
function buscarRetenciones($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoRetencion(0,'idRetencionCabezera','DESC','%s' + '|' + %s + '|' + %s);",
		$valForm['txtBusq'],
		$valForm['selEmpresa'],
		$valForm['departamento']));
	
	return $objResponse;
}

function listadoRetencion($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	
	if ($valCadBusq[1] == -1 || $valCadBusq[1] == 0){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ret.id_empresa = %s",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

	}else{
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ret.id_empresa = %s",
		valTpDato($valCadBusq[1], "int"));
	}
	
	if (strlen($valCadBusq[0]) > 0){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(
									sa_orden_tot.numero_tot LIKE %s OR
									sa_orden.numero_orden LIKE %s OR
									ret.numeroComprobante LIKE %s OR
									ret.numeroControlFactura LIKE %s OR
									ret.nombre_proveedor LIKE %s OR
									ret.numero_factura_proveedor LIKE %s
									)",
		valTpDato("".$valCadBusq[0]."", "text"),
		valTpDato("".$valCadBusq[0]."", "text"),
		valTpDato("".$valCadBusq[0]."", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text")
		);
	}
	
	if($valCadBusq[2] == 1){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" sa_orden_tot.id_orden_tot IS NOT NULL");
	}
	
	$query = sprintf("SELECT 
							ret.idRetencionCabezera,
							ret.id_empresa,
							ret.idProveedor,
							ret.nombre_proveedor,
							ret.numeroComprobante,
							ret.fechaComprobante,
							ret.numero_factura_proveedor,
							ret.numeroControlFactura,
							ret.totalCompraIncluyendoIva,
							ret.baseImponible,
							ret.impuestoIva,
							ret.IvaRetenido,
							
							sa_orden_tot.id_orden_tot,
							sa_orden_tot.numero_tot,
							sa_orden.id_orden,
							sa_orden.numero_orden
							
					  FROM vw_cp_renteciones as ret
					  LEFT JOIN sa_orden_tot ON ret.idFactura = sa_orden_tot.id_factura
					  LEFT JOIN sa_orden ON sa_orden_tot.id_orden_servicio = sa_orden.id_orden
					  ").$sqlBusq;
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit) { return $objResponse->alert("Error: ".mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }
	//$objResponse->alert($queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs) { return $objResponse->alert("Error: ".mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";

	$htmlTh .= ordenarCampo("xajax_listadoRetencion", "16%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
	$htmlTh .= ordenarCampo("xajax_listadoRetencion", "3%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Orden");
	$htmlTh .= ordenarCampo("xajax_listadoRetencion", "3%", $pageNum, "numero_tot", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. TOT");
	$htmlTh .= ordenarCampo("xajax_listadoRetencion", "5%", $pageNum, "numeroComprobante", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Comprobante");
	$htmlTh .= ordenarCampo("xajax_listadoRetencion", "4%", $pageNum, "fechaComprobante", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
    $htmlTh .= ordenarCampo("xajax_listadoRetencion", "6%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Factura Proveedor");
    $htmlTh .= ordenarCampo("xajax_listadoRetencion", "6%", $pageNum, "numeroControlFactura", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Control Factura");
    $htmlTh .= ordenarCampo("xajax_listadoRetencion", "3%", $pageNum, "totalCompraIncluyendoIva", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
    $htmlTh .= ordenarCampo("xajax_listadoRetencion", "3%", $pageNum, "baseImponible", $campOrd, $tpOrd, $valBusq, $maxRows, "Base Imponible");
    $htmlTh .= ordenarCampo("xajax_listadoRetencion", "3%", $pageNum, "impuestoIva", $campOrd, $tpOrd, $valBusq, $maxRows, "Iva");
	$htmlTh .= ordenarCampo("xajax_listadoRetencion", "3%", $pageNum, "IvaRetenido", $campOrd, $tpOrd, $valBusq, $maxRows, "Impuesto Retenido");
	
	$htmlTh.= "<td width=\"1%\" class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";
				
		$queryEmpresa = "SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$row['id_empresa']."'";
		$rsEmpresa = mysql_query($queryEmpresa);
		if(!$rsEmpresa) { return $objResponse->alert("Error: ".mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$query); }
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
			
		$nombreSucursal = "";
		if ($rowEmpresa['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
		
		$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);		
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"left\" title=\"idProveedor: ".$row['idProveedor']."\">".utf8_encode($row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"center\" title=\"id_orden: ".$row['id_orden']."\">".$row['numero_orden']."</td>";
			$htmlTb .= "<td align=\"center\" title=\"id_orden_tot: ".$row['id_orden_tot']."\">".$row['numero_tot']."</td>";
			$htmlTb .= "<td align=\"center\" title=\"idRetencionCabezera: ".$row['idRetencionCabezera']."\">".$row['numeroComprobante']."</td>";
			$htmlTb .= "<td align=\"center\">".date("d/m/Y",strtotime($row['fechaComprobante']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numeroControlFactura']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['totalCompraIncluyendoIva'],2,",",".")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['baseImponible'],2,",",".")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['impuestoIva'],2,",",".")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['IvaRetenido'],2,",",".")."</td>";
			$htmlTb .= sprintf("<td align=\"center\" class=\"noprint\" ><img class=\"puntero\" title=\"Ver Comprobante\"  onclick=\"window.open('../cxp/reportes/an_comprobante_retencion_compra_pdf.php?valBusq=%s','_blank');\" src=\"../img/iconos/ico_view.png\" /></td>",
				$row['idRetencionCabezera']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoRetencion(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoRetencion(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("tdListadoRetencion","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarRetenciones");
$xajax->register(XAJAX_FUNCTION,"listadoRetencion");
?>