<?php

function buscar($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listado(0,'','','%s|%s|%s');",
		$valForm['txtCriterio'],
		$valForm['activoBusqList'],
		$valForm['fijoBusqList']));
	
	return $objResponse;
}

function listado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	//$valCadBusq[0] criterio
	//$valCadBusq[1] activo
	//$valCadBusq[2] fijo
   
	if($valCadBusq[0] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("descripcion LIKE %s",
					valTpDato("%".$valCadBusq[0]."%","text"));
	}
	
	if($valCadBusq[1] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("activo = %s",
					valTpDato($valCadBusq[1],"int"));
	}
	
	if($valCadBusq[2] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("fijo = %s",
					valTpDato($valCadBusq[2],"int"));
	}
	
	$query = sprintf("SELECT id_art_inventario, 
							 descripcion, 
							 cantidad_definida, 
							 fijo,
							 activo,
							 fecha_registro
					FROM sa_art_inventario                               
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
            $htmlTh .= ordenarCampo("xajax_listado", "1%", $pageNum, "activo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Act"));
            $htmlTh .= ordenarCampo("xajax_listado", "4%", $pageNum, "id_art_inventario", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro"));
            $htmlTh .= ordenarCampo("xajax_listado", "35%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripci&oacute;n"));
            $htmlTh .= ordenarCampo("xajax_listado", "10%", $pageNum, "cantidad_definida", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cantidad Def."));
			$htmlTh .= ordenarCampo("xajax_listado", "10%", $pageNum, "fijo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fijo"));
            $htmlTh .= ordenarCampo("xajax_listado", "12%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Registro"));
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
			
			if($row['fijo'] == "1") { $fijo = "SI"; } else { $fijo = "NO"; }

            $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
            
            $htmlTb .= "<td align=\"center\">".$imgActivo."</td>";
            $htmlTb .= "<td align=\"center\">".$row['id_art_inventario']."</td>";
            $htmlTb .= "<td align=\"center\">".($row['descripcion'])."</td>";
            $htmlTb .= "<td align=\"center\">".$row['cantidad_definida']."</td>";
			$htmlTb .= "<td align=\"center\">".$fijo."</td>";
            $htmlTb .= "<td align=\"center\">".fechaTiempoComun($row['fecha_registro'])."</td>";
            $htmlTb .= '<td><img width="16" border="0" class="puntero" onClick="xajax_cargar('.$row['id_art_inventario'].');" src="../img/iconos/edit.png" /></td>';
            $htmlTb .= '<td><img width="16" border="0" class="puntero" onClick="eliminar('.$row['id_art_inventario'].');" src="../img/iconos/delete.png" /></td>';
            
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listado(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function guardar($form){
    
    $objResponse = new xajaxResponse();
    
    $id = $form["id"]; 
    $descripcion = $form["descripcion"]; 
    $cantidad = $form["cantidad"];
	$fijo = $form["fijoList"];
    $activoList = $form["activoList"];
    
    if($id == ""){//nuevo
        if (!xvalidaAcceso($objResponse,PAGE_PRIV,"insertar")){
		return $objResponse;
	}
	        
        $sql = sprintf("INSERT INTO sa_art_inventario (descripcion, cantidad_definida, fijo, activo, fecha_registro) 
                                VALUES (%s, %s, %s, %s, now())",
                        valTpdato($descripcion,"text"),
                        valTpDato($cantidad,"int"),
						valTpDato($fijo,"int"),
                        valTpDato($activoList,"int")
                );
        $rs = mysql_query($sql);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
        
        $objResponse->alert("Agregado correctamente");
        
    }else{//actualizar     
        if (!xvalidaAcceso($objResponse,PAGE_PRIV,"editar")){
		return $objResponse;
	}
        
        $sql = sprintf("UPDATE sa_art_inventario SET descripcion = %s, cantidad_definida = %s, fijo = %s, activo = %s
                        WHERE id_art_inventario = %s ",
                        valTpdato($descripcion,"text"),
                        valTpDato($cantidad,"int"),
						valTpDato($fijo,"int"),
                        valTpDato($activoList,"int"),
                        valTpDato($id,"int")
                );
        $rs = mysql_query($sql);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
        
        $objResponse->alert("Actualizado correctamente");
    }
    
    $objResponse->script("$('#divFlotante').hide();");
    $objResponse->script("$('#btnBuscar').click();");
    
    return $objResponse;
}

function cargar($id){
    
    $objResponse = new xajaxResponse();
    $sql = sprintf("SELECT descripcion, cantidad_definida, fijo, activo FROM sa_art_inventario WHERE id_art_inventario = %s LIMIT 1",
                        valTpDato($id,"int"));
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $row = mysql_fetch_assoc($rs);
    
    $objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Accesorio");
    $objResponse->assign("id","value",$id);
    $objResponse->assign("descripcion","value",($row["descripcion"]));
    $objResponse->assign("cantidad","value",$row["cantidad_definida"]);
	$objResponse->assign("fijoList","value",$row["fijo"]);
    $objResponse->assign("activoList","value",$row["activo"]);
    
    $objResponse->script('$("#divFlotante").show();
                         centrarDiv($("#divFlotante")[0]);');
    
    return $objResponse;
}

function eliminar($id){
    
    $objResponse = new xajaxResponse();
    
    if (!xvalidaAcceso($objResponse,PAGE_PRIV,"eliminar")){
            return $objResponse;
    }
	
	$sql = sprintf("SELECT * FROM sa_recepcion_inventario WHERE id_art_inventario = %s LIMIT 1",
                        valTpDato($id,"int"));    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
	$total = mysql_num_rows($rs);
    
	if($total > 0){ return $objResponse->alert("Ya esta en uso y no se puede eliminar, solo desactivar"); }
	
    $sql = sprintf("DELETE FROM sa_art_inventario WHERE id_art_inventario = %s LIMIT 1",
                        valTpDato($id,"int"));
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $objResponse->alert("Eliminado Correctamente");
    $objResponse->script("$('#btnBuscar').click();");
    
    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"listado");
$xajax->register(XAJAX_FUNCTION,"guardar");
$xajax->register(XAJAX_FUNCTION,"cargar");
$xajax->register(XAJAX_FUNCTION,"eliminar");

//funciones comunes
function fechaTiempoComun($fechaTiempo){
    if($fechaTiempo != "" && $fechaTiempo != NULL){
        return date("d-m-Y h:i a",strtotime($fechaTiempo));
    }
}