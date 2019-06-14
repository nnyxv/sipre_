<?php
    function cargaLstEmpresa($selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT DISTINCT id_empresa, nombre_empresa FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
		valTpDato($_SESSION['idUsuarioSysGts'], "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error());
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

    function cargaLstEstatus($selId = "") {
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM sa_estado_solicitud ORDER BY descripcion_estado_solicitud");
	$rs = mysql_query($query);

	if (!$rs) return $objResponse->alert(mysql_error());

	$html = "<select id=\"lstEstatus\" name=\"lstEstatus\" onchange=\"$('btnBuscar').click();\">";
		$html .= "<option value=\"\">Todos...</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_estado_solicitud'])
			$selected = "selected='selected'";

		$html .= "<option ".$selected." value=\"".$row['id_estado_solicitud']."\">".utf8_encode($row['descripcion_estado_solicitud'])."</option>";

	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstatus","innerHTML",$html);

	return $objResponse;
    }

    function buscar($valForm) {
	$objResponse = new xajaxResponse();

	$valBusq = sprintf("%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['lstEstatus'],
		$valForm['txtCriterio']);

        $objResponse->loadCommands(listadoSolicitudRepuestos(0, 'numero_solicitud','DESC', $valBusq));

	return $objResponse;
    }

    function listadoSolicitudRepuestos($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	//$sqlBusq = "WHERE sr.id_empresa = ".$_SESSION['idEmpresaUsuarioSysGts']."";
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sr.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}

	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("sr.estado_solicitud= %s",
			valTpDato($valCadBusq[1], "int"));
	}
        
        if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND (" : " WHERE (";
		$sqlBusq .= $cond.sprintf("sr.numero_solicitud LIKE %s
			OR numero_orden LIKE %s OR 
			
			#nombre del cliente para busqueda:
			(SELECT
			  IFNULL(
				(SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id= r.id_cliente_pago),
				(SELECT
				  (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id= ci.id_cliente_contacto)
				FROM
				  sa_cita ci
				WHERE
				  ci.id_cita= r.id_cita)
			  )
			FROM
			  sa_recepcion r
			WHERE
			  r.id_recepcion= (SELECT o.id_recepcion FROM sa_orden o WHERE o.id_orden= sr.id_orden)
			)			 
			
			LIKE %s)",
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"),
			valTpDato("%".$valCadBusq[2]."%", "text"));
	}

	$query = sprintf("SELECT
							numero_orden,
                            sr.id_solicitud,
							sr.numero_solicitud,
                            sr.id_orden,
                            sr.estado_solicitud,
                            (SELECT es.descripcion_estado_solicitud FROM sa_estado_solicitud es WHERE es.id_estado_solicitud= sr.estado_solicitud) AS estatus,
                            (SELECT e.nombre_empresa FROM pg_empresa e WHERE e.id_empresa= sr.id_empresa) AS empresa,
                            (SELECT t.nombre_tipo_orden FROM sa_tipo_orden t WHERE t.id_tipo_orden=
                              (SELECT o.id_tipo_orden FROM sa_orden o WHERE o.id_orden= sr.id_orden)) AS tipo_orden,
                            (SELECT
                              IFNULL(
                                (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id= r.id_cliente_pago),
                                (SELECT
                                  (SELECT CONCAT_WS(' ', cl.nombre, cl.apellido) FROM cj_cc_cliente cl WHERE cl.id= ci.id_cliente_contacto)
                                FROM
                                  sa_cita ci
                                WHERE
                                  ci.id_cita= r.id_cita)
                              )
                            FROM
                              sa_recepcion r
                            WHERE
                              r.id_recepcion= (SELECT o.id_recepcion FROM sa_orden o WHERE o.id_orden= sr.id_orden)
                            ) AS cliente
                        FROM
                            sa_solicitud_repuestos sr
						LEFT JOIN sa_orden ON sr.id_orden = sa_orden.id_orden
						 %s", $sqlBusq);

	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);

	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error().$queryLimit);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error());
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
                $htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudRepuestos", "15%", $pageNum, "empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudRepuestos", "10%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "N&ordm; de Solicitud");
                $htmlTh .= ordenarCampo("xajax_listadoSolicitudRepuestos", "10%", $pageNum, "numero_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "N&ordm; de Orden");
                $htmlTh .= ordenarCampo("xajax_listadoSolicitudRepuestos", "35%", $pageNum, "cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
                $htmlTh .= ordenarCampo("xajax_listadoSolicitudRepuestos", "15%", $pageNum, "estatus", $campOrd, $tpOrd, $valBusq, $maxRows, "Estatus Solicitud");
                $htmlTh .= ordenarCampo("xajax_listadoSolicitudRepuestos", "15%", $pageNum, "tipo_orden", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Orden");
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";

	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";

                if ($row['estado_solicitud'] == 1)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_azul.gif\" title=\"Abierta\"/>";
		else if ($row['estado_solicitud'] == 2)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo.gif\" title=\"Aprobada\"/>";
		else if ($row['estado_solicitud'] == 3)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja.gif\" title=\"Despachada\"/>";
		else if ($row['estado_solicitud'] == 4)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_gris.gif\" title=\"Devuelto\"/>";
		else if ($row['estado_solicitud'] == 5)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Facturada\"/>";
		else if ($row['estado_solicitud'] == 6)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Anulada\"/>";
		else if ($row['estado_solicitud'] == 7)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_amarillo_parcial.gif\" title=\"Aprobada Parcial\"/>";
		else if ($row['estado_solicitud'] == 8)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_naranja_parcial.gif\" title=\"Despachada Parcial\"/>";
		else if ($row['estado_solicitud'] == 9)
			$imgEstatusPedido = "<img src=\"../img/iconos/ico_gris_parcial.gif\" title=\"Devolucion Parcial\"/>";

		$contFila ++;
                $htmlTb .= "<tr align=\"left\" class=\"".$clase."\" onmouseover=\"this.className='trSobre';\" onmouseout=\"this.className='".$clase."';\" height=\"24\">";
                $htmlTb .= "<td align=\"center\">".$imgEstatusPedido."</td>";
                $htmlTb .= "<td align=\"center\">".utf8_encode($row['empresa'])."</td>";
                $htmlTb .= "<td align=\"center\" idsolicitudoculta=\"".$row['id_solicitud']."\">".$row['numero_solicitud']."</td>";
                $htmlTb .= "<td align=\"center\" idordenoculta=\"".$row['id_orden']."\">".$row['numero_orden']."</td>";
                $htmlTb .= "<td align=\"left\">".utf8_encode($row['cliente'])."</td>";
                $htmlTb .= "<td align=\"center\">".utf8_encode($row['estatus'])."</td>";
                $htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo_orden'])."</td>";
                $htmlTb .= "<td align=\"center\">
                                <img class=\"puntero\" onclick=\"xajax_verSolicitud(".$row['id_solicitud'].");\" src=\"../img/iconos/ico_view.png\"/></td>";
		$htmlTb .= "</tr>";
	}

	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudRepuestos(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudRepuestos(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoSolicitudRepuestos(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudRepuestos(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudRepuestos(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"8\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}

	$objResponse->assign("tdListadoSolicitud","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);

        return $objResponse;
    }

    function verSolicitud($idSolicitud){
	$objResponse= new xajaxResponse();
	
	$objResponse->script("document.forms['frmDatosGeneralesSolicitud'].reset();");
	
	$querSolicitud= sprintf("SELECT
                                    *,
                                    (SELECT es.descripcion_estado_solicitud FROM sa_estado_solicitud es WHERE es.id_estado_solicitud= estado_solicitud)
                                        AS nombre_estatus,
                                    (SELECT CONCAT_WS(' ', e.nombre_empleado, e.apellido) FROM pg_empleado e WHERE e.id_empleado= id_jefe_taller)
                                        AS nombre_jefe_taller,
                                    (SELECT CONCAT_WS(' ', e.nombre_empleado, e.apellido) FROM pg_empleado e WHERE e.id_empleado= id_jefe_repuesto)
                                        AS nombre_jefe_repuesto,
                                    (SELECT CONCAT_WS(' ', e.nombre_empleado, e.apellido) FROM pg_empleado e WHERE e.id_empleado= id_empleado_recibo)
                                        AS nombre_empleado_recibo,
                                    (SELECT CONCAT_WS(' ', e.nombre_empleado, e.apellido) FROM pg_empleado e WHERE e.id_empleado= id_empleado_devuelto)
                                        AS nombre_empleado_devuelto
                                FROM
                                    sa_solicitud_repuestos
                                WHERE
                                    id_solicitud = %s",
                        valTpDato($idSolicitud, "int"));

	$rsSolicitud= mysql_query($querSolicitud);
	if (!$rsSolicitud) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowSolicitud= mysql_fetch_array($rsSolicitud);

        $queryOrden= sprintf("SELECT
                                    o.*,
                                    date_format(o.tiempo_orden,'%s') AS timpo_orden_formato,
                                    (SELECT t.nombre_tipo_orden FROM sa_tipo_orden t WHERE t.id_tipo_orden= o.id_tipo_orden) AS nombre_tipo_orden,
                                    (SELECT e.nombre_estado FROM sa_estado_orden e WHERE e.id_estado_orden= o.id_estado_orden) AS nombre_estado_orden
                                FROM
                                    sa_orden o
                                WHERE
                                    o.id_orden = %s",
                                    "%d-%m-%Y",
                        valTpDato($rowSolicitud['id_orden'], "int"));

	$rsOrden= mysql_query($queryOrden);
	if (!$rsOrden) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowOrden= mysql_fetch_array($rsOrden);
	
	$queryRecepcion = sprintf("SELECT
                                        *
                                    FROM
                                        vw_sa_vales_recepcion
                                    WHERE
                                        id_recepcion = %s",
                            valTpDato($rowOrden['id_recepcion'], "int"));

	$rsRecepcion= mysql_query($queryRecepcion);
	if (!$rsRecepcion) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowRecepcion= mysql_fetch_array($rsRecepcion);
	
	$queryUnidadBasica= sprintf("SELECT
                                        *
                                    FROM
                                        an_uni_bas
                                    WHERE
                                        id_uni_bas = %s",
                                valTpDato($rowRecepcion['id_uni_bas'], "int"));

	$rsUnidadBasica= mysql_query($queryUnidadBasica);
	if (!$rsUnidadBasica) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowUnidadBasica= mysql_fetch_array($rsUnidadBasica);
	
	$imgFoto= $rowUnidadBasica['imagen_auto'];

	if (!file_exists($rowUnidadBasica['imagen_auto'])){
            $imgFoto = "../".$_SESSION['logoEmpresaSysGts'];
        }
	
	$objResponse->assign("imgUnidad","src",$imgFoto);
	$objResponse->assign("txtNumeroSolicitud","value",$rowSolicitud['numero_solicitud']);
	$objResponse->assign("txtEstadoSolicitud","value",utf8_encode($rowSolicitud['nombre_estatus']));
	$objResponse->assign("txtEmpleadoAprobo","value",utf8_encode($rowSolicitud['nombre_jefe_taller']));
	$objResponse->assign("hddEmpleadoRecRepuestos","value",utf8_encode($rowSolicitud['id_empleado_recibo']));
	$objResponse->assign("txtEmpleadoRecRepuestos","value",utf8_encode($rowSolicitud['nombre_empleado_recibo']));
	$objResponse->assign("txtEmpleadoDespachoRepuestos","value",utf8_encode($rowSolicitud['nombre_jefe_repuesto']));
	$objResponse->assign("hddEmpleadoDevolucionRepuestos","value",utf8_encode($rowSolicitud['id_empleado_devuelto']));
	$objResponse->assign("txtEmpleadoDevolucionRepuestos","value",utf8_encode($rowSolicitud['nombre_empleado_devuelto']));
	$objResponse->assign("txtFecha","value",utf8_encode($rowOrden['timpo_orden_formato']));
	$objResponse->assign("txtNumeroOrden","value",$rowOrden['numero_orden']);
        $objResponse->assign("hddIdOrden1","value",$rowOrden['id_orden']);
        $objResponse->assign("hddIdOrden2","value",$rowOrden['id_orden']);
	$objResponse->assign("txtTipoOrden","value",utf8_encode($rowOrden['nombre_tipo_orden']));
	$objResponse->assign("txtEstadoOrden","value",utf8_encode($rowOrden['nombre_estado_orden']));
	if ($rowRecepcion['id_cliente_pago'] == NULL){
            $objResponse->assign("txtCliente","value",utf8_encode($rowRecepcion['nombre']." ".$rowRecepcion['apellido']));
        }else{
            $objResponse->assign("txtCliente","value",utf8_encode($rowRecepcion['nombre_cliente_pago']." ".$rowRecepcion['apellido_cliente_pago']));
        }
	$objResponse->assign("txtChasis","value",utf8_encode($rowRecepcion['chasis']));
	$motor.= ($rowUnidadBasica['cil_uni_bas'] != "") ? $rowUnidadBasica['cil_uni_bas']." Cilindros " : "";
	$motor.= ($rowUnidadBasica['ccc_uni_bas'] != "") ? "Motor ".$rowUnidadBasica['ccc_uni_bas'] : "";
	$objResponse->assign("txtMotor","value",utf8_encode($motor));
	$objResponse->assign("txtPlaca","value",utf8_encode($rowRecepcion['placa']));
	$objResponse->assign("txtFechaEnsamblaje","value",utf8_encode($rowUnidadBasica['ano_uni_bas']));	
	
        $objResponse->loadCommands(listadoDetalleSolicitud(0, "", "", $rowSolicitud['id_solicitud']."|".$rowSolicitud['id_empresa']));

        $objResponse->assign("tdFlotanteTitulo","innerHTML","Consulta Solicitud");		
		
	$objResponse->script("document.forms['frmAprobacionSolicitud'].reset();");
	
	$objResponse->script("if($('divFlotante').style.display == 'none'){
                                    $('divFlotante').style.display = '';
                                    centrarDiv($('divFlotante'));
                              }");
	
	return $objResponse;
    }

    function listadoDetalleSolicitud($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
            $objResponse = new xajaxResponse();
			
			//$valCadBusq[0] = id_solicitud
			//$valCadBusq[1] = id_empresa de la solicitud
            $valCadBusq = explode("|", $valBusq);
            $startRow = $pageNum * $maxRows;

            $query = sprintf("SELECT
                                    sa_det_solicitud_repuestos.id_det_solicitud_repuesto,
                                    sa_det_solicitud_repuestos.id_solicitud,
                                    sa_det_solicitud_repuestos.id_det_orden_articulo,
                                    sa_det_solicitud_repuestos.tiempo_aprobacion,
                                    sa_det_solicitud_repuestos.tiempo_despacho,
                                    sa_det_solicitud_repuestos.id_estado_solicitud,
                                    sa_det_orden_articulo.id_det_orden_articulo,
                                    sa_det_orden_articulo.id_orden,
                                    sa_det_orden_articulo.id_articulo,
                                    sa_det_orden_articulo.id_paquete,
                                    sa_det_orden_articulo.cantidad,
                                    sa_det_orden_articulo.precio_unitario,
                                    sa_det_orden_articulo.id_iva,
                                    sa_det_orden_articulo.iva,
                                    sa_det_orden_articulo.aprobado,
                                    sa_det_orden_articulo.tiempo_asignacion,
                                    sa_det_orden_articulo.tiempo_aprobacion,
                                    sa_det_orden_articulo.id_empleado_aprobacion,
                                    sa_det_orden_articulo.estado_articulo,
                                    vw_iv_articulos_empresa.cantidad_disponible_logica,
                                    vw_iv_articulos_empresa.id_articulo,
                                    vw_iv_articulos_empresa.codigo_articulo,
                                    sa_det_solicitud_repuestos.id_casilla
                            FROM
                                    sa_det_solicitud_repuestos
                                    INNER JOIN sa_det_orden_articulo
                                        ON (sa_det_solicitud_repuestos.id_det_orden_articulo = sa_det_orden_articulo.id_det_orden_articulo)
                                    INNER JOIN vw_iv_articulos_empresa
                                        ON (sa_det_orden_articulo.id_articulo = vw_iv_articulos_empresa.id_articulo)
                            WHERE
                                    sa_det_solicitud_repuestos.id_solicitud = '%s' AND vw_iv_articulos_empresa.id_empresa = '%s'
                            ORDER BY
                                    vw_iv_articulos_empresa.codigo_articulo",
                    $valCadBusq[0],
					$valCadBusq[1]);


            $queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
            $rsLimit = mysql_query($queryLimit);

            if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
            if ($totalRows == NULL) {
                    $rs = mysql_query($query);
                    if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
                    $totalRows = mysql_num_rows($rs);
            }
            $totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
                            $htmlTh .= "<td></td>
                            <td width=\"22%\" align=\"center\">C&oacute;digo</td>
                            <td width=\"18%\" align=\"center\">Almac&eacute;n</td>
                            <td width=\"12%\" align=\"center\">Ubicaci&oacute;n</td>
                            <td width=\"12%\" align=\"center\">Estado</td>
                            <td width=\"12%\" align=\"center\">Precio</td>
                            <td width=\"12%\" align=\"center\">".nombreIva(1)."</td>
                            <td width=\"12%\" align=\"center\">Total</td>
                        </tr>";

            $totalRepuestoConIva = 0;
            $contFila = 0;
            while ($row = mysql_fetch_assoc($rsLimit)) {
                    $clase = ($clase == "trResaltar4") ? $clase = "trResaltar7" : $clase = "trResaltar4";

                    $contFila ++;

                    $totalRepuestoConIva = ($row['precio_unitario']+($row['precio_unitario']*$row['iva']/100));
                    $htmlTb.= "<tr class=\"".$clase."\">";
                    $htmlTb .= "<td align=\"center\"><img class=\"puntero\" onclick=\"xajax_cargarDescripcionArticulo(".$row['id_articulo'].")\" src=\"../img/iconos/ico_view.png\"/></td>";
                    $htmlTb .= "<td align=\"left\">".utf8_encode(elimCaracter($row['codigo_articulo'],";"))."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode(almacen($row['id_casilla']))."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode(ubicacion($row['id_casilla']))."</td>";
                    $htmlTb .= "<td align=\"center\">".utf8_encode(estadoDetalleSolicitud($row['id_estado_solicitud']))."</td>";
                    $htmlTb .= "<td align=\"right\">".number_format($row['precio_unitario'],2,".",",")."</td>";
                    $htmlTb .= "<td align=\"right\">".$row['iva']."%</td>";
                    $htmlTb .= "<td align=\"right\">".number_format($totalRepuestoConIva,2,".",",")."</td>";//
                    $htmlTb .= "</tr>";
            }

$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"8\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDetalleSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDetalleSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoDetalleSolicitud(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDetalleSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDetalleSolicitud(%s,'%s','%s','%s',%s);\">%s</a>",
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

            $htmlTableFin .= "</table>";

            if (!($totalRows > 0)) {
                    $htmlTb .= "<td colspan=\"12\">";
                            $htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
                            $htmlTb .= "<tr>";
                                    $htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
                                    $htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
                            $htmlTb .= "</tr>";
                            $htmlTb .= "</table>";
                    $htmlTb .= "</td>";
            }

			$objResponse->assign("tdListadoDetalleSolicitud","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
            

            return $objResponse;
    }

    function cargarDescripcionArticulo($idArticulo){
	$objResponse = new xajaxResponse();

	$queryArticulo = sprintf("SELECT
                                        *
                                    FROM
                                        vw_iv_articulos_datos_basicos
                                    WHERE
                                        id_articulo = %s",
                            valTpDato($idArticulo, "int"));

	$rsArticulo = mysql_query($queryArticulo);
	if (!$rsArticulo) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowArticulo = mysql_fetch_array($rsArticulo);

	$imgFoto = $rowArticulo['foto'];
	if(!file_exists($rowArticulo['foto'])){
            $imgFoto = "../".$_SESSION['logoEmpresaSysGts'];
        }

	$html .= "<table width=\"100%\">
                    <tr>
                        <td align=\"center\" valign=\"top\">
                            <table>
                                <tr>
                                    <td align=\"center\" class=\"imgBorde\">
                                        <img id=\"imgArticulo\" src=\"".$imgFoto."\" width=\"160\"/>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td valign=\"top\" width=\"100%\">
                            <table width=\"100%\">
                                <tr align=\"left\">
                                    <td align=\"right\" class=\"tituloCampo\" width=\"25%\">Codigo:</td>
                                    <td width=\"75%\">".utf8_encode(elimCaracter($rowArticulo['codigo_articulo'],";"))."</td>
                                </tr>
                                <tr align=\"left\">
                                    <td align=\"right\" class=\"tituloCampo\">Tipo:</td>
                                    <td>".utf8_encode($rowArticulo['tipo_articulo'])."</td>
                                </tr>
                                <tr align=\"left\">
                                    <td align=\"right\" class=\"tituloCampo\">Marca:</td>
                                    <td>".utf8_encode($rowArticulo['marca'])."</td>
                                </tr>
                                <tr align=\"left\">
                                    <td align=\"right\" class=\"tituloCampo\">Descripci&oacute;n:</td>
                                    <td>".utf8_encode($rowArticulo['descripcion'])."</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>";

	$htmlbtn .= "<hr><input type=\"button\" onclick=\"$('divFlotante1').style.display = 'none';\" value=\"Cerrar\">";

	$objResponse->assign("tdDescripcionArticulo","innerHTML",$html);
	$objResponse->assign("tdBotonesDiv","innerHTML",$htmlbtn);

	$objResponse->script("$('tblListados1').style.display = '';
                              $('tblAlmacen').style.display = 'none';");

	$objResponse->assign("tdFlotanteTitulo1","innerHTML","Descripcion Articulo");
	$objResponse->assign("tblListados1","width","600");
	$objResponse->script("if ($('divFlotante1').style.display == 'none') {
                                    $('divFlotante1').style.display = '';
                                    centrarDiv($('divFlotante1'));
                              }");

	return $objResponse;
    }

    function almacen($id) {
	$query = sprintf("SELECT
                            *
                        FROM
                            vw_iv_articulos_empresa_ubicacion
                        WHERE
                            id_casilla = '%s'",
		$id);

	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$row =mysql_fetch_array($rs);

	$respuesta = $row['descripcion_almacen'];
	
	return $respuesta;
    }

    function ubicacion($id) {
        $query = sprintf("SELECT
                            *
                        FROM
                            vw_iv_articulos_empresa_ubicacion
                        WHERE
                            id_casilla = '%s'",
                $id);

        $rs = mysql_query($query);
        if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $row = mysql_fetch_array($rs);

        $respuesta = $row['ubicacion'];

        return $respuesta;
    }

    function estadoDetalleSolicitud($id) {
        $queryEstadoDetalleSolicitud = sprintf("SELECT
                                                    *
                                                FROM
                                                    sa_estado_solicitud
                                                WHERE
                                                    id_estado_solicitud = '%s'",
                                        $id);
        
        $rsEstadoDetalleSolicitud = mysql_query($queryEstadoDetalleSolicitud);
        if (!$rsEstadoDetalleSolicitud) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
        $rowEstadoDetalleSolicitud = mysql_fetch_array($rsEstadoDetalleSolicitud);

        $respuesta = $rowEstadoDetalleSolicitud['descripcion_estado_solicitud'];

        return $respuesta;
    }


    $xajax->register(XAJAX_FUNCTION,"cargaLstEmpresa");
    $xajax->register(XAJAX_FUNCTION,"cargaLstEstatus");
    $xajax->register(XAJAX_FUNCTION,"buscar");
    $xajax->register(XAJAX_FUNCTION,"listadoSolicitudRepuestos");
    $xajax->register(XAJAX_FUNCTION,"verSolicitud");
    $xajax->register(XAJAX_FUNCTION,"listadoDetalleSolicitud");
    $xajax->register(XAJAX_FUNCTION,"cargarDescripcionArticulo");
    
    
    function nombreIva($idIva){
        //cuando se crea no posee iva, por lo tanto deberia ser el primero id 1 itbms-iva
        if($idIva == NULL || $idIva == "0" || $idIva == "" || $idIva == " "){
            $idIva = 1;
        }    
        $query = "SELECT observacion FROM pg_iva WHERE idIva = ".$idIva."";
        $rs = mysql_query($query);
        if(!$rs){ return ("Error cargarDcto \n".mysql_error().$query."\n Linea: ".__LINE__); }

        $row = mysql_fetch_assoc($rs);

        return $row['observacion'];

    }
?>