<?php
function CargarDatos($id_con) {
	$objResponse = new xajaxResponse();
	
/*	$queryCargarCon = "SELECT * FROM te_conciliacion WHERE id_conciliacion = ".$id_con."";
	$rsCargarCon = mysql_query($queryCargarCon);
	if ($rsCargarCon) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n".$queryCargarCon); 
	$rowCargarCon = mysql_fetch_array($rsCargarCon);*/
	
	
	$query = sprintf("SELECT * FROM te_conciliacion WHERE id_conciliacion = '%s'",$id_con);
	$rs = mysql_query($query) or die(mysql_error($query));
	$rowCargarCon = mysql_fetch_array($rs);
	
	
	
	$objResponse->assign("txtFecha","value",$rowCargarCon['fecha']);
	$objResponse->assign("txtEmpresa","value",empresa($rowCargarCon['id_empresa']));
	$objResponse->assign("hddIdEmpresa","value",$rowCargarCon['id_empresa']);
	$objResponse->assign("txtBanco","value",nombreBanco($rowCargarCon['id_cuenta']));//no es el id_banco, es el de lacuenta, corregido
	$objResponse->assign("hddIdBanco","value",$rowCargarCon['id_banco']);
	$objResponse->assign("txtCuenta","value",cuenta($rowCargarCon['id_cuenta']));
	$objResponse->assign("hddIdCuenta","value",$rowCargarCon['id_cuenta']);
	
	return $objResponse;
}



function buscarEstadoCuenta($id_con,$valForm) {
	$objResponse = new xajaxResponse();	
	
	$query = sprintf("SELECT * FROM te_conciliacion WHERE id_conciliacion = '%s'",$id_con);
	$rs = mysql_query($query) or die(mysql_error($query));
	$rowCargarCon = mysql_fetch_array($rs);
	
	$objResponse->script(sprintf("xajax_listadoEstadoCuentaAplicados(0,'fecha_registro','DESC','%s' + '|' + '%s' + '|' + '%s' + '|' + '%s' + '|' + '%s');",
		$rowCargarCon['id_cuenta'],
		$rowCargarCon['id_empresa'],
		$rowCargarCon['monto_conciliado'],
                $valForm['fechaAplicada1'],
                $valForm['fechaAplicada2']
		));	
	
	
	return $objResponse;
}
	
