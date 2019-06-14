<?php

function buscarFiltroOrden($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoFiltroOrden(0,'','','%s');",
		$valForm['txtCriterio']));
	
	return $objResponse;
}

function listadoFiltroOrden($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
        //$valCadBusq[0] criterio
       
        if($valCadBusq[0] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("sa_filtro_orden.descripcion LIKE %s",
                        valTpDato("%".$valCadBusq[0]."%","text"));
        }
	
	$query = sprintf("SELECT sa_filtro_orden.id_filtro_orden, 
                                 sa_filtro_orden.descripcion,
                                 sa_filtro_orden.bloqueo_items,
                                 sa_filtro_orden.tot_accesorio
                                FROM sa_filtro_orden
                                %s", $sqlBusq); 
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
        
        if (!$rsLimit) { return $objResponse->alert($queryLimit."\n".mysql_error()."\n\nLine: ".__LINE__); }
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
                if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__); }
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
        $title = "title='".titleItems()."'";
        
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";            
            $htmlTh .= ordenarCampo("xajax_listadoFiltroOrden", "4%", $pageNum, "id_filtro_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
            $htmlTh .= ordenarCampo("xajax_listadoFiltroOrden", "35%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripci&oacute;n"));
            $htmlTh .= ordenarCampo("xajax_listadoFiltroOrden", "25%", $pageNum, "bloqueo_items", $campOrd, $tpOrd, $valBusq, $maxRows, ("Bloqueo Items"));
            $htmlTh .= ordenarCampo("xajax_listadoFiltroOrden", "25%", $pageNum, "tot_accesorio", $campOrd, $tpOrd, $valBusq, $maxRows, ("TOT con accesorios"));
            $htmlTh .= "<td width=\"1%\"></td>";	
            $htmlTh .= "<td width=\"1%\"></td>";	
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $contFila++;
            

            $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
            
            $totAccesorio = ($row['tot_accesorio']) == "1" ? "SI" : "NO";
            
            $htmlTb .= "<td align=\"center\">".$row['id_filtro_orden']."</td>";
            $htmlTb .= "<td align=\"center\">".($row['descripcion'])."</td>";
            $htmlTb .= "<td align=\"center\" ".$title.">".$row['bloqueo_items']."</td>";
            $htmlTb .= "<td align=\"center\">".$totAccesorio."</td>";
            
            $htmlTb .= '<td><img width="16" border="0" class="puntero" onClick="xajax_cargarFiltroOrden('.$row['id_filtro_orden'].');" src="../img/iconos/edit.png" /></td>';
            $htmlTb .= '<td><img width="16" border="0" class="puntero" onClick="eliminarFiltroOrden('.$row['id_filtro_orden'].');" src="../img/iconos/delete.png" /></td>';
            
            $htmlTb.= "</tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFiltroOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFiltroOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoFiltroOrden(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFiltroOrden(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoFiltroOrden(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"18\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListadoFiltroOrden","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function guardarFiltroOrden($form){
    
    $objResponse = new xajaxResponse();
    
    $idFiltroOrden = $form["idFiltroOrden"]; 
    $descripcion = $form["descripcion"];
    $bloqueoItems = $form["bloqueoItems"];
    $totAccesorio = $form["totList"];
    
    if($idFiltroOrden == ""){//nuevo
        if (!xvalidaAcceso($objResponse,PAGE_PRIV,"insertar")){
		return $objResponse;
	}
        
        $sql = sprintf("INSERT INTO sa_filtro_orden (descripcion, bloqueo_items, tot_accesorio) 
                                VALUES (%s, %s, %s)",
                        valTpdato($descripcion,"text"),                     
                        valTpdato($bloqueoItems,"text"),
                        valTpdato($totAccesorio,"int")
                );
        $rs = mysql_query($sql);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
        
        $objResponse->alert("Agregado correctamente");
        
    }else{//actualizar     
        if (!xvalidaAcceso($objResponse,PAGE_PRIV,"editar")){
		return $objResponse;
	}
        
        $sql = sprintf("UPDATE sa_filtro_orden SET descripcion = %s, bloqueo_items = %s, tot_accesorio = %s
                        WHERE id_filtro_orden = %s ",
                        valTpdato($descripcion,"text"),
                        valTpdato($bloqueoItems,"text"),
                        valTpdato($totAccesorio,"int"),
                        valTpDato($idFiltroOrden,"int")
                );
        $rs = mysql_query($sql);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
        
        $objResponse->alert("Actualizado correctamente");
    }
    
    $objResponse->script("$('#divFlotante').hide();");
    $objResponse->script("xajax_listadoFiltroOrden();");
    
    return $objResponse;
}

function cargarFiltroOrden($idFiltroOrden){
    
    $objResponse = new xajaxResponse();
    
    $sql = sprintf("SELECT descripcion, bloqueo_items, tot_accesorio FROM sa_filtro_orden WHERE id_filtro_orden = %s LIMIT 1",
                        valTpDato($idFiltroOrden,"int"));
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $row = mysql_fetch_assoc($rs);
    
    $objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Filtro Orden");
    $objResponse->assign("idFiltroOrden","value",$idFiltroOrden);
    $objResponse->assign("descripcion","value",($row["descripcion"]));
    $objResponse->assign("bloqueoItems","value",$row["bloqueo_items"]);
    $objResponse->assign("totList","value",$row["tot_accesorio"]);
    
    $objResponse->script('$("#divFlotante").show();
                         centrarDiv($("#divFlotante")[0]);');
    
    return $objResponse;
}

function eliminarFiltroOrden($idFiltroOrden){
    
    $objResponse = new xajaxResponse();
    
    if (!xvalidaAcceso($objResponse,PAGE_PRIV,"eliminar")){
            return $objResponse;
    }
    
    $sql = sprintf("DELETE FROM sa_filtro_orden WHERE id_filtro_orden = %s LIMIT 1",
                        valTpDato($idFiltroOrden,"int"));
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $objResponse->alert("Eliminado Correctamente");
    $objResponse->script("xajax_listadoFiltroOrden();");
    
    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarFiltroOrden");
$xajax->register(XAJAX_FUNCTION,"listadoFiltroOrden");
$xajax->register(XAJAX_FUNCTION,"guardarFiltroOrden");
$xajax->register(XAJAX_FUNCTION,"cargarFiltroOrden");
$xajax->register(XAJAX_FUNCTION,"eliminarFiltroOrden");

function titleItems(){
    $html = "1-PAQUETES \n";
    $html .= "2-REPUESTOS \n";
    $html .= "3-MANO DE OBRA \n";
    $html .= "4-TOT \n";
    $html .= "5-NOTAS \n";
    
    return $html;
}