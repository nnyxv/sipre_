<?php

function aporbarSolicitudCompras($idSolicitudCompras,$idObj){
	$objResponse = new xajaxResponse();

	$sql = sprintf("SELECT 
		id_solicitud_compra, 
		ga_solicitud_compra.id_estado_solicitud_compras, 
		estado_solicitud_compras
	FROM ga_solicitud_compra
	LEFT JOIN ga_estado_solicitud_compra ON ga_estado_solicitud_compra.id_estado_solicitud_compras = ga_solicitud_compra.id_estado_solicitud_compras
	WHERE id_solicitud_compra = %s", 
	valTpDato($idSolicitudCompras, "int"));	
	mysql_query("SET NAMES 'utf8'");				
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	$rows = mysql_fetch_array($rs);
	
	$objResponse->script("byId('codEmpleado').value = ''");				
	$objResponse->assign("idEstadoSolicitud","value",$rows['id_estado_solicitud_compras']);
	$objResponse->assign("idSolicitudCompra","value",$rows['id_solicitud_compra']);

	if($rows['id_estado_solicitud_compras'] == 4 || $rows['id_estado_solicitud_compras'] == 5 || 
	$rows['id_estado_solicitud_compras'] == 7 ){
		
		switch($rows['id_estado_solicitud_compras']){
			case 4:
				return	$objResponse->alert('Estas Solicitud ya Fue Procesada');
			case 5:
				return	$objResponse->alert('Estas Solicitud ya Fue Ordenada');
			case 7:
				return	$objResponse->alert('Estas Solicitud Fue Rechazada');
		}
		
	} else {
		$objResponse->script("
			openImg(byId('divFlotante'));
			document.getElementById('codEmpleado').focus();
			$('#tablaCondicionar').hide();");
			
		switch ($rows['id_estado_solicitud_compras']){
			case 0:
				$estadoSolicitud = "Enviar ";
				//$estado_solicitud="NO Enviada";
				break;
			case 1:
				$estadoSolicitud = "Aprobar ";
				$img = "<img src=\"../img/iconos/accept.png\"/>";
				//$estado_solicitud="En espera de Aprobacion";
				
				break;
			case 2:
				$estadoSolicitud = "Conformar ";
				$img = "<img src=\"../img/iconos/ico_aceptar_amarillo.png\"/>";
				//$estado_solicitud="APROBADA - En espera de Conformación";
				break;
			case 3:
				$estadoSolicitud = "Procesar ";
				$img = "<img src=\"../img/iconos/ico_aceptar_naranja.png\"/>";
				$objResponse->script("$('#tablaCondicionar').show();");
				//$estado_solicitud="CONFORMADA - En espera de Proceso";
				break;
			case 6:
				$estadoSolicitud = "Procesar";
				$img = "<img src=\"../img/iconos/ico_aceptar_naranja.png\"/>";
				$objResponse->script("$('#tablaCondicionar').show();
									byId('idEstadoSolicitud').value = '';");
				//$estado_solicitud="EN ORDEN DE COMPRA";
				break;
			default:
				$estadoSolicitud = "";
				//$estado_solicitud="CULMINADA";
				break;
		}
		$objResponse->assign("tdFlotanteTitulo","innerHTML",$estadoSolicitud." Solicitud");
		$objResponse->assign("tdbtnNomb","innerHTML",$estadoSolicitud);
		$objResponse->assign("tdImg","innerHTML",$img);
	}
	
	return $objResponse;
}

function asignarEmpDepartamentoCento($tipoAsg, $id){
	$objResponse = new xajaxResponse();
	
	switch($tipoAsg){
		case 1://asigna empresa
			$sql = sprintf("SELECT * FROM pg_empresa WHERE id_empresa = %s",$id);
				break; 
		case 2://asigna departamento
			$sql = sprintf("SELECT * FROM pg_departamento WHERE id_departamento = %s",$id);
				break; 
		case 3://unidad centro de costo
			$sql = sprintf("SELECT * FROM pg_unidad_centro_costo WHERE id_unidad_centro_costo = %s",$id);
				break; 
	}
	
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rows = mysql_fetch_array($rs);
	
	switch($tipoAsg){
		case 1://asigna empresa
			$objResponse->assign("idEmpresa","value",$rows['id_empresa']);
			$objResponse->assign("codEmpresa","value",$rows['codigo_empresa']);
			$objResponse->assign("nombEmpresa","value",$rows['nombre_empresa']);
				break; 
		case 2://asigna departamento
			$objResponse->assign("idDepartamento","value",$rows['id_departamento']);
			$objResponse->assign("codDepartamento","value",$rows['codigo_departamento']);
			$objResponse->assign("nombDepartamento","value",$rows['nombre_departamento']);
			$objResponse->script("
				$('#btnDepartamento').hide();
				$('#btnCentroCosto').show();");
				break; 
		case 3://unidad centro de costo
			$objResponse->assign("idCentroCosto","value",$rows['id_unidad_centro_costo']);
			$objResponse->assign("codCentroCosto","value",$rows['codigo_unidad_centro_costo']);
			$objResponse->assign("nombCentroCosto","value",$rows['nombre_unidad_centro_costo']);
			$objResponse->script("$('#btnCentroCosto').hide();");
				break; 
	}
		
	return $objResponse;
}

function buscarArticulo($valorFrom){
	$objResponse = new xajaxResponse();
	
	$valBus = sprintf("%s",$valorFrom["textCriterioArt"]);
	
	$objResponse->loadCommands(listadoArticulo(0,'','',$valBus));
		
	return $objResponse;
}

function BuscarSolicituComp($valorFrom){//HACE LA BUSQUEDA
	$objResponse = new xajaxResponse();
	
	$valBus = sprintf("%s|%s|%s|%s|%s",
		$valorFrom["lstEmpresa"],
		$valorFrom["txtNroSolicitud"],
		$valorFrom["lisTipCompras"],
		$valorFrom["lisEstCompras"],
		$valorFrom["txtCriterio"]);

	$objResponse->loadCommands(listadoSolicitudCompra(0,'','', $valBus));
	
	return $objResponse;
}

function BuscarempDepaUnidadCentroCosto($valorFrom,$tipoBus){//HACE LA BUSQUEDA
	$objResponse = new xajaxResponse();

	switch($tipoBus){ // listadoCentroCosto
		case "Empresa":
			$valBus = sprintf("%s", $valorFrom["textCriterio"]);
			$objResponse->loadCommands(listadoEmpresas(0,'','', $valBus));
			break;	
		case "Departamento":
			$objResponse->script("xajax_listadoDepartamento(0,'','',$('#idEmpresa').val()+'|'+$('#textCriterio').val());");
			break;	
		case "centroCosto":
			$objResponse->script("xajax_listadoCentroCosto(0,'','',$('#idDepartamento').val()+'|'+$('#textCriterio').val());");
			break;	
	}
	
	return $objResponse;
}

function calcularPrecio($valFrmPresio){
	$objResponse = new xajaxResponse();

	$objResponse->script("byId('totalPrecio').value = '';");
	
	$precioT = 0;
	foreach($valFrmPresio['textArtCant'] as $indices => $valor){
		$cant = $valor;
		$precio = $valFrmPresio['textArtPrecio'][$indices];
		
		if($cant != "" && $cant != 0 && $precio != "" && $precio != 0 ){
			$precioT += $cant * $precio;
		}		
	}
		
	$objResponse->assign("totalPrecio", "value", $precioT);

	return $objResponse;
}

function cargarSolicitudCompras($idSolicitudCompras){
	$objResponse = new xajaxResponse();

	$sql = sprintf("SELECT 
		id_solicitud_compra,
		CONCAT_WS('-',codigo_empresa,numero_solicitud) AS numSolicitud,
		ga_solicitud_compra.id_empresa,
		codigo_empresa,
		nombre_empresa,
		fecha_solicitud,
		ga_solicitud_compra.id_unidad_centro_costo,
		codigo_unidad_centro_costo,
		nombre_unidad_centro_costo,
		pg_unidad_centro_costo.id_departamento,
		nombre_departamento,
		codigo_departamento,
		tipo_compra,
		id_proveedor,
		justificacion_proveedor,
		observaciones,
		sustitucion,
		presupuestado,
		justificacion_compra,
		id_estado_solicitud_compras, 
		id_empleado_solicitud, CONCAT_WS(' ', a.nombre_empleado, a.apellido) AS nombre_empleado_solictud,
		a.cedula AS num_empleado_solicitud, 
		fecha_empleado_solicitud, 
		id_empleado_aprobacion, 
		CONCAT_WS(' ', b.nombre_empleado, b.apellido) AS nombre_empleado_aprobacion, 
		b.cedula AS num_empleado_aprobacion,
		fecha_empleado_aprobacion, 
		id_empleado_conformacion, 
		CONCAT_WS(' ', c.nombre_empleado, c.apellido) AS nombre_empleado_conformacion, 
		c.cedula AS num_empleado_conformacion,
		fecha_empleado_conformacion, 
		id_empleado_proceso, 
		CONCAT_WS(' ', d.nombre_empleado, d.apellido) AS nombre_empleado_proceso,
		d.cedula AS num_empleado_proceso,
		fecha_empleado_proceso, 
		fecha_creacion, 
		id_empleado_condicionamiento,
		CONCAT_WS(' ', e.nombre_empleado, e.apellido) AS nombre_empleado_condicionamiento,
		e.cedula AS num_empleado_condicionamiento,
		fecha_modificacion,
		numero_actualizacion, 
		motivo_condicionamiento,
		fecha_empleado_condicionamiento
	FROM ga_solicitud_compra
	LEFT JOIN pg_empresa ON pg_empresa.id_empresa = ga_solicitud_compra.id_empresa
	LEFT JOIN pg_unidad_centro_costo ON pg_unidad_centro_costo.id_unidad_centro_costo = ga_solicitud_compra.id_unidad_centro_costo
	LEFT JOIN pg_departamento ON pg_departamento.id_departamento = pg_unidad_centro_costo.id_departamento
	LEFT JOIN pg_empleado a ON a.id_empleado = ga_solicitud_compra.id_empleado_solicitud
	LEFT JOIN pg_empleado b ON b.id_empleado = ga_solicitud_compra.id_empleado_aprobacion
	LEFT JOIN pg_empleado c ON c.id_empleado = ga_solicitud_compra.id_empleado_conformacion
	LEFT JOIN pg_empleado d ON d.id_empleado = ga_solicitud_compra.id_empleado_proceso
	LEFT JOIN pg_empleado e ON e.id_empleado = ga_solicitud_compra.id_empleado_condicionamiento
	WHERE id_solicitud_compra = %s",
	valTpDato($idSolicitudCompras, "int"));
			
	mysql_query("SET NAMES 'utf8'");
	$query = mysql_query($sql);
	if (!$query) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	$rows = mysql_fetch_array($query);	
	
	//$objResponse->assign("idSolicitudCompras","value",$rows['id_solicitud_compra']);
	$objResponse->assign("numSolicitud","value",$rows['numSolicitud']);
	if($rows["fecha_solicitud"] != "" ){
		$fechaSolicitud = date(spanDateFormat, strtotime($rows["fecha_solicitud"]));
	}
	$objResponse->assign("fechaSolicitud","value",$fechaSolicitud);
	$objResponse->assign("codEmpresa","value",$rows['codigo_empresa']);
	$objResponse->assign("nombEmpresa","value",($rows['nombre_empresa']));
	$objResponse->assign("idEmpresa","value",$rows['id_empresa']);
	$objResponse->assign("codDepartamento","value",$rows['codigo_departamento']);
	$objResponse->assign("nombDepartamento","value",($rows['nombre_departamento']));
	$objResponse->assign("idDepartamento","value",$rows['id_departamento']);
	$objResponse->assign("codCentroCosto","value",$rows['codigo_unidad_centro_costo']);
	$objResponse->assign("nombCentroCosto","value",($rows['nombre_unidad_centro_costo']));
	$objResponse->assign("idCentroCosto","value",$rows['id_unidad_centro_costo']);
	
	switch($rows['tipo_compra']){//tipo compra
		case 2:
			$radiTipo = "tipoCompra2";
			$objResponse->script("muestratabla('')");				
				break;	
		case 3:
			$radiTipo = "tipoCompra3";
			$objResponse->script("muestratabla('')");	
				break;	
		case 4:
			$radiTipo = "tipoCompra4";
			$objResponse->script("muestratabla('')");
				break;	
	}
	$objResponse->assign($radiTipo,"checked",true);
	
	$objResponse->loadCommands(combLstProveedor($rows['id_proveedor']));
	$objResponse->assign("justificacionProveedor","value",($rows['justificacion_proveedor']));
	$objResponse->assign("ObservacionProveedor","value",($rows['observaciones']));
	
	switch($rows['sustitucion']){//tipo compra
		case 1:
			$radiSus = "sustitucion1";
			break;	
		case 2:
			$radiSus = "sustitucion2";
			break;	
	}
	$objResponse->assign($radiSus,"checked",true); //radio buton

	if($rows['presupuestado'] == 1){
		$objResponse->assign("presupuestado0","checked",true);	
	} else {
		$objResponse->assign("presupuestado0","checked",false);
	}
	
	$objResponse->assign("justificacionCompra","value",($rows['justificacion_compra']));
	
	if($rows['id_estado_solicitud_compras']!= 2){}
		
	$objResponse->script("$('#tabAprobacion').show();");
	//empleado solicitud
	$objResponse->assign("tdNombFirmaS","innerHTML",($rows['nombre_empleado_solictud']));
	$objResponse->assign("tdnumEmplS","innerHTML",($rows['num_empleado_solicitud']));
	if($rows["fecha_empleado_solicitud"] != "" ){
		$fechaS = date(spanDateFormat, strtotime($rows["fecha_empleado_solicitud"]));
	}
	$objResponse->assign("tdfechaS","innerHTML",$fechaS);
	//empleado aprobacion
	$objResponse->assign("tdNombFirmaA","innerHTML",($rows['nombre_empleado_aprobacion']));
	$objResponse->assign("tdnumEmplA","innerHTML",($rows['num_empleado_aprobacion']));
	if($rows["fecha_empleado_aprobacion"] != "" ){
		$fechaA = date(spanDateFormat, strtotime($rows["fecha_empleado_aprobacion"]));
	}		
	$objResponse->assign("tdfechaA","innerHTML",$fechaA);
	//empleado conformacion
	$objResponse->assign("tdNombFirmaC","innerHTML",($rows['nombre_empleado_conformacion']));
	$objResponse->assign("tdnumEmplC","innerHTML",($rows['num_empleado_conformacion']));
	if($rows["fecha_empleado_conformacion"] != "" ){
		$fechaC = date(spanDateFormat, strtotime($rows["fecha_empleado_conformacion"]));
	}		
	$objResponse->assign("tdfechaC","innerHTML",$fechaC);
	//empleado proceso
	$objResponse->assign("tdNombFirmaP","innerHTML",($rows['nombre_empleado_proceso']));
	$objResponse->assign("tdnumEmplP","innerHTML",($rows['num_empleado_proceso']));
	if($rows["fecha_empleado_proceso"] != "" ){
		$fechaP = date(spanDateFormat, strtotime($rows["fecha_empleado_proceso"]));
	}		
	$objResponse->assign("tdfechaP","innerHTML",$fechaP);
	
	if($rows["id_estado_solicitud_compras"]== 6){
		$motivo = "Motivo de Condicionamiento";
	} else {
		$motivo = "Motivo de Rechazo";
	}			
	$objResponse->assign("tdMotivoCondicional","innerHTML",$motivo);
	$objResponse->assign("tdNombConRe","innerHTML",$rows['nombre_empleado_condicionamiento']);
	$objResponse->assign("tdnumEmplConRe","innerHTML",$rows['num_empleado_condicionamiento']);
	if($rows["fecha_empleado_condicionamiento"] != "" ){
		$fechaConRe = date(spanDateFormat, strtotime($rows["fecha_empleado_condicionamiento"]));
	}
	$objResponse->assign("tdfechaConRe","innerHTML",$fechaConRe);
	$objResponse->assign("texAreaConRe","value",$rows["motivo_condicionamiento"]);	

	$objResponse->script("openImg(byId('divFlotante1'))");
	
	return $objResponse;
}

function cargarDetallesSolComp($idSolCompras, $idArticulo){
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT 
		id_detalle_solicitud_compra, 
		id_solicitud_compra,
		ga_detalle_solicitud_compra.id_articulo,
		unidad,codigo_articulo,
		descripcion,
		cantidad, 
		precio_sugerido,
		fecha_requerida
	FROM ga_detalle_solicitud_compra
	LEFT JOIN ga_articulos ON  ga_articulos.id_articulo = ga_detalle_solicitud_compra.id_articulo
	LEFT JOIN ga_tipos_unidad ON ga_tipos_unidad.id_tipo_unidad = ga_articulos.id_tipo_unidad
	WHERE ga_detalle_solicitud_compra.id_articulo = %s 
	AND id_solicitud_compra = %s;",
	valTpDato($idArticulo, "int"),
	valTpDato($idSolCompras, "int"));
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$nombTab = "tabTipoCompra";

	$rows = mysql_fetch_array($rs);
	$inputnCheck = sprintf("<input type='checkbox' value='%s|%s' id='checkArticulo%s' name='checkArticulo[]'>",
					$rows["id_articulo"],$rows["id_articulo"],$rows["id_articulo"]);
				
	$inputText = sprintf("<input type='hidden' value='%s|%s' id='textcheckArticulo%s' name='textcheckArticulo[]'>",
					$rows["id_articulo"],$rows["id_articulo"],$rows["id_articulo"]);
					
	$inputTextCant = sprintf("<input type='text' class='inputHabilitado' id='textArtCant%s' name='textArtCant[]' size='5px'  value='%s' style='text-align:center;border:0px'>",
					$rows["id_articulo"],$rows['cantidad']);
					
	$inputTextpresio = sprintf("<input type='text' class='inputHabilitado' id='textArtPrecio%s' name='textArtPrecio[]' size='10px' value='%s' style='text-align:center;border:0px' onchange='xajax_calcularPrecio(xajax.getFormValues(&#39;frmNuevaSolicitud&#39;))'>",
					$rows["id_articulo"],$rows["precio_sugerido"]);
					
	$inputfecha = sprintf("<input type='text' class='inputHabilitado' readonly='readonly' id='txtFecha%s' name='txtFecha[]' size='10px' value='%s' style='text-align:center; border:0px'>",
					$rows["id_articulo"],$rows["fecha_requerida"]);
				
	$trTd .= "<tr id='tr".$rows["id_articulo"]."' class='textoGris_11px trEliminar'>";
		$trTd .= "<td align='center'>".$inputTextCant."</td>";
		$trTd .= "<td align='center'>".$rows["unidad"]."</td>";
		$trTd .= "<td align='center'>".utf8_encode($rows["codigo_articulo"])."</td>";
		$trTd .= "<td align='center' colspan='3'>".utf8_encode(trim($rows["descripcion"]))."</td>";
		$trTd .= "<td>".$inputTextpresio."</td>";
		$trTd .= "<td align='center'>".$inputfecha."</td>";
		$trTd .= "<td align='center'>".$inputnCheck.$inputText."</td>";
	$trTd .= "</tr>";
		
	$objResponse->script('$("#'.$nombTab.'").append("'.$trTd.'");'); 
	
	$objResponse->script('
				new JsDatePick({
				useMode:2,
				target:"txtFecha'.$rows["id_articulo"].'",
				dateFormat:"'.spanDatePick.'",
				cellColorScheme:"armygreen"
				});
				$(".JsDatePickBox").css("left",-100);
			');

	$objResponse->assign("artAgregadoSolComp","value",1);
	
	return $objResponse;
}

function combLstEstCompra() {//ESTADO DE COMPRA
	$objResponse = new xajaxResponse();
	
	$html .= "<select id=\"lisEstCompras\" name=\"lisEstCompras\" class=\"inputHabilitado\" onChange=\"byId('btnBuscar').click();\">";
	$html .= "<option value=\"\">[ Seleccione ]</option>";
		
	$query ="SELECT * FROM ga_estado_solicitud_compra WHERE id_estado_solicitud_compras IN (1,2,3,4);";
	
	mysql_query("SET NAMES 'utf8'");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");

	while($rows = mysql_fetch_array($rs)){
		$html .= "<option value=".$rows["id_estado_solicitud_compras"].">".$rows["estado_solicitud_compras"]."</option>";
	}
	
	$html .= "</select>";
		
	$objResponse->assign("tdLisEstado","innerHTML",$html);

	return $objResponse;
}

function combLstTipCompra() { //TIPO COMPRAS
	$objResponse = new xajaxResponse();
	
	$html .= "<select id=\"lisTipCompras\" name=\"lisTipCompras\" class=\"inputHabilitado\" onChange=\"byId('btnBuscar').click();\" >";
	$html .= "<option value=\"\">[ Seleccione ]</option>";
		
	$query ="SELECT * FROM ga_tipo_seccion where id_tipo_seccion IN (2,3,4);";
	
	mysql_query("SET NAMES 'utf8'");
	$rs = mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1';");
	
	while($rows = mysql_fetch_array($rs)){
		$html .= "<option value=".$rows["id_tipo_seccion"].">".$rows["tipo_seccion"]."</option>";
	}
	
	$html .= "</select>";
		
	$objResponse->assign("tdlsttipCompra","innerHTML",$html);

	return $objResponse;
}

function combLstProveedor($selId = "") { //TIPO COMPRAS
	$objResponse = new xajaxResponse();
	
	$html .= "<select id=\"lisProveedor\" name=\"lisProveedor\" class=\"inputHabilitado\">";
	$html .= "<option value=\"\">[ Seleccione ]</option>";
		
	$query ="SELECT id_proveedor, nombre FROM cp_proveedor ORDER BY nombre;;";
	$rs = mysql_query($query);
	
	while($rows = mysql_fetch_array($rs)){
		$selected = "";
		if($selId == $rows["id_proveedor"]){
			$selected = "selected='selected'";
		}
		$html .= "<option ".$selected." value=".$rows["id_proveedor"].">".utf8_encode($rows["nombre"])."</option>";
	}
	
	$html .= "</select>";
		
	$objResponse->assign("tdProveedor","innerHTML",$html);

	return $objResponse;
}

function guardarSolicitud($formval){
	$objResponse = new xajaxResponse();
	
	$sqlEmpl = sprintf("SELECT id_empleado FROM pg_usuario WHERE id_usuario = %s",$_SESSION['idUsuarioSysGts']);
	
	mysql_query("SET NAMES 'utf8'");
	$rsEmpl = mysql_query($sqlEmpl);
	if (!$rsEmpl) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1'");
	$rowsEmpl = mysql_fetch_array($rsEmpl);
	
	mysql_query("START TRANSACTION;");
		
	//EDITA LOS DATOS DE LA SOLICITUD
	if($formval['idSolicitudCompras'] > 0){
		if (!xvalidaAcceso($objResponse,"ga_solicitud_compra_list","editar")) { return $objResponse; }
			$sql = sprintf("UPDATE ga_solicitud_compra SET
						id_empresa = %s,
						fecha_modificacion = NOW(),
						id_unidad_centro_costo = %s,
						tipo_compra = %s,
						id_proveedor = %s,
						presupuestado = %s,
						sustitucion = %s,
						observaciones = %s,
						justificacion_compra = %s,
						justificacion_proveedor = %s,
						id_empleado_solicitud = %s
					WHERE id_solicitud_compra=%s",
						valTpDato($formval['idEmpresa'], "int"),
						valTpDato($formval['idCentroCosto'], "int"),
						valTpDato($formval['tipoCompra'], "int"),
						valTpDato($formval['lisProveedor'], "int"),
						valTpDato($formval['presupuestado'], "int"),
						valTpDato($formval['sustitucion'], "int"),
						valTpDato($formval['ObservacionProveedor'],"text"),
						valTpDato($formval['justificacionCompra'],"text"),
						valTpDato($formval['justificacionProveedor'],"text"),
					$rowsEmpl['id_empleado'],
						valTpDato($formval['idSolicitudCompras'], "int"));
			mysql_query("SET NAMES 'utf8'");
			$rs = mysql_query($sql);
					
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			mysql_query("SET NAMES 'latin1';");
	} else {//guardar
		if (!xvalidaAcceso($objResponse,"ga_solicitud_compra_list","insertar")){ return $objResponse; }
		if($formval['tipoCompra'] == ""){
			return $objResponse->alert("Debe seleccionar un tipo de compra");	
		}
		if($formval['artAgregadoSolComp'] == 0){
			return $objResponse->alert("Debe agregar por lomeno un material o un servicio a la solicitud");	
		} else { 
			//VALIDA LA CANTIDAD
			foreach($formval['textArtCant'] as $indiceCan => $valorCan){
				if($valorCan == "" || $valorCan == 0){
					return $objResponse->alert("Debe especificar cantidad de articulo y debe ser mayor cero");
				}
			} 
			//VALIDA LA PRECIO
			foreach ($formval['textArtPrecio'] as $indicePre => $valorPre){
					if($valorPre == "" || $valorPre == 0){
						return $objResponse->alert("No debe existir articulo sin Precio y debe ser mayor cero");
					}						
			}
		}
		$sqlNumSol = sprintf("SELECT max(numero_solicitud)+1 AS numSolicitud FROM vw_ga_solicitudes WHERE id_empresa = %s",
			valTpDato($formval['idEmpresa'],"int"));
		$queryNumSol = mysql_query($sqlNumSol);
		if (!$queryNumSol) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$rowsNumSol = mysql_fetch_array($queryNumSol);
		
		//INSERTA LOS DATOS DE LA SOLICITUD
		$sqlSolComp = sprintf("INSERT INTO ga_solicitud_compra (id_empresa, id_estado_solicitud_compras, fecha_empleado_solicitud, fecha_creacion, fecha_solicitud, numero_solicitud, id_unidad_centro_costo, tipo_compra, id_proveedor, presupuestado, sustitucion, observaciones, justificacion_compra, justificacion_proveedor,id_empleado_solicitud)
			VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
		valTpDato($formval['idEmpresa'], "int"),
		valTpDato(1, "int"),  // 1 = Solicitada, 2 = Aprobado, 3 = Conformado, 4 = Procesado, 5 = Ordenada, 6 = Condicionado, 7 = Rechazado
		valTpDato("NOW()", "campo"),
		valTpDato("NOW()", "campo"),
		valTpDato("NOW()", "campo"),
		valTpDato($rowsNumSol['numSolicitud'],"int"),
		valTpDato($formval['idCentroCosto'],"int"),
		valTpDato($formval['tipoCompra'],"int"),
		valTpDato($formval['lisProveedor'],"int"),
		valTpDato($formval['presupuestado'],"int"),
		valTpDato($formval['sustitucion'],"int"),
		valTpDato($formval['ObservacionProveedor'],"text"),
		valTpDato($formval['justificacionCompra'],"text"),
		valTpDato($formval['justificacionProveedor'],"text"),
		valTpDato($rowsEmpl['id_empleado'],"int"));
		mysql_query("SET NAMES 'utf8'");
		$query = mysql_query($sqlSolComp);
		if (!$query) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$idSolCompras = mysql_insert_id();
		mysql_query("SET NAMES 'latin1';");
		//PARA GUARDAR LOS ARTICULOS GUARDADOS
		if($formval['artAgregadoSolComp'] != 0){
			foreach ($formval['textcheckArticulo'] as $indice => $valor){
				$valorArt = explode("|",$valor);
				$cant = $formval['textArtCant'][$indice];
				$precio = $formval['textArtPrecio'][$indice];
				$fechRequerida = $formval['txtFecha'][$indice];
				
				if($valorArt[1] == 0){
					if($cant == ""){
						return $objResponse->alert("Debe indicar la cantidad de Articulos");
					}
					if($precio == ""){
						return $objResponse->alert("Debe Indicar el Precio Unitario de los articulo");
					}
					
					$sqlDetArt = sprintf("INSERT INTO ga_detalle_solicitud_compra (id_solicitud_compra, id_articulo, cantidad, precio_sugerido, fecha_requerida) 
						VALUES (%s, %s, %s, %s, '%s');",
						$idSolCompras,
						$valorArt[0],
						$cant,
						$precio,
						date("Y-m-d", strtotime($fechRequerida)));
					$queryDetArt = mysql_query($sqlDetArt);
					if (!$queryDetArt) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);	
				}
			}
		}
	}
	mysql_query("COMMIT;");
	
	$objResponse->alert("Fuente de Informacion Guardada con Éxito");
	$objResponse->loadCommands(listadoSolicitudCompra(0,'','',$_SESSION['idEmpresaUsuarioSysGts'])); 
	$objResponse->script("byId('btnCancelar').click();");	
		
	return $objResponse;
}

function insertarArtSolicitud($idArticulo){ //AGREGA ART A LA TABLA SELECCIONADO
	$objResponse = new xajaxResponse();

	$query = sprintf("SELECT * FROM vw_ga_articulos WHERE id_articulo = %s;",$idArticulo);
	$rs =mysql_query($query);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$nombTab = "tabTipoCompra";

	$rows = mysql_fetch_array($rs);
	$inputnCheck = sprintf("<input type='checkbox' value='%s|0' id='checkArticulo%s' name='checkArticulo[]'>",
					$rows["id_articulo"],$rows["id_articulo"]);
				
	$inputText = sprintf("<input type='hidden' value='%s|0' id='textcheckArticulo%s' name='textcheckArticulo[]'>",
					$rows["id_articulo"],$rows["id_articulo"]);
					
	$inputTextCant = sprintf("<input type='text' class='inputHabilitado' id='textArtCant%s' name='textArtCant[]' size='5px' style='text-align:center; border:0px'>",
					$rows["id_articulo"],$rows["id_articulo"]);
					
	$inputTextpresio = sprintf("<input type='text' class='inputHabilitado'  id='textArtPrecio%s' name='textArtPrecio[]' size='10px' style='text-align:center; border:0px' onchange='xajax_calcularPrecio(xajax.getFormValues(&#39;frmNuevaSolicitud&#39;))'>",
					$rows["id_articulo"],$rows["id_articulo"]);
					
	$inputfecha = sprintf("<input type='text' class='inputHabilitado' readonly='readonly' id='txtFecha%s' name='txtFecha[]' size='10px' value='%s' style='text-align:center; border:0px'>",
					$rows["id_articulo"],generarFecha());
				
	$trTd .= "<tr id='tr".$rows["id_articulo"]."' class='textoGris_11px trEliminar'>";
		$trTd .= "<td align='center'>".$inputTextCant."</td>";
		$trTd .= "<td align='center'>".$rows["unidad"]."</td>";
		$trTd .= "<td align='center'>".utf8_encode($rows["codigo_articulo"])."</td>";
		$trTd .= "<td align='center' colspan='3'>".utf8_encode(trim($rows["descripcion"]))."</td>";
		$trTd .= "<td>".$inputTextpresio."</td>";
		$trTd .= "<td align='center'>".$inputfecha."</td>";
		$trTd .= "<td align='center'>".$inputnCheck.$inputText."</td>";
	$trTd .= "</tr>";
		
	$objResponse->script('$("#'.$nombTab.'").append("'.$trTd.'");'); 
	$objResponse->assign("artAgregadoSolComp","value",1);
	
	$objResponse->script('
				new JsDatePick({
				useMode:2,
				target:"txtFecha'.$rows["id_articulo"].'",
				dateFormat:"'.spanDatePick.'",
				cellColorScheme:"armygreen"
				});
				$(".JsDatePickBox").css("left",-100);
			');	
			
	return $objResponse;
}

function fromSolicitud($tipo, $idSolCompras = ""){
	$objResponse = new xajaxResponse();
	
	$objResponse->script("
	document.forms['frmNuevaSolicitud'].reset();
	openImg(byId('divFlotante1'));
	eliminarTr('btnCancelar');
	
	$('#btnCentroCosto').hide();
	$('#btnEmpresa').show();
	$('#btnDepartamento').show();
	$('#btnAgregarArt').show();
	$('#btnAgregarElim').show();
	$('#btnGuardar').show();
	$('#tipoCompra4').attr('disabled', false);
	$('#tipoCompra3').attr('disabled', false);
	$('#tipoCompra2').attr('disabled', false)
	$('#tabTipoCompra').hide();
	$('#TabTotal').hide();
	$('#tabAprobacion').hide();
	");
	
	switch($tipo){
		case "nuevo"://nueva solicitus
			$objResponse->script("byId('idSolicitudCompras').value = ''");
			$objResponse->assign("tdFlotanteTitulo1","innerHTML","Nueva Solicitud De Compras y Servicios");
			$objResponse->assign("fechaSolicitud","value",date(spanDateFormat));
			
			$sql = sprintf("SELECT *, codigo_empresa
					FROM vw_iv_usuario_empresa
					LEFT JOIN pg_empresa ON pg_empresa.id_empresa = vw_iv_usuario_empresa.id_empresa
					WHERE id_usuario_empresa = %s",$_SESSION['idEmpresaUsuarioSysGts']);
			$rs = mysql_query($sql);
			$row = mysql_fetch_array($rs);
			
			$objResponse->assign("numSolicitud","value",$row['codigo_empresa']);
			$objResponse->assign("codEmpresa","value",$row['codigo_empresa']);
			$objResponse->assign("nombEmpresa","value",$row['nombre_empresa']);
			$objResponse->assign("idEmpresa","value",$row['id_empresa']);
			$objResponse->loadCommands(combLstProveedor());
				break;
		case "editar": //edita nueva solicitud
			$objResponse->script("
				byId('justificacionProveedor').className = 'inputHabilitado';
				byId('ObservacionProveedor').className = 'inputHabilitado';
				byId('justificacionCompra').className = 'inputHabilitado';");
				
			$objResponse->assign("tdFlotanteTitulo1","innerHTML","Editar Solicitud De Compras y Servicios");
			$objResponse->assign("idSolicitudCompras","value",$idSolCompras);
			$objResponse->loadCommands(cargarSolicitudCompras($idSolCompras));
		
			$sqlDetSolComp = sprintf("SELECT * FROM ga_detalle_solicitud_compra WHERE id_solicitud_compra = %s",$idSolCompras);
			$queryDetSolComp = mysql_query($sqlDetSolComp);
			while($rowsDetSolComp = mysql_fetch_array($queryDetSolComp)){
				$objResponse->loadCommands(cargarDetallesSolComp($idSolCompras,$rowsDetSolComp['id_articulo']));						
			}
			$objResponse->script("xajax_calcularPrecio(xajax.getFormValues('frmNuevaSolicitud'))");
				break;
		case "ver": //ver silicitud 
			$objResponse->script("
				byId('justificacionProveedor').className = 'inputInicial';
				byId('ObservacionProveedor').className = 'inputInicial';
				byId('justificacionCompra').className = 'inputInicial';
				
				$('#btnEmpresa').hide();
				$('#btnGuardar').hide();
				$('#btnCentroCosto').hide();
				$('#btnDepartamento').hide();
				$('#btnAgregarArt').hide();
				$('#btnAgregarElim').hide();
				");
			$objResponse->assign("tdFlotanteTitulo1","innerHTML","Ver Solicitud De Compras y Servicios");
			$objResponse->assign("idSolicitudCompras","value",$idSolCompras);
			$objResponse->loadCommands(cargarSolicitudCompras($idSolCompras));
			
			$sqlDetSolComp = sprintf("SELECT * FROM ga_detalle_solicitud_compra WHERE id_solicitud_compra = %s",$idSolCompras);
			$queryDetSolComp = mysql_query($sqlDetSolComp);
			while($rowsDetSolComp = mysql_fetch_array($queryDetSolComp)){
				$objResponse->loadCommands(cargarDetallesSolComp($idSolCompras,$rowsDetSolComp['id_articulo']));	
			}
			$objResponse->script("xajax_calcularPrecio(xajax.getFormValues('frmNuevaSolicitud'))");
			break;	
	}
		
	return $objResponse;
}
function eliminarArt($valorFrom){
	$objResponse = new xajaxResponse();	
	
	foreach ($valorFrom['checkArticulo'] as $indice => $valor) {
		$busArt = explode("|",$valor);
		
		if($busArt[1] == 0){ //ES NUEVO
			$objResponse->script("$('#tr".$busArt[0]."').remove();");
		} else { //VIENE DE BD
			$query = $sql.$busArt[0];
			$rs = mysql_query($query); 
			if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
			$objResponse->script("$('#tr".$busArt[0]."').remove();");
		}
	}
	
	$objResponse->script("xajax_calcularPrecio(xajax.getFormValues('frmNuevaSolicitud'))");
	
	return $objResponse;
}

function eliminarSolicitud($idSolicitudCompras){
	$objResponse = new xajaxResponse();
	
	if(!xvalidaAcceso($objResponse,"ga_solicitud_compra_list","eliminar")) { return $objResponse; }
		
	$query = sprintf("DELETE FROM ga_solicitud_compra WHERE id_solicitud_compra = %s", $idSolicitudCompras);
	$rs = mysql_query($query);
	if(!$rs){
		$objResponse->alert("No se puede eliminar el registro ya que existen otros registros dependientes, consulte al administrador del sistema");
	} else {
		$objResponse->alert("Se ha eliminado la solicitud");	
		$objResponse->loadCommands(listadoSolicitudCompra(0,'','',$_SESSION['idEmpresaUsuarioSysGts'])); 
	}
		
	return $objResponse;
}

function listadoArticulo($pageNum = 0, $campOrd = "codigo_articulo", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("estatus = 1");
	
	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf(" (id_articulo LIKE %s
		OR ga_articulos.descripcion LIKE %s
		OR ga_tipos_articulos.descripcion LIKE %s
		OR codigo_articulo LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}

	$query = sprintf("SELECT 
		id_articulo,
		codigo_articulo, 
		ga_articulos.descripcion AS articulos_descripcion, 
		ga_articulos.id_tipo_articulo, 
		ga_tipos_articulos.descripcion AS descripcion_tipos_articulos
	FROM ga_articulos
	LEFT JOIN ga_tipos_articulos ON ga_articulos.id_tipo_articulo = ga_tipos_articulos.id_tipo_articulo %s",$sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	
	$rsLimit = mysql_query($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoArticulo", "10%", $pageNum, "id_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listadoArticulo", "10%", $pageNum, "codigo_articulo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código Art");
		$htmlTh .= ordenarCampo("xajax_listadoArticulo", "50%", $pageNum, "articulos_descripcion", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción Art");
		$htmlTh .= ordenarCampo("xajax_listadoArticulo", "30%", $pageNum, "descripcion_tipos_articulos", $campOrd, $tpOrd, $valBusq, $maxRows, "Descripción tipo Art");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\"  height=\"24\">";
			$htmlTb .= "<td>".sprintf("<button type=\"button\" class=\"close\" 
			onclick=\"xajax_validarArt(%s,xajax.getFormValues('frmNuevaSolicitud'));byId('btnCancelar3').click();\" title=\"Seleccionar Articulo\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>",
			$row['id_articulo']);//	xajax_insertarArtSolicitud(%s)		
			$htmlTb .= "<td align=\"center\">".$row['id_articulo']."</td>";
			$htmlTb .= "<td align=\"center\">".utf8_encode($row['codigo_articulo'])."</td>";
			$htmlTb .= "<td align=\"left\">".utf8_encode($row['articulos_descripcion'])."</td>";
			$htmlTb .= "<td>".utf8_encode($row['descripcion_tipos_articulos'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoArticulo(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoArticulo(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
	
	$objResponse->script("openImg(byId('divFlotante3'));");
	$objResponse->assign("tdFlotanteTitulo3","innerHTML","Listado de Articulos");

	$objResponse->assign("tdListArticulo","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listadoEmpresas($pageNum = 0, $campOrd = "nombre_empresa", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if ($valCadBusq[0] != "-1" && $valCadBusq[0] != "") {
		$sqlBusq .= sprintf(" AND (id_empresa LIKE %s
		OR codigo_empresa LIKE %s
		OR rif LIKE %s
		OR nombre_empresa LIKE %s)",
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"),
			valTpDato("%".$valCadBusq[0]."%", "text"));
	}

	$query = sprintf("SELECT * FROM pg_empresa WHERE id_empresa != 100 %s",$sqlBusq);
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	mysql_query("SET NAMES 'utf8'");
	$rsLimit = mysql_query($queryLimit);
		//$objResponse->alert($queryLimit);
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	mysql_query("SET NAMES 'latin1'");
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "10%", $pageNum, "id_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "10%", $pageNum, "codigo_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "20%", $pageNum, "rif", $campOrd, $tpOrd, $valBusq, $maxRows, "RIF");
		$htmlTh .= ordenarCampo("xajax_listadoEmpresas", "60%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\"  height=\"24\">";
			$htmlTb .= "<td>".sprintf("<button type=\"button\" class=\"close\" 
			onclick=\"xajax_asignarEmpDepartamentoCento(%s,%s);byId('btnCancelar2').click();\" title=\"Seleccionar Empresa\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>",
			1,$row['id_empresa']);			
			$htmlTb .= "<td align=\"left\">".$row['id_empresa']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['codigo_empresa']."</td>";
			$htmlTb .= "<td align=\"left\">".$row['rif']."</td>";
			$htmlTb .= "<td>".($row['nombre_empresa'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoEmpresas(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
	$btn = "<button id=\"btnBuscarCentroCosto\" title=\"Buscar\" onclick=\"xajax_BuscarempDepaUnidadCentroCosto(xajax.getFormValues('frmBuscar2'),'Empresa');\" style=\"cursor:default\" name=\"btnBuscarCentroCosto\" type=\"button\">Buscar</button>";
	
	$objResponse->script("openImg(byId('divFlotante2'));");
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Empresa");
	$objResponse->assign("tdBtnBuscarCentroCosto","innerHTML",$btn);
	$objResponse->assign("empDepaUnidadCentroCosto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listadoDepartamento($pageNum = 0, $campOrd = "nombre_departamento", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;

	if($valCadBusq[0] != "" && $valCadBusq[0] != ""){
	$sqlBusq .= sprintf(" WHERE pg_departamento.id_empresa = %s",
		valTpDato($valCadBusq[0],"int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("  (pg_unidad_centro_costo.id_departamento LIKE %s
		OR codigo_departamento LIKE %s
		OR nombre_departamento LIKE %s)",
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"),
			valTpDato("%".$valCadBusq[1]."%", "text"));
	}
	
	$query = sprintf("SELECT id_unidad_centro_costo,pg_unidad_centro_costo.id_departamento,codigo_departamento,nombre_departamento
			FROM pg_unidad_centro_costo
		INNER JOIN pg_departamento ON pg_departamento.id_departamento = pg_unidad_centro_costo.id_departamento %s", $sqlBusq);
	mysql_query("SET NAMES 'utf8'");
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		//$objResponse->alert($query);
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	mysql_query("SET NAMES 'latin1'");
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoDepartamento", "5%", $pageNum, "id_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listadoDepartamento", "15%", $pageNum, "codigo_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listadoDepartamento", "80%", $pageNum, "nombre_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Departamento");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\"  height=\"24\">";
			$htmlTb .= sprintf("<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpDepartamentoCento(%s,%s);byId('btnCancelar2').click();vaciarCampo();\" title=\"Seleccionar Departamento\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>",
			2,$row['id_departamento']);
			$htmlTb .= "<td align=\"center\">".$row['id_departamento']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['codigo_departamento']."</td>";
			$htmlTb .= "<td align=\"left\">".($row['nombre_departamento'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoDepartamento(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoDepartamento(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
	
	$btn = "<button id=\"btnBuscarCentroCosto\" title=\"Buscar\" onclick=\"xajax_BuscarempDepaUnidadCentroCosto(xajax.getFormValues('frmBuscar2'),'Departamento');\" style=\"cursor:default\" name=\"btnBuscarCentroCosto\" type=\"button\">Buscar</button>";

	$objResponse->script("openImg(byId('divFlotante2'));");
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Departamentos");
	$objResponse->assign("tdBtnBuscarCentroCosto","innerHTML",$btn);

	$objResponse->assign("empDepaUnidadCentroCosto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listadoCentroCosto($pageNum = 0, $campOrd = "nombre_unidad_centro_costo", $tpOrd = "ASC", $valBusq = "", $maxRows = 10, $totalRows = NULL) {
	$objResponse = new xajaxResponse();
	
	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	if($valCadBusq[0] != "" && $valCadBusq[0] != ""){
	$sqlBusq .= sprintf(" WHERE id_departamento = %s",
		valTpDato($valCadBusq[0],"int"));
	}
	
	if ($valCadBusq[1] != "-1" && $valCadBusq[1] != "") {
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf(" (id_unidad_centro_costo LIKE %s
	OR codigo_unidad_centro_costo LIKE %s
	OR nombre_unidad_centro_costo LIKE %s)",
		valTpDato("%".$valCadBusq[1]."%", "text"),
		valTpDato("%".$valCadBusq[1]."%", "text"),
		valTpDato("%".$valCadBusq[1]."%", "text"));
	}

	$query = sprintf("SELECT * FROM pg_unidad_centro_costo %s", $sqlBusq);
	mysql_query("SET NAMES 'utf8'");
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
		
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	//$objResponse->alert($queryLimit);
	$rsLimit = mysql_query($queryLimit);

	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);

	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	mysql_query("SET NAMES 'latin1'");
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni .= "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		$htmlTh .= ordenarCampo("xajax_listadoCentroCosto", "5%", $pageNum, "id_unidad_centro_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Id");
		$htmlTh .= ordenarCampo("xajax_listadoCentroCosto", "15%", $pageNum, "codigo_unidad_centro_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Código");
		$htmlTh .= ordenarCampo("xajax_listadoCentroCosto", "80%", $pageNum, "nombre_unidad_centro_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Nombre Unidad Centro Costo");
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
	
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\"  height=\"24\">";
			$htmlTb .= sprintf("<td>"."<button type=\"button\" onclick=\"xajax_asignarEmpDepartamentoCento(%s,%s);byId('btnCancelar2').click();\" title=\"Seleccionar Centro de Costo\"><img src=\"../img/iconos/ico_aceptar.gif\"/></button>"."</td>",
			3,$row['id_unidad_centro_costo']);
			$htmlTb .= "<td align=\"center\">".$row['id_unidad_centro_costo']."</td>";
			$htmlTb .= "<td align=\"center\">".$row['codigo_unidad_centro_costo']."</td>";
			$htmlTb .= "<td align=\"left\">".($row['nombre_unidad_centro_costo'])."</td>";
		$htmlTb .= "</tr>";
	}
	
	$htmlTf .= "<tr>";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCentroCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCentroCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoCentroCosto(%s,'%s','%s','%s',%s)\">",
								"this.value", $campOrd, $tpOrd, $valBusq, $maxRows);
							for ($nroPag = 0; $nroPag <= $totalPages; $nroPag++) {
									$htmlTf.="<option value=\"".$nroPag."\"";
									if ($pageNum == $nroPag) {
										$htmlTf .= "selected=\"selected\"";
									}
									$htmlTf.= ">".($nroPag + 1)." / ".($totalPages + 1)."</option>";
							}
							$htmlTf .= "</select>";
							
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCentroCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoCentroCosto(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
	
	$btn = "<button id=\"btnBuscarCentroCosto\" title=\"Buscar\" onclick=\"xajax_BuscarempDepaUnidadCentroCosto(xajax.getFormValues('frmBuscar2'),'centroCosto');\" style=\"cursor:default\" name=\"btnBuscarCentroCosto\" type=\"button\">Buscar</button>";
	$btnLim = "<button id=\"btnLimpiar\" title=\"Limpiar\" style=\"cursor:default\" name=\"btnLimpiar\" type=\"button\" 
                    onblur=\"document.forms['frmBuscar2'].reset(); byId('btnBuscarCentroCosto').click();\">";
	/*
	<button id="btnLimpiar" title="Limpiar" style="cursor:default" name="btnLimpiar" type="button" 
                    onblur="document.forms['frmBuscar2'].reset(); byId('btnBuscarCentroCosto').click();">
                       Limpiar
                    </button>
	*/
	$objResponse->script("openImg(byId('divFlotante2'));");
	$objResponse->assign("tdFlotanteTitulo2","innerHTML","Departamentos");
	$objResponse->assign("tdBtnBuscarCentroCosto","innerHTML",$btn);
	
	$objResponse->assign("empDepaUnidadCentroCosto","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function listadoSolicitudCompra($pageNum = 0, $campOrd = "", $tpOrd = "", $valBusq = "", $maxRows = 20, $totalRows = NULL) {
	$objResponse = new xajaxResponse();

	$valCadBusq = explode("|", $valBusq);
	$startRow = $pageNum * $maxRows;
	
	$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
	$sqlBusq .= $cond.sprintf("vw_ga_solicitudes.id_estado_solicitud_compras IN(6,7)");
	
	if($valCadBusq[0] != "-1" && $valCadBusq[0] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("id_empresa = %s",
			valTpDato($valCadBusq[0],"int"));
	}
	
	if($valCadBusq[1] != "-1" && $valCadBusq[1] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("numero_solicitud = %s",
			valTpDato($valCadBusq[1],"int"));
	}
	
	if($valCadBusq[2] != "-1" && $valCadBusq[2] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("tipo_compra = %s",
			valTpDato($valCadBusq[2],"int"));
	}
	
	if($valCadBusq[3] != "-1" && $valCadBusq[3] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("vw_ga_solicitudes.id_estado_solicitud_compras = %s",
			valTpDato($valCadBusq[3],"int"));
	}

	if($valCadBusq[4] != ""){
		$cond = (strlen($sqlBusq) > 0) ? " AND " : " WHERE ";
		$sqlBusq .= $cond.sprintf("(numero_solicitud LIKE %s
			OR nombre_empresa LIKE %s
			OR nombre_departamento LIKE %s
			OR nombre_unidad_centro_costo LIKE %s
			OR cp_proveedor.id_proveedor LIKE %s
			OR cp_proveedor.nombre LIKE %s
			OR tipo_seccion LIKE %s
			OR estado_solicitud_compras LIKE %s)",
				valTpDato("%".$valCadBusq[4]."%","text"),
				valTpDato("%".$valCadBusq[4]."%","text"),
				valTpDato("%".$valCadBusq[4]."%","text"),
				valTpDato("%".$valCadBusq[4]."%","text"),
				valTpDato("%".$valCadBusq[4]."%","text"),
				valTpDato("%".$valCadBusq[4]."%","text"),
				valTpDato("%".$valCadBusq[4]."%","text"),
				valTpDato("%".$valCadBusq[4]."%","text"));
	}

	$query = sprintf("SELECT vw_ga_solicitudes.*, cp_proveedor.nombre AS nombre_proveedor,
 						CONCAT_WS('-', codigo_empresa, numero_solicitud) AS numero_solicitud,
						ga_estado_solicitud_compra.estado_solicitud_compras, ga_tipo_seccion.tipo_seccion
					FROM vw_ga_solicitudes
						LEFT JOIN cp_proveedor ON (cp_proveedor.id_proveedor = vw_ga_solicitudes.id_proveedor)
						INNER JOIN ga_estado_solicitud_compra ON (vw_ga_solicitudes.id_estado_solicitud_compras = ga_estado_solicitud_compra.id_estado_solicitud_compras)
						INNER JOIN ga_tipo_seccion ON (vw_ga_solicitudes.tipo_compra = ga_tipo_seccion.id_tipo_seccion)
					%s",$sqlBusq);

	mysql_query("SET NAMES 'utf8'");
	
	$sqlOrd = ($campOrd != "") ? sprintf(" ORDER BY %s %s", $campOrd, $tpOrd) : "";
	$queryLimit = sprintf("%s %s LIMIT %d OFFSET %d", $query, $sqlOrd, $maxRows, $startRow);
	$rsLimit = mysql_query($queryLimit);
	
	if (!$rsLimit) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	if ($totalRows == NULL) {
		$rs = mysql_query($query);
		if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
		$totalRows = mysql_num_rows($rs);
	}
	
	mysql_query("SET NAMES 'latin1'");
	
	$totalPages = ceil($totalRows/$maxRows)-1;
	
	$htmlTblIni = "<table border=\"0\" width=\"100%\">";
	$htmlTh .= "<tr align=\"center\" class=\"tituloColumna\">";
		$htmlTh .= "<td></td>";
		
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "20%", $pageNum, "nombre_empresa", $campOrd, $tpOrd, $valBusq, $maxRows, "Empresa");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "5%", $pageNum, "numero_solicitud", $campOrd, $tpOrd, $valBusq, $maxRows, "Nro.");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "15%", $pageNum, "nombre_departamento", $campOrd, $tpOrd, $valBusq, $maxRows, "Departamento");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "10%", $pageNum, "nombre_unidad_centro_costo", $campOrd, $tpOrd, $valBusq, $maxRows, "Centro Costo");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "25%", $pageNum, "nombre_proveedor", $campOrd, $tpOrd, $valBusq, $maxRows, "Proveedor ");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "15%", $pageNum, "tipo_seccion", $campOrd, $tpOrd, $valBusq, $maxRows, "Tipo Compra");
		$htmlTh .= ordenarCampo("xajax_listadoSolicitudCompra", "10%", $pageNum, "estado_solicitud_compras", $campOrd, $tpOrd, $valBusq, $maxRows, "Estado");
		$htmlTh .= "<td class=\"noprint\" colspan=\"5\"></td>";
	$htmlTh .= "</tr>";
	
	$contFila = 0;
	while ($row = mysql_fetch_assoc($rsLimit)) {
		$clase = (fmod($contFila, 2) == 0) ? "trResaltar4" : "trResaltar5";
		$contFila++;
		
		switch ($row['id_estado_solicitud_compras']) {
			case 1 : $imgEstatus = "<img src=\"../img/iconos/ico_aceptar_azul.png\" title=\"Solicitada\"/>"; break;
			case 2 : $imgEstatus = "<img src=\"../img/iconos/ico_aceptar.gif\" title=\"Aprobado\"/>"; break;
			case 3 : $imgEstatus = "<img src=\"../img/iconos/ico_aceptar_amarillo.png\" title=\"Conformado\"/>"; break;
			case 4 : $imgEstatus = "<img src=\"../img/iconos/ico_aceptar_naranja.png\" title=\"Procesado\"/>"; break;
			case 6 : $imgEstatus = "<img src=\"../img/iconos/ico_aceptar_f2.gif\" title=\"Condicionado\"/>"; break;
			case 7 : $imgEstatus = "<img src=\"../img/iconos/ico_error.gif\" title=\"Rechazado\"/>"; break;
		}
		
		$htmlTb .= "<tr align=\"left\" class=\"".$clase."\"  height=\"24\">";
			$htmlTb .= "<td align=\"center\">".$imgEstatus."</td>";
			$htmlTb .= "<td>".($row['nombre_empresa'])."</td>";
			$htmlTb .= "<td align=\"center\">".$row['numero_solicitud']."</td>";
			$htmlTb .= "<td align=\"\">".($row['nombre_departamento'])."</td>";
			$htmlTb .= "<td align=\"\">".($row['nombre_unidad_centro_costo'])."</td>";
			$htmlTb .= "<td align=\"\" >".($row["id_proveedor"].".- ".$row['nombre_proveedor'])."</td>";
			$htmlTb .= "<td align=\"\">".($row['tipo_seccion'])."</td>";
			$htmlTb .= "<td align=\"\">".($row['estado_solicitud_compras'])."</td>";
			$htmlTb .= sprintf("<td><img class=\"puntero modalImg\"rel=\"#divFlotante\" id=\"imgAprobSolicitud%s\" onclick=\"xajax_aporbarSolicitudCompras(%s)\" src=\"../img/iconos/accept.png\" title=\"Aprobar Solicitud\"/></td>",
				$contFila,
				$row['id_solicitud_compra'],
				$contFila);
			$htmlTb .= sprintf("<td><img class=\"puntero\" id=\"imgVerSolicitud%s\" onclick=\"xajax_fromSolicitud('ver',%s);\" src=\"../img/iconos/ico_view.png\" title=\"Ver Solicitud\"/></td>",
				$contFila,
				$row['id_solicitud_compra']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" id=\"imgSolicitudPDF%s\" onclick=\"abrePdf(%s,%s)\" src=\"../img/iconos/page_white_acrobat.png\" title=\"Solicitud PDF\"/></td>",
				$contFila,
				$row['id_solicitud_compra'],
				$_SESSION['idEmpresaUsuarioSysGts']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" id=\"imgEditarArticulo%s\" name =\"imgEditarArticulo\" onclick=\"xajax_fromSolicitud('editar',%s);\" src=\"../img/iconos/ico_edit.png\" title=\"Editar Solicitud\"/></td>",
				$contFila,
				$row['id_solicitud_compra'],
				$row['id_empresa']);
			$htmlTb .= sprintf("<td><img class=\"puntero\" id=\"imgEliminarSolicitud%s\" onclick=\"valEliminarSolicitud(%s)\" src=\"../img/iconos/ico_delete.png\" title=\"Eliminar Solicitud\"/></td>",
				$contFila,
				$row['id_solicitud_compra'],
				$contFila);
		$htmlTb .= "</tr>";
	}
	
	$htmlTf = "<tr>";
		$htmlTf .= "<td align=\"center\" colspan=\"13\">";
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
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								0, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_pri_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum > 0) { 
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								max(0, $pageNum - 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ant_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"100\">";
						
							$htmlTf .= sprintf("<select id=\"pageNum\" name=\"pageNum\" onchange=\"xajax_listadoSolicitudCompra(%s,'%s','%s','%s',%s)\">",
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
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								min($totalPages, $pageNum + 1), $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_sig_compras.gif\"/>");
						}
						$htmlTf .= "</td>";
						$htmlTf .= "<td width=\"25\">";
						if ($pageNum < $totalPages) {
							$htmlTf .= sprintf("<a class=\"puntero\" onclick=\"xajax_listadoSolicitudCompra(%s,'%s','%s','%s',%s);\">%s</a>",
								$totalPages, $campOrd, $tpOrd, $valBusq, $maxRows, "<img src=\"../img/iconos/ico_reg_ult_compras.gif\"/>");
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
		$htmlTb .= "<td colspan=\"12\">";
			$htmlTb .= "<table cellpadding=\"0\" cellspacing=\"0\" class=\"divMsjError\" width=\"100%\">";
			$htmlTb .= "<tr>";
				$htmlTb .= "<td width=\"25\"><img src=\"../img/iconos/ico_fallido.gif\" width=\"25\"/></td>";
				$htmlTb .= "<td align=\"center\">No se encontraron registros</td>";
			$htmlTb .= "</tr>";
			$htmlTb .= "</table>";
		$htmlTb .= "</td>";
	}
			
	$objResponse->assign("tdListSolictComp","innerHTML",$htmlTblIni.$htmlTf.$htmlTh.$htmlTb.$htmlTf.$htmlTblFin);
	
	return $objResponse;
}

function procesarSolicitud($fromVal){
	$objResponse = new xajaxResponse();

	$idEstado = $fromVal['idEstadoSolicitud'];
	
	if ($fromVal['idEstadoSolicitud'] != ""){
		$estadoNuevo = ($idEstado + 1);
	} else {
		$estadoNuevo = $fromVal['cambiarestado'];
	}

	switch ($estadoNuevo){
		case 1: //Solicitud/
			$campo = "id_empleado_solicitud";
			$campof = "fecha_empleado_solicitud";
			$mensaje = "Solicitud Enviada";
			break;
			//caso 1 automatico ya no se cumple
		case 2://aprobacion
			if(!xvalidaAcceso($objResponse,"ga_solicitud_compra_list_aprobar")){//valida acceso
				return $objResponse;
			}
			$campo = "id_empleado_aprobacion";
			$campof = "fecha_empleado_aprobacion";
			$mensaje = "Solicitud Aprobada";
			break;
		case 3://conformacion
			if(!xvalidaAcceso($objResponse,"ga_solicitud_compra_list_conformar")){//valida acceso
				return $objResponse;
			}
			$campo = "id_empleado_conformacion";
			$campof = "fecha_empleado_conformacion";
			$mensaje = "Solicitud Conformada";
			
			break;
		case 4://proceso
			if(!xvalidaAcceso($objResponse,"ga_solicitud_compra_list_procesar")){//valida acceso
				return $objResponse;
			}
			$campo = "id_empleado_proceso";
			$campof = "fecha_empleado_proceso";
			$mensaje = "Solicitud Procesada";
			break;
		case 6://condiocionada
			if(!xvalidaAcceso($objResponse,"ga_solicitud_compra_list_procesar")){//valida acceso
				return $objResponse;
			}
			
			$campo = "id_empleado_condicionamiento";
			$campof = "fecha_empleado_condicionamiento";
			$mensaje = "Solicitud condiocionada";
			
			if($fromVal['motivoCondicionamientoRechazo'] == ""){//valdia el campo
				return $objResponse->alert("Debe Especificar un Motivo Condicionamiento");
			}
			break;	
		case 7://rechazado
			if(!xvalidaAcceso($objResponse,"ga_solicitud_compra_list_procesar")){//valida acceso
				return $objResponse;
			}
			$campo = "id_empleado_condicionamiento";
			$campof = "fecha_empleado_condicionamiento";
			$mensaje = "Solicitud rechazado";
			
			if($fromVal['motivoCondicionamientoRechazo'] == ""){
				return $objResponse->alert("Debe Especificar un Motivo rechazo");
			}
			break;					
	}
		
	if($fromVal['codEmpleado'] == ""){//valida que el campo no esta basio
		$objResponse->alert("Debe Especificar el Codigo de Empleado");
	}
		
	$sqlCodEmpl = sprintf("SELECT * FROM pg_empleado WHERE codigo_empleado = %s ",
	valTpDato($fromVal['codEmpleado'], "text"));
	$rsCodEmpl = mysql_query($sqlCodEmpl);
	if (!$rsCodEmpl) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	$rowsCodEmpl = mysql_fetch_array($rsCodEmpl);
	
	if($rowsCodEmpl['codigo_empleado'] == NULL){
		return $objResponse->alert("No Existe Empleado con ese Codigo");
	}

	$sqlEmplSys = sprintf("SELECT * FROM pg_usuario WHERE id_usuario = %s ",$_SESSION['idUsuarioSysGts']);
	mysql_query("SET NAMES 'utf8'");
	$rsEmplSys = mysql_query($sqlEmplSys);
	if (!$rsEmplSys)return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	mysql_query("SET NAMES 'latin1'");
	$rowsEmplSys = mysql_fetch_array($rsEmplSys);
	
	if($rowsEmplSys['id_empleado'] != $rowsCodEmpl['id_empleado']){
		return $objResponse->alert("Dede ingresa al sistema con su calve de usuario");
	}
	
	//ACTUALIZA EL ESTADO DE LA SOLICITUD
	$sql = sprintf("UPDATE ga_solicitud_compra SET
			id_estado_solicitud_compras = %s,
			%s = %s,
			%s = CURRENT_DATE(),
			motivo_condicionamiento = %s
		WHERE id_solicitud_compra = %s;",
		valTpDato($estadoNuevo, "int"),
		$campo,
		valTpDato($rowsEmplSys['id_empleado'], "int"),
		$campof,
		valTpDato($fromVal['motivoCondicionamientoRechazo'], "text"),
		valTpDato($fromVal['idSolicitudCompra'], "int"));
		
	$rs = mysql_query($sql);
	if (!$rs) return $objResponse->alert(mysql_error()."\n\nLine: ".__LINE__);
	
	$objResponse->alert($mensaje);
	$objResponse->script("byId('btnCanProceSolComp').click();");
	$objResponse->loadCommands(listadoSolicitudCompra(0,'','',$_SESSION['idEmpresaUsuarioSysGts'])); 
	
	return $objResponse;
}

function validarArt($idArtSelect,$formArti){
	$objResponse = new xajaxResponse();
		 
	$arrayArt = array();
	
	foreach($formArti['textcheckArticulo'] as $indice => $valores){
		$idArt = explode("|", $valores);
		$arrayArt[$idArt[0]] = "";
	}
	
	if(array_key_exists($idArtSelect,$arrayArt)){
		return $objResponse->alert("Este articulo ya esta agregado al listado");
	}	
	
	$objResponse->loadCommands(insertarArtSolicitud($idArtSelect));

	return $objResponse;
}

$xajax->register(XAJAX_FUNCTION,"asignarEmpDepartamentoCento");
$xajax->register(XAJAX_FUNCTION,"aporbarSolicitudCompras");
$xajax->register(XAJAX_FUNCTION,"buscarArticulo");
$xajax->register(XAJAX_FUNCTION,"BuscarempDepaUnidadCentroCosto");
$xajax->register(XAJAX_FUNCTION,"BuscarSolicituComp");
$xajax->register(XAJAX_FUNCTION,"calcularPrecio");
$xajax->register(XAJAX_FUNCTION,"cargarSolicitudCompras");
$xajax->register(XAJAX_FUNCTION,"cargarDetallesSolComp");
$xajax->register(XAJAX_FUNCTION,"combLstEstCompra");
$xajax->register(XAJAX_FUNCTION,"combLstTipCompra");
$xajax->register(XAJAX_FUNCTION,"fromSolicitud");
$xajax->register(XAJAX_FUNCTION,"insertarArtSolicitud");
$xajax->register(XAJAX_FUNCTION,"guardarSolicitud");
$xajax->register(XAJAX_FUNCTION,"eliminarArt");
$xajax->register(XAJAX_FUNCTION,"eliminarSolicitud");
$xajax->register(XAJAX_FUNCTION,"listadoArticulo");
$xajax->register(XAJAX_FUNCTION,"listadoEmpresas");
$xajax->register(XAJAX_FUNCTION,"listadoDepartamento");
$xajax->register(XAJAX_FUNCTION,"listadoCentroCosto");
$xajax->register(XAJAX_FUNCTION,"listadoSolicitudCompra");
$xajax->register(XAJAX_FUNCTION,"procesarSolicitud");
$xajax->register(XAJAX_FUNCTION,"validarArt");

function generarFecha(){ //suma 8 dias a la fecha actual
	$fechaHoy = date(spanDateFormat);	
	
	for($i=1; $i <= 8;  $i++){
		$fechaHoy = date(spanDateFormat, strtotime($fechaHoy."+ 1 day"));	
		$diaSemana = date("w", strtotime($fechaHoy));
		if($diaSemana == 0){//domingo
			$fechaHoy = date(spanDateFormat,strtotime($fechaHoy."+ 1 day"));
		}elseif($diaSemana == 6){//sabado
			$fechaHoy = date(spanDateFormat,strtotime($fechaHoy."+ 2 day"));
		}
	} 	
	
	return $fechaHoy;
}

?>