function listadoEstadoCuentaAplicados($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$id= $_GET['id_con'];
	$query = sprintf("SELECT * FROM te_conciliacion WHERE id_conciliacion = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error($query));
	$rowCargarCon = mysql_fetch_array($rs);
	
	if ($valCadBusq[1] == '')
		$sqlBusq .= " AND te_estado_cuenta.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";
	
	else if ($valCadBusq[1] != '')
		$sqlBusq .= " AND te_estado_cuenta.id_empresa = '".$valCadBusq[1]."'";
		
	if ($valCadBusq[0] != 0)
		$sqlBusq .= " AND te_estado_cuenta.id_cuenta = '".$valCadBusq[0]."'";
        
        
        if($valCadBusq[3] !="" && $valCadBusq[4] !=""){
            $fecha1 = date("Y-m-d",strtotime($valCadBusq[3]));
            $fecha2 = date("Y-m-d",strtotime($valCadBusq[4]));
		$sqlBusq .= sprintf(" AND DATE(te_estado_cuenta.fecha_registro) BETWEEN '%s' AND '%s'",
                        $fecha1,
                        $fecha2);
        }
	
	//$objResponse -> alert($valCadBusq[0]);
	//$objResponse -> alert($valCadBusq[1]);
	/*if ($valCadBusq[2] != '00-00-000')
		$sqlBusq .= " AND DATE_FORMAT(te_estado_cuenta.fecha_registro,'%m-%Y') = '".$valCadBusq[2]."'";*/
		
	/*if($valCadBusq[2] == 2)
		$sqlBusq .= " AND te_estado_cuenta.estados_principales = '".$valCadBusq[2]."'";*/
	/*else if($valCadBusq[3] == 2)
		$sqlBusq .= " AND te_estado_cuenta.estados_principales = '".$valCadBusq[3]."'";*/
	
	$query = sprintf("SELECT 
						  te_estado_cuenta.id_estado_cuenta,
						  te_estado_cuenta.tipo_documento,
						  te_estado_cuenta.id_documento,
						  te_estado_cuenta.fecha_registro,
						  te_estado_cuenta.id_cuenta,
						  te_estado_cuenta.id_empresa,
						  te_estado_cuenta.monto,
						  te_estado_cuenta.suma_resta,
						  te_estado_cuenta.numero_documento,
						  te_estado_cuenta.desincorporado,
						  te_estado_cuenta.observacion,
						  te_estado_cuenta.estados_principales,
                                                  #SOLO PARA ORDENAMIENTO
                                                  if(suma_resta = 0, monto, 0)as debito,                                                  
                                                  if(suma_resta = 1, monto, 0)as credito 
						FROM
						  te_estado_cuenta
						WHERE
						  te_estado_cuenta.desincorporado <> 0 AND te_estado_cuenta.estados_principales = 2").$sqlBusq;
	
        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
        
	$rsLimit = mysql_query($queryLimit) or die(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$querySaldoIni = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valCadBusq[0]);
	$rsSaldoIni = mysql_query($querySaldoIni);
	if (!$rsSaldoIni) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowSaldoIni = mysql_fetch_array($rsSaldoIni);
	
	$htmlSalIni = "<table border=\"0\" width=\"100%\">";
	$htmlSalIni .= "<tr>";
		$htmlSalIni .= "<td align=\"right\">";
			$htmlSalIni .= "<table class=\"tabla\" border=\"0\" cellpadding=\"2\">";
			$htmlSalIni .= "<tr align=\"right\">";
				$htmlSalIni .= "<td width=\"100\" class=\"tituloCampo\" style=\"border:0px;\">Saldo:</td>";
				$htmlSalIni .= "<td><input style=\"text-align:right\" type=\"text\" id=\"txtSaldoInicial\" name=\"txtSaldoInicial\" size=\"25\" readonly=\"readonly\" value = \"".$rowSaldoIni['saldo']."\"/>
									<input style=\"text-align:right\" type=\"hidden\" id=\"hddSaldoInicial\" name=\"hddSaldoInicial\" size=\"25\" value = \"".$rowSaldoIni['saldo']."\"/></td>";
			$htmlSalIni .= "</tr>";
			$htmlSalIni .= "</table>";
		$htmlSalIni .= "</td>";
	$htmlSalIni .= "</tr>";
	$htmlSalIni .= "</table>";
        
        $htmlTblIni .= "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "8%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Documento");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "5%", $pageNum, "estados_principales", $campOrd, $tpOrd, $valBusq, $maxRows, "Estados");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "15%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "20%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Documento");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "3%", $pageNum, "debito", $campOrd, $tpOrd, $valBusq, $maxRows, "D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listadoEstadoCuentaAplicados", "3%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Cr&eacute;dito");
		
		$htmlTh .="<td width=\"3%\"></td>
                            <td width=\"3%\"></td>";
		$htmlTh .= "</tr>";
	
	$conta = 0;
	$contb = 0;
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".date("d/m/Y",strtotime($row['fecha_registro']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo_documento']."</td>";
			$htmlTb .= "<td align=\"center\">".estadoNota($row['estados_principales'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_documento']."</td>";
			if($row['suma_resta'] == 0){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$conta +=  $row['monto'];
			}else
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			if($row['suma_resta'] == 1){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],'2','.',',')."</td>";
				$contb +=  $row['monto'];
			}else
				$htmlTb .= "<td align=\"right\">".number_format(0,'2','.',',')."</td>";
			
			$htmlTb .= "<td align=\"center\"><input type=\"checkbox\" name=\"checkbox[]\" id=\"checkbox\" onclick=\"xajax_agregarIdOculto(xajax.getFormValues('frmListadoEstadoCuenta1'));\" value=\"".$row['id_estado_cuenta']."\"/></td>";
			
			$queryAuditoriaM = sprintf("SELECT * FROM te_auditoria_aplicacion WHERE id_estado_de_cuenta = '%s'",$row['id_estado_cuenta']);
			$rsAuditoriaM = mysql_query($queryAuditoriaM);
			if (!$rsAuditoriaM) return $objResponse->alert(mysql_error());
			$rowAuditoriaM = mysql_fetch_array($rsAuditoriaM);
			if($rowAuditoriaM['id_auditoria_aplicacion'] == ''){
				$htmlTb .= "<td align=\"center\" ><img src=\"../img/iconos/ico_comentario_f2.png\" /></td>";
			}
			else{
				$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" onclick=\"xajax_listAuditoria('0','','','".$row['id_estado_cuenta']."');\" src=\"../img/iconos/ico_comentario.png\" /></td>";
			}
				
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuentaAplicados(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuentaAplicados(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEstadoCuentaAplicados(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuentaAplicados(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEstadoCuentaAplicados(%s,'%s','%s','%s',%s);\">%s</a>",
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
	$htmlTblFin .= "<br><br>";
	
	$queryTotales = sprintf("SELECT 
						  te_estado_cuenta.id_estado_cuenta,
						  te_estado_cuenta.tipo_documento,
						  te_estado_cuenta.id_documento,
						  te_estado_cuenta.fecha_registro,
						  te_estado_cuenta.id_cuenta,
						  te_estado_cuenta.id_empresa,
						  te_estado_cuenta.monto,
						  te_estado_cuenta.suma_resta,
						  te_estado_cuenta.numero_documento,
						  te_estado_cuenta.desincorporado,
						  te_estado_cuenta.observacion,
						  te_estado_cuenta.estados_principales
						 FROM
						  te_estado_cuenta
						WHERE
						  te_estado_cuenta.desincorporado <> '0' AND te_estado_cuenta.estados_principales = '2'").$sqlBusq;
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	while($rowTotales = mysql_fetch_array($rsTotales)){
	
		if($rowTotales['suma_resta'] == 0)
					$contTotales1 +=  $rowTotales['monto'];
		if($rowTotales['suma_resta'] == 1)
					$contTotales2 +=  $rowTotales['monto'];
	}
	
	
	
	$queryCuentas = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valCadBusq[1]);
	$rsCuentas = mysql_query($queryCuentas);
	if(!$rsCuentas) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowCuentas = mysql_fetch_array($rsCuentas);
	/**/
	
	$saldoTotal = ($rowCuentas['saldo'] + $contTotales2) - $contTotales1; 
	
	$htmlx.="<table align=\"right\"  border=\"0\" width=\"60%\">";
		$htmlx.="<tr class=\"tituloColumna\">
					<td width=\"20%\"></td>
					<td align=\"center\" width=\"10%\">"."D&eacute;bito"."</td>
					<td align=\"center\" width=\"10%\">"."Cr&eacute;dito"."</td>
					<td align=\"center\" width=\"10%\">"."Saldo"."</td>
					<td align=\"center\" width=\"10%\">"."Saldo Banco"."</td>
				</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\"><input name=\"txtDebito\" id=\"txtDebito\" type=\"text\" readonly=\"readonly\"/>
										 <input name=\"hddDebito\" id=\"hddDebito\" type=\"hidden\"/>
					</td>";
			$htmlx.="<td align=\"right\"><input name=\"txtCredito\" id=\"txtCredito\" type=\"text\" readonly=\"readonly\"/>
									     <input name=\"hddCredito\" id=\"hddCredito\" type=\"hidden\" />
					 </td>";
			$htmlx.="<td align=\"right\"><input name=\"txtSaldo\" id=\"txtSaldo\" type=\"text\" readonly=\"readonly\" value = \"".$rowSaldoIni['saldo']."\"/>
										 <input name=\"hddSaldo\" id=\"hddSaldo\" type=\"hidden\"  value = \"".$rowSaldoIni['saldo']."\"/>
										 <input name=\"hddSaldoLibro\" id=\"hddSaldoLibro\" type=\"hidden\"  value = \"".$rowSaldoIni['saldo_tem']."\"/>
					</td>";
			$htmlx.="<td align=\"right\"><input name=\"txtSaldoBanco\" id=\"txtSaldoBanco\" type=\"text\" readonly=\"readonly\" value = \"".$rowCargarCon['saldo_banco']."\"  />
										 <input name=\"hddSaldoBanco\" id=\"hddSaldoBanco\" type=\"hidden\" value = \"".$rowCargarCon['saldo_banco']."\" />
					 </td>"; //Cambiar por la Variable q venga por get con el monto q tenga el banco para el momento de la conciliacion
		$htmlx.="</tr>";
	$htmlx.="</table>";
	
	
	
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
	
	$htmlx1.="<br><br><br><br><br><br><table align=\"right\" width=\"100%\">";
	$htmlx1.="<tr>";
	$htmlx1.="<td align=\"right\"><button type=\"button\" id=\"btnGuardarConcilia\" name=\"btnGuardarConcilia\" onclick=\"xajax_guardarConciliacion(xajax.getFormValues('frmListadoEstadoCuenta1'),xajax.getFormValues('frmBuscar'));\">Guardar</button></td>";
	$htmlx1.="<td align=\"left\"><button type=\"button\" id=\"btnCancela\" name=\"btnCancela\" onclick=\"window.open('te_conciliacion.php','_self')\">Cancelar</button></td>";
	$htmlx1.="</tr>";
	$htmlx1.="</table>";
	
        $objResponse->assign("tdListadoEstadoCuenta1","innerHTML",$htmlSalIni.$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlx.$htmlx1);
	//$objResponse->assign("tdListadoEstadoCuenta1","innerHTML",$htmlSalIni.$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin.$htmlx.$htmlx1);
	
	$objResponse->script("xajax_asignarCuentaEmpresa(xajax.getFormValues('frmBuscar'));");
	
	return $objResponse;
}

function agregarIdOculto($valForm){
 	$objResponse = new xajaxResponse();
	
	/*Con esto traigo solo aquellos checkbox q esten habilitados*/
	
	/*------ Empieza ---------*/
	
	if (isset($valForm['checkbox'])) {
		foreach($valForm['checkbox'] as $indiceItm=>$valorItm) {
			$arrayObj[] = $valorItm;
		}
	}
	
	$cadena = "";
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			$cadena .= "|".$valor;
		}
	}
	$objResponse->assign("hddObj","value",$cadena);
	
	/*------- Termina ---------*/
	
	$objResponse->script("xajax_actualizaSumaSaldo(xajax.getFormValues('frmListadoEstadoCuenta1'))");
	
	return $objResponse;
}

function actualizaSumaSaldo($valForm){
	$objResponse = new xajaxResponse();
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	for ($cont = 0; $cont <= strlen($valForm['hddObj']); $cont++){
		$caracter = substr($valForm['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
				
		}else {
			
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	$id= $_GET['id_con'];
	$query = sprintf("SELECT * FROM te_conciliacion WHERE id_conciliacion = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error($query));
	$rowCargarCon = mysql_fetch_array($rs);
	
	$montoSaldoTotal = 0;
	$montoSaldoSuma = $rowCargarCon['total_credito'];
	$montoSaldoResta = $rowCargarCon['total_debito'];
	
	
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if(isset($valor)){
				$queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$valor);
				$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
				if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error());
				$rowEstadoCuenta = mysql_fetch_array($rsEstadoCuenta);
				
				if($rowEstadoCuenta['suma_resta'] == 1)
					$montoSaldoSuma += $rowEstadoCuenta['monto'];
					
				else if($rowEstadoCuenta['suma_resta'] == 0)
					$montoSaldoResta += $rowEstadoCuenta['monto'];
					
				
			}
		}
	}
		
	$montoSaldoTotal = $montoSaldoSuma - $montoSaldoResta + $rowCargarCon['saldo_ant']; 

	$objResponse -> assign("txtDebito","value",number_format($montoSaldoResta,2,",","."));
	$objResponse -> assign("txtCredito","value",number_format($montoSaldoSuma,2,",","."));
	$objResponse -> assign("txtSaldo","value",number_format($montoSaldoTotal,2,",","."));
	$objResponse -> assign("hddDebito","value",$montoSaldoResta);
	$objResponse -> assign("hddCredito","value",$montoSaldoSuma);
	$objResponse -> assign("hddSaldo","value",$montoSaldoTotal);
	
	
	return $objResponse;
}

function guardarConciliacion($valForm,$valFormBuscar){
	$objResponse = new xajaxResponse();
	
	
	mysql_query("START TRANSACTION;");

	
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	for ($cont = 0; $cont <= strlen($valForm['hddObj']); $cont++){
		$caracter = substr($valForm['hddObj'], $cont, 1);
		
		if ($caracter != "|" && $caracter != ""){
			$cadena .= $caracter;
				
		}else {
			
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
			$queryCuenta = sprintf("SELECT saldo FROM cuentas WHERE idCuentas = '%s'",$valFormBuscar['hddIdCuenta']);
			$rsCuenta = mysql_query($queryCuenta) or die(mysql_error());
			$rowCuenta = mysql_fetch_array($rsCuenta);
	
			$idConciliacion = $_GET['id_con'];
	
            $queryConciliacion = sprintf("UPDATE te_conciliacion SET monto_conciliado = '%s', total_debito = '%s', total_credito = '%s', monto_libro = '%s' WHERE id_conciliacion = '%s'",$valForm['hddSaldo'],$valForm['hddDebito'],$valForm['hddCredito'],$valForm['hddSaldoLibro'],$idConciliacion);
            $rsConciliacion = mysql_query($queryConciliacion);
            if (!$rsConciliacion) return $objResponse->alert(mysql_error());
            
	
	

	
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
                    if(isset($valor)){
                        $queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$valor);
                        $rsEstadoCuenta = mysql_query($queryEstadoCuenta);
                        
                        if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error());
                        $rowEstadoCuenta = mysql_fetch_array($rsEstadoCuenta);

                       
                        if($rowEstadoCuenta['tipo_documento'] == 'DP' ){
				

                            $queryActualiza = sprintf("UPDATE te_depositos SET estado_documento = '%s', fecha_conciliacion = NOW() WHERE id_deposito = '%s'",3,$rowEstadoCuenta['id_documento']);
                            
                            $rsActualiza = mysql_query($queryActualiza);
                            if (!$rsActualiza) return $objResponse->alert(mysql_error());

                           $queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', id_conciliacion = '%s' WHERE id_estado_cuenta = '%s'",3,$idConciliacion,$valor);
                           
                            $rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
                            if (!$rsEstadoCuentaActualiza) return $objResponse->alert(mysql_error());

                        }
                        if($rowEstadoCuenta['tipo_documento'] == 'NC'){
                            //$objResponse -> alert($rowEstadoCuenta['tipo_documento']);

                            $queryActualiza = sprintf("UPDATE te_nota_credito SET estado_documento = '%s', fecha_conciliacion = NOW() WHERE id_nota_credito = '%s'",3,$rowEstadoCuenta['id_documento']);
                            $rsActualiza = mysql_query($queryActualiza);
                            if (!$rsActualiza) return $objResponse->alert(mysql_error());

                            $queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', id_conciliacion = '%s' WHERE id_estado_cuenta = '%s'",3,$idConciliacion,$valor);
                            $rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
                            if (!$rsEstadoCuentaActualiza) return $objResponse->alert(mysql_error());

                        }
                        if($rowEstadoCuenta['tipo_documento'] == 'ND'){
                            //$objResponse -> alert($rowEstadoCuenta['tipo_documento']);

                            $queryActualiza = sprintf("UPDATE te_nota_debito  SET estado_documento = '%s', fecha_conciliacion = NOW() WHERE id_nota_debito  = '%s'",3,$rowEstadoCuenta['id_documento']);
                            $rsActualiza = mysql_query($queryActualiza);
                            if (!$rsActualiza) return $objResponse->alert(mysql_error());

                            $queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', id_conciliacion = '%s' WHERE id_estado_cuenta = '%s'",3,$idConciliacion,$valor);
                            $rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
                            if (!$rsEstadoCuentaActualiza) return $objResponse->alert(mysql_error());

                        }
                        if($rowEstadoCuenta['tipo_documento'] == 'CH'){
                            //$objResponse -> alert($rowEstadoCuenta['tipo_documento']);	
							
							
							////////////////////	
												
/*							$querySaldoCuenta = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$rowEstadoCuenta['id_cuenta']);
							$rsSaldoCuenta = mysql_query($querySaldoCuenta);
							if (!$rsSaldoCuenta) return $objResponse->alert(mysql_error());
							$rowSaldoCuenta = mysql_fetch_array($rsSaldoCuenta);
							$sumaDepositoCuenta = $rowSaldoCuenta['saldo'] + $rowEstadoCuenta['monto'];
							$Diferido = $rowSaldoCuenta['Diferido'] - $rowEstadoCuenta['monto'];
							$queryCuentaActualiza = sprintf("UPDATE cuentas SET saldo = '%s', Diferido = '%s'  WHERE idCuentas = '%s'", $sumaDepositoCuenta, $Diferido, $rowEstadoCuenta['id_cuenta']);
							$rsCuentaActualiza = mysql_query($queryCuentaActualiza);
							if (!$rsCuentaActualiza) return $objResponse->alert(mysql_error());*/

							///////////////////
							
                            $queryActualiza = sprintf("UPDATE te_cheques  SET estado_documento = '%s', fecha_conciliacion = NOW() WHERE id_cheque  = '%s'",3,$rowEstadoCuenta['id_documento']);
                            $rsActualiza = mysql_query($queryActualiza);
                            if (!$rsActualiza) return $objResponse->alert(mysql_error());

                            $queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', id_conciliacion = '%s' WHERE id_estado_cuenta = '%s'",3,$idConciliacion,$valor);
                            $rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
                            if (!$rsEstadoCuentaActualiza) return $objResponse->alert(mysql_error());
                        }
                        if($rowEstadoCuenta['tipo_documento'] == 'TR'){
                            //$objResponse -> alert($rowEstadoCuenta['tipo_documento']);	

                            $queryActualiza = sprintf("UPDATE te_transferencia SET estado_documento = '%s', fecha_conciliacion = NOW() WHERE id_transferencia = '%s'",3,$rowEstadoCuenta['id_documento']);
                            $rsActualiza = mysql_query($queryActualiza);
                            if (!$rsActualiza) return $objResponse->alert(mysql_error());

                            $queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', id_conciliacion = '%s' WHERE id_estado_cuenta = '%s'",3,$idConciliacion,$valor);
                            $rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
                            if (!$rsEstadoCuentaActualiza) return $objResponse->alert(mysql_error());
                        }
                }
            }
		
	}
        
	$updateCuenta = sprintf("UPDATE cuentas SET saldo = '%s' WHERE idCuentas = '%s'",$valForm['hddSaldo'],$valFormBuscar['hddIdCuenta']);
	$rsUpdateCuenta = mysql_query($updateCuenta);
	if (!$rsUpdateCuenta) return $objResponse->alert(mysql_error());

        $objResponse -> alert("La conciliacion se ha realizado con exito");
        $objResponse -> script("window.open('te_conciliacion.php','_self')");

	
		
	mysql_query("COMMIT;");
		
	return $objResponse;	
}

