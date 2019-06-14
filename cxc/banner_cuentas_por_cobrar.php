<?php
require_once ("../connections/conex.php");

$idUsuario = $_SESSION['idUsuarioSysGts'];
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
?>

<link rel="stylesheet" href="../js/styleDropdownMenuCuentasPorCobrar.css" type="text/css" />

<script type="text/javascript" language="JavaScript" src="../js/dropdownMenu/scriptDropdownMenu.js"></script>

<?php //echo var_dump($_SESSION);?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr height="6">
	<td></td>
</tr>
<tr>
	<td><?php $raiz = "../"; include ("../banner_sesion.php"); ?></td>
</tr>
<tr height="6">
	<td></td>
</tr>
<tr>
	<td style="border-radius:6px;" background="../img/cuentas_por_cobrar/header.gif">
<ul class="menu" id="menu">
	<li><a href="../index2.php" class="menulink"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><img src="../img/cuentas_por_cobrar/resultset_next.png"></td><td>&nbsp;</td><td width="100%"><?php echo utf8_encode("Menú ERP"); ?></td></tr></table></a></li>
	<!--<li><a href="iv_surtido_taller_list.php" class="menulink"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><img src="../img/cuentas_por_cobrar/resultset_next.png"></td><td>&nbsp;</td><td width="100%"><?php echo utf8_encode("Surtido"); ?></td></tr></table></a></li>-->
<?php
$queryModulo = sprintf("SELECT *
FROM pg_elemento_menu element_menu
	INNER JOIN pg_menu_usuario menu_usu ON (element_menu.id_elemento_menu = menu_usu.id_elemento_menu)
WHERE menu_usu.id_usuario = %s
	AND menu_usu.id_empresa = %s
	AND modulo = 'cc'
ORDER BY element_menu.def_order ASC;",
	valTpDato($idUsuario, "int"),
	valTpDato($idEmpresa, "int"));
