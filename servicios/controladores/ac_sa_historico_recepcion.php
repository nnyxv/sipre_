<?php

function exportarExcel($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['lstEmpleado'],
		$valForm['txtCriterio']);
		
	if($valForm['txtFechaDesde'] == "" || $valForm['txtFechaHasta'] == ""){
    	return $objResponse->alert("Debe seleccionar fecha");
	}

	if (strtotime($valForm['txtFechaDesde']) > strtotime($valForm['txtFechaHasta'])){
    	return $objResponse->alert("La primera fecha no debe ser mayor a la segunda");
	}
	
	$objResponse->script("window.open('reportes/sa_historico_recepcion_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function buscar($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['lstEmpleado'],
		$valForm['txtCriterio']);
	
	$objResponse->loadCommands(listado(0, "id_recepcion", "DESC", $valBusq));
	
	return $objResponse;
}


function cargaLstEmpleado($selId = "", $nombreObjeto = "", $objetoDestino = "",$idEmpresa = "") {
	$objResponse = new xajaxResponse();
	
	if($idEmpresa == ""){
		$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];	
	}
		
	$query = sprintf("SELECT DISTINCT id_empleado, nombre_empleado
	FROM vw_pg_empleados
		WHERE id_empresa = %s AND clave_filtro = 5 AND activo = 1
	ORDER BY nombre_empleado",
		$idEmpresa);
		
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__."\n\nQuery:".$query);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\">";
		$html .= "<option value=\"\">[ Todos ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = "";
		if ($selId == $row['id_empleado'])
			$selected = "selected='selected'";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
		
	}
	$html .= "</select>";
	$objResponse->assign($objetoDestino,"innerHTML",$html);
	
	return $objResponse;
}

