<?php

//error_reporting(E_ALL);
//ini_set("display_errors", 1);
require_once ("../../connections/conex.php");

$valForm['lstEmpresa'] = $_GET["lstEmpresa"];
$valForm['txtFechaDesde'] = $_GET["txtFechaDesde"];
$valForm['txtFechaHasta'] = $_GET["txtFechaHasta"];
$valForm['txtIdTempario'] = $_GET["txtIdTempario"];
$valForm['txtIdPaquete'] = $_GET["txtIdPaquete"];

$fecha1 = $valForm['txtFechaDesde'];
$fecha2 = $valForm['txtFechaHasta'];

if($fecha1 == "" || $fecha2 == ""){
     die("debe seleccionar fecha");
}

$fecha_desde = date("Y/m/d",strtotime($fecha1));
$fecha_hasta = date("Y/m/d",strtotime($fecha2));

$valBusq = sprintf("%s|%s|%s|%s|%s",
    $valForm['lstEmpresa'],
    $valForm['txtFechaDesde'],
    $valForm['txtFechaHasta'],
    $valForm['txtIdTempario'],
    $valForm['txtIdPaquete']
);

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
    //$valCadBusq[3] id_tempario
    //$valCadBusq[4] id_paquete

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

    if ($valCadBusq[3] != "" && $valCadBusq[3] != " ") {
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond.sprintf("(SELECT COUNT(*) FROM sa_det_orden_tempario
                                    WHERE sa_det_orden_tempario.id_orden = sa_orden.id_orden AND id_tempario = %s ) > 0",
                valTpDato($valCadBusq[3]),"int",
                valTpDato($valCadBusq[3]),"int");
    }

    if ($valCadBusq[4] != "" && $valCadBusq[4] != " ") {
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond.sprintf("(SELECT COUNT(*) FROM sa_det_orden_tempario
                                    WHERE sa_det_orden_tempario.id_orden = sa_orden.id_orden AND id_paquete = %s ) > 0",
                valTpDato($valCadBusq[4]),"int",
                valTpDato($valCadBusq[4]),"int");
    }

    $query = sprintf("SELECT
            sa_orden.numero_orden,
            sa_tipo_orden.nombre_tipo_orden,
            sa_orden.fecha_factura,
            CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
            cj_cc_cliente.telf,
            cj_cc_cliente.otrotelf,
            cj_cc_cliente.correo,
            cj_cc_cliente.direccion,
            an_modelo.nom_modelo,
            en_registro_placas.placa,
            en_registro_placas.chasis
    FROM sa_orden
            INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)
            INNER JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita
            INNER JOIN en_registro_placas ON sa_cita.id_registro_placas = en_registro_placas.id_registro_placas
            INNER JOIN an_uni_bas ON en_registro_placas.id_unidad_basica = an_uni_bas.id_uni_bas
            INNER JOIN an_modelo ON an_uni_bas.mod_uni_bas = an_modelo.id_modelo
            INNER JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
            INNER JOIN cj_cc_cliente ON cj_cc_cliente.id = sa_orden.id_cliente
            INNER JOIN pg_empleado ON sa_orden.id_empleado = pg_empleado.id_empleado
            INNER JOIN cj_cc_encabezadofactura ON cj_cc_encabezadofactura.numeroPedido = sa_orden.id_orden AND cj_cc_encabezadofactura.idDepartamentoOrigenFactura = 1
            %s", $sqlBusq);

    $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

    $queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
    $rsLimit = mysql_query($queryLimit);
    if (!$rsLimit) { return die(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }

    if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if (!$rs) { return die(mysql_error()."\n\nLine: ".__LINE__); }
            $totalRows = mysql_num_rows($rs);
    }
    
    $htmlTblIni = "<table border=\"1\" width=\"100%\">";
    $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\" >";
            $htmlTh .= "<th>Nro Orden</th>";
            $htmlTh .= "<th>Tipo Orden</th>";
            $htmlTh .= "<th>Fecha Factura</th>";
            $htmlTh .= "<th>Cliente</th>";
            $htmlTh .= "<th>Tlf.</th>";
            $htmlTh .= "<th>Otro Tlf.</th>";
            $htmlTh .= "<th>Correo</th>";
            $htmlTh .= "<th>Direcci&oacute;n</th>";
            $htmlTh .= "<th>Modelo</th>";
            $htmlTh .= "<th>".$spanPlaca."</th>";
            $htmlTh .= "<th>".$spanSerialCarroceria."</th>";
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $contFila++;

            $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
                    $htmlTb .= "<td align=\"center\" title=\"id orden: ".($row['id_orden'])." id recepcion: ".($row['id_recepcion'])."\">".($row['numero_orden'])."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['nombre_tipo_orden'])."</td>";
                    $htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['fecha_factura']))."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['nombre_cliente'])."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['otrotelf']."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['correo']."</td>";
                    $htmlTb .= "<td align=\"center\">".(str_replace(";"," ",$row['direccion']))."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['nom_modelo'])."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['placa'])."</td>";
                    $htmlTb .= "<td align=\"center\">".($row['chasis'])."</td>";
            $htmlTb .= "</tr>";

    }

    echo ($htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
    
}



?>