<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if((!validaAcceso("cj_cierre_caja") && !isset($_GET['tipoPago']))
|| (!validaAcceso("cj_pagos_cargados_dia_credito") && isset($_GET['tipoPago']) && in_array(0, explode(",",$_GET['tipoPago'])))
|| (!validaAcceso("cj_pagos_cargados_dia_contado") && isset($_GET['tipoPago']) && in_array(1, explode(",",$_GET['tipoPago'])))
|| (!validaAcceso("cj_pagos_cargados_dia_todo") && isset($_GET['tipoPago']) && in_array(0, explode(",",$_GET['tipoPago'])) && in_array(1, explode(",",$_GET['tipoPago'])))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");
include("controladores/ac_cj_pagos_cargados_dia.php");

// MODIFICADO ERNESTO
if (file_exists("../contabilidad/GenerarEnviarContabilidadDirecto.php")) { include("../contabilidad/GenerarEnviarContabilidadDirecto.php"); }
// MODIFICADO ERNESTO

$xajax->processRequest();

$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];

$queryEmp = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",
	valTpDato($idEmpresa, "int"));
$rsEmp = mysql_query($queryEmp, $conex);
if (!$rsEmp) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$rowEmp = mysql_fetch_assoc($rsEmp);

(strlen($rowEmp['telefono1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono1'] : "";
(strlen($rowEmp['telefono2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono2'] : "";
(strlen($rowEmp['telefono_taller1']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller1'] : "";
(strlen($rowEmp['telefono_taller2']) > 0) ? $arrayTelefonos[] = $rowEmp['telefono_taller2'] : "";

(isset($_GET['tipoPago'])) ? $arrayTipoPago = explode(",",$_GET['tipoPago']) : ""; // 0 = Crédito, 1 = Contado
$arrayDescripcionTipoPago = array(0 => "Crédito", 1 => "Contado");

// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
	INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
	valTpDato(1, "int")); // 1 = Empresa cabecera
$rsConfig400 = mysql_query($queryConfig400);
if (!$rsConfig400) die(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
$totalRowsConfig400 = mysql_num_rows($rsConfig400);
$rowConfig400 = mysql_fetch_assoc($rsConfig400);

if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
	$andEmpresaAPER = sprintf(" AND ape.id_empresa = %s",
		valTpDato($idEmpresa, "int"));
} else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
	$andEmpresaAPER = "";
}
	
$queryAperturaCaja = sprintf("SELECT ape.*,
	cierre.fechaCierre,
	cierre.fechaEjecucionCierre,
	cierre.horaEjecucionCierre,
	cierre.observacion,
	(CASE ape.statusAperturaCaja
		WHEN 0 THEN 'CERRADA TOTALMENTE'
		WHEN 1 THEN CONCAT_WS(' EL ', 'ABIERTA', DATE_FORMAT(ape.fechaAperturaCaja, %s))
		WHEN 2 THEN 'CERRADA PARCIALMENTE'
		ELSE 'CERRADA TOTALMENTE'
	END) AS estatus_apertura_caja,
	
	vw_iv_usuario_ape.id_empleado AS id_empleado_apertura,
	vw_iv_usuario_ape.nombre_empleado AS empleado_apertura,
	vw_iv_usuario_cierre.id_empleado AS id_empleado_cierre,
	vw_iv_usuario_cierre.nombre_empleado AS empleado_cierre
FROM ".$apertCajaPpal." ape
	INNER JOIN caja ON (ape.idCaja = caja.idCaja)
	LEFT JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
	INNER JOIN vw_iv_usuarios vw_iv_usuario_ape ON (ape.id_usuario = vw_iv_usuario_ape.id_usuario)
	LEFT JOIN vw_iv_usuarios vw_iv_usuario_cierre ON (cierre.id_usuario = vw_iv_usuario_cierre.id_usuario)
WHERE ape.idCaja = %s
	AND ape.statusAperturaCaja IN (1,2) %s;",
	valTpDato(spanDatePick, "date"),
	valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
	$andEmpresaAPER);
$rsAperturaCaja = mysql_query($queryAperturaCaja);
if (!$rsAperturaCaja) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);

$fechaApertura = $rowAperturaCaja['fechaAperturaCaja'];

switch($rowAperturaCaja['statusAperturaCaja']) {
	case 0 : $classApertura = "divMsjError"; break;
	case 1 : $classApertura = "divMsjInfo"; break;
	case 2 : $classApertura = "divMsjAlerta"; break;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Pagos Cargados del Día <?php echo (count($arrayTipoPago) == 1) ? $arrayDescripcionTipoPago[$arrayTipoPago[0]] : ""; ?></title>
	<link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
	<?php $xajax->printJavascript('../controladores/xajax/'); ?>
	
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
	<link rel="stylesheet" type="text/css" href="../js/domDragCaja.css"/>
	<script type="text/javascript" language="javascript" src="../js/scriptRafk.js"></script>
	
	<script language="JavaScript">
	function imprimirPlanilla(k) {
		if (byId("hddExisteRegistro").value == 1) {
			if (confirm("Esta seguro de realizar el cierre de caja?")) {
				return true;
			} else {
				return false;
			}
		} else {
			if (byId("hddPresionoImprimirCorteCaja").value == 1) {
				if (confirm("Imprimio Correctamente el formato de Corte de Caja?")) {
					if (k == 1) {
						window.location.href="cj_depositos_form.php";
					}
					return true;
				} else {
					byId("btnImprimir").focus();
					return false;
				}
			} else {
				alert ("Debe Imprimir el Corte de Caja");
				byId("btnImprimir").focus();
				return false;
			}
		}
	}
	
	function printPage() {
		byId("hddPresionoImprimirCorteCaja").value = 1;
		
		for (cont = 1; byId('tblPagos' + cont) != undefined; cont++) {
			byId('tblPagos' + cont).border = '1';
			byId('tblPagos' + cont).className = 'tabla';
			//byId('tblPagos' + cont).setAttribute("style", "border-collapse:collapse;");
		}
		byId('divButtons').style.visibility = 'hidden';
		window.print();
		for (cont = 1; byId('tblPagos' + cont) != undefined; cont++) {
			byId('tblPagos' + cont).border = '0';
			byId('tblPagos' + cont).className = '';
			//byId('tblPagos' + cont).removeAttribute("style");
		}
		byId('divButtons').style.visibility = 'visible';
	}
	</script>
</head>
<body>
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_cj.php"); ?></div>
    
	<form id="frmCerrar" name="frmCerrar" method="post">
        <table border="0" width="100%">
        <tr class="solo_print">
            <td>
                <table>
                <tr align="left">
                    <td>
                        <table style="text-align:center; background:#FFF; border-radius:0.4em;">
                        <tr>
                            <td><img id="imgLogoEmpresa" name="imgLogoEmpresa" src="../<?php echo $rowEmp['logo_familia']; ?>" width="180"></td>
                        </tr>
                        </table>
                    </td>
                    <td class="textoNegroNegrita_10px" style="padding:4px">
                        <p>
                            <?php echo utf8_encode($rowEmp['nombre_empresa']); ?>
                            <br>
                            <?php echo utf8_encode($rowEmp['rif']); ?>
                            <br>
                            <?php echo utf8_encode($rowEmp['direccion']); ?>
                            <br>
                            <?php echo (count($arrayTelefonos) > 0) ? "Telf.: ".implode(" / ", $arrayTelefonos): ""; ?>
                            <br>
                            <?php echo utf8_encode($rowEmp['web']); ?>
                        </p>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
            	<table width="100%">
                <tr align="left" height="24">
                    <td width="14%"></td>
                    <td width="14%"></td>
                    <td width="14%"></td>
                    <td width="18%"></td>
                    <td align="right" class="tituloCampo" width="14%">Fecha Impresión:</td>
                    <td align="center" width="26%"><?php echo date(spanDateFormat); ?></td>
                </tr>
                <tr align="left" height="24">
                    <td align="right" class="tituloCampo">Fecha Apertura:</td>
                    <td align="center"><?php echo date(spanDateFormat, strtotime($rowAperturaCaja['fechaAperturaCaja'])); ?></td>
                    <td align="right" class="tituloCampo">Ejecución Apertura:</td>
                    <td align="center"><?php echo date(spanDateFormat." H:i:s", strtotime($rowAperturaCaja['fechaAperturaCaja']." ".$rowAperturaCaja['horaApertura'])); ?></td>
                    <td align="right" class="tituloCampo">Empleado Apertura:</td>
                    <td><?php echo $rowAperturaCaja['empleado_apertura']; ?></td>
                </tr>
                <tr align="left" height="24" <?php if (in_array($rowAperturaCaja['statusAperturaCaja'], array(1,2))) { echo "style=\"display:none\""; } ?>>
                    <td align="right" class="tituloCampo">Fecha Cierre:</td>
                    <td align="center"><?php echo date(spanDateFormat, strtotime($rowAperturaCaja['fechaCierre'])); ?></td>
                    <td align="right" class="tituloCampo">Ejecución Cierre:</td>
                    <td align="center"><?php echo date(spanDateFormat." H:i:s", strtotime($rowAperturaCaja['fechaEjecucionCierre']." ".$rowAperturaCaja['horaEjecucionCierre'])); ?></td>
                    <td align="right" class="tituloCampo">Empleado Cierre:</td>
                    <td><?php echo $rowAperturaCaja['empleado_cierre']; ?></td>
                </tr>
                <tr align="left" height="24">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td align="right" class="tituloCampo">Estado de Caja:</td>
                    <td align="center" class="<?php echo $classApertura; ?>"><?php echo $rowAperturaCaja['estatus_apertura_caja']; ?></td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="center">
            <strong>
            <?php
            if ($_GET['acc'] == 2) { // 2 = Corte de Caja
                echo "CORTE DE CAJA ".strtoupper((count($arrayTipoPago) == 1) ? $arrayDescripcionTipoPago[$arrayTipoPago[0]] : "")."<br>(".$nombreCajaPpal.")<br>RECIBOS POR MEDIO DE PAGO";
            } else {
                echo "PAGOS CARGADOS DEL DÍA ".strtoupper((count($arrayTipoPago) == 1) ? $arrayDescripcionTipoPago[$arrayTipoPago[0]] : "")."<br>(".$nombreCajaPpal.")";
            } ?>
            </strong>
            </td>
        </tr>
        </table>
        
        <br>
		
		<?php
        if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
            $andEmpresaPagoCorte = sprintf(" WHERE q.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
            $andEmpresaPagoDia = sprintf(" WHERE vw_pago_dia.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
            $groupBy = sprintf(", q.id_empresa");
            
            $andEmpresaPagoFA = sprintf(" AND cxc_fact.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
            $andEmpresaPagoND = sprintf(" AND cxc_nd.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
            $andEmpresaPagoAN = sprintf(" AND cxc_ant.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
            $andEmpresaPagoCH = sprintf(" AND cxc_ch.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
            $andEmpresaPagoTB = sprintf(" AND cxc_tb.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
        } else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
            $andEmpresaPagoCorte = "";
            $andEmpresaPagoDia = "";
            $groupBy = "";
            
            $andEmpresaPagoFA = "";
            $andEmpresaPagoND = "";
            $andEmpresaPagoAN = "";
            $andEmpresaPagoCH = "";
            $andEmpresaPagoTB = "";
        }
        
        if ($_GET['acc'] == 2) { // 2 = Corte de Caja
            $queryFormaPago = sprintf("SELECT
                q.formaPago,
                q.tipoDoc,
                (SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = q.tipoDoc) AS nombre_tipo_documento,
                q.id_empresa,
                forma_pago.nombreFormaPago
            FROM (SELECT
                    cxc_fact.id_empresa AS id_empresa,
                    1  AS tipoDoc,
                    cxc_pago.formaPago AS formaPago
                FROM cj_cc_encabezadofactura cxc_fact
                    INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
                WHERE cxc_pago.idCaja IN (%s) AND cxc_pago.tomadoEnCierre IN (0,2)
                    AND cxc_pago.fechaPago = %s
                    AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
                GROUP BY cxc_fact.id_empresa, cxc_pago.formaPago
                
                UNION
                
                SELECT
                    cxc_nd.id_empresa AS id_empresa,
                    2  AS tipoDoc,
                    cxc_pago.idFormaPago AS formaPago
                FROM cj_cc_notadecargo cxc_nd
                    INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
                WHERE cxc_pago.idCaja IN (%s) AND cxc_pago.tomadoEnCierre IN (0,2)
                    AND cxc_pago.fechaPago = %s
                    AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
                GROUP BY cxc_nd.id_empresa, cxc_pago.idFormaPago
                
                UNION
                
                SELECT
                    cxc_ant.id_empresa AS id_empresa,
                    4  AS tipoDoc,
                    cxc_pago.id_forma_pago AS formaPago
                FROM cj_cc_anticipo cxc_ant
                    INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
                WHERE cxc_pago.idCaja IN (%s) AND cxc_pago.tomadoEnCierre in (0,2)
                    AND cxc_pago.fechaPagoAnticipo = %s
                    AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
                GROUP BY cxc_ant.id_empresa, cxc_pago.id_forma_pago
                
                UNION
                
                SELECT
                    cxc_ch.id_empresa AS id_empresa,
                    5 AS tipoDoc,
                    2 AS formaPago
                FROM cj_cc_cheque cxc_ch
                WHERE cxc_ch.idCaja IN (%s) AND cxc_ch.tomadoEnCierre in (0,2)
                    AND cxc_ch.fecha_cheque = %s
                    AND (cxc_ch.idCierre = 0 OR cxc_ch.idCierre IS NULL)
                GROUP BY cxc_ch.id_empresa, 3
                
                UNION
                
                SELECT
                    cxc_tb.id_empresa AS id_empresa,
                    6 AS tipoDoc,
                    4 AS formaPago
                FROM cj_cc_transferencia cxc_tb
                WHERE cxc_tb.idCaja IN (%s) AND cxc_tb.tomadoEnCierre in (0,2)
                    AND cxc_tb.fecha_transferencia = %s
                    AND (cxc_tb.idCierre = 0 OR cxc_tb.idCierre IS NULL)
                GROUP BY cxc_tb.id_empresa, 3) AS q
                INNER JOIN formapagos forma_pago ON (q.formaPago = forma_pago.idFormaPago) ".$andEmpresaPagoCorte."
            GROUP BY q.formaPago ".$groupBy."
            ORDER BY q.formaPago",
                valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
                valTpDato($fechaApertura, "date"),
                valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
                valTpDato($fechaApertura, "date"),
                valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
                valTpDato($fechaApertura, "date"),
                valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
                valTpDato($fechaApertura, "date"),
                valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
                valTpDato($fechaApertura, "date"));
        } else {
            if ((count($arrayTipoPago) > 1 && in_array(0, $arrayTipoPago) && in_array(1, $arrayTipoPago)) || !isset($arrayTipoPago)) { // 0 = Crédito, 1 = Contado
                $andCondicionPagoFA = "";
                $andCondicionPagoND = "";
                $andCondicionPagoAN = "";
                $andCondicionPagoCH = "";
                $andCondicionPagoTB = "";
            } else if (count($arrayTipoPago) > 0 && in_array(0, $arrayTipoPago)) {
                $andCondicionPagoFA = " AND cxc_fact.condicionDePago = 0";
                $andCondicionPagoND = " AND cxc_nd.fechaRegistroNotaCargo <> '".$fechaApertura."'";
                $andCondicionPagoAN = " AND cxc_ant.fechaAnticipo <> '".$fechaApertura."'";
                $andCondicionPagoCH = " AND cxc_ch.fecha_cheque <> '".$fechaApertura."'";
                $andCondicionPagoTB = " AND cxc_tb.fecha_transferencia <> '".$fechaApertura."'";
            } else if (count($arrayTipoPago) > 0 && in_array(1, $arrayTipoPago)) {
                $andCondicionPagoFA = " AND cxc_fact.condicionDePago = 1";
                $andCondicionPagoND = " AND cxc_nd.fechaRegistroNotaCargo = '".$fechaApertura."'";
                $andCondicionPagoAN = " AND cxc_ant.fechaAnticipo = '".$fechaApertura."'";
                $andCondicionPagoCH = " AND cxc_ch.fecha_cheque = '".$fechaApertura."'";
                $andCondicionPagoTB = " AND cxc_tb.fecha_transferencia = '".$fechaApertura."'";
            }
            
            if ((count($arrayTipoPago) > 1 && in_array(0, $arrayTipoPago) && in_array(1, $arrayTipoPago)) || !isset($arrayTipoPago)) { // 0 = Crédito, 1 = Contado
                $queryFormaPago = "SELECT 
                    vw_pago_dia.formaPago,
                    vw_pago_dia.tipoDoc,
                    vw_pago_dia.id_empresa,
                    forma_pago.nombreFormaPago
                FROM vw_cj_vh_pago_dia vw_pago_dia
                    INNER JOIN formapagos forma_pago ON (vw_pago_dia.formaPago = forma_pago.idFormaPago) ".$andEmpresaPagoDia."
                GROUP BY vw_pago_dia.formaPago, vw_pago_dia.id_empresa
                ORDER BY vw_pago_dia.formaPago";
            } else if (count($arrayTipoPago) > 0 && in_array(0, $arrayTipoPago)) {
                $queryFormaPago = "SELECT 
                    vw_pago_dia.formaPago,
                    vw_pago_dia.tipoDoc,
                    vw_pago_dia.id_empresa,
                    forma_pago.nombreFormaPago
                FROM vw_cj_vh_pago_dia_credito vw_pago_dia
                    INNER JOIN formapagos forma_pago ON (vw_pago_dia.formaPago = forma_pago.idFormaPago) ".$andEmpresaPagoDia."
                GROUP BY vw_pago_dia.formaPago, vw_pago_dia.id_empresa
                ORDER BY vw_pago_dia.formaPago";
            } else if (count($arrayTipoPago) > 0 && in_array(1, $arrayTipoPago)) {
                $queryFormaPago = "SELECT 
                    vw_pago_dia.formaPago,
                    vw_pago_dia.tipoDoc,
                    vw_pago_dia.id_empresa,
                    forma_pago.nombreFormaPago
                FROM vw_cj_vh_pago_dia_contado vw_pago_dia
                    INNER JOIN formapagos forma_pago ON (vw_pago_dia.formaPago = forma_pago.idFormaPago) ".$andEmpresaPagoDia."
                GROUP BY vw_pago_dia.formaPago, vw_pago_dia.id_empresa
                ORDER BY vw_pago_dia.formaPago";
            }
        }
        $rsFormaPago = mysql_query($queryFormaPago); //echo $queryFormaPago."<br><br>";
        if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
        $sw = "";
        while ($rowFormaPago = mysql_fetch_array($rsFormaPago)) {
            $idFormaPago = $rowFormaPago['formaPago'];
            $nombreFormaPago = $rowFormaPago['nombreFormaPago'];
            
            if ($sw != $idFormaPago) {
                $queryPago = "SELECT 
                    cxc_pago.idPago,
                    1 AS idTipoDeDocumento,
                    'FA' AS tipoDocumento,
                    (SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 1) AS tipo_documento_pagado,
                    cxc_fact.idDepartamentoOrigenFactura AS id_modulo_documento_pagado,
                    cxc_fact.idFactura AS id_documento_pagado,
                    cxc_fact.numeroFactura AS numero_documento,
                    cliente.id AS id_cliente,
                    CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
                    CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
                    cxc_pago.fechaPago,
                    recibo.idComprobante AS id_recibo_pago,
                    recibo.numeroComprobante AS nro_comprobante,
                    cxc_pago.formaPago,
                    forma_pago.nombreFormaPago,
                    NULL AS id_concepto,
                    (CASE cxc_pago.formaPago
                        WHEN 7 THEN
                            (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
                            FROM cj_cc_detalleanticipo cxc_pago
                                INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
                            WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
                                AND cxc_pago.id_forma_pago IN (11))
                    END) AS descripcion_concepto_forma_pago,
                    (CASE cxc_pago.formaPago
                        WHEN 7 THEN
                            (SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
                        WHEN 8 THEN
                            (SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
                        ELSE
                            cxc_pago.numeroDocumento
                    END) AS numero_documento_pago,
                    cxc_pago.bancoOrigen,
                    banco_origen.nombreBanco AS nombre_banco_origen,
                    cxc_pago.bancoDestino,
                    banco_destino.nombreBanco AS nombre_banco_destino,
                    cxc_pago.cuentaEmpresa,
                    cxc_pago.idCaja,
                    cxc_pago.montoPagado,
                    cxc_pago.estatus,
                    cxc_pago.estatus AS estatus_pago,
                    DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
                    cxc_pago.id_empleado_anulado,
                    vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
                    'an_pagos' AS tabla,
                    'idPago' AS campo_id_pago,
                    IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
                FROM cj_cc_encabezadofactura cxc_fact
                    INNER JOIN cj_cc_cliente cliente ON (cxc_fact.idCliente = cliente.id)
                    INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
                    INNER JOIN formapagos forma_pago on (cxc_pago.formaPago = forma_pago.idFormaPago)
                    INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
                    INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
                    LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
                    INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.idPago = recibo_det.idPago)
                    INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_fact.idDepartamentoOrigenFactura = recibo.id_departamento AND recibo.idTipoDeDocumento = 1)
                    INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_fact.id_empresa = vw_iv_emp_suc.id_empresa_reg)
                WHERE cxc_pago.fechaPago = '".$fechaApertura."'
                    AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
                    AND (cxc_pago.formaPago NOT IN (2,4)
                        OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
                        OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
                    AND cxc_pago.tomadoEnComprobante = 1
                    AND cxc_pago.tomadoEnCierre IN (0,1,2)
                    AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
                    AND cxc_pago.formaPago = ".$idFormaPago."
                    ".$andEmpresaPagoFA." ".$andCondicionPagoFA."
                
                UNION
                
                SELECT 
                    cxc_pago.id_det_nota_cargo AS idPago,
                    2 AS idTipoDeDocumento,
                    'ND' AS tipoDocumento,
                    (SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 2) AS tipo_documento_pagado,
                    cxc_nd.idDepartamentoOrigenNotaCargo AS id_modulo_documento_pagado,
                    cxc_nd.idNotaCargo AS id_documento_pagado,
                    cxc_nd.numeroNotaCargo AS numero_documento,
                    cliente.id AS id_cliente,
                    CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
                    CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
                    cxc_pago.fechaPago,
                    recibo.idComprobante AS id_recibo_pago,
                    recibo.numeroComprobante AS nro_comprobante,
                    cxc_pago.idFormaPago AS formaPago,
                    forma_pago.nombreFormaPago,
                    NULL AS id_concepto,
                    (CASE cxc_pago.idFormaPago
                        WHEN 7 THEN
                            (SELECT GROUP_CONCAT(concepto_forma_pago.descripcion SEPARATOR ', ')
                            FROM cj_cc_detalleanticipo cxc_pago
                                INNER JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
                            WHERE cxc_pago.idAnticipo = cxc_pago.numeroDocumento
                                AND cxc_pago.id_forma_pago IN (11))
                    END) AS descripcion_concepto_forma_pago,
                    (CASE cxc_pago.idFormaPago
                        WHEN 7 THEN
                            (SELECT numeroAnticipo FROM cj_cc_anticipo WHERE idAnticipo = cxc_pago.numeroDocumento)
                        WHEN 8 THEN
                            (SELECT numeracion_nota_credito FROM cj_cc_notacredito WHERE idNotaCredito = cxc_pago.numeroDocumento)
                        ELSE
                            cxc_pago.numeroDocumento
                    END) AS numero_documento_pago,
                    cxc_pago.bancoOrigen,
                    banco_origen.nombreBanco AS nombre_banco_origen,
                    cxc_pago.bancoDestino,
                    banco_destino.nombreBanco AS nombre_banco_destino,
                    cxc_pago.cuentaEmpresa,
                    cxc_pago.idCaja,
                    cxc_pago.monto_pago AS montoPagado,
                    cxc_pago.estatus,
                    cxc_pago.estatus AS estatus_pago,
                    DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
                    cxc_pago.id_empleado_anulado,
                    vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
                    'cj_det_nota_cargo' AS tabla,
                    'id_det_nota_cargo' AS campo_id_pago,
                    IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
                FROM cj_cc_notadecargo cxc_nd
                    INNER JOIN cj_cc_cliente cliente ON (cxc_nd.idCliente = cliente.id)
                    INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
                    INNER JOIN formapagos forma_pago on (cxc_pago.idFormaPago = forma_pago.idFormaPago)
                    INNER JOIN bancos banco_origen on (cxc_pago.bancoOrigen = banco_origen.idBanco)
                    INNER JOIN bancos banco_destino on (cxc_pago.bancoDestino = banco_destino.idBanco)
                    LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
                    INNER JOIN cj_detallerecibopago recibo_det ON (cxc_pago.id_det_nota_cargo = recibo_det.idPago)
                    INNER JOIN cj_encabezadorecibopago recibo ON (recibo_det.idComprobantePagoFactura = recibo.idComprobante AND cxc_nd.idDepartamentoOrigenNotaCargo = recibo.id_departamento AND recibo.idTipoDeDocumento = 2)
                    INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_nd.id_empresa = vw_iv_emp_suc.id_empresa_reg)
                WHERE cxc_pago.fechaPago = '".$fechaApertura."'
                    AND cxc_nd.idDepartamentoOrigenNotaCargo IN (".valTpDato($idModuloPpal, "campo").")
                    AND (cxc_pago.idFormaPago NOT IN (2,4)
                        OR (cxc_pago.id_cheque IS NULL AND cxc_pago.idFormaPago IN (2))
                        OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.idFormaPago IN (4)))
                    AND cxc_pago.tomadoEnComprobante = 1
                    AND cxc_pago.tomadoEnCierre IN (0,1,2)
                    AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
                    AND cxc_pago.idFormaPago = ".$idFormaPago."
                    ".$andEmpresaPagoND." ".$andCondicionPagoND."
                
                UNION
                
                SELECT 
                    cxc_pago.idDetalleAnticipo AS idPago,
                    4 AS idTipoDeDocumento,
                    'AN' AS tipoDocumento,
                    (SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 4) AS tipo_documento_pagado,
                    cxc_ant.idDepartamento AS id_modulo_documento_pagado,
                    cxc_ant.idAnticipo AS id_documento_pagado,
                    cxc_ant.numeroAnticipo AS numero_documento,
                    cliente.id AS id_cliente,
                    CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
                    CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
                    cxc_pago.fechaPagoAnticipo AS fechaPago,
                    recibo.idReporteImpresion AS id_recibo_pago,
                    recibo.numeroReporteImpresion AS nro_comprobante,
                    cxc_pago.id_forma_pago AS formaPago,
                    forma_pago.nombreFormaPago,
                    cxc_pago.id_concepto AS id_concepto,
                    concepto_forma_pago.descripcion AS descripcion_concepto_forma_pago,
                    cxc_pago.numeroControlDetalleAnticipo AS numero_documento_pago,
                    cxc_pago.bancoClienteDetalleAnticipo AS bancoOrigen,
                    banco_origen.nombreBanco AS nombre_banco_origen,
                    cxc_pago.bancoCompaniaDetalleAnticipo AS bancoDestino,
                    banco_destino.nombreBanco AS nombre_banco_destino,
                    cxc_pago.numeroCuentaCompania AS cuentaEmpresa,
                    cxc_pago.idCaja,
                    cxc_pago.montoDetalleAnticipo AS montoPagado,
                    cxc_ant.estatus AS estatus,
                    cxc_pago.estatus AS estatus_pago,
                    DATE(cxc_pago.fecha_anulado) AS fecha_anulado,
                    cxc_pago.id_empleado_anulado,
                    vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
                    'cj_cc_detalleanticipo' AS tabla,
                    'idDetalleAnticipo' AS campo_id_pago,
                    IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
                FROM cj_cc_anticipo cxc_ant
                    INNER JOIN cj_cc_cliente cliente ON (cxc_ant.idCliente = cliente.id)
                    INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
                    INNER JOIN formapagos forma_pago on (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
                    INNER JOIN bancos banco_origen on (cxc_pago.bancoClienteDetalleAnticipo = banco_origen.idBanco)
                    INNER JOIN bancos banco_destino on (cxc_pago.bancoCompaniaDetalleAnticipo = banco_destino.idBanco)
                    LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_pago.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
                    INNER JOIN pg_reportesimpresion recibo ON (cxc_pago.id_reporte_impresion = recibo.idReporteImpresion AND cxc_ant.idDepartamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'AN')
                    LEFT JOIN cj_conceptos_formapago concepto_forma_pago ON (cxc_pago.id_concepto = concepto_forma_pago.id_concepto)
                    INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ant.id_empresa = vw_iv_emp_suc.id_empresa_reg)
                WHERE cxc_pago.fechaPagoAnticipo = '".$fechaApertura."'
                    AND cxc_ant.idDepartamento IN (".valTpDato($idModuloPpal, "campo").")
                    AND (cxc_pago.id_forma_pago NOT IN (2,4)
                        OR (cxc_pago.id_cheque IS NULL AND cxc_pago.id_forma_pago IN (2))
                        OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.id_forma_pago IN (4)))
                    AND cxc_pago.tomadoEnCierre IN (0,1,2)
                    AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
                    AND cxc_pago.id_forma_pago = ".$idFormaPago."
                    AND cxc_ant.estatus IN (1)
                    ".$andEmpresaPagoAN." ".$andCondicionPagoAN."
                
                UNION
                
                SELECT 
                    cxc_ch.id_cheque AS idPago,
                    5 AS idTipoDeDocumento,
                    'CH' AS tipoDocumento,
                    (SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 5) AS tipo_documento_pagado,
                    cxc_ch.id_departamento AS id_modulo_documento_pagado,
                    cxc_ch.id_cheque AS id_documento_pagado,
                    '-' AS numero_documento,
                    cliente.id AS id_cliente,
                    CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
                    CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
                    cxc_ch.fecha_cheque AS fechaPago,
                    recibo.idReporteImpresion AS id_recibo_pago,
                    recibo.numeroReporteImpresion AS nro_comprobante,
                    2 AS formaPago,
                    (SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 2) AS nombreFormaPago,
                    NULL AS id_concepto,
                    NULL AS descripcion_concepto_forma_pago,
                    cxc_ch.numero_cheque AS numero_documento_pago,
                    cxc_ch.id_banco_cliente AS bancoOrigen,
                    banco_origen.nombreBanco AS nombre_banco_origen,
                    1 AS bancoDestino,
                    '-' AS nombre_banco_destino,
                    '-' AS cuentaEmpresa,
                    cxc_ch.idCaja,
                    cxc_ch.total_pagado_cheque AS montoPagado,
                    cxc_ch.estatus AS estatus,
                    cxc_ch.estatus AS estatus_pago,
                    DATE(cxc_ch.fecha_anulado) AS fecha_anulado,
                    cxc_ch.id_empleado_anulado,
                    vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
                    'cj_cc_cheque' AS tabla,
                    'id_cheque' AS campo_id_pago,
                    IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
                FROM cj_cc_cheque cxc_ch
                    INNER JOIN cj_cc_cliente cliente ON (cxc_ch.id_cliente = cliente.id)
                    INNER JOIN bancos banco_origen on (cxc_ch.id_banco_cliente = banco_origen.idBanco)
                    LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_ch.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
                    INNER JOIN pg_reportesimpresion recibo ON (cxc_ch.id_cheque = recibo.idDocumento AND cxc_ch.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'CH')
                    INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_ch.id_empresa = vw_iv_emp_suc.id_empresa_reg)
                WHERE cxc_ch.fecha_cheque = '".$fechaApertura."'
                    AND cxc_ch.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
                    AND cxc_ch.tomadoEnComprobante = 1
                    AND cxc_ch.tomadoEnCierre IN (0,1,2)
                    AND (cxc_ch.idCierre = 0 OR cxc_ch.idCierre IS NULL)
                    AND 2 = ".$idFormaPago."
                    AND cxc_ch.estatus IN (1)
                    ".$andEmpresaPagoCH." ".$andCondicionPagoCH."
                
                UNION
                
                SELECT 
                    cxc_tb.id_transferencia AS idPago,
                    6 AS idTipoDeDocumento,
                    'TB' AS tipoDocumento,
                    (SELECT tipo_dcto.descripcionTipoDeDocumento FROM tipodedocumentos tipo_dcto WHERE tipo_dcto.idTipoDeDocumento = 6) AS tipo_documento_pagado,
                    cxc_tb.id_departamento AS id_modulo_documento_pagado,
                    cxc_tb.id_transferencia AS id_documento_pagado,
                    '-' AS numero_documento,
                    cliente.id AS id_cliente,
                    CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
                    CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
                    cxc_tb.fecha_transferencia AS fechaPago,
                    recibo.idReporteImpresion AS id_recibo_pago,
                    recibo.numeroReporteImpresion AS nro_comprobante,
                    4 AS formaPago,
                    (SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 4) AS nombreFormaPago,
                    NULL AS id_concepto,
                    NULL AS descripcion_concepto_forma_pago,
                    cxc_tb.numero_transferencia AS numero_documento_pago,
                    cxc_tb.id_banco_cliente AS bancoOrigen,
                    banco_origen.nombreBanco AS nombre_banco_origen,
                    1 AS bancoDestino,
                    '-' AS nombre_banco_destino,
                    '-' AS cuentaEmpresa,
                    cxc_tb.idCaja,
                    cxc_tb.total_pagado_transferencia AS montoPagado,
                    cxc_tb.estatus AS estatus,
                    cxc_tb.estatus AS estatus_pago,
                    DATE(cxc_tb.fecha_anulado) AS fecha_anulado,
                    cxc_tb.id_empleado_anulado,
                    vw_pg_empleado_anulado.nombre_empleado AS nombre_empleado_anulado,
                    'cj_cc_transferencia' AS tabla,
                    'id_transferencia' AS campo_id_pago,
                    IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
                FROM cj_cc_transferencia cxc_tb
                    INNER JOIN cj_cc_cliente cliente ON (cxc_tb.id_cliente = cliente.id)
                    INNER JOIN bancos banco_origen on (cxc_tb.id_banco_cliente = banco_origen.idBanco)
                    LEFT JOIN vw_pg_empleados vw_pg_empleado_anulado ON (cxc_tb.id_empleado_anulado = vw_pg_empleado_anulado.id_empleado)
                    INNER JOIN pg_reportesimpresion recibo ON (cxc_tb.id_transferencia = recibo.idDocumento AND cxc_tb.id_departamento = recibo.id_departamento AND recibo.tipoDocumento LIKE 'TB')
                    INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (cxc_tb.id_empresa = vw_iv_emp_suc.id_empresa_reg)
                WHERE cxc_tb.fecha_transferencia = '".$fechaApertura."'
                    AND cxc_tb.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
                    AND cxc_tb.tomadoEnComprobante = 1
                    AND cxc_tb.tomadoEnCierre IN (0,1,2)
                    AND (cxc_tb.idCierre = 0 OR cxc_tb.idCierre IS NULL)
                    AND 4 = ".$idFormaPago."
                    AND cxc_tb.estatus IN (1)
                    ".$andEmpresaPagoTB." ".$andCondicionPagoTB.";"; //echo $queryPago."<br><br>";
                $rsPago = mysql_query($queryPago);
                if (!$rsPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
                $totalRowsPago = mysql_num_rows($rsPago);
                if ($totalRowsPago > 0) {
                    $cont++; ?>
                    <table id="<?php echo "tblPagos".$cont; ?>" border="0" class="texto_9px" width="100%">
                    <tr>
                        <td class="tituloArea" colspan="10"><?php echo $nombreFormaPago; ?></td>
                    </tr>
                    <tr align="center" class="tituloColumna">
                        <td width="7%">Tipo Documento</td>
                        <td width="6%">Nro. Dcto. Pagado</td>
                        <td width="6%">Nro. Recibo</td>
                        <?php if (!in_array(idArrayPais,array(3))) { // EN PUERTO RICO NO MOSTRAR SSN ?>
                        <td width="7%"><?php echo $spanClienteCxC; ?></td>
                        <?php } ?>
                        <td width="16%">Cliente</td>
                        <td width="8%"><?php echo ($idFormaPago != 1) ? "Nro. ".$nombreFormaPago : "-"; ?></td>
                        <td width="15%">Banco Cliente</td>
                        <td width="15%">Banco Empresa</td>
                        <td width="14%">Nro. Cuenta</td>
                        <td width="6%">Monto</td>
                    </tr>
                    <?php
                    $totalPagos = 0;
                    $contFila = 0;
                    while ($rowPago = mysql_fetch_array($rsPago)) {
                        $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
                        $contFila++;
                        
                        switch($rowPago['id_modulo_documento_pagado']) {
                            case 0 : $imgDctoModulo = "<img src=\"../img/iconos/ico_repuestos.gif\" title=\"Repuestos\"/>"; break;
                            case 1 : $imgDctoModulo = "<img src=\"../img/iconos/ico_servicios.gif\" title=\"Servicios\"/>"; break;
                            case 2 : $imgDctoModulo = "<img src=\"../img/iconos/ico_vehiculos.gif\" title=\"Vehículos\"/>"; break;
                            case 3 : $imgDctoModulo = "<img src=\"../img/iconos/ico_compras.gif\" title=\"Administración\"/>"; break;
                            case 4 : $imgDctoModulo = "<img src=\"../img/iconos/ico_alquiler.gif\" title=\"Alquiler\"/>"; break;
							case 5 : $imgDctoModulo = "<img src=\"../img/iconos/ico_financiamiento.gif\" title=\"Financiamiento\"/>"; break;
                            default : $imgDctoModulo = $rowPago['id_modulo_documento_pagado'];
                        }
                        
                        switch ($rowPago['idTipoDeDocumento']) {
                            case 1 : // 1 = Factura
                                $aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_factura_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Factura Venta")."\"/><a>",
                                    $rowPago['id_documento_pagado']);
                                switch ($rowPago['id_modulo_documento_pagado']) {
                                    case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_factura_venta_pdf.php?valBusq=%s", $rowPago['id_documento_pagado']); break;
                                    case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_factura_venta_pdf.php?valBusq=%s", $rowPago['id_documento_pagado']); break;
                                    case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_factura_venta_pdf.php?valBusq=%s", $rowPago['id_documento_pagado']); break;
                                    case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_factura_venta_pdf.php?valBusq=%s", $rowPago['id_documento_pagado']); break;
                                    case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_factura_venta_pdf.php?valBusq=%s", $rowPago['id_documento_pagado']); break;
                                    default : $aVerDctoAux = "";
                                }
                                $aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Factura Venta PDF")."\"/></a>" : "";
                                break;
                            case 2 : // 2 = Nota de Débito
                                $aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_debito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Débito")."\"/><a>",
                                    $rowPago['id_documento_pagado']);
                                $aVerDcto .= sprintf("<a href=\"javascript:verVentana('../cxc/reportes/cc_nota_cargo_pdf.php?valBusq=%s', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Débito PDF")."\"/><a>",
                                    $rowPago['id_documento_pagado']);
                                break;
                            case 3 : // 3 = Nota de Crédito
                                $aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_nota_credito_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Nota de Crédito")."\"/><a>",
                                    $rowPago['id_documento_pagado']);
                                switch ($rowPago['id_modulo_documento_pagado']) {
                                    case 0 : $aVerDctoAux = sprintf("../repuestos/reportes/iv_devolucion_venta_pdf.php?valBusq=%s", $rowPago['id_documento_pagado']); break;
                                    case 1 : $aVerDctoAux = sprintf("../servicios/reportes/sa_devolucion_venta_pdf.php?valBusq=%s", $rowPago['id_documento_pagado']); break;
                                    case 2 : $aVerDctoAux = sprintf("../vehiculos/reportes/an_devolucion_venta_pdf.php?valBusq=%s", $rowPago['id_documento_pagado']); break;
                                    case 3 : $aVerDctoAux = sprintf("../repuestos/reportes/ga_devolucion_venta_pdf.php?valBusq=%s", $rowPago['id_documento_pagado']); break;
                                    case 4 : $aVerDctoAux = sprintf("../alquiler/reportes/al_devolucion_venta_pdf.php?valBusq=%s", $rowPago['id_documento_pagado']); break;
                                    default : $aVerDctoAux = "";
                                }
                                $aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Nota de Crédito PDF")."\"/></a>" : "";
                                break;
                            case 4 : // 4 = Anticipo
                                $aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_anticipo_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Anticipo")."\"/><a>",
                                    $rowPago['id_documento_pagado']);
                                if (in_array($rowPago['id_modulo_documento_pagado'],array(2,4,5))) {
                                    $aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=4&id=%s", $rowPago['id_documento_pagado']);
                                } else if (in_array($rowPago['id_modulo_documento_pagado'],array(0,1,3))) {
                                    $aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=4&id=%s", $rowPago['id_documento_pagado']);
                                }
                                $aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
                                break;
                            case 5 : // 5 = Cheque
                                $aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_cheque_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Cheque")."\"/><a>",
                                    $rowPago['id_documento_pagado']);
                                if (in_array($rowPago['id_modulo_documento_pagado'],array(2,4,5))) {
                                    $aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=5&id=%s", $rowPago['id_documento_pagado']);
                                } else if (in_array($rowPago['id_modulo_documento_pagado'],array(0,1,3))) {
                                    $aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=5&id=%s", $rowPago['id_documento_pagado']);
                                }
                                $aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
                                break;
                            case 6 : // 6 = Transferencia
                                $aVerDcto = sprintf("<a id=\"aVerDcto\" href=\"../cxc/cc_transferencia_form.php?id=%s&acc=0\" target=\"_blank\"><img src=\"../img/iconos/ico_view.png\" title=\"".utf8_encode("Ver Transferencia")."\"/><a>",
                                    $rowPago['id_documento_pagado']);
                                if (in_array($rowPago['id_modulo_documento_pagado'],array(2,4,5))) {
                                    $aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idTpDcto=6&id=%s", $rowPago['id_documento_pagado']);
                                } else if (in_array($rowPago['id_modulo_documento_pagado'],array(0,1,3))) {
                                    $aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idTpDcto=6&id=%s", $rowPago['id_documento_pagado']);
                                }
                                $aVerDcto .= (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/page_white_acrobat.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
                                break;
                            default : $aVerDcto = "";
                        }
                        
                        // ESTATUS DEL PAGO
                        /*$classDcto = "";
                        $estatusDcto = "";
                        if ($rowPago['estatus'] == NULL) { // 0 = ANULADO
                            $classDcto = "divMsjError";
                            $estatusDcto = "<br>ANULADO";
                        }*/
                        
                        // PAGO DE FACTURA DEVUELTA
                        $classPago = "";
                        $estatusPago = "";
                        if (in_array($rowPago['estatus_pago'], array(NULL,0))
                        && date(spanDateFormat, strtotime($rowPago['fechaPago'])) == date(spanDateFormat, strtotime($rowPago['fecha_anulado']))){ // Null = Anulado, 1 = Activo, 2 = Pendiente
                            $classPago = "divMsjError";
                            $estatusPago = "[PAGO ANULADO]";
                        } else if (in_array($rowPago['estatus_pago'], array(NULL,0))
                        && date(spanDateFormat, strtotime($rowPago['fechaPago'])) != date(spanDateFormat, strtotime($rowPago['fecha_anulado']))){
                            $classPago = "divMsjInfo5";
                            $estatusPago = "[PAGO PENDIENTE ANULADO]";
                        } else if ($rowPago['estatus_pago'] == 2) {
                            $classPago = "divMsjAlerta";
                            $estatusPago = "[PAGO PENDIENTE]";
                        } else if (in_array($rowPago['id_concepto'], array(7,8,9))) {
                            $classPago = "divMsjAlerta";
                        }
                        $classPago .= ($rowPago['id_documento_pagado'] == $_GET['idDcto'] && $rowPago['idPago'] == $_GET['idPago']) ? " bordeTabla_3px" : "";
                        
                        $totalPagos += (in_array($rowPago['estatus_pago'], array(NULL,0)) && date(spanDateFormat, strtotime($rowPago['fechaPago'])) == date(spanDateFormat, strtotime($rowPago['fecha_anulado']))) ? 0 : $rowPago['montoPagado']; ?>
                        <tr align="left" class="<?php echo $clase; ?>" height="22">
                            <td align="center" class="<?php echo $classDcto." ".$classPago; ?>">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr align="center">
                                    <td width="100%"><?php echo utf8_encode($rowPago['tipo_documento_pagado']); ?></td>
                                </tr>
                                <?php
                                echo (strlen($estatusDcto) > 0) ? "<tr align=\"center\"><td class=\"textoNegritaCursiva_9px\">".$estatusDcto."</td></tr>" : "";
                                echo (strlen($estatusPago) > 0) ? "<tr align=\"center\"><td class=\"textoNegritaCursiva_9px\">".$estatusPago."</td></tr>" : ""; ?>
                                </table>
                            </td>
                            <td class="<?php echo $classDcto." ".$classPago; ?>">
                                <table width="100%">
                                <tr align="right">
                                    <td class="noprint" nowrap="nowrap"><?php echo $aVerDcto; ?></td>
                                    <td><?php echo $imgDctoModulo; ?></td>
                                    <td width="100%"><?php echo $rowPago['numero_documento']; ?></td>
                                </tr>
                                </table>
                            </td>
                            <td align="right" class="<?php echo $classDcto." ".$classPago; ?>">
                            <?php
                            switch ($rowPago['idTipoDeDocumento']) {
                                case 1 : // 1 = Factura
                                    if (in_array($rowPago['id_modulo_documento_pagado'],array(2,4,5))) {
                                        $aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s", $rowPago['id_recibo_pago']);
                                    } else if (in_array($rowPago['id_modulo_documento_pagado'],array(0,1,3))) {
                                        $aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s", $rowPago['id_recibo_pago']);
                                    }
                                    $aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
                                    break;
                                case 2 : // 2 = Nota de Débito
                                    if (in_array($rowPago['id_modulo_documento_pagado'],array(2,4,5))) {
                                        $aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_pago_pdf.php?idRecibo=%s", $rowPago['id_recibo_pago']);
                                    } else if (in_array($rowPago['id_modulo_documento_pagado'],array(0,1,3))) {
                                        $aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_pago_pdf.php?idRecibo=%s", $rowPago['id_recibo_pago']);
                                    }
                                    $aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
                                    break;
                                case 4 : // 4 = Anticipo
                                    if (in_array($rowPago['id_modulo_documento_pagado'],array(2,4,5))) {
                                        $aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s", $rowPago['id_recibo_pago']);
                                    } else if (in_array($rowPago['id_modulo_documento_pagado'],array(0,1,3))) {
                                        $aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s", $rowPago['id_recibo_pago']);
                                    }
                                    $aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
                                    break;
                                case 5 : // 5 = Cheque
                                    if (in_array($rowPago['id_modulo_documento_pagado'],array(2,4,5))) {
                                        $aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s", $rowPago['id_recibo_pago']);
                                    } else if (in_array($rowPago['id_modulo_documento_pagado'],array(0,1,3))) {
                                        $aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s", $rowPago['id_recibo_pago']);
                                    }
                                    $aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
                                    break;
                                case 6 : // 6 = Transferencia
                                    if (in_array($rowPago['id_modulo_documento_pagado'],array(2,4,5))) {
                                        $aVerDctoAux = sprintf("../caja_vh/reportes/cjvh_recibo_impresion_pdf.php?idRecibo=%s", $rowPago['id_recibo_pago']);
                                    } else if (in_array($rowPago['id_modulo_documento_pagado'],array(0,1,3))) {
                                        $aVerDctoAux = sprintf("../caja_rs/reportes/cjrs_recibo_impresion_pdf.php?idRecibo=%s", $rowPago['id_recibo_pago']);
                                    }
                                    $aVerDcto = (strlen($aVerDctoAux) > 0) ? "<a href=\"javascript:verVentana('".$aVerDctoAux."', 960, 550);\"><img src=\"../img/iconos/print.png\" title=\"".utf8_encode("Recibo(s) de Pago(s)")."\"/></a>" : "";
                                    break;
                                default : $aVerDcto = "";
                            } ?>
                                <table width="100%">
                                <tr>
                                    <td class="noprint"><?php echo $aVerDcto; ?></td>
                                    <td align="right"><?php echo $rowPago['nro_comprobante']; ?></td>
                                </tr>
                                </table>
                            </td>
                            <?php if (!in_array(idArrayPais,array(3))) { // EN PUERTO RICO NO MOSTRAR SSN ?>
                            <td align="right" class="<?php echo $classDcto." ".$classPago; ?>"><?php echo $rowPago['ci_cliente']; ?></td>
                            <?php } ?>
                            <td class="<?php echo $classDcto." ".$classPago; ?>"><?php echo utf8_encode(strtoupper($rowPago['id_cliente'].".- ".$rowPago['nombre_cliente'])); ?></td>
                            <td align="<?php echo ($rowPago['formaPago'] == 11) ? "center" : "right"; ?>" class="<?php echo $classDcto." ".$classPago; ?>">
                                <?php
                                echo ($rowPago['formaPago'] == 11) ? utf8_encode($rowPago['descripcion_concepto_forma_pago']) : $rowPago['numero_documento_pago'];
                                echo (strlen($rowPago['nombre_empleado_anulado']) > 0) ? "<div align=\"center\"><span class=\"texto_9px\">Anulado por:</span> <span class=\"textoNegrita_9px\">".$rowPago['nombre_empleado_anulado']."<br>(".date(spanDateFormat, strtotime($rowPago['fecha_anulado'])).")</span></div>" : ""; ?>
                            </td>
                            <td class="<?php echo $classDcto." ".$classPago; ?>"><?php echo utf8_encode($rowPago['nombre_banco_origen']); ?></td>
                            <td class="<?php echo $classDcto." ".$classPago; ?>"><?php echo utf8_encode($rowPago['nombre_banco_destino']); ?></td>
                            <td align="center" class="<?php echo $classDcto." ".$classPago; ?>"><?php echo $rowPago['cuentaEmpresa']; ?></td>
                            <td align="right" class="<?php echo $classDcto." ".$classPago; ?>"><?php echo number_format($rowPago['montoPagado'],2,".",","); ?></td>
                        </tr>
                    <?php
                    } ?>
                    <tr align="right" height="22">
                    	<?php 
							$colspanTotal = 9;
							if (in_array(idArrayPais,array(3))) { // EN PUERTO RICO NO MOSTRAR SSN 
								$colspanTotal = 8; //puerto no usa ssn y el colspan se reduce
							} 
						?>
                        <td class="tituloColumna" colspan="<?php echo $colspanTotal; ?>">Total en <?php echo $nombreFormaPago; ?>:</td>
                        <td class="trResaltarTotal"><?php echo number_format($totalPagos,2,".",","); ?></td>
                    </tr>
                    </table>
                <?php
                }
                echo "</br>";
            }
            $sw = $idFormaPago;
        }
        
        
        if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
            $andEmpresaVentaContadoFA = sprintf(" AND cxc_fact.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
            $andEmpresaVentaContadoND = sprintf(" AND cxc_nd.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
            $andEmpresaVentaContadoAN = sprintf(" AND cxc_ant.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
            $andEmpresaVentaContadoCH = sprintf(" AND cxc_ch.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
            $andEmpresaVentaContadoTB = sprintf(" AND cxc_tb.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
        } else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
            $andEmpresaVentaContadoFA = "";
            $andEmpresaVentaContadoND = "";
            $andEmpresaVentaContadoAN = "";
            $andEmpresaVentaContadoCH = "";
            $andEmpresaVentaContadoTB = "";
        }
        
        $queryVentaContado = "SELECT
            SUM(IFNULL(cxc_pago.montoPagado, 0)) AS total,
            cxc_pago.estatus,
            cxc_pago.estatus AS estatus_pago
        FROM cj_cc_encabezadofactura cxc_fact
            INNER JOIN an_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
        WHERE cxc_pago.fechaPago = '".$fechaApertura."'
            AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
            AND (cxc_pago.formaPago NOT IN (2,4)
                OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
                OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
            AND cxc_pago.tomadoEnComprobante = 1
            AND cxc_pago.tomadoEnCierre IN (0,1,2)
            AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
            AND cxc_pago.formaPago IN (SELECT idFormaPago FROM formapagos WHERE idFormaPago NOT IN (7,8))
            AND (cxc_pago.estatus IN (1)
                OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado)))
            ".$andEmpresaVentaContadoFA." ".$andCondicionPagoFA."
        
        UNION ALL
        
        SELECT
            SUM(IFNULL(cxc_pago.monto_pago, 0)) AS total,
            cxc_pago.estatus,
            cxc_pago.estatus AS estatus_pago
        FROM cj_cc_notadecargo cxc_nd
            INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
        WHERE cxc_pago.fechaPago = '".$fechaApertura."'
            AND cxc_nd.idDepartamentoOrigenNotaCargo IN (".valTpDato($idModuloPpal, "campo").")
            AND (cxc_pago.idFormaPago NOT IN (2,4)
                OR (cxc_pago.id_cheque IS NULL AND cxc_pago.idFormaPago IN (2))
                OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.idFormaPago IN (4)))
            AND cxc_pago.tomadoEnComprobante = 1
            AND cxc_pago.tomadoEnCierre IN (0,1,2)
            AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
            AND cxc_pago.idFormaPago IN (SELECT idFormaPago FROM formapagos WHERE idFormaPago NOT IN (7,8))
            AND (cxc_pago.estatus IN (1)
                OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado)))
            ".$andEmpresaVentaContadoND." ".$andCondicionPagoND."
        
        UNION ALL
        
        SELECT
            SUM(IFNULL(cxc_pago.montoDetalleAnticipo, 0)) AS total,
            cxc_ant.estatus,
            cxc_pago.estatus AS estatus_pago
        FROM cj_cc_anticipo cxc_ant
            INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
        WHERE cxc_pago.fechaPagoAnticipo = '".$fechaApertura."'
            AND cxc_ant.idDepartamento IN (".valTpDato($idModuloPpal, "campo").")
            AND (cxc_pago.id_forma_pago NOT IN (2,4)
                OR (cxc_pago.id_cheque IS NULL AND cxc_pago.id_forma_pago IN (2))
                OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.id_forma_pago IN (4)))
            AND cxc_pago.tomadoEnCierre IN (0,1,2)
            AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
            AND cxc_pago.tipoPagoDetalleAnticipo IN (SELECT aliasFormaPago FROM formapagos)
            AND (cxc_pago.id_concepto IS NULL OR cxc_pago.id_concepto IN (SELECT id_concepto FROM cj_conceptos_formapago WHERE id_concepto NOT IN (7,8,9)))
            AND cxc_ant.estatus IN (1)
            AND (cxc_pago.estatus IN (1)
                OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado)))
            ".$andEmpresaVentaContadoAN." ".$andCondicionPagoAN."
        GROUP BY cxc_ant.estatus
        
        UNION ALL
        
        SELECT 
            SUM(IFNULL(cxc_ch.total_pagado_cheque, 0)) AS total,
            cxc_ch.estatus,
            cxc_ch.estatus AS estatus_pago
        FROM cj_cc_cheque cxc_ch
        WHERE cxc_ch.fecha_cheque = '".$fechaApertura."'
            AND cxc_ch.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
            AND cxc_ch.tomadoEnComprobante = 1
            AND cxc_ch.tomadoEnCierre IN (0,1,2)
            AND (cxc_ch.idCierre = 0 OR cxc_ch.idCierre IS NULL)
            AND (cxc_ch.estatus IN (1)
                OR (cxc_ch.estatus IS NULL AND cxc_ch.fecha_cheque <> DATE(cxc_ch.fecha_anulado)))
            ".$andEmpresaPagoCH." ".$andCondicionPagoCH."
        GROUP BY cxc_ch.estatus
        
        UNION ALL
        
        SELECT 
            SUM(IFNULL(cxc_tb.total_pagado_transferencia, 0)) AS total,
            cxc_tb.estatus,
            cxc_tb.estatus AS estatus_pago
        FROM cj_cc_transferencia cxc_tb
        WHERE cxc_tb.fecha_transferencia = '".$fechaApertura."'
            AND cxc_tb.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
            AND cxc_tb.tomadoEnComprobante = 1
            AND cxc_tb.tomadoEnCierre IN (0,1,2)
            AND (cxc_tb.idCierre = 0 OR cxc_tb.idCierre IS NULL)
            AND (cxc_tb.estatus IN (1)
                OR (cxc_tb.estatus IS NULL AND cxc_tb.fecha_transferencia <> DATE(cxc_tb.fecha_anulado)))
            ".$andEmpresaPagoTB." ".$andCondicionPagoTB."
        GROUP BY cxc_tb.estatus;"; //echo $queryVentaContado."<br><br>";
        $rsVentaContado = mysql_query($queryVentaContado);
        if (!$rsVentaContado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
        $totalVentaContado = 0;
        while ($rowVentaContado = mysql_fetch_assoc($rsVentaContado)) {
            $totalVentaContado += $rowVentaContado['total'];
        }
        
        
        if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
            $andEmpresaVentaCredito = sprintf(" AND cxc_fact.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
        } else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
            $andEmpresaVentaCredito = "";
        }
        
        $queryVentaCredito = "SELECT
            SUM(cxc_fact.montoTotalFactura) AS monto_total_ventas_credito
        FROM cj_cc_encabezadofactura cxc_fact
        WHERE cxc_fact.fechaRegistroFactura = '".$fechaApertura."'
            AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
            AND cxc_fact.condicionDePago = 0
            AND cxc_fact.anulada <> 'SI' ".$andEmpresaVentaCredito.";";
        $rsVentaCredito = mysql_query($queryVentaCredito);
        if (!$rsVentaCredito) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
        $rowVentaCredito = mysql_fetch_assoc($rsVentaCredito); ?>
	
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr id="tblVentasAContado" align="right" height="24">
            <td class="tituloColumna" width="94%">Total Pagos:</td>
            <td class="trResaltarTotal2" width="6%"><?php echo number_format($totalVentaContado, 2, ".", ","); ?></td>
        </tr>
        <tr id="tblVentasACredito" align="right" height="24" <?php if (count($arrayTipoPago) > 0) { echo "style=\"display:none\""; } ?>>
            <td class="tituloColumna">Ventas a Crédito:</td>
            <td class="trResaltarTotal2"><?php echo number_format($rowVentaCredito['monto_total_ventas_credito'], 2, ".", ","); ?></td>
        </tr>
        <tr id="tblVentasContadoCredito" align="right" height="24" <?php if (count($arrayTipoPago) > 0) { echo "style=\"display:none\""; } ?>>
            <td class="tituloColumna">Total Pagos + Ventas a Crédito:</td>
            <td class="trResaltarTotal3"><?php echo number_format($rowVentaCredito['monto_total_ventas_credito'] + $totalVentaContado, 2, ".", ","); ?></td>
        </tr>
        <tr align="left">
            <td>&nbsp;</td>
        </tr>
        <tr align="left">
            <td colspan="2" class="tituloCampo">Observaci&oacute;n:</td>
        </tr>
        <tr align="left">
            <td colspan="2"><textarea id="txtObservacionCierre" name="txtObservacionCierre" rows="3" style="width:99%"><?php echo $_GET['txtObservacionCierre']; ?></textarea></td>
        </tr>
        </table>
	
        <table id="divButtons" name="divButtons" border="0" style="display:none" width="100%">
        <tr id="infoPagosNoRealizados" style="visibility:hidden">
            <td class="divMsjInfo2">
                <table width="76%" align="center">
                <tr>
                    <td width="6%"><img src="../img/iconos/ico_info.gif" alt=""/></td>
                    <td width="94%">
                        No puede realizar el cierre, debido a que existen facturas de contado sin pagos o con pagos parciales.<br/>
                        <div id="facurasSinPagos"></div>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="right"><hr>
                <button type="button" id="btnImprimir" name="btnImprimir" onclick="printPage();" style="cursor:default"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                <button type="button" id="btnCerrarCaja" name="btnCerrarCaja" onclick="if (confirm('Desea Cerrar La Caja?')){ xajax_cerrarCaja(xajax.getFormValues('frmCerrar')); }" style="display:none" value="Cerrar Caja"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/lock.png"/></td><td>&nbsp;</td><td>Cerrar Caja</td></tr></table></button>
                <button type="button" id="btnVolver" name="btnVolver" onclick="top.history.back()"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/return.png"/></td><td>&nbsp;</td><td>Volver</td></tr></table></button>
                
                <input type="hidden" id="hddPresionoImprimirCorteCaja" name="hddPresionoImprimirCorteCaja" value="0"/>
                <input type="hidden" id="hddExisteRegistro" name="hddExisteRegistro" value="0"/>
                <input type="hidden" id="hddCierreCaja" name="hddCierreCaja" value="1"/>
            </td>
        </tr>
        </table>
    </form>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script>
byId('txtObservacionCierre').className = 'inputHabilitado';

<?php if ($sw == "") { ?>
	byId("btnImprimir").disabled = true;
	byId("hddExisteRegistro").value = 1;
<?php }?>

<?php if (!isset($arrayTipoPago)) { ?>
	xajax_validarDepositos('<?php echo $_GET['acc']; ?>');
<?php } ?>
</script>