function exportar($valForm) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s",
		$valForm['lstEmpresa'],
		$valForm['txtFechaDesde'],
		$valForm['txtFechaHasta'],
		$valForm['lstEmpleado'],
		$valForm['txtCriterio']);
	
	$objResponse->script("window.open('reportes/sa_orden_servicio_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function listado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
		
	global $spanKilometraje;
	global $spanPlaca;
	
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if($valCadBusq[0] == ""){
		$valCadBusq[0] = $_SESSION['idEmpresaUsuarioSysGts'];
	}
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("recep.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("recep.fecha_entrada BETWEEN %s AND %s",
			valTpDato(date("Y-m-d", strtotime($valCadBusq[1])),"date"),
			valTpDato(date("Y-m-d", strtotime($valCadBusq[2])),"date"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("recep.id_empleado_servicio = %s",
			valTpDato($valCadBusq[3],"int"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(IF(id_cliente_pago IS NULL, 
					CONCAT_WS(' ', recep.apellido, recep.nombre), 
					CONCAT_WS(' ', recep.apellido_pago, recep.nombre_pago)) LIKE %s
		OR recep.numeracion_recepcion LIKE %s
		OR recep.placa LIKE %s)",
			valTpDato("%".$valCadBusq[4]."%","text"),
			valTpDato("%".$valCadBusq[4]."%","text"),
			valTpDato("%".$valCadBusq[4]."%","text"));
	}

	$query = sprintf("SELECT 
				recep.id_recepcion,
				pg_empresa.nombre_empresa,
				recep.id_cita,
				recep.fecha_entrada,
				recep.hora_entrada,
				recep.placa,
				recep.numeracion_recepcion,
				recep.numero_entrada,
				recep.descripcion,
				recep.observaciones,
				recep.nro_llaves,
				(SELECT COUNT(*) FROM sa_recepcion_incidencia foto WHERE foto.id_cita = recep.id_cita AND foto.url_foto != '') AS nro_fotos,
				CONCAT_WS(' ', recep.apellido, recep.nombre) AS nombre_cliente_cita,
				CONCAT_WS(' ', recep.apellido_pago, recep.nombre_pago) AS nombre_cliente_pago,
				IF(id_cliente_pago IS NULL, 
					CONCAT_WS(' ', recep.apellido, recep.nombre), 
					CONCAT_WS(' ', recep.apellido_pago, recep.nombre_pago)) AS nombre_cliente
	FROM sa_v_historico_recepcion recep 
	INNER JOIN pg_empresa ON recep.id_empresa = pg_empresa.id_empresa
	%s", $sqlBusq);
		
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert($queryLimit."\n".mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		//$htmlTh .= ordenarCampo("xajax_listado", "16%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listado", "5%", $pageNum, "fecha_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Fecha");
		$htmlTh .= ordenarCampo("xajax_listado", "5%", $pageNum, "hora_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Hora");
		$htmlTh .= ordenarCampo("xajax_listado", "7%", $pageNum, "placa", $campOrd, $tpOrd, $valBusq, $maxRows, $spanPlaca);
		$htmlTh .= ordenarCampo("xajax_listado", "4%", $pageNum, "numeracion_recepcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro Recep.");
		$htmlTh .= ordenarCampo("xajax_listado", "2%", $pageNum, "numero_entrada", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro Ent.");
		$htmlTh .= ordenarCampo("xajax_listado", "8%", $pageNum, "descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Vale");
		$htmlTh .= ordenarCampo("xajax_listado", "20%", $pageNum, "nombre_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Cliente");
		$htmlTh .= ordenarCampo("xajax_listado", "30%", $pageNum, "observaciones", $campOrd, $tpOrd, $valBusq, $maxRows, "Observaciones");
		$htmlTh .= ordenarCampo("xajax_listado", "", $pageNum, "nro_llaves", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro Llaves");
							
		$htmlTh .= "<td colspan=\"12\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;

		$htmlTb.= "<tr class=\"".$clase."\" height=\"22\">";
			//$htmlTb .= "<td align=\"left\">".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".date("d-m-Y",strtotime($row['fecha_entrada']))."</td>";
			$htmlTb .= "<td align=\"center\">".date("H:i a",strtotime($row['hora_entrada']))."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['placa'])."</td>";
			$htmlTb .= "<td align=\"center\" title =\"id_recepcion: ".$row['id_recepcion']." id_cita: ".$row['id_cita']."\">".$row['numeracion_recepcion']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_entrada']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['descripcion']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nombre_cliente'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['observaciones'])."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['nro_llaves'])."</td>";
			
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Cambiar Tipo Vale\" src=\"../img/iconos/cc.png\" width=\"16\" border=\"0\" onclick=\"xajax_cargarTipoVale(%s);\" />",
				$row['id_recepcion']);
			$htmlTb .= "</td>";
			
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Editar Falla y Diagnostico\" src=\"../img/iconos/edit.png\" width=\"16\" border=\"0\" onclick=\"limpaiarFallas(); xajax_editarFallas(%s);\" />",
				$row['id_recepcion']);
			$htmlTb .= "</td>";
			
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Editar ".$spanKilometraje."\" src=\"../img/iconos/time_add.png\" width=\"16\" border=\"0\" onclick=\"limpiarKM(); xajax_editarKilometraje(%s);\" />",
				$row['id_recepcion']);
			$htmlTb .= "</td>";
			
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Ver Vale de Recepcion\" src=\"../img/iconos/view.png\" width=\"16\" border=\"0\" onclick=\"window.open('sa_vale_recepcion_imprimir.php?id=%s', '', 'height=700,width=900,scrollbars=1,toolbar=1');\" />",
				$row['id_recepcion']);
			$htmlTb .= "</td>";
			
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Ver Vale Diagnostico\" src=\"../img/iconos/diagnostico.png\" width=\"16\" border=\"0\" onclick=\"window.open('sa_vale_fallas.php?id=%s','','height=700,width=900,scrollbars=1,toolbar=1');\" />",
				$row['id_recepcion']);
			$htmlTb .= "</td>";
			
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Imprimir Vale de Recepcion\" src=\"../img/iconos/print.png\" width=\"16\" border=\"0\" onclick=\"window.open('sa_vale_recepcion_imprimir.php?view=print&id=%s','recep','height=700,width=900,scrollbars=1,toolbar=1');\" />",
				$row['id_recepcion']);
			$htmlTb .= "</td>";

			if($row["nro_fotos"] == 0){ $claseGris = "gris"; } else { $claseGris = ""; }
			//if($row["nro_fotos"] > 0){ $spanNroFotos = "<span class='nroFotos'>".$row["nro_fotos"]."</span>"; } else { $spanNroFotos = ""; }
			
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero %s\" title=\"Ver Fotos de Incidencias\" src=\"../img/iconos/photo.png\" width=\"16\" border=\"0\" onclick=\"xajax_fotosIncidencias('%s','%s');\" /> %s",
				$claseGris,
				$row['id_recepcion'],
				$row['numeracion_recepcion'],
				$spanNroFotos
				);
			$htmlTb .= "</td>";
			
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Editar Inventario\" src=\"../img/iconos/edit_privilegios.png\" width=\"16\" border=\"0\" onclick=\"xajax_editarInventario(%s);\" />",
				$row['id_recepcion']);
			$htmlTb .= "</td>";
			
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Editar Incidencias\" src=\"../img/iconos/unidadesAsignadas.png\" border=\"0\" onclick=\"xajax_editarIncidencias(%s);\" />",
				$row['id_recepcion']);
			$htmlTb .= "</td>";
			
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= sprintf("<img class=\"puntero\" title=\"Editar Nro Llaves\" src=\"../img/iconos/key.png\" width=\"16\" border=\"0\" onclick=\"xajax_editarLlaves(%s);\" />",
				$row['id_recepcion']);
			$htmlTb .= "</td>";
			
		$htmlTb .= "</tr>";
		
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"25\">";
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
		$htmlTb .= "<td colspan=\"25\">";
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

