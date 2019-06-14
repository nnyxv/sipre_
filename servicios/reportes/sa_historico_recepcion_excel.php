<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
require_once ("../../connections/conex.php");

$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$fechaDesde = strtotime($valCadBusq[1]);
$fechaHasta = strtotime($valCadBusq[2]);

if($fechaDesde == "" || $fechaHasta == ""){
     die("debe seleccionar fecha");
}

if ($fechaDesde > $fechaHasta){
    die("La primera fecha no debe ser mayor a la segunda");
}else{

    header('Content-type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=archivo.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    
   listado(0, "id_recepcion", "DESC", $valBusq);
}


function listado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 0, $totalRows = NULL) {
		
	global $spanKilometraje;
	global $spanPlaca;
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if($valCadBusq[0] == ""){
		$valCadBusq[0] = $_SESSION['idEmpresaUsuarioSysGts'];
	}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("recep.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("recep.fecha_entrada BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("recep.id_empleado_servicio = %s",
			valTpDato($valCadBusq[3],"int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(IF(id_cliente_pago IS NULL, 
					CONCAT_WS(' ', recep.apellido, recep.nombre), 
					CONCAT_WS(' ', recep.apellido_pago, recep.nombre_pago)) LIKE %s
		OR recep.numeracion_recepcion LIKE %s
		OR recep.placa LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%","text"),
			valTpDato("%".$valCadBusq[4]."%","text"),
			valTpDato("%".$valCadBusq[4]."%","text"));
	}
	
	$query = sprintf("SELECT 
				recep.id_recepcion,
				pg_empresa.nombre_empresa,
				recep.id_cita,
				recep.fecha_entrada,
				recep.hora_entrada,
				recep.placa,
				recep.numeracion_recepcion,
				recep.numero_entrada,
				recep.descripcion,
				recep.observaciones,
				recep.asesor,
				recep.nro_llaves,
				(SELECT COUNT(*) FROM sa_recepcion_incidencia foto WHERE foto.id_cita = recep.id_cita AND foto.url_foto != '') AS nro_fotos,
				(SELECT COUNT(*) FROM sa_recepcion_incidencia foto WHERE foto.id_cita = recep.id_cita) AS nro_incidencias,
				(SELECT COUNT(*) FROM sa_recepcion_inventario inv WHERE inv.id_cita = recep.id_cita) AS nro_inventario,
				(SELECT COUNT(*) FROM sa_recepcion_falla falla WHERE falla.id_recepcion = recep.id_recepcion) AS nro_fallas,
				CONCAT_WS(' ', recep.apellido, recep.nombre) AS nombre_cliente_cita,
				CONCAT_WS(' ', recep.apellido_pago, recep.nombre_pago) AS nombre_cliente_pago,
				IF(id_cliente_pago IS NULL, 
					CONCAT_WS(' ', recep.apellido, recep.nombre), 
					CONCAT_WS(' ', recep.apellido_pago, recep.nombre_pago)) AS nombre_cliente
	FROM sa_v_historico_recepcion recep 
	INNER JOIN pg_empresa ON recep.id_empresa = pg_empresa.id_empresa
	%s", $sqlBusq);
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s ", $query, $sqlOrd);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return die($queryLimit."\n".mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return die(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"1\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<th>Empresa</th>";
		$htmlTh .= "<th>Fecha</th>";
		$htmlTh .= "<th>Hora</th>";
		$htmlTh .= "<th>".$spanPlaca."</th>";
		$htmlTh .= "<th>Nro Recep.</th>";
		$htmlTh .= "<th>Nro Ent.</th>";
		$htmlTh .= "<th>Tipo Vale</th>";
		$htmlTh .= "<th>Cliente</th>";
		$htmlTh .= "<th>Asesor</th>";
		$htmlTh .= "<th>Observaciones</th>";
		$htmlTh .= "<th>Nro Llaves</th>";
		$htmlTh .= "<th>Total Fallas</th>";
		$htmlTh .= "<th>Total Inventario</th>";
		$htmlTh .= "<th>Total Incidencias</th>";
		$htmlTh .= "<th>Total Fotos</th>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
			$htmlTb .= "<td align=\"left\">".($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_entrada']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("H:i a",strtotime($row['hora_entrada']))."</td>";
			$htmlTb .= "<td align=\"center\">".($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\" title =\"id_recepcion: ".$row['id_recepcion']." id_cita: ".$row['id_cita']."\">".$row['numeracion_recepcion']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_entrada']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['descripcion']."</td>";
			$htmlTb .= "<td align=\"center\">".($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['asesor'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['observaciones'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['nro_llaves'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['nro_fallas']."</td>";	
			$htmlTb .= "<td align=\"center\">".$row['nro_inventario']."</td>";								
			$htmlTb .= "<td align=\"center\">".$row['nro_incidencias']."</td>";	
			$htmlTb .= "<td align=\"center\">".$row['nro_fotos']."</td>";
					
			
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTblFin .= "</table>";
	
	
	echo $htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;
		
}



?>