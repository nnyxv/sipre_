<?php
function cargaLstDias($selId = "", $nombreObjeto = ""){
	$objResponse = new xajaxResponse();
	
	$array[] = "1";		$array[] = "5";		$array[] = "10";		$array[] = "15";
	$array[] = "30";	$array[] = "45";	$array[] = "60";		$array[] = "75";
	$array[] = "90";	$array[] = "91";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:150px\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargarDias(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM gruposestadocuenta");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	//GRUPO I
	$objResponse->loadCommands(cargaLstDias($row['desde1'],"lstDesde1"));
	$objResponse->loadCommands(cargaLstDias($row['hasta1'],"lstHasta1"));
	
	//GRUPO II
	$objResponse->loadCommands(cargaLstDias($row['desde2'],"lstDesde2"));
	$objResponse->loadCommands(cargaLstDias($row['hasta2'],"lstHasta2"));
	
	//GRUPO III
	$objResponse->loadCommands(cargaLstDias($row['desde3'],"lstDesde3"));
	$objResponse->loadCommands(cargaLstDias($row['hasta3'],"lstHasta3"));
	
	//GRUPO IV
	$objResponse->loadCommands(cargaLstDias($row['masDe'],"lstMasDe"));
	
	//ULTIMA MODIFICACION
	$objResponse->assign("txtFechaFinal","value",date(spanDateFormat, strtotime($row['fechaUltimoCambio'])));
	
	return $objResponse;
}

function guardarDias($frmDias){
	$objResponse = new xajaxResponse();
									
	if (xvalidaAcceso($objResponse,"cc_grupo_dias_antiguedad_saldo","editar")) {
	
		$updateSQL = sprintf("UPDATE gruposestadocuenta SET
			desde1 = %s,
			hasta1 = %s,
			desde2 = %s,
			hasta2 = %s,
			desde3 = %s,
			hasta3 = %s,
			masDe = %s,
			fechaUltimoCambio = %s
		WHERE idGrupoEstado = %s",
			valTpDato($frmDias['lstDesde1'], "int"),
			valTpDato($frmDias['lstHasta1'], "int"),
			valTpDato($frmDias['lstDesde2'], "int"),
			valTpDato($frmDias['lstHasta2'], "int"),
			valTpDato($frmDias['lstDesde3'], "int"),
			valTpDato($frmDias['lstHasta3'], "int"),
			valTpDato($frmDias['lstMasDe'], "int"),
			valTpDato(date("Y-m-d", strtotime($frmDias['txtFechaInicial'])), "date"),
			valTpDato(1, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		$objResponse->alert('Modificacion realizada exitosamente.');
	}
	return $objResponse;
}
//
$xajax->register(XAJAX_FUNCTION,"cargaLstDias");
$xajax->register(XAJAX_FUNCTION,"cargarDias");
$xajax->register(XAJAX_FUNCTION,"guardarDias");
?>