<?php
set_time_limit(100000);
ini_set('memory_limit', '-1');

require_once ("../../connections/conex.php");

include '../clases/excelXml/excel_xml.php';
$excel = new excel_xml();

$headerStyle = array('bold' => 1, 'size' => '8', 'color' => '#FFFFFF', 'bgcolor' => '#021933');

$trCabecera =  array('bold' => 1, 'size' => '8', 'color' => '#000000');
$trTitulo =  array('bold' => 1, 'size' => '10', 'color' => '#000000');

$trResaltar4 = array('size' => '8', 'bgcolor' => '#FFFFFF');
$trResaltar5 = array('size' => '8', 'bgcolor' => '#D7D7D7');
$trResaltarTotal = array('size' => '8', 'bgcolor' => '#E6FFE6', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');
$trResaltarTotal2 = array('size' => '8', 'bgcolor' => '#DDEEFF', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');
$trResaltarTotal3 = array('size' => '8', 'bgcolor' => '#FFEED5', 'line' => 'Continuous', 'position' => 'Top', 'weight' => '1');

$excel->add_style('header', $headerStyle);
$excel->add_style('trCabecera', $trCabecera);
$excel->add_style('trTitulo', $trTitulo);
$excel->add_style('trResaltar4', $trResaltar4);
$excel->add_style('trResaltar5', $trResaltar5);
$excel->add_style('trResaltarTotal', $trResaltarTotal);
$excel->add_style('trResaltarTotal2', $trResaltarTotal2);
$excel->add_style('trResaltarTotal3', $trResaltarTotal3);

$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);

$idEmpresa = $valCadBusq[0];

$startRow = $pageNum * $maxRows;

// DATOS DE LA EMPRESA
$queryEmp = sprintf("SELECT * FROM pg_empresa
WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp);
if (!$rsEmp) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);


$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
$sqlBusq .= $cond.sprintf("orden.id_estado_orden NOT IN (18,21,24)");

if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_empresa = %s",
		valTpDato($valCadBusq[0], "int"));
}

if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("DATE(tiempo_orden) BETWEEN %s AND %s",
		valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
		valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
}

if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_empleado = %s",
		valTpDato($valCadBusq[3],"int"));
}

if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_tipo_orden = %s",
		valTpDato($valCadBusq[4], "int"));
}

if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("orden.id_estado_orden = %s",
		valTpDato($valCadBusq[5], "int"));
}

if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("((SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
		FROM cj_cc_cliente cliente
		WHERE cliente.id = (IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
							FROM sa_recepcion r
							WHERE r.id_recepcion = orden.id_recepcion ), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																		FROM sa_cita c
																		WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																						FROM sa_recepcion r
																						WHERE r.id_recepcion = orden.id_recepcion))))) LIKE %s
	
	OR orden.numero_orden LIKE %s
	OR recepcion.numeracion_recepcion LIKE %s
	OR nom_uni_bas LIKE %s
	OR placa LIKE %s
	OR chasis LIKE %s)",
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"),
		valTpDato("%".$valCadBusq[6]."%","text"));
}

