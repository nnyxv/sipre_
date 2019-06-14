<?php
set_time_limit(0);
ini_set('memory_limit', '-1');

require_once ("../connections/conex.php");

include '../clases/excelXml/excel_xml.php';
$excel = new excel_xml();

$headerStyle = array('bold' => 1, 'size' => '8', 'color' => '#FFFFFF', 'bgcolor' => '#021933');

$trCabecera =  array('bold' => 1, 'size' => '8', 'color' => '#000000');

$trResaltar4 = array('size' => '8', 'bgcolor' => '#FFFFFF');
$trResaltar5 = array('size' => '8', 'bgcolor' => '#D7D7D7');
$trResaltarTotal = array('size' => '8', 'bgcolor' => '#E6FFE6', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');
$trResaltarTotal2 = array('size' => '8', 'bgcolor' => '#DDEEFF', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');
$trResaltarTotal3 = array('size' => '8', 'bgcolor' => '#FFEED5', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');

$excel->add_style('header', $headerStyle);
$excel->add_style('trCabecera', $trCabecera);
$excel->add_style('trResaltar4', $trResaltar4);
$excel->add_style('trResaltar5', $trResaltar5);
$excel->add_style('trResaltarTotal', $trResaltarTotal);
$excel->add_style('trResaltarTotal2', $trResaltarTotal2);
$excel->add_style('trResaltarTotal3', $trResaltarTotal3);

$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

//$idEmpresa = $valCadBusq[0];

$startRow = $pageNum * $maxRows;

// DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);


if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("empleado.activo = %s",
		valTpDato($valCadBusq[0], "boolean"));
}

if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cargo.unipersonal = %s",
		valTpDato($valCadBusq[1], "boolean"));
}

if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
	$sqlBusq .= $cond.sprintf("empleado.cedula LIKE %s
		OR CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) LIKE %s
		OR dep.nombre_departamento LIKE %s
		OR cargo.nombre_cargo LIKE %s
		OR usu.nombre_usuario LIKE %s)",
		valTpDato("%".$valCadBusq[2]."%", "text"),
		valTpDato("%".$valCadBusq[2]."%", "text"),
		valTpDato("%".$valCadBusq[2]."%", "text"),
		valTpDato("%".$valCadBusq[2]."%", "text"),
		valTpDato("%".$valCadBusq[2]."%", "text"));
}