function cargarTipoVale($idRecepcion){
            
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT numeracion_recepcion , id_tipo_vale
					  FROM sa_recepcion
					  WHERE id_recepcion = %s LIMIT 1",
					  $idRecepcion);
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }
   
	$row = mysql_fetch_assoc($rs);
	
	$numeroVale = $row["numeracion_recepcion"];
	$idTipoVale = $row["id_tipo_vale"];
	
	$query = "SELECT id_tipo_vale, descripcion FROM sa_tipo_vale WHERE activo = 1  ";
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }
	
	$html = "<select id='selectTipoVale' name='selectTipoVale'>";
	while($row = mysql_fetch_assoc($rs)){
		$selected = "";
		if($idTipoVale == $row["id_tipo_vale"]){
			$selected = "selected = 'selected'";
		}
		$html .= "<option value = '".$row["id_tipo_vale"]."' ".$selected.">";
			$html .= $row["descripcion"];
		$html .= "</option>";
	   
	}
	$html .= "</select>";
	
	$objResponse->assign("numeroVale","value",$numeroVale);
	$objResponse->assign("idValeRecepcion","value",$idRecepcion);
	$objResponse->assign("tdTipoVale","innerHTML",$html);
	$objResponse->script("$('#divFlotante').show();");
	$objResponse->script("centrarDiv(byId('divFlotante'));");
	
	return $objResponse;
}

function guardarTipoVale($formTipoVale){
            
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,PAGE_PRIV,editar)){
			return $objResponse;
	}
	
	$query = sprintf("UPDATE sa_recepcion SET id_tipo_vale = %s
						WHERE id_recepcion = %s",
						$formTipoVale["selectTipoVale"],
						$formTipoVale["idValeRecepcion"]);
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }
	
	$objResponse->alert("Actualizado Correctamente");
	$objResponse->script("$('#divFlotante').hide();");
	$objResponse->script("$('#btnBuscar').click();");
	
	return $objResponse;
}

function editarKilometraje($idRecepcion){

	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT 
							 sa_cita.id_registro_placas, 
							 en_registro_placas.kilometraje AS km_vehiculo,
							 sa_recepcion.kilometraje AS km_vale,
							 sa_recepcion.numeracion_recepcion
						FROM sa_recepcion
						INNER JOIN sa_cita 
						LEFT JOIN en_registro_placas ON en_registro_placas.id_registro_placas = sa_cita.id_registro_placas 
						WHERE sa_recepcion.id_recepcion = %s",
						$idRecepcion);
	
	$rs = mysql_query($query);                
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__); }

	$row = mysql_fetch_assoc($rs);

	$objResponse->assign("idValeRecepcionKm","value",$idRecepcion);
	$objResponse->assign("numeroValeKm","value",$row["numeracion_recepcion"]);	
	$objResponse->assign("kmActualValeRecepcion","value",$row["km_vale"]);
	$objResponse->assign("kmActualVehiculo","value",$row['km_vehiculo']);
	$objResponse->script("$('#divFlotante2').show();");
	$objResponse->script("centrarDiv(byId('divFlotante2'));");

	return $objResponse;
}

