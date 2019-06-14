<?php

$tipo_de = $_GET['o'];
if ($tipo_de == 1) {
	 $tipo= 'Todos_documentos';
}elseif ($tipo_de ==2) {
	 $tipo= 'Tipo_documentos';
}elseif ($tipo_de == 3) { 
	 $tipo= 'Resumido';
}

header('Content-Disposition: attachment; filename=Cuenta_Individual_'.$tipo.'.xls');

header("Content-Type: application/vnd.openxmlformats-   officedocument.spreadsheetml.sheet");

header('Cache-Control: must-revalidate, post-check=0, pre-check=0');


$host= $_SERVER["HTTP_HOST"];
$urs= $_SERVER["REQUEST_URI"];
$url= (split( '[/]', $urs )); 

$ip =$host .'/'. $url[1];
require_once("../../connections/conex.php");

session_start();

/* Validación del Módulo */

/* Fin Validación del Módulo */

require ('../../controladores/xajax/xajax_core/xajax.inc.php');
//Instanciando el objeto xajax
$xajax = new xajax();
//Configuranto la ruta del manejador de script
$xajax->configure('javascript URI', '../../controladores/xajax/');

include("../../controladores/ac_iv_general.php");
include("../controladores/ac_cp_estado_cuenta_individual_list.php");

//$xajax->setFlag('debug',true);
//$xajax->setFlag('allowAllResponseTypes', true);

$xajax->processRequest();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>.: SIPRE <?php echo cVERSION; ?> :. Cuentas por Pagar - Estado Cuenta Individual</title>
    <link rel="icon" type="image/png" href="<?php echo $raiz; ?>img/login/icono_sipre_png.png" />
    <?php $xajax->printJavascript('../../controladores/xajax/'); ?>
    
    
    <link rel="stylesheet" type="text/css" href="http://<?php echo $ip ?>/style/styleRafk.css" />
	
   
	
</head>
<body face="Comic Sans MS,arial,verdana" >
	<h2>

<?php 
$pageNum = 0;
$campOrd = "fecha_origen";
$tpOrd = "DESC";
$valBusq = "";
$maxRows = 10000;
$totalRows = NULL;

