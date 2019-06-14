<?php

    function buscar($valForm) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s", $valForm['txtCriterio']);

        $objResponse->loadCommands(listadoEnlacesGarantias(0, 'numero_orden','ASC', $valBusq));

	return $objResponse;
    }

    function buscarOrden($valForm) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
                            $valForm['txtCriterio'],
                            $valForm['fechaDesde'],
                            $valForm['fechaHasta']);

        $objResponse->loadCommands(listadoOrdenes(0, 'numero_orden','ASC', $valBusq));

	return $objResponse;
    }

    function buscarVale($valForm, $valFormBusq) {
	$objResponse = new xajaxResponse();

        if($valForm['idVale'] != ""){
            $idvale= implode(",", $valForm['idVale']);
        }else{
            $idvale= "";
        }

	$valBusq = sprintf("%s|%s|%s|%s|%s",
                            $idvale,
                            $valFormBusq['txtCriterio'],
                            $valFormBusq['fechaDesde'],
                            $valFormBusq['fechaHasta'],
                            $valFormBusq['idEnlace']);

        $objResponse->loadCommands(listadoVales(0, 'numero_vale','ASC', $valBusq));

	return $objResponse;
    }

    function listadoEnlacesGarantias($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse= new xajaxResponse();
        
	$valCadBusq= explode("|", $valBusq);
	$startRow= $pageNum * $maxRows;

        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("seg.id_orden= so.id_orden AND so.id_empresa = %s",
								valTpDato($_SESSION["idEmpresaUsuarioSysGts"],"int"));

        if($valCadBusq[0] != "-1" && $valCadBusq[0] != ""){
            $cond= (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			
			 $sqlBusq.= $cond.sprintf("(seg.numero_referencia LIKE %s
                                        OR numero_orden LIKE %s)",
                    valTpDato("%".$valCadBusq[2]."%", "text"),
                    valTpDato("%".$valCadBusq[2]."%", "text"));
			
			//tan antiguo que no funciona...
			/*
            $sqlBusq.= $cond.sprintf("c.descripcion_concepto LIKE %s
                                        OR concat(c.id_modulo,c.id_tipo_documento,c.id_condicion_pago,c.secuencial) LIKE %s",
                    valTpDato("%".$valCadBusq[2]."%", "text"),
                    valTpDato("%".$valCadBusq[2]."%", "text"));*/
	}

        $query = "SELECT
                                seg.id_enlace_garantia,
                                seg.id_orden,
								so.numero_orden,
                                DATE_FORMAT(seg.fecha_creacion, '%d-%m-%Y') AS fecha_creacion,
                                seg.estatus,
                                seg.numero_referencia,
                                seg.monto_total_vale,
                                (SELECT sto.nombre_tipo_orden FROM sa_tipo_orden sto WHERE sto.id_tipo_orden= so.id_tipo_orden) AS nombre_tipo_orden,
                                (SELECT IFNULL(
                                    (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id=
                                        (SELECT sr.id_cliente_pago FROM sa_recepcion sr WHERE sr.id_recepcion= so.id_recepcion)),
                                    (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id=
                                        (SELECT sc.id_cliente_contacto FROM sa_cita sc WHERE sc.id_cita=
                                            (SELECT sr.id_cita FROM sa_recepcion sr WHERE sr.id_recepcion= so.id_recepcion)))
                                            )
                                ) AS nombre_cliente,
                                (((so.subtotal - so.subtotal_descuento) * so.iva / 100) + (so.subtotal - so.subtotal_descuento)) AS total_orden,
                               
                                seg.id_usuario_creador,
                                seg.id_usuario_aprobacion
                            FROM
                                sa_enlace_garantia seg,
                                sa_orden so ".$sqlBusq;

        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	
                $htmlTh .= ordenarCampo("xajax_listadoEnlacesGarantias", "10%", $pageNum, "numero_referencia", $campOrd, $tpOrd, $valBusq, $maxRows, "N&ordm; Referencia");
		$htmlTh .= ordenarCampo("xajax_listadoEnlacesGarantias", "10%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "N&ordm; Orden");
		$htmlTh .= ordenarCampo("xajax_listadoEnlacesGarantias", "10%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");
                $htmlTh .= ordenarCampo("xajax_listadoEnlacesGarantias", "30%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Cliente");
                $htmlTh .= ordenarCampo("xajax_listadoEnlacesGarantias", "10%", $pageNum, "fecha_creacion", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
                $htmlTh .= ordenarCampo("xajax_listadoEnlacesGarantias", "12%", $pageNum, "total_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Total Orden");
                $htmlTh .= ordenarCampo("xajax_listadoEnlacesGarantias", "12%", $pageNum, "monto_total_vale", $campOrd, $tpOrd, $valBusq, $maxRows, "Monto Total Vales");
                $htmlTh .= ordenarCampo("xajax_listadoEnlacesGarantias", "6%", $pageNum, "estatus", $campOrd, $tpOrd, $valBusq, $maxRows, "Estatus");
		$htmlTh .= "<td class='noprint'></td>";
                $htmlTh .= "<td class='noprint'></td>";
                $htmlTh .= "<td class='noprint'></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";

		$contFila ++;

		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";

                $htmlTb .= "<td align=\"center\">".$row['numero_referencia']."</td>";
                $htmlTb .= "<td align=\"center\" idordenoculta=\"".$row['id_orden']."\" >".$row['numero_orden']."</td>";
                $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
                $htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente'])."</td>";
                $htmlTb .= "<td align=\"center\">".$row['fecha_creacion']."</td>";
                $htmlTb .= "<td align=\"right\">".number_format($row['total_orden'],2,".",",")."</td>";
                $htmlTb .= "<td align=\"right\">".number_format($row['monto_total_vale'],2,".",",")."</td>";
                if($row['estatus'] == 0){
                    $htmlTb .= "<td align=\"center\">No Aprobada</td>";
                }else{
                    $htmlTb .= "<td align=\"center\">Aprobada</td>";
                }
                $htmlTb .= "<td align=\"center\" class='noprint'><img class=\"puntero\" onclick=\"xajax_buscarEnlaceGarantia(".$row['id_enlace_garantia'].", 3)\" src=\"../img/iconos/ico_view.png\"/></td>";
                if($row['estatus'] == 0){
                    $htmlTb .= "<td align=\"center\" class='noprint'><img class=\"puntero\" onclick=\"xajax_buscarEnlaceGarantia(".$row['id_enlace_garantia'].", 2)\" src=\"../img/iconos/ico_edit.png\"/></td>";
                    $htmlTb .= "<td align=\"center\" class='noprint'><img class=\"puntero\" onclick=\"aprobar(".$row['id_enlace_garantia'].", '".number_format($row['total_orden'],2,".",",")."', '".number_format($row['monto_total_vale'],2,".",",")."')\" src=\"../img/iconos/aprobar_presup.png\"/></td>";
                }else{
                    $htmlTb .= "<td align=\"center\" class='noprint'><img src=\"../img/iconos/ico_edit_disabled.png\"/></td>";
                    $htmlTb .= "<td align=\"center\" class='noprint'><img src=\"../img/iconos/aprobar_presup_disabled.png\"/></td>";
                }
                $htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEnlacesGarantias(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEnlacesGarantias(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEnlacesGarantias(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEnlacesGarantias(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEnlacesGarantias(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}


	$objResponse->assign("tdListadoEnlaces","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

        return $objResponse;
    }

    function listadoOrdenes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
        $objResponse= new xajaxResponse();

	$valCadBusq= explode("|", $valBusq);
	$startRow= $pageNum * $maxRows;

		$sqlBusq = "WHERE so.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']."";

        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("so.id_tipo_orden IN (1,2) AND so.id_orden NOT IN (SELECT id_orden FROM sa_enlace_garantia)");

        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("(so.id_recepcion IN
                                        (SELECT id_recepcion FROM sa_recepcion WHERE id_cliente_pago IN (SELECT id_cliente FROM sa_cliente_garantia))
                                   OR so.id_recepcion IN
                                        (SELECT id_recepcion FROM sa_recepcion WHERE id_cita IN
                                                (SELECT id_cita FROM sa_cita WHERE id_cliente_contacto IN(SELECT id_cliente FROM sa_cliente_garantia))))");

        if($valCadBusq[0] != "-1" && $valCadBusq[0] != ""){
            $cond= (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq.= $cond." so.numero_orden LIKE '%".$valCadBusq[0]."%'";
	}

        if($valCadBusq[1] != "" && $valCadBusq[2] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf(" so.tiempo_orden BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
        }

        $query = "SELECT
                        so.*,
                        (SELECT sto.nombre_tipo_orden FROM sa_tipo_orden sto WHERE sto.id_tipo_orden= so.id_tipo_orden) AS nombre_tipo_orden,
                        (SELECT IFNULL(
                            (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id=
                                (SELECT sr.id_cliente_pago FROM sa_recepcion sr WHERE sr.id_recepcion= so.id_recepcion)),
                            (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id=
                                (SELECT sc.id_cliente_contacto FROM sa_cita sc WHERE sc.id_cita=
                                    (SELECT sr.id_cita FROM sa_recepcion sr WHERE sr.id_recepcion= so.id_recepcion)))
                                    )
                        ) AS nombre_cliente,
                        (((so.subtotal - so.subtotal_descuento) * so.iva / 100) + (so.subtotal - so.subtotal_descuento)) AS total_orden
                    FROM
                        sa_orden so ".$sqlBusq;

        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
   
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
                $htmlTh .= "<td class='noprint'></td>";
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "15%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "N&ordm; Orden");
		$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "15%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");
                $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "50%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Cliente");
                $htmlTh .= ordenarCampo("xajax_listadoOrdenes", "20%", $pageNum, "total_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Orden");
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";

		$contFila ++;

		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";

                $htmlTb .= "<td align=\"center\" class='noprint'>
                                <button type=\"button\" onclick=\"asignarOrden('".$row['id_orden']."', '".utf8_encode($row['nombre_cliente'])."', '".number_format($row['total_orden'],2,".",",")."', '".$row['numero_orden']."');\" title=\"Seleccionar Orden\">
                                    <img src=\"../img/iconos/ico_aceptar.gif\"/>
                                </button>
                            </td>";
                $htmlTb .= "<td align=\"center\" idordenoculta=\"".$row['id_orden']."\" >".$row['numero_orden']."</td>";
                $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
                $htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente'])."</td>";
                $htmlTb .= "<td align=\"right\">".number_format($row['total_orden'],2,".",",")."</td>";
                $htmlTb .= "</tr>";
	}

$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("tdListadoOrdenes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
        $objResponse->script("if($('divFlotanteOrdenes').style.display == 'none'){
                                $('divFlotanteOrdenes').style.display = '';
                                centrarDiv($('divFlotanteOrdenes'));
                              }");

        return $objResponse;

    }

    function listadoVales($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
        $objResponse= new xajaxResponse();

	$startRow= $pageNum * $maxRows;

        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq.= $cond.sprintf("so.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']." AND so.id_estado_orden = 24 AND so.id_tipo_orden= 3 AND so.id_orden= sv.id_orden
                                    AND sv.id_vale_salida NOT IN(SELECT id_vale_salida FROM sa_det_enlace_garantia");
        
        $busq= explode("|", $valBusq);

        if($busq[4] != "-1" && $busq[4] != ""){
            $sqlBusq.= " WHERE id_enlace_garantia != ".$busq[4];
        }
        $sqlBusq.= ")";
        
        if($busq[0] != "-1" && $busq[0] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("sv.id_vale_salida NOT IN(".$busq[0].")");
        }

        if($busq[1] != "-1" && $busq[1] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."sv.numero_vale LIKE '%".$busq[1]."%'" ;
        }

        if($busq[2] != "" && $busq[3] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("fecha_vale BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($busq[2])),"date"),
			valTpDato(date("Y-m-d", strtotime($busq[3])),"date"));
        }

        $query = "SELECT
                        so.*,
                        (SELECT sto.nombre_tipo_orden FROM sa_tipo_orden sto WHERE sto.id_tipo_orden= so.id_tipo_orden) AS nombre_tipo_orden,
                        (SELECT IFNULL(
                            (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id=
                                (SELECT sr.id_cliente_pago FROM sa_recepcion sr WHERE sr.id_recepcion= so.id_recepcion)),
                            (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id=
                                (SELECT sc.id_cliente_contacto FROM sa_cita sc WHERE sc.id_cita=
                                    (SELECT sr.id_cita FROM sa_recepcion sr WHERE sr.id_recepcion= so.id_recepcion)))
                                    )
                        ) AS nombre_cliente,
                        (((so.subtotal - so.subtotal_descuento) * so.iva / 100) + (so.subtotal - so.subtotal_descuento)) AS total_orden,
                        sv.id_vale_salida,
                        sv.numero_vale,
                        sv.fecha_vale
                    FROM
                        sa_orden so, sa_vale_salida sv ".$sqlBusq;

        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
        
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	
                $htmlTh .= "<td class='noprint'></td>";
		$htmlTh .= ordenarCampo("xajax_listadoVales", "15%", $pageNum, "numero_vale", $campOrd, $tpOrd, $valBusq, $maxRows, "N&ordm; Vale");
		$htmlTh .= ordenarCampo("xajax_listadoVales", "15%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");
                $htmlTh .= ordenarCampo("xajax_listadoVales", "50%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Cliente");
                $htmlTh .= ordenarCampo("xajax_listadoVales", "20%", $pageNum, "total_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Total Vale");
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";

		$contFila ++;

		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";

                $htmlTb .= "<td align=\"center\" class='noprint'>
                                <button type=\"button\" id='buttonOrden' name='buttonOrden' onclick=\"asignarVale('".$row['id_vale_salida']."', '".$row['numero_vale']."', '".utf8_encode($row['nombre_cliente'])."', '".number_format($row['total_orden'],2,".",",")."', true, true)\" title=\"Seleccionar Orden\">
                                    <img src=\"../img/iconos/ico_aceptar.gif\"/>
                                </button>
                            </td>";
                $htmlTb .= "<td align=\"center\">".$row['numero_vale']."</td>";
                $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
                $htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_cliente'])."</td>";
                $htmlTb .= "<td align=\"right\">".number_format($row['total_orden'],2,".",",")."</td>";
                $htmlTb .= "</tr>";
	}

$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoVales(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoVales(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoVales(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoVales(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoVales(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"13\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}


	$objResponse->assign("tdListadoOrdenes","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
        $objResponse->script("if($('divFlotanteOrdenes').style.display == 'none'){
                                $('divFlotanteOrdenes').style.display = '';
                                centrarDiv($('divFlotanteOrdenes'));
                              }");

        return $objResponse;

    }

    function insertEnlaceGarantia($form){
        $objResponse = new xajaxResponse();

        mysql_query("START TRANSACTION;");

        $camposEnlace= "id_orden";
        $camposEnlace.= ", fecha_creacion";
        $camposEnlace.= ", estatus";
        $camposEnlace.= ", numero_referencia";
        $camposEnlace.= ", monto_total_vale";
        $camposEnlace.= ", id_usuario_creador";

        $valoresEnlace= $form['txtIdOrden'];
        $valoresEnlace.= ", current_date";
        $valoresEnlace.= ", 0";
        $valoresEnlace.= ", '".$form['txtNumeroReferencia']."'";
        $valoresEnlace.= ", ".str_replace(",", "", $form['txtMontoTotalVale'])."";
        $valoresEnlace.=", ".$_SESSION['idUsuarioSysGts']."" ;

        $sqlEnlace= "INSERT INTO sa_enlace_garantia (".$camposEnlace.") VALUES (".$valoresEnlace.");";

        $resultEnlace = mysql_query($sqlEnlace);
        if (!$resultEnlace) return $objResponse->alert(mysql_error().__LINE__.$sqlEnlace);

        $idEnlace= mysql_insert_id();

        $camposDetEnlace= "id_enlace_garantia";
        $camposDetEnlace.= ", id_vale_salida";

        for($i= 0; $i < count($form['idVale']); $i++){
            $valoresDetEnlace= $idEnlace;
            $valoresDetEnlace.= ", ".$form['idVale'][$i];

            $sqlDetEnlace= "INSERT INTO sa_det_enlace_garantia (".$camposDetEnlace.") VALUES (".$valoresDetEnlace.");";

            $resultDetEnlace = mysql_query($sqlDetEnlace);
            if (!$resultDetEnlace) return $objResponse->alert(mysql_error().__LINE__.$sqlDetEnlace);
        }
        mysql_query("COMMIT;");

        $objResponse->script("$('divFlotante').style.display='none';");
        $objResponse->script("$('divFlotanteOrdenes').style.display='none';");
        $objResponse->alert('Registro insertado con exito.');
        $objResponse->loadCommands(listadoEnlacesGarantias(0, "numero_orden", "DESC", ''));

        return $objResponse;
    }

    function buscarEnlaceGarantia($id, $acc){
        $objResponse = new xajaxResponse();
		
		if ($acc == 2)
			if (!xvalidaAcceso($objResponse,PAGE_PRIV,'editar')){
					return $objResponse;
				}
		
        $objResponse->script("$('tbodyVale').innerHTML= '';");

        $sqlEnlace= "SELECT
                            seg.id_enlace_garantia,
                            seg.id_orden,
                            seg.numero_referencia,
                            seg.monto_total_vale,
                            (SELECT IFNULL(
                                (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id=
                                    (SELECT sr.id_cliente_pago FROM sa_recepcion sr WHERE sr.id_recepcion= so.id_recepcion)),
                                (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id=
                                    (SELECT sc.id_cliente_contacto FROM sa_cita sc WHERE sc.id_cita=
                                        (SELECT sr.id_cita FROM sa_recepcion sr WHERE sr.id_recepcion= so.id_recepcion)))
                                        )
                            ) AS nombre_cliente,
                            (((so.subtotal - so.subtotal_descuento) * so.iva / 100) + (so.subtotal - so.subtotal_descuento)) AS total_orden
                        FROM
                            sa_enlace_garantia seg,
                            sa_orden so
                        WHERE
                            seg.id_orden= so.id_orden
                            AND seg.id_enlace_garantia= ".$id;

        $rsEnlace = mysql_query($sqlEnlace);
	if (!$rsEnlace) return $objResponse->alert(mysql_error());

        $rowEnlace= mysql_fetch_array($rsEnlace);

        $objResponse->script("$('idEnlace').value= '".$rowEnlace['id_enlace_garantia']."';");
        $objResponse->script("$('txtNumeroReferencia').value= '".$rowEnlace['numero_referencia']."';");
        $objResponse->script("$('txtIdOrden').value= '".$rowEnlace['id_orden']."';");
        $objResponse->script("$('txtNombreCliente').value= '".utf8_encode($rowEnlace['nombre_cliente']."';"));
        $objResponse->script("$('txtMontoTotalOrden').value= '".number_format($rowEnlace['total_orden'],2,".",",")."';");
        $objResponse->script("$('txtMontoTotalVale').value= '0';");

        $sqlDetEnlace= "SELECT
                            sdeg.*,
                            (SELECT IFNULL(
                                (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id=
                                    (SELECT sr.id_cliente_pago FROM sa_recepcion sr WHERE sr.id_recepcion= so.id_recepcion)),
                                (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id=
                                    (SELECT sc.id_cliente_contacto FROM sa_cita sc WHERE sc.id_cita=
                                        (SELECT sr.id_cita FROM sa_recepcion sr WHERE sr.id_recepcion= so.id_recepcion)))
                                        )
                            ) AS nombre_cliente,
                            (((so.subtotal - so.subtotal_descuento) * so.iva / 100) + (so.subtotal - so.subtotal_descuento)) AS total_orden,
                            sv.numero_vale
                        FROM
                            sa_orden so, sa_vale_salida sv, sa_det_enlace_garantia sdeg
                        WHERE
                            sdeg.id_enlace_garantia= ".$rowEnlace['id_enlace_garantia']."
                            AND sdeg.id_vale_salida= sv.id_vale_salida
                            AND so.id_orden= sv.id_orden";

        $rsDetEnlace = mysql_query($sqlDetEnlace);
	if (!$rsDetEnlace) return $objResponse->alert(mysql_error());

        $btn= "";
        if($acc == "2"){
            $btn= "true";
        }else{
            $btn= "false";
        }

        while($rowDetEnlace= mysql_fetch_array($rsDetEnlace)){
            $objResponse->script("asignarVale('".$rowDetEnlace['id_vale_salida']."',
                                '".$rowDetEnlace['numero_vale']."',
                                '".utf8_encode($rowDetEnlace['nombre_cliente'])."',
                                '".number_format($rowDetEnlace['total_orden'],2,".",",")."',
                                ".$btn.",
                                false);");
        }

        $objResponse->script("abrirVentana(".$acc.");");

        return $objResponse;
    }

    function updateEnlaceGarantia($form){
        $objResponse = new xajaxResponse();

        mysql_query("START TRANSACTION;");

        $camposEnlace= "id_orden= ".$form['txtIdOrden'];
        $camposEnlace.= ", numero_referencia= '".$form['txtNumeroReferencia']."'";
        $camposEnlace.= ", monto_total_vale= ".str_replace(",", "", $form['txtMontoTotalVale']);

        $condicionEnlace= "id_enlace_garantia= ".$form['idEnlace'];

        $sqlEnlace= "UPDATE sa_enlace_garantia SET ".$camposEnlace." WHERE ".$condicionEnlace.";";

        $resultEnlace = mysql_query($sqlEnlace);
        if (!$resultEnlace) return $objResponse->alert(mysql_error().__LINE__.$sqlEnlace);

        $consultaDetEnlace= "SELECT * FROM sa_det_enlace_garantia WHERE id_enlace_garantia= ".$form['idEnlace'];

        $resultConsultaDetEnlace = mysql_query($consultaDetEnlace);

        $idValesConsulta= array();
        while($rowConsultaDetEnlace= mysql_fetch_array($resultConsultaDetEnlace)){
            array_push($idValesConsulta, $rowConsultaDetEnlace['id_vale_salida']);
        }
        
        $camposDetEnlace= "id_enlace_garantia";
        $camposDetEnlace.= ", id_vale_salida";

        for($i= 0; $i < count($form['idVale']); $i++){
            if(in_array($form['idVale'][$i], $idValesConsulta)){
                $posicion=array_keys($idValesConsulta, $form['idVale'][$i]);
                array_splice($idValesConsulta,$posicion[0],1);
            }else{
                $valoresDetEnlace= $form['idEnlace'];
                $valoresDetEnlace.= ", ".$form['idVale'][$i];

                $sqlDetEnlace= "INSERT INTO sa_det_enlace_garantia (".$camposDetEnlace.") VALUES (".$valoresDetEnlace.");";

                $resultDetEnlace = mysql_query($sqlDetEnlace);
                if (!$resultDetEnlace) return $objResponse->alert(mysql_error().__LINE__.$sqlDetEnlace);
            }
        }

        for($i= 0; $i < count($idValesConsulta); $i++){
            $condicionDetEnlace= "id_vale_salida= ".$idValesConsulta[$i];

            $sqlDetEnlace= "DELETE FROM sa_det_enlace_garantia WHERE ".$condicionDetEnlace;

            $resultDetEnlace = mysql_query($sqlDetEnlace);
            if (!$resultDetEnlace) return $objResponse->alert(mysql_error().__LINE__.$sqlDetEnlace);
        }

        
        mysql_query("COMMIT;");

        $objResponse->script("$('divFlotante').style.display='none';");
        $objResponse->script("$('divFlotanteOrdenes').style.display='none';");
        $objResponse->alert('Registro actualizado con exito.');
        $objResponse->loadCommands(listadoEnlacesGarantias(0, "numero_orden", "DESC", ''));

        return $objResponse;
    }

    function aprobarEnlaceGarantia($id){
        $objResponse = new xajaxResponse();

        mysql_query("START TRANSACTION;");

        $camposEnlace= "estatus= 1";
        $camposEnlace.= ", id_usuario_aprobacion= ".$_SESSION['idUsuarioSysGts'];

        $condicionEnlace= "id_enlace_garantia= ".$id;

        $sqlEnlace= "UPDATE sa_enlace_garantia SET ".$camposEnlace." WHERE ".$condicionEnlace.";";

        $resultEnlace = mysql_query($sqlEnlace);
        if (!$resultEnlace) return $objResponse->alert(mysql_error().__LINE__.$sqlEnlace);
        
        mysql_query("COMMIT;");

        $objResponse->alert('Registro aprobado con exito.');
        $objResponse->loadCommands(listadoEnlacesGarantias(0, "numero_orden", "DESC", ''));

        return $objResponse;
    }
	
	function nuevo(){
        $objResponse = new xajaxResponse();
		
		if (!xvalidaAcceso($objResponse,PAGE_PRIV,'insertar')){
				return $objResponse;
			}
		else{
			$objResponse->script("abrirVentana(1);");
		}

        return $objResponse;
	}

    $xajax->register(XAJAX_FUNCTION,"buscar");
    $xajax->register(XAJAX_FUNCTION,"buscarOrden");
    $xajax->register(XAJAX_FUNCTION,"buscarVale");
    $xajax->register(XAJAX_FUNCTION,"listadoEnlacesGarantias");
    $xajax->register(XAJAX_FUNCTION,"listadoOrdenes");
    $xajax->register(XAJAX_FUNCTION,"listadoVales");
    $xajax->register(XAJAX_FUNCTION,"insertEnlaceGarantia");
    $xajax->register(XAJAX_FUNCTION,"buscarEnlaceGarantia");
    $xajax->register(XAJAX_FUNCTION,"updateEnlaceGarantia");
    $xajax->register(XAJAX_FUNCTION,"aprobarEnlaceGarantia");
    $xajax->register(XAJAX_FUNCTION,"nuevo");
?>