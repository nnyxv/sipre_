<?php
require_once ("../connections/conex.php");
require_once ("inc_caja.php");

session_start();

/* Validación del Módulo */
include('../inc_sesion.php');
if((!validaAcceso("cjrs_cierre_caja") && !isset($_GET['tipoPago']))
|| (!validaAcceso("cjrs_cierre_caja_credito") && isset($_GET['tipoPago']) && in_array(0, explode(",",$_GET['tipoPago'])))
|| (!validaAcceso("cjrs_cierre_caja_contado") && isset($_GET['tipoPago']) && in_array(1, explode(",",$_GET['tipoPago'])))
|| (!validaAcceso("cjrs_cierre_caja_todo") && isset($_GET['tipoPago']) && in_array(0, explode(",",$_GET['tipoPago'])) && in_array(1, explode(",",$_GET['tipoPago'])))) {
	echo "<script> alert('Acceso Denegado'); top.history.back(); </script>";
}
/* Fin Validación del Módulo */

require ('../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../controladores/xajax/');

include("../controladores/ac_iv_general.php");

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
if (!$rsConfig400) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
$totalRowsConfig400 = mysql_num_rows($rsConfig400);
$rowConfig400 = mysql_fetch_assoc($rsConfig400);

$updateSQL = sprintf("UPDATE cj_cc_detalleanticipo cxc_ant SET
    id_forma_pago = (SELECT idFormaPago FROM formapagos WHERE aliasFormaPago LIKE cxc_ant.tipoPagoDetalleAnticipo)
WHERE id_forma_pago IS NULL;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);