function guardarKilometraje($frmKilometraje){

	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,PAGE_PRIV,editar)){
			return $objResponse;
	}

	if(empty($frmKilometraje['kmNuevo']) || empty($frmKilometraje['idValeRecepcionKm'])){
		return $objResponse->alert("Debes especificar un Nro.");
	}

	$query = sprintf("UPDATE sa_recepcion SET kilometraje = %s WHERE id_recepcion = %s ",
				$frmKilometraje['kmNuevo'],
				$frmKilometraje['idValeRecepcionKm']);

	$rs = mysql_query($query);                
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }

	$objResponse->alert("Actualizado Correctamente");
	$objResponse->script("xajax_editarKilometraje(".$frmKilometraje['idValeRecepcionKm'].")");

	return $objResponse;
}

function editarFallas($idRecepcion){

	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT 
							 sa_recepcion.numeracion_recepcion,
							 falla.id_recepcion_falla,
							 falla.descripcion_falla,
							 falla.diagnostico_falla,
							 falla.respuesta_falla
						FROM sa_recepcion
						INNER JOIN sa_recepcion_falla falla ON (sa_recepcion.id_recepcion = falla.id_recepcion) 						
						WHERE sa_recepcion.id_recepcion = %s",
						$idRecepcion);
	
	$rs = mysql_query($query);                
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }

	$html = "<div class='scrollable'><table class='tablaFallas'>";
	while($row = mysql_fetch_assoc($rs)){
		$numeracionRecepcion = $row["numeracion_recepcion"];
		
		$boton = "<button onclick='quitarFalla(".$row['id_recepcion_falla'].", this);' type='button'><img border='0' src='../img/iconos/minus.png' width='14' alt='quitar'></button>";				
		$checkbox = "<input type='checkbox' style='display:none' checked='checked' name='idFalla[]' value='".$row['id_recepcion_falla']."' />";
		
		$html .= "<tr><td>".$checkbox."<b>F: </b><input type='text' name='descripcionFalla[]' value = '".utf8_encode($row['descripcion_falla'])."' /></td><td></td></tr>";
		$html .= "<tr><td><b>D: </b><input type='text' name='diagnosticoFalla[]' value = '".utf8_encode($row['diagnostico_falla'])."' /></td><td>".$boton."</td></tr>";
		$html .= "<tr><td><b>R: </b><input type='text' name='respuestaFalla[]' value = '".utf8_encode($row['respuesta_falla'])."' /></td><td></td></tr>";
		$html .= "<tr><td colspan='3'><br></td></tr>";
	}
	$html .= "</table></div>";
	
	$objResponse->assign("idValeRecepcionFalla","value",$idRecepcion);	
	$objResponse->assign("numeroValeFallas","value",$numeracionRecepcion);	
	$objResponse->assign("tdFallas","innerHTML",$html);	

	$objResponse->script("$('#divFlotante3').show();");
	$objResponse->script("centrarDiv(byId('divFlotante3'));");

	return $objResponse;
}

