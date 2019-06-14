<?php
function buscarBenficiario($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoBeneficiario(0,'','','%s');",
		$valForm['txtBusq']));
	
	return $objResponse;
}

function listadoBeneficiario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_beneficiarios")){
		$objResponse->assign("tdListadoBeneficiario","innerHTML","Acceso Denegado");
		return $objResponse;
	}
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	$sqlBusq = sprintf(" WHERE nombre_beneficiario LIKE %s ",
		valTpDato("%".$valCadBusq[0]."%", "text"));
	
	$query = sprintf("SELECT * FROM te_beneficiarios").$sqlBusq;
        
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
        
	$rsLimit = mysql_query($queryLimit) or die(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	/*$htmlTh .= "<tr class=\"tituloColumna\">
                <td width=\"12%\">ID</td>
                <td width=\"16%\">Nombre Beneficiario</td>
                <td width=\"16%\">CI/RIF</td>
                <td width=\"10%\">Telf</td>
                <td width=\"10%\">Mail</td>
				<td width=\"3%\"></td>
				<td width=\"3%\"></td>
            </tr>";*/
        
        $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoBeneficiario", "8%", $pageNum, "id_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, "ID");
		$htmlTh .= ordenarCampo("xajax_listadoBeneficiario", "26%", $pageNum, "nombre_beneficiario", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Beneficiario");
		$htmlTh .= ordenarCampo("xajax_listadoBeneficiario", "26%", $pageNum, "lci_rif", $campOrd, $tpOrd, $valBusq, $maxRows, "CI/RIF");
		$htmlTh .= ordenarCampo("xajax_listadoBeneficiario", "22%", $pageNum, "telfs", $campOrd, $tpOrd, $valBusq, $maxRows, "Telf");
		$htmlTh .= ordenarCampo("xajax_listadoBeneficiario", "22%", $pageNum, "email", $campOrd, $tpOrd, $valBusq, $maxRows, "Mail");
		$htmlTh .= '<td width=\"6%\"></td>
			    <td width=\"6%\"></td>';
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"right\">".$row['id_beneficiario']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['nombre_beneficiario']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['lci_rif'].'-'.$row['ci_rif_beneficiario']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telfs']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['email']."</td>";
			$htmlTb .= "<td align=\"center\" ><img class=\"puntero\" onclick=\"xajax_mostrarBeneficiarios(".$row['id_beneficiario'].")\" src=\"../img/iconos/ico_view.png\" /></td>";
			$htmlTb .= "<td align=\"center\"><img class=\"puntero\" onclick=\"xajax_mostrarDatosActualizar(".$row['id_beneficiario'].")\" src=\"../img/iconos/ico_edit.png\" /></td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoBeneficiario(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoBeneficiario(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoBeneficiario(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoBeneficiario(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig2_tesoreria.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoBeneficiario(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"7\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
        $objResponse->assign("tdListadoBeneficiario","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function nuevoBeneficiario(){
	$objResponse = new xajaxResponse();


	if (!xvalidaAcceso($objResponse,"te_beneficiarios","insertar")){
		return $objResponse;
	}
	
	$objResponse->script("
		document.forms['frmBeneficiario'].reset();");
	
	$objResponse->script("$('divFlotante').style.display = '';
		              $('divFlotanteTitulo').innerHTML = 'Nuevo Beneficiario';
			      centrarDiv($('divFlotante'))");
	
	$html .="<td align=\"right\"><hr>";
	$html .= "<input type=\"button\" value=\"Guardar\" onclick=\"validarFormInsertar();\">";
	$html .= "<input type=\"button\" value=\"Cancelar\" onclick=\"$('divFlotante').style.display='none';\">";
	$html .="</td>";

        $objResponse -> assign("trBeneficiariosBotones","innerHTML",$html);
	
	return $objResponse;
}

function insertarDatos($valForm){
	$objResponse = new xajaxResponse();
	
	$queryBeneficiario = sprintf ("INSERT INTO te_beneficiarios( nombre_beneficiario, lci_rif, ci_rif_beneficiario, estatus, direccion, telfs, ciudad, estado, email, idretencion)VALUE('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
			$valForm['txtNombreBeneficiario'],
			$valForm['listLetraCiRif'],
			$valForm['txtCiRif'],
			'1',
			$valForm['textDireccion'],
			$valForm['txtTelefono'],
			$valForm['txtCiudad'],
			$valForm['txtEstado'],
			$valForm['txtEmailBanco'],
			$valForm['lstRetencion']);
			$consultaBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());

	$objResponse->alert("Los Datos se han Guardado Correctamente");
	$objResponse->script("$('divFlotante').style.display = 'none'");
	$objResponse->script("xajax_listadoBeneficiario(0,'','','');");

	return $objResponse;
}

function cargaLstRetencion($selId = "") {
	$objResponse = new xajaxResponse();
	
	$queryRetencion = sprintf("SELECT * FROM te_retenciones ORDER BY id");
	$rsRetencion = mysql_query($queryRetencion) or die(mysql_error());
	$html = "<select id=\"lstRetencion\" name=\"lstRetencion\">";
		$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($rowRetencion = mysql_fetch_assoc($rsRetencion)) {
		$seleccion = "";
		if ($selId == $rowRetencion['id'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$rowRetencion['id']."\" ".$seleccion.">".htmlentities($rowRetencion['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstRetencion","innerHTML",$html);
	
	return $objResponse;
}

function mostrarBeneficiarios($idBeneficiario){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
		document.forms['frmBeneficiario'].reset();
		$('txtNombreBeneficiario').className = 'inputInicial';
		$('listLetraCiRif').className = 'inputInicial';
		$('txtCiRif').className = 'inputInicial';
		$('txtCiudad').className = 'inputInicial';
		$('txtEstado').className = 'inputInicial';
		$('textDireccion').className = 'inputInicial';
		$('txtTelefono').className = 'inputInicial';
		$('txtEmailBanco').className = 'inputInicial';
		$('lstRetencion').className = 'inputInicial';
		$('txtNombreBeneficiario').readOnly = true;
		$('txtCiRif').readOnly = true;
		$('txtCiudad').readOnly = true;
		$('txtEstado').readOnly = true;
		$('textDireccion').readOnly = true;
		$('txtTelefono').readOnly = true;
		$('txtEmailBanco').readOnly = true;");
		
	
	$objResponse->script("$('divFlotante').style.display = '';
						  $('divFlotanteTitulo').innerHTML = 'Consultar Beneficiario';
						  centrarDiv($('divFlotante'))");

	$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$idBeneficiario);
	$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
	$rowBeneficiario =mysql_fetch_array($rsBeneficiario);
	
	$objResponse->assign("txtNombreBeneficiario","value",$rowBeneficiario['nombre_beneficiario']);
	$objResponse->assign("listLetraCiRif","value",$rowBeneficiario['lci_rif']);
	$objResponse->assign("txtCiRif","value",$rowBeneficiario['ci_rif_beneficiario']);
	$objResponse->assign("txtCiudad","value",$rowBeneficiario['ciudad']);
	$objResponse->assign("txtEstado","value",$rowBeneficiario['estado']);
	$objResponse->assign("textDireccion","value",$rowBeneficiario['direccion']);
	$objResponse->assign("txtTelefono","value",$rowBeneficiario['telfs']);
	$objResponse->assign("txtEmailBanco","value",$rowBeneficiario['email']);
	$objResponse->assign("lstRetencion","value",$rowBeneficiario['idretencion']);
	$objResponse->assign("hddIdBeneficiario","value",$idBeneficiario);

	return $objResponse;
}

function mostrarDatosActualizar($idBeneficiario){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"te_beneficiarios","editar")){
		return $objResponse;
	}

	
	$objResponse->script("
		document.forms['frmBeneficiario'].reset();
		$('txtNombreBeneficiario').className = 'inputInicial';
		$('listLetraCiRif').className = 'inputInicial';
		$('txtCiRif').className = 'inputInicial';
		$('txtCiudad').className = 'inputInicial';
		$('txtEstado').className = 'inputInicial';
		$('textDireccion').className = 'inputInicial';
		$('txtTelefono').className = 'inputInicial';
		$('txtEmailBanco').className = 'inputInicial';
		$('lstRetencion').className = 'inputInicial';");
		
	
	$objResponse->script("$('divFlotante').style.display = '';
						  $('divFlotanteTitulo').innerHTML = 'Actualizar Beneficiario';
						  centrarDiv($('divFlotante'))");

	$queryBeneficiario = sprintf("SELECT * FROM te_beneficiarios WHERE id_beneficiario = '%s'",$idBeneficiario);
	$rsBeneficiario = mysql_query($queryBeneficiario) or die(mysql_error());
	$rowBeneficiario =mysql_fetch_array($rsBeneficiario);
	
	$objResponse->assign("txtNombreBeneficiario","value",$rowBeneficiario['nombre_beneficiario']);
	$objResponse->assign("listLetraCiRif","value",$rowBeneficiario['lci_rif']);
	$objResponse->assign("txtCiRif","value",$rowBeneficiario['ci_rif_beneficiario']);
	$objResponse->assign("txtCiudad","value",$rowBeneficiario['ciudad']);
	$objResponse->assign("txtEstado","value",$rowBeneficiario['estado']);
	$objResponse->assign("textDireccion","value",$rowBeneficiario['direccion']);
	$objResponse->assign("txtTelefono","value",$rowBeneficiario['telfs']);
	$objResponse->assign("txtEmailBanco","value",$rowBeneficiario['email']);
	$objResponse->assign("lstRetencion","value",$rowBeneficiario['idretencion']);
	$objResponse->assign("hddIdBeneficiario","value",$idBeneficiario);
	
	$html ="<td align=\"right\"><hr>";		
	$html .= "<input type=\"button\" value=\"Guardar\" onclick=\"validarFormActualizar();\">";
	$html .= "<input type=\"button\" value=\"Cancelar\" onclick=\"$('divFlotante').style.display='none';\">";
	$html .="</td>";
	
	$objResponse->assign("trBeneficiariosBotones","innerHTML",$html);

	return $objResponse;
}

function actualizarDatos($valForm){
	$objResponse = new xajaxResponse();

	$queryBeneficiarioActualiza = sprintf("UPDATE te_beneficiarios SET nombre_beneficiario = '%s', direccion = '%s', telfs = '%s', ciudad = '%s', estado = '%s', email = '%s', idretencion = '%s' WHERE id_beneficiario = '%s'",$valForm['txtNombreBeneficiario'],$valForm['textDireccion'],$valForm['txtTelefono'],$valForm['txtCiudad'],$valForm['txtEstado'],$valForm['txtEmailBanco'],$valForm['lstRetencion'],$valForm['hddIdBeneficiario']);
	$rsBeneficiarioActualiza = mysql_query($queryBeneficiarioActualiza) or die(mysql_error());
	
	$objResponse->alert("Los Datos han sido Actualizado con exito");
	$objResponse->script("$('divFlotante').style.display = 'none'");
	$objResponse->script("xajax_listadoBeneficiario(0,'','','');");

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarBenficiario");
$xajax->register(XAJAX_FUNCTION,"listadoBeneficiario");
$xajax->register(XAJAX_FUNCTION,"nuevoBeneficiario");
$xajax->register(XAJAX_FUNCTION,"insertarDatos");
$xajax->register(XAJAX_FUNCTION,"cargaLstRetencion");
$xajax->register(XAJAX_FUNCTION,"mostrarBeneficiarios");
$xajax->register(XAJAX_FUNCTION,"mostrarDatosActualizar");
$xajax->register(XAJAX_FUNCTION,"actualizarDatos");



?>