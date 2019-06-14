<?php

function exportarExcel($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf('%s|%s|%s|%s|%s|%s',
					$valForm['txtCriterio'],
					$valForm['selEmpresa'],
					$valForm['selAsesor'],
					$valForm['selEstatus'],
					$valForm['txtFecha1'],
					$valForm['txtFecha2']
				  );
	
				
	if($valForm['txtFecha1'] == "" || $valForm['txtFecha2'] == ""){
    	return $objResponse->alert("Debe seleccionar fecha");
	}

	if (strtotime($valForm['txtFecha1']) > strtotime($valForm['txtFecha2'])){
    	return $objResponse->alert("La primera fecha no debe ser mayor a la segunda");
	}
	
	$objResponse->script("window.open('reportes/sa_listado_citas_excel.php?valBusq=".$valBusq."','_self');");			
	
	return $objResponse;
}

function buscarUnidadFisica($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf('%s|%s|%s|%s|%s|%s',
					$valForm['txtCriterio'],
					$valForm['selEmpresa'],
					$valForm['selAsesor'],
					$valForm['selEstatus'],
					$valForm['txtFecha1'],
					$valForm['txtFecha2']
				  );
	
	$objResponse->script(sprintf("xajax_listadoUnidadFisica(0,'hora_inicio_cita','ASC','%s');",
		$valBusq
                ));
	
	return $objResponse;
}

