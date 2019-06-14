<?php
function formSubSeccion() {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_subseccion_list","insertar")) {
		$objResponse->script("xajax_cargaLstSeccion()");
		
		$objResponse->script("
			document.forms['frmSubSeccion'].reset();
			
			$('txtSubSeccion').className = 'inputInicial';
			$('lstSeccion').className = 'inputInicial';
			$('txtDescripcion').className = 'inputInicial';");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Sub-Sección");
		$objResponse->script("		
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		");
	}
	
	return $objResponse;
}

function cargarSubSeccion($idSubSeccion) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_subseccion_list","editar")) {
		$querySubSeccion = sprintf("SELECT * FROM iv_subsecciones WHERE id_subseccion = %s", $idSubSeccion);
		$rsSubSeccion = mysql_query($querySubSeccion);
		if (!$rsSubSeccion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowSubSeccion = mysql_fetch_assoc($rsSubSeccion);
		
		$objResponse->assign("hddIdSubSeccion","value",$idSubSeccion);
		$objResponse->assign("txtSubSeccion","value",$rowSubSeccion['descripcion']);
		$objResponse->script(sprintf("xajax_cargaLstSeccion(%s);",$rowSubSeccion['id_seccion']));
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Sub-Sección");
		$objResponse->script("		
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		");
	}
	
	return $objResponse;
}

function guardarSubSeccion($valForm) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdSubSeccion'] > 0) {
		if (xvalidaAcceso($objResponse,"iv_subseccion_list","editar")) {
			$updateSQL = sprintf("UPDATE iv_subsecciones SET
				id_seccion = %s,
				descripcion = %s
			WHERE id_subseccion = %s",
				valTpDato($valForm['lstSeccion'], "int"),
				valTpDato($valForm['txtSubSeccion'], "text"),
				valTpDato($valForm['hddIdSubSeccion'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"iv_subseccion_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO iv_subsecciones (id_seccion, descripcion) VALUE (%s, %s)",
				valTpDato($valForm['lstSeccion'], "int"),
				valTpDato($valForm['txtSubSeccion'], "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Sub-Seccion Guardada con Éxito");
	
	$objResponse->script("
		window.location.reload();");
		
	return $objResponse;
}

function eliminarSubSeccion($valForm) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_subseccion_list","eliminar")) {
		if (isset($valForm['cbxSubSec'])) {
			mysql_query("START TRANSACTION;");
			
			foreach ($valForm['cbxSubSec'] as $indiceItm=>$valorItm) {
				$deleteSQL = sprintf("DELETE FROM iv_subsecciones WHERE id_subseccion = %s",
					valTpDato($valorItm, "int"));
				
				$Result1 = mysql_query($deleteSQL);
				if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			}
			
			mysql_query("COMMIT;");
		
			$objResponse->script("
				window.location.reload();");
		}
	}
		
	return $objResponse;
}

function cargaLstSeccion($selId = "") {
	$objResponse = new xajaxResponse();
	
	$querySeccion = sprintf("SELECT * FROM iv_secciones ORDER BY descripcion");
	$rsSeccion = mysql_query($querySeccion);
	if (!$rsSeccion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstSeccion\" name=\"lstSeccion\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($rowSeccion = mysql_fetch_assoc($rsSeccion)) {
		$selected = ($selId == $rowSeccion['id_seccion']) ? "selected=\"selected\"" : "";
		
		$html .= "<option value=\"".$rowSeccion['id_seccion']."\" ".$selected.">".htmlentities($rowSeccion['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSeccion","innerHTML",$html);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"formSubSeccion");
$xajax->register(XAJAX_FUNCTION,"cargarSubSeccion");
$xajax->register(XAJAX_FUNCTION,"guardarSubSeccion");
$xajax->register(XAJAX_FUNCTION,"eliminarSubSeccion");
$xajax->register(XAJAX_FUNCTION,"cargaLstSeccion");
?>