<?php


function cargarUsuario($idUsuario){
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("hddIdUsuario","value",$idUsuario);
	$objResponse->assign("hddIdUsuarioClaveEspecial","value",$idUsuario);
	
	// BUSCA LAS CLAVES ESPECIALES ASIGNADAS
	$query = sprintf("SELECT
		clave_mod.id_modulo,
		clave_mod.descripcion,
		clave_usu.contrasena
	FROM pg_claves_usuarios clave_usu
		INNER JOIN pg_claves_modulos clave_mod ON (clave_usu.id_clave_modulo = clave_mod.id_clave_modulo)
	WHERE clave_usu.id_usuario = %s
	
	UNION
	
	SELECT 
		clave_mod.id_modulo,
		clave_mod.descripcion,
		clave_serv.clave
	FROM sa_claves clave_serv
		INNER JOIN pg_claves_modulos clave_mod ON (clave_serv.modulo = clave_mod.modulo)
	WHERE clave_serv.id_empleado IN (SELECT usu.id_empleado FROM pg_usuario usu
									WHERE usu.id_usuario = %s)
	
	ORDER BY 2;",
		valTpDato($idUsuario, "int"),
		valTpDato($idUsuario, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	
	$htmlTblIni = "<table style=\"font-size:12px;\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rs)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "<img src=\"img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
			case 1 : $imgPedidoModulo = "<img src=\"img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
			case 2 : $imgPedidoModulo = "<img src=\"img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
			case 3 : $imgPedidoModulo = "<img src=\"img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
			case 4 : $imgPedidoModulo = "<img src=\"img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgPedidoModulo."</td>";
			$htmlTb .= "<td width=\"100%\">".utf8_encode($row['descripcion'])."</td>";
			//$htmlTb .= "<td>".utf8_encode($row['contrasena'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTblFin .= "</table>";
	
	$objResponse->assign("divListaClavesEspeciales","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	($totalRows > 0) ? "" : $objResponse->script("byId('frmClaveEspecial').style.display = 'none'"); 
	
	return $objResponse;
}

function guardarUsuario($frmUsuario, $frmClaveEspecial){
	$objResponse = new xajaxResponse();
	
	$idUsuario = ($frmUsuario['hddIdUsuario'] > 0) ? $frmUsuario['hddIdUsuario'] : $frmClaveEspecial['hddIdUsuarioClaveEspecial'];
	$redirect = "index.php";
	
	$idElementoMenu = getmysql("SELECT id_elemento_menu FROM pg_elemento_menu WHERE modulo LIKE 'pg_cambio_clave';");
	
	$claveActual = md5(excape($frmUsuario['txtPasswordActual']));
	$claveNueva = md5(excape($frmUsuario['txtPasswordNuevo']));
	$sqlu = "SELECT clave FROM pg_usuario WHERE id_usuario = ".$idUsuario.";";
	$claveAnterior = getmysql($sqlu);
	
	if (strlen($frmUsuario['txtPasswordNuevo']) >= 4 && strlen($frmUsuario['txtPasswordConfirmar']) >= 4) {
		if (excape($frmUsuario['txtPasswordNuevo']) != excape($frmUsuario['txtPasswordConfirmar'])) {
			guardarAuditoria($idElementoMenu, $idUsuario, $_SESSION['session_empresa'], 0, 'editar');
			
			$_SESSION['session_error'] = intval($_SESSION['session_error']) + 1;
			
			$msjGuardarUsuario = ("Las contraseña no coinciden");
			
			if (intval($_SESSION['session_error']) >= 3) {
				$_SESSION['session_error'] = "bloq_cambio";
				$redirect = $redirect;
			} else {
				$redirect = "pg_cambio_clave.php";
			}
		} else if ($claveAnterior != $claveActual) {
			guardarAuditoria($idElementoMenu, $idUsuario, $_SESSION['session_empresa'], 0, 'editar');
			
			$_SESSION['session_error'] = intval($_SESSION['session_error']) + 1;
			
			$msjGuardarUsuario = ("Contraseña incorrecta");
			
			if (intval($_SESSION['session_error']) >= 3) {
				$_SESSION['session_error'] = "bloq_cambio";
				$redirect = $redirect;
			} else {
				$redirect = "pg_cambio_clave.php";
			}
		} else {
			$sql = sprintf("UPDATE pg_usuario SET
				clave = %s,
				ultima_fecha_cambio_clave = NOW()
			WHERE id_usuario = %s;",
				valTpDato($claveNueva, "text"),
				valTpDato($idUsuario, "int"));
			$r = mysql_query($sql);
			if ($r) {
				guardarAuditoria($idElementoMenu, $idUsuario, $_SESSION['session_empresa'], 1, 'editar');
				
				$msjGuardarUsuario = ("Se ha cambiado la contraseña de acceso perfectamente");
				$redirect = $redirect;
			} else {
				guardarAuditoria($idElementoMenu, $idUsuario, $_SESSION['session_empresa'], 0, 'editar');
				
				unset($_SESSION['session_error']);
				
				$msjGuardarUsuario = ("No se ha podido cambiar su contraseña, consulte con soporte al usuario");
				$redirect = $redirect;
			}
		}
	}
	
	if (strlen($frmClaveEspecial['txtPasswordClaveEspecial']) >= 4) {
		// BUSCA LOS DATOS DEL USUARIO PARA SABER SUS DATOS PERSONALES
		$queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios WHERE id_usuario = %s",
			valTpDato($idUsuario, "int"));
		$rsUsuario = mysql_query($queryUsuario);
		if (!$rsUsuario) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$rowUsuario = mysql_fetch_assoc($rsUsuario);
		
		$updateSQL = sprintf("UPDATE pg_empleado SET
			contrasena_especial = %s
		WHERE id_empleado = %s;",
			valTpDato($frmClaveEspecial['txtPasswordClaveEspecial'], "text"),
			valTpDato($rowUsuario['id_empleado'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		$updateSQL = sprintf("UPDATE pg_claves_usuarios SET 
			contrasena = %s
		WHERE id_usuario = %s;",
			valTpDato($frmClaveEspecial['txtPasswordClaveEspecial'], "text"),
			valTpDato($idUsuario, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		$updateSQL = sprintf("UPDATE sa_claves SET 
			clave = MD5(%s)
		WHERE id_empleado = %s;",
			valTpDato($frmClaveEspecial['txtPasswordClaveEspecial'], "text"),
			valTpDato($rowUsuario['id_empleado'], "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		mysql_query("SET NAMES 'latin1';");
		
		$msjGuardarUsuario = ("Se ha cambiado la contraseña de claves especiales perfectamente");
		$redirect = "index2.php";
	}
	
	$objResponse->alert($msjGuardarUsuario);
	$objResponse->script("window.location.href = '".$redirect."';");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargarUsuario");
$xajax->register(XAJAX_FUNCTION,"guardarUsuario");
?>