<?php
//FUNCIONES XAJAX

function cargarInteresMora($idInteresMora){
	$objResponse = new xajaxResponse();

	$queryInteresMora = "SELECT * FROM fi_intereses_mora WHERE id_interes_mora = $idInteresMora";
	$rsInteresMora = mysql_query($queryInteresMora);
	if (!$rsInteresMora) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$rowInteresMora = mysql_fetch_assoc($rsInteresMora);

	if ($rowInteresMora['estatus_interes_mora'] == 0){
		$cero = "selected=\"selected\"";
		$uno = "";
	}
	else{
		$cero = "";
		$uno = "selected=\"selected\"";
	}
	
	if ($rowInteresMora['descripcion_interes_mora'] == 1){
		$val1 = "selected=\"selected\"";
		$val2 = "";
	}
	else{
		$val1 = "";
		$val2 = "selected=\"selected\"";
	}


	$cadenaSelect = "<select id=\"selEstatus\" name=\"selEstatus\">
                     	<option value=\"-1\">[ Selected ]</option>
                     	<option value=\"1\" ".$uno.">Activa</option>
                     	<option value=\"0\" ".$cero.">Inactiva</option>
                     </select>";
	
	$cadenaSelect2 = "<select id=\"selTipoInteresMora\" name=\"selTipoInteresMora\">
                     	<option value=\"-1\">[ Selected ]</option>
                     	<option value=\"1\" ".$val1.">Monto Fijo</option>
                     	<option value=\"2\" ".$val2.">Porcentaje Fijo</option>
                     </select>";

	//IMPLEMENTANDO ATRIBUTOS

	$objResponse->assign("tdSelTipoInteresMora","value",  utf8_encode($rowInteresMora['descripcion_interes_mora']));
	$objResponse->assign("txtValorInteresMora","value",  utf8_encode($rowInteresMora['valor_interes_mora']));
	$objResponse->assign("tdselEstatus","innerHTML",$cadenaSelect);
	$objResponse->assign("tdSelTipoInteresMora","innerHTML",$cadenaSelect2);
	$objResponse->assign("hddIdInteresMora","value",$idInteresMora);

	$objResponse->script("byId('txtValorInteresMora').className = 'inputHabilitado';
						  byId('selTipoInteresMora').className = 'inputHabilitado';
					      byId('selEstatus').className = 'inputHabilitado';");

	return $objResponse;
}

function eliminarInteresMora($idInteresMora){

	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"fi_intereses_mora","eliminar")){
		return $objResponse;
	}

	$queryEliminar = sprintf("DELETE FROM fi_intereses_mora WHERE id_interes_mora = %s ;",
			$idInteresMora);

	$rsInteresMora = mysql_query($queryEliminar);
	if (!$rsInteresMora) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($rsInteresMora == true){
		$objResponse->script("xajax_listarInteresMora(0,'','','')");
		$objResponse->alert("InteresMora eliminado exitosamente.");
	}

	return $objResponse;
}

function guardarInteresMora($frmInteresMora){
	$objResponse = new xajaxResponse();

	if ($frmInteresMora['hddIdInteresMora'] == 0){
		$cadena = "insertado";

		$queryInteresMora = sprintf("INSERT INTO fi_intereses_mora (descripcion_interes_mora, valor_interes_mora, estatus_interes_mora) VALUES ('%s',%s,'%s');",
				$frmInteresMora['selTipoInteresMora'],
				$frmInteresMora['txtValorInteresMora'],
				$frmInteresMora['selEstatus']);

	} else {
		$cadena = "modificado";

		$queryInteresMora = sprintf("UPDATE fi_intereses_mora SET
								 	 descripcion_interes_mora = '%s',
								 	 valor_interes_mora = %s,
									 estatus_interes_mora = %s
							  	WHERE id_interes_mora = %s;",
				$frmInteresMora['selTipoInteresMora'],
				$frmInteresMora['txtValorInteresMora'],
				$frmInteresMora['selEstatus'],
				$frmInteresMora['hddIdInteresMora']);
	}

	$rs = mysql_query($queryInteresMora);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$objResponse->script("xajax_listarInteresMora(0,'','','');");
	$objResponse->script("byId('btnCancelarLista').click();");

	$objResponse->alert("Interes de Mora ".$cadena." exitosamente");

	return $objResponse;
}

