<?php 
//FUNCIONES XAJAX

function cargarAdicional($idAdicional){
	$objResponse = new xajaxResponse();

	$queryAdicional = "SELECT * FROM fi_adicionales WHERE id_adicional = '".$idAdicional."'";
	$rsAdicional = mysql_query($queryAdicional);
	if (!$rsAdicional) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$rowAdicional = mysql_fetch_assoc($rsAdicional);
	
	if ($rowAdicional['estatus_adicional'] == 0){
		$cero = "selected=\"selected\"";
		$uno = "";
	}
	else{
		$cero = "";
		$uno = "selected=\"selected\"";
	}
	
	if ($rowAdicional['tipo_adicional'] == 0){
		$ceroTipo = "selected=\"selected\"";
		$unoTipo = "";
	}
	else{
		$ceroTipo = "";
		$unoTipo = "selected=\"selected\"";
	}
	
	$cadenaSelect = "<select id=\"selEstatusAdicional\" name=\"selEstatusAdicional\" style=\"width: 100%\">
                     	<option value=\"-1\">[ Selected ]</option>
                     	<option value=\"1\" ".$uno.">Activa</option>
                     	<option value=\"0\" ".$cero.">Inactiva</option>
                     </select>";
	
	$cadenaSelect2 = "<select id=\"selTipoAdicional\" name=\"selTipoAdicional\" style=\"width: 100%\">
                     	<option value=\"-1\">[ Selected ]</option>
                     	<option value=\"Cuota\" ".$unoTipo.">Cuota</option>
                     	<option value=\"Total\" ".$ceroTipo.">Total</option>
                     </select>";


	//IMPLEMENTANDO ATRIBUTOS

	$objResponse->assign("txtNombreAdicional","value",  utf8_encode($rowAdicional['nombre_adicional']));
	$objResponse->assign("txtMontoAdicional","value",  utf8_encode($rowAdicional['monto_adicional']));
	$objResponse->assign("tdselEstatusAdicional","innerHTML",$cadenaSelect);
	$objResponse->assign("tdselTipoAdicional","innerHTML",$cadenaSelect2);

	$objResponse->script("byId('txtNombreAdicional').className = 'inputHabilitado';
						  byId('selTipoAdicional').className = 'inputHabilitado';
						  byId('txtMontoAdicional').className = 'inputHabilitado';
					      byId('selEstatusAdicional').className = 'inputHabilitado';");
	
	return $objResponse;
}

function eliminarAdicional($idAdicional){
	
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"fi_adicionales","eliminar")){
		return $objResponse;
	}

	$queryEliminar = sprintf("DELETE FROM fi_adicionales WHERE id_adicional= %s ;",
							$idAdicional);
	
	$rsAdicional = mysql_query($queryEliminar);
	if (!$rsAdicional) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($rsAdicional == true){
		$objResponse->script("xajax_listarAdicional(0,'','','')");
		$objResponse->alert("Adicional eliminado exitosamente.");
	}

	return $objResponse;
}

function guardarAdicional($frmAdicional){
	$objResponse = new xajaxResponse();
	
	if ($frmAdicional['hddIdAdicional'] == 0){
		$cadena = "insertado";
		
		$queryAdicional = sprintf("INSERT INTO fi_adicionales (nombre_adicional, tipo_adicional, monto_adicional, estatus_Adicional) VALUES ('%s','%s',%s,%s);",
				$frmAdicional['txtNombreAdicional'],
				$frmAdicional['selTipoAdicional'],
				$frmAdicional['txtMontoAdicional'],
				$frmAdicional['selEstatusAdicional']);
		
	} else {
		$cadena = "modificado";
	
		$queryAdicional = sprintf("UPDATE fi_adicionales SET 
								 	 nombre_adicional = '%s', 
								 	 tipo_adicional = '%s',
								 	 monto_adicional = %s,
									 estatus_Adicional = %s
							  	WHERE id_adicional = %s;",
						$frmAdicional['txtNombreAdicional'],
						$frmAdicional['selTipoAdicional'],
						$frmAdicional['txtMontoAdicional'],
						$frmAdicional['selEstatusAdicional'],
						$frmAdicional['hddIdAdicional']);
	}
	
	$rs = mysql_query($queryAdicional);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$objResponse->script("xajax_listarAdicional(0,'','','');");
	$objResponse->script("byId('btnCancelarLista').click();");
	
	$objResponse->alert("Adicional ".$cadena." exitosamente");
	
	return $objResponse;
}

