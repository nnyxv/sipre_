<?php
include("../../connections/conex.php");

$search = $_POST['search']; // NUMERO DE POSICION
$search2 = $_POST['search2']; // ID EMPRESA
$search3 = $_POST['search3']; // ID INVENTARIO FISICO
$search4 = $_POST['search4']; // CODIGO ARTICULO
$result = array();

// Some simple validation
if (is_string($search) && strlen($search) >= 0 && strlen($search) < 64) {
	$sqlBusq = sprintf(" WHERE id_empresa = %s", $search2);
	
	if (strlen($search) > 0)
		$sqlBusq .= sprintf(" AND numero LIKE '%s'", $search);
	
	if (strlen($search3) > 0)
		$sqlBusq .= sprintf(" AND id_inventario_fisico = %s", $search3);
	
	/*if (strlen($search4) > 0)
		$sqlBusq .= sprintf(" AND codigo_articulo LIKE '%s'", $search4."%");*/
	
	$query = sprintf("SELECT * FROM vw_iv_inventario_fisico_detalle %s", $sqlBusq);
	$rs = mysql_query($query, $conex) or die(mysql_error());
	
	if ($rs) {
		while ($row = mysql_fetch_assoc($rs)) {
			$a[0] = $row['numero'];
			$a[1] = elimCaracter($row['codigo_articulo'],";");
			$a[2] = $row['descripcion'];
			$a[3] = "../repuestos/".$row['foto'];
			$result[] = $a;
			
			//$result[] = $row['codigo_articulo'];
		}
	}
}
// Finally the JSON, including the correct content-type
header('Content-type: application/json');

echo json_encode($result); // see NOTE!
?>