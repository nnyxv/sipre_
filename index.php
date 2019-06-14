<?php
session_start();

include("connections/conex.php");

// VERIFICA SI TIENE UNA SESION BLOQUEADA
$queryBlockLog = sprintf("SELECT * FROM pg_block_log block_log
WHERE (block_log.usuario LIKE %s
	OR block_log.id_session = %s)
	AND block_log.lock_s = 1;",
	valTpDato($_POST['txtUser'], "text"),
	valTpDato(session_id(), "text"));
$rsBlockLog = mysql_query($queryBlockLog, $conex);
if (!$rsBlockLog) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsBlockLog = mysql_num_rows($rsBlockLog);
$rowBlockLog = mysql_fetch_assoc($rsBlockLog);

if (isset($_POST['txtUser']) && strlen($_POST['txtUser']) >= 4
&& isset($_POST['txtPassword']) && strlen($_POST['txtPassword']) >= 4
&& $totalRowsBlockLog == 0) {
    // BUSCA EL USUARIO ACTIVO
    $queryUsuario = sprintf("SELECT * FROM vw_iv_usuarios
	WHERE nombre_usuario = %s
		AND clave = md5(%s)
		AND activo = 1
		AND nombre_usuario NOT IN (SELECT block_log.usuario FROM pg_block_log block_log
									WHERE block_log.lock_s = 1);",
		valTpDato($_POST['txtUser'], "text"),
		valTpDato($_POST['txtPassword'], "text"));
    $rsUsuario = mysql_query($queryUsuario, $conex);
	if (!$rsUsuario) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
    $totalRowsUsuario = mysql_num_rows($rsUsuario);
    $rowUsuario = mysql_fetch_assoc($rsUsuario);

    if ($totalRowsUsuario > 0) {
        $idUsuario = $rowUsuario['id_usuario'];
        $ip = $_SERVER["REMOTE_ADDR"];
		$sesion_id = session_id();
		
		// ELIMINA LAS SESIONES DESBLOQUEADAS DEL USUARIO
		@mysql_query(sprintf("DELETE FROM pg_block_log WHERE usuario LIKE %s;",
			valTpDato($_POST['txtUser'], "text")), $conex);

		// CIERRA LA SESION ANTERIOR FORZADAMENTE
        if ($_GET['forceclose'] == "user") {
            @mysql_query(sprintf("UPDATE pg_sesion SET
				activa = 0
			WHERE id_usuario = %s
				AND fecha = CURRENT_DATE()
				AND activa = 1;",
				valTpDato($idUsuario, "int")), $conex);
		}/* else if ($_GET['forceclose'] == "ip") {
            @mysql_query(sprintf("UPDATE pg_sesion SET
				activa = 0
			WHERE ip = %s
				AND fecha = CURRENT_DATE()
				AND activa = 1;",
				valTpDato($ip, "text")), $conex);
        }*/
		
        // BUSCA SI EXISTE UNA SESION ACTIVA
        $querySesion = sprintf("SELECT * FROM pg_sesion
		WHERE id_usuario = %s
			AND fecha = CURRENT_DATE()
			AND activa = 1;",
			valTpDato($idUsuario, "int"));
        $rsSesion = mysql_query($querySesion, $conex);
		if (!$rsSesion) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsSesion = mysql_num_rows($rsSesion);
		$rowSesion = mysql_fetch_assoc($rsSesion);
		
		if ($totalRowsSesion > 0) {
			// VERIFICA SI LA SESION FUE ABIERTA DESDE OTRA IP
			if ($ip != $rowSesion['ip']) {
				$idTipoBloqueo = 1;
			}
			// VERIFICA SI LA SESION FUE ABIERTA EN LA MISMA MAQUINA EN OTRO NAVEGADOR
			if ($sesion_id != $rowSesion['session_id']) {
				$idTipoBloqueo = 2;
			}
		}

        // VERIFICA SI OTRO USUARIO ESTA USANDO EL MISMO EQUIPO
        $queryIp = sprintf("SELECT * FROM pg_sesion
		WHERE id_usuario <> %s
			AND ip = %s
			AND fecha = CURRENT_DATE()
			AND activa = 1;",
			valTpDato($idUsuario, "int"),
			valTpDato($ip, "text"));
		$rsIp = mysql_query($queryIp,$conex);
		if (!$rsIp) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsIp = mysql_num_rows($rsIp);
		$rowIp = mysql_fetch_assoc($rsIp);
		
		if ($totalRowsIp > 0) {
			//$idTipoBloqueo = 3;
		}

        if (!($idTipoBloqueo > 0)) {
			unset($_SESSION['session_error']);
			
            $insertSQL = sprintf("INSERT INTO pg_sesion (session_id, id_usuario, fecha, ip, activa)
			VALUES (%s, %s, CURRENT_DATE(), %s, 1);",
				valTpDato($sesion_id, "text"),
				valTpDato($idUsuario,"int"),
				valTpDato($ip, "text"));
            $Result1 = mysql_query($insertSQL, $conex);
			if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$_SESSION['session_database_id'] = mysql_insert_id($conex);
			
			$queryCargoEmpleado = sprintf("SELECT
				empleado.id_empleado,
				CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
				dep.nombre_departamento,
				cargo.nombre_cargo
			FROM pg_empleado empleado
				INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento)
				INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
				INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento)
			WHERE empleado.id_empleado = %s;",
				valTpDato($rowUsuario['id_empleado'], "int"));
			$rsCargoEmpleado = mysql_query($queryCargoEmpleado, $conex);
			if (!$rsCargoEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$rowCargoEmpleado = mysql_fetch_assoc($rsCargoEmpleado);
	
			$_SESSION['idUsuarioSysGts'] = $rowUsuario['id_usuario'];
			$_SESSION['nombreUsuarioSysGts'] = $rowUsuario['nombre_usuario'];
			$_SESSION['idEmpleadoSysGts'] = $rowUsuario['id_empleado'];
			$_SESSION['nombreEmpleadoSysGts'] = $rowCargoEmpleado['nombre_empleado'];
			$_SESSION['cargoUsuarioSysGts'] = $rowCargoEmpleado['nombre_departamento'] . ": " . $rowCargoEmpleado['nombre_cargo'];
			$_SESSION['idEmpresaUsuarioSysGts'] = $rowUsuario['id_empresa'];
			$_SESSION['session_empresa'] = $rowUsuario['id_empresa'];
			
			// VARIABLE DE SESION DE CONTABILIDAD
			unset($_SESSION['CCSistema']);
			unset($_SESSION['bdContabilidad']);
			unset($_SESSION['bdEmpresa']);
			
			require_once("inc_sesion.php");
			if ((strtotime($rowUsuario['ultima_fecha_cambio_clave']) + 7776000) < strtotime(date("Y-m-d"))) { // 7776000 SEGUNDOS = 3 MESES
				header("location: pg_cambio_clave.php?acc=1");
			} else {
				header("location: index2.php");
				validaModulo("index2");
				exit;
			}
        }
    } else if ($_SESSION['session_error'] != "bloqueo") {
		$_SESSION['session_error'] = intval($_SESSION['session_error']) + 1; ?>
		<script>alert('El usuario o la contraseña son inválidos');</script>
<?php
    }
} else if (isset($_SESSION['idUsuarioSysGts']) && $_SESSION['session_error'] != "bloq_cambio") {
    $_SESSION['session_error'] = intval($_SESSION['session_error']) + 1;
    unset($_SESSION['idUsuarioSysGts']);
    unset($_SESSION['nombreUsuarioSysGts']);
    unset($_SESSION['idEmpleadoSysGts']);
    unset($_SESSION['nombreEmpleadoSysGts']);
    unset($_SESSION['cargoUsuarioSysGts']);
	unset($_SESSION['idEmpresaUsuarioSysGts']);
    unset($_SESSION['session_empresa']);
    unset($_SESSION['session_first_select']);
	
	// VARIABLE DE SESION DE CONTABILIDAD
    unset($_SESSION['CCSistema']);
    unset($_SESSION['bdContabilidad']);
    unset($_SESSION['bdEmpresa']);
	
    // CIERRA LA SESION
    @mysql_query(sprintf("UPDATE pg_sesion SET
		activa = 0
	WHERE id_sesion = %s;",
		valTpDato($_SESSION['session_database_id'], "int")), $conex);
    unset($_SESSION['session_database_id']);
}

