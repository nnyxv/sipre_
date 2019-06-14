<?php 
function buscarFactura($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoFacturas(0,'','','%s' + '|' + %s);",
		$valForm['txtBusq'],
		$valForm['selEmpresa']));
	
	return $objResponse;
}

function listadoFacturas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 7, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$sqlBusq = sprintf(" AND numero_factura_proveedor LIKE %s ",
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	if ($valCadBusq[1] == -1)
		$sqlBusq .= " AND id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."'";
	else if ($valCadBusq[1] != 0)
		$sqlBusq .= " AND id_empresa = '".$valCadBusq[1]."'";
	
	$query = sprintf("SELECT * FROM cp_factura WHERE estatus_factura <> 1 %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit) or die(mysql_error());
	
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$objResponse->assign("tdFlotanteTitulo","innerHTML","Facturas");
	$objResponse->assign("tblListados","width","800px");
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
			valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	$rsEmpresa = mysql_query($queryEmpresa) or die (mysql_error());
	
	$htmlSelEmpresa = "<select id=\"selEmpresa\" name=\"selEmpresa\" onChange=\"xajax_buscarFactura(xajax.getFormValues('frmBuscarFactura'))\">";
	$htmlSelEmpresa .="<option value=\"0\">Todas</option>";
	
	while ($rowEmpresa = mysql_fetch_assoc($rsEmpresa)) {
		$nombreSucursal = "";
		if ($rowEmpresa['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";
		
		$selected = "";
		if ($valCadBusq[1] > -1){
			if ($valCadBusq[1] == $rowEmpresa['id_empresa_reg'])
				$selected = "selected=\"selected\"";
		}
		else{
			if ($_SESSION['idEmpresaUsuarioSysGts'] == $rowEmpresa['id_empresa_reg'])
				$selected = "selected=\"selected\"";
		}
		
		$htmlSelEmpresa .= "<option ".$selected." value=\"".$rowEmpresa['id_empresa_reg']."\">".htmlentities($rowEmpresa['nombre_empresa'].$nombreSucursal)."</option>";
		}
		$htmlSelEmpresa .= "</select>";
	
	$htmlTableIni1 .= "<form id=\"frmBuscarFactura\"><table width=\"50%\">";
	$htmlTh1 .= "<tr>
					<td align=\"right\" class=\"tituloCampo\">Empresa/Sucursal:</td>
					<td id='tdSelEmpresa'>
						".$htmlSelEmpresa."
                    </td>
					<td align=\"right\" class=\"tituloCampo\">No.Factura:</td>
					<td align=\"left\"><input type=\"text\" name=\"txtBusq\" id=\"txtBusq\" value=\"".$valCadBusq[0]."\"/><input type=\"button\" name=\"btnBuscarFacturas\" id=\"btnBuscarFacturas\" value=\"Buscar\" onclick=\"xajax_buscarFactura(xajax.getFormValues('frmBuscarFactura'));\"/></td>
					
				</tr>
				<tr>
					<td></td>
				</tr>";
	$htmlTableFin1 .= "</table></form>";
				
	$htmlTableIni .= "<table width=\"100%\">";
	$htmlTh .= "
				<tr class=\"tituloColumna\">
					<td></td>
					<td align=\"center\" width=\"14%\">Empresa/Sucursal</td>
					<td align=\"center\" width=\"8%\">Numero de Factura</td>
					<td align=\"center\" width=\"13%\">Numero de Control</td>
					<td align=\"center\" width=\"13%\">Rif</td>
					<td align=\"center\" width=\"13%\">Proveedor</td>
					<td align=\"center\" width=\"13%\">Fecha Factura Origen</td>
					<td align=\"center\" width=\"13%\">Fecha Factura Proveedor</td>
					<td align=\"center\" width=\"13%\">Saldo Factura</td>
				</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'", $row['id_proveedor']);
		$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
		$rowProveedor = mysql_fetch_array($rsProveedor);
		
		$queryEmpresa = "SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$row['id_empresa']."'";
		$rsEmpresa = mysql_query($queryEmpresa) or die (mysql_error());
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
			
		$nombreSucursal = "";
		if ($rowEmpresa['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$rowEmpresa['nombre_empresa_suc']." (".$rowEmpresa['sucursal'].")";	
		
		$empresa = htmlentities($rowEmpresa['nombre_empresa'].$nombreSucursal);		
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_cargarDatosNotaCredito('".$row['id_factura']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$empresa."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_factura_proveedor']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_control_factura']."</td>";
			$htmlTb .= "<td align=\"center\">".$rowProveedor['lrif']."-".$rowProveedor['rif']."</td>";
			$htmlTb .= "<td align=\"center\">".$rowProveedor['nombre']."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_factura_proveedor']))."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['saldo_factura'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\">";
		$htmlTf .= "<td align=\"center\" colspan=\"9\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_pri2.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) { 
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ant2.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";
				
					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoFacturas(%s,'%s','%s','%s',%s)\">",
						"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
					for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
							$htmlTf .= "<option value=\"".$nroPag."\"";
							if ($pageNum == $nroPag) {
								$htmlTf .= "selected=\"selected\"";
							}
							$htmlTf .= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
					}
					$htmlTf .= "</select>";
					
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_sig.png\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFacturas(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_cxp_reg_ult.png\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"9\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("tdListado","innerHTML",$htmlTableIni1.$htmlTh1.$htmlTableFin1.$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	$objResponse->script("xajax_comboEmpresa()");
	
	$objResponse->script("		
		$('tblListados').style.display='';
		$('tblMontos').style.display='none';");
	
	$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
	");
	
	return $objResponse;
}

function cargarDatosNotaCredito($idDocumento){
	$objResponse = new xajaxResponse();
	
	/*Limpia el Formulario*/
	
	$objResponse->script("
		document.forms['frmProveedor'].reset();
		document.forms['frmDatosFactura'].reset();
		document.forms['frmDatosNotaCredito'].reset();
		document.forms['frmTotalNotaCredito'].reset();");
		
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	for ($cont = 0; $cont <= strlen($valForm['hddObjGastos']); $cont++) {
		$caracter = substr($valForm['hddObjGastos'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}
	}
	
	foreach($arrayObj as $indiceItm=>$valorItm) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmGasto:%s');
			
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItm));
	}
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	
	for ($cont = 0; $cont <= strlen($valForm['hddObjMontos']); $cont++) {
		$caracter = substr($valForm['hddObjMontos'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}
	}
	
	foreach($arrayObj as $indiceItm=>$valorItm) {
		$objResponse->script(sprintf("
			fila = document.getElementById('trItmIva:%s');
			
			padre = fila.parentNode;
			padre.removeChild(fila);",
		$valorItm));
	}
	/*<-------Fin Funciones Limpiar Form------->*/
	
	$queryFactura = sprintf("SELECT * FROM cp_factura WHERE id_factura = '%s'", $idDocumento);
	$rsFactura = mysql_query($queryFactura) or die(mysql_error());
	
	$rowFactura = mysql_fetch_assoc($rsFactura);
	
	$objResponse->script("xajax_cargarTituloEmpresa(".$rowFactura['id_empresa'].")");
	

	$queryProveedor = sprintf("SELECT * FROM cp_proveedor WHERE id_proveedor = '%s'", $rowFactura['id_proveedor']);
	$rsProveedor = mysql_query($queryProveedor) or die(mysql_error());
	$rowProveedor = mysql_fetch_array($rsProveedor);
	
	
	
	
	
	if($rowFactura['id_modulo']=='0'){
		$modulo="Repuestos";
	}
	if($rowFactura['id_modulo']=='1'){
		$modulo="Servicios";
	}
	if($rowFactura['id_modulo']=='2'){
		$modulo="Vehiculos";
	}
	if($rowFactura['id_modulo']=='3'){
		$modulo="Administracion";
	}
	if($rowFactura['tipo_pago']=='0'){
		$tipoPago="Contado";
	}
	if($rowFactura['tipo_pago']=='1'){
		$tipoPago="Credito";
	}
	if($rowFactura['aplica_libros']=='0'){
		$aplicaLibros="NO";
	}
	if($rowFactura['aplica_libros']=='1'){
		$aplicaLibros="SI";
	}
	$fechaOrigen=substr($rowFactura['fecha_origen'],8,2).substr($rowFactura['fecha_origen'],4,4).substr($rowFactura['fecha_origen'],0,4);
	$fechaFactura=substr($rowFactura['fecha_factura_proveedor'],8,2).substr($rowFactura['fecha_factura_proveedor'],4,4).substr($rowFactura['fecha_factura_proveedor'],0,4);
	
	
	$objResponse->assign("txtIdProv","value",$rowProveedor['id_proveedor']);
	$objResponse->assign("txtNombreProv","value",$rowProveedor['nombre']);
	
	$objResponse->assign("txtContactoProv","value",$rowProveedor['contacto']);
	$objResponse->assign("txtDireccionProv","value",utf8_encode($rowProveedor['direccion']));
	$objResponse->assign("txtRifProv","value",$rowProveedor['lrif'].$rowProveedor['rif']);
	$objResponse->assign("txtEmailContactoProv","value",$rowProveedor['correococtacto']);
	$objResponse->assign("txtTelefonosProv","value",$rowProveedor['telefono']);
	$objResponse->assign("txtFaxProv","value",$rowProveedor['fax']);/**/

	
	$objResponse->assign("txtNumeroFacturaProveedor","value",$rowFactura['numero_factura_proveedor']);
	$objResponse->assign("hddObjIdFactura","value",$rowFactura['id_factura']);
	$objResponse->assign("txtNumeroControl","value",$rowFactura['numero_control_factura']);
	$objResponse->assign("txtFechaProveedor","value",$fechaFactura);
	$objResponse->assign("txtFechaOrigen","value",$fechaOrigen);
	$objResponse->assign("txtAplicaLibros","value",$aplicaLibros);
	$objResponse->assign("txtDepartamento","value",$modulo);
	$objResponse->assign("txtTipoPago","value",$tipoPago);
	$objResponse->assign("txtObservacionFactura","value",$rowFactura['observacion_factura']);
	$objResponse->assign("hddIdEmpresa","value",$rowFactura['id_empresa']);
	
	
	$objResponse->assign("txtSubTotal","value",number_format($rowFactura['subtotal_factura'],2,",","."));
	$objResponse->assign("txtSubTotalDescuento","value",number_format($rowFactura['subtotal_descuento'],2,",","."));
	$objResponse->assign("txtMontoExonerado","value",number_format($rowFactura['monto_exonerado'],2,",","."));
	$objResponse->assign("txtMontoExento","value",number_format($rowFactura['monto_exento'],2,",","."));
	$objResponse->assign("hddObjtxtSubTotal","value",$rowFactura['subtotal_factura']);
	$objResponse->assign("hddObjtxtSubTotalDescuento","value",$rowFactura['subtotal_descuento']);
	$objResponse->assign("hddObjtxtMontoExonerado","value",$rowFactura['monto_exonerado']);
	$objResponse->assign("hddObjtxtMontoExento","value",$rowFactura['monto_exento']);
	
	

	 /********CICLO PARA MONTAR LOS MONTOS EN MONTOS DE LOS IVA EN LA FACTURA *********/
	 
	 
	 
	$queryIvas = sprintf("SELECT * FROM cp_factura_iva WHERE id_factura='%s'", $rowFactura['id_factura']);
	
	$rsIvas = mysql_query($queryIvas) or die(mysql_error());
	while($rowIvas = mysql_fetch_array($rsIvas)) {
	$clase = "trResaltar4";
		$queryTipoIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = '%s'", $rowIvas['id_iva']);
		$rsTipoIva = mysql_query($queryTipoIva);
		$rowTipoIva = mysql_fetch_array($rsTipoIva);
		
		
		$ivaTotal = $rowIvas['subtotal_iva'];
		
		$totalBaseMasIva = $ivaTotal + $totalBaseMasIva;
	
	
		$sigValor = $arrayObj[count($arrayObj)-1] + 1;


		$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItmIva:%s', 'class':'textoGris_11px %s', 'title':'trItmIva:%s'}).adopt([
					new Element('td', {'align':'right','class':'tituloCampo'}).setHTML(\"%s:\"),
					new Element('td', {'align':'right'}).setHTML(\"<input id='baseImponibleIva%s' name='baseImponibleIva%s' type='text' readonly='readonly' style='text-align:right' size='17' value='%s' />\"),
					new Element('td', {'align':'right'}).setHTML(\"<input id='porcentajeIva%s' name='porcentajeIva%s' type='text' readonly='readonly' style='text-align:right' size='5' value='%s' />%s\"),
					new Element('td', {'align':'right'}).setHTML(\"<input id='montoPorcentajeIva%s' name='montoPorcentajeIva%s' type='text' readonly='readonly' style='text-align:right' size='17' value='%s' />"."<input type='hidden' id='hddIdIva%s' name='hddIdIva%s' value='%s'/><input type='hidden' id='hddBaseimponible%s' name='hddBaseimponible%s' value='%s'/><input type='hidden' id='hddSubtotal%s' name='hddSubtotal%s' value='%s'/><input type='hidden' id='hddIva%s' name='hddIva%s' value='%s'/>\")]);
				elemento.injectBefore('tdDescuento');",
					$sigValor,$clase,$sigValor,
					$rowTipoIva['observacion'],
					$sigValor,$sigValor,number_format($rowIvas['base_imponible'],2,",","."),
					$sigValor,$sigValor,number_format($rowIvas['iva'],2,",","."),"%",
					$sigValor,$sigValor,number_format($rowIvas['subtotal_iva'],2,",","."),
					$sigValor,$sigValor,$rowIvas['id_iva'],
					$sigValor,$sigValor,$rowIvas['base_imponible'],
					$sigValor,$sigValor,$rowIvas['subtotal_iva'],
					$sigValor,$sigValor,$rowIvas['iva']));
		
		$arrayObj[] = $sigValor;				
	}
	$cadena = "";
	foreach($arrayObj as $indice => $valor) {
		$cadena .= "|".$valor;
	}
	$objResponse->assign("hddObjMontos","value",$cadena);
	
	 /********CICLO PARA MONTAR LOS MONTOS EN MONTOS DE LOS GASTOS EN LA FACTURA *********/
	
	$queryGasto = sprintf("SELECT * FROM cp_factura_gasto WHERE id_factura='%s'", $rowFactura['id_factura']);
	
	$rsGasto = mysql_query($queryGasto);
	if (!$rsGasto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__.$queryGasto);
	$rowGasto = mysql_fetch_array($rsGasto);
	
	while($rowGasto = mysql_fetch_array($rsGasto)) {
	
	$clase = "trResaltar4";
		
		
		$queryTipoIva = sprintf("SELECT iva.*, IF (iva.tipo IN (3,2), 1, NULL) AS lujo FROM pg_iva iva WHERE iva.idIva = '%s'", $rowGasto['id_iva']);
		$rsTipoIva = mysql_query($queryTipoIva);
		$rowTipoIva = mysql_fetch_array($rsTipoIva);
		
		$queryGastos = sprintf("SELECT * FROM pg_gastos WHERE id_gasto ='%s' ",$rowGasto['id_gasto']);
		$rsTipoGasto = mysql_query($queryGastos);
		$rowTipoGasto = mysql_fetch_array($rsTipoGasto);
		
		$gastoTotalIva = $rowGasto['monto'] * ($rowGasto['iva']/100);

		$totalGastoBaseMasIva += $rowGasto['monto'];
		
	
		$sigValor = $arrayObjGasto[count($arrayObjGasto)-1] + 1;
		
		$objResponse->script(sprintf("
				var elemento = new Element('tr', {'id':'trItmGasto:%s', 'class':'textoGris_11px %s', 'title':'trItmGasto:%s'}).adopt([
					new Element('td', {'align':'right','class':'tituloCampo'}).setHTML(\"%s:\"),
					new Element('td', {'align':'right'}).setHTML(\"<input id='txtMontoGasto%s' name='txtMontoGasto%s' type='text' readonly='readonly' style='text-align:right' size='17' value='%s' />\"),
					new Element('td', {'align':'right'}).setHTML(\"<input id='txtPorcentajeIva%s' name='txtPorcentajeIva%s' type='text' readonly='readonly' style='text-align:right' size='5' value='%s' />%s\"),
					new Element('td', {'align':'right'}).setHTML(\"<input id='montoPorcentajeIva%s' name='montoPorcentajeIva%s' type='text' readonly='readonly' style='text-align:right' size='17' value='%s' />"."<input type='hidden' id='hddIdIvaGasto%s' name='hddIdIvaGasto%s' value='%s'/><input type='hidden' id='hddIdGasto%s' name='hddIdGasto%s' value='%s'/><input type='hidden' id='hddMontoGasto%s' name='hddMontoGasto%s' value='%s'/><input type='hidden' id='hddIvaGasto%s' name='hddIvaGasto%s' value='%s'/>\")]);
				elemento.injectBefore('tdDescuento');",
					$sigValor,$clase,$sigValor,
					$rowTipoGasto['nombre'],
					$sigValor,$sigValor,number_format($rowGasto['monto'],2,",","."),
					$sigValor,$sigValor,number_format($rowTipoIva['iva'],2,",","."),"%",
					$sigValor,$sigValor,number_format($gastoTotalIva,2,",","."),
					$sigValor,$sigValor,$rowGasto['id_iva'],
					$sigValor,$sigValor,$rowGasto['id_gasto'],
					$sigValor,$sigValor,$rowGasto['monto'],
					$sigValor,$sigValor,$rowGasto['iva']));
		
		$arrayObjGasto[] = $sigValor;
	}
	
	
	$cadena = "";
	if (isset($arrayObjGasto)) {
		foreach($arrayObjGasto as $indice => $valor) {
			$cadena .= "|".$valor;
		}
	}
	$objResponse->assign("hddObjGastos","value",$cadena);
	
	$totalNotaCredito = ($totalGastoBaseMasIva + $totalBaseMasIva +  $rowFactura['subtotal_factura'] ) - $rowFactura['subtotal_descuento']; 
	
	
	$objResponse->assign("txtTotalNotaCredito","value",number_format($totalNotaCredito,2,",","."));
	$objResponse->assign("hddtxtTotalNotaCredito","value",$totalNotaCredito);

$objResponse->script("
			$('tblListados').style.display='none';
			$('divFlotante').style.display='none';");
	return $objResponse;
}

function guardarDatos($valFormFactura,$valFormNotaCredito,$valFormProveedor,$valFormTotalNotaCredito){
	$objResponse = new xajaxResponse();
	
	/* DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS*/
	for ($cont = 0; $cont <= strlen($valFormTotalNotaCredito['hddObjMontos']); $cont++) {
		$caracter = substr($valFormTotalNotaCredito['hddObjMontos'], $cont, 1);
		
			
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObj[] = $cadena;
			$cadena = "";
		}	
	}
	
	/****INSERTA EN LA BASE DE DATOS LA NOTA DE CREDITO*****/
	for ($cont = 0; $cont <= strlen($valFormTotalNotaCredito['hddObjGastos']); $cont++) {
		$caracter = substr($valFormTotalNotaCredito['hddObjGastos'], $cont, 1);
		
		if ($caracter != "|" && $caracter != "")
			$cadena .= $caracter;
		else {
			$arrayObjGastos[] = $cadena;
			$cadena = "";
		}	
	}
		
	$fechaProveedorNotaCredito=substr($valFormNotaCredito['txtFechaProveedorNotaCredito'],6,4).substr($valFormNotaCredito['txtFechaProveedorNotaCredito'],2,4).substr($valFormNotaCredito['txtFechaProveedorNotaCredito'],0,2);
	$fechaOrigenNotaCredito=substr($valFormNotaCredito['txtFechaOrigenNotaCredito'],6,4).substr($valFormNotaCredito['txtFechaOrigenNotaCredito'],2,4).substr($valFormNotaCredito['txtFechaOrigenNotaCredito'],0,2);
	
	$queryFactura = sprintf("SELECT * FROM cp_factura WHERE id_factura='%s'",$valFormFactura['hddObjIdFactura']);
	$rsFactura = mysql_query($queryFactura) or die(mysql_error());
	$rowFactura = mysql_fetch_array($rsFactura);

	
	$saldoNotaCredito = $valFormTotalNotaCredito['hddtxtTotalNotaCredito']-$rowFactura['saldo_factura'];
	
	
	$insertSQL = sprintf("INSERT INTO cp_notacredito (id_empresa, numero_nota_credito, numero_control_notacredito, fecha_notacredito, fecha_registro_notacredito, id_proveedor, id_departamento_notacredito, id_documento, tipo_documento, estado_notacredito, observacion_notacredito, monto_exento_notacredito, monto_exonerado_notacredito, subtotal_notacredito, saldo_notacredito, aplica_libros_notacredito, id_empleado_creador)
	VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
		$valFormFactura['hddIdEmpresa'],
		$valFormNotaCredito['txtNumeroNotaCredito'],
		$valFormNotaCredito['txtNumeroControlNotaCredito'],
		$fechaProveedorNotaCredito,
		$fechaOrigenNotaCredito,
		$valFormProveedor['txtIdProv'],
		$valFormNotaCredito['slctDepartamentoNotaCredito'],
		$valFormFactura['hddObjIdFactura'],
		'FA',
		'1',
		$valFormNotaCredito['txtObservacionNotaCredito'],
		$valFormTotalNotaCredito['hddObjtxtMontoExento'],
		$valFormTotalNotaCredito['hddObjtxtMontoExonerado'],
		$valFormFactura['hddObjtxtSubTotal'],
		$valFormNotaCredito['slctAplicaLibrosCredito'],
		valTpDato($_SESSION['idEmpleadoSysGts'], "int"));
	$queryNotaCredito=mysql_query($insertSQL) or die(mysql_error());
	$idNotaCredito = mysql_insert_id();
	
	$queryUpdate = "UPDATE cp_factura SET saldo_factura='0', estatus_factura='1', activa = NULL WHERE id_factura = '$valFormFactura[hddObjIdFactura]'";
	$rsUpdate = mysql_query($queryUpdate) or die(mysql_error());
	
	$insertSQL = sprintf("INSERT INTO cp_estado_cuenta (tipoDocumento, idDocumento, fecha, tipoDocumentoN)
	VALUE (%s, %s, %s, %s);",
		valTpDato("NC", "text"),
		valTpDato($idNotaCredito, "int"),
		valTpDato(date("Y-m-d", strtotime($fechaOrigenNotaCredito)), "date"),
		valTpDato("3", "int")); // 1 = FA, 2 = ND, 3 = NC, 4 = AN, 5 = CH, 6 = TB (Segun Tabla tipodedocumentos)
	mysql_query("SET NAMES 'utf8';");
	$Result1 = mysql_query($insertSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	/****INSERTA EN LA BASE DE DATOS EL IVA LA NOTA DE CREDITO*****/
	
	if (isset($arrayObj)) {
		foreach($arrayObj as $indice => $valor) {
			if (strlen($valFormTotalNotaCredito['hddIdIva'.$valor]) > 0) {
				$insertivas = sprintf("INSERT INTO cp_notacredito_iva (id_notacredito, baseimponible_notacredito, subtotal_iva_notacredito, id_iva_notacredito, iva_notacredito) VALUE ('%s', '%s', '%s', '%s', '%s')",
					$idNotaCredito,
					$valFormTotalNotaCredito['hddBaseimponible'.$valor],
					$valFormTotalNotaCredito['hddSubtotal'.$valor], 
					$valFormTotalNotaCredito['hddIdIva'.$valor], 
					$valFormTotalNotaCredito['hddIva'.$valor]);
				$ResultInsertivas = mysql_query($insertivas) or die(mysql_error());
				
				
			}
		}
	}
	
	/****INSERTA EN LA BASE DE DATOS LOS GASTOS LA NOTA DE CREDITO*****/
	if (isset($arrayObjGastos)) {
		foreach($arrayObjGastos as $indiceGastos => $valorGastos) {
			if (strlen($valFormTotalNotaCredito['hddIdGasto'.$valorGastos]) > 0) {
				$insertGastos = sprintf("INSERT INTO cp_notacredito_gastos (id_notacredito, id_gastos_notacredito, tipo_gasto_notacredito, monto_gasto_notacredito, estatus_iva_notacredito, id_iva_notacredito, iva_notacredito) VALUE ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
					$idNotaCredito,
					$valFormTotalNotaCredito['hddIdGasto'.$valorGastos],
					'1'.$valorGastos,
					$valFormTotalNotaCredito['hddMontoGasto'.$valorGastos],
					'1'.$valorGastos, 
					$valFormTotalNotaCredito['hddIdIvaGasto'.$valorGastos], 
					$valFormTotalNotaCredito['hddIvaGasto'.$valorGastos]);
				$ResultInsertGastos = mysql_query($insertGastos) or die(mysql_error());
				
				
			}
		}
		
	}
 
 
 if(!mysql_error()){
		$objResponse->alert('Los Datos se han Guardado Correctamente ');
	}else{
		$objResponse->alert('Los Datos No se han Guardado');
	}
	
	$objResponse->script("window.location.href='cp_transacciones_diarias_documentos_notacredito_inicio.php'");
 
	return $objResponse;
}

function cargarTituloEmpresa($id_empresa){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = '".$id_empresa."'";
	$rs = mysql_query($query) or die(mysql_error());
	$row = mysql_fetch_array($rs);

	$td = "<table width='100%' class='textoNegrita_12px' align='center' border='0'>
                	<tr>
                    	<td align='center'>
						Empresa: ".$row['nombre_empresa']."
					<td>
				</tr>
				<tr>
					<td align='center'>
						Rif: ".$row['rif']."
					<td>
				</tr>
				<tr>
					<td align='center'>
						Telf: ".$row['telefono1']."
					<td>
				</tr>
			</table>";
	
	$objResponse->assign("tdTituloEmpresa","innerHTML",$td);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargarDatosNotaCredito");
$xajax->register(XAJAX_FUNCTION,"guardarDatos");
$xajax->register(XAJAX_FUNCTION,"listadoFacturas");
$xajax->register(XAJAX_FUNCTION,"asignarProveedor");
$xajax->register(XAJAX_FUNCTION,"buscarFactura");
$xajax->register(XAJAX_FUNCTION,"cargarTituloEmpresa");
?>