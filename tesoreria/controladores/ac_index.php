<?php
function cambiarEmpresa($valForm) {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales WHERE id_empresa_reg = %s",
		$valForm['lstEmpresa']);
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error());
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$_SESSION['idEmpresaUsuarioSysGts'] = $rowEmpresa['id_empresa_reg'];
	$_SESSION['logoEmpresaSysGts'] = $rowEmpresa['logo_familia'];
	
	$objResponse->alert(utf8_encode("Empresa asignada con exito."));
	
	$objResponse->script("location.reload(true);");
	
	return $objResponse;
}

function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error());
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\">";
	while ($row = mysql_fetch_assoc($rs)) {
		$nombreSucursal = "";
		if ($row['id_empresa_padre_suc'] > 0)
			$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
		
		$selected = "";
		if ($selId == $row['id_empresa_reg'])
			$selected = "selected='selected'";
	
		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".htmlentities($row['nombre_empresa'].$nombreSucursal)."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cambiarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
?>