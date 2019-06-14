<?php
function listadoCobranzaPendiente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	global $spanClienteCxC;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	$query = sprintf("SELECT
		cliente.id,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS cedula,
		(SELECT SUM(fact_vent.saldoFactura)
			FROM cj_cc_encabezadofactura fact_vent
			WHERE fact_vent.idCliente = cliente.id
				AND fact_vent.estadoFactura IN (0, 2)) AS saldoFactura,
		(SELECT SUM(nota_cargo.saldoNotaCargo)
			FROM cj_cc_notadecargo nota_cargo
			WHERE nota_cargo.idCliente = cliente.id
				AND nota_cargo.estadoNotaCargo IN (0, 2)) AS saldoNotaCargo
	FROM cj_cc_cliente cliente
	WHERE (SELECT SUM(fact_vent.saldoFactura)
			FROM cj_cc_encabezadofactura fact_vent
			WHERE fact_vent.idCliente = cliente.id
				AND fact_vent.estadoFactura IN (0, 2)) > 0
				OR (SELECT SUM(nota_cargo.saldoNotaCargo)
					FROM cj_cc_notadecargo nota_cargo
					WHERE nota_cargo.idCliente = cliente.id
						AND nota_cargo.estadoNotaCargo IN (0, 2)) > 0");
//AND fact_vent.id_empresa = ".$idEmpresa."	
//AND nota_cargo.id_empresa = ".$idEmpresa."	
						//AND fact_vent.id_empresa = ".$idEmpresa."
						//AND nota_cargo.id_empresa = ".$idEmpresa."		
						
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
		$htmlTh .= ordenarCampo("xajax_listadoCobranzaPendiente", "30%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listadoCobranzaPendiente", "25%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, $spanClienteCxC);
		$htmlTh .= ordenarCampo("xajax_listadoCobranzaPendiente", "15%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Facturas"));
		$htmlTh .= ordenarCampo("xajax_listadoCobranzaPendiente", "15%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Notas de Débito"));
		$htmlTh .= ordenarCampo("xajax_listadoCobranzaPendiente", "15%", $pageNum, "", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Total"));
		$htmlTh .= "<td colspan=\"1\" class=\"noprint\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		$contFila ++;	
				
		$saldoFactuta = 0;
		$saldoNotaCargo = 0;
		$saldoDocumentos = 0;
		
		$saldoFactuta = $row['saldoFactura'];
		$saldoNotaCargo = $row['saldoNotaCargo'];
		$saldoDocumentos = $saldoFactuta + $saldoNotaCargo;
								
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['cedula'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($saldoFactuta,2,'.',',')."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($saldoNotaCargo,2,'.',',')."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($saldoDocumentos,2,'.',',')."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tdDetalleCobranza', '%s');\"><img class=\"puntero\" src=\"../img/iconos/view.png\" title=\"Ver Documentos\"/></a>",
					$contFila,
					$row['id']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCobranzaPendiente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCobranzaPendiente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoCobranzaPendiente(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCobranzaPendiente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCobranzaPendiente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("tdCobranzaPendiente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$saldoFacturaTotal += $row['saldoFactura'];
		$saldoNotaCargoTotal += $row['saldoNotaCargo'];
	}
	
	$saldoTotal = $saldoFacturaTotal + $saldoNotaCargoTotal;
	
	$objResponse->script("byId('txtTotalFactura').value= '".number_format($saldoFacturaTotal,2,".",",")."';");
	$objResponse->script("byId('txtTotalNcargo').value= '".number_format($saldoNotaCargoTotal,2,".",",")."';");
	$objResponse->script("byId('txtTotalGeneral').value= '".number_format($saldoTotal,2,".",",")."';");
	
	return $objResponse;
}

function listadoDetalleCobranza($pageNum = 0, $campOrd = "", $tpOrd = "fechaRegistro", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	//CONSULTA NOMBRE DEL CLIENTE
	$queryCliente = sprintf("SELECT CONCAT_WS(' ', nombre, apellido) AS nombre_cliente FROM cj_cc_cliente WHERE id = %s", $valBusq);
	$Cliente = mysql_query($queryCliente);
	if (!$Cliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_array($Cliente);
	
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
	
	// CONSULTA FACTURAS Y NOTAS DE CARGO NO CANCELADAS DE DETERMINADO CLIENTE
	// ESTADO: 0 = No cancelado ; 2 = Cancelado ; 3 = Parcialmente Cancelado
	$query = sprintf("SELECT
		'FA' AS tipoDocumento,
		idFactura AS idDocumento,
		numeroFactura AS numeroDocumento,
		numeroControl AS numeroControl,
		fechaRegistroFactura AS fechaRegistro,
		fechaVencimientoFactura AS fechaVencimiento,
		saldoFactura AS saldoDocumento,
		montoTotalFactura AS montoDocumento,
		estadoFactura AS estadoDocumento,
		idDepartamentoOrigenFactura AS idDeperamentoOrigen,
		DATEDIFF(NOW(),fechaVencimientoFactura) AS diasMora
	FROM
		cj_cc_encabezadofactura
	WHERE
		estadoFactura IN (0, 2)
		AND idCliente = '".$valBusq."'
	
	UNION
	
	SELECT
		'ND' AS tipoDocumento,
		idNotaCargo AS idDocumento,
		numeroNotaCargo AS numeroDocumento,
		numeroControlNotaCargo AS numeroControl,
		fechaRegistroNotaCargo AS fechaRegistro,
		fechaVencimientoNotaCargo AS fechaVencimiento,
		saldoNotaCargo AS saldoDocumento,
		montoTotalNotaCargo AS montoDocumento,
		estadoNotaCargo AS estadoDocumento,
		idDepartamentoOrigenNotaCargo AS idDeperamentoOrigen,
		DATEDIFF(NOW(),fechaVencimientoNotaCargo) AS diasMora
	FROM
		cj_cc_notadecargo
	WHERE
		estadoNotaCargo IN (0, 2)
		AND idCliente = '".$valBusq."'");
		//AND id_empresa = ".$idEmpresa."	
		//AND id_empresa = ".$idEmpresa."	
		
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoDetalleCobranza", "", $pageNum, "idDeperamentoOrigen", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listadoDetalleCobranza", "10%", $pageNum, "tipoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Documento");
		$htmlTh .= ordenarCampo("xajax_listadoDetalleCobranza", "15%", $pageNum, "numeroDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
		$htmlTh .= ordenarCampo("xajax_listadoDetalleCobranza", "15%", $pageNum, "fechaRegistro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Registro");
		$htmlTh .= ordenarCampo("xajax_listadoDetalleCobranza", "15%", $pageNum, "fechaVencimiento", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Vencimiento");
		$htmlTh .= ordenarCampo("xajax_listadoDetalleCobranza", "15%", $pageNum, "diasMora", $campOrd, $tpOrd, $valBusq, $maxRows, "Dias de Mora");
		$htmlTh .= ordenarCampo("xajax_listadoDetalleCobranza", "15%", $pageNum, "saldoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Saldo");
		$htmlTh .= ordenarCampo("xajax_listadoDetalleCobranza", "15%", $pageNum, "montoDocumento", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['idDeperamentoOrigen']) {
			case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
			default : $imgDctoModulo = $row['idDeperamentoOrigen'];
		}
		
		$saldoDocumento = $row['saldoDocumento'];
		$montoDocumento = $row['montoDocumento'];
		
		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgDctoModulo."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipoDocumento'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['numeroDocumento'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaRegistro']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fechaVencimiento']))."</td>";
			if ($row['diasMora'] > 0)
				$htmlTb .= "<td align=\"center\">".$row['diasMora']."</td>";
			else
				$htmlTb .= "<td align=\"center\">0</td>";
			$htmlTb .= "<td align=\"right\">".number_format($saldoDocumento,2,'.',',')."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($montoDocumento,2,'.',',')."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDetalleCobranza(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDetalleCobranza(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoDetalleCobranza(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDetalleCobranza(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDetalleCobranza(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ult.gif\"/>");
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("tdDetalleCobranza","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"listadoCobranzaPendiente");
$xajax->register(XAJAX_FUNCTION,"listadoDetalleCobranza");
?>