$queryMaestro = sprintf("SELECT 
	empleado.id_empleado,
	empleado.cedula,
	empleado.fecha_ingreso,
	CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
	dep.nombre_departamento,
	cargo.nombre_cargo,
	cargo.unipersonal,
	CONCAT_WS(' / ', empleado.telefono, empleado.celular) AS telefono,
	usu.nombre_usuario,
	empleado.activo
FROM pg_empleado empleado
	INNER JOIN pg_cargo_departamento cargo_dep ON (empleado.id_cargo_departamento = cargo_dep.id_cargo_departamento)
	INNER JOIN pg_cargo cargo ON (cargo_dep.id_cargo = cargo.id_cargo)
	INNER JOIN pg_departamento dep ON (cargo_dep.id_departamento = dep.id_departamento)
	LEFT OUTER JOIN pg_usuario usu ON (empleado.id_empleado = usu.id_empleado) %s
ORDER BY nombre_cargo", $sqlBusq);
$rsMaestro = mysql_query($queryMaestro);
if (!$rsMaestro) die(mysql_error()."<br><br>Line: ".__LINE__);
$totalRowsMaestro = mysql_num_rows($rsMaestro);
$contFila = 0;
$arrayTotalPagina = NULL;
$arrayPagina = NULL;
while ($rowMaestro = mysql_fetch_assoc($rsMaestro)) {
	$contFila++;
	
	$imgEstatus = "";
	if ($rowMaestro['activo'] == 0)
		$imgEstatus = "Inactivo";
	else if ($rowMaestro['activo'] == 1)
		$imgEstatus = "Activo";
		
	$imgEstatusCargo = "";
	if ($rowMaestro['unipersonal'] == 1)
		$imgEstatusCargo = "Cargo Unipersonal";
	
	$arrayCol[$contFila][0] = htmlentities($imgEstatus)." ";
	$arrayCol[$contFila][1] = htmlentities($rowMaestro['cedula'])." ";
	$arrayCol[$contFila][2] = htmlentities($rowMaestro['nombre_empleado'])." ";
	$arrayCol[$contFila][3] = date("d-m-Y",strtotime($rowMaestro['fecha_ingreso']));
	$arrayCol[$contFila][4] = htmlentities($rowMaestro['nombre_departamento']);
	$arrayCol[$contFila][5] = htmlentities($rowMaestro['nombre_cargo']);
	$arrayCol[$contFila][6] = htmlentities($imgEstatusCargo)." ";
	$arrayCol[$contFila][7] = htmlentities($rowMaestro['telefono']);
	$arrayCol[$contFila][8] = htmlentities($rowMaestro['nombre_usuario']);
}
	
$arrayPagina[0][0] = "Página 1";
$arrayPagina[0][1] = "";
$arrayPagina[0][2] = "";
$arrayPagina[0][3] = $arrayCol;
$arrayPagina[0][4] = $arrayTotalPagina;
$arrayPagina[0][5] = $array;
$arrayPagina[0][6] = array($totalCantArt, $totalExist, $totalValorExist);


if (isset($arrayPagina)) {
	foreach ($arrayPagina as $indice => $valor) {
		
		$excel->add_row(array("","","","","","","","","","","","","","","","","","","",""));
		
		// DATOS DE LA EMPRESA
		$excel->add_row(array(
			$rowEmp['nombre_empresa']."|19"
		), 'trCabecera');
		$excel->add_row(array(
			"R.I.F.: ".$rowEmp['rif']."|19"
		), 'trCabecera');
		if (strlen($rowEmp['direccion']) > 1) {
			$direcEmpresa = $rowEmp['direccion'].".";
			$telfEmpresa = "";
			if (strlen($rowEmp['telefono1']) > 1) {
				$telfEmpresa .= "Telf.: ".$rowEmp['telefono1'];
			}
			if (strlen($rowEmp['telefono2']) > 1) {
				$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
				$telfEmpresa .= $rowEmp['telefono2'];
			}
			if (strlen($rowEmp['telefono3']) > 1) {
				$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
				$telfEmpresa .= $rowEmp['telefono3'];
			}
			if (strlen($rowEmp['telefono4']) > 1) {
				$telfEmpresa .= (strlen($telfEmpresa) > 0) ? " / " : "Telf.: ";
				$telfEmpresa .= $rowEmp['telefono4'];
			}
			
			$excel->add_row(array(
				$direcEmpresa." ".$telfEmpresa."|19"
			), 'trCabecera');
		}
		$excel->add_row(array(
			$rowEmp['web']."|19"
		), 'trCabecera');
		
		$excel->add_row(array("","","","","","","","","","","","","","","","","","","",""));
		
		// DETALLE ARTICULOS
		$excel->add_row(array(
			'',
			'CI',
			'Empleado',
			'Fecha Ingreso',
			'Departamento',
			'Cargo|1',
			'Teléfono',
			'Usuario'
		), 'header');
		
		
		if (isset($valor[3])) {
			$contFila = 0;
			foreach ($valor[3] as $indice2 => $valor2) {
				$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
				$contFila++;
				
				$excel->add_row(array(
					$valor2[0],
					$valor2[1],
					$valor2[2],
					$valor2[3],
					$valor2[4],
					$valor2[5],
					$valor2[6],
					$valor2[7],
					$valor2[8]
				), $clase);
			}
		}
		
		/*if (isset($valor[4])) {
			$excel->add_row(array(
				"Total Página:|7",
				round($valor[4][8],2),
				round($valor[4][9],2),
				round($valor[4][10],2),
				round($valor[4][11],2)
			), 'trResaltarTotal');
		}*/
		
		$excel->create_worksheet($valor[0]);
	}
}

$xml = $excel->generate();

$excel->download('ERP_Empleados.xls');
?>