<?php
define("insertar","insertar");
define("editar","editar");
define("eliminar","eliminar");
define("desautorizar","desautorizar");
define("desincorporar","desautorizar");
$cancelAuditory = false;
session_start();

function validaModulo($nomModulo, $accion = "", $tabla = false, $registro = false) {
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
	
	/*if (!isset($_SESSION['idUsuarioSysGts'])) {
		// predeterminado para todos los modulos fuera del Root (vehiculo, cxp,etc)
		$redirect = (strlen($redirect) > 0) ? $redirect : $raiz."index.php"; ?>
        
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <script>
			alert('<?php echo utf8_encode("Su Sesión a Expirado, debe Iniciarla Nuevamente"); ?>');
			window.location = "<?php echo $redirect; ?>";
            </script>
        </head>
        </html>
	<?php
		exit();
	}*/
	
	$queryModuloUsu = sprintf("SELECT * FROM vw_menu_usuario
	WHERE modulo = %s
		AND id_usuario = %s
		AND id_empresa = %s",
		valTpDato($nomModulo, "text"),
		valTpDato($_SESSION['idUsuarioSysGts'], "int"),
		valTpDato($_SESSION['session_empresa'], "int"));
	$rsModuloUsu = mysql_query($queryModuloUsu, $conex) or die(mysql_error());
	$rowModuloUsu = mysql_fetch_assoc($rsModuloUsu);
	
	$permitir = 1;
	
	if ($rowModuloUsu['acceso'] != 1
	|| ($accion == "insertar" && $rowModuloUsu['insertar'] != 1)
	|| ($accion == "editar" && $rowModuloUsu['editar'] != 1)
	|| ($accion == "eliminar" && $rowModuloUsu['eliminar'] != 1)
	|| ($accion == "desautorizar" && $rowModuloUsu['desincorporar'] != 1)) {
		$permitir = 0;
	}
	
	// REGISTRANDO LA AUDITORIA
	if ($accion == "insertar" || $accion == "editar" || $accion == "eliminar" || $accion == "desautorizar") {
		guardarAuditoria($rowModuloUsu['id_elemento_menu'],$_SESSION['idUsuarioSysGts'],$_SESSION['session_empresa'],$permitir,$accion,$tabla,$registro);
	}
	
	if ($permitir == 0) {
		echo '<script> alert("Acceso Denegado"); window.location="'.$redirect.'"; </script>';
		exit;
	}
}

function validaAcceso($modulo, $accion = "", $auditar = true, $tabla = NULL, $registro = NULL, $idEmpresaUsuarioSesion = NULL, $redireccionar = false, $urlRedireccion = NULL) {
	return xvalidaAcceso(NULL, $modulo, $accion, $auditar, $tabla, $registro, $idEmpresaUsuarioSesion, $redireccionar, $urlRedireccion);
}

function xvalidaAcceso($objResponse, $modulo, $accion = "", $auditar = true, $tabla = NULL, $registro = NULL, $idEmpresaUsuarioSesion = NULL, $redireccionar = false, $urlRedireccion = NULL) {
	include("connections/conex.php");
	
	$idUsuarioSesion = $_SESSION['idUsuarioSysGts'];
	$idEmpresaUsuarioSesion = ($idEmpresaUsuarioSesion == NULL) ? $_SESSION['idEmpresaUsuarioSysGts'] : $idEmpresaUsuarioSesion; //session_empresa
	$urlRedireccion = ($urlRedireccion == NULL) ? raizSite."index.php" : $urlRedireccion;
	
	$rs = @mysql_query("SELECT lock_s FROM pg_block_log WHERE id_session = '".session_id()."' AND lock_s = 1;", $conex) or die(mysql_error());
	$totalRows = mysql_num_rows($rs);
	if ($totalRows > 0) {
		$_SESSION['session_error'] = "bloq";
		echo "<script> window.location = '".$redirect."'; </script>";
		exit;
	}
	
	// VERIFICA SI EXISTE LA SESION DEL USUARIO
	if (!($idUsuarioSesion > 0)) {
		$permitir = 0;
		if ($objResponse != NULL && $objResponse != false) {
			$objResponse->script("
			alert('".utf8_decode("Su sesión a expirado, debe iniciarla nuevamente")."');
			window.location = '".$urlRedireccion."';");
			return false;
		} else if ($objResponse == NULL || $objResponse == false) {
			echo "<script>
			alert('".("Su sesión a expirado, debe iniciarla nuevamente")."');
			window.location = '".$urlRedireccion."';
			</script>";
			exit;
		} else {
			echo "Su sesión a expirado, debe iniciarla nuevamente";
			return false;
		}
	}
	
	// VERIFICA SI LA SESION ESTA ABIERTA EN LA MISMA MAQUINA
	if (!comprobarSesion($conex, true)) {
		if ($objResponse != NULL && $objResponse != false) {
			$objResponse->script("
			alert('".utf8_decode("Su Sesión a sido Iniciada en otra Máquina, ésta Sesión será Forzada al Cierre")."');
			window.location = '".$urlRedireccion."';");
			return false;
		} else if ($objResponse == NULL || $objResponse == false) {
			echo "<script>
			alert('".("Su Sesión a sido Iniciada en otra Máquina, ésta Sesión será Forzada al Cierre")."');
			window.location = '".$urlRedireccion."';
			</script>";
			exit;
		} else {
			echo "Su Sesión a sido Iniciada en otra Máquina, ésta Sesión será Forzada al Cierre";
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
	
	switch ($accion) {
		case insertar : $accion = "insertar"; break;
		case editar : $accion = "editar"; break;
		case eliminar : $accion = "eliminar"; break;
		case desautorizar : $accion = "desincorporar"; break;
		case desincorporar : $accion = "desincorporar"; break;
		default : $accion = "acceso";
	}
	$permitir = ($rowAcceso[$accion] == 1) ? 1 : 0;
	
	// REGISTRANDO LA AUDITORIA
	if ($auditar == true && in_array($accion, array("insertar","editar","eliminar","desautorizar"))) {
		guardarAuditoria($idElementoMenu, $idUsuarioSesion, $idEmpresaUsuarioSesion, $permitir, $accion, $tabla, $registro);
	}
	
	// SI NO TIENE ACCESO
	if ($permitir == 0) {
		if ($objResponse != NULL && $objResponse != false) {
			$objResponse->alert("Acceso Denegado");
			
			$objResponse->script(($redireccionar == true) ? "window.location = '".$urlRedireccion."';" : "");
			return false;
		} else if ($objResponse == NULL || $objResponse == false) {
			echo "<script> alert('Acceso Denegado'); </script>";
			
			echo (($redireccionar == true) ? "<script> window.location = '".$urlRedireccion."'; </script>" : "<script> top.history.back(); </script>");
			exit;
		} else {
			return false;
		}
	} else {
		return $permitir;
	}
}

$auditoryLast = 0;
function guardarAuditoria($idElementoMenu, $idUsuarioSesion, $idEmpresaUsuarioSesion, $permitir, $accion, $tabla = NULL, $registro = NULL) {
	global $auditoryLast, $cancelAuditory;
	
	if ($cancelAuditory == true && $permitir == 1) {
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
		valTpDato($permitir, "boolean"),
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