function listarInteresMora($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();


	if (!xvalidaAcceso($objResponses,"fi_intereses_mora")){
		$objResponse->assign("tdListaInteresMora","innerHTML","Acceso Denegado");
		return $objResponse;
	}

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$startRow = $pageNum * $maxRows;

	$queryInteresMora = "SELECT * FROM fi_intereses_mora";
	$rsInteresMora = mysql_query($queryInteresMora);
	if (!$rsInteresMora) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$queryLimitMoneda = $queryInteresMora." LIMIT ".$maxRows." OFFSET ".$startRow.";";
	$rsLimitInteresMora = mysql_query($queryLimitMoneda);
	if (!$rsLimitInteresMora) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	if ($totalRows == NULL) {
		$rsMoneda = mysql_query($queryInteresMora) or die(mysql_error());
		$totalRows = mysql_num_rows($rsInteresMora);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";

	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">
				<td width='30%'>Descripcion</td>
            	<td width='31%'>Valor Interes</td>
            	<td width='31%'>Estatus</td>
				<td colspan='2'></td>
               </tr>";


	while ($rowInteresMora = mysql_fetch_assoc($rsLimitInteresMora)) {
		$clase = ($clase == "trResaltar5") ? $clase = "trResaltar4" : $clase = "trResaltar5";

		($rowInteresMora['estatus_interes_mora'] == 1)? $estatus = "Activa" : $estatus = "Inactiva";
		($rowInteresMora['descripcion_interes_mora'] == 1) ? $tipoIntMora = "Monto Fijo" : $tipoIntMora = "Porcentaje Fijo";

		$htmlTb .= "<tr class=\"".$clase."\">";
		$htmlTb .= "<td>".  utf8_encode($tipoIntMora)."</td>";
		$htmlTb .= "<td>".  utf8_encode($rowInteresMora['valor_interes_mora'])."</td>";
		$htmlTb .= "<td>".$estatus."</td>";
		$htmlTb .= "<td>";
		$htmlTb .= "<a class=\"modalImg\" id=\"editarInteresMora\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'Editar InteresMora','Editar',".$rowInteresMora['id_interes_mora'].");\">";
		$htmlTb .= "<img src='../img/iconos/ico_edit.png' title='Editar InteresMora'\>";
		$htmlTb .= "</a>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td><img src='../img/iconos/ico_quitar.gif' onclick=\"if (confirm('¿Desea eliminar el InteresMora?') == true) {
			xajax_eliminarInteresMora(".$rowInteresMora['id_interes_mora'].");
		}\"/></td>";
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarInteresMora(%s,'%s','%s','%s',%s);\">%s</a>",
				0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum > 0) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarInteresMora(%s,'%s','%s','%s',%s);\">%s</a>",
				max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"100\">";

	$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarInteresMora(%s,'%s','%s','%s',%s)\">",
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarInteresMora(%s,'%s','%s','%s',%s);\">%s</a>",
				min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum < $totalPages) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarInteresMora(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td class=\"divMsjError\" colspan=\"5\">";
		$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
		$htmlTb .= "<tr>";
		$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
		$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("tdListaInteresMora","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}


//REGISTROS XAJAX

$xajax->register(XAJAX_FUNCTION,"cargarInteresMora");
$xajax->register(XAJAX_FUNCTION,"eliminarInteresMora");
$xajax->register(XAJAX_FUNCTION,"guardarInteresMora");
$xajax->register(XAJAX_FUNCTION,"listarInteresMora");





?>