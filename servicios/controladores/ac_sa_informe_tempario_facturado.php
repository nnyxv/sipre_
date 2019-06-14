<?php 

function asignarMecanico($idMecanico){	
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("mecanico.id_mecanico = %s",
		valTpDato($idMecanico, "int"));
	
	$query = sprintf("SELECT
								mecanico.id_mecanico,
								CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_mecanico
							FROM sa_mecanicos mecanico
							INNER JOIN pg_empleado ON (mecanico.id_empleado = pg_empleado.id_empleado) 
							%s;", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);	
		
	$objResponse->assign("txtIdMecanico","value",$row['id_mecanico']);
	$objResponse->assign("txtNombreMecanico","value",utf8_encode($row['nombre_mecanico']));
		
	$objResponse->script("byId('btnCancelarLista').click();");//si viene del listado
		
	return $objResponse;
}

function buscarMecanico($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscar['txtCriterioBuscarMecanico']);
	
	$objResponse->loadCommands(listaMecanico(0, "id_mecanico", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarTempario($valForm) {
    $objResponse = new xajaxResponse();

    $fecha1 = $valForm['txtFechaDesde'];
    $fecha2 = $valForm['txtFechaHasta'];

    $fechaDesde = date("Y/m/d",strtotime($fecha1));
    $fechaHasta = date("Y/m/d",strtotime($fecha2));
    
    $valBusq = sprintf("%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
        $valForm['txtIdMecanico'],
        $valForm['txtCriterio']
    );
    
    if ($fechaDesde > $fechaHasta){
        $objResponse->script('alert("La primera fecha no debe ser mayor a la segunda")');
    }else{
       $objResponse->loadCommands(listadoTempario('0','','ASC',$valBusq));
    }
    
    return $objResponse;
}

function listaMecanico($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
		
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(empleados.cedula LIKE %s
		OR nombre_empleado LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
			mecanicos.id_mecanico,
			equipo.nombre_equipo,
			equipo.tipo_equipo,						
			empleados.activo,
			empleados.id_empleado,
			empleados.nombre_cargo,
			empleados.cedula,
			empleados.nombre_empleado
		FROM sa_mecanicos mecanicos
		INNER JOIN vw_pg_empleados empleados ON (mecanicos.id_empleado = empleados.id_empleado) 
		INNER JOIN sa_equipos_mecanicos equipo ON (mecanicos.id_equipo_mecanico = equipo.id_equipo_mecanico) %s", $sqlBusq);	
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listaMecanico", "5%", $pageNum, "id_mecanico", $campOrd, $tpOrd, $valBusq, $maxRows, "C&oacute;digo");
		$htmlTh .= ordenarCampo("xajax_listaMecanico", "10%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaMecanico", "30%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaMecanico", "20%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
		$htmlTh .= ordenarCampo("xajax_listaMecanico", "15%", $pageNum, "nombre_equipo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Equipo"));
		$htmlTh .= ordenarCampo("xajax_listaMecanico", "10%", $pageNum, "tipo_equipo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo Equipo"));
		$htmlTh .= "<td></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch($row['activo']) {
			case 0 : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "";
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarMecanico('".$row['id_mecanico']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".sprintf("%04s",$row['id_mecanico']);"</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_equipo'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['tipo_equipo'])."</td>";
			$htmlTb .= "<td>".$imgEstatus."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"50\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMecanico(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMecanico(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaMecanico(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMecanico(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaMecanico(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"50\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divLista","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listadoTempario($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {	
    $objResponse = new xajaxResponse();

    $valCadBusq = explode("|", $valBusq);
    $startRow = $pageNum * $maxRows;

    //$valCadBusq[0] id empresa
    //$valCadBusq[1] fecha desde
    //$valCadBusq[2] fecha hasta
    //$valCadBusq[3] id mecanico
    //$valCadBusq[4] criterio

    //$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
    //$sqlBusq .= $cond.sprintf("orden.id_estado_orden NOT IN (18,21,24)");

    if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("dcto.id_empresa = %s",
                    valTpDato($valCadBusq[0], "int"));
    }

    if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("DATE(dcto.fecha_dcto) BETWEEN %s AND %s",
                    valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
                    valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
    }

    if ($valCadBusq[3] != "" && $valCadBusq[3] != " ") {
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond.sprintf("dcto.id_mecanico = %s",
                valTpDato($valCadBusq[3],"int"));
    }

    if ($valCadBusq[4] != "" && $valCadBusq[4] != " ") {
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond.sprintf("(dcto.numero_dcto = %s OR
									dcto.numero_control_dcto = %s
									OR dcto.numero_orden = %s)",
                valTpDato($valCadBusq[4],"text"),
                valTpDato($valCadBusq[4],"text"),
                valTpDato($valCadBusq[4],"text"));
    }

    $query = sprintf("SELECT * FROM (
						SELECT
							'FA' as tipo_dcto,
							'FACTURA' as nombre_dcto,
							sa_orden.id_orden,
							sa_orden.id_tipo_orden,
							sa_orden.numero_orden,
							sa_tipo_orden.nombre_tipo_orden,
							sa_estado_orden.nombre_estado,
							sa_orden.tiempo_orden,
							sa_orden.tiempo_finalizado,
							cxc_fact.idFactura AS id_dcto,
							cxc_fact.numeroFactura AS numero_dcto,
							cxc_fact.numeroControl AS numero_control_dcto,
							cxc_fact.fechaRegistroFactura AS fecha_dcto,
							cxc_fact.id_empresa,
							cxc_det_temp.id_tempario,
							cxc_det_temp.id_paquete,
							cxc_det_temp.ut,
							cxc_det_temp.precio,
							cxc_det_temp.costo,
							cxc_det_temp.costo_orden,
							cxc_det_temp.id_modo,
							cxc_det_temp.base_ut_precio,
							cxc_det_temp.operador,
							cxc_det_temp.id_mecanico,
							cxc_det_temp.precio_tempario_tipo_orden,
							
							(case cxc_det_temp.id_modo 
								when '1' then ROUND(cxc_det_temp.ut * cxc_det_temp.precio_tempario_tipo_orden / cxc_det_temp.base_ut_precio,2)
								when '2' then cxc_det_temp.precio 
								when '3' then cxc_det_temp.costo 
								end) AS total_por_tipo_orden,
							
							(case cxc_det_temp.id_modo 
								when '1' then cxc_det_temp.ut 
								when '2' then cxc_det_temp.precio 
								when '3' then cxc_det_temp.costo 
								end) AS precio_por_tipo_orden,
							
							sa_operadores.descripcion_operador,
							tempario.codigo_tempario,
							tempario.descripcion_tempario,
							
							CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
							CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_asesor,
							CONCAT_WS(' ', emp_mecanico.nombre_empleado, emp_mecanico.apellido) AS nombre_mecanico
						FROM cj_cc_encabezadofactura cxc_fact
						INNER JOIN sa_orden ON (cxc_fact.numeroPedido = sa_orden.id_orden)
						INNER JOIN sa_tipo_orden ON (sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden)
						INNER JOIN sa_estado_orden ON (sa_orden.id_estado_orden = sa_estado_orden.id_estado_orden)
						INNER JOIN cj_cc_cliente ON (cj_cc_cliente.id = sa_orden.id_cliente)
						INNER JOIN pg_empleado ON (sa_orden.id_empleado = pg_empleado.id_empleado)
						INNER JOIN sa_det_fact_tempario cxc_det_temp ON (cxc_fact.idFactura = cxc_det_temp.idFactura)
						INNER JOIN sa_tempario tempario ON (cxc_det_temp.id_tempario = tempario.id_tempario)
						INNER JOIN sa_operadores ON (cxc_det_temp.operador = sa_operadores.id_operador)
						INNER JOIN sa_mecanicos ON (cxc_det_temp.id_mecanico = sa_mecanicos.id_mecanico)
						INNER JOIN pg_empleado emp_mecanico ON (sa_mecanicos.id_empleado = emp_mecanico.id_empleado)
						WHERE cxc_fact.idDepartamentoOrigenFactura = 1
						
						UNION
						
						SELECT
							'NC' as tipo_dcto,
							'NOTA DE CR&Eacute;DITO' as nombre_dcto,
							sa_orden.id_orden,
							sa_orden.id_tipo_orden,
							sa_orden.numero_orden,
							sa_tipo_orden.nombre_tipo_orden,
							sa_estado_orden.nombre_estado,
							sa_orden.tiempo_orden,
							sa_orden.tiempo_finalizado,
							cxc_nc.idNotaCredito AS id_dcto,
							cxc_nc.numeracion_nota_credito AS numero_dcto,
							cxc_nc.numeroControl AS numero_control_dcto,
							cxc_nc.fechaNotaCredito AS fecha_dcto,
							cxc_nc.id_empresa,
							cxc_det_temp.id_tempario,
							cxc_det_temp.id_paquete,
							(cxc_det_temp.ut * -1),
							cxc_det_temp.precio,
							cxc_det_temp.costo,
							cxc_det_temp.costo_orden,
							cxc_det_temp.id_modo,
							cxc_det_temp.base_ut_precio,
							cxc_det_temp.operador,
							cxc_det_temp.id_mecanico,
							cxc_det_temp.precio_tempario_tipo_orden,
							
							((case cxc_det_temp.id_modo 
								when '1' then ROUND(cxc_det_temp.ut * cxc_det_temp.precio_tempario_tipo_orden / cxc_det_temp.base_ut_precio,2)
								when '2' then cxc_det_temp.precio 
								when '3' then cxc_det_temp.costo 
								end) * -1) AS total_por_tipo_orden,
							
							((case cxc_det_temp.id_modo 
								when '1' then cxc_det_temp.ut 
								when '2' then cxc_det_temp.precio 
								when '3' then cxc_det_temp.costo 
								end) * -1) AS precio_por_tipo_orden,
							
							sa_operadores.descripcion_operador,
							tempario.codigo_tempario,
							tempario.descripcion_tempario,
							
							CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
							CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_asesor,
							CONCAT_WS(' ', emp_mecanico.nombre_empleado, emp_mecanico.apellido) AS nombre_mecanico
						FROM cj_cc_notacredito cxc_nc
						INNER JOIN cj_cc_encabezadofactura cxc_fact ON (cxc_nc.idDocumento = cxc_fact.idFactura)
						INNER JOIN sa_orden ON (cxc_fact.numeroPedido = sa_orden.id_orden)
						INNER JOIN sa_tipo_orden ON (sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden)
						INNER JOIN sa_estado_orden ON (sa_orden.id_estado_orden = sa_estado_orden.id_estado_orden)
						INNER JOIN cj_cc_cliente ON (cj_cc_cliente.id = sa_orden.id_cliente)
						INNER JOIN pg_empleado ON (sa_orden.id_empleado = pg_empleado.id_empleado)
						INNER JOIN sa_det_fact_tempario cxc_det_temp ON (cxc_fact.idFactura = cxc_det_temp.idFactura)
						INNER JOIN sa_tempario tempario ON (cxc_det_temp.id_tempario = tempario.id_tempario)
						INNER JOIN sa_operadores ON (cxc_det_temp.operador = sa_operadores.id_operador)
						INNER JOIN sa_mecanicos ON (cxc_det_temp.id_mecanico = sa_mecanicos.id_mecanico)
						INNER JOIN pg_empleado emp_mecanico ON (sa_mecanicos.id_empleado = emp_mecanico.id_empleado)
						WHERE cxc_nc.tipoDocumento = 'FA' AND idDepartamentoNotaCredito = 1
						
						UNION
						
						SELECT
							'VS' as tipo_dcto,
							'VALE SALIDA' as nombre_dcto,
							sa_orden.id_orden,
							sa_orden.id_tipo_orden,
							sa_orden.numero_orden,
							sa_tipo_orden.nombre_tipo_orden,
							sa_estado_orden.nombre_estado,
							sa_orden.tiempo_orden,
							sa_orden.tiempo_finalizado,
							vale_salida.id_vale_salida AS id_dcto,
							vale_salida.numero_vale AS numero_dcto,
							'' AS numero_control_dcto, 
							DATE(vale_salida.fecha_vale) AS fecha_dcto,	
							vale_salida.id_empresa,
							vale_det_temp.id_tempario,
							vale_det_temp.id_paquete,
							vale_det_temp.ut,
							vale_det_temp.precio,
							vale_det_temp.costo,
							vale_det_temp.costo_orden,
							vale_det_temp.id_modo,
							vale_det_temp.base_ut_precio,
							vale_det_temp.operador,
							vale_det_temp.id_mecanico,
							vale_det_temp.precio_tempario_tipo_orden,
							
							(case vale_det_temp.id_modo 
								when '1' then ROUND(vale_det_temp.ut * vale_det_temp.precio_tempario_tipo_orden / vale_det_temp.base_ut_precio,2)
								when '2' then vale_det_temp.precio 
								when '3' then vale_det_temp.costo 
								end) AS total_por_tipo_orden,
							
							(case vale_det_temp.id_modo 
								when '1' then vale_det_temp.ut 
								when '2' then vale_det_temp.precio 
								when '3' then vale_det_temp.costo 
								end) AS precio_por_tipo_orden,
							
							sa_operadores.descripcion_operador,
							tempario.codigo_tempario,
							tempario.descripcion_tempario,
							
							CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
							CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_asesor,
							CONCAT_WS(' ', emp_mecanico.nombre_empleado, emp_mecanico.apellido) AS nombre_mecanico
						FROM sa_vale_salida vale_salida
						INNER JOIN sa_orden ON (vale_salida.id_orden = sa_orden.id_orden)
						INNER JOIN sa_tipo_orden ON (sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden)
						INNER JOIN sa_estado_orden ON (sa_orden.id_estado_orden = sa_estado_orden.id_estado_orden)
						INNER JOIN cj_cc_cliente ON (cj_cc_cliente.id = sa_orden.id_cliente)
						INNER JOIN pg_empleado ON (sa_orden.id_empleado = pg_empleado.id_empleado)
						INNER JOIN sa_det_vale_salida_tempario vale_det_temp ON (vale_salida.id_vale_salida = vale_det_temp.id_vale_salida)
						INNER JOIN sa_tempario tempario ON (vale_det_temp.id_tempario = tempario.id_tempario)
						INNER JOIN sa_operadores ON (vale_det_temp.operador = sa_operadores.id_operador)
						INNER JOIN sa_mecanicos ON (vale_det_temp.id_mecanico = sa_mecanicos.id_mecanico)
						INNER JOIN pg_empleado emp_mecanico ON (sa_mecanicos.id_empleado = emp_mecanico.id_empleado)
						
						UNION
						
						SELECT
							'VE' as tipo_dcto,
							'VALE ENTRADA' as nombre_dcto,
							sa_orden.id_orden,
							sa_orden.id_tipo_orden,
							sa_orden.numero_orden,
							sa_tipo_orden.nombre_tipo_orden,
							sa_estado_orden.nombre_estado,
							sa_orden.tiempo_orden,
							sa_orden.tiempo_finalizado,
							vale_entrada.id_vale_entrada AS id_dcto,
							vale_entrada.numero_vale_entrada AS numero_dcto,
							'' AS numero_control_dcto, 
							DATE(vale_entrada.fecha_creada) AS fecha_dcto,
							vale_entrada.id_empresa,
							vale_det_temp.id_tempario,
							vale_det_temp.id_paquete,
							vale_det_temp.ut,
							vale_det_temp.precio,
							vale_det_temp.costo,
							vale_det_temp.costo_orden,
							vale_det_temp.id_modo,
							vale_det_temp.base_ut_precio,
							vale_det_temp.operador,
							vale_det_temp.id_mecanico,
							vale_det_temp.precio_tempario_tipo_orden,
							
							(case vale_det_temp.id_modo 
								when '1' then ROUND(vale_det_temp.ut * vale_det_temp.precio_tempario_tipo_orden / vale_det_temp.base_ut_precio,2)
								when '2' then vale_det_temp.precio 
								when '3' then vale_det_temp.costo 
								end) AS total_por_tipo_orden,
							
							(case vale_det_temp.id_modo 
								when '1' then vale_det_temp.ut 
								when '2' then vale_det_temp.precio 
								when '3' then vale_det_temp.costo 
								end) AS precio_por_tipo_orden,
							
							sa_operadores.descripcion_operador,
							tempario.codigo_tempario,
							tempario.descripcion_tempario,
							
							CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
							CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_asesor,
							CONCAT_WS(' ', emp_mecanico.nombre_empleado, emp_mecanico.apellido) AS nombre_mecanico
						FROM sa_vale_entrada vale_entrada
						INNER JOIN sa_vale_salida vale_salida ON (vale_entrada.id_vale_salida = vale_salida.id_vale_salida)
						INNER JOIN sa_orden ON (vale_salida.id_orden = sa_orden.id_orden)
						INNER JOIN sa_tipo_orden ON (sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden)
						INNER JOIN sa_estado_orden ON (sa_orden.id_estado_orden = sa_estado_orden.id_estado_orden)
						INNER JOIN cj_cc_cliente ON (cj_cc_cliente.id = sa_orden.id_cliente)
						INNER JOIN pg_empleado ON (sa_orden.id_empleado = pg_empleado.id_empleado)
						INNER JOIN sa_det_vale_salida_tempario vale_det_temp ON (vale_salida.id_vale_salida = vale_det_temp.id_vale_salida)
						INNER JOIN sa_tempario tempario ON (vale_det_temp.id_tempario = tempario.id_tempario)
						INNER JOIN sa_operadores ON (vale_det_temp.operador = sa_operadores.id_operador)
						INNER JOIN sa_mecanicos ON (vale_det_temp.id_mecanico = sa_mecanicos.id_mecanico)
						INNER JOIN pg_empleado emp_mecanico ON (sa_mecanicos.id_empleado = emp_mecanico.id_empleado)
						
		) AS dcto %s", $sqlBusq); 
	
    //$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

    $queryLimit = sprintf("%s %s ", $query, $sqlOrd);
    $rsLimit = mysql_query($queryLimit);
    if (!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }

    if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
            $totalRows = mysql_num_rows($rs);
    }
    $totalPages = ceil($totalRows/$maxRows)-1;

	$htmlTb = "";
	$arrayDcto = array();
	$arrayDctoItems = array();

	$arrayTotalMecanicos = array();
	$arrayTotalMecanicosTipoOrden = array();
	$arrayTotalTipoOrden = array();
	$arrayTotalDcto = array();	
    
    while ($row = mysql_fetch_assoc($rsLimit)) {
		$arrayDcto[$row["tipo_dcto"].$row["id_orden"]] = $row;//para encabezados
		$arrayDctoItems[$row["tipo_dcto"].$row["id_orden"]][] = $row;//para items por cada encabezado
	}
	
	$contFila = 0;
	foreach($arrayDcto as $indice => $arrayDcto){
		$contFila++;
	
		$htmlTb .= "<table width=\"100%\" class=\"tabla\" border=\"1\">";
			$htmlTb .= "<tr class=\"trResaltarTotal textoNegrita_12px\" height=\"24\">";
				$htmlTb .= "<td colspan=\"20\">".utf8_encode($arrayDcto['nombre_mecanico']." - ".$arrayDcto['nombre_dcto']." - ".$arrayDcto['nombre_tipo_orden'])."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"8%\" align=\"right\" class=\"tituloCampo\">Dcto:</td>";
				$htmlTb .= "<td width=\"6%\" align=\"left\">".utf8_encode($arrayDcto["nombre_dcto"])."</td>";
				$htmlTb .= "<td width=\"8%\" align=\"right\" class=\"tituloCampo\">Nro Dcto:</td>";
				$htmlTb .= "<td width=\"6%\" align=\"left\">".utf8_encode($arrayDcto["numero_dcto"])."</td>";
				$htmlTb .= "<td width=\"8%\" align=\"right\" class=\"tituloCampo\">Nro Control Dcto:</td>";
				$htmlTb .= "<td width=\"6%\" align=\"left\">".utf8_encode($arrayDcto["numero_control_dcto"])."</td>";
				$htmlTb .= "<td width=\"8%\" align=\"right\" class=\"tituloCampo\">Cliente:</td>";
				$htmlTb .= "<td width=\"25%\" align=\"left\">".utf8_encode($arrayDcto["nombre_cliente"])."</td>";
				$htmlTb .= "<td width=\"8%\" align=\"right\" class=\"tituloCampo\">Fecha Dcto:</td>";
				$htmlTb .= "<td width=\"6%\" align=\"left\">".date(spanDateFormat, strtotime($arrayDcto["fecha_dcto"]))."</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Tipo Orden:</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayDcto["nombre_tipo_orden"])."</td>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Nro Orden:</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayDcto["numero_orden"])."</td>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Estado:</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayDcto["nombre_estado"])."</td>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Asesor:</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayDcto["nombre_asesor"])."</td>";
				$htmlTb .= "<td align=\"right\" class=\"tituloCampo\">Fecha Orden:</td>";
				$htmlTb .= "<td align=\"left\">".date(spanDateFormat, strtotime($arrayDcto["tiempo_orden"]))."</td>";
			$htmlTb .= "</tr>";
		$htmlTb .= "</table>";
		
		$htmlTb .= "<table width=\"100%\" class=\"tabla2\" border=\"0\">";
		$htmlTb .= "<tr class=\"tituloColumna\" align=\"center\">";
			$htmlTb .= "<td width=\"12%\">C&oacute;digo</td>";
			$htmlTb .= "<td width=\"35%\">Descripci&oacute;n</td>";                   
			$htmlTb .= "<td width=\"7%\">Operador</td>";
			$htmlTb .= "<td width=\"5%\">Modo</td>";
			$htmlTb .= "<td width=\"5%\">UT</td>";
			$htmlTb .= "<td width=\"5%\">Valor M.O.</td>";
			$htmlTb .= "<td width=\"5%\">Precio</td>";
		$htmlTb .= "</tr>";
				
		$arrayTotalItem = array();
				
		$contFila2 = 0;
		foreach($arrayDctoItems[$indice] as $arrayItem){
			$contFila2++;
			$clase = (fmod($contFila2, 2) == 0) ? "trResaltar5" : "trResaltar4";
			
			switch($arrayItem["id_modo"]) {
				case 1 : $modo = "UT"; break;
				case 2 : $modo = "PRECIO"; break;
				case 3 : $modo = "COSTO"; break;
				default : $modo = "";
			}
			
			$caracterPrecioTempario = ($arrayItem['id_modo'] == 1) ? $arrayItem['precio_tempario_tipo_orden'] : $arrayItem['precio_por_tipo_orden'];
			
			$htmlTb .= "<tr class=\"".$clase."\">";
				$htmlTb .= "<td align=\"left\">".$arrayItem["codigo_tempario"]."</td>";
				$htmlTb .= "<td align=\"left\">".utf8_encode($arrayItem["descripcion_tempario"])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($arrayItem["descripcion_operador"])."</td>";
				$htmlTb .= "<td align=\"center\">".utf8_encode($modo)."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($arrayItem["ut"],2,".",",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($caracterPrecioTempario,2,".",",")."</td>";
				$htmlTb .= "<td align=\"right\">".number_format($arrayItem["total_por_tipo_orden"],2,".",",")."</td>";
			$htmlTb .= "</tr>";
			
			$arrayTotalItem[0] += $arrayItem["ut"];
			$arrayTotalItem[1] += $arrayItem["total_por_tipo_orden"];
			
			$arrayTotalMecanicos[$arrayItem["id_mecanico"]][0] = $arrayDcto['nombre_mecanico'];
			$arrayTotalMecanicos[$arrayItem["id_mecanico"]][1] += $arrayItem["ut"];
			$arrayTotalMecanicos[$arrayItem["id_mecanico"]][2] += $arrayItem["total_por_tipo_orden"];
			
			$arrayTotalMecanicosTipoOrden[$arrayItem["id_mecanico"].$arrayDcto["id_tipo_orden"]][0] = $arrayDcto['nombre_mecanico'];
			$arrayTotalMecanicosTipoOrden[$arrayItem["id_mecanico"].$arrayDcto["id_tipo_orden"]][1] = $arrayDcto['nombre_tipo_orden'];
			$arrayTotalMecanicosTipoOrden[$arrayItem["id_mecanico"].$arrayDcto["id_tipo_orden"]][2] += $arrayItem["ut"];
			$arrayTotalMecanicosTipoOrden[$arrayItem["id_mecanico"].$arrayDcto["id_tipo_orden"]][3] += $arrayItem["total_por_tipo_orden"];
			
			$arrayTotalTipoOrden[$arrayDcto["id_tipo_orden"]][0] = $arrayDcto['nombre_tipo_orden'];
			$arrayTotalTipoOrden[$arrayDcto["id_tipo_orden"]][1] += $arrayItem["ut"];
			$arrayTotalTipoOrden[$arrayDcto["id_tipo_orden"]][2] += $arrayItem["total_por_tipo_orden"];
			
			$arrayTotalDcto[$arrayDcto["tipo_dcto"]][0] = $arrayDcto["nombre_dcto"];
			$arrayTotalDcto[$arrayDcto["tipo_dcto"]][1] += $arrayItem["ut"];
			$arrayTotalDcto[$arrayDcto["tipo_dcto"]][2] += $arrayItem["total_por_tipo_orden"];
			
		}
		
		$htmlTb .= "<tr>";
			$htmlTb .= "<td align=\"right\" colspan=\"4\"><b>Total:</b></td>";
			$htmlTb .= "<td align=\"right\"><b>".number_format($arrayTotalItem[0],2,".",",")."</b></td>";
			$htmlTb .= "<td align=\"right\"></td>";
			$htmlTb .= "<td align=\"right\"><b>".number_format($arrayTotalItem[1],2,".",",")."</b></td>";
		$htmlTb .= "</tr>";
		
		$htmlTb .= "</table>";
		$htmlTb .= "<br>";
	
	}
	//FINAL RAYA TABLA		
	$htmlTb .= "<table width=\"100%\">";
		$htmlTb .= "<tr><td colspan=\"20\"><hr /></td></tr>";
	$htmlTb .= "</table>";
	
	//TABLA RESUMEN POR MECANICO
	$htmlTb .= "<br>";
	$htmlTb .= "<table width=\"100%\" class=\"tablaResumen\">";	
	$htmlTb .= "<tr class=\"tituloColumna\" align=\"center\">";
		$htmlTb .= "<td width=\"35%\">Mec&aacute;nico</td>";
		$htmlTb .= "<td width=\"10%\">UT</td>";                   
		$htmlTb .= "<td width=\"10%\">Precio</td>";
	$htmlTb .= "</tr>";
	
	$totalUt = 0;
	$totalPrecio = 0;
	foreach($arrayTotalMecanicos as $arrayTotalMecanico){
		$htmlTb .= "<tr>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($arrayTotalMecanico[0])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($arrayTotalMecanico[1],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($arrayTotalMecanico[2],2,".",",")."</td>";
		$htmlTb .= "</tr>";
		
		$totalUt += $arrayTotalMecanico[1];
		$totalPrecio += $arrayTotalMecanico[2];
	}
	$htmlTb .= "<tr>";
		$htmlTb .= "<td align=\"right\"><b>Total:</b></td>";
		$htmlTb .= "<td align=\"right\"><b>".number_format($totalUt,2,".",",")."</b></td>";
		$htmlTb .= "<td align=\"right\"><b>".number_format($totalPrecio,2,".",",")."</b></td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "</table>";

    //TABLA RESUMEN MECANICO TIPO DE ORDEN
	$htmlTb .= "<br>";
	$htmlTb .= "<table width=\"100%\" class=\"tablaResumen\">";	
	$htmlTb .= "<tr class=\"tituloColumna\" align=\"center\">";
		$htmlTb .= "<td width=\"35%\">Mec&aacute;nico Tipo Orden</td>";
		$htmlTb .= "<td width=\"10%\">UT</td>";                   
		$htmlTb .= "<td width=\"10%\">Precio</td>";
	$htmlTb .= "</tr>";
	
	$totalUt = 0;
	$totalPrecio = 0;
	
	sort($arrayTotalMecanicosTipoOrden);
	foreach($arrayTotalMecanicosTipoOrden as $arrayTotalMecaTipoOrden){
		$htmlTb .= "<tr>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($arrayTotalMecaTipoOrden[0]." - ".$arrayTotalMecaTipoOrden[1])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($arrayTotalMecaTipoOrden[2],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($arrayTotalMecaTipoOrden[3],2,".",",")."</td>";
		$htmlTb .= "</tr>";
		
		$totalUt += $arrayTotalMecaTipoOrden[2];
		$totalPrecio += $arrayTotalMecaTipoOrden[3];
	}
	$htmlTb .= "<tr>";
		$htmlTb .= "<td align=\"right\"><b>Total:</b></td>";
		$htmlTb .= "<td align=\"right\"><b>".number_format($totalUt,2,".",",")."</b></td>";
		$htmlTb .= "<td align=\"right\"><b>".number_format($totalPrecio,2,".",",")."</b></td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "</table>";
	
	//TABLA RESUMEN POR TIPO DE ORDEN
	$htmlTb .= "<br>";
	$htmlTb .= "<table width=\"100%\" class=\"tablaResumen\">";	
	$htmlTb .= "<tr class=\"tituloColumna\" align=\"center\">";
		$htmlTb .= "<td width=\"35%\">Tipo de Orden</td>";
		$htmlTb .= "<td width=\"10%\">UT</td>";                   
		$htmlTb .= "<td width=\"10%\">Precio</td>";
	$htmlTb .= "</tr>";
	
	$totalUt = 0;
	$totalPrecio = 0;
	foreach($arrayTotalTipoOrden as $arrayTotalOrden){
		$htmlTb .= "<tr>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($arrayTotalOrden[0])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($arrayTotalOrden[1],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($arrayTotalOrden[2],2,".",",")."</td>";
		$htmlTb .= "</tr>";
		
		$totalUt += $arrayTotalOrden[1];
		$totalPrecio += $arrayTotalOrden[2];
	}
	$htmlTb .= "<tr>";
		$htmlTb .= "<td align=\"right\"><b>Total:</b></td>";
		$htmlTb .= "<td align=\"right\"><b>".number_format($totalUt,2,".",",")."</b></td>";
		$htmlTb .= "<td align=\"right\"><b>".number_format($totalPrecio,2,".",",")."</b></td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "</table>";
	
	//TABLA RESUMEN POR TIPO DE DOCUMENTO
	$htmlTb .= "<br>";
	$htmlTb .= "<table width=\"100%\" class=\"tablaResumen\">";	
	$htmlTb .= "<tr class=\"tituloColumna\" align=\"center\">";
		$htmlTb .= "<td width=\"35%\">Tipo de Documento</td>";
		$htmlTb .= "<td width=\"10%\">UT</td>";                   
		$htmlTb .= "<td width=\"10%\">Precio</td>";
	$htmlTb .= "</tr>";
	
	$totalUt = 0;
	$totalPrecio = 0;
	foreach($arrayTotalDcto as $arrayTotalDctoDet){
		$htmlTb .= "<tr>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($arrayTotalDctoDet[0])."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($arrayTotalDctoDet[1],2,".",",")."</td>";
			$htmlTb .= "<td align=\"right\">".number_format($arrayTotalDctoDet[2],2,".",",")."</td>";
		$htmlTb .= "</tr>";
		
		$totalUt += $arrayTotalDctoDet[1];
		$totalPrecio += $arrayTotalDctoDet[2];
	}
	$htmlTb .= "<tr>";
		$htmlTb .= "<td align=\"right\"><b>Total:</b></td>";
		$htmlTb .= "<td align=\"right\"><b>".number_format($totalUt,2,".",",")."</b></td>";
		$htmlTb .= "<td align=\"right\"><b>".number_format($totalPrecio,2,".",",")."</b></td>";
	$htmlTb .= "</tr>";
	$htmlTb .= "</table>";

   /* $htmlTf = "<tr>";
            $htmlTf .= "<td align=\"center\" colspan=\"30\">";

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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum > 0) { 
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"100\">";

                                                    $htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoTempario(%s,'%s','%s','%s',%s)\">",
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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum < $totalPages) {
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoTempario(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            $totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                    $htmlTf .= "</tr>";
                                    $htmlTf .= "</table>";
                            $htmlTf .= "</td>";
                    $htmlTf .= "</tr>";
                    $htmlTf .= "</table>";
            $htmlTf .= "</td>";
    $htmlTf .= "</tr>";*/



    if (!($totalRows > 0)) {
            $htmlTb .= "<td colspan=\"30\">";
                    $htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
                    $htmlTb .= "<tr>";
                            $htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
                            $htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
                    $htmlTb .= "</tr>";
                    $htmlTb .= "</table>";
            $htmlTb .= "</td>";
    }

    $objResponse->assign("divListaHistorial","innerHTML",$htmlTblIni.$htmlTf.$htmlTb.$htmlTf.$htmlTblFin);

    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarMecanico");
$xajax->register(XAJAX_FUNCTION,"buscarMecanico");
$xajax->register(XAJAX_FUNCTION,"buscarTempario");
$xajax->register(XAJAX_FUNCTION,"listaMecanico");
$xajax->register(XAJAX_FUNCTION,"listadoTempario");
