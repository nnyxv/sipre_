<?php 
function asignarClientes($idCliente){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM cj_cc_cliente WHERE id  = %s",
		valTpDato($idCliente, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_array($rs);
	
	$objResponse->assign("txtCedulaRifCliente","value",$row['lci']."-".$row['ci']);
	$objResponse->assign("txtCodigoCliente","value",$row['id']);
	if ($row['nit'] != "" ){
		$objResponse->assign("txtNITCliente","value",$row['nit']);
	} else {
		$objResponse->assign("txtNITCliente","value",'N/A');		
	}
	$objResponse->assign("txtTelefonoCliente","value",$row['telf']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($row['nombre']." ".$row['apellido']));
	$objResponse->assign("txtDireccionCliente","innerHTML",utf8_encode($row['direccion']));
	
	$objResponse->assign("txtCriterioBusqCliente","value","");
	
	$objResponse->script("byId('btnCancelar').click();");
	
	return $objResponse;
}

function buscarCliente($frmBuscarCliente){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarCliente['txtCriterioBusqCliente']);
		
	$objResponse->loadCommands(listadoClientes(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function cargarFecha(){
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("txtFecha","value",date(spanDateFormat));
		
	return $objResponse;
}

function cargarModulos(){
	$objResponse = new xajaxResponse();
	
	$queryModulos = sprintf("SELECT * FROM pg_modulos WHERE id_modulo NOT IN (3)");
	$rsModulos = mysql_query($queryModulos);
	if (!$rsModulos) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);

	$html = "<table border=\"0\" width=\"100%\">";
	$cont = 1;
	while ($rowModulos = mysql_fetch_array($rsModulos)) {
		if (fmod($cont, 4) == 1)
			$html .= "<tr align=\"center\" height=\"24\">";
				$html .= sprintf("<td><input type=\"radio\" id=\"cbxModulo\" name=\"cbxModulo[]\" checked=\"checked\" value=\"%s\"/>%s</td>",
					$rowModulos['id_modulo'],
					$rowModulos['descripcionModulo']);
		if (fmod($cont, 4) == 0)
			$html .= "</tr>";
	
		$cont++;	
	}
	$html .= "</table>";
	
	$objResponse->assign("tdModulos","innerHTML",$html);
	
	return $objResponse;
}

function exportarAntiguedadSaldo($frmCliente){
	$objResponse = new xajaxResponse();
	
	if (isset($frmCliente['cbxModulo'])) {
		foreach ($frmCliente['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr($idModulos, 0, (strlen($idModulos)-1));
	}
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmCliente['txtIdEmpresa'],
		$frmCliente['txtCodigoCliente'],
		$frmCliente['txtFecha'],
		$frmCliente['radioOpcion'],
		$idModulos);
	
	$objResponse->script("window.open('reportes/cc_antiguedad_saldo_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listadoClientes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(id = %s
	OR CONCAT_WS('-',lci,ci) LIKE %s
	OR CONCAT_WS('',lci,ci) LIKE %s
	OR CONCAT_WS(' ',nombre,apellido) LIKE %s)",
		valTpDato($valCadBusq[0], "int"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"),
		valTpDato("%".$valCadBusq[0]."%", "text"));
		
	$query = sprintf("SELECT
		id,
		CONCAT_WS('-',lci,ci) AS cedula_cliente,
		CONCAT_WS(' ',nombre,apellido) AS nombre_cliente,
		credito
	FROM cj_cc_cliente %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	
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
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "18%", $pageNum, "cedula_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nombre"));
		$htmlTh .= ordenarCampo("xajax_listadoClientes", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));		
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$contFila ++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarClientes(".$row['id'].");\" title=\"Seleccionar Cliente\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($arrayTipoPago[strtoupper($row['credito'])])."</td>";			
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
			$htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTf .= "<tr class=\"tituloCampo\">";
				$htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
					$htmlTf .= sprintf("Mostrando %s Registros de un total de %s&nbsp;",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_pri.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_ant.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoClientes(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxc_reg_sig.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClientes(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"5\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListadoClientes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	$objResponse->assign("tdCabeceraEstado","innerHTML","");
	
	$objResponse->script("
		byId('trBuscarCliente').style.display = '';
		byId('tblListadoCliente').style.display = '';");
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Clientes");
	$objResponse->assign("tblListados","width","600");
	
	return $objResponse;
}

function listarFacturaIndividual($idCliente,$idEmpresa,$fechaCierre,$valForm){
	$objResponse = new xajaxResponse();
	
	//TODOS LOS CLIENTES (RESUMEN)	
	$html = "<table border=\"0\" cellpadding=\"2\" width=\"100%\">
	<tr align=\"center\" class=\"tituloColumna\">
		<td></td>
		<td width=\"16%\">".utf8_encode("Empresa")."</td>
		<td width=\"12%\">".utf8_encode("Nro. Documento")."</td>
		<td width=\"12%\">".utf8_encode("Fecha Registro")."</td>
		<td width=\"15%\">".utf8_encode("Fecha Vencimiento")."</td>
		<td width=\"15%\">".utf8_encode("Monto Total")."</td>
		<td width=\"15%\">".utf8_encode("Monto Pagado")."</td>
		<td width=\"15%\">".utf8_encode("Saldo")."</td>
		<td></td>
	</tr>";
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond =" AND ";
		$sqlBusq .= $cond.sprintf("(vw_cxc.id_empresa = %s
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = vw_cxc.id_empresa))",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($idCliente != "-1" && $idCliente != "") {
		$cond = " AND ";
		$sqlCliente .= $cond.sprintf("vw_cxc.idCliente = %s",
			valTpDato($idCliente, "int"));
	}
			
	if ($valForm['cbxModulo']) {
		//$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$cond = " AND ";
		$idModulos = $cond."vw_cxc.idDepartamentoOrigenFactura IN (";
		foreach ($valForm['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr($idModulos, 0, (strlen($idModulos)-1));
		$idModulos .= ")";
	}
	
	$fechaCxC = date("Y-m-d", strtotime($fechaCierre));
	
	if ($valor == 0 || $valor == 1){//REPUESTOS Y SERVICIOS
		$queryEstadoCabecera = sprintf("SELECT
			vw_cxc.idFactura,
			vw_cxc.idCliente,
			vw_cxc.numeroFactura,
			vw_cxc.idDepartamentoOrigenFactura,
			SUM(vw_cxc.montoTotal) AS montoTotal,
			(SELECT IFNULL(SUM(pagos.montoPagado),0)
				FROM sa_iv_pagos pagos, vw_cc_cuentas_por_cobrar
				WHERE vw_cc_cuentas_por_cobrar.idFactura = pagos.id_factura AND pagos.fechaPago <= '%s'
				AND cliente.id = vw_cxc.idCliente
				AND vw_cxc.idFactura = pagos.id_factura) AS montoPagado,
			vw_cxc.fechaRegistroFactura,
			vw_cxc.fechaVencimientoFactura,
			vw_cxc.idCliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_cc_cuentas_por_cobrar vw_cxc
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN cj_cc_cliente cliente ON (vw_cxc.idCliente = cliente.id)
		WHERE vw_cxc.fechaRegistroFactura <= '%s' %s %s %s
		GROUP BY vw_cxc.idCliente, vw_cxc.idFactura
		HAVING (montoTotal - montoPagado) > 1
		ORDER BY idFactura DESC", $fechaCxC,$fechaCxC,$idModulos,$sqlCliente,$sqlBusq);
		
	} else if ($valor == 2){ //VEHICULOS
		$queryEstadoCabecera = sprintf("SELECT
			vw_cxc.idFactura,
			vw_cxc.idCliente,
			vw_cxc.numeroFactura,
			vw_cxc.idDepartamentoOrigenFactura,
			SUM(vw_cxc.montoTotal) AS montoTotal,
			(SELECT IFNULL(SUM(pagos.montoPagado),0)
				FROM an_pagos pagos, vw_cc_cuentas_por_cobrar
				WHERE vw_cc_cuentas_por_cobrar.idFactura = pagos.id_factura AND pagos.fechaPago <= '%s'
				AND cliente.id = vw_cxc.idCliente
				AND vw_cxc.idFactura = pagos.id_factura) AS montoPagado,
			vw_cxc.fechaRegistroFactura,
			vw_cxc.fechaVencimientoFactura,
			vw_cxc.idCliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_cc_cuentas_por_cobrar vw_cxc
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc.id_empresa = vw_iv_emp_suc.id_empresa_reg)
			INNER JOIN cj_cc_cliente cliente ON (vw_cxc.idCliente = cliente.id)
		WHERE vw_cxc.fechaRegistroFactura <= '%s' %s %s %s
		GROUP BY vw_cxc.idCliente, vw_cxc.idFactura
		HAVING (montoTotal - montoPagado) > 1
		ORDER BY idFactura DESC", $fechaCxC,$fechaCxC,$idModulos,$sqlCliente,$sqlBusq);
	}
	$rsEstadoCabecera = mysql_query($queryEstadoCabecera);
	if (!$rsEstadoCabecera) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if (mysql_num_rows($rsEstadoCabecera) > 0) {
		while ($rowEstadoCabecera = mysql_fetch_array($rsEstadoCabecera)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$idFactura = $rowEstadoCabecera['idFactura'];
			$fechaRegistro = $rowEstadoCabecera['fechaRegistroFactura'];
			$fechaVencimiento = $rowEstadoCabecera['fechaVencimientoFactura'];
			$nroDocumento = $rowEstadoCabecera['numeroFactura'];
			$montoTotal = $rowEstadoCabecera['montoTotal'];
			$montoPagado = $rowEstadoCabecera['montoPagado'];
			$saldoTotal = $montoTotal - $montoPagado;
			$accion = "cc_factura_form.php?id=%s&acc=0";
			$imgDevuelta = "";
			
			switch($rowEstadoCabecera['idDepartamentoOrigenFactura']) {
				case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
				case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
				case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
				case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
				default : $imgDctoModulo = $rowEstadoCabecera['idDepartamentoOrigenFactura'];
			}
			
			//VERIFICA SI EL DOCUMENTO ESTÁ DEVUELTO (SI TIENE NOTA DE CREDIO)
			$queryNotaCredito = sprintf("SELECT idDocumento, montoNetoNotaCredito
			FROM cj_cc_notacredito
			WHERE idDocumento = %s
				AND fechaNotaCredito <= %s
				AND idDepartamentoNotaCredito IN (%s)",
				valTpDato($rowEstadoCabecera['idFactura'], "int"),
				valTpDato($fechaCxC, "date"),
				valTpDato($valor, "int"));
			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			if (mysql_num_rows($rsNotaCredito) > 0) {
				$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
				
				$montoNotaCredito = $rowNotaCredito['montoNetoNotaCredito'];
				//$montoPagado = $rowNotaCredito['montoNetoNotaCredito'];
				$saldoTotal = $montoTotal - $montoNotaCredito;
				
				$imgDevuelta = "<img align=\"left\" title=\"Factura Devuelta\" src=\"../img/iconos/book_previous.png\">";
				$clase = "divMsjAlertaSinBorde";
			}
			
			$html .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
				$html .= "<td align=\"center\">".$imgDctoModulo."</td>";
				$html .= "<td align=\"left\">".$rowEstadoCabecera['nombre_empresa']."</td>";
				$html .= "<td align=\"right\">".$imgDevuelta.$nroDocumento."</td>";
				$html .= "<td align=\"center\">".date(spanDateFormat, strtotime($fechaRegistro))."</td>";
				$html .= "<td align=\"center\">".date(spanDateFormat, strtotime($fechaVencimiento))."</td>";
				$html .= "<td align=\"right\">".number_format($montoTotal,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($montoPagado,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($saldoTotal,2,'.',',')."</td>";
				$html .= sprintf("<td align=\"center\" class=\"noprint\"><a href=\"".$accion."\" target=\"_blank\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Documento\"/></a></td>",$idFactura);
			$html .= "</tr>";
			
			$montoTotalFinal += $montoTotal;
			$montoFinal += $montoPagado;
			$saldoFinal += $saldoTotal;
		}
		
		$html .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$html .= "<td class=\"tituloCampo\" colspan=\"5\">Totales:</td>";
			$html .= "<td>".number_format($montoTotalFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($montoFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($saldoFinal,2,'.',',')."</td>";
			$html .= "<td></td>";
		$html .= "</tr>";
		$html .= "</table>";
		
		$objResponse->assign("tdCabeceraEstado","innerHTML",$html);
	} else {
		$objResponse->assign("tdCabeceraEstado","innerHTML","<table cellpadding='0' cellspacing='0' class='divMsjError' width='100%'>
				<tr>
					<td width='25'><img src='../img/iconos/ico_fallido.gif' width='25'/></td>
					<td align='center'>No se encontraron registros</td>
				</tr>
			</table>");		
	}
	
	return $objResponse;
}

function listarFacturaDetalle($idCliente,$idEmpresa,$fechaCierre,$valForm){
	$objResponse = new xajaxResponse();
	
	//TODOS LOS CLIENTES (DETALLADO)	
	$html = "<table border=\"0\" cellpadding=\"2\" width=\"100%\">
	<tr align=\"center\" class=\"tituloColumna\">
		<td></td>
		<td width=\"10%\">".utf8_encode("Nro. Documento")."</td>
		<td width=\"10%\">".utf8_encode("Fecha Registro")."</td>
		<td width=\"10%\">".utf8_encode("Fecha Vencimiento")."</td>
		<td width=\"28%\">".utf8_encode("Cliente")."</td>
		<td width=\"14%\">".utf8_encode("Monto Total")."</td>
		<td width=\"14%\">".utf8_encode("Monto Pagado")."</td>
		<td width=\"14%\">".utf8_encode("Saldo")."</td>
		<td></td>
	</tr>";
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = " AND ";
		$sqlBusq .= $cond.sprintf("(vw_cxc.id_empresa = %s
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = vw_cxc.id_empresa))",
			valTpDato($idEmpresa, "int"));
	}
	
	/*if ($idCliente != "-1" && $idCliente != "") {
		$cond = " AND ";
		$sqlCliente .= $cond.sprintf("vw_cxc.idCliente = %s",
			valTpDato($idCliente, "int"));
	}*/
			
	if ($valForm['cbxModulo']) {
		//$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$cond = " AND ";
		$idModulos = $cond."vw_cxc.idDepartamentoOrigenFactura IN (";
		foreach ($valForm['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr($idModulos, 0, (strlen($idModulos)-1));
		$idModulos .= ")";
	}
	
	$fechaCxC = date("Y-m-d", strtotime($fechaCierre));
	
	if ($valor == 0 || $valor == 1){//REPUESTOS Y SERVICIOS
		$queryEstadoCabecera = sprintf("SELECT
			vw_cxc.idFactura,
			vw_cxc.idCliente,
			vw_cxc.numeroFactura,
			vw_cxc.idDepartamentoOrigenFactura,
			SUM(vw_cxc.montoTotal) AS montoTotal,
			(SELECT IFNULL(SUM(pagos.montoPagado),0)
				FROM sa_iv_pagos pagos, vw_cc_cuentas_por_cobrar
				WHERE vw_cc_cuentas_por_cobrar.idFactura = pagos.id_factura AND pagos.fechaPago <= '%s'
				AND cliente.id = vw_cxc.idCliente
				AND vw_cxc.idFactura = pagos.id_factura) AS montoPagado,
			vw_cxc.fechaRegistroFactura,
			vw_cxc.fechaVencimientoFactura,
			vw_cxc.idCliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM vw_cc_cuentas_por_cobrar vw_cxc
			INNER JOIN vw_iv_empresas_sucursales ON (vw_cxc.id_empresa = vw_iv_empresas_sucursales.id_empresa_reg)
			INNER JOIN cj_cc_cliente cliente ON (vw_cxc.idCliente = cliente.id)
		WHERE vw_cxc.fechaRegistroFactura <= '%s' %s %s
		GROUP BY vw_cxc.idCliente, vw_cxc.idFactura
		HAVING (montoTotal - montoPagado) > 1
		ORDER BY idFactura DESC", $fechaCxC,$fechaCxC,$idModulos,$sqlBusq);
		
	} else if ($valor == 2){ //VEHICULOS
		$queryEstadoCabecera = sprintf("SELECT
			vw_cxc.idFactura,
			vw_cxc.idCliente,
			vw_cxc.numeroFactura,
			vw_cxc.idDepartamentoOrigenFactura,
			SUM(vw_cxc.montoTotal) AS montoTotal,
			(SELECT IFNULL(SUM(pagos.montoPagado),0)
				FROM an_pagos pagos, vw_cc_cuentas_por_cobrar
				WHERE vw_cc_cuentas_por_cobrar.idFactura = pagos.id_factura AND pagos.fechaPago <= '%s'
				AND cliente.id = vw_cxc.idCliente
				AND vw_cxc.idFactura = pagos.id_factura) AS montoPagado,
			vw_cxc.fechaRegistroFactura,
			vw_cxc.fechaVencimientoFactura,
			vw_cxc.idCliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM vw_cc_cuentas_por_cobrar vw_cxc
			INNER JOIN vw_iv_empresas_sucursales ON (vw_cxc.id_empresa = vw_iv_empresas_sucursales.id_empresa_reg)
			INNER JOIN cj_cc_cliente cliente ON (vw_cxc.idCliente = cliente.id)
		WHERE vw_cxc.fechaRegistroFactura <= '%s' %s %s
		GROUP BY vw_cxc.idCliente, vw_cxc.idFactura
		HAVING (montoTotal - montoPagado) > 1
		ORDER BY idFactura DESC", $fechaCxC,$fechaCxC,$idModulos,$sqlBusq);
	}
	$rsEstadoCabecera = mysql_query($queryEstadoCabecera);
	if (!$rsEstadoCabecera) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if (mysql_num_rows($rsEstadoCabecera) > 0) {
		while ($rowEstadoCabecera = mysql_fetch_array($rsEstadoCabecera)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$idFactura = $rowEstadoCabecera['idFactura'];
			$fechaRegistro = $rowEstadoCabecera['fechaRegistroFactura'];
			$fechaVencimiento = $rowEstadoCabecera['fechaVencimientoFactura'];
			$nroDocumento = $rowEstadoCabecera['numeroFactura'];
			$montoTotal = $rowEstadoCabecera['montoTotal'];
			$montoPagado = $rowEstadoCabecera['montoPagado'];
			$saldoTotal = $montoTotal - $montoPagado;
			$accion = "cc_factura_form.php?id=%s&acc=0";
			$imgDevuelta = "";
			
			switch($rowEstadoCabecera['idDepartamentoOrigenFactura']) {
				case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
				case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
				case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
				case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
				default : $imgDctoModulo = $rowEstadoCabecera['idDepartamentoOrigenFactura'];
			}
			
			//VERIFICA SI EL DOCUMENTO ESTÁ DEVUELTO (SI TIENE NOTA DE CREDIO)
			$queryNotaCredito = sprintf("SELECT idDocumento, montoNetoNotaCredito
			FROM cj_cc_notacredito
			WHERE idDocumento = %s
				AND fechaNotaCredito <= %s
				AND idDepartamentoNotaCredito IN (%s)",
				valTpDato($rowEstadoCabecera['idFactura'], "int"),
				valTpDato($fechaCxC, "date"),
				valTpDato($valor, "int"));
			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			if (mysql_num_rows($rsNotaCredito) > 0) {
				$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
				
				$montoNotaCredito = $rowNotaCredito['montoNetoNotaCredito'];
				//$montoPagado = $rowNotaCredito['montoNetoNotaCredito'];
				$saldoTotal = $montoTotal - $montoNotaCredito;
				
				$imgDevuelta = "<img align=\"left\" title=\"Factura Devuelta\" src=\"../img/iconos/book_previous.png\">";
				$clase = "divMsjAlertaSinBorde";
			}
			
			$html .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
				$html .= "<td align=\"center\">".$imgDctoModulo."</td>";
				$html .= "<td align=\"right\">".$imgDevuelta.$nroDocumento."</td>";
				$html .= "<td align=\"center\">".date(spanDateFormat, strtotime($fechaRegistro))."</td>";
				$html .= "<td align=\"center\">".date(spanDateFormat, strtotime($fechaVencimiento))."</td>";
				$html .= "<td align=\"left\">".utf8_encode($rowEstadoCabecera['nombre_cliente'])."</td>";
				$html .= "<td align=\"right\">".number_format($montoTotal,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($montoPagado,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($saldoTotal,2,'.',',')."</td>";
				$html .= sprintf("<td align=\"center\" class=\"noprint\"><a href=\"".$accion."\" target=\"_blank\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Documento\"/></a></td>",$idFactura);
			$html .= "</tr>";
			
			$montoTotalFinal += $montoTotal;
			$montoFinal += $montoPagado;
			$saldoFinal += $saldoTotal;
		}
		
		$html .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$html .= "<td class=\"tituloCampo\" colspan=\"5\">Totales:</td>";
			$html .= "<td>".number_format($montoTotalFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($montoFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($saldoFinal,2,'.',',')."</td>";
			$html .= "<td></td>";
		$html .= "</tr>";
		$html .= "</table>";
		
		$objResponse->assign("tdCabeceraEstado","innerHTML",$html);
	} else {
		$objResponse->assign("tdCabeceraEstado","innerHTML","<table cellpadding='0' cellspacing='0' class='divMsjError' width='100%'>
				<tr>
					<td width='25'><img src='../img/iconos/ico_fallido.gif' width='25'/></td>
					<td align='center'>No se encontraron registros</td>
				</tr>
			</table>");		
	}
	
	return $objResponse;
}

function listarFacturaResumen($idCliente,$idEmpresa,$fechaCierre,$valForm){
	$objResponse = new xajaxResponse();
	
	//TODOS LOS CLIENTES (RESUMEN)	
	$html = "<table border=\"0\" cellpadding=\"2\" width=\"100%\">
	<tr align=\"center\" class=\"tituloColumna\">
		<td></td>		
		<td width=\"15%\">".utf8_encode("Cantidad Factura")."</td>
		<td width=\"15%\">".utf8_encode("Cantidad N.Crédito")."</td>
		<td width=\"25%\">".utf8_encode("Cliente")."</td>
		<td width=\"15%\">".utf8_encode("Monto Total")."</td>
		<td width=\"15%\">".utf8_encode("Monto Pagado")."</td>
		<td width=\"15%\">".utf8_encode("Saldo")."</td>
	</tr>";
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond =" AND ";
		$sqlBusq .= $cond.sprintf("(vw_cxc.id_empresa = %s
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = vw_cxc.id_empresa))",
			valTpDato($idEmpresa, "int"));
	}
	
	/*if ($idCliente != "-1" && $idCliente != "") {
		$cond = " AND ";
		$sqlCliente .= $cond.sprintf("vw_cxc.idCliente = %s",
			valTpDato($idCliente, "int"));
	}*/
			
	if ($valForm['cbxModulo']) {
		//$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$cond = " AND ";
		$idModulos = $cond."vw_cxc.idDepartamentoOrigenFactura IN (";
		foreach ($valForm['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr($idModulos, 0, (strlen($idModulos)-1));
		$idModulos .= ")";
	}
	
	$fechaCxC = date("Y-m-d", strtotime($fechaCierre));
	
	if ($valor == 0 || $valor == 1){//REPUESTOS Y SERVICIOS
		$queryEstadoCabecera = sprintf("SELECT
			vw_cxc.idFactura,
			vw_cxc.idCliente,
			vw_cxc.numeroFactura,
			vw_cxc.idDepartamentoOrigenFactura,
			SUM(vw_cxc.montoTotal) AS montoTotal,
			(SELECT IFNULL(SUM(pagos.montoPagado),0)
				FROM sa_iv_pagos pagos, vw_cc_cuentas_por_cobrar
				WHERE vw_cc_cuentas_por_cobrar.idFactura = pagos.id_factura AND pagos.fechaPago <= '%s'
				AND cliente.id = vw_cxc.idCliente
				AND vw_cxc.idFactura = pagos.id_factura) AS montoPagado,
			vw_cxc.fechaRegistroFactura,
			vw_cxc.fechaVencimientoFactura,
			vw_cxc.idCliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM vw_cc_cuentas_por_cobrar vw_cxc
			INNER JOIN vw_iv_empresas_sucursales ON (vw_cxc.id_empresa = vw_iv_empresas_sucursales.id_empresa_reg)
			INNER JOIN cj_cc_cliente cliente ON (vw_cxc.idCliente = cliente.id)
		WHERE vw_cxc.fechaRegistroFactura <= '%s' %s %s
		GROUP BY vw_cxc.idCliente, vw_cxc.idFactura
		HAVING (montoTotal - montoPagado) > 1
		ORDER BY idCliente DESC", $fechaCxC,$fechaCxC,$idModulos,$sqlBusq);
	} else if ($valor == 2){ //VEHICULOS
		$queryEstadoCabecera = sprintf("SELECT
			vw_cxc.idFactura,
			vw_cxc.idCliente,
			vw_cxc.numeroFactura,
			vw_cxc.idDepartamentoOrigenFactura,
			SUM(vw_cxc.montoTotal) AS montoTotal,
			(SELECT IFNULL(SUM(pagos.montoPagado),0)
				FROM an_pagos pagos, vw_cc_cuentas_por_cobrar
				WHERE vw_cc_cuentas_por_cobrar.idFactura = pagos.id_factura AND pagos.fechaPago <= '%s'
				AND cliente.id = vw_cxc.idCliente
				AND vw_cxc.idFactura = pagos.id_factura) AS montoPagado,
			vw_cxc.fechaRegistroFactura,
			vw_cxc.fechaVencimientoFactura,
			vw_cxc.idCliente,
			CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
		FROM vw_cc_cuentas_por_cobrar vw_cxc
			INNER JOIN vw_iv_empresas_sucursales ON (vw_cxc.id_empresa = vw_iv_empresas_sucursales.id_empresa_reg)
			INNER JOIN cj_cc_cliente cliente ON (vw_cxc.idCliente = cliente.id)
		WHERE vw_cxc.fechaRegistroFactura <= '%s' %s %s
		GROUP BY vw_cxc.idCliente, vw_cxc.idFactura
		HAVING (montoTotal - montoPagado) > 1
		ORDER BY idCliente DESC", $fechaCxC,$fechaCxC,$idModulos,$sqlBusq);
	}
	$rsEstadoCabecera = mysql_query($queryEstadoCabecera);
	if (!$rsEstadoCabecera) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if (mysql_num_rows($rsEstadoCabecera) > 0) {
		
		$arrayCliente = array();		
		
		while ($rowEstadoCabecera = mysql_fetch_array($rsEstadoCabecera)) {
			$montoTotal = $rowEstadoCabecera['montoTotal'];
			$montoPagado = $rowEstadoCabecera['montoPagado'];
			$saldoTotal = $montoTotal - $montoPagado;
			
			//VERIFICA SI EL DOCUMENTO ESTÁ DEVUELTO (SI TIENE NOTA DE CREDIO)
			$queryNotaCredito = sprintf("SELECT idDocumento, montoNetoNotaCredito
			FROM cj_cc_notacredito
			WHERE idDocumento = %s
				AND fechaNotaCredito <= %s
				AND idDepartamentoNotaCredito IN (%s)",
				valTpDato($rowEstadoCabecera['idFactura'], "int"),
				valTpDato($fechaCxC, "date"),
				valTpDato($valor, "int"));
			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			if (mysql_num_rows($rsNotaCredito) > 0) {
				$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
				
				$montoNotaCredito = $rowNotaCredito['montoNetoNotaCredito'];
				//$montoPagado = $rowNotaCredito['montoNetoNotaCredito'];
				$saldoTotal = $montoTotal - $montoNotaCredito;
				$arrayCliente[$idCliente]['conteo_notas']++;
			}
			
			$idCliente = $rowEstadoCabecera['idCliente'];
			$arrayCliente[$idCliente]['nombre_cliente'] = $rowEstadoCabecera['nombre_cliente'];
			$arrayCliente[$idCliente]['monto_total'] = $arrayCliente[$idCliente]['monto_total'] + $montoTotal;
			$arrayCliente[$idCliente]['monto_pagado'] = $arrayCliente[$idCliente]['monto_pagado'] + $montoPagado;
			$arrayCliente[$idCliente]['saldo'] = $arrayCliente[$idCliente]['saldo'] + $saldoTotal;
			$arrayCliente[$idCliente]['conteo_factura']++;
		}
	
		foreach($arrayCliente as $facturasCliente){
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			//$idCliente = $rowEstadoCabecera['idCliente'];
			$montoTotal = $facturasCliente['monto_total'];
			$montoPagado = $facturasCliente['monto_pagado'];			

			$saldoTotal = $facturasCliente['saldo'];
					
			switch($valor) {
				case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
				case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
				case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
				case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
				default : $imgDctoModulo = $valor;
			}
			
			
			
			$html .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
				$html .= "<td align=\"center\">".$imgDctoModulo."</td>";
				$html .= "<td align=\"center\">".$facturasCliente['conteo_factura']."</td>";
				$html .= "<td align=\"center\">".valTpDato($facturasCliente['conteo_notas'], "int")."</td>";
				$html .= "<td align=\"left\">".utf8_encode($facturasCliente['nombre_cliente'])."</td>";
				$html .= "<td align=\"right\">".number_format($montoTotal,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($montoPagado,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($saldoTotal,2,'.',',')."</td>";
				//$html .= sprintf("<td align=\"center\" class=\"noprint\"><a href=\"".$accion."\" target=\"_blank\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Documento\"/></a></td>",$idFactura);
			$html .= "</tr>";
			
			$montoTotalFinal += $montoTotal;
			$montoFinal += $montoPagado;
			$saldoFinal += $saldoTotal;				
			
		}
		
		$html .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$html .= "<td class=\"tituloCampo\" colspan=\"4\">Totales:</td>";
			$html .= "<td>".number_format($montoTotalFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($montoFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($saldoFinal,2,'.',',')."</td>";
		$html .= "</tr>";
		$html .= "</table>";
		
		$objResponse->assign("tdCabeceraEstado","innerHTML",$html);
	} else {
		$objResponse->assign("tdCabeceraEstado","innerHTML","<table cellpadding='0' cellspacing='0' class='divMsjError' width='100%'>
				<tr>
					<td width='25'><img src='../img/iconos/ico_fallido.gif' width='25'/></td>
					<td align='center'>No se encontraron registros</td>
				</tr>
			</table>");		
	}
	
	return $objResponse;
}

function listarNotaCargoResumen($idCliente,$idEmpresa,$fechaCierre,$valForm){
	$objResponse = new xajaxResponse();
	
	//TODOS LOS CLIENTES (RESUMEN)	
	$html = "<table border=\"0\" cellpadding=\"2\" width=\"100%\">
	<tr align=\"center\" class=\"tituloColumna\">
		<td></td>		
		<td width=\"15%\">".utf8_encode("Cantidad Factura")."</td>
		<td width=\"15%\">".utf8_encode("Cantidad N.Crédito")."</td>
		<td width=\"25%\">".utf8_encode("Cliente")."</td>
		<td width=\"15%\">".utf8_encode("Monto Total")."</td>
		<td width=\"15%\">".utf8_encode("Monto Pagado")."</td>
		<td width=\"15%\">".utf8_encode("Saldo")."</td>
	</tr>";
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond =" AND ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_nd.id_empresa = %s
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = vw_cxc_nd.id_empresa))",
			valTpDato($idEmpresa, "int"));
	}
	
	/*if ($idCliente != "-1" && $idCliente != "") {
		$cond = " AND ";
		$sqlCliente .= $cond.sprintf("vw_cxc_nd.idCliente = %s",
			valTpDato($idCliente, "int"));
	}*/
			
	if ($valForm['cbxModulo']) {
		//$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$cond = " AND ";
		$idModulos = $cond."vw_cxc_nd.idDepartamento IN (";
		foreach ($valForm['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr($idModulos, 0, (strlen($idModulos)-1));
		$idModulos .= ")";
	}
	
	$fechaCxC_nc = date("Y-m-d", strtotime($fechaCierre));
	
	$queryEstadoCabecera = sprintf("SELECT
		vw_cxc_nd.idNotaCargo,
		vw_cxc_nd.idCliente,
		vw_cxc_nd.numeroNotaCargo,
		vw_cxc_nd.idDepartamento,
		SUM(vw_cxc_nd.montoTotal) AS montoTotal,
		(SELECT IFNULL(SUM(pagos.monto_pago),0)
			FROM cj_det_nota_cargo pagos, vw_cc_cuentas_por_cobrar_nd
			WHERE vw_cc_cuentas_por_cobrar_nd.idNotaCargo = pagos.idNotaCargo AND pagos.fechaPago <= '%s'
			AND cliente.id = vw_cxc_nd.idCliente
			AND vw_cxc_nd.idNotaCargo = pagos.idNotaCargo) AS monto_pago,
		vw_cxc_nd.fechaRegistro,
		vw_cxc_nd.fechaVencimiento,
		vw_cxc_nd.idCliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM vw_cc_cuentas_por_cobrar_nd vw_cxc_nd
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN cj_cc_cliente cliente ON (vw_cxc_nd.idCliente = cliente.id)
	WHERE vw_cxc_nd.fechaRegistro <= '%s' %s %s
	GROUP BY vw_cxc_nd.idCliente, vw_cxc_nd.idNotaCargo
	HAVING (montoTotal - monto_pago) > 1
	ORDER BY idNotaCargo DESC", $fechaCxC_nc,$fechaCxC_nc,$idModulos,$sqlBusq);
	$rsEstadoCabecera = mysql_query($queryEstadoCabecera);
	if (!$rsEstadoCabecera) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if (mysql_num_rows($rsEstadoCabecera) > 0) {
		
		$arrayCliente = array();		
		
		while ($rowEstadoCabecera = mysql_fetch_array($rsEstadoCabecera)) {
			$montoTotal = $rowEstadoCabecera['montoTotal'];
			$montoPagado = $rowEstadoCabecera['monto_pago'];
			$saldoTotal = $montoTotal - $montoPagado;
			
			//VERIFICA SI EL DOCUMENTO ESTÁ DEVUELTO (SI TIENE NOTA DE CREDIO)
			/*$queryNotaCredito = sprintf("SELECT idDocumento, montoNetoNotaCredito
			FROM cj_cc_notacredito
			WHERE idDocumento = %s
				AND fechaNotaCredito <= %s
				AND idDepartamentoNotaCredito IN (%s)",
				valTpDato($rowEstadoCabecera['idFactura'], "int"),
				valTpDato($fechaCxC_nc, "date"),
				valTpDato($valor, "int"));
			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			if (mysql_num_rows($rsNotaCredito) > 0) {
				$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
				
				$montoNotaCredito = $rowNotaCredito['montoNetoNotaCredito'];
				//$montoPagado = $rowNotaCredito['montoNetoNotaCredito'];
				$saldoTotal = $montoTotal - $montoNotaCredito;
				$arrayCliente[$idCliente]['conteo_notas']++;
			}*/
			
			$idCliente = $rowEstadoCabecera['idCliente'];
			$arrayCliente[$idCliente]['nombre_cliente'] = $rowEstadoCabecera['nombre_cliente'];
			$arrayCliente[$idCliente]['monto_total'] = $arrayCliente[$idCliente]['monto_total'] + $montoTotal;
			$arrayCliente[$idCliente]['monto_pagado'] = $arrayCliente[$idCliente]['monto_pagado'] + $montoPagado;
			$arrayCliente[$idCliente]['saldo'] = $arrayCliente[$idCliente]['saldo'] + $saldoTotal;
			$arrayCliente[$idCliente]['conteo_factura']++;
		}
	
		foreach($arrayCliente as $facturasCliente){
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			//$idCliente = $rowEstadoCabecera['idCliente'];
			$montoTotal = $facturasCliente['monto_total'];
			$montoPagado = $facturasCliente['monto_pagado'];			

			$saldoTotal = $facturasCliente['saldo'];
					
			switch($valor) {
				case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
				case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
				case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
				case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
				default : $imgDctoModulo = $valor;
			}
			
			
			
			$html .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
				$html .= "<td align=\"center\">".$imgDctoModulo."</td>";
				$html .= "<td align=\"center\">".$facturasCliente['conteo_factura']."</td>";
				$html .= "<td align=\"center\">".valTpDato($facturasCliente['conteo_notas'], "int")."</td>";
				$html .= "<td align=\"left\">".utf8_encode($facturasCliente['nombre_cliente'])."</td>";
				$html .= "<td align=\"right\">".number_format($montoTotal,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($montoPagado,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($saldoTotal,2,'.',',')."</td>";
				//$html .= sprintf("<td align=\"center\" class=\"noprint\"><a href=\"".$accion."\" target=\"_blank\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Documento\"/></a></td>",$idFactura);
			$html .= "</tr>";
			
			$montoTotalFinal += $montoTotal;
			$montoFinal += $montoPagado;
			$saldoFinal += $saldoTotal;				
			
		}
		
		$html .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$html .= "<td class=\"tituloCampo\" colspan=\"4\">Totales:</td>";
			$html .= "<td>".number_format($montoTotalFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($montoFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($saldoFinal,2,'.',',')."</td>";
		$html .= "</tr>";
		$html .= "</table>";
		
		$objResponse->assign("tdCabeceraEstado","innerHTML",$html);
	} else {
		$objResponse->assign("tdCabeceraEstado","innerHTML","<table cellpadding='0' cellspacing='0' class='divMsjError' width='100%'>
				<tr>
					<td width='25'><img src='../img/iconos/ico_fallido.gif' width='25'/></td>
					<td align='center'>No se encontraron registros</td>
				</tr>
			</table>");		
	}
	
	return $objResponse;
}

function listarNotaCargoIndividual($idCliente,$idEmpresa,$fechaCierre,$valForm){
	$objResponse = new xajaxResponse();
	
	//TODOS LOS CLIENTES (RESUMEN)	
	$html = "<table border=\"0\" cellpadding=\"2\" width=\"100%\">
	<tr align=\"center\" class=\"tituloColumna\">
		<td></td>
		<td width='10%'>".utf8_encode("Empresa")."</td>
		<td width=\"9%\">".utf8_encode("Nro. Documento")."</td>
		<td width=\"9%\">".utf8_encode("Fecha Registro")."</td>
		<td width=\"10%\">".utf8_encode("Fecha Vencimiento")."</td>
		<td width=\"28%\">".utf8_encode("Cliente")."</td>
		<td width=\"10%\">".utf8_encode("Monto Total")."</td>
		<td width=\"10%\">".utf8_encode("Monto Pagado")."</td>
		<td width=\"10%\">".utf8_encode("Saldo")."</td>
		<td></td>
	</tr>";
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = " AND ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_nd.id_empresa = '".$idEmpresa."'
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = vw_cxc_nd.id_empresa))",
			valTpDato($idEmpresa, "int"));
	}
	
	if ($idCliente != "-1" && $idCliente != "") {
		$cond = " AND ";
		$sqlCliente .= $cond.sprintf("vw_cxc_nd.idCliente = %s",
			valTpDato($idCliente, "int"));
	}
			
	if ($valForm['cbxModulo']) {
		//$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$cond = " AND ";
		$idModulos = $cond."vw_cxc_nd.idDepartamento IN (";
		foreach ($valForm['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr($idModulos, 0, (strlen($idModulos)-1));
		$idModulos .= ")";
	}
	
	$fechaCxC_nc = date("Y-m-d", strtotime($fechaCierre));
	
	$queryEstadoCabecera = sprintf("SELECT
		vw_cxc_nd.idNotaCargo,
		vw_cxc_nd.idCliente,
		vw_cxc_nd.numeroNotaCargo,
		vw_cxc_nd.idDepartamento,
		SUM(vw_cxc_nd.montoTotal) AS montoTotal,
		(SELECT IFNULL(SUM(pagos.monto_pago),0)
			FROM cj_det_nota_cargo pagos, vw_cc_cuentas_por_cobrar_nd
			WHERE vw_cc_cuentas_por_cobrar_nd.idNotaCargo = pagos.idNotaCargo AND pagos.fechaPago <= '%s'
			AND cliente.id = vw_cxc_nd.idCliente
			AND vw_cxc_nd.idNotaCargo = pagos.idNotaCargo) AS monto_pago,
		vw_cxc_nd.fechaRegistro,
		vw_cxc_nd.fechaVencimiento,
		vw_cxc_nd.idCliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cc_cuentas_por_cobrar_nd vw_cxc_nd
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN cj_cc_cliente cliente ON (vw_cxc_nd.idCliente = cliente.id)
	WHERE vw_cxc_nd.fechaRegistro <= '%s' %s %s %s
	GROUP BY vw_cxc_nd.idCliente, vw_cxc_nd.idNotaCargo
	HAVING (montoTotal - monto_pago) > 1
	ORDER BY idNotaCargo DESC", $fechaCxC_nc,$fechaCxC_nc,$idModulos,$sqlCliente,$sqlBusq);
	$rsEstadoCabecera = mysql_query($queryEstadoCabecera);
	if (!$rsEstadoCabecera) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if (mysql_num_rows($rsEstadoCabecera) > 0) {
		while ($rowEstadoCabecera = mysql_fetch_array($rsEstadoCabecera)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$idNotaCargo = $rowEstadoCabecera['idNotaCargo'];
			$fechaRegistro = $rowEstadoCabecera['fechaRegistro'];
			$fechaVencimiento = $rowEstadoCabecera['fechaVencimiento'];
			$nroDocumento = $rowEstadoCabecera['numeroNotaCargo'];
			$montoTotal = $rowEstadoCabecera['montoTotal'];
			$montoPagado = $rowEstadoCabecera['monto_pago'];
			$saldoTotal = $montoTotal - $montoPagado;
			$accion = "cc_nota_debito_form.php?id=%s&acc=0";
			$imgDevuelta = "";
			
			switch($rowEstadoCabecera['idDepartamento']) {
				case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
				case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
				case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
				case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
				default : $imgDctoModulo = $rowEstadoCabecera['idDepartamento'];
			}
			
			//VERIFICA SI EL DOCUMENTO ESTÁ DEVUELTO (SI TIENE NOTA DE CREDIO)
			/*$queryNotaCredito = sprintf("SELECT idDocumento, montoNetoNotaCredito
			FROM cj_cc_notacredito
			WHERE idDocumento = %s
				AND fechaNotaCredito <= %s
				AND idDepartamentoNotaCredito IN (%s)",
				valTpDato($rowEstadoCabecera['idFactura'], "int"),
				valTpDato($fechaCxC_nc, "date"),
				valTpDato($valor, "int"));
			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			if (mysql_num_rows($rsNotaCredito) > 0) {
				$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
				
				$montoNotaCredito = $rowNotaCredito['montoNetoNotaCredito'];
				//$montoPagado = $rowNotaCredito['montoNetoNotaCredito'];
				$saldoTotal = $montoTotal - $montoNotaCredito;
				
				$imgDevuelta = "<img align=\"left\" title=\"Factura Devuelta\" src=\"../img/iconos/book_previous.png\">";
				$clase = "divMsjAlertaSinBorde";
			}*/
			
			$html .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
				$html .= "<td align=\"center\">".$imgDctoModulo."</td>";
				$html .= "<td align=\"left\">".utf8_encode($rowEstadoCabecera['nombre_empresa'])."</td>";
				$html .= "<td align=\"right\">".$imgDevuelta.$nroDocumento."</td>";
				$html .= "<td align=\"center\">".date(spanDateFormat, strtotime($fechaRegistro))."</td>";
				$html .= "<td align=\"center\">".date(spanDateFormat, strtotime($fechaVencimiento))."</td>";
				$html .= "<td align=\"left\">".utf8_encode($rowEstadoCabecera['nombre_cliente'])."</td>";
				$html .= "<td align=\"right\">".number_format($montoTotal,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($montoPagado,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($saldoTotal,2,'.',',')."</td>";
				$html .= sprintf("<td align=\"center\" class=\"noprint\"><a href=\"".$accion."\" target=\"_blank\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Documento\"/></a></td>",$idNotaCargo);
			$html .= "</tr>";
			
			$montoTotalFinal += $montoTotal;
			$montoFinal += $montoPagado;
			$saldoFinal += $saldoTotal;
		}
		
		$html .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$html .= "<td class=\"tituloCampo\" colspan=\"6\">Totales:</td>";
			$html .= "<td>".number_format($montoTotalFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($montoFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($saldoFinal,2,'.',',')."</td>";
			$html .= "<td></td>";
		$html .= "</tr>";
		$html .= "</table>";
		
		$objResponse->assign("tdCabeceraEstado","innerHTML",$html);
	} else {
		$objResponse->assign("tdCabeceraEstado","innerHTML","<table cellpadding='0' cellspacing='0' class='divMsjError' width='100%'>
				<tr>
					<td width='25'><img src='../img/iconos/ico_fallido.gif' width='25'/></td>
					<td align='center'>No se encontraron registros</td>
				</tr>
			</table>");		
	}
	
	return $objResponse;
}

function listarNotaCargoDetalle($idCliente,$idEmpresa,$fechaCierre,$valForm){
	$objResponse = new xajaxResponse();
	
	//TODOS LOS CLIENTES (RESUMEN)	
	$html = "<table border=\"0\" cellpadding=\"2\" width=\"100%\">
	<tr align=\"center\" class=\"tituloColumna\">
		<td></td>
		<td width=\"10%\">".utf8_encode("Nro. Documento")."</td>
		<td width=\"10%\">".utf8_encode("Fecha Registro")."</td>
		<td width=\"10%\">".utf8_encode("Fecha Vencimiento")."</td>
		<td width=\"28%\">".utf8_encode("Cliente")."</td>
		<td width=\"14%\">".utf8_encode("Monto Total")."</td>
		<td width=\"14%\">".utf8_encode("Monto Pagado")."</td>
		<td width=\"14%\">".utf8_encode("Saldo")."</td>
		<td></td>
	</tr>";
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = " AND ";
		$sqlBusq .= $cond.sprintf("(vw_cxc_nd.id_empresa = %s
			OR ".$idEmpresa." IN (SELECT id_empresa_padre FROM pg_empresa
									WHERE pg_empresa.id_empresa = vw_cxc_nd.id_empresa))",
			valTpDato($idEmpresa, "int"));
	}
	
	/*if ($idCliente != "-1" && $idCliente != "") {
		$cond = " AND ";
		$sqlCliente .= $cond.sprintf("vw_cxc_nd.idCliente = %s",
			valTpDato($idCliente, "int"));
	}*/
			
	if ($valForm['cbxModulo']) {
		//$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$cond = " AND ";
		$idModulos = $cond."vw_cxc_nd.idDepartamento IN (";
		foreach ($valForm['cbxModulo'] as $pos => $valor){
			$idModulos .= sprintf("%s,",$valor);
		}
		$idModulos = substr($idModulos, 0, (strlen($idModulos)-1));
		$idModulos .= ")";
	}
	
	$fechaCxC_nc = date("Y-m-d", strtotime($fechaCierre));
	
	$queryEstadoCabecera = sprintf("SELECT
		vw_cxc_nd.idNotaCargo,
		vw_cxc_nd.idCliente,
		vw_cxc_nd.numeroNotaCargo,
		vw_cxc_nd.idDepartamento,
		SUM(vw_cxc_nd.montoTotal) AS montoTotal,
		(SELECT IFNULL(SUM(pagos.monto_pago),0)
			FROM cj_det_nota_cargo pagos, vw_cc_cuentas_por_cobrar_nd
			WHERE vw_cc_cuentas_por_cobrar_nd.idNotaCargo = pagos.idNotaCargo AND pagos.fechaPago <= '%s'
			AND cliente.id = vw_cxc_nd.idCliente
			AND vw_cxc_nd.idNotaCargo = pagos.idNotaCargo) AS monto_pago,
		vw_cxc_nd.fechaRegistro,
		vw_cxc_nd.fechaVencimiento,
		vw_cxc_nd.idCliente,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
	FROM vw_cc_cuentas_por_cobrar_nd vw_cxc_nd
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN cj_cc_cliente cliente ON (vw_cxc_nd.idCliente = cliente.id)
	WHERE vw_cxc_nd.fechaRegistro <= '%s' %s %s
	GROUP BY vw_cxc_nd.idCliente, vw_cxc_nd.idNotaCargo
	HAVING (montoTotal - monto_pago) > 1
	ORDER BY idNotaCargo DESC", $fechaCxC_nc,$fechaCxC_nc,$idModulos,$sqlBusq);
	$rsEstadoCabecera = mysql_query($queryEstadoCabecera);
	if (!$rsEstadoCabecera) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if (mysql_num_rows($rsEstadoCabecera) > 0) {
		while ($rowEstadoCabecera = mysql_fetch_array($rsEstadoCabecera)) {
			$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila++;
			
			$idNotaCargo = $rowEstadoCabecera['idNotaCargo'];
			$fechaRegistro = $rowEstadoCabecera['fechaRegistro'];
			$fechaVencimiento = $rowEstadoCabecera['fechaVencimiento'];
			$nroDocumento = $rowEstadoCabecera['numeroNotaCargo'];
			$montoTotal = $rowEstadoCabecera['montoTotal'];
			$montoPagado = $rowEstadoCabecera['monto_pago'];
			$saldoTotal = $montoTotal - $montoPagado;
			$accion = "cc_nota_debito_form.php?id=%s&acc=0";
			$imgDevuelta = "";
			
			switch($rowEstadoCabecera['idDepartamento']) {
				case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
				case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
				case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehiculos\"/>"; break;
				case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administracion\"/>"; break;
				case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
				case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
				default : $imgDctoModulo = $rowEstadoCabecera['idDepartamento'];
			}
			
			//VERIFICA SI EL DOCUMENTO ESTÁ DEVUELTO (SI TIENE NOTA DE CREDIO)
			/*$queryNotaCredito = sprintf("SELECT idDocumento, montoNetoNotaCredito
			FROM cj_cc_notacredito
			WHERE idDocumento = %s
				AND fechaNotaCredito <= %s
				AND idDepartamentoNotaCredito IN (%s)",
				valTpDato($rowEstadoCabecera['idFactura'], "int"),
				valTpDato($fechaCxC_nc, "date"),
				valTpDato($valor, "int"));
			$rsNotaCredito = mysql_query($queryNotaCredito);
			if (!$rsNotaCredito) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			if (mysql_num_rows($rsNotaCredito) > 0) {
				$rowNotaCredito = mysql_fetch_array($rsNotaCredito);
				
				$montoNotaCredito = $rowNotaCredito['montoNetoNotaCredito'];
				//$montoPagado = $rowNotaCredito['montoNetoNotaCredito'];
				$saldoTotal = $montoTotal - $montoNotaCredito;
				
				$imgDevuelta = "<img align=\"left\" title=\"Factura Devuelta\" src=\"../img/iconos/book_previous.png\">";
				$clase = "divMsjAlertaSinBorde";
			}*/
			
			$html .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"24\">";
				$html .= "<td align=\"center\">".$imgDctoModulo."</td>";
				$html .= "<td align=\"right\">".$imgDevuelta.$nroDocumento."</td>";
				$html .= "<td align=\"center\">".date(spanDateFormat, strtotime($fechaRegistro))."</td>";
				$html .= "<td align=\"center\">".date(spanDateFormat, strtotime($fechaVencimiento))."</td>";
				$html .= "<td align=\"left\">".utf8_encode($rowEstadoCabecera['nombre_cliente'])."</td>";
				$html .= "<td align=\"right\">".number_format($montoTotal,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($montoPagado,2,'.',',')."</td>";
				$html .= "<td align=\"right\">".number_format($saldoTotal,2,'.',',')."</td>";
				$html .= sprintf("<td align=\"center\" class=\"noprint\"><a href=\"".$accion."\" target=\"_blank\"><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" title=\"Ver Documento\"/></a></td>",$idNotaCargo);
			$html .= "</tr>";
			
			$montoTotalFinal += $montoTotal;
			$montoFinal += $montoPagado;
			$saldoFinal += $saldoTotal;
		}
		
		$html .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"24\">";
			$html .= "<td class=\"tituloCampo\" colspan=\"5\">Totales:</td>";
			$html .= "<td>".number_format($montoTotalFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($montoFinal,2,'.',',')."</td>";
			$html .= "<td>".number_format($saldoFinal,2,'.',',')."</td>";
			$html .= "<td></td>";
		$html .= "</tr>";
		$html .= "</table>";
		
		$objResponse->assign("tdCabeceraEstado","innerHTML",$html);
	} else {
		$objResponse->assign("tdCabeceraEstado","innerHTML","<table cellpadding='0' cellspacing='0' class='divMsjError' width='100%'>
				<tr>
					<td width='25'><img src='../img/iconos/ico_fallido.gif' width='25'/></td>
					<td align='center'>No se encontraron registros</td>
				</tr>
			</table>");		
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarClientes");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarFecha");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"exportarAntiguedadSaldo");
$xajax->register(XAJAX_FUNCTION,"listadoClientes");
$xajax->register(XAJAX_FUNCTION,"listarFacturaIndividual");
$xajax->register(XAJAX_FUNCTION,"listarFacturaDetalle");
$xajax->register(XAJAX_FUNCTION,"listarFacturaResumen");
$xajax->register(XAJAX_FUNCTION,"listarNotaCargoIndividual");
$xajax->register(XAJAX_FUNCTION,"listarNotaCargoDetalle");
$xajax->register(XAJAX_FUNCTION,"listarNotaCargoResumen");
?>