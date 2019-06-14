<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
require_once ("../../connections/conex.php");

$valBusq = $_GET["valBusq"];

$valCadBusq = explode("|", $valBusq);

$fechaDesde = strtotime($valCadBusq[4]);
$fechaHasta = strtotime($valCadBusq[5]);

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
    
   listadoUnidadFisica(0,'hora_inicio_cita','ASC',$valBusq);
}

function listadoUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = "", $totalRows = NULL) {
		
	global $spanCI;
	global $spanRIF;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	if($valCadBusq[0] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf(" (CONCAT_WS(' ',nombre,apellido) LIKE %s 
                                       OR cedula_cliente LIKE %s
                                       OR placa LIKE %s
                                       )",
            valTpDato("%".$valCadBusq[0]."%", "text"),
            valTpDato("%".$valCadBusq[0]."%", "text"),
            valTpDato("%".$valCadBusq[0]."%", "text")
            );
	}
	
        if ($valCadBusq[1] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .=  $cond." vw_sa_consulta_citas.id_empresa = '".$valCadBusq[1]."'";		
        }
        
	if ($valCadBusq[2] != 0){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."id_empleado_servicio = '".$valCadBusq[2]."'";
        }
        
        if ($valCadBusq[3] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."estado_cita = '".$valCadBusq[3]."' ";
        }
        
        if ($valCadBusq[4] != '' && $valCadBusq[5] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf(" fecha_cita BETWEEN %s AND %s",
            valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "text"),
            valTpDato(date("Y-m-d",strtotime($valCadBusq[5])), "text")
            );
        }
		
	$query = sprintf("SELECT vw_sa_consulta_citas.*, pg_empresa.nombre_empresa FROM vw_sa_consulta_citas
                        LEFT JOIN pg_empresa ON vw_sa_consulta_citas.id_empresa = pg_empresa.id_empresa
                        %s",$sqlBusq);
        	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s", $query, $sqlOrd);
	
	$rsLimit = mysql_query($queryLimit);       
	if(!$rsLimit){ return die("Error: ".mysql_error()."\n\nNro Error: ".mysql_errno()."\n\nLinea: ".__LINE__."\n\nQuery: ".$queryLimit); }
	
	if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if(!$rs){ return die("Error: ".mysql_error()."\n\nNro Error: ".mysql_errno()."\n\nLinea: ".__LINE__."\n\nQuery: ".$query); }
            $totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"1\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
            $htmlTh .= "<th>Empresa Sucursal</th>";
            $htmlTh .= "<th>Fecha</th>";
            $htmlTh .= "<th>Hora</th>";
            $htmlTh .= "<th>Diferencia</th>";
            $htmlTh .= "<th>Estado Cita</th>";
            $htmlTh .= "<th>".$spanCI.' - '.$spanRIF."</th>";
            $htmlTh .= "<th>Nombre Cliente</th>";
            $htmlTh .= "<th>Tel&eacute;fono</th>";
            $htmlTh .= "<th>Placa</th>";
            $htmlTh .= "<th>Modelo</th>";
			$htmlTh .= "<th>Motivo de la Visita</th>";
            $htmlTh .= "<th>Asesor</th>";         
         $htmlTh .= "</tr>";
		
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar7" : "trResaltar4";
                $contFila++;
				
		if($row["selecciono_fecha"] == 0){// 1 la escogio el cliente, 0 la otorgo citas y por lo tanto se contabiliza esa
                    $diferenciaFechaTexto = $row['diferencia_fecha'];	
		}else{
                    $diferenciaFechaTexto = "-";	
		}
		
		$htmlTb.= "<tr class=\"".$clase."\" title=\"id_cita:".$row['id_cita']."\">";
			$htmlTb .= "<td align=\"center\">".($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_cita']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['hora_inicio_cita_12']."</td>";
			$htmlTb .= "<td align=\"center\">".$diferenciaFechaTexto."</td>";
			$htmlTb .= "<td align=\"center\">".$row['estado_cita']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cedula_cliente']."</td>";
			$htmlTb .= "<td align=\"center\">".($row['nombre'])." ".($row['apellido'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['placa']."</td>";
			$htmlTb .= "<td align=\"center\">".($row['nom_modelo'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['descripcion_submotivo'])."</td>";
			$htmlTb .= "<td align=\"center\">".($row['asesor'])."</td>";
		$htmlTb .= "</tr>";
		
		if($row['origen_cita'] == 'PROGRAMADA' && $row['estado_cita'] != 'CANCELADA' && $row['estado_cita'] != 'POSPUESTA'){
			$cont++;//TOTAL CITAS PROGRAMADA
			if($row["selecciono_fecha"] == 0){ $diferenciaFechas += $row['diferencia_fecha']; }
		}
		
		if($row['origen_cita'] == 'ENTRADA' && $row['estado_cita'] != 'CANCELADA' && $row['estado_cita'] != 'POSPUESTA'){
			$contAux++;//TOTAL CITAS ENTRADA
			if($row["selecciono_fecha"] == 0){ $diferenciaFechas += $row['diferencia_fecha']; }
		}
	}
	
	$htmlTblFin .= "</table>";
		
	echo $htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;
	

	$htmlResumen .= "<br><table align=\"center\" class=\"tabla\" border=\"1\" width = \"500\">";
	$htmlResumen .= "<tr class=\"tituloColumna\">";
		$htmlResumen .= "<th colspan=\"2\">Total Resumen del Dia</th>";	
	$htmlResumen .= "</tr>";
	$htmlResumen .= "<tr>";
		$htmlResumen .= "<td width = \"250\" class=\"tituloCampo\"><strong>Total Entrada</strong></td>";
		$htmlResumen .= "<td>".number_format($contAux,0,".",",")."</td>";
	$htmlResumen .= "</tr>";
	$htmlResumen .= "<tr>";
		$htmlResumen .= "<td width = \"250\" class=\"tituloCampo\"><strong>Total Dias de Diferencia</strong></td>";
		$htmlResumen .= "<td>".number_format($diferenciaFechas,0,".",",")."</td>";
	$htmlResumen .= "</tr>";
	$htmlResumen .= "<tr>";
		$htmlResumen .= "<td width = \"250\" class=\"tituloCampo\"><strong>Total Citas</strong></td>";
		$htmlResumen .= "<td>".number_format($cont,0,".",",")."</td>";
	$htmlResumen .= "</tr>";
	$htmlResumen .= "</table>";
	
	echo $htmlResumen;
	
}

?>