$ws = $_GET['o'];
$valBusq = $_GET['valBusq'];
$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_ec.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = vw_cxp_ec.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_ec.id_proveedor = %s",
			valTpDato($valCadBusq[1], "int"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_ec.id_modulo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_ec.tipoDocumento IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[3])."'", "defined", "'".str_replace(",","','",$valCadBusq[3])."'"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_cxp_ec.estado IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_cxp_ec.numero_documento LIKE %s
		OR vw_cxp_ec.observacion_factura LIKE %s
		OR prov.nombre LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}

			if ($ws == 1 ) {
				global $raiz;
	
	
	
	$query = sprintf("SELECT vw_cxp_ec.*,
		(CASE 
			WHEN (vw_cxp_ec.tipoDocumento IN ('FA','ND')) THEN
				(CASE vw_cxp_ec.estado
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Cancelado'
					WHEN 2 THEN 'Cancelado Parcial'
				END)
			WHEN (vw_cxp_ec.tipoDocumento IN ('NC','AN')) THEN
				(CASE vw_cxp_ec.estado
					WHEN 0 THEN 'No Cancelado'
					WHEN 1 THEN 'Sin Asignar'
					WHEN 2 THEN 'Asignado Parcial'
					WHEN 3 THEN 'Asignado'
				END)
		END) AS estado_documento,
		prov.nombre AS nombre_proveedor,
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_cp_estado_cuenta vw_cxp_ec
		INNER JOIN cp_proveedor prov ON (vw_cxp_ec.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_ec.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	$wsa = mysql_query($queryLimit);
	$totalPages = ceil($totalRows/$maxRows)-1;
	$a = mysql_fetch_assoc($wsa);
	$encabezado = "<div align=center><h3> CUENTA INDIVIDUAL DE TODOS LO DOCUMENTO DE PROVEEDOR:</h3><h2>".$a['nombre_proveedor']."</h2></div> <br>";
	$htmlTblIni = "<table border=\"1\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"4%\"></td>";
		$htmlTh .= "<td>Empresa</td>";
		$htmlTh .= "<td>Fecha Registro</td>";
		$htmlTh .= "<td>Fecha Dcto. Proveedor</td>";
		$htmlTh .= "<td>Fecha Venc. Dcto.</td>";
		$htmlTh .= "<td>Tipo Dcto.</td>";
		$htmlTh .= "<td colspan='2'>Nro. Dcto.</td>";
		$htmlTh .= "<td>Proveedor</td>";
		$htmlTh .= "<td>Estado Dcto.</td>";
		$htmlTh .= "<td>Saldo Dcto.</td>";
		$htmlTh .= "<td>Total Dcto.</td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['id_modulo']) {
			case 0 : $imgPedidoModulo = "R"; break;
			case 1 : $imgPedidoModulo = "S"; break;
			case 2 : $imgPedidoModulo = "V"; break;
			case 3 : $imgPedidoModulo = "C"; break;
			case 4 : $imgPedidoModulo = "A"; break;
			default : $imgPedidoModulo = $row['id_modulo'];
		}
		
		switch($row['estado']) {
			case 0 : $class = "class=\"divMsjError\""; break;
			case 1 : $class = "class=\"divMsjInfo\""; break;
			case 2 : $class = "class=\"divMsjAlerta\""; break;
			case 3 : $class = "class=\"divMsjInfo3\""; break;
			case 4 : $class = "class=\"divMsjInfo4\""; break;
		}
		
		
		
		switch ($row['tipoDocumento']) {
			case "FA" : // 1 = FA
				$cantFactura++;
				$saldoTotalFactura += $row['saldo'];
				break;
			case "ND" : // 2 = ND
				$cantNotaCargo++;
				$saldoTotalNotaCargo += $row['saldo'];
				break;
			case "NC" : // 3 = NC
				$cantNotaCredito++;
				$saldoTotalNotaCredito += $row['saldo'];
				break;
			case "AN" : // 4 = AN
				$cantAnticipo++;
				$saldoTotalAnticipo += $row['saldo'];
				break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila) + (($pageNum) * $maxRows))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($row['nombre_empleado']) > 0) ? "title=\"Factura Nro: ".$row['numero_factura_proveedor'].". Registrado por: ".$row['nombre_empleado']."\"" : "").">".date(spanDateFormat, strtotime($row['fecha_origen']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_proveedor']))."</td>";
			$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($row['fecha_vencimiento']))."</td>";
			$htmlTb .= "<td align=\"center\" title=\"".$row['idEstadoCuenta']."\">".utf8_encode($row['tipoDocumento']).(($row['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
					$htmlTb .= "<td>".$imgPedidoModulo."</td>";
					$htmlTb .= "<td>".utf8_encode($row['numero_documento'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= "<table width=\"100%\">";
				$htmlTb .= "<tr>";
					$htmlTb .= "<td width=\"100%\">".utf8_encode($row['nombre_proveedor'])."</td>";
				$htmlTb .= "</tr>";
				$htmlTb .= (strlen($row['observacion_factura']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($row['observacion_factura'])."<span></td></tr>" : "";
				$htmlTb .= "</table>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\" ".$class.">".$row['estado_documento']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['saldo'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">".$row['abreviacion_moneda_local'].number_format($row['total_cuenta_pagar'], 2, ".", ",")."</td>";
		$htmlTb .= "</tr>";
		
		$arrayTotal[11] += $row['saldo'];
		$arrayTotal[12] += $row['total_cuenta_pagar'];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">"."Total:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12],2)."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$arrayTotalFinal[11] += $row['saldo'];
				$arrayTotalFinal[12] += $row['total_cuenta_pagar'];
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"22\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"10\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12],2)."</td>";
			$htmlTb .= "</tr>";
		}
	}
	
	
	$cuenta .= "<table border=\"1\" width=\"100%\">";

	$cuenta .= "<tr>";
		$cuenta .= "<td colspan=\"12\">";
			$cuenta .= "<table border=\"1\" width=\"100%\">";
			$cuenta .= "<tr class=\"tituloColumna\">";
				$cuenta .= "<td align='center' colspan=\"8\">"."Saldos"."</td>";
			$cuenta .= "</tr>";
			$cuenta .= "<tr class=\"tituloColumna\">";
				$cuenta .= "<td colspan=\"2\">"."Facturas"."</td>";
				$cuenta .= "<td colspan=\"2\">"."Nota de Débito"."</td>";
				$cuenta .= "<td colspan=\"2\">"."Anticipo"."</td>";
				$cuenta .= "<td colspan=\"2\">"."Nota de Crédito"."</td>";
			$cuenta .= "</tr>";
			$cuenta .= "<tr align=\"right\">";
				$cuenta .= "<td width=\"10%\">".number_format($cantFactura, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"15%\">".number_format($saldoTotalFactura, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"10%\">".number_format($cantNotaCargo, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"15%\">".number_format($saldoTotalNotaCargo, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"10%\">".number_format($cantAnticipo, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"15%\">".number_format($saldoTotalAnticipo, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"10%\">".number_format($cantNotaCredito, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"15%\">".number_format($saldoTotalNotaCredito, 2, ".", ",")."</td>";
			$cuenta .= "</tr>";
			$cuenta .= "</table><br><br>";
		$cuenta .= "</td>";
	$cuenta .= "</tr>";
	$cuenta .= "</table>";
	$htmlTblFin .= "</table>";
	
	if ($rsLimit == 0) {
		$htmlTb .= "<td colspan=\"13\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"http://".$ip."/img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	echo $encabezado.$cuenta.$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;
				
			}elseif ($ws == 3) {

				
	
	$query = sprintf("SELECT vw_cxp_ec.tipoDocumentoN, vw_cxp_ec.tipoDocumento
	FROM vw_cp_estado_cuenta vw_cxp_ec
		INNER JOIN cp_proveedor prov ON (vw_cxp_ec.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_ec.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	GROUP BY vw_cxp_ec.tipoDocumento", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	$totalPages = ceil($totalRows/$maxRows)-1;

	
	$htmlTblIni = "<table border=\"1\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"40%\">"."Tipo Dcto."."</td>";
		$htmlTh .= "<td width=\"20%\">"."Cant. Dctos."."</td>";
		$htmlTh .= "<td width=\"20%\">"."Saldo Dctos."."</td>";
		$htmlTh .= "<td width=\"20%\">"."Total Dctos."."</td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.tipoDocumento LIKE %s",
			valTpDato($row['tipoDocumento'], "text"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxp_ec.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = vw_cxp_ec.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.id_proveedor = %s",
				valTpDato($valCadBusq[1], "int"));
		}
		
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.id_modulo IN (%s)",
				valTpDato($valCadBusq[2], "campo"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.tipoDocumento IN (%s)",
				valTpDato("'".str_replace(",","','",$valCadBusq[3])."'", "defined", "'".str_replace(",","','",$valCadBusq[3])."'"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.estado IN (%s)",
				valTpDato($valCadBusq[4], "campo"));
		}
		
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxp_ec.numero_documento LIKE %s
			OR vw_cxp_ec.observacion_factura LIKE %s
			OR prov.nombre LIKE %s)",
				valTpDato("%".$valCadBusq[5]."%", "text"),
				valTpDato("%".$valCadBusq[5]."%", "text"),
				valTpDato("%".$valCadBusq[5]."%", "text"));
		}
		
		$queryDetalle = sprintf("SELECT vw_cxp_ec.*,
			(CASE 
				WHEN (vw_cxp_ec.tipoDocumento IN ('FA','ND')) THEN
					(CASE vw_cxp_ec.estado
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado'
						WHEN 2 THEN 'Cancelado Parcial'
					END)
				WHEN (vw_cxp_ec.tipoDocumento IN ('NC','AN')) THEN
					(CASE vw_cxp_ec.estado
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Sin Asignar'
						WHEN 2 THEN 'Asignado Parcial'
						WHEN 3 THEN 'Asignado'
					END)
			END) AS estado_documento,
			prov.nombre AS nombre_proveedor,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_cp_estado_cuenta vw_cxp_ec
			INNER JOIN cp_proveedor prov ON (vw_cxp_ec.id_proveedor = prov.id_proveedor)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_ec.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq2);
		$queryDetalle .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsDetalle = mysql_query($queryDetalle);
		$wsa = mysql_query($queryDetalle);
		$a = mysql_fetch_assoc($wsa);
		$encabezado = "<div align=center><h3> CUENTA INDIVIDUAL DE TODOS LO DOCUMENTO DE PROVEEDOR:</h3><h2>".$a['nombre_proveedor']."</h2></div> <br>";
		$arrayTotalRenglon = NULL;
		$contFila2 = 0;
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			if (in_array($rowDetalle['tipoDocumento'],array("FA"))) {
				$saldoTotalFactura += $rowDetalle['saldo'];
			} else if (in_array($rowDetalle['tipoDocumento'],array("ND"))) {
				$saldoTotalNotaCargo += $rowDetalle['saldo'];
			} else if (in_array($rowDetalle['tipoDocumento'],array("AN"))) {
				$saldoTotalAnticipo += $rowDetalle['saldo'];
			} else if (in_array($rowDetalle['tipoDocumento'],array("NC"))) {
				$saldoTotalNotaCredito += $rowDetalle['saldo'];
			}
			
			$arrayTotalRenglon[10] ++;
			$arrayTotalRenglon[11] += $rowDetalle['saldo'];
			$arrayTotalRenglon[12] += $rowDetalle['total_cuenta_pagar'];
		}
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"22\">";
			$htmlTb .= "<td class=\"tituloCampo\">"."Total ".$row['tipoDocumento'].":"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalRenglon[10],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalRenglon[11],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalRenglon[12],2)."</td>";
		$htmlTb .= "</tr>";
			
		$arrayTotal[10] += $arrayTotalRenglon[10];
		$arrayTotal[11] += $arrayTotalRenglon[11];
		$arrayTotal[12] += $arrayTotalRenglon[12];
	}
	
	
	$htmlTblFin .= "</table>";
	
	if ($rsLimit == 0) {
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"http://".$ip."/img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	echo $encabezado.$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;
	
				
			}elseif ($ws == 2) {
				global $raiz;
	
	
	
	$query = sprintf("SELECT vw_cxp_ec.tipoDocumentoN, vw_cxp_ec.tipoDocumento
	FROM vw_cp_estado_cuenta vw_cxp_ec
		INNER JOIN cp_proveedor prov ON (vw_cxp_ec.id_proveedor = prov.id_proveedor)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_ec.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s
	GROUP BY vw_cxp_ec.tipoDocumento", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"1\" class=\"texto_9px\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.tipoDocumento LIKE %s",
			valTpDato($row['tipoDocumento'], "text"));
		
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxp_ec.id_empresa = %s
			OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
					WHERE suc.id_empresa = vw_cxp_ec.id_empresa))",
				valTpDato($valCadBusq[0], "int"),
				valTpDato($valCadBusq[0], "int"));
		}
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.id_proveedor = %s",
				valTpDato($valCadBusq[1], "int"));
		}
		
		if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.id_modulo IN (%s)",
				valTpDato($valCadBusq[2], "campo"));
		}
		
		if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.tipoDocumento IN (%s)",
				valTpDato("'".str_replace(",","','",$valCadBusq[3])."'", "defined", "'".str_replace(",","','",$valCadBusq[3])."'"));
		}
		
		if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.estado IN (%s)",
				valTpDato($valCadBusq[4], "campo"));
		}
		
		if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
			$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
			$sqlBusq2 .= $cond.sprintf("(vw_cxp_ec.numero_documento LIKE %s
			OR vw_cxp_ec.observacion_factura LIKE %s
			OR prov.nombre LIKE %s)",
				valTpDato("%".$valCadBusq[5]."%", "text"),
				valTpDato("%".$valCadBusq[5]."%", "text"),
				valTpDato("%".$valCadBusq[5]."%", "text"));
		}
		
		$queryDetalle = sprintf("SELECT vw_cxp_ec.*,
			(CASE
				WHEN (vw_cxp_ec.tipoDocumento IN ('FA','ND')) THEN
					(CASE vw_cxp_ec.estado
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Cancelado'
						WHEN 2 THEN 'Cancelado Parcial'
					END)
				WHEN (vw_cxp_ec.tipoDocumento IN ('NC','AN')) THEN
					(CASE vw_cxp_ec.estado
						WHEN 0 THEN 'No Cancelado'
						WHEN 1 THEN 'Sin Asignar'
						WHEN 2 THEN 'Asignado Parcial'
						WHEN 3 THEN 'Asignado'
					END)
			END) AS estado_documento,
			prov.nombre AS nombre_proveedor,
			IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		FROM vw_cp_estado_cuenta vw_cxp_ec
			INNER JOIN cp_proveedor prov ON (vw_cxp_ec.id_proveedor = prov.id_proveedor)
			INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_ec.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq2);
		$queryDetalle .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		$rsDetalle = mysql_query($queryDetalle);
		$wsa = mysql_query($queryLimit);
		$a = mysql_fetch_assoc($wsa);
		$encabezado = "<div align=center><h3> CUENTA INDIVIDUAL POR TIPO DE DOCUMENTO DEL PROVEEDOR:</h3><h2>".$a['nombre_proveedor']."</h2></div> <br>";
		$htmlTb .= "<tr class=\"tituloColumna\" height=\"22\">";
			$htmlTb .= "<td align=\"center\" colspan=\"13\">".$row['tipoDocumento']."</td>";
		$htmlTb .= "</tr>";
		$htmlTb .= "<tr class=\"tituloColumna\">";
			$htmlTb .= "<td width=\"4%\"></td>";
			$htmlTb .= "<td> Empresa</td>";
			$htmlTb .= "<td> Fecha Registro</td>";
			$htmlTb .= "<td> Fecha Dcto. Proveedor</td>";
			$htmlTb .= "<td> Fecha Venc. Dcto.</td>";
			$htmlTb .= "<td> Tipo Dcto.</td>";
			$htmlTb .= "<td > Nro. Dcto.</td>";
			$htmlTb .= "<td> Proveedor</td>";
			$htmlTb .= "<td> Estado Dcto.</td>";
			$htmlTb .= "<td> Saldo Dcto.</td>";
			$htmlTb .= "<td> Total Dcto.</td>";
		$htmlTb .= "</tr>";
		$arrayTotalRenglon = NULL;
		$contFila2 = 0;
		while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar4" : "trResaltar5";
			$contFila2++;
			
			switch($rowDetalle['id_modulo']) {
				case 0 : $imgPedidoModulo = "R"; break;
				case 1 : $imgPedidoModulo = "S"; break;
				case 2 : $imgPedidoModulo = "V"; break;
				case 3 : $imgPedidoModulo = "C"; break;
				case 4 : $imgPedidoModulo = "A"; break;
				default : $imgPedidoModulo = $rowDetalle['id_modulo'];
			}
			
			switch($rowDetalle['estado']) {
				case 0 : $class = "class=\"divMsjError\""; break;
				case 1 : $class = "class=\"divMsjInfo\""; break;
				case 2 : $class = "class=\"divMsjAlerta\""; break;
				case 3 : $class = "class=\"divMsjInfo3\""; break;
				case 4 : $class = "class=\"divMsjInfo4\""; break;
			}
			
			
			
			switch ($rowDetalle['tipoDocumento']) {
				case "FA" : // 1 = FA
					$cantFactura++;
					$saldoTotalFactura += $rowDetalle['saldo'];
					break;
				case "ND" : // 2 = ND
					$cantNotaCargo++;
					$saldoTotalNotaCargo += $rowDetalle['saldo'];
					break;
				case "NC" : // 3 = NC
					$cantNotaCredito++;
					$saldoTotalNotaCredito += $rowDetalle['saldo'];
					break;
				case "AN" : // 4 = AN
					$cantAnticipo++;
					$saldoTotalAnticipo += $rowDetalle['saldo'];
					break;
			}
			
			$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
				$htmlTb .= "<td align=\"center\" class=\"textoNegrita_9px\">".(($contFila2) + (($pageNum) * $maxRows))."</td>";
				$htmlTb .= "<td>".utf8_encode($rowDetalle['nombre_empresa'])."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\"".((strlen($rowDetalle['nombre_empleado']) > 0) ? "title=\"Factura Nro: ".$rowDetalle['numero_factura_proveedor'].". Registrado por: ".$rowDetalle['nombre_empleado']."\"" : "").">".date(spanDateFormat, strtotime($rowDetalle['fecha_origen']))."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($rowDetalle['fecha_proveedor']))."</td>";
				$htmlTb .= "<td align=\"center\" nowrap=\"nowrap\">".date(spanDateFormat, strtotime($rowDetalle['fecha_vencimiento']))."</td>";
				$htmlTb .= "<td align=\"center\" title=\"".$rowDetalle['idEstadoCuenta']."\">".utf8_encode($rowDetalle['tipoDocumento']).(($rowDetalle['idEstadoCuenta'] > 0) ? "" : "*")."</td>";
						$htmlTb .= "<td>".$imgPedidoModulo."</td>";

				$htmlTb .= "<td>";
					$htmlTb .= "<table width=\"100%\">";
					$htmlTb .= "<tr>";
						$htmlTb .= "<td width=\"100%\">".utf8_encode($rowDetalle['nombre_proveedor'])."</td>";
					$htmlTb .= "</tr>";
					$htmlTb .= (strlen($rowDetalle['observacion_factura']) > 0) ? "<tr><td><span class=\"textoNegritaCursiva_9px\">".utf8_encode($rowDetalle['observacion_factura'])."<span></td></tr>" : "";
					$htmlTb .= "</table>";
				$htmlTb .= "</td>";
				$htmlTb .= "<td align=\"center\" ".$class.">".$rowDetalle['estado_documento']."</td>";
				$htmlTb .= "<td align=\"right\">".$rowDetalle['abreviacion_moneda_local'].number_format($rowDetalle['saldo'], 2, ".", ",")."</td>";
				$htmlTb .= "<td align=\"right\">".$rowDetalle['abreviacion_moneda_local'].number_format($rowDetalle['total_cuenta_pagar'], 2, ".", ",")."</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalRenglon[11] += $rowDetalle['saldo'];
			$arrayTotalRenglon[12] += $rowDetalle['total_cuenta_pagar'];
		}
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal\" height=\"12\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total ".$row['tipoDocumento'].":"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalRenglon[11],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotalRenglon[12],2)."</td>";
		$htmlTb .= "</tr>";
			
		$arrayTotal[11] += $arrayTotalRenglon[11];
		$arrayTotal[12] += $arrayTotalRenglon[12];
	}
	if ($contFila > 0) {
		$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"12\">";
			$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total:"."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[11],2)."</td>";
			$htmlTb .= "<td>".number_format($arrayTotal[12],2)."</td>";
		$htmlTb .= "</tr>";
		
		if ($pageNum == $totalPages) {
			$rs = mysql_query($query);
			while ($row = mysql_fetch_assoc($rs)) {
				$sqlBusq2 = "";
				$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
				$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.tipoDocumento LIKE %s",
					valTpDato($row['tipoDocumento'], "text"));
				
				if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
					$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
					$sqlBusq2 .= $cond.sprintf("(vw_cxp_ec.id_empresa = %s
					OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
							WHERE suc.id_empresa = vw_cxp_ec.id_empresa))",
						valTpDato($valCadBusq[0], "int"),
						valTpDato($valCadBusq[0], "int"));
				}
				
				if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
					$cond = (strlen($sqlBusq2) > 0) ? " AND " : " WHERE ";
					$sqlBusq2 .= $cond.sprintf("vw_cxp_ec.id_proveedor = %s",
						valTpDato($valCadBusq[1], "int"));
				}
				
				$queryDetalle = sprintf("SELECT vw_cxp_ec.*,
					(CASE 
						WHEN (vw_cxp_ec.tipoDocumento IN ('FA','ND')) THEN
							(CASE vw_cxp_ec.estado
								WHEN 0 THEN 'No Cancelado'
								WHEN 1 THEN 'Cancelado'
								WHEN 2 THEN 'Cancelado Parcial'
							END)
						WHEN (vw_cxp_ec.tipoDocumento IN ('NC','AN')) THEN
							(CASE vw_cxp_ec.estado
								WHEN 0 THEN 'No Cancelado'
								WHEN 1 THEN 'Sin Asignar'
								WHEN 2 THEN 'Asignado Parcial'
								WHEN 3 THEN 'Asignado'
							END)
					END) AS estado_documento,
					prov.nombre AS nombre_proveedor,
					IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
				FROM vw_cp_estado_cuenta vw_cxp_ec
					INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (vw_cxp_ec.id_empresa = vw_iv_emp_suc.id_empresa_reg) %s", $sqlBusq2);
				$queryDetalle .= ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
				$rsDetalle = mysql_query($queryDetalle);
				if (!$rsDetalle) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
				while ($rowDetalle = mysql_fetch_assoc($rsDetalle)) {
					$arrayTotalFinal[10] ++;
					$arrayTotalFinal[11] += $rowDetalle['saldo'];
					$arrayTotalFinal[12] += $rowDetalle['total_cuenta_pagar'];
				}
			}
			
			$htmlTb .= "<tr align=\"right\" class=\"trResaltarTotal3\" height=\"12\">";
				$htmlTb .= "<td class=\"tituloCampo\" colspan=\"9\">"."Total de Totales:"."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[10],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[11],2)."</td>";
				$htmlTb .= "<td>".number_format($arrayTotalFinal[12],2)."</td>";
				$htmlTb .= "<td>"."</td>";
			$htmlTb .= "</tr>";
		}
	}
	

	
	$$cuenta .= "<table border=\"1\" width=\"100%\">";

	$cuenta .= "<tr>";
		$cuenta .= "<td colspan=\"12\">";
			$cuenta .= "<table border=\"1\" width=\"100%\">";
			$cuenta .= "<tr class=\"tituloColumna\">";
				$cuenta .= "<td align='center' colspan=\"8\">"."Saldos"."</td>";
			$cuenta .= "</tr>";
			$cuenta .= "<tr class=\"tituloColumna\">";
				$cuenta .= "<td colspan=\"2\">"."Facturas"."</td>";
				$cuenta .= "<td colspan=\"2\">"."Nota de Débito"."</td>";
				$cuenta .= "<td colspan=\"2\">"."Anticipo"."</td>";
				$cuenta .= "<td colspan=\"2\">"."Nota de Crédito"."</td>";
			$cuenta .= "</tr>";
			$cuenta .= "<tr align=\"right\">";
				$cuenta .= "<td width=\"10%\">".number_format($cantFactura, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"15%\">".number_format($saldoTotalFactura, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"10%\">".number_format($cantNotaCargo, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"15%\">".number_format($saldoTotalNotaCargo, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"10%\">".number_format($cantAnticipo, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"15%\">".number_format($saldoTotalAnticipo, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"10%\">".number_format($cantNotaCredito, 2, ".", ",")."</td>";
				$cuenta .= "<td width=\"15%\">".number_format($saldoTotalNotaCredito, 2, ".", ",")."</td>";
			$cuenta .= "</tr>";
			$cuenta .= "</table><br><br>";
		$cuenta .= "</td>";
	$cuenta .= "</tr>";
	$cuenta .= "</table>";
	$htmlTblFin .= "</table>";
	
	if ($rsLimit == 0) {
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"http://".$ip."/img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	echo $encabezado.$cuenta.$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin;
				# code...
			}

							?>
							
									








              
        </h2>
</body>