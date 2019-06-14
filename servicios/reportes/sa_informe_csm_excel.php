<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1); 
require_once ("../../connections/conex.php");

$valForm['lstEmpresa'] = $_GET["lstEmpresa"];
$valForm['txtFechaDesde'] = $_GET["txtFechaDesde"];
$valForm['txtFechaHasta'] = $_GET["txtFechaHasta"];

$fecha1 = $valForm['txtFechaDesde'];
$fecha2 = $valForm['txtFechaHasta'];

if($fecha1 == "" || $fecha2 == ""){
     die("debe seleccionar fecha");
}

$fecha_desde = date("Y/m/d",strtotime($fecha1));
$fecha_hasta = date("Y/m/d",strtotime($fecha2));

$valBusq = sprintf("%s|%s|%s",
            $valForm['lstEmpresa'],
            $valForm['txtFechaDesde'],
            $valForm['txtFechaHasta']);

if ($fecha_desde > $fecha_hasta){
    die("La primera fecha no debe ser mayor a la segunda");
}else{
    header('Content-type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=archivo.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    
   listadoOrdenes('0','','ASC',$valBusq);
}


function listadoOrdenes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	
    global $spanPlaca;
    global $spanSerialCarroceria;

    $valCadBusq = explode("|", $valBusq);
    $startRow = $pageNum * $maxRows;

    //$valCadBusq[0] id empresa
    //$valCadBusq[1] fecha desde
    //$valCadBusq[2] fecha hasta

    //$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
    //$sqlBusq .= $cond.sprintf("orden.id_estado_orden NOT IN (18,21,24)");

    if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("sa_orden.id_empresa = %s",
                    valTpDato($valCadBusq[0], "int"));
    }

    if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("DATE(sa_orden.fecha_factura) BETWEEN %s AND %s",
                    valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
                    valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
    }

    $query = sprintf("SELECT
            pg_empresa.codigo_dealer,
            pg_empresa.nombre_empresa,
            sa_orden.tiempo_orden,
            an_modelo.nom_modelo,                
            cj_cc_cliente.telf,
            cj_cc_cliente.otrotelf,
            cj_cc_cliente.correo,
            CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,

            IF((SELECT COUNT(sa_recepcion.id_recepcion) FROM sa_cita cita
                INNER JOIN sa_recepcion ON cita.id_cita = sa_recepcion.id_cita
                WHERE cita.id_cliente_contacto = sa_cita.id_cliente_contacto) = 1, 'SI','NO'
            ) AS primera_visita,

            sa_orden.numero_orden,
            sa_tipo_orden.nombre_tipo_orden,
            '' AS modelo_dos_digitos,
            an_ano.nom_ano,
            CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_empleado_orden,

            (SELECT CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) 
                    FROM sa_det_orden_tempario
                    INNER JOIN sa_mecanicos ON sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico
                    INNER JOIN pg_empleado ON sa_mecanicos.id_empleado = pg_empleado.id_empleado
                    WHERE sa_det_orden_tempario.id_orden = sa_orden.id_orden LIMIT 1
            ) AS nombre_tecnico,

            en_registro_placas.placa,
            en_registro_placas.chasis,

            cj_cc_cliente.direccion,
            cj_cc_cliente.ciudad,
            '' AS estado,
            cj_cc_cliente.estado AS zip_code

    FROM sa_orden
            INNER JOIN pg_empresa ON sa_orden.id_empresa = pg_empresa.id_empresa
            INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)                
            INNER JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita                
            INNER JOIN en_registro_placas ON sa_cita.id_registro_placas = en_registro_placas.id_registro_placas
            INNER JOIN an_uni_bas ON en_registro_placas.id_unidad_basica = an_uni_bas.id_uni_bas
            INNER JOIN an_modelo ON an_uni_bas.mod_uni_bas = an_modelo.id_modelo
            INNER JOIN an_ano ON an_uni_bas.ano_uni_bas = an_ano.id_ano
            INNER JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
            INNER JOIN cj_cc_cliente ON cj_cc_cliente.id = sa_cita.id_cliente_contacto
            INNER JOIN pg_empleado ON sa_orden.id_empleado = pg_empleado.id_empleado
            INNER JOIN cj_cc_encabezadofactura ON cj_cc_encabezadofactura.numeroPedido = sa_orden.id_orden AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura = 1
            %s", $sqlBusq); 

    $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

    $queryLimit = sprintf("%s %s ####LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
    $rsLimit = mysql_query($queryLimit);
    if (!$rsLimit) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }

    if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if (!$rs) { return die(mysql_error()."\n\nLine: ".__LINE__); }
            $totalRows = mysql_num_rows($rs);
    }
    
    $htmlTblIni = "<table border=\"1\" width=\"100%\">";
    $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\" >";
            $htmlTh .= "<th>Dealer ID</th>";
            $htmlTh .= "<th>Empresa</th>";
            $htmlTh .= "<th>Fecha Orden</th>";
            $htmlTh .= "<th>Modelo</th>";
            $htmlTh .= "<th>Tlf.</th>";
            $htmlTh .= "<th>Otro Tlf.</th>";
            $htmlTh .= "<th>Correo</th>";
            $htmlTh .= "<th>Cliente</th>";
            $htmlTh .= "<th>Primera Visita</th>";
            $htmlTh .= "<th>Nro Orden</th>";
            $htmlTh .= "<th>Tipo Orden</th>";
            $htmlTh .= "<th>Mod 2 dig</th>";
            $htmlTh .= "<th>A&ntilde;o</th>";
            $htmlTh .= "<th>Asesor</th>";
            $htmlTh .= "<th>T&eacute;cnico</th>";
            $htmlTh .= "<th>".$spanPlaca."</th>";
            $htmlTh .= "<th>".$spanSerialCarroceria."</th>";
            $htmlTh .= "<th>Direcci&oacute;n</th>";
            $htmlTh .= "<th>Ciudad</th>";
            $htmlTh .= "<th>Estado</th>";
            $htmlTh .= "<th>Zip code</th>";		
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $contFila++;

            $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
                    $htmlTb .= "<td align=\"center\">".$row['codigo_dealer']."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['nombre_empresa'])."</td>";
                    $htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['tiempo_orden']))."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['nom_modelo'])."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['otrotelf']."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['correo']."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['nombre_cliente'])."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['primera_visita']."</td>";
                    $htmlTb .= "<td align=\"center\" title=\"id orden: ".($row['id_orden'])." id recepcion: ".($row['id_recepcion'])."\">".($row['numero_orden'])."</td>";			
                    $htmlTb .= "<td align=\"center\">".($row['nombre_tipo_orden'])."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['modelo_dos_digitos'])."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['nom_ano']."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['nombre_empleado_orden'])."</td>";                        
                    $htmlTb .= "<td align=\"center\">".($row['nombre_tecnico'])."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['placa'])."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['chasis'])."</td>";
                    $htmlTb .= "<td align=\"center\">".(str_replace(";"," ",$row['direccion']))."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['ciudad'])."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['estado'])."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['zip_code'])."</td>";
            $htmlTb .= "</tr>";

    }

    echo ($htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
    
}



?>