<?php
if(file_exists("connections/conex.php")){
	include("connections/conex.php");//ERP
}else{
	if(file_exists("../connections/conex.php")){
		include("../connections/conex.php");//COMPRAS
	}else{
		die("NO SE PUDO INCLUIR conex.php EN control/model/model.conf.php");
	}
}

define("connectHOST",$hostname_conex);
define("connectBASENAME",$database_conex);
define("connectUSER",$username_conex);
define("connectPASSWORD",$password_conex);
?>