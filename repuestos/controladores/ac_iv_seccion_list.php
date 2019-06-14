<?php
function formSeccion() {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_seccion_list","insertar")) {
		$objResponse->script("
			document.forms['frmSeccion'].reset();
			
			$('txtSeccion').className = 'inputInicial';
			$('txtAbreviatura').className = 'inputInicial';
			$('lstTipoSeccion').className = 'inputInicial';");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Sección");
		$objResponse->script("
			if ($('divFlotante').style.display == 'none') {
				$('divFlotante').style.display='';
				centrarDiv($('divFlotante'));
			}
		");
	}
	
	return $objResponse;
}

function cargarSeccion($idSeccion) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_seccion_list","editar")) {
		$querySeccion = sprintf("SELECT * FROM iv_secciones WHERE id_seccion = %s", $idSeccion);
		$rsSeccion = mysql_query($querySeccion);
		if (!$rsSeccion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowSeccion = mysql_fetch_assoc($rsSeccion);
		
		$objResponse->assign("hddIdSeccion","value",$idSeccion);
		$objResponse->assign("txtSeccion","value",$rowSeccion['descripcion']);
		$objResponse->assign("txtAbreviatura","value",$rowSeccion['corta']);
		$objResponse->call("selectedOption","lstTipoSeccion",$rowSeccion['tipo_seccion']);
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Sección");
		$objResponse->script("		
			if ($('divFlotante').style.display == 'none') {	
				$('divFlotante').style.display='';
				centrarDiv($('divFlotante'));
			}
		");
	}
	
	return $objResponse;
}

function guardarSeccion($valForm) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdSeccion'] > 0) {
		if (xvalidaAcceso($objResponse,"iv_seccion_list","editar")) {
			$updateSQL = sprintf("UPDATE iv_secciones SET
				descripcion = %s,
				corta = %s,
				tipo_seccion = %s
			WHERE id_seccion = %s",
				valTpDato($valForm['txtSeccion'], "text"),
				valTpDato($valForm['txtAbreviatura'], "text"),
				valTpDato($valForm['lstTipoSeccion'], "int"),
				valTpDato($valForm['hddIdSeccion'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"iv_seccion_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO iv_secciones (descripcion, corta, tipo_seccion) VALUE (%s, %s, %s)",
				valTpDato($valForm['txtSeccion'], "text"),
				valTpDato($valForm['txtAbreviatura'], "text"),
				valTpDato($valForm['lstTipoSeccion'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$idSeccion = mysql_insert_id();
			mysql_query("SET NAMES 'latin1';");
			
			$insertSQL = sprintf("INSERT INTO iv_subsecciones (id_seccion, descripcion) VALUE (%s, %s)",
				valTpDato($idSeccion, "int"),
				valTpDato("NA", "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Sección Guardada con Éxito");
	
	$objResponse->script("
		window.location.reload();");
		
	return $objResponse;
}

function eliminarSeccion($valForm) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_seccion_list","eliminar")) {
		if (isset($valForm['cbxSec'])) {
			mysql_query("START TRANSACTION;");
			
			foreach ($valForm['cbxSec'] as $indiceItm=>$valorItm) {
				$deleteSQL = sprintf("DELETE FROM iv_secciones WHERE id_seccion = %s",
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

$xajax->register(XAJAX_FUNCTION,"formSeccion");
$xajax->register(XAJAX_FUNCTION,"cargarSeccion");
$xajax->register(XAJAX_FUNCTION,"guardarSeccion");
$xajax->register(XAJAX_FUNCTION,"eliminarSeccion");
?>