function listAuditoria($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();	

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$queryAuditoria = sprintf("SELECT * FROM te_auditoria_aplicacion WHERE id_estado_de_cuenta = '%s'",$valBusq);	
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitAuditoria = sprintf(" %s %s LIMIT %d OFFSET %d", $queryAuditoria, $sqlOrd, $maxRows, $startRow);
        
	$rsLimitAuditoria = mysql_query($queryLimitAuditoria) or die(mysql_error());
		
	if ($totalRows == NULL) {
		$rsAuditoria = mysql_query($queryAuditoria) or die(mysql_error());
		$totalRows = mysql_num_rows($rsAuditoria);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listAuditoria", "5%", $pageNum, "fecha_cambio", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listAuditoria", "15%", $pageNum, "id_usuario", $campOrd, $tpOrd, $valBusq, $maxRows, "Ususario");			
		$htmlTh .= ordenarCampo("xajax_listAuditoria", "5%", $pageNum, "tipo_accion", $campOrd, $tpOrd, $valBusq, $maxRows, "Acci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listAuditoria", "40%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaciones");			
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($rowAuditoria = mysql_fetch_assoc($rsLimitAuditoria)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		if($rowAuditoria['tipo_accion'] == 1){
			$tipoAccion = "Aplicado";
		}else{
			$tipoAccion = "Desaplicado";
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\">".date("d/m/Y",strtotime($rowAuditoria['fecha_cambio']))."</td>";
			$htmlTb .= "<td align=\"center\">".usuario($rowAuditoria['id_usuario'])."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoAccion."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($rowAuditoria['observacion'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listAuditoria(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
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
		
		
                $objResponse->assign("tdDescripcionArticulo","innerHTML",$htmlTblIni./*$htmlTf.*/$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
		$objResponse->script("document.getElementById('divFlotante').style.display = '';
								  document.getElementById('tblListados').style.display = '';
								  document.getElementById('tblPermiso').style.display = 'none';
								  document.getElementById('tdFlotanteTitulo').innerHTML = 'Comentario al Desaplicar';
								  centrarDiv(document.getElementById('divFlotante'))");
	return $objResponse;
}

function asignarCuentaEmpresa($valForm){
    
	$objResponse = new xajaxResponse();	
	
	$queryBanco = sprintf("SELECT 
						  bancos.idBanco,
						  bancos.nombreBanco,
						  cuentas.idCuentas,
						  cuentas.numeroCuentaCompania
						FROM
						  bancos
						  INNER JOIN cuentas ON (bancos.idBanco = cuentas.idBanco)
						WHERE
						  cuentas.idCuentas = '%s'",$valForm['hddIdCuenta']);
	
	$rsBanco = mysql_query($queryBanco) or die(mysql_error());
	$rowBanco = mysql_fetch_array($rsBanco);
	
	$rowBanco['nombreBanco'];
	
	$queryCuenta = sprintf("SELECT 
						  bancos.idBanco,
						  bancos.nombreBanco,
						  cuentas.idCuentas,
						  cuentas.numeroCuentaCompania
						FROM
						  bancos
						  INNER JOIN cuentas ON (bancos.idBanco = cuentas.idBanco)
						WHERE
						  cuentas.idCuentas = '%s'",$valForm['hddIdCuenta']);
	
	$rsCuenta = mysql_query($queryCuenta) or die(mysql_error());
	$rowCuenta = mysql_fetch_array($rsCuenta);
	
	$rowCuenta['numeroCuentaCompania'];
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$valForm['hddIdEmpresa']);
	$rsEmpresa = mysql_query($queryEmpresa) or die(mysql_error);
	$rowEmpresa = mysql_fetch_array($rsEmpresa);
	
	//$rowEmpresa['nombre_empresa'];
	
	//$objResponse -> alert($valForm['hddSaldoBancoSaldo1']);
	
	$objResponse -> assign("txtEmpresa","value",utf8_encode($rowEmpresa['nombre_empresa'].$rowEmpresa['nombre_empresa_suc']));
	$objResponse -> assign("txtBanco","value",  utf8_encode($rowBanco['nombreBanco']));
	$objResponse -> assign("hddIdBanco","value",$rowBanco['idBanco']);
	$objResponse -> assign("txtCuenta","value",$rowCuenta['numeroCuentaCompania']);
	//$objResponse -> assign("txtSaldoBanco","value",number_format($valForm['hddSaldoBancoSaldo1'],2,'.',','));
	//$objResponse -> assign("hddSaldoBanco","value",$valForm['hddSaldoBancoSaldo1']);
	
	return $objResponse;
}
$xajax->register(XAJAX_FUNCTION,"CargarDatos");
$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"agregarIdOculto");
$xajax->register(XAJAX_FUNCTION,"actualizaSumaSaldo");
$xajax->register(XAJAX_FUNCTION,"guardarConciliacion");
$xajax->register(XAJAX_FUNCTION,"listadoEstadoCuentaAplicados");
$xajax->register(XAJAX_FUNCTION,"listAuditoria");
$xajax->register(XAJAX_FUNCTION,"asignarCuentaEmpresa");


function empresa($id){
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = utf8_encode($row['nombre_empresa']);
	
	return $respuesta;
}

function nombreBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = $rowProveedor['nombre'];
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = $rowBeneficiario['nombre_beneficiario'];
	}
	return utf8_encode($respuesta);
}

function ciRifBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = $rowProveedor['lrif']."-".$rowProveedor['rif'];
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = $rowBeneficiario['lci_rif']."-".$rowBeneficiario['ci_rif_beneficiario'];
	}
	return $respuesta;
}

function direccionBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = $rowProveedor['direccion'];
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = $rowBeneficiario['direccion'];
	}
	return utf8_encode($respuesta);
}


function emailBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = $rowProveedor['correo'];
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = $rowBeneficiario['email'];
	}
	return $respuesta;
}