$updateSQL = sprintf("UPDATE cj_cc_detalleanticipo cxc_ant SET
    id_reporte_impresion = (SELECT idReporteImpresion FROM pg_reportesimpresion
							WHERE tipoDocumento LIKE 'AN'
								AND idDocumento = cxc_ant.idAnticipo
								AND fechaDocumento = cxc_ant.fechaPagoAnticipo)
WHERE cxc_ant.id_reporte_impresion IS NULL;");
$Result1 = mysql_query($updateSQL);
if (!$Result1) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>.: SIPRE <?php echo cVERSION; ?> :. <?php echo $nombreCajaPpal; ?> - Corte de Caja <?php echo (count($arrayTipoPago) == 1) ? $arrayDescripcionTipoPago[$arrayTipoPago[0]] : ""; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../controladores/xajax/'); ?>
    
	<link rel="stylesheet" type="text/css" href="../style/styleRafk.css"/>
    
	<link rel="stylesheet" type="text/css" href="../js/domDragCajaRS.css"/>
    
	<script type="text/javascript" >
	function realizarCierreParcial(caja){
		if (confirm("Esta seguro de realizar el cierre Parcial?")){
			return true
		} else {
			return false
		}
	}
	
	function realizarCierreTotal(caja){
		if (confirm("Esta seguro de realizar el cierre Total?")){
			return true
		} else {
			return false
		}
	}
	
	function validarFrmCierreCaja() {
		/*byId('frmCierre').action = "cjrs_pagos_cargados_dia.php";
		byId('frmCierre').method = "get";*/
		window.location.href = 'cjrs_pagos_cargados_dia.php?acc=2&txtObservacionCierre=' + byId('txtObservacionCierre').value;
	}
	</script>
</head>
<body class="bodyVehiculos">
<div id="divGeneralPorcentaje">
    <div class="noprint"><?php include("banner_cjrs.php"); ?></div>
    
    <div id="divInfo">
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
        	<td align="center" class="tituloPaginaCajaRS">Corte de Caja <?php echo (count($arrayTipoPago) == 1) ? $arrayDescripcionTipoPago[$arrayTipoPago[0]] : ""; ?> (<?php echo $nombreCajaPpal; ?>)<br/><span class="textoNegroNegrita_10px">(Resumen de Saldos)</span></td>
        </tr>
        </table>
        
        <?php
        if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
            $andEmpresaAPER = sprintf(" AND ape.id_empresa = %s",
                valTpDato($idEmpresa, "int"));
        } else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
            $andEmpresaAPER = "";
        }
        
        $queryAperturaCaja = sprintf("SELECT
            cierre.fechaCierre,
            ape.fechaAperturaCaja,
            cierre.tipoCierre,
            ape.cargaEfectivoCaja, 
            ape.id_empresa,
            emp.nombre_empresa,
            cierre.fechaCierre
        FROM ".$apertCajaPpal." ape
            INNER JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
            INNER JOIN pg_empresa emp ON (emp.id_empresa = ape.id_empresa)
        WHERE cierre.idCierre = (SELECT MAX(cierre2.idCierre)
                                FROM ".$apertCajaPpal." ape2
                                    INNER JOIN ".$cierreCajaPpal." cierre2 ON (ape2.id = cierre2.id)
                                WHERE ape2.idCaja = %s) %s",
			valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
			$andEmpresaAPER);
        $rsAperturaCaja = mysql_query($queryAperturaCaja);
        if (!$rsAperturaCaja) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
        if ($rowAperturaCaja = mysql_fetch_array($rsAperturaCaja)){
            $txtFechaActual = date("Y-m-d");
            $nombreEmpresa = $rowAperturaCaja['nombre_empresa'];
            $montoApertura = $rowAperturaCaja['cargaEfectivoCaja'];
            
            if (($rowAperturaCaja['fechaCierre'] != $txtFechaActual || $rowAperturaCaja['fechaAperturaCaja'] != $txtFechaActual) && !(count($arrayTipoPago) > 0)) {
                $fechaApertura = date(spanDateFormat, strtotime($rowAperturaCaja['fechaAperturaCaja']));
                $fechaCierreCaja = date(spanDateFormat, strtotime($rowAperturaCaja['fechaCierre']));
                if ($rowAperturaCaja['tipoCierre'] == '0') { $cierre = "Total"; } else if ($rowAperturaCaja['tipoCierre'] == '2') { $cierre = "Parcial"; } ?>
                <script type="text/javascript">
                alert('La caja esta abierta para la empresa <?php echo $nombreEmpresa;?> con fecha <?php echo $fechaApertura;?>.' +
                '<?php if ($rowAperturaCaja['tipoCierre'] != '1') { ?>\nCierre <?php echo $cierre." ".$fechaCierreCaja; } ?>' +
                '\nDebe realizar el cierre de la caja anterior.');
                </script>
        <?php
            }
        }
        
        saldosDeCierre($montoApertura); ?>
        
        <form id="frmCierre" name="frmCierre">
            <?php
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
            $totalRowsAperturaCaja = mysql_num_rows($rsAperturaCaja);
            while ($rowAperturaCaja = mysql_fetch_assoc($rsAperturaCaja)) {
                $fechaApertura = $rowAperturaCaja['fechaAperturaCaja'];
                $cargaEfectivoCaja = $rowAperturaCaja['cargaEfectivoCaja'];
                $saldoCaja = $rowAperturaCaja['saldoCaja'];
                
                switch($rowAperturaCaja['statusAperturaCaja']) {
                    case 0 : $classApertura = "divMsjError"; break;
                    case 1 : $classApertura = "divMsjInfo"; break;
                    case 2 : $classApertura = "divMsjAlerta"; break;
                } ?>
                <table border="0" align="center" width="860">
                <tr align="left">
                	<td colspan="5">
		                <table width="100%">
                        <tr align="left" height="24">
                            <td width="15%"></td>
                            <td width="14%"></td>
                            <td width="15%"></td>
                            <td width="18%"></td>
                            <td align="right" class="tituloCampo" width="15%">Fecha Impresión:</td>
                            <td align="center" width="23%"><input type="text" id="txtFechaActual" name="txtFechaActual" class="inputCompleto" readonly="readonly" style="text-align:center;" value="<?php echo date(spanDateFormat); ?>"/></td>
                        </tr>
                        <tr align="left" height="24">
                            <td align="right" class="tituloCampo">Fecha Apertura:</td>
                            <td align="center"><input type="text" id="txtFechaApertura" name="txtFechaApertura" class="inputCompleto" readonly="readonly" style="text-align:center;" value="<?php echo date(spanDateFormat, strtotime($rowAperturaCaja['fechaAperturaCaja'])); ?>"/></td>
                            <td align="right" class="tituloCampo">Ejecución Apertura:</td>
                            <td align="center"><input type="text" id="txtEjecucionApertura" name="txtEjecucionApertura" class="inputCompleto" readonly="readonly" style="text-align:center;" value="<?php echo date(spanDateFormat." H:i:s", strtotime($rowAperturaCaja['fechaAperturaCaja']." ".$rowAperturaCaja['horaApertura'])); ?>"/></td>
                            <td align="right" class="tituloCampo">Empleado Apertura:</td>
                            <td><?php echo $rowAperturaCaja['empleado_apertura']; ?></td>
                        </tr>
                        <tr align="left" height="24" <?php if (in_array($rowAperturaCaja['statusAperturaCaja'], array(1,2))) { echo "style=\"display:none\""; } ?>>
                            <td align="right" class="tituloCampo">Fecha Cierre:</td>
                            <td align="center"><input type="text" id="txtFechaCierre" name="txtFechaCierre" class="inputCompleto" readonly="readonly" style="text-align:center;" value="<?php echo date(spanDateFormat, strtotime($rowAperturaCaja['fechaCierre'])); ?>"/></td>
                            <td align="right" class="tituloCampo">Ejecución Cierre:</td>
                            <td align="center"><input type="text" id="txtEjecucionCierre" name="txtEjecucionCierre" class="inputCompleto" readonly="readonly" style="text-align:center;" value="<?php echo date(spanDateFormat." H:i:s", strtotime($rowAperturaCaja['fechaEjecucionCierre']." ".$rowAperturaCaja['horaEjecucionCierre'])); ?>"/></td>
                            <td align="right" class="tituloCampo">Empleado Cierre:</td>
                            <td><?php echo $rowAperturaCaja['empleado_cierre']; ?></td>
                        </tr>
                        <tr align="left" height="24">
                        	<td></td>
                        	<td></td>
                        	<td align="right" class="tituloCampo">Saldo Apertura:</td>
                            <td align="center"><input type="text" id="txtCargaEnEfectivo" name="txtCargaEnEfectivo" class="inputCompleto" readonly="readonly" style="text-align:right;" value="<?php echo number_format($cargaEfectivoCaja, 2, ".", ","); ?>"/></td>
                        	<td align="right" class="tituloCampo">Estado de Caja:</td>
                            <td align="center"><input type="text" id="txtEstadoCaja" name="txtEstadoCaja" class="<?php echo $classApertura; ?>" readonly="readonly" style="text-align:center; width:99%" value="<?php echo $rowAperturaCaja['estatus_apertura_caja']; ?>"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td width="28%">&nbsp;</td>
                    <td width="22%">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td width="28%">&nbsp;</td>
                    <td width="22%">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="2" align="center" class="tituloCaja">PAGOS</td>
                    <td>&nbsp;</td>
                    <td colspan="2" align="center" class="tituloCaja">VENTAS</td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Efectivo</td>
                    <td><input type="text" id="txtTotalEfectivo" name="txtTotalEfectivo" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                    <td>&nbsp;</td>
                    <td class="tituloColumna" <?php if (count($arrayTipoPago) == 1 && in_array(0, $arrayTipoPago)) { echo "style=\"display:none\""; } ?>>Ventas a Contado:</td>
                    <td align="right" <?php if (count($arrayTipoPago) == 1 && in_array(0, $arrayTipoPago)) { echo "style=\"display:none\""; } ?>>
                        <?php
                        if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
                            $andEmpresa = sprintf(" AND cxc_fact.id_empresa = %s",
                                valTpDato($idEmpresa, "int"));
                        } else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
                            $andEmpresa = "";
                        }
                        
                        $queryVentaContado = sprintf("SELECT
                            SUM(cxc_fact.montoTotalFactura) AS monto_total
                        FROM cj_cc_encabezadofactura cxc_fact
                        WHERE cxc_fact.condicionDePago = 1
                            AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
                            AND cxc_fact.fechaRegistroFactura = '%s'
                            AND cxc_fact.idFactura NOT IN (SELECT cxc_nc.idDocumento FROM cj_cc_notacredito cxc_nc
                                                            WHERE cxc_nc.fechaNotaCredito = '%s'
                                                                AND cxc_nc.tipoDocumento LIKE 'FA') %s",
                            $fechaApertura,
                            $fechaApertura,
                            $andEmpresa);
                        $rsVentaContado = mysql_query($queryVentaContado);
                        if (!$rsVentaContado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
                        $rowVentaContado = mysql_fetch_assoc($rsVentaContado); ?>
                        <input type="text" id="txtVentaContado" name="txtVentaContado" class="trResaltarTotal" readonly="readonly" style="text-align:right" value="<?php echo number_format($rowVentaContado['monto_total'], 2, ".", ","); ?>"/>
                    </td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Cheques</td>
                    <td><input type="text" id="txtTotalCheques" name="txtTotalCheques" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                    <td>&nbsp;</td>
                    <td class="tituloColumna" <?php if (count($arrayTipoPago) == 1 && in_array(1, $arrayTipoPago)) { echo "style=\"display:none\""; } ?>>Ventas a Crédito:</td>
                    <td align="right" <?php if (count($arrayTipoPago) == 1 && in_array(1, $arrayTipoPago)) { echo "style=\"display:none\""; } ?>>
                        <?php
                        if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
                            $andEmpresa = sprintf(" AND cxc_fact.id_empresa = %s",
                                valTpDato($idEmpresa, "int"));
                        } else if ($rowConfig400['valor'] == 1) { // MOSTRAR POR EMPRESAS
                            $andEmpresa = "";
                        }
                        
                        $queryVentaCredito = sprintf("SELECT
                            SUM(cxc_fact.montoTotalFactura) AS monto_total
                        FROM cj_cc_encabezadofactura cxc_fact
                        WHERE cxc_fact.condicionDePago = 0
                            AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
                            AND cxc_fact.fechaRegistroFactura = '%s'
                            AND ((cxc_fact.anulada = 'SI'
                                    AND (SELECT COUNT(cxc_nc.idNotaCredito) FROM cj_cc_notacredito cxc_nc
                                        WHERE cxc_nc.idDocumento = cxc_fact.idFactura
                                            AND cxc_nc.tipoDocumento LIKE 'FA'
                                            AND cxc_nc.fechaNotaCredito = cxc_fact.fechaRegistroFactura) = 0)
                                OR cxc_fact.anulada <> 'SI') %s",
                            $fechaApertura, $andEmpresa);
                        $rsVentaCredito = mysql_query($queryVentaCredito);
                        if (!$rsVentaCredito) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
                        $rowVentaCredito = mysql_fetch_assoc($rsVentaCredito); ?>
                        <input type="text" id="txtVentaCredito" name="txtVentaCredito" class="trResaltarTotal" readonly="readonly" style="text-align:right" value="<?php echo number_format($rowVentaCredito['monto_total'], 2, ".", ","); ?>"/>
                    </td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Depósitos</td>
                    <td><input type="text" id="txtTotalDepositos" name="txtTotalDepositos" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                    <td></td>
                    <td colspan="2" rowspan="5" valign="top">
                    	<table width="100%">
                        <tr>
							<td align="center" class="tituloCaja" colspan="2">COBRANZAS</td>
                        </tr>
                        <?php
                        if (isset($arrayCobranza)) {
                            foreach ($arrayCobranza as $indice => $valor) {
                                echo "<tr align=\"left\">".
                                    "<td class=\"tituloCampo\" width=\"52%\">".utf8_encode($arrayCobranza[$indice]['descripcion'])."</td>".
                                    "<td width=\"48%\"><input type=\"text\" name=\"txtCobranza".$indice."\" id=\"txtCobranza".$indice."\" class=\"inputCompleto\" value=\"".number_format($arrayCobranza[$indice]['total_cobranza'], 2, ".", ",")."\" style=\"text-align:right\" readonly=\"readonly\"/></td>".
                                "</tr>";
                            }
                        } ?>
                        <tr align="left">
                            <td class="tituloColumna" width="52%">Total Cobranzas:</td>
                            <td align="right" width="48%"><input type="text" id="txtTotalCobranza" name="txtTotalCobranza" class="trResaltarTotal" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Tarjeta de Cr&eacute;dito</td>
                    <td><input type="text" id="txtTotalTDC" name="txtTotalTDC" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                    <td></td>
                </tr>
                <?php
				if (isset($arrayTipoTarjeta)) {
					foreach ($arrayTipoTarjeta as $indiceTipoTarjeta => $valorTipoTarjeta) {
						echo "<tr align=\"right\">".
							"<td>".
								"<table cellpadding=\"0\" cellspacing=\"0\" width=\"90%\">".
								"<tr>".
									"<td class=\"tituloCampo\" width=\"42%\">&nbsp;".utf8_encode($arrayTipoTarjeta[$indiceTipoTarjeta]['descripcion'])."</td>".
									"<td>&nbsp;</td>".
									"<td width=\"58%\"><input type=\"text\" name=\"txtTipoTarjeta".$indiceTipoTarjeta."\" id=\"txtTipoTarjeta".$indiceTipoTarjeta."\" class=\"".$arrayTipoTarjeta[$indiceTipoTarjeta]['classTipoTarjeta']."\" value=\"".number_format($arrayTipoTarjeta[$indiceTipoTarjeta]['total_tipo_tarjeta'], 2, ".", ",")."\" style=\"text-align:right\" readonly=\"readonly\"/></td>".
								"</tr>".
								"</table>".
							"</td>".
						"</tr>";
					}
				} ?>
                <tr align="left">
                    <td class="tituloCampo">Tarjeta de D&eacute;bito</td>
                    <td><input type="text" id="txtTotalTDD" name="txtTotalTDD" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                    <td></td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Transferencia Bancaria</td>
                    <td><input type="text" id="txtTotalTransferencia" name="txtTotalTransferencia" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                    <td></td>
                </tr>
                <tr align="left">
                    <td class="tituloColumna">Subtotal Ingresos:</td>
                    <td align="right"><input type="text" id="txtSubtotalIngresos" name="txtSubtotalIngresos" class="trResaltarTotal" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Retenci&oacute;n I.S.L.R</td>
                    <td><input type="text" id="txtTotalRetencionISLR" name="txtTotalRetencionISLR" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                    <td></td>
                    <td colspan="2" rowspan="10" valign="top">
                        <table width="100%">
                        <tr>
                            <td align="center" class="tituloCaja" colspan="2">OTROS</td>
                        </tr>
                        <?php
                        if (isset($arrayOtro)) {
                            foreach ($arrayOtro as $indiceOtro => $valorOtro) {
                                echo "<tr align=\"left\">".
                                    "<td class=\"tituloCampo\" width=\"52%\">".utf8_encode($arrayOtro[$indiceOtro]['descripcion'])."</td>".
                                    "<td width=\"48%\"><input type=\"text\" name=\"txtOtro".$indiceOtro."\" id=\"txtOtro".$indiceOtro."\" class=\"".$arrayOtro[$indiceOtro]['classConcepto']."\" value=\"".number_format($arrayOtro[$indiceOtro]['total_forma_pago_concepto'], 2, ".", ",")."\" style=\"text-align:right\" readonly=\"readonly\"/></td>".
                                "</tr>";
                            }
                        } ?>
                        <tr align="left">
                            <td class="tituloColumna">Subtotal Otros:</td>
                            <td align="right"><input type="text" id="txtTotalOtro" name="txtTotalOtro" class="trResaltarTotal" readonly="readonly" style="text-align:right"/></td>
                        </tr>
                        </table>
                    </td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Retenci&oacute;n I.V.A.</td>
                    <td><input type="text" id="txtTotalRetencionIVA" name="txtTotalRetencionIVA" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Otros Impuestos</td>
                    <td><input type="text" id="txtOtrosImpuestos" name="txtOtrosImpuestos" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr align="left">
                    <td class="tituloColumna">Subtotal Impuestos:</td>
                    <td align="right"><input type="text" id="txtSubtotalImpuestos" name="txtSubtotalImpuestos" class="trResaltarTotal" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr align="left">
                    <td>&nbsp;</td>
                </tr>
                <tr align="left">
                    <td class="tituloColumna">Total Pagos:</td>
                    <td align="right"><input type="text" id="txtTotalPagos" name="txtTotalPagos" class="trResaltarTotal" readonly="readonly" style="text-align:right;"/></td>
                </tr>
                <tr align="left" <?php if (count($arrayTipoPago) == 1 && in_array(1, $arrayTipoPago)) { echo "style=\"display:none\""; } ?>>
                    <td class="tituloColumna">Total Pagos + Ventas a Crédito:</td>
                    <td align="right"><input type="text" id="txtTotalPagosCredito" name="txtTotalPagosCredito" class="trResaltarTotal3" readonly="readonly" style="text-align:right;"/></td>
                </tr>
                <tr>
                    <td colspan="2" align="center" class="tituloCaja">OTROS PAGOS</td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Anticipos Aplicados</td>
                    <td><input type="text" id="txtTotalAnticipo" name="txtTotalAnticipo" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Anticipos Aplicados<br><span class="textoNegrita_10px">(No Cancelados)</span></td>
                    <td><input type="text" id="txtTotalAnticipoNoCancelado" name="txtTotalAnticipoNoCancelado" class="inputCompleto" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr align="left">
                    <td class="tituloCampo">Notas Crédito Aplicadas</td>
                    <td><input type="text" id="txtTotalNotaCredito" name="txtTotalNotaCredito" class="inputCompleto" readonly="readonly" style="text-align:right;"/></td>
                </tr>
                <tr align="left">
                    <td class="tituloColumna">Subtotal Otros Pagos:</td>
                    <td align="right"><input type="text" id="txtSubtotalOtrosPagos" name="txtSubtotalOtrosPagos" class="trResaltarTotal" readonly="readonly" style="text-align:right"/></td>
                </tr>
                <tr align="left">
                    <td>&nbsp;</td>
                </tr>
                <tr align="left" <?php if (in_array($rowAperturaCaja['statusAperturaCaja'], array(1,2))) { echo "style=\"display:none\""; } ?>>
                    <td class="tituloCampo">Primer Nro. Control:</td>
                    <td><input type="text" id="pNroControl" name="pNroControl" class="inputCompleto" readonly="readonly" style="text-align:right;"/></td>
                    <td></td>
                    <td class="tituloCampo">Ultimo Nro. Control:</td>
                    <td><input type="text" id="uNroControl" name="uNroControl" class="inputCompleto" readonly="readonly" style="text-align:right;"/></td>
                </tr>
                <tr align="left" <?php if (in_array($rowAperturaCaja['statusAperturaCaja'], array(1,2))) { echo "style=\"display:none\""; } ?>>
                    <td class="tituloCampo">Primer Nro. Factura:</td>
                    <td><input type="text" id="pNroFactura" name="pNroFactura" class="inputCompleto" readonly="readonly" style="text-align:right;"/></td>
                    <td></td>
                    <td class="tituloCampo">Ultimo Nro. Factura:</td>
                    <td><input type="text" id="uNroFactura" name="uNroFactura" class="inputCompleto" readonly="readonly" style="text-align:right;"/></td>
                </tr>
                <tr align="left" <?php if (in_array($rowAperturaCaja['statusAperturaCaja'], array(1,2))) { echo "style=\"display:none\""; } ?>>
                    <td class="tituloCampo">Primer Nro. Nota Crédito:</td>
                    <td><input type="text" id="pNroNotaCredito" name="pNroNotaCredito" class="inputCompleto" readonly="readonly" style="text-align:right;"/></td>
                    <td></td>
                    <td class="tituloCampo">Ultimo Nro. Nota Crédito:</td>
                    <td><input type="text" id="uNroNotaCredito" name="uNroNotaCredito" class="inputCompleto" readonly="readonly" style="text-align:right;"/></td>
                </tr>
                <tr align="left" <?php if (in_array($rowAperturaCaja['statusAperturaCaja'], array(1,2))) { echo "style=\"display:none\""; } ?>>
                    <td>&nbsp;</td>
                </tr>
                <tr align="left">
                    <td colspan="5" class="tituloCampo">Observaci&oacute;n:</td>
                </tr>
                <tr align="left">
                    <td colspan="5"><textarea id="txtObservacionCierre" name="txtObservacionCierre" rows="3" style="width:99%"></textarea></td>
                </tr>
                <tr>
                    <td align="right" colspan="5" class="noprint"><hr>
                        <button type="button" id="btnImprimir" name="btnImprimir" onclick="window.print();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/ico_print.png"/></td><td>&nbsp;</td><td>Imprimir</td></tr></table></button>
                   <?php if (!(count($arrayTipoPago) > 0)) { ?>
                        <button type="button" id="btnCierreTotal" name="btnCierreTotal" onclick="validarFrmCierreCaja();"><table align="center" cellpadding="0" cellspacing="0"><tr><td>&nbsp;</td><td><img src="../img/iconos/key_go.png"/></td><td>&nbsp;</td><td>Recibos por Medio de Pago</td></tr></table></button>
                   <?php } ?>
                        <input type="hidden" id="ocultoTipoDeCierreDeCaja" name="ocultoTipoDeCierreDeCaja" value="1"/>
                    </td>
                </tr>
                </table>
                <br/>
                <br/>
                <table id="tabla_usuario" align="center" border="0" width="840">
                <tr>
                    <td width="30%" style="border-bottom:2px solid #000000;">&nbsp;</td>
                    <td width="5%">&nbsp;</td>
                    <td width="30%" style="border-bottom:2px solid #000000;">&nbsp;</td>
                    <td width="5%">&nbsp;</td>
                    <td width="30%" style="border-bottom:2px solid #000000;">&nbsp;</td>
                </tr>
                <tr align="center" style="font-size:12px;">
                    <td class="tituloColumna">Elaborado por:</td>
                    <td>&nbsp;</td>
                    <td class="tituloColumna">Revisado por:</td>
                    <td>&nbsp;</td>
                    <td class="tituloColumna">Revisado por:</td>
                </tr>
                <tr align="center" style="font-size:12px;">
                    <td>
                        <?php
                        $queryEmpleado = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado
						WHERE vw_pg_empleado.id_empleado = %s
							AND vw_pg_empleado.activo = 1;",
							(($rowAperturaCaja['id_empleado_cierre'] > 0) ? $rowAperturaCaja['id_empleado_cierre'] : $rowAperturaCaja['id_empleado_apertura']));
                        $rsEmpleado = mysql_query($queryEmpleado);
                        if (!$rsEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
                        if ($rowEmpleado = mysql_fetch_array($rsEmpleado)) {
                            echo utf8_encode($rowEmpleado['nombre_empleado']);
                        } ?>
                    </td>
                    <td>&nbsp;</td>
                    <td>
                        <?php
                        $queryEmpleado = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado
						WHERE vw_pg_empleado.id_empresa = %s
							AND vw_pg_empleado.clave_filtro IN (%s)
							AND vw_pg_empleado.activo = 1;",
							valTpDato($idEmpresa, "int"),
							valTpDato(9, "int")); // 9 = JEFE FACTURACION Y COBRANZA
                        $rsEmpleado = mysql_query($queryEmpleado);
                        if (!$rsEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
                        if ($rowEmpleado = mysql_fetch_array($rsEmpleado)) {
                            echo utf8_encode($rowEmpleado['nombre_empleado']);
                        } ?>
                    </td>
                    <td>&nbsp;</td>
                    <td>
                        <?php
                        $queryEmpleado = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado
						WHERE vw_pg_empleado.id_empresa = %s
							AND vw_pg_empleado.clave_filtro IN (%s)
							AND vw_pg_empleado.activo = 1;",
							valTpDato($idEmpresa, "int"),
							valTpDato(3, "int")); // 3 = GERENTE ADMINISTRATIVO
                        $rsEmpleado = mysql_query($queryEmpleado);
                        if (!$rsEmpleado) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
                        if ($rowEmpleado = mysql_fetch_array($rsEmpleado)) {
                            echo utf8_encode($rowEmpleado['nombre_empleado']);
                        } ?>
                    </td>
                </tr>
                </table>
            <?php
            }
            
            if (!($totalRowsAperturaCaja > 0)) { ?>
                <script type="text/javascript">
                alert("Esta Caja No tiene Apertura.");
                window.location.href="cjrs_apertura_caja.php";
                </script>
            <?php
            } ?>
        </form>
    </div>
    
    <div class="noprint"><?php include("pie_pagina.php"); ?></div>
