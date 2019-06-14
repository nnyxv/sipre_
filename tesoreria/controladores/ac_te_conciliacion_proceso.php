<?php
function buscarEstadoCuenta($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s",
		$valForm['hddIdCuenta'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['hddSaldoBancoSaldo1']);
	
	$objResponse->loadCommands(listaEstadoCuentaAplicado(0, "fecha_registro", "DESC", $valBusq));
	
	return $objResponse;
}

function cargarDcto(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT 
		bancos.idBanco,
		bancos.nombreBanco,
		cuentas.idCuentas,
		cuentas.numeroCuentaCompania
	FROM bancos
		INNER JOIN cuentas ON (bancos.idBanco = cuentas.idBanco)
	WHERE cuentas.idCuentas = %s",
		valTpDato($_GET["lstCuenta1"], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '%s'",$_GET['hddIdEmpresa']);
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$objResponse->assign("txtEmpresa","value",utf8_encode($rowEmpresa['nombre_empresa'].$rowEmpresa['nombre_empresa_suc']));
	$objResponse->assign("hddIdEmpresa","value",$rowEmpresa["id_empresa"]);	
	$objResponse->assign("txtBanco","value",  utf8_encode($row['nombreBanco']));
	$objResponse->assign("hddIdBanco","value",$row['idBanco']);	
	$objResponse->assign("hddIdCuenta","value",$row["idCuentas"]);
	$objResponse->assign("txtCuenta","value",$row['numeroCuentaCompania']);
	

	$objResponse->assign("hddSaldoBancoSaldo1","value",$_GET["txtSaldoBanco"]);
	//$objResponse->assign("txtSaldoBanco","value",number_format($_GET["txtSaldoBanco"],2,".",","));//generado listado
	//$objResponse->assign("hddSaldoBanco","value",$_GET["txtSaldoBanco"]);//generado listado
	$objResponse->assign("txtFecha","value",$_GET["txtFechaConciliacion"]);
	
	$objResponse->script("byId('btnBuscar').click();");
	
	return $objResponse;
}

function listaEstadoCuentaAplicado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10000, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.desincorporado != 0 
	AND te_estado_cuenta.estados_principales = 2");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("te_estado_cuenta.id_cuenta = %s",
		valTpDato($valCadBusq[0], "int"));
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(te_estado_cuenta.fecha_registro) BETWEEN %s AND %s ",
			valTpDato(date("Y-m-d",strtotime($valCadBusq[1])), "date"),
			valTpDato(date("Y-m-d",strtotime($valCadBusq[2])), "date")); 
	}

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
		DATE(fecha_registro) AS fecha_registro,
		(SELECT COUNT(*) 
			FROM te_auditoria_aplicacion 
			WHERE id_estado_de_cuenta = te_estado_cuenta.id_estado_cuenta) AS cantidad_auditoria,
		#SOLO PARA ORDENAMIENTO
		if(suma_resta = 0, monto, 0)as debito,                                                  
		if(suma_resta = 1, monto, 0)as credito
	FROM te_estado_cuenta %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);        
	$rsLimit = mysql_query($queryLimit);
	if(!$rsLimit){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$querySaldoIni = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valCadBusq[0]);
	$rsSaldoIni = mysql_query($querySaldoIni);
	if (!$rsSaldoIni){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowSaldoIni = mysql_fetch_assoc($rsSaldoIni);
	
	$htmlSalIni = "<table border=\"0\" width=\"100%\">";
	$htmlSalIni .= "<tr>";
		$htmlSalIni .= "<td align=\"right\">";
			$htmlSalIni .= "<table class=\"tabla\" border=\"0\" cellpadding=\"2\">";
			$htmlSalIni .= "<tr align=\"right\">";
				$htmlSalIni .= "<td width=\"100\" style=\"border:none;\" class=\"tituloCampo\">Saldo:</td>";
				$htmlSalIni .= "<td><input style=\"text-align:right\" type=\"text\" id=\"txtSaldoInicial\" name=\"txtSaldoInicial\" size=\"25\" readonly=\"readonly\" value = \"".$rowSaldoIni['saldo']."\"/>
									<input style=\"text-align:right\" type=\"hidden\" id=\"hddSaldoInicial\" name=\"hddSaldoInicial\" size=\"25\" value = \"".$rowSaldoIni['saldo']."\"/></td>";
			$htmlSalIni .= "</tr>";
			$htmlSalIni .= "</table>";
		$htmlSalIni .= "</td>";
	$htmlSalIni .= "</tr>";
	$htmlSalIni .= "</table>";
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
		$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td><input type=\"checkbox\" id=\"cbxItmAplicado\" onclick=\"selecAllChecks(this.checked,this.id,1); xajax_agregarIdOculto(xajax.getFormValues('frmListaEstadoCuenta'));\"/></td>";
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicadoNO", "1%", $pageNum, "estados_principales", $campOrd, $tpOrd, $valBusq, $maxRows, "");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicadoNO", "1%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Aplicaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicadoNO", "5%", $pageNum, "tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Dcto");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicadoNO", "50%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicadoNO", "35%", $pageNum, "numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Dcto");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicadoNO", "3%", $pageNum, "debito", $campOrd, $tpOrd, $valBusq, $maxRows, "D&eacute;bito");
		$htmlTh .= ordenarCampo("xajax_listaEstadoCuentaAplicadoNO", "3%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Cr&eacute;dito");
		$htmlTh .="<td colspan=\"2\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	$conta = 0;
	$contb = 0;
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$fechaRegistro = "-";
		if($row['fecha_registro'] != ""){ 
			$fechaRegistro = date(spanDateFormat,strtotime($row['fecha_registro']));
		}
		
		switch($row['estados_principales']){
			case 1: $imgEstado = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Por Aplicar\">"; break;
			case 2: $imgEstado = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Aplicado\">"; break;
			case 3: $imgEstado = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Conciliado\">"; break;
			default : $imgEstado = "";
		}
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td><input type=\"checkbox\" id=\"cbxItmAplicado\" name=\"cbxItmAplicado[]\" onclick=\"xajax_agregarIdOculto(xajax.getFormValues('frmListaEstadoCuenta'));\" value=\"".$row['id_estado_cuenta']."\"/></td>";
			$htmlTb .= "<td align=\"center\">".$imgEstado."</td>";
			$htmlTb .= "<td align=\"center\">".$fechaRegistro."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tipo_documento']."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['observacion'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_documento']."</td>";
			if($row['suma_resta'] == 0){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],2,".",",")."</td>";
				$conta +=  $row['monto'];
			}else{
				$htmlTb .= "<td align=\"right\">".number_format(0,2,".",",")."</td>";
			}
			if($row['suma_resta'] == 1){
				$htmlTb .= "<td align=\"right\">".number_format($row['monto'],2,".",",")."</td>";
				$contb +=  $row['monto'];
			}else{
				$htmlTb .= "<td align=\"right\">".number_format(0,2,".",",")."</td>";
			}
			
			if($row['cantidad_auditoria'] == 0){
				$htmlTb .= "<td align=\"center\"><img src=\"../img/iconos/ico_comentario_f2.png\" title=\"Sin Comentarios\"/></td>";
			}else{
				$htmlTb .= sprintf("<td align=\"center\"><a class=\"modalImg\" id=\"aVerAuditoria\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblListaAuditoria', %s);\"><img class=\"puntero\" src=\"../img/iconos/ico_comentario.png\" title=\"Ver Comentarios\"/></a></td>",
					$row['id_estado_cuenta']);
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaAplicado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaAplicado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEstadoCuentaAplicado(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaAplicado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEstadoCuentaAplicado(%s,'%s','%s','%s',%s);\">%s</a>",
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
		te_estado_cuenta.estados_principales,
		te_estado_cuenta.fecha_registro
	FROM te_estado_cuenta %s", $sqlBusq);
	$rsTotales = mysql_query($queryTotales);
	if (!$rsTotales){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	
	while($rowTotales = mysql_fetch_assoc($rsTotales)){	
		if($rowTotales['suma_resta'] == 0){
			$contTotales1 += $rowTotales['monto'];
		}else if($rowTotales['suma_resta'] == 1){
			$contTotales2 += $rowTotales['monto'];
		}
	}
	
	$queryCuentas = sprintf("SELECT * FROM cuentas WHERE idCuentas = '%s'",$valCadBusq[0]);
	$rsCuentas = mysql_query($queryCuentas);
	if(!$rsCuentas){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
	$rowCuentas = mysql_fetch_assoc($rsCuentas);
		
	$saldoTotal = ($rowCuentas['saldo'] + $contTotales2) - $contTotales1; 
	
	$htmlx.="<table align=\"right\" border=\"0\" width=\"60%\">";
		$htmlx.="<tr class=\"tituloColumna\">
					<td width=\"20%\"></td>
					<td align=\"center\" width=\"10%\">"."D&eacute;bito"."</td>
					<td align=\"center\" width=\"10%\">"."Cr&eacute;dito"."</td>
					<td align=\"center\" width=\"10%\">"."Saldo"."</td>
					<td align=\"center\" width=\"10%\">"."Saldo Banco"."</td>
				</tr>";
		$htmlx.="<tr>";
			$htmlx.="<td width=\"100\" class=\"tituloColumna\" align=\"right\">Total General:</td>";
			$htmlx.="<td align=\"right\">
						<input name=\"txtDebito\" id=\"txtDebito\" type=\"text\" readonly=\"readonly\"/>
						<input name=\"hddDebito\" id=\"hddDebito\" type=\"hidden\"/>
					</td>";
			$htmlx.="<td align=\"right\">
						<input name=\"txtCredito\" id=\"txtCredito\" type=\"text\" readonly=\"readonly\"/>
						<input name=\"hddCredito\" id=\"hddCredito\" type=\"hidden\" />
					 </td>";
			$htmlx.="<td align=\"right\"><input name=\"txtSaldo\" id=\"txtSaldo\" type=\"text\" readonly=\"readonly\" value = \"".$rowSaldoIni['saldo']."\"/>
						<input name=\"hddSaldo\" id=\"hddSaldo\" type=\"hidden\"  value = \"".$rowSaldoIni['saldo']."\"/>
						<input name=\"hddSaldoLibro\" id=\"hddSaldoLibro\" type=\"hidden\"  value = \"".$rowSaldoIni['saldo_tem']."\"/>
					</td>";
			$htmlx.="<td align=\"right\"><input name=\"txtSaldoBanco\" id=\"txtSaldoBanco\" type=\"text\" readonly=\"readonly\" value = \"".$valCadBusq[3]."\" />
						<input name=\"hddSaldoBanco\" id=\"hddSaldoBanco\" type=\"hidden\" value = \"".$valCadBusq[3]."\" />
					 </td>";
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
	
	$objResponse->assign("tdListaEstadoAplicado","innerHTML",$htmlSalIni.$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin.$htmlx);
	
	return $objResponse;
}

function agregarIdOculto($valForm){
 	$objResponse = new xajaxResponse();
	
	/*Con esto traigo solo aquellos checkbox q esten habilitados*/
	if (isset($valForm['cbxItmAplicado'])) {
		foreach($valForm['cbxItmAplicado'] as $indiceItm=>$valorItm) {
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
	
	$objResponse->script("xajax_actualizaSumaSaldo(xajax.getFormValues('frmListaEstadoCuenta'))");
	
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
	
	$montoSaldoTotal = 0;
	$montoSaldoSuma = 0;
	$montoSaldoResta = 0;
	
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if(isset($valor)){
				$queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$valor);
				$rsEstadoCuenta = mysql_query($queryEstadoCuenta);
				if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error());
				$rowEstadoCuenta = mysql_fetch_assoc($rsEstadoCuenta);
				
				if ($rowEstadoCuenta['suma_resta'] == 1) {
					$montoSaldoSuma += $rowEstadoCuenta['monto'];
				} else if($rowEstadoCuenta['suma_resta'] == 0) {
					$montoSaldoResta += $rowEstadoCuenta['monto'];
				}
			}
		}
	}
		
	$montoSaldoTotal = $montoSaldoSuma - $montoSaldoResta + $valForm['hddSaldoInicial']; 

	$objResponse->assign("txtDebito","value",number_format($montoSaldoResta,2,".",","));
	$objResponse->assign("txtCredito","value",number_format($montoSaldoSuma,2,".",","));
	$objResponse->assign("txtSaldo","value",number_format($montoSaldoTotal,2,".",","));
	$objResponse->assign("hddDebito","value",$montoSaldoResta);
	$objResponse->assign("hddCredito","value",$montoSaldoSuma);
	$objResponse->assign("hddSaldo","value",$montoSaldoTotal);
	
	return $objResponse;
}

function guardarConciliacion($valForm,$valFormBuscar, $verificarMonto = "SI"){
	$objResponse = new xajaxResponse();
//	$valForm['hddSaldo'];         
//        $valForm['hddSaldoBanco'];
//        
//        $resta = $valForm['hddCredito'] - $valForm['hddDebito'];
       
        //con confirmacion
//        if($verificarMonto != "NO"){        
//            if($resta != $valForm['hddSaldoBanco']){                
//                return $objResponse->script("if (confirm('El saldo del banco no coincide con el monto a conciliar, Banco: ".$valForm['hddSaldoBanco']." Monto (C-D): ".$resta." \\n ¿Deseas guardar la conciliación de todas maneras?'))"
//                        ."{ xajax_guardarConciliacion(xajax.getFormValues('frmListaEstadoCuenta'),xajax.getFormValues('frmBuscar'),'NO'); } ");
//            }
//        }
        
        
        //obligatorio NO SE PUEDE, no es el monto debido y el cuentas con el saldo inicial siempre trae el mas actual
//        if($resta != $valForm['hddSaldoBanco']){                
//                return $objResponse->alert("El saldo del banco no coincide con el monto a conciliar, Banco: ".$valForm['hddSaldoBanco']." Monto (C-D): ".$resta);                        
//        }
        
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
	$rowCuenta = mysql_fetch_assoc($rsCuenta);
	
	$queryConciliacion = sprintf("INSERT INTO te_conciliacion (id_cuenta, id_banco, fecha, monto_conciliado, monto_libro, saldo_banco, total_debito, total_credito, saldo_ant, id_empresa) VALUES ('%s' , '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s')",
		$valFormBuscar['hddIdCuenta'],
		$valFormBuscar['hddIdBanco'],//cambiar NOW() por la fecha q introduzcan los usuarios cuando ahagan el paso anterior//
		// $valFormBuscar['txtFecha'],
		date("Y-m-d",strtotime("01-".$valFormBuscar['txtFecha'])),
		$valForm['hddSaldo'],
		$valForm['hddSaldoLibro'],
		$valForm['hddSaldoBanco'],
		$valForm['hddDebito'],
		$valForm['hddCredito'],
		$rowCuenta['saldo'],
		$valFormBuscar['hddIdEmpresa']);
	$rsConciliacion = mysql_query($queryConciliacion);
	if (!$rsConciliacion) return $objResponse->alert(mysql_error());
	$idConciliacion = mysql_insert_id();	
	
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if(isset($valor)){
				$queryEstadoCuenta = sprintf("SELECT * FROM te_estado_cuenta WHERE id_estado_cuenta = '%s'",$valor);
				$rsEstadoCuenta = mysql_query($queryEstadoCuenta);				
				if (!$rsEstadoCuenta) return $objResponse->alert(mysql_error());
				$rowEstadoCuenta = mysql_fetch_assoc($rsEstadoCuenta);
			   
				if($rowEstadoCuenta['tipo_documento'] == 'DP'){
					$queryActualiza = sprintf("UPDATE te_depositos SET estado_documento = '%s', fecha_conciliacion = NOW() WHERE id_deposito = '%s'",3,$rowEstadoCuenta['id_documento']);					
					$rsActualiza = mysql_query($queryActualiza);
					if (!$rsActualiza) return $objResponse->alert(mysql_error());

				   $queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', id_conciliacion = '%s' WHERE id_estado_cuenta = '%s'",3,$idConciliacion,$valor);				   
					$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
					if (!$rsEstadoCuentaActualiza) return $objResponse->alert(mysql_error());
				}
				
				if($rowEstadoCuenta['tipo_documento'] == 'NC'){
					$queryActualiza = sprintf("UPDATE te_nota_credito SET estado_documento = '%s', fecha_conciliacion = NOW() WHERE id_nota_credito = '%s'",3,$rowEstadoCuenta['id_documento']);
					$rsActualiza = mysql_query($queryActualiza);
					if (!$rsActualiza) return $objResponse->alert(mysql_error());

					$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', id_conciliacion = '%s' WHERE id_estado_cuenta = '%s'",3,$idConciliacion,$valor);
					$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
					if (!$rsEstadoCuentaActualiza) return $objResponse->alert(mysql_error());
				}
				
				if($rowEstadoCuenta['tipo_documento'] == 'ND'){
					$queryActualiza = sprintf("UPDATE te_nota_debito  SET estado_documento = '%s', fecha_conciliacion = NOW() WHERE id_nota_debito  = '%s'",3,$rowEstadoCuenta['id_documento']);
					$rsActualiza = mysql_query($queryActualiza);
					if (!$rsActualiza) return $objResponse->alert(mysql_error());

					$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', id_conciliacion = '%s' WHERE id_estado_cuenta = '%s'",3,$idConciliacion,$valor);
					$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
					if (!$rsEstadoCuentaActualiza) return $objResponse->alert(mysql_error());
				}
				
				if($rowEstadoCuenta['tipo_documento'] == 'CH'){
					$queryActualiza = sprintf("UPDATE te_cheques  SET estado_documento = '%s', fecha_conciliacion = NOW() WHERE id_cheque  = '%s'",3,$rowEstadoCuenta['id_documento']);
					$rsActualiza = mysql_query($queryActualiza);
					if (!$rsActualiza) return $objResponse->alert(mysql_error());

					$queryEstadoCuentaActualiza = sprintf("UPDATE te_estado_cuenta SET estados_principales = '%s', id_conciliacion = '%s' WHERE id_estado_cuenta = '%s'",3,$idConciliacion,$valor);
					$rsEstadoCuentaActualiza = mysql_query($queryEstadoCuentaActualiza);
					if (!$rsEstadoCuentaActualiza) return $objResponse->alert(mysql_error());
				}
				
				if($rowEstadoCuenta['tipo_documento'] == 'TR'){
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

	$objResponse->alert("La conciliacion se ha realizado con exito");
	$objResponse->script("window.open('te_conciliacion.php','_self')");
	
	mysql_query("COMMIT;");
	
	return $objResponse;
}

function listaAuditoria($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_estado_de_cuenta = %s",
		valTpDato($valCadBusq[0], "int"));
	
	$queryAuditoria = sprintf("SELECT 
		auditoria.fecha_cambio,
		auditoria.id_usuario,
		auditoria.tipo_accion,
		auditoria.observacion,
		usuario.nombre_empleado
	FROM te_auditoria_aplicacion auditoria
	INNER JOIN vw_iv_usuarios usuario ON usuario.id_usuario = auditoria.id_usuario %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimitAuditoria = sprintf(" %s %s LIMIT %d OFFSET %d", $queryAuditoria, $sqlOrd, $maxRows, $startRow);
	$rsLimitAuditoria = mysql_query($queryLimitAuditoria);
	if(!$rsLimitAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }	
	if ($totalRows == NULL) {
		$rsAuditoria = mysql_query($queryAuditoria);
		if(!$rsAuditoria){ return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rsAuditoria);
	}
	
	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "5%", $pageNum, "fecha_cambio", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "15%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Empleado");			
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "5%", $pageNum, "tipo_accion", $campOrd, $tpOrd, $valBusq, $maxRows, "Acci&oacute;n");			
		$htmlTh .= ordenarCampo("xajax_listaAuditoria", "40%", $pageNum, "observacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaciones");			
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
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat,strtotime($rowAuditoria['fecha_cambio']))."</td>";
			$htmlTb .= "<td align=\"left\">".$rowAuditoria['nombre_empleado']."</td>";
			$htmlTb .= "<td align=\"center\">".$tipoAccion."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($rowAuditoria['observacion'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaAuditoria(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}		
	
	$objResponse->assign("tdListaAuditoria","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEstadoCuenta");
$xajax->register(XAJAX_FUNCTION,"cargarDcto");
$xajax->register(XAJAX_FUNCTION,"agregarIdOculto");
$xajax->register(XAJAX_FUNCTION,"actualizaSumaSaldo");
$xajax->register(XAJAX_FUNCTION,"guardarConciliacion");
$xajax->register(XAJAX_FUNCTION,"listaEstadoCuentaAplicado");
$xajax->register(XAJAX_FUNCTION,"listaAuditoria");

?>