$queryLogoEmpresa = "SELECT emp.*,
	emp_ppal.nombre_empresa AS nombre_empresa_ppal
FROM pg_empresa emp
	LEFT JOIN pg_empresa emp_ppal ON (emp.id_empresa_padre = emp_ppal.id_empresa)
WHERE emp.id_empresa <> 100
ORDER BY id_empresa_padre ASC LIMIT 1;";
$rsLogoEmpresa = mysql_query($queryLogoEmpresa, $conex);
if (!$rsLogoEmpresa) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowLogoEmpresa = mysql_fetch_assoc($rsLogoEmpresa);

$idEmpresa = $rowLogoEmpresa['id_empresa'];

(strlen($rowLogoEmpresa['telefono1']) > 0) ? $arrayTelefonos[] = $rowLogoEmpresa['telefono1'] : "";
(strlen($rowLogoEmpresa['telefono2']) > 0) ? $arrayTelefonos[] = $rowLogoEmpresa['telefono2'] : "";
(strlen($rowLogoEmpresa['telefono_taller1']) > 0) ? $arrayTelefonos[] = $rowLogoEmpresa['telefono_taller1'] : "";
(strlen($rowLogoEmpresa['telefono_taller2']) > 0) ? $arrayTelefonos[] = $rowLogoEmpresa['telefono_taller2'] : "";

