<?php


function buscarPresupuesto($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		$frmBuscar['txtFechaDesde'],
		$frmBuscar['txtFechaHasta'],
		$frmBuscar['lstEstatusPedido'],
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaPresupuesto(0, "CONVERT(numeracion_presupuesto, SIGNED)", "DESC", $valBusq));
	
	return $objResponse;
}

function listaPresupuesto($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanInicial;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("pres_vent.estado NOT IN (0,3)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pres_vent.id_empresa = %s
		OR %s IN (SELECT suc.id_empresa_padre FROM pg_empresa suc
				WHERE suc.id_empresa = pres_vent.id_empresa))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("pres_vent.fecha BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		switch ($valCadBusq[3]) {
			case "00" : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 0)");
			case "22" : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 2)");
			case "33" : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 3)");
			default : $sqlBusq .= $cond.sprintf("(pres_vent.estado = 1 AND estado_pedido = %s)",
						valTpDato($valCadBusq[3], "int"));
		}
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pres_vent.id_presupuesto LIKE %s
		OR numeracion_presupuesto LIKE %s
		OR ped.id_cliente LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"),
			valTpDato("%".$valCadBusq[4]."%", "text"));
	}
	
	$query = sprintf("SELECT
		pres_vent.id_presupuesto,
		pres_vent.numeracion_presupuesto,
		pres_vent.fecha,
		pres_vent_acc.id_presupuesto_accesorio,
		CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		CONCAT('[', uni_bas.nom_uni_bas, ']: ', marca.nom_marca, ' ', modelo.nom_modelo, ' ', vers.nom_version, ' ', ano.nom_ano) AS vehiculo,
		
		(pres_vent.precio_venta
			+ (SELECT SUM(pres_vent.precio_venta * iva.iva / 100)
				FROM an_unidad_basica_impuesto uni_bas_impuesto
					INNER JOIN pg_iva iva ON (uni_bas_impuesto.id_impuesto = iva.idIva)
				WHERE uni_bas_impuesto.id_unidad_basica = pres_vent.id_uni_bas
					AND iva.tipo IN (6,9,2))) AS precio_venta,
		
		pres_vent.porcentaje_inicial,
		pres_vent.monto_inicial,
		pres_vent.total_general,
		
		(SELECT COUNT(*) FROM an_unidad_fisica
		WHERE id_uni_bas = pres_vent.id_uni_bas
			AND propiedad = 'PROPIO'
			AND estado_venta = 'DISPONIBLE') AS ud,
		
		pres_vent.estado AS estado_presupuesto,
		ped.estado_pedido
	FROM an_presupuesto pres_vent
		INNER JOIN cj_cc_cliente cliente ON (pres_vent.id_cliente = cliente.id)
		INNER JOIN an_uni_bas uni_bas ON (pres_vent.id_uni_bas = uni_bas.id_uni_bas)			
			INNER JOIN an_ano ano ON (uni_bas.ano_uni_bas = ano.id_ano)
			INNER JOIN an_version vers ON (uni_bas.ver_uni_bas = vers.id_version)
			INNER JOIN an_modelo modelo ON (vers.id_modelo = modelo.id_modelo)
			INNER JOIN an_marca marca ON (modelo.id_marca = marca.id_marca)
		INNER JOIN an_transmision trans ON (uni_bas.trs_uni_bas = trans.id_transmision)
		LEFT JOIN an_pedido ped ON (pres_vent.id_presupuesto = ped.id_presupuesto)
		LEFT JOIN an_presupuesto_accesorio pres_vent_acc ON (pres_vent.id_presupuesto = pres_vent_acc.id_presupuesto) %s", $sqlBusq);
	
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

	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "7%", $pageNum, "fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "8%", $pageNum, "CONVERT(numeracion_presupuesto, SIGNED)", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "8%", $pageNum, "id_presupuesto_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro. Presupuesto Accesorios");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "24%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "26%", $pageNum, "vehiculo", $campOrd, $tpOrd, $valBusq, $maxRows, "Vehículo");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "9%", $pageNum, "precio_venta", $campOrd, $tpOrd, $valBusq, $maxRows, "Precio Venta");
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "9%", $pageNum, "porcentaje_inicial", $campOrd, $tpOrd, $valBusq, $maxRows, $spanInicial);
		$htmlTh .= ordenarCampo("xajax_listaPresupuesto", "9%", $pageNum, "total_general", $campOrd, $tpOrd, $valBusq, $maxRows, "Total General");
		$htmlTh .= "<td colspan=\"2\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$imgEstatusPedido = "";
		if ($row['estado_presupuesto'] == 0) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Presupuesto Autorizado\"/>";
		} else if ($row['estado_presupuesto'] == 1) {
			switch ($row['estado_pedido']) {
				case 1 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Pedido Autorizado\"/>"; break;
				case 2 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_morado.gif\" title=\"Facturado\"/>"; break;
				case 3 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Pedido Desautorizado\"/>"; break;
				case 4 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Nota de Crédito\"/>"; break;
				case 5 : $imgEstatusPedido = "<img src=\"../img/iconos/ico_marron.gif\" title=\"Anulado\"/>"; break;
			}
		} else if ($row['estado_presupuesto'] == 2) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Presupuesto Anulado\"/>";
		} else if ($row['estado_presupuesto'] == 3) {
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Presupuesto Desautorizado\"/>";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatusPedido."</td>";
			$htmlTb .= "<td align=\"center\">".date(spanDateFormat, strtotime($row['fecha']))."</td>";
			$htmlTb .= "<td align=\"right\">".$row['numeracion_presupuesto']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_presupuesto_accesorio']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= utf8_encode($row['vehiculo']);
				$htmlTb .= ($row['ud'] > 0) ? "<br><span class=\"textoNegrita_10px\">Disponible: ".number_format($row['ud'], 2, ".", ",")."</span>" : "";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['precio_venta'], 2, ".", ",")."</td>";
			$htmlTb .= "<td align=\"right\">";
				$htmlTb .= number_format($row['monto_inicial'], 2, ".", ",");
				$htmlTb .= "<br><span class=\"textoNegrita_10px\">".number_format($row['porcentaje_inicial'], 2, ".", ",")."%</span>";
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_general'], 2, ".", ",")."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero\" onclick=\"window.open('an_ventas_presupuesto_editar.php?view=1&id=%s','_self');\" src=\"../img/iconos/ico_view.png\" title=\"Ver Presupuesto\"/></td>",
				$row['id_presupuesto']);
			$htmlTb .= "<td>";
			if ($row['estado_presupuesto'] == 0){
				$htmlTb .= sprintf("<img class=\"puntero\" onclick=\"window.open('an_combo_presupuesto_list.php?view=1&id=%s','_self');\" src=\"../img/iconos/generarPresupuesto.png\" title=\"Presupuesto Accesorios\"/>",
					$row['id_presupuesto']);
			}
			$htmlTb .= "</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"11\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaPresupuesto(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"11\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaPresupuesto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"listaPresupuesto");
?>