function guardarFallas($frmFallas){
	
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,PAGE_PRIV,editar)){
			return $objResponse;
	}

	foreach($frmFallas["descripcionFalla"] as $key => $value){
		if(strlen(trim($value)) == 0){
			return $objResponse->alert("La descripcion de la falla no puede ser vacio");
		}
	}
	
	mysql_query("START TRANSACTION");
	
	foreach($frmFallas["idFalla"] as $key => $idRecepcionFalla){		
		
		if($idRecepcionFalla != ""){
			
			$query = sprintf("UPDATE sa_recepcion_falla SET descripcion_falla = %s, diagnostico_falla = %s, respuesta_falla = %s,
			id_empleado_diagnostico = %s, tiempo_diagnostico = now()
							  WHERE id_recepcion_falla = %s",
							  valTpDato($frmFallas["descripcionFalla"][$key],"text"),
							  valTpDato($frmFallas["diagnosticoFalla"][$key],"text"),
							  valTpDato($frmFallas["respuestaFalla"][$key],"text"),
							  valTpDato($_SESSION["idEmpleadoSysGts"],"int"),
							  $idRecepcionFalla);
							  
			$rs = mysql_query($query); 
			if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }
		
		}else{
			
			$query = sprintf("INSERT INTO sa_recepcion_falla 
							(id_recepcion, descripcion_falla, diagnostico_falla, respuesta_falla, id_empleado_diagnostico, tiempo_diagnostico) VALUES
							 (%s, %s, %s, %s, %s, now())",
							  $frmFallas['idValeRecepcionFalla'],
							  valTpDato($frmFallas["descripcionFalla"][$key],"text"),
							  valTpDato($frmFallas["diagnosticoFalla"][$key],"text"),
							  valTpDato($frmFallas["respuestaFalla"][$key],"text"),
							  valTpDato($_SESSION["idEmpleadoSysGts"],"int"));
							  
			$rs = mysql_query($query); 
			if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }
		}
		
	}
	
	//borrando los que se mandaron a eliminar:
	$idFallasEliminar = implode(",",array_filter(explode("|",$frmFallas["idFallasEliminar"])));
	
	if($idFallasEliminar != ""){
		$query = sprintf("DELETE FROM sa_recepcion_falla WHERE id_recepcion_falla IN (%s)",
						$idFallasEliminar);
								  
		$rs = mysql_query($query); 
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }
	}
	
	mysql_query("COMMIT");
	
	$objResponse->alert("Actualizado Correctamente");
	$objResponse->script("xajax_editarFallas(".$frmFallas['idValeRecepcionFalla'].")");

	return $objResponse;
}

function fotosIncidencias($idRecepcion){
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT id_recepcion_incidencia, 
							 url_foto, 
							 tipo_incidencia,
							 numeracion_recepcion 
					  FROM sa_recepcion
					  INNER JOIN sa_recepcion_incidencia ON sa_recepcion.id_cita = sa_recepcion_incidencia.id_cita					   
 					  WHERE id_recepcion = %s AND url_foto IS NOT NULL AND url_foto != '' ",
					  $idRecepcion);
	$rs = mysql_query($query);
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }

	if(mysql_num_rows($rs)){
		
		$urlFotos = "<div class='scrollable'>";
		while($row = mysql_fetch_assoc($rs)){
			
			$numeracionRecepcion = $row['numeracion_recepcion'];
			
			$urlFotos .= sprintf('<a href="fotos/%s" TARGET="_blank" data-lightbox="idUnico%s" data-title="Tipo Incidencia - %s"><img width="150" height="150" src="fotos/%s" /></a>',
					  utf8_encode($row['url_foto']),
					  $row['id_recepcion_incidencia'],
					  $row['tipo_incidencia'],
					  utf8_encode($row['url_foto']));
		}
		$urlFotos .= "</div>";
		
		$objResponse->assign("numeroValeFoto","value",$numeracionRecepcion);
		$objResponse->assign("tdFotosIncidencias","innerHTML",$urlFotos);		
		$objResponse->script("$('#divFlotante4').show();");
		$objResponse->script("centrarDiv(byId('divFlotante4'));");
		
	}else{
		$objResponse->alert("Este vale no posee fotos de incidencias");
	}
	return $objResponse;
}

