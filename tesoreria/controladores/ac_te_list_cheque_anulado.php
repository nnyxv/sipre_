<?php 

function asignarDetallesCuenta($idChequera){
	$objResponse = new xajaxResponse();
	
	$queryDetalleCuenta = sprintf("SELECT nombreBanco, numeroCuentaCompania, saldo_tem FROM vw_te_cheques WHERE id_chequera = %s Limit 1",$idChequera);
	$rsDetalleCuenta = mysql_query($queryDetalleCuenta);
        if(!$rsDetalleCuenta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowDetalleCuenta = mysql_fetch_array($rsDetalleCuenta);
	
	$objResponse->assign("txtNombreBanco","value",utf8_encode($rowDetalleCuenta['nombreBanco']));
	$objResponse->assign("tdSelCuentas","innerHTML"," <input type='text' id='selCuenta' name='selCuenta' readonly='readonly' value='".	    $rowDetalleCuenta['numeroCuentaCompania']."' size='25'/>");
	$objResponse->assign("txtSaldoCuenta","value",number_format($rowDetalleCuenta['saldo_tem'],'2','.',','));
	$objResponse->assign("hddSaldoCuenta","value",$rowDetalleCuenta['saldo_tem']);
	
	return $objResponse; 
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

function asignarEmpresa($idEmpresa,$accion){
	$objResponse = new xajaxResponse();
	
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$idEmpresa);
		$rsEmpresa = mysql_query($queryEmpresa);
                if(!$rsEmpresa) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
			
		$nombreSucursal = "";
		
		if ($rowEmpresa['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
		
		$empresa = utf8_encode($rowEmpresa['nombre_empresa'].$nombreSucursal);
		
		$objResponse -> assign("txtNombreEmpresa","value",$empresa);
		$objResponse -> assign("hddIdEmpresa","value",$rowEmpresa['id_empresa_reg']);
		if ($accion == 0){
			$objResponse->assign("txtNombreBanco","value","");
			$objResponse->assign("hddIdBanco","value","-1");
			$objResponse->assign("txtSaldoCuenta","value","");
			$objResponse->assign("hddSaldoCuenta","value","");
			$objResponse->assign("hddIdChequera","value","");
			$objResponse->script("xajax_comboCuentas(xajax.getFormValues('frmCheque'));");
		}
		
		$objResponse->script("document.getElementById('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarBeneficiario($id_beneficiario){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM te_beneficiarios WHERE id_beneficiario = '".$id_beneficiario."'";
	$rs = mysql_query($query);
        if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtIdBeneficiario","value",$row['id_beneficiario']);
	$objResponse->assign("hddBeneficiario_O_Provedor","value","0");
	$objResponse->assign("txtNombreBeneficiario","value",utf8_encode($row['nombre_beneficiario']));
	$objResponse->assign("txtCiRifBeneficiario","value",$row['lci_rif']."-".$row['ci_rif_beneficiario']);

	$objResponse->script("xajax_asignarDetallesRetencion(".$row['idretencion'].")");

    $objResponse->script("document.getElementById('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarProveedor($id_proveedor){
	$objResponse = new xajaxResponse();

	$query = "SELECT * FROM cp_proveedor WHERE id_proveedor = '".$id_proveedor."'";
	$rs = mysql_query($query);
        if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtIdBeneficiario","value",$row['id_proveedor']);
	$objResponse->assign("hddBeneficiario_O_Provedor","value","1");
	$objResponse->assign("txtNombreBeneficiario","value",utf8_encode($row['nombre']));
	$objResponse->assign("txtCiRifBeneficiario","value",$row['lrif']."-".$row['rif']);
	
	$query2 = "SELECT reimpuesto FROM cp_prove_credito WHERE id_proveedor = '".$id_proveedor."'";
	$rs2 = mysql_query($query2);
        if(!$rs2) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row2 = mysql_fetch_array($rs2);
	$objResponse->script("xajax_asignarDetallesRetencion(".$row2['reimpuesto'].")");	
  
    $objResponse->script("document.getElementById('divFlotante1').style.display = 'none';");
	
	return $objResponse;
}

function asignarProveedor2($id_proveedor){//solo para listado
	$objResponse = new xajaxResponse();        
	$query = "SELECT * FROM cp_proveedor WHERE id_proveedor = '".$id_proveedor."'";
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("idProveedorBuscar","value",$row['id_proveedor']);
	$objResponse->assign("nombreProveedorBuscar","value",utf8_encode($row['nombre']));
	$objResponse->script("document.getElementById('divFlotante1').style.display = 'none';
	document.getElementById('btnBuscar').click();");
	
	return $objResponse;
}

function buscarCheque($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$valForm['selEmpresa'],
		$valForm['selEstado'],
		$valForm['txtBusq'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['idProveedorBuscar'],
		$valForm['conceptoBuscar']);
	
	$objResponse->loadCommands(listadoChequeAnulado(0,"fecha_registro","DESC",$valBusq));
		
	return $objResponse;
}

function buscarCliente($valform,$pro_bene = "1") {
	$objResponse = new xajaxResponse();
	
	if($pro_bene=="1"){
		$valBusq = sprintf("%s",$valform['txtCriterioBusqProveedor']."|".$valform['buscarListado']);
		$objResponse->loadCommands(listarProveedores(0, "", "", $valBusq));
	}elseif($pro_bene=="0"){
		$valBusq = sprintf("%s",$valform['txtCriterioBusqBeneficiario']."|".$valform['buscarListado']);
		$objResponse->loadCommands(listarBeneficiarios(0, "", "", $valBusq));
	}
	return $objResponse;
}

function comboEmpresa($idTd, $idSelect, $selId){
	$objResponse = new xajaxResponse();
	
	if ($selId)
		$idEmpresa = $selId;
	else
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
		$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY id_empresa_reg", 
			$_SESSION['idUsuarioSysGts']);
		$rs = mysql_query($query);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		
		$html = "<select id=\"".$idSelect."\" name=\"".$idSelect."\" class=\"inputHabilitado\" onChange=\"xajax_buscarCheque(xajax.getFormValues('frmBuscar'))\">";
		$html .="<option value=\"-1\">[ Todas ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$nombreSucursal = "";
			if ($row['id_empresa_padre_suc'] > 0){
				$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
			}
			
			$selected = "";			
			if ($idEmpresa == $row['id_empresa_reg']){
				$selected = "selected='selected'";
			}
			$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".  utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
		}
	$html .= "</select>";
		
	$objResponse->assign($idTd,"innerHTML",$html);
	
	return $objResponse;
}

function listarProveedores($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
        //$valCadBusq[0] criterio
        //$valCadBusq[1] == "1" si es para buscar en el listado        
        $buscarListado = $valCadBusq[1];
	
	$sqlBusq = sprintf(" WHERE CONCAT(lrif,'-',rif) LIKE %s
		OR CONCAT(lrif,rif) LIKE %s
		OR nombre LIKE %s",
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT
		id_proveedor AS id,
		CONCAT(lrif,'-',rif) as rif_proveedor,
		nombre
	FROM cp_proveedor %s", $sqlBusq);	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "20%", $pageNum, "rif_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Cedula / RIF.");
		$htmlTh .= ordenarCampo("xajax_listarProveedores", "65%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
                
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarProveedor2('".$row['id']."');\"  title=\"Seleccionar Proveedor\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarProveedores(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarProveedores(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->script("		
		document.getElementById('tblBeneficiariosProveedores').style.display = '';");
	
	$objResponse->assign("tblListados","width","600");
	$objResponse->script("
		if (document.getElementById('divFlotante1').style.display == 'none') {
			document.getElementById('divFlotante1').style.display = '';
			centrarDiv(document.getElementById('divFlotante1'));
			
			document.forms['frmBuscarCliente'].reset();
			document.getElementById('txtCriterioBusqProveedor').focus();
		}
	");
	
	return $objResponse;
}

function listadoChequeAnulado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();	
	$objResponse->setCharacterEncoding('UTF-8');
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("te_cheques_anulados.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	/*if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("estado_documento = %s",
			valTpDato($valCadBusq[1], "int"));
	}*/
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("numero_cheque LIKE %s",
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}
	
	if ($valCadBusq[3] != "" && $valCadBusq[4] !=""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fecha_cheque BETWEEN %s AND %s",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[3])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "date")); 
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_beneficiario_proveedor = %s",
			valTpDato($valCadBusq[5], "int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("concepto LIKE %s",
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		te_cheques_anulados.*,
		cuentas.numeroCuentaCompania, 
		bancos.nombreBanco, 
		pg_empresa.nombre_empresa, 
		cp_proveedor.nombre
	FROM te_cheques_anulados
		LEFT JOIN cp_proveedor ON te_cheques_anulados.id_beneficiario_proveedor = cp_proveedor.id_proveedor 
		INNER JOIN pg_empresa ON te_cheques_anulados.id_empresa = pg_empresa.id_empresa	
		INNER JOIN te_chequeras ON te_cheques_anulados.id_chequera = te_chequeras.id_chq
		INNER JOIN cuentas ON te_chequeras.id_cuenta = cuentas.idCuentas
		INNER JOIN bancos ON cuentas.idBanco = bancos.idBanco %s", $sqlBusq);
	
	//$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd." ".$tpOrd.",numero_cheque", $tpOrd) : "";        
	if($campOrd == "fecha_cheque" || $campOrd == "fecha_registro"){//agrupar por fecha y luego ordenar por numero
		$campOrd2 = $campOrd." ".$tpOrd.", numero_cheque DESC";
	}else{
		$campOrd2 = $campOrd." ".$tpOrd;
	}
        
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s ", $campOrd2) : "";
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);        
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$queryLimit); }
	
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$queryLimit); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
        
        $htmlTh .= "<tr class=\"tituloColumna\">";
            $htmlTh .= ordenarCampo("xajax_listadoChequeAnulado", "10%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
            $htmlTh .= ordenarCampo("xajax_listadoChequeAnulado", "10%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor");
            $htmlTh .= ordenarCampo("xajax_listadoChequeAnulado", "6%", $pageNum, "numero_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "N&uacute;mero Cheque");			
            $htmlTh .= ordenarCampo("xajax_listadoChequeAnulado", "8%", $pageNum, "monto_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Cheque");
            $htmlTh .= ordenarCampo("xajax_listadoChequeAnulado", "7%", $pageNum, "fecha_cheque", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Cheque");			
            $htmlTh .= ordenarCampo("xajax_listadoChequeAnulado", "7%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Anulado");
            $htmlTh .= ordenarCampo("xajax_listadoChequeAnulado", "40%", $pageNum, "concepto", $campOrd, $tpOrd, $valBusq, $maxRows, "Concepto");			
            $htmlTh .= ordenarCampo("xajax_listadoChequeAnulado", "15%", $pageNum, "nombreBanco", $campOrd, $tpOrd, $valBusq, $maxRows, "Banco Compa&ntilde;ia");			
            $htmlTh .= ordenarCampo("xajax_listadoChequeAnulado", "15%", $pageNum, "numeroCuentaCompania", $campOrd, $tpOrd, $valBusq, $maxRows, "Cuenta Compa&ntilde;ia");			
            $htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
        
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_cheque']."</td>";
			$htmlTb .= "<td align=\"center\">".number_format($row['monto_cheque'],2,".",",")."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_cheque']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['concepto'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombreBanco'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['numeroCuentaCompania'])."</td>";
			$htmlTb .= "<td align=\"center\" title=\"Ver Cheque\"><img class=\"puntero\" onclick=\"xajax_verCheque(".$row['id_cheque']."); \" src=\"../img/iconos/ico_view.png\" ></td>";

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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoChequeAnulado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoChequeAnulado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoChequeAnulado(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoChequeAnulado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoChequeAnulado(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
        $objResponse->assign("tdListadoCheques","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}

function verCheque($idCheque){
	$objResponse = new xajaxResponse();

	$queryCheque = sprintf("SELECT * FROM te_cheques_anulados WHERE id_cheque = %s",$idCheque);
	$rsCheque = mysql_query($queryCheque);
	$rowCheque = mysql_fetch_array($rsCheque);

	$objResponse->script("xajax_asignarEmpresa(".$rowCheque['id_empresa'].",1)");
	$objResponse->script("xajax_asignarDetallesCuenta(".$rowCheque['id_chequera'].")");
	if($rowCheque['beneficiario_proveedor'] == 0){
		$objResponse->script("xajax_asignarBeneficiario(".$rowCheque['id_beneficiario_proveedor'].")");
	}else{
		$objResponse->script("xajax_asignarProveedor(".$rowCheque['id_beneficiario_proveedor'].")");            
	}
        
	$objResponse->assign("txtFechaRegistro","value",$rowCheque['fecha_registro']);
	$objResponse->assign("numCheque","value",$rowCheque['numero_cheque']);
	$objResponse->assign("txtFechaLiberacion","value",$rowCheque['fecha_liberacion']);
	$objResponse->assign("hddIdCheque","value",$rowCheque['id_cheque']);
	$objResponse->assign("txtConcepto","value",utf8_encode($rowCheque['concepto']));
	$objResponse->assign("txtComentario","value",utf8_encode($rowCheque['observacion']));
	$objResponse->assign("txtMonto","value",number_format($rowCheque['monto_cheque'],'2',',','.'));
        
	if ($rowCheque['entregado']){
		$checked = "checked";
	}else{
		$checked = "";
	}
        
	if($rowCheque['id_factura']){            
		if($rowCheque['tipo_documento'] == 0){ //factura            
			$queryFactura = sprintf("SELECT numero_factura_proveedor,	fecha_origen, fecha_vencimiento, observacion_factura FROM cp_factura WHERE id_factura = '%s'",$rowCheque['id_factura']);
			$rsFactura = mysql_query($queryFactura);			
			if (!$rsFactura) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }			
			$rowFactura = mysql_fetch_assoc($rsFactura);
			
			$objResponse->assign("txtIdFactura","value",$rowCheque['id_factura']);
			$objResponse->assign("txtNumeroFactura","value",$rowFactura['numero_factura_proveedor']);
			$objResponse->assign("txtFechaRegistroFactura","value",$rowFactura['fecha_origen']);
			$objResponse->assign("txtFechaVencimientoFactura","value",$rowFactura['fecha_vencimiento']);
			$objResponse->assign("txtDescripcionFactura","innerHTML",$rowFactura['observacion_factura']);
			$objResponse->assign("txtSaldoFactura","value",number_format($rowCheque['monto_cheque'],'2',',','.'));
			$objResponse->assign("tdFacturaNota","innerHTML","FACTURA");
                
		}else{ //nota de cargo
			$queryNota = sprintf("SELECT numero_notacargo,	fecha_origen_notacargo, fecha_vencimiento_notacargo, observacion_notacargo FROM cp_notadecargo WHERE id_notacargo = '%s'",$rowCheque['id_factura']);
			$rsNota = mysql_query($queryNota);		
			if (!$rsNota) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }		
			$rowNota = mysql_fetch_assoc($rsNota);
				
			$objResponse->assign("txtIdFactura","value",$rowCheque['id_factura']);
			$objResponse->assign("txtNumeroFactura","value",utf8_encode($rowNota['numero_notacargo']));
			$objResponse->assign("txtFechaRegistroFactura","value",$rowNota['fecha_origen_notacargo']);
			$objResponse->assign("txtFechaVencimientoFactura","value",$rowNota['fecha_vencimiento_notacargo']);
			$objResponse->assign("txtDescripcionFactura","innerHTML",utf8_encode($rowNota['observacion_notacargo']));
			$objResponse->assign("tdFacturaNota","innerHTML","NOTA DE CARGO");
		}

		$objResponse->script("document.getElementById('tdTxtSaldoFactura').style.display = 'none';
							  document.getElementById('tdSaldoFactura').style.display = 'none';");
	}else{// sino posee documento
		$objResponse->assign("txtIdFactura","value","");
		$objResponse->assign("txtNumeroFactura","value","");
		$objResponse->assign("txtFechaRegistroFactura","value","");
		$objResponse->assign("txtFechaVencimientoFactura","value","");
		$objResponse->assign("txtDescripcionFactura","innerHTML","");
		$objResponse->assign("txtSaldoFactura","value","");
            
		$queryTienePropuesta = sprintf("SELECT * FROM te_cheques_anulados_detalle WHERE id_cheque = %s LIMIT 1",
											$idCheque);
		$rsTienePropuesta = mysql_query($queryTienePropuesta);
		if (!$rsTienePropuesta) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }

		if(mysql_num_rows($rsTienePropuesta)){
			$rowTienePropuesta = mysql_fetch_assoc($rsTienePropuesta);
			$imagen = sprintf("<img src='../img/iconos/ico_view.png' style='vertical-align: top;' onclick='limpiarPropuesta(); xajax_verPropuesta(%s);' class='puntero'>",
							valTpDato($rowTienePropuesta['id_cheque'],"int"));
			$objResponse->assign("tdFacturaNota","innerHTML","PROPUESTA DE PAGO ".$imagen);
			
		}else{
			$objResponse->assign("tdFacturaNota","innerHTML","SIN DOCUMENTO");
		}
	}
	$objResponse->script("						 
	document.getElementById('trChequeEntregado').style.display = '';
	document.getElementById('cbxChequeEntregado').checked = '".$checked."';
	document.getElementById('divFlotante').style.display = '';
	centrarDiv(document.getElementById('divFlotante'));
	document.getElementById('tdFlotanteTitulo').innerHTML = 'Ver Cheque';
	document.getElementById('trSaldoCuenta').style.display = 'none';");
	
	return $objResponse;
}

function verPropuesta($idCheque){
    $objResponse = new xajaxResponse();
    
    $queryPropuesta = sprintf("SELECT numero_cheque 
	FROM te_cheques_anulados
	WHERE id_cheque = %s LIMIT 1",
		$idCheque);    
    $rsPropuesta = mysql_query($queryPropuesta);
    if (!$rsPropuesta) { return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }    
    $rowPropuesta = mysql_fetch_assoc($rsPropuesta);
    
    $queryDetalle = sprintf("SELECT 
	te_cheques_anulados_detalle.id_factura, 
	te_cheques_anulados_detalle.monto_pagar, 
	te_cheques_anulados_detalle.sustraendo_retencion, 
	te_cheques_anulados_detalle.porcentaje_retencion, 
	te_cheques_anulados_detalle.monto_retenido,                            
	te_cheques_anulados_detalle.tipo_documento,
	IF(te_cheques_anulados_detalle.tipo_documento = 0, 
		(SELECT numero_factura_proveedor FROM cp_factura WHERE cp_factura.id_factura = te_cheques_anulados_detalle.id_factura),
		(SELECT numero_notacargo FROM cp_notadecargo WHERE cp_notadecargo.id_notacargo = te_cheques_anulados_detalle.id_factura)) as numero_documento
	FROM te_cheques_anulados_detalle 
	WHERE id_cheque = %s",
		$idCheque);    
    $rsDetalle = mysql_query($queryDetalle);
    if (!$rsDetalle) { return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__); }
    
    $tabla = "";
    $tabla .= "<table class='tabla-propuesta'>";
    
    $tabla .= "<tr>";
    $tabla .= "<th>Tipo Documento</th>";
    $tabla .= "<th>N&uacute;mero Documento</th>";
    $tabla .= "<th>Monto a Pagar</th>";
    $tabla .= "<th>Sustraendo Retenci&oacute;n</th>";
    $tabla .= "<th>Porcentaje Retenci&oacute;n</th>";
    $tabla .= "<th>Monto Retenido</th>";
    $tabla .= "<th>Codigo</th>";
    $tabla .= "</tr>";
    
    while($rowDetalle = mysql_fetch_assoc($rsDetalle)){
        if($rowDetalle['tipo_documento'] == 0){
            $tipoDocumento = "FACTURA";
        }else{
            $tipoDocumento = "NOTA DE CARGO";
        }
        
        $tabla .= "<tr>";    
        $tabla .= "<td>".$tipoDocumento."</td>";
        $tabla .= "<td idFacturaNotaOculta='".$rowDetalle['id_factura']."'>".$rowDetalle['numero_documento']."</td>";
        $tabla .= "<td>".$rowDetalle['monto_pagar']."</td>";
        $tabla .= "<td>".$rowDetalle['sustraendo_retencion']."</td>";
        $tabla .= "<td>".$rowDetalle['porcentaje_retencion']."</td>";
        $tabla .= "<td>".$rowDetalle['monto_retenido']."</td>";
        $tabla .= "<td>".$rowDetalle['codigo']."</td>";        
        $tabla .= "</tr>";
    }
    
    $tabla .= "</table>";
    
	$estado = "APROBADA";
    
    $objResponse->assign("numeroPropuestaPago","innerHTML","-");
    $objResponse->assign("fechaPropuestaPago","innerHTML","-");
    $objResponse->assign("numeroChequePropuestaPago","innerHTML",$rowPropuesta['numero_cheque']);
    $objResponse->assign("estadoPropuestaPago","innerHTML",$estado);
    
    $objResponse->assign("detallePropuestaPago","innerHTML",$tabla);    
    
    $objResponse->script("document.getElementById('divFlotante3').style.display = '';
	centrarDiv(document.getElementById('divFlotante3'));");
	
    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"listadoChequeAnulado");
$xajax->register(XAJAX_FUNCTION,"buscarCheque");
$xajax->register(XAJAX_FUNCTION,"comboEmpresa");
$xajax->register(XAJAX_FUNCTION,"verCheque");
$xajax->register(XAJAX_FUNCTION,"verPropuesta");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesRetencion");
$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"asignarBeneficiario");
$xajax->register(XAJAX_FUNCTION,"asignarBeneficiario");
$xajax->register(XAJAX_FUNCTION,"asignarDetallesCuenta");
$xajax->register(XAJAX_FUNCTION,"listarProveedores");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor2");

?>