// BLOQUEA EL SISTEMA POR 3 INTENTOS ERRADOS
if (isset($_SESSION['session_error']) && intval($_SESSION['session_error']) > 3 && $_SESSION['session_error'] != "bloq_cambio") {
	// VERIFICA VALORES DE CONFIGURACION (Manejar Costo de Repuesto)
	$queryConfig305 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 305 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsConfig305 = mysql_query($queryConfig305);
	if (!$rsConfig305) { errorInsertarArticulo($objResponse); return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRowsConfig305 = mysql_num_rows($rsConfig305);
	$rowConfig305 = mysql_fetch_assoc($rsConfig305);
	
	if ($rowConfig305['valor'] == 1) {
	    $_SESSION['session_error'] = "block_log";
	}
} else if (isset($_SESSION['session_error']) && ($_SESSION['session_error'] == "bloq_cambio" || !($_SESSION['session_error']))) {
	// CIERRA LA SESION ANTERIOR FORZADAMENTE
	@mysql_query(sprintf("UPDATE pg_sesion SET
		activa = 0
	WHERE id_usuario = %s
		AND fecha = CURRENT_DATE()
		AND activa = 1;",
		valTpDato($idUsuario, "int")), $conex);
} else if ($totalRowsBlockLog > 0 && (!($_SESSION['session_error']) || !in_array($_SESSION['session_error'], array("block_log", "bloq_cambio")))) {
	$_SESSION['session_error'] = "bloqueo";
} else if ($totalRowsBlockLog == 0 && isset($_SESSION['session_error']) && in_array($_SESSION['session_error'], array("bloqueo", "block_log", "bloq_cambio"))) {
	// ELIMINA LAS SESIONES DESBLOQUEADAS DEL USUARIO
	@mysql_query(sprintf("DELETE FROM pg_block_log
	WHERE usuario LIKE %s;",
		valTpDato($_POST['txtUser'], "text")), $conex);
	unset($_SESSION['session_error']);
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Acceso al Sistema</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    
    <link rel="stylesheet" type="text/css" href="style/styleLogin.css">
    
    <script type="text/javascript" language="javascript" src="js/scriptRafk.js"></script>
    
    <script src="js/login-modernizr/modernizr.custom.63321.js"></script>
    <link rel="stylesheet" type="text/css" href="js/login-modernizr/font-awesome.css" />
    
    <link rel="stylesheet" type="text/css" href="style/animate-custom.css">
    
    <script>
    function redimensionarLogo(objeto) {
        if (byId(objeto).width > byId(objeto).height) {
            if (byId(objeto).width > 180) {
                byId(objeto).height = (180 * byId(objeto).height) / byId(objeto).width;
                byId(objeto).width = 180;
            }
        } else if (byId(objeto).width <= byId(objeto).height) {
            if (byId(objeto).height > 125) {
                byId(objeto).width = (125 * byId(objeto).width) / byId(objeto).height;
                byId(objeto).height = 125;
            }
        }
    }
    
    function validarFrm() {
        var obj = document.getElementById('txtUser');
        if (obj.value == "") {
            alert("Ingrese el usuario");
            obj.focus();
            return false;			
        } else if (obj.value.length < 4) {
            alert("Nombre de usuario muy corto");
            obj.focus();
            return false;
        }
		
        var obj2 = document.getElementById('txtPassword');
        if (obj2.value == "") {
            alert("Ingrese la contraseña");
            obj2.focus();
            return false;			
        } else if (obj2.value.length < 4) {
            alert("Clave de usuario muy corta");
            obj2.focus();
            return false;
        }
        return true;
    }
    </script>
    
    <style>
	body {
		background: #365A96 url(img/login/blurred<?php echo rand(1, 13); ?>.jpg) no-repeat center top;
		background-attachment:fixed;
		-webkit-background-size: cover;
		-moz-background-size: cover;
		background-size: cover;
	}
	</style>
</head>
<body>
<a class="hiddenanchor" id="toequipo"></a>
<a class="hiddenanchor" id="tologin"></a>
<div id="wrapper">
	<div align="center"><img src="img/login/logo_sipre_png.png" width="340"></div>
    
    <div id="login" class="animate form">
        <form id="form1" name="form1" action="<?php echo $_SERVER['vehiculos/PHP_SELF']; ?>" class="form-3" method="post" onSubmit="return validarFrm();">
            <table cellpadding="0" cellspacing="0" style="font-size:15px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; border-bottom:1px solid #333333; text-shadow:0 2px 0 rgba(102,102,102,0.8); box-shadow:0 1px 0 rgba(102,102,102,0.8);" width="100%">
            <tr>
                <td align="left">
                    <span style="display:inline-block; text-transform:uppercase; color:#B7D154; padding-right:2px;">INICIAR SESIÓN</span><!-- or sign up-->
                </td>
                <td align="right">
                	<span>Sistema</span> <a href="controladores/descargar_archivo.php?ruta=img/login/icono_sipre_ico&tipo=ico" style="display:inline-block; text-transform:uppercase; color:#38A6F0; padding-left:2px; text-decoration:none">SIPRE <?php echo cVERSION; ?></a>
                	<span style="display:inline-block; text-transform:uppercase; color:#C00; padding-right:2px;"><?php echo (strstr($_SESSION['database_conex'], "prueba")) ? "(SISTEMA DE PRUEBA)" : ""; ?></span>
                </td>
            </tr>
            </table>
            
            <table style="padding-top:10px; border-top:1px solid rgba(255,255,255,1);" width="100%">
            <tr>
                <td align="center" width="45%">
                    <p style="font-size:11px; font-weight:bold; color:#bdb5aa; padding-bottom:8px;">
                    <?php echo $rowLogoEmpresa['nombre_empresa']; ?>
                    <br>
                    <?php echo $rowLogoEmpresa['rif']; ?>
                    </p>
                    
                    <table style="text-align:center; background:#FFF; border-radius:0.4em;">
                    <tr>
                        <td><img id="imgLogoEmpresa" name="imgLogoEmpresa" src="<?php echo $rowLogoEmpresa['logo_familia']; ?>" width="180"></td>
                    </tr>
                    </table>
                </td>
                <td width="55%">
                    <div id="divLogin">
                    <?php
                    if (isset($_SESSION['session_error']) && $_SESSION['session_error'] == "bloqueo") {
                        unset($_SESSION['idUsuarioSysGts']);
                        unset($_SESSION['session_empresa']);
						
						echo "<p style=\"font-size:11px; font-weight:bold; color:#bdb5aa; padding-bottom:8px;\">";
							echo "<a style=\"color:#FFFFFF;\" href=\"unlock.php?nombreUsuario=".$_POST['txtUser']."\">SISTEMA BLOQUEADO</a>:";
							echo "<br>Aun tiene su sesión bloqueada. Comuníquese con soporte al usuario para desbloquear el sistema o reestablecer su contraseña.";
							echo "<br>(Nro. de referencia: ".$rowBlockLog['id_block_log'].")";
						echo "</p>";
                    } else if (isset($_SESSION['session_error']) && $_SESSION['session_error'] == "block_log") {
                        if (@mysql_query(sprintf("INSERT INTO pg_block_log (id_session, lock_s, usuario, fecha, hora)
                        VALUES (%s, 1, %s, CURRENT_DATE(), NOW());",
                            valTpDato(session_id(), "text"),
                            valTpDato($_POST['txtUser'], "text")), $conex)) {
                            $_SESSION['refer'] = mysql_insert_id($conex);
                        }
                        unset($_SESSION['idUsuarioSysGts']);
                        unset($_SESSION['session_empresa']);
						
						echo "<p style=\"font-size:11px; font-weight:bold; color:#bdb5aa; padding-bottom:8px;\">";
							echo "<a style=\"color:#FFFFFF;\" href=\"unlock.php?nombreUsuario=".$_POST['txtUser']."\">SISTEMA BLOQUEADO</a>:";
							echo "<br>Ha intentado acceder al sistema 3 veces de forma errada. Comuníquese con soporte al usuario para desbloquear el sistema o reestablecer su contraseña.";
							echo "<br>(Nro. de referencia: ".$_SESSION['refer'].")";
						echo "</p>";
                    } else if (isset($_SESSION['session_error']) && $_SESSION['session_error'] == "bloq_cambio") {
                        if (@mysql_query(sprintf("INSERT INTO pg_block_log (id_session, lock_s, usuario, fecha, hora)
                        SELECT %s, 1, nombre_usuario, CURRENT_DATE(), NOW() FROM pg_usuario
                        WHERE id_usuario = %s;",
                            valTpDato(session_id(), "text"),
                            valTpDato($_SESSION['idUsuarioSysGts']), "int"),$conex)) {
                            $_SESSION['refer'] = mysql_insert_id($conex);
                        }
                        unset($_SESSION['idUsuarioSysGts']);
                        unset($_SESSION['session_empresa']); 
						
						echo "<p style=\"font-size:11px; font-weight:bold; color:#bdb5aa; padding-bottom:8px;\">";
							echo "<a style=\"color:#FFFFFF;\" href=\"unlock.php?nombreUsuario=".$_POST['txtUser']."\">SISTEMA BLOQUEADO</a> :";
							echo "<br>Ha intentado cambiar su contraseña más de 3 veces. Comuníquese con soporte al usuario para desbloquear el sistema o reestablecer su contraseña.";
							echo "<br>(Nro. de referencia: ".$_SESSION['refer'].")";
						echo "</p>";
                    } else if (isset($idTipoBloqueo) && $idTipoBloqueo > 0) {
						echo "<p style=\"font-size:11px; font-weight:bold; color:#bdb5aa; padding-bottom:8px;\">";
                        switch ($idTipoBloqueo) {
                            case 1 :
                                echo "Usted ha iniciado sesi&oacute;n en otro equipo, ci&eacute;rrela primero para continuar";
                                $linkclose = " ó <a style=\"color:#FFFFFF;\" href=\"index.php?forceclose=user\">Forzar el Cierre de Sesi&oacute;n</a>";
                                break;
                            case 2 :
                                echo "Usted ha iniciado sesi&oacute;n en otra ventana";
                                $linkclose = " ó <a style=\"color:#FFFFFF;\" href=\"index.php?forceclose=user\">Forzar el Cierre de Sesi&oacute;n</a>";
                                break;
                            case 3 :
                                echo "Otro usuario inici&oacute; sesi&oacute;n en este equipo, primero ci&eacute;rre dicha sesión para continuar";
                                $linkclose = " ó <a style=\"color:#FFFFFF;\" href=\"index.php?forceclose=ip\">Forzar el Cierre de Sesi&oacute;n</a>";
                                break;
                        }
                        	echo "<br><a style=\"color:#FFFFFF;\" href=\"index.php\">Volver a intentar</a>".$linkclose;
						echo "</p>";
                    } else { ?>
                        <p class="field">
                            <input type="text" id="txtUser" name="txtUser" placeholder="Usuario">
                            <i class="icon-user icon-large"></i>
                        </p>
                        <p class="field">
                            <input type="password" id="txtPassword" name="txtPassword" placeholder="Contraseña">
                            <i class="icon-lock icon-large"></i>
                        </p>
                        <p>&nbsp;</p>
                        <p><input type="submit" name="submit" value="Entrar"></p>
                    <?php
                    } ?>
                    </div>
                    
                    <div align="center" id="divNavegador"><img border="0" src="img/login/chrome-firefox.jpg" usemap="#imgNavegadorMap" width="150"/></div>
                </td>
            </tr>
            <tr>
                <td align="center" colspan="2" style="padding-top:10px;">
                    <p style="font-size:10px; font-weight:bold; color:#bdb5aa;">
                    <?php echo (count($arrayTelefonos) > 0) ? "Telf.: ".implode(" / ", $arrayTelefonos) : ""; ?>
                    </p>
                </td>
            </tr>
            </table>
        </form>​
    
        <form class="form-4">
            <table width="100%">
            <tr align="left">
                <td style="font-size:10px; font-weight:bold; color:#bdb5aa;" width="35%">Copyrigth © 2008, <a style="color:#FFFFFF;" href="http://www.gotosys.com" target="_blank">Goto Systems C.A.</a></td>
                <td align="center"><p class="change_link"><a href="#toequipo" class="to_equipo"> Equipo de Desarrollo </a></p></td>
                <td align="right" width="35%"><img src="img/login/logo_gotosystems_png.png" height="25"></td>
            </tr>
            </table>
        </form>
	</div>
    
    <div id="equipo" class="animate form">
        <form class="form-3">
            <table cellpadding="0" cellspacing="0" style="font-size:15px; font-weight:bold; color:#bdb5aa; padding-bottom:8px; border-bottom:1px solid #333333; text-shadow:0 2px 0 rgba(102,102,102,0.8); box-shadow:0 1px 0 rgba(102,102,102,0.8);" width="100%">
            <tr>
                <td align="left">
                    <span style="display:inline-block; text-transform:uppercase; color:#B7D154; padding-right:2px;">EQUIPO DE DESARROLLO</span><!-- or sign up-->
                </td>
                <td align="right"><span>Sistema</span> <span style="display:inline-block; text-transform:uppercase; color:#38A6F0; padding-left:2px;">SIPRE <?php echo cVERSION; ?></span></td>
            </tr>
            </table>
            
            <table style="padding-top:10px; border-top:1px solid rgba(255,255,255,1);" width="100%">
            <tr>
                <td align="center" width="45%">
                    <p style="font-size:11px; font-weight:bold; color:#bdb5aa; padding-bottom:8px;">
                    GOTO SYSTEMS, C.A.
                    <br>
                    J-30906795-8
                    </p>
                    
                    <table style="text-align:center; background:#FFF; border-radius:0.4em;">
                    <tr>
                        <td><img id="imgLogoEmpresa" name="imgLogoEmpresa" src="img/login/logo_gotosystems_png.png" width="180"></td>
                    </tr>
                    </table>
                </td>
                <td width="55%">
					<?php
					$arrayEquipoTrabajo[] = array("nombre_empleado" => "Arsenio Díaz", "cargo_empleado" => "Director de Tecnología");
					$arrayEquipoTrabajo[] = array("nombre_empleado" => "Carlos Agrela", "cargo_empleado" => "Coordinador de Soporte y Adiestramiento");
					$arrayEquipoTrabajo[] = array("nombre_empleado" => "Shedymar Rodríguez", "cargo_empleado" => "Coordinadora de Desarrollo y Análisis");
					
					$arrayEquipoTrabajo[] = array("nombre_empleado" => "Sócrates Manaure", "cargo_empleado" => "Especialista Analista Programador Junior");
					
					
					$arrayEquipoTrabajo[] = array("nombre_empleado" => "Eiborth Gómez", "cargo_empleado" => "Especialista Analista Programador Junior");
										
                    $html .= "<table border=\"0\" width=\"100%\">";
                    $contFila = 0;
                    foreach ($arrayEquipoTrabajo as $indiceExamen => $valorExamen) {
                        $contFila++;
                        
                        $checked = (in_array($valorExamen['id_examen'],$arrayIdExamen)) ? "checked=\"checked\"" : "";
                        
                        $html .= (fmod($contFila, 2) == 1) ? "<tr align=\"left\">" : "";
                            
                            $html .= 
							"<td style=\"font-size:11px; font-weight:bold; color:#bdb5aa; padding-bottom:8px;\" valign=\"top\">".
								"<div>".$valorExamen['nombre_empleado']."</div>".
								"<div style=\"display:inline-block; color:#38A6F0; padding-right:2px;\">".$valorExamen['cargo_empleado']."</div>".
							"</td>";
                                
                        $html .= (fmod($contFila, 2) == 0) ? "</tr>" : "";
                    }
                    $html .= "</table>";
					
					echo $html;
                    ?>
				</td>
			</tr>
            <tr>
                <td align="center" colspan="2" style="padding-top:10px;">
                    <p style="font-size:10px; font-weight:bold; color:#bdb5aa;">
                    <?php echo (count($arrayTelefonos) > 0) ? "Telf.: 0212-2665950" : ""; ?>
                    </p>
                </td>
            </tr>
            </table>
            
            <table style="padding-top:10px;" width="100%">
            <tr align="left">
                <td style="font-size:10px; font-weight:bold; color:#bdb5aa;" width="35%">Copyrigth © 2008, <a style="color:#FFFFFF;" href="http://www.gotosys.com" target="_blank">Goto Systems C.A.</a></td>
                <td align="center"><p class="change_link"><a href="#tologin" class="to_equipo"> Iniciar Sesión </a></p></td>
                <td align="right" style="font-size:10px; font-weight:bold; color:#bdb5aa;" width="35%"><a style="color:#FFFFFF;" href="mailto:erp@gotosys.com">erp@gotosys.com</a></td>
            </tr>
            </table>
        </form>
    </div>
</div>

<map name="imgNavegadorMap" id="imgNavegadorMap">
  <area shape="rect" coords="0,57,150,115" href="https://www.google.com/intl/es/chrome/browser/" target="_blank" />
  <area shape="rect" coords="0,0,150,58" href="http://www.mozilla.org/es-ES/firefox/new/" target="_blank" />
</map>
</body>
</html>

<script language="javascript" type="text/javascript">
/*redimensionarLogo('imgLogoEmpresa');*/

var habilitar = false;
if (navigator.userAgent.indexOf('Firefox') != -1) {
	habilitar = true;
} else if (navigator.userAgent.indexOf('Chrome') != -1) {
	habilitar = true;
} else if (navigator.userAgent.indexOf('Safari') != -1) {
	habilitar = true;
} else if (navigator.userAgent.indexOf('MSIE') != -1) {
	habilitar = false;
} else if (navigator.userAgent.indexOf('Opera') != -1) {
	habilitar = false;
}

if (habilitar == false) {
	byId('divLogin').style.display = 'none';
	alert("Este Navegador no es compatible, se recomienda usar Mozilla Firefox ó Google Chrome");
} else {
	byId('divNavegador').style.display = 'none';
	if (byId('txtUser') != undefined) {
		byId('txtUser').focus();
	}
}
</script>