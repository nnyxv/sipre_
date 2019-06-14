<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_conex = "localhost";
$database_conex = "sipre_automotriz";
$username_conex = "sipre_system";
$password_conex = "s1pr3";
$conex = mysql_connect($hostname_conex, $username_conex, $password_conex) or trigger_error(mysql_error(),E_USER_ERROR); 
mysql_select_db($database_conex, $conex);

define("DBASE_SIPRE_AUT", $database_conex);
define("DUSER_SIPRE_AUT", $username_conex);
define("DPASW_SIPRE_AUT", $password_conex);

define("DBASE_CONTAB", "sipre_contabilidad");
define("DUSER_CONTAB", "");
define("DPASW_CONTAB", "");

define("DBASE_SIGSO", "solicitud_pedido");

define("DBASE_PROV", "proveedores");
define("cVERSION", "3.0");

$_SESSION['database_conex'] = $database_conex;

mysql_query("SET NAMES 'latin1';", $conex);

include("inc_funciones.php");
include("inc_idioma.php");
?>