$rsModulo = mysql_query($queryModulo);
if (!$rsModulo) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRowsModulo = mysql_num_rows($rsModulo);
while ($rowModulo = mysql_fetch_assoc($rsModulo)) {
	$idPadre = $rowModulo['id_elemento_menu'];
	
	$queryMenu2 = sprintf("SELECT DISTINCT
		element_menu.id_elemento_menu,
		element_menu.nombre
	FROM pg_elemento_menu element_menu
		INNER JOIN pg_menu_usuario menu_usu ON (element_menu.id_elemento_menu = menu_usu.id_elemento_menu)
	WHERE menu_usu.id_empresa = %s
		AND element_menu.id_padre = %s
	ORDER BY element_menu.def_order ASC;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idPadre, "int"));
	$rsMenu2 = mysql_query($queryMenu2);
	if (!$rsMenu2) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsMenu2 = mysql_num_rows($rsMenu2);
	while ($rowMenu2 = mysql_fetch_assoc($rsMenu2)) {
		$idPadre2 = $rowMenu2['id_elemento_menu'];
		
		$queryMenu3 = sprintf("SELECT *
		FROM pg_elemento_menu element_menu
			INNER JOIN pg_menu_usuario menu_usu ON (element_menu.id_elemento_menu = menu_usu.id_elemento_menu)
		WHERE menu_usu.id_usuario = %s
			AND menu_usu.id_empresa = %s
			AND element_menu.id_padre = %s
			AND ((element_menu.modulo IS NULL
					AND (SELECT COUNT(element_menu2.id_padre)
					FROM pg_elemento_menu element_menu2
						INNER JOIN pg_menu_usuario menu_usu2 ON (element_menu2.id_elemento_menu = menu_usu2.id_elemento_menu)
					WHERE menu_usu2.id_usuario = menu_usu.id_usuario
						AND menu_usu2.id_empresa = menu_usu.id_empresa
						AND element_menu2.id_padre = element_menu.id_elemento_menu) > 0)
				OR (element_menu.modulo IS NOT NULL
					AND menu_usu.acceso = 1))
			AND ((SELECT element_menu2.modulo FROM pg_elemento_menu element_menu2 WHERE element_menu2.id_elemento_menu = element_menu.id_padre) IS NULL
				OR (SELECT element_menu2.modulo FROM pg_elemento_menu element_menu2 WHERE element_menu2.id_elemento_menu = element_menu.id_padre) LIKE '')
		ORDER BY element_menu.def_order ASC;",
			valTpDato($idUsuario, "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idPadre2, "int"));
		$rsMenu3 = mysql_query($queryMenu3);
		if (!$rsMenu3) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRowsMenu3 = mysql_num_rows($rsMenu3);
		if ($totalRowsMenu3 > 0) {
			echo "<li><a href=\"#\" class=\"menulink\"><table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td><img src=\"../img/cuentas_por_cobrar/resultset_next.png\"></td><td>&nbsp;</td><td width=\"100%\">".utf8_encode($rowMenu2['nombre'])."</td></tr></table></a>";
				echo "<ul>";
				
			while ($rowMenu3 = mysql_fetch_assoc($rsMenu3)) {
				$idPadre3 = $rowMenu3['id_elemento_menu'];
				
				echo menuRecursivo($idUsuario, $idEmpresa, $idPadre3, $rowMenu3['nombre'], $rowMenu3['modulo']);
			}
				echo "</ul>";
			echo "</li>";
		}
	}
}

function menuRecursivo($idUsuario, $idEmpresa, $idPadre, $nombrePadre, $linkPadre) {
	$queryMenu = sprintf("SELECT *
	FROM pg_elemento_menu element_menu
		INNER JOIN pg_menu_usuario menu_usu ON (element_menu.id_elemento_menu = menu_usu.id_elemento_menu)
	WHERE menu_usu.id_usuario = %s
		AND menu_usu.id_empresa = %s
		AND element_menu.id_padre = %s
		AND ((element_menu.modulo IS NULL
				AND (SELECT COUNT(element_menu2.id_padre)
				FROM pg_elemento_menu element_menu2
					INNER JOIN pg_menu_usuario menu_usu2 ON (element_menu2.id_elemento_menu = menu_usu2.id_elemento_menu)
				WHERE menu_usu2.id_usuario = menu_usu.id_usuario
					AND menu_usu2.id_empresa = menu_usu.id_empresa
					AND element_menu2.id_padre = element_menu.id_elemento_menu) > 0)
			OR (element_menu.modulo IS NOT NULL
				AND menu_usu.acceso = 1))
		AND ((SELECT element_menu2.modulo FROM pg_elemento_menu element_menu2 WHERE element_menu2.id_elemento_menu = element_menu.id_padre) IS NULL
			OR (SELECT element_menu2.modulo FROM pg_elemento_menu element_menu2 WHERE element_menu2.id_elemento_menu = element_menu.id_padre) LIKE '')
	ORDER BY element_menu.def_order ASC;",
		valTpDato($idUsuario, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idPadre, "int"));
	$rsMenu = mysql_query($queryMenu);
	if (!$rsMenu) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsMenu = mysql_num_rows($rsMenu);
	if ($totalRowsMenu > 0) {
		$cadena .= "<li><a href=\"#\" class=\"sub\">".utf8_encode($nombrePadre)."</a>";
			$cadena .= "<ul>";
			
		while ($rowMenu = mysql_fetch_assoc($rsMenu)) {
			$idPadre2 = $rowMenu['id_elemento_menu'];
			
			$queryMenuVerifica = sprintf("SELECT *
			FROM pg_elemento_menu element_menu
				INNER JOIN pg_menu_usuario menu_usu ON (element_menu.id_elemento_menu = menu_usu.id_elemento_menu)
			WHERE menu_usu.id_usuario = %s
				AND menu_usu.id_empresa = %s
				AND element_menu.id_padre = %s
				AND ((element_menu.modulo IS NULL
						AND (SELECT COUNT(element_menu2.id_padre)
						FROM pg_elemento_menu element_menu2
							INNER JOIN pg_menu_usuario menu_usu2 ON (element_menu2.id_elemento_menu = menu_usu2.id_elemento_menu)
						WHERE menu_usu2.id_usuario = menu_usu.id_usuario
							AND menu_usu2.id_empresa = menu_usu.id_empresa
							AND element_menu2.id_padre = element_menu.id_elemento_menu) > 0)
					OR (element_menu.modulo IS NOT NULL
						AND menu_usu.acceso = 1))
				AND ((SELECT element_menu2.modulo FROM pg_elemento_menu element_menu2 WHERE element_menu2.id_elemento_menu = element_menu.id_padre) IS NULL
					OR (SELECT element_menu2.modulo FROM pg_elemento_menu element_menu2 WHERE element_menu2.id_elemento_menu = element_menu.id_padre) LIKE '')
			ORDER BY element_menu.def_order ASC;",
				valTpDato($idUsuario, "int"),
				valTpDato($idEmpresa, "int"),
				valTpDato($idPadre2, "int"));
			$rsMenuVerifica = mysql_query($queryMenuVerifica);
			if (!$rsMenuVerifica) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRowsMenuVerifica = mysql_num_rows($rsMenuVerifica);
			
			if ($totalRowsMenuVerifica > 0) 
				$cadena .= menuRecursivo($idUsuario, $idEmpresa, $idPadre2, $rowMenu['nombre'], $rowMenu['modulo']);
			else
				$cadena .= "<li class=\"topline\"><a href=\"".$rowMenu['modulo'].".php\">".utf8_encode($rowMenu['nombre'])."</a></li>";
		}
		
			$cadena .= "</ul>";
		$cadena .= "</li>";
	} else {
		$cadena .= "<li class=\"topline\"><a href=\"".$linkPadre.".php\">".utf8_encode($nombrePadre)."</a></li>";
	}
	
	return $cadena;
}
?>
</ul>
	</td>
</tr>
</table>
<script type="text/javascript">
var menu = new menu.dd("menu");
menu.init("menu","menuhover");
</script>