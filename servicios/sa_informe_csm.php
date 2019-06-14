<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1); 
@session_start();

include("../connections/conex.php");

/* Validación del Módulo */
include('../inc_sesion.php');

/*if(!(validaAcceso("sa_informe_csm"))) {
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

$xajax->processRequest();

function buscarOrdenes($valForm) {
    $objResponse = new xajaxResponse();
    
    $fecha1 = $valForm['txtFechaDesde'];
    $fecha2 = $valForm['txtFechaHasta'];
    
    $fecha_desde = date("Y/m/d",strtotime($fecha1));
    $fecha_hasta = date("Y/m/d",strtotime($fecha2));
    
    $valBusq = sprintf("%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta']);
    
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
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "codigo_dealer", $campOrd, $tpOrd, $valBusq, $maxRows, ("Dealer ID"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "tiempo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Orden"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Modelo"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tlf."));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "otrotelf", $campOrd, $tpOrd, $valBusq, $maxRows, ("Otro Tlf."));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "correo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Correo"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cliente"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "primera_visita", $campOrd, $tpOrd, $valBusq, $maxRows, ("Primera Visita"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro Orden"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Tipo Orden"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "modelo_dos_digitos", $campOrd, $tpOrd, $valBusq, $maxRows, ("Mod 2 dig"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "nom_ano", $campOrd, $tpOrd, $valBusq, $maxRows, ("A&ntilde;o"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "nombre_empleado_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Asesor"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "nombre_tecnico", $campOrd, $tpOrd, $valBusq, $maxRows, ("T&eacute;cnico"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanPlaca));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanSerialCarroceria));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "direccion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Direcci&oacute;n"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "ciudad", $campOrd, $tpOrd, $valBusq, $maxRows, ("Ciudad"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "estado", $campOrd, $tpOrd, $valBusq, $maxRows, ("Estado"));
            $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "", $pageNum, "zip_code", $campOrd, $tpOrd, $valBusq, $maxRows, ("Zip code"));		
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $contFila++;

            $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
                    $htmlTb .= "<td align=\"center\">".$row['codigo_dealer']."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";
                    $htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date("d-m-Y",strtotime($row['tiempo_orden']))."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['nom_modelo'])."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['otrotelf']."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['correo']."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_cliente'])."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['primera_visita']."</td>";
                    $htmlTb .= "<td align=\"center\" title=\"id orden: ".($row['id_orden'])." id recepcion: ".($row['id_recepcion'])."\">".($row['numero_orden'])."</td>";			
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['modelo_dos_digitos'])."</td>";
                    $htmlTb .= "<td align=\"center\">".$row['nom_ano']."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empleado_orden'])."</td>";                        
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tecnico'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['chasis'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['direccion'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['ciudad'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['estado'])."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode($row['zip_code'])."</td>";
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

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <title>.: SIPRE <?php echo cVERSION; ?> :. Servicios - Informe de CSM</title>
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
            
            window.open('reportes/sa_informe_csm_excel.php?lstEmpresa='+lstEmpresa+'&txtFechaDesde='+txtFechaDesde+'&txtFechaHasta='+txtFechaHasta,'_self');		
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
                <td class="tituloPaginaServicios" id="titulopag">Informe CSM</td>
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
<!--                                <td align="right" class="tituloCampo" width="100">Ordenar Por:</td>
                                <td id="td_tipo_medida">
                                    <select id="tipo_ordenamiento" name="tipo_ordenamiento">
                                        <option value="1" >Fecha Ingreso</option>                                    
                                        <option value="3" >Nº de Orden</option>
                                    </select>
                                </td>                                -->
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
                                    <button type="button" class="noprint" onclick="document.forms['frmBuscarS'].reset(); $('btnBuscar').click();">Limpiar</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </td>
            </tr>
<!--            <tr>
                <td>
                    <table border="0" width="100%">
                        <tr>
                            <td colspan="2" style="padding-right:4px">
                                <div id="divListaResumenServ" style="width:100%">
                                    <table cellpadding="0" cellspacing="0" class="divMsjInfo noprint" width="100%">
                                        <tr>
                                            <td width="25"><img src="../img/iconos/ico_info.gif" width="25"/></td>
                                            <td align="center">Ingrese Los Datos Para Realizar la Busqueda</td>
                                        </tr>
                                    </table>                         
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>-->
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

<script type="text/javascript">
    xajax_cargaLstEmpresaFinal('<?php echo $_SESSION["idEmpresaUsuarioSysGts"]; ?>','onchange="$(\'btnBuscar\').click();"','','','Todos'); //buscador
    xajax_buscarOrdenes(xajax.getFormValues('frmBuscarS'));
</script>