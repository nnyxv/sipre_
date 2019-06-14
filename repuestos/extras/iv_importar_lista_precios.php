<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Documento sin t&iacute;tulo</title>
</head>

<body>
<?php
echo utf8_decode("Ã‘");
?>
<!--<table border="1" width="100%">
<tr align="center">
	<td><b>COD. PROVEEDOR</b></td>
    <td><b>PREFIJO</b></td>
    <td><b>BASICO</b></td>
    <td><b>SUFIJO</b></td>
    <td><b>DESCRIPCION</b></td>
    <td><b>UNIDAD</b></td>
    <td>PRECIO PAQUETE</td>
    <td><b>COSTO</b></td>
    <td>PREFIJO</td>
    <td>BASE</td>
    <td>SUFIJO</td>
    <td>TIPO LINEA</td>
    <td>CATEGORIA INVENTARIO</td>
</tr>
<?php
$cadena = "25600001AYFS          22CJ    53        BUJIA 1.6L 2000   1           0        2278                        A1";
echo "<tr align=\"center\">";
	echo "<td>".substr($cadena, 0, 8)."</td>";
	echo "<td>".substr($cadena, 8, 8)."</td>";
	echo "<td>".substr($cadena, 16, 8)."</td>";
	echo "<td>".substr($cadena, 24, 6)."</td>";
	echo "<td>".substr($cadena, 40, 16)."</td>";
	echo "<td>".substr($cadena, 56, 3)."</td>";
	echo "<td>".substr($cadena, 59, 12)."</td>";
	echo "<td>".substr($cadena, 71, 12)."</td>";
	
	echo "<td>".substr($cadena, 83, 8)."</td>";
	echo "<td>".substr($cadena, 91, 8)."</td>";
	echo "<td>".substr($cadena, 99, 6)."</td>";
	echo "<td>".substr($cadena, 105, 2)."</td>";
	echo "<td>".substr($cadena, 107, 2)."</td>";
echo "</tr>";
?>
</table>-->
</body>
</html>
