<?php
require_once("connections/conex.php");

$idUsuario = $_SESSION['idUsuarioSysGts'];
$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
?>

<link rel="stylesheet" href="js/styleDropdownMenuErp.css" type="text/css" />

<script type="text/javascript" language="JavaScript" src="js/dropdownMenu/scriptDropdownMenu.js"></script>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr height="6">
	<td></td>
</tr>
<tr>
	<td><?php include("banner_sesion.php"); ?></td>
</tr>
<tr height="6">
	<td></td>
</tr>
<tr>
	<td style="border-radius:6px;" background="img/erp/header.gif">
<ul class="menu" id="menu">
	<li><a href="index2.php" class="menulink"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><img src="img/erp/resultset_next.png"></td><td>&nbsp;</td><td width="100%"><?php echo utf8_encode("Inicio"); ?></td></tr></table></a></li>
	<?php
    $queryMenu2 = sprintf("SELECT *
    FROM pg_elemento_menu element_menu
        INNER JOIN pg_menu_usuario menu_usu ON (element_menu.id_elemento_menu = menu_usu.id_elemento_menu)
    WHERE menu_usu.id_usuario = %s
        AND menu_usu.id_empresa = %s
        AND element_menu.id_padre = (SELECT element_menu2.id_elemento_menu FROM pg_elemento_menu element_menu2
                                    WHERE element_menu2.id_padre = (SELECT element_menu3.id_elemento_menu FROM pg_elemento_menu element_menu3
                                                                    WHERE element_menu3.nombre = 'ERP'))
    ORDER BY element_menu.def_order ASC;",
        valTpDato($idUsuario, "int"),
        valTpDato($idEmpresa, "int"));
    $rsMenu2 = mysql_query($queryMenu2);
    if (!$rsMenu2) die(mysql_error()."\n\nLine: ".__LINE__);
    $totalRowsMenu2 = mysql_num_rows($rsMenu2);
    if ($totalRowsMenu2 > 0) {
        echo "<li>";
            echo "<a href=\"#\" class=\"menulink\"><table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\"><tr><td><img src=\"img/erp/resultset_next.png\"></td><td>&nbsp;</td><td width=\"100%\">".utf8_encode("Parámetros")."</td></tr></table></a>";
            echo "<ul>";
            
            while ($rowMenu2 = mysql_fetch_assoc($rsMenu2)) {
                echo "<li><a href=\"".$rowMenu2['modulo'].".php\">".utf8_encode($rowMenu2['nombre'])."</a></li>";
            }
            
            echo "</ul>";
        echo "</li>";
    } ?>
    
    <li><a href="#" class="menulink"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><img src="img/erp/resultset_next.png"></td><td>&nbsp;</td><td width="100%"><?php echo utf8_encode("Sistema"); ?></td></tr></table></a>
		<ul>
        	<?php
        	$queryMenu2 = sprintf("SELECT DISTINCT
				element_menu.id_elemento_menu,
				element_menu.nombre,
				element_menu.modulo
			FROM pg_elemento_menu element_menu
				INNER JOIN pg_menu_usuario menu_usu ON (element_menu.id_elemento_menu = menu_usu.id_elemento_menu)
			WHERE menu_usu.id_usuario = %s
				AND menu_usu.id_empresa = %s
				AND element_menu.id_padre IS NULL
				AND element_menu.nombre <> 'ERP'
			ORDER BY element_menu.def_order ASC;",
				valTpDato($idUsuario, "int"),
				valTpDato($idEmpresa, "int"));
			$rsMenu2 = mysql_query($queryMenu2);
			if (!$rsMenu2) die(mysql_error()."\n\nLine: ".__LINE__);
			$totalRowsMenu2 = mysql_num_rows($rsMenu2);
			while ($rowMenu2 = mysql_fetch_assoc($rsMenu2)) {
				switch ($rowMenu2['modulo']) {
					case "co": $href = "contabilidad/principal.php"; break;
					case "cc": $href = "cxc"; break;
					case "cjrs": $href = "caja_rs"; break;
					case "cj": $href = "caja_vh"; break;
					case "te": $href = "tesoreria"; break;
					case "se": $href = "servicios"; break;
					default : $href = $rowMenu2['modulo']; break;
				}
				echo "<li><a href=\"".$href."\">".utf8_encode($rowMenu2['nombre'])."</a></li>";
			} ?>
		</ul>
	</li>
    
    <li><a href="#" class="menulink"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><img src="img/erp/resultset_next.png"></td><td>&nbsp;</td><td width="100%"><?php echo utf8_encode("Ayuda"); ?></td></tr></table></a>
		<ul>
			<li><a href="ayuda.php">Ayuda</a></li>
			<li><a href="http://soportegotosys.dyndns.org/glpi/" target="_blank">Reportar Incidencia</a></li>
		</ul>
	</li>
    
	<li><a href="index.php" class="menulink"><table cellpadding="0" cellspacing="0" width="100%"><tr><td><img src="img/erp/resultset_next.png"></td><td>&nbsp;</td><td width="100%"><?php echo utf8_encode("Cerrar Sesión"); ?></td></tr></table></a></li>
</ul>
	</td>
</tr>
</table>
<script type="text/javascript">
var menu = new menu.dd("menu");
menu.init("menu","menuhover");
</script>