<?php

function buscarPreciosTot($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoPreciosTot(0,'','','%s');",
		$valForm['txtCriterio']));
	
	return $objResponse;
}

function listadoPreciosTot($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
        //$valCadBusq[0] criterio
       
        if($valCadBusq[0] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("sa_precios_tot.descripcion LIKE %s",
                        valTpDato("%".$valCadBusq[0]."%","text"));
        }
	
	$query = sprintf("SELECT sa_precios_tot.id_precio_tot, 
                                 sa_precios_tot.descripcion, 
                                 sa_precios_tot.porcentaje, 
                                 sa_precios_tot.activo, 
                                 sa_precios_tot.id_empleado_creador, 
                                 sa_precios_tot.id_empleado_editor, 
                                 sa_precios_tot.fecha_registro, 
                                 sa_precios_tot.fecha_editado,
                                CONCAT_WS(' ',a.nombre_empleado, a.apellido) as nombre_empleado_creador,
                                CONCAT_WS(' ',b.nombre_empleado, b.apellido) as nombre_empleado_editor
                                FROM sa_precios_tot
                                LEFT JOIN pg_empleado a ON(sa_precios_tot.id_empleado_creador = a.id_empleado)
                                LEFT JOIN pg_empleado b ON(sa_precios_tot.id_empleado_editor = b.id_empleado)
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
            $htmlTh .= ordenarCampo("xajax_listadoPreciosTot", "1%", $pageNum, "activo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Act"));
            $htmlTh .= ordenarCampo("xajax_listadoPreciosTot", "4%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, ("Nro"));
            $htmlTh .= ordenarCampo("xajax_listadoPreciosTot", "35%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, ("Descripci&oacute;n"));
            $htmlTh .= ordenarCampo("xajax_listadoPreciosTot", "10%", $pageNum, "porcentaje", $campOrd, $tpOrd, $valBusq, $maxRows, ("Porcentaje"));
            $htmlTh .= ordenarCampo("xajax_listadoPreciosTot", "12%", $pageNum, "fecha_registro", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Registro"));
            $htmlTh .= ordenarCampo("xajax_listadoPreciosTot", "12%", $pageNum, "nombre_empleado_creador", $campOrd, $tpOrd, $valBusq, $maxRows, ("Emp. Creador"));
            $htmlTh .= ordenarCampo("xajax_listadoPreciosTot", "12%", $pageNum, "fecha_editado", $campOrd, $tpOrd, $valBusq, $maxRows, ("Fecha Editado"));
            $htmlTh .= ordenarCampo("xajax_listadoPreciosTot", "12%", $pageNum, "nombre_empleado_editor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Emp. Editor"));
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
            $htmlTb .= "<td align=\"center\">".$row['id_precio_tot']."</td>";
            $htmlTb .= "<td align=\"center\">".($row['descripcion'])."</td>";
            $htmlTb .= "<td align=\"center\">".$row['porcentaje']."</td>";
            $htmlTb .= "<td align=\"center\">".fechaTiempoComun($row['fecha_registro'])."</td>";
            $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empleado_creador'])."</td>";
            $htmlTb .= "<td align=\"center\">".fechaTiempoComun($row['fecha_editado'])."</td>";
            $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empleado_editor'])."</td>";
            $htmlTb .= '<td><img width="16" border="0" class="puntero" onClick="xajax_cargarPrecioTot('.$row['id_precio_tot'].');" src="../img/iconos/edit.png" /></td>';
            $htmlTb .= '<td><img width="16" border="0" class="puntero" onClick="eliminarPrecioTot('.$row['id_precio_tot'].');" src="../img/iconos/delete.png" /></td>';
            
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPreciosTot(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPreciosTot(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoPreciosTot(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPreciosTot(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoPreciosTot(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListadoPreciosTot","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function guardarPrecioTot($form){
    
    $objResponse = new xajaxResponse();
    
    $idPrecioTot = $form["idPrecioTot"]; 
    $descripcion = $form["descripcion"]; 
    $porcentaje = $form["porcentaje"];
    $activoList = $form["activoList"];
    
    if($idPrecioTot == ""){//nuevo
        if (!xvalidaAcceso($objResponse,PAGE_PRIV,"insertar")){
		return $objResponse;
	}
        
        $sql = sprintf("INSERT INTO sa_precios_tot (descripcion, porcentaje, activo, id_empleado_creador, fecha_registro) 
                                VALUES (%s, %s, %s, %s, now())",
                        valTpdato($descripcion,"text"),
                        valTpDato($porcentaje,"double"),
                        valTpDato($activoList,"int"),
                        valTpDato(idEmpleado($_SESSION["idUsuarioSysGts"]),"int")
                );
        $rs = mysql_query($sql);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
        
        $objResponse->alert("Agregado correctamente");
        
    }else{//actualizar     
        if (!xvalidaAcceso($objResponse,PAGE_PRIV,"editar")){
		return $objResponse;
	}
        
        $sql = sprintf("UPDATE sa_precios_tot SET descripcion = %s, porcentaje = %s, activo = %s, id_empleado_editor = %s, fecha_editado = NOW()
                        WHERE id_precio_tot = %s ",
                        valTpdato($descripcion,"text"),
                        valTpDato($porcentaje,"double"),
                        valTpDato($activoList,"int"),
                        valTpDato(idEmpleado($_SESSION["idUsuarioSysGts"]),"int"),
                        valTpDato($idPrecioTot,"int")
                );
        $rs = mysql_query($sql);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
        
        $objResponse->alert("Actualizado correctamente");
    }
    
    $objResponse->script("$('#divFlotante').hide();");
    $objResponse->script("xajax_listadoPreciosTot();");
    
    return $objResponse;
}

function cargarPrecioTot($idPrecioTot){
    
    $objResponse = new xajaxResponse();
    
    $sql = sprintf("SELECT descripcion, porcentaje, activo FROM sa_precios_tot WHERE id_precio_tot = %s LIMIT 1",
                        valTpDato($idPrecioTot,"int"));
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $row = mysql_fetch_assoc($rs);
    
    $objResponse->assign("tdFlotanteTitulo","innerHTML","Editar Accesorio");
    $objResponse->assign("idPrecioTot","value",$idPrecioTot);
    $objResponse->assign("descripcion","value",($row["descripcion"]));
    $objResponse->assign("porcentaje","value",$row["porcentaje"]);
    $objResponse->assign("activoList","value",$row["activo"]);
    
    $objResponse->script('$("#divFlotante").show();
                         centrarDiv($("#divFlotante")[0]);');
    
    return $objResponse;
}

function eliminarPrecioTot($idPrecioTot){
    
    $objResponse = new xajaxResponse();
    
    if (!xvalidaAcceso($objResponse,PAGE_PRIV,"eliminar")){
            return $objResponse;
    }
    
    $sql = sprintf("DELETE FROM sa_precios_tot WHERE id_precio_tot = %s LIMIT 1",
                        valTpDato($idPrecioTot,"int"));
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $objResponse->alert("Eliminado Correctamente");
    $objResponse->script("xajax_listadoPreciosTot();");
    
    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarPreciosTot");
$xajax->register(XAJAX_FUNCTION,"listadoPreciosTot");
$xajax->register(XAJAX_FUNCTION,"guardarPrecioTot");
$xajax->register(XAJAX_FUNCTION,"cargarPrecioTot");
$xajax->register(XAJAX_FUNCTION,"eliminarPrecioTot");

//funciones comunes
function fechaTiempoComun($fechaTiempo){
    if($fechaTiempo != "" && $fechaTiempo != NULL){
        return date("d-m-Y h:i a",strtotime($fechaTiempo));
    }
}