$queryMaestro = sprintf("SELECT *,
	orden.tiempo_orden,
	orden.id_orden,
	recepcion.id_recepcion,
	orden.id_empresa,
	
	(SELECT nombre_empleado FROM vw_pg_empleados
	WHERE id_empleado = orden.id_empleado) AS nombre_empleado,
	
	tipo_orden.nombre_tipo_orden,
	
	(SELECT CONCAT_WS(' ', cliente.nombre, cliente.apellido)
	FROM cj_cc_cliente cliente
	WHERE cliente.id = (IFNULL((SELECT r.id_cliente_pago AS id_cliente_pago
						FROM sa_recepcion r
						WHERE r.id_recepcion = orden.id_recepcion ), (SELECT c.id_cliente_contacto AS id_cliente_contacto
																	FROM sa_cita c
																	WHERE c.id_cita = (SELECT r.id_cita AS id_cita
																					FROM sa_recepcion r
																					WHERE r.id_recepcion = orden.id_recepcion))))) AS nombre_cliente,
	
	uni_bas.nom_uni_bas,
	placa,
	chasis,																		
	(estado_orden.tipo_estado + 0) AS id_tipo_estado,
	nombre_estado,
	color_estado,
	color_fuente,
	id_orden_retrabajo,
	((((orden.subtotal - orden.subtotal_descuento) * orden.iva) / 100) + (orden.subtotal - orden.subtotal_descuento)) AS total
FROM sa_orden orden
	INNER JOIN sa_recepcion recepcion ON (orden.id_recepcion = recepcion.id_recepcion)
	INNER JOIN sa_cita cita ON (recepcion.id_cita = cita.id_cita)
	INNER JOIN en_registro_placas reg_placas ON (cita.id_registro_placas = reg_placas.id_registro_placas)
	INNER JOIN an_uni_bas uni_bas ON (reg_placas.id_unidad_basica = uni_bas.id_uni_bas)
	INNER JOIN sa_tipo_orden tipo_orden ON (orden.id_tipo_orden = tipo_orden.id_tipo_orden)
	INNER JOIN sa_estado_orden estado_orden ON (orden.id_estado_orden = estado_orden.id_estado_orden)
	LEFT JOIN sa_retrabajo_orden orden_retrabajo ON (orden.id_orden = orden_retrabajo.id_orden) %s
ORDER BY orden.numero_orden DESC", $sqlBusq);
$rsMaestro = mysql_query($queryMaestro);
if (!$rsMaestro) die(mysql_error()."<br><br>Line: ".__LINE__);
$totalRowsMaestro = mysql_num_rows($rsMaestro);
$contFila = 0;
$arrayTotalPagina = NULL;
$arrayPagina = NULL;
while ($rowMaestro = mysql_fetch_assoc($rsMaestro)) {
	$contFila++;
	
	$arrayCol[$contFila][0] = date("d-m-Y",strtotime($rowMaestro['tiempo_orden']));
	$arrayCol[$contFila][1] = ($rowMaestro['numero_orden']);
	$arrayCol[$contFila][2] = ($rowMaestro['numeracion_recepcion']);
	$arrayCol[$contFila][3] = htmlentities($rowMaestro['nombre_empleado'])." ";
	$arrayCol[$contFila][4] = htmlentities($rowMaestro['nombre_tipo_orden']);
	$arrayCol[$contFila][5] = htmlentities($rowMaestro['nombre_cliente']);
	$arrayCol[$contFila][6] = htmlentities($rowMaestro['nom_uni_bas']);
	$arrayCol[$contFila][7] = htmlentities($rowMaestro['placa']);
	$arrayCol[$contFila][8] = htmlentities($rowMaestro['chasis']);
	$arrayCol[$contFila][9] = htmlentities($rowMaestro['nombre_estado']);
	$arrayCol[$contFila][10] = ($rowMaestro['id_orden_retrabajo']);
	$arrayCol[$contFila][11] = $rowMaestro['total'];
	
	// TOTALES
	$arrayTotalPagina[11] += $arrayCol[$contFila][11];
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
		$excel->add_row(array(
			"Fecha de Emisión: ".date("d-m-Y")."  Hora: ".date("H:i:s")."|19"
		), 'trCabecera');
		
		$excel->add_row(array("","","","","","","","","","","","","","","","","","","",""));
		$excel->add_row(array('ORDENES DE SERVICIO'."|19"), 'trTitulo');
		$excel->add_row(array("","","","","","","","","","","","","","","","","","","",""));
		
		// DETALLE ARTICULOS
		$excel->add_row(array(
			'Fecha',
			'N° Orden',
			'N° Recepción',
			'Asesor',
			'Tipo Orden',
			'Cliente',
			'Catálogo',
			'Placa',
			'Chasis',
			'Estado',
			'Ord. Retrabajo',
			'Total'
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
					$valor2[8],
					$valor2[9],
					$valor2[10],
					round($valor2[11],2)
				), $clase);
			}
		}
		
		if (isset($valor[4])) {
			$excel->add_row(array(
				"Total Página:|10",
				round($valor[4][11],2)
			), 'trResaltarTotal');
		}
		
		$excel->create_worksheet($valor[0]);
	}
}

$xml = $excel->generate();

$excel->download('ERP_Orden_Servicio.xls');
?>