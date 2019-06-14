<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

$idUsuario = $_SESSION['idUsuarioSysGts'];
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato($idEmpresa, "int"));
$rsConfig400 = mysql_query($queryConfig400);
if (!$rsConfig400) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
$rowConfig400 = mysql_fetch_assoc($rsConfig400);

if ($rowConfig400['valor'] == 1) { // 0 = Caja Propia, 1 = Caja Empresa Principal
	$queryEmpresa = sprintf("SELECT suc.id_empresa_padre FROM pg_empresa suc WHERE suc.id_empresa = %s;",
		valTpDato($idEmpresa, "int"));
	$rsEmpresa = mysql_query($queryEmpresa);
	if (!$rsEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE_);
	$rowEmpresa = mysql_fetch_assoc($rsEmpresa);
	
	$idEmpresa = ($rowEmpresa['id_empresa_padre'] > 0) ? $rowEmpresa['id_empresa_padre'] : $idEmpresa;
}

$queryCaja = sprintf("SELECT * FROM caja WHERE caja.idCaja = %s;",
	valTpDato($idCajaPpal, "int"));
$rsCaja = mysql_query($queryCaja);
if (!$rsCaja) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowCaja = mysql_fetch_assoc($rsCaja);

$queryApertura = sprintf("SELECT
	ape.fechaAperturaCaja,
	ape.statusAperturaCaja,
	(CASE ape.statusAperturaCaja
		WHEN 0 THEN 'CERRADA TOTALMENTE'
		WHEN 1 THEN CONCAT_WS(' EL ', 'ABIERTA', DATE_FORMAT(ape.fechaAperturaCaja, %s))
		WHEN 2 THEN 'CERRADA PARCIALMENTE'
		ELSE 'CERRADA TOTALMENTE'
	END) AS estatus_apertura_caja,
	IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
FROM ".$apertCajaPpal." ape
	RIGHT JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (ape.id_empresa = vw_iv_emp_suc.id_empresa_reg)
WHERE ((ape.statusAperturaCaja IN (0,1,2) AND ape.idCaja = %s)
		OR statusAperturaCaja IS NULL)
	AND vw_iv_emp_suc.id_empresa_reg = %s
ORDER BY ape.id DESC
LIMIT 1;",
	valTpDato(spanDatePick, "date"),
	valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	valTpDato($idEmpresa, "int"));
$rsApertura = mysql_query($queryApertura);
if (!$rsApertura) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowApertura = mysql_fetch_assoc($rsApertura);

switch($rowApertura['statusAperturaCaja']) {
	case 0 : $class = "divMsjError"; break;
	case 1 : $class = "divMsjInfo"; break;
	case 2 : $class = "divMsjAlerta"; break;
}
?>

<link rel="stylesheet" href="../js/styleDropdownMenuCaja.css" type="text/css" />

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
	<td style="border-radius:6px;" background="../img/caja_vehiculos/header.gif">
<ul class="menu" id="menu">
	<li><a href="../index2.php" class="menulink"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><img src="../img/caja_vehiculos/resultset_next.png"></td><td>&nbsp;</td><td width="100%"><?php echo utf8_encode("Menú ERP"); ?></td></tr></table></a></li>
	<!--<li><a href="iv_surtido_taller_list.php" class="menulink"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><img src="../img/caja_vehiculos/resultset_next.png"></td><td>&nbsp;</td><td width="100%"><?php echo utf8_encode("Surtido"); ?></td></tr></table></a></li>-->
<?php
$queryModulo = sprintf("SELECT *
FROM pg_elemento_menu element_menu
	INNER JOIN pg_menu_usuario menu_usu ON (element_menu.id_elemento_menu = menu_usu.id_elemento_menu)
WHERE menu_usu.id_usuario = %s
	AND menu_usu.id_empresa = %s
	AND modulo = 'cj'
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
			echo "<li><a href=\"#\" class=\"menulink\"><table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td><img src=\"../img/caja_vehiculos/resultset_next.png\"></td><td>&nbsp;</td><td width=\"100%\">".utf8_encode($rowMenu2['nombre'])."</td></tr></table></a>";
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
<tr>
	<td height="4"></td>
</tr>
<tr>
	<td class="<?php echo $class;?>" height="24"><?php echo $rowApertura['nombre_empresa']." (".$rowCaja['descripcion'].")<br>".$rowApertura['estatus_apertura_caja']; ?></td>
</tr>
</table>
<script type="text/javascript">
var menu = new menu.dd("menu");
menu.init("menu","menuhover");
</script>