function listadoUnidadFisica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 15, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	global $spanRIF;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
        
	if($valCadBusq[0] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf(" (CONCAT_WS(' ',nombre,apellido) LIKE %s 
                                       OR cedula_cliente LIKE %s
                                       OR placa LIKE %s
                                       )",
            valTpDato("%".$valCadBusq[0]."%", "text"),
            valTpDato("%".$valCadBusq[0]."%", "text"),
            valTpDato("%".$valCadBusq[0]."%", "text")
            );
	}
	
        if ($valCadBusq[1] != ""){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .=  $cond." vw_sa_consulta_citas.id_empresa = '".$valCadBusq[1]."'";		
        }
        
	if ($valCadBusq[2] != 0){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."id_empleado_servicio = '".$valCadBusq[2]."'";
        }
        
        if ($valCadBusq[3] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond."estado_cita = '".$valCadBusq[3]."' ";
        }
        
        if ($valCadBusq[4] != '' && $valCadBusq[5] != ''){
            $cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
            $sqlBusq .= $cond.sprintf(" fecha_cita BETWEEN %s AND %s",
            valTpDato(date("Y-m-d",strtotime($valCadBusq[4])), "text"),
            valTpDato(date("Y-m-d",strtotime($valCadBusq[5])), "text")
            );
        }
		
	$query = sprintf("SELECT vw_sa_consulta_citas.*, pg_empresa.nombre_empresa FROM vw_sa_consulta_citas
                        LEFT JOIN pg_empresa ON vw_sa_consulta_citas.id_empresa = pg_empresa.id_empresa
                        %s",$sqlBusq);
        	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);       
	if(!$rsLimit){ return $objResponse->alert("Error: ".mysql_error()."\n\nNro Error: ".mysql_errno()."\n\nLinea: ".__LINE__."\n\nQuery: ".$queryLimit); }
	
	if ($totalRows == NULL) {
            $rs = mysql_query($query);
            if(!$rs){ return $objResponse->alert("Error: ".mysql_error()."\n\nNro Error: ".mysql_errno()."\n\nLinea: ".__LINE__."\n\nQuery: ".$query); }
            $totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "10%", $pageNum, "nombre_empresa_sucursal", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa Sucursal");
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "5%", $pageNum, "fecha_cita", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "5%", $pageNum, "hora_inicio_cita_12", $campOrd, $tpOrd, $valBusq, $maxRows, "Hora");
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "3%", $pageNum, "diferencia_fecha", $campOrd, $tpOrd, $valBusq, $maxRows, "Diferencia");
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "6%", $pageNum, "estado_cita", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado Cita");
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "10%", $pageNum, "empresa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanCI.' - '.$spanRIF);
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "15%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Cliente");
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "10%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "Tel&eacute;fono");
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "5%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, "Placa");
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "10%", $pageNum, "nom_modelo", $campOrd, $tpOrd, $valBusq, $maxRows, "Modelo");
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "10%", $pageNum, "descripcion_submotivo", $campOrd, $tpOrd, $valBusq, $maxRows, "Motivo de la Visita");
            $htmlTh .= ordenarCampo("xajax_listadoUnidadFisica", "10%", $pageNum, "asesor", $campOrd, $tpOrd, $valBusq, $maxRows, "Asesor");            
         $htmlTh .= "</tr>";
		
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = ($clase == "trResaltar4") ? "trResaltar7" : "trResaltar4";
                $contFila++;
				
		if($row["selecciono_fecha"] == 0){// 1 la escogio el cliente, 0 la otorgo citas y por lo tanto se contabiliza esa
                    $diferenciaFechaTexto = $row['diferencia_fecha'];	
		}else{
                    $diferenciaFechaTexto = "-";	
		}
		
		$htmlTb.= "<tr class=\"".$clase."\" title=\"id_cita:".$row['id_cita']."\">";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y", strtotime($row['fecha_cita']))."</td>";
			$htmlTb .= "<td align=\"center\">".$row['hora_inicio_cita_12']."</td>";
			$htmlTb .= "<td align=\"center\">".$diferenciaFechaTexto."</td>";
			$htmlTb .= "<td align=\"center\">".$row['estado_cita']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cedula_cliente']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre'])." ".utf8_encode($row['apellido'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['telf']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['placa']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nom_modelo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['descripcion_submotivo'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['asesor'])."</td>";
		$htmlTb .= "</tr>";
		
		if($row['origen_cita'] == 'PROGRAMADA' && $row['estado_cita'] != 'CANCELADA' && $row['estado_cita'] != 'POSPUESTA'){
			$cont++;//TOTAL CITAS PROGRAMADA
			if($row["selecciono_fecha"] == 0){ $diferenciaFechas += $row['diferencia_fecha']; }
		}
		
		if($row['origen_cita'] == 'ENTRADA' && $row['estado_cita'] != 'CANCELADA' && $row['estado_cita'] != 'POSPUESTA'){
			$contAux++;//TOTAL CITAS ENTRADA
			if($row["selecciono_fecha"] == 0){ $diferenciaFechas += $row['diferencia_fecha']; }
		}
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"20\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoUnidadFisica(%s,'%s','%s','%s',%s)\">",
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_serv.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoUnidadFisica(%s,'%s','%s','%s',%s);\">%s</a>",
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
		$htmlTb .= "<td colspan=\"20\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td></td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
		$objResponse->assign("tdListadoProveedor","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	
	$htmlResumen .= "<table align=\"center\" class=\"tabla\" border=\"1\" width = \"500\">";
	$htmlResumen .= "<tr class=\"tituloColumna\">";
		$htmlResumen .= "<td colspan=\"2\">Total Resumen del Dia</td>";	
	$htmlResumen .= "</tr>";
	$htmlResumen .= "<tr>";
		$htmlResumen .= "<td width = \"250\" class=\"tituloCampo\"><strong>Total Entrada</strong></td>";
		$htmlResumen .= "<td>".number_format($contAux,0,".",",")."</td>";
	$htmlResumen .= "</tr>";
	$htmlResumen .= "<tr>";
		$htmlResumen .= "<td width = \"250\" class=\"tituloCampo\"><strong>Total Dias de Diferencia</strong></td>";
		$htmlResumen .= "<td>".number_format($diferenciaFechas,0,".",",")."</td>";
	$htmlResumen .= "</tr>";
	$htmlResumen .= "<tr>";
		$htmlResumen .= "<td width = \"250\" class=\"tituloCampo\"><strong>Total Citas</strong></td>";
		$htmlResumen .= "<td>".number_format($cont,0,".",",")."</td>";
	$htmlResumen .= "</tr>";
	$htmlResumen .= "</table>";
	
	$objResponse->assign("tdResumen","innerHTML",$htmlResumen);
	
	return $objResponse;
}

function comboEmpresa($valForm){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT * FROM vw_iv_usuario_empresa WHERE id_usuario = %s ORDER BY nombre_empresa",
			valTpDato($_SESSION['idUsuarioSysGts'],"int"));
		$rs = mysql_query($query);
		if(!$rs){ return $objResponse->alert("Error: ".mysql_error()."\n\nNro Error: ".mysql_errno()."\n\nLinea: ".__LINE__."\n\nQuery: ".$query); }
		$html = "<select id=\"selEmpresa\" name=\"selEmpresa\" onChange=\"xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar')); actualizarAsesor();\">";
		//$html .="<option value=\"0\">Todas</option>";
		while ($row = mysql_fetch_assoc($rs)) {
			$nombreSucursal = "";
			if ($row['id_empresa_padre_suc'] > 0)
				$nombreSucursal = " - ".$row['nombre_empresa_suc']." (".$row['sucursal'].")";
			
			$selected = "";
			if ($selId == $row['id_empresa_reg'] || $_SESSION['idEmpresaUsuarioSysGts'] == $row['id_empresa_reg'])
				$selected = "selected='selected'";
		
			$html .= "<option ".$selected." value=\"".$row['id_empresa_reg']."\">".utf8_encode($row['nombre_empresa'].$nombreSucursal)."</option>";
		}
		$html .= "</select>";
	
		$objResponse->assign("tdSelEmpresa","innerHTML",$html);
	
	return $objResponse;
}

function comboAsesor($valForm){
	$objResponse = new xajaxResponse();
	
	if($valForm['selEmpresa'] == "-1" || $valForm['selEmpresa'] == ""){
			$empresa = $_SESSION["idEmpresaUsuarioSysGts"];
		}else{
			$empresa = $valForm['selEmpresa'];
		}
	//var_dump($empresa);
	$query = sprintf("SELECT * FROM sa_v_asesores_servicio WHERE id_empresa = $empresa");
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert("Error: ".mysql_error()."\n\nNro Error: ".mysql_errno()."\n\nLinea: ".__LINE__."\n\nQuery: ".$query); }
	$html = "<select id=\"selAsesor\" name=\"selAsesor\" onChange=\"xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar'))\">";
		$html .="<option value=\"0\">Seleccione</option>";
	while ($row = mysql_fetch_array($rs)){
		$html .= "<option  value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_completo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdSelAsesor","innerHTML",$html);
	
	return $objResponse;
}

function comboEstatus($valForm){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT DISTINCT estado_cita FROM sa_cita");
	$rs = mysql_query($query);
	if(!$rs){ return $objResponse->alert("Error: ".mysql_error()."\n\nNro Error: ".mysql_errno()."\n\nLinea: ".__LINE__."\n\nQuery: ".$query); }
	$html = "<select id=\"selEstatus\" name=\"selEstatus\" onChange=\"xajax_buscarUnidadFisica(xajax.getFormValues('frmBuscar'))\">";
        $html .="<option value=\"\">TODOS</option>";
	while ($row = mysql_fetch_array($rs)){
		$html .= "<option  value=\"".$row['estado_cita']."\">".utf8_encode($row['estado_cita'])."</option>";
	}
	$html .= "</select>";

	$objResponse->assign("tdSelEstatus","innerHTML",$html);

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscarUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"listadoUnidadFisica");
$xajax->register(XAJAX_FUNCTION,"comboEmpresa");
$xajax->register(XAJAX_FUNCTION,"comboAsesor");
$xajax->register(XAJAX_FUNCTION,"comboEstatus");
$xajax->register(XAJAX_FUNCTION,"exportarExcel");

?>