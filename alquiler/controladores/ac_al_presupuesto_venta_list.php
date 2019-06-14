<?php

function anularPresupuesto($idPresupuestoVenta){
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"al_presupuesto_venta_list","eliminar")) { return $objResponse; }

	$sql = sprintf("UPDATE al_presupuesto_venta SET 
					estatus_presupuesto_venta = %s, 
					id_empleado_anulacion = %s, 
					fecha_anulacion = %s
				WHERE id_presupuesto_venta = %s",
			valTpDato(2, "int"),// 0 = Presupuesto Sin Aprobar, 1 = Aprobado, 2 = Anulado
			valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
			"NOW()",
			valTpDato($idPresupuestoVenta, "int"));
	
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	
	$objResponse->script("byId('btnBuscar').click();");
	$objResponse->alert("Presupuesto Anulado");	
	
	return $objResponse;	
}

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEmpleadoVendedor'],
		$frmBuscar['lstEstatusPresupuesto'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPresupuestoVenta(0, "numero_presupuesto_venta", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "") {
	$objResponse = new xajaxResponse();
		
	$query = sprintf("SELECT DISTINCT empleado.id_empleado, empleado.nombre_empleado
	FROM al_presupuesto_venta presupuesto
		INNER JOIN vw_pg_empleados empleado ON (presupuesto.id_empleado_creador = empleado.id_empleado)
	ORDER BY empleado.nombre_empleado");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function listaPresupuestoVenta($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanSerialCarroceria;
	global $spanPlaca;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("presupuesto.estatus_presupuesto_venta IN (0)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(presupuesto.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = presupuesto.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(presupuesto.fecha_creacion) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("presupuesto.id_empleado_creador = %s",
			valTpDato($valCadBusq[3], "int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("presupuesto.estatus_presupuesto_venta = %s",
			valTpDato($valCadBusq[4], "int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(presupuesto.numero_presupuesto_venta LIKE %s		
		OR presupuesto.numero_presupuesto_venta LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR unidad.placa LIKE %s
		OR unidad.serial_carroceria LIKE %s)",
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"),
			valTpDato("%".$valCadBusq[5]."%", "text"));
	}
	
	$query = sprintf("SELECT 
		presupuesto.id_presupuesto_venta,
		presupuesto.numero_presupuesto_venta,
		presupuesto.id_tipo_contrato,
		presupuesto.id_empresa,
		presupuesto.id_cliente,
		presupuesto.observacion,
		presupuesto.id_unidad_fisica,
		presupuesto.condicion_pago,
		presupuesto.fecha_creacion,
		presupuesto.fecha_salida,
		presupuesto.fecha_entrada,
		presupuesto.dias_contrato,
		presupuesto.estatus_presupuesto_venta,
		tipo_contrato.nombre_tipo_contrato,
		empleado.nombre_empleado,
		unidad.placa,
		unidad.serial_carroceria,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		
		IF(presupuesto.id_cliente != presupuesto.id_cliente_pago,
			(SELECT CONCAT_WS(' ', cliente_pago.nombre, cliente_pago.apellido) 
				FROM cj_cc_cliente cliente_pago 
				WHERE cliente_pago.id = presupuesto.id_cliente_pago),
			NULL) AS nombre_cliente_pago,
		
		(presupuesto.subtotal - presupuesto.subtotal_descuento) AS total_neto,
		presupuesto.total_contrato AS total,
		
		(SELECT SUM(al_presupuesto_venta_iva.subtotal_iva) FROM al_presupuesto_venta_iva
		WHERE al_presupuesto_venta_iva.id_presupuesto_venta = presupuesto.id_presupuesto_venta) AS total_iva,
				
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa		
		FROM al_presupuesto_venta presupuesto
		INNER JOIN al_tipo_contrato tipo_contrato ON (presupuesto.id_tipo_contrato = tipo_contrato.id_tipo_contrato)
		INNER JOIN vw_iv_empresas_sucursales vw_iv_emp_suc ON (presupuesto.id_empresa = vw_iv_emp_suc.id_empresa_reg)
		INNER JOIN vw_pg_empleados empleado ON (presupuesto.id_empleado_creador = empleado.id_empleado)
		INNER JOIN cj_cc_cliente cliente ON (presupuesto.id_cliente = cliente.id)
		INNER JOIN an_unidad_fisica unidad ON (presupuesto.id_unidad_fisica = unidad.id_unidad_fisica)
		%s", $sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" class=\"\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td width=\"1%\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "15%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Creado");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "4%", $pageNum, "numero_presupuesto_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "nombre_tipo_contrato", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Contrato");		
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "25%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "serial_carroceria", $campOrd, $tpOrd, $valBusq, $maxRows, $spanSerialCarroceria);
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "fecha_salida", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Salida");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "fecha_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha Entrada");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "1%", $pageNum, "dias_contrato", $campOrd, $tpOrd, $valBusq, $maxRows, "D&iacute;as Contrato");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "condicion_pago", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaPresupuestoVenta", "6%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Presupuesto");
		$htmlTh .= "<td class=\"noprint\" colspan=\"4\" width=\"1%\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusPresupuesto = "";
		switch ($row['estatus_presupuesto_venta']){			
			case 0: $imgEstatusPresupuesto = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Presupuesto Sin Aprobar\"/>"; break;
			case 1: $imgEstatusPresupuesto = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Presupuesto Aprobado\"/>"; break;			
			case 2:	$imgEstatusPresupuesto = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Anulado\"/>"; break;
			default : $imgEstatusPresupuesto = "";
		}
		
		$clientePago = "";
		if($row['nombre_cliente_pago']){
			$clientePago = "<br><span class=\"textoNegrita_9px\">".utf8_encode($row["nombre_cliente_pago"])."</span>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPresupuesto."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i a", strtotime($row['fecha_creacion']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numero_presupuesto_venta']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_contrato'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente']).$clientePago."</td>";
			$htmlTb .= "<td>".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['serial_carroceria'])."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i a", strtotime($row['fecha_salida']))."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat." h:i a", strtotime($row['fecha_entrada']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['dias_contrato']."</td>";
			$htmlTb .= "<td align=\"center\"".(($row['condicion_pago'] == 0) ? "class=\"divMsjAlerta\"" : "class=\"divMsjInfo\"").">";
				$htmlTb .= ($row['condicion_pago'] == 0) ? "CRÉDITO": "CONTADO";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'], 2, ".", ",")."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('al_presupuesto_venta_form.php?id=%s','_self');\" src=\"../img/iconos/pencil.png\" title=\"Editar Presupuesto\"/>",
					$row['id_presupuesto_venta']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('al_presupuesto_venta_form.php?id=%s&acc=1','_self');\" src=\"../img/iconos/accept.png\" title=\"Aprobar Presupuesto\"/>",
					$row['id_presupuesto_venta']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"xajax_anularPresupuesto(%s);\" src=\"../img/iconos/cancel.png\" title=\"Anular Presupuesto\"/>",
					$row['id_presupuesto_venta']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"verVentana('reportes/al_presupuesto_venta_pdf.php?valBusq=%s', 960, 550);\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Presupuesto PDF\"/>",
					$row['id_presupuesto_venta']);
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuestoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuestoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPresupuestoVenta(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuestoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_al.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuestoVenta(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_al.gif\"/>");
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
		$htmlTb .= "<td colspan=\"25\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		
	$objResponse->assign("divlistaPresupuestoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($row = mysql_fetch_assoc($rs)) {
		$totalNeto += $row['total_neto'];
		$totalIva += $row['total_iva'];
		$totalFacturas += $row['total'];
	}
	
	$objResponse->assign("spnTotalNeto","innerHTML",number_format($totalNeto, 2, ".", ","));
	$objResponse->assign("spnTotalIva","innerHTML",number_format($totalIva, 2, ".", ","));
	$objResponse->assign("spnTotalFacturas","innerHTML",number_format($totalFacturas, 2, ".", ","));
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"anularPresupuesto");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaPresupuestoVenta");
?>