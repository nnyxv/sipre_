<?php 
function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();
	
	if($selId == ""){
		$selId = $_SESSION['idEmpresaUsuarioSysGts'];	
	}
			
	$query = sprintf("SELECT id_empresa_reg, CONCAT_WS(' ', nombre_empresa, nombre_empresa_suc) as nombre_empresa FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'],"int"));
		
	$rs = mysql_query($query) or die(mysql_error());
	$html = "<select id=\"lstEmpresa\" name=\"lstEmpresa\" onchange=\"$('btnBuscar').click();\">";
		//$html .= "<option value=\"\">Todos...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_empresa_reg'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".$row['id_empresa_reg']."-".htmlentities($row['nombre_empresa'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function buscarPresupuesto($valForm) {
	$objResponse = new xajaxResponse();
	
	$busq = sprintf("%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtPalabra']);
		
	$objResponse->script("xajax_listadoPresupuestos(0,'','','".$busq."');");
		
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
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_sa_cotizacion.id_empresa = %s AND vw_sa_cotizacion.tipo_presupuesto = 0",
			valTpDato($valCadBusq[0], "int"));
	}
	else
		$condTipo = "WHERE vw_sa_cotizacion.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']." AND vw_sa_cotizacion.tipo_presupuesto = 0";
		//agregue vw_sa_cotizacion.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']." AND para que no buscara TODAS las empresas restringir
			
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf(" vw_sa_cotizacion.nombre LIKE %s OR vw_sa_cotizacion.apellido LIKE %s OR ci LIKE %s OR numero_presupuesto LIKE %s)",
		    valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"),
			valTpDato("%".$valCadBusq[1]."%","text"));
	}
	
	$query = ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") ? "
		SELECT * FROM vw_sa_cotizacion ".$condTipo : "
		SELECT * FROM vw_sa_cotizacion ".$condTipo;
		$query .= $sqlBusq;
				
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf(" %s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit) or die(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
				$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "5%", $pageNum, "numero_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, htmlentities("Nro Cotizacion"));
				$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "10%", $pageNum, "nombre_tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");	
				$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "10%", $pageNum, "fecha_presupuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
				$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "10%", $pageNum, "des_uni_bas", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Unidad Basica"));
				$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "10%", $pageNum, "nom_marca", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Marca"));
				$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "10%", $pageNum, "des_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Modelo"));
				$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "30%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
				$htmlTh .= ordenarCampo("xajax_listadoPresupuestos", "10%", $pageNum, "total", $campOrd, $tpOrd, $valBusq, $maxRows, "Total");
				$htmlTh .= "<td class=\"noprint\" colspan=\"2\"></td>";
				$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";
		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td align=\"center\" idcotizacionoculta=\"".$row['id_presupuesto']."\">".$row['numero_presupuesto']."</td>";
			$htmlTb .= "<td align=\"center\">". utf8_encode($row['nombre_tipo_orden'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['fecha_presupuesto']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['des_uni_bas'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['nom_marca']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['des_modelo']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])." ".utf8_encode($row['apellido'])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($row['total'],2,".",",")."</td>";
			$htmlTb .= sprintf("<td align=\"center\" class=\"noprint\" ><img class=\"puntero\" onclick=\"window.open('sa_cotizacion_form.php?doc_type=1&id=%s&ide=%s&acc=2','_self');\" src=\"../img/iconos/ico_view.png\" /></td>",
				$row['id_presupuesto'],
				$row['id_empresa']);
				
			$htmlTb .= sprintf("<td align=\"center\" class=\"noprint\"><img class=\"puntero\" onclick=\"verVentana('sa_imprimir_presupuesto_pdf.php?valBusq=%s|1', 950, 600);\" src=\"../img/iconos/print.png\" /></td>",
				$row['id_presupuesto']);	
								
		$html .= "</tr>";
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
	
	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"10\" class=\"divMsjError\">No se encontraron registros.</td>";
		
		
	$objResponse->assign("tdListaPresupuestoVenta","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	return $objResponse;
}


$xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarPresupuesto");
$xajax->register(XAJAX_FUNCTION,"listadoPresupuestos");





?>
