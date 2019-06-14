<?php
function cargaLstEmpresas($selId = "") {
	$objResponse = new xajaxResponse();
			
	$query = sprintf("SELECT DISTINCT id_empresa, nombre_empresa FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'],"int"));
			
	$rs = mysql_query($query) or die(mysql_error());
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"\">Todos...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_empresa'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_empresa']."\">".utf8_encode($row['nombre_empresa'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}
function cargaLstTipoOrden($selId = "") {
	$objResponse = new xajaxResponse();
			
	$query = "SELECT *
		FROM
		sa_tipo_orden WHERE id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."' ORDER BY  sa_tipo_orden.nombre_tipo_orden";
			
	$rs = mysql_query($query) or die(mysql_error());
	$html = "<select id=\"lstTipoOrden\" name=\"lstTipoOrden\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"\">Todos...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_tipo_orden'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_tipo_orden']."\">".utf8_encode($row['nombre_tipo_orden'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstTipoOrden","innerHTML",$html);
	
	return $objResponse;
}

function buscarOrden($valForm) {
	$objResponse = new xajaxResponse();
	
	$busq = sprintf("%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtPalabra'],
		$valForm['lstTipoOrden']);
		
	$objResponse->script("xajax_listadoOrdenes(0,'','','".$busq."');");
	return $objResponse;
}

function listadoOrdenes($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 17, $totalRows = NULL) {

		$objResponse = new xajaxResponse();
		
		$valCadBusq = explode("|", $valBusq);
		$startRow = $pageNum * $maxRows;
		
		$valCadBusq[0] = $_SESSION['idEmpresaUsuarioSysGts'];
		//$sqlBusq = sprintf("vw_sa_historico_ordenes.id_empresa = %s",
				//valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));
			
			
		if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_sa_historico_ordenes.id_empresa = %s %s ",
				valTpDato($valCadBusq[0], "int"),
				$condOrigenFact);
		}
		
		if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
			$sqlBusq .= $cond.sprintf(" vw_sa_historico_ordenes.numero_orden LIKE %s
										OR vw_sa_historico_ordenes.numeracion_recepcion LIKE %s)",
				valTpDato("%".$valCadBusq[1]."%","text"),
				valTpDato("%".$valCadBusq[1]."%","text"));
		}
		
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
			$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
			$sqlBusq .= $cond.sprintf("vw_sa_historico_ordenes.id_tipo_orden = %s ",
				valTpDato($valCadBusq[2], "int"));
		}
		
	$query = "
		SELECT 
		vw_sa_historico_ordenes.id_orden,
		vw_sa_historico_ordenes.fecha_orden,
		vw_sa_historico_ordenes.numero_orden,
		vw_sa_historico_ordenes.numeracion_recepcion,
		vw_sa_historico_ordenes.id_recepcion,
		vw_sa_historico_ordenes.tiempo_orden,
		vw_sa_historico_ordenes.nombre_tipo_orden,
		vw_sa_historico_ordenes.numero_documento,
		vw_sa_historico_ordenes.monto_total_documento,
		vw_sa_historico_ordenes.tipo_documento,
		vw_sa_historico_ordenes.id_empresa,
		vw_sa_historico_ordenes.id_estado_orden,
		vw_sa_historico_ordenes.id_tipo_orden,
		vw_sa_historico_ordenes.id_cliente_pago_orden,
		vw_sa_historico_ordenes.chasis,
		vw_sa_historico_ordenes.orden_retrabajo,
		sa_retrabajo_orden.id_orden_retrabajo AS orden_aplica_retrabajo
		FROM
		vw_sa_historico_ordenes
		LEFT OUTER JOIN sa_retrabajo_orden ON (vw_sa_historico_ordenes.id_orden = sa_retrabajo_orden.id_orden)
		";
	
	$query .= $sqlBusq;
	
	if($campOrd==""){
		$campOrd = "vw_sa_historico_ordenes.numero_orden DESC";	
	}
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
        
	if(!$rsLimit) { return $objResponse->alert(mysql_error()."\n\n Line: ".__LINE__."\n ".$queryLimit); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\n Line: ".__LINE__."\n ".$rs); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
				$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "10%", $pageNum, "vw_sa_historico_ordenes.numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro Orden"));
				$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "15%", $pageNum, "vw_sa_historico_ordenes.fecha_orden", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Fecha Orden"));
				$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "10%", $pageNum, "vw_sa_historico_ordenes.numeracion_recepcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Nro Recepcion"));
				$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "20%", $pageNum, "vw_sa_historico_ordenes.tiempo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tiempo Orden"));
				$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "10%", $pageNum, "vw_sa_historico_ordenes.nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");	
				$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "10%", $pageNum, "vw_sa_historico_ordenes.tipo_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Documento");
				$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "10%", $pageNum, "vw_sa_historico_ordenes.numero_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro Documento");
				$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "10%", $pageNum, "vw_sa_historico_ordenes.monto_total_documento", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
				$htmlTh .= ordenarCampo("xajax_listadoOrdenes", "10%", $pageNum, "orden_aplica_retrabajo", $campOrd, $tpOrd, $valBusq, $maxRows, "Orden retrabajo");
				$htmlTh .= "<td class=\"noprint\" colspan=\"5\"></td>";
				$htmlTh .= "</tr>";
	
	$display_vw = "";
	$display_generarPresup = "style='display:none'";
	$contFila = 0;

	while ($row = mysql_fetch_assoc($rsLimit)) {
		
		$contFila++;
		
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";
		
		if($row['orden_retrabajo'] == 0)
			$iconoRetrabajo = sprintf("<img class=\"puntero\" src=\"../img/iconos/retrabajo.png\" onclick=\"window.open('sa_orden_form.php?doc_type=2&id=%s&ide=%s&acc=3&cons=1&ret=5&acc_ret=1','_self');\"/>", $row['id_orden'], $row['id_empresa']);
		else
			$iconoRetrabajo = "<img class=\"puntero\" src=\"../img/iconos/retrabajo_disabled.png\"/>";
		
		$htmlTb .= "<tr class=\"".$clase."\" style=\"border:1px dotted #999999\">";
			$htmlTb .= "<td align=\"center\" idordenoculta=\"".$row['id_orden']."\" >".$row['numero_orden']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['fecha_orden']."</td>";
			$htmlTb .= "<td align=\"center\" idrecepcionoculta=\"".$row['id_recepcion']."\">".$row['numeracion_recepcion']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['tiempo_orden']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['nombre_tipo_orden']."</td>";
			$htmlTb .= "<td>".$row['tipo_documento']."</td>";
			$htmlTb .= "<td>".$row['numero_documento']."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['monto_total_documento'],2,".",",")."</td>";
			$htmlTb .= "<td>".$row['orden_aplica_retrabajo']."</td>";
			$htmlTb .= sprintf("<td align=\"center\" class=\"noprint\" ><img class=\"puntero\" src=\"../img/iconos/ico_view.png\" onclick=\"verVentana('sa_orden_form.php?doc_type=2&id=%s&ide=%s&acc=2&cons=0&ret=5&acc_ret=2&sinmenu=1', 1100, 600);\"/></td>", $row['id_orden'], $row['id_empresa']);		
			$htmlTb .= sprintf("<td align=\"center\" class=\"noprint\">%s</td>", $iconoRetrabajo);
		$html .= "</tr>";
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
	
	
	
	//leyenda despues del fin de la tabla
	$htmlTblFin .= "<table class=\"noprint\"><tr id=\"trLeyendaControlTaller\" >";
	$htmlTblFin .= "<td align=\"center\" colspan=\"16\" class=\"divMsjInfo\">";
	$htmlTblFin .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
	  $htmlTblFin .= "<tr>";
		$htmlTblFin .= "<td width=\"25\"><img src=\"../img/iconos/ico_info2.gif\" width=\"25\"/></td>";
		
		
		  $htmlTblFin .= "<td width=\"1375\" align=\"center\"><table>";
	  $htmlTblFin .= "<tr>";
		 $$htmlTblFin .= "<td id=\"tdImgAccionVerOrden\"><img src=\"../img/iconos/ico_view.png\" /></td>";
         $htmlTblFin .= "<td id=\"tdDescripAccionVerOrden\">Ver Orden</td>";
         $htmlTblFin .= "<td>&nbsp;</td>";
         $htmlTblFin .= "<td id=\"tdImgAccionVerOrdenDesactivado\"><img src=\"../img/iconos/retrabajo.png\" /></td>";
        $htmlTblFin .= "<td id=\"tdDescripAccionVerOrdenDesactivado\">Aplicar retrabajo</td>";
		 $htmlTblFin .= "<td>&nbsp;</td>";
        $htmlTblFin .= "<td id=\"tdImgAccionVerOrdenDesactivado\"><img src=\"../img/iconos/retrabajo_disabled.png\" /></td>";
        $htmlTblFin .= "<td id=\"tdDescripAccionVerOrdenDesactivado\">Aplicar retrabajo (Desactivado)</td>";
		 
		 
  		 $htmlTblFin .= "</table></td>";
		 
	$htmlTblFin .= "</table>";
	$htmlTblFin .= "</td>";
	$htmlTblFin .= "</tr></table>";
	
	
		/*
	$htmlTf .= "</tr>";
	$htmlTf .= "<tr id=\"trLeyendaControlTaller\" style=\"display:none\" >";
	$htmlTf .= "<td align=\"center\" colspan=\"16\" class=\"divMsjInfo\">";
	$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
	  $htmlTf .= "<tr>";
		$htmlTf .= "<td width=\"25\"><img src=\"../img/iconos/ico_info2.gif\" width=\"25\"/></td>";
		
		
		  $htmlTf .= "<td width=\"1375\" align=\"center\"><table>";
	  $htmlTf .= "<tr>";
		 $htmlTf .= "<td id=\"tdImgAccionVerOrden\"><img src=\"../img/iconos/ico_view.png\" /></td>";
         $htmlTf .= "<td id=\"tdDescripAccionVerOrden\">Ver Orden</td>";
         $htmlTf .= "<td>&nbsp;</td>";
         $htmlTf .= "<td id=\"tdImgAccionVerOrdenDesactivado\"><img src=\"../img/iconos/retrabajo.png\" /></td>";
         $htmlTf .= "<td id=\"tdDescripAccionVerOrdenDesactivado\">Aplicar retrabajo</td>";
		  $htmlTf .= "<td>&nbsp;</td>";
         $htmlTf .= "<td id=\"tdImgAccionVerOrdenDesactivado\"><img src=\"../img/iconos/retrabajo_disabled.png\" /></td>";
         $htmlTf .= "<td id=\"tdDescripAccionVerOrdenDesactivado\">Aplicar retrabajo (Desactivado)</td>";
		 
		 
  		 $htmlTf .= "</table></td>";
		 
	$htmlTf .= "</table>";
	$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	$htmlTableFin .= "</table>";*/
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"12\" class=\"divMsjError\">No se encontraron registros.</td>";
	
	
	$objResponse->assign("tdListaPresupuestoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

	if($valCadBusq[4]==2 ||  $valCadBusq[4]==0 || $valCadBusq[4]==8 || $valCadBusq[4]==9 || $valCadBusq[4]==4)//CONTROL TALLER
		$objResponse -> script("$('trLeyendaControlTaller').style.display = '';");
		
	if($valCadBusq[4]==0)
		$objResponse -> script("
			$('tdImgAccionAprobacionFacturacion').style.display = 'none';
			$('tdDescripAprobacionFacturacion').style.display = 'none';
			$('tdImgAccionDevolucion').style.display = 'none';
			$('tdDescripAccionDevolucion').style.display = 'none';
			$('tdImgAccionGenerarFactura').style.display = 'none';
			$('tdDescripAccionGenerarFactura').style.display = 'none';
			
			");
	
	if($valCadBusq[4]==2)
		$objResponse -> script("
			$('tdImgAccionVerOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionVerOrdenDesactivado').style.display = 'none';
			$('tdImgAccionAprobacionOrden').style.display = 'none';
			$('tdDescripAccionAprobacionOrden').style.display = 'none';
			$('tdImgAccionAprobacionOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionAprobacionOrdenDesactivado').style.display = 'none';
			$('tdImgAccionEdicionOrden').style.display = 'none';
			$('tdDescripAccionEdicionOrden').style.display = 'none';
			$('tdImgAccionEdicionOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionEdicionOrdenDesactivado').style.display = 'none';
			$('tdImgAccionGenerarPresupuesto').style.display = 'none';
			$('tdDescripAccionGenerarPresupuesto').style.display = 'none';
			$('tdImgAccionGenerarPresupuestoDesactivado').style.display = 'none';
			$('tdDescripAccionGenerarPresupuestoDesactivado').style.display = 'none';
			$('tdImgAccionDevolucion').style.display = 'none';
			$('tdDescripAccionDevolucion').style.display = 'none';
			$('tdImgAccionGenerarFactura').style.display = 'none';
			$('tdDescripAccionGenerarFactura').style.display = 'none';
			
			
			
			");
			
		if($valCadBusq[4]==8)
		$objResponse -> script("
			$('tdImgAccionVerOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionVerOrdenDesactivado').style.display = 'none';
			$('tdImgAccionAprobacionOrden').style.display = 'none';
			$('tdDescripAccionAprobacionOrden').style.display = 'none';
			$('tdImgAccionAprobacionOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionAprobacionOrdenDesactivado').style.display = 'none';
			$('tdImgAccionEdicionOrden').style.display = 'none';
			$('tdDescripAccionEdicionOrden').style.display = 'none';
			$('tdImgAccionEdicionOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionEdicionOrdenDesactivado').style.display = 'none';
			$('tdImgAccionGenerarPresupuesto').style.display = 'none';
			$('tdDescripAccionGenerarPresupuesto').style.display = 'none';
			$('tdImgAccionGenerarPresupuestoDesactivado').style.display = 'none';
			$('tdDescripAccionGenerarPresupuestoDesactivado').style.display = 'none';
			$('tdImgAccionAprobacionFacturacion').style.display = 'none';
			$('tdDescripAprobacionFacturacion').style.display = 'none';
			$('tdImgAccionVerOrden').style.display = 'none';
			$('tdDescripAccionVerOrden').style.display = 'none';
			$('tdImgAccionGenerarFactura').style.display = 'none';
			$('tdDescripAccionGenerarFactura').style.display = 'none';");
			
		if($valCadBusq[4]==9)
		$objResponse -> script("
			$('tdImgAccionVerOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionVerOrdenDesactivado').style.display = 'none';
			$('tdImgAccionAprobacionOrden').style.display = 'none';
			$('tdDescripAccionAprobacionOrden').style.display = 'none';
			$('tdImgAccionAprobacionOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionAprobacionOrdenDesactivado').style.display = 'none';
			$('tdImgAccionEdicionOrden').style.display = 'none';
			$('tdDescripAccionEdicionOrden').style.display = 'none';
			$('tdImgAccionEdicionOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionEdicionOrdenDesactivado').style.display = 'none';
			$('tdImgAccionGenerarPresupuesto').style.display = 'none';
			$('tdDescripAccionGenerarPresupuesto').style.display = 'none';
			$('tdImgAccionGenerarPresupuestoDesactivado').style.display = 'none';
			$('tdDescripAccionGenerarPresupuestoDesactivado').style.display = 'none';
			$('tdImgAccionAprobacionFacturacion').style.display = 'none';
			$('tdDescripAprobacionFacturacion').style.display = 'none';
			
			$('tdImgAccionGenerarFactura').style.display = 'none';
			$('tdDescripAccionGenerarFactura').style.display = 'none';");
			
			if($valCadBusq[4]==4)
		$objResponse -> script("
			$('tdImgAccionVerOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionVerOrdenDesactivado').style.display = 'none';
			$('tdImgAccionAprobacionOrden').style.display = 'none';
			$('tdDescripAccionAprobacionOrden').style.display = 'none';
			$('tdImgAccionAprobacionOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionAprobacionOrdenDesactivado').style.display = 'none';
			$('tdImgAccionEdicionOrden').style.display = 'none';
			$('tdDescripAccionEdicionOrden').style.display = 'none';
			$('tdImgAccionEdicionOrdenDesactivado').style.display = 'none';
			$('tdDescripAccionEdicionOrdenDesactivado').style.display = 'none';
			$('tdImgAccionGenerarPresupuesto').style.display = 'none';
			$('tdDescripAccionGenerarPresupuesto').style.display = 'none';
			$('tdImgAccionGenerarPresupuestoDesactivado').style.display = 'none';
			$('tdDescripAccionGenerarPresupuestoDesactivado').style.display = 'none';
			$('tdImgAccionAprobacionFacturacion').style.display = 'none';
			$('tdDescripAprobacionFacturacion').style.display = 'none';
			$('tdImgAccionDevolucion').style.display = 'none';
			$('tdDescripAccionDevolucion').style.display = 'none';
			");
	
	
	return $objResponse;
}

function detenerOrden($idOrden, $pageNum, $campOrd, $tpOrd, $valBusq, $maxRows)
{
		$objResponse = new xajaxResponse();
		
		$objResponse -> script("
			$('tblDcto').style.display = 'none';
			$('tblRetrabajoOrden').style.display = 'none';
			$('tblDetencionOrden').style.display = '';
			$('tblReanudarOrden').style.display = 'none';
			$('tblClaveAprobacionOrden').style.display = 'none';
			document.forms['frmDetenerOrden'].reset();");
			
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Detener Orden de Servicio");
		$objResponse->assign("txtNroOrden","value",$idOrden);
		$objResponse->assign("hddValBusq","value",$valBusq);
		$objResponse->assign("hddPageNum","value",$pageNum);
		$objResponse->assign("hddCampOrd","value",$campOrd);
		$objResponse->assign("hddTpOrd","value",$tpOrd);
		$objResponse->assign("hddMaxRows","value",$maxRows);

		$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}");
		
		return $objResponse;
}
function retrabajoOrden($idOrden, $pageNum, $campOrd, $tpOrd, $valBusq, $maxRows)
{
		$objResponse = new xajaxResponse();
		
		$objResponse -> script("
			$('tblDcto').style.display = 'none';
			
			$('tblRetrabajoOrden').style.display = '';
			$('tblDetencionOrden').style.display = 'none';
			$('tblReanudarOrden').style.display = 'none';
			$('tblClaveAprobacionOrden').style.display = 'none';
			document.forms['frmDetenerOrden'].reset();");
			
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Retrabajo Orden de Servicio");
		$objResponse->assign("txtNroOrdenRet","value",$idOrden);
		$objResponse->assign("hddValBusqRet","value",$valBusq);
		$objResponse->assign("hddPageNumRet","value",$pageNum);
		$objResponse->assign("hddCampOrdRet","value",$campOrd);
		$objResponse->assign("hddTpOrdRet","value",$tpOrd);
		$objResponse->assign("hddMaxRowsRet","value",$maxRows);

		$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}");
		
		return $objResponse;
}
function cargaLstMotivoDetencionOrden($selId = "")
{
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT *
		FROM
		sa_estado_orden WHERE sa_estado_orden.tipo_estado = 4 AND sa_estado_orden.activo = 1 ORDER BY sa_estado_orden.`nombre_estado`");//sa_estado_orden.`tipo_estado`=4
	$rs = mysql_query($query) or die(mysql_error());
	$html = "<select id=\"lstMotivoDetencion\" name=\"lstMotivoDetencion\" >";
		$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_estado_orden'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_estado_orden']."\" ".$seleccion.">".utf8_encode($row['nombre_estado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdListMotivoDetencionOrden","innerHTML",$html);
	
	return $objResponse;

}

function cargaLstReanudarOrden($selId = "")
{
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT *
		FROM
		sa_estado_orden WHERE sa_estado_orden.id_estado_orden = 6"); //<> 4 AND id_estado_orden <> 13 AND id_estado_orden <> 18 ORDER BY sa_estado_orden.`nombre_estado`
		
	$rs = mysql_query($query) or die(mysql_error());
	$html = "<select id=\"lstReanudarOrden\" name=\"lstReanudarOrden\" >";
		$html .= "<option value=\"-1\">Seleccione...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$seleccion = "";
		if ($selId == $row['id_estado_orden'])
			$seleccion = "selected='selected'";
		
		$html .= "<option value=\"".$row['id_estado_orden']."\" ".$seleccion.">".utf8_encode($row['nombre_estado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdListReanudarOrden","innerHTML",$html);
	
	return $objResponse;

}
function guardarDetencionOrden($valForm)
{
	$objResponse = new xajaxResponse();

	$insertSQL = sprintf("INSERT INTO sa_movimiento_orden (id_orden, id_estado_orden, id_empleado_movimiento, tiempo_movimiento, observacion) VALUE (%s, %s, %s, NOW(), %s);",	
		valTpDato($valForm['txtNroOrden'], "int"),
		valTpDato($valForm['lstMotivoDetencion'], "int"),
		valTpDato($_SESSION['idUsuarioSysGts'],"int"),
		valTpDato($valForm['txtAreaObservacionDetencion'], "text"));
	
	$Result = mysql_query($insertSQL) or die(mysql_error());
	
	
	$query = sprintf("UPDATE sa_orden set id_estado_orden = %s WHERE id_orden = %s",
		valTpDato($valForm['lstMotivoDetencion'], "int"),
		valTpDato($valForm['txtNroOrden'], "int"));
	
	mysql_query("SET NAMES 'utf8';");	
	$Result1 = mysql_query($query) or die(mysql_error());
	mysql_query("SET NAMES 'latin1';");
	
	$objResponse -> alert("La orden se ha detenido.");
	
	$objResponse->script("
			$('divFlotante').style.display='none';
			xajax_listadoOrdenes(0,'','','".$valForm['hddValBusq']."');
		");
	
	
	return $objResponse;

}
function guardarReanudoOrden($valForm)
{
	$objResponse = new xajaxResponse();
	
	$insertSQL = sprintf("INSERT INTO sa_movimiento_orden (id_orden, id_estado_orden, id_empleado_movimiento, tiempo_movimiento, observacion) VALUE (%s, %s, %s, NOW(), %s);",	
		valTpDato($valForm['txtNroOrdenRe'], "int"),
		valTpDato($valForm['lstReanudarOrden'], "int"),
		valTpDato($_SESSION['idUsuarioSysGts'],"int"),
		valTpDato($valForm['txtAreaObservacionReanudo'], "text"));
								
	$Result = mysql_query($insertSQL) or die(mysql_error());
	
	$query = sprintf("UPDATE sa_orden set id_estado_orden = %s WHERE id_orden = %s",
		valTpDato($valForm['lstReanudarOrden'], "int"),
		valTpDato($valForm['txtNroOrdenRe'], "int"));
	
	mysql_query("SET NAMES 'utf8';");	
	$Result1 = mysql_query($query) or die(mysql_error());
	mysql_query("SET NAMES 'latin1';");
	
	$objResponse -> alert("La orden se ha Reanudado.");
	
	$objResponse->script("
			$('divFlotante').style.display='none';
			xajax_listadoOrdenes(0,'','','".$valForm['hddValBusqRe']."');
		");

	return $objResponse;

}

function guardarRetrabajoOrden($valForm)
{
	$objResponse = new xajaxResponse();
	
	/*$insertSQL = sprintf("INSERT INTO sa_movimiento_orden (id_orden, id_estado_orden, id_empleado_movimiento, tiempo_movimiento, observacion) VALUE (%s, %s, %s, NOW(), %s);",	
		valTpDato($valForm['txtNroOrdenRet'], "int"),
		valTpDato($_SESSION['idUsuarioSysGts'],"int"),
		valTpDato($valForm['txtMotivoRetrabajo'], "text"));
								
	$Result = mysql_query($insertSQL) or die(mysql_error());*/
	
	$query = sprintf("UPDATE sa_orden set retrabajo = 1 WHERE id_orden = %s",
		valTpDato($valForm['txtNroOrdenRe'], "int"));
		
	mysql_query("SET NAMES 'utf8';");	
	$Result1 = mysql_query($query) or die(mysql_error());
	mysql_query("SET NAMES 'latin1';");
	
	$objResponse -> alert("La Orden esta en Retrabajo.");
	
	$objResponse->script("
			$('divFlotante').style.display='none';
			xajax_listadoOrdenes(0,'','','".$valForm['hddValBusqRet']."');
		");

	return $objResponse;

}


function reanudarOrden($idOrden, $pageNum, $campOrd, $tpOrd, $valBusq, $maxRows)
{
		$objResponse = new xajaxResponse();
		
		$objResponse -> script("
			$('tblDcto').style.display = 'none';
			$('tblRetrabajoOrden').style.display = 'none';
			$('tblDetencionOrden').style.display = 'none';
			$('tblReanudarOrden').style.display = '';
			$('tblClaveAprobacionOrden').style.display = 'none';

			document.forms['frmReanudarOrden'].reset();");
			
		$objResponse->assign("tdFlotanteTitulo","innerHTML","Reanudar Orden de Servicio");
		$objResponse->assign("txtNroOrdenRe","value",$idOrden);
		$objResponse->assign("hddValBusqRe","value",$valBusq);
		$objResponse->assign("hddPageNumRe","value",$pageNum);
		$objResponse->assign("hddCampOrdRe","value",$campOrd);
		$objResponse->assign("hddTpOrdRe","value",$tpOrd);
		$objResponse->assign("hddMaxRowsRe","value",$maxRows);

		$objResponse->script("
		if ($('divFlotante').style.display == 'none') {
			$('divFlotante').style.display='';
			centrarDiv($('divFlotante'));
		}
		");
		
		return $objResponse;
}



$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresas");
$xajax->register(XAJAX_FUNCTION,"buscarOrden");
$xajax->register(XAJAX_FUNCTION,"listadoOrdenes");
$xajax->register(XAJAX_FUNCTION,"retrabajoOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstTipoOrden");
$xajax->register(XAJAX_FUNCTION,"detenerOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstMotivoDetencionOrden");
$xajax->register(XAJAX_FUNCTION,"guardarDetencionOrden");
$xajax->register(XAJAX_FUNCTION,"cargaLstReanudarOrden");
$xajax->register(XAJAX_FUNCTION,"guardarReanudoOrden");
$xajax->register(XAJAX_FUNCTION,"reanudarOrden");
$xajax->register(XAJAX_FUNCTION,"aprobarOrdenForm");
$xajax->register(XAJAX_FUNCTION,"aprobarOrden");
?>