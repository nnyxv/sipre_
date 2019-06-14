<?php
function formMarca() {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_marca_list","insertar")) {
		$objResponse->script("
			document.forms['frmMarca'].reset();
			
			$('txtMarca').className = 'inputInicial';
			$('txtAbreviatura').className = 'inputInicial';
			$('lstTipoMarca').className = 'inputInicial';");
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Agregar Marca");
		$objResponse->script("		
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		");
	}
	
	return $objResponse;
}

function cargarMarca($idMarca) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_marca_list","editar")) {
		$queryMarca = sprintf("SELECT * FROM iv_marcas WHERE id_marca = %s", $idMarca);
		$rsMarca = mysql_query($queryMarca);
		if (!$rsMarca) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowMarca = mysql_fetch_assoc($rsMarca);
		
		$objResponse->assign("hddIdMarca","value",$idMarca);
		$objResponse->assign("txtMarca","value",$rowMarca['marca']);
		$objResponse->assign("txtDescripcion","value",$rowMarca['descripcion']);
		
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Marca");
		$objResponse->script("		
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		");
	}
	
	return $objResponse;
}

function guardarMarca($valForm) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	if ($valForm['hddIdMarca'] > 0) {
		if (xvalidaAcceso($objResponse,"iv_marca_list","editar")) {
			$updateSQL = sprintf("UPDATE iv_marcas SET
				marca = %s,
				descripcion = %s
			WHERE id_marca = %s",
				valTpDato($valForm['txtMarca'], "text"),
				valTpDato($valForm['txtDescripcion'], "text"),
				valTpDato($valForm['hddIdMarca'], "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	} else {
		if (xvalidaAcceso($objResponse,"iv_marca_list","insertar")) {
			$insertSQL = sprintf("INSERT INTO iv_marcas (marca, descripcion) VALUE (%s, %s)",
				valTpDato($valForm['txtMarca'], "text"),
				valTpDato($valForm['txtDescripcion'], "text"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
		} else {
			return $objResponse;
		}
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Marca Guardada con Éxito");
	
	$objResponse->script("
		window.location.reload();");
		
	return $objResponse;
}

function eliminarMarca($valForm) {
	$objResponse = new xajaxResponse();
	
	if (xvalidaAcceso($objResponse,"iv_marca_list","eliminar")) {
		if (isset($valForm['cbxMarc'])) {
			mysql_query("START TRANSACTION;");
			
			foreach ($valForm['cbxMarc'] as $indiceItm=>$valorItm) {
				$deleteSQL = sprintf("DELETE FROM iv_marcas WHERE id_marca = %s",
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

$xajax->register(XAJAX_FUNCTION,"formMarca");
$xajax->register(XAJAX_FUNCTION,"cargarMarca");
$xajax->register(XAJAX_FUNCTION,"guardarMarca");
$xajax->register(XAJAX_FUNCTION,"eliminarMarca");
?>