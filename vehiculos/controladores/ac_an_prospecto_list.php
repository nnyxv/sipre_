<?php


function asignarEmpleado($objDestino, $idEmpleado, $idEmpresa, $estatusFiltro = "true", $cerrarVentana = "true") {
	$objResponse = new xajaxResponse();
	
	if (in_array($estatusFiltro, array("1", "true"))) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");
		
		// 1 = ASESOR VENTAS VEHICULOS, 2 = GERENTE VENTAS VEHICULOS
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_empleado.clave_filtro IN (1,2)");
	}
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("id_empleado = %s", valTpDato($idEmpleado, "int"));
	
	if ($idEmpresa != "-1" && $idEmpresa != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.id_empresa = %s
		OR %s IN (SELECT usu_emp.id_empresa
					FROM pg_usuario usu
						INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
					WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
			valTpDato($idEmpresa, "int"),
			valTpDato($idEmpresa, "int"));
	}
	
	$queryEmpleado = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	$rsEmpleado = mysql_query($queryEmpleado);
	if (!$rsEmpleado) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rowEmpleado = mysql_fetch_assoc($rsEmpleado);
	
	$objResponse->assign("txtId".$objDestino,"value",$rowEmpleado['id_empleado']);
	$objResponse->assign("txtNombre".$objDestino,"value",utf8_encode($rowEmpleado['nombre_empleado']));
	
	if (in_array($cerrarVentana, array("1", "true"))) {
		$objResponse->script("byId('btnCancelarLista').click();");
	}
	
	return $objResponse;
}

function buscarEmpleado($frmBuscarEmpleado, $frmProspecto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s",
		$frmProspecto['txtIdEmpresa'],
		$frmBuscarEmpleado['txtCriterioBuscarEmpleado']);
	
	$objResponse->loadCommands(listaEmpleado(0, "id_empleado", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarEmpresa($frmBuscarEmpresa) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s",
		$frmBuscarEmpresa['txtCriterioBuscarEmpresa']);
	
	$objResponse->loadCommands(listaEmpresa(0, "id_empresa_reg", "ASC", $valBusq));
		
	return $objResponse;
}

function buscarUnidadBasicaModelo($frmBuscarModelo, $frmProspecto) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s",
		$frmProspecto['txtIdEmpresa'],
		(is_array($frmBuscarModelo['lstMarcaBuscarModelo']) ? implode(",",$frmBuscarModelo['lstMarcaBuscarModelo']) : $frmBuscarModelo['lstMarcaBuscarModelo']),
		(is_array($frmBuscarModelo['lstModeloBuscarModelo']) ? implode(",",$frmBuscarModelo['lstModeloBuscarModelo']) : $frmBuscarModelo['lstModeloBuscarModelo']),
		(is_array($frmBuscarModelo['lstVersionBuscarModelo']) ? implode(",",$frmBuscarModelo['lstVersionBuscarModelo']) : $frmBuscarModelo['lstVersionBuscarModelo']),
		(is_array($frmBuscarModelo['lstAnoBuscarModelo']) ? implode(",",$frmBuscarModelo['lstAnoBuscarModelo']) : $frmBuscarModelo['lstAnoBuscarModelo']),
		(is_array($frmBuscarModelo['lstCatalogoBuscarModelo']) ? implode(",",$frmBuscarModelo['lstCatalogoBuscarModelo']) : $frmBuscarModelo['lstCatalogoBuscarModelo']),
		$frmBuscarModelo['txtCriterioBuscarModelo']);
	
	$objResponse->loadCommands(listaUnidadBasica(0, "", "", $valBusq));
	
	return $objResponse;
}

