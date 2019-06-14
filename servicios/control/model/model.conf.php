<?php
if(file_exists("../connections/conex.php")){//Solo servicios
	include("../connections/conex.php");
}elseif(file_exists("../../connections/conex.php")){//reporte final de servicios en carpeta /reportes
        include("../../connections/conex.php");
}else{
    die("NO SE PUDO INCLUIR conex.php EN control/model/model.conf.php");
}

define("connectHOST",$hostname_conex);
define("connectBASENAME",$database_conex);
define("connectUSER",$username_conex);
define("connectPASSWORD",$password_conex);
?>