<?php
include("../../connections/conex.php");

$search = $_POST['search']; // NUMERO DE POSICION
$search2 = $_POST['search2']; // ID EMPRESA
$search4 = $_POST['search4']; // CODIGO ARTICULO
$result = array();

// Some simple validation
if (is_string($search) && strlen($search) >= 0 && strlen($search) < 64) {
	if (strlen($search) > 0)
		$sqlBusq .= sprintf("WHERE codigo_articulo LIKE '%s'", "%".$search."%s");
	
	$query = sprintf("SELECT * FROM iv_articulos %s", $sqlBusq);
	$rs = mysql_query($query, $conex) or die(mysql_error());
	
	if ($rs) {
		while ($row = mysql_fetch_assoc($rs)) {
			/*$a[0] = elimCaracter($row['codigo_articulo'],";");
			$a[2] = $row['descripcion'];
			$a[1] = "";
			$a[3] = "../repuestos/".$row['foto'];
			$result[] = $a;*/
			
			$result[] = elimCaracter($row['codigo_articulo'],";");
		}
	}
}
// Finally the JSON, including the correct content-type
header('Content-type: application/json');

echo json_encode($result); // see NOTE!
?>