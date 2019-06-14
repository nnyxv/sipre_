<?php
require_once("../connections/conex.php");

require_once("../inc_sesion.php");

//leyendo los datos del presupuesto:
//PENDIENTE: validar si se puede eliminar el presupuesto, cuando se defina bien la estructura de las tablas y su integracion. ------------------
$idpresupuesto = $_GET['id'];
conectar();
$sql = "delete from an_presupuesto where id_presupuesto=".$idpresupuesto.";";
$r = mysql_query($sql,$conex);
cerrar();
echo '<script language="javascript" type="text/javascript"> alert("Se ha eliminado el presupuesto"); window.location.href="an_presupuesto_venta_list.php"; </script>';
?>