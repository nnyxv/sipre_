<?php

    function buscar($valForm) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s", $valForm['txtCriterio']);

        $objResponse->loadCommands(listadoDiasHabiles(0, 'codigo','ASC', $valBusq));

	return $objResponse;
    }

    function listadoDiasHabiles($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse= new xajaxResponse();

        if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
            $objResponse->assign("tdListadoDiasHabiles","innerHTML","Acceso Denegado");
            return $objResponse;
        }

	$valCadBusq= explode("|", $valBusq);
	$startRow= $pageNum * $maxRows;

        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("borrado= %s", valTpDato(0, "int"));

	/*if($valCadBusq[0] != "-1" && $valCadBusq[0] != ""){
            $cond= (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";

            $sqlBusq.= $cond.sprintf("c.id_modulo = %s",
                    valTpDato($valCadBusq[0], "int"));
	}*/

        $query = sprintf("SELECT
                                  id_dias_habiles,
                                  descripcion,
                                  cantidad_dias,
                                  fecha_dia_habil,
                                  id_usuario_creador,
                                  id_usuario_modificacion,
                                  borrado
                          FROM
                                  sa_dias_habiles %s", $sqlBusq);

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
	
		$htmlTh .= ordenarCampo("xajax_listadoDiasHabiles", "10%", $pageNum, "id_dias_habiles", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listadoDiasHabiles", "60%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
                $htmlTh .= ordenarCampo("xajax_listadoDiasHabiles", "15%", $pageNum, "cantidad_dias", $campOrd, $tpOrd, $valBusq, $maxRows, "N&ordm; de Dias Habiles");
                $htmlTh .= ordenarCampo("xajax_listadoDiasHabiles", "15%", $pageNum, "fecha_dia_habil", $campOrd, $tpOrd, $valBusq, $maxRows, "Mes y A&ntilde;o");
		$htmlTh .= "<td class='noprint' colspan='3'></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";

		$contFila ++;

		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";

                $htmlTb .= "<td align=\"center\">".$row['id_dias_habiles']."</td>";
                $htmlTb .= "<td align=\"left\">".htmlentities(utf8_decode($row['descripcion']))."</td>";
                $htmlTb .= "<td align=\"center\">".htmlentities($row['cantidad_dias'])."</td>";
                $htmlTb .= "<td align=\"center\">".htmlentities($row['fecha_dia_habil'])."</td>";
                $htmlTb .= "<td align=\"center\" class='noprint'><img class=\"puntero\" onclick=\"xajax_buscarDiasHabiles(3, '".$row['id_dias_habiles']."');\" src=\"../img/iconos/ico_view.png\"/></td>";
                $htmlTb .= "<td align=\"center\" class='noprint'><img class=\"puntero\" onclick=\"xajax_buscarDiasHabiles(2, '".$row['id_dias_habiles']."');\" src=\"../img/iconos/ico_edit.png\"/></td>";
                $htmlTb .= "<td align=\"center\" class='noprint'><img class=\"puntero\" onclick=\"eliminar('".$row['id_dias_habiles']."');\" src=\"../img/iconos/ico_delete.png\"/></td>";
                $htmlTb .= "</tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDiasHabiles(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDiasHabiles(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoDiasHabiles(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDiasHabiles(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDiasHabiles(%s,'%s','%s','%s',%s);\">%s</a>",
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

	
	$objResponse->assign("tdListadoDiasHabiles","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
		
        return $objResponse;
    }

    function insertDiasHabiles($formDiasHabiles){
        $objResponse = new xajaxResponse();

        if (!xvalidaAcceso($objResponse,PAGE_PRIV,insertar)){
		return $objResponse;
	}

        $query= sprintf("INSERT INTO sa_dias_habiles
                                (descripcion, cantidad_dias, fecha_dia_habil, id_usuario_creador)
                            VALUES
                                (%s, %s, %s, %s)",
                            valTpDato($formDiasHabiles['txtDescripcion'],"text"),
                            valTpDato($formDiasHabiles['txtCantidadDias'], "int"),
                            valTpDato($formDiasHabiles['txtFechaDiaHabil'], "text"),
                            valTpDato($_SESSION['idUsuarioSysGts'], "int"));

        $result = mysql_query($query);
        if (!$result) return $objResponse->alert(mysql_error()." ".__LINE__." ".$query);

        $objResponse->alert("Dias Habiles Taller ingresado con exito");

        $objResponse->script("$('divFlotante').style.display='none';");
        $objResponse->loadCommands(listadoDiasHabiles(0, 'id_dias_habiles','ASC', ''));
        return $objResponse;
    }

    function updateDiasHabiles($formDiasHabiles){
        $objResponse = new xajaxResponse();

        if (!xvalidaAcceso($objResponse,PAGE_PRIV,editar)){
		return $objResponse;
	}

        $query= sprintf("UPDATE sa_dias_habiles SET
                                descripcion= %s,
                                cantidad_dias= %s,
                                fecha_dia_habil= %s,
                                id_usuario_modificacion= %s
                         WHERE
                                id_dias_habiles = %s",
                            valTpDato($formDiasHabiles['txtDescripcion'],"text"),
                            valTpDato($formDiasHabiles['txtCantidadDias'], "int"),
                            valTpDato($formDiasHabiles['txtFechaDiaHabil'], "text"),
                            valTpDato($_SESSION['idUsuarioSysGts'], "int"),
                            valTpDato($formDiasHabiles['hddIdDiasHabiles'], "int"));

        $result = mysql_query($query);
        if (!$result) return $objResponse->alert(mysql_error()." ".__LINE__." ".$query);

        $objResponse->alert("Dias Habiles Taller actualizado con exito");

        $objResponse->script("$('divFlotante').style.display='none';");
        $objResponse->loadCommands(listadoDiasHabiles(0, 'id_dias_habiles','ASC', ''));
        return $objResponse;
    }

    function buscarDiasHabiles($acc, $id){
        $objResponse = new xajaxResponse();

        $query = sprintf("SELECT
                                  *
                          FROM
                                  sa_dias_habiles
                          WHERE
                                  id_dias_habiles= %s", $id);

        $rs= mysql_query($query);
        if (!$rs) return $objResponse->alert(mysql_error());
	$row= mysql_fetch_assoc($rs);

        $objResponse->script("abrirVentana('".$acc."');");
        $objResponse->script("$('hddIdDiasHabiles').value= '".$row['id_dias_habiles']."';");
        $objResponse->script("$('txtDescripcion').value= '".$row['descripcion']."';");
        $objResponse->script("$('txtCantidadDias').value= '".$row['cantidad_dias']."';");
        $objResponse->script("$('txtFechaDiaHabil').value= '".$row['fecha_dia_habil']."';");

        if($acc == 2){
            $objResponse->script("$('txtDescripcion').disabled= false;");
            $objResponse->script("$('txtCantidadDias').disabled= false;");
            $objResponse->script("$('txtFechaDiaHabil').disabled= false;");
        }else if($acc == 3){
            $objResponse->script("$('txtDescripcion').disabled= true;");
            $objResponse->script("$('txtCantidadDias').disabled= true;");
            $objResponse->script("$('txtFechaDiaHabil').disabled= true;");
        }

        return $objResponse;
    }

    function deleteDiasHabiles($id){
        $objResponse = new xajaxResponse();

        if (!xvalidaAcceso($objResponse,PAGE_PRIV,eliminar)){
		return $objResponse;
	}

        $query= sprintf("UPDATE sa_dias_habiles SET
                                borrado= 1,
                                id_usuario_modificacion= %s
                         WHERE
                                id_dias_habiles = %s",
                            valTpDato($_SESSION['idUsuarioSysGts'], "int"),
                            valTpDato($id, "int"));

        $result = mysql_query($query);
        if (!$result) return $objResponse->alert(mysql_error()." ".__LINE__." ".$query);

        $objResponse->alert("Dias Habiles Taller eliminado con exito");
        
        $objResponse->loadCommands(listadoDiasHabiles(0, 'id_dias_habiles','ASC', ''));
        return $objResponse;
    }

    $xajax->register(XAJAX_FUNCTION,"buscar");
    $xajax->register(XAJAX_FUNCTION,"listadoDiasHabiles");
    $xajax->register(XAJAX_FUNCTION,"insertDiasHabiles");
    $xajax->register(XAJAX_FUNCTION,"updateDiasHabiles");
    $xajax->register(XAJAX_FUNCTION,"buscarDiasHabiles");
    $xajax->register(XAJAX_FUNCTION,"deleteDiasHabiles");
?>