</div>
</body>
</html>

<script type="text/javascript">
byId('txtObservacionCierre').className = 'inputHabilitado';

document.getElementById("txtTotalEfectivo").value = "<?php echo number_format($txtTotalEfectivo, 2, ".", ","); ?>";
document.getElementById("txtTotalCheques").value = "<?php echo number_format($txtTotalCheques, 2, ".", ","); ?>";
document.getElementById("txtTotalDepositos").value = "<?php echo number_format($txtTotalDepositos, 2, ".", ","); ?>";
document.getElementById("txtTotalTransferencia").value = "<?php echo number_format($txtTotalTransferencia, 2, ".", ","); ?>";
document.getElementById("txtTotalTDC").value = "<?php echo number_format($txtTotalTDC, 2, ".", ","); ?>";
document.getElementById("txtTotalTDD").value = "<?php echo number_format($txtTotalTDD, 2, ".", ","); ?>";
document.getElementById("txtSubtotalIngresos").value = "<?php echo number_format($txtTotalEfectivo + $txtTotalCheques + $txtTotalDepositos + $txtTotalTDC + $txtTotalTDD + $txtTotalTransferencia, 2, ".", ","); ?>";

document.getElementById("txtTotalRetencionISLR").value = "<?php echo number_format($txtTotalRetencionISLR, 2, ".", ","); ?>";
document.getElementById("txtTotalRetencionIVA").value = "<?php echo number_format($txtTotalRetencionIVA, 2, ".", ","); ?>";
document.getElementById("txtOtrosImpuestos").value = "<?php echo number_format($txtOtrosImpuestos, 2, ".", ","); ?>";
document.getElementById("txtSubtotalImpuestos").value = "<?php echo number_format($txtTotalRetencionISLR + $txtTotalRetencionIVA, 2, ".", ","); ?>";