function buscarCliente($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstTipoPago']) ? implode(",",$frmBuscar['lstTipoPago']) : $frmBuscar['lstTipoPago']),
		(is_array($frmBuscar['lstEstatusBuscar']) ? implode(",",$frmBuscar['lstEstatusBuscar']) : $frmBuscar['lstEstatusBuscar']),
		(is_array($frmBuscar['lstPagaImpuesto']) ? implode(",",$frmBuscar['lstPagaImpuesto']) : $frmBuscar['lstPagaImpuesto']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		(is_array($frmBuscar['lstTipoCuentaCliente']) ? implode(",",$frmBuscar['lstTipoCuentaCliente']) : $frmBuscar['lstTipoCuentaCliente']),
		(is_array($frmBuscar['lstEmpleado']) ? implode(",",$frmBuscar['lstEmpleado']) : $frmBuscar['lstEmpleado']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->loadCommands(listaCliente(0, "id", "DESC", $valBusq));
	
	return $objResponse;
}

//MUESTRA LOS DOCUMENTOS SEGUN EL PLAN DE PAGO SELECCIONADO POR EL CLIENTE
function cargaDocumentosNecesario($idCliente){
	$objResponse = new xajaxResponse();
	
	//CONSULTA SI TIENE DOCUMENTOS RECAUDADOS
	$sqlDocumentosrecaudados = sprintf("SELECT
		crm_documentos_recaudados.id_perfil_prospecto,
		id,
		id_documento_venta
	FROM crm_documentos_recaudados
		LEFT JOIN crm_perfil_prospecto ON crm_perfil_prospecto.id_perfil_prospecto = crm_documentos_recaudados.id_perfil_prospecto
	WHERE id = %s",
		valTpDato($idCliente, "int"));
	$queryDocumentosRecaudados = mysql_query($sqlDocumentosrecaudados);
	if (!$queryDocumentosRecaudados) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	while ($rowsDocumentoRecaudados = mysql_fetch_array($queryDocumentosRecaudados)){
		$idPerfilRecaudados = $rowsDocumentoRecaudados['id_perfil_prospecto'];
		$idDocumentoVenta = $rowsDocumentoRecaudados['id_documento_venta'];
		
		$documento .= " ".$idPerfilRecaudados." ".$idDocumentoVenta."<br>";
		$documentosRecaudados[] = $rowsDocumentoRecaudados['id_documento_venta'];
	}
	
	// CONSULTA EL TIPO DE PAGO
	$sqlTipoPago = sprintf("SELECT id_prospecto_vehiculo, id_cliente, id_plan_pago, idItem, item
	FROM an_prospecto_vehiculo
		LEFT JOIN grupositems ON grupositems.idItem = an_prospecto_vehiculo.id_plan_pago
	WHERE id_cliente = %s;",
		valTpDato($idCliente, "int"));
	$queryTipoPago = mysql_query($sqlTipoPago);
	if (!$queryTipoPago) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsTipoPago = mysql_num_rows($queryTipoPago);
	$rowsTipoPago = mysql_fetch_array($queryTipoPago);
	
	$tipoPago = $rowsTipoPago['idItem'];
	// CONSULTA EL DOCUMENTO SEGUN EL TIPO DE PAGO						
	$sqlDocumentos = sprintf("SELECT * FROM crm_documentos_ventas WHERE id_tipo_documento = '%s' AND activo = 1;", $tipoPago);
	$queryDocumentos = mysql_query($sqlDocumentos);
	if (!$queryDocumentos) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($queryDocumentos);
	
	$htmlTab = "<table width=\"100%\">";
	while ($rows = mysql_fetch_array($queryDocumentos)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar6";
		$contFila++;
		
		if ($documentosRecaudados){
			if (in_array($rows['id_documento_venta'], $documentosRecaudados)) {
				$checked = "checked = 'checked' disabled='disabled';";
				$t = '<img src="../img/minselect.png" width="13" height="13" />';
			} else {
				$checked = " ";
				$t = " ";
			}
		}
		
		$htmlTab .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTab .= '<td>'.utf8_encode($rows['descripcion_documento']).'</td>';
			$htmlTab .= '<td><input id="checkDocuemento" name="checkDocuemento[]" type="checkbox", '.$checked.' value="'.$rows['id_documento_venta'].'"/>'.$t.'</td>';
		$htmlTab .= '</tr>';
	}
	
	if (!($totalRows > 0)) {
		$htmlTab .= "<tr>";
			$htmlTab .= "<td colspan=\"14\">";
				$htmlTab .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmlTab .= "<tr>";
					$htmlTab .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmlTab .= "<td align=\"center\">No se encontraron registros</td>";
				$htmlTab .= "</tr>";
				$htmlTab .= "</table>";
			$htmlTab .= "</td>";
		$htmlTab .= "</tr>";
	}
	
	$htmlTab .= '</table>';
	
	// CONSULTA SI ESTE ID TIENE UN PERFIL PROSPECTO
	$sqlPerfilProspecto = sprintf("SELECT id_perfil_prospecto FROM crm_perfil_prospecto
	WHERE id = %s;",
		valTpDato($idCliente, "int"));
	$queryPerfilProspecto = mysql_query($sqlPerfilProspecto);
	$rowsPerfilProspecto = mysql_fetch_array($queryPerfilProspecto);
	$rowsPerfilProspecto['id_perfil_prospecto'];
	
	$objResponse->assign("hddIdPerfilProspecto","value",$rowsPerfilProspecto);
	
	$objResponse->script("
	$('#documentoRecaudados').show();
	$('#actividadAsignadas').show();
	$('#actividadSeguimiento').show();");
	
	$objResponse->assign("divDocumentosAEntregar","innerHTML",$htmlTab);
	
	
	
	$sqlActividadCliente = sprintf("SELECT
		id_actividad_ejecucion,
		crm_actividades_ejecucion.id_actividad,
		nombre_actividad,
		id,
		fecha_asignacion,
		crm_integrantes_equipos.id_empleado,
		CONCAT_WS(' ', nombre_empleado, apellido) AS Asesor, estatus
	FROM crm_actividades_ejecucion
		LEFT JOIN crm_integrantes_equipos ON crm_integrantes_equipos.id_integrante_equipo = crm_actividades_ejecucion.id_integrante_equipo
		LEFT JOIN pg_empleado ON pg_empleado.id_empleado = crm_integrantes_equipos.id_empleado
		LEFT JOIN crm_actividad ON crm_actividad.id_actividad = crm_actividades_ejecucion.id_actividad
	WHERE id = %s;",
		valTpDato($idCliente, "int"));
	$queryActividadCliente = mysql_query($sqlActividadCliente);
	if (!$queryActividadCliente) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($queryActividadCliente);
	
	$htmltabA .= "<table width=\"100%\">";
	$htmltabA .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmltabA .= "<td>Actividad</td>";
		$htmltabA .= "<td>Asesor</td>";
	$htmltabA .= "</tr>";
	
	while ($rowsqueryActividadCliente = mysql_fetch_array($queryActividadCliente)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar6";
		$contFila++;
		
		$nombreActividad = $rowsqueryActividadCliente['nombre_actividad'];
		$asesor = $rowsqueryActividadCliente['Asesor'];
		
		$htmltabA .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmltabA .= "<td>".$nombreActividad."</td>";
			$htmltabA .= "<td align='center'>".$asesor."</td>";
		$htmltabA .= "</tr>";
	}
	
	if (!($totalRows > 0)) {
		$htmltabA .= "<tr>";
			$htmltabA .= "<td colspan=\"14\">";
				$htmltabA .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmltabA .= "<tr>";
					$htmltabA .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmltabA .= "<td align=\"center\">No existen actividades para el dia de hoy</td>";
				$htmltabA .= "</tr>";
				$htmltabA .= "</table>";
			$htmltabA .= "</td>";
		$htmltabA .= "</tr>";
	}
	
	$htmltabA .= "</table>"; 
	
	$objResponse->assign("divActividad","innerHTML",$htmltabA);
	
	
	
	// MUESTRA EL LISTADO DE ACTIVIDADES
	$queryActividadTipo = sprintf("SELECT * FROM crm_actividad
	WHERE tipo = 'Ventas'
		AND activo = 1
		AND id_empresa = %s",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$rsActividadTipo = mysql_query($queryActividadTipo);
	if (!$rsActividadTipo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRowsActividadTipo = mysql_num_rows($rsActividadTipo);
	
	$htmltabA = "<table border=\"0\" width=\"100%\">";
	$htmltabA .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmltabA .= "<td width=\"10%\"></td>";
		$htmltabA .= "<td width=\"70%\">Actividad</td>";
		$htmltabA .= "<td width=\"20%\">Estado</td>";
	$htmltabA .= "</tr>";
	
	while ($rowActividadTipo = mysql_fetch_array($rsActividadTipo)){
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar6";
		$contFila++;
		
		$queryBuscarActividad = sprintf("SELECT * FROM crm_actividades_ejecucion
		WHERE id = %s
			AND id_actividad = %s
		LIMIT 1",
			valTpDato($idCliente, "int"),
			valTpDato($rowActividadTipo['id_actividad'], "int"));
		$buscarActividad = mysql_query($queryBuscarActividad);					
		if (!$buscarActividad) $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$existe = mysql_num_rows($buscarActividad);				
		$datosActividad = mysql_fetch_array($buscarActividad);
		
		if ($existe) {
			if ($datosActividad['estatus'] == "1"){//asignado
				$imgEstado = '<img src="../img/iconos/ico_aceptar_azul.png" title="Asignado">';
			} else if ($datosActividad['estatus'] == "0" or $datosActividad['estatus'] == "2"){//finalizado
				$imgEstado = '<img src="../img/iconos/ico_aceptar.gif" title="Finalizada">';
			} else if ($datosActividad['estatus'] == "3"){//finalizado automatico
				$imgEstado = '<img src="../img/iconos/arrow_rotate_clockwise.png"/>';
			}
		} else {
			$imgEstado = '<b style="color:#F00">Sin asignar</b>';
		}
		
		$htmltabA .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmltabA .= "<td align=\"right\">".$rowActividadTipo['posicion_actividad']."</td>";
			$htmltabA .= "<td>".utf8_encode($rowActividadTipo['nombre_actividad'])."</td>";
			$htmltabA .= "<td align='center'>".$imgEstado."</td>";
		$htmltabA .= "</tr>";
	}
	
	if (!($totalRowsActividadTipo > 0)) {
		$htmltabA .= "<tr>";
			$htmltabA .= "<td colspan=\"14\">";
				$htmltabA .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
				$htmltabA .= "<tr>";
					$htmltabA .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
					$htmltabA .= "<td align=\"center\">No existen actividades para el dia de hoy</td>";
				$htmltabA .= "</tr>";
				$htmltabA .= "</table>";
			$htmltabA .= "</td>";
		$htmltabA .= "</tr>";
	}
	
	$htmltabA .= "</table>"; 
	
	$objResponse->assign("divActividadSeguimiento","innerHTML",$htmltabA);	
	
	return $objResponse;
}

function cargaLstAnoBuscar($nombreObjeto = "", $selId = "", $onChange = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"".$onChange."\"";
	
	$query = sprintf("SELECT * FROM an_ano ORDER BY nom_ano");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_ano'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['id_ano']."\">".utf8_encode($row['nom_ano'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstClaveMovimiento($nombreObjeto, $idModulo = "", $idTipoClave = "", $tipoPago = "", $tipoDcto = "", $selId = "", $accion = "") {
	$objResponse = new xajaxResponse();
	
	if ($idModulo != "-1" && $idModulo != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_modulo IN (%s)",
			valTpDato($idModulo, "campo"));
	}
	
	if ($idTipoClave != "-1" && $idTipoClave != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo IN (%s)",
			valTpDato($idTipoClave, "campo"));
	}
	
	if ($tipoPago != "" && $tipoPago == 0) { // CREDITO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		OR pago_credito = 1)");
	} else if ($tipoPago != "" && $tipoPago == 1) { // CONTADO
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(pago_contado = 1
		AND pago_credito = 0)");
	}
	
	if ($tipoDcto != "-1" && $tipoDcto != "") { // 0 = Nada, 1 = Factura, 2 = Remisiones, 3 = Nota de Credito, 4 = Nota de Débito, 5 = Vale Salida, 6 = Vale Entrada
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("documento_genera IN (%s)",
			valTpDato($tipoDcto, "campo"));
	}
	
	$query = sprintf("SELECT DISTINCT
		tipo,
		(CASE tipo
			WHEN 1 THEN 'COMPRA'
			WHEN 2 THEN 'ENTRADA'
			WHEN 3 THEN 'VENTA'
			WHEN 4 THEN 'SALIDA'
		END) AS tipo_movimiento
	FROM pg_clave_movimiento %s
	ORDER BY tipo", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" ".$accion." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$html .= "<optgroup label=\"".$row['tipo_movimiento']."\">";
		
		$sqlBusq2 = "";
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq2 .= $cond.sprintf("tipo IN (%s)",
			valTpDato($row['tipo'], "campo"));
		
		$queryClaveMov = sprintf("SELECT * FROM pg_clave_movimiento %s %s ORDER BY clave", $sqlBusq, $sqlBusq2);
		$rsClaveMov = mysql_query($queryClaveMov);
		if (!$rsClaveMov) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClaveMov = mysql_fetch_assoc($rsClaveMov)) {
			switch($rowClaveMov['id_modulo']) {
				case 0 : $clase = "divMsjInfoSinBorde2"; break;
				case 1 : $clase = "divMsjInfoSinBorde"; break;
				case 2 : $clase = "divMsjAlertaSinBorde"; break;
				case 3 : $clase = "divMsjInfo4SinBorde"; break;
			}
			
			$selected = ($selId == $rowClaveMov['id_clave_movimiento']) ? "selected=\"selected\"" : "";
			
			$html .= "<option class=\"".$clase."\" ".$selected." value=\"".$rowClaveMov['id_clave_movimiento']."\">".utf8_encode($rowClaveMov['clave'].") ".$rowClaveMov['descripcion'])."</option>";
		}
		$html .= "</optgroup>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}

function cargaLstCredito($selId = "") {
	$objResponse = new xajaxResponse();
	
	$arrayDetCredito[0] = "1";
	$arrayDetCredito[1] = "Contado";
	$arrayCredito[] = $arrayDetCredito;
	$arrayDetCredito[0] = "0";
	$arrayDetCredito[1] = "Crédito";
	$arrayCredito[] = $arrayDetCredito;
	
	if ($selId == "0") { // 0 = Crédito
		$onChange = sprintf("selectedOption('lstCredito', 0);");
	} else if ($selId == "1") { // 1 = Contado
		$onChange = sprintf("selectedOption('lstCredito', 1);");
	}
	$onChange .= sprintf("xajax_cargaLstClaveMovimiento('lstClaveMovimiento', '0', '3', this.value, '1', '%s');",
		"19"); // 19 = Mostrador Público Contado
	
	$html = "<select id=\"lstCredito\" name=\"lstCredito\" onchange=\"".$onChange."\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($arrayCredito as $indice => $valor) {
		$selected = ($selId == $arrayCredito[$indice][0]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$arrayCredito[$indice][0]."\">".$arrayCredito[$indice][1]."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstCredito","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstado($nombreObjeto, $selId = "") {
	$objResponse = new xajaxResponse();
	
	$array[] = "Amazonas";			$array[] = "Anzoátegui";		$array[] = "Apure";				$array[] = "Aragua";
	$array[] = "Barinas";			$array[] = "Bolívar";			$array[] = "Carabobo";			$array[] = "Cojedes";
	$array[] = "Delta Amacuro";		$array[] = "Distrito Capital";	$array[] = "Falcón";			$array[] = "Guárico";
	$array[] = "Lara";				$array[] = "Mérida";			$array[] = "Miranda";			$array[] = "Monagas";
	$array[] = "Nueva Esparta";		$array[] = "Portuguesa";		$array[] = "Sucre";				$array[] = "Táchira";
	$array[] = "Trujillo";			$array[] = "Vargas";			$array[] = "Yaracuy";			$array[] = "Zulia";
	
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = ($selId == $array[$indice]) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".($array[$indice])."\">".($array[$indice])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("td".$nombreObjeto,"innerHTML",$html);
	
	return $objResponse;
}
	
function cargaLstEstadoCivil($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
	FROM grupositems git
		LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
	WHERE gps.grupo = 'estadoCivil' AND git.status = 1
	ORDER BY item");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"lstEstadoCivil\" name=\"lstEstadoCivil\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idItem']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idItem']."\">".utf8_encode($row['item'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEstadoCivil","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstEstatus($id_estatus = "") {
	$objResponse = new xajaxResponse();

	// LLAMA SELECT ESTATUS
	$sql_estatus = sprintf("SELECT id_estatus, nombre_estatus FROM crm_estatus
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_estatus;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_estatus = mysql_query($sql_estatus);
	$rs_estatus = mysql_num_rows($query_estatus);
	$select_estatus = "<select id='id_estatus' name='estatus' class=\"inputHabilitado\" style=\"width:99%\">";
		$select_estatus .= '<option value="">[ Seleccione ]</option>';
	while ($fila_estatus = mysql_fetch_array($query_estatus)) {
		$selected = ($fila_estatus['id_estatus'] == $id_estatus) ? "selected=\"selected\"" : "";
		
		$select_estatus .= '<option '.$selected.' value="'.$fila_estatus['id_estatus'].'">'.utf8_encode($fila_estatus['nombre_estatus']).'</option>';
	}
	$select_estatus .= "</select>";
	$objResponse->assign('td_select_estatus', 'innerHTML', $select_estatus);
	
	return $objResponse;
}

function cargaLstMarcaModeloVersion($tpLst, $idLstOrigen, $nombreObjeto, $objetoBuscar = "false", $padreId = "", $selId = "", $onChange = "") {
	$objResponse = new xajaxResponse();
	
	$padreId = is_array($padreId) ? implode(",",$padreId) : $padreId;
	
	switch ($tpLst) {
		case "unidad_basica" : $arraySelec = array("lstPadre","lstMarca","lstModelo","lstVersion");
	}
	
	$posList = buscarEnArray($arraySelec, $idLstOrigen);
	
	// SI EL OBJETO NO ES EL ULTIMO ASIGNA LA FUNCION PARA CARGAR EL SIGUIENTE OBJETO
	if (($posList + 1) != count($arraySelec) - 1) {
		$onChange = "onchange=\"".$onChange." xajax_cargaLstMarcaModeloVersion('".$tpLst."', '".$arraySelec[$posList+1]."', '".$nombreObjeto."', '".$objetoBuscar."', getSelectValues(byId(this.id)), '', '".str_replace("'","\'",$onChange)."');\"";
	}
	
	// SI EL VALOR DEL OBJETO ES IGUAL A SELECCIONE, LIMPIARA TODOS LOS OBJETOS SIGUIENTES
	if ($padreId == '-1' && $nombreObjeto != "Buscar") {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html = "<select id=\"".$valor.$nombreObjeto."\" name=\"".$valor.$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
					$html .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html .= "</select>";
				$objResponse->assign("td".$valor.$nombreObjeto, 'innerHTML', $html);
			}
		}
		
		return $objResponse;
	} else {
		foreach ($arraySelec as $indice => $valor) {
			if ($indice > $posList) {
				$html2 = "<select id=\"".$valor.$nombreObjeto."\" name=\"".$valor.$nombreObjeto."\" class=\"inputHabilitado\" style=\"width:99%\">";
					$html2 .= "<option value=\"-1\">[ Seleccione ]</option>";
				$html2 .= "</select>";
				$objResponse->assign("td".$valor.$nombreObjeto, 'innerHTML', $html2);
			}
		}
		
		switch ($posList) {
			case 0 :
				$query = sprintf("SELECT * FROM an_marca marca
				ORDER BY marca.nom_marca;");
				$campoId = "id_marca";
				$campoDesc = "nom_marca";
				break;
			case 1 :
				$query = sprintf("SELECT * FROM an_modelo modelo
				WHERE modelo.id_marca IN (%s)
				ORDER BY modelo.nom_modelo;",
					valTpDato($padreId, "campo"));
				$campoId = "id_modelo";
				$campoDesc = "nom_modelo";
				break;
			case 2 :
				$query = sprintf("SELECT * FROM an_version vers
				WHERE vers.id_modelo IN (%s)
				ORDER BY vers.nom_version;",
					valTpDato($padreId, "campo"));
				$campoId = "id_version";
				$campoDesc = "nom_version";
				break;
		}
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
		$html = "<select ".(($totalRows > 2 && $objetoBuscar == "true") ? "multiple=\"multiple\"": "")." id=\"".$arraySelec[$posList+1].$nombreObjeto."\" name=\"".$arraySelec[$posList+1].$nombreObjeto."\" class=\"inputHabilitado\" ".$onChange." style=\"width:99%\">";
			$html .= "<option value=\"-1\">[ Seleccione ]</option>";
		while ($row = mysql_fetch_array($rs)) {
			$selected = ($selId == $row[$campoId]) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$row[$campoId]."\">".utf8_encode($row[$campoDesc])."</option>";
		}
		$html .= "</select>";
	}
	
	$objResponse->assign("td".$arraySelec[$posList+1].$nombreObjeto, 'innerHTML', $html);
	
	return $objResponse;
}

function cargaLstModuloBuscar($selId = "", $bloquearObj = false){
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$query = sprintf("SELECT * FROM pg_modulos");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstModulo\" name=\"lstModulo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['descripcionModulo']) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['descripcionModulo']."\">".utf8_encode($row['descripcionModulo'])."</option>";
	}
	$html .= "</select>";
	
	$objResponse->assign("tdlstModulo","innerHTML",$html);
	
	return $objResponse;
}

function cargaLstMotivoRechazo($motivo, $selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT * FROM crm_motivo_rechazo
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_motivo_rechazo;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"lstMotivoRechazo\" name=\"lstMotivoRechazo\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"\">[ Seleccione ]</option>";
	if ($motivo == 'Rechazo') {
		while ($row = mysql_fetch_assoc($rs)) {
			$selected = ($selId == $row['id_motivo_rechazo']) ? "selected=\"selected\"" : "";
			
			$html .= "<option ".$selected." value=\"".$row['id_motivo_rechazo']."\">".utf8_encode($row['nombre_motivo_rechazo'])."</option>";
		}
	}
	$html .= "</select>";
	$objResponse->assign("td_select_motivo_rechazo","innerHTML",$html);
	
	return $objResponse;
}
	
function cargaLstNivelInfluencia($id_nivel_influencia = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMA SELECT NIVEL INFLUENCIA
	$sql_nivel_influencia = sprintf("SELECT id_nivel_influencia, nombre_nivel_influencia FROM crm_nivel_influencia
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_nivel_influencia;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_nivelt_influencia = mysql_query($sql_nivel_influencia);
	$rs_nivel_influencia = mysql_num_rows($query_nivelt_influencia);
	$select_nivel_influencia = "<select id='id_nivel_influencia' name='nivel_influencia' class=\"inputHabilitado\" style=\"width:99%\">";
		$select_nivel_influencia .= '<option value="">[ Seleccione ]</option>';				
	while ($fila_nivel_influencia = mysql_fetch_array($query_nivelt_influencia)) {
		$selected = ($fila_nivel_influencia['id_nivel_influencia'] == $id_nivel_influencia) ? "selected=\"selected\"" : "";
		
		$select_nivel_influencia .= '<option '.$selected.' value="'.$fila_nivel_influencia['id_nivel_influencia'].'">'.utf8_encode($fila_nivel_influencia['nombre_nivel_influencia']).'</option>';
	}
	$select_nivel_influencia .= "</select>";
	$objResponse->assign('td_select_nivel_influencia', 'innerHTML', $select_nivel_influencia);
	
	return $objResponse;
}

function cargaLstPosibilidadCierre($id_posibilidad_cierre = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMA SELECT POSIBILIDAD DE CIERRE
	$sql_posibilidad_cierre = sprintf("SELECT id_posibilidad_cierre, nombre_posibilidad_cierre FROM crm_posibilidad_cierre
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_posibilidad_cierre;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_posibilidad_cierre = mysql_query($sql_posibilidad_cierre);
	if (!$query_posibilidad_cierre) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rs_posibilidad_cierre = mysql_num_rows($query_posibilidad_cierre);
	$select_posibilidad_cierre = "<select id='posibilidad_cierre' name='posibilidad_cierre' class=\"inputHabilitado\" onchange='motivoRechazo(this.value)' style=\"width:99%\">";
		$select_posibilidad_cierre .= '<option value="">[ Seleccione ]</option>';
	while ($fila_posibilidad_cierre = mysql_fetch_array($query_posibilidad_cierre)) {
		$selected = ($fila_posibilidad_cierre['id_posibilidad_cierre'] == $id_posibilidad_cierre) ? "selected=\"selected\"" : "";
		
		$select_posibilidad_cierre .= '<option '.$selected.' value="'.$fila_posibilidad_cierre['id_posibilidad_cierre'].'">'.utf8_encode($fila_posibilidad_cierre['nombre_posibilidad_cierre']).'</option>';
	}
	$select_posibilidad_cierre .= "</select>";
	$objResponse->assign('td_select_posibilidad_cierre', 'innerHTML', $select_posibilidad_cierre);

	return $objResponse;
}

function cargaLstPuesto($id_puesto = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMAR SELECT PUESTO
	$sql_puesto = sprintf("SELECT id_puesto, nombre_puesto FROM crm_puesto
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_puesto;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_puesto = mysql_query($sql_puesto);
	$rs_puesto = mysql_num_rows($query_puesto);
	$select_puesto = "<select id='id_puesto' name='puesto' class=\"inputHabilitado\" style=\"width:99%\">";
		$select_puesto .= '<option value="">[ Seleccione ]</option>';
	while ($fila_puesto = mysql_fetch_array($query_puesto)) {
		$selected = ($fila_puesto['id_puesto'] == $id_puesto) ? "selected=\"selected\"" : "";
		
		$select_puesto .= '<option '.$selected.' value="'.$fila_puesto['id_puesto'].'">'.utf8_encode($fila_puesto['nombre_puesto']).'</option>';
	}
	$select_puesto .= "</select>";
	$objResponse->assign('td_select_puesto', 'innerHTML', $select_puesto);
	
	return $objResponse;
}

function cargaLstSector($id_sector = "") {
	$objResponse = new xajaxResponse();
	
	// LLAMAR SELECT SECTOR
	$sql_sector = sprintf("SELECT id_sector, nombre_sector FROM crm_sector
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_sector;",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_sector = mysql_query($sql_sector);
	$rs_sector = mysql_num_rows($query_sector);
	$select_sector = "<select id='id_sector' name='sector' class=\"inputHabilitado\" style=\"width:99%\">";
		$select_sector .= '<option value="">[ Seleccione ]</option>';
	while ($fila_sector = mysql_fetch_array($query_sector)) {
		$selected = ($fila_sector['id_sector'] == $id_sector) ? "selected=\"selected\"" : "";
		
		$select_sector .= '<option '.$selected.' value="'.$fila_sector['id_sector'].'">'.utf8_encode($fila_sector['nombre_sector']).'</option>';
	}
	$select_sector .= "</select>";
	$objResponse->assign('td_select_sector', 'innerHTML', $select_sector);
	
	return $objResponse;
}

function cargaLstTitulo($id_titulo = "") {
	$objResponse = new xajaxResponse();
	
	// LLENAR SELECT TITULO
	$sql_titulo = sprintf("SELECT id_titulo, nombre_titulo FROM crm_titulo
	WHERE activo = 1
		AND id_empresa = %s
	ORDER BY nombre_titulo",
		$_SESSION['idEmpresaUsuarioSysGts']);
	$query_titulo = mysql_query($sql_titulo);
	if (!$query_titulo) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$rs_titulo = mysql_num_rows($query_titulo);	
	$select_titulo = "<select id='id_titulo' name='titulo' class=\"inputHabilitado\" style=\"width:99%\">";
		$select_titulo .= '<option value="">[ Seleccione ]</option>';
	while ($fila_titulo = mysql_fetch_array($query_titulo)) {
		$selected = ($fila_titulo['id_titulo'] == $id_titulo) ? "selected=\"selected\"" : "";
		
		$select_titulo .= '<option '.$selected.' value="'.$fila_titulo['id_titulo'].'">' .utf8_encode($fila_titulo['nombre_titulo']). '</option>';
	}
	$select_titulo .= "</select>";
	$objResponse->assign("td_select_titulo","innerHTML",$select_titulo);
	
	return $objResponse;
}

function cargaLstVendedor($selId = "", $bloquearObj = false) {
	$objResponse = new xajaxResponse();
	
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "onchange=\"byId('btnBuscar').click();\"";
	
	$sqlBusq = "";
	if (in_array(idArrayPais,array(3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		// 1.- ASESOR VENTAS VEHICULOS
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((vw_pg_empleado.id_empleado IN (SELECT vw_pg_empleado2.id_empleado FROM vw_pg_empleados vw_pg_empleado2
																	WHERE vw_pg_empleado2.clave_filtro IN (1))
				AND (SELECT COUNT(vw_pg_empleado2.id_empleado) FROM vw_pg_empleados vw_pg_empleado2
					WHERE vw_pg_empleado2.id_empleado = %s
						AND vw_pg_empleado2.clave_filtro IN (1)) = 0)
			OR (vw_pg_empleado.id_empleado = %s
				AND (SELECT COUNT(vw_pg_empleado2.id_empleado) FROM vw_pg_empleados vw_pg_empleado2
					WHERE vw_pg_empleado2.id_empleado = %s
						AND vw_pg_empleado2.clave_filtro IN (1)) > 0))",
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_empleado.id_empleado IN (SELECT cliente.id_empleado_creador FROM cj_cc_cliente cliente)");
	}
	
	$query = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado %s ORDER BY vw_pg_empleado.nombre_empleado", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select ".(($totalRows > 2) ? "multiple=\"multiple\"" : "")." id=\"lstEmpleado\" name=\"lstEmpleado\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= ($totalRows > 1) ? "<option value=\"-1\">[ Seleccione ]</option>" : "";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['id_empleado']) ? "selected=\"selected\"" : "";
						
		$html .= "<option ".$selected." value=\"".$row['id_empleado']."\">".utf8_encode($row['nombre_empleado'])."</option>";
	}
	$html .= "</select>";
	$objResponse->assign("tdlstEmpleado","innerHTML",$html);
	
	return $objResponse;
}

function cargarPagina(){
	$objResponse = new xajaxResponse();
	
	$sqlBusq = "";
	if (in_array(idArrayPais,array(3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		// 1.- ASESOR VENTAS VEHICULOS
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("((vw_pg_empleado.id_empleado IN (SELECT vw_pg_empleado2.id_empleado FROM vw_pg_empleados vw_pg_empleado2
																	WHERE vw_pg_empleado2.clave_filtro IN (1))
				AND (SELECT COUNT(vw_pg_empleado2.id_empleado) FROM vw_pg_empleados vw_pg_empleado2
					WHERE vw_pg_empleado2.id_empleado = %s
						AND vw_pg_empleado2.clave_filtro IN (1)) = 0)
			OR (vw_pg_empleado.id_empleado = %s
				AND (SELECT COUNT(vw_pg_empleado2.id_empleado) FROM vw_pg_empleados vw_pg_empleado2
					WHERE vw_pg_empleado2.id_empleado = %s
						AND vw_pg_empleado2.clave_filtro IN (1)) > 0))",
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"),
		valTpDato($_SESSION['idEmpleadoSysGts'],"int"));
	} else {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_pg_empleado.id_empleado IN (SELECT cliente.id_empleado_creador FROM cj_cc_cliente cliente)");
	}
	
	$query = sprintf("SELECT * FROM vw_pg_empleados vw_pg_empleado %s ORDER BY vw_pg_empleado.nombre_empleado", $sqlBusq);
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_assoc($rs);
	
	$objResponse->loadCommands(cargaLstEmpresaFinal($_SESSION['idEmpresaUsuarioSysGts']));
	$objResponse->loadCommands(cargaLstModuloBuscar());
	$objResponse->loadCommands(cargaLstVendedor((($totalRows == 1) ? $_SESSION['idEmpleadoSysGts'] : "-1")));
	
	$objResponse->script("xajax_buscarCliente(xajax.getFormValues('frmBuscar'));");
	
	return $objResponse;
}

function eliminarClienteEmpresa($frmCliente){
	$objResponse = new xajaxResponse();
	
	if (isset($frmCliente['cbxItm'])) {
		foreach($frmCliente['cbxItm'] as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
		$objResponse->script("xajax_eliminarClienteEmpresa(xajax.getFormValues('frmCliente'));");
	}
	
	return $objResponse;
}

function eliminarModelo($frmProspecto) {
	$objResponse = new xajaxResponse();
	
	if (isset($frmProspecto['cbxItmModeloInteres'])) {
		foreach($frmProspecto['cbxItmModeloInteres'] as $indiceItm=>$valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmModeloInteres:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}		
		$objResponse->script("xajax_eliminarModelo(xajax.getFormValues('frmProspecto'));");
	}
	
	return $objResponse;
}

function exportarCliente($frmBuscar) {
	$objResponse = new xajaxResponse();
	
	$valBusq = sprintf("%s|%s|%s|%s|%s|%s|%s|%s",
		$frmBuscar['lstEmpresa'],
		(is_array($frmBuscar['lstTipoPago']) ? implode(",",$frmBuscar['lstTipoPago']) : $frmBuscar['lstTipoPago']),
		(is_array($frmBuscar['lstEstatusBuscar']) ? implode(",",$frmBuscar['lstEstatusBuscar']) : $frmBuscar['lstEstatusBuscar']),
		(is_array($frmBuscar['lstPagaImpuesto']) ? implode(",",$frmBuscar['lstPagaImpuesto']) : $frmBuscar['lstPagaImpuesto']),
		(is_array($frmBuscar['lstModulo']) ? implode(",",$frmBuscar['lstModulo']) : $frmBuscar['lstModulo']),
		(is_array($frmBuscar['lstTipoCuentaCliente']) ? implode(",",$frmBuscar['lstTipoCuentaCliente']) : $frmBuscar['lstTipoCuentaCliente']),
		(is_array($frmBuscar['lstEmpleado']) ? implode(",",$frmBuscar['lstEmpleado']) : $frmBuscar['lstEmpleado']),
		$frmBuscar['txtCriterio']);
	
	$objResponse->script("window.open('reportes/an_prospecto_excel.php?valBusq=".$valBusq."','_self');");
	
	return $objResponse;
}

function formCliente($idCliente, $frmCliente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieClienteEmpresa = $frmCliente['cbxPieClienteEmpresa'];
	
	// ELIMINA LOS OBJETOS QUE HABIAN QUEDADO ANTERIORMENTE
	if (isset($arrayObjPieClienteEmpresa)) {
		foreach ($arrayObjPieClienteEmpresa as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItm:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$idEmpresa = $_SESSION['idEmpresaUsuarioSysGts'];
	
	if ($idCliente > 0) {
		if (!xvalidaAcceso($objResponse,"cc_cliente_list","editar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarCliente').click();"); return $objResponse; }
		
		// BUSCA LOS DATOS DEL CLIENTE
		$query = sprintf("SELECT cliente.*,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			CONCAT_WS('-', cliente.lci2, cliente.cicontacto) AS ci_contacto
		FROM cj_cc_cliente cliente
		WHERE id = %s;",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if ($row['tipo_cuenta_cliente'] == 1) { // 1 = Prospecto, 2 = Cliente
			$objResponse->script("
			byId('lstTipo').className = 'inputHabilitado';
			byId('txtCedula').className = 'inputHabilitado';
			byId('txtNombre').className = 'inputHabilitado';
			byId('txtApellido').className = 'inputHabilitado';
			byId('txtLicencia').className = 'inputHabilitado';
			
			byId('txtCedula').readOnly = false;
			byId('txtNombre').readOnly = false;
			byId('txtApellido').readOnly = false;");
		}
		
		$objResponse->assign("hddIdCliente","value",$row['id']);
		
		$tipoPago = ($row['credito'] == "si") ? "0" : "1";
		$objResponse->loadCommands(cargaLstCredito($tipoPago));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", $tipoPago, "1", $row['id_clave_movimiento_predeterminado']));
		
		$objResponse->script("selectedOption('lstTipo', '".$row['tipo']."')");
		$objResponse->script("byId('lstTipo').onchange = function() { selectedOption(this.id, '".$row['tipo']."'); }");
		$objResponse->assign("txtCedula","value",(($row['lci'] != "S/I") ? $row['ci_cliente'] : ""));
		$objResponse->script("byId('txtCedula').placeholder = '".$row['ci_cliente']."';");
		$objResponse->assign("txtNombre","value",utf8_encode($row['nombre']));
		$objResponse->assign("txtNit","value",$row['nit']);
		$objResponse->assign("txtApellido","value",utf8_encode($row['apellido']));
		$objResponse->assign("txtLicencia","value",utf8_encode($row['licencia']));
		$objResponse->script("selectedOption('lstContribuyente', '".$row['contribuyente']."')");
		
		$arrayDireccion = explode(";",utf8_encode($row['direccion']));
		$objResponse->assign("txtUrbanizacion","value",trim($arrayDireccion[0]));
		$objResponse->assign("txtCalle","value",trim($arrayDireccion[1]));
		$objResponse->assign("txtCasa","value",trim($arrayDireccion[2]));
		$objResponse->assign("txtMunicipio","value",trim($arrayDireccion[3]));
		$objResponse->assign("txtCiudad","value",utf8_encode($row['ciudad']));
		$objResponse->assign("txtEstado","value",utf8_encode($row['estado']));
		$objResponse->assign("txtTelefono","value",$row['telf']);
		$objResponse->assign("txtOtroTelefono","value",$row['otrotelf']);
		$objResponse->assign("txtCorreo","value",$row['correo']);
		
		$objResponse->assign("txtUrbanizacionPostalCliente","value",utf8_encode($row['urbanizacion_postal']));
		$objResponse->assign("txtCallePostalCliente","value",utf8_encode($row['calle_postal']));
		$objResponse->assign("txtCasaPostalCliente","value",utf8_encode($row['casa_postal']));
		$objResponse->assign("txtMunicipioPostalCliente","value",utf8_encode($row['municipio_postal']));
		$objResponse->assign("txtCiudadPostalCliente","value",utf8_encode($row['ciudad_postal']));
		$objResponse->assign("txtEstadoPostalCliente","value",utf8_encode($row['estado_postal']));
		
		
		$objResponse->script("selectedOption('lstReputacionCliente', '".((strlen($row['reputacionCliente']) > 0) ? $row['reputacionCliente'] : "CLIENTE B")."');");
		$objResponse->script("selectedOption('lstTipoCliente', '".((strlen($row['tipocliente']) > 0) ? $row['tipocliente'] : "Vehiculos")."');");
		$objResponse->script("selectedOption('lstDescuento', '".$row['descuento']."');");
		$objResponse->script("
		byId('lstDescuento').onchange = function() {
			selectedOption(this.id, ".$row['descuento'].");
		}");
		$objResponse->script("selectedOption('lstEstatus', '".$row['status']."');");
		$objResponse->script("byId('cbxPagaImpuesto').checked = ".(($row['paga_impuesto'] == "1") ? 'true' : 'false'));
		$objResponse->script("byId('cbxBloquearVenta').checked = ".(($row['bloquea_venta'] == "1") ? 'true' : 'false'));
		
		$objResponse->assign("txtFechaCreacion","value",date(spanDateFormat, strtotime($row['fcreacion'])));
		$objResponse->assign("txtFechaDesincorporar","value",date(spanDateFormat, strtotime($row['fdesincorporar'])));
		
		$objResponse->assign("txtCedulaContacto","value",$row['ci_contacto']);
		$objResponse->assign("txtNombreContacto","value",utf8_encode($row['contacto']));
		$objResponse->assign("txtTelefonoContacto","value",$row['telfcontacto']);
		$objResponse->assign("txtCorreoContacto","value",$row['correocontacto']);
		
		$queryClienteEmpresa = sprintf("SELECT * FROM cj_cc_cliente_empresa cliente_emp
		WHERE cliente_emp.id_cliente = %s
		ORDER BY cliente_emp.id_empresa ASC;",
			valTpDato($idCliente, "int"));
		$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
		if (!$rsClienteEmpresa) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($rowClienteEmpresa = mysql_fetch_array($rsClienteEmpresa)) {
			$Result1 = insertarItemClienteEmpresa($contFila, $rowClienteEmpresa['id_cliente_empresa']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObjPieClienteEmpresa[] = $contFila;
			}
		}
	} else {
		if (!xvalidaAcceso($objResponse,"cc_cliente_list","insertar")) { usleep(0.5 * 1000000); $objResponse->script("byId('btnCancelarCliente').click();"); return $objResponse; }
		
		$objResponse->loadCommands(cargaLstCredito("1"));
		$objResponse->call("selectedOption","lstContribuyente","No");
		$objResponse->call("selectedOption","lstEstatus","Activo");
		$objResponse->assign("txtFechaCreacion","value",date(spanDateFormat));
		$objResponse->assign("txtFechaDesincorporar","value",date(spanDateFormat,dateAddLab(strtotime(date(spanDateFormat)),364,false)));
		$objResponse->loadCommands(cargaLstClaveMovimiento("lstClaveMovimiento", "0", "3", "1", "1", "19"));
		$objResponse->call("selectedOption","lstDescuento",0);
		$objResponse->script("
		byId('lstDescuento').onchange = function() {
			selectedOption(this.id, ".(0).");
		}");
		$objResponse->call("selectedOption","lstTipoCliente","Vehiculos");
		$objResponse->call("selectedOption","lstReputacionCliente","CLIENTE B");
		
		$objResponse->script("xajax_insertarClienteEmpresa(".$idEmpresa.", xajax.getFormValues('frmCliente'));");
	}
	
	return $objResponse;
}

function formCredito($hddNumeroItm, $frmCliente) {
	$objResponse = new xajaxResponse();
	
	if (!xvalidaAcceso($objResponse,"cc_clientes_credito")) { $objResponse->script("byId('btnCancelarCredito').click();"); return $objResponse; }
	
	if ($frmCliente['lstCredito'] != "0") {
		sleep(1);
		$objResponse->alert("Tipo de cliente inválido para esta acción");
		$objResponse->script("byId('btnCancelarCredito').click();");
		return $objResponse;
	}
	
	$objResponse->assign("hddNumeroItm","value",$hddNumeroItm);
	$objResponse->assign("txtDiasCredito","value",$frmCliente['txtDiasCredito'.$hddNumeroItm]);
	$objResponse->assign("txtLimiteCredito","value",$frmCliente['txtLimiteCredito'.$hddNumeroItm]);
	$objResponse->loadCommands(cargaLstFormaPago($frmCliente['txtFormaPago'.$hddNumeroItm]));
	
	return $objResponse;
}

function formProspecto($idCliente, $frmProspecto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieModeloInteres = $frmProspecto['cbxPieModeloInteres'];
	
	if (isset($arrayObjPieModeloInteres)) {
		foreach($arrayObjPieModeloInteres as $indiceItm => $valorItm) {
			$objResponse->script("
			fila = document.getElementById('trItmModeloInteres:".$valorItm."');
			padre = fila.parentNode;
			padre.removeChild(fila);");
		}
	}
	
	$objResponse->script("
	byId('trCedulaProspecto').style.display = '';
	byId('lstTipoProspecto').style.display = '';");
	if (in_array(idArrayPais,array(2,3))) { // 1 = Venezuela, 2 = Panama, 3 = Puerto Rico
		$objResponse->script("
		byId('trCedulaProspecto').style.display = 'none';
		byId('lstTipoProspecto').style.display = 'none';");
	}
	
	if ($idCliente > 0) {
		if (!xvalidaAcceso($objResponse,"an_prospecto_list","editar")) { $objResponse->script("byId('btnCancelarProspecto').click();"); return $objResponse; }
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';");
		
		$query = sprintf("SELECT cliente.*,
			CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
			vw_pg_empleado.nombre_empleado
		FROM cj_cc_cliente cliente
			LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado)
		WHERE id = %s;",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_assoc($rs);
		
		if ($row['tipo_cuenta_cliente'] == 1) { // 1 = Prospecto, 2 = Cliente
			$objResponse->script("
			byId('lstTipoProspecto').className = 'inputHabilitado';
			byId('txtCedulaProspecto').className = 'inputHabilitado';
			byId('txtNitProspecto').className = 'inputHabilitado';
			byId('txtNombreProspecto').className = 'inputHabilitado';
			byId('txtApellidoProspecto').className = 'inputHabilitado';
			byId('txtLicenciaProspecto').className = 'inputHabilitado';
			
			byId('txtCedulaProspecto').readOnly = false;
			byId('txtNitProspecto').readOnly = false;
			byId('txtNombreProspecto').readOnly = false;
			byId('txtApellidoProspecto').readOnly = false;
			
			byId('tdFlotanteTitulo1').innerHTML = 'Editar Prospecto';");
		} else {
			$objResponse->script("
			byId('tdFlotanteTitulo1').innerHTML = 'Editar Cliente';");
			
			$objResponse->script("
			byId('trCedulaProspecto').style.display = '';
			byId('lstTipoProspecto').style.display = '';");
		}
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "", false));
		
		$objResponse->loadCommands(asignarEmpleado("Empleado", (($row['id_empleado_creador'] > 0) ? $row['id_empleado_creador'] : $_SESSION['idEmpleadoSysGts']), "", ""));
		$objResponse->assign("hddIdClienteProspecto","value",$row['id']);
		switch ($row['tipo']) {
			case "Natural" : $lstTipoProspecto = "Natural"; break;
			case "Juridico" : $lstTipoProspecto = "Juridico"; break;
		}
		$objResponse->script("selectedOption('lstTipoProspecto', '".$lstTipoProspecto."');");
		$objResponse->script("byId('lstTipoProspecto').onchange = function() { selectedOption(this.id, '".$lstTipoProspecto."'); }");
		$objResponse->assign("txtCedulaProspecto","value",$row['ci_cliente']);
		$objResponse->assign("txtNitProspecto","value",$row['nit']);
		$objResponse->assign("txtNombreProspecto","value",utf8_encode($row['nombre']));
		$objResponse->assign("txtApellidoProspecto","value",utf8_encode($row['apellido']));
		$objResponse->assign("txtLicenciaProspecto","value",utf8_encode($row['licencia']));
		
		$objResponse->assign("txtUrbanizacionProspecto","value",utf8_encode($row['urbanizacion']));
		$objResponse->assign("txtCalleProspecto","value",utf8_encode($row['calle']));
		$objResponse->assign("txtCasaProspecto","value",utf8_encode($row['casa']));
		$objResponse->assign("txtMunicipioProspecto","value",utf8_encode($row['municipio']));
		$objResponse->assign("txtCiudadProspecto","value",utf8_encode($row['ciudad']));
		$objResponse->assign("txtEstadoProspecto","value",utf8_encode($row['estado']));
		$objResponse->assign("txtTelefonoProspecto","value",$row['telf']);
		$objResponse->assign("txtOtroTelefonoProspecto","value",$row['otrotelf']);
		$objResponse->assign("txtCorreoProspecto","value",utf8_encode($row['correo']));
		
		$objResponse->assign("txtUrbanizacionPostalProspecto","value",utf8_encode($row['urbanizacion_postal']));
		$objResponse->assign("txtCallePostalProspecto","value",utf8_encode($row['calle_postal']));
		$objResponse->assign("txtCasaPostalProspecto","value",utf8_encode($row['casa_postal']));
		$objResponse->assign("txtMunicipioPostalProspecto","value",utf8_encode($row['municipio_postal']));
		$objResponse->assign("txtCiudadPostalProspecto","value",utf8_encode($row['ciudad_postal']));
		$objResponse->assign("txtEstadoPostalProspecto","value",utf8_encode($row['estado_postal']));
		
		$objResponse->assign("txtUrbanizacionComp","value",utf8_encode($row['urbanizacion_comp']));
		$objResponse->assign("txtCalleComp","value",utf8_encode($row['calle_comp']));
		$objResponse->assign("txtCasaComp","value",utf8_encode($row['casa_comp']));
		$objResponse->assign("txtMunicipioComp","value",utf8_encode($row['municipio_comp']));
		$objResponse->assign("txtEstadoComp","value",utf8_encode($row['estado_comp']));
		$objResponse->assign("txtTelefonoComp","value",$row['telf_comp']);
		$objResponse->assign("txtOtroTelefonoComp","value",$row['otro_telf_comp']);
		$objResponse->assign("txtEmailComp","value",utf8_encode($row['correo_comp']));
		$objResponse->assign("txtFechaUltAtencion","value",(($row['fechaUltimaAtencion'] != "") ? date(spanDateFormat, strtotime($row['fechaUltimaAtencion'])) : ""));
		$objResponse->assign("txtFechaUltEntrevista","value",(($row['fechaUltimaEntrevista'] != "") ? date(spanDateFormat, strtotime($row['fechaUltimaEntrevista'])) : ""));
		$objResponse->assign("txtFechaProxEntrevista","value",(($row['fechaProximaEntrevista'] != "") ? date(spanDateFormat, strtotime($row['fechaProximaEntrevista'])) : ""));
		
		// BUSCA LOS MODELOS DE INTERES
		$query = sprintf("SELECT 
			id_prospecto_vehiculo,
			id_cliente,
			id_unidad_basica,
			precio_unidad_basica,
			id_medio,
			id_nivel_interes,
			id_plan_pago
		FROM an_prospecto_vehiculo prosp_vehi
		WHERE id_cliente = %s;",
			valTpDato($idCliente, "int"));
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		while ($row = mysql_fetch_assoc($rs)) {
			$Result1 = insertarItemModeloInteres($contFila, $row['id_prospecto_vehiculo']);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObjPieModeloInteres[] = $contFila;
			}
		}
		
		$sql_perfil_prospecto = "SELECT * FROM crm_perfil_prospecto WHERE id = $idCliente;";
		$query_perfil_prospecto = mysql_query($sql_perfil_prospecto);
		if (!$query_perfil_prospecto) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$existe_perfil =mysql_num_rows($query_perfil_prospecto);
		$ros = mysql_fetch_array($query_perfil_prospecto);
			
		$id_puesto = $ros['id_puesto'];
		$id_titulo = $ros['id_titulo'];
		$id_sector = $ros['id_sector']; 
		$id_posibilidad_cierre = $ros['id_posibilidad_cierre'];
		$id_nivel_influencia = $ros['id_nivel_influencia'];
		$id_motivo_rechazo = $ros['id_motivo_rechazo'];
		$id_estatus = $ros['id_estatus'];
		$compania = $ros['compania'];
		$estado_civil = $ros['id_estado_civil'];
		$sexo = $ros['sexo'];
		$clase_social =$ros['clase_social'];
		$observacion = $ros['observacion'];
		
		$objResponse->loadCommands(cargaLstEstadoCivil($estado_civil));
		switch ($sexo) {
			case "F" : $objResponse->script("byId('rdbSexoF').checked = true;"); break;
			case "M" : $objResponse->script("byId('rdbSexoM').checked = true;"); break;
		}

		$objResponse->assign("txtCompania","value",utf8_encode($compania));
		$objResponse->assign("txtFechaNacimiento","value",(($ros['fecha_nacimiento'] != "") ? date(spanDateFormat,strtotime($ros['fecha_nacimiento'])) : ""));
		$objResponse->assign("txtObservacion","innerHTML",utf8_encode($observacion));
		$objResponse->script("selectedOption('lstNivelSocial', '".$clase_social."')");
		$objResponse->loadCommands(cargaLstPuesto($id_puesto));
		$objResponse->loadCommands(cargaLstTitulo($id_titulo));
		$objResponse->loadCommands(cargaLstSector($id_sector)); 
		$objResponse->loadCommands(cargaLstNivelInfluencia($id_nivel_influencia));
		$objResponse->loadCommands(cargaLstEstatus($id_estatus));
		$objResponse->loadCommands(cargaLstPosibilidadCierre($id_posibilidad_cierre));
		
		$queryPosibilidad = sprintf("SELECT * FROM crm_posibilidad_cierre WHERE id_posibilidad_cierre = %s;",
			valTpDato($id_posibilidad_cierre, "int"));
		$rs = mysql_query($queryPosibilidad);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$row = mysql_fetch_array($rs);
		
		$motivo = $row['nombre_posibilidad_cierre'];
			
		$objResponse->loadCommands(cargaLstMotivoRechazo($motivo, $id_motivo_rechazo));
		
	} else {
		if (!xvalidaAcceso($objResponse,"an_prospecto_list","insertar")) { $objResponse->script("byId('btnCancelarProspecto').click();"); return $objResponse; }
		
		$objResponse->script("
		byId('txtIdEmpresa').className = 'inputHabilitado';
		byId('txtIdEmpleado').className = 'inputHabilitado';");
		
		$objResponse->loadCommands(asignarEmpresaUsuario($_SESSION['idEmpresaUsuarioSysGts'], "Empresa", "ListaEmpresa", "", false));
		
		$objResponse->loadCommands(asignarEmpleado("Empleado", $_SESSION['idEmpleadoSysGts'], "", ""));
		$objResponse->script("byId('lstTipoProspecto').onchange = function() { }");
		
		$objResponse->loadCommands(cargaLstEstadoCivil());
		$objResponse->loadCommands(cargaLstPuesto());
		$objResponse->loadCommands(cargaLstTitulo());
		$objResponse->loadCommands(cargaLstSector()); 
		$objResponse->loadCommands(cargaLstNivelInfluencia());
		$objResponse->loadCommands(cargaLstEstatus());
		$objResponse->loadCommands(cargaLstPosibilidadCierre());
		$objResponse->loadCommands(cargaLstMotivoRechazo($motivo));
	}
	
	return $objResponse;
}

function guardarCliente($frmCliente, $frmListaCliente) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	global $arrayValidarCI;
	global $arrayValidarRIF;
	global $arrayValidarNIT;
	global $spanEstado;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieClienteEmpresa = $frmCliente['cbxPieClienteEmpresa'];
	
	mysql_query("START TRANSACTION;");
	
	$idCliente = $frmCliente['hddIdCliente'];
	
	switch($frmCliente['lstTipo']) {
		case 1 :
			$lstTipo = "Natural";
			$arrayValidar = $arrayValidarCI;
			break;
		case 2 :
			$lstTipo = "Juridico";
			$arrayValidar = $arrayValidarRIF;
			break;
	}
	
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmCliente['txtCedula'])) {
				$valido = true;
			}
		}
		
		if ($valido == false) {
			$objResponse->script("byId('txtCedula').className = 'inputErrado'");
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$arrayValidar = $arrayValidarNIT;
	if (isset($arrayValidar)) {
		if (strlen($frmCliente['txtNit']) > 0) {
			$valido = false;
			foreach ($arrayValidar as $indice => $valor) {
				if (preg_match($valor, $frmCliente['txtNit'])) {
					$valido = true;
				}
			}
			
			if ($valido == false) {
				$objResponse->script("byId('txtNit').className = 'inputErrado'");
				return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
			}
		}
	}
	
	$arrayValidar = array_merge($arrayValidarCI, $arrayValidarRIF);
	if (isset($arrayValidar)) {
		$valido = false;
		foreach ($arrayValidar as $indice => $valor) {
			if (preg_match($valor, $frmCliente['txtCedulaContacto'])) {
				$valido = true;
			}
		}
		
		if ($valido == false && strlen($frmCliente['txtCedulaContacto']) > 0) {
			$objResponse->script("byId('txtCedulaContacto').className = 'inputErrado'");
			return $objResponse->alert(("Los campos señalados en rojo son requeridos, o no cumplen con el formato establecido"));
		}
	}
	
	$txtCiCliente = explode("-", $frmCliente['txtCedula']);
	if (is_numeric($txtCiCliente[0]) == true) {
		$txtCiCliente = implode("-",$txtCiCliente);
	} else {
		$txtCiClientePuntos = str_split($txtCiCliente[0]);
		if (in_array(".",$txtCiClientePuntos)) { // VERIFICA SI TIENE PUNTOS
			$txtCiCliente = $txtCiCliente[0];
		} else {
			$txtLciCliente = $txtCiCliente[0];
			array_shift($txtCiCliente);
			$txtCiCliente = implode("-",$txtCiCliente);
		}
	}
	
	$txtCiContacto = explode("-", $frmCliente['txtCedulaContacto']);
	if (is_numeric($txtCiContacto[0]) == true) {
		$txtCiContacto = implode("-",$txtCiContacto);
	} else {
		$txtCiContactoPuntos = str_split($txtCiContacto[0]);
		if (in_array(".",$txtCiContactoPuntos)) { // VERIFICA SI TIENE PUNTOS
			$txtCiContacto = $txtCiContacto[0];
		} else {
			$txtLciContacto = $txtCiContacto[0];
			array_shift($txtCiContacto);
			$txtCiContacto = implode("-",$txtCiContacto);
		}
	}
	
	// VERIFICA QUE NO EXISTA LA CEDULA
	$query = sprintf("SELECT * FROM cj_cc_cliente
	WHERE ((lci IS NULL AND %s IS NULL AND ci LIKE %s)
			OR (lci IS NOT NULL AND lci LIKE %s AND ci LIKE %s))
		AND (id <> %s OR %s IS NULL);",
		valTpDato($txtLciCliente, "text"),
		valTpDato($txtCiCliente, "text"),
		valTpDato($txtLciCliente, "text"),
		valTpDato($txtCiCliente, "text"),
		valTpDato($idCliente, "int"),
		valTpDato($idCliente, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	$row = mysql_fetch_array($rs);
	
	if ($totalRows > 0) {
		return $objResponse->alert("Ya existe la ".$spanClienteCxC." ingresada");
	}
	
	$frmCliente['txtUrbanizacion'] = trim(str_replace(",", "", $frmCliente['txtUrbanizacion']));
	$frmCliente['txtCalle'] = trim(str_replace(",", "", $frmCliente['txtCalle']));
	$frmCliente['txtCasa'] = trim(str_replace(",", "", $frmCliente['txtCasa']));
	$frmCliente['txtMunicipio'] = trim(str_replace(",", "", $frmCliente['txtMunicipio']));
	$frmCliente['txtCiudad'] = trim(str_replace(",", "", $frmCliente['txtCiudad']));
	$frmCliente['txtEstado'] = trim(str_replace(",", "", $frmCliente['txtEstado']));
	
	$txtDireccion = implode("; ", array(
		$frmCliente['txtUrbanizacion'],
		$frmCliente['txtCalle'],
		$frmCliente['txtCasa'],
		$frmCliente['txtMunicipio'],
		$frmCliente['txtCiudad'],
		((strlen($frmCliente['txtEstado']) > 0) ? $spanEstado : "")." ".$frmCliente['txtEstado']));
	
	$lstCredito = ($frmCliente['lstCredito'] == "0") ? "si" : "no";
	$cbxPagaImpuesto = (isset($frmCliente['cbxPagaImpuesto'])) ? 1 : 0;
	$cbxBloquearVenta = (isset($frmCliente['cbxBloquearVenta'])) ? 1 : 0;

	if ($idCliente > 0) {
		if (!((($frmCliente['lstCredito'] == "0" || $frmCliente['lstCredito'] == "Si") && xvalidaAcceso($objResponse,"cc_clientes_credito","editar"))
		|| (($frmCliente['lstCredito'] != "0" || $frmCliente['lstCredito'] != "Si") && xvalidaAcceso($objResponse,"cc_clientes_contado","editar")))) {
			return $objResponse;
		}
		
		$updateSQL = sprintf("UPDATE cj_cc_cliente SET
			tipo = %s,
			nombre = %s,
			apellido = %s,
			lci = %s,
			ci = %s,
			nit = %s,
			contribuyente = %s,
			urbanizacion = %s,
			calle = %s,
			casa = %s,
			municipio = %s,
			ciudad = %s,
			estado = %s,
			direccion = %s,
			telf = %s,
			otrotelf = %s,
			correo = %s,
			urbanizacion_postal = %s,
			calle_postal = %s,
			casa_postal = %s,
			municipio_postal = %s,
			ciudad_postal = %s,
			estado_postal = %s,
			contacto = %s,
			lci2 = %s,
			cicontacto = %s,
			telfcontacto = %s,
			correocontacto = %s,
			reputacionCliente = %s,
			descuento = %s,
			fcreacion = %s,
			status = %s,
			credito = %s,
			tipocliente = %s,
			fdesincorporar = %s,
			id_clave_movimiento_predeterminado = %s,
			licencia = %s,
			paga_impuesto = %s,
			bloquea_venta = %s,
			tipo_cuenta_cliente = %s
		WHERE id = %s;",
			valTpDato($lstTipo, "text"),
			valTpDato($frmCliente['txtNombre'], "text"),
			valTpDato($frmCliente['txtApellido'], "text"),
			valTpDato($txtLciCliente, "text"),
			valTpDato($txtCiCliente, "text"),
			valTpDato($frmCliente['txtNit'], "text"),
			valTpDato($frmCliente['lstContribuyente'], "text"),
			valTpDato($frmCliente['txtUrbanizacion'], "text"),
			valTpDato($frmCliente['txtCalle'], "text"),
			valTpDato($frmCliente['txtCasa'], "text"),
			valTpDato($frmCliente['txtMunicipio'], "text"),
			valTpDato($frmCliente['txtCiudad'], "text"),
			valTpDato($frmCliente['txtEstado'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmCliente['txtTelefono'], "text"),
			valTpDato($frmCliente['txtOtroTelefono'], "text"),
			valTpDato($frmCliente['txtCorreo'], "text"),
			valTpDato($frmCliente['txtUrbanizacionPostalCliente'], "text"),
			valTpDato($frmCliente['txtCallePostalCliente'], "text"),
			valTpDato($frmCliente['txtCasaPostalCliente'], "text"),
			valTpDato($frmCliente['txtMunicipioPostalCliente'], "text"),
			valTpDato($frmCliente['txtCiudadPostalCliente'], "text"),
			valTpDato($frmCliente['txtEstadoPostalCliente'], "text"),
			valTpDato($frmCliente['txtNombreContacto'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCiContacto, "text"),
			valTpDato($frmCliente['txtTelefonoContacto'], "text"),
			valTpDato($frmCliente['txtCorreoContacto'], "text"),
			valTpDato($frmCliente['lstReputacionCliente'], "text"),
			valTpDato($frmCliente['lstDescuento'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaCreacion'])),"date"),
			valTpDato($frmCliente['lstEstatus'], "text"),
			valTpDato($lstCredito, "text"),
			valTpDato($frmCliente['lstTipoCliente'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaDesincorporar'])),"date"),
			valTpDato($frmCliente['lstClaveMovimiento'], "int"),
			valTpDato($frmCliente['txtLicencia'], "text"),
			valTpDato($cbxPagaImpuesto, "int"),
			valTpDato($cbxBloquearVenta, "int"),
			valTpDato(2, "int"), // 1 = Prospecto, 2 = Cliente
			valTpDato($idCliente, "text"));
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($updateSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		mysql_query("SET NAMES 'latin1';");
	} else {
		if (!((($frmCliente['lstCredito'] == "0" || $frmCliente['lstCredito'] == "Si") && xvalidaAcceso($objResponse,"cc_clientes_credito","insertar"))
		|| (($frmCliente['lstCredito'] != "0" || $frmCliente['lstCredito'] != "Si") && xvalidaAcceso($objResponse,"cc_clientes_contado","insertar")))) {
			return $objResponse;
		}
		
		$insertSQL = sprintf("INSERT INTO cj_cc_cliente (tipo, nombre, apellido, lci, ci, nit, contribuyente, urbanizacion, calle, casa, municipio, ciudad, estado, direccion, telf, otrotelf, correo, urbanizacion_postal, calle_postal, casa_postal, municipio_postal, ciudad_postal, estado_postal, contacto, lci2, cicontacto, telfcontacto, correocontacto, reputacionCliente, descuento, fcreacion, status, credito, tipocliente, fdesincorporar, id_clave_movimiento_predeterminado, licencia, paga_impuesto, bloquea_venta, tipo_cuenta_cliente)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
			valTpDato($lstTipo, "text"),
			valTpDato($frmCliente['txtNombre'], "text"),
			valTpDato($frmCliente['txtApellido'], "text"),
			valTpDato($txtLciCliente, "text"),
			valTpDato($txtCiCliente, "text"),
			valTpDato($frmCliente['txtNit'], "text"),
			valTpDato($frmCliente['lstContribuyente'], "text"),
			valTpDato($frmCliente['txtUrbanizacion'], "text"),
			valTpDato($frmCliente['txtCalle'], "text"),
			valTpDato($frmCliente['txtCasa'], "text"),
			valTpDato($frmCliente['txtMunicipio'], "text"),
			valTpDato($frmCliente['txtCiudad'], "text"),
			valTpDato($frmCliente['txtEstado'], "text"),
			valTpDato($txtDireccion, "text"),
			valTpDato($frmCliente['txtTelefono'], "text"),
			valTpDato($frmCliente['txtOtroTelefono'], "text"),
			valTpDato($frmCliente['txtCorreo'], "text"),
			valTpDato($frmCliente['txtUrbanizacionPostalCliente'], "text"),
			valTpDato($frmCliente['txtCallePostalCliente'], "text"),
			valTpDato($frmCliente['txtCasaPostalCliente'], "text"),
			valTpDato($frmCliente['txtMunicipioPostalCliente'], "text"),
			valTpDato($frmCliente['txtCiudadPostalCliente'], "text"),
			valTpDato($frmCliente['txtEstadoPostalCliente'], "text"),
			valTpDato($frmCliente['txtNombreContacto'], "text"),
			valTpDato($txtLciContacto, "text"),
			valTpDato($txtCiContacto, "text"),
			valTpDato($frmCliente['txtTelefonoContacto'], "text"),
			valTpDato($frmCliente['txtCorreoContacto'], "text"),
			valTpDato($frmCliente['lstReputacionCliente'], "text"),
			valTpDato($frmCliente['lstDescuento'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaCreacion'])),"date"),
			valTpDato($frmCliente['lstEstatus'], "text"),
			valTpDato($lstCredito, "text"),
			valTpDato($frmCliente['lstTipoCliente'], "text"),
			valTpDato(date("Y-m-d", strtotime($frmCliente['txtFechaDesincorporar'])),"date"),
			valTpDato($frmCliente['lstClaveMovimiento'], "int"),
			valTpDato($frmCliente['txtLicencia'], "text"),
			valTpDato($cbxPagaImpuesto, "int"),
			valTpDato($cbxBloquearVenta, "int"),
			valTpDato(2, "int")); // 1 = Prospecto, 2 = Cliente
		mysql_query("SET NAMES 'utf8';");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idCliente = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	// INSERTA LAS EMPRESAS PARA EL CLIENTE
	if (isset($arrayObjPieClienteEmpresa)) {
		foreach ($arrayObjPieClienteEmpresa as $indicePieClienteEmpresa => $valorPieClienteEmpresa) {
			$idClienteEmpresa = $frmCliente['hddIdClienteEmpresa'.$valorPieClienteEmpresa];
			$idEmpresa = $frmCliente['hddIdEmpresa'.$valorPieClienteEmpresa];
			$idCredito = $frmCliente['hddIdCredito'.$valorPieClienteEmpresa];
			
			if ($idClienteEmpresa > 0) {
				$arrayIdClienteEmpresa[] = $idClienteEmpresa;
			} else {
				$insertSQL = sprintf("INSERT INTO cj_cc_cliente_empresa (id_cliente, id_empresa)
				VALUE (%s, %s);",
					valTpDato($idCliente, "int"),
					valTpDato($idEmpresa, "int"));
				mysql_query("SET NAMES 'utf8'");
				$Result1 = mysql_query($insertSQL);
				if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
				$idClienteEmpresa = mysql_insert_id();
				mysql_query("SET NAMES 'latin1';");
				
				$arrayIdClienteEmpresa[] = $idClienteEmpresa;
			}
			
			if (in_array($frmCliente['lstCredito'], array("0","Si"))) {
				if ($idCredito > 0) {
					if (!xvalidaAcceso($objResponse,"cc_clientes_credito","editar")) { return $objResponse; }
					
					if ($frmCliente['txtDiasCredito'.$valorPieClienteEmpresa] == 0 && $frmCliente['txtLimiteCredito'.$valorPieClienteEmpresa] == 0) {
						$deleteSQL = sprintf("DELETE FROM cj_cc_credito
						WHERE id = %s
							AND creditoreservado = 0;",
							valTpDato($idCredito, "int"));
						$Result1 = mysql_query($deleteSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					} else {
						$updateSQL = sprintf("UPDATE cj_cc_credito SET
							diascredito = %s,
							limitecredito = %s,
							fpago = %s
						WHERE id = %s;",
							valTpDato($frmCliente['txtDiasCredito'.$valorPieClienteEmpresa], "real_inglesa"),
							valTpDato($frmCliente['txtLimiteCredito'.$valorPieClienteEmpresa], "real_inglesa"),
							valTpDato($frmCliente['txtFormaPago'.$valorPieClienteEmpresa], "text"),
							valTpDato($idCredito, "int"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($updateSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						mysql_query("SET NAMES 'latin1';");
						
						$arrayIdCredito[] = $idCredito;
					}
				} else {
					if (str_replace(",","",$frmCliente['txtLimiteCredito'.$valorPieClienteEmpresa]) > 0) {
						if (!xvalidaAcceso($objResponse,"cc_clientes_credito","insertar")) { return $objResponse; }
						
						$insertSQL = sprintf("INSERT INTO cj_cc_credito (id_cliente_empresa, diascredito, limitecredito, fpago)
						VALUE (%s, %s, %s, %s);",
							valTpDato($idClienteEmpresa, "int"),
							valTpDato($frmCliente['txtDiasCredito'.$valorPieClienteEmpresa], "real_inglesa"),
							valTpDato($frmCliente['txtLimiteCredito'.$valorPieClienteEmpresa], "real_inglesa"),
							valTpDato($frmCliente['txtFormaPago'.$valorPieClienteEmpresa], "text"));
						mysql_query("SET NAMES 'utf8'");
						$Result1 = mysql_query($insertSQL);
						if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
						$idCredito = mysql_insert_id();
						mysql_query("SET NAMES 'latin1';");
						
						$arrayIdCredito[] = $idCredito;
					}	
				}
			}
			
			// ACTUALIZA EL CREDITO DISPONIBLE
			$updateSQL = sprintf("UPDATE cj_cc_credito cred, cj_cc_cliente_empresa cliente_emp SET
				creditodisponible = limitecredito - (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
															WHERE fact_vent.idCliente = cliente_emp.id_cliente
																AND fact_vent.id_empresa = cliente_emp.id_empresa
																AND fact_vent.estadoFactura IN (0,2)), 0)
													+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
															WHERE nota_cargo.idCliente = cliente_emp.id_cliente
																AND nota_cargo.id_empresa = cliente_emp.id_empresa
																AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
													- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
															WHERE anticip.idCliente = cliente_emp.id_cliente
																AND anticip.id_empresa = cliente_emp.id_empresa
																AND anticip.estadoAnticipo IN (1,2)
																AND anticip.estatus = 1), 0)
													- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
															WHERE nota_cred.idCliente = cliente_emp.id_cliente
																AND nota_cred.id_empresa = cliente_emp.id_empresa
																AND nota_cred.estadoNotaCredito IN (1,2)), 0)
													+ IFNULL((SELECT
																SUM(IFNULL(ped_vent.subtotal, 0)
																	- IFNULL(ped_vent.subtotal_descuento, 0)
																	+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
																			WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
																	+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
																			WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
															FROM iv_pedido_venta ped_vent
															WHERE ped_vent.id_cliente = cliente_emp.id_cliente
																AND ped_vent.id_empresa = cliente_emp.id_empresa
																AND ped_vent.estatus_pedido_venta IN (2)), 0)),
				creditoreservado = (IFNULL((SELECT SUM(fact_vent.saldoFactura) FROM cj_cc_encabezadofactura fact_vent
											WHERE fact_vent.idCliente = cliente_emp.id_cliente
												AND fact_vent.id_empresa = cliente_emp.id_empresa
												AND fact_vent.estadoFactura IN (0,2)), 0)
									+ IFNULL((SELECT SUM(nota_cargo.saldoNotaCargo) FROM cj_cc_notadecargo nota_cargo
											WHERE nota_cargo.idCliente = cliente_emp.id_cliente
												AND nota_cargo.id_empresa = cliente_emp.id_empresa
												AND nota_cargo.estadoNotaCargo IN (0,2)), 0)
									- IFNULL((SELECT SUM(anticip.saldoAnticipo) FROM cj_cc_anticipo anticip
											WHERE anticip.idCliente = cliente_emp.id_cliente
												AND anticip.id_empresa = cliente_emp.id_empresa
												AND anticip.estadoAnticipo IN (1,2)
												AND anticip.estatus = 1), 0)
									- IFNULL((SELECT SUM(nota_cred.saldoNotaCredito) FROM cj_cc_notacredito nota_cred
											WHERE nota_cred.idCliente = cliente_emp.id_cliente
												AND nota_cred.id_empresa = cliente_emp.id_empresa
												AND nota_cred.estadoNotaCredito IN (1,2)), 0)
									+ IFNULL((SELECT
												SUM(IFNULL(ped_vent.subtotal, 0)
													- IFNULL(ped_vent.subtotal_descuento, 0)
													+ IFNULL((SELECT SUM(ped_vent_gasto.monto) FROM iv_pedido_venta_gasto ped_vent_gasto
															WHERE ped_vent_gasto.id_pedido_venta = ped_vent.id_pedido_venta), 0)
													+ IFNULL((SELECT SUM(ped_vent_iva.subtotal_iva) FROM iv_pedido_venta_iva ped_vent_iva
															WHERE ped_vent_iva.id_pedido_venta = ped_vent.id_pedido_venta), 0))
											FROM iv_pedido_venta ped_vent
											WHERE ped_vent.id_cliente = cliente_emp.id_cliente
												AND ped_vent.id_empresa = cliente_emp.id_empresa
												AND ped_vent.estatus_pedido_venta IN (2)
												AND id_empleado_aprobador IS NOT NULL), 0))
			WHERE cred.id_cliente_empresa = cliente_emp.id_cliente_empresa
				AND cliente_emp.id_cliente = %s
				AND cliente_emp.id_empresa = %s;",
				valTpDato($idCliente, "int"),
				valTpDato($idEmpresa, "int"));
			mysql_query("SET NAMES 'utf8';");
			$Result1 = mysql_query($updateSQL);
			if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
			mysql_query("SET NAMES 'latin1';");
		}
	}
	if ($idCliente > 0 && is_array($arrayIdClienteEmpresa)) {
		$deleteSQL = sprintf("DELETE FROM cj_cc_cliente_empresa
		WHERE id_cliente = %s
			AND (id_cliente_empresa NOT IN (%s) OR %s = '-1');",
			valTpDato($idCliente, "int"),
			valTpDato(implode(",",$arrayIdClienteEmpresa), "campo"),
			valTpDato(implode(",",$arrayIdClienteEmpresa), "text"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Cliente guardado con éxito.");
	
	$objResponse->script("byId('btnCancelarCliente').click();");
	
	$objResponse->loadCommands(listaCliente(
		$frmListaCliente['pageNum'],
		$frmListaCliente['campOrd'],
		$frmListaCliente['tpOrd'],
		$frmListaCliente['valBusq']));
	
	return $objResponse;
}

function guardarProspecto($frmProspecto, $frmListaCliente) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	global $arrayValidarCI;
	global $arrayValidarRIF;
	global $arrayValidarNIT;
	global $spanEstado;
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieModeloInteres = $frmProspecto['cbxPieModeloInteres'];
	
	mysql_query("START TRANSACTION;");
	
	$idEmpresa = $frmProspecto['txtIdEmpresa'];
	$idProspecto = $frmProspecto['hddIdClienteProspecto'];
	
	if (!(count($arrayObjPieModeloInteres) > 0)) {
		return $objResponse->alert("Debe agregar un modelo de interés");
	}
	
	foreach ($arrayObjPieModeloInteres as $indicePieModeloInteres => $valorPieModeloInteres) {
		$objResponse->script("byId('txtPrecioUnidadBasicaItm".$valorPieModeloInteres."').className = 'inputCompleto'");
		$objResponse->script("byId('lstMedioItm".$valorPieModeloInteres."').className = 'inputCompleto'");
		$objResponse->script("byId('lstNivelInteresItm".$valorPieModeloInteres."').className = 'inputCompleto'");
		$objResponse->script("byId('lstPlanPagoItm".$valorPieModeloInteres."').className = 'inputCompleto'");
		
		if (!($frmProspecto['txtPrecioUnidadBasicaItm'.$valorPieModeloInteres] > 0)) { $arrayCantidadInvalida[] = "txtPrecioUnidadBasicaItm".$valorPieModeloInteres; }
		if (!($frmProspecto['lstMedioItm'.$valorPieModeloInteres] > 0)) { $arrayCantidadInvalida[] = "lstMedioItm".$valorPieModeloInteres; }
		if (!($frmProspecto['lstNivelInteresItm'.$valorPieModeloInteres] > 0)) { $arrayCantidadInvalida[] = "lstNivelInteresItm".$valorPieModeloInteres; }
		if (!($frmProspecto['lstPlanPagoItm'.$valorPieModeloInteres] > 0)) { $arrayCantidadInvalida[] = "lstPlanPagoItm".$valorPieModeloInteres; }
	}
	
	// SI HAY CANTIDADES INVALIDAS O NO DISPONIBLES SE PINTARAN DICHOS OBJETOS, CONTENIDOS EN LAS MATRICES
	if (count($arrayCantidadInvalida) > 0) {
		if (count($arrayCantidadInvalida) > 0) {
			foreach ($arrayCantidadInvalida as $indice => $valor) {
				$objResponse->script("byId('".$valor."').className = 'inputCompletoErrado'");
			}
		}
		
		return $objResponse->alert(("Los campos señalados en rojo son invalidos"));
	}
	
	if (($idProspecto > 0 && !xvalidaAcceso($objResponse,"an_prospecto_list","editar"))
	|| (!($idProspecto > 0) && !xvalidaAcceso($objResponse,"an_prospecto_list","insertar"))) { return $objResponse; }
	
	$objDcto = new ModeloProspecto;
	$objDcto->idEmpresa = $idEmpresa;
	$objDcto->idProspecto = $idProspecto;
	$objDcto->idEmpleado = $frmProspecto['txtIdEmpleado'];
	$Result1 = $objDcto->guardarProspecto($frmProspecto);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) { return $objResponse->alert($Result1[1]); }
	$idProspecto = $Result1['idProspecto'];
	$idPerfilProspecto = $Result1['idPerfilProspecto'];
	
	// INSERTA LOS MODELOS DE INTERES NUEVOS
	if (isset($arrayObjPieModeloInteres)) {
		foreach ($arrayObjPieModeloInteres as $indicePieModeloInteres => $valorPieModeloInteres) {
			if ($valorPieModeloInteres != "") {
				if ($frmProspecto['hddIdProspectoVehiculo'.$valorPieModeloInteres] > 0) {
					$updateSQL = sprintf("UPDATE an_prospecto_vehiculo SET
						precio_unidad_basica = %s,
						id_medio = %s,
						id_nivel_interes = %s,
						id_plan_pago = %s
					WHERE id_prospecto_vehiculo = %s;",
						valTpDato($frmProspecto['txtPrecioUnidadBasicaItm'.$valorPieModeloInteres], "real_inglesa"),
						valTpDato($frmProspecto['lstMedioItm'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['lstNivelInteresItm'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['lstPlanPagoItm'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['hddIdProspectoVehiculo'.$valorPieModeloInteres], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($updateSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					mysql_query("SET NAMES 'latin1';");
					
					$arrayIdProspectoVehiculo[] = $frmProspecto['hddIdProspectoVehiculo'.$valorPieModeloInteres];
				} else {
					$insertSQL = sprintf("INSERT INTO an_prospecto_vehiculo (id_cliente, id_unidad_basica, precio_unidad_basica, id_medio, id_nivel_interes, id_plan_pago)
					VALUE (%s, %s, %s, %s, %s, %s);", 
						valTpDato($idProspecto, "int"),
						valTpDato($frmProspecto['hddIdUnidadBasica'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['txtPrecioUnidadBasicaItm'.$valorPieModeloInteres], "real_inglesa"),
						valTpDato($frmProspecto['lstMedioItm'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['lstNivelInteresItm'.$valorPieModeloInteres], "int"),
						valTpDato($frmProspecto['lstPlanPagoItm'.$valorPieModeloInteres], "int"));
					mysql_query("SET NAMES 'utf8';");
					$Result1 = mysql_query($insertSQL);
					if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
					$idProspectoVehiculo = mysql_insert_id();
					mysql_query("SET NAMES 'latin1';");
					
					$arrayIdProspectoVehiculo[] = $idProspectoVehiculo;
				}
			}
		}
	}
	if ($idProspecto > 0 && is_array($arrayIdProspectoVehiculo)) {
		$deleteSQL = sprintf("DELETE FROM an_prospecto_vehiculo
		WHERE id_cliente = %s
			AND (id_prospecto_vehiculo NOT IN (%s) OR %s = '-1');",
			valTpDato($idProspecto, "int"),
			valTpDato(implode(",",$arrayIdProspectoVehiculo), "campo"),
			valTpDato(implode(",",$arrayIdProspectoVehiculo), "text"));
		$Result1 = mysql_query($deleteSQL);
		if (!$Result1) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	}
	
	$valores = "";
	if (isset($frmProspecto["checkDocuemento"])){
		foreach($frmProspecto["checkDocuemento"] AS $indice => $valor){
			$valores .= $valor."<br>";
			$sqlChebox = sprintf("INSERT INTO crm_documentos_recaudados (id_perfil_prospecto, id_documento_venta)
			VALUES (%s,%s)",
				$idPerfilProspecto, 
				$valor);
			mysql_query("SET NAMES 'utf8'");
			$queryChebox = mysql_query($sqlChebox);				
			if (!$queryChebox) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1'");
		}
		
		$objResponse->alert("Fuente de Informacion guardada con éxito.");
	}

	$objResponse->assign("tdChebox","innerHTML",$valores);
	
	// VERIFICA SI TIENE LA EMPRESA AGREGADA
	$query = sprintf("SELECT * FROM cj_cc_cliente_empresa
	WHERE id_cliente = %s
		AND id_empresa = %s;",
		valTpDato($idProspecto, "int"),
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
	$totalRows = mysql_num_rows($rs);
	
	if ($totalRows == 0) {
		$insertSQL = sprintf("INSERT INTO cj_cc_cliente_empresa (id_cliente, id_empresa)
		VALUE (%s, %s);",
			valTpDato($idProspecto, "int"),
			valTpDato($idEmpresa, "int"));
		mysql_query("SET NAMES 'utf8'");
		$Result1 = mysql_query($insertSQL);
		if (!$Result1) { return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__); }
		$idClienteEmpresa = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
	}
	
	mysql_query("COMMIT;");
	
	$objResponse->alert("Prospecto guardado con éxito.");
	
	if ($idPerfilProspecto > 0) {
		$objResponse->alert("Perfil del Prospecto guardado con éxito.");
	}
	
	$objResponse->script("byId('btnCancelarProspecto').click();");
	
	$objResponse->loadCommands(listaCliente(
		$frmListaCliente['pageNum'],
		$frmListaCliente['campOrd'],
		$frmListaCliente['tpOrd'],
		$frmListaCliente['valBusq']));
	
	return $objResponse;
}

function insertarClienteEmpresa($idEmpresa, $frmCliente) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieClienteEmpresa = $frmCliente['cbxPieClienteEmpresa'];
	$contFila = $arrayObjPieClienteEmpresa[count($arrayObjPieClienteEmpresa)-1];
	
	if ($idEmpresa > 0) {
		$existe = false;
		if (isset($arrayObjPieClienteEmpresa)) {
			foreach ($arrayObjPieClienteEmpresa as $indicePieClienteEmpresa => $valorPieClienteEmpresa) {
				if ($frmCliente['hddIdEmpresa'.$valorPieClienteEmpresa] == $idEmpresa) {
					$existe = true;
				}
			}
		}
		
		if ($existe == false) {
			$Result1 = insertarItemClienteEmpresa($contFila, "", $idEmpresa);
			if ($Result1[0] != true && strlen($Result1[1]) > 0) {
				return $objResponse->alert($Result1[1]);
			} else if ($Result1[0] == true) {
				$contFila = $Result1[2];
				$objResponse->script($Result1[1]);
				$arrayObjPieClienteEmpresa[] = $contFila;
			}
		} else {
			$objResponse->alert("Este item ya se encuentra incluido");
		}
	}
	
	// DESBLOQUEA LOS BOTONES DEL LISTADO
	for ($cont = 1; $cont <= 20; $cont++) {
		$objResponse->script(sprintf("byId('btnInsertarEmpresa%s').disabled = false;",
			$cont));
	}
	
	return $objResponse;
}

function insertarModelo($idUnidadBasica, $frmProspecto) {
	$objResponse = new xajaxResponse();
	
	// DESCONCATENA PARA SABER CUANTOS ITEMS HAY AGREGADOS
	$arrayObjPieModeloInteres = $frmProspecto['cbxPieModeloInteres'];
	$contFila = $arrayObjPieModeloInteres[count($arrayObjPieModeloInteres)-1];
	
	// BUSCA LOS DATOS DE LA UNIDAD BASICA
	$query = sprintf("SELECT
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo,
		uni_bas.pvp_venta1
	FROM an_uni_bas uni_bas
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas)
	WHERE uni_bas.id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$row = mysql_fetch_assoc($rs);
	
	$Result1 = insertarItemModeloInteres($contFila, "", $idUnidadBasica, $row['pvp_venta1']);
	if ($Result1[0] != true && strlen($Result1[1]) > 0) {
		return $objResponse->alert($Result1[1]);
	} else if ($Result1[0] == true) {
		$contFila = $Result1[2];
		$objResponse->script($Result1[1]);
		$arrayObjPieModeloInteres[] = $contFila;
	}
	
	$objResponse->script("byId('btnCancelarModelo').click();");
	
	return $objResponse;
}

function listaCliente($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanClienteCxC;
	global $spanNIT;
	global $spanEmail;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$arrayTipoPago = array("NO" => "CONTADO", "SI" => "CRÉDITO");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.credito LIKE %s",
			valTpDato($valCadBusq[1], "text"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.status LIKE %s",
			valTpDato($valCadBusq[2], "text"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.paga_impuesto = %s ",
			valTpDato($valCadBusq[3], "boolean"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("cliente.tipocliente IN (%s)",
			valTpDato("'".str_replace(",","','",$valCadBusq[4])."'", "defined", "'".str_replace(",","','",$valCadBusq[4])."'"));
	}
	
	if ($valCadBusq[5] != "-1" && $valCadBusq[5] != "") {
		$arrayBusq = "";
		if (in_array(1, explode(",",$valCadBusq[5]))) { // Prospecto
			$arrayBusq[] = sprintf("((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0 AND tipo_cuenta_cliente = 1)");
		}
		if (in_array(2, explode(",",$valCadBusq[5]))) { // Prospecto Aprobado (Cliente)
			$arrayBusq[] = sprintf("((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0 AND tipo_cuenta_cliente = 2)");
		}
		if (in_array(3, explode(",",$valCadBusq[5]))) { // Cliente Sin Prospectación
			$arrayBusq[] = sprintf("((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) = 0 AND tipo_cuenta_cliente = 2)");
		}
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond."(".implode(" OR ",$arrayBusq).")";
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(cliente.id_empleado_creador IN (%s)
		OR cliente.id_empleado_creador IS NULL)",
			valTpDato($valCadBusq[6], "campo"));
	}
	
	if ($valCadBusq[7] != "-1" && $valCadBusq[7] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(CONCAT_WS('-', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS('', cliente.lci, cliente.ci) LIKE %s
		OR CONCAT_WS(' ', cliente.nombre, cliente.apellido) LIKE %s
		OR cliente.nit LIKE %s
		OR cliente.licencia LIKE %s
		OR cliente.telf LIKE %s
		OR cliente.correo LIKE %s
		OR perfil_prospecto.compania LIKE %s)",
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"),
			valTpDato("%".$valCadBusq[7]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT
		cliente.id,
		cliente.tipo,
		cliente.nombre,
		cliente.apellido,
		CONCAT_WS('-', cliente.lci, cliente.ci) AS ci_cliente,
		cliente.nit AS nit_cliente,
		cliente.licencia AS licencia_cliente,
		cliente.direccion,
		cliente.telf,
		cliente.otrotelf,
		cliente.correo,
		cliente.credito,
		cliente.status,
		cliente.tipocliente,
		cliente.bloquea_venta,
		cliente.paga_impuesto,
		perfil_prospecto.compania,
		(SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) AS cantidad_modelos,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				1
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0, 2, NULL)
		END) AS tipo_cuenta_cliente,
		(CASE cliente.tipo_cuenta_cliente
			WHEN (1) THEN
				'Prospecto'
			WHEN (2) THEN
				IF ((SELECT COUNT(id_cliente) FROM an_prospecto_vehiculo WHERE id_cliente = cliente.id) > 0,
					'Prospecto Aprobado (Cliente Venta)',
					'Sin Prospectación (Cliente Post-Venta)')
		END) AS descripcion_tipo_cuenta_cliente,
		vw_pg_empleado.nombre_empleado
	FROM cj_cc_cliente cliente
		LEFT JOIN cj_cc_cliente_empresa cliente_emp ON (cliente.id = cliente_emp.id_cliente)
		LEFT JOIN crm_perfil_prospecto perfil_prospecto ON (cliente.id = perfil_prospecto.id)
		LEFT JOIN vw_pg_empleados vw_pg_empleado ON (cliente.id_empleado_creador = vw_pg_empleado.id_empleado) %s", $sqlBusq);
	
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
	
	$htmlTblIni = "<table border=\"0\" class=\"texto_9px\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td colspan=\"2\"></td>";
		$htmlTh .= ordenarCampo("xajax_listaCliente", "6%", $pageNum, "id", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "ci_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, $spanClienteCxC);
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "nit_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, $spanNIT);
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "licencia_cliente", $campOrd, $tpOrd, $valBusq, $maxRows, "Licencia");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "nombre", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "12%", $pageNum, "apellido", $campOrd, $tpOrd, $valBusq, $maxRows, "Apellido");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "14%", $pageNum, "telf", $campOrd, $tpOrd, $valBusq, $maxRows, "Teléfono / ".utf8_encode($spanEmail));
		$htmlTh .= ordenarCampo("xajax_listaCliente", "4%", $pageNum, "paga_impuesto", $campOrd, $tpOrd, $valBusq, $maxRows, "Paga Impuesto");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "4%", $pageNum, "tipo", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "6%", $pageNum, "credito", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo de Pago");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "6%", $pageNum, "compania", $campOrd, $tpOrd, $valBusq, $maxRows, "Compañia");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "4%", $pageNum, "cantidad_modelos", $campOrd, $tpOrd, $valBusq, $maxRows, "Cant. Modelos");
		$htmlTh .= ordenarCampo("xajax_listaCliente", "8%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, "Empleado Creador");
		$htmlTh .= "<td colspan=\"3\"></td>";
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['status']) {
			case "Inactivo" : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>"; break;
			case "Activo" : $imgEstatus = "<img src=\"../img/iconos/ico_verde.gif\" title=\"Activo\"/>"; break;
			default : $imgEstatus = "<img src=\"../img/iconos/ico_rojo.gif\" title=\"Inactivo\"/>";
		}
		
		switch ($row['tipo_cuenta_cliente']) {
			case 1 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_comment.png\" title=\"Prospecto\"/>"; break;
			case 2 : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_green.png\" title=\"Prospecto Aprobado (Cliente Venta)\"/>"; break;
			default : $imgTipoCuentaCliente = "<img src=\"../img/iconos/user_gray.png\" title=\"Sin Prospectación (Cliente Post-Venta)\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>".$imgEstatus."</td>";
			$htmlTb .= "<td>".$imgTipoCuentaCliente."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['id'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['ci_cliente'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['nit_cliente'])."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['licencia_cliente'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['apellido'])."</td>";
			$htmlTb .= "<td align=\"center\">";
				$htmlTb .= "<div>".utf8_encode($row['telf'])."</div>";
				$htmlTb .= ((strlen($row['correo']) > 0) ? "<div align=\"left\"><a class=\"linkAzulUnderline\" href=\"mailto:".utf8_encode($row['correo'])."\">".utf8_encode($row['correo'])."</a></div>" : "");
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"center\">".(($row['paga_impuesto'] == 1) ? "SI" : "NO")."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['tipo'])."</td>";
			$htmlTb .= "<td align=\"center\" class=\"".(($row['credito'] == "no") ? "divMsjInfo" : "divMsjAlerta")."\">".($arrayTipoPago[strtoupper($row['credito'])])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['compania'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['cantidad_modelos']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aEditar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblProspecto', '%s');\"><img class=\"puntero\" src=\"../img/iconos/user_edit.png\" title=\"Editar Prospecto\"/></a>",
					$contFila,
					$row['id']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td>";
			if ($row['tipo_cuenta_cliente'] == 1) {
				$htmlTb .= sprintf("<a class=\"modalImg\" id=\"aAprobar%s\" rel=\"#divFlotante1\" onclick=\"abrirDivFlotante1(this, 'tblCliente', '%s');\"><img class=\"puntero\" src=\"../img/iconos/accept.png\" title=\"Aprobar Prospecto\"/></a>",
					$contFila,
					$row['id']);
			}
			$htmlTb .= "</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
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
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaCliente(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
	
	$objResponse->assign("divListaCliente","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaEmpleado($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanCI;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("vw_pg_empleado.activo = 1");
	
	// 1 = ASESOR VENTAS VEHICULOS, 2 = GERENTE VENTAS VEHICULOS
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_pg_empleado.clave_filtro IN (1,2)");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.id_empresa = %s
		OR %s IN (SELECT usu_emp.id_empresa
					FROM pg_usuario usu
						INNER JOIN pg_usuario_empresa usu_emp ON (usu.id_usuario = usu_emp.id_usuario)
					WHERE usu.id_empleado = vw_pg_empleado.id_empleado))",
			valTpDato($valCadBusq[0], "int"),
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_pg_empleado.cedula LIKE %s
		OR vw_pg_empleado.nombre_empleado LIKE %s
		OR vw_pg_empleado.nombre_cargo LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT
		vw_pg_empleado.id_empleado,
		vw_pg_empleado.cedula,
		vw_pg_empleado.nombre_empleado,
		vw_pg_empleado.nombre_cargo
	FROM vw_pg_empleados vw_pg_empleado %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "10%", $pageNum, "id_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "18%", $pageNum, "cedula", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode($spanCI));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_empleado", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Empleado"));
		$htmlTh .= ordenarCampo("xajax_listaEmpleado", "36%", $pageNum, "nombre_cargo", $campOrd, $tpOrd, $valBusq, $maxRows, utf8_encode("Cargo"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpleado('Empleado', '".$row['id_empleado']."', '".$row['id_empresa']."');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empleado']."</td>";
			$htmlTb .= "<td align=\"right\">".$row['cedula']."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empleado'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_cargo'])."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpleado(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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

function listaEmpresa($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	global $spanRIF;
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq = $cond.sprintf("id_empresa_reg <> 100");
	
	if (strlen($valCadBusq[0]) > 0) {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(nombre_empresa LIKE %s
		OR nombre_empresa_suc LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}
	
	$query = sprintf("SELECT * FROM vw_iv_empresas_sucursales %s", $sqlBusq);
	
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
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "8%", $pageNum, "id_empresa_reg", $campOrd, $tpOrd, $valBusq, $maxRows, ("Id"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, ($spanRIF));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "33%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, ("Empresa"));
		$htmlTh .= ordenarCampo("xajax_listaEmpresa", "33%", $pageNum, "nombre_empresa_suc", $campOrd, $tpOrd, $valBusq, $maxRows, ("Sucursal"));
	$htmlTh .= "</tr>";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		$nombreSucursal = ($row['id_empresa_padre_suc'] > 0) ? $row['nombre_empresa_suc']." (".$row['sucursal'].")" : "";
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\" height=\"24\">";
			$htmlTb .= "<td>";
				$htmlTb .= sprintf("<button type=\"button\" id=\"btnInsertarEmpresa%s\" onclick=\"validarInsertarEmpresa('%s');\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>",
				$contFila,
				$row['id_empresa_reg']);
			$htmlTb .= "</td>";
			$htmlTb .= "<td align=\"right\">".$row['id_empresa_reg']."</td>";
			$htmlTb .= "<td align=\"right\">".utf8_encode($row['rif'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td>".utf8_encode($nombreSucursal)."</td>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaEmpresa(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
				$htmlTb .= "<td width=\"25\"><img src=\"img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
	
	$objResponse->assign("divListaEmpresa","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listaUnidadBasica($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 12, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_iv_modelo.catalogo = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("unidad_emp.id_empresa = %s",
			valTpDato($valCadBusq[0], "int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_marca IN (%s)",
			valTpDato($valCadBusq[1], "campo"));
	}
	
	if ($valCadBusq[2] != "-1" && $valCadBusq[2] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_modelo IN (%s)",
			valTpDato($valCadBusq[2], "campo"));
	}
	
	if ($valCadBusq[3] != "-1" && $valCadBusq[3] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_version IN (%s)",
			valTpDato($valCadBusq[3], "campo"));
	}
	
	if ($valCadBusq[4] != "-1" && $valCadBusq[4] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_iv_modelo.id_ano IN (%s)",
			valTpDato($valCadBusq[4], "campo"));
	}
	
	if ($valCadBusq[6] != "-1" && $valCadBusq[6] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(vw_iv_modelo.nom_uni_bas LIKE %s
		OR vw_iv_modelo.nom_modelo LIKE %s
		OR vw_iv_modelo.nom_version LIKE %s)",
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"),
			valTpDato("%".$valCadBusq[6]."%", "text"));
	}
	
	$query = sprintf("SELECT DISTINCT vw_iv_modelo.*,
		uni_bas.clv_uni_bas
	FROM an_uni_bas uni_bas
		LEFT JOIN sa_unidad_empresa unidad_emp ON (uni_bas.id_uni_bas = unidad_emp.id_unidad_basica)
		INNER JOIN vw_iv_modelos vw_iv_modelo ON (uni_bas.id_uni_bas = vw_iv_modelo.id_uni_bas) %s", $sqlBusq);
	$queryLimit = sprintf(" %s LIMIT %d OFFSET %d", $query, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" class=\"tabla\" cellpadding=\"2\" width=\"100%\">";
	
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$contFila++;
		
		$htmlTb .= (fmod($contFila, 3) == 1) ? "<tr align=\"left\">" : "";
		
		$clase = "divGris trResaltar4";
		
		switch($row['catalogo']) {
			case 0 : $classCatalogo = ""; break;
			case 1 : $classCatalogo = "class=\"divMsjInfo6\""; break;
			default : $classCatalogo = ""; break;
		}
		
		// SI NO EXISTE LA IMAGEN PONE LA DEL LOGO DE LA FAMILIA
		$imgFoto = (!file_exists($row['imagen_auto'])) ? "../".$_SESSION['logoEmpresaSysGts'] : $row['imagen_auto'];
		
		$htmlTb .= "<td valign=\"top\" width=\"33%\">";
			$htmlTb .= "<table align=\"left\" class=\"".$clase."\" height=\"24\" border=\"0\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td ".$classCatalogo." title=\"Id ".$row['id_uni_bas']."\" valign=\"top\">"."<button type=\"button\" onclick=\"xajax_insertarModelo('".$row['id_uni_bas']."', xajax.getFormValues('frmProspecto'));\" title=\"Seleccionar\"><img src=\"../img/iconos/tick.png\"/></button>"."</td>";
				$htmlTb .= sprintf("<td style=\"background-color:#FFFFFF\">%s</td>", "<img src=\"".$imgFoto."\" width=\"80\"/>");
				$htmlTb .= sprintf("<td width=\"%s\">".
						"<div align=\"center\" class=\"divGris\">%s</div>".
						"<div>%s</div>".
						"<div>%s</div>".
						"<div>%s</div>".
						"<div>%s</div>".
					"</td>", "100%",
						utf8_encode($row['nom_uni_bas']),
					utf8_encode($row['nom_marca']),
					utf8_encode($row['nom_modelo']),
					utf8_encode($row['nom_version']),
					"Año ".utf8_encode($row['nom_ano']));
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
		
		$htmlTb .= (fmod($contFila, 3) == 0) ? "</tr>" : "";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
								$htmlTf .= "<option value=\"".$nroPag."\"".(($pageNum == $nroPag) ? "selected=\"selected\"" : "").">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_vehiculos.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listaUnidadBasica(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_vehiculos.gif\"/>");
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
	
	$objResponse->assign("divListaModelo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarEmpleado");
$xajax->register(XAJAX_FUNCTION,"buscarEmpresa");
$xajax->register(XAJAX_FUNCTION,"buscarUnidadBasicaModelo");
$xajax->register(XAJAX_FUNCTION,"buscarCliente");
$xajax->register(XAJAX_FUNCTION,"cargaDocumentosNecesario");
$xajax->register(XAJAX_FUNCTION,"cargaLstAnoBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstClaveMovimiento");
$xajax->register(XAJAX_FUNCTION,"cargaLstCredito");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstado");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstadoCivil");
$xajax->register(XAJAX_FUNCTION,"cargaLstEstatus");
$xajax->register(XAJAX_FUNCTION,"cargaLstMarcaModeloVersion");
$xajax->register(XAJAX_FUNCTION,"cargaLstModuloBuscar");
$xajax->register(XAJAX_FUNCTION,"cargaLstMotivoRechazo");
$xajax->register(XAJAX_FUNCTION,"cargaLstNivelInfluencia");
$xajax->register(XAJAX_FUNCTION,"cargaLstPosibilidadCierre");
$xajax->register(XAJAX_FUNCTION,"cargaLstPuesto");
$xajax->register(XAJAX_FUNCTION,"cargaLstSector");
$xajax->register(XAJAX_FUNCTION,"cargaLstTitulo");
$xajax->register(XAJAX_FUNCTION,"cargaLstVendedor");
$xajax->register(XAJAX_FUNCTION,"cargarPagina");
$xajax->register(XAJAX_FUNCTION,"eliminarClienteEmpresa");
$xajax->register(XAJAX_FUNCTION,"eliminarModelo");
$xajax->register(XAJAX_FUNCTION,"exportarCliente");
$xajax->register(XAJAX_FUNCTION,"formCliente");
$xajax->register(XAJAX_FUNCTION,"formCredito");
$xajax->register(XAJAX_FUNCTION,"formProspecto");
$xajax->register(XAJAX_FUNCTION,"guardarCliente");
$xajax->register(XAJAX_FUNCTION,"guardarProspecto");
$xajax->register(XAJAX_FUNCTION,"insertarClienteEmpresa");
$xajax->register(XAJAX_FUNCTION,"insertarModelo");
$xajax->register(XAJAX_FUNCTION,"listaCliente");
$xajax->register(XAJAX_FUNCTION,"listaEmpleado");
$xajax->register(XAJAX_FUNCTION,"listaEmpresa");
$xajax->register(XAJAX_FUNCTION,"listaUnidadBasica");

function buscarEnArray($array, $dato) {
	// Retorna el indice de la posicion donde se encuentra el elemento en el array o null si no se encuentra
	$x = 0;
	foreach ($array as $indice => $valor) {
		if ($valor == $dato) return $x;
		
		$x++;
	}
	return NULL;
}

function cargaLstMedioItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
	FROM grupositems git
		LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
	WHERE gps.grupo = 'medios' AND status = 1
	ORDER BY item");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idItem'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idItem']."\">".utf8_encode($row['item'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstNivelInteresItm($nombreObjeto, $selId = "", $bloquearObj = false){
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$array = array(1 => "Bajo", 2 => "Medio", 3 => "Alto");
	$totalRows = count($array);
	
	$html .= "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	foreach ($array as $indice => $valor) {
		$selected = (($selId != "" && $selId == $indice) || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$indice."\">".($valor)."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function cargaLstPlanPagoItm($nombreObjeto, $selId = "", $bloquearObj = false) {
	$class = ($bloquearObj == true) ? "" : "class=\"inputHabilitado\"";
	$onChange = ($bloquearObj == true) ? "onchange=\"selectedOption(this.id,'".$selId."');\"" : "";
	
	$query = sprintf("SELECT git.idItem AS idItem, git.item AS item
	FROM grupositems git
		LEFT JOIN grupos gps ON git.idGrupo = gps.idGrupo
	WHERE gps.grupo = 'planesDePago' AND status = 1
	ORDER BY item");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__);
	$totalRows = mysql_num_rows($rs);
	$html = "<select id=\"".$nombreObjeto."\" name=\"".$nombreObjeto."\" ".$class." ".$onChange." style=\"width:99%\">";
		$html .= "<option value=\"-1\">[ Seleccione ]</option>";
	while ($row = mysql_fetch_assoc($rs)) {
		$selected = ($selId == $row['idItem'] || $totalRows == 1) ? "selected=\"selected\"" : "";
		
		$html .= "<option ".$selected." value=\"".$row['idItem']."\">".utf8_encode($row['item'])."</option>";
	}
	$html .= "</select>";
	
	return $html;
}

function insertarItemClienteEmpresa($contFila, $idClienteEmpresa = "", $idEmpresa = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idClienteEmpresa > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryClienteEmpresa = sprintf("SELECT
			cliente_emp.id_cliente_empresa,
			cliente_emp.id_empresa,
			cred.id AS id_credito,
			cred.diascredito,
			cred.fpago,
			cred.limitecredito,
			cred.creditoreservado,
			cred.creditodisponible,
			cred.intereses
		FROM cj_cc_credito cred
			RIGHT JOIN cj_cc_cliente_empresa cliente_emp ON (cred.id_cliente_empresa = cliente_emp.id_cliente_empresa)
		WHERE cliente_emp.id_cliente_empresa = %s;",
			valTpDato($idClienteEmpresa, "int"));
		$rsClienteEmpresa = mysql_query($queryClienteEmpresa);
		if (!$rsClienteEmpresa) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsClienteEmpresa = mysql_num_rows($rsClienteEmpresa);
		$rowClienteEmpresa = mysql_fetch_assoc($rsClienteEmpresa);
	}
	
	$idEmpresa = ($idEmpresa == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['id_empresa'] : $idEmpresa;
	$idCredito = ($idCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['id_credito'] : $idCredito;
	$txtDiasCredito = ($txtDiasCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['diascredito'] : $txtDiasCredito;
	$txtFormaPago = ($txtFormaPago == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['fpago'] : $txtFormaPago;
	$txtLimiteCredito = ($txtLimiteCredito == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['limitecredito'] : $txtLimiteCredito;
	$txtCreditoReservado = ($txtCreditoReservado == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['creditoreservado'] : $txtCreditoReservado;
	$txtCreditoDisponible = ($txtCreditoDisponible == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['creditodisponible'] : $txtCreditoDisponible;
	$txtIntereses = ($txtIntereses == "" && $totalRowsClienteEmpresa > 0) ? $rowClienteEmpresa['intereses'] : $txtIntereses;
	
	// BUSCA LOS DATOS DE LA EMPRESA
	$query = sprintf("SELECT
		IF (vw_iv_emp_suc.id_empresa_suc > 0, CONCAT_WS(' - ', vw_iv_emp_suc.nombre_empresa, vw_iv_emp_suc.nombre_empresa_suc), vw_iv_emp_suc.nombre_empresa) AS nombre_empresa
	FROM vw_iv_empresas_sucursales vw_iv_emp_suc
	WHERE id_empresa_reg = %s;",
		valTpDato($idEmpresa, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieClienteEmpresa').before('".
		"<tr id=\"trItm:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItm:%s\"><input id=\"cbxItm\" name=\"cbxItm[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxPieClienteEmpresa\" name=\"cbxPieClienteEmpresa[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtDiasCredito%s\" name=\"txtDiasCredito%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtFormaPago%s\" name=\"txtFormaPago%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:left\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtLimiteCredito%s\" name=\"txtLimiteCredito%s\" class=\"inputSinFondo\" readonly=\"readonly\" style=\"text-align:right\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCreditoReservado%s\" name=\"txtCreditoReservado%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td><input type=\"text\" id=\"txtCreditoDisponible%s\" name=\"txtCreditoDisponible%s\" class=\"inputSinFondo\" readonly=\"readonly\" value=\"%s\"></td>".
			"<td><a id=\"aEditarCredito%s\" class=\"modalImg\" rel=\"#divFlotante2\"><img class=\"puntero\" src=\"../img/iconos/edit_privilegios.png\" title=\"Editar Crédito\"/></a>".
				"<input type=\"hidden\" id=\"hddIdClienteEmpresa%s\" name=\"hddIdClienteEmpresa%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdCredito%s\" name=\"hddIdCredito%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdEmpresa%s\" name=\"hddIdEmpresa%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('aEditarCredito%s').onclick = function() {
			abrirDivFlotante2(this, 'tblCredito', '%s');
		}",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['nombre_empresa']),
			$contFila, $contFila, number_format($txtDiasCredito, 0, ".", ","),
			$contFila, $contFila, $txtFormaPago,
			$contFila, $contFila, number_format($txtLimiteCredito, 2, ".", ","),
			$contFila, $contFila, number_format($txtCreditoReservado, 2, ".", ","),
			$contFila, $contFila, number_format($txtCreditoDisponible, 2, ".", ","),
			$contFila,
				$contFila, $contFila, $idClienteEmpresa,
				$contFila, $contFila, $idCredito,
				$contFila, $contFila, $idEmpresa,
			
		$contFila,
			$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}

function insertarItemModeloInteres($contFila, $idProspectoVehiculo = "", $idUnidadBasica = "", $txtPrecioUnidadBasicaItm = "", $lstMedioItm = "", $lstNivelInteresItm = "", $lstPlanPagoItm = "") {
	$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
	$contFila++;
	
	if ($idProspectoVehiculo > 0) {
		// BUSCA LOS DATOS DEL DETALLE DEL PEDIDO
		$queryProspectoVehiculo = sprintf("SELECT 
			prospecto_veh.id_prospecto_vehiculo,
			prospecto_veh.id_cliente,
			prospecto_veh.id_unidad_basica,
			prospecto_veh.precio_unidad_basica,
			prospecto_veh.id_medio,
			prospecto_veh.id_plan_pago,
			prospecto_veh.id_nivel_interes
		FROM an_prospecto_vehiculo prospecto_veh
		WHERE prospecto_veh.id_prospecto_vehiculo = %s;",
			valTpDato($idProspectoVehiculo, "int"));
		$rsProspectoVehiculo = mysql_query($queryProspectoVehiculo);
		if (!$rsProspectoVehiculo) return array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila, $arrayObjUbicacion);
		$totalRowsProspectoVehiculo = mysql_num_rows($rsProspectoVehiculo);
		$rowProspectoVehiculo = mysql_fetch_assoc($rsProspectoVehiculo);
	}
	
	$idUnidadBasica = ($idUnidadBasica == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_unidad_basica'] : $idUnidadBasica;
	$txtPrecioUnidadBasicaItm = ($txtPrecioUnidadBasicaItm == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['precio_unidad_basica'] : $txtPrecioUnidadBasicaItm;
	$lstMedioItm = ($lstMedioItm == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_medio'] : $lstMedioItm;
	$lstNivelInteresItm = ($lstNivelInteresItm == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_nivel_interes'] : $lstNivelInteresItm;
	$lstPlanPagoItm = ($lstPlanPagoItm == "" && $totalRowsProspectoVehiculo > 0) ? $rowProspectoVehiculo['id_plan_pago'] : $lstPlanPagoItm;
	
	// BUSCA LOS DATOS DE LA UNIDAD BASICA
	$query = sprintf("SELECT
		CONCAT('[', vw_iv_modelo.nom_uni_bas, ']: ', vw_iv_modelo.nom_marca, ' ', vw_iv_modelo.nom_modelo, ' ', vw_iv_modelo.nom_version, ' ', vw_iv_modelo.nom_ano) AS vehiculo
	FROM vw_iv_modelos vw_iv_modelo
	WHERE id_uni_bas = %s;",
		valTpDato($idUnidadBasica, "int"));
	$rs = mysql_query($query);
	if (!$rs) array(false, mysql_error()."\nError Nro: ".mysql_errno()."\nLine: ".__LINE__, $contFila);
	$row = mysql_fetch_assoc($rs);
	
	// INSERTA EL ARTICULO MEDIANTE INJECT
	$htmlItmPie = sprintf("$('#trItmPieModeloInteres').before('".
		"<tr id=\"trItmModeloInteres:%s\" align=\"left\" class=\"textoGris_11px %s\">".
			"<td title=\"trItmModeloInteres:%s\"><input id=\"cbxItmModeloInteres\" name=\"cbxItmModeloInteres[]\" type=\"checkbox\" value=\"%s\"/>".
				"<input id=\"cbxPieModeloInteres\" name=\"cbxPieModeloInteres[]\" type=\"checkbox\" checked=\"checked\" style=\"display:none\" value=\"%s\"></td>".
			"<td>%s</td>".
			"<td><input type=\"text\" id=\"txtPrecioUnidadBasicaItm%s\" name=\"txtPrecioUnidadBasicaItm%s\" class=\"inputCompletoHabilitado\" onkeypress=\"return validarSoloNumerosReales(event);\" style=\"text-align:right\" value=\"%s\"/></td>".
			"<td>%s</td>".
			"<td>%s</td>".
			"<td>%s".
				"<input type=\"hidden\" id=\"hddIdProspectoVehiculo%s\" name=\"hddIdProspectoVehiculo%s\" readonly=\"readonly\" value=\"%s\"/>".
				"<input type=\"hidden\" id=\"hddIdUnidadBasica%s\" name=\"hddIdUnidadBasica%s\" readonly=\"readonly\" value=\"%s\"/></td>".
		"</tr>');
		
		byId('txtPrecioUnidadBasicaItm%s').onblur = function() {
			setFormatoRafk(this,2);
		}",
		$contFila, $clase,
			$contFila, $contFila,
				$contFila,
			utf8_encode($row['vehiculo']),
				$contFila, $contFila, number_format($txtPrecioUnidadBasicaItm, 2, ".", ","),
			cargaLstMedioItm("lstMedioItm".$contFila, $lstMedioItm),
			cargaLstNivelInteresItm("lstNivelInteresItm".$contFila, $lstNivelInteresItm),
			cargaLstPlanPagoItm("lstPlanPagoItm".$contFila, $lstPlanPagoItm),
				$contFila, $contFila, $idProspectoVehiculo,
				$contFila, $contFila, $idUnidadBasica,
			
		$contFila);
	
	return array(true, $htmlItmPie, $contFila);
}
?>