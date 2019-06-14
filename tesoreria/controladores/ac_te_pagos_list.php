<?php
function buscarPagos($valForm) {
    
    $objResponse = new xajaxResponse();
    
    if($valForm['buscarDocumento'] == 1){
        $objResponse->script(sprintf("xajax_listadoPagos(0,'numero_factura_proveedor','ASC','%s' + '|' + '%s' + '|' + '%s'+ '|' + '%s' + '|' + '%s');",
            $valForm['hddIdEmpresa'],
            $valForm['hddBePro'],
            $valForm['txtFechaDesde'],
            $valForm['txtFechaHasta'],
            $valForm['txtBusq']
        ));
    }
    
    if($valForm['buscarDocumento'] == 2){
        $objResponse->script(sprintf("xajax_listadoPagos2(0,'numero_notacargo','ASC','%s' + '|' + '%s' + '|' + '%s'+ '|' + '%s' + '|' + '%s');",
            $valForm['hddIdEmpresa'],
            $valForm['hddBePro'],
            $valForm['txtFechaDesde'],
            $valForm['txtFechaHasta'],
            $valForm['txtBusq']
        ));
    }
    
    return $objResponse;
}

function listadoPagos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL){
    $objResponse = new xajaxResponse();
	
	global $spanCI;
	global $spanRIF;	
        
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
        //$valCadBusq[0] = id empresa
        //$valCadBusq[1] = id benef o proveedor(solo se usa proveedor)
        //$valCadBusq[2] = fecha inicio
        //$valCadBusq[3] = fecha fin
        //$valCadBusq[4] = Criterio numero fact/nota
                
//        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
//        $sqlBusq .= $cond."vw_te_retencion_cheque.anulado IS NULL";
        
	if ($valCadBusq[0] == ''){
		//$sqlBusq .= " vw_te_retencion_cheque.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";	
	}else if ($valCadBusq[0] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."cp_factura.id_empresa = '".$valCadBusq[0]."'";
	}
        
	if ($valCadBusq[1] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."cp_factura.id_proveedor = '".$valCadBusq[1]."'";
	}
        
	if ($valCadBusq[2] != '' && $valCadBusq[3] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(cp_factura.fecha_origen) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])),"text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])),"text")
		);
	}
        
	if ($valCadBusq[4] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if(strpos($valCadBusq[4],",")){
			$arrayNumeros = implode("','", array_map('trim',explode(",",$valCadBusq[4])));
			$sqlBusq .= $cond."cp_factura.numero_factura_proveedor IN ('".$arrayNumeros."')";
		}else{
			$sqlBusq .= $cond."cp_factura.numero_factura_proveedor LIKE '%".$valCadBusq[4]."%'";
		}
	}
        
	$query = "SELECT 
		pg_empresa.nombre_empresa,
		CONCAT_WS('-',cp_proveedor.lrif,cp_proveedor.rif) as rif_proveedor,
		cp_proveedor.nombre,
		'FA' as tipo_documento,
		cp_factura.fecha_origen,
		cp_factura.numero_factura_proveedor,
		cp_factura.subtotal_factura,
		IFNULL((SELECT SUM(subtotal_iva) FROM cp_factura_iva WHERE id_factura = cp_factura.id_factura),0) AS iva_factura,
		cp_factura.monto_exento,
		cp_factura.subtotal_descuento,
		(cp_factura.subtotal_factura + cp_factura.subtotal_descuento + IFNULL((SELECT  SUM(subtotal_iva) FROM cp_factura_iva WHERE id_factura = cp_factura.id_factura),0)) AS monto_factura,
		IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle WHERE idFactura = cp_factura.id_factura),0) AS retencion_iva,
                IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque WHERE id_factura = cp_factura.id_factura AND tipo = 0 AND anulado IS NULL),0) AS retencion_islr,		

                ((cp_factura.subtotal_factura + cp_factura.subtotal_descuento + IFNULL((SELECT SUM(subtotal_iva) FROM cp_factura_iva WHERE id_factura = cp_factura.id_factura),0))
                  - cp_factura.subtotal_descuento
                  - IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle WHERE idFactura = cp_factura.id_factura),0)
                  - IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque WHERE id_factura = cp_factura.id_factura AND tipo = 0 AND anulado IS NULL),0) 
                  ) AS monto_pagar
        FROM cp_factura 
        INNER JOIN cp_proveedor ON cp_factura.id_proveedor = cp_proveedor.id_proveedor
        INNER JOIN pg_empresa ON cp_factura.id_empresa = pg_empresa.id_empresa
        ". $sqlBusq;  //#AND numero_factura_proveedor IN ( '53793', '53802', '53804', '53843', '53847', '53862', '53869', '53876' )
	
    $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);        
	$rsLimit = mysql_query($queryLimit);
        if(!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }
	if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
            $totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "7%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "15%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "2%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI."-".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Doc.");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "fecha_origen", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "numero_factura_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro Documento");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "subtotal_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Sub Total");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "iva_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Iva");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "monto_exento", $campOrd, $tpOrd, $valBusq, $maxRows, "Excento");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "subtotal_descuento", $campOrd, $tpOrd, $valBusq, $maxRows, "Descuento");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "monto_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "retencion_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Retenci&oacute;n Impuesto");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "retencion_islr", $campOrd, $tpOrd, $valBusq, $maxRows, "Retenci&oacute;n ISLR ");
		$htmlTh .= ordenarCampo("xajax_listadoPagos", "5%", $pageNum, "monto_pagar", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto a Pagar");		                
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";			
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";                        
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre'])."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['rif_proveedor']."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['tipo_documento']."</td>";                        
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_origen']))."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['numero_factura_proveedor']."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['subtotal_factura'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['iva_factura'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_exento'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['subtotal_descuento'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_factura'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['retencion_iva'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['retencion_islr'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_pagar'])."</td>";			
		$htmlTb .= "</tr>";
                
		$arrayTotales["subtotal_factura"] += $row['subtotal_factura'];
		$arrayTotales["iva_factura"] += $row['iva_factura'];
		$arrayTotales["monto_exento"] += $row['monto_exento'];
		$arrayTotales["subtotal_descuento"] += $row['subtotal_descuento'];
		$arrayTotales["monto_factura"] += $row['monto_factura'];
		$arrayTotales["retencion_iva"] += $row['retencion_iva'];
		$arrayTotales["retencion_islr"] += $row['retencion_islr'];
		$arrayTotales["monto_pagar"] += $row['monto_pagar'];
	}
        
	if(isset($arrayTotales)){
		$htmlTb.= "<tr align=\"left\" class=\"tituloColumna\" height=\"24\">";                
			$htmlTb .= "<td align=\"center\" colspan=\"6\">Total</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['subtotal_factura'])."</td>";                
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['iva_factura'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['monto_exento'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['subtotal_descuento'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['monto_factura'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['retencion_iva'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['retencion_islr'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['monto_pagar'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoPagos(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagos(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"50\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
        
	$objResponse->assign("tdListadoPagos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listadoPagos2($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 25, $totalRows = NULL){
	$objResponse = new xajaxResponse();
    
	global $spanCI;
	global $spanRIF;	
        
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
        //$valCadBusq[0] = id empresa
        //$valCadBusq[1] = id benef o proveedor(solo se usa proveedor)
        //$valCadBusq[2] = fecha inicio
        //$valCadBusq[3] = fecha fin
        //$valCadBusq[4] = Criterio numero fact/nota
                
//        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
//        $sqlBusq .= $cond."vw_te_retencion_cheque.anulado IS NULL";
        
	if ($valCadBusq[0] == ''){
		//$sqlBusq .= " vw_te_retencion_cheque.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";	
	}else if ($valCadBusq[0] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."cp_notadecargo.id_empresa = '".$valCadBusq[0]."'";
	}
        
	if ($valCadBusq[1] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."cp_notadecargo.id_proveedor = '".$valCadBusq[1]."'";
	}
	
	if ($valCadBusq[2] != '' && $valCadBusq[3] != ''){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(cp_notadecargo.fecha_origen_notacargo) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])),"text"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])),"text")
		);
	}
        
	if ($valCadBusq[4] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		if(strpos($valCadBusq[4],",")){
			$arrayNumeros = implode("','", array_map('trim',explode(",",$valCadBusq[4])));
			$sqlBusq .= $cond."cp_notadecargo.numero_notacargo IN ('".$arrayNumeros."')";
		}else{
			$sqlBusq .= $cond."cp_notadecargo.numero_notacargo LIKE '%".$valCadBusq[4]."%'";
		}
	}
        
	
    $query = "SELECT 
		pg_empresa.nombre_empresa,
		CONCAT_WS('-',cp_proveedor.lrif,cp_proveedor.rif) as rif_proveedor,
		cp_proveedor.nombre,
		'ND' as tipo_documento,
		cp_notadecargo.fecha_origen_notacargo,
		cp_notadecargo.numero_notacargo,
		cp_notadecargo.subtotal_notacargo,
		IFNULL((SELECT SUM(subtotal_iva) FROM cp_notacargo_iva WHERE id_notacargo = cp_notadecargo.id_notacargo),0) AS iva_factura,
		cp_notadecargo.monto_exento_notacargo,
		cp_notadecargo.subtotal_descuento_notacargo,
		(cp_notadecargo.subtotal_notacargo + cp_notadecargo.subtotal_descuento_notacargo + IFNULL((SELECT  SUM(subtotal_iva) FROM cp_notacargo_iva WHERE id_notacargo = cp_notadecargo.id_notacargo),0)) AS monto_factura,
		IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle WHERE id_nota_cargo = cp_notadecargo.id_notacargo),0) AS retencion_iva,
                IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque WHERE id_factura = cp_notadecargo.id_notacargo AND tipo = 1 AND anulado IS NULL),0) AS retencion_islr,		
                
                ((cp_notadecargo.subtotal_notacargo + cp_notadecargo.subtotal_descuento_notacargo + IFNULL((SELECT SUM(subtotal_iva) FROM cp_notacargo_iva WHERE id_notacargo = cp_notadecargo.id_notacargo),0))
                  - cp_notadecargo.subtotal_descuento_notacargo
                  - IFNULL((SELECT SUM(IvaRetenido) FROM cp_retenciondetalle WHERE id_nota_cargo = cp_notadecargo.id_notacargo),0)
                  - IFNULL((SELECT SUM(monto_retenido) FROM te_retencion_cheque WHERE id_factura = cp_notadecargo.id_notacargo AND tipo = 1 AND anulado IS NULL),0) 
                  ) AS monto_pagar
	FROM cp_notadecargo 
        INNER JOIN cp_proveedor ON cp_notadecargo.id_proveedor = cp_proveedor.id_proveedor
        INNER JOIN pg_empresa ON cp_notadecargo.id_empresa = pg_empresa.id_empresa
        ". $sqlBusq;
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);        
	$rsLimit = mysql_query($queryLimit);
        if(!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }
	if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
            $totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "7%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "15%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "2%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI."-".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Doc.");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "fecha_origen_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "numero_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro Documento");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "subtotal_notacargo", $campOrd, $tpOrd, $valBusq, $maxRows, "Sub Total");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "iva_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Iva");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "monto_exento", $campOrd, $tpOrd, $valBusq, $maxRows, "Excento");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "subtotal_descuento", $campOrd, $tpOrd, $valBusq, $maxRows, "Descuento");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "monto_factura", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "retencion_iva", $campOrd, $tpOrd, $valBusq, $maxRows, "Retenci&oacute;n Impuesto");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "retencion_islr", $campOrd, $tpOrd, $valBusq, $maxRows, "Retenci&oacute;n ISLR ");
		$htmlTh .= ordenarCampo("xajax_listadoPagos2", "5%", $pageNum, "monto_pagar", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto a Pagar");		                
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb.= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";			
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";                        
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre'])."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['rif_proveedor']."</td>";                        
			$htmlTb .= "<td align=\"center\">".$row['tipo_documento']."</td>";                        
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_origen_notacargo']))."</td>";                        
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['numero_notacargo'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['subtotal_notacargo'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['iva_factura'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_exento'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['subtotal_descuento'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_factura'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['retencion_iva'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['retencion_islr'])."</td>";			
			$htmlTb .= "<td align=\"center\">".formatoNumero($row['monto_pagar'])."</td>";			
		$htmlTb .= "</tr>";		
                
		$arrayTotales["subtotal_notacargo"] += $row['subtotal_notacargo'];
		$arrayTotales["iva_factura"] += $row['iva_factura'];
		$arrayTotales["monto_exento"] += $row['monto_exento'];
		$arrayTotales["subtotal_descuento"] += $row['subtotal_descuento'];
		$arrayTotales["monto_factura"] += $row['monto_factura'];
		$arrayTotales["retencion_iva"] += $row['retencion_iva'];
		$arrayTotales["retencion_islr"] += $row['retencion_islr'];
		$arrayTotales["monto_pagar"] += $row['monto_pagar'];
	}
        
	if(isset($arrayTotales)){
		$htmlTb.= "<tr align=\"left\" class=\"tituloColumna\" height=\"24\">";                
			$htmlTb .= "<td align=\"center\" colspan=\"6\">Total</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['subtotal_notacargo'])."</td>";                
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['iva_factura'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['monto_exento'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['subtotal_descuento'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['monto_factura'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['retencion_iva'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['retencion_islr'])."</td>";
			$htmlTb .= "<td align=\"center\">".formatoNumero($arrayTotales['monto_pagar'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagos2(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagos2(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoPagos2(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagos2(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPagos2(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"50\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
        
	$objResponse->assign("tdListadoPagos","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function listarBeneficiarios1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	global $spanRIF;
        
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" WHERE CONCAT(lci_rif,'-',ci_rif_beneficiario) LIKE %s
		OR CONCAT(lci_rif,ci_rif_beneficiario) LIKE %s
		OR nombre_beneficiario LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id_beneficiario AS id,
		CONCAT(lci_rif,'-',ci_rif_beneficiario) as rif_beneficiario,
		nombre_beneficiario
	FROM te_beneficiarios %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios1", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("C&oacute;digo"));
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios1", "20%", $pageNum, "rif_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI."/".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listarBeneficiarios1", "65%", $pageNum, "nombre_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" >";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarBeneficiario1('".$row['id']."');\" title=\"Seleccionar Beneficiario\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_beneficiario']."</td>";
			$htmlTb .= "<td>".  utf8_encode($row['nombre_beneficiario'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarBeneficiarios1(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdContenido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("
		byId('tblBeneficiariosProveedores').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Clientes");	
	$objResponse->script("
		if (byId('divFlotante1').style.display == 'none') {
			byId('divFlotante1').style.display = '';
			centrarDiv(byId('divFlotante1'));
			
			document.forms['frmBuscarCliente'].reset();
			byId('txtCriterioBusqBeneficiario').focus();
		}
	");
	
	return $objResponse;
}

function listarProveedores1($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	global $spanRIF;
        
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" WHERE CONCAT(lrif,'-',rif) LIKE %s
		OR CONCAT(lrif,rif) LIKE %s
		OR nombre LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id_proveedor AS id,
		CONCAT_WS('-',lrif,rif) as rif_proveedor,
		nombre
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
		$htmlTh .= ordenarCampo("xajax_listarProveedores1", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listarProveedores1", "20%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI."/".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listarProveedores1", "65%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\" >";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor1('".$row['id']."');\" title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['rif_proveedor']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores1(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdContenido","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	//$objResponse->assign("tdCabeceraEstado","innerHTML","");
	
	$objResponse->script("byId('tblBeneficiariosProveedores').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Clientes");
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if (byId('divFlotante1').style.display == 'none') {
			byId('divFlotante1').style.display = '';
			centrarDiv(byId('divFlotante1'));
			
			document.forms['frmBuscarCliente'].reset();
			byId('txtCriterioBusqProveedor').focus();
		}
	");
	
	return $objResponse;
}

function buscarCliente1($valform,$pro_bene) {
	$objResponse = new xajaxResponse();
		
	if($pro_bene==1){	
		$valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']);
		$objResponse->loadCommands(listarProveedores1(0, "", "", $valBusq));
	}elseif($pro_bene=="0"){
		$valBusq = sprintf("%s",$valform['txtCriterioBusqBeneficiario']);
		$objResponse->loadCommands(listarBeneficiarios1(0, "", "", $valBusq));
	}else{//boton buscar pro_bene es null porque no se envia
		 if($valform['buscarProv']=="1"){
			 $valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']);
			 $objResponse->loadCommands(listarProveedores1(0, "", "", $valBusq));
		 }elseif($valform['buscarProv']=="2"){
			 $valBusq = sprintf("%s",$valform['txtCriterioBusqBeneficiario']);
$objResponse->loadCommands(listarBeneficiarios1(0, "", "", $valBusq));
		 }
	}
	
	return $objResponse;
}

function asignarProveedor1($id_proveedor){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM cp_proveedor WHERE id_proveedor = '".$id_proveedor."'";
	$rs = mysql_query($query);
        if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddBePro","value",$row['id_proveedor']);
	$objResponse->assign("hddSelBePro","value",'1');
	$objResponse->assign("txtBePro","value",  utf8_encode($row['nombre']));
    $objResponse->script("byId('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarBeneficiario1($id_beneficiario){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_beneficiarios WHERE id_beneficiario = '".$id_beneficiario."'";
	$rs = mysql_query($query);
        if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("hddBePro","value",$row['id_beneficiario']);
	$objResponse->assign("hddSelBePro","value",'0');
	$objResponse->assign("txtBePro","value",  utf8_encode($row['nombre_beneficiario']));
    $objResponse->script("byId('divFlotante1').style.display = 'none';");             
	
	return $objResponse;
}

function listEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
			
	if($campOrd == "") { $campOrd = 'id_empresa_reg'; }
        
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ",$_SESSION['idUsuarioSysGts']);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitEmpresa = sprintf(" %s %s LIMIT %d OFFSET %d", $queryEmpresa, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitEmpresa = mysql_query($queryLimitEmpresa);
	if(!$rsLimitEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
	if ($totalRows == NULL) {
		$rsEmpresa = mysql_query($queryEmpresa);
                if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsEmpresa);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";	
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"5%\" align=\"center\"></td>";
		$htmlTh .= ordenarCampo("xajax_listEmpresa", "15%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, "Id Empresa");
		$htmlTh .= ordenarCampo("xajax_listEmpresa", "40%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Empresa");			
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowBanco = mysql_fetch_assoc($rsLimitEmpresa)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align='center'>"."<button type=\"button\" onclick=\"xajax_asignarEmpresa('".$rowBanco['id_empresa_reg']."');\" title=\"Seleccionar Banco\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$rowBanco['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowBanco['nombre_empresa']." - ".$rowBanco['nombre_empresa_suc'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listEmpresa(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult2_tesoreria.gif\"/>");
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"4\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("tdDescripcionArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	$objResponse->script("byId('divFlotante2').style.display = '';
	byId('tblListados2').style.display = '';
	byId('tdFlotanteTitulo2').innerHTML = 'Seleccione Empresa';
	centrarDiv(byId('divFlotante2'))");
	
	return $objResponse;
}

function asignarEmpresa($idEmpresa){
	$objResponse = new xajaxResponse();
	
	if ($idEmpresa==''){
		$idEmpresa=$_SESSION['idEmpresaUsuarioSysGts'];
	}
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
	$rsEmpresa = mysql_query($queryEmpresa);
	if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);

	$nombreSucursal = "";

	if ($rowEmpresa['id_empresa_padre_suc'] > 0){
			$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
	}

	$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);

	$objResponse -> assign("txtNombreEmpresa","value",$empresa);
	$objResponse -> assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
	$objResponse->script("byId('divFlotante2').style.display = 'none';");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarPagos");
$xajax->register(XAJAX_FUNCTION,"listadoPagos");
$xajax->register(XAJAX_FUNCTION,"listadoPagos2");
$xajax->register(XAJAX_FUNCTION,"listarBeneficiarios1");
$xajax->register(XAJAX_FUNCTION,"listarProveedores1");
$xajax->register(XAJAX_FUNCTION,"buscarCliente1");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor1");
$xajax->register(XAJAX_FUNCTION,"asignarBeneficiario1");
$xajax->register(XAJAX_FUNCTION,"listEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");

function tipoDocumento($id){	
	$query = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$id);

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	if($row['tipo_documento'] == 'NC'){
		$queryNC = sprintf("SELECT * FROM te_nota_credito WHERE id_nota_credito = '%s'", $row['id_documento']);
		$rsNC = mysql_query($queryNC);
		if (!$rsNC) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowNC = mysql_fetch_array($rsNC);
		if($rowNC['tipo_nota_credito'] == '1')
			$respuesta = "NC";
		else if($rowNC['tipo_nota_credito'] == '2')
			$respuesta = "NC/TD";
		else if($rowNC['tipo_nota_credito'] == '3')
			$respuesta = "NC/TC";
	}
	if($row['tipo_documento'] == 'ND')
		$respuesta = "ND";
	if($row['tipo_documento'] == 'TR')
		$respuesta = "TR";
	if($row['tipo_documento'] == 'CH')
		$respuesta = "CH";
	if($row['tipo_documento'] == 'DP')
		$respuesta = "DP";
	
	return $respuesta;
}

function formatoNumero($numero){
    return number_format($numero,2,".",",");
}

?>