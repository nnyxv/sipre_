<?php

function buscarCodigoAsesor($valForm) {
	$objResponse = new xajaxResponse();
	
	$objResponse->script(sprintf("xajax_listadoCodigoAsesor(0,'','','%s');",
		$valForm['txtCriterio']));
	
	return $objResponse;
}

function listadoCodigoAsesor($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
		
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
        //$valCadBusq[0] criterio
       
        if($valCadBusq[0] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("sa_codigo_asesor.codigo_asesor LIKE %s OR CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) LIKE %s",
                        valTpDato("%".$valCadBusq[0]."%","text"),
                        valTpDato("%".$valCadBusq[0]."%","text")
                    );
        }
	
	$query = sprintf("SELECT sa_codigo_asesor.id_codigo_asesor, 
                                 sa_codigo_asesor.id_empleado, 
                                 sa_codigo_asesor.codigo_asesor,
                                 pg_cargo.nombre_cargo,
                                CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) as nombre_completo                                
                                FROM sa_codigo_asesor
                                INNER JOIN pg_empleado ON sa_codigo_asesor.id_empleado = pg_empleado.id_empleado
                                INNER JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                                INNER JOIN pg_cargo ON pg_cargo_departamento.id_cargo = pg_cargo.id_cargo
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
            $htmlTh .= ordenarCampo("xajax_listadoCodigoAsesor", "4%", $pageNum, "id_codigo_asesor", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
            $htmlTh .= ordenarCampo("xajax_listadoCodigoAsesor", "35%", $pageNum, "nombre_completo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empleado"));
            $htmlTh .= ordenarCampo("xajax_listadoCodigoAsesor", "35%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cargo"));
            $htmlTh .= ordenarCampo("xajax_listadoCodigoAsesor", "12%", $pageNum, "codigo_asesor", $campOrd, $tpOrd, $valBusq, $maxRows, ("C&oacute;digo Asesor"));            
            $htmlTh .= "<td width=\"1%\"></td>";	
            $htmlTh .= "<td width=\"1%\"></td>";	
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
            $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
            $contFila++;
            
            $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
                        
            $htmlTb .= "<td align=\"center\">".$row['id_codigo_asesor']."</td>";
            $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_completo'])."</td>";
            $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_cargo'])."</td>";
            $htmlTb .= "<td align=\"center\">".$row['codigo_asesor']."</td>";
            $htmlTb .= '<td><img width="16" border="0" class="puntero" onClick="xajax_cargarCodigoAsesor('.$row['id_codigo_asesor'].');" src="../img/iconos/edit.png" /></td>';
            $htmlTb .= '<td><img width="16" border="0" class="puntero" onClick="eliminarCodigoAsesor('.$row['id_codigo_asesor'].');" src="../img/iconos/delete.png" /></td>';
            
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCodigoAsesor(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCodigoAsesor(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoCodigoAsesor(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCodigoAsesor(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCodigoAsesor(%s,'%s','%s','%s',%s);\">%s</a>",
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
	
	$objResponse->assign("divListadoCodigoAsesor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
	return $objResponse;
}

function guardarCodigoAsesor($form){
    
    $objResponse = new xajaxResponse();
    
    $idCodigoAsesor = $form["idCodigoAsesor"]; 
    $idEmpleado = $form["idEmpleado"]; 
    $codigoAsesor = $form["codigoAsesor"];    
    
    if($idCodigoAsesor == ""){//nuevo
        if (!xvalidaAcceso($objResponse,PAGE_PRIV,"insertar")){
		return $objResponse;
	}
        
        $sql = sprintf("INSERT INTO sa_codigo_asesor (id_empleado, codigo_asesor) 
                                VALUES (%s, %s)",
                        valTpdato($idEmpleado,"int"),
                        valTpDato($codigoAsesor,"text")
                );
        $rs = mysql_query($sql);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
        
        $objResponse->alert("Agregado correctamente");
        
    }else{//actualizar     
        if (!xvalidaAcceso($objResponse,PAGE_PRIV,"editar")){
		return $objResponse;
	}
        
        $sql = sprintf("UPDATE sa_codigo_asesor SET id_empleado = %s, codigo_asesor = %s
                        WHERE id_codigo_asesor = %s ",
                        valTpdato($idEmpleado,"int"),
                        valTpDato($codigoAsesor,"text"),
                        valTpDato($idCodigoAsesor,"int")
                );
        $rs = mysql_query($sql);
        if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
        
        $objResponse->alert("Actualizado correctamente");
    }
    
    $objResponse->script("$('#divFlotante').hide();");
    $objResponse->script("xajax_listadoCodigoAsesor();");
    
    return $objResponse;
}

function cargarCodigoAsesor($idCodigoAsesor){
    
    $objResponse = new xajaxResponse();
    
    $sql = sprintf("SELECT id_codigo_asesor, codigo_asesor, pg_empleado.id_empleado,
                    CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) as nombre_completo                                
                    FROM sa_codigo_asesor
                    INNER JOIN pg_empleado ON sa_codigo_asesor.id_empleado = pg_empleado.id_empleado
                    WHERE id_codigo_asesor = %s LIMIT 1",
                        valTpDato($idCodigoAsesor,"int"));
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $row = mysql_fetch_assoc($rs);
    
    $objResponse->assign("tdFlotanteTitulo","innerHTML","Editar C&oacute;digo Asesor");
    $objResponse->assign("idCodigoAsesor","value",$idCodigoAsesor);
    $objResponse->assign("idEmpleado","value",$row["id_empleado"]);
    $objResponse->assign("nombreEmpleado","value",$row["nombre_completo"]);
    $objResponse->assign("codigoAsesor","value",$row["codigo_asesor"]);
    
    $objResponse->script('$("#divFlotante").show();
                         centrarDiv($("#divFlotante")[0]);');
    
    return $objResponse;
}

function eliminarCodigoAsesor($idCodigoAsesor){
    
    $objResponse = new xajaxResponse();
    
    if (!xvalidaAcceso($objResponse,PAGE_PRIV,"eliminar")){
            return $objResponse;
    }
    
    $sql = sprintf("DELETE FROM sa_codigo_asesor WHERE id_codigo_asesor = %s LIMIT 1",
                        valTpDato($idCodigoAsesor,"int"));
    
    $rs = mysql_query($sql);
    if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nSql: ".$sql); }
    
    $objResponse->alert("Eliminado Correctamente");
    $objResponse->script("xajax_listadoCodigoAsesor();");
    
    return $objResponse;
}

function buscarEmpleado($txtCriterio){
    $objResponse = new xajaxResponse();

    $objResponse->script(sprintf("xajax_listadoEmpleado(0,'','','%s');",
            $txtCriterio));
	
    return $objResponse;
}

function listadoEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
    $objResponse = new xajaxResponse();

    $valCadBusq = explode("|", $valBusq);
    $startRow = $pageNum * $maxRows;

    //$valCadBusq[0] criterio

    if($valCadBusq[0] != ""){
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond.sprintf("CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) LIKE %s",
                    valTpDato("%".$valCadBusq[0]."%","text")
                );
    }

    $query = sprintf("SELECT id_empleado, nombre_cargo,
                            CONCAT_WS(' ',pg_empleado.nombre_empleado, pg_empleado.apellido) as nombre_completo
                            
                            FROM pg_empleado
                            INNER JOIN pg_cargo_departamento ON pg_empleado.id_cargo_departamento = pg_cargo_departamento.id_cargo_departamento
                            INNER JOIN pg_cargo ON pg_cargo_departamento.id_cargo = pg_cargo.id_cargo
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
        $htmlTh .= "<td width=\"1%\"></td>";
        $htmlTh .= ordenarCampo("xajax_listadolistadoEmpleado", "4%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
        $htmlTh .= ordenarCampo("xajax_listadolistadoEmpleado", "35%", $pageNum, "nombre_completo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empleado"));                
        $htmlTh .= ordenarCampo("xajax_listadolistadoEmpleado", "35%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, ("Cargo"));                
    $htmlTh .= "</tr>";

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
        $clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
        $contFila++;

        $htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";

        $htmlTb .= "<td align=\"center\"><button onclick=\"xajax_asignarEmpleado(".$row['id_empleado'].",'".utf8_encode($row['nombre_completo'])."');\" class=\"puntero\" type=\"button\"><img border=\"0\" src=\"../img/iconos/select.png\"></button></td>";
        $htmlTb .= "<td align=\"center\">".$row['id_empleado']."</td>";
        $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_completo'])."</td>";        
        $htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_cargo'])."</td>";        

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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum > 0) { 
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"100\">";

                                                    $htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEmpleado(%s,'%s','%s','%s',%s)\">",
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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum < $totalPages) {
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
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

    $objResponse->assign("divListadoEmpleado","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
    $objResponse->script("$('#divFlotante2').show();");
    $objResponse->script("centrarDiv($('#divFlotante2')[0]);");

    return $objResponse;
    
}

function asignarEmpleado($idEmpleado, $nombreEmpleado){
    $objResponse = new xajaxResponse();
    
    $objResponse->assign("idEmpleado","value",$idEmpleado);
    $objResponse->assign("nombreEmpleado","value",$nombreEmpleado);
    $objResponse->script("$('#divFlotante2').hide();");
    
    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarCodigoAsesor");
$xajax->register(XAJAX_FUNCTION,"listadoCodigoAsesor");
$xajax->register(XAJAX_FUNCTION,"guardarCodigoAsesor");
$xajax->register(XAJAX_FUNCTION,"cargarCodigoAsesor");
$xajax->register(XAJAX_FUNCTION,"eliminarCodigoAsesor");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"listadoEmpleado");
$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");

//funciones comunes
function fechaTiempoComun($fechaTiempo){
    if($fechaTiempo != "" && $fechaTiempo != NULL){
        return date("d-m-Y h:i a",strtotime($fechaTiempo));
    }
}