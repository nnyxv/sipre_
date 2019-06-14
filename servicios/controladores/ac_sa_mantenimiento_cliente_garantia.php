<?php
//error_reporting(E_ALL);
    function buscar($valForm) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s", $valForm['txtCriterio']);

        $objResponse->loadCommands(listadoClienteGarantia(0, 'nombre','ASC', $valBusq));

	return $objResponse;
    }

    function buscarCliente($valForm) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s", $valForm['txtCriterio']);

        $objResponse->loadCommands(listadoCliente(0, 'nombre','ASC', $valBusq));

	return $objResponse;
    }

    function listadoClienteGarantia($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse= new xajaxResponse();
	
	global $spanCI;
	global $spanRIF;

        if (!xvalidaAcceso($objResponse,PAGE_PRIV)){
            $objResponse->assign("tdListadoClienteGarantia","innerHTML","Acceso Denegado");
            return $objResponse;
        }

	$valCadBusq= explode("|", $valBusq);
	$startRow= $pageNum * $maxRows;

      //  $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	//$sqlBusq .= $cond.sprintf("sa_cliente_garantia.id_cliente= cj_cc_cliente.id");
/*
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("scg.estatus= %s AND sa_v_empresa_sucursal.id_empresa = %s", valTpDato(0, "int"), 
				valTpDato($_SESSION['idEmpresaUsuarioSysGts'],"int"));*/

	if($valCadBusq[0] != "-1" && $valCadBusq[0] != ""){
            $cond= (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";

            $sqlBusq.= $cond."/* sa_v_empresa_sucursal.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."' AND */ 
			(cj_cc_cliente.nombre LIKE '%".$valCadBusq[0]."%' OR cj_cc_cliente.apellido LIKE '%".$valCadBusq[0]."%' OR cj_cc_cliente.ci LIKE '%".$valCadBusq[0]."%')";
	}else{
		//$sqlBusq = "WHERE sa_v_empresa_sucursal.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']." AND estatus = 0 GROUP BY scg.id_cliente_garantia ";
		}

        $query = sprintf("SELECT
                                  sa_cliente_garantia.*,
                                  cj_cc_cliente.*,
                                  CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_completo,
								  sa_v_empresa_sucursal.nombre_empresa_sucursal			  
                          FROM
                                  sa_cliente_garantia
								  LEFT JOIN cj_cc_cliente ON sa_cliente_garantia.id_cliente= cj_cc_cliente.id
								  LEFT JOIN sa_v_empresa_sucursal ON sa_cliente_garantia.id_empresa = sa_v_empresa_sucursal.id_empresa
						  	  %s 
							  
							  ", $sqlBusq);

        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert("ERROR: ".mysql_error()." \n LINEA: ".__LINE__." \n QUERY: ".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert("ERROR: ".mysql_error()." \n LINEA: ".__LINE__." \n QUERY: ".$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= ordenarCampo("xajax_listadoClienteGarantia", "15%", $pageNum, "ci", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI." / ".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listadoClienteGarantia", "45%", $pageNum, "nombre_completo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Cliente");
                $htmlTh .= ordenarCampo("xajax_listadoClienteGarantia", "10%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo");
                $htmlTh .= ordenarCampo("xajax_listadoClienteGarantia", "10%", $pageNum, "status", $campOrd, $tpOrd, $valBusq, $maxRows, "Estatus");
				$htmlTh .= ordenarCampo("xajax_listadoClienteGarantia", "30%", $pageNum, "nombre_empresa_sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= "<td class='noprint' colspan='3'></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";

		$contFila ++;

		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";

                $htmlTb .= "<td align=\"center\">".$row['lci']."-".$row['ci']."</td>";
                $htmlTb .= "<td align=\"left\">".htmlentities($row['nombre_completo'])."</td>";
                $htmlTb .= "<td align=\"center\">".htmlentities($row['tipo'])."</td>";
                $htmlTb .= "<td align=\"center\">".htmlentities($row['status'])."</td>";
				$htmlTb .= "<td align=\"center\">".$row['nombre_empresa_sucursal']."</td>";
                $htmlTb .= "<td align=\"center\" class='noprint'><img class=\"puntero\" onclick=\"xajax_buscarClienteGarantia(3, ".$row['id_cliente_garantia'].")\" src=\"../img/iconos/ico_view.png\"/></td>";
                $htmlTb .= "<td align=\"center\" class='noprint'><img class=\"puntero\" onclick=\"xajax_buscarClienteGarantia(2, ".$row['id_cliente_garantia'].")\" src=\"../img/iconos/ico_edit.png\"/></td>";
                $htmlTb .= "<td align=\"center\" class='noprint'><img class=\"puntero\" onclick=\"xajax_eliminar('".$row['id_cliente_garantia']."');\" src=\"../img/iconos/ico_delete.png\"/></td>";
                $htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClienteGarantia(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClienteGarantia(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoClienteGarantia(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClienteGarantia(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoClienteGarantia(%s,'%s','%s','%s',%s);\">%s</a>",
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

	$objResponse->assign("tdListadoClienteGarantia","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

        return $objResponse;
    }

    function listadoCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL){
        $objResponse= new xajaxResponse();

	global $spanCI;	
	global $spanRIF;

	$valCadBusq= explode("|", $valBusq);
	$startRow= $pageNum * $maxRows;

        if($valCadBusq[0] != "-1" && $valCadBusq[0] != ""){
            $cond= (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";

            $sqlBusq.= $cond."/* sa_v_empresa_sucursal.id_empresa = '".$_SESSION['idEmpresaUsuarioSysGts']."' AND */ 
			(nombre LIKE '%".$valCadBusq[0]."%' OR apellido LIKE '%".$valCadBusq[0]."%' OR ci LIKE '%".$valCadBusq[0]."%'  )";
	}else{
	//	$sqlBusq = "WHERE sa_v_empresa_sucursal.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']." ";
		}

        $query = "SELECT
                        *,
                        CONCAT_WS(' ', nombre, apellido) AS nombre_completo
                    FROM
                        cj_cc_cliente
					
					 ".$sqlBusq;

        $sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert("ERROR: ".mysql_error()." \n LINEA: ".__LINE__." \n QUERY: ".$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert("ERROR: ".mysql_error()." \n LINEA: ".__LINE__." \n QUERY: ".$query);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
                $htmlTh .= "<td class='noprint'>".$totalRows."</td>";
		$htmlTh .= ordenarCampo("xajax_listadoCliente", "35%", $pageNum, "ci", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI." / ".$spanRIF);
		$htmlTh .= ordenarCampo("xajax_listadoCliente", "65%", $pageNum, "nombre_completo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Cliente");
		//$htmlTh .= ordenarCampo("xajax_listadoCliente", "25%", $pageNum, "nombre_empresa_sucursal", $camOrd, $tpOrd, $valBusq, $maxRows, "Empresa del Cliente");
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";

		$contFila ++;

		$htmlTb .= "<tr class=\"".$clase."\" onmouseover=\"$(this).className = 'trSobre';\" onmouseout=\"$(this).className = '".$clase."';\">";

                $htmlTb .= "<td align=\"center\" class='noprint'>
                                <button type=\"button\" onclick=\"asignarCliente('".$row['id']."', '".$row['lci']."-".$row['ci']."', '".htmlentities($row['nombre_completo'])."', '".$row['tipo']."', '".$row['status']."')\" title=\"Seleccionar Cliente\">
                                    <img src=\"../img/iconos/ico_aceptar.gif\"/>
                                </button>
                            </td>";
                $htmlTb .= "<td align=\"center\">".$row['lci']."-".$row['ci']."</td>";
                $htmlTb .= "<td align=\"left\">".htmlentities($row['nombre_completo'])."</td>";
				//$htmlTb .= "<td align=\"left\">".htmlentities($row['nombre_empresa_sucursal'])."</td>";
                $htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"14\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoCliente(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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

		$objResponse->assign("tdListadoCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
        $objResponse->script("if($('divFlotanteCliente').style.display == 'none'){
                                $('divFlotanteCliente').style.display = '';
                                centrarDiv($('divFlotanteCliente'));
                              }");

        return $objResponse;

    }

    function insertClienteGarantia($form){
        $objResponse = new xajaxResponse();

        if (!xvalidaAcceso($objResponse,PAGE_PRIV,insertar)){
			return $objResponse;
		}
		
		//verificacion si ya existe como cliente de garantia
		$query_busqueda_cliente = sprintf("SELECT * FROM sa_cliente_garantia WHERE id_cliente = %s AND estatus = 0", $form['id_cliente']);
		$busqueda_cliente = mysql_query($query_busqueda_cliente);
		if (!$busqueda_cliente) return $objResponse->alert("ERROR: ".mysql_error()." \n LINEA: ".__LINE__." \n QUERY: ".$query_busqueda_cliente);

		$encontrado_repetido = mysql_num_rows($busqueda_cliente);
		if($encontrado_repetido){
			return $objResponse->alert("Ya existe ese cliente");
		}
		

        $query= sprintf("INSERT INTO sa_cliente_garantia
                                (id_cliente, estatus, id_usuario_creador, id_empresa)
                            VALUES
                                (%s, %s, %s, %s)",
                            valTpDato($form['id_cliente'],"int"),
                            valTpDato(0, "int"),
                            valTpDato($_SESSION['idUsuarioSysGts'], "int"),
							valTpDato($_SESSION['idEmpresaUsuarioSysGts'], "int"));

        $result = mysql_query($query);
        if (!$result) return $objResponse->alert(mysql_error()." ".__LINE__." ".$query);

        $objResponse->alert("Cliente Garantia ingresado con exito");

        $objResponse->script("$('divFlotante').style.display='none';");
        $objResponse->loadCommands(listadoClienteGarantia(0, 'nombre','ASC', ''));
        return $objResponse;
    }

    function updateClienteGarantia($form){
        $objResponse = new xajaxResponse();

        /*if (!xvalidaAcceso($objResponse,"sa_dias_habiles",editar)){
		return $objResponse;
	}*/

        $query= sprintf("UPDATE sa_cliente_garantia SET
                                id_cliente= %s,
                                id_usuario_modificar= %s
                         WHERE
                                id_cliente_garantia = %s",
                            valTpDato($form['id_cliente'],"int"),
                            valTpDato($_SESSION['idUsuarioSysGts'], "int"),
                            valTpDato($form['id_cliente_garantia'], "int"));

        $result = mysql_query($query);
        if (!$result) return $objResponse->alert(mysql_error()." ".__LINE__." ".$query);

        $objResponse->alert("Cliente Garantia actualizado con exito");

        $objResponse->script("$('divFlotante').style.display='none';");
        $objResponse->loadCommands(listadoClienteGarantia(0, 'nombre','ASC', ''));
        return $objResponse;
    }

    function buscarClienteGarantia($acc, $id){
        $objResponse = new xajaxResponse();
		
		if ($acc == 2)
			if (!xvalidaAcceso($objResponse,PAGE_PRIV,'editar')){
					return $objResponse;
				}
		
        $query = sprintf("SELECT
                                  scg.*,
                                  ccc.*,
                                  CONCAT_WS(' ', ccc.nombre, ccc.apellido) AS nombre_completo
                          FROM
                                  sa_cliente_garantia scg, cj_cc_cliente ccc
                          WHERE
                                  id_cliente_garantia= %s
                                  AND scg.id_cliente= ccc.id
                                  /* AND scg.estatus=0 */  ", $id);

        $rs= mysql_query($query);
        if (!$rs) return $objResponse->alert("ERROR: ".mysql_error()." \n LINEA: ".__LINE__." \n QUERY: ".$query);
	$row= mysql_fetch_assoc($rs);

        $objResponse->script("abrirVentana('".$acc."');");
        $objResponse->script("$('id_cliente_garantia').value= '".$row['id_cliente_garantia']."';");
        $objResponse->script("$('id_cliente').value= '".$row['id_cliente']."';");
        $objResponse->script("$('txtIdentificacion').value= '".$row['lci']."-".$row['ci']."';");
        $objResponse->script("$('txtNombreCliente').value= '".$row['nombre_completo']."';");
        $objResponse->script("$('txtTipoCliente').value= '".$row['tipo']."';");
        $objResponse->script("$('txtEstatusCliente').value= '".$row['status']."';");

        if($acc == 2){
            $objResponse->script("$('buttonIdentificacion').disabled= false;");
        }else if($acc == 3){
            $objResponse->script("$('buttonIdentificacion').disabled= true;");
        }

        return $objResponse;
    }

    function deleteClienteGarantia($id){
        $objResponse = new xajaxResponse();

        /*if (!xvalidaAcceso($objResponse,"sa_dias_habiles",eliminar)){
		return $objResponse;
	}*/

        $query= sprintf("UPDATE sa_cliente_garantia SET
                                estatus= 1,
                                id_usuario_eliminar= %s
                         WHERE
                                id_cliente_garantia = %s",
                            valTpDato($_SESSION['idUsuarioSysGts'], "int"),
                            valTpDato($id, "int"));

        $result = mysql_query($query);
        if (!$result) return $objResponse->alert(mysql_error()." ".__LINE__." ".$query);

        $objResponse->alert("Cliente Garantia eliminado con exito");
        
        $objResponse->loadCommands(listadoClienteGarantia(0, 'nombre','ASC', ''));
        return $objResponse;
    }

	function nuevoCliente($acc){
		$objResponse = new xajaxResponse();
		
		if (!xvalidaAcceso($objResponse,PAGE_PRIV,'insertar')){
				return $objResponse;
			}
		else{
			$objResponse->script("abrirVentana(".$acc.");");
		}
		
		return $objResponse;
	}

	function eliminar($idCliente){
		$objResponse = new xajaxResponse();
		
		if (!xvalidaAcceso($objResponse,PAGE_PRIV,'eliminar')){
				return $objResponse;
			}
		else{
			$objResponse->script("eliminar(".$idCliente.");");
		}
		
		return $objResponse;
	}

    $xajax->register(XAJAX_FUNCTION,"buscar");
    $xajax->register(XAJAX_FUNCTION,"buscarCliente");
    $xajax->register(XAJAX_FUNCTION,"listadoClienteGarantia");
    $xajax->register(XAJAX_FUNCTION,"listadoCliente");
    $xajax->register(XAJAX_FUNCTION,"insertClienteGarantia");
    $xajax->register(XAJAX_FUNCTION,"updateClienteGarantia");
    $xajax->register(XAJAX_FUNCTION,"buscarClienteGarantia");
    $xajax->register(XAJAX_FUNCTION,"deleteClienteGarantia");
    $xajax->register(XAJAX_FUNCTION,"nuevoCliente");
    $xajax->register(XAJAX_FUNCTION,"eliminar");
?>
