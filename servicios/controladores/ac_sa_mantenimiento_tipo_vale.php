<?php

function buscarTipoVale($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoTipoVale(0,'','','%s');",
		$valForm['txtCriterio']));
	
	return $objResponse;
}

function listadoTipoVale($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
        //$valCadBusq[0] criterio
       
        if($valCadBusq[0] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("sa_tipo_vale.descripcion LIKE %s",
                        valTpDato("%".$valCadBusq[0]."%","text"));
        }
	
        $title = "title=\"".filtrosOrden(1)."\"";
        
	$query = sprintf("SELECT sa_tipo_vale.id_tipo_vale, 
                                 sa_tipo_vale.descripcion, 
                                 sa_tipo_vale.filtros_orden, 
                                 sa_tipo_vale.activo                           
                                FROM sa_tipo_vale
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
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
            $htmlTh .= ordenarCampo("xajax_listadoTipoVale", "1%", $pageNum, "activo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Act"));
            $htmlTh .= ordenarCampo("xajax_listadoTipoVale", "1%", $pageNum, "id_tipo_vale", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
            $htmlTh .= ordenarCampo("xajax_listadoTipoVale", "40%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripci&oacute;n"));
            $htmlTh .= ordenarCampo("xajax_listadoTipoVale", "40%", $pageNum, "filtros_orden", $campOrd, $tpOrd, $valBusq, $maxRows, ("Filtros orden"));
            $htmlTh .= "<td width=\"1%\"></td>";	
            $htmlTh .= "<td width=\"1%\"></td>";	
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $contFila++;
            
            if($row['activo'] == "1"){
                $imgActivo = '<img title="Activo" src="../img/iconos/ico_verde.gif">';
            }else{
                $imgActivo = '<img title="Activo" src="../img/iconos/ico_rojo.gif">';
            }

            $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
            
            $htmlTb .= "<td align=\"center\">".$imgActivo."</td>";
            $htmlTb .= "<td align=\"center\">".$row['id_tipo_vale']."</td>";
            $htmlTb .= "<td align=\"center\">".($row['descripcion'])."</td>";
            $htmlTb .= "<td align=\"center\" ".$title." >".$row['filtros_orden']."</td>";
            $htmlTb .= '<td><img width="16" border="0" class="puntero" onClick="xajax_cargarTipoVale('.$row['id_tipo_vale'].');" src="../img/iconos/edit.png" /></td>';
            $htmlTb .= '<td><img width="16" border="0" class="puntero" onClick="eliminarTipoVale('.$row['id_tipo_vale'].');" src="../img/iconos/delete.png" /></td>';
            
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoVale(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoVale(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoTipoVale(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoVale(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTipoVale(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListadoTipoVale","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function guardarTipoVale($form){
    
    $objResponse = new xajaxResponse();
    
    $idTipoVale = $form["idTipoVale"]; 
    $descripcion = $form["descripcion"]; 
    $filtroOrden = $form["filtrosOrden"];
    $activoList = $form["activoList"];
    
    if($idTipoVale == ""){//nuevo
        if (!xvalidaAcceso($objResponse,PAGE_PRIV,"insertar")){
		return $objResponse;
	}
        
        $sql = sprintf("INSERT INTO sa_tipo_vale (descripcion, filtros_orden, activo) 
                                VALUES (%s, %s, %s)",
                        valTpdato($descripcion,"text"),
                        valTpDato($filtroOrden,"text"),
                        valTpDato($activoList,"int")
                );
        $rs = mysql_query($sql);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
        
        $objResponse->alert("Agregado correctamente");
        
    }else{//actualizar     
        if (!xvalidaAcceso($objResponse,PAGE_PRIV,"editar")){
		return $objResponse;
	}
        
        $sql = sprintf("UPDATE sa_tipo_vale SET descripcion = %s, filtros_orden = %s, activo = %s
                        WHERE id_tipo_vale = %s ",
                        valTpdato($descripcion,"text"),
                        valTpDato($filtroOrden,"text"),
                        valTpDato($activoList,"int"),                       
                        valTpDato($idTipoVale,"int")
                );
        $rs = mysql_query($sql);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
        
        $objResponse->alert("Actualizado correctamente");
    }
    
    $objResponse->script("$('#divFlotante').hide();");
    $objResponse->script("xajax_listadoTipoVale();");
    
    return $objResponse;
}

function cargarTipoVale($idTipoVale){
    
    $objResponse = new xajaxResponse();
    
    $sql = sprintf("SELECT descripcion, filtros_orden, activo FROM sa_tipo_vale WHERE id_tipo_vale = %s LIMIT 1",
                        valTpDato($idTipoVale,"int"));
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $row = mysql_fetch_assoc($rs);
    
    $objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Tipo Vale");
    $objResponse->assign("idTipoVale","value",$idTipoVale);
    $objResponse->assign("descripcion","value",($row["descripcion"]));
    $objResponse->assign("filtrosOrden","value",$row["filtros_orden"]);
    $objResponse->assign("activoList","value",$row["activo"]);
    
    $objResponse->script('$("#divFlotante").show();
                         centrarDiv($("#divFlotante")[0]);');
    
    return $objResponse;
}

function eliminarTipoVale($idTipoVale){
    
    $objResponse = new xajaxResponse();
    
    if (!xvalidaAcceso($objResponse,PAGE_PRIV,"eliminar")){
            return $objResponse;
    }
    
    $sql = sprintf("DELETE FROM sa_tipo_vale WHERE id_tipo_vale = %s LIMIT 1",
                        valTpDato($idTipoVale,"int"));
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $objResponse->alert("Eliminado Correctamente");
    $objResponse->script("xajax_listadoTipoVale();");
    
    return $objResponse;
}

function filtrosOrden($comun = false){
    $objResponse = new xajaxResponse();
    
    $sql = "SELECT id_filtro_orden, descripcion FROM sa_filtro_orden";
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $html = "<table align='center' style='font-size:10px;'>";
    while($row = mysql_fetch_assoc($rs)){
        $html .= "<tr><td>";
        $html .= $row["id_filtro_orden"]."-".$row["descripcion"]."&nbsp;";
        $html .= "</td></tr>";
        
        $html2 .= $row["id_filtro_orden"]."-".$row["descripcion"]."\n";
    }
    $html .= "</table>";
    
    if($comun){
        return $html2;
    }
    
    $objResponse->assign("divFiltrosOrden","innerHTML",$html);
     
    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarTipoVale");
$xajax->register(XAJAX_FUNCTION,"listadoTipoVale");
$xajax->register(XAJAX_FUNCTION,"guardarTipoVale");
$xajax->register(XAJAX_FUNCTION,"cargarTipoVale");
$xajax->register(XAJAX_FUNCTION,"eliminarTipoVale");
$xajax->register(XAJAX_FUNCTION,"filtrosOrden");