function editarInventario($idRecepcion){

	$objResponse = new xajaxResponse();

	$query = "SELECT
					id_art_inventario,
					descripcion,
					cantidad_definida
			  FROM sa_art_inventario
			  WHERE activo = 1";

	$rs = mysql_query($query);                
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }
	
	$html = "<div class='scrollable'>
			<table>								
				<tr class='tdsubcaption'>
					<td></td>
					<td>Descripci&oacute;n</td>
					<td title='Buen Estado'>B</td><td title='Estado Regular'>R</td><td title='Mal Estado'>M</td>
					<td>Cant.</td>
				</tr>";
				
	while($row = mysql_fetch_assoc($rs)){
		
		$n = $row['id_art_inventario'];
		$cantidad = $row['cantidad_definida'];
		
		$html.='<tr title="Marque en la casilla los elementos a inspeccionar, los dem&aacute;s ser&aacute;n ignorados" >
			<td align="center"><input type="checkbox" id="art'.$n.'" name="idArticulos[]" value="'.$n.'" onclick="activaCheckBox(this);" /></td>
			<td align="left">'.utf8_encode($row['descripcion']).'</td>
			<td align="center"><input name="estado'.$n.'[]" type="radio" id="estadoArtB'.$n.'" value="B" onclick="activaRadio(this);" /></td>
			<td align="center"><input name="estado'.$n.'[]" type="radio" id="estadoArtR'.$n.'" value="R" onclick="activaRadio(this);" /></td>
			<td align="center"><input name="estado'.$n.'[]" type="radio" id="estadoArtM'.$n.'" value="M" onclick="activaRadio(this);" /></td>			
			<td align="center"><input type="text" maxlength="4" id="cantidadArt'.$n.'" name="cantidadArticulos[]" style="width:30px;" value="'.$cantidad.'" onchange="activaCantidad(this)" onkeypress="return soloNumeros(event);" /></td>
			</tr>';
	}
	$html .= "</table></div>";
	
	
	$query = sprintf("SELECT sa_recepcion_inventario.id_recepcion_inventario, 
							 sa_recepcion_inventario.estado, 
							 sa_recepcion_inventario.cantidad,
							 sa_recepcion_inventario.id_art_inventario,
							 sa_recepcion.numeracion_recepcion,
							 sa_art_inventario.descripcion,
							 sa_recepcion.numeracion_recepcion
					  FROM sa_recepcion
					  LEFT JOIN sa_recepcion_inventario ON sa_recepcion.id_cita = sa_recepcion_inventario.id_cita					  
					  LEFT JOIN sa_art_inventario ON sa_recepcion_inventario.id_art_inventario = sa_art_inventario.id_art_inventario 
					  WHERE id_recepcion = %s",
					  $idRecepcion);
					  
	$rs = mysql_query($query);                
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }
	
	while($row = mysql_fetch_assoc($rs)){
		$numeracionRecepcion = $row["numeracion_recepcion"];
		$idArt = $row["id_art_inventario"];
		$estado = $row["estado"];
		$cantidadGuardada = $row["cantidad"];
		
		$script .= "$('#art".$idArt."').attr('checked', true);";
		$script2 .= "$('#estadoArt".strtoupper($estado).$idArt."').attr('checked', true);";
		$script3 .= "$('#cantidadArt".$idArt."').val(".$cantidadGuardada.");";
		$script4 .= "$('#art".$idArt."').parent().parent().css('background-color', '#b8dcff');";
		
	}
  	
	$objResponse->assign("idValeRecepcionInventario","value",$idRecepcion);
	$objResponse->assign("numeroValeInventario","value",$numeracionRecepcion);
	$objResponse->assign("tdInventario","innerHTML",$html);
	$objResponse->script("$('#divFlotante5').show();");
	$objResponse->script("centrarDiv(byId('divFlotante5'));");
	$objResponse->script($script);
	$objResponse->script($script2);
	$objResponse->script($script3);
	$objResponse->script($script4);

	return $objResponse;
}

