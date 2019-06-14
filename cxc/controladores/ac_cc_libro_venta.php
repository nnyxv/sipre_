<?php


function buscarEmpresa($frmBuscarEmpresa){
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscarEmpresa['hddObjDestino'],
		$frmBuscarEmpresa['hddNomVentana'],
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listadoEmpresasUsuario(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function cargarModulos(){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_array($rs)) {
		$contFila++;
		
		$html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\" height=\"22\">" : "";
			
			$html .= "<td width=\"50%\"><label><input type=\"checkbox\" id=\"cbxModulo\" name=\"cbxModulo[]\" checked=\"checked\" value=\"".$row['id_modulo']."\"/> ".$row['descripcionModulo']."</label></td>";
				
		$html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
	}
	$html .= "</table>";
	
	$objResponse->assign("tdModulos","innerHTML",$html);
	
	return $objResponse;
}

function validaEnvia($frmBuscar){
	$objResponse = new xajaxResponse();
	
	if ($frmBuscar['txtFechaDesde'] == "" || $frmBuscar['txtFechaHasta'] == "") {
		$objResponse->alert("Por Favor Coloque el Rango de Fechas");
	} else if (!($frmBuscar['lstFormatoNumero'] > 0)) {
		$objResponse->script("byId('lstFormatoNumero').className = 'inputErrado';");
		$objResponse->alert("Por Favor seleccione un formato de número");
	} else if (strtotime($frmBuscar['txtFechaDesde']) <= strtotime($frmBuscar['txtFechaHasta'])) {
		if (count($frmBuscar['cbxModulo']) > 0){
			$objResponse->script(sprintf("window.open('cc_libro_venta_imp.php?idEmpresa=%s&f1=%s&f2=%s&idModulo=%s&lstFormatoNumero=%s&lstFormatoTotalDia=%s','_self');",
				$frmBuscar['txtIdEmpresa'],
				$frmBuscar['txtFechaDesde'],
				$frmBuscar['txtFechaHasta'],
				(is_array($frmBuscar['cbxModulo']) ? implode(",",$frmBuscar['cbxModulo']) : $frmBuscar['cbxModulo']),
				$frmBuscar['lstFormatoNumero'],
				$frmBuscar['lstFormatoTotalDia']));
		} else {
			$objResponse->alert("Debe Seleccionar minimo un modulo");
		}
	} else if (strtotime($frmBuscar['txtFechaDesde']) >= strtotime($frmBuscar['txtFechaHasta'])) {
		$objResponse->alert("Coloque una Fecha Final Mayor a la Inicial");
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarModulos");
$xajax->register(XAJAX_FUNCTION,"validaEnvia");
?>