document.getElementById("txtTotalAnticipo").value = "<?php echo number_format($txtTotalAnticipo, 2, ".", ","); ?>";
document.getElementById("txtTotalAnticipoNoCancelado").value = "<?php echo number_format($txtTotalAnticipoNoCancelado, 2, ".", ","); ?>";
document.getElementById("txtTotalNotaCredito").value = "<?php echo number_format($txtTotalNotaCredito, 2, ".", ","); ?>";
document.getElementById("txtSubtotalOtrosPagos").value = "<?php echo number_format($txtTotalAnticipo + $txtTotalAnticipoNoCancelado + $txtTotalNotaCredito, 2, ".", ","); ?>";

document.getElementById("txtTotalPagos").value = "<?php echo number_format($txtTotalEfectivo + $txtTotalCheques + $txtTotalDepositos + $txtTotalTDC + $txtTotalTDD + $txtTotalTransferencia + $txtTotalRetencionISLR + $txtTotalRetencionIVA + $txtTotalOtrosPago, 2, ".", ","); ?>";
document.getElementById("txtTotalPagosCredito").value = "<?php echo number_format($txtTotalEfectivo + $txtTotalCheques + $txtTotalDepositos + $txtTotalTDC + $txtTotalTDD + $txtTotalTransferencia + $txtTotalRetencionISLR + $txtTotalRetencionIVA + $txtTotalOtrosPago + $rowVentaCredito['monto_total'], 2, ".", ","); ?>";

