<?php  session_start();
include_once('FuncionesPHP.php');
/* para verificar si es comporbante de cierre */
 $con = ConectarBD();
   $SqlStr='update sipre_co_config.usuario set conectado =0';
   $exc = EjecutarExec($con,$SqlStr) or die($SqlStr);
?>