<?php

    function buscarUnidad($valForm){
        $objResponse = new xajaxResponse();

        $valBusq = sprintf("%s",$valForm['bus_unidad']);

        $objResponse->script("xajax_listadoUnidades('0','','','".$valBusq."')");
        return $objResponse;
    }

    function listadoUnidades($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("sa_v_unidad_basica.nombre_unidad_basica LIKE %s OR sa_v_unidad_basica.nom_modelo LIKE %s)",
		    valTpDato("%".$valCadBusq[0]."%","text"),
			valTpDato("%".$valCadBusq[0]."%","text"));
	}	
	
	$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("id_empresa = %s)",
		    valTpDato($_SESSION['idEmpresaUsuarioSysGts'],"int"));

        $query = "SELECT * FROM sa_v_unidad_basica 
					LEFT JOIN sa_unidad_empresa ON sa_v_unidad_basica.id_unidad_basica = sa_unidad_empresa.id_unidad_basica";
	
	$query .= $sqlBusq;

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$objResponse->assign("divUnidad","width","990px");

	$htmlTableIni .= "<table width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "7%", $pageNum, "id_unidad_basica", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "10%", $pageNum, "nombre_unidad_basica", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "16%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Modelo"));
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "30%", $pageNum, "unidad_completa", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripci&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "5%", $pageNum, "nom_combustible", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Combustible"));
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "10%", $pageNum, "nom_transmision", $campOrd, $tpOrd, $valBusq, $maxRows, "Transmision");
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "7%", $pageNum, "nom_version", $campOrd, $tpOrd, $valBusq, $maxRows, "Versi&oacute;n");
		$htmlTh .= ordenarCampo("xajax_listadoUnidades", "15%", $pageNum, "ano", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("A&ntilde;o"));

		$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		$estado = ($row['estado'] == 0) ? "<img src='../img/iconos/ico_verde.gif' width='12' height='12' />" : "<img src='../img/iconos/ico_rojo.gif' width='12' height='12' />";

		$htmlTb .= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"agregar_unidad('".$row['id_unidad_basica']."');\" title=\"Seleccionar Cliente\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>";
			$htmlTb .= "<td align=\"center\">".$row['id_unidad_basica']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_unidad_basica'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_modelo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['unidad_completa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_combustible'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_transmision'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nom_version'])."</td>";
			$htmlTb .= "<td>".$row['ano']."</td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf .= "<tr class=\"tituloColumna\" >";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidades(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidades(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";

					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoUnidades(%s,'%s','%s','%s',%s)\">",
						"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
					for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
							$htmlTf.="<option value=\"".$nroPag."\"";
							if ($pageNum == $nroPag) {
								$htmlTf.="selected=\"selected\"";
							}
							$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
					}
					$htmlTf.="</select>";

				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidades(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidades(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	$htmlTableFin .= "</table>";

	if (!($totalRows > 0)){
		$htmlTb = "<td colspan=\"12\" class=\"divMsjError\">No se encontraron registros.</td>";
        }
        
	$objResponse->assign("divUnidad","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);

	return $objResponse;
    }

    function cargaLstBusq() {
	$objResponse= new xajaxResponse();

	$queryMarca= sprintf("SELECT * FROM iv_marcas").$sqlBusq;
	$rsMarca= mysql_query($queryMarca);
	if(!$rsMarca) return $objResponse->alert(mysql_error());

	$html= "<select id=\"lstMarcaBusq\" name=\"lstMarcaBusq\" onchange=\"xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));\">";
        $html.= "   <option value=\"-1\">Todos...</option>";

	while ($rowMarca = mysql_fetch_assoc($rsMarca)) {
            $html.= "<option value=\"".$rowMarca['id_marca']."\">".utf8_encode($rowMarca['marca'])."</option>";
	}
	$html.= "</select>";
	$objResponse->assign("tdlstMarcaBusq","innerHTML",$html);


	$queryTipoArticulo = sprintf("SELECT * FROM iv_tipos_articulos").$sqlBusq;
	$rsTipoArticulo = mysql_query($queryTipoArticulo);
	if (!$rsTipoArticulo) return $objResponse->alert(mysql_error());

	$html= "<select id=\"lstTipoArticuloBusq\" name=\"lstTipoArticuloBusq\" onchange=\"xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));\">";
        $html.= "<option value=\"-1\">Todos...</option>";

	while ($rowTipoArticulo = mysql_fetch_assoc($rsTipoArticulo)) {
            $html.= "<option value=\"".$rowTipoArticulo['id_tipo_articulo']."\">".utf8_encode($rowTipoArticulo['descripcion'])."</option>";
	}
	$html.= "</select>";
	$objResponse->assign("tdlstTipoArticuloBusq","innerHTML",$html);



	$querySeccion= sprintf("SELECT * FROM iv_secciones ORDER BY descripcion").$sqlBusq;
	$rsSeccion= mysql_query($querySeccion);
	if (!$rsSeccion) return $objResponse->alert(mysql_error());

	$html= "<select id=\"lstSeccionBusq\" name=\"lstSeccionBusq\" onchange=\"xajax_cargaLstSubSecciones(this.value); xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));\">";
	$html.= "   <option value=\"-1\">Todos...</option>";

	while ($rowSeccion = mysql_fetch_assoc($rsSeccion)) {
            $html .= "<option value=\"".$rowSeccion['id_seccion']."\">".utf8_encode($rowSeccion['descripcion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstSeccionBusq","innerHTML",$html);
	
	return $objResponse;
    }

    function cargaLstSubSecciones($idSeccion) {
	$objResponse = new xajaxResponse();

	$querySubSeccion= sprintf("SELECT * FROM iv_subsecciones WHERE id_seccion = %s", valTpDato($idSeccion,"int"));
	$rsSubSeccion= mysql_query($querySubSeccion) or die(mysql_error().$querySubSeccion);

	$html= "<select id=\"lstSubSeccionBusq\" name=\"lstSubSeccionBusq\" onchange=\"xajax_buscarArticulo(xajax.getFormValues('frmBusArt'), xajax.getFormValues('formulario2'));\">";
        $html.= "   <option value=\"-1\">Todos...</option>";

	while ($rowSubSeccion = mysql_fetch_assoc($rsSubSeccion)) {
            $html .= "<option value=\"".$rowSubSeccion['id_subseccion']."\">".utf8_encode($rowSubSeccion['descripcion'])."</option>";
	}
	$html.= "</select>";
	$objResponse->assign("tdlstSubSeccionBusq","innerHTML",$html);

	return $objResponse;
    }

    function buscarArticulo($valFormArt, $valForm){
	$objResponse = new xajaxResponse();

	/*$codArticulo = "";
        $idUnidadBasica= "";

	for ($cont = 0; $cont <= 2; $cont++) {
		$codArticulo .= $valFormArt['txtCodigoArticulo'.$cont].";";
	}

	$codArticulo = substr($codArticulo,0,strlen($codArticulo)-1);
	$codArticulo = codArticuloExpReg($codArticulo);*/

        for($i= 0; $i < count($valForm['id_unidad_basica']); $i++){
            if($idUnidadBasica == ""){
                $idUnidadBasica.= $valForm['id_unidad_basica'][$i];
            }else{
                $idUnidadBasica.= ",".$valForm['id_unidad_basica'][$i];
            }

        }

	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
				$valFormArt['txtCodigoArticulo'],
				$valFormArt['txtDescripcionBusq'],
				$valFormArt['lstMarcaBusq'],
				$valFormArt['lstTipoArticuloBusq'],
				$valFormArt['lstSeccionBusq'],
				$valFormArt['lstSubSeccionBusq'],
				$idUnidadBasica);
        
	$objResponse->script("xajax_listadoArticulos('0','','',".$_SESSION['idEmpresaUsuarioSysGts']." + '|".$valBusq."')");

	return $objResponse;
    }

    function listadoArticulos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_empresa.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_empresa.codigo_articulo REGEXP %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_empresa.id_articulo = %s OR vw_iv_articulos_empresa.descripcion LIKE %s)",
			valTpDato($valCadBusq[2],"int"),
			valTpDato("%".$valCadBusq[2]."%","text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_empresa.id_marca = %s", valTpDato($valCadBusq[3],"int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_empresa.id_tipo_articulo = %s", valTpDato($valCadBusq[4],"int"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(SELECT id_seccion FROM iv_subsecciones WHERE id_subseccion = vw_iv_articulos_empresa.id_subseccion) = %s", valTpDato($valCadBusq[5],"int"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_articulos_empresa.id_subseccion = %s", valTpDato($valCadBusq[6],"text"));
	}
	
	
	
	$query = sprintf("
		SELECT
                    vw_iv_articulos_empresa.id_articulo,
                    vw_iv_articulos_empresa.codigo_articulo,
                    vw_iv_articulos_empresa.descripcion,

                    vw_iv_articulos_empresa.id_marca,
                    (SELECT iv_marcas.marca FROM iv_marcas WHERE iv_marcas.id_marca= vw_iv_articulos_empresa.id_marca) AS marca,

                    vw_iv_articulos_empresa.id_tipo_articulo,
                    (SELECT iv_tipos_articulos.descripcion FROM iv_tipos_articulos WHERE iv_tipos_articulos.id_tipo_articulo= vw_iv_articulos_empresa.id_tipo_articulo) AS tipo_articulo,

                    (SELECT id_seccion FROM iv_subsecciones WHERE iv_subsecciones.id_subseccion= vw_iv_articulos_empresa.id_subseccion) AS id_seccion,
                    (SELECT descripcion FROM iv_secciones WHERE iv_secciones.id_seccion= (SELECT id_seccion FROM iv_subsecciones WHERE iv_subsecciones.id_subseccion= vw_iv_articulos_empresa.id_subseccion)) AS descripcion_seccion,

                    vw_iv_articulos_empresa.id_subseccion,
                    (SELECT descripcion FROM iv_subsecciones WHERE iv_subsecciones.id_subseccion= vw_iv_articulos_empresa.id_subseccion) AS descripcion_subseccion

		FROM
                    vw_iv_articulos_empresa
		");
		
		
		
	$query .= $sqlBusq;	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
        
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error());
	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		if (!$rs) return $objResponse->alert(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTableIni .= "<table width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "12%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("C&oacute;digo"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "40%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "8%", $pageNum, "marca", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Marca"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "10%", $pageNum, "tipo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo Articulo"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "19%", $pageNum, "descripcion_seccion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Secci&oacute;n"));
		$htmlTh .= ordenarCampo("xajax_listadoArticulos", "15%", $pageNum, "descripcion_subseccion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Subsecci&oacute;n"));
	
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";
		
		$contFila ++;
		
		$srcIcono = "../img/iconos/ico_aceptar.gif";
		
		$htmlTb.= "<tr class=\"".$clase."\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"agregar_articulo('".$row['id_articulo']."');\" title=\"Seleccionar Articulo\"><img src=\"".$srcIcono."\"/></button>"."</td>";
			$htmlTb .= "<td>".elimCaracter($row['codigo_articulo'], ";")."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['marca'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_articulo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_seccion'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_subseccion'])."</td>";
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf .= "<tr class=\"tituloColumna\" >";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";

					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s)\">",
						"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
					for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
							$htmlTf.="<option value=\"".$nroPag."\"";
							if ($pageNum == $nroPag) {
								$htmlTf.="selected=\"selected\"";
							}
							$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
					}
					$htmlTf.="</select>";

				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulos(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";
	
	if (!($totalRows > 0)) {
		$htmlTb .= "<td colspan=\"12\" class=\"divMsjError\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	
	$objResponse->assign("tdListadoArticulos","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	return $objResponse;
    }

    function cargaLstBusqMo() {
	$objResponse= new xajaxResponse();

	$querySeccion= sprintf("SELECT * FROM sa_seccion ORDER BY sa_seccion.descripcion_seccion").$sqlBusq;
	$rsSeccion= mysql_query($querySeccion);
	if (!$rsSeccion) return $objResponse->alert(mysql_error());

	$html= "<select id=\"lstSeccionTemp\" name=\"lstSeccionTemp\" onchange=\"xajax_cargaLstSubSeccionesMo(this.value); xajax_buscarTempario(xajax.getFormValues('frmBusMo'), xajax.getFormValues('formulario2'));\">";
	$html.= "   <option value=\"-1\">Todos...</option>";

	while ($rowSeccion = mysql_fetch_assoc($rsSeccion)) {
            $html .= "<option value=\"".$rowSeccion['id_seccion']."\">".utf8_encode($rowSeccion['descripcion_seccion'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdListSeccionTemp","innerHTML",$html);

	return $objResponse;
    }

    function cargaLstSubSeccionesMo($idSeccion) {
	$objResponse = new xajaxResponse();

	$querySubSeccion= sprintf("SELECT * FROM sa_subseccion WHERE sa_subseccion.id_seccion = %s 
            ORDER BY sa_subseccion.descripcion_subseccion", valTpDato($idSeccion,"int"));

	$rsSubSeccion= mysql_query($querySubSeccion) or die(mysql_error().$querySubSeccion);

	$html= "<select id=\"lstSubseccionTemp\" name=\"lstSubseccionTemp\" onchange=\"xajax_buscarTempario(xajax.getFormValues('frmBusMo'), xajax.getFormValues('formulario2'));\">";
        $html.= "   <option value=\"-1\">Todos...</option>";

	while ($rowSubSeccion = mysql_fetch_assoc($rsSubSeccion)) {
            $html .= "<option value=\"".$rowSubSeccion['id_subseccion']."\">".utf8_encode($rowSubSeccion['descripcion_subseccion'])."</option>";
	}
	$html.= "</select>";
	$objResponse->assign("tdListSubseccionTemp","innerHTML",$html);

	return $objResponse;
    }

    function buscarTempario($valFormMo, $valForm){
	$objResponse = new xajaxResponse();
        
        $valBusq = sprintf("%s|%s|%s",
		$valFormMo['txtDescripcionBusqTemp'],
		$valFormMo['lstSeccionTemp'],
		$valFormMo['lstSubseccionTemp']);
	
	$objResponse->script("xajax_listado_tempario('0','','','".$_SESSION['idEmpresaUsuarioSysGts']."|".$valBusq."');");
	return $objResponse;	
    }

    function listado_tempario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 6, $totalRows = NULL){
	$objResponse = new xajaxResponse();
	$valCadBusq = explode("|", $valBusq);

	$startRow = $pageNum * $maxRows;
        
        //si NO posee tempario unico, filtro por empresa   - Proviene del ac general
        if(!temparioUnico($valCadBusq[0])){//id empresa, (NEGANDO) devuelve 1 si tiene parametro unico, null sino 
            //no se filtra por unidad, sino que muestra todos 
            if (strlen($valCadBusq[0]) > 0){
                $sqlBusq = sprintf(" WHERE sa_v_tempario.id_empresa = %s",
                            valTpDato($valCadBusq[0],"int"),
                            valTpDato($valCadBusq[1],"int"));
            }
            
        }else{//si trabaja con unico, traer las de la empresa y el padre
                //
                //regresa un array con todas las empresa de dicha empresa padre hermanos y asi misma
                $arrayEmpresas = empresasVinculadas($valCadBusq[0]);//proviene de ac iv general

                $empresasPadreHijos = implode(",",$arrayEmpresas); 
                $sqlBusq = sprintf(" WHERE sa_v_tempario.id_empresa IN (%s)",
                                    $empresasPadreHijos);
            }
        

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
            $sqlBusq .= $cond.sprintf(" sa_v_tempario.codigo_tempario LIKE %s
                        OR sa_v_tempario.descripcion_tempario LIKE %s)",
                        valTpDato("%".$valCadBusq[1]."%","text"),
                        valTpDato("%".$valCadBusq[1]."%","text"));
	}

	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
            $sqlBusq .= $cond.sprintf(" sa_v_tempario.id_seccion = %s)",
                        valTpDato($valCadBusq[2],"text"));
	}

	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
            $sqlBusq .= $cond.sprintf(" sa_v_tempario.id_subseccion = %s)",
                        valTpDato($valCadBusq[3],"text"));
	}

	$query = sprintf("SELECT
                pg_empresa.nombre_empresa,
		sa_v_tempario.id_tempario,
		sa_v_tempario.codigo_tempario,
		sa_v_tempario.descripcion_tempario,
		sa_v_tempario.id_modo,
                sa_v_tempario.id_seccion,
                sa_v_tempario.descripcion_seccion,
		sa_v_tempario.id_subseccion,
                sa_v_tempario.descripcion_subseccion
		FROM sa_v_tempario
                LEFT JOIN pg_empresa ON pg_empresa.id_empresa = sa_v_tempario.id_empresa").$sqlBusq;
		
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

    $rsLimit = mysql_query($queryLimit) or die(mysql_error().$queryLimit);

	if ($totalRows == NULL) {
		$rs = mysql_query($query) or die(mysql_error().$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTableIni .= "<table width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">
				<td></td>";
            
					
			$htmlTh .= ordenarCampo("xajax_listado_tempario", "10%", $pageNum, "codigo_tempario", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("C&oacute;digo"));
			$htmlTh .= ordenarCampo("xajax_listado_tempario", "42%", $pageNum, "descripcion_tempario", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Descripci&oacute;n"));
			$htmlTh .= ordenarCampo("xajax_listado_tempario", "20%", $pageNum, "descripcion_seccion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Secci&oacute;n"));
			$htmlTh .= ordenarCampo("xajax_listado_tempario", "20%", $pageNum, "descripcion_subseccion", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Subsecci&oacute;n"));
			$htmlTh .= ordenarCampo("xajax_listado_tempario", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empresa"));
		
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
            $clase = ($clase == "trResaltar4") ? "trResaltar5" : "trResaltar4";

            $srcIcono = "../img/iconos/ico_aceptar.gif";

            $htmlTb.= "<tr class=\"".$clase."\">";
            $htmlTb .= "    <td><button type=\"button\" onclick=\"agregar_tempario(".$row['id_tempario'].");\" title=\"Seleccionar Tempario\"><img src=\"".$srcIcono."\"/></button></td>";
            $htmlTb .= "    <td align='center'>".$row['codigo_tempario']."</td>";
            $htmlTb .= "    <td align='center'>".utf8_encode($row['descripcion_tempario'])."</td>";
            $htmlTb .= "    <td align='center'>".utf8_encode($row['descripcion_seccion'])."</td>";
            $htmlTb .= "    <td align='center'>".utf8_encode($row['descripcion_subseccion'])."</td>";
            $htmlTb .= "    <td align='center'>".utf8_encode($row['nombre_empresa'])."</td>";
            $htmlTb .= "</tr>";
	}

	$htmlTf .= "<tr class=\"tituloColumna\" >";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
			$htmlTf .= "<table cellpadding=\"0\" cellspacing=\"0\">";
			$htmlTf .= "<tr>";
				$htmlTf .= "<td align=\"center\">";
					$htmlTf .= "<input name=\"valBusq\" id=\"valBusq\" type=\"hidden\" value=\"".$valBusq."\"/>";
					$htmlTf .= "<input name=\"campOrd\" id=\"campOrd\" type=\"hidden\" value=\"".$campOrd."\"/>";
					$htmlTf .= "<input name=\"tpOrd\" id=\"tpOrd\" type=\"hidden\" value=\"".$tpOrd."\"/>";
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario(%s,'%s','%s','%s',%s);\">%s</a>",
						0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum > 0) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario(%s,'%s','%s','%s',%s);\">%s</a>",
						max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"90px\">";

					$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listado_tempario(%s,'%s','%s','%s',%s)\">",
						"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
					for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
							$htmlTf.="<option value=\"".$nroPag."\"";
							if ($pageNum == $nroPag) {
								$htmlTf.="selected=\"selected\"";
							}
							$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
					}
					$htmlTf.="</select>";

				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario(%s,'%s','%s','%s',%s);\">%s</a>",
						min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\" width=\"35px\">";
				if ($pageNum < $totalPages) {
					$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listado_tempario(%s,'%s','%s','%s',%s);\">%s</a>",
						$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
				}
				$htmlTf .= "</td>";
				$htmlTf .= "<td align=\"center\"></td>";
			$htmlTf .= "</tr>";
			$htmlTf .= "</table>";
		$htmlTf .= "</td>";
	$htmlTf .= "</tr>";
	
	$htmlTableFin .= "</table>";

	if (!($totalRows > 0))
		$htmlTb = "<td colspan=\"5\" class=\"divMsjError\">No se encontraron registros.</td>";


	$objResponse->assign("tdListadoTemparioPorUnidad","innerHTML",$htmlTableIni.$htmlTh.$htmlTb.$htmlTf.$htmlTableFin);
	return $objResponse;
    }
	
	
	
	
	function actualizar_repuestos($form){
	$objResponse = new xajaxResponse();
	
	if ($form['hddIdPaquete']==0)
		$cadenaFiltro = sprintf("");
	else
		$cadenaFiltro = sprintf(" WHERE id_paquete = %s",$form['hddIdPaquete']);
	
	$queryRepuestos = sprintf("SELECT * FROM sa_paquete_repuestos".$cadenaFiltro);
	
	$rsRepuestos = mysql_query($queryRepuestos) or die(mysql_error());
	if (!$rsRepuestos) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	while ($rowRepuestos = mysql_fetch_assoc($rsRepuestos)) {
	
		$query = sprintf("SELECT precio FROM iv_articulos_precios WHERE id_articulo = %s AND id_precio = %s",$rowRepuestos['id_articulo'],$form['hddIdPrecio']); 
		$rs = mysql_query($query) or die(mysql_error());
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		
		$queryUpdate = sprintf("UPDATE sa_paquete_repuestos SET precio = %s WHERE id_paquete = %s AND id_articulo = %s",$row['precio'],$rowRepuestos['id_paquete'],$rowRepuestos['id_articulo']);
		$rsUpdate = mysql_query($queryUpdate) or die(mysql_error());
		if (!$rsUpdate) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
		
	
	}
	
	$objResponse->alert("Precios de Repuestos Actualizados Correctamente");
	$objResponse->assign("hddIdPrecio","value",1);
	
	return $objResponse;
	}
	
	
	function actualizar_mo($id_paquete){
	$objResponse = new xajaxResponse();
	
	if ($id_paquete==0)
		$cadenaFiltro = sprintf("");
	else
		$cadenaFiltro = sprintf(" WHERE id_paquete = %s",$id_paquete);
	
	$queryTempario = sprintf("SELECT * FROM sa_paq_tempario".$cadenaFiltro);
	$rsTempario = mysql_query($queryTempario) or die(mysql_error());
	if (!$rsTempario) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	while ($rowTempario = mysql_fetch_assoc($rsTempario)) {
	
		$query = sprintf("SELECT precio FROM sa_tempario WHERE id_tempario = %s",$rowTempario['id_tempario']);
		$rs = mysql_query($query) or die(mysql_error());
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		
		$queryUpdate = sprintf("UPDATE sa_paq_tempario SET costo = %s WHERE id_paquete = %s AND id_tempario = %s",$row['precio'],$rowTempario['id_paquete'],$rowTempario['id_tempario']);
		$rsUpdate = mysql_query($queryUpdate) or die(mysql_error());
		if (!$rsUpdate) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		
			
	
	}
	
	$objResponse->alert("Precios de Mano de Obra Actualizados Correctamente");
	return $objResponse;
	}
	
	
	
	
	function comboPreciosRpto($paq){
	$objResponse = new xajaxResponse();
	
	$objResponse->assign("hddIdPaquete","value",$paq);
	
	$queryPrecioRpto = "SELECT * FROM pg_precios WHERE id_precio <=5";
	$rsPrecioRpto = mysql_query($queryPrecioRpto);
	
	$html = "<select id=\"selPrecioRpto\" name=\"selPrecioRpto\" onchange=\"xajax_asignarIdPrecio(this.value)\">";
	while ($rowPrecioRpto = mysql_fetch_assoc($rsPrecioRpto)) {
		$html .= "<option value=\"".$rowPrecioRpto['id_precio']."\">".htmlentities($rowPrecioRpto['descripcion_precio'])."</option>";
	}
	$html .= "</select>";
		
		$objResponse->assign("tdPrecioRpto","innerHTML",$html);
	

	return $objResponse;
}


function asignarIdPrecio($idPrecio){
	$objResponse = new xajaxResponse();
	
	$query = "SELECT * FROM pg_precios WHERE id_precio = '".$idPrecio."'";
	$rs = mysql_query($query) or die(mysql_error());
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLINE: ".__LINE__);
	
	$row = mysql_fetch_array($rs);

	$objResponse->assign("hddIdPrecio","value",$row['id_precio']);
	
	
														
	return $objResponse;
}






 	$xajax->register(XAJAX_FUNCTION,"comboPreciosRpto");
	$xajax->register(XAJAX_FUNCTION,"asignarIdPrecio");
    $xajax->register(XAJAX_FUNCTION,"buscarUnidad");
    $xajax->register(XAJAX_FUNCTION,"listadoUnidades");
    $xajax->register(XAJAX_FUNCTION,"cargaLstBusq");
    $xajax->register(XAJAX_FUNCTION,"cargaLstSubSecciones");
    $xajax->register(XAJAX_FUNCTION,"buscarArticulo");
    $xajax->register(XAJAX_FUNCTION,"listadoArticulos");
    $xajax->register(XAJAX_FUNCTION,"cargaLstBusqMo");
    $xajax->register(XAJAX_FUNCTION,"cargaLstSubSeccionesMo");
    $xajax->register(XAJAX_FUNCTION,"buscarTempario");
    $xajax->register(XAJAX_FUNCTION,"listado_tempario");
	$xajax->register(XAJAX_FUNCTION,"actualizar_repuestos");
	$xajax->register(XAJAX_FUNCTION,"actualizar_mo");
?>