function listarAdicional($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	
	if (!xvalidaAcceso($objResponse,"fi_adicionales")){
		$objResponse->assign("tdListaAdicional","innerHTML","Acceso Denegado");
		return $objResponse;
	}

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$startRow = $pageNum * $maxRows;

	$queryAdicional = "SELECT * FROM fi_adicionales";
	$rsAdicional = mysql_query($queryAdicional);
	if (!$rsAdicional) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$queryLimitMoneda = $queryAdicional." LIMIT ".$maxRows." OFFSET ".$startRow.";";
	$rsLimitAdicional = mysql_query($queryLimitMoneda);
	if (!$rsLimitAdicional) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if ($totalRows == NULL) {
		$rsMoneda = mysql_query($queryAdicional) or die(mysql_error());
		$totalRows = mysql_num_rows($rsAdicional);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";

	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">
				<td width='29%'>Nombre</td>
            	<td width='15%'>Tipo</td>
            	<td width='26%'>Monto</td>
				<td width='22%'>Estado Adicional</td>
				<td colspan='2'></td>
               </tr>";


	while ($rowAdicional = mysql_fetch_assoc($rsLimitAdicional)) {
		$clase = ($clase == "trResaltar5") ? $clase = "trResaltar4" : $clase = "trResaltar5";

		($rowAdicional['estatus_adicional'] == 1)? $estatusAdicional = "Activa" : $estatusAdicional = "Inactiva";
		($rowAdicional['estatus_adicional'] == 1)? $imgEstatus = "<img src='../img/iconos/ico_verde.gif'>" : $imgEstatus = "<img src='../img/iconos/ico_rojo.gif'>";

		$htmlTb .= "<tr class=\"".$clase."\">";
		$htmlTb .= "<td>".  utf8_encode($rowAdicional['nombre_adicional'])."</td>";
		$htmlTb .= "<td>".  utf8_encode($rowAdicional['tipo_adicional'])."</td>";
		$htmlTb .= "<td>".  utf8_encode($rowAdicional['monto_adicional'])."</td>";
		$htmlTb .= "<td>".$imgEstatus." ".$estatusAdicional."</td>";
		$htmlTb .= "<td>";
		$htmlTb .= "<a class=\"modalImg\" id=\"editarAdicional\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'Editar Adicional','Editar',".$rowAdicional['id_adicional'].");\">";
		$htmlTb .= "<img src='../img/iconos/ico_edit.png' title='Editar Adicional'\>";
		$htmlTb .= "</a>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td><img src='../img/iconos/ico_quitar.gif' onclick=\"if (confirm('¿Desea eliminar el Adicional?') == true) {
			xajax_eliminarAdicional(".$rowAdicional['id_adicional'].");
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
				0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum > 0) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
				max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"100\">";

	$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarAdicional(%s,'%s','%s','%s',%s)\">",
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
				min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum < $totalPages) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarAdicional(%s,'%s','%s','%s',%s);\">%s</a>",
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

	$objResponse->assign("tdListaAdicional","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}


//REGISTROS XAJAX

$xajax->register(XAJAX_FUNCTION,"cargarAdicional");
$xajax->register(XAJAX_FUNCTION,"eliminarAdicional");
$xajax->register(XAJAX_FUNCTION,"guardarAdicional");
$xajax->register(XAJAX_FUNCTION,"listarAdicional");





?>