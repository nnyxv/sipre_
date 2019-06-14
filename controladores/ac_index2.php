<?php


function asignarEmpresa($frmEmpresa) {
	$objResponse = new xajaxResponse();
	
	$queryEmpresa = sprintf("SELECT
		vw_iv_emp_suc.id_empresa_reg,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa,
		vw_iv_emp_suc.logo_familia
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE id_empresa_reg = %s;",
		valTpDato($frmEmpresa['lstEmpresa'], "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$_SESSION['idEmpresaUsuarioSysGts'] = $rowEmpresa['id_empresa_reg'];
	$_SESSION['nombreEmpresaUsuarioSysGts'] = $rowEmpresa['nombre_empresa'];
	$_SESSION['logoEmpresaSysGts'] = $rowEmpresa['logo_familia'];
	$_SESSION['session_empresa'] = $rowEmpresa['id_empresa_reg'];
	
	$objResponse->alert(("Empresa asignada con Éxito."));
	
	$objResponse->script("location.reload(true);");
	//$objResponse->loadCommands(cargarSesion($_SESSION['idUsuarioSysGts'], $_SESSION['idEmpresaUsuarioSysGts']));
	
	return $objResponse;
}

function cargaLstEmpresa($idUsuario, $selId) {
	$objResponse = new xajaxResponse();
	
	// EMPRESAS PRINCIPALES
	$queryUsuarioSuc = sprintf("SELECT DISTINCT
		id_empresa_reg,
		nombre_empresa
	FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
		AND id_empresa_padre_suc IS NULL
	ORDER BY nombre_empresa_suc ASC",
		valTpDato($idUsuario, "int"));
	$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
	if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
		$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
	
		$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".utf8_encode($rowUsuarioSuc['id_empresa_reg'].".- ".$rowUsuarioSuc['nombre_empresa'])."</option>";	
	}
	
	// EMPRESAS CON SUCURSALES
	$query = sprintf("SELECT DISTINCT
		id_empresa,
		nombre_empresa
	FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
		AND id_empresa_padre_suc IS NOT NULL
	ORDER BY nombre_empresa",
		valTpDato($idUsuario, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$htmlOption .= "<optgroup label=\"".$row['nombre_empresa']."\">";
		
		$queryUsuarioSuc = sprintf("SELECT DISTINCT
			id_empresa_reg,
			nombre_empresa_suc,
			sucursal
		FROM vw_iv_usuario_empresa
		WHERE id_usuario = %s
			AND id_empresa_padre_suc = %s
		ORDER BY nombre_empresa_suc ASC",
			valTpDato($idUsuario, "int"),
			valTpDato($row['id_empresa'], "int"));
		$rsUsuarioSuc = mysql_query($queryUsuarioSuc);
		if (!$rsUsuarioSuc) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		while ($rowUsuarioSuc = mysql_fetch_assoc($rsUsuarioSuc)) {
			$selected = ($selId == $rowUsuarioSuc['id_empresa_reg']) ? "selected=\"selected\"" : "";
		
			$htmlOption .= "<option ".$selected." value=\"".$rowUsuarioSuc['id_empresa_reg']."\">".utf8_encode($rowUsuarioSuc['id_empresa_reg'].".- ".$rowUsuarioSuc['nombre_empresa_suc'])."</option>";	
		}
	
		$htmlOption .= "</optgroup>";
	}
	
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" class=\"inputHabilitado\" ".$accion." style=\"width:200px\">";
		$html .= $htmlOption;
	$html .= "</select>";
	
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function cargarSesion() {
	$objResponse = new xajaxResponse();
	
	$idUsuario = $_SESSION['idUsuarioSysGts'];
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	// BUSCA LA EMPRESA PREDETERMINADA
	$queryEmpresaPredet = sprintf("SELECT * FROM vw_iv_usuario_empresa
	WHERE id_usuario = %s
		AND predeterminada = 1;",
		valTpDato($idUsuario, "int"));
	$rsEmpresaPredet = mysql_query($queryEmpresaPredet);
	if (!$rsEmpresaPredet) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$totalRowsEmpresaPredet = mysql_num_rows($rsEmpresaPredet);
	$rowEmpresaPredet = mysql_fetch_assoc($rsEmpresaPredet);
	
	// SI NO TIENE EMPRESA PEDETERMINADA
	if ($totalRowsEmpresaPredet == 0) {
		$objResponse->alert("No tiene Empresa Predeterminada Asignada");
	} else {
		$idEmpresa = ($idEmpresa > 0) ?$idEmpresa : $rowEmpresaPredet['id_empresa_reg'];
		
		$objResponse->loadCommands(cargaLstEmpresa($idUsuario, $idEmpresa));
	}
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"cargarSesion");
?>