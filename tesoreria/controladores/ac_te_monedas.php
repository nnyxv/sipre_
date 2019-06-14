<?php
function cargarMoneda($idMoneda){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_monedas","editar")){
		return $objResponse;
	}
	
	$queryMoneda = "SELECT * FROM pg_monedas WHERE idmoneda = '".$idMoneda."'";
	$rsMoneda = mysql_query($queryMoneda) or die(mysql_error());
	$rowMoneda = mysql_fetch_array($rsMoneda);
	
	if ($rowMoneda['estatus'] == 0){
		$cero = "selected=\"selected\"";
		$uno = "";
	}
	else{
		$cero = "";
		$uno = "selected=\"selected\"";
	}
	
	$cadenaSelect = "<select id=\"selEstatusMoneda\" name=\"selEstatusMoneda\">
                     	<option value=\"1\" ".$uno.">Activa</option>
                     	<option value=\"0\" ".$cero.">Inactiva</option>
                     </select>";
	
	$objResponse->assign("hddIdMoneda","value",$rowMoneda['idmoneda']);
	$objResponse->assign("txtDescripcion","value",  utf8_encode($rowMoneda['descripcion']));
	$objResponse->assign("txtAbreviacion","value",  utf8_encode($rowMoneda['abreviacion']));
	$objResponse->assign("tdSelEstatusMoneda","innerHTML",$cadenaSelect);
	
	$objResponse->script("$('divFlotante').style.display = '';
						  centrarDiv($('divFlotante'));");
	
	return $objResponse;
}

function eliminarMoneda($idMoneda){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_monedas","eliminar")){
		return $objResponse;
	}
	
	$queryEliminar = "DELETE FROM pg_monedas WHERE idmoneda = '".$idMoneda."'";
	if ($rs = mysql_query($queryEliminar) == true){
		$objResponse->script("xajax_listarMonedas(0,'','','')");
		$objResponse->alert("Moneda eliminada exitosamente.");
	}
		
	return $objResponse;
}

function guardarMoneda($formMoneda){
	$objResponse = new xajaxResponse();
	
	if ($formMoneda['hddIdMoneda'] == 0){
		$cadena = "insertada";
		
		$queryMoneda = "INSERT INTO pg_monedas (idmoneda, descripcion, abreviacion, estatus) VALUES ('', '".$formMoneda['txtDescripcion']."', '".$formMoneda['txtAbreviacion']."', '".$formMoneda['selEstatusMoneda']."');";
	
	}
	else{
		$cadena = "modificada";
	
		$queryMoneda = "UPDATE pg_monedas SET 
			descripcion = '".$formMoneda['txtDescripcion']."',
			abreviacion = '".$formMoneda['txtAbreviacion']."',
			estatus = '".$formMoneda['selEstatusMoneda']."'
			WHERE idmoneda = '".$formMoneda['hddIdMoneda']."';";
	}
	
	$rsMoneda = mysql_query($queryMoneda) or die(mysql_error());
	
	$objResponse->script("xajax_listarMonedas(0,'','','');
						  $('divFlotante').style.display = 'none';");
	
	$objResponse->alert("Moneda ".$cadena." exitosamente");
	
	return $objResponse;
}

function levantarDivFlotante(){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_monedas","insertar")){
		return $objResponse;
	}
	$objResponse->script("document.forms['frmMoneda'].reset();
						$('divFlotante').style.display = '';
						$('divFlotanteTitulo').innerHTML = 'Nuevo';
						centrarDiv($('divFlotante'));
						$('bttGuardar').style.display = '';
						$('hddIdMoneda').value = 0;");
	
	return $objResponse;
}

function listarMonedas($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_monedas")){
		$objResponse->assign("tdListaMonedas","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$startRow = $pageNum * $maxRows;
	
	$queryMonedas = "SELECT * FROM pg_monedas";
	$rsMonedas = mysql_query($queryMonedas) or die(mysql_error());
	
	$queryLimitMoneda = $queryMonedas." LIMIT ".$maxRows." OFFSET ".$startRow.";";
	$rsLimitMoneda = mysql_query($queryLimitMoneda) or die(mysql_error());
			
	if ($totalRows == NULL) {
		$rsMoneda = mysql_query($queryMonedas) or die(mysql_error());
		$totalRows = mysql_num_rows($rsMonedas);
	}

	$totalPages = ceil($totalRows/$maxRows)-1;
		
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">
				<td width='35%'>Descripcion</td>
            	<td width='35%'>Abreviacion </td>
				<td width='18%'>Estado</td>
				<td width='4%'></td>
				<td width='4%'></td>
            </tr>";
        
	
	while ($rowMoneda = mysql_fetch_assoc($rsLimitMoneda)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar5" : $clase = "trResaltar4";
		
		if ($rowMoneda['estatus'] == 1){
			$estatus = "Activa";
		}
		else{
			$estatus = "Inactiva";
		}
		
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>".  utf8_encode($rowMoneda['descripcion'])."</td>";
			$htmlTb .= "<td>".  utf8_encode($rowMoneda['abreviacion'])."</td>";
			$htmlTb .= "<td>".$estatus."</td>";
			$htmlTb .= "<td><img src='../img/iconos/ico_edit.png' onclick='xajax_cargarMoneda(".$rowMoneda['idmoneda'].")' /></td>";
			$htmlTb .= "<td><img src='../img/iconos/ico_quitar.gif' onclick=\"if (confirm('Desea eliminar la moneda?') == true) {
			xajax_eliminarMoneda(".$rowMoneda['idmoneda'].");
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarMonedas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarMonedas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listarMonedas(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarMonedas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listarMonedas(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
        $objResponse->assign("tdListaMonedas","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);	

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargarMoneda");
$xajax->register(XAJAX_FUNCTION,"eliminarMoneda");
$xajax->register(XAJAX_FUNCTION,"guardarMoneda");
$xajax->register(XAJAX_FUNCTION,"levantarDivFlotante");
$xajax->register(XAJAX_FUNCTION,"listarMonedas");
?>