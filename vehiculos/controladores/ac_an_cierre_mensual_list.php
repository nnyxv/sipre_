<?php
set_time_limit(0);
ini_set('memory_limit', '-1');
function aprobarCierreMensual($idCierreMensual, $frmListaCierreMensual) {
	$objResponse = new xajaxResponse();
	
	mysql_query("START TRANSACTION;");
	
	$updateSQL = sprintf("UPDATE an_cierre_mensual SET
		estatus = %s
	WHERE id_cierre_mensual = %s",
		valTpDato(1, "text"), // 0 = Pendiente, 1 = Aprobado
		valTpDato($idCierreMensual, "int"));
	mysql_query("SET NAMES 'utf8'");
	$Result1 = mysql_query($updateSQL);
	if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	mysql_query("COMMIT;");
	
	$objResponse->alert(utf8_encode("Cierre Aprobado con Éxito"));
	
	$objResponse->loadCommands(listaCierreMensual(
		$frmListaCierreMensual['pageNum'],
		$frmListaCierreMensual['campOrd'],
		$frmListaCierreMensual['tpOrd'],
		$frmListaCierreMensual['valBusq']));
	
	return $objResponse;
}

function buscar($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['lstMes'],
		$frmBuscar['lstAno']);
	
	$objResponse->loadCommands(listaCierreMensual(0, "id_cierre_mensual", "DESC", $valBusq));
	
	return $objResponse;
}

