<?php 
//FUNCIONES XAJAX

function cargarTipoInteres($idTipoInteres){
	$objResponse = new xajaxResponse();

	$queryTipoInteres = "SELECT * FROM fi_tipo_interes WHERE id_tipo_interes = $idTipoInteres";
	$rsTipoInteres = mysql_query($queryTipoInteres);
	if (!$rsTipoInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$rowTipoInteres = mysql_fetch_assoc($rsTipoInteres);
	
	if ($rowTipoInteres['estatus_tipo_interes'] == 0){
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

	$objResponse->assign("txtNombreTipoInteres","value",  utf8_encode($rowTipoInteres['descripcion_tipo_interes']));
	$objResponse->assign("txtTipoInteres","value",  utf8_encode($rowTipoInteres['valor_tipo_interes']));
	$objResponse->assign("tdselEstatus","innerHTML",$cadenaSelect);
	$objResponse->assign("hddIdTipoInteres","value",$idTipoInteres);

	$objResponse->script("byId('txtNombreTipoInteres').className = 'inputHabilitado';
						  byId('txtTipoInteres').className = 'inputHabilitado';
					      byId('selEstatus').className = 'inputHabilitado';");
	
	return $objResponse;
}

function eliminarTipoInteres($idTipoInteres){
	
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"fi_tipo_interes","eliminar")){
		return $objResponse;
	}

	$queryEliminar = sprintf("DELETE FROM fi_tipo_interes WHERE id_tipo_interes = %s ;",
							$idTipoInteres);
	
	$rsTipoInteres = mysql_query($queryEliminar);
	if (!$rsTipoInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($rsTipoInteres == true){
		$objResponse->script("xajax_listarTipoInteres(0,'','','')");
		$objResponse->alert("TipoInteres eliminado exitosamente.");
	}

	return $objResponse;
}

function guardarTipoInteres($frmTipoInteres){
	$objResponse = new xajaxResponse();
	
	if ($frmTipoInteres['hddIdTipoInteres'] == 0){
		$cadena = "insertado";
		
		$queryTipoInteres = sprintf("INSERT INTO fi_tipo_interes (descripcion_tipo_interes, valor_tipo_interes, estatus_tipo_interes) VALUES ('%s',%s,'%s');",
				$frmTipoInteres['txtNombreTipoInteres'],
				$frmTipoInteres['txtTipoInteres'],
				$frmTipoInteres['selEstatus']);
		
	} else {
		$cadena = "modificado";
	
		$queryTipoInteres = sprintf("UPDATE fi_tipo_interes SET 
								 	 descripcion_tipo_interes = '%s', 
								 	 valor_tipo_interes = %s,
									 estatus_tipo_interes = %s
							  	WHERE id_tipo_interes = %s;",
				$frmTipoInteres['txtNombreTipoInteres'],
				$frmTipoInteres['txtTipoInteres'],
				$frmTipoInteres['selEstatus'],
				$frmTipoInteres['hddIdTipoInteres']);
	}
	
	$rs = mysql_query($queryTipoInteres);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$objResponse->script("xajax_listarTipoInteres(0,'','','');");
	$objResponse->script("byId('btnCancelarLista').click();");
	
	$objResponse->alert("Tipo de Interes ".$cadena." exitosamente");
	
	return $objResponse;
}

function listarTipoInteres($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	
	if (!xvalidaAcceso($objResponses,"fi_tipo_interes")){
		$objResponse->assign("tdListaTipoInteres","innerHTML","Acceso Denegado");
		return $objResponse;
	}

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$startRow = $pageNum * $maxRows;

	$queryTipoInteres = "SELECT * FROM fi_tipo_interes";
	$rsTipoInteres = mysql_query($queryTipoInteres);
	if (!$rsTipoInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$queryLimitMoneda = $queryTipoInteres." LIMIT ".$maxRows." OFFSET ".$startRow.";";
	$rsLimitTipoInteres = mysql_query($queryLimitMoneda);
	if (!$rsLimitTipoInteres) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if ($totalRows == NULL) {
		$rsMoneda = mysql_query($queryTipoInteres) or die(mysql_error());
		$totalRows = mysql_num_rows($rsTipoInteres);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";

	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">
				<td width='30%'>Descripcion</td>
            	<td width='31%'>TipoInteres</td>
            	<td width='31%'>Estatus</td>
				<td colspan='2'></td>
               </tr>";


	while ($rowTipoInteres = mysql_fetch_assoc($rsLimitTipoInteres)) {
		$clase = ($clase == "trResaltar5") ? $clase = "trResaltar4" : $clase = "trResaltar5";

		($rowTipoInteres['estatus_tipo_interes'] == 1)? $estatus = "Activa" : $estatus = "Inactiva";

		$htmlTb .= "<tr class=\"".$clase."\">";
		$htmlTb .= "<td>".  utf8_encode($rowTipoInteres['descripcion_tipo_interes'])."</td>";
		$htmlTb .= "<td>".  utf8_encode($rowTipoInteres['valor_tipo_interes'])."</td>";
		$htmlTb .= "<td>".$estatus."</td>";
		$htmlTb .= "<td>";
		$htmlTb .= "<a class=\"modalImg\" id=\"editarTipoInteres\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'Editar TipoInteres','Editar',".$rowTipoInteres['id_tipo_interes'].");\">";
		$htmlTb .= "<img src='../img/iconos/ico_edit.png' title='Editar TipoInteres'\>";
		$htmlTb .= "</a>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td><img src='../img/iconos/ico_quitar.gif' onclick=\"if (confirm('¿Desea eliminar el TipoInteres?') == true) {
			xajax_eliminarTipoInteres(".$rowTipoInteres['id_tipo_interes'].");
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarTipoInteres(%s,'%s','%s','%s',%s);\">%s</a>",
				0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum > 0) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarTipoInteres(%s,'%s','%s','%s',%s);\">%s</a>",
				max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"100\">";

	$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarTipoInteres(%s,'%s','%s','%s',%s)\">",
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarTipoInteres(%s,'%s','%s','%s',%s);\">%s</a>",
				min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum < $totalPages) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarTipoInteres(%s,'%s','%s','%s',%s);\">%s</a>",
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

	$objResponse->assign("tdListaTipoInteres","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}


//REGISTROS XAJAX

$xajax->register(XAJAX_FUNCTION,"cargarTipoInteres");
$xajax->register(XAJAX_FUNCTION,"eliminarTipoInteres");
$xajax->register(XAJAX_FUNCTION,"guardarTipoInteres");
$xajax->register(XAJAX_FUNCTION,"listarTipoInteres");





?>