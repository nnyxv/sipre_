<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1); 
@session_start();

include("../connections/conex.php");

/* Validación del Módulo */
include('../inc_sesion.php');

/*if(!(validaAcceso("sa_informe_clientes_revisiones"))) {
	echo "
	<script>
		alert('Acceso Denegado');
		window.location.href = 'index.php';
	</script>";
*/

/* Fin Validación del Módulo */

@require('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', 'controladores/xajax/');

require("controladores/ac_iv_general.php");

$xajax->registerFunction("buscarOrdenes");
$xajax->registerFunction("listadoOrdenes");
$xajax->registerFunction("buscarTempario");
$xajax->registerFunction("listadoTempario");
$xajax->registerFunction("asignarTempario");
$xajax->registerFunction("buscarPaquete");
$xajax->registerFunction("listadoPaquete");
$xajax->registerFunction("asignarPaquete");

$xajax->processRequest();

function buscarOrdenes($valForm) {
    $objResponse = new xajaxResponse();

    $fecha1 = $valForm['txtFechaDesde'];
    $fecha2 = $valForm['txtFechaHasta'];
    
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
        $objResponse->script('alert("La primera fecha no debe ser mayor a la segunda")');
    }else{
       $objResponse->loadCommands(listadoOrdenes('0','','ASC',$valBusq));
    }
    
    return $objResponse;
}

