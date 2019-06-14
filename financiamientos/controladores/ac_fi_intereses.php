<?php 
//FUNCIONES XAJAX

function cargarInteres($idInteres){
	$objResponse = new xajaxResponse();

	$queryInteres = "SELECT * FROM fi_interes WHERE id_interes = $idInteres";
	$rsInteres = mysql_query($queryInteres);
	if (!$rsInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$rowInteres = mysql_fetch_assoc($rsInteres);
	
	if ($rowInteres['estatus_interes'] == 0){
		$cero = "selected=\"selected\"";
		$uno = "";
	}
	else{
		$cero = "";
		$uno = "selected=\"selected\"";
	}


	$cadenaSelect = "<select id=\"selEstatus\" name=\"selEstatus\">
                     	<option value=\"-1\">[ Selected ]</option>
                     	<option value=\"1\" ".$uno.">Activa</option>
                     	<option value=\"0\" ".$cero.">Inactiva</option>
                     </select>";

	//IMPLEMENTANDO ATRIBUTOS

	$objResponse->assign("txtNombreInteres","value",  utf8_encode($rowInteres['descripcion_interes']));
	$objResponse->assign("txtInteres","value",  utf8_encode($rowInteres['valor_interes']));
	$objResponse->assign("tdselEstatus","innerHTML",$cadenaSelect);
	$objResponse->assign("hddIdInteres","value",$idInteres);

	$objResponse->script("byId('txtNombreInteres').className = 'inputHabilitado';
						  byId('txtInteres').className = 'inputHabilitado';
					      byId('selEstatus').className = 'inputHabilitado';");
	
	return $objResponse;
}

function eliminarInteres($idInteres){
	
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"fi_intereses","eliminar")){
		return $objResponse;
	}

	$queryEliminar = sprintf("DELETE FROM fi_interes WHERE id_interes = %s ;",
							$idInteres);
	
	$rsInteres = mysql_query($queryEliminar);
	if (!$rsInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($rsInteres == true){
		$objResponse->script("xajax_listarInteres(0,'','','')");
		$objResponse->alert("Interes eliminado exitosamente.");
	}

	return $objResponse;
}

function guardarInteres($frmInteres){
	$objResponse = new xajaxResponse();
	
	if ($frmInteres['hddIdInteres'] == 0){
		$cadena = "insertado";
		
		$queryInteres = sprintf("INSERT INTO fi_interes (descripcion_interes, valor_interes, estatus_interes) VALUES ('%s',%s,'%s');",
				$frmInteres['txtNombreInteres'],
				$frmInteres['txtInteres'],
				$frmInteres['selEstatus']);
		
	} else {
		$cadena = "modificado";
	
		$queryInteres = sprintf("UPDATE fi_interes SET 
								 	 descripcion_interes = '%s', 
								 	 valor_interes = %s,
									 estatus_interes = %s
							  	WHERE id_interes = %s;",
				$frmInteres['txtNombreInteres'],
				$frmInteres['txtInteres'],
				$frmInteres['selEstatus'],
				$frmInteres['hddIdInteres']);
	}
	
	$rs = mysql_query($queryInteres);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$objResponse->script("xajax_listarInteres(0,'','','');");
	$objResponse->script("byId('btnCancelarLista').click();");
	
	$objResponse->alert("Interes ".$cadena." exitosamente");
	
	return $objResponse;
}

function listarInteres($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	
	if (!xvalidaAcceso($objResponses,"fi_intereses")){
		$objResponse->assign("tdListaInteres","innerHTML","Acceso Denegado");
		return $objResponse;
	}

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$startRow = $pageNum * $maxRows;

	$queryInteres = "SELECT * FROM fi_interes";
	$rsInteres = mysql_query($queryInteres);
	if (!$rsInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$queryLimitMoneda = $queryInteres." LIMIT ".$maxRows." OFFSET ".$startRow.";";
	$rsLimitInteres = mysql_query($queryLimitMoneda);
	if (!$rsLimitInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if ($totalRows == NULL) {
		$rsMoneda = mysql_query($queryInteres) or die(mysql_error());
		$totalRows = mysql_num_rows($rsInteres);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";

	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">
				<td width='30%'>Descripcion</td>
            	<td width='31%'>Interes</td>
            	<td width='31%'>Estatus</td>
				<td colspan='2'></td>
               </tr>";


	while ($rowInteres = mysql_fetch_assoc($rsLimitInteres)) {
		$clase = ($clase == "trResaltar5") ? $clase = "trResaltar4" : $clase = "trResaltar5";

		($rowInteres['estatus_interes'] == 1)? $estatus = "Activa" : $estatus = "Inactiva";

		$htmlTb .= "<tr class=\"".$clase."\">";
		$htmlTb .= "<td>".  utf8_encode($rowInteres['descripcion_interes'])."</td>";
		$htmlTb .= "<td>".  utf8_encode($rowInteres['valor_interes'])."</td>";
		$htmlTb .= "<td>".$estatus."</td>";
		$htmlTb .= "<td>";
		$htmlTb .= "<a class=\"modalImg\" id=\"editarInteres\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'Editar Interes','Editar',".$rowInteres['id_interes'].");\">";
		$htmlTb .= "<img src='../img/iconos/ico_edit.png' title='Editar Interes'\>";
		$htmlTb .= "</a>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td><img src='../img/iconos/ico_quitar.gif' onclick=\"if (confirm('¿Desea eliminar el Interes?') == true) {
			xajax_eliminarInteres(".$rowInteres['id_interes'].");
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarInteres(%s,'%s','%s','%s',%s);\">%s</a>",
				0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum > 0) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarInteres(%s,'%s','%s','%s',%s);\">%s</a>",
				max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"100\">";

	$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarInteres(%s,'%s','%s','%s',%s)\">",
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarInteres(%s,'%s','%s','%s',%s);\">%s</a>",
				min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum < $totalPages) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarInteres(%s,'%s','%s','%s',%s);\">%s</a>",
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

	$objResponse->assign("tdListaInteres","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}


//REGISTROS XAJAX

$xajax->register(XAJAX_FUNCTION,"cargarInteres");
$xajax->register(XAJAX_FUNCTION,"eliminarInteres");
$xajax->register(XAJAX_FUNCTION,"guardarInteres");
$xajax->register(XAJAX_FUNCTION,"listarInteres");





?>