<?php 
//FUNCIONES XAJAX

function cargarPlazo($idPlazo){
	$objResponse = new xajaxResponse();

	$queryPlazo = "SELECT * FROM fi_plazos WHERE id_plazo = '".$idPlazo."'";
	$rsPlazo = mysql_query($queryPlazo);
	if (!$rsPlazo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$rowPlazo = mysql_fetch_assoc($rsPlazo);
	
	if ($rowPlazo['estatus_duracion'] == 0){
		$cero = "selected=\"selected\"";
		$uno = "";
	}
	else{
		$cero = "";
		$uno = "selected=\"selected\"";
	}
	if ($rowPlazo['estatus_frecuencia'] == 0){
		$cero0 = "selected=\"selected\"";
		$uno0 = "";
	}
	else{
		$cero0 = "";
		$uno0 = "selected=\"selected\"";
	}

	if ($rowPlazo['estatus_interes'] == 0){
		$cero1 = "selected=\"selected\"";
		$uno1 = "";
	}
	else{
		$cero1 = "";
		$uno1 = "selected=\"selected\"";
	}
	
	$cadenaSelect = "<select id=\"selEstatusDuracion\" name=\"selEstatusDuracion\">
                     	<option value=\"-1\">[ Selected ]</option>
                     	<option value=\"1\" ".$uno.">Activa</option>
                     	<option value=\"0\" ".$cero.">Inactiva</option>
                     </select>";

	$cadenaSelect2 = "<select id=\"selEstatusFrecuencia\" name=\"selEstatusFrecuencia\">
                     	<option value=\"-1\">[ Selected ]</option>
                     	<option value=\"1\" ".$uno0.">Activa</option>
                     	<option value=\"0\" ".$cero0.">Inactiva</option>
                     </select>";

	$cadenaSelect3 = "<select id=\"selEstatusInteres\" name=\"selEstatusInteres\">
                     	<option value=\"-1\">[ Selected ]</option>
                     	<option value=\"1\" ".$uno1.">Activa</option>
                     	<option value=\"0\" ".$cero1.">Inactiva</option>
                     </select>";
	//IMPLEMENTANDO ATRIBUTOS

	$objResponse->assign("txtNombrePlazo","value",  utf8_encode($rowPlazo['nombre_plazo']));
	$objResponse->assign("txtPlazo","value",  utf8_encode($rowPlazo['cuotas_anuales']));
	$objResponse->assign("txtSemanas","value",  utf8_encode($rowPlazo['semanas']));
	$objResponse->assign("tdselEstatusDuracion","innerHTML",$cadenaSelect);
	$objResponse->assign("tdselEstatusFrecuencia","innerHTML",$cadenaSelect2);
	$objResponse->assign("tdselEstatusInteres","innerHTML",$cadenaSelect3);

	$objResponse->script("byId('txtNombrePlazo').className = 'inputHabilitado';
						  byId('txtPlazo').className = 'inputHabilitado';
						  byId('txtSemanas').className = 'inputHabilitado';
						  byId('selEstatusDuracion').className = 'inputHabilitado';
						  byId('selEstatusInteres').className = 'inputHabilitado';
					      byId('selEstatusFrecuencia').className = 'inputHabilitado';");
	
	return $objResponse;
}

function eliminarPlazo($idPlazo){
	
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,"fi_plazos","eliminar")){
		return $objResponse;
	}

	$queryEliminar = sprintf("DELETE FROM fi_plazos WHERE id_plazo= %s ;",
							$idPlazo);
	
	$rsPlazo = mysql_query($queryEliminar);
	if (!$rsPlazo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($rsPlazo == true){
		$objResponse->script("xajax_listarPlazos(0,'','','')");
		$objResponse->alert("Plazo eliminado exitosamente.");
	}

	return $objResponse;
}

function guardarPlazo($frmPlazo){
	$objResponse = new xajaxResponse();
	
	if ($frmPlazo['hddIdPlazo'] == 0){
		$cadena = "insertado";
		
		$queryPlazo = sprintf("INSERT INTO fi_plazos (nombre_plazo, cuotas_anuales, semanas, estatus_duracion, estatus_frecuencia, estatus_interes) VALUES ('%s',%s,%s,%s,%s,%s);",
				$frmPlazo['txtNombrePlazo'],
				$frmPlazo['txtPlazo'],
				$frmPlazo['txtSemanas'],
				$frmPlazo['selEstatusDuracion'],
				$frmPlazo['selEstatusFrecuencia'],
				$frmPlazo['selEstatusInteres']);
		
	} else {
		$cadena = "modificado";
	
		$queryPlazo = sprintf("UPDATE fi_plazos SET 
								 	 nombre_plazo = '%s', 
								 	 cuotas_anuales = %s,
								 	 semanas = %s,
									 estatus_duracion = %s,
									 estatus_frecuencia = %s,
									 estatus_interes = %s
							  	WHERE id_plazo = %s;",
						$frmPlazo['txtNombrePlazo'],
						$frmPlazo['txtPlazo'],
						$frmPlazo['txtSemanas'],
						$frmPlazo['selEstatusDuracion'],
						$frmPlazo['selEstatusFrecuencia'],
						$frmPlazo['selEstatusInteres'],
						$frmPlazo['hddIdPlazo']);
	}
	
	$rs = mysql_query($queryPlazo);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$objResponse->script("xajax_listarPlazos(0,'','','');");
	$objResponse->script("byId('btnCancelarLista').click();");
	
	$objResponse->alert("Plazo ".$cadena." exitosamente");
	
	return $objResponse;
}

function listarPlazos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();

	
	if (!xvalidaAcceso($objResponse,"fi_plazos")){
		$objResponse->assign("tdListaPlazo","innerHTML","Acceso Denegado");
		return $objResponse;
	}

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	$startRow = $pageNum * $maxRows;

	$queryPlazo = "SELECT * FROM fi_plazos";
	$rsPlazo = mysql_query($queryPlazo);
	if (!$rsPlazo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);

	$queryLimitMoneda = $queryPlazo." LIMIT ".$maxRows." OFFSET ".$startRow.";";
	$rsLimitPlazo = mysql_query($queryLimitMoneda);
	if (!$rsLimitPlazo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	if ($totalRows == NULL) {
		$rsMoneda = mysql_query($queryPlazo) or die(mysql_error());
		$totalRows = mysql_num_rows($rsPlazo);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";

	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">
				<td width='29%'>Nombre</td>
            	<td width='15%'>Cuotas Anuales</td>
            	<td width='15%'>Semanas</td>
				<td width='11%'>Estado Duracion</td>
				<td width='11%'>Estado Frecuencia</td>
				<td width='11%'>Estado Plazo Interes</td>
				<td colspan='2'></td>
               </tr>";


	while ($rowPlazo = mysql_fetch_assoc($rsLimitPlazo)) {
		$clase = ($clase == "trResaltar5") ? $clase = "trResaltar4" : $clase = "trResaltar5";

		($rowPlazo['estatus_duracion'] == 1)? $estatusDuracion = "Activa" : $estatusDuracion = "Inactiva";
		($rowPlazo['estatus_frecuencia'] == 1)? $estatusFrecuencia = "Activa" : $estatusFrecuencia = "Inactiva";
		($rowPlazo['estatus_interes'] == 1)? $estatusInteres = "Activa" : $estatusInteres = "Inactiva";

		$htmlTb .= "<tr class=\"".$clase."\">";
		$htmlTb .= "<td>".  utf8_encode($rowPlazo['nombre_plazo'])."</td>";
		$htmlTb .= "<td>".  utf8_encode($rowPlazo['cuotas_anuales'])."</td>";
		$htmlTb .= "<td>".  utf8_encode($rowPlazo['semanas'])."</td>";
		$htmlTb .= "<td>".$estatusDuracion."</td>";
		$htmlTb .= "<td>".$estatusFrecuencia."</td>";
		$htmlTb .= "<td>".$estatusInteres."</td>";
		$htmlTb .= "<td>";
		$htmlTb .= "<a class=\"modalImg\" id=\"editarPlazo\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'Editar Plazo','Editar',".$rowPlazo['id_plazo'].");\">";
		$htmlTb .= "<img src='../img/iconos/ico_edit.png' title='Editar Plazo'\>";
		$htmlTb .= "</a>";
		$htmlTb .= "</td>";
		$htmlTb .= "<td><img src='../img/iconos/ico_quitar.gif' onclick=\"if (confirm('¿Desea eliminar el Plazo?') == true) {
			xajax_eliminarPlazo(".$rowPlazo['id_plazo'].");
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPlazos(%s,'%s','%s','%s',%s);\">%s</a>",
				0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum > 0) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPlazos(%s,'%s','%s','%s',%s);\">%s</a>",
				max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"100\">";

	$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarPlazos(%s,'%s','%s','%s',%s)\">",
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
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPlazos(%s,'%s','%s','%s',%s);\">%s</a>",
				min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
	}
	$htmlTf .= "</td>";
	$htmlTf .= "<td width=\"25\">";
	if ($pageNum < $totalPages) {
		$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarPlazos(%s,'%s','%s','%s',%s);\">%s</a>",
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

	$objResponse->assign("tdListaPlazo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	return $objResponse;
}


//REGISTROS XAJAX

$xajax->register(XAJAX_FUNCTION,"cargarPlazo");
$xajax->register(XAJAX_FUNCTION,"eliminarPlazo");
$xajax->register(XAJAX_FUNCTION,"guardarPlazo");
$xajax->register(XAJAX_FUNCTION,"listarPlazos");





?>