document.getElementById("txtTotalCobranza").value = "<?php echo number_format($txtTotalCobranza, 2, ".", ","); ?>";

document.getElementById("txtTotalOtro").value = "<?php echo number_format($txtTotalOtro, 2, ".", ","); ?>";

document.getElementById("pNroControl").value = "<?php echo $varPrimerNroControl; ?>";
document.getElementById("uNroControl").value = "<?php echo $varUltimoNroControl; ?>";
document.getElementById("pNroFactura").value = "<?php echo $varPrimerNroFactura; ?>";
document.getElementById("uNroFactura").value = "<?php echo $varUltimoNroFactura; ?>";
document.getElementById("pNroNotaCredito").value = "<?php echo $varPrimerNroNotaCredito; ?>";
document.getElementById("uNroNotaCredito").value = "<?php echo $varUltimoNroNotaCredito; ?>";
</script>

<?php
function saldosDeCierre($montoApertura){
	global $idCajaPpal;
	global $idModuloPpal;
	global $apertCajaPpal;
	global $cierreCajaPpal;
	
	global $arrayTipoPago, $arrayCobranza, $arrayTipoTarjeta, $arrayOtro;
	
	global $conexion, $txtTotalEfectivo, $txtTotalCheques, $txtTotalDepositos, $txtTotalTransferencia, $txtTotalTDC, $txtTotalTDD, $txtTotalRetencionISLR, $txtTotalRetencionIVA, $txtTotalOtrosPago, $txtTotalOtro, $txtTotalCobranza, $txtTotalAnticipo, $txtTotalAnticipoNoCancelado, $txtTotalNotaCredito;
	
	global $montoTotalCierre, $montoTotalMasApertura;
	
	global $varPrimerNroControl, $varPrimerNroFactura, $varUltimoNroControl, $varUltimoNroFactura, $varPrimerNroNotaCredito, $varUltimoNroNotaCredito;
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	// VERIFICA VALORES DE CONFIGURACION (Mostrar Cajas por Empresa)
	$queryConfig400 = sprintf("SELECT * FROM pg_configuracion_empresa config_emp
		INNER JOIN pg_configuracion config ON (config_emp.id_configuracion = config.id_configuracion)
	WHERE config.id_configuracion = 400 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
		valTpDato(1, "int")); // 1 = Empresa cabecera
	$rsConfig400 = mysql_query($queryConfig400);
	if (!$rsConfig400) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRowsConfig400 = mysql_num_rows($rsConfig400);
	$rowConfig400 = mysql_fetch_assoc($rsConfig400);
	
	// BUSCA LOS CONCEPTOS DE FORMA DE PAGO OTROS
	$query = sprintf("SELECT * FROM cj_conceptos_formapago WHERE id_formapago = 11;");
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayOtro[$row['id_concepto']] = array(
			"descripcion" => $row['descripcion'],
			"total_forma_pago_concepto" => 0,
			"classConcepto" => ((in_array($row['id_concepto'], array(7,8,9))) ? "inputCompletoErrado2" : "inputCompleto"));
	}
	
	if ($rowConfig400['valor'] == 0) { // MOSTRAR POR SUCURSALES
		$andEmpresaAPER = sprintf(" AND ape.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
		$andEmpresaFA = sprintf(" AND cxc_fact.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaND = sprintf(" AND cxc_nd.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaAN = sprintf(" AND cxc_ant.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaCH = sprintf(" AND cxc_ch.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaTB = sprintf(" AND cxc_tb.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		$andEmpresaNC = sprintf(" AND cxc_nc.id_empresa = %s",
			valTpDato($idEmpresa, "int"));
		
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
		$andEmpresaAPER = "";
		
		$andEmpresaFA = "";
		$andEmpresaAN = "";
		$andEmpresaND = "";
		$andEmpresaCH = "";
		$andEmpresaTB = "";
		$andEmpresaNC = "";
		
		$andEmpresaPagoFA = "";
		$andEmpresaPagoND = "";
		$andEmpresaPagoAN = "";
		$andEmpresaPagoCH = "";
		$andEmpresaPagoTB = "";
	}
	
	$queryAperturaCaja = sprintf("SELECT *,
		(CASE ape.statusAperturaCaja
			WHEN 0 THEN 'CERRADA TOTALMENTE'
			WHEN 1 THEN CONCAT_WS(' EL ', 'ABIERTA', DATE_FORMAT(ape.fechaAperturaCaja, %s))
			WHEN 2 THEN 'CERRADA PARCIALMENTE'
			ELSE 'CERRADA TOTALMENTE'
		END) AS estatus_apertura_caja
	FROM ".$apertCajaPpal." ape
		INNER JOIN caja ON (ape.idCaja = caja.idCaja)
		LEFT JOIN ".$cierreCajaPpal." cierre ON (ape.id = cierre.id)
	WHERE ape.idCaja = %s
		AND ape.statusAperturaCaja IN (1,2) %s;",
		valTpDato(spanDatePick, "date"),
		valTpDato($idCajaPpal, "int"), // 1 = CAJA DE VEHICULOS, 2 = CAJA DE REPUESTOS Y SERVICIOS
		$andEmpresaAPER);
	$rsAperturaCaja = mysql_query($queryAperturaCaja);
	if (!$rsAperturaCaja) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowAperturaCaja = mysql_fetch_array($rsAperturaCaja);
	
	$fechaApertura = $rowAperturaCaja['fechaAperturaCaja'];
	
	// BUSCA LOS TIPOS DE TARJETA
	$query = sprintf("SELECT * FROM tipotarjetacredito tipo_tarjeta
	WHERE tipo_tarjeta.idTipoTarjetaCredito IN (
			SELECT
				(SELECT ret_punto.id_tipo_tarjeta
				FROM cj_cc_retencion_punto_pago ret_punto_pago
					INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
				WHERE ret_punto_pago.id_pago = q.idPago
					AND ret_punto_pago.id_caja = q.idCaja
					AND ret_punto_pago.id_tipo_documento = 1) AS id_tipo_tarjeta
			FROM (SELECT * FROM an_pagos cxc_pago_an
				
				UNION
				
				SELECT * FROM sa_iv_pagos cxc_pago) AS q
			WHERE q.fechaPago = %s
				AND q.idCaja IN (%s)
			
			UNION
			
			SELECT
				(SELECT ret_punto.id_tipo_tarjeta
				FROM cj_cc_retencion_punto_pago ret_punto_pago
					INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
				WHERE ret_punto_pago.id_pago = cxc_pago.id_det_nota_cargo
					AND ret_punto_pago.id_caja = cxc_pago.idCaja
					AND ret_punto_pago.id_tipo_documento = 2) AS id_tipo_tarjeta
			FROM cj_det_nota_cargo cxc_pago
			WHERE cxc_pago.fechaPago = %s
				AND cxc_pago.idCaja IN (%s)
			
			UNION
			
			SELECT		
				(SELECT ret_punto.id_tipo_tarjeta
				FROM cj_cc_retencion_punto_pago ret_punto_pago
					INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
				WHERE ret_punto_pago.id_pago = cxc_pago.idDetalleAnticipo
					AND ret_punto_pago.id_caja = cxc_pago.idCaja
					AND ret_punto_pago.id_tipo_documento = 4) AS id_tipo_tarjeta
			FROM cj_cc_detalleanticipo cxc_pago
			WHERE cxc_pago.fechaPagoAnticipo = %s
				AND cxc_pago.idCaja IN (%s))
		AND tipo_tarjeta.idTipoTarjetaCredito NOT IN (6);",
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "campo"),
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "campo"),
		valTpDato($fechaApertura, "date"),
		valTpDato($idCajaPpal, "campo"));
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayTipoTarjeta[$row['idTipoTarjetaCredito']] = array(
			"descripcion" => $row['descripcionTipoTarjetaCredito'],
			"total_tipo_tarjeta" => 0,
			"classTipoTarjeta" => "inputCompleto");
	}
	
	if (count($arrayTipoPago) > 1 && in_array(0, $arrayTipoPago) && in_array(1, $arrayTipoPago)) { // 0 = Crédito, 1 = Contado
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
	
	$queryFormaPago = "SELECT
		cxc_fact.id_empresa,
		1 AS tipoDoc,
		cxc_pago.formaPago AS idFormaPago,
		forma_pago.nombreFormaPago,
		cxc_pago.estatus,
		cxc_pago.estatus AS estatus_pago
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		INNER JOIN formapagos forma_pago ON (cxc_pago.formaPago = forma_pago.idFormaPago)
		INNER JOIN bancos banco ON (cxc_pago.bancoOrigen = banco.idBanco)
	WHERE cxc_pago.fechaPago = '".$fechaApertura."'
		AND (cxc_pago.formaPago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
		AND cxc_pago.tomadoEnCierre IN (0,2)
		AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
		".$andEmpresaFA." ".$andCondicionPagoFA."
	GROUP BY cxc_pago.formaPago, cxc_fact.id_empresa

	UNION
		
	SELECT
		cxc_nd.id_empresa,
		2 AS tipoDoc,
		cxc_pago.idFormaPago,
		forma_pago.nombreFormaPago,
		cxc_pago.estatus,
		cxc_pago.estatus AS estatus_pago
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
		INNER JOIN formapagos forma_pago ON (cxc_pago.idFormaPago = forma_pago.idFormaPago)
		INNER JOIN bancos banco ON (cxc_pago.bancoOrigen = banco.idBanco)
	WHERE cxc_pago.fechaPago = '".$fechaApertura."'
		AND cxc_nd.idDepartamentoOrigenNotaCargo IN (".valTpDato($idModuloPpal, "campo").")
		AND (cxc_pago.idFormaPago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.idFormaPago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.idFormaPago IN (4)))
		AND cxc_pago.tomadoEnCierre IN (0,2)
		AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
		".$andEmpresaND." ".$andCondicionPagoND."
	GROUP BY cxc_pago.idFormaPago, cxc_nd.id_empresa
	
	UNION
	
	SELECT
		cxc_ant.id_empresa,
		4 AS tipoDoc,
		cxc_pago.id_forma_pago AS idFormaPago,
		forma_pago.nombreFormaPago,
		cxc_ant.estatus,
		cxc_pago.estatus AS estatus_pago
	FROM cj_cc_anticipo cxc_ant
		JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
		JOIN formapagos forma_pago ON (cxc_pago.id_forma_pago = forma_pago.idFormaPago)
		JOIN bancos banco ON (cxc_pago.bancoClienteDetalleAnticipo = banco.idBanco)
	WHERE cxc_pago.fechaPagoAnticipo = '".$fechaApertura."'
		AND cxc_ant.idDepartamento IN (".valTpDato($idModuloPpal, "campo").")
		AND (cxc_pago.id_forma_pago NOT IN (2,4)
			OR (cxc_pago.id_cheque IS NULL AND cxc_pago.id_forma_pago IN (2))
			OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.id_forma_pago IN (4)))
		AND cxc_pago.tomadoEnCierre IN (0,2)
		AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
		AND cxc_ant.estatus = 1
		".$andEmpresaAN." ".$andCondicionPagoAN."
	GROUP BY cxc_pago.id_forma_pago, cxc_ant.id_empresa
	
	UNION
	
	SELECT 
		cxc_ch.id_empresa,
		5 AS tipoDoc,
		2 AS idFormaPago,
		(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 2) AS nombreFormaPago,
		cxc_ch.estatus,
		cxc_ch.estatus AS estatus_pago
	FROM cj_cc_cheque cxc_ch
		JOIN bancos banco ON (cxc_ch.id_banco_cliente = banco.idBanco)
	WHERE cxc_ch.fecha_cheque = '".$fechaApertura."'
		AND cxc_ch.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
		AND cxc_ch.tomadoEnCierre IN (0,2)
		AND (cxc_ch.idCierre = 0 OR cxc_ch.idCierre IS NULL)
		AND cxc_ch.estatus = 1
		".$andEmpresaCH." ".$andCondicionPagoCH."
	
	UNION
	
	SELECT 
		cxc_tb.id_empresa,
		6 AS tipoDoc,
		4 AS idFormaPago,
		(SELECT forma_pago.nombreFormaPago FROM formapagos forma_pago WHERE forma_pago.idFormaPago = 4) AS nombreFormaPago,
		cxc_tb.estatus,
		cxc_tb.estatus AS estatus_pago
	FROM cj_cc_transferencia cxc_tb
		JOIN bancos banco ON (cxc_tb.id_banco_cliente = banco.idBanco)
	WHERE cxc_tb.fecha_transferencia = '".$fechaApertura."'
		AND cxc_tb.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
		AND cxc_tb.tomadoEnCierre IN (0,2)
		AND (cxc_tb.idCierre = 0 OR cxc_tb.idCierre IS NULL)
		AND cxc_tb.estatus = 1
		".$andEmpresaTB." ".$andCondicionPagoTB."
	
	ORDER BY 3;"; //echo $queryFormaPago;
	$rsFormaPago = mysql_query($queryFormaPago);
	if (!$rsFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$sw = "";
	while ($rowFormaPago = mysql_fetch_array($rsFormaPago)) {
		$idFormaPago = $rowFormaPago['idFormaPago'];
		$nombreFormaPago = $rowFormaPago['nombreFormaPago'];
		
		if ($sw != $idFormaPago) {
			$queryPago = "SELECT
				cxc_pago.idPago,
				cxc_pago.fechaPago,
				cxc_pago.formaPago AS idFormaPago,
				NULL AS id_concepto,
				cxc_pago.montoPagado,
				
				(SELECT ret_punto.id_tipo_tarjeta
				FROM cj_cc_retencion_punto_pago ret_punto_pago
					INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
				WHERE ret_punto_pago.id_pago = cxc_pago.idPago
					AND ret_punto_pago.id_caja = cxc_pago.idCaja
					AND ret_punto_pago.id_tipo_documento = 1) AS id_tipo_tarjeta,
				
				cxc_pago.estatus,
				cxc_pago.estatus AS estatus_pago,
				DATE(cxc_pago.fecha_anulado) AS fecha_anulado
			FROM cj_cc_encabezadofactura cxc_fact
				INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
			WHERE cxc_pago.fechaPago = '".$fechaApertura."'
				AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
				AND (cxc_pago.formaPago NOT IN (2,4)
					OR (cxc_pago.id_cheque IS NULL AND cxc_pago.formaPago IN (2))
					OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.formaPago IN (4)))
				AND cxc_pago.tomadoEnComprobante = 1
				AND cxc_pago.tomadoEnCierre IN (0,2)
				AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
				AND cxc_pago.formaPago = ".$idFormaPago."
				AND (cxc_pago.estatus IN (1,2)
					OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado)))
				".$andEmpresaPagoFA." ".$andCondicionPagoFA."
			
			UNION
			
			SELECT
				cxc_pago.id_det_nota_cargo AS idPago,
				cxc_pago.fechaPago,
				cxc_pago.idFormaPago,
				NULL AS id_concepto,
				cxc_pago.monto_pago AS montoPagado,
				
				(SELECT ret_punto.id_tipo_tarjeta
				FROM cj_cc_retencion_punto_pago ret_punto_pago
					INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
				WHERE ret_punto_pago.id_pago = cxc_pago.id_det_nota_cargo
					AND ret_punto_pago.id_caja = cxc_pago.idCaja
					AND ret_punto_pago.id_tipo_documento = 2) AS id_tipo_tarjeta,
				
				cxc_pago.estatus,
				cxc_pago.estatus AS estatus_pago,
				DATE(cxc_pago.fecha_anulado) AS fecha_anulado
			FROM cj_cc_notadecargo cxc_nd
				INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
			WHERE cxc_pago.fechaPago = '".$fechaApertura."'
				AND cxc_nd.idDepartamentoOrigenNotaCargo IN (".valTpDato($idModuloPpal, "campo").")
				AND (cxc_pago.idFormaPago NOT IN (2,4)
					OR (cxc_pago.id_cheque IS NULL AND cxc_pago.idFormaPago IN (2))
					OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.idFormaPago IN (4)))
				AND cxc_pago.tomadoEnComprobante = 1
				AND cxc_pago.tomadoEnCierre IN (0,2)
				AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
				AND cxc_pago.idFormaPago = ".$idFormaPago."
				AND (cxc_pago.estatus IN (1,2)
					OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado)))
				".$andEmpresaPagoND." ".$andCondicionPagoND."
			
			UNION
			
			SELECT
				cxc_pago.idDetalleAnticipo AS idPago,
				cxc_pago.fechaPagoAnticipo AS fechaPago,
				cxc_pago.id_forma_pago AS idFormaPago,
				cxc_pago.id_concepto,
				cxc_pago.montoDetalleAnticipo AS montoPagado,
				
				(SELECT ret_punto.id_tipo_tarjeta
				FROM cj_cc_retencion_punto_pago ret_punto_pago
					INNER JOIN te_retencion_punto ret_punto ON (ret_punto_pago.id_retencion_punto = ret_punto.id_retencion_punto)
				WHERE ret_punto_pago.id_pago = cxc_pago.idDetalleAnticipo
					AND ret_punto_pago.id_caja = cxc_pago.idCaja
					AND ret_punto_pago.id_tipo_documento = 4) AS id_tipo_tarjeta,
				
				cxc_ant.estatus AS estatus,
				cxc_pago.estatus AS estatus_pago,
				DATE(cxc_pago.fecha_anulado) AS fecha_anulado
			FROM cj_cc_anticipo cxc_ant
				INNER JOIN cj_cc_detalleanticipo cxc_pago ON (cxc_ant.idAnticipo = cxc_pago.idAnticipo)
			WHERE cxc_pago.fechaPagoAnticipo = '".$fechaApertura."'
				AND cxc_ant.idDepartamento IN (".valTpDato($idModuloPpal, "campo").")
				AND (cxc_pago.id_forma_pago NOT IN (2,4)
					OR (cxc_pago.id_cheque IS NULL AND cxc_pago.id_forma_pago IN (2))
					OR (cxc_pago.id_transferencia IS NULL AND cxc_pago.id_forma_pago IN (4)))
				AND cxc_pago.tomadoEnCierre IN (0,2)
				AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
				AND cxc_pago.id_forma_pago = ".$idFormaPago."
				AND cxc_ant.estatus = 1
				AND (cxc_pago.estatus IN (1,2)
					OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPagoAnticipo <> DATE(cxc_pago.fecha_anulado)))
				".$andEmpresaPagoAN." ".$andCondicionPagoAN."
			
			UNION
			
			SELECT 
				cxc_ch.id_cheque AS idPago,
				cxc_ch.fecha_cheque AS fechaPago,
				2 AS idFormaPago,
				NULL AS id_concepto,
				cxc_ch.total_pagado_cheque AS montoPagado,
				NULL AS id_tipo_tarjeta,
				cxc_ch.estatus,
				cxc_ch.estatus AS estatus_pago,
				DATE(cxc_ch.fecha_anulado) AS fecha_anulado
			FROM cj_cc_cheque cxc_ch
			WHERE cxc_ch.fecha_cheque = '".$fechaApertura."'
				AND cxc_ch.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
				AND cxc_ch.tomadoEnComprobante = 1
				AND cxc_ch.tomadoEnCierre IN (0,2)
				AND (cxc_ch.idCierre = 0 OR cxc_ch.idCierre IS NULL)
				AND 2 = ".$idFormaPago."
				AND (cxc_ch.estatus IN (1,2)
					OR (cxc_ch.estatus IS NULL AND cxc_ch.fecha_cheque <> DATE(cxc_ch.fecha_anulado)))
				".$andEmpresaPagoCH." ".$andCondicionPagoCH."
			
			UNION
			
			SELECT 
				cxc_tb.id_transferencia AS idPago,
				cxc_tb.fecha_transferencia AS fechaPago,
				4 AS idFormaPago,
				NULL AS id_concepto,
				cxc_tb.total_pagado_transferencia AS montoPagado,
				NULL AS id_tipo_tarjeta,
				cxc_tb.estatus,
				cxc_tb.estatus AS estatus_pago,
				DATE(cxc_tb.fecha_anulado) AS fecha_anulado
			FROM cj_cc_transferencia cxc_tb
			WHERE cxc_tb.fecha_transferencia = '".$fechaApertura."'
				AND cxc_tb.id_departamento IN (".valTpDato($idModuloPpal, "campo").")
				AND cxc_tb.tomadoEnComprobante = 1
				AND cxc_tb.tomadoEnCierre IN (0,2)
				AND (cxc_tb.idCierre = 0 OR cxc_tb.idCierre IS NULL)
				AND 4 = ".$idFormaPago."
				AND (cxc_tb.estatus IN (1,2)
					OR (cxc_tb.estatus IS NULL AND cxc_tb.fecha_transferencia <> DATE(cxc_tb.fecha_anulado)))
				".$andEmpresaPagoTB." ".$andCondicionPagoTB.";"; //echo $queryPago."<br><br>";
			$rsPago = mysql_query($queryPago);
			if (!$rsPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
			$totalRowsPago = mysql_num_rows($rsPago);
			if ($totalRowsPago > 0) {
				$montoTotal = 0;
				while ($rowPago = mysql_fetch_array($rsPago)) {
					$idConcepto = $rowPago['id_concepto'];
					$montoTotal = $montoTotal + $rowPago['montoPagado'];
					
					if ($idConcepto > 0) {
						$arrayOtro[$idConcepto]['total_forma_pago_concepto'] += $rowPago['montoPagado'];
					}
					
					switch($idFormaPago){
						case 1 : $txtTotalEfectivo = $montoTotal; break;
						case 2 : $txtTotalCheques = $montoTotal; break;
						case 3 : $txtTotalDepositos = $montoTotal; break;
						case 4 : $txtTotalTransferencia = $montoTotal; break;
						case 5 :
							$txtTotalTDC = $montoTotal;
							$arrayTipoTarjeta[$rowPago['id_tipo_tarjeta']]['total_tipo_tarjeta'] += $rowPago['montoPagado'];
							break;
						case 6 : $txtTotalTDD = $montoTotal; break;
						case 7 : ($rowPago['estatus_pago'] == 1) ? $txtTotalAnticipo += $rowPago['montoPagado'] : $txtTotalAnticipoNoCancelado += $rowPago['montoPagado']; break;
						case 8 : $txtTotalNotaCredito = $montoTotal; break;
						case 9 : $txtTotalRetencionIVA = $montoTotal; break;
						case 10 : $txtTotalRetencionISLR = $montoTotal; break;
						case 11 :
							$txtTotalOtrosPago += (in_array($rowPago['id_concepto'], array(7,8,9))) ? 0 : $rowPago['montoPagado'];
							$txtTotalOtro = $montoTotal;
							break;
					}
					$montoTotalCierre += $montoTotal;
				}
			}
		}
		$sw = $idFormaPago;
	}
	$montoTotalMasApertura = $montoApertura + $montoTotalCierre;
	
	//min(CAST(numerofactura AS UNSIGNED) = convierte string en entero
	/*$queryNumerosFA = "SELECT
		MIN(numerocontrol) AS primernrocontrol,
		CAST(MIN(LPAD(numerofactura, 10, 0)) AS UNSIGNED) AS primernrofactura,
		MAX(numerocontrol) AS ultimonrocontrol,
		CAST(MAX(LPAD(numerofactura, 10, 0)) AS UNSIGNED) AS ultimonrofactura
	FROM cj_cc_encabezadofactura cxc_fact
	WHERE cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
		AND cxc_fact.aplicaLibros = 1
		AND cxc_fact.fechaRegistroFactura = '".$fechaApertura."' ".$andEmpresaFA.";";
	$rsNumerosFA = mysql_query($queryNumerosFA);
	if (!$rsNumerosFA) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowNumerosFA = mysql_fetch_assoc($rsNumerosFA);

	$varPrimerNroFactura = $rowNumerosFA['primernrofactura'];
	$varPrimerNroControl = $rowNumerosFA['primernrocontrol'];
	$varUltimoNroFactura = $rowNumerosFA['ultimonrofactura'];
	$varUltimoNroControl = $rowNumerosFA['ultimonrocontrol'];

	$queryNumerosNC = "SELECT
		MIN(numeroControl) AS primernrocontrolnotacredito,
		CAST(MIN(LPAD(numeracion_nota_credito, 10, 0)) AS UNSIGNED) AS primerNroNotaCredito,
		MAX(numeroControl) AS ultimonrocontrolnotacredito,
		CAST(MAX(LPAD(numeracion_nota_credito, 10, 0)) AS UNSIGNED) AS ultimoNroNotaCredito
	FROM cj_cc_notacredito cxc_nc
	WHERE cxc_nc.idDepartamentoNotaCredito IN (".valTpDato($idModuloPpal, "campo").")
		AND cxc_nc.aplicaLibros = 1
		AND cxc_nc.fechaNotaCredito = '".$fechaApertura."' ".$andEmpresaNC.";";
	$rsNumerosNC = mysql_query($queryNumerosNC);
	if (!$rsNumerosNC) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowNumerosNC = mysql_fetch_assoc($rsNumerosNC);
	
	$varPrimerNroNotaCredito = $rowNumerosNC['primerNroNotaCredito'];
	$varUltimoNroNotaCredito = $rowNumerosNC['ultimoNroNotaCredito'];*/
	
	$sqlMostrarPorFormaPago = "SELECT
		SUM(cxc_pago.montoPagado) AS montoPagado
	FROM cj_cc_encabezadofactura cxc_fact
		INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
	WHERE cxc_pago.fechaPago = '".$fechaApertura."'
		AND cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($idModuloPpal, "campo").")
		AND cxc_pago.tomadoEnComprobante = 1
		AND cxc_pago.tomadoEnCierre IN (0,2)
		AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
		AND cxc_pago.formaPago NOT IN (7,8)
		AND cxc_fact.condicionDePago = 0 ".$andEmpresaPagoFA."
	
	UNION
	
	SELECT
		SUM(cxc_pago.monto_pago) AS montoPagado
	FROM cj_cc_notadecargo cxc_nd
		INNER JOIN cj_det_nota_cargo cxc_pago ON (cxc_nd.idNotaCargo = cxc_pago.idNotaCargo)
	WHERE cxc_pago.fechaPago = '".$fechaApertura."'
		AND cxc_nd.idDepartamentoOrigenNotaCargo IN (".valTpDato($idModuloPpal, "campo").")
		AND cxc_pago.tomadoEnComprobante = 1
		AND cxc_pago.tomadoEnCierre IN (0,2)
		AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
		AND cxc_pago.idFormaPago NOT IN (7,8)
		AND cxc_nd.fechaRegistroNotaCargo <> '".$fechaApertura."' ".$andEmpresaPagoND.";";
	$consultaMostrarPorFormaPago = mysql_query($sqlMostrarPorFormaPago);
	if (!$consultaMostrarPorFormaPago) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$rowTotalCreditoTotal = mysql_fetch_assoc($consultaMostrarPorFormaPago);
	
	$varTotalPagosCredito = $rowTotalCreditoTotal['montoPagado'];
	
	
	// COBRANZA ES AQUELLA EN LA QUE SE RECIBIO EL PAGO DE LAS FACTURAS A CREDITO GENERADAS UN DIA DIFERENTE AL ACTUAL
	$query = "SELECT
		modulo.id_modulo,
		modulo.descripcionModulo
	FROM pg_modulos modulo
	WHERE modulo.id_modulo IN (".valTpDato($idModuloPpal, "campo").");";
	$rs = mysql_query($query);
	if (!$rs) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	while ($row = mysql_fetch_assoc($rs)) {
		$arrayCobranza[$row['id_modulo']] = array(
			"id_modulo" => $row['id_modulo'],
			"descripcion" => "Total Cobranzas ".$row['descripcionModulo'],
			"total_cobranza" => 0);
			
		$queryCobranza = "SELECT
			cxc_fact.idDepartamentoOrigenFactura,
			SUM(cxc_pago.montopagado) AS total
		FROM cj_cc_encabezadofactura cxc_fact
			INNER JOIN sa_iv_pagos cxc_pago ON (cxc_fact.idFactura = cxc_pago.id_factura)
		WHERE cxc_fact.idDepartamentoOrigenFactura IN (".valTpDato($row['id_modulo'], "campo").")
			AND cxc_pago.fechapago = '".$fechaApertura."'
			AND cxc_pago.fechapago <> cxc_fact.fechaRegistroFactura
			AND (cxc_pago.idCierre = 0 OR cxc_pago.idCierre IS NULL)
			AND (cxc_pago.estatus IN (1)
				OR (cxc_pago.estatus IS NULL AND cxc_pago.fechaPago <> DATE(cxc_pago.fecha_anulado)))
			AND cxc_pago.formaPago NOT IN (8) ".$andEmpresaPagoFA."
		GROUP BY cxc_fact.idDepartamentoOrigenFactura;";
		$rsCobranza = mysql_query($queryCobranza);
		if (!$rsCobranza) die(mysql_error()."<br>Error Nro: ".mysql_errno()."<br>Line: ".__LINE__);
		$totalRowsCobranza = mysql_num_rows($rsCobranza);
		while ($rowCobranza = mysql_fetch_assoc($rsCobranza)) {
			$arrayCobranza[$rowCobranza['idDepartamentoOrigenFactura']]['total_cobranza'] += $rowCobranza['total'];
			
			$txtTotalCobranza += $rowCobranza['total'];
		}
	}
}
?>