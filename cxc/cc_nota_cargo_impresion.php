<?php
require_once ("../connections/conex.php");
$sumMontoExentoIva=0;
$sumMontoIva=0;

$sql="SELECT * FROM cj_cc_notadecargo WHERE idNotaCargo='$_GET[idNotaDeCargo]'";
$consulta=mysql_query($sql,$conexion);
$fila1=mysql_fetch_array($consulta);

echo "<script language='JavaScript'>

		window.location.href= 'cc_formato_nota_cargo.php?valBusq=".$_REQUEST['idNotaDeCargo']."';

	</script>";
?>