function listadoOrdenes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	
    global $spanPlaca;
    global $spanSerialCarroceria;

    $objResponse = new xajaxResponse();	       

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
    if (!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }

    if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
            $totalRows = mysql_num_rows($rs);
    }
    $totalPages = ceil($totalRows/$maxRows)-1;

    $htmlTblIni = "<table border=\"0\" width=\"100%\">";
    $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\" >";
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro Orden"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Orden"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "fecha_factura", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Factura"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tlf."));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "otrotelf", $campOrd, $tpOrd, $valBusq, $maxRows, ("Otro Tlf."));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "correo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Correo"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "direccion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Direcci&oacute;n"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Modelo"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanPlaca));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanSerialCarroceria));
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $contFila++;

            $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
                    $htmlTb .= "<td align=\"center\" title=\"id orden: ".($row['id_orden'])." id recepcion: ".($row['id_recepcion'])."\">".($row['numero_orden'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
                    $htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['fecha_factura']))."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_cliente'])."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['otrotelf']."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['correo']."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['direccion'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['nom_modelo'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['chasis'])."</td>";
            $htmlTb .= "</tr>";

    }

    $htmlTf = "<tr>";
            $htmlTf .= "<td align=\"center\" colspan=\"30\">";
                    $htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
                    $htmlTf .= "<tr class=\"tituloCampo\">";
                            $htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
                                    $htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
                                            $contFila,
                                            $totalRows);
                                    $htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
                                    $htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
                                    $htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
                            $htmlTf .= "</td>";
                            $htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
                                    $htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
                                    $htmlTf .= "<tr align=\"center\">";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum > 0) {
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum > 0) { 
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"100\">";

                                                    $htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s)\">",
                                                            "this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
                                                    for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
                                                                    $htmlTf.="<option value=\"".$nroPag."\"";
                                                                    if ($pageNum == $nroPag) {
                                                                            $htmlTf.="selected=\"selected\"";
                                                                    }
                                                                    $htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
                                                    }
                                                    $htmlTf .= "</select>";

                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum < $totalPages) {
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum < $totalPages) {
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoOrdenes(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            $totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                    $htmlTf .= "</tr>";
                                    $htmlTf .= "</table>";
                            $htmlTf .= "</td>";
                    $htmlTf .= "</tr>";
                    $htmlTf .= "</table>";
            $htmlTf .= "</td>";
    $htmlTf .= "</tr>";

    $htmlTblFin .= "</table>";

    if (!($totalRows > 0)) {
            $htmlTb .= "<td colspan=\"30\">";
                    $htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
                    $htmlTb .= "<tr>";
                            $htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
                            $htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
                    $htmlTb .= "</tr>";
                    $htmlTb .= "</table>";
            $htmlTb .= "</td>";
    }

    $objResponse->assign("divListaOrdenes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

    return $objResponse;
}

function buscarTempario($valForm){
    $objResponse = new xajaxResponse();

    $valBusq = sprintf("%s",
        $valForm['txtCriterioTemp']);

    $objResponse->script("xajax_listadoTempario('0','','','".$valBusq."');");

    return $objResponse;
}

function listadoTempario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
    $objResponse = new xajaxResponse();

    $valCadBusq = explode("|", $valBusq);

    $startRow = $pageNum * $maxRows;

    if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
        $cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
        $sqlBusq .= $cond.sprintf("codigo_tempario LIKE %s
		OR descripcion_tempario LIKE %s)",
                valTpDato("%".$valCadBusq[0]."%","text"),
                valTpDato("%".$valCadBusq[0]."%","text"));
    }

    $query = sprintf("SELECT
                            id_tempario,
                            codigo_tempario,
                            descripcion_tempario
                        FROM sa_tempario %s",
                        $sqlBusq);

    $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
    $queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
    $rsLimit = mysql_query($queryLimit);
    if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\nquery:".$queryLimit);
    if ($totalRows == NULL) {
        $rs = mysql_query($query);
        if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $totalRows = mysql_num_rows($rs);
    }
    $totalPages = ceil($totalRows/$maxRows)-1;

    $htmlTblIni = "<table border=\"0\" width=\"100%\">";
    $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";


    $htmlTh .= "<td width='1%'></td>";
    $htmlTh .= ordenarCampo("xajax_listadoTempario", "30%", $pageNum, "codigo_tempario", $campOrd, $tpOrd, $valBusq, $maxRows, ("C&oacute;digo"));
    $htmlTh .= ordenarCampo("xajax_listadoTempario", "70%", $pageNum, "descripcion_tempario", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripci&oacute;n"));
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
        $clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
        $contFila++;

        $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
        $htmlTb .= "<td> <button type=\"button\" onclick=\"xajax_asignarTempario('".$row['id_tempario']."');\" title=\"Seleccionar Tempario\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button></td>";
        $htmlTb .= "<td align='left'>".$row['codigo_tempario']."</td>";
        $htmlTb .= "<td align='left'>".utf8_encode($row['descripcion_tempario'])."</td>";
        $htmlTb .= "</tr>";
    }

    $htmlTf = "<tr>";
    $htmlTf .= "<td align=\"center\" colspan=\"10\">";
    $htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
    $htmlTf .= "<tr class=\"tituloCampo\">";
    $htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
    $htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
        $contFila,
        $totalRows);
    $htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
    $htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
    $htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
    $htmlTf .= "</td>";
    $htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
    $htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
    $htmlTf .= "<tr align=\"center\">";
    $htmlTf .= "<td width=\"25\">";
    if ($pageNum > 0) {
        $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
            0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
    }
    $htmlTf .= "</td>";
    $htmlTf .= "<td width=\"25\">";
    if ($pageNum > 0) {
        $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
            max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
    }
    $htmlTf .= "</td>";
    $htmlTf .= "<td width=\"100\">";

    $htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoTempario(%s,'%s','%s','%s',%s)\">",
        "this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
    for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
        $htmlTf.="<option value=\"".$nroPag."\"";
        if ($pageNum == $nroPag) {
            $htmlTf.="selected=\"selected\"";
        }
        $htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
    }
    $htmlTf .= "</select>";

    $htmlTf .= "</td>";
    $htmlTf .= "<td width=\"25\">";
    if ($pageNum < $totalPages) {
        $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
            min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
    }
    $htmlTf .= "</td>";
    $htmlTf .= "<td width=\"25\">";
    if ($pageNum < $totalPages) {
        $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
            $totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
    }
    $htmlTf .= "</td>";
    $htmlTf .= "</tr>";
    $htmlTf .= "</table>";
    $htmlTf .= "</td>";
    $htmlTf .= "</tr>";
    $htmlTf .= "</table>";
    $htmlTf .= "</td>";
    $htmlTf .= "</tr>";

    $htmlTblFin .= "</table>";

    if (!($totalRows > 0)) {
        $htmlTb .= "<td colspan=\"10\">";
        $htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
        $htmlTb .= "<tr>";
        $htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
        $htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
        $htmlTb .= "</tr>";
        $htmlTb .= "</table>";
        $htmlTb .= "</td>";
    }

    $objResponse->assign("tdListadoTemparioPorUnidad","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

    $objResponse->script("$('#divFlotante').show();
                            centrarDiv(byId('divFlotante'));");

    return $objResponse;
}

function asignarTempario($idTempario){
    $objResponse = new xajaxResponse();

    $query = sprintf("SELECT
                            id_tempario,
                            codigo_tempario,
                            descripcion_tempario
                        FROM sa_tempario WHERE id_tempario = %s LIMIT 1",
        $idTempario);

    $rs = mysql_query($query);
    if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\nquery:".$query);

    $row = mysql_fetch_assoc($rs);

    $objResponse->assign("txtCodigoTempario","value",utf8_encode($row["codigo_tempario"]));
    $objResponse->assign("txtDescripcionTempario","value",utf8_encode($row["descripcion_tempario"]));
    $objResponse->assign("txtIdTempario","value",$row["id_tempario"]);

    $objResponse->script("$('#divFlotante').hide();
                            byId('btnBuscar').click();
    ");

    return $objResponse;
}

function buscarPaquete($valForm){
    $objResponse = new xajaxResponse();

    $valBusq = sprintf("%s",
        $valForm['txtCriterioPaq']);

    $objResponse->script("xajax_listadoPaquete('0','','','".$valBusq."');");

    return $objResponse;
}

function listadoPaquete($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
    $objResponse = new xajaxResponse();

    $valCadBusq = explode("|", $valBusq);

    $startRow = $pageNum * $maxRows;

    if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
        $cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
        $sqlBusq .= $cond.sprintf("codigo_paquete LIKE %s
		OR descripcion_paquete LIKE %s)",
                valTpDato("%".$valCadBusq[0]."%","text"),
                valTpDato("%".$valCadBusq[0]."%","text"));
    }

    $query = sprintf("SELECT
                             id_paquete,
                            codigo_paquete,
                            descripcion_paquete
                        FROM sa_paquetes %s",
        $sqlBusq);

    $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
    $queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
    $rsLimit = mysql_query($queryLimit);
    if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\nquery:".$queryLimit);
    if ($totalRows == NULL) {
        $rs = mysql_query($query);
        if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $totalRows = mysql_num_rows($rs);
    }
    $totalPages = ceil($totalRows/$maxRows)-1;

    $htmlTblIni = "<table border=\"0\" width=\"100%\">";
    $htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";


    $htmlTh .= "<td width='1%'></td>";
    $htmlTh .= ordenarCampo("xajax_listadoPaquete", "30%", $pageNum, "codigo_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, ("C&oacute;digo"));
    $htmlTh .= ordenarCampo("xajax_listadoPaquete", "70%", $pageNum, "descripcion_paquete", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripci&oacute;n"));
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
        $clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
        $contFila++;

        $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
        $htmlTb .= "<td> <button type=\"button\" onclick=\"xajax_asignarPaquete('".$row['id_paquete']."');\" title=\"Seleccionar Paquete\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button></td>";
        $htmlTb .= "<td align='left'>".$row['descripcion_paquete']."</td>";
        $htmlTb .= "<td align='left'>".utf8_encode($row['descripcion_paquete'])."</td>";
        $htmlTb .= "</tr>";
    }

    $htmlTf = "<tr>";
    $htmlTf .= "<td align=\"center\" colspan=\"10\">";
    $htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
    $htmlTf .= "<tr class=\"tituloCampo\">";
    $htmlTf .= "<td align=\"right\" class=\"textoNegrita_10px\">";
    $htmlTf .= sprintf("Mostrando %s de %s Registros&nbsp;",
        $contFila,
        $totalRows);
    $htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
    $htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
    $htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
    $htmlTf .= "</td>";
    $htmlTf .= "<td align=\"center\" class=\"tituloColumna\" width=\"210\">";
    $htmlTf .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
    $htmlTf .= "<tr align=\"center\">";
    $htmlTf .= "<td width=\"25\">";
    if ($pageNum > 0) {
        $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
            0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
    }
    $htmlTf .= "</td>";
    $htmlTf .= "<td width=\"25\">";
    if ($pageNum > 0) {
        $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
            max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
    }
    $htmlTf .= "</td>";
    $htmlTf .= "<td width=\"100\">";

    $htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoPaquete(%s,'%s','%s','%s',%s)\">",
        "this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
    for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
        $htmlTf.="<option value=\"".$nroPag."\"";
        if ($pageNum == $nroPag) {
            $htmlTf.="selected=\"selected\"";
        }
        $htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
    }
    $htmlTf .= "</select>";

    $htmlTf .= "</td>";
    $htmlTf .= "<td width=\"25\">";
    if ($pageNum < $totalPages) {
        $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
            min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
    }
    $htmlTf .= "</td>";
    $htmlTf .= "<td width=\"25\">";
    if ($pageNum < $totalPages) {
        $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPaquete(%s,'%s','%s','%s',%s);\">%s</a>",
            $totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
    }
    $htmlTf .= "</td>";
    $htmlTf .= "</tr>";
    $htmlTf .= "</table>";
    $htmlTf .= "</td>";
    $htmlTf .= "</tr>";
    $htmlTf .= "</table>";
    $htmlTf .= "</td>";
    $htmlTf .= "</tr>";

    $htmlTblFin .= "</table>";

    if (!($totalRows > 0)) {
        $htmlTb .= "<td colspan=\"10\">";
        $htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
        $htmlTb .= "<tr>";
        $htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
        $htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
        $htmlTb .= "</tr>";
        $htmlTb .= "</table>";
        $htmlTb .= "</td>";
    }

    $objResponse->assign("tdListadoPaquete","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

    $objResponse->script("$('#divFlotante2').show();
                            centrarDiv(byId('divFlotante2'));");

    return $objResponse;
}

function asignarPaquete($idPaquete){
    $objResponse = new xajaxResponse();

    $query = sprintf("SELECT
                            id_paquete,
                            codigo_paquete,
                            descripcion_paquete
                        FROM sa_paquetes WHERE id_paquete = %s LIMIT 1",
        $idPaquete);

    $rs = mysql_query($query);
    if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\nquery:".$query);

    $row = mysql_fetch_assoc($rs);

    $objResponse->assign("txtCodigoPaquete","value",utf8_encode($row["codigo_paquete"]));
    $objResponse->assign("txtDescripcionPaquete","value",utf8_encode($row["descripcion_paquete"]));
    $objResponse->assign("txtIdPaquete","value",$row["id_paquete"]);

    $objResponse->script("$('#divFlotante2').hide();
                            byId('btnBuscar').click();
    ");

    return $objResponse;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <title>.: SIPRE 3.0 :. Servicios - Informe de Clientes Revisiones</title>
    <link rel="icon" type="image/png" href="../img/login/icono_sipre_png.png" />
    
    <link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
    <link rel="stylesheet" type="text/css" href="../js/domDragServicios.css"/>
    <script type="text/javascript" language="javascript" src="../js/jquerytools/jquery.tools.min.js"></script>
    <script type="text/javascript" language="javascript" src="../js/dom-drag.js"></script>
    <script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
    <script type="text/javascript" language="javascript" src="../js/validaciones.js"></script>
    
    <link rel="stylesheet" type="text/css" media="all" href="../js/calendar-green.css"/>
    <script type="text/javascript" language="javascript" src="../js/calendar.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-es.js"></script>
    <script type="text/javascript" language="javascript" src="../js/calendar-setup.js"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {
          //  $("#load_animate").hide();
        }); 
        
        function exportarExcel(){
            var lstEmpresa = document.getElementById('lstEmpresa').value;
            var txtFechaDesde = document.getElementById('txtFechaDesde').value;
            var txtFechaHasta = document.getElementById('txtFechaHasta').value;
            var txtIdTempario = document.getElementById('txtIdTempario').value;
            var txtIdPaquete = document.getElementById('txtIdPaquete').value;

            window.open('reportes/sa_informe_clientes_revisiones_excel.php?lstEmpresa='+lstEmpresa+'&txtFechaDesde='+txtFechaDesde+'&txtFechaHasta='+txtFechaHasta+'&txtIdTempario='+txtIdTempario+'&txtIdPaquete='+txtIdPaquete,'_self');
        }

    </script>

       

    <?php  $xajax->printJavascript("../controladores/xajax/"); ?>
               
    
</head>
    
<body class="bodyVehiculos">
    
<div id="divGeneralPorcentaje" style="text-align:left; ">
    <div> <?php include("banner_servicios.php"); ?> </div>
    <div id="divInfo" class="print">
        <table border="0" width="100%" class="noprint">
            <tr>
                <td class="tituloPaginaServicios" id="titulopag">Informe Clientes Revisiones</td>
            </tr>
            <tr>
                <td class="noprint">
                    <table align="left">
                        <tr>
                            <td>
                                <button type="button" class="noprint" onclick="exportarExcel();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/page_excel.png"/></td><td>&nbsp;</td><td>Exportar</td></tr></table></button>
                            </td>
                        </tr>
                    </table>

                    <form id="frmBuscarS" name="frmBuscarS" onsubmit="return false;" style="margin:0">
                        <table align="right">
                            <tr align="left">
                                <td align="right" class="tituloCampo" width="100">Empresa:</td>
                                <td id="tdlstEmpresa">
                                    
                                </td>
                                <td align="right" class="tituloCampo" width="120">Fecha de Factura:</td>
                                <td>
                                    <div style="float:left">
                                        <input type="text" id="txtFechaDesde" name="txtFechaDesde" readonly="readonly" style="text-align:center" size="15" value="<?php echo date("d-m-Y"); ?>"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/ico_date.png" id="imgFechaDesde" name="imgFechaDesde" class="puntero noprint"/>
                                        <script type="text/javascript">
                                        Calendar.setup({
                                        inputField : "txtFechaDesde",
                                        ifFormat : "%d-%m-%Y",
                                        button : "imgFechaDesde"
                                        });
                                        </script>
                                    </div>
                                </td>
                                <td>
                                    <div style="float:left">
                                        <input type="text" id="txtFechaHasta" name="txtFechaHasta" readonly="readonly" style="text-align:center" size="15" value="<?php echo date("d-m-Y"); ?>"/>
                                    </div>
                                    <div style="float:left">
                                        <img src="../img/iconos/ico_date.png" id="imgFechaHasta" name="imgFechaHasta" class="puntero noprint"/>
                                        <script type="text/javascript">
                                        Calendar.setup({
                                        inputField : "txtFechaHasta",
                                        ifFormat : "%d-%m-%Y",
                                        button : "imgFechaHasta"
                                        });

                                        </script>
                                    </div>
                                </td>
                                <td>
                                    <button type="button" id="btnBuscar" class="noprint" onclick="xajax_buscarOrdenes(xajax.getFormValues('frmBuscarS'));">Buscar</button>
                                    <button type="button" class="noprint" onclick="document.forms['frmBuscarS'].reset(); byId('txtIdTempario').value = ''; byId('txtIdPaquete').value = ''; byId('btnBuscar').click();">Limpiar</button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="15">

                                    <button onclick="byId('btnBuscarTempario').click();" class="noprint puntero" type="button"><img src="../img/iconos/diagnostico.png" style="white-space: nowrap; vertical-align: middle;"  />Mano de Obra</button>&nbsp;&nbsp;&nbsp;
                                    <input type="hidden" id="txtIdTempario" name="txtIdTempario"></input>
                                    <input type="text" readonly="readonly" id="txtCodigoTempario" ></input>
                                    <input type="text" readonly="readonly" id="txtDescripcionTempario" size="60"></input>
                                </td>
                            </tr><tr>
                                <td colspan="15">

                                    <button onclick="byId('btnBuscarPaquete').click();" class="noprint puntero" type="button"><img src="../img/iconos/package_green.png" style="white-space: nowrap; vertical-align: middle;"  />Paquete</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    <input type="hidden" id="txtIdPaquete" name="txtIdPaquete"></input>
                                    <input type="text" readonly="readonly" id="txtCodigoPaquete" ></input>
                                    <input type="text" readonly="readonly" id="txtDescripcionPaquete" size="60"></input>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
        </table>
        <div id="divListaOrdenes"> 

        </div>
    </div>

    <div class="noprint">
            <?php include("pie_pagina.php"); ?>
    </div>

</div>
    
</body>
</html>

<div class="root" id="divFlotante" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; width: 600px;">
    <div class="handle" id="divFlotanteTitulo" ><table><tbody><tr><td width="100%" id="tdFlotanteTitulo">Manos de Obra</td></tr></tbody></table></div>
        <form id="frmBuscarTempario" name="frmBuscarTempario" style="margin:0" onsubmit=" return false;">
            <table align="left" border="0" id="tblBusquedaTempario">
                <tr>
                    <td align="right" class="tituloCampo" width="120">Criterio:</td>
                    <td><input type="text" id="txtCriterioTemp" name="txtCriterioTemp"></td>
                    <td>
                        <button id="btnBuscarTempario" name="btnBuscarTempario" onclick="xajax_buscarTempario(xajax.getFormValues('frmBuscarTempario'));" >Buscar</button>
                        <button onclick="byId('txtCriterioTemp').value = ''; byId('btnBuscarTempario').click();" >Limpiar</button>
                    </td>
                </tr>
            </table>

            <table align="left" style="width: 600px;">
                <tr>
                    <td id="tdListadoTemparioPorUnidad" colspan="15"></td>
                </tr>
                <tr>
                    <td align="right" colspan="2">
                        <hr/>
                        <button type="button" class="puntero" onclick="$('#divFlotante').hide();">Cancelar</button>
                    </td>
                </tr>
            </table>

        </form>
</div>

<div class="root" id="divFlotante2" style="cursor:auto; display:none; left:0px; position:absolute; top:0px; z-index:0; width: 600px;">
    <div class="handle" id="divFlotanteTitulo2" ><table><tbody><tr><td width="100%" id="tdFlotanteTitulo2">Manos de Obra</td></tr></tbody></table></div>
    <form id="frmBuscarPaquete" name="frmBuscarPaquete" style="margin:0" onsubmit=" return false;">
        <table align="left" border="0" id="tblBusquedaPaquete">
            <tr>
                <td align="right" class="tituloCampo" width="120">Criterio:</td>
                <td><input type="text" id="txtCriterioPaq" name="txtCriterioPaq"></td>
                <td>
                    <button id="btnBuscarPaquete" name="btnBuscarPaquete" onclick="xajax_buscarPaquete(xajax.getFormValues('frmBuscarPaquete'));" >Buscar</button>
                    <button onclick="byId('txtCriterioPaq').value = ''; byId('btnBuscarPaquete').click();" >Limpiar</button>
                </td>
            </tr>
        </table>

        <table align="left" style="width: 600px;">
            <tr>
                <td id="tdListadoPaquete" colspan="15"></td>
            </tr>
            <tr>
                <td align="right" colspan="2">
                    <hr/>
                    <button type="button" class="puntero" onclick="$('#divFlotante2').hide();">Cancelar</button>
                </td>
            </tr>
        </table>

    </form>
</div>

<script type="text/javascript">
    xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="$(\'btnBuscar\').click();"','','','Todos'); //buscador
    xajax_buscarOrdenes(xajax.getFormValues('frmBuscarS'));

    var theHandle = document.getElementById("divFlotanteTitulo");
    var theRoot   = document.getElementById("divFlotante");
    Drag.init(theHandle, theRoot);

    var theHandle = document.getElementById("divFlotanteTitulo2");
    var theRoot   = document.getElementById("divFlotante2");
    Drag.init(theHandle, theRoot);
</script>