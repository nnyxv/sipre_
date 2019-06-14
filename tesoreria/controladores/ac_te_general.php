<?php

/* ARCHIVO GENERAL */

/* FUNCIONES XAJAX */
function cargarDiasVencidos(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM gruposestadocuenta");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_assoc($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"corriente\"/> Cta. Corriente</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde1\"/> De ".$row['desde1']." a ".$row['hasta1']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde2\"/> De ".$row['desde2']." a ".$row['hasta2']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"desde3\"/> De ".$row['desde3']." a ".$row['hasta3']."</label></td>";
			$html .= "<td nowrap=\"nowrap\" width=\"20%\"><label><input type=\"checkbox\" id=\"cbxDiasVencidos\" name=\"cbxDiasVencidos[]\" checked=\"checked\" value=\"masDe\"/> Mas de ".$row['masDe']."</label></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
		
	$objResponse->assign("tdDiasVencidos","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstModulo($selId = "", $accion = "onchange=\"byId('btnBuscar').click();\"" , $idEtiqueta = "lstModulo", $idObjetivo = "tdlstModulo") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile: ".__FILE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$idEtiqueta."\" name=\"".$idEtiqueta."\" ".$accion." class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_modulo'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_modulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($idObjetivo,"innerHTML",$html);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargaLstModulo");
$xajax->register(XAJAX_FUNCTION,"cargarDiasVencidos");


/* FUNCIONES COMUNES */
function configChequeManual($idEmpresa = 1){//EN TESORERIA TODO ES POR LA EMPRESA 1
	$query = sprintf("SELECT valor 
	FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 408 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if(!$rs){ die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile:".__FILE__); }
	$row = mysql_fetch_assoc($rs);
	
	return $row['valor'];
}

function configPais(){	
	// VERIFICA VALORES DE CONFIGURACION (Formato Cheque Tesoreria)
	$queryConfig403 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 403 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
	$rsConfig403 = mysql_query($queryConfig403);
	if (!$rsConfig403) { die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__."\nFile:".__FILE__); }
	$totalRowsConfig403 = mysql_num_rows($rsConfig403);
	$rowConfig403 = mysql_fetch_assoc($rsConfig403);
	
	return $rowConfig403['valor'];
}

?>