<?php 

function asignarCliente($idCliente){	
	$objResponse = new xajaxResponse();
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("cliente.id = %s",
		valTpDato($idCliente, "int"));
	
	$queryCliente = sprintf("SELECT
								cliente.id,
								CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
								CONCAT_WS(' ', cliente.nombre, cliente.apellido) AS nombre_cliente
							FROM cj_cc_cliente cliente %s;", $sqlBusq);
	$rsCliente = mysql_query($queryCliente);
	if (!$rsCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowCliente = mysql_fetch_assoc($rsCliente);	
		
	$objResponse->assign("txtIdCliente","value",$rowCliente['id']);
	$objResponse->assign("txtNombreCliente","value",utf8_encode($rowCliente['nombre_cliente']));
		
	$objResponse->script("byId('btnCancelarLista').click();");//si viene del listado
		
	return $objResponse;
}

function buscarCliente($frmBuscarCliente) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarCliente['txtCriterioBuscarCliente']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
		
	return $objResponse;
}

function buscarHistorial($valForm) {
    $objResponse = new xajaxResponse();

    $fecha1 = $valForm['txtFechaDesde'];
    $fecha2 = $valForm['txtFechaHasta'];

    $fechaDesde = date("Y/m/d",strtotime($fecha1));
    $fechaHasta = date("Y/m/d",strtotime($fecha2));
    
    $valBusq = sprintf("%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
        $valForm['txtIdCliente'],
        $valForm['txtCriterio']
    );
    
    if ($fechaDesde > $fechaHasta){
        $objResponse->script('alert("La primera fecha no debe ser mayor a la segunda")');
    }else{
       $objResponse->loadCommands(listadoHistorial('0','','ASC',$valBusq));
    }
    
    return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	global $spanClienteCxC;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("status = 'Activo'");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', lci, ci) LIKE %s
		OR CONCAT_WS('', lci, ci) LIKE %s
		OR CONCAT_Ws(' ', nombre, apellido) LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT
		cliente_emp.id_empresa,
		cliente.id,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		CONCAT_Ws(' ', cliente.nombre, cliente.apellido) AS nombre_cliente,
		cliente.credito
	FROM cj_cc_cliente cliente
		INNER JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente) %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaCliente", "10%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "18%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanClienteCxC));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "56%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cliente"));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "16%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Tipo de Pago"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarCliente('".$row['id']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['ci_cliente']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"5\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaCliente(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"5\">";
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

function listadoHistorial($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	
    global $spanPlaca;
    global $spanSerialCarroceria;
	global $spanKilometraje;
	
    $objResponse = new xajaxResponse();	       

    $valCadBusq = explode("|", $valBusq);
    $startRow = $pageNum * $maxRows;

    //$valCadBusq[0] id empresa
    //$valCadBusq[1] fecha desde
    //$valCadBusq[2] fecha hasta
    //$valCadBusq[3] id cliente
    //$valCadBusq[4] criterio

    //$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
    //$sqlBusq .= $cond.sprintf("orden.id_estado_orden NOT IN (18,21,24)");

    if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("sa_orden.id_empresa = %s",
                    valTpDato($valCadBusq[0], "int"));
    }

    if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf("DATE(sa_orden.tiempo_orden) BETWEEN %s AND %s",
                    valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
                    valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
    }

    if ($valCadBusq[3] != "" && $valCadBusq[3] != " ") {
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond.sprintf("sa_orden.id_cliente = %s",
                valTpDato($valCadBusq[3],"int"),
                valTpDato($valCadBusq[3],"int"));
    }

    if ($valCadBusq[4] != "" && $valCadBusq[4] != " ") {
        $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
        $sqlBusq .= $cond.sprintf("(en_registro_placas.placa = %s OR
									en_registro_placas.chasis = %s)",
                valTpDato($valCadBusq[4],"text"),
                valTpDato($valCadBusq[4],"text"));
    }

    $query = sprintf("SELECT
					sa_orden.id_orden,
					sa_orden.numero_orden,
					sa_tipo_orden.nombre_tipo_orden,
					sa_estado_orden.nombre_estado,
					sa_orden.tiempo_orden,
					sa_orden.tiempo_finalizado,
					sa_recepcion.id_recepcion,
					CONCAT_WS(' ', cj_cc_cliente.nombre, cj_cc_cliente.apellido) AS nombre_cliente,
					CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_asesor,
					sa_recepcion.kilometraje as kilometraje_vale,
					an_uni_bas.nom_uni_bas,
					an_modelo.nom_modelo,
					an_marca.nom_marca,
					an_ano.nom_ano,
					en_registro_placas.id_registro_placas,
					en_registro_placas.placa,
					en_registro_placas.chasis,
					en_registro_placas.kilometraje as kilometraje_vehiculo,
					en_registro_placas.color
			FROM sa_orden
					INNER JOIN sa_recepcion ON (sa_orden.id_recepcion = sa_recepcion.id_recepcion)                
					INNER JOIN sa_cita ON sa_recepcion.id_cita = sa_cita.id_cita                
					INNER JOIN en_registro_placas ON sa_cita.id_registro_placas = en_registro_placas.id_registro_placas
					INNER JOIN an_uni_bas ON en_registro_placas.id_unidad_basica = an_uni_bas.id_uni_bas
					INNER JOIN an_modelo ON an_uni_bas.mod_uni_bas = an_modelo.id_modelo
					INNER JOIN an_marca ON an_uni_bas.mar_uni_bas = an_marca.id_marca
					INNER JOIN an_ano ON an_uni_bas.ano_uni_bas = an_ano.id_ano
					INNER JOIN sa_tipo_orden ON sa_orden.id_tipo_orden = sa_tipo_orden.id_tipo_orden
					INNER JOIN sa_estado_orden ON sa_orden.id_estado_orden = sa_estado_orden.id_estado_orden
					INNER JOIN cj_cc_cliente ON cj_cc_cliente.id = sa_orden.id_cliente
					INNER JOIN pg_empleado ON sa_orden.id_empleado = pg_empleado.id_empleado            
            %s
			ORDER BY en_registro_placas.id_registro_placas, id_orden ASC", $sqlBusq); 

    //$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";

    $queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
    $rsLimit = mysql_query($queryLimit);
    if (!$rsLimit) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryLimit); }

    if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
            $totalRows = mysql_num_rows($rs);
    }
    $totalPages = ceil($totalRows/$maxRows)-1;

	$html = "";
	$arrayIdRegistroPlaca = array();

    $contFila = 0;
    while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
					
		if(!array_key_exists($row["id_registro_placas"], $arrayIdRegistroPlaca)){//ES UNA UNIDAD A LA VEZ						
			//TABLA UNIDAD
			$html .= "<table width=\"100%\">";
				$html .= "<tr class=\"trResaltarTotal textoNegrita_12px\" height=\"24\">";
					$html .= "<td colspan=\"20\">Unidad</td>";
				$html .= "</tr>";
				$html .= "<tr>";
					$html .= "<td width=\"8%\" align=\"right\" class=\"tituloCampo\">Unidad:</td>";
					$html .= "<td width=\"20%\" align=\"left\">".utf8_encode($row["nom_uni_bas"])."</td>";
					$html .= "<td width=\"8%\" align=\"right\" class=\"tituloCampo\">Marca:</td>";
					$html .= "<td width=\"20%\" align=\"left\">".utf8_encode($row["nom_marca"])."</td>";
					$html .= "<td width=\"8%\" align=\"right\" class=\"tituloCampo\">Modelo:</td>";
					$html .= "<td width=\"15%\" align=\"left\">".utf8_encode($row["nom_modelo"])."</td>";
					$html .= "<td width=\"8%\" align=\"right\" class=\"tituloCampo\">Año:</td>";
					$html .= "<td align=\"left\">".utf8_encode($row["nom_ano"])."</td>";
				$html .= "</tr>";
				$html .= "<tr>";
					$html .= "<td align=\"right\" class=\"tituloCampo\">".$spanPlaca.":</td>";
					$html .= "<td align=\"left\">".utf8_encode($row["placa"])."</td>";
					$html .= "<td align=\"right\" class=\"tituloCampo\">".$spanSerialCarroceria.":</td>";
					$html .= "<td align=\"left\">".utf8_encode($row["chasis"])."</td>";
					$html .= "<td align=\"right\" class=\"tituloCampo\">Color:</td>";
					$html .= "<td align=\"left\">".utf8_encode($row["color"])."</td>";
					$html .= "<td align=\"right\" class=\"tituloCampo\">".$spanKilometraje.":</td>";
					$html .= "<td align=\"left\">".$row["kilometraje_vehiculo"]."</td>";
				$html .= "</tr>";
			$html .= "</table>";
		}
		
		$arrayIdRegistroPlaca[$row["id_registro_placas"]] = "";
		
		//TABLA ORDEN
		$html .= "<table width=\"100%\">";
			$html .= "<tr class=\"tituloColumna\" align=\"center\">";
				$html .= "<td width=\"6%\">Nro</td>";
				$html .= "<td width=\"15%\">Tipo</td>";                   
				$html .= "<td width=\"15%\">Estado</td>";
				$html .= "<td width=\"10%\">Apertura</td>";
				$html .= "<td width=\"10%\">Cierre</td>";
				$html .= "<td width=\"10%\">".$spanKilometraje."</td>";
				$html .= "<td>Asesor</td>";
			$html .= "</tr>";
			$html .= "<tr align=\"center\">";
				$html .= "<td>".$row["numero_orden"]."</td>";
				$html .= "<td>".utf8_encode($row["nombre_tipo_orden"])."</td>";
				$html .= "<td>".utf8_encode($row["nombre_estado"])."</td>";
				$html .= "<td>".tiempoComun($row["tiempo_orden"])."</td>";
				$html .= "<td>".tiempoComun($row["tiempo_finalizado"])."</td>";
				$html .= "<td>".$row["kilometraje_vale"]."</td>";
				$html .= "<td>".utf8_encode($row["nombre_asesor"])."</td>";
			$html .= "</tr>";
		$html .= "</table>";
				
		//TABLA FALLAS
		$html .= "<table width=\"100%\">";
			/*$html .= "<tr class=\"tituloColumna\">";
				$html .= "<td>Falla</td>";
				$html .= "<td>Diagnóstico</td>";
				$html .= "<td>Respuesta</td>";
			$html .= "</tr>";*/

		$queryFallas = sprintf("SELECT 
									descripcion_falla, 
									diagnostico_falla, 
									respuesta_falla
								FROM sa_recepcion_falla
								WHERE id_recepcion = %s", $row["id_recepcion"]);
    	$rs = mysql_query($queryFallas);
    	if (!$rs) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFallas); }
		
		while($rowFallas = mysql_fetch_assoc($rs)){
			$html .= "<tr>";
				$html .= "<td width=\"33%\">".utf8_encode($rowFallas["descripcion_falla"])."</td>";
				$html .= "<td width=\"33%\">".utf8_encode($rowFallas["diagnostico_falla"])."</td>";
				$html .= "<td width=\"33%\">".utf8_encode($rowFallas["respuesta_falla"])."</td>";
			$html .= "</tr>";
		}
		$html .= "</table>";
		

		//ITEMS DE LA ORDEN
		$idDocumento = $row["id_orden"];
		$arrayItems = array();
		
		$queryRepuestosGenerales = sprintf("SELECT
			sa_det_orden_articulo.cantidad,
			sa_det_orden_articulo.precio_unitario,
			vw_iv_articulos.codigo_articulo,
			vw_iv_articulos.descripcion
		FROM
			vw_iv_articulos
			INNER JOIN sa_det_orden_articulo ON (vw_iv_articulos.id_articulo = sa_det_orden_articulo.id_articulo)
		WHERE
			sa_det_orden_articulo.id_orden = %s 
			AND sa_det_orden_articulo.estado_articulo <> 'DEVUELTO' 
			AND sa_det_orden_articulo.aprobado = 1",
		valTpDato($idDocumento,"int"));
		
		$rsOrdenDetRep = mysql_query($queryRepuestosGenerales);
    	if (!$rsOrdenDetRep) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryRepuestosGenerales); }
		
		while($rowOrdenDetRep = mysql_fetch_assoc($rsOrdenDetRep)){			
			$arrayItems[] = array(
							'tipo' => 'REP',
							'codigo' => elimCaracter($rowOrdenDetRep['codigo_articulo'],";"),
							'descripcion' => $rowOrdenDetRep['descripcion'],
							'cantidad' => number_format($rowOrdenDetRep['cantidad'],2,".",","),
							'mecanico' => '');
		}
		
		$queryFactDetTemp = sprintf("SELECT
			sa_tempario.codigo_tempario,
			sa_tempario.descripcion_tempario,
			sa_det_orden_tempario.id_modo,
			CONCAT_WS(' ', pg_empleado.nombre_empleado, pg_empleado.apellido) AS nombre_mecanico,
			(case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut * sa_det_orden_tempario.precio_tempario_tipo_orden / sa_det_orden_tempario.base_ut_precio when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS total_por_tipo_orden,
			(case sa_det_orden_tempario.id_modo when '1' then sa_det_orden_tempario.ut when '2' then sa_det_orden_tempario.precio when '3' then sa_det_orden_tempario.costo when '4' then '4' end) AS precio_por_tipo_orden,
			sa_det_orden_tempario.precio_tempario_tipo_orden,
			
			IFNULL(sa_det_orden_tempario.id_mecanico, 0) AS id_mecanico
		FROM
			sa_det_orden_tempario
			INNER JOIN sa_tempario ON (sa_det_orden_tempario.id_tempario = sa_tempario.id_tempario)
			INNER JOIN sa_modo ON (sa_det_orden_tempario.id_modo = sa_modo.id_modo)
			LEFT JOIN sa_mecanicos ON sa_det_orden_tempario.id_mecanico = sa_mecanicos.id_mecanico
			LEFT JOIN pg_empleado ON sa_mecanicos.id_empleado = pg_empleado.id_empleado
		WHERE
			sa_det_orden_tempario.id_orden = %s  
			AND sa_det_orden_tempario.estado_tempario <> 'DEVUELTO'
		ORDER BY
			sa_det_orden_tempario.id_paquete",
			valTpDato($idDocumento,"int"));
			
		$rsFactDetTemp = mysql_query($queryFactDetTemp);
    	if (!$rsFactDetTemp) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryFactDetTemp); }
		
		while($rowFactDetTemp = mysql_fetch_assoc($rsFactDetTemp)){
			$caractCantTempario = ($rowFactDetTemp['id_modo'] == 1) ? number_format($rowFactDetTemp['precio_por_tipo_orden']/100,2,".",",") : number_format(1,2,".",",");
						
			$arrayItems[] = array(
							'tipo' => 'MO',
							'codigo' => $rowFactDetTemp['codigo_tempario'],
							'descripcion' => $rowFactDetTemp['descripcion_tempario'],
							'cantidad' => $caractCantTempario,
							'mecanico' => $rowFactDetTemp['nombre_mecanico']);
		}
		
		$queryDetalleTot = sprintf("SELECT
			sa_orden_tot.monto_subtotal,
			sa_det_orden_tot.id_orden_tot,
			sa_orden_tot.numero_tot,
			sa_orden_tot.observacion_factura,
			sa_det_orden_tot.porcentaje_tot
		FROM
			sa_det_orden_tot
			INNER JOIN sa_orden_tot ON (sa_det_orden_tot.id_orden_tot = sa_orden_tot.id_orden_tot)
		WHERE
			sa_det_orden_tot.id_orden = %s 
			AND sa_orden_tot.monto_subtotal > 0",
			valTpDato($idDocumento,"int"));
		
		$rsDetalleTot = mysql_query($queryDetalleTot);
    	if (!$rsDetalleTot) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryDetalleTot); }
		
		while($rowDetalleTot = mysql_fetch_assoc($rsDetalleTot)){
			$arrayItems[] = array(
							'tipo' => 'TOT',
							'codigo' => $rowDetalleTot['numero_tot'],
							'descripcion' => $rowDetalleTot['observacion_factura'],
							'cantidad' => '-',
							'mecanico' => '');
		}
		
		$queryDetTipoDocNotas = sprintf("SELECT
			sa_det_orden_notas.descripcion_nota,
			sa_det_orden_notas.precio,
			sa_det_orden_notas.id_det_orden_nota
		FROM
			sa_det_orden_notas
		WHERE
			sa_det_orden_notas.id_orden = %s",
			valTpDato($idDocumento,"int"));
			
		$rsDetTipoDocNotas = mysql_query($queryDetTipoDocNotas);
    	if (!$rsDetTipoDocNotas) { return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery: ".$queryDetTipoDocNotas); }
		
		while($rowDetTipoDocNotas = mysql_fetch_assoc($rsDetTipoDocNotas)){
			$arrayItems[] = array(
							'tipo' => 'Nota/Cargo',
							'codigo' => $rowDetTipoDocNotas['id_det_orden_nota'],
							'descripcion' => $rowDetTipoDocNotas['descripcion_nota'],
							'cantidad' => '-',
							'mecanico' => '');
		}
		
		
		$html .= "<table width=\"100%\">";
			/*$html .= "<tr class=\"tituloColumna\">";
				$html .= "<td>Tipo</td>";
				$html .= "<td>Código</td>";
				$html .= "<td>Descripción</td>";
				$html .= "<td>Cantidad</td>";
				$html .= "<td>Técnico</td>";
			$html .= "</tr>";*/

		foreach($arrayItems as $indice => $items){
			$clase = (fmod($indice, 2) == 0) ? "trResaltar5" : "trResaltar4";
						
			$html .= "<tr class=\"".$clase."\">";
				$html .= "<td width=\"6%\">".$items["tipo"]."</td>";
				$html .= "<td width=\"15%\">".utf8_encode($items["codigo"])."</td>";
				$html .= "<td width=\"40%\">".utf8_encode($items["descripcion"])."</td>";
				$html .= "<td width=\"5%\">".$items["cantidad"]."</td>";
				$html .= "<td>".utf8_encode($items["mecanico"])."</td>";
			$html .= "</tr>";
		}
		$html .= "</table>";
		
		//FINAL RAYA TABLA		
		$html .= "<table width=\"100%\">";
			$html .= "<tr><td colspan=\"20\"><hr /></td></tr>";
		$html .= "</table>";

    }

    $htmlTf = "<tr>";
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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoHistorial(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum > 0) { 
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoHistorial(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"100\">";

                                                    $htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoHistorial(%s,'%s','%s','%s',%s)\">",
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
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoHistorial(%s,'%s','%s','%s',%s);\">%s</a>",
                                                            min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
                                            }
                                            $htmlTf .= "</td>";
                                            $htmlTf .= "<td width=\"25\">";
                                            if ($pageNum < $totalPages) {
                                                    $htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoHistorial(%s,'%s','%s','%s',%s);\">%s</a>",
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

    $objResponse->assign("divListaHistorial","innerHTML",$htmlTblIni.$htmlTf.$html.$htmlTf.$htmlTblFin);

    return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"buscarHistorial");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listadoHistorial");