function guardarIventario($frmInventario){
	$objResponse = new xajaxResponse();

	if (!xvalidaAcceso($objResponse,PAGE_PRIV,editar)){
			return $objResponse;
	}
		
	if($frmInventario["idArticulos"] == ""){
		return $objResponse->alert("Debes seleccionar por lo menos un item");
	}
	
	foreach($frmInventario["idArticulos"] as $key => $idArtInventario){
		if($frmInventario["estado".$idArtInventario][0] == ""){
			return $objResponse->alert("Debes seleccionar un estado para el item: B,R,M");
		}
		
		if(trim($frmInventario["cantidadArticulos"][$key]) == ""){
			return $objResponse->alert("Debes escribir la cantidad del item");
		}
	}
	 
	mysql_query("START TRANSACTION");
	
	$query = sprintf("SELECT id_cita FROM sa_recepcion WHERE id_recepcion = %s LIMIT 1",
					$frmInventario["idValeRecepcionInventario"]);
								  
	$rs = mysql_query($query); 
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }
	
	$row = mysql_fetch_assoc($rs);	
	$idCita = $row["id_cita"];
	
	$query = sprintf("DELETE FROM sa_recepcion_inventario WHERE id_cita = %s",
						$idCita);
								  
	$rs = mysql_query($query); 
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }
	
	
	foreach($frmInventario["idArticulos"] as $key => $idArtInventario){
		$query = sprintf("INSERT INTO sa_recepcion_inventario (id_art_inventario, id_cita, estado, cantidad) 
						  VALUES (%s, %s, %s, %s)",	
						  $idArtInventario,				
		    			  $idCita,
						  valTpDato($frmInventario["estado".$idArtInventario][0],"text"),
						  valTpDato(trim($frmInventario["cantidadArticulos"][$key]),"int")						  
						);
						
		$rs = mysql_query($query); 
		if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }
		
		$insertId = mysql_insert_id();
		
	}
		  
	if($insertId == ""){
		return $objResponse->alert("No se pudo guardar");
	}
	
	mysql_query("COMMIT");
	
	$objResponse->alert("Guardado correctamente");
	$objResponse->script("xajax_editarInventario(".$frmInventario['idValeRecepcionInventario'].")");
	
	return $objResponse;
}


function editarIncidencias($idRecepcion){

	$objResponse = new xajaxResponse();
	
	$objResponse->script("window.open('sa_incidencias_vehiculo.php?idRecepcion=".$idRecepcion."','recep','height=700,width=900,scrollbars=1,toolbar=1,resizable=0');");
	

	return $objResponse;
}

function editarLlaves($idRecepcion){
	
	$objResponse = new xajaxResponse();
	
	$query = sprintf("SELECT numeracion_recepcion, nro_llaves FROM sa_recepcion WHERE id_recepcion = %s LIMIT 1",
					$idRecepcion);
  
	$rs = mysql_query($query); 
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }
	
	$row = mysql_fetch_assoc($rs);	
	
	$objResponse->assign("idValeRecepcionLlaves","value",$idRecepcion);
	$objResponse->assign("numeroValeLlaves","value",$row["numeracion_recepcion"]);
	$objResponse->assign("numeroLlaves","value",$row["nro_llaves"]);
	
	$objResponse->script("$('#divFlotante6').show();");
	$objResponse->script("centrarDiv(byId('divFlotante6'));");
	
	return $objResponse;
}

function guardarLlaves($frmLlaves){
	
	$objResponse = new xajaxResponse();

	$query = sprintf("UPDATE sa_recepcion SET nro_llaves = %s WHERE id_recepcion = %s LIMIT 1",
					valTpDato($frmLlaves["nroLlaves"],"text"),
					$frmLlaves["idValeRecepcionLlaves"]);
  
	$rs = mysql_query($query); 
	if(!$rs) { return $objResponse->alert(mysql_error()."\n\nLine:".__LINE__."\n\nQuery:".$query); }
	
	$objResponse->alert("Actualizado Correctamente");
	$objResponse->script("$('#divFlotante6').hide();");
	$objResponse->script("$('#btnBuscar').click();");
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"buscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstEmpleado");
$xajax->register(XAJAX_FUNCTION,"exportarExcel");
$xajax->register(XAJAX_FUNCTION,"listado");
$xajax->register(XAJAX_FUNCTION,"cargarTipoVale");
$xajax->register(XAJAX_FUNCTION,"guardarTipoVale");
$xajax->register(XAJAX_FUNCTION,"editarKilometraje");
$xajax->register(XAJAX_FUNCTION,"guardarKilometraje");
$xajax->register(XAJAX_FUNCTION,"editarFallas");
$xajax->register(XAJAX_FUNCTION,"guardarFallas");
$xajax->register(XAJAX_FUNCTION,"fotosIncidencias");
$xajax->register(XAJAX_FUNCTION,"editarInventario");
$xajax->register(XAJAX_FUNCTION,"guardarIventario");
$xajax->register(XAJAX_FUNCTION,"editarIncidencias");
$xajax->register(XAJAX_FUNCTION,"editarLlaves");
$xajax->register(XAJAX_FUNCTION,"guardarLlaves");


?>