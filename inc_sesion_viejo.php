<?php
/* EJEMPLO */
//validaAcceso("iv_seccion_list","",true,NULL,NULL,NULL,true,"index.php");

define("insertar","insertar");
define("editar","editar");
define("eliminar","eliminar");
define("desautorizar","desautorizar");
define("desincorporar","desautorizar");
$cancelAuditory = false;
@session_start();

function validaModulo($nomModulo, $accionModulo = "", $tabla = false, $registro = false) {
	include("connections/conex.php");
	
	global $redirect, $cancelAuditory; // para definir la pagina a redireccionar

	$ruta = explode("↓",str_replace(array("/","\\"),"↓",getcwd()));
	$ruta = array_reverse($ruta);
	$raizPpal = false;
	foreach ($ruta as $indice => $valor) {
		$valor2 = explode("_",$valor);
		if ($valor2[0] != "sipre" && $raizPpal == false) {
			$raiz .= "../";
			break;
		} else if ($valor2[0] == "sipre") {
			$raizPpal = true;
		}
	}

	comprobarSesion($conex);
	$rs = @mysql_query("SELECT lock_s FROM pg_block_log WHERE id_session = '".session_id()."' AND lock_s = 1;", $conex) or die(mysql_error());
	$totalRows = mysql_num_rows($rs);
	if ($totalRows > 0) {
		$_SESSION['session_error'] = "bloq";
		echo "<script> window.location = '".$redirect."'; </script>";
		exit;
	}
	
	if (!isset($_SESSION['idUsuarioSysGts'])) {
		// predeterminado para todos los modulos fuera del Root (vehiculo, cxp,etc)
		$redirect = (strlen($redirect) > 0) ? $redirect : $raiz."index.php"; ?>
        
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <script>
			alert('<?php echo utf8_decode("Su Sesión a Expirado, debe Iniciarla Nuevamente"); ?>');
			window.location = "<?php echo $redirect; ?>";
            </script>
        </head>
        </html>
	<?php
		exit();
	}
	
	$queryModuloUsu = sprintf("SELECT * FROM vw_menu_usuario
	WHERE modulo = %s
		AND id_usuario = %s
		AND id_empresa = %s",
		valTpDato($nomModulo, "text"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($_SESSION['session_empresa'], "int"));
	$rsModuloUsu = mysql_query($queryModuloUsu, $conex) or die(mysql_error());
	$rowModuloUsu = mysql_fetch_assoc($rsModuloUsu);
	
	$accesoPermitido = 1;
	
	if ($rowModuloUsu['acceso'] != 1
	|| ($accionModulo == "insertar" && $rowModuloUsu['insertar'] != 1)
	|| ($accionModulo == "editar" && $rowModuloUsu['editar'] != 1)
	|| ($accionModulo == "eliminar" && $rowModuloUsu['eliminar'] != 1)
	|| ($accionModulo == "desautorizar" && $rowModuloUsu['desincorporar'] != 1)) {
		$accesoPermitido = 0;
	}
	
	// REGISTRANDO LA AUDITORIA
	if ($accionModulo == "insertar" || $accionModulo == "editar" || $accionModulo == "eliminar" || $accionModulo == "desautorizar") {
		guardarAuditoria($rowModuloUsu['id_elemento_menu'],$_SESSION['idUsuarioSysGts'],$_SESSION['session_empresa'],$accesoPermitido,$accionModulo,$tabla,$registro);
	}
	
	if ($accesoPermitido == 0) {
		echo '<script> alert("Acceso Denegado"); window.location="'.$redirect.'"; </script>';
		exit;
	}
}

function validaAcceso($modulo, $accionModulo = "", $auditar = true, $tabla = NULL, $registro = NULL, $idEmpresaUsuarioSesion = NULL, $redireccionar = false, $url_redireccion = NULL) {
	return xvalidaAcceso(NULL, $modulo, $accionModulo, $auditar, $tabla, $registro, $idEmpresaUsuarioSesion, $redireccionar, $url_redireccion);
}

function xvalidaAcceso($objResponse, $modulo, $accionModulo = "", $auditar = true, $tabla = NULL, $registro = NULL, $idEmpresaUsuarioSesion = NULL, $redireccionar = false, $url_redireccion = NULL) {

	include("connections/conex.php");

	$ruta = explode("↓",str_replace(array("/","\\"),"↓",getcwd()));
	$ruta = array_reverse($ruta);
	$raizPpal = false;
	foreach ($ruta as $indice => $valor) {
		$valor2 = explode("_",$valor);
		if ($valor2[0] != "sipre" && $raizPpal == false) {
			$raiz .= "../";
			break;
		} else if ($valor2[0] == "sipre") {
			$raizPpal = true;
		}
	}
	
	$idUsuarioSesion = $_SESSION['idUsuarioSysGts'];
	$idEmpresaUsuarioSesion = ($idEmpresaUsuarioSesion == NULL) ? $_SESSION['idEmpresaUsuarioSysGts'] : $idEmpresaUsuarioSesion; //session_empresa
	$url_redireccion = ($url_redireccion == NULL) ? $raiz."index.php" : $url_redireccion;
	
	$rs = @mysql_query("SELECT lock_s FROM pg_block_log WHERE id_session = '".session_id()."' AND lock_s = 1;", $conex) or die(mysql_error());
	$totalRows = mysql_num_rows($rs);
	if ($totalRows > 0) {
		$_SESSION['session_error'] = "bloq";
		echo "<script> window.location = '".$redirect."'; </script>";
		exit;
	}
	
	// VERIFICA SI EXISTE LA SESION DEL USUARIO
	if ($idUsuarioSesion == NULL || $idUsuarioSesion == "") {
		$r = false;
		if ($objResponse != NULL && $objResponse != false) {
			$objResponse->script("
			alert('".utf8_decode("Su Sesión a Expirado, debe Iniciarla Nuevamente...")."');
			window.location = '".$url_redireccion."';");
			return false;
		} else if ($objResponse != false) {
			echo "<script>
			alert('".utf8_decode("Su Sesión a Expirado, debe Iniciarla Nuevamente...")."');
			window.location = '".$url_redireccion."';
			</script>";
			exit;
		} else {
			return false;
		}
	}
	
	// VERIFICA SI LA SESION ESTA ABIERTA EN LA MISMA MAQUINA
	if (!comprobarSesion($conex, true)) {
		if ($objResponse != NULL && $objResponse != false) {
			$objResponse->script("
			alert('".utf8_decode("Su Sesión a sido Iniciada en otra Máquina, ésta Sesión será Forzada al Cierre")."');
			window.location = '".$url_redireccion."';");
			return false;
		} else if ($objResponse != false) {
			echo "<script>
			alert('".utf8_decode("Su Sesión a sido Iniciada en otra Máquina, ésta Sesión será Forzada al Cierre")."');
			window.location = '".$url_redireccion."';
			</script>";
			exit;
		} else {
			return false;
		}
	}
	
	// obteniendo los datos del modulo sobre el usuario:
	$queryAcceso = sprintf("SELECT * FROM pg_menu_usuario
	WHERE id_elemento_menu IN (SELECT id_elemento_menu FROM pg_elemento_menu WHERE modulo LIKE %s)
		AND id_usuario = %s
		AND id_empresa = %s;",
		valTpDato($modulo, "text"),
		valTpDato($idUsuarioSesion, "int"),
		valTpDato($idEmpresaUsuarioSesion, "int"));
	$rsAcceso = mysql_query($queryAcceso, $conex);
	$rowAcceso = mysql_fetch_assoc($rsAcceso);
	
	$idElementoMenu = $rowAcceso['id_elemento_menu'];
	
	//teniendo acceso bsico, verifica las dems acciones:
	if ($accionModulo != "") {
		switch ($accionModulo) {
			case insertar : $action = "insertar"; break;
			case editar : $action = "editar"; break;
			case eliminar : $action = "eliminar"; break;
			case desautorizar : $action = "desincorporar"; break;
			case desincorporar : $action = "desincorporar"; break;
			default : $r = false;
		}
		$r = ($rowAcceso[$action] == 1) ? true : false;
	} else {
		// VERIFICA SI TIENE ACCESO
		$r = ($rowAcceso['acceso'] == 1) ? true : false;
	}
	
	$accesoPermitido = ($r) ? 1 : 0;
	
	// REGISTRANDO LA AUDITORIA
	if ($auditar == true && in_array($accionModulo, array("insertar","editar","eliminar","desautorizar"))) {
		guardarAuditoria($idElementoMenu, $idUsuarioSesion, $idEmpresaUsuarioSesion, $accesoPermitido, $accionModulo, $tabla, $registro);
	}
	
	// SI NO TIENE ACCESO
	if ($accesoPermitido == 0) {
		if ($objResponse != NULL && $objResponse != false) {
			$objResponse->script("alert('Acceso Denegado');");
			
			if ($redireccionar == true) {
				$objResponse->script("window.location = '".$url_redireccion."';");
			}
			return false;
		} else if ($objResponse != false) {
			echo "<script> alert('Acceso Denegado'); </script>";
			
			if ($redireccionar == true) {
				echo "<script> window.location = '".$url_redireccion."'; </script>";
			}
			exit;
		} else {
			return false;
		}
	} else {
		return $accesoPermitido;
	}
}

$auditoryLast = 0;
function guardarAuditoria($idElementoMenu, $idUsuarioSesion, $idEmpresaUsuarioSesion, $accesoPermitido, $accion, $tabla = NULL, $registro = NULL) {
	global $auditoryLast, $cancelAuditory;
	
	if ($cancelAuditory == true && $accesoPermitido == 1) {
		return;
	}
	//echo var_dump($cancelAuditory);
	include("connections/conex.php");
	
	$accion = ($accion == "") ? "acceso" : $accion;
	
	$sqlauditoria = sprintf("INSERT INTO pg_auditoria (id_elemento_menu,id_usuario,id_empresa,acceso,accion,tabla,id_registro,fecha) 
	VALUES (%s, %s, %s, %s, %s, %s, %s , NOW());",
		valTpDato($idElementoMenu, "int"),
		valTpDato($idUsuarioSesion, "int"),
		valTpDato($idEmpresaUsuarioSesion, "int"),
		valTpDato($accesoPermitido, "boolean"),
		valTpDato($accion, "text"),
		valTpDato($tabla, "text"),
		valTpDato($registro, "text"));
	@mysql_query($sqlauditoria, $conex);
	$auditoryLast = mysql_insert_id($conex);
}

function setAuditoryLast($id) {
	global $auditoryLast, $cancelAuditory;
	if ($cancelAuditory) {
		return;
	}
	if ($id >= 1 && $auditoryLast >= 1) {
		include("connections/conex.php");
		
		//buscando la ultima auditoria
		@mysql_query("UPDATE pg_auditoria SET id_registro = ".$id." WHERE id_auditoria = ".$auditoryLast.";", $conex);
	}
}

function comprobarSesion($conex, $returned = false) {
	$ids = $_SESSION['session_database_id'];
	
	$rsesion = mysql_query(sprintf("SELECT * FROM pg_sesion WHERE id_sesion = %s;",$ids));	
	if ($rsesion && mysql_num_rows($rsesion) != 0) {
		$rowsesion = mysql_fetch_assoc($rsesion);
		
		if ($rowsesion['activa'] == 0) {
			unset($_SESSION['idUsuarioSysGts']);
			unset($_SESSION['session_empresa']);
			unset($_SESSION['session_database_id']);
			
			//sesion forzada al cierre
			if ($returned) {
				return false;
			} else {
				echo '<h1>SIPRE <?php echo cVERSION; ?></h1><p>Sesi&oacute;n forzada al cierre.</p>';			
				exit;
			}
		}
		if ($returned) {
			return true;
		}
	} else {
		return true;
	}
}
?>