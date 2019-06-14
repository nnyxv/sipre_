<?php
function asignarEmpresa($valForm){
	$objResponse = new xajaxResponse();
	
	// SI NO TIENE SUCURSAL
	if ($valForm['lstSucursal'] == "") {
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales
		WHERE id_empresa_reg = %s;",
			valTpDato($valForm['lstEmpresa'], "int"));
	} else {
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_empresas_sucursales
		WHERE id_empresa_reg = %s;",
			valTpDato($valForm['lstSucursal'], "int"));
	}
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$_SESSION['idEmpresaUsuarioSysGts'] = $rowEmpresa['id_empresa_reg'];
	$_SESSION['session_empresa'] = $rowEmpresa['id_empresa_reg'];
	$_SESSION['logoEmpresaSysGts'] = $rowEmpresa['logo_familia'];
	
	$objResponse->alert(("Empresa asignada con Éxito."));
	
	$objResponse->script("location.reload(true);");
	//$objResponse->loadCommands(cargarSesion($_SESSION['idUsuarioSysGts'], $_SESSION['idEmpresaUsuarioSysGts']));
	
	return $objResponse;
}

function cargaLstEmpresa($idUsuario, $selId){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
	ORDER BY nombre_empresa ASC;",
		valTpDato($idUsuario, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = sprintf("<select id=\"lstEmpresa\" name=\"lstEmpresa\" class=\"inputHabilitado\" style=\"width:200px\">",
		$idUsuario);
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empresa_reg']) ? "selected='selected'" : "";
	
		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEmpresaSucursal($idUsuario, $selId){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT DISTINCT
		id_empresa,
		nombre_empresa
	FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
	ORDER BY nombre_empresa ASC;",
		valTpDato($idUsuario, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = sprintf("<select id=\"lstEmpresa\" name=\"lstEmpresa\" class=\"inputHabilitado\" onChange=\"xajax_cargaLstSucursal('%s',this.value);\" style=\"width:200px\">",
		$idUsuario);
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empresa']) ? "selected='selected'" : "";
	
		$html .= "<option ".$selected." value=\"".$row['id_empresa']."\">".utf8_encode($row['nombre_empresa'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstSucursal($idUsuario, $idEmpresa, $selId = ""){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
		AND id_empresa = %s
	ORDER BY nombre_empresa ASC;",
		valTpDato($idUsuario, "int"),
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = sprintf("<select id=\"lstSucursal\" name=\"lstSucursal\" class=\"inputHabilitado\" style=\"width:200px\">",
		$idUsuario);
		$html .="<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empresa_reg']) ? "selected='selected'" : "";
	
		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa_suc'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstSucursal","innerHTML",$html);
	
	return $objResponse;
}

function cargarSesion($idUsuario, $idEmpresa = ""){
	$objResponse = new xajaxResponse();
	
	// BUSCA LA EMPRESA PREDETERMINADA
	$queryEmpresaPredet = sprintf("SELECT * FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
		AND predeterminada = 1;",
		valTpDato($idUsuario, "int"));
	$rsEmpresaPredet = mysql_query($queryEmpresaPredet);
	if (!$rsEmpresaPredet) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsEmpresaPredet = mysql_num_rows($rsEmpresaPredet);
	$rowEmpresaPredet = mysql_fetch_assoc($rsEmpresaPredet);
	
	// SI NO TIENE EMPRESA PEDETERMINADA
	if ($totalRowsEmpresaPredet == 0) {
		$objResponse->alert("No tiene Empresa Predeterminada Asignada");
	} else {
		$idEmpresa = ($idEmpresa != "") ? $idEmpresa : $rowEmpresaPredet['id_empresa_reg'];
		
		// BUSCA LOS DATOS DE LA EMPRESA SELECCIONADA
		$queryEmpresa = sprintf("SELECT * FROM vw_iv_usuario_empresa
		WHERE id_empresa_reg = %s;",
			valTpDato($idEmpresa, "int"));
		$rsEmpresa = mysql_query($queryEmpresa);
		if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsEmpresa = mysql_num_rows($rsEmpresa);
		$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
		
		// VERIFICA SI LA EMPRESA PEDETERMINADA TIENE SUCURSAL
		$queryEmpresaSuc = sprintf("SELECT * FROM vw_iv_empresas_sucursales
		WHERE id_empresa_reg = %s
			AND id_empresa_suc > 0;",
			valTpDato($rowEmpresaPredet['id_empresa_reg'], "int"));
		$rsEmpresaSuc = mysql_query($queryEmpresaSuc);
		if (!$rsEmpresaSuc) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsEmpresaSuc = mysql_num_rows($rsEmpresaSuc);
		$rowEmpresaSuc = mysql_fetch_assoc($rsEmpresaSuc);
		
		// SI NO TIENE SUCURSAL
		if ($totalRowsEmpresaSuc == 0) {
			$objResponse->script("byId('trlstSucursal').style.display = 'none';");
			
			$objResponse->loadCommands(cargaLstEmpresa($idUsuario, $rowEmpresa['id_empresa_reg']));
		} else {
			$objResponse->script("byId('trlstSucursal').style.display = '';");
			
			$objResponse->loadCommands(cargaLstEmpresaSucursal($idUsuario, $rowEmpresa['id_empresa']));
			$objResponse->loadCommands(cargaLstSucursal($idUsuario, $rowEmpresa['id_empresa'], $rowEmpresa['id_empresa_reg']));
		}
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresaSucursal");
$xajax->register(XAJAX_FUNCTION,"cargaLstSucursal");
$xajax->register(XAJAX_FUNCTION,"cargarSesion");
?>