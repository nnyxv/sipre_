<?php
set_time_limit(0);

function calculoPorcentaje($valFormTotalDcto, $tpCalculo, $valor, $destino) {
	$objResponse = new xajaxResponse();
	
	$subTotal = str_replace(",","",$valFormTotalDcto['txtSubTotal']);
	$valor = str_replace(",","",$valor);
	
	$monto = ($tpCalculo == "Porc") ? $valor * ($subTotal/100) : $valor * (100/$subTotal);
	
	$objResponse->assign($destino,"value",number_format($monto, 2, ".", ","));
		
	$objResponse->script("xajax_calcularDcto(xajax.getFormValues('frmDcto'), xajax.getFormValues('frmListaArticulo'), xajax.getFormValues('frmTotalDcto'));");
	
	return $objResponse;
}

function formularioGastos($bloquea = "", $mantenerValores = "", $idPedido = "", $tipoPedido = "") {
	$objResponse = new xajaxResponse();
	
	$queryGastos = sprintf("SELECT *,
		(SELECT iva FROM pg_iva WHERE pg_iva.idIva = pg_gastos.id_iva) AS iva,
		(SELECT observacion FROM pg_iva WHERE pg_iva.idIva = pg_gastos.id_iva) AS observacion,
		(SELECT tipo FROM pg_iva WHERE pg_iva.idIva = pg_gastos.id_iva) AS tipo,
		(SELECT activo FROM pg_iva WHERE pg_iva.idIva = pg_gastos.id_iva) AS activo,
		(SELECT estado FROM pg_iva WHERE pg_iva.idIva = pg_gastos.id_iva) AS estado
	FROM pg_gastos");
	$rsGastos = mysql_query($queryGastos);
	if (!$rsGastos) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$html = "<table border=\"0\" width=\"100%\">";
	$cont = 0;
	while ($rowGastos = mysql_fetch_assoc($rsGastos)) {
		$cont++;
		$valueMontoPorc = number_format(0,"2",".",",");
		$valueMonto = number_format(0,"2",".",",");
		$checkPorc = "checked=\"checked\"";
		$readOnlyMonto = "readonly=\"readonly\"";
		$tipoGasto = 0;
		$estatusIva = $rowGastos['estatus_iva'];
		if ($idPedido != "") {
			if ($tipoPedido == "COMPRA") {
				$queryPedidoGastos = sprintf("SELECT * FROM iv_pedido_compra_gasto
				WHERE id_pedido_compra = %s
					AND id_gasto = %s",
					valTpDato($idPedido, "int"),
					valTpDato($rowGastos['id_gasto'], "int"));
				
			} else if ($tipoPedido == "VENTA") {
				$queryPedidoGastos = sprintf("SELECT * FROM iv_pedido_venta_gasto
				WHERE id_pedido_venta = %s
					AND id_gasto = %s",
					valTpDato($idPedido, "int"),
					valTpDato($rowGastos['id_gasto'], "int"));
				
			} else if ($tipoPedido == "PRESUPUESTO") {
				$queryPedidoGastos = sprintf("SELECT * FROM iv_presupuesto_venta_gasto
				WHERE id_presupuesto_venta = %s
					AND id_gasto = %s",
					valTpDato($idPedido, "int"),
					valTpDato($rowGastos['id_gasto'], "int"));
			}
			$rsPedidoGastos = mysql_query($queryPedidoGastos);
			if (!$rsPedidoGastos) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$rowPedidoGastos = mysql_fetch_assoc($rsPedidoGastos);
			
			if ($rowPedidoGastos['tipo'] == "0") { // PORCENTAJE
				$valueMontoPorc = number_format($rowPedidoGastos['porcentaje_monto'],"2",".",",");
				$valueMonto = number_format($rowPedidoGastos['monto'],"2",".",",");
				$checkPorc = "checked=\"checked\"";
				$checkMonto = "";
				$readOnlyPorc = "";
				$readOnlyMonto = "readonly=\"readonly\"";
				$tipoGasto = 0;
				$estatusIva = $rowPedidoGastos['estatus_iva'];
			} else if ($rowPedidoGastos['tipo'] == "1") { // MONTO
				$valueMontoPorc = number_format($rowPedidoGastos['porcentaje_monto'],"2",".",",");
				$valueMonto = number_format($rowPedidoGastos['monto'],"2",".",",");
				$checkPorc = "";
				$checkMonto = "checked=\"checked\"";
				$readOnlyPorc = "readonly=\"readonly\"";
				$readOnlyMonto = "";
				$tipoGasto = 1;
				$estatusIva = $rowPedidoGastos['estatus_iva'];
			}
		}
		
		if ($bloquea != "") {
			$disabled = "disabled=\"disabled\"";
			$readOnlyPorc = "readonly=\"readonly\"";
			$readOnlyMonto = "readonly=\"readonly\"";
		}
	
	$html .= "<tr align=\"right\" id=\"trGasto:".$cont."\" title=\"trGasto:".$cont."\">";
		$html .= "<td class=\"tituloCampo\" width=\"30%\">".$rowGastos['nombre'].":";
			$html .= sprintf("<input type=\"hidden\" id=\"hddIdGasto%s\" name=\"hddIdGasto%s\" value=\"%s\">",
				$cont, $cont, $rowGastos['id_gasto']);
			$html .= sprintf("<input type=\"hidden\" id=\"hddTipoGasto%s\" name=\"hddTipoGasto%s\" value=\"%s\">",
				$cont, $cont, $tipoGasto);
			$html .= sprintf("<input type=\"hidden\" id=\"hddIdIvaGasto%s\" name=\"hddIdIvaGasto%s\" value=\"%s\">",
				$cont, $cont, $rowGastos['id_iva']);
			$html .= sprintf("<input type=\"hidden\" id=\"hddIvaGasto%s\" name=\"hddIvaGasto%s\" value=\"%s\">",
				$cont, $cont, $rowGastos['iva']);
			$html .= sprintf("<input type='hidden' id=\"hddEstatusIvaGasto%s\" name=\"hddEstatusIvaGasto%s\" value=\"%s\">",
				$cont, $cont, $rowGastos['estatus_iva']);
		$html .= "</td>";
		$html .= "<td nowrap=\"nowrap\" width=\"22%\">";
			$html .= sprintf("<input id=\"rbtGastoPorc%s\" name=\"rbtGasto%s\" class=\"noprint\" onclick=\"$('hddTipoGasto%s').value = 0; $('txtPorcMontoGasto%s').readOnly = false; $('txtMontoGasto%s').readOnly = true;\" type=\"radio\" %s value=\"1\" %s/>",
				$cont, $cont, $cont, $cont, $cont, $checkPorc, $disabled);
			
			$html .= sprintf("<input type=\"text\" id=\"txtPorcMontoGasto%s\" name=\"txtPorcMontoGasto%s\" maxlength=\"8\" size=\"6\" style=\"text-align:right\" onfocus=\"if ($('txtPorcMontoGasto%s').value <= 0){ $('txtPorcMontoGasto%s').value = ''; }\" onkeypress=\"return validarSoloNumerosReales(event);\" onkeyup=\"xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Porc', this.value, 'txtMontoGasto%s');\" value=\"%s\" %s/>",
				$cont, $cont, $cont, $cont, $cont, $valueMontoPorc, $readOnlyPorc);
		$html .= "%</td>";
		$html .= "<td nowrap=\"nowrap\" width=\"30%\">";
			$html .= sprintf("<input id=\"rbtGastoMonto%s\" name=\"rbtGasto%s\" class=\"noprint\" onclick=\"$('hddTipoGasto%s').value = 1; $('txtPorcMontoGasto%s').readOnly = true; $('txtMontoGasto%s').readOnly = false;\" type=\"radio\" %s value=\"2\" %s/>",
				$cont, $cont, $cont, $cont, $cont, $checkMonto, $disabled);
			
			$html .= sprintf("<input type=\"text\" id=\"txtMontoGasto%s\" name=\"txtMontoGasto%s\" maxlength=\"8\" size=\"18\" style=\"text-align:right\" onfocus=\"if ($('txtMontoGasto%s').value <= 0){ $('txtMontoGasto%s').value = ''; }\" onkeypress=\"return validarSoloNumerosReales(event);\" onkeyup=\"xajax_calculoPorcentaje(xajax.getFormValues('frmTotalDcto'), 'Cant', this.value, 'txtPorcMontoGasto%s');\" value=\"%s\" %s/>",
				$cont, $cont, $cont, $cont, $cont, $valueMonto, $readOnlyMonto);
		$html .= "</td>";
		$html .= "<td width=\"18%\">";
			if ($estatusIva == 1) {
				$html .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$html .= "<tr>";
					$html .= "<td>"."<img src=\"../img/iconos/accept.png\" />"."</td>";
					$html .= "<td id=\"tdIvaArt".$cont."\">".$rowGastos['iva']."%</td>";
				$html .= "</tr>";
				$html .= "</table>";
			}
		$html .= "</td>";
	$html .= "</tr>";
	}
	$html .= "<tr>";
		$html .= "<td colspan=\"4\" class=\"divMsjInfo2\">";
			$html .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$html .= "<tr>";
				$html .= "<td width=\"25\"><img src=\"../img/iconos/ico_info.gif\" width=\"25\"/></td>";
				$html .= "<td align=\"center\">";
					$html .= "<table>";
					$html .= "<tr>";
						$html .= "<td><img src=\"../img/iconos/accept.png\" /></td>";
						$html .= "<td>Gastos que llevan IVA</td>";
					$html .= "</tr>";
					$html .= "</table>";
				$html .= "</td>";
			$html .= "</tr>";
			$html .= "</table>";
		$html .= "</td>";
	$html .= "</tr>";
	$html .= "</table>";
	
	return $html;
}


function encabezadoEmpresa($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (!($idEmpresa > 0)) {
		$idEmpresa = 100;
	}
	
	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	if ($row['id_empresa'] != "") {
		$html .= "<table class=\"textoNegrita_7px\">";
		$html .= "<tr align=\"center\">";
			$html .= "<td>";
				$html .= "<img src=\"../".htmlentities($row['logo_familia'])."\" width=\"100\"/>";
			$html .= "</td>";
			$html .= "<td>";
				$html .= "<table width=\"250\">";
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= htmlentities($row['nombre_empresa']);
					$html .= "</td>";
				$html .= "</tr>";
			if (strlen($row['rif']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>RIF: ";
						$html .= $row['rif'];
					$html .= "</td>";
				$html .= "</tr>";
			}
			if (strlen($row['direccion']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= htmlentities($row['direccion']);
					$html .= "</td>";
				$html .= "</tr>";
			}
			if (strlen($row['web']) > 1) {
				$html .= "<tr align=\"center\">";
					$html .= "<td>";
						$html .= htmlentities($row['web']);
					$html .= "</td>";
				$html .= "</tr>";
			}
				$html .= "<table>";
			$html .= "</td>";
		$html .= "</tr>";
		$html .= "<table>";
		
		$objResponse->assign("tdEncabezadoImprimir","innerHTML",$html);
	}
	
	return $objResponse;
}


function objetoCodigoDinamico($tdUbicacion, $idEmpresa, $idEmpArticulo = "", $valor = "", $formato = "", $bloquearObj = "false") {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("-", $formato);
	
	$valorCad = explode("-", $valor);
	
	if ($idEmpresa == "" && $formato == "")
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	/* SI NO SE PASA UN FORMATO, SE BUSCA EL FORMATO PREDEFINIDO DE LA EMPRESA*/
	if ($formato == "") {
		$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
			valTpDato($idEmpresa, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if ($row['id_empresa_reg'] != "")
			$valCadBusq = explode("-", $row['formato_codigo_repuestos']);
		else {
			$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
				valTpDato($idEmpArticulo, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$row = mysql_fetch_assoc($rs);
			
			$valCadBusq = explode("-", $row['formato_codigo_repuestos']);
		}
		
		$contTamano = 0;
		foreach ($valCadBusq as $indice => $valor) {
			$contTamano += $valor;
		}
	}
	
	/* SI LA CANTIDAD DE SUBDIVISIONES DEL FORMATO NO ES IGUAL A EL DE LA DATA, SE CONTRUIRA UN SOLO OBJETO CON TODO EL
	CODIGO SIN SUBDIVISIONES */
	if (count($valCadBusq) != count($valorCad) && $valor != "" && count($valorCad) > 1) {
		$contTamanoFormato = 0;
		foreach ($valCadBusq as $indice => $valor) {
			$contTamanoFormato += $valor;
		}
		$contTamanoCaracter = 0;
		foreach ($valorCad as $indice => $valor) {
			$contTamanoCaracter += strlen($valor);
		}
		
		$contTamano = $contTamanoFormato;
		if ($contTamanoFormato <= $contTamanoCaracter)
			$contTamano = $contTamanoCaracter;
		
		$valCadBusq = NULL;
		$valCadBusq[] = $contTamano;
		
		$value = "";
		foreach ($valorCad as $indice => $valor)
			$value .= $valor;
		
		$valorCad = NULL;
		$valorCad[] = $value;
	}
	
	$readonly = "";
	if ($bloquearObj == "true") {
		$readonly = "readonly=\"readonly\"";
	}
	
	$html = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
	$html .= "<tr>";
	foreach ($valCadBusq as $indice => $valor) {
		$value = "";
		if ($valor != "")
			$value = $valorCad[$indice];
		
		$tamanoObjeto = $valor+2;
		if (count($valCadBusq) == 1) {
			$tamanoObjeto = intval($contTamano) + 4;
		}
		
		$html .= sprintf("<td><input type=\"text\" id=\"txtCodigoArticulo%s\" name=\"txtCodigoArticulo%s\" onkeyup=\"letrasMayusculas(event, this.id);\" onkeypress=\"return validarCodigoArticulo(event);\" ".$readonly." size=\"%s\" maxlength=\"%s\" value=\"%s\"/></td><td>&nbsp;</td>",
			$indice,
			$indice,
			$tamanoObjeto,
			$valor,
			$value);
		
		$cantObjetos = strval($indice);
	}
	$html .= "</tr>";
	$html .= "</table>";
	$html .= sprintf("<input type=\"hidden\" id=\"hddCantCodigo\" name=\"hddCantCodigo\" readonly=\"readonly\" size=\"2\" value=\"%s\"/>",
		$cantObjetos);
	
	$objResponse->assign($tdUbicacion,"innerHTML",$html);

	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"calculoPorcentaje");
$xajax->register(XAJAX_FUNCTION,"formularioGastos");
$xajax->register(XAJAX_FUNCTION,"encabezadoEmpresa");
$xajax->register(XAJAX_FUNCTION,"objetoCodigoDinamico");
?>