function cargaLstAno($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstAno\" name=\"lstAno\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['nom_ano']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['nom_ano']."\">".utf8_encode($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstAno","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMes($selId = "") {
	$objResponse = new xajaxResponse();
	
	global $mes;
	
	$html = "<select id=\"lstMes\" name=\"lstMes\" class=\"inputHabilitado\" onchange=\"byId('btnBuscar').click();\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	for ($contMes = 1; $contMes <= 12; $contMes++) {
		$selected = ($selId == $contMes) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$disabled." value=\"".$contMes."\">".str_pad($contMes, 2, "0", STR_PAD_LEFT).".- ".$mes[$contMes]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMes","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMesAno($idEmpresa) {
	$objResponse = new xajaxResponse();
	
	if (date("m") == 1) {
		$anoInicio = date("Y")-1;
		$anoLimite = date("Y");
	} else {
		$anoInicio = date("Y");
		$anoLimite = date("Y");
	}
	
	$html = "<select id=\"lstMesAno\" name=\"lstMesAno\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	for ($ano = $anoInicio; $ano <= $anoLimite; $ano++) {
		for ($mes = 1; (($mes <= 12 && $anoInicio != $anoLimite && $ano == $anoInicio) || ($mes <= date("m") && $ano == $anoLimite)); $mes++) {
			$query = sprintf("SELECT * FROM an_cierre_mensual cierre_mens
			WHERE cierre_mens.mes = %s
				AND cierre_mens.ano = %s
				AND id_empresa = %s;",
				valTpDato(intval($mes), "int"),
				valTpDato($ano, "int"),
				valTpDato($idEmpresa, "int"));
			$rs = mysql_query($query);
			if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			$totalRows = mysql_num_rows($rs);
			$row = mysql_fetch_assoc($rs);
			
			$disabled = ($totalRows > 0) ? "disabled=\"disabled\"" : "";
			
			$html .= "<option ".$disabled." value=\"".str_pad($mes, 2, "0", STR_PAD_LEFT)."/".$ano."\">".str_pad($mes, 2, "0", STR_PAD_LEFT)."/".$ano."</option>";
		}
	}
	$html .= "</select>";
	$objResponse->assign("tdlstMesAno","innerHTML",$html);
	
	return $objResponse;
}

function formCierreMensual() {
	$objResponse = new xajaxResponse();

	$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa"));
	$objResponse->loadCommands(cargaLstMesAno($_SESSION['idEmpresaUsuarioSysGts']));

	return $objResponse;
}

function guardarCierreMensual($frmCierreMensual, $frmListaCierreMensual) {
	$objResponse = new xajaxResponse();
	
	$idEmpresa = $frmCierreMensual['txtIdEmpresa'];
	$arrayFecha = explode("/",$frmCierreMensual['lstMesAno']);
	$mesCierre = $arrayFecha[0];
	$anoCierre = $arrayFecha[1];
	
	// BUSCA LOS DOCUMENTOS DE VENTAS PENDIENTES POR FACTURAR O ANULAR
	$query = sprintf("SELECT * FROM an_cierre_mensual WHERE mes < %s;",
	 valTpDato($mesCierre, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$valCierre = (($mesCierre - 1) <= $totalRows)? true : false;
	
 	//if ($idEmpresa > 0 && $arrayFecha != '' && $valCierre) {
		mysql_query("START TRANSACTION;");
		
		require_once("../controladores/ac_pg_calcular_comision.php");
		global $conex;
		
		$query = sprintf("SELECT idFactura FROM cj_cc_encabezadofactura enfac
		WHERE idDepartamentoOrigenFactura IN (%s)
			AND (YEAR(enfac.fechaRegistroFactura) < %s
				OR (YEAR(enfac.fechaRegistroFactura) = %s AND MONTH(enfac.fechaRegistroFactura) <= %s))
			AND enfac.estatus_factura = %s;",
			valTpDato(2, "int"),
			valTpDato($anoCierre,"int"),
			valTpDato($anoCierre,"int"),
			valTpDato($mesCierre,"int"),
			valTpDato(2, "int"));
		$rs = mysql_query($query, $conex);
		$numRow = mysql_num_rows($rs);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = generarComision($row['idFactura'], false, $mesCierre, $anoCierre);
			if ($Result1[0] != true) { return $objResponse->alert($Result1[1]); }
		}
		
		$query = sprintf("SELECT * FROM cj_cc_notacredito
		WHERE idDepartamentoNotaCredito IN (%s)
			AND (YEAR(fechaNotaCredito) < %s
					OR (YEAR(fechaNotaCredito) = %s AND MONTH(fechaNotaCredito) <= %s))
			AND estatus_nota_credito = %s
		ORDER BY IdNotaCredito ASC;",
			valTpDato(2, "int"),
			valTpDato($anoCierre,"int"),
			valTpDato($anoCierre,"int"),
			valTpDato($mesCierre,"int"),
			valTpDato(2, "int"));
		$rs = mysql_query($query, $conex);
		if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = devolverComision($row['idNotaCredito'], false, $mesCierre, $anoCierre);
			if ($Result1[0] != true) { return $objResponse->alert($Result1[1]); }
		}
	
		// INSERTA LOS DATOS DEL CIERRE MENSUAL
		$insertSQL = sprintf("INSERT INTO an_cierre_mensual (id_empresa, mes, ano, id_empleado_creador)
		SELECT
			config_emp.id_empresa,
			%s,
			%s,
			%s
		FROM pg_configuracion_empresa config_emp
		WHERE config_emp.id_configuracion = 12 AND config_emp.status = 1 AND config_emp.id_empresa = %s;",
				valTpDato($mesCierre, "int"),
				valTpDato($anoCierre, "int"),
				valTpDato($_SESSION['idEmpleadoSysGts'], "int"),
				valTpDato($idEmpresa, "int"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idCierreMensual = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		
		mysql_query("COMMIT;");
		
		$objResponse->alert("Cierre Creado Satisfactoriamente");
		
		$objResponse->script("byId('btnCancelarCierreMensual').click();");
		
		$objResponse->loadCommands(listaCierreMensual(
			$frmListaCierreMensual['pageNum'],
			$frmListaCierreMensual['campOrd'],
			$frmListaCierreMensual['tpOrd'],
			$frmListaCierreMensual['valBusq']));
 	//} else {
 		//$objResponse->alert(utf8_encode("Aún existen Cierres Mensuales anteriores que deben ser gestionados"));
 	//}

	return $objResponse;
}

function listaCierreMensual($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $mes;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cierre_mensual.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("mes LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("ano LIKE %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	$query = sprintf("SELECT
		cierre_mensual.*,
		CONCAT_WS(' ', empleado.nombre_empleado, empleado.apellido) AS nombre_empleado,
		
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
		
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
		INNER JOIN an_cierre_mensual cierre_mensual ON (vw_iv_emp_suc.id_empresa_reg = cierre_mensual.id_empresa)
		INNER JOIN pg_empleado empleado ON (cierre_mensual.id_empleado_creador = empleado.id_empleado) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "8%", $pageNum, "fecha_creacion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha"));
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "32%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "30%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado Creador"));
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "10%", $pageNum, "mes", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Mes"));
		$htmlTh .= ordenarCampo("xajax_listaCierreMensual", "10%", $pageNum, "ano", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Año"));
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha_creacion']))."<br>".date("h:i:s a", strtotime($row['fecha_creacion']))."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td align=\"center\">".str_pad($row['mes'], 2, "0", STR_PAD_LEFT)."<br>(".$mes[$row['mes']].")</td>";
			$htmlTb .= "<td align=\"center\">".$row['ano']."</td>";
			$htmlTb .= "<td align=\"center\">";
			if ($row['estatus'] == 0) {
				$htmlTb .= sprintf("<img class=\"puntero\" id=\"imgAprobarCierre%s\" src=\"../img/iconos/accept.png\" onclick=\"validarFrmAprobarCierre('%s');\" title=\"".utf8_encode("Aprobar Cierre")."\">",
					$contFila,
					$row['id_cierre_mensual']);
			}
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCierreMensual(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
					$htmlTf .= "</tr>";
					$htmlTf .= "</table>";
				$htmlTf .= "</td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTblFin = "</table>";
	
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
	
	$objResponse->assign("divListaCierreMensual","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"aprobarCierreMensual");
$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstAno");
$xajax->register(XAJAX_FUNCTION,"cargaLstMes");
$xajax->register(XAJAX_FUNCTION,"cargaLstMesAno");
$xajax->register(XAJAX_FUNCTION,"formCierreMensual");
$xajax->register(XAJAX_FUNCTION,"guardarCierreMensual");
$xajax->register(XAJAX_FUNCTION,"listaCierreMensual");

function calcularCierreAnual($idEmpresa, $mesCierre = "", $anoCierre = "") {
	global $conex;
	global $mes;
	
	mysql_query("START TRANSACTION;");
	
	// BUSCA LOS ARTICULOS DE LA EMPRESA QUE ESTEN REGISTRADO EN EL KARDEX
	$queryArt = sprintf("SELECT
		art_emp.id_articulo,
		IFNULL((art_emp.cantidad_compra + art_emp.cantidad_entrada) - (art_emp.cantidad_venta + art_emp.cantidad_salida), 0) AS existencia,
		
		(SELECT COUNT(id_articulo) FROM iv_cierre_anual
		WHERE id_empresa = art_emp.id_empresa
			AND id_articulo = art_emp.id_articulo
			AND ano = %s) AS cant_cierre
	FROM iv_articulos_empresa art_emp
	WHERE id_empresa = %s;",
		valTpDato($anoCierre, "int"),
		valTpDato($idEmpresa, "int"));
	$rsArt = mysql_query($queryArt);
	if (!$rsArt) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowArt = mysql_fetch_assoc($rsArt)) {
		$idArticulo = $rowArt['id_articulo'];
		
		if ($rowArt['cant_cierre'] > 0) {
			$updateSQL = sprintf("UPDATE iv_cierre_anual SET
				%s = 0,
				cantidad_saldo = %s
			WHERE id_empresa = %s
				AND id_articulo = %s
				AND ano = %s;",
				valTpDato(strtolower($mes[intval($mesCierre)]),"campo"),
				valTpDato($rowArt['existencia'], "real_inglesa"),
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($anoCierre, "int"));
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		} else {
			$insertSQL = sprintf("INSERT INTO iv_cierre_anual (id_empresa, id_articulo, ano, cantidad_saldo)
			VALUE (%s, %s, %s, %s);",
				valTpDato($idEmpresa, "int"),
				valTpDato($idArticulo, "int"),
				valTpDato($anoCierre, "int"),
				valTpDato($rowArt['existencia'], "real_inglesa"));
			$Result1 = mysql_query($insertSQL);
			if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		}
	}
	
	// BUSCA LOS MESES DEL AÑO QUE TENGAN REGISTRADO VENTAS DEL ARTICULO
	$queryMes = sprintf("SELECT
		kardex.id_articulo,
		SUM(kardex.cantidad) AS cantidad,
		MONTH(kardex.fecha_movimiento) AS mes_movimiento
	FROM iv_kardex kardex
	WHERE kardex.tipo_movimiento IN (3)
		AND ((CASE kardex.tipo_movimiento
				WHEN 1 THEN -- COMPRA
					(SELECT id_empresa FROM cp_factura WHERE id_factura = kardex.id_documento)
				WHEN 2 THEN -- ENTRADA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- ENTRADA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN
									(SELECT id_empresa FROM iv_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
								WHEN 1 THEN
									(SELECT id_empresa FROM sa_vale_entrada WHERE id_vale_entrada = kardex.id_documento)
							END)
						WHEN 2 THEN -- ENTRADA CON NOTA DE CREDITO
							(SELECT id_empresa FROM cj_cc_notacredito WHERE idNotaCredito = kardex.id_documento)
					END)
				WHEN 3 THEN -- VENTA
					(SELECT id_empresa FROM cj_cc_encabezadofactura WHERE idFactura = kardex.id_documento)
				WHEN 4 THEN -- SALIDA
					(CASE kardex.tipo_documento_movimiento
						WHEN 1 THEN -- SALIDA CON VALE
							(CASE kardex.id_modulo
								WHEN 0 THEN -- REPUESTOS
									(SELECT id_empresa FROM iv_vale_salida WHERE id_vale_salida = kardex.id_documento)
								WHEN 1 THEN -- SERVICIOS
									(SELECT id_empresa FROM sa_vale_salida WHERE id_vale_salida = kardex.id_documento)
							END)
						WHEN 2 THEN -- SALIDA CON NOTA DE CREDITO
							(SELECT nota_cred.id_empresa FROM cp_notacredito nota_cred WHERE nota_cred.id_notacredito = kardex.id_documento)
					END)
			END) = %s
			OR %s IN (SELECT almacen.id_empresa
					FROM iv_calles calle
						INNER JOIN iv_almacenes almacen ON (calle.id_almacen = almacen.id_almacen)
						INNER JOIN iv_estantes estante ON (calle.id_calle = estante.id_calle)
						INNER JOIN iv_tramos tramo ON (estante.id_estante = tramo.id_estante)
						INNER JOIN iv_casillas casilla ON (tramo.id_tramo = casilla.id_tramo)
					WHERE casilla.id_casilla = kardex.id_casilla))
		AND MONTH(kardex.fecha_movimiento) = %s
		AND YEAR(kardex.fecha_movimiento) = %s
	GROUP BY kardex.id_articulo, MONTH(kardex.fecha_movimiento)
	ORDER BY CONCAT_WS(' ', DATE(kardex.fecha_movimiento), kardex.hora_movimiento) ASC;",
		valTpDato($idEmpresa, "int"),
		valTpDato($idEmpresa, "int"),
		valTpDato(intval($mesCierre), "int"),
		valTpDato(intval($anoCierre), "int"));
	$rsMes = mysql_query($queryMes);
	if (!$rsMes) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowMes = mysql_fetch_assoc($rsMes)) {
		$idArticulo = $rowMes['id_articulo'];
		
		$updateSQL = sprintf("UPDATE iv_cierre_anual SET
			%s = %s
		WHERE id_empresa = %s
			AND id_articulo = %s
			AND ano = %s;",
			valTpDato(strtolower($mes[intval($mesCierre)]),"campo"),
			valTpDato($rowMes['cantidad'], "int"),
			valTpDato($idEmpresa, "int"),
			valTpDato($idArticulo, "int"),
			valTpDato($anoCierre, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	if (intval($mesCierre) == 1) {
		$updateSQL = sprintf("UPDATE iv_cierre_anual a, iv_cierre_anual b SET
			a.ano_pasado = a.ano - 1,
			a.enero_pasado = b.enero,
			a.febrero_pasado = b.febrero,
			a.marzo_pasado = b.marzo,
			a.abril_pasado = b.abril,
			a.mayo_pasado = b.mayo,
			a.junio_pasado = b.junio,
			a.julio_pasado = b.julio,
			a.agosto_pasado = b.agosto,
			a.septiembre_pasado = b.septiembre,
			a.octubre_pasado = b.octubre,
			a.noviembre_pasado = b.noviembre,
			a.diciembre_pasado = b.diciembre
		WHERE a.id_articulo = b.id_articulo
			AND a.id_empresa = b.id_empresa
			AND b.ano = a.ano - 1
			AND a.ano = %s
			AND a.id_empresa = %s;",
			valTpDato($anoCierre, "int"),
			valTpDato($idEmpresa, "int"));
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	mysql_query("COMMIT;");
	
	return array(true, "");
}

function calcularConsumoPromedioVentas($idEmpresa, $idArticulo, $anoCierre, $mesesPromedio, $diasHabiles) {
	global $mes;
	
	$nroMesInicioCalculo = date("m") - $mesesPromedio;
	$nroAnoInicioCalculo = $anoCierre;
	
	if ($nroMesInicioCalculo <= 0) {
		$nroMesInicioCalculo += 12;
		$nroAnoInicioCalculo = $anoCierre - 1;
	}
	
	$mesCont = $nroMesInicioCalculo;
	$anoCont = $nroAnoInicioCalculo;
	$cont = 0;
	for ($cont = 0; $cont <= $mesesPromedio - 1; $cont++) {
		$arrayFechasCalculos[$cont][0] = $mesCont;
		$arrayFechasCalculos[$cont][1] = $anoCont;
		
		$mesCont++;
		
		if ($mesCont > 12) {
			$mesCont = 1;
			$anoCont++;
		}
	}
	
	$query = sprintf("SELECT * FROM iv_cierre_anual
	WHERE ano = %s
		AND id_empresa = %s
		AND id_articulo = %s;",
		valTpDato($anoCierre, "date"),
		valTpDato($idEmpresa, "int"),
		valTpDato($idArticulo, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while($row = mysql_fetch_assoc($rs)) {
		$cantTotArt = 0;
		if (isset($arrayFechasCalculos)) {
			foreach ($arrayFechasCalculos as $indice => $valor) {
				if ($row['ano_pasado'] == $arrayFechasCalculos[$indice][1]) {
					$nroMes = $arrayFechasCalculos[$indice][0];
					$cantidad = $row[strtolower($mes[$nroMes])."_pasado"];
				} else if ($row['ano'] == $arrayFechasCalculos[$indice][1]) {
					$nroMes = $arrayFechasCalculos[$indice][0];
					$cantidad = $row[strtolower($mes[$nroMes])];
				} else {
					$cantidad = 0;
				}
				$cantTotArt += $cantidad;
			}
		}
	}
	
	$promedioDiario = $cantTotArt / ($mesesPromedio * $diasHabiles);
	$promedioMensual = $promedioDiario * $diasHabiles;
	
	$array[] = $cantTotArt;
	$array[] = $promedioMensual;
	$array[] = $promedioDiario;
	
	return $array;
}
?>