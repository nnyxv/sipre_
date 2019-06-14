<?php 
function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT DISTINCT id_empresa, nombre_empresa FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'],"int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" onchange=\"$('btnBuscar').click();\">";
			$html .= "<option value=\"\">[ Todos ]</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = "";
			if ($selId == $row['id_empresa'])
				$selected = "selected='selected'";
			$html .= "<option ".$selected." value=\"".$row['id_empresa']."\">".$row['id_empresa']."-".utf8_encode($row['nombre_empresa'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function buscarPresupuesto($valForm) {
	$objResponse = new xajaxResponse();
	
	$busq = sprintf("%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtPalabra'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta']);
		
	$objResponse->script("xajax_listadoPresupuestos(0,'numero_presupuesto','DESC','".$busq."');");
	
	return $objResponse;
}

function listadoPresupuestos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
		//$c->rollback();
		$objResponse->assign("tdListaPresupuestoVenta","innerHTML",'Acceso Denegado');
		return $objResponse;
	}
	
	$sqlBusq = "WHERE tipo_presupuesto = 1 AND sa_orden.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']."";
	/*if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sa_orden.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}*/
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf(" cj_cc_cliente.nombre LIKE %s
			OR cj_cc_cliente.apellido LIKE %s
			OR cj_cc_cliente.ci LIKE %s
			OR numero_orden LIKE %s
			OR numero_presupuesto LIKE %s
			OR placa LIKE %s
			OR chasis LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"));
	}
	
	if ($valCadBusq[2] != "" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("DATE(fecha_presupuesto) BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[3])),"date"));
	}
	
	$query = "SELECT 
					sa_presupuesto.id_empresa,
					date_format(sa_presupuesto.fecha_presupuesto, '%d-%m-%Y %h:%i %p') AS fecha_presupuesto,
					cj_cc_cliente.id,
					cj_cc_cliente.tipo,
					cj_cc_cliente.nombre,
					cj_cc_cliente.apellido,
					cj_cc_cliente.lci,
					cj_cc_cliente.ci,
					sa_presupuesto.id_presupuesto,
					sa_presupuesto.numero_presupuesto,
					#date_format(sa_presupuesto.fecha_presupuesto, '%d-%m-%Y %h:%i %p') AS fecha_presupuesto,
					date_format(sa_presupuesto.fecha_presupuesto, '%d-%m-%Y') AS fecha_presupuesto,
					numero_orden,
					sa_orden.id_orden,
					an_modelo.nom_modelo des_modelo,
					an_marca.nom_marca,
					tipo_orden.nombre_tipo_orden,
                                        total_presupuesto,
					#(((sa_presupuesto.subtotal * sa_presupuesto.iva)/100) + sa_presupuesto.subtotal) AS total,
					#((sa_presupuesto.subtotal + sa_presupuesto.subtotal_iva) - sa_presupuesto.subtotal_descuento) as total_base,
					sa_presupuesto.estado_presupuesto,
					sa_orden.id_estado_orden,
					uni_bas.nom_uni_bas,
					placa,
					chasis
				FROM 
					cj_cc_cliente
					INNER JOIN sa_presupuesto ON (cj_cc_cliente.id = sa_presupuesto.id_cliente)
					INNER JOIN sa_orden ON (sa_presupuesto.id_orden = sa_orden.id_orden)
					INNER JOIN sa_recepcion recepcion ON (sa_orden.id_recepcion = recepcion.id_recepcion)
					INNER JOIN sa_cita cita ON (recepcion.id_cita = cita.id_cita)
					INNER JOIN en_registro_placas reg_placas ON (cita.id_registro_placas = reg_placas.id_registro_placas)
					INNER JOIN an_uni_bas uni_bas ON (reg_placas.id_unidad_basica = uni_bas.id_uni_bas)
					INNER JOIN sa_tipo_orden tipo_orden ON (sa_orden.id_tipo_orden = tipo_orden.id_tipo_orden)
					INNER JOIN an_marca ON (uni_bas.mar_uni_bas = an_marca.id_marca)
					INNER JOIN an_modelo ON (uni_bas.mod_uni_bas = an_modelo.id_modelo)".$sqlBusq;
					
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
	
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "7%", $pageNum, "numero_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, ("N° Presupuesto"));
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "8%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("N° Orden"));
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "7%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "7%", $pageNum, "fecha_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "6%", $pageNum, "nom_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Unidad B&aacute;sica"));
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "6%", $pageNum, "nom_marca", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Marca"));
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "10%", $pageNum, "des_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Modelo"));
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "25%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "6%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, "Placa");
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "8%", $pageNum, "chasis", $campOrd, $tpOrd, $valBusq, $maxRows, "Chasis");
			$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "10%", $pageNum, "total_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");//antes total
			$htmlTh .= "<td class=\"noprint\" colspan=\"9\"></td>";
		$htmlTh .= "</tr>";
		
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar5" : "trResaltar4";
		
		$contFila++;
		
		$queryPresupuestoAprobado= "SELECT COUNT(*) AS cantidad FROM sa_presupuesto WHERE id_orden= ".$row['id_orden']." AND estado_presupuesto= 1";
		$rsPresupuestoAprobado = mysql_query($queryPresupuestoAprobado);
		if (!$rsPresupuestoAprobado) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowPresupuestoAprobado = mysql_fetch_assoc($rsPresupuestoAprobado);
		
		if ($rowPresupuestoAprobado['cantidad'] == 0 && ($row['id_estado_orden'] == 6 || $row['id_estado_orden'] == 3 || $row['id_estado_orden'] == 1)) {
			$imgAprobarPresup = sprintf("<img class=\"puntero\" onclick=\"window.open('sa_presupuesto_form.php?doc_type=1&id=%s&ide=%s&acc=4','_self');\" src=\"../img/iconos/aprobar_presup.png\" title=\"Aprobar presupuesto\"/>",
				$row['id_presupuesto'],
				$row['id_empresa']);
		} else {
			$imgAprobarPresup = "<img class=\"puntero\" src=\"../img/iconos/aprobar_presup_disabled.png\"/>";
		}
                
                $imgEditarPresupuesto = "<td></td>";
                if($rowPresupuestoAprobado['cantidad'] == 0){
                    $imgEditarPresupuesto = sprintf("<td class=\"noprint\"><img class=\"puntero\" onclick=\"window.open('sa_presupuesto_form.php?doc_type=1&id=%s&ide=%s&acc=3','_self');\" src=\"../img/iconos/pencil.png\" title=\"Editar Presupuesto\" />
                                </td>",
				$row['id_presupuesto'],
				$row['id_empresa']);
                }
		
		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"this.className = 'trSobre';\" onmouseout=\"this.className = '".$clase."';\" height=\"22\" style=\"border:1px dotted #999999\">";
			$htmlTb .= "<td align=\"right\" idpresupuestooculta=\"".$row['id_presupuesto']."\">".$row['numero_presupuesto']."</td>";
			$htmlTb .= "<td align=\"right\" idordenoculta =\"".$row['id_orden']."\">".$row['numero_orden']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['fecha_presupuesto'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nom_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nom_marca'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['des_modelo'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre'])." ".utf8_encode($row['apellido'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['chasis'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total_presupuesto'],2,".",",")."</td>";//antes total
			$htmlTb .= sprintf("<td class=\"noprint\">%s</td>", $imgAprobarPresup);
//			$htmlTb .= sprintf("<td class=\"noprint\"><img class=\"puntero\" onclick=\"window.open('sa_orden_form.php?doc_type=1&id=%s&ide=%s&acc=2','_self');\" src=\"../img/iconos/ico_view.png\" />
//                                </td>",
//				$row['id_presupuesto'],
//				$row['id_empresa']);
                        
			$htmlTb .= sprintf("<td class=\"noprint\"><img class=\"puntero\" onclick=\"window.open('sa_presupuesto_form.php?doc_type=1&id=%s&ide=%s&acc=2','_self');\" src=\"../img/iconos/ico_view.png\" title=\"Ver Presupuesto\" />
                                </td>",
				$row['id_presupuesto'],
				$row['id_empresa']);
                        
			$htmlTb .= $imgEditarPresupuesto;                        
                        
			$htmlTb .= sprintf("<td class=\"noprint\"><img class=\"puntero\" onclick=\"verVentana('sa_imprimir_presupuesto_pdf.php?valBusq=%s|1', 950, 600);\" src=\"../img/iconos/print.png\" /></td>",
				$row['id_presupuesto']);
				
		$html .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"18\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPresupuestos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPresupuestos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoPresupuestos(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPresupuestos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPresupuestos(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$htmlTblFin .= "<tr id=\"trLeyendaControlTaller\">";
		$htmlTblFin .= "<td align=\"center\" colspan=\"14\">";
			$htmlTblFin .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjInfo noprint\" width=\"100%\">";
				$htmlTblFin .= "<tr>";
					$htmlTblFin .= "<td width=\"25\"><img src=\"../img/iconos/ico_info2.gif\" width=\"25\"/></td>";
					$htmlTblFin .= "<td align=\"center\">";
						$htmlTblFin .= "<table border=\"0\">";
							$htmlTblFin .= "<tr>";
								$htmlTblFin .= "<td id=\"tdImgAccionVerOrden\"><img src=\"../img/iconos/ico_view.png\" /></td>";
								$htmlTblFin .= "<td id=\"tdDescripAccionVerOrden\">Ver Presupuesto</td>";
								$htmlTblFin .= "<td>&nbsp;</td>";
								$htmlTblFin .= "<td id=\"tdImgAccionAprobacionOrden\"><img src=\"../img/iconos/aprobar_presup.png\" /></td>";
								$htmlTblFin .= "<td id=\"tdDescripAccionAprobacionOrden\" align=\"left\">Aprobaci&oacute;n</td>";
							 $htmlTblFin .= "</tr>";
						 $htmlTblFin .= "</table>";
					$htmlTblFin .= "</td>";
				$htmlTblFin .= "</tr>";
			$htmlTblFin .= "</table>";
		$htmlTblFin .= "</td>";
	$htmlTblFin .= "</tr>";
	$htmlTblFin .= "</table>";
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"14\" class=\"divMsjError\">No se encontraron registros.</td>";
		
		
	$objResponse->assign("tdListaPresupuestoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	//$objResponse->script("limpiar_select();");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"listadoPresupuestos");

?>