function telfBp($id){
	
	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['control_beneficiario_proveedor'] == 1){
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'",$row['id_beneficiario_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		$respuesta = $rowProveedor['telefono'];
	} else{	
		$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$row['id_beneficiario_proveedor']);
		$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
		$rowBeneficiario = mysql_fetch_array($rsBeneficiario);
		$respuesta = $rowBeneficiario['telfs'];
	}
	return $respuesta;
}

function nombreBanco($id){
	
	$query = sprintf("SELECT 
						  bancos.idBanco,
						  bancos.nombreBanco,
						  cuentas.idCuentas,
						  cuentas.numeroCuentaCompania
						FROM
						  bancos
						  INNER JOIN cuentas ON (bancos.idBanco = cuentas.idBanco)
						WHERE
						  cuentas.idCuentas = '%s'",$id);
	
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = utf8_encode($row['nombreBanco']);
	
	return $respuesta;	
	
}
function cuenta($id){
	
	$query = sprintf("SELECT 
						  bancos.idBanco,
						  bancos.nombreBanco,
						  cuentas.idCuentas,
						  cuentas.numeroCuentaCompania
						FROM
						  bancos
						  INNER JOIN cuentas ON (bancos.idBanco = cuentas.idBanco)
						WHERE
						  cuentas.idCuentas = '%s'",$id);
	
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	$respuesta = $row['numeroCuentaCompania'];
	
	return $respuesta;	
	
}

function estadoNota($id){

	$query = sprintf("SELECT * FROM te_estados_principales WHERE id_estados_principales = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['id_estados_principales'] == 1)
		$respuesta = "<img src=\"../img/iconos/ico_rojo.gif\">";
	if($row['id_estados_principales'] == 2)
		$respuesta = "<img src=\"../img/iconos/ico_amarillo.gif\">";
	
	
	return $respuesta;
}

function fecha($id){

	$query = sprintf("SELECT * FROM te_nota_debito WHERE id_nota_debito = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);
	
	if($row['fecha_concialicion'] == NULL)
		$respuesta = "";
	else
		$respuesta = date("d/m/Y",strtotime($row['fecha_conciliacion']));
		
	return $respuesta; 

}

function usuario($id){
	
	$query = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = '%s'",$id);
	$rs = mysql_query($query) or die(mysql_error($query));
	$row = mysql_fetch_array($rs);
	
	$respuesta = $row['nombre_empleado']." ".$row['apellido'];
	
	
	return utf8_encode($respuesta);	
}


?>